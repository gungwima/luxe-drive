<?php
// ============================================================
// user/tulis_ulasan.php — Form Tulis Ulasan
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

require_user_login();
$user = get_user_login();

$booking_id = (int)($_GET['booking_id'] ?? 0);
$error = '';

// Ambil daftar booking selesai yang belum diulas
try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.nama AS nama_mobil, m.foto_utama
        FROM booking b
        JOIN mobil m ON m.id = b.mobil_id
        WHERE b.user_id = ? AND b.status = 'selesai'
        AND b.id NOT IN (SELECT booking_id FROM ulasan WHERE booking_id IS NOT NULL)
        ORDER BY b.tgl_kembali DESC
    ");
    $stmt->execute([$user['id']]);
    $booking_list = $stmt->fetchAll();
} catch (PDOException $e) { $booking_list = []; }

// Jika ada booking_id di URL, ambil data booking tersebut
$booking_aktif = null;
if ($booking_id) {
    foreach ($booking_list as $b) {
        if ((int)$b['id'] === $booking_id) { $booking_aktif = $b; break; }
    }
    if (!$booking_aktif) {
        set_flash('error', 'Booking tidak ditemukan atau sudah pernah diulas.');
        header('Location: ' . BASE_URL . '/user/ulasan_saya.php');
        exit;
    }
}

