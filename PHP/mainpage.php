<?php
session_start();
require_once '../PHP/config/connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT a.id_attendee, a.status as ticket_status, 
               e.id_event, e.title, e.banner_image, e.start_date, e.end_date, e.start_time, e.location_type, e.location_details 
        FROM attendees a JOIN events e ON a.id_event = e.id_event 
        WHERE a.id_user = $user_id ORDER BY e.start_date ASC";

$result = $conn->query($sql);
$upcoming_events = []; $past_events = []; $today = date('Y-m-d');

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if ($row['end_date'] < $today) $past_events[] = $row;
        else $upcoming_events[] = $row;
    }
}

// Format Date Functions
function getMonthDate($dateStr) { return (new DateTime($dateStr))->format('M j'); } 
function getDayName($dateStr) { return (new DateTime($dateStr))->format('l'); }     
function getFullDate($dateStr) { return (new DateTime($dateStr))->format('M j, l'); }
function getTimeFormat($timeStr) { return (new DateTime($timeStr))->format('g:i A'); } 
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
            <i class="fa-regular fa-bell"></i>
            <a href=""><img src="../Media/pantai-indah-kapuk-dua-tbk--600.png" alt="Profile"></a>
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
                        <a href="ticket.php?id=<?= $ev['id_attendee'] ?>">
                            <div class="card-top-header">
                                <div class="header-left"><h4><?= getFullDate($ev['start_date']) ?></h4></div>
                                <div class="header-right"><h4><?= getTimeFormat($ev['start_time']) ?></h4></div>
                            </div>
                            <hr class="card-divider">
                            
                            <div class="card-hero-img">
                                <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                <img src="<?= $img_path ?>" alt="Event Banner">
                            </div>
                            <h2 class="card-title"><?= htmlspecialchars($ev['title']) ?></h2>
                            
                            <div class="card-author">
                                <img src="../Media/SVG.png" alt="Author Logo">
                                <span>SecureGate Event</span>
                            </div>
                            <div class="card-info-blocks">
                                <div class="info-block">
                                    <i class="block-icon fas fa-map-marker-alt"></i>
                                    <div class="block-text">
                                        <h3><?= $ev['location_type'] == 'online' ? 'Online' : 'Offline Venue' ?></h3>
                                        <p><?= htmlspecialchars($ev['location_details']) ?></p>
                                    </div>
                                </div>
                                <div class="price-mini-block">
                                    <i class="fas fa-tag"></i>
                                    <span style="<?= ($ev['ticket_status'] == 'need_approval' || $ev['ticket_status'] == 'awaiting_payment') ? 'color:#f97316;' : 'color:#22c55e;' ?>">
                                        <?= ($ev['ticket_status'] == 'need_approval' || $ev['ticket_status'] == 'awaiting_payment') ? 'Pending' : 'Going' ?>
                                    </span>
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
                            <div class="past-event-card">
                                <div class="left-past-card">
                                    <div class="time-event">
                                        <span class="begin-time"><?= getTimeFormat($ev['start_time']) ?></span>
                                        <span>·</span>
                                        <span><?= $ev['location_type'] == 'online' ? 'Online' : 'Offline' ?></span>
                                    </div>
                                    <h2><?= htmlspecialchars($ev['title']) ?></h2>
                                    <div class="author-past">
                                        <img src="../Media/pantai-indah-kapuk-dua-tbk--600.png" alt="author-photo">
                                        <p>By</p>
                                        <p>SecureGate</p>
                                    </div>
                                    <div class="past-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <p><?= htmlspecialchars($ev['location_details']) ?></p>
                                    </div>
                                    <p class="status-past going" style="background: #333; color: #888; border-color: #444;">Ended</p>
                                </div>
                                <div class="right-past-card">
                                    <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                    <img src="<?= $img_path ?>" alt="pamflet event">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
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