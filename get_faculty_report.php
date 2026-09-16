<?php
require_once 'security.php';
requireRole('admin', 'faculty');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if connect.php exists
if (!file_exists('connect.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection file not found'
    ]);
    exit;
}

include 'connect.php';
require_once 'weighted_score_helper.php';
require_once 'evaluation_schema.php';

function getRatingLabel($score) {
    $score = floatval($score);
    if ($score <= 0) return 'No Responses';
    if ($score >= 4.5) return 'Outstanding';
    if ($score >= 3.5) return 'Very Good';
    if ($score >= 2.5) return 'Good';
    if ($score >= 1.5) return 'Fair';
    return 'Poor';
}

function getRatingClass($score) {
    $score = floatval($score);
    if ($score <= 0) return 'no-responses';
    if ($score >= 4.5) return 'outstanding';
    if ($score >= 3.5) return 'very-good';
    if ($score >= 2.5) return 'good';
    if ($score >= 1.5) return 'fair';
    return 'poor';
}

if (!isset($_GET['faculty_id']) || empty($_GET['faculty_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faculty ID is required'
    ]);
    exit;
}

$faculty_id = $_GET['faculty_id'];
$subject_id = intval($_GET['subject_id'] ?? 0);
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$classFilter = $subject_id > 0 ? max(0, intval($class_id ?? 0)) : null;
$period_id = intval($_GET['period_id'] ?? 0);

