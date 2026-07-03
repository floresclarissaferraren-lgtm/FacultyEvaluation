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

<!-- NAVBAR -->
<nav>
  <div class="logo-container">
    <img class="logo-img" src="schoollogo.png">
    <div class="logo-text">
      <span class="main-title">Faculty Evaluation</span>
      <span class="sub-title">Granby Colleges of Science and Technology</span>
    </div>
  </div>
  <ul id="navMenu">
    <li><a href="#home"         data-page="0"><i class="ph ph-house"></i> Home</a></li>
    <li><a href="#about"        data-page="1"><i class="ph ph-info"></i> About</a></li>
    <li><a href="#how-it-works" data-page="2"><i class="ph ph-list-checks"></i> How it works</a></li>
    <li><a href="#contact"      data-page="3"><i class="ph ph-envelope"></i> Contact</a></li>
    <li><a href="#" id="loginBtn" class="login-btn"><i class="ph ph-sign-in"></i> Login</a></li>
  </ul>
  <div class="hamburger" id="hamburger"><i class="ph ph-list"></i></div>
</nav>

<!-- PAGE WRAPPER -->
<div class="page-wrapper" id="pageWrapper">

  <!-- ── PAGE 0 : HOME ── -->
  <div class="page" id="home">
    <div class="page-inner home-inner">
      <div class="home-text">
        <div class="hero-badge"><i class="ph ph-seal-check"></i> Granby Colleges of Science and Technology</div>
        <h1>Improving Teaching Quality Through <span>Smart Evaluation</span></h1>
        <p>A centralized digital platform where students, faculty, and administrators
           collaborate in a transparent and data-driven evaluation process.</p>
        <div class="hero-actions">
          <a href="#" id="loginBtn2" class="btn-primary"><i class="ph ph-sign-in"></i> Get Started</a>
          <a href="#" class="btn-ghost page-link" data-page="1"><i class="ph ph-info"></i> Learn More</a>
        </div>
      </div>
    </div>
    <!-- Scroll indicator -->
    <div class="scroll-indicator page-link" data-page="1">
      <i class="ph ph-caret-down"></i>
    </div>
  </div>

  <!-- ── PAGE 1 : ABOUT ── -->
  <div class="page" id="about">
    <div class="page-inner about-inner">
      <div class="section-label"><i class="ph ph-info"></i> About the System</div>
      <h2 class="section-heading">Built for Academic Excellence</h2>
      <p class="section-sub">The Faculty Evaluation System replaces paper evaluations with a centralized digital platform — faster, fairer, and more transparent.</p>

      <div class="about-vmg">
        <div class="vmg-card">
          <div class="vmg-icon blue"><i class="ph ph-eye"></i></div>
          <h3>Vision</h3>
          <p>To be a leading platform recognized for excellence in teaching and evaluation.</p>
        </div>
        <div class="vmg-card">
          <div class="vmg-icon green"><i class="ph ph-target"></i></div>
          <h3>Mission</h3>
          <p>To provide a transparent system that improves teaching quality and supports academic growth.</p>
        </div>
        <div class="vmg-card">
          <div class="vmg-icon amber"><i class="ph ph-flag"></i></div>
          <h3>Goal</h3>
          <p>Empower students, faculty, and admins through clear feedback and actionable insights.</p>
        </div>
      </div>

      <div class="about-grid">
        <div class="about-card"><div class="about-icon"><i class="ph ph-note-pencil"></i></div><h3>Student Evaluation</h3><p>Students evaluate instructors online anytime during the evaluation period.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-layout"></i></div><h3>Instructor Dashboard</h3><p>Faculty members view their evaluation results and performance analytics.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-chart-bar"></i></div><h3>Performance Reports</h3><p>Evaluation scores are calculated automatically and available instantly.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-shield-check"></i></div><h3>Administrative Control</h3><p>Admins manage records, evaluation criteria, and system settings.</p></div>
      </div>
    </div>
    <!-- Quick links -->
    <div class="quick-links">
      <span class="ql-label">Quick Links</span>
      <a href="#" class="ql-item page-link" data-page="0"><i class="ph ph-house"></i><span>Home</span></a>
      <a href="#" class="ql-item page-link" data-page="2"><i class="ph ph-list-checks"></i><span>How It Works</span></a>
      <a href="#" class="ql-item page-link" data-page="3"><i class="ph ph-envelope"></i><span>Contact</span></a>
    </div>
    <div class="scroll-indicator page-link" data-page="2"><i class="ph ph-caret-down"></i></div>
  </div>

  <!-- ── PAGE 2 : HOW IT WORKS ── -->
  <div class="page" id="how-it-works">
    <div class="page-inner how-inner">
      <div class="section-label"><i class="ph ph-steps"></i> Process</div>
      <h2 class="section-heading">How It Works</h2>
      <p class="section-sub">Follow the steps based on your role in the evaluation process.</p>

      <div class="how-panels">
        <div class="how-panel student-panel">
          <div class="how-panel-title"><i class="ph ph-student"></i><h3>For Students</h3></div>
          <div class="how-timeline">
            <div class="how-item"><span class="how-number">1</span><div><h4>Account Login</h4><p>Access the system using your Student Number and registered password.</p></div></div>
            <div class="how-item"><span class="how-number">2</span><div><h4>Faculty Evaluation</h4><p>Select a faculty member from your pending list and answer the evaluation criteria.</p></div></div>
            <div class="how-item"><span class="how-number">3</span><div><h4>Submission & Verification</h4><p>Review all responses before submitting to ensure the evaluation is properly recorded.</p></div></div>
          </div>
        </div>
        <div class="how-panel faculty-panel">
          <div class="how-panel-title"><i class="ph ph-chalkboard-teacher"></i><h3>For Faculty Members</h3></div>
          <div class="how-timeline">
            <div class="how-item"><span class="how-number">1</span><div><h4>Portal Access</h4><p>Log in to view your faculty profile and the subjects you are handling.</p></div></div>
            <div class="how-item"><span class="how-number">2</span><div><h4>View Analytics</h4><p>Review your performance dashboard once the evaluation period has concluded.</p></div></div>
            <div class="how-item"><span class="how-number">3</span><div><h4>Generate Report</h4><p>Download your evaluation summary to support future improvement planning.</p></div></div>
          </div>
        </div>
      </div>
    </div>
    <!-- Quick links -->
    <div class="quick-links">
      <span class="ql-label">Quick Links</span>
      <a href="#" class="ql-item page-link" data-page="0"><i class="ph ph-house"></i><span>Home</span></a>
      <a href="#" class="ql-item page-link" data-page="1"><i class="ph ph-info"></i><span>About</span></a>
      <a href="#" class="ql-item page-link" data-page="3"><i class="ph ph-envelope"></i><span>Contact</span></a>
    </div>
    <div class="scroll-indicator page-link" data-page="3"><i class="ph ph-caret-down"></i></div>
  </div>

  <!-- ── PAGE 3 : CONTACT ── -->
  <div class="page" id="contact">
    <div class="page-inner contact-inner">
      <div class="section-label"><i class="ph ph-envelope"></i> Contact</div>
      <h2 class="section-heading">Get In Touch</h2>
      <p class="section-sub" style="margin:0 auto;">Questions? Reach out to us and we'll get back to you.</p>
      <div class="contact-info">
        <div class="info-box"><i class="ph ph-map-pin"></i><h3>Location</h3><p>Granby Colleges of Science and Technology</p></div>
        <div class="info-box"><i class="ph ph-phone"></i><h3>Phone</h3><p>+63 912 345 6789</p></div>
        <div class="info-box"><i class="ph ph-envelope"></i><h3>Email</h3><p>support@granbycollege.edu</p></div>
        <div class="info-box"><i class="ph ph-clock"></i><h3>Office Hours</h3><p>Monday–Saturday<br>8:00 AM – 5:00 PM</p></div>
      </div>
      <p class="contact-copy">© 2026 Faculty Evaluation System · All Rights Reserved</p>
    </div>
    <!-- Quick links -->
    <div class="quick-links">
      <span class="ql-label">Quick Links</span>
      <a href="#" class="ql-item page-link" data-page="0"><i class="ph ph-house"></i><span>Home</span></a>
      <a href="#" class="ql-item page-link" data-page="1"><i class="ph ph-info"></i><span>About</span></a>
      <a href="#" class="ql-item page-link" data-page="2"><i class="ph ph-list-checks"></i><span>How It Works</span></a>
    </div>
  </div>

