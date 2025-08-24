<script>
var registro = false;

// ---------- Utils de números (formato y coerción) ----------
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
// Render genérico con “badge” y orden numérico correcto
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

$(() => {
  funciones();
  listar_movimientos();

  $('#movimientos, #registroMovimientos').css('cursor','pointer');

  $('#form_main_movimientos #search').on('click', function(e){
    e.preventDefault();
    listar_movimientos();
  });

  // Limpiar filtros
  $('#form_main_movimientos').on('reset', function(){
    $('#form_main_movimientos .selectpicker').val('').selectpicker('refresh');
    listar_movimientos();
  });
});

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

$('#form_main_movimientos #categoria_id, #form_main_movimientos #fechai, #form_main_movimientos #fechaf, #form_main_movimientos #almacen, #producto_movimiento_filtro, #cliente_movimiento_filtro, #inventario_tipo_productos_id')
  .on('change', listar_movimientos);

// ---------------- MOVIMIENTOS (DataTable) ----------------
var listar_movimientos = function () {
  var tipo_producto_id = $('#form_main_movimientos #inventario_tipo_productos_id').val();
  var fechai  = $("#form_main_movimientos #fechai").val();
  var fechaf  = $("#form_main_movimientos #fechaf").val();
  var bodega  = $("#form_main_movimientos #almacen").val();
  var producto= $("#producto_movimiento_filtro").val();
  var cliente = $('#cliente_movimiento_filtro').val();

  // Limpia estado guardado (por si DataTables forzó otro orden)
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
      url: "<?php echo SERVERURL;?>core/llenarDataTableMovimientos.php",
      data: { tipo_producto_id, fechai, fechaf, bodega, producto, cliente }
    },
    columns: [
      // Fecha: viene en 'YYYY-MM-DD HH:mm:ss' -> orden lexical correcto
      { data: "fecha_registro" },

      // Imagen
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

      { data:"barCode" },
      { data:"cliente" },
      { data:"producto" },
      { data:"medida" },
      { data:"documento" },

      // Números con formato y orden correcto
      { data:"saldo_anterior", className:"text-right", render: badgeRender(n => n>0 ? '#28a745' : '#ff6f61') },
      { data:"entrada",        className:"text-right", render: badgeRender(n => n>0 ? '#17a2b8' : '#f39c12') },
      { data:"salida",         className:"text-right", render: badgeRender(n => n>0 ? '#ffc107' : '#dc3545') },
      { data:"saldo",          className:"text-right", render: badgeRender(n => n>=0 ? '#007bff' : '#ff6347') },

      { data:"comentario" },
      { data:"bodega" }
    ],

    // Última fecha primero
    order: [[0, 'desc']],

    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,

    columnDefs: [
      { width: "13.5%", targets: 0, orderable: true },
      { width: "10.5%", targets: 1 },
      { width: "20.5%", targets: 2 },
      { width: "5.5%",  targets: 3 },
      { width: "18.5%", targets: 4 },
      { width: "10.5%", targets: 5 },
      { width: "10.5%", targets: 6 },
      { width: "10.5%", targets: 7 },
      { width: "10.5%", targets: 8 },
      { width: "10.5%", targets: 9 },
      { width: "10.5%", targets: 10 },
      { width: "10.5%", targets: 11 }
    ],

    footerCallback: function(row, data, start, end, display){
      var api = this.api();
      var sum = (idx) => api.column(idx, {page:'current'}).data().reduce((a,b)=> toNumber(a)+toNumber(b), 0);
      var totalSaldoAnterior = sum(8);
      var totalEntrada       = sum(9);
      var totalSalida        = sum(10);
      var total              = (totalSaldoAnterior + totalEntrada) - totalSalida;

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
        // Excluimos la columna de imagen (1)
        exportOptions: { columns: [0,2,3,4,5,6,7,8,9,10,11,12,13] }
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
        exportOptions: { columns: [0,2,3,4,5,6,7,8,9,10,11,12,13] },
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

  // Tooltips al redibujar
  $('#dataTablaMovimientos').on('draw.dt', function(){
    $('[data-toggle="tooltip"]').tooltip();
  });

  table_movimientos.search('').draw();
  $('#buscar').focus();
};

