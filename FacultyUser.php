<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['id'])) {
    header('Location: EvalMain.php');
    exit;
}

$student_id = intval($_SESSION['id']);
$studentName = '';
$studentYearLevel = '';
$studentProgram = '';
$studentStatus = 'active';

$stmt = $conn->prepare("SELECT s.firstname, s.lastname, s.yearlevel, s.student_number, s.status, p.program_name 
                          FROM add_students s 
                          LEFT JOIN add_programs p ON s.program = p.id 
                          WHERE s.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $student = $result->fetch_assoc();
    $studentName = trim(($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? ''));
    $studentYearLevel = $student['yearlevel'] ?? '';
    $studentNumber = $student['student_number'] ?? '';
    $studentProgram = $student['program_name'] ?? '';
    $studentStatus = strtolower($student['status'] ?? 'active');
} else {
    $stmt->close();
    $conn->close();
    header('Location: EvalMain.php');
    exit;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System - Admin Panel</title>
  <link rel="stylesheet" href="FacultyUser.css?v=<?=time()?>">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

</head>
<body>

<!-- Navbar ======================================================================================-->

<div class="navbar">
  <div class="brand"> 
    <img src="schoollogo.png" alt="Logo" class="navbar-logo">
    <span class="logo-text main-title">Faculty Evaluation System</span>
  </div>
  <div class="user-menu">
    <div class="student-box" onclick="toggleDropdown()">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Student Logo" class="logo-img">
      <span>Student</span>
      <i class="ph ph-caret-down"></i>
    </div>
    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" onclick="showPasswordForm()"><i class="ph ph-key"></i> Change Password</a>
      <a href="#" onclick="showProfile()"><i class="ph ph-user"></i> Profile</a>
      <a href="#" onclick="showEvaluationHistory(event)"><i class="ph ph-clock-counter-clockwise"></i> View History</a>
      <a href="#" onclick="logout(event)"><i class="ph ph-sign-out"></i> Logout</a>
    </div>
  </div>
</div>


<!-- Logout ======================================================================================-->

<div id="logoutModal" class="logoutform">
  <div class="logout-content">
    <div class="logout-icon"><span class="icon-bg"><i class="ph ph-sign-out"></i></span></div>
    <h3>Are you sure you want to logout?</h3>
    <div class="logout-buttons">
      <button class="btn logout-btn" onclick="confirmLogout()">Yes, Log me out</button>
      <button class="btn no-btn" onclick="closeLogoutModal()">No, Stay Logged In</button>
    </div>
  </div>
</div>

<div id="passwordForm" class="LoginForm">
  <div class="LoginForm-content">

  <div class="wave-bg wave-1"></div>
    <div class="wave-bg wave-2"></div>
    <div class="wave-bg wave-3"></div>
    
    <div class="LoginForm-header">
      <img src="logo.png" class="logo">
      <div class="back-btn" id="closePasswordForm">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    
    <div class="login-form-container">
      <h2 class="login-title">Change Password</h2>
      <p class="login-description">Update your account password</p>
      
      <form id="passwordChangeForm" class="modern-login-form">
        <div class="modern-input-group has-toggle">
          <input type="password" id="oldPass" placeholder="Old Password" required>
          <div class="password-toggle" onclick="togglePassword('oldPass', this)">
            <i class="ph ph-eye-slash"></i>
          </div>
          <small id="oldPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <input type="password" id="newPass" placeholder="New Password" required>
          <div class="password-toggle" onclick="togglePassword('newPass', this)">
            <i class="ph ph-eye-slash"></i>
          </div>
          <small id="newPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <input type="password" id="confirmPass" placeholder="Confirm Password" required>
          <div class="password-toggle" onclick="togglePassword('confirmPass', this)">
            <i class="ph ph-eye-slash"></i>
          </div>
          <small id="confirmPassError" class="error-message"></small>
        </div>
        
        <button type="button" class="modern-login-btn" onclick="updatePassword()">Update Password</button>
      </form>
    </div>
  </div>
</div>

<input type="hidden" id="studentId" value="<?php echo htmlspecialchars($student_id); ?>">
<input type="hidden" id="studentName" value="<?php echo htmlspecialchars($studentName); ?>">
<input type="hidden" id="studentNumber" value="<?php echo htmlspecialchars($studentNumber); ?>">
<input type="hidden" id="studentYearLevel" value="<?php echo htmlspecialchars($studentYearLevel); ?>">
<input type="hidden" id="studentProgram" value="<?php echo htmlspecialchars($studentProgram); ?>">
<input type="hidden" id="studentStatus" value="<?php echo htmlspecialchars($studentStatus); ?>">


<!-- Main Page ======================================================================================-->

<div class="content-container">
  <div class="main-box" id="mainPage">
    <div class="main-left">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Welcome Image" class="main-img">
    </div>
    <div class="main-right">
      <h1>Welcome, <?php echo htmlspecialchars($studentName ?: 'Student'); ?></h1>
      <div class="academic-year">Academic Year: 2025–2026 • 2nd Semester</div>
      <div class="academic-year">Year Level: <?php echo htmlspecialchars($studentYearLevel ?: 'N/A'); ?></div>
      <p class="subtitle">Your feedback is essential in helping us improve teaching and learning. 
        Each evaluation you complete strengthens our commitment to academic excellence.</p>
      <?php if ($studentStatus !== 'active'): ?>
        <div class="inactive-account-message">
          <i class="ph ph-warning-circle"></i>
          <span>Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.</span>
        </div>
      <?php endif; ?>
      <button class="evaluate-btn" onclick="showEvaluateSection()" <?php echo $studentStatus !== 'active' ? 'disabled' : ''; ?>>Evaluate Now</button>
    </div>
  </div>

  <!-- Evaluation Box======================================================================================-->
  <div class="evaluate-section" id="evaluateSection" style="display:none;">
    <div class="eval-box">
      <div class="eval-header">
        <div class="title-subtitle">
          <h1>Faculty Evaluation</h1>
          <p class="eval-description">Please provide your honest feedback about your faculty's teaching performance. Your responses will help improve the quality of education.</p>
        </div>
      </div>
      <div class="eval-actions">
        <button class="back-btn" onclick="goBackToMain()"><i class="ph ph-arrow-left"></i> Back</button>
      </div>
    </div>
  </div>
  
  <!-- Faculty Cards Section======================================================================================-->
  <div class="faculty-cards" id="facultyCards" style="display:none;">
    <div id="facultyContainer" class="faculty-container">
    </div>
  </div>
  <!-- Evaluation Form ======================================================================================-->
  <div id="evaluationContainer" style="display:none"></div>
</div>


<script src="FacultyUser.js?v=<?=time()?>"></script>
</body>
</html>