</div><!-- /page-wrapper -->

<!-- Page dots nav -->
<div class="page-dots" id="pageDots">
  <span class="dot active" data-page="0"></span>
  <span class="dot" data-page="1"></span>
  <span class="dot" data-page="2"></span>
  <span class="dot" data-page="3"></span>
</div>

<!-- LOGIN -->
<div class="LoginForm" id="loginModal">
  <div class="LoginForm-content">
    <div class="wave-bg wave-1"></div>
    <div class="wave-bg wave-2"></div>
    <div class="wave-bg wave-3"></div>
    <div class="LoginForm-header">
      <img src="logo.png" class="logo">
      <div class="back-btn" id="closeLogin">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    <div class="login-form-container">
      <h2 class="login-title">Login Account</h2>
      <p class="login-description">Login your account</p>
      <form id="loginForm" class="modern-login-form">
        <div class="modern-input-group has-icon">
          <input type="text" id="username" placeholder="Enter your ID" required>
          <div class="input-icon"><i class="ph ph-envelope"></i></div>
          <small id="usernameError" class="error-message"></small>
        </div>
        <div class="modern-input-group has-toggle">
          <input type="password" id="password" placeholder="Password" required>
          <div class="password-toggle" id="togglePassword"><i class="ph ph-eye-slash"></i></div>
          <small id="passwordError" class="error-message"></small>
        </div>
        <div class="form-links"><a href="#" id="openForgot" class="forgot-link">Forgot Password?</a></div>
        <button type="submit" class="modern-login-btn">Login Account</button>
      </form>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD - STEP 1: Email -->
