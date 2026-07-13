<?php
// ============================================================
// includes/navbar.php
// Navigasi atas untuk semua halaman USER
// Variabel: $page_active = 'beranda'|'armada'|'ulasan'|'kontak'
// ============================================================

$user        = get_user_login();
$page_active = $page_active ?? '';

// Helper untuk kelas menu aktif vs tidak aktif
function nav_class(string $menu, string $aktif): string {
    if ($menu === $aktif) {
        return 'font-label-md text-label-md text-primary border-b-2 border-secondary-container pb-1 font-semibold';
    }
    return 'font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors';
}
?>

<!-- ===== NAVBAR USER ===== -->
<nav class="sticky top-0 w-full z-[100] bg-surface/90 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop flex justify-between items-center h-16">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>/beranda.php"
           class="text-headline-md font-headline-md font-bold tracking-tight text-primary hover:text-primary/80 transition-colors">
            LUXE DRIVE
        </a>

        <!-- Menu Desktop -->
        <div class="hidden md:flex items-center gap-8">
            <a href="<?= BASE_URL ?>/beranda.php"
               class="<?= nav_class('beranda', $page_active) ?>">
                Beranda
            </a>
            <a href="<?= BASE_URL ?>/daftar_armada.php"
               class="<?= nav_class('armada', $page_active) ?>">
                Armada
            </a>
            <a href="<?= BASE_URL ?>/testimonial_ulasan.php"
               class="<?= nav_class('ulasan', $page_active) ?>">
                Ulasan
            </a>
            <a href="<?= BASE_URL ?>/syarat_ketentuan.php"
               class="<?= nav_class('syarat', $page_active) ?>">
                Syarat & Ketentuan
            </a>
            <a href="<?= BASE_URL ?>/kontak.php"
               class="<?= nav_class('kontak', $page_active) ?>">
                Kontak
            </a>
        </div>

        <!-- Tombol Aksi -->
        <div class="hidden md:flex items-center gap-3">
            <?php if ($user): ?>
                <!-- User sudah login: tampil nama + dropdown -->
                <div class="relative group">
                    <button class="flex items-center gap-2 px-4 py-2 rounded-full border border-outline-variant
                                   hover:bg-surface-container transition-colors font-label-md text-label-md text-on-surface">
                        <?php if ($user['foto']): ?>
                            <img src="<?= url_gambar($user['foto'], 'profil') ?>"
                                 alt="Foto" class="w-7 h-7 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center text-on-primary text-xs font-bold">
                                <?= inisial($user['nama']) ?>
                            </div>
                        <?php endif; ?>
                        <span><?= htmlspecialchars(explode(' ', $user['nama'])[0]) ?></span>
                        <span class="material-symbols-outlined text-sm">expand_more</span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 top-full mt-2 w-52 bg-surface-container-lowest rounded-xl
                                shadow-lg border border-outline-variant/30 opacity-0 invisible
                                group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="p-2">
                            <a href="<?= BASE_URL ?>/user/profil.php"
                               class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-surface-container
                                      text-on-surface font-label-md text-label-md transition-colors">
                                <span class="material-symbols-outlined text-[20px] text-on-surface-variant">person</span>
                                Profil Saya
                            </a>
                            <a href="<?= BASE_URL ?>/user/pesanan.php"
                               class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-surface-container
                                      text-on-surface font-label-md text-label-md transition-colors">
                                <span class="material-symbols-outlined text-[20px] text-on-surface-variant">receipt_long</span>
                                Pesanan Saya
                            </a>
                            <a href="<?= BASE_URL ?>/user/ulasan_saya.php"
                               class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-surface-container
                                      text-on-surface font-label-md text-label-md transition-colors">
                                <span class="material-symbols-outlined text-[20px] text-on-surface-variant">star</span>
                                Ulasan Saya
                            </a>
                            <hr class="my-1 border-outline-variant/30">
                            <a href="<?= BASE_URL ?>/auth/logout.php"
                               class="flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-error-container
                                      text-error font-label-md text-label-md transition-colors">
                                <span class="material-symbols-outlined text-[20px]">logout</span>
                                Keluar
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- User belum login -->
                <a href="<?= BASE_URL ?>/auth/masuk.php"
                   class="font-label-md text-label-md text-on-surface-variant hover:text-primary transition-colors">
                    Masuk
                </a>
                <a href="<?= BASE_URL ?>/auth/daftar.php"
                   class="bg-primary text-on-primary px-6 py-2 rounded-full font-label-md text-label-md
                          hover:bg-primary/90 transition-all">
                    Daftar
                </a>
            <?php endif; ?>
        </div>

        <!-- Hamburger Mobile -->
        <button id="btn-hamburger"
                class="md:hidden p-2 text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined" id="icon-hamburger">menu</span>
        </button>
    </div>

    <!-- Menu Mobile (tersembunyi default) -->
    <div id="mobile-menu"
         class="hidden md:hidden bg-surface border-t border-outline-variant/30 px-margin-mobile py-4">
        <div class="flex flex-col gap-1">
            <a href="<?= BASE_URL ?>/beranda.php"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                      font-label-md text-label-md <?= $page_active === 'beranda' ? 'text-primary font-semibold bg-surface-container-low' : 'text-on-surface-variant' ?>">
                <span class="material-symbols-outlined text-[20px]">home</span>
                Beranda
            </a>
            <a href="<?= BASE_URL ?>/daftar_armada.php"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                      font-label-md text-label-md <?= $page_active === 'armada' ? 'text-primary font-semibold bg-surface-container-low' : 'text-on-surface-variant' ?>">
                <span class="material-symbols-outlined text-[20px]">directions_car</span>
                Armada
            </a>
            <a href="<?= BASE_URL ?>/testimonial_ulasan.php"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                      font-label-md text-label-md <?= $page_active === 'ulasan' ? 'text-primary font-semibold bg-surface-container-low' : 'text-on-surface-variant' ?>">
                <span class="material-symbols-outlined text-[20px]">star</span>
                Ulasan
            </a>
            <a href="<?= BASE_URL ?>/syarat_ketentuan.php"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                      font-label-md text-label-md <?= $page_active === 'syarat' ? 'text-primary font-semibold bg-surface-container-low' : 'text-on-surface-variant' ?>">
                <span class="material-symbols-outlined text-[20px]">description</span>
                Syarat & Ketentuan
            </a>
            <a href="<?= BASE_URL ?>/kontak.php"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                      font-label-md text-label-md <?= $page_active === 'kontak' ? 'text-primary font-semibold bg-surface-container-low' : 'text-on-surface-variant' ?>">
                <span class="material-symbols-outlined text-[20px]">call</span>
                Kontak
            </a>

            <hr class="my-2 border-outline-variant/30">

            <?php if ($user): ?>
                <a href="<?= BASE_URL ?>/user/profil.php"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                          font-label-md text-label-md text-on-surface-variant">
                    <span class="material-symbols-outlined text-[20px]">person</span>
                    Profil Saya
                </a>
                <a href="<?= BASE_URL ?>/user/pesanan.php"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container
                          font-label-md text-label-md text-on-surface-variant">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                    Pesanan Saya
                </a>
                <a href="<?= BASE_URL ?>/auth/logout.php"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-error-container
                          font-label-md text-label-md text-error">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    Keluar
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/masuk.php"
                   class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-primary
                          font-label-md text-label-md text-primary hover:bg-surface-container transition-colors">
                    Masuk
                </a>
                <a href="<?= BASE_URL ?>/auth/daftar.php"
                   class="flex items-center justify-center gap-2 px-4 py-3 rounded-full bg-primary
                          font-label-md text-label-md text-on-primary hover:bg-primary/90 transition-colors">
                    Daftar Sekarang
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Toggle hamburger menu mobile
    document.getElementById('btn-hamburger').addEventListener('click', function () {
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('icon-hamburger');
        menu.classList.toggle('hidden');
        icon.textContent = menu.classList.contains('hidden') ? 'menu' : 'close';
    });
</script>
