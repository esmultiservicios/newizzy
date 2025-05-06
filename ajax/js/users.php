<script>
$(document).ready(function() {
    // Inicializar funciones al cargar la página
    listar_usuarios();
    getTipoUsuario();
    getPrivilegio();
    getEmpresaUsers();
    getColaboradoresUsuario();
    getPuestosColaboradoresUsuarios();

	$('#form_main_usuarios #search').on("click", function (e) {
		e.preventDefault();
		listar_usuarios();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_usuarios').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('.selectpicker') // Usa `this` para referenciar el formulario actual
			.val('')
			.selectpicker('refresh');

			listar_usuarios();
	});    

    // Cambio entre pestañas de colaborador existente/nuevo
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr("href");
        
        if(target === '#nuevo') {
            $('#es_nuevo_colaborador').val('1');
        } else {
            $('#es_nuevo_colaborador').val('0');
        }
    });
    
    // Cuando se selecciona un colaborador existente
    $('#formUsers #colaboradores_id').on('changed.bs.select', function(e) {
        var colaborador_id = $(this).val();

        if(colaborador_id) {
            obtenerInfoColaborador(colaborador_id);
        } else {
            $('#info_colaborador').hide();
        }
    });

    // Configuración del formulario al mostrarse el modal
    $("#modal_registrar_usuarios").on('shown.bs.modal', function() {
        $(this).find('#colaboradores_id').focus();
    });

    // Cambio en el switch de estado
    $('#formUsers .switch').change(function() {
        if ($('input[name=estado_usuario]').is(':checked')) {
            $('#formUsers #label_usuarios_activo').html("Activo");
            return true;
        } else {
            $('#formUsers #label_usuarios_activo').html("Inactivo");
            return false;
        }
    });
});

// Función para listar usuarios en DataTable
var listar_usuarios = function() {
    var estado = $('#form_main_usuarios #estado_usuarios').val();

    var table_usuarios = $("#dataTableUsers").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableUsuarios.php",
            "data": {
                "estado": estado
            }
        },
        "columns": [{
                "data": "colaborador"
            },
            {
                "data": "correo"
            },
            {
                "data": "tipo_usuario"
            },
            {
                "data": "privilegio"
            },
            {
                "data": "empresa"
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
                "defaultContent": "<button class='table_actualizar btn btn-secondary ocultar'><span class='fas fa-sync-alt fa-lg'></span>Restablecer</button>"
            },
            {
                "defaultContent": "<button class='table_editar btn ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "27.28%",
                targets: 0
            },
            {
                width: "24.28%",
                targets: 1
            },
            {
                width: "14.28%",
                targets: 2
            },
            {
                width: "14.28%",
                targets: 3
            },
            {
                width: "27.28%",
                targets: 4
            },
            {
                width: "2.28%",
                targets: 5
            },
            {
                width: "2.28%",
                targets: 6
            },
            {
                width: "2.28%",
                targets: 7
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Usuarios',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_usuarios();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Usuarios',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_usuarios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Usuarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Usuarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
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
        }
    });
    
    table_usuarios.search('').draw();
    $('#buscar').focus();

    actualizar_usuarios_dataTable("#dataTableUsers tbody", table_usuarios);
    editar_usuarios_dataTable("#dataTableUsers tbody", table_usuarios);
    eliminar_usuarios_dataTable("#dataTableUsers tbody", table_usuarios);
}

// Función para manejar el restablecimiento de contraseña
var actualizar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_actualizar");
    $(tbody).on("click", "button.table_actualizar", function() {
        var data = table.row($(this).parents("tr")).data();

        Swal.fire({
            title: "¿Está seguro?",
            text: "¿Desea resetear la contraseña al usuario: " + data.colaborador + "?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "¡Sí, resetear!",
            cancelButtonText: "Cancelar",
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                resetearContra(data.users_id, data.server_customers_id);
            }
        });
    });
}

