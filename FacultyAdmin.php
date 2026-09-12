
<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

include_once 'session_config.php'; // Load session settings BEFORE session_start
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['id'])) {
  header('Location: EvalMain.php');
  exit;
}
include 'totalstudents_dashcount.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System</title>
  <link rel="stylesheet" href="FacultyAdmin.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="unified_notifications.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    window.addEventListener("pageshow", event => {
      if (event.persisted || (performance.getEntriesByType("navigation")[0]?.type === "back_forward")) {
        window.location.reload();
      }
    });
  </script>
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<div class="navbar">

  <!-- Left Side -->
  <div class="nav-left">

    <div class="hamburger" onclick="toggleSidebar()">
      <i class="ph ph-list"></i>
    </div>

    
    <div class="section-title-container">
      <div class="section-title" id="navbarSectionTitle">Dashboard</div>
    </div>

  </div>

  <!-- Right Side User Menu -->
  <div class="user-menu">
    <button type="button" class="admin-notification-btn" id="adminNotificationBtn" title="Notifications" aria-label="Notifications">
      <i class="ph ph-bell"></i>
      <span class="notification-dot" aria-hidden="true"></span>
    </button>
    <div class="admin-notification-panel" id="adminNotificationPanel" role="status" aria-live="polite" hidden>
      <div class="admin-notification-panel-header">
        <strong>Notifications</strong>
        <i class="ph ph-bell-ringing" aria-hidden="true"></i>
      </div>
      <div class="admin-notification-list" id="adminNotificationList">
        <div class="admin-notification-empty">Checking evaluation deadlines...</div>
      </div>
    </div>
    <div class="admin-box">
      <div class="admin-icon">
        <span>SA</span>
      </div>
      <div class="admin-text">
        <span class="admin-title">System Administrator</span>
        <span class="admin-subtitle">admin</span>
      </div>
    </div>
  </div>

</div>


<!-- ===================== ALERT MODAL ===================== -->
<div id="alertModal" class="alert-modal-overlay" style="display:none;">
  <div class="alert-modal-box">
    <div class="alert-modal-icon">
      <i class="ph ph-warning-circle"></i>
    </div>
    <div class="alert-modal-body">
      <p class="alert-modal-title">Notice</p>
      <p class="alert-modal-message" id="alertModalMessage"></p>
    </div>
    <button class="alert-modal-close" id="alertModalClose">
      <i class="ph ph-arrow-clockwise"></i>
    </button>
  </div>
</div>

<!-- ===================== LOGOUT MODAL ===================== -->
<div id="logoutModal" class="logoutform">

  <div class="logout-content">

    <div class="logout-icon">
      <span class="icon-bg">
        <i class="ph ph-sign-out ph-2x"></i>
      </span>
    </div>

    <h3>Are you sure you want to logout?</h3>

    <div class="logout-buttons">
      <button type="button" class="btn logout-btn">
        Yes, Log me out
      </button>

      <button type="button" class="btn cancel-btn">
        No, Stay Logged In
      </button>
    </div>

  </div>

</div>


<!-- ===================== SIDEBAR ===================== -->
<div class="sidebar" id="sidebar">

  <!-- Logo Top -->
  <div class="sidebar-top">
    <div class="logo-title-container">
      <img src="assets/images/schoollogo.png" class="sidebar-logo">
      <div class="title-container">
        <h1>FaculRate</h1>
        <span class="subtitle">Faculty Evaluation System</span>
      </div>
    </div>
  </div>

  <!-- Menu -->
  <nav class="sidebar-menu">

    <!-- Main Section -->
    <div class="menu-section">
      <div class="menu-header">
        <span>Main</span>
      </div>
      <div class="menu-items">
        <a href="#" data-section="dashboard-section"
           onclick="showSection('dashboard-section', event)">
          <i class="ph ph-squares-four"></i>
          <span>Dashboard</span>
        </a>
      </div>
    </div>

    <!-- Management Section -->
    <div class="menu-section">
      <div class="menu-header">
        <span>Management</span>
      </div>
      <div class="menu-items">
        <a href="#" data-section="programs-section"
           onclick="showSection('programs-section', event)">
          <i class="ph ph-stack"></i>
          <span>Program</span>
        </a>
        <a href="#" data-section="faculties-section"
           onclick="showSection('faculties-section', event)">
          <i class="ph ph-users-three"></i>
          <span>Faculty</span>
        </a>
        <a href="#" data-section="students-section"
           onclick="showSection('students-section', event)">
          <i class="ph ph-student"></i>
          <span>Students</span>
        </a>
        <a href="#" data-section="criteria-section"
           onclick="showSection('criteria-section', event)">
          <i class="ph ph-clipboard-text"></i>
          <span>Evaluation Criteria</span>
        </a>
        <a href="#" data-section="academic-year-section"
           onclick="showSection('academic-year-section', event)">
          <i class="ph ph-calendar-blank"></i>
          <span>Academic Year</span>
        </a>
      </div>
    </div>

    <!-- Analytics Section -->
    <div class="menu-section">
      <div class="menu-header">
        <span>Analytics</span>
      </div>
      <div class="menu-items">
        <a href="#" data-section="report-section"
           onclick="showSection('report-section', event)">
          <i class="ph ph-chart-bar"></i>
          <span>Reports</span>
        </a>
      </div>
    </div>

  </nav>

  <!-- Sidebar Bottom / Logout -->
  <div class="sidebar-bottom">
    <a href="#" class="sidebar-logout-btn" id="logoutLink">
      <i class="ph ph-sign-out"></i>
      <span>Logout</span>
    </a>
  </div>
  
</div>

