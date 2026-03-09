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
        // Hitung Potongan 5% & Bersih
        $platform_fee = $amount * 0.05;
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
function formatRupiah($num) { return "Rp " . number_format($num, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureGate - Withdrawal</title>
    <link rel="stylesheet" href="../CSS/withdrawal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
            <div class="main-nav-event"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
        </div>
        <div class="right-nav" style="display: flex; align-items: center; gap: 15px;">
            <i class="fa-regular fa-bell" style="font-size: 18px; color: #a0a0a0;"></i>
            <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; border: 2px solid #2a2a2a;" title="<?= htmlspecialchars($admin_name) ?>">
                <?= $nav_initial ?>
            </div>
        </div>
    </nav>

    <div class="page-frame">
        <div class="page-frame-nav">
            <a href="adminevent.php?event_id=<?= $event_id ?>" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
            <h1 style="margin-top: 16px;">Revenue & Withdrawal</h1>
            <p>Manage your event earnings and request payouts to your bank or e-wallet.</p>
        </div>

        <?php if(isset($error)) echo "<div style='color:#ef4444; background:rgba(239,68,68,0.1); padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>$error</div>"; ?>
        <?php if(isset($success)) echo "<div style='color:#22c55e; background:rgba(34,197,94,0.1); padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>$success</div>"; ?>

        <div class="withdrawal-layout">
            
            <div class="left-layout">
                <div class="balance-card">
                    <p>Available Balance (from this event)</p>
                    <h1 id="available-balance"><?= formatRupiah($available_balance) ?></h1>
                </div>

                <div class="form-card">
                    <h3>Request Payout</h3>
                    
                    <form method="POST">
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
                                <button type="button" id="btn-max" onclick="document.getElementById('withdraw-amount').value = <?= $available_balance ?>; calculateFee();">MAX</button>
                            </div>
                        </div>

                        <div class="fee-summary">
                            <div class="fee-row">
                                <span>Withdrawal Amount</span>
                                <span id="summary-amount">Rp 0</span>
                            </div>
                            <div class="fee-row text-danger">
                                <span>Platform Fee (5%)</span>
                                <span id="summary-fee" style="color: #ef4444;">- Rp 0</span>
                            </div>
                            <hr class="fee-divider">
                            <div class="fee-row total-receive">
                                <span>You will receive</span>
                                <span id="summary-receive" class="text-green" style="color: #22c55e; font-weight:bold; font-size:18px;">Rp 0</span>
                            </div>
                        </div>

                        <div class="info-alert">
                            <i class="fa-solid fa-circle-info"></i>
                            <p>Funds will be transferred to your account within <strong>1-2 business days</strong> after the request is approved. A 5% platform fee applies to all withdrawals.</p>
                        </div>

                        <?php if($available_balance < 50000): ?>
                            <button type="button" class="btn-submit" style="background:#444; color:#888; cursor:not-allowed;">Minimum Withdrawal Rp 50.000</button>
                        <?php else: ?>
                            <button type="submit" class="btn-submit">Submit Withdrawal</button>
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
                                    <tr><td colspan="4" style="text-align:center; padding: 20px; color:#888;">No history found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($history as $h): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($h['created_at'])) ?></td>
                                            <td><?= formatRupiah($h['amount']) ?></td>
                                            <td><?= htmlspecialchars($h['payout_method']) ?> <br><span class="acc-text" style="color:#888; font-size:12px;">****<?= substr($h['payout_account'], -4) ?></span></td>
                                            <td>
                                                <?php if($h['status'] == 'done'): ?>
                                                    <span style="background:rgba(34,197,94,0.1); color:#22c55e; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold;">Done</span>
                                                <?php elseif($h['status'] == 'rejected'): ?>
                                                    <span style="background:rgba(239,68,68,0.1); color:#ef4444; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold;">Rejected</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(249,115,22,0.1); color:#f97316; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold;">Pending</span>
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

    <script>
        const inputAmount = document.getElementById('withdraw-amount');
        const maxBalance = <?= $available_balance ?>;

        function formatRp(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function calculateFee() {
            let val = parseFloat(inputAmount.value) || 0;
            
            // Cegah input melebihi saldo
            if (val > maxBalance) {
                val = maxBalance;
                inputAmount.value = maxBalance;
            }

            let fee = val * 0.05; // 5% fee
            let net = val - fee;

            document.getElementById('summary-amount').innerText = formatRp(val);
            document.getElementById('summary-fee').innerText = "- " + formatRp(fee);
            document.getElementById('summary-receive').innerText = formatRp(net);
        }

        inputAmount.addEventListener('input', calculateFee);
    </script>
</body>
</html>