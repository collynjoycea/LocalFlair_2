<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {

        $file = $_FILES['profile_picture'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($fileTmp);

        if (!in_array($fileType, $allowedTypes)) {
            die("Only JPG and PNG files are allowed.");
        }

        if ($fileSize > 2 * 1024 * 1024) {
            die("File size must be less than 2MB.");
        }

        $newFileName = "user_" . $user_id . "_" . time() . "." . pathinfo($fileName, PATHINFO_EXTENSION);

        $uploadPath = "images/" . $newFileName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {

            $oldQuery = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
            $oldQuery->bind_param("i", $user_id);
            $oldQuery->execute();
            $oldResult = $oldQuery->get_result()->fetch_assoc();

            if ($oldResult['profile_picture'] !== "default-avatar.png") {
                $oldFile = "images/" . $oldResult['profile_picture'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $updatePic = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $updatePic->bind_param("si", $newFileName, $user_id);
            $updatePic->execute();
            $_SESSION['profile_picture'] = $newFileName;

            header("Location: profile.php?avatar_success=1");
            exit();
        }
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['first_name'])) {
    $new_fname = $_POST['first_name'];
    $new_lname = $_POST['last_name'];
    $new_contact = $_POST['contact_number'];

    $update_query = "UPDATE users SET first_name = ?, last_name = ?, contact_number = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssi", $new_fname, $new_lname, $new_contact, $user_id);
    
    if ($update_stmt->execute()) {
        // Optional: Update session variables if you use them elsewhere
        $_SESSION['first_name'] = $new_fname;
        $_SESSION['last_name'] = $new_lname;
        
        // Refresh page to show updated data and exit edit mode
        header("Location: profile.php?success=1");
        exit();
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];

    // 1. Fetch current hashed password
    $pwd_query = "SELECT password FROM users WHERE user_id = ?";
    $pwd_stmt = $conn->prepare($pwd_query);
    $pwd_stmt->bind_param("i", $user_id);
    $pwd_stmt->execute();
    $pwd_result = $pwd_stmt->get_result();
    $user_data = $pwd_result->fetch_assoc();
    
    // Check length (at least 8 characters)
    if (strlen($new_pwd) < 8) {
        $error = "Password must be at least 8 characters long.";
    } 
    // Check for Uppercase, Lowercase, and Number
    elseif (!preg_match('/[A-Z]/', $new_pwd) || !preg_match('/[a-z]/', $new_pwd) || !preg_match('/[0-9]/', $new_pwd)) {
        $error = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    }
    // Check if passwords match
    elseif ($new_pwd !== $confirm_pwd) {
        $error = "New passwords do not match.";
    } 
    // Verify current password
    elseif (!password_verify($current_pwd, $user_data['password'])) {
        $error = "Current password is incorrect.";
    } 
    
    else {
        // 2. Hash and update
        $hashed_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
        $update_pwd_query = "UPDATE users SET password = ? WHERE user_id = ?";
        $update_pwd_stmt = $conn->prepare($update_pwd_query);
        $update_pwd_stmt->bind_param("si", $hashed_pwd, $user_id);
        
        if ($update_pwd_stmt->execute()) {
            header("Location: profile.php?pwd_success=1");
            exit();
        }
    }
}

// Handle Add Address Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_address'])) {
    $street = $_POST['street_address'];
    // Use the _name fields from the hidden inputs
    $barangay = $_POST['barangay_name'];
    $city = $_POST['city_name'];
    $province = $_POST['province_name'];
    $region = $_POST['region_name'];
    
    $postal = $_POST['postal_code'];
    $country = $_POST['country'];

    $addr_query = "INSERT INTO addresses (user_id, street_address, barangay, city_municipality, province, region, postal_code, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $addr_stmt = $conn->prepare($addr_query);
    $addr_stmt->bind_param("isssssss", $user_id, $street, $barangay, $city, $province, $region, $postal, $country);

    if ($addr_stmt->execute()) {
        header("Location: profile.php?address_success=1");
        exit();
    }
}

