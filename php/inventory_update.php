<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
if ($me['role'] === 'admin') {
    require_privilege('manage_products');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-inventory.php');
    exit;
}

$itemId    = intval($_POST['item_id'] ?? 0);
$quantity  = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
$threshold = filter_var($_POST['low_stock_threshold'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);

if ($itemId <= 0 || $quantity === false) {
    header('Location: ../html/admin-inventory.php?error=invalid');
    exit;
}

$currentStmt = mysqli_prepare($conn, "SELECT quantity, low_stock_threshold FROM inventory_items WHERE id = ?");
mysqli_stmt_bind_param($currentStmt, 'i', $itemId);
mysqli_stmt_execute($currentStmt);
$current = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt));
mysqli_stmt_close($currentStmt);
if (!$current) {
    header('Location: ../html/admin-inventory.php?error=not_found');
    exit;
}
// The threshold is optional on this form; when omitted it stays as it was.
if ($threshold === false) {
    $threshold = (int)$current['low_stock_threshold'];
}

$stmt = mysqli_prepare($conn, "UPDATE inventory_items SET quantity = ?, low_stock_threshold = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'iii', $quantity, $threshold, $itemId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_security_event('inventory_updated', $me['id_number'], null,
    'item_id=' . $itemId . ' quantity=' . $current['quantity'] . '->' . $quantity
    . ' threshold=' . $current['low_stock_threshold'] . '->' . $threshold);
header('Location: ../html/admin-inventory.php?msg=updated');
exit;
