<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faculty Evaluation System</title>
<link rel="stylesheet" href="EvalMain.css?v=<?=time()?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

</head>
<body>

<!-- NAVBAR ==============================================================================================================-->
<nav>
  <div class="logo-container">
    <img class="logo-img" src="schoollogo.png">
    <div class="logo-text">
      <span class="main-title">Faculty Evaluation</span>
      <span class="sub-title">Granby Colleges of Science and Technology</span>
    </div>
  </div>

  <ul id="navMenu">
    <li><a href="#home"><i class="ph ph-house"></i> Home</a></li>
    <li><a href="#about"><i class="ph ph-info"></i> About</a></li>
    <li><a href="#contact"><i class="ph ph-envelope"></i> Contact</a></li>
    <li><a href="#" id="loginBtn" class="login-btn"><i class="ph ph-sign-in"></i> Login</a></li>
  </ul>

  <div class="hamburger" id="hamburger">
    <i class="ph ph-list"></i>
  </div>
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
    <div class="vmg-card">
      <i class="ph ph-eye" style="font-size:36px;color:#2563eb;margin-bottom:8px;"></i>
      <h3>Vision</h3>
      <p>To be a leading platform recognized for excellence in teaching and evaluation.</p>
    </div>
    <div class="vmg-card">
      <i class="ph ph-target" style="font-size:36px;color:#16a34a;margin-bottom:8px;"></i>
      <h3>Mission</h3>
      <p>To provide a transparent system that improves teaching quality and supports academic growth.</p>
    </div>
    <div class="vmg-card">
      <i class="ph ph-flag" style="font-size:36px;color:#f59e0b;margin-bottom:8px;"></i>
      <h3>Goal</h3>
      <p>Empower students, faculty, and admins through clear feedback and actionable insights.</p>
    </div>
  </div>

  <div class="about-grid">
    <div class="about-card"><i class="ph ph-note-pencil about-icon"></i><h3>Student Evaluation</h3><p>Students evaluate instructors online.</p></div>
    <div class="about-card"><i class="ph ph-layout about-icon"></i><h3>Instructor Dashboard</h3><p>Faculty members view evaluation results.</p></div>
    <div class="about-card"><i class="ph ph-chart-bar about-icon"></i><h3>Performance Reports</h3><p>Evaluation scores calculated automatically.</p></div>
    <div class="about-card"><i class="ph ph-shield-check about-icon"></i><h3>Administrative Control</h3><p>Admins manage records and forms.</p></div>
  </div>
</section>


<!-- CONTACT ==============================================================================================================-->
<section id="contact" class="contact">
  <h2>Contact Information</h2>
  <p class="contact-desc">Questions? Reach out to us.</p>
  <div class="contact-container">
    <div class="contact-info">
      <div class="info-box"><i class="ph ph-map-pin"></i><p>Granby Colleges of Science and Technology</p></div>
      <div class="info-box"><i class="ph ph-phone"></i><p>+63 912 345 6789</p></div>
      <div class="info-box"><i class="ph ph-envelope"></i><p>support@granbycollege.edu</p></div>
    </div>
    <form class="contact-form">
      <input type="text" placeholder="Your Name" required>
      <input type="email" placeholder="Your Email" required>
      <textarea placeholder="Your Message" rows="5"></textarea>
      <button type="submit" class="send-btn"><i class="ph ph-paper-plane-right"></i> Send Message</button>
    </form>
  </div>
</section>

<footer>© 2026 Faculty Evaluation System | All Rights Reserved</footer>


<!-- LOGIN ==============================================================================================================-->
<div class="LoginForm" id="loginModal">
  <div class="LoginForm-content">
    <div class="LoginForm-header">
      <img src="schoollogo.png" class="logo">
      <div class="back-btn" id="closeLogin">Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div>
    </div>
    <h2>Login</h2>
    <p class="LoginForm-subtitle"><span></span>Enter your credentials<span></span></p>
    <form id="loginForm">
      <div class="input-group">
        <i class="fa-solid fa-user"></i>
        <input type="text" id="username" placeholder="Username/ID" required>
        <small id="usernameError" class="error-message"></small>
      </div>
      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" id="password" placeholder="Password" required>
        <i class="fa-solid fa-eye-slash" id="togglePassword"></i>
        <small id="passwordError" class="error-message"></small>
      </div>
      <a href="#" id="openForgot" class="StudentLogin-link">Forgot Password?</a>
      <button type="submit" class="StudentLogin-btn">Login</button>
    </form>
  </div>
</div>

<!-- FORGOT PASSWORD ==============================================================================================================-->
<div class="LoginForm" id="forgotModal">
<div class="LoginForm-content">
<div class="LoginForm-header"><img src="schoollogo.png" class="logo"><div class="back-btn" id="closeForgot">
    Back<i class="fa-solid fa-arrow-up-right-from-square"></i></div></div>
<h2>Reset Password</h2>
<p class="LoginForm-subtitle"><span></span>Enter your email to reset<span></span></p>
<form id="forgotForm">
<div class="input-group"><i class="fa-solid fa-envelope"></i><input type="email" id="resetEmail" placeholder="Email" required></div>
<button type="submit" class="StudentLogin-btn">Send Reset Link</button>
</form>
</div>
</div>

<script src="EvalMain.js?v=<?php echo time(); ?>"></script>
</body>
</html>