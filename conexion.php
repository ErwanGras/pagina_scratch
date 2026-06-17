<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Archivo de Conexión a la Base de Datos usando PDO (XAMPP Listo)
// ====================================================================

// Detectamos si estamos ejecutando en Render (producción)
$isRender = getenv('RENDER') !== false;

if ($isRender) {
    // Usamos SQLite en Render para no depender de ningún servicio externo de base de datos
    $dbPath = __DIR__ . '/database.sqlite';
    $dsn = "sqlite:$dbPath";
    $user = null;
    $pass = null;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
} else {
    // Usamos MySQL local (XAMPP / Laragon)
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
}

try {
    $conn = new PDO($dsn, $user, $pass, $options);
    if ($isRender) {
        // Habilitar soporte de claves foráneas en SQLite
        $conn->exec("PRAGMA foreign_keys = ON;");
    }

    // Redirección automática al setup si la base de datos está vacía (no tiene tablas)
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage !== 'setup.php') {
        try {
            $conn->query("SELECT 1 FROM usuarios LIMIT 1");
        } catch (\PDOException $e) {
            // Determinar prefijo según si el archivo actual está en la subcarpeta admin/
            $prefix = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
            header("Location: " . $prefix . "setup.php");
            exit;
        }
    }
} catch (\PDOException $e) {
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>
