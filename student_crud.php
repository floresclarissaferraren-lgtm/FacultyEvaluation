<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
header("Content-Type: application/json");
include 'connect.php';
require_once 'mailer.php';
require_once 'ensure_schema_column.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

ensureColumnExists($conn, 'add_students', 'student_type', 'VARCHAR(20) DEFAULT "regular"', 'section');
ensureColumnExists($conn, 'add_students', 'status', 'VARCHAR(20) NOT NULL DEFAULT "active"', 'student_type');

function resolveProgramId(mysqli $conn, string $program): int {
    $program = trim($program);
    if ($program === '') {
        return 0;
    }
    if (ctype_digit($program)) {
        return intval($program);
    }

    $stmt = $conn->prepare("SELECT id FROM add_programs WHERE program_name = ? OR program_code = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("ss", $program, $program);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? intval($row['id']) : 0;
}

function assignStudentSubjects(mysqli $conn, int $student_id, string $program, string $yearlevel, string $section, array $subjects, bool $is_regular): array {
    $conn->query("CREATE TABLE IF NOT EXISTS student_subject_classes (
        id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        student_id int(11) NOT NULL,
        subject_id int(11) NOT NULL,
        class_id int(11) NOT NULL,
        UNIQUE KEY unique_student_subject_class (student_id, subject_id),
        KEY idx_ssc_student (student_id),
        KEY idx_ssc_class (class_id)
    )");

    $program_id = resolveProgramId($conn, $program);
    if ($program_id <= 0) {
        return ['inserted' => 0, 'message' => 'Invalid program for subject assignment'];
    }

    if (!$is_regular && !empty($subjects)) {
        $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");
        $ssc = $conn->prepare("INSERT INTO student_subject_classes (student_id, subject_id, class_id) VALUES (?, ?, ?)");

        $inserted = 0;
        foreach ($subjects as $entry) {
            $sid = is_array($entry) ? intval($entry['id'] ?? 0) : intval($entry);
            $class_id = is_array($entry) ? intval($entry['class_id'] ?? 0) : 0;
            if (!$sid) {
                continue;
            }
            $sub->bind_param("ii", $student_id, $sid);
            if ($sub->execute()) {
                $inserted++;
            }

            if ($class_id > 0) {
                $ssc->bind_param("iii", $student_id, $sid, $class_id);
                $ssc->execute();
            }
        }
        $sub->close();
        $ssc->close();
        return ['inserted' => $inserted, 'message' => $inserted > 0 ? '' : 'No irregular subjects were inserted'];
    }

    $normalized_year = trim($yearlevel);
    $formatted_year = $normalized_year;
    if (ctype_digit($normalized_year)) {
        $n = intval($normalized_year);
        $suffix = $n === 1 ? 'st' : ($n === 2 ? 'nd' : ($n === 3 ? 'rd' : 'th'));
        $formatted_year = $n . $suffix . ' Year';
    }

    // Regular students: prioritize class-based subjects (program + year level + section/block).
    $class_subjects = $conn->prepare("
        SELECT DISTINCT cs.subject_id, ac.id AS class_id
        FROM add_classes ac
        INNER JOIN class_subjects cs ON cs.class_id = ac.id
        WHERE ac.program_id = ?
          AND (ac.year_level = ? OR ac.year_level = ?)
          AND TRIM(UPPER(ac.block)) = TRIM(UPPER(?))
        ORDER BY cs.subject_id ASC
    ");
    $class_subjects->bind_param("isss", $program_id, $normalized_year, $formatted_year, $section);
    $class_subjects->execute();
    $class_result = $class_subjects->get_result();

    $subject_assignments = [];
    while ($row = $class_result->fetch_assoc()) {
        $subject_assignments[] = [
            'subject_id' => intval($row['subject_id']),
            'class_id' => intval($row['class_id'])
        ];
    }
    $class_subjects->close();

    // Fallback for existing data setups without class_subjects yet.
    if (empty($subject_assignments)) {
        $auto_subjects = $conn->prepare("
            SELECT id
            FROM add_subjects
            WHERE program_id = ? AND (year_level = ? OR year_level = ?)
            ORDER BY subject_code ASC
        ");
        $auto_subjects->bind_param("iss", $program_id, $normalized_year, $formatted_year);
        $auto_subjects->execute();
        $auto_result = $auto_subjects->get_result();

        while ($subject_row = $auto_result->fetch_assoc()) {
            $subject_assignments[] = [
                'subject_id' => intval($subject_row['id']),
                'class_id' => 0
            ];
        }
        $auto_subjects->close();
    }

    if (!empty($subject_assignments)) {
        $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");
        $ssc = $conn->prepare("INSERT INTO student_subject_classes (student_id, subject_id, class_id) VALUES (?, ?, ?)");
        $inserted = 0;
        foreach ($subject_assignments as $assignment) {
            $subject_id = intval($assignment['subject_id']);
            $class_id = intval($assignment['class_id']);
            $sub->bind_param("ii", $student_id, $subject_id);
            if ($sub->execute()) {
                $inserted++;
            }
            if ($class_id > 0 && $ssc) {
                $ssc->bind_param("iii", $student_id, $subject_id, $class_id);
                $ssc->execute();
            }
        }
        $sub->close();
        if ($ssc) $ssc->close();
        return ['inserted' => $inserted, 'message' => $inserted > 0 ? '' : 'No regular subjects were inserted'];
    }
    return ['inserted' => 0, 'message' => 'No matching subjects found for the selected program/year/section'];
}

/* =========================
   ADD NEW STUDENT
========================= */
if ($action === "add") {

    $student_number = trim($data['student_number'] ?? '');
    $email = trim($data['email'] ?? '');
    $firstname = trim($data['firstname'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $suffix = $data['suffix'] ?? '';
    $yearlevel = $data['yearlevel'] ?? '';
    $program = $data['program'] ?? '';
    $section = $data['section'] ?? '';
    $subjects = $data['subjects'] ?? [];
    $student_type = $data['student_type'] ?? 'regular';

    if (!$student_number || !$email || !$firstname || !$lastname) {
        echo json_encode(["success"=>false,"message"=>"Missing fields"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success"=>false,"message"=>"Invalid email"]);
        exit;
    }

    /* duplicate student */
    $check = $conn->prepare("SELECT id FROM add_students WHERE student_number=? OR email=?");
    $check->bind_param("ss", $student_number, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success"=>false,"message"=>"Student already exists"]);
        exit;
    }
    $check->close();

    $password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $defaultStatus = 'active';

    $yearlevelValue = $student_type === 'irregular' ? 'irregular' : $yearlevel;
    $stmt = $conn->prepare("
        INSERT INTO add_students 
        (student_number,email,firstname,lastname,suffix,yearlevel,program,section,student_type,status,password)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssssssssss",
        $student_number,
        $email,
        $firstname,
        $lastname,
        $suffix,
        $yearlevelValue,
        $program,
        $section,
        $student_type,
        $defaultStatus,
        $hashedPassword
    );

    if ($stmt->execute()) {

        $student_id = $stmt->insert_id;

        $assignResult = assignStudentSubjects($conn, $student_id, $program, $yearlevelValue, $section, $subjects, $student_type !== 'irregular');

        /* ================= EMAIL WITH PASSWORD ================= */
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host . '/FacultyEvaluation';

        $title = "Welcome to Faculty Evaluation System";
        $greeting = "Hello " . htmlspecialchars($firstname . ' ' . $lastname, ENT_QUOTES, 'UTF-8') . ",";
        $content_html = "
            <p style='margin: 0 0 16px 0;'>Your student account has been successfully created. You can now access the Faculty Evaluation System using your credentials below.</p>
            <p style='margin: 0 0 16px 0;'><strong>Student Number:</strong> " . htmlspecialchars($student_number, ENT_QUOTES, 'UTF-8') . "</p>
            <p style='margin: 0 0 16px 0;'>For security reasons, we strongly recommend that you change your password immediately after logging in for the first time.</p>
        ";

        $highlight_box = [
            'label' => 'Temporary Password',
            'value' => $password,
            'subtext' => 'Keep this password secure'
        ];

        $cta = [
            'label' => 'Log In to System',
            'url' => $baseUrl . '/EvalMain.php'
        ];

        $body = getEmailHTML($title, $greeting, $content_html, $highlight_box, $cta);

        $sent = sendEmail(
            $email,
            "$firstname $lastname",
            "Welcome to Faculty Evaluation System",
            $body
        );

        echo json_encode([
            "success" => true,
            "message" => $sent ? "Student added + email sent" : "Student added but email failed",
            "subject_inserted" => $assignResult['inserted'] ?? 0,
            "subject_message" => $assignResult['message'] ?? ""
        ]);

    } else {
        echo json_encode(["success"=>false,"message"=>$stmt->error]);
    }

    $stmt->close();
}

/* =========================
   EDIT STUDENT
========================= */
elseif ($action === "edit") {

    $id = intval($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(["success"=>false,"message"=>"Invalid ID"]);
        exit;
    }

    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $suffix = $data['suffix'];
    $yearlevel = $data['yearlevel'];
    $program = $data['program'];
    $section = $data['section'];
    $email = $data['email'];
    $student_type = $data['student_type'] ?? 'regular';
    $subjects = $data['subjects'] ?? [];

    // Set the yearlevel value based on student type
    $yearlevelValue = $student_type === 'irregular' ? 'irregular' : $yearlevel;
    
    $stmt = $conn->prepare("
        UPDATE add_students 
        SET firstname=?, lastname=?, suffix=?, yearlevel=?, program=?, section=?, email=?, student_type=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssssi",
        $firstname,
        $lastname,
        $suffix,
        $yearlevelValue,
        $program,
        $section,
        $email,
        $student_type,
        $id
    );

    if ($stmt->execute()) {

        $conn->query("DELETE FROM student_subjects WHERE student_id=$id");
        $conn->query("DELETE FROM student_subject_classes WHERE student_id=$id");

        $assignResult = assignStudentSubjects($conn, $id, $program, $yearlevelValue, $section, $subjects, $student_type !== 'irregular');

        echo json_encode([
            "success"=>true,
            "message"=>"Student updated",
            "subject_inserted" => $assignResult['inserted'] ?? 0,
            "subject_message" => $assignResult['message'] ?? ""
        ]);
    } else {
        echo json_encode(["success"=>false,"message"=>$stmt->error]);
    }

    $stmt->close();
}

/* =========================
   DELETE STUDENT
========================= */
elseif ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(["success"=>false,"message"=>"Invalid ID"]);
        exit;
    }

    $conn->query("DELETE FROM student_subjects WHERE student_id=$id");
    $conn->query("DELETE FROM student_subject_classes WHERE student_id=$id");
    $conn->query("DELETE FROM add_students WHERE id=$id");

    echo json_encode(["success"=>true,"message"=>"Student deleted"]);
}

/* =========================
   UPDATE STUDENT STATUS
========================= */
elseif ($action === "update_status") {

    $id = intval($data['id'] ?? 0);
    $status = strtolower(trim($data['status'] ?? ''));

    if (!$id) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    if (!in_array($status, ["active", "inactive", "archived"], true)) {
        echo json_encode(["success" => false, "message" => "Invalid status"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE add_students SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Student status updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
