<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\BankSoal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\SesiUjian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\DB;

class UjianController extends Controller
{
    public function index(Request $request)
    {
        $query = Ujian::with(['mapel', 'guru']);

        if (auth()->user()->isGuru() && auth()->user()->guru) {
            $query->where('guru_id', auth()->user()->guru->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('nama_ujian', 'like', "%{$request->search}%");
        }

        $ujians = $query->latest()->paginate(15);

        return view('ujian.index', compact('ujians'));
    }

    public function create()
    {
        $mapels = $this->getMapelsForUser();
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->get();
        $sesiList = SesiUjian::where('is_active', true)->orderBy('jam_mulai')->get();
        $guruList = \App\Models\Guru::orderBy('nama')->get();
        return view('ujian.create', compact('mapels', 'kelasList', 'sesiList', 'guruList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ujian' => 'required|string|max:255',
            'jenis_ujian' => 'required|in:harian,uts,uas,praktik,tryout,anbk,ukk',
            'mapel_id' => 'required|exists:mapels,id',
            'durasi_menit' => 'required|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'metode_soal' => 'required|in:random,manual',
            'jumlah_soal' => 'required_if:metode_soal,random|nullable|integer|min:1',
            'soal_ids' => 'required_if:metode_soal,manual|array',
            'soal_ids.*' => 'exists:bank_soals,id',
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'exists:kelas,id',
        ]);

        // Tentukan guru_id
        $guruId = null;
        if (auth()->user()->guru) {
            $guruId = auth()->user()->guru->id;
        }
        elseif ($request->filled('guru_id')) {
            $guruId = $request->guru_id;
        }

        // Untuk mode manual, jumlah_soal selalu mengikuti jumlah soal yang benar-benar
        // dipilih — bukan angka yang diketik terpisah (mencegah keduanya tidak sinkron).
        $jumlahSoal = $request->metode_soal === 'manual'
            ? count($request->input('soal_ids', []))
            : $request->jumlah_soal;

        $ujian = Ujian::create([
            'nama_ujian' => $request->nama_ujian,
            'jenis_ujian' => $request->jenis_ujian,
            'mapel_id' => $request->mapel_id,
            'guru_id' => $guruId,
            'sesi_ujian_id' => $request->sesi_ujian_id,
            'durasi_menit' => $request->durasi_menit,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'metode_soal' => $request->metode_soal,
            'acak_opsi' => $request->boolean('acak_opsi'),
            'jumlah_soal' => $jumlahSoal,
            'status' => 'draft',
            'token' => substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5),
            'tampilkan_nilai' => $request->boolean('tampilkan_nilai'),
            'tampilkan_pembahasan' => $request->boolean('tampilkan_pembahasan'),
            'instruksi' => $request->instruksi,
        ]);

        // Attach selected kelas
        $ujian->kelasList()->attach($request->kelas_ids);

        // Attach soal — manual: sesuai urutan pemilihan admin; random: auto-pick.
        if ($ujian->metode_soal === 'manual') {
            foreach ($request->input('soal_ids', []) as $i => $soalId) {
                $ujian->bankSoals()->attach($soalId, ['nomor_urut' => $i + 1]);
            }
        }
        else {
            $this->attachRandomSoal($ujian);
        }

        // Auto-assign peserta from selected kelas
        $siswaIds = Siswa::whereIn('kelas_id', $request->kelas_ids)->pluck('id');
        foreach ($siswaIds as $siswaId) {
            PesertaUjian::create([
                'ujian_id' => $ujian->id,
                'siswa_id' => $siswaId,
                'status' => 'belum',
            ]);
        }

        ActivityLog::log('create', 'ujian', "Membuat ujian: {$ujian->nama_ujian}");

        return redirect()->route('ujian.index')->with('success', 'Ujian berhasil dibuat!');
    }

    public function show(Ujian $ujian)
    {
        $ujian->load(['mapel', 'guru', 'bankSoals.opsiJawabans', 'kelasList', 'pesertaUjians.siswa']);
        return view('ujian.show', compact('ujian'));
    }

