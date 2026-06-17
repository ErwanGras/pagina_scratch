<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Gestión CRUD - Proyectos Scratch con Carga de PDF
// ====================================================================

session_start();
require_once '../conexion.php';

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$msg_success = null;
$msg_error = null;

// --- PROCESAR ACCIONES ---

// 1. ELIMINAR PROYECTO
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    try {
        // Obtener ruta del PDF para borrarlo físicamente
        $stmt_file = $conn->prepare("SELECT ruta_pdf FROM proyectos_scratch WHERE id = ?");
        $stmt_file->execute([$id_del]);
        $ruta_pdf = $stmt_file->fetchColumn();

        if ($ruta_pdf && file_exists('../' . $ruta_pdf)) {
            unlink('../' . $ruta_pdf); // Eliminar archivo físico
        }

        // Eliminar registro base de datos
        $stmt = $conn->prepare("DELETE FROM proyectos_scratch WHERE id = ?");
        $stmt->execute([$id_del]);
        $msg_success = "Proyecto eliminado de forma definitiva.";
    } catch (PDOException $e) {
        $msg_error = "Error al eliminar el proyecto: " . $e->getMessage();
    }
}

// 2. CREAR O EDITAR PROYECTO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $url_proyecto = isset($_POST['url_proyecto']) ? trim($_POST['url_proyecto']) : '';
    $grado = isset($_POST['grado']) ? $_POST['grado'] : '5';
    $fecha_creacion = isset($_POST['fecha_creacion']) ? $_POST['fecha_creacion'] : date('Y-m-d');

    if (empty($titulo) || empty($descripcion)) {
        $msg_error = "El título y la descripción son campos obligatorios.";
    } else {
        $ruta_pdf = null;
        
        // Manejar subida de archivo PDF
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['pdf_file']['tmp_name'];
            $file_name = $_FILES['pdf_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext !== 'pdf') {
                $msg_error = "Solo se permiten archivos en formato PDF (.pdf).";
            } else {
                // Crear nombre único para evitar colisiones
                $new_file_name = 'proyecto_' . uniqid() . '.pdf';
                $upload_dir = '../uploads/pdf/';
                
                // Asegurar que el directorio existe
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                    $ruta_pdf = 'uploads/pdf/' . $new_file_name;

                    // Si es una edición y se sube nuevo archivo, borrar el archivo PDF anterior
                    if ($id > 0) {
                        $stmt_old = $conn->prepare("SELECT ruta_pdf FROM proyectos_scratch WHERE id = ?");
                        $stmt_old->execute([$id]);
                        $old_pdf = $stmt_old->fetchColumn();
                        if ($old_pdf && file_exists('../' . $old_pdf)) {
                            unlink('../' . $old_pdf);
                        }
                    }
                } else {
                    $msg_error = "Error al mover el archivo PDF cargado en el servidor.";
                }
            }
        }

        // Si no hay errores, insertar o actualizar en BD
        if ($msg_error === null) {
            try {
                if ($id > 0) {
                    // Editar
                    if ($ruta_pdf !== null) {
                        $stmt = $conn->prepare("UPDATE proyectos_scratch SET titulo = ?, descripcion = ?, ruta_pdf = ?, url_proyecto = ?, grado = ?, fecha_creacion = ? WHERE id = ?");
                        $stmt->execute([$titulo, $descripcion, $ruta_pdf, $url_proyecto, $grado, $fecha_creacion, $id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE proyectos_scratch SET titulo = ?, descripcion = ?, url_proyecto = ?, grado = ?, fecha_creacion = ? WHERE id = ?");
                        $stmt->execute([$titulo, $descripcion, $url_proyecto, $grado, $fecha_creacion, $id]);
                    }
                    $msg_success = "Proyecto actualizado correctamente.";
                } else {
                    // Crear
                    $stmt = $conn->prepare("INSERT INTO proyectos_scratch (titulo, descripcion, ruta_pdf, url_proyecto, grado, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$titulo, $descripcion, $ruta_pdf ?: '', $url_proyecto, $grado, $fecha_creacion]);
                    $msg_success = "Nuevo proyecto Scratch registrado con éxito.";
                }
            } catch (PDOException $e) {
                $msg_error = "Error al guardar el proyecto en la base de datos: " . $e->getMessage();
            }
        }
    }
}

// 3. CONSULTAR DATOS DE EDICIÓN SI SE PIDE
$edit_project = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    try {
        $stmt_edit = $conn->prepare("SELECT * FROM proyectos_scratch WHERE id = ?");
        $stmt_edit->execute([$id_edit]);
        $edit_project = $stmt_edit->fetch();
    } catch (PDOException $e) {
        $msg_error = "Error al consultar el registro para edición: " . $e->getMessage();
    }
}

