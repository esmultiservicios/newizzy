<script>
$(document).ready(function() {
    listar_usuarios();
    getTipoUsuario();
    getPrivilegio();
    getEmpresaUsers();
    getColaboradoresUsuario();
});


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

/*INICIO FORMULARIO USUARIOS*/
function modal_usuarios() {
    getEmpresaUsers();
    getTipoUsuario();
    getPrivilegio();
    $('#formUsers').attr({
        'data-form': 'save'
    });
    $('#formUsers').attr({
        'action': '<?php echo SERVERURL; ?>ajax/agregarUsuarioAjax.php'
    });
    $('#formUsers')[0].reset();
    $('#reg_usuario').show();
    $('#edi_usuario').hide();
    $('#delete_usuario').hide();
    $('#formUsers #proceso_usuarios').val("Registro");
    $('#formUsers #grupo_buscar_colaboradores').attr('disabled', false);

    //HABILITAR OBJETOS
    $('#formUsers #nickname').attr('readonly', false);
    $('#formUsers #pass').attr('readonly', false);
    $('#formUsers #correo_usuario').attr('readonly', false);
    $('#formUsers #empresa_usuario').attr('disabled', false);
    $('#formUsers #tipo_user').attr('disabled', false);
    $('#formUsers #estado_usuario').attr('disabled', false);
    $('#formUsers #privilegio_id').attr('disabled', false);
    $('#formUsers #usuarios_activo').attr('disabled', false);
    $('#formUsers #estado_usuarios').hide();

    $('#formUsers #pass').attr('disabled', false);
    $('#modal_registrar_usuarios').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
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
    var url = '<?php echo SERVERURL; ?>core/getTipoUsuario.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #tipo_user').html("");
            $('#formUsers #tipo_user').html(data);
            $('#formUsers #tipo_user').selectpicker('refresh');
        }
    });
}

function getPrivilegio() {
    var url = '<?php echo SERVERURL; ?>core/getPrivilegio.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #privilegio_id').html("");
            $('#formUsers #privilegio_id').html(data);
            $('#formUsers #privilegio_id').selectpicker('refresh');
        }
    });
}

function getEmpresaUsers() {
    var url = '<?php echo SERVERURL; ?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #empresa_usuario').html("");
            $('#formUsers #empresa_usuario').html(data);
            $('#formUsers #empresa_usuario').selectpicker('refresh');
        }
    });
}

function getColaboradoresUsuario() {
    var url = '<?php echo SERVERURL; ?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formUsers #colaborador_id_usuario').html("");
            $('#formUsers #colaborador_id_usuario').html(data);
            $('#formUsers #colaborador_id_usuario').selectpicker('refresh');
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
});

$(document).ready(function() {
    $("#modal_buscar_colaboradores").on('shown.bs.modal', function() {
        $(this).find('#DatatableColaboradoresBusqueda #buscar').focus();
    });
});

$(document).ready(function() {
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