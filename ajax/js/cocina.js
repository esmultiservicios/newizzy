// Definir SERVERURL si no está definido
if (typeof SERVERURL === 'undefined') {
    console.error('SERVERURL no está definido. Asegúrate de definirlo en el HTML.');
    var SERVERURL = window.location.origin + '/';
}

document.addEventListener('DOMContentLoaded', function() {
    // Ocultar navbar-top y navbar-lateral
    const navbarTop = document.querySelector(".sb-topnav");
    const navbarLateral = document.querySelector(".sb-sidenav");
    
    if (navbarTop) navbarTop.style.display = "none";
    if (navbarLateral) navbarLateral.style.display = "none";
    
    // Agregar clase al body
    document.body.classList.add('vista-cocina-active');
    
    // Restaurar al salir
    window.addEventListener("beforeunload", function() {
        if (navbarTop) navbarTop.style.display = "";
        if (navbarLateral) navbarLateral.style.display = "";
        document.body.classList.remove('vista-cocina-active');
    });

    // Actualizar la hora actual
    function actualizarHora() {
        const ahora = new Date();
        const hora = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');
        var el = document.getElementById('hora-actual');
        if (el) el.textContent = `${hora}:${minutos}:${segundos}`;
    }
    
    setInterval(actualizarHora, 1000);
    actualizarHora();

    // Cargar comandas pendientes (SOLO COCINA)
    function cargarComandas() {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=loadComandasCocina'
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errData => {
                    showNotify && showNotify('error', 'Error', 'Error al cargar las comandas');
                    throw new Error(errData.message || 'Error en el servidor');
                }).catch(() => {
                    showNotify && showNotify('error', 'Error', 'Error al conectar con el servidor');
                    throw new Error('Error al procesar la respuesta');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status) {
                renderizarComandas(data.comandas);
            } else {
                showNotify && showNotify('error', 'Error', data.message || 'Error desconocido al cargar comandas');
            }
        })
        .catch(error => {
            console.error('Error al cargar comandas:', error);
            showNotify && showNotify('error', 'Error', 'Error al conectar con el servidor');
        });
    }

    function renderizarComandas(comandas) {
        const container = document.getElementById('comandas-container');
        
        if (!container) return;

        if (!comandas || comandas.length === 0) {
            container.innerHTML = `
                <div class="no-comandas">
                    <i class="fas fa-info-circle"></i>
                    <p>No hay comandas pendientes</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = '';
        
        comandas.forEach(comanda => {
            const comandaElement = document.createElement('div');
            comandaElement.className = `comanda-card ${comanda.urgente ? 'comanda-urgente' : ''} fade-in`;
            comandaElement.dataset.estado = comanda.estado;
            
            const items = Array.isArray(comanda.items) ? comanda.items : [];
            
            const itemsHTML = items.map(item => `
                <div class="comanda-item">
                    <span class="item-nombre">${item.nombre || 'Producto sin nombre'}</span>
                    <span class="item-cantidad">${item.cantidad || 1}</span>
                </div>
            `).join('');
            
            const mesaInfo = comanda.mesa ? `Mesa ${comanda.mesa}` : 'Sin mesa asignada';
            const ordenNum = comanda.factura_id || comanda.id || '';

            comandaElement.innerHTML = `
                <div class="comanda-header">
                    <div class="comanda-mesa">
                        ${mesaInfo}
                        ${comanda.urgente ? '<span class="badge-urgente">URGENTE</span>' : ''}
                        <span class="badge-estado ${comanda.estado}">${(comanda.estado || '').toUpperCase()}</span>
                    </div>
                    <div class="comanda-hora">${comanda.hora || ''}</div>
                </div>
                
                <div class="comanda-info">
                    <div class="comanda-cliente">
                        <strong>Cliente:</strong> ${comanda.cliente_nombre || 'Consumidor Final'}
                    </div>
                    <div class="comanda-id">
                        <strong>Orden #:</strong> ${ordenNum}
                    </div>
                </div>
                
                <div class="comanda-items">
                    ${itemsHTML}
                </div>
                
                ${(comanda.observaciones || comanda.comentarios_cocina) ? `
                <div class="comanda-observaciones">
                    ${comanda.observaciones ? `<p><strong>Notas:</strong> ${comanda.observaciones}</p>` : ''}
                    ${comanda.comentarios_cocina ? `<p><strong>Cocina:</strong> ${comanda.comentarios_cocina}</p>` : ''}
                </div>
                ` : ''}
                
                <div class="comanda-actions">
                    ${(comanda.estado === 'pendiente' || comanda.estado === 'urgente') ? `
                    <button class="btn btn-primary btn-preparacion" 
                            data-factura-id="${comanda.factura_id || ''}" 
                            data-comanda-id="${comanda.id || ''}">
                        <i class="fas fa-utensils"></i> En Preparación
                    </button>
                    ` : ''}
                    
                    ${comanda.estado === 'en_preparacion' ? `
                    <button class="btn btn-success btn-completar" 
                            data-comanda-id="${comanda.id || ''}">
                        <i class="fas fa-check"></i> Completado
                    </button>
                    ` : ''}
                    
                    <button class="btn btn-warning btn-urgente" 
                            data-factura-id="${comanda.factura_id || ''}" 
                            data-urgente="${comanda.urgente ? 'true' : 'false'}">
                        <i class="fas fa-exclamation"></i> ${comanda.urgente ? 'Quitar Urgente' : 'Marcar como Urgente'}
                    </button>
                </div>
            `;
            
            container.appendChild(comandaElement);
        });

        // Agregar event listeners a los botones
        document.querySelectorAll('.btn-preparacion').forEach(btn => {
            btn.addEventListener('click', function() {
                const facturaId = this.getAttribute('data-factura-id');
                if (!facturaId) return;
                marcarComandaEnPreparacion(facturaId);
            });
        });

        document.querySelectorAll('.btn-completar').forEach(btn => {
            btn.addEventListener('click', function() {
                const comandaId = this.getAttribute('data-comanda-id');
                if (!comandaId) return;
                marcarComandaCompleta(comandaId);
            });
        });

        document.querySelectorAll('.btn-urgente').forEach(btn => {
            btn.addEventListener('click', function() {
                const esUrgente = this.getAttribute('data-urgente') === 'true';
                const facturaId = this.getAttribute('data-factura-id');
                if (!facturaId) return;
                marcarComandaUrgente(facturaId, !esUrgente);
            });
        });
    }

    function marcarComandaEnPreparacion(facturaId) {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=marcarComandaEnPreparacion&factura_id=${encodeURIComponent(facturaId)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                cargarComandas();
                showNotify && showNotify('success', 'Éxito', 'Comanda marcada como en preparación');
            } else {
                showNotify && showNotify('error', 'Error', data.message || 'Error al cambiar estado de comanda');
            }
        })
        .catch(err => {
            console.error('Error al cambiar estado de comanda:', err);
            showNotify && showNotify('error', 'Error', 'Error al conectar con el servidor');
        });
    }

    function marcarComandaCompleta(comandaId) {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=marcarComandaPreparada&comanda_id=${encodeURIComponent(comandaId)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                cargarComandas();
                showNotify && showNotify('success', 'Éxito', 'Comanda marcada como completada');
            } else {
                showNotify && showNotify('error', 'Error', data.message || 'Error al completar comanda');
            }
        })
        .catch(err => {
            console.error('Error al completar comanda:', err);
            showNotify && showNotify('error', 'Error', 'Error al conectar con el servidor');
        });
    }

    function marcarComandaUrgente(facturaId, urgente) {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=marcarComandaUrgente&factura_id=${encodeURIComponent(facturaId)}&urgente=${urgente ? 1 : 0}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.status) {
                cargarComandas();
                showNotify && showNotify('success', 'Éxito', urgente ? 'Comanda marcada como urgente' : 'Comanda marcada como normal');
            } else {
                showNotify && showNotify('error', 'Error', data.message || 'Error al cambiar estado de urgencia');
            }
        })
        .catch(err => {
            console.error('Error al cambiar estado de urgencia:', err);
            showNotify && showNotify('error', 'Error', 'Error al conectar con el servidor');
        });
    }

    // Botón de refrescar
    var refreshBtn = document.getElementById('btn-refresh');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            cargarComandas();
            showNotify && showNotify('info', 'Actualizando', 'Cargando comandas...');
        });
    }

    // Cargar comandas inicialmente y cada 30s
    cargarComandas();
    setInterval(cargarComandas, 30000);
});