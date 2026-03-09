<?php
session_start();
require_once 'config/connect.php'; 

// CEK LOGIN KHUSUS USER
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: signin.php"); // Arahkan ke halaman login user
    exit();
}

// 1. Tangkap Filter dari URL atau Cookie (Auto-Location)
if (isset($_GET['city'])) {
    $selected_city = $_GET['city'];
    // Simpan pilihan kota ke cookie (berlaku 30 hari) agar sistem ingat
    setcookie("user_city", $selected_city, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['user_city'])) {
    // Jika tidak ada di URL, ambil dari memori Cookie (hasil deteksi JS)
    $selected_city = $_COOKIE['user_city'];
} else {
    // Default pertama kali buka web
    $selected_city = 'All';
}

$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';

// 2. Susun Query Pencarian Berdasarkan Filter
$where_clauses = ["e.status = 'active'"];

if ($selected_category !== 'All') {
    $cat_esc = $conn->real_escape_string($selected_category);
    $where_clauses[] = "e.category = '$cat_esc'";
}

if ($selected_city !== 'All') {
    $city_esc = $conn->real_escape_string($selected_city);
    // PERBAIKAN PENTING: Sekarang kita filter menggunakan kolom 'city' yang baru
    $where_clauses[] = "e.city = '$city_esc'";
}

$where_sql = implode(' AND ', $where_clauses);

// 3. Ambil Data Event Berdasarkan Filter
$sql_events = "SELECT e.*, 
              u.full_name AS author_name, 
              u.profile_picture AS author_image,
              (SELECT MIN(price) FROM ticket_tiers t WHERE t.id_event = e.id_event) as min_price,
              (SELECT COUNT(*) FROM ticket_tiers t WHERE t.id_event = e.id_event AND price = 0) as has_free
              FROM events e 
              LEFT JOIN users u ON e.id_admin = u.id_user 
              WHERE $where_sql ORDER BY e.created_at DESC LIMIT 10";

$result_events = $conn->query($sql_events);
$events = [];
if ($result_events && $result_events->num_rows > 0) {
    while($row = $result_events->fetch_assoc()) {
        $events[] = $row;
    }
}

// 4. Hitung Jumlah Event per Kategori (Harus sama persis dengan opsi di create.php)
$cat_counts = [];
$res_cats = $conn->query("SELECT category, COUNT(*) as cnt FROM events WHERE status='active' GROUP BY category");
if ($res_cats) {
    while($row = $res_cats->fetch_assoc()) {
        $cat_counts[$row['category']] = $row['cnt'];
    }
}
function getCatCount($catName, $counts) { return isset($counts[$catName]) ? $counts[$catName] : 0; }