<div class="LoginForm" id="forgotModal">
  <div class="LoginForm-content">
    <div class="wave-bg wave-1"></div>
    <div class="wave-bg wave-2"></div>
    <div class="wave-bg wave-3"></div>
    <div class="LoginForm-header">
      <img src="logo.png" class="logo">
      <div class="back-btn" id="closeForgot">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    <div class="login-form-container">
      <h2 class="login-title">Forgot Password</h2>
      <p class="login-description">Enter your registered email and we'll send you a 6-digit reset code.</p>
      <form id="forgotForm" class="modern-login-form">
        <div class="modern-input-group has-icon">
          <input type="email" id="resetEmail" placeholder="Email address" required autocomplete="email">
          <div class="input-icon"><i class="ph ph-envelope"></i></div>
          <small id="resetEmailError" class="error-message"></small>
        </div>
        <button type="submit" class="modern-login-btn" id="sendCodeBtn">
          <i class="ph ph-paper-plane-right"></i>&nbsp; Send Code
        </button>
      </form>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD - STEP 2: OTP Verification -->
<div class="LoginForm" id="otpModal">
  <div class="LoginForm-content">
    <div class="wave-bg wave-1"></div>
    <div class="wave-bg wave-2"></div>
    <div class="wave-bg wave-3"></div>
    <div class="LoginForm-header">
      <img src="logo.png" class="logo">
      <div class="back-btn" id="closeOtp">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    <div class="login-form-container">
      <h2 class="login-title">Enter Reset Code</h2>
      <p class="login-description" id="otpDesc">Enter the 6-digit code sent to your email.</p>
      <div class="modern-login-form">
        <div class="otp-input-row" id="otpInputRow">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
          <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>
        <small id="otpError" class="error-message" style="display:block;text-align:center;margin-bottom:12px;"></small>
        <button type="button" class="modern-login-btn" id="verifyOtpBtn">
          <i class="ph ph-check-circle"></i>&nbsp; Verify Code
        </button>
        <div class="resend-row">
          <span>Didn't receive it?</span>
          <button type="button" class="resend-btn" id="resendCodeBtn">Resend Code</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD - STEP 3: New Password -->
