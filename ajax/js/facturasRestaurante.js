// ==============================
// facturasRestaurante.js (FULL + edición + uploader imagen + editar MESAS/CLIENTES + selección/edición cliente + GESTIÓN DE COMBOS MEJORADA)
// ==============================

(function(){
  // Evitar error si algún plugin de bootstrap intenta usar selectpicker
  if (typeof window.jQuery !== "undefined" && !jQuery.fn.selectpicker) {
    jQuery.fn.selectpicker = function(){ return this; };
  }
})();
if (typeof SERVERURL === 'undefined') { console.error('SERVERURL no está definido.'); var SERVERURL = ''; }

document.addEventListener('DOMContentLoaded', function () {
  // Ocultar barras del layout si existen
  const navbarTop = document.querySelector(".sb-topnav");
  const navbarLateral = document.querySelector(".sb-sidenav");
  if (navbarTop) navbarTop.style.display = "none";
  if (navbarLateral) navbarLateral.style.display = "none";
  document.body.classList.add('vista-facturacion-restaurante');

  // ===== Estado =====
  let mesaSeleccionada = null;
  let facturaActual = null;
  let productos = [];
  let categorias = [];
  let comandaItems = [];
  let clientes = [];
  let mesas = [];
  let isvRates = { 1: 0, 2: 0 };
  let combos = [];
  let clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };

  // Edición
  let editContext = {
    productoId: null,
    categoriaId: null,
    clienteId: null
  };

  // selección en el modal de clientes
  let selectedClienteForModal = null;

  // ====== Referencias DOM ======
  const mesasContainer = document.getElementById('mesas-container');
  const productosContainer = document.getElementById('productos-container');
  const categoriasTabs = document.getElementById('categorias-tabs') || document.querySelector('.categorias-tabs');
  const comandaItemsContainer = document.getElementById('comanda-items');
  const subtotalElement = document.getElementById('subtotal');
  const impuesto1Element = document.getElementById('impuesto1');
  const impuesto2Element = document.getElementById('impuesto2');
  const impuesto1Label = document.getElementById('impuesto1-label');
  const impuesto2Label = document.getElementById('impuesto2-label');
  const totalElement = document.getElementById('total');

  const btnNuevaMesa = document.getElementById('btn-nueva-mesa');
  const btnGuardar   = document.getElementById('btn-guardar');
  const btnImprimir  = document.getElementById('btn-imprimir');
  const btnCerrar    = document.getElementById('btn-cerrar');
  const btnLimpiar   = document.getElementById('btn-limpiar');
  const btnBuscar    = document.getElementById('btn-buscar');

  const buscarProductoInput = document.getElementById('buscar-producto');
  const facturaTitle = document.getElementById('factura-title');
  const mesaSeleccionadaElement = document.getElementById('mesa-seleccionada');
  const clienteInfoElement = document.getElementById('cliente-info');
  const observacionesTextarea = document.getElementById('observaciones');

  const btnMostrarProductos = document.getElementById('btn-mostrar-productos');
  const btnMostrarComanda   = document.getElementById('btn-mostrar-comanda');
  const panelProductos = document.getElementById('panel-productos');
  const panelComanda   = document.getElementById('panel-comanda');

  // Gestión de combos - botones y modales
  const btnGestionarCombos  = document.getElementById('btn-gestionar-combos');
  const modalCombos         = document.getElementById('modal-combos');
  const modalComboEditor    = document.getElementById('modal-combo-editor');
  const combosGrid          = document.getElementById('combos-grid');
  const btnNuevoCombo       = document.getElementById('btn-nuevo-combo');
  const btnAddComboItem     = document.getElementById('btn-add-combo-item');
  const btnGuardarCombo     = document.getElementById('btn-guardar-combo');

  // Modales
  const modalMesa = document.getElementById('modal-mesa');
  const modalCliente = document.getElementById('modal-cliente');
  const modalCategoria = document.getElementById('modal-categoria');
  const modalProducto  = document.getElementById('modal-producto');
  const modalNuevoCliente = document.getElementById('modal-nuevo-cliente');

  // Close genérico por X (span.close) y por data-close
  const closeModalButtonsX = document.querySelectorAll('.close');
  const closeModalButtonsData = document.querySelectorAll('[data-close]');

  const formMesa = document.getElementById('form-mesa');
  const formNuevoCliente = document.getElementById('form-nuevo-cliente');

  // ===== Helpers =====
  function getProdControls() {
    return {
      selCat: document.getElementById('prod-categoria'),
      inpNombre: document.getElementById('prod-nombre'),
      inpDesc: document.getElementById('prod-descripcion'),
      inpPrecio: document.getElementById('prod-precio'),
      chkISV1: document.getElementById('prod-isv1'),
      chkISV2: document.getElementById('prod-isv2')
    };
  }

  function findProductoById(pid){
    pid = String(pid);
    return productos.find(p => String(p.productos_id) === pid);
  }

  // ==== SELECT2 ====
  function initSelect2All(){
    if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) {
      console.warn('Select2 no encontrado. Verifica que el JS/CSS estén cargados.');
      return;
    }
    // Selects del modal de Mesa
    if ($('#ubicacion-mesa').length) {
      $('#ubicacion-mesa').select2({
        width: '100%',
        minimumResultsForSearch: -1,
        dropdownParent: $('#modal-mesa')
      });
    }
    if ($('#estado-mesa').length) {
      $('#estado-mesa').select2({
        width: '100%',
        minimumResultsForSearch: -1,
        dropdownParent: $('#modal-mesa')
      });
    }
    // Select de categoría del Producto
    if ($('#prod-categoria').length) {
      $('#prod-categoria').select2({
        width: '100%',
        allowClear: true,
        placeholder: $('#prod-categoria').data('placeholder') || '',
        dropdownParent: $('#modal-producto')
      }).on('change', actualizarProdEstacionInfo);
    }
    // Selects del editor de combo
    if ($('#combo-producto').length) {
      if ($('#combo-producto').data('select2')) $('#combo-producto').select2('destroy');
      $('#combo-producto').select2({
        width: '100%',
        allowClear: true,
        placeholder: $('#combo-producto').data('placeholder') || 'Selecciona el producto combo',
        dropdownParent: $('#modal-combo-editor')
      });
    }
  }
  function reinitSelect2ProdCategoria(){
    if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) return;
    const $el = $('#prod-categoria');
    if (!$el.length) return;
    if ($el.data('select2')) $el.select2('destroy');
    $el.select2({
      width: '100%',
      allowClear: true,
      placeholder: $el.data('placeholder') || '',
      dropdownParent: $('#modal-producto')
    }).on('change', actualizarProdEstacionInfo);
  }
  function initSelect2ForComboRow(rowEl){
    if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) return;
    const $sel = $(rowEl).find('select.combo-item-producto');
    if ($sel.length){
      if ($sel.data('select2')) $sel.select2('destroy');
      $sel.select2({
        width:'100%',
        placeholder: $sel.data('placeholder') || 'Producto del combo',
        dropdownParent: $('#modal-combo-editor')
      });
    }
  }

  // ==== ESTACIONES (Cocina/Barra) ====
  function normalizeEstacion(cat){
    let est = (cat.estacion || cat.station || cat.ruta || cat.tipo || '').toString().toLowerCase();
    if (!est) {
      if (String(cat.es_cocina || '') === '1') est = 'cocina';
      else if (String(cat.es_barra || '') === '1') est = 'barra';
      else est = 'ninguna';
    }
    if (!['cocina','barra','ninguna'].includes(est)) est = 'ninguna';
    return est;
  }
  function estacionSeleccionadaUI(){
    const r = document.querySelector('#filtro-estacion input[name="filEst"]:checked');
    return r ? r.value : 'todas';
  }
  function prodEstacionSeleccionadaUI(){
    const r = document.querySelector('#prod-estacion input[name="prodEstacion"]:checked');
    return r ? r.value : 'cocina';
  }
  function fillProdCategoriaOptionsByEstacion(preselectId){
    const { selCat } = getProdControls();
    if (!selCat) return;
    const est = prodEstacionSeleccionadaUI();
    const list = categorias.filter(c => normalizeEstacion(c) === est);
    if (!list.length) {
      selCat.innerHTML = '<option value="">(No hay categorías)</option>';
    } else {
      selCat.innerHTML = list.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join('');
    }
    if (preselectId) {
      selCat.value = String(preselectId);
    }
    reinitSelect2ProdCategoria();
    actualizarProdEstacionInfo();
  }
  function actualizarProdEstacionInfo(){
    const sel = document.getElementById('prod-categoria');
    const wrap = document.getElementById('prod-estacion-info');
    const val  = document.getElementById('prod-estacion-info-val');
    if (!sel || !wrap || !val) return;
    const cid = sel.value ? String(sel.value) : '';
    const cat = categorias.find(c => String(c.id) === cid);
    if (cat){
      wrap.style.display = 'block';
      const est = normalizeEstacion(cat);
      val.textContent = est.charAt(0).toUpperCase() + est.slice(1);
    } else {
      wrap.style.display = 'none';
      val.textContent = '—';
    }
  }

  // ====== Inicio ======
  init();
  function init() {
    cargarISV().then(actualizarEtiquetasISVCabecera);
    cargarMesas();
    cargarCategorias();
    cargarProductos().then(()=>{ /* productos listos para editor de combo */ });
    cargarClientes();
    setupEventListeners();
    bloquearCierrePorFondoYEsc();
    initProductoImageUpload();
    initSelect2All();
  }

  function setupEventListeners() {
    // Nueva mesa
    if (btnNuevaMesa) btnNuevaMesa.addEventListener('click', mostrarModalMesa);

    // Cierre por "X"
    closeModalButtonsX.forEach(btn => {
      btn.addEventListener('click', function () {
        const target = this.getAttribute('data-close');
        if (target) {
          const m = document.querySelector(target);
          if (m) m.style.display = 'none';
        } else {
          if (modalMesa) modalMesa.style.display = 'none';
          if (modalCliente) modalCliente.style.display = 'none';
          if (modalCategoria) modalCategoria.style.display = 'none';
          if (modalProducto) modalProducto.style.display = 'none';
          if (modalNuevoCliente) modalNuevoCliente.style.display = 'none';
          if (modalCombos) modalCombos.style.display = 'none';
          if (modalComboEditor) modalComboEditor.style.display = 'none';
        }
      });
    });

    // Cierre por botones con data-close
    closeModalButtonsData.forEach(btn => {
      btn.addEventListener('click', function () {
        const target = this.getAttribute('data-close');
        if (!target) return;
        const m = document.querySelector(target);
        if (m) m.style.display = 'none';
      });
    });

    // Guardar mesa (crear/editar)
    if (formMesa) {
      formMesa.addEventListener('submit', function (e) {
        e.preventDefault();
        guardarMesa();
      });
    }

    // Guardar factura
    if (btnGuardar) btnGuardar.addEventListener('click', guardarFactura);

    // Imprimir
    if (btnImprimir) {
      btnImprimir.addEventListener('click', function () {
        const id = (facturaActual && (facturaActual.factura_id || facturaActual.id)) ? (facturaActual.factura_id || facturaActual.id) : null;
        if (!id) { showNotify && showNotify('warning', 'Sin factura', 'No hay una factura cargada para imprimir'); return; }
        if (typeof printBill === 'function') printBill(id); else window.print();
      });
    }

    // Otros
    if (btnLimpiar) btnLimpiar.addEventListener('click', limpiarComanda);
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarFactura);

    // Búsqueda productos
    if (buscarProductoInput) {
      buscarProductoInput.addEventListener('input', function () { filtrarProductos(this.value); });
      buscarProductoInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); filtrarProductos(this.value); } });
    }
    if (btnBuscar) btnBuscar.addEventListener('click', function () { filtrarProductos(buscarProductoInput ? buscarProductoInput.value : ''); });

    // Cliente: seleccionar / buscar
    const btnCambiarCliente = document.getElementById('btn-cambiar-cliente');
    if (btnCambiarCliente) btnCambiarCliente.addEventListener('click', mostrarModalCliente);

    const buscarClienteInput = document.getElementById('buscar-cliente');
    if (buscarClienteInput) buscarClienteInput.addEventListener('input', function () { filtrarClientes(this.value); });

    const btnBuscarCliente = document.getElementById('btn-buscar-cliente');
    if (btnBuscarCliente) btnBuscarCliente.addEventListener('click', function (e) {
      e.preventDefault(); filtrarClientes((document.getElementById('buscar-cliente') || {}).value || '');
    });

    // Abrir modal de nuevo cliente (desde selector) y botón rápido en cabecera
    const btnNuevoCliente = document.getElementById('btn-nuevo-cliente');
    const btnNuevoClienteRapido = document.getElementById('btn-nuevo-cliente-rapido');
    if (btnNuevoCliente) btnNuevoCliente.addEventListener('click', abrirModalNuevoCliente);
    if (btnNuevoClienteRapido) btnNuevoClienteRapido.addEventListener('click', abrirModalNuevoCliente);

    // Acciones del modal de clientes
    const btnEditarSel = document.getElementById('btn-editar-cliente-seleccionado');
    const btnSeleccionar = document.getElementById('btn-seleccionar-cliente');
    if (btnEditarSel) btnEditarSel.addEventListener('click', ()=> {
      if (selectedClienteForModal && selectedClienteForModal.id !== 0) {
        abrirEdicionCliente(mapearClienteObjeto(selectedClienteForModal));
      }
    });
    if (btnSeleccionar) btnSeleccionar.addEventListener('click', confirmarSeleccionCliente);

    // Guardar nuevo/editar cliente
    if (formNuevoCliente) {
      formNuevoCliente.addEventListener('submit', function(e){
        e.preventDefault();
        guardarClienteBasico();
      });
    }

    // Toggle vistas
    if (btnMostrarProductos) btnMostrarProductos.addEventListener('click', () => mostrarVista('productos'));
    if (btnMostrarComanda)   btnMostrarComanda.addEventListener('click', () => mostrarVista('comanda'));

    // Filtro Estación (Todas/Cocina/Barra) — refresca pestañas
    document.querySelectorAll('#filtro-estacion input[name="filEst"]').forEach(radio=>{
      radio.addEventListener('change', ()=> renderizarCategorias());
    });

    // Segmentado estación del modal de Producto -> filtra categorías del select
    document.querySelectorAll('#prod-estacion input[name="prodEstacion"]').forEach(radio=>{
      radio.addEventListener('change', ()=> fillProdCategoriaOptionsByEstacion());
    });

    // Categoría / Producto
    const btnNuevaCategoria = document.getElementById('btn-nueva-categoria');
    const btnNuevoProducto  = document.getElementById('btn-nuevo-producto');

    if (btnNuevaCategoria) btnNuevaCategoria.addEventListener('click', ()=>{
      const inp = document.getElementById('cat-nombre');
      const hid = document.getElementById('cat-id');
      document.getElementById('titulo-modal-categoria') && (document.getElementById('titulo-modal-categoria').textContent = 'Nueva Categoría');
      if (inp) inp.value='';
      if (hid) hid.value='';
      if (modalCategoria) modalCategoria.style.display='block';
      setTimeout(()=>inp && inp.focus(),10);
    });

    if (btnNuevoProducto) btnNuevoProducto.addEventListener('click', ()=>{
      editContext.productoId = null;
      const { selCat, inpNombre, inpDesc, inpPrecio, chkISV1, chkISV2 } = getProdControls();
      if (inpNombre) inpNombre.value='';
      if (inpDesc) inpDesc.value='';
      if (inpPrecio) inpPrecio.value='0.00';
      if (chkISV1) chkISV1.checked=false;
      if (chkISV2) chkISV2.checked=false;

      const el = document.getElementById('prod-id');
      if (el) el.value = '';

      document.getElementById('titulo-modal-producto') && (document.getElementById('titulo-modal-producto').textContent = 'Nuevo Producto');

      prepararModalProductoISV();

      resetProductoImagen();
      initProductoImageUpload();

      fillProdCategoriaOptionsByEstacion();

      if (modalProducto) modalProducto.style.display='block';
      setTimeout(()=>{ inpNombre && inpNombre.focus(); },10);
    });

    const btnGuardarCategoria = document.getElementById('btn-guardar-categoria');
    if (btnGuardarCategoria) btnGuardarCategoria.addEventListener('click', guardarCategoriaDesdeModal);
    const btnGuardarProducto = document.getElementById('btn-guardar-producto');
    if (btnGuardarProducto) btnGuardarProducto.addEventListener('click', guardarProductoBasico);

    // Gestión de combos - listeners
    if (btnGestionarCombos) btnGestionarCombos.addEventListener('click', abrirModalCombos);
    if (btnNuevoCombo) btnNuevoCombo.addEventListener('click', ()=> {
      abrirEditorComboNuevo();
    });
    if (btnAddComboItem) btnAddComboItem.addEventListener('click', ()=> addComboItemRow());
    if (btnGuardarCombo) btnGuardarCombo.addEventListener('click', guardarCombo);

    // Delegación: acciones en grid de combos (editar / eliminar)
    if (combosGrid) {
      combosGrid.addEventListener('click', (e)=>{
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id');
        if (!id) return;
        if (action === 'edit') abrirEditorComboExistente(parseInt(id,10));
        if (action === 'delete') eliminarCombo(parseInt(id,10));
      });
    }

    // Delegación: quitar fila en items de combo
    const comboItemsContainer = document.getElementById('combo-items-container');
    if (comboItemsContainer) {
      comboItemsContainer.addEventListener('click', (e)=>{
        const btn = e.target.closest('button[data-remove-row="1"]');
        if (!btn) return;
        const row = btn.closest('.component-row');
        if (row && row.parentNode) {
          row.parentNode.removeChild(row);
          reindexComboItems();
        }
      });
    }

    // Exponer refrescar clientes al global
    window.refrescarClientes = function (nuevoCliente) {
      cargarClientes().then(() => {
        if (nuevoCliente && nuevoCliente.clientes_id) {
          clienteSeleccionado = {
            id: nuevoCliente.clientes_id,
            nombre: nuevoCliente.nombre || 'Cliente',
            identificacion: nuevoCliente.identificacion || ''
          };
          if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${clienteSeleccionado.nombre}`;
        }
      });
    };
  }

  // Evitar cerrar modales por fondo/ESC
  function bloquearCierrePorFondoYEsc(){
    [modalMesa, modalCliente, modalCategoria, modalProducto, modalNuevoCliente, modalCombos, modalComboEditor].forEach(m => {
      if (!m) return;
      m.addEventListener('click', (ev)=>{ if (ev.target === m) { ev.stopPropagation(); ev.preventDefault(); } });
    });
    window.addEventListener('keydown', (ev)=>{ if (ev.key === 'Escape') { ev.preventDefault(); ev.stopPropagation(); } }, true);
  }

  function mostrarVista(que) {
    if (que === 'productos') {
      if (panelProductos) panelProductos.style.display = '';
      if (panelComanda) panelComanda.style.display = 'none';
      if (btnMostrarProductos) btnMostrarProductos.style.display = 'none';
      if (btnMostrarComanda) btnMostrarComanda.style.display = '';
    } else {
      if (panelProductos) panelProductos.style.display = 'none';
      if (panelComanda) panelComanda.style.display = '';
      if (btnMostrarProductos) btnMostrarProductos.style.display = '';
      if (btnMostrarComanda) btnMostrarComanda.style.display = 'none';
    }
  }

  // ===== ISV (para totales de la comanda) =====
  function cargarISV() {
    return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadISV'
    })
      .then(r => r.json())
      .then(data => {
        if (data.status && Array.isArray(data.isv)) {
          data.isv.forEach(i => { isvRates[i.id] = parseFloat(i.valor || 0); });
        }
      })
      .catch(()=>{});
  }
  function actualizarEtiquetasISVCabecera(){
    if (impuesto1Label) impuesto1Label.textContent = `Impuesto (ISV ${isvRates[1]||0}%):`;
    if (impuesto2Label) impuesto2Label.textContent = `Impuesto (ISV ${isvRates[2]||0}%):`;
  }

  // ===== Mesas =====
  function cargarMesas() {
    return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadMesas'
    })
      .then(response => response.json())
      .then(data => {
        if (data.status) { mesas = data.mesas; renderizarMesas(); }
        else { showNotify && showNotify('error', 'Error', 'No se pudieron cargar las mesas'); }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al cargar las mesas'); });
  }

  function renderizarMesas() {
    if (!mesasContainer) return;
    mesasContainer.innerHTML = '';
    if (!mesas.length) {
      mesasContainer.innerHTML = `<div class="mesa-item" style="opacity:.8;">
        <div class="mesa-header"><span class="mesa-numero">Sin mesas</span></div>
        <div class="mesa-info"><span class="mesa-ubicacion">Crea una con "Nueva".</span></div>
      </div>`;
      return;
    }
    mesas.forEach(mesa => {
      const mesaElement = document.createElement('div');
      mesaElement.className = `mesa-item ${mesa.estado === 'ocupada' ? 'ocupada' : ''}`;
      mesaElement.dataset.id = String(mesa.id);
      mesaElement.innerHTML = `
        <div class="mesa-header">
          <span class="mesa-numero">Mesa: ${mesa.numero}</span>
          <span class="mesa-capacidad">${mesa.capacidad} <i class="fas fa-user"></i></span>
        </div>
        <div class="mesa-info">
          <span class="mesa-ubicacion"><i class="fas fa-map-marker-alt"></i> ${mesa.ubicacion}</span>
          <span class="mesa-estado ${mesa.estado}">${mesa.estado === 'ocupada' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>'} ${mesa.estado.toUpperCase()}</span>
        </div>
        <div class="mesa-actions">
          <button class="btn-icon btn-icon--sm btn-edit-mesa" title="Editar" type="button"><i class="fas fa-pen"></i></button>
        </div>
      `;
      mesaElement.addEventListener('click', () => seleccionarMesa(mesa));
      mesaElement.querySelector('.btn-edit-mesa').addEventListener('click', (e)=>{
        e.stopPropagation();
        abrirEdicionMesa(mesa);
      });
      mesasContainer.appendChild(mesaElement);
    });

    highlightMesaSeleccionada();
  }

  function highlightMesaSeleccionada(){
    document.querySelectorAll('.mesa-item').forEach(el => el.classList.remove('seleccionada'));
    if (!mesaSeleccionada) return;
    const el = document.querySelector(`.mesa-item[data-id="${mesaSeleccionada.id}"]`);
    if (el) el.classList.add('seleccionada');
  }

  function seleccionarMesa(mesa){
    mesaSeleccionada = {
      id: mesa.id || mesa.mesa_id,
      numero: mesa.numero,
      capacidad: mesa.capacidad,
      ubicacion: mesa.ubicacion,
      estado: mesa.estado
    };

    if (mesaSeleccionadaElement) mesaSeleccionadaElement.textContent = `Mesa: ${mesaSeleccionada.numero}`;
    if (facturaTitle) facturaTitle.textContent = 'Nueva Comanda';
    if (btnImprimir) btnImprimir.disabled = true;

    clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
    if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${clienteSeleccionado.nombre}`;

    comandaItems = [];
    actualizarComandaUI();

    highlightMesaSeleccionada();

    if (mesaSeleccionada.id) {
      cargarFacturaMesa(mesaSeleccionada.id);
    }
  }

  function abrirEdicionMesa(mesa){
    const n  = document.getElementById('numero-mesa');
    const c  = document.getElementById('capacidad-mesa');
    const u  = document.getElementById('ubicacion-mesa');
    const id = document.getElementById('mesa-id');
    const t  = document.getElementById('titulo-modal-mesa');

    if (t) t.textContent = 'Editar Mesa';
    if (id) id.value = mesa.id || mesa.mesa_id || '';
    if (n) n.value  = mesa.numero || '';
    if (c) c.value  = mesa.capacidad || 4;
    if (u) u.value  = mesa.ubicacion || 'Interior';

    if (modalMesa) modalMesa.style.display = 'block';
    setTimeout(()=> n && n.focus(), 10);
  }

  function mostrarModalMesa() {
    const n = document.getElementById('numero-mesa');
    const c = document.getElementById('capacidad-mesa');
    const u = document.getElementById('ubicacion-mesa');
    const id = document.getElementById('mesa-id');
    const t = document.getElementById('titulo-modal-mesa');

    if (t) t.textContent = 'Nueva Mesa';
    if (id) id.value = '';
    if (n) n.value = '';
    if (c) c.value = '4';
    if (u) u.value = 'Interior';
    if (modalMesa) modalMesa.style.display = 'block';
    setTimeout(() => { if (n) { n.focus(); n.select && n.select(); } }, 10);
  }

  function guardarMesa() {
    const mesaId     = (document.getElementById('mesa-id') || {}).value || '';
    const numeroMesa = (document.getElementById('numero-mesa') || {}).value?.trim() || '';
    const capacidad  = (document.getElementById('capacidad-mesa') || {}).value || '4';
    const ubicacion  = (document.getElementById('ubicacion-mesa') || {}).value || 'Interior';
    if (!numeroMesa) { showNotify && showNotify('error', 'Error', 'El número de mesa es requerido'); return; }

    const formData = new FormData();
    const action = mesaId ? 'updateMesa' : 'saveMesa';
    formData.append('action', action);
    if (mesaId) formData.append('mesa_id', mesaId);
    formData.append('numero', numeroMesa);
    formData.append('capacidad', capacidad);
    formData.append('ubicacion', ubicacion);

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          showNotify && showNotify('success', 'Éxito', mesaId ? 'Mesa actualizada correctamente' : 'Mesa guardada correctamente');
          if (modalMesa) modalMesa.style.display = 'none';
          (document.getElementById('mesa-id')||{}).value = '';
          cargarMesas();
        } else {
          showNotify && showNotify('error', 'Error', data.message || 'No se pudo guardar la mesa');
        }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al guardar la mesa'); });
  }

  // ===== Categorías / Productos =====
  function cargarCategorias() {
    return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadCategorias'
    })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          categorias = (data.categorias || []).map(c => ({
            id: c.id || c.categoria_id,
            nombre: c.nombre || c.categoria || `Cat ${c.id || c.categoria_id}`,
            estacion: normalizeEstacion(c)
          }));
          renderizarCategorias();
        }
      });
  }

  function renderizarCategorias() {
    if (!categoriasTabs) return;
    categoriasTabs.innerHTML = '';

    const estSel = estacionSeleccionadaUI();
    const catsFiltradas = (estSel === 'todas') ? categorias
      : categorias.filter(c => normalizeEstacion(c) === estSel);

    const todasCategoria = document.createElement('div');
    todasCategoria.className = 'categoria-tab active';
    todasCategoria.textContent = 'Todas';
    todasCategoria.addEventListener('click', () => {
      document.querySelectorAll('.categoria-tab').forEach(tab => tab.classList.remove('active'));
      todasCategoria.classList.add('active');
      filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');
    });
    categoriasTabs.appendChild(todasCategoria);

    catsFiltradas.forEach(categoria => {
      const categoriaElement = document.createElement('div');
      categoriaElement.className = 'categoria-tab';
      categoriaElement.dataset.id = categoria.id;

      const spanText = document.createElement('span');
      spanText.textContent = categoria.nombre;
      categoriaElement.appendChild(spanText);

      const btnEditCat = document.createElement('button');
      btnEditCat.className = 'edit-cat-btn';
      btnEditCat.title = 'Editar categoría';
      btnEditCat.type = 'button';
      btnEditCat.innerHTML = '<i class="fas fa-pen"></i>';
      btnEditCat.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirEdicionCategoria(categoria);
      });
      categoriaElement.appendChild(btnEditCat);

      categoriaElement.addEventListener('click', () => {
        document.querySelectorAll('.categoria-tab').forEach(tab => tab.classList.remove('active'));
        categoriaElement.classList.add('active');
        filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '', categoria.id);
      });
      categoriasTabs.appendChild(categoriaElement);
    });
  }

  function cargarProductos() {
    return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadProductos'
    })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          productos = data.productos || [];
          renderizarProductos(productos);
          if (!categorias.length) {
            const map = new Map();
            productos.forEach(p => { map.set(p.categoria_id, true); });
            categorias = [...map.keys()].map(id => ({ id, nombre: `Cat. ${id}`, estacion: 'ninguna' }));
            renderizarCategorias();
          }
        } else {
          showNotify && showNotify('error', 'Error', 'No se pudieron cargar los productos');
        }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al cargar los productos'); });
  }

  function renderizarProductos(productosList) {
    if (!productosContainer) return;
    productosContainer.innerHTML = '';

    if (!productosList.length) {
      productosContainer.innerHTML = `
        <div class="state-empty">
          <div class="icon"><i class="fas fa-shopping-basket"></i></div>
          <h4>Sin productos</h4>
          <p>Agrega uno con el botón "Nuevo producto".</p>
        </div>`;
      return;
    }

    const formatNumber = (num) => new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);

    productosList.forEach(producto => {
      const productoElement = document.createElement('div');
      productoElement.className = 'producto-item';

      const actions = document.createElement('div');
      actions.className = 'card-actions';
      const btnEditar = document.createElement('button');
      btnEditar.type = 'button';
      btnEditar.className = 'btn-icon btn-icon--sm';
      btnEditar.title = 'Editar';
      btnEditar.innerHTML = '<i class="fas fa-pen"></i>';
      btnEditar.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirEdicionProducto(producto);
      });
      actions.appendChild(btnEditar);
      productoElement.appendChild(actions);

      const imagenContainer = document.createElement('div');
      imagenContainer.className = 'producto-imagen-container';
      const imagenDiv = document.createElement('div');
      imagenDiv.className = 'producto-imagen';
      if (producto.file_name) {
        const img = document.createElement('img');
        img.src = `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}?${Date.now()}`;
        img.alt = producto.nombre;
        img.className = 'imagen-producto';
        img.loading = 'lazy';
        img.onerror = function () { this.remove(); imagenDiv.classList.add('sin-imagen'); };
        imagenDiv.appendChild(img);
      } else {
        imagenDiv.classList.add('sin-imagen');
      }
      imagenContainer.appendChild(imagenDiv);
      productoElement.appendChild(imagenContainer);

      const contenidoDiv = document.createElement('div');
      contenidoDiv.className = 'producto-contenido';
      const mostrarMayoreo = (producto.cantidad_mayoreo > 0 && producto.precio_mayoreo > 0);
      contenidoDiv.innerHTML = `
        <h4 class="producto-nombre">${producto.nombre}</h4>                
        <div class="producto-precios">
          <div class="precio-regular"><span class="precio-valor">L ${formatNumber(producto.precio_venta)}</span></div>
          ${mostrarMayoreo ? `<div class="precio-mayoreo"><span class="mayoreo-info">${producto.cantidad_mayoreo} x L ${formatNumber(producto.precio_mayoreo)}</span></div>` : ''}
        </div>`;
      productoElement.appendChild(contenidoDiv);

      const btnAgregar = document.createElement('button');
      btnAgregar.className = 'btn-agregar';
      btnAgregar.innerHTML = '<i class="fas fa-cart-plus"></i> Agregar';
      productoElement.appendChild(btnAgregar);

      const datosProducto = {
        id: producto.productos_id,
        nombre: producto.nombre,
        precio: parseFloat(producto.precio_venta),
        descripcion: producto.descripcion || '',
        imagen: producto.file_name ? `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}` : `${SERVERURL}vistas/plantilla/img/products/image_preview.png`,
        categoria_id: producto.categoria_id,
        isv1: parseInt(producto.isv1 || 0) === 1,
        isv2: parseInt(producto.isv2 || 0) === 1,
        para_cocina: 0
      };

      btnAgregar.addEventListener('click', (e) => { e.stopPropagation(); agregarProductoComanda(datosProducto); });
      productoElement.addEventListener('click', () => agregarProductoComanda(datosProducto));

      productosContainer.appendChild(productoElement);
    });
  }

  function filtrarProductos(termino, categoriaId = null) {
    let productosFiltrados = productos;
    if (termino) {
      const t = termino.toLowerCase();
      productosFiltrados = productosFiltrados.filter(p =>
        (p.nombre && p.nombre.toLowerCase().includes(t)) ||
        (p.descripcion && p.descripcion.toLowerCase().includes(t))
      );
    }
    if (categoriaId) productosFiltrados = productosFiltrados.filter(p => parseInt(p.categoria_id) === parseInt(categoriaId));
    renderizarProductos(productosFiltrados);
  }

  function guardarCategoriaDesdeModal(){
    const nombre = (document.getElementById('cat-nombre')||{}).value?.trim() || '';
    const idCat  = (document.getElementById('cat-id')||{}).value || '';

    if (!nombre) { showNotify && showNotify('warning','Validación','Nombre de categoría requerido'); return; }
    const fd = new FormData();
    fd.append('nombre', nombre);

    if (idCat) {
      fd.append('action','updateCategoria');
      fd.append('categoria_id', idCat);
    } else {
      fd.append('action','saveCategoria');
    }

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, { method:'POST', body: fd })
      .then(r=>r.json())
      .then(d=>{
        if (!d.status) { showNotify && showNotify('error','Error', d.message||'No se pudo guardar'); return; }
        showNotify && showNotify('success','Éxito', idCat ? 'Categoría actualizada' : 'Categoría guardada');
        if (modalCategoria) modalCategoria.style.display='none';
        (document.getElementById('cat-id')||{}).value = '';
        document.getElementById('titulo-modal-categoria') && (document.getElementById('titulo-modal-categoria').textContent = 'Nueva Categoría');
        cargarCategorias();
      })
      .catch(()=> showNotify && showNotify('error','Error','No se pudo guardar'));
  }

  function guardarProductoBasico(){
    const { inpNombre, inpDesc, selCat, inpPrecio, chkISV1, chkISV2 } = getProdControls();
    const id     = (document.getElementById('prod-id')||{}).value || '';
    const nombre = (inpNombre||{}).value?.trim() || '';
    const desc   = (inpDesc||{}).value?.trim() || '';
    const catId  = (selCat||{}).value || '';
    const precio = parseFloat((inpPrecio||{}).value || '0') || 0;
    const isv1   = !!(chkISV1||{}).checked;
    const isv2   = !!(chkISV2||{}).checked;

    if (!nombre) { showNotify && showNotify('warning','Validación','Nombre requerido'); return; }
    if (!catId)  { showNotify && showNotify('warning','Validación','Seleccione categoría'); return; }

    const payload = {
      productos_id: id ? parseInt(id) : undefined,
      nombre, descripcion: desc, categoria_id: parseInt(catId),
      precio_venta: precio,
      isv1: isv1 ? 1 : 0,
      isv2: (!isv1 && isv2) ? 1 : 0
    };

    const action = id ? 'updateProductoBasico' : 'saveProductoBasico';

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action, data: payload })
    })
      .then(r=>r.json())
      .then(async d=>{
        if (!d.status) { showNotify && showNotify('error','Error', d.message||'No se pudo guardar'); return; }

        const pid  = id || d.producto_id;
        const file = getProductoImagenFile();

        if (pid && file) {
          try {
            const ok = await subirImagenProducto(pid, file);
            if (!ok) showNotify && showNotify('warning','Atención','Guardado, pero falló la imagen');
          } catch {
            showNotify && showNotify('warning','Atención','Guardado, pero falló la imagen');
          }
        }

        showNotify && showNotify('success','Éxito', id ? 'Producto actualizado' : 'Producto guardado');
        if (modalProducto) modalProducto.style.display='none';
        (document.getElementById('prod-id')||{}).value = '';
        document.getElementById('titulo-modal-producto') && (document.getElementById('titulo-modal-producto').textContent = 'Nuevo Producto');
        resetProductoImagen();
        cargarProductos();
      })
      .catch(()=> showNotify && showNotify('error','Error','No se pudo guardar el producto'));
  }

  // ===== Clientes =====
  function cargarClientes() {
    return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadClientes'
    })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          clientes = data.clientes || [];
          if (modalCliente && modalCliente.style.display === 'block') renderizarClientes();
        } else {
          showNotify && showNotify('error', 'Error', 'No se pudieron cargar los clientes');
        }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al cargar los clientes'); });
  }

  function mapearClienteObjeto(c){
    return {
      clientes_id: c.id || c.clientes_id,
      nombre: c.nombre,
      identificacion: c.identificacion || c.rtn || ''
    };
  }

  function renderizarClientes() {
    const cont = document.getElementById('clientes-container');
    const btnEditarSel = document.getElementById('btn-editar-cliente-seleccionado');
    const btnSeleccionar = document.getElementById('btn-seleccionar-cliente');

    if (!cont) return;
    cont.innerHTML = '';
    selectedClienteForModal = null;
    actualizarBotonesModalCliente();

    // Consumidor Final
    const cf = document.createElement('div');
    cf.className = 'cliente-item';
    cf.dataset.id = '0';
    cf.innerHTML = `
      <div class="cliente-nombre">CONSUMIDOR FINAL</div>
      <div class="cliente-identificacion">Cliente genérico</div>`;
    cf.addEventListener('click', () => {
      marcarSeleccionClienteItem(cf);
      selectedClienteForModal = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
      actualizarBotonesModalCliente();
    });
    cf.addEventListener('dblclick', confirmarSeleccionCliente);
    cont.appendChild(cf);

    // Resto de clientes
    clientes.forEach(c => {
      const el = document.createElement('div');
      el.className = 'cliente-item';
      el.dataset.id = String(c.clientes_id);
      el.innerHTML = `
        <div class="cliente-nombre">${c.nombre}</div>
        <div class="cliente-identificacion">${c.identificacion || 'Sin identificación'}</div>
        <button class="btn-edit-cli btn-icon btn-icon--sm" title="Editar" type="button"><i class="fas fa-pen"></i></button>
      `;
      el.querySelector('.btn-edit-cli').addEventListener('click', (e)=>{
        e.stopPropagation();
        abrirEdicionCliente(c);
      });
      el.addEventListener('click', () => {
        marcarSeleccionClienteItem(el);
        selectedClienteForModal = { id: c.clientes_id, nombre: c.nombre, identificacion: c.identificacion || '' };
        actualizarBotonesModalCliente();
      });
      el.addEventListener('dblclick', confirmarSeleccionCliente);

      cont.appendChild(el);
    });

    function actualizarBotonesModalCliente(){
      if (btnSeleccionar) btnSeleccionar.disabled = !selectedClienteForModal;
      if (btnEditarSel) {
        const editable = !!(selectedClienteForModal && selectedClienteForModal.id && Number(selectedClienteForModal.id) > 0);
        btnEditarSel.disabled = !editable;
      }
    }
  }

  function marcarSeleccionClienteItem(el){
    const cont = document.getElementById('clientes-container');
    if (!cont) return;
    cont.querySelectorAll('.cliente-item').forEach(n => n.classList.remove('selected'));
    el.classList.add('selected');
  }

  function confirmarSeleccionCliente(){
    if (!selectedClienteForModal) return;
    clienteSeleccionado = {
      id: Number(selectedClienteForModal.id || selectedClienteForModal.clientes_id || 0),
      nombre: selectedClienteForModal.nombre,
      identificacion: selectedClienteForModal.identificacion || ''
    };
    const clienteInfoElement = document.getElementById('cliente-info');
    if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${clienteSeleccionado.nombre}`;
    if (modalCliente) modalCliente.style.display = 'none';
  }

  function mostrarModalCliente() {
    renderizarClientes();
    const buscador = document.getElementById('buscar-cliente');
    const btnSeleccionar = document.getElementById('btn-seleccionar-cliente');
    const btnEditarSel = document.getElementById('btn-editar-cliente-seleccionado');

    selectedClienteForModal = null;
    if (btnSeleccionar) btnSeleccionar.disabled = true;
    if (btnEditarSel) btnEditarSel.disabled = true;

    if (buscador) buscador.value = '';
    if (modalCliente) modalCliente.style.display = 'block';
    setTimeout(() => { if (buscador) { buscador.focus(); buscador.select && buscador.select(); } }, 10);
  }

  function filtrarClientes(termino) {
    const t = (termino || '').toLowerCase();
    const cont = document.getElementById('clientes-container');
    if (!cont) return;
    const items = cont.querySelectorAll('.cliente-item');
    if (!items.length) return;

    for (let i = 0; i < items.length; i++) {
      const nombre = items[i].querySelector('.cliente-nombre').textContent.toLowerCase();
      const identEl = items[i].querySelector('.cliente-identificacion');
      const ident = identEl ? identEl.textContent.toLowerCase() : '';
      items[i].style.display = (nombre.includes(t) || ident.includes(t)) ? 'block' : 'none';
    }
  }

  function abrirModalNuevoCliente(){
    const campos = ['cli-id','cli-nombre','cli-rtn','cli-localidad','cli-telefono','cli-correo'];
    campos.forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
    document.getElementById('titulo-modal-cliente') && (document.getElementById('titulo-modal-cliente').textContent = 'Nuevo Cliente');
    if (modalNuevoCliente) modalNuevoCliente.style.display = 'block';
    setTimeout(()=>{ const el = document.getElementById('cli-nombre'); el && el.focus(); },10);
  }

  function guardarClienteBasico(){
    const id         = (document.getElementById('cli-id')||{}).value || '';
    const nombre     = (document.getElementById('cli-nombre')||{}).value?.trim() || '';
    const rtn        = (document.getElementById('cli-rtn')||{}).value?.trim() || '';
    const localidad  = (document.getElementById('cli-localidad')||{}).value?.trim() || '';
    const telefono   = (document.getElementById('cli-telefono')||{}).value?.trim() || '';
    const correo     = (document.getElementById('cli-correo')||{}).value?.trim() || '';

    if (!nombre){ showNotify && showNotify('warning','Validación','Nombre/ Razón social es obligatorio'); return; }

    const payload = {
      clientes_id: id ? parseInt(id) : undefined,
      nombre,
      rtn,
      fecha: new Date().toISOString().slice(0,10),
      departamentos_id: 0,
      municipios_id: 0,
      localidad,
      telefono,
      correo,
      estado: 1
    };

    const action = id ? 'updateClienteBasico' : 'saveClienteBasico';

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action, data: payload })
    })
      .then(r=>r.json())
      .then(d=>{
        if (!d.status){ showNotify && showNotify('error','Error', d.message || 'No se pudo guardar el cliente'); return; }
        showNotify && showNotify('success','Éxito', id ? 'Cliente actualizado' : 'Cliente guardado');
        if (modalNuevoCliente) modalNuevoCliente.style.display = 'none';
        (document.getElementById('cli-id')||{}).value = '';
        document.getElementById('titulo-modal-cliente') && (document.getElementById('titulo-modal-cliente').textContent = 'Nuevo Cliente');

        cargarClientes().then(()=>{
          const cli = d.cliente;
          if (cli && cli.clientes_id){
            clienteSeleccionado = {
              id: cli.clientes_id,
              nombre: cli.nombre || nombre,
              identificacion: cli.rtn || rtn
            };
            if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${clienteSeleccionado.nombre}`;
          }
        });
      })
      .catch(()=> showNotify && showNotify('error','Error','No se pudo guardar el cliente'));
  }

  function abrirEdicionCliente(c){
    editContext.clienteId = c.clientes_id;
    if (modalNuevoCliente) modalNuevoCliente.style.display = 'block';
    document.getElementById('titulo-modal-cliente') && (document.getElementById('titulo-modal-cliente').textContent = 'Editar Cliente');

    setTimeout(()=>{
      const map = {
        'cli-id': c.clientes_id || '',
        'cli-nombre': c.nombre || '',
        'cli-rtn': (c.identificacion||''),
        'cli-localidad': '',
        'cli-telefono': '',
        'cli-correo': ''
      };
      Object.keys(map).forEach(id=>{
        const el = document.getElementById(id);
        if (el) el.value = map[id];
      });
      const focus = document.getElementById('cli-nombre'); focus && focus.focus();
    }, 10);
  }

  // ===== FACTURAS =====
  function cargarFacturaMesa(mesaId) {
    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=loadFacturaMesa&mesa_id=${encodeURIComponent(mesaId)}`
    })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          facturaActual = data.factura;
          mesaSeleccionada = data.mesa;
          comandaItems = data.items.map(item => ({
            producto: {
              id: item.productos_id,
              nombre: item.nombre_producto,
              precio: parseFloat(item.precio),
              descripcion: item.descripcion_producto || '',
              isv1: false,
              isv2: false,
              para_cocina: 0
            },
            cantidad: parseFloat(item.cantidad),
            precio: parseFloat(item.precio),
            total: parseFloat(item.precio) * parseFloat(item.cantidad)
          }));

          if (mesaSeleccionadaElement) mesaSeleccionadaElement.textContent = `Mesa: ${mesaSeleccionada.numero}`;
          if (facturaTitle) facturaTitle.textContent = `Factura #${facturaActual.number}`;
          if (observacionesTextarea) observacionesTextarea.value = facturaActual.notas || '';
          if (btnImprimir) btnImprimir.disabled = false;

          if (facturaActual.cliente_id && facturaActual.cliente_nombre) {
            clienteSeleccionado = { id: facturaActual.cliente_id, nombre: facturaActual.cliente_nombre, identificacion: facturaActual.cliente_identificacion || '' };
            if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${facturaActual.cliente_nombre}`;
          } else {
            clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
            if (clienteInfoElement) clienteInfoElement.textContent = 'Cliente: Consumidor final';
          }

          actualizarComandaUI();
          highlightMesaSeleccionada();
        } else {
          facturaActual = null;
          comandaItems = [];
          actualizarComandaUI();
          if (facturaTitle) facturaTitle.textContent = 'Nueva Comanda';
          if (btnImprimir) btnImprimir.disabled = true;
          if (mesaSeleccionada && mesaSeleccionadaElement) {
            mesaSeleccionadaElement.textContent = `Mesa: ${mesaSeleccionada.numero}`;
          }
          highlightMesaSeleccionada();
        }
      })
      .catch(() => {
        showNotify && showNotify('error', 'Error', 'Error al cargar la factura');
      });
  }

  function agregarProductoComanda(producto) {
    const existente = comandaItems.find(i => i.producto.id === producto.id);
    if (existente) {
      existente.cantidad += 1;
      existente.total = existente.cantidad * existente.precio;
    } else {
      comandaItems.push({ producto, cantidad: 1, precio: producto.precio, total: producto.precio });
    }
    actualizarComandaUI();
    if (window.innerWidth <= 768) mostrarVista('comanda');
  }

  function actualizarComandaUI() {
    if (!comandaItemsContainer) return;
    comandaItemsContainer.innerHTML = '';

    const table = document.createElement('table');
    table.className = 'comanda-table';
    const thead = document.createElement('thead');
    thead.innerHTML = `
      <tr>
        <th style="width:40%">Producto</th>
        <th style="width:15%">Cantidad</th>
        <th style="width:15%">P. Unitario</th>
        <th style="width:15%">Subtotal</th>
        <th style="width:15%">Acción</th>
      </tr>`;
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    const formatNumber = (num) => new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);

    comandaItems.forEach((item, index) => {
      const row = document.createElement('tr');
      row.className = 'fade-in';
      row.innerHTML = `
        <td>
          <div class="comanda-producto-info">
            <span class="comanda-producto-nombre" title="${item.producto.nombre}">${item.producto.nombre}</span>
            ${item.producto.descripcion ? `<small class="comanda-producto-desc" title="${item.producto.descripcion}">${item.producto.descripcion}</small>` : ''}
          </div>
        </td>
        <td class="comanda-item-cantidad">
          <button class="btn-cantidad" data-index="${index}" data-action="decrement">-</button>
          <input type="number" min="1" value="${item.cantidad}" data-index="${index}">
          <button class="btn-cantidad" data-index="${index}" data-action="increment">+</button>
        </td>
        <td class="comanda-item-precio"><span class="moneda">L</span> ${formatNumber(item.precio)}</td>
        <td class="comanda-item-total"><span class="moneda">L</span> ${formatNumber(item.total)}</td>
        <td class="comanda-item-eliminar">
          <button class="btn-eliminar" data-index="${index}"><i class="fas fa-trash"></i></button>
        </td>
      `;
      tbody.appendChild(row);
    });

    table.appendChild(tbody);
    comandaItemsContainer.appendChild(table);

    document.querySelectorAll('.btn-cantidad').forEach(btn => {
      btn.addEventListener('click', function () {
        const index = parseInt(this.getAttribute('data-index'));
        const action = this.getAttribute('data-action');
        actualizarCantidad(index, action);
      });
    });
    document.querySelectorAll('.comanda-item-cantidad input').forEach(input => {
      input.addEventListener('change', function () {
        const idx = parseInt(this.getAttribute('data-index'));
        const val = Math.max(1, parseInt(this.value) || 1);
        actualizarCantidadInput(idx, val);
      });
    });
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
      btn.addEventListener('click', function () {
        const index = parseInt(this.getAttribute('data-index'));
        eliminarItemComanda(index);
      });
    });

    calcularTotales();
  }

  function actualizarCantidad(index, action) {
    if (index < 0 || index >= comandaItems.length) return;
    if (action === 'increment') comandaItems[index].cantidad += 1;
    else if (action === 'decrement') {
      if (comandaItems[index].cantidad > 1) comandaItems[index].cantidad -= 1;
      else { eliminarItemComanda(index); return; }
    }
    comandaItems[index].total = comandaItems[index].cantidad * comandaItems[index].precio;
    actualizarComandaUI();
  }

  function actualizarCantidadInput(index, nuevaCantidad) {
    if (index < 0 || index >= comandaItems.length || nuevaCantidad < 1) return;
    comandaItems[index].cantidad = nuevaCantidad;
    comandaItems[index].total = comandaItems[index].cantidad * comandaItems[index].precio;
    actualizarComandaUI();
  }

  function eliminarItemComanda(index) {
    if (index < 0 || index >= comandaItems.length) return;
    comandaItems.splice(index, 1);
    actualizarComandaUI();
  }

  function calcularTotales() {
    const subtotal = comandaItems.reduce((sum, it) => sum + (it.total || 0), 0);
    const r1 = (isvRates[1] || 0) / 100.0;
    const r2 = (isvRates[2] || 0) / 100.0;

    let imp1 = 0, imp2 = 0;
    comandaItems.forEach(it => {
      const base = it.precio * it.cantidad;
      if (it.producto.isv1) imp1 += base * r1;
      if (it.producto.isv2) imp2 += base * r2;
    });

    const total = subtotal + imp1 + imp2;
    const fmt = (n) => new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);

    if (subtotalElement) subtotalElement.textContent = `L ${fmt(subtotal)}`;
    if (impuesto1Element) impuesto1Element.textContent = `L ${fmt(imp1)}`;
    if (impuesto2Element) impuesto2Element.textContent = `L ${fmt(imp2)}`;
    if (totalElement) totalElement.textContent = `L ${fmt(total)}`;
  }

  function guardarFactura() {
    if (comandaItems.length === 0) { showNotify && showNotify('warning', 'Advertencia', 'La comanda está vacía'); return; }
    const metodoPago = document.querySelector('input[name="metodo-pago"]:checked')?.value || '';
    const observaciones = observacionesTextarea ? observacionesTextarea.value : '';

    const facturaData = {
      mesa_id: mesaSeleccionada ? mesaSeleccionada.id : 0,
      cliente_id: clienteSeleccionado.id,
      items: comandaItems.map(item => ({
        producto_id: item.producto.id,
        cantidad: item.cantidad,
        precio: item.precio,
        descripcion: item.producto.descripcion
      })),
      metodo_pago: metodoPago,
      observaciones: observaciones,
      factura_id: (facturaActual && (facturaActual.factura_id || facturaActual.id)) ? (facturaActual.factura_id || facturaActual.id) : null
    };

    const accion = facturaData.factura_id ? 'updateFactura' : 'saveFactura';

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: accion, data: facturaData })
    })
      .then(r => r.json())
      .then(data => {
        if (!data.status) { showNotify && showNotify('error', 'Error', data.message || 'Error al guardar la factura'); return; }
        showNotify && showNotify('success', 'Éxito', facturaData.factura_id ? 'Factura actualizada' : 'Factura guardada');
        if (accion === 'saveFactura' && data.factura) {
          facturaActual = data.factura;
        } else if (accion === 'updateFactura' && data.factura_id) {
          if (!facturaActual) facturaActual = {};
          facturaActual.factura_id = data.factura_id;
          facturaActual.id = data.factura_id;
        }
        if (facturaActual && facturaActual.number && facturaTitle) facturaTitle.textContent = `Factura #${facturaActual.number}`;
        if (btnImprimir) btnImprimir.disabled = false;
        cargarMesas();

        if (metodoPago !== '') {
          const fid = (data.factura_id) ? data.factura_id
            : (data.factura && (data.factura.factura_id || data.factura.id)) ? (data.factura.factura_id || data.factura.id)
              : (facturaActual && (facturaActual.factura_id || facturaActual.id)) ? (facturaActual.factura_id || facturaActual.id)
                : null;
          if (fid && typeof printBill === 'function') printBill(fid);
        }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al guardar la factura'); });
  }

  function cerrarFactura() {
    if (!facturaActual || !(facturaActual.id || facturaActual.factura_id)) { showNotify && showNotify('warning', 'Advertencia', 'No hay factura abierta'); return; }
    const fid = facturaActual.id || facturaActual.factura_id;

    if (confirm('¿Está seguro que desea cerrar esta factura?')) {
      fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=closeFactura&factura_id=${encodeURIComponent(fid)}`
      })
        .then(r => r.json())
        .then(data => {
          if (data.status) {
            showNotify && showNotify('success', 'Éxito', 'Factura cerrada correctamente');
            limpiarComanda();
            mesaSeleccionada = null;
            facturaActual = null;
            if (mesaSeleccionadaElement) mesaSeleccionadaElement.textContent = 'Mesa: No seleccionada';
            if (facturaTitle) facturaTitle.textContent = 'Nueva Comanda';
            if (btnImprimir) btnImprimir.disabled = true;
            clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
            if (clienteInfoElement) clienteInfoElement.textContent = 'Cliente: Consumidor final';
            cargarMesas();
          } else {
            showNotify && showNotify('error', 'Error', data.message || 'Error al cerrar la factura');
          }
        })
        .catch(() => { showNotify && showNotify('error', 'Error', 'Error al cerrar la factura'); });
    }
  }

  function limpiarComanda() { comandaItems = []; actualizarComandaUI(); }

  // ======= ISV del modal de Producto =======
  function prepararModalProductoISV(){
    const chk1 = document.getElementById('prod-isv1');
    const chk2 = document.getElementById('prod-isv2');

    if (chk1) chk1.checked = false;
    if (chk2) chk2.checked = false;

    function setIsvLabelSingleLine(chk, rate){
      if (!chk) return;
      const label = chk.closest('label');
      if (!label) return;

      const cb = chk;
      label.innerHTML = '';
      label.appendChild(cb);

      const span = document.createElement('span');
      span.className = 'isv-inline';
      span.textContent = ` ISV ${Number(rate)}%`;
      span.style.marginLeft = '8px';
      label.appendChild(span);

      cb.setAttribute('data-valor', (Number(rate) || 0) / 100);
    }

    fetch(`${SERVERURL}core/productos/getIsvConfig.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r=>r.json())
    .then(res=>{
      if (!res || !res.success) throw new Error('Respuesta inválida');

      if (chk1){
        chk1.checked = Number(res.isv1.activar) === 1;
        setIsvLabelSingleLine(chk1, res.isv1.valor);
      }
      if (chk2){
        chk2.checked = Number(res.isv2.activar) === 1;
        setIsvLabelSingleLine(chk2, res.isv2.valor);
      }

      aplicarSeleccionExclusivaISVProducto();
      if (chk1 && chk2 && chk1.checked && chk2.checked){ chk2.checked = false; }
    })
    .catch(()=>{
      setIsvLabelSingleLine(chk1, isvRates[1] || 0);
      setIsvLabelSingleLine(chk2, isvRates[2] || 0);
      aplicarSeleccionExclusivaISVProducto();
    });
  }

  function aplicarSeleccionExclusivaISVProducto(){
    const chk1 = document.getElementById('prod-isv1');
    const chk2 = document.getElementById('prod-isv2');
    if (chk1){
      chk1.onchange = function(){ if (this.checked && chk2) chk2.checked = false; };
    }
    if (chk2){
      chk2.onchange = function(){ if (this.checked && chk1) chk1.checked = false; };
    }
  }

  // ===== Edición: abrir modales precargados =====
  function abrirEdicionProducto(prod){
    editContext.productoId = prod.productos_id;

    const { selCat, inpNombre, inpDesc, inpPrecio, chkISV1, chkISV2 } = getProdControls();

    if (inpNombre) inpNombre.value = prod.nombre || '';
    if (inpDesc)   inpDesc.value   = prod.descripcion || '';
    if (inpPrecio) inpPrecio.value = (parseFloat(prod.precio_venta)||0).toFixed(2);
    if (chkISV1)   chkISV1.checked = parseInt(prod.isv1)==1;
    if (chkISV2)   chkISV2.checked = parseInt(prod.isv2)==1 && !chkISV1.checked;

    const hid = document.getElementById('prod-id');
    if (hid) hid.value = String(prod.productos_id || '');

    document.getElementById('titulo-modal-producto') && (document.getElementById('titulo-modal-producto').textContent = 'Editar Producto');

    const catActual = categorias.find(c => String(c.id) === String(prod.categoria_id));
    if (catActual){
      const est = normalizeEstacion(catActual);
      const radio = document.querySelector(`#prod-estacion input[value="${est}"]`);
      if (radio) radio.checked = true;
    }

    fillProdCategoriaOptionsByEstacion(prod.categoria_id);

    resetProductoImagen();
    setTimeout(()=>{
      if (prod.file_name) {
        const preview = document.getElementById('productoPreview');
        const info    = document.getElementById('productoInfo');
        if (preview && info){
          const img = document.createElement('img');
          img.src = `${SERVERURL}vistas/plantilla/img/products/${prod.file_name}?${Date.now()}`;
          img.alt = prod.nombre;
          preview.innerHTML = '';
          preview.appendChild(img);

          const removeBtn = document.createElement('button');
          removeBtn.className = 'btn-remove-image';
          removeBtn.type = 'button';
          removeBtn.title = 'Eliminar imagen';
          removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
          removeBtn.addEventListener('click', e => {
            e.stopPropagation();
            resetProductoImagen();
          });
          preview.appendChild(removeBtn);

          preview.style.display = 'block';
          info.textContent = prod.file_name;
        }
      }
    }, 50);

    prepararModalProductoISV();
    initProductoImageUpload();
    if (modalProducto) modalProducto.style.display = 'block';
  }

  function abrirEdicionCategoria(cat){
    editContext.categoriaId = cat.id;
    const inp = document.getElementById('cat-nombre');
    const hid = document.getElementById('cat-id');
    if (inp) inp.value = cat.nombre || '';
    if (hid) hid.value = String(cat.id || '');
    document.getElementById('titulo-modal-categoria') && (document.getElementById('titulo-modal-categoria').textContent = 'Editar Categoría');
    if (modalCategoria) modalCategoria.style.display = 'block';
  }

  // ===== Uploader de imagen producto =====
  function initProductoImageUpload() {
    const dropArea   = document.getElementById('productoDropArea');
    const fileInput  = document.getElementById('imagen_producto');
    const preview    = document.getElementById('productoPreview');
    const fileInfo   = document.getElementById('productoInfo');
    const selectBtn  = document.getElementById('btnSeleccionarImagen');

    if (!dropArea || !fileInput || fileInput.dataset.initialized) return;
    fileInput.dataset.initialized = 'true';

    let isProcessing = false;

    ['dragenter','dragoover','dragleave','drop'].forEach(ev =>
      dropArea.addEventListener(ev, preventDefaults, false)
    );
    ['dragenter','dragover'].forEach(ev =>
      dropArea.addEventListener(ev, () => dropArea.classList.add('drag-over'), false)
    );
    ['dragleave','drop'].forEach(ev =>
      dropArea.addEventListener(ev, () => dropArea.classList.remove('drag-over'), false)
    );
    dropArea.addEventListener('drop', e => {
      const files = e.dataTransfer?.files || [];
      if (files.length) handleFiles(files);
    });

    if (selectBtn) {
      const openChooser = (e) => { e.preventDefault(); e.stopPropagation(); fileInput.click(); };
      selectBtn.addEventListener('click', openChooser);
    }

    fileInput.addEventListener('change', e => {
      if (isProcessing) return;
      isProcessing = true;
      handleFiles(e.target.files);
      isProcessing = false;
    });

    document.addEventListener('paste', e => {
      const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
      let file = null;
      for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
          file = items[i].getAsFile();
          break;
        }
      }
      if (file) {
        e.preventDefault();
        const dt = new DataTransfer();
        dt.items.add(file);
        handleFiles(dt.files);
      }
    });

    function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

    function handleFiles(fileList) {
      if (!fileList || !fileList.length) return;
      const file = fileList[0];

      if (!file.type.startsWith('image/')) {
        (window.swal
          ? swal({ title: 'Error', text: 'Selecciona una imagen válida (JPG, PNG, GIF)', icon: 'error' })
          : alert('Selecciona una imagen válida (JPG, PNG, GIF)'));
        resetProductoImagen();
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        (typeof showNotify === 'function'
          ? showNotify('error', 'Error', 'La imagen no debe exceder 2MB')
          : alert('La imagen no debe exceder 2MB'));
        resetProductoImagen();
        return;
      }

      const reader = new FileReader();
      reader.onload = ev => {
        if (preview) {
          preview.innerHTML = '';

          const img = document.createElement('img');
          img.src = ev.target.result;
          img.alt = file.name;
          preview.appendChild(img);

          const removeBtn = document.createElement('button');
          removeBtn.className = 'btn-remove-image';
          removeBtn.type = 'button';
          removeBtn.title = 'Eliminar imagen';
          removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
          removeBtn.addEventListener('click', e => {
            e.stopPropagation();
            resetProductoImagen();
          });
          preview.appendChild(removeBtn);

          preview.style.display = 'block';
        }
        if (fileInfo) fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;
      };
      reader.readAsDataURL(file);
    }

    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024, sizes = ['Bytes','KB','MB','GB'], i = Math.floor(Math.log(bytes)/Math.log(k));
      return (bytes/Math.pow(k,i)).toFixed(2) + ' ' + sizes[i];
    }
  }

  function resetProductoImagen() {
    const fileInput = document.getElementById('imagen_producto');
    const preview = document.getElementById('productoPreview');
    const fileInfo = document.getElementById('productoInfo');
    if (fileInput) fileInput.value = '';
    if (preview) { preview.innerHTML = ''; preview.style.display = 'none'; }
    if (fileInfo) fileInfo.textContent = 'Ningún archivo seleccionado';
  }

  function getProductoImagenFile(){
    const fileInput = document.getElementById('imagen_producto');
    return fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
  }

  function subirImagenProducto(productoId, file){
    const fd = new FormData();
    fd.append('producto_id', productoId);
    fd.append('imagen_producto', file);
    return fetch(`${SERVERURL}core/productos/uploadImagenProducto.php`, {
      method: 'POST',
      body: fd
    }).then(r=>r.json())
      .then(j=> !!(j && j.status))
      .catch(()=>false);
  }

