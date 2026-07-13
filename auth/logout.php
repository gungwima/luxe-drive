<?php
// ============================================================
// auth/logout.php — Logout User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions/auth.php';

logout_user();
// logout_user() sudah handle redirect ke masuk.php