// --------- (Resto de funciones: transferencias, AJAX de combos, modales, etc.) ---------
// ……….  (Tu código de soporte abajo permanece igual)

//TRANSFERIR PRODUCTO/BODEGA
var transferencia_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_transferencia");
  $(tbody).on("click", "button.table_transferencia", function() {
    var data = table.row($(this).parents("tr")).data();
    $('#formTransferencia #productos_id').val(data.productos_id);
    $('#formTransferencia #nameProduct').html(data.producto);
    $('#modal_transferencia_producto').modal({ show:true, keyboard:false, backdrop:'static' });
  });
};

$("#putEditarBodega").click(function() {
  var form = $("#formTransferencia");
  var respuesta = form.children('.RespuestaAjax');
  var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';
  $.ajax({
    type: 'POST', url, data: $('#formTransferencia').serialize(),
    beforeSend: function(){ $('#modal_transferencia_producto').modal({show:false, keyboard:false, backdrop:'static'}); },
    success: function(data){ $('#modal_transferencia_producto').modal('toggle'); respuesta.html(data); }
  });
});

function getAlmacen() {
  var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';
  $.ajax({ type:"POST", url, async:true, success:function(data){
    $('#form_main_movimientos #almacen').html(data).selectpicker('refresh');
    $('#formMovimientoInventario #almacen_modal').html(data).selectpicker('refresh');
  }});
}
function getAlmacenModal() {
  var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';
  $.ajax({ type:"POST", url, async:true, success:function(data){
    $('#formMovimientos #almacen_modal').html(data).selectpicker('refresh');
  }});
}

// Lotes por producto
$('#formMovimientos #movimiento_producto').change(function(){
  getLotesProductos($(this).val());
});
function getLotesProductos(producto_id){
  var url = '<?php echo SERVERURL;?>core/getLotesProductos.php';
  $.ajax({ type:"POST", url, data:{producto_id}, async:true, success:function(data){
    $('#formMovimientos #movimiento_lote').html(data).selectpicker('refresh');
  }});
}

// Tipos de producto
function getTipoProductos(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';
  $.ajax({ type:"POST", url, async:true, success:function(data){
    $('#form_main_movimientos #inventario_tipo_productos_id').html(data).selectpicker('refresh');
  }});
}
function getTipoProductosModal(){
  var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientosModal.php';
  $.ajax({ type:"POST", url, async:true, success:function(data){
    $('#formMovimientos #movimientos_tipo_producto_id').html(data).selectpicker('refresh');
  }});
}

function getProductoOperacion(){
  var url = '<?php echo SERVERURL;?>core/getOperacion.php';
  $.ajax({ type:"POST", url, success:function(data){
    $('#formMovimientos #movimiento_operacion').html(data).selectpicker('refresh');
    $('#formMovimientoInventario #movimiento_producto').html(data).selectpicker('refresh');
  }});
}

$(document).ready(function(){
  $('#form_main_movimientos #inventario_tipo_productos_id').on('change', function(){
    var tipo = $('#form_main_movimientos #inventario_tipo_productos_id').val() || 1;
    getProductosMovimientos(tipo);
  });
  $('#formMovimientos #movimientos_tipo_producto_id').on('change', function(){
    var tipo = $('#formMovimientos #movimientos_tipo_producto_id').val() || 1;
    getProductosMovimientos(tipo);
  });
});

function getProductosMovimientos(tipo_producto_id){
  var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';
  $.ajax({
    type:"POST", url, data:'tipo_producto_id='+tipo_producto_id, success:function(data){
      $('#form_main_movimientos #producto_movimiento_filtro').html(data).selectpicker('refresh');
      $('#formMovimientos #movimiento_producto').html(data).selectpicker('refresh');
    }
  });
}

