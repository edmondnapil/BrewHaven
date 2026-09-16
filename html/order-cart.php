<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

ensure_session_started();
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $idx = intval($_POST['index'] ?? -1);
    if (isset($_SESSION['cart'][$idx])) {
        array_splice($_SESSION['cart'], $idx, 1);
    }
    header('Location: order-cart.php');
    exit;
}

$cart = $_SESSION['cart'];
$total = 0.0;
foreach ($cart as $item) {
    $total += $item['unit_price'] * $item['quantity'];
}
$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Order - Brew Haven</title>
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
      <a href="order-cart.php" class="active">My Orders</a>
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

  <div class="bh-main" style="max-width:800px;margin:0 auto;">
    <div class="bh-storefront-header">
      <div class="bh-storefront-header-icon"><i class="fa-solid fa-basket-shopping"></i></div>
      <div>
        <h1>My Order</h1>
        <p>Review your items before sending them to the kitchen.</p>
      </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'placed'): ?>
      <div class="bh-alert bh-alert-success">Your order has been placed! Track it on the Order History page.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'table_required'): ?>
      <div class="bh-alert bh-alert-error">Please enter your table number for Dine In orders.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'error'): ?>
      <div class="bh-alert bh-alert-error">Something went wrong placing your order. Please try again.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'items_unavailable'): ?>
      <div class="bh-alert bh-alert-error">Some items are no longer available and were removed from your order. Please review it and place the order again.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'prices_updated'): ?>
      <div class="bh-alert bh-alert-error">Some prices have changed since you added them. Your order has been updated — please review the total and place the order again.</div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
      <div class="bh-panel">
        <p>Your order is empty.</p>
        <a href="menu.php" class="bh-btn bh-btn-primary">Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="bh-panel">
        <div class="bh-table-wrap">
          <table class="bh-table">
            <thead>
              <tr><th>Product</th><th>Customization</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($cart as $i => $item):
                $subtotal = $item['unit_price'] * $item['quantity'];
                $addons = !empty($item['addons']) ? implode(', ', $item['addons']) : 'None';
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['name']); ?></td>
                  <td><?php echo htmlspecialchars($item['size'] . ' / ' . $item['temperature'] . ' / ' . $item['sweetness']); ?><br><small>Add-ons: <?php echo htmlspecialchars($addons); ?></small></td>
                  <td><?php echo (int)$item['quantity']; ?></td>
                  <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                  <td>₱<?php echo number_format($subtotal, 2); ?></td>
                  <td>
                    <form method="post" action="order-cart.php" style="margin:0;">
                      <input type="hidden" name="action" value="remove">
                      <input type="hidden" name="index" value="<?php echo (int)$i; ?>">
                      <button type="submit" class="bh-btn bh-btn-danger bh-btn-sm">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <h3 style="text-align:right;margin-top:1rem;color:var(--bh-espresso);">Total: ₱<?php echo number_format($total, 2); ?></h3>

        <form method="post" action="../php/place_order.php" id="placeOrderForm" novalidate>
          <label class="bh-atc-addons-label" style="margin-top:1.4rem;">Where should we bring your order?</label>
          <div class="bh-dine-options">
            <label class="bh-dine-option">
              <input type="radio" name="order_type" value="Dine In" checked onchange="bhToggleTableField()">
              <i class="fa-solid fa-utensils"></i><span>Dine In</span>
            </label>
            <label class="bh-dine-option">
              <input type="radio" name="order_type" value="Takeout" onchange="bhToggleTableField()">
              <i class="fa-solid fa-bag-shopping"></i><span>Takeout</span>
            </label>
          </div>
          <div class="bh-table-number-field" id="tableNumberField">
            <label for="tableNumber">Table Number</label>
            <input type="text" name="table_number" id="tableNumber" placeholder="e.g. 12" maxlength="10">
            <div class="bh-field-error-msg" id="tableNumberError" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> Please enter your table number.</div>
          </div>
          <div style="display:flex;gap:0.6rem;justify-content:flex-end;">
            <a href="menu.php" class="bh-btn bh-btn-secondary">Continue Shopping</a>
            <button type="submit" class="bh-btn bh-btn-primary">Place Order</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>

  <script>
    function bhToggleTableField() {
      var isDineIn = document.querySelector('input[name="order_type"]:checked').value === 'Dine In';
      var field = document.getElementById('tableNumberField');
      var input = document.getElementById('tableNumber');
      if (field) { field.style.display = isDineIn ? '' : 'none'; }
      if (input) { if (!isDineIn) { input.value = ''; } bhClearTableError(); }
    }
    function bhClearTableError() {
      var input = document.getElementById('tableNumber');
      var err = document.getElementById('tableNumberError');
      if (input) { input.classList.remove('bh-field-error'); }
      if (err) { err.style.display = 'none'; }
    }
    var placeOrderForm = document.getElementById('placeOrderForm');
    if (placeOrderForm) {
      placeOrderForm.addEventListener('submit', function (e) {
        var isDineIn = document.querySelector('input[name="order_type"]:checked').value === 'Dine In';
        var input = document.getElementById('tableNumber');
        var err = document.getElementById('tableNumberError');
        if (isDineIn && input && input.value.trim() === '') {
          e.preventDefault();
          input.classList.add('bh-field-error');
          if (err) { err.style.display = 'flex'; }
          var field = document.getElementById('tableNumberField');
          field.classList.remove('bh-shake');
          void field.offsetWidth;
          field.classList.add('bh-shake');
          input.focus();
        }
      });
      var tableInput = document.getElementById('tableNumber');
      if (tableInput) { tableInput.addEventListener('input', bhClearTableError); }
    }
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
