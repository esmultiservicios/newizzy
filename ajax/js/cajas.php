<script>
//caja.php
$(() => {
    $("#formMainCajas #estado_cajas").val(0);
    $('#formMainCajas #estado_cajas').selectpicker('refresh');

    listar_registro_cajas();

    $('#formMainCajas #search').on("click", function (e) {
        e.preventDefault();
        listar_registro_cajas();
    });

    $('#btnGananciaPeriodo').on("click", function () {
        cargarDesgloseGananciaCaja(0, 'periodo');
    });

    $('#btnRetirosPeriodo').on("click", function () {
        cargarDetalleRetirosCaja(0, 'periodo');
    });

    $('#btnCuadreDia').on("click", function () {
        cargarCuadreDiaCaja(0, 'periodo');
    });

    $('#btnActualizarCuadreDia').on("click", function () {
        refrescarCuadreDiaCaja();
    });

    $('#btnImprimirCuadreDia').on("click", function () {
        imprimirCuadreDiaCaja();
    });

    $('#formMainCajas').on('reset', function () {
        $('#formMainCajas .selectpicker').val('').selectpicker('refresh');

        setTimeout(function () {
            $("#formMainCajas #estado_cajas").val(0);
            $('#formMainCajas #estado_cajas').selectpicker('refresh');

            var hoy = new Date().toISOString().split('T')[0];
            $("#formMainCajas #fecha_cajas").val(hoy);
            $("#formMainCajas #fecha_cajas_f").val(hoy);

            listar_registro_cajas();
        }, 100);
    });
});

$('#formMainCajas #estado_cajas').on("change", function () {
    listar_registro_cajas();
});

$('#formMainCajas #fecha_cajas').on("change", function () {
    listar_registro_cajas();
});

$('#formMainCajas #fecha_cajas_f').on("change", function () {
    listar_registro_cajas();
});

function parseMonto(valor) {
    if (typeof valor === 'string') {
        valor = valor.replace(/L\./g, '').replace(/,/g, '').trim();
    }

    valor = parseFloat(valor || 0);
    return isNaN(valor) ? 0 : valor;
}

