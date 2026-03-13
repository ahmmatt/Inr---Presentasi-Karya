document.addEventListener("DOMContentLoaded", function() {
    
    // =========================================
    // 1. NAVBAR & PROFILE DROPDOWN LOGIC
    // =========================================
    
    // --- Scroll Navbar Effect ---
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

    // --- Profile Dropdown Toggle ---
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            // Toggle display antara block dan none
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Tutup menu jika klik di luar area profil
        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }


    // =========================================
    // 2. SCANNER INITIALIZATION & CONFIG
    // =========================================

    // Ambil event_id dari URL
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('event_id');

    // Jika tidak ada event_id, jangan jalankan logika scanner
    if (!eventId) {
        console.error("Event ID tidak ditemukan di URL!");
        // Optional: Tampilkan pesan ke user jika perlu
        return;
    }

    // Deklarasi Elemen UI Scanner
    const btnStartScan = document.getElementById("btn-start-scan");
    const cameraContainer = document.getElementById("camera-container");
    const cameraText = document.getElementById("camera-text");
    const cameraIcon = document.getElementById("camera-icon");
    const emptyState = document.getElementById("empty-state");
    const scanResultCard = document.getElementById("scan-result-card");
    const btnApprove = document.getElementById("btn-approve");
    const ticketStatus = document.getElementById("ticket-status");
    const overlay = document.getElementById('camera-overlay');

    // Variabel Global Scanner
    let html5QrCode = new Html5Qrcode("reader");
    let isScanning = false; 
    let scannedToken = '';


    // =========================================
    // 3. SCANNER HELPER FUNCTIONS
    // =========================================

    // Fungsi Reset UI Kamera ke tampilan Standby
    function resetScannerUI() {
        cameraContainer.classList.remove("scanning");
        
        if(overlay) overlay.style.display = 'block';
        if(cameraIcon) {
            cameraIcon.style.display = "inline-block";
            cameraIcon.className = "fa-solid fa-camera camera-icon";
            cameraIcon.style.color = "#333";
        }
        if(cameraText) {
            cameraText.style.display = "block";
            cameraText.innerText = "Camera Standby";
        }
        
        if(btnStartScan) {
            btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Start Scanning';
            btnStartScan.classList.remove("btn-cancel-scan");
        }
        
        isScanning = false;
    }

    // Fungsi Verifikasi Tiket via AJAX
    function verifyTicket(token) {
        scannedToken = token;
        
        fetch('scanner_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify', token: token, event_id: eventId })
        })
        .then(response => response.json())
        .then(res => {
            if(emptyState) emptyState.style.display = 'none';
            if(scanResultCard) scanResultCard.style.display = 'flex'; // Gunakan flex sesuai CSS terbaru

            if(res.status === 'success') {
                const data = res.data;
                
                // Logika Foto Profil vs Inisial
                const photoEl = document.getElementById('attendee-photo');
                const initialEl = document.getElementById('attendee-initial');

                if (data.profile_picture && data.profile_picture.trim() !== "") {
                    photoEl.src = '../Media/uploads/' + data.profile_picture;
                    photoEl.style.display = 'block';
                    initialEl.style.display = 'none';
                } else {
                    initialEl.innerText = data.full_name ? data.full_name.charAt(0).toUpperCase() : '?';
                    initialEl.style.display = 'flex';
                    photoEl.style.display = 'none';
                }
                
                // Isi Data ke Kartu
                document.getElementById('attendee-name').innerText = data.full_name;
                document.getElementById('attendee-email').innerText = data.email;
                document.getElementById('ticket-tier').innerText = data.tier_name;
                document.getElementById('ticket-seat').innerText = data.seat_number ? data.seat_number : 'Open Seating';
                document.getElementById('ticket-id').innerText = data.ticket_code;

                // Reset tombol approve
                btnApprove.style.display = 'none'; 
                btnApprove.disabled = false;
                btnApprove.innerHTML = '<i class="fa-solid fa-check-circle"></i> Approve & Check-In';

                // Update Status Badge
                updateStatusBadge(data.status);

            } else {
                alert("ERROR: " + res.message);
                if(emptyState) emptyState.style.display = 'flex';
                if(scanResultCard) scanResultCard.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            alert("Koneksi Error. Pastikan scanner_api.php berfungsi normal.");
        });
    }

    // Helper untuk update tampilan badge status
    function updateStatusBadge(status) {
        if(status === 'approved') {
            ticketStatus.className = "status-badge";
            ticketStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid & Approved';
            ticketStatus.style.background = 'rgba(34,197,94,0.1)';
            ticketStatus.style.color = '#22c55e';
            ticketStatus.style.border = '1px solid #22c55e';
            btnApprove.style.display = 'block'; 
        } 
        else if(status === 'checked_in') {
            ticketStatus.className = "status-badge present";
            ticketStatus.innerHTML = '<i class="fa-solid fa-check-double"></i> Already Checked-In';
            ticketStatus.style.background = 'rgba(59,130,246,0.1)';
            ticketStatus.style.color = '#3b82f6';
            ticketStatus.style.border = '1px solid #3b82f6';
        } 
        else {
            ticketStatus.className = "status-badge pending";
            ticketStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Status: ' + status;
            ticketStatus.style.background = 'rgba(239,68,68,0.1)';
            ticketStatus.style.color = '#ef4444';
            ticketStatus.style.border = '1px solid #ef4444';
        }
    }


    // =========================================
    // 4. EVENT LISTENERS FOR SCAN ACTIONS
    // =========================================

    if (btnStartScan) {
        btnStartScan.addEventListener("click", function() {
            if (!isScanning) {
                isScanning = true;

                // Reset UI Hasil
                if(emptyState) emptyState.style.display = "flex";
                if(scanResultCard) scanResultCard.style.display = "none";

                // Update UI Kamera ke mode scanning
                cameraContainer.classList.add("scanning");
                if(overlay) overlay.style.display = 'none';
                
                btnStartScan.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Scan';
                btnStartScan.classList.add("btn-cancel-scan");

                // Jalankan Library Html5QrCode
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        // Jika Berhasil Baca QR
                        html5QrCode.stop().then(() => {
                            resetScannerUI(); 
                            btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Scan Next Ticket';
                            verifyTicket(decodedText);
                        }).catch(err => console.error("Gagal mematikan kamera", err));
                    },
                    (errorMessage) => { /* Abaikan feedback saat mencari QR */ }
                ).catch((err) => {
                    alert("Kamera gagal diakses: " + err);
                    resetScannerUI();
                });

            } else {
                // Jika Batal (User klik 'Cancel Scan')
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {
                        resetScannerUI();
                    }).catch(err => console.error("Kamera belum siap dimatikan", err));
                }
            }
        });
    }

    // --- Tombol Approve & Check-In ---
    if (btnApprove) {
        btnApprove.addEventListener('click', () => {
            const originalText = btnApprove.innerHTML;
            
            btnApprove.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            btnApprove.disabled = true;

            if(!scannedToken || !eventId) {
                alert("Data tidak valid!");
                btnApprove.innerHTML = originalText;
                btnApprove.disabled = false;
                return;
            }

            fetch('scanner_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'checkin', token: scannedToken, event_id: eventId })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const res = JSON.parse(text); 
                    if(res.status === 'success') {
                        // Update UI Sukses
                        ticketStatus.className = "status-badge present";
                        ticketStatus.innerHTML = '<i class="fa-solid fa-user-check"></i> Present (Checked In)';
                        ticketStatus.style.background = 'rgba(59,130,246,0.1)';
                        ticketStatus.style.color = '#3b82f6';
                        ticketStatus.style.border = '1px solid #3b82f6';

                        btnApprove.innerHTML = '<i class="fa-solid fa-check"></i> Checked-In';
                        setTimeout(() => { btnApprove.style.display = 'none'; }, 1500);
                    } else {
                        alert("Gagal Check-In: " + res.message);
                        btnApprove.innerHTML = originalText;
                        btnApprove.disabled = false;
                    }
                } catch (err) {
                    console.error("Respon Non-JSON dari PHP:", text);
                    alert("Terjadi kesalahan server. Cek scanner_api.php.");
                    btnApprove.innerHTML = originalText;
                    btnApprove.disabled = false;
                }
            })
            .catch(err => {
                alert("Koneksi ke database terputus: " + err.message);
                btnApprove.innerHTML = originalText;
                btnApprove.disabled = false;
            });
        });
    }

    // ==========================================
    // 5. LOGIKA HAMBURGER MENU (MOBILE)
    // ==========================================
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mainNav = document.querySelector('.main-nav');

    if (hamburgerBtn && mainNav) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            mainNav.classList.toggle('active');
            
            if (mainNav.classList.contains('active')) {
                hamburgerBtn.classList.remove('fa-bars');
                hamburgerBtn.classList.add('fa-xmark');
            } else {
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });

        document.addEventListener('click', (e) => {
            if (!hamburgerBtn.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.remove('active');
                hamburgerBtn.classList.remove('fa-xmark');
                hamburgerBtn.classList.add('fa-bars');
            }
        });
    }

});