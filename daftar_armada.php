<?php
// ============================================================
// daftar_armada.php — Katalog Semua Mobil + Filter
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/helpers.php';

$page_title  = 'Katalog Armada';
$page_active = 'armada';

// ── Parameter Filter dari URL ──
$jenis      = bersihkan($_GET['jenis']      ?? '');
$transmisi  = bersihkan($_GET['transmisi']  ?? '');
$kapasitas  = bersihkan($_GET['kapasitas']  ?? '');
$harga_min  = (int)    ($_GET['harga_min']  ?? 0);
$harga_maks = (int)    ($_GET['harga_maks'] ?? 9999999);
$urut       = bersihkan($_GET['urut']       ?? 'populer');
$tgl_ambil  = bersihkan($_GET['tgl_ambil']  ?? '');
$tgl_kembali= bersihkan($_GET['tgl_kembali']?? '');
$halaman    = max(1, (int)($_GET['halaman'] ?? 1));
$per_hal    = 12;

// ── Build Query ──
$where  = ["m.status != 'nonaktif'"];
$params = [];

if ($jenis)     { $where[] = "m.jenis = ?";     $params[] = $jenis; }
if ($transmisi) { $where[] = "m.transmisi = ?";  $params[] = $transmisi; }
if ($kapasitas) {
    match($kapasitas) {
        '2-4'  => [$where[] = "m.kapasitas BETWEEN 2 AND 4"],
        '5-7'  => [$where[] = "m.kapasitas BETWEEN 5 AND 7"],
        '8+'   => [$where[] = "m.kapasitas >= 8"],
        default => null
    };
}
if ($harga_min  > 0)       { $where[] = "m.harga_hari >= ?"; $params[] = $harga_min; }
if ($harga_maks < 9999999) { $where[] = "m.harga_hari <= ?"; $params[] = $harga_maks; }

// Cek ketersediaan jika ada tanggal
if ($tgl_ambil && $tgl_kembali) {
    $where[] = "m.id NOT IN (
        SELECT DISTINCT mobil_id FROM booking
        WHERE status IN ('confirmed','berlangsung')
        AND NOT (tgl_kembali <= ? OR tgl_ambil >= ?)
    )";
    $params[] = $tgl_ambil;
    $params[] = $tgl_kembali;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Urutan
$order_sql = match($urut) {
    'harga_asc'  => 'ORDER BY m.harga_hari ASC',
    'harga_desc' => 'ORDER BY m.harga_hari DESC',
    'terbaru'    => 'ORDER BY m.created_at DESC',
    default      => 'ORDER BY avg_rating DESC, jml_sewa DESC',
};

