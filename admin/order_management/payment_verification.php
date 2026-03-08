<?php
session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'User';

$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Counts for Statistics
$countTotal = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$countUnpaid = $conn->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'Unpaid'")->fetch_assoc()['total'];
$countPending = $conn->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'Pending Verification'")->fetch_assoc()['total'];
$countPaid = $conn->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'Paid'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Verification | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg-light: #fdfaf7; --accent: #e07a5f; --text-dark: #3d3d3d; --border: #f0e6dd; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); margin: 0; color: var(--text-dark); }
        .main { margin-left: 260px; padding: 100px 40px 80px; }
        .page-title h2 { margin: 0; font-size: 24px; color: #4a2c1d; }

        .order-status-tabs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .status-tab { background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--border); text-align: center; cursor: pointer; transition: 0.3s; position: relative; }
        .status-tab.active { border-color: var(--accent); background: #fffcfb; }
        .status-tab.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 40%; height: 3px; background: var(--accent); border-radius: 10px 10px 0 0; }
        .status-tab h4 { margin: 0; font-size: 11px; text-transform: uppercase; color: #999; }
        .status-tab .count { display: block; margin-top: 8px; font-size: 22px; font-weight: 700; }

        .order-container { background: #fff; border-radius: 15px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #faf8f6; padding: 15px; font-size: 11px; text-transform: uppercase; color: #888; border-bottom: 1px solid var(--border); text-align: left; }
        td { padding: 16px 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
        
        .method-tag { background: #f0f0f0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; }
        .amount-text { font-weight: 700; color: #333; }
        .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-Paid { background: #ecfdf5; color: #059669; }
        .status-Unpaid { background: #fef2f2; color: #dc2626; }
        .status-PendingVerification { background: #fff4e5; color: #d48307; }

        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #eee; background: #fff; cursor: pointer; transition: 0.2s; color: #777; margin-right: 5px; }
        .action-btn:hover { background: var(--accent); color: #fff; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="page-title">
        <h2>Payment Verification</h2>
        <p>Welcome, <?= htmlspecialchars($current_user) ?>! I-verify ang mga payments ng mga customer.</p>
    </div>

    <section class="order-status-tabs">
        <div class="status-tab active" data-filter="all">
            <h4>All Payments</h4>
            <span class="count"><?= $countTotal ?></span>
        </div>
        <div class="status-tab" data-filter="Pending Verification">
            <h4>For Verification</h4>
            <span class="count"><?= $countPending ?></span>
        </div>
        <div class="status-tab" data-filter="Unpaid">
            <h4>Unpaid / COD</h4>
            <span class="count"><?= $countUnpaid ?></span>
        </div>
        <div class="status-tab" data-filter="Paid">
            <h4>Confirmed Paid</h4>
            <span class="count"><?= $countPaid ?></span>
        </div>
    </section>

    <div class="order-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Payment Method</th>
                    <th>Amount Due</th>
                    <th>Payment Status</th>
                    <th>Transaction Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="paymentTableBody">
                </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.status-tab');

    function loadPayments(status) {
        fetch(`fetch_payments.php?status=${encodeURIComponent(status)}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('paymentTableBody').innerHTML = data;
                attachPaymentActions();
            });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            loadPayments(this.getAttribute('data-filter'));
        });
    });

    // After rows inserted attach event handlers
    function attachPaymentActions() {
        document.querySelectorAll('.view-receipt-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                window.open('receipt.php?order_id=' + id, '_blank');
            });
        });
        document.querySelectorAll('.verify-payment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                if (confirm('Mark payment as verified?')) {
                    fetch('payment_verification_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: 'action=verify&id=' + encodeURIComponent(id)
                    }).then(r=>r.json()).then(resp=>{
                        if(resp.success) loadPayments(document.querySelector('.status-tab.active').getAttribute('data-filter'));
                        else alert('Verification failed');
                    });
                }
            });
        });
    }

    // Initial Load
    loadPayments('all');
});
function attachPaymentActions() {
    // 1. MODERN VIEW RECEIPT (Image Preview)
    document.querySelectorAll('.view-receipt-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const receiptFile = this.dataset.receipt;
            if (!receiptFile) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'No receipt uploaded for this order.',
                    confirmButtonColor: '#e07a5f'
                });
                return;
            }

            Swal.fire({
                title: 'Payment Verification',
                text: 'Suriing mabuti ang reference number at halaga.',
                imageUrl: `uploads/receipts/${receiptFile}`,
                imageAlt: 'Customer Receipt',
                showCloseButton: true,
                confirmButtonText: 'Understood',
                confirmButtonColor: '#3d3d3d',
                customClass: {
                    popup: 'modern-swal-popup'
                }
            });
        });
    });

    // 2. MODERN VERIFY CONFIRMATION
    document.querySelectorAll('.verify-payment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            
            Swal.fire({
                title: 'Verify This Payment?',
                text: "Sigurado ka bang tama ang natanggap na bayad?",
                icon: 'question',
                iconColor: '#e07a5f',
                showCancelButton: true,
                confirmButtonColor: '#059669', // Green for Success
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fa fa-check"></i> Yes, Confirm it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                showLoaderOnConfirm: true, // Magpapakita ng loading spinner
                preConfirm: () => {
                    // Dito gagawin ang actual na database update
                    return fetch('payment_verification_actions.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: 'action=verify&id=' + encodeURIComponent(id)
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    // Modern Success Toast
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Payment successfully verified!'
                    });
                    
                    // Reload table
                    loadPayments(document.querySelector('.status-tab.active').getAttribute('data-filter'));
                }
            });
        });
    });
}
</script>



</body>
</html>