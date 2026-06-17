<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Control de Administración - Dashboard Central
// ====================================================================

session_start();
require_once '../conexion.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener estadísticas detalladas de la base de datos
try {
    $count_proy = $conn->query("SELECT COUNT(*) FROM proyectos_scratch")->fetchColumn();
    $count_gal = $conn->query("SELECT COUNT(*) FROM galeria_fotos")->fetchColumn();
    $count_plan = $conn->query("SELECT COUNT(*) FROM planificaciones")->fetchColumn();
    $count_alum = $conn->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
    $count_test = $conn->query("SELECT COUNT(*) FROM testimonios_bti")->fetchColumn();
    $count_msg = $conn->query("SELECT COUNT(*) FROM contacto_mensajes")->fetchColumn();

    // Obtener los últimos 5 mensajes de contacto
    $stmt_msgs = $conn->query("SELECT * FROM contacto_mensajes ORDER BY fecha_envio DESC LIMIT 5");
    $recent_messages = $stmt_msgs->fetchAll();
} catch (PDOException $e) {
    die("Error de base de datos en dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fc;
        }
        .admin-nav {
            background-color: #0b2545;
        }
        .stat-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(11, 37, 69, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(11, 37, 69, 0.08);
        }
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
            border-radius: 6px;
        }
        .fs-7 {
            font-size: 0.9rem;
        }
        .fs-8 {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <!-- Menú de Administración -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-nav shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
                <span class="fw-bold text-warning">CRECE Panel</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAdmin" aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAdmin">
                <ul class="navbar-nav ms-auto gap-1">
                    <li class="nav-item"><a class="nav-link px-3 active sidebar-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link px-3 sidebar-link" href="proyectos.php">Proyectos</a></li>
                    <li class="nav-item"><a class="nav-link px-3 sidebar-link" href="galeria.php">Galería</a></li>
                    <li class="nav-item"><a class="nav-link px-3 sidebar-link" href="planificaciones.php">Planificaciones</a></li>
                    <li class="nav-item"><a class="nav-link px-3 sidebar-link" href="asistencia.php">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link px-3 sidebar-link" href="testimonios.php">Testimonios</a></li>
                    <li class="nav-item ms-lg-4"><a class="btn btn-outline-light btn-sm px-3 py-1.5 fs-8 text-uppercase fw-bold" href="../index.php" target="_blank">Ver Web</a></li>
                    <li class="nav-item"><a class="btn btn-danger btn-sm px-3 py-1.5 fs-8 text-uppercase fw-bold ms-lg-1" href="logout.php">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3">
                <div>
                    <h1 class="fw-extrabold text-primary mb-1">¡Bienvenido, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</h1>
                    <p class="text-muted mb-0">Gestión de datos del Proyecto Integrador de Scratch para 5° y 6° grado.</p>
                </div>
                <div class="bg-white px-3 py-2 rounded-3 shadow-sm border text-muted fs-8 fw-semibold">
                    Rol: <span class="badge bg-success">Administrador</span>
                </div>
            </div>

            <!-- Fila de Tarjetas de Estadísticas -->
            <div class="row g-4 mb-5">
                <!-- Proyectos Scratch -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Proyectos Scratch</h5>
                            <div class="icon-box bg-primary text-warning">🐱</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_proy ?></h2>
                        <a href="proyectos.php" class="text-decoration-none fs-8 fw-semibold text-primary mt-3 d-inline-flex align-items-center gap-1">
                            Gestionar Proyectos
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Galería de Fotos -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Galería de Fotos</h5>
                            <div class="icon-box bg-success-subtle text-success">📷</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_gal ?></h2>
                        <a href="galeria.php" class="text-decoration-none fs-8 fw-semibold text-success mt-3 d-inline-flex align-items-center gap-1">
                            Gestionar Fotos
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Planificaciones -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Planificaciones</h5>
                            <div class="icon-box bg-warning-subtle text-warning">📑</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_plan ?></h2>
                        <a href="planificaciones.php" class="text-decoration-none fs-8 fw-semibold text-warning mt-3 d-inline-flex align-items-center gap-1">
                            Gestionar Guías
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Alumnos Registrados -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Alumnos Primaria</h5>
                            <div class="icon-box bg-info-subtle text-info">👥</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_alum ?></h2>
                        <a href="asistencia.php" class="text-decoration-none fs-8 fw-semibold text-info mt-3 d-inline-flex align-items-center gap-1">
                            Controlar Asistencia
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Testimonios de 3° BTI -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Testimonios 3° BTI</h5>
                            <div class="icon-box bg-purple-subtle text-purple">💬</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_test ?></h2>
                        <a href="testimonios.php" class="text-decoration-none fs-8 fw-semibold text-purple mt-3 d-inline-flex align-items-center gap-1">
                            Gestionar Testimonios
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Mensajes de Contacto Recibidos -->
                <div class="col-sm-6 col-lg-4">
                    <div class="card stat-card h-100 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-muted fs-7 fw-semibold mb-0">Mensajes de Contacto</h5>
                            <div class="icon-box bg-danger-subtle text-danger">✉️</div>
                        </div>
                        <h2 class="fw-bold text-primary mb-1"><?= $count_msg ?></h2>
                        <span class="text-muted fs-8 mt-3 d-inline-flex align-items-center gap-1">
                            Recibidos desde el portal
                        </span>
                    </div>
                </div>
            </div>

            <!-- Fila para Mensajes de Contacto Recientes -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <h3 class="fw-bold text-primary mb-4 d-flex align-items-center gap-2">
                            <span class="fs-4">📩</span>
                            Mensajes de Contacto Recientes
                        </h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-7">
                                <thead class="table-light text-primary">
                                    <tr>
                                        <th class="px-3 py-2.5">Nombre</th>
                                        <th class="py-2.5">Correo</th>
                                        <th class="py-2.5">Asunto</th>
                                        <th class="py-2.5">Mensaje</th>
                                        <th class="px-3 py-2.5">Fecha Envío</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_messages) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No se han recibido mensajes por el momento.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_messages as $msg): ?>
                                            <tr>
                                                <td class="px-3 py-2.5 fw-bold text-primary"><?= htmlspecialchars($msg['nombre']) ?></td>
                                                <td><?= htmlspecialchars($msg['email']) ?></td>
                                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($msg['asunto']) ?></td>
                                                <td class="text-muted text-justify" style="max-width: 350px; white-space: normal;"><?= nl2br(htmlspecialchars($msg['mensaje'])) ?></td>
                                                <td class="px-3 py-2.5 text-muted"><?= date('d/m/Y H:i', strtotime($msg['fecha_envio'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Bootstrap 5 Bundle JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
