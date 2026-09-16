<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_login(false);

// Any role still inside first-login setup
require_first_login_setup($me);

// Fetch user row to check current step
$checkStmt = mysqli_prepare($conn, "SELECT first_login, firstname, lastname, auth_question1 FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($checkStmt, 's', $me['id_number']);
mysqli_stmt_execute($checkStmt);
$checkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
mysqli_stmt_close($checkStmt);

$firstLoginVal = (int)($checkRow['first_login'] ?? 0);

// Step 1 incomplete → profile setup
if ($firstLoginVal === 1 || empty($checkRow['firstname']) || empty($checkRow['lastname'])) {
    header('Location: complete-profile.php');
    exit;
}

// Step 2 incomplete → password change
if ($firstLoginVal === 2) {
    header('Location: change-password-setup.php');
    exit;
}

// Already done → dashboard
if ($firstLoginVal === 0 && !empty($checkRow['auth_question1'])) {
    header('Location: ' . role_home_page($me['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q1 = sanitizeInput($_POST['auth_question1'] ?? '');
    $a1 = $_POST['auth_answer1'] ?? '';
    $q2 = sanitizeInput($_POST['auth_question2'] ?? '');
    $a2 = $_POST['auth_answer2'] ?? '';
    $q3 = sanitizeInput($_POST['auth_question3'] ?? '');
    $a3 = $_POST['auth_answer3'] ?? '';

    // Shared backend validation
    $validation = bh_validate_security_questions($q1, $a1, $q2, $a2, $q3, $a3);
    if (!$validation['valid']) {
        $error = $validation['error'];
    } else {
        $hashedA1 = hashPassword($a1);
        $hashedA2 = hashPassword($a2);
        $hashedA3 = hashPassword($a3);

        // Save questions AND mark setup complete (first_login = 0)
        // An Inactive account becomes Active once its setup is complete. A
        // waiting Super Admin is never signed in here, so it is not affected.
        $stmt = mysqli_prepare($conn, "UPDATE users SET auth_question1 = ?, auth_answer1 = ?, auth_question2 = ?, auth_answer2 = ?, auth_question3 = ?, auth_answer3 = ?, first_login = 0, account_status = IF(account_status = 'inactive' AND role <> 'super_admin', 'active', account_status) WHERE id_number = ?");
        mysqli_stmt_bind_param($stmt, 'sssssss', $q1, $hashedA1, $q2, $hashedA2, $q3, $hashedA3, $me['id_number']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        log_security_event('security_questions_setup', $me['id_number'], $me['id_number'], '');
        log_security_event('first_login_completed', $me['id_number'], $me['id_number'], 'all 3 setup steps completed');

        header('Location: ' . role_home_page($me['role']));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Set Up Security Questions - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
  <style>
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

  <div class="bh-main" style="max-width:700px;margin:0 auto;">
    <h1 class="bh-page-title">Set Up Your Security Questions</h1>

    <!-- Step indicator -->
    <div class="bh-step-indicator">
      <span class="step done"><i class="fa-solid fa-check" style="font-size:0.65rem;"></i></span> <span>Profile</span>
      <span class="step-line"></span>
      <span class="step done"><i class="fa-solid fa-check" style="font-size:0.65rem;"></i></span> <span>Password</span>
      <span class="step-line"></span>
      <span class="step active">3</span> <span style="font-weight:600;color:var(--bh-espresso);">Security Questions</span>
    </div>

    <div class="bh-panel">
      <div class="bh-alert" style="background:rgba(111,78,55,0.08);border:1px solid rgba(111,78,55,0.2);color:var(--bh-espresso);">
        <i class="fa-solid fa-shield-halved"></i> For your account's security, choose 3 different questions and answer each one yourself. You'll need these to recover your account later. This is the final step of your account setup.
      </div>
      <?php if ($error !== ''): ?><div class="bh-alert bh-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <form method="post" action="setup-security-questions.php" id="securityQuestionsForm">
        <div class="bh-form-grid">
          <div>
            <label>Question 1</label>
            <select name="auth_question1" id="auth_question1" required>
              <option value="">-- Select Question --</option>
              <option value="Who is your best friend in Elementary?">Who is your best friend in Elementary?</option>
              <option value="What is the name of your favorite pet?">What is the name of your favorite pet?</option>
              <option value="Who is your favorite teacher in high school?">Who is your favorite teacher in high school?</option>
            </select>
            <input type="password" name="auth_answer1" id="auth_answer1" placeholder="Your answer" required style="margin-top:0.4rem;">
          </div>
          <div>
            <label>Question 2</label>
            <select name="auth_question2" id="auth_question2" required>
              <option value="">-- Select Question --</option>
              <option value="What is your favorite sport?">What is your favorite sport?</option>
              <option value="What is your favorite movie?">What is your favorite movie?</option>
              <option value="What is your favorite color?">What is your favorite color?</option>
            </select>
            <input type="password" name="auth_answer2" id="auth_answer2" placeholder="Your answer" required style="margin-top:0.4rem;">
          </div>
          <div>
            <label>Question 3</label>
            <select name="auth_question3" id="auth_question3" required>
              <option value="">-- Select Question --</option>
              <option value="What is your favorite fruit?">What is your favorite fruit?</option>
              <option value="Do you like to eat vegetables?">Do you like to eat vegetables?</option>
              <option value="What is your favorite snack?">What is your favorite snack?</option>
            </select>
            <input type="password" name="auth_answer3" id="auth_answer3" placeholder="Your answer" required style="margin-top:0.4rem;">
          </div>
        </div>
        <div style="margin-top:1.25rem;display:flex;justify-content:flex-end;">
          <button type="submit" class="bh-btn bh-btn-primary">Save &amp; Go to Dashboard <i class="fa-solid fa-check" style="margin-left:0.4rem;"></i></button>
        </div>
      </form>
    </div>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
  <script>
  (function() {
    'use strict';
    var form = document.getElementById('securityQuestionsForm');

    form.addEventListener('submit', function(e) {
      var q1 = document.getElementById('auth_question1').value;
      var a1 = document.getElementById('auth_answer1').value;
      var q2 = document.getElementById('auth_question2').value;
      var a2 = document.getElementById('auth_answer2').value;
      var q3 = document.getElementById('auth_question3').value;
      var a3 = document.getElementById('auth_answer3').value;

      var result = BHValidation.validateSecurityQuestions(q1, a1, q2, a2, q3, a3);
      if (!result.valid) {
        e.preventDefault();
        // Show field-specific errors
        var fields = ['auth_question1','auth_answer1','auth_question2','auth_answer2','auth_question3','auth_answer3'];
        for (var i = 0; i < fields.length; i++) {
          var el = document.getElementById(fields[i]);
          if (result.fieldErrors[fields[i]]) {
            BHValidation.showFieldError(el, result.fieldErrors[fields[i]]);
          } else {
            BHValidation.clearFieldError(el);
          }
        }
        // General error (e.g. duplicate questions)
        if (result.fieldErrors['general']) {
          var alertEl = document.querySelector('.bh-alert-error');
          if (!alertEl) {
            alertEl = document.createElement('div');
            alertEl.className = 'bh-alert bh-alert-error';
            form.parentNode.insertBefore(alertEl, form);
          }
          alertEl.textContent = result.fieldErrors['general'];
          alertEl.style.display = 'block';
        }
      }
    });

    // Live validation on answer inputs
    ['auth_answer1','auth_answer2','auth_answer3'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', function() {
          var val = el.value.trim();
          if (val && val.length < 3) {
            BHValidation.showFieldError(el, 'Answer must be at least 3 characters');
          } else {
            BHValidation.clearFieldError(el);
          }
        });
      }
    });
  })();
  </script>
</body>
</html>

