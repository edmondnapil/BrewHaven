<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'manage_super_admin_accounts');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/super-admin-accounts.php');
    exit;
}

$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? ($me['role'] === 'super_admin' ? 'super-admin-accounts.php' : 'admin-accounts.php')));
$targetId = sanitizeInput($_POST['id_number'] ?? '');
if ($targetId === '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid'));
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'not_found'));
    exit;
}

// Authorization, self-protection (a Super Admin can never delete their own
// account) and the blocked-account restriction, all decided server-side.
$denied = bh_account_action_error($me, $target, 'delete');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=delete code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied));
    exit;
}

// Deleting an account is critical and disciplinary: reason and proof both.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', 'delete');
if (!$reasonCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Destructive action: re-verify the acting Super Admin's own password before
// anything is removed.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'account_deleted', $targetId);
if ($confirmError !== '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
    exit;
}

// Stored only after the password check, so a refused request leaves no file.
$proofCheck = bh_store_action_proof('action_proof', 'delete', $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

// Guard against deleting the last remaining Super Admin, which would leave the
// system without anyone able to create a replacement.
if ($target['role'] === 'super_admin') {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'super_admin'");
    $countRow = mysqli_fetch_assoc($countResult);
    if ((int)$countRow['c'] <= 1) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', 'last_super_admin'));
        exit;
    }
}

$deleteStmt = mysqli_prepare($conn, "DELETE FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($deleteStmt, 's', $targetId);
mysqli_stmt_execute($deleteStmt);
mysqli_stmt_close($deleteStmt);

bh_log_account_action('account_deleted', $me, $targetId, [
    'details'        => 'direct delete by ' . $me['role'] . ', target=' . trim($target['firstname'] . ' ' . $target['lastname']) . ' (' . $target['username'] . ')',
    'reason'         => $reason,
    'proof_path'     => $proofPath,
    'previous_state' => 'role=' . $target['role'] . ' status=' . $target['account_status'],
    'new_state'      => 'deleted',
]);
header('Location: ' . bh_redirect_with($redirectTo, 'msg', 'deleted'));
exit;
