<?php
// ====================================================================
// CRECE - Proyecto Integrador 3° BTI
// Página de Inicio Pública y Portal Dinámico
// ====================================================================

require_once 'conexion.php';

// --- PROCESAMIENTO DEL FORMULARIO DE CONTACTO ---
$contact_success = null;
$contact_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $asunto = isset($_POST['asunto']) ? trim($_POST['asunto']) : '';
    $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

    if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
        $contact_error = "Todos los campos del formulario son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = "El formato de correo electrónico ingresado no es válido.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contacto_mensajes (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $asunto, $mensaje]);
            $contact_success = "¡Gracias por escribirnos! Tu mensaje ha sido enviado e insertado en la base de datos con éxito.";
        } catch (PDOException $e) {
            $contact_error = "Hubo un error al guardar tu mensaje en la base de datos: " . $e->getMessage();
        }
    }
}

// --- CONSULTAS PARA ESTADÍSTICAS ---
try {
    $total_alumnos = $conn->query("SELECT COUNT(*) FROM alumnos")->fetchColumn();
    $total_proyectos = $conn->query("SELECT COUNT(*) FROM proyectos_scratch")->fetchColumn();
    $total_fotos = $conn->query("SELECT COUNT(*) FROM galeria_fotos")->fetchColumn();
    $total_planificaciones = $conn->query("SELECT COUNT(*) FROM planificaciones")->fetchColumn();
} catch (PDOException $e) {
    // Valores fallback si no hay tablas
    $total_alumnos = 6;
    $total_proyectos = 4;
    $total_fotos = 4;
    $total_planificaciones = 4;
}

// --- CONSULTA DE PROYECTOS (BÚSQUEDA Y FILTRO) ---
$search_proj = isset($_GET['search_proj']) ? trim($_GET['search_proj']) : '';
$grado_proj = isset($_GET['grado_proj']) ? $_GET['grado_proj'] : 'all';
$query_proj = "SELECT * FROM proyectos_scratch WHERE 1=1";
$params_proj = [];

if ($search_proj !== '') {
    $query_proj .= " AND (titulo LIKE ? OR descripcion LIKE ?)";
    $params_proj[] = "%$search_proj%";
    $params_proj[] = "%$search_proj%";
}
if ($grado_proj === '5' || $grado_proj === '6') {
    $query_proj .= " AND grado = ?";
    $params_proj[] = $grado_proj;
}
$query_proj .= " ORDER BY fecha_creacion DESC";
$stmt_proj = $conn->prepare($query_proj);
$stmt_proj->execute($params_proj);
$proyectos = $stmt_proj->fetchAll();

// --- CONSULTA DE GALERÍA (FILTROS POR GRADO Y FECHA) ---
$grado_gal = isset($_GET['grado_gal']) ? $_GET['grado_gal'] : 'all';
$order_gal = isset($_GET['order_gal']) && $_GET['order_gal'] === 'asc' ? 'ASC' : 'DESC';
$query_gal = "SELECT * FROM galeria_fotos WHERE 1=1";
$params_gal = [];

if ($grado_gal === '5' || $grado_gal === '6') {
    $query_gal .= " AND grado = ?";
    $params_gal[] = $grado_gal;
}
$query_gal .= " ORDER BY fecha $order_gal";
$stmt_gal = $conn->prepare($query_gal);
$stmt_gal->execute($params_gal);
$galeria = $stmt_gal->fetchAll();

// --- CONSULTA DE PLANIFICACIONES (FILTROS POR GRADO Y FECHA) ---
$grado_plan = isset($_GET['grado_plan']) ? $_GET['grado_plan'] : 'all';
$order_plan = isset($_GET['order_plan']) && $_GET['order_plan'] === 'asc' ? 'ASC' : 'DESC';
$query_plan = "SELECT * FROM planificaciones WHERE 1=1";
$params_plan = [];

if ($grado_plan === '5' || $grado_plan === '6') {
    $query_plan .= " AND grado = ?";
    $params_plan[] = $grado_plan;
}
$query_plan .= " ORDER BY fecha $order_plan";
$stmt_plan = $conn->prepare($query_plan);
$stmt_plan->execute($params_plan);
$planificaciones = $stmt_plan->fetchAll();

// --- CONSULTA DE ASISTENCIA (FILTROS POR GRADO Y FECHA) ---
$grado_asist = isset($_GET['grado_asist']) ? $_GET['grado_asist'] : 'all';
$fecha_asist = isset($_GET['fecha_asist']) ? $_GET['fecha_asist'] : '';
$query_asist = "SELECT a.*, al.nombre, al.apellido, al.grado FROM asistencia a JOIN alumnos al ON a.alumno_id = al.id WHERE 1=1";
$params_asist = [];

if ($grado_asist === '5' || $grado_asist === '6') {
    $query_asist .= " AND al.grado = ?";
    $params_asist[] = $grado_asist;
}
if ($fecha_asist !== '') {
    $query_asist .= " AND a.fecha = ?";
    $params_asist[] = $fecha_asist;
}
$query_asist .= " ORDER BY a.fecha DESC, al.apellido ASC, al.nombre ASC";
$stmt_asist = $conn->prepare($query_asist);
$stmt_asist->execute($params_asist);
$asistencias = $stmt_asist->fetchAll();

