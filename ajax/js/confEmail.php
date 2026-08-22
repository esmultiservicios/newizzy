<script>
/* =========================================================
   INICIALIZACIÓN DEL MÓDULO - SIN $(document).ready()
   ========================================================= */
function inicializarModuloCorreos() {
    listar_correos_configuracion();
    getSMTPSecure();
    getTipoCorreo();

    $('#formConfEmails #metodoEnvioConfEmail').off('changed.bs.select change');
    $('#formConfEmails #metodoEnvioConfEmail').on('changed.bs.select change', function() {
        aplicarVistaMetodoCorreo();
    });

    $("#modalRegistrarDestinatarios").off('shown.bs.modal');
    $("#modalRegistrarDestinatarios").on('shown.bs.modal', function() {
        $(this).find('#formDestinatarios #correo').focus();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarModuloCorreos);
} else {
    inicializarModuloCorreos();
}

/* =========================================================
   HEADER DINÁMICO - CORREOS
   ========================================================= */
function construirHeaderDataTableConfCorreos() {
    var $tabla = $("#dataTableConfCorreos");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Tipo Correo</th>' +
                '<th>Método</th>' +
                '<th>Servidor</th>' +
                '<th>Correo</th>' +
                '<th>Puerto</th>' +
                '<th>SMTP Secure</th>' +
                '<th>Graph User</th>' +
            '</tr>' +
        '</thead>'
    );
}

/* =========================================================
   MOSTRAR / OCULTAR CAMPOS SEGÚN MÉTODO
   ========================================================= */
function aplicarVistaMetodoCorreo() {
    var metodo = ($('#formConfEmails #metodoEnvioConfEmail').val() || 'SMTP').toUpperCase();

    if (metodo === 'GRAPH') {
        $('.seccion-smtp').hide();
        $('.seccion-graph').show();

        $('.ayuda-smtp').hide();
        $('.ayuda-graph').show();

        $('#formConfEmails #serverConfEmail').prop('required', false);
        $('#formConfEmails #passConfEmail').prop('required', false);
        $('#formConfEmails #puertoConfEmail').prop('required', false);
        $('#formConfEmails #smtpSecureConfEmail').prop('required', false);

        $('#formConfEmails #tenantIdConfEmail').prop('required', true);
        $('#formConfEmails #clientIdConfEmail').prop('required', true);
        $('#formConfEmails #graphUserConfEmail').prop('required', true);
    } else {
        $('.seccion-smtp').show();
        $('.seccion-graph').hide();

        $('.ayuda-smtp').show();
        $('.ayuda-graph').hide();

        $('#formConfEmails #serverConfEmail').prop('required', true);
        $('#formConfEmails #puertoConfEmail').prop('required', true);
        $('#formConfEmails #smtpSecureConfEmail').prop('required', true);

        $('#formConfEmails #tenantIdConfEmail').prop('required', false);
        $('#formConfEmails #clientIdConfEmail').prop('required', false);
        $('#formConfEmails #clientSecretConfEmail').prop('required', false);
        $('#formConfEmails #graphUserConfEmail').prop('required', false);
    }

    $('#formConfEmails #metodoEnvioConfEmail').selectpicker('refresh');
    $('#formConfEmails #smtpSecureConfEmail').selectpicker('refresh');
    $('#formConfEmails #saveToSentItemsConfEmail').selectpicker('refresh');
}

/* =========================================================
   ALERTA Y PERMISOS DEL MODAL
   ========================================================= */
function insertarAlertaSeguridadCorreo(puedeEditar) {
    $('#alertaCorreoSeguridad').remove();

    var claseModo = puedeEditar ? '' : ' modo-lectura';
    var titulo = puedeEditar ? 'Configuración sensible de correo' : 'Modo solo lectura';
    var texto = puedeEditar
        ? 'Tenant ID y Client ID se muestran parcialmente por seguridad. El Client Secret VALUE y la contraseña SMTP nunca se muestran. Si desea reemplazarlos, escriba un valor nuevo completo.'
        : 'Esta configuración controla el envío de facturas, notificaciones, recuperación de contraseña e inicios de sesión. Su usuario puede ver esta pantalla, pero no tiene permisos para modificarla.';

    var alerta = '' +
        '<div id="alertaCorreoSeguridad" class="alerta-correo-seguridad' + claseModo + '">' +
            '<div class="alerta-icono">' +
                '<i class="fas fa-shield-alt"></i>' +
            '</div>' +
            '<div class="alerta-contenido">' +
                '<h6>' + titulo + '</h6>' +
                '<p>' + texto + '</p>' +
            '</div>' +
        '</div>';

    $('#formConfEmails').prepend(alerta);
}

