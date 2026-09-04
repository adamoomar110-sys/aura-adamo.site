document.addEventListener('DOMContentLoaded', () => {
    
    // --- Menú Móvil ---
    const menuIcon = document.getElementById('menu-icon');
    const navbar = document.getElementById('navbar');
    const navLinks = document.querySelectorAll('.nav-link');

    menuIcon.addEventListener('click', () => {
        navbar.classList.toggle('active');
        // Cambiar icono entre menú y X
        const icon = menuIcon.querySelector('i');
        if (navbar.classList.contains('active')) {
            icon.classList.remove('bx-menu');
            icon.classList.add('bx-x');
        } else {
            icon.classList.remove('bx-x');
            icon.classList.add('bx-menu');
        }
    });

    // Cerrar menú móvil al hacer click en un enlace
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navbar.classList.remove('active');
            const icon = menuIcon.querySelector('i');
            icon.classList.remove('bx-x');
            icon.classList.add('bx-menu');
        });
    });

    // --- Animaciones on Scroll (Fade-In-Up) ---
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Opcional: Descomentar la siguiente línea si quieres que la animación ocurra solo 1 vez
                // observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const fadeElements = document.querySelectorAll('.fade-in-up');
    fadeElements.forEach(el => observer.observe(el));

    // --- Header Background on Scroll ---
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.05)';
        } else {
            header.style.boxShadow = 'none';
        }
    });

    // --- Formulario de Contacto / Cotización (API a0170001_aura) ---
    const contactForm = document.getElementById('aura-contact-form');
    const submitBtn = document.getElementById('btn-submit-contact');
    const alertBox = document.getElementById('contact-alert');

    if (contactForm && submitBtn) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = document.getElementById('contact-name').value.trim();
            const email = document.getElementById('contact-email').value.trim();
            const phone = document.getElementById('contact-phone').value.trim();
            const service = document.getElementById('contact-service').value;
            const message = document.getElementById('contact-message').value.trim();

            if (!name || !email || !message) {
                showAlert('Por favor completá los campos obligatorios (*).', 'error');
                return;
            }

            // Cambiar estado a cargando
            setLoading(true);
            hideAlert();

            try {
                const response = await fetch('aura-api/contacto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
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
                    showAlert(data.message || '¡Mensaje enviado con éxito! Te contactaremos a la brevedad.', 'success');
                    contactForm.reset();
                } else {
                    showAlert(data.error || 'Ocurrió un error al enviar tu consulta. Intentá nuevamente.', 'error');
                }
            } catch (err) {
                console.error('Error al conectar con la API de Aura:', err);
                showAlert('Error de conexión con el servidor. Por favor intentá más tarde o escribinos a contacto@aura-adamo.site.', 'error');
            } finally {
                setLoading(false);
            }
        });
    }

    function setLoading(isLoading) {
        if (!submitBtn) return;
        const btnText = submitBtn.querySelector('.btn-text');
        const btnSpinner = submitBtn.querySelector('.btn-spinner');
        
        submitBtn.disabled = isLoading;
        if (isLoading) {
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-flex';
        } else {
            btnText.style.display = 'inline-flex';
            btnSpinner.style.display = 'none';
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
});
