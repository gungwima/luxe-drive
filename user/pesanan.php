<?php
// ============================================================
// user/pesanan.php — Daftar Pesanan User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user            = get_user_login();
$menu_user_aktif = 'pesanan';

$status_filter = bersihkan($_GET['status'] ?? '');
$halaman       = max(1, (int)($_GET['halaman'] ?? 1));
$per_hal       = 10;

// Build query
$where  = ['b.user_id = ?'];
$params = [$user['id']];
if ($status_filter) { $where[] = 'b.status = ?'; $params[] = $status_filter; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

try {
    $stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM booking b $where_sql");
    $stmt_cnt->execute($params);
    $total = (int)$stmt_cnt->fetchColumn();
    $paging = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama, m.no_plat,
               t.status AS status_bayar, t.metode, t.id AS trx_id
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        $where_sql
        ORDER BY b.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $pesanan = $stmt->fetchAll();

    // Statistik cepat
    $s_aktif   = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id=? AND status IN ('pending','confirmed','berlangsung')"); $s_aktif->execute([$user['id']]);
    $s_selesai = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id=? AND status='selesai'"); $s_selesai->execute([$user['id']]);
    $s_total   = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id=?"); $s_total->execute([$user['id']]);
    $jml_aktif   = (int)$s_aktif->fetchColumn();
    $jml_selesai = (int)$s_selesai->fetchColumn();
    $jml_total   = (int)$s_total->fetchColumn();
} catch (PDOException $e) {
    $pesanan=[]; $total=0; $jml_aktif=0; $jml_selesai=0; $jml_total=0;
    $paging = pagination(0, $per_hal, 1);
}

$page_title_user = 'Pesanan Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<body class="bg-background text-on-background min-h-screen flex">

<?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>

<div class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen pt-16">
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface border-b border-outline-variant/30 z-[60]
                   flex items-center justify-between px-margin-mobile md:px-margin-desktop">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-label-md text-label-md text-on-surface hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <?= APP_NAME ?>
        </a>
    </header>

    <main class="flex-1 px-margin-mobile md:px-margin-desktop py-8 max-w-container-max mx-auto w-full">
        <div class="mb-10">
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">Pesanan Saya</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Kelola riwayat pemesanan dan pantau perjalanan aktif Anda.
            </p>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <?php foreach ([['Total','', $jml_total],['Aktif','pending,confirmed,berlangsung',$jml_aktif],['Selesai','selesai',$jml_selesai]] as [$lbl,$st,$jml]): ?>
                <a href="?status=<?= $st ?>"
                   class="bg-surface-container-lowest rounded-xl p-5 border
                          <?= $status_filter===explode(',',$st)[0] ? 'border-primary ring-1 ring-primary' : 'border-outline-variant/30' ?>
                          text-center hover:shadow-sm transition-all">
                    <p class="font-headline-md text-headline-md text-primary font-bold"><?= $jml ?></p>
                    <p class="font-label-md text-label-md text-on-surface-variant"><?= $lbl ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filter Tab -->
        <div class="flex gap-2 flex-wrap mb-6">
            <?php
            $tabs = [
                [''            , 'Semua'],
                ['pending'     , 'Pending'],
                ['confirmed'   , 'Dikonfirmasi'],
                ['berlangsung' , 'Berlangsung'],
                ['selesai'     , 'Selesai'],
                ['dibatalkan'  , 'Dibatalkan'],
            ];
            foreach ($tabs as [$val,$lbl]): ?>
                <a href="?status=<?= $val ?>"
                   class="px-4 py-2 rounded-full font-label-md text-label-md transition-colors
                          <?= $status_filter===$val ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
                    <?= $lbl ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- List Pesanan -->
        <?php if (empty($pesanan)): ?>
            <div class="text-center py-20 bg-surface-container-lowest rounded-2xl border border-outline-variant/30">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4 block">receipt_long</span>
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">Belum Ada Pesanan</h3>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                    Mulai perjalanan pertama Anda bersama <?= APP_NAME ?>!
                </p>
                <a href="<?= BASE_URL ?>/daftar_armada.php"
                   class="inline-flex items-center gap-2 bg-primary text-on-primary px-6 py-3
                          rounded-full font-label-md text-label-md font-bold hover:bg-primary/90 transition-colors">
                    <span class="material-symbols-outlined">directions_car</span>
                    Lihat Armada
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4 mb-8">
                <?php foreach ($pesanan as $p): ?>
                    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/30
                                shadow-[0px_4px_20px_rgba(26,43,60,0.06)] overflow-hidden
                                flex flex-col md:flex-row relative hover:shadow-md transition-shadow">

                        <!-- Badge Status -->
                        <div class="absolute top-4 right-4 z-10">
                            <?= badge_status_booking($p['status']) ?>
                        </div>

                        <!-- Foto Mobil -->
                        <div class="w-full md:w-52 h-44 md:h-auto relative flex-shrink-0">
                            <img src="<?= url_foto_mobil($p['foto_utama']) ?>"
                                 alt="<?= htmlspecialchars($p['nama_mobil']) ?>"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-primary/60 to-transparent"></div>
                            <div class="absolute bottom-3 left-3 text-on-primary">
                                <h3 class="font-headline-sm text-headline-sm">
                                    <?= htmlspecialchars($p['nama_mobil']) ?>
                                </h3>
                                <p class="font-label-sm text-label-sm opacity-80">
                                    <?= htmlspecialchars($p['no_plat']) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Detail -->
                        <div class="p-5 md:p-6 flex-1 flex flex-col justify-between">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant
                                               uppercase tracking-wider mb-1">
                                        Pengambilan
                                    </p>
                                    <p class="font-body-md text-body-md text-primary font-medium">
                                        <?= format_datetime($p['tgl_ambil']) ?>
                                    </p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant
                                               flex items-center gap-1 mt-1">
                                        <span class="material-symbols-outlined text-sm">location_on</span>
                                        <?= potong_teks($p['lokasi_jemput'] ?? '', 30) ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant
                                               uppercase tracking-wider mb-1">
                                        Pengembalian
                                    </p>
                                    <p class="font-body-md text-body-md text-primary font-medium">
                                        <?= format_datetime($p['tgl_kembali']) ?>
                                    </p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                                        <?= $p['durasi_hari'] ?> hari
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-outline-variant/30 pt-4
                                        flex flex-col sm:flex-row items-start sm:items-center
                                        justify-between gap-3">
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant">
                                        Kode: <span class="text-primary font-semibold">
                                            <?= htmlspecialchars($p['kode_booking']) ?>
                                        </span>
                                    </p>
                                    <p class="font-headline-sm text-headline-sm text-primary font-bold">
                                        <?= format_rupiah($p['total']) ?>
                                    </p>
                                </div>
                                <div class="flex gap-2 flex-wrap">
                                    <a href="<?= BASE_URL ?>/booking/status.php?booking_id=<?= $p['id'] ?>"
                                       class="px-4 py-2 bg-primary text-on-primary rounded-lg
                                              font-label-md text-label-md hover:bg-primary/90
                                              transition-colors">
                                        Lihat Detail
                                    </a>
                                    <?php if ($p['status'] === 'selesai'): ?>
                                        <?php
                                        // Cek sudah ulasan belum
                                        $cek_ul = $pdo->prepare("SELECT id FROM ulasan WHERE booking_id=?");
                                        $cek_ul->execute([$p['id']]);
                                        if (!$cek_ul->fetch()):
                                        ?>
                                            <a href="<?= BASE_URL ?>/user/tulis_ulasan.php?booking_id=<?= $p['id'] ?>"
                                               class="px-4 py-2 border border-secondary-container
                                                      text-secondary rounded-lg font-label-md text-label-md
                                                      hover:bg-secondary-container/10 transition-colors">
                                                Tulis Ulasan
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (in_array($p['status'], ['pending'])): ?>
                                        <a href="<?= BASE_URL ?>/booking/pembayaran.php?booking_id=<?= $p['id'] ?>"
                                           class="px-4 py-2 border border-primary text-primary rounded-lg
                                                  font-label-md text-label-md hover:bg-surface-container
                                                  transition-colors">
                                            Bayar Sekarang
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= render_pagination($paging, BASE_URL . '/user/pesanan.php?' . http_build_query(array_filter(['status'=>$status_filter]))) ?>
        <?php endif; ?>
    </main>
</div>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
