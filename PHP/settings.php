<?php
session_start();
require_once 'config/connect.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// 2. TENTUKAN WARNA AVATAR BERDASARKAN ROLE
// Admin/Superadmin = Oranye, User = Biru
$avatar_color = ($user_role === 'admin' || $user_role === 'superadmin') ? '#f97316' : '#3b82f6';

// 3. PROSES FORM (POST REQUESTS)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // A. Update Profil (Nama & Foto)
    if ($action == 'update_profile') {
        $fname = $conn->real_escape_string($_POST['first_name']);
        $lname = $conn->real_escape_string($_POST['last_name']);
        $full_name = trim($fname . " " . $lname);
        
        $update_query = "UPDATE users SET full_name = '$full_name'";

        // Jika upload foto baru
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = "pp_" . $user_id . "_" . time() . "." . $ext;
            $target_dir = "../Media/uploads/";
            
            if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_dir . $new_filename)) {
                $update_query .= ", profile_picture = '$new_filename'";
            }
        }
        
        $update_query .= " WHERE id_user = $user_id";
        $conn->query($update_query);
        
        $_SESSION['name'] = $full_name;
        header("Location: settings.php?msg=profile_updated");
        exit();
    }

    // B. Update Email
    if ($action == 'update_email') {
        $new_email = $conn->real_escape_string($_POST['new_email']);
        $cek = $conn->query("SELECT id_user FROM users WHERE email = '$new_email' AND id_user != $user_id");
        if ($cek->num_rows > 0) {
            header("Location: settings.php?err=email_exists");
            exit();
        } else {
            $conn->query("UPDATE users SET email = '$new_email' WHERE id_user = $user_id");
            header("Location: settings.php?msg=email_updated");
            exit();
        }
    }

    // C. Update Password
    if ($action == 'update_password') {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        if ($new_pass === $confirm_pass && !empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hash' WHERE id_user = $user_id");
            header("Location: settings.php?msg=password_updated");
            exit();
        } else {
            header("Location: settings.php?err=password_mismatch");
            exit();
        }
    }

    // D. Delete Account
    if ($action == 'delete_account') {
        $conn->query("DELETE FROM users WHERE id_user = $user_id");
        session_destroy();
        header("Location: signin.php");
        exit();
    }
}

// 4. AMBIL DATA USER SAAT INI
$query = $conn->query("SELECT * FROM users WHERE id_user = $user_id");
$user_data = $query->fetch_assoc();

$name_parts = explode(" ", $user_data['full_name'], 2);
$first_name = htmlspecialchars($name_parts[0]);
$last_name = isset($name_parts[1]) ? htmlspecialchars($name_parts[1]) : '';

$email = htmlspecialchars($user_data['email']);
$profile_pic = $user_data['profile_picture'];
$initial = strtoupper(substr($first_name, 0, 1));

