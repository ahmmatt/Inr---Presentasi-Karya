<?php
session_start();
require_once 'config/connect.php';

// =======================================================
// 0. TANGKAP SINYAL AJAX DARI JAVASCRIPT (PEMBAYARAN SUKSES)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'payment_success') {
    header('Content-Type: application/json');
    $att_id = (int)$_POST['attendee_id'];
    
    // Ubah status tiket menjadi approved
    $conn->query("UPDATE attendees SET status = 'approved' WHERE id_attendee = $att_id AND id_user = " . $_SESSION['user_id']);
    
    // --- PEMICU EMAIL CERDAS (Opsional jika Anda pasang fitur ini) ---
    if(file_exists('send_ticket_mail.php')) {
        require_once 'send_ticket_mail.php';
        sendVirtualTicketEmail($conn, $att_id);
    }
    // ----------------------------------------------------------------

    echo json_encode(['status' => 'success']);
    exit();
}

// 1. CEK LOGIN & KUNCI KHUSUS USER BIASA
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo "<script>alert('Akses Ditolak! Anda sedang menggunakan Sesi Admin. Silakan Logout terlebih dahulu dan login menggunakan akun User biasa untuk membeli tiket.'); window.location.href='../index.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_email = $_SESSION['email'];

// 2. Cek ID Event di URL (Misal: ticket.php?id=1)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Event not found.'); window.location.href='discover.php';</script>";
    exit();
}

$event_id = (int)$_GET['id'];

// 3. Ambil Data Event
$sql_ev = "SELECT e.*, u.full_name AS organizer_name, u.profile_picture AS organizer_pic 
           FROM events e 
           LEFT JOIN users u ON e.id_admin = u.id_user 
           WHERE e.id_event = $event_id";
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
require_once 'config/secret_keys.php';

