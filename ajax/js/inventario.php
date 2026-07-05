<script>
var registro = false;

/* =========================================================
   CONFIGURACIÓN RÁPIDA INVENTARIO
========================================================= */
const INVENTARIO_OPERACION_KEY = 'inventario_operacion_recordada';
const BODEGA_PRINCIPAL_ID = '1';

/* =========================================================
   UTILS
========================================================= */
function toNumber(val){
  if (val == null) return 0;
  if (typeof val === 'number') return val;
  return parseFloat(String(val).replace(/[^\d.-]/g,'')) || 0;
}

function formatNumber(n){
  try{
    return Number(n).toLocaleString('es-HN',{minimumFractionDigits:2, maximumFractionDigits:2});
  }catch(e){
    var s = (Number(n)||0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}

function movimientosValor(valor, textoDefault) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return textoDefault !== undefined ? textoDefault : 'No registrado';
  }

  return String(valor).trim();
}

function movimientosEscape(valor) {
  return movimientosValor(valor, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function movimientosBadgeNumero(valor, tipo) {
  var numero = toNumber(valor);
  var clase = 'movimientos-number-badge';

  if (tipo === 'anterior') {
    clase += numero > 0 ? ' movimientos-badge-anterior-ok' : ' movimientos-badge-anterior-empty';
  }

  if (tipo === 'entrada') {
    clase += numero > 0 ? ' movimientos-badge-entrada-ok' : ' movimientos-badge-neutral';
  }

  if (tipo === 'salida') {
    clase += numero > 0 ? ' movimientos-badge-salida-ok' : ' movimientos-badge-neutral';
  }

  if (tipo === 'saldo') {
    clase += numero >= 0 ? ' movimientos-badge-saldo-ok' : ' movimientos-badge-saldo-danger';
  }

  return '<span class="' + clase + '">' + formatNumber(numero) + '</span>';
}

function movimientosActualizarResumen(rows) {
  rows = rows || [];

  var totalEntrada = 0;
  var totalSalida = 0;
  var totalBalance = 0;

  rows.forEach(function(item) {
    totalEntrada += toNumber(item.entrada);
    totalSalida += toNumber(item.salida);
  });

  totalBalance = totalEntrada - totalSalida;

  $('#movimientos_total_registros').text(rows.length);
  $('#movimientos_total_entrada').text(formatNumber(totalEntrada));
  $('#movimientos_total_salida').text(formatNumber(totalSalida));
  $('#movimientos_total_balance').text(formatNumber(totalBalance));
}

function construirHeaderDataTableMovimientos() {
  var $tabla = $("#dataTablaMovimientos");

  $tabla.find('thead').remove();

  $tabla.prepend(
    '<thead>' +
      '<tr>' +
        '<th>Movimiento</th>' +
        '<th>Producto</th>' +
        '<th>Documento / Lote</th>' +
        '<th>Cliente / Bodega</th>' +
        '<th>Anterior</th>' +
        '<th>Entrada</th>' +
        '<th>Salida</th>' +
        '<th>Saldo</th>' +
      '</tr>' +
    '</thead>'
  );
}

function badgeRender(colorFn){
  return function(data, type){
    var n = toNumber(data);
    if (type === 'display'){
      var color = colorFn(n);
      return '<span style="border:2px solid '+color+';border-radius:12px;padding:5px 10px;color:'+color+';font-weight:bold;">'
             + formatNumber(n) + '</span>';
    }
    return n;
  };
}

function limpiarSaldoProductoMovimiento() {
  var $card = $('#saldoProductoMovimientoCard');

  if (!$card.length) return;

  $card
    .removeClass('saldo-producto-card-ok saldo-producto-card-warning saldo-producto-card-danger saldo-producto-card-loading')
    .addClass('saldo-producto-card-empty');

  $('#saldoProductoMovimientoIcon')
    .removeClass()
    .addClass('fas fa-box');

  $('#saldoProductoMovimientoValor').text('Seleccione o escanee un producto');
  $('#saldoProductoMovimientoEstado').text('Sin producto');
  $('#saldoProductoMovimientoDetalle').text('El saldo se mostrará según el producto, bodega y lote seleccionado.');
}

function mostrarCargandoSaldoProductoMovimiento() {
  var $card = $('#saldoProductoMovimientoCard');

  if (!$card.length) return;

  $card
    .removeClass('saldo-producto-card-empty saldo-producto-card-ok saldo-producto-card-warning saldo-producto-card-danger')
    .addClass('saldo-producto-card-loading');

  $('#saldoProductoMovimientoIcon')
    .removeClass()
    .addClass('fas fa-spinner fa-spin');

  $('#saldoProductoMovimientoValor').text('Consultando saldo...');
  $('#saldoProductoMovimientoEstado').text('Espere');
  $('#saldoProductoMovimientoDetalle').text('Validando existencia disponible del producto seleccionado.');
}

function pintarSaldoProductoMovimiento(saldo, detalle, estadoServidor) {
  var $card = $('#saldoProductoMovimientoCard');
  var saldoNumero = toNumber(saldo);

  if (!$card.length) return;

  $card.removeClass('saldo-producto-card-empty saldo-producto-card-ok saldo-producto-card-warning saldo-producto-card-danger saldo-producto-card-loading');

  $('#saldoProductoMovimientoIcon').removeClass();

  if (saldoNumero <= 0) {
    $card.addClass('saldo-producto-card-danger');
    $('#saldoProductoMovimientoIcon').addClass('fas fa-exclamation-triangle');
    $('#saldoProductoMovimientoEstado').text(estadoServidor || 'Sin saldo');
  } else if (saldoNumero <= 5) {
    $card.addClass('saldo-producto-card-warning');
    $('#saldoProductoMovimientoIcon').addClass('fas fa-exclamation-circle');
    $('#saldoProductoMovimientoEstado').text(estadoServidor || 'Saldo bajo');
  } else {
    $card.addClass('saldo-producto-card-ok');
    $('#saldoProductoMovimientoIcon').addClass('fas fa-check-circle');
    $('#saldoProductoMovimientoEstado').text(estadoServidor || 'Disponible');
  }

  $('#saldoProductoMovimientoValor').text(formatNumber(saldoNumero) + ' unidades');

  $('#saldoProductoMovimientoDetalle').text(
    detalle || 'Saldo actual según producto, bodega y lote seleccionado.'
  );
}

function mostrarErrorSaldoProductoMovimiento(mensaje) {
  var $card = $('#saldoProductoMovimientoCard');

  if (!$card.length) return;

  $card
    .removeClass('saldo-producto-card-empty saldo-producto-card-ok saldo-producto-card-warning saldo-producto-card-loading')
    .addClass('saldo-producto-card-danger');

  $('#saldoProductoMovimientoIcon')
    .removeClass()
    .addClass('fas fa-times-circle');

  $('#saldoProductoMovimientoValor').text('No disponible');
  $('#saldoProductoMovimientoEstado').text('Error');
  $('#saldoProductoMovimientoDetalle').text(mensaje || 'Hubo un problema al consultar el saldo del producto.');
}

function consultarSaldoProductoMovimiento() {
  var producto_id = $('#formMovimientos #movimiento_producto').val();
  var almacen_id = $('#formMovimientos #almacen_modal').val();
  var lote_id = $('#formMovimientos #movimiento_lote').val();

  if (!producto_id) {
    limpiarSaldoProductoMovimiento();
    return;
  }

  mostrarCargandoSaldoProductoMovimiento();

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/productos/getSaldoProductoMovimiento.php',
    dataType: 'json',
    data: {
      producto_id: producto_id,
      almacen_id: almacen_id,
      lote_id: lote_id
    },
    success: function(response) {
      if (response && response.success) {
        pintarSaldoProductoMovimiento(response.saldo, response.detalle, response.estado);
      } else {
        mostrarErrorSaldoProductoMovimiento(
          response && response.message ? response.message : 'No se pudo obtener el saldo del producto.'
        );
      }
    },
    error: function() {
      mostrarErrorSaldoProductoMovimiento('Hubo un problema en la comunicación con el servidor.');
    }
  });
}


/* =========================================================
   CONTROL DE FOCO DEL MODAL DE MOVIMIENTOS
   ---------------------------------------------------------
   Evita que el buscador del DataTable robe el cursor cuando
   el modal #modal_movimientos está abierto.
========================================================= */
var MOVIMIENTOS_FOCUS_DEFAULT = '#formMovimientos #produto_barcode';
var MOVIMIENTOS_ULTIMO_FOCUS = null;
var MOVIMIENTO_CONFIRMACION_ABIERTA = false;
var MOVIMIENTO_GUARDANDO_AJAX = false;
var TABLE_PRODUCTOS_MOVIMIENTO = null;

function modalMovimientosAbierto() {
  return $('#modal_movimientos').length > 0 && $('#modal_movimientos').hasClass('show');
}

function elementoDentroDeModalMovimientos(elemento) {
  return $(elemento).closest('#modal_movimientos').length > 0;
}

function otroModalMovimientosAbiertoConElemento(elemento) {
  var $modalVisible = $(elemento).closest('.modal.show');

  if ($modalVisible.length === 0) {
    return false;
  }

  return $modalVisible.attr('id') !== 'modal_movimientos';
}

function obtenerElementoFocusMovimientos(selector) {
  var $elemento = selector ? $(selector) : $();

  if ($elemento.length > 0 && $elemento.is(':visible') && !$elemento.prop('disabled') && !$elemento.prop('readonly')) {
    return $elemento.first();
  }

  $elemento = $(MOVIMIENTOS_FOCUS_DEFAULT);

  if ($elemento.length > 0 && $elemento.is(':visible') && !$elemento.prop('disabled') && !$elemento.prop('readonly')) {
    return $elemento.first();
  }

  return $('#modal_movimientos').find('input:visible:not(:disabled):not([readonly]), textarea:visible:not(:disabled):not([readonly]), button:visible:not(:disabled), select:visible:not(:disabled)').first();
}

function enfocarMovimientoProducto(forzarSelect) {
  var $elemento = obtenerElementoFocusMovimientos(MOVIMIENTOS_FOCUS_DEFAULT);

  if ($elemento.length === 0) {
    return;
  }

  $elemento.trigger('focus');

  if (forzarSelect === true && typeof $elemento.select === 'function') {
    $elemento.select();
  }
}

function restaurarFocusModalMovimientos() {
  if (!modalMovimientosAbierto()) {
    return;
  }

  var $elemento = obtenerElementoFocusMovimientos(MOVIMIENTOS_ULTIMO_FOCUS);

  if ($elemento.length === 0) {
    return;
  }

  $elemento.trigger('focus');
}

function registrarControlFocusModalMovimientos() {
  if (window.__controlFocusModalMovimientosRegistrado) {
    return;
  }

  window.__controlFocusModalMovimientosRegistrado = true;

  $(document)
    .off('focusin.controlFocusMovimientos', '#modal_movimientos input, #modal_movimientos textarea, #modal_movimientos button, #modal_movimientos select, #modal_movimientos .bootstrap-select button')
    .on('focusin.controlFocusMovimientos', '#modal_movimientos input, #modal_movimientos textarea, #modal_movimientos button, #modal_movimientos select, #modal_movimientos .bootstrap-select button', function () {
      if ($(this).is(':visible') && !$(this).prop('disabled')) {
        MOVIMIENTOS_ULTIMO_FOCUS = this;
      }
    });

  document.addEventListener('focusin', function (e) {
    if (!modalMovimientosAbierto()) {
      return;
    }

    if (elementoDentroDeModalMovimientos(e.target)) {
      return;
    }

    if (otroModalMovimientosAbiertoConElemento(e.target)) {
      return;
    }

    setTimeout(function () {
      restaurarFocusModalMovimientos();
    }, 20);
  }, true);
}

