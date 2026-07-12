<?php
// ============================================================
// admin/pelanggan/index.php — Manajemen Pelanggan
// ============================================================
$page_title_admin = 'Manajemen Pelanggan';
$menu_aktif       = 'pelanggan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$cari    = bersihkan($_GET['cari']   ?? '');
$status  = bersihkan($_GET['status'] ?? '');
$halaman = max(1,(int)($_GET['halaman'] ?? 1));
$per_hal = 15;

$where  = ['1=1'];
$params = [];
if ($cari) {
    $where[] = "(u.nama LIKE ? OR u.email LIKE ? OR u.no_hp LIKE ?)";
    $params[] = "%$cari%"; $params[] = "%$cari%"; $params[] = "%$cari%";
}
if ($status) { $where[] = "u.status = ?"; $params[] = $status; }
$where_sql = implode(' AND ', $where);

// Proses blokir/aktifkan
if (isset($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    try {
        $cur = $pdo->prepare("SELECT status FROM users WHERE id=?"); $cur->execute([$uid]);
        $s = $cur->fetchColumn();
        $baru = $s === 'blokir' ? 'aktif' : 'blokir';
        $pdo->prepare("UPDATE users SET status=? WHERE id=?")->execute([$baru, $uid]);
        set_flash('sukses', $baru === 'blokir' ? 'Pelanggan diblokir.' : 'Pelanggan diaktifkan.');
    } catch (PDOException $e) { set_flash('error','Gagal.'); }
    header('Location: ' . ADMIN_URL . '/pelanggan/index.php');
    exit;
}

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where_sql");
    $cnt->execute($params);
    $total  = (int)$cnt->fetchColumn();
    $paging = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT u.*,
               COUNT(DISTINCT b.id) AS total_sewa,
               COALESCE(SUM(CASE WHEN t.status='verified' THEN t.jumlah ELSE 0 END),0) AS total_belanja
        FROM users u
        LEFT JOIN booking   b ON b.user_id = u.id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        WHERE $where_sql
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $pelanggan = $stmt->fetchAll();

    $total_all    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_aktif  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='aktif'")->fetchColumn();
} catch (PDOException $e) {
    $pelanggan=[]; $total=0; $paging=pagination(0,$per_hal,1); $total_all=0; $total_aktif=0;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1400px] mx-auto">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Manajemen Pelanggan</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                <?= $total_all ?> pelanggan · <?= $total_aktif ?> aktif
            </p>
        </div>
    </div>

    <?= render_flash() ?>

    <!-- Cari -->
    <form method="GET" class="flex flex-col md:flex-row gap-3 mb-6">
        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari nama, email, atau no. HP..."
                   class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant bg-surface
                          font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
        </div>
        <select name="status" onchange="this.form.submit()"
                class="px-4 py-3 rounded-xl border border-outline-variant bg-surface font-label-md text-label-md focus:border-primary outline-none">
            <option value="">Semua Status</option>
            <option value="aktif"    <?= $status==='aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="blokir" <?= $status==='blokir' ? 'selected' : '' ?>>Diblokir</option>
        </select>
        <button type="submit" class="px-6 py-3 bg-primary text-on-primary rounded-xl font-label-md text-label-md hover:bg-primary/90 transition-colors">Cari</button>
    </form>

    <!-- Tabel -->
    <div class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.05)] border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <?php foreach (['Pelanggan','Kontak','Total Sewa','Total Belanja','Status','Aksi'] as $h): ?>
                            <th class="px-6 py-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    <?php if (empty($pelanggan)): ?>
                        <tr><td colspan="6" class="py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl block mb-3">group</span>
                            Tidak ada pelanggan.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($pelanggan as $p): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container
                                                    flex items-center justify-center font-bold overflow-hidden shrink-0">
                                            <?php if ($p['foto_profil']): ?>
                                                <img src="<?= url_gambar($p['foto_profil'], 'profil') ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?= inisial($p['nama']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($p['nama']) ?></p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">ID #<?= $p['id'] ?> · <?= waktu_lalu($p['created_at']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($p['email'] ?? '-') ?></p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></p>
                                </td>
                                <td class="px-6 py-4 font-label-md text-label-md text-on-surface"><?= $p['total_sewa'] ?>x</td>
                                <td class="px-6 py-4 font-label-md text-label-md text-primary font-semibold"><?= format_rupiah($p['total_belanja']) ?></td>
                                <td class="px-6 py-4">
                                    <?php if (($p['status'] ?? 'aktif') === 'blokir'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-error-container text-on-error-container">Diblokir</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-green-50 text-green-800 border border-green-200">Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="<?= ADMIN_URL ?>/pelanggan/detail.php?id=<?= $p['id'] ?>"
                                           class="p-1.5 text-primary hover:bg-surface-container rounded-lg transition-colors" title="Detail">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </a>
                                        <a href="?toggle=<?= $p['id'] ?>"
                                           onclick="return confirm('Ubah status pelanggan ini?')"
                                           class="p-1.5 <?= ($p['status']??'aktif')==='blokir' ? 'text-green-600' : 'text-error' ?> hover:bg-surface-container rounded-lg transition-colors"
                                           title="<?= ($p['status']??'aktif')==='blokir' ? 'Aktifkan' : 'Blokir' ?>">
                                            <span class="material-symbols-outlined text-[20px]"><?= ($p['status']??'aktif')==='blokir' ? 'lock_open' : 'block' ?></span>
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
    <?= render_pagination($paging, ADMIN_URL . '/pelanggan/index.php?' . http_build_query(array_filter(['cari'=>$cari,'status'=>$status]))) ?>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
