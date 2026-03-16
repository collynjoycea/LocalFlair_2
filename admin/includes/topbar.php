<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary-accent: #e95a24; 
    --topbar-bg: rgba(255, 245, 240, 0.9);
    --text-dark: #1e293b;
    --text-muted: #64748b;
    --white: #ffffff;
    --sidebar-width: 260px;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

.topbar{
    position:fixed;
    top:15px;
    left:calc(var(--sidebar-width) + 20px);
    right:20px;
    height:70px;
    background:var(--topbar-bg);
    border-radius:20px;

    display:flex;
    justify-content:flex-end; /* RIGHT ALIGN */
    align-items:center;

    padding:0 25px;
    z-index:1000;

    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);

    border:1px solid rgba(255,237,213,0.7);
    box-shadow:0 10px 25px rgba(233,90,36,0.08);

    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
}

.top-right{
    display:flex;
    align-items:center;
    gap:15px;
}

/* ICON BUTTON */

.top-icon-btn{
    position:relative;
    font-size:20px;
    color:var(--text-muted);
    cursor:pointer;
    padding:8px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ffffff;
    border-radius:12px;
    border:1px solid #f1f5f9;
    transition:0.2s;
}

.top-icon-btn:hover{
    color:var(--primary-accent);
    background:#fffaf5;
}

/* NOTIFICATION BADGE */

.notif-badge{
    position:absolute;
    top:-2px;
    right:-2px;
    background:#ef4444;
    color:white;
    font-size:10px;
    font-weight:700;
    min-width:18px;
    height:18px;
    padding:2px;
    border-radius:50%;
    display:none;
    align-items:center;
    justify-content:center;
    border:2px solid #ffffff;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}

/* ADMIN PROFILE */

.admin-pill{
    display:flex;
    align-items:center;
    gap:10px;
    background:#ffffff;
    padding:6px 14px;
    border-radius:14px;
    cursor:pointer;
    transition:0.3s;
    border:1px solid #f1f5f9;
}

.admin-pill:hover{
    border-color:#fed7aa;
    background:#fffaf5;
}

.admin-pill img{
    width:32px;
    height:32px;
    border-radius:50%;
    object-fit:cover;
    border:1.5px solid var(--primary-accent);
}

.admin-pill span{
    font-weight:700;
    font-size:14px;
    color:var(--text-dark);
}

/* DROPDOWN */

.dropdown-menu{
    position:absolute;
    top:75px;
    right:0;
    background:white;
    min-width:280px;
    border-radius:18px;
    box-shadow:0 15px 35px rgba(0,0,0,0.1);
    border:1px solid #f1f5f9;
    display:none;
    overflow:hidden;
    z-index:1001;
}

.dropdown-menu.show{
    display:block;
    animation:fadeIn 0.25s ease;
}

.dropdown-menu a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 20px;
    text-decoration:none;
    color:var(--text-dark);
    font-size:14px;
}

.dropdown-menu a:hover{
    background:#fff7ed;
    color:var(--primary-accent);
}

.dropdown-divider{
    height:1px;
    background:#f1f5f9;
    margin:5px 0;
}

/* NOTIFICATIONS */

.notif-header{
    padding:15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#fffcf9;
}

.badge-count{
    background:var(--primary-accent);
    color:white;
    font-size:11px;
    padding:3px 10px;
    border-radius:20px;
    font-weight:700;
}

.notif-item{
    padding:14px 15px;
    border-bottom:1px solid #f8fafc;
    cursor:pointer;
}

.notif-item:hover{
    background:#fff7ed;
}

.notif-item b{
    font-size:13px;
    color:var(--text-dark);
    display:block;
}

.notif-item p{
    font-size:11px;
    color:var(--text-muted);
}

@keyframes fadeIn{
from{opacity:0; transform:translateY(-12px);}
to{opacity:1; transform:translateY(0);}
}
</style>
</head>

<body>

<header class="topbar">

<div class="top-right">

<!-- NOTIFICATION -->
<div style="position:relative;">
<div class="top-icon-btn" id="notifBtn">
<i class="fa-regular fa-bell"></i>
<span id="mainNotifBadge" class="notif-badge">0</span>
</div>

<div class="dropdown-menu" id="notifMenu" style="width:320px;">
<div class="notif-header">
<p style="font-weight:700;color:var(--text-dark);">Notifications</p>
<span id="notifCountBadge" class="badge-count">0</span>
</div>

<div class="dropdown-divider"></div>

<div id="notifListContainer" style="max-height:380px;overflow-y:auto;">
</div>
</div>
</div>

<!-- PROFILE -->
<div style="position:relative;">
<div class="admin-pill" id="profileBtn">
<span>Admin</span>
<img src="images/profile-pic.jpg"
onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=e95a24&color=fff'">
<i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--text-muted);"></i>
</div>

<div class="dropdown-menu" id="profileMenu">
<a href="profile.php"><i class="fa-regular fa-user"></i> My Profile</a>
<a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
<div class="dropdown-divider"></div>
<a href="/LocalFlair_2/admin/logout.php">
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

profileBtn.addEventListener('click',(e)=>{
e.stopPropagation();
notifMenu.classList.remove('show');
profileMenu.classList.toggle('show');
});

notifBtn.addEventListener('click',(e)=>{
e.stopPropagation();
profileMenu.classList.remove('show');
notifMenu.classList.toggle('show');
});

document.addEventListener('click',()=>{
profileMenu.classList.remove('show');
notifMenu.classList.remove('show');
});

</script>

</body>
</html>