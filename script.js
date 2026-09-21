/**
 * AURA-ADAMO · LÓGICA FRONTEND & CONECTIVIDAD API
 * Arquitectura: Vanilla JavaScript ES6+ (Sin dependencias)
 * Conexión: DonWeb Cloud (aura-api / sistema_aura.php)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Control del Menú Móvil (Pill Navbar) ---
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const toggleIcon = document.getElementById('toggle-icon');
    const navLinks = document.querySelectorAll('.nav-link');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const isActive = navMenu.classList.contains('active');
            
            if (toggleIcon) {
                toggleIcon.className = isActive ? 'bx bx-x' : 'bx bx-menu';
            }
        });

        // Cerrar menú móvil al hacer clic en un enlace
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                if (toggleIcon) {
                    toggleIcon.className = 'bx bx-menu';
                }
            });
        });
    }

    // --- 2. Animaciones Suaves on Scroll (IntersectionObserver) ---
    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Desactivar observación una vez revelado para mejor rendimiento
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        // Fallback para navegadores antiguos
        revealElements.forEach(el => el.classList.add('visible'));
    }

    // --- 3. Enlace Activo en Navbar según Sección Visible ---
    const sections = document.querySelectorAll('section[id]');
    
    window.addEventListener('scroll', () => {
        const scrollY = window.pageYOffset;

        sections.forEach(current => {
            const sectionHeight = current.offsetHeight;
            const sectionTop = current.offsetTop - 140;
            const sectionId = current.getAttribute('id');
            const targetLink = document.querySelector(`.nav-link[href*="${sectionId}"]`);

            if (targetLink && !targetLink.classList.contains('btn-nav-contact')) {
                if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                    targetLink.classList.add('active');
                } else {
                    targetLink.classList.remove('active');
                }
            }
        });
    }, { passive: true });

    // --- 4. Conexión Formulario de Contacto (Fetch API con aura-api/contacto.php) ---
    const contactForm = document.getElementById('aura-contact-form');
    const submitBtn = document.getElementById('btn-submit-contact');
    const alertBox = document.getElementById('contact-alert');

    if (contactForm && submitBtn) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = document.getElementById('contact-name').value.trim();
            const email = document.getElementById('contact-email').value.trim();
            const phone = document.getElementById('contact-phone') ? document.getElementById('contact-phone').value.trim() : '';
            const service = document.getElementById('contact-service').value;
            const message = document.getElementById('contact-message').value.trim();

            if (!name || !email || !message) {
                showAlert('Por favor completá los campos obligatorios (*).', 'error');
                return;
            }

            // Activar estado cargando en el botón
            setLoadingState(true);
            hideAlert();

            try {
                // Enviar datos en formato JSON a la API nativa de DonWeb
                const response = await fetch('aura-api/contacto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        nombre: name,
                        email: email,
                        telefono: phone,
                        servicio: service,
                        mensaje: message
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert(data.message || '¡Mensaje recibido con éxito! Te responderemos a la brevedad.', 'success');
                    contactForm.reset();
                } else {
                    showAlert(data.error || data.message || 'Ocurrió un error al procesar tu consulta. Intenta nuevamente.', 'error');
                }
            } catch (err) {
                console.error('Error al conectar con la API de Aura:', err);
                showAlert('No pudimos conectar con el servidor. Podés escribirnos directo a contacto@aura-adamo.site.', 'error');
            } finally {
                setLoadingState(false);
            }
        });
    }

    function setLoadingState(isLoading) {
        if (!submitBtn) return;
        const btnText = submitBtn.querySelector('.btn-text');
        const btnSpinner = submitBtn.querySelector('.btn-spinner');

        submitBtn.disabled = isLoading;
        if (isLoading) {
            if (btnText) btnText.style.display = 'none';
            if (btnSpinner) btnSpinner.style.display = 'inline-flex';
        } else {
            if (btnText) btnText.style.display = 'inline-flex';
            if (btnSpinner) btnSpinner.style.display = 'none';
        }
    }

    function showAlert(msg, type) {
        if (!alertBox) return;
        alertBox.className = `contact-alert ${type}`;
        const icon = type === 'success' ? "<i class='bx bx-check-circle'></i>" : "<i class='bx bx-error-circle'></i>";
        alertBox.innerHTML = `${icon} <span>${msg}</span>`;
        alertBox.style.display = 'flex';
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.style.display = 'none';
    }

    // --- 5. Contador de Visitas Dinámico Aura (DonWeb) ---
    const contadorElem = document.getElementById('contador-visitas-aura');
    
    if (contadorElem) {
        // Consultar el endpoint de telemetría de Aura en DonWeb
        fetch('https://l1deres.site/sistema_aura.php?accion=visita&sitio=aura-adamo')
            .then(res => res.json())
            .then(data => {
                if (data && data.total) {
                    contadorElem.innerText = Number(data.total).toLocaleString('es-AR');
                } else {
                    contadorElem.innerText = 'Activo';
                }
            })
            .catch(() => {
                // Fallback de contingencia local o relativo
                fetch('aura-api/status.php')
                    .then(res => res.json())
                    .then(data => {
                        contadorElem.innerText = (data && data.visitas) ? Number(data.visitas).toLocaleString('es-AR') : 'Activo';
                    })
                    .catch(() => {
                        contadorElem.innerText = 'Activo';
                    });
            });
    }

});