function aplicarPermisosFormularioCorreo(puedeEditar) {
    puedeEditar = puedeEditar === true || puedeEditar === 1 || puedeEditar === '1';

    insertarAlertaSeguridadCorreo(puedeEditar);

    var $form = $('#formConfEmails');

    $form.find('input, textarea').removeClass('campo-solo-lectura');
    $form.find('select').prop('disabled', false).selectpicker('refresh');

    if (puedeEditar) {
        $form.find('input, textarea').prop('readonly', false);
        $('#formConfEmails #tipo_correo_confEmail').prop('disabled', true).selectpicker('refresh');

        $('#test_confEmails').show();
        $('#edi_confEmails').show();
    } else {
        $form.find('input, textarea').prop('readonly', true).addClass('campo-solo-lectura');
        $form.find('select').prop('disabled', true).selectpicker('refresh');

        $('#test_confEmails').hide();
        $('#edi_confEmails').hide();
    }

    /*
        Estos secretos nunca se muestran.
        Para administradores quedan editables para reemplazo.
        Para usuarios normales quedan solo lectura.
    */
    $('#formConfEmails #passConfEmail').val('');
    $('#formConfEmails #clientSecretConfEmail').val('');

    if (puedeEditar) {
        $('#formConfEmails #passConfEmail').prop('readonly', false).removeClass('campo-solo-lectura');
        $('#formConfEmails #clientSecretConfEmail').prop('readonly', false).removeClass('campo-solo-lectura');
    }
}

/* =========================================================
   UTILIDADES DE PRESENTACIÓN
   ========================================================= */
function textoSeguroCorreo(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return 'No configurado';
    }

    return valor;
}

function formatoDetalleCorreo(row) {
    var metodo = (row.metodo_envio || 'SMTP').toUpperCase();
    var badgeMetodo = metodo === 'GRAPH' ? 'badge-metodo-graph' : 'badge-metodo-smtp';
    var guardar = parseInt(row.save_to_sent_items || 0) === 1 ? 'Sí, guardar copia' : 'No guardar copia';

    return '' +
        '<div class="correo-detalle-premium">' +
            '<div class="correo-detalle-header">' +
                '<div>' +
                    '<h5 class="correo-detalle-title"><i class="fas fa-info-circle mr-1"></i>Detalle de configuración</h5>' +
                    '<p class="correo-detalle-subtitle">Información técnica del correo sin ampliar el tamaño de la tabla principal</p>' +
                '</div>' +
                '<span class="' + badgeMetodo + '">' + metodo + '</span>' +
            '</div>' +

            '<div class="correo-detalle-grid">' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Tipo de correo</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.tipo_correo) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Correo emisor</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.correo) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Servidor</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.server) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Puerto</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.port) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">SMTP Secure</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.smtp_secure) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Graph User</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.graph_user) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Tenant ID</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.tenant_id) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Client ID</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.client_id) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Client Secret</div>' +
                    '<div class="correo-detalle-value">Guardado de forma segura</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Guardar enviados</div>' +
                    '<div class="correo-detalle-value">' + guardar + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Estado</div>' +
                    '<div class="correo-detalle-value">' + (parseInt(row.estado || 0) === 1 ? 'Activo' : 'Inactivo') + '</div>' +
                '</div>' +

            '</div>' +
        '</div>';
}

/* =========================================================
   LISTAR CORREOS
   ========================================================= */
