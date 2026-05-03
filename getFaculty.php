<?php
header("Content-Type: application/json");
include 'connect.php';

try {

    $student_id = intval($_GET['student_id'] ?? 0);
    $year_level = trim($_GET['year_level'] ?? '');

    if ($student_id > 0) {
        $query = "
            SELECT 
                f.id,
                f.faculty_id,
                f.email,
                f.firstname,
                f.lastname,
                f.suffix,
                f.photo,
                GROUP_CONCAT(
                    CONCAT(s.subject_code, '|||', s.subject_desc, '|||', s.year_level, '|||', p.program_name)
                    ORDER BY s.subject_code
                    SEPARATOR '||;||'
                ) AS subjects_data
            FROM add_faculties f
            INNER JOIN faculty_subjects fs ON f.id = fs.faculty_id
            INNER JOIN student_subjects ss ON fs.subject_id = ss.subject_id AND ss.student_id = ?
            INNER JOIN add_subjects s ON fs.subject_id = s.id
            LEFT JOIN add_programs p ON s.program_id = p.id
            GROUP BY 
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo
            ORDER BY f.lastname, f.firstname
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } elseif ($year_level !== '') {
        $query = "
            SELECT 
                f.id,
                f.faculty_id,
                f.email,
                f.firstname,
                f.lastname,
                f.suffix,
                f.photo,
                GROUP_CONCAT(
                    CONCAT(s.subject_code, '|||', s.subject_desc, '|||', s.year_level, '|||', p.program_name)
                    ORDER BY s.subject_code
                    SEPARATOR '||;||'
                ) AS subjects_data
            FROM add_faculties f
            INNER JOIN faculty_subjects fs ON f.id = fs.faculty_id
            INNER JOIN add_subjects s ON fs.subject_id = s.id AND s.year_level = ?
            LEFT JOIN add_programs p ON s.program_id = p.id
            GROUP BY 
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo
            ORDER BY f.lastname, f.firstname
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $year_level);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $query = "
            SELECT 
                f.id,
                f.faculty_id,
                f.email,
                f.firstname,
                f.lastname,
                f.suffix,
                f.photo,
                GROUP_CONCAT(
                    CONCAT(s.subject_code, '|||', s.subject_desc, '|||', s.year_level, '|||', p.program_name)
                    ORDER BY s.subject_code
                    SEPARATOR '||;||'
                ) AS subjects_data
            FROM add_faculties f
            LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
            LEFT JOIN add_subjects s ON fs.subject_id = s.id
            LEFT JOIN add_programs p ON s.program_id = p.id
            GROUP BY 
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo
            ORDER BY f.lastname, f.firstname
        ";

        $result = $conn->query($query);
    }

    if (!$result) {
        echo json_encode([
            "error" => "Query failed: " . $conn->error
        ]);
        exit;
    }

    // Debug: Show the query and number of results
    error_log("Query: " . $query);
    error_log("Number of rows: " . $result->num_rows);

    $data = [];

    while ($row = $result->fetch_assoc()) {

        // Debug: Show the raw row data
        error_log("Raw row data: " . json_encode($row));
        
        $subjects = [];
        $subject_codes = [];

        if (!empty($row['subjects_data'])) {
            error_log("Subjects data found: " . $row['subjects_data']);
            $subject_entries = explode('||;||', $row['subjects_data']);
            error_log("Subject entries: " . json_encode($subject_entries));
            
            foreach ($subject_entries as $entry) {
                $parts = explode('|||', $entry);
                error_log("Entry parts: " . json_encode($parts));
                if (count($parts) >= 4) {
                    $subjects[] = [
                        'subject_code' => $parts[0],
                        'subject_desc' => $parts[1],
                        'year_level' => $parts[2],
                        'program_name' => $parts[3]
                    ];
                    $subject_codes[] = $parts[0]; // For simple display in table
                }
            }
        } else {
            error_log("No subjects data found for faculty: " . $row['faculty_id']);
        }

        $data[] = [
            "id" => $row["id"],
            "faculty_id" => $row["faculty_id"],
            "email" => $row["email"],
            "firstname" => $row["firstname"],
            "lastname" => $row["lastname"],
            "suffix" => $row["suffix"] ?? "",
            "photo" => $row["photo"] ?? "",
            "subjects" => $subjects,
            "subject_codes" => $subject_codes // For simple display
        ];
        
        // Debug: Show the final data structure for this faculty
        error_log("Final faculty data: " . json_encode(end($data)));
    }

    // Debug: Show the complete data structure
    error_log("Complete data being returned: " . json_encode($data));
    echo json_encode($data);

} catch (Exception $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>