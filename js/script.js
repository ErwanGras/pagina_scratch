/* ====================================================================
   CRECE - Proyecto Integrador 3° BTI
   Script de Lógica Cliente (Validaciones, Lightbox y Navegación)
   ==================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    // 1. Cerrar el menú colapsado de Bootstrap en móvil al hacer clic en un enlace
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
    const menuCollapse = document.getElementById('navbarNav');

    if (menuCollapse && navLinks) {
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                const bsCollapse = bootstrap.Collapse.getInstance(menuCollapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            });
        });
    }

    // 2. Validación en el cliente del Formulario de Contacto
    const formContacto = document.getElementById('form-contacto');
    if (formContacto) {
        formContacto.addEventListener('submit', (e) => {
            const nombre = document.getElementById('form-name').value.trim();
            const email = document.getElementById('form-email').value.trim();
            const asunto = document.getElementById('form-subject').value.trim();
            const mensaje = document.getElementById('form-message').value.trim();

            if (nombre === '' || email === '' || asunto === '' || mensaje === '') {
                e.preventDefault();
                alert('Por favor, completa todos los campos del formulario antes de enviar.');
                return;
            }

            // Expresión regular para validar formato de correo electrónico
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Por favor, ingresa una dirección de correo electrónico válida (ejemplo: usuario@correo.com).');
                return;
            }
        });
    }

    // 3. Cerrar Lightbox presionando la tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const lightbox = document.getElementById('lightbox-modal');
            if (lightbox && lightbox.classList.contains('show')) {
                lightbox.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
    });

});
