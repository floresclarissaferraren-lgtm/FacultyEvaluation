<?php
include_once 'session_config.php';
session_start();
include 'connect.php';

// Check if user is logged in as faculty
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faculty_id'])) {
    $faculty_id = $_POST['faculty_id'];
    
    try {
        // Fetch comprehensive faculty profile data
        $stmt = $conn->prepare("
            SELECT 
                f.id,
                f.faculty_id,
                f.firstname,
                f.lastname,
                f.suffix,
                f.email,
                f.phone,
                f.address,
                f.specialization,
                f.educational_background,
                f.employment_status,
                f.date_hired,
                p.program_name
            FROM add_faculties f
            LEFT JOIN add_programs p ON f.department = p.id
            WHERE f.faculty_id = ? OR f.id = ?
        ");
        
        $stmt->bind_param("si", $faculty_id, $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $faculty_data = $result->fetch_assoc();
            
            // Prepare response data
            $response = [
                'success' => true,
                'data' => [
                    'id' => $faculty_data['id'],
                    'faculty_id' => $faculty_data['faculty_id'],
                    'firstname' => $faculty_data['firstname'],
                    'lastname' => $faculty_data['lastname'],
                    'suffix' => $faculty_data['suffix'],
                    'email' => $faculty_data['email'],
                    'phone' => $faculty_data['phone'] ?: 'Not provided',
                    'address' => $faculty_data['address'] ?: 'Not provided',
                    'specialization' => $faculty_data['specialization'] ?: 'Not specified',
                    'educational_background' => $faculty_data['educational_background'] ?: 'Not provided',
                    'employment_status' => $faculty_data['employment_status'] ?: 'Active',
                    'date_hired' => $faculty_data['date_hired'] ? date('F d, Y', strtotime($faculty_data['date_hired'])) : 'Not specified',
                    'program_name' => $faculty_data['program_name'] ?: 'Not assigned'
                ]
            ];
            
            // Fetch subjects assigned to this faculty — check both tables
            $numeric_id = intval($faculty_data['id']);
            $subjects   = [];

            $subj_sql = "
                SELECT DISTINCT
                    s.subject_code,
                    s.subject_desc,
                    s.year_level,
                    s.semester,
                    p.program_code,
                    p.program_name
                FROM add_subjects s
                LEFT JOIN add_programs p ON s.program_id = p.id
                WHERE s.id IN (
                    SELECT subject_id FROM class_subjects   WHERE faculty_id = ?
                    UNION
                    SELECT subject_id FROM faculty_subjects WHERE faculty_id = ?
                )
                ORDER BY
                    COALESCE(p.program_code, p.program_name, 'ZZZ') ASC,
                    CASE
                        WHEN LOWER(s.year_level) LIKE '1%' THEN 1
                        WHEN LOWER(s.year_level) LIKE '2%' THEN 2
                        WHEN LOWER(s.year_level) LIKE '3%' THEN 3
                        WHEN LOWER(s.year_level) LIKE '4%' THEN 4
                        ELSE 99
                    END ASC,
                    s.semester ASC,
                    s.subject_code ASC
            ";
            $subj_stmt = $conn->prepare($subj_sql);
            if ($subj_stmt) {
                $subj_stmt->bind_param("ii", $numeric_id, $numeric_id);
                $subj_stmt->execute();
                $subj_result = $subj_stmt->get_result();
                while ($srow = $subj_result->fetch_assoc()) {
                    $subjects[] = [
                        'code' => $srow['subject_code'],
                        'name' => $srow['subject_desc'],
                        'year_level' => $srow['year_level'],
                        'semester' => $srow['semester'],
                        'program_code' => $srow['program_code'] ?: 'N/A',
                        'program_name' => $srow['program_name'] ?: 'Unassigned Program',
                    ];
                }
                $subj_stmt->close();
            }

            $response['data']['subjects'] = $subjects;
            header('Content-Type: application/json');
            echo json_encode($response);

        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Faculty not found']);
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
