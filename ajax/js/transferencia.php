<script>
(function () {
  function inicializarInventarioTransferencia() {
    inventario_transferencia();
    getTipoProductos();
    getAlmacen();

    $('#form_main_movimientos_transferencia').off('submit.inventarioTransferencia');
    $('#form_main_movimientos_transferencia').on('submit.inventarioTransferencia', function(e){
      e.preventDefault();
      inventario_transferencia();
    });

    $('#form_main_movimientos_transferencia').off('reset.inventarioTransferencia');
    $('#form_main_movimientos_transferencia').on('reset.inventarioTransferencia', function(){
      var form = this;

      setTimeout(function(){
        $(form).find('.selectpicker')
          .val('')
          .selectpicker('refresh');

        inventario_transferencia();
      }, 100);
    });

    $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').off('changed.bs.select.inventarioTipo change.inventarioTipo');
    $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').on('changed.bs.select.inventarioTipo change.inventarioTipo', function(){
      var tipo = $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() || 1;
      getProductosMovimientos(tipo);

      setTimeout(function(){
        inventario_transferencia();
      }, 250);
    });

    $('#form_main_movimientos_transferencia #inventario_productos_id, #form_main_movimientos_transferencia #almacen').off('changed.bs.select.inventarioFiltro change.inventarioFiltro');
    $('#form_main_movimientos_transferencia #inventario_productos_id, #form_main_movimientos_transferencia #almacen').on('changed.bs.select.inventarioFiltro change.inventarioFiltro', function(){
      inventario_transferencia();
    });

    $("#modal_transferencia_producto").off('shown.bs.modal.inventarioTransferencia');
    $("#modal_transferencia_producto").on('shown.bs.modal.inventarioTransferencia', function(){
      $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarInventarioTransferencia);
  } else {
    inicializarInventarioTransferencia();
  }
})();

/* =========================================================
   UTILIDADES
   ========================================================= */

function toNumber(val){
  if (val == null) return 0;
  if (typeof val === 'number') return val;
  return parseFloat(String(val).replace(/[^\d.-]/g,'')) || 0;
}

function formatNumber(n){
  try{
    return Number(n).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }catch(e){
    var s = (Number(n) || 0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}

function inventarioValor(valor, textoDefault) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return textoDefault !== undefined ? textoDefault : 'No registrado';
  }

  return String(valor).trim();
}

