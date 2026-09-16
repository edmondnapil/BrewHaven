<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/dashboard.php');
    exit;
}

$targetId = sanitizeInput($_POST['id_number'] ?? '');
$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? role_home_page($me['role'])));
if ($targetId === '') {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'missing_target'));
    exit;
}

$target = bh_load_target_account($targetId);
if (!$target) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'not_found'));
    exit;
}

$isSelf = ($targetId === $me['id_number']);

// Single authorization decision, server-side. It covers everything the old
// inline checks did (customer self-only, Admin needs update_user_info, the
// delegated manage_super_admin_accounts path) and adds the two rules the UI
// alone could not enforce: a normal Admin may not edit a co-admin, and no
// blocked account may be edited by anyone until it is unblocked.
$denied = bh_account_action_error($me, $target, 'edit');
if ($denied !== '') {
    log_security_event('unauthorized_action_blocked', $me['id_number'], $targetId, 'action=edit code=' . $denied);
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $denied));
    exit;
}

// Editing somebody else's account information is a critical action, so it
// carries a reason. A customer correcting their own profile does not.
$reason = null;
if (!$isSelf) {
    $reasonCheck = bh_validate_action_reason($_POST['action_reason'] ?? '', 'edit');
    if (!$reasonCheck['valid']) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', $reasonCheck['error']));
        exit;
    }
    $reason = $reasonCheck['reason'];
}

// Staff edits (their own profile or somebody else's record) are a privileged
// action, so the acting Admin / Super Admin re-enters their own password before
// the update runs. A customer editing their own profile is unchanged.
if (in_array($me['role'], ['admin', 'super_admin'], true)) {
    $confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', 'account_updated', $targetId);
    if ($confirmError !== '') {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', $confirmError));
        exit;
    }
}

$currentStmt = mysqli_prepare($conn, "SELECT firstname, lastname, middlename, extension, birth_date, age, gender, email, street, barangay, city_municipality, province, zipcode, country FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($currentStmt, 's', $targetId);
mysqli_stmt_execute($currentStmt);
$currentProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt)) ?: [];
mysqli_stmt_close($currentStmt);

$profileData = $currentProfile;
foreach (['firstname', 'lastname', 'middlename', 'extension', 'birth_date', 'age', 'gender', 'email', 'street', 'barangay', 'city_municipality', 'province', 'zipcode', 'country'] as $field) {
    if (array_key_exists($field, $_POST)) {
        $profileData[$field] = $_POST[$field];
    }
}
// bh_validate_user_profile() runs the same name, birth-date, gender, address
// and approved-email-domain rules registration does, so an edit can never put
// a value into the table that registration would have refused.
$profileCheck = bh_validate_user_profile($profileData, false);
if (!$profileCheck['valid']) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', $profileCheck['error']));
    exit;
}

// A changed email must still be unique across accounts.
if (isset($_POST['email'])) {
    $newEmail = sanitizeInput($_POST['email']);
    $emailStmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE email = ? AND id_number != ? LIMIT 1");
    mysqli_stmt_bind_param($emailStmt, 'ss', $newEmail, $targetId);
    mysqli_stmt_execute($emailStmt);
    mysqli_stmt_store_result($emailStmt);
    $emailTaken = mysqli_stmt_num_rows($emailStmt) > 0;
    mysqli_stmt_close($emailStmt);
    if ($emailTaken) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', 'That email address is already in use.'));
        exit;
    }
}

// Editing somebody else's record may carry an optional supporting document,
// stored only once every check above has passed.
$proofPath = null;
if (!$isSelf) {
    $proofCheck = bh_store_action_proof('action_proof', 'edit', $me['id_number']);
    if (!$proofCheck['valid']) {
        header('Location: ' . bh_redirect_with($redirectTo, 'error', $proofCheck['error']));
        exit;
    }
    $proofPath = $proofCheck['path'];
}

// Whitelisted, non-sensitive fields only. Never password/role/status/employee_id/auth_*.
$allowedFields = ['lastname', 'firstname', 'middlename', 'extension', 'birth_date', 'age', 'gender', 'email', 'street', 'barangay', 'city_municipality', 'province', 'zipcode', 'country'];

$setParts = [];
$values = [];
$types = '';
$changedFields = [];
$beforeValues = [];
$afterValues = [];
foreach ($allowedFields as $field) {
    if (isset($_POST[$field])) {
        $newValue = ($field === 'age') ? intval($_POST[$field]) : sanitizeInput($_POST[$field]);
        $setParts[] = "$field = ?";
        $values[] = $newValue;
        $types .= ($field === 'age') ? 'i' : 's';
        $changedFields[] = $field;
        // Only fields whose value actually moved are recorded as a change, so
        // the audit entry shows what really differed instead of every field
        // the form happened to post.
        $oldValue = $currentProfile[$field] ?? '';
        if ((string)$oldValue !== (string)$newValue) {
            $beforeValues[] = $field . '=' . $oldValue;
            $afterValues[]  = $field . '=' . $newValue;
        }
    }
}

if (empty($setParts)) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'no_changes'));
    exit;
}

$sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id_number = ?";
$values[] = $targetId;
$types .= 's';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$values);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

bh_log_account_action('account_updated', $me, $targetId, [
    'details'        => 'fields: ' . implode(',', $changedFields),
    'reason'         => $reason,
    'proof_path'     => $proofPath,
    'previous_state' => implode('; ', $beforeValues),
    'new_state'      => implode('; ', $afterValues),
]);

header('Location: ' . bh_redirect_with($redirectTo, 'msg', 'updated'));
exit;
