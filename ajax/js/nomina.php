<script>
var sueldo_diario = 0;

$(document).ready(function() {
    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpresa();
    getEmpleado();    
    getTipoNomina();
    getCuentaNominas();
    getEmpleadoVales();
    listar_vales();
    listar_nominas();
    $('#form_main_nominas #estado_nomina').val(0);
    $('#form_main_nominas #estado_nomina').selectpicker('refresh');
});

$('#form_main_nominas #estado_nomina').on("change", function(e) {
    listar_nominas();
});

$('#form_main_nominas #tipo_contrato_nomina').on("change", function(e) {
    listar_nominas();
});

$('#form_main_nominas #pago_planificado_nomina').on("change", function(e) {
    listar_nominas();
});

$('#form_main_nominas #fechai').on("change", function(e) {
    listar_nominas();
});

$('#form_main_nominas #fechaf').on("change", function(e) {
    listar_nominas();
});

//CONSULTA NOMINA DETALLES
$('#form_main_nominas_detalles #estado_nomina_detalles').on("change", function(e) {
    listar_nominas_detalles();
});

$('#form_main_nominas_detalles #detalle_nomina_empleado').on("change", function(e) {
    listar_nominas_detalles();
});

//INICIO ACCIONES FROMULARIO NOMINAS
var listar_nominas = function() {
    var estado = $("#form_main_nominas #estado_nomina").val() || 0; 
    var tipo_contrato_id = $("#form_main_nominas #tipo_contrato_nomina").val() || 0;
    
    var table_nominas = $("#dataTableNomina").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableNomina.php",
            "data": {
                "estado": estado,
                "tipo_contrato_id": tipo_contrato_id
            }
        },
        "columns": [{
                "data": "nomina_id"
            },
            {
                "data": "detalle"
            },
            {
                "data": "empresa"
            },
            {
                "data": "fecha_inicio"
            },
            {
                "data": "fecha_fin"
            },
            {
                "data": "importe",
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
                "data": "notas"
            },
            {
                "defaultContent": "<div class='btn-group'><button type='button' class='btn btn-dark table_editar ocultar dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><i class='fas fa-users-cog'></i> Aciones</button><div class='dropdown-menu'><a class='dropdown-item nomina_generar' href='#'>Generar Nomina</a><div class='dropdown-divider'></div><a class='dropdown-item voucher_pago' href='#'>Voucher de Pago</a><a class='dropdown-item consolidado' href='#'>Libro de Salarios</a></div></div>"
            },
            {
                "defaultContent": "<button class='table_editar nomina_agregar btn btn-dark ocultar'><span class='fas fa-folder-plus fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_editar nomina_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar nomina_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "2.09%",
                targets: 0
            },
            {
                width: "23.09%",
                targets: 1
            },
            {
                width: "12.09%",
                targets: 2
            },
            {
                width: "11.09%",
                targets: 3
            },
            {
                width: "12.09%",
                targets: 4
            },
            {
                width: "10.09%",
                targets: 5
            },
            {
                width: "23.09%",
                targets: 6
            },
            {
                width: "2.09%",
                targets: 7
            },
            {
                width: "2.09%",
                targets: 8
            },
            {
                width: "1.09%",
                targets: 9
            },
            {
                width: "1.09%",
                targets: 10
            },
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            var number = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['neto_importe']);
            $('#neto_importe').html(number);
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar listar_nominas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_contratos();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Nomina',
                titleAttr: 'Agregar Nomina',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_nominas();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Vales',
                titleAttr: 'Agregar Nomina',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_vales();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
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
    table_nominas.search('').draw();
    $('#buscar').focus();

    generar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    voucher_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    libro_salarios_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    crear_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    editar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
    eliminar_nominas_dataTable("#dataTableNomina tbody", table_nominas);
}

var generar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.nomina_generar");
    $(tbody).on("click", "a.nomina_generar", function() {
        var data = table.row($(this).parents("tr")).data();

        if ($('#form_main_nominas #estado_nomina').val() == 0) {
            swal({
                title: "¿Estas seguro?",
                text: "¿Desea generar esta nomina?",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        visible: true
                    },
                    confirm: {
                        text: "¡Sí, generar la nomina!",
                    }
                },
                dangerMode: true,
				closeOnEsc: false, // Desactiva el cierre con la tecla Esc
				closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
            }).then((willConfirm) => {
                if (willConfirm === true) {
                    genearNomina(data.nomina_id, data.empresa_id);
                }
            });
        } else {
            swal({
                title: "Error",
                text: "Lo sentimos, esta nomina ya ha sido generada",
                icon: "error",
				dangerMode: true,
				closeOnEsc: false, // Desactiva el cierre con la tecla Esc
				closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
            });
        }
    });
}

function genearNomina(nomina_id, empresa_id) {
    var url = '<?php echo SERVERURL; ?>core/generarNomina.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: { nomina_id: nomina_id, empresa_id: empresa_id },
        dataType: 'json',  // Asegura que la respuesta sea interpretada como JSON
        success: function(data) {
            if (data.status === 1) {
                swal({
                    title: "Success",
                    text: data.message,  // Mostrar mensaje del servidor
                    icon: "success",
                    timer: 3000,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                }).then(() => {  // Espera hasta que el swal se cierre para continuar
                    listar_nominas();
                    PrintLibroSalarios(data.nomina_id);
                    PrintVoucherPago(data.nomina_id);
                });
            } else {
                swal({
                    title: "Error",
                    text: data.message,  // Mostrar mensaje del servidor
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error en la solicitud AJAX:', error);
        }
    });
}

var crear_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_agregar");
    $(tbody).on("click", "button.nomina_agregar", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formNominaDetalles #nomina_id').val(data.nomina_id);
        $('#formNominaDetalles #nominad_numero').val(data.nomina_id);
        $("#form_main_nominas_detalles #nomina_id").val(data.nomina_id)
        $('#formNominaDetalles #nominad_detalle').val(data.detalle);
        $('#formNominaDetalles #pago_planificado_id').val(data.pago_planificado_id);
        $('#form_main_nominas_detalles #estado_nomina_detalles').val(data.estado);
        $('#form_main_nominas_detalles #estado_nomina_detalles').selectpicker('refresh');

        $('#form_main_nominas_detalles #fecha_inicio').val(data.fecha_inicio);
        $('#form_main_nominas_detalles #fecha_fin').val(data.fecha_fin);


        $("#nomina_principal").hide();
        $("#nomina_detalles").show();
        listar_nominas_detalles();
    });
}

var voucher_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.voucher_pago");
    $(tbody).on("click", "a.voucher_pago", function() {
        var data = table.row($(this).parents("tr")).data();

        if (data.estado == 0) {
            swal({
                title: "Error",
                text: "Lo sentimos, la nomina no esta generada no se puede mostrar el reporte",
                icon: "error",
				dangerMode: true,
				closeOnEsc: false, // Desactiva el cierre con la tecla Esc
				closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
            });
        } else {
            PrintVoucherPago(data.nomina_id);
        }
    });
}

