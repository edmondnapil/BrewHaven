<?php
// Shared role/session/privilege/audit-log helpers for Brew Haven.
// Every protected page/endpoint should require_once this file instead of config.php directly.
require_once __DIR__ . '/config.php';

function ensure_session_started() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        bh_harden_session_cookie();
        session_start();
    }
}

// The session cookie is never readable from JavaScript and is not sent on
// cross-site form posts (SameSite=Lax), which blocks a page on another site
// from submitting actions with a signed-in user's session.
function bh_harden_session_cookie() {
    if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }
    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $params['lifetime'],
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function current_user() {
    ensure_session_started();
    if (!isset($_SESSION['user_id_number'])) {
        return null;
    }
    return [
        'id_number'   => $_SESSION['user_id_number'],
        'username'    => $_SESSION['username']    ?? null,
        'firstname'   => $_SESSION['firstname']   ?? null,
        'lastname'    => $_SESSION['lastname']    ?? null,
        'role'        => $_SESSION['role']        ?? 'customer',
        'employee_id' => $_SESSION['employee_id'] ?? null,
    ];
}

// ---------------------------------------------------------------------------
// Single Active Super Admin Session Management
// Only ONE Super Admin account may be active at any given time.
// ---------------------------------------------------------------------------
if (!defined('BH_SUPER_ADMIN_SESSION_TIMEOUT')) {
    define('BH_SUPER_ADMIN_SESSION_TIMEOUT', 900); // 15 minutes of inactivity
}
if (!defined('BH_INACTIVITY_TIMEOUT')) {
    define('BH_INACTIVITY_TIMEOUT', 300); // 5 minutes (300 seconds) of inactivity
}

function bh_mask_email_address($email) {
    if (empty($email) || strpos($email, '@') === false) return '***';
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = substr($user, 0, 1) . '***';
    } else {
        $maskedUser = substr($user, 0, 1) . str_repeat('*', max(3, $len - 2)) . substr($user, -1);
    }
    return $maskedUser . '@' . $domain;
}

function bh_mask_id_number($id) {
    if (empty($id)) return '****';
    $id = trim($id);
    $len = strlen($id);
    if ($len <= 4) {
        return substr($id, 0, 1) . str_repeat('*', max(1, $len - 1));
    }
    if (strpos($id, '-') !== false) {
        $parts = explode('-', $id, 2);
        return $parts[0] . '-' . str_repeat('*', strlen($parts[1]));
    }
    return substr($id, 0, 4) . str_repeat('*', max(1, $len - 4));
}

function bh_mask_username($username) {
    if (empty($username)) return '****';
    $username = trim($username);
    $len = strlen($username);
    if ($len <= 4) {
        return substr($username, 0, 1) . str_repeat('*', max(1, $len - 2)) . ($len > 1 ? substr($username, -1) : '');
    }
    return substr($username, 0, 2) . '****' . substr($username, -2);
}

function bh_clean_expired_super_admin_session() {
    global $conn;
    $timeout = (int)BH_SUPER_ADMIN_SESSION_TIMEOUT;
    $stmt = mysqli_prepare($conn, "DELETE FROM active_super_admin_session WHERE last_activity < (NOW() - INTERVAL ? SECOND)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $timeout);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function bh_get_active_super_admin_session() {
    global $conn;
    bh_clean_expired_super_admin_session();
    $res = mysqli_query($conn, "SELECT id, user_id_number, session_id, last_activity FROM active_super_admin_session WHERE id = 1 LIMIT 1");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return $row;
    }
    return null;
}

function bh_is_active_super_admin($idNumber) {
    $active = bh_get_active_super_admin_session();
    return ($active !== null && $active['user_id_number'] === $idNumber);
}

function bh_claim_super_admin_session($idNumber, $sessionId) {
    global $conn;
    $timeout = (int)BH_SUPER_ADMIN_SESSION_TIMEOUT;

    mysqli_begin_transaction($conn);
    try {
        // 1. Purge stale sessions older than inactivity timeout
        $cleanStmt = mysqli_prepare($conn, "DELETE FROM active_super_admin_session WHERE last_activity < (NOW() - INTERVAL ? SECOND)");
        if ($cleanStmt) {
            mysqli_stmt_bind_param($cleanStmt, 'i', $timeout);
            mysqli_stmt_execute($cleanStmt);
            mysqli_stmt_close($cleanStmt);
        }

        // 2. Lock the single active session row if present (id = 1)
        $checkStmt = mysqli_prepare($conn, "SELECT user_id_number, session_id, last_activity FROM active_super_admin_session WHERE id = 1 FOR UPDATE");
        mysqli_stmt_execute($checkStmt);
        $res = mysqli_stmt_get_result($checkStmt);
        $existing = mysqli_fetch_assoc($res);
        mysqli_stmt_close($checkStmt);

        if ($existing) {
            if ($existing['user_id_number'] !== $idNumber) {
                // Another Super Admin currently has an active session!
                mysqli_rollback($conn);
                return ['success' => false, 'reason' => 'another_super_admin_active'];
            }

            // Same Super Admin re-authenticating (e.g. reopened tab or refreshed credentials)
            $updateStmt = mysqli_prepare($conn, "UPDATE active_super_admin_session SET session_id = ?, last_activity = NOW() WHERE id = 1");
            mysqli_stmt_bind_param($updateStmt, 's', $sessionId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            mysqli_commit($conn);
            return ['success' => true, 'reason' => 'reclaimed'];
        }

        // 3. No active Super Admin session exists: insert the single active slot (id = 1)
        $insertStmt = mysqli_prepare($conn, "INSERT INTO active_super_admin_session (id, user_id_number, session_id, last_activity) VALUES (1, ?, ?, NOW())");
        mysqli_stmt_bind_param($insertStmt, 'ss', $idNumber, $sessionId);
        $ok = mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);

        if (!$ok) {
            mysqli_rollback($conn);
            return ['success' => false, 'reason' => 'insert_failed'];
        }

        mysqli_commit($conn);
        return ['success' => true, 'reason' => 'claimed'];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'reason' => 'error'];
    }
}

function bh_validate_super_admin_session($user) {
    global $conn;
    ensure_session_started();
    $currSessionId = session_id();
    $timeout = (int)BH_SUPER_ADMIN_SESSION_TIMEOUT;

    $stmt = mysqli_prepare($conn, "SELECT user_id_number, session_id, last_activity FROM active_super_admin_session WHERE id = 1 AND user_id_number = ? AND session_id = ? AND last_activity >= (NOW() - INTERVAL ? SECOND)");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'ssi', $user['id_number'], $currSessionId, $timeout);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $valid = (mysqli_num_rows($res) > 0);
    mysqli_stmt_close($stmt);

    if ($valid) {
        // Touch last_activity to extend active session
        $touchStmt = mysqli_prepare($conn, "UPDATE active_super_admin_session SET last_activity = NOW() WHERE id = 1 AND session_id = ?");
        if ($touchStmt) {
            mysqli_stmt_bind_param($touchStmt, 's', $currSessionId);
            mysqli_stmt_execute($touchStmt);
            mysqli_stmt_close($touchStmt);
        }
        return true;
    }

    return false;
}

