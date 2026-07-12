<?php
// ============================================================
// admin/armada/index.php — List Armada (READ)
// ============================================================
$page_title_admin = 'Manajemen Armada';
$menu_aktif       = 'armada';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$cari    = bersihkan($_GET['cari']   ?? '');
$jenis   = bersihkan($_GET['jenis']  ?? '');
$status  = bersihkan($_GET['status'] ?? '');
$halaman = max(1,(int)($_GET['halaman'] ?? 1));
$per_hal = 15;

$where  = ['1=1'];
$params = [];
if ($cari)   { $where[] = "(m.nama LIKE ? OR m.no_plat LIKE ?)"; $params[] = "%$cari%"; $params[] = "%$cari%"; }
if ($jenis)  { $where[] = "m.jenis = ?";  $params[] = $jenis; }
if ($status) { $where[] = "m.status = ?"; $params[] = $status; }
$where_sql = implode(' AND ', $where);

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM mobil m WHERE $where_sql");
    $cnt->execute($params);
    $total  = (int)$cnt->fetchColumn();
    $paging = pagination($total, $per_hal, $halaman);

    $stmt = $pdo->prepare("
        SELECT m.*, COALESCE(AVG(u.rating),0) AS avg_rating,
               COUNT(DISTINCT u.id) AS jml_ulasan
        FROM mobil m
        LEFT JOIN ulasan u ON u.mobil_id=m.id AND u.status='approved'
        WHERE $where_sql
        GROUP BY m.id
        ORDER BY m.created_at DESC
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $mobil_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $mobil_list=[]; $total=0; $paging=pagination(0,$per_hal,1);
}

