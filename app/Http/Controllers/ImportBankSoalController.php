<?php

namespace App\Http\Controllers;

use App\Models\BankSoal;
use App\Models\Mapel;
use App\Models\Guru;
use App\Models\OpsiJawaban;
use App\Models\ActivityLog;
use App\Models\ImportBatch;
use App\Jobs\ImportBankSoalJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ImportBankSoalController extends Controller
{
    /**
     * Show the import bank soal page
     */
    public function index()
    {
        $mapels = Mapel::where('is_active', true)->orderBy('nama_mapel')->get();
        $recentImports = ActivityLog::where('action', 'import')
            ->where('module', 'bank_soal')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('admin.import-banksoal.index', compact('mapels', 'recentImports'));
    }

    /**
     * Download template Excel for Bank Soal
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ===== SHEET 1: Template Soal PG =====
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal Pilihan Ganda');

        $headers = [
            'A' => 'No',
            'B' => 'Kode Mapel',
            'C' => 'Tipe Soal',
            'D' => 'Bobot Nilai',
            'E' => 'Pertanyaan',
            'F' => 'Opsi A',
            'G' => 'Opsi B',
            'H' => 'Opsi C',
            'I' => 'Opsi D',
            'J' => 'Opsi E',
            'K' => 'Jawaban Benar',
        ];

        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4F46E5']],
                ],
            ]);
        }

        // Sample data PG
        $samplePG = [
            [1, 'MTK', 'pg', 1, 'Berapakah hasil dari 5 + 3?', '6', '7', '8', '9', '10', 'C'],
            [2, 'MTK', 'pg', 1, 'Nilai dari √81 adalah...', '7', '8', '9', '10', '11', 'C'],
            [3, 'BIN', 'pg', 1, 'Kata "menulis" termasuk jenis kata...', 'Kata benda', 'Kata kerja', 'Kata sifat', 'Kata keterangan', '', 'B'],
        ];

        $row = 2;
        foreach ($samplePG as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            // Light row styling
            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 === 0 ? 'F5F3FF' : 'FFFFFF'],
                ],
            ]);
            $row++;
        }

        // Column widths for PG sheet
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(45);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(16);

        // Freeze header
        $sheet->freezePane('A2');

        // ===== SHEET 2: Template Soal Essay =====
        $essaySheet = $spreadsheet->createSheet();
        $essaySheet->setTitle('Soal Essay');

        $essayHeaders = [
            'A' => 'No',
            'B' => 'Kode Mapel',
            'C' => 'Bobot Nilai',
            'D' => 'Pertanyaan',
        ];

        foreach ($essayHeaders as $col => $header) {
            $essaySheet->setCellValue($col . '1', $header);
            $essaySheet->getStyle($col . '1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0EA5E9'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Sample essay data
        $sampleEssay = [
            [1, 'MTK', 5, 'Jelaskan langkah-langkah menyelesaikan persamaan kuadrat ax² + bx + c = 0 menggunakan rumus ABC!'],
            [2, 'BIN', 10, 'Buatlah paragraf argumentasi tentang pentingnya literasi digital bagi pelajar!'],
        ];

        $row = 2;
        foreach ($sampleEssay as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $essaySheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        $essaySheet->getColumnDimension('A')->setWidth(5);
        $essaySheet->getColumnDimension('B')->setWidth(14);
        $essaySheet->getColumnDimension('C')->setWidth(12);
        $essaySheet->getColumnDimension('D')->setWidth(55);
        $essaySheet->freezePane('A2');

        // ===== SHEET 3: Panduan =====
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Panduan');

        $guideSheet->setCellValue('A1', 'PANDUAN IMPORT BANK SOAL');
        $guideSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6366F1'));
        $guideSheet->mergeCells('A1:D1');

        $guide = [
            ['', ''],
            ['SHEET "Soal Pilihan Ganda"', ''],
            ['Kolom', 'Keterangan'],
            ['No', 'Nomor urut (opsional, untuk referensi saja)'],
            ['Kode Mapel', 'Kode mata pelajaran yang terdaftar di sistem (wajib, lihat daftar di bawah)'],
            ['Tipe Soal', 'Isi dengan: pg (wajib, otomatis untuk sheet ini)'],
            ['Bobot Nilai', 'Bobot penilaian soal, angka (wajib, default: 1)'],
            ['Pertanyaan', 'Teks pertanyaan soal (wajib)'],
            ['Opsi A - E', 'Isi opsi jawaban. Minimal 2 opsi harus diisi (A dan B). Opsi E boleh kosong'],
            ['Jawaban Benar', 'Huruf opsi jawaban yang benar: A, B, C, D, atau E (wajib)'],
            ['', ''],
            ['SHEET "Soal Essay"', ''],
            ['Kolom', 'Keterangan'],
            ['No', 'Nomor urut (opsional)'],
            ['Kode Mapel', 'Kode mata pelajaran (wajib)'],
            ['Bobot Nilai', 'Bobot penilaian (wajib)'],
            ['Pertanyaan', 'Teks pertanyaan (wajib)'],
            ['', ''],
            ['CATATAN PENTING:', ''],
            ['1.', 'Soal akan otomatis dikaitkan ke guru yang sedang login'],
            ['2.', 'Kode Mapel harus sesuai dengan yang ada di sistem (lihat daftar di bawah)'],
            ['3.', 'File harus berformat .xlsx atau .xls (maks 5MB)'],
            ['4.', 'Baris kosong akan dilewati otomatis'],
            ['5.', 'Anda bisa mengisi di kedua sheet sekaligus (PG dan Essay)'],
            ['6.', 'Status soal akan otomatis diset "aktif"'],
        ];

        $row = 2;
        foreach ($guide as $data) {
            $guideSheet->setCellValue('A' . $row, $data[0]);
            $guideSheet->setCellValue('B' . $row, $data[1]);

            // Style section headers
            if (in_array($data[0], ['SHEET "Soal Pilihan Ganda"', 'SHEET "Soal Essay"', 'CATATAN PENTING:'])) {
                $guideSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6366F1'));
            }
            if ($data[0] === 'Kolom') {
                $guideSheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
                $guideSheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EEF2FF'],
                    ],
                ]);
            }
            $row++;
        }

        // Daftar Mapel
        $row += 1;
        $guideSheet->setCellValue('A' . $row, 'DAFTAR MATA PELAJARAN TERSEDIA:');
        $guideSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('6366F1'));
        $row++;

        $guideSheet->setCellValue('A' . $row, 'Kode Mapel');
        $guideSheet->setCellValue('B' . $row, 'Nama Mapel');
        $guideSheet->setCellValue('C' . $row, 'Jurusan');
        $guideSheet->setCellValue('D' . $row, 'Tipe');
        $guideSheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $guideSheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2FF'],
            ],
        ]);
        $row++;

        $mapels = Mapel::with('jurusan')->where('is_active', true)->orderBy('nama_mapel')->get();
        foreach ($mapels as $mapel) {
            $guideSheet->setCellValue('A' . $row, $mapel->kode_mapel);
            $guideSheet->setCellValue('B' . $row, $mapel->nama_mapel);
            $guideSheet->setCellValue('C' . $row, $mapel->jurusan->nama_jurusan ?? 'Semua Jurusan');
            $guideSheet->setCellValue('D' . $row, $mapel->is_umum ? 'Umum' : 'Kejuruan');
            $row++;
        }

        $guideSheet->getColumnDimension('A')->setWidth(22);
        $guideSheet->getColumnDimension('B')->setWidth(45);
        $guideSheet->getColumnDimension('C')->setWidth(35);
        $guideSheet->getColumnDimension('D')->setWidth(15);

        // Set active sheet to first
        $spreadsheet->setActiveSheetIndex(0);

        // Write file
        $fileName = 'template_import_bank_soal.xlsx';
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

        // Build mapel lookup
        $mapelLookup = Mapel::where('is_active', true)->get();
        $mapelByCode = $mapelLookup->keyBy(function ($m) {
            return strtolower(trim($m->kode_mapel));
        });

        $previewData = [];
        $validTipeSoal = ['pg', 'essay', 'pg_kompleks', 'menjodohkan'];
        $validJawaban = ['a', 'b', 'c', 'd', 'e'];

        // Process each sheet
        $sheetCount = $spreadsheet->getSheetCount();
        for ($sheetIdx = 0; $sheetIdx < $sheetCount; $sheetIdx++) {
            $sheet = $spreadsheet->getSheet($sheetIdx);
            $sheetTitle = strtolower($sheet->getTitle());

            // Skip Panduan sheet
            if (str_contains($sheetTitle, 'panduan') || str_contains($sheetTitle, 'guide')) {
                continue;
            }

            $isEssaySheet = str_contains($sheetTitle, 'essay');
            $rows = $sheet->toArray(null, true, true, true);

            // Remove header
            $header = array_shift($rows);

            foreach ($rows as $rowIdx => $row) {
                $rowNum = $rowIdx + 1;
                $rowValues = array_values($row);

                // Skip empty rows
                if (empty(array_filter($rowValues, fn($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $rowErrors = [];

                if ($isEssaySheet) {
                    // Essay format: No | Kode Mapel | Bobot | Pertanyaan
                    $kodeMapel = strtolower(trim($rowValues[1] ?? ''));
                    $bobot = intval($rowValues[2] ?? 1);
                    $pertanyaan = trim($rowValues[3] ?? '');
                    $tipeSoal = 'essay';

                    // Validate
                    $resolvedMapel = null;
                    if (empty($kodeMapel)) {
                        $rowErrors[] = 'Kode Mapel kosong';
                    }
                    else {
                        $resolvedMapel = $mapelByCode[$kodeMapel] ?? null;
                        if (!$resolvedMapel) {
                            $rowErrors[] = "Kode Mapel '{$rowValues[1]}' tidak ditemukan";
                        }
                    }

                    if ($bobot < 1) {
                        $rowErrors[] = 'Bobot Nilai harus minimal 1';
                    }

                    if (empty($pertanyaan)) {
                        $rowErrors[] = 'Pertanyaan kosong';
                    }

                    $previewData[] = [
                        'sheet' => $sheet->getTitle(),
                        'row' => $rowNum,
                        'tipe_soal' => 'essay',
                        'kode_mapel' => $rowValues[1] ?? '',
                        'mapel' => $resolvedMapel,
                        'bobot_nilai' => $bobot,
                        'pertanyaan' => $pertanyaan,
                        'opsi' => [],
                        'jawaban_benar' => '-',
                        'errors' => $rowErrors,
                        'valid' => empty($rowErrors),
                    ];
                }
                else {
                    // PG format: No | Kode Mapel | Tipe Soal | Bobot | Pertanyaan | A | B | C | D | E | Jawaban
                    $kodeMapel = strtolower(trim($rowValues[1] ?? ''));
                    $tipeSoal = strtolower(trim($rowValues[2] ?? 'pg'));
                    $bobot = intval($rowValues[3] ?? 1);
                    $pertanyaan = trim($rowValues[4] ?? '');
                    $opsiA = trim($rowValues[5] ?? '');
                    $opsiB = trim($rowValues[6] ?? '');
                    $opsiC = trim($rowValues[7] ?? '');
                    $opsiD = trim($rowValues[8] ?? '');
                    $opsiE = trim($rowValues[9] ?? '');
                    $jawabanBenar = strtolower(trim($rowValues[10] ?? ''));

                    // Validate mapel
                    $resolvedMapel = null;
                    if (empty($kodeMapel)) {
                        $rowErrors[] = 'Kode Mapel kosong';
                    }
                    else {
                        $resolvedMapel = $mapelByCode[$kodeMapel] ?? null;
                        if (!$resolvedMapel) {
                            $rowErrors[] = "Kode Mapel '{$rowValues[1]}' tidak ditemukan";
                        }
                    }

                    // Validate tipe soal
                    if (empty($tipeSoal)) {
                        $tipeSoal = 'pg';
                    }
                    if (!in_array($tipeSoal, $validTipeSoal)) {
                        $rowErrors[] = "Tipe Soal '{$rowValues[2]}' tidak valid";
                    }

                    if ($bobot < 1) {
                        $rowErrors[] = 'Bobot Nilai harus minimal 1';
                    }

                    if (empty($pertanyaan)) {
                        $rowErrors[] = 'Pertanyaan kosong';
                    }

                    // Validate opsi (minimal A dan B)
                    if (in_array($tipeSoal, ['pg', 'pg_kompleks'])) {
                        if (empty($opsiA)) {
                            $rowErrors[] = 'Opsi A kosong';
                        }
                        if (empty($opsiB)) {
                            $rowErrors[] = 'Opsi B kosong';
                        }

                        // Validate jawaban benar
                        if (empty($jawabanBenar)) {
                            $rowErrors[] = 'Jawaban Benar kosong';
                        }
                        elseif (!in_array($jawabanBenar, $validJawaban)) {
                            $rowErrors[] = "Jawaban Benar '{$rowValues[10]}' tidak valid (gunakan: A/B/C/D/E)";
                        }
                        else {
                            // Check the referenced opsi is not empty
                            $opsiMap = ['a' => $opsiA, 'b' => $opsiB, 'c' => $opsiC, 'd' => $opsiD, 'e' => $opsiE];
                            if (empty($opsiMap[$jawabanBenar])) {
                                $rowErrors[] = "Jawaban Benar merujuk ke Opsi " . strtoupper($jawabanBenar) . " yang kosong";
                            }
                        }
                    }

                    // Build opsi array
                    $opsi = [];
                    foreach (['A' => $opsiA, 'B' => $opsiB, 'C' => $opsiC, 'D' => $opsiD, 'E' => $opsiE] as $label => $isi) {
                        if (!empty($isi)) {
                            $opsi[] = [
                                'label' => $label,
                                'isi' => $isi,
                                'is_correct' => strtolower($label) === $jawabanBenar,
                            ];
                        }
                    }

                    $previewData[] = [
                        'sheet' => $sheet->getTitle(),
                        'row' => $rowNum,
                        'tipe_soal' => $tipeSoal,
                        'kode_mapel' => $rowValues[1] ?? '',
                        'mapel' => $resolvedMapel,
                        'bobot_nilai' => $bobot,
                        'pertanyaan' => $pertanyaan,
                        'opsi' => $opsi,
                        'jawaban_benar' => strtoupper($jawabanBenar),
                        'errors' => $rowErrors,
                        'valid' => empty($rowErrors),
                    ];
                }
            }
        }

        $validCount = collect($previewData)->where('valid', true)->count();
        $errorCount = collect($previewData)->where('valid', false)->count();

        // Store in session
        session(['import_banksoal_preview' => $previewData]);
        session(['import_banksoal_file_name' => $file->getClientOriginalName()]);

        return view('admin.import-banksoal.preview', compact('previewData', 'validCount', 'errorCount'));
    }

    /**
     * Execute the import
     */
    public function import(Request $request)
    {
        $previewData = session('import_banksoal_preview');

        if (!$previewData) {
            return redirect()->route('admin.import-banksoal.index')
                ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang.');
        }

        // Check guru profile
        $guru = auth()->user()->guru;
        if (!$guru) {
            // For admin/superadmin without guru profile, use first guru
            $guru = Guru::first();
            if (!$guru) {
                return redirect()->route('admin.import-banksoal.index')
                    ->with('error', 'Tidak ada profil guru yang tersedia untuk mengaitkan soal.');
            }
        }

        $skipErrors = $request->boolean('skip_errors');
        $importData = collect($previewData)->filter(function ($row) use ($skipErrors) {
            return $skipErrors ? $row['valid'] : true;
        });

        if (!$skipErrors && collect($previewData)->where('valid', false)->count() > 0) {
            return back()->with('error', 'Terdapat data yang belum valid. Perbaiki file atau centang "Lewati baris bermasalah".');
        }

        $validRows = $importData->where('valid', true);

        if ($validRows->isEmpty()) {
            return redirect()->route('admin.import-banksoal.index')
                ->with('error', 'Tidak ada data valid untuk diimport.');
        }

        // Large imports are processed in the background so the request doesn't
        // tie up a PHP-FPM worker / risk a timeout while hundreds of rows are inserted.
        if ($validRows->count() > 150) {
            $batch = ImportBatch::create([
                'created_by' => auth()->id(),
                'status' => 'processing',
            ]);

            ImportBankSoalJob::dispatch($batch->id, $validRows->values()->all(), $guru->id);

            session()->forget(['import_banksoal_preview', 'import_banksoal_file_name']);

            return redirect()->route('admin.import-banksoal.result', ['batch' => $batch->id]);
        }

        $successCount = 0;
        $importedSoals = [];

        DB::beginTransaction();
        try {
            foreach ($validRows as $row) {
                $bankSoal = BankSoal::create([
                    'mapel_id' => $row['mapel']->id,
                    'guru_id' => $guru->id,
                    'tipe_soal' => $row['tipe_soal'],
                    'bobot_nilai' => $row['bobot_nilai'],
                    'pertanyaan' => $row['pertanyaan'],
                    'status' => 'aktif',
                ]);

                // Save opsi jawaban for PG
                if (in_array($row['tipe_soal'], ['pg', 'pg_kompleks']) && !empty($row['opsi'])) {
                    foreach ($row['opsi'] as $opsi) {
                        OpsiJawaban::create([
                            'bank_soal_id' => $bankSoal->id,
                            'opsi_label' => $opsi['label'],
                            'isi_opsi' => $opsi['isi'],
                            'is_correct' => $opsi['is_correct'],
                        ]);
                    }
                }

                $importedSoals[] = [
                    'id' => $bankSoal->id,
                    'pertanyaan' => \Str::limit($row['pertanyaan'], 60),
                    'tipe_soal' => $row['tipe_soal'],
                    'mapel' => $row['mapel']->nama_mapel,
                ];

                $successCount++;
            }

            DB::commit();

            ActivityLog::log('import', 'bank_soal', "Import {$successCount} soal dari file Excel");

            session(['import_banksoal_result' => $importedSoals]);
            session(['import_banksoal_success_count' => $successCount]);
            session()->forget(['import_banksoal_preview', 'import_banksoal_file_name']);

            return redirect()->route('admin.import-banksoal.result');

        }
        catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('admin.import-banksoal.index')
                ->with('error', 'Gagal import data: ' . $e->getMessage());
        }
    }

    /**
     * Show import result — either the synchronous result stashed in session,
     * or the status of a background import batch (?batch=id, for large files).
     */
    public function result(Request $request)
    {
        if ($request->filled('batch')) {
            $batch = ImportBatch::find($request->query('batch'));

            if (!$batch) {
                return redirect()->route('admin.import-banksoal.index')
                    ->with('error', 'Batch import tidak ditemukan.');
            }

            if ($batch->status === 'processing') {
                return view('admin.import-banksoal.result', [
                    'processing' => true,
                    'batch' => $batch,
                    'importedSoals' => [],
                    'successCount' => 0,
                ]);
            }

            if ($batch->status === 'failed') {
                return redirect()->route('admin.import-banksoal.index')
                    ->with('error', 'Import gagal: ' . $batch->error_message);
            }

            return view('admin.import-banksoal.result', [
                'processing' => false,
                'batch' => $batch,
                'importedSoals' => $batch->imported_soals,
                'successCount' => $batch->success_count,
            ]);
        }

        $importedSoals = session('import_banksoal_result');
        $successCount = session('import_banksoal_success_count', 0);

        if (!$importedSoals) {
            return redirect()->route('admin.import-banksoal.index');
        }

        return view('admin.import-banksoal.result', [
            'processing' => false,
            'batch' => null,
            'importedSoals' => $importedSoals,
            'successCount' => $successCount,
        ]);
    }
}
