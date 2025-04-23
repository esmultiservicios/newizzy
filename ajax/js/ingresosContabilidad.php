<script>
$(document).ready(function() {
    // Inicializar componentes al cargar la página
    listar_ingresos_contabilidad();
    getClientesIngresos();
    getCuentaIngresos();
    getEmpresaIngresos();
    
    // Configurar eventos
    inicializarEventos();
});

// Función para inicializar todos los eventos
function inicializarEventos() {
    // Evento para buscar ingresos
    $('#formMainIngresosContabilidad #search').on("click", function(e) {
        e.preventDefault();
        listar_ingresos_contabilidad();
    });

    // Eventos para el cálculo automático de totales
    const camposCalculo = ["#subtotal_ingresos", "#isv_ingresos", "#descuento_ingresos", "#nc_ingresos"];
    camposCalculo.forEach(campo => {
        $("#formIngresosContables " + campo).on("keyup change", function() {
            if (parseFloat($(this).val()) < 0) {
                $(this).val(0);
                showNotify("warning", "Advertencia", "Los valores no pueden ser negativos");
            }
            calcularTotalIngreso();
        });
    });
}

// Función para calcular el total del ingreso
function calcularTotalIngreso() {
    const form = "#formIngresosContables ";
    const subtotal = parseFloat($(form + "#subtotal_ingresos").val()) || 0;
    const isv = parseFloat($(form + "#isv_ingresos").val()) || 0;
    const descuento = parseFloat($(form + "#descuento_ingresos").val()) || 0;
    const nc = parseFloat($(form + "#nc_ingresos").val()) || 0;
    
    const total = subtotal + isv - descuento - nc;
    $(form + "#total_ingresos").val(total.toFixed(2));
}

// Función debounce para mejorar rendimiento en búsquedas
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Función para buscar clientes
function buscarClientes(searchText) {
    return $.ajax({
        type: "POST",
        url: "<?php echo SERVERURL;?>core/buscar_clientes.php",
        data: { searchText: searchText },
        dataType: "html"
    });
}

// Función para mostrar el total en el footer
var total_ingreso_footer = function() {
    var fechai = $("#formMainIngresosContabilidad #fechai").val();
    var fechaf = $("#formMainIngresosContabilidad #fechaf").val();

    $.ajax({
            url: '<?php echo SERVERURL;?>core/totalIngresoFooter.php',
            type: "POST",
            data: {
                "fechai": fechai,
                "fechaf": fechaf
            }
        })
        .done(function(data) {
            data = JSON.parse(data);

            // Formatear los montos con separadores de miles y coma para decimales
            var totalFormatted = "L. " + parseFloat(data.total).toLocaleString('es-HN');
            var subtotalFormatted = "L. " + parseFloat(data.subtotal).toLocaleString('es-HN');
            var impuestoFormatted = "L. " + parseFloat(data.impuesto).toLocaleString('es-HN');
            var descuentoFormatted = "L. " + parseFloat(data.descuento).toLocaleString('es-HN');
            var ncFormatted = "L. " + parseFloat(data.nc).toLocaleString('es-HN');

            // Asignar los montos formateados a los elementos HTML
            $("#total-footer-ingreso").html(totalFormatted);
            $("#subtotal-i").html(subtotalFormatted);
            $("#impuesto-i").html(impuestoFormatted);
            $("#descuento-i").html(descuentoFormatted);
            $("#nc-i").html(ncFormatted);
        })
        .fail(function(data) {
            console.log("Error al cargar totales del footer");
        });
}

