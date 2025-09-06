// ==============================
// facturasRestaurante.js (FULL)
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

  // ==============================
  // BOTONES: VOLVER y CERRAR SESIÓN
  // ==============================
  const btnVolver = document.getElementById('btn-volver-dashboard');
  if (btnVolver) {
    btnVolver.addEventListener('click', function() {
      // Redirige al dashboard
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
      // Requiere jQuery (ya está cargado en tu HTML)
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

  // Restaurar layout al salir
  window.addEventListener("beforeunload", function () {
    if (navbarTop) navbarTop.style.display = "";
    if (navbarLateral) navbarLateral.style.display = "";
    document.body.classList.remove('vista-facturacion-restaurante');
  });

  // ===== Estado =====
  let mesaSeleccionada = null;
  let facturaActual = null;
  let productos = [];
  let categorias = [];
  let comandaItems = [];
  let clientes = [];
  let mesas = [];
  let isvRates = { 1: 0, 2: 0 };

  let clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };

  // ====== Referencias DOM ======
  const mesasContainer = document.getElementById('mesas-container');
  const productosContainer = document.getElementById('productos-container');
  const categoriasTabs = document.querySelector('.categorias-tabs');
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

  // Helpers de controles del modal producto
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

  // ====== Inicio ======
  init();
  function init() {
    cargarISV().then(actualizarEtiquetasISVCabecera);
    cargarMesas();
    cargarCategorias();
    cargarProductos();
    cargarClientes();
    setupEventListeners();
    bloquearCierrePorFondoYEsc();
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
          // fallback si no trae data-close: apagar todos
          if (modalMesa) modalMesa.style.display = 'none';
          if (modalCliente) modalCliente.style.display = 'none';
          if (modalCategoria) modalCategoria.style.display = 'none';
          if (modalProducto) modalProducto.style.display = 'none';
          if (modalNuevoCliente) modalNuevoCliente.style.display = 'none';
        }
      });
    });

    // Cierre por botones con data-close (Cancelar/Cerrar)
    closeModalButtonsData.forEach(btn => {
      btn.addEventListener('click', function () {
        const target = this.getAttribute('data-close');
        if (!target) return;
        const m = document.querySelector(target);
        if (m) m.style.display = 'none';
      });
    });

    // Guardar mesa
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

    // Guardar nuevo cliente
    if (formNuevoCliente) {
      formNuevoCliente.addEventListener('submit', function(e){
        e.preventDefault();
        guardarClienteBasico();
      });
    }

    // Toggle vistas
    if (btnMostrarProductos) btnMostrarProductos.addEventListener('click', () => mostrarVista('productos'));
    if (btnMostrarComanda)   btnMostrarComanda.addEventListener('click', () => mostrarVista('comanda'));

    // Categoría / Producto
    const btnNuevaCategoria = document.getElementById('btn-nueva-categoria');
    const btnNuevoProducto  = document.getElementById('btn-nuevo-producto');

    if (btnNuevaCategoria) btnNuevaCategoria.addEventListener('click', ()=>{
      const inp = document.getElementById('cat-nombre');
      if (inp) inp.value='';
      if (modalCategoria) modalCategoria.style.display='block';
      setTimeout(()=>inp && inp.focus(),10);
    });

    if (btnNuevoProducto) btnNuevoProducto.addEventListener('click', ()=>{
      const { selCat, inpNombre, inpDesc, inpPrecio } = getProdControls();
      if (selCat) selCat.innerHTML = categorias.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join('');
      if (inpNombre) inpNombre.value='';
      if (inpDesc) inpDesc.value='';
      if (inpPrecio) inpPrecio.value='0.00';

      // Cargar % ISV reales y aplicar exclusividad
      prepararModalProductoISV();

      if (modalProducto) modalProducto.style.display='block';
      setTimeout(()=>{ inpNombre && inpNombre.focus(); },10);
    });

    const btnGuardarCategoria = document.getElementById('btn-guardar-categoria');
    if (btnGuardarCategoria) btnGuardarCategoria.addEventListener('click', guardarCategoriaDesdeModal);
    const btnGuardarProducto = document.getElementById('btn-guardar-producto');
    if (btnGuardarProducto) btnGuardarProducto.addEventListener('click', guardarProductoBasico);

    // Exponer refrescar clientes al global (por si lo llamas desde otro lado)
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
    [modalMesa, modalCliente, modalCategoria, modalProducto, modalNuevoCliente].forEach(m => {
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
        <div class="mesa-info"><span class="mesa-ubicacion">Crea una con “Nueva”.</span></div>
      </div>`;
      return;
    }
    mesas.forEach(mesa => {
      const mesaElement = document.createElement('div');
      mesaElement.className = `mesa-item ${mesa.estado === 'ocupada' ? 'ocupada' : ''}`;
      mesaElement.innerHTML = `
        <div class="mesa-header">
          <span class="mesa-numero">Mesa: ${mesa.numero}</span>
          <span class="mesa-capacidad">${mesa.capacidad} <i class="fas fa-user"></i></span>
        </div>
        <div class="mesa-info">
          <span class="mesa-ubicacion"><i class="fas fa-map-marker-alt"></i> ${mesa.ubicacion}</span>
          <span class="mesa-estado ${mesa.estado}">${mesa.estado === 'ocupada' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>'} ${mesa.estado.toUpperCase()}</span>
        </div>
      `;
      mesaElement.addEventListener('click', () => seleccionarMesa(mesa));
      mesasContainer.appendChild(mesaElement);
    });
  }

  function seleccionarMesa(mesa) {
    if (mesa.estado === 'ocupada') {
      if (confirm(`La mesa ${mesa.numero} está ocupada. ¿Desea cargar la factura existente?`)) cargarFacturaMesa(mesa.id);
      return;
    }
    mesaSeleccionada = mesa;
    facturaActual = null;
    if (mesaSeleccionadaElement) mesaSeleccionadaElement.textContent = `Mesa: ${mesa.numero}`;
    if (facturaTitle) facturaTitle.textContent = 'Nueva Comanda';
    if (btnImprimir) btnImprimir.disabled = true;
    document.querySelectorAll('.mesa-item').forEach(item => item.classList.remove('seleccionada'));
    const mEl = Array.from(document.querySelectorAll('.mesa-item')).find(el => el.querySelector('.mesa-numero').textContent.includes(mesa.numero));
    if (mEl) mEl.classList.add('seleccionada');
    comandaItems = [];
    actualizarComandaUI();
  }

  function mostrarModalMesa() {
    const n = document.getElementById('numero-mesa');
    const c = document.getElementById('capacidad-mesa');
    const u = document.getElementById('ubicacion-mesa');
    if (n) n.value = '';
    if (c) c.value = '4';
    if (u) u.value = 'Interior';
    if (modalMesa) modalMesa.style.display = 'block';
    setTimeout(() => { if (n) { n.focus(); n.select && n.select(); } }, 10);
  }

  function guardarMesa() {
    const numeroMesa = (document.getElementById('numero-mesa') || {}).value?.trim() || '';
    const capacidad = (document.getElementById('capacidad-mesa') || {}).value || '4';
    const ubicacion = (document.getElementById('ubicacion-mesa') || {}).value || 'Interior';
    if (!numeroMesa) { showNotify && showNotify('error', 'Error', 'El número de mesa es requerido'); return; }

    const formData = new FormData();
    formData.append('action', 'saveMesa');
    formData.append('numero', numeroMesa);
    formData.append('capacidad', capacidad);
    formData.append('ubicacion', ubicacion);

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.status) {
          showNotify && showNotify('success', 'Éxito', 'Mesa guardada correctamente');
          if (modalMesa) modalMesa.style.display = 'none';
          cargarMesas();
        } else {
          showNotify && showNotify('error', 'Error', data.message || 'Error al guardar la mesa');
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
      .then(data => { if (data.status) { categorias = data.categorias || []; renderizarCategorias(); } });
  }

  function renderizarCategorias() {
    if (!categoriasTabs) return;
    categoriasTabs.innerHTML = '';
    const todasCategoria = document.createElement('div');
    todasCategoria.className = 'categoria-tab active';
    todasCategoria.textContent = 'Todas';
    todasCategoria.addEventListener('click', () => {
      document.querySelectorAll('.categoria-tab').forEach(tab => tab.classList.remove('active'));
      todasCategoria.classList.add('active');
      filtrarProductos(buscarProductoInput ? buscarProductoInput.value : '');
    });
    categoriasTabs.appendChild(todasCategoria);

    categorias.forEach(categoria => {
      const categoriaElement = document.createElement('div');
      categoriaElement.className = 'categoria-tab';
      categoriaElement.textContent = categoria.nombre;
      categoriaElement.dataset.id = categoria.id;
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
            categorias = [...map.keys()].map(id => ({ id, nombre: `Cat. ${id}` }));
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
        <div style="grid-column:1/-1; text-align:center; padding:30px;">
          <i class="fas fa-shopping-basket" style="font-size:42px; opacity:.4;"></i>
          <p style="margin-top:8px;color:#666">Sin productos que mostrar.</p>
        </div>`;
      return;
    }

    const formatNumber = (num) => new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);

    productosList.forEach(producto => {
      const productoElement = document.createElement('div');
      productoElement.className = 'producto-item';

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
    if (!nombre) { showNotify && showNotify('warning','Validación','Nombre de categoría requerido'); return; }
    const fd = new FormData();
    fd.append('action','saveCategoria');
    fd.append('nombre', nombre);
    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, { method:'POST', body: fd })
      .then(r=>r.json())
      .then(d=>{
        if (!d.status) { showNotify && showNotify('error','Error', d.message||'No se pudo guardar'); return; }
        showNotify && showNotify('success','Éxito','Categoría guardada');
        if (modalCategoria) modalCategoria.style.display='none';
        cargarCategorias();
      })
      .catch(()=> showNotify && showNotify('error','Error','No se pudo guardar'));
  }

  function guardarProductoBasico(){
    const { inpNombre, inpDesc, selCat, inpPrecio, chkISV1, chkISV2 } = getProdControls();
    const nombre = (inpNombre||{}).value?.trim() || '';
    const desc   = (inpDesc||{}).value?.trim() || '';
    const catId  = (selCat||{}).value || '';
    const precio = parseFloat((inpPrecio||{}).value || '0') || 0;
    const isv1   = !!(chkISV1||{}).checked;
    const isv2   = !!(chkISV2||{}).checked;
    if (!nombre) { showNotify && showNotify('warning','Validación','Nombre requerido'); return; }
    if (!catId)  { showNotify && showNotify('warning','Validación','Seleccione categoría'); return; }

    const payload = {
      nombre, descripcion: desc, categoria_id: parseInt(catId),
      precio_venta: precio, isv1: isv1?1:0, isv2: (!isv1 && isv2)?1:0
    };

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'saveProductoBasico', data: payload })
    })
      .then(r=>r.json())
      .then(d=>{
        if (!d.status) { showNotify && showNotify('error','Error', d.message||'No se pudo guardar el producto'); return; }
        showNotify && showNotify('success','Éxito','Producto guardado');
        if (modalProducto) modalProducto.style.display='none';
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

  function renderizarClientes() {
    const cont = document.getElementById('clientes-container');
    if (!cont) return;
    cont.innerHTML = '';
    const cf = document.createElement('div');
    cf.className = 'cliente-item';
    cf.innerHTML = `<div class="cliente-nombre">CONSUMIDOR FINAL</div><div class="cliente-identificacion">Cliente genérico</div>`;
    cf.addEventListener('click', () => {
      clienteSeleccionado = { id: 0, nombre: 'CONSUMIDOR FINAL', identificacion: '' };
      if (clienteInfoElement) clienteInfoElement.textContent = 'Cliente: Consumidor final';
      if (modalCliente) modalCliente.style.display = 'none';
    });
    cont.appendChild(cf);

    clientes.forEach(c => {
      const el = document.createElement('div');
      el.className = 'cliente-item';
      el.innerHTML = `<div class="cliente-nombre">${c.nombre}</div><div class="cliente-identificacion">${c.identificacion || 'Sin identificación'}</div>`;
      el.addEventListener('click', () => {
        clienteSeleccionado = { id: c.clientes_id, nombre: c.nombre, identificacion: c.identificacion || '' };
        if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${c.nombre}`;
        if (modalCliente) modalCliente.style.display = 'none';
      });
      cont.appendChild(el);
    });
  }

  function mostrarModalCliente() {
    renderizarClientes();
    const buscador = document.getElementById('buscar-cliente');
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
    items[0].style.display = 'block';
    for (let i = 1; i < items.length; i++) {
      const nombre = items[i].querySelector('.cliente-nombre').textContent.toLowerCase();
      const ident = items[i].querySelector('.cliente-identificacion').textContent.toLowerCase();
      items[i].style.display = (nombre.includes(t) || ident.includes(t)) ? 'block' : 'none';
    }
  }

  function abrirModalNuevoCliente(){
    // limpiar campos
    const campos = ['cli-nombre','cli-rtn','cli-localidad','cli-telefono','cli-correo'];
    campos.forEach(id => { const el = document.getElementById(id); if (el) el.value=''; });
    if (modalNuevoCliente) modalNuevoCliente.style.display = 'block';
    setTimeout(()=>{ const el = document.getElementById('cli-nombre'); el && el.focus(); },10);
  }

  function guardarClienteBasico(){
    const nombre     = (document.getElementById('cli-nombre')||{}).value?.trim() || '';
    const rtn        = (document.getElementById('cli-rtn')||{}).value?.trim() || '';
    const localidad  = (document.getElementById('cli-localidad')||{}).value?.trim() || '';
    const telefono   = (document.getElementById('cli-telefono')||{}).value?.trim() || '';
    const correo     = (document.getElementById('cli-correo')||{}).value?.trim() || '';

    if (!nombre){ showNotify && showNotify('warning','Validación','Nombre/ Razón social es obligatorio'); return; }

    const payload = {
      nombre,
      rtn,
      fecha: new Date().toISOString().slice(0,10), // yyyy-mm-dd
      departamentos_id: 0,
      municipios_id: 0,
      localidad,
      telefono,
      correo,
      estado: 1 // por defecto activo
      // colaboradores_id y fecha_registro los pones en PHP desde la sesión/now()
    };

    fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'saveClienteBasico', data: payload })
    })
      .then(r=>r.json())
      .then(d=>{
        if (!d.status){ showNotify && showNotify('error','Error', d.message || 'No se pudo guardar el cliente'); return; }
        showNotify && showNotify('success','Éxito','Cliente guardado');
        if (modalNuevoCliente) modalNuevoCliente.style.display = 'none';
        // refrescar lista y seleccionar el nuevo
        cargarClientes().then(()=>{
          if (d.cliente && d.cliente.clientes_id){
            clienteSeleccionado = {
              id: d.cliente.clientes_id,
              nombre: d.cliente.nombre || nombre,
              identificacion: d.cliente.rtn || rtn
            };
            if (clienteInfoElement) clienteInfoElement.textContent = `Cliente: ${clienteSeleccionado.nombre}`;
          }
        });
      })
      .catch(()=> showNotify && showNotify('error','Error','No se pudo guardar el cliente'));
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
          document.querySelectorAll('.mesa-item').forEach(item => {
            item.classList.remove('seleccionada');
            if (item.querySelector('.mesa-numero').textContent.includes(mesaSeleccionada.numero)) item.classList.add('seleccionada');
          });
        } else {
          showNotify && showNotify('error', 'Error', data.message || 'No se pudo cargar la factura');
        }
      })
      .catch(() => { showNotify && showNotify('error', 'Error', 'Error al cargar la factura'); });
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

  // ======= ISV del modal de Producto (lee getIsvConfig.php) =======
  function prepararModalProductoISV(){
    const chk1 = document.getElementById('prod-isv1'); // equivalente a producto_isv1
    const chk2 = document.getElementById('prod-isv2'); // equivalente a producto_isv2

    if (chk1) chk1.checked = false;
    if (chk2) chk2.checked = false;

    // Reescribe el label para una sola línea "ISV 15%"
    function setIsvLabelSingleLine(chk, rate){
      if (!chk) return;
      const label = chk.closest('label'); // tu HTML usa <label class="radio-container"><input ...> TEXT
      if (!label) return;

      const cb = chk;
      label.innerHTML = '';
      label.appendChild(cb);

      const span = document.createElement('span');
      span.className = 'isv-inline';
      span.textContent = ` ISV ${Number(rate)}%`;
      span.style.marginLeft = '8px';
      label.appendChild(span);

      cb.dataset.valor = (Number(rate) || 0) / 100;
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
      // Fallback simple si falla el endpoint
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

});
