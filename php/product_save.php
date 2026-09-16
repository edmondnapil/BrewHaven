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

$productId   = intval($_POST['product_id'] ?? 0);
$name        = sanitizeInput($_POST['name'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$category    = $_POST['category'] ?? '';
$price       = floatval($_POST['price'] ?? 0);
$imagePath   = sanitizeInput($_POST['image_path'] ?? '../images/product1.jpg');
$validCategories = ['Hot Coffee', 'Iced Coffee', 'Non-Coffee', 'Add-ons'];

if ($name === '' || !in_array($category, $validCategories, true) || $price <= 0) {
    header('Location: ../html/admin-products.php?error=invalid');
    exit;
}

if ($productId > 0) {
    $stmt = mysqli_prepare($conn, "UPDATE products SET name = ?, description = ?, category = ?, price = ?, image_path = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'sssdsi', $name, $description, $category, $price, $imagePath, $productId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    log_security_event('product_updated', $me['id_number'], null, 'product_id=' . $productId);
} else {
    $stmt = mysqli_prepare($conn, "INSERT INTO products (name, description, category, price, image_path, is_available) VALUES (?, ?, ?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmt, 'sssds', $name, $description, $category, $price, $imagePath);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    log_security_event('product_created', $me['id_number'], null, 'product_id=' . $newId);
}

header('Location: ../html/admin-products.php?msg=saved');
exit;
