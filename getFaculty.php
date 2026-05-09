<?php
header("Content-Type: application/json");
include 'connect.php';

try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) NOT NULL,
            faculty_id INT(11) NOT NULL,
            overall_rating DECIMAL(4,2) NOT NULL DEFAULT 0.00,
            feedback TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_student_faculty (student_id, faculty_id),
            KEY idx_eval_faculty (faculty_id),
            KEY idx_eval_student (student_id)
        )
    ");

    $student_id = intval($_GET['student_id'] ?? 0);
    $year_level = trim($_GET['year_level'] ?? '');

    $student_year_raw = '';
    $student_year_formatted = '';

    if ($student_id > 0) {
        $student_meta_stmt = $conn->prepare("SELECT yearlevel FROM add_students WHERE id = ? LIMIT 1");
        $student_meta_stmt->bind_param("i", $student_id);
        $student_meta_stmt->execute();
        $student_meta_result = $student_meta_stmt->get_result();
        if ($student_meta = $student_meta_result->fetch_assoc()) {
            $student_year_raw = trim((string)($student_meta['yearlevel'] ?? ''));
            $student_year_formatted = $student_year_raw;
            if (ctype_digit($student_year_raw)) {
                $y = intval($student_year_raw);
                $suffix = $y === 1 ? 'st' : ($y === 2 ? 'nd' : ($y === 3 ? 'rd' : 'th'));
                $student_year_formatted = $y . $suffix . ' Year';
            }
        }
        $student_meta_stmt->close();

        // Primary source: exact class assignment by student's program + year level + section.
        $query = "
            SELECT 
                f.id,
                f.faculty_id,
                f.email,
                f.firstname,
                f.lastname,
                f.suffix,
                f.photo,
                f.status,
                GROUP_CONCAT(
                    CONCAT(s.subject_code, '|||', s.subject_desc, '|||', s.year_level, '|||', p.program_name)
                    ORDER BY s.subject_code
                    SEPARATOR '||;||'
                ) AS subjects_data
            FROM add_students st
            INNER JOIN add_classes ac
                ON ac.program_id = st.program
                AND (ac.year_level = ? OR ac.year_level = ?)
                AND TRIM(UPPER(ac.block)) = TRIM(UPPER(st.section))
            INNER JOIN class_subjects cs ON cs.class_id = ac.id
            INNER JOIN add_subjects s ON s.id = cs.subject_id
            LEFT JOIN add_programs p ON s.program_id = p.id
            INNER JOIN add_faculties f ON f.id = cs.faculty_id
            LEFT JOIN evaluations ev ON ev.student_id = st.id AND ev.faculty_id = f.id
            WHERE st.id = ? AND cs.faculty_id > 0 AND ev.id IS NULL
            GROUP BY 
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo, f.status
            ORDER BY f.lastname, f.firstname
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $student_year_raw, $student_year_formatted, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fallback for legacy records: derive from student's assigned subjects + faculty_subjects.
        if ($result->num_rows === 0) {
            $query = "
                SELECT 
                    f.id,
                    f.faculty_id,
                    f.email,
                    f.firstname,
                    f.lastname,
                    f.suffix,
                    f.photo,
                    f.status,
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
                LEFT JOIN evaluations ev ON ev.student_id = ss.student_id AND ev.faculty_id = f.id
                WHERE ev.id IS NULL
                GROUP BY 
                    f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo, f.status
                ORDER BY f.lastname, f.firstname
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
        }
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
                f.status,
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
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo, f.status
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
                f.status,
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
                f.id, f.faculty_id, f.email, f.firstname, f.lastname, f.suffix, f.photo, f.status
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
            "status" => $row["status"] ?? "active",
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