function enfocarBusquedaTablaMovimientosSiAplica() {
  if (modalMovimientosAbierto()) {
    setTimeout(function () {
      restaurarFocusModalMovimientos();
    }, 80);
    return;
  }

  $('#buscar').focus();
}

/* =========================================================
   INICIO SIN DOCUMENT.READY
========================================================= */
(function () {
  function inicializarMovimientosProductos() {
    funciones();
    listar_movimientos();

    $('#movimientos, #registroMovimientos').css('cursor','pointer');

    $('#form_main_movimientos #search').off('click.movimientos');
    $('#form_main_movimientos #search').on('click.movimientos', function(e){
      e.preventDefault();
      listar_movimientos();
    });

    $('#form_main_movimientos').off('reset.movimientos');
    $('#form_main_movimientos').on('reset.movimientos', function(){
      var form = this;

      setTimeout(function(){
        $(form).find('.selectpicker').val('').selectpicker('refresh');
        listar_movimientos();
      }, 100);
    });

    $('#form_main_movimientos #categoria_id, #form_main_movimientos #fechai, #form_main_movimientos #fechaf, #form_main_movimientos #almacen, #producto_movimiento_filtro, #cliente_movimiento_filtro, #inventario_tipo_productos_id')
      .off('change.movimientosFiltro')
      .on('change.movimientosFiltro', listar_movimientos);

    $('#form_main_movimientos #inventario_tipo_productos_id').off('change.tipoProductoMovimiento');
    $('#form_main_movimientos #inventario_tipo_productos_id').on('change.tipoProductoMovimiento', function(){
      var tipo = $('#form_main_movimientos #inventario_tipo_productos_id').val() || 1;
      getProductosMovimientos(tipo);
    });

    $('#formMovimientos #movimientos_tipo_producto_id').off('change.tipoProductoMovimientoModal');
    $('#formMovimientos #movimientos_tipo_producto_id').on('change.tipoProductoMovimientoModal', function(){
      var tipo = $('#formMovimientos #movimientos_tipo_producto_id').val() || 1;
      getProductosMovimientos(tipo);
    });

    $("#modal_buscar_productos_movimientos").off('shown.bs.modal.movimientos');
    $("#modal_buscar_productos_movimientos").on('shown.bs.modal.movimientos', function(){
      $(this).find('#formulario_busqueda_productos_movimientos #buscar').focus();
    });

    $("#modal_movimientos").off('shown.bs.modal.movimientos');
    $("#modal_movimientos").on('shown.bs.modal.movimientos', function(){
      seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
      cargarOperacionRecordada();
      limpiarSaldoProductoMovimiento();
      registrarControlFocusModalMovimientos();

      MOVIMIENTOS_ULTIMO_FOCUS = $(MOVIMIENTOS_FOCUS_DEFAULT).get(0) || null;

      setTimeout(function(){
        enfocarMovimientoProducto(true);
      }, 120);

      setTimeout(function(){
        enfocarMovimientoProducto(true);
      }, 400);
    });

    $("#modal_transferencia_producto").off('shown.bs.modal.movimientos');
    $("#modal_transferencia_producto").on('shown.bs.modal.movimientos', function(){
      $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });

    registrarControlFocusModalMovimientos();
    inicializarEventosMovimientoRapido();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarMovimientosProductos);
  } else {
    inicializarMovimientosProductos();
  }
})();

function funciones() {
  getTipoProductos();
  getTipoProductosModal();
  getProductoOperacion();
  getClientes();
  getClientesModal();
  getProductosMovimientos(1);
  getAlmacen();
  getAlmacenModal();
}

/* =========================================================
   DATATABLE MOVIMIENTOS
========================================================= */
var listar_movimientos = function () {
  var tipo_producto_id = $('#form_main_movimientos #inventario_tipo_productos_id').val();
  var fechai  = $("#form_main_movimientos #fechai").val();
  var fechaf  = $("#form_main_movimientos #fechaf").val();
  var bodega  = $("#form_main_movimientos #almacen").val();
  var producto= $("#producto_movimiento_filtro").val();
  var cliente = $('#cliente_movimiento_filtro').val();

  try{
    var _dtKey = 'DataTables_' + 'dataTablaMovimientos' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  }catch(e){}

  if ($.fn.DataTable.isDataTable("#dataTablaMovimientos")) {
    $("#dataTablaMovimientos").DataTable().clear().destroy();
  }

  construirHeaderDataTableMovimientos();

  var table_movimientos = $("#dataTablaMovimientos").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,
    autoWidth: false,
    scrollX: false,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableMovimientos.php",
      data: { tipo_producto_id, fechai, fechaf, bodega, producto, cliente },
      dataSrc: function(json) {
        var rows = [];

        if (json && json.data) {
          rows = json.data;
        }

        movimientosActualizarResumen(rows);

        return rows;
      }
    },
    columns: [
      {
        data: null,
        className: "align-middle movimientos-info-cell",
        render: function(data, type, row) {
          var fecha = movimientosEscape(row.fecha_registro);
          var documento = movimientosEscape(row.documento);
          var comentario = movimientosEscape(movimientosValor(row.comentario, 'Sin comentario'));

          if (type !== 'display') {
            return fecha + ' ' + documento + ' ' + comentario;
          }

          return '' +
            '<div class="movimientos-main-box">' +
              '<div class="movimientos-main-icon">' +
                '<i class="fas fa-exchange-alt"></i>' +
              '</div>' +
              '<div class="movimientos-main-info">' +
                '<h6 class="movimientos-fecha">' + fecha + '</h6>' +
                '<div class="movimientos-documento">' +
                  '<i class="fas fa-file-alt mr-1"></i>' + documento +
                '</div>' +
                '<div class="movimientos-comentario">' + comentario + '</div>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle movimientos-producto-cell",
        render: function(data, type, row) {
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = row.image ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + row.image) : defaultImageUrl;
          var producto = movimientosEscape(row.producto);
          var medida = movimientosEscape(row.medida);
          var barcode = movimientosEscape(movimientosValor(row.barCode, 'Sin código'));

          if (type !== 'display') {
            return producto + ' ' + medida + ' ' + barcode;
          }

          return '' +
            '<div class="movimientos-product-box">' +
              '<div class="movimientos-product-img-box">' +
                '<a href="#" class="iv-trigger movimientos-zoom-trigger" ' +
                    'data-iv-src="' + imageUrl + '" ' +
                    'data-iv-fallback="' + defaultImageUrl + '" ' +
                    'data-iv-title="' + producto + '">' +
                    '<img class="movimientos-product-img table-image" src="' + imageUrl + '" alt="' + producto + '" loading="lazy" onerror="this.onerror=null;this.src=\'' + defaultImageUrl + '\';">' +
                '</a>' +
              '</div>' +
              '<div class="movimientos-product-info">' +
                '<h6 class="movimientos-product-name">' + producto + '</h6>' +
                '<div class="movimientos-product-meta">' +
                  '<span><i class="fas fa-ruler-combined mr-1"></i>' + medida + '</span>' +
                  '<span><i class="fas fa-barcode mr-1"></i>' + barcode + '</span>' +
                '</div>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle movimientos-lote-cell",
        render: function(data, type, row) {
          var lote = movimientosEscape(movimientosValor(row.numero_lote, 'No especificado'));
          var documento = movimientosEscape(row.documento);
          var loteClass = row.numero_lote ? 'movimientos-lote-ok' : 'movimientos-lote-empty';

          if (type !== 'display') {
            return lote + ' ' + documento;
          }

          return '' +
            '<div class="movimientos-detail-list">' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-lote"><i class="fas fa-box"></i></span>' +
                '<span><strong>Lote:</strong> <span class="movimientos-lote-badge ' + loteClass + '">' + lote + '</span></span>' +
              '</div>' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-doc"><i class="fas fa-file-alt"></i></span>' +
                '<span><strong>Documento:</strong> ' + documento + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle movimientos-cliente-cell",
        render: function(data, type, row) {
          var cliente = movimientosEscape(movimientosValor(row.cliente, 'Sin cliente'));
          var bodega = movimientosEscape(movimientosValor(row.bodega, 'Sin bodega'));

          if (type !== 'display') {
            return cliente + ' ' + bodega;
          }

          return '' +
            '<div class="movimientos-detail-list">' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-cliente"><i class="fas fa-user"></i></span>' +
                '<span><strong>Cliente:</strong> ' + cliente + '</span>' +
              '</div>' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-bodega"><i class="fas fa-warehouse"></i></span>' +
                '<span><strong>Bodega:</strong> ' + bodega + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: "saldo_anterior",
        className: "text-right align-middle movimientos-numero-cell",
        render: function(data, type) {
          if (type !== 'display') return toNumber(data);
          return movimientosBadgeNumero(data, 'anterior');
        }
      },
      {
        data: "entrada",
        className: "text-right align-middle movimientos-numero-cell",
        render: function(data, type) {
          if (type !== 'display') return toNumber(data);
          return movimientosBadgeNumero(data, 'entrada');
        }
      },
      {
        data: "salida",
        className: "text-right align-middle movimientos-numero-cell",
        render: function(data, type) {
          if (type !== 'display') return toNumber(data);
          return movimientosBadgeNumero(data, 'salida');
        }
      },
      {
        data: "saldo",
        className: "text-right align-middle movimientos-numero-cell",
        render: function(data, type) {
          if (type !== 'display') return toNumber(data);
          return movimientosBadgeNumero(data, 'saldo');
        }
      }
    ],
    order: [[0, 'desc']],
    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,
    columnDefs: [
      {
        width: "16%",
        targets: 0,
        className: "align-middle movimientos-info-cell"
      },
      {
        width: "26%",
        targets: 1,
        className: "align-middle movimientos-producto-cell"
      },
      {
        width: "17%",
        targets: 2,
        className: "align-middle movimientos-lote-cell"
      },
      {
        width: "17%",
        targets: 3,
        className: "align-middle movimientos-cliente-cell"
      },
      {
        width: "6%",
        targets: 4,
        className: "text-right align-middle movimientos-numero-cell"
      },
      {
        width: "6%",
        targets: 5,
        className: "text-right align-middle movimientos-numero-cell"
      },
      {
        width: "6%",
        targets: 6,
        className: "text-right align-middle movimientos-numero-cell"
      },
      {
        width: "6%",
        targets: 7,
        className: "text-right align-middle movimientos-numero-cell"
      }
    ],
    footerCallback: function(){
      var api = this.api();
      var sum = function(idx) {
        return api.column(idx, {page:'current'}).data().reduce(function(a,b){
          return toNumber(a) + toNumber(b);
        }, 0);
      };

      var totalSaldoAnterior = sum(4);
      var totalEntrada = sum(5);
      var totalSalida = sum(6);
      var total = sum(7);

      $('#anterior-footer-movimiento').html(formatNumber(totalSaldoAnterior));
      $('#entrada-footer-movimiento').html(formatNumber(totalEntrada));
      $('#salida-footer-movimiento').html(formatNumber(totalSalida));
      $('#total-footer-movimiento').html(formatNumber(total));
    },
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Movimientos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function(){ listar_movimientos(); }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
        titleAttr: 'Agregar Movimientos',
        className: 'table_crear btn btn-primary ocultar',
        action: function(){ modal_movimientos(); }
      },
      {
        text: '<i class="fas fa-balance-scale fa-lg"></i> Ajuste Inventario',
        titleAttr: 'Ajuste de Inventario por Conteo Físico',
        className: 'table_crear btn btn-warning ocultar',
        action: function(){ modal_ajuste_inventario(); }
      },
      {
        text: '<i class="fas fa-clipboard-check fa-lg"></i> Auditoría Ajustes',
        titleAttr: 'Auditoría de Ajustes de Inventario',
        className: 'table_crear btn btn-info ocultar',
        action: function(){ modal_consultar_inventario(); }
      },   
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Movimientos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: { columns: [0,1,2,3,4,5,6,7] }
      },
      {
        extend: 'pdf',
        footer: true,
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Reporte Movimientos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: { columns: [0,1,2,3,4,5,6,7] },
        customize: function(doc){
          if (typeof imagen !== 'undefined' && imagen){
            doc.content.splice(0,0,{ image:imagen, width:100, height:45, margin:[0,0,0,12] });
          }
        }
      }
    ],
    initComplete: function(){
      this.api().order([0,'desc']).draw();
      enfocarBusquedaTablaMovimientosSiAplica();
    },
    drawCallback: function(){
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  $('#dataTablaMovimientos').off('draw.dt.movimientosTooltip');
  $('#dataTablaMovimientos').on('draw.dt.movimientosTooltip', function(){
    $('[data-toggle="tooltip"]').tooltip();
  });

  table_movimientos.search('').draw();
  enfocarBusquedaTablaMovimientosSiAplica();
};

