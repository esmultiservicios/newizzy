<script>
$(() => {
  inventario_transferencia();
  getTipoProductos();
  getAlmacen();

  // Buscar
  $('#form_main_movimientos_transferencia').on('submit', function(e){
    e.preventDefault();
    inventario_transferencia();
  });

  // Limpiar (reset)
  $('#form_main_movimientos_transferencia').on('reset', function(){
    $(this).find('.selectpicker').val('').selectpicker('refresh');
    inventario_transferencia();
  });
});

/* ========= Utils numéricos y render ========= */
function toNumber(val){
  if (val == null) return 0;
  if (typeof val === 'number') return val;
  return parseFloat(String(val).replace(/[^\d.-]/g,'')) || 0;
}
function formatNumber(n){
  try{
    return Number(n).toLocaleString('es-HN', {minimumFractionDigits:2, maximumFractionDigits:2});
  }catch(e){
    var s = (Number(n)||0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}
function badgeRender(colorFn){
  return function(data, type){
    var n = toNumber(data);
    if (type === 'display'){
      var color = colorFn(n);
      return '<span style="border:2px solid '+color+';border-radius:12px;padding:5px 10px;color:'+color+';font-weight:bold;">'
             + formatNumber(n) + '</span>';
    }
    return n; // ordenar/filtrar como número
  };
}

/* ========= INVENTARIO TRANSFERENCIA ========= */
var inventario_transferencia = function() {
  var form = $("#form_main_movimientos_transferencia");
  var tipo_producto_id = form.find("#inventario_tipo_productos_id").val() || '';
  var productos_id     = form.find("#inventario_productos_id").val();
  var bodega           = form.find("#almacen").val();

  // Limpia cualquier estado previo guardado que pueda forzar otro orden
  try{
    var _dtKey = 'DataTables_' + 'dataTablaMovimientos' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  }catch(e){}

  var table_movimientos = $("#dataTablaMovimientos").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableInvetarioTransferencia.php",
      data: { tipo_producto_id, bodega, productos_id }
    },
    columns: [
      { // Botón cambiar fecha
        defaultContent: "<button data-toggle='tooltip' data-placement='top' title='Actualizar la fecha de vencimiento' class='table_change_date btn btn-secondary'><span class='fa-solid fa-calendar-days fa-lg'></span>Fecha</button>"
      },
      { data: "fecha_registro" }, // 'YYYY-MM-DD HH:mm:ss' => orden lexicográfico correcto

      // Imagen (excluir de export)
      {
        data: "image",
        orderable: false,
        render: function (data, type, row) {
          if (type !== "display") return data || "";
          var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
          var imageUrl = data ? ('<?php echo SERVERURL;?>vistas/plantilla/img/products/' + data) : defaultImageUrl;
          var safeName = (row && row.nombre) ? String(row.nombre).replace(/"/g, '&quot;') : 'Imagen de producto';
          return ''
            + '<a href="#" class="iv-trigger"'
            + '   data-iv-src="' + imageUrl + '"'
            + '   data-iv-fallback="' + defaultImageUrl + '"'
            + '   data-iv-title="' + safeName + '">'
            + '  <img class="table-image" src="' + imageUrl + '" alt="' + safeName + '"'
            + '       width="100" height="100" loading="lazy"'
            + '       style="object-fit:cover;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.12)"'
            + '       onerror="this.onerror=null;this.src=\'' + defaultImageUrl + '\';" />'
            + '</a>';
        }
      },

      // Lote
      {
        data: "numero_lote",
        render: function(data, type){
          if (type !== 'display') return data;
          var txt = data ? data : 'No especificado';
          var color = data ? '#28a745' : '#dc3545';
          return '<span class="numero-lote" style="border:2px solid '+color+';border-radius:12px;padding:5px 10px;color:'+color+';display:inline-block;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+txt+'</span>';
        }
      },

      { data: "barCode" },
      { data: "producto" },
      { data: "medida" },

      // Números con formato (orden numérico real)
      { data: "saldo_anterior", className:"text-right", render: badgeRender(n => n>0 ? '#28a745' : '#ff6f61') },
      { data: "entrada",        className:"text-right", render: badgeRender(n => n>0 ? '#17a2b8' : '#f39c12') },
      { data: "salida",         className:"text-right", render: badgeRender(n => n>0 ? '#ffc107' : '#dc3545') },
      { data: "saldo",          className:"text-right", render: badgeRender(n => n>=0 ? '#007bff' : '#ff6347') },

      { data: "bodega" },

      { // Botón transferir
        defaultContent: "<button data-toggle='tooltip' data-placement='top' title='Permite mover o transferir un producto de una bodega a otra' class='table_eliminar table_transferencia'><span class='fa fa-exchange-alt fa-lg'></span>Transferir</button>"
      }
    ],

    // Última fecha primero
    order: [[1, 'desc']],

    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,

    columnDefs: [
      { width: "13.5%", targets: 0, orderable: false }, // botón
      { width: "10.5%", targets: 1 },  // fecha
      { width: "20.5%", targets: 2 },  // imagen
      { width: "10.5%", targets: 3 },  // lote
      { width: "10.5%", targets: 4 },  // barcode
      { width: "10.5%", targets: 5 },  // producto
      { width: "10.5%", targets: 6 },  // medida
      { width: "10.5%", targets: 7 },  // saldo_anterior
      { width: "10.5%", targets: 8 },  // entrada
      { width: "10.5%", targets: 9 },  // salida
      { width: "10.5%", targets: 10 }, // saldo
      { width: "10.5%", targets: 11 }  // bodega
    ],

    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Inventario',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function(){ inventario_transferencia(); }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Exportar a Excel',
        title: 'Reporte Inventario',
        className: 'table_reportes btn btn-success ocultar',
        // Excluimos botón (0), imagen (2) y botón transferir (12)
        exportOptions: { columns: [1,3,4,5,6,7,8,9,10,11] }
      },
      {
        extend: 'pdfHtml5',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'Exportar a PDF',
        orientation: 'landscape',
        title: 'Reporte Inventario',
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: { columns: [1,3,4,5,6,7,8,9,10,11] },
        customize: function(doc){
          if (typeof imagen !== 'undefined' && imagen){
            doc.content.splice(0,0,{ image: imagen, width:100, height:45, margin:[0,0,0,12] });
          }
        }
      }
    ],

    footerCallback: function(row, data, start, end, display){
      var api = this.api();
      var sum = (idx) => api.column(idx, {page:'current'}).data().reduce((a,b)=> toNumber(a)+toNumber(b), 0);
      // Índices de columnas numéricas en esta tabla:
      var totalSaldoAnterior = sum(7);
      var totalEntrada       = sum(8);
      var totalSalida        = sum(9);
      var total              = (totalEntrada + totalSaldoAnterior) - totalSalida;

      $('#anterior-footer-movimiento').html(formatNumber(totalSaldoAnterior));
      $('#entrada-footer-movimiento').html(formatNumber(totalEntrada));
      $('#salida-footer-movimiento').html(formatNumber(totalSalida));
      $('#total-footer-movimiento').html(formatNumber(total));
    },

    initComplete: function(){
      this.api().order([1,'desc']).draw();
    },

    drawCallback: function(){
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  // Tooltips
  $('#dataTablaMovimientos').on('draw.dt', function(){
    $('[data-toggle="tooltip"]').tooltip();
  });

  transferencia_producto_dataTable("#dataTablaMovimientos tbody", table_movimientos);
  cambiarVencimientoProducto_dataTable("#dataTablaMovimientos tbody", table_movimientos);
};

/* ========= TRANSFERIR PRODUCTO/BODEGA ========= */
var transferencia_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_transferencia");
  $(tbody).on("click", "button.table_transferencia", function() {
    var data = table.row($(this).parents("tr")).data();
    $('#formTransferencia')[0].reset();

    if (toNumber(data.superior) > 0) {
      showNotify('error','Error','No se puede hacer transferencia de producto que depende de otro inventario');
      return false;
    }

    $('#formTransferencia #productos_id').val(data.productos_id);
    $('#formTransferencia #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferencia #id_bodega_actual').val(data.id_bodega);
    $('#formTransferencia #lote_id_productos').val(data.lote_id);
    $('#formTransferencia #empresa_id_productos').val(data.empresa_id);

    $('#modal_transferencia_producto').modal({
      show: true, keyboard: false, backdrop: 'static'
    });
  });
};