var libro_salarios_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.consolidado");
    $(tbody).on("click", "a.consolidado", function() {
        var data = table.row($(this).parents("tr")).data();

        if (data.estado == 0) {
            swal({
                title: "Error",
                text: "Lo sentimos, la nomina no esta generada no se puede mostrar el reporte",
                icon: "error",
				dangerMode: true,
				closeOnEsc: false, // Desactiva el cierre con la tecla Esc
				closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
            });
        } else {
            PrintLibroSalarios(data.nomina_id);
        }
    });
}

function PrintVoucherPago(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Voucher_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>"
    }; 

    // Llamar a la función para mostrar el reporte
    viewReport(params);   
}

function PrintLibroSalarios(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Libro_salario_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>"
    }; 

    // Llamar a la función para mostrar el reporte
    viewReport(params);
}

var editar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_editar");
    $(tbody).on("click", "button.nomina_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominas.php';
        $('#formNomina #nomina_id').val(data.nomina_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNomina').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formNomina').attr({
                    'data-form': 'update'
                });
                $('#formNomina').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarNominaAjax.php'
                });
                $('#formNomina')[0].reset();
                $('#reg_nomina').hide();
                $('#edi_nomina').show();
                $('#delete_nomina').hide();
                $('#formNomina #nomina_detale').val(valores[0]);
                $('#formNomina #nomina_pago_planificado_id').val(valores[1]);
                $('#formNomina #nomina_pago_planificado_id').selectpicker('refresh');
                $('#formNomina #nomina_empresa_id').val(valores[2]);
                $('#formNomina #nomina_empresa_id').selectpicker('refresh');
                $('#formNomina #nomina_fecha_inicio').val(valores[3]);
                $('#formNomina #nomina_fecha_fin').val(valores[4]);
                $('#formNomina #nomina_importe').val(valores[5]);
                $('#formNomina #nomina_notas').val(valores[6]);
                $('#formNomina #tipo_nomina').val(valores[8]);
                $('#formNomina #tipo_nomina').selectpicker('refresh');
                $('#formNomina #pago_nomina').val(valores[9]);
                $('#formNomina #pago_nomina').selectpicker('refresh');

                if (data.estado == 1) {
                    $('#edi_nomina').attr('disabled', true);
                    $('#formNomina #nomina_activo').attr('checked', true);
                    $('#formNomina #label_nomina_activo').html("Generada");
                } else {
                    $('#edi_nomina').attr('disabled', false);
                    $('#formNomina #nomina_activo').attr('checked', false);
                    $('#formNomina #label_nomina_activo').html("Sin Generar");
                }

                caracteresnotaNomina();

                //HABILITAR OBJETOS								
                $('#formNomina #nomina_detale').attr('disabled', false);
                $('#formNomina #nomina_pago_planificado_id').attr('disabled', false);
                $('#formNomina #nomina_empresa_id').attr('disabled', false);
                $('#formNomina #tipo_nomina').attr('disabled', false);
                $('#formNomina #nomina_fecha_inicio').attr('readonly', false);
                $('#formNomina #nomina_fecha_fin').attr('readonly', false);
                $('#formNomina #nomina_importe').attr('readonly', false);
                $('#formNomina #nomina_notas').attr('disabled', false);
                $('#formNomina #search_nomina_notas_start').attr('disabled', false);
                $('#formNomina #search_nomina_notas_stop').attr('disabled', false);
                $('#formNomina #nomina_activo').attr('disabled', false);
                $('#formNomina #estado_nomina').show();;

                $('#formNomina #proceso_nomina').val("Editar");

                $('#modal_registrar_nomina').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_eliminar");
    $(tbody).on("click", "button.nomina_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominas.php';
        $('#formNomina #nomina_id').val(data.nomina_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNomina').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formNomina').attr({
                    'data-form': 'delete'
                });
                $('#formNomina').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarNominaAjax.php'
                });
                $('#formNomina')[0].reset();
                $('#reg_nomina').hide();
                $('#edi_nomina').hide();
                $('#delete_nomina').show();
                $('#formNomina #nomina_detale').val(valores[0]);
                $('#formNomina #nomina_pago_planificado_id').val(valores[1]);
                $('#formNomina #nomina_pago_planificado_id').selectpicker('refresh');
                $('#formNomina #nomina_empresa_id').val(valores[2]);
                $('#formNomina #nomina_empresa_id').selectpicker('refresh');
                $('#formNomina #nomina_fecha_inicio').val(valores[3]);
                $('#formNomina #nomina_fecha_fin').val(valores[4]);
                $('#formNomina #nomina_importe').val(valores[5]);
                $('#formNomina #nomina_notas').val(valores[6]);
                $('#formNomina #tipo_nomina').val(valores[8]);
                $('#formNomina #tipo_nomina').selectpicker('refresh');

                if (data.estado == 1) {
                    $('#delete_nomina').attr('disabled', true);
                    $('#formNomina #nomina_activo').attr('checked', true);
                    $('#formNomina #label_nomina_activo').html("Generada");
                } else {
                    $('#delete_nomina').attr('disabled', false);
                    $('#formNomina #nomina_activo').attr('checked', false);
                    $('#formNomina #label_nomina_activo').html("Sin Generar");
                }

                caracteresnotaNomina();

                //HABIITAR OBJETOS
                $('#formNomina #estado_nomina').show();

                //DESHABILITAR OBJETOS
                $('#formNomina #nomina_detale').attr('disabled', true);
                $('#formNomina #nomina_pago_planificado_id').attr('disabled', true);
                $('#formNomina #nomina_empresa_id').attr('disabled', true);
                $('#formNomina #tipo_nomina').attr('disabled', true);
                $('#formNomina #nomina_fecha_inicio').attr('readonly', true);
                $('#formNomina #nomina_fecha_fin').attr('readonly', true);
                $('#formNomina #nomina_importe').attr('readonly', true);
                $('#formNomina #nomina_notas').attr('disabled', true);
                $('#formNomina #search_nomina_notas_start').attr('disabled', true);
                $('#formNomina #search_nomina_notas_stop').attr('disabled', true);
                $('#formNomina #nomina_activo').attr('disabled', true);
                $('#formNomina #estado_nomina').show();

                $('#formNomina #proceso_nomina').val("Eliminar");

                $('#modal_registrar_nomina').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}
//FIN ACCIONES FROMULARIO CONTRATOS

