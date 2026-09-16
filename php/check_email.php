<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$email = sanitizeInput($_POST['email'] ?? '');

if (empty($email)) {
    sendResponse(false, 'Email is required');
}

// A signed-in user checking their own address (first-login profile form) must
// not be told it is taken by themselves. Only an existing session is read;
// registration visitors have none, so their check is unchanged.
$selfId = '';
if (isset($_COOKIE[session_name()]) && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    $selfId = (string)($_SESSION['user_id_number'] ?? '');
    session_write_close();
}

// Check if email exists (procedural mysqli)
$sql = "SELECT id_number FROM users WHERE email = ? AND id_number <> ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $selfId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

$exists = mysqli_stmt_num_rows($stmt) > 0;

mysqli_stmt_close($stmt);

sendResponse(true, '', ['exists' => $exists]);
?>