function getClientes(){
  var url = '<?php echo SERVERURL;?>core/getClientesHostProductos.php';
  $.ajax({
    type:"POST", url, async:true, success:function(data){
      $('#form_main_movimientos #cliente_movimiento_filtro').html(data).selectpicker('refresh');
      $('#formMovimientoInventario #cliente_movimientos').html(data).selectpicker('refresh');
    }
  });
}
function getClientesModal(){
  var url = '<?php echo SERVERURL;?>core/getClientesHostProductosModal.php';
  $.ajax({
    type:"POST", url, async:true, success:function(data){
      $('#formMovimientos #cliente_movimientos').html(data).selectpicker('refresh');
    }
  });
}

// Modal movimientos
function modal_movimientos(){
  $('#formMovimientos').attr({'data-form':'save','action':'<?php echo SERVERURL; ?>ajax/agregarMovimientoProductosAjax.php'});
  $('#formMovimientos')[0].reset();
  $('#formMovimientos #proceso_movimientos').val("Registro");
  $('#modal_movimientos').show();
  funciones();
  $('#modal_movimientos').modal({ show:true, keyboard:false, backdrop:'static' });
}

$(document).ready(function(){
  $("#modal_buscar_productos_movimientos").on('shown.bs.modal', function(){ $(this).find('#formulario_busqueda_productos_movimientos #buscar').focus(); });
  $("#modal_movimientos").on('shown.bs.modal', function(){ $(this).find('#formularioMovimientos #movimiento_categoria').focus(); });
  $("#modal_transferencia_producto").on('shown.bs.modal', function(){ $(this).find('#formTransferencia #cantidad_movimiento').focus(); });
});

$('#movimientos').on('click', function(){
  if (registro === true){
    registro = false;
    $('#movimientos').removeClass('active');
    $('#main_inventario').show();
    $('#movimiento_inventario').hide();
    $('#registroMovimientos').addClass('active');
  }
});
$('#registroMovimientos').on('click', function(){
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

// Búsqueda por código de barras
const BusquedaProducto = (barcode) => {
  var url = '<?php echo SERVERURL;?>core/buscar_producto.php';
  $.ajax({
    type:'POST', url, data:{ barcode: barcode }, dataType:'json',
    success:function(registro){
      if (registro.success){
        $('#formMovimientos #movimientos_tipo_producto_id').val(registro.tipo_producto_id).selectpicker('refresh');
        $('#formMovimientos #movimiento_producto').val(registro.productos_id).selectpicker('refresh');
        $('#formMovimientos #almacen_modal').val('').selectpicker('refresh');
        $('#formMovimientos #movimiento_cantidad').focus();
      }else{
        showNotify('error','Error',registro.message);
      }
    },
    error:function(){ showNotify('error','Error','Hubo un problema en la comunicación con el servidor'); }
  });
};

$('#formMovimientos #produto_barcode').on('keypress', (event)=>{
  if (event.which === 13){
    event.preventDefault();
    let barcode = $(event.target).val().trim();
    if (barcode.length === 0){
      showNotify('error','Error','Lo sentimos, debe ingresar un nombre de producto, o escanear un código de barras');
      $('#formMovimientos #produto_barcode').focus();
      return;
    }
    if ($('input[name="movimiento_operacion"]:checked').length === 0){
      showNotify('error','Error','Debe seleccionar un tipo de operación (Entrada o Salida)');
      $('input[name="movimiento_operacion"]').first().focus();
      return;
    }
    BusquedaProducto(barcode);
  }
});

$("#modal_movimientos").on('shown.bs.modal', function(){
  $(this).find('#formMovimientos #produto_barcode').focus();
});

$(function(){
  $("input[name='movimiento_operacion'], label[for='entrada'], label[for='salida']").click(function(){
    var tipoOperacion = $("input[name='movimiento_operacion']:checked").val();
    var barcode = $('#formMovimientos #produto_barcode').val().trim();
    if (tipoOperacion){
      $('#cliente_movimientos').prop('disabled', tipoOperacion !== 'salida');
      $('#produto_barcode').focus();
      $('#proceso_movimientos').val(tipoOperacion === 'entrada' ? 'Operación: Entrada' : 'Operación: Salida');
      if (barcode.length > 0) BusquedaProducto(barcode);
    }else{
      $('#proceso_movimientos').val('Selecciona una operación');
    }
  });
});
</script>