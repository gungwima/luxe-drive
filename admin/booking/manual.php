<?php
// ============================================================
// admin/booking/manual.php — Booking Manual oleh Admin
// ============================================================
$page_title_admin = 'Booking Manual';
$menu_aktif       = 'booking';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/email.php';

require_admin_login();
$admin = get_admin_login();

$errors = [];
$step   = (int)($_SESSION['bm_step'] ?? 1);

// ── STEP 1: Pilih / Input Pelanggan ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $tipe = $_POST['tipe_pelanggan'] ?? 'existing';

        if ($tipe === 'existing') {
            $uid = (int)($_POST['user_id'] ?? 0);
            if (!$uid) $errors['user_id'] = 'Pilih pelanggan.';
            else {
                $_SESSION['bm_user_id']   = $uid;
                $_SESSION['bm_user_baru'] = null;
                $_SESSION['bm_step']      = 2;
                header('Location: manual.php'); exit;
            }
        } else {
            // Input pelanggan baru
            $nama  = bersihkan($_POST['nama_baru']  ?? '');
            $no_hp = bersihkan($_POST['no_hp_baru'] ?? '');
            $email = bersihkan($_POST['email_baru'] ?? '');
            $ktp   = bersihkan($_POST['ktp_baru']   ?? '');

            if (!$nama)  $errors['nama_baru']  = 'Nama wajib.';
            if (!$no_hp) $errors['no_hp_baru'] = 'No. HP wajib.';

            if (empty($errors)) {
                // Buat user baru atau cari existing by HP
                try {
                    $cek = $pdo->prepare("SELECT id FROM users WHERE no_hp=? OR email=?");
                    $cek->execute([$no_hp, $email]);
                    $existing = $cek->fetch();

                    if ($existing) {
                        $_SESSION['bm_user_id'] = $existing['id'];
                    } else {
                        $pdo->prepare("INSERT INTO users (nama,no_hp,email,no_ktp,password,status) VALUES (?,?,?,?,?,?)")
                            ->execute([$nama, $no_hp, $email ?: null, $ktp ?: null, hash_password(uniqid()), 'aktif']);
                        $_SESSION['bm_user_id'] = (int)$pdo->lastInsertId();
                    }
                    $_SESSION['bm_step'] = 2;
                    header('Location: manual.php'); exit;
                } catch (PDOException $e) {
                    $errors['global'] = 'Gagal menyimpan pelanggan.';
                }
            }
        }
    }
}

// ── STEP 2: Pilih Mobil & Tanggal ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $mobil_id    = (int)    ($_POST['mobil_id']    ?? 0);
        $tgl_ambil   = bersihkan($_POST['tgl_ambil']   ?? '');
        $tgl_kembali = bersihkan($_POST['tgl_kembali'] ?? '');
        $dengan_sopir= (int)isset($_POST['dengan_sopir']);

        if (!$mobil_id)    $errors['mobil_id']    = 'Pilih mobil.';
        if (!$tgl_ambil)   $errors['tgl_ambil']   = 'Tanggal ambil wajib.';
        if (!$tgl_kembali) $errors['tgl_kembali'] = 'Tanggal kembali wajib.';

        if (empty($errors)) {
            $_SESSION['bm_mobil_id']    = $mobil_id;
            $_SESSION['bm_tgl_ambil']   = $tgl_ambil;
            $_SESSION['bm_tgl_kembali'] = $tgl_kembali;
            $_SESSION['bm_dengan_sopir']= $dengan_sopir;
            $_SESSION['bm_step']        = 3;
            header('Location: manual.php'); exit;
        }
    }
}

