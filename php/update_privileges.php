<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['super_admin']);

$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? 'super-admin-accounts.php'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectTo);
    exit;
}

$targetId = sanitizeInput($_POST['admin_id_number'] ?? '');
$selected = isset($_POST['privileges']) && is_array($_POST['privileges']) ? $_POST['privileges'] : [];
$allPrivileges = array_merge(default_admin_privileges(), default_super_admin_privileges());
$selected = array_values(array_unique(array_intersect($selected, $allPrivileges))); // never trust arbitrary keys or duplicates

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_target'));
    exit;
}

// Server-side authorization: no self-granting of privileges, and no privilege
// changes on a blocked account until it is unblocked.
$denied = bh_account_action_error($me, $target, 'manage_privileges');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=manage_privileges code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied));
    exit;
}
if ($target['role'] !== 'admin') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_target'));
    exit;
}

// Changing what an account is allowed to do is a critical action.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', 'manage_privileges');
if (!$reasonCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Privilege-affecting action: re-verify the acting Super Admin's own password.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'privileges_updated', $targetId);
if ($confirmError !== '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
    exit;
}

// Optional supporting document, stored only once the action is certain to run.
// Every grant/revoke row below carries the same attachment.
$proofCheck = bh_store_action_proof('action_proof', 'manage_privileges', $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

$existingResult = mysqli_prepare($conn, "SELECT privilege_key FROM admin_privileges WHERE admin_id_number = ?");
mysqli_stmt_bind_param($existingResult, 's', $targetId);
mysqli_stmt_execute($existingResult);
$existingRows = mysqli_stmt_get_result($existingResult);
$existing = [];
while ($row = mysqli_fetch_assoc($existingRows)) { $existing[] = $row['privilege_key']; }
mysqli_stmt_close($existingResult);
$existing = array_values(array_unique($existing));

$toGrant = array_values(array_diff($selected, $existing));
$toRevoke = array_values(array_diff($existing, $selected));

foreach ($toGrant as $priv) {
    $insertStmt = mysqli_prepare($conn, "INSERT IGNORE INTO admin_privileges (admin_id_number, privilege_key, granted_by_id_number) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($insertStmt, 'sss', $targetId, $priv, $me['id_number']);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
    bh_log_account_action('privilege_granted', $me, $targetId, [
        'details'        => 'privilege=' . $priv,
        'reason'         => $reason,
        'proof_path'     => $proofPath,
        'previous_state' => 'not granted',
        'new_state'      => 'granted: ' . bh_privilege_label($priv),
    ]);
}
foreach ($toRevoke as $priv) {
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM admin_privileges WHERE admin_id_number = ? AND privilege_key = ?");
    mysqli_stmt_bind_param($deleteStmt, 'ss', $targetId, $priv);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);
    bh_log_account_action('privilege_revoked', $me, $targetId, [
        'details'        => 'privilege=' . $priv,
        'reason'         => $reason,
        'proof_path'     => $proofPath,
        'previous_state' => 'granted: ' . bh_privilege_label($priv),
        'new_state'      => 'revoked',
    ]);
}

header('Location: ' . bh_redirect_with($redirectTo, 'msg', 'privileges_updated'));
exit;
