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

  // ===========================================================
  // PANTALLA COMPLETA — RESTAURANTE / POS
  // ===========================================================
  const btnFullscreenRestaurante = document.getElementById('btn-fullscreen-restaurante');

  function rsFullscreenElementActual(){
    return document.fullscreenElement
      || document.webkitFullscreenElement
      || document.msFullscreenElement
      || null;
  }

  function rsActualizarBotonFullscreen(){
    if(!btnFullscreenRestaurante) return;

    const activo = !!rsFullscreenElementActual();

    btnFullscreenRestaurante.setAttribute('aria-pressed', activo ? 'true' : 'false');
    btnFullscreenRestaurante.setAttribute(
      'aria-label',
      activo ? 'Salir de pantalla completa' : 'Activar pantalla completa'
    );
    btnFullscreenRestaurante.title = activo ? 'Salir de pantalla completa' : 'Pantalla completa';

    const icono = btnFullscreenRestaurante.querySelector('i');
    const texto = btnFullscreenRestaurante.querySelector('span');

    if(icono){
      icono.className = activo ? 'fas fa-compress' : 'fas fa-expand';
    }
    if(texto){
      texto.textContent = activo ? 'Salir de pantalla completa' : 'Pantalla completa';
    }

    btnFullscreenRestaurante.classList.toggle('is-active', activo);
    document.body.classList.toggle('rs-fullscreen-activo', activo);
  }

  async function rsAlternarPantallaCompleta(){
    try{
      if(rsFullscreenElementActual()){
        if(document.exitFullscreen){
          await document.exitFullscreen();
        }else if(document.webkitExitFullscreen){
          document.webkitExitFullscreen();
        }else if(document.msExitFullscreen){
          document.msExitFullscreen();
        }
        return;
      }

      const objetivo = document.documentElement;

      if(objetivo.requestFullscreen){
        await objetivo.requestFullscreen();
      }else if(objetivo.webkitRequestFullscreen){
        objetivo.webkitRequestFullscreen();
      }else if(objetivo.msRequestFullscreen){
        objetivo.msRequestFullscreen();
      }else if(typeof showNotify === 'function'){
        showNotify(
          'info',
          'Pantalla completa',
          'Este navegador no permite activar pantalla completa desde la página.'
        );
      }
    }catch(error){
      console.warn('[Restaurante] Pantalla completa:', error);

      if(typeof showNotify === 'function'){
        showNotify(
          'info',
          'Pantalla completa',
          'El navegador bloqueó el cambio de pantalla completa.'
        );
      }
    }finally{
      window.setTimeout(rsActualizarBotonFullscreen, 50);
    }
  }

  if(btnFullscreenRestaurante){
    btnFullscreenRestaurante.addEventListener('click', function(event){
      event.preventDefault();
      rsAlternarPantallaCompleta();
    });
    rsActualizarBotonFullscreen();
  }

  document.addEventListener('fullscreenchange', rsActualizarBotonFullscreen);
  document.addEventListener('webkitfullscreenchange', rsActualizarBotonFullscreen);
  document.addEventListener('MSFullscreenChange', rsActualizarBotonFullscreen);

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
  let ultimoEstadoCajaConfirmado = null;
  var lastState = null;
  let servicioActual = 'mesa'; // 'mesa' | 'llevar'
  let PROMOS_VIGENTES = {};    // Mapa de promociones por producto
  let PROMOS_TICKER = null;    // Interval ID para el contador

  // Cada selección de mesa invalida cualquier respuesta asíncrona anterior.
  // Evita que una carga lenta de otra mesa vuelva a pintar productos/factura
  // sobre la mesa que el cajero acaba de seleccionar.
  let cargaFacturaMesaSecuencia = 0;

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
  const buscarMesaRapido = document.getElementById('buscar-mesa-rapido');
  const mesasCount = document.getElementById('mesas-count');
  let filtroMesaRapido = '';
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

  // POLÍTICA DE MODALES RESTAURANTE:
  // Guardar o editar nunca debe cerrar un modal automáticamente.
  // El cierre solo ocurre por X, botones con data-close (Cerrar/Cancelar) o ESC.
  // Close genérico por X (span.close) y por data-close
  const closeModalButtonsX = document.querySelectorAll('.close');
  const closeModalButtonsData = document.querySelectorAll('[data-close]');

  const formMesa = document.getElementById('form-mesa');
  const formNuevoCliente = document.getElementById('form-nuevo-cliente');

  const PRODUCT_TILE_SELECTOR = '[data-producto-id]';
  const MESA_TILE_SELECTOR = '[data-mesa-id]';  


  // ===========================================================
  //  ASISTENTE MÓVIL RESTAURANTE / POS
  //  SOLO TELÉFONOS. Reutiliza la lógica y botones existentes.
  // ===========================================================
  const RS_MOBILE_QUERY = '(max-width: 599px)';
  let rsMobileMedia = null;
  let rsMobileStep = 'servicio';
  let rsMobileObserver = null;

  function isMobileAssistantActive(){
    return !!(rsMobileMedia && rsMobileMedia.matches);
  }

  function rsMobileMesasEnabled(){
    return !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_mesas) === 0);
  }

  function rsMobileServicio(){
    return getServicioTipo() === 'mesa' ? 'mesa' : 'llevar';
  }

  function rsMobileItemCount(){
    return (Array.isArray(comandaItems) ? comandaItems : [])
      .reduce((sum,item)=>sum + Number(item && item.cantidad ? item.cantidad : 0), 0);
  }

  function rsMobileTotal(){
    const el = totalElement || document.getElementById('total');
    if(!el) return 0;
    const raw = String(el.textContent || '')
      .replace(/[^\d.,-]/g,'')
      .replace(/,/g,'');
    return Number.parseFloat(raw) || 0;
  }

  function rsMobileCliente(){
    const nombre = document.querySelector('#cliente-info .cli-nombre');
    return String(
      (nombre && nombre.textContent) ||
      (clienteSeleccionado && clienteSeleccionado.nombre) ||
      'Consumidor final'
    ).trim();
  }

  function rsMobileRtn(){
    const rtn = document.querySelector('#cliente-info .cli-rtn');
    if(rtn && String(rtn.textContent || '').trim()) return String(rtn.textContent).trim();
    return String((clienteSeleccionado && clienteSeleccionado.identificacion) || '').trim();
  }

  function rsMobileMesa(){
    if(rsMobileServicio() !== 'mesa') return 'Para llevar';
    if(mesaSeleccionada){
      const numero = mesaSeleccionada.numero || mesaSeleccionada.Numero || mesaSeleccionada.mesa_numero || '';
      return numero ? ('Mesa ' + String(numero).replace(/^Mesa\s*/i,'')) : 'Mesa seleccionada';
    }
    return 'Mesa no seleccionada';
  }

  function rsMobileOriginalAllowed(el){
    if(!el) return false;
    if(el.hidden) return false;
    if(String(el.style && el.style.display || '').toLowerCase() === 'none') return false;
    return true;
  }

  function rsMobileProxyClick(selector){
    const target = document.querySelector(selector);
    if(target && !target.disabled) target.click();
  }

  function rsMobileCreate(){
    if(document.getElementById('rs-mobile-assistant')) return;

    const main = document.querySelector('.main-content');
    if(!main) return;

    const assistant = document.createElement('section');
    assistant.id = 'rs-mobile-assistant';
    assistant.className = 'rs-mobile-assistant';
    assistant.setAttribute('aria-label','Asistente de venta');
    assistant.innerHTML = `
      <div class="rs-mobile-progress">
        <button type="button" class="rs-mobile-progress-step" data-rs-step="servicio">
          <span class="rs-mobile-step-number">1</span><span>Servicio</span>
        </button>
        <button type="button" class="rs-mobile-progress-step" data-rs-step="productos">
          <span class="rs-mobile-step-number">2</span><span>Productos</span>
        </button>
        <button type="button" class="rs-mobile-progress-step" data-rs-step="pedido">
          <span class="rs-mobile-step-number">3</span><span>Pedido</span>
        </button>
        <button type="button" class="rs-mobile-progress-step" data-rs-step="caja">
          <span class="rs-mobile-step-number">4</span><span>Cliente / Caja</span>
        </button>
      </div>

      <div class="rs-mobile-context">
        <span><i class="fas fa-concierge-bell"></i><b id="rs-mobile-context-service">Para llevar</b></span>
        <span><i class="fas fa-user"></i><b id="rs-mobile-context-client">Consumidor final</b></span>
      </div>

      <div class="rs-mobile-quick-actions">
        <button type="button" data-rs-proxy="#btn-cuentas-abiertas"><i class="fas fa-folder-open"></i><span>Cuentas</span></button>
        <button type="button" data-rs-proxy="#btn-factura-recurrente"><i class="fas fa-calendar-alt"></i><span>Recurrente</span></button>
        <button type="button" id="rs-mobile-manage" data-rs-proxy="#btn-gestionar-acciones"><i class="fas fa-tools"></i><span>Gestionar</span></button>
      </div>
    `;
    main.insertBefore(assistant, main.firstChild);

    const cajaCard = document.createElement('section');
    cajaCard.id = 'rs-mobile-caja-card';
    cajaCard.className = 'rs-mobile-caja-card';
    cajaCard.innerHTML = `
      <div class="rs-mobile-caja-title"><i class="fas fa-user-check"></i><span>Cliente de la venta</span></div>
      <div class="rs-mobile-caja-client">
        <strong id="rs-mobile-caja-client-name">Consumidor final</strong>
        <span id="rs-mobile-caja-client-rtn" style="display:none"></span>
      </div>
      <button type="button" id="rs-mobile-change-client"><i class="fas fa-exchange-alt"></i> Cambiar cliente</button>
      <div id="rs-mobile-invoice-type" class="rs-mobile-invoice-type" style="display:none;">
        <span><i class="fas fa-file-invoice-dollar"></i> Condición de factura</span>
        <div class="rs-mobile-invoice-type-options">
          <button type="button" id="rs-mobile-tipo-contado" data-rs-tipo-factura="contado" class="is-active" aria-pressed="true"><i class="fas fa-money-bill-wave"></i> Contado</button>
          <button type="button" id="rs-mobile-tipo-credito" data-rs-tipo-factura="credito" aria-pressed="false"><i class="fas fa-calendar-check"></i> Crédito</button>
        </div>
      </div>
      <div class="rs-mobile-caja-note">Puedes cambiar el cliente y, si está habilitado, la condición de factura antes de finalizar.</div>
    `;

    const panelComandaLocal = document.getElementById('panel-comanda');
    if(panelComandaLocal && panelComandaLocal.parentNode){
      panelComandaLocal.parentNode.insertBefore(cajaCard, panelComandaLocal);
    }

    const bottom = document.createElement('nav');
    bottom.id = 'rs-mobile-bottom';
    bottom.className = 'rs-mobile-bottom';
    bottom.setAttribute('aria-label','Acciones del asistente');
    bottom.innerHTML = `
      <button type="button" id="rs-mobile-back" class="rs-mobile-action rs-mobile-action-back">
        <i class="fas fa-arrow-left"></i><span>Atrás</span>
      </button>
      <div class="rs-mobile-sale-summary">
        <small id="rs-mobile-count">0 productos</small>
        <strong id="rs-mobile-total">L 0.00</strong>
      </div>
      <button type="button" id="rs-mobile-secondary" class="rs-mobile-action rs-mobile-action-secondary" style="display:none"></button>
      <button type="button" id="rs-mobile-next" class="rs-mobile-action rs-mobile-action-next">
        <span>Continuar</span><i class="fas fa-arrow-right"></i>
      </button>
    `;
    document.body.appendChild(bottom);

    assistant.addEventListener('click',function(e){
      const proxy = e.target.closest('[data-rs-proxy]');
      if(proxy){
        e.preventDefault();
        rsMobileProxyClick(proxy.getAttribute('data-rs-proxy'));
        return;
      }
      const step = e.target.closest('[data-rs-step]');
      if(step){
        e.preventDefault();
        rsMobileSetStep(step.getAttribute('data-rs-step'));
      }
    });

    document.getElementById('rs-mobile-back')?.addEventListener('click',rsMobileBack);
    document.getElementById('rs-mobile-next')?.addEventListener('click',rsMobileNext);
    document.getElementById('rs-mobile-secondary')?.addEventListener('click',rsMobileSecondary);
    document.getElementById('rs-mobile-change-client')?.addEventListener('click',function(){
      rsMobileProxyClick('#btn-cambiar-cliente');
    });

    rsMobileObserver = new MutationObserver(function(){
      if(isMobileAssistantActive()) rsMobileUpdate();
    });

    [
      comandaItemsContainer,
      totalElement,
      clienteInfoElement,
      mesaSeleccionadaElement,
      document.querySelector('.factura-actions'),
      document.getElementById('gestion-fija')
    ].filter(Boolean).forEach(function(el){
      rsMobileObserver.observe(el,{childList:true,subtree:true,attributes:true,characterData:true});
    });
  }

  function rsMobileSetStep(step){
    if(!isMobileAssistantActive()) return;

    const valid = ['servicio','productos','pedido','caja'];
    if(!valid.includes(step)) step = rsMobileMesasEnabled() ? 'servicio' : 'productos';
    if(!rsMobileMesasEnabled() && step === 'servicio') step = 'productos';

    rsMobileStep = step;
    document.body.setAttribute('data-rs-mobile-step',step);

    document.querySelectorAll('#rs-mobile-assistant [data-rs-step]').forEach(function(btn){
      const active = btn.getAttribute('data-rs-step') === step;
      btn.classList.toggle('is-active',active);
      btn.setAttribute('aria-current',active ? 'step' : 'false');
    });

    if(step === 'productos' && buscarProductoInput){
      window.setTimeout(function(){
        try{ buscarProductoInput.focus({preventScroll:true}); }catch(_){}
      },100);
    }

    rsMobileUpdate();
  }

  function rsMobileBack(){
    if(rsMobileStep === 'caja') return rsMobileSetStep('pedido');
    if(rsMobileStep === 'pedido') return rsMobileSetStep('productos');
    if(rsMobileStep === 'productos' && rsMobileMesasEnabled()) return rsMobileSetStep('servicio');
  }

  function rsMobileNext(){
    const count = rsMobileItemCount();

    if(rsMobileStep === 'servicio'){
      if(rsMobileServicio() === 'mesa' && !mesaIdActual()){
        showAlert('warning','Mesa requerida','Seleccione una mesa disponible antes de continuar.');
        return;
      }
      rsMobileSetStep('productos');
      return;
    }

    if(rsMobileStep === 'productos'){
      if(count <= 0){
        showAlert('warning','Sin productos','Agregue al menos un producto para continuar.');
        return;
      }
      rsMobileSetStep('pedido');
      return;
    }

    if(rsMobileStep === 'pedido'){
      if(count <= 0){
        showAlert('warning','Pedido vacío','Agregue productos antes de continuar.');
        rsMobileSetStep('productos');
        return;
      }

      // En mesa con comandas se conserva exactamente la acción actual:
      // Enviar a cocina / Actualizar cocina.
      if(rsMobileServicio() === 'mesa' && usaComandasOperacion()){
        const principal = document.getElementById('btn-guardar');
        if(principal && !principal.disabled){
          principal.click();
          return;
        }
      }

      rsMobileSetStep('caja');
      return;
    }

    if(rsMobileStep === 'caja'){
      if(count <= 0){
        showAlert('warning','Pedido vacío','Agregue productos antes de cobrar.');
        return;
      }

      if(rsMobileServicio() === 'mesa'){
        const cobrarMesa = document.getElementById('btn-cobrar-mesa');
        if(cobrarMesa && !cobrarMesa.disabled && rsMobileOriginalAllowed(cobrarMesa)){
          cobrarMesa.click();
          return;
        }
      }

      const principal = document.getElementById('btn-guardar');
      if(principal && !principal.disabled) principal.click();
    }
  }

  function rsMobileSecondary(){
    if(rsMobileStep === 'pedido'){
      const guardarCuenta = document.getElementById('btn-guardar-cuenta');
      if(guardarCuenta && !guardarCuenta.disabled && rsMobileOriginalAllowed(guardarCuenta)){
        guardarCuenta.click();
      }
      return;
    }

    if(rsMobileStep === 'caja'){
      rsMobileProxyClick('#btn-cambiar-cliente');
    }
  }

  function rsMobileUpdate(){
    const assistant = document.getElementById('rs-mobile-assistant');
    if(!assistant) return;

    const active = isMobileAssistantActive();
    document.body.classList.toggle('rs-mobile-assistant-enabled',active);

    if(!active){
      document.body.removeAttribute('data-rs-mobile-step');
      if(panelProductos) panelProductos.style.display = '';
      if(panelComanda) panelComanda.style.display = '';
      return;
    }

    if(!rsMobileMesasEnabled() && rsMobileStep === 'servicio') rsMobileStep = 'productos';
    document.body.setAttribute('data-rs-mobile-step',rsMobileStep);

    const serviceStep = assistant.querySelector('[data-rs-step="servicio"]');
    if(serviceStep) serviceStep.style.display = rsMobileMesasEnabled() ? '' : 'none';

    assistant.querySelectorAll('[data-rs-step]').forEach(function(btn){
      btn.classList.toggle('is-active',btn.getAttribute('data-rs-step') === rsMobileStep);
    });

    const count = rsMobileItemCount();
    const total = rsMobileTotal();
    const nombre = rsMobileCliente();
    const rtn = rsMobileRtn();

    const ctxService = document.getElementById('rs-mobile-context-service');
    const ctxClient = document.getElementById('rs-mobile-context-client');
    const countEl = document.getElementById('rs-mobile-count');
    const totalEl = document.getElementById('rs-mobile-total');
    const cajaName = document.getElementById('rs-mobile-caja-client-name');
    const cajaRtn = document.getElementById('rs-mobile-caja-client-rtn');

    if(ctxService) ctxService.textContent = rsMobileMesa();
    if(ctxClient) ctxClient.textContent = nombre;
    if(countEl) countEl.textContent = count + (count === 1 ? ' producto' : ' productos');
    if(totalEl) totalEl.textContent = 'L ' + total.toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2});
    if(cajaName) cajaName.textContent = nombre;
    if(cajaRtn){
      cajaRtn.textContent = rtn ? ('RTN ' + rtn.replace(/^RTN\s*/i,'')) : '';
      cajaRtn.style.display = rtn ? '' : 'none';
    }

    const gestionarProxy = document.getElementById('rs-mobile-manage');
    const gestionReal = document.getElementById('gestion-fija');
    if(gestionarProxy){
      gestionarProxy.style.display = (gestionReal && String(gestionReal.style.display || '') !== 'none') ? '' : 'none';
    }

    const recurrentProxy = assistant.querySelector('[data-rs-proxy="#btn-factura-recurrente"]');
    const recurrentReal = document.getElementById('btn-factura-recurrente');
    if(recurrentProxy) recurrentProxy.style.display = rsMobileOriginalAllowed(recurrentReal) ? '' : 'none';

    const back = document.getElementById('rs-mobile-back');
    const next = document.getElementById('rs-mobile-next');
    const secondary = document.getElementById('rs-mobile-secondary');
    if(!back || !next || !secondary) return;

    back.style.display = (rsMobileStep === 'servicio' || (rsMobileStep === 'productos' && !rsMobileMesasEnabled())) ? 'none' : '';
    secondary.style.display = 'none';
    secondary.innerHTML = '';

    if(rsMobileStep === 'servicio'){
      next.innerHTML = '<span>Elegir productos</span><i class="fas fa-arrow-right"></i>';
    }else if(rsMobileStep === 'productos'){
      next.innerHTML = '<span>Ver pedido' + (count ? ' ('+count+')' : '') + '</span><i class="fas fa-arrow-right"></i>';
    }else if(rsMobileStep === 'pedido'){
      const guardarCuenta = document.getElementById('btn-guardar-cuenta');
      if(guardarCuenta && count > 0 && rsMobileOriginalAllowed(guardarCuenta)){
        secondary.style.display = '';
        secondary.innerHTML = '<i class="fas fa-bookmark"></i><span>Guardar</span>';
      }

      if(rsMobileServicio() === 'mesa' && usaComandasOperacion()){
        const tieneFactura = !!facturaIdActual();
        next.innerHTML = tieneFactura
          ? '<i class="fas fa-sync-alt"></i><span>Actualizar cocina</span>'
          : '<i class="fas fa-fire"></i><span>Enviar a cocina</span>';
      }else{
        next.innerHTML = '<span>Cliente / Caja</span><i class="fas fa-arrow-right"></i>';
      }
    }else{
      secondary.style.display = '';
      secondary.innerHTML = '<i class="fas fa-user-edit"></i><span>Cliente</span>';
      next.innerHTML = '<i class="fas fa-cash-register"></i><span>Cobrar</span>';
    }
  }

  function initMobileAssistant(){
    if(rsMobileMedia) {
      rsMobileUpdate();
      return;
    }

    rsMobileMedia = window.matchMedia(RS_MOBILE_QUERY);
    rsMobileCreate();

    const apply = function(){
      if(rsMobileMedia.matches){
        document.body.classList.add('rs-mobile-assistant-enabled');
        if(!rsMobileMesasEnabled()) rsMobileStep = 'productos';
        else if (!facturaActual && !mesaSeleccionada && (!Array.isArray(comandaItems) || !comandaItems.length)) rsMobileStep = 'servicio';
        rsMobileSetStep(rsMobileStep);
        window.requestAnimationFrame(function(){
          document.body.classList.remove('rs-mobile-booting');
          document.body.classList.add('rs-mobile-ready');
        });
      }else{
        document.body.classList.remove('rs-mobile-booting','rs-mobile-ready');
        document.body.classList.remove('rs-mobile-assistant-enabled');
        document.body.removeAttribute('data-rs-mobile-step');
        if(panelProductos) panelProductos.style.display = '';
        if(panelComanda) panelComanda.style.display = '';
      }
      rsMobileUpdate();
    };

    if(typeof rsMobileMedia.addEventListener === 'function'){
      rsMobileMedia.addEventListener('change',apply);
    }else if(typeof rsMobileMedia.addListener === 'function'){
      rsMobileMedia.addListener(apply);
    }

    window.addEventListener('orientationchange',function(){
      window.setTimeout(apply,120);
    });

    apply();
  }



  // ===========================================================
  //  CONTROL ÚNICO DEL PRIMER RENDER
  //  Escritorio: libera al terminar la carga base.
  //  Móvil: libera cuando carga base + asistente responsive están listos.
  // ===========================================================
  let rsBaseInitReady = false;
  let rsMobileInitReady = false;
  let rsBootFinished = false;

  function rsEsTelefono(){
    try{
      return !!(window.matchMedia && window.matchMedia('(max-width: 599px)').matches);
    }catch(_){
      return window.innerWidth <= 599;
    }
  }

  // ====== Inicio ======
  // El salvavidas se programa ANTES de ejecutar init().
  // Así ningún error de inicialización puede dejar el POS oculto.
  const rsBootFailsafeTimer = window.setTimeout(function(){
    finalizarCargaInicialRestaurante();
  }, 6500);

  Promise.resolve()
    .then(function(){ return init(); })
    .catch(function(error){
      console.error('[Restaurante] Error durante la carga inicial:', error);
      finalizarCargaInicialRestaurante();
      if(typeof showNotify==='function'){
        showNotify('error','Punto de venta','Una parte del módulo no respondió durante la carga. La pantalla fue liberada para evitar que quede bloqueada.');
      }
    });

  function finalizarCargaInicialRestaurante(){
    if (rsBootFinished) return;
    rsBootFinished = true;

    try { window.clearTimeout(rsBootFailsafeTimer); } catch(_){}
    try {
      if (window.__RS_BOOT_EMERGENCY) {
        window.clearTimeout(window.__RS_BOOT_EMERGENCY);
        window.__RS_BOOT_EMERGENCY = null;
      }
    } catch(_){}

    const container = document.querySelector('.restaurante-container');
    if (container) container.classList.remove('rs-boot-pending');

    document.body.classList.remove('rs-booting','rs-mobile-booting');
    document.body.classList.add('rs-ready');

    const boot = document.getElementById('rs-boot-screen');
    if (boot){
      boot.setAttribute('aria-hidden','true');
      boot.classList.add('rs-boot-done');
    }
  }

  function intentarFinalizarCargaInicial(){
    if (!rsBaseInitReady) return;

    // En teléfono esperamos también a que el asistente esté construido.
    if (rsEsTelefono() && !rsMobileInitReady) return;

    window.requestAnimationFrame(finalizarCargaInicialRestaurante);
  }

  function limpiarPedidoLocalNoPersistido({limpiarCliente=false, limpiarObservaciones=false}={}){
    // Este helper SOLO limpia memoria/UI del navegador.
    // No guarda, no cancela y no modifica una cuenta persistida en servidor.
    comandaItems = [];

    if(limpiarCliente){
      clienteSeleccionado = { id: 1, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
      try { pintarClienteInfoHeader(); } catch(_){}
      try { setClienteInfoUI({ nombre:'Consumidor final', rtn:'' }); } catch(_){}
    }

    if(limpiarObservaciones && observacionesTextarea){
      observacionesTextarea.value = '';
    }

    try { actualizarComandaUI(); } catch(_){}
    try { updateProductBadges(); } catch(_){}
    try { updateAccionPrincipalUI(); } catch(_){}
    try { if(isMobileAssistantActive()) rsMobileUpdate(); } catch(_){}
  }

  function resetVentaNuevaLocal(){
    facturaActual = null;
    mesaSeleccionada = null;
    comandaItems = [];
    clienteSeleccionado = { id: 1, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
    servicioActual = 'llevar';
    REST_TIPO_FACTURA = 'contado';

    try { setServicioTipo('llevar'); } catch(_){}
    try { setTipoFacturaRestaurante('contado',{silencioso:true}); } catch(_){}
    try { setMesaSeleccionadaUI(null); } catch(_){}
    try { setClienteInfoUI({ nombre:'Consumidor final', rtn:'' }); } catch(_){}
    try { pintarClienteInfoHeader(); } catch(_){}
    try {
      if (observacionesTextarea) observacionesTextarea.value = '';
    } catch(_){}
    try { actualizarComandaUI(); } catch(_){}
    try { updateProductBadges(); } catch(_){}
    try {
      if (facturaTitle) {
        facturaTitle.innerHTML = usaComandasOperacion()
          ? '<i class="fas fa-receipt"></i> Nueva Comanda'
          : '<i class="fas fa-cash-register"></i> Nueva venta';
      }
    } catch(_){}
    try {
      if (typeof rsMobileStep !== 'undefined') rsMobileStep = 'servicio';
      if (isMobileAssistantActive()) rsMobileUpdate();
    } catch(_){}
  }

  async function init() {
    resetVentaNuevaLocal();
    setupEventListeners();
    bloquearCierrePorFondoYEsc();
    initProductoImageUpload();
    initSelect2All();
    initHotkeys();
    initSelectsPromos();

    // Estado inicial de cabecera
    setMesaSeleccionadaUI(null);
    setClienteInfoUI({ nombre:'Consumidor final', rtn:'' });

    try {
      await Promise.allSettled([
        cargarISV().then(actualizarEtiquetasISVCabecera),
        cargarMesas(),
        cargarCategorias(),
        cargarProductos(),
        cargarClientes(),
        Promise.resolve(getCajero())
      ]);

      // El estado real de caja y el contador SAR forman parte
      // de la carga inicial. La UI no se revela antes de esto.
      await verificarAperturaCaja();
    } finally {
      // La carga base ya terminó. Escritorio puede mostrarse;
      // teléfono espera también al asistente responsive.
      rsBaseInitReady = true;
      intentarFinalizarCargaInicial();
    }
  }

  window.addEventListener('pageshow', function(event){
    if (!event || !event.persisted) return;

    // El navegador restauró una página anterior desde memoria.
    // Nunca reutilizamos la venta anterior de forma automática.
    resetVentaNuevaLocal();

    Promise.allSettled([
      cargarMesas(),
      cargarProductos(),
      cargarCategorias(),
      cargarClientes(),
      verificarAperturaCaja()
    ]).then(function(){
      try { if (isMobileAssistantActive()) rsMobileSetStep('servicio'); } catch(_){}
    });
  });

  // Refresco periódico estable: no repinta si el estado no cambió y
  // no consulta mientras la pestaña está oculta.
  setInterval(function () {
    if (document.visibilityState === 'visible') verificarAperturaCaja();
  }, 15000);

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

  function usaMesasOperacion(){
    return !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_mesas)===0);
  }

  function usaComandasOperacion(){
    return !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_comandas)===0);
  }


  let REST_TIPO_FACTURA = 'contado';

  function permiteCreditoOperacion(){
    return Number(window.REST_CONFIG && window.REST_CONFIG.permitir_facturas_credito) === 1;
  }

  function tipoFacturaRestauranteActual(){
    return permiteCreditoOperacion() && REST_TIPO_FACTURA === 'credito' ? 2 : 1;
  }

  function sincronizarTipoFacturaRestauranteUI(){
    const habilitado = permiteCreditoOperacion();
    const wrap = document.getElementById('tipo-factura-restaurante-wrap');
    if(wrap) wrap.style.display = habilitado ? '' : 'none';

    if(!habilitado) REST_TIPO_FACTURA = 'contado';

    document.querySelectorAll('[data-tipo-factura]').forEach(function(btn){
      const activo = String(btn.dataset.tipoFactura || '') === REST_TIPO_FACTURA;
      btn.classList.toggle('is-active', activo);
      btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });

    const mobileContado = document.getElementById('rs-mobile-tipo-contado');
    const mobileCredito = document.getElementById('rs-mobile-tipo-credito');
    if(mobileContado){
      mobileContado.classList.toggle('is-active', REST_TIPO_FACTURA === 'contado');
      mobileContado.setAttribute('aria-pressed', REST_TIPO_FACTURA === 'contado' ? 'true' : 'false');
    }
    if(mobileCredito){
      mobileCredito.classList.toggle('is-active', REST_TIPO_FACTURA === 'credito');
      mobileCredito.setAttribute('aria-pressed', REST_TIPO_FACTURA === 'credito' ? 'true' : 'false');
    }

    const mobileWrap = document.getElementById('rs-mobile-invoice-type');
    if(mobileWrap) mobileWrap.style.display = habilitado ? '' : 'none';
  }

  function setTipoFacturaRestaurante(tipo, opciones={}){
    const solicitado = String(tipo || 'contado').toLowerCase() === 'credito' ? 'credito' : 'contado';

    if(solicitado === 'credito' && !permiteCreditoOperacion()){
      REST_TIPO_FACTURA = 'contado';
      sincronizarTipoFacturaRestauranteUI();
      if(!opciones.silencioso){
        showAlert('info','Facturación al crédito','Esta empresa trabaja únicamente con facturas de contado. Puede habilitar Crédito desde Configuración del módulo.');
      }
      return false;
    }

    REST_TIPO_FACTURA = solicitado;
    sincronizarTipoFacturaRestauranteUI();
    if(isMobileAssistantActive()) rsMobileUpdate();
    return true;
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
    const btnRecurrente = document.getElementById('btn-factura-recurrente');
    const tieneItems = Array.isArray(comandaItems) && comandaItems.length > 0;
    const tieneFactura = !!(facturaActual && (facturaActual.id || facturaActual.factura_id || facturaActual.facturas_id));
    const usarMesas = !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_mesas) === 0);
    const esMesa = usarMesas && servicioActual === 'mesa';

    el.classList.remove('btn-success','btn-warning','btn-danger');

    if (!esMesa) {
      el.innerHTML = '<i class="fas fa-cash-register"></i> Cobrar';
      el.classList.add('btn-success');
      el.title = 'Facturar y abrir el método de pago';
      if (btnCobrarMesa) btnCobrarMesa.style.display = 'none';
      if (btnGuardarCuenta) {
        btnGuardarCuenta.style.display = tieneItems ? '' : 'none';
        btnGuardarCuenta.innerHTML = '<i class="fas fa-bookmark"></i> Guardar para después';
      }
      if (btnRecurrente) btnRecurrente.style.display = '';
      if (btnCerrar) btnCerrar.style.display = tieneFactura ? '' : 'none';
    } else {
      el.innerHTML = '<i class="fas fa-save"></i> Guardar pedido';
      el.classList.add('btn-warning');
      el.title = usaComandasOperacion()
        ? 'Guardar/actualizar la cuenta y enviar únicamente lo nuevo a preparación'
        : 'Guardar/actualizar la cuenta';
      if (btnCobrarMesa) btnCobrarMesa.style.display = (mesaSeleccionada && tieneItems) ? '' : 'none';
      if (btnGuardarCuenta) btnGuardarCuenta.style.display = 'none';
      if (btnCerrar) btnCerrar.style.display = 'none';
      if (btnRecurrente) btnRecurrente.style.display = 'none';
    }

    if (btnImprimir) {
      btnImprimir.style.display = tieneItems ? '' : 'none';
      btnImprimir.disabled = !tieneItems;
    }
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
  var cajeroActualId = 0;

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
      cajeroActualId = parseInt(id, 10) || 0;
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
}
  // ===========================================================
  //  AJAX de backend
  // ===========================================================

  // 1) Consulta si la caja está abierta (1) o cerrada (2) SIN bloquear la interfaz
  function getConsultarAperturaCaja(intentos = 0) {
    return $.ajax({
      type: 'POST',
      url: BASE + 'core/getAperturaCajaUsuario.php',
      timeout: REQUEST_TIMEOUT_MS
    }).then(function (r) {
      try {
        var data = (typeof r === 'string') ? JSON.parse(r) : r;
        var estado = Number(Array.isArray(data) ? data[0] : (data && (data.estado ?? data[0])));
        return (estado === 1 || estado === 2) ? estado : null;
      } catch (e) {
        return null;
      }
    }, async function () {
      // Un fallo temporal de red jamás debe convertirse visualmente
      // en "caja cerrada". Reintentamos una vez y, si falla, conservamos
      // el último estado confirmado.
      if (intentos < 1) {
        await new Promise(function(resolve){ setTimeout(resolve, 350); });
        return getConsultarAperturaCaja(intentos + 1);
      }
      return null;
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
    if (cajaCheckEnCurso) return null;
    cajaCheckEnCurso = true;
    try {
      const estado = await getConsultarAperturaCaja();

      // null = no hubo respuesta confiable. Conservamos exactamente
      // el último estado visual; jamás mostramos "caja cerrada" por timeout.
      if (estado !== 1 && estado !== 2) {
        await getTotalFacturasDisponibles();
        return ultimoEstadoCajaConfirmado;
      }

      const nuevoAbierta = (estado === 1);
      const cambioReal = (ultimoEstadoCajaConfirmado === null || ultimoEstadoCajaConfirmado !== estado);

      cajaAbierta = nuevoAbierta;

      // Solo tocamos el DOM si el servidor confirmó un cambio real.
      // Así desaparece el efecto Aperturar/Cerrar/No disponible durante polling.
      if (cambioReal) {
        ultimoEstadoCajaConfirmado = estado;

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
      }

      await getTotalFacturasDisponibles();
      return estado;
    } catch (e) {
      // Mantener la interfaz estable ante errores transitorios.
      return ultimoEstadoCajaConfirmado;
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
    if (buscarMesaRapido) {
      buscarMesaRapido.addEventListener('input', function(){ filtroMesaRapido=this.value||''; renderizarMesas(); });
      buscarMesaRapido.addEventListener('keydown', function(e){ if(e.key==='Escape'){ this.value=''; filtroMesaRapido=''; renderizarMesas(); } });
    }
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
        // Quitar selección NO libera ni cancela una cuenta guardada.
        // Todo cambio que todavía no fue Guardado/Enviado se descarta localmente.
        // Si la mesa tenía una cuenta persistida, al volver a abrirla se reconstruye
        // exclusivamente desde el servidor.
        cargaFacturaMesaSecuencia++;
        mesaSeleccionada = null;
        facturaActual = null;
        limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
        setTipoFacturaRestaurante('contado',{silencioso:true});
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
    $(document).on('click', '#btn-apertura-caja', async function () {
      var mode = $(this).data('mode');
      if (mode === 'abrir') { formAperturaBill(); return; }
      try {
        const d = await restPost('loadCuentasAbiertas');
        const cuentas = (d && d.status && Array.isArray(d.cuentas)) ? d.cuentas : [];
        const mesasAbiertas = cuentas.filter(c => Number(c.mesa_id || 0) > 0 && Number(c.es_anterior || 0) === 0);
        if (mesasAbiertas.length) {
          showAlert('warning','Cuentas de mesa abiertas',`Hay ${mesasAbiertas.length} cuenta(s) de mesa todavía abierta(s). Revísalas, cóbralas o libera la mesa antes de cerrar caja.`);
          await cargarCuentasAbiertasUI();
          return;
        }
      } catch (_) {}
      formCierreBill();
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
        // El modal permanece abierto después de guardar/editar.
        // Solo se cierra por X, botón Cerrar/Cancelar o tecla ESC.

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
          // Mantener el editor del combo abierto después de crear/actualizar.
      // Solo X/Cerrar/Cancelar/ESC deben cerrarlo.
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
    if (isMobileAssistantActive()) {
      rsMobileSetStep(que === 'productos' ? 'productos' : 'pedido');
      return;
    }
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
  // MISMA REGLA QUE FACTURA.PHP:
  // isv_id=1 => ISV1 | isv_id=2 => ISV2.
  // No usar isv_tipo_id para decidir la tasa porque ese campo puede repetirse.
  function normalizarValorISVRestaurante(valor){
    if(valor && typeof valor === 'object'){
      if(valor.valor !== undefined) valor = valor.valor;
      else if(Array.isArray(valor) && valor.length) valor = valor[0];
    }
    const n = parseFloat(valor || 0);
    return Number.isFinite(n) ? n : 0;
  }

  async function obtenerISVPorIdRestaurante(isvId){
    const fallback = Number(isvId) === 1 ? 15 : (Number(isvId) === 2 ? 18 : 0);

    try{
      const response = await fetchWithTimeout(BASE + 'core/getISV.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body:'isv_id=' + encodeURIComponent(String(isvId))
      });

      const data = await response.json();
      let valor = 0;

      if(data && typeof data === 'object' && !Array.isArray(data) && data.valor !== undefined){
        valor = normalizarValorISVRestaurante(data.valor);
      }else if(Array.isArray(data) && data.length){
        valor = normalizarValorISVRestaurante(data[0]);
      }else{
        valor = normalizarValorISVRestaurante(data);
      }

      return valor > 0 ? valor : fallback;
    }catch(_){
      return fallback;
    }
  }

  async function cargarISV() {
    const tasas = await Promise.all([
      obtenerISVPorIdRestaurante(1),
      obtenerISVPorIdRestaurante(2)
    ]);

    isvRates[1] = Number(tasas[0] || 0);
    isvRates[2] = Number(tasas[1] || 0);

    actualizarEtiquetasISVCabecera();
    return isvRates;
  }

  function formatearTasaISVRestaurante(valor){
    const n = Number(valor || 0);
    if(!Number.isFinite(n)) return '0';
    return Number.isInteger(n) ? String(n) : n.toFixed(2).replace(/\.?0+$/,'');
  }

  function actualizarEtiquetasISVCabecera(){
    if (impuesto1Label) impuesto1Label.textContent = `Impuesto (ISV ${formatearTasaISVRestaurante(isvRates[1])}%):`;
    if (impuesto2Label) impuesto2Label.textContent = `Impuesto (ISV ${formatearTasaISVRestaurante(isvRates[2])}%):`;
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
    const todasMesas = Array.isArray(mesas) ? mesas : [];
    const termino = String(filtroMesaRapido || '').toLowerCase().trim();
    const mesasVisibles = !termino ? todasMesas : todasMesas.filter(m => {
      const texto = [m.numero,m.ubicacion,m.estado,m.reserva && m.reserva.cliente_nombre].filter(Boolean).join(' ').toLowerCase();
      return texto.includes(termino);
    });
    if (mesasCount) mesasCount.textContent = termino ? `${mesasVisibles.length}/${todasMesas.length}` : String(todasMesas.length);
    if (!todasMesas.length) {
      mesasContainer.innerHTML = `<div class="mesa-empty-state"><i class="fas fa-chair"></i><strong>Sin mesas</strong><span>Crea una con “Nueva”.</span></div>`;
      return;
    }
    if (!mesasVisibles.length) {
      mesasContainer.innerHTML = `<div class="mesa-empty-state"><i class="fas fa-search"></i><strong>Sin coincidencias</strong><span>No hay mesas que coincidan con la búsqueda.</span></div>`;
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

    mesasVisibles.forEach(mesa => {
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
    const tieneCuenta = Number(mesa && mesa.tiene_cuenta_abierta || 0) === 1;
    const texto = tieneCuenta
      ? `¿Desea liberar la Mesa ${mesa.numero}? La cuenta abierta NO se eliminará: seguirá disponible en “Cuentas abiertas” para cobrarla o continuarla después.`
      : `¿Desea liberar la Mesa ${mesa.numero}? La mesa quedará disponible inmediatamente.`;
    showConfirm('Liberar mesa', texto, async ()=>{
      try{
        setButtonBusy(boton, true, '');
        const r = await fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=liberarMesa&mesa_id=${encodeURIComponent(mesaId)}`});
        const d = await r.json();
        if (!d || !(d.ok || d.status)) throw new Error((d && (d.message || d.msg)) || 'No se pudo liberar la mesa');
        const actualId = Number((mesaSeleccionada && (mesaSeleccionada.id || mesaSeleccionada.mesa_id)) || mesaSeleccionada || 0);
        if (actualId === mesaId) {
          cargaFacturaMesaSecuencia++;
          mesaSeleccionada=null;
          facturaActual=null;
          limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
          setTipoFacturaRestaurante('contado',{silencioso:true});
          setServicioTipo('llevar');
          setMesaSeleccionadaUI(null);
        }
        showAlert('success','Mesa disponible',d.cuenta_conservada?`Mesa liberada. La cuenta #${d.factura_id} continúa abierta en “Cuentas abiertas”.`:'La mesa fue liberada correctamente.');
        await cargarMesas();
      }catch(e){ showAlert('error','No se puede liberar',e.message || 'No se pudo liberar la mesa'); }
      finally { setButtonBusy(boton, false); }
    }, {danger:false});
  }

  function highlightMesaSeleccionada(){
    document.querySelectorAll('.mesa-item').forEach(el => el.classList.remove('seleccionada'));
    if (!mesaSeleccionada || !mesaSeleccionada.id) return;
    const el = document.querySelector(`.mesa-item[data-mesa-id="${mesaSeleccionada.id}"]`);
    if (el) el.classList.add('seleccionada');
  }  

  function seleccionarMesa(mesa){
    const mesaId = Number(mesa && (mesa.id || mesa.mesa_id) || 0);
    if (!mesaId) return;

    // Regla de aislamiento del pedido:
    // cambiar/seleccionar una mesa NUNCA arrastra productos locales no guardados.
    // Una cuenta existente se reconstruye exclusivamente con lo persistido en BD.
    cargaFacturaMesaSecuencia++;
    facturaActual = null;
    limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
    setTipoFacturaRestaurante('contado',{silencioso:true});

    // En móvil NO recuperar automáticamente una cuenta existente.
    // El usuario decide expresamente si quiere continuarla.
    const tieneCuentaAbierta =
      Number(mesa && mesa.tiene_cuenta_abierta || 0) === 1 ||
      String(mesa && mesa.estado || '').toLowerCase() === 'ocupada';

    if (isMobileAssistantActive() && tieneCuentaAbierta) {
      if (typeof swal === 'undefined') {
        showAlert('warning','Cuenta abierta','Esta mesa tiene una cuenta abierta. Puede recuperarla desde “Cuentas”.');
        return;
      }

      swal({
        title: 'Cuenta abierta en esta mesa',
        text: `La Mesa ${mesa.numero || ''} ya tiene una cuenta activa. ¿Desea recuperarla y continuar ese pedido?`,
        icon: 'info',
        buttons: {
          cancel: {
            text: 'No, elegir otra',
            value: null,
            visible: true,
            closeModal: true
          },
          confirm: {
            text: 'Abrir cuenta',
            value: true,
            visible: true,
            closeModal: true
          }
        },
        dangerMode: false,
        closeOnEsc: true,
        closeOnClickOutside: false
      }).then((abrir)=>{
        if (!abrir) {
          mesaSeleccionada = null;
          setMesaSeleccionadaUI(null);
          highlightMesaSeleccionada();
          rsMobileUpdate();
          return;
        }

        setServicioTipo('mesa');
        mesaSeleccionada = {
          id: mesaId,
          numero: mesa.numero,
          capacidad: mesa.capacidad,
          ubicacion: mesa.ubicacion,
          estado: mesa.estado
        };

        setMesaSeleccionadaUI(mesaSeleccionada.numero);
        if (btnImprimir) btnImprimir.disabled = true;
        highlightMesaSeleccionada();

        // Se carga únicamente cuando el usuario lo confirma y SOLO desde servidor.
        cargarFacturaMesa(mesaId);
      });
      return;
    }

    // Mesa disponible o escritorio/tablet:
    // jamás transportar un carrito local de una mesa/venta anterior.
    setServicioTipo('mesa');

    mesaSeleccionada = {
      id: mesaId,
      numero: mesa.numero,
      capacidad: mesa.capacidad,
      ubicacion: mesa.ubicacion,
      estado: mesa.estado
    };

    setMesaSeleccionadaUI(mesaSeleccionada.numero);
    if (btnImprimir) btnImprimir.disabled = true;
    highlightMesaSeleccionada();

    // REGLA DEFINITIVA:
    // Una mesa que el catálogo acaba de declarar DISPONIBLE y sin cuenta abierta
    // SIEMPRE inicia vacía. No hacemos una segunda consulta que pueda rescatar
    // por accidente un contexto viejo de días anteriores.
    const mesaTieneCuentaHoy =
      Number(mesa && mesa.tiene_cuenta_abierta || 0) === 1 ||
      String(mesa && mesa.estado || '').toLowerCase() === 'ocupada';

    if (mesaTieneCuentaHoy) {
      cargarFacturaMesa(mesaSeleccionada.id);
    } else {
      facturaActual = null;
      limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
      setTipoFacturaRestaurante('contado',{silencioso:true});
      updateProductBadges();
      updateAccionPrincipalUI();
      if(isMobileAssistantActive()){
        rsMobileStep = 'servicio';
        rsMobileUpdate();
      }
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
            // Mantener el modal abierto después de guardar/editar la mesa.
            // El cierre queda exclusivamente a decisión del usuario.
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
  
      btnAgregar.addEventListener('click', (e) => { e.stopPropagation(); agregarProductoConValidacionCombo(datosProducto); });
      productoElement.addEventListener('click', () => agregarProductoConValidacionCombo(datosProducto));
  
      productosContainer.appendChild(productoElement);
    });
  }  

  function comboPorProductoId(productoId){
    return (combos || []).find(c =>
      Number(c.productos_id || c.producto_id || 0) === Number(productoId || 0)
      && Number(c.activo == null ? 1 : c.activo) === 1
    ) || null;
  }

  async function agregarProductoConValidacionCombo(datosProducto){
    const combo=comboPorProductoId(datosProducto && datosProducto.id);
    if(!combo){ agregarProductoComanda(datosProducto); return true; }

    const actual=(comandaItems||[]).find(it =>
      Number(it.productos_id || it.producto_id || (it.producto&&it.producto.id) || 0) === Number(datosProducto.id)
    );
    const cantidad=Math.max(1,Number(actual&&actual.cantidad||0)+1);

    try{
      const d=await $.ajax({
        type:'POST',
        url:BASE+'core/facturasRestaurante/facturasRestauranteAjax.php',
        dataType:'json',
        data:{action:'calcularDisponibilidadCombo',combo_id:Number(combo.combo_id||combo.id||0),cantidad}
      });
      if(!d||!d.status) throw new Error((d&&(d.message||d.msg))||'No se pudo validar el inventario del combo.');
      if(String(d.alcanza_para||'').toLowerCase()!=='si'){
        showAlert('warning','Combo sin inventario suficiente',`Disponibles: ${Number(d.disponibles||0)}. Revise los componentes del combo.`);
        return false;
      }
      agregarProductoComanda(datosProducto);
      return true;
    }catch(e){
      showAlert('error','Inventario del combo',e.message||'No se pudo validar la disponibilidad.');
      return false;
    }
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
    agregarProductoConValidacionCombo(datosProducto);
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
  // La mesa se reconstruye SIEMPRE desde el servidor.
  // Nunca se fusionan productos locales no guardados con otra mesa/cuenta.
  function cargarFacturaMesa(mesaId){
    const mesaSolicitada = Number(mesaId || 0);
    if(!mesaSolicitada) return Promise.resolve(false);

    // Antes de consultar servidor, el estado visual queda vacío.
    // Así el asistente móvil nunca puede mostrar el carrito anterior mientras
    // espera una respuesta de red.
    facturaActual = null;
    limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
    updateProductBadges();
    updateAccionPrincipalUI();
    if(isMobileAssistantActive()) rsMobileUpdate();

    // SeleccionarMesa incrementa primero. Tomamos el valor actual como identidad
    // de esta carga. Una nueva selección incrementará la secuencia y esta respuesta
    // quedará automáticamente obsoleta.
    const secuenciaCarga = cargaFacturaMesaSecuencia;

    return fetchWithTimeout(BASE + 'core/facturasRestaurante/facturasRestauranteAjax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=loadFacturaMesa&mesa_id=${encodeURIComponent(mesaSolicitada)}`
    })
    .then(r => r.json())
    .then(data => {
      const mesaActualId = Number(
        mesaSeleccionada &&
        (mesaSeleccionada.id || mesaSeleccionada.mesa_id || mesaSeleccionada) || 0
      );

      // Si el usuario cambió de mesa mientras esperaba, ignorar por completo
      // esta respuesta para que una petición lenta no contamine la venta actual.
      if(secuenciaCarga !== cargaFacturaMesaSecuencia || mesaActualId !== mesaSolicitada){
        return false;
      }

      if (data && data.status) {
        // Cuenta YA GUARDADA: reconstruir exclusivamente desde BD.
        setServicioTipo('mesa');

        facturaActual = data.factura || null;
        mesaSeleccionada = data.mesa || mesaSeleccionada;

        if (facturaActual && (facturaActual.cliente_id || facturaActual.clientes_id)) {
          clienteSeleccionado = {
            id: Number(facturaActual.cliente_id || facturaActual.clientes_id),
            nombre: facturaActual.cliente_nombre || 'Cliente',
            identificacion: facturaActual.cliente_identificacion || ''
          };
          pintarClienteInfoHeader();
        } else {
          clienteSeleccionado = { id: 1, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
          pintarClienteInfoHeader();
        }

        setTipoFacturaRestaurante(
          Number(facturaActual && facturaActual.tipo_factura) === 2 ? 'credito' : 'contado',
          {silencioso:true}
        );

        // CRÍTICO: reemplazar, no fusionar.
        // Aquí solo pueden existir productos realmente persistidos en facturas_detalles.
        comandaItems = (Array.isArray(data.items) ? data.items : []).map(item => ({
          producto: {
            id: item.productos_id,
            nombre: item.nombre_producto,
            precio: parseFloat(item.precio),
            descripcion: item.descripcion_producto || '',
            isv1: Number(item.isv1 || 0) === 1,
            isv2: Number(item.isv2 || 0) === 1,
            para_cocina: 0,
            medida: item.medida || 'Und'
          },
          cantidad: parseFloat(item.cantidad),
          precio: parseFloat(item.precio),
          total: parseFloat(item.precio) * parseFloat(item.cantidad),
          descuento: Number(item.descuento || 0),
          isv_valor: Number(item.isv_valor || 0)
        }));

        const nomMesa = mesaSeleccionada
          ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || mesaSeleccionada.nombre || mesaSeleccionada.nombre_mesa || null)
          : null;
        setMesaSeleccionadaUI(nomMesa);

        const numFactura = facturaActual
          ? (facturaActual.number || facturaActual.numero || facturaActual.factura_numero || facturaActual.id || facturaActual.factura_id)
          : null;

        if (facturaTitle) {
          facturaTitle.innerHTML = `<i class="fas fa-receipt"></i> ${numFactura ? 'Cuenta #'+numFactura : 'Cuenta abierta'}`;
        }
        if (observacionesTextarea) {
          observacionesTextarea.value = (facturaActual && (facturaActual.notas || facturaActual.observaciones || '')) || '';
        }
        if (btnImprimir) btnImprimir.disabled = false;

        actualizarComandaUI();
        updateProductBadges();
        highlightMesaSeleccionada();
        updateAccionPrincipalUI();
        if(isMobileAssistantActive()) rsMobileUpdate();
        return true;
      }

      // Mesa SIN cuenta guardada:
      // debe quedar 100% limpia aunque una venta anterior haya tenido productos
      // en memoria. Nada no persistido puede sobrevivir a esta selección.
      setServicioTipo('mesa');
      facturaActual = null;
      limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
      setTipoFacturaRestaurante('contado',{silencioso:true});

      if (facturaTitle) {
        facturaTitle.innerHTML = usaComandasOperacion()
          ? '<i class="fas fa-receipt"></i> Nueva Comanda'
          : '<i class="fas fa-cash-register"></i> Nueva venta';
      }
      if (btnImprimir) btnImprimir.disabled = true;

      const nomMesa = mesaSeleccionada
        ? (mesaSeleccionada.numero || mesaSeleccionada.Numero || null)
        : null;
      setMesaSeleccionadaUI(nomMesa);
      highlightMesaSeleccionada();
      updateAccionPrincipalUI();
      if(isMobileAssistantActive()) rsMobileUpdate();
      return true;
    })
    .catch((error) => {
      const mesaActualId = Number(
        mesaSeleccionada &&
        (mesaSeleccionada.id || mesaSeleccionada.mesa_id || mesaSeleccionada) || 0
      );

      // No mostrar error de una petición que ya fue reemplazada por otra selección.
      if(secuenciaCarga !== cargaFacturaMesaSecuencia || mesaActualId !== mesaSolicitada){
        return false;
      }

      // Ante fallo de red NO reutilizar carrito viejo.
      facturaActual = null;
      limpiarPedidoLocalNoPersistido({limpiarCliente:true, limpiarObservaciones:true});
      setTipoFacturaRestaurante('contado',{silencioso:true});
      showAlert('error', 'Error', 'No se pudo comprobar la cuenta de esta mesa. El pedido local se mantuvo vacío para evitar mezclar productos.');
      return false;
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
    if (window.innerWidth <= 768 && !isMobileAssistantActive()) {
      mostrarVista('comanda');
    } else if (isMobileAssistantActive()) {
      rsMobileUpdate();
    }
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
    const r1 = (Number(isvRates[1]) || 0) / 100.0;
    const r2 = (Number(isvRates[2]) || 0) / 100.0;

    let subtotal = 0;
    let descuentos = 0;
    let imp1 = 0;
    let imp2 = 0;

    (comandaItems || []).forEach(it => {
      const precio = Number(it.precio || (it.producto && it.producto.precio) || 0);
      const cantidad = Number(it.cantidad || 1);
      const descuento = Math.max(0, Number(it.descuento || 0));
      const baseBruta = precio * cantidad;
      const baseNeta = Math.max(0, baseBruta - descuento);

      subtotal += baseBruta;
      descuentos += descuento;

      // Cada producto grava SOLO el impuesto marcado en su ficha.
      // isv1 usa isv_id=1; isv2 usa isv_id=2.
      if (it.producto && it.producto.isv1 === true) imp1 += baseNeta * r1;
      if (it.producto && it.producto.isv2 === true) imp2 += baseNeta * r2;
    });

    const total = (subtotal - descuentos) + imp1 + imp2;
    const fmt = (n) => new Intl.NumberFormat('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(Number(n || 0));

    if (subtotalElement) subtotalElement.textContent = `L ${fmt(subtotal)}`;
    if (impuesto1Element) impuesto1Element.textContent = `L ${fmt(imp1)}`;
    if (impuesto2Element) impuesto2Element.textContent = `L ${fmt(imp2)}`;
    if (totalElement) totalElement.textContent = `L ${fmt(total)}`;

    actualizarEtiquetasISVCabecera();
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
      // La reserva se guarda sin cerrar el modal automáticamente.
      // Solo X/Cerrar/Cancelar/ESC deben cerrarlo.
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
      tipo_factura:tipoFacturaRestauranteActual(),
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
  let confirmandoCobroMesa = false;

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
    const tipoFactura = tipoFacturaRestauranteActual();
    params.set('facturas_activo', tipoFactura === 2 ? '0' : '1'); // UI backend: 1=Contado, 0=Crédito
    params.set('facturas_proforma', '0');                         // factura fiscal normal
    params.set('tipo_factura', String(tipoFactura));              // compatibilidad: 1=Contado, 2=Crédito
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
      /\bprintBill\s*\(\s*["']?(\d+)["']?/i,
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

    cargaFacturaMesaSecuencia++;
    facturaActual=null;
    limpiarPedidoLocalNoPersistido({limpiarCliente:false, limpiarObservaciones:false});
    updateProductBadges();
    if(observacionesTextarea) observacionesTextarea.value='';
    clienteSeleccionado={id:1,nombre:'CONSUMIDOR FINAL',identificacion:''};
    pintarClienteInfoHeader();
    mesaSeleccionada=null;
    setServicioTipo('llevar');
    setMesaSeleccionadaUI(null);
    setTipoFacturaRestaurante('contado',{silencioso:true});
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

  async function facturarConFlujoNormal(servicio, opciones){
    opciones = opciones || {};
    if(facturandoRestaurante) return;
    if(!Array.isArray(comandaItems)||!comandaItems.length){showAlert('warning','Sin productos','Agregue productos antes de cobrar.');return;}
    servicio = servicio==='mesa' ? 'mesa' : 'llevar';
    if(servicio==='mesa' && !mesaIdActual()){showAlert('warning','Mesa requerida','Seleccione una mesa antes de cobrar.');return;}
    if(Number(window.REST_COLABORADOR_ID||0)<=0){showAlert('error','Cajero no identificado','No se pudo identificar el colaborador de la sesión.');return;}

    // El SweetAlert de confirmación del pago se muestra JUSTO ANTES
    // de abrir el modal oficial de pagos. No se confirma aquí para evitar
    // que el usuario confirme demasiado pronto.
    confirmandoCobroMesa=false;
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
      const esCredito = tipoFacturaRestauranteActual() === 2;

      if(esCredito){
        // El controlador oficial ya registró la CxC. No existe pago inmediato.
        if(typeof printBill === 'function'){
          try{ printBill(realId); }catch(_){}
        }else{
          showAlert('info','Factura al crédito','La factura fue registrada correctamente. Puede imprimirla desde el historial de facturas.');
        }

        await finalizarUITrasPagoRestaurante(contextoPagoRestaurante);
        showAlert('success','Factura al crédito','La factura al crédito fue registrada correctamente.');
      }else{
        // PUNTO EXACTO DE CONFIRMACIÓN:
        // la factura ya está preparada, pero el modal de pagos TODAVÍA NO se abre.
        // Solo después de aceptar el SweetAlert se llama a pago().
        const confirmarPago = await confirmarRegistroPagoRestaurante(contextoPagoRestaurante);

        if(!confirmarPago){
          // No abrir el modal ni registrar el pago si el usuario cancela.
          contextoPagoRestaurante = null;
          showNotify && typeof showNotify === 'function'
            ? showNotify('info','Pago cancelado','No se abrió el método de pago.')
            : null;
          return;
        }

        prepararModalPagoContextual(contextoPagoRestaurante);
        instalarHookPagoRestaurante();

        if(typeof pago !== 'function'){
          throw new Error('No está disponible el flujo oficial de pagos.');
        }

        pago(realId, 1, 'factura');
      }
    }catch(e){
      contextoPagoRestaurante=null;
      showAlert('error','No se pudo facturar',e && e.message ? e.message : 'Ocurrió un error al registrar la factura.');
    }finally{
      facturandoRestaurante=false;
      setButtonBusy(cobrarBtn,false);
      updateAccionPrincipalUI();
    }
  }

  $(document).off('click.restCobrarMesa','#btn-cobrar-mesa').on('click.restCobrarMesa','#btn-cobrar-mesa',function(e){
    if(e){ e.preventDefault(); e.stopImmediatePropagation(); }
    if(facturandoRestaurante || confirmandoCobroMesa) return;
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

    showConfirm(
      'Guardar pedido',
      usaComandasOperacion()
        ? (facturaIdActual()
            ? '¿Desea guardar los cambios? Si agregó productos nuevos, solo esos productos se enviarán a preparación.'
            : '¿Desea guardar el pedido y enviar los productos a preparación?')
        : (facturaIdActual() ? '¿Desea guardar los cambios de esta cuenta?' : '¿Desea guardar esta cuenta?'),
      ()=>{
        if(guardandoFactura) return;
        guardandoFactura=true;
        setButtonBusy(btnGuardar,true,'Guardando…');
        guardarCuentaMesa({silencioso:false,imprimirAlEnviar:true,enviarComanda:true})
          .catch(e=>showAlert('error','Error',e.message||'No se pudo guardar el pedido'))
          .finally(()=>{guardandoFactura=false;setButtonBusy(btnGuardar,false);updateAccionPrincipalUI();if(isMobileAssistantActive())rsMobileUpdate();});
      });
    return false;
  }

  function cerrarFactura() {
    if (!facturaActual || !(facturaActual.id || facturaActual.factura_id)) {
      showAlert('warning','Advertencia','No hay factura abierta'); 
      return; 
    }
    if(getServicioTipo()==='mesa'){
      showAlert('info','Cuenta de mesa','Esta cuenta debe finalizarse mediante “Cobrar mesa”.');
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

  function limpiarComanda() {
    limpiarPedidoLocalNoPersistido();
  }

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

  // ===========================================================
  //  CENTRO DE AYUDA DINÁMICO
  //  Solo documentación/UI. No modifica lógica de negocio.
  // ===========================================================
  const RS_HELP_TOPICS = [
    {
      id:'inicio', category:'Primeros pasos', icon:'fa-play-circle',
      title:'Cómo iniciar una venta',
      keywords:['inicio','nueva venta','empezar','venta','pedido','flujo'],
      short:'El flujo básico para comenzar una venta desde cero.',
      body:()=>`
        <ol class="rs-help-steps">
          <li><b>Revise la caja:</b> si está cerrada, primero debe aperturarla.</li>
          ${usaMesasOperacion()
            ? '<li><b>Elija el servicio:</b> Para llevar o En mesa. Si es En mesa, seleccione una mesa disponible.</li>'
            : '<li><b>Venta directa:</b> agregue los productos; este negocio no requiere selección de mesa.</li>'}
          <li><b>Seleccione el cliente:</b> puede usar Consumidor final o cambiarlo antes de cobrar.</li>
          <li><b>Agregue productos:</b> use tarjetas, búsqueda o escáner de código.</li>
          ${usaComandasOperacion()
            ? '<li><b>Preparación:</b> los artículos asignados a Cocina/Barra pueden enviarse a sus pantallas o ticket de orden.</li>'
            : ''}
          <li><b>Finalice:</b> cobre de inmediato o guarde la cuenta para continuarla más tarde.</li>
        </ol>`
    },
    {
      id:'cuenta-abierta', category:'Ventas y cuentas', icon:'fa-folder-open',
      title:'¿Qué es una cuenta abierta?',
      keywords:['cuenta','cuenta abierta','abierta','guardar cuenta','borrador','pendiente','recuperar'],
      short:'Una venta guardada que todavía no ha sido cobrada ni finalizada.',
      body:()=>`
        <p>Una <b>cuenta abierta</b> conserva cliente, productos, cantidades, mesa cuando aplica y el avance de preparación, pero todavía no representa una venta final pagada.</p>
        <div class="rs-help-callout"><i class="fas fa-lightbulb"></i><span>Úsela cuando el cliente seguirá pidiendo productos y desea cobrar todo al final.</span></div>
        <ul>
          <li><b>Guardar cuenta:</b> conserva la venta actual sin cobrarla.</li>
          <li><b>Cuentas abiertas:</b> muestra únicamente cuentas que siguen activas.</li>
          <li><b>Recuperar:</b> abre la misma factura; no crea una factura duplicada.</li>
          <li><b>Cerrar cuenta:</b> permite cerrar una cuenta que ya no se utilizará, según las validaciones del sistema.</li>
          <li>Las cuentas pagadas, canceladas o finalizadas <b>no deben volver a cargarse</b> como abiertas.</li>
        </ul>`
    },
    {
      id:'factura-vs-cuenta', category:'Ventas y cuentas', icon:'fa-file-invoice',
      title:'Cuenta abierta, comanda y factura: diferencias',
      keywords:['factura','comanda','cuenta abierta','diferencia','orden'],
      short:'Cada concepto cumple una función diferente.',
      body:()=>`
        <div class="rs-help-compare">
          <div><b>Cuenta abierta</b><span>Venta guardada que todavía puede modificarse y cobrarse después.</span></div>
          <div><b>Comanda / orden</b><span>Información de preparación enviada a Cocina/Barra. No sustituye la factura fiscal.</span></div>
          <div><b>Factura</b><span>Documento final generado por el proceso normal de facturación al completar el cobro.</span></div>
        </div>`
    },
    {
      id:'mesas', category:'Servicio', icon:'fa-chair',
      title:'Mesas',
      keywords:['mesa','mesas','ocupada','disponible','liberar','comer aqui','en mesa'],
      short:'Cómo seleccionar, ocupar, recuperar y liberar mesas.',
      visible:()=>usaMesasOperacion(),
      body:()=>`
        <ul>
          <li>Una mesa <b>Disponible</b> puede iniciar una venta nueva.</li>
          <li>Una mesa <b>Ocupada</b> normalmente está vinculada a una cuenta activa del día.</li>
          <li>En móvil, si una mesa tiene una cuenta existente, el sistema le pide confirmación antes de recuperarla.</li>
          <li>Las cuentas anteriores pueden conservarse para consulta sin mantener ocupada la mesa indefinidamente.</li>
          <li>Al cobrar/cerrar correctamente la cuenta, la mesa debe quedar disponible según el flujo configurado.</li>
        </ul>`
    },
    {
      id:'para-llevar', category:'Servicio', icon:'fa-bag-shopping',
      title:'Para llevar / venta directa',
      keywords:['para llevar','llevar','venta directa','sin mesa'],
      short:'Venta que no necesita una mesa.',
      body:()=>`
        <p><b>Para llevar</b> permite vender sin asignar mesa. Puede cambiar cliente, guardar una cuenta o cobrar normalmente.</p>
        ${usaComandasOperacion()
          ? '<p>Si contiene productos de preparación, esos productos pueden enviarse a Cocina/Barra aunque el pedido sea Para llevar.</p>'
          : '<p>Si las comandas están desactivadas, la venta funciona sin enviar órdenes a pantallas de preparación.</p>'}`
    },
    {
      id:'comandas', category:'Preparación', icon:'fa-clipboard-list',
      title:'Comandas y preparación',
      keywords:['comanda','cocina','barra','preparacion','enviar cocina','actualizar cocina','orden'],
      short:'Qué se envía a preparación y cómo se actualiza.',
      visible:()=>usaComandasOperacion(),
      body:()=>`
        <p>La <b>comanda</b> es la orden interna de preparación. Los productos se separan por su estación configurada.</p>
        <ul>
          <li><b>Enviar a cocina/preparación:</b> guarda la cuenta cuando corresponde y envía los productos pendientes.</li>
          <li><b>Actualizar cocina:</b> aparece al recuperar una cuenta que ya había enviado productos anteriormente.</li>
          <li>El envío es <b>incremental</b>: si ya se enviaron 1 papas y luego agrega otra, se envía únicamente la nueva cantidad.</li>
          <li>Un artículo ya finalizado en cocina no debe reaparecer como nuevo salvo que se agregue otra unidad/pedido.</li>
          <li>Los productos de Barra no deben aparecer en la pantalla de Cocina y viceversa.</li>
        </ul>`
    },
    {
      id:'estaciones', category:'Preparación', icon:'fa-fire-burner',
      title:'Cocina, Barra y grupos de preparación',
      keywords:['cocina','barra','grupo','estacion','estación','filtros'],
      short:'Clasificación interna de productos para preparación.',
      body:()=>`
        <p>Cocina y Barra son los dos grupos internos usados para clasificar productos. Sus nombres visibles pueden configurarse cuando el módulo usa comandas.</p>
        <p>Además existen las <b>categorías comerciales</b> (por ejemplo Bebidas, Entradas, Comida), que sirven para organizar el catálogo y son independientes de la estación de preparación.</p>`
    },
    {
      id:'cocina-estados', category:'Preparación', icon:'fa-kitchen-set',
      title:'Estados en la pantalla de Cocina',
      keywords:['pendiente','urgente','preparacion','finalizar','cocina estado'],
      short:'Cómo interpretar Pendiente, En preparación, Urgente y Finalizado.',
      visible:()=>usaComandasOperacion(),
      body:()=>`
        <ul>
          <li><b>Pendiente:</b> la orden llegó y espera atención.</li>
          <li><b>En preparación:</b> cocina ya comenzó a trabajarla.</li>
          <li><b>Urgente:</b> prioridad visual para llamar la atención; no debe duplicarse el indicador.</li>
          <li><b>Finalizar:</b> marca la orden como completada y deja de aparecer entre pendientes.</li>
        </ul>
        <p>El flujo puede configurarse como paso a paso o finalización directa.</p>`
    },
    {
      id:'tickets', category:'Documentos', icon:'fa-receipt',
      title:'Ticket de comanda / ticket de venta',
      keywords:['ticket','ticket comanda','ticket venta','imprimir','impresora','orden'],
      short:'Comprobante interno de la orden, separado de la factura fiscal.',
      body:()=>`
        <p>El ticket de orden sirve para preparación o control interno. <b>No reemplaza la factura fiscal.</b></p>
        <ul>
          <li>En modo restaurante/comandas puede llamarse <b>Ticket de comanda</b>.</li>
          <li>En venta directa puede mostrarse como <b>Ticket de venta</b>.</li>
          <li>La salida de la comanda permite decidir si se usa pantalla, ticket o el comportamiento configurado.</li>
          <li>La factura fiscal conserva su impresión normal desde el módulo de facturación.</li>
        </ul>`
    },
    {
      id:'clientes', category:'Clientes', icon:'fa-user',
      title:'Clientes y RTN',
      keywords:['cliente','rtn','consumidor final','cambiar cliente','crear cliente'],
      short:'Seleccionar el cliente correcto antes de finalizar.',
      body:()=>`
        <ul>
          <li><b>Consumidor final:</b> cliente genérico para ventas que no requieren datos específicos.</li>
          <li><b>Cambiar:</b> permite seleccionar otro cliente mientras la venta no haya culminado.</li>
          <li>Cuando el cliente tiene RTN, se muestra junto al nombre para poder identificarlo mejor.</li>
          <li>La creación/edición administrativa de clientes está sujeta a permisos.</li>
        </ul>`
    },
    {
      id:'factura-credito', category:'Facturación', icon:'fa-file-invoice-dollar',
      title:'Facturas de contado y crédito',
      keywords:['credito','crédito','contado','cuenta por cobrar','condicion factura','condición factura'],
      short:'Cómo elegir la condición de una factura cuando Crédito está habilitado.',
      visible:()=>permiteCreditoOperacion(),
      body:()=>`
        <p>Cuando <b>Permitir facturas al crédito</b> está activa, cada venta nueva inicia en <b>Contado</b> y el cajero puede cambiarla a <b>Crédito</b> antes de finalizar.</p>
        <ul>
          <li><b>Contado:</b> utiliza el flujo normal de cobro y métodos de pago.</li>
          <li><b>Crédito:</b> registra la factura como cuenta por cobrar y no solicita pago inmediato.</li>
          <li>Una cuenta abierta conserva la condición seleccionada al guardarse y recuperarse.</li>
          <li>Si Crédito está deshabilitado, el módulo trabaja únicamente en Contado.</li>
        </ul>`
    },
    {
      id:'caja', category:'Caja y pago', icon:'fa-cash-register',
      title:'Apertura, cierre de caja y cobro',
      keywords:['caja','apertura','cerrar caja','cobrar','pago','efectivo','tarjeta','transferencia'],
      short:'Estado de caja y proceso de cobro.',
      body:()=>`
        <p>El estado real de caja se consulta al servidor. La interfaz conserva visualmente el último estado confirmado durante consultas periódicas para evitar parpadeos por un timeout.</p>
        <ul>
          <li>Si la caja está cerrada debe <b>aperturarla</b> antes de vender.</li>
          <li><b>Cobrar</b> utiliza el método de pago estándar del sistema.</li>
          <li>Los medios disponibles dependen de la configuración normal de Facturas.</li>
          <li>Un error temporal de red no debe cambiar visualmente una caja abierta a cerrada.</li>
        </ul>`
    },
    {
      id:'reservas', category:'Servicio', icon:'fa-calendar-check',
      title:'Reservaciones de mesas',
      keywords:['reserva','reservacion','reservación','reservar mesa','fecha','hora','personas'],
      short:'Registrar una mesa para un cliente en una fecha y hora.',
      visible:()=>usaMesasOperacion(),
      body:()=>`
        <p>La reserva relaciona una mesa con un cliente, fecha, hora, cantidad de personas y notas.</p>
        <ul>
          <li>Use el icono de reserva de la mesa.</li>
          <li>Seleccione el cliente mediante el selector con búsqueda.</li>
          <li>Complete fecha, hora, personas y notas cuando sea necesario.</li>
          <li>Una reserva no es lo mismo que una cuenta abierta; la venta se inicia cuando el cliente es atendido.</li>
        </ul>`
    },
    {
      id:'productos', category:'Catálogo', icon:'fa-box',
      title:'Productos',
      keywords:['producto','productos','buscar','escanear','codigo','imagen','lupa','editar producto'],
      short:'Buscar, escanear, visualizar y agregar productos.',
      body:()=>`
        <ul>
          <li>Puede buscar por nombre o descripción.</li>
          <li>El campo de código permite trabajar con lector/escáner.</li>
          <li>La lupa abre la imagen en tamaño mayor sin agregar el producto.</li>
          <li>El botón <b>Agregar</b> incorpora el artículo al pedido.</li>
          <li>La edición de productos es una acción administrativa y depende de permisos/autenticación.</li>
        </ul>`
    },
    {
      id:'categorias', category:'Catálogo', icon:'fa-tags',
      title:'Categorías y filtros',
      keywords:['categoria','categoría','categorias','filtro','bebidas','comida'],
      short:'Organizan los productos para encontrarlos rápidamente.',
      body:()=>`
        <p>Las categorías comerciales agrupan productos como Bebidas, Entradas o Comida. Al seleccionarlas se filtran las tarjetas visibles.</p>
        <p>No confunda <b>categoría</b> con <b>estación de preparación</b>: son conceptos independientes.</p>`
    },
    {
      id:'promociones', category:'Promociones', icon:'fa-percent',
      title:'Promociones',
      keywords:['promocion','promoción','descuento','vigencia','horario','dias','prioridad'],
      short:'Crear descuentos con vigencia, horario y reglas de aplicación.',
      body:()=>`
        <ul>
          <li>Puede definir descuento por <b>porcentaje</b> o <b>monto fijo</b>.</li>
          <li>La promoción puede aplicar a productos, categorías o al alcance configurado.</li>
          <li>Puede establecer fecha inicial/final, horario diario y días de la semana.</li>
          <li>La prioridad ayuda a resolver reglas cuando existen varias promociones aplicables.</li>
          <li>Las promociones se administran desde <b>Gestionar</b> cuando el usuario tiene permiso.</li>
        </ul>`
    },
    {
      id:'promo-productos', category:'Promociones', icon:'fa-boxes-stacked',
      title:'Asignar productos o categorías a una promoción',
      keywords:['asignar promocion','producto promocion','categoria promocion','regla promocion'],
      short:'Define exactamente qué artículos participan en una promoción.',
      body:()=>`
        <p>Después de crear una promoción puede relacionarla con los productos o categorías correspondientes. El sistema utiliza esas asignaciones al evaluar la promoción durante la venta.</p>
        <div class="rs-help-callout"><i class="fas fa-circle-info"></i><span>Si una promoción no tiene la asignación esperada, revise primero su tipo “Aplica a”, vigencia y reglas.</span></div>`
    },
    {
      id:'combos', category:'Promociones', icon:'fa-boxes-packing',
      title:'Combos',
      keywords:['combo','combos','paquete','regla combo','producto combo'],
      short:'Agrupa varios productos bajo una regla de venta.',
      body:()=>`
        <p>Los combos permiten definir un producto representativo y sus componentes/reglas. Se gestionan desde las opciones administrativas del módulo.</p>
        <p>Al modificar un combo revise sus productos relacionados y reglas para que el precio y contenido correspondan a la configuración deseada.</p>`
    },
    {
      id:'recurrentes', category:'Facturación', icon:'fa-calendar-days',
      title:'Facturas recurrentes',
      keywords:['recurrente','recurrentes','programar','diaria','semanal','mensual','proforma'],
      short:'Programa futuras facturas utilizando los productos de la venta.',
      body:()=>`
        <ul>
          <li>Puede consultar programaciones existentes sin tener productos cargados.</li>
          <li>Para <b>guardar una nueva recurrencia</b> sí debe tener al menos un producto en la venta actual.</li>
          <li>Permite documento normal o proforma, fecha/hora inicial y periodicidad disponible.</li>
          <li>La condición de pago de las recurrentes utiliza el flujo configurado para este módulo.</li>
          <li>Desde el panel puede revisar activas, generadas, errores, próximas ejecuciones y cancelar una programación.</li>
        </ul>`
    },
    {
      id:'configuracion', category:'Administración', icon:'fa-sliders',
      title:'Configuración del módulo',
      keywords:['configuracion','configuración','usar mesas','usar comandas','flujo','salida comanda','modo operacion'],
      short:'Adapta el POS a restaurante o venta directa.',
      body:()=>`
        <div class="rs-help-compare">
          <div><b>Usar mesas</b><span>Muestra mesas y permite trabajar Para llevar / En mesa.</span></div>
          <div><b>Usar comandas</b><span>Habilita estaciones de preparación y envío a Cocina/Barra.</span></div>
          <div><b>Ambas</b><span>Flujo completo de restaurante.</span></div>
          <div><b>Ninguna</b><span>Venta directa genérica; no necesita mesas ni preparación.</span></div>
        </div>
        <p>Cuando las comandas están activas también puede configurar nombres visibles, destino de la orden, momento del ticket y flujo de preparación.</p>
        <div class="rs-help-callout"><i class="fas fa-shield-halved"></i><span><b>Configuración del módulo siempre solicita credenciales administrativas</b>, incluso cuando la clave adicional para otras acciones de gestión está desactivada.</span></div>`
    },
    {
      id:'gestionar', category:'Administración', icon:'fa-screwdriver-wrench',
      title:'Botón Gestionar y permisos',
      keywords:['gestionar','permiso','administrador','super administrador','seguridad','autenticacion'],
      short:'Acciones sensibles disponibles únicamente para usuarios autorizados.',
      body:()=>`
        <p><b>Gestionar</b> concentra opciones administrativas como configuración, productos, categorías, promociones y combos.</p>
        <p>Su visibilidad y las acciones internas dependen de los permisos/rol configurados.</p>
        ${solicitarClaveGestionOperacion()
          ? '<p>Además, esta empresa tiene activa la <b>validación con clave administrativa</b> para acciones de gestión.</p>'
          : ''}`
    },
    {
      id:'seguridad-clave', category:'Administración', icon:'fa-shield-halved',
      title:'Validación con clave administrativa',
      keywords:['clave','password','seguridad','validacion','validación','administrador','editar','crear cliente','gestionar'],
      short:'Protección adicional para cambios administrativos del módulo.',
      visible:()=>solicitarClaveGestionOperacion(),
      body:()=>`
        <p>Esta empresa tiene activa la opción <b>Solicitar clave para gestión</b>.</p>
        <p>Por seguridad, acciones como crear o editar clientes, mesas, categorías, productos, promociones y combos solicitarán credenciales administrativas antes de continuar.</p>
        <div class="rs-help-callout"><i class="fas fa-shield-halved"></i><span>La clave es una protección adicional. Los permisos del usuario siempre se siguen respetando.</span></div>
        <p><b>Configuración del módulo siempre solicita credenciales administrativas</b>, aunque “Solicitar clave para gestión” esté desactivado.</p><p>Un usuario autorizado puede cambiar el comportamiento de las demás acciones desde <b>Gestionar → Configuración del módulo</b>.</p>`
    },
    {
      id:'ayuda-movil', category:'Móvil', icon:'fa-mobile-screen-button',
      title:'Asistente móvil',
      keywords:['movil','móvil','celular','asistente','pasos','telefono'],
      short:'Flujo simplificado para teléfonos sin alterar PC o tablet.',
      body:()=>`
        <p>En teléfonos el módulo se organiza en pasos para evitar una pantalla horizontal:</p>
        <ol class="rs-help-steps">
          <li>Servicio / Mesa</li>
          <li>Productos</li>
          <li>Pedido</li>
          <li>Cliente / Caja</li>
        </ol>
        <p>PC y tablet conservan la distribución completa. El asistente móvil utiliza las mismas funciones de negocio; solo cambia la presentación.</p>`
    },
    {
      id:'atajos', category:'Atajos', icon:'fa-keyboard',
      title:'Atajos de teclado',
      keywords:['atajo','atajos','teclado','ctrl','cmd','alt','enter'],
      short:'Acciones rápidas disponibles desde teclado.',
      body:()=>`
        <div class="rs-help-shortcuts">
          <div><span>Buscar producto</span><kbd>Ctrl/Cmd</kbd><kbd>Alt</kbd><kbd>F</kbd></div>
          <div><span>Guardar cuenta</span><kbd>Ctrl/Cmd</kbd><kbd>Alt</kbd><kbd>S</kbd></div>
          <div><span>Cuentas abiertas</span><kbd>Ctrl/Cmd</kbd><kbd>Alt</kbd><kbd>A</kbd></div>
          <div><span>Cambiar cliente</span><kbd>Ctrl/Cmd</kbd><kbd>Alt</kbd><kbd>C</kbd></div>
          ${usaMesasOperacion()?'<div><span>Nueva mesa</span><kbd>Ctrl/Cmd</kbd><kbd>M</kbd></div>':''}
          <div><span>Confirmar escaneo/búsqueda</span><kbd>Enter</kbd></div>
        </div>`
    }
  ];

  let rsHelpCategory = 'Todos';
  let rsHelpInitialized = false;

  function rsHelpNormalize(value){
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g,'')
      .toLowerCase()
      .trim();
  }

  function rsHelpVisibleTopics(){
    return RS_HELP_TOPICS.filter(function(topic){
      return typeof topic.visible !== 'function' || topic.visible();
    });
  }

  function rsHelpModeText(){
    const mesas = usaMesasOperacion();
    const comandas = usaComandasOperacion();
    if (mesas && comandas) return '<i class="fas fa-utensils"></i><span><b>Modo restaurante</b><small>Mesas + preparación</small></span>';
    if (mesas) return '<i class="fas fa-chair"></i><span><b>Modo mesas</b><small>Sin pantalla de preparación</small></span>';
    if (comandas) return '<i class="fas fa-fire-burner"></i><span><b>Venta + preparación</b><small>Sin mesas</small></span>';
    return '<i class="fas fa-cash-register"></i><span><b>Venta directa</b><small>Sin mesas ni comandas</small></span>';
  }

  function rsHelpRenderCategories(){
    const host = document.getElementById('rs-help-categories');
    if (!host) return;
    const topics = rsHelpVisibleTopics();
    const categories = ['Todos'].concat([...new Set(topics.map(t=>t.category))]);
    if (!categories.includes(rsHelpCategory)) rsHelpCategory='Todos';
    host.innerHTML = categories.map(function(cat){
      const count = cat === 'Todos' ? topics.length : topics.filter(t=>t.category===cat).length;
      return `<button type="button" class="rs-help-category ${cat===rsHelpCategory?'active':''}" data-help-category="${escapeHtml(cat)}">
        <span>${escapeHtml(cat)}</span><small>${count}</small>
      </button>`;
    }).join('');
  }

  function rsHelpRender(){
    const search = document.getElementById('rs-help-search');
    const results = document.getElementById('rs-help-results');
    const empty = document.getElementById('rs-help-empty');
    const summary = document.getElementById('rs-help-summary');
    const mode = document.getElementById('rs-help-mode');
    if (!results || !empty || !summary) return;

    if (mode) mode.innerHTML = rsHelpModeText();

    const q = rsHelpNormalize(search ? search.value : '');
    const topics = rsHelpVisibleTopics().filter(function(topic){
      if (rsHelpCategory !== 'Todos' && topic.category !== rsHelpCategory) return false;
      if (!q) return true;
      const haystack = rsHelpNormalize([
        topic.title, topic.short, topic.category,
        ...(topic.keywords || [])
      ].join(' '));
      return q.split(/\s+/).every(word=>haystack.includes(word));
    });

    summary.innerHTML = q
      ? `<i class="fas fa-filter"></i> ${topics.length} resultado(s) para <b>“${escapeHtml(search.value.trim())}”</b>`
      : `<i class="fas fa-book-open"></i> ${topics.length} tema(s) disponibles`;

    empty.style.display = topics.length ? 'none' : 'flex';

    results.innerHTML = topics.map(function(topic,index){
      return `<article class="rs-help-topic" data-help-topic="${escapeHtml(topic.id)}">
        <button type="button" class="rs-help-topic-head" aria-expanded="false">
          <span class="rs-help-topic-icon"><i class="fas ${escapeHtml(topic.icon)}"></i></span>
          <span class="rs-help-topic-copy">
            <strong>${escapeHtml(topic.title)}</strong>
            <small>${escapeHtml(topic.short)}</small>
          </span>
          <span class="rs-help-topic-category">${escapeHtml(topic.category)}</span>
          <i class="fas fa-chevron-down rs-help-chevron"></i>
        </button>
        <div class="rs-help-topic-body" hidden>${topic.body()}</div>
      </article>`;
    }).join('');

    // Si hay una búsqueda exacta o un único resultado, abrirlo automáticamente.
    if (topics.length === 1 || (q && topics.length <= 3)) {
      results.querySelectorAll('.rs-help-topic').forEach(function(card){
        const head=card.querySelector('.rs-help-topic-head');
        const body=card.querySelector('.rs-help-topic-body');
        if(head&&body){ head.setAttribute('aria-expanded','true'); body.hidden=false; card.classList.add('open'); }
      });
    }
  }

  function rsHelpOpen(){
    const modal = document.getElementById('modal-help');
    if (!modal) return;

    rsHelpRenderCategories();
    rsHelpRender();
    modal.style.display='block';

    window.setTimeout(function(){
      const search=document.getElementById('rs-help-search');
      if(search) search.focus();
    },80);
  }

  function rsHelpInit(){
    if (rsHelpInitialized) return;
    rsHelpInitialized=true;

    document.addEventListener('click', function(e){
      const helpBtn=e.target.closest && e.target.closest('#btn-help');
      if(helpBtn){
        e.preventDefault();
        rsHelpOpen();
        return;
      }

      const category=e.target.closest && e.target.closest('[data-help-category]');
      if(category){
        rsHelpCategory=category.getAttribute('data-help-category') || 'Todos';
        rsHelpRenderCategories();
        rsHelpRender();
        return;
      }

      const head=e.target.closest && e.target.closest('.rs-help-topic-head');
      if(head){
        const card=head.closest('.rs-help-topic');
        const body=card && card.querySelector('.rs-help-topic-body');
        if(!card||!body) return;
        const open=head.getAttribute('aria-expanded')==='true';
        head.setAttribute('aria-expanded', open?'false':'true');
        body.hidden=open;
        card.classList.toggle('open',!open);
        return;
      }

      const clear=e.target.closest && e.target.closest('#rs-help-clear');
      if(clear){
        const input=document.getElementById('rs-help-search');
        if(input){ input.value=''; input.focus(); }
        rsHelpRender();
      }
    });

    document.addEventListener('input',function(e){
      if(e.target && e.target.id==='rs-help-search') rsHelpRender();
    });
  }

  rsHelpInit();


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

  // ==========================================================
  // FACTURAS RECURRENTES - reutiliza el módulo público existente
  // ==========================================================
  var detallesFacturasRecurrentesRest = {};
  var solicitudListadoRecurrentesRest = null;
  var temporizadorListadoRecurrentesRest = null;
  var firmaListadoRecurrentesRest = '';
  var filtroEstadoRecurrenteRest = '1';

  function recurrenteModalDisponibleRest(){
    return !!document.getElementById('recurringBillModal');
  }

  function recurrenteTieneProductosRest(){
    return Array.isArray(comandaItems) && comandaItems.length > 0;
  }

  function recurrenteClienteIdRest(){
    return parseInt(clienteSeleccionado && clienteSeleccionado.id, 10) || 0;
  }

  function recurrenteColaboradorIdRest(){
    if (cajeroActualId > 0) return cajeroActualId;
    var desdeCaja = parseInt($('#formAperturaCaja #colaboradores_id_apertura').val(), 10) || 0;
    return desdeCaja;
  }

  function recurrenteNumeroRest(v){
    var n = parseFloat(v || 0);
    return Number.isFinite(n) ? n : 0;
  }

  function recurrenteDetalleRest(){
    return (Array.isArray(comandaItems) ? comandaItems : []).map(function(item){
      var p = item && item.producto ? item.producto : {};
      var precio = recurrenteNumeroRest(item.precio != null ? item.precio : p.precio);
      var cantidad = recurrenteNumeroRest(item.cantidad);
      var tasa1 = p.isv1 ? recurrenteNumeroRest(isvRates[1]) : 0;
      var tasa2 = p.isv2 ? recurrenteNumeroRest(isvRates[2]) : 0;
      return {
        productos_id: parseInt(p.id || item.productos_id || item.producto_id, 10) || 0,
        producto: String(p.nombre || item.nombre || 'Producto'),
        cantidad: cantidad,
        precio: precio,
        descuento: 0,
        isv_valor: tasa1,
        isv_valor1: tasa2,
        medida: String(p.medida || item.medida || 'Und'),
        almacen_id: parseInt(p.almacen_id || item.almacen_id, 10) || 0,
        precio_real: precio,
        referencia_producto: String(p.barCode || item.barCode || '')
      };
    }).filter(function(x){ return x.productos_id > 0 && x.cantidad > 0; });
  }

  function recurrenteLocalISOStringRest(dt){
    var pad = function(n){ return String(n).padStart(2,'0'); };
    return dt.getFullYear()+'-'+pad(dt.getMonth()+1)+'-'+pad(dt.getDate())+'T'+pad(dt.getHours())+':'+pad(dt.getMinutes());
  }

  function recurrenteSincronizarInicioRest(){
    var fecha = $('#rec_fecha_inicio').val();
    var hora = $('#rec_hora_inicio').val();
    $('#rec_start_at').val(fecha && hora ? fecha + 'T' + hora : '');
  }

  function recurrenteFechaLocalRest(){
    var fecha = $('#rec_fecha_inicio').val();
    var hora = $('#rec_hora_inicio').val();
    if (!fecha || !hora) return null;
    var f = fecha.split('-').map(Number), h = hora.split(':').map(Number);
    return new Date(f[0], f[1]-1, f[2], h[0], h[1] || 0, 0, 0);
  }

  function recurrenteFormatearFechaVistaRest(fecha){
    return new Intl.DateTimeFormat('es-HN', {weekday:'long',day:'numeric',month:'long',year:'numeric',hour:'numeric',minute:'2-digit'}).format(fecha);
  }

  function recurrenteSiguienteFechaRest(actual, frecuencia, diaOriginal){
    var siguiente = new Date(actual.getTime());
    if (frecuencia === 'daily') siguiente.setDate(siguiente.getDate()+1);
    if (frecuencia === 'weekly') siguiente.setDate(siguiente.getDate()+7);
    if (frecuencia === 'monthly') {
      var hora=siguiente.getHours(), minuto=siguiente.getMinutes();
      siguiente=new Date(siguiente.getFullYear(), siguiente.getMonth()+1, 1, hora, minuto, 0, 0);
      var ultimoDia=new Date(siguiente.getFullYear(), siguiente.getMonth()+1, 0).getDate();
      siguiente.setDate(Math.min(diaOriginal, ultimoDia));
    }
    return siguiente;
  }

  function recurrenteActualizarResumenRest(){
    recurrenteSincronizarInicioRest();
    var inicio = recurrenteFechaLocalRest();
    var frecuencia = $('#rec_periodicidad').val() || 'monthly';
    var $lista = $('#rec_proximas_fechas').empty();
    if (!inicio || isNaN(inicio.getTime())) {
      $('#rec_resumen_texto').text('Selecciona una fecha y una hora válidas.');
      return;
    }
    var dias=['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    var horaTexto=new Intl.DateTimeFormat('es-HN',{hour:'numeric',minute:'2-digit'}).format(inicio);
    var texto='';
    if(frecuencia==='once') texto='Se generará una sola vez: '+recurrenteFormatearFechaVistaRest(inicio)+'.';
    if(frecuencia==='daily') texto='Se generará todos los días a las '+horaTexto+', comenzando el '+recurrenteFormatearFechaVistaRest(inicio)+'.';
    if(frecuencia==='weekly') texto='Se generará cada '+dias[inicio.getDay()]+' a las '+horaTexto+', comenzando el '+recurrenteFormatearFechaVistaRest(inicio)+'.';
    if(frecuencia==='monthly') texto='Se generará el día '+inicio.getDate()+' de cada mes a las '+horaTexto+', comenzando el '+recurrenteFormatearFechaVistaRest(inicio)+'.';
    if ($('#rec_sin_fin').is(':checked') && frecuencia!=='once') texto += ' Continuará hasta que la canceles.';
    if (!$('#rec_sin_fin').is(':checked') && $('#rec_until').val() && frecuencia!=='once') texto += ' Finalizará el '+$('#rec_until').val()+'.';
    $('#rec_resumen_texto').text(texto);
    var cantidad=frecuencia==='once'?1:4, cursor=new Date(inicio.getTime());
    var hasta=(!$('#rec_sin_fin').is(':checked') && $('#rec_until').val())?$('#rec_until').val():null;
    for(var i=0;i<cantidad;i++){
      var iso=cursor.getFullYear()+'-'+String(cursor.getMonth()+1).padStart(2,'0')+'-'+String(cursor.getDate()).padStart(2,'0');
      if(hasta && iso>hasta) break;
      $lista.append('<li>'+escapeHtml(recurrenteFormatearFechaVistaRest(cursor))+'</li>');
      cursor=recurrenteSiguienteFechaRest(cursor,frecuencia,inicio.getDate());
    }
    if(!$lista.children().length) $lista.append('<li>La fecha final no permite ninguna generación.</li>');
  }

  function abrirFacturaRecurrenteRest(){
    if (!recurrenteModalDisponibleRest()) {
      showNotify('error','Modal no disponible','El modal público de Factura Recurrente no está cargado en esta plantilla.');
      return;
    }

    // Consultar recurrencias NO exige tener una venta preparada.
    // Cliente/productos/cajero se validan únicamente al guardar una NUEVA recurrencia.
    filtroEstadoRecurrenteRest='1';
    $('#confirmRecurring').prop('disabled',false).html('<i class="fas fa-calendar-check mr-1"></i> Guardar recurrencia');
    $('#rec_tipo_factura').val('2');
    $('#rec_tipo_documento').val('0');
    $('#btn-rec-tipo-normal').addClass('btn-primary').removeClass('btn-outline-primary');
    $('#btn-rec-tipo-proforma').addClass('btn-outline-primary').removeClass('btn-primary');
    var now=new Date(); now.setMinutes(now.getMinutes()+10);
    var inicio=recurrenteLocalISOStringRest(now).split('T');
    $('#rec_fecha_inicio').val(inicio[0]);
    $('#rec_hora_inicio').val(inicio[1]);
    recurrenteSincronizarInicioRest();
    $('#rec_periodicidad').val('monthly');
    $('.rec-frecuencia').removeClass('active').filter('[data-frecuencia="monthly"]').addClass('active');
    $('#rec_until').val('');
    $('#rec_sin_fin').prop('checked',true);
    $('#rec_fin_contenedor').hide();
    $('#rec_enviar_correo').prop('checked',true);
    $('#rec_info').show(); $('#rec_spinner').hide();
    recurrenteActualizarResumenRest();
    $('#recurringBillModal').modal('show');
    listarFacturasRecurrentesRest();
  }

  $(document).off('click.recurrenteRest','#btn-factura-recurrente').on('click.recurrenteRest','#btn-factura-recurrente',function(e){
    e.preventDefault();
    abrirFacturaRecurrenteRest();
  });

  $(document).off('click.recurrenteRest','.rec-frecuencia').on('click.recurrenteRest','.rec-frecuencia',function(){
    var frecuencia=String($(this).data('frecuencia'));
    $('#rec_periodicidad').val(frecuencia);
    $('.rec-frecuencia').removeClass('active'); $(this).addClass('active');
    var una=frecuencia==='once';
    $('#rec_sin_fin').closest('.custom-control').toggle(!una);
    $('#rec_fin_contenedor').toggle(!una && !$('#rec_sin_fin').is(':checked'));
    recurrenteActualizarResumenRest();
  });
  $(document).off('change.recurrenteRest input.recurrenteRest','#rec_fecha_inicio, #rec_hora_inicio, #rec_until')
    .on('change.recurrenteRest input.recurrenteRest','#rec_fecha_inicio, #rec_hora_inicio, #rec_until',recurrenteActualizarResumenRest);
  $(document).off('change.recurrenteRest','#rec_sin_fin').on('change.recurrenteRest','#rec_sin_fin',function(){
    $('#rec_fin_contenedor').toggle(!this.checked && $('#rec_periodicidad').val()!=='once');
    if(this.checked) $('#rec_until').val('');
    recurrenteActualizarResumenRest();
  });
  $(document).off('click.recurrenteRest','#btn-rec-tipo-normal, #btn-rec-tipo-proforma').on('click.recurrenteRest','#btn-rec-tipo-normal, #btn-rec-tipo-proforma',function(){
    var tipo=String($(this).data('tipo'));
    $('#rec_tipo_documento').val(tipo);
    $('#btn-rec-tipo-normal').toggleClass('btn-primary',tipo==='0').toggleClass('btn-outline-primary',tipo!=='0');
    $('#btn-rec-tipo-proforma').toggleClass('btn-primary',tipo==='1').toggleClass('btn-outline-primary',tipo!=='1');
  });

  $(document).off('click.recurrenteRest','#confirmRecurring').on('click.recurrenteRest','#confirmRecurring',function(){
    var clienteId=recurrenteClienteIdRest(), colaboradorId=recurrenteColaboradorIdRest(), detalle=recurrenteDetalleRest();
    if(clienteId<=0){ showNotify('warning','Cliente requerido','Seleccione un cliente antes de guardar la recurrencia.'); return; }
    if(colaboradorId<=0){ showNotify('error','Cajero no identificado','No se pudo identificar el colaborador actual.'); return; }
    if(!detalle.length){ showNotify('warning','Factura sin productos','Agregue al menos un producto antes de guardar la recurrencia.'); return; }
    recurrenteSincronizarInicioRest();
    var startAt=$('#rec_start_at').val();
    if(!startAt){ showNotify('warning','Fecha requerida','Debe indicar la fecha y hora de inicio.'); return; }
    if($('#rec_periodicidad').val()!=='once' && !$('#rec_sin_fin').is(':checked') && !$('#rec_until').val()){
      showNotify('warning','Fecha final requerida','Seleccione hasta cuándo se repetirá o active “Repetir sin fecha final”.'); return;
    }
    var payload={
      clientes_id:clienteId,
      colaboradores_id:colaboradorId,
      notas:observacionesTextarea ? String(observacionesTextarea.value || '') : '',
      fecha_dolar:new Date().toISOString().slice(0,10),
      tipo_documento:$('#rec_tipo_documento').val() || '0',
      tipo_factura:2,
      start_at:startAt,
      periodicidad:$('#rec_periodicidad').val() || 'monthly',
      until:($('#rec_periodicidad').val()==='once'||$('#rec_sin_fin').is(':checked'))?null:($('#rec_until').val()||null),
      enviar_correo:$('#rec_enviar_correo').is(':checked')?1:2,
      exoneracion_orden:null,
      exoneracion_constancia:null,
      exoneracion_sag:null,
      exoneracion_orden_interno:null,
      detalle:detalle
    };
    var $btn=$('#confirmRecurring');
    $('#rec_info').hide(); $('#rec_spinner').show(); $btn.prop('disabled',true);
    $.ajax({type:'POST',url:BASE+'core/facturas/agregarFacturaRecurrente.php',data:{data:JSON.stringify(payload)},dataType:'json',timeout:15000})
      .done(function(res){
        if(res && (res.ok===true || res.success===true)){
          $('#rec_info').show(); $('#rec_spinner').hide();
          $btn.prop('disabled',true).html('<i class="fas fa-check-circle mr-1"></i> Recurrencia guardada');
          showNotify('success','Recurrencia creada',res.msg || 'La factura recurrente ha sido guardada.');
          listarFacturasRecurrentesRest();
        }else{
          $('#rec_info').show(); $('#rec_spinner').hide(); $btn.prop('disabled',false);
          showNotify('error','No se pudo guardar',(res && (res.msg||res.message)) || 'No se pudo guardar la recurrencia.');
        }
      }).fail(function(xhr,status){
        $('#rec_info').show(); $('#rec_spinner').hide(); $btn.prop('disabled',false);
        showNotify('error','Error de comunicación',status==='timeout'?'La solicitud tardó demasiado.':'No fue posible guardar la recurrencia.');
      });
  });

  function escaparRecurrenteRest(v){ return $('<div>').text(v==null?'':String(v)).html(); }
  function etiquetaPeriodicidadRest(v){ return ({once:'Una vez',daily:'Diaria',weekly:'Semanal',monthly:'Mensual'})[v] || v; }
  function monedaRecurrenteRest(v){ var n=parseFloat(v||0); return 'L. '+n.toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function fechaRecurrenteRest(v){
    if(!v) return 'Sin próxima fecha';
    var m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
    if(!m) return String(v);
    var f=new Date(+m[1],+m[2]-1,+m[3],+(m[4]||0),+(m[5]||0));
    return f.toLocaleString('es-HN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
  }
  function actualizarCantidadesRecurrentesRest(datos){
    var c={'1':0,'2':0,'3':0};
    (datos||[]).forEach(function(i){var e=String(parseInt(i.estado,10)); if(c[e]!=null)c[e]++;});
    $('#rec_cantidad_pendientes').text(c['1']); $('#rec_cantidad_canceladas').text(c['2']); $('#rec_cantidad_finalizadas').text(c['3']); $('#rec_cantidad_todas').text((datos||[]).length);
  }
  function pintarResumenRecurrentesRest(resumen){
    resumen = resumen || {};
    $('#rec_kpi_activas').text((parseInt(resumen.activas || 0, 10) || 0).toLocaleString('es-HN'));
    $('#rec_kpi_generadas').text((parseInt(resumen.generadas || 0, 10) || 0).toLocaleString('es-HN'));
    $('#rec_kpi_correos').text((parseInt(resumen.correos_enviados || 0, 10) || 0).toLocaleString('es-HN'));
    $('#rec_kpi_errores').text((parseInt(resumen.errores || 0, 10) || 0).toLocaleString('es-HN'));
    $('#rec_kpi_total').text(monedaRecurrenteRest(resumen.total_facturado || 0));
    $('#rec_kpi_proxima').text(resumen.proxima_generacion ? fechaRecurrenteRest(resumen.proxima_generacion) : 'Sin programación');
    $('#rec_kpi_actualizado').text('Resumen actualizado automáticamente');
  }
  function aplicarFiltroRecurrenteRest(){
    var visibles=0;
    $('#listaFacturasRecurrentes .rec-card').each(function(){var ok=filtroEstadoRecurrenteRest==='todas'||String($(this).data('estado'))===filtroEstadoRecurrenteRest; $(this).toggle(ok); if(ok)visibles++;});
    $('#rec_filtro_sin_resultados').remove();
    if(!visibles && $('#listaFacturasRecurrentes .rec-card').length){ $('#listaFacturasRecurrentes').append('<div id="rec_filtro_sin_resultados" class="text-center text-muted py-4">No hay programaciones con este filtro.</div>'); }
    $('.rec-filtro-estado').removeClass('active').filter('[data-estado="'+filtroEstadoRecurrenteRest+'"]').addClass('active');
  }
  function listarFacturasRecurrentesRest(opciones){
    opciones=opciones||{}; var silencioso=opciones.silencioso===true, $l=$('#listaFacturasRecurrentes');
    if(!$l.length) return; if(solicitudListadoRecurrentesRest) return solicitudListadoRecurrentesRest;
    if(!silencioso)$l.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando programaciones...</div>');
    solicitudListadoRecurrentesRest=$.ajax({type:'GET',url:BASE+'core/facturas/listarFacturasRecurrentes.php',dataType:'json',cache:false,timeout:15000})
      .done(function(res){
        if(!res||res.ok!==true){if(!silencioso)$l.html('<div class="alert alert-danger mb-0">'+escaparRecurrenteRest((res&&res.msg)||'No se pudo cargar el listado.')+'</div>');return;}

        // El mismo endpoint de Facturas devuelve también el resumen/KPIs.
        // Se pinta SIEMPRE, incluso cuando la lista no cambió o está vacía.
        pintarResumenRecurrentesRest(res.resumen);

        if(!Array.isArray(res.data)||!res.data.length){firmaListadoRecurrentesRest='[]';actualizarCantidadesRecurrentesRest([]);$l.html('<div class="text-center text-muted py-4"><i class="far fa-calendar-times fa-2x d-block mb-2"></i>No hay programaciones registradas.</div>');return;}
        var firma=JSON.stringify(res.data); if(silencioso&&firma===firmaListadoRecurrentesRest)return; firmaListadoRecurrentesRest=firma;
        detallesFacturasRecurrentesRest={}; actualizarCantidadesRecurrentesRest(res.data); var html='';
        res.data.forEach(function(item){
          var estado=parseInt(item.estado,10), txt=estado===1?'Activa':(estado===2?'Cancelada':'Finalizada'), cls=estado===1?'success':(estado===2?'danger':'secondary'), id=parseInt(item.rec_id,10);
          detallesFacturasRecurrentesRest[id]=Array.isArray(item.detalle)?item.detalle:[];
          html+='<div class="rec-card" data-estado="'+estado+'"><div class="rec-card-main"><div class="d-flex justify-content-between align-items-start"><div class="rec-card-title"><i class="fas fa-user mr-1 text-primary"></i>'+escaparRecurrenteRest(item.cliente)+'</div><span class="badge badge-'+cls+'">'+txt+'</span></div><div class="rec-card-meta"><span class="rec-chip"><i class="far fa-file-alt mr-1"></i>'+escaparRecurrenteRest(item.documento)+'</span><span class="rec-chip"><i class="far fa-calendar-alt mr-1"></i>'+escaparRecurrenteRest(etiquetaPeriodicidadRest(item.periodicidad))+'</span><span class="rec-chip"><i class="fas fa-credit-card mr-1"></i>Crédito</span></div><div class="rec-card-datos"><div class="rec-dato"><small>Próxima generación</small><strong>'+escaparRecurrenteRest(fechaRecurrenteRest(item.next_run_at))+'</strong></div><div class="rec-dato"><small>Productos</small><strong>'+parseInt(item.cantidad_productos||0,10)+' producto(s)</strong></div><div class="rec-dato"><small>Total estimado</small><strong>'+escaparRecurrenteRest(monedaRecurrenteRest(item.total_estimado))+'</strong></div></div><div class="rec-card-actions"><button type="button" class="btn btn-outline-primary btn-sm ver-detalle-recurrente" data-id="'+id+'"><i class="fas fa-eye mr-1"></i>Ver detalle</button>'+(estado===1?'<button type="button" class="btn btn-outline-danger btn-sm cancelar-recurrente" data-id="'+id+'"><i class="fas fa-ban mr-1"></i>Cancelar</button>':'')+'</div></div><div class="rec-detalle" id="rec-detalle-'+id+'"></div></div>';
        });
        $l.html(html); aplicarFiltroRecurrenteRest();
      }).fail(function(xhr,status){if(!silencioso)$l.html('<div class="alert alert-danger mb-0">'+(status==='timeout'?'La consulta tardó demasiado.':'No se pudieron cargar las programaciones.')+'</div>');})
      .always(function(){solicitudListadoRecurrentesRest=null;});
    return solicitudListadoRecurrentesRest;
  }
  $(document).off('click.recurrenteRest','.rec-filtro-estado').on('click.recurrenteRest','.rec-filtro-estado',function(){filtroEstadoRecurrenteRest=String($(this).data('estado')||'1');aplicarFiltroRecurrenteRest();});
  $(document).off('click.recurrenteRest','#recargarRecurrentes').on('click.recurrenteRest','#recargarRecurrentes',function(){listarFacturasRecurrentesRest();});
  $(document).off('click.recurrenteRest','.ver-detalle-recurrente').on('click.recurrenteRest','.ver-detalle-recurrente',function(){
    var id=parseInt($(this).data('id'),10), $d=$('#rec-detalle-'+id); if(!id||!$d.length)return;
    if($d.is(':visible')){$d.slideUp(150);$(this).html('<i class="fas fa-eye mr-1"></i>Ver detalle');return;}
    $d.slideDown(150);$(this).html('<i class="fas fa-eye-slash mr-1"></i>Ocultar detalle'); if($d.data('cargado'))return;
    var ps=detallesFacturasRecurrentesRest[id]||[], total=0, html='';
    if(!ps.length)html='<div class="text-muted">Esta programación no tiene productos guardados.</div>'; else {html='<div class="rec-producto font-weight-bold text-muted"><span>Producto</span><span>Cantidad</span><span>Precio</span><span>Total</span></div>';ps.forEach(function(p){total+=parseFloat(p.total_linea||0);html+='<div class="rec-producto"><strong>'+escaparRecurrenteRest(p.producto)+'</strong><span>'+escaparRecurrenteRest(p.cantidad)+' '+escaparRecurrenteRest(p.medida||'')+'</span><span>'+escaparRecurrenteRest(monedaRecurrenteRest(p.precio))+'</span><span>'+escaparRecurrenteRest(monedaRecurrenteRest(p.total_linea))+'</span></div>';});html+='<div class="rec-detalle-total">Total programado: '+escaparRecurrenteRest(monedaRecurrenteRest(total))+'</div>';}
    $d.data('cargado',true).html(html);
  });
  $(document).off('click.recurrenteRest','.cancelar-recurrente').on('click.recurrenteRest','.cancelar-recurrente',function(){
    var id=parseInt($(this).data('id'),10); if(!id)return;
    swal({title:'Cancelar factura recurrente',text:'¿Está seguro de cancelar esta programación?',icon:'warning',buttons:{cancel:{text:'No, mantener activa',visible:true},confirm:{text:'Sí, cancelar',closeModal:false}},dangerMode:true,closeOnEsc:false,closeOnClickOutside:false}).then(function(ok){
      if(ok!==true)return;
      $.ajax({type:'POST',url:BASE+'core/facturas/cancelarFacturaRecurrente.php',dataType:'json',data:{rec_id:id},timeout:15000}).done(function(res){swal.close();if(res&&res.ok===true){showNotify('success','Recurrencia cancelada',res.msg||'La programación fue cancelada.');listarFacturasRecurrentesRest();}else showNotify('error','No se pudo cancelar',(res&&res.msg)||'No se pudo cancelar la recurrencia.');}).fail(function(){swal.close();showNotify('error','Error de comunicación','No fue posible cancelar la recurrencia.');});
    });
  });
  $('#recurringBillModal').off('shown.bs.modal.recurrenteRest hidden.bs.modal.recurrenteRest').on('shown.bs.modal.recurrenteRest',function(){
    if(temporizadorListadoRecurrentesRest)clearInterval(temporizadorListadoRecurrentesRest);
    temporizadorListadoRecurrentesRest=setInterval(function(){if($('#recurringBillModal').hasClass('show'))listarFacturasRecurrentesRest({silencioso:true});},20000);
  }).on('hidden.bs.modal.recurrenteRest',function(){if(temporizadorListadoRecurrentesRest){clearInterval(temporizadorListadoRecurrentesRest);temporizadorListadoRecurrentesRest=null;}});


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
    const danger = options.danger === true || /eliminar|anular|cancelar cuenta|borrar/i.test(String(title||''));
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

  function confirmarRegistroPagoRestaurante(contexto){
    return new Promise((resolve)=>{
      if(typeof swal === 'undefined'){
        showAlert('error','SweetAlert no disponible','No se puede registrar el pago sin confirmación.');
        resolve(false);
        return;
      }

      const total = obtenerTotalComanda();
      const numeroMesa = mesaSeleccionada && (mesaSeleccionada.numero || mesaSeleccionada.Numero);
      const destino = contexto && contexto.servicio === 'mesa'
        ? (numeroMesa ? 'Mesa ' + numeroMesa : 'la mesa seleccionada')
        : 'esta venta';

      confirmandoCobroMesa = true;

      swal({
        title:'Confirmar pago',
        text:`¿Desea registrar el pago de ${destino} por L ${formatNumber(total)}?`,
        icon:'warning',
        buttons:{
          cancel:{
            text:'Cancelar',
            value:false,
            visible:true,
            closeModal:true
          },
          confirm:{
            text:'Sí, registrar pago',
            value:true,
            visible:true,
            closeModal:true
          }
        },
        dangerMode:false,
        closeOnEsc:true,
        closeOnClickOutside:false
      }).then((ok)=>{
        confirmandoCobroMesa=false;
        resolve(ok === true);
      }).catch(()=>{
        confirmandoCobroMesa=false;
        resolve(false);
      });
    });
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
  window.REST_CONFIG = window.REST_CONFIG || {usar_mesas:1, usar_comandas:1, etiqueta_cocina:'Cocina', etiqueta_barra:'Barra', destino_comanda:'pantalla', momento_ticket:'enviar', flujo_cocina:'pasos', solicitar_clave_gestion:0, permitir_facturas_credito:0};
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

  function solicitarClaveGestionOperacion(){
    // La clave adicional SOLO aplica cuando el valor guardado es exactamente 1.
    return Number(window.REST_CONFIG && window.REST_CONFIG.solicitar_clave_gestion) === 1;
  }

  function autorizarGestionRestaurante(accion, callback, referencia){
    if(typeof callback!=='function') return;
    if(!solicitarClaveGestionOperacion()){
      callback();
      return;
    }
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

  // Configuración del módulo SIEMPRE requiere validación administrativa.
  // NO depende del switch "Solicitar clave para gestión".
  function autorizarConfiguracionRestaurante(callback){
    if(typeof callback!=='function') return;

    if(typeof window.validarAdminSistema!=='function'){
      showAlert(
        'error',
        'Validación administrativa',
        'No está disponible la validación administrativa del sistema.'
      );
      return;
    }

    window.validarAdminSistema(function(ok){
      if(ok) callback();
    },{
      modulo:'Restaurante',
      accion:'Configuración del módulo',
      referencia_id:'',
      referencia_texto:'Configuración del módulo',
      mensaje:'La configuración del módulo siempre requiere credenciales de administrador para continuar.'
    });
  }
  window.autorizarConfiguracionRestaurante=autorizarConfiguracionRestaurante;

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
          tipo_factura:tipoFacturaRestauranteActual(),
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

  function cuentaOperativamenteAbierta(cuenta){
    if (!cuenta || typeof cuenta !== 'object') return false;

    // Si el backend expone cualquier estado, debe indicar explícitamente abierto.
    const rcEstado = String(
      cuenta.cuenta_estado ??
      cuenta.estado_cuenta ??
      cuenta.contexto_estado ??
      ''
    ).toLowerCase().trim();

    if (rcEstado && !['abierta','abierto','1','activa','activo'].includes(rcEstado)) return false;

    const facturaEstadoRaw =
      cuenta.factura_estado ??
      cuenta.estado_factura ??
      cuenta.estado;

    if (facturaEstadoRaw !== undefined && facturaEstadoRaw !== null && facturaEstadoRaw !== '') {
      const fe = String(facturaEstadoRaw).toLowerCase().trim();
      // En IZZY la factura operativa abierta usa estado 1.
      if (!['1','abierta','abierto','activa','activo','borrador'].includes(fe)) return false;
    }

    return true;
  }

  function calcularTotalCuentaAbierta(cuenta){
    const items = Array.isArray(cuenta && cuenta.items) ? cuenta.items : [];
    if (!items.length) {
      // Compatibilidad: si el backend ya trae total final, úsalo.
      const totalBackend = Number(
        cuenta && (
          cuenta.total_con_isv ??
          cuenta.total_final ??
          cuenta.total ??
          cuenta.importe_total
        )
      );
      return Number.isFinite(totalBackend) && totalBackend > 0
        ? totalBackend
        : Number(cuenta && cuenta.importe || 0);
    }

    const r1 = Number(isvRates[1] || 0) / 100;
    const r2 = Number(isvRates[2] || 0) / 100;
    let subtotal = 0;
    let imp1 = 0;
    let imp2 = 0;

    items.forEach(function(item){
      const cantidad = Number(item.cantidad || 0);
      const precio = Number(item.precio || 0);
      const base = cantidad * precio;

      subtotal += base;
      if (Number(item.isv1 || 0) === 1) imp1 += base * r1;
      if (Number(item.isv2 || 0) === 1) imp2 += base * r2;
    });

    return subtotal + imp1 + imp2;
  }

  async function hidratarTotalesCuentasAbiertas(cuentas){
    const lista = Array.isArray(cuentas) ? cuentas : [];
    if (!lista.length) return [];

    const resultados = await Promise.allSettled(lista.map(async function(cuenta){
      const fid = Number(cuenta && cuenta.facturas_id || 0);
      if (!fid || !cuentaOperativamenteAbierta(cuenta)) return null;

      try{
        // Segunda validación contra el servidor.
        // Si ya fue pagada/cancelada/cerrada, loadCuentaAbierta debe rechazarla.
        const detalle = await restPost('loadCuentaAbierta',{factura_id:String(fid)});
        if (!detalle || !detalle.status || !detalle.cuenta) return null;

        const cuentaServidor = detalle.cuenta;
        if (!cuentaOperativamenteAbierta(cuentaServidor)) return null;

        cuenta.items = Array.isArray(cuentaServidor.items) ? cuentaServidor.items : [];
        cuenta.total_mostrar = calcularTotalCuentaAbierta(cuenta);
        return cuenta;
      }catch(_){
        // Ante duda no mostramos una cuenta que no pudimos confirmar abierta.
        return null;
      }
    }));

    return resultados
      .filter(function(r){ return r.status === 'fulfilled' && r.value; })
      .map(function(r){ return r.value; });
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
      const cuentasBase=Array.isArray(d.cuentas)?d.cuentas:[];
      const cuentas=await hidratarTotalesCuentasAbiertas(cuentasBase);
      window.__REST_CUENTAS_ABIERTAS=cuentas;
      renderCuentasAbiertas(cuentas);
    }catch(e){ list.innerHTML=`<div class="rs-empty-state"><i class="fas fa-exclamation-circle"></i><span>${escapeHtml(e.message||'Error')}</span></div>`; }
  }

  function renderCuentasAbiertas(cuentas,term=''){
    const list=document.getElementById('cuentas-abiertas-listado'); if(!list) return;
    const t=String(term||'').toLowerCase().trim();
    const arr=(cuentas||[])
      .filter(c=>cuentaOperativamenteAbierta(c))
      .filter(c=>!t || [c.facturas_id,c.cliente_nombre,c.cliente_rtn,c.mesa_numero,c.servicio_tipo].join(' ').toLowerCase().includes(t));
    if(!arr.length){ list.innerHTML='<div class="rs-empty-state"><i class="fas fa-folder-open"></i><span>No hay cuentas abiertas.</span></div>'; return; }
    list.innerHTML=arr.map(c=>{
      const esAnterior=Number(c.es_anterior||0)===1;
      const ubicacion=c.servicio_tipo==='mesa'
        ? (esAnterior?`Cuenta anterior · Mesa ${escapeHtml(c.mesa_numero||'')} (mesa liberada)`:'Mesa '+escapeHtml(c.mesa_numero||''))
        :'Para llevar / venta directa';
      const rtn=String(c.cliente_rtn||'').trim();
      const enviadas=Number(c.enviadas_preparacion||0);
      const unidades=Number(c.unidades||0);
      const estadoPrep=usaComandasOperacion()
        ? `<small class="rs-open-account-prep"><i class="fas fa-fire"></i> ${enviadas>0?`${enviadas} unidad(es) ya enviadas a preparación`:'Aún no enviada a preparación'}</small>`
        : '';
      const fid=Number(c.facturas_id);
      return `<div class="rs-open-account-card${esAnterior?' rs-open-account-card--previous':''}" data-fid="${fid}">
        <button type="button" class="rs-open-account-load" data-fid="${fid}" title="Abrir cuenta #${fid}">
          <span class="rs-open-account-icon"><i class="fas ${c.servicio_tipo==='mesa'?'fa-chair':'fa-shopping-bag'}"></i></span>
          <span class="rs-open-account-main">
            <strong>${escapeHtml(c.cliente_nombre||'Consumidor Final')}</strong>
            ${rtn?`<small class="rs-open-account-rtn"><i class="fas fa-id-card"></i> RTN ${escapeHtml(rtn)}</small>`:''}
            <small><i class="fas fa-map-marker-alt"></i> ${ubicacion} · ${unidades} unidad(es)</small>
            ${estadoPrep}
          </span>
          <span class="rs-open-account-total"><small>Cuenta #${fid}</small><strong>L ${fmtHNL(Number(c.total_mostrar ?? c.importe ?? 0))}</strong></span>
          <span class="rs-open-account-open"><i class="fas fa-arrow-right"></i></span>
        </button>
        <button type="button" class="rs-open-account-close" data-fid="${fid}" title="Cerrar esta cuenta sin abrirla">
          <i class="fas fa-times-circle"></i><span>Cerrar cuenta</span>
        </button>
      </div>`;
    }).join('');
  }

  async function cerrarCuentaDesdeListado(facturaId){
    const fid=Number(facturaId||0);
    if(!fid) return;
    showConfirm(
      'Cerrar cuenta',
      `¿Desea cerrar la cuenta #${fid} sin abrirla? La cuenta quedará cancelada, desaparecerá de Cuentas abiertas y cualquier mesa asociada quedará disponible.`,
      async ()=>{
        const btn=document.querySelector(`.rs-open-account-close[data-fid="${fid}"]`);
        setButtonBusy(btn,true,'Cerrando…');
        try{
          const d=await restPost('closeFactura',{factura_id:String(fid)});
          if(!d||!d.status) throw new Error(d&&d.message?d.message:'No se pudo cerrar la cuenta');

          // Si por alguna razón la misma cuenta estaba cargada en pantalla,
          // limpiamos el contexto local para no dejar una factura cancelada editable.
          if(Number(facturaIdActual()||0)===fid){
            facturaActual=null;
            comandaItems=[];
            actualizarComandaUI();
            updateProductBadges();
            setServicioTipo('llevar');
            setMesaSeleccionadaUI(null);
            if(facturaTitle) facturaTitle.innerHTML=usaComandasOperacion()?'<i class="fas fa-receipt"></i> Nueva Comanda':'<i class="fas fa-cash-register"></i> Nueva venta';
            updateAccionPrincipalUI();
          }

          showAlert('success','Cuenta cerrada',`La cuenta #${fid} fue cerrada correctamente.`);
          await cargarMesas();
          await cargarCuentasAbiertasUI();
          const buscador=document.getElementById('buscar-cuenta-abierta');
          if(buscador && buscador.value) renderCuentasAbiertas(window.__REST_CUENTAS_ABIERTAS||[],buscador.value);
        }catch(e){
          showAlert('error','Error',e.message||'No se pudo cerrar la cuenta');
        }finally{
          setButtonBusy(btn,false);
        }
      },
      {danger:true}
    );
  }

  async function abrirCuentaAbierta(facturaId){
    // Recuperar una cuenta explícitamente también invalida cualquier carga de mesa previa.
    const secuenciaCuenta = ++cargaFacturaMesaSecuencia;
    try{
      const d=await restPost('loadCuentaAbierta',{factura_id:String(facturaId)});
      if(secuenciaCuenta !== cargaFacturaMesaSecuencia) return;
      if(!d||!d.status||!d.cuenta || !cuentaOperativamenteAbierta(d.cuenta)) {
        window.__REST_CUENTAS_ABIERTAS = (window.__REST_CUENTAS_ABIERTAS || [])
          .filter(c => Number(c.facturas_id) !== Number(facturaId));
        renderCuentasAbiertas(window.__REST_CUENTAS_ABIERTAS || []);
        throw new Error('Esta cuenta ya fue cerrada, pagada o cancelada y no puede volver a cargarse.');
      }
      const c=d.cuenta;
      facturaActual={id:Number(c.facturas_id),factura_id:Number(c.facturas_id),facturas_id:Number(c.facturas_id),notas:c.notas||''};
      setTipoFacturaRestaurante(Number(c.tipo_factura)===2?'credito':'contado',{silencioso:true});
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
        flujo_cocina:String(d.config.flujo_cocina||'pasos'),
        solicitar_clave_gestion:Number(d.config.solicitar_clave_gestion)!==0?1:0,
        permitir_facturas_credito:Number(d.config.permitir_facturas_credito)===1?1:0
      };
    }catch(_){
      const anterior=window.REST_CONFIG||{};
      window.REST_CONFIG={
        usar_mesas:Number(anterior.usar_mesas)!==0?1:0,
        usar_comandas:Number(anterior.usar_comandas)!==0?1:0,
        etiqueta_cocina:String(anterior.etiqueta_cocina||'Cocina'),
        etiqueta_barra:String(anterior.etiqueta_barra||'Barra'),
        destino_comanda:String(anterior.destino_comanda||'pantalla'),
        momento_ticket:String(anterior.momento_ticket||'enviar'),
        flujo_cocina:String(anterior.flujo_cocina||'pasos'),
        solicitar_clave_gestion:Number(anterior.solicitar_clave_gestion)===1?1:0,
        permitir_facturas_credito:Number(anterior.permitir_facturas_credito)===1?1:0
      };
    }
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
    const claveGestion=document.getElementById('config-solicitar-clave-gestion');
    if(claveGestion) claveGestion.checked=solicitarClaveGestionOperacion();
    const permitirCredito=document.getElementById('config-permitir-facturas-credito');
    if(permitirCredito) permitirCredito.checked=permiteCreditoOperacion();
    const creditoEstado=document.getElementById('config-credito-estado');
    if(creditoEstado){
      creditoEstado.innerHTML=permiteCreditoOperacion()
        ? '<i class="fas fa-credit-card"></i> Contado + Crédito'
        : '<i class="fas fa-money-bill-wave"></i> Solo contado';
      creditoEstado.classList.toggle('is-off',!permiteCreditoOperacion());
    }
    sincronizarTipoFacturaRestauranteUI();
    const seguridadEstado=document.getElementById('config-seguridad-estado');
    if(seguridadEstado){
      seguridadEstado.innerHTML=solicitarClaveGestionOperacion()
        ? '<i class="fas fa-shield-halved"></i> Protección activa'
        : '<i class="fas fa-unlock"></i> Sin clave adicional';
      seguridadEstado.classList.toggle('is-off',!solicitarClaveGestionOperacion());
    }
    sincronizarTodosBotonesConfiguracion();
    aplicarEtiquetasOperacion();
    renderizarCategorias();
    filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');
    updateAccionPrincipalUI();
    if (isMobileAssistantActive()) rsMobileUpdate();
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

  $(document)
    .off('click.restTipoFactura','[data-tipo-factura],[data-rs-tipo-factura]')
    .on('click.restTipoFactura','[data-tipo-factura],[data-rs-tipo-factura]',function(e){
      e.preventDefault();
      const tipo = String(this.dataset.tipoFactura || this.dataset.rsTipoFactura || 'contado');
      setTipoFacturaRestaurante(tipo);
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
        flujo_cocina:String((document.getElementById('config-flujo-cocina')||{}).value||'pasos'),
        solicitar_clave_gestion:(document.getElementById('config-solicitar-clave-gestion')||{}).checked?'1':'0',
        permitir_facturas_credito:(document.getElementById('config-permitir-facturas-credito')||{}).checked?'1':'0'
      });
      if(!d||!d.status) throw new Error(d&&d.message?d.message:'No se pudo guardar');
      window.REST_CONFIG=d.config||window.REST_CONFIG; aplicarConfiguracionOperacion();
      rsConfigEstadoBase=rsConfigCapturarEstado();
      rsConfigActualizarEstadoCambios();
      // Guardar configuración NO cierra el Centro de configuración.
      // El usuario decide cuándo salir con X, Cerrar/Cancelar o ESC.
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
  $(document).off('click.restOpenAccount','.rs-open-account-load').on('click.restOpenAccount','.rs-open-account-load',function(e){e.preventDefault();e.stopPropagation();abrirCuentaAbierta(Number($(this).data('fid')));});
  $(document).off('click.restCloseAccount','.rs-open-account-close').on('click.restCloseAccount','.rs-open-account-close',function(e){e.preventDefault();e.stopPropagation();cerrarCuentaDesdeListado(Number($(this).data('fid')));});
  $('#btn-imprimir').off('click.restTicketOpen').on('click.restTicketOpen',function(e){e.preventDefault();e.stopPropagation();abrirTicketComanda();});
    $('#btn-imprimir-ticket-comanda').off('click.restTicket').on('click.restTicket',imprimirTicketComanda);
  $('#btn-configuracion-restaurante').off('click.restCfg').on('click.restCfg',function(e){
    e.preventDefault();e.stopPropagation();

    // SIEMPRE autenticación administrativa para entrar a Configuración.
    autorizarConfiguracionRestaurante(()=>{
      aplicarConfiguracionOperacion();
      const modalConfig = document.getElementById('modal-configuracion-restaurante');
      if(modalConfig) modalConfig.style.display='block';
      rsConfigPrepararCentro();
      setTimeout(reinitSelect2Restaurante,80);
    });
  });
  $('#btn-guardar-configuracion-restaurante').off('click.restCfgSave').on('click.restCfgSave',guardarConfiguracionOperacionUI);

  // ===========================================================
  // CENTRO DE CONFIGURACIÓN — NAVEGACIÓN / BÚSQUEDA
  // La vista usa paneles con .is-active. Este bloque es el único
  // responsable de cambiar entre General / Operación / Facturación /
  // Cocina / Dispositivos / Seguridad sin alterar el layout existente.
  // ===========================================================
  let rsConfigTabActual = 'general';

  function rsConfigNormalizarTexto(valor){
    return String(valor || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g,'')
      .toLowerCase()
      .trim();
  }

  function rsConfigComandasActivas(){
    const control=document.getElementById('config-usar-comandas');
    if(control) return !!control.checked;
    return !(window.REST_CONFIG && Number(window.REST_CONFIG.usar_comandas)===0);
  }

  function rsConfigActualizarDependencias(){
    const comandas=rsConfigComandasActivas();
    const aviso=document.getElementById('config-dependencias-aviso');

    document.querySelectorAll('[data-config-requires-comandas-nav="1"]').forEach(el=>{
      el.disabled=!comandas;
      el.classList.toggle('is-disabled',!comandas);
      el.setAttribute('aria-disabled',!comandas?'true':'false');
      el.title=!comandas?'Active “Usar comandas” para habilitar esta categoría.':'';
    });

    document.querySelectorAll('[data-config-requires-comandas="1"]').forEach(el=>{
      el.style.display=comandas?'':'none';
    });

    if(!comandas && (rsConfigTabActual==='cocina' || rsConfigTabActual==='dispositivos')){
      rsConfigActivarTab('general',{silencioso:true});
      if(aviso){
        aviso.innerHTML='<i class="fas fa-circle-info"></i><span>Active <b>Usar comandas</b> para configurar Cocina y dispositivos vinculados.</span>';
        aviso.style.display='flex';
      }
    }else if(aviso){
      aviso.style.display='none';
    }
  }

  function rsConfigActivarTab(tab,opciones={}){
    const modal=document.getElementById('modal-configuracion-restaurante');
    if(!modal) return false;

    const nombre=String(tab||'general');
    const nav=modal.querySelector(`.rs-settings-nav-item[data-config-tab="${nombre}"]`);
    const panel=modal.querySelector(`.rs-settings-panel[data-config-panel="${nombre}"]`);
    if(!nav || !panel) return false;

    if(nav.disabled || nav.getAttribute('aria-disabled')==='true'){
      if(!opciones.silencioso && typeof showNotify==='function'){
        showNotify('info','Configuración','Active “Usar comandas” para acceder a esta categoría.');
      }
      return false;
    }

    rsConfigTabActual=nombre;
    modal.classList.remove('rs-settings-searching');

    const search=document.getElementById('config-buscar-ajuste');
    const clear=document.getElementById('config-limpiar-busqueda');
    const empty=document.getElementById('config-sin-resultados');
    if(search && !opciones.conservarBusqueda) search.value='';
    if(clear && !opciones.conservarBusqueda) clear.style.display='none';
    if(empty) empty.style.display='none';

    modal.querySelectorAll('.rs-settings-nav-item[data-config-tab]').forEach(btn=>{
      const activo=btn===nav;
      btn.classList.toggle('is-active',activo);
      btn.setAttribute('aria-current',activo?'page':'false');
    });

    modal.querySelectorAll('.rs-settings-panel[data-config-panel]').forEach(sec=>{
      const activo=sec===panel;
      sec.classList.toggle('is-active',activo);
      sec.classList.remove('has-search-results');
    });

    const content=document.getElementById('config-centro-contenido');
    if(content) content.scrollTop=0;
    return true;
  }

  function rsConfigBuscar(valor){
    const modal=document.getElementById('modal-configuracion-restaurante');
    if(!modal) return;

    const query=rsConfigNormalizarTexto(valor);
    const clear=document.getElementById('config-limpiar-busqueda');
    const empty=document.getElementById('config-sin-resultados');
    if(clear) clear.style.display=query?'inline-flex':'none';

    if(!query){
      modal.classList.remove('rs-settings-searching');
      modal.querySelectorAll('.rs-settings-item[data-config-search]').forEach(item=>item.style.display='');
      modal.querySelectorAll('.rs-settings-panel').forEach(panel=>panel.classList.remove('has-search-results'));
      if(empty) empty.style.display='none';
      rsConfigActivarTab(rsConfigTabActual,{silencioso:true,conservarBusqueda:true});
      rsConfigActualizarDependencias();
      return;
    }

    modal.classList.add('rs-settings-searching');
    let total=0;

    modal.querySelectorAll('.rs-settings-panel[data-config-panel]').forEach(panel=>{
      let resultados=0;
      panel.querySelectorAll('.rs-settings-item[data-config-search]').forEach(item=>{
        const requiere=item.getAttribute('data-config-requires-comandas')==='1';
        const permitido=!requiere || rsConfigComandasActivas();
        const texto=rsConfigNormalizarTexto(item.getAttribute('data-config-search')+' '+item.textContent);
        const match=permitido && texto.includes(query);
        item.style.display=match?'':'none';
        if(match) resultados++;
      });
      panel.classList.toggle('has-search-results',resultados>0);
      total+=resultados;
    });

    if(empty) empty.style.display=total===0?'flex':'none';
  }

  function rsConfigCapturarEstado(){
    const ids=[
      'config-usar-mesas','config-usar-comandas','config-etiqueta-cocina','config-etiqueta-barra',
      'config-destino-comanda','config-momento-ticket','config-flujo-cocina',
      'config-solicitar-clave-gestion','config-permitir-facturas-credito'
    ];
    const estado={};
    ids.forEach(id=>{
      const el=document.getElementById(id);
      if(!el) return;
      estado[id]=(el.type==='checkbox')?!!el.checked:String(el.value||'');
    });
    return estado;
  }

  let rsConfigEstadoBase=null;

  function rsConfigActualizarEstadoCambios(){
    const badge=document.getElementById('config-cambios-pendientes');
    const footer=document.getElementById('config-footer-estado');
    if(!badge || !rsConfigEstadoBase) return;
    const actual=JSON.stringify(rsConfigCapturarEstado());
    const base=JSON.stringify(rsConfigEstadoBase);
    const dirty=actual!==base;
    badge.classList.toggle('is-clean',!dirty);
    badge.classList.toggle('is-dirty',dirty);
    badge.innerHTML=dirty
      ? '<i class="fas fa-circle-exclamation"></i> Cambios pendientes'
      : '<i class="fas fa-circle-check"></i> Sin cambios pendientes';
    if(footer){
      footer.innerHTML=dirty
        ? '<i class="fas fa-circle-exclamation"></i> Hay cambios sin guardar.'
        : '<i class="fas fa-circle-info"></i> Los cambios de categorías se guardan juntos.';
    }
  }

  function rsConfigPrepararCentro(){
    rsConfigActualizarDependencias();
    rsConfigActivarTab('general',{silencioso:true});
    rsConfigEstadoBase=rsConfigCapturarEstado();
    rsConfigActualizarEstadoCambios();
  }

  $(document)
    .off('click.restConfigTabs','.rs-settings-nav-item[data-config-tab]')
    .on('click.restConfigTabs','.rs-settings-nav-item[data-config-tab]',function(e){
      e.preventDefault();
      e.stopPropagation();
      rsConfigActivarTab(this.dataset.configTab);
    });

  $('#config-buscar-ajuste')
    .off('input.restConfigSearch')
    .on('input.restConfigSearch',function(){rsConfigBuscar(this.value);});

  $('#config-limpiar-busqueda')
    .off('click.restConfigSearch')
    .on('click.restConfigSearch',function(e){
      e.preventDefault();
      const search=document.getElementById('config-buscar-ajuste');
      if(search){search.value='';search.focus();}
      rsConfigBuscar('');
    });

  $(document)
    .off('change.restConfigDirty input.restConfigDirty','#modal-configuracion-restaurante input,#modal-configuracion-restaurante select')
    .on('change.restConfigDirty input.restConfigDirty','#modal-configuracion-restaurante input,#modal-configuracion-restaurante select',function(){
      rsConfigActualizarDependencias();
      rsConfigActualizarEstadoCambios();
    });

  $('#config-usar-comandas').off('change.restCfgComandas').on('change.restCfgComandas',function(){
    const wrap=document.getElementById('config-grupos-operacion');
    const salida=document.getElementById('config-salida-comanda');
    if(wrap) wrap.style.display=this.checked?'':'none';
    if(salida) salida.style.display=this.checked?'':'none';
  });

  $('#config-solicitar-clave-gestion').off('change.restCfgClave').on('change.restCfgClave',function(){
    const estado=document.getElementById('config-seguridad-estado');
    if(estado){
      estado.innerHTML=this.checked
        ? '<i class="fas fa-shield-halved"></i> Protección activa'
        : '<i class="fas fa-unlock"></i> Sin clave adicional';
      estado.classList.toggle('is-off',!this.checked);
    }
  });

  $('#config-permitir-facturas-credito').off('change.restCfgCredito').on('change.restCfgCredito',function(){
    const estado=document.getElementById('config-credito-estado');
    if(estado){
      estado.innerHTML=this.checked
        ? '<i class="fas fa-credit-card"></i> Contado + Crédito'
        : '<i class="fas fa-money-bill-wave"></i> Solo contado';
      estado.classList.toggle('is-off',!this.checked);
    }
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

  setTimeout(async function(){
    await Promise.allSettled([
      cargarConfiguracionOperacion(),
      cargarPermisosRestaurante()
    ]);
    reinitSelect2Restaurante();
    updateAccionPrincipalUI();

    if (rsEsTelefono()) {
      initMobileAssistant();
    }

    rsMobileInitReady = true;
    intentarFinalizarCargaInicial();
  },120);

});

/* ================================================================
   FIX — CENTRO DE CONFIGURACIÓN / PANTALLA DE COCINA
   La vista ya contiene los controles de Cocina/Dispositivos, pero
   esta integración no existía en el JS principal. Sin ella el bloque
   #config-dispositivos-cocina queda permanentemente en "Cargando…".
   Este bloque es autocontenido para no alterar el flujo POS existente.
   ================================================================ */
(function(){
  'use strict';

  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, {once:true});
    else fn();
  }

  ready(function(){
    const devicesBox = document.getElementById('config-dispositivos-cocina');
    if(!devicesBox) return;

    const base = String(window.SERVERURL || (typeof SERVERURL !== 'undefined' ? SERVERURL : '/') || '/').replace(/\/?$/, '/');
    const endpoint = base + 'core/cocina/cocinaAccesoAdminAjax.php';
    const adminContext = String(window.REST_COCINA_ADMIN_CONTEXT || '');
    const REQUEST_TIMEOUT = 15000;
    let loadingDevices = false;
    let lastLoadAt = 0;
    let pairingDeviceInProgress = false;

    // Equipos ocultos SOLO EN ESTA VISTA/NAVEGADOR.
    // No se elimina ni modifica ningún registro en la base de datos.
    const HIDDEN_DEVICES_KEY = 'izzy_restaurante_cocina_dispositivos_ocultos';

    function getHiddenDevices(){
      try{
        const raw = JSON.parse(localStorage.getItem(HIDDEN_DEVICES_KEY) || '[]');
        return new Set((Array.isArray(raw) ? raw : []).map(v => String(v)));
      }catch(_){
        return new Set();
      }
    }

    function saveHiddenDevices(set){
      try{ localStorage.setItem(HIDDEN_DEVICES_KEY, JSON.stringify(Array.from(set))); }catch(_){}
    }

    function hideDeviceFromView(id){
      const hidden = getHiddenDevices();
      hidden.add(String(id));
      saveHiddenDevices(hidden);
    }

    function restoreHiddenDevices(){
      try{ localStorage.removeItem(HIDDEN_DEVICES_KEY); }catch(_){}
    }

    function esc(v){
      return String(v == null ? '' : v)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    }

    function notify(type,title,message){
      if(typeof window.showNotify === 'function'){
        window.showNotify(type,title,message);
        return;
      }
      if(typeof window.swal === 'function'){
        window.swal(title || '', message || '', type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'));
        return;
      }
      if(message) console[type === 'error' ? 'error' : 'log']('[Restaurante/Cocina]', message);
    }

    function setBusy(btn,busy,text){
      if(!btn) return;
      if(busy){
        if(!btn.dataset.rsKitchenHtml) btn.dataset.rsKitchenHtml = btn.innerHTML;
        btn.disabled = true;
        btn.setAttribute('aria-busy','true');
        if(text) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + esc(text);
      }else{
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
        if(btn.dataset.rsKitchenHtml){
          btn.innerHTML = btn.dataset.rsKitchenHtml;
          delete btn.dataset.rsKitchenHtml;
        }
      }
    }

    function responseOk(data){
      if(!data || typeof data !== 'object') return false;
      if(Object.prototype.hasOwnProperty.call(data,'status')) return !!data.status;
      if(Object.prototype.hasOwnProperty.call(data,'success')) return !!data.success;
      if(Object.prototype.hasOwnProperty.call(data,'ok')) return !!data.ok;
      return true;
    }

    function messageOf(data, fallback){
      return String((data && (data.message || data.mensaje || data.error)) || fallback || 'No se pudo completar la operación.');
    }

    function actionUnsupported(data){
      const m = messageOf(data,'').toLowerCase();
      return /acci[oó]n|action/.test(m) && /no (?:especificada|v[aá]lida|permitida|soportada|existe|encontrada)|desconocida|inv[aá]lida/.test(m);
    }

    async function postOnce(action,data){
      const payload = Object.assign({}, data || {});
      payload.action = action;
      // Se envían aliases del contexto para mantener compatibilidad con
      // versiones previas del endpoint sin tocar el backend existente.
      payload.admin_context = adminContext;
      payload.contexto_admin = adminContext;
      payload.contexto = adminContext;
      payload.context = adminContext;

      const body = new URLSearchParams();
      Object.keys(payload).forEach(function(k){
        const v = payload[k];
        if(v === undefined || v === null) return;
        if(Array.isArray(v)) v.forEach(function(x){ body.append(k + '[]', String(x)); });
        else body.append(k, String(v));
      });

      const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
      const timer = controller ? setTimeout(function(){ controller.abort(); }, REQUEST_TIMEOUT) : null;
      try{
        const res = await fetch(endpoint, {
          method:'POST',
          credentials:'same-origin',
          headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-Requested-With':'XMLHttpRequest'},
          body:body.toString(),
          signal:controller ? controller.signal : undefined
        });
        if(res.status === 401){
          let auth = null;
          try{ auth = await res.clone().json(); }catch(_){}
          if(auth && auth.redirect) window.location.href = auth.redirect;
          throw new Error(messageOf(auth,'La sesión expiró.'));
        }
        const raw = await res.text();
        let json = null;
        try{ json = raw ? JSON.parse(raw) : {}; }
        catch(_){ throw new Error('La respuesta de Cocina no es JSON válido.'); }
        if(!res.ok) throw new Error(messageOf(json,'Error HTTP ' + res.status));
        return json;
      }catch(e){
        if(e && e.name === 'AbortError') throw new Error('La consulta de pantallas tardó demasiado tiempo.');
        throw e;
      }finally{
        if(timer) clearTimeout(timer);
      }
    }

    async function postCompat(actions,data){
      let last = null;
      for(let i=0;i<actions.length;i++){
        const d = await postOnce(actions[i],data);
        last = d;
        if(responseOk(d) || !actionUnsupported(d)) return d;
      }
      return last;
    }

    function arrayFrom(data,keys){
      if(Array.isArray(data)) return data;
      if(!data || typeof data !== 'object') return [];
      for(const k of keys){ if(Array.isArray(data[k])) return data[k]; }
      if(data.data && typeof data.data === 'object'){
        for(const k of keys){ if(Array.isArray(data.data[k])) return data.data[k]; }
      }
      return [];
    }

    function first(obj,keys,def){
      if(!obj || typeof obj !== 'object') return def;
      for(const k of keys){
        if(Object.prototype.hasOwnProperty.call(obj,k) && obj[k] !== null && obj[k] !== '') return obj[k];
      }
      return def;
    }

    function boolish(v,def){
      if(v === undefined || v === null || v === '') return !!def;
      if(typeof v === 'boolean') return v;
      const s = String(v).toLowerCase();
      return !['0','false','no','off','inactivo','revocado','disabled'].includes(s);
    }

    function formatDate(v){
      if(!v) return 'Sin conexión registrada';
      const raw = String(v);
      const d = new Date(raw.replace(' ','T'));
      if(Number.isNaN(d.getTime())) return raw;
      try{
        return d.toLocaleString('es-HN',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});
      }catch(_){ return raw; }
    }

    function deviceState(dev){
      const active = boolish(first(dev,['activo','active','estado_activo','habilitado'],1),true);
      if(!active) return {cls:'is-off',label:'Desvinculada',active:false};
      const last = first(dev,['ultima_conexion','ultimo_acceso','last_seen','last_connection','fecha_ultimo_acceso','fecha_actualizacion'],null);
      if(!last) return {cls:'is-idle',label:'Vinculada',active:true};
      const d = new Date(String(last).replace(' ','T'));
      if(!Number.isNaN(d.getTime()) && (Date.now()-d.getTime()) <= 5*60*1000) return {cls:'is-online',label:'En línea',active:true};
      return {cls:'is-idle',label:'Vinculada',active:true};
    }

    function renderDevices(list){
      if(!Array.isArray(list) || list.length === 0){
        devicesBox.innerHTML = '<div class="rs-kitchen-device-empty"><i class="fas fa-display"></i> No hay pantallas vinculadas todavía.</div>';
        return;
      }

      const hidden = getHiddenDevices();
      const visibles = [];
      let ocultos = 0;

      list.forEach(function(dev,index){
        const id = first(dev,['dispositivo_id','device_id','id','cocina_dispositivo_id','token_id'],index+1);
        const st = deviceState(dev);

        // Un equipo activo jamás se oculta. Solo se puede ocultar si ya está desvinculado.
        if(!st.active && hidden.has(String(id))){
          ocultos++;
          return;
        }
        visibles.push({dev,index,id,st});
      });

      let html = '';

      if(ocultos > 0){
        html += '<div class="rs-kitchen-hidden-toolbar" style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 10px 0;padding:8px 10px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc;">' +
          '<small style="color:#64748b;"><i class="fas fa-eye-slash"></i> '+ocultos+' dispositivo'+(ocultos===1?' oculto':'s ocultos')+'</small>' +
          '<button type="button" class="btn btn-light btn-sm rs-kitchen-restore-hidden"><i class="fas fa-eye"></i> Mostrar ocultos</button>' +
        '</div>';
      }

      if(visibles.length === 0){
        html += '<div class="rs-kitchen-device-empty"><i class="fas fa-display"></i> No hay pantallas visibles en esta lista.</div>';
        devicesBox.innerHTML = html;
        return;
      }

      html += visibles.map(function(row){
        const dev = row.dev;
        const index = row.index;
        const id = row.id;
        const st = row.st;
        const name = first(dev,['nombre','nombre_dispositivo','device_name','alias'], 'Pantalla ' + (index+1));
        const last = first(dev,['ultima_conexion','ultimo_acceso','last_seen','last_connection','fecha_ultimo_acceso','fecha_actualizacion'],null);
        const agent = first(dev,['user_agent','navegador','device_info','descripcion'],'');

        return '<div class="rs-kitchen-device-card '+(st.cls==='is-off'?'is-off':'')+'" data-kitchen-device-id="'+esc(id)+'">' +
          '<div class="rs-kitchen-device-icon"><i class="fas fa-display"></i></div>' +
          '<div class="rs-kitchen-device-info">' +
            '<div class="rs-kitchen-device-title"><strong>'+esc(name)+'</strong><span class="rs-kitchen-device-status '+esc(st.cls)+'"><i class="fas fa-circle"></i> '+esc(st.label)+'</span></div>' +
            '<small><i class="fas fa-clock"></i> '+esc(formatDate(last))+'</small>' +
            (agent ? '<small title="'+esc(agent)+'"><i class="fas fa-globe"></i> '+esc(agent)+'</small>' : '') +
          '</div>' +
          (st.active
            ? '<div class="rs-kitchen-device-actions"><button type="button" class="btn btn-danger btn-sm rs-kitchen-unlink" data-device-id="'+esc(id)+'"><i class="fas fa-unlink"></i> Desvincular</button></div>'
            : '<div class="rs-kitchen-device-actions"><button type="button" class="btn btn-light btn-sm rs-kitchen-hide-device" data-device-id="'+esc(id)+'" title="Ocultar solo de esta vista; no borra el registro"><i class="fas fa-eye-slash"></i> Ocultar</button></div>') +
        '</div>';
      }).join('');

      devicesBox.innerHTML = html;
    }

    async function loadDevices(force){
      if(loadingDevices) return;
      if(!force && Date.now()-lastLoadAt < 1500) return;
      loadingDevices = true;
      devicesBox.setAttribute('aria-busy','true');
      devicesBox.innerHTML = '<div class="rs-kitchen-device-empty"><i class="fas fa-spinner fa-spin"></i> Cargando pantallas…</div>';
      try{
        if(!adminContext) throw new Error('No se pudo generar el contexto administrativo de Cocina. Recargue la página e intente de nuevo.');
        const d = await postCompat(['listar','listar_dispositivos','listarDispositivos','dispositivos','listDevices'],{});
        if(!responseOk(d)) throw new Error(messageOf(d,'No se pudieron cargar las pantallas vinculadas.'));
        renderDevices(arrayFrom(d,['dispositivos','devices','pantallas','items']));
        lastLoadAt = Date.now();
      }catch(e){
        devicesBox.innerHTML = '<div class="rs-kitchen-device-empty is-error"><i class="fas fa-triangle-exclamation"></i> '+esc(e.message || 'No se pudieron cargar las pantallas.')+'</div>';
        console.error('[Restaurante][Cocina] cargar dispositivos:',e);
      }finally{
        loadingDevices = false;
        devicesBox.removeAttribute('aria-busy');
      }
    }

    function setKitchenStatus(active){
      const sw = document.getElementById('config-pantalla-cocina-activa');
      const badge = document.getElementById('config-cocina-estado');
      if(sw) sw.checked = !!active;
      if(badge){
        badge.classList.toggle('is-off',!active);
        badge.innerHTML = active ? '<i class="fas fa-circle"></i> Activa' : '<i class="fas fa-circle"></i> Inactiva';
      }
    }

    async function loadKitchenState(){
      try{
        if(!adminContext) return;
        const d = await postCompat(['load'],{});
        if(!responseOk(d)) return;
        const source = d.config || d.data || d;
        const active = boolish(first(source,['pantalla_cocina_activa','activo','active','habilitado'],false),false);
        setKitchenStatus(active);
      }catch(e){ console.warn('[Restaurante][Cocina] estado:',e.message); }
    }

    async function pairDevice(btn){
      if(pairingDeviceInProgress) return;

      const input = document.getElementById('config-codigo-vinculacion-cocina');
      const nameInput = document.getElementById('config-nombre-dispositivo-cocina');
      const code = String(input ? input.value : '').replace(/\D/g,'').slice(0,6);
      const name = String(nameInput ? nameInput.value : '').trim();

      if(code.length !== 6){
        notify('info','Vincular TV','Escriba los 6 dígitos que muestra la pantalla.');
        if(input) input.focus();
        return;
      }

      pairingDeviceInProgress = true;
      setBusy(btn,true,'Vinculando…');

      try{
        // cocinaAccesoAdminAjax.php acepta exactamente "vincularDispositivo"
        // y recibe el código en "codigo". No enviamos acciones alternativas
        // que puedan generar 403 ni modificamos el código mostrado por la TV.
        const d = await postOnce('vincularDispositivo',{
          codigo:code,
          nombre:name
        });

        if(!responseOk(d)) throw new Error(messageOf(d,'No se pudo vincular la pantalla.'));

        if(input) input.value='';
        if(nameInput) nameInput.value='';

        notify('success','Pantalla de Cocina',messageOf(d,'Pantalla vinculada correctamente.'));
        renderDevices(arrayFrom(d,['dispositivos','devices','pantallas','items']));
        lastLoadAt = Date.now();
      }catch(e){
        notify('error','Pantalla de Cocina',e.message || 'No se pudo vincular la pantalla.');
      }finally{
        pairingDeviceInProgress = false;
        setBusy(btn,false);
      }
    }

    async function unlinkDevice(btn,id){
      if(!id) return;
      const run = async function(){
        setBusy(btn,true,'Desvinculando…');
        try{
          const d = await postCompat(['desvincularDispositivo'],{
            dispositivo_id:id, device_id:id, id:id
          });
          if(!responseOk(d)) throw new Error(messageOf(d,'No se pudo desvincular la pantalla.'));
          notify('success','Pantalla de Cocina',messageOf(d,'Pantalla desvinculada.'));
          await loadDevices(true);
        }catch(e){ notify('error','Pantalla de Cocina',e.message || 'No se pudo desvincular la pantalla.'); }
        finally{ setBusy(btn,false); }
      };
      if(typeof window.swal === 'function'){
        window.swal({title:'Desvincular pantalla',text:'Este equipo perderá el acceso a Cocina. Las demás pantallas no se verán afectadas.',icon:'warning',buttons:['Cancelar','Desvincular'],dangerMode:true}).then(function(ok){ if(ok) run(); });
      }else if(window.confirm('¿Desvincular esta pantalla de Cocina?')) run();
    }

    async function testScreens(btn){
      setBusy(btn,true,'Enviando…');
      try{
        const d = await postCompat(['enviarPrueba'],{});
        if(!responseOk(d)) throw new Error(messageOf(d,'No se pudo enviar la prueba.'));
        notify('success','Pantalla de Cocina',messageOf(d,'Prueba enviada a las pantallas vinculadas.'));
      }catch(e){ notify('error','Pantalla de Cocina',e.message || 'No se pudo enviar la prueba.'); }
      finally{ setBusy(btn,false); }
    }

    async function regenerateAccess(btn){
      const run = async function(){
        setBusy(btn,true,'Regenerando…');
        try{
          const d = await postCompat(['regenerar'],{});
          if(!responseOk(d)) throw new Error(messageOf(d,'No se pudo regenerar el acceso.'));
          notify('success','Pantalla de Cocina',messageOf(d,'Acceso regenerado. Las pantallas deberán vincularse nuevamente.'));
          await Promise.allSettled([loadDevices(true),loadKitchenState()]);
        }catch(e){ notify('error','Pantalla de Cocina',e.message || 'No se pudo regenerar el acceso.'); }
        finally{ setBusy(btn,false); }
      };
      if(typeof window.swal === 'function'){
        window.swal({title:'Regenerar acceso de Cocina',text:'Se invalidará el acceso de todas las pantallas vinculadas.',icon:'warning',buttons:['Cancelar','Regenerar'],dangerMode:true}).then(function(ok){ if(ok) run(); });
      }else if(window.confirm('¿Regenerar el acceso y desvincular todas las pantallas?')) run();
    }

    async function toggleKitchen(sw){
      const desired = !!sw.checked;
      const old = !desired;
      sw.disabled = true;
      try{
        const d = await postCompat(['guardar'],{
          activo:desired ? 1 : 0,
          active:desired ? 1 : 0,
          pantalla_cocina_activa:desired ? 1 : 0
        });
        if(!responseOk(d)) throw new Error(messageOf(d,'No se pudo cambiar el estado de Pantalla de Cocina.'));
        setKitchenStatus(desired);
      }catch(e){
        sw.checked = old;
        setKitchenStatus(old);
        notify('error','Pantalla de Cocina',e.message || 'No se pudo cambiar el estado.');
      }finally{ sw.disabled = false; }
    }

    async function copyInput(id){
      const el = document.getElementById(id);
      if(!el) return;
      const value = String(el.value || '').trim();
      if(!value) return;
      try{
        if(navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(value);
        else { el.focus(); el.select(); document.execCommand('copy'); }
        notify('success','Copiado','Dirección copiada al portapapeles.');
      }catch(_){ notify('info','Copiar','Seleccione la dirección y cópiela manualmente.'); }
    }

    // Dirección corta de Cocina: la vista no necesita esperar AJAX para mostrarla.
    ['config-enlace-tv-cocina','config-url-cocina-seguridad','config-enlace-cocina'].forEach(function(id){
      const el = document.getElementById(id);
      if(el && !String(el.value || '').trim()) el.value = base + 'cocina/';
    });

    document.addEventListener('input',function(e){
      if(e.target && e.target.id === 'config-codigo-vinculacion-cocina'){
        const digits = String(e.target.value || '').replace(/\D/g,'').slice(0,6);
        e.target.value = digits.length > 3 ? digits.slice(0,3)+' '+digits.slice(3) : digits;
      }
    });

    document.addEventListener('click',function(e){
      const refresh = e.target.closest && e.target.closest('#btn-refrescar-dispositivos-cocina');
      if(refresh){ e.preventDefault(); loadDevices(true); return; }

      const pair = e.target.closest && e.target.closest('#btn-vincular-tv-cocina');
      if(pair){ e.preventDefault(); pairDevice(pair); return; }

      const unlink = e.target.closest && e.target.closest('.rs-kitchen-unlink');
      if(unlink){ e.preventDefault(); unlinkDevice(unlink,String(unlink.dataset.deviceId || '')); return; }

      const hideDevice = e.target.closest && e.target.closest('.rs-kitchen-hide-device');
      if(hideDevice){
        e.preventDefault();
        const id = String(hideDevice.dataset.deviceId || '');
        if(id){
          hideDeviceFromView(id);
          loadDevices(true);
        }
        return;
      }

      const restoreHidden = e.target.closest && e.target.closest('.rs-kitchen-restore-hidden');
      if(restoreHidden){
        e.preventDefault();
        restoreHiddenDevices();
        loadDevices(true);
        return;
      }

      const test = e.target.closest && e.target.closest('#btn-probar-pantalla-cocina');
      if(test){ e.preventDefault(); testScreens(test); return; }

      const regen = e.target.closest && e.target.closest('#btn-regenerar-enlace-cocina');
      if(regen){ e.preventDefault(); regenerateAccess(regen); return; }

      const copyTv = e.target.closest && e.target.closest('#btn-copiar-url-tv-cocina');
      if(copyTv){ e.preventDefault(); copyInput('config-enlace-tv-cocina'); return; }

      const copySec = e.target.closest && e.target.closest('#btn-copiar-url-seguridad-cocina');
      if(copySec){ e.preventDefault(); copyInput('config-url-cocina-seguridad'); return; }

      const nav = e.target.closest && e.target.closest('.rs-settings-nav-item[data-config-tab]');
      if(nav){
        const tab = String(nav.getAttribute('data-config-tab') || '');
        if(tab === 'dispositivos') setTimeout(function(){ loadDevices(true); },0);
        if(tab === 'cocina') setTimeout(function(){ loadKitchenState(); },0);
      }
    });

    const sw = document.getElementById('config-pantalla-cocina-activa');
    if(sw) sw.addEventListener('change',function(){ toggleKitchen(sw); });

    // Si el panel de Dispositivos se activa por búsqueda/navegación interna,
    // también se carga. Esto evita depender del orden de otros handlers.
    const panelDevices = document.querySelector('.rs-settings-panel[data-config-panel="dispositivos"]');
    if(panelDevices && typeof MutationObserver !== 'undefined'){
      new MutationObserver(function(){
        if(panelDevices.classList.contains('is-active')) loadDevices(false);
      }).observe(panelDevices,{attributes:true,attributeFilter:['class']});
    }

    // Al abrir el Centro de configuración precargamos estado y dispositivos.
    const modalCfg = document.getElementById('modal-configuracion-restaurante');
    if(modalCfg && typeof MutationObserver !== 'undefined'){
      new MutationObserver(function(){
        if(modalCfg.style.display !== 'none'){
          loadKitchenState();
          loadDevices(false);
        }
      }).observe(modalCfg,{attributes:true,attributeFilter:['style','class']});
    }
  });
})();