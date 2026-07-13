<?php
// ============================================================
// user/ulasan_saya.php — Daftar Ulasan Milik User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user            = get_user_login();
$menu_user_aktif = 'ulasan';

try {
    // Ulasan yang sudah ditulis
    $stmt = $pdo->prepare("
        SELECT u.*, m.nama AS nama_mobil, m.foto_utama,
               b.kode_booking, b.tgl_ambil, b.tgl_kembali, b.durasi_hari,
               ub.balasan, ub.created_at AS tgl_balas
        FROM ulasan u
        JOIN mobil   m ON m.id = u.mobil_id
        JOIN booking b ON b.id = u.booking_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id = u.id
        WHERE u.user_id = ?
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $ulasan_list = $stmt->fetchAll();

    // Booking selesai yang BELUM diulas
    $stmt2 = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        WHERE b.user_id = ? AND b.status = 'selesai'
        AND b.id NOT IN (SELECT booking_id FROM ulasan WHERE booking_id IS NOT NULL)
        ORDER BY b.tgl_kembali DESC
    ");
    $stmt2->execute([$user['id']]);
    $belum_diulas = $stmt2->fetchAll();

    // Statistik
    $jml_disetujui = count(array_filter($ulasan_list, fn($u) => $u['status']==='approved'));
    $jml_pending   = count(array_filter($ulasan_list, fn($u) => $u['status']==='pending'));
} catch (PDOException $e) {
    $ulasan_list=[]; $belum_diulas=[]; $jml_disetujui=0; $jml_pending=0;
}

$page_title_user = 'Ulasan Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<body class="bg-background text-on-background min-h-screen flex">
<?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>

<div class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen pt-16">
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface border-b border-outline-variant/30
                   z-[60] flex items-center justify-between px-margin-mobile md:px-margin-desktop">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-label-md text-label-md text-on-surface hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <?= APP_NAME ?>
        </a>
        <a href="<?= BASE_URL ?>/user/tulis_ulasan.php"
           class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2 rounded-full
                  font-label-md text-label-md hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined text-sm">rate_review</span>
            Tulis Ulasan
        </a>
    </header>

    <main class="flex-1 px-margin-mobile md:px-margin-desktop py-8 max-w-container-max mx-auto w-full">

        <?= render_flash() ?>

        <div class="mb-10">
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">Ulasan Saya</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Kelola ulasan yang pernah Anda tulis.
            </p>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-3 gap-4 mb-10">
            <?php foreach ([
                ['Total Ulasan',    count($ulasan_list), 'rate_review', ''],
                ['Disetujui',       $jml_disetujui,      'verified',    'bg-green-50 border-green-200'],
                ['Menunggu Tinjauan',$jml_pending,        'schedule',   'bg-secondary-fixed/20'],
            ] as [$lbl,$jml,$ikon,$cls]): ?>
                <div class="bg-surface-container-lowest rounded-xl p-5 border
                            <?= $cls ?: 'border-outline-variant/30' ?> flex justify-between items-center">
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">
                            <?= $lbl ?>
                        </p>
                        <p class="font-display-lg-mobile text-display-lg-mobile text-primary font-bold">
                            <?= $jml ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-[24px]"><?= $ikon ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Belum Diulas -->
        <?php if (!empty($belum_diulas)): ?>
            <section class="mb-12">
                <h2 class="font-headline-sm text-headline-sm text-primary mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary-container">edit_note</span>
                    Perlu Diulas (<?= count($belum_diulas) ?>)
                </h2>
                <div class="space-y-4">
                    <?php foreach ($belum_diulas as $b): ?>
                        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30
                                    shadow-sm overflow-hidden flex flex-col md:flex-row items-stretch">
                            <div class="w-full md:w-48 h-36 md:h-auto relative flex-shrink-0">
                                <img src="<?= url_foto_mobil($b['foto_utama']) ?>"
                                     alt="<?= htmlspecialchars($b['nama_mobil']) ?>"
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="p-5 flex-1 flex flex-col justify-center">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-headline-sm text-headline-sm text-primary">
                                            <?= htmlspecialchars($b['nama_mobil']) ?>
                                        </h3>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant mt-1
                                                   flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                                            <?= format_tanggal($b['tgl_ambil']) ?>
                                            (<?= $b['durasi_hari'] ?> hari)
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full
                                                 bg-green-50 text-green-800 font-label-sm text-label-sm">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        Selesai
                                    </span>
                                </div>
                                <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                                    Bagikan pengalaman Anda untuk membantu kami meningkatkan layanan.
                                </p>
                                <div>
                                    <a href="<?= BASE_URL ?>/user/tulis_ulasan.php?booking_id=<?= $b['id'] ?>"
                                       class="inline-flex items-center gap-2 bg-primary text-on-primary
                                              px-5 py-2.5 rounded-lg font-label-md text-label-md
                                              hover:bg-primary/90 transition-colors shadow-sm
                                              active:scale-[0.98]">
                                        Tulis Ulasan Sekarang
                                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Riwayat Ulasan -->
        <section>
            <h2 class="font-headline-sm text-headline-sm text-primary mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-on-surface-variant">history</span>
                Riwayat Ulasan
            </h2>

            <?php if (empty($ulasan_list)): ?>
                <div class="text-center py-16 bg-surface-container-lowest rounded-2xl border border-outline-variant/30">
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-4 block">
                        rate_review
                    </span>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                        Belum ada ulasan yang ditulis.
                    </p>
                    <?php if (!empty($belum_diulas)): ?>
                        <a href="<?= BASE_URL ?>/user/tulis_ulasan.php"
                           class="inline-flex items-center gap-2 bg-primary text-on-primary px-6 py-3
                                  rounded-full font-label-md text-label-md hover:bg-primary/90 transition-colors">
                            Tulis Ulasan Pertama
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php foreach ($ulasan_list as $ul): ?>
                        <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30
                                    shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col md:flex-row justify-between gap-4 mb-4
                                        pb-4 border-b border-outline-variant/30">
                                <div class="flex items-center gap-3">
                                    <img src="<?= url_foto_mobil($ul['foto_utama']) ?>"
                                         alt="<?= htmlspecialchars($ul['nama_mobil']) ?>"
                                         class="w-16 h-12 rounded-lg object-cover shrink-0">
                                    <div>
                                        <h3 class="font-headline-sm text-headline-sm text-primary">
                                            <?= htmlspecialchars($ul['nama_mobil']) ?>
                                        </h3>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
                                            <?= format_tanggal($ul['tgl_ambil']) ?> –
                                            <?= format_tanggal($ul['tgl_kembali']) ?>
                                            (<?= $ul['durasi_hari'] ?> hari)
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-start md:items-end gap-2 shrink-0">
                                    <div class="flex items-center gap-1">
                                        <?= tampil_bintang($ul['rating']) ?>
                                    </div>
                                    <?= badge_status_ulasan($ul['status']) ?>
                                </div>
                            </div>

                            <?php if ($ul['judul']): ?>
                                <p class="font-label-md text-label-md text-on-surface font-semibold mb-2">
                                    <?= htmlspecialchars($ul['judul']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="font-body-md text-body-md text-on-surface italic mb-3">
                                "<?= nl2br(htmlspecialchars($ul['komentar'])) ?>"
                            </p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                Dikirim: <?= format_tanggal($ul['created_at']) ?>
                            </p>

                            <?php if ($ul['balasan']): ?>
                                <div class="mt-4 ml-4 pl-4 border-l-2 border-secondary-container
                                            bg-surface rounded-lg p-3">
                                    <p class="font-label-sm text-label-sm text-secondary font-bold mb-1">
                                        Balasan Tim <?= APP_NAME ?>
                                    </p>
                                    <p class="font-body-md text-body-md text-on-surface-variant text-sm">
                                        <?= nl2br(htmlspecialchars($ul['balasan'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
