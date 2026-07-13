<?php
// ============================================================
// faq.php — Halaman Pertanyaan Umum (Statis)
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'FAQ';
$page_active = '';

// Data FAQ dikelompokkan per kategori
$faq_data = [
    'Pemesanan' => [
        ['Bagaimana cara menyewa mobil di ' . APP_NAME . '?',
         'Pilih mobil di halaman Armada, klik "Pesan", isi tanggal dan data diri, lalu selesaikan pembayaran. Kendaraan akan diantar ke lokasi Anda sesuai jadwal.'],
        ['Apakah bisa memesan mobil untuk hari yang sama?',
         'Bisa, selama kendaraan tersedia. Namun kami sarankan memesan minimal 1 hari sebelumnya untuk memastikan ketersediaan dan proses verifikasi.'],
        ['Apakah harus punya akun untuk memesan?',
         'Ya, Anda perlu mendaftar akun terlebih dahulu agar dapat melakukan pemesanan dan memantau status sewa Anda.'],
    ],
    'Pembayaran' => [
        ['Metode pembayaran apa saja yang tersedia?',
         'Kami menerima Transfer Bank, Virtual Account, E-Wallet (GoPay, OVO, Dana), dan Bayar di Tempat (COD).'],
        ['Berapa DP (uang muka) yang harus dibayar?',
         'DP minimal ' . DP_PERSEN . '% dari total biaya sewa untuk mengamankan reservasi. Sisanya dilunasi saat serah terima kendaraan.'],
        ['Bagaimana cara upload bukti pembayaran?',
         'Setelah checkout, Anda akan diarahkan ke halaman pembayaran. Upload foto bukti transfer di sana, lalu tim kami akan memverifikasi dalam waktu singkat.'],
    ],
    'Kendaraan & Sopir' => [
        ['Apakah tersedia sewa dengan sopir?',
         'Ya, sebagian besar armada kami tersedia dengan opsi sopir profesional. Biaya sopir ditambahkan per hari saat pemesanan.'],
        ['Apakah harga sudah termasuk BBM?',
         'Harga sewa belum termasuk bahan bakar. Pengisian BBM menjadi tanggung jawab penyewa, kecuali disepakati lain.'],
        ['Apakah mobil sudah diasuransikan?',
         'Ya, setiap penyewaan sudah termasuk biaya asuransi dasar sebesar ' . format_rupiah(BIAYA_ASURANSI) . ' untuk perlindungan selama masa sewa.'],
    ],
    'Pembatalan' => [
        ['Bagaimana kebijakan pembatalan?',
         'Pembatalan lebih dari 3x24 jam sebelum sewa mendapat refund DP penuh. Kurang dari itu dikenakan potongan sesuai kebijakan.'],
        ['Bagaimana jika saya ingin memperpanjang sewa?',
         'Hubungi tim kami via WhatsApp minimal 1 hari sebelum masa sewa berakhir. Perpanjangan tergantung ketersediaan kendaraan.'],
    ],
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow">

    <!-- Hero -->
    <section class="bg-primary text-on-primary py-16 md:py-20">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop text-center">
            <h1 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg mb-4">
                Pertanyaan Umum
            </h1>
            <p class="font-body-lg text-body-lg text-primary-fixed-dim max-w-2xl mx-auto">
                Temukan jawaban atas pertanyaan yang sering diajukan seputar layanan <?= APP_NAME ?>.
            </p>
        </div>
    </section>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-12 space-y-10">
        <?php foreach ($faq_data as $kategori => $items): ?>
            <section>
                <h2 class="font-headline-md text-headline-md text-primary mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">help</span>
                    <?= $kategori ?>
                </h2>
                <div class="space-y-3">
                    <?php foreach ($items as [$tanya, $jawab]): ?>
                        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30
                                    overflow-hidden">
                            <button type="button"
                                    class="faq-toggle w-full flex items-center justify-between gap-4
                                           p-5 text-left hover:bg-surface-container-low transition-colors">
                                <span class="font-label-md text-label-md text-on-surface font-semibold">
                                    <?= htmlspecialchars($tanya) ?>
                                </span>
                                <span class="material-symbols-outlined text-primary shrink-0 faq-icon transition-transform">
                                    expand_more
                                </span>
                            </button>
                            <div class="faq-content hidden px-5 pb-5">
                                <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                                    <?= htmlspecialchars($jawab) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <!-- CTA -->
        <div class="bg-primary rounded-2xl p-8 text-center">
            <h2 class="font-headline-sm text-headline-sm text-on-primary mb-3">
                Masih Punya Pertanyaan?
            </h2>
            <p class="font-body-md text-body-md text-on-primary-container mb-6">
                Tim kami siap membantu Anda 24/7.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <a href="https://wa.me/<?= APP_WHATSAPP ?>" target="_blank"
                   class="inline-flex items-center justify-center gap-2 bg-secondary-container
                          text-on-secondary-container px-6 py-3 rounded-full font-label-md text-label-md
                          font-bold hover:bg-secondary-container/90 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                    Chat WhatsApp
                </a>
                <a href="<?= BASE_URL ?>/kontak.php"
                   class="inline-flex items-center justify-center gap-2 border border-on-primary/30
                          text-on-primary px-6 py-3 rounded-full font-label-md text-label-md
                          hover:bg-on-primary/10 transition-colors">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    document.querySelectorAll('.faq-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const content = btn.nextElementSibling;
            const icon    = btn.querySelector('.faq-icon');
            const isOpen  = !content.classList.contains('hidden');
            // Tutup semua
            document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.faq-icon').forEach(i => i.style.transform = '');
            // Buka yang diklik (jika tadinya tertutup)
            if (!isOpen) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
