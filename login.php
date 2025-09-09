<?php
include('DBconnect.php');
session_start();

// Initialize variables
$first_name = $last_name = $dob = $password = $user_id = "";
$role = $semester = $department = $enrollment_year = "";
$degree = $graduation_year = $current_job = $linkedin_profile = $contact_no = "";

//Registration/login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Register user
    if (isset($_POST['register'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $dob = $_POST['dob'];
        $password = $_POST['password'];
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];

        // Check if the user ID already exists
        $query = "SELECT * FROM user WHERE `User ID` = '$user_id'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['message'] = "User ID already exists!";
            $_SESSION['message_type'] = "error";
        } else {
            // Insert into `user` table
            $query = "INSERT INTO user (`First Name`, `Last Name`, `User ID`, `Date of Birth`, `Password`, `Created at`, `Updated at`) 
                      VALUES ('$first_name', '$last_name', '$user_id', '$dob', '$password', NOW(), NOW())";
            
            if (mysqli_query($conn, $query)) {
                // Insert role-specific data
                if ($role == 'student') {
                    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
                    $department = mysqli_real_escape_string($conn, $_POST['department']);
                    $enrollment_year = mysqli_real_escape_string($conn, $_POST['enrollment_year']);
                    $bio = '';
                    
                    $query_student = "INSERT INTO `student profile` (`Student ID`, `Semester`, `Department`, `Enrollment Year`, `Bio`) 
                                      VALUES ('$user_id', '$semester', '$department', '$enrollment_year', '$bio')";
                    mysqli_query($conn, $query_student);
                    
                } elseif ($role == 'alumni') {
                    $degree = mysqli_real_escape_string($conn, $_POST['degree']);
                    $graduation_year = mysqli_real_escape_string($conn, $_POST['graduation_year']);
                    $current_job = mysqli_real_escape_string($conn, $_POST['current_job']);
                    $linkedin_profile = mysqli_real_escape_string($conn, $_POST['linkedin_profile']);
                    $bio = '';
                    
                    $query_alumni = "INSERT INTO `alumni profile` (`Alumni ID`, `Degree`, `Graduation Year`, `Current Job`, `LinkedIn Profile`, `Bio`) 
                                     VALUES ('$user_id', '$degree', '$graduation_year', '$current_job', '$linkedin_profile', '$bio')";
                    mysqli_query($conn, $query_alumni);
                    
                } elseif ($role == 'admin') {
                    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
                    
                    $query_admin = "INSERT INTO `admin` (`Admin ID`, `Rank`, `Contact No`, `Po ID`) 
                                    VALUES ('$user_id', 1, '$contact_no', NULL)";
                    mysqli_query($conn, $query_admin);
                }

                $_SESSION['message'] = "Registration successful! You can now log in.";
                $_SESSION['message_type'] = "success";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['message'] = "Registration failed. Please try again.";
                $_SESSION['message_type'] = "error";
            }
        }
    }

    // Login logic
    if (isset($_POST['login'])) {
        $user_id = $_POST['user_id'];
        $password = $_POST['password'];

        // Check login
        $query = "SELECT * FROM user WHERE `User ID` = '$user_id' AND `Password` = '$password'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            // Start session and set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";

            // Home.php after successful login
            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";
            header("Location: home.php");
            exit();
        } else {
            $_SESSION['message'] = "Invalid user ID or password.";
            $_SESSION['message_type'] = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Alumni Portal - Login</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
  
  * { box-sizing: border-box; margin: 0; padding: 0; }
  
  :root {
    --primary: #6366f1;        
    --primary-dark: #4f46e5;    
    --primary-light: #818cf8;    
    --primary-glow: rgba(99, 102, 241, 0.2);

    --secondary: #f8fafc;
    --secondary-dark: #e2e8f0;

    --accent: #1e1b4b;          
    --accent-light: #312e81;
    --accent-medium: #4338ca;

    --text-primary: #0f172a;
    --text-secondary: #475569;
    --text-light: #64748b;
    --text-muted: #94a3b8;

    --surface: #ffffff;
    --surface-elevated: #f9fafb;
    --surface-hover: #f3f4f6;

    --border: #e2e8f0;
    --border-light: #f1f5f9;

    --success: #10b981;
    --success-light: #d1fae5;
    --error: #ef4444;
    --error-light: #fee2e2;
    --warning: #f59e0b;
    --info: #3b82f6;

    --gradient-primary: linear-gradient(135deg, #6366f1 0%, #7c3aed 50%, #6d28d9 100%);
    --gradient-surface: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    --gradient-text: linear-gradient(135deg, #1e1b4b 0%, #6366f1 100%);

    --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --shadow-primary: 0 10px 25px -3px rgba(99, 102, 241, 0.4);

    --radius: 20px;
    --radius-lg: 24px;
    --radius-xl: 32px;

    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    overflow-x: hidden;
    font-weight: 400;
  }
  
  body::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: 
      radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
      radial-gradient(circle at 75% 75%, rgba(139, 92, 246, 0.2) 0%, transparent 50%),
      radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.1) 0%, transparent 60%);
    pointer-events: none;
    animation: float 25s ease-in-out infinite;
  }
  
  @keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 1; }
    33% { transform: translateY(-15px) rotate(0.5deg); opacity: 0.9; }
    66% { transform: translateY(10px) rotate(-0.5deg); opacity: 0.95; }
  }
  
  .portal-container {
    background: var(--gradient-surface);
    backdrop-filter: blur(40px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
    width: 100%;
    max-width: 500px;
    padding: 56px 48px;
    position: relative;
    transform: translateY(0);
    transition: var(--transition);
    overflow: hidden;
  }
  
  .portal-container::before {
    content: '';
    position: absolute;
    top: -2px; left: -2px; right: -2px; bottom: -2px;
    background: linear-gradient(135deg, rgba(255,255,255,0.4), rgba(99, 102, 241, 0.2), rgba(255,255,255,0.4));
    border-radius: var(--radius-xl);
    z-index: -1;
    animation: shimmer 4s ease-in-out infinite;
  }
  
  @keyframes shimmer { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.8; } }
  
  .portal-container:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: var(--shadow-lg), var(--shadow-primary);
  }
  
  .portal-header { text-align: center; margin-bottom: 48px; }
  
  .portal-logo {
    width: 88px; height: 88px;
    background: var(--gradient-primary);
    border-radius: 50%;
    margin: 0 auto 28px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    box-shadow: var(--shadow-primary);
    animation: pulse 3s ease-in-out infinite;
  }
  
  @keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: var(--shadow-primary); }
    50% { transform: scale(1.08); box-shadow: 0 15px 35px -5px rgba(99, 102, 241, 0.6); }
  }
  
  .portal-logo i { font-size: 40px; color: white; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
  
  .portal-title {
    font-size: 36px; font-weight: 800;
    background: var(--gradient-text);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 12px; letter-spacing: -1px; line-height: 1.2;
  }
  
  .portal-subtitle { font-size: 17px; color: var(--text-secondary); font-weight: 500; line-height: 1.6; opacity: 0.9; }
  
  .auth-tabs {
    display: flex;
    background: var(--surface-elevated);
    border-radius: 18px;
    padding: 8px; margin-bottom: 40px;
    position: relative;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--border-light);
  }
  
  .tab-button {
    flex: 1; padding: 16px 24px; border: none; background: transparent; border-radius: 14px;
    font-size: 16px; font-weight: 600; color: var(--text-light); cursor: pointer; transition: var(--transition);
    z-index: 2; position: relative; display: flex; align-items: center; justify-content: center; gap: 10px;
  }
  
  .tab-button.active { color: var(--primary); background: var(--surface); box-shadow: var(--shadow); transform: translateY(-2px); font-weight: 700; }
  .tab-button:hover:not(.active) { color: var(--text-secondary); background: var(--surface-hover); }
  
  .form-container { position: relative; }
  .form-section { display: none; animation: fadeInUp 0.5s ease-out; }
  .form-section.active { display: block; }
  
  @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
  
  .form-group { margin-bottom: 28px; position: relative; }
  .form-row { display: flex; gap: 20px; }
  .form-row .form-group { flex: 1; margin-bottom: 28px; }
  
  label { display: block; font-size: 15px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px; letter-spacing: -0.3px; display: flex; align-items: center; gap: 8px; }
  label i { color: var(--primary); font-size: 14px; opacity: 0.8; }
  
  input, select {
    width: 100%; padding: 18px 24px; border: 2px solid var(--border); border-radius: 16px;
    font-size: 16px; font-weight: 500; color: var(--text-primary); background: var(--surface); transition: var(--transition);
    font-family: inherit; box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.04);
  }
  
  input:focus, select:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-glow), inset 0 1px 4px rgba(0, 0, 0, 0.04);
    transform: translateY(-2px); background: var(--surface);
  }
  
  input::placeholder { color: var(--text-muted); font-weight: 400; }
  
  .role-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
  .role-option { position: relative; }
  .role-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
  
  .role-label {
    display: block; padding: 24px 16px; border: 2px solid var(--border); border-radius: 16px; text-align: center;
    font-size: 14px; font-weight: 600; color: var(--text-secondary); cursor: pointer; transition: var(--transition);
    background: var(--surface); position: relative; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }
  
  .role-label i { font-size: 20px; margin-bottom: 8px; display: block; opacity: 0.7; }
  .role-label::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, var(--primary-glow), transparent); transition: left 0.6s; }
  
  .role-option input[type="radio"]:checked + .role-label {
    border-color: var(--primary); background: var(--primary); color: white; transform: translateY(-4px); box-shadow: var(--shadow-primary); font-weight: 700;
  }
  .role-option input[type="radio"]:checked + .role-label i { opacity: 1; }
  .role-option:hover .role-label::before { left: 100%; }
  .role-option:hover .role-label { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); }
  
  .conditional-fields { display: none; animation: fadeInUp 0.5s ease-out; border-top: 2px solid var(--border-light); padding-top: 28px; margin-top: 16px; }
  .conditional-fields.show { display: block; border-top-color: var(--primary-glow); }
  
  .submit-button {
    width: 100%; padding: 20px 28px; background: var(--gradient-primary); border: none; border-radius: 16px;
    font-size: 17px; font-weight: 700; color: white; cursor: pointer; transition: var(--transition);
    box-shadow: var(--shadow-primary); letter-spacing: -0.3px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; gap: 10px;
  }
  
  .submit-button::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.7s; }
  .submit-button:hover { transform: translateY(-3px); box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.5); }
  .submit-button:hover::before { left: 100%; }
  .submit-button:active { transform: translateY(-1px); }
  
  .message { margin-bottom: 28px; padding: 20px 24px; border-radius: 16px; font-size: 15px; font-weight: 600; text-align: center; border: 2px solid; animation: slideIn 0.5s ease-out; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
  @keyframes slideIn { from { opacity: 0; transform: translateY(-15px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
  .message.success { background: var(--success-light); border-color: var(--success); color: var(--success); }
  .message.error { background: var(--error-light); border-color: var(--error); color: var(--error); }
  
  .portal-footer { margin-top: 48px; text-align: center; font-size: 14px; color: var(--text-light); font-weight: 500; line-height: 1.6; }
  .portal-footer::before { content: ''; display: block; width: 60px; height: 3px; background: var(--gradient-primary); margin: 0 auto 20px; border-radius: 2px; }
  
  @media (max-width: 480px) {
    body { padding: 16px; }
    .portal-container { padding: 40px 28px; max-width: 100%; }
    .portal-title { font-size: 30px; }
    .portal-logo { width: 76px; height: 76px; }
    .portal-logo i { font-size: 36px; }
    .form-row { flex-direction: column; gap: 0; }
    .role-selector { grid-template-columns: 1fr; }
    .auth-tabs { margin-bottom: 32px; }
    .tab-button { padding: 14px 20px; font-size: 15px; }
  }
  
  .loading { position: relative; pointer-events: none; color: transparent !important; }
  .loading::after { content: ''; position: absolute; top: 50%; left: 50%; width: 22px; height: 22px; margin: -11px 0 0 -11px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  
  .form-group::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 16px; background: var(--primary-glow); opacity: 0; transition: opacity 0.4s; pointer-events: none; z-index: -1; }
  .form-group:focus-within::before { opacity: 0.5; }
  
  .conditional-fields { position: relative; }
  .conditional-fields::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--gradient-primary); border-radius: 1px; opacity: 0; transition: opacity 0.4s; }
  .conditional-fields.show::before { opacity: 0.6; }
  
  input:focus, select:focus, .tab-button:focus, .submit-button:focus { outline: 2px solid --var(primary); outline-offset: 2px; }
  
</style>
</head>
<body>
  <div class="portal-container">
    <div class="portal-header">
      <div class="portal-logo">
        <i class="fas fa-graduation-cap"></i>
      </div>
      <h1 class="portal-title">Student Alumni Portal</h1>
      <p class="portal-subtitle">Connect, Learn and Grow Together</p>
    </div>

    <div class="auth-tabs">
      <button class="tab-button active" onclick="switchTab('login')">
        <i class="fas fa-sign-in-alt"></i>
        Sign In
      </button>
      <button class="tab-button" onclick="switchTab('register')">
        <i class="fas fa-user-plus"></i>
        Register
      </button>
    </div>

    <!-- Display PHP messages -->
    <?php if (isset($_SESSION['message'])): ?>
      <div class="message <?php echo $_SESSION['message_type']; ?>">
        <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
      </div>
    <?php endif; ?>

    <div class="form-container">
      <!-- Login Form -->
      <div id="loginForm" class="form-section active">
        <form method="POST" action="login.php">
          <div class="form-group">
            <label for="login_user_id">
              <i class="fas fa-user"></i>
              User ID
            </label>
            <input type="text" name="user_id" id="login_user_id" placeholder="Enter your user ID" required autocomplete="username" />
          </div>

          <div class="form-group">
            <label for="login_password">
              <i class="fas fa-lock"></i>
              Password
            </label>
            <input type="password" name="password" id="login_password" placeholder="Enter your password" required autocomplete="current-password" />
          </div>

          <button class="submit-button" type="submit" name="login">
            <i class="fas fa-sign-in-alt"></i>
            Sign In
          </button>
        </form>
      </div>

      <!-- Registration Form -->
      <div id="registerForm" class="form-section">
        <form method="POST" action="login.php">
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">
                <i class="fas fa-user"></i>
                First Name
              </label>
              <input type="text" name="first_name" id="first_name" placeholder="First name" required />
            </div>
            <div class="form-group">
              <label for="last_name">
                <i class="fas fa-user"></i>
                Last Name
              </label>
              <input type="text" name="last_name" id="last_name" placeholder="Last name" required />
            </div>
          </div>

          <div class="form-group">
            <label for="reg_user_id">
              <i class="fas fa-id-card"></i>
              User ID
            </label>
            <input type="text" name="user_id" id="reg_user_id" placeholder="Choose a unique user ID" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="dob">
                <i class="fas fa-calendar"></i>
                Date of Birth
              </label>
              <input type="date" name="dob" id="dob" required />
            </div>
            <div class="form-group">
              <label for="reg_password">
                <i class="fas fa-lock"></i>
                Password
              </label>
              <input type="password" name="password" id="reg_password" placeholder="Create password" required />
            </div>
          </div>

          <div class="form-group">
            <label>
              <i class="fas fa-user-tag"></i>
              Select Your Role
            </label>
            <div class="role-selector">
              <div class="role-option">
                <input type="radio" name="role" value="student" id="student" onchange="showConditionalFields()" required />
                <label for="student" class="role-label">
                  <i class="fas fa-graduation-cap"></i>
                  Student
                </label>
              </div>
              <div class="role-option">
                <input type="radio" name="role" value="alumni" id="alumni" onchange="showConditionalFields()" required />
                <label for="alumni" class="role-label">
                  <i class="fas fa-briefcase"></i>
                  Alumni
                </label>
              </div>
              <div class="role-option">
                <input type="radio" name="role" value="admin" id="admin" onchange="showConditionalFields()" required />
                <label for="admin" class="role-label">
                  <i class="fas fa-user-shield"></i>
                  Admin
                </label>
              </div>
            </div>
          </div>

          <!-- Student Fields -->
          <div id="studentFields" class="conditional-fields">
            <div class="form-row">
              <div class="form-group">
                <label for="semester">
                  <i class="fas fa-book"></i>
                  Semester
                </label>
                <select name="semester" id="semester">
                  <option value="">Select semester</option>
                  <option value="1st">1st Semester</option>
                  <option value="2nd">2nd Semester</option>
                  <option value="3rd">3rd Semester</option>
                  <option value="4th">4th Semester</option>
                  <option value="5th">5th Semester</option>
                  <option value="6th">6th Semester</option>
                  <option value="7th">7th Semester</option>
                  <option value="8th">8th Semester</option>
                  <option value="9th">9th Semester</option>
                  <option value="10th">10th Semester</option>
                  <option value="11th">11th Semester</option>
                  <option value="12th">12th Semester</option>
                </select>
              </div>
              <div class="form-group">
                <label for="department">
                  <i class="fas fa-building"></i>
                  Department
                </label>
                <input type="text" name="department" id="department" placeholder="e.g. Computer Science" />
              </div>
            </div>
            <div class="form-group">
              <label for="enrollment_year">
                <i class="fas fa-calendar-plus"></i>
                Enrollment Year
              </label>
              <input type="number" name="enrollment_year" id="enrollment_year" placeholder="2024" min="2000" max="2030" />
            </div>
          </div>

          <!-- Alumni Fields -->
          <div id="alumniFields" class="conditional-fields">
            <div class="form-row">
              <div class="form-group">
                <label for="degree">
                  <i class="fas fa-certificate"></i>
                  Degree
                </label>
                <input type="text" name="degree" id="degree" placeholder="e.g. Bachelor of Computer Science" />
              </div>
              <div class="form-group">
                <label for="graduation_year">
                  <i class="fas fa-graduation-cap"></i>
                  Graduation Year
                </label>
                <input type="number" name="graduation_year" id="graduation_year" placeholder="2023" min="1990" max="2030" />
              </div>
            </div>
            <div class="form-group">
              <label for="current_job">
                <i class="fas fa-briefcase"></i>
                Current Position
              </label>
              <input type="text" name="current_job" id="current_job" placeholder="e.g. Software Engineer at Company" />
            </div>
            <div class="form-group">
              <label for="linkedin_profile">
                <i class="fab fa-linkedin"></i>
                LinkedIn Profile
              </label>
              <input type="url" name="linkedin_profile" id="linkedin_profile" placeholder="https://linkedin.com/in/yourprofile" />
            </div>
          </div>

          <!-- Admin Fields -->
          <div id="adminFields" class="conditional-fields">
            <div class="form-group">
              <label for="contact_no">
                <i class="fas fa-phone"></i>
                Contact Number
              </label>
              <input type="tel" name="contact_no" id="contact_no" placeholder="Your contact number" />
            </div>
          </div>

          <button class="submit-button" type="submit" name="register">
            <i class="fas fa-user-plus"></i>
            Create Account
          </button>
        </form>
      </div>
    </div>

    <div class="portal-footer">
      &copy; 2025 Student Alumni Portal. All rights reserved.
    </div>
  </div>

  <script>
    function switchTab(tab) {
      // Update tab buttons
      document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      
      // Update form sections
      document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
      document.getElementById(tab + 'Form').classList.add('active');
      
      // Clear any validation states
      clearFormValidation();
    }
    
    function showConditionalFields() {
      // Hide all conditional fields
      document.querySelectorAll('.conditional-fields').forEach(field => field.classList.remove('show'));
      
      // Show relevant fields based on selected role
      const selectedRole = document.querySelector('input[name="role"]:checked');
      if (selectedRole) {
        const fieldsId = selectedRole.value + 'Fields';
        const fieldsElement = document.getElementById(fieldsId);
        if (fieldsElement) {
          setTimeout(() => {
            fieldsElement.classList.add('show');
          }, 150);
        }
      }
    }
    
    function clearFormValidation() {
      // Clear any existing validation states
      document.querySelectorAll('input, select').forEach(field => {
        field.classList.remove('error');
        field.style.borderColor = '';
      });
    }
    
    // Add loading animation to submit buttons
    document.querySelectorAll('.submit-button').forEach(button => {
      button.addEventListener('click', function(e) {
        const form = this.closest('form');
        if (form.checkValidity()) {
          this.classList.add('loading');
          this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
          
          // Remove loading state after timeout to prevent stuck state
          setTimeout(() => {
            this.classList.remove('loading');
            this.innerHTML = this.name === 'login' ? '<i class="fas fa-sign-in-alt"></i> Sign In' : '<i class="fas fa-user-plus"></i> Create Account';
          }, 8000);
        }
      });
    });
    
    // Auto-focus first input when switching tabs
    document.querySelectorAll('.tab-button').forEach(button => {
      button.addEventListener('click', function() {
        setTimeout(() => {
          const activeForm = document.querySelector('.form-section.active');
          const firstInput = activeForm.querySelector('input:not([type="radio"])');
          if (firstInput) firstInput.focus();
        }, 200);
      });
    });
    
    // Enhanced form validation with better visual feedback
    document.querySelectorAll('input[required], select[required]').forEach(field => {
      field.addEventListener('blur', function() {
        if (!this.value.trim()) {
          this.style.borderColor = 'var(--error)';
          this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        } else {
          this.style.borderColor = 'var(--success)';
          this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
          setTimeout(() => {
            this.style.borderColor = 'var(--border)';
            this.style.boxShadow = 'inset 0 1px 4px rgba(0, 0, 0, 0.04)';
          }, 2000);
        }
      });
      
      field.addEventListener('input', function() {
        if (this.style.borderColor === 'rgb(239, 68, 68)') {
          this.style.borderColor = 'var(--border)';
          this.style.boxShadow = 'inset 0 1px 4px rgba(0, 0, 0, 0.04)';
        }
      });
    });
    
    // Auto-hide messages after 6 seconds with smooth animation
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
      setTimeout(() => {
        message.style.opacity = '0';
        message.style.transform = 'translateY(-20px) scale(0.95)';
        setTimeout(() => { message.remove(); }, 400);
      }, 6000);
    });
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
    
    // Enhanced initialization with staggered animations
    document.addEventListener('DOMContentLoaded', function() {
      // Focus first input
      const firstInput = document.querySelector('#loginForm input');
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 300);
      }
      
      // Add subtle staggered animations to form elements
      const formElements = document.querySelectorAll('.form-group, .role-option, .submit-button, .auth-tabs');
      formElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
          element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }, index * 80 + 200);
      });
      
      // Add header animation
      const headerElements = document.querySelectorAll('.portal-logo, .portal-title, .portal-subtitle');
      headerElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
          element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }, index * 150);
      });
    });
    
    // Subtle parallax effect
    document.addEventListener('mousemove', function(e) {
      const container = document.querySelector('.portal-container');
      const rect = container.getBoundingClientRect();
      const x = (e.clientX - rect.left - rect.width / 2) / rect.width;
      const y = (e.clientY - rect.top - rect.height / 2) / rect.height;
      container.style.transform = `perspective(1000px) rotateX(${y * 2}deg) rotateY(${x * 2}deg) translateZ(0)`;
    });
    document.addEventListener('mouseleave', function() {
      const container = document.querySelector('.portal-container');
      container.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0)';
    });
  </script>
</body>
</html>