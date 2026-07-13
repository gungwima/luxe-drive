<?php
// Cegah akses langsung ke folder — redirect ke beranda
require_once __DIR__ . '/../config/config.php';
header('Location: ' . BASE_URL . '/beranda.php');
exit;
