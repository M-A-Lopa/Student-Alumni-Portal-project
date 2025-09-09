<?php
include('DBconnect.php');
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

//comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    $comment_text = mysqli_real_escape_string($conn, $_POST['comment_text']);
    $post_id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $comment_id = uniqid('comment_');
    
    $insert_comment = "INSERT INTO comment (`Comment ID`, `Date of Comment`, `Comment`, `Pos ID`, `Use ID`) 
                       VALUES ('$comment_id', NOW(), '$comment_text', '$post_id', '$user_id')";
    
    if (mysqli_query($conn, $insert_comment)) {
        $success_message = "Comment added successfully!";
    } else {
        $error_message = "Error adding comment.";
    }
}

//search
$search_query = '';
$search_condition = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_condition = "AND (p.Title LIKE '%$search_query%' OR p.Content LIKE '%$search_query%' OR u.`First Name` LIKE '%$search_query%' OR u.`Last Name` LIKE '%$search_query%')";
}

$type_filter = '';
$selected_type = '';

// Check role
$role = 'user';
$student_check = mysqli_query($conn, "SELECT * FROM `student profile` WHERE `Student ID` = '$user_id'");
$alumni_check = mysqli_query($conn, "SELECT * FROM `alumni profile` WHERE `Alumni ID` = '$user_id'");
$admin_check = mysqli_query($conn, "SELECT * FROM `admin` WHERE `Admin ID` = '$user_id'");

if (mysqli_num_rows($student_check) > 0) {
    $role = 'student';
} elseif (mysqli_num_rows($alumni_check) > 0) {
    $role = 'alumni';
} elseif (mysqli_num_rows($admin_check) > 0) {
    $role = 'admin';
}

// Page
$posts_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Count total posts
$count_query = "SELECT COUNT(*) as total FROM post p 
                JOIN user u ON p.`Ur ID` = u.`User ID` 
                WHERE (p.status = 'approved' OR p.status IS NULL)
                $search_condition";
$count_result = mysqli_query($conn, $count_query);
$total_posts = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

//posts --> database
$posts_query = "SELECT p.*, u.`First Name`, u.`Last Name`, 
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
                WHERE (p.status = 'approved' OR p.status IS NULL)
                $search_condition
                ORDER BY p.`Date of Post` DESC
                LIMIT $posts_per_page OFFSET $offset";

$posts_result = mysqli_query($conn, $posts_query);

//count comments
function countPostComments($conn, $post_id) {
    $count_query = "SELECT COUNT(*) as comment_count FROM comment WHERE `Pos ID` = '$post_id'";
    $result = mysqli_query($conn, $count_query);
    return mysqli_fetch_assoc($result)['comment_count'];
}

