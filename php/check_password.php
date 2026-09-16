<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method');
}

$password = $_POST['password'] ?? '';

if (empty($password)) {
    sendResponse(false, 'Password is required');
}

// Check if password exists by verifying against all stored password hashes
$sql = "SELECT password FROM users";
$result = mysqli_query($conn, $sql);

$exists = false;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Verify if the entered password matches any existing hash
        if (password_verify($password, $row['password'])) {
            $exists = true;
            break;
        }
    }
}

sendResponse(true, '', ['exists' => $exists]);
?>