function bh_clear_active_super_admin_session($idNumber = null, $sessionId = null) {
    global $conn;
    if ($idNumber !== null && $sessionId !== null) {
        $stmt = mysqli_prepare($conn, "DELETE FROM active_super_admin_session WHERE id = 1 AND user_id_number = ? AND session_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $idNumber, $sessionId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } elseif ($idNumber !== null) {
        $stmt = mysqli_prepare($conn, "DELETE FROM active_super_admin_session WHERE id = 1 AND user_id_number = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $idNumber);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } else {
        mysqli_query($conn, "DELETE FROM active_super_admin_session WHERE id = 1");
    }
}

// ---------------------------------------------------------------------------
// Single Active Super Admin (stored account status)
// ---------------------------------------------------------------------------
// users.account_status is the source of truth: at most one Super Admin row is
// 'active' (also enforced by the uniq_single_active_super_admin index). A
// Super Admin created or promoted while another is active is stored as
// 'inactive' and waits. When the Active Super Admin logs out, the waiting one
// takes over: the outgoing account becomes 'blocked', the incoming 'active'.

// The Active Super Admin row, or null.
function bh_get_active_super_admin_account() {
    global $conn;
    $res = mysqli_query($conn, "SELECT id_number, account_status FROM users WHERE role = 'super_admin' AND account_status = 'active' ORDER BY created_at ASC LIMIT 1");
    return ($res && $row = mysqli_fetch_assoc($res)) ? $row : null;
}

// Runs the handover. $outgoingId: the Super Admin logging out (null = whoever
// is currently Active, if anyone). $incomingId: a specific waiting Super Admin
// (null = the one that has been waiting longest). Returns the activated
// id_number, or null when nothing changed.
function bh_super_admin_handover($outgoingId, $incomingId = null, $trigger = 'logout') {
    global $conn;

    mysqli_begin_transaction($conn);
    try {
        if ($incomingId !== null) {
            $stmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE id_number = ? AND role = 'super_admin' AND account_status = 'inactive' AND approval_status = 'approved' FOR UPDATE");
            mysqli_stmt_bind_param($stmt, 's', $incomingId);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id_number FROM users WHERE role = 'super_admin' AND account_status = 'inactive' AND approval_status = 'approved' ORDER BY created_at ASC, id_number ASC LIMIT 1 FOR UPDATE");
        }
        mysqli_stmt_execute($stmt);
        $incoming = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$incoming) {
            mysqli_rollback($conn);
            return null;
        }

        $activeRes = mysqli_query($conn, "SELECT id_number FROM users WHERE role = 'super_admin' AND account_status = 'active' FOR UPDATE");
        $activeIds = [];
        while ($activeRes && $row = mysqli_fetch_assoc($activeRes)) { $activeIds[] = $row['id_number']; }

        // Logging out as an account that is no longer the Active Super Admin
        // must not hand anything over.
        if ($outgoingId !== null && !in_array($outgoingId, $activeIds, true)) {
            mysqli_rollback($conn);
            return null;
        }

        // Block the outgoing Super Admin first so the single-active index
        // never sees two Active rows.
        $blockStmt = mysqli_prepare($conn, "UPDATE users SET account_status = 'blocked' WHERE id_number = ?");
        foreach ($activeIds as $activeId) {
            mysqli_stmt_bind_param($blockStmt, 's', $activeId);
            if (!mysqli_stmt_execute($blockStmt)) { throw new Exception('block failed'); }
        }
        mysqli_stmt_close($blockStmt);

        $activateStmt = mysqli_prepare($conn, "UPDATE users SET account_status = 'active' WHERE id_number = ? AND account_status = 'inactive'");
        mysqli_stmt_bind_param($activateStmt, 's', $incoming['id_number']);
        if (!mysqli_stmt_execute($activateStmt) || mysqli_stmt_affected_rows($activateStmt) !== 1) {
            throw new Exception('activate failed');
        }
        mysqli_stmt_close($activateStmt);

        mysqli_query($conn, "DELETE FROM active_super_admin_session");
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return null;
    }

    $reason = 'Automatic Super Admin handover (' . $trigger . ').';
    foreach ($activeIds as $activeId) {
        bh_log_account_action('super_admin_handover_blocked', $activeId, $activeId, [
            'details'        => 'replaced_by=' . $incoming['id_number'],
            'reason'         => $reason,
            'previous_state' => 'status=active',
            'new_state'      => 'status=blocked',
        ]);
    }
    bh_log_account_action('super_admin_handover_activated', $activeIds[0] ?? $incoming['id_number'], $incoming['id_number'], [
        'details'        => 'replaces=' . ($activeIds ? implode(',', $activeIds) : 'none'),
        'reason'         => $reason,
        'previous_state' => 'status=inactive',
        'new_state'      => 'status=active',
    ]);
    return $incoming['id_number'];
}

// Roles a blocked Super Admin may be given when it is unblocked (value => label).
// Super Admin is deliberately absent: only one Super Admin is allowed.
function bh_unblock_super_admin_role_options() {
    return ['admin' => 'Admin', 'customer' => 'User / Customer'];
}

function bh_unblock_super_admin_roles() {
    return array_keys(bh_unblock_super_admin_role_options());
}

// Inactive accounts may only go through the first-login setup. A waiting
// (Inactive) Super Admin may not sign in at all until the handover.
function bh_inactive_account_may_setup($row) {
    return ($row['account_status'] ?? '') === 'inactive'
        && ($row['role'] ?? '') !== 'super_admin'
        && bh_in_first_login_setup($row);
}


function role_home_page($role) {
    switch ($role) {
        case 'super_admin': return 'super-admin-dashboard.php';
        case 'admin':        return 'admin-dashboard.php';
        default:              return 'dashboard.php';
    }
}

// role_home_page() (and 'login.php') return bare filenames that only resolve
// correctly when the redirecting script itself lives in html/. auth_helpers
// is require_once'd from both html/*.php pages and php/*.php endpoints, so
// redirects issued from a php/ endpoint need an ../html/ prefix instead, or
// the Location header points at a nonexistent php/<page>.php and 404s.
function bh_page_url($page) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -5) === '/html') {
        return $page;
    }
    return '../html/' . $page;
}

// Appends a query param to a URL that may already contain one (e.g. a
// redirect_to like "admin-accounts.php?view=employees") without producing a
// malformed "?view=employees?msg=updated" string.
function bh_redirect_with($url, $key, $value) {
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $sep . $key . '=' . urlencode($value);
}

// True while an account still owes the first-login setup (OTP -> complete profile ->
// change password -> security questions). first_login is 1, 2, or 3 for
// accounts created from the Super Admin console; every self-registered customer
// and every finished account sits at 0, so this never catches them.
// $row needs first_login.
function bh_in_first_login_setup($row) {
    $firstLogin = (int)($row['first_login'] ?? 0);
    return $firstLogin === 1 || $firstLogin === 2 || $firstLogin === 3;
}

// Guard for the shared first-login setup screens (complete-profile,
// change-password-setup and their save endpoints). Any account qualifies only
// while it is still inside the setup. Everyone else is bounced to their own dashboard.
function require_first_login_setup($user) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT first_login FROM users WHERE id_number = ?");
    mysqli_stmt_bind_param($stmt, 's', $user['id_number']);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row || !bh_in_first_login_setup($row)) {
        header('Location: ' . bh_page_url(role_home_page($user['role'])));
        exit;
    }
}

// Redirects unauthenticated users to login. When $recheckStatus, re-reads the
// account's live status from the DB so a mid-session block takes effect on
// the very next request instead of only at the next login.
function require_login($recheckStatus = true) {
    global $conn;
    ensure_session_started();

    $isAjax = bh_request_is_ajax();
    $user = current_user();
    if (!$user) {
        bh_require_login_fail($isAjax, 'login.php', 'You must be signed in to continue.', false);
    }

    // 5-minute inactivity timeout enforcement (both web requests and AJAX)
    $now = time();

    if (isset($_SESSION['last_activity'])) {
        if (($now - (int)$_SESSION['last_activity']) > BH_INACTIVITY_TIMEOUT) {
            $expiredUserId = $user['id_number'] ?? null;
            if ($expiredUserId) {
                if (($user['role'] ?? '') === 'super_admin') {
                    bh_clear_active_super_admin_session($expiredUserId, session_id());
                    // An inactivity auto-logout is a logout: a waiting Super Admin takes over.
                    bh_super_admin_handover($expiredUserId, null, 'inactivity logout');
                }
                log_security_event('session_timeout_inactivity', $expiredUserId, $expiredUserId, 'inactivity timeout exceeded ' . BH_INACTIVITY_TIMEOUT . 's');
            }
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();

            bh_require_login_fail($isAjax, 'login.php?msg=inactivity',
                'Session Expired: You have been logged out because of 5 minutes of inactivity.', true);
        }
    }
    // Background/AJAX requests must NOT reset the timer; only actual user activity resets it
    if (!$isAjax) {
        $_SESSION['last_activity'] = $now;
    }

    if ($user['role'] === 'super_admin') {
        if (!bh_validate_super_admin_session($user)) {
            log_security_event('session_terminated_super_admin_expired', $user['id_number'], $user['id_number'], 'session expired or superseded');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
            bh_require_login_fail($isAjax, 'login.php?msg=session_expired',
                'Your session has expired. Please sign in again.', true);
        }
    }

    if ($recheckStatus) {
        $stmt = mysqli_prepare($conn, "SELECT approval_status, account_status, role, first_login, firstname, lastname, auth_question1 FROM users WHERE id_number = ?");
        mysqli_stmt_bind_param($stmt, 's', $user['id_number']);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        $statusAllowed = $row && ($row['account_status'] === 'active'
            || ($row['account_status'] === 'inactive' && bh_inactive_account_may_setup($row)));
        // Pages authorize by the role stored in the session at sign-in. If the
        // account's role has since been changed, that session must not keep
        // the old role's access: it ends, and the next sign-in picks up the
        // new role.
        $roleChanged = $row && $row['role'] !== $user['role'];
        if (!$row || $row['approval_status'] !== 'approved' || !$statusAllowed || $roleChanged) {
            log_security_event('session_terminated_status_change', $user['id_number'], $user['id_number'],
                $roleChanged ? 'role changed from ' . $user['role'] . ' to ' . $row['role'] : 'account is no longer active/approved');
            $_SESSION = [];
            session_destroy();
            bh_require_login_fail($isAjax, 'login.php?msg=account_status_changed',
                'Your account status changed. Please sign in again.', true);
        }

        $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $inFirstLoginSetup = bh_in_first_login_setup($row);

        if ($inFirstLoginSetup) {
            // Check if OTP verified
            $otpVerified = !empty($_SESSION['first_login_otp']['otp_verified']) &&
                           (($_SESSION['first_login_otp']['user_id'] ?? '') === $user['id_number']) &&
                           (($_SESSION['first_login_otp']['purpose'] ?? '') === 'FIRST_LOGIN');

            $otpExempt = ['first-login-otp.php', 'logout.php'];
            if (!$otpVerified) {
                if (!in_array($currentScript, $otpExempt, true)) {
                    bh_require_login_fail($isAjax, 'first-login-otp.php',
                        'Please complete first-login verification first.', false);
                }
            } else {
                $firstLoginVal = (int)($row['first_login'] ?? 0);
                $exemptProfileScripts  = ['complete-profile.php', 'save_initial_profile.php', 'logout.php'];
                $exemptPasswordScripts = ['change-password-setup.php', 'save_initial_password.php', 'logout.php'];
                $exemptSecurityScripts = ['setup-security-questions.php', 'save_security_questions.php', 'logout.php'];

                if ($firstLoginVal === 1 || empty($row['firstname']) || empty($row['lastname'])) {
                    if (!in_array($currentScript, $exemptProfileScripts, true)) {
                        bh_require_login_fail($isAjax, 'complete-profile.php',
                            'Please complete your profile first.', false);
                    }
                } elseif ($firstLoginVal === 2) {
                    if (!in_array($currentScript, $exemptPasswordScripts, true)) {
                        bh_require_login_fail($isAjax, 'change-password-setup.php',
                            'Please set your password first.', false);
                    }
                } elseif ($firstLoginVal === 3 || empty($row['auth_question1'])) {
                    if (!in_array($currentScript, $exemptSecurityScripts, true)) {
                        bh_require_login_fail($isAjax, 'setup-security-questions.php',
                            'Please set your security questions first.', false);
                    }
                }
            }
        }
    }
    return $user;
}

// True for XHR / fetch requests that expect JSON rather than an HTML redirect.
function bh_request_is_ajax() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
        || (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
        || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'empty');
}