// ======== GESTIÓN DE COMBOS MEJORADA ========

function abrirModalCombos(){
  if (!modalCombos) return;
  modalCombos.style.display = 'block';
  cargarCombos();
}

function cargarCombos(){
  return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=loadCombos'
  })
  .then(r=>r.json())
  .then(data=>{
    if (!data || !data.status){ 
      showNotify && showNotify('error','Error','No se pudieron cargar los combos'); 
      return; 
    }
    combos = data.combos || [];
    renderizarCombos();
  })
  .catch(()=> showNotify && showNotify('error','Error','Error al cargar combos'));
}

function renderizarCombos(){
  if (!combosGrid) return;
  combosGrid.innerHTML = '';

  if (!combos.length){
    combosGrid.innerHTML = `
      <div class="state-empty">
        <div class="icon"><i class="fas fa-layer-group"></i></div>
        <h4>No hay combos configurados</h4>
        <p>Crea tu primer combo con el botón "Nuevo combo"</p>
      </div>`;
    return;
  }

  const formatNumber = (n) => new Intl.NumberFormat('es-HN',{minimumFractionDigits:2, maximumFractionDigits:2}).format(n);

  combos.forEach(c => {
    const comboId = c.combo_id || c.id;
    const pid = c.productos_id || c.producto_id;
    const prod = findProductoById(pid) || { nombre: c.nombre_combo || `Producto #${pid}`, precio_venta: 0 };
    const nombre = c.nombre_combo || prod.nombre || `Combo #${comboId}`;
    const precio = prod.precio_venta ? `L ${formatNumber(parseFloat(prod.precio_venta))}` : '-';
    const itemsResumen = c.componentes_resumen || c.items_resumen || c.items_count || '-';
    const activo = (String(c.activo)==='1' || c.activo===true);

    const card = document.createElement('div');
    card.className = 'combo-card';
    card.innerHTML = `
      <div class="combo-card-header">
        <h4 class="combo-card-title">${nombre}</h4>
        <div class="combo-card-status ${activo ? 'active' : 'inactive'}">
          <span class="status-indicator ${activo ? 'active' : 'inactive'}"></span>
          ${activo ? 'Activo' : 'Inactivo'}
        </div>
      </div>
      <div class="combo-card-body">
        <div class="combo-card-info">
          <div class="combo-card-price">${precio}</div>
          <div class="combo-card-items">${itemsResumen} componentes</div>
        </div>
      </div>
      <div class="combo-card-actions">
        <button class="btn btn-sm btn-primary" data-action="edit" data-id="${comboId}">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" data-action="delete" data-id="${comboId}">
          <i class="fas fa-trash"></i> Eliminar
        </button>
      </div>
    `;
    combosGrid.appendChild(card);
  });

  // Delegación de eventos (editar/eliminar)
  combosGrid.onclick = (e)=>{
    const btn = e.target.closest('button[data-action]');
    if(!btn) return;
    const id = btn.getAttribute('data-id');
    const action = btn.getAttribute('data-action');
    if (action==='edit')   abrirEditorComboExistente(id);
    if (action==='delete') eliminarCombo(id);
  };
}

