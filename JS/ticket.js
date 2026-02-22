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
    
    // --- FITUR COPY VIRTUAL LINK ---
    const copyBtn = document.getElementById("copy-link-btn");
    const linkText = document.getElementById("virtual-link-text");

    if (copyBtn && linkText) {
        copyBtn.addEventListener("click", function(e) {
            e.preventDefault(); 
            e.stopPropagation(); // Mencegah klik menyebar ke elemen lain
            
            // Perintah menyalin teks ke clipboard perangkat
            navigator.clipboard.writeText(linkText.innerText).then(() => {
                
                // Ubah ikon menjadi tanda centang (berhasil)
                copyBtn.className = "fa-solid fa-check copy-success";
                
                // Kembalikan ikon ke bentuk copy setelah 2 detik
                setTimeout(() => {
                    copyBtn.className = "fa-regular fa-copy";
                }, 2000);
                
            }).catch(err => {
                console.error("Gagal menyalin link: ", err);
            });
        });
    }

});
document.addEventListener("DOMContentLoaded", function() {
    
    // Elemen-elemen Modal
    const openModalBtn = document.getElementById("open-register-modal");
    const closeModalBtn = document.getElementById("close-register-modal");
    const registerModal = document.getElementById("register-modal");
    
    // Elemen-elemen Tahapan (Steps)
    const step1 = document.getElementById("step-1-form");
    const step2 = document.getElementById("step-2-payment");
    const step3 = document.getElementById("step-3-success");
    
    // Elemen-elemen Aksi
    const regForm = document.getElementById("registration-form");
    const donePaymentBtn = document.getElementById("btn-payment-done");
    const closeSuccessBtn = document.getElementById("btn-close-success");
    const timerDisplay = document.getElementById("countdown-timer");
    
    let countdownInterval;

    // FUNGSI 1: Membuka Modal (Mulai dari Step 1)
    if (openModalBtn) {
        openModalBtn.addEventListener("click", function(e) {
            e.preventDefault();
            registerModal.classList.add("show");
            
            // Reset ke tampilan awal form setiap kali dibuka
            step1.style.display = "flex";
            step2.style.display = "none";
            step3.style.display = "none";
            
            // Opsional: Kosongkan form input
            if(regForm) regForm.reset(); 
        });
    }

    // FUNGSI 2: Menutup Modal dan Menghentikan Timer
    function closeModal() {
        registerModal.classList.remove("show");
        clearInterval(countdownInterval); 
    }
    
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (closeSuccessBtn) closeSuccessBtn.addEventListener("click", closeModal);

    // FUNGSI 3: Submit Form -> Lanjut ke Pembayaran QRIS & Mulai Timer
    if (regForm) {
        regForm.addEventListener("submit", function(e) {
            e.preventDefault(); // Mencegah browser reload halaman
            
            step1.style.display = "none";  // Sembunyikan form
            step2.style.display = "flex";  // Tampilkan QRIS
            
            startCountdown(10 * 60); // Mulai timer 10 menit (600 detik)
        });
    }

    // FUNGSI 4: Klik "Done" -> Lanjut ke Pesan Sukses
    if (donePaymentBtn) {
        donePaymentBtn.addEventListener("click", function() {
            clearInterval(countdownInterval); // Hentikan timer karena sudah bayar
            
            step2.style.display = "none";  // Sembunyikan QRIS
            step3.style.display = "flex";  // Tampilkan Centang Sukses
        });
    }

    // FUNGSI 5: Logika Timer Hitung Mundur
    function startCountdown(duration) {
        let timer = duration, minutes, seconds;
        
        // Bersihkan interval lama jika ada
        clearInterval(countdownInterval);
        
        countdownInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            // Tambahkan angka 0 di depan jika di bawah 10 (misal: 09:05)
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            timerDisplay.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(countdownInterval);
                timerDisplay.textContent = "EXPIRED";
                timerDisplay.style.color = "#ef4444"; // Berubah merah jika habis
            }
        }, 1000);
    }
});