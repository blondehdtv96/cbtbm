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

    /** Folder berkas antrean import di storage/app */
    private const PENDING_DIR = 'import-siswa';

    /**
     * Jatah waktu (detik) per potongan import. Satu request harus selesai jauh
     * di bawah batas waktu nginx/php-fpm supaya tidak kena 504 Gateway Time-out.
     */
    private const CHUNK_TIME_BUDGET = 10;

    /** Batas jumlah baris per potongan, apa pun hasil pengukuran kecepatan hash */
    private const MIN_CHUNK_ROWS = 10;
    private const MAX_CHUNK_ROWS = 100;

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

        // Import yang terputus di tengah jalan (tab ditutup, koneksi mati) bisa
        // dilanjutkan dari posisi terakhir — tanpa ini admin tidak punya jalan
        // kembali ke halaman progress.
        $pending = $this->readPending(session('import_token'));
        $resumable = null;

        if ($pending && !empty($pending['started']) && empty($pending['completed_at'])) {
            $resumable = [
                'done' => (int) ($pending['cursor'] ?? 0),
                'total' => (int) ($pending['total'] ?? 0),
            ];
        }

        return view('admin.import-siswa.index', compact('kelasList', 'recentImports', 'resumable'));
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

        @set_time_limit(300);

        $file = $request->file('file');

        // Hanya nilai sel yang dibaca (tanpa style/format): jauh lebih hemat
        // memori dan waktu untuk file berisi ribuan baris.
        $reader = IOFactory::createReaderForFile($file->getPathname());
        $reader->setReadDataOnly(true);
        $rows = $reader->load($file->getPathname())->getActiveSheet()->toArray(null, true, true, true);

        // Remove header row
        array_shift($rows);

        // Get kelas lookup
        $kelasLookup = Kelas::where('is_active', true)->get(['id', 'nama_kelas']);
        $kelasById = $kelasLookup->keyBy('id');
        $kelasByName = $kelasLookup->keyBy(function ($k) {
            return strtolower(trim($k->nama_kelas));
        });

        // Existing NISN/NIS for duplicate checking.
        // Nama sengaja TIDAK diperiksa: siswa dengan nama sama boleh diimport
        // selama NIS (dan NISN) berbeda.
        //
        // Dipakai sebagai kunci array + isset(), bukan in_array() pada array
        // berindeks: untuk 1.200 baris dikali ribuan siswa lama, in_array()
        // berarti jutaan perbandingan string dan preview jadi ikut lambat.
        $existingNisn = array_flip(Siswa::whereNotNull('nisn')->pluck('nisn')->all());
        $existingNis = array_flip(Siswa::pluck('nis')->all());

        $previewData = [];
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
            if ($nisn === '') {
                $rowErrors[] = 'NISN kosong';
            }
            elseif (isset($existingNisn[$nisn])) {
                $rowErrors[] = "NISN '{$nisn}' sudah terdaftar di sistem";
            }
            elseif (isset($nisnSeen[$nisn])) {
                $rowErrors[] = "NISN '{$nisn}' duplikat dalam file";
            }
            $nisnSeen[$nisn] = true;

            // Validate NIS
            if ($nis === '') {
                $rowErrors[] = 'NIS kosong';
            }
            elseif (isset($existingNis[$nis])) {
                $rowErrors[] = "NIS '{$nis}' sudah terdaftar di sistem";
            }
            elseif (isset($nisSeen[$nis])) {
                $rowErrors[] = "NIS '{$nis}' duplikat dalam file";
            }
            $nisSeen[$nis] = true;

            // Validate Nama — hanya wajib terisi, nama kembar bukan masalah
            if ($nama === '') {
                $rowErrors[] = 'Nama kosong';
            }

            // Resolve kelas
            $resolvedKelas = null;
            if ($kelasInput === '') {
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

            // Baris disimpan sebagai data mentah (tanpa model Eloquent) karena
            // ikut ditulis ke berkas antrean import.
            $previewData[] = [
                'row' => $rowNum,
                'nisn' => $nisn,
                'nis' => $nis,
                'nama' => $nama,
                'kelas_input' => $kelasInput,
                'kelas_id' => $resolvedKelas->id ?? null,
                'kelas_nama' => $resolvedKelas->nama_kelas ?? null,
                'password' => $passwordInput,
                'password_manual' => $passwordInput !== '',
                'errors' => $rowErrors,
                'valid' => empty($rowErrors),
            ];
        }

        $validCount = 0;
        foreach ($previewData as $row) {
            if ($row['valid']) {
                $validCount++;
            }
        }
        $errorCount = count($previewData) - $validCount;

        // Payload preview ditaruh di berkas, bukan session: 1.200+ baris di
        // session file berarti serialisasi ratusan KB pada SETIAP request.
        $this->pruneStalePending();
        $token = Str::random(32);
        $this->writePending($token, [
            'file_name' => $file->getClientOriginalName(),
            'rows' => $previewData,
            'started' => false,
        ]);
        session(['import_token' => $token]);

        return view('admin.import-siswa.preview', compact('previewData', 'validCount', 'errorCount'));
    }

    /**
     * Mulai import: baris valid disiapkan, lalu dikerjakan bertahap oleh
     * halaman progress.
     *
     * Hash bcrypt butuh ±0,26 detik per siswa, jadi 1.200 siswa = ±5 menit
     * kerja CPU. Dikerjakan dalam satu request, nginx/php-fpm memutusnya lebih
     * dulu ("504 Gateway Time-out") dan admin tidak tahu berapa data yang
     * sempat masuk. Karena itu prosesnya dipotong menjadi banyak request kecil.
     */
    public function import(Request $request)
    {
        $token = session('import_token');
        $pending = $this->readPending($token);

        if (!$pending) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang.');
        }

        $skipErrors = $request->boolean('skip_errors');
        $rows = $pending['rows'] ?? [];
        $validRows = [];
        $invalidCount = 0;

        foreach ($rows as $row) {
            if (!empty($row['valid'])) {
                $validRows[] = $row;
            }
            else {
                $invalidCount++;
            }
        }

        // If not skipping errors, check all rows valid
        if (!$skipErrors && $invalidCount > 0) {
            return back()->with('error', 'Terdapat data yang belum valid. Perbaiki file atau centang "Lewati baris bermasalah".');
        }

        if (empty($validRows)) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Tidak ada data valid untuk diimport.');
        }

        $this->writePending($token, [
            'file_name' => $pending['file_name'] ?? null,
            'rows' => $validRows,
            'total' => count($validRows),
            'cursor' => 0,
            'credentials' => [],
            'skipped' => [],
            'invalid_skipped' => $skipErrors ? $invalidCount : 0,
            'started' => true,
            'completed_at' => null,
        ]);

        return redirect()->route('admin.import-siswa.progress');
    }

    /**
     * Halaman progress — memanggil processChunk() berulang sampai selesai
     */
    public function progress()
    {
        $pending = $this->readPending(session('import_token'));

        if (!$pending || empty($pending['started'])) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Tidak ada proses import yang sedang berjalan.');
        }

        if (!empty($pending['completed_at'])) {
            return redirect()->route('admin.import-siswa.result');
        }

        $total = (int) ($pending['total'] ?? 0);
        $done = (int) ($pending['cursor'] ?? 0);
        $fileName = $pending['file_name'] ?? null;

        return view('admin.import-siswa.progress', compact('total', 'done', 'fileName'));
    }

    /**
     * Kerjakan satu potongan import lalu laporkan posisinya (dipanggil via AJAX)
     */
    public function processChunk(Request $request)
    {
        $token = session('import_token');
        $pending = $this->readPending($token);

        if (!$pending || empty($pending['started'])) {
            return response()->json([
                'message' => 'Proses import tidak ditemukan. Silakan upload ulang file.',
            ], 410);
        }

        @set_time_limit(self::CHUNK_TIME_BUDGET + 120);

        $rows = $pending['rows'] ?? [];
        $total = (int) ($pending['total'] ?? count($rows));
        $cursor = (int) ($pending['cursor'] ?? 0);

        if ($cursor < $total) {
            try {
                $cursor = $this->processPendingChunk($token, $pending, $rows, $total, $cursor);
            }
            catch (\Throwable $e) {
                // Potongan ini gagal utuh (transaksinya di-rollback) dan cursor
                // TIDAK maju, jadi klien boleh mengulang potongan yang sama.
                return response()->json([
                    'message' => $this->writeErrorMessage($e, 'Gagal menyimpan sebagian data'),
                ], 500);
            }
        }

        $pending = $this->readPending($token) ?? $pending;
        $imported = count($pending['credentials'] ?? []);
        $skipped = count($pending['skipped'] ?? []);
        $done = $cursor >= $total;

        if ($done && empty($pending['completed_at'])) {
            $pending['completed_at'] = now()->toDateTimeString();
            $this->writePending($token, $pending);

            ActivityLog::log('import', 'siswa', "Import {$imported} siswa dari file Excel"
                . ($skipped > 0 ? ", {$skipped} baris dilewati (sudah terdaftar)" : ''));
        }

        return response()->json([
            'done' => $done,
            'processed' => min($cursor, $total),
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'percent' => $total > 0 ? (int) floor(min($cursor, $total) / $total * 100) : 100,
            'result_url' => $done ? route('admin.import-siswa.result') : null,
        ]);
    }

    /**
     * Proses satu potongan: hash di luar transaksi, tulis dengan insert massal.
     * Mengembalikan posisi cursor yang baru.
     */
    private function processPendingChunk(string $token, array $pending, array $rows, int $total, int $cursor): int
    {
        // Biaya satu hash diukur dulu supaya jumlah baris per request menyesuaikan
        // kecepatan server: satu request harus selesai jauh sebelum batas waktu
        // nginx/php-fpm, baik di server kencang maupun lambat.
        $probeStart = microtime(true);
        Hash::make('probe-' . Str::random(8));
        $hashCost = max(microtime(true) - $probeStart, 0.001);
        $size = (int) max(
            self::MIN_CHUNK_ROWS,
            min(self::MAX_CHUNK_ROWS, floor(self::CHUNK_TIME_BUDGET / $hashCost))
        );

        $candidates = array_slice($rows, $cursor, $size);

        // NISN/NIS dicek ulang per potongan: melindungi dari potongan yang
        // diulang (koneksi putus setelah commit) dan dari data yang masuk lewat
        // jalur lain setelah preview dibuat.
        $takenNisn = array_flip(
            Siswa::whereIn('nisn', array_filter(array_column($candidates, 'nisn')))->pluck('nisn')->all()
        );
        $takenNis = array_flip(
            Siswa::whereIn('nis', array_column($candidates, 'nis'))->pluck('nis')->all()
        );

        $timestamp = now();
        $userRows = [];
        $siswaRows = [];
        $credentials = [];
        $skipped = [];

        foreach ($candidates as $row) {
            if (isset($takenNisn[$row['nisn']]) || isset($takenNis[$row['nis']])) {
                $skipped[] = [
                    'nama' => $row['nama'],
                    'nisn' => $row['nisn'],
                    'nis' => $row['nis'],
                    'alasan' => 'NISN/NIS sudah terdaftar',
                ];
                continue;
            }

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
                'kelas_id' => $row['kelas_id'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $credentials[] = [
                'nama' => $row['nama'],
                'nisn' => $row['nisn'],
                'nis' => $row['nis'],
                'kelas' => $row['kelas_nama'],
                'password' => $plainPassword,
                'password_manual' => $isManualPassword,
            ];
        }

        if (!empty($userRows)) {
            // Transaksi hanya berisi dua insert massal — pendek, jadi tidak
            // bertabrakan dengan tulisan lain ke tabel users. Attempts > 1
            // mengulang otomatis kalau transaksi ini jadi korban deadlock.
            DB::transaction(function () use ($userRows, $siswaRows) {
                User::insert(array_values($userRows));

                // insert() massal tidak mengembalikan id, jadi id akun diambil
                // lewat email yang memang unik per NISN.
                $userIds = User::whereIn('email', array_keys($userRows))->pluck('id', 'email');

                if ($userIds->count() !== count($userRows)) {
                    throw new \RuntimeException('Sebagian akun siswa gagal dibuat, potongan ini dibatalkan.');
                }

                $batch = [];
                foreach (array_keys($userRows) as $email) {
                    $batch[] = $siswaRows[$email] + ['user_id' => $userIds[$email]];
                }

                Siswa::insert($batch);
            }, self::TRANSACTION_ATTEMPTS);
        }

        $cursor += count($candidates);

        $pending['cursor'] = $cursor;
        $pending['credentials'] = array_merge($pending['credentials'] ?? [], $credentials);
        $pending['skipped'] = array_merge($pending['skipped'] ?? [], $skipped);
        $this->writePending($token, $pending);

        return $cursor;
    }

    /**
     * Show import result with all generated credentials
     */
    public function result()
    {
        $pending = $this->readPending(session('import_token'));

        if (!$pending || empty($pending['completed_at'])) {
            return redirect()->route('admin.import-siswa.index');
        }

        $credentials = $pending['credentials'] ?? [];
        $successCount = count($credentials);
        $skippedRows = $pending['skipped'] ?? [];
        $invalidSkipped = (int) ($pending['invalid_skipped'] ?? 0);

        return view('admin.import-siswa.result', compact(
            'credentials', 'successCount', 'skippedRows', 'invalidSkipped'
        ));
    }

    /**
     * Download credentials as Excel
     */
    public function downloadCredentials()
    {
        $pending = $this->readPending(session('import_token'));
        $credentials = $pending['credentials'] ?? null;

        if (empty($credentials)) {
            return redirect()->route('admin.import-siswa.index')
                ->with('error', 'Data kredensial tidak ditemukan.');
        }

        @set_time_limit(300);

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

    /**
     * Berkas antrean import (payload besar tidak ditaruh di session)
     */
    private function pendingPath(string $token): string
    {
        return storage_path('app/' . self::PENDING_DIR . '/' . $token . '.json');
    }

    private function writePending(string $token, array $payload): void
    {
        $dir = storage_path('app/' . self::PENDING_DIR);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->pendingPath($token), json_encode($payload), LOCK_EX);
    }

    private function readPending($token): ?array
    {
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            return null;
        }

        $path = $this->pendingPath($token);

        if (!is_file($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Buang berkas antrean yang tertinggal lebih dari sehari
     */
    private function pruneStalePending(): void
    {
        $limit = now()->subDay()->getTimestamp();

        foreach (glob(storage_path('app/' . self::PENDING_DIR . '/*.json')) ?: [] as $path) {
            if (filemtime($path) < $limit) {
                @unlink($path);
            }
        }
    }
}
