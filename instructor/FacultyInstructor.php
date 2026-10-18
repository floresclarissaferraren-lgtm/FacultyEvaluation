<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../shared/security.php';
requireDashboardRole('faculty');

include __DIR__ . '/../shared/connect.php';
require_once __DIR__ . '/../shared/evaluation_period_helper.php';
require_once __DIR__ . '/../shared/class_period_helper.php';
ensureClassPeriodColumn($conn);

// Fetch faculty data
$faculty_id = $_SESSION['id'];
$evaluation_ongoing = isEvaluationOngoing($conn);

// Debug: Check what ID we're looking for
error_log("Faculty ID from session: " . $faculty_id);

$stmt = $conn->prepare("SELECT faculty_id, firstname, lastname, suffix, email, status, photo FROM add_faculties WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

$faculty_name = "Instructor"; // Default fallback
$faculty_email = ""; // Default fallback
$faculty_faculty_id = ""; // Default fallback
$faculty_status = "active";
$faculty_photo = ""; // Default fallback

if ($result->num_rows === 1) {
    $faculty = $result->fetch_assoc();
    
    // Debug: Log what we got from database
    error_log("Faculty data from DB: " . print_r($faculty, true));
    
    // Build full name from firstname and lastname
    $name_parts = [];
    if (!empty($faculty['firstname'])) {
        $name_parts[] = trim($faculty['firstname']);
    }
    if (!empty($faculty['lastname'])) {
        $name_parts[] = trim($faculty['lastname']);
    }
    if (!empty($faculty['suffix'])) {
        $name_parts[] = trim($faculty['suffix']);
    }
    
    // Combine name parts with space
    if (!empty($name_parts)) {
        $faculty_name = implode(' ', $name_parts);
    }
    
    // Debug: Log the final name
    error_log("Final faculty name: " . $faculty_name);
    
    $faculty_email = $faculty['email'] ?? '';
    $faculty_faculty_id = $faculty['faculty_id'] ?? '';
    $faculty_status = strtolower($faculty['status'] ?? 'active');
    $faculty_photo = $faculty['photo'] ?? '';
} else {
    error_log("No faculty found with ID: " . $faculty_id . " (Found " . $result->num_rows . " rows)");
}
$stmt->close();

// Determine the image source: use stored base64 photo or fall back to default avatar
$default_avatar = "https://cdn-icons-png.flaticon.com/512/3135/3135755.png";
$faculty_img_src = (!empty($faculty_photo) && strpos($faculty_photo, 'data:image') === 0)
    ? $faculty_photo
    : $default_avatar;

$faculty_assigned_subjects = [];
$subjectSql = <<<'SQL'
  SELECT DISTINCT s.subject_code, s.subject_desc, s.year_level,
    p.program_code, p.program_name
  FROM class_subjects cs
  INNER JOIN add_classes ac ON ac.id = cs.class_id
  INNER JOIN add_subjects s ON s.id = cs.subject_id
  LEFT JOIN add_programs p ON p.id = s.program_id
  WHERE cs.faculty_id = ?
    AND ac.period_id = (SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1)
  ORDER BY s.year_level ASC, s.subject_code ASC
SQL;
$subjectStmt = $conn->prepare($subjectSql);
if ($subjectStmt) {
  $subjectStmt->bind_param("i", $faculty_id);
  $subjectStmt->execute();
  $subjectResult = $subjectStmt->get_result();
  while ($subject = $subjectResult->fetch_assoc()) {
    $faculty_assigned_subjects[] = [
      'code' => $subject['subject_code'],
      'name' => $subject['subject_desc'],
      'year_level' => $subject['year_level'],
      'program_code' => $subject['program_code'] ?: 'N/A',
      'program_name' => $subject['program_name'] ?: 'Unassigned Program'
    ];
  }
  $subjectStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <title>Faculty Evaluation System</title>

  <link rel="stylesheet" href="FacultyInstructor.css?v=<?=time()?>&fix=<?=rand(1000,9999)?>">
  <link rel="stylesheet" href="../shared/assets/unified_notifications.css?v=<?=time()?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">

</head>

<body>

<div class="navbar">
  <div class="brand">
    <img src="../assets/images/schoollogo.png" alt="Logo" class="navbar-logo">
    <div class="brand-text">
      <span class="logo-text main-title">Faculty Evaluation System</span>
      <span class="logo-subtitle">INSTRUCTOR PORTAL</span>
    </div>
  </div>


  <div class="user-menu">
    <div class="instructor-box" onclick="toggleDropdown()">
      <span class="instructor-account-icon" aria-hidden="true">IP</span>
      <span class="student-label">Instructor</span>
      <i class="ph ph-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <div class="dropdown-user-info">
        <div class="dropdown-avatar">
          <span class="dropdown-avatar-initials"><?php 
            $names = explode(' ', $faculty_name);
            echo strtoupper(substr($names[0] ?? 'I', 0, 1) . substr($names[1] ?? 'N', 0, 1));
          ?></span>
        </div>
        <div class="dropdown-user-details">
          <div class="dropdown-user-name"><?php echo htmlspecialchars($faculty_name); ?></div>
          <div class="dropdown-user-email"><?php echo htmlspecialchars($faculty_email); ?></div>
        </div>
      </div>
      <a href="#" onclick="showPasswordForm(event)">
        <i class="ph ph-key"></i> Change Password
      </a>
      <a href="#" onclick="showProfile(event)">
        <i class="ph ph-user"></i> Profile
      </a>
      <hr class="dropdown-divider">
      <a href="#" class="logout-link" onclick="logout(event)">
        <i class="ph ph-sign-out"></i> Logout
      </a>
    </div>
  </div>
</div>

<div class="content-container">
  <!-- Welcome Card -->
  <div class="welcome-card">
    <span id="instructor-evaluation-status" class="evaluation-period-status is-checking" role="status" aria-live="polite">
      <i class="ph ph-circle-notch" aria-hidden="true"></i>
      Checking evaluation status
    </span>
    <div class="welcome-content">
      <div class="welcome-right">
        <div class="welcome-header-inline">
          <h1 class="student-name-title">Welcome, <?php echo htmlspecialchars($faculty_name); ?>!</h1>
          <!-- Debug info (remove this after fixing) -->
          <?php if ($faculty_name === 'Instructor'): ?>
          <div style="background:#ffebee;padding:10px;border-radius:8px;margin-top:10px;font-size:12px;color:#c62828;">
            <strong>Debug:</strong> Faculty ID from session: <?php echo $faculty_id; ?><br>
            Please check if this faculty exists in the database with firstname and lastname filled in.
          </div>
          <?php endif; ?>
          <span id="instructorAcademicPeriod" class="welcome-period-subtitle" hidden aria-busy="true">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path fill="currentColor" d="M3 5.75A1.75 1.75 0 0 1 4.75 4h5.086c.464 0 .91.184 1.238.512l1.414 1.414c.14.14.33.219.528.219h6.234A1.75 1.75 0 0 1 21 7.895v10.355A1.75 1.75 0 0 1 19.25 20H4.75A1.75 1.75 0 0 1 3 18.25z"/>
            </svg>
            <span class="welcome-period-label"></span>
          </span>
        </div>
        
        <p class="welcome-message">
          Track your evaluation scores, review student feedback, and refine your teaching strategies to improve teaching effectiveness and support better academic outcomes.
        </p>
        
        <?php if ($faculty_status !== 'active'): ?>
        <div class="inactive-account-message">
          <i class="ph ph-warning-circle"></i>
          <span>Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.</span>
        </div>
        <?php elseif ($evaluation_ongoing): ?>
        <div class="inactive-account-message">
          <i class="ph ph-warning-circle"></i>
          <span>Evaluation results will be available after the current evaluation process closes.</span>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
          <button type="button" id="evaluationReportButton" class="action-btn primary-btn" disabled>
            <i class="ph ph-chart-bar"></i>
            Evaluation Report
          </button>
          <button type="button" id="perSubjectReportButton" class="action-btn secondary-btn" disabled>
            <i class="ph ph-note"></i>
            Per-Subject Report
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="stats-cards-container">
    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-header">
          <span class="stat-label">OVERALL RATING</span>
          <div class="stat-icon-small yellow">
            <i class="ph-fill ph-star"></i>
          </div>
        </div>
        <div class="stat-main">
          <span class="stat-value" id="overallRatingNumber">0.00</span>
          <span class="stat-unit">/ 5.00</span>
        </div>
        <span class="stat-extra" id="overallRatingStatus">Performance Status</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-header">
          <span class="stat-label">STUDENT RESPONDENTS</span>
          <div class="stat-icon-small blue">
            <i class="ph-fill ph-users-three"></i>
          </div>
        </div>
        <div class="stat-main">
          <span class="stat-value" id="totalResponsesValue">0</span>
        </div>
        <span class="stat-extra" id="responsePeriodNote">Students participated</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-header">
          <span class="stat-label">SUBJECTS EVALUATED</span>
          <div class="stat-icon-small purple">
            <i class="ph-fill ph-book-open"></i>
          </div>
        </div>
        <div class="stat-main">
          <span class="stat-value" id="subjectsEvaluatedValue">0</span>
        </div>
        <span class="stat-extra" id="subjectPeriodNote">With student responses</span>
      </div>
    </div>
  </div>

</div>

<!-- Hidden fields -->
<input type="hidden" id="facultyId" value="<?php echo htmlspecialchars($faculty_faculty_id); ?>">
<input type="hidden" id="facultyNumericId" value="<?php echo htmlspecialchars($faculty_id); ?>">
<input type="hidden" id="facultyName" value="<?php echo htmlspecialchars($faculty_name); ?>">
<input type="hidden" id="facultyEmail" value="<?php echo htmlspecialchars($faculty_email); ?>">
<input type="hidden" id="facultyStatus" value="<?php echo htmlspecialchars($faculty_status); ?>">
<input type="hidden" id="evaluationOngoing" value="<?php echo $evaluation_ongoing ? '1' : '0'; ?>">
<script>
  window.facultyAssignedSubjects = <?php echo json_encode($faculty_assigned_subjects, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
</script>
</div>

<div id="logoutModal" class="logoutform">
  <div class="logout-content">
    <div class="logout-icon">
      <span class="icon-bg"><i class="ph ph-sign-out"></i></span>
    </div>
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
          <div class="input-wrap">
            <input type="password" id="oldPass" placeholder="Old Password" required>
            <div class="password-toggle" onclick="togglePassword('oldPass', this)">
              <i class="ph ph-eye-slash"></i>
            </div>
          </div>
          <small id="oldPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <div class="input-wrap">
            <input type="password" id="newPass" placeholder="New Password" required>
            <div class="password-toggle" onclick="togglePassword('newPass', this)">
              <i class="ph ph-eye-slash"></i>
            </div>
          </div>
          <small id="newPassError" class="error-message"></small>
        </div>
        
        <div class="modern-input-group has-toggle">
          <div class="input-wrap">
            <input type="password" id="confirmPass" placeholder="Confirm Password" required>
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


<!-- Per-Subject Report Modal (populated by JS) -->
<div id="perSubjectReportModal" class="per-subject-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="perSubjectModalTitle">
  <div class="per-subject-modal-content">
    <!-- filled dynamically by showPerSubjectReport() -->
  </div>
</div>

<!-- JS FILE -->
<script src="../shared/assets/unified_notifications.js?v=<?=time()?>"></script>
<script src="../shared/assets/session_keepalive.js?v=<?=time()?>"></script>
<script src="../shared/assets/csrf.js?v=<?=time()?>"></script>
<script src="FacultyInstructor.js?v=<?=time()?>"></script>

</body>
</html>
