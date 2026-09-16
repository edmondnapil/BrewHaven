<?php
require_once __DIR__ . '/../php/auth_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=UTF-8');

    if (!isset($conn) || mysqli_connect_errno()) {
        echo 'Database connection failed';
        exit;
    }

    $lastname       = sanitizeInput($_POST['lastname'] ?? '');
    $firstname      = sanitizeInput($_POST['firstname'] ?? '');
    $middlename     = sanitizeInput($_POST['middlename'] ?? '');
    $extension      = sanitizeInput($_POST['extension'] ?? '');
    $birth_date     = sanitizeInput($_POST['birth_date'] ?? '');
    $age            = intval($_POST['age'] ?? 0);
    $gender         = sanitizeInput($_POST['gender'] ?? '');
    $email          = sanitizeInput($_POST['email'] ?? '');
    $username       = sanitizeInput($_POST['username'] ?? '');
    $password       = $_POST['password'] ?? '';
    $repassword     = $_POST['repassword'] ?? '';
    $street         = sanitizeInput($_POST['street'] ?? '');
    $barangay       = sanitizeInput($_POST['barangay'] ?? '');
    $city           = sanitizeInput($_POST['city_municipality'] ?? '');
    $province       = sanitizeInput($_POST['province'] ?? '');
    $zipcode        = sanitizeInput($_POST['zipcode'] ?? '');
    $country        = sanitizeInput($_POST['country'] ?? '');
    $auth_question1 = sanitizeInput($_POST['auth_question1'] ?? '');
    $auth_answer1   = $_POST['auth_answer1'] ?? '';
    $auth_question2 = sanitizeInput($_POST['auth_question2'] ?? '');
    $auth_answer2   = $_POST['auth_answer2'] ?? '';
    $auth_question3 = sanitizeInput($_POST['auth_question3'] ?? '');
    $auth_answer3   = $_POST['auth_answer3'] ?? '';

    if (
        empty($lastname) || empty($firstname) || empty($birth_date) ||
        empty($age) || empty($gender) || empty($email) || empty($username) || empty($password)
    ) {
        echo 'All required fields must be filled';
        mysqli_close($conn);
        exit;
    }

    if ($password !== $repassword) {
        echo 'Passwords do not match';
        mysqli_close($conn);
        exit;
    }

    $profileCheck = bh_validate_user_profile([
        'firstname' => $_POST['firstname'] ?? '',
        'lastname' => $_POST['lastname'] ?? '',
        'middlename' => $_POST['middlename'] ?? '',
        'extension' => $_POST['extension'] ?? '',
        'birth_date' => $_POST['birth_date'] ?? '',
        'age' => (int)($_POST['age'] ?? 0),
        'gender' => $_POST['gender'] ?? '',
        'email' => $_POST['email'] ?? '',
        'username' => $_POST['username'] ?? '',
        'street' => $_POST['street'] ?? '',
        'barangay' => $_POST['barangay'] ?? '',
        'city_municipality' => $_POST['city_municipality'] ?? '',
        'province' => $_POST['province'] ?? '',
        'zipcode' => $_POST['zipcode'] ?? '',
        'country' => $_POST['country'] ?? '',
    ], true);
    if (!$profileCheck['valid']) {
        echo $profileCheck['error'];
        mysqli_close($conn);
        exit;
    }
    $age = (int)$profileCheck['calculated_age'];

    $pwCheck = bh_validate_password_strength($password);
    if (!$pwCheck['valid']) {
        echo $pwCheck['error'];
        mysqli_close($conn);
        exit;
    }

    if ($age < 18) {
        echo 'Must be at least 18 years old';
        mysqli_close($conn);
        exit;
    }

    if (
        empty($auth_question1) || empty($auth_answer1) ||
        empty($auth_question2) || empty($auth_answer2) ||
        empty($auth_question3) || empty($auth_answer3)
    ) {
        echo 'All three security questions and answers are required';
        mysqli_close($conn);
        exit;
    }

    if ($auth_question1 === $auth_question2 || $auth_question1 === $auth_question3 || $auth_question2 === $auth_question3) {
        echo 'Security questions must all be different';
        mysqli_close($conn);
        exit;
    }

    $checks = [
        'Username'  => ['sql' => "SELECT id_number FROM users WHERE username = ? LIMIT 1", 'value' => $username],
        'Email'     => ['sql' => "SELECT id_number FROM users WHERE email = ? LIMIT 1", 'value' => $email],
    ];

    foreach ($checks as $field => $check) {
        $stmt = mysqli_prepare($conn, $check['sql']);
        mysqli_stmt_bind_param($stmt, 's', $check['value']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        if ($exists) {
            echo "$field already exists";
            mysqli_close($conn);
            exit;
        }
    }

    $hashedPassword = hashPassword($password);
    $hashedAnswer1  = hashPassword($auth_answer1);
    $hashedAnswer2  = hashPassword($auth_answer2);
    $hashedAnswer3  = hashPassword($auth_answer3);
    $role   = 'customer';
    $approvalStatus = 'pending';
    // The internal id_number PK is always server-generated, never user-typed —
    // consistent with how staff accounts get theirs in admin_create_staff.php.
    // One unified ID for every account type; there is no separate Customer ID.
    $idnumber = generate_next_id_number();

    mysqli_begin_transaction($conn);

    try {
        $sql = "INSERT INTO users (
                    id_number, lastname, firstname, middlename, extension,
                    birth_date, age, gender, email, username, password, role, approval_status,
                    street, barangay, city_municipality, province, zipcode, country,
                    auth_question1, auth_answer1, auth_question2, auth_answer2, auth_question3, auth_answer3,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception('Error preparing insert: ' . mysqli_error($conn));
        }
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssissssssssssssssssss',
            $idnumber, $lastname, $firstname, $middlename, $extension,
            $birth_date, $age, $gender, $email, $username, $hashedPassword, $role, $approvalStatus,
            $street, $barangay, $city, $province, $zipcode, $country,
            $auth_question1, $hashedAnswer1, $auth_question2, $hashedAnswer2, $auth_question3, $hashedAnswer3
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error inserting user: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        log_security_event('registration_submitted', $idnumber, $idnumber, 'New customer registration pending approval');
        echo 'success';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo 'Registration failed: ' . $e->getMessage();
    }

    mysqli_close($conn);
    exit;
}

