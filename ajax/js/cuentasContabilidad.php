// Reemplaza todo el código JS con este
<script>
$(document).ready(function() {
    listar_cuentas_contabilidad();

    $('#formMainCuentasContabilidad #search').on("click", function(e) {
        e.preventDefault();
        listar_cuentas_contabilidad();
    });

    $('#formMainCuentasContabilidad').on('reset', function() {
        $(this).find('.selectpicker').val('').selectpicker('refresh');
        listar_cuentas_contabilidad();
    });    
});

function cleanNumber(numStr) {
    return numStr.replace(/[^0-9.-]+/g,"");
}

function formatCurrency(value) {
    return 'L. ' + parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

var listar_cuentas_contabilidad = function() {
    var fechai = $("#formMainCuentasContabilidad #fechai").val();
    var fechaf = $("#formMainCuentasContabilidad #fechaf").val();
    var estado = $('#formMainCuentasContabilidad #estado_cuentasContabilidad').val();

    $.ajax({
        method: "POST",
        url: "<?php echo SERVERURL;?>core/llenarDataTableCuentas.php",
        data: {
            "fechai": fechai,
            "fechaf": fechaf,
            "estado": estado
        },
        dataType: "json",
        beforeSend: function() {
            $("#cuentas-container").html('<div class="col-12 text-center py-4"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-2">Cargando cuentas...</p></div>');
        },
        success: function(response) {
            if(response.data && response.data.length > 0) {
                let html = '';
                
                response.data.forEach(function(cuenta) {
                    const saldoNeto = parseFloat(cleanNumber(cuenta.neto));
                    const saldoClass = saldoNeto >= 0 ? 'positive-balance' : 'negative-balance';
                    const estadoBadge = cuenta.estado == 1 ? 
                        '<span class="badge badge-success">Activo</span>' : 
                        '<span class="badge badge-danger">Inactivo</span>';
                    
                    html += `
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm card-account ${saldoClass}">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-truncate">${cuenta.nombre}</h5>
                                ${estadoBadge}
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="font-weight-bold">Saldo Anterior:</span>
                                    <span>${cuenta.saldo_anterior}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="font-weight-bold">Ingresos:</span>
                                    <span class="text-success">${cuenta.ingreso}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="font-weight-bold">Egresos:</span>
                                    <span class="text-danger">${cuenta.egreso}</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="font-weight-bold">Saldo Cierre:</span>
                                    <span>${cuenta.saldo_cierre}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="font-weight-bold">Saldo Total:</span>
                                    <span class="${saldoNeto >= 0 ? 'text-success' : 'text-danger'} font-weight-bold">${cuenta.neto}</span>
                                </div>
                            </div>
                            <div class="card-footer bg-white d-flex justify-content-end">
                                <button class="btn btn-sm btn-outline-primary table_editar mr-2" data-id="${cuenta.cuentas_id}">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger table_eliminar" data-id="${cuenta.cuentas_id}">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                
                $("#cuentas-container").html(html);
                
                // Asignar eventos a los botones
                $(".table_editar").click(function() {
                    var cuentas_id = $(this).data('id');
                    editar_cuenta(cuentas_id);
                });
                
                $(".table_eliminar").click(function() {
                    var cuentas_id = $(this).data('id');
                    var nombreCuenta = $(this).closest('.card').find('.card-header h5').text();
                    eliminar_cuenta(cuentas_id, nombreCuenta);
                });
            } else {
                $("#cuentas-container").html('<div class="col-12 text-center py-5"><i class="fas fa-box-open fa-3x mb-3 text-muted"></i><h4 class="text-muted">No se encontraron cuentas</h4></div>');
            }
        },
        error: function() {
            $("#cuentas-container").html('<div class="col-12"><div class="alert alert-danger">Error al cargar las cuentas. Intente nuevamente.</div></div>');
        }
    });
};

function editar_cuenta(cuentas_id) {
    var url = '<?php echo SERVERURL;?>core/editarCuentasContabilidad.php';
    $('#formCuentasContables #cuentas_id').val(cuentas_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formCuentasContables').serialize(),
        success: function(registro) {
            var valores = eval(registro);
            $('#formCuentasContables').attr({
                'data-form': 'update'
            });
            $('#formCuentasContables').attr({
                'action': '<?php echo SERVERURL;?>ajax/modificarCuentaContabilidadAjax.php'
            });
            $('#formCuentasContables')[0].reset();
            $('#reg_cuentas').hide();
            $('#edi_cuentas').show();
            $('#delete_cuentas').hide();

            $('#formCuentasContables #cuenta_codigo').val(valores[1]);
            $('#formCuentasContables #cuenta_nombre').val(valores[2]);

            if (valores[3] == 1) {
                $('#formCuentasContables #clientes_activo').attr('checked', true);
            } else {
                $('#formCuentasContables #clientes_activo').attr('checked', false);
            }

            $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
            $('#formCuentasContables #estado_cuentas_contables').show();
            $('#formCuentasContables #cuenta_codigo').attr("readonly", true);
            $('#formCuentasContables #pro_cuentas').val("Editar");
            
            $('#modalCuentascontables').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        }
    });
}

function eliminar_cuenta(cuentas_id, nombreCuenta) {
    var mensajeHTML = `¿Desea eliminar permanentemente la cuenta?<br><br>
                    <strong>Nombre:</strong> ${nombreCuenta}`;
    
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
                    
                    if(response.status === "success") {
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
                error: function() {
                    swal.close();
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
        'data-form': 'save'
    });
    $('#formCuentasContables').attr({
        'action': '<?php echo SERVERURL;?>ajax/addCuentasContablesAjax.php'
    });
    $('#formCuentasContables')[0].reset();
    $('#reg_cuentas').show();
    $('#edi_cuentas').hide();
    $('#delete_cuentas').hide();

    $('#formCuentasContables #cuenta_codigo').attr("readonly", false);
    $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
    $('#formCuentasContables #cuentas_activo').attr("disabled", false);
    $('#formCuentasContables #estado_cuentas_contables').hide();
    $('#formCuentasContables #pro_cuentas').val("Registro");

    $('#modalCuentascontables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formCuentasContables #label_cuentas_activo').html("Activo");

$('#formCuentasContables .switch').change(function() {
    if ($('input[name=cuentas_activo]').is(':checked')) {
        $('#formCuentasContables #label_cuentas_activo').html("Activo");
    } else {
        $('#formCuentasContables #label_cuentas_activo').html("Inactivo");
    }
});

$(document).ready(function() {
    $("#modalCuentascontables").on('shown.bs.modal', function() {
        $(this).find('#formCuentasContables #cuenta_nombre').focus();
    });
});
</script>