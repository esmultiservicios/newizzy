<script>
$(() => {
    getReporteFactura();
    getFacturador();
    getVendedores();
    GetProductos();
    listar_reporte_ventas();
    $('#form_main_ventas #tipo_factura_reporte').val(1);
    $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');

    // Evento para el botón de Generar Reporte
    $('#form_main_ventas').on('submit', function(e) {
        e.preventDefault();
        listar_reporte_ventas();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#form_main_ventas')[0].reset();
        $('#form_main_ventas .selectpicker').selectpicker('refresh');
        listar_reporte_ventas();
    });
});

// Eventos para los filtros
$('#form_main_ventas #tipo_factura_reporte').on("change", function(e) {
    listar_reporte_ventas();
});

$('#form_main_ventas #facturador').on("change", function(e) {
    listar_reporte_ventas();
});

$('#form_main_ventas #vendedor').on("change", function(e) {
    listar_reporte_ventas();
});

$('#form_main_ventas #fechai').on("change", function(e) {
    listar_reporte_ventas();
});

$('#form_main_ventas #fechaf').on("change", function(e) {
    listar_reporte_ventas();
});

$('#form_main_ventas #factura_reporte').on("change", function(e) {
    listar_reporte_ventas();
});

// Función para redondear números de manera personalizada
function customRound(number) {
    var truncated = Math.floor(number * 100) / 100;
    var secondDecimal = Math.floor((number * 100) % 10);

    if (secondDecimal >= 5) {
        return parseFloat((truncated + 0.01).toFixed(2));
    } else {
        return parseFloat(truncated.toFixed(2));
    }
}

// Función principal para listar el reporte de ventas
var listar_reporte_ventas = function() {
    let tipo_factura_reporte = $("#form_main_ventas #tipo_factura_reporte").val();
    tipo_factura_reporte = tipo_factura_reporte ? tipo_factura_reporte : 1;

    let factura = $("#form_main_ventas #factura_reporte").val();
    factura = factura ? factura : 1;

    var fechai = $("#form_main_ventas #fechai").val();
    var fechaf = $("#form_main_ventas #fechaf").val();
    var facturador = $("#form_main_ventas #facturador").val();
    var vendedor = $("#form_main_ventas #vendedor").val();

    var table_reporteVentas = $("#dataTablaReporteVentas").DataTable({
        "destroy": true,
        "footer": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableReporteVentas.php",
            "data": {
                "tipo_factura_reporte": tipo_factura_reporte,
                "facturador": facturador,
                "vendedor": vendedor,
                "fechai": fechai,
                "fechaf": fechaf,
                "factura": factura
            }
        },
        "columns": [
            {"data": "fecha"},
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
            {"data": "cliente"},
            {"data": "numero"},
            {
                "data": "subtotal",
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
                "data": "isv",
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
                "data": "descuento",
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
                "data": "total",
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
                "data": "ganancia",
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
            {"data": "vendedor"},
            {"data": "facturador"},
            {
                "defaultContent": "<button class='table_reportes detalle_factura btn btn-dark table_info'><span class='fas fa-search fa-lg'></span> Detalle</button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark table_info ocultar'><span class='fas fa-file-download fa-lg'></span> Factura</button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_comprobante btn btn-dark table_success ocultar'><span class='far fa-file-pdf fa-lg'></span> Comprobante</button>"
            },
            {
                "defaultContent": "<button class='table_reportes email_factura btn btn-dark table_danger ocultar'><span class='fas fa-paper-plane fa-lg'></span> Enviar</button>"
            },
            {
                "defaultContent": "<button class='table_cancelar cancelar_factura btn btn-dark table_primary ocultar'><span class='fas fa-ban fa-lg'></span> Anular</button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "footerCallback": function(row, data, start, end, display) {
            var totalSubtotal = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.subtotal) || 0);
            }, 0);

            var totalIsv = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.isv) || 0);
            }, 0);

            var totalDescuento = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.descuento) || 0);
            }, 0);

            var totalVentas = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.total) || 0);
            }, 0);

            var totalGanancia = data.reduce(function(acc, row) {
                return acc + (parseFloat(row.ganancia) || 0);
            }, 0);

            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            $('#subtotal-i').html(formatter.format(totalSubtotal));
            $('#impuesto-i').html(formatter.format(totalIsv));
            $('#descuento-i').html(formatter.format(totalDescuento));
            $('#total-footer-ingreso').html(formatter.format(totalVentas));
            $('#ganancia').html(formatter.format(totalGanancia));
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Ventas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_ventas();
                }
            },
            {
                text: '<i class="fas fa-search fa-lg crear"></i> Detalle Ventas',
                titleAttr: 'Detalle Ventas',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_detalles();
                }
            },            
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Ventas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                footer: true,
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte de Ventas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function(doc) {
                    if (imagen) {
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
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });

    table_reporteVentas.search('').draw();
    $('#buscar').focus();

    view_detalle_factura_dataTable ("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_correo_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_comprobante_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_anular_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
};

var view_detalle_factura_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.detalle_factura");
    $(tbody).on("click", "button.detalle_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        mostrarDetalleFactura(data.facturas_id);
    });
}