function formatoMoneda(valor) {
    valor = parseMonto(valor);

    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderMonedaColor(data, type) {
    var valor = parseMonto(data);
    var number = formatoMoneda(valor);

    if (type === 'display') {
        var color = valor < 0 ? '#dc2626' : '#008000';
        return '<span style="color:' + color + '; font-weight:700; white-space:nowrap;">' + number + '</span>';
    }

    return valor;
}

function esCajaActiva(row) {
    return row && row.caja && String(row.caja).toLowerCase() === 'activa';
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - REGISTRO DE CAJAS
   ========================================================= */
function construirHeaderFooterDataTableCajas() {
    var $tabla = $("#dataTableCajas");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Usuario</th>' +
                '<th>Factura Inicial</th>' +
                '<th>Factura Final</th>' +
                '<th>Monto Apertura</th>' +
                '<th>Venta del Día</th>' +
                '<th>Retiro Caja</th>' +
                '<th>Neto Caja</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right">Totales:</th>' +
                '<th id="total_monto_apertura">L. 0.00</th>' +
                '<th id="total_venta_dia">L. 0.00</th>' +
                '<th id="total_retiro_caja">L. 0.00</th>' +
                '<th id="total_neto">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTADO REGISTRO DE CAJAS
   ========================================================= */
var listar_registro_cajas = function () {
    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();
    var estado = $("#formMainCajas #estado_cajas").val();

    if ($.fn.DataTable.isDataTable("#dataTableCajas")) {
        $("#dataTableCajas").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableCajas();

    var table_registro_cajas = $("#dataTableCajas").DataTable({
        destroy: true,
        autoWidth: false,
        responsive: false,
        stateSave: true,
        bDestroy: true,
        language: idioma_español,
        lengthMenu: lengthMenu,
        dom: dom,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableCajaDisponibles.php",
            dataType: "json",
            data: {
                fechai: fechai,
                fechaf: fechaf,
                estado: estado
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    var activa = esCajaActiva(row);

                    var badgeEstado = activa
                        ? '<span class="badge-estado-caja badge-caja-abierta"><i class="fas fa-circle"></i> Abierta</span>'
                        : '<span class="badge-estado-caja badge-caja-cerrada"><i class="fas fa-lock"></i> Cerrada</span>';

                    var accionesCaja = "";

                    if (activa) {
                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-cerrar table_crear table_cerrar_caja">' +
                                '<span class="accion-icon accion-icon-success">' +
                                    '<i class="fas fa-lock"></i>' +
                                '</span>' +
                                '<span class="accion-label">Cerrar caja</span>' +
                            '</button>';

                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-retiro table_retiro_caja">' +
                                '<span class="accion-icon accion-icon-warning">' +
                                    '<i class="fas fa-money-bill-wave"></i>' +
                                '</span>' +
                                '<span class="accion-label">Retirar dinero</span>' +
                            '</button>';
                    } else {
                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-cerrada" disabled>' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-lock"></i>' +
                                '</span>' +
                                '<span class="accion-label">Caja cerrada</span>' +
                            '</button>';

                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-no-retiro" disabled>' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-ban"></i>' +
                                '</span>' +
                                '<span class="accion-label">Retiro no disponible</span>' +
                            '</button>';
                    }

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-comprobante table_reportes table_comprobante_caja">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="far fa-file-pdf"></i>' +
                            '</span>' +
                            '<span class="accion-label">Comprobante</span>' +
                        '</button>';

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-retiros-detalle table_detalle_retiros_caja">' +
                            '<span class="accion-icon accion-icon-warning">' +
                                '<i class="fas fa-list-ul"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver retiros</span>' +
                        '</button>';

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-ganancia table_ganancia">' +
                            '<span class="accion-icon accion-icon-primary">' +
                                '<i class="fas fa-chart-line"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver ganancia</span>' +
                        '</button>';

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-cuadre-dia table_cuadre_dia">' +
                            '<span class="accion-icon accion-icon-success">' +
                                '<i class="fas fa-balance-scale"></i>' +
                            '</span>' +
                            '<span class="accion-label">Cuadre del día</span>' +
                        '</button>';

                    return '' +
                        '<div class="acciones-caja-wrap">' +
                            '<div class="dropdown acciones-dropdown">' +
                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +

                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                    accionesCaja +
                                '</div>' +
                            '</div>' +
                            badgeEstado +
                        '</div>';
                }
            },
            { data: "fecha" },
            { data: "usuario" },
            { data: "factura_inicial" },
            { data: "factura_final" },
            {
                data: "monto_apertura",
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            },
            {
                data: "importe_venta",
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            },
            {
                data: "retiro_caja",
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            },
            {
                data: "neto",
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            }
        ],

        columnDefs: [
            {
                targets: [5, 6, 7, 8],
                className: "text-right text-nowrap"
            },
            {
                targets: [0, 1, 3, 4],
                className: "text-center text-nowrap"
            }
        ],

        createdRow: function (row, data) {
            if (esCajaActiva(data)) {
                $(row).addClass("fila-caja-abierta");
            } else {
                $(row).addClass("fila-caja-cerrada");
            }
        },

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: "Actualizar Registro de Cajas",
                className: "table_actualizar btn btn-secondary ocultar",
                action: function () {
                    listar_registro_cajas();
                }
            },
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: "Excel",
                title: "Reporte Registro de Cajas",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-success ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                extend: "pdf",
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: "PDF",
                orientation: "landscape",
                title: "Reporte Registro de Cajas",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-danger ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function (doc) {
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

        footerCallback: function () {
            var api = this.api();

            var totalMontoApertura = api.column(5, { page: "current" }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalVentaDia = api.column(6, { page: "current" }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalRetiroCaja = api.column(7, { page: "current" }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalNeto = api.column(8, { page: "current" }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            $("#total_monto_apertura").html("<span>" + formatoMoneda(totalMontoApertura) + "</span>");
            $("#total_venta_dia").html("<span>" + formatoMoneda(totalVentaDia) + "</span>");
            $("#total_retiro_caja").html("<span>" + formatoMoneda(totalRetiroCaja) + "</span>");
            $("#total_neto").html("<span>" + formatoMoneda(totalNeto) + "</span>");
        },

        drawCallback: function () {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            $('[title]').tooltip({
                container: "body",
                placement: "top"
            });
        }
    });

    table_registro_cajas.search("").draw();

    comprobante_cajas_dataTable("#dataTableCajas tbody", table_registro_cajas);
    cerrar_registro_cajas_dataTable("#dataTableCajas tbody", table_registro_cajas);
    desglose_ganancia_caja_dataTable("#dataTableCajas tbody", table_registro_cajas);
    retiro_caja_dataTable("#dataTableCajas tbody", table_registro_cajas);
    detalle_retiros_caja_dataTable("#dataTableCajas tbody", table_registro_cajas);
    cuadre_dia_caja_dataTable("#dataTableCajas tbody", table_registro_cajas);
};

var comprobante_cajas_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_crear");

    $(tbody).on("click", "button.table_crear", function () {
        var data = table.row($(this).parents("tr")).data();

        if (!esCajaActiva(data)) {
            showNotify('error', 'Error', 'Esta caja ya está cerrada. No se puede cerrar nuevamente.');
            return;
        }

        var url = '<?php echo SERVERURL;?>core/editarCajas.php';

        $('#formAperturaCaja #apertura_id').val(data.apertura_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formAperturaCaja').serialize(),
            success: function (registro) {
                var valores = eval(registro);

                $('#formAperturaCaja').attr({ 'data-form': 'update' });
                $('#formAperturaCaja').attr({ 'action': '<?php echo SERVERURL;?>ajax/addCierreCajaAjax.php' });
                $('#formAperturaCaja')[0].reset();

                $('#open_caja').hide();
                $('#close_caja').show();

                $('#formAperturaCaja #usuario_apertura').val(valores[0]);
                $('#formAperturaCaja #monto_apertura').val(valores[1]);
                $('#formAperturaCaja #fecha_apertura').val(valores[2]);
                $('#formAperturaCaja #colaboradores_id_apertura').val(valores[3]);

                $('#formAperturaCaja #usuario_apertura').attr('readonly', true);
                $('#formAperturaCaja #monto_apertura').attr('readonly', true);
                $('#formAperturaCaja #fecha_apertura').attr('readonly', true);

                $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");

                $('#modal_apertura_caja').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var cerrar_registro_cajas_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_reportes");

    $(tbody).on("click", "button.table_reportes", function () {
        var data = table.row($(this).parents("tr")).data();
        printComprobanteCajas(data.apertura_id);
    });
};

var desglose_ganancia_caja_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_ganancia");

    $(tbody).on("click", "button.table_ganancia", function () {
        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.apertura_id) {
            showNotify('error', 'Error', 'No se encontró el código de apertura de caja.');
            return;
        }

        cargarDesgloseGananciaCaja(data.apertura_id, 'caja');
    });
};

var retiro_caja_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_retiro_caja");

    $(tbody).on("click", "button.table_retiro_caja", function () {
        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.apertura_id) {
            showNotify('error', 'Error', 'No se encontró la apertura de caja.');
            return;
        }

        if (!esCajaActiva(data)) {
            showNotify('error', 'Error', 'Solo puede retirar dinero de una caja activa.');
            return;
        }

        abrirModalRetiroCaja(data.apertura_id);
    });
};

function abrirModalRetiroCaja(apertura_id) {
    apertura_id = parseInt(apertura_id || 0);

    if (apertura_id <= 0) {
        showNotify('error', 'Error', 'No se encontró la apertura de caja.');
        return;
    }

    $('#formRetiroCaja')[0].reset();

    $('#retiro_apertura_id').val(apertura_id);
    $('#retiro_saldo_actual').val('0.00');
    $('#retiro_saldo_final').val('0.00');
    $('#retiro_saldo_efectivo').val('0.00');
    $('#retiro_saldo_transferencia').val('0.00');
    $('#retiro_saldo_final_efectivo').val('0.00');
    $('#retiro_saldo_final_transferencia').val('0.00');
    $('#retiro_monto').val('0.00');

    $('#retiro_saldo_efectivo_text').html('Cargando...');
    $('#retiro_saldo_transferencia_text').html('Cargando...');
    $('#retiro_saldo_actual_text').html('Cargando...');
    $('#retiro_max_efectivo_text').html('Cargando...');
    $('#retiro_max_transferencia_text').html('Cargando...');
    $('#retiro_total_retirar_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_efectivo_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_transferencia_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_text').html(formatoMoneda(0));

    $('#retiro_box_efectivo').removeClass('retiro-input-error');
    $('#retiro_box_transferencia').removeClass('retiro-input-error');

    $('#retiro_monto_efectivo').val('').prop('disabled', true);
    $('#retiro_monto_transferencia').val('').prop('disabled', true);
    $('#btn_guardar_retiro_caja').prop('disabled', true);

    if ($.fn.selectpicker) {
        $('#retiro_categoria_gastos_id').selectpicker('val', '');
        $('#retiro_categoria_gastos_id').selectpicker('refresh');
    }

    cargarSaldoRetiroCaja(function () {
        $('#retiro_monto_efectivo').prop('disabled', false);
        $('#retiro_monto_transferencia').prop('disabled', false);
        validarRetiroCaja(false);
    });

    $('#modalRetiroCaja')
        .off('shown.bs.modal')
        .on('shown.bs.modal', function () {
            setTimeout(function () {
                $('#retiro_monto_efectivo').trigger('focus').select();
            }, 150);
        });

    $('#modalRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cargarSaldoRetiroCaja(callback) {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getSaldoRetiroCaja.php',
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo obtener el saldo disponible para retiro.');
                $('#modalRetiroCaja').modal('hide');
                return;
            }

            var saldoEfectivo = parseMonto(response.saldo_efectivo);
            var saldoTransferencia = parseMonto(response.saldo_transferencia);
            var saldoTotal = parseMonto(response.saldo_disponible);

            $('#retiro_apertura_id').val(response.apertura_id || $('#retiro_apertura_id').val());
            $('#retiro_saldo_efectivo').val(saldoEfectivo.toFixed(2));
            $('#retiro_saldo_transferencia').val(saldoTransferencia.toFixed(2));
            $('#retiro_saldo_actual').val(saldoTotal.toFixed(2));

            $('#retiro_saldo_efectivo_text').html(formatoMoneda(saldoEfectivo));
            $('#retiro_saldo_transferencia_text').html(formatoMoneda(saldoTransferencia));
            $('#retiro_saldo_actual_text').html(formatoMoneda(saldoTotal));
            $('#retiro_max_efectivo_text').html(formatoMoneda(saldoEfectivo));
            $('#retiro_max_transferencia_text').html(formatoMoneda(saldoTransferencia));

            $('#retiro_monto_efectivo').attr('max', saldoEfectivo.toFixed(2));
            $('#retiro_monto_transferencia').attr('max', saldoTransferencia.toFixed(2));

            actualizarResumenRetiroCaja();

            if (typeof callback === 'function') {
                callback(response);
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al obtener el saldo disponible.');
            $('#modalRetiroCaja').modal('hide');
        }
    });
}

function actualizarResumenRetiroCaja() {
    var saldoEfectivo = parseMonto($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMonto($('#retiro_saldo_transferencia').val());
    var montoEfectivo = parseMonto($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMonto($('#retiro_monto_transferencia').val());

    var totalRetirar = montoEfectivo + montoTransferencia;
    var saldoFinalEfectivo = saldoEfectivo - montoEfectivo;
    var saldoFinalTransferencia = saldoTransferencia - montoTransferencia;

    if (saldoFinalEfectivo < 0) {
        saldoFinalEfectivo = 0;
    }

    if (saldoFinalTransferencia < 0) {
        saldoFinalTransferencia = 0;
    }

    var saldoFinalTotal = saldoFinalEfectivo + saldoFinalTransferencia;

    $('#retiro_monto').val(totalRetirar.toFixed(2));
    $('#retiro_saldo_final_efectivo').val(saldoFinalEfectivo.toFixed(2));
    $('#retiro_saldo_final_transferencia').val(saldoFinalTransferencia.toFixed(2));
    $('#retiro_saldo_final').val(saldoFinalTotal.toFixed(2));

    $('#retiro_total_retirar_text').html(formatoMoneda(totalRetirar));
    $('#retiro_saldo_final_efectivo_text').html(formatoMoneda(saldoFinalEfectivo));
    $('#retiro_saldo_final_transferencia_text').html(formatoMoneda(saldoFinalTransferencia));
    $('#retiro_saldo_final_text').html(formatoMoneda(saldoFinalTotal));
}

function validarRetiroCaja(mostrarMensaje) {
    var saldoEfectivo = parseMonto($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMonto($('#retiro_saldo_transferencia').val());
    var montoEfectivo = parseMonto($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMonto($('#retiro_monto_transferencia').val());
    var categoria = parseInt($('#retiro_categoria_gastos_id').val() || 0);
    var errores = [];

    $('#retiro_box_efectivo').removeClass('retiro-input-error');
    $('#retiro_box_transferencia').removeClass('retiro-input-error');

    if (montoEfectivo < 0 || montoTransferencia < 0) {
        errores.push('Los montos no pueden ser negativos.');
    }

    if (montoEfectivo <= 0 && montoTransferencia <= 0) {
        errores.push('Ingrese un monto en efectivo, transferencia o ambos.');
    }

    if (montoEfectivo > saldoEfectivo) {
        errores.push('El retiro de efectivo no puede ser mayor al efectivo disponible.');
        $('#retiro_box_efectivo').addClass('retiro-input-error');
    }

    if (montoTransferencia > saldoTransferencia) {
        errores.push('El retiro de transferencia no puede ser mayor al saldo disponible por transferencia.');
        $('#retiro_box_transferencia').addClass('retiro-input-error');
    }

    if (categoria <= 0) {
        errores.push('Seleccione la categoría del retiro.');
    }

    actualizarResumenRetiroCaja();

    if (errores.length > 0) {
        $('#btn_guardar_retiro_caja').prop('disabled', false);

        if (mostrarMensaje === true) {
            showNotify('error', 'Error', errores[0]);
        }

        return false;
    }

    $('#btn_guardar_retiro_caja').prop('disabled', false);
    return true;
}

$('#retiro_monto_efectivo, #retiro_monto_transferencia').off('input change keyup').on('input change keyup', function () {
    validarRetiroCaja(false);
});

$('#retiro_categoria_gastos_id').off('change').on('change', function () {
    validarRetiroCaja(false);
});

$('#btn_guardar_retiro_caja').off('click.validacionRetiroCaja').on('click.validacionRetiroCaja', function (e) {
    if (!validarRetiroCaja(true)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }
});

$('#formRetiroCaja').off('submit.validacionRetiroCaja').on('submit.validacionRetiroCaja', function (e) {
    if (!validarRetiroCaja(true)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }

    return true;
});

var detalle_retiros_caja_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_detalle_retiros_caja");

    $(tbody).on("click", "button.table_detalle_retiros_caja", function () {
        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.apertura_id) {
            showNotify('error', 'Error', 'No se encontró la apertura de caja.');
            return;
        }

        cargarDetalleRetirosCaja(data.apertura_id, 'caja');
    });
};

var cuadre_dia_caja_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.table_cuadre_dia");

    $(tbody).on("click", "button.table_cuadre_dia", function () {
        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.apertura_id) {
            showNotify('error', 'Error', 'No se encontró la apertura de caja.');
            return;
        }

        cargarCuadreDiaCaja(data.apertura_id, 'caja');
    });
};

/* =========================================================
   CUADRE DEL DÍA / PERÍODO
   ========================================================= */
function setTextoCuadreDia(selector, valor) {
    $(selector).html(formatoMoneda(valor));
}

function cargarCuadreDiaCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = $('#modalCuadreDiaCaja').data('modo') || 'periodo';
    }

    if (modo === 'caja' && apertura_id <= 0) {
        showNotify('error', 'Error', 'No se recibió una apertura válida.');
        return;
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $('#modalCuadreDiaCaja').data('modo', modo);
    $('#modalCuadreDiaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        $('#cd_contexto_caja').html('Desde ' + fechai + ' hasta ' + fechaf);
    } else {
        $('#cd_contexto_caja').html('Apertura de caja #' + apertura_id);
    }

    $('#cd_tabla_gastos tbody').html('<tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>');
    $('#cd_tabla_inversiones tbody').html('<tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>');

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getCuadreDiaCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo cargar el cuadre del día.');
                return;
            }

            var resumen = response.resumen || {};
            var gastos = response.gastos || [];
            var inversiones = response.inversiones || [];

            renderCuadreDiaCaja(resumen, gastos, inversiones);

            $('#modalCuadreDiaCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar el cuadre del día.');
        }
    });
}