/*INICIO FORMULARIO NOMINAS*/
function modal_nominas() {
    $('#formNomina').attr({
        'data-form': 'save'
    });
    $('#formNomina').attr({
        'action': '<?php echo SERVERURL;?>ajax/addNominaAjax.php'
    });
    $('#formNomina')[0].reset();
    $('#reg_nomina').show();
    $('#edi_nomina').hide();
    $('#delete_nomina').hide();

    $('#formNomina #nomina_empresa_id').val(1);
    $('#formNomina #nomina_empresa_id').selectpicker('refresh');

    $('#formNomina #tipo_nomina').val(1);
    $('#formNomina #tipo_nomina').selectpicker('refresh');

    $("#formNomina #grupo_salario").hide();

    $('#formNomina #nomina_detale').attr('disabled', false);
    $('#formNomina #nomina_pago_planificado_id').attr('disabled', false);
    $('#formNomina #nomina_empresa_id').attr('disabled', false);
    $('#formNomina #tipo_nomina').attr('disabled', false);
    $('#formNomina #nomina_fecha_inicio').attr('readonly', false);
    $('#formNomina #nomina_fecha_fin').attr('readonly', false);
    $('#formNomina #nomina_importe').attr('readonly', false);
    $('#formNomina #nomina_notas').attr('disabled', false);
    $('#formNomina #search_nomina_notas_start').attr('disabled', false);
    $('#formNomina #search_nomina_notas_stop').attr('disabled', false);
    $('#formNomina #nomina_activo').attr('disabled', false);
    $('#formNomina #estado_nomina').hide();

    $('#formNomina #proceso_nomina').val("Registro Nomina Empleados");

    $('#modal_registrar_nomina').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function modal_vales() {
    $('#formVales').attr({
        'data-form': 'save'
    });
    $('#formVales').attr({
        'action': '<?php echo SERVERURL;?>ajax/addValesAjax.php'
    });
    $('#formVales')[0].reset();
    $('#reg_vale').show();
    $('#edi_vale').hide();
    $('#delete_vale').hide();

    $('#formVales #vale_empleado').attr('disabled', false);
    $('#formVales #vale_monto').attr('disabled', false);
    $('#formVales #vale_notas').attr('disabled', false);

    $('#formVales #proceso_vale').val("Registro Vale Empleados");

    $('#modalRegistrarVales').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function modalNominasDetalles() {
    if ($('#form_main_nominas #estado_nomina').val() == 0) {
        $('#formNominaDetalles').attr({
            'data-form': 'save'
        });
        $('#formNominaDetalles').attr({
            'action': '<?php echo SERVERURL;?>ajax/addNominaDetallesAjax.php'
        });

        var nomina_id = $('#formNominaDetalles #nomina_id').val();
        var numero_nomima = $('#formNominaDetalles #nominad_numero').val();
        var detalle = $('#formNominaDetalles #nominad_detalle').val();

        $('#formNominaDetalles')[0].reset();
        getEmpleado();
        $('#formNominaDetalles #nomina_id').val(nomina_id);
        $('#formNominaDetalles #nominad_numero').val(numero_nomima);
        $('#formNominaDetalles #nominad_detalle').val(detalle);

        $('#formNominaDetalles #nominad_numero').val(numero_nomima);
        $('#formNominaDetalles #nominad_detalle').val(detalle);

        $('#formNominaDetalles #fecha_inicio').val($('#form_main_nominas_detalles #fecha_inicio').val());
        $('#formNominaDetalles #fecha_fin').val($('#form_main_nominas_detalles #fecha_fin').val());

        $('#reg_nominaD').show();
        $('#edi_nominaD').hide();
        $('#delete_nominaD').hide();

        //HABILITAR OBJETOS		
        $('#formNominaDetalles #nominad_empleados').prop('disabled', false);
        $('#formNominaDetalles #nominad_retroactivo').attr('readonly', false);
        $('#formNominaDetalles #nominad_bono').attr('readonly', false);
        $('#formNominaDetalles #nominad_otros_ingresos').attr('readonly', false);
        $('#formNominaDetalles #nominad_horas25').attr('readonly', false);
        $('#formNominaDetalles #nominad_horas50').attr('readonly', false);
        $('#formNominaDetalles #nominad_horas75').attr('readonly', false);
        $('#formNominaDetalles #nominad_horas100').attr('readonly', false);
        $('#formNominaDetalles #nominad_deducciones').attr('readonly', false);
        $('#formNominaDetalles #nominad_prestamo').attr('readonly', false);
        $('#formNominaDetalles #nominad_ihss').attr('readonly', false);
        $('#formNominaDetalles #nominad_rap').attr('readonly', false);
        $('#formNominaDetalles #nominad_isr').attr('readonly', false);
        $('#formNominaDetalles #nominad_incapacidad_ihss').attr('readonly', false);
        $('#formNominaDetalles #nomina_detalles_notas').attr('readonly', false);
        $('#formNominaDetalles #nominad_neto_ingreso').attr('readonly', true);
        $('#formNominaDetalles #nominad_neto_egreso').attr('readonly', true);
        $('#formNominaDetalles #nominad_neto').attr('readonly', true);
        $('#formNominaDetalles #nomina_detalles_activo').attr('disabled', false);
        $('#formNominaDetalles #estado_nomina_detalles').hide();

        //DESHABILITAR OBJETOS
        //$('#formNominaDetalles #nominad_diast').attr('readonly', true);

        $('#formNominaDetalles #proceso_nomina_detalles').val("Registro");
        $('#modal_registrar_nomina_detalles').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    } else {
        swal({
            title: "Error",
            text: "Lo sentimos, esta nomina ya ha sido generada, no puede agregar más empleados",
            icon: "error",
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        });
    }
}
/*FIN FORMULARIO NOMINAS*/

$(document).ready(function() {
    $("#modal_registrar_nomina").on('shown.bs.modal', function() {
        $(this).find('#formNomina #nomina_detale').focus();
    });
});

$('#formNomina #label_nomina_activo').html("Sin Generar");

$('#formNomina .switch').change(function() {
    if ($('input[name=nomina_activo]').is(':checked')) {
        $('#formNomina #label_nomina_activo').html("Generada");
        return true;
    } else {
        $('#formNomina #label_nomina_activo').html("Sin Generar");
        return false;
    }
});

$('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");

$('#formNominaDetalles .switch').change(function() {
    if ($('input[name=nomina_detalles_activo]').is(':checked')) {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Generada");
        return true;
    } else {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");
        return false;
    }
});

function getTipoNomina() {
    var url = '<?php echo SERVERURL;?>core/getTipoNomina.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #tipo_nomina').html("");
            $('#formNomina #tipo_nomina').html(data);
            $('#formNomina #tipo_nomina').selectpicker('refresh');
            $('#formNomina #tipo_nomina').val(1);
            $('#formNomina #tipo_nomina').selectpicker('refresh');
        }
    });
}

function getTipoContrato() {
    var url = '<?php echo SERVERURL;?>core/getTipoContrato.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_nominas #tipo_contrato_nomina').html("");
            $('#form_main_nominas #tipo_contrato_nomina').html(data);
            $('#form_main_nominas #tipo_contrato_nomina').selectpicker('refresh');
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
            $('#form_main_nominas #pago_planificado_nomina').html("");
            $('#form_main_nominas #pago_planificado_nomina').html(data);
            $('#form_main_nominas #pago_planificado_nomina').selectpicker('refresh');

            $('#formNomina #nomina_pago_planificado_id').html("");
            $('#formNomina #nomina_pago_planificado_id').html(data);
            $('#formNomina #nomina_pago_planificado_id').selectpicker('refresh');
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
        }
    });
}

