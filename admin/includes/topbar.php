<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalFlair | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* --- UPDATED COLOR PALETTE (Orders Style) --- */
            --primary-accent: #e95a24; 
            --topbar-bg: rgba(255, 245, 240, 0.9); /* Soft Peach with Transparency */
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --sidebar-width: 260px;
        }

        /* --- BASIC SETUP --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        /* --- STICKY TOPBAR STYLES --- */
        .topbar {
            position: fixed;
            top: 15px;
            left: calc(var(--sidebar-width) + 20px);
            right: 20px;
            height: 70px;
            background: var(--topbar-bg);
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 25px;
            z-index: 1000;
            
            /* Glassmorphism Effect */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            
            /* Borders & Shadows */
            border: 1px solid rgba(255, 237, 213, 0.7);
            box-shadow: 0 10px 25px rgba(233, 90, 36, 0.08);
            
            /* Smooth Transition for Sticky Effect */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* --- SEARCH BOX --- */
        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            padding: 10px 18px;
            border-radius: 14px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #fed7aa;
        }

        .search-box input {
            border: none; outline: none; background: transparent; width: 100%; font-size: 14px; color: var(--text-dark);
        }

        .top-right { display: flex; align-items: center; gap: 15px; }

        /* --- ICONS & BUTTONS --- */
        .top-icon-btn {
            position: relative;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            transition: 0.2s;
        }
        .top-icon-btn:hover { color: var(--primary-accent); background: #fffaf5; }

        /* --- UPDATED NOTIF BADGE --- */
.notif-badge {
    position: absolute;
    top: -2px;      /* Adjust pwesto */
    right: -2px;    /* Adjust pwesto */
    background: #ef4444; /* Red color */
    color: white;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    padding: 2px;
    border-radius: 50%;
    display: none;  /* Hidden pag 0 */
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff; /* Para lutang ang kulay */
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

        /* --- ADMIN PROFILE PILL --- */
        .admin-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 6px 14px;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid #f1f5f9;
        }
        .admin-pill:hover { border-color: #fed7aa; background: #fffaf5; }
        .admin-pill img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--primary-accent); }
        .admin-pill span { font-weight: 700; font-size: 14px; color: var(--text-dark); }

        /* --- DROPDOWN MENUS --- */
        .dropdown-menu {
            position: absolute;
            top: 75px; right: 0;
            background: white;
            min-width: 280px;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid #f1f5f9;
            display: none;
            overflow: hidden;
            z-index: 1001;
        }
        .dropdown-menu.show { display: block; animation: fadeIn 0.25s ease; }

        .dropdown-menu a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 20px; text-decoration: none; color: var(--text-dark); font-size: 14px;
        }
        .dropdown-menu a:hover { background: #fff7ed; color: var(--primary-accent); }
        .dropdown-divider { height: 1px; background: #f1f5f9; margin: 5px 0; }
        .logout-link:hover { background: #fef2f2 !important; color: #ef4444 !important; }

        /* NOTIFICATION BOX STYLES */
        .notif-header {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fffcf9;
        }
        .badge-count {
            background: var(--primary-accent);
            color: white;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
        }
        .notif-item {
            padding: 14px 15px;
            border-bottom: 1px solid #f8fafc;
            cursor: pointer;
            transition: 0.2s;
        }
        .notif-item:hover { background: #fff7ed; }
        .notif-item b { font-size: 13px; color: var(--text-dark); display: block; margin-bottom: 2px; }
        .notif-item p { font-size: 11px; color: var(--text-muted); line-height: 1.4; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <header class="topbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass" style="color: var(--primary-accent);"></i>
            <input type="text" id="dirSearch" placeholder="Search products, category, or province...">
        </div>

        <div class="top-right">
            <div style="position: relative;">
               <div class="top-icon-btn" id="notifBtn">
    <i class="fa-regular fa-bell"></i>
    <span id="mainNotifBadge" class="notif-badge">0</span>
</div>
                <div class="dropdown-menu" id="notifMenu" style="width: 320px;">
                    <div class="notif-header">
                        <p style="font-weight: 700; color: var(--text-dark);">Notifications</p>
                        <span id="notifCountBadge" class="badge-count">0</span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div id="notifListContainer" style="max-height: 380px; overflow-y: auto;">
                        </div>
                </div>
            </div>

            <div style="position: relative;">
                <div class="admin-pill" id="profileBtn">
                    <span>Admin</span>
                    <img src="images/profile-pic.jpg" alt="Admin" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=e95a24&color=fff'">
                    <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                </div>
                <div class="dropdown-menu" id="profileMenu">
                    <a href="profile.php"><i class="fa-regular fa-user"></i> My Profile</a>
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="/LocalFlair_2/admin/logout.php" class="logout-btn">
    <i class="fa-solid fa-right-from-bracket"></i> Logout
</a>
                </div>
            </div>
        </div>
    </header>

    <script>
        const profileBtn = document.getElementById('profileBtn');
        const profileMenu = document.getElementById('profileMenu');
        const notifBtn = document.getElementById('notifBtn');
        const notifMenu = document.getElementById('notifMenu');
        const topbar = document.querySelector('.topbar');

        let lastNotifCount = 0;

        // --- STICKY BEHAVIOR ---
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                topbar.style.top = '0px';
                topbar.style.left = 'var(--sidebar-width)';
                topbar.style.right = '0px';
                topbar.style.borderRadius = '0px';
                topbar.style.background = 'rgba(255, 245, 240, 0.98)';
                topbar.style.borderBottom = '1px solid #fed7aa';
            } else {
                topbar.style.top = '15px';
                topbar.style.left = 'calc(var(--sidebar-width) + 20px)';
                topbar.style.right = '20px';
                topbar.style.borderRadius = '20px';
                topbar.style.background = 'var(--topbar-bg)';
                topbar.style.border = '1px solid rgba(255, 237, 213, 0.7)';
            }
        });

        // --- REAL-TIME NOTIFICATIONS LOGIC ---
       function fetchNotifications() {
    fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notifListContainer');
            const mainBadge = document.getElementById('mainNotifBadge'); // Ang badge sa bell
            const menuBadge = document.getElementById('notifCountBadge'); // Ang badge sa loob ng menu
            
            // I-update ang numero sa parehong badges
            if(mainBadge) mainBadge.innerText = data.count;
            if(menuBadge) menuBadge.innerText = data.count;

            // SHOW/HIDE LOGIC
            if (data.count > 0) {
                mainBadge.style.display = 'flex'; // Ipakita kung may order
                
                let html = '';
                data.list.forEach(notif => {
                    html += `
                        <div class="notif-item" onclick="location.href='orders.php?id=${notif.id}'">
                            <b><i class="fa-solid fa-cart-shopping" style="color: var(--primary-accent); margin-right: 8px;"></i> ${notif.title}</b>
                            <p>${notif.desc}</p>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                mainBadge.style.display = 'none'; // Itago kung 0
                container.innerHTML = '<p style="font-size: 12px; color: #999; text-align: center; padding: 30px;">No new orders</p>';
            }
        })
        .catch(err => console.error('Notification Error:', err));
}

        fetchNotifications();
        setInterval(fetchNotifications, 10000);

        // --- TOGGLE MENUS ---
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifMenu.classList.remove('show');
            profileMenu.classList.toggle('show');
        });

        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.remove('show');
            notifMenu.classList.toggle('show');
        });

        document.addEventListener('click', () => {
            profileMenu.classList.remove('show');
            notifMenu.classList.remove('show');
        });

        
        
    </script>
</body>
</html>