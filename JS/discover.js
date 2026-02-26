document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA SLIDER (UPCOMING EVENT)
    // ==========================================
    const track = document.querySelector('.upcoming-event');
    const leftBtn = document.getElementById('slide-left');
    const rightBtn = document.getElementById('slide-right');

    // Pengecekan: Hanya jalankan slider jika elemen-elemennya ada di halaman ini
    if (track && leftBtn && rightBtn) {
        const scrollAmount = 320; 

        // Fungsi terpisah untuk mengecek kapan tombol muncul/hilang
        const updateButtonState = () => {
            // Jika scroll lebih dari 0, munculkan tombol kiri
            leftBtn.style.display = track.scrollLeft > 0 ? 'flex' : 'none';
            
            // Cek batas mentok kanan (dengan toleransi 5px)
            const maxScroll = track.scrollWidth - track.clientWidth;
            rightBtn.style.display = track.scrollLeft >= maxScroll - 5 ? 'none' : 'flex';
        };

        // Panggil fungsi sekali saat halaman baru dimuat (agar tombol kiri otomatis hilang di awal)
        updateButtonState();

        // Event Klik Tombol Geser
        rightBtn.addEventListener('click', () => {
            track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        leftBtn.addEventListener('click', () => {
            track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        // Event Scroll untuk memantau pergeseran
        track.addEventListener('scroll', updateButtonState);
    }

    // ==========================================
    // 2. LOGIKA NAVBAR EFEK KACA
    // ==========================================
    const navbar = document.querySelector('.navbar');

    // Pengecekan: Hanya jalankan jika ada navbar di halaman ini
    if (navbar) {
        window.addEventListener('scroll', () => {
            // classList.toggle akan menempelkan class 'scrolled' jika window.scrollY > 50 (true), 
            // dan akan menghapusnya otomatis jika kurang dari 50 (false). Jauh lebih rapi!
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});