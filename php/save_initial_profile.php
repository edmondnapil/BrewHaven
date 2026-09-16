<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_login(false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/complete-profile.php');
    exit;
}

// Staff, plus any User/Customer still inside the first-login setup
require_first_login_setup($me);

$firstname  = sanitizeInput($_POST['firstname'] ?? '');
$lastname   = sanitizeInput($_POST['lastname'] ?? '');
$middlename = sanitizeInput($_POST['middlename'] ?? '');
$birth_date = sanitizeInput($_POST['birth_date'] ?? '');
$age        = intval($_POST['age'] ?? 0);
$gender     = sanitizeInput($_POST['gender'] ?? '');
$email      = sanitizeInput($_POST['email'] ?? '');

$profileCheck = bh_validate_user_profile([
    'firstname' => $_POST['firstname'] ?? '',
    'lastname' => $_POST['lastname'] ?? '',
    'middlename' => $_POST['middlename'] ?? '',
    'birth_date' => $_POST['birth_date'] ?? '',
    'age' => (int)($_POST['age'] ?? 0),
    'gender' => $_POST['gender'] ?? '',
    'email' => $_POST['email'] ?? '',
    'street' => $_POST['street'] ?? '',
    'barangay' => $_POST['barangay'] ?? '',
    'city_municipality' => $_POST['city_municipality'] ?? '',
    'province' => $_POST['province'] ?? '',
    'zipcode' => $_POST['zipcode'] ?? '',
    'country' => $_POST['country'] ?? '',
// Address is required for every account finishing its first login, not just
// customers, and it is validated by exactly the same rules registration uses.
], true);
if (!$profileCheck['valid']) {
    header('Location: ../html/complete-profile.php?error=' . urlencode($profileCheck['error']));
    exit;
}
$age = (int)$profileCheck['calculated_age'];

if (empty($firstname) || empty($lastname) || empty($birth_date) || empty($gender) || empty($email)) {
    header('Location: ../html/complete-profile.php?error=missing_fields');
    exit;
}

$fnCheck = bh_validate_name($firstname, 'First Name', true);
if (!$fnCheck['valid']) {
    header('Location: ../html/complete-profile.php?error=' . urlencode($fnCheck['error']));
    exit;
}

$lnCheck = bh_validate_name($lastname, 'Last Name', true);
if (!$lnCheck['valid']) {
    header('Location: ../html/complete-profile.php?error=' . urlencode($lnCheck['error']));
    exit;
}

if (!empty($middlename)) {
    $mnCheck = bh_validate_name($middlename, 'Middle Name', false);
    if (!$mnCheck['valid']) {
        header('Location: ../html/complete-profile.php?error=' . urlencode($mnCheck['error']));
        exit;
    }
}

$dobCheck = bh_validate_birthdate_and_age($birth_date, $age);
if (!$dobCheck['valid']) {
    header('Location: ../html/complete-profile.php?error=' . urlencode($dobCheck['error']));
    exit;
}
$age = $dobCheck['calculated_age'];

// bh_validate_user_profile() above already applied the shared email rules,
// including the approved-domain list. filter_var is kept as a last shape check.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../html/complete-profile.php?error=invalid_email');
    exit;
}

// Check email uniqueness excluding current user
$stmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE email = ? AND id_number != ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $email, $me['id_number']);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header('Location: ../html/complete-profile.php?error=email_exists');
    exit;
}
mysqli_stmt_close($stmt);

// Every account completes its address in this same step, whatever the role.
// Staff used to skip it, which left Admin and Super Admin rows holding the
// 'N/A' placeholders the account-creation endpoint inserts.
$street    = sanitizeInput($_POST['street'] ?? '');
$barangay  = sanitizeInput($_POST['barangay'] ?? '');
$city      = sanitizeInput($_POST['city_municipality'] ?? '');
$province  = sanitizeInput($_POST['province'] ?? '');
$zipcode   = sanitizeInput($_POST['zipcode'] ?? '');
$country   = sanitizeInput($_POST['country'] ?? '');

$addrCheck = bh_validate_address($street, $barangay, $city, $province, $zipcode, $country);
if (!$addrCheck['valid']) {
    header('Location: ../html/complete-profile.php?error=' . urlencode($addrCheck['error']));
    exit;
}

// Profile complete -> first_login state 2 (password change pending)
$updateSql = "UPDATE users SET firstname = ?, lastname = ?, middlename = ?, birth_date = ?, age = ?, gender = ?, email = ?, street = ?, barangay = ?, city_municipality = ?, province = ?, zipcode = ?, country = ?, first_login = 2 WHERE id_number = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'ssssisssssssss', $firstname, $lastname, $middlename, $birth_date, $age, $gender, $email, $street, $barangay, $city, $province, $zipcode, $country, $me['id_number']);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);

    // Update session values
    $_SESSION['firstname'] = $firstname;
    $_SESSION['lastname']  = $lastname;

    log_security_event('account_updated', $me['id_number'], $me['id_number'], 'initial profile setup completed');
    header('Location: ../html/change-password-setup.php');
    exit;
} else {
    mysqli_stmt_close($updateStmt);
    header('Location: ../html/complete-profile.php?error=save_failed');
    exit;
}
