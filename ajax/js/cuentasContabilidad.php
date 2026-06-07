<script>
$(() => {
    listar_cuentas_contabilidad();

    $('#formMainCuentasContabilidad #search').off('click');
    $('#formMainCuentasContabilidad #search').on('click', function(e) {
        e.preventDefault();
        listar_cuentas_contabilidad();
    });

    $('#formMainCuentasContabilidad #buscar_cuenta').off('keyup');
    $('#formMainCuentasContabilidad #buscar_cuenta').on('keyup', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            listar_cuentas_contabilidad();
        }
    });

    $('#formMainCuentasContabilidad #estado_cuentasContabilidad, #formMainCuentasContabilidad #tipo_cuenta, #formMainCuentasContabilidad #tipo_saldo, #formMainCuentasContabilidad #orden_cuentas').off('changed.bs.select change');
    $('#formMainCuentasContabilidad #estado_cuentasContabilidad, #formMainCuentasContabilidad #tipo_cuenta, #formMainCuentasContabilidad #tipo_saldo, #formMainCuentasContabilidad #orden_cuentas').on('changed.bs.select change', function() {
        listar_cuentas_contabilidad();
    });

    $('#formMainCuentasContabilidad').off('reset');
    $('#formMainCuentasContabilidad').on('reset', function() {
        var form = this;

        setTimeout(function() {
            $(form).find('#buscar_cuenta').val('');
            $(form).find('#estado_cuentasContabilidad').selectpicker('val', '');
            $(form).find('#tipo_cuenta').selectpicker('val', '');
            $(form).find('#tipo_saldo').selectpicker('val', '');
            $(form).find('#orden_cuentas').selectpicker('val', 'neto_desc');
            $(form).find('.selectpicker').selectpicker('refresh');

            listar_cuentas_contabilidad();
        }, 0);
    });

    $('#cuentas-container').off('click', '.js-acciones-toggle');
    $('#cuentas-container').on('click', '.js-acciones-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $dropdown = $btn.closest('.acciones-dropdown');
        var $menu = $dropdown.find('.acciones-menu').first();
        var estaAbierto = $menu.hasClass('show');

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        if (!estaAbierto) {
            $dropdown.addClass('show');
            $menu.addClass('show');
            $btn.attr('aria-expanded', 'true');
        }
    });

    $(document).off('click.cuentasAcciones');
    $(document).on('click.cuentasAcciones', function() {
        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');
    });

    $('#cuentas-container').off('click', '.acciones-menu');
    $('#cuentas-container').on('click', '.acciones-menu', function(e) {
        e.stopPropagation();
    });

    $('#cuentas-container').off('click', '.table_editar');
    $('#cuentas-container').on('click', '.table_editar', function(e) {
        e.preventDefault();

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        var cuentas_id = $(this).data('id');
        editar_cuenta(cuentas_id);
    });

    $('#cuentas-container').off('click', '.table_eliminar');
    $('#cuentas-container').on('click', '.table_eliminar', function(e) {
        e.preventDefault();

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        var cuentas_id = $(this).data('id');
        var nombreCuenta = $(this).data('nombre');

        eliminar_cuenta(cuentas_id, nombreCuenta);
    });

    $('#formCuentasContables #cuentas_activo').off('change');
    $('#formCuentasContables #cuentas_activo').on('change', function() {
        actualizarLabelEstadoCuenta();
    });

    $('#modalCuentascontables').off('shown.bs.modal.cuentas');
    $('#modalCuentascontables').on('shown.bs.modal.cuentas', function() {
        $(this).find('#formCuentasContables #cuenta_nombre').focus();
    });
});

