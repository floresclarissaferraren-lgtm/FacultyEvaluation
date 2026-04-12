<?php
include 'connect.php';
header('Content-Type: application/json');

$id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;

if (!$id) {
    echo json_encode(['success'=>false,'error'=>'Invalid or missing category_id']);
    exit;
}

$conn->begin_transaction();

try {
    // Delete questions first
    $stmtQ = $conn->prepare("DELETE FROM add_questions WHERE category_id = ?");
    $stmtQ->bind_param("i", $id);
    $stmtQ->execute();

    // Delete category
    $stmtC = $conn->prepare("DELETE FROM add_categories WHERE id = ?");
    $stmtC->bind_param("i", $id);
    $stmtC->execute();

    if ($stmtC->affected_rows > 0) {
        $conn->commit();
        echo json_encode(['success'=>true,'deleted_id'=>$id]);
    } else {
        $conn->rollback();
        echo json_encode(['success'=>false,'error'=>'Category not found']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>'Transaction failed: '.$e->getMessage()]);
}
?>
