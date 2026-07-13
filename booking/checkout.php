<?php
// ============================================================
// booking/checkout.php — Form Pemesanan (Step 1)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/email.php';

require_user_login(BASE_URL . '/booking/checkout.php?' . http_build_query($_GET));
$user = get_user_login();

// Ambil parameter dari URL
$mobil_id    = (int) ($_GET['mobil_id']    ?? $_POST['mobil_id']    ?? 0);
$tgl_ambil   = bersihkan($_GET['tgl_ambil']  ?? $_POST['tgl_ambil']  ?? date('Y-m-d'));
$tgl_kembali = bersihkan($_GET['tgl_kembali']?? $_POST['tgl_kembali']?? date('Y-m-d', strtotime('+1 day')));
$dengan_sopir= isset($_GET['dengan_sopir']) || isset($_POST['dengan_sopir']) ? 1 : 0;

if (!$mobil_id) { header('Location: ' . BASE_URL . '/daftar_armada.php'); exit; }

// Ambil data mobil
try {
    $stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ? AND status = 'tersedia'");
    $stmt->execute([$mobil_id]);
    $mobil = $stmt->fetch();
} catch (PDOException $e) { $mobil = null; }

if (!$mobil) {
    set_flash('error', 'Mobil tidak tersedia.');
    header('Location: ' . BASE_URL . '/daftar_armada.php');
    exit;
}

// Ambil data user lengkap
try {
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$user['id']]);
    $user_data = $stmt_user->fetch();
} catch (PDOException $e) { $user_data = []; }

$durasi = hitung_durasi($tgl_ambil, $tgl_kembali);
$biaya  = hitung_total(
    $mobil['harga_hari'],
    $durasi,
    $dengan_sopir ? $mobil['harga_sopir'] : 0
);

$errors = [];
$error  = '';

