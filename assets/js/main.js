// ============================================================
// assets/js/main.js
// JavaScript utama untuk semua halaman USER
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------------------------
    // Auto-hide flash message setelah 5 detik
    // ----------------------------------------------------------
    const flash = document.getElementById('flash-message');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            flash.style.opacity    = '0';
            flash.style.transform  = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 400);
        }, 5000);
    }

    // ----------------------------------------------------------
    // Rating Bintang Interaktif (untuk form ulasan)
    // ----------------------------------------------------------
    const starContainers = document.querySelectorAll('.star-rating');
    starContainers.forEach(function (container) {
        const stars  = container.querySelectorAll('.star-btn');
        const input  = container.querySelector('input[type="hidden"]');

        stars.forEach(function (star, index) {
            // Hover: highlight bintang
            star.addEventListener('mouseenter', function () {
                stars.forEach((s, i) => {
                    const icon = s.querySelector('.material-symbols-outlined');
                    icon.style.fontVariationSettings = i <= index
                        ? "'FILL' 1" : "'FILL' 0";
                    s.classList.toggle('text-secondary-container', i <= index);
                    s.classList.toggle('text-outline', i > index);
                });
            });

            // Mouse leave: kembalikan ke nilai terpilih
            container.addEventListener('mouseleave', function () {
                const nilai = parseInt(input?.value || '0');
                stars.forEach((s, i) => {
                    const icon = s.querySelector('.material-symbols-outlined');
                    icon.style.fontVariationSettings = i < nilai
                        ? "'FILL' 1" : "'FILL' 0";
                    s.classList.toggle('text-secondary-container', i < nilai);
                    s.classList.toggle('text-outline', i >= nilai);
                });
            });

            // Klik: set nilai rating
            star.addEventListener('click', function () {
                const nilai = index + 1;
                if (input) input.value = nilai;

                // Update label rating
                const label = container.closest('[data-rating-wrap]')
                    ?.querySelector('[data-rating-label]');
                if (label) {
                    const labels = ['', 'Buruk', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik'];
                    label.textContent = labels[nilai] || '';
                }
            });
        });
    });

    // ----------------------------------------------------------
    // Preview Foto Upload (untuk form profil & ulasan)
    // ----------------------------------------------------------
    document.querySelectorAll('.input-foto-preview').forEach(function (input) {
        input.addEventListener('change', function () {
            const file    = this.files[0];
            const preview = document.querySelector(this.dataset.preview);
            if (!file || !preview) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file maksimal 2MB');
                this.value = '';
                return;
            }

            const reader  = new FileReader();
            reader.onload = e => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        });
    });

    // ----------------------------------------------------------
    // Hitung Harga Booking Real-time (checkout.php)
    // ----------------------------------------------------------
    const tglAmbil   = document.getElementById('tgl_ambil');
    const tglKembali = document.getElementById('tgl_kembali');
    const hargaHari  = document.getElementById('harga_per_hari');

    function hitungHarga() {
        if (!tglAmbil || !tglKembali || !hargaHari) return;

        const ambil   = new Date(tglAmbil.value);
        const kembali = new Date(tglKembali.value);

        if (isNaN(ambil) || isNaN(kembali) || kembali <= ambil) return;

        const durasi      = Math.ceil((kembali - ambil) / (1000 * 60 * 60 * 24));
        const harga       = parseInt(hargaHari.value) || 0;
        const sopir       = parseInt(document.getElementById('harga_sopir')?.value || 0);
        const denganSopir = document.getElementById('dengan_sopir')?.checked;
        const asuransi    = parseInt(document.getElementById('biaya_asuransi')?.value || 50000);
        const admin       = parseInt(document.getElementById('biaya_admin')?.value   || 20000);

        const subtotal      = harga * durasi;
        const totalSopir    = denganSopir ? sopir * durasi : 0;
        const total         = subtotal + totalSopir + asuransi + admin;

        // Update tampilan
        const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        set('display_durasi',      durasi + ' Hari');
        set('display_subtotal',    fmt(subtotal));
        set('display_sopir',       fmt(totalSopir));
        set('display_asuransi',    fmt(asuransi));
        set('display_admin',       fmt(admin));
        set('display_total',       fmt(total));
        set('display_dp_minimal',  fmt(Math.ceil(total * 0.3)));

        // Update input hidden total
        const inputTotal = document.getElementById('input_total');
        if (inputTotal) inputTotal.value = total;
        const inputDurasi = document.getElementById('input_durasi');
        if (inputDurasi) inputDurasi.value = durasi;
    }

    if (tglAmbil)   tglAmbil.addEventListener('change', hitungHarga);
    if (tglKembali) tglKembali.addEventListener('change', hitungHarga);
    const sopirToggle = document.getElementById('dengan_sopir');
    if (sopirToggle) sopirToggle.addEventListener('change', hitungHarga);

    // ----------------------------------------------------------
    // Copy to clipboard (nomor rekening, kode promo, dll)
    // ----------------------------------------------------------
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const teks = this.dataset.copy;
            navigator.clipboard.writeText(teks).then(() => {
                const orig = this.innerHTML;
                this.innerHTML = '<span class="material-symbols-outlined text-sm">check</span> Disalin!';
                setTimeout(() => { this.innerHTML = orig; }, 2000);
            });
        });
    });

    // ----------------------------------------------------------
    // Counter animasi angka (dashboard, statistik)
    // ----------------------------------------------------------
    document.querySelectorAll('[data-count]').forEach(function (el) {
        const target   = parseInt(el.dataset.count);
        const duration = 1500;
        const step     = target / (duration / 16);
        let current    = 0;

        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = Math.floor(current).toLocaleString('id-ID');
        }, 16);
    });

});