function abrirEditorComboNuevo(){
  if (!modalComboEditor) return;

  setComboEditorTitle('Nuevo combo');
  setComboEditorIds('', 1, ''); // comboId vacío, activo=1, sin maestro
  clearComboItemsContainer();
  fillComboProductoOptions(null, null);
  initSelect2All();
  addComboItemRow();

  // Mensaje de ayuda
  const helpMessage = document.getElementById('combo-help-message');
  if (helpMessage) {
    helpMessage.innerHTML = `
      <div class="help-message">
        <h5>Define un producto "combo" y sus componentes</h5>
        <p>Organiza por <strong>Grupo</strong> (ej. Bebida, Acompañante). Usa <strong>Max selección</strong> cuando corresponda.</p>
      </div>
    `;
  }

  modalComboEditor.style.display = 'block';
  setTimeout(()=> $("#combo-producto").trigger('focus'), 10);
}

function abrirEditorComboExistente(comboId){
  const combo = combos.find(x => parseInt(x.combo_id||x.id,10) === parseInt(comboId,10));
  const maestroPid = combo ? (combo.productos_id || combo.producto_id) : null;
  const activo = combo ? (String(combo.activo)==='1' ? 1 : 0) : 1;

  fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`,{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=loadComboDetalle&combo_id=${encodeURIComponent(comboId)}`
  })
  .then(r=>r.json())
  .then(data=>{
    if (!data || !data.status){ 
      showNotify && showNotify('error','Error','No se pudo cargar el detalle del combo'); 
      return; 
    }

    const items = data.combo_detalle || [];
    setComboEditorTitle('Editar combo');
    setComboEditorIds(comboId, activo, maestroPid); // ← guarda hidden con el maestro
    clearComboItemsContainer();
    fillComboProductoOptions(maestroPid, comboId);  // excluye maestros usados en otros combos
    initSelect2All();

    if (Array.isArray(items) && items.length){
      items.sort((a,b)=> (parseInt(a.orden||1)-parseInt(b.orden||1)));
      items.forEach(it => addComboItemRow({
        productos_id: it.productos_id,
        cantidad: it.cantidad,
        es_opcional: String(it.es_opcional)==='1',
        grupo: it.grupo || '',
        max_seleccion: (it.max_seleccion ?? ''),
        precio_extra: it.precio_extra || 0,
        orden: it.orden || ''
      }));
    } else {
      addComboItemRow();
    }

    modalComboEditor.style.display = 'block';
  })
  .catch(()=> showNotify && showNotify('error','Error','Error al cargar el detalle del combo'));
}

