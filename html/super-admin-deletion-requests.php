<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'manage_deletion_requests');
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

$tab = $_GET['tab'] ?? 'all';
$validTabs = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($tab, $validTabs, true)) { $tab = 'all'; }

$sql = "SELECT dr.*, u.firstname, u.lastname, u.username, u.employee_id, u.customer_id, u.role AS target_role,
               u.approval_status AS target_approval_status, u.account_status AS target_account_status,
               req.firstname AS req_firstname, req.lastname AS req_lastname
        FROM delete_requests dr
        LEFT JOIN users u ON u.id_number = dr.target_id_number
        LEFT JOIN users req ON req.id_number = dr.requested_by_id_number";
if ($tab !== 'all') {
    $stmt = mysqli_prepare($conn, $sql . " WHERE dr.status = ? ORDER BY dr.created_at DESC");
    mysqli_stmt_bind_param($stmt, 's', $tab);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql . " ORDER BY dr.created_at DESC");
}
$requests = [];
while ($row = mysqli_fetch_assoc($result)) { $requests[] = $row; }

function bh_count_req($conn, $status = null) {
    if ($status) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM delete_requests WHERE status = ?");
        mysqli_stmt_bind_param($stmt, 's', $status);
        mysqli_stmt_execute($stmt);
        return (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] ?? 0);
    }
    $result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM delete_requests");
    return (int)(mysqli_fetch_assoc($result)['c'] ?? 0);
}
$counts = ['all' => bh_count_req($conn), 'pending' => bh_count_req($conn, 'pending'), 'approved' => bh_count_req($conn, 'approved'), 'rejected' => bh_count_req($conn, 'rejected')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deletion Requests - Brew Haven</title>
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
      <!-- An Admin reaches this page through the "Manage Deletion Requests"
           add-on privilege and stays in the Admin workspace. -->
      <span class="bh-role-label"><?php echo $me['role'] === 'super_admin' ? 'Super Admin' : 'Admin'; ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <?php if ($me['role'] === 'super_admin'): ?>
      <a href="super-admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="super-admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <a href="super-admin-deletion-requests.php" class="active"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
      <?php else: ?>
      <a href="admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <?php if (has_privilege($me, 'approve_accounts')): ?><a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a><?php endif; ?>
      <a href="super-admin-deletion-requests.php" class="active"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
      <?php endif; ?>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title">Deletion Requests</h1>
      <?php if (isset($_GET['msg'])): ?><div class="bh-alert bh-alert-success">Request <?php echo htmlspecialchars($_GET['msg']); ?>.</div><?php endif; ?>
      <?php if (isset($_GET['error'])): ?><div class="bh-alert bh-alert-error">Error: <?php echo htmlspecialchars(bh_account_action_message(bh_confirm_error_message($_GET['error']))); ?></div><?php endif; ?>

      <div class="bh-tabs">
        <a href="?tab=all" class="bh-tab <?php echo $tab === 'all' ? 'active' : ''; ?>">All (<?php echo $counts['all']; ?>)</a>
        <a href="?tab=pending" class="bh-tab <?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending (<?php echo $counts['pending']; ?>)</a>
        <a href="?tab=approved" class="bh-tab <?php echo $tab === 'approved' ? 'active' : ''; ?>">Approved (<?php echo $counts['approved']; ?>)</a>
        <a href="?tab=rejected" class="bh-tab <?php echo $tab === 'rejected' ? 'active' : ''; ?>">Rejected (<?php echo $counts['rejected']; ?>)</a>
      </div>

      <?php foreach ($requests as $r): $initials = $r['firstname'] ? bh_initials($r['firstname'], $r['lastname']) : '?'; $color = bh_avatar_color($r['target_id_number']); ?>
        <details class="bh-accordion-item" <?php echo ($r['status'] === 'pending') ? 'open' : ''; ?>>
          <summary>
            <span class="bh-avatar bh-avatar-sm" style="background:<?php echo $color; ?>;"><?php echo htmlspecialchars($initials); ?></span>
            <div>
              <div style="font-weight:700;color:var(--bh-espresso);">Request ID: DEL-<?php echo str_pad($r['id'], 5, '0', STR_PAD_LEFT); ?></div>
              <div style="font-size:0.78rem;color:var(--bh-text-muted);">Target: <?php echo $r['firstname'] ? htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) : 'Account no longer exists'; ?></div>
            </div>
            <span class="bh-badge bh-badge-<?php echo $r['status'] === 'approved' ? 'approved' : ($r['status'] === 'rejected' ? 'rejected' : 'pending'); ?>"><?php echo htmlspecialchars($r['status']); ?></span>
          </summary>
          <div class="bh-accordion-body">
            <div class="bh-accordion-meta">
              <div><div class="label">Target User</div><?php echo $r['firstname'] ? htmlspecialchars($r['firstname'] . ' ' . $r['lastname'] . ' (' . $r['username'] . ')') : '<em>Account no longer exists</em>'; ?></div>
              <?php $reqDisplayId = bh_display_id(['role' => $r['target_role'], 'id_number' => $r['target_id_number']]); ?>
              <div><div class="label"><?php echo htmlspecialchars($reqDisplayId['label']); ?></div><?php echo htmlspecialchars($reqDisplayId['value'] ?? '—'); ?></div>
              <div><div class="label">Role</div><?php echo htmlspecialchars($r['target_role'] ?? '—'); ?></div>
              <div><div class="label">Requested By</div><?php echo htmlspecialchars(($r['req_firstname'] ?? '?') . ' ' . ($r['req_lastname'] ?? '')); ?></div>
              <div><div class="label">Date Requested</div><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($r['created_at']))); ?></div>
              <div><div class="label">Status</div><?php echo htmlspecialchars($r['status']); ?></div>
            </div>
            <div class="label">Reason for Deletion</div>
            <p style="margin:0.3rem 0 1rem;"><?php echo nl2br($r['reason']); ?></p>
            <?php if (!empty($r['proof_path'])): ?>
              <!-- Served through proof_download.php, which re-checks the
                   viewer; the file itself is not reachable from the web. -->
              <div class="label">Supporting Proof</div>
              <p style="margin:0.3rem 0 1rem;"><a href="../php/proof_download.php?file=<?php echo urlencode($r['proof_path']); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> View attachment</a></p>
            <?php endif; ?>
            <?php if ($r['status'] === 'pending'): ?>
              <?php $targetLabel = $r['firstname'] ? trim($r['firstname'] . ' ' . $r['lastname']) . ' (' . $r['username'] . ')' : 'this account'; ?>
              <div class="bh-actions">
                <form method="post" action="../php/delete_request_review.php" style="display:inline;" data-bh-confirm-title="Reject Request" data-bh-confirm="Reject this deletion request? <?php echo htmlspecialchars($targetLabel); ?> will keep their account." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Reject Request">
                  <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="bh-btn bh-btn-secondary">Reject</button>
                </form>
                <form method="post" action="../php/delete_request_review.php" style="display:inline;" data-bh-confirm-title="Approve Deletion" data-bh-confirm="Permanently delete <?php echo htmlspecialchars($targetLabel); ?>? This cannot be undone." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Delete Permanently">
                  <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="bh-btn bh-btn-danger">Approve Deletion</button>
                </form>
              </div>
            <?php else: ?>
              <p style="font-size:0.8rem;color:var(--bh-text-muted);">Reviewed <?php echo $r['reviewed_at'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($r['reviewed_at']))) : ''; ?> by <?php echo htmlspecialchars($r['reviewed_by_id_number'] ?? '—'); ?></p>
            <?php endif; ?>
          </div>
        </details>
      <?php endforeach; ?>
      <?php if (empty($requests)): ?><div class="bh-panel">No deletion requests in this view.</div><?php endif; ?>
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
