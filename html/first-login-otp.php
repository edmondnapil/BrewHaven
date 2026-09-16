<?php
require_once __DIR__ . '/../php/auth_helpers.php';
require_once __DIR__ . '/../php/smtp_mailer.php';

$me = require_login(false);

// Ensure account is in first-login setup
$stmt = mysqli_prepare($conn, "SELECT id_number, firstname, lastname, email, role, first_login, auth_question1 FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$userRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$userRow || !bh_in_first_login_setup($userRow)) {
    header('Location: ' . role_home_page($me['role']));
    exit;
}

// If OTP already verified, forward to active setup step
if (!empty($_SESSION['first_login_otp']['otp_verified']) && ($_SESSION['first_login_otp']['user_id'] ?? '') === $me['id_number']) {
    $firstLoginStep = (int)($userRow['first_login'] ?? 0);
    if ($firstLoginStep === 1 || empty($userRow['firstname']) || empty($userRow['lastname'])) {
        header('Location: complete-profile.php');
    } elseif ($firstLoginStep === 2) {
        header('Location: change-password-setup.php');
    } else {
        header('Location: setup-security-questions.php');
    }
    exit;
}

// Ensure session OTP state exists or initialize
if (empty($_SESSION['first_login_otp']) || ($_SESSION['first_login_otp']['user_id'] ?? '') !== $me['id_number']) {
    $otpCode = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $userEmail = $userRow['email'] ?? '';
    $maskedEmail = bh_mask_email_address($userEmail);

    $_SESSION['first_login_otp'] = [
        'user_id'          => $me['id_number'],
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

    if (!empty($userEmail)) {
        bh_send_otp_email($userEmail, $otpCode, 'FIRST_LOGIN');
    }
    log_security_event('otp_sent', $me['id_number'], $me['id_number'], 'first_login_otp init masked_email=' . $maskedEmail);
}

// Handle AJAX actions (verify_otp, resend_otp)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'verify_otp') {
        $otpState = &$_SESSION['first_login_otp'];

        if (empty($otpState) || ($otpState['purpose'] ?? '') !== 'FIRST_LOGIN') {
            echo json_encode(['success' => false, 'message' => 'Your verification session is invalid. Please log in again.']);
            exit;
        }

        // Reject if OTP was already verified/used
        if (!empty($otpState['otp_verified']) || empty($otpState['otp'])) {
            echo json_encode(['success' => false, 'message' => 'This verification code has already been used. Please request a new one.', 'expired_otp' => true]);
            exit;
        }

        if (time() > $otpState['otp_expires_at']) {
            echo json_encode(['success' => false, 'message' => 'This verification code has expired. Please request a new one.', 'expired_otp' => true]);
            exit;
        }

        $otpState['otp_attempts']++;
        if ($otpState['otp_attempts'] > 5) {
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new verification code.', 'expired_otp' => true]);
            exit;
        }

        $enteredOtp = trim($_POST['otp_code'] ?? '');
        if ($enteredOtp === '' || $enteredOtp !== $otpState['otp']) {
            log_security_event('otp_failed', $me['id_number'], $me['id_number'], 'incorrect first_login_otp attempt ' . $otpState['otp_attempts']);
            echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check and try again.']);
            exit;
        }

        // OTP Verified successfully! Invalidate OTP so it cannot be reused
        $otpState['otp_verified'] = true;
        $otpState['otp']          = null;
        log_security_event('otp_verified', $me['id_number'], $me['id_number'], 'first_login email verified');

        $firstLoginStep = (int)($userRow['first_login'] ?? 0);
        $nextStep = 'complete-profile.php';
        if ($firstLoginStep === 2) {
            $nextStep = 'change-password-setup.php';
        } elseif ($firstLoginStep === 3) {
            $nextStep = 'setup-security-questions.php';
        }

        echo json_encode([
            'success'   => true,
            'message'   => 'Email verification successful! Proceeding to account setup...',
            'next_step' => $nextStep
        ]);
        exit;
    }

    if ($action === 'resend_otp') {
        $otpState = &$_SESSION['first_login_otp'];

        if (empty($otpState) || ($otpState['purpose'] ?? '') !== 'FIRST_LOGIN') {
            echo json_encode(['success' => false, 'message' => 'Your verification session is invalid. Please log in again.']);
            exit;
        }

        $lastResend = $otpState['last_resend_time'] ?? 0;
        $elapsed = time() - $lastResend;
        if ($elapsed < 60) {
            $cooldownLeft = 60 - $elapsed;
            echo json_encode([
                'success'       => false,
                'message'       => 'Please wait ' . $cooldownLeft . ' seconds before requesting a new code.',
                'cooldown_left' => $cooldownLeft
            ]);
            exit;
        }

        // Generate NEW OTP and invalidate previous one
        $newOtp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpState['otp']              = $newOtp;
        $otpState['otp_created_at']   = time();
        $otpState['otp_expires_at']   = time() + 600; // 10 minutes
        $otpState['otp_attempts']     = 0;
        $otpState['last_resend_time'] = time();
        $otpState['otp_verified']     = false;

        $sent = false;
        if (!empty($otpState['email'])) {
            $sent = bh_send_otp_email($otpState['email'], $newOtp, 'FIRST_LOGIN');
        }

        if (!$sent) {
            log_security_event('otp_send_failed', $me['id_number'], $me['id_number'], 'first_login_otp resend failed');
            echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
            exit;
        }

        log_security_event('otp_resent', $me['id_number'], $me['id_number'], 'first_login_otp resent');

        echo json_encode([
            'success'  => true,
            'message'  => 'A new verification code has been sent to your email address.',
            'cooldown' => 60
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$maskedEmail = $_SESSION['first_login_otp']['masked_email'] ?? 'your registered email';
$lastResend = $_SESSION['first_login_otp']['last_resend_time'] ?? time();
$initialCooldown = max(0, 60 - (time() - $lastResend));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Brew Haven Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .otp-input-wrap {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 1.5rem 0;
        }
        .otp-digit {
            width: 48px;
            height: 56px;
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            border: 2px solid var(--bh-border, #d1c7bd);
            border-radius: 10px;
            background: #ffffff;
            color: var(--bh-espresso, #2a170e);
            transition: all 0.2s ease;
        }
        .otp-digit:focus {
            outline: none;
            border-color: var(--bh-caramel, #c47a2c);
            box-shadow: 0 0 0 3px rgba(196, 122, 44, 0.18);
        }
        .otp-digit.error {
            border-color: var(--bh-danger, #e74c3c);
            background: #fff5f5;
        }
        .resend-section {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--bh-text-muted, #6f5f54);
        }
        .btn-resend {
            background: none;
            border: none;
            color: var(--bh-caramel, #c47a2c);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
        }
        .btn-resend:disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }
        .badge-step {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(196, 122, 44, 0.12);
            color: var(--bh-caramel, #c47a2c);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body class="bh-auth-page">

<div class="bh-auth-topnav">
    <a href="logout.php" class="bh-btn bh-btn-secondary"><i class="fa-solid fa-right-from-bracket" style="margin-right:0.35rem;"></i> Cancel &amp; Logout</a>
</div>

<div class="bh-auth-photo">
    <div class="bh-auth-photo-bg" style="background-image:url('https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=1200&q=80');"></div>
    <div class="bh-auth-brand">
        <img src="../images/brew-haven-logo.svg" alt="" style="width:32px;height:32px;"> Brew Haven
    </div>
    <div class="bh-auth-brand-sub">Security &amp; Account Activation</div>

    <div class="bh-auth-quote">
        <p>&ldquo;Security is an essential ingredient in crafting an experience you can always trust.&rdquo;</p>
        <span>Brew Haven Authentication Services</span>
    </div>

    <div class="bh-auth-photo-footer">
        <h2>First-Time Account Activation</h2>
        <p>Step 1 of 4: Verify your registered email address</p>
        <div class="bh-auth-dots"><span></span><span></span><span></span></div>
    </div>
</div>

<div class="bh-auth-form-side">
    <div class="bh-auth-card" style="max-width: 440px;">
        <div class="badge-step"><i class="fa-solid fa-envelope-circle-check"></i> Security Step 1: Email OTP</div>
        <h2>Verify Your Email</h2>
        <p class="bh-auth-sub">
            We sent a 6-digit verification code to <br><strong style="color:var(--bh-espresso); font-family: monospace;"><?php echo htmlspecialchars($maskedEmail); ?></strong>.
        </p>

        <div id="otpAlert" class="bh-alert" style="display:none; margin-bottom: 1rem; font-size: 0.88rem; line-height: 1.4; border-radius: 8px;"></div>

        <form id="otpForm" autocomplete="off">
            <div class="otp-input-wrap">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-0" autofocus autocomplete="off">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-1" autocomplete="off">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-2" autocomplete="off">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-3" autocomplete="off">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-4" autocomplete="off">
                <input type="password" maxlength="1" inputmode="numeric" class="otp-digit" id="otp-5" autocomplete="off">
            </div>

            <button type="submit" class="bh-auth-submit" id="verifyBtn" style="margin-top: 1rem;">
                <span id="btnText">Verify &amp; Continue</span>
                <i class="fa-solid fa-arrow-right" style="margin-left: 0.4rem;"></i>
            </button>

            <div class="resend-section">
                <span>Didn't receive a code? </span>
                <button type="button" id="resendBtn" class="btn-resend" <?php echo $initialCooldown > 0 ? 'disabled' : ''; ?>>
                    Resend Code
                </button>
                <div id="countdownWrap" style="font-size: 0.8rem; margin-top: 0.35rem; color: #888; <?php echo $initialCooldown > 0 ? '' : 'display:none;'; ?>">
                    Resend available in <span id="countdownSec"><?php echo $initialCooldown; ?></span>s
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const digits = Array.from(document.querySelectorAll('.otp-digit'));
    const form = document.getElementById('otpForm');
    const alertBox = document.getElementById('otpAlert');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const countdownWrap = document.getElementById('countdownWrap');
    const countdownSec = document.getElementById('countdownSec');

    let cooldownTimer = null;
    let cooldownRemaining = <?php echo (int)$initialCooldown; ?>;

    function showAlert(message, type = 'danger') {
        alertBox.className = 'bh-alert bh-alert-' + type;
        alertBox.innerHTML = '<i class="fa-solid fa-' + (type === 'success' ? 'circle-check' : 'circle-exclamation') + '"></i> ' + message;
        alertBox.style.display = 'block';
    }

    function clearAlert() {
        alertBox.style.display = 'none';
        alertBox.innerHTML = '';
    }

    function startCooldown(seconds) {
        clearInterval(cooldownTimer);
        cooldownRemaining = seconds;
        resendBtn.disabled = true;
        countdownWrap.style.display = 'block';
        countdownSec.textContent = cooldownRemaining;

        cooldownTimer = setInterval(function() {
            cooldownRemaining--;
            if (cooldownRemaining <= 0) {
                clearInterval(cooldownTimer);
                resendBtn.disabled = false;
                countdownWrap.style.display = 'none';
            } else {
                countdownSec.textContent = cooldownRemaining;
            }
        }, 1000);
    }

    if (cooldownRemaining > 0) {
        startCooldown(cooldownRemaining);
    }

    // OTP Box Navigation & Paste support
    digits.forEach((digit, index) => {
        digit.addEventListener('input', function(e) {
            const val = this.value.replace(/[^0-9]/g, '');
            this.value = val ? val.slice(-1) : '';
            if (this.value && index < digits.length - 1) {
                digits[index + 1].focus();
            }
            digits.forEach(d => d.classList.remove('error'));
            clearAlert();
        });

        digit.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                digits[index - 1].focus();
            }
        });

        digit.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
            const numbersOnly = pasteData.replace(/[^0-9]/g, '');
            if (numbersOnly.length > 0) {
                for (let i = 0; i < digits.length; i++) {
                    digits[i].value = numbersOnly[i] || '';
                }
                const focusIdx = Math.min(numbersOnly.length, digits.length - 1);
                digits[focusIdx].focus();
            }
        });
    });

    // Form Submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearAlert();

        const code = digits.map(d => d.value).join('');
        if (code.length !== 6) {
            showAlert('Please enter the full 6-digit verification code.', 'danger');
            digits.forEach(d => { if (!d.value) d.classList.add('error'); });
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.querySelector('#btnText').textContent = 'Verifying...';

        const fd = new FormData();
        fd.append('action', 'verify_otp');
        fd.append('otp_code', code);

        fetch('first-login-otp.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.replace(data.next_step || 'complete-profile.php');
                }, 700);
            } else {
                showAlert(data.message || 'Verification failed. Please try again.', 'danger');
                digits.forEach(d => d.classList.add('error'));
                verifyBtn.disabled = false;
                verifyBtn.querySelector('#btnText').textContent = 'Verify & Continue';
                if (data.expired_otp) {
                    digits.forEach(d => { d.value = ''; });
                    digits[0].focus();
                }
            }
        })
        .catch(err => {
            showAlert('A network error occurred. Please try again.', 'danger');
            verifyBtn.disabled = false;
            verifyBtn.querySelector('#btnText').textContent = 'Verify & Continue';
        });
    });

    // Resend OTP
    resendBtn.addEventListener('click', function() {
        if (resendBtn.disabled) return;
        clearAlert();
        resendBtn.disabled = true;

        const fd = new FormData();
        fd.append('action', 'resend_otp');

        fetch('first-login-otp.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                startCooldown(data.cooldown || 60);
                digits.forEach(d => { d.value = ''; d.classList.remove('error'); });
                digits[0].focus();
            } else {
                showAlert(data.message || 'Could not resend code.', 'danger');
                if (data.cooldown_left) {
                    startCooldown(data.cooldown_left);
                } else {
                    resendBtn.disabled = false;
                }
            }
        })
        .catch(err => {
            showAlert('Error sending verification code. Please try again.', 'danger');
            resendBtn.disabled = false;
        });
    });
});
</script>
</body>
</html>