// Ends require_login() with either a JSON body (AJAX) or an HTML redirect.
function bh_require_login_fail($isAjax, $pageWithQuery, $message, $isTimeout) {
    if (strpos($pageWithQuery, '?') !== false) {
        $parts = explode('?', $pageWithQuery, 2);
        $redirect = bh_page_url($parts[0]) . '?' . $parts[1];
    } else {
        $redirect = bh_page_url($pageWithQuery);
    }
    if ($isAjax) {
        header('Content-Type: application/json', true, 401);
        echo json_encode([
            'success'  => false,
            'timeout'  => (bool)$isTimeout,
            'message'  => $message,
            'redirect' => $redirect,
        ]);
        exit;
    }
    header('Location: ' . $redirect);
    exit;
}

// $allowedRoles e.g. ['admin','super_admin']. Wrong role bounces to the
// caller's own dashboard rather than exposing a blank 403.
function require_role(array $allowedRoles, $recheckStatus = true) {
    $user = require_login($recheckStatus);
    if (!in_array($user['role'], $allowedRoles, true)) {
        header('Location: ' . bh_page_url(role_home_page($user['role'])));
        exit;
    }
    return $user;
}

// Super Admin always passes implicitly. Admin passes only if granted the key.
function has_privilege($user, $key) {
    global $conn;
    if ($user['role'] === 'super_admin') {
        return true;
    }
    if ($user['role'] !== 'admin') {
        return false;
    }
    $privilegeKeys = $key === 'view_logs'
        ? ['view_logs', 'view_activity_logs', 'view_login_logs']
        : [$key];
    $placeholders = implode(',', array_fill(0, count($privilegeKeys), '?'));
    $sql = "SELECT 1 FROM admin_privileges WHERE admin_id_number = ? AND privilege_key IN ($placeholders) LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    $bindValues = array_merge([$user['id_number']], $privilegeKeys);
    $bindTypes = 's' . str_repeat('s', count($privilegeKeys));
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindValues);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $granted = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $granted;
}

// Allows a specific Super Admin surface to be delegated to an Admin without
// granting the Admin every Super Admin capability.
function require_role_or_privilege(array $allowedRoles, $privilegeKey) {
    $user = require_login();
    if (in_array($user['role'], $allowedRoles, true) || has_privilege($user, $privilegeKey)) {
        return $user;
    }
    header('Location: ' . bh_page_url(role_home_page($user['role'])) . '?error=insufficient_privilege');
    exit;
}

function require_privilege($key) {
    $user = require_login();
    if (!has_privilege($user, $key)) {
        header('Location: ' . bh_page_url(role_home_page($user['role'])) . '?error=insufficient_privilege');
        exit;
    }
    return $user;
}

// Prepared-statement audit log insert. $details must never contain a
// password, a secret-question answer, or a secret-answer hash.
// IP and User-Agent are captured automatically from the request here so every
// call site gets them for free, instead of having to pass them through.
function log_security_event($eventType, $actorId, $targetId, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    $stmt = mysqli_prepare($conn, "INSERT INTO security_logs (event_type, actor_id_number, target_id_number, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $eventType, $actorId, $targetId, $ip, $ua, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// ---------------------------------------------------------------------------
// Sensitive-action re-authentication.
//
// Confirms that whoever is driving the session really is the account holder by
// re-checking the ACTING user's own password (never the target's) against the
// stored hash before a destructive or privilege-affecting action runs.
//
// Returns '' when the action may proceed, or an error code otherwise. It never
// echoes or redirects, so each endpoint keeps its existing redirect/JSON
// contract. Failures are audited; the password itself is never logged.
function bh_confirm_password($user, $password, $action = '', $targetId = null) {
    global $conn;

    // The Confirm Password modal has already verified the password against the
    // stored hash (php/verify_action_password.php) and issued a single-use
    // token for this endpoint. Consuming it here means the action can run only
    // once per verification, however many times the request is replayed.
    $token = (string)($_POST['confirm_token'] ?? '');
    if ($token !== '') {
        return bh_consume_action_confirm_token($user, $token) ? '' : 'confirm_token_invalid';
    }

    // Throttle guessing: 5 failed confirmations in 15 minutes locks further
    // attempts. Reuses security_logs, so there is no new table to maintain.
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM security_logs WHERE event_type = 'action_confirm_failed' AND actor_id_number = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    mysqli_stmt_bind_param($stmt, 's', $user['id_number']);
    mysqli_stmt_execute($stmt);
    $recentFailures = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] ?? 0);
    mysqli_stmt_close($stmt);
    if ($recentFailures >= 5) {
        log_security_event('action_confirm_locked', $user['id_number'], $targetId, 'action=' . $action);
        return 'confirm_locked';
    }

    if ($password === '' || $password === null) {
        return 'confirm_password_required';
    }

    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id_number = ?");
    mysqli_stmt_bind_param($stmt, 's', $user['id_number']);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || !verifyPassword($password, $row['password'])) {
        log_security_event('action_confirm_failed', $user['id_number'], $targetId, 'action=' . $action);
        return 'confirm_password_incorrect';
    }

    return '';
}

if (!defined('BH_ACTION_CONFIRM_TOKEN_TTL')) {
    define('BH_ACTION_CONFIRM_TOKEN_TTL', 120); // seconds between verifying and submitting
}

// Endpoints a confirmation token may be issued for: every account-management
// action that calls bh_confirm_password().
function bh_action_confirm_endpoints() {
    return ['account_approve.php', 'account_update.php', 'account_change_role.php',
            'account_delete_direct.php', 'account_reset_password.php', 'update_privileges.php',
            'admin_create_staff.php', 'delete_request_submit.php', 'delete_request_review.php'];
}

// Issued only after the password has been verified on the server. Bound to the
// user, the session and one endpoint; replaces any earlier unused token.
function bh_issue_action_confirm_token($user, $endpoint) {
    ensure_session_started();
    $token = bin2hex(random_bytes(32));
    $_SESSION['bh_action_confirm'] = [
        'hash'     => hash('sha256', $token),
        'user'     => $user['id_number'],
        'endpoint' => $endpoint,
        'expires'  => time() + BH_ACTION_CONFIRM_TOKEN_TTL,
    ];
    return $token;
}

// Valid once: the stored token is removed whether or not it matches.
function bh_consume_action_confirm_token($user, $token) {
    ensure_session_started();
    $stored = $_SESSION['bh_action_confirm'] ?? null;
    unset($_SESSION['bh_action_confirm']);
    if (!is_array($stored)) {
        return false;
    }
    return hash_equals($stored['hash'], hash('sha256', (string)$token))
        && $stored['user'] === $user['id_number']
        && $stored['endpoint'] === basename($_SERVER['SCRIPT_NAME'] ?? '')
        && time() <= (int)$stored['expires'];
}

// Friendly text for the codes bh_confirm_password() returns. Any other code is
// passed straight through, so existing pages keep showing their own codes
// exactly as they always have.
function bh_confirm_error_message($code) {
    $messages = [
        'confirm_password_required'  => 'Please enter your password to confirm this action.',
        'confirm_password_incorrect' => 'Incorrect password. The action was not performed.',
        'confirm_locked'             => 'Too many incorrect password attempts. Please try again in 15 minutes.',
        'confirm_token_invalid'      => 'Your password confirmation expired or was already used. The action was not performed. Please try again.',
    ];
    return $messages[$code] ?? $code;
}

// Very small, dependency-free User-Agent sniffer good enough for a log table
// display column. Not meant to be a precise device-detection library.
function bh_parse_user_agent($ua) {
    if (empty($ua)) {
        return ['browser' => 'Unknown', 'os' => 'Unknown', 'device' => 'Unknown'];
    }
    $browser = 'Other';
    if (stripos($ua, 'Edg/') !== false) { $browser = 'Edge'; }
    elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) { $browser = 'Opera'; }
    elseif (stripos($ua, 'Chrome') !== false) { $browser = 'Chrome'; }
    elseif (stripos($ua, 'Firefox') !== false) { $browser = 'Firefox'; }
    elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; }

    $os = 'Other';
    if (stripos($ua, 'Windows NT 10') !== false) { $os = 'Windows 10/11'; }
    elseif (stripos($ua, 'Windows') !== false) { $os = 'Windows'; }
    elseif (stripos($ua, 'Mac OS X') !== false) { $os = 'macOS'; }
    elseif (stripos($ua, 'Android') !== false) { $os = 'Android'; }
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) { $os = 'iOS'; }
    elseif (stripos($ua, 'Linux') !== false) { $os = 'Linux'; }

    $device = 'Desktop';
    if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false) { $device = 'Mobile'; }
    if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) { $device = 'Tablet'; }

    return ['browser' => $browser, 'os' => $os, 'device' => $device];
}

// Bell-icon notification count for a Super Admin: pending approvals + pending
// deletion requests. Admin gets the same, scoped to what their privileges let
// them act on.
function bh_notification_count($user) {
    global $conn;
    $count = 0;
    if ($user['role'] === 'super_admin' || has_privilege($user, 'approve_accounts')) {
        $count += (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer' AND approval_status = 'pending'"))['c'] ?? 0);
    }
    if ($user['role'] === 'super_admin' || has_privilege($user, 'manage_deletion_requests')) {
        $count += (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM delete_requests WHERE status = 'pending'"))['c'] ?? 0);
    }
    return $count;
}

function bh_recent_notifications($user, $limit = 6) {
    global $conn;
    $items = [];
    if ($user['role'] === 'super_admin' || has_privilege($user, 'approve_accounts')) {
        $result = mysqli_query($conn, "SELECT id_number, firstname, lastname, role, created_at FROM users WHERE role = 'customer' AND approval_status = 'pending' ORDER BY created_at DESC LIMIT " . (int)$limit);
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'text' => htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . ' is awaiting approval',
                'time' => $row['created_at'],
                'link' => 'user-approvals.php',
            ];
        }
    }
    if ($user['role'] === 'super_admin' || has_privilege($user, 'manage_deletion_requests')) {
        $result = mysqli_query($conn, "SELECT dr.id, u.firstname, u.lastname, dr.created_at FROM delete_requests dr LEFT JOIN users u ON u.id_number = dr.target_id_number WHERE dr.status = 'pending' ORDER BY dr.created_at DESC LIMIT " . (int)$limit);
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'text' => 'Deletion requested for ' . htmlspecialchars(($row['firstname'] ?? 'an account') . ' ' . ($row['lastname'] ?? '')),
                'time' => $row['created_at'],
                'link' => 'super-admin-deletion-requests.php',
            ];
        }
    }
    usort($items, function ($a, $b) { return strtotime($b['time']) <=> strtotime($a['time']); });
    return array_slice($items, 0, $limit);
}