var listar_correos_configuracion = function() {

    if ($.fn.DataTable.isDataTable("#dataTableConfCorreos")) {
        $("#dataTableConfCorreos").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfCorreos();

    var table_correos_configuracion = $("#dataTableConfCorreos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/correo/llenarDataTableConfCorreos.php"
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="correo-acciones-wrap">' +

                            '<button type="button" class="btn-toggle-detalle-correo table_toggle_detalle" title="Mostrar detalle">' +
                                '<i class="fas fa-plus"></i>' +
                            '</button>' +

                            '<div class="dropdown acciones-dropdown">' +
                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +

                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                    '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                        '<span class="accion-icon accion-icon-editar">' +
                                            '<i class="fas fa-edit"></i>' +
                                        '</span>' +
                                        '<span class="accion-label">Editar</span>' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +

                        '</div>';
                }
            },
            { "data": "tipo_correo" },
            {
                "data": "metodo_envio",
                "className": "text-center text-nowrap",
                "render": function(data, type, row) {
                    var metodo = (data || 'SMTP').toUpperCase();

                    if (metodo === 'GRAPH') {
                        return '<span class="badge badge-success">GRAPH</span>';
                    }

                    return '<span class="badge badge-info">SMTP</span>';
                }
            },
            { "data": "server" },
            { "data": "correo" },
            {
                "data": "port",
                "className": "text-center text-nowrap"
            },
            {
                "data": "smtp_secure",
                "className": "text-center text-nowrap"
            },
            { "data": "graph_user" }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "12%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            { width: "14%", targets: 1 },
            { width: "10%", targets: 2 },
            { width: "15%", targets: 3 },
            { width: "20%", targets: 4 },
            { width: "8%", targets: 5 },
            { width: "10%", targets: 6 },
            { width: "20%", targets: 7 }
        ],
        "buttons": [
            {
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
                    columns: [1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Correos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
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

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_correos_configuracion.search('').draw();
    $('#buscar').focus();

    toggle_detalle_correos_configuracion_dataTable("#dataTableConfCorreos tbody", table_correos_configuracion);
    edit_correos_configuracion_dataTable("#dataTableConfCorreos tbody", table_correos_configuracion);
}

/* =========================================================
   TOGGLE DETALLE CORREO
   ========================================================= */
var toggle_detalle_correos_configuracion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_toggle_detalle");

    $(tbody).on("click", "button.table_toggle_detalle", function(e) {
        e.preventDefault();

        var boton = $(this);
        var tr = boton.closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');

            boton.removeClass('abierto');
            boton.attr('title', 'Mostrar detalle');
            boton.find('i').removeClass('fa-minus').addClass('fa-plus');
        } else {
            row.child(formatoDetalleCorreo(row.data())).show();
            tr.addClass('shown');

            boton.addClass('abierto');
            boton.attr('title', 'Ocultar detalle');
            boton.find('i').removeClass('fa-plus').addClass('fa-minus');
        }
    });
}

/* =========================================================
   EDITAR CORREO
   ========================================================= */
var edit_correos_configuracion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");

    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/correo/editarCorreo.php';

        $('#formConfEmails #correo_id').val(data.correo_id);

        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: {
                correo_id: data.correo_id
            },
            success: function(valores) {
                if (!valores || valores.success === false) {
                    showNotify('error', 'Error', valores.message || 'No se pudo cargar la configuración del correo');
                    return;
                }

                $('#formConfEmails').attr({
                    'data-form': 'update',
                    'action': '<?php echo SERVERURL;?>ajax/modificarCorreoAjax.php'
                });

                $('#formConfEmails')[0].reset();

                $('#formConfEmails #correo_id').val(valores.correo_id);

                $('#formConfEmails #tipo_correo_confEmail').val(valores.correo_tipo_id);
                $('#formConfEmails #tipo_correo_confEmail').selectpicker('refresh');

                $('#formConfEmails #metodoEnvioConfEmail').val(valores.metodo_envio || 'SMTP');
                $('#formConfEmails #metodoEnvioConfEmail').selectpicker('refresh');

                $('#formConfEmails #serverConfEmail').val(valores.server || '');
                $('#formConfEmails #correoConfEmail').val(valores.correo || '');
                $('#formConfEmails #passConfEmail').val('');
                $('#formConfEmails #puertoConfEmail').val(valores.port || '');

                $('#formConfEmails #smtpSecureConfEmail').val(valores.smtp_secure || '');
                $('#formConfEmails #smtpSecureConfEmail').selectpicker('refresh');

                $('#formConfEmails #tenantIdConfEmail').val(valores.tenant_id || '');
                $('#formConfEmails #clientIdConfEmail').val(valores.client_id || '');
                $('#formConfEmails #clientSecretConfEmail').val('');
                $('#formConfEmails #graphUserConfEmail').val(valores.graph_user || '');

                $('#formConfEmails #saveToSentItemsConfEmail').val(valores.save_to_sent_items || '1');
                $('#formConfEmails #saveToSentItemsConfEmail').selectpicker('refresh');

                aplicarVistaMetodoCorreo();
                aplicarPermisosFormularioCorreo(valores.puede_editar);

                $('#modalConfEmails').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            },
            error: function() {
                showNotify('error', 'Error', 'No se pudo consultar la configuración del correo');
            }
        });
    });
}

/* =========================================================
   TEST CORREO
   ========================================================= */
