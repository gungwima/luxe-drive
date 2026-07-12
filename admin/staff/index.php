<?php
// ============================================================
// admin/staff/index.php — Manajemen Staff/Admin
// ============================================================
$page_title_admin = 'Manajemen Staff';
$menu_aktif       = 'staff';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

// Hanya superadmin boleh akses
if (!is_superadmin()) {
    set_flash('error', 'Hanya Super Admin yang dapat mengakses halaman ini.');
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$errors = [];
$edit_id = (int)($_GET['edit'] ?? 0);
$staff_edit = null;

// Ambil data untuk edit
if ($edit_id) {
    $s = $pdo->prepare("SELECT * FROM admin WHERE id=?"); $s->execute([$edit_id]);
    $staff_edit = $s->fetch();
}

// Hapus staff
if (isset($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $admin = get_admin_login();
    if ($hid === (int)$admin['id']) {
        set_flash('error', 'Tidak bisa menghapus akun sendiri.');
    } else {
        try {
            $pdo->prepare("DELETE FROM admin WHERE id=?")->execute([$hid]);
            set_flash('sukses', 'Staff berhasil dihapus.');
        } catch (PDOException $e) { set_flash('error','Gagal menghapus.'); }
    }
    header('Location: ' . ADMIN_URL . '/staff/index.php');
    exit;
}

// Simpan (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $nama     = bersihkan($_POST['nama']     ?? '');
        $email    = bersihkan($_POST['email']    ?? '');
        $username = bersihkan($_POST['username'] ?? '');
        $role     = bersihkan($_POST['role']     ?? 'cs');
        $password = $_POST['password'] ?? '';
        $sid      = (int)($_POST['staff_id'] ?? 0);

        if (!$nama)     $errors['nama']     = 'Nama wajib.';
        if (!$username) $errors['username'] = 'Username wajib.';
        if (!$sid && !$password) $errors['password'] = 'Password wajib untuk staff baru.';

        // Email wajib & valid (agar admin bisa reset password via OTP)
        if (!$email) {
            $errors['email'] = 'Email wajib diisi (dipakai untuk reset password).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } else {
            // Cek email duplikat
            $cek_email = $pdo->prepare("SELECT id FROM admin WHERE email=? AND id!=?");
            $cek_email->execute([$email, $sid]);
            if ($cek_email->fetch()) $errors['email'] = 'Email sudah dipakai admin lain.';
        }

        // Cek username duplikat
        if (!isset($errors['username'])) {
            $cek = $pdo->prepare("SELECT id FROM admin WHERE username=? AND id!=?");
            $cek->execute([$username, $sid]);
            if ($cek->fetch()) $errors['username'] = 'Username sudah dipakai.';
        }

        if (empty($errors)) {
            try {
                if ($sid) {
                    if ($password) {
                        $pdo->prepare("UPDATE admin SET nama=?,email=?,username=?,role=?,password=? WHERE id=?")
                            ->execute([$nama,$email,$username,$role,hash_password($password),$sid]);
                    } else {
                        $pdo->prepare("UPDATE admin SET nama=?,email=?,username=?,role=? WHERE id=?")
                            ->execute([$nama,$email,$username,$role,$sid]);
                    }
                    set_flash('sukses','Staff berhasil diperbarui.');
                } else {
                    $pdo->prepare("INSERT INTO admin (nama,email,username,role,password) VALUES (?,?,?,?,?)")
                        ->execute([$nama,$email,$username,$role,hash_password($password)]);
                    set_flash('sukses','Staff berhasil ditambahkan.');
                }
                header('Location: ' . ADMIN_URL . '/staff/index.php');
                exit;
            } catch (PDOException $e) {
                $errors['global'] = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}

try {
    $staff_list = $pdo->query("SELECT * FROM admin ORDER BY role, nama")->fetchAll();
} catch (PDOException $e) { $staff_list = []; }

$role_label = ['superadmin'=>'Super Admin','cs'=>'Customer Service','operasional'=>'Operasional'];
$role_warna = ['superadmin'=>'bg-primary text-on-primary','cs'=>'bg-tertiary-fixed text-on-tertiary-fixed','operasional'=>'bg-secondary-fixed text-on-secondary-fixed'];

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1200px] mx-auto">

    <div class="mb-6">
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-1">Manajemen Staff</h1>
        <p class="font-body-md text-body-md text-on-surface-variant"><?= count($staff_list) ?> staff terdaftar</p>
    </div>

    <?= render_flash() ?>
    <?php if (isset($errors['global'])): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
            <span class="material-symbols-outlined">error</span>
            <p class="font-label-md text-label-md"><?= bersihkan($errors['global']) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Tambah/Edit -->
        <div class="lg:col-span-1">
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 sticky top-20">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">
                    <?= $staff_edit ? 'Edit Staff' : 'Tambah Staff' ?>
                </h2>
                <form method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="staff_id" value="<?= $staff_edit['id'] ?? 0 ?>">
                    <?php
                    $f = [
                        ['nama','Nama Lengkap','text',$staff_edit['nama'] ?? ''],
                        ['email','Email','email',$staff_edit['email'] ?? ''],
                        ['username','Username','text',$staff_edit['username'] ?? ''],
                    ];
                    foreach ($f as [$n,$l,$t,$v]): ?>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"><?= $l ?> *</label>
                            <input type="<?= $t ?>" name="<?= $n ?>" value="<?= htmlspecialchars($v) ?>"
                                   class="w-full px-4 py-2.5 border rounded-lg font-body-md text-body-md text-on-surface
                                          bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1
                                          focus:ring-primary transition-all <?= isset($errors[$n]) ? 'border-error' : 'border-outline-variant' ?>">
                            <?php if (isset($errors[$n])): ?><p class="mt-1 text-error font-label-sm text-label-sm"><?= $errors[$n] ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Role *</label>
                        <select name="role" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg
                                                    font-label-md text-label-md text-on-surface bg-surface-container-lowest
                                                    focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <?php foreach ($role_label as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= ($staff_edit['role']??'cs')===$v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                            Password <?= $staff_edit ? '(kosongkan jika tidak diubah)' : '*' ?>
                        </label>
                        <input type="password" name="password" placeholder="Min. 6 karakter"
                               class="w-full px-4 py-2.5 border rounded-lg font-body-md text-body-md text-on-surface
                                      bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1
                                      focus:ring-primary transition-all <?= isset($errors['password']) ? 'border-error' : 'border-outline-variant' ?>">
                        <?php if (isset($errors['password'])): ?><p class="mt-1 text-error font-label-sm text-label-sm"><?= $errors['password'] ?></p><?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($staff_edit): ?>
                            <a href="<?= ADMIN_URL ?>/staff/index.php"
                               class="flex-1 text-center px-4 py-2.5 border border-outline-variant text-on-surface rounded-lg
                                      font-label-md text-label-md hover:bg-surface-container transition-colors">Batal</a>
                        <?php endif; ?>
                        <button type="submit"
                                class="flex-1 px-4 py-2.5 bg-primary text-on-primary rounded-lg font-label-md text-label-md
                                       font-bold hover:bg-primary/90 transition-colors">
                            <?= $staff_edit ? 'Simpan' : 'Tambah' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Staff -->
        <div class="lg:col-span-2">
            <div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[600px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <?php foreach (['Staff','Username','Role','Login Terakhir','Aksi'] as $h): ?>
                                <th class="px-5 py-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= $h ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($staff_list as $st): ?>
                            <tr class="hover:bg-surface-container-lowest transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary text-on-primary flex items-center
                                                    justify-center font-bold text-sm"><?= inisial($st['nama']) ?></div>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($st['nama']) ?></p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($st['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-body-md text-body-md text-on-surface"><?= htmlspecialchars($st['username']) ?></td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full font-label-sm text-label-sm <?= $role_warna[$st['role']] ?? '' ?>">
                                        <?= $role_label[$st['role']] ?? $st['role'] ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-label-sm text-label-sm text-on-surface-variant">
                                    <?= $st['last_login'] ? waktu_lalu($st['last_login']) : 'Belum pernah' ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="?edit=<?= $st['id'] ?>"
                                           class="p-1.5 text-primary hover:bg-surface-container rounded-lg transition-colors" title="Edit">
                                            <span class="material-symbols-outlined text-[20px]">edit</span>
                                        </a>
                                        <a href="?hapus=<?= $st['id'] ?>" onclick="return confirm('Hapus staff ini?')"
                                           class="p-1.5 text-error hover:bg-error/10 rounded-lg transition-colors" title="Hapus">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
