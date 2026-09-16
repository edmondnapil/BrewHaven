<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['customer']);

$featured = [];
$result = mysqli_query($conn, "SELECT id, name, description, price, image_path FROM products WHERE is_available = 1 ORDER BY id ASC LIMIT 5");
if ($result) { while ($row = mysqli_fetch_assoc($result)) { $featured[] = $row; } }

$initials = bh_initials($me['firstname'], $me['lastname']);
$avatarColor = bh_avatar_color($me['id_number']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Brew Haven — Coffee Shop Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
</head>
<body class="bh-shell">
  <div class="bh-storefront-topbar">
    <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
    <div class="bh-storefront-nav">
      <a href="dashboard.php" class="active">Home</a>
      <a href="menu.php">Menu</a>
      <a href="order-cart.php">My Orders</a>
      <a href="order-history.php">Order History</a>
    </div>
    <div class="bh-storefront-icons">
      <form method="get" action="menu.php" class="bh-header-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Search menu...">
      </form>
      <details class="bh-avatar-menu">
        <summary>
          <span class="bh-avatar bh-avatar-sm" style="background:<?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($initials); ?></span>
        </summary>
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
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added_to_cart'): ?>
      <div class="bh-alert bh-alert-success">Added to your order. <a href="order-cart.php" style="color:inherit;text-decoration:underline;">View cart</a></div>
    <?php endif; ?>

    <div class="bh-hero-slider" id="heroSlider">
      <div class="bh-hero-track" id="heroTrack">
      <?php foreach ($featured as $i => $p): ?>
        <div class="bh-hero-slide" style="background-image:url('<?php echo htmlspecialchars($p['image_path']); ?>');">
          <div class="bh-hero-slide-content">
            <?php if ($i === 0): ?>
              <span class="bh-hero-kicker"><i class="fa-solid fa-mug-saucer"></i> Freshly Roasted Daily</span>
              <h1>Your <span class="bh-accent">daily cup,</span><br>your favorite haven.</h1>
              <p>Premium coffee, made with passion for your perfect moment.</p>
            <?php else: ?>
              <span class="bh-hero-kicker"><i class="fa-solid fa-star"></i> Top Pick</span>
              <h1><span class="bh-accent"><?php echo htmlspecialchars($p['name']); ?></span></h1>
              <p><?php echo htmlspecialchars($p['description']); ?></p>
            <?php endif; ?>
            <a href="menu.php" class="bh-btn bh-btn-primary" style="padding:0.7rem 1.6rem;font-size:1rem;">Order Now <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($featured)): ?>
        <div class="bh-hero-slide" style="background-image:url('https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1200&q=80');">
          <div class="bh-hero-slide-content">
            <span class="bh-hero-kicker"><i class="fa-solid fa-mug-saucer"></i> Freshly Roasted Daily</span>
            <h1>Your <span class="bh-accent">daily cup,</span><br>your favorite haven.</h1>
            <p>Premium coffee, made with passion for your perfect moment.</p>
            <a href="menu.php" class="bh-btn bh-btn-primary" style="padding:0.7rem 1.6rem;font-size:1rem;">Order Now <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <?php if (count($featured) > 1): ?>
        <button type="button" class="bh-hero-arrow prev" onclick="bhHeroMove(-1)" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="bh-hero-arrow next" onclick="bhHeroMove(1)" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="bh-hero-dots">
          <?php foreach ($featured as $i => $p): ?>
            <button type="button" class="bh-hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="bhHeroGoTo(<?php echo $i; ?>)" aria-label="Slide <?php echo $i + 1; ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="bh-section-heading">
      <span class="bh-section-kicker">Our Menu</span>
      <h2>Best Sellers</h2>
      <p>Handpicked favorites our customers keep coming back for.</p>
    </div>
    <div class="bh-product-grid">
      <?php foreach ($featured as $i => $p): ?>
        <div class="bh-product-card-v2">
          <div class="bh-pc-img-wrap">
            <?php if ($i === 0): ?><span class="bh-pc-badge">Best Seller</span><?php endif; ?>
            <button type="button" class="bh-pc-heart" data-product-id="<?php echo (int)$p['id']; ?>" aria-label="Favorite"><i class="fa-regular fa-heart"></i></button>
            <img src="<?php echo htmlspecialchars($p['image_path']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
          </div>
          <div class="bh-pc-body">
            <h3><?php echo htmlspecialchars($p['name']); ?></h3>
            <p><?php echo htmlspecialchars($p['description']); ?></p>
            <div class="bh-pc-footer">
              <div class="bh-pc-price-row">
                <span class="bh-price">₱<?php echo number_format($p['price'], 2); ?></span>
                <span class="bh-stars" style="font-size:0.75rem;">★ 4.8</span>
              </div>
              <button type="button" class="bh-pc-add" aria-label="Add to order" onclick="bhOpenAddToCart(this)"
                data-id="<?php echo (int)$p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>" data-price="<?php echo htmlspecialchars(number_format($p['price'], 2)); ?>" data-image="<?php echo htmlspecialchars($p['image_path'], ENT_QUOTES); ?>"><i class="fa-solid fa-plus"></i></button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($featured)): ?><p>No products available right now.</p><?php endif; ?>
    </div>

    <div class="bh-section-heading">
      <span class="bh-section-kicker">Why Choose Us</span>
      <h2>Why Brew Haven?</h2>
      <p>Three simple promises behind every cup we hand you.</p>
    </div>
    <div class="bh-why-grid">
      <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-mug-hot"></i></div><h3>Freshly Brewed</h3><p>Every cup made to order.</p></div>
      <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-seedling"></i></div><h3>Quality Ingredients</h3><p>Real coffee beans, real milk.</p></div>
      <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-heart"></i></div><h3>Made for You</h3><p>Size, temperature, and sweetness, your way.</p></div>
    </div>
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
        <input type="hidden" name="redirect_to" value="dashboard.php">
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
    // Hero slider — a real sliding filmstrip: translate the track horizontally.
    (function () {
      var track = document.getElementById('heroTrack');
      var slides = document.querySelectorAll('.bh-hero-slide');
      var dots = document.querySelectorAll('.bh-hero-dot');
      var current = 0;
      var timer = null;
      window.bhHeroGoTo = function (i) {
        if (!slides.length || !track) return;
        if (dots[current]) dots[current].classList.remove('active');
        current = (i + slides.length) % slides.length;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
        if (dots[current]) dots[current].classList.add('active');
      };
      window.bhHeroMove = function (delta) { bhHeroGoTo(current + delta); resetTimer(); };
      function resetTimer() {
        if (timer) clearInterval(timer);
        if (slides.length > 1) { timer = setInterval(function () { bhHeroGoTo(current + 1); }, 5000); }
      }
      resetTimer();
    })();

    // Favorites (persisted client-side in localStorage, shared across pages)
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
      });
    });

    // Add to cart modal
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