/* =========================================================
   TRANSFERIR PRODUCTO/BODEGA
========================================================= */
var transferencia_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_transferencia");
  $(tbody).on("click", "button.table_transferencia", function() {
    var data = table.row($(this).parents("tr")).data();
    $('#formTransferencia #productos_id').val(data.productos_id);
    $('#formTransferencia #nameProduct').html(data.producto);
    $('#modal_transferencia_producto').modal({ show:true, keyboard:false, backdrop:'static' });
  });
};

$("#putEditarBodega").off('click.movimientosTransferencia');
$("#putEditarBodega").on('click.movimientosTransferencia', function() {
  var form = $("#formTransferencia");
  var respuesta = form.children('.RespuestaAjax');
  var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';

  $.ajax({
    type: 'POST',
    url: url,
    data: $('#formTransferencia').serialize(),
    beforeSend: function(){
      $('#modal_transferencia_producto').modal({show:false, keyboard:false, backdrop:'static'});
    },
    success: function(data){
      $('#modal_transferencia_producto').modal('toggle');
      respuesta.html(data);
    }
  });
});

/* =========================================================
   COMBOS
========================================================= */
function seleccionarBodegaPrincipal(selector) {
  var $select = $(selector);

  if (!$select.length) return;

  if ($select.find('option[value="' + BODEGA_PRINCIPAL_ID + '"]').length) {
    $select.val(BODEGA_PRINCIPAL_ID);
  } else {
    var primerValor = $select.find('option:eq(1)').val() || $select.find('option:first').val();
    if (primerValor) $select.val(primerValor);
  }

  $select.selectpicker('refresh');
}

function getAlmacen() {
  var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#form_main_movimientos #almacen').html(data).selectpicker('refresh');
      $('#formMovimientoInventario #almacen_modal').html(data).selectpicker('refresh');

      seleccionarBodegaPrincipal('#form_main_movimientos #almacen');
      seleccionarBodegaPrincipal('#formMovimientoInventario #almacen_modal');
    }
  });
}

function getAlmacenModal() {
  var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#formMovimientos #almacen_modal').html(data).selectpicker('refresh');
      seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
    }
  });
}

$('#formMovimientos #movimiento_producto').off('changed.bs.select.saldoMovimiento change.saldoMovimiento');
$('#formMovimientos #movimiento_producto').on('changed.bs.select.saldoMovimiento change.saldoMovimiento', function(){
  var producto_id = $(this).val();

  getLotesProductos(producto_id);

  setTimeout(function(){
    consultarSaldoProductoMovimiento();
  }, 350);
});

$('#formMovimientos #almacen_modal').off('changed.bs.select.saldoMovimiento change.saldoMovimiento');
$('#formMovimientos #almacen_modal').on('changed.bs.select.saldoMovimiento change.saldoMovimiento', function(){
  consultarSaldoProductoMovimiento();
});

$('#formMovimientos #movimiento_lote').off('changed.bs.select.saldoMovimiento change.saldoMovimiento');
$('#formMovimientos #movimiento_lote').on('changed.bs.select.saldoMovimiento change.saldoMovimiento', function(){
  consultarSaldoProductoMovimiento();
});

function getLotesProductos(producto_id){
  var url = '<?php echo SERVERURL;?>core/getLotesProductos.php';

  $.ajax({
    type:"POST",
    url: url,
    data:{producto_id: producto_id},
    async:true,
    success:function(data){
      $('#formMovimientos #movimiento_lote').html(data).selectpicker('refresh');
    }
  });
}

function getTipoProductos(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#form_main_movimientos #inventario_tipo_productos_id').html(data).selectpicker('refresh');
    }
  });
}

function getTipoProductosModal(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientosModal.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#formMovimientos #movimientos_tipo_producto_id').html(data).selectpicker('refresh');
    }
  });
}

function getProductoOperacion(){
  var url = '<?php echo SERVERURL;?>core/getOperacion.php';

  $.ajax({
    type:"POST",
    url: url,
    success:function(data){
      $('#formMovimientoInventario #movimiento_producto').html(data).selectpicker('refresh');
    }
  });
}

function getProductosMovimientos(tipo_producto_id, callback){
  var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';

  $.ajax({
    type:"POST",
    url: url,
    data:'tipo_producto_id='+tipo_producto_id,
    success:function(data){
      $('#form_main_movimientos #producto_movimiento_filtro').html(data).selectpicker('refresh');
      $('#formMovimientos #movimiento_producto').html(data).selectpicker('refresh');

      if (typeof callback === 'function') {
        callback(data);
      }
    }
  });
}

function getClientes(){
  var url = '<?php echo SERVERURL;?>core/getClientesHostProductos.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#form_main_movimientos #cliente_movimiento_filtro').html(data).selectpicker('refresh');
      $('#formMovimientoInventario #cliente_movimientos').html(data).selectpicker('refresh');
    }
  });
}

function getClientesModal(){
  var url = '<?php echo SERVERURL;?>core/getClientesHostProductosModal.php';

  $.ajax({
    type:"POST",
    url: url,
    async:true,
    success:function(data){
      $('#formMovimientos #cliente_movimientos').html(data).selectpicker('refresh');
    }
  });
}

/* =========================================================
   RECORDAR OPERACIÓN
========================================================= */
function guardarOperacionRecordada() {
  var operacion = $("input[name='movimiento_operacion']:checked").val();

  if (operacion === 'entrada' || operacion === 'salida') {
    localStorage.setItem(INVENTARIO_OPERACION_KEY, operacion);
  }
}

function cargarOperacionRecordada() {
  var operacion = localStorage.getItem(INVENTARIO_OPERACION_KEY);

  if (operacion !== 'entrada' && operacion !== 'salida') {
    operacion = 'entrada';
    localStorage.setItem(INVENTARIO_OPERACION_KEY, operacion);
  }

  $('#formMovimientos input[name="movimiento_operacion"]').prop('checked', false);
  $('#formMovimientos #' + operacion).prop('checked', true);

  aplicarOperacionMovimiento(operacion);
}

function aplicarOperacionMovimiento(tipoOperacion) {
  $('#formMovimientos #cliente_movimientos').prop('disabled', tipoOperacion !== 'salida');

  if (tipoOperacion !== 'salida') {
    $('#formMovimientos #cliente_movimientos').val('').selectpicker('refresh');
  } else {
    $('#formMovimientos #cliente_movimientos').selectpicker('refresh');
  }

  $('#formMovimientos #proceso_movimientos').val(
    tipoOperacion === 'entrada' ? 'Operación: Entrada' : 'Operación: Salida'
  );

  actualizarLeyendaOperacionMovimiento(tipoOperacion);
}

function actualizarLeyendaOperacionMovimiento(tipoOperacion) {
  var $info = $('#movimientoOperacionInfo');
  var $icon = $('#movimientoOperacionIcon');
  var $titulo = $('#movimientoOperacionTitulo');
  var $descripcion = $('#movimientoOperacionDescripcion');

  if (!$info.length) return;

  $info.removeClass('movimiento-operacion-entrada movimiento-operacion-salida');

  if (tipoOperacion === 'salida') {
    $info.addClass('movimiento-operacion-salida');

    $icon
      .removeClass()
      .addClass('fas fa-sign-out-alt');

    $titulo.text('Salida de producto');

    $descripcion.text('Se descontará inventario de la bodega seleccionada. Cliente requerido para salidas.');
  } else {
    $info.addClass('movimiento-operacion-entrada');

    $icon
      .removeClass()
      .addClass('fas fa-sign-in-alt');

    $titulo.text('Entrada de producto');

    $descripcion.text('Se registrará una entrada al inventario seleccionado.');
  }
}


