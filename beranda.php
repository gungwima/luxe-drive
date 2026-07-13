<?php
// ============================================================
// beranda.php — Halaman Utama / Landing Page
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Beranda';
$page_active = 'beranda';

// ── Ambil mobil populer (rating tertinggi, status tersedia) ──
try {
    $stmt = $pdo->query("
        SELECT m.*, 
               COALESCE(AVG(u.rating), 0)  AS avg_rating,
               COUNT(DISTINCT u.id)         AS jml_ulasan,
               COUNT(DISTINCT b.id)         AS jml_sewa
        FROM   mobil m
        LEFT JOIN ulasan  u ON u.mobil_id = m.id AND u.status = 'approved'
        LEFT JOIN booking b ON b.mobil_id = m.id AND b.status = 'selesai'
        WHERE  m.status = 'tersedia'
        GROUP  BY m.id
        ORDER  BY avg_rating DESC, jml_sewa DESC
        LIMIT  8
    ");
    $mobil_populer = $stmt->fetchAll();
} catch (PDOException $e) {
    $mobil_populer = [];
}

// ── Ambil total statistik ──
try {
    $total_pelanggan = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_armada    = (int) $pdo->query("SELECT COUNT(*) FROM mobil WHERE status != 'nonaktif'")->fetchColumn();
    $total_selesai   = (int) $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'selesai'")->fetchColumn();
    $avg_rating      = (float) $pdo->query("SELECT COALESCE(AVG(rating),0) FROM ulasan WHERE status='approved'")->fetchColumn();
} catch (PDOException $e) {
    $total_pelanggan = 1000; $total_armada = 50;
    $total_selesai   = 5000; $avg_rating   = 4.8;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow">

    <!-- ══════════════════════════════════════════════════════
         HERO SECTION
    ══════════════════════════════════════════════════════ -->
    <section class="relative bg-primary-container text-on-primary min-h-[600px]
                    flex items-center justify-center overflow-hidden">

        <!-- Background Image -->
        <div class="absolute inset-0 bg-cover bg-center mix-blend-overlay opacity-80"
             style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBdn8Rmg4NtJt9GlgRU0OF0bVmXeAw3oEVXm5aHNU_jTNipLhBHPBLm9fOPEzgEoG5bEV8rWZVruqwerZxBsJOTWW0rQ9s3iRntu74IwKX6XON8ehQxVALc2uEm2Xm0kI1Wbeyy_pOtyI141tJ7VeexcmDR2G4G6KM6QeFe9xyAzzlmvPzrlgLFYmsecof_9OSdtbblCgexgR5ooNtkf4MDm-WO5heawQ41Vox1jb5cr-SQxUHb6BRidaUnl9au4rp97UKXHnScOI0')">
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-primary/60 via-primary/40 to-primary/80"></div>

        <div class="relative z-10 max-w-container-max mx-auto
                    px-margin-mobile md:px-margin-desktop text-center pb-24">
            <h1 class="font-display-lg-mobile md:font-display-lg
                        text-display-lg-mobile md:text-display-lg
                        mb-6 max-w-3xl mx-auto drop-shadow-md">
                Sewa Mobil Mudah &amp; Terpercaya
            </h1>
            <p class="font-body-lg text-body-lg text-on-primary-container mb-10 max-w-2xl mx-auto">
                Rasakan pengalaman premium dengan armada terbaik kami.
                Solusi perjalanan bisnis dan liburan Anda.
            </p>
            <a href="<?= BASE_URL ?>/daftar_armada.php"
               class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container
                      px-8 py-4 rounded-full font-label-md text-label-md font-bold
                      hover:bg-secondary-container/90 transition-all shadow-lg
                      active:scale-[0.98]">
                <span class="material-symbols-outlined">directions_car</span>
                Lihat Armada Kami
            </a>
        </div>

        <!-- Form Pencarian Cepat -->
        <!-- Mobile: statis di bawah hero (relative). Desktop: melayang (absolute) -->
        <!-- <div class="relative md:absolute md:bottom-0 md:translate-y-1/2 left-0 right-0
                    px-margin-mobile md:px-margin-desktop z-20 mt-8 md:mt-0">
            <form action="<?= BASE_URL ?>/daftar_armada.php" method="GET"
                  class="max-w-5xl mx-auto bg-surface-container-lowest rounded-2xl
                         shadow-[0px_8px_32px_rgba(26,43,60,0.16)]
                         border border-outline-variant/30
                         p-4 md:p-6 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                <div class="flex flex-col gap-1">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">
                        Jenis Mobil
                    </label>
                    <div class="relative">
                        <select name="jenis"
                                class="w-full appearance-none bg-surface border border-outline-variant
                                       rounded-xl py-3 pl-4 pr-10 font-label-md text-label-md
                                       text-on-surface focus:outline-none focus:ring-2
                                       focus:ring-primary focus:border-primary transition-all">
                            <option value="">Semua Jenis</option>
                            <option value="MPV">MPV</option>
                            <option value="SUV">SUV</option>
                            <option value="City Car">City Car</option>
                            <option value="Minibus">Minibus</option>
                            <option value="Sedan">Sedan</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2
                                     -translate-y-1/2 text-on-surface-variant pointer-events-none text-sm">
                            expand_more
                        </span>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">
                        Tanggal Ambil
                    </label>
                    <input type="date" name="tgl_ambil"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= date('Y-m-d') ?>"
                           class="w-full border border-outline-variant rounded-xl py-3 px-4
                                  font-label-md text-label-md text-on-surface bg-surface
                                  focus:outline-none focus:ring-2 focus:ring-primary
                                  focus:border-primary transition-all">
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">
                        Tanggal Kembali
                    </label>
                    <input type="date" name="tgl_kembali"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           class="w-full border border-outline-variant rounded-xl py-3 px-4
                                  font-label-md text-label-md text-on-surface bg-surface
                                  focus:outline-none focus:ring-2 focus:ring-primary
                                  focus:border-primary transition-all">
                </div>

                <button type="submit"
                        class="flex items-center justify-center gap-2 bg-primary text-on-primary
                               rounded-xl py-3 px-6 font-label-md text-label-md font-bold
                               hover:bg-primary/90 transition-all shadow-md active:scale-[0.98]
                               mt-auto">
                    <span class="material-symbols-outlined">search</span>
                    Cari Mobil
                </button>
            </form>
        </div> -->
    </section>

    <!-- ══════════════════════════════════════════════════════
         KENAPA PILIH KAMI
    ══════════════════════════════════════════════════════ -->
    <section class="pt-16 md:pt-36 pb-16 px-margin-mobile md:px-margin-desktop bg-surface">
        <div class="max-w-container-max mx-auto">
            <div class="text-center mb-12">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-4">
                    Mengapa Memilih Kami?
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto">
                    Komitmen kami untuk memberikan layanan penyewaan mobil yang aman,
                    nyaman, dan tanpa repot.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $fitur = [
                    ['sell',          'Harga Terbaik',      'Transparan tanpa biaya tersembunyi. Dapatkan nilai terbaik untuk perjalanan Anda.'],
                    ['verified_user', 'Armada Terawat',     'Setiap kendaraan melewati inspeksi ketat. Bersih, nyaman, dan siap jalan.'],
                    ['support_agent', 'Dukungan 24/7',      'Tim kami selalu siap membantu Anda kapanpun dan dimanapun Anda membutuhkan.'],
                ];
                foreach ($fitur as [$ikon, $judul, $desk]): ?>
                    <div class="bg-surface-container-low p-8 rounded-2xl shadow-sm
                                flex flex-col items-center text-center
                                transition-all hover:-translate-y-1 hover:shadow-md">
                        <div class="w-16 h-16 bg-primary/10 rounded-full
                                    flex items-center justify-center mb-6">
                            <span class="material-symbols-outlined text-primary text-[32px]"
                                  style="font-variation-settings:'FILL' 1">
                                <?= $ikon ?>
                            </span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface mb-3">
                            <?= $judul ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            <?= $desk ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         STATISTIK
    ══════════════════════════════════════════════════════ -->
    <section class="py-16 bg-primary">
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop
                    grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <?php
            $stats = [
                [$total_selesai . '+', 'Perjalanan Selesai'],
                [$total_pelanggan . '+', 'Pelanggan Puas'],
                [$total_armada . '+',   'Armada Pilihan'],
                [number_format($avg_rating, 1) . '★', 'Rating Rata-rata'],
            ];
            foreach ($stats as [$nilai, $label]): ?>
                <div class="flex flex-col gap-2">
                    <span class="font-display-lg-mobile text-display-lg-mobile
                                 text-secondary-container font-bold"
                          data-count="<?= preg_replace('/[^0-9]/', '', $nilai) ?>">
                        <?= htmlspecialchars($nilai) ?>
                    </span>
                    <span class="font-label-md text-label-md text-on-primary-container">
                        <?= $label ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         ARMADA POPULER
    ══════════════════════════════════════════════════════ -->
    <section class="py-16 px-margin-mobile md:px-margin-desktop bg-background">
        <div class="max-w-container-max mx-auto">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface mb-2">
                        Armada Populer
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Pilihan terfavorit pelanggan kami
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/daftar_armada.php"
                   class="hidden md:flex items-center gap-1 font-label-md text-label-md
                          text-primary hover:text-secondary-container transition-colors">
                    Lihat Semua
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <?php if (empty($mobil_populer)): ?>
                <div class="text-center py-12 text-on-surface-variant">
                    <span class="material-symbols-outlined text-5xl mb-4 block">directions_car</span>
                    <p class="font-body-md text-body-md">Belum ada armada tersedia saat ini.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($mobil_populer as $mobil): ?>
                        <a href="<?= BASE_URL ?>/detail_mobil.php?id=<?= $mobil['id'] ?>"
                           class="bg-surface-container-lowest rounded-2xl overflow-hidden
                                  border border-outline-variant/30
                                  shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                                  hover:shadow-[0px_8px_32px_rgba(26,43,60,0.16)]
                                  hover:-translate-y-1 transition-all group block">

                            <!-- Foto -->
                            <div class="relative w-full aspect-[4/3] overflow-hidden bg-surface-container">
                                <img src="<?= url_foto_mobil($mobil['foto_utama']) ?>"
                                     alt="<?= htmlspecialchars($mobil['nama']) ?>"
                                     class="w-full h-full object-cover
                                            group-hover:scale-105 transition-transform duration-500">
                                <!-- Badge Status -->
                                <div class="absolute top-3 right-3">
                                    <?= badge_status_mobil($mobil['status']) ?>
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="p-5">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-headline-sm text-headline-sm text-on-surface
                                               group-hover:text-primary transition-colors">
                                        <?= htmlspecialchars($mobil['nama']) ?>
                                    </h3>
                                </div>

                                <!-- Rating & Ulasan -->
                                <div class="flex items-center gap-1 mb-3">
                                    <?= tampil_bintang((float)$mobil['avg_rating']) ?>
                                    <span class="font-label-sm text-label-sm text-on-surface-variant ml-1">
                                        (<?= (int)$mobil['jml_ulasan'] ?>)
                                    </span>
                                </div>

                                <!-- Spesifikasi singkat -->
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="flex items-center gap-1 font-label-sm text-label-sm
                                                 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-sm">person</span>
                                        <?= $mobil['kapasitas'] ?> kursi
                                    </span>
                                    <span class="flex items-center gap-1 font-label-sm text-label-sm
                                                 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-sm">settings</span>
                                        <?= $mobil['transmisi'] ?>
                                    </span>
                                </div>

                                <!-- Harga -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            Mulai dari
                                        </p>
                                        <p class="font-headline-sm text-headline-sm text-primary font-bold">
                                            <?= format_rupiah($mobil['harga_hari']) ?>
                                            <span class="font-label-sm text-label-sm
                                                         text-on-surface-variant font-normal">
                                                /hari
                                            </span>
                                        </p>
                                    </div>
                                    <div class="w-10 h-10 rounded-full bg-primary
                                                flex items-center justify-center
                                                group-hover:bg-secondary-container transition-colors">
                                        <span class="material-symbols-outlined text-on-primary text-sm">
                                            arrow_forward
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-8 md:hidden">
                <a href="<?= BASE_URL ?>/daftar_armada.php"
                   class="inline-flex items-center gap-2 border border-primary text-primary
                          px-6 py-3 rounded-full font-label-md text-label-md
                          hover:bg-surface-container transition-colors">
                    Lihat Semua Armada
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         CARA SEWA
    ══════════════════════════════════════════════════════ -->
    <section class="py-16 px-margin-mobile md:px-margin-desktop bg-surface-container-low">
        <div class="max-w-container-max mx-auto">
            <div class="text-center mb-12">
                <h2 class="font-headline-md text-headline-md text-on-surface mb-4">
                    Cara Sewa Mudah
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant">
                    Hanya 3 langkah untuk mulai perjalanan Anda
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Garis penghubung desktop -->
                <div class="hidden md:block absolute top-10 left-1/4 right-1/4
                            h-0.5 bg-outline-variant"></div>

                <?php
                $langkah = [
                    ['1', 'search',        'Pilih Mobil',      'Temukan kendaraan yang sesuai kebutuhan dari katalog armada kami.'],
                    ['2', 'edit_document', 'Isi Data & Bayar', 'Lengkapi data diri dan pilih metode pembayaran yang tersedia.'],
                    ['3', 'directions_car','Mobil Diantar',    'Kendaraan diantar ke lokasi Anda tepat waktu dan siap digunakan.'],
                ];
                foreach ($langkah as [$no, $ikon, $judul, $desk]): ?>
                    <div class="flex flex-col items-center text-center">
                        <div class="w-20 h-20 rounded-full bg-primary
                                    flex items-center justify-center mb-6
                                    shadow-[0px_4px_20px_rgba(4,22,39,0.2)] relative z-10">
                            <span class="material-symbols-outlined text-on-primary text-[32px]"
                                  style="font-variation-settings:'FILL' 1">
                                <?= $ikon ?>
                            </span>
                        </div>
                        <div class="font-label-sm text-label-sm text-on-surface-variant mb-2">
                            Langkah <?= $no ?>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-on-surface mb-3">
                            <?= $judul ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant max-w-xs">
                            <?= $desk ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════
         CTA WHATSAPP
    ══════════════════════════════════════════════════════ -->
    <section class="py-16 px-margin-mobile md:px-margin-desktop bg-primary">
        <div class="max-w-container-max mx-auto text-center">
            <h2 class="font-headline-md text-headline-md text-on-primary mb-4">
                Butuh Bantuan atau Konsultasi?
            </h2>
            <p class="font-body-md text-body-md text-on-primary-container mb-8 max-w-xl mx-auto">
                Tim kami siap membantu Anda 24/7. Hubungi kami via WhatsApp
                untuk pemesanan atau pertanyaan.
            </p>
            <a href="https://wa.me/<?= APP_WHATSAPP ?>?text=Halo+Luxe+Drive,+saya+ingin+bertanya"
               target="_blank"
               class="inline-flex items-center gap-3 bg-secondary-container text-on-secondary-container
                      px-8 py-4 rounded-full font-label-md text-label-md font-bold
                      hover:bg-secondary-container/90 transition-all shadow-lg
                      active:scale-[0.98]">
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Hubungi via WhatsApp
            </a>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
