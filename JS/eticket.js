document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. FUNGSI AUTO CHECK-IN VIRTUAL EVENT
    // ==========================================
    const btnJoinVirtual = document.querySelector('.btn-join-virtual');
    
    // Periksa apakah tombolnya ada di halaman (saat ini)
    if (btnJoinVirtual) {
        btnJoinVirtual.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah reload halaman bawaan
            
            // 1. Ambil data ID dan Link dari atribut HTML yang disuntikkan PHP
            const attendeeId = this.getAttribute('data-id');
            const meetLink = this.getAttribute('data-link');
            
            // 2. Validasi Keamanan: Pastikan data tidak kosong
            if (!attendeeId || !meetLink) {
                alert("Data tiket tidak valid atau tautan rusak.");
                return;
            }

            // 3. Buka link Zoom/Meet di tab baru SEKARANG
            window.open(meetLink, '_blank');

            // 4. Kirim sinyal diam-diam ke server untuk mengubah status menjadi 'checked_in'
            let formData = new FormData();
            formData.append('action', 'auto_checkin');
            formData.append('attendee_id', attendeeId);

            fetch('eticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // 5. Refresh halaman agar E-ticket berubah jadi "This is your ticket" & tombol hilang
                    location.reload();
                }
            })
            .catch(err => {
                console.error('Gagal auto check-in:', err);
                // Jika error jaringan, minimal user tetap sudah masuk Google Meet
            });
        });
    }

    // ==========================================
    // 2. SCROLL NAVBAR
    // ==========================================
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ==========================================
    // 3. LOGIKA DROPDOWN PROFILE
    // ==========================================
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        // Klik foto profil: Munculkan/Sembunyikan
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            if (profileMenu.style.display === 'block') {
                profileMenu.style.display = 'none';
            } else {
                profileMenu.style.display = 'block';
            }
        });

        // Klik area mana saja di layar: Tutup menu
        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }
});