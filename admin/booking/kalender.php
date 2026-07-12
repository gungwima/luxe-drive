<?php
// ============================================================
// admin/booking/kalender.php — Kalender Ketersediaan Armada
// ============================================================
$page_title_admin = 'Kalender Armada';
$menu_aktif       = 'kalender';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

// Bulan aktif
$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));
if ($bulan < 1) { $bulan = 12; $tahun--; }
if ($bulan > 12){ $bulan = 1;  $tahun++; }

$jml_hari    = (int)date('t', mktime(0,0,0,$bulan,1,$tahun));
$nama_bulan  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$bulan];
$awal_bulan  = sprintf('%04d-%02d-01', $tahun, $bulan);
$akhir_bulan = sprintf('%04d-%02d-%02d', $tahun, $bulan, $jml_hari);

try {
    $mobil_list = $pdo->query("SELECT id,nama,no_plat,foto_utama FROM mobil WHERE status!='nonaktif' ORDER BY nama")->fetchAll();

    // Ambil semua booking di bulan ini
    $stmt = $pdo->prepare("
        SELECT b.mobil_id, b.kode_booking, b.status,
               DATE(b.tgl_ambil)   AS mulai,
               DATE(b.tgl_kembali) AS selesai,
               u.nama AS nama_user
        FROM booking b
        LEFT JOIN users u ON u.id = b.user_id
        WHERE b.status IN ('confirmed','berlangsung','pending')
        AND NOT (DATE(b.tgl_kembali) < ? OR DATE(b.tgl_ambil) > ?)
    ");
    $stmt->execute([$awal_bulan, $akhir_bulan]);
    $bookings = $stmt->fetchAll();

    // Kelompokkan per mobil
    $booking_map = [];
    foreach ($bookings as $b) {
        $booking_map[$b['mobil_id']][] = $b;
    }
} catch (PDOException $e) { $mobil_list=[]; $booking_map=[]; }

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1600px] mx-auto">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Kalender Armada</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Ketersediaan kendaraan per tanggal.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="?bulan=<?= $bulan-1 ?>&tahun=<?= $tahun ?>"
               class="w-10 h-10 border border-outline-variant rounded-lg flex items-center justify-center
                      hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </a>
            <h2 class="font-headline-sm text-headline-sm text-on-surface min-w-[160px] text-center font-bold">
                <?= $nama_bulan ?> <?= $tahun ?>
            </h2>
            <a href="?bulan=<?= $bulan+1 ?>&tahun=<?= $tahun ?>"
               class="w-10 h-10 border border-outline-variant rounded-lg flex items-center justify-center
                      hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </a>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex gap-4 mb-4 flex-wrap">
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-primary"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Confirmed</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-blue-500"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Berlangsung</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-secondary-container"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Pending</span>
        </div>
    </div>

    <!-- Timeline -->
    <div class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="border-collapse w-full">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-surface-container-low border-b border-r
                                   border-outline-variant/30 p-3 text-left min-w-[180px]">
                            <span class="font-label-md text-label-md text-on-surface-variant">Kendaraan</span>
                        </th>
                        <?php for ($d=1; $d<=$jml_hari; $d++):
                            $dow = date('D', mktime(0,0,0,$bulan,$d,$tahun));
                            $is_weekend = in_array($dow, ['Sat','Sun']);
                            $is_today = ($d==date('j') && $bulan==date('n') && $tahun==date('Y'));
                        ?>
                            <th class="border-b border-r border-outline-variant/20 p-1 min-w-[36px] text-center
                                       <?= $is_weekend ? 'bg-surface-container-low' : 'bg-surface' ?>
                                       <?= $is_today ? 'bg-primary-fixed' : '' ?>">
                                <div class="font-label-sm text-label-sm <?= $is_today ? 'text-primary font-bold' : 'text-on-surface-variant' ?>"><?= $d ?></div>
                                <div class="text-[9px] text-on-surface-variant uppercase"><?= substr($dow,0,1) ?></div>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mobil_list)): ?>
                        <tr><td colspan="<?= $jml_hari+1 ?>" class="p-8 text-center text-on-surface-variant">Belum ada armada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mobil_list as $m): ?>
                            <tr class="group">
                                <td class="sticky left-0 z-10 bg-surface group-hover:bg-surface-container-low
                                           border-b border-r border-outline-variant/30 p-2">
                                    <div class="flex items-center gap-2">
                                        <img src="<?= url_foto_mobil($m['foto_utama']) ?>"
                                             class="w-10 h-8 rounded object-cover shrink-0">
                                        <div class="min-w-0">
                                            <p class="font-label-sm text-label-sm text-on-surface font-semibold truncate"><?= htmlspecialchars($m['nama']) ?></p>
                                            <p class="text-[10px] text-on-surface-variant truncate"><?= htmlspecialchars($m['no_plat']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <?php
                                // Bangun map tanggal terisi (per hari dalam bulan yang ditampilkan)
                                $terisi = [];
                                $bulan_tampil = date('Y-m', strtotime($awal_bulan)); // cth 2026-07
                                foreach ($booking_map[$m['id']] ?? [] as $bk) {
                                    $ts_mulai   = strtotime($bk['mulai']);
                                    $ts_selesai = strtotime($bk['selesai']);

                                    // Jika booking mulai sebelum bulan ini → mulai dari tanggal 1
                                    if (date('Y-m', $ts_mulai) < $bulan_tampil) {
                                        $hari_mulai = 1;
                                    } else {
                                        $hari_mulai = (int)date('j', $ts_mulai);
                                    }

                                    // Jika booking selesai setelah bulan ini → sampai akhir bulan
                                    if (date('Y-m', $ts_selesai) > $bulan_tampil) {
                                        $hari_selesai = $jml_hari;
                                    } else {
                                        $hari_selesai = (int)date('j', $ts_selesai);
                                    }

                                    // Isi hanya jika range valid dalam bulan ini
                                    for ($x = $hari_mulai; $x <= $hari_selesai; $x++) {
                                        if ($x >= 1 && $x <= $jml_hari) {
                                            $terisi[$x] = $bk['status'];
                                        }
                                    }
                                }
                                for ($d=1; $d<=$jml_hari; $d++):
                                    $st = $terisi[$d] ?? null;
                                    $warna = match($st) {
                                        'confirmed'   => 'bg-primary',
                                        'berlangsung' => 'bg-blue-500',
                                        'pending'     => 'bg-secondary-container',
                                        default       => '',
                                    };
                                ?>
                                    <td class="border-b border-r border-outline-variant/10 p-0 h-10 relative">
                                        <?php if ($warna): ?>
                                            <div class="absolute inset-0.5 rounded <?= $warna ?> opacity-90"
                                                 title="<?= $st ?>"></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="font-label-sm text-label-sm text-on-surface-variant mt-4 text-center">
        Geser ke kanan untuk melihat tanggal berikutnya →
    </p>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
