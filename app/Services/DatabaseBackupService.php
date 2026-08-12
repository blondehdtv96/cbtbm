<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Backs up / restores the MySQL database by reading the live schema
 * (SHOW TABLES / information_schema) rather than a hardcoded table list,
 * so it always matches whatever tables actually exist on the server.
 *
 * Prefers the mysqldump/mysql CLI (fast, handles large tables well) and
 * falls back automatically to a pure-PHP dump/restore when the binaries
 * aren't available (e.g. not on PATH) or the process fails, so it works
 * on both a locked-down host and a full VPS.
 */
class DatabaseBackupService
{
    protected string $disk;
    protected string $path;

    public function __construct()
    {
        $this->disk = config('backup.disk', 'local');
        $this->path = config('backup.path', 'backups');
    }

    /**
     * Live list of tables in the current database with row counts and size.
     */
    public function listTables(): array
    {
        $database = config('database.connections.mysql.database');

        $rows = DB::select(
            'SELECT table_name AS `name`, table_rows AS `rows`, data_length AS `data_length`, index_length AS `index_length`
             FROM information_schema.tables
             WHERE table_schema = ?
             ORDER BY table_name',
            [$database]
        );

        return array_map(function ($row) {
            $size = (int) $row->data_length + (int) $row->index_length;

            return [
                'name' => $row->name,
                'rows' => (int) $row->rows,
                'size' => $size,
                'size_human' => $this->humanSize($size),
            ];
        }, $rows);
    }

    public function totalSize(): int
    {
        return array_sum(array_column($this->listTables(), 'size'));
    }

