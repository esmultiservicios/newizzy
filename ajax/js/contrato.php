<script>
$(() => {
    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpleado();
    listar_contratos();
    $('#form_main_contrato #estado').val(1);
    $('#form_main_contrato #estado').selectpicker('refresh');

    // Evento para el botón de Buscar (submit)
    $('#form_main_contrato').on('submit', function(e) {
        e.preventDefault();

        listar_contratos(); 
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_contrato').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

			listar_contratos();
    });	    
});

//INICIO ACCIONES FROMULARIO CONTRATOS
var listar_contratos = function() {
    var estado = $("#form_main_contrato #estado").val() === "" ? 1 : $("#form_main_contrato #estado").val();
    var tipo_contrato = $("#form_main_contrato #tipo_contrato").val();
    var pago_planificado = $("#form_main_contrato #pago_planificado").val();
    var tipo_empleado = $("#form_main_contrato #tipo_empleado").val();

    var table_contratos = $("#dataTableContrato").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableContratos.php",
            "data": {
                "estado": estado,
                "tipo_contrato": tipo_contrato,
                "pago_planificado": pago_planificado,
                "tipo_empleado": tipo_empleado
            }
        },
        "columns": [{
                "data": "contrato_id"
            },
            {
                "data": "tipo_empleado"
            },
            {
                "data": "empleado"
            },
            {
                "data": "tipo_contrato"
            },
            {
                "data": "pago_planificado"
            },
            {
                "data": "salario",
                render: function(data, type) {
                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, 'L ')
                        .display(data);

                    if (type === 'display') {
                        let color = 'green';
                        if (data < 0) {
                            color = 'red';
                        }

                        return '<span style="color:' + color + '">' + number + '</span>';
                    }

                    return number;
                },
            },
            {
                "data": "fecha_inicio"
            },
            {
                "data": "fecha_fin"
            },
            {
                "data": "notas"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
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
                width: "2.09%",
                targets: 0
            },
            {
                width: "9.09%",
                targets: 1
            },
            {
                width: "19.09%",
                targets: 2
            },
            {
                width: "9.09%",
                targets: 3
            },
            {
                width: "9.09%",
                targets: 4
            },
            {
                width: "9.09%",
                targets: 5
            },
            {
                width: "9.09%",
                targets: 6
            },
            {
                width: "9.09%",
                targets: 7
            },
            {
                width: "20.09%",
                targets: 8
            },
            {
                width: "9.09%",
                targets: 9
            },
            {
                width: "9.09%",
                targets: 10
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Contrato',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_contratos();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Contrato',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_contratos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Contratos',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Contratos',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
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
    table_contratos.search('').draw();
    $('#buscar').focus();

    editar_contratos_dataTable("#dataTableContrato tbody", table_contratos);
    eliminar_contratos_dataTable("#dataTableContrato tbody", table_contratos);
}

var editar_contratos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarContratos.php';
        $('#formContrato')[0].reset();
        $('#formContrato #contrato_id').val(data.contrato_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formContrato').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formContrato').attr({
                    'data-form': 'update'
                });
                $('#formContrato').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarContratosAjax.php'
                });
                $('#reg_contrato').hide();
                $('#edi_contrato').show();
                $('#delete_contrato').hide();
                $('#formContrato #contrato_colaborador_id').val(valores[0]);
                $('#formContrato #contrato_colaborador_id').selectpicker('refresh');
                $('#formContrato #colaborador_id').val(valores[0]);
                $('#formContrato #contrato_tipo_contrato_id').val(valores[1]);
                $('#formContrato #contrato_tipo_contrato_id').selectpicker('refresh');
                $('#formContrato #contrato_pago_planificado_id').val(valores[2]);
                $('#formContrato #contrato_pago_planificado_id').selectpicker('refresh');
                $('#formContrato #contrato_tipo_empleado_id').val(valores[3]);
                $('#formContrato #contrato_tipo_empleado_id').selectpicker('refresh');
                $('#formContrato #contrato_salario').val(valores[4]);
                $('#formContrato #contrato_fecha_inicio').val(valores[5]);
                $('#formContrato #contrato_fecha_fin').val(valores[6]);
                $('#formContrato #contrato_notas').val(valores[7]);
                $('#formContrato #contrato_salario_mensual').val(valores[9]);

                if (valores[8] == 1) {
                    $('#formContrato #contrato_activo').attr('checked', true);
                } else {
                    $('#formContrato #contrato_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS				
                $('#formContrato #contrato_tipo_contrato_id').attr('disabled', false);
                $('#formContrato #contrato_pago_planificado_id').attr('disabled', false);
                $('#formContrato #contrato_tipo_empleado_id').attr('disabled', false);
                $('#formContrato #contrato_fecha_inicio').attr('readonly', false);
                $('#formContrato #contrato_fecha_fin').attr('readonly', false);
                $('#formContrato #contrato_notas').attr('readonly', false);
                $('#formContrato #contrato_activo').attr('disabled', false);

                //DESHABILITATR OBJETOS
                $('#formContrato #contrato_colaborador_id').attr('disabled', true);
                $('#formContrato #contrato_salario_mensual').attr('readonly', true);
                $('#formContrato #contrato_salario').attr('readonly', true);
                $('#formContrato #buscar_contrato_empleado').hide();

                $('#formContrato #proceso_contrato').val("Editar");

                $('#modal_registrar_contrato').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_contratos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var contrato_id = data.contrato_id;
        var nombreContrato = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el contrato?<br><br>
                        <strong>Nombre:</strong> ${nombreContrato}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarContratosAjax.php',
                    data: {
                        contrato_id: contrato_id
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
                    }
                });
            }
        });	        
    });
}
//FIN ACCIONES FROMULARIO CONTRATOS

