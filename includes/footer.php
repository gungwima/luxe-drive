<?php
// ============================================================
// includes/footer.php
// Footer untuk semua halaman USER
// ============================================================
?>

<!-- ===== FOOTER USER ===== -->
<footer class="bg-primary-container w-full mt-auto">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12 md:py-16
                grid grid-cols-1 md:grid-cols-3 gap-12 md:gap-gutter">

        <!-- Kolom Kiri: Brand -->
        <div class="flex flex-col gap-6">
            <h2 class="text-headline-md font-headline-md font-bold tracking-tight text-on-primary">
                LUXE DRIVE
            </h2>
            <p class="text-body-md font-body-md text-on-primary-container opacity-80 max-w-xs">
                Menyediakan pengalaman berkendara premium terbaik dengan armada kendaraan pilihan
                untuk kenyamanan perjalanan Anda.
            </p>
            <!-- Sosial Media -->
            <div class="flex gap-3">
                <a href="#" title="Instagram"
                   class="w-10 h-10 rounded-full bg-primary flex items-center justify-center
                          text-secondary-container hover:bg-primary/80 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">photo_camera</span>
                </a>
                <a href="#" title="Facebook"
                   class="w-10 h-10 rounded-full bg-primary flex items-center justify-center
                          text-secondary-container hover:bg-primary/80 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">public</span>
                </a>
                <a href="https://wa.me/<?= APP_WHATSAPP ?>" title="WhatsApp" target="_blank"
                   class="w-10 h-10 rounded-full bg-primary flex items-center justify-center
                          text-secondary-container hover:bg-primary/80 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                </a>
                <a href="mailto:<?= APP_EMAIL ?>" title="Email"
                   class="w-10 h-10 rounded-full bg-primary flex items-center justify-center
                          text-secondary-container hover:bg-primary/80 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                </a>
            </div>
        </div>

        <!-- Kolom Tengah: Menu Cepat -->
        <div class="flex flex-col gap-6">
            <h3 class="text-label-sm font-label-sm font-bold text-on-primary tracking-widest uppercase">
                PERUSAHAAN
            </h3>
            <ul class="flex flex-col gap-3">
                <li>
                    <a href="<?= BASE_URL ?>/beranda.php"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        Beranda
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/daftar_armada.php"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        Armada
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/testimonial_ulasan.php"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        Ulasan
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/kontak.php"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        Kontak
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/syarat_ketentuan.php"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        Syarat &amp; Ketentuan
                    </a>
                </li>
            </ul>
        </div>

        <!-- Kolom Kanan: Kontak -->
        <div class="flex flex-col gap-6">
            <h3 class="text-label-sm font-label-sm font-bold text-on-primary tracking-widest uppercase">
                HUBUNGI KAMI
            </h3>
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-secondary-container mt-0.5 shrink-0">location_on</span>
                    <p class="text-body-md font-body-md text-on-primary-container opacity-80">
                        <?= APP_ADDRESS ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container shrink-0">call</span>
                    <a href="tel:<?= APP_PHONE ?>"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        <?= APP_PHONE ?>
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container shrink-0">chat</span>
                    <a href="https://wa.me/<?= APP_WHATSAPP ?>" target="_blank"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        WhatsApp
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-secondary-container shrink-0">mail</span>
                    <a href="mailto:<?= APP_EMAIL ?>"
                       class="text-body-md font-body-md text-on-primary-container opacity-80
                              hover:text-on-primary hover:opacity-100 transition-colors">
                        <?= APP_EMAIL ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-on-primary/10">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-4
                    flex flex-col md:flex-row justify-between items-center gap-2">
            <p class="text-label-sm font-label-sm text-on-primary-container opacity-60">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </p>
            <p class="text-label-sm font-label-sm text-on-primary-container opacity-60">
                Premium Car Rental Experience
            </p>
        </div>
    </div>
</footer>

<!-- Main JS -->
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