    /**
     * List backup files present on disk, newest first.
     */
    public function list(): array
    {
        $disk = Storage::disk($this->disk);
        $this->ensureDirectory($disk);

        return collect($disk->files($this->path))
            ->filter(fn ($file) => Str::endsWith($file, ['.sql', '.sql.gz']))
            ->map(function ($file) use ($disk) {
                $size = $disk->size($file);

                return [
                    'filename' => basename($file),
                    'size' => $size,
                    'size_human' => $this->humanSize($size),
                    'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($file)),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * Create a new backup. Returns details about the created file.
     */
    public function create(bool $compress = true, bool $silent = false): array
    {
        @set_time_limit(300);

        $disk = Storage::disk($this->disk);
        $this->ensureDirectory($disk);

        $database = config('database.connections.mysql.database');
        $baseFilename = sprintf('backup_%s_%s.sql', $database, now()->format('Y-m-d_His'));
        $fullPath = $disk->path($this->path.'/'.$baseFilename);

        $method = $this->dumpViaCli($fullPath) ? 'mysqldump' : null;
        if (!$method) {
            $this->dumpViaPhp($fullPath);
            $method = 'php';
        }

        $filename = $baseFilename;
        if ($compress) {
            $gzPath = $fullPath.'.gz';
            $this->gzipFile($fullPath, $gzPath);
            @unlink($fullPath);
            $fullPath = $gzPath;
            $filename = $baseFilename.'.gz';
        }

        $size = filesize($fullPath) ?: 0;

        if (!$silent) {
            ActivityLog::log('create', 'backup', "Membuat backup database: {$filename} ({$this->humanSize($size)}, metode: {$method})");
        }

        return [
            'filename' => $filename,
            'size' => $size,
            'size_human' => $this->humanSize($size),
            'method' => $method,
        ];
    }

    /**
     * Store an uploaded .sql / .sql.gz file into the backups folder.
     */
    public function storeUpload(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $isGz = Str::endsWith(strtolower($originalName), '.sql.gz');
        $isSql = !$isGz && strtolower($file->getClientOriginalExtension()) === 'sql';

        if (!$isGz && !$isSql) {
            throw new RuntimeException('Format file tidak didukung. Gunakan file .sql atau .sql.gz.');
        }

        $maxKb = (int) config('backup.max_upload_kb', 51200);
        if ($file->getSize() > $maxKb * 1024) {
            throw new RuntimeException('Ukuran file melebihi batas maksimum ('.$this->humanSize($maxKb * 1024).').');
        }

        $disk = Storage::disk($this->disk);
        $this->ensureDirectory($disk);

        $safeName = 'upload_'.now()->format('Y-m-d_His').($isGz ? '.sql.gz' : '.sql');
        $file->storeAs($this->path, $safeName, ['disk' => $this->disk]);

        ActivityLog::log('upload', 'backup', "Upload file backup: {$safeName} (nama asli: {$originalName})");

        return ['filename' => $safeName];
    }

    /**
     * Restore the database from a stored backup file. Always takes a
     * safety backup first so the restore can be undone.
     */
    public function restore(string $filename): array
    {
        $this->assertSafeFilename($filename);
        @set_time_limit(300);

        $disk = Storage::disk($this->disk);
        $relative = $this->path.'/'.$filename;

        if (!$disk->exists($relative)) {
            throw new RuntimeException("File backup tidak ditemukan: {$filename}");
        }

        $safety = $this->create(true, true);

        $fullPath = $disk->path($relative);
        $sqlPath = $fullPath;
        $tempDecompressed = null;

        if (Str::endsWith(strtolower($filename), '.gz')) {
            $tempDecompressed = $fullPath.'.restore_tmp.sql';
            $this->gunzipFile($fullPath, $tempDecompressed);
            $sqlPath = $tempDecompressed;
        }

        try {
            $method = $this->restoreViaCli($sqlPath) ? 'mysql' : null;
            if (!$method) {
                $this->restoreViaPhp($sqlPath);
                $method = 'php';
            }
        } finally {
            if ($tempDecompressed && file_exists($tempDecompressed)) {
                @unlink($tempDecompressed);
            }
        }

        ActivityLog::log('restore', 'backup', "Restore database dari backup: {$filename} (metode: {$method}). Safety backup dibuat: {$safety['filename']}");

        return ['method' => $method, 'safety_backup' => $safety['filename']];
    }

    public function delete(string $filename): void
    {
        $this->assertSafeFilename($filename);
        $disk = Storage::disk($this->disk);
        $relative = $this->path.'/'.$filename;

        if ($disk->exists($relative)) {
            $disk->delete($relative);
            ActivityLog::log('delete', 'backup', "Menghapus file backup: {$filename}");
        }
    }

    public function exists(string $filename): bool
    {
        $this->assertSafeFilename($filename);

        return Storage::disk($this->disk)->exists($this->path.'/'.$filename);
    }

    public function getFullPath(string $filename): string
    {
        $this->assertSafeFilename($filename);

        return Storage::disk($this->disk)->path($this->path.'/'.$filename);
    }

    public function diskUsage(): array
    {
        $storagePath = storage_path();
        $free = @disk_free_space($storagePath) ?: 0;
        $total = @disk_total_space($storagePath) ?: 0;
        $used = $total - $free;

        return [
            'free' => $free,
            'total' => $total,
            'used' => $used,
            'free_human' => $this->humanSize($free),
            'total_human' => $this->humanSize($total),
            'used_human' => $this->humanSize($used),
            'used_percent' => $total > 0 ? round(($used / $total) * 100, 1) : 0,
        ];
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $i), 2).' '.$units[$i];
    }

    // -- CLI dump/restore (mysqldump / mysql) --------------------------------

    protected function dumpViaCli(string $fullPath): bool
    {
        $handle = @fopen($fullPath, 'wb');
        if (!$handle) {
            return false;
        }

        $success = false;

        try {
            $conn = config('database.connections.mysql');
            $args = [
                config('backup.mysqldump_path', 'mysqldump'),
                '--host='.$conn['host'],
                '--port='.$conn['port'],
                '--user='.$conn['username'],
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--default-character-set=utf8mb4',
                $conn['database'],
            ];

            $process = new Process($args);
            $process->setTimeout(300);
            if (!empty($conn['password'])) {
                $process->setEnv(['MYSQL_PWD' => $conn['password']]);
            }

            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });

            $success = $process->isSuccessful();
        } catch (\Throwable $e) {
            $success = false;
        } finally {
            fclose($handle);
        }

        if (!$success && file_exists($fullPath)) {
            @unlink($fullPath);
        }

        return $success;
    }

