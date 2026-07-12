<?php
// ============================================================
// admin/logout.php — Logout Admin
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions/auth.php';

logout_admin();
// logout_admin() sudah handle redirect ke admin/login.php
