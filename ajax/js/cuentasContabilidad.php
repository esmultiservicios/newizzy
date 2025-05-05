<script>
$(() => {
    listar_cuentas_contabilidad();

	$('#formMainCuentasContabilidad #search').on("click", function(e) {
        e.preventDefault();
        listar_cuentas_contabilidad();
    });

    // Evento para el botón de Limpiar (reset)
    $('#formMainCuentasContabilidad').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

        listar_cuentas_contabilidad();
    });    
});

//INICIO ACCIONES FORMULARIO CUENTAS EN CONTABILIDAD
var listar_cuentas_contabilidad = function() {
    var fechai = $("#formMainCuentasContabilidad #fechai").val();
    var fechaf = $("#formMainCuentasContabilidad #fechaf").val();

    var table_cuentas_contabilidad = $("#dataTableCuentasContabilidad").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableCuentas.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "nombre"
            },
            {
                "data": "saldo_anterior",
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
                "data": "ingreso",
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
                "data": "egreso",
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
                "data": "saldo_cierre",
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
                width: "11.11%",
                targets: 0
            },
            {
                width: "17.11%",
                targets: 1
            },
            {
                width: "15.11%",
                targets: 2
            },
            {
                width: "11.11%",
                targets: 3
            },
            {
                width: "11.11%",
                targets: 4
            },
            {
                width: "13.11%",
                targets: 5
            },
            {
                width: "11.11%",
                targets: 6
            },
            {
                width: "5.11%",
                targets: 7
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Registro de Cuentas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_cuentas_contabilidad();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Cuentas',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_cuentas_contables();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Registro de Cuentas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Reporte Registro de Cuentas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
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
        },
    });
    table_cuentas_contabilidad.search('').draw();
    $('#buscar').focus();

    editar_cuentas_contabilidad_dataTable("#dataTableCuentasContabilidad tbody", table_cuentas_contabilidad);
    eliminar_cuentas_contabilidad_dataTable("#dataTableCuentasContabilidad tbody", table_cuentas_contabilidad);
}

var editar_cuentas_contabilidad_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarCuentasContabilidad.php';
        $('#formCuentasContables #cuentas_id').val(data.cuentas_id)

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formCuentasContables').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formCuentasContables').attr({
                    'data-form': 'update'
                });
                $('#formCuentasContables').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarCuentaContabilidadAjax.php'
                });
                $('#formCuentasContables')[0].reset();
                $('#reg_cuentas').hide();
                $('#edi_cuentas').show();
                $('#delete_cuentas').hide();

                $('#formCuentasContables #cuenta_codigo').val(valores[1]);
                $('#formCuentasContables #cuenta_nombre').val(valores[2]);

                if (valores[3] == 1) {
                    $('#formCuentasContables #clientes_activo').attr('checked', true);
                } else {
                    $('#formCuentasContables #clientes_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS
                $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
                $('#formCuentasContables #estado_cuentas_contables').show();

                //DESHABILITAR
                $('#formCuentasContables #cuenta_codigo').attr("readonly", true);

                $('#formCuentasContables #pro_cuentas').val("Editar");
                $('#modalCuentascontables').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var eliminar_cuentas_contabilidad_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();


        var cuentas_id = data.cuentas_id;
        var nombreCuenta = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la cuenta?<br><br>
                        <strong>Nombre:</strong> ${nombreCuenta}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarCuentaContabilidadAjax.php',
                    data: {
                        cuentas_id: cuentas_id
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
//FIN ACCIONES FORMULARIO CUENTAS EN CONTABILIDAD

/*INICIO FORMULARIO CUENTAS CONTABLES*/
function modal_cuentas_contables() {
    $('#formCuentasContables').attr({
        'data-form': 'save'
    });
    $('#formCuentasContables').attr({
        'action': '<?php echo SERVERURL;?>ajax/addCuentasContablesAjax.php'
    });
    $('#formCuentasContables')[0].reset();
    $('#reg_cuentas').show();
    $('#edi_cuentas').hide();
    $('#delete_cuentas').hide();

    //HABILITAR OBJETOS
    $('#formCuentasContables #cuenta_codigo').attr("readonly", false);
    $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
    $('#formCuentasContables #cuentas_activo').attr("disabled", false);
    $('#formCuentasContables #estado_cuentas_contables').hide();

    $('#formCuentasContables #pro_cuentas').val("Registro");

    $('#modalCuentascontables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO CUENTAS CONTABLES*/

$('#formCuentasContables #label_cuentas_activo').html("Activo");

$('#formCuentasContables .switch').change(function() {
    if ($('input[name=cuentas_activo]').is(':checked')) {
        $('#formCuentasContables #label_cuentas_activo').html("Activo");
        return true;
    } else {
        $('#formCuentasContables #label_cuentas_activo').html("Inactivo");
        return false;
    }
});

$(document).ready(function() {
    $("#modalCuentascontables").on('shown.bs.modal', function() {
        $(this).find('#formCuentasContables #cuenta_nombre').focus();
    });
});
</script>