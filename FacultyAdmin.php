
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<div class="navbar">

  <!-- Left Side -->
  <div class="nav-left">

    <div class="hamburger" onclick="toggleSidebar()">
      <i class="fas fa-bars"></i>
    </div>

    
    <div class="section-title" id="navbarSectionTitle">Dashboard</div>

  </div>

  <!-- Right Side User Menu -->
  <div class="user-menu">

    <div class="admin-box" id="dropdownToggle">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Admin" class="logo-img">
      <span>Administrator</span>
      <i class="fas fa-caret-down"></i>
    </div>

    <div class="dropdown-menu" id="dropdownMenu">
      <a href="#" id="logoutLink">
        <i class="fas fa-sign-out-alt"></i>
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
        <i class="fas fa-sign-out-alt fa-2x"></i>
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

    <img src="schoollogo.png" class="sidebar-logo">
    <h1>FaculRate</h1>
    <span>Admin Panel</span>

  </div>

  <!-- Menu -->
  <nav class="sidebar-menu">

    <a href="#" data-section="dashboard-section"
       onclick="showSection('dashboard-section', event)">
      <i class="fas fa-tachometer-alt"></i>
      <span>Dashboard</span>
    </a>

    <a href="#" data-section="programs-section"
       onclick="showSection('programs-section', event)">
      <i class="fas fa-book"></i>
      <span>Program</span>
    </a>

    <a href="#" data-section="faculties-section"
       onclick="showSection('faculties-section', event)">
      <i class="fas fa-user-tie"></i>
      <span>Faculty</span>
    </a>

    <a href="#" data-section="students-section"
       onclick="showSection('students-section', event)">
      <i class="fas fa-user-graduate"></i>
      <span>Students</span>
    </a>

    <a href="#" data-section="criteria-section"
       onclick="showSection('criteria-section', event)">
      <i class="fas fa-list"></i>
      <span>Evaluation Criteria</span>
    </a>

    <a href="#" data-section="report-section"
       onclick="showSection('report-section', event)">
      <i class="fas fa-chart-bar"></i>
      <span>Evaluation Report</span>
    </a>

  </nav>

</div>

<main>
<!-- Dashboard Section May nabago =====================================================================================================================================-->
<div id="dashboard-section" class="section">
  <!--<div class="dashboard-wrapper">
    <div class="dashboard-box">
      <h1>Welcome, Administrator</h1>
      <p>This dashboard provides administrators with a clear overview of ongoing faculty evaluations and results.</p>
      <div class="warning"><i class="fas fa-exclamation-triangle"></i> Authorized Personnel Only: All actions are logged and monitored for security compliance.</div>
    </div>-->
  <div class="dashboard">
    <div class="dashboard-card faculty">
      <div class="dashboard-content">
        <div>
          <h3>Total Faculty</h3>
          <p id="totalFaculty" class="dashboard-value"><?php echo $totalFacultyCount; ?></p>
          <span>Across 9 Colleges</span>
        </div>
        <div class="icon-box"><i class="fas fa-user-tie"></i></div>
      </div>
    </div>

    <div class="dashboard-card students">
      <div class="dashboard-content">
        <div>
          <h3>Total Students</h3>
          <p id="totalStudents" class="dashboard-value"><?php echo $totalStudentsCount; ?></p>
          <span>Registered Users</span>
        </div>
        <div class="icon-box"><i class="fas fa-user-graduate"></i></div>
      </div>
    </div>

    <div class="dashboard-card evaluations">
      <div class="dashboard-content">
        <div>
          <h3>Total Evaluations</h3>
          <p id="totalEvaluations" class="dashboard-value"><?php echo $totalEvaluationsCount; ?></p>
          <span>Overall</span>
        </div>
        <div class="icon-box"><i class="fas fa-chart-line"></i></div>
      </div>
    </div>

    <!-- 🔹 Period Card replacing Programs -->
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
</div>



 <!--
<div id="dashboard-details">
  <div class="ratings-box">
    <h4><i class="fas fa-star"></i> Faculty Ratings</h4>
    <table>
      <thead><tr><th>Faculty Name</th><th>Rating</th></tr></thead>
      <tbody id="ratings-body"></tbody>
    </table>
  </div>

  <div class="ranking-box">
    <h4><i class="fas fa-trophy"></i> Faculty Ranking</h4>
    <table>
      <thead><tr><th>Rank</th><th>Faculty</th><th>Ratings</th></tr></thead>
      <tbody id="ranking-body"></tbody>
    </table>
  </div>

  <div class="graph-box">
    <h4><i class="fas fa-chart-bar"></i> Responded per Department</h4>
    <canvas id="departmentGraph"></canvas>
  </div>
</div>-->