// Handle Delete Address
if (isset($_GET['delete_address'])) {
    $address_id = $_GET['delete_address'];

    // Secure the deletion by ensuring the address belongs to the logged-in user
    $delete_query = "DELETE FROM addresses WHERE address_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $address_id, $user_id);

    if ($delete_stmt->execute()) {
        header("Location: profile.php?delete_success=1");
        exit();
    }
}

// Handle Edit Address
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_address'])) {
    $address_id = $_POST['address_id'];
    $street = $_POST['street_address'];
    $barangay = $_POST['barangay_name'];
    $city = $_POST['city_name'];
    $province = $_POST['province_name'];
    $region = $_POST['region_name'];
    $postal = $_POST['postal_code'];

    $edit_addr_query = "UPDATE addresses SET street_address=?, barangay=?, city_municipality=?, province=?, region=?, postal_code=? WHERE address_id=? AND user_id=?";
    $edit_addr_stmt = $conn->prepare($edit_addr_query);
    $edit_addr_stmt->bind_param("ssssssii", $street, $barangay, $city, $province, $region, $postal, $address_id, $user_id);

    if ($edit_addr_stmt->execute()) {
        header("Location: profile.php?address_updated=1");
        exit();
    }
}

// Fetch all addresses for the logged-in user
$addr_fetch_query = "SELECT * FROM addresses WHERE user_id = ?";
$addr_fetch_stmt = $conn->prepare($addr_fetch_query);
$addr_fetch_stmt->bind_param("i", $user_id);
$addr_fetch_stmt->execute();
$addresses_result = $addr_fetch_stmt->get_result();

