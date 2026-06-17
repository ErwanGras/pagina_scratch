<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// SCRIPT DE CONFIGURACIÓN INICIAL (setup.php)
// EJECUTAR UNA SOLA VEZ en: http://localhost/Examen-PHP/setup.php
// Luego ELIMINAR o renombrar este archivo por seguridad.
// ====================================================================

// Detectamos si estamos ejecutando en Render (producción)
$isRender = getenv('RENDER') !== false;

function db_exec($pdo, $sql, $isRender) {
    if ($isRender) {
        // Si es SQLite, ignorar comandos específicos de creación/uso de base de datos MySQL
        if (stripos($sql, 'CREATE DATABASE') !== false || stripos($sql, 'USE ') === 0) {
            return true;
        }
        // Adaptar consultas para SQLite
        // 1. Reemplazar AUTO_INCREMENT con AUTOINCREMENT y INT con INTEGER
        $sql = preg_replace('/`id` INT AUTO_INCREMENT PRIMARY KEY/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/`id` INT\s+PRIMARY\s+KEY\s+AUTO_INCREMENT/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        // 2. Reemplazar ENUM(...) con TEXT
        $sql = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sql);
        // 3. Quitar ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        $sql = preg_replace('/\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci/i', ')', $sql);
        $sql = preg_replace('/\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4/i', ')', $sql);
    }
    return $pdo->exec($sql);
}