<!-- Programs Section ============================================================================================================================== -->
<div id="programs-section" class="section" style="display:none;">
  <div class="box">
    <div class="section-header">
      <div class="header-title-section">
        <!--<h2>Academic Program</h2>-->
        <p class="section-subtitle"><i class="fas fa-book" style="margin-right: 8px; font-size: 1em; color: var(--primary-600);"></i>Manage and update academic programs offered by the institution</p>
      </div>
      <div class="header-actions">
        <div class="search-wrapper">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <input type="text" id="program-search" placeholder="Search program..."/>
        </div>
        <button class="add-program-btn button-gradient"><i class="fas fa-plus"></i> Add Program</button>
      </div>
    </div>
    <div class="table-wrapper"> 
      <table class="programs-table">
        <thead><tr><th>Program Code</th><th>Program Name</th><th>Action</th></tr></thead>
        <tbody></tbody>
      </table>
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
    <div class="delete-icon-circle"><i class="fas fa-exclamation-triangle"></i></div>
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
    <div class="success-icon-circle"><i class="fas fa-check-circle"></i></div>
    <p class="success-main-text">Deleted successfully!</p>
    <div class="success-buttons">
      <button id="success-ok-btn" class="ok-btn">OK</button>
    </div>
  </div>
</div>


<!-- Manage Section ==============================================================================================================================-->
<div id="manage-section" class="section" style="display:none;">
  <div class="box">
    <div class="section-header">
      <div class="header-title-section">
        <h2 id="manageTitle">Manage Program</h2>
        <div class="manage-buttons">
          <button class="subject-btn active"><i class="fas fa-book"></i> Subjects</button>
          <button class="classes-btn"><i class="fas fa-users"></i> Classes</button>
        </div>
      </div>
      <div class="header-actions-section">
        <div class="search-wrapper">
          <input type="text" placeholder="Search records...">
          <button class="search-btn"><i class="fas fa-search"></i></button>
        </div>
        <button class="add-manage-btn button-gradient"><i class="fas fa-plus"></i> Add</button>
        <button class="back-btn"><i class="fas fa-arrow-left"></i> Back</button>
      </div>
    </div>

    
    <!-- Subject Table -->
    <div class="manage-body">
      <div id="subjects" class="table-wrapper">
        <table class="subjects-table"><thead><tr>
          <th>Subject Code</th><th>Description</th><th>Year</th><th>Action</th>
        </tr></thead>
          <tbody></tbody>
        </table>
      </div>

      <!-- Classes Table -->
      <div id="classes" class="table-wrapper" style="display:none;">
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
          <label for="class-name">Section Name</label>
          <input type="text" id="class-name" placeholder="eg. 1A, 2B">
        </div>
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
          <label for="class-block">Block</label>
          <input type="text" id="class-block" placeholder="eg. A, B, C">
        </div>
      </div>
      <div class="section-box">
        <div id="subject-checkbox-list" class="subjects-list">
          <h4><i class="fas fa-book"></i> Assigned Subjects</h4>
          <div class="subject-filters">
            <input type="text" id="class-program-search" placeholder="Search by program..." class="subject-search-input">
            <input type="text" id="class-year-search" placeholder="Search by year level..." class="subject-search-input">
          </div>
          <small>Select year level first to load subjects</small>
        </div>
      </div>
      <div class="section-box">
        <div id="faculty-list" class="subjects-list">
          <h4><i class="fas fa-user-tie"></i> Available Faculty</h4>
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
        <button class="search-btn"><i class="fas fa-search"></i></button>
        <input type="text" id="subjects-search" placeholder="Search subjects...">
      </div>
      <button class="add-subject-main-btn button-gradient"><i class="fas fa-plus"></i> Add Subject</button>
    </div>
  </div>
  <div class="table-wrapper">
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
  <div class="box">
    <div class="section-header">
      <div class="header-title-section">
       <!-- <h2>Faculty Information</h2>-->
        <p class="section-subtitle"><i class="fas fa-user-tie" style="margin-right: 8px; font-size: 1em; color: var(--primary-600);"></i>This section contains all faculty members with their profiles and teaching assignments</p>
      </div>
      <div class="header-actions">
        <div class="search-wrapper">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <input id="faculty-search" placeholder="Search faculty...">
        </div>
        <button class="add-faculty-btn button-gradient"><i class="fas fa-plus"></i> Add Faculty</button>
      </div>
    </div>
    <div class="table-wrapper">
      <table class="faculties-table">
        <thead>
          <tr><th>Faculty</th><th>ID</th><th>Info</th><th>Subjects</th><th>Action</th></tr>
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
          <h4><i class="fas fa-book"></i> Assigned Subjects</h4>
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
  <div class="box">
    <div class="section-header">
      <div class="header-title-section">
       <!-- <h2>Students Information</h2> -->
        <p class="section-subtitle"><i class="fas fa-user-graduate" style="margin-right: 8px; font-size: 1em; color: var(--primary-600);"></i>This section shows all students with their basic details and records for easy management</p>
      </div>
      <div class="header-actions">
        <div class="search-wrapper">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <input id="student-search" placeholder="Search student...">
        </div>
        <button class="add-student-btn button-gradient"><i class="fas fa-plus"></i> Add Student</button>
      </div>
    </div>
    <div class="table-wrapper">
      <table class="students-table">
        <thead>
          <tr><th>ID</th><th>Name</th><th>Program</th><th>Action</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Student Modal binago-->
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
          <div id="student-number-error" style="color:red; font-size:0.9em; margin-top:2px;"></div>
        </div>
        <div>
          <label for="student-email">Email</label>
          <input type="email" id="student-email" placeholder="eg. student@email.com">
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
        <h4><i class="fas fa-graduation-cap"></i> Academic Details</h4>
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

        <div class="form-row" id="yearlevel-row" style="display: flex !important; flex-direction: row !important; gap: 20px !important; align-items: flex-start !important; width: 100% !important;">
          <div style="flex: 1 !important; display: flex !important; flex-direction: column !important;">
            <label for="student-yearlevel" style="margin-bottom: 5px !important; font-weight: 500 !important; color: #374151 !important;">Year Level</label>
            <select id="student-yearlevel" required style="width: 100% !important; padding: 8px 12px !important; border: 2px solid #d1d5db !important; border-radius: 6px !important; font-size: 14px !important;">
              <option value="" disabled selected>-- Select Year Level --</option>
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
            </select>
          </div>
          <div style="flex: 1 !important; display: flex !important; flex-direction: column !important;">
            <label for="student-section" style="margin-bottom: 5px !important; font-weight: 500 !important; color: #374151 !important;">Section</label>
            <input type="text" id="student-section" placeholder="eg. A, B, C" required style="width: 100% !important; padding: 8px 12px !important; border: 2px solid #d1d5db !important; border-radius: 6px !important; font-size: 14px !important;">
          </div>
        </div>
      </div>

      <div class="section-box">
        <h4><i class="fas fa-book"></i> Subjects</h4>
        <button type="button" id="add-subject-btn" class="button-gradient" style="margin-bottom: 10px;">
          <i class="fas fa-plus"></i> Add Subject
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
              <th>Year Level</th>
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
      <p class="section-subtitle"><i class="fas fa-list" style="margin-right: 8px; font-size: 1em; color: var(--primary-600);"></i>This section shows the list of criteria used for evaluating faculty performance</p>
    </div>
    <div class="header-actions">
      <button id="addCategoryBtn" class="add-program-btn button-gradient">
        <i class="fas fa-plus"></i> Add Category
      </button>
    </div>
  </div>


  <div class="criteria-layout" style="display:flex; gap:24px; align-items:flex-start;">
    <div class="categories-column" id="categoriesColumn" style="flex:2;"></div>

    <div class="summary-panel" style="flex:1;">
      <h4><i class="fas fa-clipboard-check"></i> Summary</h4>
      <p><strong>Categories:</strong> <span id="total-categories">0</span></p>
      <p><strong>Total Questions:</strong> <span id="total-questions">0</span></p>
      <div class="legend">
        <h5><i class="fas fa-list-ul"></i> LEGEND</h5>
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
  <div class="section-header">
    <div class="header-title-section">
      <p class="section-subtitle"><i class="fas fa-chart-bar" style="margin-right: 8px; font-size: 1em; color: var(--primary-600);"></i>This section shows faculty evaluation results and performance ratings</p>
    </div>
    <div class="header-actions">
      <div class="search-wrapper">
        <button class="search-btn"><i class="fas fa-search"></i></button>
        <input type="text" id="searchInput" placeholder="Search Faculty...">
      </div>
    </div>
  </div>
  <div class="table-wrapper">
    <table class="evaluation-table">
      <thead>
        <tr>
          <th>Faculty Name</th>
          <th>Average Score</th>
          <th>Rating</th>
          <th>Responses</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="evaluationTableBody">
        <!-- Dynamic content will be loaded here -->
      </tbody>
    </table>
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
          <i class="fas fa-user-tie"></i>
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
          <i class="fas fa-user-graduate"></i>
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
          <p class="student-subtitle">Assigned Subjects & Teachers</p>
        </div>
      </div>
      
      <div id="viewClassSubjectsList" class="subjects-container">
        <p style="text-align: center; color: #6b7280; padding: 40px;">Loading subjects...</p>
      </div>
    </div>
  </div>
</div>

<!-- Manage Periods Modal -->
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

<script src="FacultyAdmin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
