<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Gestión CRUD - Planificaciones de Clase con carga de PDF
// ====================================================================

session_start();
require_once '../conexion.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$msg_success = null;
$msg_error = null;

// --- ELIMINAR PLANIFICACIÓN ---
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    try {
        $stmt_file = $conn->prepare("SELECT ruta_archivo FROM planificaciones WHERE id = ?");
        $stmt_file->execute([$id_del]);
        $ruta = $stmt_file->fetchColumn();

        if ($ruta && file_exists('../' . $ruta)) {
            unlink('../' . $ruta);
        }

        $stmt = $conn->prepare("DELETE FROM planificaciones WHERE id = ?");
        $stmt->execute([$id_del]);
        $msg_success = "Planificación eliminada correctamente.";
    } catch (PDOException $e) {
        $msg_error = "Error al eliminar: " . $e->getMessage();
    }
}

// --- CREAR O EDITAR PLANIFICACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = isset($_POST['id'])          ? (int)$_POST['id']          : 0;
    $titulo    = isset($_POST['titulo'])      ? trim($_POST['titulo'])      : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $grado     = isset($_POST['grado'])       ? $_POST['grado']            : '5';
    $fecha     = isset($_POST['fecha'])       ? $_POST['fecha']            : date('Y-m-d');

    if (empty($titulo)) {
        $msg_error = "El título es obligatorio.";
    } else {
        $ruta_archivo = null;

        // Manejo de subida de PDF
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $msg_error = "Solo se permiten archivos PDF.";
            } else {
                $new_name  = 'plan_' . uniqid() . '.pdf';
                $upload_dir = '../uploads/pdf/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $new_name)) {
                    $ruta_archivo = 'uploads/pdf/' . $new_name;
                    // Borrar PDF anterior si es edición
                    if ($id > 0) {
                        $stmt_old = $conn->prepare("SELECT ruta_archivo FROM planificaciones WHERE id = ?");
                        $stmt_old->execute([$id]);
                        $old = $stmt_old->fetchColumn();
                        if ($old && file_exists('../' . $old)) unlink('../' . $old);
                    }
                } else {
                    $msg_error = "Error al mover el archivo al servidor.";
                }
            }
        }

        if ($msg_error === null) {
            try {
                if ($id > 0) {
                    if ($ruta_archivo !== null) {
                        $stmt = $conn->prepare("UPDATE planificaciones SET titulo=?,descripcion=?,ruta_archivo=?,grado=?,fecha=? WHERE id=?");
                        $stmt->execute([$titulo, $descripcion, $ruta_archivo, $grado, $fecha, $id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE planificaciones SET titulo=?,descripcion=?,grado=?,fecha=? WHERE id=?");
                        $stmt->execute([$titulo, $descripcion, $grado, $fecha, $id]);
                    }
                    $msg_success = "Planificación actualizada correctamente.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO planificaciones (titulo,descripcion,ruta_archivo,grado,fecha) VALUES (?,?,?,?,?)");
                    $stmt->execute([$titulo, $descripcion, $ruta_archivo ?: '', $grado, $fecha]);
                    $msg_success = "Nueva planificación registrada con éxito.";
                }
            } catch (PDOException $e) {
                $msg_error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}

// --- DATOS PARA EDICIÓN ---
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt_e = $conn->prepare("SELECT * FROM planificaciones WHERE id = ?");
    $stmt_e->execute([(int)$_GET['edit']]);
    $edit_item = $stmt_e->fetch();
}

// --- LISTADO ---
$listado = $conn->query("SELECT * FROM planificaciones ORDER BY fecha DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Planificaciones</title>
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
                <li class="nav-item"><a class="nav-link active" href="planificaciones.php">Planificaciones</a></li>
                <li class="nav-item"><a class="nav-link" href="asistencia.php">Asistencia</a></li>
                <li class="nav-item"><a class="nav-link" href="testimonios.php">Testimonios</a></li>
                <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm fs-8 fw-bold text-uppercase" href="../index.php" target="_blank">Ver Web</a></li>
                <li class="nav-item ms-1"><a class="btn btn-danger btn-sm fs-8 fw-bold text-uppercase" href="logout.php">Salir</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="fw-extrabold mb-4" style="color:#0b2545;">Gestión de Planificaciones</h1>

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
            <!-- FORMULARIO -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0b2545;"><?= $edit_item ? 'Editar Planificación' : 'Nueva Planificación' ?></h5>
                    <form method="POST" action="planificaciones.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $edit_item ? $edit_item['id'] : 0 ?>">

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Título</label>
                            <input type="text" name="titulo" class="form-control" required
                                value="<?= $edit_item ? htmlspecialchars($edit_item['titulo']) : '' ?>"
                                placeholder="Ej: Unidad 1 – Introducción a Scratch">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"
                                placeholder="Resumen de los objetivos y actividades..."><?= $edit_item ? htmlspecialchars($edit_item['descripcion']) : '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Grado</label>
                            <select name="grado" class="form-select">
                                <option value="5" <?= ($edit_item && $edit_item['grado'] === '5') ? 'selected' : '' ?>>5° Grado</option>
                                <option value="6" <?= ($edit_item && $edit_item['grado'] === '6') ? 'selected' : '' ?>>6° Grado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Fecha</label>
                            <input type="date" name="fecha" class="form-control"
                                value="<?= $edit_item ? $edit_item['fecha'] : date('Y-m-d') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fs-8 fw-bold text-muted">Archivo PDF</label>
                            <input type="file" name="pdf_file" class="form-control" accept=".pdf"
                                <?= $edit_item ? '' : 'required' ?>>
                            <?php if ($edit_item && !empty($edit_item['ruta_archivo'])): ?>
                                <div class="form-text fs-8 text-success">
                                    Actual: <a href="../<?= $edit_item['ruta_archivo'] ?>" target="_blank">Ver PDF</a>
                                    (dejar vacío para no cambiar)
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fs-7 fw-bold text-uppercase">
                            <?= $edit_item ? 'Guardar Cambios' : 'Registrar Planificación' ?>
                        </button>
                        <?php if ($edit_item): ?>
                            <a href="planificaciones.php" class="btn btn-secondary w-100 mt-2 py-2 fs-7 fw-bold text-uppercase">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- LISTADO -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0b2545;">Planificaciones Registradas</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle fs-7 mb-0">
                            <thead class="table-light" style="color:#0b2545;">
                                <tr>
                                    <th class="px-3">Título</th>
                                    <th>Grado</th>
                                    <th>Fecha</th>
                                    <th>PDF</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($listado)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No hay planificaciones registradas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($listado as $item): ?>
                                        <tr>
                                            <td class="px-3 fw-bold" style="color:#0b2545;"><?= htmlspecialchars($item['titulo']) ?></td>
                                            <td>
                                                <span class="badge <?= $item['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?>">
                                                    <?= $item['grado'] ?>° Grado
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($item['fecha'])) ?></td>
                                            <td>
                                                <?php if (!empty($item['ruta_archivo'])): ?>
                                                    <a href="../<?= htmlspecialchars($item['ruta_archivo']) ?>" target="_blank" class="badge bg-success text-decoration-none">Ver PDF</a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin PDF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="planificaciones.php?edit=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 fs-8 fw-semibold">Editar</a>
                                                <a href="planificaciones.php?delete=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2 fs-8 fw-semibold ms-1"
                                                    onclick="return confirm('¿Eliminar esta planificación de forma definitiva?')">Eliminar</a>
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
