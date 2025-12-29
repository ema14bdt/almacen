<?php
// public/api/checkout.php
require_once '../includes/db.php';
require_once '../includes/cart_helper.php';

session_start();

$cart = $_SESSION['cart'] ?? [];
$metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
$user_id = $_SESSION['user_id'] ?? null;

if (empty($cart)) {
    http_response_code(400);
    die("Carrito vacio");
}

try {
    $pdo->beginTransaction();

    // 1. Validate Stock & Calculate Total
    $codes = array_keys($cart);
    $placeholders = str_repeat('?,', count($codes) - 1) . '?';
    
    // Lock rows logic isn't straightforward in SQLite generally, but transaction handles isolation.
    $stmt = $pdo->prepare("SELECT * FROM pos_product WHERE codigo_barra IN ($placeholders)");
    $stmt->execute($codes);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productMap = [];
    foreach ($products as $p) {
        $productMap[$p['codigo_barra']] = $p;
    }

    $total = 0;
    
    foreach ($cart as $code => $qty) {
        if (!isset($productMap[$code])) {
            throw new Exception("Producto no encontrado: $code");
        }
        $product = $productMap[$code];
        if ($product['stock'] < $qty) {
            throw new Exception("Stock insuficiente para: " . $product['nombre']);
        }
        $total += $product['precio'] * $qty;
    }

    // 2. Create Sale
    // Date: SQLite defaults are UTC usually. Let's insert explicit localtime if we want consistency with PHP date()
    $fecha = date('Y-m-d H:i:s');
    $stmtSale = $pdo->prepare("INSERT INTO pos_sale (fecha, total, metodo_pago, usuario_id) VALUES (:fecha, :total, :metodo, :user)");
    $stmtSale->execute([
        ':fecha' => $fecha,
        ':total' => $total,
        ':metodo' => $metodo_pago,
        ':user' => $user_id
    ]);
    $saleId = $pdo->lastInsertId();

    // 3. Create Details & Update Stock
    $stmtDetail = $pdo->prepare("INSERT INTO pos_saledetail (venta_id, producto_id, cantidad, precio_unitario_congelado, subtotal) VALUES (:venta, :prod, :cant, :precio, :sub)");
    $stmtStock = $pdo->prepare("UPDATE pos_product SET stock = stock - :qty WHERE id = :id");

    foreach ($cart as $code => $qty) {
        $product = $productMap[$code];
        $subtotal = $product['precio'] * $qty;
        
        // Detail
        $stmtDetail->execute([
            ':venta' => $saleId,
            ':prod' => $product['id'],
            ':cant' => $qty,
            ':precio' => $product['precio'], // frozen price
            ':sub' => $subtotal
        ]);
        
        // Stock
        $stmtStock->execute([
            ':qty' => $qty,
            ':id' => $product['id']
        ]);
    }

    $pdo->commit();
    
    // Clear Cart
    $_SESSION['cart'] = [];

    // Response: Success Toast + Empty Cart
    // We send OOB Swaps
    
    // 1. Success Toast
    echo '<div id="toast-container" hx-swap-oob="beforeend">';
    echo '<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-500 text-white px-4 py-3 rounded shadow-lg flex items-center justify-between mb-2">';
    echo '<span>Venta #' . $saleId . ' realizada con éxito. Total: $' . number_format($total, 2) . '</span>';
    echo '</div></div>';
    
    // 2. Reset Cart Area (render empty)
    $cartData = get_cart_items($pdo); // empty now
    echo render_cart_html($cartData);
    
    // 3. Close the Modal (via script trigger if needed, or rely on Alpine catching the form submit)
    // Alpine logic in pos.php `closeCheckout()` handles UI state on submit usually.

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Error Toast
    echo '<div id="toast-container" hx-swap-oob="beforeend">';
    echo '<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-red-500 text-white px-4 py-3 rounded shadow-lg mb-2">';
    echo '<span>Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
    echo '</div></div>';
    
    // Return current cart state (unchanged)
    http_response_code(200); // HTMX expects 200 to swap.
    $cartData = get_cart_items($pdo);
    echo render_cart_html($cartData);
}
?>
