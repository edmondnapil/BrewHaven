<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['admin']);

function bh_count2($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return (int)($row['c'] ?? 0);
}

$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

// KPI cards — the four figures an Admin checks first thing.
$pendingCustomers = bh_count2($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer' AND approval_status = 'pending'");
$totalCustomers    = bh_count2($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer'");
$pendingOrders     = bh_count2($conn, "SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'");
$myDeleteRequests  = 0;
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM delete_requests WHERE requested_by_id_number = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt, 's', $me['id_number']);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$myDeleteRequests = (int)($row['c'] ?? 0);
mysqli_stmt_close($stmt);

// Orders Overview — orders placed + revenue per day, last 7 days (real order data
// is day-granular so far, unlike registrations which suit a monthly window).
$dayLabels = [];
$dayKeys = [];
for ($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-$i days");
    $dayKeys[] = date('Y-m-d', $ts);
    $dayLabels[] = date('D', $ts);
}
$orderCounts = array_fill_keys($dayKeys, 0);
$orderRevenue = array_fill_keys($dayKeys, 0.0);
$orderTrendResult = mysqli_query($conn, "SELECT DATE(created_at) AS d, COUNT(*) AS c, SUM(total_amount) AS rev FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d");
while ($row = mysqli_fetch_assoc($orderTrendResult)) {
    if (isset($orderCounts[$row['d']])) {
        $orderCounts[$row['d']] = (int)$row['c'];
        $orderRevenue[$row['d']] = (float)$row['rev'];
    }
}
$orderCountValues = array_values($orderCounts);
$orderRevenueValues = array_values($orderRevenue);
$hasOrderTrendData = array_sum($orderCountValues) > 0;

// Order Status Distribution — real counts across the full status enum.
$orderStatusKeys = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];
$orderStatusColors = ['#C98A2C', '#8A6A4F', '#7C8B6E', '#5F7A4F', '#B3452A'];
$orderStatusCounts = array_fill_keys($orderStatusKeys, 0);
$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM orders GROUP BY status");
while ($row = mysqli_fetch_assoc($statusResult)) {
    if (isset($orderStatusCounts[$row['status']])) { $orderStatusCounts[$row['status']] = (int)$row['c']; }
}
$totalOrdersAllTime = array_sum($orderStatusCounts);
$orderStatusGradientParts = [];
$cursor = 0;
foreach ($orderStatusKeys as $i => $key) {
    $slice = $totalOrdersAllTime > 0 ? ($orderStatusCounts[$key] / $totalOrdersAllTime) * 360 : 0;
    $orderStatusGradientParts[] = $orderStatusColors[$i] . ' ' . round($cursor, 1) . 'deg ' . round($cursor + $slice, 1) . 'deg';
    $cursor += $slice;
}
$orderStatusGradient = implode(', ', $orderStatusGradientParts);

