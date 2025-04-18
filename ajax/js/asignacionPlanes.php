<script>
//asignacionPlanes.php
document.addEventListener("DOMContentLoaded", function() {
    const tablaAsignaciones = $('#tablaAsignaciones').DataTable({
        ajax: {
            url: "<?php echo SERVERURL; ?>core/obtenerAsignacionesRecientes.php",
            type: "POST",
            dataSrc: "data",
            error: function(xhr) {
                console.error("Error al cargar asignaciones:", xhr.responseText);
                showNotify("error", "Error", "Error al cargar las asignaciones");
            }
        },
        columns: [
            { 
                data: null,  // Datos no provienen del servidor, por eso usamos null
                render: function(data, type, row, meta) {
                    return meta.row + 1;  // Este es el número de la fila (1-indexed)
                },
                className: "text-center"
            },            
            { 
                data: "cliente",
                render: function(data) {
                    return `
                    <strong>${data.nombre}</strong><br>
                    <small class="text-muted">RTN: ${data.identificacion || 'Sin identificación'}</small><br>
                    <small class="text-muted">Codigo Cliente: ${data.codigo_cliente || 'Sin código'}</small><br>
                    `;
                }
            },
            {
                data: "plan",
                render: function(data, type, row) {
                    const planColors = {
                        1: 'badge-primary',    // Emprendedor
                        2: 'badge-info',       // Básico
                        3: 'badge-success',    // Regular
                        4: 'badge-warning',    // Estandar
                        5: 'badge-danger',     // Premium
                        6: 'badge-secondary'   // Gratis
                    };

                    const badgeClass = planColors[row.planes_id] || 'badge-light';
                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">${data.nombre}</span>`;
                }
            },
            {
                data: "sistema",
                render: function(data) {
                    const badgeClass = data.sistema_id == 1 ? 'badge-info' : 
                                     (data.sistema_id == 2 ? 'badge-success' : 'badge-warning');
                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">${data.nombre}</span>`;
                }
            },
            { 
                data: "user_extra",
                className: "text-center",
                render: function(data) {
                    return data > 0 ? `<span class="badge badge-secondary badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">+${data}</span>` : 
                                     '<span class="text-muted">Ninguno</span>';
                }
            },
            {
                data: "validar",
                className: "text-center",
                render: function(data) {
                    const badgeClass = data == 1 ? 'badge-success' : 'badge-secondary';
                    const text = data == 1 ? 'Sí' : 'No';
                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">${text}</span>`;
                }
            },
            {
                data: "estado",
                className: "text-center",
                render: function(data) {
                    const badgeClass = data == 1 ? 'badge-primary' : 'badge-danger';
                    const text = data == 1 ? 'Activo' : 'Inactivo';
                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">${text}</span>`;
                }
            },
            { 
                data: "fecha_registro",
                render: function(data) {
                    return formatFechaHora(data);
                }
            },
            {
                data: null,
                className: "text-center",
                render: function(data) {
                    return `
                        <button class="table_editar btn btn-dark ocultar btn-editar-asignacion" 
                                data-id="${data.server_customers_id}"
                                data-cliente-id="${data.cliente_id}" 
                                data-plan-id="${data.planes_id}"
                                data-sistema-id="${data.sistema_id}"
                                data-user-extra="${data.user_extra}"
                                data-validar="${data.validar}"
                                data-estado="${data.estado}">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    `;
                }
            }
        ],
        language: idioma_español,
        responsive: true
    });

    $(document).on('click', '.btn-editar-asignacion', function() {
        $('#server_customers_id').val($(this).data('id'));
        $('#cliente_id').val($(this).data('cliente-id')).selectpicker('refresh');
        $('#planes_id').val($(this).data('plan-id')).selectpicker('refresh');
        $('#sistema_id').val($(this).data('sistema-id')).selectpicker('refresh');
        $('#user_extra').val($(this).data('user-extra'));
        $('#validar').val($(this).data('validar')).selectpicker('refresh');
        $('#estado').val($(this).data('estado')).selectpicker('refresh');
        
        $('html, body').animate({
            scrollTop: $('#div_top').offset().top - 20
        }, 500);
    });    

    function cargarClientes() {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/obtenerClientesParaAsignacion.php",
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $('#cliente_id');
                    select.empty();
                    
                    response.data.forEach(cliente => {
                        select.append(`
                            <option value="${cliente.clientes_id}" 
                                    data-subtext="${cliente.identificacion || 'Sin identificación'}">
                                ${cliente.nombre}
                            </option>
                        `);
                    });
                    
                    select.selectpicker('refresh');
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar clientes");
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error de conexión al cargar clientes");
            }
        });
    }

    function cargarPlanes() {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/obtenerPlanesActivos.php",
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $('#planes_id');
                    select.empty();
                    
                    response.data.forEach(plan => {
                        select.append(`<option value="${plan.planes_id}">${plan.nombre}</option>`);
                    });
                    
                    select.selectpicker('refresh');
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar planes");
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error de conexión al cargar planes");
            }
        });
    }

    function cargarSistemas() {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/obtenerSistemas.php",
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $('#sistema_id');
                    select.empty();
                    
                    response.data.forEach(sistema => {
                        select.append(`<option value="${sistema.sistema_id}">${sistema.nombre}</option>`);
                    });
                    
                    select.selectpicker('refresh').prop('disabled', true);
                }
            },
            error: function(xhr) {
                console.error("Error al cargar sistemas:", xhr.responseText);
            }
        });
    }

    function verificarPlanCliente(clienteId, callback) {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/verificarPlanCliente.php",
            type: "POST",
            data: { cliente_id: clienteId },
            dataType: "json",
            success: function(response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error al verificar plan del cliente");
            }
        });
    }

    function actualizarPlanCliente(formData, callback) {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/actualizarPlanCliente.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function(response) {
                // Actualizar el DataTable principal
                tablaAsignaciones.ajax.reload(null, false);
                $('#modalConfirmarCambio').modal('hide');
                showNotify(response.type, response.title, response.message);
            },
            error: function(xhr) {
                showNotify("error", "Error", "Error al actualizar plan");
            }
        });
    }

    $('#cliente_id').on('change', function() {
        const clienteId = $(this).val();
        if (clienteId) {
            verificarPlanCliente(clienteId, function(response) {
                if (response.exists) {
                    $('#server_customers_id').val(response.data.server_customers_id);
                    $('#planes_id').val(response.data.planes_id).selectpicker('refresh');
                    $('#sistema_id').val(response.data.sistema_id).selectpicker('refresh');
                    $('#user_extra').val(response.data.user_extra);
                    
                    $('#mensajeConfirmacion').html(`
                        Actualizar plan para <strong>${response.data.cliente_nombre}</strong> a 
                        <strong>${$('#planes_id option:selected').text()}</strong> con 
                        <strong>${$('#user_extra').val()} usuarios extras</strong>.
                    `);
                }
            });
        }
    });

    $(document).on('click', '.btn-editar-asignacion', function() {
        $('#server_customers_id').val($(this).data('id'));
        $('#cliente_id').val($(this).data('cliente-id')).selectpicker('refresh');
        $('#planes_id').val($(this).data('plan-id')).selectpicker('refresh');
        $('#sistema_id').val($(this).data('sistema-id')).selectpicker('refresh');
        $('#user_extra').val($(this).data('user-extra'));
        
        $('html, body').animate({
            scrollTop: $('#div_top').offset().top - 20
        }, 500);
    });

    $('#formAsignacionPlan').on('submit', function(e) {
        e.preventDefault();
        
        const clienteId = $('#cliente_id').val();
        if (!clienteId) {
            showNotify("warning", "Advertencia", "Debe seleccionar un cliente");
            return;
        }
        
        $('#modalConfirmarCambio').modal('show');
    });

    $('#btn-confirmar-cambio').on('click', function() {
        const formData = $('#formAsignacionPlan').serialize();
        
        actualizarPlanCliente(formData, function(response) {
            if (response.success) {
                showNotify("success", "Éxito", response.message);
                $('#modalConfirmarCambio').modal('hide');
                // Actualizar el DataTable principal
                tablaAsignaciones.ajax.reload(null, false);
                $('#modalConfirmarCambio').modal('hide');
                $('#formAsignacionPlan')[0].reset();
                $('#server_customers_id').val('');
                $('.selectpicker').selectpicker('refresh');
            } else {
                showNotify("error", "Error", response.message);
            }
        });
    });

    cargarClientes();
    cargarPlanes();
    cargarSistemas();
});

function formatFechaHora(fecha) {
    if (!fecha) return '';
    
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>