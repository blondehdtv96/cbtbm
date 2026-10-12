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
    /** Batas panjang password manual dari file import */
    private const PASSWORD_MIN_LENGTH = 4;
    private const PASSWORD_MAX_LENGTH = 50;

    /** Jumlah baris per statement insert massal */
    private const INSERT_CHUNK = 100;

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
        $headers = ['No', 'NISN', 'NIS', 'Nama Lengkap', 'Kelas (ID atau Nama)', 'Password (Opsional)'];
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
            [1, '0030000100', '20240100', 'Ahmad Rafi Pratama', '10 TKJ', 'siswa2024'],
            [2, '0030000101', '20240101', 'Bella Anisa Putri', '10 TKJ', ''],
            [3, '0030000102', '20240102', 'Candra Dwi Saputra', '11 TSM', 'TSM#2024'],
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            foreach ($data as $col => $value) {
                $cellRef = chr(65 + $col) . $row;
                // NISN, NIS dan Password ditulis sebagai teks agar angka 0 di depan tidak hilang
                if (in_array($col, [1, 2, 5], true)) {
                    $sheet->setCellValueExplicit($cellRef, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                else {
                    $sheet->setCellValue($cellRef, $value);
                }
            }
            $row++;
        }

        // Kolom NISN, NIS dan Password diformat teks agar input admin tidak diubah Excel
        $sheet->getStyle('B2:C500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('F2:F500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

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
            ['Nama Lengkap', 'Nama lengkap siswa (wajib, boleh sama dengan siswa lain)'],
            ['Kelas', 'Nama kelas (misal: 10 TKJ) atau ID kelas dari sistem (wajib)'],
            ['Password', 'Password login siswa (opsional). Kosongkan bila ingin di-generate otomatis'],
            ['', ''],
            ['CATATAN:', ''],
            ['1.', 'Password bisa diisi manual: minimal 4 karakter, maksimal 50 karakter, tanpa spasi'],
            ['2.', 'Jika kolom Password dikosongkan, sistem akan generate password otomatis'],
            ['3.', 'Password boleh sama untuk beberapa siswa (misal satu password per kelas)'],
            ['4.', 'Email akan di-generate otomatis dari NISN'],
            ['5.', 'Hanya NISN dan NIS yang harus unik (tidak boleh duplikat)'],
            ['', 'Nama siswa kembar/sama TIDAK dianggap duplikat selama NIS dan NISN berbeda'],
            ['6.', 'Nama kelas harus sesuai dengan yang ada di sistem'],
            ['7.', 'File harus berformat .xlsx atau .xls'],
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
        foreach (range('A', 'F') as $col) {
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

        // Existing NISN/NIS for duplicate checking.
        // Nama sengaja TIDAK diperiksa: siswa dengan nama sama boleh diimport
        // selama NIS (dan NISN) berbeda.
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

            $nisn = trim((string) ($rowValues[1] ?? ''));
            $nis = trim((string) ($rowValues[2] ?? ''));
            $nama = trim((string) ($rowValues[3] ?? ''));
            $kelasInput = trim((string) ($rowValues[4] ?? ''));
            $passwordInput = trim((string) ($rowValues[5] ?? ''));

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

            // Validate Nama — hanya wajib terisi, nama kembar bukan masalah
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

            // Validate password manual (opsional, kosong = generate otomatis)
            if ($passwordInput !== '') {
                if (mb_strlen($passwordInput) < self::PASSWORD_MIN_LENGTH) {
                    $rowErrors[] = 'Password minimal ' . self::PASSWORD_MIN_LENGTH . ' karakter';
                }
                elseif (mb_strlen($passwordInput) > self::PASSWORD_MAX_LENGTH) {
                    $rowErrors[] = 'Password maksimal ' . self::PASSWORD_MAX_LENGTH . ' karakter';
                }
                elseif (preg_match('/\s/u', $passwordInput)) {
                    $rowErrors[] = 'Password tidak boleh mengandung spasi';
                }
            }

            $previewData[] = [
                'row' => $rowNum,
                'nisn' => $nisn,
                'nis' => $nis,
                'nama' => $nama,
                'kelas_input' => $kelasInput,
                'kelas' => $resolvedKelas,
                'password' => $passwordInput,
                'password_manual' => $passwordInput !== '',
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

        // Hash bcrypt ratusan siswa bisa memakan menit-an waktu CPU — beri ruang
        // seperti proses backup database (lihat DatabaseBackupService).
        @set_time_limit(300);

        // Semua pekerjaan berat disiapkan DI LUAR transaksi. Satu Hash::make()
        // (bcrypt 12 putaran) butuh ratusan milidetik, jadi menghash di dalam
        // transaksi menahan lock tabel users belasan sampai puluhan detik untuk
        // ratusan siswa. Request lain yang menulis users (siswa login, stempel
        // last_seen) ikut mengantre sampai MySQL membunuh salah satunya dengan
        // "Deadlock found when trying to get lock".
        $timestamp = now();
        $userRows = [];
        $siswaRows = [];
        $credentials = [];

        foreach ($validRows as $row) {
            $manualPassword = trim((string) ($row['password'] ?? ''));
            $isManualPassword = $manualPassword !== '';
            $plainPassword = $isManualPassword ? $manualPassword : strtoupper(Str::random(8));
            $autoEmail = 'siswa.' . $row['nisn'] . '@cbtsmk.local';

            // Dikunci per email (unik per NISN) supaya baris kembar tidak
            // menabrak unique index di tengah transaksi.
            $userRows[$autoEmail] = [
                'name' => $row['nama'],
                'email' => $autoEmail,
                'password' => Hash::make($plainPassword),
                'plain_password' => $plainPassword,
                'role' => 'siswa',
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $siswaRows[$autoEmail] = [
                'nis' => $row['nis'],
                'nisn' => $row['nisn'],
                'nama' => $row['nama'],
                'kelas_id' => $row['kelas']->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $credentials[$autoEmail] = [
                'nama' => $row['nama'],
                'nisn' => $row['nisn'],
                'nis' => $row['nis'],
                'kelas' => $row['kelas']->nama_kelas,
                'password' => $plainPassword,
                'password_manual' => $isManualPassword,
            ];
        }

        $successCount = count($userRows);

        try {
            // Transaksi kini tinggal beberapa statement insert massal. Attempts > 1
            // membuat Laravel otomatis mencoba ulang kalau transaksi ini yang
            // dipilih MySQL sebagai korban deadlock / lock wait timeout.
            DB::transaction(function () use ($userRows, $siswaRows) {
                foreach (array_chunk($userRows, self::INSERT_CHUNK, true) as $chunk) {
                    User::insert(array_values($chunk));

                    // insert() massal tidak mengembalikan id, jadi id akun diambil
                    // lewat email yang memang unik per NISN.
                    $userIds = User::whereIn('email', array_keys($chunk))->pluck('id', 'email');

                    if ($userIds->count() !== count($chunk)) {
                        throw new \RuntimeException('Sebagian akun siswa gagal dibuat, import dibatalkan.');
                    }

                    $batch = [];
                    foreach (array_keys($chunk) as $email) {
                        $batch[] = $siswaRows[$email] + ['user_id' => $userIds[$email]];
                    }

                    Siswa::insert($batch);
                }
            }, self::TRANSACTION_ATTEMPTS);
        }
        catch (\Throwable $e) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', $this->writeErrorMessage($e, 'Gagal import data'));
        }

        ActivityLog::log('import', 'siswa', "Import {$successCount} siswa dari file Excel");

        // Store credentials in session for display
        session(['import_credentials' => array_values($credentials)]);
        session(['import_success_count' => $successCount]);

        // Clear preview
        session()->forget(['import_preview', 'import_file_name']);

        return redirect()->route('admin.import-siswa.result');
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
        $headers = ['No', 'Nama', 'NISN (Login)', 'NIS', 'Kelas', 'Password', 'Sumber Password'];
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
            $sheet->setCellValueExplicit('F' . $row, $cred['password'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $row, !empty($cred['password_manual']) ? 'Manual (dari file import)' : 'Otomatis');

            // Highlight password
            $sheet->getStyle('F' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            ]);
            $row++;
        }

        // Auto-width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Warning row
        $sheet->setCellValue('A' . ($row + 1), '⚠️ RAHASIA - Jangan bagikan file ini sembarangan! Setelah dicetak, hapus file ini.');
        $sheet->mergeCells('A' . ($row + 1) . ':G' . ($row + 1));
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
