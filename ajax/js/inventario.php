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
</script>