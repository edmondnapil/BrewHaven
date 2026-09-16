<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$productId = intval($_GET['id'] ?? $_POST['product_id'] ?? 0);
if ($productId <= 0) {
    header('Location: menu.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, name, description, category, price, image_path FROM products WHERE id = ? AND is_available = 1");
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    header('Location: menu.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $size = in_array($_POST['size'] ?? '', ['Small', 'Medium', 'Large'], true) ? $_POST['size'] : 'Medium';
    $temperature = in_array($_POST['temperature'] ?? '', ['Hot', 'Iced'], true) ? $_POST['temperature'] : 'Hot';
    $sweetness = in_array($_POST['sweetness'] ?? '', ['Regular', 'Less Sweet', 'No Sugar'], true) ? $_POST['sweetness'] : 'Regular';
    $addons = bh_filter_addons($_POST['addons'] ?? []);
    $quantity = max(1, min(20, intval($_POST['quantity'] ?? 1)));

    ensure_session_started();
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'image_path' => $product['image_path'],
        'size' => $size,
        'temperature' => $temperature,
        'sweetness' => $sweetness,
        'addons' => $addons,
        'quantity' => $quantity,
        'unit_price' => (float)$product['price'],
    ];
    header('Location: order-cart.php');
    exit;
}

$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($product['name']); ?> - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
  <div class="bh-storefront-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-storefront-nav">
      <a href="dashboard.php">Home</a>
      <a href="menu.php" class="active">Menu</a>
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
          <a href="activity-log.php"><i class="fa-solid fa-clock-rotate-left" style="width:16px;"></i> Activity Log</a>
          <a href="login-logs.php"><i class="fa-solid fa-right-to-bracket" style="width:16px;"></i> Login Logs</a>
          <a href="profile.php"><i class="fa-solid fa-user" style="width:16px;"></i> Profile</a>
        </div>
      </details>
      <a href="logout.php" class="bh-storefront-logout-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="bh-main" style="max-width:700px;margin:0 auto;">
    <div class="bh-panel">
      <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:100%;max-height:260px;object-fit:cover;border-radius:12px;margin-bottom:1rem;">
      <h2><?php echo htmlspecialchars($product['name']); ?></h2>
      <p style="color:var(--bh-text-muted);"><?php echo htmlspecialchars($product['description']); ?></p>
      <p class="bh-price" style="font-size:1.3rem;">₱<?php echo number_format($product['price'], 2); ?></p>

      <form method="post" action="product-details.php?id=<?php echo (int)$product['id']; ?>">
        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
        <div class="bh-form-grid">
          <div>
            <label>Size</label>
            <select name="size">
              <option value="Small">Small</option>
              <option value="Medium" selected>Medium</option>
              <option value="Large">Large</option>
            </select>
          </div>
          <div>
            <label>Temperature</label>
            <select name="temperature">
              <option value="Hot" selected>Hot</option>
              <option value="Iced">Iced</option>
            </select>
          </div>
          <div>
            <label>Sweetness</label>
            <select name="sweetness">
              <option value="Regular" selected>Regular</option>
              <option value="Less Sweet">Less Sweet</option>
              <option value="No Sugar">No Sugar</option>
            </select>
          </div>
          <div>
            <label>Quantity</label>
            <input type="number" name="quantity" value="1" min="1" max="20">
          </div>
        </div>

        <div style="margin-top:1rem;">
          <label style="display:block;font-size:0.82rem;font-weight:700;color:var(--bh-espresso);margin-bottom:0.4rem;">Add-ons</label>
          <?php foreach (bh_addon_options() as $i => $addon): ?>
          <div class="bh-checkbox-row"><input type="checkbox" name="addons[]" value="<?php echo htmlspecialchars($addon); ?>" id="a<?php echo $i + 1; ?>"><label for="a<?php echo $i + 1; ?>" style="margin:0;"><?php echo htmlspecialchars($addon); ?></label></div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:1.25rem;display:flex;gap:0.6rem;">
          <button type="submit" class="bh-btn bh-btn-primary">Add to Order</button>
          <a href="menu.php" class="bh-btn bh-btn-secondary">Back to Menu</a>
        </div>
      </form>
    </div>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
