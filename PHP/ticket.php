<?php
session_start();
require_once 'config/connect.php';

// 1. CEK LOGIN & KUNCI KHUSUS USER BIASA
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo "<script>alert('Akses Ditolak! Anda sedang menggunakan Sesi Admin. Silakan Logout terlebih dahulu dan login menggunakan akun User biasa untuk membeli tiket.'); window.location.href='../index.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];

// ... (lanjutkan kode Anda di bawahnya) ...

// 2. Cek ID Event di URL (Misal: ticket.php?id=1)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Event not found.'); window.location.href='discover.php';</script>";
    exit();
}

$event_id = (int)$_GET['id'];

// 3. Ambil Data Event
// HAPUS tulisan "AND status = 'active'" agar event yang sudah ended tetap bisa dilihat
$sql_ev = "SELECT * FROM events WHERE id_event = $event_id";
$res_ev = $conn->query($sql_ev);

if ($res_ev->num_rows == 0) {
    echo "<script>alert('Event has ended or does not exist.'); window.location.href='discover.php';</script>";
    exit();
}
$event = $res_ev->fetch_assoc();

// 4. Ambil Kategori Tiket untuk Event ini
$tickets = [];
$res_tkt = $conn->query("SELECT * FROM ticket_tiers WHERE id_event = $event_id");
while($row = $res_tkt->fetch_assoc()) {
    $tickets[] = $row;
}

// 5. Ambil Custom Questions jika ada
$questions = [];
$res_q = $conn->query("SELECT * FROM custom_questions WHERE id_event = $event_id");
while($row = $res_q->fetch_assoc()) {
    $questions[] = $row;
}

// ==========================================
// 6. PROSES PEMBELIAN DENGAN MIDTRANS & APPROVAL LOGIC
// ==========================================
require_once 'midtrans-php-master/Midtrans.php';

// Konfigurasi Midtrans
// Panggil file rahasia
require_once 'config/secret_keys.php';

// Gunakan variabel dari file rahasia tersebut
\Midtrans\Config::$serverKey = $MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$snapToken = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_tier'])) {
    $tier_id = (int)$_POST['ticket_tier'];
    $seat_num = isset($_POST['seat_num']) ? $conn->real_escape_string($_POST['seat_num']) : '';
    
    // Generate Order ID Unik & QR Token
    $order_id = "SG-E" . $event_id . "-U" . $user_id . "-" . rand(1000,9999);
    $qr_token = hash('sha256', $order_id . time());

    // Cari Harga Tiket
    $selected_price = 0;
    foreach($tickets as $t) { if($t['id_tier'] == $tier_id) $selected_price = $t['price']; }
    
    // ----------------------------------------------------
    // LOGIKA PENENTUAN STATUS AWAL TIKET
    // ----------------------------------------------------
    $is_require_approval = ($event['require_approval'] == 1);
    
    if ($is_require_approval) {
        // Jika butuh approval, tahan dulu!
        $status = 'need_approval';
    } else {
        // Jika tidak butuh approval, cek harga
        $status = ($selected_price == 0) ? 'approved' : 'awaiting_payment';
    }

    // 1. Simpan ke Database
    $sql_insert = "INSERT INTO attendees (id_event, id_user, id_tier, ticket_code, full_name, email, seat_number, status, qr_token) 
                   VALUES ($event_id, $user_id, $tier_id, '$order_id', '$user_name', '$user_email', '$seat_num', '$status', '$qr_token')";
    
    if ($conn->query($sql_insert)) {
        $new_attendee_id = $conn->insert_id;

        // Simpan jawaban Custom Questions jika ada
        if (isset($_POST['answers'])) {
            foreach($_POST['answers'] as $q_id => $ans) {
                $ans_esc = $conn->real_escape_string($ans);
                $conn->query("INSERT INTO attendee_answers (id_attendee, id_question, answer_text) VALUES ($new_attendee_id, $q_id, '$ans_esc')");
            }
        }

        // ----------------------------------------------------
        // LOGIKA SETELAH DATA TERSIMPAN (REDIRECT / BAYAR)
        // ----------------------------------------------------
        if ($is_require_approval) {
            // CABANG 1: Event butuh persetujuan Admin
            echo "<script>
                    alert('Registration submitted successfully! Please wait for Admin approval. You can check your status in My Events.');
                    window.location.href='mainpage.php';
                  </script>";
            exit();
            
        } else if ($selected_price == 0) {
            // CABANG 2: Event langsung masuk & Gratis
            echo "<script>
                    alert('Ticket Successfully Registered!');
                    window.location.href='eticket.php?id=$new_attendee_id';
                  </script>";
            exit();
            
        } else {
            // CABANG 3: Event langsung masuk & Berbayar -> Panggil Midtrans
            $transaction = array(
                'transaction_details' => array('order_id' => $order_id, 'gross_amount' => $selected_price),
                'customer_details' => array('first_name' => $user_name, 'email' => $user_email)
            );
            try {
                $snapToken = \Midtrans\Snap::getSnapToken($transaction);
            } catch (Exception $e) {
                echo "<script>alert('Gagal terhubung ke Midtrans: " . $e->getMessage() . "');</script>";
            }
        }
    }
}

