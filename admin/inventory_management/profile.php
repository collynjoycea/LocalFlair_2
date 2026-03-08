<?php
session_start();

// 1. Database Connection
$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Access Control
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); // Labas ng folder para sa login
    exit();
}

$user_id = $_SESSION['admin_id'];

// 3. Fetch User Data
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    die("User not found.");
}

$full_name = $userData['name'];
$email = $userData['email'];

// 4. Role Labeling
$role = "Inventory Manager"; // Default label para sa folder na ito

// 5. Image Path Logic
// Dahil ang profile.php ay nasa inventory_management/, lalabas tayo para sa uploads
$session_pic = $userData['admin_pic'] ?? '';
$pic_path = "../uploads/profile/";

$profile_img = !empty($session_pic) 
    ? ($pic_path . $session_pic) 
    : "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=e95a24&color=fff&size=128";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* (Manatili ang CSS styles na binigay ko kanina) */
        :root { 
            --bg: #f0f2f5; --primary: #e95a24; --primary-light: #fff0eb;
            --text-dark: #1a202c; --text-muted: #718096; --white: #ffffff;
            --sidebar-width: 260px; --glass: rgba(255, 255, 255, 0.9);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; display: flex; min-height: 100vh; }
        .main { flex: 1; margin-left: var(--sidebar-width); padding: 40px; padding-top: 100px; display: flex; flex-direction: column; align-items: center; }
        .profile-container { width: 100%; max-width: 950px; background: var(--glass); backdrop-filter: blur(10px); border-radius: 20px; padding: 45px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(255, 255, 255, 0.6); }
        .profile-header { display: flex; align-items: center; gap: 25px; margin-bottom: 40px; padding: 25px; background: rgba(255, 255, 255, 0.5); border: 1px solid #edf2f7; border-radius: 16px; }
        .avatar-container { position: relative; width: 110px; height: 110px; }
        .avatar-img { width: 100%; height: 100%; border-radius: 16px; object-fit: cover; }
        .edit-badge { position: absolute; bottom: -10px; right: -10px; background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 4px solid var(--white); transition: 0.3s; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; column-gap: 50px; row-gap: 30px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-muted); }
        .input-box { position: relative; }
        .input-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
        .form-group input { width: 100%; padding: 12px 15px 12px 45px; border: 1.5px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; }
        .btn-row { display: flex; justify-content: flex-end; gap: 15px; margin-top: 40px; width: 100%; }
        .btn { padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; transition: 0.3s; }
        .btn-save { background: var(--primary); color: white; }
        .btn-cancel { background: #e2e8f0; color: var(--text-dark); }
    </style>
</head>
<body>

<?php 
    // DAHIL NASA PAREHONG FOLDER, DIRECT CALL LANG:
   include 'includes/sidebar.php'; 
include 'includes/topbar.php';
?>

<div class="main">
    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar-container">
                <img src="<?= $profile_img ?>" class="avatar-img">
                <label for="profile-upload" class="edit-badge">
                    <i class="fa fa-pencil"></i>
                </label>
                <form id="picForm" action="update_profile_pic.php" method="POST" enctype="multipart/form-data">
                    <input id="profile-upload" name="admin_pic" type="file" style="display:none;" onchange="document.getElementById('picForm').submit();">
                </form>
            </div>
            <div class="user-info">
                <h2><?= htmlspecialchars($full_name) ?></h2>
                <p><?= $role ?></p>
                <span style="font-size: 12px; color: #10b981;"><i class="fa fa-circle"></i> System Online</span>
            </div>
        </div>

        <form action="update_profile_action.php" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Manager ID</label>
                    <input type="text" value="IM-<?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-box">
                        <i class="fa fa-user"></i>
                        <input type="text" name="name" value="<?= htmlspecialchars($full_name) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-box">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-box">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current">
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-cancel" onclick="window.history.back()">Discard</button>
                <button type="submit" class="btn btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>