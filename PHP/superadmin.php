<?php
session_start();
require_once 'config/connect.php';

// =========================================================
// LOAD PHPMAILER
// =========================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Cek Sesi Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: admin_signin.php");
    exit();
}

// =========================================================
// 1. LOGIKA: APPROVE ADMIN + AUTO SEND EMAIL
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_admin') {
    $user_id = (int)$_POST['target_id'];
    $email_penerima = $conn->real_escape_string($_POST['target_email']);
    $nama_penerima = $conn->real_escape_string($_POST['target_name']); 
    
    // A. Generate Username & Password
    $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $new_username = "ADM-" . $suffix;

    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#';
    $raw_password = substr(str_shuffle($chars), 0, 10);
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
    
    // B. Update Database
    $update = $conn->query("UPDATE users SET role = 'admin', username = '$new_username', password = '$hashed_password' WHERE id_user = $user_id");
    
    if ($update) {
        // C. KIRIM EMAIL OTOMATIS DENGAN PHPMAILER
        $mail = new PHPMailer(true);
        $email_status = "Gagal mengirim email.";

        try {
            // Konfigurasi Server (SESUAIKAN APP PASSWORD ANDA)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'basyirsinjai@gmail.com'; 
            $mail->Password   = 'vxfj rntd snow ilve'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('no-reply@securegate.com', 'SecureGate Admin');
            $mail->addAddress($email_penerima, $nama_penerima);

            $mail->isHTML(true);
            $mail->Subject = 'Selamat! Akun Event Creator Anda Aktif';
            
            $mail->Body    = "
                <h3>Halo, $nama_penerima</h3>
                <p>Selamat! Permintaan Anda menjadi Event Creator di SecureGate telah disetujui.</p>
                <p>Silakan login menggunakan kredensial berikut:</p>
                <table style='background:#f4f4f4; padding:15px; border-radius:10px; width:100%;'>
                    <tr><td><strong>Username:</strong></td> <td>$new_username</td></tr>
                    <tr><td><strong>Password:</strong></td> <td>$raw_password</td></tr>
                </table>
                <p>Harap segera ganti password Anda setelah login.</p>
                <br>
                <p>Salam,<br>SecureGate Team</p>
            ";
            
            $mail->send();
            $email_status = "Email kredensial berhasil dikirim otomatis!";
            $email_success = true;

        } catch (Exception $e) {
            $email_status = "Gagal kirim email. Error: {$mail->ErrorInfo}";
            $email_success = false;
        }

        $_SESSION['approve_result'] = [
            'username' => $new_username,
            'password' => $raw_password,
            'email' => $email_penerima,
            'status_msg' => $email_status,
            'is_sent' => $email_success
        ];
    }
    
    header("Location: superadmin.php");
    exit();
}

// =========================================================
// 2. LOGIKA: REJECT ADMIN
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_admin') {
    $reject_id = (int)$_POST['target_id'];
    $conn->query("DELETE FROM users WHERE id_user = $reject_id");
    header("Location: superadmin.php");
    exit();
}

// =========================================================
// 3. LOGIKA: EDIT USER
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $edit_id = (int)$_POST['edit_id'];
    $edit_name = $conn->real_escape_string($_POST['edit_name']);
    $edit_email = $conn->real_escape_string($_POST['edit_email']);
    $new_pass = $_POST['edit_password'];
    
    $query = "UPDATE users SET full_name = '$edit_name', email = '$edit_email'";
    if (!empty($new_pass)) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $query .= ", password = '$new_hash'";
    }
    $query .= " WHERE id_user = $edit_id";
    $conn->query($query);
    echo "<script>alert('Data diperbarui!'); window.location.href='superadmin.php';</script>";
    exit();
}

// =========================================================
// 4. LOGIKA: APPROVE WITHDRAWAL & DELETE ACCOUNT
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $del_id = (int)$_POST['target_id'];
    $conn->query("DELETE FROM users WHERE id_user = $del_id");
    header("Location: superadmin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_withdrawal') {
    $w_id = (int)$_POST['withdrawal_id'];
    $conn->query("UPDATE withdrawals SET status = 'done' WHERE id_withdrawal = $w_id");
    header("Location: superadmin.php");
    exit();
}

