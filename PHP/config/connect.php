<?php
// Konfigurasi Database (Sesuaikan jika Anda menggunakan password di MySQL)
$host = "localhost";     // Server database (biasanya localhost)
$user = "root";          // Username default XAMPP/Laragon
$pass = "";              // Password default biasanya kosong
$db   = "securegate_pk"; // Nama database yang baru saja kita buat

// Membuat koneksi ke database
$conn = new mysqli($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if ($conn->connect_error) {
    // Jika gagal, hentikan program dan tampilkan pesan error
    die("Koneksi Database Gagal: " . $conn->connect_error);
}

// Set karakter encoding ke utf8mb4 agar mendukung karakter khusus (seperti Emoji)
$conn->set_charset("utf8mb4");

// Jika script sampai di baris ini, berarti koneksi sukses!

// AUTO-UPDATE STATUS EVENT KE 'ENDED'
date_default_timezone_set('Asia/Makassar');
$now = date('Y-m-d H:i:s');
// Ubah semua event yang waktu selesainya sudah lewat dari waktu saat ini
$conn->query("UPDATE events SET status = 'ended' WHERE status = 'active' AND CONCAT(end_date, ' ', end_time) < '$now'");
?>