// Variabel Navbar
$nav_name = $user_data['full_name'];
$nav_initial = $initial;
$nav_pic = $user_data['profile_picture'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureGate - Settings</title>
    <link rel="stylesheet" href="../CSS/userevent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
            <i class="fa-solid fa-bars hamburger-btn" id="hamburger-btn"></i>
            <h1>SecureGate</h1>
        </div>
        
        <div class="main-nav">
            <?php if($user_role == 'admin' || $user_role == 'superadmin'): ?>
                <div class="main-nav-discover"><i class="fa-solid fa-house"></i><a href="adminevent.php">Home</a></div>
                <div class="main-nav-event"><i class="fa-regular fa-calendar-plus"></i><a href="create.php">Create Event</a></div>
            <?php else: ?>
                <div class="main-nav-discover"><i class="fa-regular fa-compass"></i><a href="discover.php">Discover</a></div>
                <div class="main-nav-event"><i class="fa-solid fa-ticket"></i><a href="mainpage.php">Event</a></div>
            <?php endif; ?>
        </div>
        
        <div class="right-nav">
            <i class="fa-regular fa-bell nav-bell-icon" title="Notifications"></i>
            
            <div id="profile-dropdown-trigger" class="profile-dropdown-trigger" title="<?= htmlspecialchars($nav_name) ?>">
                <?php if(!empty($nav_pic)): ?>
                    <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="Profile" class="profile-pic-small">
                <?php else: ?>
                    <div class="profile-initial-small" style="background-color: <?= $avatar_color ?>;">
                        <?= $nav_initial ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="profile-dropdown-menu" class="profile-dropdown-menu">
                <div class="dropdown-header">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" class="profile-pic-large">
                    <?php else: ?>
                        <div class="profile-initial-large" style="background-color: <?= $avatar_color ?>;"><?= $nav_initial ?></div>
                    <?php endif; ?>
                    <div class="dropdown-user-info">
                        <h4 class="dropdown-user-name"><?= htmlspecialchars($nav_name) ?></h4>
                        <p class="dropdown-user-role"><?= $user_role ?></p>
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
            <h1>Settings</h1>
            <div class="toggle-select-settings">
                <a href="#" class="tab-link active" data-target="account-setting">Account</a>
                <a href="#" class="tab-link" data-target="preference-setting">Preferences</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert-box alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>
                <?php 
                    if($_GET['msg'] == 'profile_updated') echo "Your profile has been updated successfully.";
                    if($_GET['msg'] == 'email_updated') echo "Your email address has been updated.";
                    if($_GET['msg'] == 'password_updated') echo "Your password has been changed successfully.";
                ?>
                </span>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['err'])): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>
                <?php 
                    if($_GET['err'] == 'email_exists') echo "This email is already registered to another account.";
                    if($_GET['err'] == 'password_mismatch') echo "New passwords do not match. Please try again.";
                ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="settings-section active" id="account-setting">
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="section-block">
                    <h2>Your Profile</h2>
                    <p class="section-desc">Manage your personal information and profile picture.</p>
                    
                    <div class="profile-wrapper">
                        <div class="img-input">
                            <div class="img-container">
                                <?php if(!empty($profile_pic)): ?>
                                    <img src="../Media/uploads/<?= htmlspecialchars($profile_pic) ?>" id="preview-img" alt="Profile" class="preview-image-style">
                                <?php else: ?>
                                    <div id="preview-placeholder" class="profile-initial-preview" style="background-color: <?= $avatar_color ?>;">
                                        <?= $initial ?>
                                    </div>
                                    <img src="" id="preview-img" alt="Profile" class="preview-image-style hidden">
                                <?php endif; ?>
                            </div>
                            
                            <label for="profile-upload" class="upload-overlay">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            <input type="file" name="profile_picture" id="profile-upload" accept="image/*" hidden>
                        </div>

                        <div class="name-input">
                            <div class="input-group">
                                <span>First Name</span>
                                <input type="text" name="first_name" placeholder="First Name" value="<?= $first_name ?>" required class="custom-input">
                            </div>
                            <div class="input-group">
                                <span>Last Name</span>
                                <input type="text" name="last_name" placeholder="Last Name" value="<?= $last_name ?>" class="custom-input">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary mt-16"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
                </div>
            </form>
            
            <hr>

            <div class="section-block">
                <div class="layout-header">
                    <div>
                        <h2>Emails</h2>
                        <p class="section-desc">Manage the email addresses linked to your account.</p>
                    </div>
                    <button type="button" class="btn-add open-modal-trigger" data-modal="modal-add-email"><i class="fa-solid fa-pen"></i> Change Email</button>
                </div>
                
                <div class="display-card">
                    <div class="card-atas">
                        <h3 id="display-email-text"><?= $email ?></h3>
                        <span class="badge-primary">Primary</span>
                    </div>
                    <p>This email will be shared with hosts when you register for their events.</p>
                </div>
            </div>

            <hr>

            <div class="section-block">
                <div class="layout-header pass-layout">
                    <div class="pass-info">
                        <div class="icon-circle"><i class="fa-solid fa-lock"></i></div>
                        <div>
                            <h2>Account Password</h2>
                            <p class="section-desc">Update your password to keep your account secure.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-secondary open-modal-trigger" data-modal="modal-set-password"><i class="fa-solid fa-key"></i> Change Password</button>
                </div>
            </div>

            <hr>

            <div class="section-block">
                <h2>Delete Account</h2>
                <p class="section-desc">Permanently delete your account and all associated data.</p>
                <form method="POST" id="delete-account-form">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i> Delete My Account</button>
                </form>
            </div>
            
        </div>

        <div class="settings-section hidden" id="preference-setting">
            <div class="section-block">
                <h2>Appearance</h2>
                <p class="section-desc">Customize the look and feel of SecureGate.</p>
                
                <div class="display-card appearance-card">
                    <div>
                        <h3>Light Theme</h3>
                        <p>Change the website theme from dark to light mode.</p>
                    </div>
                    <button class="switch-btn" id="theme-toggle"></button>
                </div>
            </div>
        </div>

        <hr class="garis-footer">
        <div class="page-footer">
            <div class="left-footer">
                <a href="#">Discover</a>
                <a href="#">Help</a>
            </div>
            <div class="right-footer"><a href="#"><i class="fab fa-x"></i></a><a href="#"><i class="fab fa-youtube"></i></a><a href="#"><i class="fab fa-instagram"></i></a></div>
        </div>
    </div>

    <div class="modal-overlay" id="settings-modal-overlay"></div>

    <div class="edit-modal" id="modal-add-email">
        <button type="button" class="modal-close-icon close-modal-btn"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-header-intro">
            <div class="modal-header-icon icon-blue"><i class="fa-solid fa-envelope"></i></div>
            <h2>Change Email</h2>
            <p>Enter the new email address.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_email">
            <div class="edit-form-group mb-20">
                <label>New Email Address</label>
                <input type="email" name="new_email" class="custom-input modal-input" placeholder="e.g., hello@example.com" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel close-modal-btn">Cancel</button>
                <button type="submit" class="btn-confirm-green">Save Email</button>
            </div>
        </form>
    </div>

    <div class="edit-modal" id="modal-set-password">
        <button type="button" class="modal-close-icon close-modal-btn"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-header-intro">
            <div class="modal-header-icon icon-orange"><i class="fa-solid fa-lock"></i></div>
            <h2>Change Password</h2>
            <p>Create a new strong password.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <div class="edit-form-group mb-15">
                <label>New Password</label>
                <input type="password" name="new_password" class="custom-input modal-input" placeholder="••••••••" required>
            </div>
            <div class="edit-form-group mb-20">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="custom-input modal-input" placeholder="••••••••" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel close-modal-btn">Cancel</button>
                <button type="submit" class="btn-confirm-green">Save Password</button>
            </div>
        </form>
    </div>

    <script src="../JS/userevent.js"></script>
</body>
</html>