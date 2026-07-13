<?php
// ============================================================
// auth/lupa_password.php — Lupa Password (3 Step)
// Step 1: Input email/HP → Step 2: Input OTP → Step 3: Password Baru
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/email.php';

$step    = (int) ($_SESSION['reset_step'] ?? 1);
$error   = '';

// Deteksi apakah proses lupa password untuk admin atau user
// (disimpan di session agar konsisten selama 3 langkah)
if (isset($_GET['tipe']) && $_GET['tipe'] === 'admin') {
    $_SESSION['reset_tipe'] = 'admin';
} elseif (!isset($_SESSION['reset_tipe'])) {
    $_SESSION['reset_tipe'] = 'user';
}
$tipe_reset = $_SESSION['reset_tipe'] ?? 'user';
// URL kembali sesuai tipe
$url_kembali = ($tipe_reset === 'admin')
    ? BASE_URL . '/admin/login.php'
    : BASE_URL . '/auth/masuk.php';

// ============================================================
// PROSES STEP 1: Kirim OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $identifier = bersihkan($_POST['identifier'] ?? '');

        if (empty($identifier)) {
            $error = 'Email atau No. HP wajib diisi.';
        } else {
            try {
                if ($tipe_reset === 'admin') {
                    $stmt = $pdo->prepare("SELECT id, nama, email FROM admin WHERE email = ? LIMIT 1");
                    $stmt->execute([$identifier]);
                } else {
                    $stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE email = ? OR no_hp = ? LIMIT 1");
                    $stmt->execute([$identifier, $identifier]);
                }
                $user = $stmt->fetch();

                if ($user) {
                    // Buat OTP baru
                    $kode    = buat_kode_otp();
                    $expired = time() + (OTP_EXPIRED_MENIT * 60);

                    if ($tipe_reset === 'admin') {
                        // OTP admin disimpan di session (tabel otp_codes terikat ke users)
                        $_SESSION['admin_otp_kode']    = $kode;
                        $_SESSION['admin_otp_expired'] = $expired;
                    } else {
                        // Hapus OTP lama & simpan OTP baru di tabel
                        $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND tipe = 'lupa_password'")
                            ->execute([$user['id']]);
                        $pdo->prepare("INSERT INTO otp_codes (user_id, kode, tipe, expired_at) VALUES (?,?,?,?)")
                            ->execute([$user['id'], $kode, 'lupa_password', date('Y-m-d H:i:s', $expired)]);
                    }

                    // Kirim email OTP
                    $email_terkirim = kirim_otp_lupa_password($user['email'], $user['nama'], $kode);

                    if (!$email_terkirim) {
                        // Email gagal terkirim (PHPMailer belum dikonfigurasi / SMTP error)
                        $error = 'Gagal mengirim email OTP. Pastikan konfigurasi email (SMTP) sudah benar di config.php, atau hubungi admin.';
                    } else {
                        // Simpan data di session
                        $_SESSION['reset_user_id']    = $user['id'];
                        $_SESSION['reset_email_mask'] = substr($user['email'], 0, 3) . '***@' . explode('@', $user['email'])[1];
                        $_SESSION['reset_step']       = 2;
                        $_SESSION['otp_sent_at']      = time();

                        header('Location: ' . BASE_URL . '/auth/lupa_password.php');
                        exit;
                    }
                } else {
                    $error = 'Email atau No. HP tidak ditemukan.';
                }
            } catch (Exception $e) {
                $error = 'Gagal mengirim OTP. Coba lagi.';
                error_log('Lupa password error: ' . $e->getMessage());
            }
        }
    }
}

