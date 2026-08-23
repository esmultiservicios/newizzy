// Pantalla de Cocina - IZZY
// Mantiene la vista sincronizada sin parpadeos ni recargas completas.

if (typeof SERVERURL === 'undefined') {
    console.error('SERVERURL no está definido. Asegúrate de definirlo antes de cocina.js.');
    var SERVERURL = window.location.origin + '/';
}

document.addEventListener('DOMContentLoaded', function () {
    const navbarTop = document.querySelector('.sb-topnav');
    const navbarLateral = document.querySelector('.sb-sidenav');
    const container = document.getElementById('comandas-container');
    const refreshBtn = document.getElementById('btn-refresh');

    if (navbarTop) navbarTop.style.display = 'none';
    if (navbarLateral) navbarLateral.style.display = 'none';
    document.body.classList.add('vista-cocina-active');

    window.addEventListener('beforeunload', function () {
        if (navbarTop) navbarTop.style.display = '';
        if (navbarLateral) navbarLateral.style.display = '';
        document.body.classList.remove('vista-cocina-active');
    });

    function notificar(tipo, titulo, mensaje) {
        if (typeof showNotify === 'function') {
            showNotify(tipo, titulo, mensaje);
        }
    }

    function escapeHtml(valor) {
        return String(valor == null ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizarCantidad(valor) {
        const n = Number(valor);
        if (!Number.isFinite(n)) return '1';
        return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    }

    function actualizarHora() {
        const ahora = new Date();
        const hora = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');
        const el = document.getElementById('hora-actual');
        if (el) el.textContent = `${hora}:${minutos}:${segundos}`;
    }

    actualizarHora();
    window.setInterval(actualizarHora, 1000);

    let cargaComandasEnCurso = false;
    let ultimaFirmaComandas = null;
    let controladorCarga = null;
    let flujoCocina = 'pasos';

    function firmaComandas(comandas) {
        const lista = Array.isArray(comandas) ? comandas : [];
        return JSON.stringify({ flujo: flujoCocina, comandas: lista.map(comanda => ({
            comanda_id: comanda.comanda_id || comanda.id || '',
            factura_id: comanda.factura_id || '',
            mesa: comanda.mesa || '',
            servicio_tipo: comanda.servicio_tipo || '',
            cliente_nombre: comanda.cliente_nombre || '',
            estado: comanda.estado || '',
            urgente: !!comanda.urgente,
            hora: comanda.hora || '',
            observaciones: comanda.observaciones || '',
            comentarios_cocina: comanda.comentarios_cocina || '',
            items: (Array.isArray(comanda.items) ? comanda.items : []).map(item => ({
                id: item.productos_id || item.id || '',
                nombre: item.nombre || '',
                cantidad: item.cantidad || 0
            }))
        })) });
    }

    async function cargarComandas(opciones = {}) {
        const { forzar = false, silencioso = true } = opciones;

        if (cargaComandasEnCurso) return;
        cargaComandasEnCurso = true;

        if (refreshBtn) refreshBtn.classList.add('is-loading');

        try {
            controladorCarga = new AbortController();
            const timeoutId = window.setTimeout(() => controladorCarga.abort(), 15000);

            const response = await fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: 'action=loadComandasCocina',
                signal: controladorCarga.signal,
                cache: 'no-store'
            });

            window.clearTimeout(timeoutId);

            let data;
            try {
                data = await response.json();
            } catch (e) {
                throw new Error('El servidor devolvió una respuesta inválida.');
            }

            if (!response.ok) {
                throw new Error(data && data.message ? data.message : 'Error al cargar las comandas.');
            }

            if (!data || !data.status) {
                throw new Error(data && data.message ? data.message : 'No fue posible cargar las comandas.');
            }

            const comandas = Array.isArray(data.comandas) ? data.comandas : [];
            const flujoRecibido = String((data.config && data.config.flujo_cocina) || 'pasos').toLowerCase();
            flujoCocina = flujoRecibido === 'directo' ? 'directo' : 'pasos';
            const nuevaFirma = firmaComandas(comandas);

            // Evita el efecto de quitar y volver a poner las tarjetas cada 5 segundos.
            if (forzar || nuevaFirma !== ultimaFirmaComandas) {
                renderizarComandas(comandas);
                ultimaFirmaComandas = nuevaFirma;
            }
        } catch (error) {
            if (error && error.name === 'AbortError') {
                if (!silencioso) notificar('warning', 'Tiempo agotado', 'La actualización de Cocina tardó demasiado.');
            } else {
                console.error('Error al cargar comandas:', error);
                if (!silencioso) notificar('error', 'Error', error.message || 'Error al conectar con el servidor.');
            }
        } finally {
            cargaComandasEnCurso = false;
            controladorCarga = null;
            if (refreshBtn) refreshBtn.classList.remove('is-loading');
        }
    }

    function renderizarComandas(comandas) {
        if (!container) return;

        if (!Array.isArray(comandas) || comandas.length === 0) {
            container.innerHTML = `
                <div class="no-comandas" role="status">
                    <div class="no-comandas-icon"><i class="fas fa-clipboard-check"></i></div>
                    <strong>Sin comandas pendientes</strong>
                    <p>Las nuevas órdenes de Cocina aparecerán aquí automáticamente.</p>
                </div>
            `;
            return;
        }

        const fragment = document.createDocumentFragment();

        comandas.forEach((comanda, index) => {
            const comandaId = comanda.comanda_id || comanda.id || '';
            const facturaId = comanda.factura_id || '';
            const estado = String(comanda.estado || 'pendiente').toLowerCase();
            const urgente = !!comanda.urgente || estado === 'urgente';
            const items = Array.isArray(comanda.items) ? comanda.items : [];

            const card = document.createElement('article');
            card.className = `comanda-card${urgente ? ' comanda-urgente' : ''}`;
            card.dataset.estado = estado;
            card.dataset.comandaId = comandaId;
            card.style.setProperty('--card-delay', `${Math.min(index * 35, 175)}ms`);

            const servicio = String(comanda.servicio_tipo || '').toLowerCase();
            const mesaTexto = comanda.mesa
                ? `Mesa ${escapeHtml(comanda.mesa)}`
                : (servicio === 'llevar' ? 'Para llevar' : 'Sin mesa asignada');

            const itemsHTML = items.length
                ? items.map(item => `
                    <div class="comanda-item">
                        <span class="item-nombre">${escapeHtml(item.nombre || 'Producto sin nombre')}</span>
                        <span class="item-cantidad" aria-label="Cantidad ${escapeHtml(normalizarCantidad(item.cantidad))}">${escapeHtml(normalizarCantidad(item.cantidad))}</span>
                    </div>
                `).join('')
                : '<div class="comanda-item comanda-item-vacio">Sin productos de Cocina.</div>';

            const estadoLabel = estado.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const observaciones = comanda.observaciones ? `<p><strong>Notas:</strong> ${escapeHtml(comanda.observaciones)}</p>` : '';
            const comentarios = comanda.comentarios_cocina ? `<p><strong>Cocina:</strong> ${escapeHtml(comanda.comentarios_cocina)}</p>` : '';

            card.innerHTML = `
                <header class="comanda-header">
                    <div class="comanda-header-main">
                        <span class="comanda-mesa"><i class="fas fa-${comanda.mesa ? 'chair' : 'shopping-bag'}"></i>${mesaTexto}</span>
                        <div class="comanda-badges">
                            ${urgente ? '<span class="badge-urgente">Urgente</span>' : ''}
                            ${estado !== 'urgente' ? `<span class="badge-estado ${escapeHtml(estado)}">${escapeHtml(estadoLabel)}</span>` : ''}
                        </div>
                    </div>
                    <span class="comanda-hora"><i class="far fa-clock"></i>${escapeHtml(comanda.hora || '')}</span>
                </header>

                <div class="comanda-info">
                    <div class="comanda-cliente"><i class="far fa-user"></i><span><strong>Cliente</strong>${escapeHtml(comanda.cliente_nombre || 'Consumidor Final')}</span></div>
                    <div class="comanda-id"><i class="fas fa-receipt"></i><span><strong>Orden</strong>#${escapeHtml(facturaId || comandaId || '—')}</span></div>
                </div>

                <div class="comanda-items">${itemsHTML}</div>

                ${(observaciones || comentarios) ? `<div class="comanda-observaciones">${observaciones}${comentarios}</div>` : ''}

                <footer class="comanda-actions">
                    ${(flujoCocina === 'pasos' && (estado === 'pendiente' || estado === 'urgente')) ? `
                        <button type="button" class="btn btn-primary btn-preparacion"
                                data-factura-id="${escapeHtml(facturaId)}"
                                data-comanda-id="${escapeHtml(comandaId)}">
                            <i class="fas fa-fire-burner"></i><span>En preparación</span>
                        </button>
                    ` : ''}

                    ${((flujoCocina === 'pasos' && estado === 'en_preparacion') || (flujoCocina === 'directo' && ['pendiente','urgente','en_preparacion'].includes(estado))) ? `
                        <button type="button" class="btn btn-success btn-completar"
                                data-comanda-id="${escapeHtml(comandaId)}">
                            <i class="fas fa-check-circle"></i><span>Finalizar</span>
                        </button>
                    ` : ''}

                    <button type="button" class="btn btn-warning btn-urgente"
                            data-factura-id="${escapeHtml(facturaId)}"
                            data-urgente="${urgente ? 'true' : 'false'}">
                        <i class="fas fa-exclamation-triangle"></i><span>${urgente ? 'Quitar urgente' : 'Marcar urgente'}</span>
                    </button>
                </footer>
            `;

            fragment.appendChild(card);
        });

        container.replaceChildren(fragment);
    }

    async function ejecutarAccion(boton, body, mensajes) {
        if (!boton || boton.disabled) return;

        const htmlOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.classList.add('is-processing');
        boton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Procesando…</span>';

        try {
            const response = await fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body,
                cache: 'no-store'
            });

            const data = await response.json();
            if (!response.ok || !data.status) {
                throw new Error((data && data.message) || mensajes.error);
            }

            notificar('success', 'Éxito', mensajes.exito);
            // Forzamos una única actualización después de una acción del usuario.
            await cargarComandas({ forzar: true, silencioso: true });
        } catch (error) {
            console.error(mensajes.error, error);
            notificar('error', 'Error', error.message || mensajes.error);
        } finally {
            boton.disabled = false;
            boton.classList.remove('is-processing');
            boton.innerHTML = htmlOriginal;
        }
    }

    if (container) {
        // Delegación: no recreamos listeners en cada refresco.
        container.addEventListener('click', function (event) {
            const btnPreparacion = event.target.closest('.btn-preparacion');
            if (btnPreparacion) {
                const facturaId = btnPreparacion.dataset.facturaId;
                if (!facturaId) return;
                ejecutarAccion(
                    btnPreparacion,
                    `action=marcarComandaEnPreparacion&factura_id=${encodeURIComponent(facturaId)}`,
                    { exito: 'Comanda marcada como en preparación', error: 'No se pudo cambiar el estado de la comanda.' }
                );
                return;
            }

            const btnCompletar = event.target.closest('.btn-completar');
            if (btnCompletar) {
                const comandaId = btnCompletar.dataset.comandaId;
                if (!comandaId) return;
                ejecutarAccion(
                    btnCompletar,
                    `action=marcarComandaPreparada&comanda_id=${encodeURIComponent(comandaId)}`,
                    { exito: 'Comanda marcada como completada', error: 'No se pudo completar la comanda.' }
                );
                return;
            }

            const btnUrgente = event.target.closest('.btn-urgente');
            if (btnUrgente) {
                const facturaId = btnUrgente.dataset.facturaId;
                const esUrgente = btnUrgente.dataset.urgente === 'true';
                if (!facturaId) return;
                ejecutarAccion(
                    btnUrgente,
                    `action=marcarComandaUrgente&factura_id=${encodeURIComponent(facturaId)}&urgente=${esUrgente ? 0 : 1}`,
                    {
                        exito: esUrgente ? 'Comanda marcada como normal' : 'Comanda marcada como urgente',
                        error: 'No se pudo cambiar la prioridad de la comanda.'
                    }
                );
            }
        });
    }

    if (refreshBtn) {
        refreshBtn.setAttribute('role', 'button');
        refreshBtn.setAttribute('tabindex', '0');
        refreshBtn.setAttribute('aria-label', 'Actualizar comandas');

        const refrescarManual = function () {
            cargarComandas({ forzar: true, silencioso: false });
        };

        refreshBtn.addEventListener('click', refrescarManual);
        refreshBtn.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                refrescarManual();
            }
        });
    }

    // Primera carga y actualización silenciosa. Si no hay cambios, el DOM NO se toca.
    cargarComandas({ forzar: true, silencioso: false });
    window.setInterval(() => cargarComandas({ silencioso: true }), 5000);
});
