<?php
// ============================================================
// functions/email.php
// Semua fungsi kirim email via PHPMailer
// ============================================================

require_once __DIR__ . '/../config/config.php';

// Muat PHPMailer HANYA jika tersedia. Jika belum diinstall (folder vendor/
// belum ada), website tetap berjalan normal — email hanya di-skip.
$__phpmailer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($__phpmailer_autoload)) {
    require_once $__phpmailer_autoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Cek apakah PHPMailer siap dipakai.
 * Semua fungsi kirim email memakai ini agar tidak fatal error.
 */
function email_tersedia(): bool {
    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

// ------------------------------------------------------------
// SETUP DASAR PHPMAILER
// ------------------------------------------------------------

/**
 * Buat instance PHPMailer yang sudah dikonfigurasi
 */
function buat_mailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    $mail->isHTML(true);
    return $mail;
}

/**
 * Template HTML email dasar
 */
function template_email(string $judul, string $konten): string {
    return "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$judul}</title>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #ffffff;
                         border-radius: 12px; overflow: hidden;
                         box-shadow: 0 4px 20px rgba(4,22,39,0.08); }
            .header { background: #041627; padding: 32px 40px; text-align: center; }
            .header h1 { color: #ffffff; font-family: 'Montserrat', Arial, sans-serif;
                          font-size: 24px; margin: 0; letter-spacing: 2px; }
            .header p { color: #8192a7; font-size: 13px; margin: 8px 0 0; }
            .body { padding: 40px; color: #191c1d; }
            .body h2 { font-family: 'Montserrat', Arial, sans-serif;
                        font-size: 20px; color: #041627; margin: 0 0 16px; }
            .body p { font-size: 16px; line-height: 1.6; color: #44474c; margin: 0 0 16px; }
            .kotak { background: #f3f4f5; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
            .kotak-row { display: flex; justify-content: space-between;
                          padding: 8px 0; border-bottom: 1px solid #e1e3e4; }
            .kotak-row:last-child { border-bottom: none; }
            .kotak-label { color: #74777d; font-size: 14px; }
            .kotak-value { color: #191c1d; font-size: 14px; font-weight: 600; }
            .btn { display: inline-block; background: #041627; color: #ffffff;
                    padding: 14px 32px; border-radius: 999px; text-decoration: none;
                    font-size: 14px; font-weight: 600; margin: 8px 0; }
            .btn-gold { background: #fea619; color: #191c1d; }
            .otp-box { background: #041627; color: #ffffff; font-size: 36px;
                        font-weight: 700; letter-spacing: 12px; text-align: center;
                        padding: 24px; border-radius: 12px; margin: 24px 0; }
            .footer { background: #f3f4f5; padding: 24px 40px; text-align: center; }
            .footer p { color: #74777d; font-size: 13px; margin: 4px 0; }
            .total { font-size: 20px; font-weight: 700; color: #041627; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>LUXE DRIVE</h1>
                <p>Premium Car Rental Experience</p>
            </div>
            <div class='body'>{$konten}</div>
            <div class='footer'>
                <p>" . APP_NAME . " &copy; " . date('Y') . "</p>
                <p>" . APP_ADDRESS . "</p>
                <p><a href='mailto:" . APP_EMAIL . "' style='color:#74777d'>" . APP_EMAIL . "</a></p>
            </div>
        </div>
    </body>
    </html>";
}

// ------------------------------------------------------------
// KIRIM EMAIL OTP (Lupa Password)
// ------------------------------------------------------------

/**
 * Kirim kode OTP lupa password
 */
function kirim_otp_lupa_password(string $email, string $nama, string $kode): bool {
    // Jika PHPMailer belum diinstall, lewati pengiriman (tidak error).
    if (!email_tersedia()) { return false; }

    try {
        $mail = buat_mailer();
        $mail->addAddress($email, $nama);
        $mail->Subject = '[Luxe Drive] Kode Reset Password Anda';

        $konten = "
            <h2>Reset Password</h2>
            <p>Halo <strong>{$nama}</strong>,</p>
            <p>Kami menerima permintaan reset password untuk akun Anda.
               Gunakan kode OTP berikut:</p>
            <div class='otp-box'>{$kode}</div>
            <p style='text-align:center; color:#74777d; font-size:14px;'>
                Kode berlaku selama <strong>" . OTP_EXPIRED_MENIT . " menit</strong>
            </p>
            <p>Jika Anda tidak meminta reset password, abaikan email ini.
               Akun Anda tetap aman.</p>
        ";

        $mail->Body = template_email('Reset Password', $konten);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email OTP gagal: ' . $e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// KIRIM EMAIL KONFIRMASI BOOKING
// ------------------------------------------------------------

/**
 * Kirim konfirmasi booking ke pelanggan
 */
function kirim_konfirmasi_booking(string $email, string $nama, array $booking): bool {
    // Jika PHPMailer belum diinstall, lewati pengiriman (tidak error).
    if (!email_tersedia()) { return false; }

    try {
        $mail = buat_mailer();
        $mail->addAddress($email, $nama);
        $mail->Subject = '[Luxe Drive] Konfirmasi Booking ' . $booking['kode_booking'];

        $tgl_ambil   = format_datetime($booking['tgl_ambil']);
        $tgl_kembali = format_datetime($booking['tgl_kembali']);
        $total       = format_rupiah($booking['total']);
        $sopir       = $booking['dengan_sopir'] ? 'Ya' : 'Tidak';

        $konten = "
            <h2>Booking Berhasil Dibuat!</h2>
            <p>Halo <strong>{$nama}</strong>,</p>
            <p>Terima kasih telah mempercayai <strong>Luxe Drive</strong>.
               Booking Anda telah kami terima dan sedang menunggu verifikasi pembayaran.</p>

            <div class='kotak'>
                <div class='kotak-row'>
                    <span class='kotak-label'>Kode Booking</span>
                    <span class='kotak-value'>{$booking['kode_booking']}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Kendaraan</span>
                    <span class='kotak-value'>{$booking['nama_mobil']}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Tanggal Ambil</span>
                    <span class='kotak-value'>{$tgl_ambil}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Tanggal Kembali</span>
                    <span class='kotak-value'>{$tgl_kembali}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Durasi</span>
                    <span class='kotak-value'>{$booking['durasi_hari']} Hari</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Dengan Sopir</span>
                    <span class='kotak-value'>{$sopir}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Lokasi Jemput</span>
                    <span class='kotak-value'>{$booking['lokasi_jemput']}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label total'>Total Pembayaran</span>
                    <span class='kotak-value total'>{$total}</span>
                </div>
            </div>

            <p>Segera lakukan pembayaran untuk mengkonfirmasi booking Anda.
               Hubungi kami jika ada pertanyaan.</p>
            <p style='text-align:center'>
                <a href='" . BASE_URL . "/user/pesanan.php' class='btn'>
                    Lihat Status Booking
                </a>
                <a href='https://wa.me/" . APP_WHATSAPP . "' class='btn btn-gold'>
                    Hubungi via WhatsApp
                </a>
            </p>
        ";

        $mail->Body = template_email('Konfirmasi Booking', $konten);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email konfirmasi booking gagal: ' . $e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// KIRIM EMAIL UPDATE STATUS BOOKING
// ------------------------------------------------------------

/**
 * Kirim notifikasi perubahan status booking
 */
function kirim_notif_status(string $email, string $nama, string $kode, string $status): bool {
    // Jika PHPMailer belum diinstall, lewati pengiriman (tidak error).
    if (!email_tersedia()) { return false; }

    $pesan_status = [
        'confirmed'   => ['Booking Dikonfirmasi ✅', 'Booking Anda telah dikonfirmasi. Mohon selesaikan pembayaran.'],
        'berlangsung' => ['Kendaraan Sedang Diantar 🚗', 'Kendaraan Anda sedang dalam perjalanan menuju lokasi penjemputan.'],
        'selesai'     => ['Terima Kasih! 🎉', 'Sewa kendaraan Anda telah selesai. Bagaimana pengalaman Anda?'],
        'dibatalkan'  => ['Booking Dibatalkan ❌', 'Booking Anda telah dibatalkan. Hubungi kami jika ada pertanyaan.'],
    ];

    $info = $pesan_status[$status] ?? ['Update Status Booking', 'Status booking Anda telah diperbarui.'];

    try {
        $mail = buat_mailer();
        $mail->addAddress($email, $nama);
        $mail->Subject = '[Luxe Drive] ' . $info[0] . ' - ' . $kode;

        $konten = "
            <h2>{$info[0]}</h2>
            <p>Halo <strong>{$nama}</strong>,</p>
            <p>{$info[1]}</p>
            <div class='kotak'>
                <div class='kotak-row'>
                    <span class='kotak-label'>Kode Booking</span>
                    <span class='kotak-value'>{$kode}</span>
                </div>
                <div class='kotak-row'>
                    <span class='kotak-label'>Status Terbaru</span>
                    <span class='kotak-value'>" . ucfirst($status) . "</span>
                </div>
            </div>
            <p style='text-align:center'>
                <a href='" . BASE_URL . "/user/pesanan.php' class='btn'>
                    Lihat Detail Booking
                </a>
            </p>
        ";

        $mail->Body = template_email('Update Status Booking', $konten);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email notif status gagal: ' . $e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// KIRIM EMAIL VERIFIKASI REGISTRASI
// ------------------------------------------------------------

/**
 * Kirim email selamat datang setelah daftar
 */
function kirim_email_registrasi(string $email, string $nama): bool {
    // Jika PHPMailer belum diinstall, lewati pengiriman (tidak error).
    if (!email_tersedia()) { return false; }

    try {
        $mail = buat_mailer();
        $mail->addAddress($email, $nama);
        $mail->Subject = '[Luxe Drive] Selamat Datang di Luxe Drive!';

        $konten = "
            <h2>Selamat Datang, {$nama}! 🎉</h2>
            <p>Akun Anda telah berhasil dibuat. Sekarang Anda dapat menikmati
               layanan premium Luxe Drive.</p>
            <p>Nikmati berbagai keuntungan sebagai member Luxe Drive:</p>
            <div class='kotak'>
                <p style='margin:4px 0'>✅ Akses ke armada kendaraan premium</p>
                <p style='margin:4px 0'>✅ Pemesanan mudah dan cepat</p>
                <p style='margin:4px 0'>✅ Layanan 24 jam siap membantu</p>
                <p style='margin:4px 0'>✅ Penawaran eksklusif untuk member</p>
            </div>
            <p style='text-align:center'>
                <a href='" . BASE_URL . "/daftar_armada.php' class='btn btn-gold'>
                    Lihat Armada Kami
                </a>
            </p>
        ";

        $mail->Body = template_email('Selamat Datang', $konten);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email registrasi gagal: ' . $e->getMessage());
        return false;
    }
}
