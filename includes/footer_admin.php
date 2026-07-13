<?php
// ============================================================
// includes/footer_admin.php
// Penutup HTML untuk semua halaman ADMIN
// ============================================================
?>

            <!-- ===== AKHIR KONTEN ADMIN ===== -->
            </main>
        </div><!-- end flex wrapper -->
    </div><!-- end main content -->
</div><!-- end body wrapper -->

<!-- Admin JS -->
<script src="<?= ASSETS_URL ?>/js/admin.js"></script>

<script>
// ============================================================
// Toast Notification (Flash Message)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const toast = document.getElementById('toast-notif');
    if (toast) {
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
});

// ============================================================
// Konfirmasi Hapus Universal
// Pakai: <button data-confirm="Yakin hapus?">Hapus</button>
// ============================================================
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
        const pesan = this.getAttribute('data-confirm') || 'Yakin ingin melanjutkan?';
        if (!confirm(pesan)) e.preventDefault();
    });
});

// ============================================================
// Auto-hide alert setelah 5 detik
// ============================================================
document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
    setTimeout(() => {
        el.style.transition = 'opacity 0.3s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, 5000);
});
</script>

</body>
</html>
