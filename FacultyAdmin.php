
<?php
include 'totalstudents_dashcount.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Faculty Evaluation System</title>
  <link rel="stylesheet" href="FacultyAdmin.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

    <div class="admin-box" id="dropdownToggle">
      <div class="admin-icon">
        <span>SA</span>
      </div>
      <div class="admin-text">
        <span class="admin-title">System Administrator</span>
        <span class="admin-subtitle">admin</span>
      </div>
      <i class="ph ph-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" id="logoutLink">
        <i class="ph ph-sign-out"></i>
        Logout
      </a>
    </div>

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
      <button type="button" class="btn logout-btn" onclick="confirmLogout()">
        Yes, Log me out
      </button>

      <button type="button" class="btn cancel-btn" onclick="closeLogoutModal()">
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
      <img src="schoollogo.png" class="sidebar-logo">
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
          <i class="ph ph-chart-line"></i>
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
          <i class="ph ph-book"></i>
          <span>Program</span>
        </a>
        <a href="#" data-section="faculties-section"
           onclick="showSection('faculties-section', event)">
          <i class="ph ph-chalkboard-teacher"></i>
          <span>Faculty</span>
        </a>
        <a href="#" data-section="students-section"
           onclick="showSection('students-section', event)">
          <i class="ph ph-graduation-cap"></i>
          <span>Students</span>
        </a>
        <a href="#" data-section="criteria-section"
           onclick="showSection('criteria-section', event)">
          <i class="ph ph-list-checks"></i>
          <span>Evaluation Criteria</span>
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
          <i class="ph ph-file-text"></i>
          <span>Reports</span>
        </a>
      </div>
    </div>

  </nav>
  
</div>

