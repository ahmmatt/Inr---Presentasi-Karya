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

            // Menggunakan styling class agar tetap selaras dengan CSS external
            if (isUpcomingActive) {
                viewUpcoming.classList.remove('hidden-display');
                viewPast.classList.add('hidden-display');
            } else {
                viewUpcoming.classList.add('hidden-display');
                viewPast.classList.remove('hidden-display');
            }
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

    // ==========================================
    // 3. DROPDOWN NAVBAR PROFILE
    // ==========================================
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        // Munculkan menu saat foto profil diklik
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Tutup menu otomatis jika user klik area kosong di layar
        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }

});