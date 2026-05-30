<script>
//reporteCotizacio.php    
$(() => {
    getReporteCotizacion();
    listar_reporte_cotizaciones();
    $('#form_main_cotizaciones #tipo_cotizacion_reporte').val(1);
    $('#form_main_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh');

    $('#form_main_cotizaciones #search').on("click", function(e) {
        e.preventDefault();
        listar_reporte_cotizaciones();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_cotizaciones').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_reporte_cotizaciones();
    });	
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - REPORTE DE COTIZACIONES
   ========================================================= */
   function construirHeaderFooterDataTablaReporteCotizaciones() {
    var $tabla = $("#dataTablaReporteCotizaciones");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Tipo</th>' +
                '<th>Cliente</th>' +
                '<th>Cotización</th>' +
                '<th>SubTotal</th>' +
                '<th>ISV</th>' +
                '<th>Descuento</th>' +
                '<th>Total</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="5">Total</td>' +
                '<td id="subtotal-i"></td>' +
                '<td id="impuesto-i"></td>' +
                '<td id="descuento-i"></td>' +
                '<td id="total-footer-ingreso"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* =========================================================
   LISTADO - REPORTE DE COTIZACIONES
   ========================================================= */
var listar_reporte_cotizaciones = function() {
    var tipo_cotizacion_reporte = 1;

    if (
        $("#form_main_cotizaciones #tipo_cotizacion_reporte").val() == null ||
        $("#form_main_cotizaciones #tipo_cotizacion_reporte").val() == ""
    ) {
        tipo_cotizacion_reporte = 1;
    } else {
        tipo_cotizacion_reporte = $("#form_main_cotizaciones #tipo_cotizacion_reporte").val();
    }

    var fechai = $("#form_main_cotizaciones #fechai").val();
    var fechaf = $("#form_main_cotizaciones #fechaf").val();

    if ($.fn.DataTable.isDataTable("#dataTablaReporteCotizaciones")) {
        $("#dataTablaReporteCotizaciones").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTablaReporteCotizaciones();

    var table_reporteCotizaciones = $("#dataTablaReporteCotizaciones").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableReporteCotizaciones.php",
            "data": {
                "tipo_cotizacion_reporte": tipo_cotizacion_reporte,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
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

                                '<button type="button" class="dropdown-item accion-item table_reportes print_cotizaciones ocultar">' +
                                    '<span class="accion-icon accion-icon-success">' +
                                        '<i class="fas fa-file-download"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Cotización</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes email_cotizacion ocultar">' +
                                    '<span class="accion-icon accion-icon-secondary">' +
                                        '<i class="fas fa-paper-plane"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Enviar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar cancelar_cotizaciones ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-ban"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Anular</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                "data": "fecha"
            },
            {
                "data": "tipo_documento"
            },
            {
                "data": "cliente"
            },
            {
                "data": "numero",
                "render": function(data, type, row) {
                    if (type === 'sort') {
                        return parseInt(row.numero_ordenamiento);
                    }

                    return data;
                }
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
                }
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
                }
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
                }
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
                }
            }
        ],
        "order": [[4, "desc"]],
        "orderFixed": {
            "pre": [[4, "desc"]]
        },
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "10%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "10%",
                targets: 2
            },
            {
                width: "20%",
                targets: 3
            },
            {
                width: "14%",
                targets: 4,
                className: "text-center text-nowrap"
            },
            {
                width: "11%",
                targets: 5,
                className: "text-right text-nowrap"
            },
            {
                width: "11%",
                targets: 6,
                className: "text-right text-nowrap"
            },
            {
                width: "11%",
                targets: 7,
                className: "text-right text-nowrap"
            },
            {
                width: "13%",
                targets: 8,
                className: "text-right text-nowrap"
            }
        ],
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

            var formatter = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2
            });

            $('#subtotal-i').html(formatter.format(totalSubtotal));
            $('#impuesto-i').html(formatter.format(totalIsv));
            $('#descuento-i').html(formatter.format(totalDescuento));
            $('#total-footer-ingreso').html(formatter.format(totalVentas));
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Cotizaciones',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_cotizaciones();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Cotizaciones',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                footer: true,
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte de Cotizaciones',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
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

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_reporteCotizaciones.search('').draw();
    $('#buscar').focus();

    view_reporteCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
    view_enviarCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
    view_anularCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
}

var view_anularCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_cotizaciones");
    $(tbody).on("click", "button.cancelar_cotizaciones", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        anularCotizacion(data.cotizacion_id);
    });
}

var view_enviarCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.email_cotizacion");
    $(tbody).on("click", "button.email_cotizacion", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        mailQuote(data.cotizacion_id);
    });
}

var view_reporteCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_cotizaciones");
    $(tbody).on("click", "button.print_cotizaciones", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printQuote(data.cotizacion_id);
    });
}

function anularCotizacion(cotizacion_id) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea anular la cotización: # " + getNumeroCotizacion(cotizacion_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, anular la cotización!",
            }
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
    }).then((willConfirm) => {
        if (willConfirm === true) {
            anular(cotizacion_id);
        }
    });
}

function anular(cotizacion_id) {
    var url = '<?php echo SERVERURL; ?>core/anularCotizacion.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'cotizacion_id=' + cotizacion_id,
        success: function(data) {
            if (data == 1) {
                swal.close(); // Cierra el modal de SweetAlert  
                showNotify('success', 'Success', 'La cotización ha sido anulada con éxito');
                listar_reporte_cotizaciones();
            } else {
                swal.close(); // Cierra el modal de SweetAlert
                showNotify('error', 'Error', 'La cotización no se pudo anular');
            }
        }
    });
}

function getReporteCotizacion() {
    var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_cotizaciones #tipo_cotizacion_reporte').html("");
            $('#form_main_cotizaciones #tipo_cotizacion_reporte').html(data);
            $('#form_main_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh');
        }
    });
}
//FIN REPORTE DE COTIZACIONES
</script>