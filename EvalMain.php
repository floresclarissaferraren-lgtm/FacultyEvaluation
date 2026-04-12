<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Evaluation System</title>
<link rel="stylesheet" href="EvalMain.css?v=<?=time()?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR ==============================================================================================================-->
<nav>
<div class="logo-container">
<img class="logo-img" src="schoollogo.png">
<div class="logo-text"><span class="main-title">FaculRate</span><span class="sub-title">Granby Colleges of Science and Technology</span></div>
</div>
<ul id="navMenu">
<li><a href="#home"><span class="material-icons">home</span>Home</a></li>
<li><a href="#about"><span class="material-icons">info</span>About</a></li>
<li><a href="#contact"><span class="material-icons">mail</span>Contact</a></li>
<li class="dropdown">
<div class="profile-btn"><span class="material-icons">account_circle</span>My Profile<span class="material-icons">expand_more</span></div>
<div class="dropdown-menu">
<a href="#"><span class="material-icons">admin_panel_settings</span>Admin</a>
<a href="#"><span class="material-icons">school</span>Instructor</a>
<a href="#"><span class="material-icons">person</span>Student</a>
</div>
</li>
</ul>
<div class="hamburger" id="hamburger"><span class="material-icons">menu</span></div>
</nav>

<!-- HOME ==============================================================================================================-->
<section id="home" class="home">
<div class="home-text">
<h1>Improving Teaching Quality Through Smart Evaluation</h1>
<p>The Faculty Evaluation System provides a digital platform where students, faculty, and admins participate
     in a transparent evaluation process supporting academic improvement.</p>
</div>
<img class="home-image" src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png">
</section>

<!-- ABOUT ============================================================================================================== -->
<section id="about" class="about">
<h2>About the System</h2>
<p class="about-desc">The Faculty Evaluation System replaces paper evaluations with a centralized digital platform.</p>
<div class="about-vmg">
<div class="vmg-card"><span class="material-icons" style="font-size:36px;color:#2563eb;margin-bottom:8px;">visibility</span>
    <h3>Vision</h3><p>To be a leading platform recognized for excellence in teaching and evaluation.</p></div>
<div class="vmg-card"><span class="material-icons" style="font-size:36px;color:#16a34a;margin-bottom:8px;">track_changes</span>
    <h3>Mission</h3><p>To provide a transparent system that improves teaching quality and supports academic growth.</p></div>
<div class="vmg-card"><span class="material-icons" style="font-size:36px;color:#f59e0b;margin-bottom:8px;">flag</span>
    <h3>Goal</h3><p>Empower students, faculty, and admins through clear feedback and actionable insights.</p></div>
</div>
<div class="about-grid">
<div class="about-card"><span class="material-icons about-icon">rate_review</span><h3>Student Evaluation</h3><p>Students evaluate instructors online.</p></div>
<div class="about-card"><span class="material-icons about-icon">dashboard</span><h3>Instructor Dashboard</h3><p>Faculty members view evaluation results.</p></div>
<div class="about-card"><span class="material-icons about-icon">bar_chart</span><h3>Performance Reports</h3><p>Evaluation scores calculated automatically.</p></div>
<div class="about-card"><span class="material-icons about-icon">admin_panel_settings</span><h3>Administrative Control</h3><p>Admins manage records and forms.</p></div>
</div>
</section>

<!-- CONTACT ==============================================================================================================-->
<section id="contact" class="contact">
<h2>Contact Information</h2>
<p class="contact-desc">Questions? Reach out to us.</p>
<div class="contact-container">
<div class="contact-info">
<div class="info-box"><span class="material-icons">location_on</span><p>Granby Colleges of Science and Technology</p></div>
<div class="info-box"><span class="material-icons">call</span><p>+63 912 345 6789</p></div>
<div class="info-box"><span class="material-icons">email</span><p>support@granbycollege.edu</p></div>
</div>
<form class="contact-form">
<input type="text" placeholder="Your Name" required>
<input type="email" placeholder="Your Email" required>
<textarea placeholder="Your Message" rows="5"></textarea>
<button type="submit" class="send-btn"><span class="material-icons">send</span>Send Message</button>
</form>
</div>
</section>

