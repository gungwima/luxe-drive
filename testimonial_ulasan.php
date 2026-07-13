<?php
// ============================================================
// testimonial_ulasan.php — Halaman Semua Ulasan Publik
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Ulasan Pelanggan';
$page_active = 'ulasan';

$mobil_filter = (int)    ($_GET['mobil_id'] ?? 0);
$rating_filter= (int)    ($_GET['rating']   ?? 0);
$urut         = bersihkan($_GET['urut']     ?? 'terbaru');
$halaman      = max(1, (int)($_GET['halaman'] ?? 1));
$per_hal      = 10;

// Statistik global
try {
    $stats = $pdo->query("
        SELECT COUNT(*) AS total,
               AVG(rating) AS avg,
               SUM(rating=5) AS bintang5,
               SUM(rating=4) AS bintang4,
               SUM(rating=3) AS bintang3,
               SUM(rating=2) AS bintang2,
               SUM(rating=1) AS bintang1
        FROM ulasan WHERE status='approved'
    ")->fetch();
} catch (PDOException $e) { $stats = ['total'=>0,'avg'=>0,'bintang5'=>0,'bintang4'=>0,'bintang3'=>0,'bintang2'=>0,'bintang1'=>0]; }

// Daftar mobil untuk filter
try {
    $daftar_mobil = $pdo->query("SELECT id, nama FROM mobil WHERE status != 'nonaktif' ORDER BY nama")->fetchAll();
} catch (PDOException $e) { $daftar_mobil = []; }

// Build query ulasan
$where = ["u.status = 'approved'"];
$params = [];
if ($mobil_filter) { $where[] = "u.mobil_id = ?"; $params[] = $mobil_filter; }
if ($rating_filter) { $where[] = "u.rating = ?";  $params[] = $rating_filter; }
$where_sql = 'WHERE ' . implode(' AND ', $where);
$order_sql = $urut === 'rating_tinggi' ? 'ORDER BY u.rating DESC, u.created_at DESC' : 'ORDER BY u.created_at DESC';

try {
    $total_ulasan = (int)$pdo->prepare("SELECT COUNT(*) FROM ulasan u $where_sql")->execute($params) ?
        $pdo->prepare("SELECT COUNT(*) FROM ulasan u $where_sql")->execute($params) : 0;
    $stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM ulasan u $where_sql");
    $stmt_cnt->execute($params);
    $total_ulasan = (int)$stmt_cnt->fetchColumn();

    $paging = pagination($total_ulasan, $per_hal, $halaman);
    $stmt_ul = $pdo->prepare("
        SELECT u.*,
               m.nama AS nama_mobil,
               CASE WHEN u.tampil_nama=1 THEN us.nama ELSE 'Anonim' END AS nama_tampil,
               us.foto_profil,
               ub.balasan
        FROM ulasan u
        JOIN mobil m ON m.id = u.mobil_id
        JOIN users us ON us.id = u.user_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id = u.id
        $where_sql
        $order_sql
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt_ul->execute($params);
    $ulasan_list = $stmt_ul->fetchAll();
} catch (PDOException $e) { $ulasan_list = []; $paging = pagination(0,$per_hal,1); $total_ulasan=0; }

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">

    <!-- Hero -->
    <div class="text-center mb-12">
        <h1 class="font-headline-md text-headline-md text-on-surface mb-4">Ulasan Pelanggan ⭐</h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mx-auto">
            Apa kata mereka tentang pengalaman bersama <?= APP_NAME ?>?
        </p>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <?php
        $stat_items = [
            [number_format($stats['total']),'Total Ulasan'],
            [number_format((float)$stats['avg'],1).' ★','Rating Rata-rata'],
            [round($stats['total'] > 0 ? ($stats['bintang5']/$stats['total'])*100 : 0).'%','Sangat Puas'],
        ];
        foreach ($stat_items as [$nilai,$label]): ?>
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-6 text-center">
                <p class="font-headline-md text-headline-md text-primary font-bold mb-1"><?= $nilai ?></p>
                <p class="font-label-md text-label-md text-on-surface-variant"><?= $label ?></p>
            </div>
        <?php endforeach; ?>
        <!-- Distribusi rating -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-4 col-span-2 md:col-span-1">
            <?php foreach ([5,4,3,2,1] as $r):
                $jml = (int)($stats["bintang{$r}"] ?? 0);
                $pct = $stats['total'] > 0 ? ($jml/$stats['total'])*100 : 0;
            ?>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-label-sm text-label-sm text-on-surface-variant w-3"><?= $r ?></span>
                    <span class="material-symbols-outlined text-secondary-container text-sm"
                          style="font-variation-settings:'FILL' 1">star</span>
                    <div class="flex-1 h-2 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-secondary-container rounded-full"
                             style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="font-label-sm text-label-sm text-on-surface-variant w-6 text-right">
                        <?= round($pct) ?>%
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="flex flex-wrap gap-3 mb-8 bg-surface-container-low rounded-xl p-4">
        <select name="mobil_id" onchange="this.form.submit()"
                class="appearance-none border border-outline-variant rounded-lg py-2 pl-4 pr-8 font-label-md text-label-md text-on-surface bg-surface focus:outline-none focus:ring-2 focus:ring-primary transition-all">
            <option value="0">Semua Mobil</option>
            <?php foreach ($daftar_mobil as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $mobil_filter === (int)$m['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="rating" onchange="this.form.submit()"
                class="appearance-none border border-outline-variant rounded-lg py-2 pl-4 pr-8 font-label-md text-label-md text-on-surface bg-surface focus:outline-none focus:ring-2 focus:ring-primary transition-all">
            <option value="0">Semua Rating</option>
            <?php foreach ([5,4,3,2,1] as $r): ?>
                <option value="<?= $r ?>" <?= $rating_filter===$r ? 'selected' : '' ?>>
                    <?= $r ?> Bintang
                </option>
            <?php endforeach; ?>
        </select>
        <select name="urut" onchange="this.form.submit()"
                class="appearance-none border border-outline-variant rounded-lg py-2 pl-4 pr-8 font-label-md text-label-md text-on-surface bg-surface focus:outline-none focus:ring-2 focus:ring-primary transition-all">
            <option value="terbaru" <?= $urut==='terbaru' ? 'selected' : '' ?>>Terbaru</option>
            <option value="rating_tinggi" <?= $urut==='rating_tinggi' ? 'selected' : '' ?>>Rating Tertinggi</option>
        </select>
        <span class="font-label-md text-label-md text-on-surface-variant self-center ml-auto">
            <?= $total_ulasan ?> ulasan
        </span>
    </form>

    <!-- Daftar Ulasan -->
    <div class="space-y-6 mb-10">
        <?php if (empty($ulasan_list)): ?>
            <div class="text-center py-16">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-4 block">rate_review</span>
                <p class="font-body-md text-body-md text-on-surface-variant">Belum ada ulasan ditemukan.</p>
            </div>
        <?php else: ?>
            <?php foreach ($ulasan_list as $ul): ?>
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-on-primary font-bold overflow-hidden shrink-0">
                                <?php if ($ul['foto_profil'] && $ul['tampil_nama'] !== 'Anonim'): ?>
                                    <img src="<?= url_gambar($ul['foto_profil'], 'profil') ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= inisial($ul['nama_tampil']) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-label-md text-label-md text-on-surface font-semibold">
                                    <?= htmlspecialchars($ul['nama_tampil']) ?>
                                </p>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                                        <?= waktu_lalu($ul['created_at']) ?>
                                    </span>
                                    <span class="font-label-sm text-label-sm text-primary">
                                        · <?= htmlspecialchars($ul['nama_mobil']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <?= tampil_bintang($ul['rating']) ?>
                        </div>
                    </div>

                    <?php if ($ul['judul']): ?>
                        <p class="font-label-md text-label-md text-on-surface font-semibold mb-2">
                            <?= htmlspecialchars($ul['judul']) ?>
                        </p>
                    <?php endif; ?>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        <?= nl2br(htmlspecialchars($ul['komentar'])) ?>
                    </p>

                    <?php if ($ul['balasan']): ?>
                        <div class="mt-4 ml-4 pl-4 border-l-2 border-secondary-container bg-surface rounded-lg p-3">
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
        <?php endif; ?>
    </div>

    <?= render_pagination($paging, BASE_URL . '/testimonial_ulasan.php?' . http_build_query(array_filter(['mobil_id'=>$mobil_filter,'rating'=>$rating_filter,'urut'=>$urut]))) ?>

    <!-- CTA Tulis Ulasan -->
    <?php if (is_user_login()): ?>
        <div class="mt-12 text-center bg-primary rounded-2xl p-8">
            <h2 class="font-headline-sm text-headline-sm text-on-primary mb-3">
                ✍️ Pernah Sewa? Tulis Ulasan Anda!
            </h2>
            <p class="font-body-md text-body-md text-on-primary-container mb-6">
                Bantu pelanggan lain dengan pengalaman Anda bersama <?= APP_NAME ?>.
            </p>
            <a href="<?= BASE_URL ?>/user/tulis_ulasan.php"
               class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container
                      px-8 py-3 rounded-full font-label-md text-label-md font-bold
                      hover:bg-secondary-container/90 transition-colors">
                <span class="material-symbols-outlined">rate_review</span>
                Tulis Ulasan Sekarang
            </a>
        </div>
    <?php else: ?>
        <div class="mt-12 text-center bg-surface-container-low rounded-2xl p-8">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">
                ✍️ Pernah Sewa? Tulis Ulasan!
            </h2>
            <div class="flex justify-center gap-3 mt-4">
                <a href="<?= BASE_URL ?>/auth/masuk.php"
                   class="bg-primary text-on-primary px-6 py-3 rounded-full font-label-md text-label-md font-bold hover:bg-primary/90 transition-colors">
                    Masuk & Tulis Ulasan
                </a>
                <a href="<?= BASE_URL ?>/auth/daftar.php"
                   class="border border-primary text-primary px-6 py-3 rounded-full font-label-md text-label-md hover:bg-surface-container transition-colors">
                    Daftar Gratis
                </a>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
