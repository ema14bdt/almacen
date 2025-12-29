    </main>

    <footer class="bg-gray-800 text-gray-600 text-center py-2 text-sm mt-auto">
        &copy; <?= date('Y') ?> Almacén EGP - Sistema de gestión de productos y ventas realizado por <a href="https://emanuel-romero.vercel.app/" target="_blank">Emanuel Romero</a>
    </footer>

    <script>
        document.body.addEventListener('htmx:configRequest', (event) => {
            // Send PHP CSRF token if needed, usually passed via headers or form.
            // For now we might not strictly need it for all GETs, but for POSTs:
            event.detail.headers['X-CSRF-Token'] = '<?= get_csrf_token() ?>';
        });
    </script>
</body>
</html>
