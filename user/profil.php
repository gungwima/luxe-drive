<?php
// ============================================================
// user/profil.php — Profil & Edit Data Diri User
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user       = get_user_login();
$menu_user_aktif = 'profil';

// Ambil data user lengkap
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $user_data = $stmt->fetch();
} catch (PDOException $e) { $user_data = []; }

// Statistik user
try {
    $total_sewa    = (int)$pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND status='selesai'")->execute([$user['id']]) ? 0 : 0;
    $s1 = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ? AND status='selesai'"); $s1->execute([$user['id']]); $total_sewa = (int)$s1->fetchColumn();
    $s2 = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id = ?"); $s2->execute([$user['id']]); $total_booking = (int)$s2->fetchColumn();
    $s3 = $pdo->prepare("SELECT COUNT(*) FROM ulasan WHERE user_id = ?"); $s3->execute([$user['id']]); $total_ulasan = (int)$s3->fetchColumn();
} catch (PDOException $e) { $total_sewa=0; $total_booking=0; $total_ulasan=0; }

$errors = []; $sukses = '';

// ── PROSES UPDATE PROFIL ──
// Handler khusus: update foto profil saja (tanpa validasi nama/email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_foto'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $upload = simpan_gambar_db($_FILES['foto_profil'], 'profil');
            if ($upload) {
                try {
                    $pdo->prepare("UPDATE users SET foto_profil=? WHERE id=?")->execute([$upload, $user['id']]);
                    $_SESSION[SESSION_USER]['foto'] = $upload;
                    set_flash('sukses', 'Foto profil berhasil diperbarui.');
                    header('Location: ' . BASE_URL . '/user/profil.php');
                    exit;
                } catch (PDOException $e) {
                    $errors['global'] = 'Gagal menyimpan foto.';
                }
            } else {
                $errors['global'] = 'Gagal mengunggah foto. Pastikan file valid (JPG/PNG, maks 2MB).';
            }
        } else {
            $errors['global'] = 'Pilih file foto terlebih dahulu.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $nama      = bersihkan($_POST['nama']      ?? '');
        $no_hp     = bersihkan($_POST['no_hp']     ?? '');
        $email     = bersihkan($_POST['email']     ?? '');
        $tgl_lahir = bersihkan($_POST['tgl_lahir'] ?? '');
        $no_ktp    = bersihkan($_POST['no_ktp']    ?? '');
        $no_sim    = bersihkan($_POST['no_sim']    ?? '');

        if (!$nama)  $errors['nama']  = 'Nama wajib diisi.';
        if (!$email) $errors['email'] = 'Email wajib diisi.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';

        // Cek email duplikat (selain milik sendiri)
        if (!isset($errors['email'])) {
            $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $cek->execute([$email, $user['id']]);
            if ($cek->fetch()) $errors['email'] = 'Email sudah digunakan akun lain.';
        }

        // Upload foto profil
        $foto_baru = $user_data['foto_profil'] ?? null;
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $upload = simpan_gambar_db($_FILES['foto_profil'], 'profil');
            if ($upload) {
                // Hapus foto lama
                if ($foto_baru) hapus_file('profil', $foto_baru);
                $foto_baru = $upload;
            }
        }

        if (empty($errors)) {
            try {
                $pdo->prepare("
                    UPDATE users SET
                        nama=?, no_hp=?, email=?, tgl_lahir=?,
                        no_ktp=?, no_sim=?, foto_profil=?
                    WHERE id=?
                ")->execute([$nama, $no_hp, $email, $tgl_lahir ?: null, $no_ktp, $no_sim, $foto_baru, $user['id']]);

                // Update session
                $_SESSION[SESSION_USER]['nama']  = $nama;
                $_SESSION[SESSION_USER]['email'] = $email;
                $_SESSION[SESSION_USER]['foto']  = $foto_baru;

                $sukses = 'Profil berhasil diperbarui!';
                // Reload data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user_data = $stmt->fetch();
            } catch (PDOException $e) {
                $errors['global'] = 'Gagal menyimpan. Coba lagi.';
            }
        }
    }
}

