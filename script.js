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

    // --- 6. Lógica Interactiva del Simulador Sandbox (5 Apps Aura) ---
    initAuraSandbox();

    function initAuraSandbox() {
        // A. Control de Pestañas (Tabs)
        const tabs = document.querySelectorAll('.sandbox-tab');
        const panels = document.querySelectorAll('.sandbox-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetId = `tab-${tab.dataset.tab}`;

                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                panels.forEach(p => {
                    p.classList.remove('active');
                    p.style.display = 'none';
                });

                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');

                const targetPanel = document.getElementById(targetId);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                    targetPanel.style.display = 'block';
                }
            });
        });

        // B. Simulación RolPlay.ai
        const tensionFill = document.getElementById('sim-tension-fill');
        const tensionVal = document.getElementById('sim-tension-val');
        const userContainer = document.getElementById('sim-user-container');
        const userDialogue = document.getElementById('sim-user-dialogue');
        const feedbackBox = document.getElementById('sim-ai-feedback');
        const feedbackText = document.getElementById('sim-feedback-text');
        const simOptions = document.querySelectorAll('.btn-sim-option');

        simOptions.forEach(btn => {
            btn.addEventListener('click', () => {
                const respType = btn.dataset.resp;

                if (respType === '1') {
                    // Táctica de Valor
                    if (userDialogue) userDialogue.innerText = "Nuestro precio incluye soporte técnico 24/7 y telemetría en la nube que reduce 40% las roturas imprevistas de tu flota.";
                    if (userContainer) userContainer.style.display = 'flex';
                    if (tensionFill) {
                        tensionFill.style.width = '25%';
                        tensionFill.style.background = '#34d399';
                    }
                    if (tensionVal) {
                        tensionVal.innerText = '25% (Controlada)';
                        tensionVal.style.color = '#34d399';
                    }
                    if (feedbackText) {
                        feedbackText.innerText = "✅ ¡Excelente manejo de objeción! Desviaste el foco del costo hacia el Retorno de Inversión (ROI) y prevención de fallas. El cliente se muestra receptivo.";
                    }
                    if (feedbackBox) feedbackBox.style.display = 'block';
                } else if (respType === '2') {
                    // Táctica Comparativa
                    if (userDialogue) userDialogue.innerText = "Otras opciones aparentan ser más baratas pero cobran costos ocultos de mantenimiento mensual; nuestra plataforma se amortiza en los primeros 60 días.";
                    if (userContainer) userContainer.style.display = 'flex';
                    if (tensionFill) {
                        tensionFill.style.width = '35%';
                        tensionFill.style.background = '#facc15';
                    }
                    if (tensionVal) {
                        tensionVal.innerText = '35% (Favorable)';
                        tensionVal.style.color = '#facc15';
                    }
                    if (feedbackText) {
                        feedbackText.innerText = "💡 Muy buen argumento de Costo Total de Propiedad (TCO). Justificaste la diferencia tarifaria con datos concretos.";
                    }
                    if (feedbackBox) feedbackBox.style.display = 'block';
                } else if (respType === '3') {
                    // Reiniciar
                    if (userContainer) userContainer.style.display = 'none';
                    if (feedbackBox) feedbackBox.style.display = 'none';
                    if (tensionFill) {
                        tensionFill.style.width = '65%';
                        tensionFill.style.background = 'linear-gradient(90deg, #34d399 0%, #facc15 50%, #f87171 100%)';
                    }
                    if (tensionVal) {
                        tensionVal.innerText = '65% (Media)';
                        tensionVal.style.color = '#facc15';
                    }
                }
            });
        });

        // C. Simulación Spinaz Garage (Checklist)
        const spinazCheckboxes = document.querySelectorAll('.spinaz-chk');
        const spinazScoreText = document.getElementById('spinaz-score-text');
        const spinazScoreBadge = document.getElementById('spinaz-score-badge');

        function updateSpinazScore() {
            let total = 0;
            spinazCheckboxes.forEach(chk => {
                const card = chk.closest('.checklist-card');
                const statusSpan = card ? card.querySelector('.chk-status') : null;

                if (chk.checked) {
                    total += Number(chk.dataset.weight || 25);
                    if (card) card.classList.add('checked');
                    if (statusSpan) {
                        statusSpan.innerText = 'OK';
                        statusSpan.className = 'chk-status ok';
                    }
                } else {
                    if (card) card.classList.remove('checked');
                    if (statusSpan) {
                        statusSpan.innerText = 'REVISAR';
                        statusSpan.className = 'chk-status fail';
                    }
                }
            });

            if (spinazScoreText) spinazScoreText.innerText = `${total}%`;

            if (spinazScoreBadge) {
                if (total === 100) {
                    spinazScoreBadge.className = 'score-badge badge-green';
                    spinazScoreBadge.innerText = 'APTO PARA RUTA';
                } else if (total >= 50) {
                    spinazScoreBadge.className = 'score-badge badge-amber';
                    spinazScoreBadge.innerText = 'PRECAUCIÓN EN TALLER';
                } else {
                    spinazScoreBadge.className = 'score-badge badge-red';
                    spinazScoreBadge.innerText = 'VEHÍCULO INMOVILIZADO';
                }
            }
        }

        spinazCheckboxes.forEach(chk => {
            chk.addEventListener('change', updateSpinazScore);
        });

        // D. Simulación L1deres AutoWash (LPR / Telemetría)
        const btnScanPlate = document.getElementById('btn-sim-scan-plate');
        const inputPlate = document.getElementById('sim-plate-input');
        const bayPlate = document.getElementById('sim-bay-plate');
        const bayStatus = document.getElementById('sim-bay-status');
        const bayTimer = document.getElementById('sim-bay-timer');
        const bayPlan = document.getElementById('sim-bay-plan');

        if (btnScanPlate && inputPlate) {
            btnScanPlate.addEventListener('click', () => {
                const val = inputPlate.value.trim().toUpperCase() || 'AA 000 BB';
                inputPlate.value = val;

                btnScanPlate.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Identificando...";

                setTimeout(() => {
                    btnScanPlate.innerHTML = "<i class='bx bx-check'></i> ¡Vehículo Asignado!";
                    if (bayPlate) bayPlate.innerHTML = `🚗 Patente: <strong>${val}</strong>`;
                    if (bayStatus) bayStatus.innerText = 'EN LAVADO VIP';
                    if (bayTimer) bayTimer.innerHTML = "⏱️ Tiempo Restante: <strong>04:00 min</strong>";
                    if (bayPlan) bayPlan.innerHTML = "💎 Socio: <strong>CLUB 100 GOLD</strong>";

                    setTimeout(() => {
                        btnScanPlate.innerHTML = "<i class='bx bx-scan'></i> Simular Lectura LPR";
                    }, 2000);
                }, 800);
            });
        }

        // E. Simulación Odonto Merlo (Turnero)
        const odontoService = document.getElementById('sim-odonto-service');
        const odontoChips = document.querySelectorAll('.odonto-chip');
        const ticketService = document.getElementById('ticket-service');
        const ticketTime = document.getElementById('ticket-time');

        function updateOdontoTicket() {
            const activeChip = document.querySelector('.odonto-chip.active');
            const hour = activeChip ? activeChip.dataset.hour : '14:30';
            const srv = odontoService ? odontoService.value : 'Limpieza & Profilaxis';

            if (ticketService) ticketService.innerText = `Tratamiento: ${srv}`;
            if (ticketTime) ticketTime.innerText = `Horario: Hoy a las ${hour} hs · Consultorio 2`;
        }

        if (odontoService) {
            odontoService.addEventListener('change', updateOdontoTicket);
        }

        odontoChips.forEach(chip => {
            chip.addEventListener('click', () => {
                odontoChips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                updateOdontoTicket();
            });
        });

        // F. Simulación Chofer Online (Filtro)
        const choferFilters = document.querySelectorAll('.chofer-filter-btn');
        const choferCards = document.querySelectorAll('.chofer-item-card');

        choferFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                const cat = btn.dataset.cat;
                choferFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                choferCards.forEach(card => {
                    if (cat === 'todos' || card.dataset.cat === cat) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }

});