// ============================================================
// PROSES STEP 2: Verifikasi OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Sesi tidak valid.';
    } else {
        $kode_input = implode('', array_map(fn($i) => bersihkan($_POST["otp_$i"] ?? ''), range(1, 6)));
        $user_id    = $_SESSION['reset_user_id'] ?? null;

        if (!$user_id) {
            $error = 'Sesi tidak valid. Mulai ulang.';
            $_SESSION['reset_step'] = 1;
        } elseif (strlen($kode_input) !== 6) {
            $error = 'Masukkan 6 digit kode OTP.';
        } else {
            try {
                $otp_valid = false;
                if ($tipe_reset === 'admin') {
                    // Verifikasi OTP admin dari session
                    $kode_benar = $_SESSION['admin_otp_kode'] ?? '';
                    $expired    = $_SESSION['admin_otp_expired'] ?? 0;
                    if ($kode_input === $kode_benar && time() < $expired) {
                        $otp_valid = true;
                    }
                } else {
                    $stmt = $pdo->prepare("
                        SELECT id FROM otp_codes
                        WHERE user_id = ? AND kode = ? AND tipe = 'lupa_password'
                        AND expired_at > NOW() AND is_used = 0
                    ");
                    $stmt->execute([$user_id, $kode_input]);
                    if ($stmt->fetch()) $otp_valid = true;
                }

                if ($otp_valid) {
                    $_SESSION['reset_step']    = 3;
                    $_SESSION['reset_otp_ok']  = true;
                    header('Location: ' . BASE_URL . '/auth/lupa_password.php');
                    exit;
                } else {
                    $error = 'Kode OTP salah atau sudah kedaluwarsa.';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
        }
    }
}

// Kirim ulang OTP
if (isset($_GET['resend']) && ($step === 2) && isset($_SESSION['reset_user_id'])) {
    $elapsed = time() - ($_SESSION['otp_sent_at'] ?? 0);
    if ($elapsed >= 60) {
        try {
            $user_id = $_SESSION['reset_user_id'];
            $u = $pdo->prepare("SELECT nama, email FROM users WHERE id = ?");
            $u->execute([$user_id]);
            $user = $u->fetch();

            $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ? AND tipe = 'lupa_password'")->execute([$user_id]);
            $kode    = buat_kode_otp();
            $expired = date('Y-m-d H:i:s', time() + (OTP_EXPIRED_MENIT * 60));
            $pdo->prepare("INSERT INTO otp_codes (user_id, kode, tipe, expired_at) VALUES (?,?,?,?)")
                ->execute([$user_id, $kode, 'lupa_password', $expired]);
            kirim_otp_lupa_password($user['email'], $user['nama'], $kode);
            $_SESSION['otp_sent_at'] = time();
        } catch (Exception $e) {}
    }
    header('Location: ' . BASE_URL . '/auth/lupa_password.php');
    exit;
}

// ============================================================
// PROSES STEP 3: Simpan Password Baru
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '') || !($_SESSION['reset_otp_ok'] ?? false)) {
        $error = 'Sesi tidak valid. Mulai ulang.';
    } else {
        $password   = $_POST['password']   ?? '';
        $konfirmasi = $_POST['konfirmasi'] ?? '';
        $user_id    = $_SESSION['reset_user_id'] ?? null;

        if (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!$user_id) {
            $error = 'Sesi tidak valid.';
        } else {
            try {
                if ($tipe_reset === 'admin') {
                    // Update password admin
                    $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?")
                        ->execute([hash_password($password), $user_id]);
                    // Bersihkan OTP admin dari session
                    unset($_SESSION['admin_otp_kode'], $_SESSION['admin_otp_expired']);
                } else {
                    // Update password user
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                        ->execute([hash_password($password), $user_id]);
                    // Tandai OTP sudah dipakai
                    $pdo->prepare("UPDATE otp_codes SET is_used = 1 WHERE user_id = ? AND tipe = 'lupa_password'")
                        ->execute([$user_id]);
                }

                // URL tujuan setelah reset
                $tujuan = ($tipe_reset === 'admin')
                    ? BASE_URL . '/admin/login.php'
                    : BASE_URL . '/auth/masuk.php';

                // Hapus session reset
                unset($_SESSION['reset_step'], $_SESSION['reset_user_id'],
                      $_SESSION['reset_otp_ok'], $_SESSION['reset_email_mask'],
                      $_SESSION['otp_sent_at'], $_SESSION['reset_tipe']);

                set_flash('sukses', 'Password berhasil diubah! Silakan masuk.');
                header('Location: ' . $tujuan);
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal mengubah password. Coba lagi.';
            }
        }
    }
}

$step         = (int) ($_SESSION['reset_step']       ?? 1);
$email_mask   = $_SESSION['reset_email_mask']         ?? '';
$otp_sent_at  = $_SESSION['otp_sent_at']              ?? 0;
$sisa_detik   = max(0, 180 - (time() - $otp_sent_at)); // countdown 3 menit
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode:"class",
            theme:{ extend:{
                colors:{
                    "primary":"#041627","on-primary":"#ffffff",
                    "primary-container":"#1a2b3c","on-primary-container":"#8192a7",
                    "secondary":"#855300","secondary-container":"#fea619",
                    "background":"#f8f9fa","on-background":"#191c1d",
                    "surface":"#f8f9fa","on-surface":"#191c1d",
                    "surface-variant":"#e1e3e4","on-surface-variant":"#44474c",
                    "surface-container-lowest":"#ffffff",
                    "surface-container-low":"#f3f4f5",
                    "surface-container":"#edeeef",
                    "surface-container-high":"#e7e8e9",
                    "outline":"#74777d","outline-variant":"#c4c6cd",
                    "error":"#ba1a1a","error-container":"#ffdad6",
                    "on-error-container":"#93000a",
                },
                fontFamily:{
                    "headline-md":["Montserrat"],"headline-sm":["Montserrat"],
                    "body-md":["Inter"],"label-md":["Inter"],"label-sm":["Inter"],
                },
                fontSize:{
                    "headline-md":["24px",{lineHeight:"32px",fontWeight:"600"}],
                    "headline-sm":["20px",{lineHeight:"28px",fontWeight:"600"}],
                    "body-md":["16px",{lineHeight:"24px",fontWeight:"400"}],
                    "label-md":["14px",{lineHeight:"20px",letterSpacing:"0.01em",fontWeight:"500"}],
                    "label-sm":["12px",{lineHeight:"16px",fontWeight:"600"}],
                },
                spacing:{ "gutter":"24px","margin-mobile":"16px" },
            }}
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background min-h-screen font-body-md text-on-background antialiased flex flex-col">

