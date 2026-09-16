<?php
header('Location: logs.php');
exit;
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'view_login_logs');
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

$search = sanitizeInput($_GET['search'] ?? '');
$month  = sanitizeInput($_GET['month'] ?? '');
$date   = sanitizeInput($_GET['date'] ?? '');

// Super Admin sees every role's login history.
$allSessions = bh_build_login_sessions($conn, [], null, '', '', $search);
$availableMonths = [];
foreach ($allSessions as $s) {
    $m = substr($s['time_in'], 0, 7);
    $availableMonths[$m] = true;
}
krsort($availableMonths);

$sessions = bh_build_login_sessions($conn, [], null, $month, $date, $search);

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$totalRows = count($sessions);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$pageSessions = array_slice($sessions, $offset, $perPage);

function bh_login_role_label($role) {
    if ($role === 'super_admin') return 'Super Admin';
    if ($role === 'admin') return 'Admin';
    return 'Customer';
}
function bh_qs_login_logs($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Logs - Brew Haven Super Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
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
      <span class="bh-role-label">Super Admin</span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="super-admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="super-admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php" class="active"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title" style="margin-bottom:0;">Login Logs</h1>
      <p style="color:var(--bh-text-muted);margin-top:0.4rem;margin-bottom:1.2rem;">Time in / time out history for every account &middot; <?php echo $totalRows; ?> session<?php echo $totalRows === 1 ? '' : 's'; ?> match this filter</p>

      <div class="bh-panel">
        <form method="get" action="super-admin-login-logs.php" class="bh-toolbar" style="flex-wrap:wrap;">
          <input type="text" name="search" placeholder="Search by ID No., name, or username" value="<?php echo htmlspecialchars($search); ?>">
          <select name="month">
            <option value="">All Months</option>
            <?php foreach (array_keys($availableMonths) as $m): ?>
              <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $month === $m ? 'selected' : ''; ?>><?php echo htmlspecialchars(date('F Y', strtotime($m . '-01'))); ?></option>
            <?php endforeach; ?>
          </select>
          <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" title="Exact date">
          <button type="submit" class="bh-btn bh-btn-secondary bh-btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
          <a href="logs.php" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</a>
        </form>

        <div class="bh-table-wrap">
          <table class="bh-table">
            <thead><tr><th>ID No.</th><th>Full Name</th><th>Role</th><th>Time In</th><th>Time Out</th></tr></thead>
            <tbody>
              <?php foreach ($pageSessions as $s): ?>
                <tr>
                  <td><?php echo htmlspecialchars($s['id_number']); ?></td>
                  <td><?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?></td>
                  <td><?php echo htmlspecialchars(bh_login_role_label($s['role'])); ?></td>
                  <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($s['time_in']))); ?></td>
                  <td><?php echo $s['time_out'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($s['time_out']))) : '<span class="bh-badge bh-badge-active">Still logged in</span>'; ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($pageSessions)): ?><tr><td colspan="5">No login sessions match this filter.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php echo bh_render_pagination($totalRows, $page, $perPage, function ($p) {
            return bh_qs_login_logs(['page' => $p]);
        }, 'sessions'); ?>
      </div>
    </div>
  </div>
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
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