try {
    if ($isRender) {
        // Usamos SQLite en Render
        $dbPath = __DIR__ . '/database.sqlite';
        $pdo = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
    } else {
        // Usamos MySQL local (XAMPP / Laragon)
        $host = getenv('DB_HOST') ?: 'localhost';
        $db   = getenv('DB_NAME') ?: 'crece_scratch';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $port = getenv('DB_PORT') ?: '3306';
        $charset = 'utf8mb4';

        if ($host === 'localhost' || getenv('DB_HOST') === false) {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=$charset", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // ---- Crear base de datos ----
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`
                        DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db`");
        } else {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=$charset", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
    }

    // ---- Crear tablas ----
    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `usuario` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nombre` VARCHAR(100) DEFAULT NULL,
        `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `alumnos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(50) NOT NULL,
        `apellido` VARCHAR(50) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha_registro` DATE NOT NULL,
        `activo` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `asistencia` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `alumno_id` INT NOT NULL,
        `fecha` DATE NOT NULL,
        `estado` ENUM('Presente','Ausente','Justificado') NOT NULL,
        `observacion` VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `proyectos_scratch` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_pdf` VARCHAR(255) DEFAULT NULL,
        `url_proyecto` VARCHAR(255) DEFAULT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha_creacion` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `galeria_fotos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_imagen` VARCHAR(255) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `planificaciones` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(100) NOT NULL,
        `descripcion` TEXT DEFAULT NULL,
        `ruta_archivo` VARCHAR(255) NOT NULL,
        `grado` ENUM('5','6') NOT NULL,
        `fecha` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `testimonios_bti` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre_alumno` VARCHAR(100) NOT NULL,
        `testimonio` TEXT NOT NULL,
        `aprendizaje` TEXT NOT NULL,
        `dificultad` TEXT NOT NULL,
        `ruta_foto` VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

    db_exec($pdo, "CREATE TABLE IF NOT EXISTS `contacto_mensajes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `asunto` VARCHAR(150) NOT NULL,
        `mensaje` TEXT NOT NULL,
        `fecha_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", $isRender);

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
        db_exec($pdo, "INSERT INTO alumnos (nombre, apellido, grado, fecha_registro) VALUES
            ('Lucas',    'García',     '5', '2026-03-10'),
            ('Sofía',    'Martínez',   '5', '2026-03-10'),
            ('Mateo',    'Rodríguez',  '6', '2026-03-10'),
            ('Valentina','López',      '6', '2026-03-10'),
            ('Thiago',   'Gómez',      '5', '2026-03-11'),
            ('Emma',     'Fernández',  '6', '2026-03-11')", $isRender);

        // Asistencia
        db_exec($pdo, "INSERT INTO asistencia (alumno_id, fecha, estado, observacion) VALUES
            (1,'2026-06-15','Presente','Trabajó en bucles'),
            (2,'2026-06-15','Presente','Terminó animación de iniciales'),
            (3,'2026-06-15','Presente','Inició juego de laberinto'),
            (4,'2026-06-15','Ausente', 'Justificado por reposo médico'),
            (5,'2026-06-15','Presente','Apoyó a sus compañeros'),
            (6,'2026-06-15','Presente','Avanzó en lógica de colisiones'),
            (1,'2026-06-16','Presente','Comenzó animación espacial'),
            (2,'2026-06-16','Ausente', 'Sin justificar'),
            (3,'2026-06-16','Presente','Terminó colisiones del laberinto')", $isRender);
    }

    // ---- Insertar proyectos de prueba ----
    $proyExisten = $pdo->query("SELECT COUNT(*) FROM proyectos_scratch")->fetchColumn();
    if (!$proyExisten) {
        db_exec($pdo, "INSERT INTO proyectos_scratch (titulo, descripcion, ruta_pdf, url_proyecto, grado, fecha_creacion) VALUES
            ('Recolector de Estrellas',   'Juego interactivo de atrapar estrellas en movimiento en el menor tiempo posible.', 'uploads/pdf/Proyecto_Scratch_Recolectando_Estrellas.pdf', 'https://scratch.mit.edu/projects/532111135', '5', '2026-05-12'),
            ('Laberinto Inteligente',     'Guía el objeto a través del laberinto evitando chocar con las paredes usando las flechas del teclado.', 'uploads/pdf/Proyecto_Scratch_Laberinto_Balon.pdf', 'https://scratch.mit.edu/projects/198883970', '6', '2026-05-20'),
            ('Suma y Aprende',            'Juego educativo de matemáticas donde sumas números generados aleatoriamente para ganar puntos.', 'uploads/pdf/Proyecto_Scratch_Suma_Matematica.pdf', 'https://scratch.mit.edu/projects/505885250', '5', '2026-04-18'),
            ('Carrera de Autos',          'Juego de carreras de autos en una pista cerrada con obstáculos y control de tiempo.', 'uploads/pdf/analisis_proyecto_scratch.pdf', 'https://scratch.mit.edu/projects/445947054', '6', '2026-06-02')", $isRender);
    }

    // ---- Insertar fotos de galería (rutas en uploads/img/) ----
    $fotosExisten = $pdo->query("SELECT COUNT(*) FROM galeria_fotos")->fetchColumn();
    if (!$fotosExisten) {
        db_exec($pdo, "INSERT INTO galeria_fotos (titulo, descripcion, ruta_imagen, grado, fecha) VALUES
            ('Primeros pasos con Bloques',  'Alumnos de 5° explorando bloques de movimiento.',          'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=800&q=80', '5', '2026-04-15'),
            ('Programación de Escenarios',  'Estudiantes de 6° diseñando cambio dinámico de fondos.',   'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80', '6', '2026-04-22'),
            ('Presentación de Proyectos',   'Alumnos compartiendo sus videojuegos ante la clase.',       'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80', '5', '2026-05-18'),
            ('Taller de Lógica Avanzada',   'Variables y operadores matemáticos aplicados a juegos.',    'https://images.unsplash.com/photo-1516534775068-ba3e84589d90?auto=format&fit=crop&w=800&q=80', '6', '2026-06-05')", $isRender);
    }

    // ---- Insertar planificaciones ----
    $planExisten = $pdo->query("SELECT COUNT(*) FROM planificaciones")->fetchColumn();
    if (!$planExisten) {
        db_exec($pdo, "INSERT INTO planificaciones (titulo, descripcion, ruta_archivo, grado, fecha) VALUES
            ('Introducción a Scratch',             'Unidad 1: Interfaz, bloques de movimiento y eventos iniciales.', 'uploads/pdf/Guia_Scratch_5Grado_clase_01.pdf', '5', '2026-03-15'),
            ('Animaciones y Diálogos',             'Unidad 2: Coordinación de diálogos e historias animadas.', 'uploads/pdf/Guia_Scratch_5Grado_clase_02.pdf', '5', '2026-04-10'),
            ('Eventos y Teclado',                  'Unidad 3: Controlar objetos usando eventos físicos y teclas.', 'uploads/pdf/Guia_Scratch_5Grado_clase_03.pdf', '5', '2026-05-05'),
            ('Estructuras Repetitivas',             'Unidad 4: Bucles simples y repeticiones de acciones.', 'uploads/pdf/Guia_Scratch_5Grado_clase_04.pdf', '5', '2026-05-20'),
            ('Proyecto Inicial',                   'Unidad 5: Creación del primer minijuego interactivo.', 'uploads/pdf/Guia_Scratch_5Grado_clase_05.pdf', '5', '2026-06-01'),
            ('Repaso y Estructura',                'Clase 1: Repaso general de lógica y organización del área de trabajo.', 'uploads/pdf/Clase_01_Repaso_de_Scratch_y_Organización.pdf', '6', '2026-03-15'),
            ('Animación y Audio',                  'Clase 2: Efectos visuales avanzados, cambios de disfraces y sonidos sincronizados.', 'uploads/pdf/Clase_02_Animaciones_Avanzadas_y_Sonidos.pdf', '6', '2026-04-12'),
            ('Movimientos y Física',               'Clase 3: Coordenadas relativas, rebotes en bordes y lógica de velocidad.', 'uploads/pdf/Clase_03_Movimientos_Complejos_y_Física_Básica.pdf', '6', '2026-05-08'),
            ('Control de Flujo Avanzado',          'Clase 4: Bucles condicionales complejos y sensores de colisión.', 'uploads/pdf/Clase_04_Condicionales_Avanzadas_Bucles_y_Eventos.pdf', '6', '2026-05-25'),
            ('Variables y Marcadores',             'Clase 5: Sumar puntos, manejo de vidas y cierre del proyecto integrador.', 'uploads/pdf/Clase_05_Variables_Puntuación_y_Proyecto_Final.pdf', '6', '2026-06-05')", $isRender);
    }

    // ---- Insertar testimonios ----
    $testExisten = $pdo->query("SELECT COUNT(*) FROM testimonios_bti")->fetchColumn();
    if (!$testExisten) {
        db_exec($pdo, "INSERT INTO testimonios_bti (nombre_alumno, testimonio, aprendizaje, dificultad, ruta_foto) VALUES
            ('Clara Benítez',
             'Fue una experiencia maravillosa. Ver cómo los niños lograban animar sus personajes me hizo comprender el valor de compartir el conocimiento.',
             'Aprendí a simplificar conceptos técnicos de lógica y a tener mucha paciencia al enseñar.',
             'La mayor dificultad fue mantener la atención del grupo al inicio; lo superamos con desafíos más dinámicos.',
             'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'),
            ('Diego Ortigoza',
             'Enseñar programación a 6° grado nos retó como estudiantes de informática. Tuvimos que dominar la comunicación didáctica además de la técnica.',
             'Desarrollé habilidades interpersonales y metodologías ágiles para resolver dudas en tiempo real.',
             'Explicar variables y sensores de colisión requirió crear ejemplos cotidianos y divertidos en el pizarrón.',
             'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80')", $isRender);
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
    <p class="text-muted mt-2">La base de datos <strong>' . htmlspecialchars($db) . '</strong> fue configurada con todas las tablas y datos de prueba correctamente.</p>
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