// Proses hapus
if (isset($_GET['hapus']) && is_superadmin()) {
    $hid = (int)$_GET['hapus'];
    try {
        // Cek ada booking aktif
        $cek = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE mobil_id=? AND status IN ('pending','confirmed','berlangsung')");
        $cek->execute([$hid]);
        if ($cek->fetchColumn() > 0) {
            set_flash('error','Tidak bisa hapus: ada booking aktif untuk mobil ini.');
        } else {
            $pdo->prepare("DELETE FROM mobil WHERE id=?")->execute([$hid]);
            set_flash('sukses','Mobil berhasil dihapus.');
        }
    } catch (PDOException $e) {
        set_flash('error','Gagal menghapus mobil.');
    }
    header('Location: ' . ADMIN_URL . '/armada/index.php');
    exit;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen flex flex-col">
<main class="flex-1 flex flex-col">

    <!-- Header -->
    <div class="px-8 py-6 bg-background border-b border-outline-variant/30
                flex justify-between items-center">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary">Manajemen Armada</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                <?= $total ?> kendaraan terdaftar
            </p>
        </div>
        <a href="<?= ADMIN_URL ?>/armada/tambah.php"
           class="flex items-center gap-2 px-6 py-3 bg-primary text-on-primary
                  rounded-xl font-label-md text-label-md font-bold
                  hover:bg-primary/90 transition-colors shadow-md">
            <span class="material-symbols-outlined">add</span>
            Tambah Mobil
        </a>
    </div>

    <?= render_flash() ?>

    <!-- Filter & Cari -->
    <form method="GET" class="px-8 py-4 bg-background border-b border-outline-variant/20">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                       placeholder="Cari nama mobil, plat nomor..."
                       class="w-full pl-12 pr-4 py-3 rounded-xl border border-outline-variant
                              bg-surface focus:border-primary focus:ring-1 focus:ring-primary
                              font-body-md text-body-md outline-none transition-all">
            </div>
            <div class="flex gap-3">
                <select name="jenis" onchange="this.form.submit()"
                        class="px-4 py-3 rounded-xl border border-outline-variant bg-surface
                               font-label-md text-label-md focus:border-primary outline-none">
                    <option value="">Semua Jenis</option>
                    <?php foreach (['MPV','SUV','City Car','Minibus','Sedan','Pickup'] as $j): ?>
                        <option value="<?= $j ?>" <?= $jenis===$j ? 'selected' : '' ?>><?= $j ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" onchange="this.form.submit()"
                        class="px-4 py-3 rounded-xl border border-outline-variant bg-surface
                               font-label-md text-label-md focus:border-primary outline-none">
                    <option value="">Semua Status</option>
                    <?php foreach (['tersedia'=>'Tersedia','disewa'=>'Disewa','maintenance'=>'Maintenance','nonaktif'=>'Nonaktif'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $status===$v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"
                        class="px-4 py-3 bg-primary text-on-primary rounded-xl
                               font-label-md text-label-md hover:bg-primary/90 transition-colors">
                    Cari
                </button>
                <?php if ($cari || $jenis || $status): ?>
                    <a href="<?= ADMIN_URL ?>/armada/index.php"
                       class="px-4 py-3 border border-outline-variant text-on-surface-variant
                              rounded-xl font-label-md text-label-md hover:bg-surface-container
                              transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">close</span>
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Tabel -->
    <div class="px-8 py-6 flex-1">
        <div class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.05)]
                    border border-outline-variant/30 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="py-4 pl-6 pr-4 w-10">
                            <input type="checkbox" id="check-all"
                                   class="rounded border-outline-variant text-primary
                                          focus:ring-primary w-4 h-4 cursor-pointer">
                        </th>
                        <?php foreach (['Kendaraan','Jenis & Transmisi','No. Plat','Harga/Hari','Rating','Status','Aksi'] as $h): ?>
                            <th class="py-4 px-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                <?= $h ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php if (empty($mobil_list)): ?>
                        <tr><td colspan="8" class="py-16 text-center text-on-surface-variant font-body-md text-body-md">
                            <span class="material-symbols-outlined text-4xl block mb-3">directions_car</span>
                            Tidak ada armada ditemukan.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($mobil_list as $m): ?>
                            <tr class="hover:bg-surface-bright transition-colors group"
                                data-searchable>
                                <td class="py-4 pl-6 pr-4">
                                    <input type="checkbox" class="check-item rounded border-outline-variant
                                                                   text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-12 rounded-lg bg-surface-container-high
                                                    overflow-hidden shrink-0 border border-outline-variant/20">
                                            <img src="<?= url_foto_mobil($m['foto_utama']) ?>"
                                                 alt="<?= htmlspecialchars($m['nama']) ?>"
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface font-semibold">
                                                <?= htmlspecialchars($m['nama']) ?>
                                            </p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                <?= $m['tahun'] ?> · <?= $m['warna'] ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-body-md text-body-md text-on-surface"><?= $m['jenis'] ?></p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $m['transmisi'] ?></p>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="font-mono text-sm bg-surface-container px-2 py-1
                                                 rounded text-on-surface border border-outline-variant/50">
                                        <?= htmlspecialchars($m['no_plat']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-label-md text-label-md text-on-surface">
                                    <?= format_rupiah($m['harga_hari']) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-1">
                                        <?= tampil_bintang((float)$m['avg_rating']) ?>
                                        <span class="font-label-sm text-label-sm text-on-surface-variant ml-1">
                                            (<?= (int)$m['jml_ulasan'] ?>)
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <?= badge_status_mobil($m['status']) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-1">
                                        <a href="<?= ADMIN_URL ?>/armada/edit.php?id=<?= $m['id'] ?>"
                                           class="p-2 rounded-lg text-primary hover:bg-surface-container
                                                  transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                        <a href="<?= ADMIN_URL ?>/armada/hapus.php?id=<?= $m['id'] ?>"
                                           class="p-2 rounded-lg text-error hover:bg-error-container/10
                                                  transition-colors" title="Hapus">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= render_pagination($paging, ADMIN_URL . '/armada/index.php?' . http_build_query(array_filter(['cari'=>$cari,'jenis'=>$jenis,'status'=>$status]))) ?>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
