<script>
    //FAactura.php - js de las facturas escritorio
$(() => {
    // Evento para el botón de Generar Reporte
    $('#formulario_busqueda_cotizaciones').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_cotizaciones();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_busqueda_cotizaciones')[0].reset();
        $('#formulario_busqueda_cotizaciones .selectpicker').selectpicker('refresh');
        listar_busqueda_cotizaciones();
    });  

    // Evento para el botón de Generar Reporte
    $('#formulario_busqueda_cuentas_cobrar_clientes').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_cuentas_por_cobrar_clientes();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_busqueda_cuentas_cobrar_clientes')[0].reset();
        $('#formulario_busqueda_cuentas_cobrar_clientes .selectpicker').selectpicker('refresh');
        listar_busqueda_cuentas_por_cobrar_clientes();
    });   

    // Evento para el botón de Generar Reporte
    $('#formulario_bill_draft').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_bill_draf();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_bill_draft')[0].reset();
        $('#formulario_bill_draft .selectpicker').selectpicker('refresh');
        listar_busqueda_bill_draf();
    });

    // Evento para el botón de Generar Reporte
    $('#formulario_bill').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_bill();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_bill')[0].reset();
        $('#formulario_bill .selectpicker').selectpicker('refresh');
        listar_busqueda_bill();
    });

});

var row = 0;

$(() => {
    getCajero();
    getConsumidorFinal();
    getConsultarAperturaCaja();
    validarAperturaCajaUsuario();
    getBanco();
    getTotalFacturasDisponibles();
    getReporteCotizacion();
    getReporteFactura();
    getEstadoFacturaCredito();
    getCollaboradoresModalPagoFacturas();
    getFacturador();
    getVendedores();
    getClientesFacturasCXC();
});

function getClientesFacturasCXC() {
    var url = '<?php echo SERVERURL; ?>core/getClientesCXC.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').html("");
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').html(data);
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').selectpicker('refresh');
        }
    });
}

function getFacturador() {
    var url = '<?php echo SERVERURL; ?>core/getFacturador.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_bill #facturador').html("");
            $('#formulario_bill #facturador').html(data);
            $('#formulario_bill #facturador').selectpicker('refresh');
        }
    });
}

function getVendedores() {
    var url = '<?php echo SERVERURL; ?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#formulario_bill #vendedor').html("");
            $('#formulario_bill #vendedor').html(data);
            $('#formulario_bill #vendedor').selectpicker('refresh');
        }
    });
}

//INICIO CONSULTA FACTURAS BORRADOR
$(() => {
    $("#modal_buscar_bill_draft").on('shown.bs.modal', function() {
        $(this).find('#formulario_bill_draft #buscar').focus();
    });
});
//FIN CONSULTA FACTURAS BORRADOR

//INIICO CONSULTA DE FACTURAS
$(() => {
    $("#modal_buscar_bill").on('shown.bs.modal', function() {
        $(this).find('#formulario_bill #buscar').focus();
    });
});

$('#formulario_bill #tipo_factura_reporte').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #facturador').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #vendedor').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #fechai').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #fechaf').on("change", function(e) {
    listar_busqueda_bill();
});
//FIN CONSULTA DE FACTURAS

//INICIO CUENTAS POR COBRAR CLIENTES
$('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').on("change", function(e) {
    listar_busqueda_bill();
});


$('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

$('#formulario_busqueda_cuentas_cobrar_clientes #fechai').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

$('#formulario_busqueda_cuentas_cobrar_clientes #fechaf').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

//FIN CUENTAS POR COBRAR CLIENTES

function resetRow() {
    $("#invoice-form #bill_row").val(0);
}

$('#formulario_busqueda_productos_facturacion #almacen_facturas').on('change', function() {
    listar_productos_factura_buscar();
});

//INICIO BUSQUEDA FROMULARIO CLIENTES FACTURACION
$("#invoice-form #add_cliente").on("click", function(e) {
    e.preventDefault();
    searchCustomersBill();
});

function searchCustomersBill() {
    listar_clientes_factura_buscar();
    $('#modal_buscar_clientes_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$("#invoice-form #btn_apertura").on("click", function(e) {
    e.preventDefault();
    formAperturaBill();
});

function formAperturaBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Aperturar Caja");
    $('#open_caja').show();
    $('#close_caja').hide();
    $('#formAperturaCaja #monto_apertura_grupo').show();

    $('#formAperturaCaja').attr({
        'data-form': 'save'
    });
    $('#formAperturaCaja').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addAperturaCajaAjax.php'
    });

    $('#modal_apertura_caja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#reg_factura').on('click', function(e) {
    ProcesarFactura();
});

function ProcesarFactura(){
    $('#invoice-form').attr({
        'data-form': 'save'
    });
    $('#invoice-form').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addFacturaAjax.php'
    });
    $("#invoice-form").submit();
}

$("#guardar_factura").on("click", function(e) {
    GuardarFactura();
});

function GuardarFactura(){
    $('#invoice-form').attr({
        'data-form': 'save'
    });
    $('#invoice-form').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addFacturaOpenAjax.php'
    });
    $("#invoice-form").submit();
}

$("#invoice-form #btn_cierre").on("click", function(e) {
    e.preventDefault();
    formCierreBill();
});

function formCierreBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
    $('#open_caja').hide();
    $('#close_caja').show();
    $('#formAperturaCaja #monto_apertura_grupo').hide();

    $('#formAperturaCaja').attr({
        'data-form': 'save'
    });
    $('#formAperturaCaja').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addCierreCajaFacturasAjax.php'
    });

    $('#modal_apertura_caja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN BUSQUEDA FROMULARIO CLIENTES FACTURACION	

//INICIO INVOICES
//INICIO BUSQUEDA CLIENTES EN FACTURACION
$('#invoice-form #buscar_clientes').on('click', function(e) {
    e.preventDefault();
    listar_clientes_factura_buscar();
    $('#modal_buscar_clientes_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var listar_clientes_factura_buscar = function() {
    var table_clientes_factura_buscar = $("#DatatableClientesBusquedaFactura").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableClientes.php"
        },
        "columns": [{
                "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-copy'></span></button>"
            },
            {
                "data": "cliente"
            },
            {
                "data": "rtn"
            },
            {
                "data": "telefono"
            },
            {
                "data": "correo"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Clientes',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_clientes_factura_buscar();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Clientes',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_clientes();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_clientes_factura_buscar.search('').draw();
    $('#buscar').focus();

    view_clientes_busqueda_factura_dataTable("#DatatableClientesBusquedaFactura tbody",
        table_clientes_factura_buscar);
}

var view_clientes_busqueda_factura_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_view");
    $(tbody).on("click", "button.table_view", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#invoice-form #cliente_id').val(data.clientes_id);
        $('#invoice-form #cliente').val(data.cliente);
        $('#invoice-form #client-customers-bill').html("<b>Cliente: </b> " + data.cliente);
        $('#invoice-form #rtn-customers-bill').html("<b>RTN: </b>" + data.rtn);
        $('#modal_buscar_clientes_facturacion').modal('hide');
    });
}
//FIN BUSQUEDA CLIENTES EN FACTURACION

//INICIO BUSQUEDA COLABORADORES EN FACTURACION
function serchColaboradoresBill() {
    listar_colaboradores_buscar_factura();
    $('#modal_buscar_colaboradores_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#invoice-form #add_vendedor').on('click', function(e) {
    e.preventDefault();
    serchColaboradoresBill();
});

var listar_colaboradores_buscar_factura = function() {
    var table_colaboradores_buscar_factura = $("#DatatableColaboradoresBusquedaFactura").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableColaboradoresFacturas.php"
        },
        "columns": [{
                "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-copy'></span></button>"
            },
            {
                "data": "colaborador"
            },
            {
                "data": "identidad"
            },
            {
                "data": "telefono"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "25%",
                targets: 0
            },
            {
                width: "25%",
                targets: 1
            },
            {
                width: "25%",
                targets: 2
            },
            {
                width: "25%",
                targets: 3
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_colaboradores_buscar_factura();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_colaboradores();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_colaboradores_buscar_factura.search('').draw();
    $('#buscar').focus();

    view_colaboradores_busqueda_factura_dataTable("#DatatableColaboradoresBusquedaFactura tbody",
        table_colaboradores_buscar_factura);
}

var view_colaboradores_busqueda_factura_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_view");
    $(tbody).on("click", "button.table_view", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#invoice-form #colaborador_id').val(data.colaborador_id);
        $('#invoice-form #colaborador').val(data.colaborador);
        $('#invoice-form #colaborador').val(data.colaborador);
        $('#invoice-form #vendedor-customers-bill').html("<b>Vendedor: </b> " + data.colaborador);
        $('#modal_buscar_colaboradores_facturacion').modal('hide');
    });
}
//FIN BUSQUEDA COLABORADORES EN FACTURACION

//INICIO BUSQUEDA PRODUCTOS FACTURA
$(() => {
    $("#invoice-form #invoiceItem").on('click', '.buscar_productos', function(e) {
        e.preventDefault();
        listar_productos_factura_buscar();
        var row_index = $(this).closest("tr").index();
        var col_index = $(this).closest("td").index();

        $('#formulario_busqueda_productos_facturacion #row').val(row_index);
        $('#formulario_busqueda_productos_facturacion #col').val(col_index);
        $('#modal_buscar_productos_facturacion').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
});

var listar_productos_factura_buscar = function() {
    // Bodega seleccionada (si viene vacío, usa 1)
    var bodega = $("#formulario_busqueda_productos_facturacion #almacen_facturas").val();
    bodega = (bodega === "" || bodega == null) ? 1 : bodega;

    var table_productos_factura_buscar = $("#DatatableProductosBusquedaFactura").DataTable({
        destroy: true,
        processing: true,
        deferRender: true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableProductosFacturas.php",
            "data": { "bodega": bodega }
        },
        "columns": [
            { "defaultContent": "<button class='table_view btn btn-secondary ocultar'><span class='fas fa-cart-plus fa-lg'></span></button>" },
            {
                "data": "image",
                "orderable": false,
                "render": function (data, type, row, meta) {
                    var defaultImageUrl = '<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png';
                    var imageUrl = data ? '<?php echo SERVERURL; ?>vistas/plantilla/img/products/' + data : defaultImageUrl;
                    var safeTitle = (row && row.nombre) ? String(row.nombre).replace(/"/g, '&quot;') : 'Imagen';
                    return ''
                    + '<div class="d-flex align-items-center">'
                    +   '<img class="table-image mr-2" src="' + imageUrl + '" alt="' + safeTitle + '" style="cursor:pointer;">'
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
            { "data": "barCode" },
            { "data": "nombre" },
            {
                "data": "cantidad",
                render: function(data, type) {
                    if (data == null) data = 0;
                    var number = $.fn.dataTable.render.number(',', '.', 2, '').display(data);
                    if (type === 'display') {
                        let color = (parseFloat(data) < 0) ? 'red' : 'green';
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
                    return number;
                },
            },
            { "data": "medida" },
            { "data": "tipo_producto_nombre" },
            {
                "data": "precio_venta",
                render: function(data, type) {
                    var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
                    if (type === 'display') {
                        let color = (parseFloat(data) < 0) ? 'red' : 'green';
                        return '<span style="color:' + color + '">' + number + '</span>';
                    }
                    return number;
                },
            },
            {
                // *** BODEGA ***
                // Tu PHP manda: almacen_facturas (nombre) y almacen_id (id).
                // Además, los servicios (tipo_producto_id = 2) no tienen bodega.
                "data": null,
                "render": function(data, type, row) {
                    var esServicio = String(row.tipo_producto_id || '') === '2';
                    if (esServicio) return "Sin bodega";

                    var nombreBodega = (row.almacen_facturas || '').toString().trim();
                    var idBodega = (row.almacen_id == null || row.almacen_id === '') ? 0 : parseInt(row.almacen_id, 10);

                    if (idBodega > 0 && nombreBodega !== "") {
                        return nombreBodega;
                    }
                    return "Sin bodega";
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "responsive": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "2%",  targets: 0 },
            { width: "17%", targets: 1 },
            { width: "17%", targets: 2 },
            { width: "10%", targets: 3 },
            { width: "10%", targets: 4 },
            { width: "10%", targets: 5 },
            { width: "12%", targets: 6 },
            { width: "12%", targets: 7 },
            { width: "12%", targets: 8 }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_productos_cotizacion_buscar();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_productos();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });

    table_productos_factura_buscar.search('').draw();
    $('#buscar').focus();

    view_productos_busqueda_factura_dataTable("#DatatableProductosBusquedaFactura tbody", table_productos_factura_buscar);
};

var view_productos_busqueda_factura_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_view, td img");
  $(tbody).on("click", "button.table_view, td img", async function(e) {
    e.preventDefault();

    var row = parseInt($("#invoice-form #bill_row").val() || '0', 10);

    // Caja abierta
    if (getConsultarAperturaCaja() == 2) {
      showNotify('error', 'Error', 'Lo sentimos debe aperturar la caja antes de continuar');
      return;
    }

    getTotalFacturasDisponibles();

    // Validar cliente y vendedor
    if (
      $("#invoice-form #cliente_id").val()     !== "" &&
      $("#invoice-form #cliente").val()        !== "" &&
      $("#invoice-form #colaborador_id").val() !== "" &&
      $("#invoice-form #colaborador").val()    !== ""
    ) {
      var data = table.row($(this).parents("tr")).data() || {};

      // Normalizaciones
      var tipoProductoId = Number(data.tipo_producto_id || 0);           // 1=producto, 2=servicio
      var esServicio     = (tipoProductoId === 2);
      var almacenId      = (data.almacen_id == null || data.almacen_id === '') ? 0 : parseInt(data.almacen_id, 10);
      var existencia     = parseFloat(data.cantidad || 0);

      // ===== Validación de inventario (solo productos con bodega válida) =====
      if (!esServicio && almacenId > 0) {
        var facturar_cero = !!facturarEnCeroAlmacen(almacenId); // tu método (sincrónico)
        if (existencia <= 0 && !facturar_cero) {
          showNotify('error', 'Error', 'No se puede facturar este producto con inventario en cero');
          return false;
        }
      }

      // ===== Set de campos básicos de la fila =====
      $('#invoice-form #invoiceItem #productos_id_'        + row).val(data.productos_id);
      $('#invoice-form #invoiceItem #bar-code-id_'         + row).val(data.barCode || '');
      $('#invoice-form #invoiceItem #productName_'         + row).val(data.nombre || '');
      $('#invoice-form #invoiceItem #quantity_'            + row).val(1).focus();
      $('#invoice-form #invoiceItem #price_'               + row).val(data.precio_venta || 0);
      $('#invoice-form #invoiceItem #discount_'            + row).val(0);
      $('#invoice-form #invoiceItem #isv_'                 + row).val(parseInt(data.impuesto_venta || 0, 10)); // 1=grava, 0=no
      $('#invoice-form #invoiceItem #precio_mayoreo_'      + row).val(data.precio_mayoreo || 0);
      $('#invoice-form #invoiceItem #cantidad_mayoreo_'    + row).val(data.cantidad_mayoreo || 0);
      $('#invoice-form #invoiceItem #medida_'              + row).val(data.medida || '');
      $('#invoice-form #invoiceItem #bodega_'              + row).val(almacenId || '');
      $('#invoice-form #invoiceItem #precio_real_'         + row).val(data.precio_venta || 0);

      // FLAGS por línea (desde PHP): 1 si aplica ese ISV
      if ($('#isv1_flag_' + row).length) $('#isv1_flag_' + row).val( parseInt(data.isv1 || 0, 10) === 1 ? 1 : 0 );
      if ($('#isv2_flag_' + row).length) $('#isv2_flag_' + row).val( parseInt(data.isv2 || 0, 10) === 1 ? 1 : 0 );

      // Inicializa montos ISV de la línea
      if ($('#valor_isv_'  + row).length)  $('#valor_isv_'  + row).val('0.00'); // ISV id=1 (p.e. 15%)
      if ($('#valor_isv1_' + row).length)  $('#valor_isv1_' + row).val('0.00'); // ISV id=2 (p.e. 18%)

      // Texto visible de la línea (si lo usas)
      if (typeof actualizarTextoProducto === 'function') {
        actualizarTextoProducto(row, data.nombre || '', data.medida || '');
      }

      // ===== Calcular ISV de la línea (usa flags + % actuales de tabla isv) =====
      await recalcISVForRow(row); // escribe valor_isv_# y valor_isv1_#

      // Totales y nueva fila
      calculateTotalFacturas();
      addRowFacturas();

      // UI: ocultar iconos de búsqueda en la fila actual y la anterior
      if (row > 0) {
        var icon_search = row - 1;
        $("#invoice-form #invoiceItem #icon-search-bar_" + icon_search).hide();
      }
      $("#invoice-form #invoiceItem #icon-search-bar_" + row).hide();

      // Cerrar modal
      $('#modal_buscar_productos_facturacion').modal('hide');

      // (El incremento real de bill_row lo maneja addRowFacturas en tu flujo)
      row++;

    } else {
      showNotify('error', 'Error', 'Lo sentimos no se puede seleccionar un producto, por favor verifica cliente y vendedor.');
    }
  });
};
//FIN BUSQUEDA PRODUCTOS FACTURA

/* ===== helper: actualiza price_# por regla de mayoreo ===== */
function actualizarPrecioPorMayoreo(row_index){
  var cm = parseFloat($('#invoice-form #invoiceItem #cantidad_mayoreo_' + row_index).val()) || 0;
  var pm = parseFloat($('#invoice-form #invoiceItem #precio_mayoreo_' + row_index).val()) || 0;
  var q  = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 0;
  var pr = parseFloat($('#invoice-form #invoiceItem #precio_real_' + row_index).val()) || 0;

  var nuevo = (cm > 0 && pm > 0 && q >= cm) ? pm : pr;
  $('#invoice-form #invoiceItem #price_' + row_index).val(nuevo);
}

/* ===== listener único: blur + keyup en cantidad ===== */
$(() => {
  $("#invoice-form #invoiceItem").on('blur keyup', '.buscar_cantidad', function() {
    var row_index = $(this).closest("tr").index();

    // 1) Precio mayoreo vs real
    actualizarPrecioPorMayoreo(row_index);

    // 2) Recalcula ISV por línea y totales
    (async () => {
      await recalcularISVLinea(row_index);
      calculateTotalFacturas();
    })();
  });
});

function generarFilaFactura(count) {
  let htmlRow = '<tr>';

  htmlRow += '<td><input class="itemRow" id="itemRow_' + count + '" type="checkbox"></td>';

  // --- HIDDENS de control/valores ---
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="referenciaProducto[]" id="referenciaProducto_' + count + '" class="form-control" placeholder="Referencia Producto Precio" autocomplete="off">';
  htmlRow += '<input type="hidden" name="isv[]" id="isv_' + count + '" class="form-control" placeholder="Producto ISV (1=grava,0=no)" autocomplete="off">';
  // ISV1 (id=1) porcentaje (ej. 15.00)
  htmlRow += '<input type="hidden" name="valor_isv[]" id="valor_isv_' + count + '" class="form-control" placeholder="Valor ISV (id=1)" autocomplete="off">';
  // ISV2 (id=2) porcentaje (ej. 18.00)  <<--- NUEVO
  htmlRow += '<input type="hidden" name="valor_isv1[]" id="valor_isv1_' + count + '" class="form-control" placeholder="Valor ISV2 (id=2)" autocomplete="off">';
  htmlRow += '<input type="hidden" name="facturas_detalle_id[]" id="facturas_detalle_id_' + count + '" class="form-control" placeholder="Código Producto" autocomplete="off">';
  htmlRow += '<input type="hidden" name="productos_id[]" id="productos_id_' + count + '" class="form-control inputfield-details1" placeholder="Código del Producto" autocomplete="off">';

  htmlRow += '<input type="hidden" name="isv1_flag[]" id="isv1_flag_' + count + '" value="0">';
  htmlRow += '<input type="hidden" name="isv2_flag[]" id="isv2_flag_' + count + '" value="0">';

  // Código / búsqueda
  htmlRow += '<div class="input-group mb-3"><div class="input-group-prepend">';
  htmlRow += '<button type="button" class="btn btn-link buscar_productos p-0" data-toggle="tooltip" title="Búsqueda de Productos" id="icon-search-bar_' + count + '">';
  htmlRow += '<i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i></button></div>';
  htmlRow += '<input type="text" name="bar-code-id[]" id="bar-code-id_' + count + '" class="form-control product-bar-code inputfield-details1" placeholder="Código del Producto" autocomplete="off"></div>';
  htmlRow += '</td>';

  // Descripción (texto visible + hidden)
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="productName[]" id="productName_' + count + '" autocomplete="off">';
  htmlRow += '<span id="productName_text_' + count + '" class="product-description">Descripción del Producto</span>';
  htmlRow += '</td>';

  // Cantidad
  htmlRow += '<td>';
  htmlRow += '<input type="number" name="quantity[]" id="quantity_' + count + '" step="0.01" placeholder="Cantidad" class="buscar_cantidad form-control inputfield-details" autocomplete="off">';
  htmlRow += '<input type="hidden" name="cantidad_mayoreo[]" id="cantidad_mayoreo_' + count + '" step="0.01" class="form-control inputfield-details" autocomplete="off">';
  htmlRow += '</td>';

  // Medida (texto visible + hidden)
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="medida[]" id="medida_' + count + '" autocomplete="off">';
  htmlRow += '<span id="medida_text_' + count + '" class="medida-description">Medida</span>';
  htmlRow += '<input type="hidden" name="bodega[]" id="bodega_' + count + '" class="form-control buscar_bodega" autocomplete="off">';
  htmlRow += '</td>';

  // Precio
  htmlRow += '<td>';
  htmlRow += '<div class="input-group mb-3">';
  htmlRow += '<input type="number" name="price[]" id="price_' + count + '" class="form-control" step="0.01" placeholder="Precio" readonly autocomplete="off">';
  htmlRow += '<div id="suggestions_producto_' + count + '" class="suggestions"></div>';
  htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><i class="aplicar_precio fas fa-plus fa-lg"></i></a></div>';
  htmlRow += '</div>';
  htmlRow += '<input type="hidden" name="pprecio_mayoreo[]" id="precio_mayoreo_' + count + '" class="form-control inputfield-details" readonly autocomplete="off">';
  htmlRow += '<input type="hidden" name="precio_real[]" id="precio_real_' + count + '" class="form-control inputfield-details" readonly autocomplete="off">';
  htmlRow += '</td>';

  // Descuento
  htmlRow += '<td>';
  htmlRow += '<div class="input-group mb-3">';
  htmlRow += '<input type="number" name="discount[]" id="discount_' + count + '" class="form-control" step="0.01" placeholder="Descuento" readonly autocomplete="off">';
  htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><i class="aplicar_descuento fas fa-plus fa-lg"></i></a></div>';
  htmlRow += '</div>';
  htmlRow += '</td>';

  // Total línea (incluye impuestos)
  htmlRow += '<td><input type="number" name="total[]" id="total_' + count + '" placeholder="Total" class="form-control total inputfield-details" readonly autocomplete="off" step="0.01"></td>';

  htmlRow += '</tr>';
  return htmlRow;
}

function limpiarTablaFactura() {
    $("#invoice-form #invoiceItem > tbody").empty();
    let count = 0;
    $('#invoiceItem').append(generarFilaFactura(count));
    $("#invoice-form .tableFixHead").scrollTop($(document).height());
    $("#bar-code-id_" + count).focus();
}

function limpiarTablaFacturaDetalles(count) {
    $("#invoice-form #invoiceItem > tbody").empty();
    $('#invoiceItem').append(generarFilaFactura(count));
    $("#invoice-form .tableFixHead").scrollTop($(document).height());
    $("#bar-code-id_" + count).focus();
}

function addRowFacturas() {
    let count = parseInt($("#bill_row").val()) + 1;
    $('#invoiceItem').append(generarFilaFactura(count));
    $("#bill_row").val(count);
    $("#bar-code-id_" + count).focus();
}

// Función para actualizar la descripción y medida cuando se carga un producto
// (Esta función debe ser llamada desde donde se carga la información del producto)
function actualizarTextoProducto(index, nombreProducto, medidaProducto) {
    // Actualizar inputs ocultos
    $("#productName_" + index).val(nombreProducto);
    $("#medida_" + index).val(medidaProducto);
    
    // Actualizar textos visibles
    $("#productName_text_" + index).text(nombreProducto || "Descripción del Producto");
    $("#medida_text_" + index).text(medidaProducto || "Medida");
}

$(() => {
    $("#invoice-form #invoiceItem #bar-code-id_0").focus();

    $(document).on('click', '#checkAll', function() {
        $(".itemRow").attr("checked", this.checked);
    });
    $(document).on('click', '.itemRow', function() {
        if ($('.itemRow:checked').length == $('.itemRow').length) {
            $('#checkAll').attr('checked', true);
        } else {
            $('#checkAll').attr('checked', false);
        }
    });
    var count = $(".itemRow").length;
    $(document).on('click', '#addRows', function() {
        if ($("#invoice-form #cliente").val() != "") {
            addRowFacturas();
        } else {
            showNotify('error', 'Error', 'Lo sentimos no puede agregar más filas, debe seleccionar un usuario antes de poder continuar');
        }
    });
    $(document).on('click', '#removeRows', function() {
        if ($('.itemRow ').is(':checked')) {
            $(".itemRow:checked").each(function() {
                $(this).closest('tr').remove();
                count--;
            });
            $('#checkAll').attr('checked', false);
            calculateTotalFacturas();
        } else {
            showNotify('error', 'Error', 'Lo sentimos debe seleccionar un fila antes de intentar eliminarla');
        }
    });
    $(document).on('blur', "[id^=quantity_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=quantity_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "[id^=price_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=price_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "[id^=discount_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=discount_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "#taxRate", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "#amountPaid", function() {
        var amountPaid = $(this).val();
        var totalAftertax = $('#totalAftertax').val();
        if (amountPaid && totalAftertax) {
            totalAftertax = totalAftertax - amountPaid;
            $('#amountDue').val(totalAftertax);
        } else {
            $('#amountDue').val(totalAftertax);
        }
    });
    $(document).on('click', '.deleteInvoice', function() {
        var id = $(this).attr("id");
        if (confirm("Are you sure you want to remove this?")) {
            $.ajax({
                url: "action.php",
                method: "POST",
                dataType: "json",
                data: {
                    id: id,
                    action: 'delete_invoice'
                },
                success: function(response) {
                    if (response.status == 1) {
                        $('#' + id).closest("tr").remove();
                    }
                }
            });
        } else {
            return false;
        }
    });
});

// =====================
// Utilidades
// =====================
/* ===================================================
   Recalcular ISV por línea
   - Lee: quantity_{row}, price_{row}, discount_{row}
   - Lee flags: isv1_flag_{row}, isv2_flag_{row}
   - Respeta: isv_{row} (1 = grava, 0 = exento)
   - Escribe: valor_isv_{row} (id=1), valor_isv1_{row} (id=2)
   =================================================== */
  async function recalcISVForRow(row){
    const grava   = parseInt($('#invoice-form #invoiceItem #isv_' + row).val() || '0', 10);
    const qty     = parseFloat($('#invoice-form #invoiceItem #quantity_' + row).val()  || '1') || 1;
    const price   = parseFloat($('#invoice-form #invoiceItem #price_'    + row).val()  || '0') || 0;
    const disc    = parseFloat($('#invoice-form #invoiceItem #discount_' + row).val()  || '0') || 0;

    // Base neta (no negativa)
    let base = (price * qty) - disc;
    if (base < 0) base = 0;

    // Flags de ISV (1/0)
    const flag1 = parseInt($('#isv1_flag_' + row).val() || '0', 10) === 1;
    const flag2 = parseInt($('#isv2_flag_' + row).val() || '0', 10) === 1;

    let val1 = 0, val2 = 0;

    if (grava === 1 && flag1){
        const p1 = await fetchISVPercent(1);           // ej 15
        val1 = base * ( (parseFloat(p1)||0) / 100.0 );  // a decimal
    }
    if (grava === 1 && flag2){
        const p2 = await fetchISVPercent(2);           // ej 18
        val2 = base * ( (parseFloat(p2)||0) / 100.0 );
    }

    if ($('#valor_isv_'  + row).length)  $('#valor_isv_'  + row).val(val1.toFixed(2));
    if ($('#valor_isv1_' + row).length)  $('#valor_isv1_' + row).val(val2.toFixed(2));
}

// =====================
// Recalculador general
// =====================
function calculateTotalFacturas(){
  var totalAmount   = 0; // suma de (precio * cantidad) SIN ISV y SIN descuento
  var totalGeneral  = 0; // alias del anterior en tu código
  var totalDiscount = 0;
  var totalISV1     = 0; // suma de valor_isv_*
  var totalISV2     = 0; // suma de valor_isv1_*

  $("[id^='price_']").each(function() {
      var id = $(this).attr('id').replace("price_", '');
      var price       = parseFloat($('#price_' + id).val()) || 0;
      var discount    = parseFloat($('#discount_' + id).val()) || 0;
      var quantity    = parseFloat($('#quantity_' + id).val()) || 1;
      var isv1_line   = parseFloat($('#valor_isv_'  + id).val()) || 0;
      var isv2_line   = parseFloat($('#valor_isv1_' + id).val()) || 0;

      var lineBase = price * quantity; // sin ISV, sin descuento
      $('#total_' + id).val(customRound(lineBase - discount).toFixed(4));

      totalAmount   += lineBase;
      totalGeneral  += lineBase;
      totalDiscount += discount;
      totalISV1     += isv1_line;
      totalISV2     += isv2_line;
  });

  // Subtotales (sin formato)
  $('#subTotal').val(parseFloat(totalAmount).toFixed(2));
  $('#taxDescuento').val(parseFloat(totalDiscount).toFixed(2));
  $('#taxAmount').val(parseFloat(totalISV1).toFixed(2));      // ISV id=1 (15%)
  if ($('#taxAmount18').length) {
      $('#taxAmount18').val(parseFloat(totalISV2).toFixed(2)); // ISV id=2 (18%)
  }

  // Footer con formato (coma miles, punto decimal)
  var fmt = function(n){ return parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","); };

  $('#subTotalFooter').val(fmt(totalAmount));
  $('#taxDescuentoFooter').val(fmt(totalDiscount));
  $('#taxAmountFooter').val(fmt(totalISV1));         // ISV15
  if ($('#taxAmountFooter18').length) {
      $('#taxAmountFooter18').val(fmt(totalISV2));   // ISV18
  }

  // Total general = Subtotal + ISV1 + ISV2 - descuentos
  var total = (totalAmount + totalISV1 + totalISV2) - totalDiscount;

  $('#subTotalImporte').val(fmt(totalGeneral));
  $('#totalAftertax').val(fmt(total));
  $('#totalAftertaxFooter').val(fmt(total));

  // Conversión si aplica
  var totalAftertax = parseFloat(total) || 0;
  var cambioDolar   = parseFloat($('#cambioBill').val()) || 1;
  $('#totalHNLBill').val(customRound(parseFloat(totalAftertax * cambioDolar).toFixed(2)));
}

function customRound(number) {
    var truncated = Math.floor(number * 100) / 100;
    var secondDecimal = Math.floor((number * 100) % 10);
    if (secondDecimal >= 5) {
        return parseFloat((truncated + 0.01).toFixed(2));
    } else {
        return parseFloat(truncated.toFixed(2));
    }
}

function cleanFooterValueBill() {
    $('#subTotalFooter').val("");
    $('#taxDescuentoFooter').val("");
    $('#taxAmountFooter').val("");
    if ($('#taxAmountFooter18').length) $('#taxAmountFooter18').val("");
    $('#totalAftertaxFooter').val("");
}
//FIN INVOICE BILL

/*INICIO BARCODE*/
//INICIO FACTURAS
function redondearEnteroCercano(numero) {
    var entero = Math.floor(numero); // Obtenemos la parte entera del número
    var decimal = numero - entero; // Obtenemos la parte decimal

    if (decimal < 0.5) {
        return entero; // Redondeamos hacia abajo si la parte decimal es menor que 0.5
    } else {
        return entero + 1; // Redondeamos hacia arriba si la parte decimal es mayor o igual a 0.5
    }
}

$(() => {
  $("#invoice-form #invoiceItem").on('keypress', '.product-bar-code', function (event) {
    const row_index = $(this).closest("tr").index();
    const code = event.which || event.keyCode;

    if (code === 10 || code === 13) { // Enter
      event.preventDefault();
      manejarPresionEnter(row_index); // ya NO pasamos event dentro
      return;
    }
    if (code === 43 || code === 45) { // + / -
      event.preventDefault();
      manejarPresionTeclaMasMenos(code, row_index);
    }
  });
});

async function manejarPresionEnter(row_index) {
  const barCodeInput = $("#invoice-form #invoiceItem #bar-code-id_" + row_index);
  let barcode = barCodeInput.val().trim();
  if (barcode === "") return;

  const url = '<?php echo SERVERURL; ?>core/getProductoBarCode.php';

  // Permitir "cantidad*barcode"
  let cantidad = 1;
  let barcodeValue = barcode;
  if (barcode.includes('*')) {
    const parts = barcode.split('*');
    cantidad = parseFloat(parts[0]) || 1;
    barcodeValue = parts[1] || '';
  }

  $.ajax({
    type: 'POST',
    url: url,
    data: 'barcode=' + encodeURIComponent(barcodeValue),
    async: false,
    success: async function (registro) {
      getTotalFacturasDisponibles();

      let producto = {};
      try { producto = JSON.parse(registro); } catch (e) { producto = {}; }

      // Producto válido
      if (!producto || !producto.nombre) {
        showNotify('error', 'Error', 'Producto no encontrado, por favor corregir');
        barCodeInput.val("");
        return;
      }

      // Si no es servicio: validar bodega/saldo
      if (String(producto.tipo_producto_id) !== "2") {
        if (producto.almacen_id === null || producto.almacen_id === "") {
          swal({
            title: "Error",
            content: {
              element: "span",
              attributes: {
                innerHTML:
                  "Lo sentimos, el producto no está asignado a una bodega. Por favor, " +
                  "<a href='<?php echo SERVERURL; ?>inventario/' " +
                  "style='color: blue; text-decoration: none;' " +
                  "onmouseover='this.style.color=`purple`' onmouseout='this.style.color=`blue`' " +
                  "onmousedown='this.style.color=`purple`' target='_blank'>ingrese el movimiento</a> " +
                  "de este registro antes de continuar."
              }
            },
            icon: "error",
            buttons: { confirm: { text: "Aceptar" } },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
          });
          return;
        }

        const facturar_cero = facturarEnCeroAlmacen(producto.almacen_id);
        if ((parseFloat(producto.saldo || 0) <= 0) && (facturar_cero == 'false' || facturar_cero === false)) {
          showNotify('error', 'Error', 'No se puede facturar este producto inventario en cero');
          return;
        }
      }

      // Cantidad si venía en "cantidad*barcode"
      $("#invoice-form #invoiceItem #quantity_" + row_index).val(cantidad);
      $("#invoice-form #invoiceItem #bar-code-id_" + row_index).val(barcodeValue);

      // Setear datos base
      $("#invoice-form #invoiceItem #productName_" + row_index).val(producto.nombre);
      $("#invoice-form #invoiceItem #productName_text_" + row_index).html(producto.nombre);

      $("#invoice-form #invoiceItem #price_" + row_index).val(producto.precio_venta);
      $("#invoice-form #invoiceItem #precio_real_" + row_index).val(producto.precio_venta);
      $("#invoice-form #invoiceItem #productos_id_" + row_index).val(producto.productos_id);

      // ISV: grava 1/0
      $("#invoice-form #invoiceItem #isv_" + row_index).val(producto.impuesto_venta);

      // FLAGS por línea (cuál ISV aplica)
      if ($('#isv1_flag_' + row_index).length) $('#isv1_flag_' + row_index).val(parseInt(producto.isv1 || 0, 10) === 1 ? 1 : 0);
      if ($('#isv2_flag_' + row_index).length) $('#isv2_flag_' + row_index).val(parseInt(producto.isv2 || 0, 10) === 1 ? 1 : 0);

      // % por línea (estos son PORCENTAJES que trae el PHP; ej 15.00 / 18.00)
      // Debes tener inputs hidden por fila: porc_isv_# y porc_isv1_#
      if ($('#porc_isv_' + row_index).length)  $('#porc_isv_' + row_index).val(Number(producto.valor_isv  || 0).toFixed(2));
      if ($('#porc_isv1_' + row_index).length) $('#porc_isv1_' + row_index).val(Number(producto.valor_isv1 || 0).toFixed(2));

      // Montos de ISV (monto $). Los reiniciamos; recalc los calculará.
      if ($('#valor_isv_' + row_index).length)  $('#valor_isv_' + row_index).val('0.00');
      if ($('#valor_isv1_' + row_index).length) $('#valor_isv1_' + row_index).val('0.00');

      // Mayoreo (si la cantidad del código supera el umbral)
      (function actualizarPrecioPorMayoreo() {
        const cm = parseFloat(producto.cantidad_mayoreo || 0);
        const pm = parseFloat(producto.precio_mayoreo || 0);
        const q  = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 0;
        const pr = parseFloat($('#invoice-form #invoiceItem #precio_real_' + row_index).val()) || 0;
        const nuevo = (cm > 0 && pm > 0 && q >= cm) ? pm : pr;
        $('#invoice-form #invoiceItem #price_' + row_index).val(nuevo);
      })();

      // Otros campos
      $("#invoice-form #invoiceItem #cantidad_mayoreo_" + row_index).val(producto.cantidad_mayoreo);
      $("#invoice-form #invoiceItem #precio_mayoreo_"   + row_index).val(producto.precio_mayoreo);
      $('#invoice-form #invoiceItem #bodega_'           + row_index).val(producto.almacen_id);
      $('#invoice-form #invoiceItem #medida_'           + row_index).val(producto.medida || 'Und');

      // Calcula montos ISV por línea con lo que acabamos de setear
      await recalcISVForRow(row_index);

      // UI
      addRowFacturas();
      const icon_search = (row_index > 0) ? (row_index - 1) : 0;
      $("#invoice-form #invoiceItem #icon-search-bar_" + row_index).hide();
      $("#invoice-form #invoiceItem #icon-search-bar_" + icon_search).hide();

      calculateTotalFacturas();
    }
  });
}

function manejarPresionTeclaMasMenos(codigoTecla, row_index) {
  // 1) Actualizar cantidad
  var $cant = $("#invoice-form #invoiceItem #quantity_" + row_index);
  var cant  = parseFloat($cant.val()) || 1;
  cant = (codigoTecla === 43) ? (cant + 1) : Math.max(cant - 1, 1);
  $cant.val(cant);

  // 2) Actualizar precio por regla de mayoreo (usa tu función existente)
  actualizarPrecioPorMayoreo(row_index);

  // 3) Recalcular ISV de la línea y totales
  (async () => {
    await recalcularISVLinea(row_index); // alias que llama a recalcISVForRow(row_index)
    calculateTotalFacturas();
  })();
}

$(() => {
    $('#view_bill').on("keydown", function(e) {
        if (e.which === 118) { //TECLA F7 (COBRAR)
            ProcesarFactura();
            e.preventDefault();
        }

        if (e.which === 119) { //TECLA F8 (CLIENTES)
            searchCustomersBill();
            e.preventDefault();
        }

        if (e.which === 120) { //TECLA F9 (Colaboradores)
            serchColaboradoresBill();
            e.preventDefault();
        }

        if (e.which === 121) { //TECLA F10 (APERTURAR CAJA)
            e.preventDefault();
            if (getConsultarAperturaCaja() == 2) {
                formAperturaBill();
            } else {
                showNotify('warning', 'Caja abierta', 'La caja se encuentra abierta');
            }
        }

        if (e.which === 122) { //TECLA F11 (CERRAR CAJA)			
            e.preventDefault();
            if (getConsultarAperturaCaja() != 2) {
                formCierreBill()
            } else {
                showNotify('warning', 'Caja cerrada', 'La caja se encuentra cerrada');
            }
        }
    });
});

//INICIO ADD TASA DE CAMBIO
$(() => {
    $("#modalTasaCambio").on('shown.bs.modal', function() {
        $(this).find('#formTasaCambio #tasa_compra').focus();
    });
});

$("#invoice-form #addCambio").on("click", function(e) {
    e.preventDefault();
    $('#modalTasaCambio #pro_tasaCambio').val("Registro");


    $('#formTasaCambio').attr({
        'data-form': 'save'
    });
    $('#formTasaCambio').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addTasaCambioAjax.php'
    });

    $('#modalTasaCambio').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$("#formTasaCambio").on("submit", function(e) {
    var compra = $('#formTasaCambio #tasa_compra').val();
    var totalUSD = $('#invoice-form #totalAftertax').val();
    var totalHND = 0;

    if (compra > 0) {
        totalHND = totalUSD * compra;
    }

    $('#invoice-form #totalHNLBill').val(parseFloat(totalHND).toFixed(2));

    e.preventDefault();
});
//FIN ADD TASA DE CAMBIO

function modalAyuda() {
    $('#modalAyuda').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

// Abrir modal con F1 en cualquier momento
$(document).on('keydown', function (e) {
    if (e.key === 'F1') {
    e.preventDefault(); // Evita la ayuda del navegador
    $('#modalAyuda').modal('show');
    }
});

//INICIO AYUDA
$("#invoice-form #help_factura").on("click", function(e) {
    modalAyuda();
    e.preventDefault();
});

// ===== INICIO DESCUENTO PRODUCTO EN FACTURACION =====
$(() => {
  // % => monto
  $("#formDescuentoFacturacion #porcentaje_descuento_fact").on("keyup change", function () {
    const precio = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val()) || 0;
    const pct    = parseFloat($('#formDescuentoFacturacion #porcentaje_descuento_fact').val());
    if (!isNaN(pct)) {
      const monto = precio * (pct / 100);
      $('#formDescuentoFacturacion #descuento_fact').val(monto.toFixed(2));
    } else {
      $('#formDescuentoFacturacion #descuento_fact').val('0.00');
    }
  });

  // monto => %
  $("#formDescuentoFacturacion #descuento_fact").on("keyup change", function () {
    const precio   = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val()) || 0;
    const monto    = parseFloat($('#formDescuentoFacturacion #descuento_fact').val());
    if (!isNaN(monto) && precio > 0) {
      const pct = (monto / precio) * 100;
      $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(pct.toFixed(2));
    } else {
      $('#formDescuentoFacturacion #porcentaje_descuento_fact').val('0.00');
    }
  });
});

// DESCUENTO – guarda, recalcula ISV de la línea y actualiza totales
$("#reg_DescuentoFacturacion").on("click", function (e) {
  e.preventDefault();

  const row_index = $('#formDescuentoFacturacion #row_index').val();
  const descuento = parseFloat($('#formDescuentoFacturacion #descuento_fact').val()) || 0;

  const precio   = parseFloat($("#invoice-form #invoiceItem #price_" + row_index).val())    || 0;
  const cantidad = parseFloat($("#invoice-form #invoiceItem #quantity_" + row_index).val()) || 1;

  // Validación: descuento no puede exceder el valor de la línea
  const lineBase = precio * cantidad;
  if (descuento > lineBase) {
    showNotify('warning', 'Advertencia', 'El valor del descuento es mayor al precio total del artículo, por favor corregir');
    return;
  }

  // Asigna descuento a la fila
  $("#invoice-form #invoiceItem #discount_" + row_index).val(descuento.toFixed(2));

  // Recalcula ISV con base = (precio*cantidad) - descuento y luego totales
  (async () => {
    await recalcularISVLinea(row_index); // alias que llama a recalcISVForRow(row_index)
    calculateTotalFacturas();
    $('#modalDescuentoFacturacion').modal('hide');
  })();
});
// ===== FIN DESCUENTO PRODUCTO EN FACTURACION =====

// ===== INICIO CAMBIAR PRECIO A PRODUCTO EN FACTURACION =====
$(() => {
  $('#invoice-form #invoiceItem').on("keydown", '.product-bar-code', function(e) {
    if (e.which === 112) { // F1
      modalAyuda();
      e.preventDefault();
    }

    if (e.which === 113) { // F2
      alert('Me haz presionado');
      e.preventDefault();
    }

    if (e.which === 114) { // F3 -> buscar producto
      listar_productos_factura_buscar();
      var row_index = $(this).closest("tr").index();
      var col_index = $(this).closest("td").index();

      $('#formulario_busqueda_productos_facturacion #row').val(row_index);
      $('#formulario_busqueda_productos_facturacion #col').val(col_index);
      $('#modal_buscar_productos_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
      });
      e.preventDefault();
    }

    if (e.which === 115) { // F4 -> descuento por producto
      var row_index = $(this).closest("tr").index();
      var col_index = $(this).closest("td").index();

      $('#formDescuentoFacturacion #row_index').val(row_index);
      $('#formDescuentoFacturacion #col_index').val(col_index);

      if ($("#invoice-form #invoiceItem #productos_id_" + row_index).val() != "") {
        $('#formDescuentoFacturacion')[0].reset();
        var productos_id = $("#invoice-form #invoiceItem #productos_id_" + row_index).val();
        var producto     = $("#invoice-form #invoiceItem #productName_" + row_index).val();
        var precio       = $("#invoice-form #invoiceItem #price_" + row_index).val();

        $('#formDescuentoFacturacion #descuento_productos_id').val(productos_id);
        $('#formDescuentoFacturacion #producto_descuento_fact').val(producto);
        $('#formDescuentoFacturacion #precio_descuento_fact').val(precio);

        $('#formDescuentoFacturacion #pro_descuento_fact').val("Registrar");

        getPermisosTipoUsuarioAccesosForms(getPrivilegioTipoUsuario());

        $('#modalDescuentoFacturacion').modal({
          show: true,
          keyboard: false,
          backdrop: 'static'
        });
      }
      e.preventDefault();
    }

    if (e.which === 117) { // F6 -> modificar precio
      var row_index = $(this).closest("tr").index();
      abrirEditarPrecioFacturaEscritorio(row_index);
      e.preventDefault();
    }
  });
});

// ======================================================
// EDITAR PRECIO - MODAL NUEVO COMPARTIDO CON FACTURA MÓVIL
// Modal requerido: #editarPrecioModal
// Campos requeridos:
//   #producto-precio-index
//   #nuevo-precio-producto
//   #precio-total-preview o los spans:
//   #preview-subtotal, #preview-isv15, #preview-isv18, #preview-total
//   #guardar-precio
// ======================================================
function asegurarPreviewPrecioFacturaEscritorio() {
  var $preview = $('#precio-total-preview');

  if ($preview.length && $preview.is('input')) {
    $preview.replaceWith(
      '<div id="precio-total-preview" class="alert alert-light border mb-0" style="line-height:1.6;">' +
        '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
        '<div class="d-flex justify-content-between"><span>ISV 15%</span><strong id="preview-isv15">L. 0.00</strong></div>' +
        '<div class="d-flex justify-content-between"><span>ISV 18%</span><strong id="preview-isv18">L. 0.00</strong></div>' +
        '<hr class="my-2">' +
        '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>' +
      '</div>'
    );
  } else if (!$preview.length) {
    return;
  } else if (!$('#preview-subtotal').length) {
    $preview.html(
      '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
      '<div class="d-flex justify-content-between"><span>ISV 15%</span><strong id="preview-isv15">L. 0.00</strong></div>' +
      '<div class="d-flex justify-content-between"><span>ISV 18%</span><strong id="preview-isv18">L. 0.00</strong></div>' +
      '<hr class="my-2">' +
      '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>'
    );
  }
}

function formatMoneyPreviewFacturaEscritorio(value) {
  value = parseFloat(value || 0);
  return value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function setPrecioPreviewFacturaEscritorio(subtotal, isv15, isv18, total) {
  asegurarPreviewPrecioFacturaEscritorio();

  subtotal = parseFloat(subtotal || 0);
  isv15 = parseFloat(isv15 || 0);
  isv18 = parseFloat(isv18 || 0);
  total = parseFloat(total || 0);

  if ($('#preview-subtotal').length) $('#preview-subtotal').text('L. ' + formatMoneyPreviewFacturaEscritorio(subtotal));
  if ($('#preview-isv15').length) $('#preview-isv15').text('L. ' + formatMoneyPreviewFacturaEscritorio(isv15));
  if ($('#preview-isv18').length) $('#preview-isv18').text('L. ' + formatMoneyPreviewFacturaEscritorio(isv18));
  if ($('#preview-total').length) $('#preview-total').text('L. ' + formatMoneyPreviewFacturaEscritorio(total));

  // Compatibilidad con el modal viejo donde la vista previa era un input.
  if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
    $('#precio-total-preview').val(
      'Subtotal: L. ' + formatMoneyPreviewFacturaEscritorio(subtotal) +
      ' | ISV 15%: L. ' + formatMoneyPreviewFacturaEscritorio(isv15) +
      ' | ISV 18%: L. ' + formatMoneyPreviewFacturaEscritorio(isv18) +
      ' | Total: L. ' + formatMoneyPreviewFacturaEscritorio(total)
    );
  }
}

function limpiarPrecioPreviewFacturaEscritorio() {
  asegurarPreviewPrecioFacturaEscritorio();

  if ($('#preview-subtotal').length) $('#preview-subtotal').text('L. 0.00');
  if ($('#preview-isv15').length) $('#preview-isv15').text('L. 0.00');
  if ($('#preview-isv18').length) $('#preview-isv18').text('L. 0.00');
  if ($('#preview-total').length) $('#preview-total').text('L. 0.00');

  if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
    $('#precio-total-preview').val('');
  }
}

function obtenerPorcentajeISVFacturaEscritorio(isvId) {
  var porcentaje = 0;

  if (typeof fetchISVPercentSync === 'function') {
    porcentaje = parseFloat(fetchISVPercentSync(isvId)) || 0;
  }

  if (porcentaje <= 0 && typeof getPorcentajeISV === 'function' && isvId === 1) {
    porcentaje = parseFloat(getPorcentajeISV('Facturas')) || 0;
  }

  // Fallback seguro para no romper vista previa si la consulta global no existe.
  if (porcentaje <= 0) {
    if (parseInt(isvId, 10) === 1) porcentaje = 15;
    if (parseInt(isvId, 10) === 2) porcentaje = 18;
  }

  return porcentaje;
}

function abrirEditarPrecioFacturaEscritorio(row_index) {
  row_index = parseInt(row_index, 10);

  if (Number.isNaN(row_index) || row_index < 0) {
    showNotify('error', 'Error', 'No se pudo identificar la línea del producto');
    return;
  }

  var productos_id = $('#invoice-form #invoiceItem #productos_id_' + row_index).val();
  var producto = $('#invoice-form #invoiceItem #productName_' + row_index).val();
  var precioActual = parseFloat($('#invoice-form #invoiceItem #price_' + row_index).val()) || 0;

  if (!productos_id || productos_id === '0') {
    showNotify('error', 'Error', 'Debe seleccionar un producto antes de modificar el precio');
    return;
  }

  $('#producto-precio-index').val(row_index);
  $('#nuevo-precio-producto').val(precioActual > 0 ? precioActual.toFixed(2) : '');

  if ($('#editarPrecioModalLabel').length) {
    $('#editarPrecioModalLabel').html('<i class="fas fa-dollar-sign"></i> Editar Precio');
  }

  actualizarVistaNuevoPrecioFacturaEscritorio();

  $('#editarPrecioModal').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function actualizarVistaNuevoPrecioFacturaEscritorio() {
  var row_index = parseInt($('#producto-precio-index').val(), 10);
  var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

  if (Number.isNaN(row_index) || row_index < 0 || nuevoPrecio <= 0) {
    limpiarPrecioPreviewFacturaEscritorio();
    return;
  }

  var cantidad = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 1;
  var descuento = parseFloat($('#invoice-form #invoiceItem #discount_' + row_index).val()) || 0;
  var grava = parseInt($('#invoice-form #invoiceItem #isv_' + row_index).val() || '0', 10);
  var flag1 = parseInt($('#isv1_flag_' + row_index).val() || '0', 10) === 1;
  var flag2 = parseInt($('#isv2_flag_' + row_index).val() || '0', 10) === 1;

  var subtotal = (nuevoPrecio * cantidad) - descuento;
  if (subtotal < 0) subtotal = 0;

  var isv15 = 0;
  var isv18 = 0;

  if (grava === 1 && flag1) {
    var p1 = obtenerPorcentajeISVFacturaEscritorio(1);
    isv15 = subtotal * (p1 / 100);
  }

  if (grava === 1 && flag2) {
    var p2 = obtenerPorcentajeISVFacturaEscritorio(2);
    isv18 = subtotal * (p2 / 100);
  }

  var total = subtotal + isv15 + isv18;

  setPrecioPreviewFacturaEscritorio(subtotal, isv15, isv18, total);
}

function guardarPrecioFacturaEscritorio() {
  var row_index = parseInt($('#producto-precio-index').val(), 10);
  var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

  if (Number.isNaN(row_index) || row_index < 0) {
    showNotify('error', 'Error', 'No se pudo identificar la línea del producto');
    return;
  }

  if (nuevoPrecio <= 0) {
    showNotify('warning', 'Advertencia', 'El precio debe ser mayor a cero');
    $('#nuevo-precio-producto').focus();
    return;
  }

  var cantidad = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 1;
  var descuentoActual = parseFloat($('#invoice-form #invoiceItem #discount_' + row_index).val()) || 0;
  var totalLinea = nuevoPrecio * cantidad;

  if (descuentoActual > totalLinea) {
    showNotify('warning', 'Advertencia', 'El descuento actual es mayor al nuevo precio total. Ajuste primero el descuento.');
    return;
  }

  $('#invoice-form #invoiceItem #price_' + row_index).val(nuevoPrecio.toFixed(2));
  $('#invoice-form #invoiceItem #precio_real_' + row_index).val(nuevoPrecio.toFixed(2));

  (async () => {
    await recalcularISVLinea(row_index);
    calculateTotalFacturas();
    $('#editarPrecioModal').modal('hide');
    showNotify('success', 'Éxito', 'Precio actualizado correctamente');
  })();
}

$(document).off('input.editarPrecioFacturaEscritorio keyup.editarPrecioFacturaEscritorio change.editarPrecioFacturaEscritorio', '#nuevo-precio-producto');
$(document).on('input.editarPrecioFacturaEscritorio keyup.editarPrecioFacturaEscritorio change.editarPrecioFacturaEscritorio', '#nuevo-precio-producto', function () {
  actualizarVistaNuevoPrecioFacturaEscritorio();
});

$(document).off('click.guardarPrecioFacturaEscritorio', '#guardar-precio');
$(document).on('click.guardarPrecioFacturaEscritorio', '#guardar-precio', function (e) {
  e.preventDefault();
  guardarPrecioFacturaEscritorio();
});

$(document).off('keydown.guardarPrecioFacturaEscritorio', '#editarPrecioModal #nuevo-precio-producto');
$(document).on('keydown.guardarPrecioFacturaEscritorio', '#editarPrecioModal #nuevo-precio-producto', function (e) {
  if (e.which === 13) {
    e.preventDefault();
    guardarPrecioFacturaEscritorio();
  }
});

$('#editarPrecioModal').off('shown.bs.modal.editarPrecioFacturaEscritorio');
$('#editarPrecioModal').on('shown.bs.modal.editarPrecioFacturaEscritorio', function () {
  $('#nuevo-precio-producto').trigger('focus').select();
});

$('#editarPrecioModal').off('hidden.bs.modal.editarPrecioFacturaEscritorio');
$('#editarPrecioModal').on('hidden.bs.modal.editarPrecioFacturaEscritorio', function () {
  if ($('#editar-precio-form').length && $('#editar-precio-form')[0]) {
    $('#editar-precio-form')[0].reset();
  }

  limpiarPrecioPreviewFacturaEscritorio();
  $('#producto-precio-index').val('');
});
// ===== FIN CAMBIAR PRECIO A PRODUCTO EN FACTURACION =====
//FIN FACTURAS

function facturarEnCeroAlmacen(almacen_id) {
    var url = '<?php echo SERVERURL; ?>core/getFacturarCeroAlmacen.php';
    var estado = false;

    $.ajax({
        type: 'POST',
        url: url,
        data: {almacen_id: almacen_id},
        async: false,
        dataType: 'json', // Asegurar que esperamos JSON
        success: function(response) {
            if(response.success) {
                estado = response.facturar_cero;
            } else {
                console.error("Error al verificar facturación en cero");
            }
        },
        error: function(xhr) {
            console.error("Error de conexión al verificar facturación en cero");
        }
    });
    return estado;
}

// ======================================================
// INICIO ESTADOS PROFORMA + REBAJAR INVENTARIO PROFORMA
// ======================================================
//
// IMPORTANTE:
// En tu HTML el checkbox de inventario es:
// id="proforma_bajar_inventario"
// name="proforma_bajar_inventario"
//
// Este bloque consulta:
// core/facturas/facturaMovil.php?getConfigFacturaMovil=1
//
// Respuesta esperada:
// {
//   success: true,
//   proforma_activa: 1,
//   proforma_rebajar_inventario: 1
// }
// ======================================================

function normalizarActivoFactura(valor) {
    return valor === true || valor === 1 || valor === '1';
}

function getCheckBajarInventarioProforma() {
    // Principal: este es el ID que tienes en tu HTML actual.
    var $check = $('#invoice-form #proforma_bajar_inventario');

    // Compatibilidad por si en otra pantalla lo tienes con el nombre anterior.
    if (!$check.length) {
        $check = $('#invoice-form #bajar_inventario_proforma');
    }

    return $check;
}

function actualizarUIProformaFacturaNormal(activo, rebajarInventarioDefault) {
    activo = normalizarActivoFactura(activo);
    rebajarInventarioDefault = normalizarActivoFactura(rebajarInventarioDefault);

    var $proforma = $('#invoice-form #facturas_proforma');
    var $labelProforma = $('#invoice-form #label_facturas_proforma');

    if (!$proforma.length) {
        console.error('[PROFORMA] No existe el input #facturas_proforma');
        return;
    }

    // Forzado real del checkbox Proforma
    $proforma.prop('checked', activo);

    if ($proforma[0]) {
        $proforma[0].checked = activo;
    }

    $proforma.val(activo ? 1 : 0);

    if (activo) {
        $proforma.attr('checked', 'checked');
    } else {
        $proforma.removeAttr('checked');
    }

    // Label visual Proforma
    $labelProforma
        .removeClass('badge-light badge-secondary badge-info text-white')
        .addClass(activo ? 'badge-info text-white' : 'badge-light')
        .html(activo ? 'Sí' : 'No');

    // Mostrar/Ocultar y aplicar default de rebajar inventario
    mostrarOpcionesInventarioProforma(activo, rebajarInventarioDefault);

    console.log('[PROFORMA UI]', {
        activo: activo,
        rebajarInventarioDefault: rebajarInventarioDefault,
        checked_prop: $proforma.prop('checked'),
        checked_dom: $proforma[0] ? $proforma[0].checked : null,
        value: $proforma.val()
    });
}

function mostrarOpcionesInventarioProforma(mostrar, rebajarInventarioDefault) {
    mostrar = normalizarActivoFactura(mostrar);
    rebajarInventarioDefault = normalizarActivoFactura(rebajarInventarioDefault);

    var $container = $('#invoice-form #proforma_rebajar_inventario_container');
    var $check = getCheckBajarInventarioProforma();
    var $label = $('#invoice-form #label_bajar_inventario_proforma');

    if (!$container.length) {
        console.warn('[PROFORMA] No existe #proforma_rebajar_inventario_container');
        return;
    }

    if (mostrar) {
        $container.attr('style', 'display:flex !important;');

        if ($check.length) {
            $check.prop('checked', rebajarInventarioDefault);

            if ($check[0]) {
                $check[0].checked = rebajarInventarioDefault;
            }

            $check.val(rebajarInventarioDefault ? 1 : 0);

            if (rebajarInventarioDefault) {
                $check.attr('checked', 'checked');
            } else {
                $check.removeAttr('checked');
            }
        }

        $label
            .removeClass('badge-success badge-light text-white')
            .addClass(rebajarInventarioDefault ? 'badge-success text-white' : 'badge-light')
            .html(rebajarInventarioDefault ? 'Sí' : 'No');

    } else {
        $container.attr('style', 'display:none !important;');

        if ($check.length) {
            $check.prop('checked', false);

            if ($check[0]) {
                $check[0].checked = false;
            }

            $check.removeAttr('checked');
            $check.val(0);
        }

        $label
            .removeClass('badge-success text-white')
            .addClass('badge-light')
            .html('No');
    }
}

function actualizarLabelBajarInventarioProforma() {
    var $check = getCheckBajarInventarioProforma();
    var activo = $check.is(':checked');

    $check.val(activo ? 1 : 0);

    $('#invoice-form #label_bajar_inventario_proforma')
        .removeClass('badge-success badge-light text-white')
        .addClass(activo ? 'badge-success text-white' : 'badge-light')
        .html(activo ? 'Sí' : 'No');
}

function consultarConfigProformaFacturaNormal() {
    var url = '<?php echo SERVERURL; ?>core/facturas/facturaMovil.php?getConfigFacturaMovil=1';

    $.ajax({
        type: 'GET',
        url: url,
        dataType: 'json',
        cache: false
    }).done(function (res) {
        console.log('[DEBUG CONFIG PROFORMA]', res);

        var proformaActiva = 0;
        var rebajarInventarioProforma = 0;

        if (res && res.success === true) {
            proformaActiva = parseInt(res.proforma_activa || 0, 10);
            rebajarInventarioProforma = parseInt(res.proforma_rebajar_inventario || 0, 10);
        }

        actualizarUIProformaFacturaNormal(
            proformaActiva === 1,
            rebajarInventarioProforma === 1
        );

    }).fail(function (xhr) {
        console.error('[ERROR CONFIG PROFORMA]', xhr.status, xhr.responseText);

        // Si falla la consulta, por seguridad queda apagado visualmente.
        actualizarUIProformaFacturaNormal(false, false);
    });
}

$(function () {
    // Estado inicial visual
    actualizarUIProformaFacturaNormal(false, false);

    // Consultar configuración real
    consultarConfigProformaFacturaNormal();

    // Cambio manual de Proforma
    $(document).off('change.facturaProforma', '#invoice-form #facturas_proforma');
    $(document).on('change.facturaProforma', '#invoice-form #facturas_proforma', function () {
        var activo = $(this).is(':checked');

        // Cuando el usuario lo activa manualmente, respetamos cómo esté el check de inventario.
        var rebajarActual = getCheckBajarInventarioProforma().is(':checked');

        actualizarUIProformaFacturaNormal(activo, rebajarActual);
    });

    // Cambio manual de rebajar inventario
    $(document).off('change.bajarInventarioProforma', '#invoice-form #proforma_bajar_inventario, #invoice-form #bajar_inventario_proforma');
    $(document).on('change.bajarInventarioProforma', '#invoice-form #proforma_bajar_inventario, #invoice-form #bajar_inventario_proforma', function () {
        actualizarLabelBajarInventarioProforma();
    });
});

// ======================================================
// FIN ESTADOS PROFORMA + REBAJAR INVENTARIO PROFORMA
// ======================================================

$(() => {
    $("#modalDescuentoFacturacion").on('shown.bs.modal', function() {
        $(this).find('#formDescuentoFacturacion #porcentaje_descuento_fact').focus();
    });

    $("#modal_buscar_productos_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_productos_facturacion #buscar').focus();
    });

    $("#modal_buscar_colaboradores_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_colaboradores_facturacion #buscar').focus();
    });

    $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
    });
});

$('#invoice-form #notesBill').keyup(function() {
    var max_chars = 2000;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#invoice-form #charNum_notasBills').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresNotasBills() {
    var max_chars = 2000;
    var chars = $('#invoice-form #notesBill').val().length;
    var diff = max_chars - chars;

    $('#invoice-form #charNum_notasBills').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

$("#invoice-form #cambiar_valor").on("click", function(e) {
    e.preventDefault();
    $('#invoice-form #cambioBillValor').val(1);
    $('#invoice-form #cambioBill').attr("readonly", false);
    $('#invoice-form #cambioBill').focus();
});

$("#invoice-form #cambioBill").on('keypress', function(event) {
    calculateTotalFacturas();
});

$("#invoice-form #cambioBill").on('keyup', function(event) {
    calculateTotalFacturas();
});

$("#invoice-form #cambioBill").on('blur', function(event) {
    calculateTotalFacturas();
});

//INICIO CONVERTIR COTIZACION EN FACTURAS
$(() => {
    $("#modal_buscar_cotizaciones").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_cotizaciones #buscar').focus();
    });
});

$("#invoice-form #addQuotetoBill").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cotizaciones();

    $('#modal_buscar_cotizaciones').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

// Lista de cotizaciones
var listar_busqueda_cotizaciones = function () {
  var $form = $("#formulario_busqueda_cotizaciones");
  var tipo_cotizacion_reporte = $form.find("#tipo_cotizacion_reporte").val() || 1;
  var fechai = $form.find("#fechai").val();
  var fechaf = $form.find("#fechaf").val();

  var table_busqueda_Cotizaciones = $("#DatatableBusquedaCotizaciones").DataTable({
    destroy: true,
    processing: true,        // <-- muestra indicador y no bloquea la UI
    deferRender: true,       // <-- renderiza bajo demanda (rápido)
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL; ?>core/llenarDataTableReporteCotizaciones.php",
      data: { tipo_cotizacion_reporte: tipo_cotizacion_reporte, fechai: fechai, fechaf: fechaf },
      cache: false
    },
    columns: [
      {
        defaultContent:
          "<button type='button' class='table_view load_quote btn btn-primary ocultar' title='Cargar en factura'>" +
          "<span class='fas fa-play fa-lg'></span></button>"
      },
      {
        defaultContent:
          "<button type='button' class='table_reportes print_cotizaciones btn btn-success ocultar' title='Imprimir'>" +
          "<span class='fas fa-file-download fa-lg'></span></button>"
      },
      { data: "fecha" },
      { data: "tipo_documento" },
      { data: "cliente" },
      { data: "numero" },
      {
        data: "subtotal",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "isv",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "descuento",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "total",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      }
    ],
    // si te interesa “lo más reciente primero”
    order: [[5,'desc']], // 5 = columna "numero"
    lengthMenu: lengthMenu,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [{
      text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
      titleAttr: 'Actualizar Cotizaciones',
      className: 'table_actualizar btn btn-secondary ocultar',
      action: function () { listar_busqueda_cotizaciones(); }
    }],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
          typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }
    }
  });

  table_busqueda_Cotizaciones.search('').draw();
  $('#buscar').focus();
};

