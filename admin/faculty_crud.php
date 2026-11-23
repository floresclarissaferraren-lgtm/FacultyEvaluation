<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
header("Content-Type: application/json");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/mailer.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";

$data = json_decode(file_get_contents("php://input"), true);
$action = $data["action"] ?? "";

ensureColumnExists(
    $conn,
    "add_faculties",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
);
ensureColumnExists(
    $conn,
    "add_faculties",
    "department",
    "VARCHAR(120) DEFAULT NULL",
    "suffix",
);
ensureColumnExists(
    $conn,
    "add_faculties",
    "is_department_head",
    "TINYINT(1) NOT NULL DEFAULT 0",
    "department",
);

/* =========================
   ASSIGN EXISTING FACULTY AS DEPARTMENT HEAD
========================= */
if (
    $action === "assign_department_head" ||
    $action === "update_department_head"
) {
    $isUpdate = $action === "update_department_head";
    $facultyRecordId = intval($data["faculty_record_id"] ?? 0);
    $departmentId = intval($data["department_id"] ?? 0);

    if ($facultyRecordId < 1 || $departmentId < 1) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Select a valid faculty member and department.",
        ]);
        exit();
    }

    require_once __DIR__ . "/../shared/department_schema.php";
    ensureDepartmentSchema($conn);

    $departmentStmt = $conn->prepare(
        "SELECT department_name FROM add_departments WHERE id = ? LIMIT 1",
    );
    if (!$departmentStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to validate the selected department.",
        ]);
        exit();
    }
    $departmentStmt->bind_param("i", $departmentId);
    if (!$departmentStmt->execute()) {
        $departmentStmt->close();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to validate the selected department.",
        ]);
        exit();
    }
    $departmentResult = $departmentStmt->get_result();
    if (!$departmentResult) {
        $departmentStmt->close();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to validate the selected department.",
        ]);
        exit();
    }
    $department = $departmentResult->fetch_assoc();
    $departmentStmt->close();

    if (!$department) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "The selected department was not found.",
        ]);
        exit();
    }

    $assignStmt = $conn->prepare("
        UPDATE add_faculties
        SET department = ?, is_department_head = 1
        WHERE id = ?
          AND COALESCE(is_department_head, 0) = ?
          AND LOWER(TRIM(COALESCE(status, 'active'))) <> 'archived'
    ");
    if (!$assignStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to assign the selected faculty member.",
        ]);
        exit();
    }
    $departmentName = (string) $department["department_name"];
    $expectedHeadStatus = $isUpdate ? 1 : 0;
    $assignStmt->bind_param(
        "sii",
        $departmentName,
        $facultyRecordId,
        $expectedHeadStatus,
    );
    if (!$assignStmt->execute()) {
        $assignStmt->close();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to assign the selected faculty member.",
        ]);
        exit();
    }
    $assigned = $assignStmt->affected_rows === 1;
    $assignStmt->close();

    if (!$assigned && $isUpdate) {
        $verifyStmt = $conn->prepare("
            SELECT id FROM add_faculties
            WHERE id = ?
              AND COALESCE(is_department_head, 0) = 1
              AND LOWER(TRIM(COALESCE(status, 'active'))) <> 'archived'
            LIMIT 1
        ");
        if (!$verifyStmt) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Unable to verify the department head record.",
            ]);
            exit();
        }
        $verifyStmt->bind_param("i", $facultyRecordId);
        if (!$verifyStmt->execute()) {
            $verifyStmt->close();
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Unable to verify the department head record.",
            ]);
            exit();
        }
        $verifyResult = $verifyStmt->get_result();
        $assigned = $verifyResult && $verifyResult->num_rows === 1;
        $verifyStmt->close();
    }

    if (!$assigned) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" =>
                $isUpdate
                    ? "The department head is archived or no longer available."
                    : "The faculty member is already a department head, archived, or no longer available.",
        ]);
        exit();
    }

    echo json_encode([
        "success" => true,
        "message" => $isUpdate
            ? "Department head updated successfully."
            : "Department head assigned successfully.",
    ]);
} elseif ($action === "remove_department_head") {
    $facultyRecordId = intval($data["id"] ?? 0);
    if ($facultyRecordId < 1) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Select a valid department head.",
        ]);
        exit();
    }

    $removeStmt = $conn->prepare("
        UPDATE add_faculties
        SET is_department_head = 0
        WHERE id = ? AND COALESCE(is_department_head, 0) = 1
    ");
    if (!$removeStmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to remove the department head assignment.",
        ]);
        exit();
    }
    $removeStmt->bind_param("i", $facultyRecordId);
    if (!$removeStmt->execute()) {
        $removeStmt->close();
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to remove the department head assignment.",
        ]);
        exit();
    }
    $removed = $removeStmt->affected_rows === 1;
    $removeStmt->close();

    if (!$removed) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "The department head is no longer available.",
        ]);
        exit();
    }
    echo json_encode([
        "success" => true,
        "message" => "Department head assignment removed successfully.",
    ]);
} /* =========================
   ADD NEW FACULTY
========================= */
elseif ($action === "add") {
    $faculty_id = trim($data["faculty_id"] ?? "");
    $email = trim($data["email"] ?? "");
    $firstname = trim($data["firstname"] ?? "");
    $lastname = trim($data["lastname"] ?? "");
    $suffix = trim($data["suffix"] ?? "");
    $department = trim($data["department"] ?? "");
    $is_department_head = !empty($data["is_department_head"]) ? 1 : 0;
    $photo = $data["photo"] ?? "";
    $subjects = $data["subjects"] ?? [];

    // Generate random password
    $password = generateTemporaryPassword();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle photo - if base64 string, store as is
    if (!empty($photo) && strpos($photo, "data:image") === 0) {
        // Photo is base64 encoded, store as is
        error_log("Photo is base64 encoded, length: " . strlen($photo));
    } else {
        // Empty or invalid photo
        $photo = "";
    }

    if (!$faculty_id || !$email || !$firstname || !$lastname || !$department) {
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields",
        ]);
        exit();
    }

    if (!preg_match('/^FC-[0-9]{6}$/', $faculty_id)) {
        echo json_encode([
            "success" => false,
            "message" =>
                "Faculty ID must be FC- followed by exactly 6 digits (example: FC-123456).",
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email"]);
        exit();
    }

    /* duplicate check */
    $check = $conn->prepare(
        "SELECT id FROM add_faculties WHERE faculty_id=? OR email=?",
    );
    $check->bind_param("ss", $faculty_id, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Faculty already exists",
        ]);
        exit();
    }
    $check->close();

    $conn->begin_transaction();

    try {
        // Check if password column exists
        $result = $conn->query(
            "SHOW COLUMNS FROM add_faculties LIKE 'password'",
        );
        $hasPasswordColumn = $result->num_rows > 0;

        if ($hasPasswordColumn) {
            // Insert with password
            $stmt = $conn->prepare("
                INSERT INTO add_faculties
                (faculty_id,email,firstname,lastname,suffix,department,is_department_head,photo,password)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "ssssssiss",
                $faculty_id,
                $email,
                $firstname,
                $lastname,
                $suffix,
                $department,
                $is_department_head,
                $photo,
                $hashed_password,
            );
        } else {
            // Insert without password (fallback)
            $stmt = $conn->prepare("
                INSERT INTO add_faculties
                (faculty_id,email,firstname,lastname,suffix,department,is_department_head,photo)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "ssssssis",
                $faculty_id,
                $email,
                $firstname,
                $lastname,
                $suffix,
                $department,
                $is_department_head,
                $photo,
            );
        }

        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) {
                throw new RuntimeException("Faculty already exists");
            }
            throw new RuntimeException(
                "Unable to save faculty profile: " . $stmt->error,
            );
        }
        $faculty_db_id = $stmt->insert_id;
        $stmt->close();

        // create login credentials for faculty
        $faculty_username = $faculty_id;
        $loginStmt = $conn->prepare(
            "INSERT INTO faculty_login (faculty_id, faculty_username, faculty_password) VALUES (?, ?, ?)",
        );
        if (!$loginStmt) {
            throw new RuntimeException(
                "Unable to prepare faculty login credentials: " . $conn->error,
            );
        }
        $loginStmt->bind_param(
            "iss",
            $faculty_db_id,
            $faculty_username,
            $hashed_password,
        );
        if (!$loginStmt->execute()) {
            throw new RuntimeException(
                "Unable to save faculty login credentials: " .
                    $loginStmt->error,
            );
        }
        $loginStmt->close();

        // Confirm that the exact username and password hash were saved before committing.
        $verifyLogin = $conn->prepare(
            "SELECT faculty_password FROM faculty_login WHERE faculty_id = ? AND faculty_username = ? LIMIT 1",
        );
        if (!$verifyLogin) {
            throw new RuntimeException(
                "Unable to verify faculty login credentials: " . $conn->error,
            );
        }
        $verifyLogin->bind_param("is", $faculty_db_id, $faculty_username);
        if (!$verifyLogin->execute()) {
            throw new RuntimeException(
                "Unable to verify faculty login credentials: " .
                    $verifyLogin->error,
            );
        }
        $verifyResult = $verifyLogin->get_result();
        $verifyRow = $verifyResult ? $verifyResult->fetch_assoc() : null;
        $verifyLogin->close();

        if (
            !$verifyRow ||
            !password_verify($password, $verifyRow["faculty_password"])
        ) {
            throw new RuntimeException(
                "Faculty login credentials could not be verified",
            );
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
        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage(),
        ]);
        exit();
    }

    /* ================= EMAIL WITH PASSWORD ================= */
    $protocol =
        !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
            ? "https"
            : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";

    $title = "Welcome to Faculty Evaluation System";
    $greeting =
        "Hello " .
        ($is_department_head ? "Department Head " : "Instructor ") .
        htmlspecialchars($firstname . " " . $lastname, ENT_QUOTES, "UTF-8") .
        ",";
    $content_html =
        "
        <p style='margin: 0 0 16px 0;'>Your " .
        ($is_department_head ? "department head" : "faculty instructor") .
        " account has been successfully created. You can now access the Faculty Evaluation System using your credentials below.</p>
        <p style='margin: 0 0 16px 0;'><strong>Faculty Number / Username:</strong> " .
        htmlspecialchars($faculty_id, ENT_QUOTES, "UTF-8") .
        "</p>
        <p style='margin: 0 0 16px 0;'>For security reasons, we strongly recommend that you change your password immediately after logging in for the first time.</p>
    ";

    $highlight_box = [
        "label" => "Temporary Password",
        "value" => $password,
        "subtext" => "Keep this password secure",
    ];

    $cta = [
        "label" => "Log In to Dashboard",
        "url" => $baseUrl . "/EvalMain.php",
    ];

    $body = getEmailHTML(
        $title,
        $greeting,
        $content_html,
        $highlight_box,
        $cta,
    );

    $emailToken = "";
    try {
        $emailToken = queueSessionEmail(
            $email,
            "$firstname $lastname",
            "Welcome to Faculty Evaluation System",
            $body,
        );
    } catch (Throwable $mailQueueError) {
        error_log(
            "Faculty welcome email could not be queued: " .
                $mailQueueError->getMessage(),
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Faculty added successfully. Welcome email is being sent.",
        "email_token" => $emailToken,
    ]);
} /* =========================
   EDIT FACULTY
========================= */ elseif (
    $action === "edit"
) {
    $id = intval($data["id"] ?? 0);

    if (!$id) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit();
    }

    $faculty_id = trim((string) ($data["faculty_id"] ?? ""));
    $email = $data["email"];
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $suffix = $data["suffix"];
    $department = trim((string) ($data["department"] ?? ""));
    $photo = $data["photo"];
    $subjects = $data["subjects"] ?? [];

    if (!preg_match('/^FC-[0-9]{6}$/', $faculty_id)) {
        echo json_encode([
            "success" => false,
            "message" =>
                "Faculty ID must be FC- followed by exactly 6 digits (example: FC-123456).",
        ]);
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
        UPDATE add_faculties
        SET faculty_id=?, email=?, firstname=?, lastname=?, suffix=?, department=?, photo=?
        WHERE id=?
    ");

        $stmt->bind_param(
            "sssssssi",
            $faculty_id,
            $email,
            $firstname,
            $lastname,
            $suffix,
            $department,
            $photo,
            $id,
        );

        if ($stmt->execute()) {
            $deleteSubjects = $conn->prepare(
                "DELETE FROM faculty_subjects WHERE faculty_id = ?",
            );
            $deleteSubjects->bind_param("i", $id);
            $deleteSubjects->execute();
            $deleteSubjects->close();

            if (!empty($subjects)) {
                $sub = $conn->prepare("
                INSERT INTO faculty_subjects (faculty_id,subject_id)
                VALUES (?,?)
            ");

                foreach ($subjects as $sid) {
                    $sid = intval($sid);
                    $sub->bind_param("ii", $id, $sid);
                    if (!$sub->execute()) {
                        throw new RuntimeException(
                            "Unable to save faculty subject assignments.",
                        );
                    }
                }

                $sub->close();
            }

            $conn->commit();
            echo json_encode([
                "success" => true,
                "message" => "Faculty updated",
            ]);
        } else {
            throw new RuntimeException("Unable to update faculty profile.");
        }

        $stmt->close();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Faculty update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to update faculty. Please try again.",
        ]);
    }
} /* =========================
   UPDATE FACULTY STATUS
========================= */ elseif (
    $action === "update_status"
) {
    $id = intval($data["id"] ?? 0);
    $status = $data["status"] ?? "";

    $allowedStatuses = ["active", "inactive", "on leave", "archived"];
    if (!$id || !in_array(strtolower(trim($status)), $allowedStatuses, true)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid ID or status",
        ]);
        exit();
    }
    $status = strtolower(trim($status));

    $stmt = $conn->prepare("UPDATE add_faculties SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Faculty status updated successfully",
        ]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
} /* =========================
   DELETE FACULTY
========================= */ elseif (
    $action === "delete"
) {
    $id = intval($data["id"] ?? 0);

    if (!$id) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit();
    }

    $conn->begin_transaction();
    try {
        $deleteSubjects = $conn->prepare(
            "DELETE FROM faculty_subjects WHERE faculty_id = ?",
        );
        $deleteSubjects->bind_param("i", $id);
        $deleteSubjects->execute();
        $deleteSubjects->close();

        $deleteFaculty = $conn->prepare(
            "DELETE FROM add_faculties WHERE id = ?",
        );
        $deleteFaculty->bind_param("i", $id);
        $deleteFaculty->execute();
        $deleted = $deleteFaculty->affected_rows;
        $deleteFaculty->close();
        if ($deleted === 0) {
            throw new RuntimeException("Faculty record not found.");
        }
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Faculty deleted"]);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Faculty delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to delete faculty. Please try again.",
        ]);
    }
}

$conn->close();
?>