// Función para listar los ingresos en la tabla
var listar_ingresos_contabilidad = function() {
    var estado = 1;
    if ($("#formMainIngresosContabilidad #estado_ingresos").val() == null || $("#formMainIngresosContabilidad #estado_ingresos").val() == "") {
        estado = 1;
    } else {
        estado = $("#formMainIngresosContabilidad #estado_ingresos").val();
    }

    var fechai = $("#formMainIngresosContabilidad #fechai").val();
    var fechaf = $("#formMainIngresosContabilidad #fechaf").val();

    var table_ingresos_contabilidad = $("#dataTableIngresosContabilidad").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableIngresosContabilidad.php",
            "data": {
                "fechai": fechai,
                "fechaf": fechaf,
                "estado": estado,
            }
        },
        "columns": [{
                "data": "fecha_registro"
            },
            {
                "data": "tipo_ingreso"
            },
            {
                "data": "ingresos_id"
            },
            {
                "data": "fecha"
            },
            {
                "data": "nombre"
            },
            {
                "data": "cliente"
            },
            {
                "data": "factura"
            },
            {
                "data": "subtotal",
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
                "data": "impuesto",
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
                "data": "descuento",
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
                "data": "total",
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
                "data": "observacion"
            },
            {
                "defaultContent": "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit'></span></button>"
            },
            {
                "defaultContent": "<button class='table_reportes print_gastos btn btn-dark ocultar'><span class='fas fa-file-download fa-lg'></span></button>"
            },
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "7.69%",
                targets: 0
            },
            {
                width: "7.69%",
                targets: 1
            },
            {
                width: "7.69%",
                targets: 2
            },
            {
                width: "7.69%",
                targets: 3
            },
            {
                width: "7.69%",
                targets: 4
            },
            {
                width: "7.69%",
                targets: 5
            },
            {
                width: "7.69%",
                targets: 6
            },
            {
                width: "7.69%",
                targets: 7
            },
            {
                width: "7.69%",
                targets: 8
            },
            {
                width: "7.69%",
                targets: 9
            },
            {
                width: "7.69%",
                targets: 10
            },
            {
                width: "7.69%",
                targets: 11
            },
            {
                width: "7.69%",
                targets: 12
            },
            {
                width: "7.69%",
                targets: 13
            },
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Registro Ingresos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_ingresos_contabilidad();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Ingresos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_ingresos_contabilidad();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Registro Ingresos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                footer: true,
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Reporte Registro Ingresos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
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
            edit_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
            view_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
            total_ingreso_footer();
        }
    });

    table_ingresos_contabilidad.search('').draw();
    $('#buscar').focus();
}

// Función para editar ingresos desde la tabla
var edit_reporte_ingresos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarIngresos.php';
        $('#formIngresosContables #ingresos_id').val(data.ingresos_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formIngresosContables').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formIngresosContables').attr({
                    'data-form': 'update'
                });
                $('#formIngresosContables').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarIngresosAjax.php'
                });
                $('#formIngresosContables')[0].reset();
                $('#reg_ingresosContabilidad').hide();
                $('#edi_ingresosContabilidad').show();
                $('#delete_ingresosContabilidad').hide();
                $('#formIngresosContables #pro_ingresos_contabilidad').val("Editar");
                $('#formIngresosContables #cliente_ingresos').val(valores[0]);
                $('#formIngresosContables #cliente_ingresos').selectpicker('refresh');
                $('#formIngresosContables #cuenta_ingresos').val(valores[1]);
                $('#formIngresosContables #cuenta_ingresos').selectpicker('refresh');
                $('#formIngresosContables #empresa_ingresos').val(valores[2]);
                $('#formIngresosContables #empresa_ingresos').selectpicker('refresh');
                $('#formIngresosContables #fecha_ingresos').val(valores[3]);
                $('#formIngresosContables #factura_ingresos').val(valores[4]);
                $('#formIngresosContables #subtotal_ingresos').val(valores[5]);
                $('#formIngresosContables #isv_ingresos').val(valores[6]);
                $('#formIngresosContables #descuento_ingresos').val(valores[7]);
                $('#formIngresosContables #nc_ingresos').val(valores[8]);
                $('#formIngresosContables #total_ingresos').val(valores[9]);
                $('#formIngresosContables #observacion_ingresos').val(valores[10]);
                $('#formIngresosContables #recibide_ingresos').val(valores[11]);

                //DESHABILITAR OBJETOS
                $('#formIngresosContables #cuenta_ingresos').attr('disabled', true);
                $('#formIngresosContables #empresa_ingresos').attr('disabled', true);
                $('#formIngresosContables #subtotal_ingresos').attr('disabled', true);
                $('#formIngresosContables #isv_ingresos').attr('disabled', true);
                $('#formIngresosContables #descuento_ingresos').attr('disabled', true);
                $('#formIngresosContables #nc_ingresos').attr('disabled', true);
                $('#formIngresosContables #total_ingresos').attr('disabled', true);
                $('#formIngresosContables #recibide_ingresos').attr('disabled', true);
                $('#formIngresosContables #buscar_cuenta_ingresos').hide();
                $('#formIngresosContables #buscar_empresa_ingresos').hide();

                $('#modalIngresosContables').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

