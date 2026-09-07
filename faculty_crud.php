<?php
require_once 'security.php';
requireRole('admin');
requireMethod('POST');
header("Content-Type: application/json");
include 'connect.php';
require_once 'mailer.php';
require_once 'ensure_schema_column.php';

// Generate random password function
function generateRandomPassword($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars), 0, $length);
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

ensureColumnExists($conn, 'add_faculties', 'status', 'VARCHAR(20) NOT NULL DEFAULT "active"');

/* =========================
   ADD NEW FACULTY
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

        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                throw new RuntimeException("Faculty already exists");
            }
            throw new RuntimeException("Unable to save faculty profile: " . $stmt->error);
        }
        $faculty_db_id = $stmt->insert_id;
        $stmt->close();

        // create login credentials for faculty
        $faculty_username = $faculty_id;
        $loginStmt = $conn->prepare("INSERT INTO faculty_login (faculty_id, faculty_username, faculty_password) VALUES (?, ?, ?)");
        if (!$loginStmt) {
            throw new RuntimeException("Unable to prepare faculty login credentials: " . $conn->error);
        }
        $loginStmt->bind_param("iss", $faculty_db_id, $faculty_username, $hashed_password);
        if (!$loginStmt->execute()) {
            throw new RuntimeException("Unable to save faculty login credentials: " . $loginStmt->error);
        }
        $loginStmt->close();

        // Confirm that the exact username and password hash were saved before committing.
        $verifyLogin = $conn->prepare("SELECT faculty_password FROM faculty_login WHERE faculty_id = ? AND faculty_username = ? LIMIT 1");
        if (!$verifyLogin) {
            throw new RuntimeException("Unable to verify faculty login credentials: " . $conn->error);
        }
        $verifyLogin->bind_param("is", $faculty_db_id, $faculty_username);
        if (!$verifyLogin->execute()) {
            throw new RuntimeException("Unable to verify faculty login credentials: " . $verifyLogin->error);
        }
        $verifyResult = $verifyLogin->get_result();
        $verifyRow = $verifyResult ? $verifyResult->fetch_assoc() : null;
        $verifyLogin->close();

        if (!$verifyRow || !password_verify($password, $verifyRow['faculty_password'])) {
            throw new RuntimeException("Faculty login credentials could not be verified");
        }

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
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host . '/FacultyEvaluation';

    $title = "Welcome to Faculty Evaluation System";
    $greeting = "Hello Professor " . htmlspecialchars($firstname . ' ' . $lastname, ENT_QUOTES, 'UTF-8') . ",";
    $content_html = "
        <p style='margin: 0 0 16px 0;'>Your faculty instructor account has been successfully created. You can now access the Faculty Evaluation System using your credentials below.</p>
        <p style='margin: 0 0 16px 0;'><strong>Faculty Number / Username:</strong> " . htmlspecialchars($faculty_id, ENT_QUOTES, 'UTF-8') . "</p>
        <p style='margin: 0 0 16px 0;'>For security reasons, we strongly recommend that you change your password immediately after logging in for the first time.</p>
    ";

    $highlight_box = [
        'label' => 'Temporary Password',
        'value' => $password,
        'subtext' => 'Keep this password secure'
    ];

    $cta = [
        'label' => 'Log In to Dashboard',
        'url' => $baseUrl . '/EvalMain.php'
    ];

    $body = getEmailHTML($title, $greeting, $content_html, $highlight_box, $cta);

    // Send email asynchronously to avoid blocking
    $sent = sendEmail(
        $email,
        "$firstname $lastname",
        "Welcome to Faculty Evaluation System",
        $body
    );

    if (!$sent) {
        error_log("Failed to send email to faculty: $email");
    }

    echo json_encode([
        "success" => true,
        "message" => $sent ? "Faculty added successfully and email sent with password" : "Faculty added successfully but email failed to send",
        "username" => $faculty_username,
        "temporary_password" => $password
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
   UPDATE FACULTY STATUS
========================= */
elseif ($action === "update_status") {

    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    $allowedStatuses = ['active', 'inactive', 'on leave', 'archived'];
    if (!$id || !in_array(strtolower(trim($status)), $allowedStatuses, true)) {
        echo json_encode(["success"=>false,"message"=>"Invalid ID or status"]);
        exit;
    }
    $status = strtolower(trim($status));

    $stmt = $conn->prepare("UPDATE add_faculties SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["success"=>true,"message"=>"Faculty status updated successfully"]);
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
