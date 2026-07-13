<?php
// ============================================================
// kebijakan_privasi.php — Halaman Kebijakan Privasi (Statis)
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Kebijakan Privasi';
$page_active = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow">

    <!-- Hero -->
    <section class="bg-primary text-on-primary py-16 md:py-24">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop text-center">
            <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg mb-4">
                Kebijakan Privasi
            </h1>
            <p class="font-body-lg text-body-lg text-primary-fixed-dim">
                Berlaku sejak: 1 Januari 2026 · Versi 1.0
            </p>
        </div>
    </section>

    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12
                grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Sidebar -->
        <aside class="lg:col-span-3 hidden lg:block">
            <nav class="sticky top-20 bg-surface-container-lowest rounded-2xl border
                        border-outline-variant/30 p-5">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-4">
                    Daftar Isi
                </p>
                <ul class="space-y-1">
                    <?php
                    $daftar_isi = [
                        'pendahuluan'  => '1. Pendahuluan',
                        'data-kami'    => '2. Data yang Kami Kumpulkan',
                        'penggunaan'   => '3. Penggunaan Data',
                        'keamanan'     => '4. Keamanan Data',
                        'berbagi'      => '5. Berbagi Data',
                        'hak-anda'     => '6. Hak Anda',
                        'cookie'       => '7. Cookie',
                        'kontak'       => '8. Hubungi Kami',
                    ];
                    foreach ($daftar_isi as $anchor => $judul): ?>
                        <li>
                            <a href="#<?= $anchor ?>"
                               class="block px-3 py-2 rounded-lg font-label-md text-label-md
                                      text-on-surface-variant hover:bg-surface-container
                                      hover:text-primary transition-colors">
                                <?= $judul ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <!-- Konten -->
        <article class="lg:col-span-9 bg-surface-container-lowest rounded-2xl border
                        border-outline-variant/30 p-6 md:p-10 space-y-10">

            <div class="bg-secondary-fixed/20 rounded-xl p-5 flex items-start gap-3">
                <span class="material-symbols-outlined text-secondary shrink-0">shield</span>
                <div>
                    <h4 class="font-headline-sm text-headline-sm text-primary mb-2">Komitmen Kami</h4>
                    <p class="text-on-surface-variant font-body-md text-body-md">
                        <?= APP_NAME ?> menghargai privasi Anda. Kebijakan ini menjelaskan bagaimana kami
                        mengumpulkan, menggunakan, dan melindungi data pribadi Anda saat menggunakan layanan kami.
                    </p>
                </div>
            </div>

            <section id="pendahuluan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    1. Pendahuluan
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Dengan menggunakan layanan <?= APP_NAME ?>, Anda menyetujui pengumpulan dan penggunaan
                    informasi sesuai dengan kebijakan ini. Kami berkomitmen menjaga kerahasiaan dan keamanan
                    data pribadi setiap pelanggan.
                </p>
            </section>

            <section id="data-kami" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    2. Data yang Kami Kumpulkan
                </h2>
                <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant">
                    <?php foreach ([
                        'Data identitas: nama lengkap, nomor KTP, dan nomor SIM.',
                        'Data kontak: alamat email dan nomor telepon/WhatsApp.',
                        'Data dokumen: foto KTP dan SIM untuk verifikasi.',
                        'Data transaksi: riwayat pemesanan dan pembayaran.',
                    ] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-secondary mr-2 text-[20px]">check_circle</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section id="penggunaan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    3. Penggunaan Data
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed mb-3">
                    Data yang kami kumpulkan digunakan untuk:
                </p>
                <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant">
                    <?php foreach ([
                        'Memproses pemesanan dan verifikasi identitas penyewa.',
                        'Mengirim konfirmasi booking dan notifikasi status sewa.',
                        'Meningkatkan kualitas layanan dan pengalaman pengguna.',
                        'Memenuhi kewajiban hukum yang berlaku.',
                    ] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-secondary mr-2 text-[20px]">arrow_right</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section id="keamanan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    4. Keamanan Data
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Kami menerapkan langkah keamanan teknis dan organisasi untuk melindungi data Anda dari akses
                    tidak sah, kehilangan, atau penyalahgunaan. Kata sandi disimpan dalam bentuk terenkripsi dan
                    data sensitif hanya diakses oleh staf berwenang.
                </p>
            </section>

            <section id="berbagi" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    5. Berbagi Data
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Kami <strong class="text-on-surface">tidak menjual atau menyewakan</strong> data pribadi Anda
                    kepada pihak ketiga. Data hanya dibagikan jika diwajibkan oleh hukum atau diperlukan untuk
                    menyelesaikan transaksi sewa (misalnya kepada sopir yang ditugaskan).
                </p>
            </section>

            <section id="hak-anda" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    6. Hak Anda
                </h2>
                <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant">
                    <?php foreach ([
                        'Mengakses dan memperbarui data pribadi Anda melalui halaman profil.',
                        'Meminta penghapusan akun dan data pribadi Anda.',
                        'Menolak penggunaan data untuk keperluan pemasaran.',
                    ] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-secondary mr-2 text-[20px]">verified_user</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section id="cookie" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    7. Cookie
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Website kami menggunakan cookie untuk menjaga sesi login dan meningkatkan pengalaman
                    penggunaan. Anda dapat menonaktifkan cookie melalui pengaturan browser, namun beberapa
                    fitur mungkin tidak berfungsi optimal.
                </p>
            </section>

            <section id="kontak" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    8. Hubungi Kami
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                    Untuk pertanyaan terkait kebijakan privasi atau permintaan data, hubungi kami:
                </p>
                <div class="bg-surface-container-low rounded-xl p-5 space-y-2">
                    <p class="flex items-center gap-2 font-body-md text-body-md text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[20px]">mail</span>
                        <?= APP_EMAIL ?>
                    </p>
                    <p class="flex items-center gap-2 font-body-md text-body-md text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[20px]">call</span>
                        <?= APP_PHONE ?>
                    </p>
                    <p class="flex items-center gap-2 font-body-md text-body-md text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[20px]">location_on</span>
                        <?= APP_ADDRESS ?>
                    </p>
                </div>
            </section>
        </article>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
