<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Assume auth.php is included by the parent page if needed, or we include it here.
include_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="es" class="overflow-y-scroll">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Almacén EGP' ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased h-screen flex flex-col">

    <nav class="bg-black text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="dashboard.php" class="text-xl font-bold hover:text-blue-100">Almacén EGP</a>
            <div class="space-x-4">
                <?php if(is_logged_in()): ?>
                    <a href="dashboard.php" class="hover:underline">Panel de Ventas</a>
                    <a href="inventory.php" class="hover:underline">Stock</a>
                    <a href="pos.php" class="hover:underline font-bold bg-white text-black px-3 py-1 rounded">POS</a>
                    <a href="logout.php" class="text-sm text-red-300 hover:text-red-100 ml-4">Salir</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-6">
        <!-- Toasts Container -->
        <div id="toast-container" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md space-y-2 pointer-events-none">
            <!-- Toasts will be injected here via JS/HTMX -->
        </div>
