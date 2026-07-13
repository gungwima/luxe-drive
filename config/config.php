<?php
// ============================================================
// config/config.php
// Konstanta global project Luxe Drive
// ============================================================

// --- INFO APLIKASI ---
define('APP_NAME',        'Luxe Drive');
define('APP_TAGLINE',     'Premium Car Rental Experience');
define('APP_VERSION',     '1.0.0');

// --- URL (sesuaikan dengan localhost Anda) ---
define('BASE_URL',        'http://localhost/luxe_drive');
define('ADMIN_URL',       BASE_URL . '/admin');
define('ASSETS_URL',      BASE_URL . '/assets');
define('UPLOADS_URL',     BASE_URL . '/uploads');

// --- PATH FOLDER (absolut di server) ---
define('ROOT_PATH',       realpath(__DIR__ . '/..') ?: dirname(__DIR__));
define('UPLOADS_PATH',    ROOT_PATH . '/uploads');

// --- KONTAK BISNIS ---
define('APP_EMAIL',       'info@luxedrive.com');
define('APP_PHONE',       '+62 21 1234 5678');
define('APP_WHATSAPP',    '6281234567890');
define('APP_ADDRESS',     'Jl. Kemang Raya No. 123, Jakarta Selatan, 12730');

// --- EMAIL SMTP (PHPMailer) ---
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'gungwima321@gmail.com');
define('MAIL_PASSWORD',   'iyfi awof abxu gqik');
define('MAIL_FROM_NAME',  APP_NAME);

// --- UPLOAD SETTINGS ---
define('MAX_FILE_SIZE',   2 * 1024 * 1024); // 2MB dalam bytes
define('ALLOWED_IMAGES',  ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_DOCS',    ['pdf', 'jpg', 'jpeg', 'png']);

// --- BISNIS RULES ---
define('DP_PERSEN',       30);    // DP minimal 30%
define('BIAYA_ASURANSI',  50000); // Rp 50.000 per booking
define('BIAYA_ADMIN',     20000); // Rp 20.000 per booking
define('DENDA_PER_JAM',   50000); // Rp 50.000 per jam terlambat
define('OTP_EXPIRED_MENIT', 10);  // OTP expired 10 menit

// --- SESSION NAMES ---
define('SESSION_USER',    'luxe_user');
define('SESSION_ADMIN',   'luxe_admin');
