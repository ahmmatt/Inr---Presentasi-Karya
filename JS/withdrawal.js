document.addEventListener("DOMContentLoaded", function() {

    // ==========================================
    // 1. SCROLL NAVBAR EFEK KACA
    // ==========================================
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    // ==========================================
    // 2. LOGIKA DROPDOWN PROFILE
    // ==========================================
    const profileTrigger = document.getElementById('profile-dropdown-trigger');
    const profileMenu = document.getElementById('profile-dropdown-menu');

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        });

        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.style.display = 'none';
            }
        });
    }

    // ==========================================
    // 3. KALKULASI PENARIKAN & PAJAK (FEE)
    // ==========================================
    const inputAmount = document.getElementById("withdraw-amount");
    const inputMethod = document.getElementById("payout-method");
    const inputAccount = document.getElementById("payout-account");
    const btnSubmit = document.getElementById("btn-withdraw");
    const btnMax = document.getElementById("btn-max");

    // Ambil variabel dari PHP (pastikan menjadi integer)
    const currentBalance = typeof maxBalancePHP !== 'undefined' ? parseInt(maxBalancePHP) : 0;
    const platformFeePercentage = 0.08; // Pajak 8%

    // Format angka jadi Rupiah sesuai standar EYD (Rp15.500,00)
    function formatRp(angka) {
        const cleanNumber = parseInt(angka) || 0;
        let formattedRibuan = cleanNumber.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return "Rp" + formattedRibuan + ",00";
    }

    // Fungsi Kalkulator
    function calculateFee() {
        if (!inputAmount) return;
        
        let val = parseInt(inputAmount.value) || 0;
        
        // Cegah angka negatif
        if (val < 0) {
            val = 0;
            inputAmount.value = '';
        }

        // Cegah input melebihi saldo yang ada
        if (val > currentBalance) {
            val = currentBalance;
            inputAmount.value = currentBalance;
        }

        // Kalkulasi: Potongan 8% dibulatkan ke BAWAH (Math.floor)
        let fee = Math.floor(val * platformFeePercentage);
        let net = val - fee; 

        // Tampilkan ke layar dengan format Rupiah EYD
        document.getElementById('summary-amount').innerText = formatRp(val);
        document.getElementById('summary-fee').innerText = "- " + formatRp(fee);
        document.getElementById('summary-receive').innerText = formatRp(net);

        // Aktifkan tombol submit HANYA jika nominal >= 50.000
        if (btnSubmit) {
            btnSubmit.disabled = val < 50000;
        }
    }

    // A. Event saat mengetik angka
    if (inputAmount) {
        inputAmount.addEventListener("input", calculateFee);
    }

    // B. Event Tombol MAX (Ambil Semua Uang)
    if (btnMax && inputAmount) {
        btnMax.addEventListener("click", function() {
            if (currentBalance > 0) {
                inputAmount.value = currentBalance;
                calculateFee();
            } else {
                alert("Saldo event Anda kosong.");
            }
        });
    }

    // ==========================================
    // 4. PLACEHOLDER DINAMIS UNTUK BANK/E-WALLET
    // ==========================================
    if (inputMethod && inputAccount) {
        inputMethod.addEventListener('change', function() {
            const val = this.value;
            if (val === "DANA" || val === "OVO" || val === "GoPay") {
                inputAccount.placeholder = "e.g., 08123456789 (Phone Number)";
            } else if (val === "BCA" || val === "Mandiri" || val === "BNI" || val === "BRI") {
                inputAccount.placeholder = "e.g., 1234567890 (Bank Account)";
            } else {
                inputAccount.placeholder = "Select method first...";
            }
        });
    }

    // ==========================================
    // 5. LOGIKA SUBMIT PENARIKAN (YANG SEMPAT HILANG)
    // ==========================================
    if (btnSubmit) {
        btnSubmit.addEventListener("click", function(e) {
            let amountVal = parseInt(inputAmount.value) || 0;
            let methodVal = inputMethod.value;
            let accountVal = inputAccount.value;

            // Jika input ada yang kosong, biarkan HTML5 "required" memunculkan peringatan
            if (!methodVal || !accountVal || amountVal < 50000 || amountVal > currentBalance) {
                return; 
            }

            // Hitung bersih yang akan diterima
            let fee = Math.floor(amountVal * platformFeePercentage);
            let netReceive = amountVal - fee;

            // Konfirmasi ke User sebelum data dikirim ke PHP
            let confirmMsg = `Anda akan menarik dana sebesar ${formatRp(amountVal)} ke ${methodVal}.\n\nSetelah dipotong biaya platform 8%, Anda akan menerima bersih: ${formatRp(netReceive)}.\n\nLanjutkan?`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault(); // Batalkan submit form jika user klik "Cancel"
            }
            // Jika user klik "OK", form otomatis tersubmit ke withdrawal.php dan halaman akan me-reload
            // (Tabel history akan diperbarui secara otomatis oleh PHP setelah reload)
        });
    }

});