$('#formTransferencia').on('submit', function(e){
  e.preventDefault();
  var form = $("#formTransferencia");
  var respuesta = form.children('.RespuestaAjax');

  swal({
    title: "¿Estas seguro?",
    text: "¿Desea transferir este producto?",
    icon: "warning",
    buttons: { cancel:{text:"Cancelar",visible:true}, confirm:{text:"¡Sí, transferir el producto!"} },
    buttons: true, dangerMode: true, closeOnEsc: false, closeOnClickOutside: false
  }).then((willConfirm)=>{
    if (willConfirm){
      var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';
      $.ajax({
        type:'POST', url, data: form.serialize(),
        beforeSend: function(){
          $('#modal_transferencia_producto').modal({show:false, keyboard:false, backdrop:'static'});
        },
        success: function(data){ respuesta.html(data); }
      });
    }
  });
});

/* ========= CAMBIAR FECHA DE CADUCIDAD ========= */
var cambiarVencimientoProducto_dataTable = function(tbody, table){
  $(tbody).off("click","button.table_change_date");
  $(tbody).on("click","button.table_change_date", function(){
    var data = table.row($(this).parents("tr")).data();
    $('#formTransferenciaCambiarFecha')[0].reset();
    $('#formTransferenciaCambiarFecha #productos_id').val(data.productos_id);
    $('#formTransferenciaCambiarFecha #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferenciaCambiarFecha #id_bodega_actual').val(data.id_bodega);
    $('#formTransferenciaCambiarFecha #cantidad_productos').val(toNumber(data.saldo));
    $('#formTransferenciaCambiarFecha #empresa_id_productos').val(data.empresa_id);
    $('#formTransferenciaCambiarFecha #lote_id_productos').val(data.lote_id);

    $('#modalCambiarFechaProducto').modal({ show:true, keyboard:false, backdrop:'static' });
  });
};

