// ============================================================
// assets/js/admin.js
// JavaScript khusus halaman ADMIN
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------------------------
    // Konfirmasi hapus universal
    // Cara pakai: <button data-confirm="Yakin hapus data ini?">
    // ----------------------------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const pesan = this.dataset.confirm || 'Yakin ingin melanjutkan?';
            if (!confirm(pesan)) e.preventDefault();
        });
    });

    // ----------------------------------------------------------
    // Auto-hide toast/flash message
    // ----------------------------------------------------------
    document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
        setTimeout(() => {
            el.style.transition = 'opacity 0.3s, transform 0.3s';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-8px)';
            setTimeout(() => el.remove(), 300);
        }, 5000);
    });

    // ----------------------------------------------------------
    // Preview foto upload (tambah/edit mobil)
    // ----------------------------------------------------------
    document.querySelectorAll('.admin-foto-preview').forEach(function (input) {
        input.addEventListener('change', function () {
            const file     = this.files[0];
            const targetId = this.dataset.preview;
            const preview  = document.getElementById(targetId);
            if (!file || !preview) return;

            if (file.size > 2 * 1024 * 1024) {
                showToast('error', 'Ukuran file maksimal 2MB');
                this.value = '';
                return;
            }

            const reader  = new FileReader();
            reader.onload = e => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        });
    });

    // ----------------------------------------------------------
    // Checkbox "Pilih Semua" di tabel (manajemen armada, dll)
    // ----------------------------------------------------------
    const checkAll = document.getElementById('check-all');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.check-item').forEach(cb => {
                cb.checked = this.checked;
            });
        });

        document.querySelectorAll('.check-item').forEach(function (cb) {
            cb.addEventListener('change', function () {
                const total    = document.querySelectorAll('.check-item').length;
                const checked  = document.querySelectorAll('.check-item:checked').length;
                checkAll.checked       = checked === total;
                checkAll.indeterminate = checked > 0 && checked < total;
            });
        });
    }

    // ----------------------------------------------------------
    // Hitung total booking manual (admin)
    // ----------------------------------------------------------
    function hitungTotalAdmin() {
        const harga   = parseInt(document.getElementById('admin_harga_hari')?.value   || 0);
        const durasi  = parseInt(document.getElementById('admin_durasi')?.value        || 0);
        const sopir   = parseInt(document.getElementById('admin_harga_sopir')?.value  || 0);
        const dgSopir = document.getElementById('admin_dengan_sopir')?.checked;
        const asuransi = 50000;
        const adminFee = 20000;

        const subtotal   = harga * durasi;
        const totalSopir = dgSopir ? sopir * durasi : 0;
        const total      = subtotal + totalSopir + asuransi + adminFee;

        const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        set('admin_display_subtotal',  fmt(subtotal));
        set('admin_display_sopir',     fmt(totalSopir));
        set('admin_display_asuransi',  fmt(asuransi));
        set('admin_display_admin_fee', fmt(adminFee));
        set('admin_display_total',     fmt(total));

        const inputTotal = document.getElementById('admin_input_total');
        if (inputTotal) inputTotal.value = total;
    }

    ['admin_harga_hari','admin_durasi','admin_dengan_sopir'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', hitungTotalAdmin);
    });

    // ----------------------------------------------------------
    // Filter tabel dengan pencarian (client-side)
    // ----------------------------------------------------------
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('[data-searchable]').forEach(row => {
                const teks = row.textContent.toLowerCase();
                row.style.display = teks.includes(query) ? '' : 'none';
            });
        });
    }

    // ----------------------------------------------------------
    // Drag & drop urutan foto mobil
    // ----------------------------------------------------------
    const fotoGrid = document.getElementById('foto-sort-grid');
    if (fotoGrid && typeof Sortable !== 'undefined') {
        new Sortable(fotoGrid, {
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function () {
                // Update input hidden urutan foto
                fotoGrid.querySelectorAll('[data-foto-id]').forEach((el, i) => {
                    const input = document.getElementById('urutan_foto_' + el.dataset.fotoId);
                    if (input) input.value = i + 1;
                });
            }
        });
    }

});

// ----------------------------------------------------------
// Toast Notification helper
// Cara pakai dari PHP: showToast('sukses', 'Data berhasil disimpan')
// ----------------------------------------------------------
function showToast(tipe, pesan) {
    const warna = {
        sukses:  'bg-green-50 border-green-200 text-green-800',
        error:   'bg-error-container border-error text-on-error-container',
        info:    'bg-tertiary-fixed/50 border-tertiary-fixed text-on-tertiary-fixed',
        warning: 'bg-secondary-container/30 border-secondary text-on-secondary-container',
    };
    const ikon = {
        sukses: 'check_circle', error: 'error', info: 'info', warning: 'warning',
    };

    const toast = document.createElement('div');
    toast.className = `fixed top-20 right-6 z-[200] flex items-center gap-3 px-5 py-4
                       rounded-xl border shadow-lg min-w-[280px] max-w-sm
                       transition-all duration-300 ${warna[tipe] || warna.info}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${ikon[tipe] || 'info'}</span>
        <p class="font-label-md text-label-md flex-1">${pesan}</p>
        <button onclick="this.parentElement.remove()"
                class="material-symbols-outlined text-[18px] opacity-60 hover:opacity-100">
            close
        </button>
    `;

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
