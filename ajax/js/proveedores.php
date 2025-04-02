<script>
$(document).ready(function() {
    getEstadoProveedores();

    var estado = $('#form_main_proveedores #estado_proveedores').val() === "" ? 1 : $(
        '#form_main_proveedores #estado_proveedores').val();

    listar_proveedores(estado);
    getDepartamentoProveedores();
});

$('#form_main_proveedores #buscar_proveedores').on('click', function(e) {
    e.preventDefault();
    var estado = $('#form_main_proveedores #estado_proveedores').val() === "" ? 1 : $(
        '#form_main_proveedores #estado_proveedores').val();
    listar_proveedores(estado);
});

//INICIO ACCIONES FROMULARIO PROVEEDORES
var listar_proveedores = function(estado) {
    var table_proveedores = $("#dataTableProveedores").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableProveedores.php",
            "data": {
                "estado": estado // nuevo parámetro
            }
        },
        "columns": [{
                "data": "proveedor"
            },
            {
                "data": "rtn"
            },
            {
                "data": "telefono"
            },
            {
                "data": "correo"
            },
            {
                "data": "departamento"
            },
            {
                "data": "municipio"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "22.5%",
                targets: 0
            },
            {
                width: "12.5%",
                targets: 1
            },
            {
                width: "12.5%",
                targets: 2
            },
            {
                width: "22.5%",
                targets: 3
            },
            {
                width: "12.5%",
                targets: 4
            },
            {
                width: "12.5%",
                targets: 5
            },
            {
                width: "2.5%",
                targets: 6
            },
            {
                width: "2.5%",
                targets: 7
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Proveedores',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_proveedores();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Proveedores',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_proveedores();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Proveedores',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Proveedores',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
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
    table_proveedores.search('').draw();
    $('#buscar').focus();

    editar_proveedores_dataTable("#dataTableProveedores tbody", table_proveedores);
    eliminar_proveedores_dataTable("#dataTableProveedores tbody", table_proveedores);
}

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
        var url = '<?php echo SERVERURL;?>core/editarProveedores.php';
        $('#formProveedores #proveedores_id').val(data.proveedores_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formProveedores').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formProveedores').attr({
                    'data-form': 'delete'
                });
                $('#formProveedores').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarProveedoresAjax.php'
                });
                $('#formProveedores')[0].reset();
                $('#reg_proveedor').hide();
                $('#edi_proveedor').hide();
                $('#delete_proveedor').show();
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

                //DESHABILITAR OBJETOS
                $('#formProveedores #nombre_proveedores').attr("readonly", true);
                $('#formProveedores #apellido_proveedores').attr("readonly", true);
                $('#formProveedores #rtn_proveedores').attr("readonly", true);
                $('#formProveedores #fecha_proveedores').attr("readonly", true);
                $('#formProveedores #departamento_proveedores').attr("disabled", true);
                $('#formProveedores #municipio_proveedores').attr("disabled", true);
                $('#formProveedores #dirección_proveedores').attr("disabled", true);
                $('#formProveedores #telefono_proveedores').attr("readonly", true);
                $('#formProveedores #correo_proveedores').attr("readonly", true);
                $('#formProveedores #proveedores_activo').attr("disabled", true);
                $('#formProveedores #estado_proveedores').hide();
                $('#formProveedores #grupo_editar_rtn').hide();

                $('#formProveedores #proceso_proveedores').val("Eliminar");
                $('#modal_registrar_proveedores').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
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
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea editar el RTN para el proveedor: " + getNombreProveedor(proveedores_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Si, Deseo Editarlo!",
            }
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
    }).then((willConfirm) => {
        if (willConfirm === true) {
            editRTNProveedor(proveedores_id, rtn);
        }
    });
}

function editRTNProveedor(proveedores_id, rtn) {
    var url = '<?php echo SERVERURL; ?>core/editRTNProveedor.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'proveedores_id=' + proveedores_id + '&rtn=' + rtn,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "El RTN ha sido actualizado satisfactoriamente",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
                listar_proveedores();
                $('#formProveedores #rtn_proveedores').val(rtn);
            } else if (data == 2) {
                swal({
                    title: "Error",
                    text: "Error el RTN no se puede actualizar",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
            } else if (data == 3) {
                swal({
                    title: "Error",
                    text: "El RTN ya existe",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
            }
        }
    });
}

function getNombreProveedor(proveedores_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreProveedor.php';
    var nombreProveedor = '';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'proveedores_id=' + proveedores_id,
        success: function(data) {
            var datos = eval(data);
            nombreProveedor = datos[0];
        }
    });

    return nombreProveedor;

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

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_proveedores #estado_proveedores').html("");
            $('#form_main_proveedores #estado_proveedores').html(data);
            $('#form_main_proveedores #estado_proveedores').selectpicker('refresh');
        }
    });
}
</script>