\Midtrans\Config::$serverKey = $MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$snapToken = ''; 
$new_attendee_id = 0; // Siapkan variabel untuk menyimpan ID tiket baru

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_tier'])) {
    $tier_id = (int)$_POST['ticket_tier'];
    $seat_num = isset($_POST['seat_num']) ? $conn->real_escape_string($_POST['seat_num']) : '';
    
    // Generate Order ID Unik & QR Token
    $order_id = "SG-E" . $event_id . "-U" . $user_id . "-" . rand(1000,9999);
    $qr_token = hash('sha256', $order_id . time());

    // Cari Harga Tiket
    $selected_price = 0;
    foreach($tickets as $t) { if($t['id_tier'] == $tier_id) $selected_price = $t['price']; }
    
    $is_require_approval = ($event['require_approval'] == 1);
    
    if ($is_require_approval) {
        $status = 'need_approval';
    } else {
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

        if ($is_require_approval) {
            echo "<script>
                    alert('Registration submitted successfully! Please wait for Admin approval. You can check your status in My Events.');
                    window.location.href='mainpage.php';
                  </script>";
            exit();
            
        } else if ($selected_price == 0) {
            echo "<script>
                    alert('Ticket Successfully Registered!');
                    window.location.href='eticket.php?id=$new_attendee_id';
                  </script>";
            exit();
            
        } else {
            // Tambahkan time() pada order_id agar mencegah error Midtrans "order_id sudah digunakan"
            $transaction = array(
                'transaction_details' => array('order_id' => $order_id . '-' . time(), 'gross_amount' => $selected_price),
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
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate</h1></div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-regular fa-compass"></i><a href="discover.php">Discover</a></div>
            <div class="main-nav-event"><i class="fa-solid fa-ticket"></i><a href="mainpage.php">My Events</a></div>
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
        <div class="create-wrapper-layout">
            
            <div class="left-layout">
                <div class="add-pict-card view-mode">
                    <?php $img_path = !empty($event['banner_image']) ? "../Media/uploads/" . htmlspecialchars($event['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                    <img src="<?= $img_path ?>" alt="Event Cover">
                </div>

                <div class="presented-wrapper">
                    <div class="presented-left">
                        <?php 
                            $admin_name = !empty($event['organizer_name']) ? htmlspecialchars($event['organizer_name']) : "SecureGate User";
                            $admin_initial = strtoupper(substr($admin_name, 0, 1));
                        ?>
                        
                        <div class="presented-logo">
                            <?php if(!empty($event['organizer_pic'])): ?>
                                <img src="../Media/uploads/<?= htmlspecialchars($event['organizer_pic']) ?>" alt="organizer" style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 36px; height: 36px; border-radius: 8px; background-color: #f97316; color: #fff; display: flex; justify-content: center; align-items: center; font-size: 14px; font-weight: bold;">
                                    <?= $admin_initial ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="presented-detail">
                            <p>Organized By</p>
                            <h3><?= $admin_name ?> <i class="fa-solid fa-circle-check verified-icon"></i></h3>
                        </div>
                    </div>
                </div>
                <hr>

                <div class="maps-or-link-card">
                    <h4>Location or Virtual Link</h4>
                    
                    <div class="link-wrapper">
                        <?php 
                            if ($event['location_type'] == 'online') {
                                $icon_class = 'fa-link';
                                $display_text = "Virtual Meeting (Tautan ada di E-Ticket)";
                                $show_copy = false;
                                $virtual_class = 'is-virtual'; 
                            } else {
                                $copy_target = (!empty($event['maps_link']) && $event['maps_link'] !== 'NULL') ? $event['maps_link'] : $event['location_details'];
                                $icon_class = 'fa-map-location-dot';
                                $display_text = htmlspecialchars($copy_target);
                                $show_copy = true;
                                $virtual_class = '';
                            }
                        ?>
                        
                        <i class="fa-solid <?= $icon_class ?> link-icon-main"></i>
                        
                        <div class="url-box">
                            <span id="virtual-link-text" class="<?= $virtual_class ?>" title="<?= $display_text ?>">
                                <?= $display_text ?>
                            </span>
                            
                            <?php if($show_copy): ?>
                                <i class="fa-regular fa-copy copy-icon-btn" id="copy-link-btn" title="Copy Link" data-url="<?= $copy_target ?>"></i>
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
                                    <i class="fa-solid fa-arrow-up-right-from-square arrow-icon-small"></i>
                                </h3>
                                
                                <p>
                                    <?php 
                                        if($event['location_type'] == 'online') {
                                            echo "Online Event (Zoom / Virtual Meet)"; 
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
                            <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="User Avatar" class="profile-img-reg">
                        <?php else: ?>
                            <div class="user-avatar-reg"><?= $nav_initial ?></div>
                        <?php endif; ?>
                        
                        <div class="user-info-reg">
                            <h3><?= htmlspecialchars($user_name) ?></h3>
                            <h3 class="email-text"><?= htmlspecialchars($user_email) ?></h3>
                        </div>
                    </div>
                    
                    <?php if ($event['status'] == 'ended'): ?>
                        <button type="button" class="btn-register btn-disabled" disabled>Event Has Ended</button>
                    <?php elseif (empty($nav_pic)): ?>
                        <button type="button" class="btn-register btn-setup-photo" id="btn-setup-photo">
                            <i class="fa-solid fa-camera"></i> Setup Profile Photo
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-register" id="open-register-modal">Buy Ticket</button>
                    <?php endif; ?>
                </div>

                <div class="register-modal" id="register-modal">
                    <i class="fa-solid fa-xmark close-modal" id="close-register-modal"></i>

                    <form method="POST" id="checkout-form">
                        <div id="step-1-form" class="modal-step">
                            <h3 class="modal-step-title-text">Registration Details</h3>
                            
                            <input type="hidden" name="ticket_tier" id="selected-tier-id" required>
                            
                            <?php if(count($questions) > 0): ?>
                                <?php foreach($questions as $q): ?>
                                    <div class="modal-input-group">
                                        <span class="modal-label"><?= htmlspecialchars($q['question_text']) ?></span>
                                        <input type="text" name="answers[<?= $q['id_question'] ?>]" required class="modal-input">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="modal-input-group <?= $event['seat_assignment'] == 'bebas' ? 'hidden-step' : '' ?>">
                                <span class="modal-label">Choose your seat number (Optional)</span>
                                <input type="text" name="seat_num" placeholder="e.g. A-12" class="modal-input">
                            </div>
                            
                            <button type="button" class="btn-proceed btn-green" id="btn-to-payment">Proceed to Payment</button>
                        </div>
                    </form>
                </div>
                <div id="modal-overlay" class="modal-overlay-bg"></div>

                <div class="event-description">
                    <h4 class="about-event-title">About Event</h4>
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
            // Tutup modal secara paksa saat Midtrans terbuka
            document.getElementById('register-modal').classList.remove('show');
            document.getElementById('modal-overlay').style.display = 'none';
            document.body.style.overflow = 'auto';

            window.snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result) {
                    // JANGAN LANGSUNG REDIRECT, KITA UPDATE DATABASE DULU VIA AJAX
                    let formData = new FormData();
                    formData.append('action', 'payment_success');
                    formData.append('attendee_id', <?= $new_attendee_id ?>);

                    fetch('ticket.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            alert("Pembayaran Berhasil! Tiket Anda sudah aktif.");
                            // Langsung arahkan ke halaman e-ticket dengan ID yang baru saja lunas
                            window.location.href = "eticket.php?id=<?= $new_attendee_id ?>"; 
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Sistem gagal memperbarui tiket secara otomatis. Harap hubungi admin.");
                        window.location.href = "mainpage.php"; 
                    });
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
</body>
</html>