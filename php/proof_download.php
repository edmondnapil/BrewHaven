<?php
// Serves a proof attachment back to somebody entitled to see it.
//
// The files themselves sit under uploads/action_proofs/, which the shipped
// .htaccess denies outright, so this endpoint is the only way in and the
// authorization below is the only thing that opens it. Guessing a filename
// gets a 404 exactly like asking for one that does not exist, so this cannot
// be used to probe which proofs exist.
require_once __DIR__ . '/auth_helpers.php';
$me = require_login();

function bh_proof_deny($status = 404) {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $status === 403 ? 'You are not authorized to view this attachment.' : 'Attachment not found.';
    exit;
}

$file = (string)($_GET['file'] ?? '');
if ($file === '') {
    bh_proof_deny();
}

// Only Admin and Super Admin ever look at account-action evidence.
if (!in_array($me['role'], ['admin', 'super_admin'], true)) {
    log_security_event('proof_access_denied', $me['id_number'], null, 'unauthorized role');
    bh_proof_deny(403);
}

// The name must resolve to a real file inside the proof directory. Anything
// with a path separator or a traversal segment is rejected by the helper.
$absolute = bh_proof_absolute_path($file);
if ($absolute === null) {
    bh_proof_deny();
}

// The attachment must belong to an audit record this viewer may see. An Admin
// only sees evidence on actions they filed themselves or that concern an
// account they are allowed to manage; a Super Admin sees all of it.
$stmt = mysqli_prepare($conn, "SELECT actor_id_number, target_id_number FROM security_logs WHERE proof_path = ? LIMIT 1");
if (!$stmt) {
    // Migration not applied yet: no proof can legitimately have been stored.
    bh_proof_deny();
}
mysqli_stmt_bind_param($stmt, 's', $file);
mysqli_stmt_execute($stmt);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$record) {
    // Also accept a proof filed against a deletion request.
    $reqStmt = mysqli_prepare($conn, "SELECT requested_by_id_number AS actor_id_number, target_id_number FROM delete_requests WHERE proof_path = ? LIMIT 1");
    if ($reqStmt) {
        mysqli_stmt_bind_param($reqStmt, 's', $file);
        mysqli_stmt_execute($reqStmt);
        $record = mysqli_fetch_assoc(mysqli_stmt_get_result($reqStmt));
        mysqli_stmt_close($reqStmt);
    }
}
if (!$record) {
    bh_proof_deny();
}

if ($me['role'] === 'admin') {
    $isOwnFiling = ($record['actor_id_number'] === $me['id_number']);
    $mayViewTarget = false;
    if (!empty($record['target_id_number'])) {
        $target = bh_load_target_account($record['target_id_number']);
        // A deleted account leaves its evidence behind; the Admin who filed
        // the action keeps access to it, nobody else at Admin level does.
        $mayViewTarget = $target ? bh_can_account_action($me, $target, 'view') : false;
    }
    if (!$isOwnFiling && !$mayViewTarget) {
        log_security_event('proof_access_denied', $me['id_number'], $record['target_id_number'] ?? null, 'not entitled to this attachment');
        bh_proof_deny(403);
    }
}

$extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
$contentType = $types[$extension] ?? 'application/octet-stream';

log_security_event('proof_viewed', $me['id_number'], $record['target_id_number'] ?? null, 'proof=' . $file);

// Rendered inline for images and PDFs, never executed: the type is fixed from
// our own allow-list and sniffing is turned off.
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($absolute));
header('Content-Disposition: inline; filename="' . basename($absolute) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; img-src \'self\'; object-src \'self\'');
header('Cache-Control: private, max-age=0, no-store');
readfile($absolute);
exit;