/* =========================================================
   BUSCADOR GENERAL DE PRODUCTOS PARA MOVIMIENTOS
   ---------------------------------------------------------
   Modal: #modal_buscar_productos_movimientos_general
   Tabla: #DatatableProductosBusquedaMovimiento
   Fuente: core/llenarDataTableProductosFacturas.php
========================================================= */
function abrirModalBuscarProductosMovimiento() {
  if ($('#modal_buscar_productos_movimientos_general').length === 0) {
    showNotify('error', 'Modal no encontrado', 'No existe el modal de búsqueda de productos para movimientos.');
    return;
  }

  cargarDataTableProductosMovimiento();

  $('#modal_buscar_productos_movimientos_general').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function renderSaldoProductoBusquedaMovimiento(data, type) {
  var cantidad = parseFloat(data || 0);

  if (isNaN(cantidad)) {
    cantidad = 0;
  }

  if (type !== 'display') {
    return cantidad;
  }

  var number = $.fn.dataTable.render.number(',', '.', 2, '').display(cantidad);
  var color = '#15803d';
  var fondo = '#dcfce7';
  var borde = '#86efac';
  var icono = 'fa-check-circle';
  var texto = 'Disponible';

  if (cantidad <= 0) {
    color = '#b91c1c';
    fondo = '#fee2e2';
    borde = '#fecaca';
    icono = 'fa-exclamation-triangle';
    texto = 'Sin saldo';
  } else if (cantidad <= 5) {
    color = '#b45309';
    fondo = '#fef3c7';
    borde = '#fde68a';
    icono = 'fa-exclamation-circle';
    texto = 'Saldo bajo';
  }

  return ''
    + '<span style="display:inline-flex;align-items:center;gap:6px;border:1px solid ' + borde + ';background:' + fondo + ';color:' + color + ';border-radius:999px;padding:6px 10px;font-weight:800;white-space:nowrap;">'
    + '  <i class="fas ' + icono + '"></i>'
    + '  <span>' + number + '</span>'
    + '</span>'
    + '<small style="display:block;color:' + color + ';font-weight:700;margin-top:3px;white-space:nowrap;">' + texto + '</small>';
}

function cargarDataTableProductosMovimiento() {
  var bodega = $('#formMovimientos #almacen_modal').val();

  if (window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA === true || $('#modal_ajuste_inventario').hasClass('show')) {
    bodega = $('#formAjusteInventario #ajuste_almacen').val();
  }

  if (bodega === '' || bodega == null) {
    bodega = 1;
  }

  if ($.fn.DataTable.isDataTable('#DatatableProductosBusquedaMovimiento')) {
    $('#DatatableProductosBusquedaMovimiento').DataTable().clear().destroy();
  }

  TABLE_PRODUCTOS_MOVIMIENTO = $('#DatatableProductosBusquedaMovimiento').DataTable({
    destroy: true,
    processing: true,
    deferRender: true,
    stateSave: true,
    bDestroy: true,
    responsive: true,
    language: idioma_español,
    lengthMenu: lengthMenu,
    dom: dom,
    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL;?>core/llenarDataTableProductosFacturas.php',
      data: {
        bodega: bodega
      }
    },
    columns: [
      {
        defaultContent: "<button class='table_view btn btn-secondary ocultar btn-seleccionar-producto-movimiento' title='Seleccionar producto'><span class='fas fa-check fa-lg'></span></button>",
        orderable: false,
        searchable: false,
        className: 'text-center align-middle'
      },
      {
        data: 'image',
        orderable: false,
        searchable: false,
        className: 'align-middle',
        render: function (data, type, row, meta) {
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = data ? '<?php echo SERVERURL;?>vistas/plantilla/img/products/' + data : defaultImageUrl;
          var safeTitle = (row && row.nombre) ? String(row.nombre).replace(/"/g, '&quot;') : 'Imagen';

          if (type !== 'display') {
            return safeTitle;
          }

          return ''
            + '<div class="d-flex align-items-center">'
            +   '<img class="table-image mr-2" src="' + imageUrl + '" alt="' + safeTitle + '" style="cursor:pointer;" onerror="this.onerror=null;this.src=\'' + defaultImageUrl + '\';">'
            +   '<button type="button" class="btn btn-light btn-icon btn-xs btn-zoom iv-trigger"'
            +     ' data-iv-src="' + imageUrl + '"'
            +     ' data-iv-fallback="' + defaultImageUrl + '"'
            +     ' data-iv-title="' + safeTitle + '"'
            +     ' title="Ver imagen grande">'
            +     '<i class="fas fa-search-plus"></i>'
            +   '</button>'
            + '</div>';
        }
      },
      {
        data: 'barCode',
        className: 'align-middle text-nowrap',
        render: function (data) {
          return movimientosEscape(data || 'Sin código');
        }
      },
      {
        data: 'nombre',
        className: 'align-middle',
        render: function (data) {
          return movimientosEscape(data || 'Sin nombre');
        }
      },
      {
        data: 'cantidad',
        className: 'align-middle text-center text-nowrap',
        render: function (data, type) {
          return renderSaldoProductoBusquedaMovimiento(data, type);
        }
      },
      {
        data: 'medida',
        className: 'align-middle text-nowrap',
        render: function (data) {
          return movimientosEscape(data || 'No registrado');
        }
      },
      {
        data: 'tipo_producto_nombre',
        className: 'align-middle text-nowrap',
        render: function (data, type, row) {
          return movimientosEscape(data || row.tipo_producto || 'No registrado');
        }
      },
      {
        data: 'precio_venta',
        className: 'align-middle text-right text-nowrap',
        render: function (data, type) {
            var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data || 0);

            if (type === 'display') {
                var color = (parseFloat(data || 0) < 0) ? 'red' : 'green';
                return '<span style="color:' + color + ';">' + number + '</span>';
            }

            return parseFloat(data || 0);
        }
    },
      {
        data: null,
        className: 'align-middle',
        render: function (data, type, row) {
          var esServicio = String(row.tipo_producto_id || '') === '2';

          if (esServicio) {
            return 'Sin bodega';
          }

          var nombreBodega = (row.almacen_facturas || '').toString().trim();
          var idBodega = (row.almacen_id == null || row.almacen_id === '') ? 0 : parseInt(row.almacen_id, 10);

          if (idBodega > 0 && nombreBodega !== '') {
            return movimientosEscape(nombreBodega);
          }

          return 'Sin bodega';
        }
      }
    ],
    order: [[3, 'asc']],
    columnDefs: [
      { width: '2%',  targets: 0 },
      { width: '17%', targets: 1 },
      { width: '17%', targets: 2 },
      { width: '10%', targets: 3 },
      { width: '10%', targets: 4 },
      { width: '10%', targets: 5 },
      { width: '12%', targets: 6 },
      { width: '12%', targets: 7 },
      { width: '12%', targets: 8 }
    ],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Productos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function () {
          cargarDataTableProductosMovimiento();
        }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
        titleAttr: 'Agregar Productos',
        className: 'table_crear btn btn-primary ocultar',
        action: function () {
          if (typeof modal_productos === 'function') {
            modal_productos();
          } else {
            showNotify('error', 'No disponible', 'No está disponible el registro de productos en esta vista.');
          }
        }
      }
    ],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function' && typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }

      $('[title]').tooltip({
        container: 'body',
        placement: 'top'
      });
    },
    initComplete: function () {
      setTimeout(function () {
        $('#DatatableProductosBusquedaMovimiento_filter input').trigger('focus').select();
      }, 250);
    }
  });

  TABLE_PRODUCTOS_MOVIMIENTO.search('').draw();

  $(document).off('click.seleccionarProductoMovimiento', '#DatatableProductosBusquedaMovimiento button.btn-seleccionar-producto-movimiento, #DatatableProductosBusquedaMovimiento button.table_view');
  $(document).on('click.seleccionarProductoMovimiento', '#DatatableProductosBusquedaMovimiento button.btn-seleccionar-producto-movimiento, #DatatableProductosBusquedaMovimiento button.table_view', function(e){
    e.preventDefault();

    if (!$.fn.DataTable.isDataTable('#DatatableProductosBusquedaMovimiento')) {
      showNotify('error', 'Error', 'La tabla de productos no está disponible.');
      return false;
    }

    var $tr = $(this).closest('tr');

    if ($tr.hasClass('child')) {
      $tr = $tr.prev();
    }

    var data = $('#DatatableProductosBusquedaMovimiento').DataTable().row($tr).data();
    aplicarProductoMovimientoSeleccionado(data);

    return false;
  });
}

function aplicarProductoMovimientoSeleccionado(row) {
  if (window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA === true || $('#modal_ajuste_inventario').hasClass('show')) {
    aplicarProductoAjusteSeleccionado(row);
    return;
  }

  if (!row || !row.productos_id) {
    showNotify('error', 'Error', 'No se pudo obtener el producto seleccionado.');
    return;
  }

  var productoId = row.productos_id;
  var tipoProductoId = row.tipo_producto_id || '';
  var barcode = row.barCode || '';
  var almacenId = row.almacen_id || '';

  $('#formMovimientos #produto_barcode').val(barcode);

  if (almacenId !== '' && parseInt(almacenId || 0, 10) > 0) {
    $('#formMovimientos #almacen_modal').val(almacenId).selectpicker('refresh');
  } else {
    seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
  }

  if (tipoProductoId !== '') {
    $('#formMovimientos #movimientos_tipo_producto_id').val(tipoProductoId).selectpicker('refresh');

    getProductosMovimientos(tipoProductoId, function () {
      $('#formMovimientos #movimiento_producto').val(productoId).selectpicker('refresh');
      getLotesProductos(productoId);

      setTimeout(function () {
        consultarSaldoProductoMovimiento();
        $('#formMovimientos #movimiento_cantidad').trigger('focus').select();
      }, 450);
    });
  } else {
    $('#formMovimientos #movimiento_producto').val(productoId).selectpicker('refresh');
    getLotesProductos(productoId);

    setTimeout(function () {
      consultarSaldoProductoMovimiento();
      $('#formMovimientos #movimiento_cantidad').trigger('focus').select();
    }, 450);
  }

  $('#modal_buscar_productos_movimientos_general').modal('hide');
}

function obtenerTextoConfirmacionMovimiento() {
  var operacion = $('input[name="movimiento_operacion"]:checked').val() || 'entrada';
  var operacionTexto = operacion === 'salida' ? 'salida' : 'entrada';
  var productoTexto = $('#formMovimientos #movimiento_producto option:selected').text() || $('#formMovimientos #produto_barcode').val() || 'Producto seleccionado';
  var cantidad = $('#formMovimientos #movimiento_cantidad').val() || '0';
  var bodegaTexto = $('#formMovimientos #almacen_modal option:selected').text() || 'Bodega seleccionada';

  return '' +
    '¿Desea registrar esta <strong>' + movimientosEscape(operacionTexto) + '</strong> de producto?<br><br>' +
    '<strong>Producto:</strong> ' + movimientosEscape(productoTexto) + '<br>' +
    '<strong>Cantidad:</strong> ' + movimientosEscape(cantidad) + '<br>' +
    '<strong>Bodega:</strong> ' + movimientosEscape(bodegaTexto);
}

function confirmarGuardarMovimientoInventarioRapido() {
  if (MOVIMIENTO_CONFIRMACION_ABIERTA === true || MOVIMIENTO_GUARDANDO_AJAX === true) {
    return false;
  }

  if (!validarMovimientoInventarioRapido()) {
    return false;
  }

  MOVIMIENTO_CONFIRMACION_ABIERTA = true;

  swal({
    title: '¿Estás seguro?',
    content: {
      element: 'span',
      attributes: {
        innerHTML: obtenerTextoConfirmacionMovimiento()
      }
    },
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'Cancelar',
        visible: true
      },
      confirm: {
        text: '¡Sí, registrar!'
      }
    },
    dangerMode: false,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function (willConfirm) {
    MOVIMIENTO_CONFIRMACION_ABIERTA = false;

    if (!willConfirm) {
      setTimeout(function () {
        $('#formMovimientos #movimiento_cantidad').trigger('focus').select();
      }, 150);

      return;
    }

    guardarMovimientoInventarioRapidoAjax();
  });

  return false;
}

function guardarMovimientoInventarioRapidoAjax() {
  var $form = $('#formMovimientos');
  var url = $form.attr('action');

  if (!url) {
    showNotify('error', 'Error', 'No se encontró la ruta para registrar el movimiento.');
    return;
  }

  if (MOVIMIENTO_GUARDANDO_AJAX === true) {
    return;
  }

  MOVIMIENTO_GUARDANDO_AJAX = true;
  guardarOperacionRecordada();

  $('#btnRegistrarMovimiento')
    .prop('disabled', true)
    .html('<i class="fas fa-spinner fa-spin fa-lg mr-1"></i> Registrando...');

  $.ajax({
    type: $form.attr('method') || 'POST',
    url: url,
    data: new FormData($form[0]),
    dataType: 'html',
    cache: false,
    contentType: false,
    processData: false
  })
  .done(function (respuesta) {
    $form.children('.RespuestaAjax').html(respuesta);

    if (typeof listar_movimientos === 'function') {
      listar_movimientos();
    }

    restaurarOperacionDespuesDeReset();
  })
  .fail(function (xhr) {
    console.log(xhr.responseText);
    showNotify('error', 'Error', 'Error de comunicación al registrar el movimiento.');
  })
  .always(function () {
    MOVIMIENTO_GUARDANDO_AJAX = false;

    $('#btnRegistrarMovimiento')
      .prop('disabled', false)
      .html('<i class="fas fa-save fa-lg mr-1"></i> Registrar Movimiento');
  });
}

/* =========================================================
   MODAL MOVIMIENTOS
========================================================= */
function limpiarFormularioMovimientoRapido() {
  $('#formMovimientos #movimientos_id').val('');
  $('#formMovimientos #produto_barcode').val('');
  $('#formMovimientos #movimiento_cantidad').val('');
  $('#formMovimientos #movimiento_comentario').val('');
  $('#formMovimientos #movimiento_fecha_vencimiento').val('');

  $('#formMovimientos #movimientos_tipo_producto_id').val('').selectpicker('refresh');
  $('#formMovimientos #movimiento_producto').val('').selectpicker('refresh');
  $('#formMovimientos #movimiento_lote').html('').selectpicker('refresh');
  $('#formMovimientos #cliente_movimientos').val('').selectpicker('refresh');

  limpiarSaldoProductoMovimiento();

  seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
  cargarOperacionRecordada();

  setTimeout(function(){
    enfocarMovimientoProducto(true);
  }, 300);
}

