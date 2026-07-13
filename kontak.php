<?php
// ============================================================
// kontak.php — Halaman Kontak
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Kontak';
$page_active = 'kontak';
$error = ''; $sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $nama   = bersihkan($_POST['nama']   ?? '');
        $email  = bersihkan($_POST['email']  ?? '');
        $no_hp  = bersihkan($_POST['no_hp']  ?? '');
        $subjek = bersihkan($_POST['subjek'] ?? '');
        $pesan  = bersihkan($_POST['pesan']  ?? '');

        if (!$nama || !$email || !$pesan) {
            $error = 'Nama, email, dan pesan wajib diisi.';
        } else {
            try {
                $pdo->prepare("INSERT INTO pesan_kontak (nama,email,no_hp,subjek,pesan) VALUES (?,?,?,?,?)")
                    ->execute([$nama,$email,$no_hp,$subjek,$pesan]);
                $sukses = 'Pesan berhasil dikirim! Kami akan menghubungi Anda segera.';
            } catch (PDOException $e) {
                $error = 'Gagal mengirim pesan. Coba lagi.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="flex-grow max-w-container-max mx-auto px-margin-mobile md:px-margin-desktop py-12">
    <div class="text-center mb-12">
        <h1 class="font-headline-md text-headline-md text-on-surface mb-4">Hubungi Kami</h1>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-xl mx-auto">
            Tim kami siap membantu Anda 24/7. Jangan ragu untuk menghubungi kami.
        </p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Form Kontak -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-sm p-8">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Kirim Pesan</h2>
            <?php if ($sukses): ?>
                <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800">
                    <span class="material-symbols-outlined">check_circle</span>
                    <p class="font-label-md text-label-md"><?= $sukses ?></p>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
                    <span class="material-symbols-outlined">error</span>
                    <p class="font-label-md text-label-md"><?= $error ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Nama *</label>
                        <input type="text" name="nama" placeholder="Nama lengkap" required
                               class="w-full border border-outline-variant rounded-xl px-4 py-3 bg-surface font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">No. HP</label>
                        <input type="tel" name="no_hp" placeholder="081234..."
                               class="w-full border border-outline-variant rounded-xl px-4 py-3 bg-surface font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Email *</label>
                    <input type="email" name="email" placeholder="email@domain.com" required
                           class="w-full border border-outline-variant rounded-xl px-4 py-3 bg-surface font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Subjek</label>
                    <input type="text" name="subjek" placeholder="Topik pesan Anda"
                           class="w-full border border-outline-variant rounded-xl px-4 py-3 bg-surface font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Pesan *</label>
                    <textarea name="pesan" rows="5" placeholder="Tuliskan pesan Anda..." required
                              class="w-full border border-outline-variant rounded-xl px-4 py-3 bg-surface font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all resize-none"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary text-on-primary py-3.5 rounded-xl font-label-md text-label-md font-bold hover:bg-primary/90 transition-all shadow-md active:scale-[0.98]">
                    KIRIM PESAN
                </button>
            </form>
        </div>
        <!-- Info Kontak -->
        <div class="space-y-6">
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-sm p-8">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">Informasi Kontak</h2>
                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary">location_on</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold mb-1">Alamat</p>
                            <p class="font-body-md text-body-md text-on-surface-variant"><?= APP_ADDRESS ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary">call</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold mb-1">Telepon</p>
                            <a href="tel:<?= APP_PHONE ?>" class="font-body-md text-body-md text-primary hover:underline"><?= APP_PHONE ?></a>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary">chat</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold mb-1">WhatsApp</p>
                            <a href="https://wa.me/<?= APP_WHATSAPP ?>" target="_blank" class="font-body-md text-body-md text-primary hover:underline">Hubungi via WhatsApp</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary">mail</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-semibold mb-1">Email</p>
                            <a href="mailto:<?= APP_EMAIL ?>" class="font-body-md text-body-md text-primary hover:underline"><?= APP_EMAIL ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 shadow-sm p-6">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-3">Jam Operasional</p>
                <p class="font-body-md text-body-md text-on-surface font-semibold">Senin – Minggu</p>
                <p class="font-body-md text-body-md text-on-surface-variant">07:00 – 22:00 WIB</p>
                <p class="font-label-sm text-label-sm text-secondary mt-2">24 jam untuk layanan darurat via WhatsApp</p>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
