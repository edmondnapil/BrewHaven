<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'manage_deletion_requests');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/super-admin-deletion-requests.php');
    exit;
}

$requestId = intval($_POST['request_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header('Location: ../html/super-admin-deletion-requests.php?error=invalid');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM delete_requests WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $requestId);
mysqli_stmt_execute($stmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$request || $request['status'] !== 'pending') {
    header('Location: ../html/super-admin-deletion-requests.php?error=not_pending');
    exit;
}

// Approving or rejecting a deletion request decides whether an account
// survives, so the reviewer records why either way.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', $action);
if (!$reasonCheck['valid']) {
    header('Location: ../html/super-admin-deletion-requests.php?error=' . urlencode($reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Approving (permanent delete) and rejecting a deletion request both decide an
// account's fate, so both need the acting reviewer's own password.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'delete_request_' . ($action === 'approve' ? 'approved' : 'rejected'), $request['target_id_number']);
if ($confirmError !== '') {
    header('Location: ../html/super-admin-deletion-requests.php?error=' . urlencode($confirmError));
    exit;
}

// The reviewer may attach their own supporting document (optional). When they
// do, it is what their decision is logged with; otherwise the document the
// requesting Admin filed stays attached, as before.
$proofCheck = bh_store_action_proof('action_proof', 'delete_request_' . $action, $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ../html/super-admin-deletion-requests.php?error=' . urlencode($proofCheck['error']));
    exit;
}
$reviewProof = $proofCheck['path'] ?? ($request['proof_path'] ?? null);

if ($action === 'reject') {
    $updateStmt = mysqli_prepare($conn, "UPDATE delete_requests SET status = 'rejected', reviewed_by_id_number = ?, reviewed_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'si', $me['id_number'], $requestId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
    bh_log_account_action('delete_request_rejected', $me, $request['target_id_number'], [
        'details'        => 'request_id=' . $requestId,
        'reason'         => $reason,
        'proof_path'     => $reviewProof,
        'previous_state' => 'delete_request=pending',
        'new_state'      => 'delete_request=rejected',
    ]);
    header('Location: ../html/super-admin-deletion-requests.php?msg=rejected');
    exit;
}

// approve
mysqli_begin_transaction($conn);
try {
    $updateStmt = mysqli_prepare($conn, "UPDATE delete_requests SET status = 'approved', reviewed_by_id_number = ?, reviewed_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'si', $me['id_number'], $requestId);
    if (!mysqli_stmt_execute($updateStmt)) {
        throw new Exception('Failed to update request');
    }
    mysqli_stmt_close($updateStmt);

    $deleteStmt = mysqli_prepare($conn, "DELETE FROM users WHERE id_number = ?");
    mysqli_stmt_bind_param($deleteStmt, 's', $request['target_id_number']);
    if (!mysqli_stmt_execute($deleteStmt)) {
        throw new Exception('Failed to delete user');
    }
    mysqli_stmt_close($deleteStmt);

    mysqli_commit($conn);
    bh_log_account_action('delete_request_approved', $me, $request['target_id_number'], [
        'details'        => 'request_id=' . $requestId,
        'reason'         => $reason,
        'proof_path'     => $reviewProof,
        'previous_state' => 'delete_request=pending',
        'new_state'      => 'delete_request=approved',
    ]);
    bh_log_account_action('account_deleted', $me, $request['target_id_number'], [
        'details'        => 'via delete request #' . $requestId,
        // The requesting Admin's own reason and evidence travel with the
        // deletion, so the audit trail shows why the account was removed.
        'reason'         => $request['reason'] ?? $reason,
        'proof_path'     => ($request['proof_path'] ?? null) ?: $reviewProof,
        'previous_state' => 'account exists',
        'new_state'      => 'deleted',
    ]);
    header('Location: ../html/super-admin-deletion-requests.php?msg=approved');
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: ../html/super-admin-deletion-requests.php?error=approve_failed');
    exit;
}