    public function edit(Ujian $ujian)
    {
        $mapels = $this->getMapelsForUser();
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->get();
        $sesiList = SesiUjian::where('is_active', true)->orderBy('jam_mulai')->get();
        $guruList = \App\Models\Guru::orderBy('nama')->get();
        $ujian->load(['kelasList', 'bankSoals']);
        $adaPesertaMulai = $ujian->pesertaUjians()->where('status', '!=', 'belum')->exists();
        return view('ujian.edit', compact('ujian', 'mapels', 'kelasList', 'sesiList', 'guruList', 'adaPesertaMulai'));
    }

    public function update(Request $request, Ujian $ujian)
    {
        $request->validate([
            'nama_ujian' => 'required|string|max:255',
            'jenis_ujian' => 'required|in:harian,uts,uas,praktik,tryout,anbk,ukk',
            'mapel_id' => 'required|exists:mapels,id',
            'durasi_menit' => 'required|integer|min:1',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'metode_soal' => 'required|in:random,manual',
            'jumlah_soal' => 'nullable|integer|min:1',
            'soal_ids' => 'required_if:metode_soal,manual|array',
            'soal_ids.*' => 'exists:bank_soals,id',
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'exists:kelas,id',
        ]);

        $ujian->update($request->only([
            'nama_ujian', 'jenis_ujian', 'mapel_id', 'sesi_ujian_id',
            'durasi_menit', 'tanggal_mulai', 'tanggal_selesai', 'instruksi', 'status',
        ]));

        $ujian->update([
            'acak_opsi' => $request->boolean('acak_opsi'),
            'tampilkan_nilai' => $request->boolean('tampilkan_nilai'),
            'tampilkan_pembahasan' => $request->boolean('tampilkan_pembahasan'),
        ]);

        if (auth()->user()->guru) {
            $ujian->update(['guru_id' => auth()->user()->guru->id]);
        }
        elseif ($request->filled('guru_id')) {
            $ujian->update(['guru_id' => $request->guru_id]);
        }

        $ujian->kelasList()->sync($request->kelas_ids);

        // Soal set (dan metode-nya) hanya boleh diubah selama BELUM ada peserta yang
        // mulai mengerjakan — mengubahnya setelah itu akan merusak soal_order per-siswa
        // dan JawabanSiswa yang sudah terlanjur dibuat untuk soal lama.
        $adaPesertaMulai = $ujian->pesertaUjians()->where('status', '!=', 'belum')->exists();

        if (!$adaPesertaMulai) {
            $metodeBaru = $request->metode_soal;

            if ($metodeBaru === 'manual') {
                $ujian->bankSoals()->detach();
                foreach ($request->input('soal_ids', []) as $i => $soalId) {
                    $ujian->bankSoals()->attach($soalId, ['nomor_urut' => $i + 1]);
                }
                $ujian->update([
                    'metode_soal' => 'manual',
                    'jumlah_soal' => count($request->input('soal_ids', [])),
                ]);
            }
            else {
                $metodeBerubahDariManual = $ujian->metode_soal !== 'random';

                if ($request->boolean('acak_ulang') || $metodeBerubahDariManual) {
                    $ujian->update([
                        'metode_soal' => 'random',
                        'jumlah_soal' => $request->jumlah_soal ?? $ujian->jumlah_soal,
                    ]);
                    $ujian->bankSoals()->detach();
                    $this->attachRandomSoal($ujian);
                }
                else {
                    $ujian->update(['metode_soal' => 'random']);
                }
            }
        }
        else {
            ActivityLog::log('update', 'ujian', "Percobaan ubah soal ujian \"{$ujian->nama_ujian}\" diabaikan — sudah ada peserta yang mengerjakan");
        }

        ActivityLog::log('update', 'ujian', "Mengupdate ujian: {$ujian->nama_ujian}");

        return redirect()->route('ujian.index')->with('success', 'Ujian berhasil diupdate!');
    }

