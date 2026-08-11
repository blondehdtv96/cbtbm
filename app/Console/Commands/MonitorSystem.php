<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class MonitorSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:monitor {--interval=60 : Monitoring interval in seconds}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor system performance for 500+ concurrent users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');

        $this->info('🔍 Starting system monitoring...');
        $this->info("Interval: {$interval} seconds");
        $this->info('Press Ctrl+C to stop');
        $this->newLine();

        while (true) {
            $this->displayMetrics();
            sleep($interval);
        }

        return 0;
    }

    /**
     * Display system metrics
     */
    protected function displayMetrics()
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $this->info("=== System Metrics [{$timestamp}] ===");
        $this->newLine();

        // Database metrics
        $this->displayDatabaseMetrics();

        // Redis metrics
        $this->displayRedisMetrics();

        // Queue metrics
        $this->displayQueueMetrics();

        // Active exams
        $this->displayActiveExams();

        $this->newLine();
        $this->line(str_repeat('=', 60));
        $this->newLine();
    }

    /**
     * Display database metrics
     */
    protected function displayDatabaseMetrics()
    {
        $this->line('📊 <fg=cyan>Database Metrics:</>');

        try {
            // Connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            
            $currentConn = $connections[0]->Value ?? 0;
            $maxConn = $maxConnections[0]->Value ?? 0;
            $connPercent = $maxConn > 0 ? round(($currentConn / $maxConn) * 100, 2) : 0;

            $this->line("  Connections: {$currentConn}/{$maxConn} ({$connPercent}%)");

            // Slow queries
            $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            $this->line("  Slow Queries: " . ($slowQueries[0]->Value ?? 0));

            // Questions (total queries)
            $questions = DB::select("SHOW STATUS LIKE 'Questions'");
            $this->line("  Total Queries: " . ($questions[0]->Value ?? 0));

            // InnoDB buffer pool usage
            $bufferPool = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_total'");
            $bufferPoolFree = DB::select("SHOW STATUS LIKE 'Innodb_buffer_pool_pages_free'");
            
            $total = $bufferPool[0]->Value ?? 0;
            $free = $bufferPoolFree[0]->Value ?? 0;
            $used = $total - $free;
            $usedPercent = $total > 0 ? round(($used / $total) * 100, 2) : 0;

            $this->line("  Buffer Pool: {$usedPercent}% used");

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Display Redis metrics
     */
    protected function displayRedisMetrics()
    {
        $this->line('🔴 <fg=red>Redis Metrics:</>');

        try {
            $info = Redis::info();

            // Connected clients
            $clients = $info['connected_clients'] ?? 0;
            $this->line("  Connected Clients: {$clients}");

            // Memory usage
            $memoryUsed = $info['used_memory_human'] ?? 'N/A';
            $memoryPeak = $info['used_memory_peak_human'] ?? 'N/A';
            $this->line("  Memory Used: {$memoryUsed} (Peak: {$memoryPeak})");

            // Hit rate
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
            $this->line("  Hit Rate: {$hitRate}% ({$hits} hits, {$misses} misses)");

            // Keys
            $keys = Redis::dbSize();
            $this->line("  Total Keys: {$keys}");

            // Ops per second
            $opsPerSec = $info['instantaneous_ops_per_sec'] ?? 0;
            $this->line("  Ops/sec: {$opsPerSec}");

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Display queue metrics
     */
    protected function displayQueueMetrics()
    {
        $this->line('⚙️  <fg=yellow>Queue Metrics:</>');

        try {
            // Get queue sizes
            $highQueue = Redis::llen('queues:high');
            $defaultQueue = Redis::llen('queues:default');
            $lowQueue = Redis::llen('queues:low');

            $this->line("  High Priority: {$highQueue} jobs");
            $this->line("  Default: {$defaultQueue} jobs");
            $this->line("  Low Priority: {$lowQueue} jobs");

            // Failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $this->line("  Failed Jobs: {$failedJobs}");

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Display active exams
     */
    protected function displayActiveExams()
    {
        $this->line('📝 <fg=green>Active Exams:</>');

        try {
            // Active exams today
            $activeExams = DB::table('ujians')
                ->whereIn('status', ['publish', 'berlangsung'])
                ->whereDate('tanggal_mulai', now()->toDateString())
                ->count();

            $this->line("  Published Today: {$activeExams}");

            // Students taking exams now
            $activeStudents = DB::table('peserta_ujians')
                ->where('status', 'sedang')
                ->count();

            $this->line("  Students Active: {$activeStudents}");

            // Completed exams today
            $completedToday = DB::table('peserta_ujians')
                ->where('status', 'selesai')
                ->whereDate('waktu_selesai', now()->toDateString())
                ->count();

            $this->line("  Completed Today: {$completedToday}");

            // Answers saved in last minute
            $recentAnswers = DB::table('jawaban_siswas')
                ->where('updated_at', '>=', now()->subMinute())
                ->count();

            $this->line("  Answers/min: {$recentAnswers}");

        } catch (\Exception $e) {
            $this->error("  Error: " . $e->getMessage());
        }

        $this->newLine();
    }
}