// ── PROSES SUBMIT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_checkout'])) {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        // Validasi input
        $nama        = bersihkan($_POST['nama']        ?? '');
        $no_hp       = bersihkan($_POST['no_hp']       ?? '');
        $email       = bersihkan($_POST['email']       ?? '');
        $no_ktp      = bersihkan($_POST['no_ktp']      ?? '');
        $no_sim      = bersihkan($_POST['no_sim']      ?? '');
        $lokasi      = bersihkan($_POST['lokasi_jemput']?? '');
        $catatan     = bersihkan($_POST['catatan']     ?? '');
        $metode      = bersihkan($_POST['metode_bayar']?? '');
        $promo_kode  = bersihkan($_POST['kode_promo']  ?? '');

        if (empty($nama))   $errors['nama']   = 'Nama wajib diisi.';
        if (empty($no_hp))  $errors['no_hp']  = 'No. HP wajib diisi.';
        if (empty($email))  $errors['email']  = 'Email wajib diisi.';
        if (empty($no_ktp)) $errors['no_ktp'] = 'No. KTP wajib diisi.';
        if (empty($lokasi)) $errors['lokasi'] = 'Lokasi jemput wajib diisi.';
        if (empty($metode)) $errors['metode'] = 'Pilih metode pembayaran.';

        // Cek ketersediaan mobil
        $stmt_cek = $pdo->prepare("
            SELECT COUNT(*) FROM booking
            WHERE mobil_id = ? AND status IN ('confirmed','berlangsung')
            AND NOT (tgl_kembali <= ? OR tgl_ambil >= ?)
        ");
        $stmt_cek->execute([$mobil_id, $tgl_ambil, $tgl_kembali]);
        if ($stmt_cek->fetchColumn() > 0) {
            $errors['tanggal'] = 'Mobil sudah dipesan untuk tanggal tersebut.';
        }

        // Cek promo
        $diskon   = 0;
        $promo_id = null;
        if ($promo_kode) {
            $stmt_promo = $pdo->prepare("
                SELECT * FROM promo
                WHERE kode = ? AND status = 'aktif'
                AND (tgl_berakhir IS NULL OR tgl_berakhir >= CURDATE())
                AND (kuota IS NULL OR terpakai < kuota)
                LIMIT 1
            ");
            $stmt_promo->execute([$promo_kode]);
            $promo = $stmt_promo->fetch();
            if ($promo) {
                $diskon = $promo['tipe'] === 'persen'
                    ? ($biaya['subtotal'] * $promo['nilai'] / 100)
                    : $promo['nilai'];
                if ($promo['maks_diskon']) $diskon = min($diskon, $promo['maks_diskon']);
                $promo_id = $promo['id'];
            }
        }

        // Proses upload KTP & SIM → simpan ke DATABASE
        $foto_ktp_nama = null;
        $foto_sim_nama = null;
        if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] === UPLOAD_ERR_OK) {
            $foto_ktp_nama = simpan_gambar_db($_FILES['foto_ktp'], 'ktp');
        }
        if (isset($_FILES['foto_sim']) && $_FILES['foto_sim']['error'] === UPLOAD_ERR_OK) {
            $foto_sim_nama = simpan_gambar_db($_FILES['foto_sim'], 'sim');
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Hitung ulang biaya dengan diskon
                $biaya = hitung_total(
                    $mobil['harga_hari'], $durasi,
                    $dengan_sopir ? $mobil['harga_sopir'] : 0,
                    $diskon
                );

                $kode_booking = buat_kode_booking();

                // Simpan booking
                $stmt_b = $pdo->prepare("
                    INSERT INTO booking (
                        kode_booking, user_id, mobil_id,
                        tgl_ambil, tgl_kembali, durasi_hari,
                        lokasi_jemput, dengan_sopir, catatan_user,
                        harga_per_hari, subtotal, biaya_sopir,
                        biaya_asuransi, biaya_admin, diskon, total,
                        promo_id, status
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')
                ");
                $stmt_b->execute([
                    $kode_booking, $user['id'], $mobil_id,
                    $tgl_ambil . ' 09:00:00',
                    $tgl_kembali . ' 09:00:00',
                    $durasi, $lokasi, $dengan_sopir, $catatan,
                    $mobil['harga_hari'],
                    $biaya['subtotal'], $biaya['biaya_sopir'],
                    $biaya['biaya_asuransi'], $biaya['biaya_admin'],
                    $biaya['diskon'], $biaya['total'],
                    $promo_id,
                ]);
                $booking_id = $pdo->lastInsertId();

                // Update data user (KTP/SIM jika baru)
                $pdo->prepare("
                    UPDATE users SET
                        nama = ?, no_hp = ?, email = ?,
                        no_ktp = ?, no_sim = ?,
                        foto_ktp = COALESCE(?, foto_ktp),
                        foto_sim = COALESCE(?, foto_sim)
                    WHERE id = ?
                ")->execute([
                    $nama, $no_hp, $email, $no_ktp, $no_sim,
                    $foto_ktp_nama, $foto_sim_nama,
                    $user['id']
                ]);

                // Update kuota promo
                if ($promo_id) {
                    $pdo->prepare("UPDATE promo SET terpakai = terpakai + 1 WHERE id = ?")
                        ->execute([$promo_id]);
                }

                // Simpan metode pembayaran & transaksi
                $kode_trx = buat_kode_transaksi();
                $pdo->prepare("
                    INSERT INTO transaksi (booking_id, kode_transaksi, metode, jumlah, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ")->execute([$booking_id, $kode_trx, $metode, $biaya['total']]);

                $pdo->commit();

                // Kirim email konfirmasi
                try {
                    kirim_konfirmasi_booking($email, $nama, [
                        'kode_booking'  => $kode_booking,
                        'nama_mobil'    => $mobil['nama'],
                        'tgl_ambil'     => $tgl_ambil . ' 09:00:00',
                        'tgl_kembali'   => $tgl_kembali . ' 09:00:00',
                        'durasi_hari'   => $durasi,
                        'dengan_sopir'  => $dengan_sopir,
                        'lokasi_jemput' => $lokasi,
                        'total'         => $biaya['total'],
                    ]);
                } catch (Exception $e) {}

                // Redirect ke halaman pembayaran
                header('Location: ' . BASE_URL . '/booking/pembayaran.php?booking_id=' . $booking_id);
                exit;

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Pesan error spesifik untuk memudahkan diagnosa
                $error = 'Gagal menyimpan booking: ' . $e->getMessage();
                error_log('Checkout error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <?php
    // Reuse tailwind config dari header
    ob_start(); include __DIR__ . '/../includes/header.php'; ob_end_clean();
    // Manual script config saja
    ?>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode:"class",
            theme:{ extend:{
                colors:{
                    "primary":"#041627","on-primary":"#ffffff",
                    "primary-container":"#1a2b3c","primary-fixed":"#d2e4fb",
                    "secondary":"#855300","secondary-container":"#fea619",
                    "on-secondary-container":"#684000",
                    "background":"#f8f9fa","on-background":"#191c1d",
                    "surface":"#f8f9fa","on-surface":"#191c1d",
                    "surface-variant":"#e1e3e4","on-surface-variant":"#44474c",
                    "surface-container-lowest":"#ffffff",
                    "surface-container-low":"#f3f4f5",
                    "surface-container":"#edeeef",
                    "surface-container-high":"#e7e8e9",
                    "surface-container-highest":"#e1e3e4",
                    "outline":"#74777d","outline-variant":"#c4c6cd",
                    "error":"#ba1a1a","error-container":"#ffdad6",
                    "on-error-container":"#93000a",
                    "secondary-fixed":"#ffddb8","on-secondary-fixed-variant":"#653e00",
                },
                spacing:{
                    "margin-desktop":"64px","margin-mobile":"16px",
                    "gutter":"24px","container-max":"1280px",
                },
                fontFamily:{
                    "headline-md":["Montserrat"],"headline-sm":["Montserrat"],
                    "display-lg-mobile":["Montserrat"],
                    "body-md":["Inter"],"body-lg":["Inter"],
                    "label-md":["Inter"],"label-sm":["Inter"],
                },
                fontSize:{
                    "display-lg-mobile":["32px",{lineHeight:"40px",letterSpacing:"-0.01em",fontWeight:"700"}],
                    "headline-md":["24px",{lineHeight:"32px",fontWeight:"600"}],
                    "headline-sm":["20px",{lineHeight:"28px",fontWeight:"600"}],
                    "body-md":["16px",{lineHeight:"24px",fontWeight:"400"}],
                    "label-md":["14px",{lineHeight:"20px",letterSpacing:"0.01em",fontWeight:"500"}],
                    "label-sm":["12px",{lineHeight:"16px",fontWeight:"600"}],
                },
            }}
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
        .filled-icon { font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24; }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col antialiased">

<!-- Header Checkout -->
<header class="bg-surface border-b border-surface-variant sticky top-0 z-[100] shadow-sm">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-4
                flex justify-between items-center">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-headline-md text-headline-md font-bold tracking-tight text-primary">
            <?= APP_NAME ?>
        </a>
        <div class="flex items-center gap-2 text-on-surface-variant">
            <span class="material-symbols-outlined text-[20px] filled-icon">lock</span>
            <span class="font-label-md text-label-md">Checkout Aman</span>
        </div>
    </div>
</header>

<main class="flex-grow pb-12">
    <!-- Stepper -->
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop pt-8 mb-8">
        <div class="max-w-lg mx-auto flex items-center">
            <?php
            $steps = ['Data Penyewa', 'Pembayaran', 'Selesai'];
            foreach ($steps as $i => $s):
                $n = $i + 1;
                $aktif = $n === 1;
            ?>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                font-label-sm text-label-sm font-bold
                                <?= $aktif ? 'bg-primary text-on-primary ring-4 ring-primary/20' : 'bg-surface-container-high text-on-surface-variant' ?>">
                        <?= $n ?>
                    </div>
                    <span class="font-label-md text-label-md mt-2
                                 <?= $aktif ? 'text-primary font-semibold' : 'text-on-surface-variant' ?>">
                        <?= $s ?>
                    </span>
                </div>
                <?php if ($i < count($steps) - 1): ?>
                    <div class="flex-1 h-0.5 mx-3 <?= $aktif ? 'bg-outline-variant' : 'bg-outline-variant' ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop mb-6">
            <div class="flex items-center gap-3 p-4 rounded-xl bg-error-container
                        border border-error/20 text-on-error-container">
                <span class="material-symbols-outlined">error</span>
                <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop">
        <form method="POST" action="" enctype="multipart/form-data"
              class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

            <!-- ── KIRI: Form ── -->
            <div class="lg:col-span-7 xl:col-span-8 flex flex-col gap-6">

                <!-- Data Penyewa -->
                <section class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                                border border-surface-variant p-6 md:p-8">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mobil_id"     value="<?= $mobil_id ?>">
                    <input type="hidden" name="tgl_ambil"    value="<?= htmlspecialchars($tgl_ambil) ?>">
                    <input type="hidden" name="tgl_kembali"  value="<?= htmlspecialchars($tgl_kembali) ?>">
                    <input type="hidden" name="dengan_sopir" value="<?= $dengan_sopir ?>">

                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6
                               flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">person</span>
                        Data Penyewa
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-5">

                        <!-- Nama -->
                        <div class="md:col-span-2">
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="nama">
                                Nama Lengkap (Sesuai KTP) *
                            </label>
                            <input type="text" id="nama" name="nama"
                                   value="<?= bersihkan($user_data['nama'] ?? '') ?>"
                                   placeholder="Nama sesuai KTP"
                                   class="w-full bg-surface-container-lowest border rounded-xl
                                          px-4 py-3 font-body-md text-body-md text-on-surface
                                          focus:outline-none focus:ring-2 focus:ring-primary
                                          focus:border-primary transition-all
                                          <?= isset($errors['nama']) ? 'border-error' : 'border-outline' ?>">
                            <?php if (isset($errors['nama'])): ?>
                                <p class="mt-1 font-label-sm text-label-sm text-error">
                                    <?= $errors['nama'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- No HP -->
                        <div>
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="no_hp">
                                No. HP / WhatsApp *
                            </label>
                            <input type="tel" id="no_hp" name="no_hp"
                                   value="<?= bersihkan($user_data['no_hp'] ?? '') ?>"
                                   placeholder="081234567890"
                                   class="w-full bg-surface-container-lowest border rounded-xl
                                          px-4 py-3 font-body-md text-body-md text-on-surface
                                          focus:outline-none focus:ring-2 focus:ring-primary
                                          focus:border-primary transition-all
                                          <?= isset($errors['no_hp']) ? 'border-error' : 'border-outline' ?>">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="email">
                                Alamat Email *
                            </label>
                            <input type="email" id="email" name="email"
                                   value="<?= bersihkan($user_data['email'] ?? '') ?>"
                                   placeholder="email@domain.com"
                                   class="w-full bg-surface-container-lowest border rounded-xl
                                          px-4 py-3 font-body-md text-body-md text-on-surface
                                          focus:outline-none focus:ring-2 focus:ring-primary
                                          focus:border-primary transition-all
                                          <?= isset($errors['email']) ? 'border-error' : 'border-outline' ?>">
                        </div>

                        <!-- No KTP -->
                        <div>
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="no_ktp">
                                Nomor KTP (NIK) *
                            </label>
                            <input type="text" id="no_ktp" name="no_ktp"
                                   value="<?= bersihkan($user_data['no_ktp'] ?? '') ?>"
                                   placeholder="16 digit NIK" maxlength="16"
                                   class="w-full bg-surface-container-lowest border rounded-xl
                                          px-4 py-3 font-body-md text-body-md text-on-surface
                                          focus:outline-none focus:ring-2 focus:ring-primary
                                          focus:border-primary transition-all
                                          <?= isset($errors['no_ktp']) ? 'border-error' : 'border-outline' ?>">
                        </div>

                        <!-- No SIM -->
                        <div>
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="no_sim">
                                Nomor SIM A
                            </label>
                            <input type="text" id="no_sim" name="no_sim"
                                   value="<?= bersihkan($user_data['no_sim'] ?? '') ?>"
                                   placeholder="Nomor SIM A"
                                   class="w-full bg-surface-container-lowest border border-outline
                                          rounded-xl px-4 py-3 font-body-md text-body-md text-on-surface
                                          focus:outline-none focus:ring-2 focus:ring-primary
                                          focus:border-primary transition-all">
                        </div>

                        <!-- Upload KTP & SIM -->
                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <!-- KTP -->
                            <label class="border-2 border-dashed border-outline-variant rounded-xl
                                          p-6 flex flex-col items-center justify-center gap-2
                                          hover:bg-surface-container-low transition-colors
                                          cursor-pointer group bg-surface-container-lowest">
                                <span class="material-symbols-outlined text-[32px] text-outline
                                             group-hover:text-primary transition-colors">
                                    id_card
                                </span>
                                <p class="font-label-md text-label-md text-on-surface text-center">
                                    <?= $user_data['foto_ktp'] ? 'Ganti Foto KTP' : 'Unggah Foto KTP' ?>
                                </p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant text-center" id="nama-ktp">
                                    Maks 2MB (JPG/PNG)
                                </p>
                                <img id="preview-ktp" class="hidden mt-2 max-h-24 rounded-lg object-contain">
                                <input type="file" name="foto_ktp" accept=".jpg,.jpeg,.png"
                                       class="hidden" onchange="previewDok(this,'ktp')">
                            </label>

                            <!-- SIM -->
                            <label class="border-2 border-dashed border-outline-variant rounded-xl
                                          p-6 flex flex-col items-center justify-center gap-2
                                          hover:bg-surface-container-low transition-colors
                                          cursor-pointer group bg-surface-container-lowest">
                                <span class="material-symbols-outlined text-[32px] text-outline
                                             group-hover:text-primary transition-colors">
                                    drive_eta
                                </span>
                                <p class="font-label-md text-label-md text-on-surface text-center">
                                    <?= $user_data['foto_sim'] ? 'Ganti Foto SIM' : 'Unggah Foto SIM A' ?>
                                </p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant text-center" id="nama-sim">
                                    Maks 2MB (JPG/PNG)
                                </p>
                                <img id="preview-sim" class="hidden mt-2 max-h-24 rounded-lg object-contain">
                                <input type="file" name="foto_sim" accept=".jpg,.jpeg,.png"
                                       class="hidden" onchange="previewDok(this,'sim')">
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Detail Penjemputan -->
                <section class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                                border border-surface-variant p-6 md:p-8">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6
                               flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">location_on</span>
                        Detail Penjemputan
                    </h2>
                    <div>
                        <label class="font-label-md text-label-md text-on-surface mb-1 block"
                               for="lokasi_jemput">
                            Alamat / Lokasi Penjemputan *
                        </label>
                        <textarea id="lokasi_jemput" name="lokasi_jemput" rows="3"
                                  placeholder="Masukkan alamat lengkap, nama hotel/bandara, atau patokan"
                                  class="w-full bg-surface-container-lowest border rounded-xl px-4 py-3
                                         font-body-md text-body-md text-on-surface
                                         focus:outline-none focus:ring-2 focus:ring-primary
                                         focus:border-primary transition-all resize-none
                                         <?= isset($errors['lokasi']) ? 'border-error' : 'border-outline' ?>"><?= bersihkan($_POST['lokasi_jemput'] ?? '') ?></textarea>
                        <div class="mt-4">
                            <label class="font-label-md text-label-md text-on-surface mb-1 block"
                                   for="catatan">
                                Catatan Tambahan (Opsional)
                            </label>
                            <textarea id="catatan" name="catatan" rows="2"
                                      placeholder="Permintaan khusus, jam penjemputan, dll."
                                      class="w-full bg-surface-container-lowest border border-outline
                                             rounded-xl px-4 py-3 font-body-md text-body-md text-on-surface
                                             focus:outline-none focus:ring-2 focus:ring-primary
                                             focus:border-primary transition-all resize-none"><?= bersihkan($_POST['catatan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </section>

                <!-- Metode Pembayaran -->
                <section class="bg-surface rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                                border border-surface-variant p-6 md:p-8">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6
                               flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">payments</span>
                        Metode Pembayaran
                    </h2>
                    <?php if (isset($errors['metode'])): ?>
                        <p class="mb-4 font-label-sm text-label-sm text-error flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['metode'] ?>
                        </p>
                    <?php endif; ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $metodes = [
                            ['transfer_bank',    'account_balance',       'Transfer Bank',       'BCA, Mandiri, BRI, BNI'],
                            ['virtual_account',  'account_balance_wallet','Virtual Account',     'Verifikasi Otomatis'],
                            ['ewallet',          'wallet',                'E-Wallet',            'GoPay, OVO, Dana, ShopeePay'],
                            ['cod',              'payments',              'Bayar di Tempat (COD)','Bayar tunai saat penjemputan'],
                        ];
                        foreach ($metodes as [$val, $ikon, $label, $sub]): ?>
                            <label class="cursor-pointer relative group">
                                <input type="radio" name="metode_bayar" value="<?= $val ?>"
                                       class="peer sr-only"
                                       <?= ($_POST['metode_bayar'] ?? '') === $val ? 'checked' : '' ?>>
                                <div class="bg-surface-container-lowest border border-outline
                                            rounded-xl p-4 flex items-center justify-between
                                            peer-checked:border-primary peer-checked:bg-primary-fixed/20
                                            peer-checked:ring-1 peer-checked:ring-primary
                                            transition-all shadow-sm hover:border-primary/50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-surface-container
                                                    flex items-center justify-center text-on-surface-variant">
                                            <span class="material-symbols-outlined"><?= $ikon ?></span>
                                        </div>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface">
                                                <?= $label ?>
                                            </p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                <?= $sub ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-outline
                                                peer-checked:border-primary flex items-center justify-center
                                                bg-surface shrink-0">
                                        <div class="w-2.5 h-2.5 rounded-full bg-primary
                                                    opacity-0 peer-checked:opacity-100 transition-opacity">
                                        </div>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- ── KANAN: Ringkasan ── -->
            <div class="lg:col-span-5 xl:col-span-4">
                <div class="sticky top-20 bg-surface-container-lowest rounded-2xl border
                            border-outline-variant/30
                            shadow-[0px_4px_20px_rgba(26,43,60,0.08)] p-6">

                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">
                        Ringkasan Pesanan
                    </h2>

                    <!-- Info Mobil -->
                    <div class="flex gap-3 mb-6 p-3 bg-surface-container-low rounded-xl">
                        <img src="<?= url_foto_mobil($mobil['foto_utama']) ?>"
                             alt="<?= htmlspecialchars($mobil['nama']) ?>"
                             class="w-20 h-16 rounded-lg object-cover shrink-0">
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold">
                                <?= htmlspecialchars($mobil['nama']) ?>
                            </p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                <?= $mobil['transmisi'] ?> · <?= $mobil['kapasitas'] ?> Kursi
                            </p>
                        </div>
                    </div>

                    <!-- Detail Tanggal -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="font-label-md text-label-md text-on-surface-variant">Tanggal Ambil</span>
                            <span class="font-label-md text-label-md text-on-surface">
                                <?= format_tanggal($tgl_ambil) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-label-md text-label-md text-on-surface-variant">Tanggal Kembali</span>
                            <span class="font-label-md text-label-md text-on-surface">
                                <?= format_tanggal($tgl_kembali) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-label-md text-label-md text-on-surface-variant">Durasi</span>
                            <span class="font-label-md text-label-md text-on-surface font-semibold">
                                <?= $durasi ?> Hari
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-label-md text-label-md text-on-surface-variant">Sopir</span>
                            <span class="font-label-md text-label-md text-on-surface">
                                <?= $dengan_sopir ? 'Ya' : 'Tidak' ?>
                            </span>
                        </div>
                    </div>

                    <hr class="border-outline-variant/30 mb-4">

                    <!-- Rincian Biaya -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">
                                Sewa (<?= $durasi ?> hari × <?= format_rupiah($mobil['harga_hari']) ?>)
                            </span>
                            <span class="text-on-surface"><?= format_rupiah($biaya['subtotal']) ?></span>
                        </div>
                        <?php if ($biaya['biaya_sopir'] > 0): ?>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Biaya Sopir</span>
                                <span class="text-on-surface"><?= format_rupiah($biaya['biaya_sopir']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Asuransi</span>
                            <span class="text-on-surface"><?= format_rupiah($biaya['biaya_asuransi']) ?></span>
                        </div>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Biaya Admin</span>
                            <span class="text-on-surface"><?= format_rupiah($biaya['biaya_admin']) ?></span>
                        </div>
                    </div>

                    <!-- Kode Promo -->
                    <div class="mb-4">
                        <div class="flex gap-2">
                            <input type="text" name="kode_promo"
                                   value="<?= bersihkan($_POST['kode_promo'] ?? '') ?>"
                                   placeholder="Kode promo (opsional)"
                                   class="flex-1 border border-outline-variant rounded-xl px-3 py-2
                                          font-label-md text-label-md text-on-surface bg-surface
                                          focus:outline-none focus:ring-1 focus:ring-primary
                                          focus:border-primary transition-all">
                            <button type="submit" name="cek_promo"
                                    class="px-4 py-2 bg-surface-container text-on-surface
                                           rounded-xl font-label-md text-label-md
                                           hover:bg-surface-container-high transition-colors">
                                Pakai
                            </button>
                        </div>
                    </div>

                    <hr class="border-outline-variant/30 mb-4">

                    <!-- Total -->
                    <div class="flex justify-between items-center mb-6">
                        <span class="font-headline-sm text-headline-sm text-on-surface">Total</span>
                        <span class="font-headline-sm text-headline-sm text-primary font-bold">
                            <?= format_rupiah($biaya['total']) ?>
                        </span>
                    </div>
                    <div class="flex justify-between font-label-sm text-label-sm mb-6">
                        <span class="text-on-surface-variant">DP Minimal (<?= DP_PERSEN ?>%)</span>
                        <span class="text-secondary font-semibold">
                            <?= format_rupiah($biaya['dp_minimal']) ?>
                        </span>
                    </div>

                    <!-- S&K -->
                    <div class="flex items-start gap-2 mb-6">
                        <input type="checkbox" name="setuju_sk" id="setuju_sk" required
                               class="mt-1 w-4 h-4 border-outline-variant text-primary
                                      focus:ring-primary rounded">
                        <label for="setuju_sk"
                               class="font-label-sm text-label-sm text-on-surface-variant cursor-pointer">
                            Saya menyetujui
                            <a href="<?= BASE_URL ?>/syarat_ketentuan.php" target="_blank"
                               class="text-primary hover:underline">
                                Syarat &amp; Ketentuan
                            </a>
                            yang berlaku.
                        </label>
                    </div>

                    <button type="submit" name="proses_checkout"
                            class="w-full bg-primary text-on-primary py-4 rounded-xl
                                   font-label-md text-label-md font-bold
                                   hover:bg-primary/90 transition-all shadow-md
                                   active:scale-[0.98] flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">arrow_forward</span>
                        Lanjut ke Pembayaran
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    // Preview foto KTP/SIM setelah dipilih
    function previewDok(input, tipe) {
        const file = input.files[0];
        if (!file) return;
        const namaEl = document.getElementById('nama-' + tipe);
        const imgEl  = document.getElementById('preview-' + tipe);
        // Tampilkan nama file
        if (namaEl) namaEl.textContent = file.name;
        // Tampilkan preview gambar
        const reader = new FileReader();
        reader.onload = e => {
            if (imgEl) {
                imgEl.src = e.target.result;
                imgEl.classList.remove('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
</script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