// ── STEP 3: Detail & Simpan ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $lokasi  = bersihkan($_POST['lokasi']  ?? '');
        $metode  = bersihkan($_POST['metode']  ?? '');
        $catatan = bersihkan($_POST['catatan'] ?? '');

        if (!$lokasi) $errors['lokasi'] = 'Lokasi wajib.';
        if (!$metode) $errors['metode'] = 'Pilih metode bayar.';

        $uid          = $_SESSION['bm_user_id']     ?? 0;
        $mobil_id     = $_SESSION['bm_mobil_id']    ?? 0;
        $tgl_ambil    = $_SESSION['bm_tgl_ambil']   ?? '';
        $tgl_kembali  = $_SESSION['bm_tgl_kembali'] ?? '';
        $dengan_sopir = $_SESSION['bm_dengan_sopir']?? 0;

        if (empty($errors)) {
            try {
                $stmt_m = $pdo->prepare("SELECT * FROM mobil WHERE id=?");
                $stmt_m->execute([$mobil_id]);
                $mobil = $stmt_m->fetch();

                $durasi = hitung_durasi($tgl_ambil, $tgl_kembali);
                $biaya  = hitung_total($mobil['harga_hari'], $durasi, $dengan_sopir ? $mobil['harga_sopir'] : 0);

                $kode = buat_kode_booking();
                $pdo->beginTransaction();

                $pdo->prepare("
                    INSERT INTO booking
                    (kode_booking,user_id,mobil_id,admin_id,tgl_ambil,tgl_kembali,
                     durasi_hari,lokasi_jemput,dengan_sopir,catatan_admin,
                     harga_per_hari,subtotal,biaya_sopir,biaya_asuransi,biaya_admin,
                     diskon,total,status,is_manual)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',1)
                ")->execute([
                    $kode, $uid, $mobil_id, $admin['id'],
                    $tgl_ambil . ' 09:00:00',
                    $tgl_kembali . ' 09:00:00',
                    $durasi, $lokasi, $dengan_sopir, $catatan,
                    $mobil['harga_hari'],
                    $biaya['subtotal'], $biaya['biaya_sopir'],
                    $biaya['biaya_asuransi'], $biaya['biaya_admin'],
                    0, $biaya['total'],
                ]);
                $booking_id = (int)$pdo->lastInsertId();

                $pdo->prepare("INSERT INTO transaksi (booking_id,kode_transaksi,metode,jumlah,status) VALUES (?,?,?,?,'pending')")
                    ->execute([$booking_id, buat_kode_transaksi(), $metode, $biaya['total']]);

                $pdo->commit();

                // Kirim email jika ada
                try {
                    $u = $pdo->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$uid]);
                    $usr = $u->fetch();
                    if ($usr && $usr['email']) {
                        kirim_konfirmasi_booking($usr['email'], $usr['nama'], [
                            'kode_booking'=>$kode,'nama_mobil'=>$mobil['nama'],
                            'tgl_ambil'=>$tgl_ambil.' 09:00:00','tgl_kembali'=>$tgl_kembali.' 09:00:00',
                            'durasi_hari'=>$durasi,'dengan_sopir'=>$dengan_sopir,
                            'lokasi_jemput'=>$lokasi,'total'=>$biaya['total'],
                        ]);
                    }
                } catch (Exception $e) {}

                // Reset session
                unset($_SESSION['bm_step'],$_SESSION['bm_user_id'],$_SESSION['bm_mobil_id'],
                      $_SESSION['bm_tgl_ambil'],$_SESSION['bm_tgl_kembali'],$_SESSION['bm_dengan_sopir']);

                set_flash('sukses', "Booking #{$kode} berhasil dibuat!");
                header('Location: ' . ADMIN_URL . '/booking/detail.php?id=' . $booking_id);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors['global'] = 'Gagal menyimpan booking: ' . $e->getMessage();
            }
        }
    }
}

// Reset / Kembali
if (isset($_GET['reset']) || isset($_GET['kembali'])) {
    $goto = (int)($_GET['kembali'] ?? 0);
    if ($goto > 0) $_SESSION['bm_step'] = $goto;
    else unset($_SESSION['bm_step'],$_SESSION['bm_user_id'],$_SESSION['bm_mobil_id'],
               $_SESSION['bm_tgl_ambil'],$_SESSION['bm_tgl_kembali'],$_SESSION['bm_dengan_sopir']);
    header('Location: manual.php'); exit;
}

$step = (int)($_SESSION['bm_step'] ?? 1);

// Data untuk tampilan
$pelanggan_list = [];
$mobil_list     = [];
$user_terpilih  = null;
$mobil_terpilih = null;
$biaya_preview  = null;

