<?php
// public/includes/cart_helper.php

function get_cart_items($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        return ['items' => [], 'total' => 0];
    }
    
    // Get all product codes
    $codes = array_keys($cart);
    
    // Prepare placeholders for SQL IN clause
    $placeholders = str_repeat('?,', count($codes) - 1) . '?';
    $sql = "SELECT * FROM pos_product WHERE codigo_barra IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($codes);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map products by code
    $productMap = [];
    foreach ($products as $p) {
        $productMap[$p['codigo_barra']] = $p;
    }
    
    $items = [];
    $total = 0;
    
    foreach ($cart as $code => $qty) {
        if (isset($productMap[$code])) {
            $product = $productMap[$code];
            $subtotal = $product['precio'] * $qty;
            $total += $subtotal;
            
            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
    
    return ['items' => $items, 'total' => $total];
}

function render_cart_html($cartData) {
    // This function mimics 'pos/partials/cart_list.html'
    // We utilize PHP's output buffering to return the HTML string.
    ob_start();
    $items = $cartData['items'];
    $total = $cartData['total'];
    ?>
    <div id="cart-list" class="flex flex-col h-full">
        <div class="flex-grow overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                Carrito vacío. Escanee un producto para comenzar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($item['product']['nombre']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?= number_format($item['product']['precio'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <input type="number" min="1" value="<?= $item['qty'] ?>" 
                                           class="w-16 border rounded px-2 py-1 text-center"
                                           hx-post="api/cart_update.php?code=<?= $item['product']['codigo_barra'] ?>"
                                           hx-trigger="change"
                                           hx-target="#cart-container"
                                           name="qty">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                    $<?= number_format($item['subtotal'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-red-600 hover:text-red-900 font-bold"
                                            hx-post="api/cart_remove.php?code=<?= $item['product']['codigo_barra'] ?>"
                                            hx-target="#cart-container">
                                        X
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Footer Total -->
        <div class="bg-gray-100 p-4 border-t border-gray-200 mt-auto">
            <div class="flex justify-between items-center mb-4">
                <span class="text-lg font-bold text-gray-700">Total:</span>
                <span class="text-3xl font-bold text-blue-800">$<?= number_format($total, 2) ?></span>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <button 
                    hx-post="api/cart_clear.php"
                    hx-target="#cart-container"
                    class="w-full bg-red-500 text-white font-bold py-3 rounded-lg hover:bg-red-600 transition flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Cancelar
                </button>
                <button 
                    onclick="openCheckout(<?= $total ?>)"
                    <?= empty($items) ? 'disabled' : '' ?>
                    class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Cobrar
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
