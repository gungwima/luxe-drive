-- ============================================================
--  DATABASE: luxe_drive
--  Website Rental Mobil "Luxe Drive"
--  PHP Native + MySQL (PDO)
--
--  CARA IMPORT:
--  1. Buka phpMyAdmin (http://localhost/phpmyadmin)
--  2. Klik menu "Import" di atas
--  3. Pilih file ini (luxe_drive.sql)
--  4. Klik "Go" / "Kirim"
--  Database + semua tabel + data dummy langsung terbuat.
--
--  AKUN DEFAULT:
--  - Admin  : email  admin@luxedrive.com   | password: admin123
--  - User   : email  budi@gmail.com        | password: user123
--            (siti@gmail.com, andi@gmail.com juga password: user123)
-- ============================================================

-- Buat database
CREATE DATABASE IF NOT EXISTS luxe_drive
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE luxe_drive;

-- Matikan foreign key check dulu saat membuat tabel
SET FOREIGN_KEY_CHECKS = 0;

-- Hapus tabel lama jika ada (agar bisa import ulang tanpa error)
DROP TABLE IF EXISTS notifikasi;
DROP TABLE IF EXISTS pesan_kontak;
DROP TABLE IF EXISTS ulasan_balasan;
DROP TABLE IF EXISTS ulasan;
DROP TABLE IF EXISTS promo;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS booking;
DROP TABLE IF EXISTS dokumen_mobil;
DROP TABLE IF EXISTS foto_mobil;
DROP TABLE IF EXISTS mobil;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS gambar;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 0. TABEL GAMBAR (penyimpanan gambar di database — anti masalah permission)
-- ============================================================
CREATE TABLE gambar (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nama_file  VARCHAR(255) NULL,
    tipe_mime  VARCHAR(100) NOT NULL DEFAULT 'image/jpeg',
    data_gambar LONGBLOB NOT NULL,
    ukuran     INT DEFAULT 0,
    kategori   VARCHAR(50) DEFAULT 'umum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 1. TABEL USERS (pelanggan)
-- ============================================================
CREATE TABLE users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    nama          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE,
    no_hp         VARCHAR(20),
    password      VARCHAR(255) NOT NULL,
    tgl_lahir     DATE NULL,
    jenis_kelamin ENUM('L','P') NULL,
    foto_profil   VARCHAR(255) NULL,
    no_ktp        VARCHAR(20) NULL,
    no_sim        VARCHAR(20) NULL,
    foto_ktp      VARCHAR(255) NULL,
    foto_sim      VARCHAR(255) NULL,
    status        ENUM('aktif','blokir') DEFAULT 'aktif',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. TABEL OTP (lupa password & verifikasi)
-- ============================================================
CREATE TABLE otp_codes (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    kode       VARCHAR(6) NOT NULL,
    tipe       ENUM('lupa_password','verifikasi_email') DEFAULT 'lupa_password',
    expired_at DATETIME NOT NULL,
    is_used    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. TABEL ADMIN / STAFF
-- ============================================================
CREATE TABLE admin (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) UNIQUE,
    username   VARCHAR(50)  UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('superadmin','cs','operasional') DEFAULT 'cs',
    foto       VARCHAR(255) NULL,
    status     ENUM('aktif','nonaktif') DEFAULT 'aktif',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. TABEL MOBIL
-- ============================================================
CREATE TABLE mobil (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    nama          VARCHAR(100) NOT NULL,
    merek         VARCHAR(50)  NULL,
    model         VARCHAR(50)  NULL,
    tahun         YEAR NULL,
    no_plat       VARCHAR(20)  UNIQUE NOT NULL,
    warna         VARCHAR(30)  NULL,
    no_rangka     VARCHAR(50)  NULL,
    jenis         ENUM('MPV','SUV','City Car','Minibus','Pickup','Sedan') DEFAULT 'MPV',
    transmisi     ENUM('Manual','Automatic') DEFAULT 'Manual',
    bahan_bakar   ENUM('Bensin','Solar','Hybrid','Listrik') DEFAULT 'Bensin',
    kapasitas     INT DEFAULT 5,
    bagasi        VARCHAR(50) NULL,
    harga_hari    DECIMAL(10,0) NOT NULL DEFAULT 0,
    harga_sopir   DECIMAL(10,0) DEFAULT 0,
    deskripsi     TEXT NULL,
    fasilitas     JSON NULL,
    foto_utama    VARCHAR(255) NULL,
    status        ENUM('tersedia','disewa','maintenance','nonaktif') DEFAULT 'tersedia',
    lokasi        VARCHAR(100) NULL,
    rating_avg    DECIMAL(3,1) DEFAULT 0,
    total_ulasan  INT DEFAULT 0,
    total_sewa    INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. TABEL FOTO MOBIL
-- ============================================================
CREATE TABLE foto_mobil (
    id        INT PRIMARY KEY AUTO_INCREMENT,
    mobil_id  INT NOT NULL,
    foto      VARCHAR(255) NOT NULL,
    is_utama  TINYINT(1) DEFAULT 0,
    urutan    INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. TABEL DOKUMEN MOBIL (STNK, KIR, Asuransi)
-- ============================================================
CREATE TABLE dokumen_mobil (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    mobil_id       INT NOT NULL,
    jenis          ENUM('stnk','kir','asuransi') DEFAULT 'stnk',
    file           VARCHAR(255) NULL,
    berlaku_sampai DATE NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. TABEL BOOKING
-- ============================================================
CREATE TABLE booking (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    kode_booking    VARCHAR(30) UNIQUE NOT NULL,
    user_id         INT NULL,
    mobil_id        INT NOT NULL,
    admin_id        INT NULL,
    tgl_ambil       DATETIME NOT NULL,
    tgl_kembali     DATETIME NOT NULL,
    durasi_hari     INT NOT NULL DEFAULT 1,
    lokasi_jemput   VARCHAR(255) NULL,
    lokasi_kembali  VARCHAR(255) NULL,
    dengan_sopir    TINYINT(1) DEFAULT 0,
    catatan_user    TEXT NULL,
    catatan_admin   TEXT NULL,
    harga_per_hari  DECIMAL(10,0) DEFAULT 0,
    subtotal        DECIMAL(12,0) DEFAULT 0,
    biaya_sopir     DECIMAL(10,0) DEFAULT 0,
    biaya_asuransi  DECIMAL(10,0) DEFAULT 0,
    biaya_admin     DECIMAL(10,0) DEFAULT 0,
    diskon          DECIMAL(10,0) DEFAULT 0,
    total           DECIMAL(12,0) NOT NULL DEFAULT 0,
    promo_id        INT NULL,
    is_manual       TINYINT(1) DEFAULT 0,
    status          ENUM('pending','confirmed','berlangsung','selesai','dibatalkan') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (mobil_id) REFERENCES mobil(id),
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. TABEL TRANSAKSI / PEMBAYARAN
-- ============================================================
CREATE TABLE transaksi (
    id             INT PRIMARY KEY AUTO_INCREMENT,
    booking_id     INT NOT NULL,
    kode_transaksi VARCHAR(30) UNIQUE NULL,
    metode         ENUM('transfer_bank','virtual_account','ewallet','tunai','cod') DEFAULT 'transfer_bank',
    bank           VARCHAR(50) NULL,
    no_rekening    VARCHAR(50) NULL,
    jumlah         DECIMAL(12,0) NOT NULL DEFAULT 0,
    bukti_bayar    VARCHAR(255) NULL,
    status         ENUM('pending','verified','rejected') DEFAULT 'pending',
    verified_by    INT NULL,
    verified_at    DATETIME NULL,
    keterangan     TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id)  REFERENCES booking(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. TABEL PROMO
-- ============================================================
CREATE TABLE promo (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    kode          VARCHAR(30) UNIQUE NOT NULL,
    nama          VARCHAR(100) NULL,
    deskripsi     TEXT NULL,
    tipe          ENUM('persen','nominal') DEFAULT 'persen',
    nilai         DECIMAL(10,0) NOT NULL DEFAULT 0,
    min_transaksi DECIMAL(12,0) DEFAULT 0,
    maks_diskon   DECIMAL(10,0) NULL,
    kuota         INT DEFAULT 100,
    terpakai      INT DEFAULT 0,
    tgl_mulai     DATE NULL,
    tgl_berakhir  DATE NULL,
    status        ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. TABEL ULASAN
-- ============================================================
CREATE TABLE ulasan (
    id           INT PRIMARY KEY AUTO_INCREMENT,
    booking_id   INT NOT NULL,
    user_id      INT NOT NULL,
    mobil_id     INT NOT NULL,
    rating       TINYINT(1) NOT NULL,
    judul        VARCHAR(150) NULL,
    komentar     TEXT NOT NULL,
    foto_1       VARCHAR(255) NULL,
    foto_2       VARCHAR(255) NULL,
    foto_3       VARCHAR(255) NULL,
    tampil_nama  TINYINT(1) DEFAULT 1,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    alasan_tolak TEXT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES booking(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mobil_id)   REFERENCES mobil(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. TABEL BALASAN ULASAN
-- ============================================================
CREATE TABLE ulasan_balasan (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    ulasan_id  INT NOT NULL,
    admin_id   INT NOT NULL,
    balasan    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ulasan_id) REFERENCES ulasan(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)  REFERENCES admin(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. TABEL PESAN KONTAK
-- ============================================================
CREATE TABLE pesan_kontak (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    no_hp      VARCHAR(20) NULL,
    subjek     VARCHAR(150) NULL,
    pesan      TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. TABEL NOTIFIKASI
-- ============================================================
CREATE TABLE notifikasi (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NULL,
    admin_id   INT NULL,
    judul      VARCHAR(150) NOT NULL,
    pesan      TEXT NOT NULL,
    link       VARCHAR(255) NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--                     DATA DUMMY
-- ============================================================

-- ── ADMIN (password: admin123) ──
INSERT INTO admin (nama, email, username, password, role, status) VALUES
('Super Admin',    'admin@luxedrive.com', 'admin', '$2y$10$hsHmLH.kPud.9rIHM5Tgnuir7Dd3mmOdGNGhqCJoS9.vP19cERU7K', 'superadmin', 'aktif'),
('Customer Service','cs@luxedrive.com',    'cs01',  '$2y$10$hsHmLH.kPud.9rIHM5Tgnuir7Dd3mmOdGNGhqCJoS9.vP19cERU7K', 'cs', 'aktif'),
('Operasional',    'ops@luxedrive.com',   'ops01', '$2y$10$hsHmLH.kPud.9rIHM5Tgnuir7Dd3mmOdGNGhqCJoS9.vP19cERU7K', 'operasional', 'aktif');

-- ── USERS (password: user123) ──
INSERT INTO users (nama, email, no_hp, password, no_ktp, no_sim, status) VALUES
('Budi Santoso', 'budi@gmail.com', '081234567890', '$2y$10$ksyMZ96bHGt3UdWYOyT8ceOyIlCCSeKs7v6ybuSeh3EmxVr0TYIfG', '5171010101010001', '1234567890', 'aktif'),
('Siti Aminah',  'siti@gmail.com', '081298765432', '$2y$10$ksyMZ96bHGt3UdWYOyT8ceOyIlCCSeKs7v6ybuSeh3EmxVr0TYIfG', '5171020202020002', '2345678901', 'aktif'),
('Andi Pratama', 'andi@gmail.com', '081355556666', '$2y$10$ksyMZ96bHGt3UdWYOyT8ceOyIlCCSeKs7v6ybuSeh3EmxVr0TYIfG', '5171030303030003', '3456789012', 'aktif');

-- ── MOBIL ──
INSERT INTO mobil (nama, merek, model, tahun, no_plat, warna, jenis, transmisi, bahan_bakar, kapasitas, bagasi, harga_hari, harga_sopir, deskripsi, fasilitas, status, lokasi) VALUES
('Toyota Avanza 2023', 'Toyota', 'Avanza 1.3 G', 2023, 'DK 1234 AB', 'Silver', 'MPV', 'Manual', 'Bensin', 7, '2 Koper Besar', 350000, 150000, 'MPV keluarga paling populer, irit dan nyaman untuk perjalanan dalam dan luar kota.', '["AC","Audio/Musik","Sabuk Pengaman","Charger USB"]', 'tersedia', 'Kantor Pusat'),
('Toyota Innova 2022', 'Toyota', 'Innova Reborn', 2022, 'DK 5678 CD', 'Hitam', 'MPV', 'Automatic', 'Solar', 7, '3 Koper Besar', 600000, 200000, 'MPV premium dengan kabin luas dan mesin diesel bertenaga. Cocok untuk perjalanan jauh.', '["AC","Audio/Musik","GPS","Sabuk Pengaman","Kamera Mundur","Charger USB"]', 'tersedia', 'Kantor Pusat'),
('Daihatsu Brio 2023', 'Daihatsu', 'Brio RS', 2023, 'DK 9012 EF', 'Merah', 'City Car', 'Manual', 'Bensin', 4, '1 Koper', 300000, 100000, 'City car lincah dan irit, sempurna untuk mobilitas dalam kota.', '["AC","Audio/Musik","Sabuk Pengaman","Charger USB"]', 'tersedia', 'Cabang Kuta'),
('Toyota HiAce 2021', 'Toyota', 'HiAce Premio', 2021, 'DK 3456 GH', 'Putih', 'Minibus', 'Manual', 'Solar', 15, '5 Koper Besar', 900000, 300000, 'Minibus kapasitas besar untuk rombongan wisata dan acara keluarga besar.', '["AC","Audio/Musik","Sabuk Pengaman","Kotak P3K"]', 'tersedia', 'Kantor Pusat'),
('Honda HR-V 2023', 'Honda', 'HR-V Turbo', 2023, 'DK 7788 IJ', 'Abu-abu', 'SUV', 'Automatic', 'Bensin', 5, '2 Koper Besar', 550000, 200000, 'SUV stylish dengan fitur lengkap dan kenyamanan berkendara premium.', '["AC","Audio/Musik","GPS","Sabuk Pengaman","Kamera Mundur","Charger USB","Kursi Bayi"]', 'tersedia', 'Cabang Kuta'),
('Mitsubishi Xpander 2022', 'Mitsubishi', 'Xpander Ultimate', 2022, 'DK 4455 KL', 'Putih', 'MPV', 'Automatic', 'Bensin', 7, '2 Koper Besar', 450000, 180000, 'MPV modern dengan desain gagah dan kabin lega untuk keluarga.', '["AC","Audio/Musik","GPS","Sabuk Pengaman","Charger USB"]', 'tersedia', 'Kantor Pusat');

-- ── FOTO MOBIL ──
-- (Kosong. Foto akan otomatis memakai placeholder sampai admin upload foto asli
--  melalui panel admin > Armada > Edit. Kolom mobil.foto_utama = NULL = placeholder.)

-- ── PROMO ──
INSERT INTO promo (kode, nama, deskripsi, tipe, nilai, min_transaksi, maks_diskon, kuota, tgl_mulai, tgl_berakhir, status) VALUES
('WELCOME10', 'Diskon Pelanggan Baru', 'Potongan 10% untuk pemesanan pertama.', 'persen', 10, 300000, 100000, 100, '2026-01-01', '2026-12-31', 'aktif'),
('HEMAT50K',  'Potongan 50 Ribu',      'Diskon langsung Rp 50.000 tanpa minimum.', 'nominal', 50000, 0, NULL, 200, '2026-01-01', '2026-12-31', 'aktif'),
('LIBURAN25', 'Promo Liburan',         'Diskon 25% khusus musim liburan.', 'persen', 25, 500000, 250000, 50, '2026-06-01', '2026-08-31', 'aktif');

-- ── BOOKING (contoh 1 selesai + 1 berlangsung + 1 pending) ──
INSERT INTO booking (kode_booking, user_id, mobil_id, tgl_ambil, tgl_kembali, durasi_hari, lokasi_jemput, dengan_sopir, harga_per_hari, subtotal, biaya_sopir, biaya_asuransi, biaya_admin, diskon, total, status) VALUES
('RNT-20260601-0001', 1, 1, '2026-06-01 09:00:00', '2026-06-03 09:00:00', 2, 'Bandara Ngurah Rai, Terminal Kedatangan', 0, 350000, 700000, 0, 50000, 20000, 0, 770000, 'selesai'),
('RNT-20260610-0002', 2, 2, '2026-06-10 09:00:00', '2026-06-13 09:00:00', 3, 'Hotel Grand Inna Kuta', 1, 600000, 1800000, 600000, 50000, 20000, 0, 2470000, 'berlangsung'),
('RNT-20260705-0003', 3, 3, '2026-07-15 09:00:00', '2026-07-17 09:00:00', 2, 'Jl. Sunset Road No. 88, Kuta', 0, 300000, 600000, 0, 50000, 20000, 0, 670000, 'pending');

-- ── TRANSAKSI ──
-- created_at diisi eksplisit & tersebar agar grafik pendapatan di laporan terlihat berisi
INSERT INTO transaksi (booking_id, kode_transaksi, metode, jumlah, status, verified_by, verified_at, created_at) VALUES
(1, 'TRX-20260601-0001', 'transfer_bank', 770000, 'verified', 1, '2026-06-01 08:30:00', '2026-06-01 08:30:00'),
(2, 'TRX-20260610-0002', 'ewallet', 2470000, 'verified', 1, '2026-06-10 08:15:00', '2026-06-10 08:15:00'),
(3, 'TRX-20260705-0003', 'transfer_bank', 670000, 'pending', NULL, NULL, NOW());

-- ── DATA TAMBAHAN: transaksi tersebar di bulan berjalan (untuk grafik laporan) ──
-- Booking tambahan yang sudah selesai + transaksi verified di beberapa tanggal bulan ini
INSERT INTO booking (kode_booking, user_id, mobil_id, tgl_ambil, tgl_kembali, durasi_hari, lokasi_jemput, dengan_sopir, harga_per_hari, subtotal, biaya_sopir, biaya_asuransi, biaya_admin, diskon, total, status, created_at) VALUES
('RNT-CHART-0004', 1, 4, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 2, 'Bandara Ngurah Rai', 1, 900000, 1800000, 600000, 50000, 20000, 0, 2470000, 'selesai', DATE_SUB(NOW(), INTERVAL 8 DAY)),
('RNT-CHART-0005', 2, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 2, 'Hotel Kuta', 0, 550000, 1100000, 0, 50000, 20000, 0, 1170000, 'selesai', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('RNT-CHART-0006', 3, 6, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 'Sunset Road', 0, 450000, 900000, 0, 50000, 20000, 0, 970000, 'selesai', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('RNT-CHART-0007', 1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), CURDATE(), 1, 'Denpasar', 0, 350000, 350000, 0, 50000, 20000, 0, 420000, 'selesai', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO transaksi (booking_id, kode_transaksi, metode, jumlah, status, verified_by, verified_at, created_at) VALUES
((SELECT id FROM booking WHERE kode_booking='RNT-CHART-0004'), 'TRX-CHART-0004', 'transfer_bank', 2470000, 'verified', 1, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
((SELECT id FROM booking WHERE kode_booking='RNT-CHART-0005'), 'TRX-CHART-0005', 'ewallet', 1170000, 'verified', 1, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
((SELECT id FROM booking WHERE kode_booking='RNT-CHART-0006'), 'TRX-CHART-0006', 'virtual_account', 970000, 'verified', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM booking WHERE kode_booking='RNT-CHART-0007'), 'TRX-CHART-0007', 'transfer_bank', 420000, 'verified', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ── ULASAN (untuk booking yang sudah selesai) ──
INSERT INTO ulasan (booking_id, user_id, mobil_id, rating, judul, komentar, tampil_nama, status) VALUES
(1, 1, 1, 5, 'Pelayanan Memuaskan!', 'Mobil bersih dan wangi, proses cepat dan mudah. Sopir ramah dan tepat waktu. Sangat direkomendasikan untuk liburan keluarga.', 1, 'approved');

-- ── BALASAN ULASAN ──
INSERT INTO ulasan_balasan (ulasan_id, admin_id, balasan) VALUES
(1, 1, 'Terima kasih banyak Bapak Budi atas ulasan positifnya! Kami senang bisa melayani perjalanan Anda. Sampai jumpa di pemesanan berikutnya.');

-- ── PESAN KONTAK (contoh) ──
INSERT INTO pesan_kontak (nama, email, no_hp, subjek, pesan) VALUES
('Rina Wati', 'rina@gmail.com', '081377778888', 'Tanya Sewa Bulanan', 'Halo, apakah tersedia paket sewa mobil bulanan untuk keperluan kantor? Terima kasih.');

-- Update statistik mobil (rating & total sewa) berdasarkan data di atas
UPDATE mobil SET rating_avg = 5.0, total_ulasan = 1, total_sewa = 1 WHERE id = 1;
UPDATE mobil SET total_sewa = 1 WHERE id = 2;

-- ============================================================
--  SELESAI! Database luxe_drive siap digunakan.
-- ============================================================