<main>
<!-- Dashboard Section  =====================================================================================================================================-->
<div id="dashboard-section" class="section">
  <div class="admin-welcome-box">
    <div class="admin-welcome-icon" aria-hidden="true">
      <i class="ph ph-hand-waving"></i>
    </div>
    <div>
      <h1>Admin Overview</h1>
      <p>Here's an overview of the Faculty Evaluation System.</p>
    </div>
    <div class="admin-period-summary" aria-label="Current academic period">
      <div class="admin-period-item">
        <i class="ph ph-calendar-blank" aria-hidden="true"></i>
        <div>
          <span>Academic Year</span>
          <strong id="dashboardAcademicYear">Not set</strong>
        </div>
      </div>
      <div class="admin-period-item">
        <i class="ph ph-book-open" aria-hidden="true"></i>
        <div>
          <span>Semester</span>
          <strong id="dashboardSemester">Not set</strong>
        </div>
      </div>
    </div>
  </div>

  <div class="dashboard">

    <!-- Faculty Card -->
    <div class="dashboard-card faculty" role="button" tabindex="0"
         onclick="showSection('faculties-section', event)"
         onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); showSection('faculties-section', event); }">
      <div class="dashboard-content">
        <div>
          <h3>Total Faculty</h3>
          <p id="totalFaculty" class="dashboard-value"><?php echo $totalFacultyCount; ?></p>
          <span>Registered Users</span>
        </div>
        <div class="icon-box"><i class="ph ph-chalkboard-teacher"></i></div>
      </div>
    </div>

    <!-- Students Card -->
        <div class="dashboard-card students" role="button" tabindex="0"
          onclick="showSection('students-section', event)"
          onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); showSection('students-section', event); }">
      <div class="dashboard-content">
        <div>
          <h3>Total Students</h3>
          <p id="totalStudents" class="dashboard-value"><?php echo $totalStudentsCount; ?></p>
          <span>Registered Users</span>
        </div>
        <div class="icon-box"><i class="ph ph-graduation-cap"></i></div>
      </div>
    </div>

    <!-- Evaluations Card -->
    <div class="dashboard-card evaluations">
      <div class="dashboard-content">
        <div>
          <h3>Total Evaluators</h3>
          <p id="totalEvaluations" class="dashboard-value"><?php echo $totalEvaluationsCount; ?></p>
          <span>Overall</span>
        </div>
        <div class="icon-box"><i class="ph ph-chart-bar"></i></div>
      </div>
    </div>

    <!-- Overall Faculty Ratings Card -->
    <div class="dashboard-card overall-ratings">
      <div class="dashboard-content">
        <div>
          <h3>Overall Faculty Rating</h3>
          <p id="overallRating" class="dashboard-value">0.00</p>
          <span>Out of 5.0</span>
        </div>
        <div class="icon-box"><i class="ph ph-star"></i></div>
      </div>
    </div>

  </div>
  
  <!-- Second Row -->
  <div class="dashboard-row-2">
    <!-- Rating Distribution Card -->
    <div class="dashboard-card rating-distribution">
      <div class="dashboard-content">
        <div class="rating-header">
          <h3>Rating Distribution</h3>
        </div>
        <div class="rating-chart-wrapper">
          <canvas id="ratingDistributionChart" width="200" height="200"></canvas>
        </div>
        <div class="rating-legend" id="ratingLegend">
          <div class="legend-item">
            <div class="legend-color excellent"></div>
            <span class="legend-label">Excellent</span>
          </div>
          <div class="legend-item">
            <div class="legend-color very-good"></div>
            <span class="legend-label">Very Good</span>
          </div>
          <div class="legend-item">
            <div class="legend-color good"></div>
            <span class="legend-label">Good</span>
          </div>
          <div class="legend-item">
            <div class="legend-color fair"></div>
            <span class="legend-label">Fair</span>
          </div>
          <div class="legend-item">
            <div class="legend-color poor"></div>
            <span class="legend-label">Poor</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-card top-performance">
      <div class="dashboard-content">
        <div class="rating-header">
          <h3>Top Faculty Rankings</h3>
        </div>
        <div class="top-performance-chart-wrapper">
          <div id="topPerformanceChart" class="top-performance-progress-list" role="button" tabindex="0" aria-label="View all faculty performance rankings"></div>
        </div>
        <div class="top-performance-hint">Showing the highest-ranked evaluated faculties. Click the list to view the full ranking.</div>
      </div>
    </div>

  </div>

  <!-- Third Row -->
  <div class="dashboard-row-3">
    <div class="dashboard-card evaluation-progress">
      <div class="dashboard-content">
        <div class="rating-header">
          <h3>Evaluation Progress</h3>
        </div>
        <div class="evaluation-progress-stats">
          <div class="evaluation-progress-stat completion">
            <strong id="evaluationCompletionRate">0.0%</strong>
            <span>Completion Rate</span>
          </div>
          <div class="evaluation-progress-stat submissions">
            <strong id="evaluationSubmissionCount">0</strong>
            <span>Submissions</span>
          </div>
          <div class="evaluation-progress-stat pending">
            <strong id="evaluationPendingCount">0</strong>
            <span>Pending</span>
          </div>
        </div>
        <div class="progress-list">
          <div class="progress-item">
            <div class="progress-label-row">
              <span>Submitted Evaluations</span>
              <small id="evaluationProgressText">No submissions yet</small>
            </div>
            <div class="progress-track"><div id="evaluationProgressFill" class="progress-fill submitted" style="width:0%;"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="topPerformanceModal" class="performance-modal">
  <div class="performance-modal-content">
    <div class="performance-modal-header">
      <h3>All Faculty Performance Rankings</h3>
      <button class="modal-close-btn" type="button" id="closeTopPerformanceModal" onclick="closeTopPerformanceModal()">×</button>
    </div>
    <p id="top-performance-modal-summary" class="performance-modal-summary"></p>
    <div class="performance-modal-table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Faculty</th>
            <th>Percentage</th>
          </tr>
        </thead>
        <tbody id="top-performance-modal-body"></tbody>
      </table>
    </div>
  </div>
</div>


 <!--
<div id="dashboard-details">
  <div class="ratings-box">
    <h4><i class="ph ph-star"></i> Faculty Ratings</h4>
    <table>
      <thead><tr><th>Faculty Name</th><th>Rating</th></tr></thead>
      <tbody id="ratings-body"></tbody>
    </table>
  </div>

  <div class="ranking-box">
    <h4><i class="ph ph-trophy"></i> Faculty Ranking</h4>
    <table>
      <thead><tr><th>Rank</th><th>Faculty</th><th>Ratings</th></tr></thead>
      <tbody id="ranking-body"></tbody>
    </table>
  </div>

  <div class="graph-box">
    <h4><i class="ph ph-chart-bar"></i> Responded per Department</h4>
    <canvas id="departmentGraph"></canvas>
  </div>
</div>-->

<!-- Programs Section ============================================================================================================================== -->
<div id="programs-section" class="section" style="display:none;">
  <!-- Programs Container Box -->
  <div class="programs-container-box">
    <!-- Programs Header -->
    <div class="programs-header">
      <div class="programs-stats">
        <div class="stats-icon">
          <i class="ph ph-graduation-cap"></i>
        </div>
        <div class="stats-info">
          <p class="stats-number" id="totalPrograms">0 Program Record</p>
        </div>
      </div>
      <div class="programs-actions">
        <button type="button" class="clear-search-btn" data-clear-targets="program-search" title="Clear search" aria-label="Clear program search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="program-search" placeholder="Search program..."/>
        </div>
        <button class="add-program-btn button-gradient"><i class="ph ph-plus"></i> Add Program</button>
      </div>
    </div>

    <!-- Programs Cards Container -->
    <div class="programs-cards-container" id="programsCardsContainer">
      <!-- Program cards will be dynamically added here -->
    </div>
  </div>
</div>
<!-- Add Program Modal -->
<div id="addProgramModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header"><h3>ADD PROGRAM</h3><span class="close-btn">&times;</span></div>
    <div class="modal-body">
      <label for="program-code">Program Code</label>
      <input type="text" id="program-code" placeholder="eg. BSIT">
      <small id="program-code-message" class="program-validation-message" style="display:none;"></small>
      <label for="program-name">Program Name</label>
      <input type="text" id="program-name" placeholder="eg. Bachelor of Information Technology">
      <button id="save-program-btn" class="submit-btn">SAVE PROGRAM</button>
    </div>
  </div>
