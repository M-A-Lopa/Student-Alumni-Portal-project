<?php
include('DBconnect.php');
session_start();

//Logout 
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
    
    //goes back --> login page
    header("Location: login.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];

// details from the database
$query = "SELECT * FROM user WHERE `User ID` = '$user_id'";
$result = mysqli_query($conn, $query);

// Check if user exists
if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    echo "User not found.";
    exit();
}

$role = '';
$student_profile = null;
$alumni_profile = null;
$admin_profile = null;

//student profile
$query_student = "SELECT * FROM `student profile` WHERE `Student ID` = '$user_id'";
$result_student = mysqli_query($conn, $query_student);
if (mysqli_num_rows($result_student) > 0) {
    $role = 'student';
    $student_profile = mysqli_fetch_assoc($result_student);
}

//alumni profile
if (!$role) {
    $query_alumni = "SELECT * FROM `alumni profile` WHERE `Alumni ID` = '$user_id'";
    $result_alumni = mysqli_query($conn, $query_alumni);
    if (mysqli_num_rows($result_alumni) > 0) {
        $role = 'alumni';
        $alumni_profile = mysqli_fetch_assoc($result_alumni);
    }
}

//admin profile
if (!$role) {
    $query_admin = "SELECT * FROM `admin` WHERE `Admin ID` = '$user_id'";
    $result_admin = mysqli_query($conn, $query_admin);
    if (mysqli_num_rows($result_admin) > 0) {
        $role = 'admin';
        $admin_profile = mysqli_fetch_assoc($result_admin);
    }
}

if (!$role) {
    echo "Not found. Please contact administrator.";
    exit();
}

// Get bio
$user_bio = '';
if ($role == 'student' && $student_profile) {
    $user_bio = $student_profile['Bio'] ?? '';
} elseif ($role == 'alumni' && $alumni_profile) {
    $user_bio = $alumni_profile['Bio'] ?? '';
}

//bio update
if (isset($_POST['action']) && $_POST['action'] == 'update_bio' && isset($_POST['bio'])) {
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    
    if ($role == 'student') {
        $update_bio_query = "UPDATE `student profile` SET `Bio` = '$bio' WHERE `Student ID` = '$user_id'";
    } elseif ($role == 'alumni') {
        $update_bio_query = "UPDATE `alumni profile` SET `Bio` = '$bio' WHERE `Alumni ID` = '$user_id'";
    }
    
    if (isset($update_bio_query) && mysqli_query($conn, $update_bio_query)) {
        $success_message = "Bio updated successfully!";
        $user_bio = $bio;
    } else {
        $error_message = "Error updating bio.";
    }
}

//email table
$email_query = "SELECT `Email` FROM email WHERE `Us ID` = '$user_id'";
$email_result = mysqli_query($conn, $email_query);
$user_emails = [];
if ($email_result) {
    while ($email_row = mysqli_fetch_assoc($email_result)) {
        $user_emails[] = $email_row['Email'];
    }
}

//email updates
if (isset($_POST['action']) && $_POST['action'] == 'update_emails') {
    $delete_emails_query = "DELETE FROM email WHERE `Us ID` = '$user_id'";
    mysqli_query($conn, $delete_emails_query);
    
    if (isset($_POST['emails']) && is_array($_POST['emails'])) {
        $success_count = 0;
        foreach ($_POST['emails'] as $email) {
            $email = trim($email);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email_escaped = mysqli_real_escape_string($conn, $email);
                $insert_email_query = "INSERT INTO email (`Us ID`, `Email`) VALUES ('$user_id', '$email_escaped')";
                if (mysqli_query($conn, $insert_email_query)) {
                    $success_count++;
                }
            }
        }
        if ($success_count > 0) {
            $success_message = "Emails updated successfully! ($success_count email(s) saved)";
        } else {
            $error_message = "No valid emails were saved.";
        }
    } else {
        $error_message = "At least one email is required.";
    }
}