function modal_movimientos(){
  $('#formMovimientos').attr({
    'data-form':'save',
    'action':'<?php echo SERVERURL; ?>ajax/agregarMovimientoProductosAjax.php'
  });

  if ($('#formMovimientos')[0]) {
    $('#formMovimientos')[0].reset();
  }

  $('#formMovimientos #proceso_movimientos').val("Registro");

  funciones();

  $('#modal_movimientos').modal({
    show:true,
    keyboard:false,
    backdrop:'static'
  });
}

/* =========================================================
   NAVEGACIÓN VISTA
========================================================= */
$('#movimientos').off('click.movimientosNav');
$('#movimientos').on('click.movimientosNav', function(){
  if (registro === true){
    registro = false;
    $('#movimientos').removeClass('active');
    $('#main_inventario').show();
    $('#movimiento_inventario').hide();
    $('#registroMovimientos').addClass('active');
  }
});

$('#registroMovimientos').off('click.movimientosNav');
$('#registroMovimientos').on('click.movimientosNav', function(){
  if (registro === true){
    $('#registroMovimientos').removeClass('active');
    $('#main_inventario').hide();
    $('#movimiento_inventario').show();
    $('#movimientos').addClass('active');
  }
});

function registro_inventario(){
  registro = true;
  $('#movimiento_inventario').show();
  $('#main_inventario').hide();
  $('#registroMovimientos').removeClass('active');
  $('#movimientos').addClass('active');
}

/* =========================================================
   BÚSQUEDA POR BARCODE
========================================================= */
const BusquedaProducto = (barcode) => {
  var url = '<?php echo SERVERURL;?>core/buscar_producto.php';

  $.ajax({
    type:'POST',
    url: url,
    data:{ barcode: barcode },
    dataType:'json',
    success:function(registro){
      if (registro.success){
        $('#formMovimientos #movimientos_tipo_producto_id').val(registro.tipo_producto_id).selectpicker('refresh');
        $('#formMovimientos #movimiento_producto').val(registro.productos_id).selectpicker('refresh');

        seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');

        getLotesProductos(registro.productos_id);

        setTimeout(function(){
          consultarSaldoProductoMovimiento();
          $('#formMovimientos #movimiento_cantidad').focus().select();
        }, 450);
      }else{
        showNotify('error','Error',registro.message);
        enfocarMovimientoProducto(true);
      }
    },
    error:function(){
      showNotify('error','Error','Hubo un problema en la comunicación con el servidor');
      enfocarMovimientoProducto(true);
    }
  });
};

/* =========================================================
   VALIDACIONES
========================================================= */
function validarMovimientoInventarioRapido() {
  var operacion = $("input[name='movimiento_operacion']:checked").val();
  var barcode = $('#formMovimientos #produto_barcode').val().trim();
  var tipoProducto = $('#formMovimientos #movimientos_tipo_producto_id').val();
  var producto = $('#formMovimientos #movimiento_producto').val();
  var cantidad = toNumber($('#formMovimientos #movimiento_cantidad').val());
  var bodega = $('#formMovimientos #almacen_modal').val();

  if (!operacion) {
    showNotify('warning', 'Atención', 'Debe seleccionar una operación: Entrada o Salida');
    return false;
  }

  if (barcode === '' && !producto) {
    showNotify('warning', 'Atención', 'Debe escanear o seleccionar un producto');
    enfocarMovimientoProducto(true);
    return false;
  }

  if (!tipoProducto) {
    showNotify('warning', 'Atención', 'Debe seleccionar el tipo de producto');
    $('#formMovimientos #movimientos_tipo_producto_id').selectpicker('toggle');
    return false;
  }

  if (!producto) {
    showNotify('warning', 'Atención', 'Debe seleccionar un producto');
    $('#formMovimientos #movimiento_producto').selectpicker('toggle');
    return false;
  }

  if (cantidad <= 0) {
    showNotify('warning', 'Atención', 'La cantidad debe ser mayor a cero');
    $('#formMovimientos #movimiento_cantidad').focus().select();
    return false;
  }

  if (!bodega) {
    showNotify('warning', 'Atención', 'Debe seleccionar una bodega');
    seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
    return false;
  }

  return true;
}

/* =========================================================
   EVENTOS MODAL RÁPIDO
========================================================= */
function inicializarEventosMovimientoRapido() {
  $(document).off('change.movimientoOperacion', "input[name='movimiento_operacion']");
  $(document).on('change.movimientoOperacion', "input[name='movimiento_operacion']", function(){
    var tipoOperacion = $("input[name='movimiento_operacion']:checked").val();
    var barcode = $('#formMovimientos #produto_barcode').val().trim();

    guardarOperacionRecordada();
    aplicarOperacionMovimiento(tipoOperacion);

    enfocarMovimientoProducto(true);

    if (barcode.length > 0) {
      BusquedaProducto(barcode);
    }
  });

  $('#formMovimientos #produto_barcode').off('keypress.movimientoBarcode');
  $('#formMovimientos #produto_barcode').on('keypress.movimientoBarcode', function(event){
    if (event.which === 13){
      event.preventDefault();

      var barcode = $(this).val().trim();

      if (barcode.length === 0){
        showNotify('error','Error','Debe escanear o ingresar un código de producto');
        enfocarMovimientoProducto(true);
        return;
      }

      if ($("input[name='movimiento_operacion']:checked").length === 0){
        showNotify('error','Error','Debe seleccionar una operación: Entrada o Salida');
        return;
      }

      BusquedaProducto(barcode);
    }
  });

  $('#formMovimientos #movimiento_cantidad').off('keypress.guardarMovimientoEnter');
  $('#formMovimientos #movimiento_cantidad').on('keypress.guardarMovimientoEnter', function(event){
    if (event.which === 13){
      event.preventDefault();
      confirmarGuardarMovimientoInventarioRapido();
      return false;
    }
  });

  $('#btnRegistrarMovimiento').off('click.guardarMovimientoConfirmado');
  $('#btnRegistrarMovimiento').on('click.guardarMovimientoConfirmado', function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    confirmarGuardarMovimientoInventarioRapido();
    return false;
  });

  $('#formMovimientos').off('submit.validarMovimientoRapido');
  $('#formMovimientos').on('submit.validarMovimientoRapido', function(e){
    e.preventDefault();
    e.stopImmediatePropagation();
    confirmarGuardarMovimientoInventarioRapido();
    return false;
  });

  $('#btnBuscarProductoMovimiento').off('click.buscarProductoMovimiento');
  $('#btnBuscarProductoMovimiento').on('click.buscarProductoMovimiento', function(e){
    e.preventDefault();
    abrirModalBuscarProductosMovimiento();
    return false;
  });

  
  $('#modal_buscar_productos_movimientos_general').off('hidden.bs.modal.productosMovimientoFocus');
  $('#modal_buscar_productos_movimientos_general').on('hidden.bs.modal.productosMovimientoFocus', function(){
    if ($('#modal_movimientos').hasClass('show')) {
      setTimeout(function () {
        $('#formMovimientos #movimiento_cantidad').trigger('focus').select();
      }, 200);
    }
  });

  $('#formMovimientos').off('reset.restaurarOperacion');
  $('#formMovimientos').on('reset.restaurarOperacion', function () {
    restaurarOperacionDespuesDeReset();
  });
}

/* =========================================================
   MANTENER OPERACIÓN SELECCIONADA DESPUÉS DE REGISTRAR
========================================================= */
function restaurarOperacionDespuesDeReset() {
  setTimeout(function () {

    limpiarSaldoProductoMovimiento();

    $('#formMovimientos #movimientos_id').val('');
    $('#formMovimientos #produto_barcode').val('');
    $('#formMovimientos #movimiento_cantidad').val('');
    $('#formMovimientos #movimiento_comentario').val('');
    $('#formMovimientos #movimiento_fecha_vencimiento').val('');

    $('#formMovimientos #movimientos_tipo_producto_id').val('').selectpicker('refresh');
    $('#formMovimientos #movimiento_producto').val('').selectpicker('refresh');
    $('#formMovimientos #movimiento_lote').html('').selectpicker('refresh');
    $('#formMovimientos #cliente_movimientos').val('').selectpicker('refresh');

    seleccionarBodegaPrincipal('#formMovimientos #almacen_modal');
    cargarOperacionRecordada();

    setTimeout(function () {
      enfocarMovimientoProducto(true);
    }, 150);

  }, 300);
}

/* =========================================================
   AJUSTE DE INVENTARIO POR CONTEO FÍSICO
   ---------------------------------------------------------
   Modal: #modal_ajuste_inventario
   Form : #formAjusteInventario
   Save : core/inventario/addAjusteInventario.php
   Nota : El backend debe registrar movimiento + auditoría.
========================================================= */
var AJUSTE_INVENTARIO_GUARDANDO = false;
var AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;
window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;

function ajusteToNumber(valor) {
  if (valor === null || typeof valor === 'undefined') return 0;
  return parseFloat(String(valor).replace(/[^\d.-]/g, '')) || 0;
}

function ajusteFormatNumber(valor) {
  try {
    return Number(valor || 0).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  } catch (e) {
    return (Number(valor || 0) || 0).toFixed(2);
  }
}

function limpiarFormularioAjusteInventario() {
  var $form = $('#formAjusteInventario');

  if (!$form.length) return;

  if ($form[0]) {
    $form[0].reset();
  }

  $('#formAjusteInventario #ajuste_barcode').val('');
  $('#formAjusteInventario #ajuste_saldo_sistema').val('0');
  $('#formAjusteInventario #ajuste_saldo_visible').val('0.00');
  $('#formAjusteInventario #ajuste_conteo_fisico').val('');
  $('#formAjusteInventario #ajuste_diferencia').val('0');
  $('#formAjusteInventario #ajuste_diferencia_visible').val('0.00');
  $('#formAjusteInventario #ajuste_nueva_existencia').val('0.00');
  $('#formAjusteInventario #ajuste_tipo').val('sin_cambio');
  $('#formAjusteInventario #ajuste_fecha_vencimiento').val('');
  $('#formAjusteInventario #ajuste_comentario').val('');

  $('#formAjusteInventario #ajuste_tipo_producto_id').val('').selectpicker('refresh');
  $('#formAjusteInventario #ajuste_producto').val('').selectpicker('refresh');
  $('#formAjusteInventario #ajuste_lote').html('').selectpicker('refresh');

  $('#ajuste_resultado_info')
    .removeClass('alert-success alert-danger alert-warning alert-info')
    .addClass('alert-secondary')
    .html('<i class="fas fa-info-circle mr-1"></i> Seleccione un producto y escriba el conteo físico para calcular el ajuste.');
}

function modal_ajuste_inventario() {
  if ($('#modal_ajuste_inventario').length === 0) {
    showNotify('error', 'Modal no encontrado', 'No existe el modal de ajuste de inventario.');
    return;
  }

  limpiarFormularioAjusteInventario();

  getTipoProductosAjuste();
  getAlmacenAjuste();
  getProductosAjuste(1);

  $('#modal_ajuste_inventario').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  setTimeout(function () {
    $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
  }, 350);
}

function getTipoProductosAjuste() {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getTipoProductoMovimientosModal.php',
    success: function (data) {
      $('#formAjusteInventario #ajuste_tipo_producto_id').html(data).selectpicker('refresh');
    }
  });
}

function getAlmacenAjuste() {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getAlmacenCompras.php',
    success: function (data) {
      $('#formAjusteInventario #ajuste_almacen').html(data).selectpicker('refresh');
      seleccionarBodegaPrincipal('#formAjusteInventario #ajuste_almacen');
    }
  });
}

