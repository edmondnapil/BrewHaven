<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . bh_page_url(role_home_page($me['role'])));
    exit;
}

$targetId = sanitizeInput($_POST['id_number'] ?? '');
$action   = $_POST['action'] ?? '';
$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? role_home_page($me['role'])));

if (!in_array($action, ['approve', 'reject', 'block', 'unblock'], true) || $targetId === '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_action'));
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'not_found'));
    exit;
}

// One authorization decision for every caller. This is what actually enforces
// the rules - role, privilege, self-protection and the blocked-account
// restriction - so a hand-made POST straight at this endpoint is refused just
// like a hidden button would have been.
$denied = bh_account_action_error($me, $target, $action);
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=' . $action . ' code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied));
    exit;
}

// A blocked Super Admin can never be unblocked as a Super Admin: the person
// unblocking it must pick its new role, Admin or Customer. Checked before any
// reason or attachment is stored.
$unblockNewRole = null;
if ($action === 'unblock' && $target['role'] === 'super_admin') {
    $unblockNewRole = $_POST['new_role'] ?? '';
    if (!in_array($unblockNewRole, bh_unblock_super_admin_roles(), true)) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', 'unblock_role_required'));
        exit;
    }
}

// Every one of these four changes an account's status, so each one carries a
// reason. Proof documents are not used here — only Delete workflows require them.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', $action);
if (!$reasonCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Approve, reject, block and unblock all change who can access an account, so
// every one of them needs the acting Admin / Super Admin's own password,
// verified on the server, before anything changes.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'account_' . $action, $targetId);
if ($confirmError !== '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
    exit;
}

// Non-delete actions never store a proof; this call still runs so unexpected
// uploads are ignored consistently by bh_store_action_proof().
$proofCheck = bh_store_action_proof('action_proof', $action, $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

$newApproval = null;
$newAccountStatus = null;
$eventType = '';
$previousState = '';
if ($action === 'approve' && $target['approval_status'] === 'pending') {
    $newApproval = 'approved'; $eventType = 'account_approved'; $previousState = 'approval=' . $target['approval_status'];
}
if ($action === 'reject' && $target['approval_status'] === 'pending') {
    $newApproval = 'rejected'; $eventType = 'account_rejected'; $previousState = 'approval=' . $target['approval_status'];
}
// Inactive accounts (first-login setup, or a waiting Super Admin) can be
// blocked too; Inactive is never used in place of Blocked.
if ($action === 'block' && in_array($target['account_status'], ['active', 'inactive'], true)) {
    $newAccountStatus = 'blocked'; $eventType = 'account_blocked'; $previousState = 'status=' . $target['account_status'];
}
// Unblocked accounts return to Active, unless they still owe first-login
// setup, in which case they are Inactive until it is finished.
$newRole = $target['role'];
if ($action === 'unblock' && $target['account_status'] === 'blocked') {
    $newAccountStatus = bh_in_first_login_setup($target) ? 'inactive' : 'active';
    $eventType = 'account_unblocked';
    $previousState = 'status=' . $target['account_status'];
    // A blocked Super Admin takes the role chosen in the Unblock dialog and
    // goes straight from Blocked to Active.
    if ($unblockNewRole !== null) {
        $newRole = $unblockNewRole;
        $newAccountStatus = 'active';
        $previousState = 'role=super_admin ' . $previousState;
    }
}
if ($newRole === 'super_admin' && $newAccountStatus !== null && $action === 'unblock') {
    // Never reachable through the check above; kept as a hard stop.
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'unblock_role_required'));
    exit;
}

if ($newApproval === null && $newAccountStatus === null) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_state'));
    exit;
}

$roleChanged = ($newRole !== $target['role']);
mysqli_begin_transaction($conn);
try {
    if ($newApproval !== null) {
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET approval_status = ? WHERE id_number = ?");
        mysqli_stmt_bind_param($updateStmt, 'ss', $newApproval, $targetId);
        $newState = 'approval=' . $newApproval;
    } else {
        $updateStmt = mysqli_prepare($conn, "UPDATE users SET role = ?, account_status = ? WHERE id_number = ?");
        mysqli_stmt_bind_param($updateStmt, 'sss', $newRole, $newAccountStatus, $targetId);
        $newState = ($roleChanged ? 'role=' . $newRole . ' ' : '') . 'status=' . $newAccountStatus;
    }
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    // An Admin gets the same default privilege set a demoted or newly created
    // Admin receives; a Customer carries no staff privileges at all.
    if ($roleChanged) {
        $clearStmt = mysqli_prepare($conn, "DELETE FROM admin_privileges WHERE admin_id_number = ?");
        mysqli_stmt_bind_param($clearStmt, 's', $targetId);
        mysqli_stmt_execute($clearStmt);
        mysqli_stmt_close($clearStmt);
        if ($newRole === 'admin') {
            $privStmt = mysqli_prepare($conn, "INSERT INTO admin_privileges (admin_id_number, privilege_key, granted_by_id_number) VALUES (?, ?, ?)");
            foreach (default_admin_privileges() as $priv) {
                mysqli_stmt_bind_param($privStmt, 'sss', $targetId, $priv, $me['id_number']);
                mysqli_stmt_execute($privStmt);
            }
            mysqli_stmt_close($privStmt);
        }
        bh_clear_active_super_admin_session($targetId);
    }
    mysqli_commit($conn);
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'change_failed'));
    exit;
}

bh_log_account_action($eventType, $me, $targetId, [
    'details'        => $roleChanged
        ? 'unblock with role change: old_role=' . $target['role'] . ' new_role=' . $newRole
        : 'target_role=' . $target['role'],
    'reason'         => $reason,
    'proof_path'     => $proofPath,
    'previous_state' => $previousState,
    'new_state'      => $newState,
]);
header('Location: ' . bh_redirect_with($redirectTo, 'msg', $eventType));
exit;
