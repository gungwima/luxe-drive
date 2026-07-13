<?php
// ============================================================
// booking/pembayaran.php — Halaman Pembayaran (Step 2)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user = get_user_login();

$booking_id = (int)($_GET['booking_id'] ?? 0);
if (!$booking_id) { header('Location: ' . BASE_URL . '/daftar_armada.php'); exit; }

// Ambil data booking + mobil + transaksi
try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama, m.harga_hari,
               t.id AS transaksi_id, t.metode, t.kode_transaksi, t.status AS status_trx
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        WHERE b.id = ? AND b.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $user['id']]);
    $booking = $stmt->fetch();
} catch (PDOException $e) { $booking = null; }

if (!$booking) {
    header('Location: ' . BASE_URL . '/user/pesanan.php');
    exit;
}

$error = '';

// ── PROSES KONFIRMASI COD / TUNAI (tanpa upload bukti) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_cod'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        try {
            // COD: transaksi tetap pending (dibayar saat serah terima), booking dikonfirmasi
            $pdo->prepare("UPDATE transaksi SET status = 'pending' WHERE id = ?")
                ->execute([$booking['transaksi_id']]);
            set_flash('sukses', 'Pesanan berhasil dikonfirmasi! Tim kami akan segera menghubungi Anda.');
            header('Location: ' . BASE_URL . '/booking/status.php?booking_id=' . $booking_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memproses. Coba lagi.';
        }
    }
}

// ── PROSES UPLOAD BUKTI BAYAR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } elseif (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
        // Deteksi jenis error upload agar pesannya jelas
        $kode_err = $_FILES['bukti_bayar']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error = match($kode_err) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas server. Coba file lebih kecil.',
            UPLOAD_ERR_PARTIAL   => 'Upload terputus. Silakan coba lagi.',
            UPLOAD_ERR_NO_FILE   => 'Belum ada file dipilih. Silakan pilih bukti pembayaran.',
            UPLOAD_ERR_NO_TMP_DIR=> 'Server tidak memiliki folder temporary. Hubungi admin server.',
            UPLOAD_ERR_CANT_WRITE=> 'Server gagal menyimpan file. Periksa izin folder.',
            default              => 'Gagal mengunggah file. Silakan coba lagi.',
        };
    } else {
        $ext_cek = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_cek, ALLOWED_IMAGES)) {
            $error = 'Format file harus JPG, JPEG, PNG, atau WEBP. File Anda: .' . $ext_cek;
        } elseif ($_FILES['bukti_bayar']['size'] > MAX_FILE_SIZE) {
            $error = 'Ukuran file maksimal 2MB. File Anda: ' . round($_FILES['bukti_bayar']['size']/1024/1024, 2) . 'MB.';
        } else {
            $nama_file = simpan_gambar_db($_FILES['bukti_bayar'], 'bukti_bayar');
            if ($nama_file) {
                try {
                    $pdo->prepare("UPDATE transaksi SET bukti_bayar = ?, status = 'pending' WHERE id = ?")
                        ->execute([$nama_file, $booking['transaksi_id']]);
                    set_flash('sukses', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
                    header('Location: ' . BASE_URL . '/booking/status.php?booking_id=' . $booking_id);
                    exit;
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan bukti. Coba lagi.';
                }
            } else {
                $error = 'Gagal menyimpan bukti pembayaran. Pastikan file gambar valid (JPG/PNG/WEBP, maks 2MB).';
            }
        }
    }
}

