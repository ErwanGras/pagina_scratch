<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Archivo de Conexión a la Base de Datos usando PDO (XAMPP Listo)
// ====================================================================

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'crece_scratch';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción es mejor no mostrar el mensaje directo por seguridad,
    // pero para este proyecto escolar es excelente para depurar rápidamente.
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>
