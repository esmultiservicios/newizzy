<script>
//reporteVentas.php    
$(document).ready(function() {
    getReporteFactura();
    getFacturador();
    getVendedores();
    GetProductos();
    listar_reporte_ventas();
    $('#form_main_ventas #tipo_factura_reporte').val(1);
    $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');
});

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

function customRound(number) {
    var truncated = Math.floor(number * 100) / 100; // Trunca a dos decimales
    var secondDecimal = Math.floor((number * 100) % 10); // Obtiene el segundo decimal

    if (secondDecimal >= 5) { // Si el segundo decimal es mayor o igual a 5, redondea hacia arriba
        return parseFloat((truncated + 0.01).toFixed(2)); // Redondea hacia arriba
    } else { // Si el segundo decimal es menor que 5, no redondea
        return parseFloat(truncated.toFixed(2)); // No redondea
    }
}

//INICIO REPORTE DE VENTAS
var listar_reporte_ventas = function() {
    let tipo_factura_reporte = $("#form_main_ventas #tipo_factura_reporte").val();
    tipo_factura_reporte = tipo_factura_reporte ? tipo_factura_reporte : 1; //estdo

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
            {
                "data": "vendedor"
            },
            {
                "data": "facturador"
            },
            {
                "defaultContent": "<button class='table_reportes print_factura btn btn-dark table_info ocultar'><span class='fas fa-file-download fa-lg'></span>Factura</button>"
                
            },
            {
                "defaultContent": "<button class='table_reportes print_comprobante btn btn-dark table_success ocultar'><span class='far fa-file-pdf fa-lg'></span>Comprobante</button>"
            },
            {
                "defaultContent": "<button class='table_reportes email_factura btn btn-dark table_danger ocultar'><span class='fas fa-paper-plane fa-lg'></span>Enviar</button>"
            },
            {
                "defaultContent": "<button class='table_cancelar cancelar_factura btn btn-dark table_primary ocultar'><span class='fas fa-ban fa-lg'></span>Anular</button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, // esta se encuentra en el archivo main.js
        "dom": dom,
        "footerCallback": function(row, data, start, end, display) {
            // Aquí puedes calcular los totales y actualizar el footer
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

            // Formatear los totales con separadores de miles y coma para decimales
            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            var totalSubtotalFormatted = formatter.format(totalSubtotal);
            var totalIsvFormatted = formatter.format(totalIsv);
            var totalDescuentoFormatted = formatter.format(totalDescuento);
            var totalVentasFormatted = formatter.format(totalVentas);
            var totalGananciaFormatted = formatter.format(totalGanancia);

            // Asignar los totales a los elementos HTML en el footer
            $('#subtotal-i').html(totalSubtotalFormatted);
            $('#impuesto-i').html(totalIsvFormatted);
            $('#descuento-i').html(totalDescuentoFormatted);
            $('#total-footer-ingreso').html(totalVentasFormatted);
            $('#ganancia').html(totalGananciaFormatted);
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Ventas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_ventas();
                    total_ingreso_footer();
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
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
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
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function(doc) {
                    if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
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

    view_correo_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_comprobante_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_anular_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);

    // Función para determinar el color de fondo de la fila
    function determinarColorFila(saldo) {
        return saldo < 0 ? 'fila-roja' : 'fila-verde'; // Puedes ajustar los nombres de las clases según tu estilo
    }

    // Callback para colorear las filas según el saldo
    table_reporteVentas.on('draw', function() {
        table_reporteVentas.rows().every(function(index, element) {
            var saldo = parseFloat(this.data().saldo) || 0;
            var color = determinarColorFila(saldo);

            $(this.node()).removeClass('fila-roja fila-verde').addClass(color);
        });
    });
};

var view_anular_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_factura");
    $(tbody).on("click", "button.cancelar_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        anularFacturas(data.facturas_id);
    });
}

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
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera        
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
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                    
                });
                listar_reporte_ventas();
            } else {
                swal({
                    title: "Error",
                    text: "La factura no se puede anular",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
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
//FIN REPORTE DE VENTAS

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
            // Calcular los totales
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

            // Formatear los totales
            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2,
            });

            var totalPrecioFormatted = formatter.format(totalPrecio);
            var totalCantidadFormatted = formatter.format(totalCantidad);
            var totalISVFormatted = formatter.format(totalISV);
            var totalDescuentoFormatted = formatter.format(totalDescuento);
            var totalTotalFormatted = formatter.format(totalTotal);

            // Actualizar los elementos HTML en el footer con los totales calculados
            $('#total-precio').html(totalPrecioFormatted);
            $('#total-cantidad').html(totalCantidadFormatted);
            $('#total-isv').html(totalISVFormatted);
            $('#total-descuento').html(totalDescuentoFormatted);
            $('#total-total').html(totalTotalFormatted);
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
                title: 'Reporte Detalle de Ventas', // Título en negrita
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