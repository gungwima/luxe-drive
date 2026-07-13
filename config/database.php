<?php
// ============================================================
// config/database.php
// Koneksi database menggunakan PDO
// ============================================================

$db_host    = 'localhost';
$db_name    = 'luxe_drive';
$db_user    = 'root';
$db_pass    = '';
$db_charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Tampilkan error hanya saat development
    // Saat production, ganti dengan halaman error
    die(json_encode([
        'error' => true,
        'pesan' => 'Koneksi database gagal: ' . $e->getMessage()
    ]));
}