function getEmpresa() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #nomina_empresa_id').html("");
            $('#formNomina #nomina_empresa_id').html(data);
            $('#formNomina #nomina_empresa_id').selectpicker('refresh');
        }
    });
}

//INICIO FORMULARIO CONRATO
function getEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNominaDetalles #nominad_empleados').html("");
            $('#formNominaDetalles #nominad_empleados').html(data);
            $('#formNominaDetalles #nominad_empleados').selectpicker('refresh');
        }
    });
}

function getEmpleadoVales() {
    var url = '<?php echo SERVERURL;?>core/getEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formVales #vale_empleado').html("");
            $('#formVales #vale_empleado').html(data);
            $('#formVales #vale_empleado').selectpicker('refresh');
        }
    });
}
// FIN FORMULARIO CONTRATO

$('#formNomina #nomina_notas').keyup(function() {
    var max_chars = 254;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#formNomina #charNum_nomina_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresnotaNomina() {
    var max_chars = 254;
    var chars = $('#formNomina #nomina_notas').val().length;
    var diff = max_chars - chars;

    $('#formNomina #charNum_nomina_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

$('#formNominaDetalles #nomina_detalles_notas').keyup(function() {
    var max_chars = 254;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#formNominaDetalles #charNum_nomina_detales_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresnotaNominaDetalles() {
    var max_chars = 254;
    var chars = $('#formNominaDetalles #nomina_detalles_notas').val().length;
    var diff = max_chars - chars;

    $('#formNominaDetalles #charNum_nomina_detales_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

$('#formVales #vale_notas').keyup(function() {
    var max_chars = 254;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#formVales #charNumvale_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresnotaValeNotas() {
    var max_chars = 254;
    var chars = $('#formVales #vale_notas').val().length;
    var diff = max_chars - chars;

    $('#formVales #charNumvale_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

//INICIO GRABACIONES POR VOZ
$(document).ready(function() {
    $('#formNomina #search_nomina_notas_stop').hide();

    var recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.lang = "es";

    $('#formNomina #search_nomina_notas_start').on('click', function(event) {
        $('#formNomina #search_nomina_notas_start').hide();
        $('#formNomina #search_nomina_notas_stop').show();

        recognition.start();

        recognition.onresult = function(event) {
            finalResult = '';
            var valor_anterior = $('#formNomina #nomina_notas').val();
            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    if (valor_anterior != "") {
                        $('#formNomina #nomina_notas').val(valor_anterior + ' ' + finalResult);
                        caracteresnotaNomina();
                    } else {
                        $('#formNomina #nomina_notas').val(finalResult);
                        caracteresnotaNomina();
                    }
                }
            }
        };
        return false;
    });

    $('#formNomina #search_nomina_notas_stop').on("click", function(event) {
        $('#formNomina #search_nomina_notas_start').show();
        $('#formNomina #search_nomina_notas_stop').hide();
        recognition.stop();
    });
    /*###############################################################################################################################*/
    $('#formNominaDetalles #search_nomina_detalles_notas_stop').hide();

    var recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.lang = "es";

    $('#formNominaDetalles #search_nomina_detalles_notas_start').on('click', function(event) {
        $('#formNominaDetalles #search_nomina_notas_start').hide();
        $('#formNominaDetalles #search_nomina_detalles_notas_stop').show();

        recognition.start();

        recognition.onresult = function(event) {
            finalResult = '';
            var valor_anterior = $('#formNominaDetalles #nomina_detalles_notas').val();
            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    if (valor_anterior != "") {
                        $('#formNominaDetalles #nomina_detalles_notas').val(valor_anterior + ' ' +
                            finalResult);
                        caracteresnotaNominaDetalles();
                    } else {
                        $('#formNominaDetalles #nomina_detalles_notas').val(finalResult);
                        caracteresnotaNominaDetalles();
                    }
                }
            }
        };
        return false;
    });

    $('#formNominaDetalles #search_nomina_detalles_notas_stop').on("click", function(event) {
        $('#formNominaDetalles #search_nomina_detalles_notas_start').show();
        $('#formNominaDetalles #search_nomina_detalles_notas_start').hide();
        recognition.stop();
    });
    /*###############################################################################################################################*/
    $('#formVales #search_vale_notas_stop').hide();

    var recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.lang = "es";

    $('#formVales #search_vale_notas_start').on('click', function(event) {
        $('#formVales #search_vale_notas_start').hide();
        $('#formVales #search_vale_notas_stop').show();

        recognition.start();

        recognition.onresult = function(event) {
            finalResult = '';
            var valor_anterior = $('#formVales #vale_notas').val();
            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    if (valor_anterior != "") {
                        $('#formVales #vale_notas').val(valor_anterior + ' ' +
                            finalResult);
                        caracteresnotaValeNotas();
                    } else {
                        $('#formVales #vale_notas').val(finalResult);
                        caracteresnotaValeNotas();
                    }
                }
            }
        };
        return false;
    });

    $('#formVales #search_vale_notas_stop').on("click", function(event) {
        $('#formVales #search_vale_notas_start').show();
        $('#formVales #search_vale_notas_start').hide();
        recognition.stop();
    });
});

//ACCIONES BOTON VOLVER
$("#volver_nomina").on("click", function(e) {
    e.preventDefault();
    $("#nomina_detalles").hide();
    $("#nomina_principal").show();
});

//INICO DETALLE DE NOMINAS
var listar_nominas_detalles = function() {
    var estado = $("#form_main_nominas_detalles #estado_nomina_detalles").val() || 0;
    var empleado = $("#form_main_nominas_detalles #detalle_nomina_empleado").val() || 0;
    var nomina_id = $("#form_main_nominas_detalles #nomina_id").val() || 0;
    $("#nominad_neto_ingreso1").val("");
    $("#nominad_neto_egreso1").val("");
    $("#nominad_neto1").val("");

    var table_nominas_detalles = $("#dataTableNominaDetalles").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableNominaDetalles.php",
            "data": {
                "estado": estado,
                "empleado": empleado,
                "nomina_id": nomina_id
            }
        },
        "columns": [{
                "data": "nomina_id"
            },
            {
                "data": "contrato"
            },
            {
                "data": "empresa"
            },
            {
                "data": "empleado"
            },
            {
                "data": "neto_ingresos",
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
                "data": "neto_egresos",
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
                "data": "neto",
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
                "data": "notas"
            },
            {
                "defaultContent": "<button class='table_editar nomina_detalles_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span></button>"
            },
            {
                "defaultContent": "<button class='table_eliminar nomina_detalles_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "2%",
                targets: 0
            },
            {
                width: "8%",
                targets: 1
            },
            {
                width: "12%",
                targets: 2
            },
            {
                width: "25%",
                targets: 3
            },
            {
                width: "10%",
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
                width: "20%",
                targets: 7
            },
            {
                width: "1%",
                targets: 8
            },
            {
                width: "1%",
                targets: 9
            }
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            var neto_ingreso = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto_ingreso']);

            var neto_egreso = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto_egreso']);

            var neto_neto = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['total_neto']);

            $('#neto_ingreso').html(neto_ingreso);
            $('#neto_egreso').html(neto_egreso);
            $('#neto').html(neto_neto);
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar listar_nominas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_nominas_detalles();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Agregar',
                titleAttr: 'Agregar Empleados',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalNominasDetalles();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                customize: function(doc) {
                    doc.content.splice(1, 0, {
                        margin: [0, 0, 0, 12],
                        alignment: 'left',
                        image: imagen,
                        width: 100,
                        height: 45
                    });
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_nominas_detalles.search('').draw();
    $('#buscar').focus();

    editar_nominas_detalles_dataTable("#dataTableNominaDetalles tbody", table_nominas_detalles);
    eliminar_nominas_detalles_dataTable("#dataTableNominaDetalles tbody", table_nominas_detalles);
}

var editar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_editar");
    $(tbody).on("click", "button.nomina_detalles_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formNominaDetalles').attr({
                    'data-form': 'update'
                });
                $('#formNominaDetalles').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarNominaDetallesAjax.php'
                });
                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').show();
                $('#delete_nominaD').hide();
                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[31]);

                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[31]);
                $('#formNominaDetalles #nominad_empleados').selectpicker('refresh');
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(
                    2));

                let salario = parseFloat(valores[9]).toFixed(2);
                let salario_diario = (valores[9] / 30).toFixed(2);
                var salario_hora = (valores[37] == 1) ? salario_diario / 8 : salario_diario / 6;

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora)
                    .toFixed(2));

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(valores[24]);
                $('#formNominaDetalles #nominad_neto_egreso').val(valores[25]);
                $('#formNominaDetalles #nominad_neto').val(valores[26]);
                $('#formNominaDetalles #nominad_detalle').val(valores[36]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);
                $('#formNominaDetalles #nominad_vale').val(valores[30]);
                $('#formNominaDetalles #hrse25_valor').val(valores[32]);
                $('#formNominaDetalles #hrse50_valor').val(valores[33]);
                $('#formNominaDetalles #hrse75_valor').val(valores[34]);
                $('#formNominaDetalles #hrse100_valor').val(valores[35]);

                calculoNomina();

                caracteresnotaNominaDetalles();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').attr('checked', true);
                    $('#edi_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').attr('checked', false);
                    $('#edi_nominaD').attr('disabled', false);
                }

                //HABILITAR OBJETOS							
                $('#formNominaDetalles #nominad_retroactivo').attr('readonly', false);
                $('#formNominaDetalles #nominad_bono').attr('readonly', false);
                $('#formNominaDetalles #nominad_otros_ingresos').attr('readonly', false);
                $('#formNominaDetalles #nominad_horas25').attr('readonly', false);
                $('#formNominaDetalles #nominad_horas50').attr('readonly', false);
                $('#formNominaDetalles #nominad_horas75').attr('readonly', false);
                $('#formNominaDetalles #nominad_horas100').attr('readonly', false);
                $('#formNominaDetalles #nominad_deducciones').attr('readonly', false);
                $('#formNominaDetalles #nominad_prestamo').attr('readonly', false);
                $('#formNominaDetalles #nominad_ihss').attr('readonly', false);
                $('#formNominaDetalles #nominad_rap').attr('readonly', false);
                $('#formNominaDetalles #nominad_isr').attr('readonly', false);
                $('#formNominaDetalles #nominad_incapacidad_ihss').attr('readonly', false);
                $('#formNominaDetalles #nomina_detalles_notas').attr('readonly', false);
                $('#formNominaDetalles #estado_nomina_detalles').show();

                //DESHABILITAR OBJETOS
                //$('#formNominaDetalles #nominad_diast').attr('readonly', true);
                $('#formNominaDetalles #nominad_empleados').prop('disabled', true);
                $('#formNominaDetalles #nominad_neto_ingreso').attr('readonly', true);
                $('#formNominaDetalles #nominad_neto_egreso').attr('readonly', true);
                $('#formNominaDetalles #nominad_neto').attr('readonly', true);
                $('#formNominaDetalles #nomina_detalles_activo').attr('disabled', true);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Editar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_eliminar");
    $(tbody).on("click", "button.nomina_detalles_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {                
                var valores = eval(registro);

                $('#formNominaDetalles').attr({
                    'data-form': 'delete'
                });
                $('#formNominaDetalles').attr({
                    'action': '<?php echo SERVERURL;?>ajax/eliminarNominaDetallesAjax.php'
                });
                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').hide();
                $('#delete_nominaD').show();
                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[3]);
                $('#formNominaDetalles #colaboradores_id').selectpicker('refresh');
                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[4]);
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(
                    2));

                let salario = parseFloat(valores[9]).toFixed(2);
                let salario_diario = (valores[9] / 30).toFixed(2);
                let salario_hora = parseFloat(salario_diario).toFixed() / 8;

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(salario_hora);

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(parseFloat(valores[24]).toFixed(2));
                $('#formNominaDetalles #nominad_neto_egreso').val(parseFloat(valores[25]).toFixed(2));
                $('#formNominaDetalles #nominad_neto').val(parseFloat(valores[26]).toFixed(2));
                $('#formNominaDetalles #nominad_detalle').val(valores[27]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);

                calculoNomina();

                caracteresnotaNominaDetalles();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').attr('checked', true);
                    $('#delete_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').attr('checked', false);
                    $('#delete_nominaD').attr('disabled', false);
                }

                //HABILITAR OBJETOS
                $('#formNominaDetalles #estado_nomina_detalles').show();

                //DESHABILITAR OBJETOS
                //$('#formNominaDetalles #nominad_diast').attr('readonly', true);
                $('#formNominaDetalles #nominad_retroactivo').attr('readonly', true);
                $('#formNominaDetalles #nominad_bono').attr('readonly', true);
                $('#formNominaDetalles #nominad_otros_ingresos').attr('readonly', true);
                $('#formNominaDetalles #nominad_horas25').attr('readonly', true);
                $('#formNominaDetalles #nominad_horas50').attr('readonly', true);
                $('#formNominaDetalles #nominad_horas75').attr('readonly', true);
                $('#formNominaDetalles #nominad_horas100').attr('readonly', true);
                $('#formNominaDetalles #nominad_deducciones').attr('readonly', true);
                $('#formNominaDetalles #nominad_prestamo').attr('readonly', true);
                $('#formNominaDetalles #nominad_ihss').attr('readonly', true);
                $('#formNominaDetalles #nominad_rap').attr('readonly', true);
                $('#formNominaDetalles #nominad_isr').attr('readonly', true);
                $('#formNominaDetalles #nominad_incapacidad_ihss').attr('readonly', true);
                $('#formNominaDetalles #nomina_detalles_notas').attr('readonly', true);
                $('#formNominaDetalles #nominad_neto_ingreso').attr('readonly', true);
                $('#formNominaDetalles #nominad_neto_egreso').attr('readonly', true);
                $('#formNominaDetalles #nominad_neto').attr('readonly', true);
                $('#formNominaDetalles #nomina_detalles_activo').attr('disabled', true);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Eliminar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}
