<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$month = sanitizeInput($_GET['month'] ?? '');
$date  = sanitizeInput($_GET['date'] ?? '');

// A customer may only see their own login history.
$allSessions = bh_build_login_sessions($conn, ['customer'], $me['id_number']);
$availableMonths = [];
foreach ($allSessions as $s) {
    $m = substr($s['time_in'], 0, 7);
    $availableMonths[$m] = true;
}
krsort($availableMonths);

$sessions = bh_build_login_sessions($conn, ['customer'], $me['id_number'], $month, $date);

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$totalRows = count($sessions);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$pageSessions = array_slice($sessions, $offset, $perPage);

function bh_qs_login_logs($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
$displayId = bh_display_id($me);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Logs - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
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
          <a href="login-logs.php" class="active"><i class="fa-solid fa-right-to-bracket" style="width:16px;"></i> Login Logs</a>
          <a href="profile.php"><i class="fa-solid fa-user" style="width:16px;"></i> Profile</a>
        </div>
      </details>
      <a href="logout.php" class="bh-storefront-logout-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="bh-main" style="max-width:900px;margin:0 auto;">
    <div class="bh-storefront-header">
      <div class="bh-storefront-header-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
      <div>
        <h1>Login Logs</h1>
        <p>A record of when you signed in and out of your account.</p>
      </div>
    </div>

    <div class="bh-panel">
      <form method="get" action="login-logs.php" class="bh-toolbar" style="flex-wrap:wrap;">
        <select name="month" onchange="this.form.submit()">
          <option value="">All Months</option>
          <?php foreach (array_keys($availableMonths) as $m): ?>
            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $month === $m ? 'selected' : ''; ?>><?php echo htmlspecialchars(date('F Y', strtotime($m . '-01'))); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" title="Exact date" onchange="this.form.submit()">
        <?php if ($month !== '' || $date !== ''): ?><a href="login-logs.php" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</a><?php endif; ?>
      </form>

      <div class="bh-table-wrap">
        <table class="bh-table">
          <thead><tr><th><?php echo htmlspecialchars($displayId['label']); ?></th><th>Full Name</th><th>Time In</th><th>Time Out</th></tr></thead>
          <tbody>
            <?php foreach ($pageSessions as $s): ?>
              <tr>
                <td><?php echo htmlspecialchars($s['id_number']); ?></td>
                <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?></td>
                <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($s['time_in']))); ?></td>
                <td><?php echo $s['time_out'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($s['time_out']))) : '<span class="bh-badge bh-badge-active">Still logged in</span>'; ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($pageSessions)): ?><tr><td colspan="4">No login sessions match this filter.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php echo bh_render_pagination($totalRows, $page, $perPage, function ($p) {
          return bh_qs_login_logs(['page' => $p]);
      }, 'sessions'); ?>
    </div>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
