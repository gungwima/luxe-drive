<?php
// ============================================================
// admin/laporan/ulasan.php — Laporan Ulasan & Kepuasan
// ============================================================
$page_title_admin = 'Laporan Ulasan';
$menu_aktif       = 'laporan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

try {
    $stats = $pdo->query("
        SELECT COUNT(*) AS total, AVG(rating) AS avg,
               SUM(rating=5) AS b5, SUM(rating=4) AS b4, SUM(rating=3) AS b3,
               SUM(rating=2) AS b2, SUM(rating=1) AS b1,
               SUM(status='approved') AS disetujui,
               SUM(status='pending')  AS pending
        FROM ulasan
    ")->fetch();

    // Rating per mobil
    $per_mobil = $pdo->query("
        SELECT m.nama, COUNT(u.id) AS jml, AVG(u.rating) AS avg_rating
        FROM mobil m JOIN ulasan u ON u.mobil_id=m.id AND u.status='approved'
        GROUP BY m.id ORDER BY avg_rating DESC LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $stats=['total'=>0,'avg'=>0,'b5'=>0,'b4'=>0,'b3'=>0,'b2'=>0,'b1'=>0,'disetujui'=>0,'pending'=>0];
    $per_mobil=[];
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1200px] mx-auto space-y-6">

    <div>
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Laporan Ulasan</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Analisis kepuasan pelanggan.</p>
    </div>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ([
            ['Total Ulasan',$stats['total'],'rate_review'],
            ['Rating Rata-rata',number_format((float)$stats['avg'],1).' ★','star'],
            ['Disetujui',$stats['disetujui'],'verified'],
            ['Menunggu',$stats['pending'],'schedule'],
        ] as [$lbl,$val,$ikon]): ?>
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <span class="material-symbols-outlined text-primary text-[24px] mb-2"><?= $ikon ?></span>
                <p class="font-headline-md text-headline-md text-primary font-bold"><?= $val ?></p>
                <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $lbl ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Distribusi Rating -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-6">Distribusi Rating</h2>
            <div class="space-y-3">
                <?php foreach ([5,4,3,2,1] as $r):
                    $jml = (int)($stats["b{$r}"] ?? 0);
                    $pct = $stats['total'] > 0 ? round($jml/$stats['total']*100) : 0;
                ?>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1 w-12 shrink-0">
                            <span class="font-label-md text-label-md text-on-surface"><?= $r ?></span>
                            <span class="material-symbols-outlined text-secondary-container text-sm" style="font-variation-settings:'FILL' 1">star</span>
                        </span>
                        <div class="flex-1 bg-surface-container h-3 rounded-full overflow-hidden">
                            <div class="h-full bg-secondary-container rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant w-16 text-right shrink-0"><?= $jml ?> (<?= $pct ?>%)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Rating per Mobil -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-6">Rating Tertinggi per Mobil</h2>
            <?php if (empty($per_mobil)): ?>
                <p class="text-on-surface-variant font-body-md text-body-md">Belum ada ulasan.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($per_mobil as $pm): ?>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-label-md text-label-md text-on-surface font-medium"><?= htmlspecialchars($pm['nama']) ?></p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $pm['jml'] ?> ulasan</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="font-headline-sm text-headline-sm text-primary font-bold"><?= number_format($pm['avg_rating'],1) ?></span>
                                <span class="material-symbols-outlined text-secondary-container text-[18px]" style="font-variation-settings:'FILL' 1">star</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
