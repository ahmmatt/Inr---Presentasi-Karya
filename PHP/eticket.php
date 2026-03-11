<?php
session_start();
require_once 'config/connect.php'; 
require_once 'midtrans-php-master/Midtrans.php';

// =======================================================
// TANGKAP SINYAL AJAX DARI JAVASCRIPT
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $att_id = (int)$_POST['attendee_id'];
    
    // Sinyal 1: Klik Link Virtual (Auto Check-In)
    if ($_POST['action'] == 'auto_checkin') {
        $conn->query("UPDATE attendees SET status = 'checked_in' WHERE id_attendee = $att_id AND id_user = " . $_SESSION['user_id']);
        echo json_encode(['status' => 'success']);
        exit();
    }
    
    // Sinyal 2: Pembayaran Berhasil dari Midtrans
    if ($_POST['action'] == 'payment_success') {
        $conn->query("UPDATE attendees SET status = 'approved' WHERE id_attendee = $att_id AND id_user = " . $_SESSION['user_id']);
        echo json_encode(['status' => 'success']);
        exit();
    }
}

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$attendee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($attendee_id == 0) {
    echo "<script>alert('Invalid Ticket ID'); window.location.href='mainpage.php';</script>";
    exit();
}

// 2. Ambil Data Lengkap
// Saya tambahkan a.created_at as register_date agar sesuai dengan desain Anda
$sql = "SELECT a.*, a.created_at as register_date, e.title, e.start_date, e.start_time, e.location_type, e.venue_name, e.city, e.location_details, e.timezone, t.tier_name, t.price 
        FROM attendees a 
        JOIN events e ON a.id_event = e.id_event 
        JOIN ticket_tiers t ON a.id_tier = t.id_tier
        WHERE a.id_attendee = $attendee_id AND a.id_user = $user_id";

$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<script>alert('Ticket not found.'); window.location.href='mainpage.php';</script>";
    exit();
}

$ticket = $result->fetch_assoc();
$status = $ticket['status']; 
$snapToken = '';
$error_msg = '';

