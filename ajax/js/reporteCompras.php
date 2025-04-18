<script>
//reporteCompras.php    
$(document).ready(function() {
    getReporteCompras();
    listar_reporte_compras();
    $('#form_main_compras #tipo_compras_reporte').val(1);
    $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
});

$('#form_main_compras #tipo_compras_reporte').on("change", function(e) {
    listar_reporte_compras();
});

$('#form_main_compras #fechai').on("change", function(e) {
    listar_reporte_compras();
});

$('#form_main_compras #fechaf').on("change", function(e) {
    listar_reporte_compras();
});

//INICIO REPORTE DE COMPRAS
var listar_reporte_compras = function() {
    var tipo_compra_reporte = 1;
    if ($("#form_main_compras #tipo_compras_reporte").val() == null || $("#form_main_compras #tipo_compras_reporte")
        .val() == "") {
        tipo_compra_reporte = 1;
    } else {
        tipo_compra_reporte = $("#form_main_compras #tipo_compras_reporte").val();
    }

    var fechai = $("#form_main_compras #fechai").val();
    var fechaf = $("#form_main_compras #fechaf").val();

    var table_reporteCompras = $("#dataTablaReporteCompras").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableReporteCompras.php",
            "data": {
                "tipo_compra_reporte": tipo_compra_reporte,
                "fechai": fechai,
                "fechaf": fechaf
            }
        },
        "columns": [{
                "data": "fecha"
            },
            {
                "data": "tipo_documento",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var icon = data === 'Crédito' 
                            ? '<i class="fas fa-clock mr-1"></i>' 
                            : '<i class="fas fa-check-circle mr-1"></i>';
                        var badgeClass = data === 'Crédito' 
                            ? 'badge badge-pill badge-warning' 
                            : 'badge badge-pill badge-success';
                        return '<span class="' + badgeClass + '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' + 
                            icon + data + '</span>';
                    }
                    return data;
                }
            },
            {
                "data": "cuenta"
            },
            {
                "data": "proveedor"
            },
            {
                "data": "numero"
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
                "data": "isv",
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
                "defaultContent": "<button class='table_reportes print_compras btn btn-dark table_info ocultar'><span class='fas fa-file-download fa-lg'></span>Factura</button>"
            },
            {
                "defaultContent": "<button class='table_cancelar cancelar_compras btn btn-dark table_primary ocultar'><span class='fas fa-ban fa-lg'></span>Anular</button>"
            }
        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, //esta se encuenta en el archivo main.js
        "dom": dom,
        "columnDefs": [{
                width: "9.09%",
                targets: 0
            },
            {
                width: "9.09%",
                targets: 1
            },
            {
                width: "9.09%",
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
                width: "9.09%",
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
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();

            function formatNumber(number) {
                return 'L ' + number.toLocaleString('es-HN', {
                    minimumFractionDigits: 2
                });
            }

            // Sumar los valores de la columna de Subtotal
            var subtotal = api.column(5, {
                page: 'current'
            }).data().reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

            // Sumar los valores de la columna de ISV
            var isv = api.column(6, {
                page: 'current'
            }).data().reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

            // Sumar los valores de la columna de Descuento
            var descuento = api.column(7, {
                page: 'current'
            }).data().reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

            // Sumar los valores de la columna de Total
            var total = api.column(8, {
                page: 'current'
            }).data().reduce(function(a, b) {
                return a + parseFloat(b);
            }, 0);

            // Mostrar los totales con formato en las celdas correspondientes del pie de la tabla
            $('#subtotal-i').html(formatNumber(subtotal));
            $('#impuesto-i').html(formatNumber(isv));
            $('#descuento-i').html(formatNumber(descuento));
            $('#total-footer-ingreso').html(formatNumber(total));
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Compras',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_reporte_compras();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Compras',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
            },
            {
                extend: 'pdf',
                footer: true,
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LETTER',
                title: 'Reporte de Compras',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
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
    table_reporteCompras.search('').draw();
    $('#buscar').focus();

    view_reporteCompras_dataTable("#dataTablaReporteCompras tbody", table_reporteCompras);
    view_anularCompras_dataTable("#dataTablaReporteCompras tbody", table_reporteCompras);
}

var view_anularCompras_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.cancelar_compras");
    $(tbody).on("click", "button.cancelar_compras", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        anularCompra(data.compras_id)
    });
}

var view_reporteCompras_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_compras");
    $(tbody).on("click", "button.print_compras", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printPurchase(data.compras_id);
    });
}

function anularCompra(compras_id) {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea anular la factura de compra: # " + getNumeroCompra(compras_id) + "?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Si, anular la factura de compra!",
            }
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
    }).then((willConfirm) => {
        if (willConfirm === true) {
            anular(compras_id);
        }
    });
}

function anular(compras_id) {
    var url = '<?php echo SERVERURL; ?>core/anularCompra.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        data: 'compras_id=' + compras_id,
        success: function(data) {
            if (data == 1) {
                swal({
                    title: "Success",
                    text: "La factura de compra ha sido anulada con éxito",
                    icon: "success",
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera                    
                });
                listar_reporte_compras();
            } else {
                swal({
                    title: "Error",
                    text: "La factura de compra no se pudo anular",
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
            }
        }
    });
}

function getReporteCompras() {
    var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_compras #tipo_compras_reporte').html("");
            $('#form_main_compras #tipo_compras_reporte').html(data);
            $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
        }
    });
}
//FIN REPORTE DE COMPRAS
</script>