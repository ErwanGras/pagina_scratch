<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Panel de Gestión - Galería de Fotos (Carga de Imágenes y CRUD)
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

// 1. ELIMINAR FOTO
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    try {
        // Obtener la ruta de la imagen física para borrarla
        $stmt_file = $conn->prepare("SELECT ruta_imagen FROM galeria_fotos WHERE id = ?");
        $stmt_file->execute([$id_del]);
        $ruta_img = $stmt_file->fetchColumn();

        if ($ruta_img && file_exists('../' . $ruta_img)) {
            // Evitar borrar las fotos por defecto si el usuario está en pruebas
            // pero permitir borrar las subidas por el panel.
            unlink('../' . $ruta_img);
        }

        // Eliminar registro
        $stmt = $conn->prepare("DELETE FROM galeria_fotos WHERE id = ?");
        $stmt->execute([$id_del]);
        $msg_success = "Imagen eliminada de la galería.";
    } catch (PDOException $e) {
        $msg_error = "Error al eliminar la imagen: " . $e->getMessage();
    }
}

// 2. AGREGAR FOTO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $grado = isset($_POST['grado']) ? $_POST['grado'] : '5';
    $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');

    if (empty($titulo)) {
        $msg_error = "El título de la imagen es obligatorio.";
    } elseif (!isset($_FILES['img_file']) || $_FILES['img_file']['error'] !== UPLOAD_ERR_OK) {
        $msg_error = "Por favor, selecciona un archivo de imagen válido.";
    } else {
        $file_tmp = $_FILES['img_file']['tmp_name'];
        $file_name = $_FILES['img_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

        if (!in_repeat($file_ext, $allowed_exts)) {
            // Verificar extensión
            if (!in_array($file_ext, $allowed_exts)) {
                $msg_error = "Formato de archivo no válido. Solo se permiten imágenes (PNG, JPG, JPEG, GIF, WEBP).";
            }
        }

        if ($msg_error === null) {
            // Guardar imagen
            $new_file_name = 'galeria_' . uniqid() . '.' . $file_ext;
            $upload_dir = '../uploads/img/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $ruta_imagen = 'uploads/img/' . $new_file_name;

                try {
                    $stmt = $conn->prepare("INSERT INTO galeria_fotos (titulo, descripcion, ruta_imagen, grado, fecha) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$titulo, $descripcion, $ruta_imagen, $grado, $fecha]);
                    $msg_success = "Nueva fotografía agregada con éxito a la galería.";
                } catch (PDOException $e) {
                    $msg_error = "Error al insertar la imagen en la base de datos: " . $e->getMessage();
                }
            } else {
                $msg_error = "Error al guardar el archivo de imagen en el servidor.";
            }
        }
    }
}

// Auxiliar para in_repeat
function in_repeat($val, $arr) {
    return in_array($val, $arr);
}

// 3. CONSULTAR LISTADO COMPLETO
try {
    $stmt_list = $conn->query("SELECT * FROM galeria_fotos ORDER BY fecha DESC");
    $listado = $stmt_list->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar fotos de galería: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE Admin - Galería de Fotos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fc; }
        .admin-nav { background-color: #0b2545; }
        .gal-preview { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; }
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
                    <li class="nav-item"><a class="nav-link px-3" href="proyectos.php">Proyectos</a></li>
                    <li class="nav-item"><a class="nav-link px-3 active" href="galeria.php">Galería</a></li>
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
            <h1 class="fw-extrabold text-primary mb-4">Gestión de Galería Fotográfica</h1>

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
                <!-- Formulario Agregar Imagen -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h3 class="fw-bold text-primary mb-3 fs-5">Subir Fotografía</h3>
                        <form method="POST" action="galeria.php" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Título de la Foto</label>
                                <input type="text" name="titulo" class="form-control" required placeholder="Ej: Estudiantes codificando bucles">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Descripción / Pie de foto</label>
                                <textarea name="descripcion" class="form-control" rows="3" placeholder="Información sobre la actividad..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Grado del Grupo</label>
                                <select name="grado" class="form-select">
                                    <option value="5">5° Grado</option>
                                    <option value="6">6° Grado</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-bold text-muted">Fecha de Captura</label>
                                <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fs-8 fw-bold text-muted">Seleccionar Imagen</label>
                                <input type="file" name="img_file" class="form-control" accept="image/*" required>
                                <div class="form-text fs-8 text-muted">Se admiten formatos PNG, JPG, JPEG, GIF o WEBP.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2.5 fs-7 text-uppercase fw-bold">Subir a Galería</button>
                        </form>
                    </div>
                </div>

                <!-- Listado de Imagenes en Grid -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h3 class="fw-bold text-primary mb-3 fs-5">Fotografías Registradas</h3>
                        
                        <div class="row g-3">
                            <?php if (count($listado) === 0): ?>
                                <div class="col-12 text-center py-5">
                                    <p class="text-muted">No hay fotografías cargadas por el momento.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($listado as $foto): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="card h-100 border border-light shadow-none overflow-hidden rounded-3 position-relative">
                                            <?php 
                                            $gal_img = $foto['ruta_imagen'];
                                            if (strpos($gal_img, 'http') !== 0) {
                                                $gal_img = '../' . $gal_img;
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($gal_img) ?>" class="gal-preview" alt="<?= htmlspecialchars($foto['titulo']) ?>">
                                            <span class="badge <?= $foto['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?> position-absolute top-0 end-0 m-2 fs-9">
                                                <?= $foto['grado'] ?>° Grado
                                            </span>
                                            <div class="card-body p-2">
                                                <h6 class="fw-bold text-primary mb-1 text-truncate fs-8"><?= htmlspecialchars($foto['titulo']) ?></h6>
                                                <p class="text-muted fs-9 mb-2 text-truncate"><?= htmlspecialchars($foto['descripcion'] ?: 'Sin descripción') ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-muted fs-9"><?= date('d/m/Y', strtotime($foto['fecha'])) ?></span>
                                                    <a href="galeria.php?delete=<?= $foto['id'] ?>" class="btn btn-sm btn-outline-danger py-0.5 px-2 fs-9 fw-semibold" onclick="return confirm('¿Estás seguro de que deseas eliminar esta fotografía de la galería?')">Eliminar</a>
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

    <!-- Bootstrap 5 Bundle JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
