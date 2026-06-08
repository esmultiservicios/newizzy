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

      setTimeout(function(){
        $('#formMovimientos #produto_barcode').focus();
      }, 400);
    });

    $("#modal_transferencia_producto").off('shown.bs.modal.movimientos');
    $("#modal_transferencia_producto").on('shown.bs.modal.movimientos', function(){
      $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });

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
      $('#buscar').focus();
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
  $('#buscar').focus();
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

function getProductosMovimientos(tipo_producto_id){
  var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';

  $.ajax({
    type:"POST",
    url: url,
    data:'tipo_producto_id='+tipo_producto_id,
    success:function(data){
      $('#form_main_movimientos #producto_movimiento_filtro').html(data).selectpicker('refresh');
      $('#formMovimientos #movimiento_producto').html(data).selectpicker('refresh');
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
    $('#formMovimientos #produto_barcode').focus();
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
        $('#formMovimientos #produto_barcode').focus().select();
      }
    },
    error:function(){
      showNotify('error','Error','Hubo un problema en la comunicación con el servidor');
      $('#formMovimientos #produto_barcode').focus().select();
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
    $('#formMovimientos #produto_barcode').focus();
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

    $('#formMovimientos #produto_barcode').focus();

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
        $('#formMovimientos #produto_barcode').focus();
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

      if (!validarMovimientoInventarioRapido()) {
        return false;
      }

      $('#formMovimientos').submit();
    }
  });

  $('#formMovimientos').off('submit.validarMovimientoRapido');
  $('#formMovimientos').on('submit.validarMovimientoRapido', function(e){
    if (!validarMovimientoInventarioRapido()) {
      e.preventDefault();
      return false;
    }

    guardarOperacionRecordada();
    restaurarOperacionDespuesDeReset();
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
      $('#formMovimientos #produto_barcode').focus();
    }, 150);

  }, 300);
}
</script>