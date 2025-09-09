<?php
include('DBconnect.php');
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header("Location: login.php");
    exit();
}

// Check user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify admin
$admin_check = "SELECT * FROM `admin` WHERE `Admin ID` = '$user_id'";
$admin_result = mysqli_query($conn, $admin_check);

if (mysqli_num_rows($admin_result) == 0) {
    header("Location: home.php");
    exit();
}

$message = '';
$message_type = '';


//post actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['post_id'])) {
        $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
        $action = $_POST['action'];
        
        switch ($action) {
            case 'approve':
                $update_query = "UPDATE post SET status = 'approved' WHERE `Post ID` = '$post_id'";
                if (mysqli_query($conn, $update_query)) {
                    $message = "Post approved successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error approving post: " . mysqli_error($conn);
                    $message_type = "error";
                }
                break;
                
            case 'reject':
                $update_query = "UPDATE post SET status = 'rejected' WHERE `Post ID` = '$post_id'";
                if (mysqli_query($conn, $update_query)) {
                    $message = "Post rejected successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error rejecting post: " . mysqli_error($conn);
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $delete_query = "DELETE FROM post WHERE `Post ID` = '$post_id'";
                if (mysqli_query($conn, $delete_query)) {
                    $message = "Post deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting post: " . mysqli_error($conn);
                    $message_type = "error";
                }
                break;
        }
    }
    
}

//filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 10;
$offset = ($page - 1) * $posts_per_page;

$where_conditions = [];

//status filtering
if ($status_filter != 'all') {
    $status_escaped = mysqli_real_escape_string($conn, $status_filter);
    $where_conditions[] = "(p.status = '$status_escaped' OR p.status = BINARY '$status_escaped')";
}

if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($conn, $search_query);
    $where_conditions[] = "(p.Title LIKE '%$search_escaped%' OR 
                          p.Content LIKE '%$search_escaped%' OR 
                          u.`First Name` LIKE '%$search_escaped%' OR 
                          u.`Last Name` LIKE '%$search_escaped%')";
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

//ORDER BY
$order_clause = '';
switch ($sort_by) {
    case 'oldest':
        $order_clause = 'ORDER BY p.`Date of Post` ASC';
        break;
    case 'author':
        $order_clause = 'ORDER BY u.`First Name`, u.`Last Name`';
        break;
    case 'status':
        $order_clause = 'ORDER BY p.status, p.`Date of Post` DESC';
        break;
    default:
        $order_clause = 'ORDER BY p.`Date of Post` DESC';
        break;
}

//total page
$count_query = "SELECT COUNT(*) as total FROM post p 
                JOIN user u ON p.`Ur ID` = u.`User ID` 
                $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_posts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

//user info
$posts_query = "SELECT p.*, u.`First Name`, u.`Last Name`, u.`User ID`,
                       CASE 
                           WHEN sp.`Student ID` IS NOT NULL THEN 'Student'
                           WHEN ap.`Alumni ID` IS NOT NULL THEN 'Alumni' 
                           WHEN ad.`Admin ID` IS NOT NULL THEN 'Admin'
                           ELSE 'User'
                       END as user_role
                FROM post p
                JOIN user u ON p.`Ur ID` = u.`User ID`
                LEFT JOIN `student profile` sp ON u.`User ID` = sp.`Student ID`
                LEFT JOIN `alumni profile` ap ON u.`User ID` = ap.`Alumni ID`
                LEFT JOIN `admin` ad ON u.`User ID` = ad.`Admin ID`
                $where_clause
                $order_clause
                LIMIT $offset, $posts_per_page";

$posts_result = mysqli_query($conn, $posts_query);

//statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN (status = 'pending' OR status = BINARY 'pending') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN (status = 'approved' OR status = BINARY 'approved') THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN (status = 'rejected' OR status = BINARY 'rejected') THEN 1 ELSE 0 END) as rejected
    FROM post";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

