<?php
// ============================================================
// admin/booking/index.php — List Semua Booking
// ============================================================
$page_title_admin = 'Manajemen Booking';
$menu_aktif       = 'booking';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$status_filter = bersihkan($_GET['status'] ?? '');
$cari          = bersihkan($_GET['cari']   ?? '');
$halaman       = max(1,(int)($_GET['halaman'] ?? 1));
$per_hal       = 15;

$where  = ['1=1'];
$params = [];
if ($status_filter) { $where[] = 'b.status = ?'; $params[] = $status_filter; }
if ($cari) {
    $where[] = "(b.kode_booking LIKE ? OR u.nama LIKE ? OR m.nama LIKE ?)";
    $params[] = "%$cari%"; $params[] = "%$cari%"; $params[] = "%$cari%";
}
$where_sql = implode(' AND ', $where);

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM booking b JOIN users u ON u.id=b.user_id JOIN mobil m ON m.id=b.mobil_id WHERE $where_sql");
    $cnt->execute($params);
    $total  = (int)$cnt->fetchColumn();
    $paging = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT b.*, u.nama AS nama_user, u.no_hp,
               m.nama AS nama_mobil, m.no_plat, m.foto_utama,
               t.status AS status_bayar, t.id AS trx_id
        FROM booking b
        JOIN users u  ON u.id = b.user_id
        JOIN mobil m  ON m.id = b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        WHERE $where_sql
        ORDER BY b.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $booking_list = $stmt->fetchAll();

    // Tab count (selalu total per status, tanpa filter aktif)
    $tabs_count = [];
    foreach (['pending','confirmed','berlangsung','selesai','dibatalkan'] as $s) {
        $c = $pdo->query("SELECT COUNT(*) FROM booking WHERE status='$s'")->fetchColumn();
        $tabs_count[$s] = (int)$c;
    }
    // Total SEMUA booking (untuk tab "Semua", tidak terpengaruh filter)
    $total_semua = (int)$pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn();
} catch (PDOException $e) {
    $booking_list=[]; $total=0; $paging=pagination(0,$per_hal,1); $tabs_count=[]; $total_semua=0;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen flex flex-col">
<main class="flex-1 flex flex-col">

    <!-- Header -->
    <div class="px-8 py-6 bg-background border-b border-outline-variant/30 flex justify-between items-center">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary">Manajemen Booking</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1"><?= $total ?> booking ditemukan</p>
        </div>
        <a href="<?= ADMIN_URL ?>/booking/manual.php"
           class="flex items-center gap-2 px-6 py-3 bg-primary text-on-primary rounded-xl
                  font-label-md text-label-md font-bold hover:bg-primary/90 transition-colors shadow-md">
            <span class="material-symbols-outlined">add</span>
            Booking Manual
        </a>
    </div>

    <!-- Tabs Status -->
    <div class="px-8 pt-4 border-b border-outline-variant/20 bg-background flex gap-1 overflow-x-auto">
        <a href="?status="
           class="px-4 py-2 rounded-t-lg font-label-md text-label-md whitespace-nowrap
                  <?= !$status_filter ? 'bg-surface border border-b-0 border-outline-variant/30 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container transition-colors' ?>">
            Semua (<?= $total_semua ?>)
        </a>
        <?php foreach (['pending'=>'Pending','confirmed'=>'Dikonfirmasi','berlangsung'=>'Berlangsung','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'] as $v=>$l): ?>
            <a href="?status=<?= $v ?>"
               class="px-4 py-2 rounded-t-lg font-label-md text-label-md whitespace-nowrap flex items-center gap-1
                      <?= $status_filter===$v ? 'bg-surface border border-b-0 border-outline-variant/30 text-primary font-semibold' : 'text-on-surface-variant hover:bg-surface-container transition-colors' ?>">
                <?= $l ?>
                <?php if (($tabs_count[$v]??0) > 0): ?>
                    <span class="bg-<?= $v==='pending' ? 'error' : 'surface-container-highest' ?> text-<?= $v==='pending' ? 'on-error' : 'on-surface-variant' ?> text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
                        <?= $tabs_count[$v] ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Cari -->
    <form method="GET" class="px-8 py-3 bg-background border-b border-outline-variant/20 flex gap-3">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari kode booking, nama pelanggan, mobil..."
                   class="w-full pl-12 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface
                          font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
        </div>
        <button type="submit"
                class="px-5 py-2.5 bg-primary text-on-primary rounded-xl font-label-md text-label-md
                       hover:bg-primary/90 transition-colors">
            Cari
        </button>
    </form>

    <!-- Tabel -->
    <div class="px-8 py-6 flex-1">
        <div class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.05)]
                    border border-outline-variant/30 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <?php foreach (['ID Booking','Pelanggan','Kendaraan','Tanggal Sewa','Total','Pembayaran','Status','Aksi'] as $h): ?>
                                <th class="py-4 px-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                    <?= $h ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php if (empty($booking_list)): ?>
                            <tr><td colspan="8" class="py-16 text-center text-on-surface-variant font-body-md text-body-md">
                                <span class="material-symbols-outlined text-4xl block mb-3">calendar_month</span>
                                Tidak ada booking.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($booking_list as $b): ?>
                                <tr class="hover:bg-surface-container-lowest transition-colors group">
                                    <td class="py-3 px-4">
                                        <p class="font-label-md text-label-md font-medium text-primary">
                                            <?= htmlspecialchars($b['kode_booking']) ?>
                                        </p>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            <?= waktu_lalu($b['created_at']) ?>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-body-md text-body-md text-on-surface font-medium">
                                            <?= htmlspecialchars($b['nama_user']) ?>
                                        </p>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            <?= htmlspecialchars($b['no_hp']) ?>
                                        </p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-8 rounded-lg bg-surface-container-high
                                                        overflow-hidden shrink-0">
                                                <img src="<?= url_foto_mobil($b['foto_utama']) ?>"
                                                     class="w-full h-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-body-md text-body-md text-on-surface">
                                                    <?= htmlspecialchars($b['nama_mobil']) ?>
                                                </p>
                                                <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                    <?= htmlspecialchars($b['no_plat']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="font-body-md text-body-md text-on-surface">
                                            <?= format_tanggal($b['tgl_ambil']) ?>
                                        </p>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            <?= $b['durasi_hari'] ?> Hari
                                        </p>
                                    </td>
                                    <td class="py-3 px-4 font-label-md text-label-md font-semibold text-on-surface">
                                        <?= format_rupiah($b['total']) ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?= badge_status_transaksi($b['status_bayar'] ?? 'pending') ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?= badge_status_booking($b['status']) ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-1">
                                            <a href="<?= ADMIN_URL ?>/booking/detail.php?id=<?= $b['id'] ?>"
                                               class="p-1.5 text-primary hover:bg-surface-container rounded-lg
                                                      transition-colors" title="Detail">
                                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= render_pagination($paging, ADMIN_URL . '/booking/index.php?' . http_build_query(array_filter(['status'=>$status_filter,'cari'=>$cari]))) ?>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