function refrescarCuadreDiaCaja() {
    var apertura_id = parseInt($('#modalCuadreDiaCaja').data('apertura_id') || 0);
    var modo = $('#modalCuadreDiaCaja').data('modo') || 'periodo';

    cargarCuadreDiaCaja(apertura_id, modo);
}

function renderCuadreDiaCaja(resumen, gastos, inversiones) {
    setTextoCuadreDia('#cd_total_cobrado', resumen.total_cobrado || 0);
    setTextoCuadreDia('#cd_inversion_reposicion', resumen.inversion_total_considerada || resumen.inversion_reposicion || 0);
    setTextoCuadreDia('#cd_gastos_total', resumen.gastos_total || 0);
    setTextoCuadreDia('#cd_total_final_esperado', resumen.total_final_esperado || 0);
    setTextoCuadreDia('#cd_total_final_esperado_tabla', resumen.total_final_esperado || 0);

    setTextoCuadreDia('#cd_efectivo', resumen.efectivo || 0);
    setTextoCuadreDia('#cd_transferencia', resumen.transferencia || 0);
    setTextoCuadreDia('#cd_tarjeta', resumen.tarjeta || 0);
    setTextoCuadreDia('#cd_cheque', resumen.cheque || 0);
    setTextoCuadreDia('#cd_monto_apertura', resumen.monto_apertura || 0);

    setTextoCuadreDia('#cd_efectivo_esperado', resumen.efectivo_esperado || 0);
    setTextoCuadreDia('#cd_transferencia_esperada', resumen.transferencia_esperada || 0);
    setTextoCuadreDia('#cd_tarjeta_esperada', resumen.tarjeta_esperada || 0);
    setTextoCuadreDia('#cd_cheque_esperado', resumen.cheque_esperado || 0);

    setTextoCuadreDia('#cd_formula_efectivo', resumen.efectivo || 0);
    setTextoCuadreDia('#cd_formula_apertura', resumen.monto_apertura || 0);
    setTextoCuadreDia('#cd_formula_inversion', resumen.inversion_total_considerada || resumen.inversion_reposicion || 0);
    setTextoCuadreDia('#cd_formula_gastos_efectivo', resumen.gastos_efectivo || 0);
    setTextoCuadreDia('#cd_formula_resultado', resumen.efectivo_esperado || 0);

    var htmlGastos = '';

    if (!gastos || gastos.length <= 0) {
        htmlGastos = '<tr><td colspan="3" class="text-center text-muted">No hay gastos o retiros registrados.</td></tr>';
    } else {
        gastos.forEach(function (item) {
            htmlGastos += '' +
                '<tr>' +
                    '<td>' + (item.tipo || '') + '</td>' +
                    '<td>' + (item.cuenta || '') + '</td>' +
                    '<td class="text-right font-weight-bold">' + formatoMoneda(item.monto || 0) + '</td>' +
                '</tr>';
        });
    }

    $('#cd_tabla_gastos tbody').html(htmlGastos);

    var htmlInversiones = '';

    if (!inversiones || inversiones.length <= 0) {
        htmlInversiones = '<tr><td colspan="3" class="text-center text-muted">No hay inversión manual registrada. Se toma el costo de productos vendidos como inversión/reposición.</td></tr>';
    } else {
        inversiones.forEach(function (item) {
            htmlInversiones += '' +
                '<tr>' +
                    '<td>' + (item.tipo || '') + '</td>' +
                    '<td>' + (item.cuenta || '') + '</td>' +
                    '<td class="text-right font-weight-bold">' + formatoMoneda(item.monto || 0) + '</td>' +
                '</tr>';
        });
    }

    $('#cd_tabla_inversiones tbody').html(htmlInversiones);
}

