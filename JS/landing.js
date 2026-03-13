document.addEventListener("DOMContentLoaded", function() {
    
    // 1. SMART NAVBAR (Blur + Hide on Scroll Down)
    const navbar = document.getElementById('navbar');
    let lastScrollTop = 0; 

    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Logika Efek Blur Background
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // Logika Sembunyikan Navbar saat scroll ke bawah
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            navbar.classList.add('hidden');
        } else {
            navbar.classList.remove('hidden');
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
    });

    // 2. INTERSECTION OBSERVER UNTUK ANIMASI SCROLL
    // Mendeteksi elemen dengan class animasi
    const animatedElements = document.querySelectorAll('.fade-up, .fade-left, .fade-right');

    const observerOptions = {
        threshold: 0.2, // Muncul ketika 20% elemen masuk layar
        rootMargin: "0px 0px -50px 0px"
    };

    const scrollObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                // Hentikan observasi setelah elemen muncul agar animasi tidak berulang
                observer.unobserve(entry.target); 
            }
        });
    }, observerOptions);

    animatedElements.forEach(el => {
        scrollObserver.observe(el);
    });

    // 3. TRIGGER OTOMATIS HERO SECTION SAAT LOAD
    setTimeout(() => {
        const heroElements = document.querySelectorAll('.hero-section .fade-up');
        heroElements.forEach(el => el.classList.add('show'));
    }, 100);

});