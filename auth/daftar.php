<?php
// ============================================================
// auth/daftar.php — Register User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/email.php';

// Jika sudah login, redirect ke beranda
if (is_user_login()) {
    header('Location: ' . BASE_URL . '/beranda.php');
    exit;
}

$error   = '';
$errors  = []; // Error per field
$success = '';

// ============================================================
// PROSES REGISTER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        // Ambil & bersihkan input
        $nama        = bersihkan($_POST['nama']        ?? '');
        $no_hp       = bersihkan($_POST['no_hp']       ?? '');
        $email       = bersihkan($_POST['email']       ?? '');
        $tgl_lahir   = bersihkan($_POST['tgl_lahir']   ?? '');
        $password    = $_POST['password']              ?? '';
        $konfirmasi  = $_POST['konfirmasi_password']   ?? '';
        $setuju      = isset($_POST['setuju']);

        // Validasi per field
        if (empty($nama))       $errors['nama']      = 'Nama lengkap wajib diisi.';
        if (empty($no_hp))      $errors['no_hp']     = 'No. HP wajib diisi.';
        if (empty($email))      $errors['email']     = 'Email wajib diisi.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                $errors['email']     = 'Format email tidak valid.';
        if (empty($password))   $errors['password']  = 'Password wajib diisi.';
        elseif (strlen($password) < 8)
                                $errors['password']  = 'Password minimal 8 karakter.';
        if ($password !== $konfirmasi)
                                $errors['konfirmasi'] = 'Konfirmasi password tidak cocok.';
        if (!$setuju)           $errors['setuju']    = 'Anda harus menyetujui syarat & ketentuan.';

        if (empty($errors)) {
            try {
                // Cek email sudah ada
                $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $cek->execute([$email]);
                if ($cek->fetch()) {
                    $errors['email'] = 'Email sudah terdaftar. Silakan masuk.';
                }

                // Cek no_hp sudah ada
                $cek2 = $pdo->prepare("SELECT id FROM users WHERE no_hp = ?");
                $cek2->execute([$no_hp]);
                if ($cek2->fetch()) {
                    $errors['no_hp'] = 'No. HP sudah terdaftar.';
                }

                if (empty($errors)) {
                    // Simpan user baru
                    $stmt = $pdo->prepare("
                        INSERT INTO users (nama, email, no_hp, tgl_lahir, password, status)
                        VALUES (?, ?, ?, ?, ?, 'aktif')
                    ");
                    $stmt->execute([
                        $nama,
                        $email,
                        $no_hp,
                        $tgl_lahir ?: null,
                        hash_password($password),
                    ]);

                    // Kirim email selamat datang (tidak blocking)
                    try { kirim_email_registrasi($email, $nama); } catch (Exception $e) {}

                    // Set flash message dan redirect ke login
                    set_flash('sukses', 'Akun berhasil dibuat! Silakan masuk.');
                    header('Location: ' . BASE_URL . '/auth/masuk.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                error_log('Register error: ' . $e->getMessage());
            }
        }
    }
}

// Helper: tampil border error per field
function field_class(string $field, array $errors): string {
    return isset($errors[$field])
        ? 'border-error focus:ring-error focus:border-error'
        : 'border-outline-variant focus:ring-primary focus:border-primary';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#041627", "on-primary": "#ffffff",
                        "primary-container": "#1a2b3c",
                        "secondary": "#855300", "secondary-container": "#fea619",
                        "on-secondary-container": "#684000",
                        "background": "#f8f9fa", "on-background": "#191c1d",
                        "surface": "#f8f9fa", "on-surface": "#191c1d",
                        "surface-variant": "#e1e3e4", "on-surface-variant": "#44474c",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f3f4f5",
                        "surface-container": "#edeeef",
                        "surface-container-high": "#e7e8e9",
                        "outline": "#74777d", "outline-variant": "#c4c6cd",
                        "error": "#ba1a1a", "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                    },
                    spacing: {
                        "margin-desktop": "64px", "margin-mobile": "16px",
                        "gutter": "24px", "base": "8px",
                    },
                    fontFamily: {
                        "headline-md": ["Montserrat"], "display-lg": ["Montserrat"],
                        "body-md": ["Inter"], "body-lg": ["Inter"],
                        "label-md": ["Inter"], "label-sm": ["Inter"],
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
<body class="antialiased min-h-screen flex flex-col font-body-md text-body-md">

<!-- Tombol kembali -->
<div class="absolute top-6 left-6 z-50">
    <a href="<?= BASE_URL ?>/beranda.php"
       class="flex items-center justify-center w-10 h-10 rounded-full
              text-white hover:opacity-70 transition-all">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
</div>

<main class="flex-grow flex w-full">

    <!-- Kiri: Foto + Value Props -->
    <div class="hidden lg:flex w-1/2 relative bg-primary">
        <div class="absolute inset-0 bg-cover bg-center opacity-60 mix-blend-overlay"
             style="background-image: url('https://lh3.googleusercontent.com/aida/AP1WRLs5jW3nWRoKX7LXXLlGjSwOmSV1v4aaN_5kOOr7E7chL_gj42V7yKafqYUI5rSiM_NWJ3sY4t8jrYSSyviiISbG6rxWWU9uWOOqthyzff2MTY4GtPHmBudM4TOECwJLU5T30jl9SuK1lLiU71fLt9XfLJwtReQQa6UjlgHTapF3b3XvXXLsce3fLQfnr7x4sSLPvaW959jgdaNEhbgDdxdCXiiwVmX2ba2ehXTHvASqBVxdbazDWSMkcJs')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent"></div>
        <div class="relative z-10 p-margin-desktop flex flex-col justify-end h-full text-on-primary">
            <h1 class="font-display-lg text-display-lg mb-6 leading-tight">
                Daftar gratis,<br>langsung bisa sewa!
            </h1>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container"
                          style="font-variation-settings: 'FILL' 1;">speed</span>
                    <span class="font-body-lg text-body-lg">Proses cepat &amp; mudah</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container"
                          style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
                    <span class="font-body-lg text-body-lg">Harga transparan</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container"
                          style="font-variation-settings: 'FILL' 1;">directions_car</span>
                    <span class="font-body-lg text-body-lg">Armada terawat</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanan: Form Register -->
    <div class="w-full lg:w-1/2 flex items-center justify-center
                p-margin-mobile md:p-margin-desktop bg-surface-container-lowest overflow-y-auto">
        <div class="w-full max-w-md py-8">

            <!-- Toggle Login / Daftar -->
            <div class="flex p-1 bg-surface-container rounded-lg mb-8">
                <a href="<?= BASE_URL ?>/auth/masuk.php"
                   class="flex-1 py-2 text-center rounded font-label-md text-label-md
                          text-on-surface-variant hover:text-on-surface transition-colors">
                    Masuk
                </a>
                <button class="flex-1 py-2 text-center rounded bg-surface-container-lowest
                               shadow-sm font-label-md text-label-md font-bold text-primary">
                    Daftar
                </button>
            </div>

            <!-- Judul -->
            <div class="mb-8">
                <h2 class="font-headline-md text-headline-md mb-2 text-primary">
                    Buat Akun Baru 🎉
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Daftar dan mulai sewa!
                </p>
            </div>

            <!-- Error umum -->
            <?php if ($error): ?>
                <div class="flex items-center gap-3 p-4 mb-6 rounded-xl
                            bg-error-container border border-error/20 text-on-error-container">
                    <span class="material-symbols-outlined shrink-0">error</span>
                    <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form Register -->
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                           for="nama">
                        Nama Lengkap <span class="text-error">*</span>
                    </label>
                    <input type="text"
                           id="nama" name="nama"
                           value="<?= bersihkan($_POST['nama'] ?? '') ?>"
                           placeholder="Nama lengkap sesuai KTP"
                           required
                           class="w-full px-4 py-3 rounded-lg border bg-surface-container-lowest
                                  transition-colors outline-none text-on-surface
                                  <?= field_class('nama', $errors) ?>">
                    <?php if (isset($errors['nama'])): ?>
                        <p class="mt-1 text-label-sm font-label-sm text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['nama'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- No HP & Email -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                               for="no_hp">
                            No. HP / WhatsApp <span class="text-error">*</span>
                        </label>
                        <input type="tel"
                               id="no_hp" name="no_hp"
                               value="<?= bersihkan($_POST['no_hp'] ?? '') ?>"
                               placeholder="0812..."
                               required
                               class="w-full px-4 py-3 rounded-lg border bg-surface-container-lowest
                                      transition-colors outline-none text-on-surface
                                      <?= field_class('no_hp', $errors) ?>">
                        <?php if (isset($errors['no_hp'])): ?>
                            <p class="mt-1 text-label-sm font-label-sm text-error">
                                <?= $errors['no_hp'] ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                               for="email">
                            Email <span class="text-error">*</span>
                        </label>
                        <input type="email"
                               id="email" name="email"
                               value="<?= bersihkan($_POST['email'] ?? '') ?>"
                               placeholder="nama@email.com"
                               required
                               class="w-full px-4 py-3 rounded-lg border bg-surface-container-lowest
                                      transition-colors outline-none text-on-surface
                                      <?= field_class('email', $errors) ?>">
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-label-sm font-label-sm text-error">
                                <?= $errors['email'] ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                           for="tgl_lahir">
                        Tanggal Lahir
                    </label>
                    <div class="relative">
                        <input type="date"
                               id="tgl_lahir" name="tgl_lahir"
                               value="<?= bersihkan($_POST['tgl_lahir'] ?? '') ?>"
                               max="<?= date('Y-m-d', strtotime('-17 years')) ?>"
                               class="w-full px-4 py-3 rounded-lg border border-outline-variant
                                      bg-surface-container-lowest focus:border-primary
                                      focus:ring-1 focus:ring-primary transition-colors
                                      outline-none text-on-surface appearance-none">
                        <span class="material-symbols-outlined absolute right-3 top-1/2
                                     -translate-y-1/2 text-on-surface-variant pointer-events-none">
                            calendar_today
                        </span>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                           for="password">
                        Password <span class="text-error">*</span>
                    </label>
                    <div class="relative mb-2">
                        <input type="password"
                               id="password" name="password"
                               placeholder="Min. 8 karakter"
                               required
                               oninput="cekKekuatanPassword(this.value)"
                               class="w-full px-4 py-3 rounded-lg border bg-surface-container-lowest
                                      transition-colors outline-none text-on-surface
                                      <?= field_class('password', $errors) ?>">
                        <button type="button" onclick="togglePass('password', 'eye1')"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                       text-on-surface-variant hover:text-primary">
                            <span class="material-symbols-outlined" id="eye1">visibility_off</span>
                        </button>
                    </div>
                    <!-- Indikator kekuatan password -->
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                            <div id="strength-bar"
                                 class="h-full rounded-full transition-all duration-300"
                                 style="width:0%"></div>
                        </div>
                        <span id="strength-label"
                              class="font-label-sm text-label-sm text-on-surface-variant whitespace-nowrap">
                            Kekuatan: —
                        </span>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-label-sm font-label-sm text-error">
                            <?= $errors['password'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"
                           for="konfirmasi_password">
                        Konfirmasi Password <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input type="password"
                               id="konfirmasi_password" name="konfirmasi_password"
                               placeholder="Ulangi password"
                               required
                               class="w-full px-4 py-3 rounded-lg border bg-surface-container-lowest
                                      transition-colors outline-none text-on-surface
                                      <?= field_class('konfirmasi', $errors) ?>">
                        <button type="button" onclick="togglePass('konfirmasi_password', 'eye2')"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                       text-on-surface-variant hover:text-primary">
                            <span class="material-symbols-outlined" id="eye2">visibility_off</span>
                        </button>
                    </div>
                    <?php if (isset($errors['konfirmasi'])): ?>
                        <p class="mt-1 text-label-sm font-label-sm text-error">
                            <?= $errors['konfirmasi'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Checkbox Setuju -->
                <div class="flex items-start gap-3 mt-4">
                    <input type="checkbox" id="setuju" name="setuju"
                           <?= isset($_POST['setuju']) ? 'checked' : '' ?>
                           class="mt-1 w-5 h-5 rounded border-outline-variant
                                  text-primary focus:ring-primary cursor-pointer
                                  <?= isset($errors['setuju']) ? 'border-error' : '' ?>">
                    <label for="setuju"
                           class="font-body-md text-body-md text-on-surface-variant cursor-pointer text-sm">
                        Saya menyetujui
                        <a href="<?= BASE_URL ?>/syarat_ketentuan.php"
                           class="text-primary font-medium hover:underline" target="_blank">
                            Syarat &amp; Ketentuan
                        </a>
                        serta
                        <a href="<?= BASE_URL ?>/kebijakan_privasi.php"
                           class="text-primary font-medium hover:underline" target="_blank">
                            Kebijakan Privasi
                        </a>.
                    </label>
                </div>
                <?php if (isset($errors['setuju'])): ?>
                    <p class="text-label-sm font-label-sm text-error -mt-2">
                        <?= $errors['setuju'] ?>
                    </p>
                <?php endif; ?>

                <!-- Tombol Daftar -->
                <button type="submit"
                        class="w-full bg-primary text-on-primary py-3.5 rounded-lg
                               font-label-md text-label-md font-bold mt-6
                               hover:bg-primary/90 transition-colors
                               shadow-[0px_4px_20px_rgba(4,22,39,0.12)]
                               active:scale-[0.98]">
                    DAFTAR SEKARANG
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center gap-4 my-8">
                <div class="flex-1 h-px bg-outline-variant"></div>
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                    atau
                </span>
                <div class="flex-1 h-px bg-outline-variant"></div>
            </div>

            <!-- Link Masuk -->
            <p class="text-center font-body-md text-body-md text-on-surface-variant">
                Sudah punya akun?
                <a href="<?= BASE_URL ?>/auth/masuk.php"
                   class="text-primary font-medium hover:underline">
                    Masuk di sini &rsaquo;
                </a>
            </p>
        </div>
    </div>
</main>

<script>
    // Toggle show/hide password
    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type       = 'text';
            icon.textContent = 'visibility';
        } else {
            input.type       = 'password';
            icon.textContent = 'visibility_off';
        }
    }

    // Cek kekuatan password
    function cekKekuatanPassword(value) {
        const bar   = document.getElementById('strength-bar');
        const label = document.getElementById('strength-label');
        let score   = 0;

        if (value.length >= 8)  score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        const config = [
            { w: '0%',   color: '',                  text: '—' },
            { w: '25%',  color: 'bg-error',           text: 'Lemah' },
            { w: '50%',  color: 'bg-secondary-container', text: 'Sedang' },
            { w: '75%',  color: 'bg-secondary-container', text: 'Kuat' },
            { w: '100%', color: 'bg-green-500',        text: 'Sangat Kuat' },
        ];

        const c = config[score] || config[0];
        bar.style.width = c.w;
        bar.className   = 'h-full rounded-full transition-all duration-300 ' + c.color;
        label.textContent = 'Kekuatan: ' + c.text;
    }
</script>
</body>
</html>
