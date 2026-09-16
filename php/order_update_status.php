<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
if ($me['role'] === 'admin') {
    require_privilege('manage_orders');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-orders.php');
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$status  = $_POST['status'] ?? '';
$validStatuses = ['Pending', 'Preparing', 'Ready', 'Completed', 'Cancelled'];

if ($orderId <= 0 || !in_array($status, $validStatuses, true)) {
    header('Location: ../html/admin-orders.php?error=invalid');
    exit;
}

$currentStmt = mysqli_prepare($conn, "SELECT status, customer_id_number FROM orders WHERE id = ?");
mysqli_stmt_bind_param($currentStmt, 'i', $orderId);
mysqli_stmt_execute($currentStmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt));
mysqli_stmt_close($currentStmt);
if (!$order) {
    header('Location: ../html/admin-orders.php?error=not_found');
    exit;
}

// Orders only move forward (or get cancelled); Completed and Cancelled are
// final. The status is re-checked in the UPDATE so two staff updating the same
// order at once cannot both apply.
if (!bh_order_can_move($order['status'], $status)) {
    header('Location: ../html/admin-orders.php?error=invalid_transition');
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ? AND status = ?");
mysqli_stmt_bind_param($stmt, 'sis', $status, $orderId, $order['status']);
mysqli_stmt_execute($stmt);
$updated = mysqli_stmt_affected_rows($stmt) === 1;
mysqli_stmt_close($stmt);
if (!$updated) {
    header('Location: ../html/admin-orders.php?error=changed_meanwhile');
    exit;
}

log_security_event('order_status_changed', $me['id_number'], $order['customer_id_number'], 'order_id=' . $orderId . ' status=' . $status);
header('Location: ../html/admin-orders.php?msg=updated');
exit;
