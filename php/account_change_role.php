<?php
require_once __DIR__ . '/auth_helpers.php';
// Super Admins, and Admins holding the "Change Account Roles" add-on (checked
// by the permission matrix below).
$me = require_role(['super_admin', 'admin']);
$accountsPage = $me['role'] === 'super_admin' ? 'super-admin-accounts.php' : 'admin-accounts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/' . $accountsPage);
    exit;
}

$targetId = sanitizeInput($_POST['id_number'] ?? '');
$newRole  = sanitizeInput($_POST['new_role'] ?? '');
$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? $accountsPage));

// The roles each actor may move accounts between. A Super Admin moves accounts
// between Admin and Super Admin; an Admin (add-on privilege) only between
// User / Customer and Admin, so no Admin can ever create a Super Admin.
$roleScope = $me['role'] === 'super_admin' ? ['admin', 'super_admin'] : ['customer', 'admin'];

if ($targetId === '' || !in_array($newRole, $roleScope, true)) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'invalid_role'));
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'not_found'));
    exit;
}

// Authorization plus the two protections that matter most here, both decided
// on the server: nobody may change their own role (so the current Super Admin
// cannot demote themselves or strip their own privilege, however the request
// is crafted), and a blocked account's role cannot be changed at all.
$denied = bh_account_action_error($me, $target, 'change_role');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=change_role code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied === 'cannot_target_self' ? 'cannot_change_own_role' : $denied));
    exit;
}

// The target's current role has to be inside the same scope.
if (!in_array($target['role'], $roleScope, true)) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'forbidden'));
    exit;
}
if ($target['role'] === $newRole) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'no_change'));
    exit;
}

// A role change rewrites what an account is allowed to do, so it carries a
// reason like every other critical action.
$reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', 'change_role');
if (!$reasonCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
    exit;
}
$reason = $reasonCheck['reason'];

// Privilege-affecting action: re-verify the acting user's own password.
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'role_changed', $targetId);
if ($confirmError !== '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
    exit;
}

// Guard against demoting the last remaining Super Admin, which would leave
// the system without anyone able to create a replacement.
if ($target['role'] === 'super_admin' && $newRole === 'admin') {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'super_admin'");
    $countRow = mysqli_fetch_assoc($countResult);
    if ((int)$countRow['c'] <= 1) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', 'last_super_admin'));
        exit;
    }
}

// Optional supporting document, stored only once the action is certain to run.
$proofCheck = bh_store_action_proof('action_proof', 'change_role', $me['id_number']);
if (!$proofCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
    exit;
}
$proofPath = $proofCheck['path'];

// Only one Active Super Admin. A promoted account is Inactive and takes over
// when the current Super Admin logs out, exactly like a newly created one.
// A demoted account keeps Inactive only while it still owes first-login setup.
$newStatus = $target['account_status'];
if ($newRole === 'super_admin') {
    $newStatus = 'inactive';
} elseif ($target['account_status'] === 'inactive' && !bh_in_first_login_setup($target)) {
    $newStatus = 'active';
}

mysqli_begin_transaction($conn);
try {
    $updateStmt = mysqli_prepare($conn, "UPDATE users SET role = ?, account_status = ? WHERE id_number = ?");
    mysqli_stmt_bind_param($updateStmt, 'sss', $newRole, $newStatus, $targetId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    // Promoting to super_admin makes admin_privileges moot (super_admin always
    // passes has_privilege() implicitly); becoming an Admin starts clean with
    // the default privilege set; becoming a User / Customer carries none.
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

    mysqli_commit($conn);
    bh_log_account_action('role_changed', $me, $targetId, [
        'details'        => 'old_role=' . $target['role'] . ' new_role=' . $newRole,
        'reason'         => $reason,
        'proof_path'     => $proofPath,
        'previous_state' => 'role=' . $target['role'] . ' status=' . $target['account_status'],
        'new_state'      => 'role=' . $newRole . ' status=' . $newStatus,
    ]);
    header('Location: ' . bh_redirect_with($redirectTo, 'msg', $newRole === 'super_admin' ? 'role_changed_super_admin_inactive' : 'role_changed'));
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'change_failed'));
    exit;
}
