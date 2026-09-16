<?php
require_once __DIR__ . '/auth_helpers.php';
$me = require_role(['customer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/order-cart.php');
    exit;
}

ensure_session_started();
$cart = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

if (empty($cart)) {
    header('Location: ../html/order-cart.php');
    exit;
}

$orderType = ($_POST['order_type'] ?? '') === 'Takeout' ? 'Takeout' : 'Dine In';
$tableNumber = trim($_POST['table_number'] ?? '');
if ($orderType === 'Dine In') {
    if ($tableNumber === '') {
        header('Location: ../html/order-cart.php?msg=table_required');
        exit;
    }
    $tableNumber = substr($tableNumber, 0, 10);
} else {
    $tableNumber = null;
}

// The cart holds a snapshot from when each item was added. Before anything is
// charged, re-check every product against the live menu: an item that is no
// longer available is taken out, and a changed price is updated. Either way
// the customer is sent back to review the cart instead of being charged for
// something different from what they saw.
$productIds = array_values(array_unique(array_map(function ($it) { return (int)$it['product_id']; }, $cart)));
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$liveStmt = mysqli_prepare($conn, "SELECT id, price FROM products WHERE is_available = 1 AND id IN ($placeholders)");
mysqli_stmt_bind_param($liveStmt, str_repeat('i', count($productIds)), ...$productIds);
mysqli_stmt_execute($liveStmt);
$liveResult = mysqli_stmt_get_result($liveStmt);
$livePrices = [];
while ($row = mysqli_fetch_assoc($liveResult)) { $livePrices[(int)$row['id']] = (float)$row['price']; }
mysqli_stmt_close($liveStmt);

$reviewed = [];
$removedItems = false;
$priceChanged = false;
foreach ($cart as $item) {
    $pid = (int)$item['product_id'];
    if (!isset($livePrices[$pid])) { $removedItems = true; continue; }
    if (abs((float)$item['unit_price'] - $livePrices[$pid]) > 0.001) {
        $item['unit_price'] = $livePrices[$pid];
        $priceChanged = true;
    }
    $item['addons'] = bh_filter_addons($item['addons'] ?? []);
    $item['quantity'] = max(1, min(20, (int)$item['quantity']));
    $reviewed[] = $item;
}
if ($removedItems || $priceChanged) {
    $_SESSION['cart'] = $reviewed;
    header('Location: ../html/order-cart.php?msg=' . ($removedItems ? 'items_unavailable' : 'prices_updated'));
    exit;
}
$cart = $reviewed;

$total = 0.0;
foreach ($cart as $item) {
    $total += $item['unit_price'] * $item['quantity'];
}

mysqli_begin_transaction($conn);
try {
    $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_id_number, order_type, table_number, status, total_amount) VALUES (?, ?, ?, 'Pending', ?)");
    mysqli_stmt_bind_param($stmt, 'sssd', $me['id_number'], $orderType, $tableNumber, $total);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create order');
    }
    $orderId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $itemStmt = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, size, temperature, sweetness, addons, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($cart as $item) {
        $subtotal = $item['unit_price'] * $item['quantity'];
        $addonsStr = !empty($item['addons']) ? implode(', ', $item['addons']) : null;
        mysqli_stmt_bind_param(
            $itemStmt,
            'iissssidd',
            $orderId, $item['product_id'], $item['size'], $item['temperature'], $item['sweetness'], $addonsStr,
            $item['quantity'], $item['unit_price'], $subtotal
        );
        if (!mysqli_stmt_execute($itemStmt)) {
            throw new Exception('Failed to save order item');
        }
    }
    mysqli_stmt_close($itemStmt);

    mysqli_commit($conn);
    log_security_event('order_placed', $me['id_number'], $me['id_number'], 'order_id=' . $orderId);
    $_SESSION['cart'] = [];
    header('Location: ../html/order-cart.php?msg=placed');
    exit;
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: ../html/order-cart.php?msg=error');
    exit;
}
