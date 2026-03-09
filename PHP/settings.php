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
    <style>
        .alert-box { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="left-nav">
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
        
        <div class="right-nav" style="display: flex; align-items: center; gap: 15px; position: relative;">
            <i class="fa-regular fa-bell" style="font-size: 18px; color: #a0a0a0; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#a0a0a0'"></i>
            
            <div id="profile-dropdown-trigger" style="cursor: pointer; position: relative;" title="<?= htmlspecialchars($nav_name) ?>">
                <?php if(!empty($nav_pic)): ?>
                    <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" alt="Profile" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid #2a2a2a; display: block;">
                <?php else: ?>
                    <div style="width: 36px; height: 36px; border-radius: 50%; background-color: <?= $avatar_color ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; border: 2px solid #2a2a2a;">
                        <?= $nav_initial ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="profile-dropdown-menu" style="display: none; position: absolute; top: 50px; right: 0; background: #121212; border: 1px solid #333; border-radius: 12px; width: 220px; box-shadow: 0 10px 30px rgba(0,0,0,0.8); z-index: 1000; overflow: hidden;">
                <div style="padding: 15px; border-bottom: 1px solid #2a2a2a; display: flex; align-items: center; gap: 12px;">
                    <?php if(!empty($nav_pic)): ?>
                        <img src="../Media/uploads/<?= htmlspecialchars($nav_pic) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: <?= $avatar_color ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; flex-shrink: 0;"><?= $nav_initial ?></div>
                    <?php endif; ?>
                    <div style="overflow: hidden;">
                        <h4 style="color: #fff; font-size: 14px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($nav_name) ?></h4>
                        <p style="color: #888; font-size: 12px; margin: 0; text-transform: capitalize; margin-top: 2px;"><?= $user_role ?></p>
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
            <h1>Settings</h1>
            <div class="toggle-select-settings">
                <a href="#" class="tab-link active" data-target="account-setting">Account</a>
                <a href="#" class="tab-link" data-target="preference-setting">Preferences</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert-box alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?php 
                    if($_GET['msg'] == 'profile_updated') echo "Your profile has been updated successfully.";
                    if($_GET['msg'] == 'email_updated') echo "Your email address has been updated.";
                    if($_GET['msg'] == 'password_updated') echo "Your password has been changed successfully.";
                ?>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['err'])): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php 
                    if($_GET['err'] == 'email_exists') echo "This email is already registered to another account.";
                    if($_GET['err'] == 'password_mismatch') echo "New passwords do not match. Please try again.";
                ?>
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
                                    <img src="../Media/uploads/<?= htmlspecialchars($profile_pic) ?>" id="preview-img" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div id="preview-placeholder" style="width: 100%; height: 100%; background-color: <?= $avatar_color ?>; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700;">
                                        <?= $initial ?>
                                    </div>
                                    <img src="" id="preview-img" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <?php endif; ?>

                                <label for="profile-upload" class="upload-overlay" style="cursor: pointer;">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                                <input type="file" name="profile_picture" id="profile-upload" accept="image/*" hidden onchange="previewImage(event)">
                            </div>
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
                    <button type="submit" class="btn-primary" style="margin-top: 16px;"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
                </div>
            </form>
            
            <hr>

            <div class="section-block">
                <div class="layout-header">
                    <div>
                        <h2>Emails</h2>
                        <p class="section-desc">Manage the email addresses linked to your account.</p>
                    </div>
                    <button type="button" class="btn-add" onclick="openModal('modal-add-email')"><i class="fa-solid fa-pen"></i> Change Email</button>
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
                    <button type="button" class="btn-secondary" onclick="openModal('modal-set-password')"><i class="fa-solid fa-key"></i> Change Password</button>
                </div>
            </div>

            <hr>

            <div class="section-block">
                <h2>Delete Account</h2>
                <p class="section-desc">Permanently delete your account and all associated data.</p>
                <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete your account? This action cannot be undone.');">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn-danger"><i class="fa-solid fa-trash"></i> Delete My Account</button>
                </form>
            </div>
            
        </div>

        <div class="settings-section" id="preference-setting" style="display: none;">
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

        <hr>
        <div class="page-footer">
            <div class="left-footer"><a href="#">Discover</a><a href="#">Help</a></div>
            <div class="right-footer"><a href="#"><i class="fab fa-x"></i></a><a href="#"><i class="fab fa-youtube"></i></a><a href="#"><i class="fab fa-instagram"></i></a></div>
        </div>
    </div>

    <div class="modal-overlay" id="settings-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999; backdrop-filter: blur(5px);"></div>

    <div class="edit-modal" id="modal-add-email" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1a1a1a; padding: 30px; border-radius: 16px; border: 1px solid #333; z-index: 1000; width: 90%; max-width: 400px;">
        <button type="button" onclick="closeModal('modal-add-email')" style="position: absolute; right: 20px; top: 20px; background: transparent; border: none; color: #888; font-size: 20px; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-header-intro" style="margin-bottom: 20px; text-align: center;">
            <div style="font-size: 30px; color: #3b82f6; margin-bottom: 10px;"><i class="fa-solid fa-envelope"></i></div>
            <h2 style="color: #fff;">Change Email</h2>
            <p style="color: #a0a0a0; font-size: 13px;">Enter the new email address.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_email">
            <div class="edit-form-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #ccc; font-size: 12px; margin-bottom: 8px;">New Email Address</label>
                <input type="email" name="new_email" class="custom-input" placeholder="e.g., hello@example.com" style="width: 100%; padding: 12px; background: #0a0a0a; border: 1px solid #333; color: #fff; border-radius: 8px;" required>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeModal('modal-add-email')" style="flex: 1; padding: 12px; background: #333; color: #fff; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" style="flex: 1; padding: 12px; background: #22c55e; color: #000; font-weight: bold; border: none; border-radius: 8px; cursor: pointer;">Save Email</button>
            </div>
        </form>
    </div>

    <div class="edit-modal" id="modal-set-password" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1a1a1a; padding: 30px; border-radius: 16px; border: 1px solid #333; z-index: 1000; width: 90%; max-width: 400px;">
        <button type="button" onclick="closeModal('modal-set-password')" style="position: absolute; right: 20px; top: 20px; background: transparent; border: none; color: #888; font-size: 20px; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        <div class="modal-header-intro" style="margin-bottom: 20px; text-align: center;">
            <div style="font-size: 30px; color: #f97316; margin-bottom: 10px;"><i class="fa-solid fa-lock"></i></div>
            <h2 style="color: #fff;">Change Password</h2>
            <p style="color: #a0a0a0; font-size: 13px;">Create a new strong password.</p>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <div class="edit-form-group" style="margin-bottom: 15px;">
                <label style="display: block; color: #ccc; font-size: 12px; margin-bottom: 8px;">New Password</label>
                <input type="password" name="new_password" class="custom-input" placeholder="••••••••" style="width: 100%; padding: 12px; background: #0a0a0a; border: 1px solid #333; color: #fff; border-radius: 8px;" required>
            </div>
            <div class="edit-form-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #ccc; font-size: 12px; margin-bottom: 8px;">Confirm New Password</label>
                <input type="password" name="confirm_password" class="custom-input" placeholder="••••••••" style="width: 100%; padding: 12px; background: #0a0a0a; border: 1px solid #333; color: #fff; border-radius: 8px;" required>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeModal('modal-set-password')" style="flex: 1; padding: 12px; background: #333; color: #fff; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" style="flex: 1; padding: 12px; background: #22c55e; color: #000; font-weight: bold; border: none; border-radius: 8px; cursor: pointer;">Save Password</button>
            </div>
        </form>
    </div>

    <script>
        // SCRIPT PREVIEW GAMBAR SEBELUM DI-SAVE
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('preview-img');
                var placeholder = document.getElementById('preview-placeholder');
                output.src = reader.result;
                output.style.display = 'block';
                if(placeholder) placeholder.style.display = 'none';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // SCRIPT MODAL POP-UP
        const overlay = document.getElementById('settings-modal-overlay');
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            overlay.style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            overlay.style.display = 'none';
        }

        // SCRIPT PINDAH TAB
        const tabs = document.querySelectorAll('.tab-link');
        const sections = document.querySelectorAll('.settings-section');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                tabs.forEach(t => t.classList.remove('active'));
                sections.forEach(s => s.style.display = 'none');
                
                tab.classList.add('active');
                document.getElementById(tab.dataset.target).style.display = 'block';
            });
        });

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