<script>
$(document).ready(function() {
    listar_correos_configuracion();
    getSMTPSecure();
    getTipoCorreo();
});

//INICIO CORREO
var listar_correos_configuracion = function() {
    var table_correos_configuracion = $("#dataTableConfCorreos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableConfCorreos.php"
        },
        "columns": [{
                "data": "tipo_correo"
            },
            {
                "data": "server"
            },
            {
                "data": "correo"
            },
            {
                "data": "port"
            },
            {
                "data": "smtp_secure"
            },
            {
                "defaultContent": "<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, //esta se encuenta en el archivo main.js
        "dom": dom,
        "columnDefs": [{
                width: "22.66%",
                targets: 0
            },
            {
                width: "23.66%",
                targets: 1
            },
            {
                width: "23.66%",
                targets: 2
            },
            {
                width: "6.66%",
                targets: 3
            },
            {
                width: "6.66%",
                targets: 4
            },
            {
                width: "6.66%",
                targets: 5
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Correos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_correos_configuracion();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Destinatarios',
                titleAttr: 'Agregar Correos para enviar notificaciones',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalDestinatarios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Correos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Correos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
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
    table_correos_configuracion.search('').draw();
    $('#buscar').focus();

    edit_correos_configuracion_dataTable("#dataTableConfCorreos tbody", table_correos_configuracion);
}

var edit_correos_configuracion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarCorreo.php';
        $('#formConfEmails #correo_id').val(data.correo_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formConfEmails').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formConfEmails').attr({
                    'data-form': 'update'
                });
                $('#formConfEmails').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarCorreoAjax.php'
                });
                $('#formConfEmails')[0].reset();
                $('#test_confEmails').show();
                $('#edi_confEmails').show();
                $('#formConfEmails #pro_correos').val("Editar");
                $('#formConfEmails #tipo_correo_confEmail').val(valores[0]);
                $('#formConfEmails #tipo_correo_confEmail').selectpicker('refresh');
                $('#formConfEmails #serverConfEmail').val(valores[1]);
                $('#formConfEmails #correoConfEmail').val(valores[2]);
                $('#formConfEmails #puertoConfEmail').val(valores[3]);
                $('#formConfEmails #smtpSecureConfEmail').val(valores[4]);
                $('#formConfEmails #smtpSecureConfEmail').selectpicker('refresh');
                $('#formConfEmails #passConfEmail').val(valores[6]);

                //DESHABILITAR OBJETOS
                $('#formConfEmails #tipo_correo_confEmail').attr('disabled', true);

                $('#modalConfEmails').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

$("#test_confEmails").on("click", function(e) {
    e.preventDefault();
    var server = $('#formConfEmails #serverConfEmail').val();
    var correo = $('#formConfEmails #correoConfEmail').val();
    var password = $('#formConfEmails #passConfEmail').val();
    var port = $('#formConfEmails #puertoConfEmail').val();
    var smtpSecure = $('#formConfEmails #smtpSecureConfEmail').val();

    testEmail(server, correo, password, port, smtpSecure)
});

function testEmail(server, correo, password, port, smtpSecure) {
    var url = '<?php echo SERVERURL;?>core/testEmail.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'server=' + server + '&correo=' + correo + '&password=' + password + '&port=' + port +
            '&smtpSecure=' + smtpSecure,
        success: function(data) {
            if (data == 1) {
                showNotify('success', 'Success', 'Conexión realizada satisfactoriamente');
            } else {
                showNotify('error', 'Error', 'Credenciales invalidas, por favor corregir, también recuerde en su servidor de correo: Activar Aplicaciones poco seguras (SmtpClientAuthentication)');
            }
        }
    });
}

function getSMTPSecure() {
    var url = '<?php echo SERVERURL;?>core/getSMTPSecure.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formConfEmails #smtpSecureConfEmail').html("");
            $('#formConfEmails #smtpSecureConfEmail').html(data);
            $('#formConfEmails #smtpSecureConfEmail').selectpicker('refresh');
        }
    });
}
//FIN CORREO

function getTipoCorreo() {
    var url = '<?php echo SERVERURL;?>core/getTipoCorreo.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formConfEmails #tipo_correo_confEmail').html("");
            $('#formConfEmails #tipo_correo_confEmail').html(data);
            $('#formConfEmails #tipo_correo_confEmail').selectpicker('refresh');
        }
    });
}

/*INICIO DESTINATARIOS*/
function modalDestinatarios() {
    listar_destinatarios();

    $('#formDestinatarios').attr({
        'data-form': 'save'
    });
    $('#formDestinatarios').attr({
        'action': '<?php echo SERVERURL;?>ajax/addDestinatario.php'
    });
    $('#formDestinatarios')[0].reset();
    $('#reg_destinatarios').show();

    //HABILITAR OBJETOS
    $('#formDestinatarios #correo').attr('readonly', false);
    $('#formDestinatarios #nombre').attr('readonly', false);;

    $('#formDestinatarios #proceso_destinatarios').val("Registro Destinatarios");
    $('#modalRegistrarDestinatarios').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$(document).ready(function() {
    $("#modalRegistrarDestinatarios").on('shown.bs.modal', function() {
        $(this).find('#formDestinatarios #correo').focus();
    });
});

var listar_destinatarios = function() {
    var table_destinatarios = $("#DatatableDestinatarios").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableDestinatarios.php"
        },
        "columns": [{
                "data": "correo"
            },
            {
                "data": "nombre"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "33.33%",
                targets: 0
            },
            {
                width: "33.33%",
                targets: 1
            },
            {
                width: "33.33%",
                targets: 2
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Destinatarios',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_destinatarios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Destinatarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Destinatarios',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1]
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
    table_destinatarios.search('').draw();
    $('#buscar').focus();

    eliminar_destinatarios_dataTable("#DatatableDestinatarios tbody", table_destinatarios);
}

var eliminar_destinatarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Estas seguro?",
            text: "¿Desea eliminar el destinatario " + data.colaborador,
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, eliminar el correo!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        }).then((willConfirm) => {
            if (willConfirm === true) {
                elminarDestinatario(data.notificaciones_id);
            }
        });
    });
}

function elminarDestinatario(notificaciones_id) {
    var url = '<?php echo SERVERURL;?>core/deleteDestinatarios.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'notificaciones_id=' + notificaciones_id,
        success: function(data) {
            if (data == 1) {
                showNotify('success', 'Success', 'El destinatario ha sido eliminada correctamente');
                listar_destinatarios();
                $('#formDestinatarios #correo').focus();
            } else {
                showNotify('error', 'Error', 'Lo sentimos no se puede eliminar el destinatario');
            }
        }
    });
}
/*FIN DESTINATARIOS*/
</script>