<?php
// public/api/cart_clear.php
require_once '../includes/db.php';
require_once '../includes/cart_helper.php';

session_start();
$_SESSION['cart'] = [];

$cartData = get_cart_items($pdo);
echo render_cart_html($cartData);
?>
