// Menunggu sampai seluruh elemen HTML selesai dimuat
document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // --- SCROLL NAVBAR ---
    // ==========================================
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ==========================================
    // 1. FITUR DROPDOWN VISIBILITY (PUBLIC/PRIVATE)
    // ==========================================
    const visibilityToggle = document.getElementById("visibility-toggle");
    const visibilityDropdown = document.getElementById("visibility-dropdown");
    const visibilityText = document.getElementById("visibility-text");
    const visibilityIcon = document.getElementById("visibility-icon");
    const visibilityArrow = document.getElementById("visibility-arrow");
    const options = document.querySelectorAll(".visibility-option");

    if (visibilityToggle && visibilityDropdown) {
        visibilityToggle.addEventListener("click", function(e) {
            e.stopPropagation(); 
            visibilityDropdown.classList.toggle("show"); 
            if (visibilityDropdown.classList.contains("show")) {
                visibilityArrow.style.transform = "rotate(180deg)";
            } else {
                visibilityArrow.style.transform = "rotate(0deg)";
            }
        });

        options.forEach(option => {
            option.addEventListener("click", function() {
                const selectedValue = this.getAttribute("data-value");
                const selectedIcon = this.getAttribute("data-icon");
                if(visibilityText) visibilityText.innerText = selectedValue;
                if(visibilityIcon) visibilityIcon.className = `fa-solid ${selectedIcon}`;
                visibilityDropdown.classList.remove("show");
                if(visibilityArrow) visibilityArrow.style.transform = "rotate(0deg)";
            });
        });

        window.addEventListener("click", function(e) {
            if (!visibilityToggle.contains(e.target)) {
                visibilityDropdown.classList.remove("show");
                if(visibilityArrow) visibilityArrow.style.transform = "rotate(0deg)";
            }
        });
    }

    // ==========================================
    // 2. FITUR SWITCH (REQUIRE APPROVAL)
    // ==========================================
    const approvalSwitch = document.getElementById("approval-switch");
    if (approvalSwitch) {
        approvalSwitch.addEventListener("click", function() {
            approvalSwitch.classList.toggle("active");
        });
    }

    // ==========================================
    // 3. FITUR LOKASI (MAPS / LINK)
    // ==========================================
    const locTrigger = document.getElementById("btn-toggle-loc");
    const locExpandArea = document.getElementById("loc-expand-area");
    const locArrow = document.querySelector(".loc-arrow");

    if (locTrigger && locExpandArea) {
        locTrigger.addEventListener("click", function() {
            locExpandArea.classList.toggle("show");
            if (locExpandArea.classList.contains("show")) {
                if(locArrow) locArrow.style.transform = "rotate(180deg)";
            } else {
                if(locArrow) locArrow.style.transform = "rotate(0deg)";
            }
        });
    }

    // ==========================================
    // 4. PAYOUT METHOD
    // ==========================================
    const payoutMethod = document.getElementById('payout_method');
    const payoutAccount = document.getElementById('payout_account');

    if (payoutMethod && payoutAccount) {
        payoutMethod.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue === "DANA" || selectedValue === "OVO" || selectedValue === "GoPay") {
                payoutAccount.placeholder = "Example: 08123456789";
            } else if (selectedValue === "") {
                payoutAccount.placeholder = "Select method first...";
            } else {
                payoutAccount.placeholder = "Example: 1234567890 (Bank Account Number)";
            }
        });
    }

    // ==========================================
    // 5. FITUR MENU KAPASITAS (UNLIMITED/LIMITED) + VIP SEAT
    // ==========================================
    const capacityTrigger = document.getElementById("capacity-trigger");
    const capacityDropdown = document.getElementById("capacity-dropdown");
    const capacityDisplay = document.getElementById("capacity-display");
    const capTypeRadios = document.querySelectorAll('input[name="cap_type"]');
    const capNumContainer = document.getElementById("capacity-number-container");
    const capAmountInput = document.getElementById("cap_amount");
    const applyCapacityBtn = document.getElementById("apply-capacity");
    const closeCapacityBtn = document.getElementById("close-capacity-modal");

    const seatTypeRadios = document.querySelectorAll('input[name="seat_type"]');
    const seatAllocationContainer = document.getElementById("seat-allocation-container");
    const seatTiersContainer = document.getElementById("seat-tiers-container");
    const addSeatTierBtn = document.getElementById("add-seat-tier-btn");

    if (capacityTrigger && capacityDropdown) {
        
        // Buka Menu & Kunci Scroll
        capacityTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            capacityDropdown.classList.toggle("show");
            if (capacityDropdown.classList.contains("show")) {
                document.body.classList.add("no-scroll");
            } else {
                document.body.classList.remove("no-scroll");
            }
        });

        // Pantau Pilihan Unlimited / Limited
        capTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "Limited") {
                    if(capNumContainer) capNumContainer.style.display = "flex";
                    if(capAmountInput) capAmountInput.focus();
                } else {
                    if(capNumContainer) capNumContainer.style.display = "none";
                }
            });
        });

        // Pantau Pilihan "Bebas" atau "Pilih Kursi"
        if(seatTypeRadios) {
            seatTypeRadios.forEach(radio => {
                radio.addEventListener("change", function() {
                    if (this.value === "Pilih") {
                        if(seatAllocationContainer) seatAllocationContainer.style.display = "flex";
                    } else {
                        if(seatAllocationContainer) seatAllocationContainer.style.display = "none";
                    }
                });
            });
        }

        // --- Logika Tambah Kursi VIP ---
        if (addSeatTierBtn && seatTiersContainer) {
            addSeatTierBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const rowCount = seatTiersContainer.querySelectorAll(".seat-tier-row").length;
                if (rowCount < 2) {
                    const newRow = document.createElement("div");
                    newRow.className = "seat-tier-row";
                    newRow.innerHTML = `
                        <input type="text" class="seat-tier-name custom-input" value="VIP" readonly required style="color: #eab308; font-weight: bold; cursor: not-allowed; border-color: rgba(234, 179, 8, 0.3);">
                        <input type="text" class="seat-tier-range custom-input" placeholder="e.g., 1-50" required>
                        <i class="fa-solid fa-trash-can remove-seat-tier-btn" title="Remove"></i>
                    `;
                    seatTiersContainer.appendChild(newRow);
                    updateRemoveSeatButtons(); 
                    addSeatTierBtn.style.display = "none";
                }
            });

            seatTiersContainer.addEventListener("click", function(e) {
                if(e.target.classList.contains("remove-seat-tier-btn") || e.target.closest(".remove-seat-tier-btn")) {
                    e.stopPropagation();
                    e.target.closest(".seat-tier-row").remove();
                    updateRemoveSeatButtons();
                    addSeatTierBtn.style.display = "block";
                }
            });

            function updateRemoveSeatButtons() {
                const rows = seatTiersContainer.querySelectorAll(".seat-tier-row");
                rows.forEach((row, index) => {
                    const btn = row.querySelector(".remove-seat-tier-btn");
                    if (btn) {
                        if (index === 0) {
                            btn.style.display = "none";
                        } else {
                            btn.style.display = "flex"; 
                        }
                    }
                });
            }
        }

        // Tombol X (Tutup Modal Kapasitas)
        if (closeCapacityBtn) {
            closeCapacityBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                capacityDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); // Buka scroll
            });
        }   

        // Tombol Apply Kapasitas
        if (applyCapacityBtn) {
            applyCapacityBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                
                const selectedTypeRadio = document.querySelector('input[name="cap_type"]:checked');
                if (!selectedTypeRadio) return; 
                
                const selectedType = selectedTypeRadio.value;
                
                if (selectedType === "Unlimited") {
                    if(capacityDisplay) capacityDisplay.value = "Unlimited";
                } else {
                    const amount = capAmountInput ? capAmountInput.value : "";
                    const seatTypeRadio = document.querySelector('input[name="seat_type"]:checked');
                    const seatType = seatTypeRadio ? seatTypeRadio.value : "Bebas";
                    const seatText = seatType === "Bebas" ? "Bebas" : "Pilih Kursi";

                    if (capacityDisplay) {
                        if (amount && amount > 0) {
                            capacityDisplay.value = `${amount} Seats (${seatText})`;
                        } else {
                            capacityDisplay.value = `Limited (${seatText})`; 
                        }
                    }
                }
                capacityDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); // Buka scroll
            });
        }
        
        // FUNGSI KLIK DI LUAR CARD SUDAH DIHAPUS DARI SINI
    }


    // ==========================================
    // 6. FITUR MENU TICKET PRICE + KATEGORI VIP
    // ==========================================
    const ticketTrigger = document.getElementById("ticket-trigger");
    const ticketDropdown = document.getElementById("ticket-dropdown");
    const ticketDisplay = document.getElementById("ticket-display");
    const ticketTypeRadios = document.querySelectorAll('input[name="ticket_type"]');
    const paymentContainer = document.getElementById("payment-details-container");
    const closeTicketBtn = document.getElementById("close-ticket-modal");
    const applyTicketBtn = document.getElementById("apply-ticket");
    
    const ticketTiersContainer = document.getElementById("ticket-tiers-container");
    const addTierBtn = document.getElementById("add-tier-btn");

    if (ticketTrigger && ticketDropdown) {
        
        // Buka Menu & Kunci Scroll
        ticketTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            ticketDropdown.classList.toggle("show");
            if (ticketDropdown.classList.contains("show")) {
                document.body.classList.add("no-scroll");
            } else {
                document.body.classList.remove("no-scroll");
            }
        });

        // Pantau Free / Paid
        ticketTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "Paid") {
                    if(paymentContainer) paymentContainer.style.display = "flex"; 
                } else {
                    if(paymentContainer) paymentContainer.style.display = "none"; 
                }
            });
        });

        // --- Logika Tambah Tiket VIP ---
        if (addTierBtn && ticketTiersContainer) {
            addTierBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const rowCount = ticketTiersContainer.querySelectorAll(".ticket-tier-row").length;
                
                if (rowCount < 2) {
                    const newRow = document.createElement("div");
                    newRow.className = "ticket-tier-row";
                    newRow.innerHTML = `
                        <input type="text" class="tier-name custom-input" value="VIP" readonly required style="color: #eab308; font-weight: bold; cursor: not-allowed; border-color: rgba(234, 179, 8, 0.3);">
                        <input type="number" class="tier-price custom-input" placeholder="Price (Rp)" required>
                        <i class="fa-solid fa-trash-can remove-tier-btn" title="Remove"></i>
                    `;
                    ticketTiersContainer.appendChild(newRow);
                    updateRemoveButtons(); 
                    addTierBtn.style.display = "none";
                }
            });

            ticketTiersContainer.addEventListener("click", function(e) {
                if(e.target.classList.contains("remove-tier-btn") || e.target.closest(".remove-tier-btn")) {
                    e.stopPropagation();
                    e.target.closest(".ticket-tier-row").remove();
                    updateRemoveButtons();
                    addTierBtn.style.display = "block";
                }
            });

            function updateRemoveButtons() {
                const rows = ticketTiersContainer.querySelectorAll(".ticket-tier-row");
                rows.forEach((row, index) => {
                    const btn = row.querySelector(".remove-tier-btn");
                    if (btn) {
                        if (index === 0) {
                            btn.style.display = "none";
                        } else {
                            btn.style.display = "flex"; 
                        }
                    }
                });
            }
        }

        // Tombol X (Tutup Modal Tiket)
        if (closeTicketBtn) {
            closeTicketBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                ticketDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); // Buka scroll
            });
        }

        // Tombol Apply Tiket
        if (applyTicketBtn) {
            applyTicketBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                
                const selectedTypeRadio = document.querySelector('input[name="ticket_type"]:checked');
                if (!selectedTypeRadio) return;
                
                const selectedType = selectedTypeRadio.value;
                
                if (selectedType === "Free") {
                    if(ticketDisplay) ticketDisplay.value = "Free";
                } else {
                    const priceInputs = ticketTiersContainer ? ticketTiersContainer.querySelectorAll(".tier-price") : [];
                    let prices = [];
                    
                    priceInputs.forEach(input => {
                        let val = parseInt(input.value);
                        if (!isNaN(val)) prices.push(val);
                    });

                    if (ticketDisplay) {
                        if (prices.length > 0) {
                            const minPrice = Math.min(...prices);
                            const formattedPrice = new Intl.NumberFormat('id-ID', { 
                                style: 'currency', 
                                currency: 'IDR', 
                                minimumFractionDigits: 0 
                            }).format(minPrice);
                            
                            if (prices.length > 1) {
                                ticketDisplay.value = `${formattedPrice} (Start From)`;
                            } else {
                                ticketDisplay.value = formattedPrice;
                            }
                        } else {
                            ticketDisplay.value = "Paid (Set Price)";
                        }
                    }
                }
                ticketDropdown.classList.remove("show"); 
                document.body.classList.remove("no-scroll"); // Buka scroll
            });
        }

        // FUNGSI KLIK DI LUAR CARD SUDAH DIHAPUS DARI SINI
    }
    // ==========================================
    // 7. FITUR MENU REGISTRATION FORM (CUSTOM QUESTIONS)
    // ==========================================
    const questionTrigger = document.getElementById("question-trigger");
    const questionDropdown = document.getElementById("question-dropdown");
    const questionDisplay = document.getElementById("question-display");
    const closeQuestionBtn = document.getElementById("close-question-modal");
    const applyQuestionBtn = document.getElementById("apply-question");
    
    const questionsContainer = document.getElementById("questions-container");
    const addQuestionBtn = document.getElementById("add-question-btn");

    if (questionTrigger && questionDropdown) {
        
        // Buka Menu & Kunci Scroll
        questionTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            questionDropdown.classList.toggle("show");
            if (questionDropdown.classList.contains("show")) {
                document.body.classList.add("no-scroll");
            } else {
                document.body.classList.remove("no-scroll");
            }
        });

        // --- Logika Tambah Pertanyaan ---
        if (addQuestionBtn && questionsContainer) {
            addQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                
                // Buat elemen baris input baru
                const newRow = document.createElement("div");
                newRow.className = "question-row";
                newRow.innerHTML = `
                    <input type="text" class="custom-input" placeholder="e.g., What is your role?" required>
                    <i class="fa-solid fa-trash-can remove-question-btn" title="Remove"></i>
                `;
                questionsContainer.appendChild(newRow);
            });

            // Logika Menghapus Pertanyaan
            questionsContainer.addEventListener("click", function(e) {
                // Gunakan e.target.closest untuk memastikan klik di dalam kotak maupun langsung di ikonnya tetap terdeteksi
                const deleteBtn = e.target.closest(".remove-question-btn");
                if (deleteBtn) {
                    e.stopPropagation();
                    deleteBtn.closest(".question-row").remove();
                }
            });
        }

        // Tombol X (Tutup Modal)
        if (closeQuestionBtn) {
            closeQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                questionDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); 
            });
        }

        // Tombol Apply (Simpan Perubahan)
        if (applyQuestionBtn) {
            applyQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                
                // Hitung berapa banyak pertanyaan yang dibuat user
                const questionCount = questionsContainer.querySelectorAll(".question-row").length;
                
                // Ubah teks di layar utama
                if (questionCount > 0) {
                    questionDisplay.value = `${questionCount} Custom Questions`;
                    questionDisplay.style.color = "#22c55e"; // Warna hijau tanda aktif
                } else {
                    questionDisplay.value = "Profile Info Only";
                    questionDisplay.style.color = "#a0a0a0"; // Kembali abu-abu
                }
                
                questionDropdown.classList.remove("show"); 
                document.body.classList.remove("no-scroll"); 
            });
        }
    }

}); // AKHIR DARI DOMContentLoaded