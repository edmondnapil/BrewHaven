<?php
// Server-side check behind the shared "Confirm Password" modal
// (js/security-confirm.js). Verifies the CURRENT logged-in user's password
// against the stored hash with bh_confirm_password() - same throttling and
// audit logging as every endpoint - and, when correct, issues a single-use
// token for the one endpoint the action posts to. The action endpoint then
// consumes that token through bh_confirm_password(), so nothing runs unless
// this server check passed, and it runs once.
require_once __DIR__ . '/auth_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'code' => 'method_not_allowed', 'message' => 'Invalid request.']);
    exit;
}

$me = require_login();
if (!in_array($me['role'], ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'code' => 'forbidden', 'message' => 'You are not authorized to perform this action.']);
    exit;
}

$endpoint = basename((string)($_POST['endpoint'] ?? ''));
if (!in_array($endpoint, bh_action_confirm_endpoints(), true)) {
    echo json_encode(['success' => false, 'code' => 'invalid_action', 'message' => 'That action cannot be confirmed.']);
    exit;
}

// Never let a token in this request short-circuit the password check.
unset($_POST['confirm_token']);
$targetId = sanitizeInput((string)($_POST['target_id'] ?? '')) ?: null;
$code = bh_confirm_password($me, (string)($_POST['password'] ?? ''), 'verify:' . $endpoint, $targetId);

if ($code === 'confirm_password_incorrect') {
    echo json_encode(['success' => false, 'code' => $code, 'message' => 'Incorrect password.']);
    exit;
}
if ($code !== '') {
    echo json_encode(['success' => false, 'code' => $code, 'message' => bh_confirm_error_message($code)]);
    exit;
}

echo json_encode(['success' => true, 'token' => bh_issue_action_confirm_token($me, $endpoint)]);
