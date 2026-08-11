<?php

namespace App\Jobs;

use App\Models\BankSoal;
use App\Models\OpsiJawaban;
use App\Models\ActivityLog;
use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportBankSoalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    /**
     * @param array $rows Validated rows from ImportBankSoalController::preview(), each with a 'mapel' model.
     */
    public function __construct(
        protected int $importBatchId,
        protected array $rows,
        protected int $guruId,
    ) {
    }

    public function handle(): void
    {
        $batch = ImportBatch::find($this->importBatchId);
        if (!$batch) {
            return;
        }

        $successCount = 0;
        $importedSoals = [];

        try {
            DB::transaction(function () use (&$successCount, &$importedSoals) {
                foreach ($this->rows as $row) {
                    $bankSoal = BankSoal::create([
                        'mapel_id' => $row['mapel']->id,
                        'guru_id' => $this->guruId,
                        'tipe_soal' => $row['tipe_soal'],
                        'bobot_nilai' => $row['bobot_nilai'],
                        'pertanyaan' => $row['pertanyaan'],
                        'status' => 'aktif',
                    ]);

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
                        'pertanyaan' => Str::limit($row['pertanyaan'], 60),
                        'tipe_soal' => $row['tipe_soal'],
                        'mapel' => $row['mapel']->nama_mapel,
                    ];

                    $successCount++;
                }
            });

            $batch->update([
                'status' => 'completed',
                'success_count' => $successCount,
                'imported_soals' => $importedSoals,
            ]);

            ActivityLog::log('import', 'bank_soal', "Import {$successCount} soal dari file Excel (background)");
        } catch (\Exception $e) {
            Log::error('ImportBankSoalJob failed', ['batch_id' => $this->importBatchId, 'error' => $e->getMessage()]);

            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
