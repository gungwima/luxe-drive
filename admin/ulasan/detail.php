<?php
// ============================================================
// admin/ulasan/detail.php — Detail Ulasan
// ============================================================
$page_title_admin = 'Detail Ulasan';
$menu_aktif       = 'ulasan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . ADMIN_URL . '/ulasan/index.php'); exit; }

// Aksi approve/reject
if (isset($_GET['aksi'])) {
    $st = $_GET['aksi']==='approve' ? 'approved' : ($_GET['aksi']==='reject' ? 'rejected' : null);
    if ($st) {
        $pdo->prepare("UPDATE ulasan SET status=? WHERE id=?")->execute([$st, $id]);
        set_flash('sukses', $st==='approved' ? 'Ulasan disetujui.' : 'Ulasan ditolak.');
    }
    header('Location: ' . ADMIN_URL . '/ulasan/detail.php?id=' . $id);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.*, us.nama AS nama_user, us.email, us.foto_profil,
               m.nama AS nama_mobil, m.foto_utama,
               b.kode_booking, b.tgl_ambil, b.tgl_kembali,
               ub.balasan, ub.created_at AS tgl_balas
        FROM ulasan u
        JOIN users us ON us.id=u.user_id
        JOIN mobil m  ON m.id=u.mobil_id
        LEFT JOIN booking b ON b.id=u.booking_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id=u.id
        WHERE u.id=? LIMIT 1
    ");
    $stmt->execute([$id]);
    $ul = $stmt->fetch();
} catch (PDOException $e) { $ul = null; }

if (!$ul) { header('Location: ' . ADMIN_URL . '/ulasan/index.php'); exit; }

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[900px] mx-auto">

    <div class="flex items-center gap-2 mb-6 text-on-surface-variant font-label-md text-label-md">
        <a href="<?= ADMIN_URL ?>/ulasan/index.php" class="hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Kembali ke Ulasan
        </a>
    </div>

    <?= render_flash() ?>

    <div class="bg-surface rounded-xl p-8 shadow-sm border border-outline-variant/30">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 mb-6 pb-6 border-b border-outline-variant/30">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-primary text-on-primary flex items-center justify-center
                            font-bold text-xl overflow-hidden shrink-0">
                    <?php if ($ul['foto_profil']): ?>
                        <img src="<?= url_gambar($ul['foto_profil'], 'profil') ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= inisial($ul['nama_user']) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="font-headline-sm text-headline-sm text-on-surface"><?= htmlspecialchars($ul['nama_user']) ?></p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($ul['email']) ?></p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= waktu_lalu($ul['created_at']) ?></p>
                </div>
            </div>
            <?= badge_status_ulasan($ul['status']) ?>
        </div>

        <!-- Mobil & Booking -->
        <div class="flex items-center gap-4 mb-6 p-4 bg-surface-container-low rounded-xl">
            <img src="<?= url_foto_mobil($ul['foto_utama']) ?>" class="w-20 h-14 rounded-lg object-cover shrink-0">
            <div>
                <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($ul['nama_mobil']) ?></p>
                <p class="font-label-sm text-label-sm text-on-surface-variant">
                    <?= htmlspecialchars($ul['kode_booking'] ?? '-') ?> ·
                    <?= $ul['tgl_ambil'] ? format_tanggal($ul['tgl_ambil']) : '-' ?>
                </p>
            </div>
        </div>

        <!-- Rating & Komentar -->
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-3">
                <div class="flex"><?= tampil_bintang($ul['rating']) ?></div>
                <span class="font-headline-sm text-headline-sm text-primary font-bold"><?= $ul['rating'] ?>.0</span>
            </div>
            <?php if ($ul['judul']): ?>
                <p class="font-headline-sm text-headline-sm text-on-surface mb-2"><?= htmlspecialchars($ul['judul']) ?></p>
            <?php endif; ?>
            <p class="font-body-md text-body-md text-on-surface leading-relaxed italic">"<?= nl2br(htmlspecialchars($ul['komentar'])) ?>"</p>
        </div>

        <!-- Foto Ulasan -->
        <?php $foto_ul = array_filter([$ul['foto_1'],$ul['foto_2'],$ul['foto_3']]); if ($foto_ul): ?>
            <div class="flex gap-3 mb-6">
                <?php foreach ($foto_ul as $f): ?>
                    <a href="<?= url_gambar($f, 'ulasan') ?>" target="_blank">
                        <img src="<?= url_gambar($f, 'ulasan') ?>"
                             class="w-28 h-24 rounded-lg object-cover border border-outline-variant/30 hover:opacity-90 transition-opacity">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Balasan Admin -->
        <?php if ($ul['balasan']): ?>
            <div class="mb-6 ml-4 pl-4 border-l-2 border-secondary-container bg-surface-container-low rounded-lg p-4">
                <p class="font-label-sm text-label-sm text-secondary font-bold mb-1">Balasan Tim <?= APP_NAME ?></p>
                <p class="font-body-md text-body-md text-on-surface-variant"><?= nl2br(htmlspecialchars($ul['balasan'])) ?></p>
                <p class="font-label-sm text-label-sm text-on-surface-variant mt-2"><?= waktu_lalu($ul['tgl_balas']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Aksi -->
        <div class="flex gap-3 pt-6 border-t border-outline-variant/30">
            <?php if ($ul['status'] === 'pending'): ?>
                <a href="?id=<?= $id ?>&aksi=approve"
                   class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-xl
                          font-label-md text-label-md font-bold hover:bg-green-700 transition-colors">
                    <span class="material-symbols-outlined">check_circle</span> Setujui
                </a>
                <a href="?id=<?= $id ?>&aksi=reject"
                   class="flex items-center gap-2 px-6 py-3 bg-error text-on-error rounded-xl
                          font-label-md text-label-md font-bold hover:bg-error/90 transition-colors">
                    <span class="material-symbols-outlined">cancel</span> Tolak
                </a>
            <?php endif; ?>
            <a href="<?= ADMIN_URL ?>/ulasan/balas.php?id=<?= $id ?>"
               class="flex items-center gap-2 px-6 py-3 border border-primary text-primary rounded-xl
                      font-label-md text-label-md hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined">reply</span>
                <?= $ul['balasan'] ? 'Edit Balasan' : 'Balas Ulasan' ?>
            </a>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
