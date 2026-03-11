<?php
session_start();
require_once 'config/connect.php';

// 1. Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_signin.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$nav_initial = strtoupper(substr($admin_name, 0, 1));

// 2. Tangkap Event ID
if (!isset($_GET['event_id'])) {
    echo "<script>alert('Pilih event terlebih dahulu!'); window.location.href='adminevent.php';</script>";
    exit();
}
$event_id = (int)$_GET['event_id'];

// 3. Hitung Saldo & Validasi
// Total Pendapatan Kotor (Hanya yang statusnya Approved / Checked-in)
$rev_query = $conn->query("SELECT SUM(t.price) as total_rev FROM attendees a JOIN ticket_tiers t ON a.id_tier = t.id_tier WHERE a.id_event = $event_id AND a.status IN ('approved', 'checked_in')");
$total_revenue = $rev_query->fetch_assoc()['total_rev'] ?? 0;

// Total yang sudah pernah ditarik (agar tidak bisa ditarik dua kali)
$wd_query = $conn->query("SELECT SUM(amount) as total_wd FROM withdrawals WHERE id_event = $event_id AND status != 'rejected'");
$total_withdrawn = $wd_query->fetch_assoc()['total_wd'] ?? 0;

// Sisa Saldo Tersedia
$available_balance = $total_revenue - $total_withdrawn;

// 4. Proses Formulir Penarikan (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
    $amount = (float)$_POST['withdraw_amount'];
    $method = $conn->real_escape_string($_POST['payout_method']);
    $account = $conn->real_escape_string($_POST['payout_account']);

    if (empty($method) || empty($account)) {
        $error = "Metode Pembayaran dan Nomor Rekening wajib diisi!";
    } elseif ($amount < 50000) {
        $error = "Minimal penarikan adalah Rp 50.000";
    } elseif ($amount > $available_balance) {
        $error = "Saldo tidak mencukupi!";
    } else {
        // Hitung Potongan 5% (Dibulatkan ke Bawah) & Bersih
        $platform_fee = floor($amount * 0.08);
        $net_receive = $amount - $platform_fee;

        // Simpan Permintaan ke Database
        $sql = "INSERT INTO withdrawals (id_admin, id_event, amount, platform_fee, net_receive, payout_method, payout_account, status) 
                VALUES ($admin_id, $event_id, $amount, $platform_fee, $net_receive, '$method', '$account', 'pending')";
        
        if ($conn->query($sql)) {
            $success = "Permintaan penarikan berhasil dikirim! Menunggu proses Super Admin.";
            $available_balance -= $amount; // Kurangi saldo tampilan secara instan
        } else {
            $error = "Gagal memproses penarikan: " . $conn->error;
        }
    }
}

// 5. Ambil Riwayat Penarikan
$history = [];
$hist_query = $conn->query("SELECT * FROM withdrawals WHERE id_event = $event_id ORDER BY created_at DESC");
if ($hist_query) {
    while($row = $hist_query->fetch_assoc()) {
        $history[] = $row;
    }
}

// Fungsi Format Rupiah
// Fungsi Format Rupiah (Standar EYD)
function formatRupiah($num) { 
    // Format: Rp(angka_dengan_titik),00
    return "Rp" . number_format($num, 2, ',', '.'); 
}

// ==========================================
// MENGAMBIL DATA PROFIL UNTUK NAVBAR
// ==========================================
$nav_user_id = $_SESSION['user_id'];
$nav_query = $conn->query("SELECT full_name, profile_picture FROM users WHERE id_user = $nav_user_id");
$nav_user_data = $nav_query->fetch_assoc();

