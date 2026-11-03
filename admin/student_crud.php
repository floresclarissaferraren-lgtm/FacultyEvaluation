<?php
require_once __DIR__ . "/../shared/security.php";
requireRole("admin");
requireMethod("POST");
header("Content-Type: application/json");
include __DIR__ . "/../shared/connect.php";
require_once __DIR__ . "/../shared/mailer.php";
require_once __DIR__ . "/../shared/ensure_schema_column.php";
require_once __DIR__ . "/../shared/class_period_helper.php";

$data = json_decode(file_get_contents("php://input"), true);
$action = $data["action"] ?? "";
$action = $action !== "" ? $action : ($_POST["action"] ?? "");

ensureColumnExists(
    $conn,
    "add_students",
    "student_type",
    'VARCHAR(20) DEFAULT "regular"',
    "section",
);
ensureColumnExists(
    $conn,
    "add_students",
    "status",
    'VARCHAR(20) NOT NULL DEFAULT "active"',
    "student_type",
);

function resolveProgramId(mysqli $conn, string $program): int
{
    $program = trim($program);
    if ($program === "") {
        return 0;
    }
    if (ctype_digit($program)) {
        return intval($program);
    }

    $stmt = $conn->prepare(
        "SELECT id FROM add_programs WHERE program_name = ? OR program_code = ? LIMIT 1",
    );
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("ss", $program, $program);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? intval($row["id"]) : 0;
}