$('#EditarFechaVencimiento').on('click', function(e){
  e.preventDefault();
  var form = $("#formTransferenciaCambiarFecha");
  var respuesta = form.children('.RespuestaAjax');

  if (form[0].checkValidity()){
    var url = '<?php echo SERVERURL;?>ajax/modificarFechaVencimientoProductosAjax.php';
    $.ajax({
      type:'POST', url, data: form.serialize(),
      beforeSend: function(){
        $('#modalCambiarFechaProducto').modal({ show:false, keyboard:false, backdrop:'static' });
      },
      success: function(data){ respuesta.html(data); }
    });
  }else{
    form[0].reportValidity();
  }
});

/* ========= CARGA DE COMBOS ========= */
function getTipoProductos(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';
  $.ajax({
    type:"POST", url, async:true, success:function(data){
      $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').html(data).selectpicker('refresh');
      $('#formMovimientos #movimientos_tipo_producto_id').html(data).selectpicker('refresh');
      getProductosMovimientos($('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val());
    }
  });
}

$(()=> {
  $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').on('change', function(){
    var tipo = $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() || 1;
    getProductosMovimientos(tipo);
    return false;
  });
});

function getProductosMovimientos(tipo_producto_id){
  var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';
  $.ajax({
    type:"POST", url, data:'tipo_producto_id='+tipo_producto_id, success:function(data){
      $('#form_main_movimientos_transferencia #inventario_productos_id').html(data).selectpicker('refresh');
    }
  });
}

$(document).ready(function(){
  $("#modal_transferencia_producto").on('shown.bs.modal', function(){
    $(this).find('#formTransferencia #cantidad_movimiento').focus();
  });
});

function getAlmacen(){
  var url = '<?php echo SERVERURL;?>core/getAlmacen.php';
  $.ajax({
    type:"POST", url, async:true, success:function(data){
      $('#form_main_movimientos_transferencia #almacen').html(data).selectpicker('refresh');
      $('#formTransferencia #id_bodega').html(data).selectpicker('refresh');
    }
  });
}
</script>