//FIN DETALLE DE NOMINAS

/*$("#formNominaDetalles #nominad_empleados").on("change", function() {
    var url = '<?php echo SERVERURL;?>core/getDatosEmpleado.php';
    let colaboradores_id = $("#formNominaDetalles #nominad_empleados").val();

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'colaboradores_id=' + colaboradores_id,
        success: function(data) {
            var valores = eval(data);

            //VER EL TIPO DE EMPLEADO
            Pago Planificado
            1. Semanal 2 Quincenal 3 Mensual
            var valor_dividir =
                0; //ESTE ES EL VALOR QUE TRAE SEGUN EL TIPO DE PAGO PARA DIVIDIRSE CON EL SALARIO

            if (valores[6] == 1) {
                $('#formNominaDetalles #nominad_diast').val(7);
                valor_dividir = 7;
            }

            if (valores[6] == 2) {
                $('#formNominaDetalles #nominad_diast').val(15);
                valor_dividir = 15;
            }

            if (valores[6] == 3) {
                $('#formNominaDetalles #nominad_diast').val(30);
                valor_dividir = 30;
            }

            var salario = parseFloat(valores[3]);
            salario_diario = parseFloat(valores[3]) / parseFloat(valor_dividir);
            var _salario_diario = (parseFloat(valores[3]) / parseFloat(30)).toFixed(2);
            var salario_hora = 0;

            //EMPLEADO NORMAL
            if (valores[5] == 1) {
                salario_hora = parseFloat(salario_diario / 8).toFixed(2);
            } else { //EMPLEADO MEDICO
                salario_hora = parseFloat(salario_diario / 6).toFixed(2);
            }

            $('#formNominaDetalles #nominad_puesto').val(valores[0]);
            $('#formNominaDetalles #nominad_identidad').val(valores[1]);
            $('#formNominaDetalles #nominad_contrato_id').val(valores[2]);
            $('#formNominaDetalles #nominad_salario').val(parseFloat(salario).toFixed(2));
            $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[4]);
            $('#formNominaDetalles #nominad_sueldo_diario').val(_salario_diario);
            $('#formNominaDetalles #nominad_sueldo_hora').val(salario_hora);

            $('#formNominaDetalles #nominad_diast').val(ObtenerDiasTrabajados(colaboradores_id));
            calculoNomina();
        }
    });
});*/

