<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Pantalla de Inicio de Sesión Administrativa (Segura)
// ====================================================================

session_start();
require_once '../conexion.php';

// Si ya inició sesión, redirigir al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($usuario) || empty($password)) {
        $error_msg = "Por favor, introduce el usuario y la contraseña.";
    } else {
        try {
            // Buscar usuario en la base de datos
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();

            // Verificar si el usuario existe y la contraseña es correcta
            if ($user && password_verify($password, $user['password'])) {
                // Iniciar sesión y guardar variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $user['usuario'];
                $_SESSION['admin_name'] = $user['nombre'];
                $_SESSION['admin_id'] = $user['id'];

                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_msg = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error_msg = "Error al intentar iniciar sesión: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Iniciar Sesión</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f7fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(11, 37, 69, 0.1);
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background-color: #0b2545;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .btn-primary {
            background-color: #0b2545;
            border-color: #0b2545;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #134074;
            border-color: #134074;
        }
        .text-warning-color {
            color: #d4af37;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mb-2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <h3 class="fw-bold mb-1">Panel Administrativo</h3>
            <p class="text-white-50 fs-8 mb-0">Colegio CRECE - 3° BTI</p>
        </div>
        <div class="card-body p-4 p-md-5">
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 fs-8 py-2.5 px-3 mb-4" role="alert">
                    <?= $error_msg ?>
                    <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label fs-8 fw-semibold text-muted">Usuario</label>
                    <input type="text" name="usuario" class="form-control" id="username" required placeholder="Ingresa tu usuario..." autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fs-8 fw-semibold text-muted">Contraseña</label>
                    <input type="password" name="password" class="form-control" id="password" required placeholder="Ingresa tu contraseña...">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2.5 text-uppercase fs-7 tracking-wide mb-3">Iniciar Sesión</button>
                <div class="text-center">
                    <a href="../index.php" class="text-decoration-none text-muted fs-8">Volver al portal público</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