function mostrarDetalleFactura(facturas_id) {
    console.log('Mostrando detalle para factura ID:', facturas_id); // Debug
    
    // Mostrar el modal
    var $modal = $('#modalDetalleFactura');
    $modal.modal('show');

    // Obtener datos
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getDetalleFacturaReporteVentas.php',
        type: 'POST',
        data: { facturas_id: facturas_id },
        dataType: 'json',
        success: function(response) {
            console.log('Respuesta del servidor:', response); // Debug
            
            if (response.success && response.data) {
                var factura = response.data.cabecera;
                var detalles = response.data.detalle;
                
                console.log('Datos de factura:', factura); // Debug
                console.log('Detalles:', detalles); // Debug

                // Llenar cabecera
                $modal.find('#numero-factura-modal').text(factura.numero_factura || 'N/A');
                $modal.find('#fecha-factura').text(factura.fecha || 'N/A');
                $modal.find('#cliente-factura').text(factura.cliente || 'N/A');
                $modal.find('#tipo-factura').text(factura.tipo_factura || 'N/A');
                
                // Estado
                var estadoNum = parseInt(factura.estado) || 0;
                var estadoBadge = '';
                switch(estadoNum) {
                    case 2: estadoBadge = 'badge-success">Pagada'; break;
                    case 3: estadoBadge = 'badge-warning text-dark">Crédito'; break;
                    case 4: estadoBadge = 'badge-danger">Anulada'; break;
                    default: estadoBadge = 'badge-secondary">Pendiente';
                }
                $modal.find('#estado-factura').html('<span class="badge badge-pill ' + estadoBadge + '</span>');
                
                // Totales
                $modal.find('#subtotal-factura').text(formatMoney(factura.subtotal || 0));
                $modal.find('#total-factura').text(formatMoney(factura.total || 0));
                $modal.find('#notas-factura').text(factura.notas || 'No hay notas');

                // Llenar detalle
                var detalleHtml = '';
                if (detalles && detalles.length > 0) {
                    detalles.forEach(function(item) {
                        detalleHtml += `
                            <tr>
                                <td>${item.producto || 'Producto no especificado'}</td>
                                <td class="text-center">${item.cantidad || 0} ${item.medida || ''}</td>
                                <td class="text-right">${formatMoney(item.precio || 0)}</td>
                                <td class="text-right">${formatMoney(item.isv_valor || 0)}</td>
                                <td class="text-right">${formatMoney(item.descuento || 0)}</td>
                                <td class="text-right">${formatMoney(item.subtotal || 0)}</td>
                            </tr>`;
                    });
                } else {
                    detalleHtml = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No se encontraron detalles para esta factura
                            </td>
                        </tr>`;
                }
                
                $modal.find('#detalle-factura-body').html(detalleHtml);
                
                // Configurar botón de imprimir
                $modal.find('#btn-imprimir-factura').off('click').on('click', function() {
                    if (typeof printBillReporteVentas === 'function') {
                        printBillReporteVentas(facturas_id);
                    }
                });
                
            } else {
                $modal.find('.modal-body').html(`
                    <div class="alert alert-danger">
                        ${response.message || 'Error al cargar los detalles'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en AJAX:', error, xhr.responseText);
            $modal.find('.modal-body').html(`
                <div class="alert alert-danger">
                    Error al cargar los datos: ${error}
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="mostrarDetalleFactura(${facturas_id})">
                        <i class="fas fa-sync-alt"></i> Reintentar
                    </button>
                </div>
            `);
        }
    });
}

// Función de formato de dinero segura
function formatMoney(amount) {
    try {
        var number = parseFloat(amount) || 0;
        return 'L ' + number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    } catch(e) {
        console.error('Error formateando dinero:', e);
        return 'L 0.00';
    }
}

// Funciones para manejar los eventos de los botones en la tabla
var view_correo_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.email_factura");
    $(tbody).on("click", "button.email_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        mailBill(data.facturas_id);
    });
}

var view_reporte_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillReporteVentas(data.facturas_id);
    });
}

var view_reporte_comprobante_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_comprobante");
    $(tbody).on("click", "button.print_comprobante", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillComprobanteReporteVentas(data.facturas_id);
    });
}

var view_anular_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_factura");
    $(tbody).on("click", "button.cancelar_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        anularFacturas(data.facturas_id);
    });
}

function anularFacturas(facturas_id) {
    swal({
        title: "¿Esta seguro?",
        text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
        content: {
            element: "input",
            attributes: {
                placeholder: "Comentario",
                type: "text",
            },
        },
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            confirm: {
                text: "¡Sí, anular la factura!",
                closeModal: false,
            },
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false        
    }).then((value) => {
        if (value === null || value.trim() === "") {
            swal("¡Necesita escribir algo!", { icon: "error" });
            return false;
        }
        anular(facturas_id, value);
    });
}