function setComboEditorTitle(text){
  const el = document.getElementById('titulo-modal-combo');
  if (el) el.textContent = text;
}

// Crea/actualiza: también fija un hidden con el productos_id maestro
function setComboEditorIds(comboId, activo, productos_id){
  const hid = document.getElementById('combo-id');
  if (hid) hid.value = comboId ? String(comboId) : '';

  // Hidden para el maestro (necesario en edición)
  let hidProd = document.getElementById('combo-producto-hidden');
  if (!hidProd){
    hidProd = document.createElement('input');
    hidProd.type = 'hidden';
    hidProd.id   = 'combo-producto-hidden';
    hidProd.name = 'combo_producto_hidden';
    (modalComboEditor || document.body).appendChild(hidProd);
  }
  hidProd.value = productos_id ? String(productos_id) : '';

  // Switch Activo
  const switchContainer = document.getElementById('combo-activo-container');
  if (switchContainer) {
    switchContainer.innerHTML = `
      <label class="switch">
        <input type="checkbox" id="combo-activo-switch" ${activo ? 'checked' : ''}>
        <span class="slider round"></span>
      </label>
      <span class="switch-label">Combo activo</span>
    `;
  }

  // Mostrar producto seleccionado en edición
  const productoDisplay = document.getElementById('combo-producto-display');
  const productoSelectContainer = document.getElementById('combo-producto-container');

  if (productos_id && comboId) {
    const producto = findProductoById(productos_id);
    if (producto && productoDisplay) {
      productoDisplay.innerHTML = `
        <div class="selected-product-display">
          <strong>Producto que representa el combo:</strong>
          <span class="product-name-highlight">${producto.nombre}</span>
        </div>
        <p class="help-text"><small>Este producto debe existir y será el "maestro" del combo.</small></p>
      `;
      productoDisplay.style.display = 'block';
    }
    if (productoSelectContainer) productoSelectContainer.style.display = 'none';
  } else {
    if (productoDisplay) productoDisplay.style.display = 'none';
    if (productoSelectContainer) productoSelectContainer.style.display = 'block';
    const helpText = document.getElementById('combo-producto-help');
    if (helpText) {
      helpText.innerHTML = '<small>Este producto debe existir y será el "maestro" del combo.</small>';
    }
  }
}