</div>

<!-- Add Delete Forms ==============================================================================================================================-->
<div id="deleteModal" class="modal">
  <div class="modal-content delete-modal-card">
    <div class="delete-icon-circle"><i class="ph ph-warning"></i></div>
    <p id="deleteMessage" class="delete-main-text">
      Do you want to delete <strong>category</strong>?<br><b>TEACHING SKILLS</b>
    </p>
    <p id="deleteWarning" class="delete-sub-text">All details about this category will be deleted.</p>
    <div class="delete-buttons">
      <button onclick="closeDeleteModal()" class="cancel-btn">No, Keep It</button>
      <button onclick="confirmDelete()" class="submit-btn">Yes, Delete!</button>
    </div>
  </div>
</div>

<div id="deleteSuccessModal" class="modal" style="display:none;">
  <div class="modal-content success-modal-card">
    <div class="success-icon-circle"><i class="ph ph-check-circle"></i></div>
    <p class="success-main-text">Deleted successfully!</p>
    <div class="success-buttons">
      <button id="success-ok-btn" class="ok-btn">OK</button>
    </div>
  </div>
</div>


<!-- Manage Section ==============================================================================================================================-->
<div id="manage-section" class="section" style="display:none;">
  
  <!-- Blue Banner with Program Info and Stats -->
  <div class="manage-program-banner">
    <div class="program-banner-header">
      <div class="program-badge" id="manageProgramBadge">BSED</div>
      <div class="program-info">
        <h2 id="manageTitle">Bachelor of Science in Education in Math Major</h2>
        <p class="program-meta"><span id="manageProgramType">Academic Program</span> · <span id="manageProgramStatus">Active</span></p>
      </div>
    </div>
    
    <div class="program-stats-row">
      <div class="program-stat-item">
        <i class="ph ph-book"></i>
        <div class="stat-content">
          <span class="stat-number" id="totalSubjectsCount">38</span>
          <span class="stat-label">Total Subjects</span>
        </div>
      </div>
      <div class="program-stat-item">
        <i class="ph ph-users-three"></i>
        <div class="stat-content">
          <span class="stat-number" id="activeClassesCount">9</span>
          <span class="stat-label">Active Classes</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs and Actions Row -->
  <div class="manage-tabs-row">
    <div class="manage-tabs">
      <button class="subject-btn active"><i class="ph ph-book"></i> Subjects</button>
      <button class="classes-btn"><i class="ph ph-users"></i> Classes</button>
    </div>
    <div class="manage-actions">
      <button class="add-manage-btn button-gradient"><i class="ph ph-plus"></i> Add Subject</button>
      <button class="back-btn"><i class="ph ph-arrow-left"></i> Back</button>
    </div>
  </div>

  
  <!-- Subject Table -->
  <div class="manage-body">
    <div id="subjects" class="table-wrapper">
      <div class="subject-control-box manage-subject-control-box">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="manage-subjects-search" placeholder="Search subjects...">
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-calendar-blank"></i>
          <select id="manage-subjects-semester-filter" class="program-dropdown">
            <option value="">-- All Semesters --</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-graduation-cap"></i>
          <select id="manage-subjects-year-filter" class="program-dropdown">
            <option value="">-- All Year Levels --</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
        <button type="button" class="clear-search-btn" data-clear-targets="manage-subjects-search,manage-subjects-semester-filter,manage-subjects-year-filter" title="Clear search" aria-label="Clear subject search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
      </div>
      <div class="table-scroll-container">
        <table class="subjects-table"><thead><tr>
          <th>Subject Code</th><th>Description</th><th>Semester</th><th>Year</th><th>Action</th>
        </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- Classes Table -->
    <div id="classes" class="table-wrapper" style="display:none;">
      <div class="table-scroll-container">
        <table class="classes-table"><thead><tr>
          <th>Section Name</th><th>Year Level</th><th>Status</th><th>Action</th>
        </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Subject Form -->
<div id="addSubjectModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>ADD SUBJECT</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <label for="subject-code">Subject Code</label>
      <input type="text" id="subject-code" placeholder="eg. MATH101">
      <label for="subject-desc">Description</label>
      <input type="text" id="subject-desc" placeholder="eg. College Algebra">
      <div class="form-row">
        <div>
          <label for="subject-semester">Semester</label>
          <select id="subject-semester">
            <option value="">Select Semester</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
        <div>
          <label for="subject-year">Year</label>
          <select id="subject-year">
            <option value="">Select Year Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
      </div>
      <button id="save-subject-btn" class="submit-btn">SAVE SUBJECT</button>
    </div>
  </div>
</div>

<!-- Add Class Modal -->
<div id="addClassModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>ADD CLASS</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div>
          <label for="class-year">Year Level</label>
          <select id="class-year">
            <option value="">Select Year Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
        <div>
          <label for="class-block">Section</label>
          <input type="text" id="class-block">
        </div>
      </div>
      <div class="section-box">
        <div id="subject-checkbox-list" class="subjects-list">
          <h4><i class="ph ph-book"></i> Assigned Subjects</h4>
          <div class="class-subject-search">
            <input type="text" id="class-subject-search" placeholder="Search assigned subjects..." class="subject-search-input">
            <i class="ph ph-magnifying-glass"></i>
          </div>
          <div class="class-subject-options">
            <small>Select year level first to load subjects</small>
          </div>
        </div>
      </div>
      <div class="section-box">
        <div id="faculty-list" class="subjects-list">
          <h4><i class="ph ph-user"></i> Available Faculty</h4>
          <small>Select subjects to show available faculty</small>
        </div>
      </div>
      <button id="save-class-btn" class="submit-btn">SAVE CLASS</button>
    </div>
  </div>
</div>



<!-- ===================== Subjects Section ===================== -->
<div id="subjects-section" class="section" style="display:none;">
  <div class="section-header">
    <h2>Subjects Management</h2>
    <div class="header-actions">
      <button class="add-subject-main-btn button-gradient"><i class="ph ph-plus"></i> Add Subject</button>
    </div>
  </div>
  <div class="subject-control-box">
    <div class="search-wrapper">
      <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
      <input type="text" id="subjects-search" placeholder="Search subjects...">
    </div>
    <select id="subjects-year-filter" class="program-dropdown">
      <option value="">-- All Year Levels --</option>
      <option value="1st Year">1st Year</option>
      <option value="2nd Year">2nd Year</option>
      <option value="3rd Year">3rd Year</option>
      <option value="4th Year">4th Year</option>
    </select>
    <button type="button" class="clear-search-btn" data-clear-targets="subjects-search,subjects-year-filter" title="Clear search" aria-label="Clear subject search">
      <i class="ph ph-arrow-clockwise"></i>
    </button>
  </div>
  <div class="table-wrapper">
    <div class="table-scroll-container">
      <table class="subjects-table">
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Description</th>
            <th>Program</th>
            <th>Semester</th>
            <th>Year Level</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="subjects-main-tbody">
          <tr>
            <td colspan="6" style="text-align: center; padding: 20px;">Loading subjects...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Subject Main Modal -->
