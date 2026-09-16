<?php
require_once __DIR__ . '/../php/auth_helpers.php';

$me = require_login();
// Every Admin sees both Activity and Login logs. A normal Admin is scoped to
// customer-related entries and their own; the "View All Logs" privilege lifts
// that scope so the Admin sees every entry, as a Super Admin does.
$canViewActivity = in_array($me['role'], ['super_admin', 'admin'], true);
$canViewLogin = $canViewActivity;
$isScopedAdmin = $me['role'] === 'admin' && !has_privilege($me, 'view_logs');
if (!$canViewActivity && !$canViewLogin) {
    header('Location: ' . bh_page_url(role_home_page($me['role'])) . '?error=insufficient_privilege');
    exit;
}

$logType = $_GET['log_type'] ?? 'all';
if (!in_array($logType, ['all', 'activity', 'login'], true)) { $logType = 'all'; }
$search = sanitizeInput($_GET['search'] ?? '');
$eventFilter = sanitizeInput($_GET['event_type'] ?? '');
$roleFilter = sanitizeInput($_GET['role_filter'] ?? '');
$dateFrom = sanitizeInput($_GET['date_from'] ?? '');
$dateTo = sanitizeInput($_GET['date_to'] ?? '');
$validRoles = ['super_admin', 'admin', 'customer'];
$loginEventTypes = ['login_success', 'login_failed', 'logout'];

$where = [];
$params = [];
$types = '';
$joinSql = " FROM security_logs l LEFT JOIN users u ON u.id_number = l.actor_id_number LEFT JOIN users target_user ON target_user.id_number = l.target_id_number";

if ($isScopedAdmin) {
  $where[] = "(u.role = 'customer' OR target_user.role = 'customer' OR l.actor_id_number = ? OR l.target_id_number = ?)";
  $params[] = $me['id_number'];
  $params[] = $me['id_number'];
  $types .= 'ss';
}
if (!$canViewActivity) {
    $logType = 'login';
}
if ($logType === 'login') {
    $where[] = "l.event_type IN ('login_success', 'login_failed', 'logout')";
} elseif ($logType === 'activity') {
    $where[] = "l.event_type NOT IN ('login_success', 'login_failed', 'logout')";
}
if ($eventFilter !== '') {
    $where[] = 'l.event_type = ?';
    $params[] = $eventFilter;
    $types .= 's';
}
if ($isScopedAdmin) {
  if ($roleFilter === 'customer') {
    $where[] = "(u.role = 'customer' OR target_user.role = 'customer')";
  } elseif ($roleFilter === 'self') {
    $where[] = '(l.actor_id_number = ? OR l.target_id_number = ?)';
    $params[] = $me['id_number'];
    $params[] = $me['id_number'];
    $types .= 'ss';
  }
} elseif (in_array($roleFilter, $validRoles, true)) {
  $where[] = 'u.role = ?';
  $params[] = $roleFilter;
  $types .= 's';
}
if ($search !== '') {
    $where[] = "(l.actor_id_number LIKE ? OR l.target_id_number LIKE ? OR u.username LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR l.event_type LIKE ? OR l.details LIKE ?)";
    $like = '%' . $search . '%';
    for ($i = 0; $i < 6; $i++) { $params[] = $like; $types .= 's'; }
}
if ($dateFrom !== '') { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo !== '') { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $dateTo; $types .= 's'; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS c' . $joinSql . $whereSql);
if ($params) { mysqli_stmt_bind_param($countStmt, $types, ...$params); }
mysqli_stmt_execute($countStmt);
$totalRows = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);
mysqli_stmt_close($countStmt);

$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT l.*, u.firstname, u.lastname, u.username, u.role AS actor_role, target_user.firstname AS target_firstname, target_user.lastname AS target_lastname' . $joinSql . $whereSql . " ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if ($params) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = [];
while ($row = mysqli_fetch_assoc($result)) { $logs[] = $row; }
mysqli_stmt_close($stmt);

$eventTypes = [];
$eventResult = mysqli_query($conn, 'SELECT DISTINCT event_type FROM security_logs ORDER BY event_type ASC');
while ($row = mysqli_fetch_assoc($eventResult)) {
    $event = $row['event_type'];
    if ($logType === 'login' && !in_array($event, $loginEventTypes, true)) continue;
    if ($logType === 'activity' && in_array($event, $loginEventTypes, true)) continue;
    if (!$canViewActivity && !in_array($event, $loginEventTypes, true)) continue;
    $eventTypes[] = $event;
}