function fillComboProductoOptions(selectedProductoId, currentComboId){
  const sel = document.getElementById('combo-producto');
  if (!sel) return;

  // Productos ya usados por otros combos (excluir)
  const usados = new Set(
    combos
      .filter(c => currentComboId ? (String(c.combo_id||c.id) !== String(currentComboId)) : true)
      .map(c => String(c.productos_id || c.producto_id))
  );

  const opts = productos
    .filter(p => !usados.has(String(p.productos_id)))
    .map(p => {
      const selected = (String(p.productos_id) === String(selectedProductoId)) ? 'selected' : '';
      return `<option value="${p.productos_id}" ${selected}>${p.nombre}</option>`;
    }).join('');

  sel.innerHTML = `<option value=""></option>${opts}`;
  if (selectedProductoId) sel.value = String(selectedProductoId);

  initSelect2All();
}

function clearComboItemsContainer(){
  const container = document.getElementById('combo-items-container');
  if (container) container.innerHTML = '';
}

function addComboItemRow(data){
  const container = document.getElementById('combo-items-container');
  if (!container) return;

  const idx = container.children.length + 1;
  const d = Object.assign({
    productos_id: '',
    cantidad: 1,
    es_opcional: false,
    grupo: '',
    max_seleccion: '',
    precio_extra: 0,
    orden: idx
  }, data || {});

  // ID maestro (toma hidden en edición o select en creación)
  const productoMaestroId =
    (document.getElementById('combo-producto-hidden')?.value) ||
    (document.getElementById('combo-producto')?.value) || '';

  // Excluir maestro del listado de componentes
  const productosFiltrados = productos.filter(p =>
    String(p.productos_id) !== String(productoMaestroId)
  );

  const options = productosFiltrados.map(p => {
    const sel = (String(p.productos_id) === String(d.productos_id)) ? 'selected' : '';
    return `<option value="${p.productos_id}" ${sel}>${p.nombre}</option>`;
  }).join('');

  const row = document.createElement('div');
  row.className = 'component-row card';
  row.innerHTML = `
  <div class="component-header">
    <h5>Componente #${idx}</h5>
    <button type="button" class="btn btn-sm btn-danger" data-remove-row="1">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <div class="component-body">
    <!-- 1) PRODUCTO -->
    <div class="form-group">
      <label>Producto <small class="text-muted">(No puede ser el producto combo)</small></label>
      <select class="combo-item-producto select2" data-placeholder="Selecciona un producto">
        <option value=""></option>
        ${options}
      </select>
    </div>

    <!-- 2) CANTIDAD -->
    <div class="form-group">
      <label>Cantidad <small class="text-muted">(Cantidad de este producto en el combo)</small></label>
      <input type="number" class="form-control combo-item-cantidad" min="0.001" step="0.001" value="${d.cantidad}" placeholder="Cantidad">
    </div>

    <!-- 3) OPCIONAL (label separado del input para igualar alturas) -->
    <div class="form-group">
      <label>Opcional <small class="text-muted">(El cliente puede elegir si lo incluye)</small></label>
      <label class="checkbox-container" style="margin-top:4px;">
        <input type="checkbox" class="combo-item-opcional" ${d.es_opcional ? 'checked' : ''}>
        <span class="checkmark"></span>
        <span style="margin-left:6px;">Permitir omitir</span>
      </label>
    </div>

    <!-- 4) GRUPO -->
    <div class="form-group">
      <label>Grupo <small class="text-muted">(Ej. Bebida, Acompañante, Plato fuerte)</small></label>
      <input type="text" class="form-control combo-item-grupo" placeholder="Grupo" value="${d.grupo}">
    </div>

    <!-- 5) MÁX. SELECCIÓN -->
    <div class="form-group">
      <label>Máx. selección <small class="text-muted">(Máximo de opciones que se pueden elegir de este grupo)</small></label>
      <input type="number" class="form-control combo-item-max" min="0" step="1" placeholder="Máx. selección" value="${d.max_seleccion}">
    </div>

    <!-- 6) PRECIO EXTRA -->
    <div class="form-group">
      <label>Precio extra <small class="text-muted">(Precio adicional si se selecciona esta opción)</small></label>
      <input type="number" class="form-control combo-item-extra" min="0" step="0.01" value="${d.precio_extra}" placeholder="Precio extra">
    </div>

    <!-- 7) ORDEN -->
    <div class="form-group">
      <label>Orden <small class="text-muted">(Orden de visualización)</small></label>
      <input type="number" class="form-control combo-item-orden" min="1" step="1" value="${d.orden}" placeholder="Orden">
    </div>
  </div>
`;
  container.appendChild(row);

  // Eliminar fila
  row.querySelector('[data-remove-row]')?.addEventListener('click', ()=>{
    row.remove();
    reindexComboItems();
  });

  initSelect2ForComboRow(row);
  reindexComboItems();
}