    protected function restoreViaCli(string $sqlPath): bool
    {
        $handle = @fopen($sqlPath, 'rb');
        if (!$handle) {
            return false;
        }

        try {
            $conn = config('database.connections.mysql');
            $args = [
                config('backup.mysql_path', 'mysql'),
                '--host='.$conn['host'],
                '--port='.$conn['port'],
                '--user='.$conn['username'],
                $conn['database'],
            ];

            $process = new Process($args);
            $process->setTimeout(300);
            if (!empty($conn['password'])) {
                $process->setEnv(['MYSQL_PWD' => $conn['password']]);
            }
            $process->setInput($handle);
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        } finally {
            fclose($handle);
        }
    }

    // -- Pure-PHP dump/restore fallback --------------------------------------

    protected function dumpViaPhp(string $fullPath): void
    {
        @set_time_limit(300);
        $handle = fopen($fullPath, 'wb');
        fwrite($handle, "-- CBT BM pure-PHP database backup\n-- Generated: ".now()->toDateTimeString()."\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $chunkSize = (int) config('backup.chunk_size', 500);
        $pdo = DB::connection()->getPdo();

        foreach (array_column($this->listTables(), 'name') as $table) {
            $createRow = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = $createRow->{'Create Table'} ?? null;
            if (!$createSql) {
                continue;
            }

            fwrite($handle, "-- ----------------------------\n-- Table: {$table}\n-- ----------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createSql.";\n\n");

            $columnMeta = DB::select("SHOW COLUMNS FROM `{$table}`");
            $columns = array_map(fn ($c) => $c->Field, $columnMeta);
            $hasIdPk = collect($columnMeta)->contains(fn ($c) => $c->Field === 'id' && $c->Key === 'PRI');
            $quotedColumns = '`'.implode('`, `', $columns).'`';

            $writeChunk = function ($rows) use ($handle, $table, $quotedColumns, $columns, $pdo) {
                if ($rows->isEmpty()) {
                    return;
                }

                $lines = [];
                foreach ($rows as $row) {
                    $rowArr = (array) $row;
                    $escaped = array_map(function ($col) use ($rowArr, $pdo) {
                        $val = $rowArr[$col];

                        return is_null($val) ? 'NULL' : $pdo->quote((string) $val);
                    }, $columns);
                    $lines[] = '('.implode(', ', $escaped).')';
                }

                fwrite($handle, "INSERT INTO `{$table}` ({$quotedColumns}) VALUES\n".implode(",\n", $lines).";\n");
            };

            $query = DB::table($table);
            if ($hasIdPk) {
                $query->orderBy('id')->chunkById($chunkSize, $writeChunk);
            } elseif (!empty($columns)) {
                $query->orderBy($columns[0])->chunk($chunkSize, $writeChunk);
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    protected function restoreViaPhp(string $sqlPath): void
    {
        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            throw new RuntimeException('Gagal membaca file backup.');
        }

        DB::unprepared($sql);
    }

    // -- Helpers --------------------------------------------------------------

    protected function gzipFile(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb9');
        while (!feof($in)) {
            gzwrite($out, fread($in, 524288));
        }
        fclose($in);
        gzclose($out);
    }

    protected function gunzipFile(string $source, string $destination): void
    {
        $in = gzopen($source, 'rb');
        $out = fopen($destination, 'wb');
        while (!gzeof($in)) {
            fwrite($out, gzread($in, 524288));
        }
        gzclose($in);
        fclose($out);
    }

    protected function ensureDirectory($disk): void
    {
        if (!$disk->exists($this->path)) {
            $disk->makeDirectory($this->path);
        }
    }

    protected function assertSafeFilename(string $filename): void
    {
        if ($filename === '' || $filename !== basename($filename) || Str::contains($filename, ['..'])) {
            throw new RuntimeException('Nama file tidak valid.');
        }
    }
}