function getProductosAjuste(tipo_producto_id, callback) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getProductosMovimientosTipoProducto.php',
    data: {
      tipo_producto_id: tipo_producto_id || 1
    },
    success: function (data) {
      $('#formAjusteInventario #ajuste_producto').html(data).selectpicker('refresh');

      if (typeof callback === 'function') {
        callback(data);
      }
    }
  });
}

function getLotesProductosAjuste(producto_id, callback) {
  if (!producto_id) {
    $('#formAjusteInventario #ajuste_lote').html('').selectpicker('refresh');
    if (typeof callback === 'function') callback('');
    return;
  }

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getLotesProductos.php',
    data: {
      producto_id: producto_id
    },
    success: function (data) {
      $('#formAjusteInventario #ajuste_lote').html(data).selectpicker('refresh');

      if (typeof callback === 'function') {
        callback(data);
      }
    }
  });
}

function consultarSaldoProductoAjuste() {
  var producto_id = $('#formAjusteInventario #ajuste_producto').val();
  var almacen_id = $('#formAjusteInventario #ajuste_almacen').val();
  var lote_id = $('#formAjusteInventario #ajuste_lote').val();

  if (!producto_id || !almacen_id) {
    $('#formAjusteInventario #ajuste_saldo_sistema').val('0');
    $('#formAjusteInventario #ajuste_saldo_visible').val('0.00');
    calcularDiferenciaAjusteInventario();
    return;
  }

  $('#ajuste_resultado_info')
    .removeClass('alert-secondary alert-success alert-danger alert-warning')
    .addClass('alert-info')
    .html('<i class="fas fa-spinner fa-spin mr-1"></i> Consultando saldo actual del producto...');

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/productos/getSaldoProductoMovimiento.php',
    dataType: 'json',
    data: {
      producto_id: producto_id,
      almacen_id: almacen_id,
      lote_id: lote_id
    },
    success: function (response) {
      var saldo = 0;

      if (response && response.success) {
        saldo = ajusteToNumber(response.saldo);
      }

      $('#formAjusteInventario #ajuste_saldo_sistema').val(saldo);
      $('#formAjusteInventario #ajuste_saldo_visible').val(ajusteFormatNumber(saldo));

      calcularDiferenciaAjusteInventario();
    },
    error: function () {
      $('#formAjusteInventario #ajuste_saldo_sistema').val('0');
      $('#formAjusteInventario #ajuste_saldo_visible').val('0.00');

      $('#ajuste_resultado_info')
        .removeClass('alert-secondary alert-success alert-warning alert-info')
        .addClass('alert-danger')
        .html('<i class="fas fa-times-circle mr-1"></i> No se pudo consultar el saldo actual del producto.');
    }
  });
}

function calcularDiferenciaAjusteInventario() {
  var saldoSistema = ajusteToNumber($('#formAjusteInventario #ajuste_saldo_sistema').val());
  var conteoTexto = $('#formAjusteInventario #ajuste_conteo_fisico').val();

  $('#formAjusteInventario #ajuste_saldo_visible').val(ajusteFormatNumber(saldoSistema));

  if (conteoTexto === '' || conteoTexto === null || typeof conteoTexto === 'undefined') {
    $('#formAjusteInventario #ajuste_diferencia').val('0');
    $('#formAjusteInventario #ajuste_diferencia_visible').val('0.00');
    $('#formAjusteInventario #ajuste_nueva_existencia').val('0.00');
    $('#formAjusteInventario #ajuste_tipo').val('sin_cambio');

    $('#ajuste_resultado_info')
      .removeClass('alert-success alert-danger alert-warning alert-info')
      .addClass('alert-secondary')
      .html('<i class="fas fa-info-circle mr-1"></i> Escriba la cantidad física encontrada.');

    return;
  }

  var conteoFisico = ajusteToNumber(conteoTexto);
  var diferencia = conteoFisico - saldoSistema;
  var tipo = 'sin_cambio';

  if (diferencia > 0) {
    tipo = 'entrada';
  } else if (diferencia < 0) {
    tipo = 'salida';
  }

  $('#formAjusteInventario #ajuste_diferencia').val(diferencia.toFixed(2));
  $('#formAjusteInventario #ajuste_diferencia_visible').val(ajusteFormatNumber(diferencia));
  $('#formAjusteInventario #ajuste_nueva_existencia').val(ajusteFormatNumber(conteoFisico));
  $('#formAjusteInventario #ajuste_tipo').val(tipo);

  if (!$('#formAjusteInventario #ajuste_producto').val()) {
    $('#ajuste_resultado_info')
      .removeClass('alert-success alert-danger alert-warning alert-info')
      .addClass('alert-secondary')
      .html('<i class="fas fa-info-circle mr-1"></i> Seleccione un producto para calcular el ajuste.');
    return;
  }

  if (tipo === 'entrada') {
    $('#ajuste_resultado_info')
      .removeClass('alert-secondary alert-danger alert-warning alert-info')
      .addClass('alert-success')
      .html(
        '<i class="fas fa-sign-in-alt mr-1"></i>' +
        'Se registrará una <strong>ENTRADA por ajuste</strong> de <strong>' + ajusteFormatNumber(Math.abs(diferencia)) + '</strong> unidades. ' +
        'La nueva existencia será <strong>' + ajusteFormatNumber(conteoFisico) + '</strong>.'
      );
  } else if (tipo === 'salida') {
    $('#ajuste_resultado_info')
      .removeClass('alert-secondary alert-success alert-warning alert-info')
      .addClass('alert-danger')
      .html(
        '<i class="fas fa-sign-out-alt mr-1"></i>' +
        'Se registrará una <strong>SALIDA por ajuste</strong> de <strong>' + ajusteFormatNumber(Math.abs(diferencia)) + '</strong> unidades. ' +
        'La nueva existencia será <strong>' + ajusteFormatNumber(conteoFisico) + '</strong>.'
      );
  } else {
    $('#ajuste_resultado_info')
      .removeClass('alert-secondary alert-success alert-danger alert-info')
      .addClass('alert-warning')
      .html(
        '<i class="fas fa-check-circle mr-1"></i>' +
        'No hay diferencia. El conteo físico coincide con el saldo del sistema.'
      );
  }
}

function buscarProductoAjustePorBarcode() {
  var barcode = $('#formAjusteInventario #ajuste_barcode').val().trim();

  if (barcode === '') {
    showNotify('warning', 'Atención', 'Debe escanear o ingresar un código de producto.');
    $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
    return;
  }

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/buscar_producto.php',
    data: {
      barcode: barcode
    },
    dataType: 'json',
    success: function (registro) {
      if (!registro || !registro.success) {
        showNotify('warning', 'Producto no encontrado', registro && registro.message ? registro.message : 'No se encontró un producto con ese código.');
        $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
        return;
      }

      aplicarProductoAjusteSeleccionado({
        productos_id: registro.productos_id,
        tipo_producto_id: registro.tipo_producto_id,
        barCode: barcode,
        almacen_id: $('#formAjusteInventario #ajuste_almacen').val()
      });
    },
    error: function () {
      showNotify('error', 'Error', 'No se pudo buscar el producto.');
      $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
    }
  });
}

function abrirModalBuscarProductosAjuste() {
  window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = true;
  AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = true;

  if ($('#modal_buscar_productos_movimientos_general').length === 0) {
    showNotify('error', 'Modal no encontrado', 'No existe el buscador de productos.');
    return;
  }

  cargarDataTableProductosMovimiento();

  $('#modal_buscar_productos_movimientos_general').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function aplicarProductoAjusteSeleccionado(row) {
  if (!row || !row.productos_id) {
    showNotify('error', 'Error', 'No se pudo obtener el producto seleccionado.');
    return;
  }

  var productoId = row.productos_id;
  var tipoProductoId = row.tipo_producto_id || '';
  var barcode = row.barCode || '';
  var almacenId = row.almacen_id || '';
  var loteId = row.lote_id || '';

  $('#formAjusteInventario #ajuste_barcode').val(barcode);

  if (almacenId !== '' && parseInt(almacenId || 0, 10) > 0) {
    $('#formAjusteInventario #ajuste_almacen').val(almacenId).selectpicker('refresh');
  } else {
    seleccionarBodegaPrincipal('#formAjusteInventario #ajuste_almacen');
  }

  if (tipoProductoId !== '') {
    $('#formAjusteInventario #ajuste_tipo_producto_id').val(tipoProductoId).selectpicker('refresh');

    getProductosAjuste(tipoProductoId, function () {
      $('#formAjusteInventario #ajuste_producto').val(productoId).selectpicker('refresh');

      getLotesProductosAjuste(productoId, function () {
        if (loteId !== '' && parseInt(loteId || 0, 10) > 0) {
          $('#formAjusteInventario #ajuste_lote').val(loteId).selectpicker('refresh');
        }

        setTimeout(function () {
          consultarSaldoProductoAjuste();
          $('#formAjusteInventario #ajuste_conteo_fisico').trigger('focus').select();
        }, 350);
      });
    });
  } else {
    $('#formAjusteInventario #ajuste_producto').val(productoId).selectpicker('refresh');

    getLotesProductosAjuste(productoId, function () {
      if (loteId !== '' && parseInt(loteId || 0, 10) > 0) {
        $('#formAjusteInventario #ajuste_lote').val(loteId).selectpicker('refresh');
      }

      setTimeout(function () {
        consultarSaldoProductoAjuste();
        $('#formAjusteInventario #ajuste_conteo_fisico').trigger('focus').select();
      }, 350);
    });
  }

  window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;
  AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;
  $('#modal_buscar_productos_movimientos_general').modal('hide');
}

function validarAjusteInventario() {
  var productoId = $('#formAjusteInventario #ajuste_producto').val();
  var almacenId = $('#formAjusteInventario #ajuste_almacen').val();
  var conteoFisicoRaw = $('#formAjusteInventario #ajuste_conteo_fisico').val();

  if (!productoId) {
    showNotify('warning', 'Producto requerido', 'Seleccione o escanee el producto que desea ajustar.');
    $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
    return false;
  }

  if (!almacenId) {
    showNotify('warning', 'Bodega requerida', 'Seleccione la bodega del producto.');
    seleccionarBodegaPrincipal('#formAjusteInventario #ajuste_almacen');
    return false;
  }

  if (conteoFisicoRaw === '' || ajusteToNumber(conteoFisicoRaw) < 0) {
    showNotify('warning', 'Conteo inválido', 'Ingrese la cantidad física encontrada.');
    $('#formAjusteInventario #ajuste_conteo_fisico').trigger('focus').select();
    return false;
  }

  return true;
}

function obtenerTextoConfirmacionAjusteInventario() {
  var producto = $('#formAjusteInventario #ajuste_producto option:selected').text() || 'Producto seleccionado';
  var bodega = $('#formAjusteInventario #ajuste_almacen option:selected').text() || 'Bodega seleccionada';
  var saldoSistema = ajusteToNumber($('#formAjusteInventario #ajuste_saldo_sistema').val());
  var conteoFisico = ajusteToNumber($('#formAjusteInventario #ajuste_conteo_fisico').val());
  var diferencia = ajusteToNumber($('#formAjusteInventario #ajuste_diferencia').val());
  var tipo = $('#formAjusteInventario #ajuste_tipo').val();

  var accion = 'Sin movimiento';

  if (tipo === 'entrada') {
    accion = 'Entrada por ajuste';
  } else if (tipo === 'salida') {
    accion = 'Salida por ajuste';
  }

  return '' +
    '<strong>Producto:</strong> ' + movimientosEscape(producto) + '<br>' +
    '<strong>Bodega:</strong> ' + movimientosEscape(bodega) + '<br><br>' +
    '<strong>Stock actual sistema:</strong> ' + ajusteFormatNumber(saldoSistema) + '<br>' +
    '<strong>Conteo físico:</strong> ' + ajusteFormatNumber(conteoFisico) + '<br>' +
    '<strong>Diferencia:</strong> ' + ajusteFormatNumber(diferencia) + '<br>' +
    '<strong>Acción:</strong> ' + movimientosEscape(accion);
}

function confirmarGuardarAjusteInventario() {
  if (AJUSTE_INVENTARIO_GUARDANDO === true) {
    return false;
  }

  if (!validarAjusteInventario()) {
    return false;
  }

  calcularDiferenciaAjusteInventario();

  swal({
    title: '¿Registrar ajuste de inventario?',
    content: {
      element: 'span',
      attributes: {
        innerHTML: obtenerTextoConfirmacionAjusteInventario()
      }
    },
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'Cancelar',
        visible: true
      },
      confirm: {
        text: 'Sí, registrar ajuste'
      }
    },
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function (confirmar) {
    if (confirmar) {
      guardarAjusteInventarioAjax();
    }
  });

  return false;
}

