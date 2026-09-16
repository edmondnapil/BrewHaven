<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$username = sanitizeInput($_POST['username'] ?? '');

if (empty($username)) {
    sendResponse(false, 'Username is required');
}

// Check if username exists (procedural mysqli)
$sql = "SELECT id_number FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

$exists = mysqli_stmt_num_rows($stmt) > 0;

mysqli_stmt_close($stmt);

sendResponse(true, '', ['exists' => $exists]);
?>
