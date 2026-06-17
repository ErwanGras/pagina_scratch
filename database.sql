-- ====================================================================
-- CRECE - Proyecto Integrador 3° BTI
-- Script de Base de Datos Dinámica MySQL para XAMPP
-- Base de Datos: crece_scratch
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `crece_scratch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crece_scratch`;

-- 1. Tabla: Usuarios Administrativos
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt de la contraseña',
  `nombre` VARCHAR(100) DEFAULT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla: Alumnos
DROP TABLE IF EXISTS `asistencia`;
DROP TABLE IF EXISTS `proyectos_scratch`;
DROP TABLE IF EXISTS `alumnos`;
CREATE TABLE `alumnos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `grado` ENUM('5', '6') NOT NULL COMMENT 'Grado escolar: 5° o 6° grado',
  `fecha_registro` DATE NOT NULL,
  `activo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla: Asistencia
CREATE TABLE `asistencia` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alumno_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `estado` ENUM('Presente', 'Ausente', 'Justificado') NOT NULL,
  `observacion` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla: Proyectos Scratch
CREATE TABLE `proyectos_scratch` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `ruta_pdf` VARCHAR(255) DEFAULT NULL COMMENT 'Ruta del archivo de guía en uploads/pdf/',
  `url_proyecto` VARCHAR(255) DEFAULT NULL COMMENT 'Enlace directo al proyecto de Scratch',
  `grado` ENUM('5', '6') NOT NULL COMMENT 'Grado al que va dirigido',
  `fecha_creacion` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla: Galería de Fotos
DROP TABLE IF EXISTS `galeria_fotos`;
CREATE TABLE `galeria_fotos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `ruta_imagen` VARCHAR(255) NOT NULL COMMENT 'Ruta en uploads/img/',
  `grado` ENUM('5', '6') NOT NULL COMMENT 'Filtro por grado: 5 o 6',
  `fecha` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla: Planificaciones
DROP TABLE IF EXISTS `planificaciones`;
CREATE TABLE `planificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(100) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL COMMENT 'Ruta del PDF en uploads/pdf/',
  `grado` ENUM('5', '6') NOT NULL,
  `fecha` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla: Testimonios de Alumnos 3° BTI
