<?php
require_once __DIR__ . '/security.php';
requireRole('admin', 'faculty');
header('Content-Type: application/json');
include __DIR__ . '/connect.php';
require_once __DIR__ . '/weighted_score_helper.php';
require_once __DIR__ . '/evaluation_schema.php';
require_once __DIR__ . '/evaluation_period_helper.php';

// Verify user is logged in as faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get JSON input
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input === false ? '[]' : $raw_input, true);
if (!is_array($data)) {
    $data = [];
}

$faculty_id     = isset($data['faculty_id']) ? trim((string) $data['faculty_id']) : '';
$program_filter = isset($data['program_filter']) ? trim((string) $data['program_filter']) : '';
$period_id      = isset($data['period_id']) ? intval($data['period_id']) : 0;
$subject_id     = isset($data['subject_id']) ? intval($data['subject_id']) : 0;
$class_id       = isset($data['class_id']) ? intval($data['class_id']) : null;
$classFilter    = $subject_id > 0 ? max(0, intval($class_id ?? 0)) : null;

if (empty($faculty_id) || $period_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID and evaluation period are required.']);
    exit;
}

// Resolve string faculty_id (FC-XXXX) to numeric id
if (!is_numeric($faculty_id)) {
    $stmt = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id = ?");
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row                = $result->fetch_assoc();
        $numeric_faculty_id = intval($row['id']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid faculty ID.']);
        exit;
    }
    $stmt->close();
} else {
    $numeric_faculty_id = intval($faculty_id);
}

if ($numeric_faculty_id != $_SESSION['id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$status_stmt = $conn->prepare("SELECT status FROM add_faculties WHERE id = ? LIMIT 1");
$status_stmt->bind_param("i", $numeric_faculty_id);
$status_stmt->execute();
$status_row = $status_stmt->get_result()->fetch_assoc();
$status_stmt->close();

if (strtolower((string)($status_row['status'] ?? 'active')) !== 'active') {
    echo json_encode([
        'success'  => false,
        'inactive' => true,
        'message'  => 'Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.'
    ]);
    exit;
}

blockFacultyResultsWhileEvaluationOngoing($conn);

// --- Total responses & date range ---
ensureEvaluationsSchema($conn);
$periodStmt = $conn->prepare("
    SELECT id, ay, semester, start_date, end_date
    FROM evaluation_periods
    WHERE id = ? AND is_active = 0
      AND EXISTS (
          SELECT 1
          FROM evaluations e
          WHERE e.faculty_id = ?
            AND (
                e.period_id = evaluation_periods.id
                OR (
                    e.period_id IS NULL
                    AND evaluation_periods.start_date IS NOT NULL
                    AND evaluation_periods.end_date IS NOT NULL
                    AND DATE(e.created_at) BETWEEN evaluation_periods.start_date AND evaluation_periods.end_date
                )
            )
      )
    LIMIT 1
");
$periodStmt->bind_param("ii", $period_id, $numeric_faculty_id);
$periodStmt->execute();
$period = $periodStmt->get_result()->fetch_assoc();
$periodStmt->close();
if (!$period) {
    echo json_encode(['success' => false, 'message' => 'Evaluation period not found.']);
    exit;
}
$periodStart = $period['start_date'];
$periodEnd = $period['end_date'];
$periodWhere = " AND (e.period_id = ? OR (
    e.period_id IS NULL
    AND DATE(e.created_at) BETWEEN ? AND ?
))";
$subjectWhere = $subject_id > 0 ? " AND e.subject_id = ? AND e.class_id = ?" : "";
if ($program_filter !== '') {
    // Filter by program name — join add_programs by code or numeric id
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               MIN(DATE(e.created_at)) AS date_from,
               MAX(DATE(e.created_at)) AS date_to
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$periodWhere}
          {$subjectWhere}
          AND EXISTS (
              SELECT 1
              FROM add_students s
              INNER JOIN add_programs p
                  ON TRIM(p.program_code) = TRIM(s.program)
                  OR CAST(p.id AS CHAR) = TRIM(s.program)
              WHERE s.id = e.student_id
                AND p.program_name = ?
          )
    ");
    if ($subject_id > 0) {
        $stmt->bind_param("iissiis", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $subject_id, $classFilter, $program_filter);
    } else {
        $stmt->bind_param("iisss", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $program_filter);
    }
} else {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               MIN(DATE(created_at)) AS date_from,
               MAX(DATE(created_at)) AS date_to
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$periodWhere}
          {$subjectWhere}
    ");
    if ($subject_id > 0) {
        $stmt->bind_param("iissii", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $subject_id, $classFilter);
    } else {
        $stmt->bind_param("iiss", $numeric_faculty_id, $period_id, $periodStart, $periodEnd);
    }
}
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_responses = intval($stats['total_responses'] ?? 0);

