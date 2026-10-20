<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../shared/security.php';
requireDashboardRole('student');
include __DIR__ . '/../shared/connect.php';

$student_id = intval($_SESSION['id']);
$studentName = '';
$studentYearLevel = '';
$studentProgram = '';
$studentStatus = 'active';

$stmt = $conn->prepare("SELECT s.firstname, s.lastname, s.yearlevel, s.student_number, s.section, s.status, p.program_name, p.program_code 
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
    $studentProgramCode = $student['program_code'] ?? '';
    $studentSection = $student['section'] ?? '';
    $studentStatus = strtolower(trim((string)($student['status'] ?? 'active')));
} else {
    $stmt->close();
    $conn->close();
    header('Location: ../EvalMain.php');
    exit;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <title>Faculty Evaluation System - Admin Panel</title>
  <link rel="stylesheet" href="FacultyUser.css?v=<?=time()?>">
  <link rel="stylesheet" href="../shared/assets/unified_notifications.css?v=<?=time()?>">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <script>
    window.addEventListener("pageshow", event => {
      if (event.persisted || (performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
        window.location.reload();
      }
    });
  </script>
</head>
<body>

<!-- Navbar ======================================================================================-->

<div class="navbar student-navbar-loading" id="studentNavbar" aria-busy="true">
  <div class="student-navbar-skeleton" aria-hidden="true">
    <span class="student-navbar-skeleton-logo"></span>
    <div class="student-navbar-skeleton-brand">
      <span class="student-navbar-skeleton-title"></span>
      <span class="student-navbar-skeleton-subtitle"></span>
    </div>
    <div class="student-navbar-skeleton-account">
      <span class="student-navbar-skeleton-user"></span>
      <span class="student-navbar-skeleton-caret"></span>
    </div>
  </div>
  <div class="brand"> 
    <img src="../assets/images/schoollogo.png" alt="Logo" class="navbar-logo">
    <div class="brand-text">
      <span class="logo-text main-title">Faculty Evaluation System</span>
      <span class="logo-subtitle">STUDENT PORTAL</span>
    </div>
  </div>
  <div class="user-menu">
    <div class="student-box" onclick="toggleDropdown()" role="button" tabindex="0" aria-label="Student account menu" aria-haspopup="true" aria-expanded="false">
      <span class="student-account-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
          <circle cx="12" cy="7" r="4" />
          <path d="M4 21a8 8 0 0 1 16 0v1H4z" />
        </svg>
      </span>
      <i class="ph ph-caret-down student-caret" aria-hidden="true"></i>
    </div>
    <div class="dropdown-menu" id="dropdownMenu">
      <div class="dropdown-user-info">
        <div class="dropdown-avatar">
          <span class="dropdown-avatar-initials"><?php 
            $names = explode(' ', $studentName);
            echo strtoupper(substr($names[0] ?? 'S', 0, 1) . substr($names[1] ?? 'T', 0, 1));
          ?></span>
        </div>
        <div class="dropdown-user-details">
          <span class="dropdown-user-name"><?php echo htmlspecialchars($studentName ?: 'Student'); ?></span>
          <span class="dropdown-user-email"><?php 
            // Get email from database
            include __DIR__ . '/../shared/connect.php';
            $stmt = $conn->prepare("SELECT email FROM add_students WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $email = '';
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $email = $row['email'] ?? '';
            }
            $stmt->close();
            $conn->close();
            echo htmlspecialchars($email ?: 'student@email.com');
          ?></span>
        </div>
      </div>
      <hr class="dropdown-divider">
      <a href="#" onclick="showProfile(event)"><i class="ph ph-user"></i> Profile</a>
      <a href="#" onclick="showPasswordForm(event)"><i class="ph ph-key"></i> Change Password</a>
      <a href="#" onclick="showEvaluationHistory(event)"><i class="ph ph-clock-counter-clockwise"></i> View History</a>
      <hr class="dropdown-divider">
      <a href="#" onclick="logout(event)" class="logout-link"><i class="ph ph-sign-out"></i> Logout</a>
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

    <div class="LoginForm-header">
      <div class="back-btn" id="closePasswordForm">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    
    <div class="login-form-container">
      <h2 class="login-title">Change Password</h2>
      <p class="login-description">Update your account password</p>
      
      <form id="passwordChangeForm" class="modern-login-form">
        <div class="modern-input-group has-toggle">
          <label class="password-field-label" for="oldPass">Old Password</label>
          <div class="input-wrap">
            <input type="password" id="oldPass" required>
            <div class="password-toggle" onclick="togglePassword('oldPass', this)">
              <i class="ph ph-eye-slash"></i>
            </div>
          </div>
          <small id="oldPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <label class="password-field-label" for="newPass">New Password</label>
          <div class="input-wrap">
            <input type="password" id="newPass" required>
            <div class="password-toggle" onclick="togglePassword('newPass', this)">
              <i class="ph ph-eye-slash"></i>
            </div>
          </div>
          <small id="newPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <label class="password-field-label" for="confirmPass">Confirm Password</label>
          <div class="input-wrap">
            <input type="password" id="confirmPass" required>
            <div class="password-toggle" onclick="togglePassword('confirmPass', this)">
              <i class="ph ph-eye-slash"></i>
            </div>
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
<input type="hidden" id="studentSection" value="<?php echo htmlspecialchars($studentSection); ?>">
<input type="hidden" id="studentProgram" value="<?php echo htmlspecialchars($studentProgram); ?>">
<input type="hidden" id="studentStatus" value="<?php echo htmlspecialchars($studentStatus); ?>">


<!-- Main Page ======================================================================================-->

<div class="content-container">
  <!-- Welcome Card -->
  <div class="welcome-card student-dashboard-loading" id="mainPage" aria-busy="true">
    <div class="student-dashboard-skeleton" aria-hidden="true">
      <div class="student-dashboard-skeleton-content">
        <div class="student-dashboard-skeleton-header">
          <span class="student-dashboard-skeleton-line name"></span>
          <span class="student-dashboard-skeleton-period"></span>
        </div>
        <div class="student-dashboard-skeleton-copy">
          <span></span><span></span><span></span>
        </div>
        <span class="student-dashboard-skeleton-button"></span>
      </div>
      <span class="student-dashboard-skeleton-image"></span>
      <span class="student-dashboard-skeleton-status"></span>
    </div>
    <span id="evaluation-period-status" class="evaluation-period-status is-checking" role="status" aria-live="polite">
      <i class="ph ph-circle-notch" aria-hidden="true"></i>
      Checking evaluation status
    </span>
    <div class="welcome-content">
      <div class="welcome-right">
        <div class="welcome-header-inline">
          <h1 class="student-name-title" style="font-family: Arial, Helvetica, sans-serif; font-weight: 800;">Welcome, <?php echo htmlspecialchars($studentName ?: 'Student'); ?>!</h1>
          <span id="studentAcademicPeriod" class="welcome-period-subtitle" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path fill="currentColor" d="M3 5.75A1.75 1.75 0 0 1 4.75 4h5.086c.464 0 .91.184 1.238.512l1.414 1.414c.14.14.33.219.528.219h6.234A1.75 1.75 0 0 1 21 7.895v10.355A1.75 1.75 0 0 1 19.25 20H4.75A1.75 1.75 0 0 1 3 18.25z"/>
            </svg>
            <span class="welcome-period-label"></span>
          </span>
        </div>

        <p id="evaluation-period-access-message" class="evaluation-period-access-message" role="status" aria-live="polite" hidden></p>
        
        <div class="welcome-message">
          <p>Your feedback is essential in improving teaching and learning. Your honest evaluation helps us recognize our instructors&rsquo; strengths and identify areas for improvement.</p>
          <p>Thank you for your time and participation!</p>
        </div>
        
        <?php if ($studentStatus !== 'active'): ?>
        <div class="inactive-account-message">
          <i class="ph ph-warning-circle"></i>
          <span>Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.</span>
        </div>
        <?php endif; ?>
        
        <button class="evaluate-now-btn" onclick="showEvaluateSection()" <?php echo $studentStatus !== 'active' ? 'disabled' : ''; ?>>
          <i class="ph ph-clipboard-text"></i>
          Evaluate Now
        </button>
      </div>
      <img class="welcome-main-image" src="../assets/images/mainimg.png" alt="" aria-hidden="true">
    </div>
  </div>

  <!-- Evaluation Box======================================================================================-->
  <div class="evaluate-section" id="evaluateSection" style="display:none;">
    <div class="eval-box">
      <div class="eval-header">
        <div class="title-subtitle">
          <h1>Evaluation Guide</h1>
          <p class="eval-description">Select a subject to evaluate its assigned instructor. Rate each statement honestly based on your learning experience. Complete all items to submit your evaluation.</p>
        </div>
      </div>
      <div class="eval-actions">
        <button class="back-btn" onclick="goBackToMain()"><i class="ph ph-arrow-left"></i> Back</button>
      </div>
    </div>
  </div>
  
  <!-- Faculty Cards Section======================================================================================-->
  <div class="faculty-cards" id="facultyCards" style="display:none;">
    <div class="faculty-evaluation-layout">
      <div class="faculty-list-panel">
        <div class="evaluation-list-header">
          <h3><i class="ph ph-books"></i> Enrolled Subjects <span id="evaluationPeriodLabel"></span></h3>
          <span id="evaluationSubjectCount">0 subjects</span>
        </div>
        <div id="facultyContainer" class="faculty-container"></div>
      </div>
      <aside class="evaluation-summary" aria-label="Evaluation summary">
        <h3>Evaluation Summary</h3>
        <div class="summary-progress">
          <strong id="summaryPercent">0%</strong>
          <span>Complete</span>
        </div>
        <div class="summary-stat summary-total"><span><i class="ph ph-circle"></i> Total Subjects</span><strong id="summaryTotal">0</strong></div>
        <div class="summary-stat summary-evaluated"><span><i class="ph ph-check-circle"></i> Evaluated</span><strong id="summaryEvaluated">0</strong></div>
        <div class="summary-stat summary-pending"><span><i class="ph ph-clock"></i> Pending</span><strong id="summaryPending">0</strong></div>
      </aside>
    </div>
  </div>
  <!-- Evaluation Form ======================================================================================-->
  <div id="evaluationContainer" style="display:none"></div>
</div>


<script src="../shared/assets/unified_notifications.js?v=<?=time()?>"></script>
<script src="../shared/assets/session_keepalive.js?v=<?=time()?>"></script>
<script src="../shared/assets/csrf.js?v=<?=time()?>"></script>
<script src="FacultyUser.js?v=<?=time()?>"></script>
</body>
</html>
