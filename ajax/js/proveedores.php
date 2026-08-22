<script>
/* =========================================================
   PERMISOS ASÍNCRONOS EXCLUSIVOS DEL DATATABLE PROVEEDORES
   - No modifica permisos globales, navbar ni menús.
   - Evita getPrivilegioTipoUsuario() síncrono en cada draw.
   ========================================================= */
var proveedoresPermisosDataTablePromise = null;

function proveedoresParseJsonSeguro(valor, respaldo) {
    if (valor !== null && typeof valor === 'object') {
        return valor;
    }

    try {
        return JSON.parse(valor || '');
    } catch (error) {
        return respaldo;
    }
}

function aplicarPermisosProveedoresDataTableAsync() {
    if (!proveedoresPermisosDataTablePromise) {
        proveedoresPermisosDataTablePromise = $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/getPrivilegioUsuarioTipo.php',
            timeout: 30000
        }).then(function(respuestaTipoUsuario) {
            var tipoUsuario = proveedoresParseJsonSeguro(respuestaTipoUsuario, []);

            return $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php',
                data: {
                    permisos_tipo_user_id: tipoUsuario[0]
                },
                timeout: 30000
            });
        }).then(function(respuestaPermisos) {
            return proveedoresParseJsonSeguro(respuestaPermisos, []);
        }).catch(function(error) {
            proveedoresPermisosDataTablePromise = null;
            throw error;
        });
    }

    return proveedoresPermisosDataTablePromise.then(function(permisos) {
        permisos.forEach(function(permiso) {
            var $controles = $('.table_' + permiso.tipo_permiso);
            var habilitado = Number(permiso.estado) === 1;

            $controles.toggle(habilitado).prop('disabled', !habilitado);
        });
    }).catch(function(error) {
        console.error('No se pudieron aplicar los permisos de Proveedores.', error);
    });
}

$(() => {
    getDepartamentoProveedores();

    // Primero llena el filtro Estado y después carga la tabla.
    // Evita enviar estado=null/estado= mientras el select aún está cargando.
    getEstadoProveedores().always(function() {
        var $estado = $('#form_main_proveedores #estado_proveedores');

        if ($estado.val() === null || $estado.val() === undefined || $estado.val() === '') {
            $estado.val('1').selectpicker('refresh');
        }

        listar_proveedores();
    });

    // Evento para el botón de Buscar (submit)
    $('#form_main_proveedores #search').on("click", function(e) {
        e.preventDefault();
        listar_proveedores();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_proveedores').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

        listar_proveedores();
    });
});

$('#form_main_proveedores #buscar_proveedores').on('click', function(e) {
    e.preventDefault();

    listar_proveedores();
});

