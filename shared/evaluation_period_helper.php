<?php
/**
 * evaluation_period_helper.php
 *
 * Provides shared evaluation period checking, status queries,
 * automatic period closing, PDF report generation, and notifications.
 */

require_once __DIR__ . "/connect.php";
require_once __DIR__ . "/mailer.php";
require_once __DIR__ . "/weighted_score_helper.php";

if (!defined("FPDF_FONTPATH")) {
    define(
        "FPDF_FONTPATH",
        dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "vendor" .
            DIRECTORY_SEPARATOR .
            "fpdf" .
            DIRECTORY_SEPARATOR .
            "font" .
            DIRECTORY_SEPARATOR,
    );
}
require_once __DIR__ . "/../vendor/fpdf/fpdf.php";

date_default_timezone_set("Asia/Manila");

/**
 * Ensure all necessary tables exist in the database.
 */
function ensureEvaluationPeriodTables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS evaluation_periods (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ay VARCHAR(20) NOT NULL,
            semester VARCHAR(20) NOT NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS evaluation_settings (
            id INT(11) NOT NULL PRIMARY KEY,
            evaluation_open TINYINT(1) NOT NULL DEFAULT 0,
            active_period_id INT(11) NULL,
            selected_ay VARCHAR(20) NULL,
            selected_semester VARCHAR(20) NULL,
            display_ay VARCHAR(20) NULL,
            display_semester VARCHAR(20) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_active_period (active_period_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS faculty_uploaded_reports (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            faculty_id INT(11) NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            semester VARCHAR(20) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_faculty (faculty_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Returns true if an evaluation is active and currently open.
 */
function isEvaluationOngoing(mysqli $conn): bool
{
    $settingsRes = $conn->query(
        "SELECT evaluation_open, active_period_id FROM evaluation_settings WHERE id = 1 LIMIT 1",
    );
    $settings = $settingsRes ? $settingsRes->fetch_assoc() : null;

    $evaluationOpen = $settings
        ? intval($settings["evaluation_open"]) === 1
        : false;
    $activePeriodId = $settings
        ? intval($settings["active_period_id"] ?? 0)
        : 0;

    if ($evaluationOpen && $activePeriodId > 0) {
        $today = date("Y-m-d");
        $stmt = $conn->prepare(
            "SELECT start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1",
        );
        $stmt->bind_param("i", $activePeriodId);
        $stmt->execute();
        $period = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (
            $period &&
            !empty($period["start_date"]) &&
            !empty($period["end_date"]) &&
            $period["start_date"] <= $today &&
            $period["end_date"] >= $today
        ) {
            return true;
        }
    }
    return false;
}

/**
 * Blocks faculty execution and outputs an error if evaluations are ongoing.
 */
function blockFacultyResultsWhileEvaluationOngoing(mysqli $conn): void
{
    if (isEvaluationOngoing($conn)) {
        echo json_encode([
            "success" => false,
            "message" =>
                "Evaluation results are unavailable while the evaluation process is still ongoing.",
        ]);
        exit();
    }
}

/**
 * Automatically close expired evaluation periods.
 * This runs on page loads and triggers notifications + PDF reports generation.
 */
function closeExpiredEvaluationPeriod(mysqli $conn): void
{
    ensureEvaluationPeriodTables($conn);
    $today = date("Y-m-d");

    // Fetch any active period whose end date has passed
    $stmt = $conn->prepare(
        "SELECT id, ay, semester, end_date FROM evaluation_periods WHERE is_active = 1 AND end_date < ?",
    );
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $expired = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($expired)) {
        return;
    }

    foreach ($expired as $period) {
        $periodId = intval($period["id"]);
        $ay = $period["ay"];
        $semester = $period["semester"];

        $conn->begin_transaction();
        try {
            // Deactivate period
            $conn->query(
                "UPDATE evaluation_periods SET is_active = 0 WHERE id = $periodId",
            );
            // Set settings to closed
            $conn->query(
                "UPDATE evaluation_settings SET evaluation_open = 0, active_period_id = NULL WHERE id = 1",
            );
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            error_log(
                "Error closing evaluation period $periodId: " .
                    $e->getMessage(),
            );
            continue;
        }

        try {
            // Keep the existing student notice and send both faculty PDF reports.
            $result = handleExpiredPeriodClosing(
                $conn,
                $periodId,
                $ay,
                $semester,
                true,
            );
            if (($result["report_emails_failed"] ?? 0) > 0) {
                error_log(
                    "Automatic report delivery for evaluation period $periodId: " .
                        ($result["report_emails_sent"] ?? 0) .
                        " sent, " .
                        $result["report_emails_failed"] .
                        " failed.",
                );
            }
        } catch (Throwable $e) {
            error_log(
                "Evaluation period $periodId closed, but automatic report processing failed: " .
                    $e->getMessage(),
            );
        }
    }
}

/**
 * Sends notifications to students and faculty, generates evaluation PDF results,
 * and uploads them to faculty accounts.
 */
function handleExpiredPeriodClosing(
    mysqli $conn,
    int $periodId,
    string $ay,
    string $semester,
    bool $notifyStudents = true,
): array {
    $reportEmailsSent = 0;
    $reportEmailsFailed = 0;
    $reportEmailErrors = [];

    // 1. Notify Students
    if ($notifyStudents) {
        $studentRes = $conn->query("
            SELECT email, firstname, lastname
            FROM add_students
            WHERE LOWER(status) = 'active' AND email IS NOT NULL AND email != ''
        ");
        if ($studentRes) {
            while ($row = $studentRes->fetch_assoc()) {
                $email = trim($row["email"]);
                $name = trim($row["firstname"] . " " . $row["lastname"]);
                $subject = "Faculty Evaluation Concluded - A.Y. $ay, $semester";
                $title = "Faculty Evaluation Period Concluded";
                $greeting =
                    "Dear " .
                    htmlspecialchars($name, ENT_QUOTES, "UTF-8") .
                    ",";
                $content_html =
                    "
                    <p style='margin: 0 0 16px 0;'>This is to inform you that the faculty evaluation period for <strong>Academic Year " .
                    htmlspecialchars($ay, ENT_QUOTES, "UTF-8") .
                    ", " .
                    htmlspecialchars($semester, ENT_QUOTES, "UTF-8") .
                    "</strong> has closed.</p>
                    <p style='margin: 0 0 16px 0; font-size: 15px; font-weight: bold; color: #dc2626;'>The evaluation process is now complete.</p>
                    <p style='margin: 0 0 16px 0;'>We thank you very much for participating and sharing your valuable feedback, which is key to improving our instruction and college standards.</p>
                ";

                $body = getEmailHTML($title, $greeting, $content_html);
                sendEmail($email, $name, $subject, $body);
            }
        } else {
            error_log(
                "Unable to load students for closing notifications for period $periodId.",
            );
        }
    }

    // 2. Process Faculty Members: Generate & Upload PDF, Send Email
    $facultyRes = $conn->query("
        SELECT id, faculty_id, email, firstname, lastname, suffix 
        FROM add_faculties 
        WHERE LOWER(TRIM(COALESCE(status, 'active'))) = 'active'
    ");

    if ($facultyRes) {
        // Ensure upload directory exists
        $uploadDir =
            __DIR__ .
            DIRECTORY_SEPARATOR .
            "uploads" .
            DIRECTORY_SEPARATOR .
            "evaluation_reports" .
            DIRECTORY_SEPARATOR;
        if (
            !is_dir($uploadDir) &&
            !mkdir($uploadDir, 0750, true) &&
            !is_dir($uploadDir)
        ) {
            error_log(
                "Unable to create evaluation report directory for period $periodId.",
            );
        }

        while ($facultyRow = $facultyRes->fetch_assoc()) {
            $facultyDbId = intval($facultyRow["id"]);
            $facultyStrId = trim($facultyRow["faculty_id"]);
            $facultyEmail = trim($facultyRow["email"]);
            $facultyName = trim(
                $facultyRow["firstname"] .
                    " " .
                    $facultyRow["lastname"] .
                    " " .
                    ($facultyRow["suffix"] ?? ""),
            );

            $reportPath = null;
            $evaluationReportPath = null;
            $fileName = null;
            $evaluationReportFileName = null;
            // a. Fetch per-subject evaluation data for this specific period
            $reportData = helperGetFacultyPerSubjectReportData(
                $conn,
                $facultyDbId,
                $periodId,
                $facultyName,
                $facultyStrId,
            );

            if ($reportData) {
                // b. Generate and save PDF report to local folder
                $safeFacultyName =
                    preg_replace("/[^A-Za-z0-9._-]+/", "_", $facultyName) ?:
                    "Faculty";
                $timestamp = date("Ymd_His");
                $fileName =
                    "Per_Subject_Report_" .
                    $safeFacultyName .
                    "_" .
                    $timestamp .
                    ".pdf";
                $filePath = $uploadDir . $fileName;

                if (
                    is_dir($uploadDir) &&
                    helperGenerateReportPDF($conn, $reportData, $filePath) &&
                    is_file($filePath) &&
                    filesize($filePath) > 0
                ) {
                    $reportPath = $filePath;
                } else {
                    $error = "Unable to generate per-subject evaluation PDF for faculty $facultyDbId, period $periodId.";
                    error_log($error);
                    $reportEmailErrors[] = $error;
                }

                $evaluationReportFileName =
                    "Evaluation_Report_" .
                    $safeFacultyName .
                    "_" .
                    $timestamp .
                    ".pdf";
                $evaluationReportPath = $uploadDir . $evaluationReportFileName;
                if (
                    !is_dir($uploadDir) ||
                    !helperGenerateEvaluationSummaryPDF(
                        $reportData,
                        $evaluationReportPath,
                    ) ||
                    !is_file($evaluationReportPath) ||
                    filesize($evaluationReportPath) <= 0
                ) {
                    $evaluationReportPath = null;
                    $error = "Unable to generate evaluation summary PDF for faculty $facultyDbId, period $periodId.";
                    error_log($error);
                    $reportEmailErrors[] = $error;
                }

                // c. Upload both report references to the instructor portal.
                foreach (
                    [
                        [$fileName, $reportPath],
                        [$evaluationReportFileName, $evaluationReportPath],
                    ]
                    as [$savedFileName, $savedFilePath]
                ) {
                    if ($savedFileName === null || $savedFilePath === null) {
                        continue;
                    }
                    $insStmt = null;
                    try {
                        $insStmt = $conn->prepare("
                            INSERT INTO faculty_uploaded_reports (faculty_id, academic_year, semester, file_name, file_path)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        if (!$insStmt) {
                            throw new Exception(
                                "Unable to prepare report record for faculty $facultyDbId, period $periodId.",
                            );
                        }
                        $insStmt->bind_param(
                            "issss",
                            $facultyDbId,
                            $ay,
                            $semester,
                            $savedFileName,
                            $savedFilePath,
                        );
                        $insStmt->execute();
                    } catch (Exception $e) {
                        error_log($e->getMessage());
                    } finally {
                        if ($insStmt) {
                            $insStmt->close();
                        }
                    }
                }
            } else {
                error_log(
                    "Unable to load evaluation report data for faculty $facultyDbId, period $periodId.",
                );
            }

            // d. Email the generated PDF to the faculty member.
            if (
                $reportPath !== null &&
                $evaluationReportPath !== null &&
                filter_var($facultyEmail, FILTER_VALIDATE_EMAIL)
            ) {
                $subject = "Faculty Evaluation Results Uploaded - A.Y. $ay, $semester";
                $protocol =
                    !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
                        ? "https"
                        : "http";
                $host = $_SERVER["HTTP_HOST"] ?? "localhost";
                $baseUrl = $protocol . "://" . $host . "/FacultyEvaluation";

                $title = "Evaluation Results Ready";
                $greeting =
                    "Dear Professor " .
                    htmlspecialchars($facultyName, ENT_QUOTES, "UTF-8") .
                    ",";
                $content_html =
                    "
                    <p style='margin: 0 0 16px 0;'>The faculty evaluation period for <strong>Academic Year " .
                    htmlspecialchars($ay, ENT_QUOTES, "UTF-8") .
                    ", " .
                    htmlspecialchars($semester, ENT_QUOTES, "UTF-8") .
                    "</strong> has closed.</p>
                    <p style='margin: 0 0 16px 0;'>Your faculty evaluation report and per-subject evaluation report are attached to this email and have also been uploaded to your instructor portal.</p>
                    <p style='margin: 0 0 16px 0;'>You can view and download this report by logging into the dashboard and visiting the <strong>Evaluation History & Uploaded PDFs</strong> tab.</p>
                ";

                $cta = [
                    "label" => "View My Evaluation Dashboard",
                    "url" => $baseUrl . "/EvalMain.php",
                ];

                $body = getEmailHTML(
                    $title,
                    $greeting,
                    $content_html,
                    null,
                    $cta,
                );
                if (
                    sendEmail(
                        $facultyEmail,
                        $facultyName,
                        $subject,
                        $body,
                        $reportPath,
                        $fileName,
                        [
                            [
                                "path" => $evaluationReportPath,
                                "name" => $evaluationReportFileName,
                            ],
                        ],
                    )
                ) {
                    $reportEmailsSent++;
                } else {
                    $reportEmailsFailed++;
                    $error =
                        "Unable to email reports to faculty $facultyDbId: " .
                        getLastEmailError();
                    $reportEmailErrors[] = $error;
                    error_log($error);
                }
            } else {
                $reportEmailsFailed++;
                $error =
                    "Report email skipped for faculty $facultyDbId: " .
                    ($reportPath === null || $evaluationReportPath === null
                        ? "One or both PDFs were not generated."
                        : "Faculty email address is invalid.");
                $reportEmailErrors[] = $error;
                error_log($error);
            }
        }
    } else {
        $reportEmailsFailed++;
        $error = "Unable to load faculty members for evaluation reports for period $periodId.";
        $reportEmailErrors[] = $error;
        error_log($error);
    }

    return [
        "report_emails_sent" => $reportEmailsSent,
        "report_emails_failed" => $reportEmailsFailed,
        "report_email_errors" => $reportEmailErrors,
    ];
}

/**
 * Returns structured evaluation statistics for a single faculty member, filtered to a specific period.
 */
function helperGetFacultyPerSubjectReportData(
    mysqli $conn,
    int $faculty_id,
    int $period_id,
    string $facultyName,
    string $facultyStrId,
): ?array {
    $periodStmt = $conn->prepare(
        "SELECT ay, semester, start_date, end_date FROM evaluation_periods WHERE id = ? LIMIT 1",
    );
    $periodStmt->bind_param("i", $period_id);
    $periodStmt->execute();
    $periodInfo = $periodStmt->get_result()->fetch_assoc();
    $periodStmt->close();
    if (!$periodInfo) {
        return null;
    }

    $ratingLabelFn = function (float $score): string {
        if ($score <= 0) {
            return "No Responses";
        }
        if ($score >= 4.5) {
            return "Outstanding";
        }
        if ($score >= 3.5) {
            return "Very Good";
        }
        if ($score >= 2.5) {
            return "Good";
        }
        if ($score >= 1.5) {
            return "Fair";
        }
        return "Poor";
    };

    $ratingClassFn = function (float $score): string {
        if ($score <= 0) {
            return "no-responses";
        }
        if ($score >= 4.5) {
            return "outstanding";
        }
        if ($score >= 3.5) {
            return "very-good";
        }
        if ($score >= 2.5) {
            return "good";
        }
        if ($score >= 1.5) {
            return "fair";
        }
        return "poor";
    };

    // Fetch subjects + classes evaluated
    $subjectSql = "
        SELECT
            s.id                                                 AS subject_id,
            s.subject_code,
            s.subject_desc,
            s.semester,
            p.id                                                 AS program_id,
            p.program_code,
            p.program_name,
            e.class_id,
            COUNT(DISTINCT e.id)                                 AS eval_count,
            MIN(DATE(e.created_at))                              AS date_from,
            MAX(DATE(e.created_at))                              AS date_to
        FROM evaluations e
        INNER JOIN add_subjects s  ON s.id  = e.subject_id
        INNER JOIN add_programs p  ON p.id  = s.program_id
        WHERE e.faculty_id = ?
          AND e.period_id = ?
          AND e.subject_id > 0
        GROUP BY
            s.id,
            s.subject_code,
            s.subject_desc,
            s.semester,
            p.id,
            p.program_code,
            p.program_name,
            e.class_id
        ORDER BY p.program_name ASC, s.subject_code ASC
    ";

    $subStmt = $conn->prepare($subjectSql);
    if (!$subStmt) {
        return null;
    }
    $subStmt->bind_param("ii", $faculty_id, $period_id);
    $subStmt->execute();
    $subjectRows = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $subStmt->close();

    $departments = [];
    foreach ($subjectRows as $row) {
        $prog_id = intval($row["program_id"]);
        $subj_id = intval($row["subject_id"]);
        $class_id = intval($row["class_id"]);
        $eval_cnt = intval($row["eval_count"]);

        if (!isset($departments[$prog_id])) {
            $departments[$prog_id] = [
                "program_id" => $prog_id,
                "program_code" => $row["program_code"],
                "program_name" => $row["program_name"],
                "subjects" => [],
            ];
        }

        $avg =
            $eval_cnt > 0
                ? calcWeightedScore(
                    $conn,
                    $faculty_id,
                    $subj_id,
                    $class_id,
                    $period_id,
                )
                : 0.0;
        $cats = getCategoryStats(
            $conn,
            $faculty_id,
            $subj_id,
            $class_id,
            $period_id,
        );
        $category_totals = [];
        foreach ($cats as $cat) {
            $category_totals[] = [
                "category_name" => $cat["category_name"],
                "avg_rating" => $cat["avg_rating"],
                "responses" => $cat["responses"],
                "weight" => $cat["weight"],
                "normalised_weight" => $cat["normalised_weight"],
            ];
        }

        // Student comments
        $fbStmt = $conn->prepare("
            SELECT feedback FROM evaluations
            WHERE faculty_id = ? AND subject_id = ? AND class_id = ? AND period_id = ?
              AND feedback IS NOT NULL AND TRIM(feedback) != ''
            ORDER BY created_at DESC
        ");
        $fbStmt->bind_param(
            "iiii",
            $faculty_id,
            $subj_id,
            $class_id,
            $period_id,
        );
        $fbStmt->execute();
        $fbRows = $fbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $fbStmt->close();
        $comments = array_map(fn($r) => trim($r["feedback"]), $fbRows);

        $periodText = "N/A";
        if (!empty($row["date_from"]) && !empty($row["date_to"])) {
            $start = date("M j, Y", strtotime($row["date_from"]));
            $end = date("M j, Y", strtotime($row["date_to"]));
            $periodText = $start === $end ? $start : "$start – $end";
        }

        $departments[$prog_id]["subjects"][] = [
            "subject_id" => $subj_id,
            "subject_code" => $row["subject_code"],
            "subject_desc" => $row["subject_desc"],
            "semester" => $row["semester"],
            "eval_count" => $eval_cnt,
            "avg_rating" => number_format($avg, 2),
            "percentage" => number_format(($avg / 5) * 100, 2),
            "rating_label" => $ratingLabelFn($avg),
            "rating_class" => $ratingClassFn($avg),
            "period" => $periodText,
            "category_totals" => $category_totals,
            "comments" => $comments,
        ];
    }
    $departments = array_values($departments);

    // Overall metrics
    $totalStmt = $conn->prepare("
        SELECT COUNT(id) AS total, MIN(DATE(created_at)) AS date_from, MAX(DATE(created_at)) AS date_to
        FROM evaluations
        WHERE faculty_id = ? AND period_id = ?
    ");
    $totalStmt->bind_param("ii", $faculty_id, $period_id);
    $totalStmt->execute();
    $totalRow = $totalStmt->get_result()->fetch_assoc();
    $totalStmt->close();

    $grandTotal = intval($totalRow["total"] ?? 0);
    $overallScore =
        $grandTotal > 0
            ? calcWeightedScore($conn, $faculty_id, null, null, $period_id)
            : 0.0;

    $overallPeriod = "N/A";
    if (!empty($totalRow["date_from"]) && !empty($totalRow["date_to"])) {
        $s = date("M j, Y", strtotime($totalRow["date_from"]));
        $e = date("M j, Y", strtotime($totalRow["date_to"]));
        $overallPeriod = $s === $e ? $s : "$s – $e";
    }

    $overallCats = getCategoryStats($conn, $faculty_id, null, null, $period_id);
    $overallCatTotals = [];
    foreach ($overallCats as $cat) {
        $overallCatTotals[] = [
            "category_name" => $cat["category_name"],
            "avg_rating" => $cat["avg_rating"],
            "responses" => $cat["responses"],
            "weight" => $cat["weight"],
            "normalised_weight" => $cat["normalised_weight"],
        ];
    }

    $allFbStmt = $conn->prepare("
        SELECT feedback FROM evaluations
        WHERE faculty_id = ? AND period_id = ? AND feedback IS NOT NULL AND TRIM(feedback) != ''
        ORDER BY created_at DESC
    ");
    $allFbStmt->bind_param("ii", $faculty_id, $period_id);
    $allFbStmt->execute();
    $allFbRows = $allFbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $allFbStmt->close();
    $allComments = array_map(fn($r) => trim($r["feedback"]), $allFbRows);

    return [
        "facultyName" => $facultyName,
        "facultyId" => $facultyStrId,
        "period" => $periodInfo,
        "departments" => $departments,
        "overall" => [
            "avg_rating" => number_format($overallScore, 2),
            "percentage" => number_format(($overallScore / 5) * 100, 2),
            "rating_label" => $ratingLabelFn($overallScore),
            "rating_class" => $ratingClassFn($overallScore),
            "eval_count" => $grandTotal,
            "period" => $overallPeriod,
            "category_totals" => $overallCatTotals,
            "comments" => $allComments,
        ],
    ];
}

/**
 * Generates the faculty evaluation summary PDF and saves it locally.
 */
function helperGenerateEvaluationSummaryPDF(
    array $data,
    string $outputPath,
): bool {
    try {
        $toPdfText = static fn($text): string => (string) iconv(
            "UTF-8",
            "windows-1252//TRANSLIT",
            (string) $text,
        );
        $facultyName = $toPdfText($data["facultyName"] ?? "N/A");
        $facultyId = $toPdfText($data["facultyId"] ?? "N/A");
        $period = $data["period"] ?? [];
        $overall = $data["overall"] ?? [];
        $categories = is_array($overall["category_totals"] ?? null)
            ? $overall["category_totals"]
            : [];
        $comments = is_array($overall["comments"] ?? null)
            ? $overall["comments"]
            : [];
        $pdf = new class ("P", "mm", "A4") extends FPDF {
            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetDrawColor(210, 220, 232);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                $this->SetY(-10);
                $this->SetFont("Arial", "", 8);
                $this->SetTextColor(120, 130, 145);
                $this->Cell(
                    130,
                    6,
                    iconv(
                        "UTF-8",
                        "windows-1252//TRANSLIT",
                        "Generated " . date("M j, Y  h:i A"),
                    ),
                    0,
                    0,
                    "L",
                );
                $this->Cell(60, 6, "Page " . $this->PageNo(), 0, 0, "R");
            }
        };
        $pdf->SetAutoPageBreak(true, 17);
        $pdf->AddPage();
        $blue = [11, 126, 196];
        $navy = [30, 64, 120];
        $light = [245, 249, 255];
        $border = [215, 225, 238];
        $textColor = [50, 50, 50];

        $pdf->SetFillColor($blue[0], $blue[1], $blue[2]);
        $pdf->Rect(10, 10, 190, 34, "F");
        $logoPath =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "assets" .
            DIRECTORY_SEPARATOR .
            "images" .
            DIRECTORY_SEPARATOR .
            "granbylogo.png";
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 15, 15, 23);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("Arial", "B", 15);
        $pdf->SetXY(10, 16);
        $pdf->Cell(
            190,
            9,
            "Granby Colleges of Science and Technology",
            0,
            1,
            "C",
        );
        $pdf->SetFont("Arial", "B", 11);
        $pdf->SetX(10);
        $pdf->Cell(190, 8, "Faculty Evaluation Report", 0, 1, "C");
        $pdf->SetY(64);

        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $detailRows = [
            ["Faculty Name:", $facultyName],
            ["Faculty ID:", $facultyId],
            ["Academic Year:", $toPdfText($period["ay"] ?? "Not specified")],
            ["Semester:", $toPdfText($period["semester"] ?? "Not specified")],
            [
                "Evaluation Period:",
                $toPdfText(
                    !empty($period["start_date"]) && !empty($period["end_date"])
                        ? date("F j, Y", strtotime($period["start_date"])) .
                            " - " .
                            date("F j, Y", strtotime($period["end_date"]))
                        : "Not specified",
                ),
            ],
        ];
        $boxHeight = 8 + count($detailRows) * 7;
        $boxY = $pdf->GetY();
        $pdf->SetDrawColor($border[0], $border[1], $border[2]);
        $pdf->SetFillColor($light[0], $light[1], $light[2]);
        $pdf->Rect(10, $boxY, 190, $boxHeight, "DF");
        $pdf->SetXY(14, $boxY + 4);
        foreach ($detailRows as [$label, $value]) {
            $pdf->SetX(14);
            $pdf->SetFont("Arial", "B", 10);
            $pdf->Cell(40, 7, $label, 0, 0);
            $pdf->SetFont("Arial", "", 10);
            $pdf->Cell(142, 7, $value, 0, 1);
        }
        $pdf->SetY($boxY + $boxHeight + 4);

        $score = is_numeric($overall["avg_rating"] ?? null)
            ? (float) $overall["avg_rating"]
            : 0.0;
        $performancePercent = max(0, min(100, $score * 20));
        $performanceStatus = $toPdfText(
            $overall["rating_label"] ?? "No Responses",
        );
        $evaluationCount = (string) max(0, (int) ($overall["eval_count"] ?? 0));

        $pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(190, 8, "  OVERALL FACULTY RATING", 0, 1, "L", true);

        $summaryY = $pdf->GetY();
        $summaryWidth = 47.5;
        $pdf->SetFillColor($light[0], $light[1], $light[2]);
        $pdf->SetDrawColor($border[0], $border[1], $border[2]);
        $pdf->Rect(10, $summaryY, 190, 23, "DF");
        $summaryItems = [
            [
                "OVERALL RATING",
                $toPdfText(number_format($score, 2) . " / 5.00"),
            ],
            ["PERFORMANCE SCORE", number_format($performancePercent, 1) . "%"],
            ["EVALUATIONS", $evaluationCount],
            ["STATUS", $performanceStatus],
        ];
        foreach ($summaryItems as $index => [$label, $value]) {
            $x = 10 + $index * $summaryWidth;
            if ($index > 0) {
                $pdf->Line($x, $summaryY + 3, $x, $summaryY + 20);
            }
            $pdf->SetXY($x + 3, $summaryY + 4);
            $pdf->SetFont("Arial", "B", 7);
            $pdf->SetTextColor(95, 110, 130);
            $pdf->Cell($summaryWidth - 6, 4, $label, 0, 1, "L");
            $pdf->SetX($x + 3);
            $pdf->SetFont("Arial", "B", 10);
            $pdf->SetTextColor($navy[0], $navy[1], $navy[2]);
            $pdf->Cell(
                $summaryWidth - 6,
                7,
                $toPdfText((string) $value),
                0,
                1,
                "L",
            );
        }
        $pdf->SetY($summaryY + 27);

        $pdf->SetFillColor($navy[0], $navy[1], $navy[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("Arial", "B", 10);
        $pdf->Cell(190, 8, "  Overall Category Ratings", 0, 1, "L", true);

        $pdf->SetDrawColor($border[0], $border[1], $border[2]);
        $pdf->SetFont("Arial", "B", 8);
        $categoryWidths = [64, 24, 34, 28, 40];
        foreach (
            ["Category", "Score", "Average", "Responses", "Status"]
            as $index => $heading
        ) {
            $pdf->Cell(
                $categoryWidths[$index],
                7,
                $heading,
                1,
                $index === 4 ? 1 : 0,
                "C",
                true,
            );
        }

        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetFont("Arial", "", 8);
        if (empty($categories)) {
            $pdf->Cell(
                190,
                8,
                "No category evaluation data available.",
                1,
                1,
                "C",
            );
        } else {
            foreach (array_values($categories) as $index => $category) {
                if ($pdf->GetY() > 265) {
                    $pdf->AddPage();
                }
                $categoryScore = is_numeric($category["avg_rating"] ?? null)
                    ? (float) $category["avg_rating"]
                    : 0.0;
                $categoryStatus =
                    $categoryScore <= 0
                        ? "No Responses"
                        : ($categoryScore >= 4.5
                            ? "Outstanding"
                            : ($categoryScore >= 3.5
                                ? "Very Good"
                                : ($categoryScore >= 2.5
                                    ? "Good"
                                    : ($categoryScore >= 1.5
                                        ? "Fair"
                                        : "Poor"))));
                $pdf->SetFillColor(
                    ...$index % 2 === 0 ? [255, 255, 255] : [247, 250, 255],
                );
                $values = [
                    $toPdfText($category["category_name"] ?? "N/A"),
                    number_format(max(0, min(100, $categoryScore * 20)), 0) .
                    "%",
                    $categoryScore > 0
                        ? number_format($categoryScore, 2) . " / 5"
                        : "N/A",
                    (string) max(0, (int) ($category["responses"] ?? 0)),
                    $categoryStatus,
                ];
                foreach ($values as $column => $value) {
                    $pdf->Cell(
                        $categoryWidths[$column],
                        7,
                        $toPdfText((string) $value),
                        1,
                        $column === 4 ? 1 : 0,
                        $column === 0 ? "L" : "C",
                        true,
                    );
                }
            }
        }

        $comments = array_values(
            array_filter(
                array_map(
                    static fn($comment): string => trim((string) $comment),
                    $comments,
                ),
                static fn($comment): bool => $comment !== "",
            ),
        );
        if (empty($comments)) {
            $comments = ["No feedback available"];
        }
        $pdf->Ln(4);
        $pdf->SetFillColor(220, 235, 255);
        $pdf->SetTextColor(20, 40, 100);
        $pdf->SetFont("Arial", "B", 9);
        $pdf->Cell(
            190,
            7,
            "  Student Comments  (" . count($comments) . ")",
            0,
            1,
            "L",
            true,
        );
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetFont("Arial", "", 8);
        foreach ($comments as $index => $comment) {
            if ($pdf->GetY() > 265) {
                $pdf->AddPage();
            }
            $pdf->SetFillColor(250, 252, 255);
            $pdf->SetDrawColor($border[0], $border[1], $border[2]);
            $pdf->SetX(13);
            $pdf->MultiCell(
                184,
                5,
                $toPdfText($index + 1 . ". " . $comment),
                1,
                "L",
                true,
            );
        }

        $pdf->Output("F", $outputPath);
        return is_file($outputPath) && filesize($outputPath) > 0;
    } catch (Throwable $e) {
        error_log(
            "Evaluation summary PDF generation error: " . $e->getMessage(),
        );
        return false;
    }
}

/**
 * Generates the report PDF and saves it to the local filesystem.
 */
function helperGenerateReportPDF(
    mysqli $conn,
    array $data,
    string $outputPath,
): bool {
    try {
        $facultyName = iconv(
            "UTF-8",
            "windows-1252//TRANSLIT",
            $data["facultyName"] ?? "N/A",
        );
        $facultyId = iconv(
            "UTF-8",
            "windows-1252//TRANSLIT",
            $data["facultyId"] ?? "N/A",
        );
        $period = $data["period"] ?? [];
        $departments = $data["departments"] ?? [];
        $pdf = new class ("P", "mm", "A4") extends FPDF {
            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetDrawColor(210, 220, 232);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                $this->SetY(-10);
                $this->SetFont("Arial", "", 8);
                $this->SetTextColor(120, 130, 145);
                $this->Cell(
                    130,
                    6,
                    iconv(
                        "UTF-8",
                        "windows-1252//TRANSLIT",
                        "Generated " . date("M j, Y  h:i A"),
                    ),
                    0,
                    0,
                    "L",
                );
                $this->Cell(
                    60,
                    6,
                    iconv(
                        "UTF-8",
                        "windows-1252//TRANSLIT",
                        "Page " . $this->PageNo(),
                    ),
                    0,
                    0,
                    "R",
                );
            }
        };
        $pdf->SetAutoPageBreak(true, 17);
        $pdf->AddPage();

        $BLUE = [11, 126, 196];
        $DARK = [50, 50, 50];
        $LIGHT = [245, 249, 255];
        $GRAY = [215, 225, 238];
        $NAVY = [30, 64, 120];

        $ratingLabel = function (float $score) {
            if ($score <= 0) {
                return "N/A";
            }
            if ($score >= 4.5) {
                return "Outstanding";
            }
            if ($score >= 3.5) {
                return "Very Good";
            }
            if ($score >= 2.5) {
                return "Good";
            }
            if ($score >= 1.5) {
                return "Fair";
            }
            return "Poor";
        };

        $ct = function ($text) {
            return iconv("UTF-8", "windows-1252//TRANSLIT", (string) $text);
        };

        // Header block
        $pdf->SetFillColor($BLUE[0], $BLUE[1], $BLUE[2]);
        $pdf->Rect(10, 10, 190, 34, "F");

        $logoPath =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "assets" .
            DIRECTORY_SEPARATOR .
            "images" .
            DIRECTORY_SEPARATOR .
            "granbylogo.png";
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 15, 15, 23);
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont("Arial", "B", 15);
        $pdf->SetXY(10, 16);
        $pdf->Cell(
            190,
            9,
            "Granby Colleges of Science and Technology",
            0,
            1,
            "C",
        );
        $pdf->SetFont("Arial", "B", 11);
        $pdf->SetX(10);
        $pdf->Cell(190, 8, "Faculty Per-Subject Evaluation Report", 0, 1, "C");
        $pdf->Ln(20);
        $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

        // Faculty Info Box
        $pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
        $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
        $infoY = $pdf->GetY();
        $pdf->Rect(10, $infoY, 190, 43, "DF");
        $pdf->SetXY(14, $pdf->GetY() + 4);
        $pdf->SetFont("Arial", "B", 11);
        $pdf->Cell(40, 7, "Faculty Name:", 0, 0);
        $pdf->SetFont("Arial", "", 11);
        $pdf->Cell(0, 7, $facultyName, 0, 1);
        $pdf->SetX(14);
        $pdf->SetFont("Arial", "B", 11);
        $pdf->Cell(40, 7, "Faculty ID:", 0, 0);
        $pdf->SetFont("Arial", "", 11);
        $pdf->Cell(0, 7, $facultyId, 0, 1);
        $pdf->SetX(14);
        $pdf->SetFont("Arial", "B", 11);
        $pdf->Cell(40, 7, "Academic Year:", 0, 0);
        $pdf->SetFont("Arial", "", 11);
        $pdf->Cell(0, 7, $ct($period["ay"] ?? "Not specified"), 0, 1);
        $pdf->SetX(14);
        $pdf->SetFont("Arial", "B", 11);
        $pdf->Cell(40, 7, "Semester:", 0, 0);
        $pdf->SetFont("Arial", "", 11);
        $pdf->Cell(0, 7, $ct($period["semester"] ?? "Not specified"), 0, 1);
        $pdf->SetX(14);
        $pdf->SetFont("Arial", "B", 11);
        $pdf->Cell(40, 7, "Evaluation Dates:", 0, 0);
        $dates =
            !empty($period["start_date"]) && !empty($period["end_date"])
                ? date("F j, Y", strtotime($period["start_date"])) .
                    " - " .
                    date("F j, Y", strtotime($period["end_date"]))
                : "Not specified";
        $pdf->SetFont("Arial", "", 11);
        $pdf->Cell(0, 7, $ct($dates), 0, 1);
        $pdf->SetY($infoY + 48);

        // Departments & Subjects Breakdown
        foreach ($departments as $dept) {
            $progName = $ct($dept["program_name"] ?? "Unknown Program");
            $progCode = $ct($dept["program_code"] ?? "");
            $label = $progCode ? "$progName ($progCode)" : $progName;

            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }

            $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
            $pdf->SetTextColor(255, 255, 255);
            $subjects = is_array($dept["subjects"] ?? null)
                ? $dept["subjects"]
                : [];
            $pdf->SetFont("Arial", "B", 10);
            $pdf->Cell(
                0,
                8,
                "  " .
                    $label .
                    "  |  " .
                    count($subjects) .
                    (count($subjects) === 1 ? " subject" : " subjects"),
                0,
                1,
                "L",
                true,
            );
            $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

            foreach ($subjects as $subj) {
                if ($pdf->GetY() > 235) {
                    $pdf->AddPage();
                }

                $code = $ct($subj["subject_code"] ?? "");
                $desc = $ct($subj["subject_desc"] ?? "");
                $title = $code . ($desc !== "" ? " - $desc" : "");

                $pdf->SetFillColor(220, 235, 255);
                $pdf->SetTextColor(20, 40, 100);
                $pdf->SetFont("Arial", "B", 10);
                $pdf->Cell(0, 7, "  " . $title, 0, 1, "L", true);
                $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);

                $avg = (float) ($subj["avg_rating"] ?? 0);
                $scorePercent = max(
                    0,
                    min(100, (float) ($subj["percentage"] ?? 0)),
                );
                $evaluationCount = max(0, (int) ($subj["eval_count"] ?? 0));
                $summaryY = $pdf->GetY() + 3;
                $pdf->SetFillColor($LIGHT[0], $LIGHT[1], $LIGHT[2]);
                $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
                $pdf->Rect(10, $summaryY, 190, 24, "DF");
                $summaryItems = [
                    ["AVERAGE RATING", number_format($avg, 2) . " / 5.00"],
                    [
                        "PERFORMANCE SCORE",
                        number_format($scorePercent, 1) . "%",
                    ],
                    ["EVALUATIONS", (string) $evaluationCount],
                    [
                        "STATUS",
                        $evaluationCount > 0
                            ? $subj["rating_label"] ?? $ratingLabel($avg)
                            : "No Responses",
                    ],
                ];
                $summaryWidth = 47.5;
                foreach ($summaryItems as $index => [$summaryLabel, $value]) {
                    $x = 10 + $index * $summaryWidth;
                    if ($index > 0) {
                        $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
                        $pdf->Line($x, $summaryY + 4, $x, $summaryY + 20);
                    }
                    $pdf->SetXY($x + 3, $summaryY + 4);
                    $pdf->SetFont("Arial", "B", 7);
                    $pdf->SetTextColor(95, 110, 130);
                    $pdf->Cell(
                        $summaryWidth - 6,
                        4,
                        $ct($summaryLabel),
                        0,
                        1,
                        "L",
                    );
                    $pdf->SetX($x + 3);
                    $pdf->SetFont("Arial", "B", 10);
                    $pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
                    $pdf->Cell($summaryWidth - 6, 7, $ct($value), 0, 1, "L");
                }
                $pdf->SetY($summaryY + 27);
                $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
                $pdf->SetFont("Arial", "", 8);
                $pdf->Cell(
                    0,
                    5,
                    $ct("Evaluation period: " . ($subj["period"] ?? "N/A")),
                    0,
                    1,
                    "L",
                );
                $pdf->Ln(2);

                if (!empty($subj["category_totals"])) {
                    $pdf->SetFont("Arial", "B", 8);
                    $pdf->SetFillColor($NAVY[0], $NAVY[1], $NAVY[2]);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->Cell(80, 7, "Category", 1, 0, "L", true);
                    $pdf->Cell(24, 7, "Score", 1, 0, "C", true);
                    $pdf->Cell(30, 7, "Average", 1, 0, "C", true);
                    $pdf->Cell(27, 7, "Responses", 1, 0, "C", true);
                    $pdf->Cell(28, 7, "Status", 1, 1, "C", true);
                    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
                    $pdf->SetFont("Arial", "", 8);
                    foreach (
                        array_values($subj["category_totals"])
                        as $rowIndex => $cat
                    ) {
                        $subcatavg = max(0, (float) ($cat["avg_rating"] ?? 0));
                        $catResponses = max(0, (int) ($cat["responses"] ?? 0));
                        $catPercent =
                            $catResponses > 0
                                ? max(0, min(100, ($subcatavg / 5) * 100))
                                : 0;
                        $pdf->SetFillColor(
                            ...$rowIndex % 2 === 0
                                ? [255, 255, 255]
                                : [247, 250, 255],
                        );
                        $pdf->Cell(
                            80,
                            7,
                            $ct($cat["category_name"] ?? ""),
                            1,
                            0,
                            "L",
                            true,
                        );
                        $pdf->Cell(
                            24,
                            7,
                            $ct(number_format($catPercent, 0) . "%"),
                            1,
                            0,
                            "C",
                            true,
                        );
                        $pdf->Cell(
                            30,
                            7,
                            $catResponses > 0
                                ? $ct(number_format($subcatavg, 2) . " / 5")
                                : "N/A",
                            1,
                            0,
                            "C",
                            true,
                        );
                        $pdf->Cell(
                            27,
                            7,
                            $ct((string) $catResponses),
                            1,
                            0,
                            "C",
                            true,
                        );
                        $pdf->Cell(
                            28,
                            7,
                            $ct(
                                $catResponses > 0
                                    ? $ratingLabel($subcatavg)
                                    : "N/A",
                            ),
                            1,
                            1,
                            "C",
                            true,
                        );
                    }
                } else {
                    $pdf->SetFont("Arial", "", 8);
                    $pdf->SetTextColor(110, 120, 135);
                    $pdf->Cell(
                        0,
                        7,
                        $ct("No category ratings available for this subject."),
                        0,
                        1,
                        "L",
                    );
                }
                $pdf->Ln(2);

                $comments = is_array($subj["comments"] ?? null)
                    ? $subj["comments"]
                    : [];
                if (!empty($comments)) {
                    $pdf->SetFont("Arial", "B", 8);
                    $pdf->SetFillColor(235, 243, 255);
                    $pdf->SetTextColor($NAVY[0], $NAVY[1], $NAVY[2]);
                    $pdf->Cell(
                        0,
                        7,
                        "  Student Comments  (" . count($comments) . ")",
                        0,
                        1,
                        "L",
                        true,
                    );
                    $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
                    $pdf->SetFont("Arial", "", 8);
                    $shown = array_slice($comments, 0, 5);
                    foreach ($shown as $i => $comment) {
                        if ($pdf->GetY() > 265) {
                            $pdf->AddPage();
                        }
                        $num = $i + 1;
                        $pdf->SetFillColor(250, 252, 255);
                        $pdf->SetDrawColor($GRAY[0], $GRAY[1], $GRAY[2]);
                        $pdf->SetX(13);
                        $pdf->MultiCell(
                            184,
                            5,
                            $ct($num . ". " . trim((string) $comment)),
                            1,
                            "L",
                            true,
                        );
                    }
                    if (count($comments) > 5) {
                        $pdf->SetFont("Arial", "", 7);
                        $pdf->SetTextColor(110, 120, 135);
                        $pdf->Cell(
                            0,
                            5,
                            $ct(
                                "Showing 5 of " .
                                    count($comments) .
                                    " comments.",
                            ),
                            0,
                            1,
                            "L",
                        );
                    }
                } else {
                    $pdf->SetFont("Arial", "", 8);
                    $pdf->SetTextColor(110, 120, 135);
                    $pdf->Cell(
                        0,
                        7,
                        $ct("No comments were submitted for this subject."),
                        0,
                        1,
                        "L",
                    );
                }
                $pdf->SetTextColor($DARK[0], $DARK[1], $DARK[2]);
                $pdf->Ln(3);
            }
            $pdf->Ln(3);
        }

        // Output to local file
        $pdf->Output("F", $outputPath);
        return true;
    } catch (Exception $ex) {
        error_log("PDF generation error: " . $ex->getMessage());
        return false;
    }
}
