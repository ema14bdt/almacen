<?php
// public/login.php
session_start();
include 'includes/auth.php'; // For csrf helper

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Verify CSRF
    $token = $_POST['csrf_token'] ?? '';
    if(!hash_equals($_SESSION['csrf_token'], $token)){
        $error = "Sesión inválida (CSRF). Recargue la página.";
    } else {
        // Hardcoded check for migration simplicity
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Usuario o contraseña inválidos.";
        }
    }
}

$pageTitle = "Iniciar Sesión";
include 'includes/header.php'; 
?>

<div class="flex items-center justify-center min-h-[50vh]">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md overflow-hidden">
        <div class="py-4 px-6 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 text-center">Iniciar Sesión</h2>
        </div>
        <div class="p-6">
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-500 p-3 rounded text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-gray-700 font-bold mb-2">Usuario</label>
                    <input type="text" name="username" autofocus 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-2">Contraseña</label>
                    <input type="password" name="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                    Entrar
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
