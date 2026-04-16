<?php
header("Content-Type: application/json");
include 'connect.php';

try {
    // Get faculty with their assigned subjects
    $query = "SELECT 
        f.id,
        f.faculty_id,
        f.firstname,
        f.lastname,
        f.suffix,
        f.email,
        f.photo,
        f.program,
        f.yearlevel,
        f.password,
        GROUP_CONCAT(
            CONCAT(s.subject_code, ' - ', s.description, ' (', s.year_level, ')')
            SEPARATOR '|'
        ) as subjects
    FROM add_faculties f
    LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
    LEFT JOIN subjects s ON fs.subject_id = s.id
    GROUP BY f.id, f.faculty_id, f.firstname, f.lastname, f.suffix, f.email, f.photo, f.program, f.yearlevel, f.password
    ORDER BY f.lastname, f.firstname";
    
    $result = $conn->query($query);
    
    $data = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Parse subjects into array
            $subjects = [];
            if (!empty($row['subjects'])) {
                $subjectList = explode('|', $row['subjects']);
                foreach ($subjectList as $subject) {
                    if (!empty(trim($subject))) {
                        $subjects[] = trim($subject);
                    }
                }
            }
            
            $data[] = [
                'id' => $row['id'],
                'faculty_id' => $row['faculty_id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'suffix' => $row['suffix'],
                'email' => $row['email'],
                'photo' => $row['photo'],
                'program' => $row['program'],
                'yearlevel' => $row['yearlevel'],
                'subjects' => $subjects
            ];
        }
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error fetching faculty: ' . $e->getMessage()
    ]);
}

$conn->close();
?>