document.addEventListener("DOMContentLoaded", function() {
    
    // --- SCROLL NAVBAR ---
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

    // Elemen Scanner
    const btnStartScan = document.getElementById("btn-start-scan");
    const cameraContainer = document.getElementById("camera-container");
    const cameraText = document.getElementById("camera-text");
    const cameraIcon = document.getElementById("camera-icon");
    const scannedTicketId = document.getElementById("scanned-ticket-id");
    
    // Elemen Hasil
    const emptyState = document.getElementById("empty-state");
    const scanResultCard = document.getElementById("scan-result-card");
    
    // Elemen Action Approve
    const btnApprove = document.getElementById("btn-approve");
    const ticketStatus = document.getElementById("ticket-status");

    // Variabel Global untuk Scanner
    let html5QrCode;
    let isScanning = false; // Penanda apakah kamera sedang aktif

    // FUNGSI UNTUK MERESET UI KAMERA KE AWAL
    function resetScannerUI() {
        cameraContainer.classList.remove("scanning");
        cameraIcon.style.display = "block";
        cameraIcon.className = "fa-solid fa-camera camera-icon";
        cameraIcon.style.color = "#333";
        cameraText.style.display = "block";
        cameraText.innerText = "Camera Standby";
        
        // Kembalikan Tombol ke "Start Scanning"
        btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Start Scanning';
        btnStartScan.classList.remove("btn-cancel-scan");
        
        isScanning = false;
    }

    // 1. FUNGSI MEMULAI/MEMBATALKAN KAMERA
    if (btnStartScan) {
        btnStartScan.addEventListener("click", function() {
            
            // JIKA SEDANG TIDAK SCANNING -> NYALAKAN KAMERA
            if (!isScanning) {
                
                isScanning = true;

                // Reset UI Hasil
                emptyState.style.display = "flex";
                scanResultCard.style.display = "none";
                ticketStatus.className = "status-badge pending";
                ticketStatus.innerHTML = '<i class="fa-solid fa-clock"></i> Pending (Not Checked In)';
                btnApprove.disabled = false;
                btnApprove.innerHTML = '<i class="fa-solid fa-check-circle"></i> Approve & Check-In';

                // Ubah UI Kamera
                cameraContainer.classList.add("scanning");
                cameraIcon.style.display = "none"; 
                cameraText.style.display = "none";
                
                // UBAH TOMBOL MENJADI CANCEL (Tidak di-disabled)
                btnStartScan.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Scan';
                btnStartScan.classList.add("btn-cancel-scan");

                // PANGGIL KAMERA
                html5QrCode = new Html5Qrcode("reader");
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText, decodedResult) => {
                        // SUKSES MEMBACA QR
                        html5QrCode.stop().then(() => {
                            
                            resetScannerUI(); // Kembalikan tampilan kamera
                            btnStartScan.innerHTML = '<i class="fa-solid fa-qrcode"></i> Scan Next Ticket';
                            
                            // Masukkan Hasil Teks QR
                            if(scannedTicketId) {
                                scannedTicketId.innerText = decodedText;
                            }

                            // Munculkan Kartu Data Pembeli
                            emptyState.style.display = "none";
                            scanResultCard.style.display = "flex";
                            
                        }).catch(err => console.log("Gagal mematikan kamera", err));
                    },
                    (errorMessage) => { /* Abaikan saat belum ketemu QR */ }
                ).catch((err) => {
                    alert("Gagal mengakses kamera. Pastikan izin kamera diberikan.");
                    resetScannerUI();
                });

            } 
            // JIKA SEDANG SCANNING -> BATALKAN & MATIKAN KAMERA
            else {
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {
                        resetScannerUI(); // Kembalikan semua UI ke awal
                    }).catch(err => {
                        console.log("Gagal mematikan kamera saat cancel", err);
                    });
                }
            }

        });
    }

    // 2. FUNGSI APPROVE TIKET
    if (btnApprove) {
        btnApprove.addEventListener("click", function() {
            ticketStatus.className = "status-badge present";
            ticketStatus.innerHTML = '<i class="fa-solid fa-user-check"></i> Present (Checked In)';
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-check"></i> Approved';

            // Bersihkan kamera standby jika sukses approve
            setTimeout(() => {
                cameraText.innerText = "Camera Standby";
                cameraIcon.className = "fa-solid fa-camera camera-icon";
                cameraIcon.style.color = "#333";
            }, 1500);
        });
    }
});