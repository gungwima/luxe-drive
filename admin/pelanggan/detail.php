<?php
// ============================================================
// admin/pelanggan/detail.php — Detail Pelanggan
// ============================================================
$page_title_admin = 'Detail Pelanggan';
$menu_aktif       = 'pelanggan';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . ADMIN_URL . '/pelanggan/index.php'); exit; }

try {
    $u = $pdo->prepare("SELECT * FROM users WHERE id=?"); $u->execute([$id]);
    $user = $u->fetch();
    if (!$user) { header('Location: ' . ADMIN_URL . '/pelanggan/index.php'); exit; }

    // Riwayat booking
    $b = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama, t.status AS status_bayar
        FROM booking b JOIN mobil m ON m.id=b.mobil_id
        LEFT JOIN transaksi t ON t.booking_id=b.id
        WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 20
    ");
    $b->execute([$id]);
    $bookings = $b->fetchAll();

    // Statistik
    $total_sewa    = (int)$pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id=? AND status='selesai'")->execute([$id]) ? 0 : 0;
    $ss = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE user_id=? AND status='selesai'"); $ss->execute([$id]); $total_sewa=(int)$ss->fetchColumn();
    $sb = $pdo->prepare("SELECT COALESCE(SUM(t.jumlah),0) FROM transaksi t JOIN booking b ON b.id=t.booking_id WHERE b.user_id=? AND t.status='verified'"); $sb->execute([$id]); $total_belanja=(float)$sb->fetchColumn();
    $su = $pdo->prepare("SELECT COUNT(*) FROM ulasan WHERE user_id=?"); $su->execute([$id]); $total_ulasan=(int)$su->fetchColumn();
} catch (PDOException $e) {
    header('Location: ' . ADMIN_URL . '/pelanggan/index.php'); exit;
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1200px] mx-auto">

    <div class="flex items-center gap-2 mb-6 text-on-surface-variant font-label-md text-label-md">
        <a href="<?= ADMIN_URL ?>/pelanggan/index.php" class="hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali ke Pelanggan
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kiri: Profil -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30 text-center">
                <div class="w-24 h-24 mx-auto rounded-full bg-primary text-on-primary flex items-center justify-center
                            text-3xl font-bold overflow-hidden mb-4">
                    <?php if ($user['foto_profil']): ?>
                        <img src="<?= url_gambar($user['foto_profil'], 'profil') ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= inisial($user['nama']) ?>
                    <?php endif; ?>
                </div>
                <h1 class="font-headline-sm text-headline-sm text-primary mb-1"><?= htmlspecialchars($user['nama']) ?></h1>
                <p class="font-label-sm text-label-sm text-on-surface-variant mb-4">Member sejak <?= format_tanggal($user['created_at']) ?></p>
                <?php if (($user['status']??'aktif')==='blokir'): ?>
                    <span class="inline-flex px-3 py-1 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm">Diblokir</span>
                <?php else: ?>
                    <span class="inline-flex px-3 py-1 rounded-full bg-green-50 text-green-800 border border-green-200 font-label-sm text-label-sm">Aktif</span>
                <?php endif; ?>
            </div>

            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">Informasi Kontak</h2>
                <div class="space-y-3">
                    <?php foreach ([
                        ['mail','Email',$user['email']],
                        ['call','No. HP',$user['no_hp']],
                        ['badge','No. KTP',$user['no_ktp']],
                        ['drive_eta','No. SIM',$user['no_sim']],
                    ] as [$ikon,$lbl,$val]): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant text-[20px]"><?= $ikon ?></span>
                            <div>
                                <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $lbl ?></p>
                                <p class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($val ?: '-') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Dokumen -->
                <div class="flex gap-2 mt-4 pt-4 border-t border-outline-variant/30">
                    <?php if ($user['foto_ktp']): ?>
                        <a href="<?= url_gambar($user['foto_ktp'], 'ktp') ?>" target="_blank"
                           class="flex-1 flex items-center justify-center gap-1 px-3 py-2 rounded-lg bg-surface-container
                                  font-label-sm text-label-sm text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-sm">id_card</span> KTP
                        </a>
                    <?php endif; ?>
                    <?php if ($user['foto_sim']): ?>
                        <a href="<?= url_gambar($user['foto_sim'], 'sim') ?>" target="_blank"
                           class="flex-1 flex items-center justify-center gap-1 px-3 py-2 rounded-lg bg-surface-container
                                  font-label-sm text-label-sm text-on-surface hover:bg-surface-container-high transition-colors">
                            <span class="material-symbols-outlined text-sm">badge</span> SIM
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kanan: Statistik + Riwayat -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Statistik -->
            <div class="grid grid-cols-3 gap-4">
                <?php foreach ([
                    ['Total Sewa',$total_sewa.'x','directions_car'],
                    ['Total Belanja',format_rupiah($total_belanja),'payments'],
                    ['Ulasan',$total_ulasan,'star'],
                ] as [$lbl,$val,$ikon]): ?>
                    <div class="bg-surface rounded-xl p-5 shadow-sm border border-outline-variant/30">
                        <span class="material-symbols-outlined text-primary text-[24px] mb-2"><?= $ikon ?></span>
                        <p class="font-headline-sm text-headline-sm text-primary font-bold"><?= $val ?></p>
                        <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $lbl ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Riwayat Booking -->
            <div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
                <h2 class="font-headline-sm text-headline-sm text-on-surface p-6 pb-4">Riwayat Booking</h2>
                <?php if (empty($bookings)): ?>
                    <p class="px-6 pb-6 text-on-surface-variant font-body-md text-body-md">Belum ada booking.</p>
                <?php else: ?>
                    <div class="divide-y divide-outline-variant/20">
                        <?php foreach ($bookings as $bk): ?>
                            <a href="<?= ADMIN_URL ?>/booking/detail.php?id=<?= $bk['id'] ?>"
                               class="flex items-center gap-4 p-4 hover:bg-surface-container-low transition-colors">
                                <img src="<?= url_foto_mobil($bk['foto_utama']) ?>" class="w-14 h-10 rounded object-cover shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($bk['nama_mobil']) ?></p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($bk['kode_booking']) ?> · <?= format_tanggal($bk['tgl_ambil']) ?></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-label-md text-label-md text-primary font-bold"><?= format_rupiah($bk['total']) ?></p>
                                    <?= badge_status_booking($bk['status']) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</div>

<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
