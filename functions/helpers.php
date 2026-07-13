<?php
// ============================================================
// functions/helpers.php
// Fungsi-fungsi pembantu yang dipakai di semua halaman
// ============================================================

require_once __DIR__ . '/../config/config.php';

// ------------------------------------------------------------
// FORMAT ANGKA & MATA UANG
// ------------------------------------------------------------

/**
 * Format angka jadi Rupiah
 * format_rupiah(350000) → "Rp 350.000"
 */
function format_rupiah(int|float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format angka besar (statistik dashboard)
 * format_angka_pendek(1248) → "1.2rb"
 */
function format_angka_pendek(int $angka): string {
    if ($angka >= 1000000) {
        return number_format($angka / 1000000, 1) . 'jt';
    } elseif ($angka >= 1000) {
        return number_format($angka / 1000, 1) . 'rb';
    }
    return (string) $angka;
}

// ------------------------------------------------------------
// FORMAT TANGGAL & WAKTU
// ------------------------------------------------------------

/**
 * Format tanggal Indonesia
 * format_tanggal("2026-06-21") → "21 Juni 2026"
 */
function format_tanggal(string $tanggal): string {
    $bulan = [
        1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
        4  => 'April',    5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',     8  => 'Agustus',   9  => 'September',
        10 => 'Oktober',  11 => 'November',  12 => 'Desember',
    ];
    $d = date_create($tanggal);
    if (!$d) return $tanggal;
    return date_format($d, 'j') . ' '
         . $bulan[(int) date_format($d, 'n')] . ' '
         . date_format($d, 'Y');
}

/**
 * Format tanggal + jam
 * format_datetime("2026-06-21 09:00:00") → "21 Juni 2026, 09:00"
 */
function format_datetime(string $datetime): string {
    $d = date_create($datetime);
    if (!$d) return $datetime;
    $bulan = [
        1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
        4  => 'April',    5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',     8  => 'Agustus',   9  => 'September',
        10 => 'Oktober',  11 => 'November',  12 => 'Desember',
    ];
    return date_format($d, 'j') . ' '
         . $bulan[(int) date_format($d, 'n')] . ' '
         . date_format($d, 'Y') . ', '
         . date_format($d, 'H:i');
}

/**
 * Hitung durasi sewa dalam hari
 * hitung_durasi("2026-06-21", "2026-06-24") → 3
 */
function hitung_durasi(string $tgl_ambil, string $tgl_kembali): int {
    $ambil   = date_create($tgl_ambil);
    $kembali = date_create($tgl_kembali);
    if (!$ambil || !$kembali) return 0;
    $diff = date_diff($ambil, $kembali);
    return max(1, $diff->days);
}

/**
 * Tampilkan selisih waktu dari sekarang
 * waktu_lalu("2026-06-20 10:00:00") → "2 hari lalu"
 */
function waktu_lalu(string $datetime): string {
    $sekarang = time();
    $waktu    = strtotime($datetime);
    $selisih  = $sekarang - $waktu;

    if ($selisih < 60)         return 'Baru saja';
    if ($selisih < 3600)       return (int)($selisih / 60)    . ' menit lalu';
    if ($selisih < 86400)      return (int)($selisih / 3600)  . ' jam lalu';
    if ($selisih < 2592000)    return (int)($selisih / 86400) . ' hari lalu';
    return format_tanggal($datetime);
}

// ------------------------------------------------------------
// BOOKING & KODE UNIK
// ------------------------------------------------------------

/**
 * Generate kode booking unik
 * buat_kode_booking() → "RNT-20260621-0842"
 */
function buat_kode_booking(): string {
    return 'RNT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Generate kode transaksi unik
 */
function buat_kode_transaksi(): string {
    return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Generate kode OTP 6 digit
 */
function buat_kode_otp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Hitung total biaya booking
 */
function hitung_total(
    float $harga_hari,
    int   $durasi,
    float $biaya_sopir   = 0,
    float $diskon        = 0
): array {
    $subtotal        = $harga_hari * $durasi;
    $total_sopir     = $biaya_sopir * $durasi;
    $biaya_asuransi  = BIAYA_ASURANSI;
    $biaya_admin     = BIAYA_ADMIN;
    $total           = $subtotal + $total_sopir + $biaya_asuransi + $biaya_admin - $diskon;

    return [
        'subtotal'       => $subtotal,
        'biaya_sopir'    => $total_sopir,
        'biaya_asuransi' => $biaya_asuransi,
        'biaya_admin'    => $biaya_admin,
        'diskon'         => $diskon,
        'total'          => max(0, $total),
        'dp_minimal'     => ceil($total * DP_PERSEN / 100),
    ];
}

// ------------------------------------------------------------
// RATING & BINTANG
// ------------------------------------------------------------

/**
 * Render bintang HTML dari rating
 * tampil_bintang(4.5) → HTML bintang penuh & setengah
 */
function tampil_bintang(float $rating): string {
    $html  = '';
    $penuh = floor($rating);
    $sisa  = $rating - $penuh;

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $penuh) {
            $html .= '<span class="material-symbols-outlined text-secondary-container text-base"
                           style="font-variation-settings:\'FILL\' 1">star</span>';
        } elseif ($i == $penuh + 1 && $sisa >= 0.5) {
            $html .= '<span class="material-symbols-outlined text-secondary-container text-base"
                           style="font-variation-settings:\'FILL\' 1">star_half</span>';
        } else {
            $html .= '<span class="material-symbols-outlined text-outline text-base">star</span>';
        }
    }
    return $html;
}

// ------------------------------------------------------------
// STATUS BADGE
// ------------------------------------------------------------

/**
 * Badge status booking dengan warna
 */
function badge_status_booking(string $status): string {
    $map = [
        'pending'      => ['bg-secondary-container/30 text-on-secondary-container', 'schedule',       'Menunggu'],
        'confirmed'    => ['bg-tertiary-fixed/50 text-on-tertiary-fixed',            'check_circle',   'Dikonfirmasi'],
        'berlangsung'  => ['bg-primary/10 text-primary',                             'directions_car', 'Berlangsung'],
        'selesai'      => ['bg-green-100 text-green-800',                            'task_alt',       'Selesai'],
        'dibatalkan'   => ['bg-error-container text-on-error-container',             'cancel',         'Dibatalkan'],
    ];
    $s = $map[$status] ?? ['bg-surface-container text-on-surface-variant', 'help', ucfirst($status)];
    return "<span class=\"inline-flex items-center gap-1 px-2 py-1 rounded-full text-label-sm font-label-sm {$s[0]}\">
                <span class=\"material-symbols-outlined text-sm\">{$s[1]}</span>
                {$s[2]}
            </span>";
}

/**
 * Badge status pembayaran
 */
function badge_status_transaksi(string $status): string {
    $map = [
        'pending'  => ['bg-secondary-container/30 text-on-secondary-container', 'Menunggu Verifikasi'],
        'verified' => ['bg-green-100 text-green-800',                            'Terverifikasi'],
        'rejected' => ['bg-error-container text-on-error-container',             'Ditolak'],
    ];
    $s = $map[$status] ?? ['bg-surface-container text-on-surface-variant', ucfirst($status)];
    return "<span class=\"inline-flex items-center px-2 py-1 rounded-full text-label-sm font-label-sm {$s[0]}\">
                {$s[1]}
            </span>";
}

/**
 * Badge status mobil
 */
function badge_status_mobil(string $status): string {
    $map = [
        'tersedia'    => ['bg-green-100 text-green-800',                      'Tersedia'],
        'disewa'      => ['bg-primary/10 text-primary',                       'Disewa'],
        'maintenance' => ['bg-secondary-container/30 text-on-secondary-container', 'Maintenance'],
        'nonaktif'    => ['bg-surface-container-high text-on-surface-variant', 'Nonaktif'],
    ];
    $s = $map[$status] ?? ['bg-surface-container text-on-surface-variant', ucfirst($status)];
    return "<span class=\"inline-flex items-center px-2 py-1 rounded-full text-label-sm font-label-sm {$s[0]}\">
                {$s[1]}
            </span>";
}

/**
 * Badge status ulasan
 */
function badge_status_ulasan(string $status): string {
    $map = [
        'pending'  => ['bg-secondary-container/30 text-on-secondary-container', 'Menunggu'],
        'approved' => ['bg-green-100 text-green-800',                            'Disetujui'],
        'rejected' => ['bg-error-container text-on-error-container',             'Ditolak'],
    ];
    $s = $map[$status] ?? ['bg-surface-container text-on-surface-variant', ucfirst($status)];
    return "<span class=\"inline-flex items-center px-2 py-1 rounded-full text-label-sm font-label-sm {$s[0]}\">
                {$s[1]}
            </span>";
}

// ------------------------------------------------------------
// UPLOAD FILE
// ------------------------------------------------------------

/**
 * Upload gambar ke folder tertentu
 * Return: nama file baru atau false jika gagal
 */
// ============================================================
// PENYIMPANAN GAMBAR DI DATABASE (anti masalah permission folder)
// ============================================================

/**
 * Simpan file gambar yang di-upload ke DATABASE (bukan folder).
 * Mengembalikan ID gambar (integer sebagai string) atau false jika gagal.
 */
function simpan_gambar_db(array $file, string $kategori = 'umum'): string|false {
    global $pdo;
    if (!isset($pdo)) return false;

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return false;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > MAX_FILE_SIZE) return false;

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGES)) return false;

    // Baca isi file (dukung upload asli & file biasa untuk testing)
    if (is_uploaded_file($file['tmp_name'])) {
        $data = @file_get_contents($file['tmp_name']);
    } else {
        $data = @file_get_contents($file['tmp_name']);
    }
    if ($data === false || $data === '') return false;

    $mime = match($ext) {
        'png'  => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $deteksi = @finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
            if ($deteksi && strpos($deteksi, 'image/') === 0) $mime = $deteksi;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO gambar (nama_file, tipe_mime, data_gambar, ukuran, kategori) VALUES (?,?,?,?,?)");
        $stmt->bindValue(1, substr($file['name'], 0, 255));
        $stmt->bindValue(2, $mime);
        $stmt->bindValue(3, $data, PDO::PARAM_LOB);
        $stmt->bindValue(4, (int)$file['size'], PDO::PARAM_INT);
        $stmt->bindValue(5, $kategori);
        $stmt->execute();
        return (string)$pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('simpan_gambar_db error: ' . $e->getMessage());
        return false;
    }
}

