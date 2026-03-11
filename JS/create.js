document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. SCROLL NAVBAR
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
    // 2. FITUR PREVIEW GAMBAR (BANNER UPLOAD)
    // ==========================================
    const bannerInput = document.getElementById("banner_image");
    const imagePreview = document.getElementById("image-preview");
    const pictActionSelect = document.querySelector(".pict-action-select");

    if (bannerInput && imagePreview) {
        bannerInput.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = "block"; 
                    if(pictActionSelect) pictActionSelect.style.zIndex = "3"; 
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = "none";
                imagePreview.src = "";
            }
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
            if(locArrow) locArrow.style.transform = locExpandArea.classList.contains("show") ? "rotate(180deg)" : "rotate(0deg)";
        });
    }

    // ==========================================
    // 4. FITUR SWITCH (REQUIRE APPROVAL)
    // ==========================================
    const approvalSwitch = document.getElementById("approval-switch");
    const approvalVal = document.getElementById("require-approval-val"); 

    if (approvalSwitch && approvalVal) {
        approvalSwitch.addEventListener("click", function() {
            this.classList.toggle("active");
            approvalVal.value = this.classList.contains("active") ? "true" : "false"; 
        });
    }

    // ==========================================
    // 5. FITUR MENU EVENT CATEGORY (DROPDOWN + TYPE)
    // ==========================================
    const categoryTrigger = document.getElementById("category-trigger");
    const categoryDropdown = document.getElementById("category-dropdown");
    const categoryDisplay = document.getElementById("category-display");
    const categorySelect = document.getElementById("category_select");
    const categoryOtherInput = document.getElementById("category_other_input");
    const realCategoryInput = document.getElementById("real_category_input"); 
    const closeCategoryBtn = document.getElementById("close-category-modal");
    const applyCategoryBtn = document.getElementById("apply-category");

    if (categoryTrigger && categoryDropdown) {
        categoryTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            categoryDropdown.classList.toggle("show");
            document.body.classList.toggle("no-scroll", categoryDropdown.classList.contains("show"));
        });

        if (categorySelect) {
            categorySelect.addEventListener("change", function() {
                if (this.value === "Other") {
                    categoryOtherInput.style.display = "block";
                    categoryOtherInput.focus();
                } else {
                    categoryOtherInput.style.display = "none";
                }
            });
        }

        if (closeCategoryBtn) {
            closeCategoryBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                categoryDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); 
            });
        }

        if (applyCategoryBtn) {
            applyCategoryBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                let selectedCategory = categorySelect.value === "Other" ? categoryOtherInput.value.trim() : categorySelect.value;
                
                if (selectedCategory !== "") {
                    categoryDisplay.value = selectedCategory;
                    categoryDisplay.classList.add("selected-text"); 
                    if(realCategoryInput) realCategoryInput.value = selectedCategory; 
                } else {
                    categoryDisplay.value = "Select Category";
                    categoryDisplay.classList.remove("selected-text"); 
                    if(realCategoryInput) realCategoryInput.value = "";
                }
                categoryDropdown.classList.remove("show"); 
                document.body.classList.remove("no-scroll"); 
            });
        }
    }

    // ==========================================
    // 6. FITUR MENU TICKET PRICE (TANPA PAYOUT)
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
        ticketTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            ticketDropdown.classList.toggle("show");
            document.body.classList.toggle("no-scroll", ticketDropdown.classList.contains("show"));
        });

        ticketTypeRadios.forEach(radio => {
            radio.addEventListener("change", function() {
                if(paymentContainer) paymentContainer.style.display = (this.value === "Paid") ? "flex" : "none"; 
            });
        });

        if (addTierBtn && ticketTiersContainer) {
            addTierBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const rowCount = ticketTiersContainer.querySelectorAll(".ticket-tier-row").length;
                if (rowCount < 2) { 
                    const newRow = document.createElement("div");
                    newRow.className = "ticket-tier-row";
                    newRow.innerHTML = `
                        <input type="text" name="tier_name[]" class="tier-name custom-input readonly-vip" value="VIP" readonly required>
                        <input type="number" name="tier_price[]" class="tier-price custom-input" placeholder="Price (Rp)" required>
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
                    if (btn) btn.style.display = (index === 0) ? "none" : "flex"; 
                });
            }
        }

        if (closeTicketBtn) {
            closeTicketBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                ticketDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll");
            });
        }

        if (applyTicketBtn) {
            applyTicketBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const selectedTypeRadio = document.querySelector('input[name="ticket_type"]:checked');
                if (!selectedTypeRadio) return;
                
                if (selectedTypeRadio.value === "Free") {
                    if(ticketDisplay) { 
                        ticketDisplay.value = "Free"; 
                        ticketDisplay.classList.remove("selected-text"); 
                        ticketDisplay.style.color = "#22c55e"; 
                    }
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
                            const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(minPrice);
                            ticketDisplay.value = (prices.length > 1) ? `${formattedPrice} (Start From)` : formattedPrice;
                            ticketDisplay.style.color = ""; // hapus warna inline jika ada
                            ticketDisplay.classList.add("selected-text");
                        } else {
                            ticketDisplay.value = "Paid (Set Price)";
                            ticketDisplay.style.color = "";
                            ticketDisplay.classList.remove("selected-text");
                        }
                    }
                }
                ticketDropdown.classList.remove("show"); 
                document.body.classList.remove("no-scroll"); 
            });
        }
    }

    // ==========================================
    // 7. FITUR MENU KAPASITAS (UNLIMITED/LIMITED)
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
        capacityTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            capacityDropdown.classList.toggle("show");
            document.body.classList.toggle("no-scroll", capacityDropdown.classList.contains("show"));
        });

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

        if (closeCapacityBtn) {
            closeCapacityBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                capacityDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll");
            });
        }   

        if (applyCapacityBtn) {
            applyCapacityBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const selectedTypeRadio = document.querySelector('input[name="cap_type"]:checked');
                if (!selectedTypeRadio) return; 
                
                if (selectedTypeRadio.value === "Unlimited") {
                    if(capacityDisplay) { 
                        capacityDisplay.value = "Unlimited"; 
                        capacityDisplay.classList.remove("selected-text"); 
                    }
                } else {
                    const amount = capAmountInput ? capAmountInput.value : "";
                    const seatTypeRadio = document.querySelector('input[name="seat_type"]:checked');
                    const seatText = (seatTypeRadio && seatTypeRadio.value === "Bebas") ? "General" : "Select Seat";

                    if (capacityDisplay) {
                        if (amount && amount > 0) {
                            capacityDisplay.value = `${amount} Seats (${seatText})`;
                            capacityDisplay.classList.add("selected-text");
                        } else {
                            capacityDisplay.value = `Limited (${seatText})`; 
                            capacityDisplay.classList.remove("selected-text");
                        }
                    }
                }
                capacityDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll");
            });
        }
    }

    // ==========================================
    // 8. FITUR MENU REGISTRATION FORM (CUSTOM QUESTIONS)
    // ==========================================
    const questionTrigger = document.getElementById("question-trigger");
    const questionDropdown = document.getElementById("question-dropdown");
    const questionDisplay = document.getElementById("question-display");
    const closeQuestionBtn = document.getElementById("close-question-modal");
    const applyQuestionBtn = document.getElementById("apply-question");
    
    const questionsContainer = document.getElementById("questions-container");
    const addQuestionBtn = document.getElementById("add-question-btn");

    if (questionTrigger && questionDropdown) {
        questionTrigger.addEventListener("click", function(e) {
            e.stopPropagation();
            questionDropdown.classList.toggle("show");
            document.body.classList.toggle("no-scroll", questionDropdown.classList.contains("show"));
        });

        if (addQuestionBtn && questionsContainer) {
            addQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const newRow = document.createElement("div");
                newRow.className = "question-row";
                newRow.innerHTML = `
                    <input type="text" name="custom_questions[]" class="custom-input" placeholder="e.g., What is your role?" required>
                    <i class="fa-solid fa-trash-can remove-question-btn" title="Remove"></i>
                `;
                questionsContainer.appendChild(newRow);
            });

            questionsContainer.addEventListener("click", function(e) {
                const deleteBtn = e.target.closest(".remove-question-btn");
                if (deleteBtn) {
                    e.stopPropagation();
                    deleteBtn.closest(".question-row").remove();
                }
            });
        }

        if (closeQuestionBtn) {
            closeQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                questionDropdown.classList.remove("show");
                document.body.classList.remove("no-scroll"); 
            });
        }

        if (applyQuestionBtn) {
            applyQuestionBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                const questionCount = questionsContainer.querySelectorAll(".question-row").length;
                
                if (questionCount > 0) {
                    questionDisplay.value = `${questionCount} Custom Questions`;
                    questionDisplay.classList.add("selected-text");
                } else {
                    questionDisplay.value = "Profile Info Only";
                    questionDisplay.classList.remove("selected-text"); 
                }
                
                questionDropdown.classList.remove("show"); 
                document.body.classList.remove("no-scroll"); 
            });
        }
    }

    // ==========================================
    // 9. DROPDOWN NAVBAR PROFILE
    // ==========================================
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        // Munculkan menu saat foto profil diklik
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Tutup menu otomatis jika user klik area kosong di layar
        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }

}); // AKHIR DARI DOMContentLoaded