<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Cierre de Sesión Administrativa Seguro
// ====================================================================

session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también se puede borrar
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la pantalla de login
header('Location: login.php');
exit;
?>