$nav_name = $nav_user_data['full_name'];
$nav_initial = strtoupper(substr($nav_name, 0, 1));
$nav_pic = $nav_user_data['profile_picture'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureGate - Withdrawal</title>
    <link rel="stylesheet" href="../CSS/withdrawal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        if (localStorage.getItem('securegate_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    
    <script>
        const maxBalancePHP = <?= floor($available_balance) ?>;
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        
        <div class="main-nav">
            <div class="main-nav-item"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
            <div class="main-nav-item"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
        </div>

        <div class="right-nav">
            <i class="fa-regular fa-bell nav-bell-icon" title="Notifications"></i>
            
            <div id="profile-dropdown-trigger" class="profile-dropdown-trigger" title="<?= htmlspecialchars($nav_name) ?>">
                <?php if(!empty($nav_pic)): ?>
                    <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="Profile" class="profile-pic-small">
                <?php else: ?>
                    <div class="profile-initial-small">
                        <?= $nav_initial ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="profile-dropdown-menu" class="profile-dropdown-menu">
                <div class="dropdown-header">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" class="profile-pic-large">
                    <?php else: ?>
                        <div class="profile-initial-large"><?= $nav_initial ?></div>
                    <?php endif; ?>
                    <div class="dropdown-user-info">
                        <h4 class="dropdown-user-name"><?= htmlspecialchars($nav_name) ?></h4>
                        <p class="dropdown-user-role"><?= $_SESSION['role'] ?></p>
                    </div>
                </div>

                <div class="dropdown-menu-links">
                    <a href="settings.php" class="dropdown-link">
                        <i class="fa-solid fa-gear dropdown-link-icon"></i> Settings
                    </a>
                    <a href="logout.php" class="dropdown-link logout-link">
                        <i class="fa-solid fa-arrow-right-from-bracket dropdown-link-icon"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="page-frame">
        <div class="page-frame-nav">
            <a href="adminevent.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            <h1 class="header-title">Revenue & Withdrawal</h1>
            <p>Manage your event earnings and request payouts to your bank or e-wallet.</p>
        </div>

        <?php if(isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

        <div class="withdrawal-layout">
            
            <div class="left-layout">
                <div class="balance-card">
                    <p>Available Balance (from this event)</p>
                    <h1 id="available-balance"><?= formatRupiah($available_balance) ?></h1>
                </div>

                <div class="form-card">
                    <h3>Request Payout</h3>
                    
                    <form method="POST" id="withdrawal-form">
                        <div class="form-group">
                            <label>Payout Method</label>
                            <select name="payout_method" id="payout-method" class="custom-select" required>
                                <option value="">Select Bank / E-Wallet...</option>
                                <option value="BCA">BCA Bank</option>
                                <option value="Mandiri">Mandiri Bank</option>
                                <option value="BNI">BNI Bank</option>
                                <option value="BRI">BRI Bank</option>
                                <option value="GoPay">GoPay</option>
                                <option value="DANA">DANA</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Account Number / E-Wallet ID</label>
                            <input type="number" name="payout_account" id="payout-account" class="custom-input" placeholder="e.g., 08123456789" required>
                        </div>

                        <div class="form-group">
                            <label>Amount to Withdraw (Rp)</label>
                            <div class="input-wrapper-max">
                                <input type="number" name="withdraw_amount" id="withdraw-amount" class="custom-input" placeholder="Min. Rp 50.000" min="50000" max="<?= $available_balance ?>" required>
                                <button type="button" id="btn-max">MAX</button>
                            </div>
                        </div>

                        <div class="fee-summary">
                            <div class="fee-row">
                                <span>Withdrawal Amount</span>
                                <span id="summary-amount">Rp 0</span>
                            </div>
                            <div class="fee-row text-danger">
                                <span>Platform Fee (8%)</span>
                                <span id="summary-fee">- Rp 0</span>
                            </div>
                            <hr class="fee-divider">
                            <div class="fee-row total-receive">
                                <span>You will receive</span>
                                <span id="summary-receive" class="text-green-bold">Rp 0</span>
                            </div>
                        </div>

                        <div class="info-alert">
                            <i class="fa-solid fa-circle-info"></i>
                            <p>Funds will be transferred to your account within <strong>1-2 business days</strong> after the request is approved. An 8% platform fee applies to all withdrawals.</p>
                        </div>

                        <?php if($available_balance < 50000): ?>
                            <button type="button" class="btn-submit btn-disabled" disabled>Minimum Withdrawal Rp 50.000</button>
                        <?php else: ?>
                            <button type="submit" class="btn-submit" id="btn-withdraw">Submit Withdrawal</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="right-layout">
                <div class="history-card">
                    <h3>Withdrawal History</h3>
                    <div class="table-responsive">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($history) == 0): ?>
                                    <tr><td colspan="4" class="table-empty">No history found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($history as $h): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($h['created_at'])) ?></td>
                                            <td><?= formatRupiah($h['net_receive']) ?></td>
                                            <td><?= htmlspecialchars($h['payout_method']) ?> <br><span class="acc-text">****<?= substr($h['payout_account'], -4) ?></span></td>
                                            <td>
                                                <?php if($h['status'] == 'done'): ?>
                                                    <span class="badge-done">Done</span>
                                                <?php elseif($h['status'] == 'rejected'): ?>
                                                    <span class="badge-rejected">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge-pending">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="../JS/withdrawal.js"></script>                                         
</body>
</html>