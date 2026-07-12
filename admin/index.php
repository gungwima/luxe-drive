<?php
// ============================================================
// admin/index.php — Dashboard Utama Admin
// ============================================================
$page_title_admin = 'Dashboard';
$menu_aktif       = 'dashboard';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_admin_login();
$admin = get_admin_login();

// ── Statistik Hari Ini ──
try {
    $hari_ini = date('Y-m-d');

    // Booking baru hari ini
    $s = $pdo->query("SELECT COUNT(*) FROM booking WHERE DATE(created_at)='$hari_ini'")->fetchColumn();
    $booking_hari_ini = (int)$s;

    // Pendapatan hari ini (transaksi verified)
    $s2 = $pdo->query("SELECT COALESCE(SUM(t.jumlah),0) FROM transaksi t
                        JOIN booking b ON b.id=t.booking_id
                        WHERE t.status='verified' AND DATE(t.created_at)='$hari_ini'")->fetchColumn();
    $pendapatan_hari_ini = (float)$s2;

    // Total mobil & tersedia
    $total_mobil    = (int)$pdo->query("SELECT COUNT(*) FROM mobil WHERE status!='nonaktif'")->fetchColumn();
    $mobil_tersedia = (int)$pdo->query("SELECT COUNT(*) FROM mobil WHERE status='tersedia'")->fetchColumn();

    // Pelanggan aktif bulan ini
    $bulan = date('Y-m');
    $pelanggan_aktif = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM booking WHERE DATE_FORMAT(created_at,'%Y-%m')='$bulan'")->fetchColumn();

    // 7 hari terakhir revenue
    $revenue_7hari = [];
    for ($i = 6; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("-$i days"));
        $r   = $pdo->query("SELECT COALESCE(SUM(t.jumlah),0) FROM transaksi t
                             JOIN booking b ON b.id=t.booking_id
                             WHERE t.status='verified' AND DATE(t.created_at)='$tgl'")->fetchColumn();
        $revenue_7hari[] = ['tgl' => date('d M', strtotime($tgl)), 'nilai' => (float)$r];
    }
    $max_rev = max(array_column($revenue_7hari, 'nilai')) ?: 1;

    // Booking terbaru (10)
    $stmt_booking = $pdo->query("
        SELECT b.*, m.nama AS nama_mobil, u.nama AS nama_user
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        JOIN users  u ON u.id = b.user_id
        ORDER BY b.created_at DESC LIMIT 10
    ");
    $booking_terbaru = $stmt_booking->fetchAll();

    // Notif pending
    $pending_booking  = (int)$pdo->query("SELECT COUNT(*) FROM booking WHERE status='pending'")->fetchColumn();
    $pending_ulasan   = (int)$pdo->query("SELECT COUNT(*) FROM ulasan  WHERE status='pending'")->fetchColumn();
    $pending_transaksi= (int)$pdo->query("SELECT COUNT(*) FROM transaksi WHERE status='pending'")->fetchColumn();

    // Armada paling laris
    $stmt_laris = $pdo->query("
        SELECT m.nama, COUNT(b.id) AS jml
        FROM mobil m LEFT JOIN booking b ON b.mobil_id=m.id AND b.status='selesai'
        GROUP BY m.id ORDER BY jml DESC LIMIT 5
    ");
    $armada_laris = $stmt_laris->fetchAll();

} catch (PDOException $e) {
    $booking_hari_ini=0; $pendapatan_hari_ini=0;
    $total_mobil=0; $mobil_tersedia=0; $pelanggan_aktif=0;
    $revenue_7hari=[]; $booking_terbaru=[];
    $pending_booking=0; $pending_ulasan=0; $pending_transaksi=0;
    $armada_laris=[];
}

require_once __DIR__ . '/../includes/navbar_admin.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Main Content -->
<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 space-y-8 max-w-[1400px] mx-auto">

    <!-- Header -->
    <header class="flex justify-between items-center">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary">
                Dashboard Overview
            </h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mt-1">
                Selamat datang kembali, <?= htmlspecialchars($admin['nama']) ?>.
                Berikut ringkasan hari ini.
            </p>
        </div>
        <div class="flex gap-3">
            <a href="<?= ADMIN_URL ?>/laporan/index.php"
               class="flex items-center gap-2 px-4 py-2 border border-outline-variant
                      text-on-surface rounded-lg font-label-md text-label-md
                      hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">download</span>
                Export Laporan
            </a>
            <a href="<?= ADMIN_URL ?>/booking/manual.php"
               class="flex items-center gap-2 px-6 py-2 bg-secondary-container
                      text-on-secondary-container rounded-lg font-label-md text-label-md
                      font-semibold hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Booking Baru
            </a>
        </div>
    </header>

    <!-- Notif Pending (jika ada) -->
    <?php if ($pending_booking + $pending_ulasan + $pending_transaksi > 0): ?>
        <div class="flex flex-wrap gap-3">
            <?php if ($pending_booking > 0): ?>
                <a href="<?= ADMIN_URL ?>/booking/index.php?status=pending"
                   class="flex items-center gap-2 px-4 py-2 bg-error-container text-on-error-container
                          rounded-lg font-label-md text-label-md animate-pulse">
                    <span class="material-symbols-outlined text-sm">notifications_active</span>
                    <?= $pending_booking ?> Booking Pending
                </a>
            <?php endif; ?>
            <?php if ($pending_transaksi > 0): ?>
                <a href="<?= ADMIN_URL ?>/booking/index.php?status=pending"
                   class="flex items-center gap-2 px-4 py-2 bg-secondary-fixed/30
                          text-on-secondary-container rounded-lg font-label-md text-label-md">
                    <span class="material-symbols-outlined text-sm">payments</span>
                    <?= $pending_transaksi ?> Pembayaran Perlu Diverifikasi
                </a>
            <?php endif; ?>
            <?php if ($pending_ulasan > 0): ?>
                <a href="<?= ADMIN_URL ?>/ulasan/index.php?status=pending"
                   class="flex items-center gap-2 px-4 py-2 bg-tertiary-fixed
                          text-on-tertiary-fixed rounded-lg font-label-md text-label-md">
                    <span class="material-symbols-outlined text-sm">rate_review</span>
                    <?= $pending_ulasan ?> Ulasan Perlu Ditinjau
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $kartu = [
            ['book_online',       'Booking Hari Ini',    $booking_hari_ini,    '+',  'bg-primary-fixed',        'Today'],
            ['account_balance_wallet','Pendapatan Hari Ini', format_rupiah($pendapatan_hari_ini),'', 'bg-primary-fixed','Today'],
            ['directions_car',    'Mobil Tersedia',      "$mobil_tersedia / $total_mobil", '','bg-primary-fixed','Real-time'],
            ['groups',            'Pelanggan Aktif',     $pelanggan_aktif,     '',   'bg-primary-fixed',        'Bulan Ini'],
        ];
        foreach ($kartu as [$ikon,$label,$nilai,$trend,$cls,$periode]): ?>
            <div class="bg-surface rounded-xl p-6 shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                        border border-outline-variant/30 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-4">
                    <div class="<?= $cls ?> p-3 rounded-lg">
                        <span class="material-symbols-outlined text-primary"><?= $ikon ?></span>
                    </div>
                    <span class="bg-surface-container text-on-surface-variant
                                 font-label-sm text-label-sm px-2 py-1 rounded">
                        <?= $periode ?>
                    </span>
                </div>
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant mb-1"><?= $label ?></p>
                    <h3 class="font-headline-md text-headline-md text-primary text-2xl font-bold">
                        <?= $nilai ?>
                    </h3>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Chart + Armada Laris -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Revenue 7 Hari -->
        <div class="lg:col-span-2 bg-surface rounded-xl p-6
                    shadow-[0px_4px_20px_rgba(26,43,60,0.08)] border border-outline-variant/30">
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-primary font-bold">
                    Tren Pendapatan 7 Hari
                </h2>
            </div>
            <div class="flex items-end gap-3 h-40">
                <?php foreach ($revenue_7hari as $r):
                    $pct = $max_rev > 0 ? ($r['nilai'] / $max_rev * 100) : 0;
                ?>
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <span class="font-label-sm text-label-sm text-on-surface-variant text-center text-xs">
                            <?= $r['nilai'] > 0 ? format_angka_pendek($r['nilai']) : '' ?>
                        </span>
                        <div class="w-full rounded-t-lg bg-primary transition-all"
                             style="height: <?= max(4, $pct) ?>%; min-height: 4px; max-height: 100px;">
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant text-center text-xs whitespace-nowrap">
                            <?= $r['tgl'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Armada Paling Laris -->
        <div class="bg-surface rounded-xl p-6
                    shadow-[0px_4px_20px_rgba(26,43,60,0.08)] border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-6">
                Armada Terlaris
            </h2>
            <div class="space-y-4">
                <?php foreach ($armada_laris as $i => $a): ?>
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-primary-fixed text-primary flex items-center
                                     justify-center font-label-sm text-label-sm font-bold text-xs shrink-0">
                            <?= $i+1 ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-label-md text-label-md text-on-surface font-medium truncate">
                                <?= htmlspecialchars($a['nama']) ?>
                            </p>
                            <div class="w-full bg-surface-container-high h-1.5 rounded-full mt-1 overflow-hidden">
                                <?php $max_laris = max(array_column($armada_laris, 'jml')) ?: 1; ?>
                                <div class="h-full bg-secondary-container rounded-full"
                                     style="width: <?= ($a['jml'] / $max_laris * 100) ?>%"></div>
                            </div>
                        </div>
                        <span class="font-label-sm text-label-sm text-on-surface-variant shrink-0">
                            <?= $a['jml'] ?>x
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Booking Terbaru -->
    <div class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                border border-outline-variant/30 overflow-hidden">
        <div class="flex justify-between items-center px-6 py-4
                    border-b border-outline-variant/30 bg-surface-container-low/50">
            <h2 class="font-headline-sm text-headline-sm text-primary font-semibold">
                Booking Terbaru
            </h2>
            <a href="<?= ADMIN_URL ?>/booking/index.php"
               class="font-label-md text-label-md text-primary hover:underline flex items-center gap-1">
                Lihat Semua
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <?php foreach (['ID','Pelanggan','Kendaraan','Tanggal','Total','Status','Aksi'] as $h): ?>
                            <th class="px-5 py-3 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                <?= $h ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (empty($booking_terbaru)): ?>
                        <tr><td colspan="7" class="px-5 py-8 text-center text-on-surface-variant font-body-md text-body-md">
                            Belum ada booking.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($booking_terbaru as $b): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-5 py-3 font-label-md text-label-md font-medium text-primary">
                                    <?= htmlspecialchars($b['kode_booking']) ?>
                                </td>
                                <td class="px-5 py-3 font-body-md text-body-md text-on-surface">
                                    <?= htmlspecialchars($b['nama_user']) ?>
                                </td>
                                <td class="px-5 py-3 font-body-md text-body-md text-on-surface">
                                    <?= htmlspecialchars($b['nama_mobil']) ?>
                                </td>
                                <td class="px-5 py-3 font-label-md text-label-md text-on-surface-variant">
                                    <?= format_tanggal($b['tgl_ambil']) ?>
                                </td>
                                <td class="px-5 py-3 font-label-md text-label-md text-on-surface font-semibold">
                                    <?= format_rupiah($b['total']) ?>
                                </td>
                                <td class="px-5 py-3">
                                    <?= badge_status_booking($b['status']) ?>
                                </td>
                                <td class="px-5 py-3">
                                    <a href="<?= ADMIN_URL ?>/booking/detail.php?id=<?= $b['id'] ?>"
                                       class="p-1.5 text-primary hover:bg-surface-container rounded-lg
                                              transition-colors inline-flex">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
