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

class ImportSiswaController extends Controller
{
    /**
     * Show the import siswa page
     */
    public function index()
    {
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->orderBy('nama_kelas')->get();
        $recentImports = ActivityLog::where('action', 'import')
            ->where('module', 'siswa')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('admin.import-siswa.index', compact('kelasList', 'recentImports'));
    }

    /**
     * Download template Excel
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Siswa');

        // Headers
        $headers = ['No', 'NISN', 'NIS', 'Nama Lengkap', 'Kelas (ID atau Nama)'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);

            // Style header
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

        // Sample data
        $sampleData = [
            [1, '0030000100', '20240100', 'Ahmad Rafi Pratama', '10 TKJ'],
            [2, '0030000101', '20240101', 'Bella Anisa Putri', '10 TKJ'],
            [3, '0030000102', '20240102', 'Candra Dwi Saputra', '11 TSM'],
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            foreach ($data as $col => $value) {
                $sheet->setCellValue(chr(65 + $col) . $row, $value);
            }
            $row++;
        }

        // Info sheet
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Panduan');
        $infoSheet->setCellValue('A1', 'PANDUAN IMPORT SISWA');
        $infoSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $guide = [
            ['', ''],
            ['Kolom', 'Keterangan'],
            ['No', 'Nomor urut (opsional)'],
            ['NISN', 'Nomor Induk Siswa Nasional (wajib, unik, digunakan untuk login)'],
            ['NIS', 'Nomor Induk Siswa internal sekolah (wajib, unik)'],
            ['Nama Lengkap', 'Nama lengkap siswa (wajib)'],
            ['Kelas', 'Nama kelas (misal: 10 TKJ) atau ID kelas dari sistem (wajib)'],
            ['', ''],
            ['CATATAN:', ''],
            ['1.', 'Password akan di-generate otomatis oleh sistem'],
            ['2.', 'Email akan di-generate otomatis dari NISN'],
            ['3.', 'NISN dan NIS harus unik (tidak boleh duplikat)'],
            ['4.', 'Nama kelas harus sesuai dengan yang ada di sistem'],
            ['5.', 'File harus berformat .xlsx atau .xls'],
        ];

        $row = 2;
        foreach ($guide as $data) {
            $infoSheet->setCellValue('A' . $row, $data[0]);
            $infoSheet->setCellValue('B' . $row, $data[1]);
            if ($row === 3) {
                $infoSheet->getStyle('A3:B3')->getFont()->setBold(true);
            }
            $row++;
        }

        // Auto-width columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $infoSheet->getColumnDimension('A')->setAutoSize(true);
        $infoSheet->getColumnDimension('B')->setAutoSize(true);

        // Daftar kelas yang tersedia di sheet panduan
        $infoSheet->setCellValue('A' . ($row + 1), 'DAFTAR KELAS TERSEDIA:');
        $infoSheet->getStyle('A' . ($row + 1))->getFont()->setBold(true);
        $row += 2;

        $kelasList = Kelas::with('jurusan')->where('is_active', true)->orderBy('nama_kelas')->get();
        $infoSheet->setCellValue('A' . $row, 'ID');
        $infoSheet->setCellValue('B' . $row, 'Nama Kelas');
        $infoSheet->setCellValue('C' . $row, 'Jurusan');
        $infoSheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($kelasList as $kelas) {
            $infoSheet->setCellValue('A' . $row, $kelas->id);
            $infoSheet->setCellValue('B' . $row, $kelas->nama_kelas);
            $infoSheet->setCellValue('C' . $row, $kelas->jurusan->nama_jurusan ?? '-');
            $row++;
        }
        $infoSheet->getColumnDimension('C')->setAutoSize(true);

        // Write to temp file
        $fileName = 'template_import_siswa.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview the uploaded Excel file
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'File Excel harus diupload.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Remove header row
        $header = array_shift($rows);

        // Get kelas lookup
        $kelasLookup = Kelas::with('jurusan')->where('is_active', true)->get();
        $kelasById = $kelasLookup->keyBy('id');
        $kelasByName = $kelasLookup->keyBy(function ($k) {
            return strtolower(trim($k->nama_kelas));
        });

        // Existing NISN/NIS for duplicate checking
        $existingNisn = Siswa::pluck('nisn')->filter()->toArray();
        $existingNis = Siswa::pluck('nis')->toArray();

        $previewData = [];
        $errors = [];
        $nisnSeen = [];
        $nisSeen = [];

        foreach ($rows as $rowIdx => $row) {
            $rowNum = $rowIdx + 1;
            $rowValues = array_values($row);

            // Skip empty rows
            if (empty(array_filter($rowValues)))
                continue;

            $nisn = trim($rowValues[1] ?? '');
            $nis = trim($rowValues[2] ?? '');
            $nama = trim($rowValues[3] ?? '');
            $kelasInput = trim($rowValues[4] ?? '');

            $rowErrors = [];

            // Validate NISN
            if (empty($nisn)) {
                $rowErrors[] = 'NISN kosong';
            }
            elseif (in_array($nisn, $existingNisn)) {
                $rowErrors[] = "NISN '{$nisn}' sudah terdaftar di sistem";
            }
            elseif (in_array($nisn, $nisnSeen)) {
                $rowErrors[] = "NISN '{$nisn}' duplikat dalam file";
            }
            $nisnSeen[] = $nisn;

            // Validate NIS
            if (empty($nis)) {
                $rowErrors[] = 'NIS kosong';
            }
            elseif (in_array($nis, $existingNis)) {
                $rowErrors[] = "NIS '{$nis}' sudah terdaftar di sistem";
            }
            elseif (in_array($nis, $nisSeen)) {
                $rowErrors[] = "NIS '{$nis}' duplikat dalam file";
            }
            $nisSeen[] = $nis;

            // Validate Nama
            if (empty($nama)) {
                $rowErrors[] = 'Nama kosong';
            }

            // Resolve kelas
            $resolvedKelas = null;
            if (empty($kelasInput)) {
                $rowErrors[] = 'Kelas kosong';
            }
            else {
                // Try by ID first
                if (is_numeric($kelasInput) && isset($kelasById[intval($kelasInput)])) {
                    $resolvedKelas = $kelasById[intval($kelasInput)];
                }
                else {
                    // Try by name
                    $resolvedKelas = $kelasByName[strtolower($kelasInput)] ?? null;
                    if (!$resolvedKelas) {
                        $rowErrors[] = "Kelas '{$kelasInput}' tidak ditemukan";
                    }
                }
            }

            $previewData[] = [
                'row' => $rowNum,
                'nisn' => $nisn,
                'nis' => $nis,
                'nama' => $nama,
                'kelas_input' => $kelasInput,
                'kelas' => $resolvedKelas,
                'errors' => $rowErrors,
                'valid' => empty($rowErrors),
            ];
        }

        $validCount = collect($previewData)->where('valid', true)->count();
        $errorCount = collect($previewData)->where('valid', false)->count();

        // Store preview data in session for import
        session(['import_preview' => $previewData]);
        session(['import_file_name' => $file->getClientOriginalName()]);

        return view('admin.import-siswa.preview', compact('previewData', 'validCount', 'errorCount'));
    }

    /**
     * Execute the import from previewed data
     */
    public function import(Request $request)
    {
        $previewData = session('import_preview');

        if (!$previewData) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang.');
        }

