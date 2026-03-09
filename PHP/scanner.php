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
</head>
<body>
    <nav class="navbar">
        <div class="left-nav"><h1>SecureGate</h1></div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
            <div class="main-nav-event"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
        </div>
        <div class="right-nav" style="display: flex; align-items: center; gap: 15px; position: relative;">
            <i class="fa-regular fa-bell" style="font-size: 18px; color: #a0a0a0; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a0a0a0'"></i>
            
            <div id="profile-dropdown-trigger" style="cursor: pointer; position: relative;" title="<?= htmlspecialchars($nav_name) ?>">
                <?php if(!empty($nav_pic)): ?>
                    <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #2a2a2a; display: block;">
                <?php else: ?>
                    <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; border: 2px solid #2a2a2a;">
                        <?= $nav_initial ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="profile-dropdown-menu" style="display: none; position: absolute; top: 50px; right: 0; background: #121212; border: 1px solid #333; border-radius: 12px; width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); z-index: 1000; overflow: hidden;">
                <div style="padding: 15px; border-bottom: 1px solid #2a2a2a; display: flex; align-items: center; gap: 12px;">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; flex-shrink: 0;"><?= $nav_initial ?></div>
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
        <div class="page-frame-nav">
            <div>
                <h1>Ticket Scanner</h1>
                <p>Scan attendee QR codes to verify and check-in.</p>
            </div>
        </div>

        <div class="scanner-layout">
            <div class="scanner-section">
                <div class="camera-container" id="camera-container" style="overflow: hidden; border-radius: 12px; background: #000; position: relative;">
                    <div id="reader" style="width: 100%; border: none;"></div>
                    <div class="laser-beam" id="laser-beam" style="display:none;"></div>
                    
                    <div class="camera-overlay-text" id="camera-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666; width: 100%;">
                        <i class="fa-solid fa-camera camera-icon" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <p id="camera-text">Camera Standby</p>
                    </div>
                </div>

                <button class="btn-action btn-scan" id="btn-start-scan" style="width: 100%; margin-top: 20px; padding: 16px; background: #22c55e; color: #000; font-weight: bold; border-radius: 12px; border: none; cursor: pointer; font-size: 16px;">
                    <i class="fa-solid fa-qrcode"></i> Start Scanning
                </button>
            </div>

            <div class="result-section">
                <div class="empty-state" id="empty-state" style="text-align: center; padding: 50px 20px; background: #1a1a1a; border-radius: 16px; border: 1px dashed #333;">
                    <i class="fa-solid fa-expand" style="font-size: 40px; color: #444; margin-bottom: 16px;"></i>
                    <h3 style="color: #fff;">Awaiting Scan</h3>
                    <p style="color: #888; font-size: 14px;">Point the camera at the attendee's e-ticket.</p>
                </div>

                <div class="scan-result-card" id="scan-result-card" style="display: none; background: #1a1a1a; border-radius: 16px; padding: 24px; border: 1px solid #333;">
                    <div class="status-badge" id="ticket-status" style="padding: 8px 16px; border-radius: 8px; font-weight: bold; font-size: 14px; margin-bottom: 20px; display: inline-block;"></div>

                    <div class="attendee-info" style="display: flex; align-items: center; gap: 24px; margin-bottom: 24px; padding-top: 10px;">
                        
                        <img id="attendee-photo" src="" alt="Attendee Photo" style="width: 140px; height: 140px; border-radius: 16px; object-fit: cover; display: none; flex-shrink: 0; border: 3px solid #333; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">
                        
                        <div id="attendee-initial" style="width: 140px; height: 140px; border-radius: 16px; background: #3b82f6; display: flex; justify-content: center; align-items: center; font-size: 64px; font-weight: bold; color: #fff; flex-shrink: 0; border: 3px solid #333; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">A</div>
                        
                        <div style="flex: 1; text-align: left; overflow: hidden;">
                            <h2 style="color: #fff; font-size: 20px; margin-bottom: 8px; line-height: 1.2; word-wrap: break-word;" id="attendee-name">Nama Tamu</h2>
                            <p style="color: #a0a0a0; font-size: 15px; word-break: break-all;" id="attendee-email">email@contoh.com</p>
                        </div>
                        
                    </div>

                    <hr class="divider" style="border-color: #333; margin-bottom: 20px;">

                    <div class="ticket-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div class="detail-item">
                            <span style="color: #888; font-size: 12px; display: block; margin-bottom: 4px;">Ticket Type</span>
                            <h4 style="color: #fff; font-size: 15px;" id="ticket-tier">VIP Access</h4>
                        </div>
                        <div class="detail-item">
                            <span style="color: #888; font-size: 12px; display: block; margin-bottom: 4px;">Seat Number</span>
                            <h4 style="color: #fff; font-size: 15px;" id="ticket-seat">Row A - 12</h4>
                        </div>
                        <div class="detail-item">
                            <span style="color: #888; font-size: 12px; display: block; margin-bottom: 4px;">Ticket ID</span>
                            <h4 style="color: #fff; font-size: 15px; font-family: monospace;" id="ticket-id">#SG-8829-XV</h4>
                        </div>
                    </div>

                    <button class="btn-action btn-approve" id="btn-approve" style="width: 100%; padding: 16px; background: #3b82f6; color: #fff; font-weight: bold; border-radius: 12px; border: none; cursor: pointer; font-size: 16px; display: none;">
                        <i class="fa-solid fa-check-circle"></i> Approve & Check-In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../JS/scanner.js"></script>
    <script>
        // SCRIPT UNTUK DROPDOWN NAVBAR
        document.addEventListener('DOMContentLoaded', function() {
            const profileTrigger = document.getElementById('profile-dropdown-trigger');
            const profileMenu = document.getElementById('profile-dropdown-menu');

            if (profileTrigger && profileMenu) {
                profileTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
                });

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