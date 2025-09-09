<?php
include('DBconnect.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// check if student
$student_check_query = "SELECT * FROM `student profile` WHERE `Student ID` = '$student_id'";
$student_check_result = mysqli_query($conn, $student_check_query);

if (mysqli_num_rows($student_check_result) == 0) {
    header("Location: home.php");
    exit();
}

//mentorship request submission (student)
if (isset($_POST['send_request'])) {
    $alumni_id = mysqli_real_escape_string($conn, $_POST['alumni_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Check if request exists
    $existing_request_query = "SELECT * FROM mentorship_requests WHERE student_id = '$student_id' AND alumni_id = '$alumni_id' AND status = 'pending'";
    $existing_request_result = mysqli_query($conn, $existing_request_query);
    
    if (mysqli_num_rows($existing_request_result) > 0) {
        $error_message = "You already have a pending request with this alumni.";
    } else {
        //new request
        $insert_request_query = "INSERT INTO mentorship_requests (student_id, alumni_id, message, status, created_at) VALUES ('$student_id', '$alumni_id', '$message', 'pending', NOW())";
        
        if (mysqli_query($conn, $insert_request_query)) {
            $success_message = "Mentorship request sent successfully!";
        } else {
            $error_message = "Failed to send mentorship request. Please try again.";
        }
    }
}

//search
$search_results = [];
$search_query = '';
if (isset($_GET['search']) || isset($_POST['search'])) {
    $search_query = isset($_GET['search']) ? $_GET['search'] : $_POST['search'];
    $search_query = mysqli_real_escape_string($conn, $search_query);
    
    if (!empty($search_query)) {
        $alumni_search_query = "SELECT u.*, ap.* FROM user u 
                               JOIN `alumni profile` ap ON u.`User ID` = ap.`Alumni ID` 
                               WHERE u.`User ID` LIKE '%$search_query%' 
                               OR u.`First Name` LIKE '%$search_query%' 
                               OR u.`Last Name` LIKE '%$search_query%' 
                               OR CONCAT(u.`First Name`, ' ', u.`Last Name`) LIKE '%$search_query%'
                               OR ap.`Degree` LIKE '%$search_query%'
                               OR ap.`Current Job` LIKE '%$search_query%'
                               ORDER BY u.`First Name`, u.`Last Name`";
        
        $search_result = mysqli_query($conn, $alumni_search_query);
        
        if ($search_result) {
            while ($row = mysqli_fetch_assoc($search_result)) {
                $search_results[] = $row;
            }
        }
    }
} else {

    $all_alumni_query = "SELECT u.*, ap.* FROM user u 
                         JOIN `alumni profile` ap ON u.`User ID` = ap.`Alumni ID` 
                         ORDER BY u.`First Name`, u.`Last Name`";
    
    $all_alumni_result = mysqli_query($conn, $all_alumni_query);
    
    if ($all_alumni_result) {
        while ($row = mysqli_fetch_assoc($all_alumni_result)) {
            $search_results[] = $row;
        }
    }
}

//alumni emails
function getAlumniEmails($conn, $alumni_id) {
    $email_query = "SELECT `Email` FROM email WHERE `US ID` = '$alumni_id'";
    $email_result = mysqli_query($conn, $email_query);
    $emails = [];
    
    if ($email_result) {
        while ($email_row = mysqli_fetch_assoc($email_result)) {
            $emails[] = $email_row['Email'];
        }
    }
    
    return $emails;
}

//check if request exists
function hasExistingRequest($conn, $student_id, $alumni_id) {
    $check_query = "SELECT * FROM mentorship_requests WHERE student_id = '$student_id' AND alumni_id = '$alumni_id' AND status = 'pending'";
    $check_result = mysqli_query($conn, $check_query);
    return mysqli_num_rows($check_result) > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentors - Student Alumni Portal</title>
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }

        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
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
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .search-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .clear-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px;
            background: #e2e8f0;
            color: #4a5568;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .clear-button:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        .results-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .results-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .alumni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .alumni-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .alumni-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .alumni-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 16px 16px 0 0;
        }

        .alumni-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .alumni-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .alumni-basic-info h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .alumni-id {
            font-size: 0.9rem;
            color: #718096;
            font-weight: 500;
        }

        .alumni-details {
            margin-bottom: 12px;
        }
        .alumni-details > * {
            margin-bottom: 12px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 8px 0;
        }

        .detail-icon {
            width: 20px;
            color: #667eea;
            text-align: center;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
            width: 120px;
            font-size: 0.9rem;
        }

        .detail-value {
            flex: 1;
            color: #2d3748;
            font-weight: 500;
        }

        .detail-value a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .detail-value a:hover {
            text-decoration: underline;
        }

        .alumni-bio {
            background: #f7fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin: 15px 0;
            font-style: italic;
            color: #4a5568;
        }

        .alumni-emails {
            background: #f0fff4;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #48bb78;
            margin: 15px 0;
        }

        .email-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .request-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .request-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.3);
        }

        .request-button:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #333;
        }

        .modal-header {
            margin-bottom: 25px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .modal-subtitle {
            color: #718096;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .form-textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-cancel {
            padding: 12px 20px;
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
        }

        .btn-send {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4a5568;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input {
                min-width: auto;
            }

            .alumni-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="home.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Profile
            </a>
            <h1>Find Mentors</h1>
            <p>Connect with experienced alumni who can guide your career journey</p>
        </div>

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

        <div class="search-section">
            <form class="search-form" method="GET" action="">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search by name, user ID, degree, or current job..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <a href="findmentor.php" class="clear-button">
                    <i class="fas fa-times"></i>
                    Clear
                </a>
            </form>
        </div>

        <div class="results-section">
            <div class="results-header">
                <h2 class="results-title">
                    <i class="fas fa-users"></i>
                    <?php echo !empty($search_query) ? "Search Results" : "All Alumni Mentors"; ?>
                </h2>
                <div class="results-count">
                    <?php echo count($search_results); ?> alumni found
                </div>
            </div>

            <?php if (count($search_results) > 0): ?>
                <div class="alumni-grid">
                    <?php foreach ($search_results as $alumni): ?>
                        <div class="alumni-card">
                            <div class="alumni-header">
                                <div class="alumni-avatar">
                                    <?php 
                                    $firstInitial = strtoupper(substr($alumni['First Name'], 0, 1));
                                    $lastInitial = strtoupper(substr($alumni['Last Name'], 0, 1));
                                    echo $firstInitial . $lastInitial; 
                                    ?>
                                </div>
                                <div class="alumni-basic-info">
                                    <h3><?php echo htmlspecialchars($alumni['First Name'] . ' ' . $alumni['Last Name']); ?></h3>
                                    <div class="alumni-id">ID: <?php echo htmlspecialchars($alumni['User ID']); ?></div>
                                </div>
                            </div>

                            <div class="alumni-details">
                                <div class="detail-item">
                                    <i class="fas fa-graduation-cap detail-icon"></i>
                                    <span class="detail-label">Degree:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($alumni['Degree']); ?></span>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-calendar detail-icon"></i>
                                    <span class="detail-label">Graduated:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($alumni['Graduation Year']); ?></span>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-briefcase detail-icon"></i>
                                    <span class="detail-label">Current Job:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($alumni['Current Job']); ?></span>
                                </div>

                                <?php if (!empty($alumni['LinkedIn Profile'])): ?>
                                <div class="detail-item">
                                    <i class="fab fa-linkedin detail-icon"></i>
                                    <span class="detail-label">LinkedIn:</span>
                                    <span class="detail-value">
                                        <a href="<?php echo htmlspecialchars($alumni['LinkedIn Profile']); ?>" target="_blank">
                                            View Profile
                                        </a>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($alumni['bio'])): ?>
                            <div class="alumni-bio">
                                <strong>Bio:</strong> <?php echo htmlspecialchars($alumni['bio']); ?>
                            </div>
                            <?php endif; ?>

                            <?php 
                            $alumni_emails = getAlumniEmails($conn, $alumni['User ID']);
                            if (!empty($alumni_emails)): 
                            ?>
                            <div class="alumni-emails">
                                <strong style="display: block; margin-bottom: 8px; color: #2d3748;">Contact:</strong>
                                <?php foreach ($alumni_emails as $email): ?>
                                <div class="email-item">
                                    <i class="fas fa-envelope" style="color: #48bb78;"></i>
                                    <span><?php echo htmlspecialchars($email); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (hasExistingRequest($conn, $student_id, $alumni['User ID'])): ?>
                                <button class="request-button" disabled>
                                    <i class="fas fa-clock"></i>
                                    Request Pending
                                </button>
                            <?php else: ?>
                                <button class="request-button" onclick="openRequestModal('<?php echo $alumni['User ID']; ?>', '<?php echo htmlspecialchars($alumni['First Name'] . ' ' . $alumni['Last Name']); ?>')">
                                    <i class="fas fa-handshake"></i>
                                    Request Mentorship
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No alumni found</h3>
                    <p><?php echo !empty($search_query) ? "Try different search terms" : "No alumni mentors available at the moment"; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="requestModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRequestModal()">&times;</span>
            <div class="modal-header">
                <h2 class="modal-title">Request Mentorship</h2>
                <p class="modal-subtitle">Send a mentorship request to <span id="alumniName"></span></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="alumni_id" id="alumniId">
                
                <div class="form-group">
                    <label class="form-label" for="message">
                        <i class="fas fa-comment"></i>
                        Your Message
                    </label>
                    <textarea 
                        name="message" 
                        id="message" 
                        class="form-textarea" 
                        placeholder="Introduce yourself and explain why you'd like this person to be your mentor..."
                        required
                    ></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeRequestModal()">
                        Cancel
                    </button>
                    <button type="submit" name="send_request" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                        Send Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRequestModal(alumniId, alumniName) {
            document.getElementById('alumniId').value = alumniId;
            document.getElementById('alumniName').textContent = alumniName;
            document.getElementById('requestModal').style.display = 'block';
            document.getElementById('message').focus();
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
            document.getElementById('message').value = '';
        }


        window.addEventListener('click', function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target === modal) {
                closeRequestModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeRequestModal();
            }
        });

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


            const cards = document.querySelectorAll('.alumni-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });


        document.querySelector('#requestModal form').addEventListener('submit', function(e) {
            const message = document.getElementById('message').value.trim();
            if (message.length < 10) {
                e.preventDefault();
                alert('Please write a more detailed message (at least 10 characters).');
                document.getElementById('message').focus();
            }
        });

  
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {

            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.closest('form').submit();
                }
            });

            searchInput.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.value = '';
                    this.focus();
                }
            });
        }
    </script>
</body>
</html>