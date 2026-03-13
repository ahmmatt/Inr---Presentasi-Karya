<?php
session_start(); // WAJIB DITAMBAHKAN UNTUK MENGAMBIL DATA LOGIN

// CEK KEAMANAN SUPER KETAT: Harus login DAN rolenya harus admin/superadmin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: admin_signin.php");
    exit();
}

// 1. KONEKSI DATABASE
require_once 'config/connect.php';

// 2. MENANGANI AKSI TOMBOL (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $cur_ev_id = isset($_POST['current_event_id']) ? (int)$_POST['current_event_id'] : 0;

    // A. Hapus Event Keseluruhan
    if ($action === 'delete_event') {
        $eid = (int)$_POST['event_id'];
        $conn->query("DELETE FROM events WHERE id_event = $eid");
        header("Location: adminevent.php"); 
        exit;
    }
    // B. Ubah Status Peserta (Approve / Confirm Pay / Check-In / Revert)
    elseif ($action === 'update_status') {
        $aid = (int)$_POST['attendee_id'];
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $conn->query("UPDATE attendees SET status = '$new_status' WHERE id_attendee = $aid");
        header("Location: adminevent.php?event_id=" . $cur_ev_id); 
        exit;
    }
    // C. Hapus 1 Peserta
    elseif ($action === 'delete_attendee') {
        $aid = (int)$_POST['attendee_id'];
        $conn->query("DELETE FROM attendees WHERE id_attendee = $aid");
        header("Location: adminevent.php?event_id=" . $cur_ev_id); 
        exit;
    }
    // D. Simpan Perubahan dari Modal Edit
    elseif ($action === 'edit_attendee') {
        $aid = (int)$_POST['edit_attendee_id'];
        $name = $conn->real_escape_string($_POST['edit_name']);
        $email = $conn->real_escape_string($_POST['edit_email']);
        $seat = $conn->real_escape_string($_POST['edit_seat']);
        $status = $conn->real_escape_string($_POST['edit_status']);
        
        $conn->query("UPDATE attendees SET full_name='$name', email='$email', seat_number='$seat', status='$status' WHERE id_attendee = $aid");
        header("Location: adminevent.php?event_id=" . $cur_ev_id); 
        exit;
    }
}

// 3. MENGAMBIL DATA EVENT UNTUK DROPDOWN
$events = [];
$res_events = $conn->query("SELECT id_event, title, status FROM events ORDER BY created_at DESC");
while($row = $res_events->fetch_assoc()) {
    $events[] = $row;
}

// Tentukan event mana yang sedang aktif dilihat (Berdasarkan URL ?event_id=... atau ambil event terbaru)
$current_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (count($events) > 0 ? $events[0]['id_event'] : 0);

// 4. MENGAMBIL DETAIL EVENT AKTIF
$current_event = null;
if ($current_event_id > 0) {
    $res = $conn->query("SELECT * FROM events WHERE id_event = $current_event_id");
    $current_event = $res->fetch_assoc();
}

// 5. MENGAMBIL DATA PESERTA & MENGHITUNG METRIK KEUANGAN
$attendees = [];
$total_reg = 0; $pending = 0; $checked_in = 0; $revenue = 0;

if ($current_event_id > 0) {
    // JOIN dengan tabel ticket_tiers untuk mengambil nama tiket dan harganya
    $sql_att = "SELECT a.*, t.tier_name, t.price 
                FROM attendees a 
                JOIN ticket_tiers t ON a.id_tier = t.id_tier 
                WHERE a.id_event = $current_event_id 
                ORDER BY a.registered_at DESC";
    $res_att = $conn->query($sql_att);
    
    while($row = $res_att->fetch_assoc()) {
        $attendees[] = $row;
        $total_reg++;
        if ($row['status'] === 'need_approval') $pending++;
        
        // PERBAIKAN 1: Hitung status 'checked_in'
        if ($row['status'] === 'checked_in') $checked_in++;
        
        // PERBAIKAN 2: Tambahkan ke revenue jika 'checked_in'
        if (in_array($row['status'], ['approved', 'checked_in'])) {
            $revenue += (float)$row['price'];
        }
    }
}

