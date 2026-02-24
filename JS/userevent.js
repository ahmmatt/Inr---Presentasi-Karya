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

            // Hapus class active dari semua tab dan section
            tabLinks.forEach(t => t.classList.remove('active'));
            sections.forEach(s => s.style.display = 'none');

            // Tambahkan class active ke tab yang diklik
            this.classList.add('active');

            // Tampilkan section yang sesuai dengan data-target
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // ==========================================
    // 3. THEME TOGGLE SWITCH LOGIC (Real Functionality)
    // ==========================================
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // A. Saat halaman dimuat, cek apakah user sebelumnya memilih Light Mode
    if (localStorage.getItem('securegate_theme') === 'light') {
        body.classList.add('light-mode');
        if (themeToggle) {
            themeToggle.classList.add('active');
        }
    }

    // B. Logika ketika tombol switch ditekan
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            // Ubah animasi tombol switch (nyala/mati)
            this.classList.toggle('active');
            
            if (this.classList.contains('active')) {
                // Jika aktif, ubah ke Light Mode dan simpan memori
                body.classList.add('light-mode');
                localStorage.setItem('securegate_theme', 'light');
            } else {
                // Jika tidak aktif, kembalikan ke Dark Mode dan simpan memori
                body.classList.remove('light-mode');
                localStorage.setItem('securegate_theme', 'dark');
            }
        });
    }

    // ==========================================
    // 4. LOGIKA MODAL POP-UPS (EMAIL, PHONE, PASSWORD)
    // ==========================================
    const modalOverlay = document.getElementById("settings-modal-overlay");
    const modalEmail = document.getElementById("modal-add-email");
    const modalPhone = document.getElementById("modal-add-phone");
    const modalPassword = document.getElementById("modal-set-password");

    // Fungsi untuk membuka modal spesifik
    function openModal(modalElement) {
        modalOverlay.classList.add("show");
        modalElement.classList.add("show");
        document.body.style.overflow = "hidden"; // Mengunci scroll layar belakang
    }

    // Fungsi untuk menutup semua modal
    function closeModals() {
        modalOverlay.classList.remove("show");
        modalEmail.classList.remove("show");
        modalPhone.classList.remove("show");
        modalPassword.classList.remove("show");
        document.body.style.overflow = "auto";
        
        // Bersihkan isi input saat ditutup
        document.querySelectorAll(".edit-modal input").forEach(input => input.value = "");
    }

    // Event Listener untuk Tombol Buka Modal
    document.getElementById("btn-add-email")?.addEventListener("click", () => openModal(modalEmail));
    document.getElementById("btn-add-phone")?.addEventListener("click", () => openModal(modalPhone));
    document.getElementById("btn-set-password")?.addEventListener("click", () => openModal(modalPassword));

    // Event Listener untuk Tombol Tutup/Cancel
    // modalOverlay.addEventListener("click", closeModals); 
    
    document.querySelectorAll(".close-modal-btn").forEach(btn => {
        btn.addEventListener("click", closeModals);
    });

    // Event Listener untuk Tombol Save (Konfirmasi)
    // 1. Simpan Email Baru
    document.getElementById("save-email-btn")?.addEventListener("click", function() {
        const newEmail = document.getElementById("input-new-email").value;
        if (newEmail.trim() !== "") {
            document.getElementById("display-email-text").innerText = newEmail;
            closeModals();
        }
    });

    // 2. Simpan Phone Baru
    document.getElementById("save-phone-btn")?.addEventListener("click", function() {
        const newPhone = document.getElementById("input-new-phone").value;
        if (newPhone.trim() !== "") {
            document.getElementById("display-phone-text").innerText = newPhone;
            closeModals();
        }
    });

    // 3. Simpan Password Baru
    document.getElementById("save-password-btn")?.addEventListener("click", function() {
        const pass1 = document.getElementById("input-new-pass").value;
        const pass2 = document.getElementById("input-confirm-pass").value;
        
        if (pass1 !== "" && pass1 === pass2) {
            alert("Password successfully updated!");
            closeModals();
        } else {
            alert("Passwords do not match. Please try again.");
        }
    });

});