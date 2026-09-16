<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_login();

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id_number = ?");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$isCustomer = $me['role'] === 'customer';
$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
if (!$isCustomer) {
    $notifCount = bh_notification_count($me);
    $notifItems = bh_recent_notifications($me);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
  <?php if ($isCustomer): ?>
  <div class="bh-storefront-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-storefront-nav">
      <a href="dashboard.php">Home</a>
      <a href="menu.php">Menu</a>
      <a href="order-cart.php">My Orders</a>
      <a href="order-history.php">Order History</a>
    </div>
    <div class="bh-storefront-icons">
      <form method="get" action="menu.php" class="bh-header-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Search menu...">
      </form>
      <details class="bh-avatar-menu">
        <summary><span class="bh-avatar bh-avatar-sm" style="background:<?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($initials); ?></span></summary>
        <div class="bh-avatar-dropdown">
          <a href="activity-log.php"><i class="fa-solid fa-clock-rotate-left" style="width:16px;"></i> Activity Log</a>
          <a href="login-logs.php"><i class="fa-solid fa-right-to-bracket" style="width:16px;"></i> Login Logs</a>
          <a href="profile.php"><i class="fa-solid fa-user" style="width:16px;"></i> Profile</a>
        </div>
      </details>
      <a href="logout.php" class="bh-storefront-logout-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <?php else: ?>
  <div class="bh-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-user">
      <div class="bh-notif-wrap">
        <button type="button" class="bh-notif-bell" onclick="bhToggleNotif()" aria-label="Notifications">
          <i class="fa-solid fa-bell"></i>
          <?php if ($notifCount > 0): ?><span class="bh-notif-count"><?php echo $notifCount; ?></span><?php endif; ?>
        </button>
        <div class="bh-notif-dropdown" id="notifDropdown">
          <div class="bh-notif-header">Notifications</div>
          <?php if (empty($notifItems)): ?>
            <div class="bh-notif-empty">You're all caught up.</div>
          <?php else: ?>
            <?php foreach ($notifItems as $n): ?>
              <a href="<?php echo htmlspecialchars($n['link']); ?>" class="bh-notif-item">
                <div><?php echo $n['text']; ?></div>
                <div class="bh-notif-time"><?php echo htmlspecialchars(date('M j, g:i A', strtotime($n['time']))); ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <span class="bh-role-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $me['role']))); ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <?php if ($me['role'] === 'super_admin'): ?>
        <a href="super-admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
        <a href="super-admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
        <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
        <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
        <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
        <a href="profile.php" class="active"><i class="fa-solid fa-user"></i>Profile</a>
      <?php else: ?>
        <a href="admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
        <a href="admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
        <?php if (has_privilege($me, 'approve_accounts')): ?><a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a><?php endif; ?>
        <?php if (has_privilege($me, 'manage_deletion_requests')): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
        <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
        <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
        <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
        <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
        <a href="profile.php" class="active"><i class="fa-solid fa-user"></i>Profile</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="bh-main">
    <h1 class="bh-page-title">My Profile</h1>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div class="bh-alert bh-alert-success">Profile updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'password_updated'): ?>
      <div class="bh-alert bh-alert-success">Password updated successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="bh-alert bh-alert-error">Error: <?php echo htmlspecialchars(bh_account_action_message(bh_confirm_error_message($_GET['error']))); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['pwerror'])): ?>
      <div class="bh-alert bh-alert-error">
        <?php
          $pwErrors = [
            'missing_fields' => 'All password fields are required.',
            'mismatch' => 'New password and confirmation do not match.',
            'too_short' => 'New password must be at least 8 characters.',
            'incorrect_current' => 'Current password is incorrect.',
            'same_as_current' => 'Your new password must be different from your current one.',
          ];
          // Unmapped codes are the strength messages bh_validate_password_strength()
          // returns verbatim, so they are shown as they are.
          echo htmlspecialchars($pwErrors[$_GET['pwerror']] ?? $_GET['pwerror']);
        ?>
      </div>
    <?php endif; ?>

    <div class="bh-panel">
      <h3>Account Details</h3>
      <?php $profileDisplayId = bh_display_id($user); ?>
      <p style="color:var(--bh-text-muted);font-size:0.85rem;">Username: <?php echo htmlspecialchars($user['username']); ?><?php if (!empty($profileDisplayId['value'])): ?> &middot; <?php echo htmlspecialchars($profileDisplayId['label']); ?>: <?php echo htmlspecialchars($profileDisplayId['value']); ?><?php endif; ?></p>
      <form method="post" action="../php/account_update.php"<?php if (!$isCustomer): ?> data-bh-confirm-title="Confirm Update" data-bh-confirm="Are you sure you want to update your account information?" data-bh-confirm-password="1"<?php endif; ?>>
        <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($user['id_number']); ?>">
        <input type="hidden" name="redirect_to" value="profile.php">
        <div class="bh-form-grid">
          <div><label>Last Name</label><input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>"></div>
          <div><label>First Name</label><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>"></div>
          <div><label>Middle Name</label><input type="text" name="middlename" value="<?php echo htmlspecialchars($user['middlename'] ?? ''); ?>"></div>
          <div><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"></div>
          <div><label>Street</label><input type="text" name="street" value="<?php echo htmlspecialchars($user['street']); ?>"></div>
          <div><label>Barangay</label><input type="text" name="barangay" value="<?php echo htmlspecialchars($user['barangay']); ?>"></div>
          <div><label>City/Municipality</label><input type="text" name="city_municipality" value="<?php echo htmlspecialchars($user['city_municipality']); ?>"></div>
          <div><label>Province</label><input type="text" name="province" value="<?php echo htmlspecialchars($user['province']); ?>"></div>
          <div><label>Zip Code</label><input type="text" name="zipcode" value="<?php echo htmlspecialchars($user['zipcode']); ?>"></div>
          <div><label>Country</label><input type="text" name="country" value="<?php echo htmlspecialchars($user['country']); ?>"></div>
        </div>
        <div style="margin-top:1rem;"><button type="submit" class="bh-btn bh-btn-primary">Save Changes</button></div>
      </form>
    </div>

    <div class="bh-panel">
      <h3>Change Password</h3>
      <form method="post" action="../php/change_password.php">
        <div class="bh-form-grid">
          <div><label>Current Password</label><input type="password" name="current_password"></div>
          <div><label>New Password</label><input type="password" name="new_password"></div>
          <div><label>Confirm New Password</label><input type="password" name="confirm_password"></div>
        </div>
        <div style="margin-top:1rem;"><button type="submit" class="bh-btn bh-btn-primary">Update Password</button></div>
      </form>
    </div>
  </div>
  <?php if (!$isCustomer): ?>
  </div>
  <?php endif; ?>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <?php if (!$isCustomer): ?>
  <script>
    function bhToggleNotif() {
      document.getElementById('notifDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
      var wrap = document.querySelector('.bh-notif-wrap');
      var dd = document.getElementById('notifDropdown');
      if (wrap && dd && !wrap.contains(e.target)) { dd.classList.remove('open'); }
    });
  </script>
  <?php endif; ?>
  <!-- The approved email domains, straight from php/auth_helpers.php, so the live check and the server rule are always the same list. -->
  <script>window.BH_APPROVED_EMAIL_DOMAINS = <?php echo bh_approved_email_domains_json(); ?>;</script>
  <!-- The shared validation rules (the front-end mirror of the bh_validate_* helpers) must load before the edit-form validator that uses them. -->
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <script src="../js/user-profile-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/user-profile-validation.js'); ?>"></script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
