<script>
$(() => {
    inventario_transferencia();
    getTipoProductos();
    getAlmacen();

    // Evento para el botón de Buscar (submit)
    $('#form_main_movimientos_transferencia').on('submit', function(e) {
        e.preventDefault();

        inventario_transferencia(); 
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_movimientos_transferencia').on('reset', function() {
        // Limpia y refresca los selects
        $(this).find('.selectpicker')  // Usa `this` para referenciar el formulario actual
            .val('')
            .selectpicker('refresh');

            inventario_transferencia();
    });
});

//INVENTARIO TRANSFERENCIA
var inventario_transferencia = function() {
    var form = $("#form_main_movimientos_transferencia");
    var tipo_producto_id = form.find("#inventario_tipo_productos_id").val() || '';
    var productos_id = form.find("#inventario_productos_id").val();
    var bodega = form.find("#almacen").val();

    var table_movimientos = $("#dataTablaMovimientos").DataTable({
        destroy: true,
        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableInvetarioTransferencia.php",
            data: {
                tipo_producto_id: tipo_producto_id,
                bodega: bodega,
                productos_id: productos_id
            }
        },
        columns: [ 
            { 
                defaultContent: "<button data-toggle='tooltip' data-placement='top' title='Actualizar la fecha de vencimiento' class='table_change_date table_crear'><span class='fa-solid fa-calendar-days fa-lg'></span></button>" 
            },                    
            { 
                data: "fecha_registro" 
            },
            { 
                data: "image", 
                render: function(data) {
                    var defaultImageUrl = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
                    var imageUrl = data ? '<?php echo SERVERURL;?>vistas/plantilla/img/products/' + data : defaultImageUrl;
                    return `<img class="table-image" src="${imageUrl}" alt="Image Preview" height="100px" width="100px"/>`;
                } 
            },
            {
                "data": "numero_lote",
                "render": function(data, type, row) {
                    var loteText = data ? data : 'No especificado'; 
                    var loteColor = data ? '#28a745' : '#dc3545'; 

                    return '<span class="numero-lote" style="border: 2px solid ' + loteColor + '; border-radius: 12px; padding: 5px 10px; color: ' + loteColor + '; display: inline-block; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + loteText + '</span>';
                }
            },         
            { data: "barCode" },
            { data: "producto" },
            { data: "medida" },
            {
                "data": "saldo_anterior",
                "render": function(data, type, row) {
                    var saldoAnteriorColor = data > 0 ? '#28a745' : '#ff6f61'; // Verde si es positivo, coral si es negativo
                    var saldoAnteriorText = formatNumber(data); // Formateamos el número

                    return '<span style="border: 2px solid ' + saldoAnteriorColor + '; border-radius: 12px; padding: 5px 10px; color: ' + saldoAnteriorColor + '; font-weight: bold;">' + saldoAnteriorText + '</span>';
                }
            },
            {
                "data": "entrada",
                "render": function(data, type, row) {
                    var entradaColor = data > 0 ? '#17a2b8' : '#f39c12'; // Azul claro si es positivo, amarillo si es negativo
                    var entradaText = formatNumber(data); // Formateamos el número

                    return '<span style="border: 2px solid ' + entradaColor + '; border-radius: 12px; padding: 5px 10px; color: ' + entradaColor + '; font-weight: bold;">' + entradaText + '</span>';
                }
            },
            {
                "data": "salida",
                "render": function(data, type, row) {
                    var salidaColor = data > 0 ? '#ffc107' : '#dc3545'; // Amarillo si es positivo, rojo si es negativo
                    var salidaText = formatNumber(data); // Formateamos el número

                    return '<span style="border: 2px solid ' + salidaColor + '; border-radius: 12px; padding: 5px 10px; color: ' + salidaColor + '; font-weight: bold;">' + salidaText + '</span>';
                }
            },
            {
                "data": "saldo",
                "render": function(data, type, row) {
                    var saldoColor = data >= 0 ? '#007bff' : '#ff6347'; // Azul si es positivo, rojo tomate si es negativo
                    var saldoText = formatNumber(data); // Formateamos el saldo

                    return '<span style="border: 2px solid ' + saldoColor + '; border-radius: 12px; padding: 5px 10px; color: ' + saldoColor + '; font-weight: bold;">' + saldoText + '</span>';
                }
            },  
            { 
                data: "bodega" 
            },
            { 
                defaultContent: "<button data-toggle='tooltip' data-placement='top' title='Permite mover o transferir un producto de una bodega a otra' class='table_eliminar table_transferencia'><span class='fa fa-exchange-alt fa-lg'></span>Transferir</button>" 
            }
        ],
        lengthMenu: lengthMenu10,
        stateSave: true,
        language: idioma_español,
        dom: dom,
        columnDefs: [
            { width: "13.5%", targets: 0, orderable: true },
            { width: "10.5%", targets: 1 },
            { width: "20.5%", targets: 2 },
            { width: "5.5%", targets: 3 },
            { width: "18.5%", targets: 4 },
            { width: "10.5%", targets: 5 },
            { width: "10.5%", targets: 6 },
            { width: "10.5%", targets: 7 },
            { width: "10.5%", targets: 8 },
            { width: "10.5%", targets: 9 }
        ],
        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Movimientos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() { inventario_transferencia(); }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Exportar a Excel',
                title: 'Reporte Movimientos',
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'Exportar a PDF',
                orientation: 'landscape',
                title: 'Reporte Movimientos',
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] },
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
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();

            // Sumar el saldo anterior (índice 8)
            var totalSaldoAnterior = api.column(6, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);
            }, 0);

            // Sumar las entradas (índice 9)
            var totalEntrada = api.column(7, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);
            }, 0);

            // Sumar las salidas (índice 10)
            var totalSalida = api.column(8, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);
            }, 0);

            // Calcular el total
            var total = (totalEntrada + totalSaldoAnterior) - totalSalida;

            // Mostrar los totales en el footer
            $('#anterior-footer-movimiento').html(formatNumber(totalSaldoAnterior));
            $('#entrada-footer-movimiento').html(formatNumber(totalEntrada));
            $('#salida-footer-movimiento').html(formatNumber(totalSalida));
            $('#total-footer-movimiento').html(formatNumber(total));
        }
    });

        // Inicializar tooltips después de cada redibujado de la tabla
	$('#dataTablaMovimientos').on('draw.dt', function() {
		$('[data-toggle="tooltip"]').tooltip();
	});

    transferencia_producto_dataTable("#dataTablaMovimientos tbody",table_movimientos);
    cambiarVencimientoProducto_dataTable("#dataTablaMovimientos tbody",table_movimientos);
};

