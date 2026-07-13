-- ============================================================
-- upgrade_struktur.sql — Perbarui struktur tabel TANPA hapus data
-- ============================================================
-- Gunakan file ini jika Anda TIDAK ingin kehilangan data
-- (booking, user, dll) yang sudah ada, tapi perlu menambahkan
-- kolom/tabel baru yang dibutuhkan fitur terbaru.
--
-- Cara pakai: phpMyAdmin > pilih database luxe_drive > Import > pilih file ini
-- AMAN dijalankan berkali-kali (pakai IF NOT EXISTS).
-- ============================================================

-- 1. Tabel gambar (penyimpanan gambar di database)
CREATE TABLE IF NOT EXISTS gambar (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nama_file  VARCHAR(255) NULL,
    tipe_mime  VARCHAR(100) NOT NULL DEFAULT 'image/jpeg',
    data_gambar LONGBLOB NOT NULL,
    ukuran     INT DEFAULT 0,
    kategori   VARCHAR(50) DEFAULT 'umum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel ulasan — tambah kolom yang mungkin belum ada
-- (MariaDB/MySQL 8+ mendukung IF NOT EXISTS pada ADD COLUMN)
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS judul VARCHAR(150) NULL AFTER rating;
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS foto_1 VARCHAR(255) NULL AFTER komentar;
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS foto_2 VARCHAR(255) NULL AFTER foto_1;
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS foto_3 VARCHAR(255) NULL AFTER foto_2;
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS tampil_nama TINYINT(1) DEFAULT 1 AFTER foto_3;
ALTER TABLE ulasan ADD COLUMN IF NOT EXISTS alasan_tolak TEXT NULL AFTER status;

-- 3. Tabel users — pastikan kolom foto & dokumen ada
ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_ktp VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_sim VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS no_ktp VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS no_sim VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS tgl_lahir DATE NULL;

-- Selesai! Struktur tabel sudah diperbarui tanpa menghapus data.