/*function calculoHorasExtras(hora_valor, salario_hora, horas) {
    Tipo 1. Normal 2. Medico

    var valor = 0.00;

    if (horas == "25") {
        valor = (parseFloat(salario_hora) * 0.25 + salario_hora) * parseFloat(hora_valor);
    }

    if (horas == "50") {
        valor = (parseFloat(salario_hora) * 0.50 + salario_hora) * parseFloat(hora_valor);
    }

    if (horas == "75") {
        valor = (parseFloat(salario_hora) * 0.75 + salario_hora) * parseFloat(hora_valor);
    }

    if (horas == "100") {
        valor = (parseFloat(salario_hora) * 1 + salario_hora) * parseFloat(hora_valor);
    }

    console.log("El valor por hora es: ", valor);

    return parseFloat(valor);
}*/

/*function calculoNomina() {
    var neto_ingresos = 0;
    var neto_egresos = 0;
    var neto = 0;

    //INGRESOS
    var dias_trabajadas = 0;
    var salario = 0;
    var salario_dia = 0;
    var salario_hora = 0;
    var salario_diario = $('#formNominaDetalles #nominad_sueldo_diario').val();

    if ($('#formNominaDetalles #nominad_diast').val() != "" || $('#formNominaDetalles #nominad_diast').val() != null) {
        dias_trabajadas = parseFloat($('#formNominaDetalles #nominad_diast').val());
    }

    if ($('#formNominaDetalles #nominad_sueldo_diario').val() != "" || $('#formNominaDetalles #nominad_sueldo_diario')
        .val() != null) {
        salario_dia = salario_diario;
    }

    if ($('#formNominaDetalles #nominad_sueldo_hora').val() != "" || $('#formNominaDetalles #nominad_sueldo_hora')
        .val() != null) {
        salario_hora = parseFloat($('#formNominaDetalles #nominad_sueldo_hora').val());
    }

    var hora25 = 0;
    var hora50 = 0;
    var hora75 = 0;
    var hora100 = 0;

    if ($('#formNominaDetalles #nominad_horas25').val() != "" || $('#formNominaDetalles #nominad_horas25').val() !=
        null) {
        hora25 = parseFloat(calculoHorasExtras($('#formNominaDetalles #nominad_horas25').val(), salario_hora, "25"));
    }

    if ($('#formNominaDetalles #nominad_horas50').val() != "" || $('#formNominaDetalles #nominad_horas50').val() !=
        null) {
        hora50 = parseFloat(calculoHorasExtras($('#formNominaDetalles #nominad_horas50').val(), salario_hora, "25"));
    }

    if ($('#formNominaDetalles #nominad_horas75').val() != "" || $('#formNominaDetalles #nominad_horas75').val() !=
        null) {
        hora75 = parseFloat(calculoHorasExtras($('#formNominaDetalles #nominad_horas75').val(), salario_hora, "25"));
    }

    if ($('#formNominaDetalles #nominad_horas100').val() != "" || $('#formNominaDetalles #nominad_horas100').val() !=
        null) {
        hora100 = parseFloat(calculoHorasExtras($('#formNominaDetalles #nominad_horas100').val(), salario_hora, "25"));
    }

    console.log("Horas 25: " + hora25);
    console.log("Horas 50: " + hora50);
    console.log("Horas 75: " + hora75);
    console.log("Horas 100: " + hora100);

    var retroactivo = 0;
    var bono = 0;
    var otros_ingresos = 0;

    if ($('#formNominaDetalles #nominad_retroactivo').val() != "" || $('#formNominaDetalles #nominad_retroactivo')
        .val() != null) {
        retroactivo = parseFloat($('#formNominaDetalles #nominad_retroactivo').val());
    }

    if ($('#formNominaDetalles #nominad_bono').val() != "" || $('#formNominaDetalles #nominad_bono').val() != null) {
        bono = parseFloat($('#formNominaDetalles #nominad_bono').val());
    }

    if ($('#formNominaDetalles #nominad_otros_ingresos').val() != "" || $('#formNominaDetalles #nominad_otros_ingresos')
        .val() != null) {
        otros_ingresos = parseFloat($('#formNominaDetalles #nominad_otros_ingresos').val());
    }

    console.log("retroactivo: " + retroactivo);
    console.log("bono: " + bono);
    console.log("otros_ingresos: " + otros_ingresos);

    var sueldo_por_hora_trabajada = salario_dia * parseFloat(dias_trabajadas);

    neto_ingresos = (parseFloat(dias_trabajadas) * parseFloat(salario_dia)) + parseFloat(retroactivo) + parseFloat(
            bono) + parseFloat(otros_ingresos) + parseFloat(hora25) + parseFloat(hora50) + parseFloat(hora75) +
        parseFloat(
            hora100);

    console.log("Salario" + salario);
    console.log("reroactivo" + retroactivo);
    console.log("otros ingresos" + otros_ingresos);
    console.log("neto_ingresos" + neto_ingresos);

    //EGRESOS
    var deducciones = 0;
    var prestamo = 0;
    var ihss = 0;
    var rap = 0;
    var isr = 0;
    var vales = 0;
    var incapacidad_ihss = 0;

    if ($('#formNominaDetalles #nominad_deducciones').val() != "" || $('#formNominaDetalles #nominad_deducciones')
        .val() != null) {
        deducciones = parseFloat($('#formNominaDetalles #nominad_deducciones').val());
    }

    if ($('#formNominaDetalles #nominad_prestamo').val() != "" || $('#formNominaDetalles #nominad_prestamo').val() !=
        null) {
        prestamo = parseFloat($('#formNominaDetalles #nominad_prestamo').val());
    }

    if ($('#formNominaDetalles #nominad_ihss').val() != "" || $('#formNominaDetalles #nominad_ihss').val() != null) {
        ihss = parseFloat($('#formNominaDetalles #nominad_ihss').val());
    }

    if ($('#formNominaDetalles #nominad_rap').val() != "" || $('#formNominaDetalles #nominad_rap').val() != null) {
        rap = parseFloat($('#formNominaDetalles #nominad_rap').val());
    }

    if ($('#formNominaDetalles #nominad_isr').val() != "" || $('#formNominaDetalles #nominad_isr').val() != null) {
        isr = parseFloat($('#formNominaDetalles #nominad_isr').val());
    }

    if ($('#formNominaDetalles #nominad_vale').val() != "" || $('#formNominaDetalles #nominad_vale').val() != null) {
        vales = parseFloat($('#formNominaDetalles #nominad_vale').val());
    }

    if ($('#formNominaDetalles #nominad_incapacidad_ihss').val() != "" || $(
            '#formNominaDetalles #nominad_incapacidad_ihss').val() != null) {
        incapacidad_ihss = parseFloat($('#formNominaDetalles #nominad_incapacidad_ihss').val());
    }

    neto_egresos = parseFloat(deducciones) + parseFloat(prestamo) + parseFloat(ihss) + parseFloat(rap) + parseFloat(
        isr) + parseFloat(vales) + parseFloat(incapacidad_ihss);

    neto = parseFloat(neto_ingresos) - parseFloat(neto_egresos);

    $('#formNominaDetalles #nominad_neto_ingreso').val(parseFloat(Math.round(neto_ingresos)).toFixed(2));
    $('#formNominaDetalles #nominad_neto_egreso').val(parseFloat(Math.round(neto_egresos)).toFixed(2));
    $('#formNominaDetalles #nominad_neto').val(parseFloat(Math.round(neto)).toFixed(2));

    $('#nominad_neto_ingreso1').val(parseFloat(Math.round(neto_ingresos)).toFixed(2));
    $('#nominad_neto_egreso1').val(parseFloat(Math.round(neto_egresos)).toFixed(2));
    $('#nominad_neto1').val(parseFloat(Math.round(neto)).toFixed(2));
}*/

