document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA NAVBAR EFEK KACA
    // ==========================================
    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});

/**
 * Menutup Modal Sukses (Notifikasi Email)
 */
function closeSuccessModal() {
    const successModal = document.getElementById('successModal');
    if (successModal) {
        successModal.style.display = 'none';
        successModal.classList.remove('active');
    }
}

/**
 * Membuka Modal Edit User
 * @param {string} id 
 * @param {string} name 
 * @param {string} email 
 */
function openEditModal(id, name, email) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('editModal').classList.add('active');
}

/**
 * Menutup Modal Edit User
 */
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// ==========================================
// 2. LOGIKA THEME TOGGLE (DARK / LIGHT MODE)
// ==========================================
const themeToggleBtn = document.getElementById('theme-toggle');

if (themeToggleBtn) {
    // Cek status saat halaman dimuat
    if (localStorage.getItem('securegate_theme') === 'light') {
        themeToggleBtn.classList.add('active'); // Posisi switch ke kanan (terang)
    }

    // Event saat tombol diklik
    themeToggleBtn.addEventListener('click', () => {
        // Toggle class di HTML
        document.documentElement.classList.toggle('light-mode');
        
        // Animasi tombol
        themeToggleBtn.classList.toggle('active');

        // Simpan preferensi ke LocalStorage
        if (document.documentElement.classList.contains('light-mode')) {
            localStorage.setItem('securegate_theme', 'light');
        } else {
            localStorage.setItem('securegate_theme', 'dark');
        }
    });
}