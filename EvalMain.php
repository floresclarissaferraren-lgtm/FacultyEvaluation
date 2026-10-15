<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$destination = "main/EvalMain.php";
if (isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] !== "") {
    $destination .= "?" . $_SERVER["QUERY_STRING"];
}

header("Location: " . $destination, true, 302);
exit();