function pagoPlanificado(pago_planificado) {
    var valor_dividir = 0;

    // Mapear el tipo de pago a días trabajados
    var diasTrabajadosMap = {
        1: 7,
        2: 15,
        3: 30
    };

    return valor_dividir = diasTrabajadosMap[pago_planificado] || 0;
}

// Función para obtener datos de empleado
function obtenerDatosEmpleado(colaboradores_id) {
    var url = '<?php echo SERVERURL;?>core/getDatosEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'colaboradores_id=' + colaboradores_id,
        success: function(data) {
            var valores = JSON.parse(data);
            var validar_semanal = valores[9];

            var valor_dividir = pagoPlanificado(valores[6]);

            var salario = parseFloat(valores[3]);

            if (validar_semanal === 1) {

            } else {

            }

            var salario_diario = salario / parseFloat(30);
            var salario_hora = (valores[5] == 1) ? salario_diario / 8 : salario_diario / 6;

            // Asignar valores
            $('#formNominaDetalles #nominad_puesto').val(valores[0]);
            $('#formNominaDetalles #nominad_identidad').val(valores[1]);
            $('#formNominaDetalles #nominad_contrato_id').val(valores[2]);
            $('#formNominaDetalles #nominad_salario').val(parseFloat(salario).toFixed(2));
            $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[4]);
            $('#formNominaDetalles #nominad_sueldo_diario').val((salario / 30).toFixed(2));
            $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));
            $('#formNominaDetalles #nominad_vale').val(valores[7]);
            $('#formNominaDetalles #salario').val(parseFloat(valores[8]).toFixed(2));
            $('#formNominaDetalles #validar_semanal').val(valores[9])
            $('#formNominaDetalles #pago_planificado_id').val(valor_dividir)
            var fecha_inicio = $('#formNominaDetalles #fecha_inicio').val();
            var fecha_fin = $('#formNominaDetalles #fecha_fin').val();

            $('#formNominaDetalles #nominad_diast').val(ObtenerDiasTrabajados(colaboradores_id,
                fecha_inicio, fecha_fin));
            calculoNomina();
        }
    });
}

// Evento change para el campo #nominad_empleados
$("#formNominaDetalles #nominad_empleados").on("change", function() {
    var colaboradores_id = $(this).val();
    obtenerDatosEmpleado(colaboradores_id);
});

// Llamada a la función con un valor predeterminado (descomentar y ajustar según sea necesario)
// var colaboradores_id_predeterminado = 123; // Reemplaza con el valor deseado
// obtenerDatosEmpleado(colaboradores_id_predeterminado);



function calculoHorasExtras(hora_valor, salario_hora, horas) {
    var porcentaje = parseFloat(horas) / 100;

    if (porcentaje >= 0.25 && porcentaje <= 1) {
        return parseFloat((salario_hora * porcentaje + salario_hora) * hora_valor);
    }

    console.log("El valor por hora es: ", 0);
    return 0;
}

