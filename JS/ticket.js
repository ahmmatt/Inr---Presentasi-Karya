document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. ELEMEN MODAL REGISTRASI ---
    const registerBtn = document.getElementById("open-register-modal");
    const registerModal = document.getElementById("register-modal");
    const closeRegisterModalBtn = document.getElementById("close-register-modal");
    
    // --- 2. ELEMEN STEPS DALAM MODAL ---
    const step1Form = document.getElementById("step-1-form");
    const step2Payment = document.getElementById("step-2-payment");
    const step3Success = document.getElementById("step-3-success");
    
    // --- 3. ELEMEN FORM & TOMBOL ---
    const regForm = document.getElementById("registration-form");
    const btnPaymentDone = document.getElementById("btn-payment-done");
    const btnCloseSuccess = document.getElementById("btn-close-success");
    
    // --- 4. ELEMEN DINAMIS (TIKET & HARGA) ---
    const ticketCards = document.querySelectorAll(".ticket-option-card");
    const ticketSelectDropdown = document.getElementById("ticket-type-select");
    const dynamicPaymentPrice = document.getElementById("dynamic-payment-price");

    // ==========================================
    // LOGIKA 1: KLIK CARD HARGA DI LUAR MODAL
    // ==========================================
    // Ketika user mengklik card "Regular" atau "VIP" di halaman utama
    ticketCards.forEach(card => {
        card.addEventListener("click", function() {
            // Hapus seleksi sebelumnya dari semua card
            ticketCards.forEach(c => {
                c.classList.remove("selected-regular", "selected-vip");
            });

            // Tambahkan seleksi pada card yang diklik
            const ticketType = this.getAttribute("data-type");
            if (ticketType === "Regular") {
                this.classList.add("selected-regular");
            } else if (ticketType === "VIP") {
                this.classList.add("selected-vip");
            }

            // Otomatis ubah nilai dropdown di dalam modal sesuai card yang diklik
            ticketSelectDropdown.value = ticketType;

            // Buka Modal Registrasi
            openModalAndReset();
        });
    });

    // ==========================================
    // LOGIKA 2: BUKA/TUTUP MODAL
    // ==========================================
    // Buka modal via tombol "Register" biasa (tanpa klik card)
    if (registerBtn) {
        registerBtn.addEventListener("click", function(e) {
            e.preventDefault();
            openModalAndReset();
        });
    }

    // Fungsi khusus untuk mereset dan membuka modal ke Step 1
    function openModalAndReset() {
        registerModal.classList.add("show");
        document.body.style.overflow = "hidden"; // Kunci scroll background
        
        // Selalu mulai dari form awal
        step1Form.classList.remove("hidden-step");
        step2Payment.classList.add("hidden-step");
        step3Success.classList.add("hidden-step");
    }

    // Tutup Modal via Tombol X
    if (closeRegisterModalBtn) {
        closeRegisterModalBtn.addEventListener("click", function() {
            registerModal.classList.remove("show");
            document.body.style.overflow = "auto"; // Buka kunci scroll
        });
    }

    // ==========================================
    // LOGIKA 3: PROSES FORM -> PEMBAYARAN (STEP 1 ke 2)
    // ==========================================
    if (regForm) {
        regForm.addEventListener("submit", function(e) {
            e.preventDefault(); // Cegah halaman reload

            // AMBIL DATA DARI DROPDOWN TIKET
            const selectedOption = ticketSelectDropdown.options[ticketSelectDropdown.selectedIndex];
            
            // Validasi jika user belum milih tipe tiket
            if (selectedOption.value === "") {
                alert("Please select a ticket type first!");
                return;
            }

            const rawPrice = selectedOption.getAttribute("data-price");
            
            // Format angka menjadi Rupiah (contoh: Rp 150.000)
            const formattedPrice = new Intl.NumberFormat('id-ID', { 
                style: 'currency', 
                currency: 'IDR', 
                minimumFractionDigits: 0 
            }).format(rawPrice);

            // Masukkan harga ke instruksi nomor 3
            dynamicPaymentPrice.innerText = formattedPrice;
            
            // Sembunyikan Form, Tampilkan QRIS
            step1Form.classList.add("hidden-step");
            step2Payment.classList.remove("hidden-step");
        });
    }

    // ==========================================
    // LOGIKA 4: PEMBAYARAN -> SUKSES (STEP 2 ke 3)
    // ==========================================
    if (btnPaymentDone) {
        btnPaymentDone.addEventListener("click", function() {
            step2Payment.classList.add("hidden-step");
            step3Success.classList.remove("hidden-step");
        });
    }

    // ==========================================
    // LOGIKA 5: TUTUP MODAL SETELAH SUKSES
    // ==========================================
    if (btnCloseSuccess) {
        btnCloseSuccess.addEventListener("click", function() {
            registerModal.classList.remove("show");
            document.body.style.overflow = "auto";
            
            // Opsional: Reset form isian untuk pendaftaran berikutnya
            regForm.reset(); 
            ticketSelectDropdown.value = "";
            ticketCards.forEach(c => c.classList.remove("selected-regular", "selected-vip"));
        });
    }
    // ==========================================
    // LOGIKA 6: FITUR COPY LINK VIRTUAL
    // ==========================================
    const copyLinkBtn = document.getElementById("copy-link-btn");
    const virtualLinkText = document.getElementById("virtual-link-text");

    if (copyLinkBtn && virtualLinkText) {
        copyLinkBtn.addEventListener("click", function() {
            // Ambil teks dari dalam span
            const textToCopy = virtualLinkText.innerText;

            // Gunakan Clipboard API untuk menyalin teks
            navigator.clipboard.writeText(textToCopy).then(() => {
                
                // UBAH TAMPILAN IKON SEBAGAI FEEDBACK (Berhasil)
                // Hapus ikon copy lama
                copyLinkBtn.classList.remove("fa-regular", "fa-copy");
                // Tambahkan ikon centang (check) dan class warna hijau
                copyLinkBtn.classList.add("fa-solid", "fa-check", "copy-success");

                // Kembalikan ke ikon semula setelah 2 detik
                setTimeout(() => {
                    copyLinkBtn.classList.remove("fa-solid", "fa-check", "copy-success");
                    copyLinkBtn.classList.add("fa-regular", "fa-copy");
                }, 2000);

            }).catch(err => {
                console.error("Gagal menyalin teks: ", err);
                alert("Browser Anda tidak mendukung fitur copy otomatis.");
            });
        });
    }

});