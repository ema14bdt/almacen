<?php
// public/inventory.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

require_login();

$message = '';
$error = '';

// --- HANDLE POST REQUESTS (Create/Update/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. DELETE
    if ($action === 'delete') {
        $id = $_POST['product_id'] ?? 0;
        try {
            $stmt = $pdo->prepare("UPDATE pos_product SET activo = 0, stock = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = "Producto eliminado y stock reseteado.";
        } catch (PDOException $e) {
            $error = "Error al eliminar: " . $e->getMessage();
        }
    }
    // 2. CREATE / UPDATE
    elseif ($action === 'save') {
        $id = $_POST['product_id'] ?? ''; // Empty if new
        $codigo = trim($_POST['codigo_barra'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock_add = intval($_POST['stock_add'] ?? 0);
        $reset_stock = isset($_POST['reset_stock']);
        
        if (!$codigo || !$nombre) {
            $error = "Código y Nombre son obligatorios.";
        } else {
            try {
                if ($id) {
                    // UPDATE
                    // First get current stock if needed, or just update directly
                    // Logic: reset_stock first?
                    // We can do it in one atomic update or transaction.
                    // If reset_stock: stock = 0 + stock_add
                    // Else: stock = stock + stock_add
                    
                    $sql = "UPDATE pos_product SET nombre = :nombre, precio = :precio, activo = 1 ";
                    if ($reset_stock) {
                        $sql .= ", stock = :stock_add ";
                    } else {
                        $sql .= ", stock = stock + :stock_add ";
                    }
                    $sql .= "WHERE id = :id";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':precio' => $precio,
                        ':stock_add' => $stock_add,
                        ':id' => $id
                    ]);
                    $message = "Producto actualizado.";
                    
                } else {
                    // CREATE
                    // Check if code exists (soft deleted or active)
                    $check = $pdo->prepare("SELECT id FROM pos_product WHERE codigo_barra = :code");
                    $check->execute([':code' => $codigo]);
                    $existing = $check->fetch();
                    
                    if ($existing) {
                        // Reactivate strategy? Or duplicate error?
                        // Django logic: get instance. If existing, update it.
                        // Here, if ID is empty but code exists, it's effectively an update/reactivate logic.
                        // Let's reuse the UPDATE logic if code exists.
                        $id = $existing['id'];
                        // Repeat Update Logic or redirect to it?
                        // Let's implement Reactivate logic here:
                         $sql = "UPDATE pos_product SET nombre = :nombre, precio = :precio, activo = 1 ";
                        if ($reset_stock) {
                            $sql .= ", stock = :stock_add ";
                        } else {
                            $sql .= ", stock = stock + :stock_add ";
                        }
                        $sql .= "WHERE id = :id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':nombre' => $nombre,
                            ':precio' => $precio,
                            ':stock_add' => $stock_add,
                            ':id' => $id
                        ]);
                        $message = "Producto reactivado/actualizado.";
                        
                    } else {
                        // Real INSERT
                        $stmt = $pdo->prepare("INSERT INTO pos_product (codigo_barra, nombre, precio, stock, activo) VALUES (:code, :nombre, :precio, :stock, 1)");
                        $stmt->execute([
                            ':code' => $codigo,
                            ':nombre' => $nombre,
                            ':precio' => $precio,
                            ':stock' => $stock_add // New product stock is just stock_add
                        ]);
                        $message = "Producto creado.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Error DB: " . $e->getMessage();
            }
        }
    }
}

// --- QUERY PRODUCTS (Pagination + Search) ---
$query = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereClause = "WHERE activo = 1";
$params = [];