function bh_log_action_label($event) {
    $labels = [
        'login_success' => 'Login Successful',
        'login_failed' => 'Failed Login',
        'logout' => 'Logout',
    ];
    return $labels[$event] ?? ucwords(str_replace('_', ' ', $event));
}
function bh_log_qs($overrides) {
    return '?' . http_build_query(array_merge($_GET, $overrides));
}
function bh_log_type($event) {
    return in_array($event, ['login_success', 'login_failed', 'logout'], true) ? 'LOGIN' : 'ACTIVITY';
}
function bh_log_type_class($event) {
    return bh_log_type($event) === 'LOGIN' ? 'bh-badge-active' : 'bh-badge-approved';
}
// ---------------------------------------------------------------------------
// Readable "Details" cell
// ---------------------------------------------------------------------------
// The stored details / previous_state / new_state are compact machine text
// ("target_role=customer", "status=active"). They stay untouched in the
// database and the CSV export; only the table renders them for people.

// Splits "key=value key2=value two" into [prose, [key => value]]. A value runs
// until the next " key=" so values containing spaces survive.
function bh_log_parse_pairs($text) {
    $text = trim((string)$text);
    $pairs = [];
    if ($text === '') {
        return ['', $pairs];
    }
    // State strings written as "a=1; b=2" (account updates).
    if (strpos($text, ';') !== false && preg_match('/^[A-Za-z_]+=/', $text)) {
        foreach (explode(';', $text) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2 && $kv[0] !== '') { $pairs[$kv[0]] = trim($kv[1]); }
        }
        return ['', $pairs];
    }
    $prose = preg_replace_callback('/\b([A-Za-z_]+)=((?:(?!\s+[A-Za-z_]+=).)*)/', function ($m) use (&$pairs) {
        $pairs[$m[1]] = trim($m[2], " ,");
        return ' ';
    }, $text);
    $prose = trim(preg_replace('/\s+/', ' ', $prose), " ,:;");
    return [$prose, $pairs];
}

function bh_log_detail_label($key) {
    $labels = [
        'target_role' => 'Role', 'role' => 'Role', 'old_role' => 'Old role', 'new_role' => 'New role',
        'status' => 'Status', 'account' => 'Account status', 'account_status' => 'Account status', 'approval' => 'Approval',
        'request_id' => 'Request', 'order_id' => 'Order', 'product_id' => 'Product',
        'privilege' => 'Privilege', 'masked_email' => 'Email', 'action' => 'Attempted action',
        'code' => 'Result', 'id_number' => 'ID Number', 'correctCount' => 'Correct answers',
        'delete_request' => 'Deletion request', 'proof' => 'Attachment', 'target' => 'Account',
        'password' => 'Password', 'replaced_by' => 'Replaced by', 'replaces' => 'Replaces',
        'middlename' => 'Middle name', 'firstname' => 'First name', 'lastname' => 'Last name',
        'birth_date' => 'Birth date', 'city_municipality' => 'City / Municipality', 'zipcode' => 'Zip code',
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function bh_log_detail_value($key, $value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '(empty)';
    }
    $roles = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'customer' => 'Customer'];
    if (isset($roles[$value])) {
        return $roles[$value];
    }
    switch ($key) {
        case 'request_id':
        case 'order_id':
        case 'product_id':
            return '#' . $value;
        case 'privilege':
            return bh_privilege_label($value);
        case 'action':
            if (strpos($value, 'verify:') === 0) { return 'Password confirmation'; }
            return ucfirst(str_replace(['account_', '_'], ['', ' '], $value));
        case 'code':
            return bh_account_action_message($value) !== $value
                ? bh_account_action_message($value)
                : ucfirst(str_replace('_', ' ', $value));
        case 'status':
        case 'account':
        case 'account_status':
        case 'approval':
        case 'delete_request':
            return ucfirst($value);
        case 'proof':
            return 'Document attached';
    }
    return $value;
}

