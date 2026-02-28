document.addEventListener("DOMContentLoaded", function() {

    // 1. SCROLL NAVBAR EFEK
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    // ==========================================
    // 2. SIMULASI DATA SALDO & HISTORY
    // ==========================================
    let currentBalance = 1500000; // Saldo Rp 1.500.000
    const platformFeePercentage = 0.05; // Pajak 5%

    // Format Rupiah
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
    }

    // Ambil Elemen DOM
    const balanceDisplay = document.getElementById("available-balance");
    const inputAmount = document.getElementById("withdraw-amount");
    const inputMethod = document.getElementById("payout-method");
    const inputAccount = document.getElementById("payout-account");
    const btnSubmit = document.getElementById("btn-withdraw");
    const historyTbody = document.getElementById("history-tbody");

    // Teks Ringkasan Fee
    const summaryAmount = document.getElementById("summary-amount");
    const summaryFee = document.getElementById("summary-fee");
    const summaryReceive = document.getElementById("summary-receive");

    // Inisialisasi Tampilan Awal
    function updateBalanceDisplay() {
        balanceDisplay.innerText = formatRupiah(currentBalance);
    }
    updateBalanceDisplay();

    // ==========================================
    // 3A. LOGIKA PLACEHOLDER DINAMIS (BANK VS E-WALLET)
    // ==========================================
    inputMethod.addEventListener('change', function() {
        const val = this.value;
        // Jika E-Wallet (Minta Nomor HP)
        if (val === "DANA" || val === "OVO" || val === "GoPay") {
            inputAccount.placeholder = "e.g., 08123456789 (Phone Number)";
        } 
        // Jika Bank (Minta Nomor Rekening)
        else if (val === "BCA" || val === "Mandiri" || val === "BNI") {
            inputAccount.placeholder = "e.g., 1234567890 (Bank Account)";
        } 
        // Jika Kosong
        else {
            inputAccount.placeholder = "Select method first...";
        }
    });

    // ==========================================
    // 3B. LOGIKA TOMBOL "MAX" (TARIK SEMUA)
    // ==========================================
    const btnMax = document.getElementById("btn-max");
    if (btnMax) {
        btnMax.addEventListener("click", function() {
            if (currentBalance > 0) {
                // Masukkan seluruh saldo ke dalam input
                inputAmount.value = currentBalance;
                // PENTING: Panggil event 'input' secara manual agar sistem 
                // langsung menghitung potongan pajak 5% di bawahnya!
                inputAmount.dispatchEvent(new Event('input'));
            } else {
                alert("Your balance is currently zero.");
            }
        });
    }

    // ==========================================
    // 3C. KALKULASI REAL-TIME SAAT MENGETIK (PAJAK 5%)
    // ==========================================
    inputAmount.addEventListener("input", function() {
        let amountVal = parseInt(this.value);

        // Jika input dikosongkan atau diisi angka tidak valid
        if (isNaN(amountVal) || amountVal <= 0) {
            summaryAmount.innerText = "Rp 0";
            summaryFee.innerText = "- Rp 0";
            summaryReceive.innerText = "Rp 0";
            btnSubmit.disabled = true;
            return;
        }

        // Jika user mengetik angka lebih besar dari saldo, paksa kembali ke saldo maksimal
        if (amountVal > currentBalance) {
            amountVal = currentBalance;
            this.value = currentBalance; // Ubah teks di dalam kotak input
        }

        // Kalkulasi Potongan
        let fee = amountVal * platformFeePercentage;
        let receive = amountVal - fee;

        // Tampilkan ke layar
        summaryAmount.innerText = formatRupiah(amountVal);
        summaryFee.innerText = "- " + formatRupiah(fee);
        summaryReceive.innerText = formatRupiah(receive);

        // Validasi: Aktifkan tombol HANYA jika minimal narik Rp 50.000
        if (amountVal >= 50000) {
            btnSubmit.disabled = false;
        } else {
            btnSubmit.disabled = true;
        }
    });
    
    // ==========================================
    // 4. LOGIKA SUBMIT PENARIKAN
    // ==========================================
    btnSubmit.addEventListener("click", function() {
        let amountVal = parseInt(inputAmount.value);
        let methodVal = inputMethod.value;
        let accountVal = inputAccount.value;

        // Validasi form kosong
        if (!methodVal || !accountVal) {
            alert("Please select a payout method and enter your account number.");
            return;
        }

        if (amountVal < 50000) {
            alert("Minimum withdrawal is Rp 50.000");
            return;
        }

        if (amountVal > currentBalance) {
            alert("Insufficient balance.");
            return;
        }

        // Konfirmasi
        if (confirm(`Are you sure you want to withdraw ${formatRupiah(amountVal)} to ${methodVal}?`)) {
            
            // 1. Kurangi Saldo Utama
            currentBalance -= amountVal;
            updateBalanceDisplay();

            // 2. Dapatkan Tanggal Hari Ini
            const today = new Date();
            const dateString = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            // Sensor nomor rekening (Tampilkan 4 digit terakhir saja)
            const maskedAccount = "****" + accountVal.slice(-4);

            // 3. Tambahkan ke Tabel History (Status: Pending)
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
                <td>${dateString}</td>
                <td>${formatRupiah(amountVal)}</td>
                <td>${methodVal} <br><span class="acc-text">${maskedAccount}</span></td>
                <td><span class="badge-pending">Pending</span></td>
            `;
            
            // Masukkan ke posisi paling atas tabel
            historyTbody.insertBefore(newRow, historyTbody.firstChild);

            // 4. Reset Form
            inputAmount.value = "";
            inputAccount.value = "";
            inputMethod.value = "";
            
            // Reset Ringkasan Fee
            summaryAmount.innerText = "Rp 0";
            summaryFee.innerText = "- Rp 0";
            summaryReceive.innerText = "Rp 0";
            btnSubmit.disabled = true;

            alert("Withdrawal request submitted! Processing will take 1-2 business days.");
        }
    });

    // Panggil event input sekali di awal agar tombol submit mati jika input kosong
    inputAmount.dispatchEvent(new Event('input'));

});