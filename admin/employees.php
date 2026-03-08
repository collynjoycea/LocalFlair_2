<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. FETCH FROM ADMINS TABLE (Dito nakalagay ang staff mo base sa SQL)
// Ine-exclude natin ang main 'Admin' para staff lang ang lumabas
$sql = "SELECT * FROM admins WHERE name != 'Admin' ORDER BY id DESC"; 
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalFlair | Employees</title>
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
        
        body { 
            background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%); 
            min-height: 100vh; 
            color: var(--text-dark); 
        }

        .main {
            margin-left: var(--sidebar-width);
            padding: calc(var(--topbar-height) + 30px) 40px 40px 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .header-flex { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }
        .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        .table-container {
            background: #fff; 
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03); 
            border: 1px solid #edf2f7;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        
        thead th {
            text-align: left; 
            padding: 20px 25px; 
            background: #fcfdfe;
            color: #94a3b8; 
            font-size: 11px; 
            font-weight: 800; 
            text-transform: uppercase;
            letter-spacing: 1px; 
            border-bottom: 1px solid #f1f5f9;
        }

        tbody td { 
            padding: 18px 25px; 
            border-bottom: 1px solid #f8fafc; 
            font-size: 14px; 
            vertical-align: middle; 
        }

        tbody tr:hover { background-color: #f8fafc; }

        .emp-id { font-weight: 800; color: var(--primary-orange); font-size: 13px; }
        .emp-name { font-weight: 700; color: var(--text-dark); }

        .status-pill { 
            padding: 6px 14px; 
            border-radius: 10px; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        .status-pill.active { background: #ecfdf5; color: #059669; }

        .actions button {
            border: none;
            background: #f1f5f9;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
            color: var(--text-dark);
            margin-right: 4px;
        }
        .actions button:hover { background: var(--primary-orange); color: white; }

        /* MODAL STYLING */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 900px;
            margin: 5vh auto;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .profile-head { display: flex; gap: 20px; align-items: center; }
        .avatar { width: 70px; height: 70px; border-radius: 20px; object-fit: cover; border: 2px solid #f1f5f9; }
        
        .badge.admin {
            background: #fff5f2; color: var(--primary-orange);
            padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 700;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #f1f5f9;
        }

        .card h4 { margin-bottom: 15px; color: var(--text-dark); font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .card p { font-size: 13px; margin-bottom: 8px; color: var(--text-muted); }
        .card b { color: var(--text-dark); }

        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }
        .btn.primary { background: var(--text-dark); color: white; }
        .btn.primary:hover { background: #000; }
        .btn.secondary { background: #f1f5f9; color: var(--text-dark); }
        
        input.edit {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            width: 100%;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="header-flex animate__animated animate__fadeIn">
        <div class="page-title">
            <h2>Employees Management</h2>
            <p>View and manage your boutique's staff and permissions.</p>
        </div>
        <button class="btn primary"><i class="fa-solid fa-plus"></i> Add Employee</button>
    </div>

    <div class="table-container animate__animated animate__fadeInUp">
        <table>
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Date Created</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="emp-id">#STF-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td class="emp-name"><?php echo htmlspecialchars(str_replace('_', ' ', $row['name'])); ?></td>
                        <td style="color: var(--text-muted);">Staff / Manager</td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td style="color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td><span class="status-pill active">Active</span></td>
                        <td class="actions">
                            <button onclick='openModal(<?php echo json_encode($row); ?>)'><i class="fa-solid fa-eye"></i></button>
                            <button><i class="fa-solid fa-pen-to-square"></i></button>
                            <button><i class="fa-solid fa-lock"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 20px;">No staff found in admins table.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="profileModal" class="modal">
    <div class="modal-content animate__animated animate__zoomIn animate__faster">
        <div class="modal-header">
            <div class="profile-head">
                <img src="" id="modal_avatar" class="avatar">
                <div>
                    <h2 id="modal_full_name" class="view">---</h2>
                    <input type="text" class="edit" id="edit_full_name" name="name" value="" hidden>
                    <span class="badge admin">Staff Member</span>
                </div>
            </div>
            <span style="cursor:pointer; font-size:24px; color:var(--text-muted)" onclick="closeModal()">&times;</span>
        </div>

        <div class="modal-grid">
            <div class="card">
                <h4><i class="fa-solid fa-user"></i> Account Info</h4>
                <p><b>Staff ID:</b> <span id="modal_emp_id">---</span></p>
                <p><b>Email:</b> <span id="modal_email">---</span></p>
            </div>

            <div class="card">
                <h4><i class="fa-solid fa-briefcase"></i> Employment</h4>
                <p><b>Position:</b> <span id="modal_role_text">---</span></p>
                <p><b>Status:</b> <span style="color:#059669">🟢 Active</span></p>
            </div>
        </div>

        <div class="modal-actions" style="margin-top:30px; display:flex; justify-content:flex-end; gap:10px;">
            <button class="btn secondary" onclick="closeModal()">Close</button>
            <button class="btn primary" onclick="enableEdit()">Edit Details</button>
            <button class="btn primary edit" onclick="saveChanges()" hidden style="background:#059669">Save Changes</button>
        </div>
    </div>
</div>

<script>
    function openModal(data) {
        // Clean the name (remove underscores for display)
        const cleanName = data.name.replace(/_/g, ' ');
        
        document.getElementById("modal_full_name").textContent = cleanName;
        document.getElementById("edit_full_name").value = data.name;
        document.getElementById("modal_role_text").textContent = cleanName;
        document.getElementById("modal_emp_id").textContent = "STF-" + data.id.toString().padStart(4, '0');
        document.getElementById("modal_email").textContent = data.email;
        
        // Auto-generate avatar based on name
        document.getElementById("modal_avatar").src = "https://ui-avatars.com/api/?background=f05a28&color=fff&name=" + cleanName;

        document.getElementById("profileModal").style.display = "block";
    }

    function closeModal() { 
        document.getElementById("profileModal").style.display = "none"; 
        resetEditMode(); 
    }

    function enableEdit() {
        document.querySelectorAll('.view').forEach(el => el.hidden = true);
        document.querySelectorAll('.edit').forEach(el => el.hidden = false);
        document.querySelector('.btn.primary').hidden = true;
        document.querySelector('.btn.primary.edit').hidden = false;
    }

    function resetEditMode() {
        document.querySelectorAll('.view').forEach(el => el.hidden = false);
        document.querySelectorAll('.edit').forEach(el => el.hidden = true);
        document.querySelector('.btn.primary').hidden = false;
        document.querySelector('.btn.primary.edit').hidden = true;
    }

    window.onclick = function(e) { if (e.target.className === 'modal') closeModal(); };
</script>

</body>
</html>