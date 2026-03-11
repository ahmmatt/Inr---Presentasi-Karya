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