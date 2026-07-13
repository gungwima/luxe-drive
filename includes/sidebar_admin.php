<?php
// ============================================================
// includes/sidebar_admin.php
// Sidebar navigasi kiri untuk semua halaman ADMIN
// Variabel: $menu_aktif = 'dashboard'|'armada'|'booking'|dst
// ============================================================

$menu_aktif = $menu_aktif ?? '';

// Helper class menu aktif vs tidak
function sidebar_class(string $menu, string $aktif): string {
    if ($menu === $aktif) {
        return 'flex items-center px-4 py-3 rounded-lg transition-colors
                bg-secondary-container text-on-primary font-semibold';
    }
    return 'flex items-center px-4 py-3 rounded-lg transition-colors
            text-on-surface-variant hover:bg-surface-container-low group';
}

// Hitung badge notifikasi
$badge_booking = 0;
$badge_ulasan  = 0;
$badge_transaksi = 0;
try {
    global $pdo;
    if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';

    $badge_booking   = (int) $pdo->query("SELECT COUNT(*) FROM booking   WHERE status = 'pending'")->fetchColumn();
    $badge_ulasan    = (int) $pdo->query("SELECT COUNT(*) FROM ulasan    WHERE status = 'pending'")->fetchColumn();
    $badge_transaksi = (int) $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    // Biarkan badge = 0
}

function badge(int $n): string {
    if ($n <= 0) return '';
    return "<span class=\"ml-auto min-w-[20px] h-5 bg-error text-on-error
                           text-[10px] font-bold rounded-full flex items-center
                           justify-center px-1\">"
         . ($n > 99 ? '99+' : $n)
         . "</span>";
}
?>

<!-- ===== SIDEBAR ADMIN ===== -->
<aside class="w-64 flex flex-col justify-between border-r border-surface-variant
              flex-shrink-0 z-20 fixed left-0 top-16 h-[calc(100vh-4rem)]
              bg-surface-container-lowest overflow-y-auto pb-4">

    <!-- Menu Navigasi -->
    <nav class="p-4 space-y-1">

        <!-- Dashboard -->
        <a href="<?= ADMIN_URL ?>/index.php"
           class="<?= sidebar_class('dashboard', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">dashboard</span>
            <span class="font-label-md text-label-md">Dashboard</span>
        </a>

        <!-- Divider -->
        <div class="pt-2 pb-1 px-4">
            <p class="text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                Operasional
            </p>
        </div>

        <!-- Armada -->
        <a href="<?= ADMIN_URL ?>/armada/index.php"
           class="<?= sidebar_class('armada', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">directions_car</span>
            <span class="font-label-md text-label-md">Armada</span>
        </a>

        <!-- Booking -->
        <a href="<?= ADMIN_URL ?>/booking/index.php"
           class="<?= sidebar_class('booking', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">event_available</span>
            <span class="font-label-md text-label-md">Booking</span>
            <?= badge($badge_booking + $badge_transaksi) ?>
        </a>

        <!-- Kalender -->
        <a href="<?= ADMIN_URL ?>/booking/kalender.php"
           class="<?= sidebar_class('kalender', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">calendar_month</span>
            <span class="font-label-md text-label-md">Kalender</span>
        </a>

        <!-- Divider -->
        <div class="pt-2 pb-1 px-4">
            <p class="text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                Manajemen
            </p>
        </div>

        <!-- Pelanggan -->
        <a href="<?= ADMIN_URL ?>/pelanggan/index.php"
           class="<?= sidebar_class('pelanggan', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">group</span>
            <span class="font-label-md text-label-md">Pelanggan</span>
        </a>

        <!-- Ulasan -->
        <a href="<?= ADMIN_URL ?>/ulasan/index.php"
           class="<?= sidebar_class('ulasan', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">star</span>
            <span class="font-label-md text-label-md">Ulasan</span>
            <?= badge($badge_ulasan) ?>
        </a>

        <!-- Divider -->
        <div class="pt-2 pb-1 px-4">
            <p class="text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                Laporan
            </p>
        </div>

        <!-- Laporan -->
        <a href="<?= ADMIN_URL ?>/laporan/index.php"
           class="<?= sidebar_class('laporan', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">bar_chart</span>
            <span class="font-label-md text-label-md">Laporan & Analitik</span>
        </a>

        <!-- Divider -->
        <div class="pt-2 pb-1 px-4">
            <p class="text-[10px] font-bold tracking-widest text-on-surface-variant/50 uppercase">
                Sistem
            </p>
        </div>

        <!-- Staff -->
        <a href="<?= ADMIN_URL ?>/staff/index.php"
           class="<?= sidebar_class('staff', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">badge</span>
            <span class="font-label-md text-label-md">Staff</span>
        </a>

        <!-- Pengaturan -->
        <a href="<?= ADMIN_URL ?>/pengaturan/index.php"
           class="<?= sidebar_class('pengaturan', $menu_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">settings</span>
            <span class="font-label-md text-label-md">Pengaturan</span>
        </a>

    </nav>

    <!-- Bagian Bawah: Link ke Website + Logout -->
    <div class="p-4 border-t border-surface-variant space-y-1">
        <!-- Link ke website user -->
        <a href="<?= BASE_URL ?>/beranda.php" target="_blank"
           class="flex items-center px-4 py-2.5 rounded-lg text-on-surface-variant
                  hover:bg-surface-container-low transition-colors">
            <span class="material-symbols-outlined mr-3 text-[20px]">open_in_new</span>
            <span class="font-label-md text-label-md">Lihat Website</span>
        </a>

        <!-- Logout -->
        <a href="<?= ADMIN_URL ?>/logout.php"
           class="flex items-center px-4 py-2.5 rounded-lg text-error
                  hover:bg-error-container transition-colors">
            <span class="material-symbols-outlined mr-3 text-[20px]">logout</span>
            <span class="font-label-md text-label-md">Keluar</span>
        </a>
    </div>
</aside>
