<?php
// ============================================================
// tampil_gambar.php — Menampilkan gambar dari database
// Dipanggil via: tampil_gambar.php?id=123
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Gambar tidak ditemukan.');
}

try {
    $stmt = $pdo->prepare("SELECT tipe_mime, data_gambar FROM gambar WHERE id = ?");
    $stmt->execute([$id]);
    $gambar = $stmt->fetch();

    if (!$gambar) {
        header('HTTP/1.1 404 Not Found');
        exit('Gambar tidak ditemukan.');
    }

    // Cache 1 hari agar tidak query berulang
    header('Content-Type: ' . $gambar['tipe_mime']);
    header('Cache-Control: public, max-age=86400');
    header('Content-Length: ' . strlen($gambar['data_gambar']));

    echo $gambar['data_gambar'];
    exit;

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Gagal memuat gambar.');
}
