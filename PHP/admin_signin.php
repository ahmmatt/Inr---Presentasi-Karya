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
</head>
<body>
    
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate <span style="color:var(--accent-green); font-size:12px;">PARTNERS</span></h1>
        </div>
    </nav>

    <div class="glass-card">
        <h2 style="margin-bottom: 10px; text-align:center; color:#fff;">Portal Access</h2>
        <p style="text-align:center; color:#888; font-size:13px; margin-bottom:25px;">Masuk menggunakan Username ID Admin Anda.</p>
        
        <?php if(isset($error)) echo "<p style='color:#ef4444; background:rgba(239,68,68,0.1); padding:10px; border-radius:8px; text-align:center; font-size:13px; margin-bottom:20px;'>$error</p>"; ?>
        
        <form method="POST">
            <label style="font-size: 13px; color:#aaa; font-weight:600;">Username ID</label>
            <input type="text" name="username" class="custom-input" required placeholder="Ex: ADM-X7Z9">
            
            <label style="font-size: 13px; color:#aaa; font-weight:600;">Password</label>
            <input type="password" name="password" class="custom-input" required>
            
            <button type="submit" class="btn-primary">Sign In to Dashboard</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:13px; color:#888;">
            Ingin membuat Event? <a href="admin_signup.php" style="color:#3b82f6; text-decoration:none; font-weight:600;">Daftar Partnership</a>
        </p>
    </div>
</body>
</html>