function anular(facturas_id, comentario) {
    var url = '<?php echo SERVERURL; ?>core/anularFactura.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'facturas_id=' + facturas_id + '&comentario=' + comentario,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "La factura ha sido anulada con éxito",
                    icon: "success",
                    closeOnEsc: false,
                    closeOnClickOutside: false                    
                });
                listar_reporte_ventas();
            } else {
                swal({
                    title: "Error",
                    text: "La factura no se puede anular",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
            }
        }
    });
}

function getReporteFactura() {
    var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_ventas #tipo_factura_reporte').html("");
            $('#form_main_ventas #tipo_factura_reporte').html(data);
            $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');
        }
    });
}

function getFacturador() {
    var url = '<?php echo SERVERURL;?>core/getFacturador.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_ventas #facturador').html("");
            $('#form_main_ventas #facturador').html(data);
            $('#form_main_ventas #facturador').selectpicker('refresh');
        }
    });
}

function getVendedores() {
    var url = '<?php echo SERVERURL;?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_ventas #vendedor').html("");
            $('#form_main_ventas #vendedor').html(data);
            $('#form_main_ventas #vendedor').selectpicker('refresh');

            $('#FormDetalleVentas #DetalleVendedores').html("");
            $('#FormDetalleVentas #DetalleVendedores').html(data);
            $('#FormDetalleVentas #DetalleVendedores').selectpicker('refresh');            
        }
    });
}

function GetProductos() {
    var url = '<?php echo SERVERURL;?>core/getProductos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#FormDetalleVentas #DetallesProductos').html("");
            $('#FormDetalleVentas #DetallesProductos').html(data);
            $('#FormDetalleVentas #DetallesProductos').selectpicker('refresh');            
        }
    });
}

function modal_detalles(){
    getVendedores();
    GetProductos();
    ListarDetalleVenas();
    $('#ModalDetalleVentas').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });    
}

var ListarDetalleVenas = function(){
    var fechai = $("#FormDetalleVentas #DetallesFechai").val();
    var fechaf = $("#FormDetalleVentas #DetallesFechaf").val();
    var productos_id = $("#FormDetalleVentas #DetallesProductos").val();
    var colaboradores_id = $("#FormDetalleVentas #DetalleVendedores").val();

	var table_puestos  = $("#DatatableDetalleVentas").DataTable({
		"destroy":true,
		"ajax":{
			"method":"POST",
			"url":"<?php echo SERVERURL;?>core/llenarDataTableDetalleVentas.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf,
                "productos_id": productos_id,
                "colaboradores_id": colaboradores_id
            }
		},
		"columns":[
			{"data":"Producto"},
			{"data":"numero"},
			{
                "data":"Precio",
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
			{"data":"Cantidad"},
            {
                "data":"ISV",
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
                "data":"Descuento",
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
                "data":"Total",
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
            {"data":"Vendedor"}
		],
        "lengthMenu": lengthMenu,
		"stateSave": true,
		"bDestroy": true,
		"language": idioma_español,
		"dom": dom,
        "footerCallback": function(row, data, start, end, display) {
            var totalPrecio = 0;
            var totalCantidad = 0;
            var totalISV = 0;
            var totalDescuento = 0;
            var totalTotal = 0;

            data.forEach(function(row) {
                totalPrecio += parseFloat(row.Precio) || 0;
                totalCantidad += parseFloat(row.Cantidad) || 0;
                totalISV += parseFloat(row.ISV) || 0;
                totalDescuento += parseFloat(row.Descuento) || 0;
                totalTotal += parseFloat(row.Total) || 0;
            });

            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            $('#total-precio').html(formatter.format(totalPrecio));
            $('#total-cantidad').html(formatter.format(totalCantidad));
            $('#total-isv').html(formatter.format(totalISV));
            $('#total-descuento').html(formatter.format(totalDescuento));
            $('#total-total').html(formatter.format(totalTotal));
        },
		"columnDefs": [
		  { width: "5%", targets: 0 },
		  { width: "85%", targets: 1 },
		  { width: "5%", targets: 2 },
		  { width: "5%", targets: 3 }
		],
		"buttons":[
			{
				text:      '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
				titleAttr: 'Actualizar Puestos',
				className: 'table_actualizar btn btn-secondary ocultar',
				action: 	function(){
					ListarDetalleVenas();
				}
			},
			{
				extend:    'excelHtml5',
				text:      '<i class="fas fa-file-excel fa-lg"></i> Excel',
				titleAttr: 'Excel',
				title: 'Reporte Detalle de Ventas',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-success ocultar',					
			},
			{
				extend:    'pdf',
                orientation: 'landscape',
				text:      '<i class="fas fa-file-pdf fa-lg"></i> PDF',
				titleAttr: 'PDF',
                title: 'Reporte Detalle de Ventas',
				messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
				className: 'table_reportes btn btn-danger ocultar',			
				customize: function ( doc ) {
					doc.content.splice( 1, 0, {
						margin: [ 0, 0, 0, 12 ],
						alignment: 'left',
						image: imagen,
						width:100,
                        height:45
					} );
				}
			}
		],
		"drawCallback": function( settings ) {
        	getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    	}
	});
	table_puestos.search('').draw();
	$('#buscar').focus();
}
</script>