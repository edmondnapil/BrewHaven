<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['admin', 'super_admin']);
if ($me['role'] === 'admin') {
    require_privilege('manage_products');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/admin-products.php');
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    header('Location: ../html/admin-products.php?error=invalid');
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE products SET is_available = NOT is_available WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_security_event('product_availability_toggled', $me['id_number'], null, 'product_id=' . $productId);
header('Location: ../html/admin-products.php?msg=toggled');
exit;