// Fetch user data 
$query = "SELECT first_name, last_name, email, contact_number, profile_picture FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fallback in case the session variables aren't set yet
$display_fname = $user['first_name'];
$display_lname = $user['last_name'];
$display_email = $user['email'];
$display_contact = $user['contact_number'];
$display_avatar = !empty($user['profile_picture']) ? $user['profile_picture'] : "default-avatar.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | My Profile</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="css/style.css">

    <style>
        body{
            background:#efe1c7;
            font-family:Segoe UI;
        }

        .profile-header{
            background:#d9c098;
            padding:15px 30px;
            border-radius:8px;
            margin:80px auto 10px;
            width:90%;
            font-weight:600;
            font-style:italic;
        }

        .main-card{
            background:#f7eee2;
            border:1px solid #9b6b3e;
            border-radius:10px;
            padding:20px;
            width:90%;
            margin:auto;
            margin-bottom: 5px;
        }

        .avatar{
            width:90px;
            height:90px;
            border:2px solid #8b5a2b;
            border-radius:50%;
            object-fit:cover;
        }

        .profile-top{
            background:#f3eadb;
            border:1px solid #8b5a2b;
            border-radius:8px;
            padding:15px;
            justify-content: space-between;
            margin-bottom: 3px;
            
        }

        .section{
            background:#fff;
            border:1px solid #8b5a2b;
            border-radius:8px;
            padding:20px;
            height:100%;
        }

        .section h5{
            color:#8b5a2b;
            font-weight:600;
            margin-bottom:15px;
        }

        .label{
            font-size:14px;
            color:#8b5a2b;
            font-weight:600;
        }

        .value{
            font-size:14px;
            margin-bottom:15px;
        }

        .btn-outline{
            border:1px solid #8b5a2b;
            background:none;
            border-radius:20px;
            padding:4px 14px;
            font-size:13px;
            color:#8b5a2b;
        }

        .save-btn {
            background: #e0c48f;
            border: 1px solid #8b5a2b; 
            padding: 4px 14px;
            border-radius: 20px;
            color: #8b5a2b;
            font-size: 13px;
        }

        input,select{
            border:1px solid #8b5a2b!important;
            border-radius:20px!important;
            padding:6px 14px!important;
        }

        .address-entry {
            transition: transform 0.2s ease;
        }

        .address-entry:hover {
            transform: translateY(-2px);
            border-color: #8b5a2b !important;
        }

        .badge-outline {
            border: 1px solid #8b5a2b;
            color: #8b5a2b;
            background: transparent;
        }

        .alert-custom {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            width: 90%;
            margin: 10px auto;
            padding: 10px 20px;
        }

        .btn-outline, .save-btn {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-outline:hover {
            background-color: #8b5a2b;
            color: #fff !important; 
        }

        .save-btn:hover {
            background-color: #d4b47a;
            transform: translateY(-1px); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-cancel-hover:hover {
            background-color: #dc3545 !important;
            color: #fff !important;
            border-color: #dc3545 !important;
        }

        .password-container .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #8b5a2b;
            z-index: 10;
        }

        .password-container input {
            padding-right: 40px !important;
        }

        .change-pwd-link {
            transition: all 0.2s ease;
            display: inline-block;
        }

        .change-pwd-link:hover {
            color: #9b6b3e !important; 
            text-decoration: underline !important;
            transform: translateX(2px); 
        }

        .btn-cancel-modal:hover {
            background-color: #dc3545 !important;
            color: #fff !important;
            border-color: #dc3545 !important;
        }

        #address-container {
            max-height: 320px;  
            overflow-y: auto;   
            padding-right: 5px; 
        }

        #address-container::-webkit-scrollbar {
            width: 8px;
        }

        #address-container::-webkit-scrollbar-track {
            background: #f3eadb;
            border-radius: 4px;
        }

        #address-container::-webkit-scrollbar-thumb {
            background: #d9c098; 
            border-radius: 4px;
            border: 1px solid #f3eadb;
        }

        #address-container::-webkit-scrollbar-thumb:hover {
            background: #8b5a2b; 
        }

        .address-action-link {
            transition: all 0.3s ease;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 500;
        }

        .edit-link:hover {
            color: #6d441f !important; 
            background-color: #f3eadb; 
            text-decoration: underline !important;
        }

        .delete-link:hover {
            color: #a71d2a !important; 
            background-color: #fce8e9; 
            text-decoration: underline !important;
        }

        .avatar-wrapper {
            position: relative;
            width: 90px;
            height: 90px;
            cursor: pointer;
        }

        .avatar-wrapper .avatar {
            width: 100%;
            height: 100%;
            border: 2px solid #8b5a2b;
            border-radius: 50%;
            object-fit: cover;
            transition: 0.3s ease;
        }

        .avatar-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #8b5a2b;
            color: #fff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            border: 2px solid #f7eee2;
            transition: 0.3s ease;
        }

        .avatar-wrapper:hover .avatar {
            transform: scale(1.05);
        }

        .avatar-wrapper:hover .avatar-icon {
            background: #6d441f;
        }
    </style>
<head>

<body>

<?php include 'includes/header.php'; ?>

<div class="profile-header">My Profile</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-custom alert-dismissible fade show auto-fade" role="alert">
        <strong>Success!</strong> Your profile has been updated.
    </div>
<?php endif; ?>

<?php if(isset($_GET['pwd_success'])): ?>
    <div class="alert alert-success alert-custom auto-fade">Password updated successfully!</div>
<?php endif; ?>

<?php if(isset($_GET['avatar_success'])): ?>
    <div class="alert alert-success alert-custom auto-fade">Profile picture updated successfully!</div>
<?php endif; ?>

<form class="main-card" method="POST" enctype="multipart/form-data">

<!-- TOP PROFILE CARD -->
<div class="profile-top d-flex align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <label for="profileInput" class="avatar-wrapper">
            <img src="images/<?php echo htmlspecialchars($display_avatar); ?>" 
                id="avatarPreview" 
                class="avatar">

            <span class="avatar-icon">
                <i class="fa-solid fa-camera"></i>
            </span>
        </label>

        <input type="file" name="profile_picture" id="profileInput" accept="image/*" style="display:none;">
        
        <div>
            <div class="value mb-0" style="font-weight: bold; font-size: 1.1rem; color: #8b5a2b;">
                <?php echo htmlspecialchars($display_fname . ' ' . $display_lname); ?>
            </div>

            <div class="value mb-0" style="color: #8b5a2b;">
                <?php echo htmlspecialchars($display_email); ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