if ($query) {
    $whereClause .= " AND (nombre LIKE :q OR codigo_barra LIKE :q)";
    $params[':q'] = "%$query%";
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pos_product $whereClause");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Fetch Items
$sql = "SELECT * FROM pos_product $whereClause ORDER BY nombre LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$pageTitle = "Gestión de Stock";
include 'includes/header.php';
?>

<div x-data="inventoryApp()">
    
    <?php if($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Gestión de Stock</h1>
            
            <div class="flex items-center space-x-2">
                <!-- Search Form -->
                <form method="get" class="flex">
                    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" 
                           placeholder="Buscar producto..." 
                           class="rounded-l-lg border-y border-l border-gray-300 p-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700">
                        Buscar
                    </button>
                    <?php if($query): ?>
                        <a href="inventory.php" class="ml-2 bg-gray-200 text-gray-700 px-3 py-2 rounded hover:bg-gray-300">X</a>
                    <?php endif; ?>
                </form>

                <!-- Add Product Button -->
                <button @click="newProduct()" 
                        class="bg-green-600 text-white p-2 rounded-lg hover:bg-green-700 shadow flex items-center gap-2">
                     <span class="font-bold px-2">Nuevo Producto</span>
                </button>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach($products as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($p['codigo_barra']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">$<?= number_format($p['precio'], 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?= $p['stock'] < 5 ? 'text-red-600 font-bold' : 'text-green-600' ?>">
                                <?= $p['stock'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 font-bold"
                                      @click="editProduct({
                                          id: '<?= $p['id'] ?>',
                                          codigo_barra: '<?= $p['codigo_barra'] ?>',
                                          nombre: `<?= addslashes($p['nombre']) ?>`,
                                          precio: '<?= $p['precio'] ?>',
                                          stock: '<?= $p['stock'] ?>'
                                      })">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($products)): ?>
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No se encontraron productos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
             <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
                <!-- Simple Pagination -->
                <div class="flex-1 flex justify-between">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&q=<?= htmlspecialchars($query) ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Anterior</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    
                    <span class="text-sm text-gray-700 pt-2">Página <?= $page ?> de <?= $totalPages ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&q=<?= htmlspecialchars($query) ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Siguiente</a>
                    <?php else: ?>
                         <span></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODAL -->
    <div x-cloak x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="open = false"></div>
             
             <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
             
             <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="product_id" x-model="form.id">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    <span x-text="isEditMode ? 'Editar Producto' : 'Nuevo Producto'"></span>
                                </h3>
                                <div class="mt-4 space-y-4">
                                    
                                    <!-- Campos Formulario -->
                                    <div>
                                        <label class="block text-gray-700 font-bold mb-1">Código de Barra</label>
                                        <input type="text" name="codigo_barra" x-model="form.codigo_barra" required 
                                               :readonly="isEditMode"
                                               :class="{'bg-gray-100': isEditMode}"
                                               class="w-full px-3 py-2 border rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                     <div>
                                        <label class="block text-gray-700 font-bold mb-1">Nombre</label>
                                        <input type="text" name="nombre" x-model="form.nombre" required 
                                               class="w-full px-3 py-2 border rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                     <div>
                                        <label class="block text-gray-700 font-bold mb-1">Precio ($)</label>
                                        <input type="number" step="0.01" name="precio" x-model="form.precio" required 
                                               class="w-full px-3 py-2 border rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    
                                    <!-- Stock Logic -->
                                    <div x-data="{ resetStock: false }">
                                         <div class="flex justify-between items-center mb-1">
                                             <label class="block text-gray-700 font-bold">Añadir Stock</label>
                                             
                                             <template x-if="isEditMode">
                                                 <div class="flex items-center">
                                                     <input type="checkbox" name="reset_stock" id="reset_stock" x-model="resetStock" class="mr-2">
                                                     <label for="reset_stock" class="text-sm text-red-600 cursor-pointer">Vaciar Stock (0)</label>
                                                 </div>
                                             </template>
                                         </div>
                                         <input type="number" name="stock_add" 
                                                class="w-full px-3 py-2 border rounded shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                :disabled="resetStock"
                                                :class="{'bg-gray-100 cursor-not-allowed': resetStock}"
                                                :value="resetStock ? '' : ''" 
                                                placeholder="0">
                                         
                                         <template x-if="isEditMode">
                                             <p class="text-xs text-gray-500 mt-1">Stock Actual: <strong x-text="form.stock"></strong>. Se sumará lo ingresado.</p>
                                         </template>
                                          <template x-if="!isEditMode">
                                             <p class="text-xs text-gray-500 mt-1">Stock inicial para el nuevo producto.</p>
                                         </template>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse justify-between">
                         <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                        
                        <!-- Delete Button only in Edit Mode -->
                        <template x-if="isEditMode">
                             <button type="button" @click="confirmDelete()" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                                Eliminar
                            </button>
                        </template>
                    </div>
                </form>
                
                <!-- Hidden Delete Form -->
                <form method="post" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" x-model="form.id">
                </form>
             </div>
        </div>
    </div>
</div>

<script>
function inventoryApp() {
    return {
        open: false,
        isEditMode: false,
        form: {
            id: '',
            codigo_barra: '',
            nombre: '',
            precio: '',
            stock: 0
        },
        
        newProduct() {
            this.form = { id: '', codigo_barra: '', nombre: '', precio: '', stock: 0 };
            this.isEditMode = false;
            this.open = true;
        },
        
        editProduct(product) {
            this.form = { ...product };
            this.isEditMode = true;
            this.open = true;
        },
        
        confirmDelete() {
            if(confirm('¿Estás seguro de eliminar este producto?')) {
                document.getElementById('deleteForm').submit();
            }
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