// Shared sequential-ID generator: id_number uses prefix "YYYY-" over the
// id_number column, employee_id uses prefix "EMP-" over the employee_id column.
function generate_next_sequential_id($column, $prefix, $numberLength = 4) {
    global $conn;
    $sql = "SELECT $column FROM users WHERE $column LIKE ? ORDER BY $column DESC LIMIT 1";
    $pattern = $prefix . '%';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $pattern);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $nextNumber = 1;
    if ($row && isset($row[$column])) {
        $existing = $row[$column];
        $escapedPrefix = preg_quote($prefix, '/');
        if (preg_match('/^' . $escapedPrefix . '(\d{' . $numberLength . '})$/', $existing, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }
    }
    $nextId = $prefix . str_pad($nextNumber, $numberLength, '0', STR_PAD_LEFT);

    // Race-condition guard, mirrors the original get_next_id_number.php logic
    $checkStmt = mysqli_prepare($conn, "SELECT $column FROM users WHERE $column = ? LIMIT 1");
    mysqli_stmt_bind_param($checkStmt, 's', $nextId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        $nextNumber++;
        $nextId = $prefix . str_pad($nextNumber, $numberLength, '0', STR_PAD_LEFT);
    }
    mysqli_stmt_close($checkStmt);

    return $nextId;
}

function generate_next_id_number() {
    return generate_next_sequential_id('id_number', date('Y') . '-', 4);
}

function generate_next_employee_id() {
    return generate_next_sequential_id('employee_id', 'EMP-', 3);
}

function generate_next_customer_id() {
    return generate_next_sequential_id('customer_id', date('Y') . '-', 4);
}

// One unified identifier for every account, whatever its role: the id_number
// column, which has always been a single global "YYYY-####" sequence shared by
// Super Admins, Admins and Customers alike. Role — not the ID — determines what
// an account may do. The legacy employee_id / customer_id columns are no longer
// generated or displayed; they are left in place for existing rows only.
function bh_display_id($row) {
    return ['label' => 'ID Number', 'value' => $row['id_number'] ?? null];
}

// Default privilege set granted to a freshly-created Admin account.
function default_admin_privileges() {
    return ['manage_users', 'approve_accounts', 'block_accounts', 'update_user_info', 'manage_products', 'manage_orders'];
}

// Additional Super Admin surfaces that may be delegated to an Admin.
// Order matters: the Manage Privileges modal lays these out three per row.
function default_super_admin_privileges() {
    return ['manage_super_admin_accounts', 'manage_deletion_requests', 'view_logs',
            'change_account_roles', 'reset_passwords'];
}

// ---------------------------------------------------------------------------
// Orders
// ---------------------------------------------------------------------------

// The add-ons a customer may pick. The order forms render from this list and
// the cart endpoints accept nothing else.
function bh_addon_options() {
    return ['Extra Espresso', 'Caramel Syrup', 'Vanilla Syrup', 'Whipped Cream'];
}

function bh_filter_addons($posted) {
    if (!is_array($posted)) {
        return [];
    }
    return array_values(array_intersect(bh_addon_options(), array_map('strval', $posted)));
}

// Order life cycle. Completed and Cancelled are final.
function bh_order_status_transitions() {
    return [
        'Pending'   => ['Preparing', 'Cancelled'],
        'Preparing' => ['Ready', 'Cancelled'],
        'Ready'     => ['Completed', 'Cancelled'],
        'Completed' => [],
        'Cancelled' => [],
    ];
}

function bh_order_can_move($from, $to) {
    return in_array($to, bh_order_status_transitions()[$from] ?? [], true);
}

// Items for a set of orders, grouped by order id, with the product name.
function bh_fetch_order_items(array $orderIds) {
    global $conn;
    $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
    if (!$orderIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt = mysqli_prepare($conn, "SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($placeholders) ORDER BY oi.id ASC");
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($orderIds)), ...$orderIds);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $grouped = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $grouped[(int)$row['order_id']][] = $row;
    }
    mysqli_stmt_close($stmt);
    return $grouped;
}

// One compact line per ordered item, for tables.
function bh_order_items_html(array $items) {
    if (!$items) {
        return '<span style="color:var(--bh-text-muted);">—</span>';
    }
    $lines = [];
    foreach ($items as $it) {
        $custom = $it['size'] . ' · ' . $it['temperature'] . ' · ' . $it['sweetness'];
        if (!empty($it['addons'])) {
            $custom .= ' · + ' . $it['addons'];
        }
        $lines[] = '<div style="margin-bottom:0.25rem;"><strong>' . (int)$it['quantity'] . '× '
            . htmlspecialchars($it['product_name'] ?? ('Product #' . (int)$it['product_id'])) . '</strong>'
            . '<div style="font-size:0.75rem;color:var(--bh-text-muted);">' . htmlspecialchars($custom) . '</div></div>';
    }
    return implode('', $lines);
}

function bh_cart_count() {
    ensure_session_started();
    return (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? count($_SESSION['cart']) : 0;
}

function bh_initials($firstname, $lastname) {
    $a = $firstname !== '' ? strtoupper(substr($firstname, 0, 1)) : '';
    $b = $lastname !== '' ? strtoupper(substr($lastname, 0, 1)) : '';
    return $a . $b;
}

// Deterministic avatar color from an id string, picked from the theme palette.
function bh_avatar_color($seed) {
    $palette = ['#6F4E37', '#7C8B6E', '#B3452A', '#3B2419', '#C98A2C', '#5F7A4F'];
    $hash = crc32((string)$seed);
    return $palette[$hash % count($palette)];
}

// Shared "what badge should this account row show" logic. $row needs
// role, approval_status, account_status, first_login, auth_question1.
function bh_account_status_badge($row) {
    if ($row['account_status'] === 'blocked') {
        return '<span class="bh-badge bh-badge-blocked">Blocked</span>';
    }
    if ($row['approval_status'] === 'pending') {
        return '<span class="bh-badge bh-badge-pending">Pending</span>';
    }
    if ($row['approval_status'] === 'rejected') {
        return '<span class="bh-badge bh-badge-rejected">Rejected</span>';
    }
    // The stored status decides. Only one Super Admin can be stored Active.
    if ($row['account_status'] === 'inactive') {
        return '<span class="bh-badge bh-badge-inactive">Inactive</span>';
    }
    // Rows from before the stored Inactive status existed.
    if ($row['role'] !== 'super_admin' && bh_in_first_login_setup($row)) {
        return '<span class="bh-badge bh-badge-inactive">Inactive</span>';
    }
    return '<span class="bh-badge bh-badge-active">Active</span>';
}

// Pairs login_success/logout security_logs rows per user into Time In / Time
// Out sessions. Pairing is done across the user's FULL event history (not
// pre-filtered by month/date) so a session that started before midnight and
// ended after it is never split into an orphaned half; month/date filters
// are applied afterward against each session's time_in.
// $roleScope: which account roles to include (empty array = no role restriction).
// $selfId: when set, restricts to that one actor (a user viewing their own log).
function bh_build_login_sessions($conn, array $roleScope, $selfId = null, $month = '', $date = '', $search = '') {
    $where = ["l.event_type IN ('login_success','logout')"];
    $params = [];
    $types = '';
    if (!empty($roleScope)) {
        $placeholders = implode(',', array_fill(0, count($roleScope), '?'));
        $where[] = "u.role IN ($placeholders)";
        foreach ($roleScope as $r) { $params[] = $r; $types .= 's'; }
    }
    if ($selfId !== null) {
        $where[] = "l.actor_id_number = ?";
        $params[] = $selfId;
        $types .= 's';
    }
    if ($search !== '') {
        $where[] = "(l.actor_id_number LIKE ? OR u.username LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR u.employee_id LIKE ? OR u.customer_id LIKE ?)";
        $like = '%' . $search . '%';
        for ($i = 0; $i < 5; $i++) { $params[] = $like; $types .= 's'; }
    }
    $whereSql = implode(' AND ', $where);

    $sql = "SELECT l.event_type, l.actor_id_number, l.created_at, u.firstname, u.lastname, u.role, u.employee_id, u.customer_id
            FROM security_logs l JOIN users u ON u.id_number = l.actor_id_number
            WHERE $whereSql ORDER BY l.actor_id_number ASC, l.created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $open = [];
    $sessions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $actor = $row['actor_id_number'];
        if ($row['event_type'] === 'login_success') {
            if (isset($open[$actor])) {
                // Previous session never got a matching logout (session expired / browser closed).
                $sessions[] = $open[$actor] + ['time_out' => null];
            }
            $open[$actor] = [
                'id_number'   => $actor,
                'employee_id' => $row['employee_id'],
                'customer_id' => $row['customer_id'],
                'role'        => $row['role'],
                'firstname'   => $row['firstname'],
                'lastname'    => $row['lastname'],
                'time_in'     => $row['created_at'],
            ];
        } elseif ($row['event_type'] === 'logout' && isset($open[$actor])) {
            $sessions[] = $open[$actor] + ['time_out' => $row['created_at']];
            unset($open[$actor]);
        }
    }
    foreach ($open as $s) { $sessions[] = $s + ['time_out' => null]; }

    if ($month !== '' || $date !== '') {
        $sessions = array_values(array_filter($sessions, function ($s) use ($month, $date) {
            if ($month !== '' && substr($s['time_in'], 0, 7) !== $month) return false;
            if ($date !== '' && substr($s['time_in'], 0, 10) !== $date) return false;
            return true;
        }));
    }

    usort($sessions, function ($a, $b) { return strcmp($b['time_in'], $a['time_in']); });
    return $sessions;
}

