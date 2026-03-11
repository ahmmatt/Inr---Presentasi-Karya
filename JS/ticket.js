document.addEventListener("DOMContentLoaded", () => {
    
    /* =========================================
       1. DROPDOWN NAVBAR & PROFILE
       ========================================= */
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        // Toggle menu saat trigger diklik
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Tutup menu otomatis saat klik di luar area menu
        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }

    // ==========================================
    // 2. LOGIKA NAVBAR EFEK KACA (BLUR ON SCROLL)
    // ==========================================
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }


    /* =========================================
       2. PEMILIHAN TIKET (TIER SELECTION)
       ========================================= */
    const ticketCards = document.querySelectorAll(".ticket-option-card");
    const selectedTierInput = document.getElementById("selected-tier-id");
    let currentPrice = 0;

    if (ticketCards.length > 0) {
        ticketCards.forEach(card => {
            card.addEventListener("click", function() {
                // Hapus class aktif dari semua card
                ticketCards.forEach(c => c.classList.remove("active-card"));
                
                // Tambahkan class aktif ke card yang dipilih
                this.classList.add("active-card");
                
                // Update hidden input dan variabel harga
                if (selectedTierInput) {
                    selectedTierInput.value = this.getAttribute("data-id");
                }
                currentPrice = parseInt(this.getAttribute("data-price")) || 0;
                
                // Jika kamu punya teks harga dinamis di UI, bisa diupdate di sini:
                // const dynamicPriceText = document.getElementById("dynamic-payment-price");
                // if(dynamicPriceText) dynamicPriceText.innerText = currentPrice.toLocaleString();
            });
        });
    }


    /* =========================================
       3. MODAL REGISTRASI & CHECKOUT
       ========================================= */
    const openModalBtn = document.getElementById("open-register-modal");
    const closeModalBtn = document.getElementById("close-register-modal");
    const registerModal = document.getElementById("register-modal");
    const modalOverlay = document.getElementById("modal-overlay");
    const step1Form = document.getElementById("step-1-form");
    const step2Payment = document.getElementById("step-2-payment");
    const btnToPayment = document.getElementById("btn-to-payment");

    // Fungsi Tutup Modal
    const closeModal = () => {
        if (registerModal) registerModal.style.display = "none";
        if (modalOverlay) modalOverlay.style.display = "none";
        document.body.style.overflow = "auto"; // Aktifkan scroll kembali
        
        // Reset tampilan ke Step 1 (jika modal menggunakan sistem multi-step)
        if (step1Form) step1Form.classList.remove("hidden-step");
        if (step2Payment) step2Payment.classList.add("hidden-step");
    };

    // Event Buka Modal
    if (openModalBtn) {
        openModalBtn.addEventListener("click", (e) => {
            e.preventDefault();
            
            // Validasi: Harus pilih tiket dulu
            if (!selectedTierInput || !selectedTierInput.value) {
                alert("Please select a ticket category first!");
                return;
            }
            
            if (registerModal && modalOverlay) {
                registerModal.style.display = "block";
                modalOverlay.style.display = "block";
                document.body.style.overflow = "hidden"; // Kunci scroll latar belakang
            }
        });
    }

    // Event Tutup Modal
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (modalOverlay) modalOverlay.addEventListener("click", closeModal);


    /* =========================================
       4. PROSES SUBMIT KE PEMBAYARAN
       ========================================= */
    if (btnToPayment) {
        btnToPayment.addEventListener("click", () => {
            const form = document.getElementById("checkout-form");
            
            if (!form) return;

            // Validasi kelengkapan form HTML5
            if (!form.reportValidity()) {
                return;
            }

            // Menambahkan element button secara dinamis untuk men-trigger submit PHP
            const submitBtn = document.createElement("button");
            submitBtn.type = "submit";
            submitBtn.name = "buy_ticket";
            submitBtn.style.display = "none";
            form.appendChild(submitBtn);
            
            // Eksekusi submit
            submitBtn.click();
        });
    }

});