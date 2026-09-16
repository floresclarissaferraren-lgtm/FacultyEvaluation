<?php
header("Content-Type: application/json");
include 'connect.php';
require_once 'evaluation_schema.php';
require_once 'ensure_schema_column.php';
require_once 'evaluation_period_helper.php';

try {
    ensureColumnExists($conn, 'add_faculties', 'status', 'VARCHAR(20) NOT NULL DEFAULT "active"');

    ensureEvaluationsSchema($conn);

    $student_id = intval($_GET['student_id'] ?? 0);
    $year_level = trim($_GET['year_level'] ?? '');

    if ($student_id > 0 && !isEvaluationOngoing($conn)) {
        echo json_encode([]);
        $conn->close();
        exit;
    }

    $student_year_raw = '';
    $student_year_formatted = '';

    // Function to resolve program ID from program name/code
    function resolveProgramId(mysqli $conn, string $program): int {
        $program = trim($program);
        if ($program === '') return 0;
        if (ctype_digit($program)) return intval($program);
        $stmt = $conn->prepare("SELECT id FROM add_programs WHERE program_code = ? OR program_name = ? LIMIT 1");
        if (!$stmt) return 0;
        $stmt->bind_param("ss", $program, $program);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ? intval($row['id']) : 0;
    }

    if ($student_id > 0) {
        $student_meta_stmt = $conn->prepare("SELECT yearlevel, program, section FROM add_students WHERE id = ? LIMIT 1");
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
            $student_program = $student_meta['program'] ?? '';
            $student_section = trim((string)($student_meta['section'] ?? ''));
        }
        $student_meta_stmt->close();

        // Resolve program ID from program name/code
        $program_id = resolveProgramId($conn, $student_program);

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
                ev.id AS evaluation_id,
                cs.subject_id,
                ac.id AS class_id,
                ac.year_level AS class_year_level,
                ac.block AS class_section,
                s.subject_code,
                s.subject_desc,
                s.year_level,
                p.program_code,
                p.program_name
            FROM add_students st
            INNER JOIN add_classes ac
                ON ac.program_id = ?
                AND (ac.year_level = ? OR ac.year_level = ?)
                AND TRIM(UPPER(ac.block)) = TRIM(UPPER(?))
            INNER JOIN class_subjects cs ON cs.class_id = ac.id
            INNER JOIN add_subjects s ON s.id = cs.subject_id
            LEFT JOIN add_programs p ON s.program_id = p.id
            INNER JOIN add_faculties f ON f.id = cs.faculty_id
            LEFT JOIN evaluations ev
                ON ev.student_id = st.id
               AND ev.faculty_id = f.id
               AND ev.subject_id = cs.subject_id
               AND ev.class_id = ac.id
               AND ev.period_id = (SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1)
                WHERE st.id = ? AND cs.faculty_id > 0
            ORDER BY f.lastname, f.firstname, p.program_code, s.year_level, s.subject_code
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssi", $program_id, $student_year_raw, $student_year_formatted, $student_section, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fallback for legacy records: derive from student's assigned subjects + faculty_subjects.
        if ($result->num_rows === 0) {
            $hasClassAssignments = false;
            $classCheck = $conn->prepare("
                SELECT cs.subject_id
                FROM add_classes ac
                INNER JOIN class_subjects cs ON cs.class_id = ac.id
                WHERE ac.program_id = ?
                  AND (ac.year_level = ? OR ac.year_level = ?)
                  AND TRIM(UPPER(ac.block)) = TRIM(UPPER(?))
                  AND cs.faculty_id > 0
                LIMIT 1
            ");
            if ($classCheck) {
                $classCheck->bind_param("isss", $program_id, $student_year_raw, $student_year_formatted, $student_section);
                $classCheck->execute();
                $hasClassAssignments = (bool)$classCheck->get_result()->fetch_assoc();
                $classCheck->close();
            }

            if ($hasClassAssignments) {
                echo json_encode([]);
                $conn->close();
                exit;
            }

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
                    ev.id AS evaluation_id,
                    ss.subject_id,
                    COALESCE(ssc.class_id, 0) AS class_id,
                    ac.year_level AS class_year_level,
                    ac.block AS class_section,
                    s.subject_code,
                    s.subject_desc,
                    s.year_level,
                    p.program_code,
                    p.program_name
                FROM student_subjects ss
                LEFT JOIN student_subject_classes ssc
                    ON ssc.student_id = ss.student_id
                   AND ssc.subject_id = ss.subject_id
                LEFT JOIN class_subjects cs
                    ON cs.class_id = ssc.class_id
                   AND cs.subject_id = ss.subject_id
                   AND cs.faculty_id > 0
                LEFT JOIN faculty_subjects fs ON fs.subject_id = ss.subject_id
                INNER JOIN add_faculties f ON f.id = COALESCE(NULLIF(cs.faculty_id, 0), fs.faculty_id)
                INNER JOIN add_subjects s ON ss.subject_id = s.id
                LEFT JOIN add_programs p ON s.program_id = p.id
                LEFT JOIN add_classes ac ON ac.id = COALESCE(ssc.class_id, 0)
                LEFT JOIN evaluations ev
                    ON ev.student_id = ss.student_id
                   AND ev.faculty_id = f.id
                   AND ev.subject_id = ss.subject_id
                   AND ev.class_id = COALESCE(ssc.class_id, 0)
                   AND ev.period_id = (SELECT active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1)
                     WHERE ss.student_id = ?
                ORDER BY f.lastname, f.firstname, p.program_code, s.year_level, s.subject_code
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
                fs.subject_id,
                0 AS class_id,
                NULL AS class_year_level,
                NULL AS class_section,
                s.subject_code,
                s.subject_desc,
                s.year_level,
                p.program_code,
                p.program_name
            FROM add_faculties f
            INNER JOIN faculty_subjects fs ON f.id = fs.faculty_id
            INNER JOIN add_subjects s ON fs.subject_id = s.id AND s.year_level = ?
            LEFT JOIN add_programs p ON s.program_id = p.id
            ORDER BY f.lastname, f.firstname, p.program_code, s.year_level, s.subject_code
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
                fs.subject_id,
                0 AS class_id,
                NULL AS class_year_level,
                NULL AS class_section,
                s.subject_code,
                s.subject_desc,
                s.year_level,
                p.program_code,
                p.program_name
            FROM add_faculties f
            LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
            LEFT JOIN add_subjects s ON fs.subject_id = s.id
            LEFT JOIN add_programs p ON s.program_id = p.id
            ORDER BY f.lastname, f.firstname, p.program_code, s.year_level, s.subject_code
        ";

        $result = $conn->query($query);
    }

    if (!$result) {
        echo json_encode([
            "error" => "Query failed: " . $conn->error
        ]);
        exit;
    }

    if ($student_id > 0) {
        // ── STUDENT PATH ─────────────────────────────────────────────────────
        // Return one entry per faculty-subject pair so the student dashboard
        // renders a separate evaluation card for every subject the professor
        // teaches.  Each row from the query is already a unique
        // faculty + subject + class combination (evaluated ones are excluded
        // by the LEFT JOIN / ev.id IS NULL filter in the query above).
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $subject = [
                'subject_id'      => intval($row['subject_id'] ?? 0),
                'class_id'        => intval($row['class_id']   ?? 0),
                'class_year_level'=> $row['class_year_level']  ?? '',
                'class_section'   => $row['class_section']     ?? '',
                'subject_code'    => $row['subject_code']      ?? '',
                'subject_desc'    => $row['subject_desc']      ?? '',
                'year_level'      => $row['year_level']        ?? '',
                'program_code'    => $row['program_code']      ?? '',
                'program_name'    => $row['program_name']      ?? '',
            ];
            $data[] = [
                'id'            => intval($row['id']),
                'faculty_id'    => $row['faculty_id'],
                'email'         => $row['email'],
                'firstname'     => $row['firstname'],
                'lastname'      => $row['lastname'],
                'suffix'        => $row['suffix']  ?? '',
                'photo'         => $row['photo']   ?? '',
                'status'        => $row['status']  ?? 'active',
                'evaluation_id' => intval($row['evaluation_id'] ?? 0),
                // subjects array keeps the same shape the JS already reads
                'subjects'      => [$subject],
                'subject_codes' => [$subject['subject_code']],
                // flat copies for legacy JS that reads top-level keys directly
                'subject_id'    => $subject['subject_id'],
                'class_id'      => $subject['class_id'],
                'subject_code'  => $subject['subject_code'],
                'subject_desc'  => $subject['subject_desc'],
                'class_year_level' => $subject['class_year_level'],
                'class_section'    => $subject['class_section'],
            ];
        }
        echo json_encode($data);

    } else {
        // ── ADMIN / YEAR-LEVEL PATH ──────────────────────────────────────────
        // Group every subject under its faculty so each faculty appears exactly
        // once in the Faculty Management table.
        $byId = [];
        while ($row = $result->fetch_assoc()) {
            $fid        = intval($row['id']);
            $subjectCode = $row['subject_code'] ?? '';
            $subject = [
                'subject_id'      => intval($row['subject_id'] ?? 0),
                'class_id'        => intval($row['class_id']   ?? 0),
                'class_year_level'=> $row['class_year_level']  ?? '',
                'class_section'   => $row['class_section']     ?? '',
                'subject_code'    => $subjectCode,
                'subject_desc'    => $row['subject_desc']      ?? '',
                'year_level'      => $row['year_level']        ?? '',
                'program_code'    => $row['program_code']      ?? '',
                'program_name'    => $row['program_name']      ?? '',
            ];

            if (!isset($byId[$fid])) {
                $byId[$fid] = [
                    'id'            => $fid,
                    'faculty_id'    => $row['faculty_id'],
                    'email'         => $row['email'],
                    'firstname'     => $row['firstname'],
                    'lastname'      => $row['lastname'],
                    'suffix'        => $row['suffix']  ?? '',
                    'photo'         => $row['photo']   ?? '',
                    'status'        => $row['status']  ?? 'active',
                    'subjects'      => [],
                    'subject_codes' => [],
                ];
            }

            if ($subjectCode !== '') {
                // Guard against duplicate subject+class entries in the result.
                $alreadyAdded = false;
                foreach ($byId[$fid]['subjects'] as $existing) {
                    if ($existing['subject_id'] === $subject['subject_id']
                        && $existing['class_id']   === $subject['class_id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if (!$alreadyAdded) {
                    $byId[$fid]['subjects'][]      = $subject;
                    $byId[$fid]['subject_codes'][] = $subjectCode;
                }
            }
        }
        echo json_encode(array_values($byId));
    }

} catch (Exception $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>
