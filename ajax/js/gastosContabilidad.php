<script>
$(document).ready(function() {
    listar_gastos_contabilidad();
    getEmpresaEgresos();
    getCuentaEgresos();
    getProveedorEgresos();
    getCategoriaGastos();
});

$('#formMainGastosContabilidad #search').on("click", function(e) {
    e.preventDefault();
    listar_gastos_contabilidad();
});

//INICIO ACCIONES FORMULARIO EGRESOS
var total_gastos_footer = function() {
    var fechai = $("#formMainGastosContabilidad #fechai").val();
    var fechaf = $("#formMainGastosContabilidad #fechaf").val();

    $.ajax({
            url: '<?php echo SERVERURL;?>core/totalGastosFooter.php',
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
            $("#total-footer-gastos").html(totalFormatted);
            $("#subtotal-g").html(subtotalFormatted);
            $("#impuesto-g").html(impuestoFormatted);
            $("#descuento-g").html(descuentoFormatted);
            $("#nc-g").html(ncFormatted);
        })
        .fail(function(data) {
            console.log("total gastos error");
        });
}

var listar_gastos_contabilidad = function() {
    var estado = 1;
    if ($("#formMainGastosContabilidad #estado_egresos").val() == null || $(
            "#formMainGastosContabilidad #estado_egresos").val() == "") {
        estado = 1;
    } else {
        estado = $("#formMainGastosContabilidad #estado_egresos").val();
    }

    var fechai = $("#formMainGastosContabilidad #fechai").val();
    var fechaf = $("#formMainGastosContabilidad #fechaf").val();

    var table_gastos_contabilidad = $("#dataTableGastosContabilidad").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableEgresosContabilidad.php",
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
                "data": "egresos_id"
            },
            {
                "data": "categoria"
            },
            {
                "data": "fecha"
            },
            {
                "data": "nombre"
            },
            {
                "data": "proveedor"
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
                "data": "nc",
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
                width: "7.14%",
                targets: 0
            },
            {
                width: "7.14%",
                targets: 1
            },
            {
                width: "7.14%",
                targets: 2
            },
            {
                width: "7.14%",
                targets: 3
            },
            {
                width: "7.14%",
                targets: 4
            },
            {
                width: "7.14%",
                targets: 5
            },
            {
                width: "7.14%",
                targets: 6
            },
            {
                width: "7.14%",
                targets: 7
            },
            {
                width: "7.14%",
                targets: 8
            },
            {
                width: "7.14%",
                targets: 9
            },
            {
                width: "7.14%",
                targets: 10
            },
            {
                width: "7.14%",
                targets: 11
            },
            {
                width: "7.14%",
                targets: 12
            },
            {
                width: "7.14%",
                targets: 13
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Registro Gastos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_gastos_contabilidad();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Egresos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_egresos_contabilidad();
                }
            },
            {
                text: '<i class="fas fa-layer-group fa-lg crear"></i> Categorías',
                titleAttr: 'Categorías',
                className: 'table_crear btn btn-dark ocultar',
                action: function() {
                    modal_categorias_contabilidad();
                }
            }, {
                text: '<i class="fas fa-layer-group fa-lg crear"></i> Reporte Categorías',
                titleAttr: 'Reporte Categorías',
                className: 'table_crear btn btn-info ocultar',
                action: function() {
                    modal_reporte_categorias_contabilidad();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Registro Gastos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdfHtml5',
                footer: true,
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Reporte Registro Gastos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
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

    table_gastos_contabilidad.search('').draw();

    $('#buscar').focus();

    edit_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
    view_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
    total_gastos_footer();
}

var edit_reporte_gastos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarGastos.php';
        $('#formEgresosContables #egresos_id').val(data.egresos_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formEgresosContables').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formEgresosContables').attr({
                    'data-form': 'update'
                });
                $('#formEgresosContables').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarGastosAjax.php'
                });
                $('#formEgresosContables')[0].reset();
                $('#reg_egresosContabilidad').hide();
                $('#edi_egresosContabilidad').show();
                $('#delete_egresosContabilidad').hide();
                $('#formEgresosContables #pro_egresos_contabilidad').val("Editar Egresos");
                $('#formEgresosContables #proveedor_egresos').val(valores[0]);
                $('#formEgresosContables #proveedor_egresos').selectpicker('refresh');
                $('#formEgresosContables #cuenta_egresos').val(valores[1]);
                $('#formEgresosContables #cuenta_egresos').selectpicker('refresh');
                $('#formEgresosContables #empresa_egresos').val(valores[2]);
                $('#formEgresosContables #empresa_egresos').selectpicker('refresh');
                $('#formEgresosContables #fecha_egresos').val(valores[3]);
                $('#formEgresosContables #factura_egresos').val(valores[4]);
                $('#formEgresosContables #subtotal_egresos').val(valores[5]);
                $('#formEgresosContables #isv_egresos').val(valores[6]);
                $('#formEgresosContables #descuento_egresos').val(valores[7]);
                $('#formEgresosContables #nc_egresos').val(valores[8]);
                $('#formEgresosContables #total_egresos').val(valores[9]);
                $('#formEgresosContables #observacion_egresos').val(valores[10]);

                //DESHABILITAR OBJETOS
                $('#formEgresosContables #cuenta_egresos').attr('disabled', true);
                $('#formEgresosContables #empresa_egresos').attr('disabled', true);
                $('#formEgresosContables #subtotal_egresos').attr('disabled', true);
                $('#formEgresosContables #isv_egresos').attr('disabled', true);
                $('#formEgresosContables #descuento_egresos').attr('disabled', true);
                $('#formEgresosContables #nc_egresos').attr('disabled', true);
                $('#formEgresosContables #total_egresos').attr('disabled', true);
                $('#formEgresosContables #buscar_cuenta_egresos').hide();
                $('#formEgresosContables #buscar_empresa_egresos').hide();

                $('#modalEgresosContables').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var view_reporte_gastos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_gastos");
    $(tbody).on("click", "button.print_gastos", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printGastos(data.egresos_id);
    });
}

