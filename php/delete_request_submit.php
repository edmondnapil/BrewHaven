<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-accounts.php');
    exit;
}

$targetId = sanitizeInput($_POST['id_number'] ?? '');

if ($targetId === '') {
    header('Location: ../html/admin-accounts.php?error=invalid');
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ../html/admin-accounts.php?error=not_found');
    exit;
}

// Server-side authorization. Confirms the Admin holds manage_users, that the
// target is inside their scope (customers only, never a co-admin or a Super
// Admin), that they are not targeting themselves, and that the account is not
// already blocked.
$denied = bh_account_action_error($me, $target, 'delete_request');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=delete_request code=' . $denied);
    header('Location: ../html/admin-accounts.php?error=' . urlencode($denied));
    exit;
}
// An Admin's deletion-request scope is customer accounts only.
if ($target['role'] !== 'customer') {
    header('Location: ../html/admin-accounts.php?error=forbidden');
    exit;
}

// Reason has always been required here; it is now validated and sanitised
// through the shared critical-action rules rather than an empty-string check.
$reasonCheck = bh_validate_action_reason($_POST['reason'] ?? '', 'delete_request');
if (!$reasonCheck['valid']) {
    header('Location: ../html/admin-accounts.php?error=' . urlencode($reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Requesting a deletion is a sensitive request, so the acting Admin re-enters
// their own password before it is filed.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'delete_request_submitted', $targetId);
if ($confirmError !== '') {
    header('Location: ../html/admin-accounts.php?error=' . urlencode($confirmError));
    exit;
}

// A deletion request requires both a reason and a supporting proof document.
// Stored only after the password check, so a refused request leaves no file.
$proofCheck = bh_store_action_proof('action_proof', 'delete_request', $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ../html/admin-accounts.php?error=' . urlencode($proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

// The proof column arrives with the account-management migration; fall back to
// the original insert if it has not been applied yet, so filing a request
// never fails outright on an un-migrated database.
$insertStmt = mysqli_prepare($conn, "INSERT INTO delete_requests (target_id_number, requested_by_id_number, reason, proof_path, status) VALUES (?, ?, ?, ?, 'pending')");
if ($insertStmt) {
    mysqli_stmt_bind_param($insertStmt, 'ssss', $targetId, $me['id_number'], $reason, $proofPath);
} else {
    $insertStmt = mysqli_prepare($conn, "INSERT INTO delete_requests (target_id_number, requested_by_id_number, reason, status) VALUES (?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($insertStmt, 'sss', $targetId, $me['id_number'], $reason);
}
mysqli_stmt_execute($insertStmt);
$requestId = mysqli_insert_id($conn);
mysqli_stmt_close($insertStmt);

bh_log_account_action('delete_request_submitted', $me, $targetId, [
    'details'        => 'request_id=' . $requestId . ' target_role=' . $target['role'],
    'reason'         => $reason,
    'proof_path'     => $proofPath,
    'previous_state' => 'status=' . $target['account_status'],
    'new_state'      => 'delete_request=pending',
]);
header('Location: ../html/admin-accounts.php?msg=delete_request_submitted');
exit;