function imprimirCuadreDiaCaja() {
    var contenido = document.getElementById('cd_ticket_area');

    if (!contenido) {
        showNotify('error', 'Error', 'No se encontró el contenido del cuadre para imprimir.');
        return;
    }

    var ventana = window.open('', '_blank', 'width=900,height=700');

    if (!ventana) {
        showNotify('error', 'Error', 'El navegador bloqueó la ventana de impresión.');
        return;
    }

    ventana.document.write('<html><head><title>Cuadre del Día</title>');
    ventana.document.write('<style>');
    ventana.document.write('body{font-family:Arial,sans-serif;font-size:12px;color:#111;}');
    ventana.document.write('.alert{border:1px solid #9ec5fe;padding:8px;margin-bottom:10px;}');
    ventana.document.write('.row{display:block;}');
    ventana.document.write('.card{border:1px solid #ddd;margin-bottom:10px;}');
    ventana.document.write('.card-header{font-weight:bold;background:#f3f4f6;padding:6px;}');
    ventana.document.write('.card-body{padding:8px;}');
    ventana.document.write('table{width:100%;border-collapse:collapse;margin-bottom:8px;}');
    ventana.document.write('th,td{border:1px solid #ddd;padding:5px;}');
    ventana.document.write('.text-right{text-align:right;} .font-weight-bold{font-weight:bold;} .text-success{color:#198754;} .text-primary{color:#0d6efd;} .text-danger{color:#dc3545;} .text-warning{color:#b7791f;}');
    ventana.document.write('@media print{button{display:none;}}');
    ventana.document.write('</style>');
    ventana.document.write('</head><body>');
    ventana.document.write('<h3>Cuadre del Día</h3>');
    ventana.document.write('<p>' + ($('#cd_contexto_caja').text() || '') + '</p>');
    ventana.document.write(contenido.innerHTML);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();

    setTimeout(function () {
        ventana.print();
    }, 300);
}

