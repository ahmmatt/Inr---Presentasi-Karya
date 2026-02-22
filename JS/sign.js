// Menunggu sampai seluruh elemen HTML selesai dimuat
document.addEventListener("DOMContentLoaded", function() {
    
    // Mengambil elemen navbar
    const navbar = document.querySelector('.navbar');

    // Memantau event 'scroll' pada halaman
    window.addEventListener('scroll', function() {
        
        // Jika halaman di-scroll lebih dari 50 pixel ke bawah
        if (window.scrollY > 50) {
            // Tambahkan class 'scrolled' (navbar jadi blur)
            navbar.classList.add('scrolled');
        } else {
            // Jika kembali ke paling atas, hapus class 'scrolled' (navbar jadi transparan lagi)
            navbar.classList.remove('scrolled');
        }
        
    });

});

// 1. Ambil semua elemen yang dibutuhkan
const toggleBtn = document.getElementById('toggle-mode');
const labelText = document.getElementById('dynamic-label');
const inputEmail = document.getElementById('input-email');
const inputPhone = document.getElementById('input-phone');

// 2. Tambahkan Event Listener saat teks diklik
toggleBtn.addEventListener('click', function() {
        
    // Cek apakah Input Phone sedang tersembunyi?
    const isPhoneHidden = inputPhone.classList.contains('hidden');

    if (isPhoneHidden) {
        // === MODE: GANTI KE PHONE NUMBER ===
                
        // Tampilan: Sembunyikan Email, Munculkan Phone
        inputEmail.classList.add('hidden');
        inputPhone.classList.remove('hidden');

        // Logika Required: Pindahkan 'required' ke Phone
        inputEmail.removeAttribute('required'); // Email jadi tidak wajib
        inputPhone.setAttribute('required', ''); // Phone jadi wajib

        // Ubah Teks Label & Tombol
        labelText.innerText = "Phone Number";
        toggleBtn.innerHTML = '<i class="fa-solid fa-envelope"></i> Use Email';
                
        // Otomatis fokus ke input phone agar user langsung ngetik
        inputPhone.focus();

    } else {
        // === MODE: GANTI KEMBALI KE EMAIL ===

        // Tampilan: Sembunyikan Phone, Munculkan Email
        inputPhone.classList.add('hidden');
        inputEmail.classList.remove('hidden');

        // Logika Required: Pindahkan 'required' ke Email
        inputPhone.removeAttribute('required');
        inputEmail.setAttribute('required', '');

        // Ubah Teks Label & Tombol
        labelText.innerText = "Email";
        toggleBtn.innerHTML = '<i class="fa-solid fa-mobile-screen"></i> Use Phone Number';
                
        inputEmail.focus();
    }
});
