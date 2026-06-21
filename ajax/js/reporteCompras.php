<script>
//reporteCompras.php    
$(() => {
    getReporteCompras();
    listar_reporte_compras();
    $('#form_main_compras #tipo_compras_reporte').val(1);
    $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');

    $('#form_main_compras #search').on("click", function(e) {
        e.preventDefault();
        listar_reporte_compras();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_compras').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_reporte_compras();
    });		   
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - REPORTE DE COMPRAS
   ========================================================= */
   function construirHeaderFooterDataTablaReporteCompras() {
    var $tabla = $("#dataTablaReporteCompras");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Tipo</th>' +
                '<th>Cuenta</th>' +
                '<th>Proveedor</th>' +
                '<th>Número</th>' +
                '<th>SubTotal</th>' +
                '<th>ISV</th>' +
                '<th>Descuento</th>' +
                '<th>Total</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="6">Total</td>' +
                '<td id="subtotal-i"></td>' +
                '<td id="impuesto-i"></td>' +
                '<td id="descuento-i"></td>' +
                '<td id="total-footer-ingreso"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTADO - REPORTE DE COMPRAS
   ========================================================= */
//INICIO REPORTE DE COMPRAS
var listar_reporte_compras = function() {
    var tipo_compra_reporte = 1;

    if (
        $("#form_main_compras #tipo_compras_reporte").val() == null ||
        $("#form_main_compras #tipo_compras_reporte").val() == ""
    ) {
        tipo_compra_reporte = 1;
    } else {
        tipo_compra_reporte = $("#form_main_compras #tipo_compras_reporte").val();
    }

    var fechai = $("#form_main_compras #fechai").val();
    var fechaf = $("#form_main_compras #fechaf").val();

    if ($.fn.DataTable.isDataTable("#dataTablaReporteCompras")) {
        $("#dataTablaReporteCompras").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTablaReporteCompras();

    var table_reporteCompras = $("#dataTablaReporteCompras").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableReporteCompras.php",
            "data": {
                "tipo_compra_reporte": tipo_compra_reporte,
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

                                '<button type="button" class="dropdown-item accion-item table_reportes print_compras ocultar">' +
                                    '<span class="accion-icon accion-icon-success">' +
                                        '<i class="fas fa-file-download"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Factura</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar cancelar_compras ocultar">' +
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
                "data": "tipo_documento",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var icon = data === 'Crédito'
                            ? '<i class="fas fa-clock mr-1"></i>'
                            : '<i class="fas fa-check-circle mr-1"></i>';

                        var badgeClass = data === 'Crédito'
                            ? 'badge badge-pill badge-warning'
                            : 'badge badge-pill badge-success';

                        return '<span class="' + badgeClass + '" style="font-size:0.85rem; padding:0.45em 0.7em; font-weight:500;">' +
                            icon +
                            data +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                "data": "cuenta"
            },
            {
                "data": "proveedor"
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
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "isv",
                render: function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "descuento",
                render: function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            },
            {
                "data": "total",
                render: function(data, type) {
                    var valor = parseFloat(data || 0);
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(valor);

                    if (type === 'display') {
                        var color = valor < 0 ? 'red' : 'green';

                        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                            number +
                        '</span>';
                    }

                    return valor;
                }
            }
        ],
        "order": [[5, "desc"]],
        "orderFixed": {
            "pre": [[5, "desc"]]
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
                width: "9%",
                targets: 1
            },
            {
                width: "9%",
                targets: 2,
                className: "text-center text-nowrap"
            },
            {
                width: "12%",
                targets: 3
            },
            {
                width: "15%",
                targets: 4
            },
            {
                width: "12%",
                targets: 5,
                className: "text-center text-nowrap"
            },
            {
                width: "11%",
                targets: 6,
                className: "text-right text-nowrap"
            },
            {
                width: "10%",
                targets: 7,
                className: "text-right text-nowrap"
            },
            {
                width: "10%",
                targets: 8,
                className: "text-right text-nowrap"
            },
            {
                width: "12%",
                targets: 9,
                className: "text-right text-nowrap"
            }
        ],
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();

            function formatNumber(number) {
                number = parseFloat(number || 0);

                return 'L ' + number.toLocaleString('es-HN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            var subtotal = api.column(6, { page: 'current' }).data().reduce(function(a, b) {
                return a + (parseFloat(b) || 0);
            }, 0);

            var isv = api.column(7, { page: 'current' }).data().reduce(function(a, b) {
                return a + (parseFloat(b) || 0);
            }, 0);

            var descuento = api.column(8, { page: 'current' }).data().reduce(function(a, b) {
                return a + (parseFloat(b) || 0);
            }, 0);

            var total = api.column(9, { page: 'current' }).data().reduce(function(a, b) {
                return a + (parseFloat(b) || 0);
            }, 0);

            $('#subtotal-i').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatNumber(subtotal) +
                '</span>'
            );

            $('#impuesto-i').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatNumber(isv) +
                '</span>'
            );

            $('#descuento-i').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatNumber(descuento) +
                '</span>'
            );

            $('#total-footer-ingreso').html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatNumber(total) +
                '</span>'
            );
        },
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Compras',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_compras();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Compras',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
                }
            },
            {
                extend: 'pdf',
                footer: true,
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                pageSize: 'LETTER',
                title: 'Reporte de Compras',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
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

    table_reporteCompras.search('').draw();
    $('#buscar').focus();

    view_reporteCompras_dataTable("#dataTablaReporteCompras tbody", table_reporteCompras);
    view_anularCompras_dataTable("#dataTablaReporteCompras tbody", table_reporteCompras);
};

var view_anularCompras_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.cancelar_compras");
    $(tbody).on("click", "button.cancelar_compras", function (e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.compras_id) {
            showNotify('error', 'Error', 'No se pudo obtener la compra seleccionada');
            return false;
        }

        if (typeof validarAdminSistema !== 'function') {
            showNotify('error', 'Validación no disponible', 'No está cargado el JS de autenticación administrativa.');
            return false;
        }

        var compraId = data.compras_id;
        var numeroCompra = data.number || data.numero || data.compra || data.numero_compra || data.factura_compra || data.compras_id;

        validarAdminSistema(function (permitido) {
            if (permitido !== true) {
                return;
            }

            anularCompra(compraId);
        }, {
            mensaje: 'Para anular esta compra debe validar un administrador.',
            modulo: 'Compras',
            accion: 'Anular compra',
            referencia_id: compraId,
            referencia_texto: numeroCompra,
            motivo: 'Validación requerida para anular compra'
        });

        return false;
    });
};

var view_reporteCompras_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_compras");
    $(tbody).on("click", "button.print_compras", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printPurchase(data.compras_id);
    });
}

function anularCompra(compras_id) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea anular la factura de compra: # " + getNumeroCompra(compras_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Si, anular la factura de compra!",
            }
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
    }).then((willConfirm) => {
        if (willConfirm === true) {
            anular(compras_id);
        }
    });
}

function anular(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/anularCompra.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'compras_id=' + compras_id,
        success: function(data) {
            if (data == 1) {
                swal.close(); // Cierra el modal de SweetAlert
                showNotify('success', 'Success', 'La factura de compra ha sido anulada con éxito');
                listar_reporte_compras();
            } else {
                swal.close(); // Cierra el modal de SweetAlert
                showNotify('error', 'Error', 'La factura de compra no se pudo anular');
            }
        }
    });
}

function getReporteCompras() {
    var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_compras #tipo_compras_reporte').html("");
            $('#form_main_compras #tipo_compras_reporte').html(data);
            $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
        }
    });
}
//FIN REPORTE DE COMPRAS
</script>