// Inventory Overview — real stock levels vs. each item's own low-stock threshold
// (the system tracks stock at the ingredient level, not per finished product).
$inventoryItems = [];
$inventoryResult = mysqli_query($conn, "SELECT name, quantity, unit, low_stock_threshold FROM inventory_items ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($inventoryResult)) { $inventoryItems[] = $row; }
$hasInventoryData = !empty($inventoryItems);

// Low Stock Items — anything at or below its own threshold.
$lowStockItems = array_values(array_filter($inventoryItems, function ($item) {
    return $item['quantity'] <= $item['low_stock_threshold'];
}));

// Recent Orders — latest orders with customer name and delivery info.
$recentOrders = [];
$recentOrdersResult = mysqli_query($conn, "SELECT o.id, o.order_type, o.table_number, o.status, o.total_amount, o.created_at, u.firstname, u.lastname
    FROM orders o LEFT JOIN users u ON u.id_number = o.customer_id_number
    ORDER BY o.created_at DESC LIMIT 6");
while ($row = mysqli_fetch_assoc($recentOrdersResult)) { $recentOrders[] = $row; }

function bh_order_status_badge_class($status) {
    switch ($status) {
        case 'Completed': return 'bh-badge-active';
        case 'Cancelled': return 'bh-badge-blocked';
        case 'Ready': return 'bh-badge-approved';
        default: return 'bh-badge-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Brew Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/brew-haven.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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
      <span class="bh-role-label">Admin</span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="admin-dashboard.php" class="active"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <?php if (has_privilege($me, 'approve_accounts')): ?><a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a><?php endif; ?>
      <?php if (has_privilege($me, 'manage_deletion_requests')): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">

      <div class="bh-dash-banner">
        <div class="bh-dash-banner-text">
          <h1>Welcome back, <?php echo htmlspecialchars($me['firstname']); ?>!</h1>
          <p>Here's how the shop is running today — orders, stock, and customers.</p>
        </div>
      </div>

      <div class="bh-cards">
        <div class="bh-card" style="--bh-card-accent:#C98A2C;">
          <div class="bh-card-icon"><i class="fa-solid fa-clock"></i></div>
          <div><div class="bh-card-num"><?php echo number_format($pendingCustomers); ?></div><div class="bh-card-label">Pending Registrations</div></div>
        </div>
        <div class="bh-card" style="--bh-card-accent:#6F4E37;">
          <div class="bh-card-icon"><i class="fa-solid fa-user-group"></i></div>
          <div><div class="bh-card-num"><?php echo number_format($totalCustomers); ?></div><div class="bh-card-label">Total Customers</div></div>
        </div>
        <div class="bh-card" style="--bh-card-accent:#3B2419;">
          <div class="bh-card-icon"><i class="fa-solid fa-receipt"></i></div>
          <div><div class="bh-card-num"><?php echo number_format($pendingOrders); ?></div><div class="bh-card-label">Pending Orders</div></div>
        </div>
        <div class="bh-card" style="--bh-card-accent:#5F7A4F;">
          <div class="bh-card-icon"><i class="fa-solid fa-trash-can"></i></div>
          <div><div class="bh-card-num"><?php echo number_format($myDeleteRequests); ?></div><div class="bh-card-label">My Pending Delete Requests</div></div>
        </div>
      </div>

      <div class="bh-panel">
        <h3>Orders Overview</h3>
        <div class="bh-chart-wrap">
          <?php if ($hasOrderTrendData): ?>
            <canvas id="ordersChart"></canvas>
          <?php else: ?>
            <div class="bh-chart-empty"><i class="fa-solid fa-chart-line"></i>No orders placed in the last 7 days.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bh-dash-grid-split">
        <div class="bh-panel">
          <h3>Order Status Distribution</h3>
          <?php if ($totalOrdersAllTime > 0): ?>
            <div class="bh-donut-wrap" style="justify-content:center;">
              <div style="position:relative;">
                <div class="bh-donut" style="--bh-donut: <?php echo $orderStatusGradient; ?>;"></div>
                <div class="bh-donut-center">
                  <div class="bh-donut-num"><?php echo $totalOrdersAllTime; ?></div>
                  <div class="bh-donut-label">Orders</div>
                </div>
              </div>
            </div>
            <div class="bh-donut-legend" style="margin-top:1rem;">
              <?php foreach ($orderStatusKeys as $i => $key): ?>
                <div class="bh-legend-row"><span class="bh-dot" style="background:<?php echo $orderStatusColors[$i]; ?>;"></span><?php echo $key; ?><span class="bh-legend-count"><?php echo $orderStatusCounts[$key]; ?></span></div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="bh-chart-empty"><i class="fa-solid fa-chart-pie"></i>No orders placed yet.</div>
          <?php endif; ?>
        </div>

        <div class="bh-panel">
          <h3>Inventory Overview</h3>
          <div class="bh-chart-wrap bh-chart-tall">
            <?php if ($hasInventoryData): ?>
              <canvas id="inventoryChart"></canvas>
            <?php else: ?>
              <div class="bh-chart-empty"><i class="fa-solid fa-boxes-stacked"></i>No inventory items recorded yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bh-dash-grid-2">
        <div class="bh-panel">
          <h3>Recent Orders</h3>
          <?php if (empty($recentOrders)): ?>
            <div class="bh-chart-empty"><i class="fa-solid fa-receipt"></i>No orders placed yet.</div>
          <?php else: ?>
            <div class="bh-table-wrap">
              <table class="bh-table">
                <thead><tr><th>Customer</th><th>Deliver To</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                  <?php foreach ($recentOrders as $o): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(($o['firstname'] ?? '?') . ' ' . ($o['lastname'] ?? '')); ?></td>
                      <td>
                        <?php if (($o['order_type'] ?? 'Dine In') === 'Takeout'): ?>
                          <span class="bh-badge bh-badge-first-login"><i class="fa-solid fa-bag-shopping"></i> Takeout</span>
                        <?php else: ?>
                          <span class="bh-badge bh-badge-approved"><i class="fa-solid fa-utensils"></i> Table <?php echo htmlspecialchars($o['table_number'] ?? '?'); ?></span>
                        <?php endif; ?>
                      </td>
                      <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                      <td><span class="bh-badge <?php echo bh_order_status_badge_class($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
          <div class="bh-actions" style="margin-top:1rem;">
            <a href="admin-orders.php" class="bh-btn bh-btn-primary bh-btn-sm">View All Orders</a>
          </div>
        </div>

        <div class="bh-panel">
          <h3>Low Stock Items</h3>
          <?php if (empty($lowStockItems)): ?>
            <div class="bh-chart-empty"><i class="fa-solid fa-circle-check"></i>All stock levels are healthy.</div>
          <?php else: ?>
            <div class="bh-activity-list">
              <?php foreach ($lowStockItems as $item): ?>
                <div class="bh-activity-row">
                  <div class="bh-activity-icon" style="background:rgba(179,69,42,0.14);color:var(--bh-danger);"><i class="fa-solid fa-triangle-exclamation"></i></div>
                  <div class="bh-activity-body">
                    <div class="bh-activity-title"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="bh-activity-meta">Threshold: <?php echo (int)$item['low_stock_threshold']; ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                  </div>
                  <div class="bh-activity-time" style="color:var(--bh-danger);font-weight:700;"><?php echo (int)$item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="bh-actions" style="margin-top:1rem;">
            <a href="admin-inventory.php" class="bh-btn bh-btn-secondary bh-btn-sm">Manage Inventory</a>
          </div>
        </div>
      </div>

      <div class="bh-panel">
        <h3>Quick Links</h3>
        <div class="bh-actions">
          <a href="admin-accounts.php" class="bh-btn bh-btn-primary">Manage Accounts</a>
          <a href="admin-orders.php" class="bh-btn bh-btn-secondary">View Orders</a>
          <a href="admin-products.php" class="bh-btn bh-btn-secondary">Manage Products</a>
        </div>
      </div>
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

    var bhChartFont = "'Inter', 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.family = bhChartFont;
    Chart.defaults.color = '#6b5644';

    <?php if ($hasOrderTrendData): ?>
    new Chart(document.getElementById('ordersChart'), {
      data: {
        labels: <?php echo json_encode($dayLabels); ?>,
        datasets: [
          {
            type: 'bar',
            label: 'Orders',
            data: <?php echo json_encode($orderCountValues); ?>,
            backgroundColor: 'rgba(111, 78, 55, 0.55)',
            borderRadius: 6,
            maxBarThickness: 40,
            yAxisID: 'yCount'
          },
          {
            type: 'line',
            label: 'Revenue (₱)',
            data: <?php echo json_encode($orderRevenueValues); ?>,
            borderColor: '#C98A2C',
            backgroundColor: '#C98A2C',
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2.5,
            yAxisID: 'yRevenue'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', align: 'end', labels: { boxWidth: 12, usePointStyle: true } },
          tooltip: { backgroundColor: '#3B2419', padding: 10, cornerRadius: 8, titleFont: { weight: '700' } }
        },
        scales: {
          yCount: { beginAtZero: true, position: 'left', ticks: { precision: 0 }, grid: { color: 'rgba(111,78,55,0.08)' }, title: { display: true, text: 'Orders' } },
          yRevenue: { beginAtZero: true, position: 'right', grid: { display: false }, title: { display: true, text: 'Revenue (₱)' } },
          x: { grid: { display: false } }
        }
      }
    });
    <?php endif; ?>

    <?php if ($hasInventoryData): ?>
    new Chart(document.getElementById('inventoryChart'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode(array_column($inventoryItems, 'name')); ?>,
        datasets: [{
          label: 'Stock on hand',
          data: <?php echo json_encode(array_map('intval', array_column($inventoryItems, 'quantity'))); ?>,
          backgroundColor: <?php echo json_encode(array_map(function ($item) {
              if ($item['quantity'] <= $item['low_stock_threshold']) return '#B3452A';
              if ($item['quantity'] <= $item['low_stock_threshold'] * 2) return '#C98A2C';
              return '#5F7A4F';
          }, $inventoryItems)); ?>,
          borderRadius: 6,
          maxBarThickness: 32
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#3B2419', padding: 10, cornerRadius: 8,
            callbacks: {
              label: function (ctx) {
                var units = <?php echo json_encode(array_column($inventoryItems, 'unit')); ?>;
                return ctx.parsed.x + ' ' + units[ctx.dataIndex] + ' on hand';
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(111,78,55,0.08)' } },
          y: { grid: { display: false } }
        }
      }
    });
    <?php endif; ?>
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
