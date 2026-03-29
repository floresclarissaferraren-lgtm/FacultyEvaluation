<?php
include "connect.php";

$result = $conn->query("SELECT * FROM add_faculties");

$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode($data);
?>