// ---------------------------------------------------------------------------
// Shared Registration & First-Login Backend Validation Helpers
// ---------------------------------------------------------------------------

function bh_validate_name($name, $fieldName = 'Name', $required = true) {
    $raw = (string)$name;
    if ($required && trim($raw) === '') {
        return ['valid' => false, 'error' => $fieldName . ' is required.'];
    }
    if (!$required && trim($raw) === '') {
        return ['valid' => true];
    }
    if (preg_match('/^\s/', $raw) || preg_match('/\s$/', $raw)) {
        return ['valid' => false, 'error' => 'Leading or trailing spaces are not allowed in ' . strtolower($fieldName) . '.'];
    }
    if (preg_match('/[0-9]/', $raw)) {
        return ['valid' => false, 'error' => 'Numbers are not allowed in ' . strtolower($fieldName) . '.'];
    }
    if (preg_match('/[^A-Za-zñÑ\s\-\']/u', $raw)) {
        return ['valid' => false, 'error' => 'Special characters are not allowed in ' . strtolower($fieldName) . '.'];
    }
    if (preg_match('/\s{2,}/', $raw)) {
        return ['valid' => false, 'error' => 'Double spaces are not allowed in ' . strtolower($fieldName) . '.'];
    }
    if (preg_match('/([A-Za-zñÑ])\1{2,}/iu', $raw)) {
        return ['valid' => false, 'error' => 'Three identical consecutive letters are not allowed in ' . strtolower($fieldName) . '.'];
    }
    $trimmed = trim($raw);
    if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 50) {
        return ['valid' => false, 'error' => $fieldName . ' must be between 2 and 50 characters.'];
    }
    if (!preg_match('/^[A-ZÑ]/u', $trimmed)) {
        return ['valid' => false, 'error' => 'First letter of ' . strtolower($fieldName) . ' must be uppercase.'];
    }
    return ['valid' => true];
}

function bh_validate_birthdate_and_age($birthDateStr, $ageInput = null) {
    if (empty($birthDateStr)) {
        return ['valid' => false, 'error' => 'Birth date is required.'];
    }
    $dateObj = DateTime::createFromFormat('Y-m-d', $birthDateStr);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDateStr) {
        return ['valid' => false, 'error' => 'Invalid birth date format.'];
    }
    $today = new DateTime('today');
    if ($dateObj > $today) {
        return ['valid' => false, 'error' => 'Birth date cannot be in the future.'];
    }
    $calcAge = $dateObj->diff($today)->y;
    if ($calcAge < 18) {
        return ['valid' => false, 'error' => 'Must be at least 18 years old.'];
    }
    return ['valid' => true, 'calculated_age' => $calcAge];
}

function bh_validate_address($street, $barangay, $city, $province, $zipcode, $country) {
    $street = trim((string)$street);
    $barangay = trim((string)$barangay);
    $city = trim((string)$city);
    $province = trim((string)$province);
    $zipcode = trim((string)$zipcode);
    $country = trim((string)$country);

    if ($street === '' || $barangay === '' || $city === '' || $province === '' || $zipcode === '' || $country === '') {
        return ['valid' => false, 'error' => 'All address fields are required.'];
    }
    if (mb_strlen($street) < 5 || mb_strlen($street) > 100) {
        return ['valid' => false, 'error' => 'Street address must be between 5 and 100 characters.'];
    }
    if (mb_strlen($barangay) < 2 || mb_strlen($barangay) > 50) {
        return ['valid' => false, 'error' => 'Barangay must be between 2 and 50 characters.'];
    }
    if (mb_strlen($city) < 2 || mb_strlen($city) > 50) {
        return ['valid' => false, 'error' => 'City / Municipality must be between 2 and 50 characters.'];
    }
    if (mb_strlen($province) < 2 || mb_strlen($province) > 50) {
        return ['valid' => false, 'error' => 'Province must be between 2 and 50 characters.'];
    }
    if (!preg_match('/^\d{4}$/', $zipcode)) {
        return ['valid' => false, 'error' => 'Zip code must be exactly 4 digits.'];
    }
    return ['valid' => true];
}

function bh_validate_email_address($email) {
    $email = (string)$email;
    if ($email === '') return ['valid' => false, 'error' => 'Email is required.'];
    if ($email !== strtolower($email)) return ['valid' => false, 'error' => 'Invalid email format: use small letters only.'];
    if (preg_match('/\s/', $email) || substr_count($email, '@') !== 1) {
        return ['valid' => false, 'error' => 'Please enter a valid email address.'];
    }
    if (!preg_match('/^[a-z][a-z0-9._-]*@[a-z0-9-]+(?:\.[a-z0-9-]+)+$/', $email) || strpos($email, '..') !== false) {
        return ['valid' => false, 'error' => 'Please enter a valid email address.'];
    }
    // Correct syntax is not enough: the domain has to be one we accept. Every
    // caller of this function (registration, first-login profile, account
    // edits, profile updates) picks the rule up from here automatically.
    $domain = bh_validate_email_domain($email);
    if (!$domain['valid']) {
        return $domain;
    }
    return ['valid' => true];
}

function bh_validate_username_format($username) {
    $username = (string)$username;
    if ($username === '') return ['valid' => false, 'error' => 'Username is required.'];
    if (strlen($username) < 4 || strlen($username) > 20 || !preg_match('/^[a-z]+[0-9]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username must use lowercase letters followed by numbers.'];
    }
    return ['valid' => true];
}

function bh_validate_gender($gender) {
    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        return ['valid' => false, 'error' => 'Please select a valid gender.'];
    }
    return ['valid' => true];
}

function bh_validate_extension($extension) {
    if ($extension === '') return ['valid' => true];
    if (!preg_match('/^(Jr|Sr|I|II|III|IV|V|VI|VII|VIII|IX|X)$/', $extension)) {
        return ['valid' => false, 'error' => 'Input a valid extension (e.g., Jr, Sr, III).'];
    }
    return ['valid' => true];
}

function bh_validate_user_profile(array $data, $requireAddress = false) {
    foreach ([['firstname', 'First Name'], ['lastname', 'Last Name']] as [$field, $label]) {
        $result = bh_validate_name($data[$field] ?? '', $label, true);
        if (!$result['valid']) return $result;
    }
    $middle = bh_validate_name($data['middlename'] ?? '', 'Middle Name', false);
    if (!$middle['valid']) return $middle;

    $extension = bh_validate_extension((string)($data['extension'] ?? ''));
    if (!$extension['valid']) return $extension;

    $birth = bh_validate_birthdate_and_age($data['birth_date'] ?? '', $data['age'] ?? null);
    if (!$birth['valid']) return $birth;
    if (isset($data['age']) && (int)$data['age'] !== (int)$birth['calculated_age']) {
        return ['valid' => false, 'error' => 'Age does not match the birth date.'];
    }

    $gender = bh_validate_gender((string)($data['gender'] ?? ''));
    if (!$gender['valid']) return $gender;
    $email = bh_validate_email_address((string)($data['email'] ?? ''));
    if (!$email['valid']) return $email;

    $addressFields = ['street', 'barangay', 'city_municipality', 'province', 'zipcode', 'country'];
    $hasAddress = false;
    foreach ($addressFields as $field) {
        if (trim((string)($data[$field] ?? '')) !== '') { $hasAddress = true; break; }
    }
    if ($requireAddress || $hasAddress) {
        $address = bh_validate_address(
            $data['street'] ?? '', $data['barangay'] ?? '', $data['city_municipality'] ?? '',
            $data['province'] ?? '', $data['zipcode'] ?? '', $data['country'] ?? ''
        );
        if (!$address['valid']) return $address;
    }
    if (isset($data['username'])) {
        $username = bh_validate_username_format((string)$data['username']);
        if (!$username['valid']) return $username;
    }
    return ['valid' => true, 'calculated_age' => $birth['calculated_age']];
}

function bh_validate_password_strength($password) {
    $len = strlen((string)$password);
    if ($len < 8) {
        return ['valid' => false, 'error' => 'Password must be at least 8 characters long.'];
    }
    if ($len > 64) {
        return ['valid' => false, 'error' => 'Password must not exceed 64 characters.'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one lowercase letter.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one number.'];
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'error' => 'Password must contain at least one special character.'];
    }
    return ['valid' => true];
}

function bh_validate_security_questions($q1, $a1, $q2, $a2, $q3, $a3) {
    $q1 = trim((string)$q1);
    $q2 = trim((string)$q2);
    $q3 = trim((string)$q3);
    $a1 = trim((string)$a1);
    $a2 = trim((string)$a2);
    $a3 = trim((string)$a3);

    if ($q1 === '' || $q2 === '' || $q3 === '') {
        return ['valid' => false, 'error' => 'Please select all three security questions.'];
    }
    if ($q1 === $q2 || $q1 === $q3 || $q2 === $q3) {
        return ['valid' => false, 'error' => 'Security questions must all be different.'];
    }
    if ($a1 === '' || $a2 === '' || $a3 === '') {
        return ['valid' => false, 'error' => 'Please provide answers for all three security questions.'];
    }
    foreach (['Answer 1' => $a1, 'Answer 2' => $a2, 'Answer 3' => $a3] as $label => $ans) {
        if (mb_strlen($ans) < 3) {
            return ['valid' => false, 'error' => $label . ' must be at least 3 characters.'];
        }
        if (mb_strlen($ans) > 50) {
            return ['valid' => false, 'error' => $label . ' must not exceed 50 characters.'];
        }
    }
    return ['valid' => true];
}