    /**
     * Auto-pick `jumlah_soal` soal aktif secara acak dari mapel ujian ini dan
     * attach ke pivot ujian_bank_soals. Dipakai oleh store() dan update() supaya
     * logic random tidak terduplikasi.
     */
    private function attachRandomSoal(Ujian $ujian): void
    {
        $soals = BankSoal::where('mapel_id', $ujian->mapel_id)
            ->where('status', 'aktif')
            ->inRandomOrder()
            ->take($ujian->jumlah_soal)
            ->get();

        foreach ($soals as $i => $soal) {
            $ujian->bankSoals()->attach($soal->id, ['nomor_urut' => $i + 1]);
        }
    }

    /**
     * AJAX: daftar soal aktif untuk satu mapel, dipakai oleh picker soal manual
     * di form buat/edit ujian.
     */
    public function soalPicker(Request $request)
    {
        $request->validate([
            'mapel_id' => 'required|exists:mapels,id',
            'search' => 'nullable|string|max:255',
        ]);

        $query = BankSoal::where('mapel_id', $request->mapel_id)->where('status', 'aktif');

        if ($request->filled('search')) {
            $query->where('pertanyaan', 'like', '%' . $request->search . '%');
        }

        $soals = $query->orderBy('id')->get(['id', 'pertanyaan', 'tipe_soal', 'bobot_nilai'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'pertanyaan' => Str::limit(strip_tags($s->pertanyaan), 100),
                'tipe_soal' => $s->tipe_soal,
                'bobot_nilai' => $s->bobot_nilai,
            ]);

        return response()->json($soals);
    }

    public function destroy(Ujian $ujian)
    {
        ActivityLog::log('delete', 'ujian', "Menghapus ujian: {$ujian->nama_ujian}");
        $ujian->delete();
        return redirect()->route('ujian.index')->with('success', 'Ujian berhasil dihapus!');
    }

    public function publish(Ujian $ujian)
    {
        $ujian->update(['status' => 'publish']);
        ActivityLog::log('publish', 'ujian', "Publish ujian: {$ujian->nama_ujian}");
        return back()->with('success', 'Ujian berhasil dipublish!');
    }

    public function hasil(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelasList']);

        $query = $ujian->pesertaUjians()->where('status', 'selesai')->with('siswa.kelas');

        // Filter by kelas
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        $peserta = $query->orderBy('nilai', 'desc')->get();
        $kelasList = $ujian->kelasList;

        return view('ujian.hasil', compact('ujian', 'peserta', 'kelasList'));
    }