/* =========================================================
   DETALLE RETIROS CAJA
   ========================================================= */
function cargarDetalleRetirosCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';
    }

    if (modo === 'caja' && apertura_id <= 0) {
        showNotify('error', 'Error', 'No se recibió una apertura válida.');
        return;
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $('#dr_apertura_id').val(apertura_id);
    $('#modalDetalleRetirosCaja').data('modo', modo);
    $('#modalDetalleRetirosCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        $('#dr_contexto_caja').html('Desde ' + fechai + ' hasta ' + fechaf);
    } else {
        $('#dr_contexto_caja').html('Apertura de caja #' + apertura_id);
    }

    $('#dr_total_retiros').html('Cargando...');
    $('#dr_estado_caja').html('Cargando...');
    $('#dr_accion_permitida').html('Cargando...');

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getRetirosCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo cargar el detalle de retiros.');
                return;
            }

            var resumen = response.resumen || {};
            var detalles = response.data || [];

            $('#dr_total_retiros').html(formatoMoneda(resumen.total_retiros || 0));

            if (modo === 'periodo') {
                $('#dr_estado_caja').html('<span class="badge badge-info">Período</span>');
                $('#dr_accion_permitida').html('<span class="badge badge-warning">Depende de cada caja</span>');
            } else {
                if (parseInt(resumen.estado_caja || 0) === 1) {
                    $('#dr_estado_caja').html('<span class="badge badge-success">Abierta</span>');
                    $('#dr_accion_permitida').html('<span class="badge badge-success">Puede reintegrar</span>');
                } else {
                    $('#dr_estado_caja').html('<span class="badge badge-secondary">Cerrada</span>');
                    $('#dr_accion_permitida').html('<span class="badge badge-danger">No puede reintegrar</span>');
                }
            }

            cargarTablaDetalleRetirosCaja(detalles);

            $('#modalDetalleRetirosCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar los retiros de caja.');
        }
    });
}