// Función para editar usuario
var editar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL; ?>core/editarUsuarios.php';
        $('#formUsers #usuarios_id').val(data.users_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: {users_id: data.users_id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Configurar formulario para edición
                    $('#formUsers').attr('data-form', 'update');
                    $('#formUsers').attr('action', '<?php echo SERVERURL; ?>ajax/modificarUsersAjax.php');
                    
                    // Mostrar/ocultar botones
                    $('#reg_usuario').hide();
                    $('#edi_usuario').show();
                    
                    // Llenar datos del colaborador
                    $('#formUsers #colaboradores_id').val(response.data.colaboradores_id);
                    $('#formUsers #es_nuevo_colaborador').val('0');
                    
                    // Seleccionar colaborador en el select
                    $('#formUsers #colaboradores_id').val(response.data.colaboradores_id);
                    $('#formUsers #colaboradores_id').selectpicker('refresh');
                    
                    // Mostrar información del colaborador
                    $('#formUsers #info_nombre').text(response.data.nombre_completo);
                    $('#formUsers #info_identidad').text(response.data.identidad || 'No especificado');
                    $('#formUsers #info_telefono').text(response.data.telefono || 'No especificado');
                    $('#formUsers #info_fecha_ingreso').text(response.data.fecha_ingreso);
                    $('#formUsers #info_estado').html(response.data.estado_colaborador == 1 ? 
                        '<span class="badge badge-success">Activo</span>' : 
                        '<span class="badge badge-danger">Inactivo</span>');
                    $('#formUsers #info_colaborador').show();
                    
                    // Llenar datos del usuario
                    $('#formUsers #correo_usuario').val(response.data.correo);
                    $('#formUsers #empresa_usuario').val(response.data.empresa_id);
                    $('#formUsers #empresa_usuario').selectpicker('refresh');
                    $('#formUsers #tipo_user').val(response.data.tipo_user_id);
                    $('#formUsers #tipo_user').selectpicker('refresh');
                    $('#formUsers #privilegio_id').val(response.data.privilegio_id);
                    $('#formUsers #privilegio_id').selectpicker('refresh');
                    $('#formUsers #server_customers_id').val(response.data.server_customers_id);
                    
                    // Estado del usuario
                    if(response.data.estado == 1) {
                        $('#formUsers #estado_usuario').prop('checked', true);
                        $('#formUsers #label_usuarios_activo').html("Activo");
                    } else {
                        $('#formUsers #estado_usuario').prop('checked', false);
                        $('#formUsers #label_usuarios_activo').html("Inactivo");
                    }
                    
                    // Mostrar modal
                    $('#modal_registrar_usuarios').modal({
                        show: true,
                        keyboard: false,
                        backdrop: 'static'
                    });
                } else {
                    showNotify("error", "Error", response.message || "Error al cargar datos del usuario");
                }
            },
            error: function() {
                showNotify("error", "Error", "Error de conexión al cargar datos del usuario");
            }
        });
    });
}

// Función para eliminar usuario
var eliminar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var mensajeHTML = `¿Desea eliminar permanentemente el usuario?<br><br>
                        <strong>Nombre:</strong> ${data.colaborador}<br>
                        <strong>Correo:</strong> ${data.correo}`;
                                                
        Swal.fire({
            title: "Confirmar eliminación",
            html: mensajeHTML,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarUsersAjax.php',
                    data: {
                        users_id: data.users_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Procesando',
                            html: 'Eliminando usuario...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },                    
                    success: function(response) {
                        Swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false);
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function() {
                        Swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
    });
}

// Función para abrir modal de registro
function modal_usuarios() {
    // Resetear formulario completamente
    $('#formUsers')[0].reset();
    
    // Limpiar y resetear selects
    $('#buscar_colaborador').empty();
    $('#buscar_colaborador').selectpicker('refresh');
    $('#empresa_usuario').val('').selectpicker('refresh');
    $('#privilegio_id').val('').selectpicker('refresh');
    $('#tipo_user').val('').selectpicker('refresh');
    $('#puesto_colaborador').val('').selectpicker('refresh');
    
    // Ocultar información del colaborador
    $('#info_colaborador').hide();
    $('#es_nuevo_colaborador').val('0');
    
    // Mostrar pestaña de colaborador existente por defecto
    $('#existente-tab').tab('show');
    
    // Configurar formulario para nuevo registro
    $('#formUsers').attr('data-form', 'save');
    $('#formUsers').attr('action', '<?php echo SERVERURL; ?>ajax/agregarUsuarioAjax.php');
    
    // Mostrar/ocultar botones
    $('#reg_usuario').show();
    $('#edi_usuario').hide();
    
    // Establecer fecha actual
    var fechaActual = new Date().toISOString().split('T')[0];
    $('#fecha_ingreso_colaborador').val(fechaActual);
    
    // Estado activo por defecto
    $('#estado_usuario').prop('checked', true);
    $('#label_usuarios_activo').html("Activo");
    
    // Mostrar modal
    $('#modal_registrar_usuarios').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

// Función para obtener información de un colaborador
function obtenerInfoColaborador(colaborador_id) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getColaboradorInfo.php',
        type: 'POST',
        data: {colaborador_id: colaborador_id},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#colaboradores_id').val(response.data.colaboradores_id);
                $('#info_nombre').text(response.data.nombre + ' ' + response.data.apellido);
                $('#info_identidad').text(response.data.identidad || 'No especificado');
                $('#info_telefono').text(response.data.telefono || 'No especificado');
                $('#info_fecha_ingreso').text(response.data.fecha_ingreso);
                $('#info_estado').html(response.data.estado == 1 ? 
                    '<span class="badge badge-success">Activo</span>' : 
                    '<span class="badge badge-danger">Inactivo</span>');
                
                $('#info_colaborador').show();
            } else {
                showNotify("error", "Error", response.message || "Error al obtener información del colaborador");
                $('#info_colaborador').hide();
            }
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al obtener información del colaborador");
            $('#info_colaborador').hide();
        }
    });
}