// ---------------------------------------------------------------------------
// Approved / verified email domains
// ---------------------------------------------------------------------------
// One configurable list, defined once. Every surface that accepts an email
// address — customer registration, Super Admin account creation, the
// first-login profile form, account edits and profile updates — funnels
// through bh_validate_email_address(), so changing this constant changes the
// rule everywhere instead of in a dozen scattered regexes.
//
// A syntactically valid address on an unlisted domain is rejected: format
// alone has never been proof that a mailbox exists.
if (!defined('BH_APPROVED_EMAIL_DOMAINS')) {
    define('BH_APPROVED_EMAIL_DOMAINS', implode(',', [
        'gmail.com',
        'yahoo.com',
        'outlook.com',
        'hotmail.com',
        'icloud.com',
        // Domains already in use by existing Brew Haven accounts. Removing one
        // would lock those accounts out of every form that revalidates email.
        'brewhaven.local',
        'csucc.edu.ph',
    ]));
}

function bh_approved_email_domains() {
    $list = array_map('trim', explode(',', BH_APPROVED_EMAIL_DOMAINS));
    $list = array_filter(array_map('strtolower', $list), function ($d) { return $d !== ''; });
    return array_values(array_unique($list));
}

// Checked on the server for every email the system stores. The live front-end
// check in bh-validation-rules.js reads the same list (see
// bh_approved_email_domains_json()) so the two can never drift apart, but the
// front-end check is only the courtesy message — this one is the rule.
function bh_validate_email_domain($email) {
    $email = strtolower(trim((string)$email));
    $at = strrpos($email, '@');
    if ($at === false) {
        return ['valid' => false, 'error' => 'Please enter a valid email address.'];
    }
    $domain = substr($email, $at + 1);
    if ($domain === '' || !in_array($domain, bh_approved_email_domains(), true)) {
        return ['valid' => false, 'error' => 'Email domain is not accepted. Please use one of: ' . implode(', ', bh_approved_email_domains()) . '.'];
    }
    return ['valid' => true];
}

// Handy for embedding the list in a page so the live validator matches.
function bh_approved_email_domains_json() {
    return json_encode(bh_approved_email_domains());
}

// ---------------------------------------------------------------------------
// Role -> privilege configuration (single source of truth)
// ---------------------------------------------------------------------------
// Everything that *displays* a role's privileges — the Create Account modal
// and the Change Role modal — reads these functions. They are built from the
// same default_admin_privileges() / default_super_admin_privileges() arrays the
// backend actually grants from, so a shown privilege can never drift away from
// the privilege the authorization layer enforces.

function bh_privilege_catalog() {
    return [
        // Admin privilege keys (admin_privileges.privilege_key)
        'manage_users'                => 'Manage Users',
        'approve_accounts'            => 'Approve Accounts',
        'block_accounts'              => 'Block Accounts',
        'update_user_info'            => 'Update User Information',
        'manage_products'             => 'Manage Products',
        'manage_orders'               => 'Manage Orders',
        // Delegable Super Admin surfaces
        'manage_super_admin_accounts' => 'Delete Users',
        'manage_deletion_requests'    => 'Manage Deletion Requests',
        'view_logs'                   => 'View All Logs',
        'change_account_roles'        => 'Change Account Roles',
        'reset_passwords'             => 'Reset Passwords',
    ];
}

function bh_privilege_label($key) {
    $catalog = bh_privilege_catalog();
    return $catalog[$key] ?? $key;
}

// Privilege keys a role carries by default.
// super_admin: has_privilege() returns true for every key implicitly, so the
// honest answer is the whole catalog, assembled from both grant arrays.
function bh_role_privilege_keys($role) {
    if ($role === 'super_admin') {
        return array_values(array_unique(array_merge(default_admin_privileges(), default_super_admin_privileges())));
    }
    if ($role === 'admin') {
        return default_admin_privileges();
    }
    return [];
}

// Capabilities that no privilege key controls because they are gated by role
// alone — each one corresponds to a require_role(['super_admin']) guard on a
// real endpoint, named here so the modal tells the truth about them too.
//   manage_roles    -> php/update_privileges.php
//   create_accounts -> php/admin_create_staff.php
// (Changing roles and resetting passwords are the delegable
// change_account_roles / reset_passwords keys, listed from the catalog.)
function bh_role_exclusive_capabilities($role) {
    if ($role !== 'super_admin') {
        return [];
    }
    return [
        'create_accounts' => 'Create Admin & Super Admin Accounts',
        'manage_roles'    => 'Grant & Revoke Admin Privileges',
    ];
}

// The full ordered key => label map a role's privilege panel shows.
function bh_role_privilege_display($role) {
    $display = [];
    foreach (bh_role_privilege_keys($role) as $key) {
        $display[$key] = bh_privilege_label($key);
    }
    foreach (bh_role_exclusive_capabilities($role) as $key => $label) {
        $display[$key] = $label;
    }
    return $display;
}

// What happens to an account created from the console with each role. Kept
// next to the rules it describes: admin_create_staff.php stores every new
// account as 'inactive'; setup-security-questions.php activates Admins and
// Customers; bh_super_admin_handover() activates a new Super Admin.
function bh_role_creation_notes() {
    return [
        'customer'    => 'The account is created Inactive. It becomes Active once the user completes the first-login setup.',
        'admin'       => 'The account is created Inactive with the default Admin privileges. It becomes Active once the first-login setup is completed.',
        'super_admin' => 'The account is created Inactive. It becomes the Active Super Admin when you log out, and your account will be blocked.',
    ];
}

// Emitted into a page as JSON so the role <select> can swap the privilege list
// live without a round trip, still from the backend's own configuration.
function bh_role_privileges_json(array $roles = ['admin', 'super_admin', 'customer']) {
    $out = [];
    foreach ($roles as $role) {
        $out[$role] = array_values(bh_role_privilege_display($role));
    }
    return json_encode($out);
}

// ---------------------------------------------------------------------------
// Central account-management permission matrix
// ---------------------------------------------------------------------------
// Every account action asks this one function whether it may run, on the
// server, before it touches a row. Hiding a menu item is presentation only:
// the endpoint makes the identical call, so a hand-crafted POST, a replayed
// form or a typed URL is refused exactly the same way.
//
//   $actor  : current_user() array - needs id_number + role
//   $target : the target users row - needs id_number, role, account_status
//   $action : view | edit | block | unblock | delete | delete_request |
//             reset_password | change_role | approve | reject | manage_privileges
//
// Returns '' when the action is allowed, otherwise a machine error code the
// caller redirects with (the pages already render ?error= codes).

function bh_account_actions() {
    return ['view', 'edit', 'block', 'unblock', 'delete', 'delete_request',
            'reset_password', 'change_role', 'approve', 'reject', 'manage_privileges'];
}

// Actions that a BLOCKED account may still be the target of. Once an account
// is blocked the only things left are looking at it, letting it back in, or
// removing it for good. Everything else (edit, role, privileges, password
// reset...) is refused until it is unblocked. Delete still goes through the
// normal role rules below, the reason + password confirmation, and the
// last-Super-Admin guard in account_delete_direct.php.
function bh_actions_allowed_on_blocked_account() {
    return ['view', 'unblock', 'delete'];
}

// Actions nobody may perform against their own account, whatever their role.
// This is what stops the current Super Admin deleting, blocking, demoting or
// de-privileging themselves and leaving the system with no Super Admin
// authority - and it holds for a direct API call, not just for the UI.
function bh_self_destructive_actions() {
    return ['block', 'delete', 'delete_request', 'reset_password',
            'change_role', 'approve', 'reject', 'manage_privileges'];
}

