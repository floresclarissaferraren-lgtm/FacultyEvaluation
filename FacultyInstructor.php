<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header("Location: faculty_login.php");
    exit();
}

include 'connect.php';

// Fetch faculty data
$faculty_id = $_SESSION['id'];
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
    $name_parts = array_filter([$faculty['firstname'], $faculty['lastname']]);
    if (!empty($faculty['suffix'])) {
        $name_parts[] = $faculty['suffix'];
    }
    $faculty_name = implode(' ', $name_parts);
    $faculty_email = $faculty['email'] ?? '';
    $faculty_faculty_id = $faculty['faculty_id'] ?? '';
    $faculty_status = strtolower($faculty['status'] ?? 'active');
    $faculty_photo = $faculty['photo'] ?? '';
}
$stmt->close();

// Determine the image source: use stored base64 photo or fall back to default avatar
$default_avatar = "https://cdn-icons-png.flaticon.com/512/3135/3135755.png";
$faculty_img_src = (!empty($faculty_photo) && strpos($faculty_photo, 'data:image') === 0)
    ? $faculty_photo
    : $default_avatar;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System</title>

  <link rel="stylesheet" href="FacultyInstructor.css?v=<?=time()?>&fix=<?=rand(1000,9999)?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

</head>

<body>

<div class="navbar">
  <div class="brand">
    <img src="schoollogo.png" alt="Logo" class="navbar-logo">
    <span class="logo-text main-title">Faculty Evaluation System</span>
  </div>


  <div class="user-menu">
    <div class="instructor-box" onclick="toggleDropdown()">
      <img src="<?php echo htmlspecialchars($faculty_img_src); ?>" class="logo-img" alt="Profile Photo">
      <span>Instructor</span>
      <i class="ph ph-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" onclick="showPasswordForm(event)">
        <i class="ph ph-key"></i> Change Password
      </a>
      <a href="#" onclick="showProfile(event)">
        <i class="ph ph-user"></i> Profile
      </a>
      <a href="#" onclick="logout(event)">
        <i class="ph ph-sign-out"></i> Logout
      </a>
    </div>
  </div>
</div>

<div class="main-box" id="mainPage">
  <div class="main-left">
    <img src="<?php echo htmlspecialchars($faculty_img_src); ?>" alt="Welcome Image" class="main-img">
  </div>
  <div class="main-right">
    <h1>Welcome, <?php echo htmlspecialchars($faculty_name); ?></h1>
    <div class="academic-year">
      <i class="ph ph-calendar"></i> Academic Year: 2025–2026 • 2nd Semester
    </div>
    <p class="subtitle">
      Track your evaluation scores, analyze feedback, and enhance your 
      teaching strategies for better student engagement.
    </p>
    <?php if ($faculty_status !== 'active'): ?>
      <div class="inactive-account-message">
        <i class="ph ph-warning-circle"></i>
        <span>Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.</span>
      </div>
    <?php endif; ?>
  </div>
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
    <!-- Decorative waves background -->
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

<!-- Hidden fields -->
<input type="hidden" id="facultyId" value="<?php echo htmlspecialchars($faculty_faculty_id); ?>">
<input type="hidden" id="facultyNumericId" value="<?php echo htmlspecialchars($faculty_id); ?>">
<input type="hidden" id="facultyName" value="<?php echo htmlspecialchars($faculty_name); ?>">
<input type="hidden" id="facultyEmail" value="<?php echo htmlspecialchars($faculty_email); ?>">
<input type="hidden" id="facultyStatus" value="<?php echo htmlspecialchars($faculty_status); ?>">

<!-- Faculty Info Box -->
<div class="faculty-box">
  <div class="faculty-info-header">
    <div class="faculty-info">
      <span class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></span>
      <span class="faculty-id"><?php echo htmlspecialchars($faculty_faculty_id ?: 'N/A'); ?></span>
    </div>
    <button class="report-btn" onclick="showEvaluationReport()" <?php echo $faculty_status !== 'active' ? 'disabled' : ''; ?>>Report</button>
  </div>
  
  <div class="faculty-cards">
    <div class="card">
      <div class="card-info">
        <span class="card-title">Overall Rating</span>
        <div class="rating-container">
          <span class="rating-number" id="overallRatingNumber">0.00 / 5.00</span>
          <span class="rating-percentage" id="overallRatingPercentage">0%</span>
          <span class="rating-status" id="overallRatingStatus">No Data</span>
        </div>
        <div class="rating-progress-track">
          <div class="rating-progress-fill" id="overallRatingProgressFill" style="width:0%"></div>
        </div>
      </div>
      <i class="ph ph-star"></i>
    </div>

    <div class="card">
      <i class="ph ph-users-three"></i>
      <div class="card-info">
        <span class="card-title">Total Responses</span>
        <span class="card-value" id="totalResponsesValue">0</span>
      </div>
    </div>
</div>


<!-- JS FILE -->
<script src="FacultyInstructor.js?v=<?=time()?>"></script>

</body>
</html>
