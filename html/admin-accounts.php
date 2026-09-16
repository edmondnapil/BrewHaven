<?php
require_once __DIR__ . '/../php/auth_helpers.php';
$me = require_role(['admin']);

$search = sanitizeInput($_GET['search'] ?? '');
$roleFilter = sanitizeInput($_GET['role_filter'] ?? '');

$where = [
    "role IN ('admin', 'customer')",
    "role != 'super_admin'"
];
$params = [];
$types = '';
if ($search !== '') {
    $where[] = "(id_number LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ? OR username LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
  if (in_array($roleFilter, ['admin', 'customer'], true)) {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
    $types .= 's';
  }

$sql = "SELECT id_number, firstname, lastname, middlename, extension, birth_date, age, gender, username, email, street, barangay, city_municipality, province, zipcode, country, role, approval_status, account_status, first_login, auth_question1, created_at FROM users WHERE " . implode(' AND ', $where) . " ORDER BY role ASC, created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$rows = mysqli_stmt_get_result($stmt);
$accounts = [];
while ($row = mysqli_fetch_assoc($rows)) { $accounts[] = $row; }
mysqli_stmt_close($stmt);

$redirectBack = 'admin-accounts.php';
// Per-row permissions are resolved by bh_can_account_action() inside the table
// loop, using the same matrix the endpoints run, so the page no longer needs
// its own has_privilege() flags here.
$notifCount = bh_notification_count($me);
$notifItems = bh_recent_notifications($me);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accounts - Brew Haven Admin</title>
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
      <span class="bh-role-label">Admin</span>
      <a href="logout.php" class="bh-topbar-icon-btn" title="Logout" aria-label="Logout" data-bh-confirm-title="Confirm Logout" data-bh-confirm="Are you sure you want to log out?" data-bh-confirm-button="Yes, Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="bh-layout">
    <div class="bh-sidebar">
      <a href="admin-dashboard.php"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="admin-accounts.php" class="active"><i class="fa-solid fa-users"></i>Accounts</a>
      <?php if (has_privilege($me, 'approve_accounts')): ?><a href="user-approvals.php"><i class="fa-solid fa-user-check"></i>User Approvals</a><?php endif; ?>
      <?php if (has_privilege($me, 'manage_deletion_requests')): ?><a href="super-admin-deletion-requests.php"><i class="fa-solid fa-trash-can"></i>Deletion Requests</a><?php endif; ?>
      <a href="logs.php"><i class="fa-solid fa-clipboard-list"></i>Logs</a>
      <a href="admin-products.php"><i class="fa-solid fa-mug-saucer"></i>Products</a>
      <a href="admin-orders.php"><i class="fa-solid fa-receipt"></i>Orders</a>
      <a href="admin-inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</a>
      <a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
    </div>
    <div class="bh-main">
      <h1 class="bh-page-title">Accounts</h1>
      <?php if (isset($_GET['msg'])): ?><div class="bh-alert bh-alert-success">Action completed: <?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
      <?php if (isset($_GET['error'])): ?><div class="bh-alert bh-alert-error">Error: <?php echo htmlspecialchars(bh_account_action_message(bh_confirm_error_message($_GET['error']))); ?></div><?php endif; ?>

      <div class="bh-panel" style="margin-top:0.75rem;">
        <form method="get" action="admin-accounts.php" class="bh-toolbar" id="accountFilters">
          <input class="admin-account-search" type="text" name="search" placeholder="Search by ID Number, Name, or Username" value="<?php echo htmlspecialchars($search); ?>">
          <select class="admin-account-role-filter" name="role_filter" aria-label="Filter by role">
            <option value="">All Roles</option>
            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            <option value="customer" <?php echo $roleFilter === 'customer' ? 'selected' : ''; ?>>Customers</option>
          </select>
          <button type="button" id="resetAccountFilters" class="bh-btn bh-btn-secondary bh-btn-sm">Reset</button>
        </form>

        <div class="bh-table-wrap">
          <table class="bh-table bh-accounts-table">
            <thead><tr><th>ID Number</th><th>Name</th><th>Username</th><th>Role</th><th>Approval</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="accountsTableBody">
              <?php foreach ($accounts as $a):
                $a['display_status'] = trim(strip_tags(bh_account_status_badge($a)));
                $displayId = bh_display_id($a);
                $isSelf = ($a['id_number'] === $me['id_number']);
                $acctLabel = trim($a['firstname'] . ' ' . $a['lastname']) . ' (' . $a['username'] . ')';
                // Same permission matrix the endpoints run. For an Admin this
                // resolves to: customers per the granted privilege keys, and a
                // co-admin as View only.
                $rowCanView    = bh_can_account_action($me, $a, 'view');
                $rowCanEdit    = bh_can_account_action($me, $a, 'edit');
                $rowCanApprove = bh_can_account_action($me, $a, 'approve');
                $rowCanReject  = bh_can_account_action($me, $a, 'reject');
                $rowCanBlock   = bh_can_account_action($me, $a, 'block');
                $rowCanUnblock = bh_can_account_action($me, $a, 'unblock');
                $rowCanRequestDelete = bh_can_account_action($me, $a, 'delete_request');
                // "Delete Users" add-on privilege: delete a customer directly.
                $rowCanDelete  = bh_can_account_action($me, $a, 'delete');
                // "Reset Passwords" / "Change Account Roles" add-on privileges.
                $rowCanResetPassword = bh_can_account_action($me, $a, 'reset_password');
                $rowCanChangeRole    = bh_can_account_action($me, $a, 'change_role');
                $rowIsBlocked  = ($a['account_status'] === 'blocked');
              ?>
                <tr data-edit='<?php echo htmlspecialchars(json_encode($a), ENT_QUOTES); ?>'>
                  <td><?php echo htmlspecialchars($displayId['value'] ?? '—'); ?></td>
                  <td style="display:flex;align-items:center;gap:0.6rem;">
                    <span class="bh-avatar bh-avatar-sm" style="background:<?php echo bh_avatar_color($a['id_number']); ?>;"><?php echo htmlspecialchars(bh_initials($a['firstname'], $a['lastname'])); ?></span>
                    <?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname']); ?>
                  </td>
                  <td><?php echo htmlspecialchars($a['username']); ?></td>
                  <td><?php echo htmlspecialchars($a['role']); ?></td>
                  <td><span class="bh-badge bh-badge-<?php echo $a['approval_status']; ?>"><?php echo htmlspecialchars(ucfirst($a['approval_status'])); ?></span></td>
                  <td><?php echo bh_account_status_badge($a); ?></td>
                  <td>
                    <?php if ($isSelf): ?>
                      <em style="color:var(--bh-text-muted);font-size:0.8rem;">This is you</em>
                    <?php elseif ($rowCanView): ?>
                      <div class="bh-dropdown">
                        <button type="button" class="bh-dropdown-trigger" onclick="bhToggleMenu(this)" aria-label="Actions"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                        <div class="bh-dropdown-menu">
                          <button type="button" onclick="bhOpenView(this)"><i class="fa-solid fa-eye"></i> View Details</button>
                          <?php if ($rowCanEdit): ?>
                            <button type="button" onclick="bhOpenEdit(this)"><i class="fa-solid fa-pen"></i> Edit</button>
                          <?php endif; ?>
                          <?php if ($rowCanResetPassword): ?>
                            <button type="button" onclick="bhOpenResetPassword(this)"><i class="fa-solid fa-key"></i> Reset Password</button>
                          <?php endif; ?>
                          <?php if ($rowCanChangeRole): ?>
                            <button type="button" onclick="bhOpenChangeRole(this)"><i class="fa-solid fa-user-shield"></i> Change Role</button>
                          <?php endif; ?>
                          <?php if ($a['approval_status'] === 'pending' && ($rowCanApprove || $rowCanReject)): ?>
                            <div class="bh-menu-divider"></div>
                            <?php if ($rowCanApprove): ?>
                              <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Approve Account" data-bh-confirm="Approve <?php echo htmlspecialchars($acctLabel); ?>? They will be able to sign in immediately." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-button="Approve">
                                <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit"><i class="fa-solid fa-check"></i> Approve</button>
                              </form>
                            <?php endif; ?>
                            <?php if ($rowCanReject): ?>
                              <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Reject Account" data-bh-confirm="Reject the registration of <?php echo htmlspecialchars($acctLabel); ?>? They will not be able to sign in." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Reject">
                                <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="bh-menu-danger"><i class="fa-solid fa-xmark"></i> Reject</button>
                              </form>
                            <?php endif; ?>
                          <?php else: ?>
                            <?php if ($rowCanBlock || $rowCanUnblock): ?>
                              <div class="bh-menu-divider"></div>
                            <?php endif; ?>
                            <?php if ($rowCanBlock): ?>
                              <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Block Account" data-bh-confirm="Block <?php echo htmlspecialchars($acctLabel); ?>? They will be signed out and unable to sign in again until unblocked." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Block">
                                <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                                <input type="hidden" name="action" value="block">
                                <button type="submit"><i class="fa-solid fa-ban"></i> Block</button>
                              </form>
                            <?php endif; ?>
                            <?php if ($rowCanUnblock): ?>
                              <form method="post" action="../php/account_approve.php" data-bh-confirm-title="Unblock Account" data-bh-confirm="Unblock <?php echo htmlspecialchars($acctLabel); ?>? They will be able to sign in again." data-bh-confirm-reason="1" data-bh-confirm-password="1" data-bh-confirm-button="Unblock">
                                <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                                <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                                <input type="hidden" name="action" value="unblock">
                                <button type="submit"><i class="fa-solid fa-lock-open"></i> Unblock</button>
                              </form>
                            <?php endif; ?>
                          <?php endif; ?>
                          <?php if ($rowCanDelete): ?>
                            <div class="bh-menu-divider"></div>
                            <form method="post" action="../php/account_delete_direct.php" enctype="multipart/form-data" data-bh-confirm-title="Delete Account" data-bh-confirm="Permanently delete <?php echo htmlspecialchars($acctLabel); ?>? This cannot be undone." data-bh-confirm-reason="1" data-bh-confirm-proof="1" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Delete Permanently">
                              <input type="hidden" name="id_number" value="<?php echo htmlspecialchars($a['id_number']); ?>">
                              <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectBack); ?>">
                              <button type="submit" class="bh-menu-danger"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                          <?php elseif ($rowCanRequestDelete): ?>
                            <div class="bh-menu-divider"></div>
                            <button type="button" class="bh-menu-danger" onclick="bhOpenRequest('<?php echo htmlspecialchars($a['id_number'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['firstname'] . ' ' . $a['lastname'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($a['username'], ENT_QUOTES); ?>')"><i class="fa-solid fa-trash-can"></i> Request Delete</button>
                          <?php endif; ?>
                          <?php if ($a['role'] === 'admin'): ?>
                            <div class="bh-menu-divider"></div>
                            <span style="display:block;padding:0.45rem 0.75rem;font-size:0.72rem;color:var(--bh-text-muted);line-height:1.35;"><?php echo $rowCanChangeRole ? 'Administrator accounts are view only, apart from changing their role. Ask a Super Admin for other changes.' : 'Administrator accounts are view only. Ask a Super Admin to make changes.'; ?></span>
                          <?php elseif ($rowIsBlocked): ?>
                            <div class="bh-menu-divider"></div>
                            <span style="display:block;padding:0.45rem 0.75rem;font-size:0.72rem;color:var(--bh-text-muted);line-height:1.35;"><?php echo $rowCanDelete ? 'This account is blocked. It can be unblocked or deleted; unblock it to make any other change.' : 'This account is blocked. Unblock it to make any other change.'; ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php else: ?>
                      <em style="color:var(--bh-text-muted);font-size:0.8rem;">No actions available</em>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($accounts)): ?><tr><td colspan="7">No accounts match this filter.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="editAccountModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseEdit()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3 id="editAccountTitle">Edit Account</h3>
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

  <!-- View Details: read-only mirror of the Edit form above, so an Admin can
       see every field on a co-admin they are not allowed to change, and the
       same fields on a customer. No inputs, no action buttons. -->
  <div class="bh-modal-backdrop" id="viewAccountModal">
    <div class="bh-modal bh-modal-lg">
      <button type="button" class="bh-modal-close" onclick="bhCloseView()" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      <h3>Account Details</h3>
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
        <div><label>Date Created</label><p id="viewCreatedAt">—</p></div>
      </div>
      <div class="bh-modal-actions">
        <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseView()">Close</button>
      </div>
    </div>
  </div>

  <div class="bh-modal-backdrop" id="bhRequestModal">
    <div class="bh-modal">
      <h3>Request Account Deletion</h3>
      <p><strong>Target Account:</strong> <span id="bhReqName"></span> (<span id="bhReqUsername"></span>)</p>
      <!-- This modal already collects its own reason, so the shared dialog is
           left to do the identity check only. The proof file posts as
           action_proof, the same field name every other critical action uses. -->
      <form method="post" action="../php/delete_request_submit.php" id="bhRequestForm" enctype="multipart/form-data" data-bh-confirm-title="Submit Deletion Request" data-bh-confirm="" data-bh-confirm-password="1" data-bh-confirm-danger="1" data-bh-confirm-button="Submit Request">
        <input type="hidden" name="id_number" id="bhReqId">
        <label for="bhReqReason">Reason for deletion <span style="color:var(--bh-danger);">*</span></label>
        <textarea name="reason" id="bhReqReason" rows="4" maxlength="500" placeholder="Enter reason..." required></textarea>
        <div style="display:flex;justify-content:space-between;gap:0.5rem;">
          <div id="bhReqReason-error" class="error-message"></div>
          <span id="bhReqReasonCount" style="font-size:0.72rem;color:var(--bh-text-muted);white-space:nowrap;">0/500</span>
        </div>
        <label for="bhReqProof" style="margin-top:0.75rem;">Supporting Proof <span style="color:var(--bh-danger);">*</span></label>
        <input type="file" name="action_proof" id="bhReqProof" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required>
        <div style="font-size:0.72rem;color:var(--bh-text-muted);margin-top:2px;">Required. JPG, PNG, GIF, WEBP or PDF. Maximum 5 MB.</div>
        <div id="bhReqProof-error" class="error-message"></div>
        <div class="bh-modal-actions">
          <button type="button" class="bh-btn bh-btn-secondary" onclick="bhCloseRequest()">Cancel</button>
          <button type="submit" class="bh-btn bh-btn-danger">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (has_privilege($me, 'reset_passwords')): ?>
  <!-- Reset Password ("Reset Passwords" add-on). Same modal as the Super Admin
       console; account_reset_password.php limits an Admin to customers. -->
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
  <?php endif; ?>

  <?php if (has_privilege($me, 'change_account_roles')): ?>
  <!-- Change Role ("Change Account Roles" add-on). Same modal as the Super
       Admin console, limited to User / Customer and Administrator. -->
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
              <option value="customer">User / Customer</option>
              <option value="admin">Administrator</option>
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
  <?php endif; ?>

  <script>
    // --- Reset Password / Change Role (add-on privileges) -------------------
    // Mirrors the Super Admin console. The server re-checks everything.
    window.BH_ROLE_PRIVILEGES = <?php echo bh_role_privileges_json(['admin', 'customer']); ?>;
    function bhShowFieldError(id, message) {
      var input = document.getElementById(id);
      var box = document.getElementById(id + '-error');
      if (box) { box.textContent = message; box.classList.toggle('show', !!message); }
      if (input) { input.classList.toggle('bh-field-error', !!message); }
      return !message;
    }
    function bhClearFieldError(id) { return bhShowFieldError(id, ''); }

    function bhRenderRolePrivileges(selectId, listId, captionId) {
      var select = document.getElementById(selectId);
      var list = document.getElementById(listId);
      if (!select || !list) return;
      var role = select.value;
      var caption = document.getElementById(captionId);
      if (caption) { caption.textContent = role ? (BH_ROLE_LABELS[role] || role) : '—'; }
      var privileges = (window.BH_ROLE_PRIVILEGES && window.BH_ROLE_PRIVILEGES[role]) || [];
      list.innerHTML = '';
      if (!role) { list.className = 'bh-privilege-list-empty'; list.textContent = 'Select a role to see the privileges it carries.'; return; }
      if (!privileges.length) { list.className = 'bh-privilege-list-empty'; list.textContent = 'This role carries no administrative privileges.'; return; }
      list.className = 'bh-privilege-list';
      privileges.forEach(function (label) { var li = document.createElement('li'); li.textContent = label; list.appendChild(li); });
    }

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
      var modal = document.getElementById('resetPasswordModal');
      if (!modal) return;
      modal.classList.remove('open');
      document.getElementById('rpPassword').value = '';
      document.getElementById('rpConfirmPassword').value = '';
    }
    // Same rules as bh_validate_password_strength() on the server.
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
    if (document.getElementById('resetPasswordModal')) {
      document.getElementById('rpPassword').addEventListener('input', function () {
        bhValidateResetPassword(true);
        if (document.getElementById('rpConfirmPassword').value !== '') { bhValidateResetMatch(true); }
      });
      document.getElementById('rpConfirmPassword').addEventListener('input', function () { bhValidateResetMatch(true); });
      document.getElementById('resetPasswordForm').bhValidate = function () {
        var ok = bhValidateResetPassword(false);
        ok = bhValidateResetMatch(false) && ok;
        return ok;
      };
      document.getElementById('resetPasswordModal').addEventListener('click', function (e) {
        if (e.target === this) { bhCloseResetPassword(); }
      });
    }

    function bhOpenChangeRole(btn) {
      bhCloseAllMenus();
      var a = JSON.parse(btn.closest('tr').getAttribute('data-edit'));
      var select = document.getElementById('crNewRole');
      document.getElementById('crId').value = a.id_number;
      document.getElementById('crName').textContent = a.firstname + ' ' + a.lastname;
      document.getElementById('crUsername').textContent = a.username;
      document.getElementById('crCurrentRole').textContent = BH_ROLE_LABELS[a.role] || a.role;
      select.value = '';
      // The role the account already holds is not offered.
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
      var modal = document.getElementById('changeRoleModal');
      if (modal) { modal.classList.remove('open'); }
    }
    if (document.getElementById('changeRoleModal')) {
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
    }
  </script>

  <script src="../js/bh-dropdown.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-dropdown.js'); ?>"></script>
  <script>
    function bhToggleNotif() {
      document.getElementById('notifDropdown').classList.toggle('open');
    }
    document.addEventListener('click', function (e) {
      var notifWrap = document.querySelector('.bh-notif-wrap');
      var notifDd = document.getElementById('notifDropdown');
      if (notifWrap && notifDd && !notifWrap.contains(e.target)) { notifDd.classList.remove('open'); }
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

    function bhSetText(id, value) {
      var el = document.getElementById(id);
      if (el) { el.textContent = (value === null || value === undefined || value === '') ? '—' : value; }
    }

    var BH_ROLE_LABELS = { admin: 'Administrator', super_admin: 'Super Administrator', customer: 'Customer' };

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
      bhSetText('viewRole', BH_ROLE_LABELS[a.role] || a.role);
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

    function bhOpenRequest(id, name, username) {
      bhCloseAllMenus();
      document.getElementById('bhReqId').value = id;
      document.getElementById('bhReqName').textContent = name;
      document.getElementById('bhReqUsername').textContent = username;
      document.getElementById('bhReqReason').value = '';
      document.getElementById('bhReqProof').value = '';
      document.getElementById('bhReqReasonCount').textContent = '0/500';
      bhReqSetError('bhReqReason', '');
      bhReqSetError('bhReqProof', '');
      document.getElementById('bhRequestForm').setAttribute('data-bh-confirm',
        'Submit a deletion request for ' + name + ' (' + username + ')? A Super Admin will review it before the account is removed.');
      document.getElementById('bhRequestModal').classList.add('open');
    }
    function bhCloseRequest() {
      document.getElementById('bhRequestModal').classList.remove('open');
    }

    // Live validation for the deletion request, matching what
    // bh_validate_action_reason() and bh_store_action_proof() enforce server-side.
    function bhReqSetError(id, message) {
      var input = document.getElementById(id);
      var box = document.getElementById(id + '-error');
      if (box) { box.textContent = message; box.classList.toggle('show', !!message); }
      if (input) { input.classList.toggle('bh-field-error', !!message); }
      return !message;
    }
    function bhValidateReqReason(live) {
      var value = document.getElementById('bhReqReason').value.trim();
      if (value === '') { return live ? bhReqSetError('bhReqReason', '') : bhReqSetError('bhReqReason', 'A reason is required for this action.'); }
      if (value.length < 5) { return bhReqSetError('bhReqReason', 'Please give a reason of at least 5 characters.'); }
      if (value.length > 500) { return bhReqSetError('bhReqReason', 'The reason must not exceed 500 characters.'); }
      return bhReqSetError('bhReqReason', '');
    }
    function bhValidateReqProof() {
      var input = document.getElementById('bhReqProof');
      var file = input.files && input.files[0];
      // Deletion requests require a proof document — matching
      // bh_store_action_proof() / bh_actions_requiring_proof() on the server.
      if (!file) { return bhReqSetError('bhReqProof', 'A supporting proof document is required for this action.'); }
      var name = file.name || '';
      var dot = name.lastIndexOf('.');
      var extension = dot > -1 ? name.substring(dot + 1).toLowerCase() : '';
      if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'].indexOf(extension) === -1) {
        return bhReqSetError('bhReqProof', 'Only JPG, PNG, GIF, WEBP or PDF files may be attached as proof.');
      }
      if (file.size <= 0) { return bhReqSetError('bhReqProof', 'The proof file is empty.'); }
      if (file.size > 5 * 1024 * 1024) { return bhReqSetError('bhReqProof', 'The proof file is too large. Maximum size is 5 MB.'); }
      return bhReqSetError('bhReqProof', '');
    }
    document.getElementById('bhReqReason').addEventListener('input', function () {
      document.getElementById('bhReqReasonCount').textContent = this.value.trim().length + '/500';
      bhValidateReqReason(true);
    });
    document.getElementById('bhReqReason').addEventListener('blur', function () { bhValidateReqReason(true); });
    document.getElementById('bhReqProof').addEventListener('change', function () { bhValidateReqProof(); });
    // Checked before the shared dialog asks for the Admin's password.
    document.getElementById('bhRequestForm').bhValidate = function () {
      var ok = bhValidateReqReason(false);
      ok = bhValidateReqProof() && ok;
      return ok;
    };

    (function () {
      var form = document.getElementById('accountFilters');
      var tableBody = document.getElementById('accountsTableBody');
      var searchTimer = null;
      var requestController = null;

      function refreshAccounts() {
        var params = new URLSearchParams(new FormData(form));
        params.set('partial', '1');
        if (requestController) requestController.abort();
        requestController = new AbortController();
        tableBody.style.opacity = '0.55';

        fetch('admin-accounts.php?' + params.toString(), {
          headers: { 'Accept': 'text/html' },
          signal: requestController.signal
        })
          .then(function (response) { return response.text(); })
          .then(function (html) {
            var parsed = new DOMParser().parseFromString(html, 'text/html');
            var nextBody = parsed.getElementById('accountsTableBody');
            if (nextBody) tableBody.innerHTML = nextBody.innerHTML;
          })
          .catch(function (error) {
            if (error.name !== 'AbortError') console.error('Unable to refresh accounts:', error);
          })
          .finally(function () { tableBody.style.opacity = ''; });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        refreshAccounts();
      });
      form.elements.role_filter.addEventListener('change', refreshAccounts);
      form.elements.search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refreshAccounts, 250);
      });
      document.getElementById('resetAccountFilters').addEventListener('click', function () {
        form.reset();
        refreshAccounts();
      });
    })();
  </script>
  <!-- The approved email domains, straight from php/auth_helpers.php, so the live check and the server rule are always the same list. -->
  <script>window.BH_APPROVED_EMAIL_DOMAINS = <?php echo bh_approved_email_domains_json(); ?>;</script>
  <!-- The shared validation rules (the front-end mirror of the bh_validate_* helpers) must load before the edit-form validator that uses them. -->
  <script src="../js/bh-validation-rules.js?v=<?php echo @filemtime(__DIR__ . '/../js/bh-validation-rules.js'); ?>"></script>
  <script src="../js/toggle-visibility.js?v=<?php echo @filemtime(__DIR__ . '/../js/toggle-visibility.js'); ?>"></script>
  <script src="../js/user-profile-validation.js?v=<?php echo @filemtime(__DIR__ . '/../js/user-profile-validation.js'); ?>"></script>
  <script src="../js/security-confirm.js?v=<?php echo @filemtime(__DIR__ . '/../js/security-confirm.js'); ?>"></script>
</body>
</html>
