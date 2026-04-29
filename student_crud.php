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

    // Validate required fields
    if (
        empty($student_number) || empty($email) ||
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

    /* CHECK DUPLICATES (student number) */
    $check = $conn->prepare("SELECT id FROM add_students WHERE student_number=?");
    $check->bind_param("s", $student_number);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Student number already exists"
        ]);
        exit;
    }
    $check->close();

    /* CHECK DUPLICATES (email) */
    $checkEmail = $conn->prepare("SELECT id FROM add_students WHERE email=?");
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

    /* INSERT STUDENT */
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
        
        // Insert student subjects if any
        if (!empty($subjects) && is_array($subjects)) {
            $subjectStmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
            foreach ($subjects as $subject_id) {
                $subjectStmt->bind_param("ii", $student_id, $subject_id);
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
    $mail->Subject = "Your Student Account Credentials";
            $mail->Body = "
                <h2>Welcome to Faculty Evaluation System</h2>
                <p>Hello <b>$firstname</b>,</p>

                <p>Your student account has been created successfully.</p>

                <p><b>Student Number:</b> $student_number</p>
                <p><b>Temporary Password:</b> $password</p>

                <br>
                <p>Please change your password immediately after login.</p>

                <br>
                <p>Regards,<br>Faculty Evaluation System</p>
            ";

            $mail->send();

            echo json_encode([
                "success" => true,
                "message" => "Student added successfully. Email sent!"
            ]);

        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);

            echo json_encode([
                "success" => false,
                "message" => "Student saved but email failed: " . $mail->ErrorInfo
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
   EDIT STUDENT
========================= */
elseif ($action === "edit") {

    $id = intval($data['id'] ?? 0);
    $firstname = trim($data['firstname'] ?? '');
    $lastname = trim($data['lastname'] ?? '');
    $suffix = $data['suffix'] ?? '';
    $yearlevel = $data['yearlevel'] ?? '';
    $program = $data['program'] ?? '';
    $section = $data['section'] ?? '';
    $email = trim($data['email'] ?? '');
    $subjects = $data['subjects'] ?? [];

    if (empty($id)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid ID"
        ]);
        exit;
    }

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
        $yearlevel,
        $program,
        $section,
        $email,
        $id
    );

    if ($stmt->execute()) {
        // Update student subjects if any
        if (!empty($subjects) && is_array($subjects)) {
            // Delete existing subjects for this student
            $deleteSubjects = $conn->prepare("DELETE FROM student_subjects WHERE student_id = ?");
            $deleteSubjects->bind_param("i", $id);
            $deleteSubjects->execute();
            $deleteSubjects->close();
            
            // Insert new subjects
            $subjectStmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
            foreach ($subjects as $subject_id) {
                $subjectStmt->bind_param("ii", $id, $subject_id);
                $subjectStmt->execute();
            }
            $subjectStmt->close();
        }
        
        echo json_encode([
            "success" => true,
            "message" => "Student updated successfully"
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
   DELETE STUDENT
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

    $stmt = $conn->prepare("DELETE FROM add_students WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        error_log("Student deleted successfully, ID: " . $id);
        echo json_encode([
            "success" => true,
            "message" => "Student deleted successfully"
        ]);
    } else {
        error_log("Student delete failed for ID: " . $id . ", Error: " . $stmt->error);
        echo json_encode([
            "success" => false,
            "message" => $stmt->error
        ]);
    }

    $stmt->close();
}

$conn->close();
?>