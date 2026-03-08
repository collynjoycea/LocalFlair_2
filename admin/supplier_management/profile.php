<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

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
$role = "Administrator";

if (strpos($email, 'inventory') !== false) $role = "Inventory Manager";
if (strpos($email, 'supplier') !== false) $role = "Supplier Manager";
if (strpos($email, 'order') !== false) $role = "Order Manager";

$profile_img = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=e95a24&color=fff&size=128";
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
        :root { 
            --bg: #f0f2f5; 
            --primary: #e95a24; 
            --primary-light: #fff0eb;
            --text-dark: #1a202c; 
            --text-muted: #718096;
            --white: #ffffff;
            --sidebar-width: 260px;
            --glass: rgba(255, 255, 255, 0.9);
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg);
            /* Soft Abstract Background */
            background-image: 
                radial-gradient(at 0% 0%, rgba(233, 90, 36, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(233, 90, 36, 0.05) 0px, transparent 50%);
            margin: 0; 
            display: flex; 
            color: var(--text-dark);
            min-height: 100vh;
        }

        .main { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 40px; 
            padding-top: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: fadeIn 0.8s ease-out;
        }

        .profile-container {
            width: 100%;
            max-width: 950px;
            background: var(--glass);
            backdrop-filter: blur(10px); /* Glassmorphism effect */
            border-radius: 20px;
            padding: 45px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: fadeInUp 0.6s ease-out;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 40px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid #edf2f7;
            border-radius: 16px;
            transition: 0.3s;
        }

        .profile-header:hover {
            border-color: var(--primary);
            background: var(--white);
        }

        .avatar-container {
            position: relative;
            width: 110px;
            height: 110px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .edit-badge {
            position: absolute;
            bottom: -10px;
            right: -10px;
            background: var(--primary);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 4px solid var(--white);
            box-shadow: 0 4px 10px rgba(233, 90, 36, 0.3);
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .edit-badge:hover { transform: scale(1.15) rotate(10deg); background: #d14a1a; }

        .user-info h2 { margin: 0; font-size: 24px; font-weight: 700; color: #1a202c; }
        .user-info p { margin: 2px 0; color: var(--text-muted); font-size: 14px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; 
            column-gap: 50px; 
            row-gap: 30px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            margin-left: 2px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box i {
            position: absolute;
            left: 18px;
            color: #a0aec0;
            transition: 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            background: var(--white);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
            background: #fff;
        }

        .form-group input:focus + i {
            color: var(--primary);
        }

        .form-group input:disabled {
            background: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
            border-style: dashed;
            padding-left: 15px;
        }

        .btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 50px;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-cancel { 
            background: transparent; 
            color: #718096; 
            border: 1.5px solid #e2e8f0;
        }
        
        .btn-cancel:hover { 
            background: #edf2f7; 
            color: #4a5568;
            transform: translateY(-2px);
        }

        .btn-save { 
            background: var(--primary); 
            color: white; 
            border: none;
            box-shadow: 0 4px 15px rgba(233, 90, 36, 0.25);
        }

        .btn-save:hover { 
            background: #d14a1a; 
            box-shadow: 0 6px 20px rgba(233, 90, 36, 0.4);
            transform: translateY(-2px);
        }

        .btn-save:active { transform: translateY(0); }

    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; include 'includes/topbar.php'; ?>

<div class="main">
    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar-container">
                <img src="<?= $profile_img ?>" class="avatar-img">
                <label for="profile-upload" class="edit-badge">
                    <i class="fa fa-pencil"></i>
                </label>
                <input id="profile-upload" type="file" style="display:none;">
            </div>
            <div class="user-info">
                <h2><?= htmlspecialchars($full_name) ?></h2>
                <p><?= $role ?> Role</p>
                <div style="background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: inline-block; margin-top: 8px; font-weight: 600; letter-spacing: 0.3px;">
                    <i class="fa fa-check-circle" style="margin-right: 4px;"></i> Active Account
                </div>
            </div>
        </div>

        <form action="update_profile_action.php" method="POST">
            <div class="form-grid">
                
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" value="SM-<?= str_pad($user_id, 5, '0', STR_PAD_LEFT) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-box">
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>">
                        <i class="fa fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?= $role ?>" disabled>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-box">
                        <input type="password" name="new_password" placeholder="••••••••">
                        <i class="fa fa-lock"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Display Name</label>
                    <div class="input-box">
                        <input type="text" name="name" value="<?= htmlspecialchars($full_name) ?>">
                        <i class="fa fa-user"></i>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
                <button type="submit" class="btn btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>