//mentorship request approval/disapproval (Alumni)
if (isset($_POST['mentorship_action']) && isset($_POST['request_id'])) {
    $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
    $action = $_POST['mentorship_action'];
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $update_mentorship_query = "UPDATE mentorship_requests SET status = '$status' WHERE id = '$request_id'";
    mysqli_query($conn, $update_mentorship_query);
}

//post approval/disapproval (Admin) 
if (isset($_POST['post_action']) && isset($_POST['post_id'])) {
    $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $action = $_POST['post_action'];
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $update_post_query = "UPDATE post SET status = '$status' WHERE `Post ID` = '$post_id'";
    mysqli_query($conn, $update_post_query);
    
    if (mysqli_affected_rows($conn) > 0) {
        $success_message = "Post has been " . $status . " successfully!";
    }
}

// If no emails found, add the primary email from user table as fallback
if (empty($user_emails) && !empty($user['Email'])) {
    $user_emails[] = $user['Email'];
}

// Initialize post stats
$post_stats = array('pending_posts' => 0, 'approved_posts' => 0, 'rejected_posts' => 0);

//post statistics 
if ($role == 'student' || $role == 'alumni') {
    $post_status_query = "SELECT 
                         SUM(IF(status = 'pending', 1, 0)) as pending_posts,
                         SUM(IF(status = 'approved', 1, 0)) as approved_posts,
                         SUM(IF(status = 'rejected', 1, 0)) as rejected_posts
                         FROM post WHERE `Ur ID` = '$user_id'";
    $post_status_result = mysqli_query($conn, $post_status_query);
    if ($post_status_result) {
        $post_stats = mysqli_fetch_assoc($post_status_result);

        $post_stats['pending_posts'] = $post_stats['pending_posts'] ?? 0;
        $post_stats['approved_posts'] = $post_stats['approved_posts'] ?? 0;
        $post_stats['rejected_posts'] = $post_stats['rejected_posts'] ?? 0;
    }
}

//mentorship requests (Alumni)
$mentorship_result = null;
if ($role == 'alumni') {
    $mentorship_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'mentorship_requests'");
    if (mysqli_num_rows($mentorship_table_check) > 0) {
        $mentorship_query = "SELECT mr.*, u.`First Name`, u.`Last Name` FROM mentorship_requests mr 
                            JOIN user u ON mr.student_id = u.`User ID`
                            WHERE mr.alumni_id = '$user_id' AND mr.status = 'pending'";
        $mentorship_result = mysqli_query($conn, $mentorship_query);
    }
}

//get pending posts (admin )
$pending_posts_result = null;
if ($role == 'admin') {
    $pending_posts_query = "SELECT p.*, u.`First Name`, u.`Last Name` FROM post p 
                           JOIN user u ON p.`Ur ID` = u.`User ID` 
                           WHERE p.status = 'pending'
                           ORDER BY p.`Date of Post` DESC";
    $pending_posts_result = mysqli_query($conn, $pending_posts_query);
}

