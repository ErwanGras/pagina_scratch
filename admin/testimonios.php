<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Gestión CRUD - Testimonios de Alumnos del 3° BTI
// ====================================================================

session_start();
require_once '../conexion.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$msg_success = null;
$msg_error    = null;

// --- ELIMINAR TESTIMONIO ---
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    try {
        $stmt_file = $conn->prepare("SELECT ruta_foto FROM testimonios_bti WHERE id = ?");
        $stmt_file->execute([$id_del]);
        $ruta_foto = $stmt_file->fetchColumn();

        if ($ruta_foto && file_exists('../' . $ruta_foto)) {
            unlink('../' . $ruta_foto);
        }

        $stmt = $conn->prepare("DELETE FROM testimonios_bti WHERE id = ?");
        $stmt->execute([$id_del]);
        $msg_success = "Testimonio eliminado correctamente.";
    } catch (PDOException $e) {
        $msg_error = "Error al eliminar: " . $e->getMessage();
    }
}

// --- CREAR O EDITAR TESTIMONIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id            = isset($_POST['id'])             ? (int)$_POST['id']             : 0;
    $nombre_alumno = isset($_POST['nombre_alumno'])  ? trim($_POST['nombre_alumno'])  : '';
    $testimonio    = isset($_POST['testimonio'])      ? trim($_POST['testimonio'])      : '';
    $aprendizaje   = isset($_POST['aprendizaje'])     ? trim($_POST['aprendizaje'])     : '';
    $dificultad    = isset($_POST['dificultad'])      ? trim($_POST['dificultad'])      : '';

    if (empty($nombre_alumno) || empty($testimonio)) {
        $msg_error = "El nombre del alumno y el testimonio son obligatorios.";
    } else {
        $ruta_foto = null;

        // Manejo de subida de foto del alumno
        if (isset($_FILES['foto_file']) && $_FILES['foto_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                $msg_error = "Solo se permiten imágenes (JPG, PNG, GIF, WEBP).";
            } else {
                $new_name   = 'alumno_' . uniqid() . '.' . $ext;
                $upload_dir = '../uploads/img/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (move_uploaded_file($_FILES['foto_file']['tmp_name'], $upload_dir . $new_name)) {
                    $ruta_foto = 'uploads/img/' . $new_name;
                    // Borrar foto anterior si es edición
                    if ($id > 0) {
                        $stmt_old = $conn->prepare("SELECT ruta_foto FROM testimonios_bti WHERE id = ?");
                        $stmt_old->execute([$id]);
                        $old_foto = $stmt_old->fetchColumn();
                        if ($old_foto && file_exists('../' . $old_foto)) unlink('../' . $old_foto);
                    }
                } else {
                    $msg_error = "Error al subir la foto al servidor.";
                }
            }
        }

        if ($msg_error === null) {
            try {
                if ($id > 0) {
                    if ($ruta_foto !== null) {
                        $stmt = $conn->prepare("UPDATE testimonios_bti SET nombre_alumno=?,testimonio=?,aprendizaje=?,dificultad=?,ruta_foto=? WHERE id=?");
                        $stmt->execute([$nombre_alumno, $testimonio, $aprendizaje, $dificultad, $ruta_foto, $id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE testimonios_bti SET nombre_alumno=?,testimonio=?,aprendizaje=?,dificultad=? WHERE id=?");
                        $stmt->execute([$nombre_alumno, $testimonio, $aprendizaje, $dificultad, $id]);
                    }
                    $msg_success = "Testimonio actualizado correctamente.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO testimonios_bti (nombre_alumno,testimonio,aprendizaje,dificultad,ruta_foto) VALUES (?,?,?,?,?)");
                    $stmt->execute([$nombre_alumno, $testimonio, $aprendizaje, $dificultad, $ruta_foto ?: '']);
                    $msg_success = "Nuevo testimonio registrado con éxito.";
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
    $stmt_e = $conn->prepare("SELECT * FROM testimonios_bti WHERE id = ?");
    $stmt_e->execute([(int)$_GET['edit']]);
    $edit_item = $stmt_e->fetch();
}

// --- LISTADO ---
$listado = $conn->query("SELECT * FROM testimonios_bti ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Testimonios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fc; }
        .admin-nav { background-color: #0b2545; }
        .fs-7 { font-size: 0.9rem; } .fs-8 { font-size: 0.8rem; }
        .btn-primary { background-color: #0b2545 !important; border-color: #0b2545 !important; }
        .thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 50%; border: 2px solid #0b2545; }
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
                <li class="nav-item"><a class="nav-link" href="asistencia.php">Asistencia</a></li>
                <li class="nav-item"><a class="nav-link active" href="testimonios.php">Testimonios</a></li>
                <li class="nav-item ms-lg-3"><a class="btn btn-outline-light btn-sm fs-8 fw-bold text-uppercase" href="../index.php" target="_blank">Ver Web</a></li>
                <li class="nav-item ms-1"><a class="btn btn-danger btn-sm fs-8 fw-bold text-uppercase" href="logout.php">Salir</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-5">
    <div class="container">
        <h1 class="fw-extrabold mb-4" style="color:#0b2545;">Testimonios del 3° BTI</h1>

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
                    <h5 class="fw-bold mb-3" style="color:#0b2545;"><?= $edit_item ? 'Editar Testimonio' : 'Nuevo Testimonio' ?></h5>
                    <form method="POST" action="testimonios.php" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $edit_item ? $edit_item['id'] : 0 ?>">

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Nombre del Alumno (3° BTI)</label>
                            <input type="text" name="nombre_alumno" class="form-control" required
                                value="<?= $edit_item ? htmlspecialchars($edit_item['nombre_alumno']) : '' ?>"
                                placeholder="Ej: Clara Benítez">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Testimonio / Reflexión</label>
                            <textarea name="testimonio" class="form-control" rows="4" required
                                placeholder="Escribe la experiencia personal del alumno durante el proyecto..."><?= $edit_item ? htmlspecialchars($edit_item['testimonio']) : '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Aprendizaje Obtenido</label>
                            <textarea name="aprendizaje" class="form-control" rows="3"
                                placeholder="¿Qué habilidades o conocimientos obtuvo durante el proyecto?"><?= $edit_item ? htmlspecialchars($edit_item['aprendizaje']) : '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 fw-bold text-muted">Dificultad Superada</label>
                            <textarea name="dificultad" class="form-control" rows="3"
                                placeholder="¿Qué obstáculos enfrentó y cómo los resolvió?"><?= $edit_item ? htmlspecialchars($edit_item['dificultad']) : '' ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fs-8 fw-bold text-muted">Foto del Alumno (Opcional)</label>
                            <input type="file" name="foto_file" class="form-control" accept="image/*">
                            <?php if ($edit_item && !empty($edit_item['ruta_foto'])): ?>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <img src="../<?= htmlspecialchars($edit_item['ruta_foto']) ?>" class="thumb" alt="Foto actual">
                                    <span class="text-muted fs-8">Foto actual (sube una nueva para reemplazarla)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fs-7 fw-bold text-uppercase">
                            <?= $edit_item ? 'Guardar Cambios' : 'Registrar Testimonio' ?>
                        </button>
                        <?php if ($edit_item): ?>
                            <a href="testimonios.php" class="btn btn-secondary w-100 mt-2 py-2 fs-7 fw-bold text-uppercase">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- LISTADO -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold mb-3" style="color:#0b2545;">Testimonios Registrados</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($listado)): ?>
                            <p class="text-center py-4 text-muted">No hay testimonios registrados todavía.</p>
                        <?php else: ?>
                            <?php foreach ($listado as $item): ?>
                                <div class="border rounded-3 p-3 d-flex gap-3 align-items-start">
                                    <div class="flex-shrink-0">
                                        <img src="../<?= htmlspecialchars($item['ruta_foto'] ?: 'img/galeria/foto_scratch_1.jpg') ?>"
                                            class="thumb" alt="<?= htmlspecialchars($item['nombre_alumno']) ?>">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <h6 class="fw-bold mb-0" style="color:#0b2545;"><?= htmlspecialchars($item['nombre_alumno']) ?></h6>
                                            <div class="d-flex gap-1 flex-shrink-0">
                                                <a href="testimonios.php?edit=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 fs-8 fw-semibold">Editar</a>
                                                <a href="testimonios.php?delete=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2 fs-8 fw-semibold"
                                                    onclick="return confirm('¿Eliminar este testimonio?')">Eliminar</a>
                                            </div>
                                        </div>
                                        <p class="text-muted fs-8 mb-1 fst-italic">"<?= htmlspecialchars(mb_substr($item['testimonio'], 0, 120)) ?>..."</p>
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <span class="badge bg-success-subtle text-success fs-9">💡 <?= htmlspecialchars(mb_substr($item['aprendizaje'], 0, 60)) ?>...</span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="badge bg-warning-subtle text-warning-emphasis fs-9">⚠️ <?= htmlspecialchars(mb_substr($item['dificultad'], 0, 60)) ?>...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