function escapeHtml(text) {
    if (text === null || text === undefined) {
        return '';
    }

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cleanNumber(numStr) {
    if (numStr === null || numStr === undefined) {
        return '0';
    }

    return String(numStr).replace(/[^0-9.-]+/g, '');
}

function formatCurrency(value) {
    var n = parseFloat(value);

    if (isNaN(n)) {
        n = 0;
    }

    return 'L. ' + n.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function actualizarLabelEstadoCuenta() {
    if ($('#formCuentasContables #cuentas_activo').is(':checked')) {
        $('#formCuentasContables #label_cuentas_activo').html('Activo');
    } else {
        $('#formCuentasContables #label_cuentas_activo').html('Inactivo');
    }
}

function badgeEstadoCuenta(estado) {
    var activo = parseInt(estado || 0, 10) === 1;

    if (activo) {
        return '<span class="badge-cuenta-activa"><i class="fas fa-check-circle"></i> Activa</span>';
    }

    return '<span class="badge-cuenta-inactiva"><i class="fas fa-times-circle"></i> Inactiva</span>';
}

function badgeInversionCuenta(es_inversion) {
    var inversion = parseInt(es_inversion || 0, 10) === 1;

    if (inversion) {
        return '<span class="badge-cuenta-inversion"><i class="fas fa-seedling"></i> Inversión</span>';
    }

    return '<span class="badge-cuenta-normal"><i class="fas fa-wallet"></i> Normal</span>';
}

function renderAccionesCuenta(cuenta) {
    var cuentas_id = escapeHtml(cuenta.cuentas_id);
    var nombre = escapeHtml(cuenta.nombre);

    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +

            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar" data-id="' + cuentas_id + '">' +
                    '<span class="accion-icon accion-icon-primary">' +
                        '<i class="fas fa-edit"></i>' +
                    '</span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar" data-id="' + cuentas_id + '" data-nombre="' + nombre + '">' +
                    '<span class="accion-icon accion-icon-danger">' +
                        '<i class="fas fa-trash-alt"></i>' +
                    '</span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

var listar_cuentas_contabilidad = function() {
    var fechai = $("#formMainCuentasContabilidad #fechai").val();
    var fechaf = $("#formMainCuentasContabilidad #fechaf").val();
    var estado = $('#formMainCuentasContabilidad #estado_cuentasContabilidad').val();
    var buscar = $('#formMainCuentasContabilidad #buscar_cuenta').val();
    var tipo_cuenta = $('#formMainCuentasContabilidad #tipo_cuenta').val();
    var tipo_saldo = $('#formMainCuentasContabilidad #tipo_saldo').val();
    var orden_cuentas = $('#formMainCuentasContabilidad #orden_cuentas').val();

    $.ajax({
        method: "POST",
        url: "<?php echo SERVERURL;?>core/llenarDataTableCuentas.php",
        data: {
            fechai: fechai,
            fechaf: fechaf,
            estado: estado,
            buscar: buscar,
            tipo_cuenta: tipo_cuenta,
            tipo_saldo: tipo_saldo,
            orden_cuentas: orden_cuentas
        },
        dataType: "json",
        beforeSend: function() {
            $("#cuentas-container").html(
                '<div class="col-12 text-center py-5">' +
                    '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>' +
                    '<p class="mt-3 mb-0 text-muted font-weight-bold">Cargando cuentas...</p>' +
                '</div>'
            );
        },
        success: function(response) {
            if (response.data && response.data.length > 0) {
                let html = '';
                
                response.data.forEach(function(cuenta) {
                    const saldoNeto = parseFloat(cleanNumber(cuenta.neto));
                    const saldoClass = saldoNeto >= 0 ? 'positive-balance' : 'negative-balance';
                    const inversionClass = parseInt(cuenta.es_inversion || 0, 10) === 1 ? 'account-investment' : '';

                    const nombre = escapeHtml(cuenta.nombre);
                    const codigo = escapeHtml(cuenta.codigo || 'Sin código');
                    
                    html += `
                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100 card-account ${saldoClass} ${inversionClass}">
                            <div class="cuenta-card-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="cuenta-card-title-wrap">
                                        <h5 class="cuenta-card-title text-truncate" title="${nombre}">
                                            ${nombre}
                                        </h5>

                                        <span class="cuenta-card-code">
                                            <i class="fas fa-hashtag"></i>
                                            ${codigo}
                                        </span>
                                    </div>

                                    <div class="cuenta-badges-wrap">
                                        ${badgeEstadoCuenta(cuenta.estado)}
                                        ${badgeInversionCuenta(cuenta.es_inversion)}
                                    </div>
                                </div>
                            </div>

                            <div class="cuenta-card-body">
                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-history"></i>
                                        Saldo Anterior
                                    </span>
                                    <span class="cuenta-row-value">${cuenta.saldo_anterior}</span>
                                </div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-arrow-down"></i>
                                        Ingresos
                                    </span>
                                    <span class="cuenta-row-value text-success">${cuenta.ingreso}</span>
                                </div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-arrow-up"></i>
                                        Egresos
                                    </span>
                                    <span class="cuenta-row-value text-danger">${cuenta.egreso}</span>
                                </div>

                                <div class="cuenta-divider"></div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-balance-scale"></i>
                                        Saldo Cierre
                                    </span>
                                    <span class="cuenta-row-value">${cuenta.saldo_cierre}</span>
                                </div>

                                <div class="cuenta-total-box d-flex justify-content-between align-items-center">
                                    <span class="cuenta-total-label">
                                        <i class="fas fa-wallet mr-1"></i>
                                        Saldo Total
                                    </span>

                                    <span class="cuenta-total-value ${saldoNeto >= 0 ? 'text-success' : 'text-danger'}">
                                        ${cuenta.neto}
                                    </span>
                                </div>
                            </div>

                            <div class="cuenta-card-footer">
                                ${renderAccionesCuenta(cuenta)}
                            </div>
                        </div>
                    </div>`;
                });
                
                $("#cuentas-container").html(html);

                $('[data-toggle="tooltip"]').tooltip({
                    container: "body",
                    placement: "top"
                });
            } else {
                $("#cuentas-container").html(
                    '<div class="col-12 text-center py-5">' +
                        '<i class="fas fa-box-open fa-3x mb-3 text-muted"></i>' +
                        '<h4 class="text-muted mb-1">No se encontraron cuentas</h4>' +
                        '<p class="text-muted mb-0">No hay información disponible con los filtros seleccionados.</p>' +
                    '</div>'
                );
            }
        },
        error: function(xhr) {
            console.error("Error al cargar cuentas:", xhr.responseText);

            $("#cuentas-container").html(
                '<div class="col-12">' +
                    '<div class="alert alert-danger mb-0">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                        'Error al cargar las cuentas. Intente nuevamente.' +
                    '</div>' +
                '</div>'
            );
        }
    });
};

function editar_cuenta(cuentas_id) {
    var url = '<?php echo SERVERURL;?>core/editarCuentasContabilidad.php';

    $('#formCuentasContables #cuentas_id').val(cuentas_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            cuentas_id: cuentas_id
        },
        dataType: 'json',
        success: function(valores) {
            $('#formCuentasContables').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/modificarCuentaContabilidadAjax.php'
            });

            $('#formCuentasContables')[0].reset();

            $('#reg_cuentas').hide();
            $('#edi_cuentas').show();
            $('#delete_cuentas').hide();

            $('#formCuentasContables #cuentas_id').val(valores[0]);
            $('#formCuentasContables #cuenta_codigo').val(valores[1]);
            $('#formCuentasContables #cuenta_nombre').val(valores[2]);

            $('#formCuentasContables #cuentas_activo')
                .prop('checked', parseInt(valores[3] || 0, 10) === 1)
                .prop('disabled', false);

            $('#formCuentasContables #es_inversion')
                .prop('checked', parseInt(valores[4] || 0, 10) === 1);

            actualizarLabelEstadoCuenta();

            $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
            $('#formCuentasContables #estado_cuentas_contables').show();
            $('#formCuentasContables #cuenta_codigo').attr("readonly", true);
            $('#formCuentasContables #pro_cuentas').val("Editar");
            
            $('#modalCuentascontables').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr) {
            console.error("Error al editar cuenta:", xhr.responseText);
            showNotify("error", "Error", "No se pudieron cargar los datos de la cuenta.");
        }
    });
}

