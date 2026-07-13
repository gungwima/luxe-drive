<?php
// ============================================================
// includes/navbar_admin.php
// Header/Navbar atas untuk semua halaman ADMIN
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

// Wajib login admin
require_admin_login();

$admin = get_admin_login();

// Hitung notifikasi per kategori (agar rincian jelas)
$notif_count = 0;
$notif_booking = 0;
$notif_ulasan  = 0;
try {
    global $pdo;
    if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';

    $notif_booking = (int) $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'pending'")->fetchColumn();
    $notif_ulasan  = (int) $pdo->query("SELECT COUNT(*) FROM ulasan WHERE status = 'pending'")->fetchColumn();
    $notif_count   = $notif_booking + $notif_ulasan;
} catch (Exception $e) {
    $notif_count = 0;
}

$page_title_admin = isset($page_title_admin)
    ? $page_title_admin . ' — Admin ' . APP_NAME
    : 'Admin Panel — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title_admin) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Tailwind Config (sama dengan user) -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary":                  "#041627",
                        "on-primary":               "#ffffff",
                        "primary-container":        "#1a2b3c",
                        "on-primary-container":     "#8192a7",
                        "primary-fixed":            "#d2e4fb",
                        "secondary":                "#855300",
                        "on-secondary":             "#ffffff",
                        "secondary-container":      "#fea619",
                        "on-secondary-container":   "#684000",
                        "tertiary":                 "#0b1624",
                        "on-tertiary":              "#ffffff",
                        "tertiary-container":       "#202a39",
                        "on-tertiary-container":    "#8791a4",
                        "tertiary-fixed":           "#d9e3f7",
                        "on-tertiary-fixed":        "#121c2a",
                        "background":               "#f8f9fa",
                        "on-background":            "#191c1d",
                        "surface":                  "#f8f9fa",
                        "on-surface":               "#191c1d",
                        "surface-variant":          "#e1e3e4",
                        "on-surface-variant":       "#44474c",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low":    "#f3f4f5",
                        "surface-container":        "#edeeef",
                        "surface-container-high":   "#e7e8e9",
                        "surface-container-highest":"#e1e3e4",
                        "outline":                  "#74777d",
                        "outline-variant":          "#c4c6cd",
                        "error":                    "#ba1a1a",
                        "on-error":                 "#ffffff",
                        "error-container":          "#ffdad6",
                        "on-error-container":       "#93000a",
                    },
                    spacing: {
                        "base":           "8px",
                        "gutter":         "24px",
                        "margin-mobile":  "16px",
                        "margin-desktop": "64px",
                        "container-max":  "1280px",
                    },
                    fontFamily: {
                        "headline-md": ["Montserrat"],
                        "headline-sm": ["Montserrat"],
                        "body-md":     ["Inter"],
                        "body-lg":     ["Inter"],
                        "label-md":    ["Inter"],
                        "label-sm":    ["Inter"],
                    },
                    fontSize: {
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                        "headline-sm": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                        "body-lg":     ["18px", { lineHeight: "28px", fontWeight: "400" }],
                        "body-md":     ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "label-md":    ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "500" }],
                        "label-sm":    ["12px", { lineHeight: "16px", fontWeight: "600" }],
                    },
                }
            }
        }
    </script>

    <style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            -webkit-font-smoothing: antialiased;
        }
        .material-symbols-outlined.fill {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex">

<!-- ===== HEADER ADMIN ===== -->
<header class="fixed top-0 left-0 right-0 h-16 bg-surface-container-lowest
               border-b border-surface-variant z-50
               flex items-center justify-between px-8">

    <!-- Logo + Subtitle -->
    <div class="flex flex-col">
        <span class="text-headline-sm font-headline-md font-bold tracking-tight text-primary leading-none">
            LUXE DRIVE
        </span>
        <span class="text-[10px] font-bold tracking-[0.2em] text-on-surface-variant uppercase mt-0.5">
            Executive Portal
        </span>
    </div>

    <!-- Kanan: Notifikasi + Profil Admin -->
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-2">

            <!-- Tombol Notifikasi -->
            <div class="relative">
                <button onclick="toggleNotifDropdown()"
                        class="relative p-2 text-on-surface-variant hover:bg-surface-container-low
                               rounded-full transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <?php if ($notif_count > 0): ?>
                        <span class="absolute top-1.5 right-1.5 min-w-[18px] h-[18px] bg-error rounded-full
                                     text-on-error text-[10px] font-bold flex items-center justify-center px-1">
                            <?= $notif_count > 99 ? '99+' : $notif_count ?>
                        </span>
                    <?php endif; ?>
                </button>

                <!-- Dropdown Notifikasi -->
                <div id="notif-dropdown"
                     class="hidden absolute right-0 top-full mt-2 w-80 bg-surface-container-lowest
                            rounded-xl shadow-lg border border-outline-variant/30 z-50">
                    <div class="p-4 border-b border-outline-variant/30">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Notifikasi</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <a href="<?= ADMIN_URL ?>/booking/index.php?status=pending"
                           class="flex items-start gap-3 px-4 py-3 hover:bg-surface-container transition-colors border-b border-outline-variant/20">
                            <span class="material-symbols-outlined text-secondary-container mt-0.5">schedule</span>
                            <div class="flex-1">
                                <p class="font-label-md text-label-md text-on-surface">Booking Pending</p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">Perlu verifikasi pembayaran</p>
                            </div>
                            <?php if ($notif_booking > 0): ?>
                                <span class="bg-error text-on-error text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_booking ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?= ADMIN_URL ?>/ulasan/index.php?status=pending"
                           class="flex items-start gap-3 px-4 py-3 hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-secondary-container mt-0.5">star</span>
                            <div class="flex-1">
                                <p class="font-label-md text-label-md text-on-surface">Ulasan Baru</p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">Perlu moderasi ulasan</p>
                            </div>
                            <?php if ($notif_ulasan > 0): ?>
                                <span class="bg-error text-on-error text-[10px] font-bold px-2 py-0.5 rounded-full"><?= $notif_ulasan ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="p-3 border-t border-outline-variant/30">
                        <a href="<?= ADMIN_URL ?>/index.php"
                           class="block text-center font-label-md text-label-md text-primary hover:underline">
                            Lihat Semua
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tombol Help -->
            <button class="p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full transition-colors">
                <span class="material-symbols-outlined">help</span>
            </button>
        </div>

        <!-- Divider -->
        <div class="h-8 w-px bg-surface-variant"></div>

        <!-- Profil Admin + Dropdown -->
        <div class="relative group">
            <button class="flex items-center gap-3 cursor-pointer hover:opacity-80 transition-opacity">
                <div class="w-10 h-10 rounded-full overflow-hidden border border-surface-variant bg-primary
                            flex items-center justify-center text-on-primary font-bold text-sm">
                    <?php if ($admin['foto']): ?>
                        <img src="<?= url_gambar($admin['foto'], 'admin') ?>"
                             alt="Admin" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= inisial($admin['nama']) ?>
                    <?php endif; ?>
                </div>
                <div class="text-left hidden lg:block">
                    <p class="font-label-md text-label-md font-bold text-primary leading-none">
                        <?= htmlspecialchars($admin['nama']) ?>
                    </p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant capitalize mt-0.5">
                        <?= htmlspecialchars($admin['role']) ?>
                    </p>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant text-sm hidden lg:block">expand_more</span>
            </button>

            <!-- Dropdown Profil Admin -->
            <div class="absolute right-0 top-full mt-2 w-48 bg-surface-container-lowest rounded-xl
                        shadow-lg border border-outline-variant/30
                        opacity-0 invisible group-hover:opacity-100 group-hover:visible
                        transition-all duration-200 z-50">
                <div class="p-2">
                    <a href="<?= ADMIN_URL ?>/profil.php"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-surface-container
                              font-label-md text-label-md text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-[20px] text-on-surface-variant">manage_accounts</span>
                        Profil Admin
                    </a>
                    <a href="<?= ADMIN_URL ?>/pengaturan/index.php"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-surface-container
                              font-label-md text-label-md text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-[20px] text-on-surface-variant">settings</span>
                        Pengaturan
                    </a>
                    <hr class="my-1 border-outline-variant/30">
                    <a href="<?= ADMIN_URL ?>/logout.php"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-error-container
                              font-label-md text-label-md text-error transition-colors">
                        <span class="material-symbols-outlined text-[20px]">logout</span>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleNotifDropdown() {
        const el = document.getElementById('notif-dropdown');
        el.classList.toggle('hidden');
    }
    // Tutup dropdown notif jika klik di luar
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notif-dropdown');
        if (!dropdown) return;
        if (!e.target.closest('[onclick="toggleNotifDropdown()"]') &&
            !e.target.closest('#notif-dropdown')) {
            dropdown.classList.add('hidden');
        }
    });
</script>