<main>
<!-- Dashboard Section  =====================================================================================================================================-->
<div id="dashboard-section" class="section">
  <div class="dashboard">

    <!-- Faculty Card -->
    <div class="dashboard-card faculty">
      <div class="dashboard-content">
        <div>
          <h3>Total Faculty</h3>
          <p id="totalFaculty" class="dashboard-value"><?php echo $totalFacultyCount; ?></p>
          <span>Across 9 Colleges</span>
        </div>
        <div class="icon-box"><i class="ph ph-chalkboard-teacher"></i></div>
      </div>
    </div>

    <!-- Students Card -->
    <div class="dashboard-card students">
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
          <h3>Total Evaluations</h3>
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
          <p id="overallRating" class="dashboard-value">4.2</p>
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
            <div class="legend-color excellence"></div>
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
          <h3>Top 5 Faculty Performance</h3>
        </div>
        <div class="top-performance-chart-wrapper">
          <canvas id="topPerformanceChart" height="220"></canvas>
        </div>
      </div>
    </div>

  </div>

  <!-- Third Row -->
  <div class="dashboard-row-3">
    <div class="period-container">
      <div class="dashboard-card period">
        <div class="dashboard-content">
          <div class="period-header">
            <h3>Period</h3>
            <div class="period-box">No Active Period</div>
          </div>
          <p class="system-status">Evaluation is Open</p>
          <div class="period-actions">
            <button class="btn-close">Close</button>
            <button class="btn-manage">Manage</button>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-card evaluation-progress">
      <div class="dashboard-content">
        <div class="rating-header">
          <h3>Evaluation Progress</h3>
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
  <div class="section-header">
    <div class="header-title-section">
      <h2 id="manageTitle">Manage Program</h2>
      <div class="manage-buttons">
        <button class="subject-btn active"><i class="ph ph-book"></i> Subjects</button>
        <button class="classes-btn"><i class="ph ph-users"></i> Classes</button>
      </div>
    </div>
    <div class="header-actions-section">
      <div class="search-wrapper">
        <input type="text" id="manage-search" placeholder="Search records...">
        <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
      </div>
      <button class="add-manage-btn button-gradient"><i class="ph ph-plus"></i> Add</button>
      <button class="back-btn"><i class="ph ph-arrow-left"></i> Back</button>
    </div>
  </div>

  
  <!-- Subject Table -->
  <div class="manage-body">
    <div id="subjects" class="table-wrapper">
      <div class="table-scroll-container">
        <table class="subjects-table"><thead><tr>
          <th>Subject Code</th><th>Description</th><th>Year</th><th>Action</th>
        </tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- Classes Table -->
    <div id="classes" class="table-wrapper" style="display:none;">
      <div class="table-scroll-container">
        <table class="classes-table"><thead><tr>
          <th>Section Name</th><th>Year Level</th><th>Semester</th><th>Status</th><th>Action</th>
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
      <label for="subject-year">Year</label>
      <select id="subject-year">
        <option value="">Select Year Level</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
      </select>
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
          <label for="class-year"><i class="ph ph-graduation-cap"></i> Year Level</label>
          <select id="class-year">
            <option value="">Select Year Level</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>
        <div>
          <label for="class-block"><i class="ph ph-hash"></i> Section</label>
          <input type="text" id="class-block">
        </div>
        <div>
          <label for="class-semester"><i class="ph ph-calendar-check"></i> Semester</label>
          <select id="class-semester">
            <option value="">Select Semester</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
          </select>
        </div>
      </div>
      <div class="section-box">
        <div id="subject-checkbox-list" class="subjects-list">
          <h4><i class="ph ph-book"></i> Assigned Subjects</h4>
          <div class="subject-filters">
            <div class="search-input-wrapper">
              <input type="text" id="class-program-search" placeholder="Search by program..." class="subject-search-input">
              <i class="ph ph-magnifying-glass"></i>
            </div>
            <div class="search-input-wrapper">
              <input type="text" id="class-year-search" placeholder="Search by year level..." class="subject-search-input">
              <i class="ph ph-magnifying-glass"></i>
            </div>
          </div>
          <small>Select year level first to load subjects</small>
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
      <div class="search-wrapper">
        <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
        <input type="text" id="subjects-search" placeholder="Search subjects...">
      </div>
      <button class="add-subject-main-btn button-gradient"><i class="ph ph-plus"></i> Add Subject</button>
    </div>
  </div>
  <div class="table-wrapper">
    <div class="table-scroll-container">
      <table class="subjects-table">
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Description</th>
            <th>Program</th>
            <th>Year Level</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="subjects-main-tbody">
          <tr>
            <td colspan="5" style="text-align: center; padding: 20px;">Loading subjects...</td>
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

  <div class="table-wrapper">
    <div class="table-header">
      <div class="search-filters-container">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input id="faculty-search" placeholder="Search faculty...">
        </div>
        <div class="filter-wrapper">
          <i class="ph ph-funnel" style="color: #64748b; font-size: 16px; margin-right: 8px;"></i>
          <select id="faculty-status-filter" class="status-dropdown">
            <option value="">Filter by Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="header-actions">
        <button class="add-faculty-btn button-gradient"><i class="ph ph-plus"></i> Add Faculty</button>
      </div>
    </div>
    <div class="table-scroll-container">
      <table class="faculties-table">
        <thead>
          <tr><th>Faculty ID</th><th>Faculty Name</th><th>Subjects</th><th>Status</th><th>Actions</th></tr>
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
        <input type="file" id="faculty-photo" accept="image/*" hidden>
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
          <div class="subject-filters">
            <input type="text" id="faculty-program-search" placeholder="Search by program..." class="subject-search-input">
            <input type="text" id="faculty-year-search" placeholder="Search by year level..." class="subject-search-input">
          </div>
        </div>
      </div>
      <button class="submit-btn">SAVE FACULTY</button>
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

  <div class="table-wrapper">
    <div class="table-header">
      <div class="search-filters-container">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input id="student-search" placeholder="Search student...">
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-funnel" style="color: #64748b; font-size: 16px; margin-right: 8px;"></i>
          <select id="student-program-filter" class="program-dropdown">
            <option value="">All Programs</option>
          </select>
        </div>
        <div class="program-filter-wrapper">
          <i class="ph ph-graduation-cap" style="color: #64748b; font-size: 16px; margin-right: 8px;"></i>
          <select id="student-yearlevel-filter" class="program-dropdown">
            <option value="">All Year Levels</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
            <option value="irregular">Irregular</option>
          </select>
        </div>
      </div>
      <div class="header-actions">
        <button class="add-student-btn button-gradient"><i class="ph ph-plus"></i> Add Student</button>
      </div>
    </div>
    <div class="table-scroll-container">
      <table class="students-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Year Level & Section</th><th>Status</th><th>Action</th></tr>
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
  <div class="section-header">
    <div class="header-title-section">
    </div>
    <div class="header-actions" style="width:100%; display:flex; align-items:center; justify-content:space-between; padding-right:0; margin-left:0;">
      <span style="font-size: 15px; color: #64748b; display:flex; align-items:center; gap:8px;">
        <i class="ph ph-info"></i>
        This section shows the list of criteria used for evaluating faculty performance.
      </span>
      <button id="addCategoryBtn" class="add-program-btn button-gradient">
        <i class="ph ph-plus"></i> Add Category
      </button>
    </div>
  </div>


  <div class="criteria-layout" style="display:flex; gap:24px; align-items:flex-start;">
    <div class="categories-column" id="categoriesColumn" style="flex:2;"></div>

    <div class="summary-panel" style="flex:1;">
      <h4><i class="ph ph-clipboard-check"></i> Summary</h4>
      <p><strong>Categories:</strong> <span id="total-categories">0</span></p>
      <p><strong>Total Questions:</strong> <span id="total-questions">0</span></p>
      <div class="legend">
        <h5><i class="ph ph-list-bullets"></i> LEGEND</h5>
        <ul>
          <li data-value="5"><span class="badge">5</span> Strongly Agree - Excellent</li>
          <li data-value="4"><span class="badge">4</span> Agree - Very Good</li>
          <li data-value="3"><span class="badge">3</span> Neutral - Satisfactory</li>
          <li data-value="2"><span class="badge">2</span> Disagree - Fair</li>
          <li data-value="1"><span class="badge">1</span> Strongly Disagree - Poor</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Category Form -->
