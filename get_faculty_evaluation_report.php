<?php
session_start();
header('Content-Type: application/json');
include 'connect.php';

// Verify user is logged in as faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$faculty_id = isset($data['faculty_id']) ? trim($data['faculty_id']) : '';

// Validate required fields
if (empty($faculty_id)) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required.']);
    exit;
}

// Verify that the faculty_id matches the logged-in user's ID
// First check if it's a faculty_id format (FC-XXXX), then get the numeric ID
if (!is_numeric($faculty_id)) {
    // It's a faculty_id format, get the numeric ID from add_faculties table
    $stmt = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id = ?");
    $stmt->bind_param("s", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $numeric_faculty_id = $row['id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid faculty ID.']);
        exit;
    }
    $stmt->close();
} else {
    $numeric_faculty_id = $faculty_id;
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
        'success' => false,
        'inactive' => true,
        'message' => 'Your account has been set to inactive by an admin. You cannot generate or view result until your account is active again.'
    ]);
    exit;
}

// Get faculty evaluation statistics
$stmt = $conn->prepare("
    SELECT 
        COALESCE(AVG(overall_rating), 0) as overall_rating,
        COUNT(*) as total_responses
    FROM evaluations 
    WHERE faculty_id = ?
");
$stmt->bind_param("i", $numeric_faculty_id);
$stmt->execute();
$result = $stmt->get_result();

// Get feedback comments
$stmt_feedback = $conn->prepare("
    SELECT feedback 
    FROM evaluations 
    WHERE faculty_id = ? 
    AND feedback IS NOT NULL 
    AND feedback != ''
    ORDER BY created_at DESC
");
$stmt_feedback->bind_param("i", $numeric_faculty_id);
$stmt_feedback->execute();
$feedback_result = $stmt_feedback->get_result();

$feedback_comments = [];
if ($feedback_result->num_rows > 0) {
    while ($row = $feedback_result->fetch_assoc()) {
        $feedback_comments[] = $row['feedback'];
    }
}

if ($result->num_rows > 0) {
    $stats = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'overall_rating' => number_format($stats['overall_rating'], 2),
        'total_responses' => $stats['total_responses'],
        'feedback' => empty($feedback_comments) ? 'No feedback available' : implode("\n\n", $feedback_comments)
    ]);
} else {
    echo json_encode([
        'success' => true,
        'overall_rating' => '0.00',
        'total_responses' => 0,
        'feedback' => 'No feedback available'
    ]);
}

$stmt->close();
$stmt_feedback->close();
?>
