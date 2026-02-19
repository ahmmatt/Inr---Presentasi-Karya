document.addEventListener("DOMContentLoaded", function() {
    // Ambil elemen
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnPast = document.getElementById('btn-past');
    const viewUpcoming = document.getElementById('view-upcoming');
    const viewPast = document.getElementById('view-past');

    // Fungsi saat tombol Upcoming ditekan
    btnUpcoming.addEventListener('click', function(e) {
        e.preventDefault();
            
        // Ganti style tombol
        btnUpcoming.classList.add('active');
        btnPast.classList.remove('active');

        // Ganti tampilan konten
        viewUpcoming.style.display = 'flex'; // Munculkan empty state
        viewPast.style.display = 'none';     // Sembunyikan list
    });

    // Fungsi saat tombol Past ditekan
    btnPast.addEventListener('click', function(e) {
        e.preventDefault();

        // Ganti style tombol
        btnPast.classList.add('active');
        btnUpcoming.classList.remove('active');

        // Ganti tampilan konten
        viewUpcoming.style.display = 'none';
        viewPast.style.display = 'flex'; // Munculkan mode list timeline
    });
});
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