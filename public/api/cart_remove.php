<?php
// public/api/cart_remove.php
require_once '../includes/db.php';
require_once '../includes/cart_helper.php';

session_start();

$code = $_GET['code'] ?? null;

if ($code && isset($_SESSION['cart'][$code])) {
    unset($_SESSION['cart'][$code]);
}

$cartData = get_cart_items($pdo);
echo render_cart_html($cartData);
?>
