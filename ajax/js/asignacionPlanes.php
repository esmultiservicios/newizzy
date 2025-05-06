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
                    const planInfo = {
                        1: { class: 'badge-primary', icon: 'fas fa-rocket' },        // Emprendedor
                        2: { class: 'badge-info', icon: 'fas fa-leaf' },             // Básico
                        3: { class: 'badge-success', icon: 'fas fa-check-circle' },  // Regular
                        4: { class: 'badge-warning', icon: 'fas fa-star-half-alt' }, // Estándar
                        5: { class: 'badge-danger', icon: 'fas fa-gem' },            // Premium
                        6: { class: 'badge-secondary', icon: 'fas fa-gift' }         // Gratis
                    };

                    const info = planInfo[row.planes_id] || { class: 'badge-light', icon: 'fas fa-question-circle' };

                    return `<span class="badge ${info.class} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">
                                <i class="${info.icon}" style="margin-right: 5px;"></i>${data.nombre}
                            </span>`;
                }
            },
            {
                data: "sistema",
                render: function(data) {
                    let badgeClass, iconClass;

                    switch (data.nombre) {
                        case 'CAMI':
                            badgeClass = 'badge-info';
                            iconClass = 'fas fa-stethoscope'; // ícono para sistema médico
                            break;
                        case 'IZZY':
                            badgeClass = 'badge-success';
                            iconClass = 'fas fa-store'; // ícono para sistema comercial
                            break;
                        case 'MONISYS':
                            badgeClass = 'badge-warning';
                            iconClass = 'fas fa-chart-line'; // ícono para monitoreo o gestión
                            break;
                        default:
                            badgeClass = 'badge-secondary';
                            iconClass = 'fas fa-question-circle'; // ícono por defecto
                            break;
                    }

                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">
                                <i class="${iconClass}" style="margin-right: 5px;"></i>${data.nombre}
                            </span>`;
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
                    const isValid = data == 1;
                    const badgeClass = isValid ? 'badge-success' : 'badge-secondary';
                    const iconClass = isValid ? 'fas fa-check-circle' : 'fas fa-times-circle';
                    const text = isValid ? 'Sí' : 'No';

                    return `<span class="badge ${badgeClass} badge-pill" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">
                                <i class="${iconClass}" style="margin-right: 5px;"></i>${text}
                            </span>`;
                }
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';
                        var icon = data == 1 ? 
                            '<i class="fas fa-check-circle mr-1"></i>' : 
                            '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ? 
                            'badge badge-pill badge-success' : 
                            'badge badge-pill badge-danger';
                        
                        return '<span class="' + badgeClass + 
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }
                    return data;
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
                        <button class="table_editar btn ocultar btn-editar-asignacion" 
                                data-id="${data.server_customers_id}"
                                data-cliente-id="${data.cliente_id}" 
                                data-plan-id="${data.planes_id}"
                                data-sistema-id="${data.sistema_id}"
                                data-user-extra="${data.user_extra}"
                                data-validar="${data.validar}"
                                data-estado="${data.estado}">
                            <i class="fas fa-edit fa-lg"></i> Editar
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