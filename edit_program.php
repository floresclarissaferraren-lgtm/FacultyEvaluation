<?php
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id   = $_POST['id'];
    $code = $_POST['program_code'];
    $name = $_POST['program_name'];

    $stmt = $conn->prepare("UPDATE add_programs SET program_code=?, program_name=? WHERE id=?");
    $stmt->bind_param("ssi", $code, $name, $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
}
?>