$("#test_confEmails").off("click");
$("#test_confEmails").on("click", function(e) {
    e.preventDefault();
    testEmail();
});

function testEmail() {
    var url = '<?php echo SERVERURL;?>core/correo/testEmail.php';

    /*
        No usamos serialize() porque los campos disabled no se envían.
        Aquí mandamos los valores manualmente para asegurar que el método GRAPH/SMTP llegue siempre.
    */
    var datosTest = {
        correo_id: $('#formConfEmails #correo_id').val(),

        metodoEnvioConfEmail: $('#formConfEmails #metodoEnvioConfEmail').val() || 'SMTP',

        serverConfEmail: $('#formConfEmails #serverConfEmail').val() || '',
        correoConfEmail: $('#formConfEmails #correoConfEmail').val() || '',
        passConfEmail: $('#formConfEmails #passConfEmail').val() || '',
        puertoConfEmail: $('#formConfEmails #puertoConfEmail').val() || '',
        smtpSecureConfEmail: $('#formConfEmails #smtpSecureConfEmail').val() || '',

        tenantIdConfEmail: $('#formConfEmails #tenantIdConfEmail').val() || '',
        clientIdConfEmail: $('#formConfEmails #clientIdConfEmail').val() || '',
        clientSecretConfEmail: $('#formConfEmails #clientSecretConfEmail').val() || '',
        graphUserConfEmail: $('#formConfEmails #graphUserConfEmail').val() || '',
        saveToSentItemsConfEmail: $('#formConfEmails #saveToSentItemsConfEmail').val() || '1'
    };

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: datosTest,
        success: function(data) {
            data = $.trim(data);

            if (data == 1) {
                showNotify('success', 'Success', 'Conexión realizada satisfactoriamente');
            } else {
                showNotify('error', 'Error', data || 'No se pudo realizar la conexión. Verifique la configuración.');
            }
        },
        error: function() {
            showNotify('error', 'Error', 'No se pudo ejecutar la prueba de conexión');
        }
    });
}

/* =========================================================
   CARGAR SMTP SECURE
   ========================================================= */
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

/* =========================================================
   CARGAR TIPO DE CORREO
   ========================================================= */
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

/* =========================================================
   MODAL DESTINATARIOS
   ========================================================= */
function modalDestinatarios() {
    if (!$('#modalRegistrarDestinatarios').length || !$('#formDestinatarios').length || !$('#DatatableDestinatarios').length) {
        showNotify('error', 'Modal no disponible', 'No se encontró el formulario para administrar destinatarios.');
        return;
    }

    $('#formDestinatarios').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL;?>ajax/addDestinatario.php'
    });

    $('#formDestinatarios').get(0).reset();
    $('#reg_destinatarios').show();

    $('#formDestinatarios #correo').attr('readonly', false);
    $('#formDestinatarios #nombre').attr('readonly', false);

    $('#formDestinatarios #proceso_destinatarios').val("Registro Destinatarios");

    $('#modalRegistrarDestinatarios')
    .off('shown.bs.modal.cargarDestinatarios')
    .one('shown.bs.modal.cargarDestinatarios', function() {
        listar_destinatarios();
        $(this).find('#formDestinatarios #correo').trigger('focus');
    })
    .modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/* =========================================================
   LISTAR DESTINATARIOS
   ========================================================= */
var listar_destinatarios = function() {
    var table_destinatarios = $("#DatatableDestinatarios").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableDestinatarios.php"
        },
        "columns": [
            { "data": "correo" },
            { "data": "nombre" },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "33.33%", targets: 0 },
            { width: "33.33%", targets: 1 },
            { width: "33.33%", targets: 2 }
        ],
        "buttons": [
            {
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

    table_destinatarios.search('').draw();
    $('#buscar').focus();

    eliminar_destinatarios_dataTable("#DatatableDestinatarios tbody", table_destinatarios);
}

/* =========================================================
   ELIMINAR DESTINATARIO
   ========================================================= */
var eliminar_destinatarios_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");

    $(tbody).on("click", "button.table_eliminar", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Estas seguro?",
            text: "¿Desea eliminar el destinatario " + data.nombre + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, eliminar el correo!"
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
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
                showNotify('success', 'Success', 'El destinatario ha sido eliminado correctamente');
                listar_destinatarios();
                $('#formDestinatarios #correo').focus();
            } else {
                showNotify('error', 'Error', 'Lo sentimos no se puede eliminar el destinatario');
            }
        }
    });
}
</script>