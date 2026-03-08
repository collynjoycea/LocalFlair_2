
<style>
    /* TOP BAR CONTAINER */
    .topbar {
        background: linear-gradient(135deg, #fde6d8, #f9d3b4);
        padding: 12px 35px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px;
        position: fixed;
        top: 20px;           
        left: 260px;     
        right: 20px;          
        z-index: 999;         
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
    }

    /* SEARCH BAR */
    .search-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f4f4f4;
        padding: 8px 12px;
        border-radius: 12px;
        border: 1px solid #aba385;
    }

    .search-box input {
        border: none;
        outline: none;
        background: transparent;
        width: 350px;
        font-size: 14px;
    }

    .search-btn {
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 18px;
    }

    /* RIGHT SIDE ICONS */
    .top-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification {
        position: relative;
        font-size: 20px;
        cursor: pointer;
    }

    .notif-dot {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 8px;
        height: 8px;
        background: red;
        border-radius: 50%;
    }

    .notif-dropdown {
        display: none;
        position: absolute;
        top: 25px;
        right: 0;
        width: 250px;
        background: white;
        border: 1px solid #ccc;
        padding: 10px;
        border-radius: 5px;
        z-index: 100;
    }

    .notification:hover .notif-dropdown {
        display: block;
    }

    .admin-info {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative; 
        cursor: pointer;
    }

    .admin-info img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
    }

    .profile-dropdown {
        display: none;
        position: absolute;
        top: 50px; 
        right: 0;
        width: 150px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 100;
    }

    .profile-dropdown a {
        display: block;
        padding: 10px;
        text-decoration: none;
        color: #333;
    }

    .profile-dropdown a:hover {
        background: #f2f2f2;
    }
    
    .profile-dropdown a.logout-link:hover {
        background: #fff0f0;
        color: #d9534f;
    }
    
    .dropdown-divider {
        height: 1px;
        background-color: #eee;
        margin: 4px 0;
    }
</style>

<header class="topbar">
    <div class="search-box">
        <input type="text" placeholder="Search...">
        <button class="search-btn">🔍</button>
    </div>

    <div class="top-right">
        <div class="notification">
            🔔 <span class="notif-dot"></span>
            <div class="notif-dropdown">
                <p><strong>Notifications</strong></p>
                <hr>
                <p>No new notifications</p>
            </div>
        </div>

        <div class="admin-info" id="adminProfile">
            <span class="admin-name">Admin</span>
            <img src="images/profile-pic.jpg" alt="Admin">
            <div class="profile-dropdown">
                <a href="profile.php"><i class="fa-regular fa-user"></i> Profile</a>
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <div class="dropdown-divider"></div>
               <a href="logout.php" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
               </a>
            </div>
        </div>
    </div>
</header>

<script>
    const adminProfile = document.getElementById('adminProfile');
    const profileDropdown = adminProfile.querySelector('.profile-dropdown');

    adminProfile.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.style.display = profileDropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!adminProfile.contains(e.target)) {
            profileDropdown.style.display = 'none';
        }
    });
</script>