/**
 * URL untuk menampilkan gambar dari database.
 */
function url_gambar_db(?string $id_gambar, string $placeholder = 'car'): string {
    if ($id_gambar && is_numeric($id_gambar)) {
        return BASE_URL . '/tampil_gambar.php?id=' . (int)$id_gambar;
    }
    $file = $placeholder === 'avatar' ? 'placeholder-avatar.svg' : 'placeholder-car.svg';
    return ASSETS_URL . '/images/' . $file;
}

/**
 * Hapus gambar dari database.
 */
function hapus_gambar_db(?string $id_gambar): void {
    global $pdo;
    if ($id_gambar && is_numeric($id_gambar)) {
        try {
            $pdo->prepare("DELETE FROM gambar WHERE id=?")->execute([(int)$id_gambar]);
        } catch (PDOException $e) {}
    }
}

function upload_gambar(array $file, string $folder): string|false {
    // Cek error upload bawaan PHP
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return false;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > MAX_FILE_SIZE) return false;

    // Validasi ekstensi (case-insensitive)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg'; // normalisasi
    $ext_asli = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext_asli, ALLOWED_IMAGES)) return false;

    // Buat nama file unik
    $nama_file   = uniqid('img_') . '_' . time() . '.' . $ext_asli;
    $folder_path = UPLOADS_PATH . '/' . $folder;

    // Buat folder otomatis jika belum ada (0777 agar writable di berbagai server)
    if (!is_dir($folder_path)) {
        @mkdir($folder_path, 0777, true);
        @chmod($folder_path, 0777);
    }
    // Jika folder tetap tidak ada / tidak writable, gagal terkontrol
    if (!is_dir($folder_path) || !is_writable($folder_path)) return false;

    $path = $folder_path . '/' . $nama_file;

    // Coba move_uploaded_file (cara normal via HTTP).
    // Fallback ke copy() untuk kompatibilitas lingkungan tertentu.
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $nama_file;
    }
    if (@copy($file['tmp_name'], $path)) {
        return $nama_file;
    }
    return false;
}

