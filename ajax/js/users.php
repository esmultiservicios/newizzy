<script>
$(() => {
    listar_usuarios();
    getTipoUsuario();
    getPrivilegio();
    getEmpresaUsers();
    getColaboradoresUsuario();

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
    $('#buscar_colaborador').on('changed.bs.select', function(e) {
        var colaborador_id = $(this).val();
        
        if(colaborador_id) {
            obtenerInfoColaborador(colaborador_id);
        } else {
            $('#info_colaborador').hide();
        }
    });
    
    // Botón para ver información del colaborador
    $('#btn_ver_info_colaborador').click(function() {
        var colaborador_id = $('#buscar_colaborador').val();
        
        if(colaborador_id) {
            obtenerInfoColaborador(colaborador_id);
        } else {
            Swal.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione un colaborador primero',
                icon: 'warning'
            });
        }
    });   

    // Evento para el botón "Ver Información"
    $('#btn_ver_info_colaborador').click(function() {
        // Obtener el ID del colaborador seleccionado
        var colaborador_id = $('#buscar_colaborador').val();
        
        if (colaborador_id) {
            obtenerInfoColaborador(colaborador_id); // Llamar a tu función existente
        } else {
            Swal.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione un colaborador primero',
                icon: 'warning'
            });
        }
    });     
});

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
                $('#info_identidad').text(response.data.identidad);
                $('#info_telefono').text(response.data.telefono);
                $('#info_fecha_ingreso').text(response.data.fecha_ingreso);
                $('#info_estado').html(response.data.estado == 1 ? '<span class="badge badge-pill badge-success" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">Activo</span>' : '<span class="badge badge-pill badge-danger" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">Inactivo</span>');
                
                $('#info_colaborador').show();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error'
                });
                $('#info_colaborador').hide();
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Error al obtener información del colaborador',
                icon: 'error'
            });
            $('#info_colaborador').hide();
        }
    });
}

//INICIO ACCIONES FROMULARIO USUARIOS
var listar_usuarios = function() {
    var table_usuarios = $("#dataTableUsers").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableUsuarios.php",
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
                "data": "estado"
            },
            {
                "data": "empresa"
            },
            {
                "defaultContent": "<button class='table_actualizar btn btn-dark ocultar'><span class='fas fa-sync-alt fa-lg'></span>Restablecer</button>"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>"
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
					if (imagen) { // Solo agrega la imagen si 'imagen' tiene contenido válido
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

var actualizar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_actualizar");
    $(tbody).on("click", "button.table_actualizar", function() {
        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Esta seguro?",
            text: "¿Desea resetear la contraseña al usuario: " + consultarNombre(data.users_id) +
                    "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Si, deseo resetear la contraseña!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        }).then((willConfirm) => {
            if (willConfirm === true) {
                resetearContra(data.users_id, data.server_customers_id);
            }
        });
    });
}