function reindexComboItems(){
  const rows = document.querySelectorAll('#combo-items-container .component-row');
  let n = 1;
  rows.forEach(r => {
    const ord = r.querySelector('.combo-item-orden');
    if (ord) ord.value = n++;
    const header = r.querySelector('.component-header h5');
    if (header) header.textContent = `Componente #${n-1}`;
  });
}

function collectComboItems(){
  const rows = document.querySelectorAll('#combo-items-container .component-row');
  const items = [];
  for (let r of rows){
    const prodSel = r.querySelector('select.combo-item-producto');
    const producto_id = prodSel ? (prodSel.value || '') : '';
    if (!producto_id) continue;

    const cantidad       = parseFloat((r.querySelector('.combo-item-cantidad')||{}).value || '1') || 1;
    const es_opcional    = (r.querySelector('.combo-item-opcional')||{}).checked ? 1 : 0;
    const grupo          = (r.querySelector('.combo-item-grupo')||{}).value?.trim() || '';
    const max_sel_raw    = (r.querySelector('.combo-item-max')||{}).value;
    const max_seleccion  = max_sel_raw === '' ? null : parseInt(max_sel_raw,10);
    const precio_extra   = parseFloat((r.querySelector('.combo-item-extra')||{}).value || '0') || 0;
    const orden          = parseInt((r.querySelector('.combo-item-orden')||{}).value || '1',10) || 1;

    items.push({
      productos_id: parseInt(producto_id,10),
      cantidad,
      es_opcional,
      grupo: grupo || null,
      max_seleccion: (max_seleccion===null ? null : max_seleccion),
      precio_extra,
      orden
    });
  }
  return items;
}

