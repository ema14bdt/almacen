<?php
// public/api/cart_add.php
require_once '../includes/db.php';
require_once '../includes/cart_helper.php';

session_start();

$barcode = $_POST['barcode'] ?? null;

if ($barcode) {
    // Verify product exists
    $stmt = $pdo->prepare("SELECT id FROM pos_product WHERE codigo_barra = :code AND activo = 1");
    $stmt->execute([':code' => $barcode]);
    
    if ($stmt->fetch()) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$barcode])) {
            $_SESSION['cart'][$barcode]++;
        } else {
            $_SESSION['cart'][$barcode] = 1;
        }
    } else {
        // Return Error Toast/Alert (HTMX OOB)
        echo '<div id="toast-container" hx-swap-oob="beforeend">';
        echo '<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="bg-red-500 text-white px-4 py-3 rounded shadow-lg flex items-center justify-between pointer-events-auto transform transition-all duration-300 ease-in-out">';
        echo '<span>Producto no encontrado</span>';
        echo '</div></div>';
        exit(); // Stop here, don't re-render cart if failed? Or re-render anyway.
    }
}

// Return updated cart
$cartData = get_cart_items($pdo);
echo render_cart_html($cartData);
?>