// Cargar cotización en la factura (delegado)
$(document)
  .off('click', '#DatatableBusquedaCotizaciones button.load_quote')
  .on('click', '#DatatableBusquedaCotizaciones button.load_quote', function (e) {
    e.preventDefault();

    const $btn = $(this);
    const originalHtml = $btn.html();
    $btn.prop('disabled', true).html("<span class='spinner-border spinner-border-sm'></span>");

    const table = $('#DatatableBusquedaCotizaciones').DataTable();
    const row = table.row($(this).closest('tr')).data();
    if (!row) { 
      $btn.prop('disabled', false).html(originalHtml); 
      return; 
    }

    $.ajax({
      type: 'POST',
      url: "<?php echo SERVERURL; ?>core/cotizacion/getCotizacionParaFactura.php",
      data: { cotizacion_id: row.cotizacion_id },
      dataType: 'json'
    })
    .done(function (res) {
      if (!res || res.ok !== true) {
        showNotify('error', 'Error', (res && res.msg) ? res.msg : 'No se pudo cargar la cotización');
        return;
      }

      const h = res.header || {};
      const d = Array.isArray(res.detalle) ? res.detalle : [];
      const today = new Date().toISOString().slice(0,10);

      // Oculta el cuerpo de la tabla de factura para evitar reflow continuo
      const $tbody = $("#invoiceItem tbody");
      $tbody.hide();

      // Limpia la tabla y deja 1 fila base
      if (typeof limpiarTablaFacturaDetalles === 'function') limpiarTablaFacturaDetalles(0);

      /* ===== Header (campos ocultos y visibles del formulario) ===== */
      // Es nueva factura, no conservar el id anterior
      $("#invoice-form #facturas_id").val('');

      // Campos ocultos de ids/nombres
      $("#cliente_id").val(h.clientes_id || '');
      $("#cliente").val(h.cliente_nombre || '');
      $("#colaborador_id").val(h.colaboradores_id || '');
      $("#colaborador").val(h.colaborador_nombre || '');
      $("#notesBill").val(h.notas || '');

      // Fechas
      $("#fecha").val(h.fecha || today);
      $("#fecha_dolar").val(h.fecha_dolar || today);

      // Tipo de factura (1=Contado, 2=Crédito)
      if (typeof setTipoFactura === 'function') {
        setTipoFactura(h.tipo_factura == 1 ? 'contado' : 'credito');
      } else {
        $("#facturas_activo").val(h.tipo_factura == 1 ? 1 : 0); // compatibilidad backend
        // (Opcional) si usas botones toggle manuales, pon/quita .active aquí
        try {
          const isContado = (h.tipo_factura == 1);
          $("#btn-tipo-contado").toggleClass('active', isContado)
                                .toggleClass('btn-primary', isContado)
                                .toggleClass('btn-outline-primary', !isContado);
          $("#btn-tipo-credito").toggleClass('active', !isContado)
                                .toggleClass('btn-primary', !isContado)
                                .toggleClass('btn-outline-primary', isContado);
        } catch(_) {}
      }

      // Cabecera “bonita” arriba del formulario
      $("#rtn-customers-bill").text(h.cliente_rtn ? ("RTN: " + h.cliente_rtn) : "");
      $("#client-customers-bill").text(h.cliente_nombre ? ("Cliente: " + h.cliente_nombre) : "");
      $("#vendedor-customers-bill").text(h.colaborador_nombre ? ("Vendedor: " + h.colaborador_nombre) : "");

      /* ===== Detalle ===== */
      // Pre-crear filas necesarias de una sola vez
      if (typeof addRowFacturas === 'function' && d.length > 1) {
        for (let k = 1; k < d.length; k++) addRowFacturas();
      }

      // Rellenar filas
      for (let i = 0; i < d.length; i++) {
        $("#facturas_detalle_id_"+i).val(''); // viene de cotización, no reusar id de detalle
        $("#bar-code-id_"+i).val(d[i].barCode || "");
        $("#productos_id_"+i).val(d[i].productos_id || "");
        $("#productName_"+i).val(d[i].producto || "");
        $("#productName_text_"+i).text(d[i].producto || "Descripción del Producto");

        $("#quantity_"+i).val(d[i].cantidad || 0);
        $("#price_"+i).val(d[i].precio || 0);
        $("#precio_real_"+i).val(d[i].precio || 0);

        $("#isv_"+i).val(d[i].isv_venta || 0);
        $("#valor_isv_"+i).val(d[i].isv_valor || 0);

        $("#cantidad_mayoreo_"+i).val(d[i].cantidad_mayoreo || 0);
        $("#precio_mayoreo_"+i).val(d[i].precio_mayoreo || 0);

        $("#bodega_"+i).val(d[i].almacen_id || "");
        $("#medida_"+i).val(d[i].medida || "");
        $("#medida_text_"+i).text(d[i].medida || "Medida");

        $("#discount_"+i).val(d[i].descuento || 0);
      }

      // Totales (si prefieres usar los calculados en backend al instante)
      if (res.totales) {
        $("#subTotalImporte").val(res.totales.subtotal || 0);
        $("#taxDescuento").val(res.totales.descuento || 0);
        $("#taxAmount").val(res.totales.isv || 0);
        $("#totalAftertax").val(res.totales.total || 0);
      }

      // Recalcular por si tu lógica local maneja ISV/desc. automáticos
      if (typeof calculateTotalFacturas === 'function') calculateTotalFacturas();

      // Agregar una fila vacía extra y enfocar el código
      if (typeof addRowFacturas === 'function') {
        addRowFacturas();
        const next = parseInt($("#bill_row").val(), 10);
        if (!Number.isNaN(next)) $("#bar-code-id_"+next).focus();
      }

      // Mostrar todo de golpe (un solo reflow)
      $tbody.show();

      // Cerrar modal y avisar
      $('#modal_buscar_cotizaciones').modal('hide');
      showNotify('success', 'Cotización cargada', 'Se cargó la cotización en la factura');
    })
    .fail(function (xhr) {
      console.error('AJAX error', xhr.responseText);
      showNotify('error', 'Error', 'Falló la petición al servidor');
    })
    .always(function(){
      $btn.prop('disabled', false).html(originalHtml);
    });
  });