// ── PROSES GANTI PASSWORD ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $pass_lama    = $_POST['pass_lama']    ?? '';
        $pass_baru    = $_POST['pass_baru']    ?? '';
        $pass_konfirm = $_POST['pass_konfirm'] ?? '';

        if (!verify_password($pass_lama, $user_data['password'])) {
            $errors['pass_lama'] = 'Password lama tidak benar.';
        } elseif (strlen($pass_baru) < 8) {
            $errors['pass_baru'] = 'Password minimal 8 karakter.';
        } elseif ($pass_baru !== $pass_konfirm) {
            $errors['pass_konfirm'] = 'Konfirmasi password tidak cocok.';
        } else {
            try {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                    ->execute([hash_password($pass_baru), $user['id']]);
                $sukses = 'Password berhasil diubah!';
            } catch (PDOException $e) {
                $errors['global'] = 'Gagal mengubah password.';
            }
        }
    }
}

$page_title_user = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<body class="bg-background text-on-background min-h-screen flex">

<?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen pt-16">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface border-b border-outline-variant/30
                   z-[60] flex items-center justify-between px-margin-mobile md:px-margin-desktop">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-label-md text-label-md text-on-surface hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <?= APP_NAME ?>
        </a>
        <div class="flex items-center gap-3">
            <span class="font-label-md text-label-md text-on-surface-variant hidden md:block">
                <?= htmlspecialchars($user_data['nama'] ?? '') ?>
            </span>
        </div>
    </header>

    <main class="flex-1 px-margin-mobile md:px-margin-desktop py-8 max-w-container-max mx-auto w-full">

        <!-- Header -->
        <div class="mb-10">
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">Profil Saya</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Kelola informasi pribadi dan keamanan akun Anda.
            </p>
        </div>

        <?= render_flash() ?>

        <!-- Flash -->
        <?php if ($sukses): ?>
            <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 alert-auto-hide">
                <span class="material-symbols-outlined">check_circle</span>
                <p class="font-label-md text-label-md"><?= bersihkan($sukses) ?></p>
            </div>
        <?php endif; ?>
        <?php if (isset($errors['global'])): ?>
            <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
                <span class="material-symbols-outlined">error</span>
                <p class="font-label-md text-label-md"><?= bersihkan($errors['global']) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Kiri: Foto & Statistik -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Foto Profil -->
                <div class="bg-surface rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                            border border-surface-variant flex flex-col items-center text-center">
                    <form method="POST" enctype="multipart/form-data" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="update_foto" value="1">
                        <div class="relative">
                            <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-surface shadow-sm">
                                <?php if ($user_data['foto_profil']): ?>
                                    <img src="<?= url_gambar($user_data['foto_profil'], 'profil') ?>"
                                         alt="Foto Profil" id="preview-foto"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-primary flex items-center justify-center
                                                text-on-primary text-4xl font-bold" id="preview-avatar">
                                        <?= inisial($user_data['nama'] ?? 'U') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <label class="absolute bottom-0 right-0 bg-primary text-on-primary p-2
                                          rounded-full shadow-md hover:bg-primary/90 transition-colors
                                          cursor-pointer" title="Ubah Foto">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <input type="file" name="foto_profil" accept=".jpg,.jpeg,.png"
                                       class="hidden input-foto-preview" data-preview="#preview-foto"
                                       onchange="previewFoto(this)">
                            </label>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-primary mt-4 mb-1">
                            <?= htmlspecialchars($user_data['nama'] ?? '') ?>
                        </h3>
                        <div class="inline-flex items-center gap-1 bg-surface-container
                                    px-3 py-1 rounded-full mb-4">
                            <span class="material-symbols-outlined text-secondary text-sm"
                                  style="font-variation-settings:'FILL' 1">stars</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant">
                                Member sejak <?= date('Y', strtotime($user_data['created_at'] ?? 'now')) ?>
                            </span>
                        </div>

                        <!-- Tombol simpan foto (muncul setelah pilih foto baru) -->
                        <div id="aksi-foto" class="hidden w-full mt-2">
                            <p class="font-label-sm text-label-sm text-on-surface-variant mb-2" id="nama-file-foto"></p>
                            <button type="submit"
                                    class="w-full flex items-center justify-center gap-2 bg-primary text-on-primary
                                           py-2.5 rounded-lg font-label-md text-label-md font-bold
                                           hover:bg-primary/90 transition-colors">
                                <span class="material-symbols-outlined text-[20px]">save</span>
                                Simpan Foto
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Statistik -->
                <div class="bg-surface rounded-xl p-6 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                            border border-surface-variant">
                    <h4 class="font-headline-sm text-headline-sm text-primary mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">bar_chart</span>
                        Aktivitas Saya
                    </h4>
                    <div class="space-y-3">
                        <?php
                        $stat_items = [
                            ['Total Booking',   $total_booking, 'directions_car'],
                            ['Sewa Selesai',    $total_sewa,    'task_alt'],
                            ['Ulasan Ditulis',  $total_ulasan,  'rate_review'],
                        ];
                        foreach ($stat_items as [$label, $nilai, $ikon]): ?>
                            <div class="flex justify-between items-center py-2
                                        border-b border-outline-variant/30 last:border-0">
                                <div class="flex items-center gap-2 text-on-surface-variant">
                                    <span class="material-symbols-outlined text-sm"><?= $ikon ?></span>
                                    <span class="font-body-md text-body-md"><?= $label ?></span>
                                </div>
                                <span class="font-headline-sm text-headline-sm text-primary font-bold">
                                    <?= $nilai ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Kanan: Form Edit -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Informasi Pribadi -->
                <div class="bg-surface rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                            border border-surface-variant">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-headline-sm text-headline-sm text-primary">Informasi Pribadi</h3>
                        <button id="btn-edit-profil" onclick="toggleEditProfil()"
                                class="text-secondary hover:text-secondary/70 transition-colors
                                       flex items-center gap-1 font-label-md text-label-md">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Edit
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="form-profil">
                        <?= csrf_field() ?>
                        <input type="hidden" name="update_profil" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php
                            $fields = [
                                ['nama',      'Nama Lengkap',    'text',  $user_data['nama']      ?? '', false],
                                ['email',     'Alamat Email',    'email', $user_data['email']     ?? '', false],
                                ['no_hp',     'Nomor HP/WA',     'tel',   $user_data['no_hp']     ?? '', false],
                                ['tgl_lahir', 'Tanggal Lahir',   'date',  $user_data['tgl_lahir'] ?? '', false],
                                ['no_ktp',    'Nomor KTP (NIK)', 'text',  $user_data['no_ktp']    ?? '', false],
                                ['no_sim',    'Nomor SIM A',     'text',  $user_data['no_sim']    ?? '', false],
                            ];
                            foreach ($fields as [$name, $label, $type, $val, $readonly]): ?>
                                <div>
                                    <label class="block font-label-sm text-label-sm
                                                  text-on-surface-variant mb-2">
                                        <?= $label ?>
                                    </label>
                                    <input type="<?= $type ?>" name="<?= $name ?>"
                                           value="<?= htmlspecialchars($val) ?>"
                                           readonly id="field-<?= $name ?>"
                                           class="w-full px-4 py-3 bg-surface-container-lowest
                                                  border border-outline-variant rounded-lg
                                                  font-body-md text-body-md text-on-surface
                                                  focus:outline-none focus:border-primary
                                                  focus:ring-1 focus:ring-primary transition-colors
                                                  read-only:cursor-default read-only:bg-surface-container-low
                                                  <?= isset($errors[$name]) ? 'border-error' : '' ?>">
                                    <?php if (isset($errors[$name])): ?>
                                        <p class="mt-1 font-label-sm text-label-sm text-error">
                                            <?= $errors[$name] ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="btn-simpan-profil" class="hidden mt-6 flex gap-3 justify-end">
                            <button type="button" onclick="toggleEditProfil()"
                                    class="px-6 py-2.5 border border-outline-variant text-on-surface
                                           rounded-lg font-label-md text-label-md hover:bg-surface-container
                                           transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-primary text-on-primary rounded-lg
                                           font-label-md text-label-md font-bold
                                           hover:bg-primary/90 transition-colors">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Ganti Password -->
                <div class="bg-surface rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                            border border-surface-variant">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-headline-sm text-headline-sm text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined">lock</span>
                            Keamanan Akun
                        </h3>
                        <button onclick="toggleGantiPassword()"
                                class="text-secondary hover:text-secondary/70 transition-colors
                                       flex items-center gap-1 font-label-md text-label-md">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Ganti Password
                        </button>
                    </div>

                    <form method="POST" id="form-password" class="hidden space-y-5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ganti_password" value="1">

                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                                Password Lama *
                            </label>
                            <div class="relative">
                                <input type="password" name="pass_lama" placeholder="Password saat ini"
                                       class="w-full px-4 py-3 bg-surface-container-lowest border
                                              rounded-lg font-body-md text-body-md text-on-surface
                                              focus:outline-none focus:border-primary focus:ring-1
                                              focus:ring-primary transition-colors
                                              <?= isset($errors['pass_lama']) ? 'border-error' : 'border-outline-variant' ?>">
                            </div>
                            <?php if (isset($errors['pass_lama'])): ?>
                                <p class="mt-1 font-label-sm text-label-sm text-error"><?= $errors['pass_lama'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                                    Password Baru *
                                </label>
                                <input type="password" name="pass_baru" placeholder="Min. 8 karakter"
                                       class="w-full px-4 py-3 bg-surface-container-lowest border rounded-lg
                                              font-body-md text-body-md text-on-surface focus:outline-none
                                              focus:border-primary focus:ring-1 focus:ring-primary transition-colors
                                              <?= isset($errors['pass_baru']) ? 'border-error' : 'border-outline-variant' ?>">
                                <?php if (isset($errors['pass_baru'])): ?>
                                    <p class="mt-1 font-label-sm text-label-sm text-error"><?= $errors['pass_baru'] ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                                    Konfirmasi Password *
                                </label>
                                <input type="password" name="pass_konfirm" placeholder="Ulangi password baru"
                                       class="w-full px-4 py-3 bg-surface-container-lowest border rounded-lg
                                              font-body-md text-body-md text-on-surface focus:outline-none
                                              focus:border-primary focus:ring-1 focus:ring-primary transition-colors
                                              <?= isset($errors['pass_konfirm']) ? 'border-error' : 'border-outline-variant' ?>">
                                <?php if (isset($errors['pass_konfirm'])): ?>
                                    <p class="mt-1 font-label-sm text-label-sm text-error"><?= $errors['pass_konfirm'] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex gap-3 justify-end">
                            <button type="button" onclick="toggleGantiPassword()"
                                    class="px-6 py-2.5 border border-outline-variant text-on-surface
                                           rounded-lg font-label-md text-label-md hover:bg-surface-container
                                           transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-primary text-on-primary rounded-lg
                                           font-label-md text-label-md font-bold hover:bg-primary/90
                                           transition-colors">
                                Simpan Password
                            </button>
                        </div>
                    </form>

                    <div id="info-password" class="text-on-surface-variant font-body-md text-body-md">
                        Password terakhir diubah: —
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function toggleEditProfil() {
        const fields = document.querySelectorAll('#form-profil input:not([type=file])');
        const btnSimpan = document.getElementById('btn-simpan-profil');
        const btnEdit = document.getElementById('btn-edit-profil');
        const sedangReadonly = fields[0].readOnly;
        if (sedangReadonly) {
            fields.forEach(f => { if (!f.dataset.keepReadonly) f.readOnly = false; });
            btnSimpan.classList.remove('hidden');
            if (btnEdit) btnEdit.classList.add('hidden');
        } else {
            fields.forEach(f => f.readOnly = true);
            btnSimpan.classList.add('hidden');
            if (btnEdit) btnEdit.classList.remove('hidden');
        }
    }
    function toggleGantiPassword() {
        const form = document.getElementById('form-password');
        const info = document.getElementById('info-password');
        form.classList.toggle('hidden');
        info.classList.toggle('hidden');
    }
    function previewFoto(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            let img = document.getElementById('preview-foto');
            if (!img) {
                const div = document.getElementById('preview-avatar');
                if (div) {
                    div.outerHTML = '<img id="preview-foto" class="w-full h-full object-cover">';
                    img = document.getElementById('preview-foto');
                }
            }
            if (img) img.src = e.target.result;
        };
        reader.readAsDataURL(file);
        // Tampilkan tombol simpan foto + nama file
        const aksiFoto = document.getElementById('aksi-foto');
        const namaFile = document.getElementById('nama-file-foto');
        if (aksiFoto) aksiFoto.classList.remove('hidden');
        if (namaFile) namaFile.textContent = 'File dipilih: ' + file.name;
    }
</script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
