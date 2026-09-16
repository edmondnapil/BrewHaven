<?php
require_once __DIR__ . '/../php/auth_helpers.php';
// The Super Admin console is for Super Admins only. An Admin's add-on
// privileges ("Delete Users" etc.) are exercised from the Admin pages.
$me = require_role(['super_admin']);
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);

$search = sanitizeInput($_GET['search'] ?? '');
$roleFilter = $_GET['role_filter'] ?? '';
$approvalFilter = $_GET['approval_filter'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'newest';
$validRoles = ['super_admin', 'admin', 'customer'];
$validApproval = ['pending', 'approved', 'rejected'];
$validAccountStatus = ['active', 'inactive', 'blocked'];
$validPerPage = [10, 25, 50];
$requestedPerPage = (int)($_GET['per_page'] ?? 10);
$perPage = in_array($requestedPerPage, $validPerPage, true) ? $requestedPerPage : 10;
$page = max(1, intval($_GET['page'] ?? 1));

// One unified account list: every role in the same table, filtered by role
// rather than split into separate Employee / Customer views.
$where = [];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = "(id_number LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ? OR username LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}
if (in_array($roleFilter, $validRoles, true)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}
if (in_array($approvalFilter, $validApproval, true)) {
    $where[] = "approval_status = ?";
    $params[] = $approvalFilter;
    $types .= 's';
}
if (in_array($statusFilter, $validAccountStatus, true)) {
    if ($statusFilter === 'blocked') {
        $where[] = "account_status = 'blocked'";
    } elseif ($statusFilter === 'inactive') {
        // Mirrors bh_account_status_badge(): stored Inactive, plus older rows
        // still in first-login setup that predate the stored status.
        $where[] = "(account_status = 'inactive' OR (account_status = 'active' AND first_login IN (1, 2, 3) AND role != 'super_admin'))";
    } elseif ($statusFilter === 'active') {
        $where[] = "(account_status = 'active' AND approval_status = 'approved' AND NOT (first_login IN (1, 2, 3) AND role != 'super_admin'))";
    }
}
$whereSql = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

$orderSql = 'ORDER BY created_at DESC';
if ($sortBy === 'name_asc') { $orderSql = 'ORDER BY firstname ASC, lastname ASC'; }
elseif ($sortBy === 'name_desc') { $orderSql = 'ORDER BY firstname DESC, lastname DESC'; }
elseif ($sortBy === 'oldest') { $orderSql = 'ORDER BY created_at ASC'; }

$countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM users" . $whereSql);
if (!empty($params)) { mysqli_stmt_bind_param($countStmt, $types, ...$params); }
mysqli_stmt_execute($countStmt);
$totalRows = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);
mysqli_stmt_close($countStmt);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT id_number, firstname, lastname, middlename, extension, birth_date, age, gender, username, email, street, barangay, city_municipality, province, zipcode, country, role, approval_status, account_status, first_login, auth_question1, created_at FROM users" . $whereSql . " $orderSql LIMIT $perPage OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);
$accounts = [];
while ($row = mysqli_fetch_assoc($rows)) { $accounts[] = $row; }
mysqli_stmt_close($stmt);

// Privileges lookup for the "Manage Privileges" modal, keyed by admin id_number,
// so the row's data-edit JSON can carry it without a separate page/AJAX call.
$privilegesByAdmin = [];
$privResult = mysqli_query($conn, "SELECT admin_id_number, privilege_key FROM admin_privileges");
while ($row = mysqli_fetch_assoc($privResult)) {
    $privilegesByAdmin[$row['admin_id_number']][] = $row['privilege_key'];
}
foreach ($accounts as &$a) {
    $a['privileges'] = array_values(array_unique($privilegesByAdmin[$a['id_number']] ?? []));
    if (array_intersect($a['privileges'], ['view_logs', 'view_activity_logs', 'view_login_logs'])) {
        $a['privileges'][] = 'view_logs';
        $a['privileges'] = array_values(array_unique($a['privileges']));
    }
}
unset($a);

// Both checkbox groups in the Manage Privileges modal are built from the same
// catalog the backend labels and grants by, so a privilege can never appear
// here under a name the authorization layer does not know.
$privilegeLabels = [];
foreach (default_admin_privileges() as $key) {
    $privilegeLabels[$key] = bh_privilege_label($key);
}
$superAdminPrivilegeLabels = [];
foreach (default_super_admin_privileges() as $key) {
    $superAdminPrivilegeLabels[$key] = bh_privilege_label($key);
}

function bh_role_label($role) {
    $map = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'customer' => 'Customer'];
    return $map[$role] ?? $role;
}

function bh_qs($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}

// Actions land back on the single unified accounts list.
$redirectBack = 'super-admin-accounts.php';