/*INICIO FORMULARIO CONTRATOS*/
function modal_contratos() {
    $('#formContrato').attr({
        'data-form': 'save'
    });
    $('#formContrato').attr({
        'action': '<?php echo SERVERURL;?>ajax/addContratosAjax.php'
    });
    $('#formContrato')[0].reset();
    $('#reg_contrato').show();
    $('#edi_contrato').hide();
    $('#delete_contrato').hide();

    //HABILITAR OBJETOS
    $('#formContrato #contrato_colaborador_id').attr('disabled', false);
    $('#formContrato #contrato_tipo_contrato_id').attr('disabled', false);
    $('#formContrato #contrato_pago_planificado_id').attr('disabled', false);
    $('#formContrato #contrato_tipo_empleado_id').attr('disabled', false);
    $('#formContrato #contrato_salario').attr('readonly', true);
    $('#formContrato #contrato_fecha_inicio').attr('readonly', false);
    $('#formContrato #contrato_fecha_fin').attr('disabled', false);
    $('#formContrato #contrato_notas').attr('readonly', false);
    $('#formContrato #contrato_activo').attr('disabled', false);
    $('#formContrato #contrato_salario_mensual').attr('readonly', false);
    $('#formContrato #buscar_contrato_empleado').show();

    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpleado();

    $('#formContrato #proceso_contrato').val("Registro");

    $('#modal_registrar_contrato').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO CONTRATOS*/

$(document).ready(function() {
    $("#modal_registrar_contrato").on('shown.bs.modal', function() {
        $(this).find('#formContrato #puesto').focus();
    });
});

$('#formContrato #label_contrato_activo').html("Activo");

$('#formContrato .switch').change(function() {
    if ($('input[name=contrato_activo]').is(':checked')) {
        $('#formContrato #label_contrato_activo').html("Activo");
        return true;
    } else {
        $('#formContrato #label_contrato_activo').html("Inactivo");
        return false;
    }
});

$('#formContrato #label_calculo_semanal').html("Inactivo");

$('#formContrato .switch').change(function() {
    if ($('input[name=calculo_semanal]').is(':checked')) {
        $('#formContrato #label_calculo_semanal').html("Activo");
        return true;
    } else {
        $('#formContrato #label_calculo_semanal').html("Inactivo");
        return false;
    }
});

function getTipoContrato() {
    var url = '<?php echo SERVERURL;?>core/getTipoContrato.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#form_main_contrato #tipo_contrato').html("");
            $('#form_main_contrato #tipo_contrato').html(data);
            $('#form_main_contrato #tipo_contrato').selectpicker('refresh');

            $('#formContrato #contrato_tipo_contrato_id').html("");
            $('#formContrato #contrato_tipo_contrato_id').html(data);
            $('#formContrato #contrato_tipo_contrato_id').selectpicker('refresh');
        }
    });
}

function getPagoPlanificado() {
    var url = '<?php echo SERVERURL;?>core/getPagoPlanificado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#form_main_contrato #pago_planificado').html("");
            $('#form_main_contrato #pago_planificado').html(data);
            $('#form_main_contrato #pago_planificado').selectpicker('refresh');

            $('#formContrato #contrato_pago_planificado_id').html("");
            $('#formContrato #contrato_pago_planificado_id').html(data);
            $('#formContrato #contrato_pago_planificado_id').selectpicker('refresh');
        }
    });
}

function getTipoEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getTipoEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_contrato #tipo_empleado').html("");
            $('#form_main_contrato #tipo_empleado').html(data);
            $('#form_main_contrato #tipo_empleado').selectpicker('refresh');

            $('#formContrato #contrato_tipo_empleado_id').html("");
            $('#formContrato #contrato_tipo_empleado_id').html(data);
            $('#formContrato #contrato_tipo_empleado_id').selectpicker('refresh');
        }
    });
}

//INICIO FORMULARIO CONRATO
function getEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getEmpleadoContrato.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#formContrato #contrato_colaborador_id').html("");
            $('#formContrato #contrato_colaborador_id').html(data);
            $('#formContrato #contrato_colaborador_id').selectpicker('refresh');
        }
    });
}
// FIN FORMULARIO CONTRATO

$('#formContrato #contrato_notas').keyup(function() {
    var max_chars = 254;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#formContrato #charNum_contrato_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresEstadoContrato() {
    var max_chars = 254;
    var chars = $('#formContrato #contrato_notas').val().length;
    var diff = max_chars - chars;

    $('#formContrato #charNum_contrato_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

//INICIO GRABACIONES POR VOZ
$(document).ready(function() {
    //INICIO FORMULARIO ATENCIONES EXPEDIENTE CLINICO
    $('#formContrato #search_contrato_notas_stop').hide();

    var recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.lang = "es";

    $('#formContrato #search_contrato_notas_start').on('click', function(event) {
        $('#formContrato #search_contrato_notas_start').hide();
        $('#formContrato #search_contrato_notas_stop').show();

        recognition.start();

        recognition.onresult = function(event) {
            finalResult = '';
            var valor_anterior = $('#formContrato #contrato_notas').val();
            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    if (valor_anterior != "") {
                        $('#formContrato #contrato_notas').val(valor_anterior + ' ' + finalResult);
                        caracteresEstadoContrato();
                    } else {
                        $('#formContrato #contrato_notas').val(finalResult);
                        caracteresEstadoContrato();
                    }
                }
            }
        };
        return false;
    });

    $('#formContrato #search_contrato_notas_stop').on("click", function(event) {
        $('#formContrato #search_contrato_notas_start').show();
        $('#formContrato #search_contrato_notas_stop').hide();
        recognition.stop();
    });

    /*###############################################################################################################################*/
});

$('#formContrato #contrato_salario_mensual').on("keyup", function(e) {
    ValidarTipoPago(false);
});

$('#formContrato #contrato_pago_planificado_id').on("change", function(e) {
    ValidarTipoPago(false);
});

$('#formContrato #contrato_pago_planificado_id').on("change", function(e) {
    if ($('#formContrato #contrato_pago_planificado_id').val() === "1") {
        $('#formContrato #estado_base_semanal').show();
    } else {
        $('#formContrato #estado_base_semanal').hide();
    }
});

$('#formContrato #calculo_semanal').on("change", function() {
    if ($(this).is(":checked")) {
        ValidarTipoPago(true);
    } else {
        ValidarTipoPago(false);
    }
});

function ValidarTipoPago(semanal) {
    if ($('#formContrato #contrato_pago_planificado_id').val() != "") {
        var valor = 0;

        if ($('#formContrato #contrato_pago_planificado_id').val() == 1) { //SEMANAL
            valor = 7;
        }

        if ($('#formContrato #contrato_pago_planificado_id').val() == 2) { //QUINCENAL
            valor = 15;
        }

        if ($('#formContrato #contrato_pago_planificado_id').val() == 3) { //MENSUAL
            valor = 30;
        }

        var salarioMensual = parseFloat($('#formContrato #contrato_salario_mensual').val());
        var salarioDiario = parseFloat($('#formContrato #contrato_salario_mensual').val()) / parseFloat(30);
        var salario = 0.00;

        if (semanal) {
            salario = parseFloat(salarioMensual) / parseFloat(4);
        } else {
            salario = parseFloat(salarioDiario) * parseFloat(valor);
        }

        $('#formContrato #contrato_salario').val(parseFloat(salario).toFixed(2));
    } else {
        swal({
            title: "Error",
            text: "Lo sentimos debe seleccionar un pago planificado antes de llenar este valor",
            icon: "error",
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
        });
    }
}
</script>