// 4. CONSULTAR LISTADO COMPLETO
try {
    $stmt_list = $conn->query("SELECT * FROM proyectos_scratch ORDER BY fecha_creacion DESC");
    $listado = $stmt_list->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar proyectos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Gestionar Proyectos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fc; }
        .admin-nav { background-color: #0b2545; }
        .fs-7 { font-size: 0.9rem; }
        .fs-8 { font-size: 0.8rem; }
    </style>
</head>
<body>

    <!-- Menú -->
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link px-3" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active" href="proyectos.php">Proyectos</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="galeria.php">Galería</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="planificaciones.php">Planificaciones</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="asistencia.php">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="testimonios.php">Testimonios</a></li>
                    <li class="nav-item ms-lg-4"><a class="btn btn-outline-light btn-sm px-3 py-1.5 fs-8 text-uppercase fw-bold" href="../index.php" target="_blank">Ver Web</a></li>
                    <li class="nav-item"><a class="btn btn-danger btn-sm px-3 py-1.5 fs-8 text-uppercase fw-bold ms-lg-1" href="logout.php">Salir</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido -->
    <main class="py-5">
        <div class="container">
            <h1 class="fw-extrabold text-primary mb-4">Gestión de Proyectos Scratch</h1>

            <!-- Mensajes de Operación -->
            <?php if ($msg_success): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 fs-7" role="alert">
                    <?= $msg_success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg_error): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 fs-7" role="alert">
                    <?= $msg_error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Formulario Añadir / Editar -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h3 class="fw-bold text-primary mb-3 fs-5"><?= $edit_project ? 'Editar Proyecto' : 'Agregar Proyecto' ?></h3>
                        <form method="POST" action="proyectos.php" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $edit_project ? $edit_project['id'] : 0 ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Título del Proyecto</label>
                                <input type="text" name="titulo" class="form-control" required value="<?= $edit_project ? htmlspecialchars($edit_project['titulo']) : '' ?>" placeholder="Ej: Aventura Espacial">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="4" required placeholder="Describe las mecánicas y lógica utilizada por el alumno..."><?= $edit_project ? htmlspecialchars($edit_project['descripcion']) : '' ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Grado Escolar</label>
                                <select name="grado" class="form-select">
                                    <option value="5" <?= ($edit_project && $edit_project['grado'] === '5') ? 'selected' : '' ?>>5° Grado</option>
                                    <option value="6" <?= ($edit_project && $edit_project['grado'] === '6') ? 'selected' : '' ?>>6° Grado</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Enlace Web Scratch (Opcional)</label>
                                <input type="url" name="url_proyecto" class="form-control" value="<?= $edit_project ? htmlspecialchars($edit_project['url_proyecto']) : '' ?>" placeholder="https://scratch.mit.edu/projects/...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Fecha Creación</label>
                                <input type="date" name="fecha_creacion" class="form-control" value="<?= $edit_project ? $edit_project['fecha_creacion'] : date('Y-m-d') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fs-8 fw-bold text-muted">Guía Didáctica PDF (Opcional)</label>
                                <input type="file" name="pdf_file" class="form-control" accept=".pdf">
                                <?php if ($edit_project && !empty($edit_project['ruta_pdf'])): ?>
                                    <div class="form-text fs-8 text-success">Archivo actual: <a href="../<?= $edit_project['ruta_pdf'] ?>" target="_blank">Ver PDF</a></div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2.5 fs-7 text-uppercase fw-bold"><?= $edit_project ? 'Guardar Cambios' : 'Registrar Proyecto' ?></button>
                            <?php if ($edit_project): ?>
                                <a href="proyectos.php" class="btn btn-secondary w-100 py-2 fs-7 text-uppercase fw-bold mt-2">Cancelar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Listado -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h3 class="fw-bold text-primary mb-3 fs-5">Listado de Proyectos</h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 fs-7">
                                <thead class="table-light text-primary">
                                    <tr>
                                        <th class="px-3">Título</th>
                                        <th>Grado</th>
                                        <th>Fecha</th>
                                        <th>PDF</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($listado) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No hay proyectos registrados todavía.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($listado as $proj): ?>
                                            <tr>
                                                <td class="px-3 fw-bold text-primary"><?= htmlspecialchars($proj['titulo']) ?></td>
                                                <td>
                                                    <span class="badge <?= $proj['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?> rounded">
                                                        <?= $proj['grado'] ?>° Grado
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($proj['fecha_creacion'])) ?></td>
                                                <td>
                                                    <?php if (!empty($proj['ruta_pdf'])): ?>
                                                        <a href="../<?= htmlspecialchars($proj['ruta_pdf']) ?>" target="_blank" class="badge bg-success text-decoration-none">Ver PDF</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Ninguno</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="proyectos.php?edit=<?= $proj['id'] ?>" class="btn btn-sm btn-outline-warning text-primary py-1 px-2.5 fs-8 fw-semibold">Editar</a>
                                                    <a href="proyectos.php?delete=<?= $proj['id'] ?>" class="btn btn-sm btn-outline-danger py-1 px-2.5 fs-8 fw-semibold ms-1" onclick="return confirm('¿Estás seguro de que deseas eliminar este proyecto de manera definitiva?')">Eliminar</a>
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

    <!-- Bootstrap 5 Bundle JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
