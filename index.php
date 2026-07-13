<?php
// Redirect ke beranda jika mengakses root folder
require_once __DIR__ . '/config/config.php';
header('Location: ' . BASE_URL . '/beranda.php');
exit;
