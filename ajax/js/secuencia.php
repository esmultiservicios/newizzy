<script>
$(document).ready(function() {
    listar_secuencia_facturacion();
    getEmpresaSecuencia();
    getDocumentoSecuencia();
});

//INICIO ACCIONES FROMULARIO SECUENCIA FACTURACION
var listar_secuencia_facturacion = function() {
    var table_secuencia_facturacion = $("#dataTableSecuencia").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableSecuenciaFacturacion.php"
        },
        "columns": [{
                "data": "empresa"
            },
            {
                "data": "documento"
            },
            {
                "data": "cai"
            },
            {
                "data": "prefijo"
            },
            {
                "data": "siguiente"
            },
            {
                "data": "rango_inicial"
            },
            {
                "data": "rango_final"
            },
            {
                "data": "fecha_limite"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>"
            },
            {
                "defaultContent": "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "16%",
                targets: 0
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "21%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3
            },
            {
                width: "5%",
                targets: 4
            },
            {
                width: "10%",
                targets: 5
            },
            {
                width: "10%",
                targets: 6
            },
            {
                width: "10%",
                targets: 7
            },
            {
                width: "2%",
                targets: 8
            },
            {
                width: "2%",
                targets: 9
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Secuencia de Facturación',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_secuencia_facturacion();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Secuencia de Facturación',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_secuencia_facturacion();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Secuencia de Facturación',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Secuencia de Facturación',
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
    table_secuencia_facturacion.search('').draw();
    $('#buscar').focus();

    editar_secuencia_facturacion_dataTable("#dataTableSecuencia tbody", table_secuencia_facturacion);
    eliminar_secuencia_facturacion_dataTable("#dataTableSecuencia tbody", table_secuencia_facturacion);
}

var editar_secuencia_facturacion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarSecuenciaFacturacion.php';
        $('#formSecuencia #secuencia_facturacion_id').val(data.secuencia_facturacion_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formSecuencia').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formSecuencia').attr({
                    'data-form': 'update'
                });
                $('#formSecuencia').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarSecuenciaFacturacionAjax.php'
                });
                $('#formSecuencia')[0].reset();
                $('#reg_secuencia').hide();
                $('#edi_secuencia').show();
                $('#delete_secuencia').hide();
                $('#formSecuencia #empresa_secuencia').val(valores[0]);
                $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
                $('#formSecuencia #cai_secuencia').val(valores[1]);
                $('#formSecuencia #prefijo_secuencia').val(valores[2]);
                $('#formSecuencia #relleno_secuencia').val(valores[3]);
                $('#formSecuencia #incremento_secuencia').val(valores[4]);
                $('#formSecuencia #siguiente_secuencia').val(valores[5]);
                $('#formSecuencia #rango_inicial_secuencia').val(valores[6]);
                $('#formSecuencia #rango_final_secuencia').val(valores[7]);
                $('#formSecuencia #fecha_activacion_secuencia').val(valores[8]);
                $('#formSecuencia #fecha_limite_secuencia').val(valores[9]);
                $('#formSecuencia #documento_secuencia').val(valores[11]);
                $('#formSecuencia #documento_secuencia').selectpicker('refresh');

                if (valores[10] == 1) {
                    $('#formSecuencia #estado_secuencia').attr('checked', true);
                } else {
                    $('#formSecuencia #estado_secuencia').attr('checked', false);
                }

                //DESHABILITAR OBJETOS
                $('#formSecuencia #empresa_secuencia').attr('disabled', true);
                $('#formSecuencia #documento_secuencia').attr('disabled', true);
                $('#formSecuencia #cai_secuencia').attr('readonly', true);
                $('#formSecuencia #prefijo_secuencia').attr('readonly', true);
                $('#formSecuencia #relleno_secuencia').attr('readonly', true);
                $('#formSecuencia #incremento_secuencia').attr('readonly', true);
                $('#formSecuencia #rango_inicial_secuencia').attr('readonly', true);
                $('#formSecuencia #rango_final_secuencia').attr('readonly', true);
                $('#formSecuencia #fecha_activacion_secuencia').attr('readonly', true);
                $('#formSecuencia #fecha_limite_secuencia').attr('readonly', true);

                $('#formSecuencia #estado_secuencia_container').show();

                //HABILITAR OBJETOS
                $('#formSecuencia #siguiente_secuencia').attr('readonly', false);
                $('#formSecuencia #estado_secuencia').attr('disabled', false);

                $('#formSecuencia #proceso_secuencia_facturacion').val("Editar");
                $('#modal_registrar_secuencias').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_secuencia_facturacion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarSecuenciaFacturacion.php';
        $('#formSecuencia #secuencia_facturacion_id').val(data.secuencia_facturacion_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formSecuencia').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formSecuencia').attr({
                    'data-form': 'delete'
                });
                $('#formSecuencia').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarSecuenciaFacturacionAjax.php'
                });
                $('#formSecuencia')[0].reset();
                $('#edi_secuencia').hide();
                $('#reg_secuencia').hide();
                $('#delete_secuencia').show();
                $('#formSecuencia #empresa_secuencia').val(valores[0]);
                $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
                $('#formSecuencia #cai_secuencia').val(valores[1]);
                $('#formSecuencia #prefijo_secuencia').val(valores[2]);
                $('#formSecuencia #relleno_secuencia').val(valores[3]);
                $('#formSecuencia #incremento_secuencia').val(valores[4]);
                $('#formSecuencia #siguiente_secuencia').val(valores[5]);
                $('#formSecuencia #rango_inicial_secuencia').val(valores[6]);
                $('#formSecuencia #rango_final_secuencia').val(valores[7]);
                $('#formSecuencia #fecha_activacion_secuencia').val(valores[8]);
                $('#formSecuencia #fecha_limite_secuencia').val(valores[9]);
                $('#formSecuencia #documento_secuencia').val(valores[11]);
                $('#formSecuencia #documento_secuencia').selectpicker('refresh');

                if (valores[10] == 1) {
                    $('#formSecuencia #estado_secuencia').attr('checked', true);
                } else {
                    $('#formSecuencia #estado_secuencia').attr('checked', false);
                }

                //DESHABILITAR OBJETOS
                $('#formSecuencia #empresa_secuencia').attr('disabled', true);
                $('#formSecuencia #documento_secuencia').attr('disabled', true);
                $('#formSecuencia #cai_secuencia').attr('readonly', true);
                $('#formSecuencia #prefijo_secuencia').attr('readonly', true);
                $('#formSecuencia #relleno_secuencia').attr('readonly', true);
                $('#formSecuencia #incremento_secuencia').attr('readonly', true);
                $('#formSecuencia #siguiente_secuencia').attr('readonly', true);
                $('#formSecuencia #rango_inicial_secuencia').attr('readonly', true);
                $('#formSecuencia #rango_final_secuencia').attr('readonly', true);
                $('#formSecuencia #fecha_activacion_secuencia').attr('readonly', true);
                $('#formSecuencia #fecha_limite_secuencia').attr('readonly', true);
                $('#formSecuencia #estado_secuencia').attr('disabled', true);

                $('#formSecuencia #estado_secuencia_container').hide();

                $('#formSecuencia #proceso_secuencia_facturacion').val("Eliminar");
                $('#modal_registrar_secuencias').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}