// 5. Hitung Jumlah Event per Kota
$city_counts = [];
$cities_list = ['Jakarta', 'Bali', 'Bandung', 'Surabaya', 'Yogyakarta', 'Makassar', 'Medan', 'Semarang'];
foreach($cities_list as $c) {
    // PERBAIKAN PENTING: Sekarang kita menghitung dari kolom 'city'
    $res = $conn->query("SELECT COUNT(*) as cnt FROM events WHERE status='active' AND city = '$c'");
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
            
            <a href="discover.php?category=<?= urlencode('Music Concert') ?>&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card <?= $selected_category == 'Music Concert' ? 'active-filter' : '' ?>">
                <div class="category-card-content">
                    <div class="icon-box icon-yellow"><i class="fa-solid fa-music"></i></div>
                    <div class="category-card-info">
                        <h3>Konser</h3>
                        <div class="number-of-event">
                            <p><?= getCatCount('Music Concert', $cat_counts) ?></p>
                            <p>Events</p>
                        </div>
                    </div>
                </div>
            </a>

            <a href="discover.php?category=<?= urlencode('Workshop & Training') ?>&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card <?= $selected_category == 'Workshop & Training' ? 'active-filter' : '' ?>">
                <div class="category-card-content">
                    <div class="icon-box icon-green"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div class="category-card-info">
                        <h3>Workshop</h3>
                        <div class="number-of-event">
                            <p><?= getCatCount('Workshop & Training', $cat_counts) ?></p>
                            <p>Events</p>
                        </div>
                    </div>
                </div>
            </a>  

            <a href="discover.php?category=<?= urlencode('Tech Seminar') ?>&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card <?= $selected_category == 'Tech Seminar' ? 'active-filter' : '' ?>">
                <div class="category-card-content">
                    <div class="icon-box icon-orange"><i class="fa-solid fa-microphone-lines"></i></div>
                    <div class="category-card-info">
                        <h3>Seminar</h3>
                        <div class="number-of-event">
                            <p><?= getCatCount('Tech Seminar', $cat_counts) ?></p>
                            <p>Events</p>
                        </div>
                    </div>
                </div>
            </a>

            <a href="discover.php?category=All&city=<?= urlencode($selected_city) ?>" class="filter-card-link category-card <?= $selected_category == 'All' ? 'active-filter' : '' ?>">
                <div class="category-card-content">
                    <div class="icon-box icon-purple"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="category-card-info">
                        <h3>All Events</h3>
                        <div class="number-of-event">
                            <p><?php echo array_sum($cat_counts); ?></p>
                            <p>Events</p>
                        </div>
                    </div>
                </div>
            </a>

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
                                
                                <div class="card-author-wrapper" style="margin-bottom: 12px;">
                                    <div class="card-author-left" style="display: flex; align-items: center; gap: 8px;">
                                        <?php 
                                            // 1. Ambil Nama (Jika kosong di DB, tampilkan "Unknown Admin")
                                            $author_name = !empty($ev['author_name']) ? htmlspecialchars($ev['author_name']) : "Unknown Admin";
                                            
                                            // 2. Ambil Huruf Pertama untuk Avatar Default
                                            $initial = strtoupper(substr($author_name, 0, 1));
                                            
                                            // 3. Tampilkan Foto atau Avatar Inisial Huruf
                                            if(!empty($ev['author_image'])): 
                                        ?>
                                            <img src="../Media/uploads/<?= htmlspecialchars($ev['author_image']) ?>" alt="Author Logo" style="border-radius: 50%; object-fit: cover; width: 24px; height: 24px; background: #fff;">
                                        <?php else: ?>
                                            <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #f97316; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0;">
                                                <?= $initial ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span style="font-size: 13px; color: #a0a0a0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $author_name ?></span>
                                    </div>
                                </div>                 

                                <div class="card-info-blocks" style="align-items: flex-start; margin-top: 0;">
                                    <div class="info-block" style="align-items: flex-start; flex: 1; padding-right: 12px;">
                                        <div class="block-icon" style="margin-top: 2px;"><i class="fas fa-map-marker-alt"></i></div>
                                        <div class="block-text" style="flex: 1; width: 100%;">
                                            <h3 style="font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 4px; line-height: 1.2;">
                                                <?= $ev['location_type'] == 'online' ? 'Online' : (!empty($ev['venue_name']) ? htmlspecialchars($ev['venue_name']) : 'Offline') ?>
                                            </h3>
                                            
                                            <p style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; white-space: normal; overflow-wrap: anywhere; line-height: 1.4; color: #a0a0a0; font-size: 12px; margin: 0;">
                                                <?php 
                                                    if($ev['location_type'] == 'online') {
                                                        echo htmlspecialchars($ev['location_details']);
                                                    } else {
                                                        $city_text = !empty($ev['city']) ? htmlspecialchars($ev['city']) . ', ' : '';
                                                        echo $city_text . htmlspecialchars($ev['location_details']);
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="price-mini-block" style="margin-top: 2px;">
                                        <i class="fas fa-tag"></i>
                                        <span style="color: #22c55e; font-weight: 600;"><?= getPriceLabel($ev) ?></span>
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