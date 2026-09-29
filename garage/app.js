/**
 * AURA GARAGE · CLIENT APPLICATION SCRIPT (2026)
 * Conexión nativa con DonWeb PHP API sin dependencias de Supabase
 * Startup Aura · Titular: Omar Horacio Adamo
 */

const SESSION_KEY = 'aura_garage_session';

const state = {
    session: null,
    vehicles: [],
    applicants: [],
    currentTab: 'home', // 'home' | 'admin' | 'chofer' | 'postular'
    loading: false
};

// --- Inicialización ---
document.addEventListener('DOMContentLoaded', () => {
    initSession();
    setupEventListeners();
    loadFleetVehicles();
    
    // Auto-comprobar hash si existe (#postular, #login, #admin)
    handleHashNavigation();
});

// Manejo de Hash en URL para navegación directa
function handleHashNavigation() {
    const hash = window.location.hash.replace('#', '');
    if (hash === 'postular') {
        showView('postular');
    } else if (hash === 'login') {
        openLoginModal();
    } else if (hash === 'admin' && state.session) {
        showView('admin');
    }
}

window.addEventListener('hashchange', handleHashNavigation);

// --- Sesión & Auth ---
function initSession() {
    try {
        const stored = localStorage.getItem(SESSION_KEY);
        if (stored) {
            state.session = JSON.parse(stored);
            updateAuthUI();
        }
    } catch (e) {
        console.warn('Error al restaurar sesión:', e);
    }
}

function updateAuthUI() {
    const authActions = document.getElementById('header-auth-actions');
    const adminLink = document.getElementById('nav-admin-link');
    
    if (state.session && state.session.user) {
        const user = state.session.user;
        const role = user.role || 'driver';
        
        if (authActions) {
            authActions.innerHTML = `
                <div style="display:flex; align-items:center; gap: 0.8rem;">
                    <div style="text-align: right; display: flex; flex-direction: column;">
                        <span style="font-size: 0.85rem; font-weight: 700; color: #fff;">${user.full_name || user.email}</span>
                        <span style="font-size: 0.7rem; color: #00f2fe; text-transform: uppercase; font-weight: 800;">${role === 'admin' ? 'Titular / Admin' : 'Chofer Flota'}</span>
                    </div>
                    <button onclick="handleLogout()" class="btn-nav-outline" title="Cerrar Sesión" style="padding: 0.45rem 0.9rem;">
                        <i class="bx bx-log-out"></i> Salir
                    </button>
                </div>
            `;
        }

        if (adminLink) {
            adminLink.style.display = 'inline-flex';
            adminLink.onclick = () => showView(role === 'admin' ? 'admin' : 'chofer');
        }
    } else {
        if (authActions) {
            authActions.innerHTML = `
                <button onclick="showView('postular')" class="btn-nav-outline">
                    <i class="bx bx-id-card"></i> Postularme
                </button>
                <button onclick="openLoginModal()" class="btn-primary-aura">
                    <i class="bx bx-log-in"></i> Iniciar Sesión
                </button>
            `;
        }
        if (adminLink) {
            adminLink.style.display = 'none';
        }
    }
}