//FIN ACCIONES FROMULARIO SECUENCIA FACTURACION

/*INICIO FORMULARIO SECUENCIA DE FACTURACION*/
function modal_secuencia_facturacion() {
    getEmpresaSecuencia();
    getDocumentoSecuencia();
    $('#formSecuencia').attr({
        'data-form': 'save'
    });
    $('#formSecuencia').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarSecuenciaFacturacionAjax.php'
    });
    $('#formSecuencia')[0].reset();
    $('#reg_secuencia').show();
    $('#edi_secuencia').hide();
    $('#delete_secuencia').hide();

    //HABILITAR OBJETOS
    $('#formSecuencia #documento_secuencia').attr('disabled', false);
    $('#formSecuencia #empresa_secuencia').attr('disabled', false);
    $('#formSecuencia #documento_secuencia').attr('disabled', false);
    $('#formSecuencia #cai_secuencia').attr('readonly', false);
    $('#formSecuencia #prefijo_secuencia').attr('readonly', false);
    $('#formSecuencia #relleno_secuencia').attr('readonly', false);
    $('#formSecuencia #incremento_secuencia').attr('readonly', false);
    $('#formSecuencia #siguiente_secuencia').attr('readonly', false);
    $('#formSecuencia #rango_inicial_secuencia').attr('readonly', false);
    $('#formSecuencia #rango_final_secuencia').attr('readonly', false);
    $('#formSecuencia #fecha_activacion_secuencia').attr('readonly', false);
    $('#formSecuencia #fecha_limite_secuencia').attr('readonly', false);
    $('#formSecuencia #estado_secuencia').attr('disabled', false);

    $('#formSecuencia #proceso_secuencia_facturacion').val("Registro");
    $('#formSecuencia #empresa_secuencia').attr('disabled', false);
    $('#modal_registrar_secuencias').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO SECUENCIA DE FACTURACION*/

function getEmpresaSecuencia() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formSecuencia #empresa_secuencia');
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

            $('#formSecuencia #empresa_secuencia').val(1);
            $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
        },
        error: function(xhr) {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formSecuencia #empresa_secuencia').html('<option value="">Error al cargar</option>');
            $('#formSecuencia #empresa_secuencia').selectpicker('refresh');
        }
    });
}

function getDocumentoSecuencia() {
    var url = '<?php echo SERVERURL;?>core/getDocumentoSecuencia.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formSecuencia #documento_secuencia').html("");
            $('#formSecuencia #documento_secuencia').html(data);
            $('#formSecuencia #documento_secuencia').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $("#modal_registrar_secuencias").on('shown.bs.modal', function() {
        $(this).find('#formSecuencia #empresa_secuencia').focus();
    });
});

$('#formSecuencia #label_estado_secuencia').html("Activo");

$('#formSecuencia .switch').change(function() {
    if ($('input[name=estado_secuencia]').is(':checked')) {
        $('#formSecuencia #label_estado_secuencia').html("Activo");
        return true;
    } else {
        $('#formSecuencia #label_estado_secuencia').html("Inactivo");
        return false;
    }
});
</script>