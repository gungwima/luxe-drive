<?php
// ============================================================
// admin/profil/index.php — Profil Admin
// ============================================================
$page_title_admin = 'Profil Saya';
$menu_aktif       = 'profil';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_admin_login();
$admin = get_admin_login();

$sukses = ''; $error = '';

// Ambil data admin lengkap
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$admin['id']]);
$data = $stmt->fetch();

// ── PROSES UPDATE FOTO ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_foto'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $id_foto = simpan_gambar_db($_FILES['foto'], 'admin');
        if ($id_foto) {
            $pdo->prepare("UPDATE admin SET foto = ? WHERE id = ?")->execute([$id_foto, $admin['id']]);
            set_flash('sukses', 'Foto profil berhasil diperbarui.');
            header('Location: ' . ADMIN_URL . '/profil.php');
            exit;
        } else {
            $error = 'Gagal mengunggah foto. Pastikan file valid (JPG/PNG, maks 2MB).';
        }
    } else {
        $error = 'Pilih file foto terlebih dahulu.';
    }
}

// ── PROSES HAPUS FOTO ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_foto'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        if (!empty($data['foto'])) hapus_gambar_db($data['foto']);
        $pdo->prepare("UPDATE admin SET foto = NULL WHERE id = ?")->execute([$admin['id']]);
        set_flash('sukses', 'Foto profil dihapus.');
        header('Location: ' . ADMIN_URL . '/profil.php');
        exit;
    }
}

// ── PROSES UPDATE INFO PRIBADI ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $nama  = bersihkan($_POST['nama']  ?? '');
        $email = bersihkan($_POST['email'] ?? '');

        if (!$nama)  $error = 'Nama wajib diisi.';
        elseif (!$email) $error = 'Email wajib diisi.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
        else {
            // Cek email duplikat
            $cek = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
            $cek->execute([$email, $admin['id']]);
            if ($cek->fetch()) {
                $error = 'Email sudah digunakan admin lain.';
            } else {
                $pdo->prepare("UPDATE admin SET nama = ?, email = ? WHERE id = ?")
                    ->execute([$nama, $email, $admin['id']]);
                $_SESSION[SESSION_ADMIN]['nama'] = $nama;
                set_flash('sukses', 'Informasi pribadi berhasil diperbarui.');
                header('Location: ' . ADMIN_URL . '/profil.php');
                exit;
            }
        }
    }
}

// ── PROSES GANTI PASSWORD ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $lama    = $_POST['pass_lama']    ?? '';
        $baru    = $_POST['pass_baru']    ?? '';
        $konfirm = $_POST['pass_konfirm'] ?? '';

        $a = $pdo->prepare("SELECT password FROM admin WHERE id=?");
        $a->execute([$admin['id']]);
        $pass_db = $a->fetchColumn();

        if (!verify_password($lama, $pass_db)) $error = 'Password lama salah.';
        elseif (strlen($baru) < 8) $error = 'Password baru minimal 8 karakter.';
        elseif ($baru !== $konfirm) $error = 'Konfirmasi password tidak cocok.';
        else {
            $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?")
                ->execute([hash_password($baru), $admin['id']]);
            set_flash('sukses', 'Password berhasil diubah.');
            header('Location: ' . ADMIN_URL . '/profil.php');
            exit;
        }
    }
}

// Refresh data setelah kemungkinan perubahan
$stmt->execute([$admin['id']]);
$data = $stmt->fetch();

// Label role
$role_label = match($data['role']) {
    'superadmin'  => 'Super Administrator',
    'cs'          => 'Customer Service',
    'operasional' => 'Operasional',
    default       => ucfirst($data['role']),
};

