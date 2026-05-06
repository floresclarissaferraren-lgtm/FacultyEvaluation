<?php
session_start();
// Simulate faculty login session
$_SESSION['id'] = 33; // Using existing faculty ID from database
$_SESSION['role'] = 'faculty';

echo "<h2>Testing Password Change Functionality</h2>";

// Test the password update endpoint
$data = [
    'faculty_id' => 33,
    'old_password' => 'password123', // You'll need to use the actual current password
    'new_password' => 'newpassword123'
];

$ch = curl_init('http://localhost/eval/update_facultyPassForm.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Response:</h3>";
echo "<pre>";
echo "HTTP Status: " . $httpCode . "\n";
echo $response;
echo "</pre>";

$result = json_decode($response, true);
if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "<h3 style='color: green;'>✓ Password change test successful!</h3>";
    } else {
        echo "<h3 style='color: red;'>✗ Password change test failed: " . htmlspecialchars($result['message']) . "</h3>";
    }
} else {
    echo "<h3 style='color: red;'>✗ Invalid response from server</h3>";
}
?>