// Imprimir
$(document)
  .off('click', '#DatatableBusquedaCotizaciones button.print_cotizaciones')
  .on('click', '#DatatableBusquedaCotizaciones button.print_cotizaciones', function (e) {
    e.preventDefault();
    const table = $('#DatatableBusquedaCotizaciones').DataTable();
    const data = table.row($(this).closest('tr')).data();
    if (data && typeof printQuote === 'function') {
      printQuote(data.cotizacion_id);
    }
  });
//FIN CONVERTIR COTIZACION EN FACTURAS

//INICIO CUENTAS POR COBRAR CLIENTES
$(() => {
    $("#modal_buscar_cuentas_cobrar_clientes").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_cuentas_cobrar_clientes #buscar').focus();
    });
});

$("#invoice-form #addPayCustomers").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cuentas_por_cobrar_clientes();

    $('#modal_buscar_cuentas_cobrar_clientes').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var listar_busqueda_cuentas_por_cobrar_clientes = function() {
    var estado = $("#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado").val() === "" ? 1 : $(
        "#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado").val();
    var clientes_id = $("#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes").val();
    var fechai = $("#formulario_busqueda_cuentas_cobrar_clientes #fechai").val();
    var fechaf = $("#formulario_busqueda_cuentas_cobrar_clientes #fechaf").val();

    var table_busqueda_cuentas_por_cobrar_clientes = $("#DatatableBusquedaCuentasCobrarClientes").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableCobrarClientes.php",
            "data": {
                "estado": estado,
                "clientes_id": clientes_id,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "cliente"
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var text = data == 1 ? 'Crédito' : 'Contado';
                        var icon = data == 1 
                            ? '<i class="fas fa-clock mr-1"></i>' 
                            : '<i class="fas fa-check-circle mr-1"></i>';
                        var badgeClass = data == 1 
                            ? 'badge badge-pill badge-warning' 
                            : 'badge badge-pill badge-success';
                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' + 
                            icon + text + '</span>';
                    }
                    return data;
                }
            },             
            {
                "data": "numero"
            },
            {
                data: 'credito',
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                data: "abono",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                data: "saldo",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "defaultContent": "<button class='table_abono btn btn-dark'><span class='fas fa-cash-register fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes abono_factura btn btn-dark ocultar'><span class='fa fa-money-bill-wave fa-solid'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark ocultar'><span class='fas fa-file-download fa-lg'></span></button>"
            }
        ],
        "pageLength": 10,
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "12.11%",
                targets: 0
            },
            {
                width: "21.11%",
                targets: 1
            },
            {
                width: "21.11%",
                targets: 2
            },
            {
                width: "13.11%",
                targets: 3,
                className: "text-center"
            },
            {
                width: "13.11%",
                targets: 4,
                className: "text-center"
            },
            {
                width: "13.11%",
                targets: 5,
                className: "text-center"
            },
            {
                width: "2.11%",
                targets: 6
            },
            {
                width: "2.11%",
                targets: 7
            },
            {
                width: "2.11%",
                targets: 8
            },
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            for (let index = 0; index < aData.length; index++) {
                console.log(aData[i]["credito"]);
            }
            $(row).find('td:eq(2)').css('color', 'red');
            $('#credito-cxc').html('L. ' + aData['total_credito'])
            $('#abono-cxc').html('L. ' + aData['total_abono'])
            $('#total-footer-cxc').html('L. ' + aData['total_pendiente'])

        },
        "buttons": [{
            text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
            titleAttr: 'Actualizar Cuentas por Cobrar Clientes',
            className: 'table_actualizar btn btn-secondary ocultar',
            action: function() {
                listar_busqueda_cuentas_por_cobrar_clientes();
            }
        }],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_busqueda_cuentas_por_cobrar_clientes.search('').draw();
    $('#buscar').focus();

    registrar_abono_cxc_clientes_dataTable("#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes);
    ver_abono_cxc_clientes_dataTable("#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes);
    view_reporte_facturas_dataTable("#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes);
}

var view_reporte_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillReporteVentas(data.facturas_id);
    });
}

