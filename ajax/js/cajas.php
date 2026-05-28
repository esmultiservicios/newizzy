<script>
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

    function notificarCaja(tipo, mensaje) {
        if (typeof showNotify === 'function') {
            showNotify(tipo, mensaje);
        } else if (typeof swal === 'function') {
            swal({
                title: tipo === 'error' ? 'Error' : 'Información',
                text: mensaje,
                icon: tipo === 'error' ? 'error' : 'info',
                button: 'Aceptar'
            });
        } else {
            alert(mensaje);
        }
    }

    function parseMonto(valor) {
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

    var listar_registro_cajas = function () {
    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();
    var estado = $("#formMainCajas #estado_cajas").val();

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
                        '<button type="button" class="dropdown-item accion-item accion-ganancia table_ganancia">' +
                            '<span class="accion-icon accion-icon-primary">' +
                                '<i class="fas fa-chart-line"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver ganancia</span>' +
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
            cerrarDropdownAcciones();

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
};

    var comprobante_cajas_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.table_crear");

        $(tbody).on("click", "button.table_crear", function () {
            var data = table.row($(this).parents("tr")).data();

            if (!esCajaActiva(data)) {
                notificarCaja('error', 'Esta caja ya está cerrada. No se puede cerrar nuevamente.');
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
    }

    var cerrar_registro_cajas_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.table_reportes");

        $(tbody).on("click", "button.table_reportes", function () {
            var data = table.row($(this).parents("tr")).data();
            printComprobanteCajas(data.apertura_id);
        });
    }

    var desglose_ganancia_caja_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.table_ganancia");

        $(tbody).on("click", "button.table_ganancia", function () {
            var data = table.row($(this).parents("tr")).data();

            if (!data || !data.apertura_id) {
                notificarCaja('error', 'No se encontró el código de apertura de caja.');
                return;
            }

            cargarDesgloseGananciaCaja(data.apertura_id, 'caja');
        });
    }

    var retiro_caja_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.table_retiro_caja");

        $(tbody).on("click", "button.table_retiro_caja", function () {
            var data = table.row($(this).parents("tr")).data();

            if (!data || !data.apertura_id) {
                notificarCaja('error', 'No se encontró la apertura de caja.');
                return;
            }

            if (!esCajaActiva(data)) {
                notificarCaja('error', 'Solo puede retirar dinero de una caja activa.');
                return;
            }

            var saldoDisponible = parseMonto(data.neto);

            $('#formRetiroCaja')[0].reset();

            $('#retiro_apertura_id').val(data.apertura_id);
            $('#retiro_saldo_actual').val(saldoDisponible.toFixed(2));
            $('#retiro_saldo_final').val(saldoDisponible.toFixed(2));

            $('#retiro_saldo_actual_text').html(formatoMoneda(saldoDisponible));
            $('#retiro_saldo_final_text').html(formatoMoneda(saldoDisponible));

            $('#retiro_mensaje_validacion').hide().html('');
            $('#btn_guardar_retiro_caja').prop('disabled', true);

            if ($.fn.selectpicker) {
                $('#retiro_motivo').selectpicker('val', '');
                $('#retiro_motivo').selectpicker('refresh');
            }

            $('#modalRetiroCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });

            setTimeout(function () {
                $('#retiro_monto').focus();
            }, 500);
        });
    };
        
 function cargarDesgloseGananciaCaja(apertura_id, modo) {
    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = 'caja';
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            notificarCaja('error', 'Debe seleccionar fecha inicial y fecha final.');
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

            $('#dg_total_cobrado').html('Cargando...');
            $('#dg_costo_productos').html('Cargando...');
            $('#dg_costo_productos_2').html('Cargando...');
            $('#dg_dinero_despues_reponer').html('Cargando...');

            $('#dg_efectivo').html('Cargando...');
            $('#dg_transferencia').html('Cargando...');
            $('#dg_tarjeta').html('Cargando...');
            $('#dg_cheque').html('Cargando...');

            $('#dg_monto_apertura').html('Cargando...');
            $('#dg_efectivo_caja').html('Cargando...');
            $('#dg_retiro_caja').html('Cargando...');
            $('#dg_efectivo_esperado_caja').html('Cargando...');

            $('#dg_total_vendido_detalle').html('Cargando...');
            $('#dg_ganancia_bruta').html('Cargando...');
            $('#dg_diferencia_conciliacion').html('Cargando...');
        },
        success: function (response) {
            if (!response.success) {
                notificarCaja('error', response.message || 'No se pudo cargar el desglose de ganancia.');
                return;
            }

            var resumen = response.resumen || {};
            var detalles = response.detalles || [];

            var totalCobrado = parseMonto(resumen.total_cobrado);
            var costoProductos = parseMonto(resumen.costo_productos_vendidos);
            var dineroDespuesReponer = parseMonto(resumen.dinero_despues_reponer);
            var efectivo = parseMonto(resumen.efectivo);
            var transferencia = parseMonto(resumen.transferencia);
            var tarjeta = parseMonto(resumen.tarjeta);
            var cheque = parseMonto(resumen.cheque);
            var montoApertura = parseMonto(resumen.monto_apertura);
            var retiroCaja = parseMonto(resumen.retiro_caja);
            var efectivoEsperadoCaja = parseMonto(resumen.efectivo_esperado_caja);
            var totalVendidoDetalle = parseMonto(resumen.total_vendido_detalle);
            var gananciaBruta = parseMonto(resumen.ganancia_bruta);
            var diferenciaConciliacion = parseMonto(resumen.diferencia_conciliacion);

            $('#dg_total_cobrado').html(formatoMoneda(totalCobrado));
            $('#dg_costo_productos').html(formatoMoneda(costoProductos));
            $('#dg_costo_productos_2').html(formatoMoneda(costoProductos));
            $('#dg_dinero_despues_reponer').html(formatoMoneda(dineroDespuesReponer));

            $('#dg_efectivo').html(formatoMoneda(efectivo));
            $('#dg_transferencia').html(formatoMoneda(transferencia));
            $('#dg_tarjeta').html(formatoMoneda(tarjeta));
            $('#dg_cheque').html(formatoMoneda(cheque));

            $('#dg_monto_apertura').html(formatoMoneda(montoApertura));
            $('#dg_efectivo_caja').html(formatoMoneda(efectivo));
            $('#dg_retiro_caja').html(formatoMoneda(retiroCaja));
            $('#dg_efectivo_esperado_caja').html(formatoMoneda(efectivoEsperadoCaja));

            $('#dg_total_vendido_detalle').html(formatoMoneda(totalVendidoDetalle));
            $('#dg_ganancia_bruta').html(formatoMoneda(gananciaBruta));
            $('#dg_diferencia_conciliacion').html(formatoMoneda(diferenciaConciliacion));

            cargarTablaDetalleGananciaCaja(detalles);

            $('#modalDesgloseGananciaCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            notificarCaja('error', 'Error de comunicación al cargar el desglose de ganancia.');
        }
    });
}

    function cargarTablaDetalleGananciaCaja(detalles) {
        $('#dataTableDetalleGananciaCaja').DataTable({
            "destroy": true,
            "autoWidth": false,
            "data": detalles,
            "columns": [
                { "data": "factura" },
                { "data": "producto" },
                { "data": "cantidad" },
                {
                    "data": "costo_unitario",
                    "render": function (data, type) {
                        return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                    }
                },
                {
                    "data": "precio_venta",
                    "render": function (data, type) {
                        return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                    }
                },
                {
                    "data": "total_costo",
                    "render": function (data, type) {
                        return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                    }
                },
                {
                    "data": "total_venta",
                    "render": function (data, type) {
                        return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                    }
                },
                {
                    "data": "ganancia",
                    "render": function (data, type) {
                        return renderMonedaColor(data, type);
                    }
                }
            ],
            "columnDefs": [
                {
                    "targets": [3, 4, 5, 6, 7],
                    "className": "text-right text-nowrap"
                },
                {
                    "targets": [0, 2],
                    "className": "text-center text-nowrap"
                }
            ],
            "lengthMenu": lengthMenu,
            "language": idioma_español,
            "dom": dom,
            "buttons": [
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
            "footerCallback": function () {
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