function guardarCombo(){
  const comboId = (document.getElementById('combo-id')||{}).value || '';

  // Obtener el ID del producto maestro según modo
  const productos_id =
    comboId
      ? (document.getElementById('combo-producto-hidden')?.value || '')       // ← edición (hidden)
      : (document.getElementById('combo-producto')?.value || '');             // ← creación (select)

  const activoSwitch = document.getElementById('combo-activo-switch');
  const activo = activoSwitch ? (activoSwitch.checked ? 1 : 0) : 1;

  if (!productos_id){ 
    showNotify && showNotify('warning','Validación','Seleccione el producto que representa el combo'); 
    return; 
  }

  const items = collectComboItems();
  if (!items.length){ 
    showNotify && showNotify('warning','Validación','Agregue al menos un ítem al combo'); 
    return; 
  }

  const payload = {
    productos_id: parseInt(productos_id,10),
    activo,
    items
  };
  let action = 'saveCombo';
  if (comboId){
    action = 'updateCombo';
    payload.combo_id = parseInt(comboId,10);
  }

  // Unifica envío: application/json (si tu PHP espera urlencoded, cambia abajo)
  fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`,{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action, data: payload })
  })
  .then(r=>r.json())
  .then(d=>{
    if (!d || !d.status){ 
      showNotify && showNotify('error','Error', d && d.message ? d.message : 'No se pudo guardar el combo'); 
      return; 
    }
    showNotify && showNotify('success','Éxito', comboId ? 'Combo actualizado' : 'Combo creado');
    if (modalComboEditor) modalComboEditor.style.display = 'none';
    if (modalCombos && modalCombos.style.display === 'block'){
      cargarCombos();
    }
  })
  .catch(()=> showNotify && showNotify('error','Error','Error al guardar combo'));
}

function eliminarCombo(comboId){
  if (!confirm('¿Eliminar este combo?')) return;
  const fd = new FormData();
  fd.append('action','deleteCombo');
  fd.append('combo_id', String(comboId));
  fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`,{
    method:'POST',
    body: fd
  })
  .then(r=>r.json())
  .then(d=>{
    if (!d || !d.status){ 
      showNotify && showNotify('error','Error', d && d.message ? d.message : 'No se pudo eliminar'); 
      return; 
    }
    showNotify && showNotify('success','Éxito','Combo eliminado');
    cargarCombos();
  })
  .catch(()=> showNotify && showNotify('error','Error','Error al eliminar combo'));
}

/* ========= Helpers ========= */

function findProductoById(id){
  id = parseInt(id,10);
  return productos.find(p => parseInt(p.productos_id,10) === id);
}

// Select2 global para el modal (evita “distorsión” y dropdown fuera)
function initSelect2All(){
  if (typeof $ === 'undefined' || !$.fn.select2) return;
  const $modal = $('#modal-combo-editor'); // contenedor del modal
  $('#combo-producto').select2({
    width: '100%',
    dropdownParent: $modal
  });
}

function initSelect2ForComboRow(row){
  if (typeof $ === 'undefined' || !$.fn.select2) return;
  const $modal = $('#modal-combo-editor');
  $(row).find('select.select2').select2({
    width: '100%',
    dropdownParent: $modal
  });
}

  // ===== Abrir selectores =====
  window.abrirEdicionProducto = abrirEdicionProducto;
  window.abrirEdicionCategoria = abrirEdicionCategoria;
  window.abrirEdicionCliente = abrirEdicionCliente;
  window.abrirEdicionMesa = abrirEdicionMesa;

  // ======= FIN JS =======

  // Restaurar layout al salir
  window.addEventListener("beforeunload", function () {
    if (navbarTop) navbarTop.style.display = "";
    if (navbarLateral) navbarLateral.style.display = "";
    document.body.classList.remove('vista-facturacion-restaurante');
  });

  // ==============================
  // BOTONES: VOLVER y CERRAR SESIÓN
  // ==============================

  const btnVolver = document.getElementById('btn-volver-dashboard');
  if (btnVolver) {
    btnVolver.addEventListener('click', function() {
      window.location.href = SERVERURL + 'dashboard/';
    });
  }

  const btnSalir = document.getElementById('btn-cerrar-sesion');
  if (btnSalir) {
    btnSalir.addEventListener('click', function(e) {
      e.preventDefault();
      const token = this.getAttribute('data-token') || '';
      swal({
        content: {
          element: "div",
          attributes: {
            innerHTML: `
              <h2 style="color:#f39c12;font-size:22px;margin-bottom:15px;">⚠️ ¿Está seguro?</h2>
              <p style="font-size:16px;color:#555;">Está a punto de cerrar su sesión. ¿Desea continuar?</p>
            `
          }
        },
        icon: "warning",
        buttons: true,
        dangerMode: true
      }).then((willExit) => {
        if (willExit) {
          salir(token);
        }
      });
    });

    function salir(token) {
      $.ajax({
        url: SERVERURL + 'login/cerrarSesion?token=' + encodeURIComponent(token),
        success: function(data) {
          if (String(data).trim() == '1') {
            window.location.href = SERVERURL + 'login/';
          } else {
            swal({
              content: {
                element: "div",
                attributes: {
                  innerHTML: `
                    <h2 style="color:#e74c3c;font-size:22px;margin-bottom:15px;">❌ Ocurrió un error inesperado</h2>
                    <p style="font-size:16px;color:#555;">Algo salió mal al cerrar la sesión. Por favor, intente de nuevo.</p>
                  `
                }
              },
              icon: "error",
              dangerMode: true,
              closeOnEsc: false,
              closeOnClickOutside: false
            });
          }
        },
        error: function() {
          swal({
            content: {
              element: "div",
              attributes: {
                innerHTML: `
                  <h2 style="color:#e74c3c;font-size:22px;margin-bottom:15px;">❌ Ocurrió un error inesperado</h2>
                  <p style="font-size:16px;color:#555;">Por favor, intente de nuevo.</p>
                `
              }
            },
            icon: "error",
            dangerMode: true,
              closeOnEsc: false,
              closeOnClickOutside: false
          });
        }
      });
    }
  }

});