try {
    ensureEvaluationsSchema($conn);

    // Get faculty basic information
    $faculty_query = "SELECT id, CONCAT(firstname, ' ', lastname, ' ', suffix) as name, faculty_id 
                   FROM add_faculties WHERE id = ?";
    $faculty_stmt = $conn->prepare($faculty_query);
    $faculty_stmt->bind_param("i", $faculty_id);
    $faculty_stmt->execute();
    $faculty_result = $faculty_stmt->get_result();
    
    if ($faculty_result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Faculty not found'
        ]);
        exit;
    }
    
    $faculty = $faculty_result->fetch_assoc();
    
    // Get overall rating, total responses, and period range from evaluations
    $subjectWhere = $subject_id > 0 ? " AND e.subject_id = ? AND e.class_id = ?" : "";
    $periodWhere = $period_id > 0 ? " AND e.period_id = ?" : "";
    $overall_query = "SELECT 
                        COUNT(e.id) as total_responses,
                        MIN(DATE(e.created_at)) as date_from,
                        MAX(DATE(e.created_at)) as date_to,
                        MAX(ep.ay) AS ay,
                        MAX(ep.semester) AS semester
                    FROM evaluations e
                    LEFT JOIN evaluation_periods ep ON ep.id = e.period_id
                    WHERE e.faculty_id = ? {$subjectWhere} {$periodWhere}";
    $overall_stmt = $conn->prepare($overall_query);
    if ($subject_id > 0 && $period_id > 0) {
        $overall_stmt->bind_param("iiii", $faculty_id, $subject_id, $classFilter, $period_id);
    } elseif ($subject_id > 0) {
        $overall_stmt->bind_param("iii", $faculty_id, $subject_id, $classFilter);
    } elseif ($period_id > 0) {
        $overall_stmt->bind_param("ii", $faculty_id, $period_id);
    } else {
        $overall_stmt->bind_param("i", $faculty_id);
    }
    $overall_stmt->execute();
    $overall_result = $overall_stmt->get_result();
    $overall_data   = $overall_result->fetch_assoc();

    // Weighted overall score (real-time)
    $totalResponses = intval($overall_data['total_responses'] ?? 0);
    $overallScore   = $totalResponses > 0 ? calcWeightedScore($conn, intval($faculty_id), $subject_id ?: null, $classFilter, $period_id ?: null) : 0.00;
    
    // Get all feedback comments for this faculty.
    $feedback_query = "SELECT e.feedback
                    FROM evaluations e
                    WHERE e.faculty_id = ?
                    {$subjectWhere}
                    {$periodWhere}
                    AND e.feedback IS NOT NULL
                    AND TRIM(e.feedback) != ''
                    ORDER BY e.updated_at DESC, e.created_at DESC";
    $feedback_stmt = $conn->prepare($feedback_query);
    if ($subject_id > 0 && $period_id > 0) {
        $feedback_stmt->bind_param("iiii", $faculty_id, $subject_id, $classFilter, $period_id);
    } elseif ($subject_id > 0) {
        $feedback_stmt->bind_param("iii", $faculty_id, $subject_id, $classFilter);
    } elseif ($period_id > 0) {
        $feedback_stmt->bind_param("ii", $faculty_id, $period_id);
    } else {
        $feedback_stmt->bind_param("i", $faculty_id);
    }
    $feedback_stmt->execute();
    $feedback_result = $feedback_stmt->get_result();

    $feedback_comments = [];
    while ($feedback_row = $feedback_result->fetch_assoc()) {
        $feedback_comments[] = trim($feedback_row['feedback']);
    }
    $feedback_stmt->close();

    $all_feedback = empty($feedback_comments) ? 'No feedback available' : implode("\n\n", $feedback_comments);

    // Get evaluation details by category with weights (real-time).
    $category_stats  = getCategoryStats($conn, intval($faculty_id), $subject_id ?: null, $classFilter, $period_id ?: null);
    $evaluation_details = [];
    $category_totals    = [];
    foreach ($category_stats as $cat) {
        $avg   = floatval($cat['avg_rating']);
        $label = getRatingLabel($avg);
        $class = getRatingClass($avg);
        $evaluation_details[] = [
            'category'     => $cat['category_name'],
            'average_score'=> $cat['avg_rating'],
            'rating'       => $label,
            'rating_class' => $class,
            'responses'    => $cat['responses'],
            'weight'       => $cat['weight'],
            'normalised_weight' => $cat['normalised_weight'],
            'all_feedback' => $all_feedback,
        ];
        $category_totals[] = [
            'category_name'     => $cat['category_name'],
            'avg_rating'        => $cat['avg_rating'],
            'responses'         => $cat['responses'],
            'rating'            => $label,
            'rating_class'      => $class,
            'weight'            => $cat['weight'],
            'normalised_weight' => $cat['normalised_weight'],
        ];
    }
    
    $periodText = 'All evaluation periods';
    if ($period_id > 0 && !empty($overall_data['ay']) && !empty($overall_data['semester'])) {
        $periodText = $overall_data['ay'] . ' - ' . $overall_data['semester'];
    } elseif (!empty($overall_data['date_from']) && !empty($overall_data['date_to'])) {
        $start = date('F j, Y', strtotime($overall_data['date_from']));
        $end = date('F j, Y', strtotime($overall_data['date_to']));
        $periodText = $start === $end ? $start : "$start - $end";
    }

    $overallStatus      = getRatingLabel($overallScore);
    $overallStatusClass = getRatingClass($overallScore);

    $subjectLabel = '';
    $classLabel = '';
    if ($subject_id > 0) {
        $subjectStmt = $conn->prepare("
            SELECT s.subject_code, s.subject_desc, ac.year_level, ac.block
            FROM add_subjects s
            LEFT JOIN add_classes ac ON ac.id = ?
            WHERE s.id = ?
            LIMIT 1
        ");
        if ($subjectStmt) {
            $subjectStmt->bind_param("ii", $classFilter, $subject_id);
            $subjectStmt->execute();
            $subjectRow = $subjectStmt->get_result()->fetch_assoc();
            $subjectStmt->close();
            if ($subjectRow) {
                $subjectLabel = trim(($subjectRow['subject_code'] ?? '') . (($subjectRow['subject_desc'] ?? '') !== '' ? ' - ' . $subjectRow['subject_desc'] : ''));
                $classLabel = trim(($subjectRow['year_level'] ?? '') . (($subjectRow['block'] ?? '') !== '' ? ' / ' . $subjectRow['block'] : ''));
            }
        }
    }

    // Prepare response data
    $response_data = [
        'id'                  => $faculty['id'],
        'name'                => $faculty['name'],
        'faculty_id'          => $faculty['faculty_id'],
        'subject_id'          => $subject_id,
        'class_id'            => $classFilter ?? 0,
        'subject_label'       => $subjectLabel,
        'class_label'         => $classLabel,
        'overall_rating'      => $overallScore > 0 ? number_format($overallScore, 2) : '0.00',
        'percentage_score'    => $overallScore > 0 ? number_format(($overallScore / 5) * 100, 2) : '0.00',
        'total_responses'     => $totalResponses,
        'overall_status'      => $overallStatus,
        'overall_status_class'=> $overallStatusClass,
        'evaluation_period'   => $periodText,
        'evaluation_details'  => $evaluation_details,
        'category_totals'     => $category_totals,
        'all_feedback'        => $all_feedback,
        'feedback_comments'   => $feedback_comments
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $response_data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
