<?php
/**
 * get_faculty_per_subject_report.php
 *
 * Returns per-subject evaluation data for the logged-in faculty member,
 * grouped by program/department.  Also returns a combined overall rating
 * across every subject.
 *
 * Method : POST
 * Body   : { "faculty_id": "FC-XXXX" | numeric }
 * Returns: JSON
 */
include_once 'session_config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';
require_once 'evaluation_period_helper.php';

/* ── helpers ───────────────────────────────────────────────────────────── */
function ratingLabel(float $score): string {
    if ($score <= 0)   return 'No Responses';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

function ratingClass(float $score): string {
    if ($score <= 0)   return 'no-responses';
    if ($score >= 4.5) return 'outstanding';
    if ($score >= 3.5) return 'very-good';
    if ($score >= 2.5) return 'good';
    if ($score >= 1.5) return 'fair';
    return 'poor';
}

/* ── parse input ───────────────────────────────────────────────────────── */
$data      = json_decode(file_get_contents('php://input'), true);
$raw_id    = trim($data['faculty_id'] ?? '');

if ($raw_id === '') {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required.']);
    exit;
}

// Resolve string faculty_id (FC-XXXX) → numeric id
if (!is_numeric($raw_id)) {
    $stmt = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id = ?");
    $stmt->bind_param("s", $raw_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Invalid faculty ID.']);
        exit;
    }
    $numeric_id = intval($row['id']);
} else {
    $numeric_id = intval($raw_id);
}

// Security: faculty can only view their own data
if ($numeric_id !== intval($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Check faculty active status
$sStmt = $conn->prepare("SELECT status FROM add_faculties WHERE id = ? LIMIT 1");
$sStmt->bind_param("i", $numeric_id);
$sStmt->execute();
$sRow = $sStmt->get_result()->fetch_assoc();
$sStmt->close();

if (strtolower((string)($sRow['status'] ?? 'active')) !== 'active') {
    echo json_encode([
        'success'  => false,
        'inactive' => true,
        'message'  => 'Your account has been set to inactive by an admin.'
    ]);
    exit;
}

blockFacultyResultsWhileEvaluationOngoing($conn);

ensureEvaluationsSchema($conn);

/* ── fetch every subject+class the faculty was evaluated for ───────────── */
/*
 * We query the evaluations table directly so we only surface subjects that
 * actually have evaluation data.  The subject's program drives the department
 * grouping.  We also look up class info (year_level, block) from add_classes.
 */
$subjectSql = "
    SELECT
        s.id                                                 AS subject_id,
        s.subject_code,
        s.subject_desc,
        s.year_level                                         AS subject_year,
        s.semester,
        p.id                                                 AS program_id,
        p.program_code,
        p.program_name,
        e.class_id,
        ac.year_level                                        AS class_year,
        ac.block                                             AS class_block,
        COUNT(DISTINCT e.id)                                 AS eval_count,
        MIN(DATE(e.created_at))                              AS date_from,
        MAX(DATE(e.created_at))                              AS date_to
    FROM evaluations e
    INNER JOIN add_subjects s  ON s.id  = e.subject_id
    INNER JOIN add_programs p  ON p.id  = s.program_id
    LEFT  JOIN add_classes  ac ON ac.id = e.class_id
    WHERE e.faculty_id = ?
      AND e.subject_id > 0
    GROUP BY
        s.id,
        s.subject_code,
        s.subject_desc,
        s.year_level,
        s.semester,
        p.id,
        p.program_code,
        p.program_name,
        e.class_id,
        ac.year_level,
        ac.block
    ORDER BY p.program_name ASC, s.subject_code ASC
";

$subStmt = $conn->prepare($subjectSql);
$subStmt->bind_param("i", $numeric_id);
$subStmt->execute();
$subjectRows = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subStmt->close();

/* ── build per-department, per-subject structure ────────────────────────── */
$departments = [];   // keyed by program_id

foreach ($subjectRows as $row) {
    $prog_id   = intval($row['program_id']);
    $subj_id   = intval($row['subject_id']);
    $class_id  = intval($row['class_id']);
    $eval_cnt  = intval($row['eval_count']);

    // Initialise department bucket
    if (!isset($departments[$prog_id])) {
        $departments[$prog_id] = [
            'program_id'   => $prog_id,
            'program_code' => $row['program_code'],
            'program_name' => $row['program_name'],
            'subjects'     => [],
        ];
    }

    // Weighted average for this subject+class
    $avg = $eval_cnt > 0
        ? calcWeightedScore($conn, $numeric_id, $subj_id, $class_id)
        : 0.00;

    // Category breakdown for this subject+class
    $cats = getCategoryStats($conn, $numeric_id, $subj_id, $class_id);
    $category_totals = [];
    foreach ($cats as $cat) {
        $category_totals[] = [
            'category_name'     => $cat['category_name'],
            'avg_rating'        => $cat['avg_rating'],
            'responses'         => $cat['responses'],
            'weight'            => $cat['weight'],
            'normalised_weight' => $cat['normalised_weight'],
        ];
    }

    // Student comments for this subject+class
    $fbStmt = $conn->prepare("
        SELECT feedback
        FROM evaluations
        WHERE faculty_id   = ?
          AND subject_id   = ?
          AND class_id     = ?
          AND feedback IS NOT NULL
          AND TRIM(feedback) != ''
        ORDER BY created_at DESC
    ");
    $fbStmt->bind_param("iii", $numeric_id, $subj_id, $class_id);
    $fbStmt->execute();
    $fbRows   = $fbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $fbStmt->close();
    $comments = array_map(fn($r) => trim($r['feedback']), $fbRows);

    // Period text
    $period = 'N/A';
    if (!empty($row['date_from']) && !empty($row['date_to'])) {
        $start  = date('M j, Y', strtotime($row['date_from']));
        $end    = date('M j, Y', strtotime($row['date_to']));
        $period = $start === $end ? $start : "$start – $end";
    }

    // Class label
    $classLabel = '';
    if (!empty($row['class_year'])) {
        $classLabel = $row['class_year'];
        if (!empty($row['class_block'])) {
            $classLabel .= ' / ' . $row['class_block'];
        }
    }

    $departments[$prog_id]['subjects'][] = [
        'subject_id'      => $subj_id,
        'subject_code'    => $row['subject_code'],
        'subject_desc'    => $row['subject_desc'],
        'year_level'      => $row['subject_year'],
        'semester'        => $row['semester'],
        'class_id'        => $class_id,
        'class_label'     => $classLabel,
        'eval_count'      => $eval_cnt,
        'avg_rating'      => number_format($avg, 2),
        'percentage'      => number_format(($avg / 5) * 100, 2),
        'rating_label'    => ratingLabel($avg),
        'rating_class'    => ratingClass($avg),
        'period'          => $period,
        'category_totals' => $category_totals,
        'comments'        => $comments,
    ];
}

// Re-index as plain array for JSON
$departments = array_values($departments);

/* ── overall combined rating (all subjects, all classes) ────────────────── */
$totalStmt = $conn->prepare("
    SELECT COUNT(id) AS total, MIN(DATE(created_at)) AS date_from, MAX(DATE(created_at)) AS date_to
    FROM evaluations
    WHERE faculty_id = ?
");
$totalStmt->bind_param("i", $numeric_id);
$totalStmt->execute();
$totalRow = $totalStmt->get_result()->fetch_assoc();
$totalStmt->close();

$grandTotal   = intval($totalRow['total'] ?? 0);
$overallScore = $grandTotal > 0 ? calcWeightedScore($conn, $numeric_id) : 0.00;

$overallPeriod = 'All evaluation periods';
if (!empty($totalRow['date_from']) && !empty($totalRow['date_to'])) {
    $s = date('M j, Y', strtotime($totalRow['date_from']));
    $e = date('M j, Y', strtotime($totalRow['date_to']));
    $overallPeriod = $s === $e ? $s : "$s – $e";
}

// Overall category stats (global)
$overallCats = getCategoryStats($conn, $numeric_id);
$overallCatTotals = [];
foreach ($overallCats as $cat) {
    $overallCatTotals[] = [
        'category_name'     => $cat['category_name'],
        'avg_rating'        => $cat['avg_rating'],
        'responses'         => $cat['responses'],
        'weight'            => $cat['weight'],
        'normalised_weight' => $cat['normalised_weight'],
    ];
}

// Overall feedback (all subjects)
$allFbStmt = $conn->prepare("
    SELECT feedback FROM evaluations
    WHERE faculty_id = ?
      AND feedback IS NOT NULL
      AND TRIM(feedback) != ''
    ORDER BY created_at DESC
");
$allFbStmt->bind_param("i", $numeric_id);
$allFbStmt->execute();
$allFbRows     = $allFbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allFbStmt->close();
$allComments   = array_map(fn($r) => trim($r['feedback']), $allFbRows);

$conn->close();

echo json_encode([
    'success'      => true,
    'departments'  => $departments,
    'overall'      => [
        'avg_rating'      => number_format($overallScore, 2),
        'percentage'      => number_format(($overallScore / 5) * 100, 2),
        'rating_label'    => ratingLabel($overallScore),
        'rating_class'    => ratingClass($overallScore),
        'eval_count'      => $grandTotal,
        'period'          => $overallPeriod,
        'category_totals' => $overallCatTotals,
        'comments'        => $allComments,
    ],
]);
?>
