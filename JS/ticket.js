document.addEventListener("DOMContentLoaded", () => {
    
    const ticketCards = document.querySelectorAll(".ticket-option-card");
    const selectedTierInput = document.getElementById("selected-tier-id");
    
    const openModalBtn = document.getElementById("open-register-modal");
    const closeModalBtn = document.getElementById("close-register-modal");
    const registerModal = document.getElementById("register-modal");
    const modalOverlay = document.getElementById("modal-overlay");

    const step1Form = document.getElementById("step-1-form");
    const step2Payment = document.getElementById("step-2-payment");
    const btnToPayment = document.getElementById("btn-to-payment");
    const dynamicPriceText = document.getElementById("dynamic-payment-price");

    let currentPrice = 0;

    // 1. Logika Pemilihan Tiket
    ticketCards.forEach(card => {
        card.addEventListener("click", function() {
            // Hapus efek aktif dari semua card
            ticketCards.forEach(c => c.classList.remove("active-card"));
            // Tambah efek aktif ke card yang diklik
            this.classList.add("active-card");
            
            // Simpan ID tiket ke input hidden untuk dikirim ke PHP
            selectedTierInput.value = this.getAttribute("data-id");
            currentPrice = parseInt(this.getAttribute("data-price"));
        });
    });

    // 2. Buka Modal
    openModalBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (!selectedTierInput.value) {
            alert("Please select a ticket category first!");
            return;
        }
        registerModal.style.display = "block";
        modalOverlay.style.display = "block";
        document.body.style.overflow = "hidden"; // Kunci scroll
    });

    // 3. Tutup Modal
    const closeModal = () => {
        registerModal.style.display = "none";
        modalOverlay.style.display = "none";
        document.body.style.overflow = "auto";
        // Reset kembali ke step 1
        step1Form.classList.remove("hidden-step");
        step2Payment.classList.add("hidden-step");
    };

    closeModalBtn.addEventListener("click", closeModal);
    modalOverlay.addEventListener("click", closeModal);

    // 4. Lanjut ke Pembayaran (Kirim ke PHP untuk diubah jadi Snap Token Midtrans)
    btnToPayment.addEventListener("click", () => {
        const form = document.getElementById("checkout-form");
        
        // Cek apakah data form sudah lengkap
        if (!form.reportValidity()) {
            return;
        }

        // Jika harga 0 (Gratis) atau berbayar, langsung paksa form untuk Submit!
        // PHP akan mengambil alih, menyimpan ke database, dan memanggil Popup Midtrans
        const submitBtn = document.createElement("button");
        submitBtn.type = "submit";
        submitBtn.name = "buy_ticket";
        submitBtn.style.display = "none";
        form.appendChild(submitBtn);
        submitBtn.click();
    });

});