//admin rank
function getAdminRankDescription($rank) {
    switch($rank) {
        case 1:
            return 'Admin (Full Access)';
        default:
            return 'Rank ' . $rank;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Dashboard</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #333;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header {
            background: linear-gradient(135deg, #2d3748, #4a5568);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="25" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .welcome-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            font-weight: bold;
            position: relative;
            z-index: 1;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .content {
            padding: 40px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .profile-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .profile-item {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .profile-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .profile-label {
            font-weight: 600;
            color: #4a5568;
            width: 140px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .profile-value {
            color: #2d3748;
            font-weight: 500;
            flex-grow: 1;
        }

        .profile-value a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .profile-value a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .contact-info {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-top: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .contact-item i {
            color: #667eea;
            width: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            padding: 20px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            margin-top: 30px;
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

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .bio-section {
            margin-top: 20px;
        }

        .bio-text {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            min-height: 60px;
        }

        .bio-form {
            display: none;
        }

        .bio-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .bio-textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .email-section {
            margin-top: 20px;
        }

        .email-display {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
        }

        .email-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
        }

        .email-form {
            display: none;
        }

        .email-input-container {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .email-input {
            flex-grow: 1;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-family: inherit;
        }

        .email-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .remove-email-btn {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 8px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .add-email-btn {
            background: #48bb78;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f0f7ff, #e8f2ff);
            border-radius: 12px;
            border: 2px solid #d1e7ff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4a6aff;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #5a7eb8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* NOTIFICATION CARD STYLES */
        .notification-card {
            background: linear-gradient(135deg, #e8f2ff, #f0f7ff);
            border: 2px solid #a8d8ff;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(168, 216, 255, 0.3);
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-weight: 600;
            color: #2d5aa0;
        }

        .notification-content {
            color: #4a5568;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .notification-meta {
            font-size: 0.85rem;
            color: #7085b0;
            margin-bottom: 15px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
        }

        .btn-approve {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #38a169, #2d7d5a);
            transform: translateY(-1px);
        }

        .btn-reject {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #c53030, #a02626);
            transform: translateY(-1px);
        }

        .request-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffb347);
            color: #2d3748;
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #a8a8a8);
            color: #2d3748;
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #b8860b);
            color: white;
        }

        .rank-default {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .post-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #ed8936;
        }

        .post-meta {
            font-size: 0.85rem;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .post-content {
            color: #2d3748;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin: 10px;
                border-radius: 20px;
            }

            .header {
                padding: 30px 20px;
            }

            .welcome-title {
                font-size: 1.8rem;
            }

            .content {
                padding: 30px 20px;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .profile-label {
                width: 120px;
                font-size: 0.85rem;
            }

            .profile-value {
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }

            .email-input-container {
                flex-direction: column;
                align-items: stretch;
            }

            .email-input {
                margin-bottom: 10px;
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
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

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #718096;
            font-style: italic;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="dashboard-container fade-in">
        <div class="header">
            <div class="profile-avatar pulse">
                <?php 
                $firstInitial = strtoupper(substr($user['First Name'], 0, 1));
                $lastInitial = strtoupper(substr($user['Last Name'], 0, 1));
                echo $firstInitial . $lastInitial; 
                ?>
            </div>
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['First Name']); ?>!</h1>
            <p class="welcome-subtitle">Here's your profile overview</p>
        </div>

        <div class="content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        Basic Information
                    </h3>
                    <div class="profile-item">
                        <span class="profile-label">Full Name:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($user['First Name'] . ' ' . $user['Last Name']); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">User ID:</span>
                        <span class="profile-value"><?php echo htmlspecialchars($user['User ID']); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Date of Birth:</span>
                        <span class="profile-value"><?php echo date('F j, Y', strtotime($user['Date of Birth'])); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Member Since:</span>
                        <span class="profile-value"><?php echo date('F j, Y', strtotime($user['Created at'])); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">Role:</span>
                        <span class="profile-value">
                            <span class="role-badge role-<?php echo $role; ?>">
                                <i class="fas fa-<?php 
                                    echo $role == 'student' ? 'graduation-cap' : 
                                        ($role == 'alumni' ? 'briefcase' : 'user-shield'); 
                                ?>"></i>
                                <?php echo ucfirst($role); ?>
                            </span>
                        </span>
                    </div>

                    <!-- Contact Information (Admin) -->
                    <?php if ($role == 'admin'): ?>
                    <div class="contact-info">
                        <h4 style="color: #2d3748; font-weight: 600; margin-bottom: 10px;">
                            <i class="fas fa-address-book"></i> Contact Information
                        </h4>
                        <?php if (!empty($admin_profile['Contact No'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($admin_profile['Contact No']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Email Section -->
                    <div class="email-section">
                        <div class="profile-item">
                            <span class="profile-label">Email(s):</span>
                            <span class="profile-value">
                                <button class="btn btn-secondary btn-sm" onclick="toggleEmailEdit()">
                                    <i class="fas fa-edit"></i> Edit Emails
                                </button>
                            </span>
                        </div>
                        <div class="email-display" id="emailDisplay">
                            <?php if (!empty($user_emails)): ?>
                                <?php foreach ($user_emails as $email): ?>
                                <div class="email-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($email); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="email-item">
                                    <i class="fas fa-exclamation-triangle" style="color: #ed8936;"></i>
                                    <span style="color: #718096; font-style: italic;">No email addresses added</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form class="email-form" id="emailForm" method="POST">
                            <div id="emailInputs">
                                <?php if (!empty($user_emails)): ?>
                                    <?php foreach ($user_emails as $index => $email): ?>
                                    <div class="email-input-container" data-email-index="<?php echo $index; ?>">
                                        <input type="email" class="email-input" name="emails[]" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter email address" required>
                                        <?php if ($index > 0): ?>
                                        <button type="button" class="remove-email-btn" onclick="removeEmailInput(this)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="email-input-container" data-email-index="0">
                                        <input type="email" class="email-input" name="emails[]" value="" placeholder="Enter email address" required>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="add-email-btn" onclick="addEmailInput()">
                                <i class="fas fa-plus"></i> Add Another Email
                            </button>
                            <div style="display: flex; gap: 10px;">
                                <input type="hidden" name="action" value="update_emails">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Save Emails
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="cancelEmailEdit()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Bio Section -->
                    <div class="bio-section">
                        <div class="profile-item">
                            <span class="profile-label">Bio:</span>
                            <span class="profile-value">
                                <?php if ($role == 'student' || $role == 'alumni'): ?>
                                    <button class="btn btn-secondary btn-sm" onclick="toggleBioEdit()">
                                        <i class="fas fa-edit"></i> Edit Bio
                                    </button>
                                <?php else: ?>
                                    <span style="color: #718096; font-style: italic;">Bio not available for admin users</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($role == 'student' || $role == 'alumni'): ?>
                        <div class="bio-display" id="bioDisplay">
                            <div class="bio-text">
                                <?php echo !empty($user_bio) ? htmlspecialchars($user_bio) : 'No bio added yet. Click "Edit Bio" to add one.'; ?>
                            </div>
                        </div>
                        <form class="bio-form" id="bioForm" method="POST">
                            <textarea class="bio-textarea" name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_bio); ?></textarea>
                            <div style="margin-top: 10px; display: flex; gap: 10px;">
                                <input type="hidden" name="action" value="update_bio">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="cancelBioEdit()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-section">
                    <?php if ($role == 'student'): ?>
                        <h3 class="section-title">
                            <div class="section-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            Student Profile
                        </h3>
                        <div class="profile-item">
                            <span class="profile-label">Semester:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($student_profile['Semester']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Department:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($student_profile['Department']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Enrollment Year:</span>
                            <span class="profile-value"><?php 
                                $enrollment_year = $student_profile['Enrollment Year'];
                                if (empty($enrollment_year) || $enrollment_year == '0000-00-00' || $enrollment_year == '0000' || $enrollment_year == '0') {
                                    echo 'Not specified';
                                } elseif (is_numeric($enrollment_year)) {
                                    echo htmlspecialchars($enrollment_year);
                                } elseif (strpos($enrollment_year, '-') !== false) {
                                    echo date('Y', strtotime($enrollment_year));
                                } else {
                                    echo htmlspecialchars($enrollment_year);
                                }
                            ?></span>
                        </div>
                    
                    <?php elseif ($role == 'alumni'): ?>
                        <h3 class="section-title">
                            <div class="section-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            Alumni Profile
                        </h3>
                        <div class="profile-item">
                            <span class="profile-label">Degree:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($alumni_profile['Degree']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Graduation Year:</span>
                            <span class="profile-value"><?php 
                                $graduation_year = $alumni_profile['Graduation Year'];
                                if (empty($graduation_year) || $graduation_year == '0000-00-00' || $graduation_year == '0000' || $graduation_year == '0') {
                                    echo 'Not specified';
                                } elseif (is_numeric($graduation_year)) {
                                    echo htmlspecialchars($graduation_year);
                                } elseif (strpos($graduation_year, '-') !== false) {
                                    echo date('Y', strtotime($graduation_year));
                                } else {
                                    echo htmlspecialchars($graduation_year);
                                }
                            ?></span>
                        </div>
                        <div class="profile-item">
                            <span class="profile-label">Current Job:</span>
                            <span class="profile-value"><?php echo htmlspecialchars($alumni_profile['Current Job']); ?></span>
                        </div>
                        <?php if (!empty($alumni_profile['LinkedIn Profile'])): ?>
                        <div class="profile-item">
                            <span class="profile-label">LinkedIn:</span>
                            <span class="profile-value">
                                <a href="<?php echo htmlspecialchars($alumni_profile['LinkedIn Profile']); ?>" target="_blank">
                                    <i class="fab fa-linkedin"></i> View Profile
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    
                    <?php elseif ($role == 'admin'): ?>
                        <h3 class="section-title">
                            <div class="section-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            Admin Profile
                        </h3>
                        <div class="profile-item">
                            <span class="profile-label">Admin Rank:</span>
                            <span class="profile-value">
                                <?php 
                                $rank = $admin_profile['Rank'];
                                $rankClass = 'rank-default';
                                if ($rank <= 3) {
                                    $rankClass = 'rank-' . $rank;
                                }
                                ?>
                                <span class="rank-badge <?php echo $rankClass; ?>">
                                    <i class="fas fa-star"></i>
                                    <?php echo getAdminRankDescription($rank); ?>
                                </span>
                            </span>
                        </div>
                        <?php if (!empty($admin_profile['Po ID'])): ?>
                        <div class="profile-item">
                            <span class="profile-label">Post Access:</span>
                            <span class="profile-value">
                                <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                                Has posting privileges
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Post Statistics (Students & Alumni) -->
                <?php if ($role == 'student' || $role == 'alumni'): ?>
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        Post Statistics
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $post_stats['pending_posts']; ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $post_stats['approved_posts']; ?></span>
                            <span class="stat-label">Approved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $post_stats['rejected_posts']; ?></span>
                            <span class="stat-label">Rejected</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($role == 'alumni' && $mentorship_result && mysqli_num_rows($mentorship_result) > 0): ?>
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        Pending Mentorship Requests
                    </h3>
                    <?php while ($request = mysqli_fetch_assoc($mentorship_result)): ?>
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-user-graduate"></i>
                                <strong><?php echo htmlspecialchars($request['First Name'] . ' ' . $request['Last Name']); ?></strong>
                            </div>
                            <div class="notification-content">
                                <?php echo htmlspecialchars($request['message']); ?>
                            </div>
                            <div class="notification-meta">
                                Requested on: <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </div>
                            <div class="notification-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="mentorship_action" value="approve" class="btn-approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="mentorship_action" value="reject" class="btn-reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php elseif ($role == 'alumni'): ?>
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        Mentorship Requests
                    </h3>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No pending mentorship requests</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($role == 'admin' && $pending_posts_result && mysqli_num_rows($pending_posts_result) > 0): ?>
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        Pending Post Approvals
                    </h3>
                    <?php while ($post = mysqli_fetch_assoc($pending_posts_result)): ?>
                        <div class="notification-card">
                            <div class="notification-header">
                                <i class="fas fa-file-alt"></i>
                                <strong><?php echo htmlspecialchars($post['Title']); ?></strong>
                            </div>
                            <div class="notification-content">
                                <?php echo nl2br(htmlspecialchars(substr($post['Content'], 0, 150))); ?><?php if (strlen($post['Content']) > 150) echo '...'; ?>
                            </div>
                            <div class="notification-meta">
                                By: <strong><?php echo htmlspecialchars($post['First Name'] . ' ' . $post['Last Name']); ?></strong> | 
                                Posted: <?php echo date('M j, Y g:i A', strtotime($post['Date of Post'])); ?>
                            </div>
                            <div class="notification-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="post_id" value="<?php echo $post['Post ID']; ?>">
                                    <button type="submit" name="post_action" value="approve" class="btn-approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="post_action" value="reject" class="btn-reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php elseif ($role == 'admin'): ?>
                <div class="profile-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        Post Management
                    </h3>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No posts pending approval</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <?php if ($role == 'student'): ?>
                    <a href="feed.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Feed
                    </a>
                    <a href="createpost.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Create Post
                    </a>
                    <a href="findmentor.php" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Find Mentors
                    </a>
                <?php elseif ($role == 'alumni'): ?>
                    <a href="feed.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Feed
                    </a>
                    <a href="createpost.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Create Post
                    </a>
                    <a href="mentorship.php" class="btn btn-secondary">
                        <i class="fas fa-handshake"></i> Mentorship
                    </a>
                <?php elseif ($role == 'admin'): ?>
                    <a href="feed.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Feed
                    </a>
                    <a href="createpost.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Create Post
                    </a>
                    <a href="managepost.php" class="btn btn-secondary">
                        <i class="fas fa-clipboard-list"></i> Manage Posts
                    </a>
                    <a href="manageevent.php" class="btn btn-secondary">
                        <i class="fas fa-calendar"></i> Manage Events
                    </a>
                <?php endif; ?>
                
                <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        let emailCounter = <?php echo count($user_emails) > 0 ? count($user_emails) : 1; ?>;

        function toggleBioEdit() {
            document.getElementById('bioDisplay').style.display = 'none';
            document.getElementById('bioForm').style.display = 'block';
            document.querySelector('.bio-textarea').focus();
        }

        function cancelBioEdit() {
            document.getElementById('bioDisplay').style.display = 'block';
            document.getElementById('bioForm').style.display = 'none';
        }

        function toggleEmailEdit() {
            document.getElementById('emailDisplay').style.display = 'none';
            document.getElementById('emailForm').style.display = 'block';
            
            const firstInput = document.querySelector('.email-input');
            if (firstInput) {
                firstInput.focus();
            }
        }

        function cancelEmailEdit() {
            document.getElementById('emailDisplay').style.display = 'block';
            document.getElementById('emailForm').style.display = 'none';
        }

        function addEmailInput() {
            const emailInputs = document.getElementById('emailInputs');
            const newContainer = document.createElement('div');
            newContainer.className = 'email-input-container';
            newContainer.setAttribute('data-email-index', emailCounter);
            
            newContainer.innerHTML = `
                <input type="email" class="email-input" name="emails[]" value="" placeholder="Enter email address" required>
                <button type="button" class="remove-email-btn" onclick="removeEmailInput(this)">
                    <i class="fas fa-trash"></i> Remove
                </button>
            `;
            
            emailInputs.appendChild(newContainer);
            emailCounter++;
            
            const newInput = newContainer.querySelector('.email-input');
            newInput.focus();
        }

        function removeEmailInput(button) {
            const container = button.parentElement;
            const emailInputs = document.getElementById('emailInputs');
            
            if (emailInputs.children.length > 1) {
                container.remove();
            } else {
                alert('At least one email address is required.');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.profile-section');
            sections.forEach((section, index) => {
                setTimeout(() => {
                    section.classList.add('fade-in');
                }, index * 100);
            });

            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            const emailForm = document.getElementById('emailForm');
            emailForm.addEventListener('submit', function(e) {
                const emailInputs = document.querySelectorAll('.email-input');
                let hasValidEmail = false;
                let emailAddresses = [];

                emailInputs.forEach(input => {
                    const email = input.value.trim();
                    if (email) {
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                            alert('Please enter valid email addresses.');
                            e.preventDefault();
                            input.focus();
                            return;
                        }
                        if (emailAddresses.includes(email.toLowerCase())) {
                            alert('Duplicate email addresses are not allowed.');
                            e.preventDefault();
                            input.focus();
                            return;
                        }
                        emailAddresses.push(email.toLowerCase());
                        hasValidEmail = true;
                    }
                });

                if (!hasValidEmail) {
                    alert('Please enter at least one valid email address.');
                    e.preventDefault();
                }
            });
        });

        document.querySelectorAll('form').forEach(form => {
            const buttons = form.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                if (button.name === 'mentorship_action' || button.name === 'post_action') {
                    button.addEventListener('click', function(e) {
                        const action = this.value;
                        const actionText = action === 'approve' ? 'approve' : 'reject';
                        const itemType = this.name === 'mentorship_action' ? 'mentorship request' : 'post';
                        
                        if (!confirm(`Are you sure you want to ${actionText} this ${itemType}?`)) {
                            e.preventDefault();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>