<div class="LoginForm" id="newPasswordModal">
  <div class="LoginForm-content">
    <div class="wave-bg wave-1"></div>
    <div class="wave-bg wave-2"></div>
    <div class="wave-bg wave-3"></div>
    <div class="LoginForm-header">
      <img src="logo.png" class="logo">
      <div class="back-btn" id="closeNewPass">Cancel<i class="ph ph-arrow-up-right"></i></div>
    </div>
    <div class="login-form-container">
      <h2 class="login-title">New Password</h2>
      <p class="login-description">Create a strong new password for your account.</p>
      <div class="modern-login-form">
        <div class="modern-input-group has-toggle">
          <div class="input-wrap">
            <input type="password" id="newResetPass" placeholder="New Password" required autocomplete="new-password">
            <div class="password-toggle" onclick="toggleResetPass('newResetPass', this)"><i class="ph ph-eye-slash"></i></div>
          </div>
          <small id="newResetPassError" class="error-message"></small>
        </div>
        <div class="modern-input-group has-toggle">
          <div class="input-wrap">
            <input type="password" id="confirmResetPass" placeholder="Confirm New Password" required autocomplete="new-password">
            <div class="password-toggle" onclick="toggleResetPass('confirmResetPass', this)"><i class="ph ph-eye-slash"></i></div>
          </div>
          <small id="confirmResetPassError" class="error-message"></small>
        </div>
        <button type="button" class="modern-login-btn" id="resetPasswordBtn">
          Reset Password
        </button>
      </div>
    </div>
  </div>
</div>

