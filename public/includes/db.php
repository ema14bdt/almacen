<?php
// public/includes/db.php

try {
    // Database is in the parent directory relative to public/
    // Adjust path if necessary. Ideally absolute path or relative to __DIR__
    $dbPath = __DIR__ . '/../../db.sqlite3';
    
    if (!file_exists($dbPath)) {
        die("Database file not found at: " . $dbPath);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    // Enable exceptions for errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch as associative array by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
