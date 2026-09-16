<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
$canManage = has_privilege($me, 'manage_orders');
$sidebarActive = $me['role'] === 'admin' ? 'admin-dashboard.php' : 'super-admin-dashboard.php';

$statusFilter = $_GET['status'] ?? '';
$validStatuses = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

$sql = "SELECT o.*, u.firstname, u.lastname FROM orders o LEFT JOIN users u ON u.id_number = o.customer_id_number";
if (in_array($statusFilter, $validStatuses, true)) {
    $stmt = mysqli_prepare($conn, $sql . " WHERE o.status = ? ORDER BY o.created_at DESC");
    mysqli_stmt_bind_param($stmt, 's', $statusFilter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql . " ORDER BY o.created_at DESC");
}
$orders = [];
while ($row = mysqli_fetch_assoc($result)) { $orders[] = $row; }
$orderItems = bh_fetch_order_items(array_column($orders, 'id'));
$transitions = bh_order_status_transitions();
$orderMessages = [
    'updated'            => ['success', 'Order status updated.'],
    'invalid'            => ['error', 'That status update was not valid.'],
    'not_found'          => ['error', 'That order no longer exists.'],
    'invalid_transition' => ['error', 'That status change is not allowed. Orders move Pending → Preparing → Ready → Completed, and can be cancelled until they are completed.'],
    'changed_meanwhile'  => ['error', 'Someone else updated this order at the same time. Please check its current status.'],
];
$flashKey = $_GET['msg'] ?? ($_GET['error'] ?? '');
$flash = $orderMessages[$flashKey] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Orders - Brew Haven</title>
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
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="<?php echo $sidebarActive; ?>"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="<?php echo $me['role'] === 'admin' ? 'admin-accounts.php' : 'super-admin-accounts.php'; ?>"><i class="fa-solid fa-users"></i>Accounts</a>
      <?php if ($me['role'] === 'super_admin' || has_privilege($me, 'approve_accounts')): ?><a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a><?php endif; ?>
      <?php if ($me['role'] === 'admin' && has_privilege($me, 'manage_deletion_requests')): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
      <?php if ($me['role'] === 'super_admin'): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
      <?php if ($me['role'] === 'admin'): ?><a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a><?php endif; ?>
      <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php" class="active"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title">Order Queue</h1>
      <?php if (!$canManage): ?><div class="bh-alert bh-alert-error">You do not have the Manage Orders privilege — contact a Super Admin.</div><?php endif; ?>
      <?php if ($flash): ?><div class="bh-alert bh-alert-<?php echo $flash[0]; ?>"><?php echo htmlspecialchars($flash[1]); ?></div><?php endif; ?>

      <div class="bh-panel">
        <div class="bh-toolbar">
          <a href="admin-orders.php" class="bh-btn <?php echo $statusFilter === '' ? 'bh-btn-primary' : 'bh-btn-secondary'; ?> bh-btn-sm">All</a>
          <?php foreach ($validStatuses as $s): ?>
            <a href="admin-orders.php?status=<?php echo urlencode($s); ?>" class="bh-btn <?php echo $statusFilter === $s ? 'bh-btn-primary' : 'bh-btn-secondary'; ?> bh-btn-sm"><?php echo $s; ?></a>
          <?php endforeach; ?>
        </div>
        <div class="bh-table-wrap">
          <table class="bh-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Deliver To</th><th>Total</th><th>Placed</th><th>Status</th><?php if ($canManage): ?><th>Update</th><?php endif; ?></tr></thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td>#<?php echo (int)$o['id']; ?></td>
                  <td><?php echo htmlspecialchars(($o['firstname'] ?? '?') . ' ' . ($o['lastname'] ?? '')); ?></td>
                  <td style="min-width:180px;"><?php echo bh_order_items_html($orderItems[(int)$o['id']] ?? []); ?></td>
                  <td>
                    <?php if (($o['order_type'] ?? 'Dine In') === 'Takeout'): ?>
                      <span class="bh-badge bh-badge-first-login"><i class="fa-solid fa-bag-shopping"></i> Takeout</span>
                    <?php else: ?>
                      <span class="bh-badge bh-badge-approved"><i class="fa-solid fa-utensils"></i> Table <?php echo htmlspecialchars($o['table_number'] ?? '?'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                  <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                  <td><?php echo htmlspecialchars($o['status']); ?></td>
                  <?php if ($canManage): ?>
                  <td>
                    <?php $nextStatuses = $transitions[$o['status']] ?? []; ?>
                    <?php if ($nextStatuses): ?>
                    <form method="post" action="../php/order_update_status.php" style="display:flex;gap:0.4rem;">
                      <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                      <select name="status" class="bh-status-select">
                        <?php foreach ($nextStatuses as $s): ?>
                          <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="bh-btn bh-btn-primary bh-btn-sm">Update</button>
                    </form>
                    <?php else: ?>
                      <span style="font-size:0.78rem;color:var(--bh-text-muted);">Final</span>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($orders)): ?><tr><td colspan="8">No orders found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