<div id="addSubjectMainModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>ADD SUBJECT</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div>
          <label for="main-subject-code">Subject Code</label>
          <input type="text" id="main-subject-code" placeholder="eg. MATH101">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label for="main-subject-desc">Description</label>
          <input type="text" id="main-subject-desc" placeholder="eg. College Algebra">
        </div>
      </div>
      <div class="form-row">
        <div>
          <label for="main-program-select">Program</label>
          <select id="main-program-select" required>
            <option value="" disabled selected>-- Select Program --</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div>
          <label for="main-semester-select">Semester</label>
          <select id="main-semester-select" required>
            <option value="" disabled selected>-- Select Semester --</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
        <div>
          <label for="main-year-select">Year Level</label>
          <select id="main-year-select" required>
            <option value="" disabled selected>-- Select Year --</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
      </div>
      <button id="save-main-subject-btn" class="submit-btn">SAVE SUBJECT</button>
    </div>
  </div>
</div>

<!-- ===================== Faculties Section ===================== -->
<div id="faculties-section" class="section" style="display:none;">
  
  <!-- Faculty Statistics Cards -->
  <div class="faculty-stats-container">
    <div class="faculty-stat-card total-faculty">
      <div class="stat-content">
        <div class="stat-info">
          <h3>All Registered Faculty</h3>
          <p class="stat-number"><?php echo $facultyStats['total_faculty']; ?></p>
          <span>Total Faculty Members</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-chalkboard-teacher"></i>
        </div>
      </div>
    </div>

    <div class="faculty-stat-card active-faculty">
      <div class="stat-content">
        <div class="stat-info">
          <h3>With Assigned Subjects</h3>
          <p class="stat-number"><?php echo $facultyStats['active_faculty']; ?></p>
          <span>Active Faculty</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-user-check"></i>
        </div>
      </div>
    </div>

    <div class="faculty-stat-card pending-evaluation">
      <div class="stat-content">
        <div class="stat-info">
          <h3>Awaiting Evaluation</h3>
          <p class="stat-number"><?php echo $facultyStats['pending_evaluation']; ?></p>
          <span>Pending Faculty</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-clock-clockwise"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="faculty-toolbar">
    <div class="search-filters-container">
      <div class="search-wrapper">
        <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
        <input id="faculty-search" placeholder="Search faculty...">
      </div>
      <div class="program-filter-wrapper">
        <i class="ph ph-funnel"></i>
        <select id="faculty-status-filter" class="status-dropdown">
          <option value="">Filter by Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="on leave">On Leave</option>
        </select>
      </div>
      <button type="button" class="clear-search-btn" data-clear-targets="faculty-search,faculty-status-filter" title="Clear search" aria-label="Clear faculty search">
        <i class="ph ph-arrow-clockwise"></i>
      </button>
    </div>
    <div class="header-actions">
      <button class="add-faculty-btn button-gradient"><i class="ph ph-plus"></i> Add Faculty</button>
      <button class="show-archived-faculty-btn">Show Archived</button>
    </div>
  </div>

  <div class="table-wrapper">
    <div class="table-scroll-container">
      <table class="faculties-table">
        <thead>
          <tr><th>Image</th><th>ID</th><th>Name</th><th>Subjects</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="archived-faculty-section" hidden>
    <div class="archived-faculty-header">
      <div>
        <h3>Archived Faculty</h3>
        <span>Faculty records moved out of the active list</span>
      </div>
      <div class="archived-faculty-header-actions">
        <button class="clear-search-btn" id="archived-faculty-clear-btn" type="button" title="Clear search" aria-label="Clear archived search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
        <div class="archived-search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="archived-faculty-search" placeholder="Search archived faculty...">
        </div>
        <button class="back-to-faculty-btn"><i class="ph ph-arrow-left"></i> Back to Faculty</button>
      </div>
    </div>
    <div class="table-scroll-container">
      <table class="faculties-table archived-faculties-table">
        <thead>
          <tr><th>Image</th><th>Faculty ID</th><th>Faculty Name</th><th>Subjects</th><th>Status</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Faculty Form -->
<div id="addFacultyModal" class="modal" hidden>
  <div class="modal-content">
    
    <div class="modal-header">
      <h3>ADD FACULTY</h3>
      <span class="close-btn">&times;</span>
    </div>
        <div class="modal-body">

      <div class="profile-upload">
        <div class="profile-circle">
          <img id="faculty-photo-preview" hidden>
          <label for="faculty-photo" class="upload-badge">
            <svg viewBox="0 0 16 16">
              <path d="M8 3v10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
              <path d="M3 8h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
          </label>
        </div>
        <input type="file" id="faculty-photo" accept=".jpg,.jpeg,image/jpeg,image/png" hidden>
      </div>
      
      <div class="form-row">
        <label>ID <input id="faculty-number" placeholder="eg. FC-"></label>
        <label>Email <input type="email" id="faculty-email" placeholder="eg. faculty@email.com"></label>
      </div>
      <div class="form-row">
        <label>First <input id="faculty-firstname" placeholder="eg. Juan"></label>
        <label>Last <input id="faculty-lastname" placeholder="eg. Dela Cruz"></label>
        <label>Suffix <input id="faculty-suffix" placeholder="Jr., III"></label>
      </div>
      
            
      <div class="section-box">
        <div id="faculty-subjects-list" class="subjects-list">
          <h4><i class="ph ph-book"></i> Assigned Subjects</h4>
          <button type="button" id="open-faculty-subject-selection" class="button-gradient">
            <i class="ph ph-plus"></i> Add Subjects
          </button>
          <div id="selected-faculty-subjects" class="selected-subjects">
            <p class="empty-subject-message">No subjects selected</p>
          </div>
        </div>
      </div>
      <button class="submit-btn">SAVE FACULTY</button>
    </div>
  </div>
</div>

<!-- Faculty Subject Selection Modal -->
<div id="facultySubjectSelectionModal" class="modal" style="display:none;">
  <div class="modal-content faculty-subject-selection-content">
    <div class="modal-header">
      <h3>Select Faculty Subjects</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div>
          <label for="faculty-subject-selection-search">Search Subject Code or Name</label>
          <input type="text" id="faculty-subject-selection-search" placeholder="Search subject code or name...">
        </div>
        <div>
          <label for="faculty-subject-year-filter">Year Level</label>
          <select id="faculty-subject-year-filter">
            <option value="all">All Years</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
          </select>
        </div>
        <div>
          <label for="faculty-subject-program-filter">Program</label>
          <select id="faculty-subject-program-filter">
            <option value="all">All Programs</option>
          </select>
        </div>
      </div>
      <div class="subjects-table-wrapper faculty-subject-selection-wrapper">
        <table class="subjects-selection-table">
          <thead>
            <tr>
              <th></th>
              <th>Subject Code</th>
              <th>Subject Name</th>
              <th>Year</th>
              <th>Program</th>
            </tr>
          </thead>
          <tbody id="faculty-subject-selection-tbody">
            <tr><td colspan="5" style="text-align:center;padding:20px;">Loading subjects...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-actions">
        <button type="button" id="cancel-faculty-subject-selection" class="cancel-btn">Cancel</button>
        <button type="button" id="confirm-faculty-subject-selection" class="submit-btn">Add Selected Subjects</button>
      </div>
    </div>
  </div>
