<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Gestión - Registro de Asistencia Diaria
// ====================================================================

session_start();
require_once '../conexion.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$msg_success = null;
$msg_error    = null;

// --- ELIMINAR REGISTRO ---
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM asistencia WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        $msg_success = "Registro de asistencia eliminado.";
    } catch (PDOException $e) {
        $msg_error = "Error al eliminar: " . $e->getMessage();
    }
}

// --- REGISTRAR ASISTENCIA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno_id   = isset($_POST['alumno_id'])   ? (int)$_POST['alumno_id']         : 0;
    $fecha       = isset($_POST['fecha'])        ? $_POST['fecha']                  : date('Y-m-d');
    $estado      = isset($_POST['estado'])       ? $_POST['estado']                 : 'Presente';
    $observacion = isset($_POST['observacion'])  ? trim($_POST['observacion'])       : '';

    if ($alumno_id === 0) {
        $msg_error = "Selecciona un alumno válido.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO asistencia (alumno_id, fecha, estado, observacion) VALUES (?, ?, ?, ?)");
            $stmt->execute([$alumno_id, $fecha, $estado, $observacion]);
            $msg_success = "Asistencia registrada correctamente.";
        } catch (PDOException $e) {
            $msg_error = "Error al registrar asistencia: " . $e->getMessage();
        }
    }
}

// --- FILTROS CONSULTA ---
$grado_f = isset($_GET['grado_f']) ? $_GET['grado_f'] : 'all';
$fecha_f = isset($_GET['fecha_f']) ? $_GET['fecha_f'] : '';

$query = "SELECT a.*, al.nombre, al.apellido, al.grado
          FROM asistencia a
          JOIN alumnos al ON a.alumno_id = al.id
          WHERE 1=1";
$params = [];

if ($grado_f === '5' || $grado_f === '6') {
    $query .= " AND al.grado = ?";
    $params[] = $grado_f;
}
if ($fecha_f !== '') {
    $query .= " AND a.fecha = ?";
    $params[] = $fecha_f;
}
$query .= " ORDER BY a.fecha DESC, al.apellido ASC";

$stmt_list = $conn->prepare($query);
$stmt_list->execute($params);
$registros = $stmt_list->fetchAll();

// --- LISTA DE ALUMNOS (para el formulario de registro) ---
$alumnos = $conn->query("SELECT * FROM alumnos WHERE activo = 1 ORDER BY grado, apellido, nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fc; }
        .admin-nav { background-color: #0b2545; }
        .fs-7 { font-size: 0.9rem; } .fs-8 { font-size: 0.8rem; }
        .btn-primary { background-color: #0b2545 !important; border-color: #0b2545 !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark admin-nav shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="dashboard.php">CRECE Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navAdmin">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="proyectos.php">Proyectos</a></li>
                <li class="nav-item"><a class="nav-link" href="galeria.php">Galería</a></li>
                <li class="nav-item"><a class="nav-link" href="planificaciones.php">Planificaciones</a></li>
                <li class="nav-item"><a class="nav-link active" href="asistencia.php">Asistencia</a></li>
                <li class="nav-item"><a class="nav-link" href="testimonios.php">Testimonios</a></li>
                <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm fs-8 fw-bold text-uppercase" href="../index.php" target="_blank">Ver Web</a></li>
                <li class="nav-item ms-1"><a class="btn btn-danger btn-sm fs-8 fw-bold text-uppercase" href="logout.php">Salir</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="fw-extrabold mb-4" style="color:#0b2545;">Registro de Asistencia</h1>

        <?php if ($msg_success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 fs-7" role="alert">
                <?= $msg_success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($msg_error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 fs-7" role="alert">
                <?= $msg_error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- FORMULARIO DE REGISTRO -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0b2545;">Registrar Asistencia</h5>
                    <form method="POST" action="asistencia.php">
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Alumno</label>
                            <select name="alumno_id" class="form-select" required>
                                <option value="">-- Seleccionar alumno --</option>
                                <?php foreach ($alumnos as $al): ?>
                                    <option value="<?= $al['id'] ?>">
                                        <?= htmlspecialchars("{$al['apellido']}, {$al['nombre']} ({$al['grado']}° Grado)") ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Fecha de Clase</label>
                            <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="Presente">✅ Presente</option>
                                <option value="Ausente">❌ Ausente</option>
                                <option value="Justificado">⚠️ Justificado</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fs-8 fw-bold text-muted">Observación (Opcional)</label>
                            <input type="text" name="observacion" class="form-control"
                                placeholder="Ej: Trabajó en bucles, presentó proyecto...">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-7 fw-bold text-uppercase">
                            Registrar Asistencia
                        </button>
                    </form>
                </div>
            </div>

            <!-- CONSULTA CON FILTROS + TABLA -->
            <div class="col-lg-8">
                <!-- Filtros de consulta -->
                <form method="GET" action="asistencia.php" class="card border-0 bg-primary text-white p-3 rounded-4 shadow-sm mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label fs-8 fw-bold">Grado</label>
                            <select name="grado_f" class="form-select form-select-sm border-0">
                                <option value="all" <?= $grado_f === 'all' ? 'selected' : '' ?>>Todos</option>
                                <option value="5"   <?= $grado_f === '5'   ? 'selected' : '' ?>>5° Grado</option>
                                <option value="6"   <?= $grado_f === '6'   ? 'selected' : '' ?>>6° Grado</option>
                            </select>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label fs-8 fw-bold">Fecha</label>
                            <input type="date" name="fecha_f" class="form-control form-control-sm border-0"
                                value="<?= htmlspecialchars($fecha_f) ?>">
                        </div>
                        <div class="col-sm-3 d-flex gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm w-100">Buscar</button>
                            <a href="asistencia.php" class="btn btn-outline-light btn-sm w-100">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0b2545;">
                        Registros <?= count($registros) > 0 ? "(" . count($registros) . ")" : "" ?>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle fs-7 mb-0">
                            <thead class="table-light" style="color:#0b2545;">
                                <tr>
                                    <th class="px-3">Alumno</th>
                                    <th>Grado</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($registros)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron registros con los filtros seleccionados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($registros as $reg): ?>
                                        <tr>
                                            <td class="px-3 fw-bold" style="color:#0b2545;">
                                                <?= htmlspecialchars("{$reg['apellido']}, {$reg['nombre']}") ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $reg['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?>">
                                                    <?= $reg['grado'] ?>° Grado
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($reg['fecha'])) ?></td>
                                            <td>
                                                <?php if ($reg['estado'] === 'Presente'): ?>
                                                    <span class="text-success fw-bold fs-8">✅ Presente</span>
                                                <?php elseif ($reg['estado'] === 'Ausente'): ?>
                                                    <span class="text-danger fw-bold fs-8">❌ Ausente</span>
                                                <?php else: ?>
                                                    <span class="text-warning fw-bold fs-8">⚠️ Justificado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted"><?= htmlspecialchars($reg['observacion'] ?: '—') ?></td>
                                            <td>
                                                <a href="asistencia.php?delete=<?= $reg['id'] ?>&grado_f=<?= $grado_f ?>&fecha_f=<?= $fecha_f ?>"
                                                    class="btn btn-sm btn-outline-danger py-1 px-2 fs-8"
                                                    onclick="return confirm('¿Eliminar este registro de asistencia?')">✕</a>
                                            </td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