// ── PROSES KIRIM ULASAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $bid        = (int)    ($_POST['booking_id']  ?? 0);
        $rating     = (int)    ($_POST['rating']      ?? 0);
        $judul      = bersihkan($_POST['judul']       ?? '');
        $komentar   = bersihkan($_POST['komentar']    ?? '');
        $tampil     = isset($_POST['tampil_nama']) ? 1 : 0;

        if (!$bid)     $error = 'Pilih pesanan yang ingin diulas.';
        elseif ($rating < 1 || $rating > 5) $error = 'Pilih rating bintang (1–5).';
        elseif (str_word_count($komentar) < 5) $error = 'Komentar minimal 5 kata.';
        else {
            // Verifikasi booking milik user & sudah selesai
            $cek = $pdo->prepare("SELECT id, mobil_id FROM booking WHERE id=? AND user_id=? AND status='selesai'");
            $cek->execute([$bid, $user['id']]);
            $bk = $cek->fetch();
            if (!$bk) $error = 'Booking tidak valid.';
            else {
                // Cek belum pernah ulasan
                $cek2 = $pdo->prepare("SELECT id FROM ulasan WHERE booking_id=?");
                $cek2->execute([$bid]);
                if ($cek2->fetch()) $error = 'Booking ini sudah pernah diulas.';
                else {
                    // Upload foto ulasan (maks 3)
                    $foto = [null, null, null];
                    for ($i = 1; $i <= 3; $i++) {
                        $key = "foto_ulasan_{$i}";
                        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                            $upload = simpan_gambar_db($_FILES[$key], 'ulasan');
                            if ($upload) $foto[$i-1] = $upload;
                        }
                    }

                    try {
                        $pdo->prepare("
                            INSERT INTO ulasan
                            (booking_id, user_id, mobil_id, rating, judul, komentar,
                             foto_1, foto_2, foto_3, tampil_nama, status)
                            VALUES (?,?,?,?,?,?,?,?,?,?,'pending')
                        ")->execute([$bid, $user['id'], $bk['mobil_id'],
                                     $rating, $judul ?: null, $komentar,
                                     $foto[0], $foto[1], $foto[2], $tampil]);

                        set_flash('sukses', 'Ulasan berhasil dikirim! Sedang ditinjau admin.');
                        header('Location: ' . BASE_URL . '/user/ulasan_saya.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Gagal menyimpan ulasan: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$page_title_user = 'Tulis Ulasan';
require_once __DIR__ . '/../includes/header.php';
?>
<body class="bg-background text-on-background min-h-screen flex">
<?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>

<div class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen pt-16">
    <header class="fixed top-0 left-0 right-0 h-16 bg-surface border-b border-outline-variant/30
                   z-[60] flex items-center justify-between px-margin-mobile md:px-margin-desktop">
        <a href="<?= BASE_URL ?>/user/ulasan_saya.php"
           class="font-label-md text-label-md text-on-surface hover:text-primary transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali
        </a>
    </header>

    <main class="flex-1 py-8 px-margin-mobile md:px-margin-desktop w-full max-w-3xl mx-auto">

        <div class="mb-8">
            <h1 class="font-headline-md text-headline-md text-primary mb-2">Tulis Ulasan Anda</h1>
            <?php if ($booking_aktif): ?>
                <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">directions_car</span>
                    Booking #<?= htmlspecialchars($booking_aktif['kode_booking']) ?> ·
                    <?= htmlspecialchars($booking_aktif['nama_mobil']) ?> ·
                    <?= format_tanggal($booking_aktif['tgl_ambil']) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
                <span class="material-symbols-outlined">error</span>
                <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-surface-container-lowest rounded-xl shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                    border border-outline-variant/30 p-6 md:p-8">
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="kirim_ulasan" value="1">

                <!-- Pilih Pesanan -->
                <?php if (!$booking_aktif): ?>
                    <div class="mb-8">
                        <label class="block font-headline-sm text-headline-sm text-primary mb-4">
                            Pilih Pesanan yang Ingin Diulas *
                        </label>
                        <?php if (empty($booking_list)): ?>
                            <div class="text-center py-10 bg-surface-container-low rounded-xl">
                                <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-3 block">rate_review</span>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    Belum ada pesanan yang bisa diulas.
                                </p>
                                <p class="font-label-sm text-label-sm text-on-surface-variant mt-2 max-w-md mx-auto">
                                    Anda hanya bisa menulis ulasan untuk sewa yang <strong>sudah selesai</strong>
                                    (mobil sudah dikembalikan &amp; status booking "Selesai"). Pesanan yang masih
                                    berjalan atau menunggu belum bisa diulas.
                                </p>
                                <a href="<?= BASE_URL ?>/user/pesanan.php"
                                   class="inline-flex items-center gap-2 mt-4 bg-primary text-on-primary
                                          px-6 py-2 rounded-full font-label-md text-label-md hover:bg-primary/90">
                                    Lihat Pesanan Saya
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($booking_list as $b): ?>
                                    <label class="flex items-center gap-4 p-4 rounded-xl border
                                                  border-outline-variant bg-surface cursor-pointer
                                                  hover:border-primary transition-colors group">
                                        <input type="radio" name="booking_id"
                                               value="<?= $b['id'] ?>"
                                               <?= (int)($_POST['booking_id']??0)===$b['id'] ? 'checked' : '' ?>
                                               class="w-5 h-5 border-outline-variant text-primary focus:ring-primary">
                                        <img src="<?= url_foto_mobil($b['foto_utama']) ?>"
                                             alt="<?= htmlspecialchars($b['nama_mobil']) ?>"
                                             class="w-16 h-12 rounded-lg object-cover shrink-0">
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface font-semibold
                                                       group-hover:text-primary transition-colors">
                                                <?= htmlspecialchars($b['nama_mobil']) ?>
                                            </p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                #<?= htmlspecialchars($b['kode_booking']) ?> ·
                                                <?= format_tanggal($b['tgl_ambil']) ?> –
                                                <?= format_tanggal($b['tgl_kembali']) ?>
                                            </p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="booking_id" value="<?= $booking_aktif['id'] ?>">
                <?php endif; ?>

                <?php if (!empty($booking_list) || $booking_aktif): ?>

                    <hr class="border-outline-variant/30 mb-8">

                    <!-- Rating Keseluruhan -->
                    <div class="mb-8 flex flex-col items-center">
                        <label class="font-headline-sm text-headline-sm text-primary mb-4 text-center">
                            Bagaimana pengalaman sewa Anda?
                        </label>
                        <div class="star-rating flex gap-3 mb-2" id="rating-container">
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <button type="button" class="star-btn" data-nilai="<?= $i ?>"
                                        onclick="setRating(<?= $i ?>)">
                                    <span class="material-symbols-outlined text-5xl cursor-pointer
                                                 text-outline-variant hover:text-secondary-container
                                                 transition-colors" id="star-<?= $i ?>">
                                        star
                                    </span>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-value"
                               value="<?= (int)($_POST['rating']??0) ?>">
                        <span id="rating-label" class="font-label-md text-label-md text-secondary-container font-bold">
                            <?php $rl=['','Buruk','Kurang','Cukup','Baik','Sangat Puas!'];
                            echo $rl[(int)($_POST['rating']??0)] ?? ''; ?>
                        </span>
                    </div>

                    <hr class="border-outline-variant/30 mb-8">

                    <!-- Judul -->
                    <div class="mb-6">
                        <label class="block font-label-md text-label-md text-primary mb-2" for="judul">
                            Judul Ulasan
                            <span class="font-body-md text-body-md text-on-surface-variant font-normal">
                                (Opsional)
                            </span>
                        </label>
                        <input type="text" id="judul" name="judul"
                               value="<?= bersihkan($_POST['judul'] ?? '') ?>"
                               placeholder="Contoh: Pengalaman sewa yang menyenangkan!"
                               class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest
                                      py-3 px-4 font-body-md text-body-md text-on-surface outline-none
                                      focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    </div>

                    <!-- Komentar -->
                    <div class="mb-8">
                        <label class="block font-label-md text-label-md text-primary mb-2" for="komentar">
                            Ceritakan Pengalaman Anda <span class="text-error">*</span>
                        </label>
                        <textarea id="komentar" name="komentar" rows="5"
                                  placeholder="Ceritakan kondisi mobil, pelayanan, ketepatan waktu, atau saran..."
                                  oninput="hitungKata(this)"
                                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest
                                         py-3 px-4 font-body-md text-body-md text-on-surface outline-none
                                         focus:border-primary focus:ring-1 focus:ring-primary transition-colors
                                         resize-none mb-2"><?= bersihkan($_POST['komentar'] ?? '') ?></textarea>
                        <div class="flex justify-between items-center">
                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                Minimal 5 kata.
                            </p>
                            <span id="kata-counter" class="font-label-sm text-label-sm text-on-surface-variant">
                                0 kata
                            </span>
                        </div>
                    </div>

                    <!-- Upload Foto -->
                    <div class="mb-8">
                        <label class="block font-label-md text-label-md text-primary mb-2">
                            Unggah Foto
                            <span class="font-body-md text-body-md text-on-surface-variant font-normal">
                                (Opsional, maks. 3 foto)
                            </span>
                        </label>
                        <div class="flex gap-4 flex-wrap">
                            <?php for ($i=1;$i<=3;$i++): ?>
                                <label class="w-24 h-24 flex-shrink-0 flex flex-col items-center
                                              justify-center border-2 border-dashed border-outline-variant
                                              rounded-xl bg-surface-container-low text-on-surface-variant
                                              cursor-pointer hover:bg-surface-container hover:border-primary
                                              transition-colors relative overflow-hidden group">
                                    <span class="material-symbols-outlined text-2xl mb-1">add_photo_alternate</span>
                                    <span class="font-label-sm text-label-sm">Foto <?= $i ?></span>
                                    <input type="file" name="foto_ulasan_<?= $i ?>"
                                           accept=".jpg,.jpeg,.png" class="hidden"
                                           onchange="previewFotoUlasan(this, <?= $i ?>)">
                                    <img id="preview-ulasan-<?= $i ?>"
                                         class="absolute inset-0 w-full h-full object-cover hidden">
                                </label>
                            <?php endfor; ?>
                        </div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant mt-2">
                            Format: JPG/PNG · Maks. 2MB per foto
                        </p>
                    </div>

                    <!-- Privacy -->
                    <div class="mb-8 flex items-start gap-3">
                        <input type="checkbox" id="tampil_nama" name="tampil_nama"
                               <?= isset($_POST['tampil_nama']) || !isset($_POST['kirim_ulasan']) ? 'checked' : '' ?>
                               class="mt-1 w-5 h-5 rounded border-outline-variant text-primary
                                      focus:ring-primary focus:ring-offset-0 bg-surface-container-lowest cursor-pointer">
                        <label for="tampil_nama" class="font-body-md text-body-md text-on-surface cursor-pointer">
                            Tampilkan nama saya di ulasan ini
                            <span class="block font-label-sm text-label-sm text-on-surface-variant mt-1">
                                (jika tidak dicentang, tampil sebagai 'Anonim')
                            </span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-4 mt-8">
                        <a href="<?= BASE_URL ?>/user/ulasan_saya.php"
                           class="py-3 px-6 rounded-lg border border-primary text-primary
                                  font-label-md text-label-md text-center hover:bg-surface-container
                                  transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="py-3 px-6 rounded-lg bg-primary text-on-primary
                                       font-label-md text-label-md font-bold text-center
                                       hover:bg-primary/90 transition-colors
                                       flex items-center justify-center gap-2 active:scale-[0.98]">
                            KIRIM ULASAN
                            <span class="material-symbols-outlined text-xl">arrow_forward</span>
                        </button>
                    </div>

                    <div class="mt-6 text-center">
                        <p class="font-label-sm text-label-sm text-on-surface-variant
                                  bg-surface-container-low py-2 px-4 rounded-lg inline-block">
                            ⚠️ Ulasan akan ditinjau admin sebelum ditampilkan
                        </p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>
</div>

<script>
    // Set rating bintang
    const ratingLabel = ['','Buruk','Kurang','Cukup','Baik','Sangat Puas!'];
    function setRating(nilai) {
        document.getElementById('rating-value').value = nilai;
        document.getElementById('rating-label').textContent = ratingLabel[nilai] || '';
        for (let i = 1; i <= 5; i++) {
            const star = document.getElementById('star-' + i);
            star.style.fontVariationSettings = i <= nilai ? "'FILL' 1" : "'FILL' 0";
            star.classList.toggle('text-secondary-container', i <= nilai);
            star.classList.toggle('text-outline-variant', i > nilai);
        }
    }
    // Init rating jika ada nilai dari POST
    const initRating = parseInt(document.getElementById('rating-value')?.value || '0');
    if (initRating) setRating(initRating);

    // Hitung kata
    function hitungKata(textarea) {
        const kata = textarea.value.trim().split(/\s+/).filter(w => w.length > 0);
        document.getElementById('kata-counter').textContent = kata.length + ' kata';
    }

    // Preview foto ulasan
    function previewFotoUlasan(input, idx) {
        const file = input.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { alert('Maks 2MB'); input.value=''; return; }
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview-ulasan-' + idx);
            img.src = e.target.result;
            img.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