// Internal identifiers inside free-text details, turned into words.
function bh_log_humanize_prose($prose) {
    $prose = strtr($prose, [
        'first_login_otp' => 'first-login verification code',
        'first_login'     => 'first-login',
        'find_account'    => 'account lookup',
        'super_admin'     => 'Super Admin',
        'otp_success'     => 'verification code accepted',
    ]);
    return ucfirst(str_replace('_', ' ', $prose));
}

// Keys that repeat what another column already shows.
function bh_log_hidden_detail_keys($eventType) {
    $hidden = ['proof'];
    if ($eventType === 'staff_account_created' || $eventType === 'customer_account_created') {
        $hidden[] = 'id_number';
    }
    return $hidden;
}

function bh_log_details_html($log) {
    $lines = [];
    $label = function ($text) {
        return '<span style="color:var(--bh-text-muted);">' . htmlspecialchars($text) . ':</span> ';
    };

    [$prose, $pairs] = bh_log_parse_pairs($log['details'] ?? '');
    $previousRaw = trim((string)($log['previous_state'] ?? ''));
    $newRaw      = trim((string)($log['new_state'] ?? ''));
    $hasChange   = ($previousRaw !== '' || $newRaw !== '');

    // Summary sentence, e.g. "Direct delete by Super Admin". The long field
    // list of an account update is replaced by the actual changes below.
    if ($prose !== '' && !($hasChange && stripos($prose, 'fields:') === 0)) {
        $lines[] = '<div style="color:var(--bh-text);font-weight:500;">' . htmlspecialchars(bh_log_humanize_prose($prose)) . '</div>';
    }

    // Target account, by name when it still exists, when it is not simply the actor.
    $targetId = trim((string)($log['target_id_number'] ?? ''));
    $actorId  = trim((string)($log['actor_id_number'] ?? ''));
    if ($targetId !== '' && $targetId !== $actorId) {
        $targetName = trim(($log['target_firstname'] ?? '') . ' ' . ($log['target_lastname'] ?? ''));
        if ($targetName === '' && isset($pairs['target'])) {
            $targetName = $pairs['target'];
        }
        unset($pairs['target']);
        $idHtml = '<span style="white-space:nowrap;">' . htmlspecialchars($targetId) . '</span>';
        $lines[] = '<div>' . $label('Target') . ($targetName !== '' ? htmlspecialchars($targetName) . ' · ' . $idHtml : $idHtml) . '</div>';
    }

    // Facts from the details text. A role change shows as Old → New.
    $roleShown = isset($pairs['old_role'], $pairs['new_role']);
    if ($roleShown) {
        $lines[] = '<div>' . $label('Role') . htmlspecialchars(bh_log_detail_value('role', $pairs['old_role']))
            . ' &rarr; <strong>' . htmlspecialchars(bh_log_detail_value('role', $pairs['new_role'])) . '</strong></div>';
        unset($pairs['old_role'], $pairs['new_role']);
    }
    foreach ($pairs as $key => $value) {
        if (in_array($key, bh_log_hidden_detail_keys($log['event_type'] ?? ''), true)) { continue; }
        $lines[] = '<div>' . $label(bh_log_detail_label($key)) . htmlspecialchars(bh_log_detail_value($key, $value)) . '</div>';
    }

    // Before → after, one line per field that changed.
    if ($hasChange) {
        [$prevProse, $prev] = bh_log_parse_pairs($previousRaw);
        [$newProse, $new]   = bh_log_parse_pairs($newRaw);
        $changes = [];
        // Whole-record outcome ("deleted"): one line, not one per field.
        if (!$new && $newProse !== '' && $prev) {
            $was = [];
            foreach ($prev as $key => $value) { $was[] = bh_log_detail_label($key) . ' ' . bh_log_detail_value($key, $value); }
            $changes[] = '<div>' . $label('Changed') . htmlspecialchars(implode(', ', $was))
                . ' &rarr; <strong>' . htmlspecialchars(ucfirst($newProse)) . '</strong></div>';
            $prev = [];
        }
        // A password change never shows the (hashed) values, only that it happened.
        if (isset($prev['password']) || isset($new['password'])) {
            $changes[] = '<div>' . $label('Password') . '<strong>Reset by an administrator</strong></div>';
            unset($prev['password'], $new['password']);
        }
        // Different fields before and after (e.g. Status Active → Deletion
        // request Pending): one line instead of two half-empty ones.
        if ($prev && $new && !array_intersect_key($prev, $new)) {
            $describe = function ($set) {
                $out = [];
                foreach ($set as $key => $value) { $out[] = bh_log_detail_label($key) . ' ' . bh_log_detail_value($key, $value); }
                return implode(', ', $out);
            };
            $changes[] = '<div>' . $label('Changed') . htmlspecialchars($describe($prev))
                . ' &rarr; <strong>' . htmlspecialchars($describe($new)) . '</strong></div>';
            $prev = $new = [];
        }
        foreach (array_unique(array_merge(array_keys($prev), array_keys($new))) as $key) {
            if ($key === 'role' && $roleShown) { continue; }
            $from = array_key_exists($key, $prev) ? bh_log_detail_value($key, $prev[$key]) : null;
            $to   = array_key_exists($key, $new) ? bh_log_detail_value($key, $new[$key]) : null;
            if ($from !== null && $to !== null && $from === $to) { continue; }
            $changes[] = '<div>' . $label(bh_log_detail_label($key))
                . ($from !== null ? htmlspecialchars($from) : '—')
                . ' &rarr; <strong>' . ($to !== null ? htmlspecialchars($to) : htmlspecialchars(ucfirst($newProse ?: '—'))) . '</strong></div>';
        }
        if (!$changes && ($prevProse !== '' || $newProse !== '')) {
            $changes[] = '<div>' . $label('Changed') . htmlspecialchars(ucfirst($prevProse ?: '—'))
                . ' &rarr; <strong>' . htmlspecialchars(ucfirst($newProse ?: '—')) . '</strong></div>';
        }
        if ($changes) {
            $lines[] = '<div style="margin-top:0.2rem;padding-top:0.2rem;border-top:1px dashed var(--bh-border, rgba(0,0,0,0.12));">' . implode('', $changes) . '</div>';
        }
    }

    return $lines ? implode('', $lines) : '—';
}

