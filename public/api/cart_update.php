<?php
// public/api/cart_update.php
require_once '../includes/db.php';
require_once '../includes/cart_helper.php';

session_start();

$code = $_GET['code'] ?? null;
$qty = intval($_POST['qty'] ?? 1);

if ($code && isset($_SESSION['cart'][$code])) {
    if ($qty > 0) {
        $_SESSION['cart'][$code] = $qty;
    } else {
        unset($_SESSION['cart'][$code]);
    }
}

$cartData = get_cart_items($pdo);
echo render_cart_html($cartData);
?>
