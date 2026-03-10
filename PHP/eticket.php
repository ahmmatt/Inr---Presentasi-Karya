<?php
session_start();
require_once 'config/connect.php'; 
require_once 'midtrans-php-master/Midtrans.php';

// =======================================================
// TANGKAP SINYAL AUTO CHECK-IN DARI TOMBOL "JOIN VIRTUAL"
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'auto_checkin') {
    header('Content-Type: application/json');
    $att_id = (int)$_POST['attendee_id'];
    
    // Ubah status jadi checked_in di database
    $conn->query("UPDATE attendees SET status = 'checked_in' WHERE id_attendee = $att_id AND id_user = " . $_SESSION['user_id']);
    
    echo json_encode(['status' => 'success']);
    exit();
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
            'order_id' => $ticket['ticket_code'], 
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
    <nav class="navbar scrolled">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-regular fa-compass"></i><a href="discover.php">Discover</a></div>
            <div class="main-nav-event"><i class="fa-solid fa-ticket"></i><a href="mainpage.php">Event</a></div>
        </div>
        <div class="right-nav" style="display: flex; align-items: center; gap: 15px; position: relative;">
            <i class="fa-regular fa-bell" style="font-size: 18px; color: #a0a0a0; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a0a0a0'"></i>
            
            <div id="profile-dropdown-trigger" style="cursor: pointer; position: relative;" title="<?= htmlspecialchars($nav_name) ?>">
                <?php if(!empty($nav_pic)): ?>
                    <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #2a2a2a; display: block;">
                <?php else: ?>
                    <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; border: 2px solid #2a2a2a;">
                        <?= $nav_initial ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="profile-dropdown-menu" style="display: none; position: absolute; top: 50px; right: 0; background: #121212; border: 1px solid #333; border-radius: 12px; width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); z-index: 1000; overflow: hidden;">
                
                <div style="padding: 15px; border-bottom: 1px solid #2a2a2a; display: flex; align-items: center; gap: 12px;">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #3b82f6; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; flex-shrink: 0;"><?= $nav_initial ?></div>
                    <?php endif; ?>
                    <div style="overflow: hidden;">
                        <h4 style="color: #fff; font-size: 14px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($nav_name) ?></h4>
                        <p style="color: #888; font-size: 12px; margin: 0; text-transform: capitalize; margin-top: 2px;"><?= $_SESSION['role'] ?></p>
                    </div>
                </div>

                <div style="padding: 8px;">
                    <a href="settings.php" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: #ccc; text-decoration: none; font-size: 13px; border-radius: 8px; transition: 0.2s;" onmouseover="this.style.background='#222'; this.style.color='#fff';" onmouseout="this.style.background='transparent'; this.style.color='#ccc';">
                        <i class="fa-solid fa-gear" style="width: 16px; text-align: center;"></i> Settings
                    </a>
                    <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 10px 12px; color: #ef4444; text-decoration: none; font-size: 13px; border-radius: 8px; transition: 0.2s; margin-top: 4px;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)';" onmouseout="this.style.background='transparent';">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="width: 16px; text-align: center;"></i> Logout
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
                    <div class="ticket-info" style="text-align: center; width: 100%; padding: 40px 0;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 50px; color: #f97316; margin-bottom: 20px;"></i>
                        <h1 class="event-title" style="color: #f97316;">Registration Pending</h1>
                        <p style="color: #a0a0a0; margin-top: 10px;">Your registration for <strong><?= htmlspecialchars($ticket['title']) ?></strong> is currently waiting for the organizer's approval.</p>
                    </div>

                <?php elseif($status == 'awaiting_payment'): ?>
                    <div class="ticket-info" style="text-align: center; width: 100%; padding: 40px 0;">
                        <i class="fa-solid fa-wallet" style="font-size: 50px; color: #ef4444; margin-bottom: 20px;"></i>
                        <h1 class="event-title" style="color: #ef4444;">Action Required: Payment</h1>
                        <p style="color: #a0a0a0; margin-top: 10px;">Your registration for <strong><?= htmlspecialchars($ticket['title']) ?></strong> is approved!</p>
                        <p style="color: #fff; font-size: 18px; margin-top: 10px;">Please complete your payment of <strong style="color: #22c55e;"><?= formatRupiah($ticket['price']) ?></strong>.</p>
                        
                        <?php if(!empty($snapToken)): ?>
                            <button class="btn-primary" id="pay-button" style="margin-top: 24px; padding: 14px 30px; background: #22c55e; border: none; font-weight: bold; font-size: 16px; cursor: pointer;">Pay Now</button>
                        <?php else: ?>
                            <p style="color:red; font-size:12px; margin-top:10px;">Error generating payment link: <?= htmlspecialchars($error_msg) ?></p>
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
                    
                    <div class="ticket-qr-section" style="margin-top: 20px;">
                        <?php if($ticket['location_type'] == 'offline'): ?>
                            <div class="qr-code-box" style="background: #fff; padding: 10px; border-radius: 12px; display: flex; justify-content: center; align-items: center; width: 170px; height: 170px; margin: 0 auto;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($ticket['qr_token']) ?>" alt="QR Code" style="width: 100%; height: 100%;">
                            </div>
                            <p style="margin-top: 12px; color: #a0a0a0; font-size: 13px; text-align: center;">Scan this at the entrance</p>
                        
                        <?php else: ?>
                            <div style="background: #222; border: 1px solid #333; border-radius: 12px; padding: 24px; text-align: center;">
                                <?php if($is_revealed): ?>
                                    <i class="fa-solid fa-unlock-keyhole" style="font-size: 32px; color: #22c55e; margin-bottom: 12px;"></i>
                                    <h3 style="color: #fff; margin-bottom: 8px;">Virtual Meeting is Ready</h3>
                                    <p style="color: #a0a0a0; font-size: 13px; margin-bottom: 16px;">Klik tombol di bawah ini untuk bergabung ke dalam acara.</p>
                                    <button onclick="joinAndCheckIn(<?= $ticket['id_attendee'] ?>, '<?= htmlspecialchars($ticket['location_details']) ?>')" style="display: inline-block; padding: 12px 24px; background: #22c55e; color: #000; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; font-size: 16px;">
                                        <i class="fa-solid fa-video"></i> Join Virtual Event
                                    </button>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock" style="font-size: 32px; color: #f97316; margin-bottom: 12px;"></i>
                                    <h3 style="color: #fff; margin-bottom: 8px;">Link is Locked</h3>
                                    <p style="color: #a0a0a0; font-size: 13px; line-height: 1.5;">Tautan virtual akan otomatis terbuka pada:<br>
                                        <strong style="color: #f97316;"><?= date('l, d M Y H:i', $h_min_1) ?> WITA</strong> (H-1)
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
                        alert("Payment Success!"); 
                        location.reload(); 
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
    <script>
        // SCRIPT UNTUK DROPDOWN NAVBAR
        document.addEventListener('DOMContentLoaded', function() {
            const profileTrigger = document.getElementById('profile-dropdown-trigger');
            const profileMenu = document.getElementById('profile-dropdown-menu');

            if (profileTrigger && profileMenu) {
                // Munculkan menu saat foto profil diklik
                profileTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
                });

                // Tutup menu otomatis jika user klik area kosong di layar
                window.addEventListener('click', function(e) {
                    if (!profileTrigger.contains(e.target) && !profileMenu.contains(e.target)) {
                        profileMenu.style.display = 'none';
                    }
                });
            }
        });
    </script>
    <script>
        // FUNGSI AUTO CHECK-IN SAAT KLIK VIRTUAL LINK
        function joinAndCheckIn(attendeeId, meetLink) {
            // 1. Buka link Zoom/Meet di tab baru
            window.open(meetLink, '_blank');

            // 2. Kirim sinyal diam-diam ke server untuk mengubah status
            let formData = new FormData();
            formData.append('action', 'auto_checkin');
            formData.append('attendee_id', attendeeId);

            fetch('eticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // 3. Refresh halaman agar label E-ticket berubah jadi "Checked-In"
                    location.reload();
                }
            })
            .catch(err => console.error('Gagal auto check-in:', err));
        }
    </script>
</body>

</html>