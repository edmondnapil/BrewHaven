<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_login(false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/change-password-setup.php');
    exit;
}

// Any role still inside first-login setup
require_first_login_setup($me);

$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    header('Location: ../html/change-password-setup.php?error=missing_fields');
    exit;
}

// Shared password-strength validation
$strengthCheck = bh_validate_password_strength($newPassword);
if (!$strengthCheck['valid']) {
    header('Location: ../html/change-password-setup.php?error=weak_password');
    exit;
}

if ($newPassword !== $confirmPassword) {
    header('Location: ../html/change-password-setup.php?error=mismatch');
    exit;
}

if ($newPassword === $currentPassword) {
    header('Location: ../html/change-password-setup.php?error=same_as_current');
    exit;
}

// Fetch stored password hash from DB
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$row || !verifyPassword($currentPassword, $row['password'])) {
    header('Location: ../html/change-password-setup.php?error=incorrect_current');
    exit;
}

$hashedPassword = hashPassword($newPassword);

// Advance to step 3 (security questions)
$updateSql = "UPDATE users SET password = ?, first_login = 3 WHERE id_number = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'ss', $hashedPassword, $me['id_number']);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);

    log_security_event('password_reset', $me['id_number'], $me['id_number'], 'first-login password changed (step 2 of 3)');

    // Redirect to the next step: security questions
    header('Location: ../html/setup-security-questions.php');
    exit;
} else {
    mysqli_stmt_close($updateStmt);
    header('Location: ../html/change-password-setup.php?error=save_failed');
    exit;
}