function eliminar_cuenta(cuentas_id, nombreCuenta) {
    var mensajeHTML = `¿Desea eliminar permanentemente la cuenta?<br><br>
                    <strong>Nombre:</strong> ${escapeHtml(nombreCuenta)}`;
    
    swal({
        title: "Confirmar eliminación",
        content: {
            element: "span",
            attributes: {
                innerHTML: mensajeHTML
            }
        },
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                value: null,
                visible: true,
                className: "btn-light"
            },
            confirm: {
                text: "Sí, eliminar",
                value: true,
                className: "btn-danger",
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((confirmar) => {
        if (confirmar) {
            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>ajax/eliminarCuentaContabilidadAjax.php',
                data: {
                    cuentas_id: cuentas_id
                },
                dataType: 'json',
                beforeSend: function() {
                    swal({
                        title: "Eliminando...",
                        text: "Por favor espere",
                        icon: "info",
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    });
                },
                success: function(response) {
                    swal.close();
                    
                    if (response.status === "success") {
                        swal({
                            title: response.title,
                            text: response.message,
                            icon: "success",
                            timer: 2000,
                            buttons: false
                        });

                        listar_cuentas_contabilidad();
                    } else {
                        swal({
                            title: response.title,
                            text: response.message,
                            icon: "error"
                        });
                    }
                },
                error: function(xhr) {
                    swal.close();

                    console.error("Error al eliminar cuenta:", xhr.responseText);

                    swal({
                        title: "Error",
                        text: "Ocurrió un error al procesar la solicitud",
                        icon: "error"
                    });
                }
            });
        }
    });        
}

function modal_cuentas_contables() {
    $('#formCuentasContables').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL;?>ajax/addCuentasContablesAjax.php'
    });

    $('#formCuentasContables')[0].reset();

    $('#formCuentasContables #cuentas_id').val('');
    $('#formCuentasContables #es_inversion').prop('checked', false);
    $('#formCuentasContables #cuentas_activo').prop('checked', true).prop('disabled', false);

    actualizarLabelEstadoCuenta();

    $('#reg_cuentas').show();
    $('#edi_cuentas').hide();
    $('#delete_cuentas').hide();

    $('#formCuentasContables #cuenta_codigo').attr("readonly", false);
    $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
    $('#formCuentasContables #estado_cuentas_contables').hide();
    $('#formCuentasContables #pro_cuentas').val("Registro");

    $('#modalCuentascontables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
</script>