//FIN TRANSFERENCIA

//TRANSFERIR PRODUCTO/BODEGA
var transferencia_producto_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_transferencia");
    $(tbody).on("click", "button.table_transferencia", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formTransferencia')[0].reset();
        if (data.superior > 0) {
            showNotify('error', 'Error', 'No se puede hacer transferencia de producto que depente de otro inventario');
            return false
        }

        $('#formTransferencia #productos_id').val(data.productos_id);
        $('#formTransferencia #nameProduct').html("<b style='color: #007bff; font-size: 16px; text-transform: uppercase;'>Producto:</b> " + data.producto)
        $('#formTransferencia #id_bodega_actual').val(data.id_bodega);
        $('#formTransferencia #lote_id_productos').val(data.lote_id);
        $('#formTransferencia #empresa_id_productos').val(data.empresa_id);

        $('#modal_transferencia_producto').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    })
};

$('#formTransferencia').on('submit', function(e) {   
    e.preventDefault();
    var form = $("#formTransferencia");
    var respuesta = form.children('.RespuestaAjax');

    swal({
        title: "¿Estas seguro?",
        text: "¿Desea transferir este producto?",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, transferir el producto!",
            }
        },
		buttons: true,
		dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm) {
            var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';
            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(),
                beforeSend: function() {
                    $('#modal_transferencia_producto').modal({
                        show: false,
                        keyboard: false,
                        backdrop: 'static'
                    });
                },
                success: function(data) {
                    respuesta.html(data);
                }
            });
        }
    });
});

//TRANSFERIR PRODUCTO/BODEGA