function bh_account_action_error($actor, $target, $action) {
    if (!in_array($action, bh_account_actions(), true)) {
        return 'invalid_action';
    }
    if (empty($actor['id_number']) || empty($target['id_number'])) {
        return 'not_found';
    }

    $actorRole  = $actor['role'] ?? 'customer';
    $targetRole = $target['role'] ?? 'customer';
    $isSelf     = ($actor['id_number'] === $target['id_number']);
    $isBlocked  = (($target['account_status'] ?? 'active') === 'blocked');

    // --- Self-protection (Super Admin account protection) -------------------
    if ($isSelf && in_array($action, bh_self_destructive_actions(), true)) {
        return 'cannot_target_self';
    }

    // --- Blocked-account restriction ---------------------------------------
    // Checked before any role logic so no role, delegated privilege or
    // hand-made request can edit, reset or re-role a blocked account.
    if ($isBlocked && !in_array($action, bh_actions_allowed_on_blocked_account(), true)) {
        return 'target_blocked';
    }
    // Symmetrically, unblocking something that is not blocked is a no-op.
    if (!$isBlocked && $action === 'unblock') {
        return 'invalid_state';
    }

    // Approve / reject are strictly for Customer / User registrations. Admin
    // and Super Admin accounts are never part of the approval queue, even if a
    // crafted POST names them.
    if (in_array($action, ['approve', 'reject'], true) && $targetRole !== 'customer') {
        return 'forbidden';
    }

    // --- Customer actor -----------------------------------------------------
    // A customer only ever reaches their own account, and only to look at it
    // or edit their own permitted profile fields.
    if ($actorRole === 'customer') {
        if (!$isSelf || !in_array($action, ['view', 'edit'], true)) {
            return 'forbidden';
        }
        return '';
    }

    // --- Super Admin actor --------------------------------------------------
    // Full authority over Customer, Admin and other Super Admin records. The
    // remaining guards (last-remaining-Super-Admin, target must be staff for a
    // role change) stay in the endpoints that own those rules.
    if ($actorRole === 'super_admin') {
        return '';
    }

    // --- Admin actor --------------------------------------------------------
    if ($actorRole !== 'admin') {
        return 'forbidden';
    }

    // An Admin stays an Admin whatever it is granted. The "Super Admin
    // Privileges" are individual add-ons (see bh_privilege_catalog()), never a
    // way to act as a Super Admin: an Admin never touches a Super Admin
    // account, and other Administrators are view only.
    if ($targetRole === 'super_admin') {
        return 'forbidden';
    }

    if ($targetRole === 'admin') {
        if ($action === 'view') {
            return '';
        }
        // "Change Account Roles" add-on: may move another Admin to User /
        // Customer (never to Super Admin; see account_change_role.php).
        if ($action === 'change_role') {
            return has_privilege($actor, 'change_account_roles') ? '' : 'forbidden';
        }
        return 'forbidden';
    }

    // Target is a Customer. Each action maps to the privilege key the existing
    // authorization model already uses for it.
    switch ($action) {
        case 'view':
            return '';
        case 'edit':
            return has_privilege($actor, 'update_user_info') ? '' : 'insufficient_privilege';
        case 'block':
        case 'unblock':
            return has_privilege($actor, 'block_accounts') ? '' : 'insufficient_privilege';
        case 'approve':
        case 'reject':
            return has_privilege($actor, 'approve_accounts') ? '' : 'insufficient_privilege';
        case 'delete_request':
            return has_privilege($actor, 'manage_users') ? '' : 'insufficient_privilege';
        // "Delete Users" add-on: delete a User / Customer account directly,
        // instead of filing a deletion request.
        case 'delete':
            return has_privilege($actor, 'manage_super_admin_accounts') ? '' : 'forbidden';
        // "Reset Passwords" add-on: reset a User / Customer's password.
        case 'reset_password':
            return has_privilege($actor, 'reset_passwords') ? '' : 'forbidden';
        // "Change Account Roles" add-on: make a User / Customer an Admin.
        case 'change_role':
            return has_privilege($actor, 'change_account_roles') ? '' : 'forbidden';
        // Editing privileges remains Super Admin authority.
        case 'manage_privileges':
        default:
            return 'forbidden';
    }
}

function bh_can_account_action($actor, $target, $action) {
    return bh_account_action_error($actor, $target, $action) === '';
}

// Loads the target row every guard needs, in one place, so an endpoint cannot
// forget to read account_status and then miss the blocked-account rule.
function bh_load_target_account($idNumber) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT id_number, firstname, lastname, username, email, role, approval_status, account_status, first_login FROM users WHERE id_number = ?");
    mysqli_stmt_bind_param($stmt, 's', $idNumber);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// Friendly text for the codes the matrix returns, for pages that show a banner.
function bh_account_action_message($code) {
    $messages = [
        'forbidden'              => 'You are not authorized to perform this action on that account.',
        'insufficient_privilege' => 'You do not have the privilege required for this action.',
        'cannot_target_self'     => 'You cannot perform this action on your own account.',
        'target_blocked'         => 'This account is blocked. Unblock it first before making any other change (it can still be deleted).',
        'invalid_action'         => 'That action is not recognised.',
        'invalid_state'          => 'The account is not in a state where that action applies.',
        'not_found'              => 'That account no longer exists.',
        'unblock_role_required'  => 'A blocked Super Admin must be assigned a new role (Admin or User / Customer) before it can be unblocked.',
    ];
    return $messages[$code] ?? $code;
}

// ---------------------------------------------------------------------------
// Critical actions: mandatory reason, delete-only proof, audit record
// ---------------------------------------------------------------------------
// A critical action is one that changes an account's status, privileges or
// security. Each one must carry a reason from the person performing it.
// Supporting proof documents are required only for Delete workflows.

// Actions that may not proceed without a reason.
function bh_actions_requiring_reason() {
    return ['block', 'unblock', 'edit', 'delete', 'delete_request',
            'reset_password', 'change_role', 'approve', 'reject', 'manage_privileges'];
}

// Actions that may not proceed without a proof file attached.
//
// Document / proof upload is reserved for Delete workflows only (direct delete
// and Admin deletion requests). Approve, reject, block, unblock, edit, role
// change, password reset and similar actions keep a mandatory reason where
// required, but never a proof attachment.
//
// A file that IS attached is still fully validated - type, size and real
// content - by bh_store_action_proof().
function bh_actions_requiring_proof() {
    return ['delete', 'delete_request'];
}

// Actions allowed to accept a proof upload at all. Non-delete endpoints ignore
// any unexpected file rather than storing it.
function bh_actions_allowing_proof() {
    return ['delete', 'delete_request'];
}

function bh_action_requires_reason($action) {
    return in_array($action, bh_actions_requiring_reason(), true);
}

function bh_action_requires_proof($action) {
    return in_array($action, bh_actions_requiring_proof(), true);
}

// Validated and sanitised on the server. An empty or whitespace-only reason
// stops the action; the stored text is length-bounded and control characters
// are stripped so a reason can never break the log views that render it.
function bh_validate_action_reason($reason, $action = '') {
    $raw = (string)$reason;
    // Normalise newlines, drop other control characters.
    $raw = str_replace(["\r\n", "\r"], "\n", $raw);
    $raw = preg_replace('/[^\P{C}\n]+/u', '', $raw);
    $clean = trim($raw);

    if ($clean === '') {
        return ['valid' => false, 'error' => 'A reason is required for this action.'];
    }
    if (mb_strlen($clean) < 5) {
        return ['valid' => false, 'error' => 'Please give a reason of at least 5 characters.'];
    }
    if (mb_strlen($clean) > 500) {
        return ['valid' => false, 'error' => 'The reason must not exceed 500 characters.'];
    }
    // Stored escaped, matching how every other free-text field in the app is
    // persisted, so the existing log templates render it safely as-is.
    return ['valid' => true, 'reason' => htmlspecialchars($clean, ENT_QUOTES, 'UTF-8')];
}

// ---------------------------------------------------------------------------
// Proof attachments
// ---------------------------------------------------------------------------
// Stored under uploads/action_proofs/, which ships with an .htaccess that
// denies direct web access. Files are only ever served back through
// php/proof_download.php, which re-checks who is asking. Names are generated,
// never taken from the upload, so a crafted filename cannot traverse out of
// the directory or land as something executable.

if (!defined('BH_PROOF_MAX_BYTES')) {
    define('BH_PROOF_MAX_BYTES', 5 * 1024 * 1024); // 5 MB
}

function bh_proof_storage_dir() {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'action_proofs';
}

// Allowed types, by real MIME type as well as extension. Anything that can be
// executed by the server or the browser is absent by design.
function bh_proof_allowed_types() {
    return [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];
}

function bh_proof_accept_attribute() {
    return '.jpg,.jpeg,.png,.gif,.webp,.pdf';
}

// Validates and stores one uploaded proof file.
// Returns ['valid'=>true,'path'=>relative path or null] - path is null when no
// file was supplied and none was required.
function bh_store_action_proof($fileField, $action, $actorId) {
    $required = bh_action_requires_proof($action);
    $allowed  = in_array($action, bh_actions_allowing_proof(), true);
    $file = $_FILES[$fileField] ?? null;

    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            return ['valid' => false, 'error' => 'A supporting proof document is required for this action.'];
        }
        return ['valid' => true, 'path' => null];
    }
    // Non-delete actions never store an uploaded file, even if one is posted.
    if (!$allowed) {
        return ['valid' => true, 'path' => null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['valid' => false, 'error' => 'The proof file is too large. Maximum size is 5 MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'The proof file could not be uploaded. Please try again.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'The proof file could not be verified.'];
    }
    if ((int)$file['size'] <= 0) {
        return ['valid' => false, 'error' => 'The proof file is empty.'];
    }
    if ((int)$file['size'] > BH_PROOF_MAX_BYTES) {
        return ['valid' => false, 'error' => 'The proof file is too large. Maximum size is 5 MB.'];
    }

    // The real type is read from the file contents, not from the browser's
    // Content-Type header and not from the extension, either of which the
    // uploader controls.
    $allowed = bh_proof_allowed_types();
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = (string)finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = (string)mime_content_type($file['tmp_name']);
    }
    if ($mime === '' || !isset($allowed[$mime])) {
        return ['valid' => false, 'error' => 'Only JPG, PNG, GIF, WEBP or PDF files may be attached as proof.'];
    }
    // The declared extension has to agree with what the file actually is, so a
    // .php renamed to .png (or the reverse) never gets through.
    $extension = $allowed[$mime];
    $declared = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($declared === 'jpeg') { $declared = 'jpg'; }
    if ($declared !== $extension) {
        return ['valid' => false, 'error' => 'The file extension does not match the file contents.'];
    }
    // An image must actually decode as one.
    if ($mime !== 'application/pdf' && @getimagesize($file['tmp_name']) === false) {
        return ['valid' => false, 'error' => 'The attached image file is not a valid image.'];
    }

    $dir = bh_proof_storage_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['valid' => false, 'error' => 'Proof storage is unavailable. Please contact the administrator.'];
    }

    // Generated name: nothing from the uploaded filename survives.
    $safeAction = preg_replace('/[^a-z_]/', '', strtolower($action));
    $safeActor  = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$actorId);
    $name = date('Ymd-His') . '_' . $safeAction . '_' . $safeActor . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

    if (!@move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
        return ['valid' => false, 'error' => 'The proof file could not be saved. Please try again.'];
    }
    @chmod($dir . DIRECTORY_SEPARATOR . $name, 0644);

    // Only the bare generated name is stored; proof_download.php rebuilds the
    // full path, so a stored value can never point outside the directory.
    return ['valid' => true, 'path' => $name];
}