// Función para generar reportes desde la tabla
var view_reporte_ingresos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_gastos");
    $(tbody).on("click", "button.print_gastos", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printIngresos(data.ingresos_id);
    });
}

// Función para imprimir ingresos
function printIngresos(ingresos_id) {
    var url = '<?php echo SERVERURL; ?>core/generaIngresos.php?ingresos_id=' + ingresos_id;
    window.open(url);
}

/*INICIO FORMULARIO INGRESOS CONTABLES*/
function modal_ingresos_contabilidad() {
    $('#formIngresosContables').attr({
        'data-form': 'save'
    });
    $('#formIngresosContables').attr({
        'action': '<?php echo SERVERURL;?>ajax/addIngresoContabilidadAjax.php'
    });
    $('#formIngresosContables')[0].reset();
    $('#reg_ingresosContabilidad').show();
    $('#edi_ingresosContabilidad').hide();
    $('#delete_ingresosContabilidad').hide();

    //HABILITAR OBJETOS
    $('#formIngresosContables #cuenta_codigo').attr("readonly", false);
    $('#formIngresosContables #cuenta_nombre').attr("readonly", false);
    $('#formIngresosContables #cuentas_activo').attr("disabled", false);
    $('#formIngresosContables #cuenta_ingresos').attr('disabled', false);
    $('#formIngresosContables #empresa_ingresos').attr('disabled', false);
    $('#formIngresosContables #subtotal_ingresos').attr('disabled', false);
    $('#formIngresosContables #isv_ingresos').attr('disabled', false);
    $('#formIngresosContables #descuento_ingresos').attr('disabled', false);
    $('#formIngresosContables #nc_ingresos').attr('disabled', false);
    $('#formIngresosContables #total_ingresos').attr('disabled', false);
    $('#formIngresosContables #recibide_ingresos').attr('disabled', false);
    $('#formIngresosContables #buscar_cuenta_ingresos').show();
    $('#formIngresosContables #buscar_empresa_ingresos').show();

    $('#formIngresosContables #pro_ingresos_contabilidad').val("Registro");

    $('#modalIngresosContables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
/*FIN FORMULARIO INGRESOS CONTABLES*/

// Función para obtener empresas
function getEmpresaIngresos() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formIngresosContables #empresa_ingresos').html("");
            $('#formIngresosContables #empresa_ingresos').html(data);
            $('#formIngresosContables #empresa_ingresos').selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "No se pudieron cargar las empresas");
        }
    });
}

// Función para obtener cuentas contables
function getCuentaIngresos() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formIngresosContables #cuenta_ingresos').html("");
            $('#formIngresosContables #cuenta_ingresos').html(data);
            $('#formIngresosContables #cuenta_ingresos').selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "No se pudieron cargar las cuentas contables");
        }
    });
}

// Función para obtener clientes
function getClientesIngresos() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getClientes.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formIngresosContables #recibide_ingresos');
            select.empty();
            
            if(response.success) {
                response.data.forEach(cliente => {
                    select.append(`
                        <option value="${cliente.clientes_id}" 
                                data-subtext="${cliente.rtn || 'Sin RTN o Identidad'}">
                            ${cliente.nombre}
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
            $('#formIngresosContables #recibide_ingresos').html('<option value="">Error al cargar</option>');
            $('#formIngresosContables #recibide_ingresos').selectpicker('refresh');
        }
    });
}

// Eventos para el foco en modales
$(document).ready(function() {
    $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
    });

    $("#modalIngresosContables").on('shown.bs.modal', function() {
        $(this).find('#formIngresosContables #recibide_ingresos').focus();
    });
});

$('#btnNuevoCliente').on('click', function() {
    modal_clientes();
});
</script>