<!-- PERSONAL INFORMATION -->
<div class="col-md-6"> 
    <div class="section"> 
        <h5>Personal Information</h5>

        <div class="view-mode" id="personal-view">
            <div class="label">First Name</div><div class="value"><?php echo htmlspecialchars($display_fname); ?></div>
            <div class="label">Last Name</div><div class="value"><?php echo htmlspecialchars($display_lname); ?></div>
            <div class="label">Email Address</div><div class="value"><?php echo htmlspecialchars($display_email); ?></div>
            <div class="label">Contact Number</div><div class="value"><?php echo htmlspecialchars($display_contact); ?></div>
        </div>

        <div class="edit-mode d-none" id="personal-edit">
            <label class="label">First Name</label>
            <input type="text" class="form-control mb-2" name="first_name" value="<?php echo htmlspecialchars($display_fname); ?>" required>

            <label class="label">Last Name</label>
            <input type="text" class="form-control mb-2" name="last_name" value="<?php echo htmlspecialchars($display_lname); ?>" required>

            <label class="label">Email Address (Read Only)</label>
            <input type="email" class="form-control mb-3 text-muted" value="<?php echo htmlspecialchars($display_email); ?>" readonly>

            <label class="label">Contact Number</label>
            <input type="text" class="form-control mb-2" name="contact_number" value="<?php echo htmlspecialchars($display_contact); ?>">
        </div>

        <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
            <div class="d-none" id="saveWrap">
                <button type="submit" class="save-btn">Save Changes</button>
            </div>
            
            <button type="button" class="btn-outline" id="personal-edit-btn" onclick="toggleSection('personal')">
                Edit
            </button>
        </div>

        <div class="mt-3 pt-3 border-top">
            <a href="#" class="change-pwd-link" style="color: #8b5a2b; font-size: 13px; text-decoration: none; font-weight: 500;" data-bs-toggle="modal" data-bs-target="#passwordModal">
                <i class="fa-solid fa-key me-1"></i> Change Password?
            </a>
        </div>
    </div> 
</div>

