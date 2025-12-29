<?php
// public/pos.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/cart_helper.php';

require_login();

$pageTitle = "Punto de Venta";
include 'includes/header.php';

// Initial render of cart
$cartData = get_cart_items($pdo);
?>

<div class="flex flex-col h-full space-y-4" x-data="posApp()">
    
    <!-- Top Bar: Scan & Actions -->
    <div class="bg-white p-4 rounded-lg shadow flex justify-between items-center gap-4">
        <div class="flex-grow">
            <!-- Scan Form -->
            <form hx-post="api/cart_add.php" hx-target="#cart-container" hx-swap="innerHTML" 
                  hx-on::after-request="this.reset(); document.getElementById('barcode-input').focus()"
                  @submit.prevent="if($refs.barcode.value.trim() === '') return; $el.requestSubmit()"
                  class="relative">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <label class="sr-only">Escanear Código</label>
                <div class="relative">
                    <input type="text" name="barcode" id="barcode-input" x-ref="barcode" autofocus
                           class="w-full text-lg p-3 pl-4 rounded-lg border-2 border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                           placeholder="Escanear producto..." autocomplete="off">
                </div>
            </form>
        </div>
        
        <div class="flex space-x-2">
            <!-- Cancel / Clear Cart -->
            <button hx-post="api/cart_clear.php" hx-target="#cart-container"
                    :disabled="currentTotal <= 0"
                    :class="{'opacity-50 cursor-not-allowed': currentTotal <= 0, 'hover:bg-red-600': currentTotal > 0}"
                    class="bg-red-500 text-white font-bold py-3 px-6 rounded-lg shadow transition">
                Cancelar
            </button>
            
            <!-- Checkout Button -->
            <button @click="openCheckout()"
                    :disabled="currentTotal <= 0"
                    :class="{'opacity-50 cursor-not-allowed': currentTotal <= 0, 'hover:scale-105 hover:bg-green-700': currentTotal > 0}"
                    class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow transition transform">
                Finalizar Venta
            </button>
        </div>
    </div>

    <!-- Cart Area -->
    <div id="cart-container" class="bg-white rounded-lg shadow flex-grow overflow-hidden flex flex-col"
         @htmx:after-swap.camel="updateTotal()">
        <?= render_cart_html($cartData) ?>
    </div>

    <!-- Checkout Modal (Teleport to Body) -->
    <template x-teleport="body">
        <div x-show="checkoutOpen" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black bg-opacity-50"
             style="display: none;" x-transition x-cloak>
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" @click.away="closeCheckout()">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Finalizar Venta</h2>
                    <button @click="closeCheckout()" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <!-- Total Display -->
                <div class="mb-6 text-center">
                    <p class="text-gray-500 uppercase text-sm tracking-wide">Total a Pagar</p>
                    <p class="text-4xl font-extrabold text-blue-600">$<span x-text="currentTotal.toFixed(2)"></span></p>
                </div>

                <form hx-post="api/checkout.php" hx-target="#cart-container" 
                      @submit="closeCheckout()"> <!-- We close modal immediately on submit? Or wait for response? -->
                      <!-- Better UX: close on submit. If error, toast appears. If success, toast appears. UI resets. -->
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Método de Pago</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer border rounded-lg p-3 flex items-center justify-center hover:bg-blue-50 transition"
                                   :class="{'bg-blue-100 border-blue-500': paymentMethod === 'Efectivo'}"
                                   @click="setPaymentMode('Efectivo')">
                                <span class="font-semibold">Efectivo</span>
                            </label>
                            <label class="cursor-pointer border rounded-lg p-3 flex items-center justify-center hover:bg-blue-50 transition"
                                   :class="{'bg-blue-100 border-blue-500': paymentMethod === 'Transferencia'}"
                                   @click="setPaymentMode('Transferencia')">
                                 <span class="font-semibold">Transferencia</span>
                            </label>
                        </div>
                        <input type="hidden" name="metodo_pago" x-model="paymentMethod">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">Monto Cliente</label>
                        <input type="number" step="0.01" x-model="cashGiven"
                               :disabled="paymentMethod === 'Transferencia'"
                               class="w-full p-3 rounded-lg border-2 border-gray-300 focus:border-blue-500 text-lg disabled:bg-gray-100 disabled:text-gray-500" 
                               placeholder="0.00">
                        
                        <div class="mt-4 p-4 bg-gray-100 rounded-lg flex justify-between"
                             :class="{'opacity-50': paymentMethod === 'Transferencia'}">
                            <span class="font-bold text-gray-600">Vuelto:</span>
                            <span class="font-bold text-green-600 text-xl" 
                                  x-text="calculateChange()">0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 shadow-lg">
                        Confirmar Pago
                    </button>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
    function posApp() {
        return {
            checkoutOpen: false,
            paymentMethod: 'Efectivo',
            currentTotal: <?= $cartData['total'] ?>, // Init from server
            cashGiven: '',
            
            init() {
                // Ensure text is consistent with server state
            },

            openCheckout() {
                this.updateTotal(); // Refresh total before opening
                if (this.currentTotal <= 0) return;
                this.checkoutOpen = true;
                this.setPaymentMode('Efectivo');
            },
            
            closeCheckout() {
                this.checkoutOpen = false;
            },
            
            setPaymentMode(mode) {
                this.paymentMethod = mode;
                if (mode === 'Transferencia') {
                    this.cashGiven = this.currentTotal.toFixed(2);
                } else {
                    this.cashGiven = '';
                }
            },
            
            updateTotal() {
                // Parse total from the rendered cart HTML specific element
                // We rely on #cart-total-value existing in the re-rendered HTML
                const el = document.getElementById('cart-total-value');
                if (el) {
                    let val = el.innerText.trim();
                    // Basic cleanup for currency format
                    val = val.replace(/[^\d.,]/g, '');
                    // Assuming EN format output from number_format usually (1,200.00)
                    // If we used default english locale in PHP: comma is thousand sep, dot is decimal.
                    val = val.replace(/,/g, ''); 
                    this.currentTotal = parseFloat(val) || 0;
                } else {
                    this.currentTotal = 0;
                }
            },
            
            calculateChange() {
                const given = parseFloat(this.cashGiven) || 0;
                const total = this.currentTotal;
                if (given < total) return '0.00';
                return (given - total).toFixed(2);
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
