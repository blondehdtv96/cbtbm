<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling 500+ concurrent users
    |
    */

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                // Connection pooling
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]) : [],
            // Connection pool settings
            'pool' => [
                'min' => env('DB_POOL_MIN', 5),
                'max' => env('DB_POOL_MAX', 50),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    */
    'query' => [
        // Enable query caching
        'cache_enabled' => env('QUERY_CACHE_ENABLED', true),
        'cache_ttl' => env('QUERY_CACHE_TTL', 300), // 5 minutes
        
        // Eager loading
        'eager_load' => true,
        
        // Chunk size for large datasets
        'chunk_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Optimization
    |--------------------------------------------------------------------------
    */
    'indexes' => [
        'jawaban_siswas' => [
            'peserta_ujian_id',
            'bank_soal_id',
            ['peserta_ujian_id', 'bank_soal_id'], // Composite
        ],
        'peserta_ujians' => [
            'ujian_id',
            'siswa_id',
            'status',
            ['ujian_id', 'status'], // Composite
        ],
        'bank_soals' => [
            'mapel_id',
            'tipe_soal',
        ],
        'users' => [
            'email',
            'role',
            'is_active',
        ],
    ],
];
