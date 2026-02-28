<?php
// 1. Hubungkan ke database (Sesuaikan path file db_connect.php Anda)
require_once '../PHP/config/connect.php'; 

// 2. Jika Formulir Dikirim (Tombol Submit Ditekan)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
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
    
    // --- AMBIL DATA LOKASI ---
    $location_type = 'offline';
    $location_details = '';
    if (!empty($_POST['offline_location'])) {
        $location_details = $conn->real_escape_string($_POST['offline_location']);
    } else if (!empty($_POST['online_link'])) {
        $location_type = 'online';
        $location_details = $conn->real_escape_string($_POST['online_link']);
    }

    // --- AMBIL DATA KAPASITAS & KURSI ---
    // Ubah ke huruf kecil agar cocok dengan ENUM di database (unlimited/limited, bebas/pilih)
    $capacity_type = strtolower($conn->real_escape_string($_POST['cap_type']));
    $max_capacity = "NULL"; 
    if ($capacity_type === 'limited' && !empty($_POST['cap_amount'])) {
        $max_capacity = (int)$_POST['cap_amount'];
    }
    $seat_assignment = strtolower($conn->real_escape_string($_POST['seat_type'] ?? 'bebas'));

    // --- PROSES UPLOAD FILE (BANNER & 3D SPACE) ---
    $target_dir = "../Media/uploads/"; 
    // Buat folder otomatis jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Upload Banner
    $banner_image = "";
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $ext = pathinfo($_FILES["banner_image"]["name"], PATHINFO_EXTENSION);
        $banner_image = uniqid("banner_") . "." . $ext;
        move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_dir . $banner_image);
    }

    // Upload 3D Space (Opsional)
    $space_3d_file = "NULL";
    if (isset($_FILES['space_3d']) && $_FILES['space_3d']['error'] == 0) {
        $ext = pathinfo($_FILES["space_3d"]["name"], PATHINFO_EXTENSION);
        $fileName = uniqid("3d_") . "." . $ext;
        if (move_uploaded_file($_FILES["space_3d"]["tmp_name"], $target_dir . $fileName)) {
            $space_3d_file = "'$fileName'"; // Bungkus dengan tanda kutip untuk SQL
        }
    }

    // --- 3. EKSEKUSI QUERY KE TABEL `events` ---
    $sql_event = "INSERT INTO events (
        title, banner_image, category, location_type, location_details, space_3d_file,
        start_date, start_time, end_date, end_time, timezone, description,
        require_approval, capacity_type, max_capacity, seat_assignment
    ) VALUES (
        '$title', '$banner_image', '$category', '$location_type', '$location_details', $space_3d_file,
        '$start_date', '$start_time', '$end_date', '$end_time', '$timezone', '$description',
        '$require_approval', '$capacity_type', $max_capacity, '$seat_assignment'
    )";

    if ($conn->query($sql_event) === TRUE) {
        $new_event_id = $conn->insert_id; // Ambil ID event yang baru saja dibuat

        // --- 4. EKSEKUSI QUERY TIKET KE TABEL `ticket_tiers` ---
        $ticket_type = $_POST['ticket_type'];
        if ($ticket_type === 'Free') {
            $conn->query("INSERT INTO ticket_tiers (id_event, tier_name, price) VALUES ('$new_event_id', 'Free Ticket', 0)");
        } else {
            // Looping data tiket array (Reguler, VIP, dsb)
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

        // --- SUKSES! Arahkan ke halaman Admin ---
        echo "<script>alert('Event successfully created!'); window.location.href='adminevent.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error saving event: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - SecureGate</title>
    <link rel="stylesheet" href="../CSS/create.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <h1>SecureGate</h1>
        </div>
        <div class="main-nav">
            <div class="main-nav-discover"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
            <div class="main-nav-event"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
        </div>
        <div class="right-nav">
            <i class="fa-regular fa-bell"></i>
            <a href=""><img src="../Media/pantai-indah-kapuk-dua-tbk--600.png" alt="Profile"></a>
        </div>
    </nav>

    <div class="page-frame">
        <form action="create.php" method="POST" enctype="multipart/form-data" id="create-event-form">
            
            <div class="create-wrapper-layout">
                
                <div class="left-layout">
                    <div class="add-pict-card">
                        <input type="file" name="banner_image" id="banner_image" accept="image/*" required style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
                        <div class="pict-action-select">
                            <i class="fa-solid fa-image"></i> 
                        </div>
                        <img id="image-preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:16px; position:absolute; top:0; left:0; z-index:1;">
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
                            <div class="virtual-link-input">
                                <i class="fa-solid fa-map-location-dot"></i>
                                <input type="text" name="offline_location" placeholder="Paste Google Maps link or Address...">
                            </div>
                            <div class="loc-divider"><span>OR</span></div>
                            <div class="virtual-link-input">
                                <i class="fa-solid fa-link"></i>
                                <input type="url" name="online_link" placeholder="Paste virtual link (Zoom, Meet, dll)...">
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="left-section-title">Optional</h4>
                    <div class="space-3d-card">
                        <input type="file" name="space_3d" accept="video/*, .glb, .gltf" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
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
                        <input type="text" name="event_name" placeholder="Your Event Name" required style="width: 100%; background: transparent; border: none; outline: none; font-size: 44px; font-weight: 700; color: #fff;">
                    </div>
                    
                    <div class="time-loc-wrapper">
                        <div class="schedule-wrapper card-box">
                            <div class="start-end-wrapper">
                                <div class="start-text">
                                    <i class="fa-solid fa-circle" style="font-size: 8px;"></i><h4>Start</h4>
                                </div>
                                <div class="line-of-circle"></div>
                                <div class="end-text">
                                    <i class="fa-regular fa-circle" style="font-size: 8px;"></i><h4>End</h4>
                                </div>
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
                        <input type="text" name="description" placeholder="Add Description" style="width: 100%; background: transparent; border: none; outline: none; color: #fff; font-size: 14px;">
                    </div>
                    
                    <h4>Event Options</h4>
                    <div class="event-options-wrapper card-box">
                        
                        <div class="category-wrapper" style="position: relative;">
                            <div class="category-wrapper-left" style="display: flex; align-items: center; gap: 12px; color: #a0a0a0;">
                                <i class="fa-solid fa-layer-group"></i> 
                                <h3 style="margin: 0; font-size: 14px; font-weight: 500; color: #e0e0e0;">Event Category</h3>
                            </div>
                            <div class="category-wrapper-right" id="category-trigger" style="cursor: pointer; display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="category-display" value="Select Category" readonly style="cursor: pointer; background: transparent; border: none; outline: none; color: #a0a0a0; text-align: right; font-size: 14px; width: 140px; pointer-events: none;">
                                <i class="fa-solid fa-pen" style="font-size: 12px; color: #a0a0a0;"></i>
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
                                    <input type="text" id="category_other_input" class="custom-input" placeholder="Type your category name..." style="display: none; margin-top: 8px;">
                                </div>
                                <input type="hidden" name="event_category" id="real_category_input">
                                
                                <div class="ticket-actions" style="margin-top: 24px;">
                                    <button type="button" class="apply-btn" id="apply-category" style="width: 100%;">Apply</button>
                                </div>
                            </div>
                        </div>
                        <hr>

                        <div class="ticket-wrapper" style="position: relative;">
                            <div class="ticket-wrapper-left">
                                <i class="fa-solid fa-ticket"></i> 
                                <h3>Ticket Price</h3>
                            </div>
                            <div class="ticket-wrapper-right" id="ticket-trigger" style="cursor: pointer; display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="ticket-display" value="Free" readonly style="cursor: pointer; background: transparent; border: none; outline: none; color: #a0a0a0; text-align: right; font-size: 14px; width: 140px; pointer-events: none;">
                                <i class="fa-solid fa-pen" style="font-size: 12px; color: #a0a0a0;"></i>
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
                                
                                <div class="payment-details" id="payment-details-container" style="display: none;">
                                    <div class="ticket-tiers-wrapper">
                                        <label style="font-size: 12px; color: #a0a0a0; margin-bottom: 6px; display: block;">Ticket Categories & Prices:</label>
                                        <div id="ticket-tiers-container">
                                            <div class="ticket-tier-row">
                                                <input type="text" name="tier_name[]" class="tier-name custom-input" value="Regular" readonly style="color: #a0a0a0;">
                                                <input type="number" name="tier_price[]" class="tier-price custom-input" placeholder="Price (Rp)">
                                            </div>
                                        </div>
                                        <button type="button" id="add-tier-btn" class="add-tier-btn"><i class="fa-solid fa-plus"></i> Add VIP Tier</button>
                                    </div>
                                </div>

                                <div class="ticket-actions">
                                    <button type="button" class="apply-btn" id="apply-ticket" style="width: 100%;">Apply</button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="approval-wrapper">
                            <div class="approval-wrapper-left">
                                <i class="fa-solid fa-user-check"></i>
                                <h3>Require Approval</h3>
                            </div>
                            <div class="approval-wrapper-right">
                                <input type="hidden" name="require_approval" id="require-approval-val" value="false">
                                <button type="button" class="switch-btn" id="approval-switch"></button>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="capacity-wrapper" style="position: relative;">
                            <div class="capacity-wrapper-left">
                                <i class="fa-solid fa-users"></i> 
                                <h3>Capacity</h3>
                            </div>
                            <div class="capacity-wrapper-right" id="capacity-trigger" style="cursor: pointer; display: flex; gap: 8px; align-items: center;">
                                <input type="text" id="capacity-display" value="Unlimited" readonly style="cursor: pointer; background: transparent; border: none; outline: none; color: #a0a0a0; text-align: right; font-size: 14px; width: 160px; pointer-events: none;">
                                <i class="fa-solid fa-pen" style="font-size: 12px; color: #a0a0a0;"></i>
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
                                
                                <div class="capacity-number-input" id="capacity-number-container" style="display: none;">
                                    <label>Capacity Limit:</label>
                                    <input type="number" name="cap_amount" id="cap_amount" min="1" placeholder="e.g., 100" class="custom-input">
                                    
                                    <label style="margin-top: 8px;">Seat Assignment:</label>
                                    <div class="seat-type-options">
                                        <label class="cap-radio"><input type="radio" name="seat_type" value="Bebas" checked><span style="font-size: 12px; color: #ccc;">General Admission</span></label>
                                        <label class="cap-radio"><input type="radio" name="seat_type" value="Pilih"><span style="font-size: 12px; color: #ccc;">Select Seat Number</span></label>
                                    </div>
                                </div>
                                <button type="button" class="apply-btn" id="apply-capacity" style="margin-top: 16px; width: 100%;">Apply</button>
                            </div>
                        </div>
                        <hr>
                        
                        <div class="question-wrapper" style="position: relative; display: flex; justify-content: space-between; align-items: center;">
                            <div class="question-wrapper-left" style="display: flex; align-items: center; gap: 12px; color: #a0a0a0;">
                                <i class="fa-solid fa-clipboard-list"></i> 
                                <h3 style="margin: 0; font-size: 14px; font-weight: 500; color: #e0e0e0;">Registration Form</h3>
                            </div>
                            <div class="question-wrapper-right" id="question-trigger" style="cursor: pointer; display: flex; gap: 8px; align-items: center; color: #a0a0a0;">
                                <input type="text" id="question-display" value="Profile Info Only" readonly style="cursor: pointer; background: transparent; border: none; outline: none; color: #a0a0a0; text-align: right; font-size: 14px; width: 170px; pointer-events: none;">
                                <i class="fa-solid fa-pen" style="font-size: 12px; color: #a0a0a0;"></i>
                            </div>

                            <div class="ticket-dropdown" id="question-dropdown"> 
                                <i class="fa-solid fa-xmark close-modal" id="close-question-modal"></i>
                                <div class="modal-header-intro">
                                    <div class="modal-header-icon"><i class="fa-solid fa-clipboard-question"></i></div>
                                    <h2>Registration Form</h2>
                                    <p>Ask custom questions to your attendees.</p>
                                </div>

                                <div id="questions-container" style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                                    <label style="font-size: 14px; color: #b0b0b0; margin-bottom: 4px; display: block;">Custom Questions:</label>
                                    </div>
                                
                                <button type="button" id="add-question-btn" class="add-tier-btn" style="margin-top: 10px;"><i class="fa-solid fa-plus"></i> Add Question</button>
                                <button type="button" class="apply-btn" id="apply-question" style="width: 100%;">Apply</button>
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