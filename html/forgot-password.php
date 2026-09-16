<?php
require_once __DIR__ . '/../php/auth_helpers.php';
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // The account is the one pinned by the server-side reset session, never
    // a client-supplied id - and only after security questions have actually
    // been verified in this same session.
    if (
        empty($_SESSION['pwd_reset']['id_number']) ||
        ($_SESSION['pwd_reset']['otp_verified'] ?? false) !== true ||
        ($_SESSION['pwd_reset']['security_verified'] ?? false) !== true
    ) {
        echo json_encode(['success' => false, 'message' => 'Your session has expired. Please start over.', 'expired_session' => true]);
        exit;
    }
    $idNumber = $_SESSION['pwd_reset']['id_number'];

    $newPassword     = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['reenter_password'] ?? '');

    if (empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        exit;
    }

    // A blocked/rejected account resetting its own password would bypass the block.
    $statusStmt = mysqli_prepare($conn, "SELECT approval_status, account_status FROM users WHERE id_number = ?");
    mysqli_stmt_bind_param($statusStmt, 's', $idNumber);
    mysqli_stmt_execute($statusStmt);
    $statusRow = mysqli_fetch_assoc(mysqli_stmt_get_result($statusStmt));
    mysqli_stmt_close($statusStmt);

    if (!$statusRow) {
        echo json_encode(['success' => false, 'message' => 'Unable to reset password. Please try again.']);
        exit;
    }
    if ($statusRow['account_status'] === 'blocked' || $statusRow['approval_status'] === 'rejected') {
        log_security_event('password_reset', null, $idNumber, 'denied: approval=' . $statusRow['approval_status'] . ' account=' . $statusRow['account_status']);
        $blockedMessage = $statusRow['account_status'] === 'blocked'
            ? 'This account is blocked. Please contact support.'
            : 'This account\'s registration was rejected. Unable to reset password. Please contact support.';
        echo json_encode(['success' => false, 'message' => $blockedMessage]);
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        $hashedPassword = hashPassword($newPassword);

        $sql = "UPDATE users SET password = ? WHERE id_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $idNumber);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating user password: " . mysqli_error($conn));
        }
        if (mysqli_stmt_affected_rows($stmt) === 0) {
            throw new Exception("No user found with the provided ID number.");
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        log_security_event('password_reset', $idNumber, $idNumber, 'via secret question recovery');

        // Single-use: the reset session is consumed the moment the password changes.
        unset($_SESSION['pwd_reset']);

        echo json_encode(['success' => true, 'message' => 'Password successfully reset.']);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Unable to reset password. Please try again.']);
    }
    exit;
}

// GET: creating a new password is only reachable after Email -> Security Questions.
if (
    empty($_SESSION['pwd_reset']['id_number']) ||
    ($_SESSION['pwd_reset']['otp_verified'] ?? false) !== true ||
    ($_SESSION['pwd_reset']['security_verified'] ?? false) !== true
) {
    header('Location: authentication.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Brew Haven</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>
<body class="bg-login forgot-password-page">


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
        <div class="phone-container">
            <div class="phone-frame">
                <div class="phone-screen">
                    <div class="phone-notch"></div>
                    <div class="login-content">
                        <?php
                        $pwdSession = $_SESSION['pwd_reset'] ?? [];
                        $maskedId = $pwdSession['masked_id'] ?? bh_mask_id_number($pwdSession['id_number'] ?? '');
                        $maskedUsername = $pwdSession['masked_username'] ?? bh_mask_username($pwdSession['username'] ?? '');
                        ?>
                        <form class="login-form" id="forgotPasswordForm">
                            <h2>Create New Password</h2>
                            
                            <!-- Masked User Information Header at Top -->
                            <div class="user-info-header" id="userInfoHeader">
                                <div class="info-block left">
                                    <span class="info-label">ID Number</span>
                                    <span class="info-value" id="hdrMaskedId"><?php echo htmlspecialchars($maskedId); ?></span>
                                </div>
                                <div class="info-block right">
                                    <span class="info-label">Username</span>
                                    <span class="info-value" id="hdrMaskedUsername"><?php echo htmlspecialchars($maskedUsername); ?></span>
                                </div>
                            </div>

                            <p class="subtitle">Choose a new password for your account</p>
                            <label for="new_password">New Password<span style="color:red">*</span></label>
                            <div class="input-with-toggle">
                                <input type="password" id="new_password" name="new_password">
                                <i class="fa-solid fa-eye togele-eye" data-target="new_password"></i>
                            </div>
                            <div class="password-strength-container">
                                <div class="password-strength-bar">
                                    <div class="strength-bar"></div>
                                </div>
                                <div class="password-strength-message"></div>
                            </div>
                            <div class="error-message" id="new_password-error"></div>
                            <label for="reenter_password">Confirm Password<span style="color:red">*</span></label>
                            <div class="input-with-toggle">
                                <input type="password" id="reenter_password" name="reenter_password">
                                <i class="fa-solid fa-eye togele-eye" data-target="reenter_password"></i>
                            </div>
                            <div class="error-message" id="reenter_password-error"></div>
                            <div class="auth-buttons" style="display:flex;gap:0.75rem;margin-top:1.25rem;">
                                <a href="login.php" class="btn-no" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">Cancel</a>
                                <button type="submit" class="login-btn btn-yes" style="margin:0;">RESET NOW</button>
                            </div>
                            <p class="switch-form" style="margin-top:1rem;">Remember your password? <a href="login.php">Login</a></p>
                        </form>

                        <div class="login-content" id="resetSuccessView" style="display:none;">
                            <h2>Password Reset Successful</h2>
                            <p class="subtitle">Your password has been successfully changed.<br>You can now log in using your new password.</p>
                            <a href="login.php" class="login-btn" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;">RETURN TO LOGIN</a>
                        </div>
                    </div>
                </div>
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
<script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
<script src="../js/forgot-password.js?v=<?php echo @filemtime(__DIR__ . '/../js/forgot-password.js'); ?>"></script>
</body>
</html>
