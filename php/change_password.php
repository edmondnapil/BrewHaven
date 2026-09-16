<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/profile.php');
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    header('Location: ../html/profile.php?pwerror=missing_fields');
    exit;
}
if ($newPassword !== $confirmPassword) {
    header('Location: ../html/profile.php?pwerror=mismatch');
    exit;
}
// The same rules registration enforces - length, upper, lower, number and a
// special character. The old bare strlen check here was weaker than that, so
// a self-service change could set a password the account holder would not have
// been allowed to choose when they registered.
$pwCheck = bh_validate_password_strength($newPassword);
if (!$pwCheck['valid']) {
    header('Location: ../html/profile.php?pwerror=' . urlencode($pwCheck['error']));
    exit;
}
if ($newPassword === $currentPassword) {
    header('Location: ../html/profile.php?pwerror=same_as_current');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row || !verifyPassword($currentPassword, $row['password'])) {
    log_security_event('password_reset', $me['id_number'], $me['id_number'], 'denied: incorrect current password');
    header('Location: ../html/profile.php?pwerror=incorrect_current');
    exit;
}

$hashed = hashPassword($newPassword);
$updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_number = ?");
mysqli_stmt_bind_param($updateStmt, 'ss', $hashed, $me['id_number']);
mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

log_security_event('password_reset', $me['id_number'], $me['id_number'], 'via profile self-service');
header('Location: ../html/profile.php?msg=password_updated');
exit;
