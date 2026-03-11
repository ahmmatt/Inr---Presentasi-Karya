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
                            
                            <div class="card-author author-info-wrapper">
                                <?php 
                                    // 1. Ambil Nama (Jika kosong di DB, tampilkan "Unknown Admin")
                                    $author_name = !empty($ev['author_name']) ? htmlspecialchars($ev['author_name']) : "Unknown Admin";
                                    
                                    // 2. Ambil Huruf Pertama untuk Avatar Default
                                    $initial = strtoupper(substr($author_name, 0, 1));
                                    
                                    // 3. Tampilkan Foto atau Avatar Inisial Huruf
                                    if(!empty($ev['author_image'])): 
                                ?>
                                    <img src="../Media/uploads/<?= htmlspecialchars($ev['author_image']) ?>" alt="Author Profile" class="author-img-small">
                                <?php else: ?>
                                    <div class="author-initial-small">
                                        <?= $initial ?>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="author-name-text"><?= $author_name ?></span>
                            </div>

                            <div class="card-info-blocks info-blocks-compact">
                                <div class="info-block info-block-expanded">
                                    <div class="block-icon icon-mt-2">
                                        <i class="fas fa-location-dot"></i>
                                    </div>
                                    <div class="block-text text-flex-1">
                                        <h3 class="location-title-small">
                                            <?= $ev['location_type'] == 'online' ? 'Online' : (!empty($ev['venue_name']) ? htmlspecialchars($ev['venue_name']) : 'Offline') ?>
                                        </h3>
                                        <p class="location-desc-small">
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
                                
                                <div class="price-mini-block-alt">
                                    <?php if($ev['ticket_status'] == 'need_approval'): ?>
                                        <span class="ticket-badge-pending">
                                            <i class="fa-solid fa-clock-rotate-left badge-icon-mr"></i> Pending
                                        </span>
                                        
                                    <?php elseif($ev['ticket_status'] == 'awaiting_payment'): ?>
                                        <span class="ticket-badge-pay">
                                            <i class="fa-solid fa-wallet badge-icon-mr"></i> Pay Now
                                        </span>
                                        
                                    <?php elseif($ev['ticket_status'] == 'checked_in'): ?>
                                        <span class="ticket-badge-scanned">
                                            <i class="fa-solid fa-expand badge-icon-mr"></i> Scanned
                                        </span>
                                        
                                    <?php else: ?>
                                        <span class="ticket-badge-ready">
                                            <i class="fa-solid fa-ticket-simple badge-icon-mr"></i> E-Ticket Ready
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

        <div class="past-event hidden-display" id="view-past">
            <?php if(count($past_events) > 0): ?>
                <?php foreach($past_events as $ev): ?>
                    <div class="timeline-row">
                        <div class="left-info">
                            <h3><?= getMonthDate($ev['start_date']) ?></h3>
                            <h3><?= getDayName($ev['start_date']) ?></h3>
                        </div>
                        <div class="right-info">
                            <div class="past-event-card past-card-inner">
                                
                                <div class="left-past-card past-card-left-col">
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
                                    
                                    <div class="author-past past-author-wrapper">
                                        <?php 
                                            $author_name = !empty($ev['author_name']) ? htmlspecialchars($ev['author_name']) : "Unknown Admin";
                                            $initial = strtoupper(substr($author_name, 0, 1));
                                            
                                            if(!empty($ev['author_image'])): 
                                        ?>
                                            <img src="../Media/uploads/<?= htmlspecialchars($ev['author_image']) ?>" alt="author-photo" class="past-author-img">
                                        <?php else: ?>
                                            <div class="past-author-initial">
                                                <?= $initial ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="past-author-by">By</p>
                                        <p class="past-author-name"><?= $author_name ?></p>
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
                                    
                                    <p class="status-past past-status-ended">Ended</p>
                                </div>

                                <div class="right-past-card past-card-right-col">
                                    <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                    <img src="<?= $img_path ?>" alt="pamflet event" class="past-card-img">
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
</body>
</html>