// Info rekening berdasarkan metode
$info_bayar = [
    'transfer_bank' => [
        'judul' => 'Transfer Bank',
        'detail' => [
            ['Bank BCA',     '1234567890', 'PT Luxe Drive Indonesia'],
            ['Bank Mandiri', '0987654321', 'PT Luxe Drive Indonesia'],
        ]
    ],
    'virtual_account' => [
        'judul' => 'Virtual Account',
        'detail' => [
            ['BCA VA',     'VA' . str_pad($booking['id'], 10, '0', STR_PAD_LEFT), 'Bayar sebelum ' . date('d M Y H:i', strtotime('+24 hours'))],
        ]
    ],
    'ewallet' => [
        'judul' => 'E-Wallet',
        'detail' => [
            ['GoPay / OVO / Dana', '0812-3456-7890', APP_NAME],
        ]
    ],
    'cod' => [
        'judul' => 'Bayar di Tempat (COD)',
        'detail' => []
    ],
];
$info = $info_bayar[$booking['metode']] ?? $info_bayar['transfer_bank'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode:"class",
            theme:{ extend:{
                colors:{
                    "primary":"#041627","on-primary":"#ffffff",
                    "primary-container":"#1a2b3c","primary-fixed":"#d2e4fb",
                    "secondary":"#855300","secondary-container":"#fea619",
                    "on-secondary-container":"#684000","secondary-fixed":"#ffddb8",
                    "on-secondary-fixed-variant":"#653e00",
                    "background":"#f8f9fa","on-background":"#191c1d",
                    "surface":"#f8f9fa","on-surface":"#191c1d",
                    "surface-variant":"#e1e3e4","on-surface-variant":"#44474c",
                    "surface-container-lowest":"#ffffff",
                    "surface-container-low":"#f3f4f5",
                    "surface-container":"#edeeef","surface-container-high":"#e7e8e9",
                    "outline":"#74777d","outline-variant":"#c4c6cd",
                    "error":"#ba1a1a","error-container":"#ffdad6","on-error-container":"#93000a",
                },
                spacing:{ "margin-desktop":"64px","margin-mobile":"16px","gutter":"24px","container-max":"1280px" },
                fontFamily:{
                    "headline-md":["Montserrat"],"headline-sm":["Montserrat"],
                    "display-lg-mobile":["Montserrat"],
                    "body-md":["Inter"],"label-md":["Inter"],"label-sm":["Inter"],
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
        .material-symbols-outlined { font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
        .filled-icon { font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24; }
    </style>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex flex-col antialiased">

<!-- Header -->
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
            <?php foreach (['Data Penyewa','Pembayaran','Selesai'] as $i => $s):
                $n = $i + 1; $selesai = $n < 2; $aktif = $n === 2; ?>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                font-label-sm text-label-sm font-bold
                                <?= $selesai ? 'bg-primary text-on-primary' : ($aktif ? 'bg-primary text-on-primary ring-4 ring-primary/20' : 'bg-surface-container-high text-on-surface-variant') ?>">
                        <?= $selesai ? '<span class="material-symbols-outlined text-sm filled-icon">check</span>' : $n ?>
                    </div>
                    <span class="font-label-md text-label-md mt-2
                                 <?= $aktif ? 'text-primary font-semibold' : ($selesai ? 'text-primary' : 'text-on-surface-variant') ?>">
                        <?= $s ?>
                    </span>
                </div>
                <?php if ($i < 2): ?>
                    <div class="flex-1 h-0.5 mx-3 <?= $selesai ? 'bg-primary' : 'bg-outline-variant' ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop mb-6">
            <div class="flex items-center gap-3 p-4 rounded-xl bg-error-container text-on-error-container">
                <span class="material-symbols-outlined">error</span>
                <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop">

        <!-- Kode Booking -->
        <div class="bg-surface-container-low rounded-2xl p-4 mb-6
                    flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary">receipt_long</span>
                <div>
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                        Kode Booking
                    </p>
                    <p class="font-headline-sm text-headline-sm text-primary font-bold">
                        <?= htmlspecialchars($booking['kode_booking']) ?>
                    </p>
                </div>
            </div>
            <?= badge_status_booking($booking['status']) ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">

            <!-- ── Instruksi Pembayaran ── -->
            <div class="lg:col-span-7">
                <div class="bg-surface rounded-xl p-8 shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                            border border-surface-container border-b-4 border-b-secondary-container">

                    <h2 class="font-headline-sm text-headline-sm text-primary font-bold mb-2">
                        SELESAIKAN PEMBAYARAN
                    </h2>
                    <p class="font-label-sm text-label-sm text-outline uppercase tracking-widest mb-6">
                        <?= $info['judul'] ?>
                    </p>

                    <?php if ($booking['metode'] === 'cod'): ?>
                        <!-- COD -->
                        <div class="bg-secondary-fixed/30 rounded-xl p-6 mb-6">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-full bg-secondary-fixed
                                            flex items-center justify-center text-on-secondary-fixed-variant shrink-0">
                                    <span class="material-symbols-outlined text-2xl">payments</span>
                                </div>
                                <div>
                                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold mb-2">
                                        Bayar di Tempat (COD)
                                    </h3>
                                    <p class="font-body-md text-body-md text-on-surface-variant mb-3">
                                        Siapkan uang tunai saat kendaraan diantar ke lokasi Anda.
                                        Driver akan mengkonfirmasi pembayaran saat tiba.
                                    </p>
                                    <div class="flex items-center gap-2 bg-surface rounded-lg p-3">
                                        <span class="font-label-sm text-label-sm text-on-surface-variant">Jumlah:</span>
                                        <span class="font-headline-sm text-headline-sm text-primary font-bold">
                                            <?= format_rupiah($booking['total']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <?= csrf_field() ?>
                            <button type="submit" name="konfirmasi_cod"
                               class="w-full bg-primary text-on-primary py-4 rounded-xl
                                      font-label-md text-label-md font-bold
                                      hover:bg-primary/90 transition-all text-center
                                      flex items-center justify-center gap-2 active:scale-[0.98]">
                                KONFIRMASI PESANAN
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- Transfer / VA / E-Wallet -->
                        <?php foreach ($info['detail'] as $d): ?>
                            <div class="bg-surface-container-low rounded-xl p-5 mb-4">
                                <p class="font-label-sm text-label-sm text-on-surface-variant mb-1">
                                    <?= htmlspecialchars($d[0]) ?>
                                </p>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-headline-sm text-headline-sm text-primary font-bold tracking-wider">
                                        <?= htmlspecialchars($d[1]) ?>
                                    </p>
                                    <button onclick="salinTeks('<?= $d[1] ?>', this)"
                                            class="flex items-center gap-1 px-3 py-1 rounded-full
                                                   border border-outline-variant text-on-surface-variant
                                                   hover:bg-surface-container font-label-sm text-label-sm
                                                   transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-sm">content_copy</span>
                                        Salin
                                    </button>
                                </div>
                                <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                                    a.n. <?= htmlspecialchars($d[2]) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>

                        <!-- Nominal -->
                        <div class="bg-primary/5 rounded-xl p-4 mb-6 flex items-center justify-between">
                            <span class="font-label-md text-label-md text-on-surface-variant">
                                Nominal Transfer
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-headline-sm text-headline-sm text-primary font-bold" id="nominal-transfer">
                                    <?= format_rupiah($booking['total']) ?>
                                </span>
                                <button onclick="salinTeks('<?= $booking['total'] ?>', this)"
                                        class="p-1 text-on-surface-variant hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-sm">content_copy</span>
                                </button>
                            </div>
                        </div>

                        <!-- Upload Bukti -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="border-2 border-dashed border-outline-variant rounded-xl p-6
                                        text-center hover:bg-surface-container-low transition-colors mb-4">
                                <span class="material-symbols-outlined text-[40px] text-outline mb-3 block">
                                    upload_file
                                </span>
                                <p class="font-label-md text-label-md text-on-surface mb-2">
                                    Upload Bukti Transfer
                                </p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant mb-4">
                                    Format JPG/PNG · Maks 2MB
                                </p>
                                <label class="cursor-pointer inline-flex items-center gap-2
                                              bg-surface-container border border-outline-variant
                                              px-4 py-2 rounded-lg hover:bg-surface-container-high
                                              transition-colors font-label-md text-label-md text-on-surface">
                                    <span class="material-symbols-outlined text-sm">attach_file</span>
                                    Pilih File
                                    <input type="file" name="bukti_bayar"
                                           accept=".jpg,.jpeg,.png" class="hidden"
                                           onchange="tampilkanNamaFile(this)">
                                </label>
                                <p id="nama-file-terpilih"
                                   class="font-label-sm text-label-sm text-primary mt-2 hidden">
                                </p>
                            </div>
                            <button type="submit" name="upload_bukti"
                                    class="w-full bg-primary text-on-primary py-4 rounded-xl
                                           font-label-md text-label-md font-bold
                                           hover:bg-primary/90 transition-all shadow-md
                                           active:scale-[0.98]">
                                KONFIRMASI PEMBAYARAN
                            </button>
                        </form>
                        <!-- Opsi lewati: upload bukti nanti -->
                        <a href="<?= BASE_URL ?>/booking/status.php?booking_id=<?= $booking_id ?>"
                           class="block text-center mt-3 font-label-sm text-label-sm
                                  text-on-surface-variant hover:text-primary transition-colors">
                            Nanti saja, saya akan upload bukti transfer kemudian
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Ringkasan ── -->
            <div class="lg:col-span-5">
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30
                            shadow-sm p-6">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">
                        Detail Pesanan
                    </h3>
                    <div class="flex gap-3 mb-4">
                        <img src="<?= url_foto_mobil($booking['foto_utama']) ?>"
                             alt="<?= htmlspecialchars($booking['nama_mobil']) ?>"
                             class="w-20 h-16 rounded-lg object-cover shrink-0">
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold">
                                <?= htmlspecialchars($booking['nama_mobil']) ?>
                            </p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                <?= $booking['dengan_sopir'] ? 'Dengan Sopir' : 'Tanpa Sopir' ?>
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Ambil</span>
                            <span class="text-on-surface"><?= format_datetime($booking['tgl_ambil']) ?></span>
                        </div>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Kembali</span>
                            <span class="text-on-surface"><?= format_datetime($booking['tgl_kembali']) ?></span>
                        </div>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Lokasi</span>
                            <span class="text-on-surface text-right max-w-[60%]">
                                <?= htmlspecialchars($booking['lokasi_jemput']) ?>
                            </span>
                        </div>
                    </div>
                    <hr class="border-outline-variant/30 mb-4">
                    <div class="space-y-2">
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Subtotal</span>
                            <span><?= format_rupiah($booking['subtotal']) ?></span>
                        </div>
                        <?php if ($booking['biaya_sopir'] > 0): ?>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Sopir</span>
                                <span><?= format_rupiah($booking['biaya_sopir']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">Asuransi</span>
                            <span><?= format_rupiah($booking['biaya_asuransi']) ?></span>
                        </div>
                        <?php if ($booking['diskon'] > 0): ?>
                            <div class="flex justify-between font-label-md text-label-md text-green-700">
                                <span>Diskon Promo</span>
                                <span>-<?= format_rupiah($booking['diskon']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr class="border-outline-variant/30 my-3">
                    <div class="flex justify-between font-headline-sm text-headline-sm">
                        <span class="text-on-surface">Total</span>
                        <span class="text-primary font-bold"><?= format_rupiah($booking['total']) ?></span>
                    </div>

                    <div class="mt-6 pt-4 border-t border-outline-variant/30">
                        <a href="https://wa.me/<?= APP_WHATSAPP ?>?text=Halo+Luxe+Drive,+saya+ingin+konfirmasi+pembayaran+untuk+booking+<?= urlencode($booking['kode_booking']) ?>"
                           target="_blank"
                           class="flex items-center justify-center gap-2 w-full border
                                  border-outline-variant text-on-surface py-3 rounded-xl
                                  font-label-md text-label-md hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-secondary-container text-[20px]">chat</span>
                            Konfirmasi via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function salinTeks(teks, btn) {
        navigator.clipboard.writeText(teks).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">check</span> Tersalin!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
    function tampilkanNamaFile(input) {
        const el = document.getElementById('nama-file-terpilih');
        if (input.files[0]) {
            el.textContent = '✓ ' + input.files[0].name;
            el.classList.remove('hidden');
        }
    }
</script>
</body>
</html>
