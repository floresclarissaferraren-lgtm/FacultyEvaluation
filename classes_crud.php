<?php
include "connect.php";

$action = $_REQUEST['action'] ?? "";

/* =====================================================
   ADD CLASS + MULTIPLE SUBJECTS
===================================================== */
if ($action === "add") {

    $section_name = $_POST['section_name'] ?? '';
    $year_level   = $_POST['year_level'] ?? '';
    $subjects     = isset($_POST['subjects']) ? json_decode($_POST['subjects'], true) : [];

    if (!$section_name || !$year_level) {
        echo "error: missing fields";
        exit;
    }

    // 1. Insert class
    $stmt = $conn->prepare("INSERT INTO add_classes (section_name, year_level) VALUES (?, ?)");
    $stmt->bind_param("ss", $section_name, $year_level);

    if ($stmt->execute()) {

        $class_id = $stmt->insert_id;

        // 2. Insert class_subjects (if any selected)
        if (!empty($subjects)) {

            $stmt2 = $conn->prepare("
                INSERT INTO class_subjects (class_id, subject_id)
                VALUES (?, ?)
            ");

            foreach ($subjects as $subject_id) {
                $stmt2->bind_param("ii", $class_id, $subject_id);
                $stmt2->execute();
            }
        }

        echo "success";

    } else {
        echo "error";
    }
}


/* =====================================================
   GET CLASSES (ALL OR BY ID WITH SUBJECTS)
===================================================== */
elseif ($action === "get") {

    $class_id = $_GET['class_id'] ?? null;

    // GET SINGLE CLASS WITH SUBJECTS
    if ($class_id) {

        $stmt = $conn->prepare("
            SELECT s.id, s.subject_code, s.subject_desc, s.year_level
            FROM class_subjects cs
            JOIN add_subjects s ON cs.subject_id = s.id
            WHERE cs.class_id = ?
        ");

        $stmt->bind_param("i", $class_id);

    } 
    // GET ALL CLASSES
    else {

        $stmt = $conn->prepare("
            SELECT * FROM add_class ORDER BY id DESC
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}


/* =====================================================
   DELETE CLASS (auto deletes subjects via FK cascade)
===================================================== */
elseif ($action === "delete") {

    $id = $_POST['id'] ?? 0;

    $stmt = $conn->prepare("DELETE FROM add_class WHERE id=?");
    $stmt->bind_param("i", $id);

    echo $stmt->execute() ? "success" : "error";
}


/* =====================================================
   EDIT CLASS (basic info only)
===================================================== */
elseif ($action === "edit") {

    $id = $_POST['id'] ?? 0;
    $section_name = $_POST['section_name'] ?? '';
    $year_level = $_POST['year_level'] ?? '';

    $stmt = $conn->prepare("
        UPDATE add_class 
        SET section_name=?, year_level=? 
        WHERE id=?
    ");

    $stmt->bind_param("ssi", $section_name, $year_level, $id);

    echo $stmt->execute() ? "success" : "error";
}
?>