</div>


<!-- ===================== Students Section ===================== -->
<div id="students-section" class="section" style="display:none;">
  
  <!-- Student Statistics Cards -->
  <div class="student-stats-container">
    <div class="student-stat-card total-students">
      <div class="stat-content">
        <div class="stat-info">
          <h3>Total Students</h3>
          <p class="stat-number"><?php echo $studentStats['total_students']; ?></p>
          <span>All Registered Students</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-users"></i>
        </div>
      </div>
    </div>

    <div class="student-stat-card active-students">
      <div class="stat-content">
        <div class="stat-info">
          <h3>Active Students</h3>
          <p class="stat-number"><?php echo $studentStats['active_students']; ?></p>
          <span>With Enrolled Subjects</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-user-check"></i>
        </div>
      </div>
    </div>

    <div class="student-stat-card regular-students">
      <div class="stat-content">
        <div class="stat-info">
          <h3>Regular Students</h3>
          <p class="stat-number"><?php echo $studentStats['regular_students']; ?></p>
          <span>Regular Status</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-graduation-cap"></i>
        </div>
      </div>
    </div>

    <div class="student-stat-card irregular-students">
      <div class="stat-content">
        <div class="stat-info">
          <h3>Irregular Students</h3>
          <p class="stat-number"><?php echo $studentStats['irregular_students']; ?></p>
          <span>Irregular Status</span>
        </div>
        <div class="stat-icon">
          <i class="ph ph-warning"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="student-table-box">
    <div class="student-toolbar-box">
    <div class="student-toolbar">
      <div class="student-header-actions">
        <button class="add-student-btn button-gradient"><i class="ph ph-plus"></i> Add Student</button>
        <button class="show-archived-student-btn">Show Archived</button>
      </div>
      <div class="student-filter-row">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input id="student-search" placeholder="Search student...">
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-funnel"></i>
          <select id="student-program-filter" class="program-dropdown">
            <option value="">All Programs</option>
          </select>
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-graduation-cap"></i>
          <select id="student-yearlevel-filter" class="program-dropdown">
            <option value="">-- All Year Levels --</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
            <option value="irregular">Irregular</option>
          </select>
        </div>
        <button type="button" class="clear-search-btn" data-clear-targets="student-search,student-program-filter,student-yearlevel-filter" title="Clear search" aria-label="Clear student search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
      </div>
    </div>
    </div>

    <div class="table-wrapper">
    <div class="table-scroll-container">
      <table class="students-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Year Level & Section</th><th>Status</th><th>Type</th><th>Action</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    </div>
  </div>

  <div class="archived-student-section" hidden>
    <div class="archived-student-header">
      <div>
        <h3>Archived Students</h3>
        <span>Student records moved out of the active list</span>
      </div>
      <div class="archived-student-header-actions">
        <button class="clear-search-btn" id="archived-student-clear-btn" type="button" title="Clear search" aria-label="Clear archived search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
        <div class="archived-search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="archived-student-search" placeholder="Search archived students...">
        </div>
        <button class="back-to-student-btn"><i class="ph ph-arrow-left"></i> Back to Students</button>
      </div>
    </div>
    <div class="table-scroll-container">
      <table class="students-table archived-students-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Year Level & Section</th><th>Status</th><th>Type</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Student Modal-->
<div id="addStudentModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>ADD STUDENT</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <!-- Student Info -->
      <div class="form-row">
        <div>
          <label for="student-number">Student ID</label>
          <input type="text" id="student-number" placeholder="eg. GC-">
          <!-- Inline error message for GC- validation -->
          <div id="student-number-error" style="color:red; font-size:0.9em; margin-top:2px; min-height:16px;"></div>
        </div>
        <div>
          <label for="student-email">Email</label>
          <input type="email" id="student-email" placeholder="eg. student@email.com">
          <!-- Placeholder div to maintain consistent height -->
          <div style="min-height:16px;"></div>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="student-firstname">First Name</label>
          <input type="text" id="student-firstname" placeholder="eg. Juan">
        </div>
        <div>
          <label for="student-lastname">Last Name</label>
          <input type="text" id="student-lastname" placeholder="eg. Dela Cruz">
        </div>
        <div class="suffix-field">
          <label for="student-suffix">Suffix</label>
          <input type="text" id="student-suffix" placeholder="Jr., III">
        </div>
      </div>

      <div class="section-box">
        <h4><i class="ph ph-graduation-cap"></i> Academic Details</h4>
        <div class="form-row">
          <div>
            <label>Student Type</label>
            <div style="display: flex; gap: 20px; margin-top: 5px; justify-content: center;">
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="radio" name="student-type" value="regular" checked> Regular 
              </label>
              <label style="display: flex; align-items: center; gap: 5px;">
                <input type="radio" name="student-type" value="irregular"> Irregular 
              </label>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div>
            <label for="student-program">Program</label>
            <select id="student-program" required>
              <option value="" disabled selected>-- Select Program --</option>
            </select>
          </div>
        </div>

        <div class="form-row" id="yearlevel-row">
          <div>
            <label for="student-yearlevel">Year Level</label>
            <select id="student-yearlevel" required>
              <option value="" disabled selected>-- Select Year Level --</option>
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
            </select>
          </div>
          <div>
            <label for="student-section">Section</label>
            <input type="text" id="student-section" required>
          </div>
        </div>
      </div>

      <div class="section-box">
        <h4><i class="ph ph-book"></i> Subjects</h4>
        <button type="button" id="add-subject-btn" class="button-gradient" style="margin-bottom: 10px;">
          <i class="ph ph-plus"></i> Add Subject
        </button>
        <div id="selected-subjects" class="selected-subjects">
          <p style="color: #6b7280; font-size: 0.9em;">No subjects selected</p>
        </div>
      </div>

      <button class="submit-btn">SAVE STUDENT</button>
    </div>
  </div>
</div>

<!-- Subject Selection Modal -->
<div id="subjectSelectionModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Select Subjects</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="form-row">
        <div>
          <label for="subject-search">Search</label>
          <input type="text" id="subject-search" placeholder="Search subjects...">
        </div>
      </div>
      
      <div class="subjects-table-wrapper">
        <table class="subjects-selection-table">
          <thead>
            <tr>
              <th></th>
              <th>Subject Code</th>
              <th>Description</th>
              <th>Year Level / Section</th>
            </tr>
          </thead>
          <tbody id="subjects-selection-tbody">
            <tr>
              <td colspan="5" style="text-align: center; padding: 20px;">
                Loading subjects...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="modal-actions">
        <button type="button" id="cancel-subject-selection" class="cancel-btn">Cancel</button>
        <button type="button" id="confirm-subject-selection" class="submit-btn">Add Selected Subjects</button>
      </div>
    </div>
  </div>
