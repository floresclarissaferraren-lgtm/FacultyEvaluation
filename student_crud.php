<?php
header("Content-Type: application/json");
include 'connect.php';
require_once 'mailer.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

/* =========================
   ADD STUDENT
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

    $stmt = $conn->prepare("
        INSERT INTO add_students 
        (student_number,email,firstname,lastname,suffix,yearlevel,program,section,password)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssssssss",
        $student_number,
        $email,
        $firstname,
        $lastname,
        $suffix,
        $yearlevel,
        $program,
        $section,
        $hashedPassword
    );

    if ($stmt->execute()) {

        $student_id = $stmt->insert_id;

        /* subjects - automatic assignment for regular students */
        if (!empty($subjects)) {
            // Manual subject assignment (for special cases)
            $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");

            foreach ($subjects as $sid) {
                $sid = intval($sid);
                $sub->bind_param("ii", $student_id, $sid);
                $sub->execute();
            }

            $sub->close();
        } else {
            // Automatic subject assignment for regular students based on program and year level
            $auto_subjects = $conn->prepare("
                SELECT id FROM add_subjects 
                WHERE program_id = ? AND year_level = ?
                ORDER BY subject_code ASC
            ");
            $auto_subjects->bind_param("is", $program, $yearlevel);
            $auto_subjects->execute();
            $auto_result = $auto_subjects->get_result();

            $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");
            
            while ($subject_row = $auto_result->fetch_assoc()) {
                $subject_id = $subject_row['id'];
                $sub->bind_param("ii", $student_id, $subject_id);
                $sub->execute();
            }

            $auto_subjects->close();
            $sub->close();
        }

        /* ================= EMAIL WITH PASSWORD ================= */
        $body = "
            <h2>Welcome to Faculty Evaluation System</h2>
            <p>Hello $firstname $lastname</p>
            <p>Your account has been created successfully.</p>
            <p><b>Faculty Number:</b> $student_number</p>
            <p><b>Temporary Password:</b> $password</p>
            <hr>
            <p>Please use this password to login to the Faculty Evaluation System.</p>
            <p>You can change your password after logging in.</p>
        ";

        $sent = sendEmail(
            $email,
            "$firstname $lastname",
            "Faculty Evaluation System",
            $body
        );

        echo json_encode([
            "success" => true,
            "message" => $sent ? "Student added + email sent" : "Student added but email failed"
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
        SET firstname=?, lastname=?, suffix=?, yearlevel=?, program=?, section=?, email=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssssi",
        $firstname,
        $lastname,
        $suffix,
        $yearlevelValue,
        $program,
        $section,
        $email,
        $id
    );

    if ($stmt->execute()) {

        $conn->query("DELETE FROM student_subjects WHERE student_id=$id");

        if (!empty($subjects)) {
            // Manual subject assignment (for special cases)
            $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");

            foreach ($subjects as $sid) {
                $sid = intval($sid);
                $sub->bind_param("ii", $id, $sid);
                $sub->execute();
            }

            $sub->close();
        } else {
            // Automatic subject assignment for regular students based on program and year level
            $auto_subjects = $conn->prepare("
                SELECT id FROM add_subjects 
                WHERE program_id = ? AND year_level = ?
                ORDER BY subject_code ASC
            ");
            $auto_subjects->bind_param("is", $program, $yearlevel);
            $auto_subjects->execute();
            $auto_result = $auto_subjects->get_result();

            $sub = $conn->prepare("INSERT INTO student_subjects (student_id,subject_id) VALUES (?,?)");
            
            while ($subject_row = $auto_result->fetch_assoc()) {
                $subject_id = $subject_row['id'];
                $sub->bind_param("ii", $id, $subject_id);
                $sub->execute();
            }

            $auto_subjects->close();
            $sub->close();
        }

        echo json_encode(["success"=>true,"message"=>"Student updated"]);
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
    $conn->query("DELETE FROM add_students WHERE id=$id");

    echo json_encode(["success"=>true,"message"=>"Student deleted"]);
}

$conn->close();
?>