//CAMBIAR FECHA DE CADUCICAD PRODUCTOS
var cambiarVencimientoProducto_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_change_date");
    $(tbody).on("click", "button.table_change_date", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formTransferenciaCambiarFecha')[0].reset();
        $('#formTransferenciaCambiarFecha #productos_id').val(data.productos_id);
        $('#formTransferenciaCambiarFecha #nameProduct').html("<b style='color: #007bff; font-size: 16px; text-transform: uppercase;'>Producto:</b> " + data.producto);
        $('#formTransferenciaCambiarFecha #id_bodega_actual').val(data.id_bodega);
        $('#formTransferenciaCambiarFecha #cantidad_productos').val(data.saldo);
        $('#formTransferenciaCambiarFecha #empresa_id_productos').val(data.empresa_id);
        $('#formTransferenciaCambiarFecha #lote_id_productos').val(data.lote_id);

        $('#modalCambiarFechaProducto').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    })
};

$('#EditarFechaVencimiento').on('click', function(e) {
    e.preventDefault(); // Evita la acción por defecto (en este caso, el submit del botón)
    
    var form = $("#formTransferenciaCambiarFecha");
    var respuesta = form.children('.RespuestaAjax');
    
    // Verificar si el formulario es válido antes de hacer la petición AJAX
    if (form[0].checkValidity()) {
        // Verificar el valor de la fecha antes de enviarla
        var fechaCaducidad = $('#fecha_caducidad').val();
        console.log('Fecha Caducidad:', fechaCaducidad); // Verifica la fecha capturada

        var url = '<?php echo SERVERURL;?>ajax/modificarFechaVencimientoProductosAjax.php';
        $.ajax({
            type: 'POST',
            url: url,
            data: form.serialize(),
            beforeSend: function() {
                $('#modalCambiarFechaProducto').modal({
                    show: false,
                    keyboard: false,
                    backdrop: 'static'
                });
            },
            success: function(data) {
                console.log("Respuesta del servidor:", data); // Verifica lo que llega
                respuesta.html(data);
            }
        });
    } else {
        // Si no es válido, activar los mensajes de validación de HTML5
        form[0].reportValidity();
    }
});
//CAMBIAR FECHA DE CADUCICAD PRODUCTOS

//INIICO OBTENER EL TIPO DE PRODUCTO
function getTipoProductos() {
    var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').html("");
            $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').html(data);
            $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').selectpicker('refresh');

            $('#formMovimientos #movimientos_tipo_producto_id').html("");
            $('#formMovimientos #movimientos_tipo_producto_id').html(data);
            $('#formMovimientos #movimientos_tipo_producto_id').selectpicker('refresh');

            getProductosMovimientos($('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val());
        }
    });
}
//FIN OBTENER EL TIPO DE PRODUCTO

$(() => {
    $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').on('change', function() {
        var tipo_producto_id;

        if ($('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() == "" || $(
                '#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() == null) {
            tipo_producto_id = 1;
        } else {
            tipo_producto_id = $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val();
        }

        getProductosMovimientos(tipo_producto_id);
        return false;
    });
});

function getProductosMovimientos(tipo_producto_id) {
    var url = '<?php echo SERVERURL; ?>core/getProductosMovimientosTipoProducto.php';

    $.ajax({
        type: "POST",
        url: url,
        data: 'tipo_producto_id=' + tipo_producto_id,
        success: function(data) {
            $('#form_main_movimientos_transferencia #inventario_productos_id').html("");
            $('#form_main_movimientos_transferencia #inventario_productos_id').html(data);
            $('#form_main_movimientos_transferencia #inventario_productos_id').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $("#modal_transferencia_producto").on('shown.bs.modal', function() {
        $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });
});

function getAlmacen() {
    var url = '<?php echo SERVERURL;?>core/getAlmacen.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_movimientos_transferencia #almacen').html("");
            $('#form_main_movimientos_transferencia #almacen').html(data);
            $('#form_main_movimientos_transferencia #almacen').selectpicker('refresh');

            $('#formTransferencia #id_bodega').html("");
            $('#formTransferencia #id_bodega').html(data);
            $('#formTransferencia #id_bodega').selectpicker('refresh');
        }
    });
}
</script>