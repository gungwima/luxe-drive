<?php
// ============================================================
// admin/laporan/index.php — Laporan & Analitik
// ============================================================
$page_title_admin = 'Laporan Analitik';
$menu_aktif       = 'laporan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$periode = bersihkan($_GET['periode'] ?? 'bulan_ini');
[$tgl_awal, $tgl_akhir] = match($periode) {
    '7hari'      => [date('Y-m-d', strtotime('-7 days')),  date('Y-m-d')],
    '30hari'     => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')],
    'tahun_ini'  => [date('Y-01-01'), date('Y-m-d')],
    default      => [date('Y-m-01'), date('Y-m-t')],
};

try {
    // Total revenue periode
    $rev = $pdo->prepare("SELECT COALESCE(SUM(t.jumlah),0) FROM transaksi t JOIN booking b ON b.id=t.booking_id WHERE t.status='verified' AND DATE(t.created_at) BETWEEN ? AND ?");
    $rev->execute([$tgl_awal, $tgl_akhir]); $total_revenue = (float)$rev->fetchColumn();

    // Total booking periode
    $bk = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE DATE(created_at) BETWEEN ? AND ?");
    $bk->execute([$tgl_awal, $tgl_akhir]); $total_booking = (int)$bk->fetchColumn();

    // Rata durasi
    $dur = $pdo->prepare("SELECT COALESCE(AVG(durasi_hari),0) FROM booking WHERE DATE(created_at) BETWEEN ? AND ?");
    $dur->execute([$tgl_awal, $tgl_akhir]); $avg_durasi = (float)$dur->fetchColumn();

    // Mobil terlaris
    $best = $pdo->prepare("
        SELECT m.nama, COUNT(b.id) AS jml FROM booking b JOIN mobil m ON m.id=b.mobil_id
        WHERE DATE(b.created_at) BETWEEN ? AND ? GROUP BY m.id ORDER BY jml DESC LIMIT 1
    ");
    $best->execute([$tgl_awal, $tgl_akhir]); $best_car = $best->fetch();

    // Revenue per hari (chart)
    $chart = $pdo->prepare("
        SELECT DATE(t.created_at) AS tgl, SUM(t.jumlah) AS total
        FROM transaksi t JOIN booking b ON b.id=t.booking_id
        WHERE t.status='verified' AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY DATE(t.created_at) ORDER BY tgl
    ");
    $chart->execute([$tgl_awal, $tgl_akhir]);
    $chart_data = $chart->fetchAll();
    $max_chart = max(array_column($chart_data, 'total') ?: [1]);

    // Booking per status (pie)
    $status_data = [];
    foreach (['pending','confirmed','berlangsung','selesai','dibatalkan'] as $s) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE status=? AND DATE(created_at) BETWEEN ? AND ?");
        $c->execute([$s, $tgl_awal, $tgl_akhir]);
        $status_data[$s] = (int)$c->fetchColumn();
    }

    // Top 5 mobil
    $top5 = $pdo->prepare("
        SELECT m.nama, COUNT(b.id) AS jml, COALESCE(SUM(CASE WHEN t.status='verified' THEN t.jumlah ELSE 0 END),0) AS revenue
        FROM mobil m LEFT JOIN booking b ON b.mobil_id=m.id AND DATE(b.created_at) BETWEEN ? AND ?
        LEFT JOIN transaksi t ON t.booking_id=b.id
        GROUP BY m.id ORDER BY jml DESC LIMIT 5
    ");
    $top5->execute([$tgl_awal, $tgl_akhir]);
    $top5_data = $top5->fetchAll();
} catch (PDOException $e) {
    $total_revenue=0; $total_booking=0; $avg_durasi=0; $best_car=null;
    $chart_data=[]; $max_chart=1; $status_data=[]; $top5_data=[];
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1400px] mx-auto space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Laporan Analitik</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Periode: <?= format_tanggal($tgl_awal) ?> – <?= format_tanggal($tgl_akhir) ?>
            </p>
        </div>
        <form method="GET">
            <select name="periode" onchange="this.form.submit()"
                    class="px-4 py-3 rounded-xl border border-outline-variant bg-surface font-label-md text-label-md focus:border-primary outline-none">
                <option value="7hari"     <?= $periode==='7hari' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                <option value="30hari"    <?= $periode==='30hari' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                <option value="bulan_ini" <?= $periode==='bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="tahun_ini" <?= $periode==='tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
            </select>
        </form>
    </div>

    <!-- Kartu Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php
        $kartu = [
            ['payments','Total Pendapatan',format_rupiah($total_revenue),'bg-primary-container text-on-primary-container'],
            ['book_online','Total Booking',$total_booking,'bg-tertiary-fixed text-on-tertiary-fixed'],
            ['timelapse','Rata-rata Durasi',number_format($avg_durasi,1).' hari','bg-surface-container-high text-on-surface-variant'],
            ['directions_car','Mobil Terlaris',$best_car['nama'] ?? '-','bg-secondary-fixed text-on-secondary-fixed'],
        ];
        foreach ($kartu as [$ikon,$lbl,$val,$cls]): ?>
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <div class="w-10 h-10 rounded-full <?= $cls ?> flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-[20px]"><?= $ikon ?></span>
                </div>
                <p class="font-label-md text-label-md text-on-surface-variant mb-1"><?= $lbl ?></p>
                <h3 class="font-headline-md text-headline-md text-primary font-bold truncate"><?= $val ?></h3>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart Revenue -->
        <div class="lg:col-span-2 bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-6">Grafik Pendapatan</h2>
            <?php if (empty($chart_data)): ?>
                <p class="text-center py-12 text-on-surface-variant font-body-md text-body-md">Belum ada data pendapatan pada periode ini.</p>
            <?php else: ?>
                <div class="flex items-end gap-2 h-52 overflow-x-auto">
                    <?php foreach ($chart_data as $c):
                        $pct = $max_chart > 0 ? ($c['total']/$max_chart*100) : 0; ?>
                        <div class="flex-1 min-w-[30px] flex flex-col items-center gap-2">
                            <span class="font-label-sm text-label-sm text-on-surface-variant text-[10px] whitespace-nowrap">
                                <?= format_angka_pendek($c['total']) ?>
                            </span>
                            <div class="w-full bg-primary rounded-t-lg transition-all hover:bg-primary/80"
                                 style="height: <?= max(4,$pct) ?>%; min-height:4px;" title="<?= format_rupiah($c['total']) ?>"></div>
                            <span class="font-label-sm text-label-sm text-on-surface-variant text-[10px] whitespace-nowrap">
                                <?= date('d/m', strtotime($c['tgl'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Status Booking -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-6">Status Booking</h2>
            <div class="space-y-3">
                <?php
                $total_status = array_sum($status_data) ?: 1;
                $status_labels = ['pending'=>['Pending','bg-secondary-container'],'confirmed'=>['Dikonfirmasi','bg-primary'],'berlangsung'=>['Berlangsung','bg-blue-500'],'selesai'=>['Selesai','bg-green-500'],'dibatalkan'=>['Dibatalkan','bg-error']];
                foreach ($status_labels as $k=>[$lbl,$warna]):
                    $jml = $status_data[$k] ?? 0;
                    $pct = round($jml/$total_status*100);
                ?>
                    <div>
                        <div class="flex justify-between font-label-sm text-label-sm mb-1">
                            <span class="text-on-surface"><?= $lbl ?></span>
                            <span class="text-on-surface-variant"><?= $jml ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden">
                            <div class="h-full <?= $warna ?> rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Top 5 Mobil -->
    <div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <h2 class="font-headline-sm text-headline-sm text-primary font-bold p-6 pb-4">Top 5 Armada Terlaris</h2>
        <table class="w-full text-left">
            <thead class="bg-surface-container-low border-y border-outline-variant/30">
                <tr>
                    <?php foreach (['#','Nama Mobil','Jumlah Booking','Pendapatan'] as $h): ?>
                        <th class="px-6 py-3 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                <?php if (empty($top5_data)): ?>
                    <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">Belum ada data.</td></tr>
                <?php else: foreach ($top5_data as $i=>$t): ?>
                    <tr class="hover:bg-surface-container-lowest transition-colors">
                        <td class="px-6 py-4">
                            <span class="w-7 h-7 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-bold text-sm"><?= $i+1 ?></span>
                        </td>
                        <td class="px-6 py-4 font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($t['nama']) ?></td>
                        <td class="px-6 py-4 font-body-md text-body-md text-on-surface"><?= $t['jml'] ?>x</td>
                        <td class="px-6 py-4 font-label-md text-label-md text-primary font-bold"><?= format_rupiah($t['revenue']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
