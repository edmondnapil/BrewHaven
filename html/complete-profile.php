<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_login(false);

// Staff, plus any User/Customer still inside the first-login setup
require_first_login_setup($me);

// Fetch current user row to check status
$stmt = mysqli_prepare($conn, "SELECT id_number, firstname, lastname, middlename, birth_date, age, gender, email, first_login FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$userRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// If profile is already completed, move to next step
if ((int)($userRow['first_login'] ?? 0) === 2) {
    header('Location: change-password-setup.php');
    exit;
} elseif ((int)($userRow['first_login'] ?? 0) === 0 && !empty($userRow['firstname']) && !empty($userRow['lastname'])) {
    header('Location: ' . role_home_page($me['role']));
    exit;
}

// Every newly created account completes its address here, whatever the role,
// using the same fields and the same rules the self-service registration asks
// for. Staff records used to skip this step, which left Admin and Super Admin
// accounts sitting on the 'N/A' placeholders admin_create_staff.php inserts.
$needsAddress = true;

$error = $_GET['error'] ?? '';
$errorMessages = [
    'missing_fields'  => 'Please fill in all required personal information fields.',
    'missing_address' => 'Please fill in all required address fields.',
    'underage'        => 'You must be at least 18 years old.',
    'invalid_email'   => 'Please provide a valid email address.',
    'email_exists'    => 'The email address provided is already registered to another account.',
    'save_failed'     => 'Failed to save your profile information. Please try again.',
];
$errorMessage = $errorMessages[$error] ?? ($error !== '' ? htmlspecialchars($error) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Complete Your Profile - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
  <div class="bh-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-user">
      <span class="bh-role-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $me['role']))); ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <!-- Wider than the usual form card so the fields can sit four to a row and
       the whole setup fits on one screen. -->
  <div class="bh-main" style="max-width:1120px;margin:0 auto;padding-top:1.5rem;">
    <h1 class="bh-page-title">Complete Your Profile</h1>
    <div class="bh-panel">
      <div class="bh-alert" style="background:rgba(111,78,55,0.08);border:1px solid rgba(111,78,55,0.2);color:var(--bh-espresso);margin-bottom:1rem;">
        <i class="fa-solid fa-user-gear"></i> Welcome to Brew Haven! Since this is your first login, please complete your personal profile information to finalize your account setup.
      </div>

      <?php if (!empty($errorMessage)): ?>
        <div class="bh-alert bh-alert-error" style="margin-bottom:1.25rem;">
          <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="../php/save_initial_profile.php" id="profileSetupForm">
        <div class="bh-form-grid bh-form-grid-4">
          <div>
            <label for="firstname">First Name <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="firstname" name="firstname" required maxlength="30" placeholder="e.g. Juan" autocomplete="given-name">
            <div id="firstname-error" class="error-message"></div>
          </div>
          <div>
            <label for="lastname">Last Name <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="lastname" name="lastname" required maxlength="30" placeholder="e.g. Dela Cruz" autocomplete="family-name">
            <div id="lastname-error" class="error-message"></div>
          </div>
          <div>
            <label for="middlename">Middle Name</label>
            <input type="text" id="middlename" name="middlename" maxlength="30" placeholder="Optional" autocomplete="additional-name">
            <div id="middlename-error" class="error-message"></div>
          </div>
          <div>
            <label for="birth_date">Birth Date <span style="color:var(--bh-danger);">*</span></label>
            <input type="date" id="birth_date" name="birth_date" required>
            <div id="birth_date-error" class="error-message"></div>
          </div>
          <div>
            <label for="age">Age <span style="color:var(--bh-danger);">*</span></label>
            <input type="number" id="age" name="age" readonly style="background:var(--bh-cream);color:var(--bh-text-muted);" placeholder="Calculated automatically">
            <div id="age-error" class="error-message"></div>
          </div>
          <div>
            <label for="gender">Gender <span style="color:var(--bh-danger);">*</span></label>
            <select id="gender" name="gender" required>
              <option value="">-- Select Gender --</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
            <div id="gender-error" class="error-message"></div>
          </div>
          <!-- Two cells wide: it completes the second row alongside Age and
               Gender, and an email address needs the extra room. -->
          <div class="bh-span-2">
            <label for="email">Email Address <span style="color:var(--bh-danger);">*</span></label>
            <input type="email" id="email" name="email" required placeholder="e.g. juan.delacruz@example.com" value="<?php echo htmlspecialchars($userRow['email'] ?? ''); ?>">
            <div id="email-error" class="error-message"></div>
          </div>
        </div>

        <?php if ($needsAddress): ?>
        <h3 style="font-family:var(--bh-font-heading);color:var(--bh-espresso);margin:1.25rem 0 0.9rem;">Address Information</h3>
        <div class="bh-form-grid bh-form-grid-4">
          <div>
            <label for="street">Purok/Street <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="street" name="street" required maxlength="100" placeholder="e.g. Purok 1, Mabuhay St.">
            <div id="street-error" class="error-message"></div>
          </div>
          <div>
            <label for="barangay">Barangay <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="barangay" name="barangay" required maxlength="50" placeholder="e.g. Brgy. Mabini">
            <div id="barangay-error" class="error-message"></div>
          </div>
          <div>
            <label for="city_municipality">City/Municipality <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="city_municipality" name="city_municipality" required maxlength="50" placeholder="e.g. Quezon City">
            <div id="city_municipality-error" class="error-message"></div>
          </div>
          <div>
            <label for="province">Province <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="province" name="province" required maxlength="50" placeholder="e.g. Metro Manila">
            <div id="province-error" class="error-message"></div>
          </div>
          <div>
            <label for="zipcode">Zip Code <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="zipcode" name="zipcode" required maxlength="4" inputmode="numeric" pattern="\d*" placeholder="e.g. 1100">
            <div id="zipcode-error" class="error-message"></div>
          </div>
          <div>
            <label for="country">Country <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="country" name="country" required maxlength="50" placeholder="e.g. Philippines">
            <div id="country-error" class="error-message"></div>
          </div>
        </div>
        <?php endif; ?>

        <div style="margin-top:1.35rem;display:flex;justify-content:flex-end;">
          <button type="submit" class="bh-btn bh-btn-primary">Save &amp; Continue to Password Setup <i class="fa-solid fa-arrow-right" style="margin-left:0.4rem;"></i></button>
        </div>
      </form>
    </div>
  </div>

  <footer class="main-footer" style="position:static;margin-top:3rem;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>

  <!-- The approved email domains, straight from php/auth_helpers.php, so the live check and the server rule are always the same list. -->
  <script>window.BH_APPROVED_EMAIL_DOMAINS = <?php echo bh_approved_email_domains_json(); ?>;</script>
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <!-- Live validation, field by field as you type, with the same rules as
       registration (shared in bh-validation-rules.js). save_initial_profile.php
       re-checks everything on the server. -->
  <script src="../js/user-profile-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/user-profile-validation.js'); ?>"></script>
  <script>
    (function () {
      var form = document.getElementById('profileSetupForm');
      if (!form || !window.BHProfileValidation) { return; }
      var validate = function (field) { return window.BHProfileValidation.validateField(field, form); };
      var fields = Array.prototype.slice.call(form.querySelectorAll('input[name], select[name]'));

      // Zip Code: digits only, as on registration (typing, paste, drag-drop).
      var zip = document.getElementById('zipcode');
      zip.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.key.length > 1) { return; }
        if (!/^\d$/.test(e.key)) { e.preventDefault(); }
      });
      zip.addEventListener('input', function () {
        var digits = zip.value.replace(/\D/g, '').slice(0, 4);
        if (zip.value !== digits) { zip.value = digits; validate(zip); }
      });

      // Email: live "already exists" check, as on registration. The account's
      // own address is never reported as taken.
      var email = document.getElementById('email');
      var emailTimer = null;
      function checkEmailTaken() {
        var value = email.value;
        if (!window.BHValidation.validateEmail(value).valid) { return; }
        fetch('../php/check_email.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'email=' + encodeURIComponent(value),
          credentials: 'same-origin'
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (email.value !== value) { return; } // typed on since
            if (data && data.success && data.data && data.data.exists) {
              email.dataset.bhRemoteError = 'Email already exists.';
              email.dataset.bhRemoteValue = value;
            } else {
              delete email.dataset.bhRemoteError;
              delete email.dataset.bhRemoteValue;
            }
            validate(email);
          })
          .catch(function () {});
      }
      email.addEventListener('input', function () {
        clearTimeout(emailTimer);
        emailTimer = setTimeout(checkEmailTaken, 400);
      });
      email.addEventListener('blur', checkEmailTaken);

      // Keep what was typed if the server sends the form back with an error,
      // the same way registration keeps its draft. Stored for this tab only.
      var DRAFT_KEY = 'bhCompleteProfileDraft:<?php echo htmlspecialchars($me['id_number'], ENT_QUOTES); ?>';
      var cameBackWithError = <?php echo $errorMessage !== '' ? 'true' : 'false'; ?>;
      function saveDraft() {
        var data = {};
        fields.forEach(function (f) { if (f.name !== 'age') { data[f.name] = f.value; } });
        try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(data)); } catch (e) {}
      }
      try {
        var saved = cameBackWithError ? JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null') : null;
        if (!cameBackWithError) { sessionStorage.removeItem(DRAFT_KEY); }
        if (saved) {
          fields.forEach(function (f) {
            if (Object.prototype.hasOwnProperty.call(saved, f.name)) { f.value = saved[f.name]; }
          });
          // Re-derive the age and show which fields still need attention.
          fields.forEach(function (f) { if (f.value !== '') { validate(f); } });
          checkEmailTaken();
        }
      } catch (e) {}
      fields.forEach(function (f) {
        f.addEventListener('input', saveDraft);
        f.addEventListener('change', saveDraft);
      });
    })();
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