        $skipErrors = $request->boolean('skip_errors');
        $importData = collect($previewData)->filter(function ($row) use ($skipErrors) {
            return $skipErrors ? $row['valid'] : true;
        });

        // If not skipping errors, check all rows valid
        if (!$skipErrors && collect($previewData)->where('valid', false)->count() > 0) {
            return back()->with('error', 'Terdapat data yang belum valid. Perbaiki file atau centang "Lewati baris bermasalah".');
        }

        $validRows = $importData->where('valid', true);

        if ($validRows->isEmpty()) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Tidak ada data valid untuk diimport.');
        }

        $credentials = [];
        $successCount = 0;

        DB::beginTransaction();
        try {
            foreach ($validRows as $row) {
                $plainPassword = strtoupper(Str::random(8));
                $autoEmail = 'siswa.' . $row['nisn'] . '@cbtsmk.local';

                $user = User::create([
                    'name' => $row['nama'],
                    'email' => $autoEmail,
                    'password' => Hash::make($plainPassword),
                    'plain_password' => $plainPassword,
                    'role' => 'siswa',
                    'is_active' => true,
                ]);

                Siswa::create([
                    'nis' => $row['nis'],
                    'nisn' => $row['nisn'],
                    'nama' => $row['nama'],
                    'kelas_id' => $row['kelas']->id,
                    'user_id' => $user->id,
                ]);

                $credentials[] = [
                    'nama' => $row['nama'],
                    'nisn' => $row['nisn'],
                    'nis' => $row['nis'],
                    'kelas' => $row['kelas']->nama_kelas,
                    'password' => $plainPassword,
                ];

                $successCount++;
            }

            DB::commit();

            ActivityLog::log('import', 'siswa', "Import {$successCount} siswa dari file Excel");

            // Store credentials in session for display
            session(['import_credentials' => $credentials]);
            session(['import_success_count' => $successCount]);

            // Clear preview
            session()->forget(['import_preview', 'import_file_name']);

            return redirect()->route('admin.import-siswa.result');

        }
        catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Gagal import data: ' . $e->getMessage());
        }
    }

    /**
     * Show import result with all generated credentials
     */
    public function result()
    {
        $credentials = session('import_credentials');
        $successCount = session('import_success_count', 0);

        if (!$credentials) {
            return redirect()->route('admin.import-siswa.index');
        }

        return view('admin.import-siswa.result', compact('credentials', 'successCount'));
    }

    /**
     * Download credentials as Excel
     */
    public function downloadCredentials()
    {
        $credentials = session('import_credentials');

        if (!$credentials) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Data kredensial tidak ditemukan.');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kredensial Siswa');

        // Headers
        $headers = ['No', 'Nama', 'NISN (Login)', 'NIS', 'Kelas', 'Password'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '22C55E'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        $row = 2;
        foreach ($credentials as $idx => $cred) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $cred['nama']);
            $sheet->setCellValueExplicit('C' . $row, $cred['nisn'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, $cred['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $cred['kelas']);
            $sheet->setCellValue('F' . $row, $cred['password']);

            // Highlight password
            $sheet->getStyle('F' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            ]);
            $row++;
        }

        // Auto-width
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Warning row
        $sheet->setCellValue('A' . ($row + 1), '⚠️ RAHASIA - Jangan bagikan file ini sembarangan! Setelah dicetak, hapus file ini.');
        $sheet->mergeCells('A' . ($row + 1) . ':F' . ($row + 1));
        $sheet->getStyle('A' . ($row + 1))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'F59E0B']],
        ]);

        $fileName = 'kredensial_siswa_' . date('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