function refrescarDetalleRetirosCaja() {
    var apertura_id = parseInt($('#modalDetalleRetirosCaja').data('apertura_id') || $('#dr_apertura_id').val() || 0);
    var modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';

    cargarDetalleRetirosCaja(apertura_id, modo);
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLE RETIROS CAJA
   ========================================================= */
function construirHeaderFooterDetalleRetirosCaja() {
    var $tabla = $('#dataTableDetalleRetirosCaja');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Caja</th>' +
                '<th>Fecha</th>' +
                '<th>Motivo</th>' +
                '<th>Observación</th>' +
                '<th>Cuenta</th>' +
                '<th>Egreso</th>' +
                '<th>Monto</th>' +
                '<th>Estado</th>' +
                '<th>Registrado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="7" class="text-right">Total activo:</th>' +
                '<th id="dr_footer_total">L. 0.00</th>' +
                '<th colspan="2"></th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   DATATABLE - DETALLE RETIROS CAJA
   ========================================================= */
function cargarTablaDetalleRetirosCaja(detalles) {
    if ($.fn.DataTable.isDataTable('#dataTableDetalleRetirosCaja')) {
        $('#dataTableDetalleRetirosCaja').DataTable().clear().destroy();
    }

    construirHeaderFooterDetalleRetirosCaja();

    $('#dataTableDetalleRetirosCaja').DataTable({
        destroy: true,
        autoWidth: false,
        data: detalles,
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }

                    if (parseInt(row.puede_reintegrar || 0) === 1) {
                        return '' +
                            '<button type="button" class="btn btn-sm btn-success btn-reintegrar-retiro" ' +
                                'data-caja-retiros-id="' + row.caja_retiros_id + '" ' +
                                'data-apertura-id="' + row.apertura_id + '" ' +
                                'data-monto="' + row.monto + '">' +
                                '<i class="fas fa-undo-alt"></i> Reintegrar' +
                            '</button>';
                    }

                    return '<span class="badge badge-secondary">No disponible</span>';
                }
            },
            {
                data: "apertura_id",
                className: "text-center text-nowrap",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    var estadoCaja = parseInt(row.estado_caja || 0) === 1
                        ? '<span class="badge badge-success ml-1">Abierta</span>'
                        : '<span class="badge badge-secondary ml-1">Cerrada</span>';

                    return '#' + data + ' ' + estadoCaja;
                }
            },
            { data: "fecha" },
            { data: "motivo" },
            { data: "observacion" },
            { data: "cuenta" },
            { data: "factura_egreso" },
            {
                data: "monto",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "estado_label",
                className: "text-center",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    if (parseInt(row.estado || 0) === 1) {
                        return '<span class="badge badge-success">Activo</span>';
                    }

                    return '<span class="badge badge-danger">Anulado</span>';
                }
            },
            { data: "fecha_registro" }
        ],
        columnDefs: [
            {
                targets: [7],
                className: "text-right text-nowrap"
            },
            {
                targets: [0, 1, 2, 6, 8, 9],
                className: "text-center text-nowrap"
            }
        ],
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Detalle de Retiros de Caja',
                className: 'btn btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Detalle de Retiros de Caja',
                className: 'btn btn-danger'
            }
        ],
        footerCallback: function () {
            var api = this.api();

            var total = api.rows({ page: 'current' }).data().reduce(function (acum, row) {
                if (parseInt(row.estado || 0) === 1) {
                    return acum + parseMonto(row.monto);
                }

                return acum;
            }, 0);

            $('#dr_footer_total').html('<span>' + formatoMoneda(total) + '</span>');
        },
        drawCallback: function () {
            $('.btn-reintegrar-retiro').off('click').on('click', function () {
                abrirModalReintegroRetiroCaja(
                    $(this).data('caja-retiros-id'),
                    $(this).data('apertura-id'),
                    $(this).data('monto')
                );
            });
        }
    });
}

/* =========================================================
   REINTEGRO DE RETIROS
   ========================================================= */