/* =========================================================
   HEADER DINÁMICO - PROVEEDORES
   ========================================================= */
   function construirHeaderDataTableProveedores() {
    var $tabla = $("#dataTableProveedores");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Proveedor</th>' +
                '<th>RTN</th>' +
                '<th>Teléfono</th>' +
                '<th>Correo</th>' +
                '<th>Departamento</th>' +
                '<th>Municipio</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO ACCIONES FROMULARIO PROVEEDORES
var listar_proveedores = function() {
    var estadoSeleccionado = $('#form_main_proveedores #estado_proveedores').val();
    var estado = (
        estadoSeleccionado === null ||
        estadoSeleccionado === undefined ||
        estadoSeleccionado === ''
    ) ? 1 : estadoSeleccionado;

    if ($.fn.DataTable.isDataTable("#dataTableProveedores")) {
        $("#dataTableProveedores").DataTable().clear().destroy();
    }

    construirHeaderDataTableProveedores();

    var table_proveedores = $("#dataTableProveedores").DataTable({
        destroy: true,
        processing: true,
        deferRender: true,
        searchDelay: 350,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableProveedores.php",
            data: {
                estado: estado
            },
            timeout: 30000
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
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

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar</span>' +
                                '</button>' +

                            '</div>' +

                        '</div>';
                }
            },
            {
                data: "proveedor"
            },
            {
                data: "rtn"
            },
            {
                data: "telefono"
            },
            {
                data: "correo"
            },
            {
                data: "departamento"
            },
            {
                data: "municipio"
            },
            {
                data: "estado",
                render: function(data, type, row) {
                    if (type === "display") {
                        var estadoText = data == 1 ? "Activo" : "Inactivo";

                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';

                        var badgeClass = data == 1 ?
                            "badge badge-pill badge-success" :
                            "badge badge-pill badge-danger";

                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon +
                            estadoText +
                        '</span>';
                    }

                    return data;
                }
            }
        ],

        order: [[1, "asc"]],

        lengthMenu: lengthMenu10,
        stateSave: true,
        stateLoadParams: function(settings, data) {
            if (data && data.search) {
                data.search.search = "";
            }
        },
        bDestroy: true,
        language: idioma_español,
        dom: dom,

        columnDefs: [
            {
                width: "8%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "22%",
                targets: 1
            },
            {
                width: "12%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3
            },
            {
                width: "22%",
                targets: 4
            },
            {
                width: "12%",
                targets: 5
            },
            {
                width: "12%",
                targets: 6
            },
            {
                width: "8%",
                targets: 7,
                className: "text-center text-nowrap"
            }
        ],

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: "Actualizar Proveedores",
                className: "table_actualizar btn btn-secondary ocultar",
                action: function() {
                    listar_proveedores();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: "Agregar Proveedores",
                className: "table_crear btn btn-primary ocultar",
                action: function() {
                    modal_proveedores();
                }
            },
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: "Excel",
                title: "Reporte de Proveedores",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
                className: "table_reportes btn btn-success ocultar"
            },
            {
                extend: "pdf",
                orientation: "landscape",
                pageSize: "LEGAL",
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: "PDF",
                title: "Reporte de Proveedores",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-danger ocultar",
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

        drawCallback: function(settings) {
            aplicarPermisosProveedoresDataTableAsync();

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    $("#buscar").focus();

    editar_proveedores_dataTable("#dataTableProveedores tbody", table_proveedores);
    eliminar_proveedores_dataTable("#dataTableProveedores tbody", table_proveedores);
};
//FIN ACCIONES FROMULARIO PROVEEDORES

var editar_proveedores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarProveedores.php';
        $('#formProveedores #proveedores_id').val(data.proveedores_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formProveedores').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formProveedores').attr({
                    'data-form': 'update'
                });
                $('#formProveedores').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarProveedoresAjax.php'
                });
                $('#formProveedores')[0].reset();
                $('#reg_proveedor').hide();
                $('#edi_proveedor').show();
                $('#delete_proveedor').hide();
                $('#formProveedores #nombre_proveedores').val(valores[0]);
                $('#formProveedores #rtn_proveedores').val(valores[1]);
                $('#formProveedores #fecha_proveedores').attr('disabled', true);
                $('#formProveedores #fecha_proveedores').val(valores[2]);
                $('#formProveedores #departamento_proveedores').val(valores[3]);
                $('#formProveedores #departamento_proveedores').selectpicker('refresh');
                getMunicipiosProveedores(valores[4]);
                $('#formProveedores #municipio_proveedores').val(valores[4]);
                $('#formProveedores #municipio_proveedores').selectpicker('refresh');
                $('#formProveedores #dirección_proveedores').val(valores[5]);
                $('#formProveedores #telefono_proveedores').val(valores[6]);
                $('#formProveedores #correo_proveedores').val(valores[7]);

                if (valores[8] == 1) {
                    $('#formProveedores #proveedores_activo').attr('checked', true);
                } else {
                    $('#formProveedores #proveedores_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS
                $('#formProveedores #nombre_proveedores').attr("readonly", false);
                $('#formProveedores #apellido_proveedores').attr("readonly", false);
                $('#formProveedores #departamento_proveedores').attr("disabled", false);
                $('#formProveedores #municipio_proveedores').attr("disabled", false);
                $('#formProveedores #dirección_proveedores').attr("disabled", false);
                $('#formProveedores #telefono_proveedores').attr("readonly", false);
                $('#formProveedores #correo_proveedores').attr("readonly", false);
                $('#formProveedores #proveedores_activo').attr("disabled", false);
                $('#formProveedores #estado_proveedores').show();
                $('#formProveedores #grupo_editar_rtn').show();

                //DESHABILITAR OBJETOS
                $('#formProveedores #rtn_proveedores').attr("readonly", true);

                $('#formProveedores #proceso_proveedores').val("Editar");
                $('#modal_registrar_proveedores').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_proveedores_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var proveedores_id = data.proveedores_id;
        var nombre = data.proveedor; 
        var rtn = data.rtn || 'No registrado'; // Manejo de RTN vacío

        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente al proveedor?<br><br>
                <strong>Nombre:</strong> ${nombre}<br>
				<strong>RTN:</strong> ${rtn}`;

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
                    url: '<?php echo SERVERURL;?>ajax/eliminarProveedoresAjax.php',
                    data: {
                        proveedores_id: proveedores_id
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
                            table.search('');
                            table.ajax.reload(null, false); // Recargar una sola vez sin resetear paginación
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });                
    });
}

//INICIO EDITAR RTN PROVEEDORES
//SE LLAMA AL MODAL CUANDO PRESIONAMOS EN EDITAR RTN EN CLIENTES
$('#formProveedores #grupo_editar_rtn').on('click', function(e) {
    e.preventDefault();

    $('#formEditarRTNProveedores')[0].reset();
    $('#formEditarRTNProveedores #pro_proveedores').val("Editar");
    $('#formEditarRTNProveedores #proveedores_id').val($('#formProveedores #proveedores_id').val());
    $('#formEditarRTNProveedores #proveedor').val($('#formProveedores #nombre_proveedores').val());
    $('#modalEditarRTNProveedores').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$(document).ready(function() {
    $("#modalEditarRTNProveedores").on('shown.bs.modal', function() {
        $(this).find('#formEditarRTNProveedores #rtn_proveedor').focus();
    });
});

$('#editar_rtn_proveedores').on('click', function(e) {
    e.preventDefault();

    editRTNProvider($('#formEditarRTNProveedores #proveedores_id').val(), $(
        '#formEditarRTNProveedores #rtn_proveedor').val());
});

function editRTNProvider(proveedores_id, rtn) {
    getNombreProveedor(proveedores_id).then(function(nombreProveedor) {
        return swal({
            title: "¿Estas seguro?",
            text: "¿Desea editar el RTN para el proveedor: " + nombreProveedor + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true,
                    closeModal: true
                },
                confirm: {
                    text: "¡Sí, deseo editarlo!",
                    className: "btn-primary"
                }
            },
            closeOnClickOutside: false,
            closeOnEsc: false
        });
    }).then(function(willConfirm) {
        if (willConfirm === true) {
            editRTNProveedor(proveedores_id, rtn);
        }
    }).catch(function() {
        showNotify('error', 'Error', 'No se pudo consultar la información del proveedor');
    });
}

function editRTNProveedor(proveedores_id, rtn) {
    var url = '<?php echo SERVERURL; ?>core/editRTNProveedor.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'proveedores_id=' + proveedores_id + '&rtn=' + rtn,
        success: function(data) {
            if (data == 1) {
                showNotify('success', 'Success', 'El RTN ha sido actualizado satisfactoriamente');
                listar_proveedores();
                $('#formProveedores #rtn_proveedores').val(rtn);
            } else if (data == 2) {
                showNotify('error', 'Error', 'Error el RTN no se puede actualizar');
            } else if (data == 3) {
                showNotify('error', 'Error', 'El RTN ya existe');
            }
        }
    });
}

function getNombreProveedor(proveedores_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreProveedor.php';

    return $.ajax({
        type: 'POST',
        url: url,
        data: 'proveedores_id=' + proveedores_id,
        timeout: 30000
    }).then(function(data) {
        var datos = proveedoresParseJsonSeguro(data, []);
        return datos[0] || '';
    });
}
//FIN EDITAR RTN PROVEEDORES
//FIN ACCIONES FROMULARIO PROVEEDORES
/*FIN FORMULARIO PROVEEDORES*/
$(document).ready(function() {
    $("#modal_registrar_proveedores").on('shown.bs.modal', function() {
        $(this).find('#formProveedores #nombre_proveedores').focus();
    });
});

$('#formProveedores #label_proveedores_activo').html("Activo");

$('#formProveedores .switch').change(function() {
    if ($('input[name=proveedores_activo]').is(':checked')) {
        $('#formProveedores #label_proveedores_activo').html("Activo");
        return true;
    } else {
        $('#formProveedores #label_proveedores_activo').html("Inactivo");
        return false;
    }
});

function getEstadoProveedores() {
    var url = '<?php echo SERVERURL;?>core/getEstado.php';

    return $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_proveedores #estado_proveedores').html("");
            $('#form_main_proveedores #estado_proveedores').html(data);
            $('#form_main_proveedores #estado_proveedores').val('1');
            $('#form_main_proveedores #estado_proveedores').selectpicker('refresh');
        }
    });
}
</script>