<!-- ADDRESS -->
<div class="col-md-6">
    <div class="section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0">My Addresses</h5>
            <button type="button" class="btn-outline" data-bs-toggle="modal" data-bs-target="#addressModal">+ Add New</button>
        </div>

        <?php if(isset($_GET['address_success'])): ?>
            <div class="alert alert-success py-2 mb-2 auto-fade" style="font-size: 12px; border-radius: 20px;">Address added successfully!</div>
        <?php endif; ?>

        <?php if(isset($_GET['delete_success'])): ?>
            <div class="alert alert-danger py-2 mb-2 auto-fade" style="font-size: 12px; border-radius: 20px;">Address deleted successfully.</div>
        <?php endif; ?>

        <?php if(isset($_GET['address_updated'])): ?>
            <div class="alert alert-success py-2 mb-2 auto-fade" style="font-size: 12px; border-radius: 20px;">Address updated successfully!</div>
        <?php endif; ?>

        <div id="address-container">
            <?php if ($addresses_result->num_rows > 0): ?>
                <?php while($row = $addresses_result->fetch_assoc()): ?>
                    <div class="address-entry p-3 mb-2 border rounded shadow-sm" style="background: #fff;">
                        <div class="d-flex justify-content-between">
                             <span class="badge badge-outline mb-2">Address</span>
                            <div>
                                <a href="#" 
                                    class="text-decoration-none small me-2 edit-address-btn address-action-link edit-link"
                                    style="color: #8b5a2b;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editAddressModal"
                                    data-id="<?php echo $row['address_id']; ?>"
                                    data-street="<?php echo htmlspecialchars($row['street_address']); ?>"
                                    data-brgy="<?php echo htmlspecialchars($row['barangay']); ?>"
                                    data-city="<?php echo htmlspecialchars($row['city_municipality']); ?>"
                                    data-prov="<?php echo htmlspecialchars($row['province']); ?>"
                                    data-reg="<?php echo htmlspecialchars($row['region']); ?>"
                                    data-zip="<?php echo htmlspecialchars($row['postal_code']); ?>">
                                    Edit
                                </a>
                                
                                <a href="profile.php?delete_address=<?php echo $row['address_id']; ?>" 
                                    class="text-decoration-none small text-danger address-action-link delete-link" 
                                    onclick="return confirm('Are you sure you want to delete this address?');">
                                    Delete
                                </a>
                            </div>
                        </div>
                        <div class="value mb-0">
                            <?php echo htmlspecialchars($row['street_address'] . ', ' . $row['barangay']); ?>,<br>
                            <?php echo htmlspecialchars($row['city_municipality'] . ', ' . $row['province']); ?>,<br>
                            <?php echo htmlspecialchars($row['region'] . ' ' . $row['postal_code']); ?><br>
                            <?php echo htmlspecialchars($row['country']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted small text-center mt-3">No addresses saved yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

</form>

<script>
// Store original values to reset them if user cancels
const originalData = {
    first_name: "<?php echo addslashes($display_fname); ?>",
    last_name: "<?php echo addslashes($display_lname); ?>",
    contact_number: "<?php echo addslashes($display_contact); ?>"
};

function toggleSection(section) {
    const view = document.getElementById(section + '-view');
    const edit = document.getElementById(section + '-edit');
    const saveWrap = document.getElementById('saveWrap'); 
    const actionBtn = document.getElementById(section + '-edit-btn');

    const isEditing = view.classList.toggle('d-none');
    edit.classList.toggle('d-none');
    saveWrap.classList.toggle('d-none');

    if (isEditing) {
        // User clicked EDIT -> Now in Cancel Mode
        actionBtn.innerHTML = "Cancel";
        actionBtn.style.color = "#dc3545"; 
        actionBtn.style.borderColor = "#dc3545";
        actionBtn.classList.add('btn-cancel-hover'); 
    } else {
        // User clicked CANCEL -> Now in Edit Mode
        actionBtn.innerHTML = "Edit";
        actionBtn.style.color = "#8b5a2b";
        actionBtn.style.borderColor = "#8b5a2b";
        actionBtn.classList.remove('btn-cancel-hover'); 

        // Reset fields
        document.getElementsByName('first_name')[0].value = originalData.first_name;
        document.getElementsByName('last_name')[0].value = originalData.last_name;
        document.getElementsByName('contact_number')[0].value = originalData.contact_number;
    }
}

// Auto-Fade Alerts 
setTimeout(() => {
    const alerts = document.querySelectorAll('.auto-fade');
    
    alerts.forEach(alert => {
        alert.style.transition = "opacity 0.5s ease";
        alert.style.opacity = "0";
        
        setTimeout(() => alert.remove(), 500);
    });
}, 3000);

function toggleVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

// Keep modal open if there is a password error
<?php if (isset($error)): ?>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('passwordModal'));
        myModal.show();
    });
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function () {

    const apiBase = "https://psgc.gitlab.io/api";

    const regionSelect = document.getElementById('region');
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    // Load Regions
    fetch(`${apiBase}/regions/`)
        .then(res => res.json())
        .then(data => {
            data.sort((a, b) => a.name.localeCompare(b.name));
            data.forEach(region => {
                let opt = document.createElement('option');
                opt.value = region.code;
                opt.text = region.name;
                regionSelect.add(opt);
            });
        });

    // Region → Province
    regionSelect.addEventListener('change', function () {

        const regionCode = this.value;
        document.getElementById('region_name').value =
            this.options[this.selectedIndex].text;

        // Reset everything
        provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
        citySelect.innerHTML = '<option value="" disabled selected>Select City</option>';
        barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';

        barangaySelect.disabled = true;

        // NCR special case (NO PROVINCE)
        if (regionCode === "130000000") {

            provinceSelect.disabled = true;
            document.getElementById('province_name').value = "NCR";

            citySelect.disabled = false;

            fetch(`${apiBase}/regions/${regionCode}/cities-municipalities/`)
                .then(res => res.json())
                .then(data => {
                    data.sort((a, b) => a.name.localeCompare(b.name));
                    data.forEach(city => {
                        let opt = document.createElement('option');
                        opt.value = city.code;
                        opt.text = city.name;
                        citySelect.add(opt);
                    });
                });

            return;
        }

        // Normal regions WITH provinces
        provinceSelect.disabled = false;

        fetch(`${apiBase}/regions/${regionCode}/provinces/`)
            .then(res => res.json())
            .then(data => {
                data.sort((a, b) => a.name.localeCompare(b.name));
                data.forEach(prov => {
                    let opt = document.createElement('option');
                    opt.value = prov.code;
                    opt.text = prov.name;
                    provinceSelect.add(opt);
                });
            });
    });

    // Province → City
    provinceSelect.addEventListener('change', function () {
        citySelect.disabled = false;
        citySelect.innerHTML = '<option value="" disabled selected>Select City</option>';

        document.getElementById('province_name').value =
            this.options[this.selectedIndex].text;

        fetch(`${apiBase}/provinces/${this.value}/cities-municipalities/`)
            .then(res => res.json())
            .then(data => {
                data.sort((a, b) => a.name.localeCompare(b.name));
                data.forEach(city => {
                    let opt = document.createElement('option');
                    opt.value = city.code;
                    opt.text = city.name;
                    citySelect.add(opt);
                });
            });
    });

    // City → Barangay
    citySelect.addEventListener('change', function () {
        barangaySelect.disabled = false;
        barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';

        document.getElementById('city_name').value =
            this.options[this.selectedIndex].text;

        fetch(`${apiBase}/cities-municipalities/${this.value}/barangays/`)
            .then(res => res.json())
            .then(data => {
                data.sort((a, b) => a.name.localeCompare(b.name));
                data.forEach(brgy => {
                    let opt = document.createElement('option');
                    opt.value = brgy.code;
                    opt.text = brgy.name;
                    barangaySelect.add(opt);
                });
            });
    });

    barangaySelect.addEventListener('change', function () {
        document.getElementById('barangay_name').value =
            this.options[this.selectedIndex].text;
    });

});

