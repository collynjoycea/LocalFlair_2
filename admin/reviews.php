<?php
session_start();
include('../db.php'); 

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 80px;
            --primary-orange: #f05a28;
        }

        body { 
            background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .main-content { 
            margin-left: var(--sidebar-width);
            padding: calc(var(--topbar-height) + 20px) 40px 40px 40px; 
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px; 
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        .stat-label { color: #64748b; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .stat-value { font-size: 2.4rem; font-weight: 800; color: #1e293b; margin: 5px 0; }

        .reviews-container { 
            background: white; 
            border-radius: 28px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.03); 
            margin-top: 30px;
            border: 1px solid #edf2f7;
        }
        
        .table thead th { 
            background: #fcfdfe; 
            color: #94a3b8; 
            font-weight: 700; 
            font-size: 0.75rem; 
            padding: 20px; 
            text-transform: uppercase;
        }

        .product-img { width: 50px; height: 50px; border-radius: 14px; object-fit: cover; }

        /* Status Colors */
        .status-pill { padding: 6px 14px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-published { background: #ecfdf5; color: #059669; }
        .status-pending { background: #fffbeb; color: #d97706; }
        .status-hidden { background: #f1f5f9; color: #475569; }

        .action-btn { width: 35px; height: 35px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: none; }
        .btn-status-toggle { background: #eff6ff; color: #3b82f6; }
        .btn-delete { background: #fef2f2; color: #ef4444; }

        @media (max-width: 992px) { .main-content { margin-left: 0; width: 100%; } }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php');?>

    <div class="main-content">
        <?php include('includes/topbar.php'); ?>

        <div class="container-fluid animate__animated animate__fadeIn">
            <div class="mb-5 mt-2">
                <h2 class="fw-bold text-dark">Reviews Management</h2>
                <p class="text-muted">Monitor and moderate customer feedback real-time.</p>
            </div>

            <div id="stats-section" class="row g-4 mb-5">
                <div class="col-12 text-center text-muted">Loading analytics...</div>
            </div>

            <div class="reviews-container overflow-hidden animate__animated animate__fadeInUp">
                <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Customer Feedback List</h5>
                    <div class="input-group" style="max-width: 350px;">
                        <span class="input-group-text bg-light border-0"><i class="fa fa-search text-muted"></i></span>
                        <input type="text" id="reviewSearch" class="form-control bg-light border-0" placeholder="Search product or customer...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reviews-table-body">
                            <tr><td colspan="6" class="text-center py-5 text-muted">Fetching reviews...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadReviews() {
            $.ajax({
                url: 'get_reviews_ajax.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Update Stats
                    $('#stats-section').html(`
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Total Reviews</div>
                                <div class="stat-value text-primary">${response.total}</div>
                                <small class="text-muted">Volume of feedback received</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Average Rating</div>
                                <div class="stat-value text-warning">${response.average} <i class="fas fa-star" style="font-size: 24px;"></i></div>
                                <small class="text-muted">${response.stars_summary}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-label">Positive Sentiment</div>
                                <div class="stat-value text-success">${response.pos_percent}%</div>
                                <div class="progress mt-2" style="height: 8px; border-radius: 10px;">
                                    <div class="progress-bar bg-success" style="width: ${response.pos_percent}%"></div>
                                </div>
                            </div>
                        </div>
                    `);

                    // Update Table
                    let rows = '';
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(function(row) {
                            rows += `
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="uploads/${row.p_img}" class="product-img me-3" onerror="this.src='../images/placeholder.jpg'">
                                            <div>
                                                <div class="fw-bold text-dark">${row.product_name}</div>
                                                <small class="text-muted">ID: #${row.product_id}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="fw-semibold">${row.first_name} ${row.last_name}</div></td>
                                    <td><div class="text-warning small">${row.stars_html}</div></td>
                                    <td class="text-muted" style="max-width: 250px;">
                                        <div class="text-truncate" title="${row.comment}">${row.comment}</div>
                                    </td>
                                    <td><span class="status-pill status-${row.status.toLowerCase()}">${row.status}</span></td>
                                    <td class="text-center">
                                        <button onclick="changeStatus(${row.review_id}, '${row.status}')" class="action-btn btn-status-toggle me-1" title="Toggle Status (Pending/Published/Hidden)">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button onclick="deleteReview(${row.review_id})" class="action-btn btn-delete" title="Delete Permanently">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="6" class="text-center py-5 text-muted">No reviews found.</td></tr>';
                    }
                    $('#reviews-table-body').html(rows);
                }
            });
        }

        // Action: Delete
        function deleteReview(id) {
            if(confirm('Are you sure you want to delete this review? This cannot be undone.')) {
                $.post('delete_review.php', { id: id }, function(res) {
                    if(res.trim() === 'success') loadReviews();
                });
            }
        }

        // Action: Change Status (Pending -> Published -> Hidden)
        function changeStatus(id, currentStatus) {
            let nextStatus = currentStatus === 'Pending' ? 'Published' : (currentStatus === 'Published' ? 'Hidden' : 'Pending');
            $.post('update_review_status.php', { id: id, status: nextStatus }, function(res) {
                if(res.trim() === 'success') loadReviews();
            });
        }

        $(document).ready(function() {
            loadReviews(); 
            setInterval(loadReviews, 5000); // 5 seconds auto-refresh

            $("#reviewSearch").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#reviews-table-body tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
</body>
</html>