function calculoNomina() {
    var neto_ingresos = 0;
    var neto_egresos = 0;
    var neto = 0;

    var validar_semanal = parseFloat($('#formNominaDetalles #validar_semanal').val() === '' ? 0 : $(
        '#formNominaDetalles #validar_semanal').val());

    // INGRESOS
    var dias_trabajadas = parseFloat($('#formNominaDetalles #nominad_diast').val()) || 0;
    var salario_diario = parseFloat($('#formNominaDetalles #nominad_sueldo_diario').val()) || 0;
    var salario_hora = parseFloat($('#formNominaDetalles #nominad_sueldo_hora').val()) || 0;
    var salario_mensual = parseFloat($('#formNominaDetalles #nominad_salario').val()) || 0;
    var salario = parseFloat($('#formNominaDetalles #salario').val()) || 0;

    var hora25 = calculoHorasExtras($('#formNominaDetalles #nominad_horas25').val(), salario_hora, "25");
    var hora50 = calculoHorasExtras($('#formNominaDetalles #nominad_horas50').val(), salario_hora, "50");
    var hora75 = calculoHorasExtras($('#formNominaDetalles #nominad_horas75').val(), salario_hora, "75");
    var hora100 = calculoHorasExtras($('#formNominaDetalles #nominad_horas100').val(), salario_hora, "100");

    $('#formNominaDetalles #hrse25_valor').val(hora25.toFixed(2));
    $('#formNominaDetalles #hrse50_valor').val(hora50.toFixed(2));
    $('#formNominaDetalles #hrse75_valor').val(hora75.toFixed(2));
    $('#formNominaDetalles #hrse100_valor').val(hora100.toFixed(2));

    var retroactivo = parseFloat($('#formNominaDetalles #nominad_retroactivo').val()) || 0;
    var bono = parseFloat($('#formNominaDetalles #nominad_bono').val()) || 0;
    var otros_ingresos = parseFloat($('#formNominaDetalles #nominad_otros_ingresos').val()) || 0;

    if (validar_semanal === 1) {
        neto_ingresos = dias_trabajadas * ((salario_mensual / 4) / 7) + retroactivo + bono + otros_ingresos + hora25 +
            hora50 +
            hora75 +
            hora100;
    } else {
        neto_ingresos = dias_trabajadas * (salario_mensual / 30) + retroactivo + bono + otros_ingresos + hora25 +
            hora50 +
            hora75 +
            hora100;
    }

    // EGRESOS
    var deducciones = parseFloat($('#formNominaDetalles #nominad_deducciones').val()) || 0;
    var prestamo = parseFloat($('#formNominaDetalles #nominad_prestamo').val()) || 0;
    var ihss = parseFloat($('#formNominaDetalles #nominad_ihss').val()) || 0;
    var rap = parseFloat($('#formNominaDetalles #nominad_rap').val()) || 0;
    var isr = parseFloat($('#formNominaDetalles #nominad_isr').val()) || 0;
    var vales = parseFloat($('#formNominaDetalles #nominad_vale').val()) || 0;
    var incapacidad_ihss = parseFloat($('#formNominaDetalles #nominad_incapacidad_ihss').val()) || 0;

    neto_egresos = deducciones + prestamo + ihss + rap + isr + incapacidad_ihss + vales;

    neto = neto_ingresos - neto_egresos;

    // Actualizar valores en los campos
    $('#formNominaDetalles #nominad_neto_ingreso').val(neto_ingresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto_egreso').val(neto_egresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto').val(neto.toFixed(2));

    // También actualiza los valores en otros campos si es necesario
    $('#nominad_neto_ingreso1').val(neto_ingresos.toFixed(2));
    $('#nominad_neto_egreso1').val(neto_egresos.toFixed(2));
    $('#nominad_neto1').val(neto.toFixed(2));
}


function actualizarCampo(selector, valor) {
    $(selector).val(valor);
}


$("#formNominaDetalles #nominad_diast").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_retroactivo").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_retroactivo").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_bono").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_otros_ingresos").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_horas25").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_horas50").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_horas75").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_horas100").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_deducciones").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_prestamo").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_ihss").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_rap").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_isr").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_incapacidad_ihss").on("keyup", function() {
    calculoNomina();
});

$("#formNominaDetalles #nominad_vale").on("keyup", function() {
    calculoNomina();
});

function ObtenerDiasTrabajados(colaboradores_id, fechaiNomina, fechafNomina) {
    var url = '<?php echo SERVERURL;?>core/getDiasTrabajados.php';

    var dt;

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            colaboradores_id: colaboradores_id,
            fechaiNomina: fechaiNomina,
            fechafNomina: fechafNomina,
        },
        success: function(registro) {
            try {
                var valores = JSON.parse(registro);
                dt = valores[0];
            } catch (error) {
                console.error("Error al procesar la respuesta JSON:", error);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        },
        async: false // Considera eliminar esta opción y usar un enfoque asincrónico más moderno
    });

    return dt;
}

function getCuentaNominas() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #pago_nomina').html("");
            $('#formNomina #pago_nomina').html(data);
            $('#formNomina #pago_nomina').selectpicker('refresh');
        }
    });
}


//INICIO VALES
var listar_vales = function() {
    var table_vales = $("#DatatableVale").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableVales.php"
        },
        "columns": [{
                "data": "empleado"
            },
            {
                "data": "monto",
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
                "data": "nota"
            },
            {
                "defaultContent": "<button class='table_editar anular_vale btn btn-dark ocultar'><span class='fas fa-ban fa-lg'></span></button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "45%",
                targets: 0
            },
            {
                width: "10%",
                targets: 1
            },
            {
                width: "35%",
                targets: 2
            },
            {
                width: "2%",
                targets: 3
            }
        ],
        "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            var number = $.fn.dataTable.render
                .number(',', '.', 2, 'L ')
                .display(aData['neto_importe']);
            $('#neto_importe').html(number);
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Vales',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_vales();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Nomina Empleados',
                messageTop: 'Fecha: ' + convertDateFormat(today()),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                },
                customize: function(doc) {
                    doc.content.splice(1, 0, {
                        margin: [0, 0, 0, 12],
                        alignment: 'left',
                        image: imagen,
                        width: 100,
                        height: 45
                    });
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_vales.search('').draw();
    $('#buscar').focus();

    anular_vale_nominas_dataTable("#DatatableVale tbody", table_vales);
}

var anular_vale_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.anular_vale");
    $(tbody).on("click", "button.anular_vale", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Estás seguro?",
            content: {
                element: "span",
                attributes: {
                    innerHTML: "¿Desea anular este vale de <strong>" + data.empleado + "</strong>?",
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, anular el vale!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        }).then((willConfirm) => {
            if (willConfirm) {
                anularVale(data.vale_id);
            }
        });
    });
}

function anularVale(vale_id) {
    var url = '<?php echo SERVERURL;?>core/anularVale.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'vale_id=' + vale_id,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "El vale ha sido anulado correctamente",
                    type: "success",
                    timer: 3000,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                    
                });
                listar_vales();
            } else {
                swal({
                    title: "Error",
                    text: "Lo sentimos, no se puede anular el vale",
                    type: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
            }
        }
    });
}
//FIN VALES
</script>