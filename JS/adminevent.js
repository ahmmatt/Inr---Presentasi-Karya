document.addEventListener("DOMContentLoaded", function() {
    
    // 1. SCROLL NAVBAR
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    // ==========================================
    // 2. SIMULASI DATABASE EVENT
    // ==========================================
    let eventDB = {
        "event_1": {
            title: "AI Connect Offline Series",
            capacity: 200,
            attendees: [
                { id: "#SG-8829", name: "Budi Santoso", email: "budi@example.com", img: "../Media/pantai-indah-kapuk-dua-tbk--600.png", initial: "", cat: "VIP", seat: "Row A - 12", status: "pending" },
                { id: "#SG-5511", name: "Sarah Jenkins", email: "sarah.j@company.com", img: "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif", initial: "", cat: "VIP", seat: "Row 1 - 01", status: "present" }
            ]
        },
        "event_2": {
            title: "Tech Startup Mixer 2026",
            capacity: 100,
            attendees: [
                { id: "#TS-1024", name: "Ahmad Mubasysyir Yuz", email: "ahmad@gmail.com", img: "", initial: "A", cat: "REGULAR", seat: "Open Seating", status: "pending" }
            ]
        },
        "event_3": {
            title: "Web3 Developer Summit",
            capacity: 500,
            attendees: [] 
        },
        // --- TAMBAHAN DATA EVENT KE-4 YANG SUDAH SELESAI ---
        "event_4": {
            title: "Design System Workshop",
            capacity: 50,
            status: "ended", // Penanda khusus bahwa event ini sudah selesai
            attendees: [
                { id: "#DS-9001", name: "Rina Melati", email: "rina@example.com", img: "", initial: "R", cat: "VIP", seat: "Row A - 05", status: "present" },
                { id: "#DS-9002", name: "Kevin Sanjaya", email: "kevin@example.com", img: "", initial: "K", cat: "REGULAR", seat: "Row C - 10", status: "present" },
                { id: "#DS-9003", name: "Dina Putri", email: "dina@example.com", img: "", initial: "D", cat: "REGULAR", seat: "Row C - 11", status: "pending" } // Contoh orang yang tidak hadir
            ]
        }
    };

    let currentEventId = "event_1"; // Event yang aktif saat web pertama dibuka

    // Elemen DOM
    const eventSwitcherBtn = document.getElementById("event-switcher-btn");
    const eventDropdown = document.getElementById("event-dropdown");
    const dropdownList = document.getElementById("dropdown-event-list");
    const tableBody = document.getElementById("attendee-table-body");
    const eventTitleDisplay = eventSwitcherBtn.querySelector("h1");

    // Harga untuk kalkulasi Revenue
    const ticketPrices = { "VIP": 1000000, "REGULAR": 500000 };

    // ==========================================
    // 3. FUNGSI UNTUK MERENDER DATA EVENT KE LAYAR
    // ==========================================
    function renderEventData(eventId) {
        const eventData = eventDB[eventId];
        if (!eventData) return;

        // A. Ubah Judul
        // A. Ubah Judul
        if (eventData.status === "ended") {
            // Jika event sudah selesai, tambahkan badge Ended di sebelah judul
            eventTitleDisplay.innerHTML = `${eventData.title} <span class="badge-ended-title">Ended</span>`;
        } else {
            eventTitleDisplay.innerText = eventData.title;
        }

        // B. Siapkan Variabel Hitungan
        let pendingCount = 0;
        let checkedInCount = 0;
        let revenue = 0;
        tableBody.innerHTML = ""; // Kosongkan tabel sebelumnya

        // C. Render Tabel Tamu
        if (eventData.attendees.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#888;">No attendees registered for this event yet.</td></tr>`;
        } else {
            eventData.attendees.forEach(person => {
                // Hitung Statistik
                if (person.status === "pending") pendingCount++;
                if (person.status === "present") checkedInCount++;
                if (ticketPrices[person.cat]) revenue += ticketPrices[person.cat];

                // Siapkan Elemen Visual HTML
                let avatarHTML = person.img ? `<img src="${person.img}" alt="Profile">` : `<div class="avatar-placeholder">${person.initial}</div>`;
                let statusHTML = person.status === 'pending' ? `<span class="status-badge pending">Pending</span>` : `<span class="status-badge present">Checked-In</span>`;
                let actionHTML = person.status === 'pending' 
                    ? `<button class="action-btn approve" data-id="${person.id}" title="Approve & Check-In"><i class="fa-solid fa-check"></i></button>`
                    : `<button class="action-btn revert" data-id="${person.id}" title="Cancel Check-In"><i class="fa-solid fa-rotate-left"></i></button>`;
                
                // --- KUNCI PERUBAHAN ADA DI SINI ---
                // Jika kategorinya VIP, berikan class 'text-vip' yang berwarna emas
                let vipClass = person.cat === 'VIP' ? 'text-vip' : '';

                // Masukkan ke dalam tabel
                let tr = document.createElement("tr");
                tr.innerHTML = `
                    <td class="t-id">${person.id}</td>
                    <td>
                        <div class="attendee-cell">
                            ${avatarHTML}
                            <div><h4>${person.name}</h4><p>${person.email}</p></div>
                        </div>
                    </td>
                    <td>
                        <span class="t-category ${vipClass}">${person.cat}</span><br>
                        <span class="t-seat">${person.seat}</span>
                    </td>
                    <td>${statusHTML}</td>
                    <td class="action-cell">
                        ${actionHTML}
                        <button class="action-btn edit" data-id="${person.id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button class="action-btn delete" data-id="${person.id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                tableBody.appendChild(tr);
            });
        }

        // D. Update Kartu Metrik Apple Style
        function formatCurrency(num) {
            if (num >= 1000000) return "Rp " + (num / 1000000).toFixed(1) + "M";
            if (num >= 1000) return "Rp " + (num / 1000).toFixed(1) + "K";
            return "Rp " + num;
        }

        document.getElementById("metric-total-reg").innerHTML = `${eventData.attendees.length} <span class="metric-total">/ ${eventData.capacity}</span>`;
        document.getElementById("metric-pending").innerText = pendingCount;
        document.getElementById("metric-checkedin").innerText = checkedInCount;
        document.getElementById("metric-revenue").innerText = formatCurrency(revenue);
        
        const badgeCount = document.querySelector(".badge-count");
        if (badgeCount) badgeCount.innerText = pendingCount;
        
        // Kembalikan filter tab ke "All Guests" dan kosongkan kolom pencarian setiap ganti event
        document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
        document.querySelector(".tab-btn[data-tab='all']").classList.add("active");
        if(searchInput) searchInput.value = ""; // Kosongkan ketikan lama
        
        // Terapkan filter agar tabel langsung rapi
        applyTableFilters();
    }

    // ==========================================
    // 4. LOGIKA EVENT SWITCHER (PILIH EVENT) & PENCARIAN
    // ==========================================
    const dropdownSearchInput = document.getElementById("dropdown-search-input");

    // A. Buka/Tutup Dropdown Event
    eventSwitcherBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        eventDropdown.classList.toggle("show");
        const icon = eventSwitcherBtn.querySelector("i");
        
        if (eventDropdown.classList.contains("show")) {
            icon.style.transform = "rotate(180deg)";
            if(dropdownSearchInput) dropdownSearchInput.focus(); // Otomatis fokus ke kolom ketik
        } else {
            icon.style.transform = "rotate(0deg)";
            resetDropdownSearch(); // Bersihkan pencarian jika ditutup
        }
    });

    // B. Tutup dropdown jika klik di luar area
    window.addEventListener("click", function(e) {
        if (!eventSwitcherBtn.contains(e.target) && !eventDropdown.contains(e.target)) {
            eventDropdown.classList.remove("show");
            eventSwitcherBtn.querySelector("i").style.transform = "rotate(0deg)";
            resetDropdownSearch(); // Bersihkan pencarian jika ditutup
        }
    });

    // C. Mengganti event saat list dropdown diklik
    dropdownList.addEventListener("click", function(e) {
        const clickedLi = e.target.closest("li");
        if (!clickedLi || clickedLi.id === "no-event-found-msg") return; // Abaikan jika mengklik tulisan Not Found

        // Ganti UI Centang pada Dropdown
        dropdownList.querySelectorAll("li").forEach(li => {
            li.classList.remove("active-event");
            const checkIcon = li.querySelector("i");
            if(checkIcon) checkIcon.remove();
        });

        clickedLi.classList.add("active-event");
        
        // Hanya tambahkan icon check jika event tersebut BUKAN event yang sudah selesai (Tidak ada badge Ended)
        if (!clickedLi.querySelector('.badge-ended')) {
            clickedLi.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-check"></i> ');
        }

        // Panggil fungsi render untuk mengubah data layar
        currentEventId = clickedLi.getAttribute("data-id");
        renderEventData(currentEventId);
        
        // Tutup dropdown & bersihkan pencarian
        eventDropdown.classList.remove("show");
        eventSwitcherBtn.querySelector("i").style.transform = "rotate(0deg)";
        resetDropdownSearch();
    });

    // D. Fitur Pencarian di dalam Dropdown
    if (dropdownSearchInput) {
        dropdownSearchInput.addEventListener("input", function() {
            const keyword = this.value.toLowerCase();
            const listItems = dropdownList.querySelectorAll("li:not(#no-event-found-msg)");
            let visibleCount = 0;

            listItems.forEach(li => {
                const text = li.textContent.toLowerCase();
                if (text.includes(keyword)) {
                    li.style.display = ""; // Munculkan
                    visibleCount++;
                } else {
                    li.style.display = "none"; // Sembunyikan
                }
            });

            // Tampilkan pesan jika event tidak ditemukan
            let noEventMsg = document.getElementById("no-event-found-msg");
            if (visibleCount === 0) {
                if (!noEventMsg) {
                    noEventMsg = document.createElement("li");
                    noEventMsg.id = "no-event-found-msg";
                    noEventMsg.style.textAlign = "center";
                    noEventMsg.style.color = "#888";
                    noEventMsg.style.padding = "20px";
                    noEventMsg.style.pointerEvents = "none"; // Agar tidak bisa diklik
                    noEventMsg.innerHTML = "Event not found";
                    dropdownList.appendChild(noEventMsg);
                }
                noEventMsg.style.display = "";
            } else if (noEventMsg) {
                noEventMsg.style.display = "none";
            }
        });
    }

    // E. Fungsi untuk membersihkan pencarian saat dropdown ditutup
    function resetDropdownSearch() {
        if (dropdownSearchInput) {
            dropdownSearchInput.value = ""; // Kosongkan teks
            // Munculkan kembali semua list
            dropdownList.querySelectorAll("li").forEach(li => li.style.display = "");
            // Sembunyikan pesan not found jika ada
            const noEventMsg = document.getElementById("no-event-found-msg");
            if (noEventMsg) noEventMsg.style.display = "none";
        }
    }

    // ==========================================
    // 5. LOGIKA HAPUS EVENT
    // ==========================================
    const deleteEventBtn = document.getElementById("delete-event-btn");
    if (deleteEventBtn) {
        deleteEventBtn.addEventListener("click", function() {
            const eventData = eventDB[currentEventId];
            if (!eventData) return;

            const isConfirmed = confirm(`Are you sure you want to delete "${eventData.title}"? This action cannot be undone.`);
            
            if (isConfirmed) {
                // 1. Hapus dari simulasi database
                delete eventDB[currentEventId];
                
                // 2. Hapus elemen dari dropdown
                const liToRemove = dropdownList.querySelector(`li[data-id="${currentEventId}"]`);
                if (liToRemove) liToRemove.remove();

                // 3. Cari apakah masih ada event lain yang tersisa?
                const remainingEvents = Object.keys(eventDB);
                if (remainingEvents.length > 0) {
                    // Pindah ke event berikutnya yang tersedia
                    currentEventId = remainingEvents[0];
                    const nextLi = dropdownList.querySelector(`li[data-id="${currentEventId}"]`);
                    nextLi.classList.add("active-event");
                    nextLi.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-check"></i> ');
                    renderEventData(currentEventId);
                    alert("Event deleted successfully. Switched to next available event.");
                } else {
                    // Jika semua event sudah habis dihapus
                    currentEventId = null;
                    eventTitleDisplay.innerText = "No Event Available";
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No events to manage. Create a new event.</td></tr>`;
                    document.getElementById("metric-total-reg").innerHTML = `0 <span class="metric-total">/ 0</span>`;
                    document.getElementById("metric-pending").innerText = "0";
                    document.getElementById("metric-checkedin").innerText = "0";
                    document.getElementById("metric-revenue").innerText = "Rp 0";
                    alert("All events have been deleted.");
                }
            }
        });
    }

    // ==========================================
    // 6. LOGIKA PENCARIAN & FILTER TABEL (GABUNGAN)
    // ==========================================
    const tabBtns = document.querySelectorAll(".tab-btn");
    const searchInput = document.getElementById("search-input");

    // Fungsi Utama untuk menyaring data & menampilkan "Not Found"
    function applyTableFilters() {
        const searchQuery = searchInput.value.toLowerCase();
        const activeTab = document.querySelector(".tab-btn.active").getAttribute("data-tab");
        
        // Ambil SEMUA baris kecuali baris "Not Found" dan baris "Kosong dari awal"
        const tableRows = tableBody.querySelectorAll("tr:not(#no-results-row):not(.empty-event-row)");
        
        let visibleCount = 0; // Variabel untuk menghitung baris yang lolos filter

        tableRows.forEach(row => {
            // --- 1. CEK KONDISI TAB ---
            let matchesTab = true;
            if (activeTab === "pending" && !row.querySelector(".status-badge.pending")) {
                matchesTab = false;
            } else if (activeTab === "checked-in" && !row.querySelector(".status-badge.present")) {
                matchesTab = false;
            }

            // --- 2. CEK KONDISI PENCARIAN ---
            let matchesSearch = false;
            const ticketIdText = row.querySelector(".t-id") ? row.querySelector(".t-id").textContent.toLowerCase() : "";
            const attendeeInfoText = row.querySelector(".attendee-cell") ? row.querySelector(".attendee-cell").textContent.toLowerCase() : "";
            const combinedText = ticketIdText + " " + attendeeInfoText;

            if (combinedText.includes(searchQuery)) {
                matchesSearch = true;
            }

            // --- 3. TERAPKAN KE BARIS ---
            if (matchesTab && matchesSearch) {
                row.style.display = "";
                visibleCount++; // Tambah 1 jika baris ini berhasil ditampilkan
            } else {
                row.style.display = "none";
            }
        });

        // --- 4. LOGIKA MENAMPILKAN PESAN "NOT FOUND" ---
        let noResultsRow = document.getElementById("no-results-row");
        
        // Jika tidak ada data yang lolos filter, DAN tabel aslinya memang punya data tamu
        if (visibleCount === 0 && tableRows.length > 0) {
            if (!noResultsRow) {
                // Buat baris baru khusus untuk pesan Not Found jika belum ada
                noResultsRow = document.createElement("tr");
                noResultsRow.id = "no-results-row";
                noResultsRow.innerHTML = `
                    <td colspan="5" style="text-align:center; padding: 60px 20px; color:#888;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 32px; color: #444; margin-bottom: 16px;"></i><br>
                        <h4 style="color: #fff; font-size: 16px; margin-bottom: 4px;">No guests found</h4>
                        <p style="font-size: 13px;">We couldn't find anyone matching your search or filter criteria.</p>
                    </td>`;
                tableBody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = ""; // Tampilkan pesannya
        } else if (noResultsRow) {
            noResultsRow.style.display = "none"; // Sembunyikan pesannya jika ada data yang ketemu
        }
    }

    // Event Listener saat Tab diklik
    tabBtns.forEach(btn => {
        btn.addEventListener("click", function() {
            tabBtns.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            
            applyTableFilters(); // Jalankan penyaringan ulang
        });
    });

    // Event Listener saat kita mengetik di kolom pencarian
    if (searchInput) {
        // 'input' akan mendeteksi setiap kali ada huruf yang diketik atau dihapus
        searchInput.addEventListener("input", applyTableFilters); 
    }


    // ==========================================
    // 7. LOGIKA AKSI TOMBOL TABEL (APPROVE, DELETE, EDIT)
    // ==========================================
    let editingAttendeeIndex = null; // Menyimpan index orang yang sedang di-edit

    // Fungsi klik di dalam area tabel (Event Delegation)
    tableBody.addEventListener("click", function(e) {
        const btn = e.target.closest(".action-btn");
        if (!btn) return;

        const attendeeId = btn.getAttribute("data-id");
        const eventData = eventDB[currentEventId];
        
        // Cari posisi (index) orang tersebut di dalam array attendees
        const index = eventData.attendees.findIndex(person => person.id === attendeeId);
        if (index === -1) return;

        // JIKA TOMBOL APPROVE/CHECK-IN DITEKAN
        if (btn.classList.contains("approve")) {
            eventData.attendees[index].status = "present";
            renderEventData(currentEventId); // Render ulang layar
        } 
        
        // JIKA TOMBOL REVERT/BATAL DITEKAN
        else if (btn.classList.contains("revert")) {
            eventData.attendees[index].status = "pending";
            renderEventData(currentEventId);
        } 
        
        // JIKA TOMBOL DELETE DITEKAN
        else if (btn.classList.contains("delete")) {
            if (confirm(`Remove ${eventData.attendees[index].name} from the guest list?`)) {
                eventData.attendees.splice(index, 1); // Hapus data dari array
                renderEventData(currentEventId);
            }
        } 
        
        // JIKA TOMBOL EDIT DITEKAN
        else if (btn.classList.contains("edit")) {
            openEditModal(eventData.attendees[index], index);
        }
    });

    // ==========================================
    // 8. LOGIKA MODAL EDIT ATTENDEE
    // ==========================================
    const modalOverlay = document.getElementById("edit-modal-overlay");
    const editModal = document.getElementById("edit-modal");
    const editName = document.getElementById("edit-name");
    const editEmail = document.getElementById("edit-email");
    const editCategory = document.getElementById("edit-category");
    const editSeat = document.getElementById("edit-seat");
    const editStatusToggle = document.getElementById("edit-status-toggle");

    // Fungsi Buka Modal & Isi Form
    function openEditModal(person, index) {
        editingAttendeeIndex = index;
        
        // Masukkan data ke dalam form
        editName.value = person.name;
        editEmail.value = person.email;
        editCategory.value = person.cat;
        editSeat.value = person.seat;
        
        // Atur posisi Switch Toggle
        if (person.status === "present") {
            editStatusToggle.classList.add("active");
        } else {
            editStatusToggle.classList.remove("active");
        }

        // Tampilkan Modal
        modalOverlay.classList.add("show");
        editModal.classList.add("show");
        document.body.style.overflow = "hidden"; // Kunci scroll layar belakang
    }

    // Fungsi Tutup Modal
    function closeEditModal() {
        modalOverlay.classList.remove("show");
        editModal.classList.remove("show");
        document.body.style.overflow = "auto";
        editingAttendeeIndex = null;
    }

    // Event Klik Tutup Modal
    document.getElementById("close-modal-btn").addEventListener("click", closeEditModal);
    modalOverlay.addEventListener("click", closeEditModal);

    // Event Klik Toggle Switch di dalam Modal
    editStatusToggle.addEventListener("click", function() {
        this.classList.toggle("active");
    });

    // Event Klik Simpan Perubahan (Confirm)
    document.getElementById("save-edit-btn").addEventListener("click", function() {
        if (editingAttendeeIndex !== null) {
            const eventData = eventDB[currentEventId];
            
            // Perbarui data di dalam array
            eventData.attendees[editingAttendeeIndex].name = editName.value;
            eventData.attendees[editingAttendeeIndex].email = editEmail.value;
            eventData.attendees[editingAttendeeIndex].cat = editCategory.value;
            eventData.attendees[editingAttendeeIndex].seat = editSeat.value;
            
            // Perbarui status berdasarkan toggle
            eventData.attendees[editingAttendeeIndex].status = editStatusToggle.classList.contains("active") ? "present" : "pending";

            // Tutup modal dan refresh data tabel
            closeEditModal();
            renderEventData(currentEventId);
        }
    });

    // PANGGIL RENDER PERTAMA KALI SAAT WEB DIBUKA
    renderEventData(currentEventId);

    

});