<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
require_once __DIR__ . "/../shared/security.php";
requireDashboardRole("faculty");

include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";
require_once __DIR__ . "/../shared/department_schema.php";

ensureColumnExists(
    $conn,
    "add_faculties",
    "department",
    "VARCHAR(120) DEFAULT NULL",
    "suffix",
);
ensureColumnExists(
    $conn,
    "add_faculties",
    "is_department_head",
    "TINYINT(1) NOT NULL DEFAULT 0",
    "department",
);
ensureDepartmentSchema($conn);

$stmt = $conn->prepare("
    SELECT d.id, d.department_name, f.firstname, f.lastname, f.suffix, f.email
    FROM add_faculties f
    INNER JOIN add_departments d ON d.department_name = f.department
    WHERE f.id = ?
      AND COALESCE(f.is_department_head, 0) = 1
      AND LOWER(TRIM(COALESCE(f.status, 'active'))) = 'active'
    LIMIT 2
");
if (!$stmt) {
    http_response_code(500);
    exit("Unable to verify department head assignment.");
}
$facultyId = (int) ($_SESSION["id"] ?? 0);
$stmt->bind_param("i", $facultyId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("Unable to verify department head assignment.");
}
$result = $stmt->get_result();
$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$stmt->close();

if (count($departments) !== 1) {
    http_response_code(count($departments) > 1 ? 409 : 403);
    exit(
        count($departments) > 1
            ? "The department assignment is ambiguous. Contact an administrator."
            : "Department Head access is not assigned to this account."
    );
}

$departmentName = (string) $departments[0]["department_name"];
$departmentHeadName = trim(
    implode(
        " ",
        array_filter([
            $departments[0]["firstname"] ?? "",
            $departments[0]["lastname"] ?? "",
            $departments[0]["suffix"] ?? "",
        ]),
    ),
);
$departmentHeadName = $departmentHeadName !== ""
    ? $departmentHeadName
    : "Department Head";
$nameParts = preg_split("/\\s+/", $departmentHeadName);
$departmentHeadInitials = strtoupper(
    substr($nameParts[0] ?? "D", 0, 1) .
        substr($nameParts[1] ?? "H", 0, 1),
);
$departmentHeadEmail = (string) ($departments[0]["email"] ?? "");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Department Head Dashboard</title>
  <link rel="stylesheet" href="FacultyInstructor.css?v=<?= time() ?>">
  <link rel="stylesheet" href="DepartmentHead.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
</head>
<body>
  <div class="navbar">
    <div class="brand">
      <img src="../assets/images/schoollogo.png" alt="Logo" class="navbar-logo">
      <div class="brand-text">
        <span class="logo-text main-title">Faculty Evaluation System</span>
        <span class="logo-subtitle">INSTRUCTOR PORTAL</span>
      </div>
    </div>
    <div class="user-menu">
      <div class="instructor-box" onclick="toggleDropdown()">
        <span class="instructor-account-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <circle cx="12" cy="7" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0v1H4z" />
          </svg>
        </span>
        <i class="ph ph-caret-down" aria-hidden="true"></i>
      </div>
      <div class="dropdown-menu" id="dropdownMenu">
        <div class="dropdown-user-info">
          <div class="dropdown-avatar">
            <span class="dropdown-avatar-initials"><?= htmlspecialchars($departmentHeadInitials, ENT_QUOTES, "UTF-8") ?></span>
          </div>
          <div class="dropdown-user-details">
            <div class="dropdown-user-name"><?= htmlspecialchars($departmentHeadName, ENT_QUOTES, "UTF-8") ?></div>
            <div class="dropdown-user-email"><?= htmlspecialchars($departmentHeadEmail, ENT_QUOTES, "UTF-8") ?></div>
          </div>
        </div>
        <div class="role-switch">
          <div class="role-switch-heading">
            <span>Switch role</span>
            <span class="role-switch-count">2 roles</span>
          </div>
          <a href="FacultyInstructor.php" class="role-switch-option">
            <span class="role-option-icon"><i class="ph ph-user"></i></span>
            <span class="role-option-copy">
              <strong>Faculty</strong>
              <small>Teaching workspace</small>
            </span>
          </a>
          <a href="DepartmentHead.php" class="role-switch-option is-active" aria-current="page">
            <span class="role-option-icon"><i class="ph ph-buildings"></i></span>
            <span class="role-option-copy">
              <strong>Department Head</strong>
              <small>Management workspace</small>
            </span>
            <i class="ph ph-check-circle role-option-selected" aria-label="Current role"></i>
          </a>
        </div>
        <hr class="dropdown-divider">
        <a href="FacultyInstructor.php?open=profile">
          <i class="ph ph-user"></i> Profile
        </a>
        <a href="FacultyInstructor.php?open=password">
          <i class="ph ph-key"></i> Change Password
        </a>
        <a href="FacultyInstructor.php?open=history">
          <i class="ph ph-clock-counter-clockwise"></i> Evaluation History
        </a>
        <hr class="dropdown-divider">
        <a href="../shared/logout.php" class="logout-link" onclick="return confirm('Are you sure you want to logout?')">
          <i class="ph ph-sign-out" aria-hidden="true"></i>
          Logout
        </a>
      </div>
    </div>
  </div>
  <main class="department-head-page">
    <section class="page-heading">
      <p class="eyebrow">Department Head Portal</p>
      <h1><?= htmlspecialchars($departmentName, ENT_QUOTES, "UTF-8") ?></h1>
      <p>View department evaluation results.</p>
    </section>
    <section class="report-panel" aria-labelledby="report-title">
      <div class="report-toolbar">
        <div>
          <h2 id="report-title">Department Evaluation Results</h2>
        </div>
        <div class="report-toolbar-filters" aria-label="Filter department results">
          <select id="academic-year-filter" aria-label="Academic year" disabled><option value="">Loading...</option></select>
          <select id="semester-filter" aria-label="Semester" disabled><option value="">Select academic year first</option></select>
        </div>
      </div>
      <p id="report-status" class="report-status" role="status" aria-live="polite">Loading department results...</p>
      <div class="report-table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Faculty Name</th>
              <th scope="col">Academic Year / Semester</th>
              <th scope="col">Overall Rating</th>
              <th scope="col">Responses</th>
              <th scope="col">Status</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody id="faculty-results">
            <tr><td colspan="6" class="empty-row">Loading results...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
  <div id="faculty-report-modal" class="department-report-modal" hidden>
    <div class="department-report-dialog" role="dialog" aria-modal="true" aria-labelledby="faculty-report-modal-title">
      <div id="faculty-report-modal-content"></div>
    </div>
  </div>
  <script src="DepartmentHead.js" defer></script>
</body>
</html>