/**
 * Hapus file dari uploads
 */
function hapus_file(string $folder, string $nama_file): void {
    $path = UPLOADS_PATH . '/' . $folder . '/' . $nama_file;
    if (file_exists($path)) unlink($path);
}

/**
 * URL gambar mobil — deteksi otomatis:
 * - Jika berupa angka → ID gambar di database (tampil_gambar.php)
 * - Jika berupa nama file → cara lama (folder)
 * - Jika kosong → placeholder
 */
function url_foto_mobil(?string $foto): string {
    if ($foto && is_numeric($foto)) {
        return BASE_URL . '/tampil_gambar.php?id=' . (int)$foto;
    }
    if ($foto && file_exists(UPLOADS_PATH . '/mobil/' . $foto)) {
        return UPLOADS_URL . '/mobil/' . $foto;
    }
    return ASSETS_URL . '/images/placeholder-car.svg';
}

/**
 * URL foto profil user — deteksi otomatis ID database vs nama file
 */
function url_foto_profil(?string $foto): string {
    if ($foto && is_numeric($foto)) {
        return BASE_URL . '/tampil_gambar.php?id=' . (int)$foto;
    }
    if ($foto && file_exists(UPLOADS_PATH . '/profil/' . $foto)) {
        return UPLOADS_URL . '/profil/' . $foto;
    }
    return ASSETS_URL . '/images/placeholder-avatar.svg';
}