var editar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL; ?>core/editarUsuarios.php';
        $('#formUsers #usuarios_id').val(data.users_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formUsers').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formUsers').attr({
                    'data-form': 'update'
                });
                $('#formUsers').attr({
                    'action': '<?php echo SERVERURL; ?>ajax/modificarUsersAjax.php'
                });
                $('#formUsers')[0].reset();
                $('#reg_usuario').hide();
                $('#edi_usuario').show();
                $('#delete_usuario').hide();
                $('#formUsers #usuarios_colaborador_id').val(valores[0]);
                $('#formUsers #colaborador_id_usuario').val(valores[0]);
                $('#formUsers #colaborador_id_usuario').selectpicker('refresh');
                $('#formUsers #nickname').val(valores[2]);
                $('#formUsers #pass').attr('disabled', true);
                $('#formUsers #correo_usuario').val(valores[3]);
                $('#formUsers #empresa_usuario').val(valores[4]);
                $('#formUsers #empresa_usuario').selectpicker('refresh');
                $('#formUsers #tipo_user').val(valores[5]);
                $('#formUsers #tipo_user').selectpicker('refresh');
                $('#formUsers #privilegio_id').val(valores[7]);
                $('#formUsers #server_customers_id').val(valores[8]);
                $('#formUsers #privilegio_id').selectpicker('refresh');

                if (valores[6] == 1) {
                    $('#formUsers #usuarios_activo').attr('checked', true);
                } else {
                    $('#formUsers #usuarios_activo').attr('checked', false);
                }

                $('#info_identidad').text(response.data.identidad);
                
                //HABILITAR OBJETOS
                $('#formUsers #pass').attr('readonly', false);
                $('#formUsers #correo_usuario').attr('readonly', false);
                $('#formUsers #empresa_usuario').attr('disabled', false);
                $('#formUsers #tipo_user').attr('disabled', false);
                $('#formUsers #estado_usuario').attr('disabled', false);
                $('#formUsers #privilegio_id').attr('disabled', false);
                $('#formUsers #usuarios_activo').attr('disabled', false);
                $('#formUsers #estado_usuarios').show();

                //DESHABILITAR OBJETOS
                $('#formUsers #nickname').attr('readonly', true);
                $('#formUsers #correo_usuario').attr('readonly', true);

                $('#formUsers #proceso_usuarios').val("Editar");
                $('#formUsers #grupo_buscar_colaboradores').attr('disabled', true);
                $('#modal_registrar_usuarios').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_usuarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var users_id = data.users_id;
        var nombre = data.colaborador; 
        var correo = data.correo;

        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el usuario?<br><br>
                        <strong>Nombre:</strong> ${nombre}<br>
                        <strong>Correo:</strong> ${correo}`;
                                                
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarUsersAjax.php',
                    data: {
                        users_id: users_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    before: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },                    
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false); // Recargar tabla sin resetear paginación
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                        console.error("Error en la solicitud AJAX:", error);
                    }
                });
            }
        });

    });
}

function modal_usuarios() {
    // Resetear formulario completamente
    $('#formUsers')[0].reset();
    
    // Limpiar y resetear select de buscar colaborador
    $('#buscar_colaborador').empty();
    $('#buscar_colaborador').selectpicker('refresh');
    $('#buscar_colaborador').selectpicker('val', '');
    
    // Ocultar información del colaborador
    $('#info_colaborador').hide();
    $('#es_nuevo_colaborador').val('0');
    
    // Mostrar pestaña de colaborador existente por defecto
    $('#existente-tab').tab('show');
    
    // Resetear campos de nuevo colaborador
    $('#nombre_colaborador').val('');
    $('#identidad_colaborador').val('');
    $('#telefono_colaborador').val('');
    $('#fecha_ingreso_colaborador').val(new Date().toISOString().split('T')[0]);
    $('#puesto_colaborador').val('').selectpicker('refresh');
    
    // Resetear credenciales de usuario
    $('#correo_usuario').val('');
    $('#empresa_usuario').val('').selectpicker('refresh');
    $('#privilegio_id').val('').selectpicker('refresh');
    $('#tipo_user').val('').selectpicker('refresh');
    $('#estado_usuario').prop('checked', true);
    
    // Configurar formulario para nuevo registro
    $('#formUsers').attr('data-form', 'save');
    $('#formUsers').attr('action', '<?php echo SERVERURL; ?>ajax/agregarUsuarioAjax.php');
    
    // Mostrar/ocultar botones
    $('#reg_usuario').show();
    $('#edi_usuario').hide();
    
    // Cargar selects
    getEmpresaUsers();
    getTipoUsuario();
    getPrivilegio();
    getPuestosColaboradoresUsuarios();
    getEmpresaUsuarios();
    getColaboradoresUsuario();

    // Establecer fecha actual
    var fechaActual = new Date().toISOString().split('T')[0];
    $('#fecha_ingreso_colaborador').val(fechaActual);
    
    // Mostrar modal
    $('#modal_registrar_usuarios').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getPuestosColaboradoresUsuarios() {
    var url = '<?php echo SERVERURL; ?>core/getPuestoColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #puesto_colaborador').html("");
            $('#formUsers #puesto_colaborador').html(data);
            $('#formUsers #puesto_colaborador').selectpicker('refresh');
        }
    });
}

function getEmpresaUsuarios() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #colaborador_empresa_id').html("");
            $('#formUsers #colaborador_empresa_id').html(data);
            $('#formUsers #colaborador_empresa_id').selectpicker('refresh');
        }
    });
}

function consultarNombre(users_id) {
    var url = '<?php echo SERVERURL; ?>core/getUsuarioNombre.php';
    var resp;

    $.ajax({
        type: 'POST',
        url: url,
        data: 'users_id=' + users_id,
        async: false,
        success: function(data) {
            var valores = eval(data);
            resp = valores[0];
        }
    });
    return resp;
}

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
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar tipos de usuario");
            $('#formUsers #tipo_user').html('<option value="">Error al cargar</option>');
            $('#formUsers #tipo_user').selectpicker('refresh');
        }
    });
}

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
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar privilegios");
            $('#formUsers #privilegio_id').html('<option value="">Error al cargar</option>');
            $('#formUsers #privilegio_id').selectpicker('refresh');
        }
    });
}

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
                
                // Establecer valor por defecto si existe
                if(response.data.length > 0) {
                    select.val(1); // O el valor que necesites por defecto
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            
            select.selectpicker('refresh');
        },
        error: function(xhr) {
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
            const select = $('#formUsers #buscar_colaborador');
            select.empty();
            
            if(response.success) {
                response.data.forEach(colaborador => {
                    select.append(`
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${colaborador.identidad || 'Sin identidad'}">
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
            $('#formUsers #buscar_colaborador').html('<option value="">Error al cargar</option>');
            $('#formUsers #buscar_colaborador').selectpicker('refresh');
        }
    });
}

function resetearContra(users_id, server_customers_id) {
    var url = '<?php echo SERVERURL; ?>ajax/resetearContrasenaAjax.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            users_id: users_id,
            server_customers_id: server_customers_id,
        },
        success: function(registro) {
            if (registro == 1) {
                swal({
                    title: "Success",
                    text: "Contraseña cambiada correctamente",
                    icon: "success",
                    timer: 3000, //timeOut for auto-close	
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera				
                });
                return false;
            } else {
                swal({
                    title: "Error",
                    text: "Error al resetear la contraseña",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
                return false;
            }
        }
    });
    return false;
}

$(document).ready(function() {
    $("#modal_registrar_usuarios").on('shown.bs.modal', function() {
        $(this).find('#formUsers #colaborador_id_usuario').focus();
    });

    $("#modal_buscar_colaboradores").on('shown.bs.modal', function() {
        $(this).find('#DatatableColaboradoresBusqueda #buscar').focus();
    });

    $("#modal_buscar_colaboradores_usuarios").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_coloboradores #buscar').focus();
    });    
});

$('#formUsers #label_usuarios_activo').html("Activo");

$('#formUsers .switch').change(function() {
    if ($('input[name=usuarios_activo]').is(':checked')) {
        $('#formUsers #label_usuarios_activo').html("Activo");
        return true;
    } else {
        $('#formUsers #label_usuarios_activo').html("Inactivo");
        return false;
    }
});
</script>