// Resolves a stored proof name back to a readable absolute path, refusing
// anything that is not a plain generated filename.
function bh_proof_absolute_path($storedName) {
    $storedName = (string)$storedName;
    if ($storedName === '' || !preg_match('/^[A-Za-z0-9._\-]+$/', $storedName) || strpos($storedName, '..') !== false) {
        return null;
    }
    $full = bh_proof_storage_dir() . DIRECTORY_SEPARATOR . $storedName;
    return is_file($full) ? $full : null;
}

// ---------------------------------------------------------------------------
// Structured audit record for a critical action
// ---------------------------------------------------------------------------
// Wraps the existing log_security_event() contract (which already captures the
// actor, target, IP, user agent and timestamp) and adds the reason, the proof
// reference and the before/after state, so one row answers who did what to
// whom, why, with what evidence, and what changed.
function bh_log_account_action($eventType, $actor, $targetId, $options = []) {
    global $conn;

    $details       = (string)($options['details'] ?? '');
    $reason        = isset($options['reason']) && $options['reason'] !== '' ? (string)$options['reason'] : null;
    $proofPath     = isset($options['proof_path']) && $options['proof_path'] !== '' ? (string)$options['proof_path'] : null;
    $previousState = isset($options['previous_state']) && $options['previous_state'] !== '' ? (string)$options['previous_state'] : null;
    $newState      = isset($options['new_state']) && $options['new_state'] !== '' ? (string)$options['new_state'] : null;

    $actorId = is_array($actor) ? ($actor['id_number'] ?? null) : $actor;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

    $sql = "INSERT INTO security_logs (event_type, actor_id_number, target_id_number, ip_address, user_agent, details, reason, proof_path, previous_state, new_state)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    // If the migration has not been applied yet the extra columns do not
    // exist. Rather than lose the audit record entirely, fall back to the
    // original five-column insert and fold the reason into `details`.
    if (!$stmt) {
        $fallback = $details;
        if ($reason !== null)    { $fallback = trim($fallback . ' reason=' . $reason); }
        if ($proofPath !== null) { $fallback = trim($fallback . ' proof=' . $proofPath); }
        if ($previousState !== null || $newState !== null) {
            $fallback = trim($fallback . ' ' . (string)$previousState . '->' . (string)$newState);
        }
        log_security_event($eventType, $actorId, $targetId, $fallback);
        return;
    }

    mysqli_stmt_bind_param($stmt, 'ssssssssss', $eventType, $actorId, $targetId, $ip, $ua,
                           $details, $reason, $proofPath, $previousState, $newState);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// True once the migration has been applied. Lets the log views show the new
// columns only when they are actually there, so an un-migrated install keeps
// rendering exactly as it did before.
function bh_audit_columns_available() {
    global $conn;
    static $available = null;
    if ($available === null) {
        $result = @mysqli_query($conn, "SHOW COLUMNS FROM security_logs LIKE 'reason'");
        $available = ($result && mysqli_num_rows($result) > 0);
    }
    return $available;
}

// Logs table "Reason" cell: the reason entered for the action, or an em dash.
function bh_log_reason_cell_html($log) {
    // Stored already-escaped by bh_validate_action_reason(), so printed as-is.
    $reason = trim((string)($log['reason'] ?? ''));
    return $reason !== '' ? nl2br($reason, false) : '—';
}

// Logs table "Document" cell: a link to the attached proof, or an em dash. The
// link goes through php/proof_download.php, which re-checks the viewer.
function bh_log_document_cell_html($log) {
    $proof = trim((string)($log['proof_path'] ?? ''));
    if ($proof === '') {
        return '—';
    }
    $icon = strtolower(pathinfo($proof, PATHINFO_EXTENSION)) === 'pdf' ? 'fa-file-pdf' : 'fa-file-image';
    return '<a href="../php/proof_download.php?file=' . urlencode($proof) . '" target="_blank" rel="noopener">'
        . '<i class="fa-solid ' . $icon . '"></i> View</a>';
}

// Renders the reason / proof / before-after part of one audit row for the
// existing Logs screens. Returns '' when the row has none of it (or when the
// migration has not been applied), so every log view keeps looking exactly as
// it did for the rows that never carried this data.
// $includeReasonAndProof = false when the table shows them in their own columns.
function bh_log_audit_detail_html($log, $includeReasonAndProof = true) {
    if (!bh_audit_columns_available()) {
        return '';
    }
    $parts = [];

    $previous = trim((string)($log['previous_state'] ?? ''));
    $new      = trim((string)($log['new_state'] ?? ''));
    if ($previous !== '' || $new !== '') {
        $parts[] = '<br><strong>Changed:</strong> '
            . htmlspecialchars($previous !== '' ? $previous : '—')
            . ' &rarr; ' . htmlspecialchars($new !== '' ? $new : '—');
    }
    if (!$includeReasonAndProof) {
        return implode('', $parts);
    }

    // The reason is stored already-escaped by bh_validate_action_reason(), so
    // it is printed as-is here rather than escaped a second time.
    $reason = trim((string)($log['reason'] ?? ''));
    if ($reason !== '') {
        $parts[] = '<br><strong>Reason:</strong> ' . $reason;
    }

    // Proof is never linked to by its stored path. The link goes through
    // php/proof_download.php, which re-checks the viewer before serving it.
    $proof = trim((string)($log['proof_path'] ?? ''));
    if ($proof !== '') {
        $parts[] = '<br><strong>Proof:</strong> <a href="../php/proof_download.php?file='
            . urlencode($proof) . '" target="_blank" rel="noopener">View attachment</a>';
    }

    return implode('', $parts);
}

// ---------------------------------------------------------------------------
// Compact pagination (shared by Logs, Accounts, and other list pages)
// ---------------------------------------------------------------------------

/**
 * Normalize page math. Matches existing Brew Haven behaviour: with 0 rows,
 * total_pages is 1 and callers hide the bar; with N rows, page is clamped.
 */
function bh_pagination_calc($totalRows, $page, $perPage) {
    $totalRows = max(0, (int)$totalRows);
    $perPage = max(1, (int)$perPage);
    $page = max(1, (int)$page);
    if ($totalRows === 0) {
        return [
            'total'       => 0,
            'page'        => 1,
            'per_page'    => $perPage,
            'total_pages' => 1,
            'offset'      => 0,
            'from'        => 0,
            'to'          => 0,
        ];
    }
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    return [
        'total'       => $totalRows,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'from'        => $offset + 1,
        'to'          => min($offset + $perPage, $totalRows),
    ];
}

/**
 * Page numbers to render, with null = ellipsis.
 * e.g. [1, 2, 3, 4, 5, null, 62] or [1, null, 20, 21, 22, 23, 24, null, 62]
 */
function bh_pagination_pages($current, $totalPages, $siblings = 2) {
    $current = max(1, (int)$current);
    $totalPages = max(1, (int)$totalPages);
    $siblings = max(1, (int)$siblings);

    if ($totalPages <= 1) {
        return [1];
    }

    // Few pages: show the full list (still compact).
    if ($totalPages <= ($siblings * 2) + 5) {
        return range(1, $totalPages);
    }

    $include = [1 => true, $totalPages => true];
    for ($i = $current - $siblings; $i <= $current + $siblings; $i++) {
        if ($i >= 1 && $i <= $totalPages) {
            $include[$i] = true;
        }
    }
    // Widen the window near the ends so the first/last block stays readable.
    if ($current <= $siblings + 2) {
        for ($i = 2; $i <= min($totalPages - 1, ($siblings * 2) + 2); $i++) {
            $include[$i] = true;
        }
    }
    if ($current >= $totalPages - ($siblings + 1)) {
        for ($i = max(2, $totalPages - (($siblings * 2) + 1)); $i <= $totalPages - 1; $i++) {
            $include[$i] = true;
        }
    }

    $nums = array_map('intval', array_keys($include));
    sort($nums, SORT_NUMERIC);

    $out = [];
    $prev = null;
    foreach ($nums as $n) {
        if ($prev !== null && $n - $prev > 1) {
            $out[] = null;
        }
        $out[] = $n;
        $prev = $n;
    }
    return $out;
}

/**
 * Shared pagination bar HTML.
 *
 * @param int      $totalRows
 * @param int      $page
 * @param int      $perPage
 * @param callable $urlFn   function (int $page): string
 * @param string   $noun    e.g. entries|results|sessions
 */
function bh_render_pagination($totalRows, $page, $perPage, callable $urlFn, $noun = 'entries') {
    $calc = bh_pagination_calc($totalRows, $page, $perPage);
    if ($calc['total'] <= 0) {
        return '';
    }

    $page = $calc['page'];
    $totalPages = $calc['total_pages'];
    $pages = bh_pagination_pages($page, $totalPages);
    $nounSafe = htmlspecialchars((string)$noun, ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <div class="bh-pagination-bar">
      <span class="bh-pagination-summary">Showing <?php echo (int)$calc['from']; ?>–<?php echo (int)$calc['to']; ?> of <?php echo (int)$calc['total']; ?> <?php echo $nounSafe; ?></span>
      <nav class="bh-pagination" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a href="<?php echo $urlFn($page - 1); ?>" aria-label="Previous page">&laquo;</a>
        <?php else: ?>
          <span class="bh-page-disabled" aria-disabled="true">&laquo;</span>
        <?php endif; ?>

        <?php foreach ($pages as $p): ?>
          <?php if ($p === null): ?>
            <span class="bh-page-ellipsis" aria-hidden="true">&hellip;</span>
          <?php elseif ((int)$p === $page): ?>
            <span class="active" aria-current="page"><?php echo (int)$p; ?></span>
          <?php else: ?>
            <a href="<?php echo $urlFn((int)$p); ?>"><?php echo (int)$p; ?></a>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?php echo $urlFn($page + 1); ?>" aria-label="Next page">&raquo;</a>
        <?php else: ?>
          <span class="bh-page-disabled" aria-disabled="true">&raquo;</span>
        <?php endif; ?>
      </nav>
    </div>
    <?php
    return ob_get_clean();
}

?>