function printGastos(egresos_id) {
    var url = '<?php echo SERVERURL; ?>core/generaGastos.php?egresos_id=' + egresos_id;
    window.open(url);
}

/*INICIO FORMULARIO EGRESOS CONTABLES*/
function modal_egresos_contabilidad() {
    getCategoriaGastos();

    $('#formEgresosContables').attr({
        'data-form': 'save'
    });
    $('#formEgresosContables').attr({
        'action': '<?php echo SERVERURL;?>ajax/addEgresoContabilidadAjax.php'
    });
    $('#formEgresosContables')[0].reset();
    $('#reg_egresosContabilidad').show();
    $('#edi_egresosContabilidad').hide();
    $('#delete_egresosContabilidad').hide();

    //HABILITAR OBJETOS
    $('#formEgresosContables #cuenta_codigo').attr("readonly", false);
    $('#formEgresosContables #cuenta_nombre').attr("readonly", false);
    $('#formEgresosContables #cuentas_activo').attr("disabled", false);
    $('#formEgresosContables #buscar_cuenta_egresos').show();
    $('#formEgresosContables #buscar_empresa_egresos').show();
    $('#formEgresosContables #cuenta_egresos').attr('disabled', false);
    $('#formEgresosContables #empresa_egresos').attr('disabled', false);
    $('#formEgresosContables #subtotal_egresos').attr('disabled', false);
    $('#formEgresosContables #isv_egresos').attr('disabled', false);
    $('#formEgresosContables #descuento_egresos').attr('disabled', false);
    $('#formEgresosContables #nc_egresos').attr('disabled', false);
    $('#formEgresosContables #total_egresos').attr('disabled', false);

    $('#formEgresosContables #pro_egresos_contabilidad').val("Registrar Egresos");

    $('#modalEgresosContables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function modal_categorias_contabilidad() {
    $('#formCategoriaEgresos').attr({
        'data-form': 'save'
    });
    $('#formCategoriaEgresos').attr({
        'action': '<?php echo SERVERURL;?>ajax/addCategoriaEgresos.php'
    });
    $('#formCategoriaEgresos')[0].reset();
    $('#regCategoriaEgresos').show();

    $('#formCategoriaEgresos #pro_categoriaEgresos').val("Registro Categorias");

    listar_categoria_egresos();

    $('#modalCategoriasEgresos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function modal_editar_categorias_contabilidad(categoria_gastos_id, categoria) {
    $('#formUpdateCategoriaEgresos').attr({
        'data-form': 'update'
    });
    $('#formUpdateCategoriaEgresos').attr({
        'action': '<?php echo SERVERURL;?>ajax/modificarCategoriaEgresos.php'
    });
    $('#formUpdateCategoriaEgresos')[0].reset();
    $('#ediCategoriaEgresos').show();

    $('#formUpdateCategoriaEgresos #categoria_gastos_id').val(categoria_gastos_id);
    $('#formUpdateCategoriaEgresos #categoria').val(categoria);
    $('#formUpdateCategoriaEgresos #pro_categoriaEgresos').val("Editar Categorias");

    $('#modalUpdateCategoriasEgresos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function modal_reporte_categorias_contabilidad() {
    listar_reporte_categoria_egresos();

    $('#modalReporteCategorias').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/*FIN FORMULARIO EGRESOS CONTABLES*/

function getProveedorEgresos() {

    var url = '<?php echo SERVERURL;?>core/getProveedores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEgresosContables #proveedor_egresos').html("");
            $('#formEgresosContables #proveedor_egresos').html(data);
            $('#formEgresosContables #proveedor_egresos').selectpicker('refresh');
        }
    });
}

