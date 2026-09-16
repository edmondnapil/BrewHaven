<?php
// A customer cancels their own order while it is still Pending (the kitchen
// has not started it). Anything further along is up to the staff.
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['customer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/order-history.php');
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    header('Location: ../html/order-history.php?error=invalid');
    exit;
}

// Ownership and status are part of the UPDATE itself, so another customer's
// order, or one that moved on in the meantime, is simply not touched.
$stmt = mysqli_prepare($conn, "UPDATE orders SET status = 'Cancelled' WHERE id = ? AND customer_id_number = ? AND status = 'Pending'");
mysqli_stmt_bind_param($stmt, 'is', $orderId, $me['id_number']);
mysqli_stmt_execute($stmt);
$cancelled = mysqli_stmt_affected_rows($stmt) === 1;
mysqli_stmt_close($stmt);

if (!$cancelled) {
    log_security_event('order_cancel_denied', $me['id_number'], $me['id_number'], 'order_id=' . $orderId);
    header('Location: ../html/order-history.php?error=cannot_cancel');
    exit;
}

log_security_event('order_cancelled', $me['id_number'], $me['id_number'], 'order_id=' . $orderId . ' by customer');
header('Location: ../html/order-history.php?msg=cancelled');
exit;
