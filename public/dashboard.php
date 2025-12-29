<?php
// public/dashboard.php
require_once 'includes/db.php';
require_once 'includes/auth.php';

require_login();

// Date Filter
$dateStr = $_GET['date'] ?? date('Y-m-d');
$selectedDate = $dateStr;

// Stats Logic
try {
    // 1. Sales Today (Total sum)
    $stmt = $pdo->prepare("SELECT SUM(total) as total FROM pos_sale WHERE date(fecha) = date('now', 'localtime')"); 
    // Note: SQLite 'now' is UTC. 'localtime' modifier converts it if system time is set, 
    // but PHP date('Y-m-d') is better if we query against string dates stored in DB.
    // Django stores DateTime. format: "2024-01-01 12:00:00" etc.
    // Let's use simpler query: "WHERE fecha LIKE '2025-01-01%'" for today.
    
    $todayPrefix = date('Y-m-d');
    $monthPrefix = date('Y-m');
    
    $stmtToday = $pdo->prepare("SELECT SUM(total) FROM pos_sale WHERE fecha LIKE :prefix");
    $stmtToday->execute([':prefix' => $todayPrefix . '%']);
    $salesToday = $stmtToday->fetchColumn() ?: 0;
    
    // 2. Sales Month
    $stmtMonth = $pdo->prepare("SELECT SUM(total) FROM pos_sale WHERE fecha LIKE :prefix");
    $stmtMonth->execute([':prefix' => $monthPrefix . '%']);
    $salesMonth = $stmtMonth->fetchColumn() ?: 0;
    
    // 3. Sales History for Selected Date
    // Query: WHERE date(fecha) = selectedDate. Since formatting varies, LIKE is safer if we stick to 'YYYY-MM-DD%'
    $stmtHistory = $pdo->prepare("SELECT * FROM pos_sale WHERE fecha LIKE :datePrefix ORDER BY fecha DESC");
    $stmtHistory->execute([':datePrefix' => $selectedDate . '%']);
    $salesHistory = $stmtHistory->fetchAll();
    
    // 4. Monthly History (Chart/Table) - Last 12 months
    // In SQLite, we can use strftime('%Y-%m', fecha)
    $stmtChart = $pdo->query("
        SELECT strftime('%Y-%m', fecha) as month, SUM(total) as total 
        FROM pos_sale 
        GROUP BY month 
        ORDER BY month DESC 
        LIMIT 12
    ");
    $historyData = $stmtChart->fetchAll();

} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}

$pageTitle = "Panel de Ventas";
include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Panel de Ventas</h1>
    
    <!-- Date Filter & Sales Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
        <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-800">Ventas del Día</h2>
            <form method="get" class="flex items-center space-x-2 mt-4 md:mt-0">
                <label for="date" class="text-sm font-medium text-gray-700">Fecha:</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" 
                       onchange="this.form.submit()"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Venta</th>
                        <th class="px-6 py-3"></th> 
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($salesHistory)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No hay ventas registradas para esta fecha.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($salesHistory as $sale): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <!-- Helper PHP to parse date -->
                            <?php $dt = new DateTime($sale['fecha']); ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $dt->format('H:i') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $sale['metodo_pago'] === 'Efectivo' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= htmlspecialchars($sale['metodo_pago']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">$<?= number_format($sale['total'], 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#<?= $sale['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <!-- Could add a view detail button here later -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-medium uppercase">Ventas de Hoy</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">$<?= number_format($salesToday, 2) ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
            <h3 class="text-gray-500 text-sm font-medium uppercase">Ventas del Mes</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">$<?= number_format($salesMonth, 2) ?></p>
        </div>
    </div>

    <!-- History Chart/Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-800">Historial Mensual (Últimos 12 Meses)</h2>
        </div>
        <?php if ($historyData): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Ventas</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($historyData as $row): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['month'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">$<?= number_format($row['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="p-6 text-gray-500">No hay historial disponible.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