function getCategoriaGastos() {

    var url = '<?php echo SERVERURL;?>core/getCategoriaGastos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEgresosContables #categoria_gastos').html("");
            $('#formEgresosContables #categoria_gastos').html(data);
            $('#formEgresosContables #categoria_gastos').selectpicker('refresh');

            $('#formEgresosContables #categoria_gastos').val(0);
            $('#formEgresosContables #categoria_gastos').selectpicker('refresh');
        }
    });
}

function getCuentaEgresos() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEgresosContables #cuenta_egresos').html("");
            $('#formEgresosContables #cuenta_egresos').html(data);
            $('#formEgresosContables #cuenta_egresos').selectpicker('refresh');
        }
    });
}

function getEmpresaEgresos() {
    var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formEgresosContables #empresa_egresos').html("");
            $('#formEgresosContables #empresa_egresos').html(data);
            $('#formEgresosContables #empresa_egresos').selectpicker('refresh');
        }
    });
}

//INICIO CALCULAR VALORES INGRESADOS EN EGRESOS
$(document).ready(function() {
    $("#formEgresosContables #subtotal_egresos").on("keyup", function() {
        var subtotal;
        var isv;
        var descuento;
        var nc;

        if ($("#formEgresosContables #subtotal_egresos").val() != "") {
            subtotal = parseFloat($("#formEgresosContables #subtotal_egresos").val());
        } else {
            subtotal = 0;
        }

        if ($("#formEgresosContables #isv_egresos").val() != "") {
            isv = parseFloat($("#formEgresosContables #isv_egresos").val());
        } else {
            isv = 0;
        }

        if ($("#formEgresosContables #descuento_egresos").val() != "") {
            descuento = parseFloat($("#formEgresosContables #descuento_egresos").val());
        } else {
            descuento = 0;
        }

        if ($("#formEgresosContables #nc_egresos").val() != "") {
            nc = parseFloat($("#formEgresosContables #nc_egresos").val());
        } else {
            nc = 0;
        }

        var total = subtotal + isv - descuento - nc;

        $("#formEgresosContables #total_egresos").val(parseFloat(total).toFixed(2));
    });

    $("#formEgresosContables #isv_egresos").on("keyup", function() {
        var subtotal;
        var isv;
        var descuento;
        var nc;

        if ($("#formEgresosContables #subtotal_egresos").val() != "") {
            subtotal = parseFloat($("#formEgresosContables #subtotal_egresos").val());
        } else {
            subtotal = 0;
        }

        if ($("#formEgresosContables #isv_egresos").val() != "") {
            isv = parseFloat($("#formEgresosContables #isv_egresos").val());
        } else {
            isv = 0;
        }

        if ($("#formEgresosContables #descuento_egresos").val() != "") {
            descuento = parseFloat($("#formEgresosContables #descuento_egresos").val());
        } else {
            descuento = 0;
        }

        if ($("#formEgresosContables #nc_egresos").val() != "") {
            nc = parseFloat($("#formEgresosContables #nc_egresos").val());
        } else {
            nc = 0;
        }

        var total = subtotal + isv - descuento - nc;

        $("#formEgresosContables #total_egresos").val(parseFloat(total).toFixed(2));
    });

    $("#formEgresosContables #descuento_egresos").on("keyup", function() {
        var subtotal;
        var isv;
        var descuento;
        var nc;

        if ($("#formEgresosContables #subtotal_egresos").val() != "") {
            subtotal = parseFloat($("#formEgresosContables #subtotal_egresos").val());
        } else {
            subtotal = 0;
        }

        if ($("#formEgresosContables #isv_egresos").val() != "") {
            isv = parseFloat($("#formEgresosContables #isv_egresos").val());
        } else {
            isv = 0;
        }

        if ($("#formEgresosContables #descuento_egresos").val() != "") {
            descuento = parseFloat($("#formEgresosContables #descuento_egresos").val());
        } else {
            descuento = 0;
        }

        if ($("#formEgresosContables #nc_egresos").val() != "") {
            nc = parseFloat($("#formEgresosContables #nc_egresos").val());
        } else {
            nc = 0;
        }

        var total = subtotal + isv - descuento - nc;

        $("#formEgresosContables #total_egresos").val(parseFloat(total).toFixed(2));

    });

    $("#formEgresosContables #nc_egresos").on("keyup", function() {
        var subtotal;
        var isv;
        var descuento;
        var nc;

        if ($("#formEgresosContables #subtotal_egresos").val() != "") {
            subtotal = parseFloat($("#formEgresosContables #subtotal_egresos").val());
        } else {
            subtotal = 0;
        }

        if ($("#formEgresosContables #isv_egresos").val() != "") {
            isv = parseFloat($("#formEgresosContables #isv_egresos").val());
        } else {
            isv = 0;
        }

        if ($("#formEgresosContables #descuento_egresos").val() != "") {
            descuento = parseFloat($("#formEgresosContables #descuento_egresos").val());
        } else {
            descuento = 0;
        }

        if ($("#formEgresosContables #nc_egresos").val() != "") {
            nc = parseFloat($("#formEgresosContables #nc_egresos").val());
        } else {
            nc = 0;
        }

        var total = subtotal + isv - descuento - nc;

        $("#formEgresosContables #total_egresos").val(parseFloat(total).toFixed(2));
    });
});
//FIN CALCULAR VALORES INGRESADOS EN EGRESOS

