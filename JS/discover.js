document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 0. AUTO GEOLOCATION (CARI KOTA TERDEKAT)
    // ==========================================
    
    // Daftar 8 kota beserta koordinat aslinya (Latitude, Longitude)
    const cityCoords = [
        { name: 'Jakarta', lat: -6.2088, lon: 106.8456 },
        { name: 'Bali', lat: -8.6500, lon: 115.2167 },
        { name: 'Bandung', lat: -6.9175, lon: 107.6191 },
        { name: 'Surabaya', lat: -7.2504, lon: 112.7688 },
        { name: 'Yogyakarta', lat: -7.7970, lon: 110.3705 },
        { name: 'Makassar', lat: -5.1477, lon: 119.4327 },
        { name: 'Medan', lat: 3.5952, lon: 98.6722 },
        { name: 'Semarang', lat: -6.9667, lon: 110.4167 }
    ];

    // Fungsi matematika (Haversine) untuk menghitung jarak antar 2 titik di bumi
    function getDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius bumi dalam kilometer
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c; // Hasil dalam kilometer
    }

    // Cek apakah user sudah punya Cookie kota
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // JIKA belum ada Cookie KOTA & tidak sedang mengeklik filter kota secara manual
    if (!getCookie("user_city") && !window.location.search.includes("city=")) {
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const userLat = position.coords.latitude;
                const userLon = position.coords.longitude;
                
                let nearestCity = 'Jakarta'; // Default awal
                let minDistance = Infinity;

                // Cari kota mana yang jaraknya paling kecil (terdekat)
                cityCoords.forEach(city => {
                    const dist = getDistance(userLat, userLon, city.lat, city.lon);
                    if (dist < minDistance) {
                        minDistance = dist;
                        nearestCity = city.name;
                    }
                });

                // Simpan kota terdekat ke dalam Cookie agar tidak melacak berulang kali
                document.cookie = `user_city=${nearestCity}; path=/; max-age=2592000`; // 30 Hari
                
                // Segarkan halaman dan otomatis terapkan filter kota terdekat!
                window.location.href = `discover.php?city=${nearestCity}`;
                
            }, (error) => {
                // Jika user menolak akses lokasi, setel ke "All" agar tidak ditanya lagi
                console.log("Izin lokasi ditolak.");
                document.cookie = "user_city=All; path=/; max-age=2592000";
            });
        }
    }


    // ==========================================
    // 1. LOGIKA SLIDER (UPCOMING EVENT)
    // ==========================================
    const track = document.querySelector('.upcoming-event');
    const leftBtn = document.getElementById('slide-left');
    const rightBtn = document.getElementById('slide-right');

    if (track && leftBtn && rightBtn) {
        const scrollAmount = 320; 

        const updateButtonState = () => {
            leftBtn.style.display = track.scrollLeft > 0 ? 'flex' : 'none';
            const maxScroll = track.scrollWidth - track.clientWidth;
            rightBtn.style.display = track.scrollLeft >= maxScroll - 5 ? 'none' : 'flex';
        };

        updateButtonState();

        rightBtn.addEventListener('click', () => {
            track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        leftBtn.addEventListener('click', () => {
            track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        track.addEventListener('scroll', updateButtonState);
        window.addEventListener('resize', updateButtonState);
    }

    // ==========================================
    // 2. LOGIKA NAVBAR EFEK KACA
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