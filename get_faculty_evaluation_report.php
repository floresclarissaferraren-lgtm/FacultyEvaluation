<?php
include_once 'session_config.php';
session_start();
header('Content-Type: application/json');
include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';
require_once 'evaluation_period_helper.php';

// Verify user is logged in as faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get JSON input
$data           = json_decode(file_get_contents('php://input'), true);
$faculty_id     = isset($data['faculty_id']) ? trim($data['faculty_id']) : '';
$program_filter = isset($data['program_filter']) ? trim($data['program_filter']) : '';
$subject_id     = intval($data['subject_id'] ?? 0);
$class_id       = isset($data['class_id']) ? intval($data['class_id']) : null;
$classFilter    = $subject_id > 0 ? max(0, intval($class_id ?? 0)) : null;

if (empty($faculty_id)) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required.']);
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
$subjectWhere = $subject_id > 0 ? " AND e.subject_id = ? AND e.class_id = ?" : "";
if ($program_filter !== '') {
    // Filter by program name — join add_programs by code or numeric id
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               MIN(DATE(e.created_at)) AS date_from,
               MAX(DATE(e.created_at)) AS date_to
        FROM evaluations e
        INNER JOIN add_students s ON s.id = e.student_id
        LEFT  JOIN add_programs p ON TRIM(p.program_code) = TRIM(s.program)
                                  OR CAST(p.id AS CHAR)   = TRIM(s.program)
        WHERE e.faculty_id = ?
          {$subjectWhere}
          AND p.program_name = ?
    ");
    if ($subject_id > 0) {
        $stmt->bind_param("iiis", $numeric_faculty_id, $subject_id, $classFilter, $program_filter);
    } else {
        $stmt->bind_param("is", $numeric_faculty_id, $program_filter);
    }
} else {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_responses,
               MIN(DATE(created_at)) AS date_from,
               MAX(DATE(created_at)) AS date_to
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$subjectWhere}
    ");
    if ($subject_id > 0) {
        $stmt->bind_param("iii", $numeric_faculty_id, $subject_id, $classFilter);
    } else {
        $stmt->bind_param("i", $numeric_faculty_id);
    }
}
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_responses = intval($stats['total_responses'] ?? 0);

// --- Weighted overall score ---
if ($program_filter !== '') {
    $overall_rating = $total_responses > 0
        ? calcWeightedScoreByProgram($conn, $numeric_faculty_id, $program_filter, $subject_id ?: null, $classFilter)
        : 0.00;
} else {
    $overall_rating = $total_responses > 0
        ? calcWeightedScore($conn, $numeric_faculty_id, $subject_id ?: null, $classFilter)
        : 0.00;
}

// --- Period text ---
$periodText = 'All evaluation periods';
if (!empty($stats['date_from']) && !empty($stats['date_to'])) {
    $start      = date('F j, Y', strtotime($stats['date_from']));
    $end        = date('F j, Y', strtotime($stats['date_to']));
    $periodText = $start === $end ? $start : "$start \u{2013} $end";
}

// --- Feedback comments ---
if ($program_filter !== '') {
    $stmt_feedback = $conn->prepare("
        SELECT e.feedback
        FROM evaluations e
        INNER JOIN add_students s ON s.id = e.student_id
        LEFT  JOIN add_programs p ON TRIM(p.program_code) = TRIM(s.program)
                                  OR CAST(p.id AS CHAR)   = TRIM(s.program)
        WHERE e.faculty_id = ?
          {$subjectWhere}
          AND p.program_name = ?
          AND e.feedback IS NOT NULL
          AND e.feedback != ''
        ORDER BY e.created_at DESC
    ");
    if ($subject_id > 0) {
        $stmt_feedback->bind_param("iiis", $numeric_faculty_id, $subject_id, $classFilter, $program_filter);
    } else {
        $stmt_feedback->bind_param("is", $numeric_faculty_id, $program_filter);
    }
} else {
    $stmt_feedback = $conn->prepare("
        SELECT e.feedback
        FROM evaluations e
        WHERE e.faculty_id = ?
          {$subjectWhere}
          AND feedback IS NOT NULL
          AND feedback != ''
        ORDER BY created_at DESC
    ");
    if ($subject_id > 0) {
        $stmt_feedback->bind_param("iii", $numeric_faculty_id, $subject_id, $classFilter);
    } else {
        $stmt_feedback->bind_param("i", $numeric_faculty_id);
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
    ? getCategoryStatsByProgram($conn, $numeric_faculty_id, $program_filter, $subject_id ?: null, $classFilter)
    : getCategoryStats($conn, $numeric_faculty_id, $subject_id ?: null, $classFilter);
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
    'category_totals'   => $category_totals,
    'feedback_comments' => $feedback_comments,
    'feedback'          => empty($feedback_comments) ? 'No feedback available' : implode("\n\n", $feedback_comments),
]);

$conn->close();
?>
