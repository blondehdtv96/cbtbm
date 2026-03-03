-- Script SQL untuk mengecek data jawaban siswa
-- Ganti 11 dengan peserta_ujian_id yang sesuai

-- 1. Cek data peserta ujian
SELECT 
    pu.id as peserta_id,
    pu.ujian_id,
    u.nama_ujian,
    s.nama as nama_siswa,
    s.nis,
    pu.status,
    pu.nilai,
    pu.waktu_mulai,
    pu.waktu_selesai
FROM peserta_ujians pu
LEFT JOIN ujians u ON pu.ujian_id = u.id
LEFT JOIN siswas s ON pu.siswa_id = s.id
WHERE pu.id = 11;

-- 2. Cek semua jawaban siswa
SELECT 
    js.id,
    js.bank_soal_id,
    js.jawaban_dipilih,
    CASE 
        WHEN js.jawaban_dipilih IS NULL THEN 'NULL'
        WHEN js.jawaban_dipilih = '' THEN 'EMPTY STRING'
        WHEN TRIM(js.jawaban_dipilih) = '' THEN 'WHITESPACE ONLY'
        ELSE 'HAS VALUE'
    END as status_jawaban,
    LENGTH(js.jawaban_dipilih) as panjang_jawaban,
    js.is_correct,
    js.nilai,
    js.is_ragu,
    bs.pertanyaan,
    bs.tipe_soal
FROM jawaban_siswas js
LEFT JOIN bank_soals bs ON js.bank_soal_id = bs.id
WHERE js.peserta_ujian_id = 11
ORDER BY js.id;

-- 3. Summary jawaban
SELECT 
    COUNT(*) as total_soal,
    SUM(CASE WHEN js.jawaban_dipilih IS NOT NULL AND TRIM(js.jawaban_dipilih) != '' THEN 1 ELSE 0 END) as terisi,
    SUM(CASE WHEN js.jawaban_dipilih IS NULL OR TRIM(js.jawaban_dipilih) = '' THEN 1 ELSE 0 END) as kosong,
    SUM(CASE WHEN js.is_correct = 1 THEN 1 ELSE 0 END) as benar,
    SUM(CASE WHEN js.is_correct = 0 THEN 1 ELSE 0 END) as salah,
    SUM(js.nilai) as total_nilai
FROM jawaban_siswas js
WHERE js.peserta_ujian_id = 11;

-- 4. Cek opsi jawaban untuk soal tertentu (ganti 123 dengan bank_soal_id)
SELECT 
    oj.id,
    oj.bank_soal_id,
    oj.opsi_label,
    oj.isi_opsi,
    oj.is_correct
FROM opsi_jawabans oj
WHERE oj.bank_soal_id IN (
    SELECT DISTINCT bank_soal_id 
    FROM jawaban_siswas 
    WHERE peserta_ujian_id = 11
)
ORDER BY oj.bank_soal_id, oj.opsi_label;

-- 5. Cek jawaban yang tidak cocok dengan opsi
SELECT 
    js.id,
    js.bank_soal_id,
    js.jawaban_dipilih,
    GROUP_CONCAT(oj.opsi_label) as opsi_tersedia
FROM jawaban_siswas js
LEFT JOIN opsi_jawabans oj ON js.bank_soal_id = oj.bank_soal_id
WHERE js.peserta_ujian_id = 11
    AND js.jawaban_dipilih IS NOT NULL
    AND js.jawaban_dipilih != ''
GROUP BY js.id, js.bank_soal_id, js.jawaban_dipilih
HAVING FIND_IN_SET(js.jawaban_dipilih, GROUP_CONCAT(oj.opsi_label)) = 0;

-- 6. Detail lengkap untuk debugging
SELECT 
    js.id as jawaban_id,
    js.bank_soal_id,
    SUBSTRING(bs.pertanyaan, 1, 50) as pertanyaan,
    bs.tipe_soal,
    js.jawaban_dipilih,
    CONCAT('[', 
        CASE WHEN js.jawaban_dipilih IS NULL THEN 'NULL' ELSE '' END,
        CASE WHEN js.jawaban_dipilih = '' THEN 'EMPTY' ELSE '' END,
        CASE WHEN js.jawaban_dipilih IS NOT NULL AND js.jawaban_dipilih != '' THEN 'HAS_VALUE' ELSE '' END,
    ']') as debug_status,
    (SELECT GROUP_CONCAT(opsi_label) FROM opsi_jawabans WHERE bank_soal_id = js.bank_soal_id) as opsi_tersedia,
    (SELECT opsi_label FROM opsi_jawabans WHERE bank_soal_id = js.bank_soal_id AND is_correct = 1) as jawaban_benar,
    js.is_correct,
    js.nilai,
    bs.bobot_nilai
FROM jawaban_siswas js
LEFT JOIN bank_soals bs ON js.bank_soal_id = bs.id
WHERE js.peserta_ujian_id = 11
ORDER BY js.id;
