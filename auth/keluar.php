<?php
// ============================================================
// auth/keluar.php — Halaman Konfirmasi Logout
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

// Jika konfirmasi logout
if (isset($_POST['konfirmasi_keluar'])) {
    logout_user(); // Redirect otomatis ke masuk.php
}
// Jika batal
if (isset($_POST['batal'])) {
    header('Location: ' . BASE_URL . '/beranda.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keluar — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode:"class",
            theme:{ extend:{
                colors:{
                    "primary":"#041627","on-primary":"#ffffff",
                    "primary-container":"#1a2b3c",
                    "secondary":"#855300","secondary-container":"#fea619",
                    "on-secondary-container":"#684000",
                    "background":"#f8f9fa","on-background":"#191c1d",
                    "surface":"#f8f9fa","on-surface":"#191c1d",
                    "surface-variant":"#e1e3e4","on-surface-variant":"#44474c",
                    "surface-container-lowest":"#ffffff",
                    "surface-container-low":"#f3f4f5",
                    "surface-container":"#edeeef",
                    "surface-container-high":"#e7e8e9",
                    "outline":"#74777d","outline-variant":"#c4c6cd",
                    "error":"#ba1a1a","error-container":"#ffdad6",
                },
                fontFamily:{
                    "headline-md":["Montserrat"],"headline-sm":["Montserrat"],
                    "body-md":["Inter"],"label-md":["Inter"],"label-sm":["Inter"],
                },
                fontSize:{
                    "headline-sm":["20px",{lineHeight:"28px",fontWeight:"600"}],
                    "body-md":["16px",{lineHeight:"24px",fontWeight:"400"}],
                    "label-md":["14px",{lineHeight:"20px",letterSpacing:"0.01em",fontWeight:"500"}],
                },
                spacing:{ "margin-mobile":"16px" },
            }}
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
        .glass-panel {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(24px);
            box-shadow: 0px 8px 32px rgba(4,22,39,0.15);
        }
    </style>
</head>
<body class="bg-surface-container-low min-h-screen flex items-center justify-center
             relative overflow-hidden text-on-surface">

    <!-- Background -->
    <div class="absolute inset-0 z-0">
        <div class="w-full h-full bg-cover bg-center opacity-80"
             style="background-image: url('https://lh3.googleusercontent.com/aida/AP1WRLs5jW3nWRoKX7LXXLlGjSwOmSV1v4aaN_5kOOr7E7chL_gj42V7yKafqYUI5rSiM_NWJ3sY4t8jrYSSyviiISbG6rxWWU9uWOOqthyzff2MTY4GtPHmBudM4TOECwJLU5T30jl9SuK1lLiU71fLt9XfLJwtReQQa6UjlgHTapF3b3XvXXLsce3fLQfnr7x4sSLPvaW959jgdaNEhbgDdxdCXiiwVmX2ba2ehXTHvASqBVxdbazDWSMkcJs')">
        </div>
        <div class="absolute inset-0 bg-primary/70 backdrop-blur-[2px]"></div>
    </div>

    <main class="relative z-10 w-full max-w-md px-margin-mobile md:px-0">

        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="font-headline-md text-headline-md font-bold text-on-primary tracking-tight">
                <?= APP_NAME ?>
            </h1>
        </div>

        <!-- Card Konfirmasi -->
        <section class="glass-panel rounded-xl p-8 md:p-10 text-center">
            <div class="w-16 h-16 mx-auto bg-surface-container-high rounded-full
                        flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-[32px] text-primary">logout</span>
            </div>

            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">
                Apakah Anda yakin ingin keluar?
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-8">
                Anda akan dialihkan ke halaman masuk setelah keluar.
                Sesi Anda saat ini akan diakhiri demi keamanan.
            </p>

            <form method="POST" class="flex flex-col-reverse md:flex-row gap-4 w-full">
                <?= csrf_field() ?>
                <button type="submit" name="batal"
                        class="flex-1 py-3 px-6 rounded-lg font-label-md text-label-md
                               border border-outline-variant text-on-surface
                               hover:bg-surface-container-low transition-colors active:scale-[0.98]">
                    Batal
                </button>
                <button type="submit" name="konfirmasi_keluar"
                        class="flex-1 py-3 px-6 rounded-lg font-label-md text-label-md
                               bg-secondary-container text-on-secondary-container
                               hover:opacity-90 transition-opacity shadow-sm active:scale-[0.98]">
                    Ya, Keluar
                </button>
            </form>
        </section>
    </main>
</body>
</html>