// Fungsi Bantuan
function formatDate($dateStr) { return (new DateTime($dateStr))->format('M j, l'); }
function formatTime($timeStr) { return (new DateTime($timeStr))->format('g:i A'); }
function formatMonthDay($dateStr) { 
    $d = new DateTime($dateStr); 
    return ['month' => strtoupper($d->format('M')), 'day' => $d->format('d')]; 
}
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
    <title><?= htmlspecialchars($event['title']) ?> - SecureGate</title>
    <link rel="stylesheet" href="../CSS/ticket.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hidden-step { display: none !important; }
        .active-card { border: 2px solid #22c55e !important; background: rgba(34, 197, 94, 0.1) !important; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background-color: #f97316; color: white; display: flex; justify-content: center; align-items: center; font-weight: bold; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate</h1></div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-regular fa-compass"></i><a href="discover.php">Discover</a></div>
            <div class="main-nav-event"><i class="fa-solid fa-ticket"></i><a href="mainpage.php">My Events</a></div>
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

    <div class="page-frame">
        <div class="create-wrapper-layout">
            
            <div class="left-layout">
                <div class="add-pict-card view-mode">
                    <?php $img_path = !empty($event['banner_image']) ? "../Media/uploads/" . htmlspecialchars($event['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                    <img src="<?= $img_path ?>" alt="Event Cover">
                </div>

                <div class="presented-wrapper">
                    <div class="presented-left">
                        <div class="presented-logo"><img src="../Media/pantai-indah-kapuk-dua-tbk--600.png" alt="logo"></div>
                        <div class="presented-detail">
                            <p>Organized By</p>
                            <h3>SecureGate User <i class="fa-solid fa-circle-check" style="color: #1d9bf0;"></i></h3>
                        </div>
                    </div>
                </div>
                <hr>

                <div class="maps-or-link-card" style="width: 100%; overflow: hidden; box-sizing: border-box;">
                    <h4>Location or Virtual Link</h4>
                    
                    <div class="link-wrapper" style="display: flex; align-items: center; margin-top: 16px; width: 100%;">
                        <?php 
                            if ($event['location_type'] == 'online') {
                                // 1. ONLINE: Samarkan Link
                                $icon_class = 'fa-link';
                                $display_text = "Virtual Meeting (Tautan ada di E-Ticket)";
                                $show_copy = false;
                            } else {
                                // 2. OFFLINE: Tampilkan GMaps / Alamat
                                $copy_target = (!empty($event['maps_link']) && $event['maps_link'] !== 'NULL') ? $event['maps_link'] : $event['location_details'];
                                $icon_class = 'fa-map-location-dot';
                                $display_text = htmlspecialchars($copy_target);
                                $show_copy = true;
                            }
                        ?>
                        
                        <i class="fa-solid <?= $icon_class ?>" style="color: #a0a0a0; font-size: 16px; flex-shrink: 0;"></i>
                        
                        <div class="url-box" style="flex: 1; min-width: 0; display: flex; align-items: center; overflow: hidden; margin-left: 12px;">
                            
                            <span id="virtual-link-text" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; <?= !$show_copy ? 'color: #22c55e; font-weight: 600;' : '' ?>" title="<?= $display_text ?>">
                                <?= $display_text ?>
                            </span>
                            
                            <?php if($show_copy): ?>
                                <i class="fa-regular fa-copy" id="copy-link-btn" title="Copy Link" onclick="navigator.clipboard.writeText('<?= $copy_target ?>'); alert('Link copied!');" style="cursor: pointer; margin-left: 10px; flex-shrink: 0;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="category-card">
                    <i class="fa-solid fa-layer-group"></i>
                    <h3><?= htmlspecialchars($event['category']) ?></h3>
                </div>  
            </div>

            <div class="right-layout">    
                <div class="event-name view-mode-title">
                    <h1><?= htmlspecialchars($event['title']) ?></h1>
                </div>
                
                <div class="time-loc-and-price-wrapper">
                    <div class="time-loc-left">
    
                        <div class="time-date-wrapper">
                            <?php $md = formatMonthDay($event['start_date']); ?>
                            <div class="time-date-card">
                                <span><?= $md['month'] ?></span>
                                <h4><?= $md['day'] ?></h4>
                            </div>
                            <div class="time-date-detail">
                                <h3><?= (new DateTime($event['start_date']))->format('l, F j') ?></h3>
                                <p><?= formatTime($event['start_time']) ?> - <?= formatTime($event['end_time']) ?></p>
                            </div>
                        </div>
                        
                        <div class="event-loc-wrapper">
                            <div class="event-loc-icon">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="time-date-detail">
                                <h3>
                                    <?= !empty($event['venue_name']) ? htmlspecialchars($event['venue_name']) : 'Event Location' ?> 
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px; margin-left: 4px; color: #666;"></i>
                                </h3>
                                
                                <p>
                                    <?php 
                                        if($event['location_type'] == 'online') {
                                            echo "Online Event (Zoom / Virtual Meet)"; // Link asli disamarkan
                                        } else {
                                            $city_text = !empty($event['city']) ? htmlspecialchars($event['city']) . ', ' : '';
                                            echo $city_text . htmlspecialchars($event['location_details']);
                                        }
                                    ?>
                                </p>
                            </div>
                        </div>

                    </div>
                    
                    <div class="price-cards-wrapper">
                        <?php foreach($tickets as $t): ?>
                            <div class="price-right-card ticket-option-card <?= strtolower($t['tier_name']) == 'vip' ? 'vip-card' : '' ?>" 
                                 data-id="<?= $t['id_tier'] ?>" data-price="<?= $t['price'] ?>" data-name="<?= $t['tier_name'] ?>">
                                <i class="fa-solid <?= strtolower($t['tier_name']) == 'vip' ? 'fa-crown' : 'fa-ticket' ?>"></i>
                                <p><?= htmlspecialchars($t['tier_name']) ?></p>
                                <h3><?= $t['price'] == 0 ? 'Free' : formatRupiah($t['price']) ?></h3>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="register-card">
                    <div class="register-header"><h4>Registration</h4></div>
                    <h3>Welcome! To join the event, please register below.</h3>
                    <div class="profile-detail">
                        <?php if(!empty($nav_pic)): ?>
                            <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="User Avatar" style="width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:12px; border: 1px solid #333;">
                        <?php else: ?>
                            <div class="user-avatar" style="margin-right:12px; background-color: #3b82f6;"><?= $nav_initial ?></div>
                        <?php endif; ?>
                        
                        <div>
                            <h3><?= htmlspecialchars($user_name) ?></h3>
                            <h3 class="email-text" style="font-weight:normal; color:#888; font-size:13px;"><?= htmlspecialchars($user_email) ?></h3>
                        </div>
                    </div>
                    
                    <?php if ($event['status'] == 'ended'): ?>
                        <button type="button" class="btn-register" style="background: #444; color: #888; cursor: not-allowed;" disabled>Event Has Ended</button>
                    <?php elseif (empty($nav_pic)): ?>
                        <button type="button" class="btn-register" style="background: #f97316; color: #fff; display: flex; justify-content: center; align-items: center; gap: 8px;" onclick="alert('Sistem Keamanan Event:\n\nAnda DIWAJIBKAN untuk mengunggah Foto Profil berisi wajah Anda di menu Settings sebelum dapat mendaftar.\n\nFoto ini akan digunakan untuk verifikasi Check-in di lokasi event.'); window.location.href='settings.php';">
                            <i class="fa-solid fa-camera"></i> Setup Profile Photo
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-register" id="open-register-modal">Buy Ticket</button>
                    <?php endif; ?>
                </div>

                <div class="register-modal" id="register-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#1a1a1a; padding:30px; border-radius:16px; z-index:9999; width:90%; max-width:450px; border:1px solid #333; max-height: 90vh; overflow-y: auto;">
                    <i class="fa-solid fa-xmark close-modal" id="close-register-modal" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:20px;"></i>

                    <form method="POST" id="checkout-form">
                        
                        <div id="step-1-form" class="modal-step">
                            <h3 style="margin-bottom:20px;">Registration Details</h3>
                            
                            <input type="hidden" name="ticket_tier" id="selected-tier-id" required>
                            
                            <?php if(count($questions) > 0): ?>
                                <?php foreach($questions as $q): ?>
                                    <div style="margin-bottom:15px; display:flex; flex-direction:column; gap:8px;">
                                        <span style="font-size:13px; color:#aaa;"><?= htmlspecialchars($q['question_text']) ?></span>
                                        <input type="text" name="answers[<?= $q['id_question'] ?>]" required style="padding:12px; border-radius:8px; background:#111; border:1px solid #333; color:#fff;">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div style="margin-bottom:15px; display:flex; flex-direction:column; gap:8px; <?= $event['seat_assignment'] == 'bebas' ? 'display:none;' : '' ?>">
                                <span style="font-size:13px; color:#aaa;">Choose your seat number (Optional)</span>
                                <input type="text" name="seat_num" placeholder="e.g. A-12" style="padding:12px; border-radius:8px; background:#111; border:1px solid #333; color:#fff;">
                            </div>
                            
                            <button type="button" class="btn-proceed" id="btn-to-payment" style="width:100%; padding:14px; background:#22c55e; color:#000; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:10px;">Proceed to Payment</button>
                        </div>

                        <div id="step-2-payment" class="modal-step payment-step hidden-step" style="text-align:center;">
                            <h2 style="margin-bottom:10px;">Complete Payment</h2>
                            <p style="color:#aaa; font-size:14px; margin-bottom:20px;">Scan this QRIS to pay.</p>
                            
                            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QRIS" style="width:200px; height:200px; background:#fff; padding:10px; border-radius:10px; margin-bottom:20px;">
                            
                            <div style="background:#222; padding:15px; border-radius:10px; text-align:left; margin-bottom:20px; font-size:13px; color:#bbb;">
                                <p>1. Open your E-Wallet app.</p>
                                <p>2. Scan the QR code.</p>
                                <p>3. Pay exactly <strong id="dynamic-payment-price" style="color:#22c55e; font-size:16px;">Rp 0</strong>.</p>
                            </div>
                            
                            <button type="submit" name="buy_ticket" class="btn-proceed" style="width:100%; padding:14px; background:#fff; color:#000; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">I Have Paid (Simulate)</button>
                        </div>
                    </form>
                </div>
                <div id="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9998; backdrop-filter:blur(5px);"></div>

                <div class="event-description">
                    <h4 style="color: #fff; margin-bottom: 12px; font-size: 16px;">About Event</h4>
                    <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                </div>  
            </div>
        </div>
    </div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="CLIENT_KEY_ANDA"></script>
    <script src="../JS/ticket.js"></script>
    <?php if (!empty($snapToken)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Tutup modal lama kita
            document.getElementById('register-modal').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
            document.body.style.overflow = 'auto';

            // Panggil Popup Midtrans
            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result) {
                    alert("Pembayaran Berhasil! Tiket Anda sedang diproses.");
                    window.location.href = "mainpage.php"; 
                },
                onPending: function(result) {
                    alert("Menunggu pembayaran Anda...");
                    window.location.href = "mainpage.php"; 
                },
                onError: function(result) {
                    alert("Pembayaran Gagal!");
                },
                onClose: function() {
                    alert("Anda menutup jendela sebelum menyelesaikan pembayaran.");
                }
            });
        });
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

</body>
</html>