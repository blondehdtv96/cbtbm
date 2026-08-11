<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations.
     * 
     * Add indexes for better performance with 500+ concurrent users
     */
    public function up(): void
    {
        // Jawaban Siswas - Most frequently queried table
        if (Schema::hasTable('jawaban_siswas')) {
            Schema::table('jawaban_siswas', function (Blueprint $table) {
                if (Schema::hasColumn('jawaban_siswas', 'peserta_ujian_id') && !$this->indexExists('jawaban_siswas', 'idx_jawaban_peserta')) {
                    $table->index('peserta_ujian_id', 'idx_jawaban_peserta');
                }
                if (Schema::hasColumn('jawaban_siswas', 'bank_soal_id') && !$this->indexExists('jawaban_siswas', 'idx_jawaban_soal')) {
                    $table->index('bank_soal_id', 'idx_jawaban_soal');
                }
                if (Schema::hasColumn('jawaban_siswas', 'peserta_ujian_id') && 
                    Schema::hasColumn('jawaban_siswas', 'bank_soal_id') && 
                    !$this->indexExists('jawaban_siswas', 'idx_jawaban_composite')) {
                    $table->index(['peserta_ujian_id', 'bank_soal_id'], 'idx_jawaban_composite');
                }
                if (Schema::hasColumn('jawaban_siswas', 'is_correct') && !$this->indexExists('jawaban_siswas', 'idx_jawaban_correct')) {
                    $table->index('is_correct', 'idx_jawaban_correct');
                }
                if (Schema::hasColumn('jawaban_siswas', 'created_at') && !$this->indexExists('jawaban_siswas', 'idx_jawaban_created')) {
                    $table->index('created_at', 'idx_jawaban_created');
                }
            });
        }

        // Peserta Ujians - High traffic during exams
        if (Schema::hasTable('peserta_ujians')) {
            Schema::table('peserta_ujians', function (Blueprint $table) {
                if (Schema::hasColumn('peserta_ujians', 'ujian_id') && !$this->indexExists('peserta_ujians', 'idx_peserta_ujian')) {
                    $table->index('ujian_id', 'idx_peserta_ujian');
                }
                if (Schema::hasColumn('peserta_ujians', 'siswa_id') && !$this->indexExists('peserta_ujians', 'idx_peserta_siswa')) {
                    $table->index('siswa_id', 'idx_peserta_siswa');
                }
                if (Schema::hasColumn('peserta_ujians', 'status') && !$this->indexExists('peserta_ujians', 'idx_peserta_status')) {
                    $table->index('status', 'idx_peserta_status');
                }
                if (Schema::hasColumn('peserta_ujians', 'ujian_id') && 
                    Schema::hasColumn('peserta_ujians', 'status') && 
                    !$this->indexExists('peserta_ujians', 'idx_peserta_ujian_status')) {
                    $table->index(['ujian_id', 'status'], 'idx_peserta_ujian_status');
                }
                if (Schema::hasColumn('peserta_ujians', 'ujian_id') && 
                    Schema::hasColumn('peserta_ujians', 'siswa_id') && 
                    !$this->indexExists('peserta_ujians', 'idx_peserta_composite')) {
                    $table->index(['ujian_id', 'siswa_id'], 'idx_peserta_composite');
                }
                if (Schema::hasColumn('peserta_ujians', 'waktu_mulai') && !$this->indexExists('peserta_ujians', 'idx_peserta_waktu_mulai')) {
                    $table->index('waktu_mulai', 'idx_peserta_waktu_mulai');
                }
            });
        }

        // Bank Soals
        if (Schema::hasTable('bank_soals')) {
            Schema::table('bank_soals', function (Blueprint $table) {
                if (Schema::hasColumn('bank_soals', 'mapel_id') && !$this->indexExists('bank_soals', 'idx_soal_mapel')) {
                    $table->index('mapel_id', 'idx_soal_mapel');
                }
                if (Schema::hasColumn('bank_soals', 'tipe_soal') && !$this->indexExists('bank_soals', 'idx_soal_tipe')) {
                    $table->index('tipe_soal', 'idx_soal_tipe');
                }
                if (Schema::hasColumn('bank_soals', 'digunakan_count') && !$this->indexExists('bank_soals', 'idx_soal_digunakan')) {
                    $table->index('digunakan_count', 'idx_soal_digunakan');
                }
            });
        }

        // Opsi Jawabans
        if (Schema::hasTable('opsi_jawabans')) {
            Schema::table('opsi_jawabans', function (Blueprint $table) {
                if (Schema::hasColumn('opsi_jawabans', 'bank_soal_id') && !$this->indexExists('opsi_jawabans', 'idx_opsi_soal')) {
                    $table->index('bank_soal_id', 'idx_opsi_soal');
                }
                if (Schema::hasColumn('opsi_jawabans', 'is_correct') && !$this->indexExists('opsi_jawabans', 'idx_opsi_correct')) {
                    $table->index('is_correct', 'idx_opsi_correct');
                }
            });
        }

        // Ujians
        if (Schema::hasTable('ujians')) {
            Schema::table('ujians', function (Blueprint $table) {
                // mapel_id - check if index already exists
                if (Schema::hasColumn('ujians', 'mapel_id') && !$this->indexExists('ujians', 'idx_ujian_mapel')) {
                    $table->index('mapel_id', 'idx_ujian_mapel');
                }
                
                // Use 'status' instead of 'is_published'
                if (Schema::hasColumn('ujians', 'status') && !$this->indexExists('ujians', 'idx_ujian_status')) {
                    $table->index('status', 'idx_ujian_status');
                }
                
                // Use 'tanggal_mulai' instead of 'tanggal_ujian'
                if (Schema::hasColumn('ujians', 'tanggal_mulai') && !$this->indexExists('ujians', 'idx_ujian_tanggal_mulai')) {
                    $table->index('tanggal_mulai', 'idx_ujian_tanggal_mulai');
                }
                
                // Composite index for active exams
                if (Schema::hasColumn('ujians', 'tanggal_mulai') && 
                    Schema::hasColumn('ujians', 'status') && 
                    !$this->indexExists('ujians', 'idx_ujian_active')) {
                    $table->index(['tanggal_mulai', 'status'], 'idx_ujian_active');
                }
                
                // Index for token (used for exam access)
                if (Schema::hasColumn('ujians', 'token') && !$this->indexExists('ujians', 'idx_ujian_token')) {
                    $table->index('token', 'idx_ujian_token');
                }
            });
        }

        // Users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role') && !$this->indexExists('users', 'idx_user_role')) {
                    $table->index('role', 'idx_user_role');
                }
                if (Schema::hasColumn('users', 'is_active') && !$this->indexExists('users', 'idx_user_active')) {
                    $table->index('is_active', 'idx_user_active');
                }
                if (Schema::hasColumn('users', 'role') && 
                    Schema::hasColumn('users', 'is_active') && 
                    !$this->indexExists('users', 'idx_user_role_active')) {
                    $table->index(['role', 'is_active'], 'idx_user_role_active');
                }
            });
        }

        // Siswa - Skip if table doesn't exist
        // Note: This table might not exist in all installations
        if (Schema::hasTable('siswa')) {
            Schema::table('siswa', function (Blueprint $table) {
                if (Schema::hasColumn('siswa', 'user_id') && !$this->indexExists('siswa', 'idx_siswa_user')) {
                    $table->index('user_id', 'idx_siswa_user');
                }
                if (Schema::hasColumn('siswa', 'kelas_id') && !$this->indexExists('siswa', 'idx_siswa_kelas')) {
                    $table->index('kelas_id', 'idx_siswa_kelas');
                }
                if (Schema::hasColumn('siswa', 'nisn') && !$this->indexExists('siswa', 'idx_siswa_nisn')) {
                    $table->index('nisn', 'idx_siswa_nisn');
                }
                if (Schema::hasColumn('siswa', 'nis') && !$this->indexExists('siswa', 'idx_siswa_nis')) {
                    $table->index('nis', 'idx_siswa_nis');
                }
            });
        }

        // Activity Logs - for monitoring
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (Schema::hasColumn('activity_logs', 'user_id') && !$this->indexExists('activity_logs', 'idx_log_user')) {
                    $table->index('user_id', 'idx_log_user');
                }
                if (Schema::hasColumn('activity_logs', 'action') && !$this->indexExists('activity_logs', 'idx_log_action')) {
                    $table->index('action', 'idx_log_action');
                }
                if (Schema::hasColumn('activity_logs', 'created_at') && !$this->indexExists('activity_logs', 'idx_log_created')) {
                    $table->index('created_at', 'idx_log_created');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to safely drop index
        $dropIndexSafely = function($table, $indexName) {
            try {
                Schema::table($table, function (Blueprint $t) use ($indexName) {
                    $t->dropIndex($indexName);
                });
            } catch (\Exception $e) {
                // Index doesn't exist, ignore
            }
        };

        // Jawaban Siswas
        if (Schema::hasTable('jawaban_siswas')) {
            $dropIndexSafely('jawaban_siswas', 'idx_jawaban_peserta');
            $dropIndexSafely('jawaban_siswas', 'idx_jawaban_soal');
            $dropIndexSafely('jawaban_siswas', 'idx_jawaban_composite');
            $dropIndexSafely('jawaban_siswas', 'idx_jawaban_correct');
            $dropIndexSafely('jawaban_siswas', 'idx_jawaban_created');
        }

        // Peserta Ujians
        if (Schema::hasTable('peserta_ujians')) {
            $dropIndexSafely('peserta_ujians', 'idx_peserta_ujian');
            $dropIndexSafely('peserta_ujians', 'idx_peserta_siswa');
            $dropIndexSafely('peserta_ujians', 'idx_peserta_status');
            $dropIndexSafely('peserta_ujians', 'idx_peserta_ujian_status');
            $dropIndexSafely('peserta_ujians', 'idx_peserta_composite');
            $dropIndexSafely('peserta_ujians', 'idx_peserta_waktu_mulai');
        }

        // Bank Soals
        if (Schema::hasTable('bank_soals')) {
            $dropIndexSafely('bank_soals', 'idx_soal_mapel');
            $dropIndexSafely('bank_soals', 'idx_soal_tipe');
            $dropIndexSafely('bank_soals', 'idx_soal_digunakan');
        }

        // Opsi Jawabans
        if (Schema::hasTable('opsi_jawabans')) {
            $dropIndexSafely('opsi_jawabans', 'idx_opsi_soal');
            $dropIndexSafely('opsi_jawabans', 'idx_opsi_correct');
        }

        // Ujians
        if (Schema::hasTable('ujians')) {
            $dropIndexSafely('ujians', 'idx_ujian_mapel');
            $dropIndexSafely('ujians', 'idx_ujian_status');
            $dropIndexSafely('ujians', 'idx_ujian_tanggal_mulai');
            $dropIndexSafely('ujians', 'idx_ujian_active');
            $dropIndexSafely('ujians', 'idx_ujian_token');
        }

        // Users
        if (Schema::hasTable('users')) {
            $dropIndexSafely('users', 'idx_user_role');
            $dropIndexSafely('users', 'idx_user_active');
            $dropIndexSafely('users', 'idx_user_role_active');
        }

        // Siswa
        if (Schema::hasTable('siswa')) {
            $dropIndexSafely('siswa', 'idx_siswa_user');
            $dropIndexSafely('siswa', 'idx_siswa_kelas');
            $dropIndexSafely('siswa', 'idx_siswa_nisn');
            $dropIndexSafely('siswa', 'idx_siswa_nis');
        }

        // Activity Logs
        if (Schema::hasTable('activity_logs')) {
            $dropIndexSafely('activity_logs', 'idx_log_user');
            $dropIndexSafely('activity_logs', 'idx_log_action');
            $dropIndexSafely('activity_logs', 'idx_log_created');
        }
    }
};
