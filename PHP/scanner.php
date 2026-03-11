<?php
session_start();

// CEK KEAMANAN SUPER KETAT: Harus login DAN rolenya harus admin/superadmin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: admin_signin.php");
    exit();
}

// 1. Panggil file koneksi database
require_once 'config/connect.php';

// Tangkap ID event dari URL
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    echo "<script>alert('Pilih event terlebih dahulu dari Dashboard!'); window.location.href='adminevent.php';</script>";
    exit();
}
$event_id = (int)$_GET['event_id'];

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
    <title>SecureGate - Scan Ticket</title>
    <link rel="stylesheet" href="../CSS/scanner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        if (localStorage.getItem('securegate_theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate</h1></div>
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
        <div class="page-frame-nav">
            <div>
                <h1>Ticket Scanner</h1>
                <p>Scan attendee QR codes to verify and check-in.</p>
            </div>
        </div>

        <div class="scanner-layout">
            <div class="scanner-section">
                <div class="camera-container" id="camera-container">
                    <div id="reader"></div>
                    <div class="camera-overlay-text" id="camera-overlay">
                        <i class="fa-solid fa-camera camera-icon"></i>
                        <p id="camera-text">Camera Standby</p>
                    </div>
                </div>

                <button class="btn-action btn-scan" id="btn-start-scan">
                    <i class="fa-solid fa-qrcode"></i> Start Scanning
                </button>
            </div>

            <div class="result-section">
                <div class="empty-state" id="empty-state">
                    <i class="fa-solid fa-expand"></i>
                    <h3>Awaiting Scan</h3>
                    <p>Point the camera at the attendee's e-ticket.</p>
                </div>

                <div class="scan-result-card" id="scan-result-card">
                    <div class="status-badge" id="ticket-status"></div>

                    <div class="attendee-info">
                        <img id="attendee-photo" class="attendee-pict" src="" alt="Attendee Photo">
                        
                        <div id="attendee-initial" class="attendee-initial">A</div>
                        
                        <div class="attendee-details">
                            <h2 id="attendee-name">Nama Tamu</h2>
                            <p id="attendee-email">email@contoh.com</p>
                        </div>
                    </div>

                    <hr class="divider">

                    <div class="ticket-details-grid">
                        <div class="detail-item">
                            <span>Ticket Type</span>
                            <h4 id="ticket-tier">VIP Access</h4>
                        </div>
                        <div class="detail-item">
                            <span>Seat Number</span>
                            <h4 id="ticket-seat">Row A - 12</h4>
                        </div>
                        <div class="detail-item">
                            <span>Ticket ID</span>
                            <h4 id="ticket-id" class="monospace">#SG-8829-XV</h4>
                        </div>
                    </div>

                    <button class="btn-action btn-approve" id="btn-approve">
                        <i class="fa-solid fa-check-circle"></i> Approve & Check-In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../JS/scanner.js"></script>
</body>
</html>