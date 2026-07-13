<?php
// ============================================================
// user/riwayat_pembayaran.php — Riwayat Semua Transaksi User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user            = get_user_login();
$menu_user_aktif = 'pembayaran';

$halaman = max(1,(int)($_GET['halaman'] ?? 1));
$per_hal = 15;

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM transaksi t JOIN booking b ON b.id=t.booking_id WHERE b.user_id=?");
    $cnt->execute([$user['id']]);
    $total   = (int)$cnt->fetchColumn();
    $paging  = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT t.*, b.kode_booking, b.tgl_ambil, b.tgl_kembali,
               m.nama AS nama_mobil
        FROM transaksi t
        JOIN booking b ON b.id = t.booking_id
        JOIN mobil   m ON m.id = b.mobil_id
        WHERE b.user_id = ?
        ORDER BY t.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute([$user['id']]);
    $transaksi = $stmt->fetchAll();

    // Total yang sudah terverifikasi
    $tot_v = $pdo->prepare("SELECT COALESCE(SUM(t.jumlah),0) FROM transaksi t JOIN booking b ON b.id=t.booking_id WHERE b.user_id=? AND t.status='verified'");
    $tot_v->execute([$user['id']]); $total_verified = (float)$tot_v->fetchColumn();
    $jml_trx = $total;
} catch (PDOException $e) {
    $transaksi=[]; $total=0; $total_verified=0; $jml_trx=0;
    $paging = pagination(0,$per_hal,1);
}

$page_title_user = 'Riwayat Pembayaran';
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
    </header>

    <main class="flex-1 px-margin-mobile md:px-margin-desktop py-8 max-w-container-max mx-auto w-full">
        <div class="mb-10">
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">
                Riwayat Pembayaran
            </h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Pantau semua transaksi pembayaran Anda.
            </p>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 flex justify-between items-center">
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant">Total Transaksi</p>
                    <p class="font-headline-md text-headline-md text-primary font-bold"><?= $jml_trx ?></p>
                </div>
                <span class="material-symbols-outlined text-primary text-[28px] opacity-80">receipt_long</span>
            </div>
            <div class="bg-primary text-on-primary rounded-xl p-6 flex justify-between items-center relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                <div class="relative z-10">
                    <p class="font-label-md text-label-md text-primary-fixed-dim">Total Dibayarkan</p>
                    <p class="font-headline-md text-headline-md text-on-primary font-bold">
                        <?= format_rupiah($total_verified) ?>
                    </p>
                </div>
                <span class="material-symbols-outlined text-secondary-container text-[28px] relative z-10">payments</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/30 flex justify-between items-center">
                <div>
                    <p class="font-label-md text-label-md text-on-surface-variant">Transaksi Terbaru</p>
                    <p class="font-headline-sm text-headline-sm text-on-surface font-bold">
                        <?= isset($transaksi[0]) ? format_tanggal($transaksi[0]['created_at']) : '—' ?>
                    </p>
                </div>
                <span class="material-symbols-outlined text-primary text-[28px] opacity-80">event</span>
            </div>
        </div>

        <!-- Tabel Transaksi -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant/20
                    shadow-[0px_4px_20px_rgba(26,43,60,0.08)] overflow-hidden">
            <div class="px-6 py-4 border-b border-outline-variant/30 bg-surface-container-low/50
                        flex justify-between items-center">
                <h2 class="font-headline-sm text-headline-sm text-primary">Semua Transaksi</h2>
            </div>

            <?php if (empty($transaksi)): ?>
                <div class="text-center py-16">
                    <span class="material-symbols-outlined text-5xl text-on-surface-variant mb-4 block">
                        payments
                    </span>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        Belum ada transaksi.
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface border-b border-outline-variant/30">
                                <?php foreach (['ID Transaksi','Tanggal','Kendaraan','Jumlah','Metode','Status',''] as $th): ?>
                                    <th class="px-5 py-4 font-label-sm text-label-sm text-on-surface-variant
                                               uppercase tracking-wider <?= $th==='' ? 'text-right' : '' ?>">
                                        <?= $th ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($transaksi as $t):
                                $metode_label = [
                                    'transfer_bank'  => 'Transfer Bank',
                                    'virtual_account'=> 'Virtual Account',
                                    'ewallet'        => 'E-Wallet',
                                    'cod'            => 'COD',
                                ][$t['metode']] ?? ucfirst($t['metode']);
                            ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors group">
                                    <td class="px-5 py-4">
                                        <span class="font-label-md text-label-md font-medium text-primary">
                                            #<?= htmlspecialchars($t['kode_transaksi']) ?>
                                        </span>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            <?= htmlspecialchars($t['kode_booking']) ?>
                                        </p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-body-md text-body-md text-on-surface">
                                            <?= format_tanggal($t['created_at']) ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded bg-surface-container-high
                                                        flex items-center justify-center border border-outline-variant/30">
                                                <span class="material-symbols-outlined text-sm text-primary">
                                                    directions_car
                                                </span>
                                            </div>
                                            <span class="font-label-md text-label-md text-on-surface">
                                                <?= htmlspecialchars($t['nama_mobil']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-label-md text-label-md font-bold text-primary">
                                        <?= format_rupiah($t['jumlah']) ?>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-label-sm text-label-sm text-on-surface-variant">
                                            <?= $metode_label ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <?= badge_status_transaksi($t['status']) ?>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <?php if ($t['bukti_bayar']): ?>
                                            <a href="<?= url_gambar($t['bukti_bayar'], 'bukti_bayar') ?>"
                                               target="_blank"
                                               class="text-on-surface-variant hover:text-primary transition-colors
                                                      opacity-0 group-hover:opacity-100"
                                               title="Lihat Bukti Bayar">
                                                <span class="material-symbols-outlined text-[20px]">
                                                    receipt
                                                </span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?= render_pagination($paging, BASE_URL . '/user/riwayat_pembayaran.php') ?>
    </main>
</div>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