<div id="addCategoryModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:500px;">
    <div class="modal-header">
      <h3 id="categoryModalTitle">ADD CATEGORY</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <label for="category-name">Category Name</label>
      <input type="text" id="category-name" placeholder="eg. Instructional Competence">
      <label for="section-number">Section #</label>
      <input type="number" id="section-number" min="1" placeholder="eg. 1">
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

<!-- Report Section =========================================================================================================-->
<div id="report-section" class="section" style="display:none;">
  <div class="table-wrapper">
    <div class="table-header">
      <div class="search-filters-container">
        <div class="search-wrapper">
          <button class="search-btn"><i class="ph ph-magnifying-glass"></i></button>
          <input type="text" id="searchInput" placeholder="Search Faculty...">
        </div>
      </div>
      <div class="header-actions">
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
  </div>
</div>

<!-- View Faculty Subjects Modal -->
<div id="viewFacultySubjectsModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:700px;">
    <div class="modal-header">
      <h3>Faculty Subjects</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="student-info-header">
        <div class="student-avatar">
          <i class="ph ph-user"></i>
        </div>
        <div class="student-details">
          <h4 id="viewFacultyName">Faculty Name</h4>
          <p class="student-subtitle">Assigned Subjects</p>
        </div>
      </div>
      <div id="viewFacultySubjectsList" class="subjects-container">
        <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
      </div>
    </div>
  </div>
</div>