</div>

<!-- ===================== Evaluation Criteria Section ================================================================================= -->
<div id="criteria-section" class="section" style="display:none;">
  <div class="section-header criteria-header-enhanced">
    <div class="criteria-header-top">
      <div class="header-title-section">
        <h2 class="section-title">
          <i class="ph ph-clipboard-text"></i>
          Evaluation Criteria List
        </h2>
        <p class="section-subtitle">
          <i class="ph ph-info"></i>
          Manage the criteria used for evaluating faculty performance.
        </p>
      </div>
      <div class="criteria-header-controls">
        <div class="criteria-search-container">
          <div class="search-wrapper">
            <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
            <input type="text" id="criteria-search-input" placeholder="Search categories...">
          </div>
          <button type="button" class="clear-search-btn" id="criteria-search-clear" title="Clear search" aria-label="Clear criteria search">
            <i class="ph ph-arrow-clockwise"></i>
          </button>
        </div>
        <div class="header-actions">
          <button id="addCategoryBtn" class="add-program-btn button-gradient">
            <i class="ph ph-plus"></i> Add Category
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="criteria-layout" style="display:flex; gap:24px; align-items:flex-start;">
    <div class="categories-column" id="categoriesColumn" style="flex:2;"></div>

    <div class="summary-panel" style="flex:1;">
      <h4>Summary</h4>
      
      <div class="summary-stats-simple">
        <div class="summary-row">
          <span class="summary-label">Categories</span>
          <span class="summary-value" id="total-categories">3</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Total Questions</span>
          <span class="summary-value" id="total-questions">13</span>
        </div>
        <div class="summary-row" id="weight-sum-row">
          <span class="summary-label">Total Weight</span>
          <span class="summary-value summary-weight" id="total-weight-display">
            100.00%
            <i class="ph ph-check-circle" id="weight-check-icon" style="color:#10b981; font-size:1rem; margin-left:4px;"></i>
          </span>
        </div>
      </div>

      <!-- Weight Distribution -->
      <div class="weight-distribution-section" id="weight-distribution-section">
        <h5>WEIGHT DISTRIBUTION</h5>
        <div class="weight-list" id="weight-list-container"></div>
      </div>

      <!-- Rating Scale -->
      <div class="rating-scale-section">
        <h5>Rating Scale</h5>
        <div class="rating-item">
          <div class="rating-badge rating-5">5</div>
          <span class="rating-text">Strongly Agree</span>
        </div>
        <div class="rating-item">
          <div class="rating-badge rating-4">4</div>
          <span class="rating-text">Agree</span>
        </div>
        <div class="rating-item">
          <div class="rating-badge rating-3">3</div>
          <span class="rating-text">Neutral</span>
        </div>
        <div class="rating-item">
          <div class="rating-badge rating-2">2</div>
          <span class="rating-text">Disagree</span>
        </div>
        <div class="rating-item">
          <div class="rating-badge rating-1">1</div>
          <span class="rating-text">Strongly Disagree</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Category Form -->
<div id="addCategoryModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:850px; width: 92%;">
    <div class="modal-header">
      <h3 id="categoryModalTitle">ADD CATEGORY</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <label for="category-name">Category Name</label>
      <input type="text" id="category-name" placeholder="eg. Instructional Competence">
      <label for="section-number">Section #</label>
      <input type="number" id="section-number" min="1" placeholder="eg. 1">

      <label for="category-weight" style="display:flex;align-items:center;gap:8px;">
        Weight (%)
        <span id="weightSumBadge" style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;background:#e2e8f0;color:#475569;"></span>
      </label>
      <input type="number" id="category-weight" min="0" max="100" step="0.01" placeholder="eg. 40 — leave 0 for equal split">
      <p id="weightHint" style="font-size:12px;color:#64748b;margin-top:4px;"></p>

      <button id="saveCategoryBtn" class="submit-btn">SAVE CATEGORY</button>
    </div>
  </div>
</div>

<!-- Add Question Form -->
<div id="addQuestionModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:500px;">
    <div class="addProgram-header">
      <h3 id="questionModalTitle">ADD QUESTION</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <!-- Error message container -->
      <div id="questionErrorMsg" class="question-error-msg" style="display:none;">
        <i class="ph ph-warning-circle"></i>
        <span class="error-text"></span>
      </div>
      
      <p class="category-label">
        <span class="label-text">Category:</span>
        <span class="category-name" id="questionCategoryName"></span>
      </p>
      <label for="question-text">Question</label>
      <textarea id="question-text" rows="3" placeholder="Enter evaluation question..."></textarea>
      <button id="saveQuestionBtn" class="submit-btn">SAVE QUESTION</button>
    </div>
  </div>
</div>


<!-- Academic Year Section =========================================================================================================-->
<div id="academic-year-section" class="section" style="display:none;">

  <!-- Table Header with Search and Filters -->
  <div class="table-wrapper academic-year-controls">
    <div class="table-header">
      <div class="search-filters-container">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="academic-year-search" placeholder="Search academic year...">
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-calendar-blank"></i>
          <select id="academic-year-filter" class="program-dropdown">
            <option value="">All Academic Years</option>
          </select>
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-calendar"></i>
          <select id="semester-filter" class="program-dropdown">
            <option value="">All Semesters</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
        <button type="button" class="clear-search-btn" data-clear-targets="academic-year-search,academic-year-filter,semester-filter" title="Clear search" aria-label="Clear academic year search">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
      </div>
      <div class="header-actions">
        <button class="add-program-btn button-gradient" id="addAcademicYearBtn">
          <i class="ph ph-plus"></i> Add New
        </button>
      </div>
    </div>

  </div>

  <!-- Academic Year Table -->
  <div class="table-wrapper academic-year-results">
    <div class="table-scroll-container">
      <table class="academic-year-table">
        <thead>
          <tr>
            <th>Academic Year</th>
            <th>Semester</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="academicYearTableBody">
          <tr>
            <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
              <i class="ph ph-calendar-blank" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.5;"></i>
              No academic years added yet. Click "Add New" to get started.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Academic Year Modal -->
<div id="addAcademicYearModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>ADD ACADEMIC YEAR</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <label for="academic-year">Academic Year</label>
      <input type="text" id="academic-year" placeholder="e.g., 2024-2025">
      
      <label for="semester">Semester</label>
      <select id="semester">
        <option value="">Select Semester</option>
        <option value="1st Semester">1st Semester</option>
        <option value="2nd Semester">2nd Semester</option>
        <option value="Summer">Summer</option>
      </select>
      
      <button id="save-academic-year-btn" class="submit-btn">SAVE</button>
    </div>
  </div>
</div>

