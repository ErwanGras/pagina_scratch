<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Archivo de Conexión a la Base de Datos usando PDO (XAMPP Listo)
// ====================================================================

$host = 'localhost';
$db   = 'crece_scratch';
$user = 'root';
$pass = ''; // Por defecto vacío en XAMPP y Laragon
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
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
