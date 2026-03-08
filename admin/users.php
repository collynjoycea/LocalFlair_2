<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) as total_orders 
          FROM users u 
          ORDER BY first_name ASC, last_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalFlair | Users Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-orange: #f05a28;
            --bg-color: #f8fafc;
            --sidebar-width: 260px;
            --topbar-height: 80px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Background UI kagaya ng Orders.php */
        body { 
            background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%); 
            min-height: 100vh; 
            color: var(--text-dark);
            display: flex;
        }

        .main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: calc(var(--topbar-height) + 30px) 40px 40px 40px;
            transition: all 0.3s ease;
        }

        /* HEADER SECTION */
        .header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            margin-bottom: 30px; 
        }
        
        .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* SEARCH BAR SA RIGHT SIDE */
        .search-wrapper {
            position: relative;
            width: 350px;
        }

        .search-wrapper input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            outline: none;
            font-size: 14px;
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .search-wrapper input:focus {
            background: #fff;
            border-color: var(--primary-orange);
            box-shadow: 0 8px 20px rgba(240, 90, 40, 0.15);
        }

        .search-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* TABLE CONTAINER kagaya ng Orders.php */
        .table-container {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid #edf2f7;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left; padding: 20px 25px; background: #fcfdfe;
            color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; border-bottom: 1px solid #f1f5f9;
        }

        tbody td { padding: 18px 25px; border-bottom: 1px solid #f8fafc; font-size: 14px; vertical-align: middle; }
        tbody tr:hover td { background-color: #fcfdfe; }

        .user-name { font-weight: 700; color: var(--text-dark); }
        .user-email { color: var(--text-muted); font-size: 13px; }
        
        .order-badge {
            background: #f1f5f9;
            color: var(--text-dark);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 12px;
        }

        .status-pill { 
            padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; 
            text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;
            background: #ecfdf5; color: #059669; /* Default Active */
        }

        /* ACTIONS */
        .action-group { display: flex; gap: 8px; }
        .act-btn { 
            width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; 
            justify-content: center; text-decoration: none; transition: 0.2s; font-size: 14px;
            background: #fff5f2; color: var(--primary-orange);
        }
        .act-btn:hover { transform: translateY(-2px); background: var(--primary-orange); color: white; }

        @media (max-width: 992px) {
            .main { margin-left: 0; width: 100%; padding: 100px 20px 20px 20px; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="header-flex animate__animated animate__fadeIn">
        <div class="page-title">
            <h2>Customers</h2>
            <p>Manage and view your registered boutique members.</p>
        </div>

        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="userSearch" placeholder="Search name or email..." onkeyup="filterUsers()">
        </div>
    </div>

    <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <table id="userTable">
            <thead>
                <tr>
                    <th>Customer Details</th>
                    <th>Contact Number</th>
                    <th>Join Date</th>
                    <th style="text-align:center;">Total Orders</th>
                    <th>Account Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <div class="user-name"><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></div>
                                <div class="user-email"><?= htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td style="font-weight:600; color:var(--text-dark);"><?= htmlspecialchars($row['contact_number']); ?></td>
                            <td style="color:var(--text-muted);"><?= date("M d, Y", strtotime($row['created_at'])); ?></td>
                            <td style="text-align:center;">
                                <span class="order-badge"><?= $row['total_orders']; ?></span>
                            </td>
                            <td><span class="status-pill">Active</span></td>
                            <td style="text-align:right;">
                                <div class="action-group" style="justify-content: flex-end;">
                                    <a href="view_user.php?id=<?= $row['user_id'] ?>" class="act-btn" title="View Profile">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 80px 0;">
                            <i class="fa-solid fa-users-slash" style="font-size:40px; color:#e2e8f0; margin-bottom:15px; display:block;"></i>
                            <p style="color:var(--text-muted); font-weight:600;">No customers found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterUsers() {
    let input = document.getElementById('userSearch');
    let filter = input.value.toLowerCase();
    let table = document.getElementById('userTable');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let textContent = tr[i].textContent.toLowerCase();
        if (textContent.includes(filter)) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
</script>

</body>
</html>