var registrar_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_abono");
    $(tbody).on("click", "button.table_abono", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 2 || data.saldo <=
            0) { //no tiene acceso a la accion si la factura ya fue cancelada							
            showNotify('error', 'Error', 'No puede realizar esta accion a las facturas canceladas!');
        } else {
            console.log('cxc', data.facturas_id, 2)
            pago(data.facturas_id, 2, 'facturacion');
        }
    });
}

var ver_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_factura");
    $(tbody).on("click", "button.abono_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxc').modal('show');
        $("#formulario_ver_abono_cxc #abono_facturas_id").val(data.facturas_id);
        listar_AbonosCXC();
    });
}

var ver_abono_cxp_proveedor_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_proveedor");
    $(tbody).on("click", "button.abono_proveedor", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxc').modal('show');
        getAbonosCXP(data.compras_id);
    });
}
//FIN CUENTAS POR COBRAR CLIENTES

function getReporteCotizacion() {
    var url = '<?php echo SERVERURL; ?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').html("");
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').html(data);
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh');
        }
    });
}

$("#formulario_busqueda_cotizaciones #search").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cotizaciones();
});

$("#formulario_busqueda_cuentas_cobrar_clientes #search").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cuentas_por_cobrar_clientes();
});

function convertirCotizacion(cotizacion_id) {
    var url = '<?php echo SERVERURL; ?>core/convertirCotizacion.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'cotizacion_id=' + cotizacion_id,
        success: function(data) {
            var valores = eval(data);

            if (valores[0] == 1) {
                pago(valores[1]);
                listar_busqueda_cotizaciones();
            }
        }
    });
}