// --- Weighted overall score ---
if ($program_filter !== '') {
    $overall_rating = $total_responses > 0
        ? calcWeightedScoreByProgram($conn, $numeric_faculty_id, $program_filter, $subject_id ?: null, $classFilter, $period_id)
        : 0.00;
} else {
    $overall_rating = $total_responses > 0
        ? calcWeightedScore($conn, $numeric_faculty_id, $subject_id ?: null, $classFilter, $period_id)
        : 0.00;
}

// --- Period text ---
$periodDates = '';
if (!empty($period['start_date']) && !empty($period['end_date'])) {
    $periodDates = date('F j, Y', strtotime($period['start_date'])) . ' – ' . date('F j, Y', strtotime($period['end_date']));
}
$periodText = $periodDates !== '' ? $periodDates : 'Dates not specified';

// --- Feedback comments ---
if ($program_filter !== '') {
    $stmt_feedback = $conn->prepare("
        SELECT e.feedback
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$periodWhere}
          {$subjectWhere}
          AND EXISTS (
              SELECT 1
              FROM add_students s
              INNER JOIN add_programs p
                  ON TRIM(p.program_code) = TRIM(s.program)
                  OR CAST(p.id AS CHAR) = TRIM(s.program)
              WHERE s.id = e.student_id
                AND p.program_name = ?
          )
          AND e.feedback IS NOT NULL
          AND e.feedback != ''
        ORDER BY e.created_at DESC
    ");
    if ($subject_id > 0) {
        $stmt_feedback->bind_param("iissiis", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $subject_id, $classFilter, $program_filter);
    } else {
        $stmt_feedback->bind_param("iisss", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $program_filter);
    }
} else {
    $stmt_feedback = $conn->prepare("
        SELECT e.feedback
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$periodWhere}
          {$subjectWhere}
          AND feedback IS NOT NULL
          AND feedback != ''
        ORDER BY created_at DESC
    ");
    if ($subject_id > 0) {
        $stmt_feedback->bind_param("iissii", $numeric_faculty_id, $period_id, $periodStart, $periodEnd, $subject_id, $classFilter);
    } else {
        $stmt_feedback->bind_param("iiss", $numeric_faculty_id, $period_id, $periodStart, $periodEnd);
    }
}
$stmt_feedback->execute();
$feedback_result   = $stmt_feedback->get_result();
$feedback_comments = [];
while ($row = $feedback_result->fetch_assoc()) {
    $feedback_comments[] = $row['feedback'];
}
$stmt_feedback->close();

// --- Per-category stats with weights ---
$category_stats  = $program_filter !== ''
    ? getCategoryStatsByProgram($conn, $numeric_faculty_id, $program_filter, $subject_id ?: null, $classFilter, $period_id)
    : getCategoryStats($conn, $numeric_faculty_id, $subject_id ?: null, $classFilter, $period_id);
$category_totals = [];
foreach ($category_stats as $cat) {
    $category_totals[] = [
        'category_name'      => $cat['category_name'],
        'weight'             => $cat['weight'],
        'normalised_weight'  => $cat['normalised_weight'],
        'responses'          => $cat['responses'],
        'avg_rating'         => $cat['avg_rating'],
    ];
}

echo json_encode([
    'success'           => true,
    'overall_rating'    => number_format($overall_rating, 2),
    'percentage_score'  => number_format(($overall_rating / 5) * 100, 2),
    'total_responses'   => $total_responses,
    'evaluation_period' => $periodText,
    'period'            => [
        'id' => intval($period['id']),
        'academic_year' => $period['ay'],
        'semester' => $period['semester'],
        'start_date' => $period['start_date'],
        'end_date' => $period['end_date']
    ],
    'category_totals'   => $category_totals,
    'feedback_comments' => $feedback_comments,
    'feedback'          => empty($feedback_comments) ? 'No feedback available' : implode("\n\n", $feedback_comments),
]);

$conn->close();
?>
