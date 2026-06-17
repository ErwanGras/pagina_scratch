<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// SCRIPT DE CONFIGURACIÓN INICIAL (setup.php)
// EJECUTAR UNA SOLA VEZ en: http://localhost/Examen-PHP/setup.php
// Luego ELIMINAR o renombrar este archivo por seguridad.
// ====================================================================

$host    = 'localhost';
$user    = 'root';
$pass    = '';           // En XAMPP la contraseña de root es vacía por defecto
$charset = 'utf8mb4';

try {
    // Conectar sin especificar base de datos
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ---- Crear base de datos ----
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `crece_scratch`
                DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `crece_scratch`");

    // ---- Crear tablas ----
    $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `usuario` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nombre` VARCHAR(100) DEFAULT NULL,
        `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `alumnos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(50) NOT NULL,
        `apellido` VARCHAR(50) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha_registro` DATE NOT NULL,
        `activo` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `asistencia` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `alumno_id` INT NOT NULL,
        `fecha` DATE NOT NULL,
        `estado` ENUM('Presente','Ausente','Justificado') NOT NULL,
        `observacion` VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `proyectos_scratch` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_pdf` VARCHAR(255) DEFAULT NULL,
        `url_proyecto` VARCHAR(255) DEFAULT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha_creacion` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `galeria_fotos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_imagen` VARCHAR(255) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `planificaciones` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `testimonios_bti` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre_alumno` VARCHAR(100) NOT NULL,
        `testimonio` TEXT NOT NULL,
        `aprendizaje` TEXT NOT NULL,
        `dificultad` TEXT NOT NULL,
        `ruta_foto` VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `contacto_mensajes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `asunto` VARCHAR(150) NOT NULL,
        `mensaje` TEXT NOT NULL,
        `fecha_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ---- Insertar usuario admin (con hash correcto generado aquí mismo) ----
    $adminExiste = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE usuario='admin'")->fetchColumn();
    if (!$adminExiste) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, nombre) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Administrador General']);
    }

    // ---- Insertar alumnos de prueba ----
    $alumnosExisten = $pdo->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
    if (!$alumnosExisten) {
        $pdo->exec("INSERT INTO alumnos (nombre, apellido, grado, fecha_registro) VALUES
            ('Lucas',    'García',     '5', '2026-03-10'),
            ('Sofía',    'Martínez',   '5', '2026-03-10'),
            ('Mateo',    'Rodríguez',  '6', '2026-03-10'),
            ('Valentina','López',      '6', '2026-03-10'),
            ('Thiago',   'Gómez',      '5', '2026-03-11'),
            ('Emma',     'Fernández',  '6', '2026-03-11')");

        // Asistencia
        $pdo->exec("INSERT INTO asistencia (alumno_id, fecha, estado, observacion) VALUES
            (1,'2026-06-15','Presente','Trabajó en bucles'),
            (2,'2026-06-15','Presente','Terminó animación de iniciales'),
            (3,'2026-06-15','Presente','Inició juego de laberinto'),
            (4,'2026-06-15','Ausente', 'Justificado por reposo médico'),
            (5,'2026-06-15','Presente','Apoyó a sus compañeros'),
            (6,'2026-06-15','Presente','Avanzó en lógica de colisiones'),
            (1,'2026-06-16','Presente','Comenzó animación espacial'),
            (2,'2026-06-16','Ausente', 'Sin justificar'),
            (3,'2026-06-16','Presente','Terminó colisiones del laberinto')");
    }

    // ---- Insertar proyectos de prueba ----
    $proyExisten = $pdo->query("SELECT COUNT(*) FROM proyectos_scratch")->fetchColumn();
    if (!$proyExisten) {
        $pdo->exec("INSERT INTO proyectos_scratch (titulo, descripcion, ruta_pdf, url_proyecto, grado, fecha_creacion) VALUES
            ('Aventura Espacial',        'Videojuego de naves esquivando asteroides usando flechas del teclado.',         'uploads/pdf/guia_proyecto_aventura.pdf',  'https://scratch.mit.edu/projects/100000001', '5', '2026-05-12'),
            ('Laberinto Inteligente',    'El usuario guía un gato por un laberinto usando lógica de colisiones.',         'uploads/pdf/guia_proyecto_laberinto.pdf', 'https://scratch.mit.edu/projects/100000002', '6', '2026-05-20'),
            ('Dialogando con mi Mascota','Historia animada con diálogos interactivos y cambios de fondo.',                'uploads/pdf/guia_proyecto_mascota.pdf',   'https://scratch.mit.edu/projects/100000003', '5', '2026-04-18'),
            ('Pintor Galáctico',         'Herramienta de dibujo interactiva con eventos del mouse y el lápiz de Scratch.','uploads/pdf/guia_proyecto_pintor.pdf',    'https://scratch.mit.edu/projects/100000004', '6', '2026-06-02')");
    }

    // ---- Insertar fotos de galería (rutas en uploads/img/) ----
    $fotosExisten = $pdo->query("SELECT COUNT(*) FROM galeria_fotos")->fetchColumn();
    if (!$fotosExisten) {
        $pdo->exec("INSERT INTO galeria_fotos (titulo, descripcion, ruta_imagen, grado, fecha) VALUES
            ('Primeros pasos con Bloques',  'Alumnos de 5° explorando bloques de movimiento.',          'uploads/img/foto_scratch_1.jpg', '5', '2026-04-15'),
            ('Programación de Escenarios',  'Estudiantes de 6° diseñando cambio dinámico de fondos.',   'uploads/img/foto_scratch_2.jpg', '6', '2026-04-22'),
            ('Presentación de Proyectos',   'Alumnos compartiendo sus videojuegos ante la clase.',       'uploads/img/foto_scratch_3.jpg', '5', '2026-05-18'),
            ('Taller de Lógica Avanzada',   'Variables y operadores matemáticos aplicados a juegos.',    'uploads/img/foto_scratch_4.jpg', '6', '2026-06-05')");
    }

    // ---- Insertar planificaciones ----
    $planExisten = $pdo->query("SELECT COUNT(*) FROM planificaciones")->fetchColumn();
    if (!$planExisten) {
        $pdo->exec("INSERT INTO planificaciones (titulo, descripcion, ruta_archivo, grado, fecha) VALUES
            ('Introducción a Scratch',             'Interfaz, bloques de movimiento y eventos iniciales.',   'uploads/pdf/planificacion_unidad1_grado5.pdf', '5', '2026-03-15'),
            ('Bucles y Condicionales',             'Estructuras de control repetitivas y toma de decisiones.','uploads/pdf/planificacion_unidad2_grado5.pdf', '5', '2026-04-10'),
            ('Juegos Interactivos y Sensores',     'Mecánicas de juego usando sensores y variables.',        'uploads/pdf/planificacion_unidad1_grado6.pdf', '6', '2026-05-05'),
            ('Clonación y Colisiones Avanzadas',   'Clones dinámicos y detección precisa de contactos.',    'uploads/pdf/planificacion_unidad2_grado6.pdf', '6', '2026-06-01')");
    }

    // ---- Insertar testimonios ----
    $testExisten = $pdo->query("SELECT COUNT(*) FROM testimonios_bti")->fetchColumn();
    if (!$testExisten) {
        $pdo->exec("INSERT INTO testimonios_bti (nombre_alumno, testimonio, aprendizaje, dificultad, ruta_foto) VALUES
            ('Clara Benítez',
             'Fue una experiencia maravillosa. Ver cómo los niños lograban animar sus personajes me hizo comprender el valor de compartir el conocimiento.',
             'Aprendí a simplificar conceptos técnicos de lógica y a tener mucha paciencia al enseñar.',
             'La mayor dificultad fue mantener la atención del grupo al inicio; lo superamos con desafíos más dinámicos.',
             'uploads/img/foto_scratch_1.jpg'),
            ('Diego Ortigoza',
             'Enseñar programación a 6° grado nos retó como estudiantes de informática. Tuvimos que dominar la comunicación didáctica además de la técnica.',
             'Desarrollé habilidades interpersonales y metodologías ágiles para resolver dudas en tiempo real.',
             'Explicar variables y sensores de colisión requirió crear ejemplos cotidianos y divertidos en el pizarrón.',
             'uploads/img/foto_scratch_2.jpg')");
    }

    echo '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Completado – CRECE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>body{font-family:Outfit,sans-serif;background:#f3f7fa;display:flex;align-items:center;justify-content:center;min-height:100vh;}</style>
</head>
<body>
<div class="card border-0 shadow-lg rounded-4 p-5 text-center" style="max-width:520px;">
    <div style="font-size:4rem;">✅</div>
    <h2 class="fw-bold mt-3" style="color:#0b2545;">¡Base de datos lista!</h2>
    <p class="text-muted mt-2">La base de datos <strong>crece_scratch</strong> fue creada con todas las tablas y datos de prueba correctamente.</p>
    <hr>
    <div class="text-start mb-3">
        <p class="mb-1"><strong>Credenciales de Administrador:</strong></p>
        <ul class="text-muted">
            <li>Usuario: <code>admin</code></li>
            <li>Contraseña: <code>admin123</code></li>
        </ul>
    </div>
    <div class="alert alert-warning rounded-3 fs-7 text-start py-2 px-3">
        ⚠️ <strong>Importante:</strong> Elimina o renombra el archivo <code>setup.php</code> después de usar este script para mayor seguridad.
    </div>
    <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="index.php" class="btn btn-primary fw-bold px-4">Ver Portal Público</a>
        <a href="admin/login.php" class="btn btn-warning text-dark fw-bold px-4">Ir al Panel Admin</a>
    </div>
</div>
</body>
</html>';

} catch (PDOException $e) {
    echo '<div style="font-family:monospace;padding:30px;background:#fff3f3;border:2px solid red;margin:20px;border-radius:8px;">';
    echo '<h3 style="color:red;">❌ Error de Configuración</h3>';
    echo '<p><strong>Mensaje:</strong> ' . $e->getMessage() . '</p>';
    echo '<p><strong>Posible causa:</strong> Verifica que XAMPP esté en ejecución y que MySQL esté activo.</p>';
    echo '</div>';
}
?>
