<?php
require_once 'config/connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Gunakan null coalescing operator (?? '') untuk mencegah error jika data kosong
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    
    // Tangkap data Sosmed (Tambahkan pengecekan isset agar tidak error)
    $instagram = isset($_POST['instagram']) ? $conn->real_escape_string($_POST['instagram']) : '';
    $tiktok = isset($_POST['tiktok']) ? $conn->real_escape_string($_POST['tiktok']) : '';
    
    // Validasi sederhana
    if(empty($name) || empty($email) || empty($instagram) || empty($tiktok)) {
        $error = "Semua kolom wajib diisi!";
    } else {
        // Cek email apakah sudah dipakai
        $cek = $conn->query("SELECT * FROM users WHERE email = '$email'");
        if($cek->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Beri password dummy acak
            $dummy_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            
            // INSERT data lengkap termasuk sosmed
            $sql = "INSERT INTO users (full_name, email, password, instagram, tiktok, role) 
                    VALUES ('$name', '$email', '$dummy_pass', '$instagram', '$tiktok', 'pending_admin')";
            
            if($conn->query($sql)){
                header("Location: ../HTML/adminwaiting.html");
                exit();
            } else {
                $error = "Terjadi kesalahan sistem: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureGate - Partner Registration</title>
    <link rel="stylesheet" href="../CSS/adminsign.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate <span style="color:var(--accent-green); font-size:12px;">PARTNERS</span></h1>
        </div>
    </nav>

    <div class="glass-card" style="margin-top: 20px;">
        <h2 style="margin-bottom: 10px; text-align:center; color: #fff;">Become an Event Creator</h2>
        <p style="color: var(--text-secondary); text-align:center; font-size:13px; margin-bottom: 30px; line-height: 1.5;">
            Daftarkan diri Anda untuk menjadi penyelenggara acara.<br>
            Akun akan ditinjau oleh Super Admin melalui Profil Sosial Media Anda.
        </p>
        
        <?php if(isset($error)) echo "<p style='color:#ef4444; background:rgba(239,68,68,0.1); padding:10px; border-radius:8px; text-align:center; font-size:13px; margin-bottom:20px;'>$error</p>"; ?>
        
        <form method="POST">
            <label style="font-size: 13px; font-weight: 600; color: #ccc;">Organization / Full Name</label>
            <input type="text" name="name" class="custom-input" required placeholder="Ex: Telkom AI Connect">
            
            <label style="font-size: 13px; font-weight: 600; color: #ccc;">Business Email</label>
            <input type="email" name="email" class="custom-input" required placeholder="contact@organization.com">
            
            <div style="display: flex; gap: 15px; margin-top: 5px;">
                <div style="flex: 1;">
                    <label style="font-size: 13px; font-weight: 600; color: #ccc;">
                        <i class="fa-brands fa-instagram" style="color: #E1306C; margin-right: 4px;"></i> Instagram
                    </label>
                    <input type="text" name="instagram" class="custom-input" required placeholder="@username">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 13px; font-weight: 600; color: #ccc;">
                        <i class="fa-brands fa-tiktok" style="color: #00f2ea; margin-right: 4px;"></i> TikTok
                    </label>
                    <input type="text" name="tiktok" class="custom-input" required placeholder="@username">
                </div>
            </div>
            
            <button type="submit" class="btn-primary" style="margin-top: 10px;">Submit Request</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:13px; color:#888;">
            Sudah punya akun? <a href="admin_signin.php" style="color:var(--accent-green); text-decoration: none; font-weight: 600;">Sign In</a>
        </p>
    </div>

</body>
</html>