document.addEventListener("DOMContentLoaded", () => {

    const api = "https://psgc.gitlab.io/api";

    const er = document.getElementById("edit_region");
    const ep = document.getElementById("edit_province");
    const ec = document.getElementById("edit_city");
    const eb = document.getElementById("edit_barangay");

    // Load Regions
    fetch(`${api}/regions/`)
        .then(r => r.json())
        .then(data => {
            data.sort((a,b) => a.name.localeCompare(b.name));
            data.forEach(rg => {
                er.add(new Option(rg.name, rg.code));
            });
        });

    er.addEventListener("change", () => {
        document.getElementById("edit_region_name").value =
            er.options[er.selectedIndex].text;

        ep.innerHTML = '<option disabled selected>Select Province</option>';
        ec.innerHTML = '<option disabled selected>Select City</option>';
        eb.innerHTML = '<option disabled selected>Select Barangay</option>';

        ec.disabled = eb.disabled = true;

        // NCR
        if (er.value === "130000000") {
            ep.disabled = true;
            document.getElementById("edit_province_name").value = "NCR";
            ec.disabled = false;

            fetch(`${api}/regions/${er.value}/cities-municipalities/`)
                .then(r => r.json())
                .then(data => {
                    data.forEach(c => ec.add(new Option(c.name, c.code)));
                });
            return;
        }

        ep.disabled = false;

        fetch(`${api}/regions/${er.value}/provinces/`)
            .then(r => r.json())
            .then(data => {
                data.forEach(p => ep.add(new Option(p.name, p.code)));
            });
    });

    ep.addEventListener("change", () => {
        document.getElementById("edit_province_name").value =
            ep.options[ep.selectedIndex].text;

        ec.innerHTML = '<option disabled selected>Select City</option>';
        eb.innerHTML = '<option disabled selected>Select Barangay</option>';
        ec.disabled = false;

        fetch(`${api}/provinces/${ep.value}/cities-municipalities/`)
            .then(r => r.json())
            .then(data => {
                data.forEach(c => ec.add(new Option(c.name, c.code)));
            });
    });

    ec.addEventListener("change", () => {
        document.getElementById("edit_city_name").value =
            ec.options[ec.selectedIndex].text;

        eb.innerHTML = '<option disabled selected>Select Barangay</option>';
        eb.disabled = false;

        fetch(`${api}/cities-municipalities/${ec.value}/barangays/`)
            .then(r => r.json())
            .then(data => {
                data.forEach(b => eb.add(new Option(b.name, b.code)));
            });
    });

    eb.addEventListener("change", () => {
        document.getElementById("edit_barangay_name").value =
            eb.options[eb.selectedIndex].text;
    });
});