<!-- Report Section =========================================================================================================-->
<div id="report-section" class="section" style="display:none;">
  <div class="table-wrapper">
    <div class="table-header">
      <div class="search-filters-container">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="searchInput" placeholder="Search Faculty...">
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-calendar-blank"></i>
          <select id="report-ay-filter" class="program-dropdown">
            <option value="">All Academic Years</option>
          </select>
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-calendar"></i>
          <select id="report-semester-filter" class="program-dropdown">
            <option value="">All Semesters</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="Summer">Summer</option>
          </select>
        </div>
      </div>
      <div class="header-actions">
        <button type="button" class="clear-search-btn report-clear-btn" data-clear-targets="searchInput,report-ay-filter,report-semester-filter" title="Clear report filters" aria-label="Clear report filters">
          <i class="ph ph-arrow-clockwise"></i>
        </button>
        <button class="generate-report-btn" onclick="generateEvaluationReport()">
          <i class="ph ph-download-simple"></i> Generate Report
        </button>
      </div>
    </div>
    <div class="table-scroll-container">
      <table class="evaluation-table">
        <thead>
          <tr>
            <th>Faculty Name</th>
            <th>Academic Year / Semester</th>
            <th>Overall Rating</th>
            <th>Responses</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="evaluationTableBody">
          <!-- Dynamic content will be loaded here -->
        </tbody>
      </table>
    </div>
    <div class="report-analytics" aria-label="Evaluation report analytics">
      <div class="report-chart-card report-distribution-card">
        <div class="report-chart-heading">
          <div><h3>Overall Rating Distribution</h3></div>
        </div>
        <div class="report-donut-layout"><div class="report-donut-canvas"><canvas id="reportRatingDistributionChart"></canvas></div><div id="reportRatingLegend" class="report-chart-legend"></div></div>
      </div>
      <div class="report-chart-card report-program-card">
        <div class="report-chart-heading"><div><h3>Average Ratings by Program</h3></div></div>
        <div class="report-bar-canvas"><canvas id="reportProgramRatingsChart"></canvas></div>
      </div>
      <div class="report-chart-card report-trend-card">
        <div class="report-chart-heading"><div><h3>Ratings Trend Over Time</h3></div></div>
        <div class="report-line-canvas"><canvas id="reportRatingsTrendChart"></canvas></div>
      </div>
      <div id="reportRankingCard" class="report-chart-card report-ranking-card" onclick="openReportFacultyRankings(event)" onkeydown="if (event.key === 'Enter' || event.key === ' ') openReportFacultyRankings(event)" tabindex="0" role="button" aria-label="View all report faculty rankings">
        <div class="report-chart-heading rating-header"><div><h3>Top Faculty Rankings</h3></div></div>
        <div id="reportRankingClickTarget" class="top-performance-chart-wrapper" role="button" tabindex="0" aria-label="View all report faculty rankings">
          <div id="reportFacultyRankings" class="top-performance-progress-list" role="button" tabindex="0" aria-label="View all report faculty rankings"></div>
        </div>
        <div class="top-performance-hint">Showing the highest-ranked evaluated faculties. Click the list to view the full ranking.</div>
      </div>
    </div>
  </div>
</div>

<!-- View Faculty Subjects Modal -->
<div id="viewFacultySubjectsModal" class="modal" style="display:none;">
  <div class="modal-content faculty-profile-modal-content" style="max-width:700px;">
    <div class="faculty-profile-header">
      <div id="viewFacultyInitials" class="faculty-profile-avatar">FA</div>
      <div class="faculty-profile-details">
        <h3 id="viewFacultyName">Faculty Name</h3>
        <p id="viewFacultyMeta"><span class="faculty-header-id">Faculty ID</span></p>
      </div>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="faculty-subject-list">
        <div class="faculty-subjects-heading">
          <h4>Assigned Subjects</h4>
          <span id="viewFacultySubjectCount" class="faculty-subject-count">0 subjects</span>
        </div>
        <div id="viewFacultySubjectsList" class="subjects-container">
          <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- View Student Subjects Modal -->
<div id="viewStudentSubjectsModal" class="modal" style="display:none;">
  <div class="modal-content student-profile-modal-content" style="max-width:700px;">
    <div class="student-profile-header">
      <div id="viewStudentInitials" class="student-profile-avatar">ST</div>
      <div class="student-profile-details">
        <h3 id="viewStudentName">Student Name</h3>
        <p id="viewStudentMeta">Student information</p>
      </div>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div id="viewStudentSubjectsList" class="subjects-container student-subject-list">
        <div class="student-subjects-heading">
          <h4>Enrolled Subjects</h4>
          <span id="viewStudentSubjectCount" class="student-subject-count">0 subjects</span>
        </div>
        <div id="studentSubjectRows">
          <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- View Class Subjects Modal -->
<div id="viewClassSubjectsModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:700px;">
    <div class="modal-header">
      <h3>Class Subjects</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="student-info-header">
        <div class="student-avatar">
          <i class="ph ph-chalkboard-teacher"></i>
        </div>
        <div class="student-details">
          <h4 id="viewClassName">Class Name</h4>
          <p class="student-subtitle">Assigned Subjects and Instructor</p>
        </div>
      </div>
      
      <div id="viewClassSubjectsList" class="subjects-container">
        <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
      </div>
    </div>
  </div>
</div>

