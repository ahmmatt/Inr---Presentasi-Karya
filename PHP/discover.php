<?php
session_start();
require_once '../PHP/config/connect.php'; 

// 1. Tangkap Filter dari URL (Default: Kota Makassar, Kategori All)
$selected_city = isset($_GET['city']) ? $_GET['city'] : 'Makassar';
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';

// 2. Susun Query Pencarian Berdasarkan Filter
$where_clauses = ["e.status = 'active'"];

if ($selected_category !== 'All') {
    $cat_esc = $conn->real_escape_string($selected_category);
    $where_clauses[] = "e.category = '$cat_esc'";
}

if ($selected_city !== 'All') {
    $city_esc = $conn->real_escape_string($selected_city);
    // Mencari kata nama kota di dalam kolom location_details
    $where_clauses[] = "e.location_details LIKE '%$city_esc%'";
}

$where_sql = implode(' AND ', $where_clauses);

// 3. Ambil Data Event Berdasarkan Filter
$sql_events = "SELECT e.*, 
              (SELECT MIN(price) FROM ticket_tiers t WHERE t.id_event = e.id_event) as min_price,
              (SELECT COUNT(*) FROM ticket_tiers t WHERE t.id_event = e.id_event AND price = 0) as has_free
              FROM events e WHERE $where_sql ORDER BY e.created_at DESC LIMIT 10";

$result_events = $conn->query($sql_events);
$events = [];
if ($result_events && $result_events->num_rows > 0) {
    while($row = $result_events->fetch_assoc()) {
        $events[] = $row;
    }
}

// 4. Hitung Jumlah Event per Kategori (Dinamis dari Database)
$cat_counts = [];
$res_cats = $conn->query("SELECT category, COUNT(*) as cnt FROM events WHERE status='active' GROUP BY category");
if ($res_cats) {
    while($row = $res_cats->fetch_assoc()) {
        $cat_counts[$row['category']] = $row['cnt'];
    }
}
function getCatCount($catName, $counts) { return isset($counts[$catName]) ? $counts[$catName] : 0; }

// 5. Hitung Jumlah Event per Kota (Dinamis dari Database)
$city_counts = [];
$cities_list = ['Jakarta', 'Bali', 'Bandung', 'Surabaya', 'Yogyakarta', 'Makassar', 'Medan', 'Semarang'];
foreach($cities_list as $c) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE status='active' AND location_details LIKE '%$c%'");
    $city_counts[$c] = $res ? $res->fetch_assoc()['cnt'] : 0;
}

// Format Fungsi
function formatDate($dateStr) { return (new DateTime($dateStr))->format('M j, l'); }
function formatTime($timeStr) { return (new DateTime($timeStr))->format('g:i A'); }
function formatRupiah($num) { return "Rp " . number_format($num, 0, ',', '.'); }