<footer>© 2026 Faculty Evaluation System | All Rights Reserved</footer>

<!-- STUDENT LOGIN ==============================================================================================================-->
<div class="LoginForm" id="studentLoginModal">
  <div class="LoginForm-content">
    <div class="LoginForm-header">
      <img src="schoollogo.png" class="logo">
      <div class="back-btn" id="closeStudentLogin">Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div>
    </div>
    <h2>Student Login</h2>
    <p class="LoginForm-subtitle"><span></span>Enter your account details<span></span></p>
    <form id="studentLoginForm">
      <div class="input-group">
        <i class="fa-solid fa-user"></i>
        <input type="text" id="studentNumber" placeholder="Student ID" required>
        <small id="studentNumberError" class="error-message"></small>
      </div>
      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" id="studentPassword" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
        <small id="studentPasswordError" class="error-message"></small>
      </div>
      <a href="#" id="openStudentForgot" class="StudentLogin-link">Forgot Password?</a>
      <button type="submit" class="StudentLogin-btn">Login</button>
    </form>
  </div>
</div>



<div class="LoginForm" id="studentForgotModal">
<div class="LoginForm-content">
<div class="LoginForm-header"><img src="schoollogo.png" class="logo"><div class="back-btn" id="closeStudentForgot">
    Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div></div>
<h2>Reset Password</h2>
<p class="LoginForm-subtitle"><span></span>Enter your email to reset<span></span></p>
<form>
<div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" placeholder="Student Email" required></div>
<button type="submit" class="StudentLogin-btn">Send Reset Link</button>
</form>
</div>
</div>

<!-- INSTRUCTOR LOGIN ==============================================================================================================-->
<div class="LoginForm" id="instructorLoginModal">
  <div class="LoginForm-content">
    <div class="LoginForm-header">
      <img src="schoollogo.png" class="logo">
      <div class="back-btn" id="closeInstructorLogin">Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div>
    </div>
    <h2>Instructor Login</h2>
    <p class="LoginForm-subtitle"><span></span>Enter your credentials<span></span></p>
    <form>
      <div class="input-group">
        <i class="fa-solid fa-chalkboard-user"></i>
        <input type="text" placeholder="Instructor ID" required>
      </div>
      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" id="instructorPassword" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
      </div>
      <a href="#" id="openInstructorForgot" class="StudentLogin-link">Forgot Password?</a>
      <button type="submit" class="StudentLogin-btn">Login</button>
    </form>
  </div>
</div>

<div class="LoginForm" id="instructorForgotModal">
<div class="LoginForm-content">
<div class="LoginForm-header"><img src="schoollogo.png" class="logo"><div class="back-btn" id="closeInstructorForgot">
    Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div></div>
<h2>Reset Password</h2>
<p class="LoginForm-subtitle"><span></span>Enter your email to reset<span></span></p>
<form>
<div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" placeholder="Instructor Email" required></div>
<button type="submit" class="StudentLogin-btn">Send Reset Link</button>
</form>
</div>
</div>

<!-- ADMIN LOGIN ==============================================================================================================-->
<div class="LoginForm" id="adminLoginModal">
  <div class="LoginForm-content">
    <div class="LoginForm-header">
      <img src="schoollogo.png" class="logo">
      <div class="back-btn" id="closeAdminLogin">
        Back<i class="fa-solid fa-arrow-up-right-from-square"></i>
      </div>
    </div>
    <h2>Admin Login</h2>
    <p class="LoginForm-subtitle"><span></span>Enter admin credentials<span></span></p>
    <form id="adminLoginForm">
      <div class="input-group">
        <i class="fa-solid fa-user-gear"></i>
        <input type="text" name="username" placeholder="Admin ID" required>
      </div>
      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" id="adminPassword" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash" id="togglePassword" style="cursor:pointer;"></i>
      </div>
      <button type="submit" class="StudentLogin-btn">Login</button>
    </form>
  </div>
</div>

<script src="/FacultyEvaluation/Evalmain.js?v=<?=time()?>"></script>
</body>
</html>