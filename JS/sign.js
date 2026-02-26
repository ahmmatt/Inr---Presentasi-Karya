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

    // ==========================================
    // 2. TOGGLE INPUT EMAIL / PHONE NUMBER
    // ==========================================
    const toggleBtn = document.getElementById('toggle-mode');
    const labelText = document.getElementById('dynamic-label');
    const inputEmail = document.getElementById('input-email');
    const inputPhone = document.getElementById('input-phone');

    // Pengecekan Keamanan: Pastikan semua elemen form ini ada di halaman
    if (toggleBtn && labelText && inputEmail && inputPhone) {
        
        toggleBtn.addEventListener('click', (e) => {
            // Mencegah tombol secara tidak sengaja men-submit form/merefresh halaman
            e.preventDefault(); 
            
            // Cek apakah Input Phone sedang tersembunyi
            const isPhoneHidden = inputPhone.classList.contains('hidden');

            if (isPhoneHidden) {
                // === MODE: GANTI KE PHONE NUMBER ===
                inputEmail.classList.add('hidden');
                inputPhone.classList.remove('hidden');

                // Logika Required
                inputEmail.removeAttribute('required'); 
                inputPhone.setAttribute('required', 'true'); // Gunakan 'true' sebagai standard HTML

                // Ubah Teks Label & Tombol
                labelText.innerText = "Phone Number";
                toggleBtn.innerHTML = '<i class="fa-solid fa-envelope"></i> Use Email';
                
                // Fokus otomatis
                inputPhone.focus();

            } else {
                // === MODE: GANTI KEMBALI KE EMAIL ===
                inputPhone.classList.add('hidden');
                inputEmail.classList.remove('hidden');

                // Logika Required
                inputPhone.removeAttribute('required');
                inputEmail.setAttribute('required', 'true');

                // Ubah Teks Label & Tombol
                labelText.innerText = "Email";
                toggleBtn.innerHTML = '<i class="fa-solid fa-mobile-screen"></i> Use Phone Number';
                
                // Fokus otomatis
                inputEmail.focus();
            }
        });
    }

});