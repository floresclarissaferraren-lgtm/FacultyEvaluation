<?php
header("Content-Type: application/json");
include 'connect.php';
require_once 'mailer.php';

// Generate random password function
function generateRandomPassword($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars), 0, $length);
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

/* =========================
   ADD FACULTY
========================= */
if ($action === "add") {

    $faculty_id = trim($data['faculty_id'] ?? '');
    $email = trim($data['email'] ?? '');
    $firstname = trim($data['firstname'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $suffix = trim($data['suffix'] ?? '');
    $photo = $data['photo'] ?? '';
    $subjects = $data['subjects'] ?? [];
    
    // Generate random password
    $password = generateRandomPassword();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle photo - if base64 string, store as is
    if (!empty($photo) && strpos($photo, 'data:image') === 0) {
        // Photo is base64 encoded, store as is
        error_log("Photo is base64 encoded, length: " . strlen($photo));
    } else {
        // Empty or invalid photo
        $photo = '';
    }

    if (!$faculty_id || !$email || !$firstname || !$lastname) {
        echo json_encode(["success"=>false,"message"=>"Missing required fields"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success"=>false,"message"=>"Invalid email"]);
        exit;
    }

    /* duplicate check */
    $check = $conn->prepare("SELECT id FROM add_faculties WHERE faculty_id=? OR email=?");
    $check->bind_param("ss", $faculty_id, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["success"=>false,"message"=>"Faculty already exists"]);
        exit;
    }
    $check->close();

    $conn->begin_transaction();

    try {

        // Check if password column exists
        $result = $conn->query("SHOW COLUMNS FROM add_faculties LIKE 'password'");
        $hasPasswordColumn = $result->num_rows > 0;
        
        if ($hasPasswordColumn) {
            // Insert with password
            $stmt = $conn->prepare("
                INSERT INTO add_faculties
                (faculty_id,email,firstname,lastname,suffix,photo,password)
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "sssssss",
                $faculty_id,
                $email,
                $firstname,
                $lastname,
                $suffix,
                $photo,
                $hashed_password
            );
        } else {
            // Insert without password (fallback)
            $stmt = $conn->prepare("
                INSERT INTO add_faculties
                (faculty_id,email,firstname,lastname,suffix,photo)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "ssssss",
                $faculty_id,
                $email,
                $firstname,
                $lastname,
                $suffix,
                $photo
            );
        }

        $stmt->execute();
        $faculty_db_id = $stmt->insert_id;
        $stmt->close();

        /* subjects */
        if (!empty($subjects)) {

            $sub = $conn->prepare("
                INSERT INTO faculty_subjects (faculty_id,subject_id)
                VALUES (?,?)
            ");

            foreach ($subjects as $sid) {
                $sid = intval($sid);
                $sub->bind_param("ii", $faculty_db_id, $sid);
                $sub->execute();
            }

            $sub->close();
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Faculty add error: " . $e->getMessage());
        echo json_encode(["success"=>false,"message"=>"Database error: " . $e->getMessage()]);
        exit;
    }

    /* ================= EMAIL WITH PASSWORD ================= */
    $body = "
        <h2>Welcome to Faculty Evaluation System</h2>
        <p>Hello $firstname $lastname</p>
        <p>Your account has been created successfully.</p>
        <p><b>Faculty Number:</b> FC-$faculty_id</p>
        <p><b>Temporary Password:</b> $password</p>
        <hr>
        <p>Please use this password to login to the Faculty Evaluation System.</p>
        <p>You can change your password after logging in.</p>
    ";

    // Send email asynchronously to avoid blocking
    $sent = sendEmail(
        $email,
        "$firstname $lastname",
        "Faculty Evaluation System",
        $body
    );

    if (!$sent) {
        error_log("Failed to send email to faculty: $email");
    }

    echo json_encode([
        "success" => true,
        "message" => $sent ? "Faculty added successfully and email sent with password" : "Faculty added successfully but email failed to send"
    ]);
}

/* =========================
   EDIT FACULTY
========================= */
elseif ($action === "edit") {

    $id = intval($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(["success"=>false,"message"=>"Invalid ID"]);
        exit;
    }

    $faculty_id = $data['faculty_id'];
    $email = $data['email'];
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $suffix = $data['suffix'];
    $photo = $data['photo'];
    $subjects = $data['subjects'] ?? [];

    $stmt = $conn->prepare("
        UPDATE add_faculties
        SET faculty_id=?, email=?, firstname=?, lastname=?, suffix=?, photo=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssi",
        $faculty_id,
        $email,
        $firstname,
        $lastname,
        $suffix,
        $photo,
        $id
    );

    if ($stmt->execute()) {

        $conn->query("DELETE FROM faculty_subjects WHERE faculty_id=$id");

        if (!empty($subjects)) {

            $sub = $conn->prepare("
                INSERT INTO faculty_subjects (faculty_id,subject_id)
                VALUES (?,?)
            ");

            foreach ($subjects as $sid) {
                $sid = intval($sid);
                $sub->bind_param("ii", $id, $sid);
                $sub->execute();
            }

            $sub->close();
        }

        echo json_encode(["success"=>true,"message"=>"Faculty updated"]);
    } else {
        echo json_encode(["success"=>false,"message"=>$stmt->error]);
    }

    $stmt->close();
}

/* =========================
   DELETE FACULTY
========================= */
elseif ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(["success"=>false,"message"=>"Invalid ID"]);
        exit;
    }

    $conn->query("DELETE FROM faculty_subjects WHERE faculty_id=$id");
    $conn->query("DELETE FROM add_faculties WHERE id=$id");

    echo json_encode(["success"=>true,"message"=>"Faculty deleted"]);
}

$conn->close();
?>