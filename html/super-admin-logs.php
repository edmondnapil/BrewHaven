<?php
header('Location: logs.php');
exit;
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'view_activity_logs');
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

$eventFilter = sanitizeInput($_GET['event_type'] ?? '');
$roleFilter  = sanitizeInput($_GET['role_filter'] ?? '');
$userFilter  = sanitizeInput($_GET['user_filter'] ?? '');
$dateFrom    = sanitizeInput($_GET['date_from'] ?? '');
$dateTo      = sanitizeInput($_GET['date_to'] ?? '');
$validRoles  = ['super_admin', 'admin', 'customer'];

$where = [];
$params = [];
$types = '';
if ($eventFilter !== '') { $where[] = 'l.event_type = ?'; $params[] = $eventFilter; $types .= 's'; }
if (in_array($roleFilter, $validRoles, true)) { $where[] = 'u.role = ?'; $params[] = $roleFilter; $types .= 's'; }
if ($userFilter !== '') {
    $where[] = "(l.actor_id_number LIKE ? OR u.username LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ?)";
    $like = '%' . $userFilter . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($dateFrom !== '') { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo !== '') { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $dateTo; $types .= 's'; }
$whereSql = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';
$joinSql = " FROM security_logs l LEFT JOIN users u ON u.id_number = l.actor_id_number";

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Export everything matching the filter, not just the current page.
    $exportStmt = mysqli_prepare($conn, "SELECT l.*, u.firstname, u.lastname, u.role AS actor_role" . $joinSql . $whereSql . " ORDER BY l.created_at DESC LIMIT 5000");
    if (!empty($params)) { mysqli_stmt_bind_param($exportStmt, $types, ...$params); }
    mysqli_stmt_execute($exportStmt);
    $exportResult = mysqli_stmt_get_result($exportStmt);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="brew-haven-activity-logs-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    $auditColumns = bh_audit_columns_available();
    $header = ['Time', 'Event', 'Actor ID', 'Actor Name', 'Actor Role', 'Target ID', 'IP Address', 'Browser', 'OS', 'Device', 'Details'];
    if ($auditColumns) { array_push($header, 'Previous State', 'New State', 'Reason', 'Proof'); }
    fputcsv($out, $header);
    while ($l = mysqli_fetch_assoc($exportResult)) {
        $ua = bh_parse_user_agent($l['user_agent']);
        $row = [
            $l['created_at'],
            $l['event_type'],
            $l['actor_id_number'] ?? '',
            trim(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? '')),
            $l['actor_role'] ?? '',
            $l['target_id_number'] ?? '',
            $l['ip_address'] ?? '',
            $ua['browser'],
            $ua['os'],
            $ua['device'],
            $l['details'] ?? '',
        ];
        if ($auditColumns) {
            // The reason is stored HTML-escaped; decode it so the CSV carries
            // the text the actor actually typed.
            array_push($row,
                $l['previous_state'] ?? '',
                $l['new_state'] ?? '',
                html_entity_decode((string)($l['reason'] ?? ''), ENT_QUOTES, 'UTF-8'),
                $l['proof_path'] ?? ''
            );
        }
        fputcsv($out, $row);
    }
    mysqli_stmt_close($exportStmt);
    fclose($out);
    exit;
}

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c" . $joinSql . $whereSql);
if (!empty($params)) { mysqli_stmt_bind_param($countStmt, $types, ...$params); }
mysqli_stmt_execute($countStmt);
$totalRows = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);
mysqli_stmt_close($countStmt);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$baseSql = "SELECT l.*, u.firstname, u.lastname, u.role AS actor_role" . $joinSql . $whereSql . " ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = mysqli_prepare($conn, $baseSql);
if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = [];
while ($row = mysqli_fetch_assoc($result)) { $logs[] = $row; }
mysqli_stmt_close($stmt);

$eventTypesResult = mysqli_query($conn, "SELECT DISTINCT event_type FROM security_logs ORDER BY event_type ASC");
$eventTypes = [];
while ($row = mysqli_fetch_assoc($eventTypesResult)) { $eventTypes[] = $row['event_type']; }

function bh_qs_logs($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Activity Logs - Brew Haven Super Admin</title>
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
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <h1 class="bh-page-title" style="margin-bottom:0;">Activity Logs</h1>
        <a href="<?php echo bh_qs_logs(['export' => 'csv']); ?>" class="bh-btn bh-btn-secondary"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
      </div>
      <p style="color:var(--bh-text-muted);margin-top:0.4rem;margin-bottom:1.2rem;">Live system audit trail &middot; <?php echo $totalRows; ?> entr<?php echo $totalRows === 1 ? 'y' : 'ies'; ?> match this filter</p>

      <div class="bh-panel">
        <form method="get" action="super-admin-logs.php" class="bh-toolbar" style="flex-wrap:wrap;">
          <input type="text" name="user_filter" placeholder="Filter by user (ID, name, username)" value="<?php echo htmlspecialchars($userFilter); ?>">
          <select name="event_type">
            <option value="">All Actions</option>
            <?php foreach ($eventTypes as $et): ?>
              <option value="<?php echo htmlspecialchars($et); ?>" <?php echo $eventFilter === $et ? 'selected' : ''; ?>><?php echo htmlspecialchars($et); ?></option>
            <?php endforeach; ?>
          </select>
          <select name="role_filter">
            <option value="">All Roles</option>
            <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
          </select>
          <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From date">
          <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" title="To date">
          <button type="submit" class="bh-btn bh-btn-secondary bh-btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
          <a href="logs.php" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</a>
        </form>
        <div class="bh-table-wrap">
          <table class="bh-table">
            <thead><tr><th>User</th><th>Role</th><th>Action</th><th>Device</th><th>Date/Time</th><th>IP Address</th><th>Details</th></tr></thead>
            <tbody>
              <?php foreach ($logs as $l): $ua = bh_parse_user_agent($l['user_agent']); ?>
                <tr>
                  <td><?php echo $l['firstname'] ? htmlspecialchars($l['firstname'] . ' ' . $l['lastname']) : htmlspecialchars($l['actor_id_number'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($l['actor_role'] ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($l['event_type']); ?></td>
                  <td><?php echo htmlspecialchars($ua['device'] . ' / ' . $ua['browser']); ?></td>
                  <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($l['created_at']))); ?></td>
                  <td><?php echo htmlspecialchars($l['ip_address'] ?? '—'); ?></td>
                  <td style="max-width:260px;white-space:normal;font-size:0.78rem;color:var(--bh-text-muted);">
                    <?php echo htmlspecialchars($l['details'] ?? ''); ?>
                    <?php if ($l['target_id_number']): ?><br>Target: <?php echo htmlspecialchars($l['target_id_number']); ?><?php endif; ?>
                    <?php echo bh_log_audit_detail_html($l); ?>
                    <br>OS: <?php echo htmlspecialchars($ua['os']); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($logs)): ?><tr><td colspan="7">No log entries match this filter.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php echo bh_render_pagination($totalRows, $page, $perPage, function ($p) {
            return bh_qs_logs(['page' => $p]);
        }, 'entries'); ?>
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
