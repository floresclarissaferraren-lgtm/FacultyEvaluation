<?php
require_once __DIR__ . "/../shared/security.php";
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (isset($_GET["logout"]) && $_GET["logout"] === "true") {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"],
        );
    }
    session_unset();
    session_destroy();
    header("Location: EvalMain.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(
    csrfToken(),
    ENT_QUOTES,
    "UTF-8",
) ?>">
<title>Faculty Evaluation System</title>
<link rel="stylesheet" href="EvalMain.css?v=<?= time() ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="logo-container">
    <img class="logo-img" src="../assets/images/schoollogo.png" alt="Granby Colleges logo">
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
      <div class="hero-highlights" aria-label="Key benefits of the evaluation system">
        <div class="hero-highlight">
          <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
          <div><h2>Student voice matters</h2><p>Share feedback through a clear, guided evaluation.</p></div>
        </div>
        <div class="hero-highlight">
          <i class="ph ph-list-checks" aria-hidden="true"></i>
          <div><h2>Fair, structured process</h2><p>Use consistent criteria to support thoughtful reviews.</p></div>
        </div>
        <div class="hero-highlight">
          <i class="ph ph-shield-check" aria-hidden="true"></i>
          <div><h2>Privacy-aware reporting</h2><p>Results are hidden when response groups are too small.</p></div>
        </div>
        <div class="hero-highlight">
          <i class="ph ph-chart-line-up" aria-hidden="true"></i>
          <div><h2>Actionable insights</h2><p>Use clear results to guide meaningful teaching improvements.</p></div>
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
      <div class="about-layout">
        <div class="about-content">
      <img class="about-logo" src="../assets/images/schoollogo.png" alt="Granby Colleges of Science and Technology logo">
      <h2 class="section-heading">Feedback That Supports Better Teaching</h2>
      <p class="section-sub">The Faculty Evaluation System gives students a convenient way to share feedback about their learning experience. It helps faculty and administrators review evaluation results and identify opportunities to strengthen teaching and learning.</p>

      <div class="about-grid">
        <div class="about-card"><div class="about-icon"><i class="ph ph-note-pencil"></i></div><h3>Student Feedback</h3><p>Students can submit faculty evaluations online while an evaluation period is open.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-layout"></i></div><h3>Faculty Results</h3><p>Faculty members can review their evaluation results and available performance analytics.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-chart-bar"></i></div><h3>Evaluation Reports</h3><p>Evaluation results are organized into reports to help users review performance and trends.</p></div>
        <div class="about-card"><div class="about-icon"><i class="ph ph-shield-check"></i></div><h3>System Administration</h3><p>Administrators manage student and faculty records, evaluation criteria, and system settings.</p></div>
      </div>
        </div>
      </div>
    </div>
    <div class="scroll-indicator page-link" data-page="2"><i class="ph ph-caret-down"></i></div>
  </div>

  <!-- ── PAGE 2 : HOW IT WORKS ── -->
  <div class="page" id="how-it-works">
    <div class="page-inner how-inner">
      <img class="section-logo" src="../assets/images/schoollogo.png" alt="Granby Colleges of Science and Technology logo">
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
    <div class="scroll-indicator page-link" data-page="3"><i class="ph ph-caret-down"></i></div>
  </div>

  <!-- ── PAGE 3 : CONTACT ── -->
  <div class="page" id="contact">
    <div class="page-inner contact-inner">
      <main class="contact-content">
        <header class="contact-intro">
          <div class="section-label"><i class="ph ph-envelope"></i> Contact</div>
          <h2 class="section-heading">Get in Touch</h2>
          <p class="section-sub">Questions? Reach out to us and we'll get back to you</p>
        </header>
        <div class="contact-cards">
          <article class="contact-card">
            <i class="ph ph-map-pin" aria-hidden="true"></i>
            <div><h3>Visit Us</h3><p>Granby Colleges of Science and Technology</p></div>
          </article>
          <article class="contact-card">
            <i class="ph ph-phone" aria-hidden="true"></i>
            <div><h3>Call Us</h3><p>+63 912 345 6789</p></div>
          </article>
          <article class="contact-card">
            <i class="ph ph-envelope" aria-hidden="true"></i>
            <div><h3>Email Us</h3><p>support@granbycollege.edu</p></div>
          </article>
        </div>
        <img class="contact-map" src="../assets/images/map.jpg" alt="Map showing the location of Granby Colleges of Science and Technology">
      </main>
      <footer class="contact-footer">
        <div class="contact-footer-main">
          <div class="contact-brand">
            <img src="../assets/images/granbylogo.png" alt="" aria-hidden="true">
            <div class="contact-brand-copy">
              <h3>FaculRate</h3>
              <p>Granby Colleges of Science and Technology</p>
            </div>
          </div>
          <div class="contact-footer-links" role="navigation" aria-label="Navigation links">
            <h3>Navigation</h3>
            <a href="#home" data-page="0">Home</a>
            <a href="#about" data-page="1">About Us</a>
            <a href="#how-it-works" data-page="2">How It Works</a>
          </div>
          <div class="contact-footer-links" role="navigation" aria-label="Quick links">
            <h3>Quick Links</h3>
            <a href="#" id="contactLoginBtn">Login</a>
          </div>
        </div>
        <div class="contact-copy">
          <span>© 2026 Granby Colleges of Science and Technology</span>
          <span>FaculRate · All rights reserved</span>
        </div>
      </footer>
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
      <img src="../assets/images/logo.png" class="logo">
      <div class="back-btn" id="closeLogin">Back<i class="ph ph-arrow-up-right"></i></div>
    </div>
    <div class="login-form-container">
      <h2 class="login-title">Login Account</h2>
      <p class="login-description">Login your account</p>
      <form id="loginForm" class="modern-login-form">
        <div class="modern-input-group login-field-group">
          <label class="login-field-label" for="username">Username</label>
          <input type="text" id="username" placeholder="Enter your ID" required>
          <small id="usernameError" class="error-message"></small>
        </div>
        <div class="modern-input-group login-field-group">
          <label class="login-field-label" for="password">Password</label>
          <input type="password" id="password" placeholder="Password" required>
          <label class="show-password-control" for="showPassword">
            <input type="checkbox" id="showPassword">
            <span>Show password</span>
          </label>
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
      <img src="../assets/images/logo.png" class="logo">
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
      <img src="../assets/images/logo.png" class="logo">
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
      <img src="../assets/images/logo.png" class="logo">
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

  <!-- Faculty Layout Skeleton -->
  <div class="faculty-skeleton">
    <div class="faculty-loading-navbar">
      <div class="faculty-loading-brand">
        <span class="faculty-loading-logo"></span>
        <div class="faculty-loading-brand-text">
          <span class="faculty-loading-brand-title"></span>
          <span class="faculty-loading-brand-subtitle"></span>
        </div>
      </div>
      <div class="faculty-loading-account">
        <span class="faculty-loading-avatar"></span>
        <span class="faculty-loading-caret"></span>
      </div>
    </div>

    <div class="faculty-skeleton-content">
      <div class="faculty-loading-welcome">
        <span class="faculty-loading-status"></span>
        <div class="faculty-loading-welcome-content">
          <div class="faculty-loading-copy">
            <div class="faculty-loading-heading">
              <span class="faculty-loading-title"></span>
              <span class="faculty-loading-period"></span>
            </div>
            <div class="faculty-loading-description">
              <span></span>
              <span></span>
            </div>
            <div class="faculty-loading-actions">
              <span></span>
              <span></span>
            </div>
          </div>
          <span class="faculty-loading-illustration"></span>
        </div>
      </div>
    </div>
  </div>

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
      <div class="skeleton-sidebar-profile">
        <div class="skeleton-sidebar-profile-avatar"></div>
        <div class="skeleton-sidebar-profile-copy">
          <div class="skeleton-text" style="width: 82px; height: 12px; margin: 0 0 4px;"></div>
          <div class="skeleton-text" style="width: 64px; height: 10px; margin: 0;"></div>
        </div>
        <div class="skeleton-caret"></div>
      </div>
    </div>
    <div class="skeleton-main">
      <div class="skeleton-navbar">
        <div class="skeleton-nav-left">
          <div class="skeleton-hamburger"></div>
          <div class="skeleton-nav-title"></div>
        </div>
        <div class="skeleton-notification-icon"></div>
      </div>
      <div class="skeleton-dashboard-content">
        <div class="skeleton-welcome-box">
          <div class="skeleton-welcome-copy">
            <div class="skeleton-welcome-heading">
              <div class="skeleton-title" style="width: 180px; height: 22px;"></div>
              <div class="skeleton-text" style="width: 150px; height: 18px;"></div>
            </div>
            <div class="skeleton-welcome-description">
              <div class="skeleton-text" style="width: 82%; height: 12px;"></div>
              <div class="skeleton-text" style="width: 58%; height: 12px;"></div>
            </div>
          </div>
          <div class="skeleton-admin-welcome-image"></div>
        </div>

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
          <div class="skeleton-academic-overview">
            <div class="skeleton-title" style="width: 130px; height: 16px;"></div>
            <div class="skeleton-academic-items">
              <div class="skeleton-academic-item">
                <div class="skeleton-text" style="width: 64px; height: 10px;"></div>
                <div class="skeleton-title" style="width: 82px; height: 14px;"></div>
              </div>
              <div class="skeleton-academic-item">
                <div class="skeleton-text" style="width: 48px; height: 10px;"></div>
                <div class="skeleton-title" style="width: 70px; height: 14px;"></div>
              </div>
              <div class="skeleton-academic-item">
                <div class="skeleton-text" style="width: 74px; height: 10px;"></div>
                <div class="skeleton-title" style="width: 60px; height: 14px;"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="skeleton-row-2">
          <div class="skeleton-large-card">
            <div class="skeleton-title" style="width: 140px; height: 16px; margin-bottom: 24px;"></div>
            <div class="skeleton-doughnut-container">
              <div class="skeleton-circle-chart">
                <div class="skeleton-circle-chart-inner"></div>
              </div>
            </div>
            <div class="skeleton-legend">
              <div class="skeleton-legend-item"><span class="skeleton-legend-dot"></span><span class="skeleton-legend-label"></span><span class="skeleton-legend-percent"></span></div>
              <div class="skeleton-legend-item"><span class="skeleton-legend-dot"></span><span class="skeleton-legend-label"></span><span class="skeleton-legend-percent"></span></div>
              <div class="skeleton-legend-item"><span class="skeleton-legend-dot"></span><span class="skeleton-legend-label"></span><span class="skeleton-legend-percent"></span></div>
              <div class="skeleton-legend-item"><span class="skeleton-legend-dot"></span><span class="skeleton-legend-label"></span><span class="skeleton-legend-percent"></span></div>
              <div class="skeleton-legend-item"><span class="skeleton-legend-dot"></span><span class="skeleton-legend-label"></span><span class="skeleton-legend-percent"></span></div>
            </div>
          </div>
          <div class="skeleton-large-card">
            <div class="skeleton-title" style="width: 150px; height: 16px;"></div>
            <div class="skeleton-program-progress-list">
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
              <div class="skeleton-program-progress-row">
                <div class="skeleton-program-progress-heading">
                  <span class="skeleton-program-progress-code"></span>
                  <span class="skeleton-program-progress-name"></span>
                  <span class="skeleton-program-progress-percent"></span>
                </div>
                <span class="skeleton-program-progress-bar"></span>
              </div>
            </div>
            <div class="skeleton-progress-summary">
              <div><span class="skeleton-value"></span><span class="skeleton-text"></span></div>
              <div><span class="skeleton-value"></span><span class="skeleton-text"></span></div>
              <div><span class="skeleton-value"></span><span class="skeleton-text"></span></div>
            </div>
          </div>
          <div class="skeleton-large-card skeleton-top-performance">
            <div class="skeleton-title" style="width: 160px; height: 16px;"></div>
            <div class="skeleton-top-performance-feature">
              <div class="skeleton-top-performance-avatar"></div>
              <div class="skeleton-top-performance-name"></div>
              <div class="skeleton-top-performance-score"></div>
              <div class="skeleton-top-performance-rank"></div>
            </div>
            <div class="skeleton-text" style="width: 80%; height: 10px; margin-top: auto; margin-bottom: 0;"></div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<script src="../shared/assets/csrf.js?v=<?php echo time(); ?>"></script>
<script src="EvalMain.js?v=<?php echo time(); ?>"></script>
</body>
</html>
