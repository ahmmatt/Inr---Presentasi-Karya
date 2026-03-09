document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. SCROLL NAVBAR ---
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

    // --- 2. AMBIL EVENT ID DARI URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('event_id');

    // Jika tidak ada event_id, jangan jalankan scanner
    if (!eventId) {
        alert("Event ID tidak ditemukan di URL!");
        return;
    }

    // --- 3. DEKLARASI ELEMEN ---
    const btnStartScan = document.getElementById("btn-start-scan");
    const cameraContainer = document.getElementById("camera-container");
    const cameraText = document.getElementById("camera-text");
    const cameraIcon = document.getElementById("camera-icon");
    const emptyState = document.getElementById("empty-state");
    const scanResultCard = document.getElementById("scan-result-card");
    const btnApprove = document.getElementById("btn-approve");
    const ticketStatus = document.getElementById("ticket-status");
    const overlay = document.getElementById('camera-overlay');

    // Variabel Global
    let html5QrCode = new Html5Qrcode("reader");
    let isScanning = false; 
    let scannedToken = '';

    // --- 4. FUNGSI UNTUK MERESET UI KAMERA ---
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
        
        btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Start Scanning';
        btnStartScan.classList.remove("btn-cancel-scan");
        
        isScanning = false;
    }

    // --- 5. FUNGSI CEK KE DATABASE VIA AJAX ---
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
            if(scanResultCard) scanResultCard.style.display = 'block';

            if(res.status === 'success') {
                const data = res.data;
                
                // --- PERBAIKAN: LOGIKA FOTO PROFIL ATAU INISIAL ---
                const photoEl = document.getElementById('attendee-photo');
                const initialEl = document.getElementById('attendee-initial');

                if (data.profile_picture && data.profile_picture !== null && data.profile_picture.trim() !== "") {
                    // Jika user punya foto, tampilkan gambarnya
                    photoEl.src = '../Media/uploads/' + data.profile_picture;
                    photoEl.style.display = 'block';
                    initialEl.style.display = 'none';
                } else {
                    // Jika tidak punya foto, tampilkan inisial huruf
                    initialEl.innerText = data.full_name.charAt(0).toUpperCase();
                    initialEl.style.display = 'flex';
                    photoEl.style.display = 'none';
                }
                
                // Isi Data ke Kartu
                document.getElementById('attendee-name').innerText = data.full_name;
                document.getElementById('attendee-email').innerText = data.email;
                document.getElementById('ticket-tier').innerText = data.tier_name;
                document.getElementById('ticket-seat').innerText = data.seat_number ? data.seat_number : 'Open Seating';
                
                // Tampilkan ID sebagian saja agar rapi
                document.getElementById('ticket-id').innerText = data.ticket_code;

                // Reset tombol approve
                btnApprove.style.display = 'none'; 
                btnApprove.disabled = false;
                btnApprove.innerHTML = '<i class="fa-solid fa-check-circle"></i> Approve & Check-In';

                // Tentukan Warna Status
                if(data.status === 'approved') {
                    ticketStatus.className = "status-badge";
                    ticketStatus.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid & Approved';
                    ticketStatus.style.background = 'rgba(34,197,94,0.1)';
                    ticketStatus.style.color = '#22c55e';
                    ticketStatus.style.border = '1px solid #22c55e';
                    
                    btnApprove.style.display = 'block'; // Tombol hanya muncul jika valid
                } 
                else if(data.status === 'checked_in') {
                    ticketStatus.className = "status-badge present";
                    ticketStatus.innerHTML = '<i class="fa-solid fa-check-double"></i> Already Checked-In';
                    ticketStatus.style.background = 'rgba(59,130,246,0.1)';
                    ticketStatus.style.color = '#3b82f6';
                    ticketStatus.style.border = '1px solid #3b82f6';
                } 
                else {
                    ticketStatus.className = "status-badge pending";
                    ticketStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Invalid Status: ' + data.status;
                    ticketStatus.style.background = 'rgba(239,68,68,0.1)';
                    ticketStatus.style.color = '#ef4444';
                    ticketStatus.style.border = '1px solid #ef4444';
                }

            } else {
                alert("ERROR: " + res.message);
                if(emptyState) emptyState.style.display = 'block';
                if(scanResultCard) scanResultCard.style.display = 'none';
            }
        })
        .catch(err => {
            console.error(err);
            alert("Koneksi Error. Pastikan scanner_api.php berfungsi normal.");
        });
    }

    // --- 6. FUNGSI MEMULAI / MEMBATALKAN KAMERA ---
    if (btnStartScan) {
        btnStartScan.addEventListener("click", function() {
            
            if (!isScanning) {
                isScanning = true;

                // Reset UI Hasil
                if(emptyState) emptyState.style.display = "block"; // diubah ke block sesuai CSS
                if(scanResultCard) scanResultCard.style.display = "none";

                // Ubah UI Kamera
                cameraContainer.classList.add("scanning");
                if(overlay) overlay.style.display = 'none';
                
                // Ubah Tombol Menjadi Cancel
                btnStartScan.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Scan';
                btnStartScan.classList.add("btn-cancel-scan");

                // Mulai Kamera
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        // JIKA BERHASIL BACA QR
                        html5QrCode.stop().then(() => {
                            resetScannerUI(); 
                            btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Scan Next Ticket';
                            
                            // Kirim ke database
                            verifyTicket(decodedText);
                        }).catch(err => console.log("Gagal mematikan kamera", err));
                    },
                    (errorMessage) => { /* Abaikan saat tidak ada QR */ }
                ).catch((err) => {
                    alert("Kamera gagal diakses: " + err);
                    resetScannerUI();
                });

            } else {
                // JIKA BATAL
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {
                        resetScannerUI();
                    }).catch(err => {
                        console.log("Kamera belum siap dimatikan", err);
                    });
                }
            }
        });
    }

    // --- 7. FUNGSI KLIK TOMBOL APPROVE & CHECK-IN ---
    if (btnApprove) {
        btnApprove.addEventListener('click', () => {
            const originalText = btnApprove.innerHTML;
            
            // Ubah tombol jadi loading
            btnApprove.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            btnApprove.disabled = true;

            // Pastikan token dan eventId ada
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
            .then(response => response.text()) // Ambil teks asli dulu untuk jaga-jaga ada error PHP
            .then(text => {
                try {
                    const res = JSON.parse(text); 
                    
                    if(res.status === 'success') {
                        // Animasi sukses
                        ticketStatus.className = "status-badge present";
                        ticketStatus.innerHTML = '<i class="fa-solid fa-user-check"></i> Present (Checked In)';
                        ticketStatus.style.background = 'rgba(59,130,246,0.1)';
                        ticketStatus.style.color = '#3b82f6';
                        ticketStatus.style.border = '1px solid #3b82f6';

                        btnApprove.innerHTML = '<i class="fa-solid fa-check"></i> Checked-In';
                        
                        // Hilangkan tombol setelah 1.5 detik
                        setTimeout(() => {
                            btnApprove.style.display = 'none';
                        }, 1500);

                    } else {
                        // Gagal Check In (Mungkin sudah check in / belum bayar)
                        alert("Gagal Check-In: " + res.message);
                        btnApprove.innerHTML = originalText;
                        btnApprove.disabled = false;
                    }
                } catch (err) {
                    // Jika PHP malah mengirim pesan error berdarah (Fatal error / Warning)
                    console.error("Respon Error dari PHP:", text);
                    alert("Terjadi kesalahan di server. Silakan cek file scanner_api.php. Pesan asli: \n\n" + text.substring(0, 150));
                    btnApprove.innerHTML = originalText;
                    btnApprove.disabled = false;
                }
            })
            .catch(err => {
                // Jika koneksi internet putus / file tidak ditemukan
                alert("Koneksi ke database terputus: " + err.message);
                btnApprove.innerHTML = originalText;
                btnApprove.disabled = false;
            });
        });
    }

});