//BUSQUEDA DE FACTURAS EN BORRADOR
$("#addDraft").on("click", function(e) {
    e.preventDefault();

    listar_busqueda_bill_draf();

    $('#modal_buscar_bill_draft').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var listar_busqueda_bill_draf = function () {
  var fechai = $("#formulario_bill_draft #fechai").val();
  var fechaf = $("#formulario_bill_draft #fechaf").val();

  var table = $("#DatatableBusquedaBillDraft").DataTable({
    destroy: true,
    processing: true,        // <-- muestra indicador y no bloquea la UI
    deferRender: true,       // <-- renderiza bajo demanda (rápido)
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableFacturasBorrador.php",
      data: { fechai: fechai, fechaf: fechaf }
    },
    columns: [
      { defaultContent: "<button class='table_pay pay btn btn-primary'><span class='fas fa-play fa-lg'></span></button>" },
      { defaultContent: "<button class='table_eliminar eliminar btn btn-danger'><span class='fa fa-trash fa-lg'></span></button>" },
      { data: "fecha" },
      { data: "tipo_documento" },
      { data: "cliente" },
      { data: "numero" },
      {
        data: "subtotal",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "isv",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "descuento",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "total",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      }
    ],
    pageLength: 5,
    lengthMenu: lengthMenu,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [{
      text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
      titleAttr: 'Actualizar Facturas Borrador',
      className: 'table_actualizar btn btn-secondary',
      action: function () { listar_busqueda_bill_draf(); }
    }],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }
    }
  });

  table.search('').draw();
  continue_bill_draft_dataTable("#DatatableBusquedaBillDraft tbody", table);
  delete_bill_draft_dataTable("#DatatableBusquedaBillDraft tbody", table);
};

