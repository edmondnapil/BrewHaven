<?php
require_once __DIR__ . '/../php/auth_helpers.php';
require_once __DIR__ . '/../php/smtp_mailer.php';
ensure_session_started();

function bh_mask_email($email) {
    if (empty($email) || strpos($email, '@') === false) return '***';
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = substr($user, 0, 1) . '***';
    } else {
        $maskedUser = substr($user, 0, 1) . str_repeat('*', max(3, $len - 2)) . substr($user, -1);
    }
    return $maskedUser . '@' . $domain;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'find_account_by_id') {
        $idInput = sanitizeInput($_POST['id_number'] ?? '');
        if ($idInput === '') {
            echo json_encode(['success' => false, 'message' => 'Please enter your ID Number.']);
            exit;
        }

        $sql = "SELECT id_number, email, username, role, approval_status, account_status, auth_question1, auth_question2, auth_question3 FROM users WHERE id_number = ? OR employee_id = ? OR customer_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $idInput, $idInput, $idInput);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'No account was found for this ID Number. Please check your ID Number and try again.']);
            exit;
        }

        if ($user['account_status'] === 'blocked' || $user['approval_status'] === 'rejected') {
            log_security_event('password_reset_blocked', null, $user['id_number'], 'approval=' . $user['approval_status'] . ' account=' . $user['account_status']);
            $blockedMessage = $user['account_status'] === 'blocked'
                ? 'This account is blocked. Unable to reset password. Please contact support.'
                : 'This account\'s registration was rejected. Unable to reset password. Please contact support.';
            echo json_encode(['success' => false, 'message' => $blockedMessage]);
            exit;
        }

        $userEmail = trim($user['email'] ?? '');
        if (empty($userEmail)) {
            echo json_encode(['success' => false, 'message' => 'No registered email address found for this account. Please contact support.']);
            exit;
        }

        $otpCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $maskedEmail    = bh_mask_email($userEmail);
        $maskedId       = bh_mask_id_number($user['id_number']);
        $maskedUsername = bh_mask_username($user['username'] ?? '');

        $_SESSION['pwd_reset'] = [
            'id_number'         => $user['id_number'],
            'username'          => $user['username'] ?? '',
            'email'             => $userEmail,
            'masked_email'      => $maskedEmail,
            'masked_id'         => $maskedId,
            'masked_username'   => $maskedUsername,
            'otp'               => $otpCode,
            'otp_created_at'    => time(),
            'otp_expires_at'    => time() + 600, // 10 minutes
            'otp_attempts'      => 0,
            'last_resend_time'  => time(),
            'otp_verified'      => false,
            'security_verified' => false,
            'purpose'           => 'FORGOT_PASSWORD',
        ];

        // Send real OTP email via SMTP
        $sent = bh_send_otp_email($userEmail, $otpCode, 'FORGOT_PASSWORD');
        if (!$sent) {
            log_security_event('otp_send_failed', null, $user['id_number'], 'find_account send failed');
            echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please contact support or try again later.']);
            exit;
        }

        log_security_event('otp_sent', null, $user['id_number'], 'masked_email=' . $maskedEmail);

        echo json_encode([
            'success'         => true,
            'message'         => 'A verification code has been sent to your registered email address.',
            'masked_email'    => $maskedEmail,
            'masked_id'       => $maskedId,
            'masked_username' => $maskedUsername,
            'cooldown'        => 60
        ]);
        exit;
    }

    if ($action === 'resend_otp') {
        if (empty($_SESSION['pwd_reset']['id_number'])) {
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please start over.', 'expired_session' => true]);
            exit;
        }

        $pwdReset = &$_SESSION['pwd_reset'];
        $lastResend = $pwdReset['last_resend_time'] ?? 0;
        $elapsed = time() - $lastResend;
        if ($elapsed < 60) {
            $cooldownLeft = 60 - $elapsed;
            echo json_encode([
                'success' => false,
                'message' => 'Please wait ' . $cooldownLeft . ' seconds before requesting another code.',
                'cooldown' => $cooldownLeft
            ]);
            exit;
        }

        // Generate NEW OTP and invalidate old OTP
        $otpCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $pwdReset['otp']            = $otpCode;
        $pwdReset['otp_created_at'] = time();
        $pwdReset['otp_expires_at'] = time() + 600;
        $pwdReset['last_resend_time'] = time();

        // Send new OTP email via SMTP
        $sent = bh_send_otp_email($pwdReset['email'], $otpCode, 'FORGOT_PASSWORD');
        if (!$sent) {
            log_security_event('otp_send_failed', null, $pwdReset['id_number'], 'resend send failed');
            echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
            exit;
        }

        log_security_event('otp_resent', null, $pwdReset['id_number'], 'masked_email=' . $pwdReset['masked_email']);

        echo json_encode([
            'success' => true,
            'message' => 'A new verification code has been sent to your email address.',
            'cooldown' => 60
        ]);
        exit;
    }

    if ($action === 'verify_otp') {
        if (empty($_SESSION['pwd_reset']['id_number'])) {
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please start over.', 'expired_session' => true]);
            exit;
        }

        $pwdReset = &$_SESSION['pwd_reset'];
        $otpInput = trim($_POST['otp_code'] ?? '');

        if ($pwdReset['otp_attempts'] >= 5) {
            log_security_event('otp_lockout', null, $pwdReset['id_number'], 'max_attempts_exceeded');
            unset($_SESSION['pwd_reset']);
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please start over.', 'expired_session' => true]);
            exit;
        }

        if (time() > $pwdReset['otp_expires_at']) {
            log_security_event('otp_expired', null, $pwdReset['id_number'], 'attempted_after_expiry');
            echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please click "Resend Code".']);
            exit;
        }

        if ($otpInput !== $pwdReset['otp']) {
            $pwdReset['otp_attempts']++;
            $remaining = 5 - $pwdReset['otp_attempts'];
            log_security_event('otp_failed', null, $pwdReset['id_number'], 'attempts=' . $pwdReset['otp_attempts']);

            if ($remaining <= 0) {
                unset($_SESSION['pwd_reset']);
                echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please start over.', 'expired_session' => true]);
                exit;
            }

            echo json_encode(['success' => false, 'message' => "Invalid verification code. You have {$remaining} attempt(s) remaining."]);
            exit;
        }

        $pwdReset['otp_verified'] = true;
        log_security_event('otp_verified', null, $pwdReset['id_number'], 'otp_success');

        $sql = "SELECT auth_question1, auth_question2, auth_question3 FROM users WHERE id_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $pwdReset['id_number']);
        mysqli_stmt_execute($stmt);
        $userQs = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success'    => true,
            'message'    => 'Verification successful.',
            'questions'  => [
                'q1' => $userQs['auth_question1'] ?? '',
                'q2' => $userQs['auth_question2'] ?? '',
                'q3' => $userQs['auth_question3'] ?? '',
            ]
        ]);
        exit;
    }

    if ($action === 'verify_security_answers') {
        if (empty($_SESSION['pwd_reset']['id_number']) || $_SESSION['pwd_reset']['otp_verified'] !== true) {
            echo json_encode(['success' => false, 'message' => 'Your session has expired. Please start over.', 'expired_session' => true]);
            exit;
        }

        $idNumber = $_SESSION['pwd_reset']['id_number'];

        $q1 = trim($_POST['q1'] ?? '');
        $ans1 = trim($_POST['ans1'] ?? '');
        $q2 = trim($_POST['q2'] ?? '');
        $ans2 = trim($_POST['ans2'] ?? '');
        $q3 = trim($_POST['q3'] ?? '');
        $ans3 = trim($_POST['ans3'] ?? '');

        $sql = "SELECT auth_question1, auth_answer1, auth_question2, auth_answer2, auth_question3, auth_answer3 FROM users WHERE id_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $idNumber);
        mysqli_stmt_execute($stmt);
        $userSec = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$userSec) {
            echo json_encode(['success' => false, 'message' => 'Account not found.']);
            exit;
        }

        $pairs = [
            'q1' => ['q_selected' => $q1, 'answer' => $ans1, 'q_stored' => $userSec['auth_question1'], 'a_stored' => $userSec['auth_answer1']],
            'q2' => ['q_selected' => $q2, 'answer' => $ans2, 'q_stored' => $userSec['auth_question2'], 'a_stored' => $userSec['auth_answer2']],
            'q3' => ['q_selected' => $q3, 'answer' => $ans3, 'q_stored' => $userSec['auth_question3'], 'a_stored' => $userSec['auth_answer3']],
        ];

        $correctCount = 0;
        $fieldErrors = [];
        $fieldStatuses = [];

        foreach ($pairs as $field => $pv) {
            if ($pv['q_selected'] === '' || $pv['answer'] === '') {
                $fieldErrors[$field] = 'Please select a question and provide an answer.';
                $fieldStatuses[$field] = 'empty';
                continue;
            }

            $saved = (string)$pv['a_stored'];
            $qMatch = (strcasecmp($pv['q_selected'], trim((string)$pv['q_stored'])) === 0);

            $isHash = (strlen($saved) > 3) && (str_starts_with($saved, '$2y$') || str_starts_with($saved, '$2a$') || str_starts_with($saved, '$argon2'));
            $ansOk = $isHash ? password_verify($pv['answer'], $saved) : (strcasecmp($pv['answer'], $saved) === 0);

            if ($qMatch && $ansOk) {
                $correctCount++;
                $fieldStatuses[$field] = 'correct';
            } else {
                $fieldErrors[$field] = 'Incorrect answer';
                $fieldStatuses[$field] = 'incorrect';
            }
        }

        if ($correctCount >= 2) {
            $_SESSION['pwd_reset']['security_verified'] = true;
            log_security_event('secret_question_verified', null, $idNumber, 'correctCount=' . $correctCount);
            echo json_encode(['success' => true, 'message' => 'Verification successful', 'correctCount' => $correctCount, 'fieldStatuses' => $fieldStatuses]);
        } else {
            log_security_event('secret_question_failed', null, $idNumber, 'correctCount=' . $correctCount);
            echo json_encode(['success' => false, 'message' => 'The security answers are incorrect. Please try again.', 'fieldErrors' => $fieldErrors, 'correctCount' => $correctCount, 'fieldStatuses' => $fieldStatuses]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Brew Haven</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/authentication.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>
<body class="bg-register">

    <header class="main-header">
        <div class="header-content">
            <h1><img src="../images/brew-haven-logo.svg" alt="" style="width:1.2em;height:1.2em;vertical-align:-0.22em;margin-right:0.3rem;"> Brew Haven</h1>
            <div class="header-actions">
                <a href="index.php" class="nav-link">Home</a>
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </header>

    <main class="hero">
        <div class="hero-inner">
            <div class="left-side">
                <div class="form-container">
                    <form class="form-box auth-form" id="authForm" autocomplete="off">
                        <h2 class="full-row" id="authFormTitle">Enter ID Number</h2>

                        <!-- Masked User Information Header at Top -->
                        <div class="user-info-header" id="userInfoHeader" style="display:none;">
                            <div class="info-block left">
                                <span class="info-label">ID Number</span>
                                <span class="info-value" id="hdrMaskedId">****</span>
                            </div>
                            <div class="info-block right">
                                <span class="info-label">Username</span>
                                <span class="info-value" id="hdrMaskedUsername">****</span>
                            </div>
                        </div>

                        <!-- Phase 1: Enter ID Number -->
                        <div id="identifierPhase">
                            <p class="auth-description">Enter your ID Number to locate your account.</p>
                            <div class="full-row">
                                <label for="fp_id_number">ID Number<span style="color:red">*</span>:</label>
                                <input type="password" id="fp_id_number" placeholder="e.g. 2026-0001 ">
                                <div id="identifierError" class="error-message"></div>
                            </div>
                            <div class="full-row">
                                <div class="auth-buttons">
                                    <button type="button" id="cancelIdentifierBtnId" class="btn-no">Cancel</button>
                                    <button type="button" id="findAccountBtnId" class="btn-yes">Find Account</button>
                                </div>
                            </div>
                        </div>

                        <!-- Phase 2: Verify OTP -->
                        <div id="otpPhase" style="display:none;">
                            <div class="otp-notice-box">
                                <i class="fa-solid fa-envelope-circle-check"></i>
                                <span>A 6-digit verification code has been sent to your registered email (<strong id="otpMaskedEmail">e***@gmail.com</strong>).</span>
                            </div>
                            <div class="full-row">
                                <label for="fp_otp_code">Verification Code (OTP)<span style="color:red">*</span>:</label>
                                <input type="password" id="fp_otp_code" maxlength="6" inputmode="numeric" placeholder="Enter 6-digit OTP" autocomplete="off">
                                <div id="otpError" class="error-message"></div>
                            </div>
                            <div class="full-row">
                                <div class="auth-buttons">
                                    <button type="button" id="backToIdBtnId" class="btn-no">Back</button>
                                    <button type="button" id="verifyOtpBtnId" class="btn-yes">Verify Code</button>
                                </div>
                            </div>
                            <div class="resend-container">
                                <a href="#" id="resendOtpBtnId" class="resend-link">Resend Code</a>
                                <span id="timerCountdownText" class="timer-countdown"></span>
                                <div id="resendStatusText" class="resend-status"></div>
                            </div>
                        </div>

                        <!-- Phase 3: Security Questions (Matching Registration Dropdowns) -->
                        <div id="questionsPhase" style="display:none;">
                            <p class="auth-description">Please select your security questions and enter your answers. At least 2 must be correct.</p>

                            <div class="questions-grid auth-questions-center">
                                <div class="question-group">
                                    <label for="fp_question1">Question 1<span style="color:red">*</span>:</label>
                                    <select id="fp_question1" name="fp_question1">
                                        <option value="">-- Select Question --</option>
                                        <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
                                        <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
                                        <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
                                    </select>
                                    <div id="q1Error" class="error-message"></div>
                                    <div class="input-with-toggle" id="answerWrap1" style="display:none;">
                                        <input type="password" id="security_answer1_id" placeholder="Your answer" autocomplete="off">
                                        <i class="fa-solid fa-eye togele-eye" data-target="security_answer1_id"></i>
                                    </div>
                                    <div id="ans1Error" class="error-message"></div>
                                </div>

                                <div class="question-group">
                                    <label for="fp_question2">Question 2<span style="color:red">*</span>:</label>
                                    <select id="fp_question2" name="fp_question2">
                                        <option value="">-- Select Question --</option>
                                        <option value="What is your favorite sport?">What is your favorite sport?</option>
                                        <option value="What is your favorite movie?">What is your favorite movie?</option>
                                        <option value="What is your favorite color?">What is your favorite color?</option>
                                    </select>
                                    <div id="q2Error" class="error-message"></div>
                                    <div class="input-with-toggle" id="answerWrap2" style="display:none;">
                                        <input type="password" id="security_answer2_id" placeholder="Your answer" autocomplete="off">
                                        <i class="fa-solid fa-eye togele-eye" data-target="security_answer2_id"></i>
                                    </div>
                                    <div id="ans2Error" class="error-message"></div>
                                </div>

                                <div class="question-group">
                                    <label for="fp_question3">Question 3<span style="color:red">*</span>:</label>
                                    <select id="fp_question3" name="fp_question3">
                                        <option value="">-- Select Question --</option>
                                        <option value="What is your favorite fruit?">What is your favorite fruit?</option>
                                        <option value="Do you like to eat vegetables?">Do you like to eat vegetables?</option>
                                        <option value="What is your favorite snack?">What is your favorite snack?</option>
                                    </select>
                                    <div id="q3Error" class="error-message"></div>
                                    <div class="input-with-toggle" id="answerWrap3" style="display:none;">
                                        <input type="password" id="security_answer3_id" placeholder="Your answer" autocomplete="off">
                                        <i class="fa-solid fa-eye togele-eye" data-target="security_answer3_id"></i>
                                    </div>
                                    <div id="ans3Error" class="error-message"></div>
                                </div>
                            </div>
                            <div id="sqErrorId" class="error-message" style="text-align: center; margin: 10px 0;"></div>
                            <div class="full-row">
                                <div class="auth-buttons">
                                    <button type="button" id="cancelQuestionsBtnId" class="btn-no">Cancel</button>
                                    <button type="button" id="verifyAnswersBtnId" class="btn-yes">Verify Answers</button>
                                </div>
                            </div>
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
    <script src="../js/authentication.js?v=<?php echo @filemtime(__DIR__ . '/../js/authentication.js'); ?>"></script>
</body>
</html>
