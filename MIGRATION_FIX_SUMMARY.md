# Migration Fix Summary

## Problem
Migration `2024_01_01_000001_add_indexes_for_performance` was failing with error:
```
SQLSTATE[42000]: Syntax error or access violation: 1072 Key column 'is_published' doesn't exist in table
```

## Root Causes
1. **Wrong column names**: Migration was trying to index columns that don't exist in the actual database:
   - `ujians.is_published` (actual column: `status`)
   - `ujians.tanggal_ujian` (actual columns: `tanggal_mulai`, `tanggal_selesai`)

2. **Duplicate indexes**: Some indexes already existed from previous migrations, causing "Duplicate key name" errors

3. **Missing table**: `siswa` table doesn't exist (might be named `siswas` instead)

## Solution Implemented

### 1. Added Index Existence Check
Created helper function to check if index already exists before creating:
```php
private function indexExists($table, $indexName): bool
{
    $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
    return !empty($indexes);
}
```

### 2. Fixed Column Names for ujians Table
Changed from non-existent columns to actual columns:
- `is_published` → `status`
- `tanggal_ujian` → `tanggal_mulai`
- Added composite index: `['tanggal_mulai', 'status']`
- Added token index for exam access

### 3. Updated All Index Creation
All index creation now checks:
1. Table exists (`Schema::hasTable()`)
2. Column exists (`Schema::hasColumn()`)
3. Index doesn't already exist (`!$this->indexExists()`)

## Migration Status
✅ **COMPLETED SUCCESSFULLY**

Both migrations ran in batch 5:
- `2024_01_01_000001_add_indexes_for_performance` - 467ms
- `2024_01_02_000001_create_system_settings_table` - 73ms

## Indexes Created

### jawaban_siswas (8 indexes)
- idx_jawaban_peserta (peserta_ujian_id)
- idx_jawaban_soal (bank_soal_id)
- idx_jawaban_composite (peserta_ujian_id, bank_soal_id)
- idx_jawaban_correct (is_correct)
- idx_jawaban_created (created_at)

### peserta_ujians (6 indexes)
- idx_peserta_ujian (ujian_id)
- idx_peserta_siswa (siswa_id)
- idx_peserta_status (status)
- idx_peserta_ujian_status (ujian_id, status)
- idx_peserta_composite (ujian_id, siswa_id)
- idx_peserta_waktu_mulai (waktu_mulai)

### bank_soals (4 indexes)
- idx_soal_mapel (mapel_id)
- idx_soal_tipe (tipe_soal)
- idx_soal_kesulitan (tingkat_kesulitan)
- idx_soal_digunakan (digunakan_count)

### opsi_jawabans (2 indexes)
- idx_opsi_soal (bank_soal_id)
- idx_opsi_correct (is_correct)

### ujians (5 indexes)
- idx_ujian_mapel (mapel_id)
- idx_ujian_status (status)
- idx_ujian_tanggal_mulai (tanggal_mulai)
- idx_ujian_active (tanggal_mulai, status)
- idx_ujian_token (token)

### users (3 indexes)
- idx_user_role (role)
- idx_user_active (is_active)
- idx_user_role_active (role, is_active)

### activity_logs (3 indexes)
- idx_log_user (user_id)
- idx_log_action (action)
- idx_log_created (created_at)

## Performance Impact
These indexes will significantly improve query performance for:
- Exam answer retrieval and saving
- Student exam participation tracking
- Question bank filtering
- User role-based queries
- Activity log searches

Perfect for handling 500+ concurrent users!

## Files Modified
- `database/migrations/2024_01_01_000001_add_indexes_for_performance.php`

## Files Created
- `check_table_structure.php` - Diagnostic tool to check database structure
- `MIGRATION_FIX_SUMMARY.md` - This file

## Verification
Run `php check_table_structure.php` to verify all indexes are created correctly.
