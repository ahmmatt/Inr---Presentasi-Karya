<?php
// 1. Mulai Sesi dan Hubungkan Database
session_start();
require_once '../PHP/config/connect.php'; 

// Jika user sudah login, langsung arahkan ke discover.php agar tidak perlu login lagi
if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
    header("Location: discover.php");
    exit();
}

$error_msg = '';

// 2. Logika ketika tombol Sign In ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    // Cari user berdasarkan email
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 3. Verifikasi Password (Mencocokkan teks asli dengan Hash di database)
        if (password_verify($password, $user['password'])) {
            
            // 4. PASSWORD BENAR! Buat Session (KTP Sementara)
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user'; // Kunci Keamanan: Dia adalah User, bukan Admin

            // 5. Arahkan ke halaman utama user (Discover)
            header("Location: discover.php");
            exit();
        } else {
            // Password salah
            $error_msg = "Incorrect password. Please try again.";
        }
    } else {
        // Email tidak ditemukan
        $error_msg = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - SecureGate</title>
    <link rel="stylesheet" href="../CSS/sign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        if (localStorage.getItem('securegate_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        <div class="right-nav">
            <a href="signup.php">Sign Up</a>
        </div>
    </nav>
    <nav class="sign-card" style="margin-top: 40px;">
        <img src="../Media/SVG.png" alt="entry door image">
        <h1>Welcome Back</h1>
        <p>Please sign in below:</p>
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form action="signin.php" method="POST">
            <span class="dynamic-label-span" id="dynamic-label">Email</span> 
            <input type="email" id="input-email" name="email" placeholder="you@gmail.com" required>
            
            <span class="dynamic-label-span">Password</span>
            <input type="password" name="password" placeholder="Enter your password" required>
            
            <button type="submit" name="submit">Sign In</button>
        </form>
        <hr>
        <div class="bottom-button-sign" onclick="window.location.href='google_auth.php'">
            <i class="fab fa-google"></i>
            <a href="google_auth.php">Sign In with Google</a>
        </div>
        <div class="bottom-button-sign"><i class="fas fa-user"></i><a href="signup.php">Not Have Account?</a></div>
    </nav>

</body>
</html>