// =========================================================
// 5. DATA FETCHING
// =========================================================
$pending_admins = $conn->query("SELECT * FROM users WHERE role = 'pending_admin'");
$withdrawals = $conn->query("SELECT w.*, u.full_name FROM withdrawals w JOIN users u ON w.id_admin = u.id_user WHERE w.status = 'pending'");

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if ($filter === 'admin') {
    $sql_acc = "SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC";
} elseif ($filter === 'user') {
    $sql_acc = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
} else {
    $sql_acc = "SELECT * FROM users WHERE role IN ('admin', 'user') ORDER BY role ASC";
}
$all_accounts = $conn->query($sql_acc);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SecureGate - Super Admin</title>
    <link rel="stylesheet" href="../CSS/superadmin.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); z-index: 2000; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
        .modal-box { background: #121212; border: 1px solid #333; border-radius: 20px; padding: 40px 30px; width: 90%; max-width: 480px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); text-align: center; color: #fff; position: relative; }
        .modal-close-btn { position: absolute; top: 15px; right: 20px; font-size: 24px; color: #666; cursor: pointer; transition: color 0.2s, transform 0.2s; }
        .modal-close-btn:hover { color: #fff; transform: rotate(90deg); }
        .icon-success { font-size: 60px; color: #22c55e; margin-bottom: 20px; animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards; }
        .cred-box { background: #1a1a1a; border: 1px dashed #444; border-radius: 12px; padding: 20px; margin: 25px 0; text-align: left; }
        .cred-item { margin-bottom: 8px; font-size: 13px; color: #aaa; }
        .cred-val { color: #fff; font-weight: bold; font-family: monospace; font-size: 15px; margin-left: 5px; letter-spacing: 0.5px;}
        .val-username { color: #f97316; font-size: 16px; } 
        .social-link { font-size: 18px; margin-right: 12px; transition: transform 0.2s; text-decoration: none; display: inline-block; }
        .social-link:hover { transform: scale(1.2); }
        .icon-ig { color: #E1306C; }
        .icon-tiktok { color: #00f2ea; text-shadow: 2px 2px 0px #ff0050; }
        .no-social { color: #444; font-size: 11px; font-style: italic; }
        .filter-group { display: flex; background: #0a0a0a; padding: 4px; border-radius: 8px; border: 1px solid #333; }
        .filter-btn { text-decoration: none; color: #888; font-size: 12px; font-weight: 600; padding: 6px 16px; border-radius: 6px; transition: all 0.3s ease; }
        .filter-btn:hover { color: #fff; }
        .filter-btn.active { background: #333; color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .edit-group { text-align: left; margin-bottom: 15px; }
        .edit-group label { display: block; font-size: 12px; color: #aaa; margin-bottom: 5px; }
        .edit-input { width: 100%; padding: 10px; background: #0a0a0a; border: 1px solid #333; color: #fff; border-radius: 6px; outline: none; }
        .edit-input:focus { border-color: #22c55e; }
        .btn-action { padding: 8px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; font-size: 13px; color: white; display: inline-flex; align-items: center; gap: 6px;}
        .btn-green { background: var(--accent-green); color: black; }
        .btn-blue { background: #3b82f6; }
        .btn-red { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .btn-red:hover { background: #ef4444; color: white; }

        @keyframes bounceIn { 0% { transform: scale(0); opacity: 0; } 60% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(1); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate <span style="color:var(--accent-orange); font-size:12px;">SUPER ADMIN</span></h1></div>
        <div class="main-nav"></div>
        <div class="right-nav"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-power-off"></i> Logout</a></div>
    </nav>

    <div class="page-frame">
        <div class="page-frame-nav">
            <h1>Command Center</h1>
            <p>Kelola Otoritas Sistem, Verifikasi Event Creator, dan Keuangan.</p>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-user-shield" style="color: #3b82f6; margin-right:8px;"></i> Pending Creator Requests</h2>
            <?php if($pending_admins->num_rows > 0): ?>
                <?php while($admin = $pending_admins->fetch_assoc()): ?>
                    <div class="data-row">
                        <div class="info">
                            <h4><?= htmlspecialchars($admin['full_name']) ?></h4>
                            <p style="margin-bottom: 8px; color: #888; font-size: 12px;"><?= htmlspecialchars($admin['email']) ?> · Reg: <?= date('d M Y', strtotime($admin['created_at'])) ?></p>
                            <div class="social-links">
                                <?php if(!empty($admin['instagram'])): ?><a href="https://instagram.com/<?= htmlspecialchars(str_replace('@','',$admin['instagram'])) ?>" target="_blank" class="social-link icon-ig"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
                                <?php if(!empty($admin['tiktok'])): ?><a href="https://tiktok.com/@<?= htmlspecialchars(str_replace('@','',$admin['tiktok'])) ?>" target="_blank" class="social-link icon-tiktok"><i class="fa-brands fa-tiktok"></i></a><?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <form method="POST" onsubmit="return confirm('Setujui dan KIRIM EMAIL OTOMATIS?');">
                                <input type="hidden" name="action" value="approve_admin">
                                <input type="hidden" name="target_id" value="<?= $admin['id_user'] ?>">
                                <input type="hidden" name="target_email" value="<?= htmlspecialchars($admin['email']) ?>">
                                <input type="hidden" name="target_name" value="<?= htmlspecialchars($admin['full_name']) ?>">
                                <button type="submit" class="btn-action btn-green"><i class="fa-solid fa-bolt"></i> Approve & Auto-Send</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Tolak dan Hapus?');">
                                <input type="hidden" name="action" value="reject_admin">
                                <input type="hidden" name="target_id" value="<?= $admin['id_user'] ?>">
                                <button type="submit" class="btn-action btn-red"><i class="fa-solid fa-xmark"></i> Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #888; font-style: italic;">Belum ada pendaftaran.</p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-money-bill-transfer" style="color: var(--accent-green); margin-right:8px;"></i> Pending Withdrawals</h2>
            <?php if($withdrawals->num_rows > 0): ?>
                <?php while($wd = $withdrawals->fetch_assoc()): ?>
                    <div class="data-row">
                        <div class="info">
                            <h4><?= htmlspecialchars($wd['full_name']) ?> (Event Admin)</h4>
                            <p style="color: var(--accent-green); font-weight: bold; font-size: 16px;">Rp <?= number_format($wd['net_receive'], 0, ',', '.') ?> <span style="color:#666; font-weight:normal; font-size:12px;">(Net)</span></p>
                            <p><?= htmlspecialchars($wd['payout_method']) ?> - <?= htmlspecialchars($wd['payout_account']) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Tandai bahwa dana ini SUDAH Anda transfer?');">
                            <input type="hidden" name="action" value="approve_withdrawal">
                            <input type="hidden" name="withdrawal_id" value="<?= $wd['id_withdrawal'] ?>">
                            <button type="submit" class="btn-action btn-blue"><i class="fa-solid fa-check-double"></i> Transferred</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #888; font-style: italic;">Tidak ada antrean pencairan dana.</p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px;">
                <h2 style="border: none; margin: 0; padding: 0;"><i class="fa-solid fa-users-gear" style="color: #f97316; margin-right:8px;"></i> Manage Accounts</h2>
                <div class="filter-group">
                    <a href="superadmin.php?filter=all" class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>">All</a>
                    <a href="superadmin.php?filter=admin" class="filter-btn <?= $filter == 'admin' ? 'active' : '' ?>">Admins</a>
                    <a href="superadmin.php?filter=user" class="filter-btn <?= $filter == 'user' ? 'active' : '' ?>">Users</a>
                </div>
            </div>
            <?php if($all_accounts->num_rows > 0): ?>
                <?php while($acc = $all_accounts->fetch_assoc()): ?>
                    <div class="data-row">
                        <div class="info">
                            <h4><?= htmlspecialchars($acc['full_name']) ?> <?php if($acc['role'] == 'admin'): ?><span style="background:#f97316; padding:2px 8px; border-radius:4px; font-size:10px; color:#fff; margin-left:6px;">Admin</span><?php else: ?><span style="background:#333; padding:2px 8px; border-radius:4px; font-size:10px; color:#aaa; margin-left:6px;">User</span><?php endif; ?></h4>
                            <p><?= htmlspecialchars($acc['email']) ?></p>
                            <?php if(!empty($acc['username'])): ?><p style="font-size:11px; color:#f97316; margin-top:2px;">User ID: <?= htmlspecialchars($acc['username']) ?></p><?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn-action btn-blue" onclick="openEditModal('<?= $acc['id_user'] ?>', '<?= htmlspecialchars($acc['full_name']) ?>', '<?= htmlspecialchars($acc['email']) ?>')"><i class="fa-solid fa-pen"></i> Edit</button>
                            <form method="POST" onsubmit="return confirm('Hapus akun ini permanen?');">
                                <input type="hidden" name="action" value="delete_account">
                                <input type="hidden" name="target_id" value="<?= $acc['id_user'] ?>">
                                <button type="submit" class="btn-action btn-red"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #888; font-style: italic; text-align: center; padding: 20px;">Tidak ada data.</p>
            <?php endif; ?>
        </div>
        <hr>
        <div class="page-footer">
            <div class="left-footer">
                <a href="">Discover</a>
                <a href="">Help</a>
            </div>
            <div class="right-footer">
                <a href=""><i class="fab fa-x"></i></a>
                <a href=""><i class="fab fa-youtube"></i></a>
                <a href=""><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['approve_result'])): ?>
        <div class="modal-overlay active" id="successModal">
            <div class="modal-box">
                <span class="modal-close-btn" onclick="closeSuccessModal()">&times;</span>
                <i class="fa-solid fa-check-circle icon-success"></i>
                
                <h2>Proses Selesai</h2>
                <p style="color:#ccc; font-size:14px; margin-bottom: 5px;">Akun telah diaktifkan.</p>
                
                <?php if($_SESSION['approve_result']['is_sent']): ?>
                    <p style="color:#22c55e; font-size:13px; font-weight:bold; margin-bottom: 20px;">
                        <i class="fa-solid fa-envelope-circle-check"></i> <?= $_SESSION['approve_result']['status_msg'] ?>
                    </p>
                <?php else: ?>
                    <p style="color:#ef4444; font-size:13px; font-weight:bold; margin-bottom: 20px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= $_SESSION['approve_result']['status_msg'] ?>
                    </p>
                <?php endif; ?>

                <div class="cred-box">
                    <div class="cred-item">Email: <span class="cred-val"><?= $_SESSION['approve_result']['email'] ?></span></div>
                    <div class="cred-item">Username: <span class="cred-val val-username"><?= $_SESSION['approve_result']['username'] ?></span></div>
                    <div class="cred-item">Password: <span class="cred-val"><?= $_SESSION['approve_result']['password'] ?></span></div>
                </div>
                
                <p style="font-size:11px; color:#666;">Data di atas telah dikirim ke email pendaftar.</p>
            </div>
        </div>
        <?php unset($_SESSION['approve_result']); ?>
    <?php endif; ?>

    <div class="modal-overlay" id="editModal">
        <div class="modal-box" style="text-align: left;">
            <span class="modal-close-btn" onclick="closeEditModal()">&times;</span>
            <h2 style="margin-bottom: 20px;">Edit Account</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="edit-group"><label>Full Name</label><input type="text" name="edit_name" id="edit_name" class="edit-input" required></div>
                <div class="edit-group"><label>Email Address</label><input type="email" name="edit_email" id="edit_email" class="edit-input" required></div>
                <div class="edit-group"><label>Reset Password</label><input type="text" name="edit_password" class="edit-input" placeholder="New password..."><small style="color: #666; font-size: 11px;">Biarkan kosong jika tidak ingin mengubah.</small></div>
                <button type="submit" class="btn-action btn-green" style="width: 100%; padding: 12px; margin-top: 10px;">Save Changes</button>
            </form>
        </div>
    </div>

    

    <script src="../JS/superadmin.js"></script>
</body>
</html>