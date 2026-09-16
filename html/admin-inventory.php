<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
$canManage = has_privilege($me, 'manage_products');
$sidebarActive = $me['role'] === 'admin' ? 'admin-dashboard.php' : 'super-admin-dashboard.php';

$result = mysqli_query($conn, "SELECT * FROM inventory_items ORDER BY name ASC");
$items = [];
while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
$inventoryMessages = [
    'updated'      => ['success', 'Inventory item updated.'],
    'added'        => ['success', 'Inventory item added.'],
    'invalid'      => ['error', 'Quantity and low-stock threshold must be whole numbers of 0 or more.'],
    'invalid_name' => ['error', 'Ingredient name must be 2–100 characters (letters, numbers, spaces and . , & ( ) \' -).'],
    'invalid_unit' => ['error', 'Unit must be 1–20 letters (e.g. kg, liters, pcs).'],
    'duplicate'    => ['error', 'An ingredient with that name already exists.'],
    'not_found'    => ['error', 'That inventory item no longer exists.'],
];
$flashKey = $_GET['msg'] ?? ($_GET['error'] ?? '');
$flash = $inventoryMessages[$flashKey] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inventory - Brew Haven</title>
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
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php" class="active"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title">Ingredient Inventory</h1>
      <?php if (!$canManage): ?><div class="bh-alert bh-alert-error">You do not have the Manage Products privilege — contact a Super Admin.</div><?php endif; ?>
      <?php if ($flash): ?><div class="bh-alert bh-alert-<?php echo $flash[0]; ?>"><?php echo htmlspecialchars($flash[1]); ?></div><?php endif; ?>

      <?php if ($canManage): ?>
      <div class="bh-panel" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 0.75rem;font-family:var(--bh-font-heading);color:var(--bh-espresso);">Add Ingredient</h3>
        <form method="post" action="../php/inventory_add.php" id="inventoryAddForm" class="bh-form-grid bh-form-grid-4" style="align-items:end;">
          <div>
            <label for="inv_name">Ingredient <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="inv_name" name="name" required minlength="2" maxlength="100" placeholder="e.g. Oat Milk">
          </div>
          <div>
            <label for="inv_quantity">Quantity <span style="color:var(--bh-danger);">*</span></label>
            <input type="number" id="inv_quantity" name="quantity" required min="0" step="1" value="0">
          </div>
          <div>
            <label for="inv_unit">Unit <span style="color:var(--bh-danger);">*</span></label>
            <input type="text" id="inv_unit" name="unit" required maxlength="20" placeholder="e.g. liters">
          </div>
          <div>
            <label for="inv_threshold">Low-stock alert below <span style="color:var(--bh-danger);">*</span></label>
            <input type="number" id="inv_threshold" name="low_stock_threshold" required min="0" step="1" value="10">
          </div>
          <div style="grid-column:1 / -1;display:flex;justify-content:flex-end;">
            <button type="submit" class="bh-btn bh-btn-primary"><i class="fa-solid fa-plus"></i> Add Ingredient</button>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <div class="bh-panel">
        <div class="bh-table-wrap">
          <table class="bh-table">
            <thead><tr><th>Ingredient</th><th>Quantity</th><th>Unit</th><th>Low-stock alert below</th><th>Status</th><?php if ($canManage): ?><th>Update</th><?php endif; ?></tr></thead>
            <tbody>
              <?php foreach ($items as $it): $low = $it['quantity'] < $it['low_stock_threshold']; ?>
                <tr>
                  <td><?php echo htmlspecialchars($it['name']); ?></td>
                  <td><?php echo (int)$it['quantity']; ?></td>
                  <td><?php echo htmlspecialchars($it['unit']); ?></td>
                  <td><?php echo (int)$it['low_stock_threshold']; ?></td>
                  <td><span class="bh-badge <?php echo $low ? 'bh-badge-blocked' : 'bh-badge-active'; ?>"><?php echo $low ? 'Low Stock' : 'OK'; ?></span></td>
                  <?php if ($canManage): ?>
                  <td>
                    <form method="post" action="../php/inventory_update.php" style="display:flex;gap:0.4rem;align-items:center;flex-wrap:wrap;">
                      <input type="hidden" name="item_id" value="<?php echo (int)$it['id']; ?>">
                      <input type="number" name="quantity" value="<?php echo (int)$it['quantity']; ?>" min="0" step="1" required style="width:80px;" title="Quantity" aria-label="Quantity">
                      <input type="number" name="low_stock_threshold" value="<?php echo (int)$it['low_stock_threshold']; ?>" min="0" step="1" required style="width:80px;" title="Low-stock alert below" aria-label="Low-stock alert below">
                      <button type="submit" class="bh-btn bh-btn-primary bh-btn-sm">Save</button>
                    </form>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