    public function cetakNilai(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelasList']);

        $query = $ujian->pesertaUjians()->where('status', 'selesai')
            ->with('siswa.kelas')
            ->withCount(['jawabanSiswas as benar_count' => function ($q) {
            $q->where('is_correct', true);
        }]);

        // Filter by kelas
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        $peserta = $query->orderBy('nilai', 'desc')->get();

        $kelasName = 'Semua Kelas';
        if ($request->filled('kelas_id')) {
            $kelas = Kelas::find($request->kelas_id);
            if ($kelas)
                $kelasName = $kelas->nama_kelas;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Ujian');

        // Header Info
        $sheet->setCellValue('A1', 'HASIL UJIAN: ' . strtoupper($ujian->nama_ujian));
        $sheet->setCellValue('A2', 'Mata Pelajaran: ' . ($ujian->mapel->nama_mapel ?? '-'));
        $sheet->setCellValue('A3', 'Kelas: ' . $kelasName);
        $sheet->setCellValue('A4', 'Tanggal Export: ' . now()->format('d M Y H:i:s'));

        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        // Table Headers
        $headers = ['Rank', 'Nama Siswa', 'NIS', 'Kelas', 'Benar', 'Nilai', 'Waktu Mulai', 'Waktu Selesai'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $col++;
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Primary blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];

        $sheet->getStyle('A6:H6')->applyFromArray($headerStyle);

        // Data Rows
        $row = 7;
        foreach ($peserta as $i => $p) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $p->siswa->nama ?? '-');
            $sheet->setCellValueExplicit('C' . $row, $p->siswa->nis ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $p->siswa->kelas->nama_kelas ?? '-');
            $sheet->setCellValue('E' . $row, $p->benar_count);
            $sheet->setCellValue('F' . $row, $p->nilai);
            $sheet->setCellValue('G' . $row, $p->waktu_mulai ? $p->waktu_mulai->format('d/m/Y H:i:s') : '-');
            $sheet->setCellValue('H' . $row, $p->waktu_selesai ? $p->waktu_selesai->format('d/m/Y H:i:s') : '-');

            // Highlight score depending on value
            $scoreColor = $p->nilai >= 75 ? '16A34A' : ($p->nilai >= 50 ? 'CA8A04' : 'DC2626');
            $sheet->getStyle('F' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $scoreColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        // Auto-width columns
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $filename = 'Nilai_Ujian_' . str_replace(' ', '_', $ujian->nama_ujian) . '_' . str_replace(' ', '_', $kelasName) . '_' . date('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function printNilai(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel']);

        $query = $ujian->pesertaUjians()->where('status', 'selesai')
            ->with('siswa.kelas')
            ->withCount(['jawabanSiswas as benar_count' => function ($q) {
            $q->where('is_correct', true);
        }]);

        $kelasName = 'Semua Kelas';
        // Filter by kelas
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
            $kelas = Kelas::find($request->kelas_id);
            if ($kelas)
                $kelasName = $kelas->nama_kelas;
        }

        $peserta = $query->orderBy('nilai', 'desc')->get();

