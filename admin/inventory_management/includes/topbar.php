<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$top_admin_name = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'Admin';
$session_pic = $_SESSION['admin_pic'] ?? $_SESSION['employee_pic'] ?? ''; 
$top_img_path = "../uploads/profile/";

$display_pic = !empty($session_pic) 
    ? ($top_img_path . $session_pic) 
    : "https://ui-avatars.com/api/?name=" . urlencode($top_admin_name) . "&background=e95a24&color=fff";
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
        padding: 0 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: var(--topbar-height);
        position: fixed;
        top: 0; left: var(--sidebar-width); right: 0;
        z-index: 1000;
        border-bottom: 1px solid #f1f5f9;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
    }

    .search-box {
        display: flex; align-items: center; gap: 12px;
        background: #f8fafc; padding: 10px 18px;
        border-radius: 14px; border: 1px solid #e2e8f0; width: 400px;
    }

    .search-box input { border: none; outline: none; background: transparent; width: 100%; font-size: 14px; font-weight: 500; }

    .top-right { display: flex; align-items: center; gap: 25px; }

    /* NOTIFICATION STYLES */
    .notif-container { position: relative; }
    
    .notification-icon {
        position: relative; font-size: 18px; color: #64748b;
        cursor: pointer; padding: 10px; border-radius: 12px;
        transition: 0.2s; background: #f8fafc; border: 1px solid #e2e8f0;
    }

    .notification-icon:hover { color: var(--accent-orange); background: #fff4f0; }

    .notif-badge {
        position: absolute; top: -5px; right: -5px;
        background: #ef4444; color: white; font-size: 10px;
        padding: 2px 6px; border-radius: 50%; border: 2px solid #fff;
        display: none; /* Hidden if 0 */
    }

    .notif-dropdown {
        display: none; position: absolute; top: 55px; right: 0;
        width: 300px; background: #fff; border: 1px solid #e2e8f0;
        border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 1100; overflow: hidden;
    }

    .notif-header { padding: 15px; border-bottom: 1px solid #f1f5f9; font-weight: 700; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; }
    .notif-list { max-height: 350px; overflow-y: auto; }
    .notif-item { padding: 15px; display: flex; gap: 12px; text-decoration: none; color: inherit; border-bottom: 1px solid #f8fafc; transition: 0.2s; }
    .notif-item:hover { background: #f8fafc; }
    .notif-item i { color: var(--accent-orange); font-size: 16px; margin-top: 3px; }
    .notif-item .n-info strong { display: block; font-size: 13px; color: var(--text-dark); }
    .notif-item .n-info span { font-size: 12px; color: var(--text-muted); }

    /* ADMIN PROFILE */
    .admin-info { display: flex; align-items: center; gap: 12px; padding: 6px 12px; border-radius: 12px; cursor: pointer; position: relative; }
    .admin-info img { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 1px solid #e2e8f0; }
    
    .profile-dropdown {
        display: none; position: absolute; top: 55px; right: 0; width: 180px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 15px;
        padding: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1001;
    }
    .profile-dropdown a { display: flex; align-items: center; gap: 10px; padding: 10px; text-decoration: none; color: var(--text-muted); font-size: 13px; border-radius: 10px; }
    .profile-dropdown a:hover { background: #fff4f0; color: var(--accent-orange); }
</style>

<header class="topbar">
    <div class="search-box">
        <i class="fa fa-search" style="color: #94a3b8;"></i>
        <input type="text" id="dirSearch" placeholder="Search products, category, province...">
    </div>

    <div class="top-right">
        <div class="notif-container">
            <div class="notification-icon" id="notifBell">
                <i class="fa-regular fa-bell"></i>
                <span class="notif-badge" id="notifCount">0</span>
            </div>
            
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <span id="notifLabel" style="font-size: 11px; background: #fff4f0; color: var(--accent-orange); padding: 2px 8px; border-radius: 20px;">0 New</span>
                </div>
                <div class="notif-list" id="notifList">
                    </div>
                <a href="low_stock_alert.php" style="display: block; text-align: center; padding: 12px; font-size: 12px; color: var(--accent-orange); font-weight: 600; text-decoration: none; background: #f8fafc;">View All Alerts</a>
            </div>
        </div>

        <div class="admin-info" id="adminProfile">
            <span class="admin-name"><?= htmlspecialchars($top_admin_name) ?></span>
            <img src="<?= $display_pic ?>" alt="Profile">
            <i class="fa fa-chevron-down" style="font-size: 10px; color: #94a3b8;"></i>
            
            <div class="profile-dropdown" id="pDropdown">
                <a href="profile.php"><i class="fa-regular fa-user"></i> My Profile</a>
                <a href="../settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <div style="height: 1px; background: #f1f5f9; margin: 5px 0;"></div>
                <a href="../logout.php" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
    // Toggle Logic
    const adminBtn = document.getElementById('adminProfile');
    const pDropdown = document.getElementById('pDropdown');
    const notifBell = document.getElementById('notifBell');
    const notifDropdown = document.getElementById('notifDropdown');

    adminBtn.onclick = (e) => {
        e.stopPropagation();
        pDropdown.style.display = pDropdown.style.display === 'block' ? 'none' : 'block';
        notifDropdown.style.display = 'none';
    };

    notifBell.onclick = (e) => {
        e.stopPropagation();
        notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
        pDropdown.style.display = 'none';
    };

    document.onclick = () => {
        pDropdown.style.display = 'none';
        notifDropdown.style.display = 'none';
    };

    // REAL-TIME NOTIFICATIONS FETCHING
    function updateNotifications() {
        fetch('fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                const countBadge = document.getElementById('notifCount');
                const notifLabel = document.getElementById('notifLabel');
                const notifList = document.getElementById('notifList');

                // Update Count
                if (data.count > 0) {
                    countBadge.innerText = data.count;
                    countBadge.style.display = 'block';
                    notifLabel.innerText = data.count + " New Alerts";
                } else {
                    countBadge.style.display = 'none';
                    notifLabel.innerText = "No New Alerts";
                }

                // Update List
                if (data.list.length > 0) {
                    notifList.innerHTML = data.list.map(item => `
                        <a href="low_stock_alert.php" class="notif-item">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <div class="n-info">
                                <strong>${item.title}</strong>
                                <span>${item.msg}</span>
                            </div>
                        </a>
                    `).join('');
                } else {
                    notifList.innerHTML = '<p style="text-align:center; padding: 20px; font-size: 12px; color: #94a3b8;">No low stock alerts.</p>';
                }
            })
            .catch(err => console.error('Notif Fetch Error:', err));
    }

    // Initial load and set interval (10 seconds)
    updateNotifications();
    setInterval(updateNotifications, 10000);

    // Search Logic (as requested)
    const mainSearch = document.getElementById('dirSearch');
    if (mainSearch) {
        mainSearch.onkeyup = function() {
            let filter = this.value.toUpperCase();
            document.querySelectorAll(".product-card, tbody tr").forEach(el => {
                if(!el.classList.contains('empty-state')) {
                    el.style.display = el.textContent.toUpperCase().includes(filter) ? "" : "none";
                }
            });
        };
    }
</script>