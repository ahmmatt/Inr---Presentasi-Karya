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
document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. FITUR DROPDOWN VISIBILITY (PUBLIC/PRIVATE)
    // ==========================================
    const visibilityToggle = document.getElementById("visibility-toggle");
    const visibilityDropdown = document.getElementById("visibility-dropdown");
    const visibilityText = document.getElementById("visibility-text");
    const visibilityIcon = document.getElementById("visibility-icon");
    const visibilityArrow = document.getElementById("visibility-arrow");
    const options = document.querySelectorAll(".visibility-option");

    // Ketika tombol Public ditekan
    visibilityToggle.addEventListener("click", function(e) {
        e.stopPropagation(); // Mencegah body menyembunyikan dropdown ini secara langsung
        visibilityDropdown.classList.toggle("show"); // Munculkan/Hilangkan menu
        
        // Putar panah
        if (visibilityDropdown.classList.contains("show")) {
            visibilityArrow.style.transform = "rotate(180deg)";
        } else {
            visibilityArrow.style.transform = "rotate(0deg)";
        }
    });

    // Ketika opsi (Public/Private) dipilih
    options.forEach(option => {
        option.addEventListener("click", function() {
            // Ambil data yang ada di HTML (data-value dan data-icon)
            const selectedValue = this.getAttribute("data-value");
            const selectedIcon = this.getAttribute("data-icon");

            // Ubah teks dan icon di tombol utama
            visibilityText.innerText = selectedValue;
            visibilityIcon.className = `fa-solid ${selectedIcon}`;

            // Sembunyikan dropdown setelah memilih
            visibilityDropdown.classList.remove("show");
            visibilityArrow.style.transform = "rotate(0deg)";
        });
    });

    // Sembunyikan dropdown jika user mengklik bagian luar kotak
    window.addEventListener("click", function(e) {
        if (!visibilityToggle.contains(e.target)) {
            visibilityDropdown.classList.remove("show");
            visibilityArrow.style.transform = "rotate(0deg)";
        }
    });


    // ==========================================
    // 2. FITUR SWITCH (REQUIRE APPROVAL)
    // ==========================================
    const approvalSwitch = document.getElementById("approval-switch");

    approvalSwitch.addEventListener("click", function() {
        // Menambah & menghapus class 'active' agar CSS memicu warnanya (Abu abu <-> Hijau)
        approvalSwitch.classList.toggle("active");
    });


    // ==========================================
    // 3. FITUR LOKASI (MAPS / LINK)
    // ==========================================
    const locTrigger = document.getElementById("btn-toggle-loc");
    const locExpandArea = document.getElementById("loc-expand-area");
    const locArrow = document.querySelector(".loc-arrow");

    if (locTrigger) {
        locTrigger.addEventListener("click", function() {
            locExpandArea.classList.toggle("show");
            
            if (locExpandArea.classList.contains("show")) {
                locArrow.style.transform = "rotate(180deg)";
            } else {
                locArrow.style.transform = "rotate(0deg)";
            }
        });
    }

    // Mengambil elemen select dan input
    const payoutMethod = document.getElementById('payout_method');
    const payoutAccount = document.getElementById('payout_account');

    if (payoutMethod && payoutAccount) {
        // Mendengarkan perubahan pada dropdown Payout Method
        payoutMethod.addEventListener('change', function() {
            const selectedValue = this.value;

            // Logika perubahan placeholder
            if (selectedValue === "DANA" || selectedValue === "OVO" || selectedValue === "GoPay") {
                // Jika memilih E-Wallet
                payoutAccount.placeholder = "Example: 08123456789";
            } else if (selectedValue === "") {
                // Jika kembali ke pilihan default
                payoutAccount.placeholder = "Select method first...";
            } else {
                // Jika memilih Bank (BCA, Mandiri, BNI, BRI)
                payoutAccount.placeholder = "Example: 1234567890 (Bank Account Number)";
            }
        });
    }

});
    // ==========================================
    // 5. FITUR MENU KAPASITAS (UNLIMITED/LIMITED)
    // ==========================================
    const capacityTrigger = document.getElementById("capacity-trigger");
    const capacityDropdown = document.getElementById("capacity-dropdown");
    const capacityDisplay = document.getElementById("capacity-display");
    const capTypeRadios = document.querySelectorAll('input[name="cap_type"]');
    const capNumContainer = document.getElementById("capacity-number-container");
    const capAmountInput = document.getElementById("cap_amount");
    const applyCapacityBtn = document.getElementById("apply-capacity");
    const closeCapacityBtn = document.getElementById("close-capacity-modal");

    if (capacityTrigger && capacityDropdown) {
        // Buka tutup menu saat tulisan "Unlimited" atau ikon Pen diklik
        capacityTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            capacityDropdown.classList.toggle("show");
        });

        // Pantau perubahan pada pilihan Radio (Bebas vs Atur Kursi)
        capTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                // Jika pilih "Limited", munculkan input angka
                if (this.value === "Limited") {
                    capNumContainer.style.display = "flex";
                    capAmountInput.focus(); // Langsung arahkan kursor ke input
                } else {
                    // Jika "Unlimited", sembunyikan input angka
                    capNumContainer.style.display = "none";
                }
            });
        });


        // Tombol X (Tutup Modal Kapasitas)
        if (closeCapacityBtn) {
            closeCapacityBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                capacityDropdown.classList.remove("show");
            });
        }   

        // Ketika tombol "Terapkan" ditekan
        applyCapacityBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            
            // Cari tahu opsi utama mana yang sedang dipilih
            const selectedType = document.querySelector('input[name="cap_type"]:checked').value;
            
            if (selectedType === "Unlimited") {
                capacityDisplay.value = "Unlimited";
            } else {
                // Ambil nilai angka jumlah kursi
                const amount = capAmountInput.value;
                
                // Ambil nilai tipe kursi (Bebas / Pilih)
                const seatType = document.querySelector('input[name="seat_type"]:checked').value;
                const seatText = seatType === "Bebas" ? "Bebas" : "Pilih Kursi";

                // Format teks untuk ditampilkan ke layar utama
                if (amount && amount > 0) {
                    capacityDisplay.value = `${amount} Seats (${seatText})`;
                } else {
                    capacityDisplay.value = `Limited (${seatText})`; 
                }
            }
            
            // Tutup menu dropdown setelah selesai
            capacityDropdown.classList.remove("show");
        });

        // Menutup menu jika klik sembarang tempat di luar menu
        window.addEventListener("click", function(e) {
            if (!capacityTrigger.contains(e.target) && !capacityDropdown.contains(e.target)) {
                capacityDropdown.classList.remove("show");
            }
        });
    }
    // ==========================================
    // 6. FITUR MENU TICKET PRICE (FREE / PAID)
    // ==========================================
    const ticketTrigger = document.getElementById("ticket-trigger");
    const ticketDropdown = document.getElementById("ticket-dropdown");
    const ticketDisplay = document.getElementById("ticket-display");
    const ticketTypeRadios = document.querySelectorAll('input[name="ticket_type"]');
    const paymentContainer = document.getElementById("payment-details-container");
    const cancelTicketBtn = document.getElementById("cancel-ticket");
    const applyTicketBtn = document.getElementById("apply-ticket");
    const closeTicketBtn = document.getElementById("close-ticket-modal");
    const priceInput = document.getElementById("ticket_price_input");

    if (ticketTrigger && ticketDropdown) {
        // Buka menu saat diklik
        ticketTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            ticketDropdown.classList.toggle("show");
        });

        // Pantau pilihan Radio (Gratis vs Berbayar)
        ticketTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "Paid") {
                    // Munculkan form input rekening dan harga
                    paymentContainer.style.display = "flex";
                } else {
                    // Sembunyikan form
                    paymentContainer.style.display = "none";
                }
            });
        });

        // Tombol X (Tutup Modal Tiket)
        if (closeTicketBtn) {
            closeTicketBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                ticketDropdown.classList.remove("show");
            });
        }

        // Tombol Batal
        cancelTicketBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            ticketDropdown.classList.remove("show");
        });

        // Tombol Simpan
        applyTicketBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            
            const selectedType = document.querySelector('input[name="ticket_type"]:checked').value;
            
            if (selectedType === "Free") {
                ticketDisplay.value = "Free";
            } else {
                const price = priceInput.value;
                if (price && price > 0) {
                    // Otomatis format angka menjadi Rupiah (contoh: Rp 50.000)
                    const formattedPrice = new Intl.NumberFormat('id-ID', { 
                        style: 'currency', 
                        currency: 'IDR', 
                        minimumFractionDigits: 0 
                    }).format(price);
                    
                    ticketDisplay.value = formattedPrice;
                } else {
                    ticketDisplay.value = "Paid (Set Price)";
                }
            }
            
            ticketDropdown.classList.remove("show"); // Tutup menu setelah simpan
        });

        // Tutup jika klik area kosong di luar dropdown
        window.addEventListener("click", function(e) {
            if (!ticketTrigger.contains(e.target) && !ticketDropdown.contains(e.target)) {
                ticketDropdown.classList.remove("show");
            }
        });
    }