if (!$stats['total']) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-left h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .header-left p {
            color: #718096;
            font-size: 1.1rem;
        }

        .header-right {
            display: flex;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        .stat-total .stat-number { color: #667eea; }
        .stat-pending .stat-number { color: #ed8936; }
        .stat-approved .stat-number { color: #48bb78; }
        .stat-rejected .stat-number { color: #e53e3e; }

        .filters-section {
            background: #f7fafc;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .filter-input {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .posts-section {
            margin-top: 30px;
        }

        .posts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .posts-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }

        .posts-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .posts-table th,
        .posts-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .posts-table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .posts-table tr:hover {
            background: #f7fafc;
        }

        .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 5px;
        }

        .role-student {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .role-alumni {
            background: linear-gradient(135deg, #ed8936, #dd6b20);
            color: white;
        }

        .role-admin {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }

        .post-content {
            max-width: 300px;
            line-height: 1.5;
        }

        .post-content-full {
            max-width: 300px;
            line-height: 1.5;
        }

        .expand-btn {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 5px;
            text-decoration: underline;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-pending {
            background: #fed7aa;
            color: #ea580c;
        }

        .status-approved {
            background: #bbf7d0;
            color: #16a34a;
        }

        .status-rejected {
            background: #fecaca;
            color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: #f7fafc;
            color: #4a5568;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: #667eea;
            color: white;
        }

        .pagination .disabled {
            background: #f7fafc;
            color: #a0aec0;
            cursor: not-allowed;
        }

        .no-posts {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-posts i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-posts h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .post-details {
            margin-bottom: 10px;
        }

        .post-title {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .post-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 5px;
        }

        .type-general { background: #e2e8f0; color: #4a5568; }
        .type-academic { background: #bee3f8; color: #2b6cb0; }
        .type-career { background: #c6f6d5; color: #2f855a; }
        .type-event { background: #fed7d7; color: #c53030; }
        .type-announcement { background: #fef5e7; color: #d69e2e; }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .header-right {
                width: 100%;
                justify-content: center;
            }

            .content {
                padding: 25px 20px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .posts-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .posts-header {
                flex-direction: column;
                align-items: stretch;
            }

        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-clipboard-list"></i> Manage Posts</h1>
                <p>Review and moderate user posts</p>
            </div>
            <div class="header-right">
                <a href="home.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card stat-pending">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card stat-approved">
                    <div class="stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card stat-rejected">
                    <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label class="filter-label">Status Filter</label>
                            <select name="status" class="filter-input">
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending Only</option>
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Posts</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="filter-input" placeholder="Search posts or authors..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Sort By</label>
                            <select name="sort" class="filter-input">
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort_by == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="author" <?php echo $sort_by == 'author' ? 'selected' : ''; ?>>Author Name</option>
                                <option value="status" <?php echo $sort_by == 'status' ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Posts Section -->
                <?php if (mysqli_num_rows($posts_result) > 0): ?>
                    <table class="posts-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAll" onchange="toggleAll()">
                                </th>
                                <th>Post Details</th>
                                <th>Author</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = mysqli_fetch_assoc($posts_result)): 
                                $content_preview = strlen($post['Content']) > 150 ? substr($post['Content'], 0, 150) . '...' : $post['Content'];
                            ?>
                                <tr>
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="selected_posts[]" value="<?php echo $post['Post ID']; ?>" form="bulkForm" class="post-checkbox">
                                    </td>
                                    <td>
                                        <div class="post-details">
                                            <div class="post-title"><?php echo htmlspecialchars($post['Title']); ?></div>
                                            <div class="post-content">
                                                <?php echo htmlspecialchars($content_preview); ?>
                                            </div>
                                            <?php if (!empty($post['File URL'])): ?>
                                                <div style="margin-top: 8px;">
                                                    <i class="fas fa-paperclip"></i>
                                                    <a href="<?php echo htmlspecialchars($post['File URL']); ?>" target="_blank" style="color: #667eea; text-decoration: none;">
                                                        View Attachment
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($post['First Name'] . ' ' . $post['Last Name']); ?></strong>
                                            <div class="role-badge role-<?php echo strtolower($post['user_role']); ?>">
                                                <i class="fas fa-<?php echo $post['user_role'] == 'Student' ? 'graduation-cap' : ($post['user_role'] == 'Alumni' ? 'user-graduate' : 'user-shield'); ?>"></i>
                                                <?php echo $post['user_role']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="post-type-badge type-<?php echo strtolower($post['Type']); ?>">
                                            <?php echo ucfirst($post['Type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $post['status']; ?>">
                                            <i class="fas fa-<?php echo $post['status'] == 'pending' ? 'clock' : ($post['status'] == 'approved' ? 'check' : 'times'); ?>"></i>
                                            <?php echo ucfirst($post['status'] ?: 'pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;">
                                            <?php echo date('M j, Y', strtotime($post['Date of Post'])); ?>
                                            <div style="color: #718096; font-size: 0.8rem;">
                                                <?php echo date('g:i A', strtotime($post['Date of Post'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($post['status'] == 'pending' || empty($post['status'])): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this post?')">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['Post ID']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-secondary btn-sm">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this post?')">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['Post ID']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?')">
                                                <input type="hidden" name="post_id" value="<?php echo $post['Post ID']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php
                            $base_url = "?status=" . urlencode($status_filter) . "&search=" . urlencode($search_query) . "&sort=" . urlencode($sort_by);
                            
                            if ($page > 1): ?>
                                <a href="<?php echo $base_url; ?>&page=1">First</a>
                                <a href="<?php echo $base_url; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo $base_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo $base_url; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                <a href="<?php echo $base_url; ?>&page=<?php echo $total_pages; ?>">Last</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-posts">
                        <i class="fas fa-inbox"></i>
                        <h3>No posts found</h3>
                        <p>There are no posts matching your current filters.</p>
                        <?php if ($status_filter == 'pending'): ?>
                            <p>Try creating some posts as a student or alumni user first, then return here to approve them.</p>
                            <a href="?status=all" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-eye"></i> Show All Posts
                            </a>
                        <?php else: ?>
                            <a href="?status=pending" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-clock"></i> Show Pending Posts
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>

        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.post-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
    </script>
</body>
</html>