function abrirModalReintegroRetiroCaja(caja_retiros_id, apertura_id, monto) {
    caja_retiros_id = parseInt(caja_retiros_id || 0);
    apertura_id = parseInt(apertura_id || 0);
    monto = parseMonto(monto);

    if (caja_retiros_id <= 0 || apertura_id <= 0 || monto <= 0) {
        showNotify('error', 'Error', 'No se pudo cargar la información del retiro.');
        return;
    }

    $('#formReintegroRetiroCaja')[0].reset();

    $('#reintegro_caja_retiros_id').val(caja_retiros_id);
    $('#reintegro_apertura_id').val(apertura_id);
    $('#reintegro_monto_actual').val(monto.toFixed(2));
    $('#reintegro_monto_actual_text').html(formatoMoneda(monto));

    $('#reintegro_monto')
        .attr('max', monto.toFixed(2))
        .val('')
        .prop('readonly', false)
        .prop('disabled', false);

    $('#modalReintegroRetiroCaja')
        .off('shown.bs.modal')
        .on('shown.bs.modal', function () {
            setTimeout(function () {
                $('#reintegro_monto').trigger('focus').select();
            }, 150);
        });

    $('#modalReintegroRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formReintegroRetiroCaja').off('submit').on('submit', function (e) {
    e.preventDefault();

    var apertura_id = parseInt($('#reintegro_apertura_id').val() || 0);
    var montoActual = parseMonto($('#reintegro_monto_actual').val());
    var montoReintegro = parseMonto($('#reintegro_monto').val());

    if (montoReintegro <= 0) {
        showNotify('error', 'Error', 'Ingrese un monto válido para reintegrar.');
        return;
    }

    if (montoReintegro > montoActual) {
        showNotify('error', 'Error', 'El monto a reintegrar no puede ser mayor al retiro actual.');
        return;
    }

    $('#btnGuardarReintegroRetiroCaja').prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/reintegrarRetiroCaja.php',
        dataType: 'json',
        data: $('#formReintegroRetiroCaja').serialize(),
        success: function (response) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);

            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo realizar el reintegro.');
                return;
            }

            $('#modalReintegroRetiroCaja').modal('hide');

            showNotify('success', 'Éxito', response.message || 'Reintegro registrado correctamente.');

            refrescarDetalleRetirosCaja();
            listar_registro_cajas();

            if ($('#modalDesgloseGananciaCaja').hasClass('show')) {
                refrescarDesgloseGananciaCaja();
            }

            if ($('#modalCuadreDiaCaja').hasClass('show')) {
                refrescarCuadreDiaCaja();
            }
        },
        error: function (xhr) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al registrar el reintegro.');
        }
    });
});

/* =========================================================
   DESGLOSE GANANCIA CAJA
   ========================================================= */
   function cargarDesgloseGananciaCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = 'caja';
    }

    $('#dg_apertura_id').val(apertura_id);
    $('#dg_modo').val(modo);
    $('#modalDesgloseGananciaCaja').data('modo', modo);
    $('#modalDesgloseGananciaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getDesgloseGananciaCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        beforeSend: function () {
            if (modo === 'periodo') {
                $('#titulo_modal_ganancia').html('Resumen de caja y ganancia del período');
                $('#dg_contexto_consulta').html('Desde ' + fechai + ' hasta ' + fechaf);
            } else {
                $('#titulo_modal_ganancia').html('Resumen de caja y ganancia');
                $('#dg_contexto_consulta').html('Apertura de caja #' + apertura_id);
            }

            $('#dg_total_vendido').html('Cargando...');
            $('#dg_otros_ingresos').html('Cargando...');
            $('#dg_total_gastos').html('Cargando...');
            $('#dg_total_egresos_registrados').html('Cargando...');
            $('#dg_total_inversion_apartada').html('Cargando...');
            $('#dg_retiro_caja_pendiente').html('Cargando...');
            $('#dg_neto_disponible').html('Cargando...');

            $('#dg_efectivo').html('Cargando...');
            $('#dg_transferencia').html('Cargando...');
            $('#dg_tarjeta').html('Cargando...');
            $('#dg_cheque').html('Cargando...');

            $('#dg_monto_apertura').html('Cargando...');
            $('#dg_efectivo_caja').html('Cargando...');
            $('#dg_retiro_caja_total').html('Cargando...');
            $('#dg_retiro_caja_convertido').html('Cargando...');
            $('#dg_efectivo_esperado_caja').html('Cargando...');

            $('#dg_total_vendido_detalle').html('Cargando...');
            $('#dg_costo_productos').html('Cargando...');
            $('#dg_ganancia_bruta').html('Cargando...');
            $('#dg_dinero_recomendado_guardar').html('Cargando...');
            $('#dg_dinero_despues_reponer').html('Cargando...');
            $('#dg_porcentaje_costo').html('0.00%');
            $('#dg_porcentaje_ganancia').html('0.00%');
            $('#dg_diferencia_conciliacion').html('Cargando...');
        },
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo cargar el desglose de ganancia.');
                return;
            }

            var resumen = response.resumen || {};
            var detalles = response.detalles || [];

            var totalVendido = parseMonto(resumen.total_vendido || resumen.total_cobrado);
            var otrosIngresos = parseMonto(resumen.otros_ingresos);
            var totalGastos = parseMonto(resumen.total_gastos_reales || resumen.total_gastos);
            var totalEgresosRegistrados = parseMonto(resumen.total_egresos_registrados);
            var totalInversionApartada = parseMonto(resumen.total_inversion_apartada);
            var retiroCajaPendiente = parseMonto(resumen.retiro_caja_pendiente);
            var netoDisponible = parseMonto(resumen.neto_disponible);

            var efectivo = parseMonto(resumen.efectivo);
            var transferencia = parseMonto(resumen.transferencia);
            var tarjeta = parseMonto(resumen.tarjeta);
            var cheque = parseMonto(resumen.cheque);

            var montoApertura = parseMonto(resumen.monto_apertura);
            var retiroCajaTotal = parseMonto(resumen.retiro_caja_total || resumen.retiro_caja);
            var retiroCajaConvertido = parseMonto(resumen.retiro_caja_convertido_gasto);
            var efectivoEsperadoCaja = parseMonto(resumen.efectivo_esperado_caja);

            var totalVendidoDetalle = parseMonto(resumen.total_vendido_detalle);
            var costoProductos = parseMonto(resumen.costo_productos_vendidos);
            var gananciaBruta = parseMonto(resumen.ganancia_bruta);
            var dineroRecomendadoGuardar = parseMonto(resumen.dinero_recomendado_guardar);
            var dineroDespuesReponer = parseMonto(resumen.dinero_despues_reponer);
            var porcentajeCosto = parseMonto(resumen.porcentaje_costo);
            var porcentajeGanancia = parseMonto(resumen.porcentaje_ganancia);
            var diferenciaConciliacion = parseMonto(resumen.diferencia_conciliacion);

            $('#dg_total_vendido').html(formatoMoneda(totalVendido));
            $('#dg_otros_ingresos').html(formatoMoneda(otrosIngresos));
            $('#dg_total_gastos').html(formatoMoneda(totalGastos));
            $('#dg_total_egresos_registrados').html(formatoMoneda(totalEgresosRegistrados));
            $('#dg_total_inversion_apartada').html(formatoMoneda(totalInversionApartada));
            $('#dg_retiro_caja_pendiente').html(formatoMoneda(retiroCajaPendiente));
            $('#dg_neto_disponible').html(formatoMoneda(netoDisponible));

            $('#dg_efectivo').html(formatoMoneda(efectivo));
            $('#dg_transferencia').html(formatoMoneda(transferencia));
            $('#dg_tarjeta').html(formatoMoneda(tarjeta));
            $('#dg_cheque').html(formatoMoneda(cheque));

            $('#dg_monto_apertura').html(formatoMoneda(montoApertura));
            $('#dg_efectivo_caja').html(formatoMoneda(efectivo));
            $('#dg_retiro_caja_total').html(formatoMoneda(retiroCajaTotal));
            $('#dg_retiro_caja_convertido').html(formatoMoneda(retiroCajaConvertido));
            $('#dg_efectivo_esperado_caja').html(formatoMoneda(efectivoEsperadoCaja));

            $('#dg_total_vendido_detalle').html(formatoMoneda(totalVendidoDetalle));
            $('#dg_costo_productos').html(formatoMoneda(costoProductos));
            $('#dg_ganancia_bruta').html(formatoMoneda(gananciaBruta));
            $('#dg_dinero_recomendado_guardar').html(formatoMoneda(dineroRecomendadoGuardar));
            $('#dg_dinero_despues_reponer').html(formatoMoneda(dineroDespuesReponer));
            $('#dg_porcentaje_costo').html(porcentajeCosto.toFixed(2) + '%');
            $('#dg_porcentaje_ganancia').html(porcentajeGanancia.toFixed(2) + '%');
            $('#dg_diferencia_conciliacion').html(formatoMoneda(diferenciaConciliacion));

            var textoRegla = '';
            if (totalInversionApartada > 0) {
                textoRegla = 'Hay egresos marcados como inversión/reposición. Salen de caja, pero no se cuentan como gasto real.';
            } else if (retiroCajaConvertido > 0) {
                textoRegla = 'Esta caja ya tiene retiros convertidos en gasto. Por eso no se restan doble en el neto.';
            } else {
                textoRegla = 'Los retiros pendientes todavía no son egreso. Por eso sí se restan del neto disponible.';
            }

            $('#dg_regla_retiros').html(textoRegla);

            cargarTablaDetalleGananciaCaja(detalles);

            $('#modalDesgloseGananciaCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar el desglose de ganancia.');
        }
    });
}

