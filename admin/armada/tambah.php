<?php
// ============================================================
// admin/armada/tambah.php & edit.php (dipakai bersama via ?id=)
// ============================================================
$id_mobil = (int)($_GET['id'] ?? 0);
$mode     = $id_mobil ? 'edit' : 'tambah';

$page_title_admin = $mode === 'edit' ? 'Edit Mobil' : 'Tambah Mobil';
$menu_aktif       = 'armada';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/auth.php';
require_once __DIR__ . '/../../functions/helpers.php';

require_admin_login();

$mobil  = [];
$errors = [];

// Ambil data mobil jika mode edit
if ($mode === 'edit') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM mobil WHERE id=?");
        $stmt->execute([$id_mobil]);
        $mobil = $stmt->fetch();
        if (!$mobil) { header('Location: ' . ADMIN_URL . '/armada/index.php'); exit; }
    } catch (PDOException $e) { header('Location: ' . ADMIN_URL . '/armada/index.php'); exit; }
}

// Ambil foto mobil jika edit
$fotos = [];
if ($mode === 'edit') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM foto_mobil WHERE mobil_id=? ORDER BY is_utama DESC, urutan ASC");
        $stmt->execute([$id_mobil]);
        $fotos = $stmt->fetchAll();
    } catch (PDOException $e) { $fotos = []; }
}

// Ambil fasilitas
$fasilitas_list = ['AC','Audio/Musik','GPS','Sabuk Pengaman','Kotak P3K','Kamera Mundur','Charger USB','Kursi Bayi'];
$fasilitas_aktif = $mobil['fasilitas'] ? (json_decode($mobil['fasilitas'], true) ?? []) : [];

// ── PROSES SIMPAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['global'] = 'Sesi tidak valid.';
    } else {
        $nama        = bersihkan($_POST['nama']        ?? '');
        $merek       = bersihkan($_POST['merek']       ?? '');
        $model_mobil = bersihkan($_POST['model_mobil'] ?? '');
        $tahun       = (int)    ($_POST['tahun']       ?? 0);
        $no_plat     = bersihkan($_POST['no_plat']     ?? '');
        $warna       = bersihkan($_POST['warna']       ?? '');
        $jenis       = bersihkan($_POST['jenis']       ?? '');
        $transmisi   = bersihkan($_POST['transmisi']   ?? '');
        $bbm         = bersihkan($_POST['bahan_bakar'] ?? '');
        $kapasitas   = (int)    ($_POST['kapasitas']   ?? 0);
        $bagasi      = bersihkan($_POST['bagasi']       ?? '');
        $harga_hari  = (int)    ($_POST['harga_hari']  ?? 0);
        $harga_sopir = (int)    ($_POST['harga_sopir'] ?? 0);
        $status_m    = bersihkan($_POST['status_mobil']?? 'tersedia');
        $lokasi      = bersihkan($_POST['lokasi']      ?? '');
        $deskripsi   = bersihkan($_POST['deskripsi']   ?? '');
        $fasilitas_p = $_POST['fasilitas'] ?? [];

        if (!$nama)    $errors['nama']    = 'Nama wajib diisi.';
        if (!$no_plat) $errors['no_plat'] = 'No. plat wajib diisi.';
        if (!$harga_hari) $errors['harga_hari'] = 'Harga wajib diisi.';

        // Cek duplikat plat (kecuali diri sendiri)
        if (!isset($errors['no_plat'])) {
            $cek = $pdo->prepare("SELECT id FROM mobil WHERE no_plat=? AND id!=?");
            $cek->execute([$no_plat, $id_mobil]);
            if ($cek->fetch()) $errors['no_plat'] = 'No. plat sudah digunakan.';
        }

        if (empty($errors)) {
            try {
                $fasilitas_json = json_encode(array_values($fasilitas_p));

                if ($mode === 'tambah') {
                    $pdo->prepare("
                        INSERT INTO mobil
                        (nama,merek,model,tahun,no_plat,warna,jenis,transmisi,
                         bahan_bakar,kapasitas,bagasi,harga_hari,harga_sopir,
                         status,lokasi,deskripsi,fasilitas)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ")->execute([$nama,$merek,$model_mobil,$tahun,$no_plat,$warna,
                                 $jenis,$transmisi,$bbm,$kapasitas,$bagasi,
                                 $harga_hari,$harga_sopir,$status_m,$lokasi,$deskripsi,$fasilitas_json]);
                    $new_id = (int)$pdo->lastInsertId();
                } else {
                    $pdo->prepare("
                        UPDATE mobil SET
                        nama=?,merek=?,model=?,tahun=?,no_plat=?,warna=?,jenis=?,
                        transmisi=?,bahan_bakar=?,kapasitas=?,bagasi=?,harga_hari=?,
                        harga_sopir=?,status=?,lokasi=?,deskripsi=?,fasilitas=?
                        WHERE id=?
                    ")->execute([$nama,$merek,$model_mobil,$tahun,$no_plat,$warna,
                                 $jenis,$transmisi,$bbm,$kapasitas,$bagasi,
                                 $harga_hari,$harga_sopir,$status_m,$lokasi,$deskripsi,
                                 $fasilitas_json,$id_mobil]);
                    $new_id = $id_mobil;
                }

                // Upload foto baru (maks 5)
                $ada_foto_baru   = false;
                $foto_pertama    = null;
                // Cek apakah mobil sudah punya foto utama
                $cek_utama = $pdo->prepare("SELECT foto_utama FROM mobil WHERE id=?");
                $cek_utama->execute([$new_id]);
                $foto_utama_lama = $cek_utama->fetchColumn();

                for ($fi = 1; $fi <= 5; $fi++) {
                    $key = "foto_mobil_{$fi}";
                    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                        $fn = simpan_gambar_db($_FILES[$key], 'mobil');
                        if ($fn) {
                            // Foto pertama yang berhasil diupload dicatat
                            if ($foto_pertama === null) $foto_pertama = $fn;

                            // Tentukan apakah ini jadi foto utama
                            $jadikan_utama = 0;
                            if (empty($foto_utama_lama) && $foto_pertama === $fn) {
                                $jadikan_utama = 1;
                            }
                            $pdo->prepare("INSERT INTO foto_mobil (mobil_id,foto,is_utama,urutan) VALUES (?,?,?,?)")
                                ->execute([$new_id, $fn, $jadikan_utama, $fi]);
                            $ada_foto_baru = true;
                        }
                    }
                }

                // Set foto_utama di tabel mobil jika belum ada & ada foto baru
                if (empty($foto_utama_lama) && $foto_pertama !== null) {
                    $pdo->prepare("UPDATE mobil SET foto_utama=? WHERE id=?")->execute([$foto_pertama, $new_id]);
                }

                set_flash('sukses', $mode === 'tambah' ? 'Mobil berhasil ditambahkan!' : 'Mobil berhasil diperbarui!');
                header('Location: ' . ADMIN_URL . '/armada/index.php');
                exit;
            } catch (PDOException $e) {
                $errors['global'] = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}

