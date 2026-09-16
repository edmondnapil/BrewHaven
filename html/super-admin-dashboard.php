<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['super_admin']);

function bh_count($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return (int)($row['c'] ?? 0);
}

$totalUsers   = bh_count($conn, "SELECT COUNT(*) AS c FROM users");
$pendingUsers = bh_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer' AND approval_status = 'pending'");
$totalAdmins  = bh_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'admin'");
$blockedUsers = bh_count($conn, "SELECT COUNT(*) AS c FROM users WHERE account_status = 'blocked'");
$pendingDeleteRequests = bh_count($conn, "SELECT COUNT(*) AS c FROM delete_requests WHERE status = 'pending'");

$activeUsers = bh_count($conn, "SELECT COUNT(*) AS c FROM users WHERE approval_status = 'approved' AND account_status = 'active' AND role = 'customer'");
$superAdmins = bh_count($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'super_admin'");

$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

// Build the donut chart's conic-gradient stops server-side from the account-status breakdown.
$segments = [
    ['label' => 'Active',      'count' => $activeUsers,   'color' => '#5F7A4F'],
    ['label' => 'Pending',     'count' => $pendingUsers,  'color' => '#C98A2C'],
    ['label' => 'Blocked',     'count' => $blockedUsers,  'color' => '#B3452A'],
    ['label' => 'Admins',      'count' => $totalAdmins,   'color' => '#6F4E37'],
    ['label' => 'Super Admins','count' => $superAdmins,   'color' => '#3B2419'],
];
$gradientParts = [];
$cursor = 0;
foreach ($segments as $seg) {
    $slice = $totalUsers > 0 ? ($seg['count'] / $totalUsers) * 360 : 0;
    $start = $cursor;
    $end = $cursor + $slice;
    $gradientParts[] = $seg['color'] . ' ' . round($start, 1) . 'deg ' . round($end, 1) . 'deg';
    $cursor = $end;
}
$donutGradient = implode(', ', $gradientParts);

$recent = [];
$recentResult = mysqli_query($conn, "SELECT id_number, firstname, lastname, username, role, approval_status, account_status, first_login, auth_question1, created_at FROM users ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($recentResult)) { $recent[] = $row; }

// Registration Trend — last 6 calendar months, using the existing created_at field.
$monthLabels = [];
$monthKeys = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $monthKeys[] = date('Y-m', $ts);
    $monthLabels[] = date('M', $ts);
}
$monthCounts = array_fill_keys($monthKeys, 0);
$trendResult = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym");
while ($row = mysqli_fetch_assoc($trendResult)) {
    if (isset($monthCounts[$row['ym']])) { $monthCounts[$row['ym']] = (int)$row['c']; }
}
$trendValues = array_values($monthCounts);
$hasTrendData = array_sum($trendValues) > 0;

// Recent Activity feed from the existing audit log
function bh_activity_icon($eventType) {
    if (strpos($eventType, 'login') !== false) return 'fa-right-to-bracket';
    if (strpos($eventType, 'order') !== false) return 'fa-mug-hot';
    if (strpos($eventType, 'delete') !== false) return 'fa-trash-can';
    if (strpos($eventType, 'block') !== false) return 'fa-ban';
    if (strpos($eventType, 'approve') !== false || strpos($eventType, 'unblock') !== false) return 'fa-circle-check';
    if (strpos($eventType, 'registration') !== false) return 'fa-user-plus';
    if (strpos($eventType, 'staff') !== false || strpos($eventType, 'privilege') !== false) return 'fa-user-shield';
    if (strpos($eventType, 'password') !== false) return 'fa-key';
    return 'fa-circle-info';
}
function bh_activity_label($eventType) {
    $map = [
        'login_success' => 'Logged in',
        'login_failed' => 'Failed login attempt',
        'login_blocked' => 'Login blocked',
        'registration_submitted' => 'New customer registered',
        'account_approved' => 'Customer account approved',
        'account_blocked' => 'Customer account blocked',
        'account_unblocked' => 'Customer account unblocked',
        'account_updated' => 'Account information updated',
        'account_deleted' => 'Account deleted',
        'staff_account_created' => 'Employee account created',
        'delete_request_submitted' => 'Deletion request submitted',
        'delete_request_approved' => 'Deletion request approved',
        'order_placed' => 'New order placed',
        'order_status_changed' => 'Order status updated',
        'super_admin_handover_blocked' => 'Previous Super Admin blocked (handover)',
        'super_admin_handover_activated' => 'New Super Admin activated (handover)',
        'privilege_granted' => 'Privilege granted',
        'privilege_revoked' => 'Privilege revoked',
        'password_reset' => 'Password reset',
        'password_reset_by_admin' => 'Password reset by admin',
        'security_questions_setup' => 'Security questions set up',
    ];
    return $map[$eventType] ?? ucwords(str_replace('_', ' ', $eventType));
}
$activityFeed = [];
$activityResult = mysqli_query($conn, "SELECT l.event_type, l.created_at, l.target_id_number, u.firstname, u.lastname
    FROM security_logs l LEFT JOIN users u ON u.id_number = l.target_id_number
    WHERE l.event_type NOT IN ('login_success','login_failed','login_blocked')
    ORDER BY l.created_at DESC LIMIT 6");
while ($row = mysqli_fetch_assoc($activityResult)) { $activityFeed[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Dashboard - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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
      <a href="super-admin-dashboard.php" class="active"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="super-admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">

      <div class="bh-dash-banner">
        <div class="bh-dash-banner-text">
          <h1>Welcome back, <?php echo htmlspecialchars($me['firstname']); ?>!</h1>
          <p>Here's the full picture across every account in Brew Haven.</p>
        </div>
      </div>

      <div class="bh-cards">
        <div class="bh-card" style="--bh-card-accent:#6F4E37;"><div class="bh-card-icon"><i class="fa-solid fa-users"></i></div><div><div class="bh-card-num"><?php echo $totalUsers; ?></div><div class="bh-card-label">Total Users</div></div></div>
        <div class="bh-card" style="--bh-card-accent:#3B2419;"><div class="bh-card-icon"><i class="fa-solid fa-user-tie"></i></div><div><div class="bh-card-num"><?php echo $totalAdmins; ?></div><div class="bh-card-label">Administrators</div></div></div>
        <div class="bh-card" style="--bh-card-accent:#C98A2C;"><div class="bh-card-icon"><i class="fa-solid fa-clock"></i></div><div><div class="bh-card-num"><?php echo $pendingUsers; ?></div><div class="bh-card-label">Pending Customers</div></div></div>
        <div class="bh-card" style="--bh-card-accent:#B3452A;"><div class="bh-card-icon"><i class="fa-solid fa-ban"></i></div><div><div class="bh-card-num"><?php echo $blockedUsers; ?></div><div class="bh-card-label">Blocked Accounts</div></div></div>
        <div class="bh-card" style="--bh-card-accent:#5F7A4F;"><div class="bh-card-icon"><i class="fa-solid fa-trash-can"></i></div><div><div class="bh-card-num"><?php echo $pendingDeleteRequests; ?></div><div class="bh-card-label">Pending Delete Requests</div></div></div>
      </div>

      <div class="bh-panel">
        <h3>Registration Trends</h3>
        <div class="bh-chart-wrap">
          <?php if ($hasTrendData): ?>
            <canvas id="trendChart"></canvas>
          <?php else: ?>
            <div class="bh-chart-empty"><i class="fa-solid fa-chart-line"></i>No registration data available yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bh-dash-grid-2">
        <div class="bh-panel">
          <h3>Recent Registrations</h3>
          <div class="bh-table-wrap">
            <table class="bh-table">
              <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>ID</th><th>Status</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($recent as $r): $recentDisplayId = bh_display_id($r); ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                    <td><?php echo htmlspecialchars($r['role']); ?></td>
                    <td title="<?php echo htmlspecialchars($recentDisplayId['label']); ?>"><?php echo htmlspecialchars($recentDisplayId['value'] ?? '—'); ?></td>
                    <td><?php echo bh_account_status_badge($r); ?></td>
                    <td><?php echo htmlspecialchars(date('M j, Y', strtotime($r['created_at']))); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="bh-actions" style="margin-top:1rem;">
            <a href="super-admin-accounts.php?approval_filter=pending" class="bh-btn bh-btn-secondary bh-btn-sm">View All Pending</a>
            <a href="super-admin-accounts.php" class="bh-btn bh-btn-primary bh-btn-sm">Manage Accounts</a>
          </div>
        </div>

        <div class="bh-panel">
          <h3>Account Status</h3>
          <div class="bh-donut-wrap" style="justify-content:center;">
            <div style="position:relative;">
              <div class="bh-donut" style="--bh-donut: <?php echo $donutGradient; ?>;"></div>
              <div class="bh-donut-center">
                <div class="bh-donut-num"><?php echo $totalUsers; ?></div>
                <div class="bh-donut-label">Total</div>
              </div>
            </div>
          </div>
          <div class="bh-donut-legend" style="margin-top:1rem;">
            <?php foreach ($segments as $seg): ?>
              <div class="bh-legend-row"><span class="bh-dot" style="background:<?php echo $seg['color']; ?>;"></span><?php echo $seg['label']; ?><span class="bh-legend-count"><?php echo $seg['count']; ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="bh-panel">
        <h3>Recent Activity</h3>
        <?php if (empty($activityFeed)): ?>
          <div class="bh-chart-empty"><i class="fa-solid fa-clock-rotate-left"></i>No activity recorded yet.</div>
        <?php else: ?>
          <div class="bh-activity-list">
            <?php foreach ($activityFeed as $a): ?>
              <div class="bh-activity-row">
                <div class="bh-activity-icon"><i class="fa-solid <?php echo bh_activity_icon($a['event_type']); ?>"></i></div>
                <div class="bh-activity-body">
                  <div class="bh-activity-title"><?php echo htmlspecialchars(bh_activity_label($a['event_type'])); ?></div>
                  <?php if (!empty($a['firstname'])): ?><div class="bh-activity-meta"><?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname']); ?></div><?php endif; ?>
                </div>
                <div class="bh-activity-time"><?php echo htmlspecialchars(date('M j, g:i A', strtotime($a['created_at']))); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    var bhChartFont = "'Inter', 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.family = bhChartFont;
    Chart.defaults.color = '#6b5644';

    <?php if ($hasTrendData): ?>
    new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: <?php echo json_encode($monthLabels); ?>,
        datasets: [{
          label: 'New Registrations',
          data: <?php echo json_encode($trendValues); ?>,
          borderColor: '#6F4E37',
          backgroundColor: 'rgba(111, 78, 55, 0.12)',
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#6F4E37',
          pointRadius: 4,
          pointHoverRadius: 6,
          borderWidth: 2.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#3B2419', padding: 10, cornerRadius: 8, titleFont: { weight: '700' } } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(111,78,55,0.08)' } },
          x: { grid: { display: false } }
        }
      }
    });
    <?php endif; ?>

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
