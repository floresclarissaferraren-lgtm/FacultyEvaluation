<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System</title>

  <link rel="stylesheet" href="FacultyInstructor.css?v=<?=time()?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="navbar">
  <div class="brand">
    <i class="fas fa-graduation-cap"></i>
    <span class="logo-text main-title">FaculRate</span>
  </div>

  <div class="user-menu">
    <div class="instructor-box" onclick="toggleDropdown()">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" class="logo-img">
      <span>Instructor</span>
      <i class="fas fa-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" onclick="showPasswordForm()">
        <i class="fas fa-key"></i> Change Password
      </a>
      <a href="#" onclick="logout(event)">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</div>

<div class="main-box" id="mainPage">
  <div class="main-left"><img src="schoollogo.png" alt="School Logo" class="main-img"></div>
  <div class="main-right">
    <h1>Welcome, Instructor</h1>
    <div class="academic-year"><i class="fas fa-calendar-alt"></i> Academic Year: 2025–2026 • 2nd Semester</div>
    <p class="subtitle">Track your evaluation scores, analyze feedback, and enhance your 
      teaching strategies for better student engagement.</p>
  </div>
</div>


<div id="logoutModal" class="logoutform">
  <div class="logout-content">
    <div class="logout-icon"><span class="icon-bg"><i class="fas fa-sign-out-alt fa-2x"></i></span></div>
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
        <i class="fas fa-user-lock password-icon"></i>
      </div>
      <div class="password-title">Change Password</div>
    </div>

    <div class="password-body">
      <div class="password-field">
        <input id="oldPass" type="password" placeholder="Old Password">
        <i class="fa-solid fa-eye-slash toggle" onclick="togglePassword('oldPass', this)"></i>
      </div>
      <div class="password-field">
        <input id="newPass" type="password" placeholder="New Password">
        <i class="fa-solid fa-eye-slash toggle" onclick="togglePassword('newPass', this)"></i>
      </div>
      <div class="password-field">
        <input id="confirmPass" type="password" placeholder="Confirm Password">
        <i class="fa-solid fa-eye-slash toggle" onclick="togglePassword('confirmPass', this)"></i>
      </div>
    </div>

<input type="hidden" id="studentId" value="<?php echo $row['student_number']; ?>">

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
      <span class="faculty-id"></strong> FAC-0001:</span>
      <span class="faculty-name"></strong> Juan Dela Cruz</span>
    </div>
    <button class="report-btn">Generate Report</button>
  </div>

 <div class="faculty-cards">
  <div class="card">
    <i class="fas fa-star"></i>
    <div class="card-info">
      <span class="card-title">Overall Ratings</span>
      <span class="card-value">4.50 / 5.00 - Outstanding</span>
    </div>
  </div>

  <div class="card">
    <i class="fas fa-users"></i>
    <div class="card-info">
      <span class="card-title">Total Responses</span>
      <span class="card-value">67</span>
    </div>
  </div>
</div>

</div>


<!-- JS FILE -->
<script src="FacultyInstructor.js?v=<?=time()?>"></script>

</body>
</html>