function refrescarDesgloseGananciaCaja() {
    var apertura_id = parseInt($('#dg_apertura_id').val() || $('#modalDesgloseGananciaCaja').data('apertura_id') || 0);
    var modo = $('#dg_modo').val() || $('#modalDesgloseGananciaCaja').data('modo') || 'caja';

    cargarDesgloseGananciaCaja(apertura_id, modo);
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLE GANANCIA CAJA
   ========================================================= */
function construirHeaderFooterDetalleGananciaCaja() {
    var $tabla = $('#dataTableDetalleGananciaCaja');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Factura</th>' +
                '<th>Producto</th>' +
                '<th>Cantidad</th>' +
                '<th>Costo Unit.</th>' +
                '<th>Precio Venta</th>' +
                '<th>Total Costo</th>' +
                '<th>Total Venta</th>' +
                '<th>Ganancia</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right">Totales:</th>' +
                '<th id="dg_footer_total_costo">L. 0.00</th>' +
                '<th id="dg_footer_total_venta">L. 0.00</th>' +
                '<th id="dg_footer_total_ganancia">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   DATATABLE - DETALLE GANANCIA CAJA
   ========================================================= */
function cargarTablaDetalleGananciaCaja(detalles) {
    if ($.fn.DataTable.isDataTable('#dataTableDetalleGananciaCaja')) {
        $('#dataTableDetalleGananciaCaja').DataTable().clear().destroy();
    }

    construirHeaderFooterDetalleGananciaCaja();

    $('#dataTableDetalleGananciaCaja').DataTable({
        destroy: true,
        autoWidth: false,
        data: detalles,
        columns: [
            { data: "factura" },
            { data: "producto" },
            { data: "cantidad" },
            {
                data: "costo_unitario",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "precio_venta",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "total_costo",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "total_venta",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "ganancia",
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            }
        ],
        columnDefs: [
            {
                targets: [3, 4, 5, 6, 7],
                className: "text-right text-nowrap"
            },
            {
                targets: [0, 2],
                className: "text-center text-nowrap"
            }
        ],
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Detalle de Ganancia',
                className: 'btn btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Detalle de Ganancia',
                className: 'btn btn-danger'
            }
        ],
        footerCallback: function () {
            var api = this.api();

            var totalCosto = api.column(5, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalVenta = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalGanancia = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            $('#dg_footer_total_costo').html('<span>' + formatoMoneda(totalCosto) + '</span>');
            $('#dg_footer_total_venta').html('<span>' + formatoMoneda(totalVenta) + '</span>');
            $('#dg_footer_total_ganancia').html('<span>' + formatoMoneda(totalGanancia) + '</span>');
        }
    });
}
</script>