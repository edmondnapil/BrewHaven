<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$stmt = mysqli_prepare($conn, "SELECT id, status, order_type, table_number, total_amount, created_at FROM orders WHERE customer_id_number = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
$orderRows = [];
while ($row = mysqli_fetch_assoc($orders)) { $orderRows[] = $row; }
mysqli_stmt_close($stmt);
$orderItems = bh_fetch_order_items(array_column($orderRows, 'id'));

function bh_order_status_class($status) {
    switch ($status) {
        case 'Completed': return 'bh-badge-active';
        case 'Cancelled': return 'bh-badge-blocked';
        case 'Ready': return 'bh-badge-approved';
        default: return 'bh-badge-pending';
    }
}
$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order History - Brew Haven</title>
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
      <a href="order-history.php" class="active">Order History</a>
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

  <div class="bh-main" style="max-width:800px;margin:0 auto;">
    <div class="bh-storefront-header">
      <div class="bh-storefront-header-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
      <div>
        <h1>Order History</h1>
        <p>Every order you've placed with Brew Haven, all in one place.</p>
      </div>
    </div>
    <?php if (($_GET['msg'] ?? '') === 'cancelled'): ?>
      <div class="bh-alert bh-alert-success">Your order has been cancelled.</div>
    <?php elseif (($_GET['error'] ?? '') === 'cannot_cancel'): ?>
      <div class="bh-alert bh-alert-error">That order can no longer be cancelled — the kitchen has already started on it.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="bh-alert bh-alert-error">That order could not be found.</div>
    <?php endif; ?>
    <div class="bh-panel">
      <div class="bh-table-wrap">
        <table class="bh-table">
          <thead><tr><th>Order #</th><th>Items</th><th>Deliver To</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orderRows as $o): ?>
              <tr>
                <td>#<?php echo (int)$o['id']; ?></td>
                <td style="min-width:180px;"><?php echo bh_order_items_html($orderItems[(int)$o['id']] ?? []); ?></td>
                <td>
                  <?php if (($o['order_type'] ?? 'Dine In') === 'Takeout'): ?>
                    <span class="bh-badge bh-badge-first-login"><i class="fa-solid fa-bag-shopping"></i> Takeout</span>
                  <?php else: ?>
                    <span class="bh-badge bh-badge-approved"><i class="fa-solid fa-utensils"></i> Table <?php echo htmlspecialchars($o['table_number'] ?? '?'); ?></span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                <td><span class="bh-badge <?php echo bh_order_status_class($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
                <td>
                  <?php if ($o['status'] === 'Pending'): ?>
                    <form method="post" action="../php/order_cancel.php" style="margin:0;" data-bh-confirm-title="Cancel Order" data-bh-confirm="Cancel order #<?php echo (int)$o['id']; ?>? This cannot be undone." data-bh-confirm-danger="1" data-bh-confirm-button="Yes, Cancel Order">
                      <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                      <button type="submit" class="bh-btn bh-btn-danger bh-btn-sm">Cancel</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($orderRows)): ?>
              <tr><td colspan="7">You haven't placed any orders yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
