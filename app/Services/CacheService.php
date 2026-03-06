<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache TTL in seconds
     */
    const TTL_SHORT = 300;      // 5 minutes
    const TTL_MEDIUM = 1800;    // 30 minutes
    const TTL_LONG = 3600;      // 1 hour
    const TTL_VERY_LONG = 86400; // 24 hours

    /**
     * Cache ujian data
     */
    public function cacheUjian($ujianId)
    {
        $key = "ujian:{$ujianId}";
        
        return Cache::remember($key, self::TTL_MEDIUM, function () use ($ujianId) {
            return \App\Models\Ujian::with(['mapel', 'bankSoals.opsiJawabans'])
                ->find($ujianId);
        });
    }

    /**
     * Cache soal for ujian
     */
    public function cacheSoalUjian($ujianId, $soalOrder)
    {
        $key = "ujian:{$ujianId}:soals";
        
        return Cache::remember($key, self::TTL_LONG, function () use ($soalOrder) {
            return \App\Models\BankSoal::with('opsiJawabans')
                ->whereIn('id', $soalOrder)
                ->get()
                ->sortBy(function ($soal) use ($soalOrder) {
                    return array_search($soal->id, $soalOrder);
                })
                ->values();
        });
    }

    /**
     * Cache peserta ujian
     */
    public function cachePesertaUjian($ujianId, $siswaId)
    {
        $key = "peserta:{$ujianId}:{$siswaId}";
        
        return Cache::remember($key, self::TTL_SHORT, function () use ($ujianId, $siswaId) {
            return \App\Models\PesertaUjian::where('ujian_id', $ujianId)
                ->where('siswa_id', $siswaId)
                ->first();
        });
    }

    /**
     * Cache jawaban siswa
     */
    public function cacheJawabanSiswa($pesertaUjianId)
    {
        $key = "jawaban:peserta:{$pesertaUjianId}";
        
        return Cache::remember($key, self::TTL_SHORT, function () use ($pesertaUjianId) {
            return \App\Models\JawabanSiswa::where('peserta_ujian_id', $pesertaUjianId)
                ->pluck('jawaban_dipilih', 'bank_soal_id')
                ->toArray();
        });
    }

    /**
     * Invalidate ujian cache
     */
    public function invalidateUjian($ujianId)
    {
        Cache::forget("ujian:{$ujianId}");
        Cache::forget("ujian:{$ujianId}:soals");
    }

    /**
     * Invalidate peserta cache
     */
    public function invalidatePeserta($ujianId, $siswaId)
    {
        Cache::forget("peserta:{$ujianId}:{$siswaId}");
    }

    /**
     * Invalidate jawaban cache
     */
    public function invalidateJawaban($pesertaUjianId)
    {
        Cache::forget("jawaban:peserta:{$pesertaUjianId}");
    }

    /**
     * Cache user data
     */
    public function cacheUser($userId)
    {
        $key = "user:{$userId}";
        
        return Cache::remember($key, self::TTL_MEDIUM, function () use ($userId) {
            return \App\Models\User::with('siswa.kelas')->find($userId);
        });
    }

    /**
     * Batch cache multiple items
     */
    public function batchCache(array $items, $ttl = self::TTL_MEDIUM)
    {
        foreach ($items as $key => $value) {
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Get or set cache with lock (prevent cache stampede)
     */
    public function getOrSetWithLock($key, $callback, $ttl = self::TTL_MEDIUM)
    {
        $value = Cache::get($key);
        
        if ($value !== null) {
            return $value;
        }

        // Acquire lock
        $lock = Cache::lock("lock:{$key}", 10);

        try {
            if ($lock->get()) {
                // Double check
                $value = Cache::get($key);
                if ($value !== null) {
                    return $value;
                }

                // Generate value
                $value = $callback();
                Cache::put($key, $value, $ttl);
                
                return $value;
            }
        } finally {
            optional($lock)->release();
        }

        // If lock failed, wait and retry
        sleep(1);
        return Cache::get($key) ?? $callback();
    }

    /**
     * Warm up cache for active exams
     */
    public function warmUpActiveExams()
    {
        $activeUjians = \App\Models\Ujian::where('is_published', true)
            ->where('tanggal_ujian', '>=', now()->subDays(1))
            ->where('tanggal_ujian', '<=', now()->addDays(1))
            ->get();

        foreach ($activeUjians as $ujian) {
            $this->cacheUjian($ujian->id);
        }
    }

    /**
     * Clear all exam-related cache
     */
    public function clearExamCache()
    {
        $pattern = 'ujian:*';
        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del($keys);
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        return [
            'redis_info' => Redis::info(),
            'cache_hits' => Cache::get('cache_hits', 0),
            'cache_misses' => Cache::get('cache_misses', 0),
        ];
    }
}
