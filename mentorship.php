<?php
include('DBconnect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// if alumni
$alumni_check = "SELECT * FROM `alumni profile` WHERE `Alumni ID` = '$user_id'";
$alumni_result = mysqli_query($conn, $alumni_check);

// mentorship request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
        $action = $_POST['action'];
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        //request details
        $get_request_query = "SELECT * FROM mentorship_requests WHERE id = '$request_id' AND alumni_id = '$user_id'";
        $get_request_result = mysqli_query($conn, $get_request_query);
        
        if (mysqli_num_rows($get_request_result) > 0) {
            $request_data = mysqli_fetch_assoc($get_request_result);
            $student_id = $request_data['student_id'];
            
            $update_request_query = "UPDATE mentorship_requests SET status = '$status', updated_at = NOW() WHERE id = '$request_id'";
            
            if (mysqli_query($conn, $update_request_query)) {
                if ($status == 'approved') {                    
                }
                
                $_SESSION['message'] = "Mentorship request " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating mentorship request. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Request not found or access denied.";
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: mentorship.php");
        exit();
    }
}

$pending_requests = [];
$approved_requests = [];
$rejected_requests = [];

//requests
$pending_query = "SELECT mr.*, u.`First Name`, u.`Last Name`, sp.Department, sp.Semester 
                  FROM mentorship_requests mr 
                  JOIN user u ON mr.student_id = u.`User ID`
                  LEFT JOIN `student profile` sp ON mr.student_id = sp.`Student ID`
                  WHERE mr.alumni_id = '$user_id' AND mr.status = 'pending'
                  ORDER BY mr.created_at DESC";

$approved_query = "SELECT mr.*, u.`First Name`, u.`Last Name`, sp.Department, sp.Semester 
                   FROM mentorship_requests mr 
                   JOIN user u ON mr.student_id = u.`User ID`
                   LEFT JOIN `student profile` sp ON mr.student_id = sp.`Student ID`
                   WHERE mr.alumni_id = '$user_id' AND mr.status = 'approved'
                   ORDER BY mr.updated_at DESC";

$rejected_query = "SELECT mr.*, u.`First Name`, u.`Last Name`, sp.Department, sp.Semester 
                   FROM mentorship_requests mr 
                   JOIN user u ON mr.student_id = u.`User ID`
                   LEFT JOIN `student profile` sp ON mr.student_id = sp.`Student ID`
                   WHERE mr.alumni_id = '$user_id' AND mr.status = 'rejected'
                   ORDER BY mr.updated_at DESC";

$pending_result = mysqli_query($conn, $pending_query);
$approved_result = mysqli_query($conn, $approved_query);
$rejected_result = mysqli_query($conn, $rejected_query);

if ($pending_result) {
    while ($row = mysqli_fetch_assoc($pending_result)) {
        $pending_requests[] = $row;
    }
}

if ($approved_result) {
    while ($row = mysqli_fetch_assoc($approved_result)) {
        $approved_requests[] = $row;
    }
}

if ($rejected_result) {
    while ($row = mysqli_fetch_assoc($rejected_result)) {
        $rejected_requests[] = $row;
    }
}

//user details --> header
$user_query = "SELECT * FROM user WHERE `User ID` = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentorship Management - Alumni Portal</title>
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
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 20px;
        }

        .welcome-info {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            display: inline-block;
            font-weight: 600;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
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

        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .pending { color: #ed8936; }
        .approved { color: #48bb78; }
        .rejected { color: #e53e3e; }

        .request-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .student-info {
            flex-grow: 1;
        }

        .student-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .student-details {
            font-size: 0.95rem;
            color: #4a5568;
            margin-bottom: 3px;
        }

        .request-message {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin: 15px 0;
            font-style: italic;
            color: #4a5568;
        }

        .request-meta {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 10px;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-pending {
            background: #fef5e7;
            color: #d69e2e;
            border: 1px solid #f6e05e;
        }

        .status-approved {
            background: #f0fff4;
            color: #38a169;
            border: 1px solid #9ae6b4;
        }

        .status-rejected {
            background: #fed7d7;
            color: #e53e3e;
            border: 1px solid #fc8181;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1rem;
            line-height: 1.6;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #fc8181;
        }

        .tabs {
            display: flex;
            background: #f7fafc;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 25px;
        }

        .tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            border-radius: 12px;
            font-weight: 600;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
        }

        .tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .request-header {
                flex-direction: column;
                gap: 15px;
            }

            .request-actions {
                flex-direction: column;
            }

            .tabs {
                flex-direction: column;
            }

            .section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header fade-in">
            <h1><i class="fas fa-handshake"></i> Mentorship Management</h1>
            <p>Manage your mentorship requests and guide the next generation</p>
            <div class="welcome-info">
                <i class="fas fa-user-graduate"></i>
                Welcome, <?php echo htmlspecialchars($user['First Name'] . ' ' . $user['Last Name']); ?>!
            </div>
            <div class="nav-buttons">
                <a href="home.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
                <a href="feed.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Feed
                </a>
            </div>
        </div>

        <div class="section fade-in">
            <h2 class="section-title">
                <div class="section-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                Mentorship Overview
            </h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number pending"><?php echo count($pending_requests); ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number approved"><?php echo count($approved_requests); ?></div>
                    <div class="stat-label">Active Mentorships</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number rejected"><?php echo count($rejected_requests); ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <div class="section fade-in">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('pending')">
                    <i class="fas fa-clock"></i> Pending (<?php echo count($pending_requests); ?>)
                </button>
                <button class="tab" onclick="switchTab('approved')">
                    <i class="fas fa-check"></i> Active Mentorships (<?php echo count($approved_requests); ?>)
                </button>
                <button class="tab" onclick="switchTab('rejected')">
                    <i class="fas fa-times"></i> Rejected (<?php echo count($rejected_requests); ?>)
                </button>
            </div>

            <div id="pending-content" class="tab-content active">
                <?php if (!empty($pending_requests)): ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-info">
                                    <div class="student-name">
                                        <i class="fas fa-user-graduate"></i>
                                        <?php echo htmlspecialchars($request['First Name'] . ' ' . $request['Last Name']); ?>
                                    </div>
                                    <?php if (!empty($request['Department'])): ?>
                                        <div class="student-details">
                                            <i class="fas fa-building"></i> 
                                            <?php echo htmlspecialchars($request['Department']); ?>
                                            <?php if (!empty($request['Semester'])): ?>
                                                - <?php echo htmlspecialchars($request['Semester']); ?> Semester
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="student-details">
                                        <i class="fas fa-id-card"></i> 
                                        Student ID: <?php echo htmlspecialchars($request['student_id']); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-pending">
                                    <i class="fas fa-clock"></i> Pending
                                </div>
                            </div>

                            <?php if (!empty($request['message'])): ?>
                            <div class="request-message">
                                <strong>Message from student:</strong><br>
                                "<?php echo htmlspecialchars($request['message']); ?>"
                            </div>
                            <?php endif; ?>

                            <div class="request-meta">
                                <i class="fas fa-calendar"></i>
                                Request sent: <?php echo date('F j, Y \a\t g:i A', strtotime($request['created_at'])); ?>
                            </div>

                            <div class="request-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-secondary" 
                                            onclick="return confirm('Are you sure you want to approve this mentorship request?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to reject this mentorship request?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Pending Requests</h3>
                        <p>You currently have no pending mentorship requests. When students request your mentorship, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="approved-content" class="tab-content">
                <?php if (!empty($approved_requests)): ?>
                    <?php foreach ($approved_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-info">
                                    <div class="student-name">
                                        <i class="fas fa-user-graduate"></i>
                                        <?php echo htmlspecialchars($request['First Name'] . ' ' . $request['Last Name']); ?>
                                    </div>
                                    <?php if (!empty($request['Department'])): ?>
                                        <div class="student-details">
                                            <i class="fas fa-building"></i> 
                                            <?php echo htmlspecialchars($request['Department']); ?>
                                            <?php if (!empty($request['Semester'])): ?>
                                                - <?php echo htmlspecialchars($request['Semester']); ?> Semester
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="student-details">
                                        <i class="fas fa-id-card"></i> 
                                        Student ID: <?php echo htmlspecialchars($request['student_id']); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-approved">
                                    <i class="fas fa-check"></i> Active Mentorship
                                </div>
                            </div>

                            <?php if (!empty($request['message'])): ?>
                            <div class="request-message">
                                <strong>Original request message:</strong><br>
                                "<?php echo htmlspecialchars($request['message']); ?>"
                            </div>
                            <?php endif; ?>

                            <div class="request-meta">
                                <i class="fas fa-calendar"></i>
                                Mentorship started: <?php echo date('F j, Y', strtotime($request['updated_at'])); ?>
                                <br>
                                <i class="fas fa-clock"></i>
                                Originally requested: <?php echo date('F j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-handshake"></i>
                        <h3>No Active Mentorships</h3>
                        <p>You haven't approved any mentorship requests yet. Approved relationships will be displayed here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="rejected-content" class="tab-content">
                <?php if (!empty($rejected_requests)): ?>
                    <?php foreach ($rejected_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="student-info">
                                    <div class="student-name">
                                        <i class="fas fa-user-graduate"></i>
                                        <?php echo htmlspecialchars($request['First Name'] . ' ' . $request['Last Name']); ?>
                                    </div>
                                    <?php if (!empty($request['Department'])): ?>
                                        <div class="student-details">
                                            <i class="fas fa-building"></i> 
                                            <?php echo htmlspecialchars($request['Department']); ?>
                                            <?php if (!empty($request['Semester'])): ?>
                                                - <?php echo htmlspecialchars($request['Semester']); ?> Semester
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="student-details">
                                        <i class="fas fa-id-card"></i> 
                                        Student ID: <?php echo htmlspecialchars($request['student_id']); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-rejected">
                                    <i class="fas fa-times"></i> Rejected
                                </div>
                            </div>

                            <?php if (!empty($request['message'])): ?>
                            <div class="request-message">
                                <strong>Original request message:</strong><br>
                                "<?php echo htmlspecialchars($request['message']); ?>"
                            </div>
                            <?php endif; ?>

                            <div class="request-meta">
                                <i class="fas fa-calendar"></i>
                                Rejected on: <?php echo date('F j, Y', strtotime($request['updated_at'])); ?>
                                <br>
                                <i class="fas fa-clock"></i>
                                Originally requested: <?php echo date('F j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-ban"></i>
                        <h3>No Rejected Requests</h3>
                        <p>You haven't rejected any mentorship requests. Rejected requests will be shown here for your records.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            
            event.target.classList.add('active');
            document.getElementById(tabName + '-content').classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
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

            const sections = document.querySelectorAll('.fade-in');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.6s ease-out';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>
</html>