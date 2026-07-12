<?php
// ============================================================
// admin/login.php — Login Admin
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

// Jika sudah login admin, redirect ke dashboard
if (is_admin_login()) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';

// ============================================================
// PROSES LOGIN ADMIN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan coba lagi.';
    } else {
        $identifier = bersihkan($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';
        $ingat      = isset($_POST['ingat']);

        if (empty($identifier) || empty($password)) {
            $error = 'Email dan password wajib diisi.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM admin
                    WHERE email = ? AND status = 'aktif'
                    LIMIT 1
                ");
                $stmt->execute([$identifier]);
                $admin = $stmt->fetch();

                if ($admin && verify_password($password, $admin['password'])) {
                    // Login berhasil
                    set_admin_session($admin);

                    // Update last_login
                    $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE id = ?")
                        ->execute([$admin['id']]);

                    header('Location: ' . ADMIN_URL . '/index.php');
                    exit;
                } else {
                    $error = 'Email atau password salah.';
                    // Delay untuk mencegah brute force
                    sleep(1);
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Coba lagi.';
                error_log('Admin login error: ' . $e->getMessage());
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
    <title>Login Admin — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode:"class",
            theme:{ extend:{
                colors:{
                    "primary":"#041627","on-primary":"#ffffff",
                    "primary-container":"#1a2b3c","on-primary-container":"#8192a7",
                    "secondary":"#855300","secondary-container":"#fea619",
                    "background":"#f8f9fa","on-background":"#191c1d",
                    "surface":"#f8f9fa","on-surface":"#191c1d",
                    "surface-variant":"#e1e3e4","on-surface-variant":"#44474c",
                    "surface-container-lowest":"#ffffff",
                    "surface-container-low":"#f3f4f5",
                    "surface-container":"#edeeef",
                    "outline":"#74777d","outline-variant":"#c4c6cd",
                    "error":"#ba1a1a","error-container":"#ffdad6",
                    "on-error-container":"#93000a",
                },
                fontFamily:{
                    "headline-md":["Montserrat"],"headline-sm":["Montserrat"],
                    "body-md":["Inter"],"label-md":["Inter"],"label-sm":["Inter"],
                },
                fontSize:{
                    "headline-md":["24px",{lineHeight:"32px",fontWeight:"600"}],
                    "headline-sm":["20px",{lineHeight:"28px",fontWeight:"600"}],
                    "body-md":["16px",{lineHeight:"24px",fontWeight:"400"}],
                    "label-md":["14px",{lineHeight:"20px",letterSpacing:"0.01em",fontWeight:"500"}],
                    "label-sm":["12px",{lineHeight:"16px",fontWeight:"600"}],
                },
                spacing:{ "margin-desktop":"64px","margin-mobile":"16px" },
            }}
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .shadow-ambient {
            box-shadow: 0px 4px 32px rgba(4,22,39,0.08), 0px 2px 12px rgba(4,22,39,0.04);
        }
    </style>
</head>
<body class="bg-background min-h-screen flex items-center justify-center
             relative overflow-hidden font-body-md text-body-md text-on-background">

    <!-- Background: Foto + Overlay -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0 bg-cover bg-center"
             style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBJJEUJyhskMcBin1pc4xtbgtrrR2vQRb83lkS4KRDWf_Z7XVpB_wdAkda7dxR4c9wJnkPU4icub6WbOxnIvcNXDJWAc45I6aRVRLrtLY5h1vCETRlwgUxq8G5cFVrL0IvmbB3qvC6eTt8WWv3SQgQe9ShzH2UEfCsr2b6MOb22WKW3q7c7WclrHQTLoirHZqln988jH38sVkSb-4E1-44RmN0EfdxNxIaTVntOlBn8766-0BzaLN4ZE8tu1z2hRfJ6oFFInPhV2g8')">
        </div>
        <div class="absolute inset-0 bg-primary/80 backdrop-blur-sm mix-blend-multiply"></div>
    </div>

    <!-- Konten -->
    <main class="relative z-10 w-full px-margin-mobile md:px-margin-desktop py-12
                 flex items-center justify-center">

        <div class="bg-surface-container-lowest rounded-xl shadow-ambient
                    w-full max-w-[440px] p-8 md:p-10
                    border border-outline-variant/30
                    flex flex-col items-center">

            <!-- Brand -->
            <div class="mb-10 text-center w-full flex flex-col items-center">
                <div class="w-12 h-12 bg-surface-container rounded-full
                            flex items-center justify-center mb-4 text-primary">
                    <span class="material-symbols-outlined text-[28px]"
                          style="font-variation-settings:'FILL' 1">
                        admin_panel_settings
                    </span>
                </div>
                <h1 class="font-headline-md text-headline-md tracking-tight
                           text-primary uppercase mb-2">
                    <?= APP_NAME ?>
                </h1>
                <h2 class="font-headline-sm text-headline-sm text-on-surface-variant">
                    Panel Administrator
                </h2>
                <p class="font-body-md text-body-md text-outline mt-2 text-sm text-center">
                    Masuk untuk mengelola armada dan layanan.
                </p>
            </div>

            <!-- Error -->
            <?php if ($error): ?>
                <div class="w-full flex items-center gap-3 p-4 mb-6 rounded-xl
                            bg-error-container border border-error/20 text-on-error-container">
                    <span class="material-symbols-outlined shrink-0">error</span>
                    <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="" class="w-full flex flex-col gap-6">
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="flex flex-col gap-2">
                    <label class="font-label-sm text-label-sm text-on-surface"
                           for="identifier">
                        Email atau Username
                    </label>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-outline">
                            person
                        </span>
                        <input type="text" id="identifier" name="identifier"
                               value="<?= bersihkan($_POST['identifier'] ?? '') ?>"
                               placeholder="admin@luxedrive.com"
                               required autocomplete="email"
                               class="w-full h-12 pl-10 pr-4 bg-surface rounded-xl
                                      border border-outline-variant text-on-surface
                                      placeholder:text-outline
                                      focus:outline-none focus:border-2 focus:border-primary
                                      focus:bg-surface-container-lowest transition-all">
                    </div>
                </div>

                <!-- Password -->
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-center">
                        <label class="font-label-sm text-label-sm text-on-surface"
                               for="password">
                            Kata Sandi
                        </label>
                        <a href="<?= BASE_URL ?>/auth/lupa_password.php?tipe=admin"
                           class="font-label-sm text-label-sm text-primary
                                  hover:text-secondary-container transition-colors">
                            Lupa sandi?
                        </a>
                    </div>
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-outline">
                            lock
                        </span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               required autocomplete="current-password"
                               class="w-full h-12 pl-10 pr-12 bg-surface rounded-xl
                                      border border-outline-variant text-on-surface
                                      placeholder:text-outline
                                      focus:outline-none focus:border-2 focus:border-primary
                                      focus:bg-surface-container-lowest transition-all">
                        <button type="button"
                                onclick="togglePass()"
                                class="absolute right-3 text-outline hover:text-primary
                                       transition-colors focus:outline-none">
                            <span class="material-symbols-outlined" id="eye-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Ingat Saya -->
                <div class="flex items-center gap-3 mt-1">
                    <input type="checkbox" id="ingat" name="ingat"
                           class="w-5 h-5 rounded border-outline-variant text-primary
                                  focus:ring-primary focus:ring-2 focus:ring-offset-0
                                  bg-surface checked:bg-primary cursor-pointer transition-all">
                    <label for="ingat"
                           class="font-body-md text-body-md text-on-surface-variant cursor-pointer text-sm">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <!-- Tombol Masuk -->
                <button type="submit"
                        class="w-full h-12 mt-4 bg-primary text-on-primary
                               font-label-md text-label-md rounded-xl
                               hover:bg-primary-container focus:ring-2 focus:ring-offset-2
                               focus:ring-primary transition-all duration-200
                               flex items-center justify-center gap-2 shadow-sm
                               active:scale-[0.98]">
                    <span>MASUK</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </form>

            <!-- Footer keamanan -->
            <div class="mt-8 pt-6 border-t border-surface-container w-full
                        text-center flex items-center justify-center gap-2 text-outline">
                <span class="material-symbols-outlined text-[16px]">lock_clock</span>
                <span class="font-label-sm text-label-sm">
                    Akses Terenkripsi &amp; Terpantau 24/7
                </span>
            </div>
        </div>
    </main>

<script>
    function togglePass() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        if (input.type === 'password') {
            input.type       = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type       = 'password';
            icon.textContent = 'visibility';
        }
    }
</script>
</body>
</html>
