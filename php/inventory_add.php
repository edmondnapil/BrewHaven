<?php
// Adds a new ingredient to the inventory. Same privilege as editing it.
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
if ($me['role'] === 'admin') {
    require_privilege('manage_products');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-inventory.php');
    exit;
}

$name      = trim(preg_replace('/\s+/', ' ', (string)($_POST['name'] ?? '')));
$unit      = trim(preg_replace('/\s+/', ' ', (string)($_POST['unit'] ?? '')));
$quantity  = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
$threshold = filter_var($_POST['low_stock_threshold'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);

if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || !preg_match('/^[\p{L}\p{N} .,&()\'-]+$/u', $name)) {
    header('Location: ../html/admin-inventory.php?error=invalid_name');
    exit;
}
if (mb_strlen($unit) < 1 || mb_strlen($unit) > 20 || !preg_match('/^[\p{L} .]+$/u', $unit)) {
    header('Location: ../html/admin-inventory.php?error=invalid_unit');
    exit;
}
if ($quantity === false || $threshold === false) {
    header('Location: ../html/admin-inventory.php?error=invalid');
    exit;
}

$dupStmt = mysqli_prepare($conn, "SELECT id FROM inventory_items WHERE LOWER(name) = LOWER(?) LIMIT 1");
mysqli_stmt_bind_param($dupStmt, 's', $name);
mysqli_stmt_execute($dupStmt);
mysqli_stmt_store_result($dupStmt);
$exists = mysqli_stmt_num_rows($dupStmt) > 0;
mysqli_stmt_close($dupStmt);
if ($exists) {
    header('Location: ../html/admin-inventory.php?error=duplicate');
    exit;
}

$stmt = mysqli_prepare($conn, "INSERT INTO inventory_items (name, quantity, unit, low_stock_threshold) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sisi', $name, $quantity, $unit, $threshold);
mysqli_stmt_execute($stmt);
$newId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

log_security_event('inventory_item_added', $me['id_number'], null, 'item_id=' . $newId . ' name=' . $name . ' quantity=' . $quantity . ' unit=' . $unit);
header('Location: ../html/admin-inventory.php?msg=added');
exit;