try {
    // Hitung total
    $stmt_total = $pdo->prepare("
        SELECT COUNT(DISTINCT m.id) FROM mobil m $where_sql
    ");
    $stmt_total->execute($params);
    $total = (int) $stmt_total->fetchColumn();

    $paging = pagination($total, $per_hal, $halaman);

    // Ambil data
    $stmt = $pdo->prepare("
        SELECT m.*,
               COALESCE(AVG(u.rating), 0) AS avg_rating,
               COUNT(DISTINCT u.id)       AS jml_ulasan,
               COUNT(DISTINCT b.id)       AS jml_sewa
        FROM   mobil m
        LEFT JOIN ulasan  u ON u.mobil_id = m.id AND u.status = 'approved'
        LEFT JOIN booking b ON b.mobil_id = m.id AND b.status = 'selesai'
        $where_sql
        GROUP BY m.id
        $order_sql
        LIMIT {$per_hal} OFFSET {$paging['offset']}
    ");
    $stmt->execute($params);
    $daftar_mobil = $stmt->fetchAll();
} catch (PDOException $e) {
    $daftar_mobil = [];
    $total = 0;
    $paging = pagination(0, $per_hal, 1);
}

// URL untuk pagination (pertahankan filter)
$query_params = array_filter([
    'jenis'      => $jenis,
    'transmisi'  => $transmisi,
    'kapasitas'  => $kapasitas,
    'harga_min'  => $harga_min  ?: null,
    'harga_maks' => $harga_maks < 9999999 ? $harga_maks : null,
    'urut'       => $urut !== 'populer' ? $urut : null,
    'tgl_ambil'  => $tgl_ambil,
    'tgl_kembali'=> $tgl_kembali,
]);
$base_url_paging = BASE_URL . '/daftar_armada.php?' . http_build_query($query_params);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-grow w-full max-w-container-max mx-auto
             px-margin-mobile md:px-margin-desktop py-8 md:py-12 flex flex-col gap-8">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-on-surface-variant">
        <a href="<?= BASE_URL ?>/beranda.php"
           class="font-label-md text-label-md hover:text-primary transition-colors">
            Beranda
        </a>
        <span class="material-symbols-outlined text-sm">chevron_right</span>
        <span class="font-label-md text-label-md text-primary font-medium">Armada</span>
    </nav>

    <!-- Header & Urutkan -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center
                gap-4 border-b border-surface-container-highest pb-6">
        <div>
            <h1 class="font-display-lg-mobile text-display-lg-mobile text-on-background">
                Katalog Armada
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2">
                <?= $total ?> kendaraan ditemukan
            </p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <span class="font-label-md text-label-md text-on-surface-variant whitespace-nowrap">
                Urutkan:
            </span>
            <div class="relative w-full md:w-52">
                <select id="select-urut" onchange="terapkanUrut(this.value)"
                        class="w-full appearance-none bg-surface border border-outline-variant
                               rounded-lg py-2 pl-4 pr-10 font-label-md text-label-md
                               text-on-surface focus:outline-none focus:ring-2
                               focus:ring-primary focus:border-primary transition-all">
                    <option value="populer"    <?= $urut==='populer'    ? 'selected' : '' ?>>Paling Populer</option>
                    <option value="harga_asc"  <?= $urut==='harga_asc'  ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                    <option value="harga_desc" <?= $urut==='harga_desc' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                    <option value="terbaru"    <?= $urut==='terbaru'    ? 'selected' : '' ?>>Tahun Terbaru</option>
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2
                             -translate-y-1/2 text-on-surface-variant pointer-events-none text-sm">
                    expand_more
                </span>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">

        <!-- ── SIDEBAR FILTER ── -->
        <aside class="w-full lg:w-64 flex-shrink-0">
            <form method="GET" action="" id="form-filter"
                  class="bg-surface-container-lowest rounded-2xl border border-outline-variant/30
                         shadow-sm p-6 sticky top-20">
                <h3 class="font-headline-sm text-headline-sm text-on-surface mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">tune</span>
                    Filter
                </h3>

                <!-- Tanggal (jika dari beranda) -->
                <?php if ($tgl_ambil): ?>
                    <input type="hidden" name="tgl_ambil"   value="<?= htmlspecialchars($tgl_ambil) ?>">
                    <input type="hidden" name="tgl_kembali" value="<?= htmlspecialchars($tgl_kembali) ?>">
                <?php endif; ?>
                <input type="hidden" name="urut" value="<?= htmlspecialchars($urut) ?>">

                <!-- Jenis Mobil -->
                <div class="mb-6">
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase
                               tracking-wider mb-3">
                        Jenis Mobil
                    </p>
                    <div class="space-y-2">
                        <?php foreach (['MPV','SUV','City Car','Minibus','Sedan','Pickup'] as $j): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="jenis" value="<?= $j ?>"
                                       <?= $jenis === $j ? 'checked' : '' ?>
                                       onchange="document.getElementById('form-filter').submit()"
                                       class="w-4 h-4 rounded border-outline-variant text-primary
                                              focus:ring-primary">
                                <span class="font-label-md text-label-md text-on-surface
                                             group-hover:text-primary transition-colors">
                                    <?= $j ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Transmisi -->
                <div class="mb-6 pt-6 border-t border-outline-variant/30">
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase
                               tracking-wider mb-3">
                        Transmisi
                    </p>
                    <div class="space-y-2">
                        <?php foreach (['Manual','Automatic'] as $t): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="transmisi" value="<?= $t ?>"
                                       <?= $transmisi === $t ? 'checked' : '' ?>
                                       onchange="document.getElementById('form-filter').submit()"
                                       class="w-4 h-4 border-outline-variant text-primary
                                              focus:ring-primary">
                                <span class="font-label-md text-label-md text-on-surface
                                             group-hover:text-primary transition-colors">
                                    <?= $t ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($transmisi): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="transmisi" value=""
                                       onchange="document.getElementById('form-filter').submit()"
                                       class="w-4 h-4 border-outline-variant text-primary">
                                <span class="font-label-md text-label-md text-on-surface-variant">
                                    Semua
                                </span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kapasitas -->
                <div class="mb-6 pt-6 border-t border-outline-variant/30">
                    <p class="font-label-sm text-label-sm text-on-surface-variant uppercase
                               tracking-wider mb-3">
                        Kapasitas Penumpang
                    </p>
                    <div class="space-y-2">
                        <?php foreach (['2-4'=>'2–4 Orang','5-7'=>'5–7 Orang','8+'=>'8+ Orang'] as $v=>$l): ?>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="kapasitas" value="<?= $v ?>"
                                       <?= $kapasitas === $v ? 'checked' : '' ?>
                                       onchange="document.getElementById('form-filter').submit()"
                                       class="w-4 h-4 border-outline-variant text-primary
                                              focus:ring-primary">
                                <span class="font-label-md text-label-md text-on-surface
                                             group-hover:text-primary transition-colors">
                                    <?= $l ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reset Filter -->
                <?php if ($jenis || $transmisi || $kapasitas): ?>
                    <a href="<?= BASE_URL ?>/daftar_armada.php"
                       class="flex items-center justify-center gap-2 w-full py-2 rounded-lg
                              border border-outline-variant text-on-surface-variant
                              hover:bg-surface-container font-label-md text-label-md
                              transition-colors mt-2">
                        <span class="material-symbols-outlined text-sm">filter_alt_off</span>
                        Reset Filter
                    </a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- ── GRID MOBIL ── -->
        <div class="flex-1">
            <?php if (empty($daftar_mobil)): ?>
                <div class="text-center py-20">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4 block">
                        search_off
                    </span>
                    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-2">
                        Tidak Ada Mobil Ditemukan
                    </h3>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                        Coba ubah filter atau hapus beberapa kriteria pencarian.
                    </p>
                    <a href="<?= BASE_URL ?>/daftar_armada.php"
                       class="inline-flex items-center gap-2 bg-primary text-on-primary
                              px-6 py-3 rounded-full font-label-md text-label-md
                              hover:bg-primary/90 transition-colors">
                        Lihat Semua Armada
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($daftar_mobil as $mobil): ?>
                        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden
                                    border border-outline-variant/30
                                    shadow-[0px_4px_20px_rgba(26,43,60,0.08)]
                                    hover:shadow-[0px_8px_32px_rgba(26,43,60,0.16)]
                                    hover:-translate-y-1 transition-all group">

                            <!-- Foto -->
                            <a href="<?= BASE_URL ?>/detail_mobil.php?id=<?= $mobil['id'] ?>"
                               class="block relative w-full aspect-[4/3] overflow-hidden bg-surface-container">
                                <img src="<?= url_foto_mobil($mobil['foto_utama']) ?>"
                                     alt="<?= htmlspecialchars($mobil['nama']) ?>"
                                     class="w-full h-full object-cover
                                            group-hover:scale-105 transition-transform duration-500">
                                <div class="absolute top-3 left-3">
                                    <?= badge_status_mobil($mobil['status']) ?>
                                </div>
                                <?php if ((float)$mobil['avg_rating'] >= 4.5): ?>
                                    <div class="absolute top-3 right-3 bg-secondary-container
                                                text-on-secondary-container px-2 py-1 rounded-full
                                                font-label-sm text-label-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs"
                                              style="font-variation-settings:'FILL' 1">star</span>
                                        Popular
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Konten -->
                            <div class="p-5">
                                <a href="<?= BASE_URL ?>/detail_mobil.php?id=<?= $mobil['id'] ?>">
                                    <h3 class="font-headline-sm text-headline-sm text-on-surface
                                               group-hover:text-primary transition-colors mb-1">
                                        <?= htmlspecialchars($mobil['nama']) ?>
                                    </h3>
                                </a>

                                <!-- Rating -->
                                <div class="flex items-center gap-1 mb-3">
                                    <?= tampil_bintang((float)$mobil['avg_rating']) ?>
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">
                                        <?= number_format($mobil['avg_rating'], 1) ?>
                                        (<?= (int)$mobil['jml_ulasan'] ?> ulasan)
                                    </span>
                                </div>

                                <!-- Spesifikasi -->
                                <div class="flex items-center gap-3 mb-4 flex-wrap">
                                    <span class="flex items-center gap-1 font-label-sm text-label-sm
                                                 text-on-surface-variant bg-surface-container px-2 py-1 rounded-full">
                                        <span class="material-symbols-outlined text-sm">person</span>
                                        <?= $mobil['kapasitas'] ?> kursi
                                    </span>
                                    <span class="flex items-center gap-1 font-label-sm text-label-sm
                                                 text-on-surface-variant bg-surface-container px-2 py-1 rounded-full">
                                        <span class="material-symbols-outlined text-sm">settings</span>
                                        <?= $mobil['transmisi'] ?>
                                    </span>
                                    <span class="flex items-center gap-1 font-label-sm text-label-sm
                                                 text-on-surface-variant bg-surface-container px-2 py-1 rounded-full">
                                        <span class="material-symbols-outlined text-sm">local_gas_station</span>
                                        <?= $mobil['bahan_bakar'] ?>
                                    </span>
                                </div>

                                <!-- Harga & Tombol -->
                                <div class="flex items-center justify-between pt-4
                                            border-t border-outline-variant/30">
                                    <div>
                                        <p class="font-label-sm text-label-sm text-on-surface-variant">
                                            Mulai dari
                                        </p>
                                        <p class="font-headline-sm text-headline-sm text-primary font-bold">
                                            <?= format_rupiah($mobil['harga_hari']) ?>
                                            <span class="font-label-sm text-label-sm
                                                         text-on-surface-variant font-normal">
                                                /hari
                                            </span>
                                        </p>
                                    </div>
                                    <a href="<?= BASE_URL ?>/detail_mobil.php?id=<?= $mobil['id'] ?>"
                                       class="flex items-center gap-1 bg-primary text-on-primary
                                              px-4 py-2 rounded-full font-label-md text-label-md
                                              hover:bg-primary/90 transition-all text-sm">
                                        Detail
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?= render_pagination($paging, $base_url_paging) ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function terapkanUrut(nilai) {
        const url  = new URL(window.location.href);
        url.searchParams.set('urut', nilai);
        url.searchParams.set('halaman', '1');
        window.location.href = url.toString();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
