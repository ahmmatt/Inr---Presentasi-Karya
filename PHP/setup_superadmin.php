<?php
require_once 'config/connect.php';

$nama = "Sang Maha Admin";
$email = "super@securegate.com"; 
$password_asli = "SuperRahasia123!"; // Ini password login Super Admin
$password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$nama', '$email', '$password_hash', 'superadmin')";

if ($conn->query($sql)) {
    echo "<h1 style='color:green; font-family:sans-serif; text-align:center; margin-top:50px;'>SUPER ADMIN BERHASIL DIBUAT!</h1>";
    echo "<p style='text-align:center;'>Silakan HAPUS file setup_superadmin.php ini sekarang juga.</p>";
} else {
    echo "Gagal: " . $conn->error;
}
?>