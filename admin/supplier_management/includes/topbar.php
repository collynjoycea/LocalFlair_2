<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** * LOGIC CHECK:
 * Hinihila ang details depende kung sino ang active session.
**/
$user_name = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'Staff Member';
$user_pic = $_SESSION['admin_pic'] ?? $_SESSION['employee_pic'] ?? ''; 

// Pathing logic: I-check kung ang current page ay nasa loob ng anumang subfolder
$current_path = $_SERVER['PHP_SELF'];
$is_subfolder = (
    strpos($current_path, '/admin/') !== false || 
    strpos($current_path, '/includes/') !== false || 
    strpos($current_path, '/supplier_management/') !== false
);

$base_path = $is_subfolder ? "../" : "";
$img_folder = $base_path . "uploads/profile/";

// Default Image Logic
$display_pic = !empty($user_pic) ? ($img_folder . $user_pic) : "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=e95a24&color=fff";
?>

<style>
    :root {
        --topbar-height: 70px;
        --sidebar-width: 260px;
        --accent-orange: #e95a24;
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    .topbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 0 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: var(--topbar-height);
        position: fixed;
        top: 20px;            
        left: calc(var(--sidebar-width) + 20px); 
        right: 20px;          
        z-index: 1000;         
        border-radius: 20px; 
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        padding: 8px 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        transition: 0.3s;
        width: 350px;
    }

    .search-box:focus-within {
        border-color: var(--accent-orange);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(233, 90, 36, 0.1);
    }

    .search-box input {
        border: none !important;
        outline: none !important;
        background: transparent !important;
        width: 100%;
        font-size: 14px;
        color: var(--text-dark);
        font-weight: 500;
    }

    .top-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    /* Notification Styles */
    .notif-btn {
        position: relative;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: 0.2s;
    }
    .notif-btn:hover { background: #f8fafc; }

    #notif-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #ef4444;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 2px solid #fff;
        display: none; /* Hidden by default */
    }

    .notif-dropdown {
        display: none;
        position: absolute;
        top: 55px;
        right: 0;
        width: 300px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 1100;
        overflow: hidden;
    }

    .notif-header { padding: 12px 15px; font-weight: 700; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .notif-item { padding: 12px 15px; display: block; text-decoration: none; color: var(--text-dark); font-size: 13px; transition: 0.2s; border-bottom: 1px solid #f8fafc; }
    .notif-item:hover { background: #fff4f0; }

    /* User Info & Dropdown */
    .user-info-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 5px 15px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: 0.2s;
        position: relative;
        height: 50px;
    }

    .user-info-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }

    .user-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark);
        white-space: nowrap;
    }

    .user-info-btn img {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid #fff;
    }

    .profile-dropdown {
        display: none;
        position: absolute;
        top: 60px; 
        right: 0;
        width: 200px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 1100;
        padding: 8px;
    }

    .profile-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 15px;
        text-decoration: none;
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 500;
        border-radius: 10px;
        transition: 0.2s;
    }

    .profile-dropdown a:hover {
        background: #fff4f0;
        color: var(--accent-orange);
    }
    
    .profile-dropdown a.logout-link:hover {
        background: #fef2f2;
        color: #ef4444;
    }
</style>

<header class="topbar">
    <div class="search-box">
        <i class="fa fa-search" style="color: #94a3b8;"></i>
        <input type="text" id="globalSearch" placeholder="Search anything...">
    </div>

    <div class="top-right">
        <div class="notif-btn" id="notifBellBtn">
            <i class="fa-regular fa-bell" style="color: #64748b; font-size: 18px;"></i>
            <span id="notif-badge"></span>
            
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">Notifications</div>
                <div id="notif-content">
                    <p style="text-align:center; padding: 15px; color: #94a3b8; font-size: 12px;">No new updates</p>
                </div>
            </div>
        </div>
        
        <div class="user-info-btn" id="userProfileBtn">
            <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
            <img src="<?= $display_pic ?>" alt="Profile">
            <i class="fa fa-chevron-down" style="font-size: 10px; color: #94a3b8;"></i>
            
          <div class="profile-dropdown" id="userDropdown">
    <?php 
        // Logic: Kung ang user ay nasa loob na ng supplier_management, 
        // direkta na ang tawag sa profile.php. Kung wala, kailangan ng folder path.
        $profile_link = (strpos($_SERVER['PHP_SELF'], 'supplier_management/') !== false) 
                        ? "profile.php" 
                        : $base_path . "supplier_management/profile.php";
    ?>
    
    <a href="<?= $profile_link ?>"><i class="fa-regular fa-user"></i> My Profile</a>
    <a href="<?= $base_path ?>settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <div style="height: 1px; background: #f1f5f9; margin: 5px 0;"></div>
    <a href="<?= $base_path ?>logout.php" class="logout-link">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>
        </div>
    </div>
</header>

<script>
    // Real-time Notification Fetcher
    function checkNotifications() {
        const fetchUrl = '<?= $base_path ?>supplier_management/fetch_notifications.php';
        
        fetch(fetchUrl)
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                const content = document.getElementById('notif-content');
                
                if (data.unread_count > 0) {
                    badge.style.display = 'block';
                    content.innerHTML = `
                        <a href="<?= $base_path ?>supplier_management/suppliers.php" class="notif-item">
                            <i class="fa-solid fa-circle-exclamation" style="color: var(--accent-orange); margin-right: 8px;"></i>
                            You have <strong>${data.unread_count}</strong> pending suppliers to review.
                        </a>
                    `;
                } else {
                    badge.style.display = 'none';
                    content.innerHTML = '<p style="text-align:center; padding: 15px; color: #94a3b8; font-size: 12px;">No new updates</p>';
                }
            })
            .catch(error => console.error('Notification Error:', error));
    }

    // Run immediately and then every 10s
    checkNotifications();
    setInterval(checkNotifications, 10000);

    // Dropdown Logic (Multi-dropdown support)
    const profileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('userDropdown');
    const notifBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');

    function closeAllDropdowns() {
        profileDropdown.style.display = 'none';
        notifDropdown.style.display = 'none';
    }

    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = profileDropdown.style.display === 'block';
        closeAllDropdowns();
        profileDropdown.style.display = isVisible ? 'none' : 'block';
    });

    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = notifDropdown.style.display === 'block';
        closeAllDropdowns();
        notifDropdown.style.display = isVisible ? 'none' : 'block';
    });

    document.addEventListener('click', closeAllDropdowns);

    // Global Search Logic
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelectorAll("tbody tr");
            rows.forEach(row => {
                if(!row.classList.contains('no-filter')) {
                    let text = row.textContent.toUpperCase();
                    row.style.display = text.indexOf(filter) > -1 ? "" : "none";
                }
            });
        });
    }
</script>