document.addEventListener("DOMContentLoaded", () => {


    // ==========================================
    // DETEKSI ZONA WAKTU USER OTOMATIS
    // ==========================================
    const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    // Simpan ke dalam Cookie yang berlaku selama 30 hari
    document.cookie = "user_tz=" + userTimeZone + "; path=/; max-age=2592000";
    
    // ==========================================
    // 1. LOGIKA TAB (UPCOMING vs PAST EVENT)
    // ==========================================
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnPast = document.getElementById('btn-past');
    const viewUpcoming = document.getElementById('view-upcoming');
    const viewPast = document.getElementById('view-past');

    if (btnUpcoming && btnPast && viewUpcoming && viewPast) {
        
        const switchTab = (isUpcomingActive) => {
            // Ubah style tombol aktif
            btnUpcoming.classList.toggle('active', isUpcomingActive);
            btnPast.classList.toggle('active', !isUpcomingActive);

            // KUNCI PERBAIKAN: Keduanya wajib menggunakan 'flex'!
            // Jika menggunakan 'block', perintah 'gap: 30px' di CSS akan mati.
            viewUpcoming.style.display = isUpcomingActive ? 'flex' : 'none'; 
            viewPast.style.display = isUpcomingActive ? 'none' : 'flex';  
        };

        // Pastikan saat halaman pertama kali dimuat, tab Upcoming yang aktif
        switchTab(true);

        btnUpcoming.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(true); 
        });

        btnPast.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(false); 
        });
    }

    // ==========================================
    // 2. LOGIKA NAVBAR EFEK KACA (BLUR ON SCROLL)
    // ==========================================
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});