// Función para resetear contraseña
function resetearContra(users_id, server_customers_id) {
    Swal.fire({
        title: 'Procesando',
        html: 'Reseteando contraseña...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>ajax/resetearContrasenaAjax.php',
        data: {
            users_id: users_id,
            server_customers_id: server_customers_id,
        },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if(response.status === "success") {
                showNotify("success", response.title, response.message);
            } else {
                showNotify("error", response.title, response.message);
            }
        },
        error: function() {
            Swal.close();
            showNotify("error", "Error", "Error de conexión al resetear contraseña");
        }
    });
}

// Función para obtener tipos de usuario
function getTipoUsuario() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getTipoUsuario.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formUsers #tipo_user');
            select.empty();
            
            if(response.success) {
                response.data.forEach(tipo => {
                    select.append(`
                        <option value="${tipo.tipo_user_id}">
                            ${tipo.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay tipos de usuario disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron tipos de usuario");
            }
            
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar tipos de usuario");
            $('#formUsers #tipo_user').html('<option value="">Error al cargar</option>');
            $('#formUsers #tipo_user').selectpicker('refresh');
        }
    });
}

// Función para obtener privilegios
function getPrivilegio() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getPrivilegio.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formUsers #privilegio_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(privilegio => {
                    select.append(`
                        <option value="${privilegio.privilegio_id}">
                            ${privilegio.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay privilegios disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron privilegios");
            }
            
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar privilegios");
            $('#formUsers #privilegio_id').html('<option value="">Error al cargar</option>');
            $('#formUsers #privilegio_id').selectpicker('refresh');
        }
    });
}

// Función para obtener empresas
function getEmpresaUsers() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formUsers #empresa_usuario');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`
                        <option value="${empresa.empresa_id}">
                            ${empresa.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formUsers #empresa_usuario').html('<option value="">Error al cargar</option>');
            $('#formUsers #empresa_usuario').selectpicker('refresh');
        }
    });
}

function getColaboradoresUsuario() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getColaboradores.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formUsers #colaboradores_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    select.append(`
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${cliente.identidad || 'Sin RTN o Identidad'}">
                            ${colaborador.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay colaboradores disponibles</option>');
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar colaboradores");
            $('#formUsers #colaboradores_id').html('<option value="">Error al cargar</option>');
            $('#formUsers #colaboradores_id').selectpicker('refresh');
        }
    });
}

// Función para obtener puestos de colaboradores
function getPuestosColaboradoresUsuarios() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getPuestoColaboradores.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formUsers #puesto_colaborador');
            select.empty();
            
            if(response.success) {
                response.data.forEach(puesto => {
                    select.append(`
                        <option value="${puesto.puestos_id}">
                            ${puesto.nombre}
                        </option>
                    `);
                });
            } else {
                select.append('<option value="">No hay puestos disponibles</option>');
            }
            
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar puestos");
            $('#formUsers #puesto_colaborador').html('<option value="">Error al cargar</option>');
            $('#formUsers #puesto_colaborador').selectpicker('refresh');
        }
    });
}

$('#btnNuevoPuesto').on('click', function() {
    modal_puestos();
});
</script>