function bh_render_log_rows($logs) {
    ob_start();
    foreach ($logs as $log): ?>
      <tr>
        <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($log['created_at']))); ?></td>
        <td><?php echo $log['firstname'] ? htmlspecialchars(trim($log['firstname'] . ' ' . $log['lastname'])) : 'Unknown'; ?></td>
        <td><?php echo htmlspecialchars($log['actor_id_number'] ?: ($log['target_id_number'] ?: '—')); ?></td>
        <td><span class="bh-badge <?php echo bh_log_type_class($log['event_type']); ?>"><?php echo bh_log_type($log['event_type']); ?></span></td>
        <td><?php echo htmlspecialchars(bh_log_action_label($log['event_type'])); ?></td>
        <td style="max-width:320px;white-space:normal;font-size:0.78rem;line-height:1.45;color:var(--bh-text);"><?php echo bh_log_details_html($log); ?></td>
        <td style="white-space:nowrap;font-size:0.78rem;"><?php echo bh_log_document_cell_html($log); ?></td>
        <td style="max-width:240px;white-space:normal;font-size:0.78rem;color:var(--bh-text-muted);"><?php echo bh_log_reason_cell_html($log); ?></td>
      </tr>
    <?php endforeach;
    if (empty($logs)): ?><tr><td colspan="8">No log entries match this filter.</td></tr><?php endif;
    return ob_get_clean();
}
function bh_render_log_pagination($totalRows, $page, $totalPages, $offset, $perPage) {
    return bh_render_pagination($totalRows, $page, $perPage, function ($p) {
        return bh_log_qs(['page' => $p]);
    }, 'entries');
}

