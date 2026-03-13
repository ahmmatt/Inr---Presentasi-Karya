document.addEventListener("DOMContentLoaded", function() {
    
    // 1. SCROLL NAVBAR
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    // 2. TAB SWITCHING LOGIC
    const tabLinks = document.querySelectorAll('.tab-link');
    const sections = document.querySelectorAll('.settings-section');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            tabLinks.forEach(t => t.classList.remove('active'));
            sections.forEach(s => {
                s.classList.remove('active');
                s.classList.add('hidden');
            });

            this.classList.add('active');
            const targetId = this.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);
            targetSection.classList.add('active');
            targetSection.classList.remove('hidden');
        });
    });

    // 3. THEME TOGGLE SWITCH LOGIC
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    if (localStorage.getItem('securegate_theme') === 'light') {
        body.classList.add('light-mode');
        if (themeToggle) themeToggle.classList.add('active');
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            if (this.classList.contains('active')) {
                body.classList.add('light-mode');
                localStorage.setItem('securegate_theme', 'light');
            } else {
                body.classList.remove('light-mode');
                localStorage.setItem('securegate_theme', 'dark');
            }
        });
    }

    // 4. LOGIKA MODAL POP-UPS
    const modalOverlay = document.getElementById("settings-modal-overlay");
    
    // Trigger buka modal
    document.querySelectorAll(".open-modal-trigger").forEach(trigger => {
        trigger.addEventListener("click", function() {
            const modalId = this.getAttribute("data-modal");
            openModal(modalId);
        });
    });

    function openModal(modalId) {
        modalOverlay.classList.add("show");
        document.getElementById(modalId).classList.add("show");
        document.body.style.overflow = "hidden";
    }

    // Trigger tutup modal
    document.querySelectorAll(".close-modal-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const modal = this.closest(".edit-modal");
            closeModal(modal);
        });
    });

    function closeModal(modalElement) {
        modalOverlay.classList.remove("show");
        modalElement.classList.remove("show");
        document.body.style.overflow = "auto";
    }

    // 5. PREVIEW GAMBAR
    const fileInput = document.getElementById('profile-upload');
    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('preview-img');
                const placeholder = document.getElementById('preview-placeholder');
                output.src = reader.result;
                output.classList.remove('hidden');
                output.style.display = 'block';
                if (placeholder) placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(event.target.files[0]);
        });
    }

    // 6. DROPDOWN NAVBAR PROFILE
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }

    // 7. LOGIKA HAMBURGER MENU (MOBILE)
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mainNav = document.querySelector('.main-nav');

    if (hamburgerBtn && mainNav) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Mencegah klik bocor
            mainNav.classList.toggle('active');
            
            // Ubah icon dari garis tiga (bars) menjadi X (close)
            if (mainNav.classList.contains('active')) {
                hamburgerBtn.classList.remove('fa-bars');
                hamburgerBtn.classList.add('fa-xmark');
            } else {
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });

        // Tutup menu otomatis jika user klik area kosong di luar sidebar
        document.addEventListener('click', (e) => {
            if (!hamburgerBtn.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('active');
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });
    }
});