function guardarAjusteInventarioAjax() {
  var productoId = $('#formAjusteInventario #ajuste_producto').val();
  var almacenId = $('#formAjusteInventario #ajuste_almacen').val();
  var loteId = $('#formAjusteInventario #ajuste_lote').val();
  var fechaVencimiento = $('#formAjusteInventario #ajuste_fecha_vencimiento').val();
  var saldoSistema = ajusteToNumber($('#formAjusteInventario #ajuste_saldo_sistema').val());
  var conteoFisico = ajusteToNumber($('#formAjusteInventario #ajuste_conteo_fisico').val());
  var diferencia = ajusteToNumber($('#formAjusteInventario #ajuste_diferencia').val());
  var tipo = $('#formAjusteInventario #ajuste_tipo').val();
  var comentario = $.trim($('#formAjusteInventario #ajuste_comentario').val());

  if (comentario === '') {
    comentario = 'Ajuste por conteo físico de inventario';
  }

  /*
    IMPORTANTE:
    El JS solo envía el ajuste. Quien debe afectar inventario es:
    core/inventario/addAjusteInventario.php

    Ese PHP debe:
    1) Insertar en movimientos la entrada/salida por diferencia.
    2) Insertar en inventario_ajustes la auditoría.
    3) Responder movimientos_id o movimiento_registrado=true cuando afectó movimientos.
  */
  var cantidadMovimiento = Math.abs(diferencia);
  var comentarioMovimiento = comentario +
    ' | Ajuste inventario' +
    ' | Stock sistema: ' + ajusteFormatNumber(saldoSistema) +
    ' | Conteo físico: ' + ajusteFormatNumber(conteoFisico) +
    ' | Diferencia: ' + ajusteFormatNumber(diferencia);

  AJUSTE_INVENTARIO_GUARDANDO = true;

  $('#btnRegistrarAjusteInventario')
    .prop('disabled', true)
    .html('<i class="fas fa-spinner fa-spin fa-lg mr-1"></i> Registrando...');

  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/inventario/addAjusteInventario.php',
    dataType: 'json',
    data: {
      /* Datos propios del ajuste */
      productos_id: productoId,
      almacen_id: almacenId,
      lote_id: loteId,
      fecha_vencimiento: fechaVencimiento,
      saldo_sistema: saldoSistema,
      conteo_fisico: conteoFisico,
      diferencia: diferencia,
      tipo_ajuste: tipo,
      comentario: comentario,

      /* Datos espejo para que el PHP registre en movimientos sin usar ajax/ */
      movimiento_operacion: tipo,
      movimiento_producto: productoId,
      movimiento_cantidad: cantidadMovimiento,
      almacen_modal: almacenId,
      movimiento_lote: loteId,
      movimiento_fecha_vencimiento: fechaVencimiento,
      movimiento_comentario: comentarioMovimiento,
      documento: tipo === 'entrada' ? 'Ajuste de inventario - Entrada' : (tipo === 'salida' ? 'Ajuste de inventario - Salida' : 'Ajuste de inventario - Sin cambio')
    },
    success: function (resp) {
      if (resp && resp.success) {
        var requiereMovimiento = tipo !== 'sin_cambio' && Math.abs(diferencia) > 0.0001;
        var movimientoConfirmado = false;

        if (!requiereMovimiento) {
          movimientoConfirmado = true;
        }

        if (resp.movimiento_registrado === true || resp.movimientos_id || resp.movimiento_id) {
          movimientoConfirmado = true;
        }

        if (requiereMovimiento && !movimientoConfirmado) {
          showNotify(
            'warning',
            'Ajuste guardado sin movimiento',
            'Se guardó la auditoría, pero el servidor no confirmó que haya registrado la entrada/salida en movimientos. Revise core/inventario/addAjusteInventario.php.'
          );

          if (typeof listar_movimientos === 'function') {
            listar_movimientos();
          }

          return;
        }

        showNotify(
          'success',
          resp.title || 'Ajuste registrado',
          resp.message || 'El ajuste de inventario se registró correctamente.'
        );

        $('#modal_ajuste_inventario').modal('hide');

        if (typeof listar_movimientos === 'function') {
          listar_movimientos();
        }

        if (typeof listar_consulta_inventario === 'function' && $('#modal_consultar_inventario').length) {
          listar_consulta_inventario();
        }

        setTimeout(function () {
          if ($('#modal_ajuste_inventario').length) {
            limpiarFormularioAjusteInventario();
          }
        }, 300);
      } else {
        showNotify(
          'error',
          resp && resp.title ? resp.title : 'Error',
          resp && resp.message ? resp.message : 'No se pudo registrar el ajuste de inventario.'
        );
      }
    },
    error: function (xhr) {
      console.log(xhr.responseText);
      showNotify('error', 'Error', 'No se pudo comunicar con el servidor para registrar el ajuste.');
    },
    complete: function () {
      AJUSTE_INVENTARIO_GUARDANDO = false;

      $('#btnRegistrarAjusteInventario')
        .prop('disabled', false)
        .html('<i class="fas fa-save fa-lg mr-1"></i> Registrar Ajuste');
    }
  });
}

/* =========================================================
   EVENTOS AJUSTE INVENTARIO
========================================================= */
$(document)
  .off('click.ajusteInventario', '#btnRegistrarAjusteInventario')
  .on('click.ajusteInventario', '#btnRegistrarAjusteInventario', function (e) {
    e.preventDefault();
    confirmarGuardarAjusteInventario();
    return false;
  });

$(document)
  .off('click.ajusteBuscarProducto', '#btnBuscarProductoAjuste')
  .on('click.ajusteBuscarProducto', '#btnBuscarProductoAjuste', function (e) {
    e.preventDefault();
    abrirModalBuscarProductosAjuste();
    return false;
  });

$(document)
  .off('keydown.ajusteBarcode', '#formAjusteInventario #ajuste_barcode')
  .on('keydown.ajusteBarcode', '#formAjusteInventario #ajuste_barcode', function (e) {
    if (e.key === 'Enter' || e.which === 13) {
      e.preventDefault();
      buscarProductoAjustePorBarcode();
      return false;
    }
  });

$(document)
  .off('changed.bs.select.ajusteTipo change.ajusteTipo', '#formAjusteInventario #ajuste_tipo_producto_id')
  .on('changed.bs.select.ajusteTipo change.ajusteTipo', '#formAjusteInventario #ajuste_tipo_producto_id', function () {
    var tipo = $(this).val() || 1;
    getProductosAjuste(tipo);
  });

$(document)
  .off('changed.bs.select.ajusteProducto change.ajusteProducto', '#formAjusteInventario #ajuste_producto')
  .on('changed.bs.select.ajusteProducto change.ajusteProducto', '#formAjusteInventario #ajuste_producto', function () {
    var productoId = $(this).val();

    getLotesProductosAjuste(productoId, function () {
      consultarSaldoProductoAjuste();
    });
  });

$(document)
  .off('changed.bs.select.ajusteAlmacen change.ajusteAlmacen', '#formAjusteInventario #ajuste_almacen')
  .on('changed.bs.select.ajusteAlmacen change.ajusteAlmacen', '#formAjusteInventario #ajuste_almacen', function () {
    consultarSaldoProductoAjuste();
  });

$(document)
  .off('changed.bs.select.ajusteLote change.ajusteLote', '#formAjusteInventario #ajuste_lote')
  .on('changed.bs.select.ajusteLote change.ajusteLote', '#formAjusteInventario #ajuste_lote', function () {
    consultarSaldoProductoAjuste();
  });

$(document)
  .off('input.ajusteConteo', '#formAjusteInventario #ajuste_conteo_fisico')
  .on('input.ajusteConteo', '#formAjusteInventario #ajuste_conteo_fisico', function () {
    calcularDiferenciaAjusteInventario();
  });

$(document)
  .off('keypress.ajusteConteoEnter', '#formAjusteInventario #ajuste_conteo_fisico')
  .on('keypress.ajusteConteoEnter', '#formAjusteInventario #ajuste_conteo_fisico', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      confirmarGuardarAjusteInventario();
      return false;
    }
  });

$(document)
  .off('shown.bs.modal.ajusteInventario', '#modal_ajuste_inventario')
  .on('shown.bs.modal.ajusteInventario', '#modal_ajuste_inventario', function () {
    setTimeout(function () {
      $('#formAjusteInventario #ajuste_barcode').trigger('focus').select();
    }, 250);
  });

$(document)
  .off('hidden.bs.modal.productosAjusteFocus', '#modal_buscar_productos_movimientos_general')
  .on('hidden.bs.modal.productosAjusteFocus', '#modal_buscar_productos_movimientos_general', function () {
    if ($('#modal_ajuste_inventario').hasClass('show')) {
      window.AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;
      AJUSTE_INVENTARIO_BUSQUEDA_ACTIVA = false;

      setTimeout(function () {
        $('#formAjusteInventario #ajuste_conteo_fisico').trigger('focus').select();
      }, 200);
    }
  });


/* =========================================================
   AUDITORÍA DE AJUSTES DE INVENTARIO
   ---------------------------------------------------------
   Modal: #modal_consultar_inventario
   Tabla: #dataTablaConsultaInventario
   Fuente: core/inventario/llenarDataTableConsultaInventario.php
   Nota : Consulta inventario_ajustes, no el inventario actual.
========================================================= */
var TABLE_CONSULTA_INVENTARIO = null;

function modal_consultar_inventario() {
  if ($('#modal_consultar_inventario').length === 0) {
    showNotify('error', 'Modal no encontrado', 'No existe el modal de auditoría de ajustes de inventario.');
    return;
  }

  cargarCombosConsultaInventario();
  limpiarFiltrosAuditoriaAjustes(false);

  $('#modal_consultar_inventario').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  setTimeout(function () {
    listar_consulta_inventario();
  }, 350);
}

function cargarCombosConsultaInventario() {
  getAlmacenConsultaInventario();
  getTipoProductosConsultaInventario();
  getProductosConsultaInventario(1);
}

function getAlmacenConsultaInventario() {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getAlmacenCompras.php',
    success: function (data) {
      $('#formConsultaInventario #consulta_almacen').html(data).selectpicker('refresh');
      seleccionarBodegaPrincipal('#formConsultaInventario #consulta_almacen');
    }
  });
}

function getTipoProductosConsultaInventario() {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getTipoProductoMovimientosModal.php',
    success: function (data) {
      $('#formConsultaInventario #consulta_tipo_producto_id').html(data).selectpicker('refresh');
    }
  });
}

function getProductosConsultaInventario(tipo_producto_id, callback) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/getProductosMovimientosTipoProducto.php',
    data: {
      tipo_producto_id: tipo_producto_id || 1
    },
    success: function (data) {
      $('#formConsultaInventario #consulta_producto').html(data).selectpicker('refresh');

      if (typeof callback === 'function') {
        callback(data);
      }
    }
  });
}