// Preview only: the actual id_number is (re)generated at submit time above, so
// a concurrent registration can't collide with this preview.
$nextIdNumberPreview = generate_next_id_number();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Brew Haven</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/register.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>

<body class="bg-register">


    <header class="main-header">
        <div class="header-content">
            <h1><img src="../images/brew-haven-logo.svg" alt="" style="width:1.2em;height:1.2em;vertical-align:-0.22em;margin-right:0.3rem;"> Brew Haven</h1>
            <div class="header-actions">
                <a href="index.php" class="bh-btn bh-btn-secondary">Home</a>
                <a href="login.php" class="bh-btn bh-btn-primary">Login</a>
            </div>
        </div>
    </header>

    <main class="hero">
        <div class="hero-inner">
            <!-- Left Side - Registration Form -->
            <div class="left-side">
                <div class="form-container">
                    <form class="form-box register-form" autocomplete="off" method="post" action="register.php">
                        <h2 class="full-row">Create Your Brew Haven Account</h2>
                        <!-- Personal Information Section -->
                        <h2 class="full-row">Personal Information</h2>
                        <div class="register-grid">
                            <div>
                                <label for="idnumber">ID Number:</label>
                                <input id="idnumber" type="text" value="<?php echo htmlspecialchars($nextIdNumberPreview); ?>" readonly>
                            </div>
                            <div>
                                <label for="lastname">Last Name<span style="color:red">*</span>:</label>
                                <input id="lastname" name="lastname" type="text" placeholder="Dela Cruz">
                                <div class="error-message" id="lastname-error"></div>
                            </div>
                            <div>
                                <label for="firstname">First Name<span style="color:red">*</span>:</label>
                                <input id="firstname" name="firstname" type="text" placeholder="Juan">
                                <div class="error-message" id="firstname-error"></div>
                            </div>
                            <div>
                                <label for="middlename">Middle Name<span class="optional">(optional)</span>:</label>
                                <input id="middlename" name="middlename" type="text" placeholder="Santos">
                                <div class="error-message" id="middlename-error"></div>
                            </div>
                            <div>
                                <label for="extension">Extension<span class="optional">(optional)</span>:</label>
                                <input id="extension" name="extension" type="text" placeholder="Jr., Sr., III">
                                <div class="error-message" id="extension-error"></div>
                            </div>
                            <div>
                                <label for="birth_date">Birth Date<span style="color:red">*</span>:</label>
                                <input id="birth_date" name="birth_date" type="date">
                                <div class="error-message" id="birth_date-error"></div>
                            </div>
                            <div>
                                <label for="age">Age<span style="color:red">*</span>:</label>
                                <input id="age" name="age" type="number" min="18" max="100" readonly>
                                <div class="error-message" id="age-error"></div>
                            </div>
                            <div>
                                <label for="gender">Sex<span style="color:red">*</span>:</label>
                                <select id="gender" name="gender">
                                    <option value="">-- Select Sex --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <div class="error-message" id="gender-error"></div>
                            </div>
                        </div>

                        <!-- Account Information Section -->
                        <h2 class="full-row">Account Information</h2>
                        <div class="register-grid">
                            <div>
                                <label for="email">Email<span style="color:red">*</span>:</label>
                                <input id="email" name="email" type="" placeholder="juan.delacruz@example.com">
                                <div class="error-message" id="email-error"></div>
                            </div>
                            <div>
                                <label for="username">Username<span style="color:red">*</span>:</label>
                                <input id="username" name="username" type="text" placeholder="juandelacruz1234">
                                <div class="error-message" id="username-error"></div>
                            </div>
                            <div>
                                <label for="password">Password<span style="color:red">*</span>:</label>
                                <div class="input-with-toggle">
                                    <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters">
                                    <i class="fa-solid fa-eye togele-eye" data-target="password"></i>
                                </div>
                                <!-- Password Strength Container -->
                                <div class="password-strength-container">
                                    <div class="password-strength-bar">
                                        <div class="strength-bar"></div>
                                    </div>
                                    <div class="password-strength-message"></div>
                                </div>
                                <div class="error-message" id="password-error"></div>
                            </div>
                            <div>
                                <label for="repassword">Confirm Password<span style="color:red">*</span>:</label>
                                <div class="input-with-toggle">
                                    <input id="repassword" name="repassword" type="password" autocomplete="new-password" placeholder="Re-enter password">
                                    <i class="fa-solid fa-eye togele-eye" data-target="repassword"></i>
                                </div>
                                <div class="error-message" id="repassword-error"></div>
                                <div id="password-match" class="password-match"></div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <h2 class="full-row">Address Information</h2>
                        <div class="register-grid address-row">
                            <div>
                                <label for="street">Purok/Street<span style="color:red">*</span>:</label>
                                <input id="street" name="street" placeholder="Purok 1, Mabuhay St.">
                                <div class="error-message" id="street-error"></div>
                            </div>
                            <div>
                                <label for="barangay">Barangay<span style="color:red">*</span>:</label>
                                <input id="barangay" name="barangay" placeholder="Brgy. Mabini">
                                <div class="error-message" id="barangay-error"></div>
                            </div>
                            <div>
                                <label for="city_municipality">City/Municipality<span style="color:red">*</span>:</label>
                                <input id="city_municipality" name="city_municipality" placeholder="Quezon City">
                                <div class="error-message" id="city_municipality-error"></div>
                            </div>
                            <div>
                                <label for="province">Province<span style="color:red">*</span>:</label>
                                <input id="province" name="province" placeholder="Metro Manila">
                                <div class="error-message" id="province-error"></div>
                            </div>
                        </div>
                        <div class="register-grid address-row">
                            <div>
                                <label for="country">Country<span style="color:red">*</span>:</label>
                                <input id="country" name="country" placeholder="Philippines">
                                <div class="error-message" id="country-error"></div>
                            </div>
                            <div>
                                <label for="zipcode">Zip Code<span style="color:red">*</span>:</label>
                                <input id="zipcode" name="zipcode" type="text" placeholder="1100">
                                <div class="error-message" id="zipcode-error"></div>
                            </div>
                        </div>

                        <div class="full-row">
                            <button type="button" id="next-button">Next</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-content">
            <span>&copy; 2025 Brew Haven</span>
        </div>
    </footer>

    <script src="../js/toggle-visibility.js?v=<?php echo @filemtime(__DIR__ . '/../js/toggle-visibility.js'); ?>"></script>
  <!-- The approved email domains, straight from php/auth_helpers.php, so the live check and the server rule are always the same list. -->
  <script>window.BH_APPROVED_EMAIL_DOMAINS = <?php echo bh_approved_email_domains_json(); ?>;</script>
    <script src="../js/register-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/register-validation.js'); ?>"></script>
    <script src="../js/register-session-storage.js?v=<?php echo @filemtime(__DIR__ . '/../js/register-session-storage.js'); ?>"></script>
</body>
</html>
