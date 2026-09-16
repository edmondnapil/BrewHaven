<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['customer']);

$redirectTo = bh_page_url(sanitizeInput($_POST['redirect_to'] ?? 'menu.php'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectTo);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT id, name, image_path, price FROM products WHERE id = ? AND is_available = 1");
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$product) {
    header('Location: ' . bh_redirect_with($redirectTo, 'error', 'product_unavailable'));
    exit;
}

$size = in_array($_POST['size'] ?? '', ['Small', 'Medium', 'Large'], true) ? $_POST['size'] : 'Medium';
$temperature = in_array($_POST['temperature'] ?? '', ['Hot', 'Iced'], true) ? $_POST['temperature'] : 'Hot';
$sweetness = in_array($_POST['sweetness'] ?? '', ['Regular', 'Less Sweet', 'No Sugar'], true) ? $_POST['sweetness'] : 'Regular';
$addons = bh_filter_addons($_POST['addons'] ?? []);
$quantity = max(1, min(20, intval($_POST['quantity'] ?? 1)));

ensure_session_started();
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$_SESSION['cart'][] = [
    'product_id' => $product['id'],
    'name' => $product['name'],
    'image_path' => $product['image_path'],
    'size' => $size,
    'temperature' => $temperature,
    'sweetness' => $sweetness,
    'addons' => $addons,
    'quantity' => $quantity,
    'unit_price' => (float)$product['price'],
];

header('Location: ' . bh_redirect_with($redirectTo, 'msg', 'added_to_cart'));
exit;
