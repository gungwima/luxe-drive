<?php
// ============================================================
// admin/pengaturan/index.php — Pengaturan Sistem
// ============================================================
$page_title_admin = 'Pengaturan';
$menu_aktif       = 'pengaturan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();
$admin = get_admin_login();

$sukses = ''; $error = '';

// Ganti password sendiri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $lama    = $_POST['pass_lama'] ?? '';
        $baru    = $_POST['pass_baru'] ?? '';
        $konfirm = $_POST['pass_konfirm'] ?? '';

        try {
            $a = $pdo->prepare("SELECT password FROM admin WHERE id=?"); $a->execute([$admin['id']]);
            $pass_db = $a->fetchColumn();

            if (!verify_password($lama, $pass_db)) $error = 'Password lama salah.';
            elseif (strlen($baru) < 6) $error = 'Password baru minimal 6 karakter.';
            elseif ($baru !== $konfirm) $error = 'Konfirmasi tidak cocok.';
            else {
                $pdo->prepare("UPDATE admin SET password=? WHERE id=?")->execute([hash_password($baru), $admin['id']]);
                $sukses = 'Password berhasil diubah.';
            }
        } catch (PDOException $e) { $error = 'Gagal mengubah password.'; }
    }
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[900px] mx-auto">

    <div class="mb-8">
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Pengaturan</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Kelola akun dan preferensi sistem.</p>
    </div>

    <?php if ($sukses): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800">
            <span class="material-symbols-outlined">check_circle</span>
            <p class="font-label-md text-label-md"><?= bersihkan($sukses) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
            <span class="material-symbols-outlined">error</span>
            <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="space-y-6">

        <!-- Profil Admin -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">account_circle</span>
                Profil Admin
            </h2>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-2xl">
                    <?= inisial($admin['nama']) ?>
                </div>
                <div>
                    <p class="font-headline-sm text-headline-sm text-on-surface"><?= htmlspecialchars($admin['nama']) ?></p>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        <?= htmlspecialchars($admin['username'] ?? '') ?> ·
                        <span class="capitalize"><?= str_replace('_',' ', $admin['role'] ?? '') ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">lock</span>
                Ganti Password
            </h2>
            <form method="POST" class="space-y-4 max-w-md">
                <?= csrf_field() ?>
                <input type="hidden" name="ganti_password" value="1">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Password Lama</label>
                    <input type="password" name="pass_lama"
                           class="w-full px-4 py-2.5 border border-outline-variant rounded-lg font-body-md text-body-md
                                  text-on-surface bg-surface-container-lowest focus:outline-none focus:border-primary
                                  focus:ring-1 focus:ring-primary transition-all">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Password Baru</label>
                    <input type="password" name="pass_baru"
                           class="w-full px-4 py-2.5 border border-outline-variant rounded-lg font-body-md text-body-md
                                  text-on-surface bg-surface-container-lowest focus:outline-none focus:border-primary
                                  focus:ring-1 focus:ring-primary transition-all">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="pass_konfirm"
                           class="w-full px-4 py-2.5 border border-outline-variant rounded-lg font-body-md text-body-md
                                  text-on-surface bg-surface-container-lowest focus:outline-none focus:border-primary
                                  focus:ring-1 focus:ring-primary transition-all">
                </div>
                <button type="submit"
                        class="px-6 py-2.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md
                               font-bold hover:bg-primary/90 transition-colors">
                    Simpan Password
                </button>
            </form>
        </div>

        <!-- Info Sistem -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">info</span>
                Informasi Sistem
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ([
                    ['Nama Aplikasi', APP_NAME],
                    ['Email', APP_EMAIL],
                    ['WhatsApp', APP_WHATSAPP],
                    ['DP Minimal', DP_PERSEN . '%'],
                    ['Biaya Asuransi', format_rupiah(BIAYA_ASURANSI)],
                    ['Biaya Admin', format_rupiah(BIAYA_ADMIN)],
                ] as [$lbl,$val]): ?>
                    <div class="flex justify-between p-3 bg-surface-container-low rounded-lg">
                        <span class="font-label-md text-label-md text-on-surface-variant"><?= $lbl ?></span>
                        <span class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($val) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="font-label-sm text-label-sm text-on-surface-variant mt-4">
                Pengaturan sistem seperti biaya dan persentase DP dapat diubah di file
                <code class="bg-surface-container px-1.5 py-0.5 rounded">config/config.php</code>.
            </p>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