if (isset($_GET['partial']) && $_GET['partial'] === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'rows' => bh_render_log_rows($logs),
        'pagination' => bh_render_log_pagination($totalRows, $page, $totalPages, $offset, $perPage),
        'total' => $totalRows,
        'logType' => $logType,
        'eventTypes' => $eventTypes,
    ]);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportSql = 'SELECT l.*, u.firstname, u.lastname, u.username, u.role AS actor_role' . $joinSql . $whereSql . ' ORDER BY l.created_at DESC LIMIT 5000';
    $exportStmt = mysqli_prepare($conn, $exportSql);
    if ($params) { mysqli_stmt_bind_param($exportStmt, $types, ...$params); }
    mysqli_stmt_execute($exportStmt);
    $exportResult = mysqli_stmt_get_result($exportStmt);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="brew-haven-logs-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    $auditColumns = bh_audit_columns_available();
    $header = ['Date/Time', 'User', 'ID Number', 'Log Type', 'Action', 'Target', 'Details'];
    if ($auditColumns) { array_push($header, 'Previous State', 'New State', 'Reason', 'Document'); }
    fputcsv($out, $header);
    while ($row = mysqli_fetch_assoc($exportResult)) {
        $line = [$row['created_at'], trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')), $row['actor_id_number'] ?? '', bh_log_type($row['event_type']), bh_log_action_label($row['event_type']), $row['target_id_number'] ?? '', $row['details'] ?? ''];
        if ($auditColumns) {
            // The reason is stored HTML-escaped; decode it so the CSV carries
            // the text the actor actually typed.
            array_push($line,
                $row['previous_state'] ?? '',
                $row['new_state'] ?? '',
                html_entity_decode((string)($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8'),
                $row['proof_path'] ?? ''
            );
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);
$isSuperAdmin = $me['role'] === 'super_admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logs - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css">
</head>
<body class="bh-shell">
  <div class="bh-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-user">
      <div class="bh-notif-wrap">
        <button type="button" class="bh-notif-bell" onclick="bhToggleNotif()" aria-label="Notifications"><i class="fa-solid fa-bell"></i><?php if ($notifCount > 0): ?><span class="bh-notif-count"><?php echo $notifCount; ?></span><?php endif; ?></button>
        <div class="bh-notif-dropdown" id="notifDropdown"><div class="bh-notif-header">Notifications</div><?php if (empty($notifItems)): ?><div class="bh-notif-empty">You're all caught up.</div><?php else: foreach ($notifItems as $n): ?><a href="<?php echo htmlspecialchars($n['link']); ?>" class="bh-notif-item"><div><?php echo $n['text']; ?></div><div class="bh-notif-time"><?php echo htmlspecialchars(date('M j, g:i A', strtotime($n['time']))); ?></div></a><?php endforeach; endif; ?></div>
      </div>
      <span class="bh-role-label"><?php echo $isSuperAdmin ? 'Super Admin' : 'Admin'; ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="<?php echo $isSuperAdmin ? 'super-admin-dashboard.php' : 'admin-dashboard.php'; ?>"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="<?php echo $isSuperAdmin ? 'super-admin-accounts.php' : 'admin-accounts.php'; ?>"><i class="fa-solid fa-users"></i>Accounts</a>
      <?php if ($isSuperAdmin || has_privilege($me, 'approve_accounts')): ?>
        <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <?php endif; ?>
      <?php if ($isSuperAdmin): ?>
        <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <?php elseif (has_privilege($me, 'manage_deletion_requests')): ?>
        <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <?php endif; ?>
      <a href="logs.php" class="active"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <?php if (!$isSuperAdmin): ?><a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a><a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a><a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a><?php endif; ?>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;"><h1 class="bh-page-title" style="margin-bottom:0;">Logs</h1><a id="exportLogs" href="<?php echo bh_log_qs(['export' => 'csv']); ?>" class="bh-btn bh-btn-secondary"><i class="fa-solid fa-file-csv"></i> Export CSV</a></div>
      <p id="logsSummary" style="color:var(--bh-text-muted);margin-top:0.4rem;margin-bottom:1.2rem;">Activity and login history &middot; <?php echo $totalRows; ?> entr<?php echo $totalRows === 1 ? 'y' : 'ies'; ?> match this filter</p>
      <div class="bh-panel">
        <form method="get" action="logs.php" class="bh-toolbar" style="flex-wrap:wrap;">
          <input type="text" name="search" placeholder="Search logs by username, ID, action..." value="<?php echo htmlspecialchars($search); ?>">
          <select name="log_type"><option value="all" <?php echo $logType === 'all' ? 'selected' : ''; ?>>All Logs</option><option value="activity" <?php echo $logType === 'activity' ? 'selected' : ''; ?>>Activity Logs</option><option value="login" <?php echo $logType === 'login' ? 'selected' : ''; ?>>Login Logs</option></select>
          <select name="event_type"><option value="">All Actions</option><?php foreach ($eventTypes as $event): ?><option value="<?php echo htmlspecialchars($event); ?>" <?php echo $eventFilter === $event ? 'selected' : ''; ?>><?php echo htmlspecialchars(bh_log_action_label($event)); ?></option><?php endforeach; ?></select>
          <select name="role_filter">
            <?php if ($isScopedAdmin): ?>
              <option value="">All Logs</option>
              <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customers</option>
              <option value="self" <?php echo $roleFilter === 'self' ? 'selected' : ''; ?>>My Logs</option>
            <?php else: ?>
              <option value="">All Roles</option>
              <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
              <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
              <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customer</option>
            <?php endif; ?>
          </select>
          <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" title="From date"><input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" title="To date">
          <button type="button" id="resetLogs" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</button>
        </form>
        <div class="bh-table-wrap"><table class="bh-table"><thead><tr><th>Date &amp; Time</th><th>User</th><th>ID Number</th><th>Log Type</th><th>Action</th><th>Details</th><th>Document</th><th>Reason</th></tr></thead><tbody id="logsTableBody"><?php echo bh_render_log_rows($logs); ?></tbody></table></div>
        <div id="logsPagination"><?php echo bh_render_log_pagination($totalRows, $page, $totalPages, $offset, $perPage); ?></div>
      </div>
    </div>
  </div>
  <script>
    function bhToggleNotif(){document.getElementById('notifDropdown').classList.toggle('open');}
    document.addEventListener('click',function(e){var w=document.querySelector('.bh-notif-wrap'),d=document.getElementById('notifDropdown');if(w&&d&&!w.contains(e.target)){d.classList.remove('open');}});

    (function () {
      var form = document.querySelector('form[action="logs.php"]');
      var tableBody = document.getElementById('logsTableBody');
      var pagination = document.getElementById('logsPagination');
      var summary = document.getElementById('logsSummary');
      var actionSelect = form.elements.event_type;
      var requestController = null;
      var searchTimer = null;

      function actionLabel(eventType) {
        var labels = { login_success: 'Login Successful', login_failed: 'Failed Login', logout: 'Logout' };
        return labels[eventType] || eventType.replace(/_/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
      }

      function updateActionOptions(eventTypes) {
        var selected = actionSelect.value;
        actionSelect.innerHTML = '';
        actionSelect.appendChild(new Option('All Actions', ''));
        eventTypes.forEach(function (eventType) {
          actionSelect.appendChild(new Option(actionLabel(eventType), eventType));
        });
        actionSelect.value = eventTypes.indexOf(selected) !== -1 ? selected : '';
      }

      function syncExportLink() {
        var params = new URLSearchParams(new FormData(form));
        params.set('export', 'csv');
        params.delete('page');
        params.delete('partial');
        document.getElementById('exportLogs').href = 'logs.php?' + params.toString();
      }

      function refreshLogs(page) {
        var params = new URLSearchParams(new FormData(form));
        params.set('partial', '1');
        params.set('page', String(page || 1));
        syncExportLink();

        if (requestController) requestController.abort();
        requestController = new AbortController();
        tableBody.style.opacity = '0.55';

        fetch('logs.php?' + params.toString(), {
          headers: { 'Accept': 'application/json' },
          signal: requestController.signal
        })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            tableBody.innerHTML = data.rows || '';
            pagination.innerHTML = data.pagination || '';
            form.elements.log_type.value = data.logType || form.elements.log_type.value;
            updateActionOptions(data.eventTypes || []);
            var total = Number(data.total || 0);
            summary.textContent = 'Activity and login history · ' + total + ' entr' + (total === 1 ? 'y' : 'ies') + ' match this filter';
          })
          .catch(function (error) {
            if (error.name !== 'AbortError') console.error('Unable to refresh logs:', error);
          })
          .finally(function () { tableBody.style.opacity = ''; });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        refreshLogs(1);
      });

      Array.prototype.forEach.call(form.querySelectorAll('select, input[type="date"]'), function (field) {
        field.addEventListener('change', function () {
          if (field.name === 'log_type') actionSelect.value = '';
          refreshLogs(1);
        });
      });

      form.elements.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { refreshLogs(1); }, 250);
      });

      document.getElementById('resetLogs').addEventListener('click', function () {
        form.reset();
        refreshLogs(1);
      });

      pagination.addEventListener('click', function (event) {
        var link = event.target.closest('a');
        if (!link) return;
        event.preventDefault();
        var url = new URL(link.href, window.location.href);
        refreshLogs(url.searchParams.get('page') || 1);
      });
    })();
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
