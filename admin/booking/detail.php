<?php
// ============================================================
// admin/booking/detail.php — Detail Booking & Update Status
// ============================================================
$page_title_admin = 'Detail Booking';
$menu_aktif       = 'booking';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/email.php';

require_admin_login();
$admin = get_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . ADMIN_URL . '/booking/index.php'); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.nama AS nama_user, u.email AS email_user, u.no_hp,
               u.no_ktp, u.no_sim, u.foto_ktp, u.foto_sim,
               m.nama AS nama_mobil, m.foto_utama, m.no_plat, m.jenis,
               t.id AS trx_id, t.metode, t.jumlah, t.status AS status_bayar,
               t.bukti_bayar, t.kode_transaksi
        FROM booking b
        JOIN users u  ON u.id = b.user_id
        JOIN mobil m  ON m.id = b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id = b.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
} catch (PDOException $e) { $booking = null; }

if (!$booking) { header('Location: ' . ADMIN_URL . '/booking/index.php'); exit; }

// ── PROSES UPDATE STATUS BOOKING ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Sesi tidak valid.');
    } else {
        $status_baru  = bersihkan($_POST['status_baru']   ?? '');
        $catatan_adm  = bersihkan($_POST['catatan_admin'] ?? '');

        $valid_status = ['confirmed','berlangsung','selesai','dibatalkan'];
        if (in_array($status_baru, $valid_status)) {
            try {
                $pdo->prepare("UPDATE booking SET status=?, catatan_admin=? WHERE id=?")
                    ->execute([$status_baru, $catatan_adm, $id]);

                // Jika selesai → update mobil jadi tersedia
                if ($status_baru === 'selesai') {
                    $pdo->prepare("UPDATE mobil SET status='tersedia' WHERE id=?")
                        ->execute([$booking['mobil_id']]);
                }
                // Jika berlangsung → update mobil jadi disewa
                if ($status_baru === 'berlangsung') {
                    $pdo->prepare("UPDATE mobil SET status='disewa' WHERE id=?")
                        ->execute([$booking['mobil_id']]);
                }

                // Kirim email notif
                try {
                    kirim_notif_status($booking['email_user'], $booking['nama_user'], $booking['kode_booking'], $status_baru);
                } catch (Exception $e) {}

                set_flash('sukses', 'Status booking berhasil diperbarui.');
            } catch (PDOException $e) {
                set_flash('error', 'Gagal memperbarui status.');
            }
        }
    }
    header('Location: ' . ADMIN_URL . '/booking/detail.php?id=' . $id);
    exit;
}

