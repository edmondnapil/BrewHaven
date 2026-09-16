<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
$canManage = has_privilege($me, 'manage_products');
$sidebarActive = $me['role'] === 'admin' ? 'admin-dashboard.php' : 'super-admin-dashboard.php';

$editId = intval($_GET['edit'] ?? 0);
$editProduct = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    $editProduct = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$result = mysqli_query($conn, "SELECT * FROM products ORDER BY category ASC, name ASC");
$products = [];
while ($row = mysqli_fetch_assoc($result)) { $products[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Products - Brew Haven</title>
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
      <a href="admin-products.php" class="active"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <h1 class="bh-page-title" style="margin-bottom:0;">Product Management</h1>
        <?php if ($canManage): ?>
          <button type="button" id="productFormToggle" class="bh-btn bh-btn-primary" onclick="bhOpenAddProduct()"><i class="fa-solid fa-plus"></i> Add Product</button>
        <?php endif; ?>
      </div>
      <?php if (isset($_GET['msg'])): ?><div class="bh-alert bh-alert-success" style="margin-top:1.25rem;">Saved.</div><?php endif; ?>
      <?php if (!$canManage): ?><div class="bh-alert bh-alert-error" style="margin-top:1.25rem;">You do not have the Manage Products privilege — contact a Super Admin.</div><?php endif; ?>
      <div style="margin-top:1.25rem;"></div>

      <div class="bh-panel">
        <div class="bh-product-mgmt-grid">
          <?php foreach ($products as $p): ?>
            <div class="bh-product-mgmt-card">
              <div class="bh-product-mgmt-img-wrap">
                <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <span class="bh-badge <?php echo $p['is_available'] ? 'bh-badge-active' : 'bh-badge-blocked'; ?>"><?php echo $p['is_available'] ? 'Available' : 'Unavailable'; ?></span>
              </div>
              <div class="bh-product-mgmt-body">
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <div class="bh-product-mgmt-category"><?php echo htmlspecialchars($p['category']); ?></div>
                <div class="bh-product-mgmt-price">₱<?php echo number_format($p['price'], 2); ?></div>
                <?php if ($canManage): ?>
                  <div class="bh-product-mgmt-actions">
                    <a href="admin-products.php?edit=<?php echo (int)$p['id']; ?>" class="bh-btn bh-btn-secondary bh-btn-sm">Edit</a>
                    <form method="post" action="../php/product_toggle.php">
                      <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
                      <button type="submit" class="bh-btn bh-btn-sm <?php echo $p['is_available'] ? 'bh-btn-danger' : 'bh-btn-success'; ?>"><?php echo $p['is_available'] ? 'Disable' : 'Enable'; ?></button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($canManage): ?>
      <div class="bh-modal-backdrop<?php echo $editProduct ? ' open' : ''; ?>" id="productFormModal">
        <div class="bh-modal bh-modal-lg">
          <button type="button" class="bh-modal-close" onclick="bhToggleProductForm()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
          <h3 id="productFormTitle"><?php echo $editProduct ? 'Edit Product' : 'Add Product'; ?></h3>
          <form method="post" action="../php/product_save.php" id="productForm">
            <input type="hidden" name="product_id" id="pf_product_id" value="<?php echo $editProduct ? (int)$editProduct['id'] : 0; ?>">
            <div class="bh-form-grid">
              <div><label>Name</label><input type="text" name="name" id="pf_name" value="<?php echo htmlspecialchars($editProduct['name'] ?? ''); ?>" required></div>
              <div><label>Category</label>
                <select name="category" id="pf_category" required>
                  <?php foreach (['Hot Coffee', 'Iced Coffee', 'Non-Coffee', 'Add-ons'] as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo (isset($editProduct['category']) && $editProduct['category'] === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div><label>Price (₱)</label><input type="number" step="0.01" name="price" id="pf_price" value="<?php echo htmlspecialchars($editProduct['price'] ?? ''); ?>" required></div>
              <div><label>Image Path</label><input type="text" name="image_path" id="pf_image_path" value="<?php echo htmlspecialchars($editProduct['image_path'] ?? '../images/product1.jpg'); ?>"></div>
            </div>
            <div style="margin-top:0.75rem;"><label>Description</label><textarea name="description" id="pf_description" rows="2" style="width:100%;padding:0.5rem;border-radius:8px;border:1px solid rgba(111,78,55,0.28);"><?php echo htmlspecialchars($editProduct['description'] ?? ''); ?></textarea></div>
            <div class="bh-modal-actions">
              <button type="button" class="bh-btn bh-btn-secondary" onclick="bhToggleProductForm()">Cancel</button>
              <button type="submit" class="bh-btn bh-btn-primary" id="pf_submit"><?php echo $editProduct ? 'Update Product' : 'Add Product'; ?></button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <script>
    function bhToggleProductForm() {
      document.getElementById('productFormModal').classList.toggle('open');
    }
    function bhOpenAddProduct() {
      document.getElementById('productFormTitle').textContent = 'Add Product';
      document.getElementById('pf_submit').textContent = 'Add Product';
      document.getElementById('pf_product_id').value = '0';
      document.getElementById('pf_name').value = '';
      document.getElementById('pf_category').selectedIndex = 0;
      document.getElementById('pf_price').value = '';
      document.getElementById('pf_image_path').value = '../images/product1.jpg';
      document.getElementById('pf_description').value = '';
      document.getElementById('productFormModal').classList.add('open');
    }
    <?php if ($canManage): ?>
    document.getElementById('productFormModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });
    <?php endif; ?>
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