function inventarioEscape(valor) {
  return inventarioValor(valor, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function inventarioNumeroBadge(valor, tipo) {
  var numero = toNumber(valor);
  var clase = 'inventario-number-badge';

  if (tipo === 'anterior') {
    clase += numero > 0 ? ' inventario-badge-anterior-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'entrada') {
    clase += numero > 0 ? ' inventario-badge-entrada-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'salida') {
    clase += numero > 0 ? ' inventario-badge-salida-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'saldo') {
    clase += numero > 0 ? ' inventario-badge-saldo-ok' : ' inventario-badge-saldo-empty';
  }

  return '<span class="' + clase + '">' + formatNumber(numero) + '</span>';
}

function inventarioActualizarResumen(rows) {
  rows = rows || [];

  var totalEntrada = 0;
  var totalSalida = 0;
  var totalSaldo = 0;

  rows.forEach(function(item) {
    totalEntrada += toNumber(item.entrada);
    totalSalida += toNumber(item.salida);
    totalSaldo += toNumber(item.saldo);
  });

  $('#inventario_total_registros').text(rows.length);
  $('#inventario_total_entrada').text(formatNumber(totalEntrada));
  $('#inventario_total_salida').text(formatNumber(totalSalida));
  $('#inventario_total_saldo').text(formatNumber(totalSaldo));
}

function construirHeaderDataTableInventarioTransferencia() {
  var $tabla = $("#dataTablaMovimientos");

  $tabla.find('thead').remove();

  $tabla.prepend(
    '<thead>' +
      '<tr>' +
        '<th>Acciones</th>' +
        '<th>Producto</th>' +
        '<th>Lote / Bodega</th>' +
        '<th>Último Movimiento</th>' +
        '<th>Anterior</th>' +
        '<th>Entrada</th>' +
        '<th>Salida</th>' +
        '<th>Saldo</th>' +
      '</tr>' +
    '</thead>'
  );
}

/* =========================================================
   INVENTARIO TRANSFERENCIA
   ========================================================= */

var inventario_transferencia = function() {
  var form = $("#form_main_movimientos_transferencia");
  var tipo_producto_id = form.find("#inventario_tipo_productos_id").val() || '';
  var productos_id = form.find("#inventario_productos_id").val() || '';
  var bodega = form.find("#almacen").val() || '';

  try{
    var _dtKey = 'DataTables_' + 'dataTablaMovimientos' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  }catch(e){}

  if ($.fn.DataTable.isDataTable("#dataTablaMovimientos")) {
    $("#dataTablaMovimientos").DataTable().clear().destroy();
  }

  construirHeaderDataTableInventarioTransferencia();

  var table_movimientos = $("#dataTablaMovimientos").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,
    autoWidth: false,
    scrollX: false,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableInvetarioTransferencia.php",
      data: {
        tipo_producto_id: tipo_producto_id,
        bodega: bodega,
        productos_id: productos_id
      },
      dataSrc: function(json) {
        var rows = [];

        if (json && json.data) {
          rows = json.data;
        }

        inventarioActualizarResumen(rows);

        return rows;
      }
    },
    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center align-middle inventario-acciones-cell",
        render: function(data, type, row) {
          if (type !== "display") {
            return "";
          }

          return '' +
            '<div class="dropdown acciones-dropdown">' +

              '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
              '</button>' +

              '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                '<button type="button" class="dropdown-item accion-item accion-editar table_change_date ocultar" data-toggle="tooltip" data-placement="top" title="Actualizar fecha de vencimiento">' +
                  '<span class="accion-icon accion-icon-editar">' +
                    '<i class="fas fa-calendar-alt"></i>' +
                  '</span>' +
                  '<span class="accion-label">Cambiar fecha</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item accion-transferir table_transferencia ocultar" data-toggle="tooltip" data-placement="top" title="Transferir producto a otra bodega">' +
                  '<span class="accion-icon accion-icon-editar">' +
                    '<i class="fas fa-exchange-alt"></i>' +
                  '</span>' +
                  '<span class="accion-label">Transferir</span>' +
                '</button>' +

              '</div>' +

            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle inventario-producto-cell",
        render: function(data, type, row) {
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = row.image ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + row.image) : defaultImageUrl;

          var producto = inventarioEscape(row.producto);
          var barcode = inventarioEscape(inventarioValor(row.barCode, 'Sin código'));
          var medida = inventarioEscape(inventarioValor(row.medida, 'Sin medida'));

          var superior = toNumber(row.superior);
          var tipoProductoHtml = superior > 0
            ? '<span class="inventario-product-badge inventario-product-compuesto"><i class="fas fa-project-diagram mr-1"></i> Compuesto</span>'
            : '<span class="inventario-product-badge inventario-product-normal"><i class="fas fa-box mr-1"></i> Normal</span>';

          if (type !== "display") {
            return producto + ' ' + barcode + ' ' + medida;
          }

          return '' +
            '<div class="inventario-product-box">' +
              '<div class="inventario-product-img-box">' +
                '<a href="#" class="iv-trigger inventario-zoom-trigger" ' +
                  'data-iv-src="' + imageUrl + '" ' +
                  'data-iv-fallback="' + defaultImageUrl + '" ' +
                  'data-iv-title="' + producto + '">' +
                  '<img class="inventario-product-img table-image" src="' + imageUrl + '" alt="' + producto + '" loading="lazy" onerror="this.onerror=null;this.src=\'' + defaultImageUrl + '\';">' +
                '</a>' +
              '</div>' +
              '<div class="inventario-product-info">' +
                '<h6 class="inventario-product-name">' + producto + '</h6>' +
                '<div class="inventario-product-meta">' +
                  '<span><i class="fas fa-barcode mr-1"></i>' + barcode + '</span>' +
                  '<span><i class="fas fa-ruler-combined mr-1"></i>' + medida + '</span>' +
                '</div>' +
                '<div class="inventario-product-type">' + tipoProductoHtml + '</div>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle inventario-lote-cell",
        render: function(data, type, row) {
          var lote = inventarioEscape(inventarioValor(row.numero_lote, 'No especificado'));
          var bodega = inventarioEscape(inventarioValor(row.bodega, 'Sin bodega'));
          var loteClass = inventarioValor(row.numero_lote, '') !== '' ? 'inventario-lote-ok' : 'inventario-lote-empty';

          if (type !== "display") {
            return lote + ' ' + bodega;
          }

          return '' +
            '<div class="inventario-detail-list">' +
              '<div class="inventario-detail-item">' +
                '<span class="inventario-detail-icon inventario-icon-lote"><i class="fas fa-box"></i></span>' +
                '<span><strong>Lote:</strong> <span class="inventario-lote-badge ' + loteClass + '">' + lote + '</span></span>' +
              '</div>' +
              '<div class="inventario-detail-item">' +
                '<span class="inventario-detail-icon inventario-icon-bodega"><i class="fas fa-warehouse"></i></span>' +
                '<span><strong>Bodega:</strong> ' + bodega + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: null,
        className: "align-middle inventario-fecha-cell",
        render: function(data, type, row) {
          var fecha = inventarioEscape(inventarioValor(row.fecha_registro, 'No registrada'));
          var movimientoId = inventarioEscape(inventarioValor(row.movimientos_id, 'Sin ID'));

          if (type !== "display") {
            return fecha + ' ' + movimientoId;
          }

          return '' +
            '<div class="inventario-detail-list">' +
              '<div class="inventario-detail-item">' +
                '<span class="inventario-detail-icon inventario-icon-date"><i class="fas fa-calendar-alt"></i></span>' +
                '<span><strong>Fecha:</strong> ' + fecha + '</span>' +
              '</div>' +
              '<div class="inventario-detail-item">' +
                '<span class="inventario-detail-icon inventario-icon-doc"><i class="fas fa-hashtag"></i></span>' +
                '<span><strong>Movimiento:</strong> ' + movimientoId + '</span>' +
              '</div>' +
            '</div>';
        }
      },
      {
        data: "saldo_anterior",
        className: "text-center align-middle inventario-numero-cell",
        render: function(data, type) {
          if (type !== "display") {
            return toNumber(data);
          }

          return inventarioNumeroBadge(data, 'anterior');
        }
      },
      {
        data: "entrada",
        className: "text-center align-middle inventario-numero-cell",
        render: function(data, type) {
          if (type !== "display") {
            return toNumber(data);
          }

          return inventarioNumeroBadge(data, 'entrada');
        }
      },
      {
        data: "salida",
        className: "text-center align-middle inventario-numero-cell",
        render: function(data, type) {
          if (type !== "display") {
            return toNumber(data);
          }

          return inventarioNumeroBadge(data, 'salida');
        }
      },
      {
        data: "saldo",
        className: "text-center align-middle inventario-numero-cell",
        render: function(data, type) {
          if (type !== "display") {
            return toNumber(data);
          }

          return inventarioNumeroBadge(data, 'saldo');
        }
      }
    ],
    order: [[3, 'desc']],
    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,
    columnDefs: [
      {
        width: "12%",
        targets: 0,
        orderable: false,
        searchable: false,
        className: "text-center align-middle inventario-acciones-cell"
      },
      {
        width: "28%",
        targets: 1,
        className: "align-middle inventario-producto-cell"
      },
      {
        width: "20%",
        targets: 2,
        className: "align-middle inventario-lote-cell"
      },
      {
        width: "16%",
        targets: 3,
        className: "align-middle inventario-fecha-cell"
      },
      {
        width: "6%",
        targets: 4,
        className: "text-center align-middle inventario-numero-cell"
      },
      {
        width: "6%",
        targets: 5,
        className: "text-center align-middle inventario-numero-cell"
      },
      {
        width: "6%",
        targets: 6,
        className: "text-center align-middle inventario-numero-cell"
      },
      {
        width: "6%",
        targets: 7,
        className: "text-center align-middle inventario-numero-cell"
      }
    ],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Inventario',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function(){
          inventario_transferencia();
        }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Exportar a Excel',
        title: 'Reporte Inventario',
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7]
        }
      },
      {
        extend: 'pdfHtml5',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'Exportar a PDF',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Reporte Inventario',
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7]
        },
        customize: function(doc){
          if (typeof imagen !== 'undefined' && imagen){
            doc.content.splice(0, 0, {
              image: imagen,
              width: 100,
              height: 45,
              margin: [0, 0, 0, 12]
            });
          }
        }
      }
    ],
    footerCallback: function(row, data, start, end, display){
      var api = this.api();

      var sum = function(idx) {
        return api.column(idx, { page: 'current' }).data().reduce(function(a, b) {
          return toNumber(a) + toNumber(b);
        }, 0);
      };

      var totalSaldoAnterior = sum(4);
      var totalEntrada = sum(5);
      var totalSalida = sum(6);
      var total = (totalEntrada + totalSaldoAnterior) - totalSalida;

      $('#anterior-footer-movimiento').html(formatNumber(totalSaldoAnterior));
      $('#entrada-footer-movimiento').html(formatNumber(totalEntrada));
      $('#salida-footer-movimiento').html(formatNumber(totalSalida));
      $('#total-footer-movimiento').html(formatNumber(total));
    },
    initComplete: function(){
      this.api().order([3, 'desc']).draw();
    },
    drawCallback: function(){
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

      $('[data-toggle="tooltip"]').tooltip();
    }
  });

  transferencia_producto_dataTable("#dataTablaMovimientos tbody", table_movimientos);
  cambiarVencimientoProducto_dataTable("#dataTablaMovimientos tbody", table_movimientos);
};