require_once __DIR__ . '/../includes/navbar_admin.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1100px] mx-auto">

    <div class="mb-8">
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Profil Saya</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">
            Kelola informasi pribadi dan keamanan akun Anda.
        </p>
    </div>

    <?= render_flash() ?>
    <?php if ($error): ?>
        <div class="mb-6 flex items-center gap-3 bg-error-container text-error px-5 py-4 rounded-xl">
            <span class="material-symbols-outlined">error</span>
            <span class="font-body-md text-body-md"><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Foto Profil -->
        <div class="lg:col-span-1">
            <div class="bg-surface-container-lowest rounded-xl p-6 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                        flex flex-col items-center text-center">
                <div class="w-32 h-32 rounded-full overflow-hidden mb-6 border-4 border-surface-container-low shadow-sm">
                    <?php if (!empty($data['foto'])): ?>
                        <img src="<?= url_gambar($data['foto'], 'admin') ?>" alt="Foto Profil"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-primary flex items-center justify-center
                                    text-on-primary text-4xl font-bold">
                            <?= inisial($data['nama']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="font-headline-sm text-headline-sm font-bold text-primary">
                    <?= htmlspecialchars($data['nama']) ?>
                </h3>
                <span class="inline-block mt-2 mb-4 px-3 py-1 bg-secondary-container
                             text-on-secondary-container rounded-full font-label-sm text-label-sm">
                    <?= htmlspecialchars($role_label) ?>
                </span>

                <!-- Ganti / Hapus Foto -->
                <form method="POST" enctype="multipart/form-data" class="w-full flex flex-col gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_foto" value="1">
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png" id="input-foto-admin"
                           class="hidden" onchange="pilihFotoAdmin(this)">

                    <label for="input-foto-admin"
                           class="px-4 py-2 bg-primary text-on-primary rounded-lg font-label-md text-label-md
                                  font-bold hover:brightness-110 transition-all cursor-pointer text-center">
                        Ganti Foto
                    </label>
                    <p id="nama-file-admin" class="font-label-sm text-label-sm text-on-surface-variant hidden"></p>
                    <button type="submit" id="btn-simpan-foto-admin"
                            class="hidden px-4 py-2 bg-secondary-container text-on-secondary-container rounded-lg
                                   font-label-md text-label-md font-bold hover:brightness-95 transition-all">
                        Simpan Foto
                    </button>
                </form>

                <?php if (!empty($data['foto'])): ?>
                    <form method="POST" class="w-full mt-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="hapus_foto" value="1">
                        <button type="submit"
                                class="w-full px-4 py-2 border border-outline text-on-surface-variant rounded-lg
                                       font-label-md text-label-md font-medium hover:bg-surface-variant transition-all">
                            Hapus Foto
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Info & Keamanan -->
        <div class="lg:col-span-2 flex flex-col gap-8">

            <!-- Informasi Pribadi -->
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]">
                <h3 class="font-headline-sm text-headline-sm font-bold text-primary mb-6
                           border-b border-outline-variant pb-4">Informasi Pribadi</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_info" value="1">

                    <div class="flex flex-col gap-2">
                        <label class="font-label-md text-label-md text-on-surface font-semibold">Nama Lengkap</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']) ?>"
                               class="px-4 py-3 rounded-lg border border-outline-variant focus:border-primary
                                      focus:ring-1 focus:ring-primary outline-none transition-all
                                      font-body-md text-body-md text-on-surface bg-surface-bright">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-label-md text-label-md text-on-surface font-semibold">Alamat Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                               class="px-4 py-3 rounded-lg border border-outline-variant focus:border-primary
                                      focus:ring-1 focus:ring-primary outline-none transition-all
                                      font-body-md text-body-md text-on-surface bg-surface-bright">
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-label-md text-label-md text-on-surface font-semibold">Username</label>
                        <input type="text" value="<?= htmlspecialchars($data['username']) ?>" disabled
                               class="px-4 py-3 rounded-lg border border-outline-variant bg-surface-container-low
                                      font-body-md text-body-md text-on-surface-variant cursor-not-allowed">
                        <p class="text-xs text-on-surface-variant mt-1">Username tidak dapat diubah.</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="font-label-md text-label-md text-on-surface font-semibold">Peran</label>
                        <input type="text" value="<?= htmlspecialchars($role_label) ?>" disabled
                               class="px-4 py-3 rounded-lg border border-outline-variant bg-surface-container-low
                                      font-body-md text-body-md text-on-surface-variant cursor-not-allowed">
                        <p class="text-xs text-on-surface-variant mt-1">Peran tidak dapat diubah sendiri.</p>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit"
                                class="px-8 py-3 bg-secondary-container text-on-secondary-container rounded-lg
                                       font-label-md text-label-md font-bold hover:brightness-95 transition-all shadow-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Keamanan Akun -->
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]">
                <h3 class="font-headline-sm text-headline-sm font-bold text-primary mb-6
                           border-b border-outline-variant pb-4">Keamanan Akun</h3>
                <form method="POST" class="space-y-5">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ganti_password" value="1">

                    <div class="flex flex-col gap-2">
                        <label class="font-label-md text-label-md text-on-surface font-semibold">Password Lama</label>
                        <input type="password" name="pass_lama" required
                               class="px-4 py-3 rounded-lg border border-outline-variant focus:border-primary
                                      focus:ring-1 focus:ring-primary outline-none transition-all
                                      font-body-md text-body-md bg-surface-bright">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-2">
                            <label class="font-label-md text-label-md text-on-surface font-semibold">Password Baru</label>
                            <input type="password" name="pass_baru" required minlength="8"
                                   class="px-4 py-3 rounded-lg border border-outline-variant focus:border-primary
                                          focus:ring-1 focus:ring-primary outline-none transition-all
                                          font-body-md text-body-md bg-surface-bright">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-label-md text-label-md text-on-surface font-semibold">Konfirmasi Password</label>
                            <input type="password" name="pass_konfirm" required minlength="8"
                                   class="px-4 py-3 rounded-lg border border-outline-variant focus:border-primary
                                          focus:ring-1 focus:ring-primary outline-none transition-all
                                          font-body-md text-body-md bg-surface-bright">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-2 border border-primary text-primary rounded-lg font-label-md text-label-md
                                       font-bold hover:bg-primary hover:text-on-primary transition-all">
                            Ubah Kata Sandi
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</main>
</div>

<script>
    function pilihFotoAdmin(input) {
        const file = input.files[0];
        if (!file) return;
        const nama = document.getElementById('nama-file-admin');
        const btn  = document.getElementById('btn-simpan-foto-admin');
        if (nama) { nama.textContent = 'File dipilih: ' + file.name; nama.classList.remove('hidden'); }
        if (btn) btn.classList.remove('hidden');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
