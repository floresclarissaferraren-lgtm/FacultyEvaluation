<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System - Admin Panel</title>
  <link rel="stylesheet" href="FacultyUser.css?v=<?=time()?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Navbar ======================================================================================-->

<div class="navbar">
  <div class="brand"> <i class="fas fa-graduation-cap"></i><span class="logo-text main-title">FaculRate</span></div>
  <div class="user-menu">
    <div class="student-box" onclick="toggleDropdown()"><img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Student Logo" class="logo-img">
        <span>Student</span><i class="fas fa-caret-down"></i></div>
    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" onclick="showPasswordForm()"><i class="fas fa-key"></i> Change Password</a>
      <a href="#" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</div>

<!-- Logout ======================================================================================-->

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

    <!-- Hidden field to store student ID -->
<input type="hidden" id="studentId" value="<?php echo $row['student_number']; ?>">

    <div class="password-actions">
      <button class="cancel-btn" onclick="closePasswordForm()">Cancel</button>
      <button class="update-btn" onclick="updatePassword()">Update</button>
    </div>
  </div>
</div>


<!-- Main Page ======================================================================================-->

<div class="main-box" id="mainPage">
  <div class="main-left"><img src="schoollogo.png" alt="School Logo" class="main-img"></div>
  <div class="main-right">
    <h1>Welcome, Students</h1>
    <div class="academic-year"><i class="fas fa-calendar-alt"></i> Academic Year: 2025–2026 • 2nd Semester</div>
    <p class="subtitle">Your feedback is essential in helping us improve teaching and learning. 
      Each evaluation you complete strengthens our commitment to academic excellence.</p>
    <button class="evaluate-btn" onclick="showEvaluateSection()"><i class="fas fa-check-circle"></i> Evaluate Now</button>
  </div>
</div>

<!-- Evaluation Box======================================================================================-->
<div class="evaluate-section" id="evaluateSection" style="display:none;">
  <div class="eval-box">
    <div class="eval-header">
      <div class="title-subtitle">
        <h1>Faculty Evaluation</h1>
        <!-- Header Section 
        <div class="academic-year-eval"><i class="fas fa-calendar-alt"></i> Academic Year: 2025–2026 • 2nd Semester</div> -->
      </div>
      <button class="back-btn" onclick="goBackToMain()"><i class="fas fa-arrow-left"></i> Back</button>
    </div>

    <div class="faculty-select">
      <label for="facultyDropdown"><i class="fas fa-user-tie"></i> Select Faculty:</label>
      <select id="facultyDropdown" class="faculty-dropdown"><option value="">-- No faculty available --</option></select>
    </div>
  </div>
</div>

<!-- Rating Legends Box ======================================================================================-->
<div class="rating-legends" id="ratingLegends" style="display:none;">
  <h2>Rating Legends</h2>
  <ul>
    <li><span class="dot dot5"></span> 5 - Strongly Agree</li>
    <li><span class="dot dot4"></span> 4 - Agree</li>
    <li><span class="dot dot3"></span> 3 - Neutral</li>
    <li><span class="dot dot2"></span> 2 - Disagree</li>
    <li><span class="dot dot1"></span> 1 - Strongly Disagree</li>
  </ul>
</div>

<!-- Evaluation Form ======================================================================================-->
<div id="evaluationContainer" style="display:none"></div>

<div class="feedback-container">
  <div class="feedback-box">
    <label for="studentFeedback">OPTIONAL COMMENTS</label>
    <textarea id="studentFeedback" placeholder="Type your feedback here..."></textarea>
  </div>
</div>

<div class="submit-container">
  <button id="submitEvaluation" onclick="submitEvaluation()">
    SUBMIT EVALUATION <i class="fa-solid fa-paper-plane"></i>
  </button>
</div>



<script src="FacultyUser.js?v=<?=time()?>"></script>
</body>
</html>