<main class="flex-grow flex items-center justify-center p-gutter">
    <div class="bg-surface-container-lowest rounded-xl
                shadow-[0px_4px_20px_rgba(26,43,60,0.12)]
                w-full max-w-2xl p-8 md:p-12
                border border-surface-variant relative overflow-hidden">

        <!-- Dekorasi BG -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-bl-full -z-10 blur-3xl"></div>

        <!-- Kembali ke Login -->
        <a href="<?= $url_kembali ?>"
           class="inline-flex items-center text-primary hover:text-primary/70
                  transition-colors font-label-md text-label-md mb-8 group">
            <span class="material-symbols-outlined mr-2 text-[18px]
                         group-hover:-translate-x-1 transition-transform">
                arrow_back
            </span>
            Kembali ke Login
        </a>

        <!-- Ikon + Judul -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20
                        rounded-full bg-primary/10 text-primary mb-6">
                <span class="material-symbols-outlined text-[40px]"
                      style="font-variation-settings:'FILL' 1">lock_reset</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-on-surface mb-2">
                Lupa Password?
            </h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Tenang, kami bantu reset sandi Anda
            </p>
        </div>

        <!-- Stepper -->
        <div class="flex items-center justify-center mb-10 max-w-sm mx-auto">
            <?php foreach ([1=>'Email/HP', 2=>'Kode OTP', 3=>'Password Baru'] as $n => $label): ?>
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                font-label-sm text-label-sm font-bold text-sm
                                <?= $step >= $n
                                    ? 'bg-primary text-on-primary'
                                    : 'bg-surface-container text-on-surface-variant' ?>">
                        <?= $step > $n
                            ? '<span class="material-symbols-outlined text-sm">check</span>'
                            : $n ?>
                    </div>
                    <span class="text-[11px] font-label-sm text-on-surface-variant mt-1 whitespace-nowrap">
                        <?= $label ?>
                    </span>
                </div>
                <?php if ($n < 3): ?>
                    <div class="flex-1 h-px mx-2 <?= $step > $n ? 'bg-primary' : 'bg-outline-variant' ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="flex items-center gap-3 p-4 mb-6 rounded-xl
                        bg-error-container border border-error/20 text-on-error-container">
                <span class="material-symbols-outlined shrink-0">error</span>
                <p class="font-label-md text-label-md"><?= bersihkan($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- ================================================ -->
        <!-- STEP 1: Input Email / HP                         -->
        <!-- ================================================ -->
        <?php if ($step === 1): ?>
        <form method="POST" action="" class="space-y-6">
            <?= csrf_field() ?>
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-2"
                       for="identifier">
                    Email atau Nomor HP
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3
                                 top-1/2 -translate-y-1/2 text-outline">
                        contact_mail
                    </span>
                    <input type="text" id="identifier" name="identifier"
                           placeholder="Masukkan email atau No. HP terdaftar"
                           required
                           class="w-full pl-10 pr-4 py-3 rounded-lg border border-outline-variant
                                  bg-surface-container-low text-on-surface font-body-md text-body-md
                                  focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                </div>
            </div>
            <button type="submit" name="step1"
                    class="w-full bg-primary text-on-primary font-label-md text-label-md
                           py-3 rounded-lg hover:bg-primary/90 transition-colors
                           shadow-sm active:scale-[0.98]">
                KIRIM KODE VERIFIKASI
            </button>
        </form>

        <!-- ================================================ -->
        <!-- STEP 2: Input OTP                                -->
        <!-- ================================================ -->
        <?php elseif ($step === 2): ?>
        <div class="text-center mb-6">
            <p class="font-body-md text-body-md text-on-surface-variant">
                Kode OTP dikirim ke <strong class="text-on-surface"><?= htmlspecialchars($email_mask) ?></strong>
            </p>
            <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
                Berlaku selama <?= OTP_EXPIRED_MENIT ?> menit
            </p>
        </div>

        <form method="POST" action="" class="space-y-6">
            <?= csrf_field() ?>

            <!-- 6 Input OTP -->
            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-4 text-center">
                    Masukkan Kode OTP (6 digit)
                </label>
                <div class="flex justify-center gap-3" id="otp-container">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <input type="text" name="otp_<?= $i ?>" id="otp_<?= $i ?>"
                               maxlength="1" inputmode="numeric" pattern="[0-9]"
                               autocomplete="one-time-code"
                               class="w-12 h-14 text-center text-headline-sm font-headline-sm
                                      rounded-lg border border-outline-variant
                                      focus:border-primary focus:ring-1 focus:ring-primary
                                      outline-none bg-surface-container-lowest
                                      transition-colors text-on-surface">
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Countdown & Resend -->
            <div class="flex justify-between items-center text-sm">
                <span class="font-label-sm text-label-sm text-on-surface-variant">
                    Kirim ulang dalam
                    <span id="countdown" class="text-primary font-bold"></span>
                </span>
                <a href="<?= BASE_URL ?>/auth/lupa_password.php?resend=1"
                   id="resend-btn"
                   class="font-label-sm text-label-sm text-primary hover:underline hidden">
                    Kirim Ulang
                </a>
            </div>

            <button type="submit" name="step2"
                    class="w-full bg-primary text-on-primary font-label-md text-label-md
                           py-3 rounded-lg hover:bg-primary/90 transition-colors
                           shadow-sm active:scale-[0.98]">
                VERIFIKASI KODE
            </button>
        </form>

        <!-- ================================================ -->
        <!-- STEP 3: Password Baru                            -->
        <!-- ================================================ -->
        <?php elseif ($step === 3): ?>
        <form method="POST" action="" class="space-y-6">
            <?= csrf_field() ?>

            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-2"
                       for="password">
                    Password Baru
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3
                                 top-1/2 -translate-y-1/2 text-outline">lock</span>
                    <input type="password" id="password" name="password"
                           placeholder="Min. 8 karakter" required
                           class="w-full pl-10 pr-10 py-3 rounded-lg border border-outline-variant
                                  bg-surface-container-low text-on-surface font-body-md text-body-md
                                  focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    <button type="button" onclick="togglePass('password','eye3')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-outline
                                   hover:text-primary transition-colors">
                        <span class="material-symbols-outlined" id="eye3">visibility_off</span>
                    </button>
                </div>
            </div>

            <div>
                <label class="block font-label-md text-label-md text-on-surface mb-2"
                       for="konfirmasi">
                    Konfirmasi Password Baru
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3
                                 top-1/2 -translate-y-1/2 text-outline">lock</span>
                    <input type="password" id="konfirmasi" name="konfirmasi"
                           placeholder="Ulangi password baru" required
                           class="w-full pl-10 pr-10 py-3 rounded-lg border border-outline-variant
                                  bg-surface-container-low text-on-surface font-body-md text-body-md
                                  focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    <button type="button" onclick="togglePass('konfirmasi','eye4')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-outline
                                   hover:text-primary transition-colors">
                        <span class="material-symbols-outlined" id="eye4">visibility_off</span>
                    </button>
                </div>
            </div>

            <button type="submit" name="step3"
                    class="w-full bg-primary text-on-primary font-label-md text-label-md
                           py-3 rounded-lg hover:bg-primary/90 transition-colors
                           shadow-sm active:scale-[0.98]">
                SIMPAN PASSWORD BARU
            </button>
        </form>
        <?php endif; ?>

    </div>
</main>

<script>
    // Toggle show/hide password
    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        input.type       = input.type === 'password' ? 'text' : 'password';
        icon.textContent = input.type === 'password' ? 'visibility_off' : 'visibility';
    }

    // Auto-focus & auto-next input OTP
    document.querySelectorAll('#otp-container input').forEach(function (input, idx, inputs) {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && idx < inputs.length - 1) inputs[idx + 1].focus();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) inputs[idx - 1].focus();
        });
        input.addEventListener('paste', function (e) {
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
            paste.split('').forEach((c, i) => { if (inputs[idx + i]) inputs[idx + i].value = c; });
            e.preventDefault();
            const last = Math.min(idx + paste.length, inputs.length) - 1;
            inputs[last]?.focus();
        });
    });

    // Countdown OTP
    let sisaDetik = <?= $sisa_detik ?>;
    const countdownEl = document.getElementById('countdown');
    const resendBtn   = document.getElementById('resend-btn');

    if (countdownEl) {
        function updateCountdown() {
            if (sisaDetik <= 0) {
                countdownEl.textContent = '';
                if (resendBtn) resendBtn.classList.remove('hidden');
                return;
            }
            const m = String(Math.floor(sisaDetik / 60)).padStart(2,'0');
            const s = String(sisaDetik % 60).padStart(2,'0');
            countdownEl.textContent = m + ':' + s;
            sisaDetik--;
            setTimeout(updateCountdown, 1000);
        }
        updateCountdown();
    }
</script>
</body>
</html>
