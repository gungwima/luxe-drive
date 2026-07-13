<?php
// ============================================================
// functions/auth.php
// Semua fungsi login, logout, cek session
// ============================================================

require_once __DIR__ . '/../config/config.php';

// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// CEK USER (PELANGGAN)
// ------------------------------------------------------------

/**
 * Cek apakah user sudah login
 * Pakai di: semua halaman yang butuh login user
 */
function is_user_login(): bool {
    return isset($_SESSION[SESSION_USER]) && !empty($_SESSION[SESSION_USER]['id']);
}

/**
 * Ambil data user yang sedang login
 * Return: array data user atau null
 */
function get_user_login(): ?array {
    return $_SESSION[SESSION_USER] ?? null;
}

/**
 * Wajib login user - redirect ke halaman masuk jika belum login
 * Pakai di: checkout, pesanan, profil, tulis ulasan
 */
function require_user_login(string $redirect_back = ''): void {
    if (!is_user_login()) {
        $url = BASE_URL . '/auth/masuk.php';
        if ($redirect_back) {
            $url .= '?redirect=' . urlencode($redirect_back);
        }
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Set session user setelah login berhasil
 */
function set_user_session(array $user): void {
    $_SESSION[SESSION_USER] = [
        'id'     => $user['id'],
        'nama'   => $user['nama'],
        'email'  => $user['email'],
        'no_hp'  => $user['no_hp'],
        'foto'   => $user['foto_profil'] ?? null,
        'status' => $user['status'],
    ];
}

/**
 * Logout user
 */
function logout_user(): void {
    unset($_SESSION[SESSION_USER]);
    session_regenerate_id(true);
    header('Location: ' . BASE_URL . '/auth/masuk.php');
    exit;
}

// ------------------------------------------------------------
// CEK ADMIN / STAFF
// ------------------------------------------------------------

/**
 * Cek apakah admin sudah login
 */
function is_admin_login(): bool {
    return isset($_SESSION[SESSION_ADMIN]) && !empty($_SESSION[SESSION_ADMIN]['id']);
}

/**
 * Ambil data admin yang sedang login
 */
function get_admin_login(): ?array {
    return $_SESSION[SESSION_ADMIN] ?? null;
}

/**
 * Wajib login admin - redirect ke login admin jika belum
 * Pakai di: semua halaman admin/
 */
function require_admin_login(): void {
    if (!is_admin_login()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

/**
 * Cek role admin (superadmin / cs / operasional)
 */
function is_superadmin(): bool {
    $admin = get_admin_login();
    return $admin && $admin['role'] === 'superadmin';
}

function is_role(string $role): bool {
    $admin = get_admin_login();
    return $admin && $admin['role'] === $role;
}

/**
 * Set session admin setelah login berhasil
 */
function set_admin_session(array $admin): void {
    $_SESSION[SESSION_ADMIN] = [
        'id'    => $admin['id'],
        'nama'  => $admin['nama'],
        'email' => $admin['email'],
        'role'  => $admin['role'],
        'foto'  => $admin['foto'] ?? null,
    ];
}

/**
 * Logout admin
 */
function logout_admin(): void {
    unset($_SESSION[SESSION_ADMIN]);
    session_regenerate_id(true);
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// ------------------------------------------------------------
// KEAMANAN
// ------------------------------------------------------------

/**
 * Generate CSRF token
 * Pakai di semua form untuk keamanan
 */
function generate_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token dari form
 */
function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Input CSRF hidden untuk ditaruh di dalam form
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf() . '">';
}

/**
 * Hash password
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifikasi password
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}