async function handleLoginSubmit(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value.trim();
    const errorEl = document.getElementById('login-error-msg');
    const btnSubmit = document.getElementById('btn-login-submit');

    if (!email || !password) {
        showLoginError('Por favor complete su correo y contraseña.');
        return;
    }

    try {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<i class="bx bx-loader-alt bx-spin"></i> Conectando...`;
        errorEl.style.display = 'none';

        const res = await fetch('./auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await res.json();

        if (!res.ok || data.error) {
            throw new Error(data.error || 'Credenciales inválidas');
        }

        state.session = data.session;
        localStorage.setItem(SESSION_KEY, JSON.stringify(data.session));
        closeLoginModal();
        updateAuthUI();
        showNotification('¡Bienvenido a Aura Garage!', 'success');

        if (data.user && data.user.role === 'admin') {
            showView('admin');
        } else {
            showView('chofer');
        }
    } catch (err) {
        showLoginError(err.message);
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = `<i class="bx bx-log-in"></i> Ingresar al Sistema`;
    }
}

function handleLogout() {
    state.session = null;
    localStorage.removeItem(SESSION_KEY);
    updateAuthUI();
    showView('home');
    showNotification('Sesión finalizada correctamente', 'info');
}

function showLoginError(msg) {
    const errorEl = document.getElementById('login-error-msg');
    if (errorEl) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
    }
}

function quickLoginAdmin() {
    document.getElementById('login-email').value = 'adamoomar110@gmail.com';
    document.getElementById('login-password').value = '123456';
    document.getElementById('btn-login-submit').click();
}

function quickLoginDriver() {
    document.getElementById('login-email').value = 'chofer@aura.com';
    document.getElementById('login-password').value = '123456';
    document.getElementById('btn-login-submit').click();
}

// --- Vistas & Navegación ---
function showView(viewId) {
    state.currentTab = viewId;
    
    const views = ['view-home', 'view-postular', 'view-admin', 'view-chofer'];
    views.forEach(v => {
        const el = document.getElementById(v);
        if (el) el.style.display = (v === `view-${viewId}`) ? 'block' : 'none';
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (viewId === 'admin') {
        if (!state.session) {
            openLoginModal();
            return;
        }
        loadAdminData();
    } else if (viewId === 'chofer') {
        if (!state.session) {
            openLoginModal();
            return;
        }
    }
}

// --- Modales ---
function openLoginModal() {
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.classList.add('active');
        const err = document.getElementById('login-error-msg');
        if (err) err.style.display = 'none';
    }
}

function closeLoginModal() {
    const modal = document.getElementById('login-modal');
    if (modal) modal.classList.remove('active');
}

// --- Carga de Flota (Pública y Admin) ---
async function loadFleetVehicles() {
    try {
        const res = await fetch('./vehicles.php');
        const data = await res.json();
        if (data.success && Array.isArray(data.vehicles)) {
            state.vehicles = data.vehicles;
            renderHomeVehicles(state.vehicles);
            renderAdminVehicles(state.vehicles);
            updateKPICards();
        }
    } catch (e) {
        console.warn('Error al cargar vehículos:', e);
    }
}

function renderHomeVehicles(vehicles) {
    const container = document.getElementById('home-vehicles-grid');
    if (!container) return;

    if (!vehicles || vehicles.length === 0) {
        container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 2rem;">No hay vehículos registrados en este momento.</p>`;
        return;
    }

    container.innerHTML = vehicles.map(v => {
        const metrics = v.metrics || {};
        const isMaintenance = v.status === 'maintenance';
        const badgeClass = isMaintenance ? 'badge-maintenance' : 'badge-active';
        const statusLabel = isMaintenance ? 'En Taller' : 'En Pista (Activo)';

        return `
            <div class="portal-action-card" style="padding: 1.8rem;">
                <div class="card-icon-header">
                    <span class="plate-badge">${v.plate}</span>
                    <span class="badge-status ${badgeClass}">
                        <i class="bx ${isMaintenance ? 'bx-wrench' : 'bx-check-circle'}"></i> ${statusLabel}
                    </span>
                </div>
                <h4 style="font-family: var(--font-heading); font-size: 1.3rem; margin-bottom: 0.4rem; color: #fff;">
                    ${v.brand} ${v.model}
                </h4>
                <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.35rem;">
                    <div><i class="bx bx-tachometer" style="color:#00f2fe;"></i> <strong>${(metrics.km || 0).toLocaleString('es-AR')} KM</strong> recorridos</div>
                    <div><i class="bx bx-gas-pump" style="color:#6366f1;"></i> Combustible: <strong>${metrics.gnc ? 'GNC 5ta Gen + Nafta' : 'Nafta Premium'}</strong></div>
                    <div><i class="bx bx-user" style="color:#10b981;"></i> Chofer: <strong>${metrics.driver_name || 'Sin Asignar'}</strong></div>
                </div>
                <div style="display: flex; gap: 0.6rem; margin-top: auto;">
                    <button onclick="openInspectModal('${v.id}')" class="btn-nav-outline" style="flex:1; justify-content:center;">
                        <i class="bx bx-info-circle"></i> Ficha Técnica
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function updateKPICards() {
    const totalVeh = state.vehicles.length;
    const activeVeh = state.vehicles.filter(v => v.status === 'active').length;
    const maintVeh = state.vehicles.filter(v => v.status === 'maintenance').length;

    const elTotal = document.getElementById('kpi-total-vehicles');
    const elActive = document.getElementById('kpi-active-vehicles');
    const elMaint = document.getElementById('kpi-maint-vehicles');

    if (elTotal) elTotal.textContent = totalVeh;
    if (elActive) elActive.textContent = activeVeh;
    if (elMaint) elMaint.textContent = maintVeh;
}

// --- Carga de Datos Admin ---
async function loadAdminData() {
    await loadFleetVehicles();
    await loadApplicants();
}

async function loadApplicants() {
    try {
        const res = await fetch('./applicants.php');
        const data = await res.json();
        if (data.success && Array.isArray(data.applicants)) {
            state.applicants = data.applicants;
            renderAdminApplicants(state.applicants);
            const kpiApp = document.getElementById('kpi-applicants-count');
            if (kpiApp) kpiApp.textContent = state.applicants.length;
        }
    } catch (e) {
        console.warn('Error al cargar postulantes:', e);
    }
}

function renderAdminVehicles(vehicles) {
    const tbody = document.getElementById('admin-vehicles-tbody');
    if (!tbody) return;

    if (!vehicles || vehicles.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 2rem;">No hay vehículos registrados</td></tr>`;
        return;
    }

    tbody.innerHTML = vehicles.map(v => {
        const m = v.metrics || {};
        const isMaint = v.status === 'maintenance';
        return `
            <tr>
                <td><span class="plate-badge">${v.plate}</span></td>
                <td style="font-weight:700; color:#fff;">${v.brand} ${v.model}</td>
                <td>${m.driver_name || '<span style="color:#64748b">Disponible</span>'}</td>
                <td>${(m.km || 0).toLocaleString('es-AR')} km</td>
                <td>
                    <span class="badge-status ${isMaint ? 'badge-maintenance' : 'badge-active'}">
                        ${isMaint ? 'En Taller' : 'Operativo'}
                    </span>
                </td>
                <td>
                    <button onclick="toggleVehicleStatus('${v.id}', '${v.status}')" class="btn-nav-outline" style="padding: 0.35rem 0.8rem; font-size: 0.78rem;">
                        ${isMaint ? 'Habilitar' : 'Enviar a Taller'}
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderAdminApplicants(applicants) {
    const tbody = document.getElementById('admin-applicants-tbody');
    if (!tbody) return;

    if (!applicants || applicants.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 2rem;">No hay postulantes pendientes</td></tr>`;
        return;
    }

    tbody.innerHTML = applicants.map(a => {
        return `
            <tr>
                <td style="font-weight:700; color:#fff;">${a.full_name}</td>
                <td>${a.dni}</td>
                <td><a href="https://wa.me/${(a.phone || '').replace(/[^0-9]/g, '')}" target="_blank" style="color:#00f2fe; text-decoration:none;"><i class="bx bxl-whatsapp"></i> ${a.phone}</a></td>
                <td>${a.zone || '-'}</td>
                <td style="max-width: 250px; font-size: 0.82rem;">${a.app_experience || 'Sin detalle'}</td>
                <td>
                    <span class="badge-status badge-pending">Pendiente</span>
                </td>
            </tr>
        `;
    }).join('');
}

async function toggleVehicleStatus(id, currentStatus) {
    const newStatus = (currentStatus === 'active') ? 'maintenance' : 'active';
    try {
        const res = await fetch('./vehicles.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            showNotification(`Vehículo actualizado a ${newStatus === 'active' ? 'Operativo' : 'En Taller'}`, 'success');
            await loadFleetVehicles();
        }
    } catch (e) {
        showNotification('Error al cambiar estado del vehículo', 'error');
    }
}

// --- Envío de Formulario de Postulación ---
async function handlePostulationSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-postulacion');
    const form = document.getElementById('form-postulacion');
    
    const payload = {
        full_name: document.getElementById('post-name').value.trim(),
        dni: document.getElementById('post-dni').value.trim(),
        phone: document.getElementById('post-phone').value.trim(),
        zone: document.getElementById('post-zone').value.trim(),
        app_experience: document.getElementById('post-experience').value.trim()
    };

    if (!payload.full_name || !payload.dni || !payload.phone) {
        showNotification('Por favor complete los campos obligatorios.', 'error');
        return;
    }

    try {
        btn.disabled = true;
        btn.innerHTML = `<i class="bx bx-loader-alt bx-spin"></i> Registrando solicitud...`;

        const res = await fetch('./applicants.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok || data.error) {
            throw new Error(data.error || 'Error al enviar solicitud.');
        }

        form.reset();
        showNotification('¡Postulación enviada con éxito! El equipo de Aura se comunicará a la brevedad.', 'success');
        setTimeout(() => showView('home'), 2200);
    } catch (err) {
        showNotification(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bx bx-send"></i> Enviar Postulación Oficial`;
    }
}

// --- Ficha Técnica Modal ---
function openInspectModal(vehicleId) {
    const v = state.vehicles.find(item => item.id === vehicleId);
    if (!v) return;

    const modal = document.getElementById('inspect-modal');
    const content = document.getElementById('inspect-modal-content');
    if (!modal || !content) return;

    const m = v.metrics || {};
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <span class="plate-badge" style="font-size: 1.2rem; padding: 0.4rem 1rem;">${v.plate}</span>
            <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: #fff; margin-top: 0.8rem;">
                ${v.brand} ${v.model}
            </h3>
            <span class="badge-status ${v.status === 'active' ? 'badge-active' : 'badge-maintenance'}" style="margin-top: 0.4rem;">
                ${v.status === 'active' ? 'Unidad Operativa en Pista' : 'En Mantenimiento Preventivo'}
            </span>
        </div>

        <div style="background: rgba(255,255,255,0.03); border: var(--border-glass); border-radius: var(--radius-md); padding: 1.2rem; display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.9rem;">
            <div style="display:flex; justify-content:space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                <span style="color:#94a3b8;">Kilometraje actual:</span>
                <strong style="color:#fff;">${(m.km || 0).toLocaleString('es-AR')} km</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                <span style="color:#94a3b8;">Sistema de combustible:</span>
                <strong style="color:#00f2fe;">${m.gnc ? 'GNC 5ta Generación Certificado' : 'Nafta 98 Octanos'}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                <span style="color:#94a3b8;">Chofer asignado:</span>
                <strong style="color:#fff;">${m.driver_name || 'Sin Chofer'}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.4rem;">
                <span style="color:#94a3b8;">Póliza de Seguro Vence:</span>
                <strong style="color:#10b981;">${m.insurance_due || 'Al día'}</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:#94a3b8;">VTV / RTO Obligatoria:</span>
                <strong style="color:#10b981;">Vigente y aprobada</strong>
            </div>
        </div>

        <button onclick="closeInspectModal()" class="btn-primary-aura" style="width:100%; margin-top: 1.5rem; justify-content:center;">
            Cerrar Ficha
        </button>
    `;

    modal.classList.add('active');
}

function closeInspectModal() {
    const modal = document.getElementById('inspect-modal');
    if (modal) modal.classList.remove('active');
}

// --- Notificaciones Toast ---
function showNotification(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'aura-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #0f172a;
        color: #ffffff;
        border: 1px solid ${type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#00f2fe')};
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
        padding: 1rem 1.4rem;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        gap: 0.8rem;
        z-index: 9999;
        font-size: 0.92rem;
        font-weight: 600;
        animation: fadeInToast 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'bx-check-circle' : (type === 'error' ? 'bx-error-circle' : 'bx-info-circle');
    const color = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#00f2fe');

    toast.innerHTML = `<i class="bx ${icon}" style="font-size: 1.4rem; color: ${color};"></i> <span>${msg}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// --- Listeners de Formularios ---
function setupEventListeners() {
    const loginForm = document.getElementById('login-form');
    if (loginForm) loginForm.addEventListener('submit', handleLoginSubmit);

    const postForm = document.getElementById('form-postulacion');
    if (postForm) postForm.addEventListener('submit', handlePostulationSubmit);
}
