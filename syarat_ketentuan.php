<?php
// ============================================================
// syarat_ketentuan.php — Halaman Syarat & Ketentuan (Statis)
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Syarat & Ketentuan';
$page_active = '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow">

    <!-- Hero -->
    <section class="bg-primary text-on-primary py-16 md:py-24">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop text-center">
            <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg mb-4">
                Syarat &amp; Ketentuan
            </h1>
            <p class="font-body-lg text-body-lg text-primary-fixed-dim">
                Berlaku sejak: 1 Januari 2026 · Versi 2.1
            </p>
        </div>
    </section>

    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12
                grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Sidebar Navigasi -->
        <aside class="lg:col-span-3 hidden lg:block">
            <nav class="sticky top-20 bg-surface-container-lowest rounded-2xl border
                        border-outline-variant/30 p-5">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-4">
                    Daftar Isi
                </p>
                <ul class="space-y-1">
                    <?php
                    $daftar_isi = [
                        'pendahuluan'    => '1. Pendahuluan',
                        'definisi'       => '2. Definisi',
                        'syarat-penyewa' => '3. Syarat Penyewa',
                        'pemesanan'      => '4. Pemesanan',
                        'pembayaran'     => '5. Pembayaran',
                        'pembatalan'     => '6. Pembatalan & Refund',
                        'penggunaan'     => '7. Penggunaan Kendaraan',
                        'larangan'       => '8. Larangan',
                        'kontak'         => '9. Hubungi Kami',
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

            <!-- Tentang Dokumen -->
            <div class="bg-secondary-fixed/20 rounded-xl p-5 flex items-start gap-3">
                <span class="material-symbols-outlined text-secondary shrink-0">info</span>
                <div>
                    <h4 class="font-headline-sm text-headline-sm text-primary mb-2">Tentang Dokumen Ini</h4>
                    <p class="text-on-surface-variant font-body-md text-body-md">
                        Harap baca syarat &amp; ketentuan ini dengan seksama sebelum menggunakan
                        layanan <?= APP_NAME ?>. Dengan mengakses dan menggunakan layanan kami,
                        Anda dianggap telah membaca, memahami, dan menyetujui seluruh isi dokumen ini.
                    </p>
                </div>
            </div>

            <section id="pendahuluan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    1. Pendahuluan
                </h2>
                <p class="font-body-md text-body-md mb-4 text-on-surface-variant leading-relaxed">
                    Syarat &amp; Ketentuan ini mengatur hubungan hukum antara Anda (selanjutnya disebut
                    "Penyewa") dengan <?= APP_NAME ?> sebagai penyedia layanan penyewaan kendaraan.
                    Dengan melakukan pemesanan, Penyewa menyetujui untuk terikat pada seluruh ketentuan
                    yang berlaku.
                </p>
            </section>

            <section id="definisi" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    2. Definisi
                </h2>
                <ul class="space-y-3 font-body-md text-body-md text-on-surface-variant">
                    <li><strong class="text-on-surface">Kendaraan:</strong> Mobil beserta aksesoris dan perlengkapan standar yang disewakan oleh <?= APP_NAME ?>.</li>
                    <li><strong class="text-on-surface">Periode Sewa:</strong> Jangka waktu penyewaan kendaraan sebagaimana disepakati dalam konfirmasi pemesanan.</li>
                    <li><strong class="text-on-surface">Biaya Sewa:</strong> Total biaya yang harus dibayar oleh Penyewa atas penyewaan kendaraan.</li>
                </ul>
            </section>

            <section id="syarat-penyewa" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    3. Syarat Penyewa
                </h2>
                <h3 class="font-label-md text-label-md font-bold mt-6 mb-3 text-primary">3.1 Identitas &amp; Usia</h3>
                <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant mb-4">
                    <?php foreach ([
                        'Penyewa harus berusia minimal 17 tahun.',
                        'Memiliki Kartu Tanda Penduduk (KTP) yang masih berlaku.',
                        'Memiliki Surat Izin Mengemudi (SIM A) yang masih berlaku.',
                    ] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-secondary mr-2 text-[20px]">check_circle</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <h3 class="font-label-md text-label-md font-bold mt-6 mb-3 text-primary">3.2 Dokumen Wajib</h3>
                <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant">
                    <?php foreach (['KTP Asli','SIM A Asli','Foto Selfie dengan KTP'] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-secondary mr-2 text-[20px]">check_circle</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section id="pemesanan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    4. Pemesanan
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Pemesanan dapat dilakukan melalui website, aplikasi, atau menghubungi tim kami secara langsung.
                    Pemesanan dianggap sah setelah Penyewa menerima konfirmasi resmi dan menyelesaikan pembayaran
                    Down Payment (DP).
                </p>
            </section>

            <section id="pembayaran" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    5. Pembayaran
                </h2>
                <h3 class="font-label-md text-label-md font-bold mb-3 text-primary">5.1 Metode Pembayaran</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                    Kami menerima pembayaran melalui Transfer Bank, Virtual Account (VA), E-Wallet terpilih,
                    dan Pembayaran Tunai (Cash) di kantor cabang.
                </p>
                <h3 class="font-label-md text-label-md font-bold mb-3 text-primary">5.2 Down Payment (DP)</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                    DP minimal sebesar <?= DP_PERSEN ?>% dari total Biaya Sewa wajib dibayarkan untuk mengamankan
                    reservasi kendaraan.
                </p>
                <h3 class="font-label-md text-label-md font-bold mb-3 text-primary">5.3 Pelunasan</h3>
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Sisa pembayaran wajib dilunasi paling lambat saat serah terima kendaraan.
                </p>
            </section>

            <section id="pembatalan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    6. Pembatalan &amp; Refund
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Pembatalan lebih dari 3x24 jam sebelum periode sewa akan mendapat pengembalian DP penuh.
                    Pembatalan kurang dari 1x24 jam dikenakan potongan sesuai kebijakan yang berlaku.
                </p>
            </section>

            <section id="penggunaan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    7. Penggunaan Kendaraan
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                    Kendaraan hanya boleh dikemudikan oleh Penyewa terdaftar atau sopir resmi dari <?= APP_NAME ?>.
                    Penyewa bertanggung jawab penuh atas kondisi kendaraan selama periode sewa.
                </p>
            </section>

            <section id="larangan" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    8. Larangan
                </h2>
                <ul class="space-y-3 font-body-md text-body-md text-on-surface-variant">
                    <?php foreach ([
                        'Menyewakan kembali (sub-lease) kendaraan kepada pihak ketiga.',
                        'Menggunakan kendaraan untuk tindakan ilegal atau melanggar hukum.',
                        'Merokok di dalam kendaraan. Denda pembersihan (detailing) akan dikenakan.',
                        'Membawa hewan peliharaan tanpa persetujuan tertulis sebelumnya.',
                    ] as $item): ?>
                        <li class="flex items-start">
                            <span class="material-symbols-outlined text-error mr-3 text-[24px]">cancel</span>
                            <?= $item ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section id="kontak" class="scroll-mt-24">
                <h2 class="font-headline-md text-headline-md text-primary mb-4 pb-2 border-b border-surface-variant">
                    9. Hubungi Kami
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                    Jika ada pertanyaan mengenai Syarat &amp; Ketentuan ini, silakan hubungi kami:
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
