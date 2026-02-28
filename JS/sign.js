// Jadikan SATU wadah utama agar semua kode menunggu HTML selesai dimuat
document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA NAVBAR EFEK KACA
    // ==========================================
    const navbar = document.querySelector('.navbar');

    // Pengecekan: Hanya jalankan jika ada navbar di halaman ini
    if (navbar) {
        window.addEventListener('scroll', () => {
            // Jauh lebih ringkas: tambah 'scrolled' jika scroll > 50, hapus jika tidak
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});