<?php
session_start();
// Hancurkan semua data sesi
session_unset();
session_destroy();

// Arahkan kembali ke halaman portal
header("Location: ../HTML/landingpage.html"); // Sesuaikan dengan halaman depan / signin Anda
exit();
?>