var continue_bill_draft_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.pay").on("click", "button.pay", function (e) {
    e.preventDefault();
    var row = table.row($(this).parents("tr")).data();
    if (!row) return;

    // set factura seleccionada
    $("#invoice-form #facturas_id").val(row.facturas_id);

    $.ajax({
      type: 'POST',
      url: "<?php echo SERVERURL;?>core/facturas/getDraftBills.php",
      data: { facturas_id: row.facturas_id },
      dataType: 'json', // <<--- IMPORTANTE: así recibimos un objeto y no hay que parsear nada
      success: function (r) {
        if (!r || r.type !== 'success') {
          return showNotify('error', 'Error', (r && r.message) ? r.message : 'No se pudo cargar la factura.');
        }

        // ====== ENCABEZADO ======
        var h = r.header || {};

        // Cliente (inputs ocultos + cabecera visible)
        $("#cliente_id").val(h.clientes_id || "");
        $("#cliente").val(h.cliente_nombre || "");
        $("#rtn-customers-bill").text(h.cliente_rtn ? ("RTN: " + h.cliente_rtn) : "");
        $("#client-customers-bill").text(h.cliente_nombre ? ("Cliente: " + h.cliente_nombre) : "");

        // Vendedor (inputs ocultos + cabecera visible)
        $("#colaborador_id").val(h.colaboradores_id || "");
        $("#colaborador").val(h.vendedor_nombre || "");
        $("#vendedor-customers-bill").text(h.vendedor_nombre ? ("Vendedor: " + h.vendedor_nombre) : "");

        // Fecha / fecha dólar / notas
        if (h.fecha) $("#fecha").val(h.fecha);
        if (h.fecha_dolar) $("#fecha_dolar").val(h.fecha_dolar);
        $("#notesBill").val(h.notas || "");

        // Exoneración
        $("#exoneracion_orden").val(h.no_orden || "");
        $("#exoneracion_constancia").val(h.constancia || "");
        $("#exoneracion_sag").val(h.identificativo_sag || "");
        $("#exoneracion_orden_interno").val(h.numero_interno || "");

        // Tipo de factura (tu hidden usa 1=Contado, 0=Crédito; la BD trae 1/2)
        var tf = parseInt(h.tipo_factura, 10);
        if (tf === 2) { // Crédito
          $("#facturas_activo").val(0);
          $("#btn-tipo-credito").addClass("active btn-primary").removeClass("btn-outline-primary");
          $("#btn-tipo-contado").removeClass("active btn-primary").addClass("btn-outline-primary");
        } else { // Contado (default)
          $("#facturas_activo").val(1);
          $("#btn-tipo-contado").addClass("active btn-primary").removeClass("btn-outline-primary");
          $("#btn-tipo-credito").removeClass("active btn-primary").addClass("btn-outline-primary");
        }

        // ====== DETALLE ======
        var datos = Array.isArray(r.detalle) ? r.detalle : [];

        // Limpia la tabla y deja UNA fila base (index 0)
        limpiarTablaFacturaDetalles(0);

        // Rellena filas guardadas
        for (var i = 0; i < datos.length; i++) {
          if (i > 0) addRowFacturas();

          $("#facturas_detalle_id_" + i).val(datos[i]["facturas_detalle_id"] || "");
          $("#bar-code-id_" + i).val(datos[i]["barCode"] || "");
          $("#productos_id_" + i).val(datos[i]["productos_id"] || "");
          $("#productName_" + i).val(datos[i]["producto"] || "");
          $("#productName_text_" + i).text(datos[i]["producto"] || "Descripción del Producto");
          $("#quantity_" + i).val(datos[i]["cantidad"] || 0);
          $("#price_" + i).val(datos[i]["precio"] || 0);
          $("#precio_real_" + i).val(datos[i]["precio"] || 0);
          $("#isv_" + i).val(datos[i]["isv_venta"] || 0);
          $("#valor_isv_" + i).val(datos[i]["isv_valor"] || 0);
          $("#cantidad_mayoreo_" + i).val(datos[i]["cantidad_mayoreo"] || 0);
          $("#precio_mayoreo_" + i).val(datos[i]["precio_venta"] || 0);
          $("#bodega_" + i).val(datos[i]["almacen_id"] || "");
          $("#medida_" + i).val(datos[i]["medida"] || "");
          $("#medida_text_" + i).text(datos[i]["medida"] || "Medida");
          $("#discount_" + i).val(datos[i]["descuento"] || 0);
        }

        // Recalcula importes
        calculateTotalFacturas();

        // Agrega una fila VACÍA extra y deja el cursor en su código
        addRowFacturas();

        // Cierra el modal
        $('#modal_buscar_bill_draft').modal('hide');

        // Notificación bonita
        showNotify('success', 'Borrador', 'Factura cargada correctamente.');
      },
      error: function (xhr) {
        showNotify('error', 'Error', 'Error de comunicación (' + xhr.status + ').');
      }
    });
  });
};

var delete_bill_draft_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.eliminar").on("click", "button.eliminar", function (e) {
    e.preventDefault();
    var row = table.row($(this).parents("tr")).data();
    if (!row) return;
    deleteBillDraft(row.facturas_id);
  });
};

function deleteBillDraft(facturas_id) {
  swal({
    title: "¿Estas seguro?",
    text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
    icon: "warning",
    buttons: { cancel: { text: "Cancelar", visible: true }, confirm: { text: "¡Sí, anular la factura!" } },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then((willConfirm) => {
    if (willConfirm === true) deleteBill(facturas_id);
  });
}

function deleteBill(facturas_id) {
  $.ajax({
    type: 'POST',
    url: BASE_URL + 'core/deleteBillDraft.php',
    data: { facturas_id: facturas_id },
    success: function (data) {
      if (String(data).trim() === "1") {
        showNotify('success', 'Success', 'La factura en borrador ha sido eliminada con éxito');
        listar_busqueda_bill_draf();
      } else {
        showNotify('error', 'Error', 'La factura no se puede eliminar');
      }
    }
  });
}

//BUSQUEDA FACTURAS AL CREDITO Y CONTADO
$("#BillReports").on("click", function(e) {
    e.preventDefault();

    listar_busqueda_bill();

    $('#modal_buscar_bill').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

function getTipoDocumento() {
    var url = '<?php echo SERVERURL; ?>core/getTipoDocumento.php';
	var Documento;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function(response) {
			var datos = eval(response);
	
			Documento = datos[0];				
        },
        error: function(xhr, status, error) {
            console.error("Error en la solicitud:", error);
        }
    });
	
	return Documento;
}

var listar_busqueda_bill = function() {
    var tipo_factura_reporte = 1;
    if ($("#formulario_bill #tipo_factura_reporte").val() == null || $("#formulario_bill #tipo_factura_reporte")
        .val() == "") {
        tipo_factura_reporte = 1;
    } else {
        tipo_factura_reporte = $("#formulario_bill #tipo_factura_reporte").val();
    }

    var fechai = $("#formulario_bill #fechai").val();
    var fechaf = $("#formulario_bill #fechaf").val();
    var facturador = $("#formulario_bill #facturador").val();
    var vendedor = $("#formulario_bill #vendedor").val();
	var factura = getTipoDocumento();
	
    if (factura === "No hay datos que mostrar" || factura === "Error en la solicitud") {
        showNotify('error', 'Error', 'Lo sentimos, hubo un error al obtener la información de la factura.');
        return;
    }
	
    var table_busqueda_bill = $("#DatatableBusquedaBill").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableReporteVentas.php",
            "data": {
                "tipo_factura_reporte": tipo_factura_reporte,
                "facturador": facturador,
                "vendedor": vendedor,
				"factura": factura,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "tipo_documento",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var icon = data === 'Crédito' 
                            ? '<i class="fas fa-clock mr-1"></i>' 
                            : '<i class="fas fa-check-circle mr-1"></i>';
                        var badgeClass = data === 'Crédito' 
                            ? 'badge badge-pill badge-warning' 
                            : 'badge badge-pill badge-success';
                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' + 
                            icon + data + '</span>';
                    }
                    return data;
                }
            },
            {
                "data": "cliente"
            },
            {
                "data": "numero"
            },
            {
                "data": "subtotal",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'black';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "data": "isv",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'black';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "data": "descuento",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'black';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "data": "total",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'black';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark ocultar'><span class='fas fa-file-download fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_comprobante btn btn-dark ocultar'><span class='far fa-file-pdf fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes email_factura btn btn-dark ocultar'><span class='fas fa-paper-plane fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_cancelar cancelar_factura btn btn-dark ocultar'><span class='fas fa-ban fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "9.09%",
                targets: 0
            },
            {
                width: "9.09%",
                targets: 1
            },
            {
                width: "19.09%",
                targets: 2
            },
            {
                width: "18.09%",
                targets: 3
            },
            {
                width: "9.09%",
                targets: 4
            },
            {
                width: "9.09%",
                targets: 5
            },
            {
                width: "9.09%",
                targets: 6
            },
            {
                width: "9.09%",
                targets: 7
            },
            {
                width: "3.09%",
                targets: 8
            },
            {
                width: "3.09%",
                targets: 9
            },
            {
                width: "2.09%",
                targets: 10
            }
        ],
        "buttons": [{
            text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
            titleAttr: 'Actualizar Facturas Borrador',
            className: 'table_actualizar btn btn-secondary ocultar',
            action: function() {
                listar_busqueda_bill();
            }
        }],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_busqueda_bill.search('').draw();
    $('#buscar').focus();

    view_correo_bills_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_reporte_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_comoprobante_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_anular_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
}

var view_anular_bill_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_factura");
    $(tbody).on("click", "button.cancelar_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        anularFacturas(data.facturas_id);
    });
}

var view_correo_bills_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.email_factura");
    $(tbody).on("click", "button.email_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        mailBill(data.facturas_id);
    });
}

var view_reporte_bill_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBill(data.facturas_id);
    });
}

var view_comoprobante_bill_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_comprobante");
    $(tbody).on("click", "button.print_comprobante", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        var url_comprobante = '<?php echo SERVERURL; ?>core/generaComprobante.php?facturas_id=' + data
            .facturas_id;
        window.open(url_comprobante);
    });
}

function anularFacturas(facturas_id) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, anular la factura!",
            }
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
    }).then((willConfirm) => {
        if (willConfirm === true) {
            anular(facturas_id);
        }
    });
}

function anular(facturas_id) {
    var url = '<?php echo SERVERURL; ?>core/anularFactura.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'facturas_id=' + facturas_id,
        success: function(data) {
            if (data == 1) {
                showNotify('success', 'Success', 'La factura ha sido anulada con éxito');
                listar_busqueda_bill();
            } else {
                showNotify('error', 'Error', 'La factura no se puede anular');
            }
        }
    });
}

function getReporteFactura() {
    var url = '<?php echo SERVERURL; ?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_bill #tipo_factura_reporte').html("");
            $('#formulario_bill #tipo_factura_reporte').html(data);
            $('#formulario_bill #tipo_factura_reporte').selectpicker('refresh');
        }
    });
}

function getEstadoFacturaCredito() {
    var url = '<?php echo SERVERURL; ?>core/getEstadoFacturaCredito.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').html("");
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').html(data);
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').selectpicker(
                'refresh');
        }
    });
}

$('#formulario_bill_draft #search').on("click", function(e) {
    e.preventDefault();
    listar_busqueda_bill_draf();
});

$('#formulario_bill #search').on("click", function(e) {
    e.preventDefault();
    listar_busqueda_bill();
});

//INICIO DESCUENTO PRODUCTO EN FACTURACION
$(() => {
    $("#invoice-form #invoiceItem").on('click', '.aplicar_descuento', function(e) {
        e.preventDefault();
        $('#formDescuentoFacturacion')[0].reset();

        var row_index = $(this).closest("tr").index();
        var col_index = $(this).closest("td").index();

        if ($('#invoice-form #cliente_id').val() != "" && $("#invoice-form #invoiceItem #productos_id_" + row_index).val() != "") {
            $('#formDescuentoFacturacion #row_index').val(row_index);
            $('#formDescuentoFacturacion #col_index').val(col_index);

            var productos_id = $("#invoice-form #invoiceItem #productos_id_" + row_index).val();
            var producto = $("#invoice-form #invoiceItem #productName_" + row_index).val();
            var precio = $("#invoice-form #invoiceItem #price_" + row_index).val();
            var cantidad = $("#invoice-form #invoiceItem #quantity_" + row_index).val();
            var total = precio * cantidad;

            $('#formDescuentoFacturacion #descuento_productos_id').val(productos_id);
            $('#formDescuentoFacturacion #producto_descuento_fact').val(producto);
            $('#formDescuentoFacturacion #precio_descuento_fact').val(total); // Guardamos el total, no el precio unitario
            $('#formDescuentoFacturacion #cantidad_descuento_fact').val(cantidad); // Guardamos la cantidad

            $('#formDescuentoFacturacion #pro_descuento_fact').val("Aplicar Descuento");

            getPermisosTipoUsuarioAccesosForms(getPrivilegioTipoUsuario());

            $('#modalDescuentoFacturacion').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        } else {
            showNotify('error', 'Error', 'Debe seleccionar un cliente y un producto antes de continuar');
        }
    });

    // Cálculo del descuento en porcentaje
    $("#formDescuentoFacturacion #porcentaje_descuento_fact").on("keyup", function() {
        var total = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val());
        var porcentaje = parseFloat($(this).val()) || 0;

        var descuento = total * (porcentaje / 100);
        $('#formDescuentoFacturacion #descuento_fact').val(descuento.toFixed(2));
    });

    // Cálculo del porcentaje cuando se ingresa el monto directo
    $("#formDescuentoFacturacion #descuento_fact").on("keyup", function() {
        var total = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val());
        var descuento = parseFloat($(this).val()) || 0;

        var porcentaje = (descuento / total) * 100;
        $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(porcentaje.toFixed(2));
    });
});

// Aplicar el descuento
$("#reg_DescuentoFacturacion").on("click", function(e) {
    e.preventDefault();
    var row_index = $('#formDescuentoFacturacion #row_index').val();
    var col_index = $('#formDescuentoFacturacion #col_index').val();

    var descuento = parseFloat($('#formDescuentoFacturacion #descuento_fact').val()) || 0;
    var precio = parseFloat($("#invoice-form #invoiceItem #price_" + row_index).val());
    var cantidad = parseFloat($("#invoice-form #invoiceItem #quantity_" + row_index).val());
    var impuesto_venta = $("#invoice-form #invoiceItem #isv_" + row_index).val();
    
    // Guardamos el descuento en la fila
    $("#invoice-form #invoiceItem #discount_" + row_index).val(descuento.toFixed(2));

    var total_sin_descuento = precio * cantidad;
    var total_con_descuento = total_sin_descuento - descuento;

    if (total_con_descuento >= 0) {
        // Cálculo de ISV
        if (impuesto_venta == 1) {
            var porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);
            var isv_actual = parseFloat($('#invoice-form #taxAmount').val()) || 0;
            var isv_nuevo = (total_con_descuento * porcentaje_isv).toFixed(2);
            
            // Actualizamos el ISV
            $('#invoice-form #taxAmount').val(parseFloat(isv_actual) + parseFloat(isv_nuevo));
            $('#invoice-form #invoiceItem #valor_isv_' + row_index).val(isv_nuevo);
        }

        $('#modalDescuentoFacturacion').modal('hide');
        calculateTotalFacturas();
    } else {
        showNotify('warning', 'Advertencia', 'El valor del descuento es mayor al precio total del artículo, por favor corregir');
    }
});
//FIN DESCUENTO PRODUCTO EN FACTURACION

//INICIO MODIFICAR PRECIO EN PRODUCTO FACTURACION
$(() => {
    $("#invoice-form #invoiceItem").off('click.editarPrecioFacturaEscritorio', '.aplicar_precio');
    $("#invoice-form #invoiceItem").on('click.editarPrecioFacturaEscritorio', '.aplicar_precio', function(e) {
        e.preventDefault();
        var row_index = $(this).closest("tr").index();
        abrirEditarPrecioFacturaEscritorio(row_index);
    });
});
//FIN MODIFICAR PRECIO EN PRODUCTO FACTURACION