/* =========================================================
   TRANSFERIR PRODUCTO / BODEGA
   ========================================================= */

var transferencia_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_transferencia");

  $(tbody).on("click", "button.table_transferencia", function() {
    var data = table.row($(this).parents("tr")).data();

    $('#formTransferencia')[0].reset();

    if (toNumber(data.superior) > 0) {
      showNotify('error', 'Error', 'No se puede hacer transferencia de producto que depende de otro inventario');
      return false;
    }

    $('#formTransferencia #productos_id').val(data.productos_id);
    $('#formTransferencia #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferencia #id_bodega_actual').val(data.id_bodega);
    $('#formTransferencia #lote_id_productos').val(data.lote_id);
    $('#formTransferencia #empresa_id_productos').val(data.empresa_id);

    $('#modal_transferencia_producto').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });
  });
};

$('#formTransferencia').off('submit.inventarioTransferencia');
$('#formTransferencia').on('submit.inventarioTransferencia', function(e){
  e.preventDefault();

  var form = $("#formTransferencia");
  var respuesta = form.children('.RespuestaAjax');

  swal({
    title: "¿Estas seguro?",
    text: "¿Desea transferir este producto?",
    icon: "warning",
    buttons: {
      cancel: {
        text: "Cancelar",
        visible: true
      },
      confirm: {
        text: "¡Sí, transferir el producto!"
      }
    },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then((willConfirm) => {
    if (willConfirm) {
      var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';

      $.ajax({
        type: 'POST',
        url: url,
        data: form.serialize(),
        beforeSend: function(){
          $('#modal_transferencia_producto').modal({
            show: false,
            keyboard: false,
            backdrop: 'static'
          });
        },
        success: function(data){
          respuesta.html(data);
        }
      });
    }
  });
});

/* =========================================================
   CAMBIAR FECHA DE CADUCIDAD
   ========================================================= */

var cambiarVencimientoProducto_dataTable = function(tbody, table){
  $(tbody).off("click", "button.table_change_date");

  $(tbody).on("click", "button.table_change_date", function(){
    var data = table.row($(this).parents("tr")).data();

    $('#formTransferenciaCambiarFecha')[0].reset();

    $('#formTransferenciaCambiarFecha #productos_id').val(data.productos_id);
    $('#formTransferenciaCambiarFecha #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferenciaCambiarFecha #id_bodega_actual').val(data.id_bodega);
    $('#formTransferenciaCambiarFecha #cantidad_productos').val(toNumber(data.saldo));
    $('#formTransferenciaCambiarFecha #empresa_id_productos').val(data.empresa_id);
    $('#formTransferenciaCambiarFecha #lote_id_productos').val(data.lote_id);

    $('#modalCambiarFechaProducto').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });
  });
};