document.querySelectorAll(".edit-address-btn").forEach(btn => {
    btn.addEventListener("click", () => {

        document.getElementById("edit_addr_id").value = btn.dataset.id;
        document.getElementById("edit_street").value = btn.dataset.street;
        document.getElementById("edit_zip").value = btn.dataset.zip;

        const regName = btn.dataset.reg;
        const provName = btn.dataset.prov;
        const cityName = btn.dataset.city;
        const brgyName = btn.dataset.brgy;

        // Wait for region list to load
        setTimeout(() => {
            [...edit_region.options].forEach(opt => {
                if (opt.text === regName) {
                    opt.selected = true;
                    edit_region.dispatchEvent(new Event("change"));
                }
            });

            setTimeout(() => {
                if (provName !== "NCR") {
                    [...edit_province.options].forEach(opt => {
                        if (opt.text === provName) {
                            opt.selected = true;
                            edit_province.dispatchEvent(new Event("change"));
                        }
                    });
                }

                setTimeout(() => {
                    [...edit_city.options].forEach(opt => {
                        if (opt.text === cityName) {
                            opt.selected = true;
                            edit_city.dispatchEvent(new Event("change"));
                        }
                    });

                    setTimeout(() => {
                        [...edit_barangay.options].forEach(opt => {
                            if (opt.text === brgyName) opt.selected = true;
                        });
                    }, 400);
                }, 400);
            }, 400);
        }, 400);
    });
});

document.getElementById("profileInput").addEventListener("change", function(e) {

    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function() {
        document.getElementById("avatarPreview").src = reader.result;
    };

    reader.readAsDataURL(file);

    e.target.closest("form").submit();
});

let cropper;
const input = document.getElementById('profileInput');
const cropImage = document.getElementById('cropImage');
const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

input.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        cropImage.src = event.target.result;
        cropModal.show();

        setTimeout(() => {
            cropper = new Cropper(cropImage, {
                aspectRatio: 1, // 🔥 1:1 ratio (square)
                viewMode: 1,
                autoCropArea: 1
            });
        }, 300);
    };
    reader.readAsDataURL(file);
});

document.getElementById('cropSaveBtn').addEventListener('click', function() {

    const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300
    });

    canvas.toBlob(function(blob) {

        const formData = new FormData();
        formData.append('profile_picture', blob, 'avatar.png');

        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            location.reload();
        });

    });

    cropModal.hide();
});
</script>

