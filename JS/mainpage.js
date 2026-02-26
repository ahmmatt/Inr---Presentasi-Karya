document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA TAB (UPCOMING vs PAST EVENT)
    // ==========================================
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnPast = document.getElementById('btn-past');
    const viewUpcoming = document.getElementById('view-upcoming');
    const viewPast = document.getElementById('view-past');

    // Pengecekan Keamanan: Hanya jalankan jika semua elemen tab ada di halaman ini
    if (btnUpcoming && btnPast && viewUpcoming && viewPast) {
        
        // Fungsi pembantu agar kode tidak berulang
        const switchTab = (isUpcomingActive) => {
            // Mengatur state tombol (nyala/mati)
            btnUpcoming.classList.toggle('active', isUpcomingActive);
            btnPast.classList.toggle('active', !isUpcomingActive);

            // Mengatur tampilan konten (muncul/hilang)
            viewUpcoming.style.display = isUpcomingActive ? 'flex' : 'none';
            viewPast.style.display = isUpcomingActive ? 'none' : 'flex';
        };

        // Event Klik Tombol Upcoming
        btnUpcoming.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(true); // true = Upcoming aktif
        });

        // Event Klik Tombol Past
        btnPast.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(false); // false = Past aktif (Upcoming mati)
        });
    }

    // ==========================================
    // 2. LOGIKA NAVBAR EFEK KACA (BLUR ON SCROLL)
    // ==========================================
    const navbar = document.querySelector('.navbar');

    // Pengecekan Keamanan: Hanya jalankan jika navbar ada
    if (navbar) {
        window.addEventListener('scroll', () => {
            // classList.toggle akan otomatis menambah class 'scrolled' jika scrollY > 50, 
            // dan menghapusnya otomatis jika kurang dari 50. Jauh lebih ringkas!
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});