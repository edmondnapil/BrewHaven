<?php
require_once __DIR__ . '/../php/auth_helpers.php';
ensure_session_started();

$reason = $_GET['reason'] ?? '';
$isTimeout = ($reason === 'inactivity');

if (!empty($_SESSION['user_id_number'])) {
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        bh_clear_active_super_admin_session($_SESSION['user_id_number'], session_id());
    }
    if ($isTimeout) {
        log_security_event('session_timeout_inactivity', $_SESSION['user_id_number'], $_SESSION['user_id_number'], 'auto-logout after 5 minutes of inactivity');
    } else {
        log_security_event('logout', $_SESSION['user_id_number'], $_SESSION['user_id_number'], '');
    }
    // If a newly created Super Admin is waiting, it becomes the Active Super
    // Admin now and the outgoing one is blocked. No-op when nobody is waiting.
    if (($_SESSION['role'] ?? '') === 'super_admin') {
        bh_super_admin_handover($_SESSION['user_id_number'], null, $isTimeout ? 'inactivity logout' : 'logout');
    }
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

$redirectTarget = $isTimeout ? 'login.php?msg=inactivity' : 'login.php?msg=logout';
header('Location: ' . $redirectTarget);
exit;