$(document).ready(function() {
    $("#modalCategoriasEgresos").on('shown.bs.modal', function() {
        $(this).find('#formCategoriaEgresos #categoria').focus();
    });
});

$(document).ready(function() {
    $("#modalReporteCategorias").on('shown.bs.modal', function() {
        $(this).find('#formularioReporteCategorias #buscar').focus();
    });
});

$(document).ready(function() {
    $("#modalUpdateCategoriasEgresos").on('shown.bs.modal', function() {
        $(this).find('#formUpdateCategoriaEgresos #categoria').focus();
    });
});

var listar_categoria_egresos = function() {
    var table_categoria_egresos = $("#DatatableCategoriaEgresos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableCategoriaEgresos.php"
        },
        "columns": [{
                "data": "nombre"
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
                "width": "70%",
                "targets": 0
            },
            {
                "width": "15%",
                "targets": 1
            },
            {
                "width": "15%",
                "targets": 2
            }
        ],
        "autoWidth": false,
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Categoría Egresos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_categoria_egresos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Categoría Egresos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0]
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Categoría Egresos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0]
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
    table_categoria_egresos.search('').draw();
    $('#buscar').focus();


    edit_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
    delete_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
}

var edit_categoria_gastos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarProductos.php';
        modal_editar_categorias_contabilidad(data.categoria_gastos_id, data.nombre)
    });
}

