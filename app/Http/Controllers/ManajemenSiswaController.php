<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ManajemenSiswaController extends Controller
{
    /**
     * Daftar semua siswa
     */
    public function index(Request $request)
    {
        $query = Siswa::with(['user', 'kelas.jurusan']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $sortBy = $request->get('sort', 'nama');
        $sortDir = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        switch ($sortBy) {
            case 'nisn':
            case 'nis':
            case 'nama':
                $query->orderBy($sortBy, $sortDir);
                break;
            case 'kelas':
                $query->select('siswas.*')
                    ->join('kelas', 'kelas.id', '=', 'siswas.kelas_id')
                    ->orderBy('kelas.nama_kelas', $sortDir);
                break;
            case 'status':
                $query->select('siswas.*')
                    ->leftJoin('users', 'users.id', '=', 'siswas.user_id')
                    ->orderBy('users.is_active', $sortDir);
                break;
            case 'last_login':
                $query->select('siswas.*')
                    ->leftJoin('users', 'users.id', '=', 'siswas.user_id')
                    ->orderBy('users.last_login', $sortDir);
                break;
            default:
                $sortBy = 'nama';
                $query->orderBy('nama', $sortDir);
                break;
        }

        $siswas = $query->paginate(20)->withQueryString();
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->orderBy('nama_kelas')->get();

        return view('admin.siswa.index', compact('siswas', 'kelasList', 'sortBy', 'sortDir'));
    }

    /**
     * Form tambah siswa baru
     */
    public function create()
    {
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->orderBy('nama_kelas')->get();
        return view('admin.siswa.create', compact('kelasList'));
    }

    /**
     * Simpan siswa baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:20|unique:siswas,nisn',
            'nis' => 'required|string|unique:siswas,nis',
            'kelas_id' => 'required|exists:kelas,id',
        ], [
            'nisn.unique' => 'NISN sudah terdaftar di sistem.',
            'nis.unique' => 'NIS sudah terdaftar di sistem.',
        ]);

        $plainPassword = strtoupper(Str::random(8));
        $autoEmail = 'siswa.' . $request->nisn . '@cbtsmk.local';

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $autoEmail,
                'password' => Hash::make($plainPassword),
                'plain_password' => $plainPassword,
                'role' => 'siswa',
                'is_active' => true,
            ]);

            Siswa::create([
                'nis' => $request->nis,
                'nisn' => $request->nisn,
                'nama' => $request->name,
                'kelas_id' => $request->kelas_id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            ActivityLog::log('create', 'siswa', "Membuat akun siswa: {$user->name} (NISN: {$request->nisn})");

            return redirect()->route('admin.siswa.credential', $user)
                ->with('generated_password', $plainPassword);

        }
        catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Gagal membuat akun siswa: ' . $e->getMessage());
        }
    }

    /**
     * Form edit siswa
     */
    public function edit(Siswa $siswa)
    {
        $siswa->load(['user', 'kelas.jurusan']);
        $kelasList = Kelas::with('jurusan')
            ->where(function ($q) use ($siswa) {
            $q->where('is_active', true)
                ->orWhere('id', $siswa->kelas_id);
        })
            ->orderBy('nama_kelas')
            ->get();
        return view('admin.siswa.edit', compact('siswa', 'kelasList'));
    }

    /**
     * Update data siswa
     */
    public function update(Request $request, Siswa $siswa)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:20|unique:siswas,nisn,' . $siswa->id,
            'nis' => 'required|string|unique:siswas,nis,' . $siswa->id,
            'kelas_id' => 'required|exists:kelas,id',
        ]);

        DB::beginTransaction();
        try {
            $siswa->update([
                'nama' => $request->name,
                'nisn' => $request->nisn,
                'nis' => $request->nis,
                'kelas_id' => $request->kelas_id,
            ]);

            if ($siswa->user) {
                $siswa->user->update([
                    'name' => $request->name,
                    'is_active' => $request->boolean('is_active'),
                ]);
            }

            DB::commit();

            ActivityLog::log('update', 'siswa', "Mengupdate siswa: {$request->name} (NISN: {$request->nisn})");

            return redirect()->route('admin.siswa.index')->with('success', 'Data siswa berhasil diupdate!');

        }
        catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Hapus siswa + user terkait
     */
    public function destroy(Siswa $siswa)
    {
        $nama = $siswa->nama;

        DB::beginTransaction();
        try {
            if ($siswa->user) {
                $siswa->user->delete();
            }
            $siswa->delete();
            DB::commit();

            ActivityLog::log('delete', 'siswa', "Menghapus siswa: {$nama}");

            return redirect()->route('admin.siswa.index')->with('success', "Siswa {$nama} berhasil dihapus!");
        }
        catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }

    /**
     * Toggle aktif/nonaktif
     */
    public function toggleActive(Siswa $siswa)
    {
        if ($siswa->user) {
            $siswa->user->update(['is_active' => !$siswa->user->is_active]);
            $status = $siswa->user->is_active ? 'diaktifkan' : 'dinonaktifkan';
            ActivityLog::log('update', 'siswa', "Siswa {$siswa->nama} {$status}");

            return back()->with('success', "Siswa {$siswa->nama} berhasil {$status}!");
        }

        return back()->with('error', 'Akun user tidak ditemukan.');
    }

    /**
     * Tampilkan halaman kredensial
     */
    public function showCredential(User $user)
    {
        if ($user->role !== 'siswa' || !session('generated_password')) {
            return redirect()->route('admin.siswa.index');
        }

        $siswa = $user->siswa;
        $generatedPassword = session('generated_password');

        return view('admin.siswa.credential', compact('user', 'siswa', 'generatedPassword'));
    }

    /**
     * Export data siswa ke Excel (mengikuti filter pencarian/kelas yang aktif)
     */
    public function export(Request $request)
    {
        $query = Siswa::with(['user', 'kelas.jurusan']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        $siswas = $query->orderBy('nama')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $headers = ['No', 'NISN', 'NIS', 'Nama Lengkap', 'Kelas', 'Username', 'Password'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        $row = 2;
        foreach ($siswas as $idx => $siswa) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValueExplicit('B' . $row, $siswa->nisn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, $siswa->nis, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $siswa->nama);
            $sheet->setCellValue('E' . $row, $siswa->kelas->nama_kelas ?? '-');
            $sheet->setCellValueExplicit('F' . $row, $siswa->nisn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $row, $siswa->user->plain_password ?? '-');

            $sheet->getStyle('G' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            ]);

            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        ActivityLog::log('export', 'siswa', "Export data siswa ke Excel ({$siswas->count()} data)");

        $fileName = 'data_siswa_' . date('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Reset password siswa
     */
    public function resetPassword(User $user)
    {
        if ($user->role !== 'siswa') {
            return back()->with('error', 'Reset password hanya untuk siswa.');
        }

        $plainPassword = strtoupper(Str::random(8));
        $user->update([
            'password' => Hash::make($plainPassword),
            'plain_password' => $plainPassword,
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        ActivityLog::log('update', 'siswa', "Reset password siswa: {$user->name}");

        return redirect()->route('admin.siswa.credential', $user)
            ->with('generated_password', $plainPassword);
    }
}