if ($step >= 1) {
    try {
        $pelanggan_list = $pdo->query("SELECT id,nama,no_hp,email FROM users ORDER BY nama LIMIT 200")->fetchAll();
    } catch (PDOException $e) {}
}
if ($step >= 2) {
    try {
        $u = $pdo->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$_SESSION['bm_user_id']]);
        $user_terpilih = $u->fetch();
        $mobil_list = $pdo->query("SELECT * FROM mobil WHERE status IN ('tersedia','maintenance') ORDER BY nama")->fetchAll();
    } catch (PDOException $e) {}
}
if ($step >= 3) {
    try {
        $u2 = $pdo->prepare("SELECT * FROM users WHERE id=?"); $u2->execute([$_SESSION['bm_user_id']]);
        $user_terpilih  = $u2->fetch();
        $m2 = $pdo->prepare("SELECT * FROM mobil WHERE id=?"); $m2->execute([$_SESSION['bm_mobil_id']]);
        $mobil_terpilih = $m2->fetch();
        $durasi_prev    = hitung_durasi($_SESSION['bm_tgl_ambil'], $_SESSION['bm_tgl_kembali']);
        $biaya_preview  = hitung_total($mobil_terpilih['harga_hari'], $durasi_prev, $_SESSION['bm_dengan_sopir'] ? $mobil_terpilih['harga_sopir'] : 0);
    } catch (PDOException $e) {}
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[900px] mx-auto">

    <div class="mb-8">
        <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">Booking Manual</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Buat booking baru atas nama pelanggan.</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center max-w-lg mb-10">
        <?php foreach ([1=>'Pelanggan',2=>'Mobil',3=>'Detail'] as $n=>$l): ?>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                            font-label-sm text-label-sm font-bold
                            <?= $step > $n ? 'bg-primary text-on-primary' : ($step===$n ? 'bg-primary text-on-primary ring-4 ring-primary/20' : 'bg-surface-container text-on-surface-variant') ?>">
                    <?= $step > $n ? '<span class="material-symbols-outlined text-sm">check</span>' : $n ?>
                </div>
                <span class="font-label-md text-label-md mt-2 <?= $step===$n ? 'text-primary font-semibold' : 'text-on-surface-variant' ?>">
                    <?= $l ?>
                </span>
            </div>
            <?php if ($n < 3): ?>
                <div class="flex-1 h-0.5 mx-3 <?= $step > $n ? 'bg-primary' : 'bg-outline-variant' ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (isset($errors['global'])): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
            <span class="material-symbols-outlined">error</span>
            <p class="font-label-md text-label-md"><?= bersihkan($errors['global']) ?></p>
        </div>
    <?php endif; ?>

    <!-- ========== STEP 1: PILIH PELANGGAN ========== -->
    <?php if ($step === 1): ?>
        <form method="POST" class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 space-y-6">
            <?= csrf_field() ?>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Pilih Pelanggan</h2>

            <!-- Cari pelanggan terdaftar -->
            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-2">
                    Pelanggan Terdaftar
                </label>
                <input type="text" id="cari-pelanggan" placeholder="Ketik nama / HP untuk mencari..."
                       class="w-full px-4 py-3 border border-outline-variant rounded-xl font-body-md text-body-md
                              bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all mb-3">
                <div id="list-pelanggan" class="border border-outline-variant rounded-xl overflow-hidden max-h-48 overflow-y-auto">
                    <?php foreach ($pelanggan_list as $p): ?>
                        <label class="flex items-center justify-between p-3 hover:bg-surface-container-low
                                      transition-colors border-b border-outline-variant/20 last:border-0 cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="user_id" value="<?= $p['id'] ?>"
                                       class="text-primary focus:ring-primary">
                                <div class="w-9 h-9 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant
                                            flex items-center justify-center font-bold text-sm">
                                    <?= inisial($p['nama']) ?>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($p['nama']) ?></p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($p['no_hp']) ?></p>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['user_id'])): ?>
                    <p class="mt-1 font-label-sm text-label-sm text-error"><?= $errors['user_id'] ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-outline-variant"></div>
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">atau input baru</span>
                <div class="flex-1 h-px bg-outline-variant"></div>
            </div>

            <!-- Input pelanggan baru -->
            <div class="bg-surface-container-low rounded-xl p-5">
                <input type="hidden" name="tipe_pelanggan" value="new" id="tipe-input">
                <h3 class="font-label-md text-label-md text-on-surface font-semibold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[20px]">person_add</span>
                    Input Data Pelanggan Baru
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_baru" placeholder="Nama lengkap"
                               class="w-full px-3 py-2 border border-outline-variant rounded-lg font-body-md text-body-md
                                      bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">No. HP / WA</label>
                        <input type="tel" name="no_hp_baru" placeholder="0812..."
                               class="w-full px-3 py-2 border border-outline-variant rounded-lg font-body-md text-body-md
                                      bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Email</label>
                        <input type="email" name="email_baru" placeholder="email@..."
                               class="w-full px-3 py-2 border border-outline-variant rounded-lg font-body-md text-body-md
                                      bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">No. KTP</label>
                        <input type="text" name="ktp_baru" placeholder="NIK"
                               class="w-full px-3 py-2 border border-outline-variant rounded-lg font-body-md text-body-md
                                      bg-surface text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="<?= ADMIN_URL ?>/booking/index.php"
                   class="px-6 py-3 border border-outline-variant text-on-surface rounded-xl
                          font-label-md text-label-md hover:bg-surface-container transition-colors">
                    Batal
                </a>
                <button type="submit" name="step1"
                        class="px-8 py-3 bg-primary text-on-primary rounded-xl font-label-md text-label-md
                               font-bold hover:bg-primary/90 transition-colors flex items-center gap-2">
                    Lanjut
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </button>
            </div>
        </form>

    <!-- ========== STEP 2: PILIH MOBIL ========== -->
    <?php elseif ($step === 2): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl font-label-md text-label-md text-green-800 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            Pelanggan: <strong><?= htmlspecialchars($user_terpilih['nama'] ?? '') ?></strong>
            (<?= htmlspecialchars($user_terpilih['no_hp'] ?? '') ?>)
            <a href="manual.php?kembali=1" class="ml-auto text-green-600 hover:underline text-xs">Ganti</a>
        </div>
        <form method="POST" class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 space-y-6">
            <?= csrf_field() ?>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Pilih Mobil & Tanggal</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Tanggal Ambil *</label>
                    <input type="date" name="tgl_ambil" min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['tgl_ambil'] ?? date('Y-m-d')) ?>"
                           class="w-full px-4 py-3 border border-outline-variant rounded-xl font-label-md text-label-md
                                  text-on-surface bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Tanggal Kembali *</label>
                    <input type="date" name="tgl_kembali" min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           value="<?= htmlspecialchars($_POST['tgl_kembali'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                           class="w-full px-4 py-3 border border-outline-variant rounded-xl font-label-md text-label-md
                                  text-on-surface bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-2">Pilih Mobil *</label>
                <div class="space-y-2 max-h-64 overflow-y-auto border border-outline-variant rounded-xl p-2">
                    <?php foreach ($mobil_list as $m): ?>
                        <label class="flex items-center gap-4 p-3 rounded-lg hover:bg-surface-container cursor-pointer border border-transparent hover:border-primary transition-colors">
                            <input type="radio" name="mobil_id" value="<?= $m['id'] ?>"
                                   class="text-primary focus:ring-primary shrink-0">
                            <img src="<?= url_foto_mobil($m['foto_utama']) ?>"
                                 class="w-16 h-12 rounded-lg object-cover shrink-0">
                            <div class="flex-1">
                                <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($m['nama']) ?></p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $m['jenis'] ?> · <?= $m['transmisi'] ?> · <?= $m['kapasitas'] ?> kursi</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-label-md text-label-md text-primary font-bold"><?= format_rupiah($m['harga_hari']) ?>/hari</p>
                                <?= badge_status_mobil($m['status']) ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="dengan_sopir" class="w-5 h-5 text-primary focus:ring-primary rounded">
                <span class="font-body-md text-body-md text-on-surface">Dengan Sopir</span>
            </label>

            <div class="flex justify-between gap-3">
                <a href="manual.php?kembali=1"
                   class="px-6 py-3 border border-outline-variant text-on-surface rounded-xl
                          font-label-md text-label-md hover:bg-surface-container transition-colors">
                    ← Kembali
                </a>
                <button type="submit" name="step2"
                        class="px-8 py-3 bg-primary text-on-primary rounded-xl font-label-md text-label-md
                               font-bold hover:bg-primary/90 transition-colors flex items-center gap-2">
                    Lanjut → Detail
                </button>
            </div>
        </form>

    <!-- ========== STEP 3: DETAIL & SIMPAN ========== -->
    <?php elseif ($step === 3): ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7">
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl font-label-sm text-label-sm text-green-800">
                    ✅ <strong><?= htmlspecialchars($user_terpilih['nama'] ?? '') ?></strong> ·
                    <strong><?= htmlspecialchars($mobil_terpilih['nama'] ?? '') ?></strong> ·
                    <?= format_tanggal($_SESSION['bm_tgl_ambil']) ?> – <?= format_tanggal($_SESSION['bm_tgl_kembali']) ?>
                </div>
                <form method="POST" class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 space-y-5">
                    <?= csrf_field() ?>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Detail Sewa</h2>

                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Lokasi Penjemputan *</label>
                        <input type="text" name="lokasi" placeholder="Nama hotel, bandara, atau alamat lengkap"
                               class="w-full px-4 py-3 border <?= isset($errors['lokasi']) ? 'border-error' : 'border-outline-variant' ?> rounded-xl font-body-md text-body-md
                                      text-on-surface bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-2">Metode Pembayaran *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach (['transfer_bank'=>'Transfer Bank','virtual_account'=>'Virtual Account','ewallet'=>'E-Wallet','tunai'=>'Tunai'] as $v=>$l): ?>
                                <label class="flex items-center gap-3 p-3 border border-outline-variant rounded-xl cursor-pointer hover:border-primary transition-colors">
                                    <input type="radio" name="metode" value="<?= $v ?>" class="text-primary focus:ring-primary">
                                    <span class="font-label-md text-label-md text-on-surface"><?= $l ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Catatan (Opsional)</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan tambahan untuk booking ini..."
                                  class="w-full px-4 py-3 border border-outline-variant rounded-xl font-body-md text-body-md
                                         text-on-surface bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary
                                         outline-none transition-all resize-none"></textarea>
                    </div>

                    <div class="flex justify-between gap-3">
                        <a href="manual.php?kembali=2"
                           class="px-6 py-3 border border-outline-variant text-on-surface rounded-xl
                                  font-label-md text-label-md hover:bg-surface-container transition-colors">
                            ← Kembali
                        </a>
                        <button type="submit" name="step3"
                                class="px-8 py-3 bg-primary text-on-primary rounded-xl font-label-md text-label-md
                                       font-bold hover:bg-primary/90 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined">check_circle</span>
                            Simpan Booking
                        </button>
                    </div>
                </form>
            </div>

            <!-- Ringkasan -->
            <div class="lg:col-span-5">
                <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 sticky top-20">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Ringkasan</h3>
                    <div class="flex gap-3 mb-4">
                        <img src="<?= url_foto_mobil($mobil_terpilih['foto_utama'] ?? null) ?>"
                             class="w-20 h-14 rounded-lg object-cover shrink-0">
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold">
                                <?= htmlspecialchars($mobil_terpilih['nama'] ?? '') ?>
                            </p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                <?= $_SESSION['bm_dengan_sopir'] ? 'Dengan Sopir' : 'Tanpa Sopir' ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($biaya_preview): ?>
                        <div class="space-y-2 text-sm">
                            <?php $durasi_p = hitung_durasi($_SESSION['bm_tgl_ambil'],$_SESSION['bm_tgl_kembali']); ?>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Sewa (<?= $durasi_p ?> hari)</span>
                                <span><?= format_rupiah($biaya_preview['subtotal']) ?></span>
                            </div>
                            <?php if ($biaya_preview['biaya_sopir']>0): ?>
                                <div class="flex justify-between font-label-md text-label-md">
                                    <span class="text-on-surface-variant">Sopir</span>
                                    <span><?= format_rupiah($biaya_preview['biaya_sopir']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Asuransi</span>
                                <span><?= format_rupiah($biaya_preview['biaya_asuransi']) ?></span>
                            </div>
                            <hr class="border-outline-variant/30">
                            <div class="flex justify-between font-headline-sm text-headline-sm">
                                <span class="text-on-surface">Total</span>
                                <span class="text-primary font-bold"><?= format_rupiah($biaya_preview['total']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
</div>

<script>
    // Filter cari pelanggan
    document.getElementById('cari-pelanggan')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#list-pelanggan label').forEach(el => {
            el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Set tipe pelanggan berdasarkan input aktif
    document.querySelectorAll('[name="user_id"]').forEach(r => {
        r.addEventListener('change', () => {
            document.getElementById('tipe-input').value = 'existing';
        });
    });
    document.querySelectorAll('[name="nama_baru"],[name="no_hp_baru"],[name="email_baru"],[name="ktp_baru"]').forEach(i => {
        i.addEventListener('focus', () => {
            document.getElementById('tipe-input').value = 'new';
            document.querySelectorAll('[name="user_id"]').forEach(r => r.checked = false);
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