//get comments
function getPostComments($conn, $post_id) {
    $comments_query = "SELECT c.*, u.`First Name`, u.`Last Name`,
                              CASE 
                                  WHEN sp.`Student ID` IS NOT NULL THEN 'Student'
                                  WHEN ap.`Alumni ID` IS NOT NULL THEN 'Alumni' 
                                  WHEN ad.`Admin ID` IS NOT NULL THEN 'Admin'
                                  ELSE 'User'
                              END as user_role
                       FROM comment c 
                       JOIN user u ON c.`Use ID` = u.`User ID`
                       LEFT JOIN `student profile` sp ON u.`User ID` = sp.`Student ID`
                       LEFT JOIN `alumni profile` ap ON u.`User ID` = ap.`Alumni ID`
                       LEFT JOIN `admin` ad ON u.`User ID` = ad.`Admin ID`
                       WHERE c.`Pos ID` = '$post_id' 
                       ORDER BY c.`Date of Comment` ASC";
    return mysqli_query($conn, $comments_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - Student Alumni Portal</title>
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            color: #2d3748;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #2d3748, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .ticket-button-container {
            background: #a8d8ff;
            border: 5px solid #7fb8e6;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 8px 30px rgba(168, 216, 255, 0.4);
        }

        .ticket-btn {
            display: inline-block;
            background: #ffffff;
            color: #4a7ba7;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            border: 3px solid #7fb8e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ticket-btn:hover {
            background: #7fb8e6;
            color: #ffffff;
            transform: scale(1.05);
        }

        .post-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 2px;
        }

        .user-role {
            font-size: 0.85rem;
            padding: 3px 8px;
            border-radius: 12px;
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

        .post-meta {
            font-size: 0.85rem;
            color: #718096;
        }

        .post-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 10px;
        }

        .type-event {
            background: #fed7d7;
            color: #c53030;
        }

        .type-general {
            background: #e2e8f0;
            color: #4a5568;
        }

        .type-announcement {
            background: #d4edda;
            color: #155724;
        }

        .post-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .post-content {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .post-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            flex-wrap: wrap;
        }

        .comment-count {
            color: #718096;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .toggle-comments {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 5px 0;
            transition: color 0.3s ease;
        }

        .toggle-comments:hover { color: #764ba2; }

        .comments-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f1f5f9;
            display: none;
        }

        .comments-section.active { display: block; }

        .comment-form {
            margin-bottom: 20px;
            background: #f7fafc;
            padding: 15px;
            border-radius: 12px;
        }

        .comment-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .comment-textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .comment-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .comment {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .comment-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .comment-user {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .comment-date {
            color: #718096;
            font-size: 0.8rem;
            margin-left: auto;
        }

        .comment-text {
            color: #4a5568;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #2f855a;
        }

        .alert-error {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                min-width: 100%;
                margin-bottom: 10px;
            }

            .search-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="header fade-in">
            <h1>Community Feed</h1>
            <div class="header-actions">
                <a href="createpost.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Post
                </a>
                <a href="home.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        </div>

        <div class="search-section fade-in">
            <form class="search-form" method="GET" action="">
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search posts, users, or topics..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($posts_result) > 0): ?>
            <?php while ($post = mysqli_fetch_assoc($posts_result)): ?>
                <div class="post-card fade-in">
                    <div class="post-header">
                        <div class="user-avatar">
                            <?php 
                            $firstInitial = strtoupper(substr($post['First Name'], 0, 1));
                            $lastInitial = strtoupper(substr($post['Last Name'], 0, 1));
                            echo $firstInitial . $lastInitial; 
                            ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name">
                                <?php echo htmlspecialchars($post['First Name'] . ' ' . $post['Last Name']); ?>
                                <span class="user-role role-<?php echo strtolower($post['user_role']); ?>">
                                    <?php echo $post['user_role']; ?>
                                </span>
                            </div>
                            <div class="post-meta">
                                <?php echo date('F j, Y \a\t g:i A', strtotime($post['Date of Post'])); ?>
                                <span class="post-type type-<?php echo strtolower($post['Type']); ?>">
                                    <i class="fas fa-<?php 
                                        echo strtolower($post['Type']) == 'event' ? 'calendar' : 
                                            (strtolower($post['Type']) == 'announcement' ? 'bullhorn' : 'file-alt'); 
                                    ?>"></i>
                                    <?php echo ucfirst($post['Type']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <h2 class="post-title"><?php echo htmlspecialchars($post['Title']); ?></h2>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                    </div>

                    <?php 
                    $postType = strtolower(trim($post['Type']));
                    if (strpos($postType, 'event') !== false): 
                    ?>
                        <div class="ticket-button-container">
                            <h3 style="color: white; margin-bottom: 10px;">🎟️ EVENT TICKETS AVAILABLE!</h3>
                            <a href="ticket.php?event_id=<?php echo urlencode($post['Post ID']); ?>" 
                               target="_blank" 
                               class="ticket-btn">
                                <i class="fas fa-ticket-alt"></i> BUY TICKETS NOW
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="post-actions">
                        <?php $comment_count = countPostComments($conn, $post['Post ID']); ?>
                        <span class="comment-count">
                            <i class="fas fa-comments"></i>
                            <?php echo $comment_count; ?> <?php echo $comment_count == 1 ? 'comment' : 'comments'; ?>
                        </span>
                        
                        <button class="toggle-comments" onclick="toggleComments('post-<?php echo $post['Post ID']; ?>')">
                            <i class="fas fa-reply"></i>
                            <?php echo $comment_count > 0 ? 'View Comments' : 'Add Comment'; ?>
                        </button>
                    </div>

                    <div class="comments-section" id="comments-post-<?php echo $post['Post ID']; ?>">
                        <div class="comment-form">
                            <form method="POST" action="">
                                <textarea class="comment-textarea" name="comment_text" 
                                          placeholder="Write a comment..." required></textarea>
                                <div class="comment-actions">
                                    <input type="hidden" name="post_id" value="<?php echo $post['Post ID']; ?>">
                                    <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane"></i> Post Comment
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php
                        $comments_result = getPostComments($conn, $post['Post ID']);
                        if (mysqli_num_rows($comments_result) > 0):
                        ?>
                            <div class="comments-list">
                                <?php while ($comment = mysqli_fetch_assoc($comments_result)): ?>
                                    <div class="comment">
                                        <div class="comment-header">
                                            <div class="comment-avatar">
                                                <?php 
                                                $firstInitial = strtoupper(substr($comment['First Name'], 0, 1));
                                                $lastInitial = strtoupper(substr($comment['Last Name'], 0, 1));
                                                echo $firstInitial . $lastInitial; 
                                                ?>
                                            </div>
                                            <span class="comment-user">
                                                <?php echo htmlspecialchars($comment['First Name'] . ' ' . $comment['Last Name']); ?>
                                            </span>
                                            <span class="comment-date">
                                                <?php echo date('M j, Y g:i A', strtotime($comment['Date of Comment'])); ?>
                                            </span>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br(htmlspecialchars($comment['Comment'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: #718096; font-style: italic; padding: 20px;">
                                <i class="fas fa-comments"></i>
                                <p>No comments yet. Be the first to comment!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: rgba(255, 255, 255, 0.95); border-radius: 20px; color: #718096;">
                <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                <h3 style="font-size: 1.3rem; margin-bottom: 10px; color: #4a5568;">No posts found</h3>
                <p>Try adjusting your search terms!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleComments(postId) {
            const commentsSection = document.getElementById('comments-' + postId);
            const button = event.target.closest('button');
            
            if (commentsSection.classList.contains('active')) {
                commentsSection.classList.remove('active');
                commentsSection.style.display = 'none';
                button.innerHTML = '<i class="fas fa-reply"></i> View Comments';
            } else {
                commentsSection.classList.add('active');
                commentsSection.style.display = 'block';
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Comments';
            }
        }
    </script>
</body>
</html>