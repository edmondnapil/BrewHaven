<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../php/auth_helpers.php';

    if (!isset($conn) || mysqli_connect_errno()) {
        sendResponse(false, 'Database connection failed');
    }

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendResponse(false, 'Username and password are required');
    }

    $sql = "SELECT id_number, username, password, firstname, lastname, email, role, approval_status, account_status, first_login, employee_id, auth_question1 FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        log_security_event('login_failed', null, null, 'unknown username');
        sendResponse(false, 'Invalid username or password.');
    }

    if (!verifyPassword($password, $user['password'])) {
        log_security_event('login_failed', null, $user['id_number'], 'incorrect password');
        sendResponse(false, 'Invalid username or password.');
    }

    if ($user['approval_status'] !== 'approved') {
        $reason = $user['approval_status'] === 'pending'
            ? 'Your account is pending approval. Please wait for an administrator to approve your registration.'
            : 'Your registration was not approved. Please contact support.';
        log_security_event('login_blocked', null, $user['id_number'], 'approval_status=' . $user['approval_status']);
        sendResponse(false, $reason);
    }
    if ($user['account_status'] === 'blocked') {
        log_security_event('login_blocked', null, $user['id_number'], 'account_status=blocked');
        sendResponse(false, 'Your account has been blocked. Please contact support.');
    }
    if ($user['account_status'] === 'inactive') {
        if ($user['role'] === 'super_admin') {
            // A waiting Super Admin stays Inactive while the current Super
            // Admin is logged in. If that session has already ended without a
            // logout (closed browser, expired), the handover happens now.
            if (bh_get_active_super_admin_session() === null
                && bh_super_admin_handover(null, $user['id_number'], 'previous Super Admin session ended') === $user['id_number']) {
                $user['account_status'] = 'active';
            } else {
                log_security_event('login_blocked', null, $user['id_number'], 'account_status=inactive super_admin_awaiting_handover');
                sendResponse(false, 'Your Super Admin account is inactive. It will be activated automatically when the current Super Admin logs out.');
            }
        } elseif (!bh_inactive_account_may_setup($user)) {
            log_security_event('login_blocked', null, $user['id_number'], 'account_status=inactive');
            sendResponse(false, 'Your account is inactive. Please contact support.');
        }
    }

    ensure_session_started();
    session_regenerate_id(true);

    if ($user['role'] === 'super_admin') {
        $claim = bh_claim_super_admin_session($user['id_number'], session_id());
        if (!$claim['success']) {
            log_security_event('login_blocked', null, $user['id_number'], 'super_admin_already_active');
            sendResponse(false, 'Another Super Admin is currently active. Please try again after the current Super Admin logs out.');
        }
    }

    $_SESSION['user_id_number'] = $user['id_number'];
    $_SESSION['username']       = $user['username'];
    $_SESSION['firstname']      = $user['firstname'];
    $_SESSION['lastname']       = $user['lastname'];
    $_SESSION['role']           = $user['role'];
    $_SESSION['employee_id']    = $user['employee_id'];
    $_SESSION['last_activity']  = time();

    log_security_event('login_success', $user['id_number'], $user['id_number'], '');

    // Newly created accounts going through first-login setup require Email OTP verification
    $inFirstLoginSetup = bh_in_first_login_setup($user);
    if ($inFirstLoginSetup) {
        $userEmail = trim($user['email'] ?? '');
        $maskedEmail = bh_mask_email_address($userEmail);

        // Check if an active, unexpired OTP already exists for this user in session
        $hasActiveOtp = !empty($_SESSION['first_login_otp']['otp']) &&
                        (time() <= (int)($_SESSION['first_login_otp']['otp_expires_at'] ?? 0)) &&
                        (($_SESSION['first_login_otp']['user_id'] ?? '') === $user['id_number']) &&
                        (($_SESSION['first_login_otp']['purpose'] ?? '') === 'FIRST_LOGIN');

        if (!$hasActiveOtp) {
            $otpCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $_SESSION['first_login_otp'] = [
                'user_id'          => $user['id_number'],
                'email'            => $userEmail,
                'masked_email'     => $maskedEmail,
                'otp'              => $otpCode,
                'otp_created_at'   => time(),
                'otp_expires_at'   => time() + 600, // 10 minutes
                'otp_attempts'     => 0,
                'last_resend_time' => time(),
                'otp_verified'     => false,
                'purpose'          => 'FIRST_LOGIN',
            ];

            // Send OTP email
            if (!empty($userEmail)) {
                require_once __DIR__ . '/../php/smtp_mailer.php';
                bh_send_otp_email($userEmail, $otpCode, 'FIRST_LOGIN');
            }

            log_security_event('otp_sent', $user['id_number'], $user['id_number'], 'first_login_otp masked_email=' . $maskedEmail);
        }

        sendResponse(true, 'Login credentials verified. Please enter the verification code sent to your email.', [
            'user_id_number' => $user['id_number'],
            'username'       => $user['username'],
            'firstname'      => $user['firstname'],
            'lastname'       => $user['lastname'],
            'role'           => $user['role'],
            'home'           => 'first-login-otp.php'
        ]);
    }

    sendResponse(true, 'Login successful', [
        'user_id_number' => $user['id_number'],
        'username'  => $user['username'],
        'firstname' => $user['firstname'],
        'lastname'  => $user['lastname'],
        'role'      => $user['role'],
        'home'      => role_home_page($user['role'])
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brew Haven</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>
<body class="bh-auth-page">

<div class="bh-auth-topnav">
    <a href="index.php" class="bh-btn bh-btn-secondary">Home</a>
    <a href="register.php" class="bh-btn bh-btn-primary">Register</a>
</div>

<div class="bh-auth-photo">
    <div class="bh-auth-photo-bg" style="background-image:url('https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=1200&q=80');"></div>
    <div class="bh-auth-brand">
        <img src="../images/brew-haven-logo.svg" alt="" style="width:32px;height:32px;"> Brew Haven
    </div>
    <div class="bh-auth-brand-sub">Coffee Shop Management System</div>

    <div class="bh-auth-quote">
        <p>&ldquo;Every cup tells a story &mdash; ours starts with the finest beans, roasted with care.&rdquo;</p>
        <span>The Brew Haven Promise</span>
    </div>

    <div class="bh-auth-photo-footer">
        <h2>Good to see you again.</h2>
        <p>Sign in to continue to your account</p>
        <div class="bh-auth-dots"><span></span><span></span><span></span></div>
    </div>
</div>

<div class="bh-auth-form-side">
    <div class="bh-auth-card">
        <span class="bh-auth-kicker">Brew Haven</span>
        <h2>Welcome Back</h2>
        <p class="bh-auth-sub">Sign in to your account</p>

        <?php
        $msg = $_GET['msg'] ?? '';
        $alertType = '';
        $alertText = '';
        if ($msg === 'inactivity') {
            $alertType = 'warning';
            $alertText = 'Session Expired: You have been logged out because of 5 minutes of inactivity.';
        } elseif ($msg === 'session_expired') {
            $alertType = 'warning';
            $alertText = 'Your session has expired. Please sign in again.';
        } elseif ($msg === 'account_status_changed') {
            $alertType = 'error';
            $alertText = 'Your account status has changed. Please sign in again.';
        } elseif ($msg === 'logout') {
            $alertType = 'info';
            $alertText = 'You have been successfully logged out.';
        }
        ?>
        <?php if (!empty($alertText)): ?>
            <div class="bh-alert bh-alert-<?php echo $alertType; ?>" id="sessionMsgBanner" style="margin-bottom: 1.25rem; font-size: 0.88rem; line-height: 1.4; border-radius: 8px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($alertText); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" autocomplete="off" method="post" action="login.php">
            <div class="bh-input-icon">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Enter your username">
            </div>
            <div class="error-message" id="username-error"></div>

            <div class="bh-input-icon">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password">
                <i class="fa-solid fa-eye togele-eye bh-toggle-eye" data-target="password"></i>
            </div>
            <div class="error-message" id="password-error"></div>

            <div class="login-attempts" id="loginAttempts" style="display: none;">
                <p class="attempts-text">Login attempts: <span id="attemptCount">0</span>/3</p>
                <p class="timer-text" id="timerText" style="display: none;"></p>
            </div>

            <button type="submit" class="bh-auth-submit" id="loginBtn">Sign In</button>
            <p class="bh-auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</div>

<script src="../js/toggle-visibility.js?v=<?php echo @filemtime(__DIR__ . '/../js/toggle-visibility.js'); ?>"></script>
<script src="../js/login-form-clear.js?v=<?php echo @filemtime(__DIR__ . '/../js/login-form-clear.js'); ?>"></script>
<script src="../js/login-lockout.js?v=<?php echo @filemtime(__DIR__ . '/../js/login-lockout.js'); ?>"></script>
<script src="../js/login-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/login-validation.js'); ?>"></script>
</body>
</html>