// ── PROSES VERIFIKASI PEMBAYARAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi_bayar'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Sesi tidak valid.');
    } else {
        $aksi = $_POST['aksi_bayar'] ?? '';
        if (in_array($aksi, ['verified','rejected'])) {
            try {
                $pdo->prepare("UPDATE transaksi SET status=?, verified_by=?, verified_at=NOW() WHERE id=?")
                    ->execute([$aksi, $admin['id'], $booking['trx_id']]);

                if ($aksi === 'verified') {
                    $pdo->prepare("UPDATE booking SET status='confirmed' WHERE id=?")->execute([$id]);
                    set_flash('sukses', 'Pembayaran terverifikasi. Booking dikonfirmasi!');
                } else {
                    set_flash('sukses', 'Pembayaran ditolak.');
                }
            } catch (PDOException $e) {
                set_flash('error', 'Gagal memproses verifikasi.');
            }
        }
    }
    header('Location: ' . ADMIN_URL . '/booking/detail.php?id=' . $id);
    exit;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1400px] mx-auto">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 mb-6 text-on-surface-variant font-label-md text-label-md">
        <a href="<?= ADMIN_URL ?>/booking/index.php"
           class="hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali ke Booking
        </a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
        <span class="text-on-surface"><?= htmlspecialchars($booking['kode_booking']) ?></span>
    </div>

    <?= render_flash() ?>

    <!-- Header -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-2">
                Detail Booking
            </h1>
            <div class="flex items-center gap-3">
                <span class="font-label-md text-label-md text-on-surface-variant">
                    <?= htmlspecialchars($booking['kode_booking']) ?>
                </span>
                <?= badge_status_booking($booking['status']) ?>
                <?= badge_status_transaksi($booking['status_bayar'] ?? 'pending') ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Kiri: Info Detail (8 kolom) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Data Pelanggan -->
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Data Pelanggan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center
                                    text-on-primary font-bold text-lg shrink-0">
                            <?= inisial($booking['nama_user']) ?>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold">
                                <?= htmlspecialchars($booking['nama_user']) ?>
                            </p>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">
                                <?= htmlspecialchars($booking['email_user']) ?>
                            </p>
                            <p class="font-body-md text-body-md text-on-surface-variant">
                                <?= htmlspecialchars($booking['no_hp']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">No. KTP</span>
                            <span class="text-on-surface"><?= htmlspecialchars($booking['no_ktp']) ?></span>
                        </div>
                        <div class="flex justify-between font-label-md text-label-md">
                            <span class="text-on-surface-variant">No. SIM</span>
                            <span class="text-on-surface"><?= htmlspecialchars($booking['no_sim']) ?></span>
                        </div>
                    </div>
                </div>
                <!-- Dokumen KTP & SIM -->
                <div class="flex gap-3 mt-4 pt-4 border-t border-outline-variant/30">
                    <?php if ($booking['foto_ktp']): ?>
                        <a href="<?= url_gambar($booking['foto_ktp'], 'ktp') ?>"
                           target="_blank"
                           class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-container
                                  border border-outline-variant/50 font-label-sm text-label-sm text-on-surface
                                  hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-sm">id_card</span>
                            Lihat KTP
                            <span class="material-symbols-outlined text-sm text-primary">open_in_new</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($booking['foto_sim']): ?>
                        <a href="<?= url_gambar($booking['foto_sim'], 'sim') ?>"
                           target="_blank"
                           class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-container
                                  border border-outline-variant/50 font-label-sm text-label-sm text-on-surface
                                  hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-sm">badge</span>
                            Lihat SIM
                            <span class="material-symbols-outlined text-sm text-primary">open_in_new</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Sewa -->
            <div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <h2 class="font-headline-sm text-headline-sm text-on-surface p-6 pb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">car_rental</span>
                    Detail Sewa
                </h2>
                <!-- Kendaraan -->
                <div class="flex flex-col md:flex-row items-center gap-6 p-5 border-b border-outline-variant/20
                            hover:bg-surface-container-low transition-colors">
                    <div class="w-full md:w-40 h-28 rounded-xl overflow-hidden bg-surface-variant shrink-0">
                        <img src="<?= url_foto_mobil($booking['foto_utama']) ?>"
                             class="w-full h-full object-cover">
                    </div>
                    <div>
                        <p class="font-headline-sm text-headline-sm text-on-surface">
                            <?= htmlspecialchars($booking['nama_mobil']) ?>
                        </p>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            <?= htmlspecialchars($booking['jenis']) ?> ·
                            <?= htmlspecialchars($booking['no_plat']) ?>
                        </p>
                    </div>
                </div>
                <!-- Jadwal -->
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-outline-variant/20">
                    <div class="p-5 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined text-[20px]">location_on</span>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Penjemputan</p>
                            <p class="font-label-md text-label-md text-on-surface">
                                <?= format_datetime($booking['tgl_ambil']) ?>
                            </p>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                                <?= htmlspecialchars($booking['lokasi_jemput']) ?>
                            </p>
                        </div>
                    </div>
                    <div class="p-5 flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center text-secondary shrink-0">
                            <span class="material-symbols-outlined text-[20px]">flag</span>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Pengembalian</p>
                            <p class="font-label-md text-label-md text-on-surface">
                                <?= format_datetime($booking['tgl_kembali']) ?>
                            </p>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                                <?= $booking['durasi_hari'] ?> Hari ·
                                <?= $booking['dengan_sopir'] ? 'Dengan Sopir' : 'Tanpa Sopir' ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php if ($booking['catatan_user']): ?>
                    <div class="p-5 border-t border-outline-variant/20 bg-surface-container-low">
                        <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-2">
                            Catatan Pelanggan
                        </p>
                        <p class="font-body-md text-body-md text-on-surface">
                            <?= nl2br(htmlspecialchars($booking['catatan_user'])) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Verifikasi Pembayaran -->
            <?php if ($booking['trx_id'] && $booking['status_bayar'] === 'pending'): ?>
                <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">payments</span>
                        Verifikasi Pembayaran
                    </h2>
                    <div class="flex items-center justify-between mb-4 p-4 bg-surface-container-low rounded-xl">
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant">Metode</p>
                            <p class="font-label-md text-label-md text-on-surface capitalize">
                                <?= str_replace('_',' ', $booking['metode']) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-label-sm text-label-sm text-on-surface-variant">Jumlah</p>
                            <p class="font-headline-sm text-headline-sm text-primary font-bold">
                                <?= format_rupiah($booking['jumlah']) ?>
                            </p>
                        </div>
                    </div>
                    <?php if ($booking['bukti_bayar']): ?>
                        <div class="mb-4">
                            <p class="font-label-sm text-label-sm text-on-surface-variant mb-2">Bukti Transfer:</p>
                            <a href="<?= url_gambar($booking['bukti_bayar'], 'bukti_bayar') ?>"
                               target="_blank" class="inline-block">
                                <img src="<?= url_gambar($booking['bukti_bayar'], 'bukti_bayar') ?>"
                                     class="max-h-48 rounded-xl border border-outline-variant/30 object-contain">
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="font-body-md text-body-md text-on-surface-variant mb-4">
                            Belum ada bukti transfer diunggah.
                        </p>
                    <?php endif; ?>
                    <form method="POST" class="flex gap-3">
                        <?= csrf_field() ?>
                        <button type="submit" name="verifikasi_bayar" value="1"
                                onclick="document.querySelector('[name=aksi_bayar]').value='verified'"
                                class="flex-1 py-3 bg-green-600 text-white rounded-xl font-label-md text-label-md
                                       font-bold hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">check_circle</span>
                            Verifikasi & Konfirmasi
                        </button>
                        <button type="submit" name="verifikasi_bayar" value="1"
                                onclick="document.querySelector('[name=aksi_bayar]').value='rejected'"
                                class="flex-1 py-3 bg-error text-on-error rounded-xl font-label-md text-label-md
                                       font-bold hover:bg-error/90 transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">cancel</span>
                            Tolak
                        </button>
                        <input type="hidden" name="aksi_bayar" value="">
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kanan: Ringkasan Biaya + Update Status (4 kolom) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Ringkasan Biaya -->
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">receipt_long</span>
                    Ringkasan Biaya
                </h2>
                <div class="space-y-3">
                    <?php
                    $rincian = [
                        ["Sewa ({$booking['durasi_hari']} hari × " . format_rupiah($booking['harga_per_hari']) . ")", $booking['subtotal']],
                        ['Biaya Sopir', $booking['biaya_sopir']],
                        ['Asuransi',    $booking['biaya_asuransi']],
                        ['Biaya Admin', $booking['biaya_admin']],
                    ];
                    foreach ($rincian as [$l,$v]): if (!$v) continue; ?>
                        <div class="flex justify-between font-body-md text-body-md">
                            <span class="text-on-surface-variant"><?= $l ?></span>
                            <span class="text-on-surface"><?= format_rupiah($v) ?></span>
                        </div>
                    <?php endforeach;
                    if ($booking['diskon'] > 0): ?>
                        <div class="flex justify-between font-body-md text-body-md text-green-700">
                            <span>Diskon Promo</span>
                            <span>-<?= format_rupiah($booking['diskon']) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr class="border-outline-variant/30">
                    <div class="flex justify-between font-headline-sm text-headline-sm">
                        <span class="text-on-surface">Total</span>
                        <span class="text-primary font-bold"><?= format_rupiah($booking['total']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">update</span>
                    Ubah Status Booking
                </h2>
                <p class="font-label-sm text-label-sm text-on-surface-variant mb-4">
                    Status saat ini: <?= badge_status_booking($booking['status']) ?>
                </p>
                <form method="POST" class="space-y-3">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                            Status Baru
                        </label>
                        <select name="status_baru"
                                class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                       font-label-md text-label-md text-on-surface bg-surface-container-lowest
                                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <?php foreach (['confirmed'=>'Dikonfirmasi','berlangsung'=>'Berlangsung','selesai'=>'Selesai','dibatalkan'=>'Dibatalkan'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= $booking['status']===$v ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                            Catatan Admin (Opsional)
                        </label>
                        <textarea name="catatan_admin" rows="2"
                                  placeholder="Catatan internal..."
                                  class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                         font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                         focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                                         transition-all resize-none"><?= htmlspecialchars($booking['catatan_admin'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_status"
                            class="w-full py-3 bg-primary text-on-primary rounded-xl
                                   font-label-md text-label-md font-bold
                                   hover:bg-primary/90 transition-colors">
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Kirim Notif WA -->
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $booking['no_hp']) ?>?text=<?= urlencode("Halo " . $booking['nama_user'] . ", terkait booking " . $booking['kode_booking'] . " Luxe Drive:") ?>"
               target="_blank"
               class="flex items-center justify-center gap-3 w-full py-3 border border-outline-variant
                      text-on-surface rounded-xl font-label-md text-label-md hover:bg-surface-container
                      transition-colors">
                <svg class="w-5 h-5 fill-[#25D366]" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                Hubungi via WhatsApp
            </a>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
