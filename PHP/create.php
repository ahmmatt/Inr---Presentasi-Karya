<?php
session_start(); // WAJIB ADA UNTUK MENGAMBIL DATA ADMIN

// CEK KEAMANAN SUPER KETAT: Harus login DAN rolenya harus admin/superadmin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: admin_signin.php");
    exit();
}

// 1. Hubungkan ke database
require_once 'config/connect.php'; 

// 2. Jika Formulir Dikirim (Tombol Submit Ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // TANGKAP ID ADMIN YANG SEDANG LOGIN
    $admin_id = $_SESSION['user_id'];
    
    // --- AMBIL DATA DASAR EVENT ---
    $title = $conn->real_escape_string($_POST['event_name']);
    $category = $conn->real_escape_string($_POST['event_category']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_date = $_POST['end_date'];
    $end_time = $_POST['end_time'];
    $timezone = $conn->real_escape_string($_POST['timezone']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Cek toggle Require Approval (true = 1, false = 0)
    $require_approval = (isset($_POST['require_approval']) && $_POST['require_approval'] == 'true') ? 1 : 0;
    
    // --- AMBIL DATA LOKASI (BARU) ---
    $location_type = 'offline';
    $location_details = '';
    $venue_name = "NULL";
    $city = "NULL";
    $maps_link = "NULL";

    if (!empty($_POST['online_link'])) {
        // Jika link online diisi, otomatis menjadi event online
        $location_type = 'online';
        $location_details = $conn->real_escape_string($_POST['online_link']);
    } else {
        // Jika tidak, berarti event offline
        $location_type = 'offline';
        $location_details = $conn->real_escape_string($_POST['offline_location']);
        
        $v_name = $conn->real_escape_string($_POST['venue_name']);
        $venue_name = !empty($v_name) ? "'$v_name'" : "NULL";
        
        $c_name = $conn->real_escape_string($_POST['city']);
        $city = !empty($c_name) ? "'$c_name'" : "NULL";
        
        $m_link = $conn->real_escape_string($_POST['maps_link']);
        $maps_link = !empty($m_link) ? "'$m_link'" : "NULL";
    }

    // --- AMBIL DATA KAPASITAS & KURSI ---
    $capacity_type = strtolower($conn->real_escape_string($_POST['cap_type']));
    $max_capacity = "NULL"; 
    if ($capacity_type === 'limited' && !empty($_POST['cap_amount'])) {
        $max_capacity = (int)$_POST['cap_amount'];
    }
    $seat_assignment = strtolower($conn->real_escape_string($_POST['seat_type'] ?? 'bebas'));

    // --- PROSES UPLOAD FILE (BANNER & 3D SPACE) ---
    $target_dir = "../Media/uploads/"; 
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $banner_image = "";
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $ext = pathinfo($_FILES["banner_image"]["name"], PATHINFO_EXTENSION);
        $banner_image = uniqid("banner_") . "." . $ext;
        move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_dir . $banner_image);
    }

    $space_3d_file = "NULL";
    if (isset($_FILES['space_3d']) && $_FILES['space_3d']['error'] == 0) {
        $ext = pathinfo($_FILES["space_3d"]["name"], PATHINFO_EXTENSION);
        $fileName = uniqid("3d_") . "." . $ext;
        if (move_uploaded_file($_FILES["space_3d"]["tmp_name"], $target_dir . $fileName)) {
            $space_3d_file = "'$fileName'";
        }
    }

    // --- 3. EKSEKUSI QUERY KE TABEL `events` ---
    $sql_event = "INSERT INTO events (
        id_admin, title, banner_image, category, location_type, venue_name, city, maps_link, location_details, space_3d_file,
        start_date, start_time, end_date, end_time, timezone, description,
        require_approval, capacity_type, max_capacity, seat_assignment
    ) VALUES (
        '$admin_id', '$title', '$banner_image', '$category', '$location_type', $venue_name, $city, $maps_link, '$location_details', $space_3d_file,
        '$start_date', '$start_time', '$end_date', '$end_time', '$timezone', '$description',
        '$require_approval', '$capacity_type', $max_capacity, '$seat_assignment'
    )";

    if ($conn->query($sql_event) === TRUE) {
        $new_event_id = $conn->insert_id; 

        // --- 4. EKSEKUSI QUERY TIKET KE TABEL `ticket_tiers` ---
        $ticket_type = $_POST['ticket_type'];
        if ($ticket_type === 'Free') {
            $conn->query("INSERT INTO ticket_tiers (id_event, tier_name, price) VALUES ('$new_event_id', 'Free Ticket', 0)");
        } else {
            if (isset($_POST['tier_name']) && isset($_POST['tier_price'])) {
                for ($i = 0; $i < count($_POST['tier_name']); $i++) {
                    $t_name = $conn->real_escape_string($_POST['tier_name'][$i]);
                    $t_price = (float)$_POST['tier_price'][$i];
                    if (!empty($t_name)) {
                        $conn->query("INSERT INTO ticket_tiers (id_event, tier_name, price) VALUES ('$new_event_id', '$t_name', '$t_price')");
                    }
                }
            }
        }

        // --- 5. EKSEKUSI QUERY PERTANYAAN TAMBAHAN KE `custom_questions` ---
        if (isset($_POST['custom_questions'])) {
            foreach ($_POST['custom_questions'] as $q) {
                $q_text = $conn->real_escape_string($q);
                if (!empty($q_text)) {
                    $conn->query("INSERT INTO custom_questions (id_event, question_text) VALUES ('$new_event_id', '$q_text')");
                }
            }
        }

        echo "<script>alert('Event successfully created!'); window.location.href='adminevent.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error saving event: " . $conn->error . "');</script>";
    }
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
    <title>Create Event - SecureGate</title>
    <link rel="stylesheet" href="../CSS/create.css">
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
        <form action="create.php" method="POST" enctype="multipart/form-data" id="create-event-form">
            
            <div class="create-wrapper-layout">
                
                <div class="left-layout">
                    <div class="add-pict-card">
                        <input type="file" name="banner_image" id="banner_image" accept="image/*" required>
                        
                        <div class="upload-placeholder">
                            <i class="fa-regular fa-image"></i>
                            <p>Masukkan gambar ukuran 1:1</p> </div>

                        <div class="pict-action-select"><i class="fa-solid fa-image"></i></div>
                        
                        <img id="image-preview" class="image-preview">
                    </div>
                    
                    <div class="add-loc-wrapper">
                        <div class="add-loc-card" id="btn-toggle-loc">
                            <div class="left-loc-card"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="right-loc-card">
                                <h3>Add Event Location</h3>
                                <p>Offline location or virtual link</p>
                            </div>
                            <i class="fa-solid fa-chevron-down loc-arrow"></i>
                        </div>
                        
                        <div class="loc-options" id="loc-expand-area">
                            
                            <h4 class="loc-section-title">Offline Event</h4>
                            
                            <div class="virtual-link-input mb-8">
                                <i class="fa-solid fa-building"></i>
                                <input type="text" name="venue_name" placeholder="Venue Name (e.g., IndigoHub Makassar)...">
                            </div>
                            
                            <div class="virtual-link-input mb-8">
                                <i class="fa-solid fa-city"></i>
                                <select name="city" class="city-select">
                                    <option value="">Select City...</option>
                                    <option value="Jakarta">Jakarta</option>
                                    <option value="Bali">Bali</option>
                                    <option value="Bandung">Bandung</option>
                                    <option value="Surabaya">Surabaya</option>
                                    <option value="Yogyakarta">Yogyakarta</option>
                                    <option value="Makassar">Makassar</option>
                                    <option value="Medan">Medan</option>
                                    <option value="Semarang">Semarang</option>
                                </select>
                            </div>

                            <div class="virtual-link-input mb-8">
                                <i class="fa-solid fa-map"></i>
                                <input type="text" name="offline_location" placeholder="Full Address Details...">
                            </div>

                            <div class="virtual-link-input">
                                <i class="fa-solid fa-map-location-dot"></i>
                                <input type="url" name="maps_link" placeholder="Paste Google Maps Embed Link (src url)...">
                            </div>
                            
                            <div class="loc-divider"><span>OR VIRTUAL</span></div>
                            
                            <h4 class="loc-section-title">Online Event</h4>
                            <div class="virtual-link-input">
                                <i class="fa-solid fa-link"></i>
                                <input type="url" name="online_link" placeholder="Paste virtual link (Zoom, Meet, dll)...">
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="left-section-title">Optional</h4>
                    <div class="space-3d-card">
                        <input type="file" name="space_3d" accept="video/*, .glb, .gltf">
                        <div class="space-3d-info">
                            <i class="fa-solid fa-vr-cardboard"></i> 
                            <div class="space-3d-text">
                                <h3>Add 3D Event Space</h3>
                                <p>Upload video preview or 3D space</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-upload upload-icon"></i>
                    </div>
                </div>

                <div class="right-layout">
                    <div class="event-name">
                        <input type="text" name="event_name" placeholder="Your Event Name" required>
                    </div>
                    
                    <div class="time-loc-wrapper">
                        <div class="schedule-wrapper card-box">
                            <div class="start-end-wrapper">
                                <div class="start-text"><i class="fa-solid fa-circle dot-icon"></i><h4>Start</h4></div>
                                <div class="line-of-circle"></div>
                                <div class="end-text"><i class="fa-regular fa-circle dot-icon"></i><h4>End</h4></div>
                            </div>
                            <div class="start-date-time-card-left"><input type="date" name="start_date" required></div>
                            <div class="start-date-time-card-right"><input type="time" name="start_time" required></div>
                            <div class="end-date-time-card-left"><input type="date" name="end_date" required></div>
                            <div class="end-date-time-card-right"><input type="time" name="end_time" required></div>
                        </div>
                        
                        <div class="time-area card-box">
                            <i class="fa-solid fa-globe"></i>
                            <h4>GMT+08:00</h4>
                            <p>Makassar</p>
                            <input type="hidden" name="timezone" value="GMT+08:00">
                        </div>
                    </div>
                    
                    <div class="desc-wrapper card-box">
                        <i class="fa-solid fa-align-left"></i> 
                        <input type="text" name="description" placeholder="Add Description">
                    </div>
                    
                    <h4>Event Options</h4>
                    <div class="event-options-wrapper card-box">
                        
                        <div class="category-wrapper wrapper-relative">
                            <div class="category-wrapper-left options-left-label">
                                <i class="fa-solid fa-layer-group"></i> 
                                <h3>Event Category</h3>
                            </div>
                            <div class="category-wrapper-right options-right-trigger" id="category-trigger">
                                <input type="text" id="category-display" class="right-display-input input-140" value="Select Category" readonly>
                                <i class="fa-solid fa-pen edit-pen-icon"></i>
                            </div>

                            <div class="ticket-dropdown" id="category-dropdown">
                                <i class="fa-solid fa-xmark close-modal" id="close-category-modal"></i>
                                <div class="modal-header-intro">
                                    <div class="modal-header-icon"><i class="fa-solid fa-layer-group"></i></div>
                                    <h2>Event Category</h2>
                                    <p>Help attendees discover your event by assigning it to a category.</p>
                                </div>
                                <div class="input-group">
                                    <label>Select Category:</label>
                                    <select id="category_select" class="custom-select">
                                        <option value="">Choose a category...</option>
                                        <option value="Music Concert">Music Concert</option>
                                        <option value="Tech Seminar">Tech Seminar</option>
                                        <option value="Workshop & Training">Workshop & Training</option>
                                        <option value="Other">Other (Type your own)</option>
                                    </select>
                                    <input type="text" id="category_other_input" class="custom-input category-other-input" placeholder="Type your category name...">
                                </div>
                                <input type="hidden" name="event_category" id="real_category_input">
                                
                                <div class="ticket-actions mt-24">
                                    <button type="button" class="apply-btn full-width" id="apply-category">Apply</button>
                                </div>
                            </div>
                        </div>
                        <hr>

                        <div class="ticket-wrapper wrapper-relative">
                            <div class="ticket-wrapper-left options-left-label">
                                <i class="fa-solid fa-ticket"></i> 
                                <h3>Ticket Price</h3>
                            </div>
                            <div class="ticket-wrapper-right options-right-trigger" id="ticket-trigger">
                                <input type="text" id="ticket-display" class="right-display-input input-140" value="Free" readonly>
                                <i class="fa-solid fa-pen edit-pen-icon"></i>
                            </div>

                            <div class="ticket-dropdown" id="ticket-dropdown">
                                <i class="fa-solid fa-xmark close-modal" id="close-ticket-modal"></i>
                                <div class="modal-header-intro">
                                    <div class="modal-header-icon"><i class="fa-solid fa-ticket"></i></div>
                                    <h2>Ticket Price</h2>
                                    <p>Set the cost of your event.</p>
                                </div>
                                <div class="ticket-type-selector">
                                    <label class="cap-radio">
                                        <input type="radio" name="ticket_type" value="Free" checked>
                                        <span>Free</span>
                                    </label>
                                    <label class="cap-radio">
                                        <input type="radio" name="ticket_type" value="Paid">
                                        <span>Paid</span>
                                    </label>
                                </div>
                                
                                <div class="payment-details hidden-by-default" id="payment-details-container">
                                    <div class="ticket-tiers-wrapper">
                                        <label class="section-sub-label">Ticket Categories & Prices:</label>
                                        <div id="ticket-tiers-container">
                                            <div class="ticket-tier-row">
                                                <input type="text" name="tier_name[]" class="tier-name custom-input readonly-input" value="Regular" readonly>
                                                <input type="number" name="tier_price[]" class="tier-price custom-input" placeholder="Price (Rp)">
                                            </div>
                                        </div>
                                        <button type="button" id="add-tier-btn" class="add-tier-btn"><i class="fa-solid fa-plus"></i> Add VIP Tier</button>
                                    </div>
                                </div>

                                <div class="ticket-actions">
                                    <button type="button" class="apply-btn full-width" id="apply-ticket">Apply</button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="approval-wrapper wrapper-relative">
                            <div class="approval-wrapper-left options-left-label">
                                <i class="fa-solid fa-user-check"></i>
                                <h3>Require Approval</h3>
                            </div>
                            <div class="approval-wrapper-right">
                                <input type="hidden" name="require_approval" id="require-approval-val" value="false">
                                <button type="button" class="switch-btn" id="approval-switch"></button>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="capacity-wrapper wrapper-relative">
                            <div class="capacity-wrapper-left options-left-label">
                                <i class="fa-solid fa-users"></i> 
                                <h3>Capacity</h3>
                            </div>
                            <div class="capacity-wrapper-right options-right-trigger" id="capacity-trigger">
                                <input type="text" id="capacity-display" class="right-display-input input-160" value="Unlimited" readonly>
                                <i class="fa-solid fa-pen edit-pen-icon"></i>
                            </div>

                            <div class="capacity-dropdown" id="capacity-dropdown">
                                <i class="fa-solid fa-xmark close-modal" id="close-capacity-modal"></i>
                                <div class="modal-header-intro">
                                    <div class="modal-header-icon"><i class="fa-solid fa-users"></i></div>
                                    <h2>Max Capacity</h2>
                                    <p>Limit the number of attendees for your event.</p>
                                </div>
                                <div class="capacity-type-selector">
                                    <label class="cap-radio"><input type="radio" name="cap_type" value="Unlimited" checked><span>Unlimited / Open Seating</span></label>
                                    <label class="cap-radio"><input type="radio" name="cap_type" value="Limited"><span>Limited / Assigned Seating</span></label>
                                </div>
                                
                                <div class="capacity-number-input hidden-by-default" id="capacity-number-container">
                                    <label>Capacity Limit:</label>
                                    <input type="number" name="cap_amount" id="cap_amount" min="1" placeholder="e.g., 100" class="custom-input">
                                    
                                    <label class="mt-8-label">Seat Assignment:</label>
                                    <div class="seat-type-options">
                                        <label class="cap-radio"><input type="radio" name="seat_type" value="Bebas" checked><span class="radio-subtext">General Admission</span></label>
                                        <label class="cap-radio"><input type="radio" name="seat_type" value="Pilih"><span class="radio-subtext">Select Seat Number</span></label>
                                    </div>
                                </div>
                                <button type="button" class="apply-btn full-width mt-16" id="apply-capacity">Apply</button>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="question-wrapper wrapper-relative">
                            <div class="question-wrapper-left options-left-label">
                                <i class="fa-solid fa-clipboard-list"></i> 
                                <h3 style="margin: 0; font-size: 14px; font-weight: 500; color: #e0e0e0;">Registration Form</h3>
                            </div>
                            <div class="question-wrapper-right options-right-trigger" id="question-trigger">
                                <input type="text" id="question-display" class="right-display-input input-170" value="Profile Info Only" readonly>
                                <i class="fa-solid fa-pen edit-pen-icon"></i>
                            </div>

                            <div class="ticket-dropdown" id="question-dropdown"> 
                                <i class="fa-solid fa-xmark close-modal" id="close-question-modal"></i>
                                <div class="modal-header-intro">
                                    <div class="modal-header-icon"><i class="fa-solid fa-clipboard-question"></i></div>
                                    <h2>Registration Form</h2>
                                    <p>Ask custom questions to your attendees.</p>
                                </div>

                                <div id="questions-container" class="questions-container-box">
                                    <label class="questions-label">Custom Questions:</label>
                                </div>
                                
                                <button type="button" id="add-question-btn" class="add-tier-btn mt-10"><i class="fa-solid fa-plus"></i> Add Question</button>
                                <button type="button" class="apply-btn full-width mt-16" id="apply-question">Apply</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="submit-wrapper">
                        <button type="submit">Create Event</button>
                    </div>
                </div>
            </div>
        </form>

        <hr>
        <div class="page-footer">
            <div class="left-footer"><a href="">Discover</a><a href="">Help</a></div>
            <div class="right-footer"><a href=""><i class="fab fa-x"></i></a><a href=""><i class="fab fa-youtube"></i></a><a href=""><i class="fab fa-instagram"></i></a></div>
        </div>
    </div>
    
    <script src="../JS/create.js"></script>
</body>
</html>