// Helper nilai field (POST > DB > default)
function val(string $key, array $mobil, string $default = ''): string {
    return htmlspecialchars($_POST[$key] ?? $mobil[$key] ?? $default);
}

require_once __DIR__ . '/../../includes/navbar_admin.php';
require_once __DIR__ . '/../../includes/sidebar_admin.php';
?>

<div class="flex-1 ml-64 mt-16 bg-background min-h-screen">
<main class="p-8 max-w-[1200px] mx-auto">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 mb-6 text-on-surface-variant font-label-md text-label-md">
        <a href="<?= ADMIN_URL ?>/armada/index.php"
           class="hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            Kembali ke Armada
        </a>
    </div>

    <h1 class="font-display-lg-mobile text-display-lg-mobile text-primary mb-8">
        <?= $mode === 'edit' ? 'Edit Mobil' : 'Tambah Mobil Baru' ?>
    </h1>

    <?php if (isset($errors['global'])): ?>
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-error-container text-on-error-container">
            <span class="material-symbols-outlined">error</span>
            <p class="font-label-md text-label-md"><?= bersihkan($errors['global']) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Foto Mobil -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">photo_camera</span>
                Foto Kendaraan
            </h2>

            <!-- Foto yang sudah ada (mode edit) -->
            <?php if (!empty($fotos)): ?>
                <div class="flex gap-3 mb-4 flex-wrap">
                    <?php foreach ($fotos as $f): ?>
                        <div class="relative">
                            <img src="<?= url_gambar($f['foto'], 'mobil') ?>"
                                 class="w-24 h-20 rounded-lg object-cover border-2
                                        <?= $f['is_utama'] ? 'border-primary' : 'border-outline-variant' ?>">
                            <?php if ($f['is_utama']): ?>
                                <span class="absolute top-1 left-1 bg-primary text-on-primary
                                             text-[10px] px-1 rounded font-bold">UTAMA</span>
                            <?php endif; ?>
                            <a href="?id=<?= $id_mobil ?>&hapus_foto=<?= $f['id'] ?>"
                               class="absolute top-1 right-1 bg-error text-on-error
                                      w-5 h-5 rounded-full flex items-center justify-center
                                      hover:opacity-90 transition-opacity"
                               onclick="return confirm('Hapus foto ini?')">
                                <span class="material-symbols-outlined text-[12px]">close</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <?php for ($fi = 1; $fi <= 5; $fi++): ?>
                    <label class="aspect-square bg-surface-container border-2 border-dashed
                                  border-outline-variant rounded-xl flex flex-col items-center
                                  justify-center cursor-pointer hover:bg-surface-container-high
                                  hover:border-primary transition-colors relative overflow-hidden group">
                        <span class="material-symbols-outlined text-outline text-3xl
                                     group-hover:text-primary transition-colors">
                            add_photo_alternate
                        </span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                            <?= $fi === 1 ? 'Foto Utama' : "Foto $fi" ?>
                        </span>
                        <input type="file" name="foto_mobil_<?= $fi ?>" accept=".jpg,.jpeg,.png,.webp"
                               class="hidden" onchange="previewFotoAdmin(this, <?= $fi ?>)">
                        <img id="preview-admin-<?= $fi ?>"
                             class="absolute inset-0 w-full h-full object-cover hidden rounded-xl">
                    </label>
                <?php endfor; ?>
            </div>
            <p class="font-label-sm text-label-sm text-on-surface-variant mt-2">
                Format: JPG/PNG/WebP · Maks 2MB per foto
            </p>
        </div>

        <!-- Informasi Dasar -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">info</span>
                    Informasi Dasar
                </h2>
                <div class="space-y-4">
                    <div class="col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                            Nama Mobil (Display) *
                        </label>
                        <input type="text" name="nama" value="<?= val('nama',$mobil) ?>"
                               placeholder="Contoh: Toyota Avanza 2023"
                               class="w-full px-4 py-3 border rounded-xl font-body-md text-body-md
                                      text-on-surface bg-surface-container-lowest focus:outline-none
                                      focus:border-primary focus:ring-1 focus:ring-primary transition-all
                                      <?= isset($errors['nama']) ? 'border-error' : 'border-outline-variant' ?>">
                        <?php if (isset($errors['nama'])): ?><p class="mt-1 text-error font-label-sm text-label-sm"><?= $errors['nama'] ?></p><?php endif; ?>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Merek</label>
                            <input type="text" name="merek" value="<?= val('merek',$mobil) ?>"
                                   placeholder="Toyota"
                                   class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                          font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                          focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Model</label>
                            <input type="text" name="model_mobil" value="<?= val('model',$mobil) ?>"
                                   placeholder="Avanza 1.3 G"
                                   class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                          font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                          focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Tahun</label>
                            <input type="number" name="tahun" value="<?= val('tahun',$mobil,date('Y')) ?>"
                                   min="2000" max="<?= date('Y')+1 ?>"
                                   class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                          font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                          focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Warna</label>
                            <input type="text" name="warna" value="<?= val('warna',$mobil) ?>"
                                   placeholder="Putih"
                                   class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                          font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                          focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">No. Plat *</label>
                        <input type="text" name="no_plat" value="<?= val('no_plat',$mobil) ?>"
                               placeholder="DK 1234 AB"
                               class="w-full px-4 py-3 border rounded-xl font-body-md text-body-md
                                      text-on-surface bg-surface-container-lowest focus:outline-none
                                      focus:border-primary focus:ring-1 focus:ring-primary transition-all
                                      <?= isset($errors['no_plat']) ? 'border-error' : 'border-outline-variant' ?>">
                        <?php if (isset($errors['no_plat'])): ?><p class="mt-1 text-error font-label-sm text-label-sm"><?= $errors['no_plat'] ?></p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Spesifikasi -->
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">settings</span>
                    Spesifikasi
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php
                    $selects = [
                        ['jenis',       'Jenis',        ['MPV','SUV','City Car','Minibus','Sedan','Pickup']],
                        ['transmisi',   'Transmisi',     ['Manual','Automatic']],
                        ['bahan_bakar', 'Bahan Bakar',   ['Bensin','Solar','Hybrid','Listrik']],
                    ];
                    foreach ($selects as [$n,$l,$opts]): $cur = val($n,$mobil); ?>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1"><?= $l ?></label>
                            <select name="<?= $n ?>"
                                    class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                           font-label-md text-label-md text-on-surface bg-surface-container-lowest
                                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <?php foreach ($opts as $o): ?>
                                    <option value="<?= $o ?>" <?= $cur===$o ? 'selected' : '' ?>><?= $o ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Kapasitas Kursi</label>
                        <input type="number" name="kapasitas" value="<?= val('kapasitas',$mobil,'5') ?>"
                               min="1" max="30"
                               class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                      font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                      focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Keterangan Bagasi</label>
                        <input type="text" name="bagasi" value="<?= val('bagasi',$mobil) ?>"
                               placeholder="Cth: 3 Koper Besar"
                               class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                      font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                      focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- Harga & Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">payments</span>
                    Harga & Ketersediaan
                </h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Harga Sewa/Hari *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-on-surface-variant font-body-md">Rp</span>
                                <input type="number" name="harga_hari" value="<?= val('harga_hari',$mobil,'0') ?>"
                                       placeholder="350000"
                                       class="w-full pl-12 pr-4 py-3 border border-outline-variant rounded-xl
                                              font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                              focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Harga + Sopir/Hari</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-on-surface-variant font-body-md">Rp</span>
                                <input type="number" name="harga_sopir" value="<?= val('harga_sopir',$mobil,'0') ?>"
                                       placeholder="150000"
                                       class="w-full pl-12 pr-4 py-3 border border-outline-variant rounded-xl
                                              font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                              focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-2">Status Saat Ini</label>
                        <div class="flex flex-wrap gap-4">
                            <?php foreach (['tersedia'=>'Tersedia','disewa'=>'Disewa','maintenance'=>'Maintenance','nonaktif'=>'Nonaktif'] as $v=>$l):
                                $cur_status = $_POST['status_mobil'] ?? ($mobil['status'] ?? 'tersedia');
                            ?>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="status_mobil" value="<?= $v ?>"
                                           <?= $cur_status===$v ? 'checked' : '' ?>
                                           class="text-primary focus:ring-primary">
                                    <span class="font-body-md text-body-md text-on-surface"><?= $l ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Lokasi Armada</label>
                        <input type="text" name="lokasi" value="<?= val('lokasi',$mobil) ?>"
                               placeholder="Kantor Pusat"
                               class="w-full px-4 py-3 border border-outline-variant rounded-xl
                                      font-body-md text-body-md text-on-surface bg-surface-container-lowest
                                      focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>
            </div>

            <!-- Fasilitas -->
            <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
                <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">star</span>
                    Fasilitas
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($fasilitas_list as $f): ?>
                        <label class="flex items-center gap-3 p-3 border border-outline-variant
                                      rounded-xl hover:bg-surface-container cursor-pointer transition-colors">
                            <input type="checkbox" name="fasilitas[]" value="<?= $f ?>"
                                   <?= in_array($f, $fasilitas_aktif) || in_array($f, $_POST['fasilitas'] ?? []) ? 'checked' : '' ?>
                                   class="text-primary rounded focus:ring-primary h-5 w-5">
                            <span class="font-label-md text-label-md text-on-surface"><?= $f ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Deskripsi -->
        <div class="bg-surface rounded-xl p-6 shadow-sm border border-outline-variant/30">
            <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">description</span>
                Deskripsi
            </h2>
            <textarea name="deskripsi" rows="4"
                      placeholder="Deskripsi lengkap kendaraan..."
                      class="w-full px-4 py-3 border border-outline-variant rounded-xl font-body-md text-body-md
                             text-on-surface bg-surface-container-lowest focus:outline-none
                             focus:border-primary focus:ring-1 focus:ring-primary transition-all resize-none"><?= val('deskripsi',$mobil) ?></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center pb-8">
            <?php if ($mode === 'edit'): ?>
                <a href="<?= ADMIN_URL ?>/armada/hapus.php?id=<?= $id_mobil ?>"
                   class="flex items-center gap-2 px-5 py-3 border border-error text-error
                          rounded-xl font-label-md text-label-md hover:bg-error-container
                          transition-colors">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                    Hapus Mobil
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <div class="flex gap-3">
                <a href="<?= ADMIN_URL ?>/armada/index.php"
                   class="px-6 py-3 border border-outline-variant text-on-surface rounded-xl
                          font-label-md text-label-md hover:bg-surface-container transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="flex items-center gap-2 px-8 py-3 bg-primary text-on-primary
                               rounded-xl font-label-md text-label-md font-bold
                               hover:bg-primary/90 transition-colors shadow-md">
                    <span class="material-symbols-outlined text-[20px]">save</span>
                    <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Mobil' ?>
                </button>
            </div>
        </div>
    </form>
</main>
</div>

<script>
    function previewFotoAdmin(input, idx) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview-admin-' + idx);
            img.src = e.target.result;
            img.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>
<?php require_once __DIR__ . '/../../includes/footer_admin.php'; ?>
