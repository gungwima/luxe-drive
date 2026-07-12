<?php
// ============================================================
// admin/ulasan/index.php — Moderasi Ulasan
// ============================================================
$page_title_admin = 'Manajemen Ulasan';
$menu_aktif       = 'ulasan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$status_filter = bersihkan($_GET['status'] ?? '');
$rating_filter = (int)($_GET['rating'] ?? 0);
$cari          = bersihkan($_GET['cari'] ?? '');
$halaman       = max(1,(int)($_GET['halaman'] ?? 1));
$per_hal       = 15;

// Proses approve / reject
if (isset($_GET['aksi'], $_GET['ulasan_id'])) {
    $uid  = (int)$_GET['ulasan_id'];
    $aksi = $_GET['aksi'];
    if (in_array($aksi, ['approve','reject'])) {
        $status_baru = $aksi === 'approve' ? 'approved' : 'rejected';
        try {
            $pdo->prepare("UPDATE ulasan SET status=? WHERE id=?")->execute([$status_baru, $uid]);
            set_flash('sukses', $aksi==='approve' ? 'Ulasan disetujui.' : 'Ulasan ditolak.');
        } catch (PDOException $e) { set_flash('error','Gagal.'); }
    }
    header('Location: ' . ADMIN_URL . '/ulasan/index.php');
    exit;
}

$where  = ['1=1'];
$params = [];
if ($status_filter) { $where[] = 'u.status = ?'; $params[] = $status_filter; }
if ($rating_filter) { $where[] = 'u.rating = ?'; $params[] = $rating_filter; }
if ($cari) {
    $where[] = "(us.nama LIKE ? OR m.nama LIKE ? OR u.komentar LIKE ?)";
    $params[] = "%$cari%"; $params[] = "%$cari%"; $params[] = "%$cari%";
}
$where_sql = implode(' AND ', $where);

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM ulasan u JOIN users us ON us.id=u.user_id JOIN mobil m ON m.id=u.mobil_id WHERE $where_sql");
    $cnt->execute($params);
    $total  = (int)$cnt->fetchColumn();
    $paging = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT u.*, us.nama AS nama_user, m.nama AS nama_mobil,
               ub.id AS ada_balasan
        FROM ulasan u
        JOIN users us ON us.id = u.user_id
        JOIN mobil m  ON m.id = u.mobil_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id = u.id
        WHERE $where_sql
        ORDER BY u.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $ulasan_list = $stmt->fetchAll();

    // Count per status
    $c_pending  = (int)$pdo->query("SELECT COUNT(*) FROM ulasan WHERE status='pending'")->fetchColumn();
    $c_approved = (int)$pdo->query("SELECT COUNT(*) FROM ulasan WHERE status='approved'")->fetchColumn();
} catch (PDOException $e) {
    $ulasan_list=[]; $total=0; $paging=pagination(0,$per_hal,1); $c_pending=0; $c_approved=0;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1400px] mx-auto">

    <div class="mb-6">
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Manajemen Ulasan</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">
            <?= $c_pending ?> menunggu tinjauan · <?= $c_approved ?> disetujui
        </p>
    </div>

    <?= render_flash() ?>

    <!-- Filter -->
    <form method="GET" class="bg-surface rounded-xl p-4 shadow-sm border border-outline-variant/30 mb-6
                              flex flex-col lg:flex-row gap-3 items-center">
        <div class="relative flex-1 w-full lg:max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari nama, mobil, komentar..."
                   class="w-full pl-10 pr-4 py-3 rounded-lg border border-outline-variant bg-surface
                          font-body-md text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
        </div>
        <select name="status" onchange="this.form.submit()"
                class="px-4 py-3 rounded-lg border border-outline-variant bg-surface font-body-md focus:border-primary outline-none">
            <option value="">Semua Status</option>
            <option value="pending"  <?= $status_filter==='pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $status_filter==='approved' ? 'selected' : '' ?>>Disetujui</option>
            <option value="rejected" <?= $status_filter==='rejected' ? 'selected' : '' ?>>Ditolak</option>
        </select>
        <select name="rating" onchange="this.form.submit()"
                class="px-4 py-3 rounded-lg border border-outline-variant bg-surface font-body-md focus:border-primary outline-none">
            <option value="0">Semua Rating</option>
            <?php foreach ([5,4,3,2,1] as $r): ?>
                <option value="<?= $r ?>" <?= $rating_filter===$r ? 'selected' : '' ?>><?= $r ?> Bintang</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-6 py-3 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors">Cari</button>
    </form>

    <!-- Tabel -->
    <div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/50">
                    <tr>
                        <?php foreach (['Pelanggan','Mobil','Rating','Komentar','Tanggal','Status','Aksi'] as $h): ?>
                            <th class="px-5 py-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider <?= $h==='Aksi' ? 'text-center' : '' ?>"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    <?php if (empty($ulasan_list)): ?>
                        <tr><td colspan="7" class="py-16 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl block mb-3">rate_review</span>
                            Tidak ada ulasan.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($ulasan_list as $ul): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-5 py-4 font-body-md text-body-md text-on-surface font-medium"><?= htmlspecialchars($ul['nama_user']) ?></td>
                                <td class="px-5 py-4 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($ul['nama_mobil']) ?></td>
                                <td class="px-5 py-4"><div class="flex"><?= tampil_bintang($ul['rating']) ?></div></td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="font-body-md text-body-md text-on-surface truncate" title="<?= htmlspecialchars($ul['komentar']) ?>">
                                        "<?= potong_teks($ul['komentar'], 40) ?>"
                                    </p>
                                </td>
                                <td class="px-5 py-4 font-label-sm text-label-sm text-on-surface-variant"><?= format_tanggal($ul['created_at']) ?></td>
                                <td class="px-5 py-4"><?= badge_status_ulasan($ul['status']) ?></td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <?php if ($ul['status'] === 'pending'): ?>
                                            <a href="?aksi=approve&ulasan_id=<?= $ul['id'] ?>"
                                               class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Setujui">
                                                <span class="material-symbols-outlined text-[20px]">check</span>
                                            </a>
                                            <a href="?aksi=reject&ulasan_id=<?= $ul['id'] ?>"
                                               class="p-1.5 text-error hover:bg-error/10 rounded-lg transition-colors" title="Tolak">
                                                <span class="material-symbols-outlined text-[20px]">close</span>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= ADMIN_URL ?>/ulasan/detail.php?id=<?= $ul['id'] ?>"
                                           class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Lihat">
                                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                                        </a>
                                        <a href="<?= ADMIN_URL ?>/ulasan/balas.php?id=<?= $ul['id'] ?>"
                                           class="p-1.5 <?= $ul['ada_balasan'] ? 'text-secondary' : 'text-on-surface-variant' ?> hover:bg-surface-container rounded-lg transition-colors"
                                           title="Balas">
                                            <span class="material-symbols-outlined text-[20px]">reply</span>
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
    <?= render_pagination($paging, ADMIN_URL . '/ulasan/index.php?' . http_build_query(array_filter(['status'=>$status_filter,'rating'=>$rating_filter,'cari'=>$cari]))) ?>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
