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
  console.error('SERVERURL no está definido.'); 
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

  // --- Estado global ---
  var cajaAbierta = false;
  var lastState = null;

// ===== NUEVO: Variables para promociones =====
let PROMOS_VIGENTES = {};    // Mapa de promociones por producto
let PROMOS_TICKER = null;    // Interval ID para el contador

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
    getTotalFacturasDisponibles();
  }

  // refresca cada 5s (seguro)
  setInterval(function () {
    verificarAperturaCaja();
    getTotalFacturasDisponibles();
  }, 5000);

  // ===========================================================
  //  UI: Botón Apertura/Cierre + Modal
  // ===========================================================
  function validateForm(formId) {
      const form = document.getElementById(formId);
      if (!form) {
          console.error('Formulario no encontrado:', formId);
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
      console.error('getCajero error:', xhr.responseText);
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
      console.warn('Select2 no encontrado. Verifica que el JS/CSS estén cargados.');
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
                  console.warn('Error al destruir Select2:', e);
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
              console.error('Error al inicializar Select2:', e);
          }
      }
      
      return null;
  }

  // Selects del modal de Mesa
  initSelect2WithModal('#ubicacion-mesa', '#modal-mesa', {
      ...baseConfig,
      minimumResultsForSearch: -1
  });
  
  initSelect2WithModal('#estado-mesa', '#modal-mesa', {
      ...baseConfig,
      minimumResultsForSearch: -1
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
    if (!selCat) return;
  
    // Si aún no hay categorías, espera y vuelve a intentarlo
    if (!Array.isArray(categorias) || !categorias.length){
      ensureCategoriasReady().then(()=> fillProdCategoriaOptionsByEstacion(preselectId));
      return;
    }
  
    const est = prodEstacionSeleccionadaUI(); // 'cocina' | 'barra'
    let list = categorias.filter(c => normalizeEstacion(c) === est);
  
    // Fallback: si no hay por estación, muestra todas (para no dejar vacío)
    if (!list.length && categorias.length){
      list = categorias.slice();
    }
  
    if (!list.length){
      selCat.innerHTML = '<option value="">(No hay categorías)</option>';
    } else {
      selCat.innerHTML = list.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
      // Seleccionar algo para activar el badge
      if (preselectId){
        selCat.value = String(preselectId);
      } else if (!selCat.value && list.length){
        selCat.value = String(list[0].id);
      }
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
  function setClienteInfoUI({ nombre = 'Consumidor final', rtn = '' } = {}) {
    if (!clienteInfoElement) return;

    // Asegurar la estructura (icono + .cli-datos con nombre y RTN)
    let datos = clienteInfoElement.querySelector('.cli-datos');
    if (!datos) {
      clienteInfoElement.innerHTML = `
        <i class="fas fa-user"></i>
        <span class="cli-datos">
          <span class="cli-nombre"></span>
          <small class="cli-rtn-wrap is-hidden">
            <i class="fas fa-id-card"></i>
            <span class="cli-rtn"></span>
          </small>
        </span>
      `;
      datos = clienteInfoElement.querySelector('.cli-datos');
    }

    const elNombre = clienteInfoElement.querySelector('.cli-nombre');
    const elWrap   = clienteInfoElement.querySelector('.cli-rtn-wrap');
    const elRtn    = clienteInfoElement.querySelector('.cli-rtn');

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
    $('#btn-imprimir').prop('disabled', disable);
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
        .removeClass('btn-success')
        .addClass('btn-danger')
        .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
    } else {
      $('#btn-guardar')
        .removeClass('btn-danger')
        .addClass('btn-success')
        .html('<i class="fas fa-save"></i> Guardar');
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

  // 1) Consulta si la caja está abierta (1) o cerrada (2)
  function getConsultarAperturaCaja() {
    var estado_apertura = 2; // default cerrada
    $.ajax({
      type: 'POST',
      url: BASE + 'core/getAperturaCajaUsuario.php',
      async: false
    }).done(function (r) {
      try {
        var data = JSON.parse(r);
        estado_apertura = Number(data[0]) || 2;
      } catch (e) {
        console.error('getAperturaCajaUsuario parse:', e);
      }
    }).fail(function () {
      console.error('AJAX getAperturaCajaUsuario');
    });
    return estado_apertura;
  }

  // 2) Contador SAR
  function getTotalFacturasDisponibles() {
    $.ajax({
      type: 'POST',
      url: BASE + 'core/getTotalFacturasDisponibles.php?_=' + Date.now(),
      dataType: 'json'
    })
      .done(function (datos) {
        console.log('[SAR] Datos recibidos:', datos);
        if (!datos || typeof datos.facturasPendientes === 'undefined') {
          showErrorState();
          return;
        }
        facturasDisponibles = Number(datos.facturasPendientes) || 0;
        renderCounter(datos);
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        console.error('Error en AJAX:', textStatus, errorThrown);
        showErrorState();
      });
  }

  function showErrorState(){ if (typeof paintCounterError === 'function') paintCounterError(); } 

  // Abre modal para APERTURA
  function formAperturaBill() {
      $('#formAperturaCaja #proceso_aperturaCaja').val("Aperturar Caja");
      $('#open_caja').show();
      $('#close_caja').hide();
      $('#formAperturaCaja #monto_apertura_grupo').show();

      $('#formAperturaCaja').attr({ 'data-form': 'save' });
      $('#formAperturaCaja').attr({ 'action': '<?php echo SERVERURL; ?>ajax/addAperturaCajaAjax.php' });

      $('#modal_apertura_caja').modal({
          show: true,
          keyboard: false,
          backdrop: 'static'
      });
      
      // Enfocar un campo específico (por ejemplo, el campo de monto)
      $('#modal_apertura_caja').on('shown.bs.modal', function () {
          $('#monto_apertura').focus(); // Reemplaza con el ID de tu campo
      });
  }
  // Abre modal para CIERRE
  function formCierreBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
    $('#open_caja').hide();
    $('#close_caja').show();
    $('#formAperturaCaja #monto_apertura_grupo').hide();

    $('#formAperturaCaja').attr({ 'data-form': 'save' });
    $('#formAperturaCaja').attr({ 'action': '<?php echo SERVERURL; ?>ajax/addCierreCajaFacturasAjax.php' });

    $('#modal_apertura_caja').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });
  }

  // ===== Verificación de estado de caja y bloqueo de UI =====
  function verificarAperturaCaja() {
    var estado = Number(getConsultarAperturaCaja());
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

    // Habilitar/Deshabilitar UI
    toggleUIForCajaAbierta(cajaAbierta);

    // Actualizar contador SAR
    getTotalFacturasDisponibles();
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

    // ejemplo: imprimir bloqueado si no hay caja o SAR invalidado
    $('#btn-imprimir').prop('disabled', !(sarOK && cajaAbierta));
  }

  function initSelectsPromos(){
    try{
      $('#promo-tipo').select2({width:'100%'});
      $('#promo-aplica-a').select2({width:'100%'});
      $('#pp-promocion').select2({width:'100%'});
      $('#pp-productos').select2({width:'100%'});
      $('#pc-promocion').select2({width:'100%'});
      $('#pc-categorias').select2({width:'100%'});
    }catch(e){}
  }

  function setupEventListeners() {
    // Click del botón único (abre el modal correcto)
    $(document).on('click', '#btn-apertura-caja', function () {
      var mode = $(this).data('mode');
      if (mode === 'abrir') {
        formAperturaBill();
      } else {
        formCierreBill();
      }
    });
  
    // Cuando se cierra el modal de apertura/cierre
    $('#modal_apertura_caja').on('hidden.bs.modal', function () {
      verificarAperturaCaja();
    });

      // Dropdown Gestionar
    $(document).on('click','#btn-gestionar-acciones',function(e){
      e.stopPropagation();
      $('#gestionar-menu').toggleClass('show');
    });
    $(document).on('click',function(){ $('#gestionar-menu').removeClass('show'); });
    $(document).on('click','#gestionar-menu button',function(){
      var t = $(this).data('target');
      if(t){ $(t).trigger('click'); }
      $('#gestionar-menu').removeClass('show');
    });

    // Dispara los botones ocultos originales
    $(document).on('click','#gestionar-menu button',function(){
      var t = $(this).data('target');
      if(t){ try{ $(t).trigger('click'); }catch(e){} }
      $('#gestionar-menu').removeClass('show');
    });

    // ====== Apertura y cierre genérico de modales .rs-modal
    $(document).on('click','[data-close]',function(){
      var sel = $(this).data('close'); $(sel).hide();
    });

    // ====== Promos: abrir modales
    $('#btn-gestionar-promos').on('click', function(){
      $('#modal-promociones-list').show();
      // Enfocar el primer elemento del modal después de mostrarlo
      setTimeout(function() {
        $('#modal-promociones-list').find('input, select, textarea').first().focus();
      }, 100);
      // TODO: cargar listado por AJAX
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

    $('#btn-asignar-promo-productos').on('click', function(){
      $('#modal-promo-productos').show();
      setTimeout(function() {
        initSelectsPromos();
        // Enfocar el primer select después de inicializar
        $('#pp-promocion').select2('focus');
      }, 0);
      // TODO: cargar promos, productos y asignados por AJAX
    });

    $('#btn-asignar-promo-categorias').on('click', function(){
      $('#modal-promo-categorias').show();
      setTimeout(function() {
        initSelectsPromos();
        // Enfocar el primer select después de inicializar
        $('#pc-promocion').select2('focus');
      }, 0);
      // TODO: cargar promos, categorías y asignados por AJAX
    });

    /* ============================================================
    * ========== PROMOS - EVENTOS (dentro de setupEventListeners) =
    * ============================================================ */

    // Guardar promoción (Crear/Actualizar)
    $(document).on('click', '#btn-guardar-promocion', async function(){
      try{
        if (!validateForm('form-promocion')) { // Asumiendo que tu formulario tiene id="form-producto"
          return;
        }   
        
        const data = recogerFormPromocion(); // arma el payload
        const accion = data.promo_id ? 'updatePromocion' : 'savePromocion';     

        const res = await apiPromos(accion, data);
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo guardar'); }
        showAlert('success','Éxito', data.promo_id ? 'Promoción actualizada' : 'Promoción creada');
        $('#modal-promocion').hide();

        // Refrescar selects de otros modales (productos/categorías)
        await Promise.all([
          llenarPromosSelect($('#pp-promocion')),
          llenarPromosSelect($('#pc-promocion')),
        ]);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al guardar la promoción');
      }
    });

    if (srvLlevar) srvLlevar.addEventListener('change', () => setServicioTipo('llevar'));
    if (srvMesa)   srvMesa.addEventListener('change',   () => setServicioTipo('mesa'));

    // Cambiar promo en "Asignar productos": carga la lista asignada
    $(document).on('change', '#pp-promocion', async function(){
      const pid = $(this).val();
      $('#pp-listado').html('');
      if (pid) await cargarAsignadosProductos(pid);
    });

    // Guardar asignación productos -> promo
    $(document).on('click', '#btn-guardar-promo-productos', async function(){
      const promo_id = $('#pp-promocion').val();
      const productos_ids = ($('#pp-productos').val() || []).map(v => parseInt(v,10)).filter(Boolean);
      if(!promo_id){ showAlert('warning','Atención','Seleccione una promoción'); return; }
      if(!productos_ids.length){ showAlert('warning','Atención','Seleccione al menos un producto'); return; }
      try{
        const res = await apiPromos('assignPromoProductos', { promo_id, productos_ids });
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo asignar'); }
        showAlert('success','Éxito','Productos asignados');
        $('#pp-productos').val(null).trigger('change');
        await cargarAsignadosProductos(promo_id);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al asignar productos');
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
    });

    // Guardar asignación categorías -> promo
    $(document).on('click', '#btn-guardar-promo-categorias', async function(){
      const promo_id = $('#pc-promocion').val();
      const categorias_ids = ($('#pc-categorias').val() || []).map(v => parseInt(v,10)).filter(Boolean);
      if(!promo_id){ showAlert('warning','Atención','Seleccione una promoción'); return; }
      if(!categorias_ids.length){ showAlert('warning','Atención','Seleccione al menos una categoría'); return; }
      try{
        const res = await apiPromos('assignPromoCategorias', { promo_id, categorias_ids });
        if(!res || !res.status){ throw new Error(res && res.message ? res.message : 'No se pudo asignar'); }
        showAlert('success','Éxito','Categorías asignadas');
        $('#pc-categorias').val(null).trigger('change');
        await cargarAsignadosCategorias(promo_id);
      }catch(e){
        showAlert('error','Error', e.message || 'Error al asignar categorías');
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
    if (btnGuardar) btnGuardar.addEventListener('click', guardarFactura);

    // Imprimir
    if (btnImprimir) {
      btnImprimir.addEventListener('click', function () {
        const id = (facturaActual && (facturaActual.factura_id || facturaActual.id)) ? (facturaActual.factura_id || facturaActual.id) : null;
        if (!id) { showAlert('warning', 'Sin factura', 'No hay una factura cargada para imprimir'); return; }
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
    const r = await fetch(AJAX_PROMOS, {
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
    return fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
      console.error('Error cargando promociones:', error);
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

  async function llenarProductosSelect($sel){
    // Si ya tienes productos cargados globalmente, puedes usarlos.
    // Para ser autónomos, pedimos al backend:
    const res = await apiPromos('loadProductos', {});
    const list = (res && res.productos) ? res.productos : [];
    $sel.empty();
    list.forEach(p=>{
      const opt = new Option(`${p.nombre} (${p.barCode||''})`, p.productos_id, false, false);
      $sel.append(opt);
    });
    $sel.trigger('change');
  }

  async function llenarCategoriasSelect($sel){
    const res = await apiPromos('loadCategorias', { estacion:'todas' });
    const list = (res && res.categorias) ? res.categorias : [];
    $sel.empty();
    list.forEach(c=>{
      const opt = new Option(`${c.nombre}`, c.id, false, false);
      $sel.append(opt);
    });
    $sel.trigger('change');
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
    const res = await apiPromos('loadPromoProductos', { promo_id });
    const items = (res && res.items) ? res.items : [];
    const tbody = $('#pp-listado');
    tbody.html('');
    if(!items.length){
      tbody.append('<tr><td colspan="3" class="text-center muted">Sin productos asignados</td></tr>');
      return;
    }
    items.forEach(row=>{
      const tr = `
        <tr>
          <td>${escapeHtml(row.nombre || '')}</td>
          <td>${escapeHtml(row.barCode || '')}</td>
          <td class="text-right">
            <button type="button" class="btn btn-sm btn-danger pp-del" data-promo="${promo_id}" data-pid="${row.producto_id}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>`;
      tbody.append(tr);
    });
  }

  async function cargarAsignadosCategorias(promo_id){
    const res = await apiPromos('loadPromoCategorias', { promo_id });
    const items = (res && res.items) ? res.items : [];
    const tbody = $('#pc-listado');
    tbody.html('');
    if(!items.length){
      tbody.append('<tr><td colspan="2" class="text-center muted">Sin categorías asignadas</td></tr>');
      return;
    }
    items.forEach(row=>{
      const tr = `
        <tr>
          <td>${escapeHtml(row.nombre || '')}</td>
          <td class="text-right">
            <button type="button" class="btn btn-sm btn-danger pc-del" data-promo="${promo_id}" data-cid="${row.categoria_id}">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>`;
      tbody.append(tr);
    });
  }

// Utilidad: escapar HTML (si ya tienes escapeHtml, esta usa la tuya)
function escapeHtml(s){ return String(s ?? '').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

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

    // ===== cargarReglasCombo =====
  function cargarReglasCombo(comboId){
    return fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:`action=loadComboCategoriaReglas&combo_id=${comboId}`
    })
    .then(r=>r.json())
    .then(d => (d && d.status) ? (d.reglas || []) : []);
  }

  
  // ===== ISV (para totales de la comanda) =====
  function cargarISV() {
    return fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
        else { showAlert('error', 'Error', 'No se pudieron cargar las mesas'); }
      })
      .catch(() => { showAlert('error', 'Error', 'Error al cargar las mesas'); });
  }

  function setServicioTipo(tipo){
    if (tipo === 'mesa') {
      if (srvMesa)   srvMesa.checked = true;
    } else {
      if (srvLlevar) srvLlevar.checked = true;
    }
    toggleServicioUI(tipo);
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
    // Si el usuario elige una mesa, la modalidad pasa a "mesa"
    setServicioTipo('mesa');
  
    // Guardar mesa seleccionada
    mesaSeleccionada = {
      id: mesa.id || mesa.mesa_id,
      numero: mesa.numero,
      capacidad: mesa.capacidad,
      ubicacion: mesa.ubicacion,
      estado: mesa.estado
    };
  
    // Actualizar UI sin perder iconos
    setMesaSeleccionadaUI(mesaSeleccionada.numero);
    // ¡NO cambies el título con textContent para no borrar el <i>!
    if (btnImprimir) btnImprimir.disabled = true;
  
    // Reiniciar comanda visualmente
    comandaItems = [];
    actualizarComandaUI();
  
    // Marcar mesa activa
    highlightMesaSeleccionada();
  
    // Cargar factura (si existe) para esta mesa
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

    limpiarValidacionFormulario('form-mesa');

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
 
    if (!validateForm('form-mesa')) { // Asumiendo que tu formulario tiene id="form-producto"
      return;
    }

    const accion = mesaId ? 'editar' : 'guardar';
    const mensaje = mesaId ? 
      `¿Está seguro que desea editar la mesa ${numeroMesa}?` : 
      `¿Está seguro que desea guardar la nueva mesa ${numeroMesa}?`;
    
    showConfirm(accion === 'editar' ? 'Editar Mesa' : 'Nueva Mesa', mensaje, () => {
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
            showAlert('success', 'Éxito', mesaId ? 'Mesa actualizada correctamente' : 'Mesa guardada correctamente');
            if (modalMesa) modalMesa.style.display = 'none';
            (document.getElementById('mesa-id')||{}).value = '';
            cargarMesas();
          } else {
            showAlert('error', 'Error', data.message || 'No se pudo guardar la mesa');
          }
        })
        .catch(() => { showAlert('error', 'Error', 'Error al guardar la mesa'); });
    });
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
    .then(async data => {
      if (data.status) {
        productos = data.productos || [];
  
        // === CARGAR PROMOCIONES ===
        try {
          await fetchPromosVigentesProductos();
        } catch (e) {
          console.warn('No se pudieron cargar promociones:', e);
          // Continuar aunque falle las promociones
        }
  
        renderizarProductos(productos);
  
        // Iniciar contador de promociones
        try {
          startPromosTicker();
        } catch (e) {
          console.warn('No se pudo iniciar el ticker de promociones:', e);
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
  
    productosList.forEach(producto => {
      const isCombo = !!(window.__comboProductoIds && window.__comboProductoIds.has(parseInt(producto.productos_id,10)));
      const comboBadge = isCombo
        ? '<span class="badge badge-pill badge-primary" style="position:absolute;top:8px;left:8px;z-index:2;">Combo</span>'
        : '';
  
      const productoElement = document.createElement('div');
      productoElement.className = 'producto-item';
      if (!productoElement.style.position) productoElement.style.position = 'relative';
  
      // Acciones (editar)
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
  
      // ==== Imagen (SE QUEDA IGUAL con tu file_name) ====
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
  
      // ==== BADGE de PROMO sobre la imagen (nuevo) ====
      let promoData = PROMOS_VIGENTES[ Number(producto.productos_id) ];
      // si viniera array, toma la de mayor prioridad
      if (Array.isArray(promoData) && promoData.length) {
        promoData = promoData.sort((a,b)=> (b.prioridad||0)-(a.prioridad||0))[0];
      }
      if (promoData) {
        const wrap = document.createElement('div');
        wrap.innerHTML = buildPromoBadge(promoData); // helper nuevo (abajo)
        imagenContainer.appendChild(wrap.firstElementChild);
      }
  
      imagenContainer.appendChild(imagenDiv);
      productoElement.appendChild(imagenContainer);
  
      // Contenido
      const contenidoDiv = document.createElement('div');
      contenidoDiv.className = 'producto-contenido';
  
      const mostrarMayoreo = (producto.cantidad_mayoreo > 0 && producto.precio_mayoreo > 0);
  
      // calcular precio con promo (si hay)
      const calc = precioConPromo(producto, promoData); // helper nuevo (abajo)
  
      // nombre + (si era combo, badge se queda)
      const nombreHtml = `<h4 class="producto-nombre">${producto.nombre}</h4>`;
  
      // precios (si hay promo: tachado + nuevo precio)
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
  
      // mete todo el contenido
      contenidoDiv.innerHTML = `
        ${comboBadge}
        ${nombreHtml}
        ${preciosHtml}
      `;
      productoElement.appendChild(contenidoDiv);
  
      // Botón Agregar (igual)
      const btnAgregar = document.createElement('button');
      btnAgregar.className = 'btn-agregar';
      btnAgregar.innerHTML = '<i class="fas fa-cart-plus"></i> Agregar';
      productoElement.appendChild(btnAgregar);
  
      // Datos que mandas al carrito (igual, respetando tu imagen)
      const datosProducto = {
        id: producto.productos_id,
        nombre: producto.nombre,
        precio: parseFloat(producto.precio_venta),
        descripcion: producto.descripcion || '',
        imagen: producto.file_name ? `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}` : `${SERVERURL}vistas/plantilla/img/products/image_preview.png`,
        categoria_id: producto.categoria_id,
        isv1: parseInt(producto.isv1 || 0) === 1,
        isv2: parseInt(producto.isv2 || 0) === 1,
        para_cocina: 0,
        barCode: producto.barCode || ''
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
      para_cocina: 0,
      barCode: prod.barCode || ''
    };
    agregarProductoComanda(datosProducto);
    return true;
  }

  function guardarCategoriaDesdeModal(){
    const nombre = (document.getElementById('cat-nombre')||{}).value?.trim() || '';
    const idCat  = (document.getElementById('cat-id')||{}).value || '';

    // Primero validar el formulario
    if (!validateForm('form-categoria')) { // Asumiendo que tu formulario tiene id="form-producto"
      return;
    }
    
    const accion = idCat ? 'editar' : 'guardar';
    const mensaje = idCat ? 
      `¿Está seguro que desea editar la categoría "${nombre}"?` : 
      `¿Está seguro que desea guardar la nueva categoría "${nombre}"?`;
    
    showConfirm(accion === 'editar' ? 'Editar Categoría' : 'Nueva Categoría', mensaje, () => {
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
          if (!d.status) { showAlert('error','Error', d.message||'No se pudo guardar'); return; }
          showAlert('success','Éxito', idCat ? 'Categoría actualizada' : 'Categoría guardada');
          if (modalCategoria) modalCategoria.style.display='none';
          (document.getElementById('cat-id')||{}).value = '';
          document.getElementById('titulo-modal-categoria') && (document.getElementById('titulo-modal-categoria').textContent = 'Nueva Categoría');
          cargarCategorias();
        })
        .catch(()=> showAlert('error','Error','No se pudo guardar'));
    });
  }

  function guardarProductoBasico(){
    const { inpNombre, inpDesc, selCat, inpPrecio, chkISV1, chkISV2 } = getProdControls();
  
     // Primero validar el formulario
     if (!validateForm('form-producto')) { // Asumiendo que tu formulario tiene id="form-producto"
        return;
    }

    const id     = (document.getElementById('prod-id')||{}).value || '';
    const nombre = (inpNombre||{}).value?.trim() || '';
    const desc   = (inpDesc||{}).value?.trim() || '';
    const catId  = (selCat||{}).value || '';
    const precio = parseFloat((inpPrecio||{}).value || '0') || 0;
    const isv1   = !!(chkISV1||{}).checked;
    const isv2   = !!(chkISV2||{}).checked;

  
    const esEdicion = !!id;
    const titulo    = esEdicion ? 'Editar Producto' : 'Nuevo Producto';
    const mensaje   = esEdicion
      ? `¿Está seguro que desea editar el producto "${nombre}"?`
      : `¿Está seguro que desea guardar el nuevo producto "${nombre}"?`;
  
    showConfirm(titulo, mensaje, async () => {
      try{
        // FormData para que PHP reciba $_POST y $_FILES
        const fd = new FormData();
        fd.append('action', esEdicion ? 'updateProductoBasico' : 'saveProductoBasico');
        if (esEdicion) fd.append('productos_id', String(parseInt(id,10)));
  
        fd.append('nombre', nombre);
        fd.append('descripcion', desc);
        fd.append('categoria_id', String(parseInt(catId,10)));
        fd.append('precio_venta', String(precio.toFixed(2)));
        fd.append('isv1', isv1 ? '1' : '0');
        fd.append('isv2', isv2 ? '1' : '0'); // permite marcar ambos si aplica
  
        // Imagen (opcional)
        const file = getProductoImagenFile && getProductoImagenFile();
        if (file) fd.append('imagen_producto', file);
  
        const resp = await fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
          method: 'POST',
          body: fd
        });
  
        // Manejo de respuesta
        let d = null;
        try { d = await resp.json(); } catch(e){ /* respuesta inválida */ }
        if (!d || !d.status){
          const msg = (d && (d.message||d.msg)) || 'No se pudo guardar';
          showAlert('error','Error', msg);
          return;
        }
  
        showAlert('success','Éxito', esEdicion ? 'Producto actualizado' : 'Producto guardado');
  
        // Cerrar modal y limpiar
        if (typeof modalProducto !== 'undefined' && modalProducto) modalProducto.style.display = 'none';
        const elId = document.getElementById('prod-id'); if (elId) elId.value = '';
        const t = document.getElementById('titulo-modal-producto'); if (t) t.textContent = 'Nuevo Producto';
        if (typeof resetProductoImagen === 'function') resetProductoImagen();
  
        // Refrescar listado
        if (typeof cargarProductos === 'function') cargarProductos();
  
      } catch(err){
        console.error(err);
        showAlert('error','Error','No se pudo guardar el producto');
      }
    });
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
      identificacion: (selectedClienteForModal.identificacion || '').trim()
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

  function guardarClienteBasico(){
    const id         = (document.getElementById('cli-id')||{}).value || '';
    const nombre     = (document.getElementById('cli-nombre')||{}).value?.trim() || '';
    const rtn        = (document.getElementById('cli-rtn')||{}).value?.trim() || '';
    const localidad  = (document.getElementById('cli-localidad')||{}).value?.trim() || '';
    const telefono   = (document.getElementById('cli-telefono')||{}).value?.trim() || '';
    const correo     = (document.getElementById('cli-correo')||{}).value?.trim() || '';

    // Primero validar el formulario
    if (!validateForm('form-nuevo-cliente')) { // Asumiendo que tu formulario tiene id="form-producto"
      return;
    }

    const accion = id ? 'editar' : 'guardar';
    const mensaje = id ? 
      `¿Está seguro que desea editar el cliente "${nombre}"?` : 
      `¿Está seguro que desea guardar el nuevo cliente "${nombre}"?`;
    
    showConfirm(accion === 'editar' ? 'Editar Cliente' : 'Nuevo Cliente', mensaje, () => {
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
          if (!d.status){ showAlert('error','Error', d.message || 'No se pudo guardar el cliente'); return; }
          showAlert('success','Éxito', id ? 'Cliente actualizado' : 'Cliente guardado');
          if (modalNuevoCliente) modalNuevoCliente.style.display = 'none';
          (document.getElementById('cli-id')||{}).value = '';
          document.getElementById('titulo-modal-cliente') && (document.getElementById('titulo-modal-cliente').textContent = 'Nuevo Cliente');

          cargarClientes().then(()=>{
            const cli = d.cliente;
            if (cli && cli.clientes_id){
              clienteSeleccionado = {
                id: cli.clientes_id,
                nombre: cli.nombre || nombre,
                identificacion: (cli.identificacion || cli.rtn || rtn || '').trim()
              };
              pintarClienteInfoHeader();
            }
          });          
        })
        .catch(()=> showAlert('error','Error','No se pudo guardar el cliente'));
    });
  }

  function abrirEdicionCliente(c){
    editContext.clienteId = c.clientes_id;

    // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
    limpiarValidacionFormulario('form-nuevo-cliente');

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
  fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `action=loadFacturaMesa&mesa_id=${encodeURIComponent(mesaId)}`
  })
  .then(r => r.json())
  .then(data => {
    if (data && data.status) {
      // En mesa
      setServicioTipo('mesa');

      facturaActual    = data.factura || null;
      mesaSeleccionada = data.mesa    || mesaSeleccionada;

      const itemsArr = Array.isArray(data.items) ? data.items : [];
      comandaItems = itemsArr.map(item => ({
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
        precio:   parseFloat(item.precio),
        total:    parseFloat(item.precio) * parseFloat(item.cantidad)
      }));

      // Mesa en el header (sin depender de variables externas)
      const nomMesa = mesaSeleccionada
        ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || mesaSeleccionada.nombre || mesaSeleccionada.nombre_mesa || null)
        : null;
      setMesaSeleccionadaUI(nomMesa);

      // Título de factura robusto
      const numFactura = facturaActual
        ? (facturaActual.number || facturaActual.numero || facturaActual.factura_numero || facturaActual.id || facturaActual.factura_id)
        : null;
      if (facturaTitle) {
        facturaTitle.innerHTML = `<i class="fas fa-receipt"></i> ${numFactura ? 'Factura #' + numFactura : 'Factura abierta'}`;
      }

      // Observaciones (notas/observaciones)
      if (observacionesTextarea) {
        observacionesTextarea.value = (facturaActual && (facturaActual.notas || facturaActual.observaciones || '')) || '';
      }
      if (btnImprimir) btnImprimir.disabled = false;

      // --- Cliente de la factura (tolerante a diferentes llaves) ---
      (function () {
        const f = data.factura || {};
        const c = f.cliente || data.cliente || {};

        const clienteId = parseInt(
          (f.cliente_id ?? c.id ?? c.clientes_id ?? 0),
          10
        ) || 0;

        const clienteNombre = (
          f.cliente_nombre ??
          f.nombre_cliente ??
          c.nombre ??
          ''
        ).toString().trim();

        const clienteIdent = (
          f.cliente_identificacion ??
          f.cliente_rtn ??
          c.identificacion ??
          c.rtn ??
          ''
        ).toString().trim();

        clienteSeleccionado = (clienteId > 0 || clienteNombre)
          ? { id: clienteId, nombre: (clienteNombre || 'Cliente'), identificacion: clienteIdent }
          : { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };

        pintarClienteInfoHeader();
      })();

      actualizarComandaUI();
      highlightMesaSeleccionada();

    } else {
      // Sin factura previa (sigue servicio "mesa")
      setServicioTipo('mesa');

      facturaActual = null;
      comandaItems = [];
      actualizarComandaUI();

      if (facturaTitle) {
        facturaTitle.innerHTML = `<i class="fas fa-receipt"></i> Nueva Comanda`;
      }
      if (btnImprimir) btnImprimir.disabled = true;

      // Evita ReferenceError: no uses mesaSeleccionadaElement aquí
      const nomMesa = mesaSeleccionada
        ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || null)
        : null;
      setMesaSeleccionadaUI(nomMesa);

      highlightMesaSeleccionada();
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
    if (!comandaItems.length) { showAlert('warning','Advertencia','Agregue productos a la comanda'); return; }
  
    const metodoPago = (document.querySelector('input[name="metodo-pago"]:checked') || {}).value || '';
    const observaciones = (observacionesTextarea && observacionesTextarea.value || '').trim();
  
    const servicio_tipo = getServicioTipo(); // 'mesa' | 'llevar'
    const mesa_id = (servicio_tipo === 'mesa' && mesaSeleccionada) ? mesaSeleccionada.id : 0;
  
    const facturaData = {
      servicio_tipo: servicio_tipo,     // <--- NUEVO
      mesa_id: mesa_id,                 // 0 si es para llevar
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
    const mensaje = facturaData.factura_id
      ? '¿Está seguro que desea actualizar esta factura?'
      : '¿Está seguro que desea guardar esta factura?';
  
    showConfirm(facturaData.factura_id ? 'Actualizar Factura' : 'Guardar Factura', mensaje, () => {
      fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: accion, data: facturaData })
      })
        .then(r => r.json())
        .then(data => {
          if (!data.status) { showAlert('error','Error', data.message || 'Error al guardar la factura'); return; }
          showAlert('success', 'Éxito', facturaData.factura_id ? 'Factura actualizada' : 'Factura guardada');
  
          if (accion === 'saveFactura' && data.factura) {
            facturaActual = data.factura;
          } else if (accion === 'updateFactura' && data.factura_id) {
            if (!facturaActual) facturaActual = {};
            facturaActual.factura_id = data.factura_id;
            facturaActual.id = data.factura_id;
          }
  
          if (facturaActual && facturaActual.number && facturaTitle) facturaTitle.textContent = `Factura #${facturaActual.number}`;
          if (btnImprimir) btnImprimir.disabled = false;
  
          // Refrescar estado de mesas (por si ocupó/liberó)
          cargarMesas();
  
          // Si se pagó (metodo_pago != ''), imprime
          if (metodoPago !== '') {
            const fid = (data.factura_id)
              ? data.factura_id
              : (data.factura && (data.factura.factura_id || data.factura.id))
                ? (data.factura.factura_id || data.factura.id)
                : (facturaActual && (facturaActual.factura_id || facturaActual.id))
                  ? (facturaActual.factura_id || facturaActual.id)
                  : null;
            if (fid && typeof printBill === 'function') printBill(fid);
          }
        })
        .catch(() => { showAlert('error','Error','Error al guardar la factura'); });
    });
  }
  
  function cerrarFactura() {
    if (!facturaActual || !(facturaActual.id || facturaActual.factura_id)) {
      showAlert('warning','Advertencia','No hay factura abierta'); 
      return; 
    }
    const fid = facturaActual.id || facturaActual.factura_id;
  
    showConfirm('Cerrar Factura', '¿Está seguro que desea cerrar esta factura?', () => {
      fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=closeFactura&factura_id=${encodeURIComponent(fid)}`
      })
        .then(r => r.json())
        .then(data => {
          if (!data.status) { showAlert('error','Error', data.message || 'Error al cerrar la factura'); return; }
  
          showAlert('success','Cerrada','La factura fue cerrada');
          facturaActual = null;
          comandaItems = [];
          actualizarComandaUI();
  
          // UI neutral
          setServicioTipo('llevar');      
          setMesaSeleccionadaUI(null);    
          if (facturaTitle) facturaTitle.innerHTML = `<i class="fas fa-receipt"></i> Nueva Comanda`;
          if (btnImprimir) btnImprimir.disabled = true;
  
          cargarMesas();
        })
        .catch(() => { showAlert('error','Error','Error al cerrar la factura'); });
    });
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

    // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
    limpiarValidacionFormulario('form-producto');

    if (modalProducto) modalProducto.style.display = 'block';
  }

  function abrirEdicionCategoria(cat){
    editContext.categoriaId = cat.id;
    const inp = document.getElementById('cat-nombre');
    const hid = document.getElementById('cat-id');
    if (inp) inp.value = cat.nombre || '';
    if (hid) hid.value = String(cat.id || '');
    document.getElementById('titulo-modal-categoria') && (document.getElementById('titulo-modal-categoria').textContent = 'Editar Categoría');
    
    // CAMBIO: Determinar y establecer la estación correcta
    const estacionActual = normalizeEstacion(cat);
    const radioEstacion = document.querySelector(`#prod-estacion input[value="${estacionActual}"]`);
    if (radioEstacion) radioEstacion.checked = true;
    
    // LIMPIAR VALIDACIÓN ANTES DE MOSTRAR
    limpiarValidacionFormulario('form-categoria');

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
        showNotify('error', 'Error', 'No puede realizar esta accion a las facturas canceladas!');
        resetProductoImagen();
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
          showNotify('error', 'Error', 'La imagen no debe exceder 2MB')
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
  return fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
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
      <div class="help-message">
        <h5>¿Cómo configurar un combo?</h5>
        <p><strong>Producto que representa el combo:</strong> el artículo padre que vendes (ticket).</p>
        <p><strong>Componentes:</strong> insumos o artículos hijos que se descuentan del inventario.</p>
        <p><strong>Cantidad por porción:</strong> cuánto consume el combo de este insumo por unidad vendida.</p>
        <p><strong>Unidad:</strong> g, ml, und u otra unidad compatible con tu inventario.</p>
        <p><strong>Merma (%):</strong> pérdida prevista. <em>Ej. 10 = suma 10% al consumo</em> para evitar quedarte corto.</p>
        <p><strong>Componente obligatorio:</strong> si está marcado, siempre se incluye y descuenta. Si no, es opcional.</p>
        <p><strong>Precio extra:</strong> si el componente es opcional y el cliente lo elige, se suma este monto.</p>
        <p><strong>Orden:</strong> posición de visualización en la lista del combo.</p>
      </div>`;
  }

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
  fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php',{
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

  wrap.innerHTML = `
    <div class="form-group" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <label style="margin-right:8px;">Precio del combo</label>
      <label class="radio-container">
        <input type="radio" name="comboPrecioModo" value="heredar" ${ (precio===null || precio===undefined) ? 'checked':'' }>
        <span style="margin-left:6px;">Heredar del producto maestro</span>
      </label>
      <label class="radio-container">
        <input type="radio" name="comboPrecioModo" value="propio" ${ (precio!==null && precio!==undefined) ? 'checked':'' }>
        <span style="margin-left:6px;">Fijar precio propio</span>
      </label>
      <input type="number" class="form-control" id="combo-precio-valor" min="0" step="0.01" placeholder="L 0.00" style="max-width:160px;" ${ (precio===null || precio===undefined) ? 'disabled':'' } value="${ (precio!==null && precio!==undefined) ? Number(precio).toFixed(2) : '' }">
    </div>
  `;

  const radios = wrap.querySelectorAll('input[name="comboPrecioModo"]');
  const inputPrecio = document.getElementById('combo-precio-valor');
  radios.forEach(r=>{
    r.addEventListener('change', ()=>{
      if (r.value==='propio'){
        inputPrecio.disabled = false;
        if (!inputPrecio.value) inputPrecio.value = '0.00';
        inputPrecio.focus();
      } else {
        inputPrecio.value = '';
        inputPrecio.disabled = true;
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

  fetch(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', { method:'POST', body: fd })
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
      ['.combo-item-precio-extra',  'Si el componente es opcional y se elige, suma este monto.'],
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

  // 'categorias' debe estar cargado (ya lo usas en otros lados)
  const opts = (Array.isArray(categorias) ? categorias : []).map(c => {
    const id = c.id || c.categoria_id || c.categoriaId || '';
    const nombre = c.nombre || c.text || '';
    return `<option value="${id}">${nombre}</option>`;
  }).join('');

  body.innerHTML = '';

  (reglas || []).forEach(r => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>
        <select class="form-control regla-categoria">
          <option value=""></option>
          ${opts}
        </select>
      </td>
      <td>
        <input type="number" class="form-control regla-max" min="1" value="${parseInt(r.max_seleccion||1,10)}">
      </td>
      <td class="text-right">
        <button type="button" class="btn btn-danger btn-sm" data-remove-regla="1">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;
    body.appendChild(row);
    const sel = row.querySelector('.regla-categoria');
    if (sel) sel.value = String(r.categoria_id || '');
  });

  // Delegación para quitar
  body.onclick = (e)=>{
    const btn = e.target.closest('button[data-remove-regla="1"]');
    if (!btn) return;
    const tr = btn.closest('tr');
    if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
  };
}

function collectReglasCategoria(){
  const rows = Array.from(document.querySelectorAll('#combo-reglas-rows tr'));
  return rows.map(tr => {
    const cat = tr.querySelector('.regla-categoria')?.value || '';
    const max = parseInt(tr.querySelector('.regla-max')?.value || '1', 10);
    if (!cat) return null;
    return { categoria_id: parseInt(cat,10), max_seleccion: isNaN(max) ? 1 : Math.max(1, max) };
  }).filter(Boolean);
}

function cargarReglasCombo(comboId){
  return fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
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

  showConfirm(accion==='editar' ? 'Editar Combo' : 'Nuevo Combo', mensaje, () => {
    const payload = {
      productos_id: parseInt(productos_id,10),
      activo,
      precio_venta, // null -> back debe interpretar como "usar precio del producto maestro"
      items,
      reglas: collectReglasCategoria()
    };
    let action = 'saveCombo';
    if (comboId){
      action = 'updateCombo';
      payload.combo_id = parseInt(comboId,10);
    }

    fetch(BASE + ' core/facturasRestaurante/facturasRestauranteAjax.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action, data: payload })
    })
    .then(r=>r.json())
    .then(d=>{
      if (!d || !d.status){
        showAlert('error','Error', d && d.message ? d.message : 'No se pudo guardar el combo');
        return;
      }
      showAlert('success','Éxito', comboId ? 'Combo actualizado' : 'Combo creado');
      if (modalComboEditor) modalComboEditor.style.display = 'none';
      if (modalCombos && modalCombos.style.display==='block'){
        cargarCombos();
      }
    })
    .catch(()=> showAlert('error','Error','Error al guardar combo'));
  });
}

function eliminarCombo(comboId){
  showConfirm('Eliminar Combo', '¿Está seguro que desea eliminar este combo?', () => {
    const fd = new FormData();
    fd.append('action','deleteCombo');
    fd.append('combo_id', String(comboId));
    fetch(BASE + ' core/facturasRestaurante/facturasRestauranteAjax.php',{
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
  const nombre = (clienteSeleccionado && clienteSeleccionado.nombre) ? clienteSeleccionado.nombre : 'Consumidor final';
  const rtn    = (clienteSeleccionado && clienteSeleccionado.identificacion) ? String(clienteSeleccionado.identificacion).trim() : '';

  // Pinta: nombre arriba, RTN (si hay) abajo en pequeño
  setClienteInfoUI({ nombre, rtn });
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
function setProdEstacion(value = 'cocina') {
  const wrap = document.getElementById('prod-estacion');
  if (!wrap) return;

  const inputs = wrap.querySelectorAll('input[name="prodEstacion"]');
  inputs.forEach(inp => {
    const active = (inp.value === value);
    inp.checked = active;

    // Mantén sincronizado el aspecto visual (label .active si usas estilos tipo btn-group)
    const btn = inp.closest('label') || inp.closest('.btn');
    if (btn) {
      btn.classList.toggle('active', active);
    }
  });

  // Dispara change en el que quedó seleccionado para refrescar categorías
  const checked = wrap.querySelector('input[name="prodEstacion"]:checked');
  if (checked) checked.dispatchEvent(new Event('change', { bubbles: true }));
}

function setCatEstacion(value='cocina'){
  const radio = document.querySelector(`#cat-estacion input[value="${value}"]`);
  if (radio) radio.checked = true;
}

function setProdEstacion(value='cocina'){
  const r = document.querySelector(`#prod-estacion input[name="prodEstacion"][value="${value}"]`);
  if (r) r.checked = true;
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
      console.warn('Select2 no encontrado. Verifica que el JS/CSS estén cargados.');
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
                console.warn('Error al destruir Select2:', e);
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
            console.error('Error al inicializar Select2:', e);
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
              console.error('Select2 no se cargó después de varios intentos');
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
              config.minimumResultsForSearch = -1;
          }
          
          try {
              if ($select.data('select2')) {
                  $select.select2('destroy');
              }
              $select.select2(config);
          } catch (error) {
              console.error('Error al inicializar Select2 para', selectId, error);
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
    showNotify(icon, title, text);
  }

  function showConfirm(title, text, callback) {
    if (typeof swal !== 'undefined') {
      swal({
        title: title,
        text: text,
        icon: "warning",
        buttons: true,
        dangerMode: true,
      }).then((willConfirm) => {
        if (willConfirm) {
          callback();
        }
      });
    } else {
      if (confirm(`${title}: ${text}`)) {
        callback();
      }
    }
  }

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