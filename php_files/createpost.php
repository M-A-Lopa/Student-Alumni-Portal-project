<?php
include('DBconnect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

//user details for display
$query = "SELECT `First Name`, `Last Name` FROM user WHERE `User ID` = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// check user role 
function getUserRole($user_id, $conn) {
    $student_check = mysqli_query($conn, "SELECT 1 FROM `student profile` WHERE `Student ID` = '$user_id'");
    if (mysqli_num_rows($student_check) > 0) return 'student';
    
    $alumni_check = mysqli_query($conn, "SELECT 1 FROM `alumni profile` WHERE `Alumni ID` = '$user_id'");
    if (mysqli_num_rows($alumni_check) > 0) return 'alumni';
    
    $admin_check = mysqli_query($conn, "SELECT 1 FROM `admin` WHERE `Admin ID` = '$user_id'");
    if (mysqli_num_rows($admin_check) > 0) return 'admin';
    
}

$user_role = getUserRole($user_id, $conn);

//post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $file_url = '';

    //file upload if provided
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "pdf", "doc", "docx", "txt");
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($_FILES["file"]["size"] <= 10 * 1024 * 1024) {
                $file_name = uniqid() . "_" . basename($_FILES["file"]["name"]);
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    $file_url = $target_file;
                } else {
                    $error_message = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error_message = "File size must be less than 10MB.";
            }
        } else {
            $error_message = "Sorry, only JPG, JPEG, PNG, GIF, PDF, DOC, DOCX & TXT files are allowed.";
        }
    }

    if (!isset($error_message)) {
        $post_id = uniqid('post_');
        
        if ($user_role == 'admin') {
            // Admin posts --> auto approved
            $insert_query = "INSERT INTO post (`Post ID`, `Content`, `Date of Post`, `Title`, `File URL`, `Ur ID`, `Type`, `status`) 
                             VALUES ('$post_id', '$content', NOW(), '$title', '$file_url', '$user_id', '$type', NULL)";
            
            if (mysqli_query($conn, $insert_query)) {
                $success_message = "Post created and published successfully!";
                $title = $content = $type = '';
            } else {
                $error_message = "Error creating post: " . mysqli_error($conn);
            }
        } else {
            // Student and Alumni --> pending (admin)
            $insert_query = "INSERT INTO post (`Post ID`, `Content`, `Date of Post`, `Title`, `File URL`, `Ur ID`, `Type`, `status`) 
                             VALUES ('$post_id', '$content', NOW(), '$title', '$file_url', '$user_id', '$type', 'pending')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success_message = "Post submitted successfully! It will be visible after admin approval.";
                $title = $content = $type = '';
            } else {
                $error_message = "Error creating post: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Student Alumni Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        .header h1 {
            color: #2d3748;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #2d3748, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .header p { color: #4a5568; font-size: 1rem; font-weight: 500; }
        .role-info { margin-top: 15px; padding: 12px 20px; border-radius: 10px; font-size: 0.9rem; font-weight: 600; }
        .role-student, .role-alumni { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
        .role-admin { background: #dcfce7; color: #166534; border: 1px solid #16a34a; }
        .post-form-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .form-group { margin-bottom: 25px; }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group label i { color: #667eea; width: 16px; }
        .form-control {
            width: 100%; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 16px; font-family: inherit; transition: all 0.3s ease; background: white;
        }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); transform: translateY(-1px);}
        .form-control::placeholder { color: #a0aec0; }
        textarea.form-control { resize: vertical; min-height: 120px; font-family: inherit; }
        .post-type-selector {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .post-type-option { position: relative; }
        .post-type-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0;}
        .post-type-label {
            background: #f9f6ff;
            border: 1px solid #ece7f8;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            color: #7b7aea;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.2s, border 0.2s;
        }
        .post-type-option input[type="radio"]:checked + .post-type-label {
            background: #ece7f8;
            border-color: #7b7aea;
            color: #3e3c77;
        }
        .post-type-label i { font-size: 1.1em;}
        .btn {
            display: inline-flex; align-items: center; gap: 10px; padding: 15px 25px;
            border: none; border-radius: 12px; font-size: 16px; font-weight: 600; text-decoration: none;
            cursor: pointer; transition: all 0.3s ease; font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);}
        .btn-secondary { background: #e2e8f0; color: #4a5568; }
        .btn-secondary:hover { background: #cbd5e0; transform: translateY(-1px);}
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 25px; border-top: 1px solid #e2e8f0;}
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px;}
        .alert-success { background: #f0fff4; border: 1px solid #9ae6b4; color: #2f855a;}
        .alert-error { background: #fed7d7; border: 1px solid #feb2b2; color: #c53030;}
        .back-nav { margin-bottom: 20px; }
        .back-nav a {
            display: inline-flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-weight: 500;
            padding: 10px 15px; border-radius: 10px; background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px); transition: all 0.3s ease;
        }
        .back-nav a:hover { background: rgba(255,255,255,0.2); transform: translateY(-1px);}
        @media (max-width: 768px) {
            .container { padding: 10px;}
            .header, .post-form-container { padding: 25px 20px; margin-bottom: 20px;}
            .header h1 { font-size: 1.8rem;}
            .post-type-selector { flex-wrap: wrap;}
            .form-actions { flex-direction: column;}
            .btn { width: 100%; justify-content: center;}
        }
        .fade-in { animation: fadeInUp 0.6s ease-out;}
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-nav">
            <a href="home.php">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
        
        <div class="header fade-in">
            <h1>Create New Post</h1>
            <p>Share your thoughts, experiences, or resources with the community</p>
            <?php if ($user_role == 'student' || $user_role == 'alumni'): ?>
                <div class="role-info role-<?php echo $user_role; ?>">
                    <i class="fas fa-info-circle"></i>
                    Your post will be sent to admin for approval before being published.
                </div>
            <?php elseif ($user_role == 'admin'): ?>
                <div class="role-info role-admin">
                    <i class="fas fa-check-circle"></i>
                    As an admin, your posts will be published immediately.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="post-form-container fade-in">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="postForm">
                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-heading"></i>
                        Post Title
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           class="form-control" 
                           placeholder="Enter a catchy title for your post"
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                           required
                           maxlength="200">
                </div>
                
                <div class="form-group">
                    <label for="type">
                        <i class="fas fa-tag"></i>
                        Post Type
                    </label>
                    <div class="post-type-selector">
                        <div class="post-type-option">
                            <input type="radio" name="type" value="general" id="general"
                                   <?php echo (isset($type) && $type == 'general') ? 'checked' : ''; ?> required>
                            <label for="general" class="post-type-label">
                                <i class="fas fa-comments"></i>
                                General
                            </label>
                        </div>
                        <div class="post-type-option">
                            <input type="radio" name="type" value="academic" id="academic"
                                   <?php echo (isset($type) && $type == 'academic') ? 'checked' : ''; ?>>
                            <label for="academic" class="post-type-label">
                                <i class="fas fa-graduation-cap"></i>
                                Academic
                            </label>
                        </div>
                        <div class="post-type-option">
                            <input type="radio" name="type" value="career" id="career"
                                   <?php echo (isset($type) && $type == 'career') ? 'checked' : ''; ?>>
                            <label for="career" class="post-type-label">
                                <i class="fas fa-briefcase"></i>
                                Career
                            </label>
                        </div>
                        <div class="post-type-option">
                            <input type="radio" name="type" value="announcement" id="announcement"
                                   <?php echo (isset($type) && $type == 'announcement') ? 'checked' : ''; ?>>
                            <label for="announcement" class="post-type-label">
                                <i class="fas fa-bullhorn"></i>
                                News
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="content">
                        <i class="fas fa-edit"></i>
                        Content
                    </label>
                    <textarea name="content" 
                              id="content" 
                              class="form-control" 
                              placeholder="Write your post content here... Share your thoughts, experiences, or ask questions!"
                              required
                              maxlength="5000"><?php echo isset($content) ? htmlspecialchars($content) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="file">
                        <i class="fas fa-paperclip"></i>
                        Attachment (Optional)
                    </label>
                    <input type="file" 
                           name="file" 
                           id="file" 
                           class="form-control"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                    <div style="font-size: 0.85rem; color: #718096; margin-top: 8px;">
                        Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX, TXT (Max 10MB)
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                        Reset
                    </button>
                    <button type="submit" name="create_post" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $user_role == 'admin' ? 'Publish Post' : 'Submit for Approval'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>