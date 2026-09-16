<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_login(false);

// Any role still inside first-login setup
require_first_login_setup($me);

// Fetch user row to check first_login status
$stmt = mysqli_prepare($conn, "SELECT id_number, firstname, lastname, first_login FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$userRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$firstLoginVal = (int)($userRow['first_login'] ?? 0);

// Step 1 incomplete → profile setup
if ($firstLoginVal === 1 || empty($userRow['firstname']) || empty($userRow['lastname'])) {
    header('Location: complete-profile.php');
    exit;
}

// Step 3 → security questions
if ($firstLoginVal === 3) {
    header('Location: setup-security-questions.php');
    exit;
}

// Already done → dashboard
if ($firstLoginVal === 0) {
    header('Location: ' . role_home_page($me['role']));
    exit;
}

$error = $_GET['error'] ?? '';
$errorMessages = [
    'missing_fields'     => 'Please fill in all password fields.',
    'incorrect_current'  => 'Current/Default password is incorrect.',
    'mismatch'           => 'New password and confirmation do not match.',
    'weak_password'      => 'Password is too weak. It must be at least 8 characters with letters, numbers, and a special character.',
    'same_as_current'    => 'New password cannot be the same as the default password.',
    'save_failed'        => 'Failed to update password. Please try again.',
];
$errorMessage = $errorMessages[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Change Password - Brew Haven Setup</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
  <style>
    .bh-pwd-strength { font-size:0.82rem; margin-top:0.3rem; font-weight:500; }
    .bh-pwd-strength.weak   { color:#ff4757; }
    .bh-pwd-strength.medium { color:#f0932b; }
    .bh-pwd-strength.strong { color:#27ae60; }
    .error-message { color:#ff4757; font-size:0.82rem; margin-top:0.25rem; display:none; }
    .error-message.show { display:block; }
    .bh-step-indicator { display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem; font-size:0.85rem; color:var(--bh-text-muted); }
    .bh-step-indicator .step { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; font-weight:600; font-size:0.75rem; }
    .bh-step-indicator .step.done { background:var(--bh-sage); color:#fff; }
    .bh-step-indicator .step.active { background:var(--bh-espresso); color:#fff; }
    .bh-step-indicator .step.pending { background:rgba(111,78,55,0.12); color:var(--bh-espresso); }
    .bh-step-indicator .step-line { width:28px; height:2px; background:rgba(111,78,55,0.18); }
  </style>
</head>
<body class="bh-shell">
  <div class="bh-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-user">
      <span class="bh-role-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $me['role']))); ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="bh-main" style="max-width:700px;margin:0 auto;padding-top:2rem;">
    <h1 class="bh-page-title">Change Password</h1>

    <!-- Step indicator -->
    <div class="bh-step-indicator">
      <span class="step done"><i class="fa-solid fa-check" style="font-size:0.65rem;"></i></span> <span>Profile</span>
      <span class="step-line"></span>
      <span class="step active">2</span> <span style="font-weight:600;color:var(--bh-espresso);">Password</span>
      <span class="step-line"></span>
      <span class="step pending">3</span> <span>Security Questions</span>
    </div>

    <div class="bh-panel">
      <div class="bh-alert" style="background:rgba(111,78,55,0.08);border:1px solid rgba(111,78,55,0.2);color:var(--bh-espresso);margin-bottom:1.5rem;">
        <i class="fa-solid fa-lock"></i> For your security, you must replace your assigned default password with a new personal password before accessing your dashboard.
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div class="bh-alert bh-alert-error" style="margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="../php/save_initial_password.php" id="passwordSetupForm">
        <h3 style="color:var(--bh-espresso);font-size:1.1rem;margin-bottom:1rem;">Password Credentials</h3>
        <div class="bh-form-grid">
          <div style="grid-column: 1 / -1;">
            <label for="current_password">Current / Default Password <span style="color:var(--bh-danger);">*</span></label>
            <input type="password" id="current_password" name="current_password" required placeholder="Enter the default password given to you">
          </div>
          <div>
            <label for="new_password">New Password <span style="color:var(--bh-danger);">*</span></label>
            <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="At least 8 characters">
            <div class="password-strength-container">
              <div class="password-strength-bar">
                <div class="strength-bar"></div>
              </div>
              <div class="password-strength-message" id="new_password-strength"></div>
            </div>
          </div>
          <div>
            <label for="confirm_password">Confirm New Password <span style="color:var(--bh-danger);">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter new password">
          </div>
        </div>

        <div style="margin-top:1.75rem;display:flex;justify-content:flex-end;">
          <button type="submit" class="bh-btn bh-btn-primary">Save &amp; Continue <i class="fa-solid fa-arrow-right" style="margin-left:0.4rem;"></i></button>
        </div>
      </form>
    </div>
  </div>

  <footer class="main-footer" style="position:static;margin-top:3rem;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
  <script>
  (function() {
    'use strict';
    var form = document.getElementById('passwordSetupForm');
    var newPwd = document.getElementById('new_password');
    var confirmPwd = document.getElementById('confirm_password');
    var strengthBarEl = form.querySelector('.strength-bar');
    var strengthMsgEl = document.getElementById('new_password-strength');

    newPwd.addEventListener('input', function() {
      if (window.BHValidation && window.BHValidation.updatePasswordStrengthUI) {
        window.BHValidation.updatePasswordStrengthUI(newPwd.value, strengthBarEl, strengthMsgEl);
      }
      BHValidation.clearFieldError(confirmPwd);
    });

    confirmPwd.addEventListener('input', function() {
      if (confirmPwd.value) {
        var match = BHValidation.validatePasswordMatch(newPwd.value, confirmPwd.value);
        if (!match.valid) {
          BHValidation.showFieldError(confirmPwd, match.error);
        } else {
          BHValidation.clearFieldError(confirmPwd);
        }
      }
    });

    form.addEventListener('submit', function(e) {
      var valid = true;

      if (!document.getElementById('current_password').value.trim()) {
        BHValidation.showFieldError(document.getElementById('current_password'), 'Current password is required');
        valid = false;
      } else {
        BHValidation.clearFieldError(document.getElementById('current_password'));
      }

      var strength = BHValidation.validatePasswordStrength(newPwd.value);
      if (!strength.valid) {
        BHValidation.showFieldError(newPwd, strength.error);
        valid = false;
      } else {
        BHValidation.clearFieldError(newPwd);
      }

      var match = BHValidation.validatePasswordMatch(newPwd.value, confirmPwd.value);
      if (!match.valid) {
        BHValidation.showFieldError(confirmPwd, match.error);
        valid = false;
      } else {
        BHValidation.clearFieldError(confirmPwd);
      }

      if (!valid) { e.preventDefault(); }
    });
  })();
  </script>
</body>
</html>