$('#EditarFechaVencimiento').off('click.inventarioFecha');
$('#EditarFechaVencimiento').on('click.inventarioFecha', function(e){
  e.preventDefault();

  var form = $("#formTransferenciaCambiarFecha");
  var respuesta = form.children('.RespuestaAjax');

  if (form[0].checkValidity()) {
    var url = '<?php echo SERVERURL;?>ajax/modificarFechaVencimientoProductosAjax.php';

    $.ajax({
      type: 'POST',
      url: url,
      data: form.serialize(),
      beforeSend: function(){
        $('#modalCambiarFechaProducto').modal({
          show: false,
          keyboard: false,
          backdrop: 'static'
        });
      },
      success: function(data){
        respuesta.html(data);
      }
    });
  } else {
    form[0].reportValidity();
  }
});

/* =========================================================
   CARGA DE COMBOS
   ========================================================= */

function getTipoProductos(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data){
      $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').html(data).selectpicker('refresh');
      $('#formMovimientos #movimientos_tipo_producto_id').html(data).selectpicker('refresh');

      var tipo = $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() || 1;
      getProductosMovimientos(tipo);
    }
  });
}

function getProductosMovimientos(tipo_producto_id){
  var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';

  $.ajax({
    type: "POST",
    url: url,
    data: {
      tipo_producto_id: tipo_producto_id
    },
    success: function(data){
      $('#form_main_movimientos_transferencia #inventario_productos_id').html(data).selectpicker('refresh');
    }
  });
}

function getAlmacen(){
  var url = '<?php echo SERVERURL;?>core/getAlmacen.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data){
      $('#form_main_movimientos_transferencia #almacen').html(data).selectpicker('refresh');
      $('#formTransferencia #id_bodega').html(data).selectpicker('refresh');
    }
  });
}
</script>