<!-- Manage Periods Modal -->
<div id="managePeriodsModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:800px;">
    <div class="modal-header">
      <h3>MANAGE PERIODS</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <!-- Create New Period Section -->
      <div class="period-creation-section">
        <div class="period-section-header">
          <div>
            <h4 class="period-section-title">Create New Period</h4>
            <p class="period-section-subtitle">Define the academic year, semester, and date range</p>
          </div>
        </div>

        <div class="period-form-grid">
          <div class="period-input-group">
            <label for="period-ay">Academic Year</label>
            <input type="text" id="period-ay" placeholder="Set from dashboard" readonly>
            <div class="period-tooltip" id="ay-tooltip" style="display:none;">2025-2026</div>
          </div>

          <div class="period-input-group">
            <label for="period-sem">Semester</label>
            <select id="period-sem" disabled>
              <option value="" selected disabled>Select semester</option>
              <option value="1st Semester">1st Semester</option>
              <option value="2nd Semester">2nd Semester</option>
              <option value="Summer">Summer</option>
            </select>
          </div>

          <div class="period-input-group">
            <label for="period-start">Start Date</label>
            <div class="date-input-wrapper">
              <input type="text" id="period-start" placeholder="YYYY-MM-DD" readonly>
              <i class="ph ph-calendar-blank calendar-icon" data-target="period-start"></i>
              <div class="calendar-picker" id="period-start-calendar">
                <div class="calendar-header">
                  <button class="action-icon-btn calendar-nav" data-direction="prev" type="button" aria-label="Previous month">
                    <i class="ph ph-caret-left"></i>
                  </button>
                  <span class="calendar-month-year"></span>
                  <button class="action-icon-btn calendar-nav" data-direction="next" type="button" aria-label="Next month">
                    <i class="ph ph-caret-right"></i>
                  </button>
                </div>
                <div class="calendar-grid">
                  <div class="calendar-day-header">Sun</div>
                  <div class="calendar-day-header">Mon</div>
                  <div class="calendar-day-header">Tue</div>
                  <div class="calendar-day-header">Wed</div>
                  <div class="calendar-day-header">Thu</div>
                  <div class="calendar-day-header">Fri</div>
                  <div class="calendar-day-header">Sat</div>
                </div>
              </div>
            </div>
          </div>

          <div class="period-input-group">
            <label for="period-end">End Date</label>
            <div class="date-input-wrapper">
              <input type="text" id="period-end" placeholder="YYYY-MM-DD" readonly>
              <i class="ph ph-calendar-blank calendar-icon" data-target="period-end"></i>
              <div class="calendar-picker" id="period-end-calendar">
                <div class="calendar-header">
                  <button class="action-icon-btn calendar-nav" data-direction="prev" type="button" aria-label="Previous month">
                    <i class="ph ph-caret-left"></i>
                  </button>
                  <span class="calendar-month-year"></span>
                  <button class="action-icon-btn calendar-nav" data-direction="next" type="button" aria-label="Next month">
                    <i class="ph ph-caret-right"></i>
                  </button>
                </div>
                <div class="calendar-grid">
                  <div class="calendar-day-header">Sun</div>
                  <div class="calendar-day-header">Mon</div>
                  <div class="calendar-day-header">Tue</div>
                  <div class="calendar-day-header">Wed</div>
                  <div class="calendar-day-header">Thu</div>
                  <div class="calendar-day-header">Fri</div>
                  <div class="calendar-day-header">Sat</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="period-form-footer">
          <button id="add-period-btn" class="period-add-btn">
            <i class="ph ph-plus"></i>Add Period
          </button>
        </div>
      </div>

      <!-- Existing Periods Table -->
      <div class="periods-table-section">
        <div class="periods-table-header">
          <div class="periods-table-title-group">
            <i class="ph ph-clock-countdown"></i>
            <span>Existing Periods</span>
          </div>
        </div>
        <table class="periods-table">
          <colgroup>
            <col style="width:32%">
            <col style="width:30%">
            <col style="width:22%">
            <col style="width:16%">
          </colgroup>
          <thead>
            <tr>
              <th>PERIOD NAME</th>
              <th>DURATION</th>
              <th>STATUS</th>
              <th>ACTION</th>
            </tr>
          </thead>
          <tbody id="periods-tbody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Notification for Add/Edit Success -->
<div id="notification" style="
    position: fixed;
    top: 90px;                  /* distance from top */
    left: 50%;                  /* center horizontally */
    transform: translateX(-50%);/* adjust by half its width */
    padding: 12px 20px;
    background-color: #4caf50;
    color: white;
    border-radius: 5px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    display: none;
    z-index: 9999;
    font-family: sans-serif;
    font-size: 14px;
"></div>

<!-- DEPRECATED: Custom Notification Modal - replaced with unified showGlobalNotification system -->
<!-- 
<div id="notificationModal" class="notification-modal">
  <div class="notification-modal-content">
    <div class="notification-modal-icon">
      <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h3 class="notification-modal-title">Limit Reached</h3>
    <p class="notification-modal-message">Maximum of 5 questions per category reached!</p>
    <button id="notificationModalOkBtn" class="notification-modal-btn">OK</button>
  </div>
</div>
-->

<!-- Faculty Report Modal -->
<div id="facultyReportModal" class="modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center;">
  <div class="modal-content report-modal-content">
    <div class="modal-header report-modal-header">
      <h3>Evaluation Details</h3>
      <span class="close-btn" onclick="closeFacultyReportModal()">&times;</span>
    </div>
    <div class="modal-body report-modal-body">
      <div class="faculty-info-header">
        <div id="reportFacultyInitials" class="faculty-avatar" aria-hidden="true">FA</div>
        <div class="faculty-details">
          <h4 id="reportFacultyName">Loading...</h4>
          <p id="reportFacultyId">ID: Loading...</p>
          <p id="reportEvaluationPeriod" class="faculty-meta">All evaluation periods</p>
        </div>
        <button class="download-pdf-btn" onclick="downloadFacultyReportPDF()">
          <i class="ph ph-download-simple"></i> Download PDF
        </button>
      </div>

      <div class="details-summary-grid">
        <div class="report-card">
          <span class="report-card-label">Overall Rating</span>
          <strong id="reportOverallRating" class="report-card-value">-</strong>
          <span class="report-card-note">out of 5.0</span>
          <span id="reportOverallPercentage" class="report-card-percent">0%</span>
          <div class="report-card-progress" aria-hidden="true">
            <div id="reportOverallProgressFill" class="report-card-progress-fill"></div>
          </div>
        </div>
        <div class="report-card">
          <span class="report-card-label">Total Responses</span>
          <strong id="reportTotalResponses" class="report-card-value">-</strong>
          <span class="report-card-note">evaluation responses</span>
        </div>
        <div class="report-card status-card">
          <span class="report-card-label">Report Status</span>
          <strong id="reportOverallStatus" class="report-card-value">-</strong>
          <span class="report-card-note">performance indicator</span>
        </div>
      </div>

      <div class="report-section-title">
        <div>
          <h4>Evaluation Categories</h4>
          <p>Category scores shown as percentage progress.</p>
        </div>
        <span id="reportCategoryCount" class="section-count">0 categories</span>
      </div>

      <div id="reportCategoryList" class="category-list">
        <span class="category-list-empty">Loading categories...</span>
      </div>

      <div class="evaluation-details-table-wrap">
        <div class="eval-details-label">
          <h4><i class="ph ph-list-checks"></i> Evaluation Details</h4>
        </div>
        <div class="table-scroll">
          <table class="evaluation-details-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Overall Rating</th>
                <th>Responses</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="reportEvaluationDetailsBody">
              <tr>
                <td colspan="4" style="padding: 14px; text-align: center; color: #6b7280;">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="feedback-section">
        <div class="report-section-title compact">
          <div>
            <h4>Student Feedback</h4>
            <p>Comments submitted by students for this faculty.</p>
          </div>
          <span id="reportFeedbackCount" class="section-count">0 comments</span>
        </div>
        <div id="reportFeedbackText" class="feedback-list">
          <div class="feedback-loading">Loading feedback...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Archive Confirmation Modal -->
<div id="archiveModal" class="archive-modal-overlay">
  <div class="archive-modal-box">
    <div class="archive-modal-icon">
      <i class="ph ph-archive-box"></i>
    </div>
    <h3 class="archive-modal-title">Archive Faculty</h3>
    <p class="archive-modal-message">Archive this faculty account?</p>
    <div class="archive-modal-buttons">
      <button class="archive-btn-cancel" onclick="closeArchiveModal()">Cancel</button>
      <button class="archive-btn-confirm" id="archiveConfirmBtn">Yes, Archive</button>
    </div>
  </div>
</div>

<script src="unified_notifications.js?v=<?php echo time(); ?>"></script>
<script src="session_keepalive.js?v=<?php echo time(); ?>"></script>
<script src="FacultyAdmin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
