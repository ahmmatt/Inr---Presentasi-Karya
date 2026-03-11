<?php
session_start();
require_once 'config/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Tangkap input (Hanya Username)
    $username = $conn->real_escape_string($_POST['username']); 
    $password = $_POST['password'];

    // 2. Query Database: HANYA MENCARI BERDASARKAN USERNAME
    // Email tidak akan terbaca di sini
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            // FILTER BERDASARKAN ROLE
            if ($user['role'] == 'pending_admin') {
                $error = "Akun Anda masih ditinjau oleh Super Admin.";
            } 
            elseif ($user['role'] == 'user') {
                $error = "Akses ditolak. Ini portal khusus Partner/Admin.";
            }
            else {
                // Berhasil login! Set session
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];
                
                // Redirect sesuai role
                if ($user['role'] == 'superadmin') {
                    header("Location: superadmin.php");
                } else { 
                    header("Location: adminevent.php");
                }
                exit();
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username ID tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureGate - Partner Access</title>
    <link rel="stylesheet" href="../CSS/adminsign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        if (localStorage.getItem('securegate_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>
    
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate <span>PARTNERS</span></h1>
        </div>
    </nav>

    <div class="glass-card">
        <h2>Portal Access</h2>
        <p class="glass-card-desc">Masuk menggunakan Username ID Admin Anda.</p>
        
        <?php if(isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
        
        <form method="POST">
            <label class="form-label">Username ID</label>
            <input type="text" name="username" class="custom-input" required placeholder="Ex: ADM-X7Z9">
            
            <label class="form-label">Password</label>
            <input type="password" name="password" class="custom-input" required>
            
            <button type="submit" class="btn-primary">Sign In to Dashboard</button>
        </form>
        
        <p class="glass-card-footer">
            Ingin membuat Event? <a href="admin_signup.php" class="link-blue">Daftar Partnership</a>
        </p>
    </div>
</body>
</html>