// 3. Jika Butuh Bayar -> Generate Midtrans
if ($status == 'awaiting_payment' && $ticket['price'] > 0) {
    
    // Konfigurasi Midtrans
    // Panggil file rahasia
    require_once 'config/secret_keys.php';

    // Gunakan variabel dari file rahasia tersebut
    \Midtrans\Config::$serverKey = $MIDTRANS_SERVER_KEY;
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    \Midtrans\Config::$is3ds = true;

    $transaction = array(
        'transaction_details' => array(
            // Tambahkan time() agar order_id selalu unik setiap kali halaman dimuat ulang
            'order_id' => $ticket['ticket_code'] . '-' . time(), 
            'gross_amount' => $ticket['price'],
        ),
        'customer_details' => array(
            'first_name' => $ticket['full_name'],
            'email' => $ticket['email'],
        )
    );

    try {
        $snapToken = \Midtrans\Snap::getSnapToken($transaction);
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Fungsi Format untuk menyesuaikan desain Anda
function formatDate($dateStr) { return (new DateTime($dateStr))->format('M j, Y'); }
function formatTime($timeStr) { return (new DateTime($timeStr))->format('g:i A'); }
function formatRupiah($num) { return "Rp " . number_format($num, 0, ',', '.'); }

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
    <title>SecureGate - Your E-Ticket</title>
    <link rel="stylesheet" href="../CSS/eticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-regular fa-compass"></i><a href="discover.php">Discover</a></div>
            <div class="main-nav-event"><i class="fa-solid fa-ticket"></i><a href="mainpage.php">Event</a></div>
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

    <div class="page-frame ticket-page-frame">
        
        <div class="ticket-actions-top">
            <a href="mainpage.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to My Tickets</a>
        </div>

        <div class="ticket-card">
            
            <div class="ticket-header">
                <h2><?= $status == 'approved' ? 'This is your ticket' : 'Ticket Status' ?></h2>
                <div class="organizer-logo">
                    <span>SecureGate</span>
                </div>
            </div>

            <hr class="ticket-divider">

            <div class="ticket-body">
                
                <?php if($status == 'need_approval'): ?>
                    <div class="ticket-info-v2">
                        <i class="fa-solid fa-clock-rotate-left status-icon-orange"></i>
                        <h1 class="event-title title-orange">Registration Pending</h1>
                        <p class="status-desc">Your registration for <strong><?= htmlspecialchars($ticket['title']) ?></strong> is currently waiting for the organizer's approval.</p>
                    </div>

                <?php elseif($status == 'awaiting_payment'): ?>
                    <div class="ticket-info-v2">
                        <i class="fa-solid fa-wallet status-icon-red"></i>
                        <h1 class="event-title title-red">Action Required: Payment</h1>
                        <p class="status-desc">Your registration for <strong><?= htmlspecialchars($ticket['title']) ?></strong> is approved!</p>
                        <p class="status-instruction">Please complete your payment of <strong class="text-green-highlight"><?= formatRupiah($ticket['price']) ?></strong>.</p>
                        
                        <?php if(!empty($snapToken)): ?>
                            <button class="btn-pay-now" id="pay-button">Pay Now</button>
                        <?php else: ?>
                            <p class="payment-error-msg">Error generating payment link: <?= htmlspecialchars($error_msg) ?></p>
                        <?php endif; ?>
                    </div>

                <?php elseif($status == 'approved'): ?>
                    <div class="ticket-info">
                        <p class="ticket-subtitle">
                            <?= !empty($ticket['venue_name']) ? htmlspecialchars($ticket['venue_name']) : 'SecureGate Event' ?> - 
                            <?= !empty($ticket['city']) ? htmlspecialchars($ticket['city']) : 'Virtual / Online' ?>
                        </p>
                        
                        <h1 class="event-title"><?= htmlspecialchars($ticket['title']) ?></h1>
                        
                        <div class="event-datetime-location">
                            <p>
                                <i class="fa-solid <?= $ticket['location_type'] == 'online' ? 'fa-link' : 'fa-location-dot' ?>"></i> 
                                <?= $ticket['location_type'] == 'online' ? 'Virtual Meeting (Tautan di bawah)' : htmlspecialchars($ticket['location_details']) ?>
                            </p>
                            <p><i class="fa-regular fa-calendar"></i> <?= strtoupper(formatDate($ticket['start_date'])) ?>, <?= strtoupper(formatTime($ticket['start_time'])) ?> <?= !empty($ticket['timezone']) ? '('.htmlspecialchars($ticket['timezone']).')' : '' ?></p>
                        </div>

                        <div class="attendee-details-grid">
                            <div class="detail-box">
                                <span class="detail-label">ISSUED TO</span>
                                <span class="detail-value"><?= htmlspecialchars($ticket['full_name']) ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">ORDER NUMBER</span>
                                <span class="detail-value"><?= $ticket['ticket_code'] ?></span>
                                <span class="detail-sub-value">Registered <?= strtoupper(formatDate($ticket['register_date'])) ?></span>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">TICKET</span>
                                <span class="detail-value"><?= htmlspecialchars($ticket['tier_name']) ?></span>
                                <span class="detail-sub-value <?= $ticket['price'] == 0 ? 'text-free' : '' ?>" <?= $ticket['price'] > 0 ? 'style="color: #22c55e; font-weight: bold;"' : '' ?>>
                                    <?= $ticket['price'] == 0 ? 'FREE' : formatRupiah($ticket['price']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php
                        // LOGIKA GEMBOK WAKTU H-1
                        date_default_timezone_set('Asia/Makassar'); 
                        $now = time();
                        $event_start_time = strtotime($ticket['start_date'] . ' ' . $ticket['start_time']);
                        
                        // Hitung H-1 (Dikurangi 1 Hari / 24 Jam)
                        $h_min_1 = strtotime('-1 day', $event_start_time);
                        $is_revealed = ($now >= $h_min_1);
                    ?>
                    
                    <div class="ticket-qr-section">
                    <?php if($ticket['location_type'] == 'offline'): ?>
                        <div class="qr-code-box">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($ticket['qr_token']) ?>" alt="QR Code">
                        </div>
                        <p class="qr-desc">Scan this at the entrance</p>
                        
                        <?php else: ?>
                            <div class="virtual-box-wrapper">
                                <?php if($is_revealed): ?>
                                    <i class="fa-solid fa-unlock-keyhole virtual-icon-green"></i>
                                    <h3 class="virtual-title">Virtual Meeting is Ready</h3>
                                    <p class="virtual-desc">Klik tombol di bawah ini untuk bergabung ke dalam acara.</p>
                                    
                                    <button class="btn-join-virtual" data-id="<?= $ticket['id_attendee'] ?>" data-link="<?= htmlspecialchars($ticket['location_details']) ?>">
                                        <i class="fa-solid fa-video"></i> Join Virtual Event
                                    </button>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock virtual-icon-orange"></i>
                                    <h3 class="virtual-title">Link is Locked</h3>
                                    <p class="virtual-desc-locked">Tautan virtual akan otomatis terbuka pada:<br>
                                        <strong class="text-orange"><?= date('l, d M Y H:i', $h_min_1) ?> WITA</strong> (H-1)
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

            <div class="ticket-footer">
                <p>&copy; SecureGate - All Rights Reserved.</p>
            </div>
        </div>


    </div>

    <div class="page-frame" style="margin-top: 0;">
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

    <?php if(!empty($snapToken) && $status == 'awaiting_payment'): ?>
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="MASUKKAN_CLIENT_KEY_ANDA"></script>
        <script>
            document.getElementById('pay-button').onclick = function(){
                window.snap.pay('<?= $snapToken ?>', {
                    onSuccess: function(result){
                        // JANGAN LANGSUNG RELOAD, KITA UPDATE DATABASE DULU VIA AJAX!
                        let formData = new FormData();
                        formData.append('action', 'payment_success');
                        formData.append('attendee_id', <?= $attendee_id ?>);

                        fetch('eticket.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                alert("Payment Success! Tiket Anda sudah aktif."); 
                                location.reload(); // Reload halaman setelah database berhasil diubah
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("Sistem gagal memperbarui tiket. Silakan hubungi admin.");
                        });
                    },
                    onPending: function(result){
                        alert("Waiting for payment...");
                    },
                    onError: function(result){
                        alert("Payment failed!");
                    }
                });
            };
        </script>
    <?php endif; ?>
    <script src="../JS/eticket.js"></script>
</body>

</html>