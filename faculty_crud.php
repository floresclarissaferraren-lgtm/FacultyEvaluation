<?php
header("Content-Type: application/json");
include 'connect.php';

require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';
require __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    $suffix = $data['suffix'] ?? '';
    $program = $data['program'] ?? '';
    $yearlevel = $data['yearlevel'] ?? '';
    $subjects = $data['subjects'] ?? [];

    // Validate required fields
    if (
        empty($faculty_id) || empty($email) ||
        empty($firstname) || empty($lastname)
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Please fill all required fields"
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        exit;
    }

    /* CHECK DUPLICATES (faculty ID) */
    $check = $conn->prepare("SELECT id FROM add_faculty WHERE faculty_id=?");
    $check->bind_param("s", $faculty_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Faculty ID already exists"
        ]);
        exit;
    }
    $check->close();

    /* CHECK DUPLICATES (email) */
    $checkEmail = $conn->prepare("SELECT id FROM add_faculty WHERE email=?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        exit;
    }
    $checkEmail->close();

    // Generate password
    $password = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    /* INSERT FACULTY */
    $stmt = $conn->prepare("
        INSERT INTO add_faculties
        (faculty_id,email,firstname,lastname,suffix,program,yearlevel,password) 
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssssssss",
        $faculty_id,
        $email,
        $firstname,
        $lastname,
        $suffix,
        $program,
        $yearlevel,
        $hashedPassword
    );

    if ($stmt->execute()) {
        $faculty_db_id = $stmt->insert_id;

        /* INSERT FACULTY SUBJECTS IF ANY */
        if (!empty($subjects) && is_array($subjects)) {
            $subjectStmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id) VALUES (?, ?)");
            
            foreach ($subjects as $subject_id) {
                $subjectStmt->bind_param("ii", $faculty_db_id, $subject_id);
                $subjectStmt->execute();
            }
            $subjectStmt->close();
        }

        /* =========================
           SEND EMAIL
        ========================= */
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;

            $mail->Username = 'floresclarissaferraren@gmail.com';
            $mail->Password = 'nicj elgi ruam ozca';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->SMTPAutoTLS = true;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->SMTPDebug = 0;

            $mail->setFrom('floresclarissaferraren@gmail.com', 'Faculty Evaluation System');
            $mail->addAddress($email, $firstname . " " . $lastname);

            $mail->isHTML(true);
            $mail->Subject = "Your Faculty Account Credentials";
            $mail->Body = "
                <h2>Welcome to Faculty Evaluation System</h2>
                <p>Hello <b>$firstname $lastname</b>,</p>

                <p>Your faculty account has been created successfully.</p>

                <p><b>Faculty ID:</b> $faculty_id</p>
                <p><b>Email:</b> $email</p>
                <p><b>Temporary Password:</b> $password</p>

                <br>
                <p>Please change your password immediately after login.</p>

                <br>
                <p>Regards,<br>Faculty Evaluation System</p>
            ";

            $mail->send();

            echo json_encode([
                "success" => true,
                "message" => "Faculty added successfully. Email sent!"
            ]);

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);

            echo json_encode([
                "success" => false,
                "message" => "Faculty saved but email failed: " . $mail->ErrorInfo
            ]);
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => $stmt->error
        ]);
    }

    $stmt->close();
}

/* =========================
   EDIT FACULTY
========================= */
elseif ($action === "edit") {

    $id = intval($data['id'] ?? 0);
    $firstname = trim($data['firstname'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $suffix = $data['suffix'] ?? '';
    $email = trim($data['email'] ?? '');
    $program = $data['program'] ?? '';
    $yearlevel = $data['yearlevel'] ?? '';
    $subjects = $data['subjects'] ?? [];

    if (empty($id)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE add_faculties 
        SET firstname=?, lastname=?, suffix=?, email=?, program=?, yearlevel=? 
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssi",
        $firstname,
        $lastname,
        $suffix,
        $email,
        $program,
        $yearlevel,
        $id
    );

    if ($stmt->execute()) {
        
        /* UPDATE FACULTY SUBJECTS */
        // Delete existing subjects
        $deleteSubjects = $conn->prepare("DELETE FROM faculty_subjects WHERE faculty_id=?");
        $deleteSubjects->bind_param("i", $id);
        $deleteSubjects->execute();
        $deleteSubjects->close();

        // Insert new subjects
        if (!empty($subjects) && is_array($subjects)) {
            $subjectStmt = $conn->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id) VALUES (?, ?)");
            
            foreach ($subjects as $subject_id) {
                $subjectStmt->bind_param("ii", $id, $subject_id);
                $subjectStmt->execute();
            }
            $subjectStmt->close();
        }

        echo json_encode([
            "success" => true,
            "message" => "Faculty updated successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => $stmt->error
        ]);
    }

    $stmt->close();
}

/* =========================
   DELETE FACULTY
========================= */
elseif ($action === "delete") {

    $id = intval($data['id'] ?? 0);

    if (empty($id)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM add_faculty WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Faculty deleted successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => $stmt->error
        ]);
    }

    $stmt->close();
}

$conn->close();
?>