        return view('ujian.print-nilai', compact('ujian', 'peserta', 'kelasName'));
    }

    /**
     * Shared data for the formal score sheet (Excel + print) — header info,
     * peserta list, dan hitungan menjawab/benar per peserta.
     */
    private function resolveNilaiResmiData(Request $request, Ujian $ujian): array
    {
        $ujian->load(['mapel']);

        $query = $ujian->pesertaUjians()->where('status', 'selesai')
            ->with('siswa.kelas')
            ->withCount([
                'jawabanSiswas as benar_count' => function ($q) {
                    $q->where('is_correct', true);
                },
                'jawabanSiswas as menjawab_count' => function ($q) {
                    $q->whereNotNull('jawaban_dipilih')->where('jawaban_dipilih', '!=', '');
                },
            ]);

        $kelasName = 'Semua Kelas';
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', function ($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
            $kelas = Kelas::find($request->kelas_id);
            if ($kelas) {
                $kelasName = $kelas->nama_kelas;
            }
        }

        // Urut sesuai nomor ujian (NISN) supaya konsisten dengan kartu peserta / absensi
        $peserta = $query->get()->sortBy(fn ($p) => $p->siswa->nisn ?? $p->siswa->nis ?? '')->values();

        return [
            'ujian' => $ujian,
            'peserta' => $peserta,
            'kelasName' => $kelasName,
            'namaSekolah' => Setting::getValue('kartu_nama_sekolah', 'SMK NEGERI 1'),
            'tahunAjaran' => Setting::getValue('kartu_tahun_pelajaran', date('Y') . '/' . (date('Y') + 1)),
            'judul' => 'NILAI ' . strtoupper($ujian->nama_ujian) . ' BERBASIS ONLINE',
        ];
    }

    /**
     * Lembar Nilai Resmi — Excel (format kop sekolah, sesuai contoh laporan sekolah).
     */
    public function nilaiResmiExcel(Request $request, Ujian $ujian)
    {
        $data = $this->resolveNilaiResmiData($request, $ujian);
        ['peserta' => $peserta, 'kelasName' => $kelasName, 'namaSekolah' => $namaSekolah,
            'tahunAjaran' => $tahunAjaran, 'judul' => $judul] = $data;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Resmi');

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', $judul);
        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', $namaSekolah);
        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'TAHUN AJARAN ' . $tahunAjaran);

        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(13);
        $sheet->getStyle('A2')->getFont()->setSize(15);
        $sheet->getStyle('A3')->getFont()->setSize(12);
        $sheet->getStyle('A1:F3')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->setCellValue('A5', 'KELAS');
        $sheet->setCellValue('B5', ': '.$kelasName);
        $sheet->setCellValue('A6', 'MATA PELAJARAN');
        $sheet->setCellValue('B6', ': '.($ujian->mapel->nama_mapel ?? '-'));
        $sheet->getStyle('A5:A6')->getFont()->setBold(true);

        $headers = ['NO', 'NOMOR UJIAN', 'NAMA PESERTA', 'MENJAWAB', 'BENAR', 'TOTAL NILAI'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'8', $header);
            $col++;
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A8:F8')->applyFromArray($headerStyle);

        $row = 9;
        foreach ($peserta as $i => $p) {
            $sheet->setCellValue('A'.$row, $i + 1);
            $sheet->setCellValueExplicit('B'.$row, $p->siswa->nisn ?? $p->siswa->nis ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$row, $p->siswa->nama ?? '-');
            $sheet->setCellValue('D'.$row, $p->menjawab_count);
            $sheet->setCellValue('E'.$row, $p->benar_count);
            $sheet->setCellValue('F'.$row, $p->nilai);

            $sheet->getStyle('A'.$row.':F'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('A'.$row.':B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        if ($peserta->isEmpty()) {
            $sheet->mergeCells('A9:F9');
            $sheet->setCellValue('A9', 'Tidak ada peserta yang menyelesaikan ujian.');
            $sheet->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(32);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(12);

        $filename = 'Nilai_Resmi_'.str_replace(' ', '_', $ujian->nama_ujian).'_'.str_replace(' ', '_', $kelasName).'_'.date('Ymd_His').'.xlsx';
        $tempPath = storage_path('app/'.$filename);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Lembar Nilai Resmi — PDF (dicetak lewat browser, sama seperti print-nilai/kartu-peserta).
     */
    public function nilaiResmiPrint(Request $request, Ujian $ujian)
    {
        $data = $this->resolveNilaiResmiData($request, $ujian);

        return view('ujian.nilai-resmi-print', $data);
    }

    public function showJawaban(Ujian $ujian, PesertaUjian $peserta)
    {
        // Load relationships
        $ujian->load('mapel');
        $peserta->load('siswa.kelas');

        // Ensure the peserta belongs to the ujian
        if ($peserta->ujian_id !== $ujian->id) {
            abort(404);
        }

        // Get answers with questions and options
        $jawabans = JawabanSiswa::with(['bankSoal.opsiJawabans'])
            ->where('peserta_ujian_id', $peserta->id)
            ->get();

        // Log untuk debugging
        \Log::info('Showing jawaban page', [
            'peserta_id' => $peserta->id,
            'ujian_id' => $ujian->id,
            'total_jawaban' => $jawabans->count(),
            'jawaban_terisi' => $jawabans->filter(function($j) {
                return $j->jawaban_dipilih !== null && trim($j->jawaban_dipilih) !== '';
            })->count(),
            'jawaban_kosong' => $jawabans->filter(function($j) {
                return $j->jawaban_dipilih === null || trim($j->jawaban_dipilih) === '';
            })->count(),
            'sample_jawaban' => $jawabans->take(3)->map(function($j) {
                return [
                    'id' => $j->id,
                    'soal_id' => $j->bank_soal_id,
                    'jawaban' => $j->jawaban_dipilih,
                    'is_null' => $j->jawaban_dipilih === null,
                    'is_empty' => $j->jawaban_dipilih === '',
                    'trimmed' => trim($j->jawaban_dipilih ?? ''),
                ];
            })->toArray(),
        ]);

        // Sort jawabans according to soal_order if available
        if ($peserta->soal_order) {
            $soalOrder = $peserta->getSoalOrderArray();
            $jawabans = $jawabans->sortBy(function ($jawaban) use ($soalOrder) {
                return array_search($jawaban->bank_soal_id, $soalOrder);
            })->values();
        }

        return view('ujian.jawaban', compact('ujian', 'peserta', 'jawabans'));
    }

    public function updateNilai(Request $request, Ujian $ujian, PesertaUjian $peserta)
    {
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*' => 'required|numeric|min:0'
        ]);

        if ($peserta->ujian_id !== $ujian->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $totalNilai = 0;
            $totalBobot = 0;

            foreach ($request->nilai as $jawabanId => $nilaiInput) {
                $jawaban = JawabanSiswa::with('bankSoal')->find($jawabanId);

                if ($jawaban && $jawaban->peserta_ujian_id === $peserta->id && $jawaban->bankSoal) {
                    $soal = $jawaban->bankSoal;
                    $maxNilai = $soal->bobot_nilai;

                    // Cap input at max bobot
                    $nilaiFinal = min($nilaiInput, $maxNilai);

                    $jawaban->update([
                        'nilai' => $nilaiFinal,
                        'is_correct' => $nilaiFinal > 0
                    ]);
                }
            }

            // Recalculate final score
            $semuaJawaban = JawabanSiswa::with('bankSoal')->where('peserta_ujian_id', $peserta->id)->get();
            foreach ($semuaJawaban as $jwb) {
                if ($jwb->bankSoal) {
                    $totalBobot += $jwb->bankSoal->bobot_nilai;
                    $totalNilai += $jwb->nilai;
                }
            }

            $nilaiAkhir = $totalBobot > 0 ? round(($totalNilai / $totalBobot) * 100, 2) : 0;

            $peserta->update([
                'nilai' => $nilaiAkhir
            ]);

            ActivityLog::log('update', 'ujian', "Update nilai manual peserta {$peserta->siswa->nama} pada ujian {$ujian->nama_ujian} (Nilai akhir: {$nilaiAkhir})");

            DB::commit();
            return redirect()->route('ujian.hasil', $ujian->id)->with('success', 'Nilai berhasil diupdate!');

        }
        catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menyimpan nilai: ' . $e->getMessage());
        }
    }

    public function monitoring(Ujian $ujian)
    {
        $ujian->load(['mapel', 'pesertaUjians.siswa']);
        return view('ujian.monitoring', compact('ujian'));
    }

    /**
     * Token management page — list all ujian tokens.
     */
    public function tokenIndex(Request $request)
    {
        $query = Ujian::with(['mapel', 'sesiUjian'])
            ->where('status', 'publish');

        if (auth()->user()->isGuru() && auth()->user()->guru) {
            $query->where('guru_id', auth()->user()->guru->id);
        }

        if ($request->filled('search')) {
            $query->where('nama_ujian', 'like', "%{$request->search}%");
        }

        $ujians = $query->latest()->paginate(15)->withQueryString();

        return view('ujian.token-index', compact('ujians'));
    }

    /**
     * Regenerate token for an ujian.
     */
    public function regenerateToken(Ujian $ujian)
    {
        $newToken = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
        $ujian->update(['token' => $newToken]);

        ActivityLog::log('update', 'ujian', "Regenerate token ujian: {$ujian->nama_ujian} → {$newToken}");

        return back()->with('success', "Token baru untuk \"{$ujian->nama_ujian}\": {$newToken}");
    }

    /**
     * Helper: Get mapels filtered by guru assignment (if guru role).
     */
    private function getMapelsForUser()
    {
        $user = auth()->user();

        // Guru: only show assigned mapels
        if ($user->isGuru() && $user->guru) {
            $assignedMapelIds = $user->guru->mapels()->pluck('mapels.id');
            if ($assignedMapelIds->isNotEmpty()) {
                return Mapel::where('is_active', true)
                    ->whereIn('id', $assignedMapelIds)
                    ->orderBy('nama_mapel')
                    ->get();
            }
        }

        // Admin/Superadmin or guru without assignment: show all
        return Mapel::where('is_active', true)->orderBy('nama_mapel')->get();
    }
}