// Re-open the create-account form automatically if we just bounced back from a
// failed submission there, so the admin doesn't lose context.
$createStaffErrors = ['invalid_role', 'missing_fields', 'weak_password', 'duplicate_account', 'create_failed'];
$openCreatePanel = isset($_GET['error']) && in_array($_GET['error'], $createStaffErrors, true);
// Preview only: the id_number actually assigned is (re)generated at submit time
// in admin_create_staff.php, so a concurrent creation can't collide with this
// preview. One sequence now serves every role.
$nextIdNumberPreview = generate_next_id_number();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accounts - Brew Haven Super Admin</title>
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
      <span class="bh-role-label">Super Admin</span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="super-admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="super-admin-accounts.php" class="active"><i class="fa-solid fa-users"></i>Accounts</a>
      <a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a>
      <a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
        <h1 class="bh-page-title" style="margin-bottom:0;">Accounts</h1>
        <button type="button" id="createAccountToggle" class="bh-btn bh-btn-primary" onclick="bhToggleCreateAccount()"><i class="fa-solid fa-plus"></i> Create New</button>
      </div>

      <?php
        $bhMsgText = [
            'super_admin_created_inactive'      => 'Super Admin account created as Inactive. It becomes the Active Super Admin when you log out, and your account will be blocked.',
            'role_changed_super_admin_inactive' => 'Role changed to Super Admin (Inactive). It becomes the Active Super Admin when you log out, and your account will be blocked.',
        ];
      ?>
      <?php if (isset($_GET['msg'])): ?><div class="bh-alert bh-alert-success"><?php echo isset($bhMsgText[$_GET['msg']]) ? htmlspecialchars($bhMsgText[$_GET['msg']]) : 'Action completed: ' . htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
      <?php if (isset($_GET['error'])): ?><div class="bh-alert bh-alert-error">Error: <?php echo htmlspecialchars(bh_account_action_message(bh_confirm_error_message($_GET['error']))); ?></div><?php endif; ?>

      <div class="bh-tabs" style="margin-top:0.5rem;">
        <span class="bh-tab active"><i class="fa-solid fa-users" style="margin-right:0.35rem;"></i>All Accounts</span>
      </div>

      <div class="bh-panel" style="margin-top:0.75rem;">
        <form method="get" action="super-admin-accounts.php" id="accountsFilterForm" class="bh-toolbar" style="flex-wrap:wrap;">
          <div class="bh-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" name="search" placeholder="Search by ID Number, name, username, or email" value="<?php echo htmlspecialchars($search); ?>"></div>
          <select name="role_filter">
            <option value="">All Roles</option>
            <option value="super_admin" <?php echo $roleFilter === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
            <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>User / Customer</option>
          </select>
          <select name="approval_filter">
            <option value="">All Approval</option>
            <option value="pending" <?php echo $approvalFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $approvalFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $approvalFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
          <select name="status_filter">
            <option value="">All Status</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
          </select>
          <select name="sort_by">
            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="name_asc" <?php echo $sortBy === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
            <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
          </select>
          <select name="per_page">
            <?php foreach ($validPerPage as $pp): ?>
              <option value="<?php echo $pp; ?>" <?php echo $perPage === $pp ? 'selected' : ''; ?>><?php echo $pp; ?> per page</option>
            <?php endforeach; ?>
          </select>
          <!-- Filters apply live (see the script at the end); Reset clears them. -->
          <a href="super-admin-accounts.php" id="resetAccounts" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</a>
        </form>

        <div class="bh-table-wrap">
          <table class="bh-table bh-accounts-table">
            <thead><tr><th>ID Number</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Approval</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="accountsTableBody">
              <?php foreach ($accounts as $a):
                $a['display_status'] = trim(strip_tags(bh_account_status_badge($a)));
                $initials = bh_initials($a['firstname'], $a['lastname']);
                $color = bh_avatar_color($a['id_number']);
                $isSelf = ($a['id_number'] === $me['id_number']);
                $displayId = bh_display_id($a);
                $acctLabel = trim($a['firstname'] . ' ' . $a['lastname']) . ' (' . $a['username'] . ')';
                // Every menu entry asks the same permission matrix the endpoint
                // asks. The menu is the readable summary of the rule; the
                // endpoint re-runs it, so a hidden entry is never the only
                // thing standing between a user and the action.
                $canEdit          = bh_can_account_action($me, $a, 'edit');
                $canBlock         = bh_can_account_action($me, $a, 'block');
                $canUnblock       = bh_can_account_action($me, $a, 'unblock');
                $canDelete        = bh_can_account_action($me, $a, 'delete');
                $canResetPassword = bh_can_account_action($me, $a, 'reset_password');
                $canChangeRole    = bh_can_account_action($me, $a, 'change_role') && in_array($a['role'], ['admin', 'super_admin'], true);
                $canPrivileges    = bh_can_account_action($me, $a, 'manage_privileges') && in_array($a['role'], ['admin', 'super_admin'], true);
                $isBlockedRow     = ($a['account_status'] === 'blocked');
              ?>
                <tr data-edit='<?php echo htmlspecialchars(json_encode($a), ENT_QUOTES); ?>'>
                  <td><?php echo htmlspecialchars($displayId['value'] ?? '—'); ?></td>
                  <td style="display:flex;align-items:center;gap:0.6rem;">
                    <span class="bh-avatar bh-avatar-sm" style="background:<?php echo $color; ?>;"><?php echo htmlspecialchars($initials); ?></span>
                    <?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname']); ?>
                  </td>
                  <td><?php echo htmlspecialchars($a['username']); ?></td>
                  <td><?php echo htmlspecialchars($a['email']); ?></td>
                  <td><?php echo htmlspecialchars(bh_role_label($a['role'])); ?></td>
                  <td><span class="bh-badge bh-badge-<?php echo $a['approval_status']; ?>"><?php echo htmlspecialchars(ucfirst($a['approval_status'])); ?></span></td>
                  <td><?php echo bh_account_status_badge($a); ?></td>
                  <td>
                    <?php if ($isSelf): ?>
                      <em style="color:var(--bh-text-muted);font-size:0.8rem;">This is you</em>
                    <?php else: ?>
                      <div class="bh-dropdown">
                        <button type="button" class="bh-dropdown-trigger" onclick="bhToggleMenu(this)" aria-label="Actions"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <div class="bh-dropdown-menu">
                          <button type="button" onclick="bhOpenView(this)"><i class="fa-solid fa-eye"></i> View Details</button>
                          <?php if ($canEdit): ?>
                            <button type="button" onclick="bhOpenEdit(this)"><i class="fa-solid fa-pen"></i> Edit</button>
                          <?php endif; ?>
                          <?php if ($canResetPassword): ?>
                            <button type="button" onclick="bhOpenResetPassword(this)"><i class="fa-solid fa-key"></i> Reset Password</button>
                          <?php endif; ?>
                          <?php if ($canChangeRole): ?>
                            <button type="button" onclick="bhOpenChangeRole(this)"><i class="fa-solid fa-user-shield"></i> Change Role</button>
                          <?php endif; ?>
                          <?php if ($canPrivileges): ?>
                            <button type="button" onclick="bhOpenPrivileges(this)"><i class="fa-solid fa-sliders"></i> Manage Privileges</button>
                          <?php endif; ?>
                          <?php if ($canBlock || $canUnblock || $canDelete): ?>
                            <div class="bh-menu-divider"></div>
                          <?php endif; ?>
                          <?php if ($canBlock): ?>
                            <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Block Account" data-bh-confirm="Block <?php echo htmlspecialchars($acctLabel); ?>? They will be signed out and unable to sign in again until unblocked." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Block">
                              <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                              <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                              <input type="hidden" name="action" value="block">
                              <button type="submit"><i class="fa-solid fa-ban"></i> Block</button>
                            </form>
                          <?php endif; ?>
                          <?php if ($canUnblock): ?>
                            <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Unblock Account" <?php if ($a['role'] === 'super_admin'): ?>data-bh-confirm="Unblock <?php echo htmlspecialchars($acctLabel); ?>? This Super Admin account must be assigned a new role before it can be unblocked." data-bh-confirm-role='<?php echo htmlspecialchars(json_encode(bh_unblock_super_admin_role_options()), ENT_QUOTES); ?>'<?php else: ?>data-bh-confirm="Unblock <?php echo htmlspecialchars($acctLabel); ?>? They will be able to sign in again."<?php endif; ?> data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-button="Unblock">
                              <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                              <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                              <input type="hidden" name="action" value="unblock">
                              <button type="submit"><i class="fa-solid fa-lock-open"></i> Unblock</button>
                            </form>
                          <?php endif; ?>
                          <?php if ($canDelete): ?>
                            <button type="button" class="bh-menu-danger" onclick="bhOpenDelete('<?php echo htmlspecialchars($a['id_number'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['username'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($displayId['value'] ?? '-', ENT_QUOTES); ?>','<?php echo htmlspecialchars(bh_role_label($a['role']), ENT_QUOTES); ?>','<?php echo htmlspecialchars($displayId['label'], ENT_QUOTES); ?>')"><i class="fa-solid fa-trash"></i> Delete</button>
                          <?php endif; ?>
                          <?php if ($isBlockedRow): ?>
                            <div class="bh-menu-divider"></div>
                            <span style="display:block;padding:0.45rem 0.75rem;font-size:0.72rem;color:var(--bh-text-muted);line-height:1.35;"><?php echo $canDelete ? 'This account is blocked. It can be unblocked or deleted; unblock it to make any other change.' : 'This account is blocked. Unblock it to make any other change.'; ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($accounts)): ?><tr><td colspan="8">No accounts match this filter.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <div id="accountsPagination">
        <?php echo bh_render_pagination($totalRows, $page, $perPage, function ($p) {
            return bh_qs(['page' => $p]);
        }, 'results'); ?>
        </div>
      </div>

      <div class="bh-modal-backdrop<?php echo $openCreatePanel ? ' open' : ''; ?>" id="createAccountModal">
      <!-- Same width class the Edit and View modals on this page already use,
           so the create form matches them and the privilege columns have room. -->
      <div class="bh-modal bh-modal-lg">
        <button type="button" class="bh-modal-close" onclick="bhToggleCreateAccount()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <h3>Create Account</h3>
        <div id="ca_form-error" class="error-message" style="margin-bottom:0.75rem;"></div>
        <form method="post" action="../php/admin_create_staff.php" id="createStaffForm" novalidate data-bh-confirm-title="Create Account" data-bh-confirm="Are you sure you want to create this account?" data-bh-confirm-password="1" data-bh-confirm-button="Create Account">
          <h4 style="margin:0 0 0.75rem;color:var(--bh-espresso);"><i class="fa-solid fa-id-card" style="margin-right:0.4rem;"></i>Account Information</h4>
          <!-- Same four fields for every account type. Only the read-only ID
               preview follows the selected role; the value that is actually
               stored is generated by the backend. -->
          <div class="bh-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
              <label for="ca_idPreview">ID Number</label>
              <input type="text" id="ca_idPreview" readonly
                     value="<?php echo htmlspecialchars($nextIdNumberPreview); ?>"
                     style="background:var(--bh-cream);color:var(--bh-text-muted);cursor:not-allowed;">
              <span style="font-size:0.75rem;color:var(--bh-text-muted);">Automatically generated by system.</span>
            </div>
            <div>
              <label for="ca_email">Email<span style="color:var(--bh-danger);">*</span></label>
              <input type="email" name="email" id="ca_email" required placeholder="Working email address" autocomplete="off">
              <div id="ca_email-error" class="error-message"></div>
            </div>
            <div>
              <label for="ca_username">Username<span style="color:var(--bh-danger);">*</span></label>
              <input type="text" name="username" id="ca_username" required placeholder="e.g. juan1234" autocomplete="off">
              <div id="ca_username-error" class="error-message"></div>
            </div>
            <div>
              <label for="ca_role">Role<span style="color:var(--bh-danger);">*</span></label>
              <select name="role" id="ca_role" required onchange="bhRenderRolePrivileges('ca_role','ca_privileges','ca_selectedRole','ca_roleNote')">
                <option value="">-- Select a role --</option>
                <option value="customer">User / Customer</option>
                <option value="admin">Administrator</option>
                <option value="super_admin">Super Administrator</option>
              </select>
              <div id="ca_role-error" class="error-message"></div>
              <!-- Filled from bh_role_creation_notes() for the selected role. -->
              <small id="ca_roleNote" style="display:none;margin-top:4px;color:var(--bh-text-muted);font-size:0.75rem;"></small>
            </div>
            <!-- The privileges the chosen role actually carries, swapped in
                 live from the backend's own role configuration. Given a row of
                 its own across the whole grid so the list has room to breathe
                 instead of being squeezed into the Role column. -->
            <div class="bh-privilege-block">
              <p class="bh-privilege-caption">Privileges: <span id="ca_selectedRole">—</span></p>
              <ul id="ca_privileges" class="bh-privilege-list-empty">Select a role to see the privileges it carries.</ul>
            </div>
            <div>
              <label for="ca_password">Password<span style="color:var(--bh-danger);">*</span></label>
              <div class="input-with-toggle" style="position:relative;">
                <input type="password" name="password" id="ca_password" required placeholder="At least 8 characters" autocomplete="new-password">
                <i class="fa-solid fa-eye togele-eye" data-target="ca_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--bh-text-muted);"></i>
              </div>
              <div class="password-strength-container" style="margin-top:4px;">
                <div class="password-strength-bar">
                  <div class="strength-bar"></div>
                </div>
                <div class="password-strength-message" style="font-size:0.75rem;margin-top:2px;"></div>
              </div>
              <div id="ca_password-error" class="error-message"></div>
            </div>
            <div>
              <label for="ca_repassword">Confirm Password<span style="color:var(--bh-danger);">*</span></label>
              <div class="input-with-toggle" style="position:relative;">
                <input type="password" name="repassword" id="ca_repassword" required placeholder="Re-enter password" autocomplete="new-password">
                <i class="fa-solid fa-eye togele-eye" data-target="ca_repassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--bh-text-muted);"></i>
              </div>
              <div id="ca_repassword-error" class="error-message"></div>
              <div id="ca_password-match" class="password-match" style="font-size:0.75rem;margin-top:2px;"></div>
            </div>
          </div>
          <div class="bh-modal-actions" style="margin-top:1.25rem;">
            <button type="button" class="bh-btn bh-btn-secondary" onclick="bhToggleCreateAccount()">Cancel</button>
            <button type="submit" class="bh-btn bh-btn-primary" id="createStaffSubmit">Save</button>
          </div>
        </form>
      </div>
      </div>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="editAccountModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseEdit()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Edit Account</h3>
      <form method="post" action="../php/account_update.php" id="editAccountForm" data-bh-confirm-title="Confirm Update" data-bh-confirm="" data-bh-confirm-reason="1" data-bh-confirm-password="1">
        <input type="hidden" name="id_number" id="editIdNumber">
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
        <div class="bh-form-grid">
          <div><label>First Name</label><input type="text" name="firstname" id="editFirstname" required></div>
          <div><label>Last Name</label><input type="text" name="lastname" id="editLastname" required></div>
          <div><label>Middle Name</label><input type="text" name="middlename" id="editMiddlename"></div>
          <div><label>Birth Date</label><input type="date" name="birth_date" id="editBirthDate"></div>
          <div><label>Age</label><input type="number" name="age" id="editAge" min="18" max="100"></div>
          <div><label>Gender</label>
            <select name="gender" id="editGender"><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select>
          </div>
          <div><label>Email</label><input type="email" name="email" id="editEmail" required></div>
        </div>
        <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Address</h4>
        <div class="bh-form-grid">
          <div><label>Street</label><input type="text" name="street" id="editStreet"></div>
          <div><label>Barangay</label><input type="text" name="barangay" id="editBarangay"></div>
          <div><label>City/Municipality</label><input type="text" name="city_municipality" id="editCity"></div>
          <div><label>Province</label><input type="text" name="province" id="editProvince"></div>
          <div><label>Zip Code</label><input type="text" name="zipcode" id="editZipcode"></div>
          <div><label>Country</label><input type="text" name="country" id="editCountry"></div>
        </div>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseEdit()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="viewAccountModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseView()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Account Details</h3>
      <!-- Read-only mirror of the Edit form: every field the Edit modal can
           change is shown here, plus the account/system fields Edit never
           touches. No inputs and no action buttons - this modal only shows. -->
      <div class="bh-form-grid">
        <div><label>ID Number</label><p id="viewIdNumber">-</p></div>
        <div><label>Username</label><p id="viewUsername">-</p></div>
        <div><label>First Name</label><p id="viewFirstname">-</p></div>
        <div><label>Last Name</label><p id="viewLastname">-</p></div>
        <div><label>Middle Name</label><p id="viewMiddlename">-</p></div>
        <div><label>Extension</label><p id="viewExtension">-</p></div>
        <div><label>Birth Date</label><p id="viewBirthDate">-</p></div>
        <div><label>Age</label><p id="viewAge">-</p></div>
        <div><label>Gender</label><p id="viewGender">-</p></div>
        <div><label>Email</label><p id="viewEmail">-</p></div>
      </div>
      <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Address</h4>
      <div class="bh-form-grid">
        <div><label>Street</label><p id="viewStreet">-</p></div>
        <div><label>Barangay</label><p id="viewBarangay">-</p></div>
        <div><label>City/Municipality</label><p id="viewCity">-</p></div>
        <div><label>Province</label><p id="viewProvince">-</p></div>
        <div><label>Zip Code</label><p id="viewZipcode">-</p></div>
        <div><label>Country</label><p id="viewCountry">-</p></div>
      </div>
      <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Account</h4>
      <div class="bh-form-grid">
        <div><label>Role</label><p id="viewRole">-</p></div>
        <div><label>Account Status</label><p id="viewStatus">-</p></div>
        <div><label>Approval Status</label><p id="viewApproval">-</p></div>
        <div><label>Date Created</label><p id="viewCreatedAt">-</p></div>
      </div>
      <div id="viewPrivilegesSection" style="display:none;">
        <h4 style="margin-top:1.1rem;color:var(--bh-espresso);">Privileges</h4>
        <ul id="viewPrivileges" class="bh-privilege-list"></ul>
      </div>
      <div class="bh-modal-actions">
        <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseView()">Close</button>
      </div>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="privilegesModal">
    <div class="bh-modal privilege-modal">
      <button type="button" class="bh-modal-close" onclick="bhClosePrivileges()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Manage Privileges</h3>
      <p id="privDescription">Choose what <strong id="privName"></strong> is allowed to do.</p>
      <form method="post" action="../php/update_privileges.php" id="privilegesForm" data-bh-confirm-title="Update Privileges" data-bh-confirm="" data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-button="Save Privileges">
        <input type="hidden" name="admin_id_number" id="privId">
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
        <section class="privilege-section">
          <h4>Admin Privileges</h4>
          <div class="privilege-grid privilege-grid-admin">
            <?php foreach ($privilegeLabels as $key => $label): ?>
              <label class="privilege-item" for="priv_<?php echo $key; ?>">
                <input type="checkbox" name="privileges[]" value="<?php echo $key; ?>" id="priv_<?php echo $key; ?>" class="bh-priv-checkbox">
                <span><?php echo $label; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </section>
        <section class="privilege-section">
          <h4>Super Admin Privileges</h4>
          <p style="margin:-0.35rem 0 0.5rem;font-size:0.75rem;color:var(--bh-text-muted);">Extra permissions added to this Admin. The account stays an Admin and cannot access the Super Admin console or Super Admin accounts.</p>
          <div class="privilege-grid privilege-grid-super">
            <?php foreach ($superAdminPrivilegeLabels as $key => $label): ?>
              <label class="privilege-item" for="priv_<?php echo $key; ?>">
                <input type="checkbox" name="privileges[]" value="<?php echo $key; ?>" id="priv_<?php echo $key; ?>" class="bh-priv-checkbox">
                <span><?php echo $label; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </section>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhClosePrivileges()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-primary" id="privSaveButton">Save Privileges</button>
        </div>
      </form>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="bhDeleteModal">
    <div class="bh-modal">
      <h3>Delete Account</h3>
      <p>You are about to permanently delete this account:</p>
      <p><strong>Name:</strong> <span id="bhDelName"></span><br>
         <strong>Username:</strong> <span id="bhDelUsername"></span><br>
         <strong id="bhDelEmpIdLabel">ID Number:</strong> <span id="bhDelEmpId"></span><br>
         <strong>Role:</strong> <span id="bhDelRole"></span></p>
      <p style="color:var(--bh-danger);font-weight:600;">This action cannot be undone.</p>
      <form method="post" action="../php/account_delete_direct.php" id="bhDeleteForm" enctype="multipart/form-data" data-bh-confirm-title="Delete Account" data-bh-confirm="" data-bh-confirm-reason="1" data-bh-confirm-proof="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Delete Permanently">
        <input type="hidden" name="id_number" id="bhDelId">
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseDelete()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-danger">Confirm Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Reset Password. Reuses the existing account_reset_password.php endpoint
       and the same password rules as registration; the shared confirm dialog
       collects the reason and the acting Super Admin's own password. -->
  <div class="bh-modal-backdrop" id="resetPasswordModal">
    <div class="bh-modal">
      <button type="button" class="bh-modal-close" onclick="bhCloseResetPassword()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Reset Password</h3>
      <p style="color:var(--bh-text-muted);font-size:0.85rem;">Set a new password for <strong id="rpName"></strong> (<span id="rpUsername"></span>). They keep the same username and can sign in with the new password immediately.</p>
      <form method="post" action="../php/account_reset_password.php" id="resetPasswordForm" data-bh-confirm-title="Reset Password" data-bh-confirm="" data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Reset Password">
        <input type="hidden" name="id_number" id="rpId">
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
        <div class="bh-form-grid" style="grid-template-columns:1fr;">
          <div>
            <label for="rpPassword">New Password <span style="color:var(--bh-danger);">*</span></label>
            <!-- Same show/hide markup the Create Account fields above use:
                 .input-with-toggle positions the icon, and toggle-visibility.js
                 binds the click through the .togele-eye class. -->
            <div class="input-with-toggle" style="position:relative;">
              <input type="password" name="new_password" id="rpPassword" required placeholder="At least 8 characters" autocomplete="new-password">
              <i class="fa-solid fa-eye togele-eye" data-target="rpPassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--bh-text-muted);"></i>
            </div>
            <div id="rpPassword-error" class="error-message"></div>
          </div>
          <div>
            <label for="rpConfirmPassword">Confirm New Password <span style="color:var(--bh-danger);">*</span></label>
            <div class="input-with-toggle" style="position:relative;">
              <input type="password" name="confirm_new_password" id="rpConfirmPassword" required placeholder="Re-enter the new password" autocomplete="new-password">
              <i class="fa-solid fa-eye togele-eye" data-target="rpConfirmPassword" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--bh-text-muted);"></i>
            </div>
            <div id="rpConfirmPassword-error" class="error-message"></div>
          </div>
        </div>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseResetPassword()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-primary">Reset Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Change Role. The privilege list under the dropdown is rendered from
       bh_role_privileges_json(), i.e. the backend's own role configuration, so
       what is shown here is what the authorization layer actually grants. -->
  <div class="bh-modal-backdrop" id="changeRoleModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseChangeRole()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Change Role</h3>
      <p style="color:var(--bh-text-muted);font-size:0.85rem;">Change what <strong id="crName"></strong> (<span id="crUsername"></span>) is. Current role: <strong id="crCurrentRole"></strong>.</p>
      <form method="post" action="../php/account_change_role.php" id="changeRoleForm" data-bh-confirm-title="Change Role" data-bh-confirm="" data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Change Role">
        <input type="hidden" name="id_number" id="crId">
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
        <div class="bh-form-grid" style="grid-template-columns:1fr;">
          <div>
            <label for="crNewRole">New Role <span style="color:var(--bh-danger);">*</span></label>
            <select name="new_role" id="crNewRole" required onchange="bhRenderRolePrivileges('crNewRole','crPrivileges','crSelectedRole')">
              <option value="">Select a role...</option>
              <option value="admin">Administrator</option>
              <option value="super_admin">Super Administrator</option>
            </select>
            <div id="crNewRole-error" class="error-message"></div>
          </div>
        </div>
        <p class="bh-privilege-caption">Selected Role: <span id="crSelectedRole">—</span></p>
        <ul id="crPrivileges" class="bh-privilege-list"></ul>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseChangeRole()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-primary" id="crSubmit">Change Role</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../js/bh-dropdown.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-dropdown.js'); ?>"></script>
  <script>
    // The privileges each role actually carries, emitted from
    // php/auth_helpers.php (bh_role_privileges_json), so the lists shown when
    // creating an account or changing a role can never drift from the
    // authorization configuration the backend enforces.
    window.BH_ROLE_PRIVILEGES = <?php echo bh_role_privileges_json(); ?>;
    window.BH_ROLE_LABELS = { admin: 'Administrator', super_admin: 'Super Administrator', customer: 'Customer' };
    // Privilege key -> label, from the same catalog the backend labels by.
    window.BH_PRIVILEGE_LABELS = <?php echo json_encode(bh_privilege_catalog()); ?>;
    // What happens to a new account of each role, from bh_role_creation_notes().
    window.BH_ROLE_CREATION_NOTES = <?php echo json_encode(bh_role_creation_notes()); ?>;

    // Renders one role's privilege list into a <ul>, from that shared config,
    // and (when noteId is given) the note for the selected role.
    function bhRenderRolePrivileges(selectId, listId, captionId, noteId) {
      var select = document.getElementById(selectId);
      var list = document.getElementById(listId);
      if (!select || !list) return;
      var role = select.value;
      if (noteId) {
        var note = document.getElementById(noteId);
        var noteText = (role && window.BH_ROLE_CREATION_NOTES && window.BH_ROLE_CREATION_NOTES[role]) || '';
        if (note) { note.textContent = noteText; note.style.display = noteText ? 'block' : 'none'; }
      }
      var privileges = (window.BH_ROLE_PRIVILEGES && window.BH_ROLE_PRIVILEGES[role]) || [];
      if (captionId) {
        var caption = document.getElementById(captionId);
        if (caption) { caption.textContent = role ? (window.BH_ROLE_LABELS[role] || role) : '—'; }
      }
      list.innerHTML = '';
      if (!role) {
        list.className = 'bh-privilege-list-empty';
        list.textContent = 'Select a role to see the privileges it carries.';
        return;
      }
      if (!privileges.length) {
        list.className = 'bh-privilege-list-empty';
        list.textContent = 'This role carries no administrative privileges.';
        return;
      }
      list.className = 'bh-privilege-list';
      privileges.forEach(function (label) {
        var li = document.createElement('li');
        li.textContent = label;
        list.appendChild(li);
      });
    }

    function bhToggleNotif() {
      document.getElementById('notifDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
      var notifWrap = document.querySelector('.bh-notif-wrap');
      var notifDd = document.getElementById('notifDropdown');
      if (notifWrap && notifDd && !notifWrap.contains(e.target)) { notifDd.classList.remove('open'); }
    });

    function bhToggleCreateAccount() {
      var modal = document.getElementById('createAccountModal');
      var opening = !modal.classList.contains('open');
      modal.classList.toggle('open');
      if (opening && window.bhResetCreateStaffWizard) window.bhResetCreateStaffWizard();
    }
    document.getElementById('createAccountModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });

    function bhSetText(id, value) {
      var el = document.getElementById(id);
      if (el) { el.textContent = (value === null || value === undefined || value === '') ? '—' : value; }
    }

    // View Details shows every field the Edit form can change, plus the
    // account fields Edit never touches. It stays strictly read-only.
    function bhOpenView(btn) {
      bhCloseAllMenus();
      var a = JSON.parse(btn.closest('tr').getAttribute('data-edit'));
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
      bhSetText('viewRole', window.BH_ROLE_LABELS[a.role] || a.role);
      bhSetText('viewStatus', a.display_status || a.account_status);
      bhSetText('viewApproval', a.approval_status);
      bhSetText('viewCreatedAt', a.created_at);

      // Staff accounts also show what they are allowed to do. A Super Admin
      // carries the full role set; an Admin shows the keys actually granted.
      var section = document.getElementById('viewPrivilegesSection');
      var list = document.getElementById('viewPrivileges');
      if (a.role === 'customer') {
        section.style.display = 'none';
      } else {
        section.style.display = '';
        var labels = [];
        if (a.role === 'super_admin') {
          labels = (window.BH_ROLE_PRIVILEGES && window.BH_ROLE_PRIVILEGES.super_admin) || [];
        } else {
          var granted = a.privileges || [];
          labels = granted.map(function (key) { return window.BH_PRIVILEGE_LABELS[key] || key; });
        }
        list.innerHTML = '';
        if (!labels.length) {
          list.className = 'bh-privilege-list-empty';
          list.textContent = 'No privileges are currently granted to this account.';
        } else {
          list.className = 'bh-privilege-list';
          labels.forEach(function (label) {
            var li = document.createElement('li');
            li.textContent = label;
            list.appendChild(li);
          });
        }
      }
      document.getElementById('viewAccountModal').classList.add('open');
    }
    function bhCloseView() {
      document.getElementById('viewAccountModal').classList.remove('open');
    }
    document.getElementById('viewAccountModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });

    function bhOpenEdit(btn) {
      bhCloseAllMenus();
      var row = btn.closest('tr');
      var a = JSON.parse(row.getAttribute('data-edit'));
      document.getElementById('editIdNumber').value = a.id_number;
      document.getElementById('editFirstname').value = a.firstname || '';
      document.getElementById('editLastname').value = a.lastname || '';
      document.getElementById('editMiddlename').value = a.middlename || '';
      document.getElementById('editBirthDate').value = a.birth_date || '';
      document.getElementById('editAge').value = a.age || '';
      document.getElementById('editGender').value = a.gender || 'Male';
      document.getElementById('editEmail').value = a.email || '';
      document.getElementById('editStreet').value = a.street || '';
      document.getElementById('editBarangay').value = a.barangay || '';
      document.getElementById('editCity').value = a.city_municipality || '';
      document.getElementById('editProvince').value = a.province || '';
      document.getElementById('editZipcode').value = a.zipcode || '';
      document.getElementById('editCountry').value = a.country || '';
      document.getElementById('editAccountForm').setAttribute('data-bh-confirm',
        'Are you sure you want to update the information of ' + a.firstname + ' ' + a.lastname + ' (' + a.username + ')?');
      document.getElementById('editAccountModal').classList.add('open');
    }
    function bhCloseEdit() {
      document.getElementById('editAccountModal').classList.remove('open');
    }
    document.getElementById('editAccountModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });

    function bhOpenPrivileges(btn) {
      bhCloseAllMenus();
      var row = btn.closest('tr');
      var a = JSON.parse(row.getAttribute('data-edit'));
      var isSuperAdmin = a.role === 'super_admin';
      document.getElementById('privId').value = a.id_number;
      document.getElementById('privName').textContent = a.firstname + ' ' + a.lastname;
      document.querySelectorAll('.bh-priv-checkbox').forEach(function (cb) {
        cb.checked = isSuperAdmin || (a.privileges && a.privileges.indexOf(cb.value) !== -1);
        cb.disabled = isSuperAdmin;
      });
      document.getElementById('privDescription').textContent = isSuperAdmin
        ? 'Privileges for Super Admin accounts are view-only.'
        : 'Choose what ' + a.firstname + ' ' + a.lastname + ' is allowed to do.';
      document.getElementById('privSaveButton').style.display = isSuperAdmin ? 'none' : '';
      document.getElementById('privilegesForm').setAttribute('data-bh-confirm',
        'Update what ' + a.firstname + ' ' + a.lastname + ' (' + a.username + ') is allowed to do?');
      document.getElementById('privilegesModal').classList.add('open');
    }
    function bhClosePrivileges() {
      document.getElementById('privilegesModal').classList.remove('open');
    }
    document.getElementById('privilegesModal').addEventListener('click', function (e) {
      if (e.target === this) { this.classList.remove('open'); }
    });

    // --- Reset Password ----------------------------------------------------
    function bhOpenResetPassword(btn) {
      bhCloseAllMenus();
      var a = JSON.parse(btn.closest('tr').getAttribute('data-edit'));
      document.getElementById('rpId').value = a.id_number;
      document.getElementById('rpName').textContent = a.firstname + ' ' + a.lastname;
      document.getElementById('rpUsername').textContent = a.username;
      document.getElementById('rpPassword').value = '';
      document.getElementById('rpConfirmPassword').value = '';
      bhClearFieldError('rpPassword');
      bhClearFieldError('rpConfirmPassword');
      document.getElementById('resetPasswordForm').setAttribute('data-bh-confirm',
        'Reset the password of ' + a.firstname + ' ' + a.lastname + ' (' + a.username + ')? Their current password stops working immediately.');
      document.getElementById('resetPasswordModal').classList.add('open');
      document.getElementById('rpPassword').focus();
    }
    function bhCloseResetPassword() {
      document.getElementById('resetPasswordModal').classList.remove('open');
      document.getElementById('rpPassword').value = '';
      document.getElementById('rpConfirmPassword').value = '';
    }
    document.getElementById('resetPasswordModal').addEventListener('click', function (e) {
      if (e.target === this) { bhCloseResetPassword(); }
    });

    function bhShowFieldError(id, message) {
      var input = document.getElementById(id);
      var box = document.getElementById(id + '-error');
      if (box) { box.textContent = message; box.classList.toggle('show', !!message); }
      if (input) { input.classList.toggle('bh-field-error', !!message); }
      return !message;
    }
    function bhClearFieldError(id) { return bhShowFieldError(id, ''); }

    // Live validation against the same rules bh_validate_password_strength()
    // enforces on the server, so the reset never fails on a rule the admin
    // could have been told about while typing.
    function bhValidateResetPassword(live) {
      var value = document.getElementById('rpPassword').value;
      if (value === '') { return live ? bhClearFieldError('rpPassword') : bhShowFieldError('rpPassword', 'Password is required.'); }
      if (value.length < 8) { return bhShowFieldError('rpPassword', 'Password must be at least 8 characters long.'); }
      if (value.length > 64) { return bhShowFieldError('rpPassword', 'Password must not exceed 64 characters.'); }
      if (!/[a-z]/.test(value)) { return bhShowFieldError('rpPassword', 'Password must contain at least one lowercase letter.'); }
      if (!/[A-Z]/.test(value)) { return bhShowFieldError('rpPassword', 'Password must contain at least one uppercase letter.'); }
      if (!/[0-9]/.test(value)) { return bhShowFieldError('rpPassword', 'Password must contain at least one number.'); }
      if (!/[^A-Za-z0-9]/.test(value)) { return bhShowFieldError('rpPassword', 'Password must contain at least one special character.'); }
      return bhClearFieldError('rpPassword');
    }
    function bhValidateResetMatch(live) {
      var pw = document.getElementById('rpPassword').value;
      var cf = document.getElementById('rpConfirmPassword').value;
      if (cf === '') { return live ? bhClearFieldError('rpConfirmPassword') : bhShowFieldError('rpConfirmPassword', 'Please confirm the new password.'); }
      if (pw !== cf) { return bhShowFieldError('rpConfirmPassword', 'Passwords do not match.'); }
      return bhClearFieldError('rpConfirmPassword');
    }
    document.getElementById('rpPassword').addEventListener('input', function () {
      bhValidateResetPassword(true);
      if (document.getElementById('rpConfirmPassword').value !== '') { bhValidateResetMatch(true); }
    });
    document.getElementById('rpConfirmPassword').addEventListener('input', function () { bhValidateResetMatch(true); });
    // The shared confirm dialog asks a form for its own verdict before it
    // prompts for a reason or a password, so an invalid entry is caught first.
    document.getElementById('resetPasswordForm').bhValidate = function () {
      var ok = bhValidateResetPassword(false);
      ok = bhValidateResetMatch(false) && ok;
      return ok;
    };

    // --- Change Role -------------------------------------------------------
    function bhOpenChangeRole(btn) {
      bhCloseAllMenus();
      var a = JSON.parse(btn.closest('tr').getAttribute('data-edit'));
      var select = document.getElementById('crNewRole');
      document.getElementById('crId').value = a.id_number;
      document.getElementById('crName').textContent = a.firstname + ' ' + a.lastname;
      document.getElementById('crUsername').textContent = a.username;
      document.getElementById('crCurrentRole').textContent = window.BH_ROLE_LABELS[a.role] || a.role;
      select.value = '';
      // Offering the role the account already holds would only produce a
      // "no change" error from the endpoint, so it is not offered.
      Array.prototype.forEach.call(select.options, function (opt) {
        opt.hidden = (opt.value !== '' && opt.value === a.role);
        opt.disabled = opt.hidden;
      });
      bhClearFieldError('crNewRole');
      bhRenderRolePrivileges('crNewRole', 'crPrivileges', 'crSelectedRole');
      document.getElementById('changeRoleForm').setAttribute('data-bh-confirm',
        'Change the role of ' + a.firstname + ' ' + a.lastname + ' (' + a.username + ')? This rewrites what the account is allowed to do.');
      document.getElementById('changeRoleModal').classList.add('open');
    }
    function bhCloseChangeRole() {
      document.getElementById('changeRoleModal').classList.remove('open');
    }
    document.getElementById('changeRoleModal').addEventListener('click', function (e) {
      if (e.target === this) { bhCloseChangeRole(); }
    });
    document.getElementById('crNewRole').addEventListener('change', function () { bhClearFieldError('crNewRole'); });
    document.getElementById('changeRoleForm').bhValidate = function () {
      if (document.getElementById('crNewRole').value === '') {
        return bhShowFieldError('crNewRole', 'Please select the new role.');
      }
      return true;
    };

    function bhOpenDelete(id, name, username, empId, role, idLabel) {
      bhCloseAllMenus();
      document.getElementById('bhDelId').value = id;
      document.getElementById('bhDelName').textContent = name;
      document.getElementById('bhDelUsername').textContent = username;
      document.getElementById('bhDelEmpId').textContent = empId;
      document.getElementById('bhDelEmpIdLabel').textContent = (idLabel || 'ID Number') + ':';
      document.getElementById('bhDelRole').textContent = role;
      document.getElementById('bhDeleteForm').setAttribute('data-bh-confirm',
        'Permanently delete ' + name + ' (' + username + ')? This cannot be undone.');
      document.getElementById('bhDeleteModal').classList.add('open');
    }
    function bhCloseDelete() {
      document.getElementById('bhDeleteModal').classList.remove('open');
    }
  </script>
  <script>
    // Live filters, like the Logs page: every change re-renders the list without
    // a page reload. The rows come from this same page rendered by the server for
    // the new filters, so each row keeps exactly the actions the permission
    // matrix allows; only the table body and the pagination are swapped in.
    (function () {
      var form = document.getElementById('accountsFilterForm');
      var body = document.getElementById('accountsTableBody');
      var pagination = document.getElementById('accountsPagination');
      if (!form || !body || !pagination) return;
      var controller = null;
      var searchTimer = null;

      function queryFor(page) {
        var params = new URLSearchParams(new FormData(form));
        var defaults = { sort_by: 'newest', per_page: '10' };
        Array.from(params.keys()).forEach(function (key) {
          if (params.get(key) === '' || defaults[key] === params.get(key)) params.delete(key);
        });
        if (page && page > 1) params.set('page', String(page));
        return params.toString();
      }

      function refresh(page) {
        var qs = queryFor(page);
        var url = 'super-admin-accounts.php' + (qs ? '?' + qs : '');
        if (controller) controller.abort();
        controller = new AbortController();
        body.style.opacity = '0.55';

        fetch(url, { credentials: 'same-origin', signal: controller.signal })
          .then(function (response) {
            // Session ended or role lost: follow the server to the right page.
            if (response.redirected && response.url.indexOf('super-admin-accounts.php') === -1) {
              window.location.href = response.url;
              return null;
            }
            return response.text();
          })
          .then(function (html) {
            if (html === null) return;
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var newBody = doc.getElementById('accountsTableBody');
            var newPagination = doc.getElementById('accountsPagination');
            if (!newBody || !newPagination) { window.location.href = url; return; }
            if (typeof window.bhCloseAllMenus === 'function') window.bhCloseAllMenus();
            body.innerHTML = newBody.innerHTML;
            pagination.innerHTML = newPagination.innerHTML;
            // Keep the address bar in step so refresh / back keep the filters;
            // one-off ?msg= / ?error= banners are not carried along.
            window.history.replaceState(null, '', url);
          })
          .catch(function (error) {
            if (error.name !== 'AbortError') { window.location.href = url; }
          })
          .finally(function () { body.style.opacity = ''; });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        refresh(1);
      });
      Array.prototype.forEach.call(form.querySelectorAll('select'), function (select) {
        select.addEventListener('change', function () { refresh(1); });
      });
      form.elements.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { refresh(1); }, 250);
      });
      document.getElementById('resetAccounts').addEventListener('click', function (event) {
        event.preventDefault();
        form.elements.search.value = '';
        Array.prototype.forEach.call(form.querySelectorAll('select'), function (select) {
          select.selectedIndex = 0;
        });
        form.elements.per_page.value = '10';
        refresh(1);
      });
      pagination.addEventListener('click', function (event) {
        var link = event.target.closest('a');
        if (!link) return;
        event.preventDefault();
        refresh(Number(new URL(link.href, window.location.href).searchParams.get('page')) || 1);
      });
    })();
  </script>
  <!-- The approved email domains, straight from php/auth_helpers.php, so the live check and the server rule are always the same list. -->
  <script>window.BH_APPROVED_EMAIL_DOMAINS = <?php echo bh_approved_email_domains_json(); ?>;</script>
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <script src="../js/toggle-visibility.js?v=<?php echo @filemtime(__DIR__ . '/../js/toggle-visibility.js'); ?>"></script>
  <script src="../js/user-profile-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/user-profile-validation.js'); ?>"></script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
  <script src="../js/admin-create-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/admin-create-validation.js'); ?>"></script>
</body>
</html>
