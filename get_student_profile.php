<?php
require_once 'security.php';
requireRole('student');
include_once 'session_config.php';
session_start();
include 'connect.php';

// Check if user is logged in as student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_SESSION['id'] ?? 0);
    
    try {
        // Fetch comprehensive student profile data
        $stmt = $conn->prepare("
            SELECT 
                s.id,
                s.student_number,
                s.firstname,
                s.lastname,
                s.email,
                s.phone,
                s.address,
                s.yearlevel,
                s.program,
                s.section,
                s.student_type,
                s.status,
                s.enrollment_date,
                p.program_name
            FROM add_students s
            LEFT JOIN add_programs p ON s.program = p.id
            WHERE s.id = ? OR s.student_number = ?
        ");
        
        $stmt->bind_param("is", $student_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student_data = $result->fetch_assoc();
            
            // Prepare response data
            $response = [
                'success' => true,
                'data' => [
                    'id' => $student_data['id'],
                    'student_number' => $student_data['student_number'],
                    'firstname' => $student_data['firstname'],
                    'lastname' => $student_data['lastname'],
                    'email' => $student_data['email'] ?: 'student@example.com',
                    'phone' => $student_data['phone'] ?: 'Not provided',
                    'address' => $student_data['address'] ?: 'Not provided',
                    'yearlevel' => $student_data['yearlevel'] ?: 'Not specified',
                    'program' => $student_data['program'],
                    'section' => $student_data['section'] ?: 'Not assigned',
                    'student_type' => $student_data['student_type'] ?: 'Regular',
                    'status' => $student_data['status'] ?: 'Active',
                    'enrollment_date' => $student_data['enrollment_date'] ? date('F d, Y', strtotime($student_data['enrollment_date'])) : 'Not specified',
                    'program_name' => $student_data['program_name'] ?: 'Not assigned'
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Student not found']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

$conn->close();
?>