$(() => {
    // Guardar datos de exoneración en campos ocultos al hacer clic en el botón "Guardar datos"
    $("#guardar_exoneracion").click(function() {
        $("#exoneracion_orden").val($("#modal_exoneracion_orden").val());
        $("#exoneracion_constancia").val($("#modal_exoneracion_constancia").val());
        $("#exoneracion_sag").val($("#modal_exoneracion_sag").val());
        $("#exoneracion_orden_interno").val($("#modal_exoneracion_orden_interno").val());
        
        // Cerrar modal
        $("#exoneracionModal").modal("hide");
        
        // Opcional: Mostrar un indicador visual de que hay datos de exoneración
        if ($("#modal_exoneracion_orden").val() || $("#modal_exoneracion_constancia").val() || 
            $("#modal_exoneracion_sag").val() || $("#modal_exoneracion_orden_interno").val()) {
            $("#btn_exoneracion").removeClass("btn-outline-info").addClass("btn-info");
        } else {
            $("#btn_exoneracion").removeClass("btn-info").addClass("btn-outline-info");
        }
    });
    
    // Cargar datos en el modal cuando se abre
    $("#exoneracionModal").on("show.bs.modal", function() {
        $("#modal_exoneracion_orden").val($("#exoneracion_orden").val());
        $("#modal_exoneracion_constancia").val($("#exoneracion_constancia").val());
        $("#modal_exoneracion_sag").val($("#exoneracion_sag").val());
        $("#modal_exoneracion_orden_interno").val($("#exoneracion_orden_interno").val());
    });

    $('#exoneracionModal').on('shown.bs.modal', function() {
        $('#modal_exoneracion_orden').focus();
    });
});

// === Control de Tipo de Factura con botones ===
// Proforma NO bloquea contado/crédito.
// Puede existir:
// Contado + Proforma
// Crédito + Proforma

let tipoActual = 'contado';

function setTipoFactura(tipo) {
    const isContado = (tipo === 'contado');

    tipoActual = tipo;

    $('#tipo-factura-control').removeClass('proforma-activa');

    $('#btn-tipo-contado')
        .prop('disabled', false)
        .toggleClass('active btn-primary', isContado)
        .toggleClass('btn-outline-primary', !isContado);

    $('#btn-tipo-credito')
        .prop('disabled', false)
        .toggleClass('active btn-primary', !isContado)
        .toggleClass('btn-outline-primary', isContado);

    // Backend actual:
    // 1 = Contado
    // 0 = Crédito
    $('#facturas_activo').val(isContado ? 1 : 0);

    if ($('#label_facturas_activo').length) {
        $('#label_facturas_activo').text(isContado ? 'Contado' : 'Crédito');
    }

    console.log('[TIPO FACTURA]', {
        tipo: tipo,
        facturas_activo: $('#facturas_activo').val(),
        proforma: $('#invoice-form #facturas_proforma').is(':checked')
    });
}

// Inicial: siempre Contado seleccionado
$(function () {
    setTipoFactura('contado');
});

// Click en Contado
$(document).off('click.tipoContado', '#btn-tipo-contado');
$(document).on('click.tipoContado', '#btn-tipo-contado', function () {
    setTipoFactura('contado');
});

// Click en Crédito
$(document).off('click.tipoCredito', '#btn-tipo-credito');
$(document).on('click.tipoCredito', '#btn-tipo-credito', function () {
    if (tipoActual === 'contado') {
        $('#confirmTipoFactura')
            .data('next', 'credito')
            .modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
    } else {
        setTipoFactura('credito');
    }
});

// Confirmar Crédito
$(document).off('click.confirmarTipoFactura', '#confirmarCambioTipo');
$(document).on('click.confirmarTipoFactura', '#confirmarCambioTipo', function () {
    const next = $('#confirmTipoFactura').data('next') || 'credito';
    setTipoFactura(next);
    $('#confirmTipoFactura').modal('hide');
});


/*RECURRENCIA EN FACTURAS*/
/* =========== RECURRENCIA EN FACTURAS =========== */

/* Utilidades */
// Utilidad para recalcular ISV de una línea (usada por blur/keyup y +/-)
async function recalcularISVLinea(row_index){
  // llama al nuevo cálculo con caché de ISV 1/2
  await recalcISVForRow(row_index);
}

function _localISOString(dt){
  // datetime-local: "YYYY-MM-DDTHH:mm" (sin zona)
  const pad = n => String(n).padStart(2,'0');
  return dt.getFullYear() + '-' +
         pad(dt.getMonth()+1) + '-' +
         pad(dt.getDate()) + 'T' +
         pad(dt.getHours()) + ':' +
         pad(dt.getMinutes());
}

function hayDetalleFactura(){
  const totalFilas = parseInt($('#bill_row').val(), 10) || 0;
  for (let i = 0; i <= totalFilas; i++){
    const pid   = $('#productos_id_'+i).val();
    const pname = $('#productName_'+i).val();
    const qty   = $('#quantity_'+i).val();
    const price = $('#price_'+i).val();
    if(pid && pname && qty && price) return true;
  }
  return false;
}

function hayEncabezado(){
  return !!($('#cliente_id').val() && $('#colaborador_id').val());
}

/* Abrir modal de recurrencia */
$(document).on('click', '#addRecurringBill', function(){
  // Validar que la factura tenga encabezado y detalle
  if(!hayEncabezado()){
    showNotify('error','Faltan datos','Debe seleccionar cliente y vendedor antes de programar la recurrencia');
    return;
  }
  if(!hayDetalleFactura()){
    showNotify('error','Sin productos','Debe agregar al menos un producto para programar la recurrencia');
    return;
  }

  // Tipo actual de la UI (1=contado, 0=crédito en tu front)
  var tipoActual = $('#facturas_activo').val(); 
  var tipoFacturaBack = (tipoActual === "1") ? "1" : "2"; // backend: 1=contado, 2=crédito
  $('#rec_tipo_factura').val(tipoFacturaBack);

  // Toggle botones de tipo factura
  $('#btn-rec-contado')
    .toggleClass('btn-primary', tipoFacturaBack === "1")
    .toggleClass('btn-outline-primary', tipoFacturaBack !== "1");
  $('#btn-rec-credito')
    .toggleClass('btn-primary', tipoFacturaBack === "2")
    .toggleClass('btn-outline-primary', tipoFacturaBack !== "2");

  // Documento por defecto: normal (0)
  $('#rec_tipo_documento').val('0');
  $('#btn-rec-tipo-normal').addClass('btn-primary').removeClass('btn-outline-primary');
  $('#btn-rec-tipo-proforma').addClass('btn-outline-primary').removeClass('btn-primary');

  // Fecha inicio default: ahora + 10min
  var now = new Date();
  now.setMinutes(now.getMinutes()+10);
  $('#rec_start_at').val(_localISOString(now));

  // Periodicidad default
  $('#rec_periodicidad').val('monthly');
  $('#rec_until').val('');

  // Reset spinner/info
  $('#rec_info').show();
  $('#rec_spinner').hide();

  $('#recurringBillModal').modal('show');
});

/* Toggle tipo documento (0 normal, 1 proforma) */
$(document).on('click', '#btn-rec-tipo-normal, #btn-rec-tipo-proforma', function(){
  var tipo = String($(this).data('tipo')); // "0" o "1"
  $('#rec_tipo_documento').val(tipo);
  $('#btn-rec-tipo-normal')
    .toggleClass('btn-primary', tipo === "0")
    .toggleClass('btn-outline-primary', tipo !== "0");
  $('#btn-rec-tipo-proforma')
    .toggleClass('btn-primary', tipo === "1")
    .toggleClass('btn-outline-primary', tipo !== "1");
});

/* Toggle tipo factura (1 contado, 2 crédito) */
$(document).on('click', '#btn-rec-contado, #btn-rec-credito', function(){
  var tipo = String($(this).data('tipo')); // "1" o "2"
  $('#rec_tipo_factura').val(tipo);
  $('#btn-rec-contado')
    .toggleClass('btn-primary', tipo === "1")
    .toggleClass('btn-outline-primary', tipo !== "1");
  $('#btn-rec-credito')
    .toggleClass('btn-primary', tipo === "2")
    .toggleClass('btn-outline-primary', tipo !== "2");
});

/* Guardar recurrencia */
$(document).on('click', '#confirmRecurring', function(){
  // Validación rápida
  if(!hayEncabezado()){
    showNotify('error','Faltan datos','Debe seleccionar cliente y vendedor');
    return;
  }
  if(!hayDetalleFactura()){
    showNotify('error','Sin productos','Debe agregar al menos un producto');
    return;
  }

  var startAt = $('#rec_start_at').val();
  if(!startAt){
    showNotify('error','Error','Debes indicar la fecha de generación');
    return;
  }

  // Construir payload (encabezado)
  var payload = {
    clientes_id: $('#cliente_id').val(),
    colaboradores_id: $('#colaborador_id').val(),
    notas: $('#notesBill').val(),
    fecha_dolar: $('#fecha_dolar').val(),
    tipo_documento: $('#rec_tipo_documento').val(), // "0" normal, "1" proforma
    tipo_factura: $('#rec_tipo_factura').val(),     // "1" contado, "2" crédito
    start_at: startAt,                              // datetime-local
    periodicidad: $('#rec_periodicidad').val(),     // once/daily/weekly/monthly (según tu modal)
    until: $('#rec_until').val() || null,
    exoneracion_orden: $('#exoneracion_orden').val() || null,
    exoneracion_constancia: $('#exoneracion_constancia').val() || null,
    exoneracion_sag: $('#exoneracion_sag').val() || null,
    exoneracion_orden_interno: $('#exoneracion_orden_interno').val() || null,
    detalle: []
  };

  // Detalle
  var totalFilas = parseInt($('#bill_row').val(), 10) || 0;
  for (var i = 0; i <= totalFilas; i++) {
    var pid   = $('#productos_id_'+i).val();
    var pname = $('#productName_'+i).val();
    var qty   = $('#quantity_'+i).val();
    var price = $('#price_'+i).val();
    if(!pid || !pname || !qty || !price) continue;

    payload.detalle.push({
      productos_id : pid,
      producto     : pname,
      cantidad     : parseFloat(qty),
      precio       : parseFloat(price),
      descuento    : parseFloat($('#discount_'+i).val() || 0),
      isv_valor    : parseFloat($('#valor_isv_'+i).val() || 0),
      isv_valor1    : parseFloat($('#valor_isv1_'+i).val() || 0),
      medida       : $('#medida_'+i).val() || '',
      almacen_id   : $('#bodega_'+i).val() || ''
    });
  }

  if(payload.detalle.length === 0){
    showNotify('error','Sin productos','Debes tener al menos un producto en la factura');
    return;
  }

  // Spinner ON
  $('#rec_info').hide();
  $('#rec_spinner').show();

  $.ajax({
    type: 'POST',
    url: "<?php echo SERVERURL;?>core/facturas/agregarFacturaRecurrente.php",
    data: { data: JSON.stringify(payload) },
    dataType: 'json'
  })
  .done(function(res){
    if(res && (res.ok === true || res.success === true)){
      $('#recurringBillModal').modal('hide');
      showNotify('success','Recurrencia creada','La factura recurrente ha sido guardada');
    }else{
      $('#rec_info').show();
      $('#rec_spinner').hide();
      var msg = (res && (res.msg || res.message)) ? (res.msg || res.message) : 'No se pudo guardar la recurrencia';
      showNotify('error','Error', msg);
    }
  })
  .fail(function(xhr){
    $('#rec_info').show();
    $('#rec_spinner').hide();
    console.error('AJAX error:', xhr.responseText);
    showNotify('error','Error','Falló la petición al servidor');
  });
});

/* ===== Ayuda (lógica) ===== */
(function(){
  // Filtro por texto
  function filterRows(term){
    term = (term || '').toLowerCase();
    $('#tableShortcuts tbody tr').each(function(){
      const txt = $(this).text().toLowerCase();
      $(this).toggle(txt.indexOf(term) > -1);
    });
  }

  // Al abrir: limpiar y enfocar el buscador
  $('#modalAyuda').on('shown.bs.modal', function(){
    $('#helpSearch').val('').trigger('focus');
  });

  // Buscar mientras escribe
  $(document).on('input', '#helpSearch', function(){
    filterRows(this.value);
  });

  // Copiar a portapapeles
  $('#helpCopy').on('click', function(){
    const rows = [];
    $('#tableShortcuts tbody tr:visible').each(function(){
      const tds = $(this).find('td');
      rows.push(
        `${$(tds[0]).text()} — ${$(tds[1]).text()} — ${$(tds[2]).text()}`
      );
    });
    const text = rows.join('\n');
    navigator.clipboard.writeText(text).then(function(){
      if (typeof showNotify === 'function') {
        showNotify('success','Copiado','Atajos copiados al portapapeles');
      }
    }).catch(function(){ /* silencioso */ });
  });

  // Imprimir
  $('#helpPrint').on('click', function(){
    const htmlTable = document.querySelector('#tableShortcuts').outerHTML;
    const w = window.open('', '_blank');
    w.document.write(`
      <html>
        <head>
          <title>Ayuda - Atajos</title>
          <style>
            body{font:14px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:24px}
            h2{margin:0 0 12px}
            table{width:100%; border-collapse:collapse}
            th,td{padding:8px 10px; border-bottom:1px solid #eee; text-align:left}
            kbd{background:#111;color:#fff;padding:2px 6px;border-radius:4px}
          </style>
        </head>
        <body>
          <h2>Atajos de teclado</h2>
          ${htmlTable}
        </body>
      </html>
    `);
    w.document.close(); w.focus(); w.print();
  });
})();
</script>