<div id="fullscreen-spinner" class="hidden">

  <!-- Admin Dashboard Layout Skeleton -->
  <div class="admin-skeleton">
    <div class="skeleton-sidebar">
      <div class="skeleton-sidebar-logo-title">
        <div class="skeleton-sidebar-logo"></div>
        <div class="skeleton-sidebar-title-lines">
          <div class="skeleton-title" style="width: 80px; height: 16px; margin-bottom: 6px;"></div>
          <div class="skeleton-text" style="width: 110px; height: 10px; margin-bottom: 0;"></div>
        </div>
      </div>
      <div class="skeleton-sidebar-menu">
        <div class="skeleton-menu-header"></div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 80px;"></div>
        </div>
        <div class="skeleton-menu-header" style="margin-top: 14px;"></div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 70px;"></div>
        </div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 65px;"></div>
        </div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 75px;"></div>
        </div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 100px;"></div>
        </div>
        <div class="skeleton-menu-header" style="margin-top: 14px;"></div>
        <div class="skeleton-menu-item">
          <div class="skeleton-menu-icon"></div>
          <div class="skeleton-menu-text" style="width: 60px;"></div>
        </div>
      </div>
    </div>
    <div class="skeleton-main">
      <div class="skeleton-navbar">
        <div class="skeleton-nav-left">
          <div class="skeleton-hamburger"></div>
          <div class="skeleton-nav-title"></div>
        </div>
        <div class="skeleton-user-box">
          <div class="skeleton-avatar-circle"></div>
          <div class="skeleton-user-text">
            <div class="skeleton-text" style="width: 120px; height: 12px; margin-bottom: 4px;"></div>
            <div class="skeleton-text" style="width: 40px; height: 10px; margin-bottom: 0;"></div>
          </div>
          <div class="skeleton-caret"></div>
        </div>
      </div>
      <div class="skeleton-dashboard-content">
        <!-- Top Toolbar -->
        <div class="skeleton-toolbar">
          <div class="skeleton-toolbar-item">
            <div class="skeleton-toolbar-label"></div>
            <div class="skeleton-dropdown"></div>
          </div>
          <div class="skeleton-toolbar-item">
            <div class="skeleton-toolbar-label" style="width: 60px;"></div>
            <div class="skeleton-dropdown" style="width: 140px;"></div>
          </div>
        </div>
        <!-- Card Grid -->
        <div class="skeleton-cards-grid">
          <div class="skeleton-dash-card">
            <div class="skeleton-card-left">
              <div class="skeleton-title" style="width: 90px; height: 12px; margin-bottom: 8px;"></div>
              <div class="skeleton-value" style="width: 40px; height: 32px; margin-bottom: 8px;"></div>
              <div class="skeleton-text short" style="width: 100px; height: 10px; margin-bottom: 0;"></div>
            </div>
            <div class="skeleton-card-icon-box"></div>
          </div>
          <div class="skeleton-dash-card">
            <div class="skeleton-card-left">
              <div class="skeleton-title" style="width: 100px; height: 12px; margin-bottom: 8px;"></div>
              <div class="skeleton-value" style="width: 40px; height: 32px; margin-bottom: 8px;"></div>
              <div class="skeleton-text short" style="width: 90px; height: 10px; margin-bottom: 0;"></div>
            </div>
            <div class="skeleton-card-icon-box"></div>
          </div>
          <div class="skeleton-dash-card">
            <div class="skeleton-card-left">
              <div class="skeleton-title" style="width: 110px; height: 12px; margin-bottom: 8px;"></div>
              <div class="skeleton-value" style="width: 40px; height: 32px; margin-bottom: 8px;"></div>
              <div class="skeleton-text short" style="width: 60px; height: 10px; margin-bottom: 0;"></div>
            </div>
            <div class="skeleton-card-icon-box"></div>
          </div>
          <div class="skeleton-dash-card">
            <div class="skeleton-card-left">
              <div class="skeleton-title" style="width: 120px; height: 12px; margin-bottom: 8px;"></div>
              <div class="skeleton-value" style="width: 60px; height: 32px; margin-bottom: 8px;"></div>
              <div class="skeleton-text short" style="width: 70px; height: 10px; margin-bottom: 0;"></div>
            </div>
            <div class="skeleton-card-icon-box"></div>
          </div>
        </div>
        <!-- Row 2 -->
        <div class="skeleton-row-2">
          <div class="skeleton-large-card">
            <div class="skeleton-title" style="width: 140px; height: 16px; margin-bottom: 24px;"></div>
            <div class="skeleton-doughnut-container">
              <div class="skeleton-circle-chart">
                <div class="skeleton-circle-chart-inner"></div>
              </div>
            </div>
            <div class="skeleton-legend">
              <div class="skeleton-legend-item"></div>
              <div class="skeleton-legend-item"></div>
              <div class="skeleton-legend-item"></div>
              <div class="skeleton-legend-item"></div>
              <div class="skeleton-legend-item"></div>
            </div>
          </div>
          <div class="skeleton-large-card">
            <div class="skeleton-title" style="width: 150px; height: 16px; margin-bottom: 24px;"></div>
            <div class="skeleton-rankings-list">
              <div class="skeleton-ranking-row">
                <div class="skeleton-rank-number"></div>
                <div class="skeleton-rank-avatar"></div>
                <div class="skeleton-rank-bar-container">
                  <div class="skeleton-rank-name"></div>
                  <div class="skeleton-rank-progress"></div>
                </div>
              </div>
              <div class="skeleton-ranking-row">
                <div class="skeleton-rank-number" style="width: 12px;"></div>
                <div class="skeleton-rank-avatar"></div>
                <div class="skeleton-rank-bar-container">
                  <div class="skeleton-rank-name" style="width: 90px;"></div>
                  <div class="skeleton-rank-progress" style="width: 60%;"></div>
                </div>
              </div>
              <div class="skeleton-ranking-row">
                <div class="skeleton-rank-number" style="width: 12px;"></div>
                <div class="skeleton-rank-avatar"></div>
                <div class="skeleton-rank-bar-container">
                  <div class="skeleton-rank-name" style="width: 110px;"></div>
                  <div class="skeleton-rank-progress" style="width: 45%;"></div>
                </div>
              </div>
            </div>
            <div class="skeleton-text" style="width: 80%; height: 10px; margin-top: auto; margin-bottom: 0;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Student Dashboard Layout Skeleton -->
  <div class="student-skeleton">
    <!-- Navbar -->
    <div class="skeleton-navbar">
      <div class="skeleton-nav-left">
        <div class="skeleton-logo"></div>
        <div class="skeleton-nav-title" style="width: 180px;"></div>
      </div>
      <div class="skeleton-user-box">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-user-name" style="width: 50px;"></div>
      </div>
    </div>
    <!-- Main Content -->
    <div class="skeleton-content-container">
      <div class="skeleton-main-box">
        <div class="skeleton-main-left">
          <div class="skeleton-img"></div>
        </div>
        <div class="skeleton-main-right">
          <div class="skeleton-title" style="width: 250px; height: 28px; margin-bottom: 20px;"></div>
          <div class="skeleton-badge" style="width: 280px; height: 38px; margin-bottom: 14px;"></div>
          <div class="skeleton-badge" style="width: 180px; height: 38px; margin-bottom: 14px;"></div>
          <div class="skeleton-text" style="width: 90%; height: 14px; margin-bottom: 10px;"></div>
          <div class="skeleton-text" style="width: 80%; height: 14px; margin-bottom: 24px;"></div>
          <div class="skeleton-btn" style="width: 150px; height: 44px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="EvalMain.js?v=<?php echo time(); ?>"></script>
</body>
</html>