// --- CONSULTA DE TESTIMONIOS ---
try {
    $testimonios = $conn->query("SELECT * FROM testimonios_bti ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $testimonios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRECE - Proyecto Aprendizaje y Servicio (3° BTI)</title>
    <meta name="description" content="Portal oficial de documentación y difusión del proyecto de Scratch de los estudiantes de 3° BTI del CRECE.">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="css/style.css?v=3">
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar-main" data-bs-offset="100">

    <!-- Navegación Fija -->
    <nav class="navbar navbar-expand-xl navbar-dark bg-primary sticky-top shadow-sm" id="navbar-main">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#9c131a" stroke="#1b3668" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M12 8v4" stroke="white" stroke-width="3"/>
                    <path d="M12 16h.01" stroke="white" stroke-width="3"/>
                </svg>
                <div class="d-flex flex-column lh-1">
                    <span class="fw-bold fs-5 tracking-wide">CRECE</span>
                    <span class="fs-7 text-light-50">3° BTI Scratch</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-1">
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold active" href="#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#historia">Colegio</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#institucion">Proyecto</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#proyectos">Juegos</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#galeria">Fotos</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#planificaciones">Planes</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#asistencia">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#experiencias">Testimonios</a></li>
                    <li class="nav-item"><a class="nav-link text-uppercase fs-7 fw-semibold" href="#contacto">Contacto</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-warning btn-sm text-white fw-bold text-uppercase px-3" href="admin/login.php">Acceso Admin</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sección de Inicio (Hero + Banner + Stats + Carrusel) -->
    <header id="inicio" class="bg-light-blue position-relative overflow-hidden py-5 py-lg-6">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge bg-warning text-primary px-3 py-2 rounded-pill fw-bold text-uppercase mb-3 tracking-wider shadow-sm">Proyecto Aprendizaje y Servicio</span>
                    <h1 class="display-4 fw-extrabold text-primary mb-3 text-gradient">Codifica tu mundo – construyendo el futuro bloque a bloque</h1>
                    <p class="lead text-muted mb-4">
                        Proyecto de Aprendizaje-Servicio. Los alumnos del 3° Año del BTI enseñan habilidades de pensamiento computacional a niños del 5to y 6to grado del CRECE.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#proyectos" class="btn btn-primary btn-lg shadow-sm">Explorar Proyectos</a>
                        <a href="#experiencias" class="btn btn-outline-primary btn-lg">Testimonios</a>
                    </div>

                    <!-- Estadísticas en Grid -->
                    <div class="row g-4 mt-5 pt-3 border-top">
                        <div class="col-6 col-sm-3 text-center border-end">
                            <h3 class="fw-bold text-primary mb-0"><?= $total_alumnos ?></h3>
                            <span class="text-muted fs-7">Alumnos</span>
                        </div>
                        <div class="col-6 col-sm-3 text-center border-end-sm">
                            <h3 class="fw-bold text-primary mb-0"><?= $total_proyectos ?></h3>
                            <span class="text-muted fs-7">Proyectos</span>
                        </div>
                        <div class="col-6 col-sm-3 text-center border-end">
                            <h3 class="fw-bold text-primary mb-0"><?= $total_fotos ?></h3>
                            <span class="text-muted fs-7">Evidencias</span>
                        </div>
                        <div class="col-6 col-sm-3 text-center">
                            <h3 class="fw-bold text-primary mb-0"><?= $total_planificaciones ?></h3>
                            <span class="text-muted fs-7">Clases</span>
                        </div>
                    </div>
                </div>

                <!-- Carrusel de Imágenes en Banner -->
                <div class="col-lg-6">
                    <div id="carouselHero" class="carousel slide shadow-lg rounded-4 overflow-hidden border border-3 border-white" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="1" aria-label="Slide 2"></button>
                            <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="2" aria-label="Slide 3"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active" data-bs-interval="4000">
                                <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=80" class="d-block w-100 object-fit-cover" style="height: 420px;" alt="Clase de Scratch">
                                <div class="carousel-caption d-none d-md-block bg-dark-gradient rounded px-3 py-2 text-start">
                                    <h5 class="fw-bold text-warning">Interacción Interactiva</h5>
                                    <p class="fs-7 text-light mb-0">Estudiantes aprendiendo la lógica computacional con bloques.</p>
                                </div>
                            </div>
                            <div class="carousel-item" data-bs-interval="4000">
                                <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1200&q=80" class="d-block w-100 object-fit-cover" style="height: 420px;" alt="Proyectos Creativos">
                                <div class="carousel-caption d-none d-md-block bg-dark-gradient rounded px-3 py-2 text-start">
                                    <h5 class="fw-bold text-warning">Lógica de Bucles</h5>
                                    <p class="fs-7 text-light mb-0">Diseño de laberintos y programación de variables en 6° grado.</p>
                                </div>
                            </div>
                            <div class="carousel-item" data-bs-interval="4000">
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80" class="d-block w-100 object-fit-cover" style="height: 420px;" alt="Exposiciones en Aula">
                                <div class="carousel-caption d-none d-md-block bg-dark-gradient rounded px-3 py-2 text-start">
                                    <h5 class="fw-bold text-warning">Exposición y Defensa</h5>
                                    <p class="fs-7 text-light mb-0">Alumnos compartiendo sus videojuegos desarrollados al final del taller.</p>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselHero" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselHero" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sección de Reseña Histórica del CRECE -->
    <section id="historia" class="py-5 py-lg-6 border-bottom bg-white">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">El Colegio</span>
                <h2 class="fw-extrabold text-primary text-gradient">Reseña Histórica de la Institución</h2>
                <p class="text-muted">Conoce la trayectoria y los pilares del Centro Regional de Educación.</p>
            </div>
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=800&q=80" alt="Fachada del CRECE" class="img-fluid rounded-4 shadow-lg mb-4" style="max-height: 250px; width: 100%; object-fit: cover;">
                    <h3 class="fw-bold text-primary mb-3">Historia y Oferta Educativa</h3>
                    <p class="text-muted text-justify">
                        El <strong>Centro Regional de Educación "Dr. José Gaspar Rodríguez de Francia" (CRECE)</strong> es una de las instituciones educativas públicas más reconocidas de Ciudad del Este, departamento de Alto Paraná, Paraguay. Fue inaugurado oficialmente el <strong>10 de marzo de 1977</strong> y se consolidó como uno de los principales centros educativos del este del país.
                    </p>
                    <p class="text-muted text-justify">
                        Ofrece educación en distintos niveles: Educación Escolar Básica, Bachillerato Científico, Bachillerato en Ciencias Sociales y Letras, <strong>Bachilleratos Técnicos (como el BTI)</strong>, y Formación Docente.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 bg-light p-4 rounded-4 shadow-sm mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fs-2 text-warning">💻</span>
                            <h4 class="fw-bold text-primary mb-0">Infraestructura y Tecnología</h4>
                        </div>
                        <p class="text-muted fs-7 mb-0 text-justify">
                            En los últimos años, la institución ha recibido importantes inversiones, como la donación de 60 computadoras para los estudiantes del Bachillerato Técnico y la construcción de un <strong>Aula de Robótica</strong> destinada a fomentar el aprendizaje de programación e inteligencia artificial.
                        </p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-primary text-white rounded-4 shadow-sm h-100 overflow-hidden">
                                <img src="https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=400&h=200&q=80" class="card-img-top object-fit-cover" style="height: 120px;" alt="Misión">
                                <div class="p-4">
                                    <h5 class="fw-bold text-warning mb-2">🎯 Misión</h5>
                                    <p class="fs-8 mb-0 text-justify text-white-50">Brindar una educación integral de calidad, promoviendo la formación académica, técnica y humana de los estudiantes, fomentando valores, innovación y compromiso con la sociedad.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-primary text-white rounded-4 shadow-sm h-100 overflow-hidden">
                                <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=400&h=200&q=80" class="card-img-top object-fit-cover" style="height: 120px;" alt="Visión">
                                <div class="p-4">
                                    <h5 class="fw-bold text-warning mb-2">👁️ Visión</h5>
                                    <p class="fs-8 mb-0 text-justify text-white-50">Ser una institución educativa líder y referente a nivel regional y nacional, reconocida por la excelencia académica, la innovación tecnológica y la formación de ciudadanos comprometidos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Detalles del Proyecto -->
    <section id="institucion" class="py-5 py-lg-6 border-bottom bg-light">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Centro Regional de Educación</span>
                <h2 class="fw-extrabold text-primary text-gradient">Detalles del Proyecto</h2>
                <p class="text-muted">Conoce a profundidad la investigación, metodología y objetivos de nuestro Proyecto de Aprendizaje-Servicio.</p>
            </div>

            <div class="accordion shadow-sm" id="accordionProyecto">
                
                <!-- IDENTIFICACIÓN -->
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingIdentificacion">
                        <button class="accordion-button fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIdentificacion" aria-expanded="true" aria-controls="collapseIdentificacion">
                            📋 Identificación y Responsables
                        </button>
                    </h2>
                    <div id="collapseIdentificacion" class="accordion-collapse collapse show" aria-labelledby="headingIdentificacion" data-bs-parent="#accordionProyecto">
                        <div class="accordion-body text-muted fs-7 row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-dark">Institución:</h6>
                                <p>Centro Regional De Educación “Dr. José Gaspar Rodríguez De Francia”</p>
                                <h6 class="fw-bold text-dark">Localidad y Año:</h6>
                                <p>Ciudad del Este, Alto Paraná, Paraguay. 2026</p>
                                <h6 class="fw-bold text-dark">Nivel y Curso:</h6>
                                <p>Nivel Medio, 3° BTI (Bachillerato Técnico en Informática) - Sección Única</p>
                                <h6 class="fw-bold text-dark">Destinatarios:</h6>
                                <p>Alumnos del 5to y 6to grado de la institución.</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-dark">Responsables:</h6>
                                <ul>
                                    <li>Alumnos del Tercer Año del Bachillerato Técnico en Informática</li>
                                    <li><strong>Directora BTI:</strong> Lic. Mirley Araujo</li>
                                    <li><strong>Coordinador y Tutor:</strong> Prof. Ing. Carlos Giménez</li>
                                </ul>
                                <h6 class="fw-bold text-dark">Eje Problemático:</h6>
                                <p>La falta de habilidades tecnológicas avanzadas a pesar del buen nivel de manejo de computadoras.</p>
                                <h6 class="fw-bold text-dark">Sub-eje Temático:</h6>
                                <p>Desarrollar habilidades de pensamiento computacional.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- INTRODUCCIÓN -->
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingIntro">
                        <button class="accordion-button collapsed fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIntro" aria-expanded="false" aria-controls="collapseIntro">
                            📝 Introducción y Diagnóstico
                        </button>
                    </h2>
                    <div id="collapseIntro" class="accordion-collapse collapse" aria-labelledby="headingIntro" data-bs-parent="#accordionProyecto">
                        <div class="accordion-body text-muted fs-7 text-justify">
                            <p><strong>Introducción:</strong> Este proyecto, titulado "Codifica tu mundo", es una iniciativa innovadora llevada a cabo por los estudiantes del Tercero BTI. El objetivo principal es impartir conocimientos en Scratch a los estudiantes de 5to y 6to grado. Los alumnos del Tercero BTI se convierten en mentores, fomentando el interés por la programación, la colaboración y el liderazgo.</p>
                            <p><strong>Diagnóstico:</strong> Mediante encuestas, descubrimos que existen niños sin acceso adecuado a computadoras o internet, mientras que otros poseen habilidades que podrían mejorarse. Confiamos en que estas acciones mejorarán las oportunidades de nuestros estudiantes para enfrentar desafíos futuros.</p>
                            <p><strong>Fundamentación:</strong> Ofrecemos cursos sobre programación sencilla de videojuegos para atraer el interés y enseñar informática. Consideramos que el manejo de la informática es vital para su futuro y su desempeño en un mercado laboral competitivo.</p>
                        </div>
                    </div>
                </div>

                <!-- OBJETIVOS Y PROBLEMAS -->
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingObj">
                        <button class="accordion-button collapsed fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseObj" aria-expanded="false" aria-controls="collapseObj">
                            🎯 Núcleo Problemático y Objetivos
                        </button>
                    </h2>
                    <div id="collapseObj" class="accordion-collapse collapse" aria-labelledby="headingObj" data-bs-parent="#accordionProyecto">
                        <div class="accordion-body text-muted fs-7">
                            <p><strong>Núcleo Problemático:</strong> Falta de desarrollo de habilidades con la tecnología y la utilización de herramientas de programación para resolver problemas y crear soluciones.</p>
                            <p><strong>Temas Transversales:</strong> Desarrollo del pensamiento computacional, resolución de problemas y Pensamiento crítico a través de talleres de Scratch.</p>
                            <h6 class="fw-bold text-dark mt-3">Objetivo General:</h6>
                            <p>Fomentar el desarrollo de habilidades creativas y del razonamiento lógico en los niños a través de actividades educativas y lúdicas, con el objetivo de apoyar su crecimiento académico y personal.</p>
                            <h6 class="fw-bold text-dark mt-3">Objetivos Específicos:</h6>
                            <ul>
                                <li>Comprender cómo el desarrollo de videojuegos ayuda a adquirir pensamiento computacional.</li>
                                <li>Identificar de qué manera la creación de videojuegos motiva a aprender y fomenta habilidades autodidactas.</li>
                                <li>Explorar cómo la programación ayuda a desarrollar el razonamiento lógico en los niños.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- MARCO TEORICO -->
                <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header" id="headingTeoria">
                        <button class="accordion-button collapsed fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTeoria" aria-expanded="false" aria-controls="collapseTeoria">
                            📚 Marco Teórico y Contenidos
                        </button>
                    </h2>
                    <div id="collapseTeoria" class="accordion-collapse collapse" aria-labelledby="headingTeoria" data-bs-parent="#accordionProyecto">
                        <div class="accordion-body text-muted fs-7 text-justify">
                            <h6 class="fw-bold text-dark mt-3">Contenidos a Desarrollar:</h6>
                            <p>Introducción a Scratch, Objetos, Programación en bloques, Animación, El plano cartesiano, Movimiento, Mensajes, Condicionales, Bucles, Objetos consumibles, Variables e Iteradores.</p>
                            <h6 class="fw-bold text-dark mt-3">Programación en Bloque y Scratch:</h6>
                            <p>Un bloque de código es un conjunto de instrucciones que se agrupan para realizar una tarea específica. Scratch es un lenguaje de programación gráfico diseñado para edades de 8 a 16 años, que permite crear juegos y animaciones arrastrando piezas a modo de puzle. Ayuda a pensar creativamente y razonar sistemáticamente.</p>
                        </div>
                    </div>
                </div>

                <!-- METODOLOGIA -->
                <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="headingMetodologia">
                        <button class="accordion-button collapsed fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMetodologia" aria-expanded="false" aria-controls="collapseMetodologia">
                            ⚙️ Metodología y Evaluación
                        </button>
                    </h2>
                    <div id="collapseMetodologia" class="accordion-collapse collapse" aria-labelledby="headingMetodologia" data-bs-parent="#accordionProyecto">
                        <div class="accordion-body text-muted fs-7">
                            <h6 class="fw-bold text-dark">Desarrollo de Tareas:</h6>
                            <ul>
                                <li><strong>Tarea 1:</strong> Conformación de grupos y división por grados (5to: fundamentos, 6to: profundización).</li>
                                <li><strong>Tarea 2, 3 y 4:</strong> Diseño, aplicación y análisis de encuestas a 300 alumnos para diagnóstico.</li>
                                <li><strong>Tarea 5 y 6:</strong> Estudio e instalación de Scratch en los laboratorios del CRECE.</li>
                                <li><strong>Tarea 7:</strong> Desarrollo de las clases prácticas (plano cartesiano, variables, bucles, etc.).</li>
                            </ul>
                            <h6 class="fw-bold text-dark mt-3">Proceso de Evaluación y Conclusión:</h6>
                            <p>Se evalúa el uso de tecnología digital, la capacidad crítica, el razonamiento lógico-matemático y el trabajo en equipo. Como conclusión, el proyecto busca empoderar a los estudiantes a través de la educación informática, preparándolos para enfrentar los desafíos de un mundo cada vez más digitalizado.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Sección de Proyectos Scratch -->
    <section id="proyectos" class="py-5 py-lg-6 bg-light border-bottom">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Catálogo de Desarrollo</span>
                <h2 class="fw-extrabold text-primary">Proyectos Scratch Desarrollados</h2>
                <p class="text-muted">Explora la lista de videojuegos e historias interactivas creadas en clase.</p>
            </div>

            <!-- Formulario de Búsqueda y Filtro Servidor -->
            <form method="GET" action="index.php#proyectos" class="row g-3 mb-5 justify-content-center">
                <div class="col-md-5">
                    <input type="text" name="search_proj" class="form-control" placeholder="Buscar proyecto por nombre o descripción..." value="<?= htmlspecialchars($search_proj) ?>">
                </div>
                <div class="col-md-3">
                    <select name="grado_proj" class="form-select">
                        <option value="all" <?= $grado_proj === 'all' ? 'selected' : '' ?>>Todos los grados</option>
                        <option value="5" <?= $grado_proj === '5' ? 'selected' : '' ?>>5° Grado</option>
                        <option value="6" <?= $grado_proj === '6' ? 'selected' : '' ?>>6° Grado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
                <?php if ($search_proj !== '' || $grado_proj !== 'all'): ?>
                    <div class="col-md-2">
                        <a href="index.php#proyectos" class="btn btn-secondary w-100">Restablecer</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Grid de Proyectos -->
            <div class="row g-4">
                <?php if (count($proyectos) === 0): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No se encontraron proyectos Scratch con los criterios de búsqueda seleccionados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($proyectos as $proj): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative hover-lift">
                                <div class="card-body p-4 d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge <?= $proj['grado'] === '5' ? 'bg-primary-soft text-primary-color' : 'bg-purple-soft text-purple' ?> px-3 py-2 rounded-pill fw-bold">
                                            <?= $proj['grado'] ?>° Grado
                                        </span>
                                        <span class="text-muted fs-7"><?= date('d/m/Y', strtotime($proj['fecha_creacion'])) ?></span>
                                    </div>
                                    <h4 class="card-title fw-bold text-primary mb-2"><?= htmlspecialchars($proj['titulo']) ?></h4>
                                    <p class="card-text text-muted fs-7 mb-4 flex-grow-1"><?= htmlspecialchars($proj['descripcion']) ?></p>
                                    
                                    <div class="d-flex gap-2 mt-auto border-top pt-3">
                                        <?php if (!empty($proj['ruta_pdf'])): ?>
                                            <a href="<?= htmlspecialchars($proj['ruta_pdf']) ?>" target="_blank" class="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15h6"/><path d="M9 11h6"/></svg>
                                                Ver Guía PDF
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($proj['url_proyecto'])): ?>
                                            <a href="<?= htmlspecialchars($proj['url_proyecto']) ?>" target="_blank" rel="noopener" class="btn btn-warning btn-sm text-primary fw-bold d-flex align-items-center justify-content-center gap-1">
                                                <span>Jugar</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección de Galería de Fotos (Filtros por Grado y Fecha + Lightbox) -->
    <section id="galeria" class="py-5 py-lg-6 border-bottom">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Evidencias en el Aula</span>
                <h2 class="fw-extrabold text-primary">Galería de Fotos del Proyecto</h2>
                <p class="text-muted">Registro fotográfico del trabajo realizado en el laboratorio informático.</p>
            </div>

            <!-- Filtros Galería -->
            <form method="GET" action="index.php#galeria" class="row g-3 justify-content-between align-items-center mb-4 border-bottom pb-4">
                <input type="hidden" name="search_proj" value="<?= htmlspecialchars($search_proj) ?>">
                <input type="hidden" name="grado_proj" value="<?= htmlspecialchars($grado_proj) ?>">
                
                <div class="col-md-auto d-flex gap-2 align-items-center flex-wrap">
                    <span class="fw-bold fs-7 text-muted me-2">Filtrar Grado:</span>
                    <a href="index.php?grado_gal=all&order_gal=<?= $order_gal ?>#galeria" class="btn btn-sm <?= $grado_gal === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
                    <a href="index.php?grado_gal=5&order_gal=<?= $order_gal ?>#galeria" class="btn btn-sm <?= $grado_gal === '5' ? 'btn-primary' : 'btn-outline-primary' ?>">5° Grado</a>
                    <a href="index.php?grado_gal=6&order_gal=<?= $order_gal ?>#galeria" class="btn btn-sm <?= $grado_gal === '6' ? 'btn-primary' : 'btn-outline-primary' ?>">6° Grado</a>
                </div>
                <div class="col-md-auto d-flex gap-2 align-items-center">
                    <span class="fw-bold fs-7 text-muted">Fecha:</span>
                    <select name="order_gal" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="desc" <?= $order_gal === 'DESC' ? 'selected' : '' ?>>Más reciente primero</option>
                        <option value="asc" <?= $order_gal === 'ASC' ? 'selected' : '' ?>>Más antiguo primero</option>
                    </select>
                    <input type="hidden" name="grado_gal" value="<?= htmlspecialchars($grado_gal) ?>">
                </div>
            </form>

            <!-- Grid de Galería -->
            <div class="row g-4">
                <?php if (count($galeria) === 0): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No hay imágenes registradas para este grado.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($galeria as $foto): ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden galeria-card" onclick="openLightbox('<?= htmlspecialchars($foto['ruta_imagen']) ?>', '<?= htmlspecialchars($foto['titulo']) ?>', '<?= htmlspecialchars($foto['descripcion']) ?>')">
                                <div class="position-relative overflow-hidden" style="height: 180px;">
                                    <img src="<?= htmlspecialchars($foto['ruta_imagen']) ?>" class="w-100 h-100 object-fit-cover transition-img" alt="<?= htmlspecialchars($foto['titulo']) ?>">
                                    <span class="badge <?= $foto['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?> position-absolute top-0 end-0 m-3 px-3 py-1.5 shadow">
                                        <?= $foto['grado'] ?>° Grado
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <span class="text-muted fs-8 d-block mb-1"><?= date('d M, Y', strtotime($foto['fecha'])) ?></span>
                                    <h5 class="fw-bold text-primary fs-7 mb-1 text-truncate"><?= htmlspecialchars($foto['titulo']) ?></h5>
                                    <p class="text-muted fs-8 mb-0 text-truncate"><?= htmlspecialchars($foto['descripcion']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección de Planificaciones Curriculares -->
    <section id="planificaciones" class="py-5 py-lg-6 bg-light border-bottom">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Organización Curricular</span>
                <h2 class="fw-extrabold text-primary">Planificaciones y Guiones</h2>
                <p class="text-muted">Consulta la estructura didáctica aplicada a las clases por grado.</p>
            </div>

            <!-- Filtros Planificaciones -->
            <form method="GET" action="index.php#planificaciones" class="row g-3 justify-content-between align-items-center mb-4 border-bottom pb-4">
                <input type="hidden" name="search_proj" value="<?= htmlspecialchars($search_proj) ?>">
                <input type="hidden" name="grado_proj" value="<?= htmlspecialchars($grado_proj) ?>">
                <input type="hidden" name="grado_gal" value="<?= htmlspecialchars($grado_gal) ?>">
                <input type="hidden" name="order_gal" value="<?= htmlspecialchars($order_gal) ?>">

                <div class="col-md-auto d-flex gap-2 align-items-center flex-wrap">
                    <span class="fw-bold fs-7 text-muted me-2">Filtrar Grado:</span>
                    <a href="index.php?grado_plan=all&order_plan=<?= $order_plan ?>#planificaciones" class="btn btn-sm <?= $grado_plan === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
                    <a href="index.php?grado_plan=5&order_plan=<?= $order_plan ?>#planificaciones" class="btn btn-sm <?= $grado_plan === '5' ? 'btn-primary' : 'btn-outline-primary' ?>">5° Grado</a>
                    <a href="index.php?grado_plan=6&order_plan=<?= $order_plan ?>#planificaciones" class="btn btn-sm <?= $grado_plan === '6' ? 'btn-primary' : 'btn-outline-primary' ?>">6° Grado</a>
                </div>
                <div class="col-md-auto d-flex gap-2 align-items-center">
                    <span class="fw-bold fs-7 text-muted">Fecha:</span>
                    <select name="order_plan" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="desc" <?= $order_plan === 'DESC' ? 'selected' : '' ?>>Más reciente primero</option>
                        <option value="asc" <?= $order_plan === 'ASC' ? 'selected' : '' ?>>Más antiguo primero</option>
                    </select>
                    <input type="hidden" name="grado_plan" value="<?= htmlspecialchars($grado_plan) ?>">
                </div>
            </form>

            <!-- Lista de Planes -->
            <div class="d-flex flex-column gap-3">
                <?php if (count($planificaciones) === 0): ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm text-center">
                        <p class="text-muted mb-0">No hay planificaciones didácticas cargadas para este grado.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($planificaciones as $plan): ?>
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-light d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 hover-slide">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge <?= $plan['grado'] === '5' ? 'bg-primary' : 'bg-purple' ?> px-2 py-1 fs-8 rounded">
                                        <?= $plan['grado'] ?>° Grado
                                    </span>
                                    <span class="text-muted fs-8 fw-semibold"><?= date('d/m/Y', strtotime($plan['fecha'])) ?></span>
                                </div>
                                <h4 class="fw-bold text-primary fs-6 mb-1"><?= htmlspecialchars($plan['titulo']) ?></h4>
                                <p class="text-muted fs-7 mb-0"><?= htmlspecialchars($plan['descripcion']) ?></p>
                            </div>
                            <div class="flex-shrink-0 align-self-stretch align-self-md-auto d-flex align-items-center">
                                <a href="<?= htmlspecialchars($plan['ruta_archivo']) ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-1 px-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Ver / Descargar PDF
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección de Asistencia -->
    <section id="asistencia" class="py-5 py-lg-6 border-bottom">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Registro y Monitoreo</span>
                <h2 class="fw-extrabold text-primary">Consulta de Asistencia diaria</h2>
                <p class="text-muted">Monitoreo de participación presencial de los estudiantes en los talleres.</p>
            </div>

            <!-- Filtros Asistencia -->
            <form method="GET" action="index.php#asistencia" class="card border-0 bg-primary p-4 rounded-4 shadow mb-4 text-white">
                <input type="hidden" name="search_proj" value="<?= htmlspecialchars($search_proj) ?>">
                <input type="hidden" name="grado_proj" value="<?= htmlspecialchars($grado_proj) ?>">
                <input type="hidden" name="grado_gal" value="<?= htmlspecialchars($grado_gal) ?>">
                <input type="hidden" name="order_gal" value="<?= htmlspecialchars($order_gal) ?>">
                <input type="hidden" name="grado_plan" value="<?= htmlspecialchars($grado_plan) ?>">
                <input type="hidden" name="order_plan" value="<?= htmlspecialchars($order_plan) ?>">

                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fs-7 fw-bold">Filtrar por Grado:</label>
                        <select name="grado_asist" class="form-select border-0 shadow-sm bg-white text-dark">
                            <option value="all" <?= $grado_asist === 'all' ? 'selected' : '' ?>>Todos los grados</option>
                            <option value="5" <?= $grado_asist === '5' ? 'selected' : '' ?>>5° Grado</option>
                            <option value="6" <?= $grado_asist === '6' ? 'selected' : '' ?>>6° Grado</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fs-7 fw-bold">Filtrar por Fecha:</label>
                        <input type="date" name="fecha_asist" class="form-select border-0 shadow-sm bg-white text-dark" value="<?= htmlspecialchars($fecha_asist) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning text-primary fw-bold w-100 shadow-sm">Consultar</button>
                    </div>
                </div>
            </form>

            <!-- Tabla de Asistencia -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-primary border-bottom py-3">
                            <tr>
                                <th class="px-4 py-3">Estudiante</th>
                                <th class="py-3">Grado</th>
                                <th class="py-3">Fecha Clase</th>
                                <th class="py-3">Estado</th>
                                <th class="px-4 py-3">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($asistencias) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros de asistencia con los filtros seleccionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($asistencias as $asist): ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold text-primary"><?= htmlspecialchars($asist['apellido'] . ', ' . $asist['nombre']) ?></td>
                                        <td>
                                            <span class="badge <?= $asist['grado'] === '5' ? 'bg-primary-soft text-primary-color' : 'bg-purple-soft text-purple' ?> px-2 py-1 rounded">
                                                <?= $asist['grado'] ?>° Grado
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($asist['fecha'])) ?></td>
                                        <td>
                                            <?php if ($asist['estado'] === 'Presente'): ?>
                                                <span class="text-success fw-bold d-inline-flex align-items-center gap-1">
                                                    <span class="d-inline-block bg-success rounded-circle" style="width: 8px; height: 8px;"></span>
                                                    Presente
                                                </span>
                                            <?php elseif ($asist['estado'] === 'Ausente'): ?>
                                                <span class="text-danger fw-bold d-inline-flex align-items-center gap-1">
                                                    <span class="d-inline-block bg-danger rounded-circle" style="width: 8px; height: 8px;"></span>
                                                    Ausente
                                                </span>
                                            <?php else: ?>
                                                <span class="text-warning fw-bold d-inline-flex align-items-center gap-1">
                                                    <span class="d-inline-block bg-warning rounded-circle" style="width: 8px; height: 8px;"></span>
                                                    Justificado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-muted fs-7"><?= htmlspecialchars($asist['observacion'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Experiencias de Alumnos de 3° BTI -->
    <section id="experiencias" class="py-5 py-lg-6 bg-light border-bottom">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Testimonios de Servicio</span>
                <h2 class="fw-extrabold text-primary">Experiencias del 3° BTI</h2>
                <p class="text-muted">Voces y reflexiones de los estudiantes encargados de liderar y enseñar en los talleres.</p>
            </div>

            <!-- Listado de Testimonios -->
            <div class="row g-4 justify-content-center">
                <?php if (count($testimonios) === 0): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No se han registrado testimonios todavía.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($testimonios as $test): ?>
                        <div class="col-lg-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white position-relative">
                                <div class="position-absolute top-0 end-0 m-4 text-warning opacity-10" style="font-size: 8rem; font-family: Georgia, serif; line-height: 1; pointer-events: none;">“</div>
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="flex-shrink-0" style="width: 70px; height: 70px;">
                                        <img src="<?= htmlspecialchars($test['ruta_foto'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80') ?>" class="w-100 h-100 object-fit-cover rounded-circle border border-2 border-primary" alt="<?= htmlspecialchars($test['nombre_alumno']) ?>">
                                    </div>
                                    <div>
                                        <h4 class="fw-bold text-primary fs-5 mb-0"><?= htmlspecialchars($test['nombre_alumno']) ?></h4>
                                        <span class="text-muted fs-8 fw-semibold">Alumno del 3° BTI CRECE</span>
                                    </div>
                                </div>
                                <blockquote class="blockquote fs-7 text-muted mb-4 italic text-justify">
                                    "<?= htmlspecialchars($test['testimonio']) ?>"
                                </blockquote>
                                <div class="row g-3 border-top pt-3 mt-auto">
                                    <div class="col-sm-6">
                                        <h5 class="fw-bold text-primary fs-8 text-uppercase tracking-wide mb-1">💡 Aprendizaje</h5>
                                        <p class="text-muted fs-8 mb-0 text-justify"><?= htmlspecialchars($test['aprendizaje']) ?></p>
                                    </div>
                                    <div class="col-sm-6 border-start-sm">
                                        <h5 class="fw-bold text-primary fs-8 text-uppercase tracking-wide mb-1">⚠️ Dificultad superada</h5>
                                        <p class="text-muted fs-8 mb-0 text-justify"><?= htmlspecialchars($test['dificultad']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Sección de Contacto y Ubicación -->
    <section id="contacto" class="py-5 py-lg-6">
        <div class="container">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="text-warning fw-bold text-uppercase fs-7 tracking-wider d-block mb-1">Canales de Comunicación</span>
                <h2 class="fw-extrabold text-primary">Contacto y Ubicación</h2>
                <p class="text-muted">Envíanos un mensaje o encuéntranos fácilmente en el mapa interactivo.</p>
            </div>

            <!-- Alertas Formulario -->
            <?php if ($contact_success): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <?= $contact_success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($contact_error): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    <?= $contact_error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-5">
                <!-- Información de Contacto + Formulario -->
                <div class="col-lg-5">
                    <div class="card border-0 bg-primary text-white p-4 rounded-4 shadow-sm mb-4">
                        <h4 class="fw-bold mb-4">Información de la Institución</h4>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex gap-3 align-items-start">
                                <span class="fs-4 text-warning">📍</span>
                                <div>
                                    <h5 class="fw-bold fs-7 text-uppercase tracking-wider mb-1">Dirección</h5>
                                    <p class="fs-7 text-white-50 mb-0">Avenida, Cd. del Este 100151, Paraguay<br>F9GP+6R Cd. del Este, Paraguay.</p>
                                </div>
                            </div>
                            <div class="d-flex gap-3 align-items-start">
                                <span class="fs-4 text-warning">📞</span>
                                <div>
                                    <h5 class="fw-bold fs-7 text-uppercase tracking-wider mb-1">Teléfono</h5>
                                    <p class="fs-7 text-white-50 mb-0">+595 61 511 200</p>
                                </div>
                            </div>
                            <div class="d-flex gap-3 align-items-start">
                                <span class="fs-4 text-warning">✉️</span>
                                <div>
                                    <h5 class="fw-bold fs-7 text-uppercase tracking-wider mb-1">Correo Electrónico</h5>
                                    <p class="fs-7 text-white-50 mb-0">contacto@crece.edu.py</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <div class="card border-0 bg-light p-4 rounded-4 shadow-sm">
                        <h4 class="fw-bold text-primary mb-3">Enviar un Mensaje</h4>
                        <form method="POST" action="index.php#contacto" id="form-contacto">
                            <input type="hidden" name="action" value="contact">
                            <div class="mb-3">
                                <label for="form-name" class="form-label fs-7 fw-semibold">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control bg-white border-light shadow-sm" id="form-name" required placeholder="Escribe tu nombre...">
                            </div>
                            <div class="mb-3">
                                <label for="form-email" class="form-label fs-7 fw-semibold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control bg-white border-light shadow-sm" id="form-email" required placeholder="ejemplo@correo.com">
                            </div>
                            <div class="mb-3">
                                <label for="form-subject" class="form-label fs-7 fw-semibold">Asunto</label>
                                <input type="text" name="asunto" class="form-control bg-white border-light shadow-sm" id="form-subject" required placeholder="Consulta sobre el proyecto...">
                            </div>
                            <div class="mb-3">
                                <label for="form-message" class="form-label fs-7 fw-semibold">Mensaje</label>
                                <textarea name="mensaje" class="form-control bg-white border-light shadow-sm" id="form-message" rows="4" required placeholder="Escribe tu mensaje aquí..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm py-2 text-uppercase fw-bold fs-7 tracking-wider">Enviar Mensaje</button>
                        </form>
                    </div>
                </div>

                <!-- Mapa Embebido de Google Maps -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100" style="min-height: 450px;">
                        <!-- Mapa del CRECE Ciudad del Este -->
                        <iframe 
                            src="https://maps.google.com/maps?q=F9GP%2B6R%20Ciudad%20del%20Este,%20Paraguay&t=&z=16&ie=UTF8&iwloc=&output=embed" 
                            width="100%" 
                            height="100%" 
                            style="border:0; min-height: 450px;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Institucional con Redes Sociales -->
    <footer class="bg-primary text-white pt-5 pb-3">
        <div class="container">
            <div class="row g-4 justify-content-between mb-4">
                <div class="col-lg-5">
                    <h3 class="fw-extrabold text-warning mb-3">Colegio CRECE</h3>
                    <p class="text-white-50 fs-7 text-justify max-w-400">
                        Centro Regional de Educación "Dr. José G. Rodríguez de Francia" - Ciudad del Este. Institución de referencia formando profesionales técnicos comprometidos con el servicio de su comunidad.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <h4 class="fw-bold mb-3">Redes Sociales Oficiales</h4>
                    <div class="d-flex gap-3 justify-content-lg-end">
                        <a href="https://facebook.com" target="_blank" rel="noopener" class="social-icon-btn" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-facebook" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/></svg>
                        </a>
                        <a href="https://instagram.com" target="_blank" rel="noopener" class="social-icon-btn" aria-label="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16"><path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.6.282-.109.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/></svg>
                        </a>
                        <a href="https://youtube.com" target="_blank" rel="noopener" class="social-icon-btn" aria-label="YouTube">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16"><path d="M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.104 2.081a2.01 2.01 0 0 1-1.415 1.419c-1.114.3-5.27.3-6.11.3h-.088c-.823-.003-4.987-.03-6.11-.3a2.01 2.01 0 0 1-1.415-1.419c-.101-.38-.172-.882-.22-1.4l-.011-.104-.022-.261-.009-.104c-.065-.913-.073-1.77-.074-1.957V7.57c.001-.194.01-1.108.104-2.081a2.01 2.01 0 0 1 1.415-1.419c1.113-.3 5.27-.3 6.11-.3h.088zM5.93 11.258l4.808-2.619-4.808-2.62v5.239z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-top border-light-50 pt-3 text-center text-white-50 fs-8">
                <p class="mb-1">&copy; 2026 Centro Regional de Educación "Dr. José G. Rodríguez de Francia". Todos los derechos reservados.</p>
                <p class="mb-0">Proyecto Integrador para el 3° BTI - Desarrollado en PHP, MySQL y Bootstrap 5.</p>
            </div>
        </div>
    </footer>

    <!-- Ventana Modal Lightbox para Ampliar Fotos de Galería -->
    <div class="lightbox-modal" id="lightbox-modal" onclick="closeLightbox(event)">
        <button class="lightbox-close" onclick="closeLightbox(event)">&times;</button>
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <img src="" id="lightbox-img" alt="Vista Ampliada">
            <div class="lightbox-caption">
                <h5 id="lightbox-title" class="fw-bold text-warning mb-1"></h5>
                <p id="lightbox-desc" class="mb-0 fs-7 text-white-50"></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script Personalizado -->
    <script src="js/script.js"></script>
    
    <!-- Script para abrir/cerrar lightbox -->
    <script>
        function openLightbox(src, title, desc) {
            const modal = document.getElementById('lightbox-modal');
            const img = document.getElementById('lightbox-img');
            const txtTitle = document.getElementById('lightbox-title');
            const txtDesc = document.getElementById('lightbox-desc');
            
            img.src = src;
            txtTitle.textContent = title;
            txtDesc.textContent = desc;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox(event) {
            const modal = document.getElementById('lightbox-modal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
