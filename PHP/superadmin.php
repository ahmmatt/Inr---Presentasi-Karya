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
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate <span class="superadmin-label">SUPER ADMIN</span></h1></div>
        <div class="main-nav"></div>
        <div class="right-nav"><a href="logout.php" class="logout-text"><i class="fa-solid fa-power-off"></i> Logout</a></div>
    </nav>

    <div class="page-frame">
        <div class="page-frame-nav">
            <h1>Command Center</h1>
            <p>Kelola Otoritas Sistem, Verifikasi Event Creator, dan Keuangan.</p>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-user-shield title-icon-blue"></i> Pending Creator Requests</h2>
            <?php if($pending_admins->num_rows > 0): ?>
                <?php while($admin = $pending_admins->fetch_assoc()): ?>
                    <div class="data-row">
                        <div class="info">
                            <h4><?= htmlspecialchars($admin['full_name']) ?></h4>
                            <p class="mb-8-grey"><?= htmlspecialchars($admin['email']) ?> · Reg: <?= date('d M Y', strtotime($admin['created_at'])) ?></p>
                            <div class="social-links">
                                <?php if(!empty($admin['instagram'])): ?><a href="https://instagram.com/<?= htmlspecialchars(str_replace('@','',$admin['instagram'])) ?>" target="_blank" class="social-link icon-ig"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
                                <?php if(!empty($admin['tiktok'])): ?><a href="https://tiktok.com/@<?= htmlspecialchars(str_replace('@','',$admin['tiktok'])) ?>" target="_blank" class="social-link icon-tiktok"><i class="fa-brands fa-tiktok"></i></a><?php endif; ?>
                            </div>
                        </div>
                        <div class="flex-gap-8">
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
                <p class="empty-text">Belum ada pendaftaran.</p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h2><i class="fa-solid fa-money-bill-transfer title-icon-green"></i> Pending Withdrawals</h2>
            <?php if($withdrawals->num_rows > 0): ?>
                <?php while($wd = $withdrawals->fetch_assoc()): ?>
                    <div class="data-row">
                        <div class="info">
                            <h4><?= htmlspecialchars($wd['full_name']) ?> (Event Admin)</h4>
                            <p class="net-revenue">Rp <?= number_format($wd['net_receive'], 0, ',', '.') ?> <span class="net-label">(Net)</span></p>
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
                <p class="empty-text">Tidak ada antrean pencairan dana.</p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="manage-acc-header">
                <h2 class="no-border-m0"><i class="fa-solid fa-users-gear title-icon-orange"></i> Manage Accounts</h2>
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
                            <h4><?= htmlspecialchars($acc['full_name']) ?> <?php if($acc['role'] == 'admin'): ?><span class="role-badge-admin">Admin</span><?php else: ?><span class="role-badge-user">User</span><?php endif; ?></h4>
                            <p><?= htmlspecialchars($acc['email']) ?></p>
                            <?php if(!empty($acc['username'])): ?><p class="user-id-text">User ID: <?= htmlspecialchars($acc['username']) ?></p><?php endif; ?>
                        </div>
                        <div class="flex-gap-8">
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
                <p class="centered-empty-text">Tidak ada data.</p>
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
                <p class="modal-subtext">Akun telah diaktifkan.</p>
                
                <?php if($_SESSION['approve_result']['is_sent']): ?>
                    <p class="status-msg-success">
                        <i class="fa-solid fa-envelope-circle-check"></i> <?= $_SESSION['approve_result']['status_msg'] ?>
                    </p>
                <?php else: ?>
                    <p class="status-msg-error">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= $_SESSION['approve_result']['status_msg'] ?>
                    </p>
                <?php endif; ?>

                <div class="cred-box">
                    <div class="cred-item">Email: <span class="cred-val"><?= $_SESSION['approve_result']['email'] ?></span></div>
                    <div class="cred-item">Username: <span class="cred-val val-username"><?= $_SESSION['approve_result']['username'] ?></span></div>
                    <div class="cred-item">Password: <span class="cred-val"><?= $_SESSION['approve_result']['password'] ?></span></div>
                </div>
                
                <p class="footer-note">Data di atas telah dikirim ke email pendaftar.</p>
            </div>
        </div>
        <?php unset($_SESSION['approve_result']); ?>
    <?php endif; ?>

    <div class="modal-overlay" id="editModal">
        <div class="modal-box text-left">
            <span class="modal-close-btn" onclick="closeEditModal()">&times;</span>
            <h2 class="mb-20">Edit Account</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="edit-group"><label>Full Name</label><input type="text" name="edit_name" id="edit_name" class="edit-input" required></div>
                <div class="edit-group"><label>Email Address</label><input type="email" name="edit_email" id="edit_email" class="edit-input" required></div>
                <div class="edit-group">
                    <label>Reset Password</label>
                    <input type="text" name="edit_password" class="edit-input" placeholder="New password...">
                    <small class="form-note">Biarkan kosong jika tidak ingin mengubah.</small>
                </div>
                <button type="submit" class="btn-action btn-green full-width-p12-mt10">Save Changes</button>
            </form>
        </div>
    </div>

    <script src="../JS/superadmin.js"></script>
</body>
</html>