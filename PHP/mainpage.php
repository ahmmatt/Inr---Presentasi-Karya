<?php
session_start();
require_once 'config/connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT a.id_attendee, a.status as ticket_status, 
               e.id_event, e.title, e.banner_image, e.start_date, e.end_date, e.start_time, e.end_time, e.status AS event_status, e.location_type, e.venue_name, e.city, e.location_details,
               u.full_name AS author_name, u.profile_picture AS author_image 
        FROM attendees a 
        JOIN events e ON a.id_event = e.id_event 
        LEFT JOIN users u ON e.id_admin = u.id_user 
        WHERE a.id_user = $user_id 
        ORDER BY e.start_date ASC";

$result = $conn->query($sql);
$upcoming_events = []; 
$past_events = []; 

// PERBAIKAN LOGIKA WAKTU: Cek berdasarkan Lokasi User Saat Ini
$user_tz = isset($_COOKIE['user_tz']) ? $_COOKIE['user_tz'] : 'Asia/Makassar';
date_default_timezone_set($user_tz);
$current_datetime = date('Y-m-d H:i:s');

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        
        // Gabungkan tanggal dan jam mulai menjadi format waktu penuh
        $start_datetime = $row['start_date'] . ' ' . $row['start_time'];
        
        // LOGIKA BARU PENENTUAN KAPAN ACARA BERAKHIR:
        // Gunakan secara presisi end_date dan end_time dari database
        $event_end_datetime = $row['end_date'] . ' ' . $row['end_time'];
        
        // Pindahkan ke Past JIKA waktu saat ini sudah melewati waktu SELESAI, atau status di DB 'ended'
        if ($current_datetime > $event_end_datetime || $row['event_status'] == 'ended') {
            $past_events[] = $row;
        } else {
            $upcoming_events[] = $row;
        }
    }
}

