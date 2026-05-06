<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header("Location: faculty_login.php");
    exit();
}

include 'connect.php';

// Fetch faculty data
$faculty_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT faculty_id, firstname, lastname, suffix, email FROM add_faculties WHERE id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

$faculty_name = "Instructor"; // Default fallback
$faculty_email = ""; // Default fallback
$faculty_faculty_id = ""; // Default fallback
if ($result->num_rows === 1) {
    $faculty = $result->fetch_assoc();
    $name_parts = array_filter([$faculty['firstname'], $faculty['lastname']]);
    if (!empty($faculty['suffix'])) {
        $name_parts[] = $faculty['suffix'];
    }
    $faculty_name = implode(' ', $name_parts);
    $faculty_email = $faculty['email'] ?? '';
    $faculty_faculty_id = $faculty['faculty_id'] ?? '';
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System</title>

  <link rel="stylesheet" href="FacultyInstructor.css?v=<?=time()?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

</head>

<body>

<div class="navbar">
  <div class="brand">
    <i class="ph ph-graduation-cap"></i>
    <span class="logo-text main-title">Faculty Evaluation System</span>
  </div>


  <div class="user-menu">
    <div class="instructor-box" onclick="toggleDropdown()">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" class="logo-img">
      <span>Instructor</span>
      <i class="ph ph-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" onclick="showPasswordForm()">
        <i class="ph ph-key"></i> Change Password
      </a>
      <a href="#" onclick="showProfile()">
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
    <img src="schoollogo.png" alt="School Logo" class="main-img">
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

<div id="passwordForm" class="passwordForm">
  <div class="password-box">
    <div class="password-icon-container">
      <div class="password-icon-circle">
        <i class="ph ph-lock-key"></i>
      </div>
      <div class="password-title">Change Password</div>
    </div>

    <div class="password-body">
      <div class="password-field">
        <input id="oldPass" type="password" placeholder="Old Password">
        <i class="ph ph-eye-slash toggle" onclick="togglePassword('oldPass', this)"></i>
      </div>
      <div class="password-field">
        <input id="newPass" type="password" placeholder="New Password">
        <i class="ph ph-eye-slash toggle" onclick="togglePassword('newPass', this)"></i>
      </div>
      <div class="password-field">
        <input id="confirmPass" type="password" placeholder="Confirm Password">
        <i class="ph ph-eye-slash toggle" onclick="togglePassword('confirmPass', this)"></i>
      </div>
    </div>

    <input type="hidden" id="facultyId" value="<?php echo htmlspecialchars($faculty_id); ?>">
    <input type="hidden" id="facultyName" value="<?php echo htmlspecialchars($faculty_name); ?>">
    <input type="hidden" id="facultyEmail" value="<?php echo htmlspecialchars($faculty_email); ?>">

    <div class="password-actions">
      <button class="cancel-btn" onclick="closePasswordForm()">Cancel</button>
      <button class="update-btn" onclick="updatePassword()">Update</button>
    </div>
  </div>
</div>

<!-- Faculty Info Box -->
<div class="faculty-box">
  <div class="faculty-header">
    <div class="faculty-info">
      <span class="faculty-id"><?php echo htmlspecialchars($faculty_faculty_id ?: 'N/A'); ?>:</span>
      <span class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></span>
    </div>
    <button class="report-btn">Generate Report</button>
  </div>

  <div class="faculty-cards">
    <div class="card">
      <i class="ph ph-star"></i>
      <div class="card-info">
        <span class="card-title">Overall Ratings</span>
        <span class="card-value" id="overallRatingValue">0.00 / 5.00 - No Data</span>
      </div>
    </div>

    <div class="card">
      <i class="ph ph-users-three"></i>
      <div class="card-info">
        <span class="card-title">Total Responses</span>
        <span class="card-value" id="totalResponsesValue">0</span>
      </div>
    </div>
  </div>
</div>


<!-- JS FILE -->
<script src="FacultyInstructor.js?v=<?=time()?>"></script>

</body>
</html>
