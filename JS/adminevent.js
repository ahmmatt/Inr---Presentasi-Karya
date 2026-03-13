document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. SCROLL NAVBAR
    // ==========================================
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    // ==========================================
    // 2. EVENT SWITCHER DROPDOWN (PILIH EVENT)
    // ==========================================
    const eventSwitcherBtn = document.getElementById("event-switcher-btn");
    const eventDropdown = document.getElementById("event-dropdown");
    const dropdownSearchInput = document.getElementById("dropdown-search-input");
    const dropdownList = document.getElementById("dropdown-event-list");

    if (eventSwitcherBtn && eventDropdown) {
        // Buka/Tutup dropdown saat judul diklik
        eventSwitcherBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            eventDropdown.classList.toggle("show");
            const icon = eventSwitcherBtn.querySelector("i");
            
            if (eventDropdown.classList.contains("show")) {
                icon.style.transform = "rotate(180deg)";
                if(dropdownSearchInput) dropdownSearchInput.focus(); 
            } else {
                icon.style.transform = "rotate(0deg)";
                if(dropdownSearchInput) dropdownSearchInput.value = ""; // Reset pencarian
                dropdownList.querySelectorAll("li").forEach(li => li.style.display = "");
            }
        });

        // Tutup dropdown jika klik di luar kotak
        window.addEventListener("click", function(e) {
            if (!eventSwitcherBtn.contains(e.target) && !eventDropdown.contains(e.target)) {
                eventDropdown.classList.remove("show");
                const icon = eventSwitcherBtn.querySelector("i");
                if(icon) icon.style.transform = "rotate(0deg)";
            }
        });

        // Fitur Search di dalam Dropdown Event
        if (dropdownSearchInput) {
            dropdownSearchInput.addEventListener("input", function() {
                const keyword = this.value.toLowerCase();
                const listItems = dropdownList.querySelectorAll("li");
                
                listItems.forEach(li => {
                    const text = li.textContent.toLowerCase();
                    if (text.includes(keyword)) {
                        li.style.display = ""; 
                    } else {
                        li.style.display = "none"; 
                    }
                });
            });
        }
    }

    // ==========================================
    // 3. LOGIKA PENCARIAN & FILTER TABEL PESERTA
    // ==========================================
    const tabBtns = document.querySelectorAll(".tab-btn");
    const searchInput = document.getElementById("search-input");
    const tableBody = document.getElementById("attendee-table-body");

    function applyTableFilters() {
        if(!tableBody) return;

        const searchQuery = searchInput ? searchInput.value.toLowerCase() : "";
        const activeTabElement = document.querySelector(".tab-btn.active");
        const activeTab = activeTabElement ? activeTabElement.getAttribute("data-tab") : "all";
        
        // Ambil semua baris peserta (kecuali baris pesan "Not Found")
        const tableRows = tableBody.querySelectorAll("tr:not(#no-results-row)");
        let visibleCount = 0; 

        tableRows.forEach(row => {
            // Abaikan baris jika itu adalah pesan tabel kosong bawaan PHP
            if(row.querySelector("td[colspan='5']")) return;

            // 1. Cek Tab Aktif
            let matchesTab = true;
            if (activeTab !== "all") {
                // Cari elemen badge dengan class yang sesuai (need_approval, awaiting_payment, dll)
                if (!row.querySelector(".status-badge." + activeTab)) {
                    matchesTab = false;
                }
            }

            // 2. Cek Pencarian Text (Nama, Email, ID Tiket)
            let matchesSearch = false;
            const ticketIdText = row.querySelector(".t-id") ? row.querySelector(".t-id").textContent.toLowerCase() : "";
            const attendeeInfoText = row.querySelector(".attendee-cell") ? row.querySelector(".attendee-cell").textContent.toLowerCase() : "";
            const combinedText = ticketIdText + " " + attendeeInfoText;

            if (combinedText.includes(searchQuery)) {
                matchesSearch = true;
            }

            // 3. Terapkan (Tampilkan atau Sembunyikan baris)
            if (matchesTab && matchesSearch) { 
                row.style.display = ""; 
                visibleCount++; 
            } else { 
                row.style.display = "none"; 
            }
        });

        // 4. Tampilkan pesan "Not Found" jika tidak ada baris yang cocok
        let noResultsRow = document.getElementById("no-results-row");
        
        if (visibleCount === 0 && tableRows.length > 0 && !tableRows[0].querySelector("td[colspan='5']")) {
            if (!noResultsRow) {
                noResultsRow = document.createElement("tr");
                noResultsRow.id = "no-results-row";
                noResultsRow.innerHTML = `<td colspan="5" class="empty-table-cell">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 32px; color: #444; margin-bottom: 16px;"></i><br>
                    <h4 style="color: #fff; font-size: 16px; margin-bottom: 4px;">No guests found</h4>
                    <p style="font-size: 13px;">We couldn't find anyone matching your search or filter criteria.</p>
                </td>`;
                tableBody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = ""; 
        } else if (noResultsRow) { 
            noResultsRow.style.display = "none"; 
        }
    }

    // Event listener klik tab
    tabBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            tabBtns.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            applyTableFilters(); 
        });
    });

    // Event listener saat mengetik di pencarian
    if (searchInput) {
        searchInput.addEventListener("input", applyTableFilters); 
    }

    // ==========================================
    // 4. LOGIKA MODAL EDIT PESERTA
    // ==========================================
    const modalOverlay = document.getElementById("edit-modal-overlay");
    const editModal = document.getElementById("edit-modal");
    const editBtns = document.querySelectorAll(".edit-btn-trigger");
    const closeBtn = document.getElementById("close-modal-btn");

    // Ketika tombol edit (ikon pensil) diklik
    editBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            // Ambil data dari atribut HTML (yang telah disisipkan oleh PHP di adminevent.php)
            const id = this.getAttribute("data-id");
            const name = this.getAttribute("data-name");
            const email = this.getAttribute("data-email");
            const seat = this.getAttribute("data-seat");
            const status = this.getAttribute("data-status");

            // Masukkan data ke dalam form modal
            document.getElementById("edit_attendee_id").value = id;
            document.getElementById("edit-name").value = name;
            document.getElementById("edit-email").value = email;
            
            // Jangan masukkan teks "Open Seating" ke input text
            document.getElementById("edit-seat").value = (seat === "Open Seating") ? "" : seat; 
            
            document.getElementById("edit-status").value = status;

            // Tampilkan Modal
            modalOverlay.classList.add("show");
            editModal.classList.add("show");
            document.body.style.overflow = "hidden"; // Kunci scroll layar belakang
        });
    });

    // Fungsi untuk menutup modal
    function closeEditModal() {
        if(modalOverlay) modalOverlay.classList.remove("show");
        if(editModal) editModal.classList.remove("show");
        document.body.style.overflow = "auto"; 
    }

    if(closeBtn) closeBtn.addEventListener("click", closeEditModal);
    if(modalOverlay) modalOverlay.addEventListener("click", closeEditModal);

    // ==========================================
    // 5. DROPDOWN NAVBAR PROFILE
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

    // ==========================================
    // 6. LOGIKA HAMBURGER MENU (MOBILE)
    // ==========================================
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mainNav = document.querySelector('.main-nav');

    if (hamburgerBtn && mainNav) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Mencegah event klik bocor
            mainNav.classList.toggle('active');
            
            // Ubah icon dari garis tiga (bars) menjadi silang (xmark)
            if (mainNav.classList.contains('active')) {
                hamburgerBtn.classList.remove('fa-bars');
                hamburgerBtn.classList.add('fa-xmark');
            } else {
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });

        // Tutup menu otomatis saat user klik area kosong di layar
        document.addEventListener('click', (e) => {
            if (!hamburgerBtn.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('active');
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });
    }

}); // AKHIR DARI DOMContentLoaded