// Format Date Functions
function getMonthDate($dateStr) { return (new DateTime($dateStr))->format('M j'); } 
function getDayName($dateStr) { return (new DateTime($dateStr))->format('l'); }     
function getFullDate($dateStr) { return (new DateTime($dateStr))->format('M j, l'); }
function getTimeFormat($timeStr) { return (new DateTime($timeStr))->format('g:i A'); } 

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
    <title>SecureGate - Your Events</title>
    <link rel="stylesheet" href="../CSS/mainpage.css">
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
        <div class="page-frame-nav">
            <h1>Your Event</h1>
            <div class="toggle-select-event">
                <a href="#" id="btn-upcoming" class="active">Upcoming</a>
                <a href="#" id="btn-past">Past</a>
            </div>
        </div>
        
        <div class="upcoming-event" id="view-upcoming">
            <?php if(count($upcoming_events) > 0): ?>
                <?php foreach($upcoming_events as $ev): ?>
                    <div class="event-card-detail">
                        <a href="eticket.php?id=<?= $ev['id_attendee'] ?>">
                            <div class="card-top-header">
                                <div class="header-left"><h4><?= getFullDate($ev['start_date']) ?></h4></div>
                                <div class="header-right"><h4><?= getTimeFormat($ev['start_time']) ?></h4></div>
                            </div>
                            <hr class="card-divider">
                            
                            <div class="card-hero-img">
                                <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                <img src="<?= $img_path ?>" alt="Event Banner">
                            </div>
                            <h3 class="card-title"><?= htmlspecialchars($ev['title']) ?></h3>
                            
                            <div class="card-author" style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <?php 
                                    // 1. Ambil Nama (Jika kosong di DB, tampilkan "Unknown Admin")
                                    $author_name = !empty($ev['author_name']) ? htmlspecialchars($ev['author_name']) : "Unknown Admin";
                                    
                                    // 2. Ambil Huruf Pertama untuk Avatar Default
                                    $initial = strtoupper(substr($author_name, 0, 1));
                                    
                                    // 3. Tampilkan Foto atau Avatar Inisial Huruf
                                    if(!empty($ev['author_image'])): 
                                ?>
                                    <img src="../Media/uploads/<?= htmlspecialchars($ev['author_image']) ?>" alt="Author Profile" style="border-radius: 50%; object-fit: cover; width: 24px; height: 24px; background: #fff;">
                                <?php else: ?>
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                
                                <span style="font-size: 13px; color: #a0a0a0;"><?= $author_name ?></span>
                            </div>

                            <div class="card-info-blocks" style="align-items: flex-start; margin-top: 0;">
                                <div class="info-block" style="align-items: flex-start; flex: 1; padding-right: 12px;">
                                    <i class="block-icon fas fa-map-marker-alt" style="margin-top: 2px;"></i>
                                    <div class="block-text" style="flex: 1; width: 100%;">
                                        <h3 style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px; line-height: 1.2;">
                                            <?= $ev['location_type'] == 'online' ? 'Online' : (!empty($ev['venue_name']) ? htmlspecialchars($ev['venue_name']) : 'Offline') ?>
                                        </h3>
                                        <p style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; white-space: normal; overflow-wrap: anywhere; line-height: 1.4; color: #a0a0a0; font-size: 12px; margin: 0;">
                                            <?php 
                                                if($ev['location_type'] == 'online') {
                                                    echo "Online Event / Virtual Meeting";
                                                } else {
                                                    $city_text = !empty($ev['city']) ? htmlspecialchars($ev['city']) . ', ' : '';
                                                    echo $city_text . htmlspecialchars($ev['location_details']);
                                                }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="price-mini-block" style="background: none; border: none; padding: 0; margin-top: 2px;">
                                    <?php if($ev['ticket_status'] == 'need_approval'): ?>
                                        <span style="color:#f97316; background:rgba(249,115,22,0.1); padding:6px 12px; border-radius:8px; font-weight:bold; font-size: 13px;">
                                            <i class="fa-solid fa-clock-rotate-left" style="margin-right:4px;"></i> Pending
                                        </span>
                                        
                                    <?php elseif($ev['ticket_status'] == 'awaiting_payment'): ?>
                                        <span style="color:#ef4444; background:rgba(239,68,68,0.1); padding:6px 12px; border-radius:8px; font-weight:bold; font-size: 13px;">
                                            <i class="fa-solid fa-wallet" style="margin-right:4px;"></i> Pay Now
                                        </span>
                                        
                                    <?php elseif($ev['ticket_status'] == 'checked_in'): ?>
                                        <span style="color:#3b82f6; background:rgba(59,130,246,0.1); padding:6px 12px; border-radius:8px; font-weight:bold; font-size: 13px;">
                                            <i class="fa-solid fa-expand" style="margin-right:4px;"></i> Scanned
                                        </span>
                                        
                                    <?php else: ?>
                                        <span style="color:#22c55e; background:rgba(34,197,94,0.1); padding:6px 12px; border-radius:8px; font-weight:bold; font-size: 13px;">
                                            <i class="fa-solid fa-ticket-simple" style="margin-right:4px;"></i> E-Ticket Ready
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <h3>No Events Available</h3>
                    <p>You haven't registered for any future events yet. Let's find some!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="past-event" id="view-past" style="display: none;">
            <?php if(count($past_events) > 0): ?>
                <?php foreach($past_events as $ev): ?>
                    <div class="timeline-row">
                        <div class="left-info">
                            <h3><?= getMonthDate($ev['start_date']) ?></h3>
                            <h3><?= getDayName($ev['start_date']) ?></h3>
                        </div>
                        <div class="right-info">
                            <div class="past-event-card" style="flex-direction: row; justify-content: space-between; align-items: center; width: 100%;">
                                
                                <div class="left-past-card" style="flex: 1; padding-right: 20px;">
                                    <div class="time-event">
                                        <span class="begin-time"><?= getTimeFormat($ev['start_time']) ?></span>
                                        <span>·</span>
                                        <span>
                                            <?= !empty($ev['end_date']) && !empty($ev['start_time']) && strtotime($ev['end_date']) > 0 
                                                ? getTimeFormat($ev['end_date'] . ' ' . $ev['start_time']) 
                                                : 'Ended' ?> 
                                        </span>
                                        <span>WITA</span> 
                                    </div>
                                    
                                    <h2><?= htmlspecialchars($ev['title']) ?></h2>
                                    
                                    <div class="author-past" style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px;">
                                        <?php 
                                            $author_name = !empty($ev['author_name']) ? htmlspecialchars($ev['author_name']) : "Unknown Admin";
                                            $initial = strtoupper(substr($author_name, 0, 1));
                                            
                                            if(!empty($ev['author_image'])): 
                                        ?>
                                            <img src="../Media/uploads/<?= htmlspecialchars($ev['author_image']) ?>" alt="author-photo" style="border-radius: 50%; object-fit: cover; width: 20px; height: 20px; background: #fff;">
                                        <?php else: ?>
                                            <div style="width: 20px; height: 20px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                                <?= $initial ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p style="font-size: 12px; color: #888;">By</p>
                                        <p style="font-size: 12px; color: #ccc; font-weight: 600;"><?= $author_name ?></p>
                                    </div>
                                    
                                    <div class="past-location">
                                        <i class="fas <?= $ev['location_type'] == 'online' ? 'fa-link' : 'fa-map-marker-alt' ?>"></i>
                                        <p>
                                            <?php 
                                                if($ev['location_type'] == 'online') {
                                                    echo "Virtual Meeting / Online";
                                                } else {
                                                    echo !empty($ev['venue_name']) ? htmlspecialchars($ev['venue_name']) : htmlspecialchars($ev['city']);
                                                }
                                            ?>
                                        </p>
                                    </div>
                                    
                                    <p class="status-past going" style="background: transparent; color: #a0a0a0; border: 1px solid #444; border-radius: 8px; width: fit-content; padding: 4px 12px; font-weight: bold; margin-top: 12px;">Ended</p>
                                </div>

                                <div class="right-past-card" style="width: 180px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                    <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                    <img src="<?= $img_path ?>" alt="pamflet event" style="width: 100%; height: auto; border-radius: 10px; object-fit: cover;">
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h3>No Events Available</h3>
                    <p>You haven't attended any events in the past.</p>
                </div>
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
    
    <script src="../JS/mainpage.js"></script>
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