var delete_categoria_gastos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();

        swal({
            title: "¿Estas seguro?",
            text: "¿Desea eliminar la categoria: " + data.nombre + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Sí, eliminar la categoria!",
                }
            },
            dangerMode: true,
            closeOnEsc: false, // Desactiva el cierre con la tecla Esc
            closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
        }).then((willConfirm) => {
            if (willConfirm === true) {
                deleteCategoriaGastos(data.categoria_gastos_id, data.nombre);
            }
        });
    });
}

function deleteCategoriaGastos(categoria_gastos_id, categoria) {
    var url = '<?php echo SERVERURL;?>core/deleteCategoriaGastos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: {
            categoria_gastos_id: categoria_gastos_id,
            categoria: categoria
        },
        success: function(response) {
            if (response === "success") {
                swal({
                    title: "Success",
                    text: "La categoria se elimino correctamente.",
                    icon: "success",
                    timer: 3000,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                    
                });
                listar_categoria_egresos();
            } else if (response.startsWith("error-existe: ")) {
                var errorMessage = response.substring(13);
                swal({
                    title: "Error",
                    text: "Error: " + errorMessage,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            } else {
                var errorMessage = response.substring(7);
                swal({
                    title: "Error",
                    text: "Error: " + errorMessage,
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
                });
            }
        },
        error: function() {
            swal({
                title: "Error",
                text: "Ha ocurrido un error en la solicitud.",
                icon: "error",
                dangerMode: true,
                closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
            });
        }
    });
}

var listar_reporte_categoria_egresos = function() {
    var fechai = $("#formularioReporteCategorias #fechai").val();
    var fechaf = $("#formularioReporteCategorias #fechaf").val();

    var table_reoprte_categoria_egresos = $("#DatatableReporteCategorias").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableReporteCategoriaEgresos.php",
            data: {
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "categoria"
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
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                "width": "70%",
                "targets": 0
            },
            {
                "width": "30%",
                "targets": 1
            }
        ],
        "autoWidth": false,
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Categoría Egresos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_categoria_egresos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Categoría Egresos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1]
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Categoría Egresos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1]
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
    table_reoprte_categoria_egresos.search('').draw();
    $('#buscar').focus();

    total_reporte_categoria_gastos_footer();
}

$('#formularioReporteCategorias #search').on('click', function(e) {
    e.preventDefault();
    listar_reporte_categoria_egresos();
})

var total_reporte_categoria_gastos_footer = function() {
    var fechai = $("#formularioReporteCategorias #fechai").val();
    var fechaf = $("#formularioReporteCategorias #fechaf").val();

    $.ajax({
            url: '<?php echo SERVERURL;?>core/totalReporteCategoriaGastosFooter.php',
            type: "POST",
            data: {
                "fechai": fechai,
                "fechaf": fechaf
            }
        })
        .done(function(data) {
            data = JSON.parse(data);

            // Formatear el monto con comas para miles y punto para decimales
            var montoFormateado = data.monto.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            $("#monto-i").html("L. " + montoFormateado);

        })
        .fail(function(data) {
            console.log("total ingreso error");
        });
}
</script>