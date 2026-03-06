<?php

namespace App\Jobs;

use App\Models\JawabanSiswa;
use App\Services\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SaveJawabanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pesertaUjianId;
    public $bankSoalId;
    public $jawaban;
    public $isRagu;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct($pesertaUjianId, $bankSoalId, $jawaban, $isRagu = false)
    {
        $this->pesertaUjianId = $pesertaUjianId;
        $this->bankSoalId = $bankSoalId;
        $this->jawaban = $jawaban;
        $this->isRagu = $isRagu;
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService): void
    {
        try {
            JawabanSiswa::updateOrCreate(
                [
                    'peserta_ujian_id' => $this->pesertaUjianId,
                    'bank_soal_id' => $this->bankSoalId,
                ],
                [
                    'jawaban_dipilih' => $this->jawaban,
                    'is_ragu' => $this->isRagu,
                ]
            );

            // Invalidate cache
            $cacheService->invalidateJawaban($this->pesertaUjianId);

            Log::info('Jawaban saved via queue', [
                'peserta_ujian_id' => $this->pesertaUjianId,
                'bank_soal_id' => $this->bankSoalId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save jawaban via queue', [
                'error' => $e->getMessage(),
                'peserta_ujian_id' => $this->pesertaUjianId,
                'bank_soal_id' => $this->bankSoalId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SaveJawabanJob failed permanently', [
            'peserta_ujian_id' => $this->pesertaUjianId,
            'bank_soal_id' => $this->bankSoalId,
            'error' => $exception->getMessage(),
        ]);
    }
}
