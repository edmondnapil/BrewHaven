<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$eventFilter = sanitizeInput($_GET['event_type'] ?? '');
$where = ['actor_id_number = ?'];
$params = [$me['id_number']];
$types = 's';
if ($eventFilter !== '') { $where[] = 'event_type = ?'; $params[] = $eventFilter; $types .= 's'; }
$whereSql = ' WHERE ' . implode(' AND ', $where);

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM security_logs" . $whereSql);
mysqli_stmt_bind_param($countStmt, $types, ...$params);
mysqli_stmt_execute($countStmt);
$totalRows = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);
mysqli_stmt_close($countStmt);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM security_logs" . $whereSql . " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = [];
while ($row = mysqli_fetch_assoc($result)) { $logs[] = $row; }
mysqli_stmt_close($stmt);

$eventTypesResult = mysqli_prepare($conn, "SELECT DISTINCT event_type FROM security_logs WHERE actor_id_number = ? ORDER BY event_type ASC");
mysqli_stmt_bind_param($eventTypesResult, 's', $me['id_number']);
mysqli_stmt_execute($eventTypesResult);
$eventTypesRes = mysqli_stmt_get_result($eventTypesResult);
$eventTypes = [];
while ($row = mysqli_fetch_assoc($eventTypesRes)) { $eventTypes[] = $row['event_type']; }

function bh_event_icon($eventType) {
    if (strpos($eventType, 'login') !== false) return 'fa-right-to-bracket';
    if (strpos($eventType, 'order') !== false) return 'fa-mug-hot';
    if (strpos($eventType, 'password') !== false) return 'fa-key';
    if (strpos($eventType, 'security_question') !== false || strpos($eventType, 'secret_question') !== false) return 'fa-shield-halved';
    if (strpos($eventType, 'registration') !== false) return 'fa-user-plus';
    return 'fa-circle-info';
}
function bh_event_label($eventType) {
    return ucwords(str_replace('_', ' ', $eventType));
}

function bh_qs_activity($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Activity Log - Brew Haven</title>
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
          <a href="activity-log.php" class="active"><i class="fa-solid fa-clock-rotate-left" style="width:16px;"></i> Activity Log</a>
          <a href="login-logs.php"><i class="fa-solid fa-right-to-bracket" style="width:16px;"></i> Login Logs</a>
          <a href="profile.php"><i class="fa-solid fa-user" style="width:16px;"></i> Profile</a>
        </div>
      </details>
      <a href="logout.php" class="bh-storefront-logout-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="bh-main" style="max-width:900px;margin:0 auto;">
    <div class="bh-storefront-header">
      <div class="bh-storefront-header-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
      <div>
        <h1>Activity Log</h1>
        <p>A record of logins, orders, and account activity on your account.</p>
      </div>
    </div>

    <div class="bh-panel">
      <form method="get" action="activity-log.php" class="bh-toolbar">
        <select name="event_type" onchange="this.form.submit()">
          <option value="">All Activity</option>
          <?php foreach ($eventTypes as $et): ?>
            <option value="<?php echo htmlspecialchars($et); ?>" <?php echo $eventFilter === $et ? 'selected' : ''; ?>><?php echo htmlspecialchars(bh_event_label($et)); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($eventFilter !== ''): ?><a href="activity-log.php" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</a><?php endif; ?>
      </form>

      <div class="bh-activity-list">
        <?php foreach ($logs as $l): $ua = bh_parse_user_agent($l['user_agent']); ?>
          <div class="bh-activity-row">
            <div class="bh-activity-icon"><i class="fa-solid <?php echo bh_event_icon($l['event_type']); ?>"></i></div>
            <div class="bh-activity-body">
              <div class="bh-activity-title"><?php echo htmlspecialchars(bh_event_label($l['event_type'])); ?></div>
              <?php if (!empty($l['details'])): ?><div class="bh-activity-details"><?php echo htmlspecialchars($l['details']); ?></div><?php endif; ?>
              <div class="bh-activity-meta"><?php echo htmlspecialchars($ua['device'] . ' · ' . $ua['browser'] . ' · ' . $ua['os']); ?></div>
            </div>
            <div class="bh-activity-time"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($l['created_at']))); ?></div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
          <div class="bh-activity-empty">No activity recorded yet.</div>
        <?php endif; ?>
      </div>

      <?php echo bh_render_pagination($totalRows, $page, $perPage, function ($p) {
        return bh_qs_activity(['page' => $p]);
      }, 'entries'); ?>
    </div>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
