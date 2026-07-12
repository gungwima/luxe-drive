<?php
// ============================================================
// admin/armada/hapus.php — Konfirmasi & Proses Hapus Mobil
// ============================================================
$page_title_admin = 'Hapus Mobil';
$menu_aktif       = 'armada';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . ADMIN_URL . '/armada/index.php'); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM mobil WHERE id=?");
    $stmt->execute([$id]);
    $mobil = $stmt->fetch();
} catch (PDOException $e) { $mobil = null; }

if (!$mobil) { header('Location: ' . ADMIN_URL . '/armada/index.php'); exit; }

// Cek booking aktif
try {
    $cek = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE mobil_id=? AND status IN ('pending','confirmed','berlangsung')");
    $cek->execute([$id]);
    $ada_booking_aktif = (int)$cek->fetchColumn();

    $cek_total = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE mobil_id=?");
    $cek_total->execute([$id]);
    $total_booking = (int)$cek_total->fetchColumn();
} catch (PDOException $e) { $ada_booking_aktif=0; $total_booking=0; }

// Proses hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Sesi tidak valid.');
        header('Location: ' . ADMIN_URL . '/armada/index.php');
        exit;
    }

    if (isset($_POST['nonaktifkan'])) {
        $pdo->prepare("UPDATE mobil SET status='nonaktif' WHERE id=?")->execute([$id]);
        set_flash('sukses', 'Mobil berhasil dinonaktifkan (disembunyikan dari website).');
        header('Location: ' . ADMIN_URL . '/armada/index.php');
        exit;
    }

    if (isset($_POST['hapus_permanen'])) {
        $konfirmasi = bersihkan($_POST['konfirmasi_nama'] ?? '');
        if ($konfirmasi !== $mobil['nama']) {
            set_flash('error', 'Nama mobil tidak cocok. Hapus dibatalkan.');
            header('Location: ' . ADMIN_URL . '/armada/hapus.php?id=' . $id);
            exit;
        }
        if ($ada_booking_aktif > 0) {
            set_flash('error', 'Tidak bisa hapus: ada booking aktif.');
            header('Location: ' . ADMIN_URL . '/armada/index.php');
            exit;
        }
        try {
            // Hapus foto
            $fotos = $pdo->prepare("SELECT foto FROM foto_mobil WHERE mobil_id=?");
            $fotos->execute([$id]);
            foreach ($fotos->fetchAll() as $f) { hapus_file('mobil', $f['foto']); }
            if ($mobil['foto_utama']) hapus_file('mobil', $mobil['foto_utama']);

            $pdo->prepare("DELETE FROM mobil WHERE id=?")->execute([$id]);
            set_flash('sukses', "Mobil \"{$mobil['nama']}\" berhasil dihapus permanen.");
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
        header('Location: ' . ADMIN_URL . '/armada/index.php');
        exit;
    }
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen flex items-center justify-center p-8">
    <!-- Modal Overlay -->
    <div class="w-full max-w-lg bg-surface rounded-2xl shadow-[0px_8px_32px_rgba(26,43,60,0.15)]
                border border-outline-variant/30 overflow-hidden">

        <!-- Header -->
        <div class="bg-error-container px-6 py-5 flex items-center gap-3">
            <span class="material-symbols-outlined text-error text-[28px]">delete_forever</span>
            <h1 class="font-headline-sm text-headline-sm text-on-error-container">Hapus Kendaraan</h1>
        </div>

        <div class="p-6">
            <!-- Info Mobil -->
            <div class="flex items-center gap-4 p-4 bg-surface-container-low rounded-xl mb-6">
                <img src="<?= url_foto_mobil($mobil['foto_utama']) ?>"
                     alt="<?= htmlspecialchars($mobil['nama']) ?>"
                     class="w-20 h-16 rounded-lg object-cover shrink-0">
                <div>
                    <p class="font-headline-sm text-headline-sm text-on-surface font-semibold">
                        <?= htmlspecialchars($mobil['nama']) ?>
                    </p>
                    <p class="font-label-sm text-label-sm text-on-surface-variant">
                        <?= htmlspecialchars($mobil['no_plat']) ?> ·
                        <?= format_rupiah($mobil['harga_hari']) ?>/hari
                    </p>
                </div>
            </div>

            <!-- Warning -->
            <?php if ($ada_booking_aktif > 0): ?>
                <div class="flex items-start gap-3 p-4 bg-error-container rounded-xl mb-6 text-on-error-container">
                    <span class="material-symbols-outlined shrink-0">warning</span>
                    <div>
                        <p class="font-label-md text-label-md font-semibold">Ada <?= $ada_booking_aktif ?> Booking Aktif!</p>
                        <p class="font-body-md text-body-md mt-1">
                            Tidak dapat dihapus permanen. Silakan gunakan opsi Nonaktifkan.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex items-start gap-3 p-4 bg-secondary-fixed/30 rounded-xl mb-6">
                    <span class="material-symbols-outlined text-secondary shrink-0">info</span>
                    <div class="font-body-md text-body-md text-on-surface-variant">
                        <p>Mobil ini memiliki <strong class="text-on-surface"><?= $total_booking ?> riwayat booking</strong>.</p>
                        <p class="mt-1">Hapus permanen akan menghapus data mobil namun <strong class="text-on-surface">riwayat booking tetap tersimpan</strong>.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>

                <!-- Opsi Nonaktifkan (Rekomendasi) -->
                <div class="p-4 border-2 border-secondary-container rounded-xl bg-secondary-container/5">
                    <p class="font-label-sm text-label-sm text-secondary font-bold uppercase tracking-wider mb-2">
                        ⭐ Rekomendasi
                    </p>
                    <p class="font-body-md text-body-md text-on-surface mb-3">
                        <strong>Nonaktifkan saja</strong> — Mobil disembunyikan dari website,
                        semua data dan histori tetap tersimpan.
                    </p>
                    <button type="submit" name="nonaktifkan"
                            class="w-full py-3 bg-secondary-container text-on-secondary-container
                                   rounded-xl font-label-md text-label-md font-bold
                                   hover:opacity-90 transition-opacity">
                        Nonaktifkan Saja (Disarankan)
                    </button>
                </div>

                <?php if ($ada_booking_aktif === 0): ?>
                    <!-- Hapus Permanen -->
                    <div class="p-4 border border-error/30 rounded-xl">
                        <p class="font-label-md text-label-md text-error font-semibold mb-3">
                            Hapus Permanen
                        </p>
                        <p class="font-label-sm text-label-sm text-on-surface-variant mb-3">
                            Ketik nama mobil untuk konfirmasi:
                            <strong class="text-on-surface"><?= htmlspecialchars($mobil['nama']) ?></strong>
                        </p>
                        <input type="text" name="konfirmasi_nama"
                               placeholder="Ketik nama mobil di sini..."
                               class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                      font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                      focus:outline-none focus:border-error focus:ring-1 focus:ring-error
                                      transition-all mb-3">
                        <button type="submit" name="hapus_permanen"
                                class="w-full py-3 bg-error text-on-error rounded-xl
                                       font-label-md text-label-md font-bold
                                       hover:bg-error/90 transition-colors">
                            Ya, Hapus Permanen
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Batal -->
                <a href="<?= ADMIN_URL ?>/armada/index.php"
                   class="block text-center py-3 border border-outline-variant text-on-surface
                          rounded-xl font-label-md text-label-md hover:bg-surface-container
                          transition-colors">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