// Fungsi Format Rupiah PHP
function formatRupiah($num) {
    if ($num >= 1000000) return "Rp " . number_format($num / 1000000, 1, ',', '.') . "M";
    if ($num >= 1000) return "Rp " . number_format($num / 1000, 1, ',', '.') . "K";
    return "Rp " . number_format($num, 0, ',', '.');
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
    <title>SecureGate - Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/adminevent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        if (localStorage.getItem('securegate_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <i class="fa-solid fa-bars hamburger-btn" id="hamburger-btn"></i>
            <h1>SecureGate</h1>
        </div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
            <div class="main-nav-event"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
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
        
        <?php if ($current_event): ?>
        <div class="page-frame-nav admin-header">
            <div class="event-switcher-wrapper">
                <p>Currently Managing:</p>
                <div class="event-switcher" id="event-switcher-btn">
                    <h1><?= htmlspecialchars($current_event['title']) ?> 
                        <?= $current_event['status'] == 'ended' ? '<span class="badge-ended-title">Ended</span>' : '' ?>
                    </h1>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                
                <div class="event-dropdown" id="event-dropdown">
                    <div class="search-event">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="dropdown-search-input" placeholder="Search other events...">
                    </div>
                    <ul class="event-list" id="dropdown-event-list">
                        <?php foreach($events as $ev): ?>
                            <li class="<?= $ev['id_event'] == $current_event_id ? 'active-event' : '' ?>" 
                                onclick="window.location.href='adminevent.php?event_id=<?= $ev['id_event'] ?>'">
                                <?= $ev['id_event'] == $current_event_id && $ev['status'] != 'ended' ? '<i class="fa-solid fa-check"></i> ' : '' ?>
                                <?= htmlspecialchars($ev['title']) ?>
                                <?= $ev['status'] == 'ended' ? '<span class="badge-ended">Ended</span>' : '' ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="header-actions">
                <a href="scanner.php?event_id=<?= $current_event_id ?>" class="btn-primary"><i class="fa-solid fa-qrcode"></i> Open Scanner</a>
                
                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this event and ALL its data?');">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="event_id" value="<?= $current_event_id ?>">
                    <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i> Delete Event</button>
                </form>
            </div>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-ticket"></i></div>
                <div class="metric-info">
                    <p>Total Registrations</p>
                    <h3><?= $total_reg ?> <span class="metric-total">/ <?= $current_event['max_capacity'] ? $current_event['max_capacity'] : '∞' ?></span></h3>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-user-clock"></i></div>
                <div class="metric-info">
                    <p>Need Approval</p>
                    <h3><?= $pending ?></h3>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon"><i class="fa-solid fa-user-check"></i></div>
                <div class="metric-info">
                    <p>Checked-In</p>
                    <h3><?= $checked_in ?></h3>
                </div>
            </div>
            <?php
                // Logika Cek Waktu Berakhir Event YANG BENAR (Sesuai Database)
                date_default_timezone_set('Asia/Makassar');
                $curr_time = date('Y-m-d H:i:s');
                $end_time_full = $current_event['end_date'] . ' ' . $current_event['end_time'];
                
                // Tombol bisa diklik jika waktu sekarang lebih besar dari waktu selesai, atau status sudah ended
                $is_ended = ($curr_time > $end_time_full || $current_event['status'] == 'ended') ? 'true' : 'false';
            ?>
            <div class="metric-card clickable-card" onclick="if(<?= $is_ended ?>){ window.location.href='withdrawal.php?event_id=<?= $current_event_id ?>'; } else { alert('Event belum selesai! Penarikan dana (Withdrawal) hanya bisa dilakukan setelah event berakhir.'); }">
                <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
                <div class="metric-info">
                    <p>Est. Revenue <i class="fa-solid fa-arrow-up-right-from-square icon-small-right"></i></p>
                    <h3><?= formatRupiah($revenue) ?></h3>
                </div>
            </div>
        </div>

        <div class="admin-workspace">
            <div class="tabs-nav">
                <button class="tab-btn active" data-tab="all">All Guests</button>
                <button class="tab-btn" data-tab="need_approval">Need Approval <?= $pending > 0 ? "<span class='badge-count'>$pending</span>" : "" ?></button>
                <button class="tab-btn" data-tab="awaiting_payment">Awaiting Payment</button>
                <button class="tab-btn" data-tab="approved">Approved</button>
                <button class="tab-btn" data-tab="present">Checked-In</button>
            </div>

            <div class="table-tools">
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="search-input" placeholder="Search by name, email, or Ticket ID...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Attendee Info</th>
                            <th>Category & Seat</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendee-table-body">
                        <?php if(count($attendees) == 0): ?>
                            <tr><td colspan="5" class="empty-table-cell">No attendees registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($attendees as $att): ?>
                            <tr>
                                <td class="t-id"><?= htmlspecialchars($att['ticket_code']) ?></td>
                                <td>
                                    <div class="attendee-cell">
                                        <div class="avatar-placeholder"><?= strtoupper(substr($att['full_name'], 0, 1)) ?></div>
                                        <div>
                                            <h4><?= htmlspecialchars($att['full_name']) ?></h4>
                                            <p><?= htmlspecialchars($att['email']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="t-category <?= strtolower($att['tier_name']) == 'vip' ? 'text-vip' : '' ?>"><?= htmlspecialchars($att['tier_name']) ?></span><br>
                                    <span class="t-seat"><?= htmlspecialchars($att['seat_number'] ? $att['seat_number'] : 'Open Seating') ?></span>
                                </td>
                                <td>
                                    <?php if($att['status'] == 'need_approval'): ?>
                                        <span class="status-badge need_approval">Need Approval</span>
                                    <?php elseif($att['status'] == 'awaiting_payment'): ?>
                                        <span class="status-badge awaiting_payment">Awaiting Payment</span>
                                    <?php elseif($att['status'] == 'approved'): ?>
                                        <span class="status-badge approved">Approved</span>
                                    <?php elseif($att['status'] == 'checked_in'): ?>
                                        <span class="status-badge present">Checked-In</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-cell">
                                    <form method="POST" class="display-contents">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="attendee_id" value="<?= $att['id_attendee'] ?>">
                                        <input type="hidden" name="current_event_id" value="<?= $current_event_id ?>">
                                        
                                        <?php if($att['status'] == 'need_approval'): ?>
                                            <?php 
                                                // LOGIKA CERDAS: Cek apakah tiket ini gratis atau berbayar
                                                $target_status = ($att['price'] == 0) ? 'approved' : 'awaiting_payment'; 
                                            ?>
                                            <input type="hidden" name="new_status" value="<?= $target_status ?>">
                                            <button type="submit" class="action-btn approve-tkt" title="Approve Request"><i class="fa-solid fa-thumbs-up"></i></button>
                                        
                                        <?php elseif($att['status'] == 'awaiting_payment'): ?>
                                            <input type="hidden" name="new_status" value="approved">
                                            <button type="submit" class="action-btn pay-confirm" title="Confirm Payment Received"><i class="fa-solid fa-money-bill-wave"></i></button>
                                            
                                        <?php elseif($att['status'] == 'approved'): ?>
                                            <input type="hidden" name="new_status" value="checked_in">
                                            <button type="submit" class="action-btn checkin" title="Manual Check-In"><i class="fa-solid fa-check-to-slot"></i></button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if($att['status'] != 'need_approval'): ?>
                                    <form method="POST" class="display-contents" onsubmit="return confirm('Revert status for this user?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="attendee_id" value="<?= $att['id_attendee'] ?>">
                                        <input type="hidden" name="current_event_id" value="<?= $current_event_id ?>">
                                        <?php 
                                            $revert_target = 'need_approval';
                                            if($att['status'] == 'checked_in') {
                                                $revert_target = 'approved';
                                            } elseif($att['status'] == 'approved') {
                                                // Jika gratis kembalikan ke butuh persetujuan, jika berbayar ke nunggu bayar
                                                $revert_target = ($att['price'] == 0) ? 'need_approval' : 'awaiting_payment';
                                            }
                                        ?>
                                        <input type="hidden" name="new_status" value="<?= $revert_target ?>">
                                        <button type="submit" class="action-btn revert" title="Revert / Cancel"><i class="fa-solid fa-rotate-left"></i></button>
                                    </form>
                                    <?php endif; ?>

                                    <button type="button" class="action-btn edit edit-btn-trigger" 
                                            data-id="<?= $att['id_attendee'] ?>"
                                            data-name="<?= htmlspecialchars($att['full_name']) ?>"
                                            data-email="<?= htmlspecialchars($att['email']) ?>"
                                            data-seat="<?= htmlspecialchars($att['seat_number']) ?>"
                                            data-status="<?= $att['status'] ?>" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <form method="POST" class="display-contents" onsubmit="return confirm('Remove attendee permanently?');">
                                        <input type="hidden" name="action" value="delete_attendee">
                                        <input type="hidden" name="attendee_id" value="<?= $att['id_attendee'] ?>">
                                        <input type="hidden" name="current_event_id" value="<?= $current_event_id ?>">
                                        <button type="submit" class="action-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </form>

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
            <div class="no-events-wrapper">
                <i class="fa-solid fa-calendar-xmark no-events-icon"></i>
                <h2>No Events Found</h2>
                <p>You haven't created any events yet.</p>
                <a href="create.php" class="btn-primary btn-inline-auto"><i class="fa-solid fa-plus"></i> Create New Event</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="edit-modal-overlay"></div>
    <div class="edit-modal" id="edit-modal">
        <form method="POST" action="adminevent.php">
            <input type="hidden" name="action" value="edit_attendee">
            <input type="hidden" name="edit_attendee_id" id="edit_attendee_id">
            <input type="hidden" name="current_event_id" value="<?= $current_event_id ?>">

            <div class="modal-header-intro">
                <div class="modal-header-icon"><i class="fa-solid fa-user-pen"></i></div>
                <h2>Edit Attendee</h2>
                <p>Modify guest details or manually change their status.</p>
            </div>

            <div class="edit-form-group">
                <label>Full Name</label>
                <input type="text" name="edit_name" id="edit-name" class="custom-input" required>
            </div>
            
            <div class="edit-form-group">
                <label>Email Address</label>
                <input type="email" name="edit_email" id="edit-email" class="custom-input" required>
            </div>

            <div class="edit-form-group">
                <label>Seat Number</label>
                <input type="text" name="edit_seat" id="edit-seat" class="custom-input">
            </div>

            <div class="edit-form-group mt-8">
                <label>Current Status</label>
                <select name="edit_status" id="edit-status" class="custom-select">
                    <option value="need_approval">Need Approval (Pending)</option>
                    <option value="awaiting_payment">Awaiting Payment (Unpaid)</option>
                    <option value="approved">Approved (Paid & Has Ticket)</option>
                    <option value="checked_in">Checked-In (Present)</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="close-modal-btn">Cancel</button>
                <button type="submit" class="btn-confirm">Save Changes</button>
            </div>
        </form>
    </div>
    
    <script src="../JS/adminevent.js"></script>
</body>
</html>