DROP TABLE IF EXISTS `testimonios_bti`;
CREATE TABLE `testimonios_bti` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_alumno` VARCHAR(100) NOT NULL,
  `testimonio` TEXT NOT NULL,
  `aprendizaje` TEXT NOT NULL,
  `dificultad` TEXT NOT NULL,
  `ruta_foto` VARCHAR(255) DEFAULT NULL COMMENT 'Foto del alumno en uploads/img/'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla: Mensajes de Contacto
DROP TABLE IF EXISTS `contacto_mensajes`;
CREATE TABLE `contacto_mensajes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `asunto` VARCHAR(150) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `fecha_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ====================================================================
-- INSERCIÓN DE DATOS DE MUESTRA PARA PRUEBAS (XAMPP LISTO)
-- ====================================================================

-- Usuario Administrador por defecto: admin / admin123
INSERT INTO `usuarios` (`usuario`, `password`, `nombre`) VALUES
('admin', '$2y$10$MX5n0Z2XU/XGvV8W.N34k.H/T6k7m4BfFqGfH2Y1yW5v7s8q7pCqy', 'Administrador General');

-- Alumnos de 5° y 6° Grado
INSERT INTO `alumnos` (`nombre`, `apellido`, `grado`, `fecha_registro`) VALUES
('Lucas', 'García', '5', '2026-03-10'),
('Sofía', 'Martínez', '5', '2026-03-10'),
('Mateo', 'Rodríguez', '6', '2026-03-10'),
('Valentina', 'López', '6', '2026-03-10'),
('Thiago', 'Gómez', '5', '2026-03-11'),
('Emma', 'Fernández', '6', '2026-03-11');

-- Asistencia (Historial de clase)
INSERT INTO `asistencia` (`alumno_id`, `fecha`, `estado`, `observacion`) VALUES
(1, '2026-06-15', 'Presente', 'Trabajó en bucles'),
(2, '2026-06-15', 'Presente', 'Terminó animación de iniciales'),
(3, '2026-06-15', 'Presente', 'Inició juego de laberinto'),
(4, '2026-06-15', 'Ausente', 'Justificado por reposo médico'),
(5, '2026-06-15', 'Presente', 'Apoyó a sus compañeros'),
(6, '2026-06-15', 'Presente', 'Avanzó en lógica de colisiones'),
(1, '2026-06-16', 'Presente', 'Comenzó animación espacial'),
(2, '2026-06-16', 'Ausente', 'Sin justificar'),
(3, '2026-06-16', 'Presente', 'Terminó colisiones del laberinto');

-- Proyectos Scratch
INSERT INTO `proyectos_scratch` (`titulo`, `descripcion`, `ruta_pdf`, `url_proyecto`, `grado`, `fecha_creacion`) VALUES
('Aventura Espacial', 'Un juego interactivo de naves esquivando asteroides usando las flechas del teclado.', 'uploads/pdf/guia_proyecto_aventura.pdf', 'https://scratch.mit.edu/projects/100000001', '5', '2026-05-12'),
('Laberinto Inteligente', 'El usuario debe guiar a un gato a través de un laberinto usando lógica de colisiones.', 'uploads/pdf/guia_proyecto_laberinto.pdf', 'https://scratch.mit.edu/projects/100000002', '6', '2026-05-20'),
('Dialogando con mi Mascota', 'Una historia animada con diálogos interactivos y cambios de fondo.', 'uploads/pdf/guia_proyecto_mascota.pdf', 'https://scratch.mit.edu/projects/100000003', '5', '2026-04-18'),
('Pintor Galáctico', 'Una herramienta de dibujo interactiva usando lápiz en Scratch y eventos del mouse.', 'uploads/pdf/guia_proyecto_pintor.pdf', 'https://scratch.mit.edu/projects/100000004', '6', '2026-06-02');

-- Galería de Fotos (Imágenes en uploads/img/)
INSERT INTO `galeria_fotos` (`titulo`, `descripcion`, `ruta_imagen`, `grado`, `fecha`) VALUES
('Primeros pasos con Bloques', 'Alumnos de 5° grado explorando los bloques de movimiento y apariencia en Scratch.', 'uploads/img/foto_scratch_1.jpg', '5', '2026-04-15'),
('Programación de Escenarios', 'Estudiantes de 6° grado diseñando y programando el cambio dinámico de escenarios.', 'uploads/img/foto_scratch_2.jpg', '6', '2026-04-22'),
('Presentación de Proyectos', 'Alumnos compartiendo sus videojuegos terminados frente a la clase.', 'uploads/img/foto_scratch_3.jpg', '5', '2026-05-18'),
('Taller de Lógica Avanzada', 'Sesión práctica de variables y operadores matemáticos aplicada a videojuegos.', 'uploads/img/foto_scratch_4.jpg', '6', '2026-06-05');

-- Planificaciones del Docente (PDFs en uploads/pdf/)
INSERT INTO `planificaciones` (`titulo`, `descripcion`, `ruta_archivo`, `grado`, `fecha`) VALUES
('Introducción a la Programación con Scratch', 'Planificación didáctica sobre interfaz, bloques de movimiento y eventos iniciales.', 'uploads/pdf/planificacion_unidad1_grado5.pdf', '5', '2026-03-15'),
('Estructuras de Control: Bucles y Condicionales', 'Guía docente sobre bucles repetitivos y toma de decisiones en Scratch.', 'uploads/pdf/planificacion_unidad2_grado5.pdf', '5', '2026-04-10'),
('Juegos Interactivos y Sensores', 'Metodología para el desarrollo de mecánicas de juego usando sensores y variables.', 'uploads/pdf/planificacion_unidad1_grado6.pdf', '6', '2026-05-05'),
('Clonación de Objetos y Lógica de Colisiones', 'Avanzado: Creación de enemigos en masa y detección precisa de contactos en pantalla.', 'uploads/pdf/planificacion_unidad2_grado6.pdf', '6', '2026-06-01');

-- Testimonios Alumnos de 3° BTI
INSERT INTO `testimonios_bti` (`nombre_alumno`, `testimonio`, `aprendizaje`, `dificultad`, `ruta_foto`) VALUES
('Clara Benítez', 'Fue una experiencia maravillosa. Ver cómo los niños de 5° grado lograban animar sus personajes de Scratch y sonreían al ver sus logros me hizo comprender el valor de compartir el conocimiento.', 'Aprendí a simplificar conceptos técnicos de lógica de programación y a tener mucha paciencia al enseñar.', 'La mayor dificultad fue mantener la atención del grupo en las primeras sesiones, lo cual superamos creando desafíos más dinámicos y juegos lúdicos.', 'uploads/img/alumno_clara.jpg'),
('Diego Ortigoza', 'Enseñar programación a 6° grado nos retó como estudiantes de informática. Tuvimos que dominar no solo la técnica, sino también la comunicación didáctica.', 'Desarrollé habilidades interpersonales y metodologías ágiles para resolver dudas en tiempo real en un aula de clase.', 'Explicar cómo funcionan las variables y los sensores de colisión requirió crear ejemplos cotidianos interactivos en el pizarrón.', 'uploads/img/alumno_diego.jpg');
