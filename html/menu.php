<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$search = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$validCategories = ['Hot Coffee', 'Iced Coffee', 'Non-Coffee', 'Add-ons'];

$where = ['is_available = 1'];
$params = [];
$types = '';
if ($category !== '' && in_array($category, $validCategories, true)) {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}
if ($search !== '') {
    $where[] = 'name LIKE ?';
    $params[] = '%' . $search . '%';
    $types .= 's';
}

$sql = "SELECT id, name, description, category, price, image_path FROM products WHERE " . implode(' AND ', $where) . " ORDER BY category ASC, name ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$products = [];
while ($row = mysqli_fetch_assoc($result)) { $products[] = $row; }
mysqli_stmt_close($stmt);

$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
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
        <input type="text" name="q" placeholder="Search menu..." value="<?php echo htmlspecialchars($search); ?>">
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

  <div class="bh-main" style="max-width:1150px;margin:0 auto;">
    <div class="bh-storefront-header">
      <div class="bh-storefront-header-icon"><i class="fa-solid fa-mug-saucer"></i></div>
      <div>
        <h1>Our Menu</h1>
        <p>Browse every brew and customize it just the way you like.</p>
      </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added_to_cart'): ?>
      <div class="bh-alert bh-alert-success">Added to your order. <a href="order-cart.php" style="color:inherit;text-decoration:underline;">View cart</a></div>
    <?php endif; ?>

    <div class="bh-toolbar">
      <a href="menu.php" class="bh-tab <?php echo $category === '' ? 'active' : ''; ?>">All</a>
      <?php foreach ($validCategories as $c): ?>
        <a href="menu.php?category=<?php echo urlencode($c); ?>" class="bh-tab <?php echo $category === $c ? 'active' : ''; ?>"><?php echo htmlspecialchars($c); ?></a>
      <?php endforeach; ?>
      <button type="button" class="bh-tab" id="favTab" onclick="bhToggleFavView()"><i class="fa-solid fa-heart" style="margin-right:0.3rem;"></i>Favourites</button>
    </div>

    <div class="bh-product-grid" id="productGrid">
      <?php foreach ($products as $p): ?>
        <div class="bh-product-card-v2" data-product-id="<?php echo (int)$p['id']; ?>">
          <div class="bh-pc-img-wrap">
            <button type="button" class="bh-pc-heart" data-product-id="<?php echo (int)$p['id']; ?>" aria-label="Favorite"><i class="fa-regular fa-heart"></i></button>
            <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
          </div>
          <div class="bh-pc-body">
            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
            <p><?php echo htmlspecialchars($p['description']); ?></p>
            <div class="bh-pc-footer">
              <div class="bh-pc-price-row"><span class="bh-price">₱<?php echo number_format($p['price'], 2); ?></span></div>
              <button type="button" class="bh-pc-add" aria-label="Add to order" onclick="bhOpenAddToCart(this)"
                data-id="<?php echo (int)$p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>" data-price="<?php echo htmlspecialchars(number_format($p['price'], 2)); ?>" data-image="<?php echo htmlspecialchars($p['image_path'], ENT_QUOTES); ?>"><i class="fa-solid fa-plus"></i></button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($products)): ?><p id="noResults">No items match your search.</p><?php endif; ?>
    </div>
    <p id="noFavResults" style="display:none;">You haven't favourited any items yet.</p>
  </div>

  <footer class="main-footer" style="position:static;">
    <div class="footer-content"><span>&copy; 2025 Brew Haven</span></div>
  </footer>

  <div class="bh-modal-backdrop" id="addToCartModal">
    <div class="bh-modal">
      <button type="button" class="bh-modal-close" onclick="bhCloseAddToCart()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <div class="bh-atc-preview">
        <img id="atcImage" src="" alt="">
        <div class="bh-atc-preview-text">
          <h3 id="atcName">Customize Your Order</h3>
          <p id="atcPrice"></p>
        </div>
      </div>
      <form method="post" action="../php/add_to_cart.php">
        <input type="hidden" name="product_id" id="atcProductId">
        <input type="hidden" name="redirect_to" value="menu.php">
        <div class="bh-atc-grid">
          <div>
            <label>Size</label>
            <select name="size"><option value="Small">Small</option><option value="Medium" selected>Medium</option><option value="Large">Large</option></select>
          </div>
          <div>
            <label>Temperature</label>
            <select name="temperature"><option value="Hot" selected>Hot</option><option value="Iced">Iced</option></select>
          </div>
          <div>
            <label>Sweetness</label>
            <select name="sweetness"><option value="Regular" selected>Regular</option><option value="Less Sweet">Less Sweet</option><option value="No Sugar">No Sugar</option></select>
          </div>
          <div>
            <label>Quantity</label>
            <input type="number" name="quantity" value="1" min="1" max="20">
          </div>
        </div>
        <label class="bh-atc-addons-label">Add-ons</label>
        <div class="bh-atc-addons">
          <?php foreach (bh_addon_options() as $addon): ?>
          <label class="bh-atc-addon"><input type="checkbox" name="addons[]" value="<?php echo htmlspecialchars($addon); ?>"><span><?php echo htmlspecialchars($addon); ?></span></label>
          <?php endforeach; ?>
        </div>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseAddToCart()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-primary">Add to Order</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function bhGetFavorites() {
      try { return JSON.parse(localStorage.getItem('bh_favorites') || '[]'); } catch (e) { return []; }
    }
    function bhSaveFavorites(list) {
      try { localStorage.setItem('bh_favorites', JSON.stringify(list)); } catch (e) {}
    }
    document.querySelectorAll('.bh-pc-heart').forEach(function (btn) {
      var id = btn.getAttribute('data-product-id');
      var favs = bhGetFavorites();
      var icon = btn.querySelector('i');
      if (id && favs.indexOf(id) !== -1) { icon.classList.remove('fa-regular'); icon.classList.add('fa-solid'); }
      btn.addEventListener('click', function () {
        if (!id) return;
        var list = bhGetFavorites();
        var idx = list.indexOf(id);
        if (idx === -1) { list.push(id); icon.classList.remove('fa-regular'); icon.classList.add('fa-solid'); }
        else { list.splice(idx, 1); icon.classList.remove('fa-solid'); icon.classList.add('fa-regular'); }
        bhSaveFavorites(list);
        if (favMode) { applyFavFilter(); }
      });
    });

    var favMode = false;
    function applyFavFilter() {
      var favs = bhGetFavorites();
      var cards = document.querySelectorAll('#productGrid .bh-product-card-v2');
      var visibleCount = 0;
      cards.forEach(function (card) {
        var id = card.getAttribute('data-product-id');
        var show = favs.indexOf(id) !== -1;
        card.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });
      document.getElementById('noFavResults').style.display = visibleCount === 0 ? '' : 'none';
    }
    function bhToggleFavView() {
      favMode = !favMode;
      var tab = document.getElementById('favTab');
      var cards = document.querySelectorAll('#productGrid .bh-product-card-v2');
      if (favMode) {
        tab.classList.add('active');
        applyFavFilter();
      } else {
        tab.classList.remove('active');
        cards.forEach(function (card) { card.style.display = ''; });
        document.getElementById('noFavResults').style.display = 'none';
      }
    }

    function bhOpenAddToCart(btn) {
      document.getElementById('atcProductId').value = btn.getAttribute('data-id');
      document.getElementById('atcName').textContent = btn.getAttribute('data-name');
      document.getElementById('atcPrice').textContent = '₱' + btn.getAttribute('data-price');
      var img = document.getElementById('atcImage');
      img.src = btn.getAttribute('data-image') || '';
      img.alt = btn.getAttribute('data-name') || '';
      document.getElementById('addToCartModal').classList.add('open');
    }
    function bhCloseAddToCart() {
      document.getElementById('addToCartModal').classList.remove('open');
    }
    document.getElementById('addToCartModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