function assignStudentSubjects(
    mysqli $conn,
    int $student_id,
    string $program,
    string $yearlevel,
    string $section,
    array $subjects,
    bool $is_regular,
): array {
    ensureStudentSubjectClassesSchema($conn);

    $program_id = resolveProgramId($conn, $program);
    if ($program_id <= 0) {
        return [
            "inserted" => 0,
            "message" => "Invalid program for subject assignment",
        ];
    }

    if (!$is_regular && !empty($subjects)) {
        $sub = $conn->prepare(
            "INSERT IGNORE INTO student_subjects (student_id,subject_id) VALUES (?,?)",
        );
        $ssc = $conn->prepare(
            "INSERT IGNORE INTO student_subject_classes (student_id, subject_id, class_id) VALUES (?, ?, ?)",
        );

        $inserted = 0;
        foreach ($subjects as $entry) {
            $sid = is_array($entry)
                ? intval($entry["id"] ?? 0)
                : intval($entry);
            $class_id = is_array($entry) ? intval($entry["class_id"] ?? 0) : 0;
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
        return [
            "inserted" => $inserted,
            "message" =>
                $inserted > 0 ? "" : "No irregular subjects were inserted",
        ];
    }

    $normalized_year = trim($yearlevel);
    $formatted_year = $normalized_year;
    if (ctype_digit($normalized_year)) {
        $n = intval($normalized_year);
        $suffix =
            $n === 1 ? "st" : ($n === 2 ? "nd" : ($n === 3 ? "rd" : "th"));
        $formatted_year = $n . $suffix . " Year";
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
    $class_subjects->bind_param(
        "isss",
        $program_id,
        $normalized_year,
        $formatted_year,
        $section,
    );
    $class_subjects->execute();
    $class_result = $class_subjects->get_result();

    $subject_assignments = [];
    while ($row = $class_result->fetch_assoc()) {
        $subject_assignments[] = [
            "subject_id" => intval($row["subject_id"]),
            "class_id" => intval($row["class_id"]),
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
        $auto_subjects->bind_param(
            "iss",
            $program_id,
            $normalized_year,
            $formatted_year,
        );
        $auto_subjects->execute();
        $auto_result = $auto_subjects->get_result();

        while ($subject_row = $auto_result->fetch_assoc()) {
            $subject_assignments[] = [
                "subject_id" => intval($subject_row["id"]),
                "class_id" => 0,
            ];
        }
        $auto_subjects->close();
    }

    if (!empty($subject_assignments)) {
        $sub = $conn->prepare(
            "INSERT IGNORE INTO student_subjects (student_id,subject_id) VALUES (?,?)",
        );
        $ssc = $conn->prepare(
            "INSERT IGNORE INTO student_subject_classes (student_id, subject_id, class_id) VALUES (?, ?, ?)",
        );
        $inserted = 0;
        foreach ($subject_assignments as $assignment) {
            $subject_id = intval($assignment["subject_id"]);
            $class_id = intval($assignment["class_id"]);
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
        if ($ssc) {
            $ssc->close();
        }
        return [
            "inserted" => $inserted,
            "message" =>
                $inserted > 0 ? "" : "No regular subjects were inserted",
        ];
    }
    return [
        "inserted" => 0,
        "message" =>
            "No matching subjects found for the selected program/year/section",
    ];
}

/* =========================
   ADD NEW STUDENT
========================= */
if ($action === "bulk_import") {
    $file = $_FILES["student_csv"] ?? null;
    if (!$file || !isset($file["error"]) || $file["error"] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Select a CSV file to import.",
        ]);
        exit();
    }

    if (
        strtolower(pathinfo($file["name"] ?? "", PATHINFO_EXTENSION)) !==
        "csv"
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Only CSV files are supported.",
        ]);
        exit();
    }

    if (($file["size"] ?? 0) > 5 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode([
            "success" => false,
            "message" => "The CSV file must be 5 MB or smaller.",
        ]);
        exit();
    }

    $handle = fopen($file["tmp_name"], "r");
    if (!$handle) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Unable to read the uploaded CSV file.",
        ]);
        exit();
    }

    $headers = fgetcsv($handle, 0, ",", '"', "\\");
    if (!is_array($headers) || empty($headers)) {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "The CSV file must include a header row.",
        ]);
        exit();
    }

    $headerAliases = [
        "student_id" => "student_number",
        "first_name" => "firstname",
        "last_name" => "lastname",
        "year_level" => "yearlevel",
        "program_id" => "program",
    ];
    $headerIndexes = [];
    foreach ($headers as $index => $header) {
        $normalizedHeader = strtolower(trim((string) $header));
        if ($index === 0) {
            $normalizedHeader = preg_replace(
                '/^\xEF\xBB\xBF/',
                "",
                $normalizedHeader,
            );
        }
        $normalizedHeader = preg_replace(
            '/[\s-]+/',
            "_",
            $normalizedHeader,
        );
        $normalizedHeader =
            $headerAliases[$normalizedHeader] ?? $normalizedHeader;
        if (isset($headerIndexes[$normalizedHeader])) {
            fclose($handle);
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "The CSV file contains duplicate column headers.",
            ]);
            exit();
        }
        $headerIndexes[$normalizedHeader] = $index;
    }

    $requiredHeaders = ["student_number"];
    $missingHeaders = array_values(
        array_diff($requiredHeaders, array_keys($headerIndexes)),
    );
    if (!empty($missingHeaders)) {
        fclose($handle);
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" =>
                "Missing required CSV columns: " .
                implode(", ", $missingHeaders) .
                ".",
        ]);
        exit();
    }

    $insert = $conn->prepare("
        INSERT INTO add_students
        (student_number,email,firstname,lastname,suffix,yearlevel,program,section,student_type,status,password)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");
    $duplicateCheck = $conn->prepare("
        SELECT student_number, email
        FROM add_students
        WHERE student_number = ? OR email = ?
        LIMIT 1
    ");
    $studentIdCheck = $conn->prepare("
        SELECT id, student_number, program, yearlevel, section, student_type
        FROM add_students
        WHERE student_number = ?
        LIMIT 1
    ");
    $studentUpdate = $conn->prepare("
        UPDATE add_students
        SET student_type = ?, yearlevel = ?, section = ?
        WHERE id = ?
    ");
    if (!$insert || !$duplicateCheck || !$studentIdCheck || !$studentUpdate) {
        fclose($handle);
        http_response_code(500);
        error_log("Bulk student import: unable to prepare database statements.");
        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare the student import.",
        ]);
        exit();
    }

    $evaluationOpen = false;
    $evaluationAcademicYear = "";
    $evaluationSemester = "";
    try {
        $evaluationStatusResult = $conn->query(
            "SELECT es.evaluation_open, ep.start_date, ep.end_date, ep.ay, ep.semester
             FROM evaluation_settings es
             LEFT JOIN evaluation_periods ep ON ep.id = es.active_period_id
             WHERE es.id = 1
             LIMIT 1",
        );
        $evaluationStatus = $evaluationStatusResult
            ? $evaluationStatusResult->fetch_assoc()
            : null;
        if ($evaluationStatus) {
            $today = (new DateTimeImmutable(
                "now",
                new DateTimeZone("Asia/Manila"),
            ))->format("Y-m-d");
            $evaluationOpen =
                intval($evaluationStatus["evaluation_open"] ?? 0) === 1 &&
                !empty($evaluationStatus["start_date"]) &&
                !empty($evaluationStatus["end_date"]) &&
                $evaluationStatus["start_date"] <= $today &&
                $evaluationStatus["end_date"] >= $today;
            $evaluationAcademicYear = (string) ($evaluationStatus["ay"] ?? "");
            $evaluationSemester = (string) ($evaluationStatus["semester"] ?? "");
        }
        if ($evaluationStatusResult) {
            $evaluationStatusResult->free();
        }
    } catch (Throwable $e) {
        error_log("Bulk student import: unable to read evaluation status: " . $e->getMessage());
    }

    $summary = [
        "success" => true,
        "imported" => 0,
        "updated" => 0,
        "skipped" => 0,
        "failed" => 0,
        "email_sent" => 0,
        "email_failed" => 0,
        "evaluation_open" => $evaluationOpen,
        "skipped_records" => [],
        "failed_records" => [],
        "email_warnings" => [],
    ];
    $rowNumber = 1;
    $defaultStatus = "active";
    $seenStudentNumbers = [];

    while (($row = fgetcsv($handle, 0, ",", '"', "\\")) !== false) {
        $rowNumber++;
        if (count($row) === 1 && trim((string) $row[0]) === "") {
            continue;
        }

        $record = [];
        foreach (
            [
                "student_number",
                "email",
                "firstname",
                "lastname",
                "suffix",
                "program",
                "student_type",
                "yearlevel",
                "section",
            ]
            as $field
        ) {
            $record[$field] = isset($headerIndexes[$field])
                ? trim((string) ($row[$headerIndexes[$field]] ?? ""))
                : "";
        }

        $studentNumber = strtoupper($record["student_number"]);
        $email = $record["email"];
        $firstName = $record["firstname"];
        $lastName = $record["lastname"];
        $suffix = $record["suffix"];
        $studentType = strtolower($record["student_type"] ?: "regular");
        $yearLevel = $record["yearlevel"];
        $section = $record["section"];

        if (count($row) !== count($headers)) {
            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "The number of values does not match the CSV columns.",
            ];
            continue;
        }
        if (!preg_match('/^GC-[0-9]{6}$/', $studentNumber)) {
            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Student ID must be GC- followed by exactly 6 digits.",
            ];
            continue;
        }
        if (isset($seenStudentNumbers[$studentNumber])) {
            $summary["skipped"]++;
            $summary["skipped_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Student ID is duplicated in this CSV file.",
            ];
            continue;
        }

        $studentIdCheck->bind_param("s", $studentNumber);
        try {
            $studentIdChecked = $studentIdCheck->execute();
        } catch (Throwable $e) {
            $studentIdChecked = false;
            error_log(
                "Bulk student import existing student lookup failed on CSV row " .
                    $rowNumber .
                    ": " .
                    $e->getMessage(),
            );
        }
        if (!$studentIdChecked) {
            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Unable to check for an existing student account.",
            ];
            continue;
        }
        $existingResult = $studentIdCheck->get_result();
        $existingStudent = $existingResult
            ? $existingResult->fetch_assoc()
            : null;
        if ($existingResult) {
            $existingResult->free();
        }

        if ($existingStudent) {
            // Consume the first row for this ID even if its update later fails,
            // so a repeated ID in the same CSV is never processed twice.
            $seenStudentNumbers[$studentNumber] = true;
            $existingType = strtolower(
                (string) ($existingStudent["student_type"] ?: "regular"),
            );
            $updatedType = isset($headerIndexes["student_type"]) && $record["student_type"] !== ""
                ? strtolower($record["student_type"])
                : $existingType;
            $updatedYearLevel = isset($headerIndexes["yearlevel"]) && $yearLevel !== ""
                ? $yearLevel
                : (string) $existingStudent["yearlevel"];
            $updatedSection = isset($headerIndexes["section"]) && $section !== ""
                ? $section
                : (string) ($existingStudent["section"] ?? "");

            if (!in_array($updatedType, ["regular", "irregular"], true)) {
                $reason = "Student type must be regular or irregular.";
            } elseif ($updatedType === "regular" && !in_array($updatedYearLevel, ["1", "2", "3", "4"], true)) {
                $reason = "Regular students must have a year level from 1 to 4.";
            } elseif ($updatedType === "regular" && trim($updatedSection) === "") {
                $reason = "Regular students must have a section.";
            } else {
                $reason = "";
            }

            if ($reason !== "") {
                $summary["failed"]++;
                $summary["failed_records"][] = [
                    "row" => $rowNumber,
                    "student_number" => $studentNumber,
                    "reason" => $reason,
                ];
                continue;
            }

            $updatedYearLevel = $updatedType === "irregular"
                ? "irregular"
                : trim($updatedYearLevel);
            $updatedSection = $updatedType === "irregular"
                ? ""
                : trim($updatedSection);
            $existingId = (int) $existingStudent["id"];
            $oldYearLevel = (string) $existingStudent["yearlevel"];
            $oldSection = (string) ($existingStudent["section"] ?? "");
            $needsSubjectRefresh =
                $updatedType !== $existingType ||
                ($updatedType === "regular" &&
                    ($updatedYearLevel !== $oldYearLevel ||
                        strcasecmp($updatedSection, $oldSection) !== 0));

            try {
                if ($needsSubjectRefresh) {
                    ensureStudentSubjectClassesSchema($conn);
                }
                $conn->begin_transaction();
                $studentUpdate->bind_param(
                    "sssi",
                    $updatedType,
                    $updatedYearLevel,
                    $updatedSection,
                    $existingId,
                );
                if (!$studentUpdate->execute()) {
                    throw new RuntimeException($studentUpdate->error);
                }

                if ($needsSubjectRefresh) {
                    $deleteSubjectClasses = $conn->prepare(
                        "DELETE FROM student_subject_classes WHERE student_id = ?",
                    );
                    $deleteSubjects = $conn->prepare(
                        "DELETE FROM student_subjects WHERE student_id = ?",
                    );
                    if (!$deleteSubjectClasses || !$deleteSubjects) {
                        throw new RuntimeException("Unable to refresh student subject assignments.");
                    }
                    $deleteSubjectClasses->bind_param("i", $existingId);
                    $deleteSubjectClasses->execute();
                    $deleteSubjects->bind_param("i", $existingId);
                    $deleteSubjects->execute();
                    $deleteSubjectClasses->close();
                    $deleteSubjects->close();

                    if ($updatedType === "regular") {
                        assignStudentSubjects(
                            $conn,
                            $existingId,
                            (string) $existingStudent["program"],
                            $updatedYearLevel,
                            $updatedSection,
                            [],
                            true,
                        );
                    }
                }
                $conn->commit();
                $summary["updated"]++;
            } catch (Throwable $e) {
                $conn->rollback();
                $summary["failed"]++;
                $summary["failed_records"][] = [
                    "row" => $rowNumber,
                    "student_number" => $studentNumber,
                    "reason" => "Unable to update this student account.",
                ];
                error_log(
                    "Bulk student update failed on CSV row " .
                        $rowNumber .
                        ": " .
                        $e->getMessage(),
                );
            }
            continue;
        }

        try {
            $programId = resolveProgramId($conn, $record["program"]);
        } catch (Throwable $e) {
            $programId = 0;
            error_log(
                "Bulk student import program lookup failed on CSV row " .
                    $rowNumber .
                    ": " .
                    $e->getMessage(),
            );
        }

        $reason = "";
        if (count($row) !== count($headers)) {
            $reason = "The number of values does not match the CSV columns.";
        } elseif (
            $studentNumber === "" ||
            $email === "" ||
            $firstName === "" ||
            $lastName === "" ||
            $record["program"] === ""
        ) {
            $reason =
                "Student ID, email, first name, last name, and program are required.";
        } elseif (!preg_match('/^GC-[0-9]{6}$/', $studentNumber)) {
            $reason =
                "Student ID must be GC- followed by exactly 6 digits.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reason = "Email address is invalid.";
        } elseif (!in_array($studentType, ["regular", "irregular"], true)) {
            $reason = "Student type must be regular or irregular.";
        } elseif ($programId <= 0) {
            $reason = "Program does not match an existing program.";
        } else {
            $programCheck = $conn->prepare(
                "SELECT id FROM add_programs WHERE id = ? LIMIT 1",
            );
            if (!$programCheck) {
                $reason = "Unable to validate the selected program.";
            } else {
                $programCheck->bind_param("i", $programId);
                try {
                    $programCheck->execute();
                    $programCheck->store_result();
                    if ($programCheck->num_rows === 0) {
                        $reason =
                            "Program does not match an existing program.";
                    }
                } catch (Throwable $e) {
                    $reason = "Unable to validate the selected program.";
                    error_log(
                        "Bulk student import program validation failed on CSV row " .
                            $rowNumber .
                            ": " .
                            $e->getMessage(),
                    );
                }
                $programCheck->close();
            }
        }

        if ($reason === "" && $studentType === "regular") {
            if (!in_array($yearLevel, ["1", "2", "3", "4"], true)) {
                $reason =
                    "Regular students must have a year level from 1 to 4.";
            } elseif ($section === "") {
                $reason = "Regular students must have a section.";
            }
        }

        if ($reason !== "") {
            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => $reason,
            ];
            continue;
        }

        if (isset($seenStudentNumbers[$studentNumber])) {
            $summary["skipped"]++;
            $summary["skipped_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Student ID is duplicated in this CSV file.",
            ];
            continue;
        }
        $seenStudentNumbers[$studentNumber] = true;

        $duplicateCheck->bind_param("ss", $studentNumber, $email);
        try {
            $duplicateChecked = $duplicateCheck->execute();
        } catch (Throwable $e) {
            $duplicateChecked = false;
            error_log(
                "Bulk student import duplicate check failed on CSV row " .
                    $rowNumber .
                    ": " .
                    $e->getMessage(),
            );
        }
        if (!$duplicateChecked) {
            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Unable to check for existing accounts.",
            ];
            error_log(
                "Bulk student import: duplicate check failed on CSV row " .
                    $rowNumber .
                    ": " .
                    $duplicateCheck->error,
            );
            continue;
        }
        $duplicateResult = $duplicateCheck->get_result();
        $existing = $duplicateResult
            ? $duplicateResult->fetch_assoc()
            : null;
        if ($duplicateResult) {
            $duplicateResult->free();
        }
        if ($existing) {
            $matchesStudentNumber =
                strcasecmp(
                    (string) $existing["student_number"],
                    $studentNumber,
                ) === 0;
            $matchesEmail =
                strcasecmp((string) $existing["email"], $email) === 0;
            $duplicateReason =
                $matchesStudentNumber && $matchesEmail
                    ? "Student ID and email already exist."
                    : ($matchesStudentNumber
                        ? "Student ID already exists."
                        : "Email address already exists.");
            $summary["skipped"]++;
            $summary["skipped_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => $duplicateReason,
            ];
            continue;
        }

        $yearLevelValue = $studentType === "irregular" ? "irregular" : $yearLevel;
        $program = (string) $programId;
        $sectionValue = $studentType === "irregular" ? "" : $section;
        $password = generateTemporaryPassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->begin_transaction();
            $insert->bind_param(
                "sssssssssss",
                $studentNumber,
                $email,
                $firstName,
                $lastName,
                $suffix,
                $yearLevelValue,
                $program,
                $sectionValue,
                $studentType,
                $defaultStatus,
                $hashedPassword,
            );
            if (!$insert->execute()) {
                throw new RuntimeException($insert->error);
            }

            assignStudentSubjects(
                $conn,
                (int) $insert->insert_id,
                $program,
                $yearLevelValue,
                $sectionValue,
                [],
                $studentType === "regular",
            );
            $conn->commit();
            $summary["imported"]++;

            try {
                $protocol =
                    !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
                        ? "https"
                        : "http";
                $host = $_SERVER["HTTP_HOST"] ?? "localhost";
                $baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";
                $safeName = htmlspecialchars(
                    $firstName . " " . $lastName,
                    ENT_QUOTES,
                    "UTF-8",
                );
                $safeStudentNumber = htmlspecialchars(
                    $studentNumber,
                    ENT_QUOTES,
                    "UTF-8",
                );
                $evaluationStatusText = $evaluationOpen ? "OPEN" : "CLOSED";
                $evaluationPeriodName = trim(
                    $evaluationSemester . " " . $evaluationAcademicYear,
                );
                $evaluationPeriodDetails = $evaluationPeriodName !== ""
                    ? " for <strong>" . htmlspecialchars($evaluationPeriodName, ENT_QUOTES, "UTF-8") . "</strong>"
                    : "";
                $emailBody = getEmailHTML(
                    "Welcome to Faculty Evaluation System",
                    "Hello " . $safeName . ",",
                    "<p>Your student account has been successfully created.</p><p><strong>Student Number:</strong> " .
                        $safeStudentNumber .
                        "</p><p>The faculty evaluation period" .
                        $evaluationPeriodDetails .
                        " is currently <strong>" .
                        $evaluationStatusText .
                        "</strong>.</p>",
                    [
                        "label" => "Temporary Password",
                        "value" => $password,
                        "subtext" => "Keep this password secure",
                    ],
                    [
                        "label" => "Log In to System",
                        "url" => $baseUrl . "/EvalMain.php",
                    ],
                );
                $emailSent = sendEmail(
                    $email,
                    $firstName . " " . $lastName,
                    "Welcome to Faculty Evaluation System",
                    $emailBody,
                    null,
                    null,
                    [],
                    true,
                );
            } catch (Throwable $mailError) {
                $emailSent = false;
                error_log(
                    "Bulk student import email failed on CSV row " .
                        $rowNumber .
                        ": " .
                        $mailError->getMessage(),
                );
            }
            if ($emailSent) {
                $summary["email_sent"]++;
            } else {
                $summary["email_failed"]++;
                $summary["email_warnings"][] = [
                    "row" => $rowNumber,
                    "student_number" => $studentNumber,
                    "reason" =>
                        "Account imported, but the password email could not be sent.",
                ];
            }
        } catch (Throwable $e) {
            $insertError = (int) $insert->errno;
            $conn->rollback();
            $isDuplicate =
                $insertError === 1062 ||
                ($e instanceof mysqli_sql_exception &&
                    (int) $e->getCode() === 1062);
            if ($isDuplicate) {
                $summary["skipped"]++;
                $summary["skipped_records"][] = [
                    "row" => $rowNumber,
                    "student_number" => $studentNumber,
                    "reason" =>
                        "Student ID or email already exists.",
                ];
                continue;
            }

            $summary["failed"]++;
            $summary["failed_records"][] = [
                "row" => $rowNumber,
                "student_number" => $studentNumber,
                "reason" => "Unable to save this student account.",
            ];
            error_log(
                "Bulk student import failed on CSV row " .
                    $rowNumber .
                    ": " .
                    $e->getMessage(),
            );
        }
    }

    fclose($handle);
    $insert->close();
    $duplicateCheck->close();
    $studentIdCheck->close();
    $studentUpdate->close();
    echo json_encode($summary);
    exit();
} elseif ($action === "add") {
    $student_number = trim($data["student_number"] ?? "");
    $email = trim($data["email"] ?? "");
    $firstname = trim($data["firstname"] ?? "");
    $lastname = trim($data["lastname"] ?? "");
    $suffix = $data["suffix"] ?? "";
    $yearlevel = $data["yearlevel"] ?? "";
    $program = $data["program"] ?? "";
    $section = $data["section"] ?? "";
    $subjects = $data["subjects"] ?? [];
    $student_type = $data["student_type"] ?? "regular";

    if (!$student_number || !$email || !$firstname || !$lastname) {
        echo json_encode(["success" => false, "message" => "Missing fields"]);
        exit();
    }

    if (!preg_match('/^GC-[0-9]{6}$/', $student_number)) {
        echo json_encode([
            "success" => false,
            "message" =>
                "Student ID must be GC- followed by exactly 6 digits (example: GC-123456).",
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email"]);
        exit();
    }

    /* duplicate student */
    $check = $conn->prepare(
        "SELECT id FROM add_students WHERE student_number=? OR email=?",
    );
    $check->bind_param("ss", $student_number, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Student already exists",
        ]);
        exit();
    }
    $check->close();

    $password = generateTemporaryPassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $defaultStatus = "active";

    $yearlevelValue = $student_type === "irregular" ? "irregular" : $yearlevel;
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
        $hashedPassword,
    );

    $conn->begin_transaction();
    try {
        if ($stmt->execute()) {
            $student_id = $stmt->insert_id;

            $assignResult = assignStudentSubjects(
                $conn,
                $student_id,
                $program,
                $yearlevelValue,
                $section,
                $subjects,
                $student_type !== "irregular",
            );
            $conn->commit();

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
                htmlspecialchars(
                    $firstname . " " . $lastname,
                    ENT_QUOTES,
                    "UTF-8",
                ) .
                ",";
            $content_html =
                "
            <p style='margin: 0 0 16px 0;'>Your student account has been successfully created. You can now access the Faculty Evaluation System using your credentials below.</p>
            <p style='margin: 0 0 16px 0;'><strong>Student Number:</strong> " .
                htmlspecialchars($student_number, ENT_QUOTES, "UTF-8") .
                "</p>
            <p style='margin: 0 0 16px 0;'>For security reasons, we strongly recommend that you change your password immediately after logging in for the first time.</p>
        ";

            $highlight_box = [
                "label" => "Temporary Password",
                "value" => $password,
                "subtext" => "Keep this password secure",
            ];

            $cta = [
                "label" => "Log In to System",
                "url" => $baseUrl . "/EvalMain.php",
            ];

            $body = getEmailHTML(
                $title,
                $greeting,
                $content_html,
                $highlight_box,
                $cta,
            );

            $sent = sendEmail(
                $email,
                "$firstname $lastname",
                "Welcome to Faculty Evaluation System",
                $body,
            );

            echo json_encode([
                "success" => true,
                "message" => $sent
                    ? "Student added and temporary password sent by email."
                    : "Student added, but the password email could not be sent. Ask the student to use Forgot Password.",
                "subject_inserted" => $assignResult["inserted"] ?? 0,
                "subject_message" => $assignResult["message"] ?? "",
            ]);
        } else {
            $conn->rollback();
            echo json_encode([
                "success" => false,
                "message" => "Unable to create student account.",
            ]);
        }
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Student creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to create student account. Please try again.",
        ]);
    }

    $stmt->close();
} /* =========================
   EDIT STUDENT
========================= */ elseif (
    $action === "edit"
) {
    $id = intval($data["id"] ?? 0);

    if (!$id) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit();
    }

    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $suffix = $data["suffix"];
    $yearlevel = $data["yearlevel"];
    $program = $data["program"];
    $section = $data["section"];
    $email = $data["email"];
    $student_type = $data["student_type"] ?? "regular";
    $subjects = $data["subjects"] ?? [];

    // Set the yearlevel value based on student type
    $yearlevelValue = $student_type === "irregular" ? "irregular" : $yearlevel;

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
        $id,
    );

    $conn->begin_transaction();
    try {
        if ($stmt->execute()) {
            $deleteSubjects = $conn->prepare(
                "DELETE FROM student_subjects WHERE student_id = ?",
            );
            $deleteSubjects->bind_param("i", $id);
            $deleteSubjects->execute();
            $deleteSubjects->close();
            $deleteClasses = $conn->prepare(
                "DELETE FROM student_subject_classes WHERE student_id = ?",
            );
            $deleteClasses->bind_param("i", $id);
            $deleteClasses->execute();
            $deleteClasses->close();

            $assignResult = assignStudentSubjects(
                $conn,
                $id,
                $program,
                $yearlevelValue,
                $section,
                $subjects,
                $student_type !== "irregular",
            );
            $conn->commit();

            echo json_encode([
                "success" => true,
                "message" => "Student updated",
                "subject_inserted" => $assignResult["inserted"] ?? 0,
                "subject_message" => $assignResult["message"] ?? "",
            ]);
        } else {
            $conn->rollback();
            echo json_encode([
                "success" => false,
                "message" => "Unable to update student account.",
            ]);
        }
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Student update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to update student. Please try again.",
        ]);
    }

    $stmt->close();
} /* =========================
   DELETE STUDENT
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
            "DELETE FROM student_subjects WHERE student_id = ?",
        );
        $deleteSubjects->bind_param("i", $id);
        $deleteSubjects->execute();
        $deleteSubjects->close();
        $deleteClasses = $conn->prepare(
            "DELETE FROM student_subject_classes WHERE student_id = ?",
        );
        $deleteClasses->bind_param("i", $id);
        $deleteClasses->execute();
        $deleteClasses->close();
        $deleteStudent = $conn->prepare(
            "DELETE FROM add_students WHERE id = ?",
        );
        $deleteStudent->bind_param("i", $id);
        $deleteStudent->execute();
        $deleted = $deleteStudent->affected_rows;
        $deleteStudent->close();
        if ($deleted === 0) {
            throw new RuntimeException("Student record not found.");
        }
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Student deleted"]);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("Student delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to delete student. Please try again.",
        ]);
    }
} /* =========================
   UPDATE STUDENT STATUS
========================= */ elseif (
    $action === "update_status"
) {
    $id = intval($data["id"] ?? 0);
    $status = strtolower(trim($data["status"] ?? ""));

    if (!$id) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit();
    }

    if (!in_array($status, ["active", "inactive", "archived"], true)) {
        echo json_encode(["success" => false, "message" => "Invalid status"]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE add_students SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Student status updated successfully",
        ]);
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
