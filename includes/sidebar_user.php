<?php
// ============================================================
// includes/sidebar_user.php
// Sidebar untuk halaman PROFIL USER (pesanan, ulasan, dll)
// Variabel: $menu_user_aktif = 'profil'|'pesanan'|'ulasan'|dst
// ============================================================

require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user = get_user_login();
$menu_user_aktif = $menu_user_aktif ?? '';

function sidebar_user_class(string $menu, string $aktif): string {
    if ($menu === $aktif) {
        return 'flex items-center px-4 py-3 rounded-lg font-bold
                border-r-4 border-secondary-container bg-surface-container-low
                text-primary transition-all';
    }
    return 'flex items-center px-4 py-3 rounded-lg
            text-on-surface-variant hover:bg-surface-container-low
            hover:text-on-surface transition-colors';
}

// Hitung pesanan aktif
$pesanan_aktif = 0;
try {
    global $pdo;
    if (!isset($pdo)) require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND status IN ('pending','confirmed','berlangsung')");
    $stmt->execute([$user['id']]);
    $pesanan_aktif = (int) $stmt->fetchColumn();
} catch (Exception $e) {}
?>

<!-- ===== SIDEBAR USER ===== -->
<aside class="h-screen w-64 fixed left-0 top-0 bg-surface
              shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
              z-50 flex flex-col py-base hidden md:flex mt-16
              border-r border-outline-variant/30">

    <!-- Avatar & Info User -->
    <div class="py-6 px-gutter border-b border-outline-variant/30 mb-4 flex flex-col items-center">
        <!-- Foto Profil -->
        <div class="w-20 h-20 rounded-full bg-surface-container-highest
                    shadow-sm border-2 border-primary overflow-hidden mb-4">
            <?php if ($user['foto']): ?>
                <img src="<?= url_gambar($user['foto'], 'profil') ?>"
                     alt="Foto Profil" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center
                            bg-primary text-on-primary text-2xl font-bold">
                    <?= inisial($user['nama']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nama User -->
        <h2 class="font-headline-md text-headline-md font-bold tracking-tight
                   text-primary uppercase text-center w-full truncate text-sm">
            <?= htmlspecialchars($user['nama']) ?>
        </h2>
        <p class="font-label-sm text-label-sm text-on-surface-variant mt-1
                  text-center uppercase tracking-widest text-secondary">
            Premium Member
        </p>
    </div>

    <!-- Menu Navigasi User -->
    <nav class="flex-1 px-4 space-y-1 overflow-y-auto">

        <a href="<?= BASE_URL ?>/user/profil.php"
           class="<?= sidebar_user_class('profil', $menu_user_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">person</span>
            <span class="font-label-md text-label-md">Profil Saya</span>
        </a>

        <a href="<?= BASE_URL ?>/user/pesanan.php"
           class="<?= sidebar_user_class('pesanan', $menu_user_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">receipt_long</span>
            <span class="font-label-md text-label-md">Pesanan Saya</span>
            <?php if ($pesanan_aktif > 0): ?>
                <span class="ml-auto min-w-[20px] h-5 bg-secondary-container text-on-secondary-container
                             text-[10px] font-bold rounded-full flex items-center justify-center px-1">
                    <?= $pesanan_aktif ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/user/riwayat_pembayaran.php"
           class="<?= sidebar_user_class('pembayaran', $menu_user_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">payments</span>
            <span class="font-label-md text-label-md">Riwayat Pembayaran</span>
        </a>

        <a href="<?= BASE_URL ?>/user/ulasan_saya.php"
           class="<?= sidebar_user_class('ulasan', $menu_user_aktif) ?>">
            <span class="material-symbols-outlined mr-3 text-[20px]">star</span>
            <span class="font-label-md text-label-md">Ulasan Saya</span>
        </a>

        <hr class="my-2 border-outline-variant/30">

        <!-- Kembali ke Website -->
        <a href="<?= BASE_URL ?>/beranda.php"
           class="flex items-center px-4 py-3 rounded-lg text-on-surface-variant
                  hover:bg-surface-container-low transition-colors">
            <span class="material-symbols-outlined mr-3 text-[20px]">home</span>
            <span class="font-label-md text-label-md">Kembali ke Beranda</span>
        </a>

        <!-- Logout -->
        <a href="<?= BASE_URL ?>/auth/logout.php"
           class="flex items-center px-4 py-3 rounded-lg text-error
                  hover:bg-error-container transition-colors">
            <span class="material-symbols-outlined mr-3 text-[20px]">logout</span>
            <span class="font-label-md text-label-md">Keluar</span>
        </a>

    </nav>
</aside>
