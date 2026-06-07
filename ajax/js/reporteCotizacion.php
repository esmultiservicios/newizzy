<script>
//reporteCotizacio.php

$(() => {
    getReporteCotizacion();
    listar_reporte_cotizaciones();

    $('#form_main_cotizaciones #tipo_cotizacion_reporte').val(1);
    $('#form_main_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh');

    $('#form_main_cotizaciones #search').off('click.reporteCotizaciones');
    $('#form_main_cotizaciones #search').on('click.reporteCotizaciones', function(e) {
        e.preventDefault();
        listar_reporte_cotizaciones();
    });

    $('#form_main_cotizaciones').off('reset.reporteCotizaciones');
    $('#form_main_cotizaciones').on('reset.reporteCotizaciones', function() {
        $(this).find('.selectpicker').val('').selectpicker('refresh');

        setTimeout(function() {
            listar_reporte_cotizaciones();
        }, 100);
    });
});

/* =========================================================
   HELPERS - REPORTE DE COTIZACIONES
   ========================================================= */

function normalizarNumeroReporteCotizaciones(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return 0;
    }

    valor = String(valor)
        .replace(/L\./g, '')
        .replace(/L/g, '')
        .replace(/,/g, '')
        .trim();

    var numero = parseFloat(valor);

    return isNaN(numero) ? 0 : numero;
}

function moneyCellCotizaciones(data, type) {
    var valor = normalizarNumeroReporteCotizaciones(data);

    if (type !== 'display') {
        return valor;
    }

    var number = 'L ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    var color = valor < 0 ? 'red' : 'green';

    return '<span style="color:' + color + '; font-size: 1rem; font-weight: 600;">' + number + '</span>';
}

