// Jadikan SATU wadah utama agar semua kode menunggu HTML selesai dimuat
document.addEventListener("DOMContentLoaded", () => {
    
    // ==========================================
    // 1. LOGIKA NAVBAR EFEK KACA
    // ==========================================
    const navbar = document.querySelector('.navbar');

    // Pengecekan: Hanya jalankan jika ada navbar di halaman ini
    if (navbar) {
        window.addEventListener('scroll', () => {
            // Jauh lebih ringkas: tambah 'scrolled' jika scroll > 50, hapus jika tidak
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

});

function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function openEditModal(id, name, email) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }