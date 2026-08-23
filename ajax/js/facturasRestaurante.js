// ==============================
// facturasRestaurante.js (FULL + edición + uploader imagen + editar MESAS/CLIENTES + selección/edición cliente + GESTIÓN DE COMBOS MEJORADA)
// ==============================
(function(){
  // Evitar error si algún plugin de bootstrap intenta usar selectpicker
  if (typeof window.jQuery !== "undefined" && !jQuery.fn.selectpicker) {
    jQuery.fn.selectpicker = function(){ return this; };
  }

  const cb = document.getElementById('promo-usa-horario');
  const hi = document.getElementById('promo-hora-inicio');
  const hf = document.getElementById('promo-hora-fin');
  function toggle(){
    const on = cb.checked;
    [hi, hf].forEach(el => { el.disabled = !on; });
  }
  if (cb) { cb.addEventListener('change', toggle); toggle(); }  
})();

if (typeof SERVERURL === 'undefined') { 
  var SERVERURL = ''; 
}

var BASE = (typeof SERVERURL !== 'undefined' && SERVERURL) ? SERVERURL : (window.SERVERURL || '/');

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
  let promocionesVigentes = {};
  var cajaAbierta = false;
  var lastState = null;
  let servicioActual = 'mesa'; // 'mesa' | 'llevar'
  let PROMOS_VIGENTES = {};    // Mapa de promociones por producto
  let PROMOS_TICKER = null;    // Interval ID para el contador

  // Control de solicitudes para evitar bloqueos, solapamientos y dobles envíos
  let cajaCheckEnCurso = false;
  let contadorSarEnCurso = false;
  let guardandoFactura = false;
  const REQUEST_TIMEOUT_MS = 15000;

  function setButtonBusy(button, busy, busyText) {
    if (!button) return;
    if (busy) {
      if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      if (busyText) button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${busyText}`;
    } else {
      button.disabled = false;
      button.removeAttribute('aria-busy');
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
      }
    }
  }

  async function fetchWithTimeout(url, options, timeoutMs) {
    const opts = Object.assign({}, options || {});
    const timeout = Number(timeoutMs || REQUEST_TIMEOUT_MS);
    const controller = (typeof AbortController !== 'undefined' && !opts.signal) ? new AbortController() : null;
    if (controller) opts.signal = controller.signal;
    const timer = controller ? setTimeout(() => controller.abort(), timeout) : null;
    try {
      const response = await window.fetch(url, opts);
      if (response.status === 401) {
        try {
          const authData = await response.clone().json();
          if (authData && authData.redirect) window.location.href = authData.redirect;
        } catch (_) {}
      }
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return response;
    } catch (error) {
      if (error && error.name === 'AbortError') {
        const timeoutError = new Error('La solicitud tardó demasiado tiempo. Intente nuevamente.');
        timeoutError.name = 'TimeoutError';
        throw timeoutError;
      }
      throw error;
    } finally {
      if (timer) clearTimeout(timer);
    }
  }

  // Timeout por defecto para llamadas jQuery/$.post sin alterar las que ya definan uno propio.
  if (window.jQuery && typeof $.ajaxPrefilter === 'function') {
    $.ajaxPrefilter(function(options) {
      if (!options.timeout) options.timeout = REQUEST_TIMEOUT_MS;
    });
  }

  // Edición
  let editContext = {
    productoId: null,
    categoriaId: null,
    clienteId: null
  };

  // selección en el modal de clientes
  let selectedClienteForModal = null;

  // ====== Referencias DOM ======
  const servicioSwitch = document.getElementById('servicio-switch');
  const srvLlevar = document.getElementById('srv-llevar');
  const srvMesa   = document.getElementById('srv-mesa');
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
  const btnCobrarMesa = document.getElementById('btn-cobrar-mesa');
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
  const btnAddRegla         = document.getElementById('btn-add-regla');
  const scanCodigoInput     = document.getElementById('scan-codigo');

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

  const PRODUCT_TILE_SELECTOR = '[data-producto-id]';
  const MESA_TILE_SELECTOR = '[data-mesa-id]';  

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
    initHotkeys();
    // Estado inicial de cabecera
    setMesaSeleccionadaUI(null);
    setClienteInfoUI({ nombre:'Consumidor final', rtn:'' });
    initSelectsPromos();

    getCajero();
    verificarAperturaCaja();
  }

  // Refresco periódico sin duplicar solicitudes ni solapar ciclos previos
  setInterval(function () {
    verificarAperturaCaja();
  }, 5000);

   function getServicioTipo(){
      try {
        if (typeof servicioActual !== 'undefined' && (servicioActual === 'mesa' || servicioActual === 'llevar')) {
          return servicioActual;
        }
      } catch(e){}
      // Fallback por radios si existieran
      try {
        var rL = document.getElementById('srv-llevar');
        var rM = document.getElementById('srv-mesa');
        if (rL && rL.checked) return 'llevar';
        if (rM && rM.checked) return 'mesa';
      } catch(e){}
      return 'mesa';
   }

   function toJson(res){
    if (!res) return null;
    if (typeof res === 'object') return res; // ya viene parseado
    try {
      return JSON.parse(res);
    } catch(e) {
      // por si viene con warnings/HTML antes del JSON
      try {
        var m = String(res).match(/(\{[\s\S]*\}|\[[\s\S]*\])\s*$/);
        return m ? JSON.parse(m[1]) : null;
      } catch(e2){ return null; }
    }
  }
  
  function notifyErr(title, payload){
    var d = (payload && payload.responseJSON) || toJson(payload) || {};
    var msg = d.msg || d.message || (payload && payload.responseText) || (typeof payload === 'string' ? payload : 'No se pudo procesar');
    if (typeof showNotify === 'function') showNotify('error', title || 'Error', String(msg));
    else console.error(title || 'Error', String(msg));
  }  

  function isProductoDeCocina(prod) {
    return normalizeEstacion(prod) === 'cocina';
  }
  
  function getComandaProductIdsSet() {
    // lee cualquiera de las 3 variantes según cómo venga el item
    return new Set(
      comandaItems.map(it =>
        String(
          it.productos_id ||             // por si viene plano del backend
          it.producto_id  ||             // otra variante
          (it.producto && it.producto.id) // estructura actual { producto: {...} }
        )
      ).filter(Boolean)
    );
  }  

  function updateProductBadges() {
    const ids = getComandaProductIdsSet();
    document.querySelectorAll(PRODUCT_TILE_SELECTOR).forEach(el => {
      const pid = String(el.getAttribute('data-producto-id') || '');
      if (!pid) return;
      if (ids.has(pid)) {
        el.setAttribute('data-selected', '1');
      } else {
        el.removeAttribute('data-selected');
      }
    });
  }
  
  // ===== Botón contextual Guardar/Cobrar según servicio =====
  function etiquetaEstacion(clave){
    const cfg=window.REST_CONFIG||{};
    if(clave==='barra') return String(cfg.etiqueta_barra||'Barra').trim()||'Barra';
    return String(cfg.etiqueta_cocina||'Cocina').trim()||'Cocina';
  }

  function usaComandasOperacion(){
    return !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_comandas)===0);
  }

  function destinoComandaOperacion(){
    if(!usaComandasOperacion()) return 'ninguna';
    const v=String((window.REST_CONFIG&&window.REST_CONFIG.destino_comanda)||'pantalla').toLowerCase().trim();
    return ['pantalla','ticket','ambos'].includes(v)?v:'pantalla';
  }

  function momentoTicketOperacion(){
    const v=String((window.REST_CONFIG&&window.REST_CONFIG.momento_ticket)||'enviar').toLowerCase().trim();
    return v==='cobrar'?'cobrar':'enviar';
  }

  function debeEnviarPantallaComanda(){
    const d=destinoComandaOperacion();
    return usaComandasOperacion() && (d==='pantalla' || d==='ambos');
  }

  function debeImprimirTicketComanda(){
    const d=destinoComandaOperacion();
    return usaComandasOperacion() && (d==='ticket' || d==='ambos');
  }

  function aplicarEtiquetasOperacion(){
    const usaComandas=usaComandasOperacion();
    const e1=etiquetaEstacion('cocina');
    const e2=etiquetaEstacion('barra');
    const setText=(id,text)=>{ const el=document.getElementById(id); if(el) el.textContent=text; };

    setText('label-fil-est-cocina',e1); setText('label-fil-est-barra',e2);
    setText('label-cat-est-cocina',e1); setText('label-cat-est-barra',e2);
    setText('label-prod-est-cocina',e1); setText('label-prod-est-barra',e2);

    // Los grupos/estaciones son exclusivos del flujo de comandas.
    const filtroEstacion=document.getElementById('filtro-estacion');
    if(filtroEstacion) filtroEstacion.style.display=usaComandas?'':'none';
    const catEstWrap=document.getElementById('cat-estacion-wrap');
    if(catEstWrap) catEstWrap.style.display=usaComandas?'':'none';
    const prodEstWrap=document.getElementById('prod-estacion-wrap');
    if(prodEstWrap) prodEstWrap.style.display=usaComandas?'':'none';
    const configStations=document.getElementById('config-grupos-operacion');
    if(configStations) configStations.style.display=usaComandas?'':'none';
    setText('texto-btn-mostrar-comanda',usaComandas?'Ver Comanda':'Ver detalle');
    setText('texto-btn-ticket',usaComandas?'Ticket comanda':'Ticket de venta');
    setText('titulo-ticket-operacion',usaComandas?'Ticket de comanda':'Ticket de venta');

    const titulo=document.getElementById('titulo-panel-comanda');
    if(titulo) titulo.innerHTML=usaComandas
      ? '<i class="fas fa-clipboard-list"></i> Comanda'
      : '<i class="fas fa-file-invoice"></i> Detalle de venta';

    const btnMob=document.getElementById('btn-mostrar-comanda');
    if(btnMob) btnMob.title=usaComandas?'Ver comanda':'Ver detalle de la venta';
    if(btnImprimir) btnImprimir.title=usaComandas?'Vista e impresión del ticket de comanda':'Vista e impresión del ticket de venta';

    const helpTitulo=document.getElementById('help-titulo-operacion');
    if(helpTitulo) helpTitulo.innerHTML=usaComandas?'<i class="fas fa-receipt"></i> Comanda':'<i class="fas fa-file-invoice"></i> Venta';
    setText('help-accion-principal',usaComandas?'Cobrar / enviar a preparación':'Cobrar venta');
    setText('help-ticket-label',usaComandas?'Ticket de comanda':'Ticket de venta');
    setText('help-limpiar-label',usaComandas?'Limpiar comanda':'Limpiar detalle');
    setText('help-ver-panel-label',usaComandas?'Ver Productos/Comanda':'Ver Productos/Detalle');
    const flujo=document.getElementById('help-flujo-operacion');
    if(flujo){
      const ul=flujo.querySelector('.help-bullets');
      if(ul) ul.innerHTML=usaComandas
        ? `<li><b>Para llevar:</b> puede cobrar de inmediato o usar <b>Guardar cuenta</b> para continuarla más tarde.</li><li><b>En mesa:</b> seleccione la mesa, envíe a preparación y vuelva a abrirla desde la tarjeta de mesa o desde <b>Cuentas abiertas</b>.</li><li>Los productos de <b>${escapeHtml(e1)}</b> y <b>${escapeHtml(e2)}</b> se separan por su grupo/ruta.</li><li>La factura fiscal se muestra después del pago. <b>Ticket de comanda</b> es un documento interno.</li>`
        : `<li><b>Venta directa:</b> agregue productos y cobre de inmediato o use <b>Guardar cuenta</b> para continuarla más tarde.</li><li><b>Cuentas abiertas</b> permite recuperar una venta pendiente sin crear otra factura.</li><li>Use las <b>categorías</b> para organizar y filtrar el catálogo. Los grupos de preparación no se muestran en este modo.</li><li>La factura fiscal se muestra después del pago. <b>Ticket de venta</b> es un documento interno y no sustituye la factura.</li>`;
    }
  }

  function updateAccionPrincipalUI(){
    const el = document.getElementById('btn-guardar');
    if (!el) return;
    const btnGuardarCuenta = document.getElementById('btn-guardar-cuenta');
    const btnCuentas = document.getElementById('btn-cuentas-abiertas');
    const tieneItems = Array.isArray(comandaItems) && comandaItems.length > 0;
    const tieneFactura = !!(facturaActual && (facturaActual.id || facturaActual.factura_id || facturaActual.facturas_id));
    const usarMesas = !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_mesas) === 0);
    const usarComandas = usaComandasOperacion();

    el.classList.remove('btn-success','btn-warning','btn-danger');

    if (!usarMesas || servicioActual === 'llevar') {
      el.innerHTML = '<i class="fas fa-cash-register"></i> Cobrar';
      el.classList.add('btn-success');
      el.title = 'Facturar y abrir el método de pago';
      if (btnCobrarMesa) btnCobrarMesa.style.display = 'none';
      if (btnGuardarCuenta) {
        btnGuardarCuenta.style.display = tieneItems ? '' : 'none';
        btnGuardarCuenta.innerHTML = tieneFactura ? '<i class="fas fa-save"></i> Actualizar cuenta' : '<i class="fas fa-bookmark"></i> Guardar cuenta';
      }
    } else {
      if (usarComandas) {
        el.innerHTML = tieneFactura
          ? '<i class="fas fa-sync-alt"></i> Actualizar cocina'
          : '<i class="fas fa-fire"></i> Enviar a cocina';
        el.title = tieneFactura ? 'Guardar cambios de la cuenta abierta y actualizar la comanda' : 'Guardar la cuenta y enviarla a preparación';
      } else {
        el.innerHTML = tieneFactura
          ? '<i class="fas fa-save"></i> Actualizar cuenta'
          : '<i class="fas fa-bookmark"></i> Guardar cuenta';
        el.title = tieneFactura ? 'Actualizar la cuenta abierta de esta mesa' : 'Guardar la cuenta abierta de esta mesa';
      }
      el.classList.add('btn-warning');
      if (btnCobrarMesa) btnCobrarMesa.style.display = (mesaSeleccionada && tieneItems) ? '' : 'none';
      if (btnGuardarCuenta) {
        btnGuardarCuenta.style.display = tieneItems ? '' : 'none';
        btnGuardarCuenta.innerHTML = tieneFactura ? '<i class="fas fa-save"></i> Guardar cambios' : '<i class="fas fa-bookmark"></i> Guardar cuenta';
        btnGuardarCuenta.title = 'Guardar la cuenta sin reenviar productos a preparación';
      }
    }

    if (btnImprimir) {
      btnImprimir.style.display = tieneItems ? '' : 'none';
      btnImprimir.disabled = !tieneItems;
    }
    if (btnCerrar) btnCerrar.style.display = tieneFactura ? '' : 'none';
    if (btnCuentas) btnCuentas.style.display = '';
  }

  // Click único del botón
  // ============= REEMPLAZO EXACTO =============
  function onAccionPrincipalClick(){
    try {
      // Delegamos todo (confirm + notificaciones) a guardarFactura()
      guardarFactura();
    } catch (e) {
      // log silencioso para no duplicar notificaciones
    }
    // evita cualquier acción extra del botón (por si es <button type="submit">)
    return false;
  }
  // =========== FIN REEMPLAZO EXACTO ===========  

  // ===========================================================
  //  UI: Botón Apertura/Cierre + Modal
  // ===========================================================
  function validateForm(formId) {
      const form = document.getElementById(formId);
      if (!form) {
          return false;
      }
      
      form.classList.remove('was-validated');
      
      if (!form.checkValidity()) {
          form.classList.add('was-validated');
          form.reportValidity();
          
          // Enfocar el primer campo inválido
          const firstInvalid = form.querySelector(':invalid');
          if (firstInvalid) {
              firstInvalid.focus();
          }
          
          return false;
      }
      
      return true;
  }

  function limpiarValidacionFormulario(formId) {
      const form = document.getElementById(formId);
      if (!form) return;
      
      // Remover clase de validación
      form.classList.remove('was-validated');
      
      // Limpiar estilos de campos
      const campos = form.querySelectorAll('.form-control, .form-select');
      campos.forEach(campo => {
          campo.classList.remove('is-valid', 'is-invalid');
      });
      
      // Ocultar mensajes de error
      const mensajesError = form.querySelectorAll('.invalid-feedback');
      mensajesError.forEach(mensaje => {
          mensaje.style.display = 'none';
      });
  }

  // --- Helper para abrir modales con BS4 (jQuery) y BS5 (todas las versiones)
  function getCajero() {
    var url = BASE + 'core/getCajero.php';

    $.ajax({
      type: 'POST',
      url: url,
      dataType: 'json'
    })
    .done(function(datos){
      // Soporta array [id, nombre] o objeto {colaboradores_id, colaborador}
      var id  = Array.isArray(datos) ? datos[0] : (datos.colaboradores_id || '');
      var nom = Array.isArray(datos) ? datos[1] : (datos.colaborador || '');
      if (!id || !nom) return;
      // Apertura de caja (como ya lo tenías)
      $('#formAperturaCaja #colaboradores_id_apertura').val(id);
      $('#formAperturaCaja #usuario_apertura').val(nom);
      $('#cajero-nombre').html('Cajero: ' + nom);
    })
    .fail(function(xhr){

    });
  }

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
      return;
  }
  
  // Configuración base para todos los Select2
  const baseConfig = {
      width: '100%',
      theme: 'bootstrap'
  };
  
  // Función optimizada para inicializar Select2 en modales
  function initSelect2WithModal(selector, modalSelector, config) {
      const $element = $(selector);
      const $modal = $(modalSelector);
      
      if ($element.length && $modal.length) {
          // Destruir si ya estaba inicializado
          if ($element.data('select2')) {
              try {
                  $element.select2('destroy');
              } catch (e) {

              }
          }
          
          // Inicializar con la modal como parent
          try {
              $element.select2({
                  ...config,
                  dropdownParent: $modal
              });
              
              // Forzar actualización visual
              setTimeout(() => {
                  $element.trigger('change.select2');
              }, 50);
              
              return $element;
          } catch (e) {

          }
      }
      
      return null;
  }

  // Selects del modal de Mesa
  initSelect2WithModal('#ubicacion-mesa', '#modal-mesa', {
      ...baseConfig,
      minimumResultsForSearch: 0
  });
  
  initSelect2WithModal('#estado-mesa', '#modal-mesa', {
      ...baseConfig,
      minimumResultsForSearch: 0
  });
  
  // Select de categoría del Producto
  initSelect2WithModal('#prod-categoria', '#modal-producto', {
      ...baseConfig,
      allowClear: true,
      placeholder: $('#prod-categoria').data('placeholder') || ''
  }).on('change', actualizarProdEstacionInfo);
  
  // Select del editor de combo (el más importante)
  initSelect2WithModal('#combo-producto', '#modal-combo-editor', {
      ...baseConfig,
      allowClear: true,
      placeholder: $('#combo-producto').data('placeholder') || 'Selecciona el producto combo'
  });
  
  // Inicializar otros Select2 que no están en modales
  $('select.select2').not('#ubicacion-mesa, #estado-mesa, #prod-categoria, #combo-producto').each(function() {
      if (!$(this).data('select2')) {
          $(this).select2(baseConfig);
      }
  });
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
    // tomar la mejor fuente, recortar y bajar a minúsculas
    let est = (cat.estacion || cat.station || cat.ruta || cat.tipo || '')
      .toString()
      .trim()          // <- importante para "cocina " / " barra"
      .toLowerCase();
  
    // alias comunes por si llegan de BD/servicios
    if (est === 'kitchen' || est === 'cuisine') est = 'cocina';
    if (est === 'bar' || est === 'barra de tragos') est = 'barra';
  
    // compatibilidad con flags antiguos
    if (!est) {
      if (String(cat.es_cocina || '').trim() === '1') est = 'cocina';
      else if (String(cat.es_barra || '').trim() === '1') est = 'barra';
      else est = 'ninguna';
    }
  
    if (!['cocina','barra','ninguna'].includes(est)) est = 'ninguna';
    return est;
  }  

  function estacionSeleccionadaUI(){
    // En modo genérico los grupos Cocina/Barra no forman parte de la experiencia.
    if (!usaComandasOperacion()) return 'todas';
    const r = document.querySelector('#filtro-estacion input[name="filEst"]:checked');
    return r ? r.value : 'todas';
  }

  function prodEstacionSeleccionadaUI() {
    const wrap = document.getElementById('prod-estacion');
    const r = wrap ? wrap.querySelector('input[name="prodEstacion"]:checked') : null;
    return r ? r.value : 'cocina';
  }

  function fillProdCategoriaOptionsByEstacion(preselectId){
    const { selCat } = getProdControls();
    if (!selCat) return Promise.resolve();

    if (!Array.isArray(categorias) || !categorias.length){
      return ensureCategoriasReady().then(()=> fillProdCategoriaOptionsByEstacion(preselectId));
    }

    // La estación es una propiedad operativa del PRODUCTO. La categoría organiza el catálogo,
    // pero no obliga al producto a cocinarse en la misma estación.
    const list = categorias.slice().sort((a,b)=>String(a.nombre||'').localeCompare(String(b.nombre||''),'es'));
    selCat.innerHTML = '<option value="">Seleccione una categoría…</option>' +
      list.map(c => `<option value="${c.id}" data-estacion="${normalizeEstacion(c)}">${escapeHtml(c.nombre)}</option>`).join('');

    if (preselectId) selCat.value = String(preselectId);
    reinitSelect2ProdCategoria();
    actualizarProdEstacionInfo();
    return Promise.resolve();
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
      val.textContent = est === 'ninguna' ? 'Sin estación definida' : (est.charAt(0).toUpperCase() + est.slice(1));
      wrap.title = 'La categoría puede tener una estación sugerida, pero la estación del producto se guarda de forma independiente.';
    } else {
      wrap.style.display = 'none';
      val.textContent = '—';
    }
  }

  // ================= Header helpers =================
  function setMesaSeleccionadaUI(nombreMesa) {
    // Buscar el nodo SIEMPRE, así no depende de variables de otro ámbito
    var el = document.getElementById('mesa-seleccionada');
    if (!el) return; // si el span no existe aún, no rompas

    if (!nombreMesa) {
      el.innerHTML = '<i class="fas fa-table"></i> No seleccionada';
    } else {
      el.innerHTML = '<i class="fas fa-table"></i> Mesa: ' + nombreMesa;
    }
  }

  // Asume que tienes: const clienteInfoElement = document.getElementById('cliente-info');
  function setClienteInfoUI({ clientes_id = 1, nombre = 'Consumidor final', rtn = '' } = {}) {
    if (!clienteInfoElement) return;
  
    // Asegurar la estructura (icono + .cli-datos con nombre y RTN)
    let datos = clienteInfoElement.querySelector('.cli-datos');
    if (!datos) {
      clienteInfoElement.innerHTML = `
        <input type="hidden" class="cli-id" id="clientes_id" name="clientes_id" value="0">
        <span class="cli-datos">
          <span class="cli-nombre-wrap"><i class="fas fa-user"></i><span class="cli-nombre"></span></span>
          <small class="cli-rtn-wrap is-hidden"><i class="fas fa-id-card"></i><span class="cli-rtn-label">RTN</span><span class="cli-rtn"></span></small>
        </span>
      `;
      datos = clienteInfoElement.querySelector('.cli-datos');
    }
  
    const elId = clienteInfoElement.querySelector('.cli-id');
    const elNombre = clienteInfoElement.querySelector('.cli-nombre');
    const elWrap = clienteInfoElement.querySelector('.cli-rtn-wrap');
    const elRtn = clienteInfoElement.querySelector('.cli-rtn');
  
    // CORRECCIÓN: Usar value en lugar de .val()
    elId.value = clientes_id;
    
    // Setear nombre
    elNombre.textContent = (nombre || 'Consumidor final').trim();
  
    // Mostrar/ocultar RTN debajo del nombre
    const hasRtn = !!(rtn && String(rtn).trim());
    if (hasRtn) {
      elRtn.textContent = String(rtn).trim();
      elWrap.classList.remove('is-hidden');
    } else {
      elRtn.textContent = '';
      elWrap.classList.add('is-hidden');
    }
  }

  /**
   * Habilita/deshabilita la UI según el estado de la caja
   * @param {boolean} abierta
   */
  function toggleUIForCajaAbierta(abierta) {
    var disable = !abierta;

    // Bloquear botones principales
    $('#btn-guardar').prop('disabled', disable);
    $('#btn-cerrar').prop('disabled', disable);
    
    // Bloquear otros elementos de la UI (manteniendo tu código original)
    $('#agregar-producto').prop('disabled', disable);
    $('#procesar-factura-top, #procesar-factura-bottom').prop('disabled', disable);
    $('#cancelar-factura-top, #cancelar-factura-bottom').prop('disabled', disable);

    $('#cliente-select, #vendedor-select, #producto-select, #cantidad, #descuento, #codigo-barra, #notas')
      .prop('disabled', disable);

    // Refrescar selects bootstrap-select
    if ($('#cliente-select').hasClass('selectpicker')) $('#cliente-select').selectpicker('refresh');
    if ($('#vendedor-select').hasClass('selectpicker')) $('#vendedor-select').selectpicker('refresh');
    if ($('#producto-select').hasClass('selectpicker')) $('#producto-select').selectpicker('refresh');

    // Cambiar texto y estilo del botón Guardar cuando la caja está cerrada
    if (disable) {
      $('#btn-guardar')
        .removeClass('btn-success btn-warning')
        .addClass('btn-danger')
        .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
    } else {
      $('#btn-guardar').removeClass('btn-danger');
      updateAccionPrincipalUI(); // <- usamos nuestro rótulo contextual
    }
  }

// ==============================
// ATAJOS DE TECLADO – SOLO combinaciones (evita disparos al escribir)
// Windows/Linux: Ctrl; Mac: Cmd (metaKey)
// Algunas acciones usan Alt para no chocar con el navegador
// ==============================
function initHotkeys(){
  const clickFirst = (selectors=[])=>{
    for (const sel of selectors){
      const el = document.querySelector(sel);
      if (el && !el.disabled){ el.click(); return true; }
    }
    return false;
  };
  const focusFirst = (selectors=[])=>{
    for (const sel of selectors){
      const el = document.querySelector(sel);
      if (el){ el.focus(); el.select && el.select(); return true; }
    }
    return false;
  };

  // Mapa de combinaciones seguras
  // ctrlOrMeta = Ctrl (Win/Linux) o Cmd (Mac)
  // alt = true para combos que podrían chocar con atajos del navegador
  const HK = [
    // Comanda
    { key:'g', ctrlOrMeta:true, alt:false, type:'click', targets:['#btn-guardar'] },
    { key:'i', ctrlOrMeta:true, alt:false, type:'click', targets:['#btn-imprimir'] },
    { key:'l', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-limpiar'] },
    { key:'x', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-cerrar'] },
    { key:'f', ctrlOrMeta:true, alt:true,  type:'focus', targets:['#buscar-producto'] },
    { key:'v', ctrlOrMeta:true, alt:true,  type:'toggleView' },

    // Gestión
    { key:'m', ctrlOrMeta:true, alt:false, type:'click', targets:['#btn-nueva-mesa'] },
    { key:'c', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-cambiar-cliente'] },
    { key:'r', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-nuevo-cliente-rapido', '#btn-nuevo-cliente'] },
    { key:'p', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-nuevo-producto'] },
    { key:'k', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-nueva-categoria'] },
    { key:'b', ctrlOrMeta:true, alt:true,  type:'click', targets:['#btn-gestionar-combos'] },
  ];

  document.addEventListener('keydown', (e)=>{
    // Requiere SIEMPRE ctrl/meta: sin modificadores, no hacemos nada.
    const ctrlOrMeta = !!e.ctrlKey || !!e.metaKey;
    if (!ctrlOrMeta) return;

    const alt = !!e.altKey;
    const k   = (e.key || '').toLowerCase();

    // Si estás escribiendo en inputs/selects/textarea, SÍ permitimos combos (porque llevan Ctrl/Cmd)
    // Esto NO interfiere con texto normal (no hay letra suelta).
    const match = HK.find(h => h.key === k && h.ctrlOrMeta === true && (!!h.alt) === alt);
    if (!match) return;

    // Evita que el navegador se “robe” el atajo (ej. Ctrl+P, Ctrl+F, etc.)
    // OJO: Nosotros solo usamos 'i' (imprimir), NO 'p'; y usamos Alt para F, P, etc., así que es seguro.
    e.preventDefault();

    if (match.type === 'click'){ clickFirst(match.targets); return; }
    if (match.type === 'focus'){ focusFirst(match.targets); return; }
    if (match.type === 'toggleView'){
      const panelProductos = document.getElementById('panel-productos');
      const panelComanda   = document.getElementById('panel-comanda');
      const btnVerProd     = document.getElementById('btn-mostrar-productos');
      const btnVerCom      = document.getElementById('btn-mostrar-comanda');

      const productosVisibles = panelProductos && panelProductos.style.display !== 'none';
      if (productosVisibles){
        if (btnVerCom) btnVerCom.click();
        else {
          if (panelProductos) panelProductos.style.display = 'none';
          if (panelComanda)   panelComanda.style.display   = '';
        }
      } else {
        if (btnVerProd) btnVerProd.click();
        else {
          if (panelProductos) panelProductos.style.display = '';
          if (panelComanda)   panelComanda.style.display   = 'none';
        }
      }
    }
  });

  // Abrir modal de ayuda con clic (friendly para táctil)
  const btnHelp = document.getElementById('btn-help');
  if (btnHelp){
    btnHelp.addEventListener('click', ()=>{
      const modal = document.getElementById('modal-help');
      if (modal) modal.style.display = 'block';
    });
  }
}   
  // ===========================================================
  //  AJAX de backend
  // ===========================================================

  // 1) Consulta si la caja está abierta (1) o cerrada (2) SIN bloquear la interfaz
  function getConsultarAperturaCaja() {
    return $.ajax({
      type: 'POST',
      url: BASE + 'core/getAperturaCajaUsuario.php',
      timeout: REQUEST_TIMEOUT_MS
    }).then(function (r) {
      try {
        var data = (typeof r === 'string') ? JSON.parse(r) : r;
        return Number(Array.isArray(data) ? data[0] : (data && (data.estado || data[0]))) || 2;
      } catch (e) {
        return 2;
      }
    }, function () {
      return 2;
    });
  }

  // 2) Contador SAR, protegido contra solicitudes solapadas
  function getTotalFacturasDisponibles() {
    if (contadorSarEnCurso) return Promise.resolve(null);
    contadorSarEnCurso = true;

    return $.ajax({
      type: 'POST',
      url: BASE + 'core/getTotalFacturasDisponibles.php?_=' + Date.now(),
      dataType: 'json',
      timeout: REQUEST_TIMEOUT_MS
    })
      .done(function (datos) {
        if (!datos || typeof datos.facturasPendientes === 'undefined') {
          showErrorState();
          return;
        }
        facturasDisponibles = Number(datos.facturasPendientes) || 0;
        renderCounter(datos);
      })
      .fail(function () {
        showErrorState();
      })
      .always(function () {
        contadorSarEnCurso = false;
      });
  }

  function showErrorState(){ if (typeof paintCounterError === 'function') paintCounterError(); } 

  // Abre modal para APERTURA
  function formAperturaBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Aperturar Caja");
    $('#open_caja').show();
    $('#close_caja').hide();
    $('#formAperturaCaja #monto_apertura_grupo').show();

    // Ruta en JS puro (sin PHP). BASE ya se usa en el archivo.
    var _BASE = (typeof BASE !== 'undefined' && BASE)
                ? BASE
                : ((typeof SERVERURL !== 'undefined' && SERVERURL) ? SERVERURL : '/');

    $('#formAperturaCaja')
      .attr({ 'data-form': 'save', 'action': _BASE + 'ajax/addAperturaCajaAjax.php' });

    $('#modal_apertura_caja').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });

    // Evitar múltiples binds al reabrir: enfoca una sola vez
    $('#modal_apertura_caja').one('shown.bs.modal', function () {
      $('#monto_apertura').trigger('focus');
    });
  }

  // Abre modal para CIERRE
  function formCierreBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
    $('#open_caja').hide();
    $('#close_caja').show();
    $('#formAperturaCaja #monto_apertura_grupo').hide();

    var _BASE = (typeof BASE !== 'undefined' && BASE)
                ? BASE
                : ((typeof SERVERURL !== 'undefined' && SERVERURL) ? SERVERURL : '/');

    $('#formAperturaCaja')
      .attr({ 'data-form': 'save', 'action': _BASE + 'ajax/addCierreCajaFacturasAjax.php' });

    $('#modal_apertura_caja').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });

    // (Opcional) Enfocar el primer campo visible del modal al abrir
    $('#modal_apertura_caja').one('shown.bs.modal', function () {
      $(this).find('input,select,textarea,button:enabled:visible').first().trigger('focus');
    });
  }

  // ===== Verificación de estado de caja y bloqueo de UI =====
  async function verificarAperturaCaja() {
    if (cajaCheckEnCurso) return;
    cajaCheckEnCurso = true;
    try {
      var estado = Number(await getConsultarAperturaCaja());
      cajaAbierta = (estado === 1);

      // Actualizar botón de apertura/cierre
      var $btn = $('#btn-apertura-caja');
      if ($btn.length) {
        if (cajaAbierta) {
          $btn.removeClass('btn-primary').addClass('btn-warning')
            .html('<i class="fas fa-lock mr-1"></i> Cerrar Caja')
            .data('mode', 'cerrar');
        } else {
          $btn.removeClass('btn-warning').addClass('btn-primary')
            .html('<i class="fas fa-lock-open mr-1"></i> Aperturar Caja')
            .data('mode', 'abrir');
        }
      }

      toggleUIForCajaAbierta(cajaAbierta);
      await getTotalFacturasDisponibles();
    } catch (e) {
      showErrorState();
    } finally {
      cajaCheckEnCurso = false;
    }
  }

  // Si el modal se cierra, revalida
  $(document).on('hidden.bs.modal', '#modal_apertura_caja', function () {
    verificarAperturaCaja();
  });

  // ===========================================================
  //  UI: Contador SAR
  // ===========================================================
  function renderCounter(datos) {
    var count = Number(datos.facturasPendientes) || 0;
    var daysLeft = parseInt(datos.contador, 10);
    var fechaLimite = datos.fechaLimite;

    var state = getCurrentState(count, daysLeft, fechaLimite);
    var cfg = getStateConfig(state, count, daysLeft, fechaLimite);

    var $counter = $('#factura-counter');
    if (!$counter.length) return;

    // icono
    if ($counter.find('i').length) {
      $counter.find('i').first().attr('class', cfg.icon);
    } else {
      $counter.prepend('<i class="' + cfg.icon + '"></i> ');
    }

    // valor + hint
    var $value = $('#factura-disponibles');
    if (!$value.length) {
      $counter.append('<span id="factura-disponibles" class="counter-value"></span>');
      $value = $('#factura-disponibles');
    }
    $value.text(cfg.mainText);

    $counter.find('.counter-hint').remove();
    if (cfg.hintHtml) {
      $counter.append('<span class="counter-hint">' + cfg.hintHtml + '</span>');
    }

    // clases de estado
    $counter
      .removeClass(function (i, c) { return (c.match(/\bcounter-\S+/g) || []).join(' '); })
      .addClass(cfg.class)
      .attr('title', cfg.mainText);

    // animación leve
    $counter.removeClass('state-change');
    void $counter[0].offsetWidth;
    $counter.addClass('state-change');

    lastState = state;

    updateButtonsState(count, fechaLimite, daysLeft);
  }

  function paintCounterError() {
    var $counter = $('#factura-counter');
    if (!$counter.length) return;
    if ($counter.find('i').length) $counter.find('i').first().attr('class', 'fas fa-exclamation-circle');
    var $value = $('#factura-disponibles');
    if (!$value.length) {
      $counter.append('<span id="factura-disponibles" class="counter-value"></span>');
      $value = $('#factura-disponibles');
    }
    $value.text('Error al cargar');
    $counter
      .removeClass(function (i, c) { return (c.match(/\bcounter-\S+/g) || []).join(' '); })
      .addClass('counter-danger');
  }

  function getCurrentState(count, daysLeft, fechaLimite) {
    if (!fechaLimite || String(fechaLimite).trim() === 'Sin definir') return 'no-config';
    if (count < 0) return 'blocked';
    if (daysLeft < 0) return 'expired';
    if (daysLeft <= 5) return 'danger';
    if (count <= 9) return 'danger';
    if (count <= 30) return 'warning';
    return 'normal';
  }

  /**
   * Devuelve la config visual del contador SAR.
   * @param {('normal'|'warning'|'danger'|'expired'|'blocked'|'no-config'|string)} state
   * @param {number} count        Cantidad de facturas disponibles
   * @param {?number} daysLeft    Días para vencer (negativo = vencido). Puede ser null/undefined.
   * @param {?string|Date} fechaLimite  (Opcional) fecha de límite, solo informativo
   */
  function getStateConfig(state, count, daysLeft, fechaLimite) {
    // Sanitizar entradas
    var nCount = Number.isFinite(Number(count)) ? Number(count) : 0;
    var nDays  = (typeof daysLeft === 'number' && Number.isFinite(daysLeft)) ? daysLeft : null;

    // Texto principal
    var main = nCount.toLocaleString('es-HN') + ' facturas';

    // Hint dinámico (solo cuando faltan <= 5 días)
    var hint = '';
    if (nDays !== null && nDays <= 5) {
      if (nDays < 0)       hint = 'Autorizaciones vencidas';
      else if (nDays === 0) hint = 'Vencen hoy';
      else                  hint = 'Vencen en ' + nDays + ' día(s)';
    }

    // (Opcional) agrega fecha límite al hint si la tienes y aplica
    if (hint && fechaLimite) {
      try {
        var d = (fechaLimite instanceof Date) ? fechaLimite : new Date(String(fechaLimite).replace(' ', 'T'));
        if (!Number.isNaN(d.getTime())) {
          var fechaTxt = d.toLocaleDateString('es-HN', { year:'numeric', month:'2-digit', day:'2-digit' });
          hint += ' · límite: ' + fechaTxt;
        }
      } catch (_e) { /* noop */ }
    }

    var hintHtml = hint ? ('<small class="d-block">' + hint + '</small>') : '';

    // Enlaces reutilizables
    var linkHtml = function (texto) {
      return '<small class="d-block"><a href="' + BASE + 'secuencia/" target="_blank" rel="noopener" class="text-white text-decoration-underline">' + texto + '</a></small>';
    };
    var linkCfg = linkHtml('Configurar');
    var linkUpd = linkHtml('Actualizar');

    // Tabla de estados
    var configs = {
      normal:     { icon:'fas fa-file-invoice',         class:'counter-normal',     mainText: main,                         hintHtml: hintHtml },
      warning:    { icon:'fas fa-hourglass-half',       class:'counter-warning',    mainText: main,                         hintHtml: hintHtml },
      danger:     { icon:'fas fa-exclamation-triangle', class:'counter-danger',     mainText: main,                         hintHtml: hintHtml },
      expired:    { icon:'fas fa-calendar-times',       class:'counter-expired',    mainText:'Autorizaciones vencidas',     hintHtml: linkUpd  },
      blocked:    { icon:'fas fa-ban',                  class:'counter-blocked',    mainText:'Límite alcanzado',            hintHtml: linkCfg  },
      'no-config':{ icon:'fas fa-calendar-times',       class:'counter-no-config',  mainText:'Sin fecha límite',            hintHtml: linkCfg  }
    };

    return configs[state] || configs.normal;
  }

  // Habilitar / deshabilitar acciones según caja + SAR
  function updateButtonsState(count, fechaLimite, daysLeft) {
    var vencido = daysLeft < 0;
    var sarOK = (count > 0) && !!fechaLimite && String(fechaLimite).trim() !== 'Sin definir' && !vencido;

    // El ticket operativo no depende de SAR ni de la apertura de caja.
    updateAccionPrincipalUI();
  }

  function initSelectsPromos(){
    try{
      $('#promo-tipo').select2({width:'100%', dropdownParent:$('#modal-promocion')});
      $('#promo-aplica-a').select2({width:'100%', dropdownParent:$('#modal-promocion')});
      $('#pp-promocion').select2({width:'100%', dropdownParent:$('#modal-promo-productos')});
      $('#pc-promocion').select2({width:'100%', dropdownParent:$('#modal-promo-categorias')});
      // Los multiselect conservan su valor para el backend, pero su UI visible son tarjetas.
      try { $('#pp-productos').select2('destroy'); } catch(_) {}
      try { $('#pc-categorias').select2('destroy'); } catch(_) {}
    }catch(e){}
  }

  function setupEventListeners() {
    // Delegación: selección/deselección de mesa sin ocuparla por el simple clic
    document.addEventListener('click', function(e){
      const tile = e.target.closest(MESA_TILE_SELECTOR);
      if (!tile) return;
      if (e.target.closest('.mesa-actions')) return;

      const mesaId = parseInt(tile.getAttribute('data-mesa-id'), 10);
      const mesa = mesas.find(m => Number(m.id || m.mesa_id) === mesaId);
      if (!mesa) return;

      const estado = String(mesa.estado || tile.getAttribute('data-estado') || 'disponible').toLowerCase();
      if (estado === 'mantenimiento') {
        showAlert('warning','Mesa no disponible','La mesa se encuentra en mantenimiento.');
        return;
      }

      const actualId = mesaSeleccionada && Number(mesaSeleccionada.id || mesaSeleccionada.mesa_id || mesaSeleccionada || 0);
      if (actualId === mesaId) {
        // Quitar selección NO libera ni cancela la cuenta guardada; solo limpia la sesión visual.
        const teniaCuenta = !!(facturaActual && (facturaActual.id || facturaActual.factura_id || facturaActual.facturas_id));
        mesaSeleccionada = null;
        facturaActual = null;
        if (teniaCuenta) {
          comandaItems = [];
          actualizarComandaUI();
          clienteSeleccionado = { id: 1, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
          pintarClienteInfoHeader();
          if (observacionesTextarea) observacionesTextarea.value = '';
        }
        setServicioTipo('llevar');
        setMesaSeleccionadaUI(null);
        highlightMesaSeleccionada();
        if (facturaTitle) facturaTitle.innerHTML = usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
        if (btnImprimir) btnImprimir.disabled = true;
        updateAccionPrincipalUI();
        return;
      }

      // Si es reserva, precargar el cliente reservado; la mesa se vuelve ocupada hasta guardar la cuenta.
      if (estado === 'reservada' && mesa.reserva && mesa.reserva.clientes_id) {
        clienteSeleccionado = {
          id: Number(mesa.reserva.clientes_id),
          nombre: mesa.reserva.cliente_nombre || 'Cliente reservado',
          identificacion: mesa.reserva.cliente_rtn || ''
        };
        pintarClienteInfoHeader();
      }

      seleccionarMesa(mesa);
      updateAccionPrincipalUI();
    });

    // Click del botón único (abre el modal correcto)
    $(document).on('click', '#btn-apertura-caja', function () {
      var mode = $(this).data('mode');
      if (mode === 'abrir') {
        formAperturaBill();
      } else {
        formCierreBill();
      }
    });
  
      // Dropdown Gestionar
    $(document).on('click','#btn-gestionar-acciones',function(e){
      e.stopPropagation();
      $('#gestionar-menu').toggleClass('show');
    });
    $(document).on('click',function(){ $('#gestionar-menu').removeClass('show'); });
    $(document).on('click','#gestionar-menu button',function(e){
      var t = $(this).data('target');
      $('#gestionar-menu').removeClass('show');
      if(!t) return; // configuración tiene su propio listener protegido
      e.preventDefault(); e.stopPropagation();
      var label = ($(this).text() || 'Administrar').trim();
      autorizarGestionRestaurante(label, function(){
        REST_AUTH_BYPASS = true;
        try { $(t).trigger('click'); } finally { setTimeout(function(){ REST_AUTH_BYPASS=false; },0); }
      });
    });

    // ====== Apertura y cierre genérico de modales .rs-modal
    $(document).on('click','[data-close]',function(){
      var sel = $(this).data('close'); $(sel).hide();
    });

    // ====== Promos: abrir modales
    $('#btn-gestionar-promos').on('click', async function(){
      $('#modal-promociones-list').show();
      await cargarPromocionesListado();
      setTimeout(function() {
        $('#modal-promociones-list').find('input, select, textarea, button').filter(':visible').first().focus();
      }, 50);
    });

    $('#btn-nueva-promocion, #btn-abrir-nueva-promocion').on('click', function(){
      // reset simple
      $('#form-promocion')[0].reset();
      $('#promo-id').val('');
      $('#titulo-modal-promocion').text('Nueva promoción');
      // limpiar checks de días
      $('.promo-dia').prop('checked', false);

      // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
      limpiarValidacionFormulario('form-promocion');

      $('#modal-promocion').show();
      setTimeout(function() {
        initSelectsPromos();
        // Enfocar el primer input después de inicializar los selects
        $('#promo-nombre').focus();
      }, 0);
    });

    $('#btn-asignar-promo-productos').on('click', async function(){
      $('#modal-promo-productos').show();
      renderListLoading($('#pp-listado'), 'Cargando productos asignados…');
      try {
        initSelectsPromos();
        await llenarSelectsPromoProductos();
        setTimeout(function() { try { $('#pp-promocion').select2('focus'); } catch(_) {} }, 0);
      } catch (e) {
        renderListError($('#pp-listado'), e.message || 'No se pudo cargar la información');
        showAlert('error','Error', e.message || 'No se pudo cargar la información de promociones');
      }
    });

    $('#btn-asignar-promo-categorias').on('click', async function(){
      $('#modal-promo-categorias').show();
      renderListLoading($('#pc-listado'), 'Cargando categorías asignadas…');
      try {
        initSelectsPromos();
        await llenarSelectsPromoCategorias();
        setTimeout(function() { try { $('#pc-promocion').select2('focus'); } catch(_) {} }, 0);
      } catch (e) {
        renderListError($('#pc-listado'), e.message || 'No se pudo cargar la información');
        showAlert('error','Error', e.message || 'No se pudo cargar la información de promociones');
      }
    });

    /* ============================================================
    * ========== PROMOS - EVENTOS (dentro de setupEventListeners) =
    * ============================================================ */

    // Guardar promoción (Crear/Actualizar)
    $(document).on('click', '#btn-guardar-promocion', async function(){
      const btn = this;
      if (btn.disabled) return;
      try{
        if (!validateForm('form-promocion')) return;
        const data = recogerFormPromocion();
        const accion = data.promo_id ? 'updatePromocion' : 'savePromocion';
        setButtonBusy(btn, true, data.promo_id ? 'Actualizando…' : 'Guardando…');

        const res = await apiPromos(accion, data);
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo guardar'); }
        showAlert('success','Éxito', data.promo_id ? 'Promoción actualizada' : 'Promoción creada');
        $('#modal-promocion').hide();

        await Promise.allSettled([
          llenarPromosSelect($('#pp-promocion')),
          llenarPromosSelect($('#pc-promocion')),
          cargarPromocionesListado()
        ]);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al guardar la promoción');
      } finally {
        setButtonBusy(btn, false);
      }
    });

    $(document).on('click', '.promo-edit', function(){
      const id = parseInt($(this).data('id'), 10);
      if (id) abrirEdicionPromocion(id);
    });

    if (srvMesa) {
      srvMesa.addEventListener('change', () => setServicioTipo('mesa'));
    }

    if (srvLlevar) {
      srvLlevar.addEventListener('change', () => setServicioTipo('llevar'));
    }

    // Cambiar promo en "Asignar productos": carga la lista asignada
    $(document).on('change', '#pp-promocion', async function(){
      const pid = $(this).val();
      $('#pp-listado').html('');
      if (pid) await cargarAsignadosProductos(pid);
      else { $('#pp-productos').val(null).trigger('change'); renderPromoProductosPicker(window.__promoProductosPicker || []); syncPromoPickerCount('producto'); }
    });

    // Guardar asignación productos -> promo
    $(document).on('click', '#btn-guardar-promo-productos', async function(){
      const btn = this;
      if (btn.disabled) return;
      const promo_id = $('#pp-promocion').val();
      const productos_ids = ($('#pp-productos').val() || []).map(v => parseInt(v,10)).filter(Boolean);
      if(!promo_id){ showAlert('warning','Atención','Seleccione una promoción'); return; }
      if(!productos_ids.length){ showAlert('warning','Atención','Seleccione al menos un producto'); return; }
      try{
        setButtonBusy(btn, true, 'Guardando…');
        const res = await apiPromos('assignPromoProductos', { promo_id, productos_ids });
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo asignar'); }
        showAlert('success','Éxito','Productos asignados');
        $('#pp-productos').val(null).trigger('change');
        renderPromoProductosPicker(window.__promoProductosPicker || []); syncPromoPickerCount('producto');
        await cargarAsignadosProductos(promo_id);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al asignar productos');
      } finally {
        setButtonBusy(btn, false);
      }
    });

    // Quitar producto de una promo (delegado)
    $(document).on('click', '.pp-del', async function(){
      const producto_id = $(this).data('pid');
      const promo_id    = $(this).data('promo');
      if(!promo_id || !producto_id) return;
      showConfirm('Quitar producto','¿Desea quitar este producto de la promoción?', async ()=>{
        try{
          const res = await apiPromos('removePromoProducto', { promo_id, producto_id });
          if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo quitar'); }
          await cargarAsignadosProductos(promo_id);
        }catch(e){
          showAlert('error','Error', e.message || 'Error al quitar producto');
        }
      });
    });

    // Cambiar promo en "Asignar categorías": carga la lista asignada
    $(document).on('change', '#pc-promocion', async function(){
      const pid = $(this).val();
      $('#pc-listado').html('');
      if (pid) await cargarAsignadosCategorias(pid);
      else { $('#pc-categorias').val(null).trigger('change'); renderPromoCategoriasPicker(window.__promoCategoriasPicker || []); syncPromoPickerCount('categoria'); }
    });

    // Guardar asignación categorías -> promo
    $(document).on('click', '#btn-guardar-promo-categorias', async function(){
      const btn = this;
      if (btn.disabled) return;
      const promo_id = $('#pc-promocion').val();
      const categorias_ids = ($('#pc-categorias').val() || []).map(v => parseInt(v,10)).filter(Boolean);
      if(!promo_id){ showAlert('warning','Atención','Seleccione una promoción'); return; }
      if(!categorias_ids.length){ showAlert('warning','Atención','Seleccione al menos una categoría'); return; }
      try{
        setButtonBusy(btn, true, 'Guardando…');
        const res = await apiPromos('assignPromoCategorias', { promo_id, categorias_ids });
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo asignar'); }
        showAlert('success','Éxito','Categorías asignadas');
        $('#pc-categorias').val(null).trigger('change');
        renderPromoCategoriasPicker(window.__promoCategoriasPicker || []); syncPromoPickerCount('categoria');
        await cargarAsignadosCategorias(promo_id);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al asignar categorías');
      } finally {
        setButtonBusy(btn, false);
      }
    });

    // Quitar categoría de una promo (delegado)
    $(document).on('click', '.pc-del', async function(){
      const categoria_id = $(this).data('cid');
      const promo_id     = $(this).data('promo');
      if(!promo_id || !categoria_id) return;
      showConfirm('Quitar categoría','¿Desea quitar esta categoría de la promoción?', async ()=>{
        try{
          const res = await apiPromos('removePromoCategoria', { promo_id, categoria_id });
          if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo quitar'); }
          await cargarAsignadosCategorias(promo_id);
        }catch(e){
          showAlert('error','Error', e.message || 'Error al quitar categoría');
        }
      });
    });

    $(document).off('input.promoPickerProductos','#pp-buscar-producto').on('input.promoPickerProductos','#pp-buscar-producto',function(){ renderPromoProductosPicker(window.__promoProductosPicker || [], this.value); });
    $(document).off('input.promoPickerCategorias','#pc-buscar-categoria').on('input.promoPickerCategorias','#pc-buscar-categoria',function(){ renderPromoCategoriasPicker(window.__promoCategoriasPicker || [], this.value); });

    // ====== Función genérica para enfocar el primer input en modales
    $(document).on('shown', '.rs-modal', function() {
      var $modal = $(this);
      // Pequeña demora para asegurar que el modal esté completamente visible
      setTimeout(function() {
        // Buscar el primer input, select o textarea que no esté oculto o deshabilitado
        var $firstInput = $modal.find('input:visible:not(:disabled), select:visible:not(:disabled), textarea:visible:not(:disabled)').first();
        if ($firstInput.length) {
          // Si es un select2, usar el método específico de select2
          if ($firstInput.hasClass('select2-hidden-accessible')) {
            $firstInput.select2('focus');
          } else {
            $firstInput.focus();
          }
        }
      }, 100);
    });

    // ====== Añadir esta funcionalidad a otros modales existentes
    // Modal para nueva/editar mesa
    $(document).on('click', '#btn-nueva-mesa', function() {
      $('#modal-mesa').show();      
      setTimeout(function() {
        $('#numero-mesa').focus();
      }, 100);
    });

    // Modal para seleccionar cliente
    $(document).on('click', '#btn-cambiar-cliente', function() {
      $('#modal-cliente').show();
      setTimeout(function() {
        $('#buscar-cliente').focus();
      }, 100);
    });

    // Modal para nuevo/editar cliente
    $(document).on('click', '#btn-nuevo-cliente', function() {
      $('#modal-nuevo-cliente').show();
      setTimeout(function() {
        $('#cli-nombre').focus();
      }, 100);
    });

    // Modal para nueva categoría
    $(document).on('click', '#btn-nueva-categoria', function() {
      $('#modal-categoria').show();
      setTimeout(function() {
        $('#cat-nombre').focus();
      }, 100);
    });

    // Modal para nuevo producto
    $(document).on('click', '#btn-nuevo-producto', function() {
      // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
      limpiarValidacionFormulario('form-producto');

      $('#modal-producto').show();
      setTimeout(function() {
        $('#prod-nombre').focus();
      }, 100);
    });

    // Modal para combos
    $(document).on('click', '#btn-gestionar-combos', function() {
      $('#modal-combos').show();
      setTimeout(function() {
        $('#modal-combos').find('input, select, textarea').first().focus();
      }, 100);
    });

    // Modal de ayuda
    $(document).on('click', '#btn-help', function() {
      $('#modal-help').show();
      // No es necesario enfocar en el modal de ayuda ya que es principalmente de lectura
    });
    // ===== Búsqueda productos =====
    if (buscarProductoInput) {
      // filtra por nombre/desc al escribir
      buscarProductoInput.addEventListener('input', function () {
        filtrarProductos(this.value);
      });
      // Enter: intenta como código primero; si no, filtra
      buscarProductoInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (!tryBarcodeAdd(this.value)) {
            filtrarProductos(this.value);
          } else {
            this.value = '';
          }
        }
      });
    }

    if (btnBuscar) {
      btnBuscar.addEventListener('click', function () {
        const t = buscarProductoInput ? buscarProductoInput.value : '';
        if (!tryBarcodeAdd(t)) {
          filtrarProductos(t);
        } else if (buscarProductoInput) {
          buscarProductoInput.value = '';
        }
      });
    }

    // Campo dedicado al escáner (recomendado)
    if (scanCodigoInput) {
      scanCodigoInput.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
          e.preventDefault();
          const ok = tryBarcodeAdd(this.value);
          this.value = '';
          if (!ok) showAlert('warning','No encontrado','Código no corresponde a ningún producto');
        }
      });
    }

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
    if (btnGuardar) btnGuardar.addEventListener('click', onAccionPrincipalClick);

    // Ticket de comanda (la factura fiscal se imprime al finalizar el pago oficial)
    if (btnImprimir) {
      btnImprimir.addEventListener('click', function () {
        abrirTicketComanda();
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
        autorizarGestionRestaurante('Editar cliente', ()=>abrirEdicionCliente(mapearClienteObjeto(selectedClienteForModal)), selectedClienteForModal && (selectedClienteForModal.clientes_id || selectedClienteForModal.id || ''));
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

    // Filtro Estación (Todas/Cocina/Barra) — refresca categorías Y productos
    document.querySelectorAll('#filtro-estacion input[name="filEst"]').forEach(radio=>{
      radio.addEventListener('change', ()=> {
        renderizarCategorias();
        filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');
      });
    });

    // Segmentado estación del modal de Producto.
    // La estación pertenece al PRODUCTO; cambiar Cocina/Barra NO debe borrar la categoría.
    document.querySelectorAll('#prod-estacion input[name="prodEstacion"]').forEach(radio=>{
      radio.addEventListener('change', ()=> {
        const actual = (document.getElementById('prod-categoria') || {}).value || '';
        fillProdCategoriaOptionsByEstacion(actual);
      });
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
    
      setCatEstacion('cocina'); // ← **este es el fix**
    
      if (modalCategoria) modalCategoria.style.display='block';

      // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
      limpiarValidacionFormulario('form-categoria');

      setTimeout(()=>inp && inp.focus(),10);
    });    

    if (btnNuevoProducto) btnNuevoProducto.addEventListener('click', ()=>{
      editContext.productoId = null;
      const { inpNombre, inpDesc, inpPrecio, chkISV1, chkISV2 } = getProdControls();
    
      if (inpNombre) inpNombre.value='';
      if (inpDesc)   inpDesc.value='';
      if (inpPrecio) {
        inpPrecio.value = '';
        inpPrecio.placeholder = '0.00';
      }
      if (chkISV1)   chkISV1.checked=false;
      if (chkISV2)   chkISV2.checked=false;
    
      const hid = document.getElementById('prod-id');
      if (hid) hid.value = '';
    
      const t = document.getElementById('titulo-modal-producto');
      if (t) t.textContent = 'Nuevo Producto';
    
      prepararModalProductoISV();
      resetProductoImagen();
      initProductoImageUpload();
    
      // 1) Selecciona REALMENTE Cocina
      setProdEstacion('cocina');
    
      // 2) Si por timing aún no hay categorías, espera y llena; si ya hay, no pasa nada
      ensureCategoriasReady().then(()=> fillProdCategoriaOptionsByEstacion());
    
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

    if (btnAddRegla) btnAddRegla.addEventListener('click', ()=>{
      const cur = collectReglasCategoria();
      cur.push({ categoria_id: (categorias[0]?.id || null), max_seleccion: 1 });
      renderReglasCategoria(cur);
    });

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
            identificacion: (nuevoCliente.identificacion || nuevoCliente.rtn || '').trim()
          };
          pintarClienteInfoHeader();
        }
      });
    };    
  }

  /* ============================================================
  * ===== PROMOS - FUNCIONES (fuera de setupEventListeners) =====
  *    PÉGALAS en el mismo archivo, dentro del scope de
  *    DOMContentLoaded, por ejemplo después de tus helpers de
  *    productos/categorías y antes de "/* ========= Helpers =========
  * ============================================================ */
  // URL de AJAX
  const AJAX_PROMOS = BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php';

  // Post JSON con manejo de error
  async function apiPromos(action, data){
    const r = await fetchWithTimeout(AJAX_PROMOS, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action, data })
    });
    let j=null; try{ j = await r.json(); }catch(_){}
    return j;
  }

  // ======= PROMOS: estado y utilidades =======
  const nfHNL = new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const fmtHNL = n => nfHNL.format(Number(n || 0));

  /**
   * Carga las promociones vigentes desde el servidor
   */
  function fetchPromosVigentesProductos() {
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadPromocionesVigentesProductos'
    })
    .then(r => r.json())
    .then(data => {
      if (data.status && data.promociones) {
        PROMOS_VIGENTES = data.promociones;
      }
      return data;
    })
    .catch(error => {
      return {status: false};
    });
  }

  /**
   * Calcula el precio con descuento de promoción
   */
  function precioConPromo(producto, promo) {
    const base = Number(producto.precio_venta || 0);
    if (!promo) return { base, promo: null };

    if (promo.tipo_descuento === 'PORC') {
      const p = Math.max(0, base * (1 - (Number(promo.valor) / 100)));
      return { base, promo: p };
    }
    if (promo.tipo_descuento === 'MONTO') {
      const p = Math.max(0, base - Number(promo.valor));
      return { base, promo: p };
    }
    return { base, promo: null };
  }

  /**
   * Formatea el tiempo restante para una promoción
   */
  function countdownLabel(finISO) {
    try {
      const fin = new Date(finISO.replace(' ', 'T'));
      if (Number.isNaN(fin.getTime())) return '';
      
      const diff = fin - new Date();
      if (diff <= 0) return 'expirada';
      
      const s = Math.floor(diff / 1000);
      const d = Math.floor(s / 86400);
      const h = Math.floor((s % 86400) / 3600);
      const m = Math.floor((s % 3600) / 60);
      
      if (d > 0) return `${d}d ${h}h`;
      if (h > 0) return `${h}h ${m}m`;
      return `${m}m`;
    } catch (e) {
      return '';
    }
  }

  /**
   * Crea el HTML del badge de promoción
   */
  function buildPromoBadge(promo) {
    const tipo = promo.tipo_descuento; // PORC | MONTO
    const fmt = (n) => new Intl.NumberFormat('es-HN', { 
      minimumFractionDigits: 2, 
      maximumFractionDigits: 2 
    }).format(n);
    
    const etiqueta = (tipo === 'PORC') ? 
      `-${promo.valor}%` : 
      `-L ${fmt(promo.valor)}`;
    
    const fin = promo.fin_iso || `${promo.fecha_fin} ${promo.hora_fin || '23:59:59'}`;
    const tooltip = `${promo.nombre || 'Promoción'} · finaliza ${fin}`;

    return `
      <div class="badge-promocion" data-tipo="${tipo}" data-tooltip="${tooltip}">
        <i class="fas fa-bolt"></i>
        <span class="badge-promo-text">${etiqueta}</span>
        <span class="badge-promo-count" data-ends="${fin}">${countdownLabel(fin)}</span>
      </div>
    `;
  }

  /**
   * Inicia el contador que actualiza los badges de promoción cada 30 segundos
   */
  function startPromosTicker() {
    // Detener ticker anterior si existe
    stopPromosTicker();
    
    PROMOS_TICKER = setInterval(() => {
      // Actualizar contadores de promociones
      document.querySelectorAll('.badge-promo-count').forEach(el => {
        const fin = el.getAttribute('data-ends');
        if (fin) {
          el.textContent = countdownLabel(fin);
        }
      });
    }, 30000); // 30 segundos
  }

  /**
   * Detiene el contador de promociones
   */
  function stopPromosTicker() {
    if (PROMOS_TICKER) {
      clearInterval(PROMOS_TICKER);
      PROMOS_TICKER = null;
    }
  }

  // Construye el payload desde el form del modal de promoción
  function recogerFormPromocion(){
    const promo_id   = ($('#promo-id').val() || '').trim();
    const empresa_id = parseInt($('#promo-empresa-id').val(),10) || 1;
    const nombre     = ($('#promo-nombre').val() || '').trim();
    const descripcion= ($('#promo-descripcion').val() || '').trim();
    const tipo_descuento = $('#promo-tipo').val();
    const valor      = parseFloat($('#promo-valor').val() || '0') || 0;

    const fecha_inicio = $('#promo-fecha-inicio').val(); // YYYY-MM-DD
    const fecha_fin    = $('#promo-fecha-fin').val();

    // Horario diario opcional
    const usaHorario   = $('#promo-usa-horario').is(':checked');
    const hora_inicio  = usaHorario ? ($('#promo-hora-inicio').val() || null) : null;
    const hora_fin     = usaHorario ? ($('#promo-hora-fin').val() || null) : null;

    // Días de la semana: SET en MySQL => "mon,tue,wed"
    const dias_semana = $('.promo-dia:checked').map(function(){ return $(this).val(); }).get().join(',') || null;

    const prioridad   = parseInt($('#promo-prioridad').val(),10) || 0;
    const aplica_a    = $('#promo-aplica-a').val();  // 'PRODUCTO' | 'CATEGORIA' | 'TODOS'
    const acumula_con_mayoreo = $('#promo-acumula').is(':checked') ? 1 : 0;
    const estado      = $('#promo-estado').is(':checked') ? 1 : 0;

    if(!nombre){ throw new Error('El nombre es obligatorio'); }
    if(!fecha_inicio || !fecha_fin){ throw new Error('Seleccione fecha inicio y fin'); }

    return {
      promo_id: promo_id ? parseInt(promo_id,10) : null,
      empresa_id, nombre, descripcion,
      tipo_descuento, valor,
      fecha_inicio, fecha_fin,
      hora_inicio, hora_fin,
      dias_semana,
      prioridad, aplica_a, acumula_con_mayoreo, estado
    };
  }

  let promocionesListado = [];

  function renderListLoading($container, text){
    if (!$container || !$container.length) return;
    $container.html(`<div class="rs-list-state"><i class="fas fa-spinner fa-spin"></i><span>${escapeHtml(text || 'Cargando…')}</span></div>`);
  }

  function renderListError($container, text){
    if (!$container || !$container.length) return;
    $container.html(`<div class="rs-list-state rs-list-state-error"><i class="fas fa-exclamation-circle"></i><span>${escapeHtml(text || 'No se pudo cargar')}</span></div>`);
  }

  function fmtPromoValor(p){
    return String(p.tipo_descuento || '').toUpperCase() === 'PORC'
      ? `${Number(p.valor || 0).toFixed(2)}%`
      : `L ${fmtHNL(p.valor || 0)}`;
  }

  async function cargarPromocionesListado(){
    const $list = $('#promos-rows');
    if (!$list.length) return;
    renderListLoading($list, 'Cargando promociones…');
    try {
      const res = await apiPromos('loadPromociones', {});
      if (!res || !res.status) throw new Error((res && res.message) || 'No se pudieron cargar las promociones');
      promocionesListado = Array.isArray(res.promociones) ? res.promociones : [];
      $list.empty();
      if (!promocionesListado.length) {
        $list.html('<div class="rs-list-state"><i class="fas fa-tags"></i><span>No hay promociones registradas</span></div>');
        return;
      }
      promocionesListado.forEach(p => {
        const horario = (p.hora_inicio || p.hora_fin) ? `${escapeHtml(p.hora_inicio || '00:00')} - ${escapeHtml(p.hora_fin || '23:59')}` : 'Todo el día';
        const estado = Number(p.estado) === 1 ? 'Activa' : 'Inactiva';
        const estadoClass = Number(p.estado) === 1 ? 'is-active' : 'is-inactive';
        $list.append(`
          <div class="promo-list-item" data-promo-id="${Number(p.promo_id)}">
            <div class="promo-list-main">
              <div class="promo-list-title">${escapeHtml(p.nombre || '')}</div>
              <div class="promo-list-desc">${escapeHtml(p.descripcion || 'Sin descripción')}</div>
            </div>
            <div class="promo-list-field"><span>Descuento</span><strong>${escapeHtml(fmtPromoValor(p))}</strong></div>
            <div class="promo-list-field"><span>Vigencia</span><strong>${escapeHtml(p.fecha_inicio || '')} → ${escapeHtml(p.fecha_fin || '')}</strong><small>${horario}</small></div>
            <div class="promo-list-field"><span>Aplica a</span><strong>${escapeHtml(p.aplica_a || '')}</strong></div>
            <div class="promo-list-field"><span>Prioridad</span><strong>${Number(p.prioridad || 0)}</strong></div>
            <div class="promo-list-field"><span>Estado</span><strong class="promo-state ${estadoClass}">${estado}</strong></div>
            <div class="promo-list-actions">
              <button type="button" class="btn btn-sm btn-primary promo-edit" data-id="${Number(p.promo_id)}"><i class="fas fa-edit"></i> Editar</button>
            </div>
          </div>`);
      });
    } catch (e) {
      renderListError($list, e.message || 'No se pudieron cargar las promociones');
    }
  }

  function abrirEdicionPromocion(id){
    const p = promocionesListado.find(x => Number(x.promo_id) === Number(id));
    if (!p) { showAlert('warning','Atención','No se encontró la promoción seleccionada'); return; }
    const form = document.getElementById('form-promocion');
    if (form) form.classList.remove('was-validated');
    $('#promo-id').val(p.promo_id || '');
    $('#promo-nombre').val(p.nombre || '');
    $('#promo-descripcion').val(p.descripcion || '');
    $('#promo-tipo').val(p.tipo_descuento || 'PORC').trigger('change');
    $('#promo-valor').val(Number(p.valor || 0).toFixed(2));
    $('#promo-fecha-inicio').val(p.fecha_inicio || '');
    $('#promo-fecha-fin').val(p.fecha_fin || '');
    const usaHorario = !!(p.hora_inicio || p.hora_fin);
    $('#promo-usa-horario').prop('checked', usaHorario).trigger('change');
    $('#promo-hora-inicio').val((p.hora_inicio || '').substring(0,5));
    $('#promo-hora-fin').val((p.hora_fin || '').substring(0,5));
    $('.promo-dia').prop('checked', false);
    String(p.dias_semana || '').split(',').filter(Boolean).forEach(d => $(`.promo-dia[value="${d}"]`).prop('checked', true));
    $('#promo-prioridad').val(Number(p.prioridad || 0));
    $('#promo-aplica-a').val(p.aplica_a || 'PRODUCTO').trigger('change');
    $('#promo-acumula').prop('checked', Number(p.acumula_con_mayoreo) === 1);
    $('#promo-estado').prop('checked', Number(p.estado) === 1);
    $('#titulo-modal-promocion').text('Editar promoción');
    $('#modal-promocion').show();
    setTimeout(() => $('#promo-nombre').trigger('focus'), 0);
  }

  /* ---------- SELECTS (llenado) ---------- */
  async function llenarPromosSelect($sel){
    const res = await apiPromos('loadPromocionesMin', {});
    const list = (res && res.promociones) ? res.promociones : [];
    const selected = String($sel.val() || '');
    $sel.empty().append(`<option value=""></option>`);
    list.forEach(p => {
      const opt = new Option(`${p.nombre}`, p.promo_id, false, selected===String(p.promo_id));
      $sel.append(opt);
    });
    $sel.trigger('change');
  }

  function syncPromoPickerCount(kind){
    const sel = document.getElementById(kind === 'producto' ? 'pp-productos' : 'pc-categorias');
    const count = sel ? Array.from(sel.selectedOptions || []).length : 0;
    const out = document.getElementById(kind === 'producto' ? 'pp-seleccion-count' : 'pc-seleccion-count');
    if (out) out.textContent = `${count} ${kind === 'producto' ? (count===1?'seleccionado':'seleccionados') : (count===1?'seleccionada':'seleccionadas')}`;
  }

  function togglePromoPickerSelection(selectId, value, card, kind){
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const opt = Array.from(sel.options).find(o => String(o.value) === String(value));
    if (!opt) return;
    opt.selected = !opt.selected;
    card.classList.toggle('selected', opt.selected);
    card.setAttribute('aria-pressed', opt.selected ? 'true' : 'false');
    $(sel).trigger('change');
    syncPromoPickerCount(kind);
  }

  function renderPromoProductosPicker(list, query=''){
    const grid = document.getElementById('pp-productos-grid');
    if (!grid) return;
    const q = String(query||'').trim().toLowerCase();
    const sel = document.getElementById('pp-productos');
    const selected = new Set(sel ? Array.from(sel.selectedOptions).map(o=>String(o.value)) : []);
    const items = (list||[]).filter(p => !q || String(p.nombre||'').toLowerCase().includes(q) || String(p.barCode||'').toLowerCase().includes(q));
    if (!items.length){ grid.innerHTML='<div class="promo-picker-empty"><i class="fas fa-search"></i><span>No encontramos productos con ese filtro.</span></div>'; return; }
    grid.innerHTML = items.map(p=>{
      const id=String(p.productos_id); const isSel=selected.has(id);
      const img=p.file_name ? `${SERVERURL}vistas/plantilla/img/products/${encodeURIComponent(p.file_name)}` : `${SERVERURL}vistas/plantilla/img/products/image_preview.png`;
      const est=normalizeEstacion(p);
      return `<button type="button" class="promo-product-card ${isSel?'selected':''}" data-picker-product="${id}" aria-pressed="${isSel?'true':'false'}">
        <span class="promo-card-check"><i class="fas fa-check"></i></span>
        <span class="promo-product-image"><img src="${img}" alt="${escapeHtml(p.nombre||'Producto')}" loading="lazy"></span>
        <span class="promo-product-info"><strong>${escapeHtml(p.nombre||'')}</strong><small>${escapeHtml(p.barCode||'Sin código')}</small>${usaComandasOperacion()?`<em><i class="${est==='barra'?'fas fa-glass-martini-alt':'fas fa-utensils'}"></i> ${escapeHtml(etiquetaEstacion(est==='barra'?'barra':'cocina'))}</em>`:''}</span>
      </button>`;
    }).join('');
    grid.querySelectorAll('[data-picker-product]').forEach(card=> card.addEventListener('click', ()=>togglePromoPickerSelection('pp-productos',card.dataset.pickerProduct,card,'producto')));
  }

  function renderPromoCategoriasPicker(list, query=''){
    const grid = document.getElementById('pc-categorias-grid');
    if (!grid) return;
    const q=String(query||'').trim().toLowerCase();
    const sel=document.getElementById('pc-categorias');
    const selected=new Set(sel ? Array.from(sel.selectedOptions).map(o=>String(o.value)) : []);
    const items=(list||[]).filter(c=>!q || String(c.nombre||'').toLowerCase().includes(q));
    if(!items.length){ grid.innerHTML='<div class="promo-picker-empty"><i class="fas fa-search"></i><span>No encontramos categorías con ese filtro.</span></div>'; return; }
    grid.innerHTML=items.map(c=>{ const id=String(c.id||c.categoria_id); const on=selected.has(id); const est=normalizeEstacion(c); const sub=usaComandasOperacion()?`<small>${est==='ninguna'?'Sin estación sugerida':escapeHtml(etiquetaEstacion(est==='barra'?'barra':'cocina'))}</small>`:''; return `<button type="button" class="promo-category-card ${on?'selected':''}" data-picker-category="${id}" aria-pressed="${on?'true':'false'}"><span class="promo-card-check"><i class="fas fa-check"></i></span><span class="promo-category-icon"><i class="fas fa-layer-group"></i></span><span><strong>${escapeHtml(c.nombre||'')}</strong>${sub}</span></button>`; }).join('');
    grid.querySelectorAll('[data-picker-category]').forEach(card=>card.addEventListener('click',()=>togglePromoPickerSelection('pc-categorias',card.dataset.pickerCategory,card,'categoria')));
  }

  async function llenarProductosSelect($sel){
    const res = await apiPromos('loadProductos', {});
    const list = (res && res.productos) ? res.productos : [];
    window.__promoProductosPicker = list;
    $sel.empty();
    list.forEach(p=>$sel.append(new Option(p.nombre || `Producto ${p.productos_id}`, p.productos_id, false, false)));
    $sel.trigger('change');
    renderPromoProductosPicker(list, (document.getElementById('pp-buscar-producto')||{}).value || '');
    syncPromoPickerCount('producto');
  }

  async function llenarCategoriasSelect($sel){
    const res = await apiPromos('loadCategorias', { estacion:'todas' });
    const list = (res && res.categorias) ? res.categorias : [];
    window.__promoCategoriasPicker = list;
    $sel.empty();
    list.forEach(c=>$sel.append(new Option(c.nombre || `Categoría ${c.id}`, c.id, false, false)));
    $sel.trigger('change');
    renderPromoCategoriasPicker(list, (document.getElementById('pc-buscar-categoria')||{}).value || '');
    syncPromoPickerCount('categoria');
  }

  // Helpers para apertura de modales de asignación
  async function llenarSelectsPromoProductos(){
    await Promise.all([
      llenarPromosSelect($('#pp-promocion')),
      llenarProductosSelect($('#pp-productos'))
    ]);
    // Si la promo ya está seleccionada, cargar asignados
    const pid = $('#pp-promocion').val();
    if (pid) await cargarAsignadosProductos(pid);
  }

  async function llenarSelectsPromoCategorias(){
    await Promise.all([
      llenarPromosSelect($('#pc-promocion')),
      llenarCategoriasSelect($('#pc-categorias'))
    ]);
    const pid = $('#pc-promocion').val();
    if (pid) await cargarAsignadosCategorias(pid);
  }

  /* ---------- Listados asignados + render ---------- */
  async function cargarAsignadosProductos(promo_id){
    const $list = $('#pp-listado');
    renderListLoading($list, 'Cargando productos asignados…');
    try {
      const res = await apiPromos('loadPromoProductos', { promo_id });
      if (!res || !res.status) throw new Error((res && res.message) || 'No se pudieron cargar los productos');
      const items = Array.isArray(res.items) ? res.items : [];
      const selectedIds = items.map(x=>String(x.producto_id || x.productos_id || '')).filter(Boolean);
      $('#pp-productos').val(selectedIds).trigger('change');
      renderPromoProductosPicker(window.__promoProductosPicker || [], (document.getElementById('pp-buscar-producto')||{}).value || '');
      syncPromoPickerCount('producto');
      $list.empty();
      if(!items.length){
        $list.html('<div class="rs-list-state"><i class="fas fa-box-open"></i><span>Sin productos asignados</span></div>');
        return;
      }
      items.forEach(row=>{
        $list.append(`
          <div class="assignment-list-item">
            <div class="assignment-main"><strong>${escapeHtml(row.nombre || '')}</strong><small>${escapeHtml(row.barCode || 'Sin código')}</small></div>
            <button type="button" class="btn btn-sm btn-danger pp-del" data-promo="${Number(promo_id)}" data-pid="${Number(row.producto_id)}" title="Quitar producto"><i class="fas fa-trash"></i></button>
          </div>`);
      });
    } catch (e) {
      renderListError($list, e.message || 'No se pudieron cargar los productos');
    }
  }

  async function cargarAsignadosCategorias(promo_id){
    const $list = $('#pc-listado');
    renderListLoading($list, 'Cargando categorías asignadas…');
    try {
      const res = await apiPromos('loadPromoCategorias', { promo_id });
      if (!res || !res.status) throw new Error((res && res.message) || 'No se pudieron cargar las categorías');
      const items = Array.isArray(res.items) ? res.items : [];
      const selectedIds = items.map(x=>String(x.categoria_id || '')).filter(Boolean);
      $('#pc-categorias').val(selectedIds).trigger('change');
      renderPromoCategoriasPicker(window.__promoCategoriasPicker || [], (document.getElementById('pc-buscar-categoria')||{}).value || '');
      syncPromoPickerCount('categoria');
      $list.empty();
      if(!items.length){
        $list.html('<div class="rs-list-state"><i class="fas fa-layer-group"></i><span>Sin categorías asignadas</span></div>');
        return;
      }
      items.forEach(row=>{
        $list.append(`
          <div class="assignment-list-item">
            <div class="assignment-main"><strong>${escapeHtml(row.nombre || '')}</strong></div>
            <button type="button" class="btn btn-sm btn-danger pc-del" data-promo="${Number(promo_id)}" data-cid="${Number(row.categoria_id)}" title="Quitar categoría"><i class="fas fa-trash"></i></button>
          </div>`);
      });
    } catch (e) {
      renderListError($list, e.message || 'No se pudieron cargar las categorías');
    }
  }

// Utilidad: escapar HTML (si ya tienes escapeHtml, esta usa la tuya)
function escapeHtml(s){ return String(s ?? '').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  // Mantener cierre por fondo bloqueado, pero permitir cerrar con ESC cuando corresponda
  function bloquearCierrePorFondoYEsc(){
    [modalMesa, modalCliente, modalCategoria, modalProducto, modalNuevoCliente, modalCombos, modalComboEditor].forEach(m => {
      if (!m) return;
      m.addEventListener('click', (ev)=>{ if (ev.target === m) { ev.stopPropagation(); ev.preventDefault(); } });
    });

    window.addEventListener('keydown', (ev)=>{
      if (ev.key !== 'Escape') return;
      const visibles = Array.from(document.querySelectorAll('.rs-modal')).filter(m => {
        const cs = window.getComputedStyle(m);
        return cs.display !== 'none' && cs.visibility !== 'hidden';
      });
      const modal = visibles.length ? visibles[visibles.length - 1] : null;
      if (!modal) return;
      ev.preventDefault();
      ev.stopPropagation();
      modal.style.display = 'none';
    }, true);
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

    // ===== cargarReglasCombo =====
  function cargarReglasCombo(comboId){
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`action=loadComboCategoriaReglas&combo_id=${comboId}`
    })
    .then(r=>r.json())
    .then(d => (d && d.status) ? (d.reglas || []) : []);
  }

  
  // ===== ISV (para totales de la comanda) =====
  function cargarISV() {
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadMesas'
    })
      .then(response => response.json())
      .then(data => {
        if (data.status) { mesas = data.mesas; renderizarMesas(); }
        else { showAlert('error', 'Error', 'No se pudieron cargar las mesas'); }
      })
      .catch(() => { showAlert('error', 'Error', 'Error al cargar las mesas'); });
  }

  function setServicioTipo(tipo) {
    // Normalizamos a 'mesa' | 'llevar'
    servicioActual = (tipo === 'llevar') ? 'llevar' : 'mesa';
  
    // 1) Refleja en los radios (conserva tu comportamiento original)
    if (servicioActual === 'mesa') {
      if (srvMesa)   srvMesa.checked   = true;
      if (srvLlevar) srvLlevar.checked = false;
    } else {
      if (srvLlevar) srvLlevar.checked = true;
      if (srvMesa)   srvMesa.checked   = false;
    }
  
    // 2) Si es "para llevar", limpiamos mesa seleccionada en estado y en UI
    if (servicioActual === 'llevar') {
      // Estado
      if (typeof mesaSeleccionada !== 'undefined') mesaSeleccionada = null;
      // UI (tu helper)
      if (typeof setMesaSeleccionadaUI === 'function') setMesaSeleccionadaUI(null);
      // Quitar highlight visual de cualquier tile de mesa
      document.querySelectorAll(MESA_TILE_SELECTOR + '.seleccionada')
        .forEach(el => el.classList.remove('seleccionada'));
    }
  
    // 3) Mantén tu lógica visual extra si ya la tenías
    if (typeof toggleServicioUI === 'function') {
      toggleServicioUI(servicioActual);
    }

    document.body && document.body.setAttribute('data-servicio', servicioActual);
    updateAccionPrincipalUI();
  }

  function toggleServicioUI(tipo){
    // Si es "para llevar", no exigimos mesa y mostramos "No seleccionada"
    if (tipo === 'llevar') {
      mesaSeleccionada = null;
      setMesaSeleccionadaUI(null);   // mantiene el ícono y pone "No seleccionada"
      highlightMesaSeleccionada();   // <-- agrega esto para quitar el highlight
    } else {
      if (!mesaSeleccionada) setMesaSeleccionadaUI(null);
    }
  }  

  // Cambios por usuario en el control
  if (servicioSwitch){
    servicioSwitch.addEventListener('change', ()=>{
      setServicioTipo(getServicioTipo());
    });
  }

  // Estado inicial recomendado:
  setServicioTipo('llevar');  // Por defecto funciona como "supermercado"
  setMesaSeleccionadaUI(null);
    
  function renderizarMesas() {
    if (!mesasContainer) return;
    mesasContainer.innerHTML = '';
    if (!Array.isArray(mesas) || !mesas.length) {
      mesasContainer.innerHTML = `<div class="mesa-item" style="opacity:.8;">
        <div class="mesa-header"><span class="mesa-numero">Sin mesas</span></div>
        <div class="mesa-info"><span class="mesa-ubicacion">Crea una con "Nueva".</span></div>
      </div>`;
      return;
    }

    const iconFor = (estado) => {
      switch ((estado || 'disponible').toLowerCase()) {
        case 'ocupada':       return 'fas fa-times-circle';
        case 'reservada':     return 'fas fa-calendar-check';
        case 'mantenimiento': return 'fas fa-tools';
        default:              return 'fas fa-check-circle';
      }
    };

    mesas.forEach(mesa => {
      const id       = mesa.id || mesa.mesa_id;
      const numero   = mesa.numero;
      const cap      = mesa.capacidad || 4;
      const ubic     = mesa.ubicacion || 'Interior';
      const estado   = (mesa.estado || 'disponible').toLowerCase();
      const reserva  = mesa.reserva || null;

      const mesaElement = document.createElement('div');
      mesaElement.className = `mesa-item ${estado}`;
      mesaElement.setAttribute('data-mesa-id', String(id));
      mesaElement.setAttribute('data-estado', estado);
      mesaElement.setAttribute('data-ocupada', estado === 'ocupada' ? '1' : '0');
      mesaElement.setAttribute('data-nombre', numero);

      const reservaHtml = reserva ? `
        <div class="mesa-reserva-info">
          <strong><i class="fas fa-user-clock"></i> ${escapeHtml(reserva.cliente_nombre || 'Cliente reservado')}</strong>
          <span>${escapeHtml(reserva.fecha_reserva || '')}${reserva.hora_reserva ? ' · '+escapeHtml(String(reserva.hora_reserva).slice(0,5)) : ''}${reserva.personas ? ' · '+reserva.personas+' pers.' : ''}</span>
        </div>` : '';

      mesaElement.innerHTML = `
        <div class="mesa-header mesa-header--clean">
          <span class="mesa-numero">Mesa: ${escapeHtml(numero)}</span>
          <div class="mesa-header-tools">
            <span class="mesa-capacidad" title="Capacidad">${cap} <i class="fas fa-user"></i></span>
            <div class="mesa-actions mesa-actions--inline">
              ${estado === 'disponible' ? '<button class="btn-reservar-mesa" title="Reservar mesa" type="button" aria-label="Reservar mesa"><i class="fas fa-calendar-plus"></i></button>' : ''}
              ${estado === 'reservada' ? '<button class="btn-cancelar-reserva" title="Cancelar reserva" type="button" aria-label="Cancelar reserva"><i class="fas fa-calendar-times"></i></button>' : ''}
              ${estado === 'ocupada' ? '<button class="btn-liberar-mesa" title="Liberar mesa" type="button" aria-label="Liberar mesa"><i class="fas fa-door-open"></i></button>' : ''}
              <button class="btn-icon btn-icon--sm btn-edit-mesa" title="Editar mesa" type="button" aria-label="Editar mesa"><i class="fas fa-pen"></i></button>
            </div>
          </div>
        </div>
        <div class="mesa-info">
          <span class="mesa-ubicacion"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(ubic)}</span>
          <span class="mesa-estado estado ${estado}"><i class="${iconFor(estado)}"></i> ${estado.toUpperCase()}</span>
        </div>
        ${reservaHtml}`;

      const edit = mesaElement.querySelector('.btn-edit-mesa');
      if (edit) edit.addEventListener('click', (e)=>{ e.stopPropagation(); autorizarGestionRestaurante('Editar mesa', ()=>abrirEdicionMesa(mesa), mesa.id || mesa.mesa_id || ''); });
      const reserve = mesaElement.querySelector('.btn-reservar-mesa');
      if (reserve) reserve.addEventListener('click', (e)=>{ e.stopPropagation(); abrirReservaMesa(mesa); });
      const cancel = mesaElement.querySelector('.btn-cancelar-reserva');
      if (cancel) cancel.addEventListener('click', (e)=>{ e.stopPropagation(); cancelarReservaMesaUI(mesa); });
      const liberar = mesaElement.querySelector('.btn-liberar-mesa');
      if (liberar) liberar.addEventListener('click', (e)=>{ e.stopPropagation(); liberarMesaManual(mesa, liberar); });

      mesasContainer.appendChild(mesaElement);
    });

    highlightMesaSeleccionada();
    updateAccionPrincipalUI();
  }

  function liberarMesaManual(mesa, boton){
    const mesaId = Number(mesa && (mesa.id || mesa.mesa_id) || 0);
    if (!mesaId) return;
    showConfirm('Liberar mesa', `¿Desea liberar la Mesa ${mesa.numero}? Solo será posible si no tiene una cuenta abierta.`, async ()=>{
      try{
        setButtonBusy(boton, true, '');
        const r = await fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:`action=liberarMesa&mesa_id=${encodeURIComponent(mesaId)}`
        });
        const d = await r.json();
        if (!d || !(d.ok || d.status)) throw new Error((d && (d.message || d.msg)) || 'No se pudo liberar la mesa');
        if (mesaSeleccionada && Number(mesaSeleccionada.id||0) === mesaId) {
          mesaSeleccionada = null; facturaActual = null; setServicioTipo('llevar'); setMesaSeleccionadaUI(null);
        }
        showAlert('success','Mesa disponible','La mesa fue liberada correctamente.');
        await cargarMesas();
      }catch(e){ showAlert('error','No se puede liberar',e.message || 'No se pudo liberar la mesa'); }
      finally { setButtonBusy(boton, false); }
    });
  }

  function highlightMesaSeleccionada(){
    document.querySelectorAll('.mesa-item').forEach(el => el.classList.remove('seleccionada'));
    if (!mesaSeleccionada || !mesaSeleccionada.id) return;
    const el = document.querySelector(`.mesa-item[data-mesa-id="${mesaSeleccionada.id}"]`);
    if (el) el.classList.add('seleccionada');
  }  

  function seleccionarMesa(mesa){
    // Cambiamos a modalidad mesa, pero SIN limpiar la comanda
    setServicioTipo('mesa');
  
    // Copiamos lo que el cajero ya tenía seleccionado (si venía de “para llevar”)
    const pendientes = Array.isArray(comandaItems) ? comandaItems.map(i => ({
      producto: i.producto, cantidad: i.cantidad, precio: i.precio, total: i.total
    })) : [];
  
    // Guardar mesa seleccionada y refrescar header
    mesaSeleccionada = {
      id: mesa.id || mesa.mesa_id,
      numero: mesa.numero,
      capacidad: mesa.capacidad,
      ubicacion: mesa.ubicacion,
      estado: mesa.estado
    };
  
    setMesaSeleccionadaUI(mesaSeleccionada.numero);
    if (btnImprimir) btnImprimir.disabled = true;
    highlightMesaSeleccionada();
  
    // Cargar factura de la mesa y FUSIONAR con lo que ya llevaba el cajero
    if (mesaSeleccionada.id) {
      cargarFacturaMesa(mesaSeleccionada.id, pendientes);
    } else {
      // Si por alguna razón no hay id, al menos no borres lo que tenía
      comandaItems = pendientes;
      actualizarComandaUI();
      if (typeof updateProductBadges === 'function') updateProductBadges();
    }
  }   

  function abrirEdicionMesa(mesa){
    const n  = document.getElementById('numero-mesa');
    const c  = document.getElementById('capacidad-mesa');
    const u  = document.getElementById('ubicacion-mesa');
    const id = document.getElementById('mesa-id');
    const t  = document.getElementById('titulo-modal-mesa');
    const eSel = document.getElementById('estado-mesa');

    if (t) t.textContent = 'Editar Mesa';
    if (id) id.value = mesa.id || mesa.mesa_id || '';
    if (n) n.value  = mesa.numero || '';
    if (c) c.value  = mesa.capacidad || 4;
    if (u) u.value  = mesa.ubicacion || 'Interior';
    if (eSel) eSel.value = String(mesa.estado || 'disponible').toLowerCase()==='mantenimiento' ? 'mantenimiento' : 'disponible';

    limpiarValidacionFormulario('form-mesa');

    if (modalMesa) modalMesa.style.display = 'block';
    reinitSelect2InModal('#modal-mesa');
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
    const eSel = document.getElementById('estado-mesa'); if (eSel) eSel.value = 'disponible';
    if (modalMesa) modalMesa.style.display = 'block';
    
     // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
    limpiarValidacionFormulario('form-mesa');
    // Añade esta línea:
    reinitSelect2InModal('#modal-mesa');
    
    setTimeout(() => { if (n) { n.focus(); n.select && n.select(); } }, 10);
  }

  function guardarMesa() {
    const mesaId     = (document.getElementById('mesa-id') || {}).value || '';
    const numeroMesa = (document.getElementById('numero-mesa') || {}).value?.trim() || '';
    const capacidad  = (document.getElementById('capacidad-mesa') || {}).value || '4';
    const ubicacion  = (document.getElementById('ubicacion-mesa') || {}).value || 'Interior';
    const estado     = (document.getElementById('estado-mesa') || {}).value || 'disponible';
  
    if (!validateForm('form-mesa')) return;
  
    const accion  = mesaId ? 'editar' : 'guardar';
    const mensaje = mesaId
      ? `¿Está seguro que desea editar la mesa ${numeroMesa}?`
      : `¿Está seguro que desea guardar la nueva mesa ${numeroMesa}?`;
  
    showConfirm(accion === 'editar' ? 'Editar Mesa' : 'Nueva Mesa', mensaje, () => {
      const fd = new FormData();
      fd.append('action', mesaId ? 'updateMesa' : 'saveMesa');
      if (mesaId) fd.append('mesa_id', mesaId);
      fd.append('numero', numeroMesa);
      fd.append('capacidad', capacidad);
      fd.append('ubicacion', ubicacion);
      fd.append('estado', estado); // ⬅️ importante
  
      fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.status) {
            showAlert('success', 'Éxito', mesaId ? 'Mesa actualizada correctamente' : 'Mesa guardada correctamente');
            if (modalMesa) modalMesa.style.display = 'none';
            (document.getElementById('mesa-id')||{}).value = '';
            cargarMesas(); // repinta con el estado correcto
          } else {
            showAlert('error', 'Error', data.message || 'No se pudo guardar la mesa');
          }
        })
        .catch(() => { showAlert('error', 'Error', 'Error al guardar la mesa'); });
    });
  }  

  // ===== Categorías / Productos =====
  function cargarCategorias() {
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
    // Las pestañas de categoría siguen a los PRODUCTOS visibles de la estación.
    // Así una categoría puede contener productos de Cocina y Barra sin desaparecer del filtro correcto.
    let catsFiltradas = categorias;
    if (usaComandasOperacion() && estSel !== 'todas') {
      const idsVisibles = new Set((productos || [])
        .filter(p => normalizeEstacion(p) === estSel)
        .map(p => String(p.categoria_id)));
      catsFiltradas = categorias.filter(c => idsVisibles.has(String(c.id)));
    }

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
        autorizarGestionRestaurante('Editar categoría', ()=>abrirEdicionCategoria(categoria), categoria.id || '');
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
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=loadProductos'
    })
    .then(r => r.json())
    .then(async data => {
      if (data.status) {
        productos = data.productos || [];
  
        // === CARGAR PROMOCIONES ===
        try {
          await fetchPromosVigentesProductos();
        } catch (e) {
          // Continuar aunque falle las promociones
        }
  
        renderizarProductos(productos);
  
        // Iniciar contador de promociones
        try {
          startPromosTicker();
        } catch (e) {

        }
  
        if (!categorias.length) {
          const map = new Map();
          productos.forEach(p => { map.set(p.categoria_id, true); });
          categorias = [...map.keys()].map(id => ({ id, nombre: `Cat. ${id}`, estacion: 'ninguna' }));
          renderizarCategorias();
        }
      } else {
        showAlert('error', 'Error', 'No se pudieron cargar los productos');
      }
    })
    .catch(() => { 
      showAlert('error', 'Error', 'Error al cargar los productos'); 
    });
  }
      
  function renderizarProductos(productosList) {
    if (!productosContainer) return;
    productosContainer.innerHTML = '';
  
    if (!Array.isArray(productosList) || !productosList.length) {
      productosContainer.innerHTML = `
        <div class="state-empty">
          <div class="icon"><i class="fas fa-shopping-basket"></i></div>
          <h4>Sin productos</h4>
          <p>Agrega uno con el botón "Nuevo producto".</p>
        </div>`;
      return;
    }
  
    const formatNumber = (num) =>
      new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
  
    const escapeHtml = (s='') =>
      String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  
    productosList.forEach(producto => {
      const isCombo = !!(window.__comboProductoIds && window.__comboProductoIds.has(parseInt(producto.productos_id,10)));
      const comboBadge = isCombo
        ? '<span class="badge badge-pill badge-primary" style="position:absolute;top:8px;left:8px;z-index:2;">Combo</span>'
        : '';
  
      const productoElement = document.createElement('div');
      productoElement.className = 'producto-item';
      if (!productoElement.style.position) productoElement.style.position = 'relative';
      productoElement.setAttribute('data-producto-id', String(producto.productos_id));
  
      // Acciones (editar)
      const actions = document.createElement('div');
      actions.className = 'card-actions';
      const btnZoom = document.createElement('button');
      btnZoom.type = 'button';
      btnZoom.className = 'btn-icon btn-icon--sm btn-zoom-producto';
      btnZoom.title = 'Ver imagen';
      btnZoom.setAttribute('aria-label','Ver imagen de '+(producto.nombre||'producto'));
      btnZoom.innerHTML = '<i class="fas fa-search-plus"></i>';
      btnZoom.addEventListener('click', (e) => {
        e.stopPropagation();
        abrirFotoProducto(producto);
      });
      actions.appendChild(btnZoom);

      const btnEditar = document.createElement('button');
      btnEditar.type = 'button';
      btnEditar.className = 'btn-icon btn-icon--sm btn-edit-producto-admin';
      btnEditar.title = 'Editar';
      btnEditar.innerHTML = '<i class="fas fa-pen"></i>';
      btnEditar.addEventListener('click', (e) => {
        e.stopPropagation();
        autorizarGestionRestaurante('Editar producto', ()=>abrirEdicionProducto(producto), producto.productos_id || '');
      });
      actions.appendChild(btnEditar);
      productoElement.appendChild(actions);
  
      // Imagen
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
  
      // Badge de promo (si hay)
      let promoData = PROMOS_VIGENTES[ Number(producto.productos_id) ];
      if (Array.isArray(promoData) && promoData.length) {
        promoData = promoData.sort((a,b)=> (b.prioridad||0)-(a.prioridad||0))[0];
      }
      if (promoData) {
        const wrap = document.createElement('div');
        wrap.innerHTML = buildPromoBadge(promoData);
        imagenContainer.appendChild(wrap.firstElementChild);
      }
  
      imagenContainer.appendChild(imagenDiv);
      productoElement.appendChild(imagenContainer);
  
      // Contenido
      const contenidoDiv = document.createElement('div');
      contenidoDiv.className = 'producto-contenido';
  
      const mostrarMayoreo = (producto.cantidad_mayoreo > 0 && producto.precio_mayoreo > 0);
      const calc = precioConPromo(producto, promoData);
  
      const nombreHtml = `<h4 class="producto-nombre">${escapeHtml(producto.nombre)}</h4>`;
  
      // === AQUÍ: descripción en small, entre nombre y precio ===
      const descTxt = producto.descripcion ? escapeHtml(producto.descripcion) : '';
      const descHtml = `<small class="producto-desc" title="${descTxt}">${descTxt}</small>`;
  
      let preciosHtml = '';
      if (calc.promo !== null) {
        preciosHtml = `
          <div class="producto-precios">
            <div class="precio-regular"><span class="precio-valor"><del>L ${formatNumber(calc.base)}</del></span></div>
            <div class="precio-regular"><span class="precio-valor">L ${formatNumber(calc.promo)}</span></div>
            ${mostrarMayoreo ? `<div class="precio-mayoreo"><span class="mayoreo-info">${producto.cantidad_mayoreo} x L ${formatNumber(producto.precio_mayoreo)}</span></div>` : ''}
          </div>`;
      } else {
        preciosHtml = `
          <div class="producto-precios">
            <div class="precio-regular"><span class="precio-valor">L ${formatNumber(producto.precio_venta)}</span></div>
            ${mostrarMayoreo ? `<div class="precio-mayoreo"><span class="mayoreo-info">${producto.cantidad_mayoreo} x L ${formatNumber(producto.precio_mayoreo)}</span></div>` : ''}
          </div>`;
      }
  
      const estacionHtml = usaComandasOperacion() ? `
        <div class="producto-meta-line">
          <span class="producto-estacion-badge producto-estacion-${normalizeEstacion(producto)}">
            <i class="${normalizeEstacion(producto)==='barra'?'fas fa-glass-martini-alt':'fas fa-utensils'}"></i>
            ${escapeHtml(etiquetaEstacion(normalizeEstacion(producto)==='barra'?'barra':'cocina'))}
          </span>
        </div>` : '';
      contenidoDiv.innerHTML = `
        ${comboBadge}
        ${nombreHtml}
        ${estacionHtml}
        ${descHtml}
        ${preciosHtml}
      `;
      productoElement.appendChild(contenidoDiv);
  
      // Botón Agregar
      const btnAgregar = document.createElement('button');
      btnAgregar.className = 'btn-agregar';
      btnAgregar.innerHTML = '<i class="fas fa-cart-plus"></i> Agregar';
      productoElement.appendChild(btnAgregar);
  
      // Payload para comanda
      const datosProducto = {
        id: producto.productos_id,
        nombre: producto.nombre,
        precio: parseFloat(producto.precio_venta),
        descripcion: producto.descripcion || '',
        imagen: producto.file_name ? `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}` : `${SERVERURL}vistas/plantilla/img/products/image_preview.png`,
        categoria_id: producto.categoria_id,
        isv1: parseInt(producto.isv1 || 0) === 1,
        isv2: parseInt(producto.isv2 || 0) === 1,
        para_cocina: normalizeEstacion(producto) === 'cocina' ? 1 : 0,
        estacion: normalizeEstacion(producto),
        barCode: producto.barCode || '',
        almacen_id: Number(producto.almacen_id || 0),
        medida_id: Number(producto.medida_id || 0),
        medida: producto.medida || 'Und'
      };
  
      btnAgregar.addEventListener('click', (e) => { e.stopPropagation(); agregarProductoComanda(datosProducto); });
      productoElement.addEventListener('click', () => agregarProductoComanda(datosProducto));
  
      productosContainer.appendChild(productoElement);
    });
  }  

  function filtrarProductos(termino, categoriaId = null) {
    let productosFiltrados = Array.isArray(productos) ? productos.slice() : [];

    // La estación operativa viene del PRODUCTO; la categoría solo organiza el catálogo.
    // El filtro superior debe afectar tanto las pestañas como las tarjetas.
    const estacion = estacionSeleccionadaUI();
    if (usaComandasOperacion() && (estacion === 'cocina' || estacion === 'barra')) {
      productosFiltrados = productosFiltrados.filter(p => normalizeEstacion(p) === estacion);
    }

    if (termino) {
      const t = String(termino).toLowerCase().trim();
      productosFiltrados = productosFiltrados.filter(p =>
        (p.nombre && String(p.nombre).toLowerCase().includes(t)) ||
        (p.descripcion && String(p.descripcion).toLowerCase().includes(t))
      );
    }

    if (categoriaId) {
      productosFiltrados = productosFiltrados.filter(p =>
        parseInt(p.categoria_id, 10) === parseInt(categoriaId, 10)
      );
    }

    renderizarProductos(productosFiltrados);
  }

  function tryBarcodeAdd(code){
    const c = String(code || '').trim();
    if (!c) return false;
    const prod = productos.find(p => String(p.barCode || '').trim() === c);
    if (!prod) return false;
    // Mapear el producto a la estructura que usa la comanda
    const datosProducto = {
      id: prod.productos_id,
      nombre: prod.nombre,
      precio: parseFloat(prod.precio_venta),
      descripcion: prod.descripcion || '',
      imagen: prod.file_name ? `${SERVERURL}vistas/plantilla/img/products/${prod.file_name}` : `${SERVERURL}vistas/plantilla/img/products/image_preview.png`,
      categoria_id: prod.categoria_id,
      isv1: parseInt(prod.isv1 || 0) === 1,
      isv2: parseInt(prod.isv2 || 0) === 1,
      para_cocina: normalizeEstacion(prod) === 'cocina' ? 1 : 0,
      estacion: normalizeEstacion(prod),
      barCode: prod.barCode || ''
    };
    agregarProductoComanda(datosProducto);
    return true;
  }

  // =======================================================
  // CLIENTE: guardar (no limpia en edición; sí limpia en nuevo)
  // =======================================================
  function guardarClienteBasico(){
    const id        = (document.getElementById('cli-id')||{}).value || '';
    const nombre    = (document.getElementById('cli-nombre')||{}).value?.trim() || '';
    const rtn       = (document.getElementById('cli-rtn')||{}).value?.trim() || '';
    const localidad = (document.getElementById('cli-localidad')||{}).value?.trim() || '';
    const telefono  = (document.getElementById('cli-telefono')||{}).value?.trim() || '';
    const correo    = (document.getElementById('cli-correo')||{}).value?.trim() || '';

    if (!validateForm('form-nuevo-cliente')) return;

    const esEdicion   = !!id;
    const titulo      = esEdicion ? 'Editar Cliente' : 'Nuevo Cliente';
    const mensaje     = esEdicion
      ? `¿Está seguro que desea editar el cliente "${nombre}"?`
      : `¿Está seguro que desea guardar el nuevo cliente "${nombre}"?`;
    const prevFocusEl = document.activeElement; // <-- para no perder foco en edición

    showConfirm(titulo, mensaje, () => {
      const payload = {
        clientes_id: esEdicion ? parseInt(id,10) : undefined,
        nombre, rtn,
        fecha: new Date().toISOString().slice(0,10),
        departamentos_id: 0, municipios_id: 0,
        localidad, telefono, correo, estado: 1
      };
      const action = esEdicion ? 'updateClienteBasico' : 'saveClienteBasico';

      fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action, data: payload })
      })
      .then(r=>r.json())
      .then(d=>{
        if (!d || !d.status){
          showAlert('error','Error',(d && (d.message||d.msg)) || 'No se pudo guardar el cliente');
          return;
        }
        showAlert('success','Éxito', esEdicion ? 'Cliente actualizado' : 'Cliente guardado');

        if (!esEdicion){
          // NUEVO: limpiar y enfocar primer input
          const form = document.getElementById('form-nuevo-cliente');
          if (form){ form.reset(); form.classList.remove('was-validated'); }
          (document.getElementById('cli-id')||{}).value = '';
          const t = document.getElementById('titulo-modal-cliente'); if (t) t.textContent = 'Nuevo Cliente';
          const inp = document.getElementById('cli-nombre'); if (inp){ inp.focus({preventScroll:true}); inp.select?.(); }
        } else if (prevFocusEl && typeof prevFocusEl.focus === 'function'){
          // EDICIÓN: mantener foco donde estaba
          setTimeout(()=>{ try{ prevFocusEl.focus({preventScroll:true}); }catch(_){} }, 0);
        }

        // Recargar lista / reflejar cabecera
        const afterReload = () => {
          const cli = d.cliente;
          if (cli && cli.clientes_id){
            clienteSeleccionado = {
              id: cli.clientes_id,
              nombre: cli.nombre || nombre,
              identificacion: (cli.identificacion || cli.rtn || rtn || '').trim()
            };
            if (typeof pintarClienteInfoHeader === 'function') pintarClienteInfoHeader();
          }
        };
        if (typeof cargarClientes === 'function'){
          const maybe = cargarClientes();
          if (maybe && typeof maybe.then === 'function') maybe.then(afterReload); else afterReload();
        } else { afterReload(); }
      })
      .catch(()=> showAlert('error','Error','No se pudo guardar el cliente'));
    });
  }

  // ===================================================================
  // CATEGORÍA: guardar (no limpia en edición; sí limpia cuando es nueva)
  // ===================================================================
  function guardarCategoriaDesdeModal(){
    const nombre   = (document.getElementById('cat-nombre')||{}).value?.trim() || '';
    const idCat    = (document.getElementById('cat-id')||{}).value || '';
    const rChecked = document.querySelector('#form-categoria input[name="catEstacion"]:checked');
    const estacion = (rChecked && rChecked.value) ? String(rChecked.value).toLowerCase().trim() : '';

    if (!validateForm('form-categoria')) return;
    if (!['cocina','barra'].includes(estacion)){
      showAlert('warning','Falta grupo','Selecciona '+etiquetaEstacion('cocina')+' o '+etiquetaEstacion('barra')+'.'); return;
    }

    const esEdicion   = !!idCat;
    const titulo      = esEdicion ? 'Editar Categoría' : 'Nueva Categoría';
    const mensaje     = esEdicion
      ? `¿Está seguro que desea editar la categoría "${nombre}"?`
      : `¿Está seguro que desea guardar la nueva categoría "${nombre}"?`;
    const prevFocusEl = document.activeElement;

    showConfirm(titulo, mensaje, () => {
      const fd = new FormData();
      fd.append('nombre', nombre);
      fd.append('estacion', estacion);
      if (esEdicion){ fd.append('action','updateCategoria'); fd.append('categoria_id', idCat); }
      else          { fd.append('action','saveCategoria'); }

      fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', { method:'POST', body: fd })
        .then(r=>r.json())
        .then(d=>{
          if (!d || !d.status){
            showAlert('error','Error', (d && (d.message||d.msg)) || 'No se pudo guardar'); return;
          }
          showAlert('success','Éxito', esEdicion ? 'Categoría actualizada' : 'Categoría guardada');

          if (!esEdicion){
            const formCat = document.getElementById('form-categoria');
            if (formCat){ formCat.reset(); formCat.classList.remove('was-validated'); }
            (document.getElementById('cat-id')||{}).value = '';
            const t = document.getElementById('titulo-modal-categoria'); if (t) t.textContent = 'Nueva Categoría';
            const rSel = document.querySelector(`#form-categoria input[name="catEstacion"][value="${estacion}"]`);
            if (rSel) rSel.checked = true;
            const inp = document.getElementById('cat-nombre'); if (inp){ inp.focus({preventScroll:true}); inp.select?.(); }
          } else if (prevFocusEl && typeof prevFocusEl.focus === 'function'){
            setTimeout(()=>{ try{ prevFocusEl.focus({preventScroll:true}); }catch(_){} }, 0);
          }

          if (typeof cargarCategorias === 'function') cargarCategorias();
        })
        .catch(()=> showAlert('error','Error','No se pudo guardar'));
    });
  }

  // ===================================================================
  // PRODUCTO: guardar (no limpia en edición; sí limpia cuando es nuevo)
  // ===================================================================
  function guardarProductoBasico(){
    const { inpNombre, inpDesc, selCat, inpPrecio, chkISV1, chkISV2 } = getProdControls();

    const lastEstacion = (document.querySelector('input[name="prodEstacion"]:checked')?.value || 'cocina').toLowerCase();

    if (!validateForm('form-producto')) return;

    const id     = (document.getElementById('prod-id')||{}).value || '';
    const nombre = (inpNombre||{}).value?.trim() || '';
    const desc   = (inpDesc||{}).value?.trim() || '';
    const catId  = (selCat||{}).value || '';
    const precio = parseFloat((inpPrecio||{}).value || '0') || 0;
    const isv1   = !!(chkISV1||{}).checked;
    const isv2   = !!(chkISV2||{}).checked;

    const esEdicion   = !!id;
    const titulo      = esEdicion ? 'Editar Producto' : 'Nuevo Producto';
    const mensaje     = esEdicion
      ? `¿Está seguro que desea editar el producto "${nombre}"?`
      : `¿Está seguro que desea guardar el nuevo producto "${nombre}"?`;
    const prevFocusEl = document.activeElement;

    showConfirm(titulo, mensaje, async () => {
      const saveBtn = document.getElementById('btn-guardar-producto');
      if (saveBtn && saveBtn.disabled) return;
      try{
        setButtonBusy(saveBtn, true, esEdicion ? 'Actualizando…' : 'Guardando…');
        const fd = new FormData();
        fd.append('action', esEdicion ? 'updateProductoBasico' : 'saveProductoBasico');
        if (esEdicion) fd.append('productos_id', String(parseInt(id,10)));
        fd.append('nombre', nombre);
        fd.append('descripcion', desc);
        fd.append('categoria_id', String(parseInt(catId,10)));
        fd.append('precio_venta', String(precio.toFixed(2)));
        fd.append('isv1', isv1 ? '1' : '0');
        fd.append('isv2', isv2 ? '1' : '0');
        fd.append('estacion', lastEstacion);

        const file = (typeof getProductoImagenFile === 'function') ? getProductoImagenFile() : null;
        if (file) fd.append('imagen_producto', file);

        const resp = await fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', { method:'POST', body: fd });
        let d = null; try { d = await resp.json(); } catch(_) {}
        if (!d || !d.status){
          showAlert('error','Error', (d && (d.message||d.msg)) || 'No se pudo guardar'); return;
        }

        showAlert('success','Éxito', esEdicion ? 'Producto actualizado' : 'Producto guardado');

        if (!esEdicion){
          // NUEVO: limpiar + preparar para otro
          const form = document.getElementById('form-producto');
          if (form){ form.reset(); form.classList.remove('was-validated'); }
          const elId = document.getElementById('prod-id'); if (elId) elId.value = '';
          const t = document.getElementById('titulo-modal-producto'); if (t) t.textContent = 'Nuevo Producto';
          if (typeof resetProductoImagen === 'function') resetProductoImagen();

          // restaurar estación y recargar categorías seleccionando la primera
          const r = document.querySelector(`input[name="prodEstacion"][value="${lastEstacion}"]`);
          if (r){ r.checked = true; r.dispatchEvent(new Event('change', {bubbles:true})); }

          const cargar = (typeof cargarCategoriasProductoPorEstacion === 'function')
                        ? cargarCategoriasProductoPorEstacion
                        : (typeof cargarCategoriasPorEstacion === 'function')
                          ? cargarCategoriasPorEstacion
                          : (typeof filtrarSelectCategoriasPorEstacion === 'function')
                            ? filtrarSelectCategoriasPorEstacion
                            : null;

          const seleccionarPrimera = () => {
            const sel = document.getElementById('prod-categoria');
            if (!sel) return;
            let firstVal = '';
            for (let i=0;i<sel.options.length;i++){
              const op = sel.options[i];
              if (!op.disabled && op.value !== ''){ firstVal = op.value; break; }
            }
            if (!firstVal) return;
            if (window.jQuery){ window.jQuery(sel).val(firstVal).trigger('change'); }
            else { sel.value = firstVal; sel.dispatchEvent(new Event('change',{bubbles:true})); }
          };

          if (cargar){
            const maybe = cargar(lastEstacion);
            if (maybe && typeof maybe.then === 'function') maybe.then(()=> setTimeout(seleccionarPrimera, 10));
            else setTimeout(seleccionarPrimera, 50);
          } else { setTimeout(seleccionarPrimera, 20); }

          // foco al nombre para tipear el siguiente
          if (inpNombre && typeof inpNombre.focus === 'function'){
            inpNombre.focus({preventScroll:true});
            inpNombre.select?.();
          }
        } else {
          // EDICIÓN: mantener foco donde estaba
          if (prevFocusEl && typeof prevFocusEl.focus === 'function'){
            setTimeout(()=>{ try{ prevFocusEl.focus({preventScroll:true}); }catch(_){} }, 0);
          }
        }

        // Refrescar catálogos usando SIEMPRE la respuesta real del servidor.
        if (typeof cargarCategorias === 'function') await cargarCategorias();
        if (typeof cargarProductos === 'function') await cargarProductos();

        // Verificación de consistencia: después de guardar, el producto debe regresar
        // desde loadProductos con la misma estación seleccionada.
        const productoGuardadoId = esEdicion ? parseInt(id, 10) : parseInt(d.producto_id || 0, 10);
        if (productoGuardadoId > 0) {
          const productoServidor = productos.find(p => parseInt(p.productos_id, 10) === productoGuardadoId);
          if (productoServidor && normalizeEstacion(productoServidor) !== lastEstacion) {
            throw new Error(`El servidor no confirmó la estación ${etiquetaEstacion(lastEstacion === 'barra' ? 'barra' : 'cocina')} del producto. Revise productos.estacion.`);
          }
        }

        renderizarCategorias();
        filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');

      } catch(err){
        showAlert('error','Error', (err && err.message) ? err.message : 'No se pudo guardar el producto');
      } finally {
        setButtonBusy(saveBtn, false);
      }
    });
  }
  
  // ===== Clientes =====
  function cargarClientes() {
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
          showAlert('error', 'Error', 'No se pudieron cargar los clientes');
        }
      })
      .catch(() => { showAlert('error', 'Error', 'Error al cargar los clientes'); });
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
        autorizarGestionRestaurante('Editar cliente', ()=>abrirEdicionCliente(c), c.clientes_id || c.id || '');
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
      identificacion: (selectedClienteForModal.identificacion || selectedClienteForModal.rtn || '').trim()
    };
  
    pintarClienteInfoHeader();
  
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

    // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
    limpiarValidacionFormulario('form-nuevo-cliente');

    setTimeout(()=>{ const el = document.getElementById('cli-nombre'); el && el.focus(); },10);
  }

  function abrirEdicionCliente(c){
    // Guarda id en tu contexto de edición
    editContext.clienteId = c?.clientes_id || '';
  
    // Limpia estados de validación previos
    limpiarValidacionFormulario('form-nuevo-cliente');
  
    // Título y mostrar modal
    const titulo = document.getElementById('titulo-modal-cliente');
    if (titulo) titulo.textContent = 'Editar Cliente';
    if (modalNuevoCliente) modalNuevoCliente.style.display = 'block';
  
    // Precargar campos (con fallback seguros)
    setTimeout(() => {
      const vals = {
        'cli-id'       : c?.clientes_id ?? '',
        'cli-nombre'   : c?.nombre ?? '',
        'cli-rtn'      : (c?.identificacion ?? c?.rtn ?? ''),
        'cli-localidad': c?.localidad ?? '',
        'cli-telefono' : c?.telefono ?? '',
        'cli-correo'   : c?.correo ?? ''
      };
  
      Object.keys(vals).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = vals[id];
      });
  
      // 👉 Foco al primer input y seleccionar texto para editar directo
      const inp = document.getElementById('cli-nombre');
      if (inp) {
        inp.focus({ preventScroll: true });
        inp.select?.();
      }
    }, 50); // pequeño delay para asegurar que el modal ya está visible
  }  

  // ===== FACTURAS =====
  // itemsToMerge = items que ya venía armando el cajero antes de tocar la mesa
  function cargarFacturaMesa(mesaId, itemsToMerge = []){
    fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=loadFacturaMesa&mesa_id=${encodeURIComponent(mesaId)}`
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.status) {
        // Hay factura abierta en la mesa
        setServicioTipo('mesa');

        facturaActual    = data.factura || null;
        mesaSeleccionada = data.mesa    || mesaSeleccionada;

        if (facturaActual && (facturaActual.cliente_id || facturaActual.clientes_id)) {
          clienteSeleccionado = {
            id: Number(facturaActual.cliente_id || facturaActual.clientes_id),
            nombre: facturaActual.cliente_nombre || 'Cliente',
            identificacion: facturaActual.cliente_identificacion || ''
          };
          pintarClienteInfoHeader();
        }

        const itemsMesa = (Array.isArray(data.items) ? data.items : []).map(item => ({
          producto: {
            id: item.productos_id,
            nombre: item.nombre_producto,
            precio: parseFloat(item.precio),
            descripcion: item.descripcion_producto || '',
            isv1: Number(item.isv1 || 0) === 1, isv2: Number(item.isv2 || 0) === 1, para_cocina: 0,
            medida: item.medida || 'Und'
          },
          cantidad: parseFloat(item.cantidad),
          precio:   parseFloat(item.precio),
          total:    parseFloat(item.precio) * parseFloat(item.cantidad)
        }));

        // 🔀 Fusionar: lo de la mesa + lo que el cajero ya llevaba
        comandaItems = mergeComandaItems(itemsMesa, itemsToMerge);

        // UI de cabecera
        const nomMesa = mesaSeleccionada
          ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || mesaSeleccionada.nombre || mesaSeleccionada.nombre_mesa || null)
          : null;
        setMesaSeleccionadaUI(nomMesa);

        // Título y cliente
        const numFactura = facturaActual
          ? (facturaActual.number || facturaActual.numero || facturaActual.factura_numero || facturaActual.id || facturaActual.factura_id)
          : null;
        if (facturaTitle) {
          facturaTitle.innerHTML = `<i class="fas fa-receipt"></i> ${numFactura ? 'Factura #'+numFactura : 'Factura abierta'}`;
        }
        if (observacionesTextarea) {
          observacionesTextarea.value = (facturaActual && (facturaActual.notas || facturaActual.observaciones || '')) || '';
        }
        if (btnImprimir) btnImprimir.disabled = false;

        actualizarComandaUI();
        if (typeof updateProductBadges === 'function') updateProductBadges();
        highlightMesaSeleccionada();
        updateAccionPrincipalUI();

      } else {
        // ❗ La mesa no tiene factura abierta: mantenemos lo que el cajero traía
        setServicioTipo('mesa');
        facturaActual = null;

        // NO limpiar: conservar o establecer lo pendiente
        if (Array.isArray(itemsToMerge) && itemsToMerge.length) {
          comandaItems = mergeComandaItems([], itemsToMerge);
        } // si venía algo ya en comandaItems, déjalo igual

        actualizarComandaUI();
        if (typeof updateProductBadges === 'function') updateProductBadges();

        if (facturaTitle) {
          facturaTitle.innerHTML = usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
        }
        if (btnImprimir) btnImprimir.disabled = true;

        const nomMesa = mesaSeleccionada
          ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || null)
          : null;
        setMesaSeleccionadaUI(nomMesa);
        highlightMesaSeleccionada();
        updateAccionPrincipalUI();
      }
    })
    .catch(() => {
      showAlert('error', 'Error', 'Error al cargar la factura');
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

    updateProductBadges();
  }

  // Fusiona items por producto y precio (suma cantidades)
  function mergeComandaItems(baseList, addList){
    const dst = Array.isArray(baseList) ? baseList.slice() : [];
    (Array.isArray(addList) ? addList : []).forEach(src => {
      const idx = dst.findIndex(d => d.producto && src.producto &&
        Number(d.producto.id) === Number(src.producto.id) &&
        Number(d.precio) === Number(src.precio));
      if (idx > -1) {
        dst[idx].cantidad += Number(src.cantidad || 1);
        dst[idx].total = dst[idx].cantidad * dst[idx].precio;
      } else {
        dst.push({
          producto: src.producto,
          cantidad: Number(src.cantidad || 1),
          precio: Number(src.precio),
          total: Number(src.precio) * Number(src.cantidad || 1)
        });
      }
    });
    return dst;
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
    updateAccionPrincipalUI();
  }

  function hoyLocalISO(){
    const d = new Date();
    const off = d.getTimezoneOffset();
    return new Date(d.getTime() - off * 60000).toISOString().slice(0,10);
  }

  function horaLocalHHMM(){
    const d = new Date();
    return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
  }

  function abrirReservaMesa(mesa){
    const modal = document.getElementById('modal-reserva-mesa');
    const sel = document.getElementById('reserva-cliente');
    if (!modal || !sel) return;
    document.getElementById('reserva-mesa-id').value = mesa.id || mesa.mesa_id || '';
    sel.innerHTML = '<option value="">Seleccione un cliente…</option>';
    (clientes || []).forEach(c => {
      const id = c.clientes_id || c.id;
      if (!id) return;
      const op = document.createElement('option');
      op.value = id;
      op.textContent = c.nombre + ((c.identificacion || c.rtn) ? ' · ' + (c.identificacion || c.rtn) : '');
      sel.appendChild(op);
    });
    if (clienteSeleccionado && Number(clienteSeleccionado.id || 0) > 1) sel.value = String(clienteSeleccionado.id);
    document.getElementById('reserva-fecha').value = hoyLocalISO();
    document.getElementById('reserva-hora').value = horaLocalHHMM();
    document.getElementById('reserva-personas').value = Math.min(Number(mesa.capacidad || 2), 2) || 2;
    document.getElementById('reserva-notas').value = '';
    modal.style.display = 'block';
    reinitSelect2InModal('#modal-reserva-mesa');
    setTimeout(()=>{ try { $('#reserva-cliente').select2('open'); } catch(_){} },60);
  }

  function cancelarReservaMesaUI(mesa){
    showConfirm('Cancelar reserva', `¿Desea cancelar la reserva de la Mesa ${mesa.numero}?`, async ()=>{
      try{
        const body = new URLSearchParams({action:'cancelarReservaMesa', mesa_id:String(mesa.id || mesa.mesa_id || 0)});
        const r = await fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString()
        });
        const d = await r.json();
        if (!d || !d.status) throw new Error((d && d.message) || 'No se pudo cancelar la reserva');
        showAlert('success','Reserva',d.message || 'Reserva cancelada');
        await cargarMesas();
      }catch(e){ showAlert('error','Error',e.message || 'No se pudo cancelar la reserva'); }
    });
  }

  $(document).off('click.restReserva','#btn-guardar-reserva-mesa').on('click.restReserva','#btn-guardar-reserva-mesa', async function(){
    const btn=this;
    const mesa_id=Number($('#reserva-mesa-id').val() || 0);
    const clientes_id=Number($('#reserva-cliente').val() || 0);
    const fecha_reserva=$('#reserva-fecha').val();
    const hora_reserva=$('#reserva-hora').val();
    const personas=Number($('#reserva-personas').val() || 0);
    const notas=String($('#reserva-notas').val() || '').trim();
    if (!mesa_id || !clientes_id || !fecha_reserva || !hora_reserva || personas <= 0){
      showAlert('warning','Datos incompletos','Seleccione cliente, fecha, hora y cantidad de personas.'); return;
    }
    setButtonBusy(btn,true,'Guardando…');
    try{
      const body=new URLSearchParams({action:'reservarMesa',mesa_id:String(mesa_id),clientes_id:String(clientes_id),fecha_reserva,hora_reserva,personas:String(personas),notas});
      const r=await fetchWithTimeout(BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()});
      const d=await r.json();
      if(!d || !d.status) throw new Error((d&&d.message)||'No se pudo reservar la mesa');
      $('#modal-reserva-mesa').hide();
      showAlert('success','Reserva',d.message || 'Mesa reservada correctamente');
      await cargarMesas();
    }catch(e){showAlert('error','Error',e.message||'No se pudo reservar la mesa');}
    finally{setButtonBusy(btn,false);}
  });

  function obtenerTotalComanda(){
    const txt = totalElement ? String(totalElement.textContent || '') : '';
    const n = Number(txt.replace(/[^0-9.,-]/g,'').replace(/,/g,''));
    if (Number.isFinite(n) && n > 0) return n;
    return (comandaItems || []).reduce((a,it)=>a+(Number(it.precio||it.producto?.precio||0)*Number(it.cantidad||1)),0);
  }

  function prepararDetalleRestaurante(){
    return JSON.stringify((comandaItems || []).map(it=>({
      productos_id:(it.productos_id || it.producto_id || (it.producto && it.producto.id)),
      cantidad:Number(it.cantidad || 1),
      precio:Number(it.precio || (it.producto && it.producto.precio) || 0),
      isv_valor:Number(it.isv_valor || 0),
      descuento:Number(it.descuento || 0),
      medida:(it.medida || '')
    })));
  }

  function clienteIdActual(){
    const el=document.getElementById('clientes_id');
    return Number((el && el.value) || (clienteSeleccionado && clienteSeleccionado.id) || 1) || 1;
  }

  function mesaIdActual(){
    return Number(mesaSeleccionada && (mesaSeleccionada.id || mesaSeleccionada.mesa_id || mesaSeleccionada) || 0);
  }

  function facturaIdActual(){
    return Number(facturaActual && (facturaActual.facturas_id || facturaActual.factura_id || facturaActual.id) || 0);
  }

  function hayProductoCocinaActual(){
    return (comandaItems || []).some(it=>{
      const pid=String(it.productos_id || it.producto_id || (it.producto && it.producto.id) || '');
      const prod=(productos || []).find(p=>String(p.productos_id || p.id)===pid);
      return prod ? isProductoDeCocina(prod) : false;
    });
  }

  function guardarCuentaMesa({silencioso=false,imprimirAlEnviar=false,enviarComanda=false}={}){
    const mesa_id=mesaIdActual();
    if(!mesa_id) return Promise.reject(new Error('Debe seleccionar una mesa'));
    if(!Array.isArray(comandaItems)||!comandaItems.length) return Promise.reject(new Error('Agregue productos a la comanda'));
    const payload={
      action:'guardarFacturaRestaurante', servicio:'mesa', mesa_id,
      factura_id:facturaIdActual(), clientes_id:clienteIdActual(),
      observaciones:String((observacionesTextarea&&observacionesTextarea.value)||'').trim(),
      detalle:prepararDetalleRestaurante(), enviar_comanda:(enviarComanda&&debeEnviarPantallaComanda())?1:0
    };
    return new Promise((resolve,reject)=>{
      $.ajax({type:'POST',url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',data:payload,dataType:'json'})
        .done(async d=>{
          if(!d||!d.ok){reject(new Error((d&&(d.msg||d.message))||'No se pudo guardar la cuenta'));return;}
          facturaActual={id:Number(d.factura_id),factura_id:Number(d.factura_id),facturas_id:Number(d.factura_id)};
          if(facturaTitle) facturaTitle.innerHTML='<i class="fas fa-receipt"></i> Cuenta abierta #'+d.factura_id;
          if(btnImprimir) btnImprimir.disabled=false;
          const tile=document.querySelector(`${MESA_TILE_SELECTOR}[data-mesa-id="${mesa_id}"]`);
          if(tile){tile.classList.remove('disponible','reservada');tile.classList.add('ocupada');tile.setAttribute('data-estado','ocupada');tile.setAttribute('data-ocupada','1');}
          updateAccionPrincipalUI();
          cargarMesas();
          if(imprimirAlEnviar && debeImprimirTicketComanda() && momentoTicketOperacion()==='enviar'){
            const snap=crearSnapshotTicket({factura_id:Number(d.factura_id||facturaIdActual()||0)});
            setTimeout(()=>imprimirTicketAutomatico(snap),120);
          }
          if(!silencioso){
            if(enviarComanda && usaComandasOperacion()){
              const nuevos=Number(d.nuevos_comanda||0);
              showAlert('success','Comanda',nuevos>0 ? `Cuenta actualizada y ${nuevos} producto(s) nuevo(s) enviados a preparación.` : 'Cuenta actualizada. No había productos nuevos por enviar.');
            }else{
              showAlert('success','Cuenta',d.updated ? 'Cuenta actualizada correctamente.' : 'Cuenta guardada correctamente.');
            }
          }
          resolve(d);
        }).fail(xhr=>reject(new Error((xhr.responseJSON&&(xhr.responseJSON.msg||xhr.responseJSON.message))||xhr.responseText||'Error de comunicación')));
    });
  }

  // ============================================================
  // COBRO RESTAURANTE -> FLUJO OFICIAL DE FACTURACIÓN IZZY
  // ============================================================
  // No crea pagos ni números de factura dentro de Restaurante.
  // addFacturaAjax.php/facturasControlador asigna el número real y su
  // respuesta abre pago() del modal unificado oficial.
  let contextoPagoRestaurante = null;
  let facturandoRestaurante = false;

  function clienteNombreActual(){
    return String((clienteSeleccionado && clienteSeleccionado.nombre) || 'CONSUMIDOR FINAL').trim() || 'CONSUMIDOR FINAL';
  }

  function productoMaestroPorId(pid){
    return (productos || []).find(p=>Number(p.productos_id || p.id || 0)===Number(pid||0)) || null;
  }

  function construirPayloadFacturaNormal(facturaId){
    const params = new URLSearchParams();
    const clienteId = clienteIdActual();
    const clienteNombre = clienteNombreActual();
    const colaboradorId = Number(window.REST_COLABORADOR_ID || 0);
    const cajeroNombre = String($('#cajero-nombre').text() || '').replace(/^Cajero:\s*/i,'').trim() || 'Cajero';
    const fecha = String(window.REST_FECHA_SERVIDOR || new Date().toISOString().slice(0,10));

    params.set('cliente_id', String(clienteId));
    params.set('cliente', clienteNombre);
    params.set('colaborador_id', String(colaboradorId));
    params.set('colaborador', cajeroNombre);
    params.set('fecha', fecha);
    params.set('fecha_dolar', fecha);
    params.set('notesBill', String((observacionesTextarea && observacionesTextarea.value) || '').trim());
    params.set('facturas_activo', '1');          // contado
    params.set('facturas_proforma', '0');        // factura fiscal normal
    params.set('tipo_factura', '1');
    if (facturaId) params.set('facturas_id', String(facturaId));

    (comandaItems || []).forEach((it, idx)=>{
      const pid = Number(it.productos_id || it.producto_id || (it.producto && it.producto.id) || 0);
      const master = productoMaestroPorId(pid) || {};
      const prod = it.producto || master || {};
      const nombre = String(prod.nombre || master.nombre || ('Producto '+pid));
      const cantidad = Number(it.cantidad || 1);
      const precio = Number(it.precio != null ? it.precio : (prod.precio != null ? prod.precio : master.precio_venta || 0));
      const descuento = Number(it.descuento || 0);
      const medida = String(prod.medida || it.medida || master.medida || 'Und');
      const almacen = Number(prod.almacen_id || master.almacen_id || 0);
      const barcode = String(prod.barCode || master.barCode || '');

      params.append('productos_id[]', String(pid));
      params.append('productName[]', nombre);
      params.append('quantity[]', String(cantidad));
      params.append('price[]', String(precio));
      params.append('discount[]', String(descuento));
      params.append('medida[]', medida);
      params.append('bodega[]', String(almacen));
      params.append('referenciaProducto[]', barcode);
      params.append('precio_real[]', String(precio));
      // El controlador normal vuelve a calcular el ISV desde productos.
      params.append('valor_isv[]', '0');
      params.append('valor_isv1[]', '0');
    });
    return params;
  }

  function limpiarTextoRespuestaFactura(html){
    const tmp=document.createElement('div');
    tmp.innerHTML=String(html||'');
    return String(tmp.textContent||tmp.innerText||'').replace(/\s+/g,' ').trim();
  }

  function extraerFacturaIdDeRespuesta(html){
    const raw=String(html||'');
    // La respuesta oficial de Facturas incluye pago(<facturas_id>, ...).
    // Solo extraemos el ID: NO ejecutamos el script completo porque contiene
    // instrucciones propias de la pantalla Facturas (por ejemplo reset del
    // formulario #formulario_facturacion), elementos que no existen aquí.
    const patrones=[
      /\bpago\s*\(\s*["']?(\d+)["']?/i,
      /facturas?_id[^0-9]{0,20}(\d+)/i
    ];
    for(const re of patrones){
      const m=raw.match(re);
      if(m && Number(m[1])>0) return Number(m[1]);
    }
    return 0;
  }

  function validarRespuestaFacturaNormal(html){
    const raw=String(html||'').trim();
    if(!raw) throw new Error('El servidor no devolvió una respuesta de facturación.');
    const facturaId=extraerFacturaIdDeRespuesta(raw);
    if(facturaId>0) return facturaId;

    const texto=limpiarTextoRespuestaFactura(raw);
    const msgMatch=raw.match(/(?:showNotify|swal)\s*\([^)]*?["']([^"']{4,220})["']/i);
    const msg=(msgMatch && msgMatch[1]) ? msgMatch[1] : texto;
    throw new Error(msg || 'El sistema no devolvió el identificador de la factura generada.');
  }

  async function registrarComandaPostPago(contexto){
    if(!contexto || !contexto.factura_id) return true;
    if(contexto.comanda_enviada===true) return true;
    if(!debeEnviarPantallaComanda()) return true;
    if(contexto.servicio!=='llevar') return true;
    try{
      const r=await $.ajax({
        type:'POST',
        url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',
        dataType:'json',
        data:{action:'registrarComandaCocina',factura_id:contexto.factura_id,mesa_id:0,servicio:'llevar',comentarios:contexto.observaciones||''},
        timeout:15000
      });
      if(!r || !r.ok){
        throw new Error((r&&(r.msg||r.message))||'No se pudo registrar la comanda');
      }
      contexto.comanda_enviada=true;
      return true;
    }catch(e){
      showAlert('warning','Factura pagada','La factura se pagó correctamente, pero no se pudo enviar la orden a preparación: '+(e.message||'Error desconocido'));
      return false;
    }
  }

  async function finalizarUITrasPagoRestaurante(contexto){
    if(!contexto || contexto.finalizado) return;
    contexto.finalizado=true;
    // Para llevar se envía a preparación DESPUÉS de confirmar el pago.
    // Así no aparece una orden en Cocina si el cliente abandona/cancela el pago.
    await registrarComandaPostPago(contexto);
    const imprimirPorCobro = debeImprimirTicketComanda() && (
      momentoTicketOperacion()==='cobrar' ||
      (contexto.servicio==='llevar' && momentoTicketOperacion()==='enviar')
    );
    if(imprimirPorCobro && contexto.ticket_snapshot){
      setTimeout(()=>imprimirTicketAutomatico(contexto.ticket_snapshot),250);
    }

    // Una vez pagada, ya no debe seguir apareciendo en Cuentas abiertas.
    try{
      await $.ajax({
        type:'POST',
        url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',
        dataType:'json',
        data:{action:'cerrarCuentaOperativa',factura_id:contexto.factura_id}
      });
    }catch(_){}

    if(contexto.servicio==='mesa' && contexto.liberar_mesa && contexto.mesa_id){
      try{
        const r=await $.ajax({type:'POST',url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',dataType:'json',data:{action:'liberarMesa',mesa_id:contexto.mesa_id}});
        if(!r || !(r.ok || r.status)) showAlert('warning','Mesa','La factura se pagó, pero no se pudo liberar la mesa automáticamente.');
      }catch(e){
        showAlert('warning','Mesa','La factura se pagó, pero no se pudo liberar la mesa automáticamente.');
      }
    }

    facturaActual=null;
    comandaItems=[];
    actualizarComandaUI();
    updateProductBadges();
    if(observacionesTextarea) observacionesTextarea.value='';
    clienteSeleccionado={id:1,nombre:'CONSUMIDOR FINAL',identificacion:''};
    pintarClienteInfoHeader();
    mesaSeleccionada=null;
    setServicioTipo('llevar');
    setMesaSeleccionadaUI(null);
    if(facturaTitle) facturaTitle.innerHTML=usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
    if(btnImprimir) btnImprimir.disabled=true;
    updateAccionPrincipalUI();
    await cargarMesas();
    contextoPagoRestaurante=null;
  }

  function instalarHookPagoRestaurante(){
    if(window.__REST_PAGO_HOOK_INSTALLED__) return;
    window.__REST_PAGO_HOOK_INSTALLED__=true;

    // 1) Compatibilidad: si main.php expone handleServerResponse, envolverlo.
    //    contexto.finalizado evita ejecutar dos veces el cierre de Restaurante.
    if(typeof window.handleServerResponse === 'function'){
      const original=window.handleServerResponse;
      window.handleServerResponse=function(resp){
        const ctx=contextoPagoRestaurante;
        const salida=original.apply(this,arguments);
        if(ctx && resp && resp.status===true && !ctx.finalizado){
          setTimeout(()=>finalizarUITrasPagoRestaurante(ctx),0);
        }
        return salida;
      };
    }

    // 2) Observador robusto del AJAX real de pagos.
    //    El flujo oficial de main.php usa $.ajax() sobre estos endpoints. No
    //    modificamos main.php: solo escuchamos una respuesta exitosa y, si
    //    pertenece a una venta de Restaurante actualmente en cobro, enviamos
    //    la comanda incremental y cerramos la cuenta operativa.
    $(document).off('ajaxSuccess.restPago').on('ajaxSuccess.restPago',function(_evt,xhr,settings,data){
      const ctx=contextoPagoRestaurante;
      if(!ctx || ctx.finalizado) return;

      const url=String((settings&&settings.url)||'').toLowerCase();
      const esPagoFactura = /addpagofacturas(?:efectivo|tarjeta|transferencia|cheque|puntos)ajax\.php/.test(url);
      if(!esPagoFactura) return;

      let resp=data;
      if(!resp || typeof resp!=='object'){
        resp=(xhr&&xhr.responseJSON)||null;
      }
      if((!resp || typeof resp!=='object') && xhr && xhr.responseText){
        try{resp=JSON.parse(xhr.responseText);}catch(_){resp=null;}
      }

      if(resp && resp.status===true){
        setTimeout(()=>finalizarUITrasPagoRestaurante(ctx),0);
      }
    });
  }

  function inyectarOpcionLiberarMesaPago(){
    const bar=document.getElementById('global_options_bar');
    if(!bar || document.getElementById('rest-liberar-mesa-option')) return;
    const wrap=document.createElement('div');
    wrap.className='option-item rest-release-option';
    wrap.id='rest-liberar-mesa-option';
    wrap.style.display='none';
    wrap.innerHTML=`<label class="payment-switch mb-0"><span class="switch-label">Liberar mesa al pagar</span><input type="checkbox" id="rest-liberar-mesa-switch" checked><span class="payment-slider round"></span></label><span class="question mb-0 ml-2">Sí</span>`;
    bar.appendChild(wrap);
    $(document).off('change.restRelease','#rest-liberar-mesa-switch').on('change.restRelease','#rest-liberar-mesa-switch',function(){
      const on=$(this).is(':checked');
      $('#rest-liberar-mesa-option .question').text(on?'Sí':'No');
      if(contextoPagoRestaurante) contextoPagoRestaurante.liberar_mesa=on;
    });
  }

  function prepararModalPagoContextual(contexto){
    inyectarOpcionLiberarMesaPago();
    const mesa=contexto && contexto.servicio==='mesa';
    $('#rest-liberar-mesa-option').toggle(!!mesa);
    $('#rest-liberar-mesa-switch').prop('checked',true).trigger('change.restRelease');
  }

  async function facturarConFlujoNormal(servicio){
    if(facturandoRestaurante) return;
    if(!Array.isArray(comandaItems)||!comandaItems.length){showAlert('warning','Sin productos','Agregue productos antes de cobrar.');return;}
    servicio = servicio==='mesa' ? 'mesa' : 'llevar';
    if(servicio==='mesa' && !mesaIdActual()){showAlert('warning','Mesa requerida','Seleccione una mesa antes de cobrar.');return;}
    if(Number(window.REST_COLABORADOR_ID||0)<=0){showAlert('error','Cajero no identificado','No se pudo identificar el colaborador de la sesión.');return;}

    facturandoRestaurante=true;
    const cobrarBtn = servicio==='mesa' ? document.getElementById('btn-cobrar-mesa') : document.getElementById('btn-guardar');
    setButtonBusy(cobrarBtn,true,'Preparando factura…');
    try{
      let facturaId = facturaIdActual();
      if(servicio==='mesa'){
        const saved=await guardarCuentaMesa({silencioso:true,imprimirAlEnviar:false,enviarComanda:false});
        facturaId=Number(saved.factura_id||facturaId||0);
      }

      contextoPagoRestaurante={
        servicio,
        factura_id:facturaId,
        mesa_id:servicio==='mesa'?mesaIdActual():0,
        liberar_mesa:servicio==='mesa',
        observaciones:String((observacionesTextarea&&observacionesTextarea.value)||'').trim(),
        ticket_snapshot:crearSnapshotTicket({factura_id:facturaId}),
        finalizado:false
      };
      prepararModalPagoContextual(contextoPagoRestaurante);

      const response=await $.ajax({
        type:'POST',
        url:BASE+'ajax/addFacturaAjax.php',
        data:construirPayloadFacturaNormal(facturaId).toString(),
        contentType:'application/x-www-form-urlencoded; charset=UTF-8',
        dataType:'text',
        timeout:20000
      });

      const realId=validarRespuestaFacturaNormal(response);
      contextoPagoRestaurante.factura_id=realId;
      if(contextoPagoRestaurante.ticket_snapshot) contextoPagoRestaurante.ticket_snapshot.factura_id=realId;
      facturaActual={id:realId,factura_id:realId,facturas_id:realId};

      // Para llevar: una vez que el controlador oficial ya creó la factura real,
      // registrar inmediatamente la orden de preparación. No dependemos del
      // callback posterior del modal de pago para que Cocina reciba la orden.
      if(servicio==='llevar' && debeEnviarPantallaComanda()){
        const envio=await $.ajax({
          type:'POST',
          url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',
          dataType:'json',
          timeout:15000,
          data:{
            action:'registrarComandaCocina',
            factura_id:realId,
            mesa_id:0,
            servicio:'llevar',
            comentarios:contextoPagoRestaurante.observaciones||''
          }
        });
        if(!envio || !envio.ok){
          throw new Error((envio&&(envio.msg||envio.message))||'La factura se creó, pero no se pudo enviar la orden a Cocina/Barra.');
        }
        contextoPagoRestaurante.comanda_enviada=true;
      }

      // IMPORTANTE: no ejecutar con eval() el script devuelto por addFacturaAjax.
      // Ese script pertenece a la vista normal de Facturas y puede intentar
      // resetear #formulario_facturacion, que no existe en Restaurante.
      // El backend oficial YA hizo la factura/numeración; desde aquí abrimos
      // solamente el flujo oficial de pago con el ID real generado.
      instalarHookPagoRestaurante();
      if(typeof pago !== 'function'){
        throw new Error('No está disponible el flujo oficial de pagos.');
      }
      pago(realId, 1, 'factura');
    }catch(e){
      contextoPagoRestaurante=null;
      showAlert('error','No se pudo facturar',e && e.message ? e.message : 'Ocurrió un error al registrar la factura.');
    }finally{
      facturandoRestaurante=false;
      setButtonBusy(cobrarBtn,false);
      updateAccionPrincipalUI();
    }
  }

  $(document).off('click.restCobrarMesa','#btn-cobrar-mesa').on('click.restCobrarMesa','#btn-cobrar-mesa',function(){
    facturarConFlujoNormal('mesa');
  });

  function guardarFactura(){
    if (!Array.isArray(comandaItems) || !comandaItems.length) {
      showAlert('warning','Sin productos','Agregue productos a la comanda.');
      return false;
    }
    const esLlevar=getServicioTipo()==='llevar';
    if(esLlevar){
      facturarConFlujoNormal('llevar');
      return false;
    }
    const mesa_id=mesaIdActual();
    if(!mesa_id){showAlert('warning','Mesa requerida','Debe seleccionar una mesa.');return false;}
    const tile=document.querySelector(`${MESA_TILE_SELECTOR}[data-mesa-id="${mesa_id}"]`);
    const estado=String((tile&&tile.getAttribute('data-estado'))||'disponible').toLowerCase();
    if(estado==='mantenimiento'){showAlert('warning','Mesa no disponible','La mesa se encuentra en mantenimiento.');return false;}

    showConfirm(facturaIdActual()?'Actualizar cuenta':'Enviar a cocina',
      facturaIdActual()?'¿Desea guardar los cambios de esta cuenta y actualizar la comanda?':'¿Desea guardar esta cuenta y enviarla a cocina?',
      ()=>{
        if(guardandoFactura) return;
        guardandoFactura=true;
        setButtonBusy(btnGuardar,true,facturaIdActual()?'Actualizando…':'Enviando…');
        guardarCuentaMesa({silencioso:false,imprimirAlEnviar:true,enviarComanda:true})
          .catch(e=>showAlert('error','Error',e.message||'No se pudo guardar la cuenta'))
          .finally(()=>{guardandoFactura=false;setButtonBusy(btnGuardar,false);updateAccionPrincipalUI();});
      });
    return false;
  }

  function cerrarFactura() {
    if (!facturaActual || !(facturaActual.id || facturaActual.factura_id)) {
      showAlert('warning','Advertencia','No hay factura abierta'); 
      return; 
    }
    const fid = facturaActual.id || facturaActual.factura_id;
  
    showConfirm('Cancelar cuenta', '¿Desea cancelar esta cuenta abierta? Se retirará de Cuentas abiertas y, si tenía mesa, la mesa quedará disponible. La cuenta no se elimina físicamente: queda marcada como cancelada para conservar trazabilidad.', () => {
      fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=closeFactura&factura_id=${encodeURIComponent(fid)}`
      })
        .then(r => r.json())
        .then(data => {
          if (!data.status) { showAlert('error','Error', data.message || 'Error al cerrar la factura'); return; }
  
          showAlert('success','Cuenta cancelada','La cuenta fue cancelada correctamente.');
          facturaActual = null;
          comandaItems = [];
          actualizarComandaUI();
  
          // UI neutral
          setServicioTipo('llevar');      
          setMesaSeleccionadaUI(null);    
          if (facturaTitle) facturaTitle.innerHTML = usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
          if (btnImprimir) btnImprimir.disabled = true;
  
          cargarMesas();
        })
        .catch(() => { showAlert('error','Error','Error al cerrar la factura'); });
    });
  }  

  function limpiarComanda() { comandaItems = []; actualizarComandaUI(); }

  // ======= ISV del modal de Producto =======
  function prepararModalProductoISV(valoresEdicion = null){
    const chk1 = document.getElementById('prod-isv1');
    const chk2 = document.getElementById('prod-isv2');

    const tieneEdicion = valoresEdicion && typeof valoresEdicion === 'object';
    const editISV1 = tieneEdicion ? (parseInt(valoresEdicion.isv1 || 0, 10) === 1) : null;
    const editISV2 = tieneEdicion ? (parseInt(valoresEdicion.isv2 || 0, 10) === 1) : null;

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

    return fetchWithTimeout(`${SERVERURL}core/productos/getIsvConfig.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r=>r.json())
    .then(res=>{
      if (!res || !res.success) throw new Error('Respuesta inválida');
      if (chk1){
        chk1.checked = tieneEdicion ? editISV1 : (Number(res.isv1.activar) === 1);
        setIsvLabelSingleLine(chk1, res.isv1.valor);
      }
      if (chk2){
        chk2.checked = tieneEdicion ? (editISV2 && !editISV1) : (Number(res.isv2.activar) === 1);
        setIsvLabelSingleLine(chk2, res.isv2.valor);
      }
      aplicarSeleccionExclusivaISVProducto();
      if (chk1 && chk2 && chk1.checked && chk2.checked){ chk2.checked = false; }
      return true;
    })
    .catch(()=>{
      setIsvLabelSingleLine(chk1, isvRates[1] || 0);
      setIsvLabelSingleLine(chk2, isvRates[2] || 0);
      if (tieneEdicion) {
        if (chk1) chk1.checked = editISV1;
        if (chk2) chk2.checked = editISV2 && !editISV1;
      }
      aplicarSeleccionExclusivaISVProducto();
      return false;
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

  // ===== Edición: abrir modal de PRODUCTO (precargado, con foco correcto)
  function abrirEdicionProducto(prod){
    // Contexto de edición
    editContext.productoId = prod?.productos_id || '';

    // Limpiar validación previa
    limpiarValidacionFormulario('form-producto');

    // Referencias de controles
    const { selCat, inpNombre, inpDesc, inpPrecio, chkISV1, chkISV2 } = getProdControls();

    // Precargar campos base
    if (inpNombre) inpNombre.value = prod?.nombre || '';
    if (inpDesc)   inpDesc.value   = prod?.descripcion || '';
    if (inpPrecio) inpPrecio.value = (parseFloat(prod?.precio_venta)||0).toFixed(2);
    if (chkISV1)   chkISV1.checked = parseInt(prod?.isv1,10) === 1;
    if (chkISV2)   chkISV2.checked = (parseInt(prod?.isv2,10) === 1) && !chkISV1?.checked;

    // Hidden id
    const hid = document.getElementById('prod-id');
    if (hid) hid.value = String(prod?.productos_id || '');

    // Título
    const ttl = document.getElementById('titulo-modal-producto');
    if (ttl) ttl.textContent = 'Editar Producto';

    // Estación real del producto. La categoría es independiente.
    const est = normalizeEstacion(prod);
    const realEst = (est === 'barra') ? 'barra' : 'cocina';
    const radio = document.querySelector(`#prod-estacion input[value="${realEst}"]`);
    if (radio) radio.checked = true;

    const categoriaIdEdicion = String(prod?.categoria_id ?? '');
    const setCategoriaSeleccionada = () => {
      if (!selCat || !categoriaIdEdicion) return;
      selCat.value = categoriaIdEdicion;
      if (window.jQuery) {
        window.jQuery(selCat).val(categoriaIdEdicion).trigger('change');
      } else {
        selCat.dispatchEvent(new Event('change', { bubbles:true }));
      }
    };

    ensureCategoriasReady()
      .then(()=>fillProdCategoriaOptionsByEstacion(categoriaIdEdicion))
      .then(()=>setCategoriaSeleccionada())
      .catch(()=>setCategoriaSeleccionada());

    // Imagen (preview si existe)
    try {
      if (typeof resetProductoImagen === 'function') resetProductoImagen();
      setTimeout(() => {
        if (!prod?.file_name) return;

        const preview = document.getElementById('productoPreview');
        const info    = document.getElementById('productoInfo');
        if (!preview || !info) return;

        preview.innerHTML = '';
        const img = document.createElement('img');
        img.src = `${SERVERURL}vistas/plantilla/img/products/${prod.file_name}?${Date.now()}`;
        img.alt = prod?.nombre || '';
        preview.appendChild(img);

        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn-remove-image';
        removeBtn.type = 'button';
        removeBtn.title = 'Eliminar imagen';
        removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
        removeBtn.addEventListener('click', e => {
          e.stopPropagation();
          if (typeof resetProductoImagen === 'function') resetProductoImagen();
        });
        preview.appendChild(removeBtn);

        preview.style.display = 'block';
        info.textContent = prod.file_name;
      }, 50);
    } catch(_) {}

    // Preparar toggles/ISV y uploader si aplica
    try { if (typeof prepararModalProductoISV === 'function') prepararModalProductoISV(prod); } catch(_) {}
    try { if (typeof initProductoImageUpload   === 'function') initProductoImageUpload();   } catch(_) {}

    // Mostrar modal
    if (typeof modalProducto !== 'undefined' && modalProducto) {
      modalProducto.style.display = 'block';
    }

    // 👉 Foco + selección en el primer input (nombre), ya con el modal abierto
    setTimeout(() => {
      if (inpNombre) {
        inpNombre.focus({ preventScroll:true });
        inpNombre.select?.();
      }
    }, 60);
  }

  function abrirEdicionCategoria(cat){
    editContext.categoriaId = cat.id;
    const inp = document.getElementById('cat-nombre');
    const hid = document.getElementById('cat-id');
  
    if (inp) inp.value = cat.nombre || '';
    if (hid) hid.value = String(cat.id || '');
  
    const titulo = document.getElementById('titulo-modal-categoria');
    if (titulo) titulo.textContent = 'Editar Categoría';
  
    // Establecer estación correcta
    const estacionActual = normalizeEstacion(cat);
    const radioEstacion = document.querySelector(`#form-categoria input[name="catEstacion"][value="${estacionActual}"]`);
    if (radioEstacion) radioEstacion.checked = true;
  
    // Limpiar validación antes de mostrar
    limpiarValidacionFormulario('form-categoria');
  
    // Mostrar modal
    if (modalCategoria) modalCategoria.style.display = 'block';
  
    // 👉 Foco inmediato al input nombre
    if (inp) {
      setTimeout(() => {
        inp.focus({ preventScroll: true });
        inp.select?.(); // selecciona el texto
      }, 50); // pequeño delay por el render del modal
    }
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
        showNotify('error', 'Error', 'No puede realizar esta accion a las facturas canceladas!');
        resetProductoImagen();
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
          showNotify('error', 'Error', 'La imagen no debe exceder 2MB');
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
    return fetchWithTimeout(`${SERVERURL}core/productos/uploadImagenProducto.php`, {
      method: 'POST',
      body: fd
    }).then(r=>r.json())
      .then(j=> !!(j && j.status))
      .catch(()=>false);
  }

// ======== GESTIÓN DE COMBOS (v2, alineado a BD) ========

// --- Config UI ---
const UNIDADES_COMBO = ['und','porción','g','kg','ml','l'];
const MAX_COMPONENTES = 100;

// Abre modal principal (listado) y refresca
function abrirModalCombos(){
  if (!modalCombos) return;
  modalCombos.style.display = 'block';
  cargarCombos();
}

// Lista combos (grid)
function cargarCombos(){
  return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=loadCombos'
  })
  .then(r=>r.json())
  .then(data=>{
    if (!data || !data.status){
      showAlert('error','Error','No se pudieron cargar los combos');
      return;
    }
    combos = data.combos || [];

    window.__comboProductoIds = new Set((combos || []).map(c => parseInt(c.productos_id || c.producto_id, 10)).filter(Boolean));
    
    renderizarCombos();
  })
  .catch(()=> showAlert('error','Error','Error al cargar combos'));
}

// Render tarjetas de combos
function renderizarCombos(){
  if (!combosGrid) return;
  combosGrid.innerHTML = '';

  // 🔹 Construye (o actualiza) el Set global de productos que SON combos
  //    Lo usamos luego al pintar la grilla de productos para mostrar el badge.
  try {
    window.__comboProductoIds = new Set((combos || [])
      .map(c => parseInt(c.productos_id || c.producto_id, 10))
      .filter(Boolean));
  } catch(e){
    window.__comboProductoIds = new Set();
  }

  if (!combos || !combos.length){
    combosGrid.innerHTML = `
      <div class="state-empty">
        <div class="icon"><i class="fas fa-layer-group"></i></div>
        <h4>No hay combos configurados</h4>
        <p>Crea tu primer combo con el botón "Nuevo combo"</p>
      </div>`;
    return;
  }

  const fmt = (n)=> new Intl.NumberFormat('es-HN',{minimumFractionDigits:2, maximumFractionDigits:2}).format(n);

  combos.forEach(c => {
    const comboId   = c.combo_id || c.id;
    const maestroId = c.productos_id || c.producto_id;
    const prod      = findProductoById(maestroId) || { nombre: c.nombre_combo || `Producto #${maestroId}`, precio_venta: 0 };
    const nombre    = c.nombre_combo || prod.nombre || `Combo #${comboId}`;
    // Si combo.precio_venta es NULL => usa precio del producto padre
    const precio    = (c.precio_venta===null || typeof c.precio_venta==='undefined')
                        ? (prod.precio_venta ? `L ${fmt(parseFloat(prod.precio_venta))}` : '-')
                        : `L ${fmt(parseFloat(c.precio_venta))}`;
    const activo    = (String(c.activo)==='1' || c.activo===true);
    const version   = c.version_actual ? `v${c.version_actual}` : '';

    const card = document.createElement('div');
    card.className = 'combo-card';
    card.innerHTML = `
      <div class="combo-card-header">
        <h4 class="combo-card-title">${nombre} <small class="text-muted">${version}</small></h4>
        <div class="combo-card-status ${activo ? 'active' : 'inactive'}">
          <span class="status-indicator ${activo ? 'active' : 'inactive'}"></span>
          ${activo ? 'Activo' : 'Inactivo'}
        </div>
      </div>
      <div class="combo-card-body">
        <div class="combo-card-info">
          <div class="combo-card-price">${precio}</div>
          <div class="combo-card-items">${c.items_count ?? (c.componentes_resumen || '-') } componentes</div>
          <div class="combo-card-producto">Maestro: <strong>${prod.nombre}</strong></div>
        </div>
      </div>
      <div class="combo-card-actions">
        <button class="btn btn-sm btn-primary" data-action="edit" data-id="${comboId}">
          <i class="fas fa-edit"></i> Editar
        </button>
        <button class="btn btn-sm btn-danger" data-action="delete" data-id="${comboId}">
          <i class="fas fa-trash"></i> Eliminar
        </button>
      </div>`;
    combosGrid.appendChild(card);
  });

  combosGrid.onclick = (e)=>{
    const btn = e.target.closest('button[data-action]');
    if(!btn) return;
    const id = btn.getAttribute('data-id');
    const action = btn.getAttribute('data-action');
    if (action==='edit')   abrirEditorComboExistente(id);
    if (action==='delete') eliminarCombo(id);
  };
}

// ----- Editor de Combo -----

function abrirEditorComboNuevo() {
  if (!modalComboEditor) return;

  setComboEditorTitle('Nuevo combo');
  setComboEditorIds('', 1, '');   // comboId vacío, activo=1, sin maestro
  clearComboItemsContainer();
  fillComboProductoOptions(null, null); // llena selector maestro excluyendo ya usados
  setPrecioComboUI(null);          // null => hereda precio del producto maestro
  addComboItemRow();

  const help = document.getElementById('combo-help-message');
  if (help){
    help.innerHTML = `
      <div class="combo-guide">
        <div class="combo-guide-step"><span>1</span><div><strong>Elige el producto combo</strong><small>Es el producto que verá y cobrará el cajero.</small></div></div>
        <div class="combo-guide-step"><span>2</span><div><strong>Agrega sus componentes</strong><small>Define productos, cantidades, unidad y merma.</small></div></div>
        <div class="combo-guide-step"><span>3</span><div><strong>Configura opciones</strong><small>Solo si el cliente puede elegir entre categorías.</small></div></div>
      </div>`;
  }
  renderReglasCategoria([]);

  modalComboEditor.style.display = 'block';
  
  // Añade estas líneas:
  setTimeout(() => {
      reinitSelect2InModal('#modal-combo-editor');
      $('#combo-producto').trigger('focus');
  }, 150);
}

// Llamar esta función cuando se cierre cualquier modal
$(document).on('click', '.close, [data-close]', function() {
  // Cerrar el modal
  const target = $(this).data('close');
  if (target) {
      $(target).css('display', 'none');
  }
  
  // Destruir Select2 para liberar memoria
  $('select.select2').each(function() {
      if ($(this).data('select2')) {
          $(this).select2('destroy');
      }
  });
});

function abrirEditorComboExistente(comboId){
  // Busca info básica del combo en la lista ya cargada
  const combo = combos.find(x => parseInt(x.combo_id||x.id,10) === parseInt(comboId,10));
  const maestroPid = combo ? (combo.productos_id || combo.producto_id) : null;
  const activo = combo ? (String(combo.activo)==='1' ? 1 : 0) : 1;
  // Si el combo tiene precio propio -> número; si hereda -> null/undefined
  const precioCombo = (combo && combo.hasOwnProperty('precio_venta')) ? combo.precio_venta : null;

  // Carga el detalle de componentes
  fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=loadComboDetalle&combo_id=${encodeURIComponent(comboId)}`
  })
  .then(r=>r.json())
  .then(data=>{
    if (!data || !data.status){
      showAlert('error','Error','No se pudo cargar el detalle del combo');
      return;
    }

    const items = data.combo_detalle || [];

    // Configura encabezado/estado/maestro
    setComboEditorTitle('Editar combo');
    setComboEditorIds(comboId, activo, maestroPid);               // setea hidden del padre
    clearComboItemsContainer();
    fillComboProductoOptions(maestroPid, comboId);                 // llena select del padre
    initSelect2All();
    setPrecioComboUI(precioCombo);                                 // precio propio vs heredado

    // Pinta filas
    if (Array.isArray(items) && items.length){
      items.sort((a,b)=> (parseInt(a.orden||1)-parseInt(b.orden||1)));
      items.forEach(it => addComboItemRow({
        productos_id: it.productos_id,
        cantidad_por_porcion: it.cantidad_por_porcion,
        unidad: it.unidad || '',
        merma_pct: it.merma_pct || 0,
        obligatorio: (String(it.obligatorio)==='1'),
        precio_extra: it.precio_extra || 0,
        orden: it.orden || 1
      }));
    } else {
      addComboItemRow();
    }
    
    // 🔒 FILTRO DURO: oculta el padre en TODAS las filas ya pintadas
    refiltrarComponentesContraPadre();

     // 🔹 Cargar y pintar reglas por categoría
    cargarReglasCombo(comboId).then(reglas => renderReglasCategoria(reglas));

    const help = document.getElementById('combo-help-message');
    if (help){
      help.innerHTML = `
        <div class="combo-guide combo-guide--edit">
          <div class="combo-guide-step"><span><i class="fas fa-edit"></i></span><div><strong>Editando combo</strong><small>Actualiza componentes, precio u opciones sin cambiar el producto maestro.</small></div></div>
        </div>`;
    }

    // Muestra modal
    if (modalComboEditor) modalComboEditor.style.display = 'block';
  })
  .catch(()=> showAlert('error','Error','Error al cargar el detalle del combo'));
}

function setComboEditorTitle(text){
  const el = document.getElementById('titulo-modal-combo');
  if (el) el.textContent = text;
}

// comboId + switch activo + maestro (hidden en edición o select en creación)
function setComboEditorIds(comboId, activo, productos_id){
  const hid = document.getElementById('combo-id');
  if (hid) hid.value = comboId ? String(comboId) : '';

  // Hidden maestro (edición) para mantener relación
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
      <span class="switch-label">Combo activo</span>`;
  }

  // Mostrar maestro en edición
  const productoDisplay = document.getElementById('combo-producto-display');
  const productoSelectContainer = document.getElementById('combo-producto-container');

  if (productos_id && comboId) {
    const producto = findProductoById(productos_id);
    if (producto && productoDisplay) {
      productoDisplay.innerHTML = `
        <div class="selected-product-display">
          <strong>Producto maestro:</strong>
          <span class="product-name-highlight">${producto.nombre}</span>
        </div>
        <p class="help-text"><small>Este producto representa el combo en ventas.</small></p>`;
      productoDisplay.style.display = 'block';
    }
    if (productoSelectContainer) productoSelectContainer.style.display = 'none';
  } else {
    if (productoDisplay) productoDisplay.style.display = 'none';
    if (productoSelectContainer) productoSelectContainer.style.display = 'block';
  }
}

// Precio del combo: null => hereda del producto maestro; número => precio propio
function setPrecioComboUI(precio){
  const wrap = document.getElementById('combo-precio-wrap');
  if (!wrap) return;

  const propio = (precio !== null && precio !== undefined && precio !== '');
  wrap.innerHTML = `
    <div class="combo-price-header">
      <span class="combo-price-icon"><i class="fas fa-tags"></i></span>
      <div><strong>Precio del combo</strong><small>Usa el precio del producto maestro o define uno especial.</small></div>
    </div>
    <div class="combo-price-options">
      <label class="combo-price-option">
        <input type="radio" name="comboPrecioModo" value="heredar" ${!propio ? 'checked':''}>
        <span><i class="fas fa-link"></i><strong>Heredar precio</strong><small>Usar el precio actual del producto maestro.</small></span>
      </label>
      <label class="combo-price-option">
        <input type="radio" name="comboPrecioModo" value="propio" ${propio ? 'checked':''}>
        <span><i class="fas fa-dollar-sign"></i><strong>Precio propio</strong><small>Definir un precio exclusivo para este combo.</small></span>
      </label>
      <div class="combo-price-input">
        <label for="combo-precio-valor">Precio especial</label>
        <input type="number" class="form-control" id="combo-precio-valor" min="0" step="0.01" placeholder="0.00" ${!propio ? 'disabled':''} value="${propio ? Number(precio).toFixed(2) : ''}">
      </div>
    </div>
  `;

  const radios = wrap.querySelectorAll('input[name="comboPrecioModo"]');
  const inputPrecio = document.getElementById('combo-precio-valor');
  radios.forEach(r=>{
    r.addEventListener('change', ()=>{
      const esPropio = r.checked && r.value === 'propio';
      if (!r.checked) return;
      inputPrecio.disabled = !esPropio;
      if (esPropio){
        if (!inputPrecio.value) inputPrecio.value = '0.00';
        inputPrecio.focus();
        inputPrecio.select?.();
      } else {
        inputPrecio.value = '';
      }
    });
  });
}

// Llena el select del producto maestro (excluye los ya usados por otros combos)
// === Helpers para saber qué productos ya son padre de un combo
function getUsadosComoPadreSet(currentComboId){
  const usados = new Set();
  (combos || []).forEach(c=>{
    const cid = String(c.combo_id || c.id || '');
    const pid = String(c.productos_id || c.producto_id || '');
    if (!pid) return;
    // Si estoy editando, permito el padre del combo actual
    if (currentComboId && String(currentComboId) === cid) return;
    usados.add(pid);
  });
  return usados;
}

function isProductoUsadoComoPadre(pid, currentComboId){
  return getUsadosComoPadreSet(currentComboId).has(String(pid));
}

// === Llenar el select del producto maestro (mostrar todos, marcar los ya usados)
function fillComboProductoOptions(selectedProductoId, currentComboId){
  const sel = document.getElementById('combo-producto');
  if (!sel) return;

  // Productos ya usados como padre por otros combos (excepto el actual al editar)
  const usados = getUsadosComoPadreSet(currentComboId);

  const opts = productos.map(p=>{
    const idStr = String(p.productos_id);
    const selected = (String(selectedProductoId) === idStr) ? 'selected' : '';
    const disabled = (usados.has(idStr) && !selected) ? 'disabled' : '';
    const badge = (usados.has(idStr) && !selected) ? ' (ya es combo)' : '';
    return `<option value="${p.productos_id}" ${selected} ${disabled}>${p.nombre}${badge}</option>`;
  }).join('');

  sel.innerHTML = `<option value=""></option>${opts}`;
  if (selectedProductoId) sel.value = String(selectedProductoId);

  // Select2 para que el dropdown quede dentro del modal
  if (typeof $ !== 'undefined' && $.fn.select2){
    const $modal = $('#modal-combo-editor');
    $(sel).select2({ width:'100%', dropdownParent: $modal });
  }

  // Cuando cambie el padre, actualiza hidden + refiltra componentes
  sel.onchange = ()=>{
    const hid = document.getElementById('combo-producto-hidden');
    if (hid) hid.value = sel.value || '';
    refiltrarComponentesContraPadre();
  };
}

// Re-filtra TODAS las filas de componentes para ocultar el producto padre actual
function refiltrarComponentesContraPadre(){
  // Padre vigente: usa hidden (edición) o select (creación)
  const padreId = (document.getElementById('combo-producto-hidden')?.value)
               || (document.getElementById('combo-producto')?.value) || '';

  const rows = document.querySelectorAll('#combo-items-container .component-row');
  rows.forEach(row=>{
    const selProd = row.querySelector('select.combo-item-producto');
    if (!selProd) return;

    // Valor actual antes de reconstruir opciones
    const valorPrevio = selProd.value || '';

    // Reconstruye opciones EXCLUYENDO al padre
    const opciones = productos
      .filter(p => String(p.productos_id) !== String(padreId))
      .map(p => `<option value="${p.productos_id}">${p.nombre}</option>`)
      .join('');

    selProd.innerHTML = `<option value=""></option>${opciones}`;

    // Restaura selección si no era el padre; si era el padre, se limpia
    if (valorPrevio && String(valorPrevio) !== String(padreId)){
      selProd.value = valorPrevio;
    } else {
      selProd.value = '';
    }

    // Refresca Select2 si está activo
    if (typeof $ !== 'undefined' && $.fn.select2){
      $(selProd).trigger('change.select2');
    }
  });
}

function calcularDisponibilidadComboUI(comboId, cantidad=1){
  if (!comboId){
    showAlert('info','Info','Guarda el combo primero para calcular disponibilidad.');
    return;
  }
  const fd = new FormData();
  fd.append('action','calcComboDisponibilidad');
  fd.append('combo_id', String(comboId));
  fd.append('cantidad', String(cantidad));

  fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(res=>{
      if(!res || !res.status){ showAlert('error','Error',res?.message||'No se pudo calcular'); return; }
      showAlert('success','Disponibilidad', `Disponibles: ${res.disponibles} | ¿Alcanza para ${cantidad}? ${res.alcanza_para.toUpperCase()}`);
    })
    .catch(()=> showAlert('error','Error','Error al consultar disponibilidad'));
}

// Limpia contenedor de componentes
function clearComboItemsContainer(){
  const container = document.getElementById('combo-items-container');
  if (container) container.innerHTML = '';
}

function addComboItemRow(data){
  const container = document.getElementById('combo-items-container');
  if (!container) return;

  if (container.children.length >= MAX_COMPONENTES){
    showAlert('warning','Máximo alcanzado',`Solo se permiten ${MAX_COMPONENTES} componentes por combo.`);
    return;
  }

  const idx = container.children.length + 1;
  const d = Object.assign({
    productos_id: '',
    cantidad_por_porcion: 1,
    unidad: 'und',
    merma_pct: 0,
    obligatorio: true,
    precio_extra: 0,
    orden: idx
  }, data || {});

  // Padre actual (hidden o select)
  const productoMaestroId =
      (document.getElementById('combo-producto-hidden')?.value) ||
      (document.getElementById('combo-producto')?.value) || '';

  // Lista filtrada: NUNCA incluir el padre
  const productosFiltrados = productos.filter(p =>
    String(p.productos_id) !== String(productoMaestroId)
  );

  const options = productosFiltrados.map(p => {
    const sel = (String(p.productos_id) === String(d.productos_id)) ? 'selected' : '';
    return `<option value="${p.productos_id}" ${sel}>${p.nombre}</option>`;
  }).join('');

  const unidadOpts = UNIDADES_COMBO.map(u=>{
    const sel = (String(u).toLowerCase()===String(d.unidad||'').toLowerCase()) ? 'selected':'';
    return `<option value="${u}" ${sel}>${u}</option>`;
  }).join('');

  const row = document.createElement('div');
  row.className = 'component-row card';
  row.innerHTML = `
    <div class="component-header">
      <h5>Componente #${idx}</h5>
      <button type="button" class="btn btn-sm btn-danger" data-remove-row="1" title="Quitar">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="component-body">
      <!-- PRODUCTO -->
      <div class="form-group">
        <label>Producto <small class="text-muted">(insumo o artículo hijo)</small></label>
        <select class="combo-item-producto select2" data-placeholder="Selecciona un producto">
          <option value=""></option>
          ${options}
        </select>
      </div>

      <!-- CANTIDAD -->
      <div class="form-group">
        <label>Cantidad por porción</label>
        <input type="number" class="form-control combo-item-cantidad" min="0.0001" step="0.0001" value="${d.cantidad_por_porcion}">
      </div>

      <!-- UNIDAD -->
      <div class="form-group">
        <label>Unidad</label>
        <select class="form-control combo-item-unidad">
          ${unidadOpts}
        </select>
      </div>

      <!-- MERMA -->
      <div class="form-group">
        <label>Merma (%) <small class="text-muted">Ej.: 10 = consumir 10% extra (pérdidas)</small></label>
        <input type="number" class="form-control combo-item-merma" min="0" max="100" step="0.01" value="${Number(d.merma_pct).toFixed(2)}">
      </div>

      <!-- OBLIGATORIO -->
      <div class="form-group">
        <label>Componente obligatorio</label>
        <label class="checkbox-container" style="margin-top:4px;">
          <input type="checkbox" class="combo-item-obligatorio" ${d.obligatorio ? 'checked' : ''}>
          <span class="checkmark"></span>
          <span style="margin-left:6px;">Siempre incluido</span>
        </label>
      </div>

      <!-- PRECIO EXTRA -->
      <div class="form-group">
        <label>Precio extra <small class="text-muted">(si aplica)</small></label>
        <input type="number" class="form-control combo-item-extra" min="0" step="0.01" value="${Number(d.precio_extra).toFixed(2)}">
      </div>

      <!-- ORDEN -->
      <div class="form-group">
        <label>Orden</label>
        <input type="number" class="form-control combo-item-orden" min="1" step="1" value="${d.orden}">
      </div>
    </div>
  `;
  container.appendChild(row);

  appendInlineHelpsForComboRow(row);

  // Quitar fila
  row.querySelector('[data-remove-row]')?.addEventListener('click', ()=>{
    row.remove();
    reindexComboItems();
  });

  // Activa Select2 en la fila
  initSelect2ForComboRow(row);
  reindexComboItems();
}

  // Pegarlo en facturasRestaurante.js (por ejemplo, debajo de addComboItemRow)
  function appendInlineHelpsForComboRow(row){
    const defs = [
      ['.combo-item-producto',      'Insumo que compone el combo y se descuenta del inventario.'],
      ['.combo-item-cantidad',      'Consumo por 1 combo. Debe ser > 0.'],
      ['.combo-item-unidad',        'Unidad compatible con tu inventario (g, ml, und, etc.).'],
      ['.combo-item-merma',         'Pérdida prevista. 10 = +10% al consumo para cubrir desperdicio.'],
      ['.combo-item-extra',         'Si el componente es opcional y se elige, suma este monto.'],
      ['.combo-item-obligatorio',   'Si está marcado, siempre se incluye y descuenta.'],
      ['.combo-item-orden',         'Posición de visualización del componente.']
    ];

    defs.forEach(([selector, help])=>{
      const input = row.querySelector(selector);
      if (!input) return;

      const group = input.closest('.form-group');
      const label = group ? group.querySelector('label') : null;

      // small debajo del label
      if (label && !label.querySelector('.help-inline')){
        const sm = document.createElement('small');
        sm.className = 'help-inline text-muted';
        sm.textContent = help;
        label.appendChild(sm);
      }
      // tooltip nativo
      input.setAttribute('title', help);
    });
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

// Recolecta items listos para enviar a combo_detalle
function collectComboItems(){
  const rows = document.querySelectorAll('#combo-items-container .component-row');
  const items = [];
  for (let r of rows){
    const prodSel   = r.querySelector('select.combo-item-producto');
    const producto_id = prodSel ? (prodSel.value || '') : '';
    if (!producto_id) continue;

    const cantidad   = parseFloat((r.querySelector('.combo-item-cantidad')||{}).value || '1') || 1;
    const unidad     = (r.querySelector('.combo-item-unidad')||{}).value || 'und';
    const merma      = parseFloat((r.querySelector('.combo-item-merma')||{}).value || '0') || 0;
    const obligatorio= (r.querySelector('.combo-item-obligatorio')||{}).checked ? 1 : 0;
    const precioExtra= parseFloat((r.querySelector('.combo-item-extra')||{}).value || '0') || 0;
    const orden      = parseInt((r.querySelector('.combo-item-orden')||{}).value || '1',10) || 1;

    items.push({
      productos_id: parseInt(producto_id,10),
      cantidad_por_porcion: cantidad,
      unidad: unidad || null,
      merma_pct: Math.max(0, Math.min(100, merma)),
      obligatorio,
      precio_extra: precioExtra,
      orden
    });
  }
  return items;
}

// ====== Reglas por categoría (UI) ======
function renderReglasCategoria(reglas = []) {
  const body = document.getElementById('combo-reglas-rows');
  if (!body) return;

  const opts = (Array.isArray(categorias) ? categorias : []).map(c => {
    const id = c.id || c.categoria_id || c.categoriaId || '';
    const nombre = c.nombre || c.text || '';
    return `<option value="${id}">${escapeHtml(nombre)}</option>`;
  }).join('');

  body.innerHTML = '';

  if (!reglas || !reglas.length) {
    body.innerHTML = `
      <div class="combo-rules-empty">
        <i class="fas fa-sliders-h"></i>
        <div>
          <strong>Sin reglas especiales</strong>
          <small>Agrega una regla solo si deseas limitar cuántos productos puede elegir el cliente de una categoría.</small>
        </div>
      </div>`;
    return;
  }

  (reglas || []).forEach((r, index) => {
    const row = document.createElement('div');
    row.className = 'combo-rule-row';
    row.innerHTML = `
      <div class="combo-rule-number">${index + 1}</div>
      <div class="form-group">
        <label>Categoría</label>
        <select class="form-control regla-categoria">
          <option value="">Seleccione una categoría…</option>
          ${opts}
        </select>
      </div>
      <div class="form-group combo-rule-max">
        <label>Máx. selección</label>
        <input type="number" class="form-control regla-max" min="1" value="${parseInt(r.max_seleccion||1,10)}">
      </div>
      <button type="button" class="btn btn-danger btn-sm combo-rule-remove" data-remove-regla="1" title="Quitar regla">
        <i class="fas fa-trash"></i>
      </button>
    `;
    body.appendChild(row);
    const sel = row.querySelector('.regla-categoria');
    if (sel) sel.value = String(r.categoria_id || '');
  });

  body.onclick = (e)=>{
    const btn = e.target.closest('button[data-remove-regla="1"]');
    if (!btn) return;
    const row = btn.closest('.combo-rule-row');
    if (row) row.remove();
    if (!body.querySelector('.combo-rule-row')) renderReglasCategoria([]);
  };
}

function collectReglasCategoria(){
  const rows = Array.from(document.querySelectorAll('#combo-reglas-rows .combo-rule-row'));
  return rows.map(row => {
    const cat = row.querySelector('.regla-categoria')?.value || '';
    const max = parseInt(row.querySelector('.regla-max')?.value || '1', 10);
    if (!cat) return null;
    return { categoria_id: parseInt(cat,10), max_seleccion: isNaN(max) ? 1 : Math.max(1, max) };
  }).filter(Boolean);
}

function cargarReglasCombo(comboId){
  return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=loadComboCategoriaReglas&combo_id=${encodeURIComponent(comboId)}`
  })
  .then(r=>r.json())
  .then(d => (d && d.status) ? (d.reglas || []) : []);
}

// Botón Añadir regla
(function(){
  const btnAddRegla = document.getElementById('btn-add-regla');
  if (btnAddRegla){
    btnAddRegla.addEventListener('click', ()=>{
      const cur = collectReglasCategoria();
      // intenta seleccionar la primera categoría disponible por defecto
      const primeraCatId = (Array.isArray(categorias) && categorias.length) ? (categorias[0].id || categorias[0].categoria_id) : null;
      cur.push({ categoria_id: primeraCatId, max_seleccion: 1 });
      renderReglasCategoria(cur);
    });
  }
})();

// Validaciones adicionales
function validarComponentesComboV2(items, maestroId){
  if (!items.length) return { ok:false, msg:'Agregue al menos un componente.' };
  const seen = new Set();
  for (const it of items){
    if (String(it.productos_id) === String(maestroId)){
      return { ok:false, msg:'El producto maestro no puede estar entre los componentes.' };
    }
    const key = String(it.productos_id);
    if (seen.has(key)){
      return { ok:false, msg:'Hay productos repetidos entre los componentes.' };
    }
    seen.add(key);
    if (!(it.cantidad_por_porcion>0)){
      return { ok:false, msg:'Todas las cantidades deben ser mayores a 0.' };
    }
    if (it.precio_extra<0){
      return { ok:false, msg:'El precio extra no puede ser negativo.' };
    }
    if (it.merma_pct<0 || it.merma_pct>100){
      return { ok:false, msg:'La merma debe estar entre 0 y 100%.' };
    }
  }
  return { ok:true };
}

// Guardar / Actualizar combo
function guardarCombo(){
  const comboId = (document.getElementById('combo-id')||{}).value || '';

  // Maestro (hidden en edición o select en creación)
  const productos_id =
    comboId
      ? (document.getElementById('combo-producto-hidden')?.value || '')
      : (document.getElementById('combo-producto')?.value || '');

  const activoSwitch = document.getElementById('combo-activo-switch');
  const activo = activoSwitch ? (activoSwitch.checked ? 1 : 0) : 1;

  if (!productos_id){
    showAlert('warning','Validación','Seleccione el producto maestro del combo');
    return;
  }

  const items = collectComboItems();
  const valid = validarComponentesComboV2(items, productos_id);
  if (!valid.ok){
    showAlert('warning','Validación', valid.msg);
    return;
  }

  // Precio del combo (heredado vs propio)
  const modo = document.querySelector('input[name="comboPrecioModo"]:checked')?.value || 'heredar';
  const precioInput = document.getElementById('combo-precio-valor');
  let precio_venta = null;
  if (modo === 'propio'){
    const v = parseFloat(precioInput?.value || '0');
    if (isNaN(v) || v < 0){
      showAlert('warning','Validación','Precio del combo inválido');
      return;
    }
    precio_venta = Number(v.toFixed(2));
  } else {
    precio_venta = null; // heredado
  }

  const accion = comboId ? 'editar' : 'guardar';
  const mensaje = comboId ? '¿Desea actualizar este combo?' : '¿Desea guardar este combo?';

  showConfirm(accion==='editar' ? 'Editar Combo' : 'Nuevo Combo', mensaje, async () => {
    const btn = document.getElementById('btn-guardar-combo');
    if (btn && btn.disabled) return;

    const payload = {
      productos_id: parseInt(productos_id,10),
      activo,
      precio_venta,
      items,
      reglas: collectReglasCategoria()
    };
    let action = 'saveCombo';
    if (comboId){
      action = 'updateCombo';
      payload.combo_id = parseInt(comboId,10);
    }

    try{
      setButtonBusy(btn, true, comboId ? 'Actualizando…' : 'Guardando…');
      const r = await fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action, data: payload })
      });
      const d = await r.json();
      if (!d || !d.status){
        throw new Error(d && d.message ? d.message : 'No se pudo guardar el combo');
      }
      showAlert('success','Éxito', comboId ? 'Combo actualizado' : 'Combo creado');
      if (modalComboEditor) modalComboEditor.style.display = 'none';
      if (modalCombos && modalCombos.style.display==='block') await cargarCombos();
    }catch(e){
      showAlert('error','Error', e.message || 'Error al guardar combo');
    }finally{
      setButtonBusy(btn, false);
    }
  });
}

function eliminarCombo(comboId){
  showConfirm('Eliminar Combo', '¿Está seguro que desea eliminar este combo?', () => {
    const fd = new FormData();
    fd.append('action','deleteCombo');
    fd.append('combo_id', String(comboId));
    fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php',{
      method:'POST',
      body: fd
    })
    .then(r=>r.json())
    .then(d=>{
      if (!d || !d.status){
        showAlert('error','Error', d && d.message ? d.message : 'No se pudo eliminar');
        return;
      }
      showAlert('success','Éxito','Combo eliminado');
      cargarCombos();
    })
    .catch(()=> showAlert('error','Error','Error al eliminar combo'));
  });
}

/* ========= Helpers ========= */
  // ===== Helper para el título "Nueva Comanda" (con ícono) =====
  function setFacturaTitle(text){
    const el = document.getElementById('factura-title') || facturaTitle;
    if (!el) return;
    const label = (text && String(text).trim()) ? text : 'Nueva Comanda';
    el.innerHTML = `<i class="fas fa-receipt"></i> ${label}`;
  }

// === Mostrar cliente en la cabecera (NOMBRE + RTN en línea) ===
function pintarClienteInfoHeader(){
  // Usa el estado global que ya manejas
  const clientes_id = (clienteSeleccionado && clienteSeleccionado.id) ? clienteSeleccionado.id : '1';
  const nombre = (clienteSeleccionado && clienteSeleccionado.nombre) ? clienteSeleccionado.nombre : 'Consumidor final';
  const rtn    = (clienteSeleccionado && (clienteSeleccionado.identificacion || clienteSeleccionado.rtn)) ? String(clienteSeleccionado.identificacion || clienteSeleccionado.rtn).trim() : '';

  // Pinta: nombre arriba, RTN (si hay) abajo en pequeño
  setClienteInfoUI({ clientes_id, nombre, rtn });
}

// Mapa rápido producto_id -> categoria_id
function getCategoriaIdDeProducto(pid){
  pid = parseInt(pid,10);
  const p = productos.find(x => parseInt(x.productos_id,10) === pid);
  return p ? parseInt(p.categoria_id,10) : null;
}

// Nombre de categoría por id
function getCategoriaNombre(cid){
  const c = categorias.find(x => parseInt(x.id,10) === parseInt(cid,10));
  return c ? c.nombre : `Cat #${cid}`;
}

// Evita maestro como hijo y duplicados de hijo (mismo producto dentro del combo)
function validarComponentesCombo(items, maestroId){
  const seen = new Set();
  for (const it of items){
    if (String(it.productos_id) === String(maestroId)){
      return { ok:false, msg:'El producto maestro no puede estar entre los componentes.' };
    }
    const key = String(it.productos_id);
    if (seen.has(key)){
      return { ok:false, msg:'Hay productos repetidos entre los componentes.' };
    }
    seen.add(key);
    if (!(it.cantidad_por_porcion>0)){
      return { ok:false, msg:'Todas las cantidades deben ser mayores a 0.' };
    }
    if (it.precio_extra<0){
      return { ok:false, msg:'El precio extra no puede ser negativo.' };
    }
  }
  return { ok:true };
}

// === CATEGORÍA: estación (Cocina/Barra) ===
function setProdEstacion(value = 'cocina', dispararCambio = true) {
  const wrap = document.getElementById('prod-estacion');
  if (!wrap) return;
  const inputs = wrap.querySelectorAll('input[name="prodEstacion"]');
  inputs.forEach(inp => {
    const active = (inp.value === value);
    inp.checked = active;
    const label = inp.nextElementSibling || inp.closest('label');
    if (label && label.classList) label.classList.toggle('active', active);
  });
  const checked = wrap.querySelector('input[name="prodEstacion"]:checked');
  if (checked && dispararCambio) checked.dispatchEvent(new Event('change', { bubbles: true }));
}

function setCatEstacion(value='cocina'){
  const radio = document.querySelector(`#cat-estacion input[value="${value}"]`);
  if (radio) radio.checked = true;
}

// Asegura que 'categorias' esté cargado antes de usarlo
function ensureCategoriasReady(){
  if (Array.isArray(categorias) && categorias.length) return Promise.resolve();
  return cargarCategorias(); // ya devuelve Promise
}

function findProductoById(id){
  id = parseInt(id,10);
  return productos.find(p => parseInt(p.productos_id,10) === id);
}

// Select2 global para el modal (evita "distorsión" y dropdown fuera)
function initSelect2All(){
  if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) {
      return;
  }
  
  // Configuración base para todos los Select2
  const baseConfig = {
      width: '100%',
      theme: 'bootstrap'
  };
  
  // Inicializar otros Select2 que no están en modales
  $('select.select2').not('#ubicacion-mesa, #estado-mesa, #prod-categoria, #combo-producto').each(function() {
      if (!$(this).data('select2')) {
          $(this).select2(baseConfig);
      }
  });
}

  // Función optimizada para inicializar Select2 en modales
  function initSelect2WithModal(selector, modalSelector, config) {
    const $element = $(selector);
    const $modal = $(modalSelector);
    
    if ($element.length && $modal.length) {
        // Destruir si ya estaba inicializado
        if ($element.data('select2')) {
            try {
                $element.select2('destroy');
            } catch (e) {

            }
        }
        
        // Inicializar con la modal como parent
        try {
            $element.select2({
                ...config,
                dropdownParent: $modal
            });
            
            // Forzar actualización visual
            setTimeout(() => {
                $element.trigger('change.select2');
            }, 50);
            
            return $element;
        } catch (e) {

        }
    }
    
    return null;
  }
  
  // Función especial para reinicializar Select2 en modales cuando se abren
 // Función especial para reinicializar Select2 en modales cuando se abren
function reinitSelect2InModal(modalSelector) {
  const $modal = $(modalSelector);
  if (!$modal.length) return;
  
  // Intentar múltiples veces en caso de que Select2 no esté listo
  let attempts = 0;
  const maxAttempts = 5;
  
  const tryInitSelect2 = () => {
      if (typeof $.fn.select2 === 'undefined') {
          attempts++;
          if (attempts < maxAttempts) {
              setTimeout(tryInitSelect2, 200);
          } else {

          }
          return;
      }
      
      $modal.find('select.select2').each(function() {
          const $select = $(this);
          const selectId = $select.attr('id');
          
          let config = {
              width: '100%',
              theme: 'bootstrap',
              dropdownParent: $modal
          };
          
          if (selectId === 'combo-producto') {
              config.allowClear = true;
              config.placeholder = $select.data('placeholder') || 'Selecciona el producto combo';
          } else if (selectId === 'prod-categoria') {
              config.allowClear = true;
              config.placeholder = $select.data('placeholder') || '';
          } else {
              config.minimumResultsForSearch = 0;
          }
          
          try {
              if ($select.data('select2')) {
                  $select.select2('destroy');
              }
              $select.select2(config);
          } catch (error) {

          }
      });
  };
  
  setTimeout(tryInitSelect2, 100);
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

  // ======= SweetAlert Helpers =======
  function showAlert(icon, title, text) {
    if (typeof showNotify === 'function') {
      showNotify(icon, title, text);
    } else {
      console[(icon === 'error') ? 'error' : 'log'](title || '', text || '');
    }
  }

  function showConfirm(title, text, callback, options) {
    options = options || {};
    const danger = options.danger === true || /eliminar|cancelar|cerrar factura|liberar mesa/i.test(String(title||''));
    if (typeof swal !== 'undefined') {
      swal({
        title: title,
        text: text,
        icon: danger ? "warning" : "info",
        buttons: true,
        dangerMode: danger,
        closeOnEsc: true,
        closeOnClickOutside: false
      }).then((willConfirm) => {
        if (willConfirm) callback();
      });
      return;
    }
    showAlert('error', 'SweetAlert no disponible', 'No se puede confirmar la operación de forma segura.');
  }


  // ===== Integración con componentes oficiales de Facturación =====
  function normalizarOpcionesSelect(data, valueKeys, labelKeys){
    if (typeof data === 'string') {
      const raw=data.trim();
      if (!raw) return '';
      if (raw[0] !== '{' && raw[0] !== '[') return data;
      try { data=JSON.parse(raw); } catch (_) { return data; }
    }
    let rows=[];
    if (Array.isArray(data)) rows=data;
    else if (data && Array.isArray(data.data)) rows=data.data;
    if (!rows.length) return '';
    return rows.map(function(x){
      let value=''; let label='';
      for (const k of valueKeys){ if(x && x[k] != null){ value=x[k]; break; } }
      for (const k of labelKeys){ if(x && x[k] != null){ label=x[k]; break; } }
      const sub=(x && (x.cuenta || x.identidad || x.descripcion)) ? String(x.cuenta || x.identidad || x.descripcion) : '';
      return `<option value="${escapeHtml(value)}"${sub?` data-subtext="${escapeHtml(sub)}"`:''}>${escapeHtml(label || value)}</option>`;
    }).join('');
  }

  window.getBanco = window.getBanco || function(){
    return $.ajax({type:'POST',url:BASE+'core/getBanco.php',timeout:15000})
      .done(function(data){
        const opts=normalizarOpcionesSelect(data,['bancos_id','banco_id','id'],['nombre','banco']);
        const sels=$('#formTransferenciaBill #bk_nm,#formChequeBill #bk_nm_chk');
        if(opts) sels.html(opts);
        try{sels.selectpicker('refresh');}catch(_){}
      })
      .fail(function(){showAlert('error','Error','No se pudieron cargar los bancos.');});
  };

  window.getCollaboradoresModalPagoFacturas = window.getCollaboradoresModalPagoFacturas || function(){
    const sels=$('#formEfectivoBill #usuario_efectivo,#formTarjetaBill #usuario_tarjeta,#formTransferenciaBill #usuario_transferencia,#formChequeBill #usuario_cheque,#formPuntosBill #usuario_puntos');
    const aplicar=function(data){
      const opts=normalizarOpcionesSelect(data,['colaboradores_id','colaborador_id','id'],['nombre','colaborador']);
      if(opts) sels.html(opts);
      try{sels.selectpicker('refresh');}catch(_){}
    };
    // En instalaciones históricas de IZZY existe con ambas grafías; se intenta sin duplicar la carga.
    return $.ajax({type:'POST',url:BASE+'core/getColaboradores.php',timeout:15000})
      .done(aplicar)
      .fail(function(){
        return $.ajax({type:'POST',url:BASE+'core/getCollaboradores.php',timeout:15000})
          .done(aplicar)
          .fail(function(){showAlert('error','Error','No se pudieron cargar los colaboradores del pago.');});
      });
  };

  window.printBill = window.printBill || function(facturas_id, print_comprobante){
    const fid=Number(facturas_id||0);
    if(!fid){showAlert('error','Factura inválida','No se recibió la factura a mostrar.');return false;}

    const abrirFallback=function(){
      abrirFacturaEnModal(BASE+'php/facturacion/generaFactura.php?facturas_id='+encodeURIComponent(fid),'Factura #'+fid);
    };

    $.ajax({type:'POST',url:BASE+'core/getImpresoraComprobante.php',data:{formato:'Factura'},dataType:'text',timeout:12000})
      .done(function(data){
        let parsed=data;
        try { if(typeof data==='string') parsed=JSON.parse(data); } catch (_) {}
        const impresora=Array.isArray(parsed) ? parsed[0] : parsed;
        if(!impresora || typeof impresora!=='object') { abrirFallback(); return; }

        const estado=String(impresora.estado == null ? '' : impresora.estado);
        const formato=String(impresora.formato || '').trim();
        const reportServer=String(window.REST_REPORT_SERVER||'').trim();
        if(estado !== '1' || !reportServer){ abrirFallback(); return; }

        let reportType='';
        if(formato==='Carta') reportType='Factura_carta_izzy';
        else if(formato==='Media Carta') reportType='Factura_media_izzy';
        else if(formato==='Ticket') reportType='Factura_ticket_izzy';
        else { abrirFallback(); return; }

        const params={
          id:fid,
          type:reportType,
          db:String(window.REST_DB||''),
          demo_sistema:String(window.REST_SISTEMA_PRUEBA||0)
        };
        const sep=reportServer.indexOf('?')===-1?'?':'&';
        abrirFacturaEnModal(reportServer+sep+new URLSearchParams(params).toString(),'Factura #'+fid);
      })
      .fail(abrirFallback);
    return false;
  };

  window.mailBill = window.mailBill || function(){ return false; };
  window.listar_cuentas_por_cobrar_clientes = window.listar_cuentas_por_cobrar_clientes || function(){};
  window.getTotalFacturasDisponibles = window.getTotalFacturasDisponibles || function(){ return Promise.resolve(); };

  function abrirFacturaEnModal(url,titulo){
    const modal=document.getElementById('modal-factura-restaurante');
    const iframe=document.getElementById('iframe-factura-restaurante');
    const loading=document.getElementById('rs-report-loading');
    const subtitle=document.getElementById('rs-report-subtitle');
    if(!modal||!iframe){window.open(url,'_blank');return;}
    if(subtitle) subtitle.textContent=titulo||'';
    if(loading) loading.style.display='flex';
    iframe.src='about:blank';
    modal.classList.add('show');
    modal.setAttribute('aria-hidden','false');
    const onload=function(){ if(iframe.src && iframe.src!=='about:blank' && loading) loading.style.display='none'; };
    iframe.onload=onload;
    setTimeout(()=>{iframe.src=url;},80);
  }

  $(document).off('click.restReportClose','#btn-cerrar-factura-preview').on('click.restReportClose','#btn-cerrar-factura-preview',function(){
    $('#modal-factura-restaurante').removeClass('show').attr('aria-hidden','true');
    const iframe=document.getElementById('iframe-factura-restaurante'); if(iframe) iframe.src='about:blank';
  });

  // El pago unificado está integrado en este mismo archivo para respetar la arquitectura original del módulo.
  instalarHookPagoRestaurante();

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

  /* ================================================================
     CIERRE FUNCIONAL RESTAURANTE - CONFIG / CUENTAS / TICKET / AUTH
     ================================================================ */
  window.REST_CONFIG = window.REST_CONFIG || {usar_mesas:1, usar_comandas:1, etiqueta_cocina:'Cocina', etiqueta_barra:'Barra', destino_comanda:'pantalla', momento_ticket:'enviar', flujo_cocina:'pasos'};
  let REST_TIPO_USUARIO = 0;
  let REST_PERMISOS = {};
  let REST_AUTH_BYPASS = false;

  function restJsonSeguro(v, fallback){
    if (v && typeof v === 'object') return v;
    try { return JSON.parse(String(v||'')); } catch(_) { return fallback; }
  }

  function restPost(action, data={}){
    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams(Object.assign({action},data)).toString()
    }).then(async r=>{
      let d=null; try{ d=await r.json(); }catch(_){ throw new Error('Respuesta inválida del servidor'); }
      if(r.status===401 && d.redirect){ window.location.href=d.redirect; throw new Error(d.message||'Sesión expirada'); }
      return d;
    });
  }

  async function cargarPermisosRestaurante(){
    try{
      const tipoRaw = await $.ajax({type:'POST',url:BASE+'core/getPrivilegioUsuarioTipo.php',timeout:15000});
      const tipoData = restJsonSeguro(tipoRaw, []);
      REST_TIPO_USUARIO = Number(Array.isArray(tipoData) ? tipoData[0] : (tipoData.tipo_user_id || tipoData.id || 0));
      let rows=[];
      if(REST_TIPO_USUARIO>0){
        const pRaw=await $.ajax({type:'POST',url:BASE+'core/getTipoUsuarioAccesos.php',data:{permisos_tipo_user_id:REST_TIPO_USUARIO},timeout:15000});
        rows=restJsonSeguro(pRaw,[]) || [];
      }
      REST_PERMISOS={};
      (Array.isArray(rows)?rows:[]).forEach(r=>{ if(r && r.tipo_permiso) REST_PERMISOS[String(r.tipo_permiso).toLowerCase()]=Number(r.estado)===1; });
    }catch(_){ REST_TIPO_USUARIO=0; REST_PERMISOS={}; }
    aplicarPermisosRestauranteUI();
  }

  function tienePermisoRestaurante(nombre){
    if(REST_TIPO_USUARIO===1 || REST_TIPO_USUARIO===2) return true;
    return REST_PERMISOS[String(nombre||'').toLowerCase()]===true || REST_PERMISOS.restaurante_gestionar===true;
  }

  function aplicarPermisosRestauranteUI(){
    const gestionar=document.getElementById('gestion-fija');
    const mapa=[
      ['#btn-nuevo-cliente-rapido','restaurante_clientes'],
      ['#btn-nuevo-cliente','restaurante_clientes'],
      ['#btn-editar-cliente-seleccionado','restaurante_clientes'],
      ['#btn-nueva-mesa','restaurante_mesas'],
      ['#btn-nueva-categoria','restaurante_categorias'],
      ['#btn-nuevo-producto','restaurante_productos'],
      ['#btn-gestionar-combos','restaurante_combos'],
      ['#btn-gestionar-promos','restaurante_promociones'],
      ['#btn-nueva-promocion','restaurante_promociones'],
      ['#btn-asignar-promo-productos','restaurante_promociones'],
      ['#btn-asignar-promo-categorias','restaurante_promociones']
    ];
    const puedeGestionar = REST_TIPO_USUARIO===1 || REST_TIPO_USUARIO===2 || Object.keys(REST_PERMISOS).some(k=>k.indexOf('restaurante_')===0 && REST_PERMISOS[k]);
    if(gestionar) gestionar.style.display=puedeGestionar?'inline-block':'none';
    mapa.forEach(([sel,perm])=>{
      const trigger=document.querySelector(sel);
      const item=trigger ? document.querySelector(`#gestionar-menu [data-target="${sel}"]`) : null;
      if(item) item.style.display=tienePermisoRestaurante(perm)?'':'none';
    });
    const cfg=document.getElementById('btn-configuracion-restaurante');
    if(cfg) cfg.style.display=tienePermisoRestaurante('restaurante_configuracion')?'':'none';
    document.querySelectorAll('.btn-edit-mesa').forEach(x=>x.style.display=tienePermisoRestaurante('restaurante_mesas')?'':'none');
    document.querySelectorAll('.edit-cat-btn').forEach(x=>x.style.display=tienePermisoRestaurante('restaurante_categorias')?'':'none');
    document.querySelectorAll('.btn-edit-producto-admin').forEach(x=>x.style.display=tienePermisoRestaurante('restaurante_productos')?'':'none');
    const nm=document.getElementById('btn-nueva-mesa'); if(nm) nm.style.display=tienePermisoRestaurante('restaurante_mesas')?'':'none';
    const nc=document.getElementById('btn-nuevo-cliente'); if(nc) nc.style.display=tienePermisoRestaurante('restaurante_clientes')?'':'none';
    const ec=document.getElementById('btn-editar-cliente-seleccionado'); if(ec) ec.style.display=tienePermisoRestaurante('restaurante_clientes')?'':'none';
  }

  function autorizarGestionRestaurante(accion, callback, referencia){
    if(typeof callback!=='function') return;
    if(typeof window.validarAdminSistema!=='function'){
      showAlert('error','Validación administrativa','No está disponible la validación administrativa del sistema.');
      return;
    }
    window.validarAdminSistema(function(ok){ if(ok) callback(); },{
      modulo:'Restaurante', accion:accion||'Gestión de restaurante', referencia_id:String(referencia||''),
      referencia_texto:String(accion||''), mensaje:'Esta acción modifica configuración o datos maestros. Ingrese credenciales de administrador para continuar.'
    });
  }
  window.autorizarGestionRestaurante=autorizarGestionRestaurante;

  // Protege accesos de creación/gestión antes de que disparen sus listeners originales.
  document.addEventListener('click', function(ev){
    const target=ev.target && ev.target.closest ? ev.target.closest('#btn-nuevo-cliente-rapido,#btn-nuevo-cliente,#btn-editar-cliente-seleccionado,#btn-nueva-mesa,#btn-nueva-categoria,#btn-nuevo-producto,#btn-gestionar-combos,#btn-gestionar-promos,#btn-nueva-promocion,#btn-asignar-promo-productos,#btn-asignar-promo-categorias') : null;
    if(!target || REST_AUTH_BYPASS) return;
    ev.preventDefault(); ev.stopPropagation(); ev.stopImmediatePropagation();
    const label=(target.textContent||target.title||'Administrar').trim();
    autorizarGestionRestaurante(label, function(){ REST_AUTH_BYPASS=true; try{ target.click(); } finally{ setTimeout(()=>{REST_AUTH_BYPASS=false;},0); } });
  }, true);

  function abrirFotoProducto(producto){
    const modal=document.getElementById('modal-foto-producto');
    const img=document.getElementById('foto-producto-grande');
    const title=document.getElementById('foto-producto-titulo');
    if(!modal||!img) return;
    const nombre=String((producto&&producto.nombre)||'Producto');
    if(title) title.textContent=nombre;
    img.src=(producto&&producto.file_name) ? `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}` : '';
    img.alt=nombre;
    modal.style.display='block';
  }

  function formatoMonedaTicket(valor){
    return new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      .format(Number(valor || 0));
  }

  function crearSnapshotTicket(extra={}){
    const items=(comandaItems||[]).map(it=>{
      const p=it.producto||productoMaestroPorId(it.productos_id||it.producto_id)||{};
      return {
        nombre:String(p.nombre||it.nombre_producto||'Producto'),
        cantidad:Number(it.cantidad||1),
        precio:Number(it.precio||p.precio||0),
        estacion:normalizeEstacion(p)
      };
    });
    const nombreCliente=String((clienteSeleccionado&&clienteSeleccionado.nombre)||'Consumidor Final');
    const rtnCliente=String((clienteSeleccionado&&(clienteSeleccionado.identificacion||clienteSeleccionado.rtn))||'').trim();
    const mesa=(mesaSeleccionada&&(mesaSeleccionada.numero||mesaSeleccionada.nombre))
      ? 'Mesa '+(mesaSeleccionada.numero||mesaSeleccionada.nombre)
      : 'Venta directa / Para llevar';
    const obs=String((observacionesTextarea&&observacionesTextarea.value)||'').trim();
    const subtotal=items.reduce((sum,it)=>sum+(it.precio*it.cantidad),0);
    const totalPantalla=Number(String((totalElement&&totalElement.textContent)||'').replace(/[^0-9.-]/g,''));
    const total=Number.isFinite(totalPantalla)&&totalPantalla>0?totalPantalla:subtotal;
    return {
      items,
      cliente:nombreCliente,
      rtn:rtnCliente,
      contexto:mesa,
      observaciones:obs,
      total:Number(extra.total!=null?extra.total:total),
      fecha:extra.fecha||new Date().toISOString(),
      factura_id:Number(extra.factura_id||facturaIdActual()||0),
      modo_comanda:usaComandasOperacion()
    };
  }

  function construirTicketComandaHtml(snapshot=null){
    const snap=snapshot||crearSnapshotTicket();
    const rows=(snap.items||[]).map(it=>{
      const n=escapeHtml(it.nombre||'Producto');
      const q=Number(it.cantidad||1);
      return `<div class="rs-ticket-row"><strong>${q} × ${n}</strong><span>L ${formatoMonedaTicket(Number(it.precio||0)*q)}</span></div>`;
    }).join('');
    const tipoTicket=snap.modo_comanda!==false?'ORDEN / COMANDA':'TICKET DE VENTA';
    const fecha=new Date(snap.fecha||Date.now());
    const numero=snap.factura_id>0?`<span>Cuenta/Factura #${snap.factura_id}</span>`:'';
    const rtn=snap.rtn?`<span>RTN ${escapeHtml(snap.rtn)}</span>`:'';
    return `<div class="rs-ticket-brand"><strong>${tipoTicket}</strong><span>${escapeHtml(snap.contexto||'Venta')}</span>${numero}</div>
      <div class="rs-ticket-meta"><span>${escapeHtml(snap.cliente||'Consumidor Final')}${rtn?'<br>'+rtn:''}</span><span>${fecha.toLocaleString('es-HN')}</span></div>
      <div class="rs-ticket-items">${rows}</div>
      ${snap.observaciones?`<div class="rs-ticket-notes"><strong>Observaciones</strong><span>${escapeHtml(snap.observaciones)}</span></div>`:''}
      <div class="rs-ticket-total"><span>Total referencial</span><strong>L ${formatoMonedaTicket(Number(snap.total||0))}</strong></div>
      <small class="rs-ticket-foot">${snap.modo_comanda!==false?'Documento interno de orden/comanda.':'Ticket interno de venta.'} No sustituye la factura fiscal.</small>`;
  }

  function abrirTicketComanda(snapshot=null){
    const snap=snapshot||crearSnapshotTicket();
    if(!Array.isArray(snap.items)||!snap.items.length){ showAlert('warning','Sin productos','Agregue productos antes de generar el ticket.'); return; }
    const preview=document.getElementById('ticket-comanda-preview');
    if(preview) preview.innerHTML=construirTicketComandaHtml(snap);
    const modal=document.getElementById('modal-ticket-comanda');
    if(!modal){ showAlert('error','Ticket','No se encontró la vista previa del ticket.'); return; }
    modal.dataset.ticketSnapshot=encodeURIComponent(JSON.stringify(snap));
    modal.style.setProperty('display','block','important');
    modal.setAttribute('aria-hidden','false');
    const paper=modal.querySelector('.rs-ticket-paper');
    if(paper) paper.scrollTop=0;
  }

  function documentoTicketImpresion(snapshot){
    const snap=snapshot||crearSnapshotTicket();
    const html=construirTicketComandaHtml(snap);
    const titulo=snap.modo_comanda!==false?'Ticket de comanda':'Ticket de venta';
    return `<!doctype html><html><head><meta charset="utf-8"><title>${titulo}</title><style>
      body{font-family:Arial,sans-serif;width:72mm;margin:0 auto;padding:5mm;color:#111;font-size:12px}
      .rs-ticket-brand{text-align:center;border-bottom:1px dashed #777;padding-bottom:8px;display:flex;flex-direction:column;gap:3px}
      .rs-ticket-meta{display:flex;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px dashed #aaa;font-size:10px}
      .rs-ticket-row{display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px dotted #ccc}
      .rs-ticket-notes{padding:8px 0;display:flex;flex-direction:column;gap:4px}
      .rs-ticket-total{border-top:1px solid #111;margin-top:6px;padding-top:8px;display:flex;justify-content:space-between;font-size:14px}
      .rs-ticket-foot{display:block;text-align:center;margin-top:12px}
      @page{size:80mm auto;margin:3mm}@media print{body{width:auto}}
    </style></head><body>${html}</body></html>`;
  }

  function imprimirTicketAutomatico(snapshot){
    const snap=snapshot||crearSnapshotTicket();
    if(!snap.items||!snap.items.length) return false;
    try{
      const iframe=document.createElement('iframe');
      iframe.setAttribute('aria-hidden','true');
      iframe.style.position='fixed';
      iframe.style.right='0';
      iframe.style.bottom='0';
      iframe.style.width='1px';
      iframe.style.height='1px';
      iframe.style.opacity='0';
      iframe.style.pointerEvents='none';
      iframe.srcdoc=documentoTicketImpresion(snap);
      iframe.onload=function(){
        setTimeout(()=>{
          try{
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
          }catch(e){
            showAlert('warning','Impresión de comanda','No se pudo abrir la impresión automática. Use “Ticket comanda” para imprimir manualmente.');
          }
          setTimeout(()=>iframe.remove(),1500);
        },120);
      };
      document.body.appendChild(iframe);
      return true;
    }catch(e){
      showAlert('warning','Impresión de comanda','No se pudo preparar el ticket automático.');
      return false;
    }
  }

  function imprimirTicketComanda(){
    const modal=document.getElementById('modal-ticket-comanda');
    let snap=null;
    if(modal&&modal.dataset.ticketSnapshot){
      try{ snap=JSON.parse(decodeURIComponent(modal.dataset.ticketSnapshot)); }catch(_){}
    }
    snap=snap||crearSnapshotTicket();
    const w=window.open('','_blank','width=420,height=700');
    if(!w){ showAlert('warning','Ventana bloqueada','Permita ventanas emergentes para imprimir el ticket.'); return; }
    w.document.open();
    w.document.write(documentoTicketImpresion(snap).replace('</body>','<script>window.onload=function(){window.print();setTimeout(function(){window.close();},500)}<\\/script></body>'));
    w.document.close();
  }

  async function guardarCuentaAbiertaGeneral(){
    if(!Array.isArray(comandaItems)||!comandaItems.length){ showAlert('warning','Sin productos','Agregue productos a la cuenta.'); return; }
    const btn=document.getElementById('btn-guardar-cuenta');
    setButtonBusy(btn,true,'Guardando…');
    try{
      if(getServicioTipo()==='mesa' && window.REST_CONFIG.usar_mesas){
        await guardarCuentaMesa({silencioso:true,imprimirAlEnviar:false,enviarComanda:false});
      } else {
        const d=await $.ajax({type:'POST',url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',dataType:'json',data:{
          action:'guardarFacturaRestaurante',servicio:'llevar',mesa_id:0,factura_id:facturaIdActual(),clientes_id:clienteIdActual(),
          observaciones:String((observacionesTextarea&&observacionesTextarea.value)||'').trim(),detalle:prepararDetalleRestaurante(),
          enviar_comanda:0
        }});
        if(!d||!d.ok) throw new Error((d&&(d.msg||d.message))||'No se pudo guardar la cuenta');
        facturaActual={id:Number(d.factura_id),factura_id:Number(d.factura_id),facturas_id:Number(d.factura_id)};
        if(debeImprimirTicketComanda() && momentoTicketOperacion()==='enviar'){
          const snap=crearSnapshotTicket({factura_id:Number(d.factura_id||0)});
          setTimeout(()=>imprimirTicketAutomatico(snap),120);
        }
        if(facturaTitle) facturaTitle.innerHTML='<i class="fas fa-bookmark"></i> Cuenta abierta #'+d.factura_id;
      }
      showAlert('success','Cuenta abierta','La cuenta quedó guardada. Puede recuperarla desde “Cuentas abiertas”.');
      updateAccionPrincipalUI();
    }catch(e){ showAlert('error','Error',e.message||'No se pudo guardar la cuenta'); }
    finally{ setButtonBusy(btn,false); updateAccionPrincipalUI(); }
  }

  function mapCuentaItems(items){
    return (Array.isArray(items)?items:[]).map(item=>{
      const master=productoMaestroPorId(item.productos_id)||{};
      return {producto:{id:item.productos_id,nombre:item.nombre_producto||master.nombre||'Producto',precio:Number(item.precio||0),descripcion:item.descripcion_producto||'',isv1:Number(item.isv1||0)===1,isv2:Number(item.isv2||0)===1,estacion:normalizeEstacion(master),medida:item.medida||master.medida||'Und',file_name:master.file_name||''},cantidad:Number(item.cantidad||1),precio:Number(item.precio||0),total:Number(item.precio||0)*Number(item.cantidad||1),descuento:Number(item.descuento||0)};
    });
  }

  async function cargarCuentasAbiertasUI(){
    const modal=document.getElementById('modal-cuentas-abiertas');
    const list=document.getElementById('cuentas-abiertas-listado');
    if(!modal||!list) return;
    modal.style.display='block';
    list.innerHTML='<div class="rs-empty-state"><i class="fas fa-spinner fa-spin"></i><span>Cargando cuentas…</span></div>';
    try{
      const d=await restPost('loadCuentasAbiertas');
      if(!d||!d.status) throw new Error(d&&d.message?d.message:'No se pudieron cargar las cuentas');
      const cuentas=Array.isArray(d.cuentas)?d.cuentas:[];
      window.__REST_CUENTAS_ABIERTAS=cuentas;
      renderCuentasAbiertas(cuentas);
    }catch(e){ list.innerHTML=`<div class="rs-empty-state"><i class="fas fa-exclamation-circle"></i><span>${escapeHtml(e.message||'Error')}</span></div>`; }
  }

  function renderCuentasAbiertas(cuentas,term=''){
    const list=document.getElementById('cuentas-abiertas-listado'); if(!list) return;
    const t=String(term||'').toLowerCase().trim();
    const arr=(cuentas||[]).filter(c=>!t || [c.facturas_id,c.cliente_nombre,c.cliente_rtn,c.mesa_numero,c.servicio_tipo].join(' ').toLowerCase().includes(t));
    if(!arr.length){ list.innerHTML='<div class="rs-empty-state"><i class="fas fa-folder-open"></i><span>No hay cuentas abiertas.</span></div>'; return; }
    list.innerHTML=arr.map(c=>{
      const ubicacion=c.servicio_tipo==='mesa'?'Mesa '+escapeHtml(c.mesa_numero||''):'Para llevar / venta directa';
      const rtn=String(c.cliente_rtn||'').trim();
      const enviadas=Number(c.enviadas_preparacion||0);
      const unidades=Number(c.unidades||0);
      const estadoPrep=usaComandasOperacion()
        ? `<small class="rs-open-account-prep"><i class="fas fa-fire"></i> ${enviadas>0?`${enviadas} unidad(es) ya enviadas a preparación`:'Aún no enviada a preparación'}</small>`
        : '';
      return `<button type="button" class="rs-open-account-card" data-fid="${Number(c.facturas_id)}">
        <span class="rs-open-account-icon"><i class="fas ${c.servicio_tipo==='mesa'?'fa-chair':'fa-shopping-bag'}"></i></span>
        <span class="rs-open-account-main">
          <strong>${escapeHtml(c.cliente_nombre||'Consumidor Final')}</strong>
          ${rtn?`<small class="rs-open-account-rtn"><i class="fas fa-id-card"></i> RTN ${escapeHtml(rtn)}</small>`:''}
          <small><i class="fas fa-map-marker-alt"></i> ${ubicacion} · ${unidades} unidad(es)</small>
          ${estadoPrep}
        </span>
        <span class="rs-open-account-total"><small>Cuenta #${Number(c.facturas_id)}</small><strong>L ${fmtHNL(Number(c.importe||0))}</strong></span>
        <span class="rs-open-account-open"><i class="fas fa-arrow-right"></i></span>
      </button>`;
    }).join('');
  }

  async function abrirCuentaAbierta(facturaId){
    try{
      const d=await restPost('loadCuentaAbierta',{factura_id:String(facturaId)});
      if(!d||!d.status||!d.cuenta) throw new Error(d&&d.message?d.message:'Cuenta no disponible');
      const c=d.cuenta;
      facturaActual={id:Number(c.facturas_id),factura_id:Number(c.facturas_id),facturas_id:Number(c.facturas_id),notas:c.notas||''};
      clienteSeleccionado={id:Number(c.clientes_id||1),nombre:c.cliente_nombre||'Consumidor Final',identificacion:c.cliente_rtn||''};
      pintarClienteInfoHeader();
      comandaItems=mapCuentaItems(c.items);
      if(observacionesTextarea) observacionesTextarea.value=c.notas||'';
      if(window.REST_CONFIG.usar_mesas && c.servicio_tipo==='mesa' && Number(c.mesa_id)>0){
        mesaSeleccionada=(mesas||[]).find(m=>Number(m.id||m.mesa_id)===Number(c.mesa_id)) || {id:Number(c.mesa_id),numero:c.mesa_numero||''};
        setServicioTipo('mesa'); setMesaSeleccionadaUI(c.mesa_numero||''); highlightMesaSeleccionada();
      }else{ mesaSeleccionada=null; setServicioTipo('llevar'); setMesaSeleccionadaUI(null); }
      if(facturaTitle) facturaTitle.innerHTML='<i class="fas fa-bookmark"></i> Cuenta abierta #'+c.facturas_id;
      actualizarComandaUI(); updateProductBadges(); updateAccionPrincipalUI();
      const modal=document.getElementById('modal-cuentas-abiertas'); if(modal) modal.style.display='none';
      showAlert('success','Cuenta recuperada','Puede seguir agregando productos o cobrarla cuando corresponda.');
    }catch(e){ showAlert('error','Error',e.message||'No se pudo abrir la cuenta'); }
  }

  async function cargarConfiguracionOperacion(){
    try{
      const d=await restPost('loadConfiguracionOperacion');
      if(d&&d.status&&d.config) window.REST_CONFIG={
        usar_mesas:Number(d.config.usar_mesas)!==0?1:0,
        usar_comandas:Number(d.config.usar_comandas)!==0?1:0,
        etiqueta_cocina:String(d.config.etiqueta_cocina||'Cocina'),
        etiqueta_barra:String(d.config.etiqueta_barra||'Barra'),
        destino_comanda:String(d.config.destino_comanda||'pantalla'),
        momento_ticket:String(d.config.momento_ticket||'enviar'),
        flujo_cocina:String(d.config.flujo_cocina||'pasos')
      };
    }catch(_){ window.REST_CONFIG={usar_mesas:1,usar_comandas:1,etiqueta_cocina:'Cocina',etiqueta_barra:'Barra',destino_comanda:'pantalla',momento_ticket:'enviar',flujo_cocina:'pasos'}; }
    aplicarConfiguracionOperacion();
  }

  function aplicarConfiguracionOperacion(){
    const usar=Number(window.REST_CONFIG.usar_mesas)!==0;
    const usarComandasCfg=Number(window.REST_CONFIG.usar_comandas)!==0;
    document.body.classList.toggle('rs-modo-venta-directa',!usar);
    document.body.classList.toggle('rs-sin-comandas',!usarComandasCfg);
    const sidebar=document.querySelector('.mesas-sidebar'); if(sidebar) sidebar.style.display=usar?'':'none';
    const sw=document.getElementById('servicio-switch'); if(sw) sw.style.display=usar?'':'none';
    const mesaMeta=document.getElementById('mesa-seleccionada'); if(mesaMeta) mesaMeta.style.display=usar?'':'none';
    if(!usar){
      mesaSeleccionada=null; setServicioTipo('llevar'); setMesaSeleccionadaUI(null);
      if(!facturaIdActual() && facturaTitle) facturaTitle.innerHTML='<i class="fas fa-cash-register"></i> Nueva venta';
    } else if(!facturaIdActual() && facturaTitle) {
      facturaTitle.innerHTML=usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
    }
    const c1=document.getElementById('config-usar-mesas'); if(c1)c1.checked=usar;
    const c2=document.getElementById('config-usar-comandas'); if(c2)c2.checked=Number(window.REST_CONFIG.usar_comandas)!==0;
    const usaComandas=usaComandasOperacion();
    const configGrupos=document.getElementById('config-grupos-operacion'); if(configGrupos) configGrupos.style.display=usaComandas?'':'none';
    const filtroEst=document.getElementById('filtro-estacion'); if(filtroEst) filtroEst.style.display=usaComandas?'':'none';
    const ec=document.getElementById('config-etiqueta-cocina'); if(ec)ec.value=etiquetaEstacion('cocina');
    const eb=document.getElementById('config-etiqueta-barra'); if(eb)eb.value=etiquetaEstacion('barra');
    const salidaWrap=document.getElementById('config-salida-comanda'); if(salidaWrap) salidaWrap.style.display=usaComandas?'':'none';
    const destino=document.getElementById('config-destino-comanda'); if(destino) destino.value=destinoComandaOperacion();
    const momento=document.getElementById('config-momento-ticket'); if(momento) momento.value=momentoTicketOperacion();
    const flujo=document.getElementById('config-flujo-cocina'); if(flujo) flujo.value=String(window.REST_CONFIG.flujo_cocina||'pasos');
    sincronizarTodosBotonesConfiguracion();
    aplicarEtiquetasOperacion();
    renderizarCategorias();
    filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');
    updateAccionPrincipalUI();
  }

  function sincronizarBotonesConfiguracion(targetId){
    const input=document.getElementById(targetId);
    if(!input) return;
    const value=String(input.value||'');
    document.querySelectorAll(`.rs-config-choice-list[data-choice-target="${targetId}"] .rs-config-choice`).forEach(btn=>{
      const active=String(btn.dataset.value||'')===value;
      btn.classList.toggle('is-active',active);
      btn.setAttribute('aria-pressed',active?'true':'false');
      btn.tabIndex=active?0:-1;
    });
  }

  function sincronizarTodosBotonesConfiguracion(){
    ['config-destino-comanda','config-momento-ticket','config-flujo-cocina'].forEach(sincronizarBotonesConfiguracion);
  }

  document.addEventListener('click',function(ev){
    const btn=ev.target&&ev.target.closest?ev.target.closest('.rs-config-choice'):null;
    if(!btn) return;
    const list=btn.closest('.rs-config-choice-list');
    if(!list) return;
    const targetId=String(list.dataset.choiceTarget||'');
    const input=document.getElementById(targetId);
    if(!input) return;
    ev.preventDefault();
    input.value=String(btn.dataset.value||'');
    input.dispatchEvent(new Event('change',{bubbles:true}));
    sincronizarBotonesConfiguracion(targetId);
  });

  async function guardarConfiguracionOperacionUI(){
    const btn=document.getElementById('btn-guardar-configuracion-restaurante');
    setButtonBusy(btn,true,'Guardando…');
    try{
      const d=await restPost('saveConfiguracionOperacion',{
        usar_mesas:document.getElementById('config-usar-mesas').checked?'1':'0',
        usar_comandas:document.getElementById('config-usar-comandas').checked?'1':'0',
        etiqueta_cocina:String(document.getElementById('config-etiqueta-cocina').value||'Cocina').trim(),
        etiqueta_barra:String(document.getElementById('config-etiqueta-barra').value||'Barra').trim(),
        destino_comanda:String((document.getElementById('config-destino-comanda')||{}).value||'pantalla'),
        momento_ticket:String((document.getElementById('config-momento-ticket')||{}).value||'enviar'),
        flujo_cocina:String((document.getElementById('config-flujo-cocina')||{}).value||'pasos')
      });
      if(!d||!d.status) throw new Error(d&&d.message?d.message:'No se pudo guardar');
      window.REST_CONFIG=d.config||window.REST_CONFIG; aplicarConfiguracionOperacion();
      document.getElementById('modal-configuracion-restaurante').style.display='none';
      showAlert('success','Configuración','La configuración del módulo fue actualizada.');
    }catch(e){showAlert('error','Error',e.message||'No se pudo guardar');}
    finally{setButtonBusy(btn,false);}
  }

  function reinitSelect2Restaurante(){
    if(!(window.jQuery&&$.fn&&$.fn.select2)) return;
    const $selects=$('.rs-modal select.select2, .rs-modal select.form-control');
    $selects.each(function(){
      const $sel=$(this);
      const $modal=$sel.closest('.rs-modal');
      try{ if($sel.hasClass('select2-hidden-accessible') || $sel.data('select2')) $sel.select2('destroy'); }catch(_){}
      $sel.select2({
        width:'100%',
        dropdownParent:$modal.length?$modal:$(document.body),
        minimumResultsForSearch:0,
        allowClear:false,
        placeholder:$sel.data('placeholder')||$sel.find('option[value=""]').first().text()||'Seleccione…'
      });
    });
  }

  // Mejora visual/funcional de modales al abrirlos.
  $(document).off('click.restSelect2Open','[data-close]').on('click.restSelect2Open','[data-close]',function(){ const target=$(this).data('close'); if(target) $(target).hide(); });
  $(document).on('click.restOpenSelect2','#btn-cambiar-cliente,#btn-nueva-mesa,#btn-nuevo-cliente,#btn-nueva-categoria,#btn-nuevo-producto,#btn-gestionar-combos,#btn-gestionar-promos,#btn-nueva-promocion,#btn-asignar-promo-productos,#btn-asignar-promo-categorias,.btn-reservar-mesa',function(){ setTimeout(reinitSelect2Restaurante,80); });

  $('#btn-guardar-cuenta').off('click.restSaveAccount').on('click.restSaveAccount',guardarCuentaAbiertaGeneral);
  $('#btn-cuentas-abiertas').off('click.restOpenAccounts').on('click.restOpenAccounts',cargarCuentasAbiertasUI);
  $('#buscar-cuenta-abierta').off('input.restOpenAccounts').on('input.restOpenAccounts',function(){renderCuentasAbiertas(window.__REST_CUENTAS_ABIERTAS||[],this.value);});
  $(document).off('click.restOpenAccount','.rs-open-account-card').on('click.restOpenAccount','.rs-open-account-card',function(){abrirCuentaAbierta(Number($(this).data('fid')));});
  $('#btn-imprimir').off('click.restTicketOpen').on('click.restTicketOpen',function(e){e.preventDefault();e.stopPropagation();abrirTicketComanda();});
    $('#btn-imprimir-ticket-comanda').off('click.restTicket').on('click.restTicket',imprimirTicketComanda);
  $('#btn-configuracion-restaurante').off('click.restCfg').on('click.restCfg',function(e){
    e.preventDefault();e.stopPropagation();
    autorizarGestionRestaurante('Configuración del módulo',()=>{
      aplicarConfiguracionOperacion();
      document.getElementById('modal-configuracion-restaurante').style.display='block';
      setTimeout(reinitSelect2Restaurante,80);
    });
  });
  $('#btn-guardar-configuracion-restaurante').off('click.restCfgSave').on('click.restCfgSave',guardarConfiguracionOperacionUI);

  $('#config-usar-comandas').off('change.restCfgComandas').on('change.restCfgComandas',function(){
    const wrap=document.getElementById('config-grupos-operacion');
    const salida=document.getElementById('config-salida-comanda');
    if(wrap) wrap.style.display=this.checked?'':'none';
    if(salida) salida.style.display=this.checked?'':'none';
  });

  // Captura única y robusta para el ticket actual. Evita que handlers heredados del antiguo botón Imprimir lo bloqueen.
  if(!window.__REST_TICKET_CAPTURE_BOUND){
    window.__REST_TICKET_CAPTURE_BOUND=true;
    document.addEventListener('click',function(e){
      const btn=e.target&&e.target.closest?e.target.closest('#btn-imprimir'):null;
      if(!btn) return;
      e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
      abrirTicketComanda();
    },true);
  }

  // Atajos adicionales; mantiene los existentes y agrega los que realmente existen en esta versión.
  document.addEventListener('keydown',function(e){
    const mod=e.ctrlKey||e.metaKey; if(!mod||!e.altKey) return;
    const k=String(e.key||'').toLowerCase();
    if(k==='s'){e.preventDefault();document.getElementById('btn-guardar-cuenta')?.click();}
    if(k==='a'){e.preventDefault();document.getElementById('btn-cuentas-abiertas')?.click();}
  });

  // Estado dinámico de acciones cada vez que cambia la comanda.
  const _actualizarComandaUIOriginal=actualizarComandaUI;
  actualizarComandaUI=function(){ const r=_actualizarComandaUIOriginal.apply(this,arguments); updateAccionPrincipalUI(); return r; };

  const restPermObserver = new MutationObserver(function(){ aplicarPermisosRestauranteUI(); });
  ['mesas-container','productos-container','categorias-tabs'].forEach(function(id){ const el=document.getElementById(id); if(el) restPermObserver.observe(el,{childList:true,subtree:true}); });

  setTimeout(function(){
    cargarConfiguracionOperacion();
    cargarPermisosRestaurante();
    reinitSelect2Restaurante();
    updateAccionPrincipalUI();
  },120);

});
