document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA TAB (UPCOMING vs PAST EVENT)
    // ==========================================
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnPast = document.getElementById('btn-past');
    const viewUpcoming = document.getElementById('view-upcoming');
    const viewPast = document.getElementById('view-past');

    if (btnUpcoming && btnPast && viewUpcoming && viewPast) {
        
        const switchTab = (isUpcomingActive) => {
            btnUpcoming.classList.toggle('active', isUpcomingActive);
            btnPast.classList.toggle('active', !isUpcomingActive);

            // PERBAIKAN: Gunakan string kosong ('') agar layout mengikuti file CSS asli
            viewUpcoming.style.display = isUpcomingActive ? '' : 'none';
            viewPast.style.display = isUpcomingActive ? 'none' : '';
        };

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