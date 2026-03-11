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

    <div class="glass-card signup-card">
        <h2>Become an Event Creator</h2>
        <p class="glass-card-desc mb-30">
            Daftarkan diri Anda untuk menjadi penyelenggara acara.<br>
            Akun akan ditinjau oleh Super Admin melalui Profil Sosial Media Anda.
        </p>
        
        <?php if(isset($error)) echo "<p class='error-msg'>$error</p>"; ?>
        
        <form method="POST">
            <label class="form-label">Organization / Full Name</label>
            <input type="text" name="name" class="custom-input" required placeholder="Ex: Telkom AI Connect">
            
            <label class="form-label">Business Email</label>
            <input type="email" name="email" class="custom-input" required placeholder="contact@organization.com">
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">
                        <i class="fa-brands fa-instagram icon-ig"></i> Instagram
                    </label>
                    <input type="text" name="instagram" class="custom-input" required placeholder="@username">
                </div>
                <div class="form-col">
                    <label class="form-label">
                        <i class="fa-brands fa-tiktok icon-tt"></i> TikTok
                    </label>
                    <input type="text" name="tiktok" class="custom-input" required placeholder="@username">
                </div>
            </div>
            
            <button type="submit" class="btn-primary btn-submit-signup">Submit Request</button>
        </form>
        
        <p class="glass-card-footer">
            Sudah punya akun? <a href="admin_signin.php" class="link-green">Sign In</a>
        </p>
    </div>

</body>
</html>