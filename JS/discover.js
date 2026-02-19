document.addEventListener("DOMContentLoaded", function() {
    // Ambil elemen yang dibutuhkan
    const track = document.querySelector('.upcoming-event');
    const leftBtn = document.getElementById('slide-left');
    const rightBtn = document.getElementById('slide-right');

    // Menghitung jarak geser (lebar card 300px + gap 20px)
    const scrollAmount = 320; 

    // Fungsi klik tombol Kanan
    rightBtn.addEventListener('click', () => {
        track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    // Fungsi klik tombol Kiri
    leftBtn.addEventListener('click', () => {
        track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    // Fungsi untuk memantau kapan tombol harus muncul/hilang
    track.addEventListener('scroll', () => {
        // 1. Logika Tombol Kiri: Muncul jika sudah digeser dari ujung kiri (> 0)
        if (track.scrollLeft > 0) {
            leftBtn.style.display = 'flex'; // Munculkan tombol
        } else {
            leftBtn.style.display = 'none'; // Sembunyikan jika mentok kiri
        }

        // 2. Logika Tombol Kanan: Hilang jika sudah mentok ujung kanan
        // Menghitung batas maksimal scroll
        const maxScroll = track.scrollWidth - track.clientWidth;
        
        // Kita beri toleransi 5 pixel karena kadang perhitungan browser ada desimal
        if (track.scrollLeft >= maxScroll - 5) {
            rightBtn.style.display = 'none'; // Sembunyikan jika mentok kanan
        } else {
            rightBtn.style.display = 'flex'; // Munculkan tombol
        }
    });
});