<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #f7eee2; border: 1px solid #8b5a2b;">
            <div class="modal-header" style="border-bottom: 1px solid #d9c098;">
                <h5 class="modal-title" style="color: #8b5a2b;">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger py-2" style="font-size: 13px; border-radius: 20px;">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <label class="label">Current Password</label>
                    <div class="password-container position-relative mb-3">
                        <input type="password" name="current_password" id="curr_pwd" class="form-control" required>
                        <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('curr_pwd', this)"></i>
                    </div>
                    
                    <label class="label">New Password</label>
                    <div class="password-container position-relative mb-3">
                        <input type="password" name="new_password" id="new_pwd" class="form-control" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                        <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('new_pwd', this)"></i>
                    </div>
                    
                    <label class="label">Confirm New Password</label>
                    <div class="password-container position-relative mb-3">
                        <input type="password" name="confirm_password" id="conf_pwd" class="form-control" required>
                        <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('conf_pwd', this)"></i>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #d9c098;">
                    <button type="button" class="btn-outline btn-cancel-modal" data-bs-dismiss="modal" style="border: none;">Cancel</button>
                    <button type="submit" name="change_password" class="save-btn">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: #f7eee2; border: 1px solid #8b5a2b;">
            <div class="modal-header" style="border-bottom: 1px solid #d9c098;">
                <h5 class="modal-title" style="color: #8b5a2b;">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="label">Street Address / House Number</label>
                            <input type="text" name="street_address" class="form-control" placeholder="House No., Building, Street Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Region</label>
                            <select name="region" id="region" class="form-select" required>
                                <option value="" disabled selected>Select Region</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Province</label>
                            <select name="province" id="province" class="form-select" required disabled>
                                <option value="" disabled selected>Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">City / Municipality</label>
                            <select name="city_municipality" id="city" class="form-select" required disabled>
                                <option value="" disabled selected>Select City</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Barangay</label>
                            <select name="barangay" id="barangay" class="form-select" required disabled>
                                <option value="" disabled selected>Select Barangay</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Country</label>
                            <input type="text" name="country" class="form-control" value="Philippines" readonly>
                        </div>
                    </div>
                    <input type="hidden" name="region_name" id="region_name">
                    <input type="hidden" name="province_name" id="province_name">
                    <input type="hidden" name="city_name" id="city_name">
                    <input type="hidden" name="barangay_name" id="barangay_name">
                </div>
                <div class="modal-footer" style="border-top: 1px solid #d9c098;">
                    <button type="button" class="btn-outline btn-cancel-modal" data-bs-dismiss="modal" style="border: none;">Cancel</button>
                    <button type="submit" name="add_address" class="save-btn">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #f7eee2; border: 1px solid #8b5a2b;">
            <div class="modal-header" style="border-bottom: 1px solid #d9c098;">
                <h5 class="modal-title" style="color: #8b5a2b;">Edit Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="address_id" id="edit_addr_id">
                <div class="modal-body">
                    <label class="label">Street Address</label>
                    <input type="text" name="street_address" id="edit_street" class="form-control mb-3" required>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="label">Region</label>
                            <select id="edit_region" class="form-select" required>
                                <option value="" disabled selected>Select Region</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Province</label>
                            <select id="edit_province" class="form-select" required disabled>
                                <option value="" disabled selected>Select Province</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">City / Municipality</label>
                            <select id="edit_city" class="form-select" required disabled>
                                <option value="" disabled selected>Select City</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label">Barangay</label>
                            <select id="edit_barangay" class="form-select" required disabled>
                                <option value="" disabled selected>Select Barangay</option>
                            </select>
                        </div>
                    </div>
                    <label class="label">Postal Code</label>
                    <input type="text" name="postal_code" id="edit_zip" class="form-control mb-3" required>
                    <input type="hidden" name="region_name" id="edit_region_name">
                    <input type="hidden" name="province_name" id="edit_province_name">
                    <input type="hidden" name="city_name" id="edit_city_name">
                    <input type="hidden" name="barangay_name" id="edit_barangay_name">
                </div>
                <div class="modal-footer" style="border-top: 1px solid #d9c098;">
                    <button type="button" class="btn-outline btn-cancel-modal" data-bs-dismiss="modal" style="border: none;">Cancel</button>
                    <button type="submit" name="update_address" class="save-btn">Update Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cropModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#f7eee2;">
      <div class="modal-header">
        <h5 class="modal-title">Crop Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div>
          <img id="cropImage" style="max-width:100%;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-outline" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="save-btn" id="cropSaveBtn">Save</button>
      </div>
    </div>
  </div>
</div>



</body>
</html>