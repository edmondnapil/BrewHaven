<?php
require_once __DIR__ . '/auth_helpers.php';
// Super Admins, and Admins holding the "Reset Passwords" add-on, which the
// permission matrix limits to User / Customer accounts.
$me = require_role(['super_admin', 'admin']);
$accountsPage = $me['role'] === 'super_admin' ? 'super-admin-accounts.php' : 'admin-accounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/' . $accountsPage);
    exit;
}

$targetId = sanitizeInput($_POST['id_number'] ?? '');
$newPassword = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';
$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? $accountsPage));

if ($targetId === '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_target'));
    exit;
}
if ($targetId === $me['id_number']) {
    // Use the profile page's own change-password flow instead, which
    // correctly requires the current password.
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'use_profile_to_change_own_password'));
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'not_found'));
    exit;
}

// Server-side authorization: role, self-protection and the blocked-account
// restriction (a blocked account's password cannot be reset until it is
// unblocked), enforced here rather than by hiding the menu entry.
$denied = bh_account_action_error($me, $target, 'reset_password');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=reset_password code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied));
    exit;
}

// The same password rules the rest of the system enforces - length, upper,
// lower, number and special character. The old 8-character-minimum check here
// was weaker than registration's, so it let an admin-set password through that
// the account holder could not have chosen themselves.
$pwCheck = bh_validate_password_strength($newPassword);
if (!$pwCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $pwCheck['error']));
    exit;
}
if ($confirmNewPassword !== '' && $newPassword !== $confirmNewPassword) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'Passwords do not match.'));
    exit;
}

// Resetting somebody else's credentials is a critical action: reason required.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', 'reset_password');
if (!$reasonCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Sensitive action: re-verify the acting Super Admin's own password before
// overwriting somebody else's credentials.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'password_reset_by_admin', $targetId);
if ($confirmError !== '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
    exit;
}

// Optional supporting document, stored only once the action is certain to run.
$proofCheck = bh_store_action_proof('action_proof', 'reset_password', $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

$hashed = hashPassword($newPassword);
$updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id_number = ?");
mysqli_stmt_bind_param($updateStmt, 'ss', $hashed, $targetId);
mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

// Never log the new password itself, and never the old hash either.
bh_log_account_action('password_reset_by_admin', $me, $targetId, [
    'details'        => 'target_role=' . $target['role'],
    'reason'         => $reason,
    'proof_path'     => $proofPath,
    'previous_state' => 'password=(previous hash)',
    'new_state'      => 'password=(reset by ' . $me['id_number'] . ')',
]);
header('Location: ' . bh_redirect_with($redirectTo, 'msg', 'password_reset'));
exit;
