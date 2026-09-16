<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['super_admin']);

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

// Same error-handling contract as customer registration: field-specific
// messages instead of one generic banner, and no full page reload for AJAX
// callers so the admin never loses what they already typed.
function bh_staff_error($field, $message, $isAjax, $legacyErrorCode) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'field' => $field, 'message' => $message]);
        exit;
    }
    header('Location: ../html/super-admin-accounts.php?error=' . $legacyErrorCode);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/super-admin-accounts.php');
    exit;
}

$role = $_POST['role'] ?? '';
if (!in_array($role, ['admin', 'super_admin', 'customer'], true)) {
    bh_staff_error('role', 'Please select a valid role.', $isAjax, 'invalid_role');
}

$username   = sanitizeInput($_POST['username'] ?? '');
$email      = sanitizeInput($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$repassword = $_POST['repassword'] ?? '';

if ($username === '') {
    bh_staff_error('username', 'Username is required.', $isAjax, 'missing_fields');
}
if (strlen($username) < 4) {
    bh_staff_error('username', 'Username must be at least 4 characters.', $isAjax, 'missing_fields');
}
if (strlen($username) > 20) {
    bh_staff_error('username', 'Username must be less than 20 characters.', $isAjax, 'invalid_username');
}
if (!preg_match('/^[a-z]+[0-9]+$/', $username)) {
    bh_staff_error('username', 'Invalid format. Must start with a lowercase letter and look like "juan1234".', $isAjax, 'invalid_username');
}

if ($password === '') {
    bh_staff_error('password', 'Password is required.', $isAjax, 'missing_fields');
}
if ($repassword === '') {
    bh_staff_error('repassword', 'Please confirm your password.', $isAjax, 'missing_fields');
}
if ($password !== $repassword) {
    bh_staff_error('repassword', 'Passwords do not match.', $isAjax, 'password_mismatch');
}
$pwCheck = bh_validate_password_strength($password);
if (!$pwCheck['valid']) {
    bh_staff_error('password', $pwCheck['error'], $isAjax, 'weak_password');
}

// Same email rules the rest of the system already applies: required, a valid
// address (filter_var, as in save_initial_profile.php) and not already taken
// (the duplicate check customer registration performs).
if ($email === '') {
    bh_staff_error('email', 'Email is required.', $isAjax, 'missing_fields');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    bh_staff_error('email', 'Please provide a valid email address.', $isAjax, 'missing_fields');
}
// Same shape rules and same approved-domain list registration applies, so an
// account created from the console can never hold an address a self-registered
// customer would have been refused.
$emailCheck = bh_validate_email_address($email);
if (!$emailCheck['valid']) {
    bh_staff_error('email', $emailCheck['error'], $isAjax, 'invalid_email');
}
$emailStmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($emailStmt, 's', $email);
mysqli_stmt_execute($emailStmt);
mysqli_stmt_store_result($emailStmt);
$emailExists = mysqli_stmt_num_rows($emailStmt) > 0;
mysqli_stmt_close($emailStmt);
if ($emailExists) {
    bh_staff_error('email', 'Email already exists.', $isAjax, 'duplicate_account');
}

// Check if username already exists
$stmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if ($exists) {
    bh_staff_error('username', 'Username already exists.', $isAjax, 'duplicate_account');
}

// Creating an account grants system access, so the acting Super Admin
// re-enters their own password before the account is created.
$confirmAction = ($role === 'customer') ? 'customer_account_created' : 'staff_account_created';
$confirmError = bh_confirm_password($me, $_POST['confirm_password'] ?? '', $confirmAction, null);
if ($confirmError !== '') {
    bh_staff_error('confirm_password', bh_confirm_error_message($confirmError), $isAjax, $confirmError);
}

// Default placeholder profile values until completed by the new account holder during first login
$lastname   = '';
$firstname  = '';
$middlename = '';
$birth_date = '2000-01-01';
$age        = 0;
$gender     = 'Other';
$street     = 'N/A';
$barangay   = 'N/A';
$city       = 'N/A';
$province   = 'N/A';
$zipcode    = '0000';
$country    = 'Philippines';

// One unified ID for every account type, from the single global YYYY-####
// sequence. The backend is the only place it is decided; nothing the form
// posts can influence it.
$idNumber = generate_next_id_number();
$isCustomerAccount = ($role === 'customer');
$hashedPassword = hashPassword($password);
$approvalStatus = 'approved'; // Super Admin creating the account IS the approval
$firstLogin = 1;
// Every console-created account starts Inactive. An Admin/Customer becomes
// Active on finishing first-login setup; a new Super Admin stays Inactive
// until the current (Active) Super Admin logs out and hands over.
$accountStatus = 'inactive';

mysqli_begin_transaction($conn);
try {
    $sql = "INSERT INTO users (
                id_number, lastname, firstname, middlename, extension,
                birth_date, age, gender, email, username, password, role, approval_status, account_status, first_login,
                street, barangay, city_municipality, province, zipcode, country,
                created_at
            ) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        'sssssisssssssissssss',
        $idNumber, $lastname, $firstname, $middlename,
        $birth_date, $age, $gender, $email, $username, $hashedPassword, $role, $approvalStatus, $accountStatus, $firstLogin,
        $street, $barangay, $city, $province, $zipcode, $country
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create the account');
    }
    mysqli_stmt_close($stmt);

    if ($role === 'admin') {
        $privStmt = mysqli_prepare($conn, "INSERT INTO admin_privileges (admin_id_number, privilege_key, granted_by_id_number) VALUES (?, ?, ?)");
        foreach (default_admin_privileges() as $priv) {
            mysqli_stmt_bind_param($privStmt, 'sss', $idNumber, $priv, $me['id_number']);
            mysqli_stmt_execute($privStmt);
        }
        mysqli_stmt_close($privStmt);
    }

    mysqli_commit($conn);
    log_security_event(
        $isCustomerAccount ? 'customer_account_created' : 'staff_account_created',
        $me['id_number'],
        $idNumber,
        'role=' . $role . ' id_number=' . $idNumber . ' status=' . $accountStatus
    );

    $createdMsg = $isCustomerAccount ? 'customer_created' : ($role === 'super_admin' ? 'super_admin_created_inactive' : 'staff_created');
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id_number' => $idNumber, 'msg' => $createdMsg]);
        exit;
    }
    header('Location: ../html/super-admin-accounts.php?msg=' . $createdMsg);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    bh_staff_error('username', 'Could not create the account. Please try again.', $isAjax, 'create_failed');
}
