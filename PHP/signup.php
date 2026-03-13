<?php
// Mulai sesi dan hubungkan ke database
session_start();
require_once '../PHP/config/connect.php'; 

$error_msg = '';
$success_msg = '';

// Jika tombol Sign Up ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $gender = $conn->real_escape_string(trim($_POST['gender']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    // 1. Cek apakah email sudah pernah terdaftar
    $check_email = "SELECT id_user FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        // Jika email sudah ada
        $error_msg = "Email is already registered. Please sign in.";
    } else {
        // 2. Enkripsi (Hash) Password demi keamanan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Simpan ke database
        $sql = "INSERT INTO users (full_name, gender, email, password) 
                VALUES ('$name', '$gender', '$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            // Berhasil mendaftar! Arahkan ke halaman login
            echo "<script>alert('Registration successful! Please Sign In.'); window.location.href='signin.php';</script>";
            exit();
        } else {
            $error_msg = "Error: Something went wrong. " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SecureGate</title>
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
            <a href="signin.php">Sign In</a>
        </div>
    </nav>
    <nav class="sign-card" style="margin-top: 10px;">
        <img src="../Media/SVG.png" alt="entry door image">
        <h1>Welcome to SecureGate</h1>
        <p>Please enter your details to get started.</p>
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <span class="dynamic-label-span">Name</span>
            <input type="text" name="name" placeholder="Adam" required>
            
            <span class="dynamic-label-span">Gender</span>
            <input type="text" name="gender" placeholder="Male/Female" required>
            
            <span class="dynamic-label-span" id="dynamic-label">Email</span> 
            <input type="email" id="input-email" name="email" placeholder="you@gmail.com" required>
            
            <span class="dynamic-label-span">Password</span>
            <input type="password" name="password" placeholder="Use Strong Password" required>
            
            <button type="submit" name="submit">Sign Up</button>
        </form>
        <hr>
        <div class="bottom-button-sign" onclick="window.location.href='google_auth.php'">
            <i class="fab fa-google"></i>
            <a href="google_auth.php">Sign In with Google</a>
        </div>
        <div class="bottom-button-sign"><i class="fas fa-user"></i><a href="signin.php">Have Account?</a></div>
    </nav>
    <script src="../JS/sign.js"></script>
</body>
</html>