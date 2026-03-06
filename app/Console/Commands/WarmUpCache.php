<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Models\Ujian;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmUpCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warmup {--force : Force cache refresh}';

    /**
     * The console command description.
     */
    protected $description = 'Warm up cache for active exams and frequently accessed data';

    protected $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔥 Warming up cache...');
        $this->newLine();

        $force = $this->option('force');

        if ($force) {
            $this->warn('Force mode: Clearing existing cache...');
            Cache::flush();
            $this->newLine();
        }

        // Warm up active exams
        $this->warmUpActiveExams();

        // Warm up static data
        $this->warmUpStaticData();

        // Warm up routes
        $this->warmUpRoutes();

        $this->newLine();
        $this->info('✅ Cache warm-up completed!');

        return 0;
    }

    /**
     * Warm up active exams cache
     */
    protected function warmUpActiveExams()
    {
        $this->info('📚 Warming up active exams...');

        $activeUjians = Ujian::with(['mapel', 'bankSoals.opsiJawabans'])
            ->where('is_published', true)
            ->where('tanggal_ujian', '>=', now()->subDays(1))
            ->where('tanggal_ujian', '<=', now()->addDays(7))
            ->get();

        $bar = $this->output->createProgressBar($activeUjians->count());
        $bar->start();

        foreach ($activeUjians as $ujian) {
            // Cache ujian data
            Cache::put("ujian:{$ujian->id}", $ujian, 3600);

            // Cache soal order
            $soalIds = $ujian->bankSoals->pluck('id')->toArray();
            Cache::put("ujian:{$ujian->id}:soals", $ujian->bankSoals, 3600);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Cached {$activeUjians->count()} active exams");
    }

    /**
     * Warm up static data cache
     */
    protected function warmUpStaticData()
    {
        $this->info('📊 Warming up static data...');

        // Cache mapel
        $mapels = \App\Models\Mapel::all();
        Cache::put('mapels:all', $mapels, 86400);
        $this->info("✓ Cached {$mapels->count()} mata pelajaran");

        // Cache kelas
        $kelas = \App\Models\Kelas::with('jurusan')->get();
        Cache::put('kelas:all', $kelas, 86400);
        $this->info("✓ Cached {$kelas->count()} kelas");

        // Cache jurusan
        $jurusans = \App\Models\Jurusan::all();
        Cache::put('jurusans:all', $jurusans, 86400);
        $this->info("✓ Cached {$jurusans->count()} jurusan");

        // Cache settings
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        Cache::put('settings:all', $settings, 86400);
        $this->info("✓ Cached system settings");
    }

    /**
     * Warm up routes cache
     */
    protected function warmUpRoutes()
    {
        $this->info('🛣️  Caching routes...');
        
        $this->call('route:cache');
        
        $this->info('✓ Routes cached');
    }
}
