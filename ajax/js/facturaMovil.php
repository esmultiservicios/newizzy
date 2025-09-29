<script>
// facturaMovil.js
// =======================================
// VISTA: Facturación Móvil
// =======================================

$(() => {
  // ===============================
  // VARIABLES GLOBALES
  // ===============================
  let productosAgregados = [];        // Array para almacenar productos agregados a la factura
  let currentFacturaId = null;        // ID de la factura actual en proceso
  let secuenciaFactura = null;        // Secuencia de facturación del SAR
  let facturasDisponibles = 0;        // Número de facturas disponibles del SAR
  let lastState = null;               // Último estado del contador de facturas (para no repintar)
  let lastFacturasCount = null;       // Último conteo de facturas (para no repintar)
  let currentProductPrice = 0;        // Precio del producto actual seleccionado (para modal descuento)
  let cajaAbierta = false;            // Estado de la caja (abierta/cerrada)
  let aperturaInfo = null;            // Información de apertura de caja

  // Formateador de números para montos en lempiras
  const formatter = new Intl.NumberFormat('es-HN', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  // ===============================
  // 1. CARGAR DATOS INICIALES
  // ===============================
  cargarClientes();                   // Cargar lista de clientes
  cargarVendedores();                 // Cargar lista de vendedores
  cargarProductos();                  // Cargar lista de productos (con ISV desde PHP)
  obtenerSecuenciaFactura();          // Obtener secuencia de facturación
  getTotalFacturasDisponibles();      // Obtener facturas disponibles del SAR
  verificarAperturaCaja();            // Verificar estado de la caja
  getCajero();                        // Tu método global (no tocar)

  // ===============================
  // 2. EVENTOS PRINCIPALES
  // ===============================
  $('#agregar-producto').click(agregarProducto);
  $('#procesar-factura-top, #procesar-factura-bottom').click(procesarFactura);
  $('#cancelar-factura-top, #cancelar-factura-bottom').click(cancelarFactura);
  $('#efectivo-pago, #transferencia-pago, #tarjeta-pago').on('input', calcularCambio);
  $('#registrar-pago').click(registrarPago);
  $('#guardar-descuento').click(guardarDescuento);
  $('#nuevo-descuento-monto').on('input', actualizarDescuentoDesdeMonto);
  $('#nuevo-descuento-porcentaje').on('input', actualizarDescuentoDesdePorcentaje);

  // Búsqueda por código de barras
  $('#codigo-barra').on('change keyup paste', function () {
    const codigo = $(this).val().trim();
    if (codigo.length > 3) buscarProductoPorCodigo(codigo);
  });

  // Apertura/Cierre de caja
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

  // ===============================
  // 3. FUNCIONES DE CAJA
  // ===============================

  /**
   * Consulta el estado de apertura de caja del usuario
   * @returns {number} 1 = caja abierta, 2 = caja cerrada
   */
  function getConsultarAperturaCaja() {
    var url = '<?php echo SERVERURL; ?>core/getAperturaCajaUsuario.php';
    var estado_apertura = 2; // Por defecto cerrada

    $.ajax({
      type: 'POST',
      url: url,
      async: false,
      success: function (registro) {
        try {
          var valores = JSON.parse(registro);
          estado_apertura = valores[0];
        } catch (e) {
          console.error("Error parsing apertura caja response:", e);
          estado_apertura = 2;
        }
      },
      error: function () {
        console.error("Error en AJAX getAperturaCajaUsuario");
        estado_apertura = 2;
      }
    });
    return estado_apertura;
  }

  /**
   * Verifica y actualiza el estado de la caja
   */
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

  /**
   * Habilita/deshabilita la UI según el estado de la caja
   * @param {boolean} abierta
   */
  function toggleUIForCajaAbierta(abierta) {
    var disable = !abierta;

    $('#agregar-producto').prop('disabled', disable);
    $('#procesar-factura-top, #procesar-factura-bottom').prop('disabled', disable);
    $('#cancelar-factura-top, #cancelar-factura-bottom').prop('disabled', !cajaAbierta);

    $('#cliente-select, #vendedor-select, #producto-select, #cantidad, #descuento, #codigo-barra, #notas')
      .prop('disabled', disable);

    // Refrescar selects bootstrap-select
    if ($('#cliente-select').hasClass('selectpicker')) $('#cliente-select').selectpicker('refresh');
    if ($('#vendedor-select').hasClass('selectpicker')) $('#vendedor-select').selectpicker('refresh');
    if ($('#producto-select').hasClass('selectpicker')) $('#producto-select').selectpicker('refresh');

    // Botones de procesar
    if (disable) {
      $('#procesar-factura-top, #procesar-factura-bottom')
        .removeClass('btn-success')
        .addClass('btn-outline-danger')
        .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
    } else {
      $('#procesar-factura-top, #procesar-factura-bottom')
        .removeClass('btn-outline-danger')
        .addClass('btn-success')
        .html('<i class="fas fa-save mr-1"></i> Registrar Factura');
    }
  }

  /**
   * Muestra el modal para aperturar caja
   */
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
  }

  /**
   * Muestra el modal para cerrar caja
   */
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

  // ===============================
  // 4. FACTURAS DISPONIBLES (CONTADOR)
  // ===============================

  function getTotalFacturasDisponibles() {
    $.ajax({
      type: 'POST',
      url: '<?php echo SERVERURL; ?>core/getTotalFacturasDisponibles.php?_=' + new Date().getTime(),
      dataType: 'json'
    })
      .done(function (datos) {
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

  function renderCounter(datos) {
    const rawCount = datos.facturasPendientes;
    const facturasPendientes = Number(rawCount) || 0;
    const daysLeft = parseInt(datos.contador, 10);
    const fechaLimite = datos.fechaLimite;

    const state = getCurrentState(facturasPendientes, daysLeft, fechaLimite);
    const cfg = getStateConfig(state, facturasPendientes, daysLeft, fechaLimite);

    const $counter = $('#factura-counter');
    let $value = $('#factura-disponibles');
    if (!$value.length) {
      $counter.append('<span class="counter-value" id="factura-disponibles"></span>');
      $value = $('#factura-disponibles');
    }
    const $icon = $counter.find('i').first();

    const desiredText = (cfg.mainText ?? `${facturasPendientes.toLocaleString('es-HN')} facturas`).trim();
    const currentText = $value.text().trim();

    if (state === lastState && facturasPendientes === lastFacturasCount && currentText === desiredText) {
      updateButtonsState(facturasPendientes, fechaLimite, daysLeft);
      return;
    }

    if ($icon.length) {
      $icon.attr('class', cfg.icon);
    } else {
      $counter.prepend(`<i class="${cfg.icon}"></i> `);
    }

    $value.text(desiredText);

    $counter.find('.counter-hint').remove();
    if (cfg.hintHtml) {
      $counter.append(`<span class="counter-hint">${cfg.hintHtml}</span>`);
    }

    $counter.attr('data-count', String(facturasPendientes));
    $counter.attr('title', desiredText);

    $counter
      .removeClass(function (_i, c) {
        return (c.match(/\bcounter-\S+/g) || []).join(' ');
      })
      .addClass(cfg.class);

    $counter.removeClass('state-change');
    if ($counter.length) { void $counter[0].offsetWidth; }
    $counter.addClass('state-change');

    lastState = state;
    lastFacturasCount = facturasPendientes;

    updateButtonsState(facturasPendientes, fechaLimite, daysLeft);
  }

  function getCurrentState(facturasPendientes, daysLeft, fechaLimite) {
    if (!fechaLimite || String(fechaLimite).trim() === 'Sin definir') return 'no-config';
    if (facturasPendientes < 0) return 'blocked';
    if (daysLeft < 0) return 'expired';
    if (daysLeft <= 5) return 'danger';
    if (facturasPendientes <= 9) return 'danger';
    if (facturasPendientes <= 30) return 'warning';
    return 'normal';
  }

  function getStateConfig(state, facturasPendientes, daysLeft, fechaLimite) {
    const facturasFormateadas = Number(facturasPendientes).toLocaleString('es-HN');

    let hint = '';
    if (daysLeft <= 5) {
      hint = (daysLeft < 0)
        ? 'Autorizaciones vencidas'
        : (daysLeft === 0 ? 'Vencen hoy' : `Vencen en ${daysLeft} día(s)`);
    }

    const base = {
      mainText: `${facturasFormateadas} facturas`,
      hintHtml: hint ? `<small class="d-block">${hint}</small>` : ''
    };

    const map = {
      normal: { icon: 'fas fa-file-invoice', class: 'counter-normal', ...base },
      warning: { icon: 'fas fa-hourglass-half', class: 'counter-warning', ...base },
      danger: { icon: 'fas fa-exclamation-triangle', class: 'counter-danger', ...base },
      expired: {
        icon: 'fas fa-calendar-times',
        class: 'counter-expired',
        mainText: 'Autorizaciones vencidas',
        hintHtml:
          `<small class="d-block">
             <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Actualizar</a>
           </small>`
      },
      blocked: {
        icon: 'fas fa-ban',
        class: 'counter-blocked',
        mainText: 'Límite alcanzado',
        hintHtml:
          `<small class="d-block">
             <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Configurar</a>
           </small>`
      },
      'no-config': {
        icon: 'fas fa-calendar-times',
        class: 'counter-no-config',
        mainText: 'Sin fecha límite',
        hintHtml:
          `<small class="d-block">
             <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Establecer</a>
           </small>`
      }
    };

    return map[state] || map.normal;
  }

  function showErrorState() {
    const $counter = $('#factura-counter');
    let $value = $('#factura-disponibles');
    if (!$value.length) {
      $counter.append('<span class="counter-value" id="factura-disponibles"></span>');
      $value = $('#factura-disponibles');
    }

    $counter
      .removeClass(function (_i, c) {
        return (c.match(/\bcounter-\S+/g) || []).join(' ');
      })
      .addClass('counter-danger');

    if ($counter.find('i').length) {
      $counter.find('i').first().attr('class', 'fas fa-exclamation-circle');
    } else {
      $counter.prepend('<i class="fas fa-exclamation-circle"></i> ');
    }
    $value.text('Error al cargar');
    $counter.attr('data-count', '0');
  }

  function updateButtonsState(facturasPendientes, fechaLimite, daysLeft) {
    const vencimientoPasado = daysLeft < 0;
    const sarDisabled = facturasPendientes <= 0 || !fechaLimite || String(fechaLimite).trim() === "Sin definir" || vencimientoPasado;
    const disabled = sarDisabled || !cajaAbierta;

    $('#procesar-factura-top, #procesar-factura-bottom').prop('disabled', disabled);
    $('#agregar-producto, #cancelar-factura-top, #cancelar-factura-bottom').prop('disabled', !cajaAbierta);

    if (disabled) {
      if (sarDisabled && cajaAbierta) {
        $('#procesar-factura-top, #procesar-factura-bottom')
          .removeClass('btn-success')
          .addClass('btn-danger')
          .html('<i class="fas fa-ban mr-1"></i> No disponible (Límite SAR)');
      } else if (!cajaAbierta) {
        $('#procesar-factura-top, #procesar-factura-bottom')
          .removeClass('btn-success')
          .addClass('btn-danger')
          .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
      }
    } else {
      $('#procesar-factura-top, #procesar-factura-bottom')
        .removeClass('btn-danger')
        .addClass('btn-success')
        .html('<i class="fas fa-save mr-1"></i> Registrar Factura');
    }

    // Actualizar botón de caja
    if (cajaAbierta) {
      $('#btn-apertura-caja')
        .removeClass('btn-primary')
        .addClass('btn-warning')
        .html('<i class="fas fa-lock mr-1"></i> Cerrar Caja')
        .data('mode', 'cerrar');
    } else {
      $('#btn-apertura-caja')
        .removeClass('btn-warning')
        .addClass('btn-primary')
        .html('<i class="fas fa-lock-open mr-1"></i> Aperturar Caja')
        .data('mode', 'abrir');
    }
  }

  // ===============================
  // 5. PRODUCTOS Y FACTURACIÓN
  // ===============================

  function agregarProducto() {
    const $opt = $('#producto-select option:selected');
    const productoId   = $opt.val();
    const productoText = $opt.text();
    const precio       = parseFloat($opt.data('precio'));
    const cantidad     = parseInt($('#cantidad').val()) || 1;
    const descuento    = parseFloat($('#descuento').val()) || 0;

    // Nuevos data-* desde getProductos.php
    const isvVenta   = parseInt($opt.data('isvventa')) === 1;     // 1 = sí aplica
    const isvRate    = parseFloat($opt.data('isvrate')) || 0;     // 0.15 / 0.18 / 0
    const isvLabel   = ($opt.data('isvlabel') || '').toString();  // "ISV 15.00%" / "ISV 18.00%" / ""
    let   isvId      = parseInt($opt.data('isvid') || 0);         // 1 ó 2 (si viene)

    if (!productoId) {
      showNotify("warning", "Advertencia", "Seleccione un producto");
      return;
    }

    // Inferir isvId si no vino en el option
    if (!isvId) {
      if (Math.abs(isvRate - 0.15) < 1e-6) isvId = 1;
      else if (Math.abs(isvRate - 0.18) < 1e-6) isvId = 2;
      else isvId = 0;
    }

    // isv por unidad
    const isvUnit = isvVenta ? ((precio || 0) * isvRate) : 0;

    const index = productosAgregados.findIndex(p => p.productoId == productoId);

    if (index >= 0) {
      swal({
        title: "Producto existente",
        text: "Este producto ya está en la lista. ¿Desea actualizar la cantidad o agregar como nuevo?",
        icon: "warning",
        buttons: {
          cancel: { text: "Cancelar", visible: true },
          confirm: { text: "¡Si, actualizar!" }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
      }).then((result) => {
        if ((result && result.isConfirmed) || result === true) {
          productosAgregados[index].cantidad  += cantidad;
          productosAgregados[index].descuento += descuento;
          actualizarListaProductos();
          calcularTotales();
        } else if (result && result.isDenied) {
          productosAgregados.push({ productoId, productoText, precio, cantidad, descuento, isv: isvUnit, isvRate, isvId, isvLabel });
          actualizarListaProductos();
          calcularTotales();
        } else if (result === true) {
          productosAgregados[index].cantidad  += cantidad;
          productosAgregados[index].descuento += descuento;
          actualizarListaProductos();
          calcularTotales();
        }
      });
    } else {
      productosAgregados.push({ productoId, productoText, precio, cantidad, descuento, isv: isvUnit, isvRate, isvId, isvLabel });
      actualizarListaProductos();
      calcularTotales();
    }

    $('#producto-select').val('').selectpicker('refresh');
    $('#cantidad').val(1);
    $('#descuento').val('').attr('placeholder', '');
    $('#codigo-barra').val('').focus();
  }

  function actualizarListaProductos() {
    const $container = $('#productos-agregados');
    $container.empty();

    if (productosAgregados.length === 0) {
      $container.append('<div class="alert alert-info">No hay productos agregados</div>');
      return;
    }

    productosAgregados.forEach((producto, index) => {
      producto.precio    = producto.precio || 0;
      producto.cantidad  = producto.cantidad || 1;
      producto.descuento = producto.descuento || 0;
      producto.isv       = producto.isv || 0; // por unidad
      producto.isvRate   = producto.isvRate || 0;
      producto.isvLabel  = producto.isvLabel || '';
      producto.isvId     = producto.isvId || 0;

      const subtotal = (producto.precio * producto.cantidad) - producto.descuento;
      const isvTotal = (producto.isv) * producto.cantidad;

      $container.append(`
        <div class="product-item" data-index="${index}">
          <div class="d-flex justify-content-between align-items-start">
            <h6 class="mb-0">${producto.productoText}${producto.isvLabel ? ' · ' + producto.isvLabel : ''}</h6>
          </div>
          <div class="product-details">
            <div class="product-detail">
              <span>Precio:</span>
              <strong>L. ${formatter.format(producto.precio)}</strong>
            </div>
            <div class="product-detail">
              <div class="d-flex align-items-center justify-content-between">
                <span>Cantidad:</span>
                <div class="cantidad-group d-flex align-items-center mt-2">
                  <button class="btn btn-outline-secondary btn-sm btn-cantidad-minus" type="button" data-index="${index}">
                    <i class="fas fa-minus"></i>
                  </button>
                  <input type="number" class="form-control text-center input-cantidad mx-1" value="${producto.cantidad}" min="1" data-index="${index}" style="width: 50px;">
                  <button class="btn btn-outline-secondary btn-sm btn-cantidad-plus" type="button" data-index="${index}">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="product-detail">
              <span>Descuento:</span>
              <strong>L. ${formatter.format(producto.descuento)}</strong>
            </div>
            <div class="product-detail">
              <span>${producto.isvLabel ? producto.isvLabel : 'ISV'}:</span>
              <strong>L. ${formatter.format(isvTotal)}</strong>
            </div>
          </div>
          <div class="product-actions">
            <button type="button" class="btn btn-warning btn-sm btn-edit-product" data-index="${index}">
              <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <div class="product-subtotal">
            Subtotal: L. ${formatter.format(subtotal)}
          </div>
        </div>
      `);
    });

    // Eliminar producto
    $(document).off('click', '.btn-eliminar-producto').on('click', '.btn-eliminar-producto', function () {
      const index = $(this).closest('.product-item').data('index');
      const producto = productosAgregados[index];

      if (index === undefined || !producto) {
        showNotify("error", "Error", "No se pudo encontrar el producto para eliminar");
        return;
      }

      swal({
        title: "¿Estás seguro?",
        text: `¿Desea eliminar el producto: ${producto.productoText}?`,
        icon: "warning",
        buttons: {
          cancel: { text: "Cancelar", visible: true },
          confirm: { text: "¡Si, eliminar!" }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
      }).then((result) => {
        if ((result && result.isConfirmed) || result === true) {
          productosAgregados.splice(index, 1);
          actualizarListaProductos();
          calcularTotales();
          showNotify("success", "Éxito", "Producto eliminado correctamente");
        }
      });
    });

    // Editar producto (abrir modal de descuento)
    $(document).off('click', '.btn-edit-product').on('click', '.btn-edit-product', function () {
      const idx = Number($(this).data('index'));
      if (!isNaN(idx) && productosAgregados[idx]) {
        currentProductPrice = (productosAgregados[idx].precio * productosAgregados[idx].cantidad) || 0;
        $('#producto-index').val(idx);
        $('#nuevo-descuento-monto').val(productosAgregados[idx].descuento || 0);
        $('#nuevo-descuento-porcentaje').val(currentProductPrice > 0 ? ((productosAgregados[idx].descuento || 0) / currentProductPrice * 100).toFixed(2) : '0.00');
        $('#descuento-total').val(`L. ${formatter.format(productosAgregados[idx].descuento || 0)} (${currentProductPrice > 0 ? ((productosAgregados[idx].descuento || 0) / currentProductPrice * 100).toFixed(2) : '0.00'}%)`);
        $('#editarDescuentoModal').modal('show');
      }
    });

    // Cambiar cantidad con botones
    $(document).off('click', '.btn-cantidad-minus').on('click', '.btn-cantidad-minus', function () {
      const idx = Number($(this).data('index'));
      if (!isNaN(idx) && productosAgregados[idx]) {
        productosAgregados[idx].cantidad = Math.max(1, (productosAgregados[idx].cantidad || 1) - 1);
        actualizarListaProductos();
        calcularTotales();
      }
    });
    $(document).off('click', '.btn-cantidad-plus').on('click', '.btn-cantidad-plus', function () {
      const idx = Number($(this).data('index'));
      if (!isNaN(idx) && productosAgregados[idx]) {
        productosAgregados[idx].cantidad = (productosAgregados[idx].cantidad || 1) + 1;
        actualizarListaProductos();
        calcularTotales();
      }
    });

    // Cambiar cantidad editando el input
    $(document).off('input', '.input-cantidad').on('input', '.input-cantidad', function () {
      const idx = Number($(this).data('index'));
      let val = parseInt($(this).val(), 10);
      if (isNaN(val) || val < 1) val = 1;
      if (!isNaN(idx) && productosAgregados[idx]) {
        productosAgregados[idx].cantidad = val;
        actualizarListaProductos();
        calcularTotales();
      }
    });
  }

  function calcularTotales() {
    let subtotal = 0;
    let totalDescuento = 0;
    let isv15 = 0;
    let isv18 = 0;

    productosAgregados.forEach(producto => {
      producto.precio    = producto.precio || 0;
      producto.cantidad  = producto.cantidad || 1;
      producto.descuento = producto.descuento || 0;
      const isvUnit      = producto.isv || 0;     // isv por unidad
      const isvId        = producto.isvId || 0;   // 1 ó 2 (si 0, no acumula)

      subtotal       += producto.precio * producto.cantidad;
      totalDescuento += producto.descuento;

      const isvLinea = isvUnit * producto.cantidad;
      if (isvId === 1)      isv15 += isvLinea;
      else if (isvId === 2) isv18 += isvLinea;
    });

    const totalIsv = isv15 + isv18;
    const total    = (subtotal - totalDescuento) + totalIsv;

    $('#subtotal').text(`L. ${formatter.format(subtotal - totalDescuento)}`);
    $('#isv-15').text(`L. ${formatter.format(isv15)}`);
    $('#isv-18').text(`L. ${formatter.format(isv18)}`);
    $('#total-descuento').text(`L. ${formatter.format(totalDescuento)}`);
    $('#total').text(`L. ${formatter.format(total)}`);
  }

  function procesarFactura(e) {
    e.preventDefault();

    if (!cajaAbierta) {
      showNotify("warning", "Caja cerrada", "No puedes procesar facturas sin aperturar caja");
      formAperturaBill();
      return;
    }

    // Toma el valor desde selectpicker si .val() viniera vacío
    const clienteId  = $('#cliente-select').val()  || $('#cliente-select').selectpicker('val');
    const vendedorId = $('#vendedor-select').val() || $('#vendedor-select').selectpicker('val') || 0;

    if (!clienteId) {
      showNotify("warning", "Advertencia", "Seleccione un cliente");
      return;
    }

    if (productosAgregados.length === 0) {
      showNotify("warning", "Advertencia", "Agregue al menos un producto");
      return;
    }

    const tipoFactura = parseInt($('input[name="tipo-factura"]:checked').val(), 10);

    const datos = {
      clienteId: clienteId,
      vendedorId: vendedorId,
      tipoFactura: tipoFactura,
      productos: productosAgregados, // incluye isvId e isvRate; el backend recalcula con BD igualmente.
      notas: $('#notas').val(),
      aperturaId: (aperturaInfo && aperturaInfo.apertura_id) ? aperturaInfo.apertura_id : null
      // secuencia_facturacion_id opcional
    };

    showNotify("info", "Procesando factura", "Por favor espere...", true);

    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/procesarFactura.php',
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(datos),
      contentType: 'application/json',
      success: function (response) {
        if (response.estado) {
          currentFacturaId = response.factura_id;

          if (tipoFactura === 1) {
            // contado → abrir modal de pago
            pago(currentFacturaId, 1, 'facturacion');
          } else {
            // crédito
            showNotify("success", "Éxito", "Factura registrada correctamente");
            resetearFormulario();
            cargarClientes();                // deja “Consumidor Final” (ID 1)
          }

          getTotalFacturasDisponibles();
        } else {
          showNotify("error", "Error", response.message || 'Error al procesar la factura');
        }
      },
      error: function (xhr) {
        showNotify("error", "Error", (xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar la factura');
      }
    });
  }

  // ===============================
  // 6. PAGO
  // ===============================

  function registrarPago() {
    const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
    const transferencia = parseFloat($('#transferencia-pago').val()) || 0;
    const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
    const totalPago = efectivo + transferencia + tarjeta;
    const montoFactura = parseFloat($('#monto-pago').val()) || 0;

    if (totalPago < montoFactura) {
      showNotify("warning", "Advertencia", "El pago no cubre el total de la factura");
      return;
    }

    const faltanteTrasNoEfectivo = Math.max(0, montoFactura - (transferencia + tarjeta));
    const cambioDesdeEfectivo = Math.max(0, efectivo - faltanteTrasNoEfectivo);
    $('#cambio-pago').val(formatter.format(cambioDesdeEfectivo));

    const datos = {
      facturaId: $('#factura-id-pago').val(),
      efectivo: efectivo,
      transferencia: transferencia,
      tarjeta: tarjeta,
      cambio: cambioDesdeEfectivo
    };

    showNotify("info", "Registrando pago", "Por favor espere...", true);

    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/registrarPago.php',
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(datos),
      contentType: 'application/json',
      success: function (response) {
        if (response.success) {
          $('#pagoModal').modal('hide');
          showNotify("success", "Éxito", "Pago registrado correctamente");

          // imprimir si es contado
          if (response.imprimir && response.factura_id) {
            try { printBill(response.factura_id); } catch (e) { console.error(e); }
          }

          resetearFormulario();
          cargarClientes();  
          getTotalFacturasDisponibles();
        } else {
          showNotify("error", "Error", response.message || 'Error al registrar el pago');
        }
      },
      error: function (xhr) {
        showNotify("error", "Error", (xhr.responseJSON && xhr.responseJSON.message) || 'Error al registrar el pago');
      }
    });
  }

  function calcularCambio() {
    const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
    const transferencia = parseFloat($('#transferencia-pago').val()) || 0;
    const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
    const montoFactura = parseFloat($('#monto-pago').val()) || 0;

    const cambioDesdeEfectivo = Math.max(0, efectivo - Math.max(0, montoFactura - (transferencia + tarjeta)));
    $('#cambio-pago').val(cambioDesdeEfectivo > 0 ? formatter.format(cambioDesdeEfectivo) : '');
  }

  // Cuando se muestre el modal de pago, enfocar en "Efectivo"
  $('#pagoModal').on('shown.bs.modal', function () {
    $('#efectivo-pago').trigger('focus');
  });


  // ===============================
  // 7. AUXILIARES
  // ===============================
  function cargarClientes() {
    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/getClientes.php',
      type: 'GET',
      dataType: 'json',
      success: function (resp) {
        const clientes = Array.isArray(resp) ? resp : (resp.data || []);
        const $sel = $('#cliente-select');

        $sel.empty().append('<option value="">Seleccione un cliente</option>');

        clientes.forEach(c => {
          const nombre = (c && c.nombre) ? c.nombre : 'Sin nombre';
          const rtn    = (c && c.rtn && c.rtn.trim() !== '') ? c.rtn : 'Sin RTN';
          $sel.append(`<option value="${c.clientes_id}">${nombre} - ${rtn}</option>`);
        });

        // Refrescar y seleccionar "Consumidor Final" (ID = 1)
        $sel.selectpicker('refresh');
        $sel.selectpicker('val', '1');

        if (window.innerWidth < 768) {
          $('.bootstrap-select').addClass('mobile-select');
          $('.dropdown-menu').addClass('mobile-dropdown');
        }
      },
      error: function () {
        showNotify("error", "Error", "No se pudieron cargar los clientes");
      }
    });
  }

  function cargarVendedores() {
    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/getVendedores.php',
      type: 'GET',
      dataType: 'json',
      success: function (data) {
        $('#vendedor-select').empty().append('<option value="">Seleccione un vendedor</option>');
        $.each(data, function (index, vendedor) {
          $('#vendedor-select').append(`<option value="${vendedor.colaboradores_id}">${vendedor.nombre}</option>`);
        });
        $('#vendedor-select').selectpicker('refresh');
      },
      error: function () {
        showNotify("error", "Error", "No se pudieron cargar los vendedores");
      }
    });
  }

  /**
   * IMPORTANTE: este PHP debe devolver por producto:
   * - productos_id, nombre, precio_venta, isv_venta
   * - isv_id_aplicado (1 o 2), isv_rate_decimal (0.15/0.18), isv_label ("ISV 15.00%" / "ISV 18.00%")
   *   tomando prioridad isv2=1 sobre isv1=1 y solo cuando isv_venta=1
   */
  function cargarProductos() {
    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/getProductos.php',
      type: 'GET',
      dataType: 'json',
      success: function (data) {
        // Asegurar que 'lista' sea realmente un array de productos
        let lista = [];
        if (Array.isArray(data)) {
          lista = data;
        } else if (data && Array.isArray(data.productos)) {
          lista = data.productos;
        } else if (data && Array.isArray(data.items)) {
          lista = data.items;
        } else {
          console.warn('Formato inesperado en getProductos.php:', data);
        }

        $('#producto-select').empty().append('<option value="">Seleccione un producto</option>');

        lista.forEach((p) => {
          const id        = (p.productos_id != null) ? p.productos_id : p.id;
          const nombre    = (p.nombre != null) ? p.nombre : p.descripcion || 'Producto';
          const precio    = Number(p.precio_venta != null ? p.precio_venta : p.precio) || 0;
          const isvVenta  = Number(p.isv_venta || 0);
          const isvId     = Number(p.isv_id_aplicado || 0);
          const isvRate   = Number(p.isv_rate_decimal || 0);
          const isvLabel  = (p.isv_label || '').toString();

          if (!id || !nombre) return;

          $('#producto-select').append(
            `<option value="${id}"
                     data-precio="${precio}"
                     data-isvventa="${isvVenta}"
                     data-isvid="${isvId}"
                     data-isvrate="${isvRate}"
                     data-isvlabel="${isvLabel}">
               ${nombre} - L. ${formatter.format(precio)}${isvLabel ? ' · ' + isvLabel : ''}
             </option>`
          );
        });

        $('#producto-select').selectpicker('refresh');
      },
      error: function () {
        showNotify("error", "Error", "No se pudieron cargar los productos");
      }
    });
  }

  function obtenerSecuenciaFactura() {
    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/getSecuenciaFactura.php',
      type: 'GET',
      dataType: 'json',
      success: function (data) {
        secuenciaFactura = data;
      },
      error: function () {
        showNotify("error", "Error", "No se pudo obtener la secuencia de facturación");
      }
    });
  }

  function cancelarFactura() {
    if (productosAgregados.length === 0) {
      resetearFormulario();
      return;
    }

    swal({
      title: "¿Estás seguro?",
      text: "¿Desea cancelar la factura en proceso? Se perderán todos los datos ingresados",
      icon: "warning",
      buttons: {
        cancel: { text: "Cancelar", visible: true },
        confirm: { text: "¡Si, cancelar!" }
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then((result) => {
      if ((result && result.isConfirmed) || result === true) {
        resetearFormulario();
        showNotify("success", "Éxito", "Factura cancelada correctamente");
      }
    });
  }

  function resetearFormulario() {
    productosAgregados = [];
    currentFacturaId = null;
    $('#cliente-select, #vendedor-select, #producto-select').val('').selectpicker('refresh');
    $('#cantidad').val(1);
    $('#descuento').val('').attr('placeholder', '0.00');
    $('#notas').val('');
    $('#contado').prop('checked', true);
    actualizarListaProductos();
    calcularTotales();
    obtenerSecuenciaFactura();
  }

  /**
   * Este PHP debe devolver los mismos campos ISV que getProductos.php para el item encontrado.
   */
  function buscarProductoPorCodigo(codigo) {
    $.ajax({
      url: '<?php echo SERVERURL;?>core/facturas/buscarProductoPorCodigo.php',
      type: 'POST',
      data: { codigo: codigo },
      dataType: 'json',
      success: function (response) {
        if (response.success && response.producto) {
          const p = response.producto;

          // Si no existe la opción aún, la agregamos al select con los data-* correctos
          if (!$(`#producto-select option[value="${p.productos_id}"]`).length) {
            const isvId   = Number(p.isv_id_aplicado || 0);
            const isvRate = Number(p.isv_rate_decimal || 0);
            const isvLbl  = (p.isv_label || '');

            $('#producto-select').append(
              `<option value="${p.productos_id}"
                       data-precio="${p.precio_venta}"
                       data-isvventa="${p.isv_venta}"
                       data-isvid="${isvId}"
                       data-isvrate="${isvRate}"
                       data-isvlabel="${isvLbl}">
                 ${p.nombre} - L. ${formatter.format(p.precio_venta)}${isvLbl ? ' · ' + isvLbl : ''}
               </option>`
            ).selectpicker('refresh');
          }

          $('#producto-select').val(p.productos_id).selectpicker('refresh');
          agregarProducto();
          $('#codigo-barra').val('').focus();
        } else {
          showNotify("warning", "Advertencia", "Producto no encontrado");
        }
      },
      error: function () {
        showNotify("error", "Error", "No se pudo buscar el producto");
      }
    });
  }

  // ===============================
  // 8. DESCUENTOS
  // ===============================

  function actualizarDescuentoDesdeMonto() {
    const monto = parseFloat($('#nuevo-descuento-monto').val()) || 0;
    const porcentaje = currentProductPrice > 0 ? (monto / currentProductPrice) * 100 : 0;
    $('#nuevo-descuento-porcentaje').val(porcentaje.toFixed(2));
    $('#descuento-total').val(`L. ${formatter.format(monto)} (${porcentaje.toFixed(2)}%)`);
  }

  function actualizarDescuentoDesdePorcentaje() {
    const porcentaje = parseFloat($('#nuevo-descuento-porcentaje').val()) || 0;
    const monto = (porcentaje / 100) * currentProductPrice;
    $('#nuevo-descuento-monto').val((monto || 0).toFixed(2));
    $('#descuento-total').val(`L. ${formatter.format(monto || 0)} (${porcentaje.toFixed(2)}%)`);
  }

  function guardarDescuento() {
    const index = $('#producto-index').val();
    const nuevoDescuento = parseFloat($('#nuevo-descuento-monto').val()) || 0;

    if (index !== null && productosAgregados[index]) {
      const precioTotal = (productosAgregados[index].precio * productosAgregados[index].cantidad) || 0;
      if (nuevoDescuento > precioTotal) {
        showNotify("error", "Error", "El descuento no puede ser mayor al precio total");
        return;
      }

      productosAgregados[index].descuento = nuevoDescuento;
      actualizarListaProductos();
      calcularTotales();

      $('#editarDescuentoModal').modal('hide');
      showNotify("success", "Éxito", "Descuento actualizado correctamente");
    } else {
      showNotify("error", "Error", "No se pudo actualizar el descuento: producto no encontrado");
    }
  }

  // Eventos del modal de descuento
  $('#editarDescuentoModal').on('shown.bs.modal', function () {
    $('#descuento-tab .nav-link').first().trigger('click');
    $('#nuevo-descuento-monto').trigger('focus');
  });

  $('#editarDescuentoModal').on('hidden.bs.modal', function () {
    $('#editar-descuento-form')[0].reset();
    $('#descuento-total').val('');
    $('#producto-index').val('');
  });

  // ===============================
  // 9. INTERVALOS
  // ===============================
  // Cada 5s: comprobar caja y contador SAR
  setInterval(() => {
    verificarAperturaCaja();
    getTotalFacturasDisponibles();
  }, 5000);

  // ===============================
  // 10. STUBS OPCIONALES
  // ===============================
  // (Ninguno: tus globales showNotify/getCajero ya existen)
});
</script>