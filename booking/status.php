<?php
// ============================================================
// booking/status.php — Status Pemesanan Berhasil (Step 3)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user = get_user_login();

$booking_id = (int)($_GET['booking_id'] ?? 0);
if (!$booking_id) { header('Location: ' . BASE_URL . '/user/pesanan.php'); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama, m.jenis,
               t.metode, t.status AS status_trx, t.kode_transaksi
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        WHERE b.id = ? AND b.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$booking_id, $user['id']]);
    $booking = $stmt->fetch();
} catch (PDOException $e) { $booking = null; }

if (!$booking) { header('Location: ' . BASE_URL . '/user/pesanan.php'); exit; }

$page_title  = 'Status Pemesanan';
$page_active = '';
require_once __DIR__ . '/../includes/header.php';
?>

<body class="bg-background min-h-screen flex flex-col antialiased text-on-surface font-body-md">

<!-- Header minimal -->
<header class="bg-surface border-b border-surface-variant fixed top-0 left-0 z-[100] shadow-sm w-full">
    <div class="max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-4
                flex justify-between items-center">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-headline-md text-headline-md font-bold tracking-tight text-primary">
            <?= APP_NAME ?>
        </a>
        <div class="flex items-center gap-2 text-on-surface-variant">
            <span class="material-symbols-outlined text-[20px]"
                  style="font-variation-settings:'FILL' 1">lock</span>
            <span class="font-label-md text-label-md">Checkout Aman</span>
        </div>
    </div>
</header>

<main class="flex-grow pt-24 pb-12 px-margin-mobile md:px-margin-desktop flex flex-col items-center">

    <?= render_flash() ?>

    <div class="w-full max-w-3xl bg-surface-container-lowest rounded-2xl
                shadow-[0px_4px_20px_rgba(26,43,60,0.08)] p-6 md:p-12 relative overflow-hidden">

        <!-- Garis gradient atas -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-primary to-secondary-container"></div>

        <!-- Tombol Kembali -->
        <div class="mb-6">
            <a href="<?= BASE_URL ?>/user/pesanan.php"
               class="flex items-center gap-2 text-on-surface-variant hover:text-primary
                      transition-colors font-label-md text-label-md group">
                <span class="material-symbols-outlined text-[20px]
                             group-hover:-translate-x-1 transition-transform">
                    arrow_back
                </span>
                Kembali ke Pesanan
            </a>
        </div>

        <!-- Ikon Sukses -->
        <div class="flex flex-col items-center text-center mb-10 mt-4">
            <div class="w-24 h-24 rounded-full bg-green-50 flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-6xl text-green-700"
                      style="font-variation-settings:'FILL' 1">
                    check_circle
                </span>
            </div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-4">
                PEMESANAN BERHASIL!
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                Pemesanan berhasil dikirimkan. Notifikasi dikirim ke email Anda.
            </p>

            <!-- Kode & Status -->
            <div class="flex flex-col sm:flex-row items-center gap-4
                        bg-surface-container-low px-6 py-3 rounded-full">
                <span class="font-label-md text-label-md text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-xl">receipt_long</span>
                    Kode: <strong class="text-primary tracking-wide">
                        <?= htmlspecialchars($booking['kode_booking']) ?>
                    </strong>
                </span>
                <span class="hidden sm:block w-1 h-1 rounded-full bg-outline-variant"></span>
                <?= badge_status_booking($booking['status']) ?>
            </div>
        </div>

        <hr class="border-outline-variant/50 mb-10">

        <!-- Ringkasan Pesanan -->
        <div class="mb-10">
            <h2 class="font-headline-sm text-headline-sm text-primary mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined">description</span>
                Ringkasan Pesanan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter
                        bg-surface rounded-xl border border-outline-variant/30 p-6">

                <!-- Foto Mobil -->
                <div class="flex flex-col gap-4">
                    <div class="w-full h-36 rounded-lg overflow-hidden bg-surface-container-highest">
                        <img src="<?= url_foto_mobil($booking['foto_utama']) ?>"
                             alt="<?= htmlspecialchars($booking['nama_mobil']) ?>"
                             class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-primary mb-1">
                            <?= htmlspecialchars($booking['nama_mobil']) ?>
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            <?= $booking['dengan_sopir'] ? 'Dengan Sopir' : 'Tanpa Sopir' ?>
                        </p>
                    </div>
                </div>

                <!-- Detail Waktu & Biaya -->
                <div class="flex flex-col justify-between gap-4 md:border-l
                            md:border-outline-variant/30 md:pl-6">
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center mt-1">
                                <span class="w-3 h-3 rounded-full bg-primary ring-4 ring-primary-fixed"></span>
                                <span class="w-0.5 h-8 bg-outline-variant/50 my-1"></span>
                                <span class="w-3 h-3 rounded-full bg-secondary-container ring-4 ring-surface"></span>
                            </div>
                            <div class="flex flex-col justify-between h-full space-y-4">
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                        Mulai
                                    </p>
                                    <p class="font-label-md text-label-md text-primary">
                                        <?= format_datetime($booking['tgl_ambil']) ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                        Selesai
                                    </p>
                                    <p class="font-label-md text-label-md text-primary">
                                        <?= format_datetime($booking['tgl_kembali']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                Lokasi Jemput
                            </p>
                            <p class="font-label-md text-label-md text-on-surface">
                                <?= htmlspecialchars($booking['lokasi_jemput']) ?>
                            </p>
                        </div>

                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
                                Metode Pembayaran
                            </p>
                            <p class="font-label-md text-label-md text-on-surface capitalize">
                                <?= str_replace('_', ' ', $booking['metode']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-outline-variant/30 flex justify-between items-end">
                        <span class="font-body-md text-body-md text-on-surface-variant">
                            Total Pembayaran
                        </span>
                        <span class="font-headline-md text-headline-md text-primary font-bold">
                            <?= format_rupiah($booking['total']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?= BASE_URL ?>/user/pesanan.php"
               class="w-full sm:w-auto px-6 py-3 bg-primary text-on-primary rounded-xl
                      font-label-md text-label-md font-bold hover:bg-primary/90
                      transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                Lihat Semua Pesanan
            </a>
            <a href="https://wa.me/<?= APP_WHATSAPP ?>?text=Halo+Luxe+Drive,+saya+ingin+konfirmasi+pesanan+<?= urlencode($booking['kode_booking']) ?>"
               target="_blank"
               class="w-full sm:w-auto px-6 py-3 bg-surface-container-low text-primary
                      rounded-xl font-label-md text-label-md hover:bg-surface-container-high
                      transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5 fill-[#25D366]" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Hubungi Admin
            </a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
