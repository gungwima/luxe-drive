<?php
// ============================================================
// detail_mobil.php — Detail Satu Mobil + Ulasan
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/daftar_armada.php'); exit; }

// ── Ambil data mobil ──
try {
    $stmt = $pdo->prepare("
        SELECT m.*,
               COALESCE(AVG(u.rating), 0) AS avg_rating,
               COUNT(DISTINCT u.id)       AS jml_ulasan
        FROM   mobil m
        LEFT JOIN ulasan u ON u.mobil_id = m.id AND u.status = 'approved'
        WHERE  m.id = ? AND m.status != 'nonaktif'
        GROUP  BY m.id
    ");
    $stmt->execute([$id]);
    $mobil = $stmt->fetch();
} catch (PDOException $e) { $mobil = null; }

if (!$mobil) {
    header('Location: ' . BASE_URL . '/daftar_armada.php');
    exit;
}

// ── Foto mobil ──
try {
    $stmt_foto = $pdo->prepare("SELECT * FROM foto_mobil WHERE mobil_id = ? ORDER BY is_utama DESC, urutan ASC");
    $stmt_foto->execute([$id]);
    $fotos = $stmt_foto->fetchAll();
} catch (PDOException $e) { $fotos = []; }

// ── Ulasan (5 terbaru) ──
try {
    $stmt_ulasan = $pdo->prepare("
        SELECT u.*, 
               CASE WHEN u.tampil_nama = 1 THEN us.nama ELSE 'Anonim' END AS nama_tampil,
               us.foto_profil,
               ub.balasan, ub.created_at AS tgl_balas
        FROM   ulasan u
        JOIN   users us ON us.id = u.user_id
        LEFT JOIN ulasan_balasan ub ON ub.ulasan_id = u.id
        WHERE  u.mobil_id = ? AND u.status = 'approved'
        ORDER  BY u.created_at DESC
        LIMIT  5
    ");
    $stmt_ulasan->execute([$id]);
    $ulasan_list = $stmt_ulasan->fetchAll();
} catch (PDOException $e) { $ulasan_list = []; }

// ── Mobil serupa ──
try {
    $stmt_serupa = $pdo->prepare("
        SELECT m.*, COALESCE(AVG(u.rating),0) AS avg_rating
        FROM   mobil m
        LEFT JOIN ulasan u ON u.mobil_id = m.id AND u.status = 'approved'
        WHERE  m.jenis = ? AND m.id != ? AND m.status = 'tersedia'
        GROUP  BY m.id
        LIMIT  4
    ");
    $stmt_serupa->execute([$mobil['jenis'], $id]);
    $mobil_serupa = $stmt_serupa->fetchAll();
} catch (PDOException $e) { $mobil_serupa = []; }

// ── Parse fasilitas JSON ──
$fasilitas = [];
if ($mobil['fasilitas']) {
    $fasilitas = json_decode($mobil['fasilitas'], true) ?? [];
}

$page_title  = $mobil['nama'];
$page_active = 'armada';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow max-w-container-max mx-auto
             px-margin-mobile md:px-margin-desktop py-8 md:py-12">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-on-surface-variant mb-8">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-label-md text-label-md hover:text-primary transition-colors">Beranda</a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
        <a href="<?= BASE_URL ?>/daftar_armada.php"
           class="font-label-md text-label-md hover:text-primary transition-colors">Armada</a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
        <span class="font-label-md text-label-md text-primary">
            <?= htmlspecialchars($mobil['nama']) ?>
        </span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">

        <!-- ── KIRI: Foto + Info ── -->
        <div class="lg:col-span-7">

            <!-- Galeri Foto -->
            <div class="mb-6">
                <div class="w-full aspect-[16/9] rounded-2xl overflow-hidden
                            bg-surface-container mb-3 relative group">
                    <img id="foto-utama"
                         src="<?= url_foto_mobil($mobil['foto_utama']) ?>"
                         alt="<?= htmlspecialchars($mobil['nama']) ?>"
                         class="w-full h-full object-cover transition-opacity duration-300">
                    <!-- Badge status -->
                    <div class="absolute top-4 left-4">
                        <?= badge_status_mobil($mobil['status']) ?>
                    </div>
                </div>

                <?php if (count($fotos) > 1): ?>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        <?php foreach ($fotos as $i => $foto): ?>
                            <button onclick="gantiGambar('<?= url_gambar($foto['foto'], 'mobil') ?>')"
                                    class="w-20 h-16 rounded-lg overflow-hidden flex-shrink-0
                                           border-2 border-transparent hover:border-primary
                                           transition-all focus:border-primary">
                                <img src="<?= url_gambar($foto['foto'], 'mobil') ?>"
                                     alt="Foto <?= $i+1 ?>"
                                     class="w-full h-full object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Nama & Rating -->
            <div class="mb-6">
                <div class="flex items-start justify-between gap-4">
                    <h1 class="font-headline-md text-headline-md text-on-surface">
                        <?= htmlspecialchars($mobil['nama']) ?>
                    </h1>
                    <span class="font-label-sm text-label-sm text-on-surface-variant shrink-0">
                        <?= htmlspecialchars($mobil['tahun']) ?>
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-2">
                    <?= tampil_bintang((float)$mobil['avg_rating']) ?>
                    <span class="font-label-md text-label-md text-on-surface-variant">
                        <?= number_format($mobil['avg_rating'], 1) ?>
                        (<?= (int)$mobil['jml_ulasan'] ?> ulasan)
                    </span>
                </div>
            </div>

            <!-- Spesifikasi -->
            <div class="bg-surface-container-low rounded-2xl p-6 mb-6">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">
                    Spesifikasi
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php
                    $spek = [
                        ['person',          'Kapasitas',   $mobil['kapasitas'] . ' Kursi'],
                        ['settings',        'Transmisi',   $mobil['transmisi']],
                        ['local_gas_station','Bahan Bakar', $mobil['bahan_bakar']],
                        ['luggage',         'Bagasi',      $mobil['bagasi'] ?: '—'],
                        ['calendar_month',  'Tahun',       $mobil['tahun']],
                        ['pin',             'No. Plat',    $mobil['no_plat']],
                    ];
                    foreach ($spek as [$ikon, $label, $nilai]): ?>
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center
                                        justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-[18px]">
                                    <?= $ikon ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">
                                    <?= $label ?>
                                </p>
                                <p class="font-label-md text-label-md text-on-surface font-medium">
                                    <?= htmlspecialchars($nilai) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fasilitas -->
            <?php if (!empty($fasilitas)): ?>
                <div class="mb-6">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">
                        Fasilitas
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($fasilitas as $f): ?>
                            <span class="flex items-center gap-1 px-3 py-1.5 rounded-full
                                         bg-surface-container font-label-md text-label-md
                                         text-on-surface">
                                <span class="material-symbols-outlined text-primary text-sm"
                                      style="font-variation-settings:'FILL' 1">
                                    check_circle
                                </span>
                                <?= htmlspecialchars($f) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Deskripsi -->
            <?php if ($mobil['deskripsi']): ?>
                <div class="mb-6">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface mb-3">
                        Deskripsi
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                        <?= nl2br(htmlspecialchars($mobil['deskripsi'])) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- ══ ULASAN ══ -->
            <div class="mt-8 pt-8 border-t border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-6">
                    Ulasan Pelanggan
                    <span class="font-label-md text-label-md text-on-surface-variant font-normal ml-2">
                        (<?= (int)$mobil['jml_ulasan'] ?>)
                    </span>
                </h2>

                <?php if (empty($ulasan_list)): ?>
                    <p class="font-body-md text-body-md text-on-surface-variant text-center py-8">
                        Belum ada ulasan untuk kendaraan ini.
                    </p>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($ulasan_list as $ul): ?>
                            <div class="bg-surface-container-low rounded-xl p-5">
                                <!-- Header ulasan -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary
                                                    flex items-center justify-center
                                                    text-on-primary font-bold text-sm overflow-hidden">
                                            <?php if ($ul['foto_profil'] && $ul['tampil_nama'] == 1): ?>
                                                <img src="<?= url_gambar($ul['foto_profil'], 'profil') ?>"
                                                     class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?= inisial($ul['nama_tampil']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-label-md text-label-md text-on-surface font-medium">
                                                <?= htmlspecialchars($ul['nama_tampil']) ?>
                                            </p>
                                            <p class="font-label-sm text-label-sm text-on-surface-variant">
                                                <?= waktu_lalu($ul['created_at']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0">
                                        <?= tampil_bintang($ul['rating']) ?>
                                    </div>
                                </div>

                                <!-- Komentar -->
                                <?php if ($ul['judul']): ?>
                                    <p class="font-label-md text-label-md text-on-surface font-semibold mb-1">
                                        <?= htmlspecialchars($ul['judul']) ?>
                                    </p>
                                <?php endif; ?>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    <?= nl2br(htmlspecialchars($ul['komentar'])) ?>
                                </p>

                                <!-- Foto Ulasan -->
                                <?php
                                $foto_ul = array_filter([$ul['foto_1'],$ul['foto_2'],$ul['foto_3']]);
                                if ($foto_ul): ?>
                                    <div class="flex gap-2 mt-3">
                                        <?php foreach ($foto_ul as $fu): ?>
                                            <img src="<?= url_gambar($fu, 'ulasan') ?>"
                                                 alt="Foto ulasan"
                                                 class="w-20 h-16 rounded-lg object-cover
                                                        border border-outline-variant/30">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Balasan Admin -->
                                <?php if ($ul['balasan']): ?>
                                    <div class="mt-4 ml-4 pl-4 border-l-2 border-secondary-container
                                                bg-surface rounded-lg p-3">
                                        <p class="font-label-sm text-label-sm text-secondary font-bold mb-1">
                                            Balasan Tim <?= APP_NAME ?>
                                        </p>
                                        <p class="font-body-md text-body-md text-on-surface-variant text-sm">
                                            <?= nl2br(htmlspecialchars($ul['balasan'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ((int)$mobil['jml_ulasan'] > 5): ?>
                        <div class="text-center mt-6">
                            <a href="<?= BASE_URL ?>/testimonial_ulasan.php?mobil_id=<?= $id ?>"
                               class="inline-flex items-center gap-2 border border-outline-variant
                                      text-on-surface px-6 py-2 rounded-full font-label-md text-label-md
                                      hover:bg-surface-container transition-colors">
                                Lihat Semua <?= (int)$mobil['jml_ulasan'] ?> Ulasan
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── KANAN: Sticky Booking Form ── -->
        <div class="lg:col-span-5">
            <div class="sticky top-20">
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30
                            shadow-[0px_8px_32px_rgba(26,43,60,0.12)] p-6">

                    <!-- Harga -->
                    <div class="mb-6">
                        <p class="font-label-sm text-label-sm text-on-surface-variant mb-1">
                            Harga sewa mulai dari
                        </p>
                        <div class="flex items-baseline gap-2">
                            <span class="font-display-lg-mobile text-display-lg-mobile text-primary font-bold">
                                <?= format_rupiah($mobil['harga_hari']) ?>
                            </span>
                            <span class="font-label-md text-label-md text-on-surface-variant">/hari</span>
                        </div>
                        <?php if ($mobil['harga_sopir'] > 0): ?>
                            <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                                + <?= format_rupiah($mobil['harga_sopir']) ?>/hari (dengan sopir)
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Form Booking -->
                    <form action="<?= BASE_URL ?>/booking/checkout.php" method="GET"
                          class="space-y-4">
                        <input type="hidden" name="mobil_id" value="<?= $id ?>">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="font-label-sm text-label-sm text-on-surface-variant mb-1 block">
                                    Tanggal Ambil
                                </label>
                                <input type="date" name="tgl_ambil" id="tgl_ambil"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= date('Y-m-d') ?>"
                                       required
                                       class="w-full border border-outline-variant rounded-xl
                                              py-2.5 px-3 font-label-md text-label-md text-on-surface
                                              bg-surface focus:outline-none focus:ring-2
                                              focus:ring-primary focus:border-primary transition-all">
                            </div>
                            <div>
                                <label class="font-label-sm text-label-sm text-on-surface-variant mb-1 block">
                                    Tanggal Kembali
                                </label>
                                <input type="date" name="tgl_kembali" id="tgl_kembali"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       required
                                       class="w-full border border-outline-variant rounded-xl
                                              py-2.5 px-3 font-label-md text-label-md text-on-surface
                                              bg-surface focus:outline-none focus:ring-2
                                              focus:ring-primary focus:border-primary transition-all">
                            </div>
                        </div>

                        <!-- Dengan Sopir -->
                        <?php if ($mobil['harga_sopir'] > 0): ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border
                                          border-outline-variant bg-surface cursor-pointer
                                          hover:border-primary transition-colors group">
                                <input type="checkbox" name="dengan_sopir" value="1"
                                       id="dengan_sopir"
                                       class="w-5 h-5 rounded border-outline-variant text-primary
                                              focus:ring-primary">
                                <div>
                                    <p class="font-label-md text-label-md text-on-surface">
                                        Dengan Sopir
                                    </p>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant">
                                        +<?= format_rupiah($mobil['harga_sopir']) ?>/hari
                                    </p>
                                </div>
                            </label>
                        <?php endif; ?>

                        <!-- Estimasi Harga -->
                        <div id="estimasi-harga"
                             class="bg-surface-container-low rounded-xl p-4 space-y-2 hidden">
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Sewa (<span id="disp-durasi">0</span> hari)</span>
                                <span id="disp-subtotal" class="text-on-surface">Rp 0</span>
                            </div>
                            <div class="flex justify-between font-label-md text-label-md" id="row-sopir" style="display:none">
                                <span class="text-on-surface-variant">Biaya Sopir</span>
                                <span id="disp-sopir" class="text-on-surface">Rp 0</span>
                            </div>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Asuransi</span>
                                <span class="text-on-surface"><?= format_rupiah(BIAYA_ASURANSI) ?></span>
                            </div>
                            <div class="flex justify-between font-label-md text-label-md">
                                <span class="text-on-surface-variant">Biaya Admin</span>
                                <span class="text-on-surface"><?= format_rupiah(BIAYA_ADMIN) ?></span>
                            </div>
                            <div class="flex justify-between font-headline-sm text-headline-sm
                                        pt-2 border-t border-outline-variant/30">
                                <span class="text-on-surface">Total</span>
                                <span id="disp-total" class="text-primary font-bold">Rp 0</span>
                            </div>
                        </div>

                        <?php if ($mobil['status'] === 'tersedia'): ?>
                            <?php if (is_user_login()): ?>
                                <button type="submit"
                                        class="w-full bg-primary text-on-primary py-4 rounded-xl
                                               font-label-md text-label-md font-bold
                                               hover:bg-primary/90 transition-all shadow-md
                                               active:scale-[0.98] flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined">directions_car</span>
                                    Pesan Sekarang
                                </button>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/auth/masuk.php?redirect=<?= urlencode(BASE_URL . '/detail_mobil.php?id=' . $id) ?>"
                                   class="w-full bg-primary text-on-primary py-4 rounded-xl
                                          font-label-md text-label-md font-bold
                                          hover:bg-primary/90 transition-all shadow-md text-center block">
                                    Masuk untuk Pesan
                                </a>
                                <p class="text-center font-label-sm text-label-sm text-on-surface-variant">
                                    Belum punya akun?
                                    <a href="<?= BASE_URL ?>/auth/daftar.php"
                                       class="text-primary hover:underline">Daftar gratis</a>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="w-full bg-surface-container text-on-surface-variant py-4
                                        rounded-xl font-label-md text-label-md text-center">
                                Tidak Tersedia Saat Ini
                            </div>
                        <?php endif; ?>

                        <!-- WhatsApp -->
                        <a href="https://wa.me/<?= APP_WHATSAPP ?>?text=Halo,+saya+ingin+tanya+tentang+<?= urlencode($mobil['nama']) ?>"
                           target="_blank"
                           class="w-full flex items-center justify-center gap-2 border border-outline-variant
                                  text-on-surface py-3 rounded-xl font-label-md text-label-md
                                  hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[20px] text-secondary-container">chat</span>
                            Tanya via WhatsApp
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobil Serupa -->
    <?php if (!empty($mobil_serupa)): ?>
        <div class="mt-16 pt-8 border-t border-outline-variant/30">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-8">
                Mobil Serupa
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($mobil_serupa as $s): ?>
                    <a href="<?= BASE_URL ?>/detail_mobil.php?id=<?= $s['id'] ?>"
                       class="bg-surface-container-lowest rounded-2xl overflow-hidden
                              border border-outline-variant/30
                              hover:shadow-md hover:-translate-y-1 transition-all group block">
                        <div class="w-full aspect-[4/3] overflow-hidden bg-surface-container">
                            <img src="<?= url_foto_mobil($s['foto_utama']) ?>"
                                 alt="<?= htmlspecialchars($s['nama']) ?>"
                                 class="w-full h-full object-cover
                                        group-hover:scale-105 transition-transform duration-500">
                        </div>
                        <div class="p-4">
                            <h3 class="font-label-md text-label-md text-on-surface font-semibold mb-1
                                       group-hover:text-primary transition-colors">
                                <?= htmlspecialchars($s['nama']) ?>
                            </h3>
                            <p class="font-headline-sm text-headline-sm text-primary text-sm">
                                <?= format_rupiah($s['harga_hari']) ?>/hari
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<script>
    // Ganti foto utama
    function gantiGambar(src) {
        document.getElementById('foto-utama').src = src;
    }

    // Hitung estimasi harga real-time
    const hargaHari  = <?= $mobil['harga_hari'] ?>;
    const hargaSopir = <?= $mobil['harga_sopir'] ?>;
    const asuransi   = <?= BIAYA_ASURANSI ?>;
    const adminFee   = <?= BIAYA_ADMIN ?>;

    function hitungEstimasi() {
        const ambil   = new Date(document.getElementById('tgl_ambil').value);
        const kembali = new Date(document.getElementById('tgl_kembali').value);
        if (isNaN(ambil) || isNaN(kembali) || kembali <= ambil) return;

        const durasi      = Math.ceil((kembali - ambil) / (1000 * 60 * 60 * 24));
        const dgSopir     = document.getElementById('dengan_sopir')?.checked;
        const subtotal    = hargaHari * durasi;
        const totalSopir  = dgSopir ? hargaSopir * durasi : 0;
        const total       = subtotal + totalSopir + asuransi + adminFee;

        const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
        document.getElementById('disp-durasi').textContent   = durasi;
        document.getElementById('disp-subtotal').textContent = fmt(subtotal);
        document.getElementById('disp-total').textContent    = fmt(total);

        const rowSopir = document.getElementById('row-sopir');
        if (totalSopir > 0 && rowSopir) {
            rowSopir.style.display = 'flex';
            document.getElementById('disp-sopir').textContent = fmt(totalSopir);
        } else if (rowSopir) {
            rowSopir.style.display = 'none';
        }

        document.getElementById('estimasi-harga').classList.remove('hidden');
    }

    document.getElementById('tgl_ambil')?.addEventListener('change', function () {
        const kembali = document.getElementById('tgl_kembali');
        if (kembali.value <= this.value) {
            const next = new Date(this.value);
            next.setDate(next.getDate() + 1);
            kembali.value = next.toISOString().split('T')[0];
        }
        hitungEstimasi();
    });
    document.getElementById('tgl_kembali')?.addEventListener('change', hitungEstimasi);
    document.getElementById('dengan_sopir')?.addEventListener('change', hitungEstimasi);
    hitungEstimasi();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