function limpiarFiltrosAuditoriaAjustes(recargar) {
  var hoy = new Date();
  var primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

  function fechaIso(fecha) {
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  $('#formConsultaInventario #consulta_barcode').val('');
  $('#formConsultaInventario #consulta_fechai').val(fechaIso(primerDia));
  $('#formConsultaInventario #consulta_fechaf').val(fechaIso(hoy));
  $('#formConsultaInventario #consulta_tipo_ajuste').val('').selectpicker('refresh');
  $('#formConsultaInventario #consulta_tipo_producto_id').val('').selectpicker('refresh');
  $('#formConsultaInventario #consulta_producto').val('').selectpicker('refresh');
  seleccionarBodegaPrincipal('#formConsultaInventario #consulta_almacen');

  if (recargar === true) {
    listar_consulta_inventario();
  }
}

function listar_consulta_inventario() {
  if ($('#dataTablaConsultaInventario').length === 0) {
    return;
  }

  var fechai = $('#formConsultaInventario #consulta_fechai').val();
  var fechaf = $('#formConsultaInventario #consulta_fechaf').val();
  var almacen = $('#formConsultaInventario #consulta_almacen').val();
  var tipoProducto = $('#formConsultaInventario #consulta_tipo_producto_id').val();
  var producto = $('#formConsultaInventario #consulta_producto').val();
  var tipoAjuste = $('#formConsultaInventario #consulta_tipo_ajuste').val();
  var barcode = $.trim($('#formConsultaInventario #consulta_barcode').val());

  if ($.fn.DataTable.isDataTable('#dataTablaConsultaInventario')) {
    $('#dataTablaConsultaInventario').DataTable().clear().destroy();
  }

  TABLE_CONSULTA_INVENTARIO = $('#dataTablaConsultaInventario').DataTable({
    destroy: true,
    processing: true,
    deferRender: true,
    responsive: true,
    autoWidth: false,
    language: idioma_español,
    lengthMenu: lengthMenu10,
    dom: dom,
    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL;?>core/inventario/llenarDataTableConsultaInventario.php',
      data: {
        fechai: fechai,
        fechaf: fechaf,
        almacen: almacen,
        tipo_producto_id: tipoProducto,
        producto: producto,
        tipo_ajuste: tipoAjuste,
        barcode: barcode
      },
      dataSrc: function (json) {
        if (json && json.data) {
          actualizarResumenConsultaInventario(json.data);
          return json.data;
        }

        actualizarResumenConsultaInventario([]);
        return [];
      },
      error: function (xhr) {
        actualizarResumenConsultaInventario([]);
        console.log(xhr.responseText);
        showNotify('error', 'Error', 'No se pudo consultar la auditoría de ajustes de inventario.');
      }
    },
    columns: [
      {
        data: null,
        className: 'align-middle text-nowrap',
        render: function (data, type, row) {
          var fecha = movimientosEscape(row.fecha_registro || 'Sin fecha');
          var id = movimientosEscape(row.inventario_ajustes_id || '');

          if (type !== 'display') {
            return fecha + ' ' + id;
          }

          return '' +
            '<div class="movimientos-detail-list">' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-doc"><i class="fas fa-history"></i></span>' +
                '<span><strong>Ajuste #:</strong> ' + id + '</span>' +
              '</div>' +
              '<div class="movimientos-detail-item">' +
                '<span class="movimientos-detail-icon movimientos-icon-lote"><i class="fas fa-calendar-alt"></i></span>' +
                '<span>' + fecha + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: 'align-middle',
        render: function (data, type, row) {
          var productoTexto = movimientosEscape(row.producto || row.nombre || 'Sin nombre');
          var barcodeTexto = movimientosEscape(row.barCode || row.barcode || 'Sin código');
          var tipoTexto = movimientosEscape(row.tipo_producto_nombre || 'No registrado');

          if (type !== 'display') {
            return productoTexto + ' ' + barcodeTexto + ' ' + tipoTexto;
          }

          return '' +
            '<div class="movimientos-product-info">' +
              '<h6 class="movimientos-product-name mb-1">' + productoTexto + '</h6>' +
              '<div class="movimientos-product-meta">' +
                '<span><i class="fas fa-barcode mr-1"></i>' + barcodeTexto + '</span>' +
                '<span><i class="fas fa-cubes mr-1"></i>' + tipoTexto + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: 'align-middle text-nowrap',
        render: function (data, type, row) {
          var bodega = movimientosEscape(row.bodega || 'Sin bodega');
          var lote = movimientosEscape(row.numero_lote || 'Sin lote');
          var vence = movimientosEscape(row.fecha_vencimiento || 'Sin vencimiento');

          if (type !== 'display') {
            return bodega + ' ' + lote + ' ' + vence;
          }

          return '' +
            '<strong><i class="fas fa-warehouse mr-1"></i>' + bodega + '</strong><br>' +
            '<small class="text-muted"><i class="fas fa-tags mr-1"></i>' + lote + ' / ' + vence + '</small>';
        }
      },
      {
        data: 'saldo_sistema',
        className: 'text-right align-middle text-nowrap',
        render: function (data, type) {
          var valor = ajusteToNumber(data);
          if (type !== 'display') return valor;
          return '<span class="badge badge-secondary p-2" style="font-size:13px;">' + ajusteFormatNumber(valor) + '</span>';
        }
      },
      {
        data: 'conteo_fisico',
        className: 'text-right align-middle text-nowrap',
        render: function (data, type) {
          var valor = ajusteToNumber(data);
          if (type !== 'display') return valor;
          return '<span class="badge badge-info p-2" style="font-size:13px;">' + ajusteFormatNumber(valor) + '</span>';
        }
      },
      {
        data: null,
        className: 'text-right align-middle text-nowrap',
        render: function (data, type, row) {
          var diferencia = ajusteToNumber(row.diferencia);
          var tipo = String(row.tipo_ajuste || 'sin_cambio').toLowerCase();

          if (type !== 'display') return diferencia;

          var clase = 'badge-warning';
          var icono = 'fa-check-circle';
          var texto = 'Sin cambio';

          if (tipo === 'entrada') {
            clase = 'badge-success';
            icono = 'fa-sign-in-alt';
            texto = 'Entrada';
          } else if (tipo === 'salida') {
            clase = 'badge-danger';
            icono = 'fa-sign-out-alt';
            texto = 'Salida';
          }

          return '' +
            '<span class="badge ' + clase + ' p-2" style="font-size:13px;">' +
              '<i class="fas ' + icono + ' mr-1"></i>' + texto +
            '</span><br>' +
            '<strong>' + ajusteFormatNumber(diferencia) + '</strong>';
        }
      },
      {
        data: null,
        className: 'align-middle',
        render: function (data, type, row) {
          var usuario = movimientosEscape(row.colaborador || 'No registrado');
          var comentario = movimientosEscape(row.comentario || 'Sin comentario');
          var mov = row.movimientos_id && parseInt(row.movimientos_id, 10) > 0 ? row.movimientos_id : 'Sin movimiento';

          if (type !== 'display') {
            return usuario + ' ' + comentario + ' ' + mov;
          }

          return '' +
            '<div><strong><i class="fas fa-user mr-1"></i>' + usuario + '</strong></div>' +
            '<small class="text-muted"><i class="fas fa-exchange-alt mr-1"></i>Movimiento: ' + movimientosEscape(mov) + '</small><br>' +
            '<small class="text-muted"><i class="fas fa-comment mr-1"></i>' + comentario + '</small>';
        }
      }
    ],
    order: [[0, 'desc']],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Auditoría',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function () {
          listar_consulta_inventario();
        }
      },
      {
        extend: 'collection',
        text: '<i class="fas fa-file-export fa-lg"></i> Exportar',
        titleAttr: 'Exportar Auditoría',
        className: 'table_reportes btn btn-success ocultar',
        buttons: [
          {
            extend: 'excelHtml5',
            footer: false,
            text: '<i class="fas fa-file-excel mr-1"></i> Excel',
            title: 'Auditoría de Ajustes de Inventario',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
          },
          {
            extend: 'pdf',
            footer: false,
            text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
            orientation: 'landscape',
            pageSize: 'LEGAL',
            title: 'Auditoría de Ajustes de Inventario',
            exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }
          }
        ]
      }
    ],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function' && typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }

      $('[title]').tooltip({
        container: 'body',
        placement: 'top'
      });
    }
  });
}

function actualizarResumenConsultaInventario(rows) {
  rows = rows || [];

  var totalAjustes = rows.length;
  var totalEntradas = 0;
  var totalSalidas = 0;
  var totalSinCambio = 0;
  var balance = 0;

  rows.forEach(function (item) {
    var diferencia = ajusteToNumber(item.diferencia);
    var tipo = String(item.tipo_ajuste || 'sin_cambio').toLowerCase();

    balance += diferencia;

    if (tipo === 'entrada') {
      totalEntradas++;
    } else if (tipo === 'salida') {
      totalSalidas++;
    } else {
      totalSinCambio++;
    }
  });

  $('#consulta_total_productos').text(totalAjustes);
  $('#consulta_total_saldo').text(totalEntradas + ' / ' + totalSalidas);
  $('#consulta_total_sin_saldo').text(totalSinCambio);
  $('#consulta_total_balance_ajuste').text(ajusteFormatNumber(balance));
}

$(document)
  .off('click.consultaInventarioBuscar', '#btnBuscarConsultaInventario')
  .on('click.consultaInventarioBuscar', '#btnBuscarConsultaInventario', function (e) {
    e.preventDefault();
    listar_consulta_inventario();
    return false;
  });

$(document)
  .off('click.consultaInventarioLimpiar', '#btnLimpiarConsultaInventario')
  .on('click.consultaInventarioLimpiar', '#btnLimpiarConsultaInventario', function (e) {
    e.preventDefault();
    limpiarFiltrosAuditoriaAjustes(true);
    return false;
  });

$(document)
  .off('changed.bs.select.consultaTipo change.consultaTipo', '#formConsultaInventario #consulta_tipo_producto_id')
  .on('changed.bs.select.consultaTipo change.consultaTipo', '#formConsultaInventario #consulta_tipo_producto_id', function () {
    var tipo = $(this).val() || 1;
    getProductosConsultaInventario(tipo);
  });

$(document)
  .off('changed.bs.select.consultaFiltro change.consultaFiltro', '#formConsultaInventario #consulta_almacen, #formConsultaInventario #consulta_producto, #formConsultaInventario #consulta_tipo_ajuste')
  .on('changed.bs.select.consultaFiltro change.consultaFiltro', '#formConsultaInventario #consulta_almacen, #formConsultaInventario #consulta_producto, #formConsultaInventario #consulta_tipo_ajuste', function () {
    listar_consulta_inventario();
  });

$(document)
  .off('change.consultaFechas', '#formConsultaInventario #consulta_fechai, #formConsultaInventario #consulta_fechaf')
  .on('change.consultaFechas', '#formConsultaInventario #consulta_fechai, #formConsultaInventario #consulta_fechaf', function () {
    listar_consulta_inventario();
  });

$(document)
  .off('keypress.consultaBarcode', '#formConsultaInventario #consulta_barcode')
  .on('keypress.consultaBarcode', '#formConsultaInventario #consulta_barcode', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      listar_consulta_inventario();
      return false;
    }
  });

$(document)
  .off('shown.bs.modal.consultaInventario', '#modal_consultar_inventario')
  .on('shown.bs.modal.consultaInventario', '#modal_consultar_inventario', function () {
    setTimeout(function () {
      $('#formConsultaInventario #consulta_barcode').trigger('focus').select();
    }, 250);
  });

</script>