<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role_or_privilege(['super_admin'], 'approve_accounts');

$isSuperAdmin = ($me['role'] === 'super_admin');
$search = sanitizeInput($_GET['search'] ?? '');
$redirectBack = 'user-approvals.php';

// Pending Customer / User registrations only — never Admin or Super Admin.
$where = [
    "role = 'customer'",
    "approval_status = 'pending'",
];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = "(id_number LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ? OR username LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

$sql = "SELECT id_number, firstname, lastname, middlename, extension, birth_date, age, gender, username, email, street, barangay, city_municipality, province, zipcode, country, role, approval_status, account_status, first_login, created_at
        FROM users
        WHERE " . implode(' AND ', $where) . "
        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);
$accounts = [];
while ($row = mysqli_fetch_assoc($rows)) {
    $accounts[] = $row;
}
mysqli_stmt_close($stmt);

$pendingCount = count($accounts);
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

$msgText = [
    'account_approved' => 'Customer account approved successfully.',
    'account_rejected' => 'Customer registration rejected.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Approvals - Brew Haven</title>
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
      <span class="bh-role-label"><?php echo $isSuperAdmin ? 'Super Admin' : 'Admin'; ?></span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <?php if ($isSuperAdmin): ?>
      <a href="super-admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="super-admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php" class="active"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
      <?php else: ?>
      <a href="admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="admin-accounts.php"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php" class="active"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <?php if (has_privilege($me, 'manage_deletion_requests')): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
      <?php endif; ?>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title">User Approvals</h1>
      <p style="color:var(--bh-text-muted);margin-top:-0.35rem;margin-bottom:1rem;">Review pending Customer / User registrations. Admin and Super Admin accounts are never listed here.</p>

      <?php if (isset($_GET['msg'])): ?>
        <div class="bh-alert bh-alert-success"><?php echo htmlspecialchars($msgText[$_GET['msg']] ?? ('Action completed: ' . $_GET['msg'])); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="bh-alert bh-alert-error">Error: <?php echo htmlspecialchars(bh_account_action_message(bh_confirm_error_message($_GET['error']))); ?></div>
      <?php endif; ?>

      <div class="bh-panel" style="margin-top:0.75rem;">
        <form method="get" action="user-approvals.php" class="bh-toolbar" id="approvalFilters">
          <input class="admin-account-search" type="text" name="search" placeholder="Search by ID, name, username, or email" value="<?php echo htmlspecialchars($search); ?>">
          <button type="button" id="resetApprovalFilters" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</button>
          <span style="font-size:0.8rem;color:var(--bh-text-muted);margin-left:auto;"><?php echo (int)$pendingCount; ?> pending</span>
        </form>

        <div class="bh-table-wrap">
          <table class="bh-table bh-accounts-table">
            <thead>
              <tr>
                <th>ID Number</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Registered</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="approvalsTableBody">
              <?php foreach ($accounts as $a):
                $a['display_status'] = trim(strip_tags(bh_account_status_badge($a)));
                $displayId = bh_display_id($a);
                $acctLabel = trim($a['firstname'] . ' ' . $a['lastname']) . ' (' . $a['username'] . ')';
                $rowCanApprove = bh_can_account_action($me, $a, 'approve');
                $rowCanReject  = bh_can_account_action($me, $a, 'reject');
                $rowCanView    = bh_can_account_action($me, $a, 'view');
              ?>
                <tr data-view='<?php echo htmlspecialchars(json_encode($a), ENT_QUOTES); ?>'>
                  <td><?php echo htmlspecialchars($displayId['value'] ?? '—'); ?></td>
                  <td style="display:flex;align-items:center;gap:0.6rem;">
                    <span class="bh-avatar bh-avatar-sm" style="background:<?php echo bh_avatar_color($a['id_number']); ?>;"><?php echo htmlspecialchars(bh_initials($a['firstname'], $a['lastname'])); ?></span>
                    <?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname']); ?>
                  </td>
                  <td><?php echo htmlspecialchars($a['username']); ?></td>
                  <td><?php echo htmlspecialchars($a['email']); ?></td>
                  <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($a['created_at']))); ?></td>
                  <td>
                    <span class="bh-badge bh-badge-pending">Pending</span>
                  </td>
                  <td>
                    <?php if ($rowCanView || $rowCanApprove || $rowCanReject): ?>
                      <div class="bh-dropdown">
                        <button type="button" class="bh-dropdown-trigger" onclick="bhToggleMenu(this)" aria-label="Actions"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <div class="bh-dropdown-menu">
                          <?php if ($rowCanView): ?>
                            <button type="button" onclick="bhOpenView(this)"><i class="fa-solid fa-eye"></i> View Details</button>
                          <?php endif; ?>
                          <?php if ($rowCanApprove || $rowCanReject): ?>
                            <div class="bh-menu-divider"></div>
                          <?php endif; ?>
                          <?php if ($rowCanApprove): ?>
                            <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Approve Customer" data-bh-confirm="Approve <?php echo htmlspecialchars($acctLabel); ?>? This Customer / User will be able to sign in immediately." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-button="Approve">
                              <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                              <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                              <input type="hidden" name="action" value="approve">
                              <button type="submit"><i class="fa-solid fa-check"></i> Approve</button>
                            </form>
                          <?php endif; ?>
                          <?php if ($rowCanReject): ?>
                            <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Reject Customer" data-bh-confirm="Reject the registration of <?php echo htmlspecialchars($acctLabel); ?>? They will not be able to sign in." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Reject">
                              <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                              <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                              <input type="hidden" name="action" value="reject">
                              <button type="submit" class="bh-menu-danger"><i class="fa-solid fa-xmark"></i> Reject</button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php else: ?>
                      <em style="color:var(--bh-text-muted);font-size:0.8rem;">No actions available</em>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($accounts)): ?>
                <tr><td colspan="7">No pending Customer / User registrations<?php echo $search !== '' ? ' match this search' : ''; ?>.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="viewAccountModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseView()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Customer Registration Details</h3>
      <div class="bh-form-grid">
        <div><label>ID Number</label><p id="viewIdNumber">—</p></div>
        <div><label>Username</label><p id="viewUsername">—</p></div>
        <div><label>First Name</label><p id="viewFirstname">—</p></div>
        <div><label>Last Name</label><p id="viewLastname">—</p></div>
        <div><label>Middle Name</label><p id="viewMiddlename">—</p></div>
        <div><label>Extension</label><p id="viewExtension">—</p></div>
        <div><label>Birth Date</label><p id="viewBirthDate">—</p></div>
        <div><label>Age</label><p id="viewAge">—</p></div>
        <div><label>Gender</label><p id="viewGender">—</p></div>
        <div><label>Email</label><p id="viewEmail">—</p></div>
      </div>
      <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Address</h4>
      <div class="bh-form-grid">
        <div><label>Street</label><p id="viewStreet">—</p></div>
        <div><label>Barangay</label><p id="viewBarangay">—</p></div>
        <div><label>City/Municipality</label><p id="viewCity">—</p></div>
        <div><label>Province</label><p id="viewProvince">—</p></div>
        <div><label>Zip Code</label><p id="viewZipcode">—</p></div>
        <div><label>Country</label><p id="viewCountry">—</p></div>
      </div>
      <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Account</h4>
      <div class="bh-form-grid">
        <div><label>Role</label><p id="viewRole">—</p></div>
        <div><label>Account Status</label><p id="viewStatus">—</p></div>
        <div><label>Approval Status</label><p id="viewApproval">—</p></div>
        <div><label>Date Registered</label><p id="viewCreatedAt">—</p></div>
      </div>
      <div class="bh-modal-actions">
        <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseView()">Close</button>
      </div>
    </div>
  </div>

  <script src="../js/bh-dropdown.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-dropdown.js'); ?>"></script>
  <script>
    function bhToggleNotif() {
      document.getElementById('notifDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
      var wrap = document.querySelector('.bh-notif-wrap');
      var dd = document.getElementById('notifDropdown');
      if (wrap && dd && !wrap.contains(e.target)) { dd.classList.remove('open'); }
    });

    function bhSetText(id, value) {
      var el = document.getElementById(id);
      if (!el) return;
      var text = (value === null || value === undefined || value === '') ? '—' : String(value);
      el.textContent = text;
    }
    function bhOpenView(btn) {
      bhCloseAllMenus();
      var row = btn.closest('tr');
      var a = JSON.parse(row.getAttribute('data-view'));
      bhSetText('viewIdNumber', a.id_number);
      bhSetText('viewUsername', a.username);
      bhSetText('viewFirstname', a.firstname);
      bhSetText('viewLastname', a.lastname);
      bhSetText('viewMiddlename', a.middlename);
      bhSetText('viewExtension', a.extension);
      bhSetText('viewBirthDate', a.birth_date);
      bhSetText('viewAge', a.age);
      bhSetText('viewGender', a.gender);
      bhSetText('viewEmail', a.email);
      bhSetText('viewStreet', a.street);
      bhSetText('viewBarangay', a.barangay);
      bhSetText('viewCity', a.city_municipality);
      bhSetText('viewProvince', a.province);
      bhSetText('viewZipcode', a.zipcode);
      bhSetText('viewCountry', a.country);
      bhSetText('viewRole', 'Customer / User');
      bhSetText('viewStatus', a.display_status || a.account_status);
      bhSetText('viewApproval', a.approval_status);
      bhSetText('viewCreatedAt', a.created_at);
      document.getElementById('viewAccountModal').classList.add('open');
    }
    function bhCloseView() {
      document.getElementById('viewAccountModal').classList.remove('open');
    }
    document.getElementById('viewAccountModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });

    (function () {
      var form = document.getElementById('approvalFilters');
      var searchTimer = null;
      form.addEventListener('submit', function (event) { event.preventDefault(); form.submit(); });
      form.elements.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { form.submit(); }, 350);
      });
      document.getElementById('resetApprovalFilters').addEventListener('click', function () {
        window.location.href = 'user-approvals.php';
      });
    })();
  </script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