<!-- View Student Subjects Modal -->
<div id="viewStudentSubjectsModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:700px;">
    <div class="modal-header">
      <h3>Student Subjects</h3>
      <span class="close-btn">&times;</span>
    </div>
    <div class="modal-body">
      <div class="student-info-header">
        <div class="student-avatar">
          <i class="ph ph-graduation-cap"></i>
        </div>
        <div class="student-details">
          <h4 id="viewStudentName">Student Name</h4>
          <p class="student-subtitle">Enrolled Subjects</p>
        </div>
      </div>
      
      <div id="viewStudentSubjectsList" class="subjects-container">
        <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
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
          <i class="fas fa-chalkboard-teacher"></i>
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
        <h4 style="color: #dc2626; margin-bottom: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
          <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>CREATE NEW PERIOD
        </h4>
        <div class="period-form-row">
          <div class="period-input-group">
            <label for="period-ay">
              <i class="fas fa-calendar-alt" style="margin-right: 4px; font-size: 0.8rem;"></i>AY
            </label>
            <input type="text" id="period-ay" placeholder="e.g. 2025-2026">
            <div class="period-tooltip" id="ay-tooltip" style="display:none;">2025-2026</div>
          </div>
          
          <div class="period-input-group">
            <label for="period-sem">
              <i class="fas fa-graduation-cap" style="margin-right: 4px; font-size: 0.8rem;"></i>Semester
            </label>
            <select id="period-sem">
              <option value="1st Semester" selected>1st Semester</option>
              <option value="2nd Semester">2nd Semester</option>
              <option value="Summer">Summer</option>
            </select>
          </div>
          
          <div class="period-input-group">
            <label for="period-start">
              <i class="fas fa-play-circle" style="margin-right: 4px; font-size: 0.8rem;"></i>Start Date
            </label>
            <div class="date-input-wrapper">
              <input type="text" id="period-start" placeholder="dd/mm/yyyy" readonly>
              <i class="fas fa-calendar calendar-icon" data-target="period-start"></i>
              <div class="calendar-picker" id="start-calendar">
                <div class="calendar-header">
                  <button class="calendar-nav" data-direction="prev">&lt;</button>
                  <span class="calendar-month-year"></span>
                  <button class="calendar-nav" data-direction="next">&gt;</button>
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
            <label for="period-end">
              <i class="fas fa-stop-circle" style="margin-right: 4px; font-size: 0.8rem;"></i>End Date
            </label>
            <div class="date-input-wrapper">
              <input type="text" id="period-end" placeholder="dd/mm/yyyy" readonly>
              <i class="fas fa-calendar calendar-icon" data-target="period-end"></i>
              <div class="calendar-picker" id="end-calendar">
                <div class="calendar-header">
                  <button class="calendar-nav" data-direction="prev">&lt;</button>
                  <span class="calendar-month-year"></span>
                  <button class="calendar-nav" data-direction="next">&gt;</button>
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
        
        <div style="text-align: right; margin-top: 24px;">
          <button id="add-period-btn" class="period-add-btn">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Add Period
          </button>
        </div>
      </div>

      <!-- Existing Periods Table -->
      <div class="periods-table-section">
        <table class="periods-table">
          <thead>
            <tr>
              <th>PERIOD NAME</th>
              <th>DURATION</th>
              <th>STATUS</th>
              <th>ACTION</th>
            </tr>
          </thead>
          <tbody id="periods-tbody">
            <tr>
              <td>1st 2025-2026</td>
              <td>Jan 01 - May 01, 2026</td>
              <td><span class="status-badge active">ACTIVE</span></td>
              <td class="action-cell">
                <div class="action-buttons">
                  <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
                  <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                </div>
              </td>
            </tr>
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

<!-- Custom Notification Modal -->
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

<!-- Faculty Report Modal -->
<div id="facultyReportModal" class="modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center;">
  <div class="modal-content" style="max-width:500px;">
    <div class="modal-header" style="background: var(--primary-900); color: white;">
      <h3 style="margin: 0; color: white;">Evaluation Details</h3>
      <span class="close-btn" onclick="closeFacultyReportModal()" style="color: white;">&times;</span>
    </div>
    <div class="modal-body" style="background: white; padding: 20px;">
      <!-- Faculty Info Header -->
      <div class="faculty-info-header" style="display: flex; align-items: center; margin-bottom: 15px; padding: 12px; background: #2c5282; border-radius: 6px; color: white;">
        <div class="faculty-avatar" style="width: 35px; height: 35px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
          <i class="ph ph-user" style="font-size: 18px; color: var(--primary-900);"></i>
        </div>
        <div class="faculty-details" style="flex: 1;">
          <h4 id="reportFacultyName" style="margin: 0; font-size: 16px; font-weight: 600; color: white;">Loading...</h4>
          <p id="reportFacultyId" style="margin: 2px 0 0 0; opacity: 0.9; font-size: 11px; color: white;">ID: Loading...</p>
        </div>
        <button class="download-pdf-btn" onclick="downloadFacultyReportPDF()" style="background: white; color: var(--primary-900); border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; font-size: 11px;">
          <i class="ph ph-download-simple"></i> PDF
        </button>
      </div>

      <!-- Stats Cards -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <!-- Overall Rating Card -->
        <div class="stat-card" style="background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
          <div style="font-size: 11px; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Overall Rating</div>
          <div id="reportOverallRating" style="font-size: 24px; font-weight: 700; margin-bottom: 2px; color: var(--primary-900);">-</div>
          <div style="font-size: 10px; color: #9ca3af;">out of 5.0</div>
        </div>

        <!-- Total Responses Card -->
        <div class="stat-card" style="background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
          <div style="font-size: 11px; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Total Responses</div>
          <div id="reportTotalResponses" style="font-size: 24px; font-weight: 700; margin-bottom: 2px; color: var(--primary-900);">-</div>
          <div style="font-size: 10px; color: #9ca3af;">evaluation responses</div>
        </div>
      </div>

      </div>
  </div>
</div>

<script src="FacultyAdmin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
