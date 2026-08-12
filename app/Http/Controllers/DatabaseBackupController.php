<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use RuntimeException;

class DatabaseBackupController extends Controller
{
    public function __construct(protected DatabaseBackupService $backups)
    {
    }

    public function index()
    {
        $tables = $this->backups->listTables();
        $backupFiles = $this->backups->list();
        $totalDbSizeHuman = $this->backups->humanSize($this->backups->totalSize());
        $diskUsage = $this->backups->diskUsage();
        $database = config('database.connections.mysql.database');

        return view('superadmin.backup.index', compact(
            'tables', 'backupFiles', 'totalDbSizeHuman', 'diskUsage', 'database'
        ));
    }

    public function store(Request $request)
    {
        try {
            $compress = $request->boolean('compress', true);
            $result = $this->backups->create($compress);

            return redirect()->route('superadmin.backup.index')
                ->with('success', "Backup berhasil dibuat: {$result['filename']} ({$result['size_human']}).");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backup.index')
                ->with('error', 'Gagal membuat backup: '.$e->getMessage());
        }
    }

    public function download(string $filename)
    {
        try {
            if (!$this->backups->exists($filename)) {
                abort(404, 'File backup tidak ditemukan.');
            }

            return response()->download($this->backups->getFullPath($filename));
        } catch (RuntimeException $e) {
            abort(400, $e->getMessage());
        }
    }

    public function destroy(string $filename)
    {
        try {
            $this->backups->delete($filename);

            return redirect()->route('superadmin.backup.index')
                ->with('success', "Backup {$filename} berhasil dihapus.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backup.index')
                ->with('error', 'Gagal menghapus backup: '.$e->getMessage());
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:'.((int) config('backup.max_upload_kb', 51200)),
        ]);

        try {
            $result = $this->backups->storeUpload($request->file('backup_file'));

            return redirect()->route('superadmin.backup.index')
                ->with('success', "File backup berhasil diupload: {$result['filename']}. Anda bisa menjalankan Restore dari daftar backup.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backup.index')
                ->with('error', 'Gagal upload file backup: '.$e->getMessage());
        }
    }

    public function restore(Request $request, string $filename)
    {
        $expectedDatabase = config('database.connections.mysql.database');

        $request->validate([
            'confirm_database' => 'required|string',
            'confirm_ack' => 'required|accepted',
        ], [
            'confirm_database.required' => 'Ketik nama database untuk konfirmasi.',
            'confirm_ack.required' => 'Anda harus menyetujui bahwa data saat ini akan ditimpa.',
            'confirm_ack.accepted' => 'Anda harus menyetujui bahwa data saat ini akan ditimpa.',
        ]);

        if ($request->input('confirm_database') !== $expectedDatabase) {
            return redirect()->route('superadmin.backup.index')
                ->with('error', 'Konfirmasi gagal: nama database yang diketik tidak sesuai.');
        }

        try {
            $result = $this->backups->restore($filename);

            return redirect()->route('superadmin.backup.index')
                ->with('success', "Restore berhasil (metode: {$result['method']}). Safety backup sebelum restore: {$result['safety_backup']}.");
        } catch (\Throwable $e) {
            return redirect()->route('superadmin.backup.index')
                ->with('error', 'Gagal restore database: '.$e->getMessage());
        }
    }
}