function moneyCellTotalCotizaciones(data, type) {
    var valor = normalizarNumeroReporteCotizaciones(data);

    if (type !== 'display') {
        return valor;
    }

    var numberFormatted = 'L ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    return `
        <div class="total-container" style="display:flex;flex-direction:column;align-items:flex-end;min-width:120px;">
            <div style="background:#fff;border-left:6px solid #28a745;padding:8px 12px;border-radius:.5rem;box-shadow:0 1px 5px rgba(0,0,0,.08);font-size:1.05rem;font-weight:bold;color:#212529;min-width:110px;text-align:right;">
                ${numberFormatted}
            </div>
        </div>`;
}

function formatMoneyReporteCotizaciones(valor) {
    valor = normalizarNumeroReporteCotizaciones(valor);

    return new Intl.NumberFormat('es-HN', {
        style: 'currency',
        currency: 'HNL',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
}

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
    try {
        var _dtKey = 'DataTables_' + 'dataTablaReporteCotizaciones' + '_' + window.location.pathname;
        localStorage.removeItem(_dtKey);
    } catch (e) {}

    var tipo_cotizacion_reporte = $("#form_main_cotizaciones #tipo_cotizacion_reporte").val();

    if (tipo_cotizacion_reporte == null || tipo_cotizacion_reporte === "") {
        tipo_cotizacion_reporte = 1;
    }

    var fechai = $("#form_main_cotizaciones #fechai").val();
    var fechaf = $("#form_main_cotizaciones #fechaf").val();

    if ($.fn.DataTable.isDataTable("#dataTablaReporteCotizaciones")) {
        $("#dataTablaReporteCotizaciones").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTablaReporteCotizaciones();

    var table_reporteCotizaciones = $("#dataTablaReporteCotizaciones").DataTable({
        destroy: true,
        footer: true,
        stateSave: false,
        orderMulti: false,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableReporteCotizaciones.php",
            data: {
                "tipo_cotizacion_reporte": tipo_cotizacion_reporte,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
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
                data: "fecha"
            },
            {
                data: "tipo_documento",
                render: function(data, type) {
                    if (type !== 'display') {
                        return data;
                    }

                    var texto = data || '';

                    var icon = texto === 'Crédito'
                        ? '<i class="fas fa-clock mr-1"></i>'
                        : '<i class="fas fa-check-circle mr-1"></i>';

                    var badgeClass = texto === 'Crédito'
                        ? 'badge badge-pill badge-warning'
                        : 'badge badge-pill badge-success';

                    return '<span class="' + badgeClass + '" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">' +
                        icon +
                        texto +
                    '</span>';
                }
            },
            {
                data: "cliente"
            },
            {
                data: "numero",
                render: function(data, type, row) {
                    if (type === 'sort') {
                        return parseInt(row.numero_ordenamiento, 10) || 0;
                    }

                    if (type !== 'display') {
                        return data;
                    }

                    return '<span style="font-size:1rem;font-weight:600;white-space:nowrap;">' + data + '</span>';
                }
            },
            {
                data: "subtotal",
                render: moneyCellCotizaciones
            },
            {
                data: "isv",
                render: moneyCellCotizaciones
            },
            {
                data: "descuento",
                render: moneyCellCotizaciones
            },
            {
                data: "total",
                render: moneyCellTotalCotizaciones
            }
        ],

        order: [[4, "desc"]],

        columnDefs: [
            {
                targets: 0,
                width: "8%",
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 1,
                width: "11%",
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 2,
                width: "10%",
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 3,
                width: "23%",
                className: "align-middle"
            },
            {
                targets: 4,
                width: "14%",
                className: "text-center text-nowrap align-middle",
                type: "num"
            },
            {
                targets: 5,
                width: "11%",
                className: "text-right text-nowrap align-middle"
            },
            {
                targets: 6,
                width: "10%",
                className: "text-right text-nowrap align-middle"
            },
            {
                targets: 7,
                width: "10%",
                className: "text-right text-nowrap align-middle"
            },
            {
                targets: 8,
                width: "13%",
                className: "text-right text-nowrap align-middle"
            }
        ],

        lengthMenu: lengthMenu10,
        bDestroy: true,
        language: idioma_español,
        dom: dom,

        footerCallback: function(row, data) {
            var totalSubtotal = data.reduce(function(acc, row) {
                return acc + normalizarNumeroReporteCotizaciones(row.subtotal);
            }, 0);

            var totalIsv = data.reduce(function(acc, row) {
                return acc + normalizarNumeroReporteCotizaciones(row.isv);
            }, 0);

            var totalDescuento = data.reduce(function(acc, row) {
                return acc + normalizarNumeroReporteCotizaciones(row.descuento);
            }, 0);

            var totalVentas = data.reduce(function(acc, row) {
                return acc + normalizarNumeroReporteCotizaciones(row.total);
            }, 0);

            $('#subtotal-i').html(formatMoneyReporteCotizaciones(totalSubtotal));
            $('#impuesto-i').html(formatMoneyReporteCotizaciones(totalIsv));
            $('#descuento-i').html(formatMoneyReporteCotizaciones(totalDescuento));
            $('#total-footer-ingreso').html(formatMoneyReporteCotizaciones(totalVentas));
        },

        buttons: [
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
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
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
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
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

        drawCallback: function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    table_reporteCotizaciones.search('').draw();
    $('#buscar').focus();

    view_reporteCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
    view_enviarCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
    view_anularCotizaciones_dataTable("#dataTablaReporteCotizaciones tbody", table_reporteCotizaciones);
};

/* =========================================================
   ACCIONES DATATABLE - REPORTE DE COTIZACIONES
   ========================================================= */

var view_anularCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_cotizaciones");
    $(tbody).on("click", "button.cancelar_cotizaciones", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.cotizacion_id) {
            showNotify('error', 'Error', 'No se pudo obtener la cotización seleccionada');
            return false;
        }

        anularCotizacion(data.cotizacion_id);
    });
};

var view_enviarCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.email_cotizacion");

    $(tbody).on("click", "button.email_cotizacion", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.cotizacion_id) {
            showNotify('error', 'Error', 'No se pudo obtener la cotización seleccionada');
            return false;
        }

        swal({
            title: "Enviar cotización",
            text: "¿Desea enviar esta cotización por correo electrónico?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true,
                    closeModal: true
                },
                confirm: {
                    text: "Sí, enviar",
                    closeModal: true
                }
            },
            dangerMode: false,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmado) => {
            if (!confirmado) {
                return false;
            }

            mailQuote(data.cotizacion_id);
        });
    });
};

var view_reporteCotizaciones_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_cotizaciones");
    $(tbody).on("click", "button.print_cotizaciones", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.cotizacion_id) {
            showNotify('error', 'Error', 'No se pudo obtener la cotización seleccionada');
            return false;
        }

        printQuote(data.cotizacion_id);
    });
};

/* =========================================================
   ANULAR COTIZACIÓN
   ========================================================= */

function anularCotizacion(cotizacion_id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea anular la cotización: # " + getNumeroCotizacion(cotizacion_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, anular la cotización!",
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
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
        async: true,
        data: {
            cotizacion_id: cotizacion_id
        },
        success: function(data) {
            swal.close();

            if (data == 1) {
                showNotify('success', 'Success', 'La cotización ha sido anulada con éxito');
                listar_reporte_cotizaciones();
            } else {
                showNotify('error', 'Error', 'La cotización no se pudo anular');
            }
        },
        error: function(xhr) {
            swal.close();
            showNotify('error', 'Error', xhr.responseText || 'Hubo un problema al anular la cotización');
        }
    });
}

/* =========================================================
   COMBOS - REPORTE DE COTIZACIONES
   ========================================================= */

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
        },
        error: function() {
            showNotify('error', 'Error', 'No se pudo cargar el tipo de cotización');
        }
    });
}
//FIN REPORTE DE COTIZACIONES
</script>