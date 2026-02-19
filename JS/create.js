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