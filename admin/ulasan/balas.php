<?php
// ============================================================
// admin/ulasan/balas.php — Balas Ulasan Pelanggan
// ============================================================
$page_title_admin = 'Balas Ulasan';
$menu_aktif       = 'ulasan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();
$admin = get_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . ADMIN_URL . '/ulasan/index.php'); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT u.*, us.nama AS nama_user, m.nama AS nama_mobil,
               ub.id AS balasan_id, ub.balasan
        FROM ulasan u
        JOIN users us ON us.id=u.user_id
        JOIN mobil m  ON m.id=u.mobil_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id=u.id
        WHERE u.id=? LIMIT 1
    ");
    $stmt->execute([$id]);
    $ul = $stmt->fetch();
} catch (PDOException $e) { $ul = null; }

if (!$ul) { header('Location: ' . ADMIN_URL . '/ulasan/index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $balasan = bersihkan($_POST['balasan'] ?? '');
        if (!$balasan) $error = 'Balasan tidak boleh kosong.';
        else {
            try {
                if ($ul['balasan_id']) {
                    $pdo->prepare("UPDATE ulasan_balasan SET balasan=?, admin_id=? WHERE id=?")
                        ->execute([$balasan, $admin['id'], $ul['balasan_id']]);
                } else {
                    $pdo->prepare("INSERT INTO ulasan_balasan (ulasan_id,admin_id,balasan) VALUES (?,?,?)")
                        ->execute([$id, $admin['id'], $balasan]);
                }
                set_flash('sukses', 'Balasan berhasil disimpan.');
                header('Location: ' . ADMIN_URL . '/ulasan/detail.php?id=' . $id);
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan balasan.';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-2xl mx-auto">

    <div class="flex items-center gap-2 mb-6 text-on-surface-variant font-label-md text-label-md">
        <a href="<?= ADMIN_URL ?>/ulasan/detail.php?id=<?= $id ?>" class="hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Kembali
        </a>
    </div>

    <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-6">Balas Ulasan</h1>

    <?php if ($error): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
            <span class="material-symbols-outlined">error</span>
            <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
        </div>
    <?php endif; ?>

    <!-- Ulasan Asli -->
    <div class="bg-surface-container-low rounded-xl p-5 mb-6">
        <div class="flex items-center justify-between mb-2">
            <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($ul['nama_user']) ?></p>
            <div class="flex"><?= tampil_bintang($ul['rating']) ?></div>
        </div>
        <p class="font-label-sm text-label-sm text-on-surface-variant mb-2"><?= htmlspecialchars($ul['nama_mobil']) ?></p>
        <p class="font-body-md text-body-md text-on-surface italic">"<?= nl2br(htmlspecialchars($ul['komentar'])) ?>"</p>
    </div>

    <!-- Form Balasan -->
    <form method="POST" class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
        <?= csrf_field() ?>
        <label class="block font-label-md text-label-md text-on-surface font-semibold mb-2">
            Balasan Anda (sebagai <?= APP_NAME ?>)
        </label>
        <textarea name="balasan" rows="5"
                  placeholder="Tulis balasan yang sopan dan profesional..."
                  class="w-full px-4 py-3 border border-outline-variant rounded-xl font-body-md text-body-md
                         text-on-surface bg-surface-container-lowest focus:outline-none focus:border-primary
                         focus:ring-1 focus:ring-primary transition-all resize-none mb-4"><?= htmlspecialchars($ul['balasan'] ?? '') ?></textarea>
        <div class="flex justify-end gap-3">
            <a href="<?= ADMIN_URL ?>/ulasan/detail.php?id=<?= $id ?>"
               class="px-6 py-3 border border-outline-variant text-on-surface rounded-xl
                      font-label-md text-label-md hover:bg-surface-container transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="flex items-center gap-2 px-8 py-3 bg-primary text-on-primary rounded-xl
                           font-label-md text-label-md font-bold hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined text-[20px]">send</span>
                Kirim Balasan
            </button>
        </div>
    </form>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