// Label Harga
function getPriceLabel($ev) {
    if ($ev['has_free'] > 0 || $ev['min_price'] == 0) return 'Free';
    return formatRupiah($ev['min_price']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureGate - Discover</title>
    <link rel="stylesheet" href="../CSS/discover.css">
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
            <h1>Discover Event</h1>
            <p>Find whats happening nearby, pick your favorite category, or search instantly.</p>
        </div>
        
        <div class="search-bar-wrapper">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <form action="discover.php" method="GET">
                    <input type="hidden" name="city" value="<?= htmlspecialchars($selected_city) ?>">
                    <input type="text" name="search" placeholder="Search..">
                </form>
            </div>
        </div>
        
        <div class="event-location-wrapper">
            <div class="event-location-now" onclick="window.location.href='discover.php?city=All&category=<?= urlencode($selected_category) ?>'">
                <i class="fa-solid fa-location-dot location-icon" style="color: #22c55e;"></i>
                <h3><?= $selected_city === 'All' ? 'All Locations' : htmlspecialchars($selected_city) . ', ID' ?></h3>               
                <i class="fa-solid fa-chevron-down location-arrow"></i>
            </div>
        </div>
        
        <h3>Browse by Category</h3>
        <div class="category-wrapper">
            <a href="discover.php?category=Music Concert&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card"><div class="category-card-content"><div class="icon-box icon-yellow"><i class="fa-solid fa-music"></i></div><div class="category-card-info"><h3>Konser</h3><div class="number-of-event"><p><?= getCatCount('Music Concert', $cat_counts) ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?category=Religious / Kajian&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card"><div class="category-card-content"><div class="icon-box icon-green"><i class="fa-solid fa-book-quran"></i></div><div class="category-card-info"><h3>Ceramah & Kajian</h3><div class="number-of-event"><p><?= getCatCount('Religious / Kajian', $cat_counts) ?></p><p>Events</p></div></div></div></a>  
            
            <a href="discover.php?category=Tech Seminar&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card"><div class="category-card-content"><div class="icon-box icon-orange"><i class="fa-solid fa-microphone-lines"></i></div><div class="category-card-info"><h3>Seminar</h3><div class="number-of-event"><p><?= getCatCount('Tech Seminar', $cat_counts) ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?category=Festival / Exhibition&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card"><div class="category-card-content"><div class="icon-box icon-purple"><i class="fa-solid fa-ribbon"></i></div><div class="category-card-info"><h3>Festival & Pameran</h3><div class="number-of-event"><p><?= getCatCount('Festival / Exhibition', $cat_counts) ?></p><p>Events</p></div></div></div></a>
        </div>
        
        <hr>
        
        <h3>Recently Added</h3>
        <div class="carousel-container">
            <button class="carousel-btn left-btn" id="slide-left"><i class="fa-solid fa-chevron-left"></i></button>
            
            <div class="upcoming-event">
                
                <?php if(count($events) > 0): ?>
                    <?php foreach($events as $ev): ?>
                        <div class="event-card-detail">
                            <a href="ticket.php?id=<?= $ev['id_event'] ?>" class="card-link-wrapper">
                                <div class="card-top-header">
                                    <div class="header-left"><h4><?= formatDate($ev['start_date']) ?></h4></div>
                                    <div class="header-right"><h4><?= formatTime($ev['start_time']) ?></h4></div>
                                </div>
                                <hr class="card-divider">
                                
                                <h2 class="card-title"><?= htmlspecialchars($ev['title']) ?></h2>
                                <div class="event-card-img">
                                    <?php $img_path = !empty($ev['banner_image']) ? "../Media/uploads/" . htmlspecialchars($ev['banner_image']) : "../Media/09071799-7231-4faa-883e-a1eb2d01ef9b.avif"; ?>
                                    <img src="<?= $img_path ?>" alt="">
                                </div>
                                
                                <div class="card-author-wrapper">
                                    <div class="card-author-left">
                                        <img src="../Media/SVG.png" alt="Author Logo">
                                        <span>SecureGate Event</span>
                                    </div>
                                </div>                   
                                <div class="card-info-blocks">
                                    <div class="info-block">
                                        <div class="block-icon"><i class="fas fa-map-marker-alt"></i></div>
                                        <div class="block-text">
                                            <h3><?= $ev['location_type'] == 'online' ? 'Online' : 'Offline' ?></h3>
                                            <p><?= htmlspecialchars($ev['location_details']) ?></p>
                                        </div>
                                    </div>
                                    <div class="price-mini-block">
                                        <i class="fas fa-tag"></i>
                                        <span><?= getPriceLabel($ev) ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <h3>No Events Available</h3>
                        <p>There are no upcoming events at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>

            </div>
            
            <button class="carousel-btn right-btn" id="slide-right"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
        
        <hr>
        
        <h3>Explore Your City</h3>
        <div class="city-wrapper">
            <a href="discover.php?city=Jakarta&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-orange"><i class="fa-solid fa-monument"></i></div><div class="city-card-info"><h3>Jakarta</h3><div class="value-city-event"><p><?= $city_counts['Jakarta'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Bali&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-cyan"><i class="fa-solid fa-gopuram"></i></div><div class="city-card-info"><h3>Bali</h3><div class="value-city-event"><p><?= $city_counts['Bali'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Bandung&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-green"><i class="fa-solid fa-mountain"></i></div><div class="city-card-info"><h3>Bandung</h3><div class="value-city-event"><p><?= $city_counts['Bandung'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Surabaya&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-red"><i class="fa-solid fa-city"></i></div><div class="city-card-info"><h3>Surabaya</h3><div class="value-city-event"><p><?= $city_counts['Surabaya'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Yogyakarta&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-yellow"><i class="fa-solid fa-landmark"></i></div><div class="city-card-info"><h3>Yogyakarta</h3><div class="value-city-event"><p><?= $city_counts['Yogyakarta'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Makassar&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-blue"><i class="fa-solid fa-anchor"></i></div><div class="city-card-info"><h3>Makassar</h3><div class="value-city-event"><p><?= $city_counts['Makassar'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Medan&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-purple"><i class="fa-solid fa-map-location-dot"></i></div><div class="city-card-info"><h3>Medan</h3><div class="value-city-event"><p><?= $city_counts['Medan'] ?></p><p>Events</p></div></div></div></a>
            
            <a href="discover.php?city=Semarang&category=<?= urlencode($selected_category) ?>" class="filter-card-link"><div class="city-card"><div class="city-card-logo logo-pink"><i class="fa-solid fa-train-subway"></i></div><div class="city-card-info"><h3>Semarang</h3><div class="value-city-event"><p><?= $city_counts['Semarang'] ?></p><p>Events</p></div></div></div></a>
        </div>
        
        <hr>
        
        <div class="page-footer">
            <div class="left-footer"><a href="">Discover</a><a href="">Help</a></div>
            <div class="right-footer"><a href=""><i class="fab fa-x"></i></a><a href=""><i class="fab fa-youtube"></i></a><a href=""><i class="fab fa-instagram"></i></a></div>
        </div>
    </div>
    
    <script src="../JS/discover.js"></script>
</body>
</html>