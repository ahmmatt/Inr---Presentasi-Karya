document.addEventListener("DOMContentLoaded", function() {
    
    // 1. SMART NAVBAR (Blur + Hide on Scroll Down)
    const navbar = document.getElementById('navbar');
    const scrollContainer = document.querySelector('.scroll-container');
    let lastScrollTop = 0; // Menyimpan posisi scroll terakhir

    scrollContainer.addEventListener('scroll', function() {
        // Ambil posisi scroll saat ini
        let scrollTop = scrollContainer.scrollTop;

        // A. Logika Efek Blur (sama seperti sebelumnya)
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // B. LOGIKA BARU: Sembunyikan/Munculkan Navbar
        // Jika scroll ke BAWAH DAN sudah melewati ambang batas tertentu (misal 100px)
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Tambahkan class untuk menyembunyikan
            navbar.classList.add('navbar--hidden');
        } else {
            // Jika scroll ke ATAS, hapus class agar muncul kembali
            navbar.classList.remove('navbar--hidden');
        }
        
        // Update posisi scroll terakhir untuk perbandingan berikutnya
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile or negative scrolling
    });

    // ... (Sisa kode Intersection Observer untuk animasi tetap sama) ...
    // ...

    // 2. Mesin Animasi Premium (Intersection Observer)
    // Mencari semua elemen yang memiliki class fade-up atau fade-in
    const animatedElements = document.querySelectorAll('.fade-up, .fade-in');

    const observerOptions = {
        root: scrollContainer, // Menggunakan scroll container sebagai referensi batas layar
        threshold: 0.3,        // Animasi terpicu saat 30% elemen terlihat di layar (terasa lebih pas)
        rootMargin: "0px"
    };

    const scrollObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Menambahkan class 'show' yang berisi properti CSS transisi kita yang lambat
                entry.target.classList.add('show');
                
                // Matikan observasi agar animasi tidak mengulang dari awal jika discroll naik-turun
                // Ini mempertahankan kesan bahwa layar tersebut sudah "solid" dimuat
                observer.unobserve(entry.target); 
            }
        });
    }, observerOptions);

    // Menerapkan observer ke setiap elemen
    animatedElements.forEach(el => {
        scrollObserver.observe(el);
    });

    // 3. Trigger otomatis untuk Hero Section
    // Hero section selalu terlihat pertama kali, jadi kita paksa nyala setelah jeda sangat singkat
    setTimeout(() => {
        const heroElements = document.querySelectorAll('.hero-section .fade-up, .hero-section .fade-in');
        heroElements.forEach(el => el.classList.add('show'));
    }, 100);

});