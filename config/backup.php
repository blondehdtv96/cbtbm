<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Storage Disk
    |--------------------------------------------------------------------------
    |
    | Filesystem disk (see config/filesystems.php) where database backup
    | files are stored. Kept off the "public" disk since dumps contain
    | sensitive data and must never be web-accessible directly.
    |
    */
    'disk' => env('BACKUP_DISK', 'local'),

    'path' => 'backups',

    /*
    |--------------------------------------------------------------------------
    | mysqldump / mysql Binary Paths
    |--------------------------------------------------------------------------
    |
    | Used for the fast CLI backup/restore path. Left as bare command names
    | by default, which resolves via PATH on most Linux servers. On Windows
    |/ XAMPP dev machines these binaries are usually not on PATH (e.g.
    | C:\xampp\mysql\bin\mysqldump.exe) — set DB_MYSQLDUMP_PATH / DB_MYSQL_PATH
    | in .env to the full path if you want the CLI path used locally too.
    | When the binary can't be found or the process fails, the app falls
    | back automatically to a pure-PHP dump/restore, so this is optional.
    |
    */
    'mysqldump_path' => env('DB_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_path' => env('DB_MYSQL_PATH', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Upload Limit
    |--------------------------------------------------------------------------
    |
    | Max size (in KB) accepted for an uploaded .sql / .sql.gz backup file.
    | Also make sure php.ini upload_max_filesize / post_max_size (see
    | php-fpm-optimization.conf) are large enough to actually receive it.
    |
    */
    'max_upload_kb' => env('BACKUP_MAX_UPLOAD_KB', 51200),

    /*
    |--------------------------------------------------------------------------
    | Pure-PHP Dump Chunk Size
    |--------------------------------------------------------------------------
    |
    | Rows fetched per batch when the CLI fallback dump/restore path is
    | used, to keep memory usage bounded on large tables.
    |
    */
    'chunk_size' => env('BACKUP_CHUNK_SIZE', 500),
];
