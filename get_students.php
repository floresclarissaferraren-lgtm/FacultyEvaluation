<?php
header("Content-Type: application/json");
require 'connect.php';

function formattedYearLevel(string $yearlevel): string {
    $val = trim($yearlevel);
    if (ctype_digit($val)) {
        $n = intval($val);
        $suffix = $n === 1 ? 'st' : ($n === 2 ? 'nd' : ($n === 3 ? 'rd' : 'th'));
        return $n . $suffix . ' Year';
    }
    return $val;
}

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

function syncRegularStudentSubjectsByClass(mysqli $conn, array $student): void {
    $student_id = intval($student['id'] ?? 0);
    $year_raw = trim((string)($student['yearlevel'] ?? ''));
    $section = trim((string)($student['section'] ?? ''));
    $program_id = resolveProgramId($conn, (string)($student['program'] ?? ''));

    if ($student_id <= 0 || $program_id <= 0 || $section === '' || $year_raw === '' || strtolower($year_raw) === 'irregular') {
        return;
    }

    $year_fmt = formattedYearLevel($year_raw);
    $classStmt = $conn->prepare("
        SELECT id
        FROM add_classes
        WHERE program_id = ?
          AND (year_level = ? OR year_level = ?)
          AND TRIM(UPPER(block)) = TRIM(UPPER(?))
        LIMIT 1
    ");
    if (!$classStmt) return;
    $classStmt->bind_param("isss", $program_id, $year_raw, $year_fmt, $section);
    $classStmt->execute();
    $classRes = $classStmt->get_result();
    $class = $classRes ? $classRes->fetch_assoc() : null;
    $classStmt->close();
    if (!$class) return;

    $class_id = intval($class['id']);
    $subStmt = $conn->prepare("SELECT subject_id FROM class_subjects WHERE class_id = ?");
    if (!$subStmt) return;
    $subStmt->bind_param("i", $class_id);
    $subStmt->execute();
    $subRes = $subStmt->get_result();
    $subjectIds = [];
    while ($r = $subRes->fetch_assoc()) {
        $subjectIds[] = intval($r['subject_id']);
    }
    $subStmt->close();

    $conn->query("DELETE FROM student_subjects WHERE student_id = " . $student_id);
    $conn->query("DELETE FROM student_subject_classes WHERE student_id = " . $student_id);

    if (!empty($subjectIds)) {
        $ins = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
        if ($ins) {
            foreach ($subjectIds as $sid) {
                $ins->bind_param("ii", $student_id, $sid);
                $ins->execute();
            }
            $ins->close();
        }
    }
}

// Check if connection works
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS student_subject_classes (
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id int(11) NOT NULL,
    subject_id int(11) NOT NULL,
    class_id int(11) NOT NULL,
    UNIQUE KEY unique_student_subject_class (student_id, subject_id),
    KEY idx_ssc_student (student_id),
    KEY idx_ssc_class (class_id)
)");

$result = $conn->query("SELECT * FROM add_students ORDER BY id ASC");
if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

$students = [];
while($row = $result->fetch_assoc()){
    syncRegularStudentSubjectsByClass($conn, $row);

    // Get subjects for this student
    $student_id = $row['id'];
    $subject_query = "
        SELECT
            s.id,
            s.subject_code,
            s.subject_desc,
            s.year_level,
            p.program_name,
            ssc.class_id AS class_id,
            ac.year_level AS class_year_level,
            ac.block AS class_section,
            COALESCE(
                TRIM(CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''))),
                (
                    SELECT TRIM(CONCAT(f2.firstname, ' ', f2.lastname, ' ', COALESCE(f2.suffix, '')))
                    FROM add_classes ac2
                    INNER JOIN class_subjects cs2 ON cs2.class_id = ac2.id AND cs2.subject_id = s.id
                    INNER JOIN add_faculties f2 ON f2.id = cs2.faculty_id
                    WHERE ac2.program_id = st.program
                      AND (ac2.year_level = st.yearlevel OR ac2.year_level = ?)
                      AND TRIM(UPPER(ac2.block)) = TRIM(UPPER(st.section))
                    LIMIT 1
                )
            ) AS instructor_name
        FROM student_subjects ss
        INNER JOIN add_subjects s ON ss.subject_id = s.id
        INNER JOIN add_students st ON st.id = ss.student_id
        LEFT JOIN add_programs p ON s.program_id = p.id
        LEFT JOIN student_subject_classes ssc ON ssc.student_id = ss.student_id AND ssc.subject_id = ss.subject_id
        LEFT JOIN add_classes ac ON ac.id = ssc.class_id
        LEFT JOIN class_subjects cs ON cs.class_id = ac.id AND cs.subject_id = s.id
        LEFT JOIN add_faculties f ON f.id = cs.faculty_id
        WHERE ss.student_id = ?
        ORDER BY s.subject_code ASC
    ";
    
    $stmt = $conn->prepare($subject_query);
    $year_fmt = formattedYearLevel((string)$row['yearlevel']);
    $stmt->bind_param("si", $year_fmt, $student_id);
    $stmt->execute();
    $subject_result = $stmt->get_result();
    
    $subjects = [];
    while($subject_row = $subject_result->fetch_assoc()){
        $subjects[] = $subject_row;
    }

    // Fallback: derive subjects from class mapping for regular students with empty student_subjects.
    if (empty($subjects) && strtolower(trim((string)$row['yearlevel'])) !== 'irregular') {
        $year_raw = trim((string)$row['yearlevel']);
        $year_fmt = formattedYearLevel($year_raw);
        $program_id = $row['program'] ?? 0;
        $section = trim((string)($row['section'] ?? ''));

        if (!empty($program_id) && $year_raw !== '' && $section !== '') {
            $class_stmt = $conn->prepare("
                SELECT
                    s.id,
                    s.subject_code,
                    s.subject_desc,
                    s.year_level,
                    p.program_name,
                    ac.id AS class_id,
                    ac.year_level AS class_year_level,
                    ac.block AS class_section,
                    TRIM(CONCAT(f.firstname, ' ', f.lastname, ' ', COALESCE(f.suffix, ''))) AS instructor_name
                FROM add_classes ac
                INNER JOIN class_subjects cs ON cs.class_id = ac.id
                INNER JOIN add_subjects s ON s.id = cs.subject_id
                LEFT JOIN add_programs p ON s.program_id = p.id
                LEFT JOIN add_faculties f ON f.id = cs.faculty_id
                WHERE ac.program_id = ?
                  AND (ac.year_level = ? OR ac.year_level = ?)
                  AND TRIM(UPPER(ac.block)) = TRIM(UPPER(?))
                ORDER BY s.subject_code ASC
            ");
            $class_stmt->bind_param("isss", $program_id, $year_raw, $year_fmt, $section);
            $class_stmt->execute();
            $class_result = $class_stmt->get_result();

            while ($class_row = $class_result->fetch_assoc()) {
                $subjects[] = $class_row;
            }
            $class_stmt->close();
        }
    }
    
    $row['subjects'] = $subjects;
    $students[] = $row;
    $stmt->close();
}

// Add debug info
$debug_info = [
    'total_students' => count($students),
    'query_success' => true
];

echo json_encode($students);
$conn->close();
?>
