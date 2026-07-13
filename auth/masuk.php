<?php
// ============================================================
// auth/masuk.php — Login User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

// Jika sudah login, redirect ke beranda
if (is_user_login()) {
    header('Location: ' . BASE_URL . '/beranda.php');
    exit;
}

$error   = '';
$success = '';

// Ambil redirect url jika ada
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : BASE_URL . '/beranda.php';

// ============================================================
// PROSES LOGIN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifikasi CSRF
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $identifier = bersihkan($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $ingat      = isset($_POST['ingat']);

        if (empty($identifier) || empty($password)) {
            $error = 'Email/No. HP dan password wajib diisi.';
        } else {
            try {
                // Cari user berdasarkan email ATAU no_hp
                $stmt = $pdo->prepare("
                    SELECT * FROM users
                    WHERE (email = ? OR no_hp = ?)
                    AND status = 'aktif'
                    LIMIT 1
                ");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch();

                if ($user && verify_password($password, $user['password'])) {
                    // Login berhasil
                    set_user_session($user);

                    // Ingat saya: set cookie 30 hari
                    if ($ingat) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                        // Simpan token ke database (opsional - untuk keamanan lebih)
                    }

                    // Redirect ke halaman sebelumnya atau beranda
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Email/No. HP atau password salah.';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#041627", "on-primary": "#ffffff",
                        "primary-container": "#1a2b3c", "on-primary-container": "#8192a7",
                        "secondary": "#855300", "secondary-container": "#fea619",
                        "on-secondary-container": "#684000",
                        "background": "#f8f9fa", "on-background": "#191c1d",
                        "surface": "#f8f9fa", "on-surface": "#191c1d",
                        "surface-variant": "#e1e3e4", "on-surface-variant": "#44474c",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f3f4f5",
                        "surface-container": "#edeeef",
                        "outline": "#74777d", "outline-variant": "#c4c6cd",
                        "error": "#ba1a1a", "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "tertiary-fixed": "#d9e3f7",
                    },
                    spacing: {
                        "margin-desktop": "64px", "margin-mobile": "16px",
                        "gutter": "24px", "base": "8px",
                    },
                    fontFamily: {
                        "headline-md": ["Montserrat"], "headline-sm": ["Montserrat"],
                        "display-lg": ["Montserrat"], "body-md": ["Inter"],
                        "body-lg": ["Inter"], "label-md": ["Inter"], "label-sm": ["Inter"],
                    },
                    fontSize: {
                        "display-lg": ["48px", { lineHeight: "56px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                        "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "500" }],
                        "label-sm": ["12px", { lineHeight: "16px", fontWeight: "600" }],
                    },
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body-md h-screen flex flex-col antialiased">

<!-- Header minimal -->
<header class="absolute top-0 left-0 w-full p-6 z-10 flex justify-between items-center pointer-events-none">
    <a href="<?= BASE_URL ?>/beranda.php"
       class="pointer-events-auto flex items-center gap-2 text-on-primary hover:opacity-70 transition-all">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div class="text-headline-md font-headline-md font-bold text-on-primary tracking-tight">
        <?= APP_NAME ?>
    </div>
    <div class="hidden md:flex gap-6 pointer-events-auto">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="text-on-primary font-label-md text-label-md hover:text-secondary-container transition-colors">
            Beranda
        </a>
        <a href="<?= BASE_URL ?>/daftar_armada.php"
           class="text-on-primary font-label-md text-label-md hover:text-secondary-container transition-colors">
            Armada
        </a>
        <a href="<?= BASE_URL ?>/kontak.php"
           class="text-on-primary font-label-md text-label-md hover:text-secondary-container transition-colors">
            Kontak
        </a>
    </div>
</header>

<main class="flex-1 flex w-full">

    <!-- Kiri: Foto + Value Props -->
    <div class="hidden md:flex md:w-1/2 relative bg-primary items-end
                pb-margin-desktop px-margin-desktop">
        <div class="absolute inset-0 bg-cover bg-center"
             style="background-image: url('https://lh3.googleusercontent.com/aida/AP1WRLs5jW3nWRoKX7LXXLlGjSwOmSV1v4aaN_5kOOr7E7chL_gj42V7yKafqYUI5rSiM_NWJ3sY4t8jrYSSyviiISbG6rxWWU9uWOOqthyzff2MTY4GtPHmBudM4TOECwJLU5T30jl9SuK1lLiU71fLt9XfLJwtReQQa6UjlgHTapF3b3XvXXLsce3fLQfnr7x4sSLPvaW959jgdaNEhbgDdxdCXiiwVmX2ba2ehXTHvASqBVxdbazDWSMkcJs')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
        <div class="relative z-10 w-full max-w-lg">
            <h1 class="text-display-lg font-display-lg text-on-primary mb-8 leading-tight">
                Sewa mudah,<br>perjalanan menyenangkan
            </h1>
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3 text-on-primary">
                    <span class="material-symbols-outlined text-secondary-container">group</span>
                    <span class="font-label-md text-label-md">10.000+ pelanggan puas</span>
                </div>
                <div class="flex items-center gap-3 text-on-primary">
                    <span class="material-symbols-outlined text-secondary-container">directions_car</span>
                    <span class="font-label-md text-label-md">50+ armada pilihan</span>
                </div>
                <div class="flex items-center gap-3 text-on-primary">
                    <span class="material-symbols-outlined text-secondary-container">support_agent</span>
                    <span class="font-label-md text-label-md">Layanan 24 jam</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanan: Form Login -->
    <div class="w-full md:w-1/2 flex items-center justify-center
                p-margin-mobile md:p-margin-desktop bg-surface-container-lowest">
        <div class="w-full max-w-md">

            <!-- Toggle Login / Daftar -->
            <div class="flex p-1 bg-surface-container-low rounded-lg w-max mb-8">
                <button class="px-6 py-2 bg-surface-container-lowest rounded shadow-sm
                               text-primary font-label-md text-label-md transition-all">
                    Masuk
                </button>
                <a href="<?= BASE_URL ?>/auth/daftar.php"
                   class="px-6 py-2 text-on-surface-variant font-label-md text-label-md
                          hover:text-primary transition-all">
                    Daftar
                </a>
            </div>

            <!-- Judul -->
            <div class="mb-8">
                <h2 class="text-headline-md font-headline-md text-on-surface mb-2">
                    Selamat Datang Kembali 👋
                </h2>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Masuk ke akun Anda
                </p>
            </div>

            <!-- Pesan Error -->
            <?php if ($error): ?>
                <div class="flex items-center gap-3 p-4 mb-6 rounded-xl
                            bg-error-container border border-error/20 text-on-error-container">
                    <span class="material-symbols-outlined shrink-0">error</span>
                    <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Pesan Sukses (dari register/reset password) -->
            <?php
            $flash = get_flash();
            if ($flash): ?>
                <div class="flex items-center gap-3 p-4 mb-6 rounded-xl
                            bg-green-50 border border-green-200 text-green-800">
                    <span class="material-symbols-outlined shrink-0">check_circle</span>
                    <p class="font-label-md text-label-md"><?= bersihkan($flash['pesan']) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="" class="flex flex-col gap-5">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <!-- Email / No HP -->
                <div>
                    <label class="block text-label-md font-label-md text-on-surface mb-1"
                           for="identifier">
                        Email / No. HP
                    </label>
                    <input type="text"
                           id="identifier"
                           name="identifier"
                           value="<?= bersihkan($_POST['identifier'] ?? '') ?>"
                           placeholder="Masukkan email atau nomor HP"
                           required
                           class="w-full border border-outline-variant rounded-lg px-4 py-3
                                  bg-surface focus:ring-1 focus:ring-primary focus:border-primary
                                  transition-colors text-body-md font-body-md outline-none
                                  <?= $error ? 'border-error' : '' ?>">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-label-md font-label-md text-on-surface mb-1"
                           for="password">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="Masukkan password"
                               required
                               class="w-full border border-outline-variant rounded-lg px-4 py-3
                                      bg-surface focus:ring-1 focus:ring-primary focus:border-primary
                                      transition-colors text-body-md font-body-md outline-none
                                      <?= $error ? 'border-error' : '' ?>">
                        <button type="button" id="toggle-password"
                                class="absolute right-4 top-1/2 -translate-y-1/2
                                       text-on-surface-variant hover:text-primary transition-colors">
                            <span class="material-symbols-outlined" id="eye-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Ingat + Lupa Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="ingat"
                               class="rounded border-outline-variant text-primary
                                      focus:ring-primary w-4 h-4 bg-surface">
                        <span class="text-label-md font-label-md text-on-surface
                                     group-hover:text-primary transition-colors">
                            Ingat saya
                        </span>
                    </label>
                    <a href="<?= BASE_URL ?>/auth/lupa_password.php"
                       class="text-label-md font-label-md text-secondary-container
                              hover:text-secondary transition-colors">
                        Lupa Password?
                    </a>
                </div>

                <!-- Tombol Masuk -->
                <button type="submit"
                        class="w-full bg-primary text-on-primary rounded-lg py-3
                               flex items-center justify-center gap-2
                               hover:bg-primary-container transition-colors mt-2
                               active:scale-95 shadow-md font-label-md text-label-md">
                    MASUK
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-4 my-8">
                <div class="h-px bg-outline-variant flex-1"></div>
                <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">
                    atau
                </span>
                <div class="h-px bg-outline-variant flex-1"></div>
            </div>

            <!-- Link ke WhatsApp Admin -->
            <a href="https://wa.me/<?= APP_WHATSAPP ?>?text=Halo, saya ingin bantuan login ke Luxe Drive"
               target="_blank"
               class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg
                      border border-outline-variant hover:bg-surface-container transition-colors
                      text-on-surface font-label-md text-label-md">
                <span class="material-symbols-outlined text-[20px] text-secondary-container">chat</span>
                Butuh Bantuan? Hubungi Admin
            </a>

            <!-- Link Daftar -->
            <div class="mt-8 text-center">
                <p class="text-label-md font-label-md text-on-surface-variant">
                    Belum punya akun?
                    <a href="<?= BASE_URL ?>/auth/daftar.php"
                       class="text-secondary-container font-semibold hover:underline">
                        Daftar Sekarang Gratis &rsaquo;
                    </a>
                </p>
            </div>

        </div>
    </div>
</main>

<script>
    // Toggle show/hide password
    document.getElementById('toggle-password').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type   = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type   = 'password';
            icon.textContent = 'visibility';
        }
    });
</script>
</body>
</html>