/**
 * URL universal untuk gambar apapun (KTP, SIM, bukti bayar, ulasan) dari DB.
 * Menerima ID database. Jika bukan angka, dianggap nama file lama di $folder.
 */
function url_gambar(?string $nilai, string $folder = ''): string {
    if ($nilai && is_numeric($nilai)) {
        return BASE_URL . '/tampil_gambar.php?id=' . (int)$nilai;
    }
    if ($nilai && $folder) {
        return UPLOADS_URL . '/' . $folder . '/' . $nilai;
    }
    return ASSETS_URL . '/images/placeholder-car.svg';
}

// ------------------------------------------------------------
// KEAMANAN INPUT
// ------------------------------------------------------------

/**
 * Bersihkan input dari XSS
 */
function bersihkan(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Potong teks panjang
 * potong_teks("teks sangat panjang...", 50) → "teks sangat pan..."
 */
function potong_teks(string $teks, int $maks = 100): string {
    if (mb_strlen($teks) <= $maks) return $teks;
    return mb_substr($teks, 0, $maks) . '...';
}

/**
 * Inisial nama untuk avatar
 * inisial("Budi Santoso") → "BS"
 */
function inisial(string $nama): string {
    $kata = explode(' ', trim($nama));
    if (count($kata) >= 2) {
        return strtoupper($kata[0][0] . $kata[1][0]);
    }
    return strtoupper(substr($nama, 0, 2));
}

// ------------------------------------------------------------
// PAGINATION
// ------------------------------------------------------------

/**
 * Hitung data untuk pagination
 */
function pagination(int $total, int $per_halaman, int $halaman_aktif): array {
    $total_halaman = (int) ceil($total / $per_halaman);
    $offset        = ($halaman_aktif - 1) * $per_halaman;

    return [
        'total'          => $total,
        'per_halaman'    => $per_halaman,
        'halaman_aktif'  => $halaman_aktif,
        'total_halaman'  => $total_halaman,
        'offset'         => $offset,
        'ada_sebelumnya' => $halaman_aktif > 1,
        'ada_berikutnya' => $halaman_aktif < $total_halaman,
    ];
}

/**
 * Render HTML pagination
 */
function render_pagination(array $data, string $base_url): string {
    if ($data['total_halaman'] <= 1) return '';

    $html = '<div class="flex items-center justify-center gap-2 mt-8">';

    // Tombol sebelumnya
    if ($data['ada_sebelumnya']) {
        $prev = $data['halaman_aktif'] - 1;
        $html .= "<a href=\"{$base_url}?halaman={$prev}\"
                     class=\"p-2 rounded-lg border border-outline-variant text-on-surface-variant
                             hover:bg-surface-container transition-colors\">
                    <span class=\"material-symbols-outlined text-sm\">chevron_left</span>
                  </a>";
    }

    // Nomor halaman
    for ($i = 1; $i <= $data['total_halaman']; $i++) {
        if (
            $i === 1 ||
            $i === $data['total_halaman'] ||
            abs($i - $data['halaman_aktif']) <= 2
        ) {
            $aktif = $i === $data['halaman_aktif']
                ? 'bg-primary text-on-primary'
                : 'border border-outline-variant text-on-surface-variant hover:bg-surface-container';
            $html .= "<a href=\"{$base_url}?halaman={$i}\"
                         class=\"w-9 h-9 flex items-center justify-center rounded-lg
                                 font-label-md text-label-md transition-colors {$aktif}\">
                        {$i}
                      </a>";
        } elseif (abs($i - $data['halaman_aktif']) === 3) {
            $html .= '<span class="text-on-surface-variant px-1">...</span>';
        }
    }

    // Tombol berikutnya
    if ($data['ada_berikutnya']) {
        $next = $data['halaman_aktif'] + 1;
        $html .= "<a href=\"{$base_url}?halaman={$next}\"
                     class=\"p-2 rounded-lg border border-outline-variant text-on-surface-variant
                             hover:bg-surface-container transition-colors\">
                    <span class=\"material-symbols-outlined text-sm\">chevron_right</span>
                  </a>";
    }

    $html .= '</div>';
    return $html;
}

// ------------------------------------------------------------
// FLASH MESSAGE
// ------------------------------------------------------------

/**
 * Set pesan flash (sukses / error / info)
 */
function set_flash(string $tipe, string $pesan): void {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/**
 * Ambil & hapus pesan flash
 */
function get_flash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render pesan flash jadi HTML
 */
function render_flash(): string {
    $flash = get_flash();
    if (!$flash) return '';

    $map = [
        'sukses' => ['bg-green-50 border-green-200 text-green-800',  'check_circle'],
        'error'  => ['bg-error-container border-error text-on-error-container', 'error'],
        'info'   => ['bg-tertiary-fixed/30 border-tertiary-fixed text-on-tertiary-fixed', 'info'],
        'warning'=> ['bg-secondary-container/30 border-secondary text-on-secondary-container', 'warning'],
    ];

    $s = $map[$flash['tipe']] ?? $map['info'];
    return "<div class=\"flex items-center gap-3 p-4 mb-6 rounded-xl border {$s[0]}\">
                <span class=\"material-symbols-outlined\">{$s[1]}</span>
                <p class=\"font-body-md text-body-md\">" . bersihkan($flash['pesan']) . "</p>
            </div>";
}
