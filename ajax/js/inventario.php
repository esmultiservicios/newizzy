<script>
var registro = false;

$(() => {
    funciones();
    listar_movimientos();

    $('#movimientos').css('cursor', 'pointer');
    $('#registroMovimientos').css('cursor', 'pointer');

	$('#form_main_movimientos #search').on("click", function(e) {
        e.preventDefault();
        listar_movimientos();
    });

    // Evento para el botón de Limpiar Filtros
    $('#form_main_movimientos').on('reset', function() {
        // Limpia y refresca los selects
        $('#form_main_movimientos .selectpicker')
            .val('')
            .selectpicker('refresh');
        listar_movimientos();
    });    
});

function funciones() {
    getTipoProductos();
    getTipoProductosModal();
    getProductoOperacion();
    getClientes();
    getClientesModal();
    getProductosMovimientos(1);
    getAlmacen();
    getAlmacenModal();
}

$('#form_main_movimientos #categoria_id').on('change', function() {
    listar_movimientos();
});

$('#form_main_movimientos #fechai').on('change', function() {
    listar_movimientos();
});

$('#form_main_movimientos #fechaf').on('change', function() {
    listar_movimientos();
});

$('#form_main_movimientos #almacen').on('change', function() {
    listar_movimientos();
});

$('#producto_movimiento_filtro').on('change', function() {
    listar_movimientos();
});

$('#cliente_movimiento_filtro').on('change', function() {
    listar_movimientos();
});

$('#inventario_tipo_productos_id').on('change', function() {
    listar_movimientos();
});

//INICIO MOVIMIENTOS
var listar_movimientos = function() {
    var tipo_producto_id = $('#form_main_movimientos #inventario_tipo_productos_id').val();
    var fechai = $("#form_main_movimientos #fechai").val();
    var fechaf = $("#form_main_movimientos #fechaf").val();
    var bodega = $("#form_main_movimientos #almacen").val();
    var producto = $("#producto_movimiento_filtro").val();
    var cliente = $('#cliente_movimiento_filtro').val();

    var table_movimientos = $("#dataTablaMovimientos").DataTable({
        "destroy": true,
        "footer": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableMovimientos.php",
            "data": {
                "tipo_producto_id": tipo_producto_id,
                "fechai": fechai,
                "fechaf": fechaf,
                "bodega": bodega,
                "producto": producto,
                "cliente": cliente,
            }
        },
        "columns": [
            {
                "data": "fecha_registro",
                "render": function(data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        return new Date(data);
                    }
                    // For display or other types, return the formatted date string
                    return data;
                }
            },
            {
                "data": "image",                
                "render": function(data, type, row, meta) {
                    var defaultImageUrl =
                        '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
                    var imageUrl = data ? '<?php echo SERVERURL;?>vistas/plantilla/img/products/' +
                        data : defaultImageUrl;

                    var imageHtml = '<img class="table-image" src="' + imageUrl +
                        '" alt="Image Preview" height="100px" width="100px"/>';

                    var cell = $('td:eq(' + meta.col + ')', meta.settings.oInstance.api().row(meta
                        .row).node());

                    var img = new Image();

                    img.onload = function() {
                        // La imagen se cargó correctamente, actualizar la imagen en la celda
                        $('.table-image', cell).attr('src', imageUrl);
                    };

                    img.onerror = function() {
                        // La imagen no se pudo cargar, usar la imagen de vista previa
                        $('.table-image', cell).attr('src', defaultImageUrl);
                    };

                    // Establecer la fuente de la imagen
                    img.src = imageUrl;

                    return imageHtml;
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
            {
                "data": "barCode"
            },
            {
                "data": "cliente"
            },
            {
                "data": "producto"
            },
            {
                "data": "medida"
            },
            {
                "data": "documento"
            },
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
                "data": "comentario"
            },
            {
                "data": "bodega"
            },

        ],
        "lengthMenu": lengthMenu10,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español, //esta se encuenta en el archivo main.js
        "dom": dom,
		"columnDefs": [
			{ width: "13.5%", targets: 0, "orderable": true },
			{ width: "10.5%", targets: 1 },
			{ width: "20.5%", targets: 2 }, // Ajusta el ancho de la columna del número de lote
			{ width: "5.5%", targets: 3 },
			{ width: "18.5%", targets: 4 },
			{ width: "10.5%", targets: 5 },
			{ width: "10.5%", targets: 6 },
			{ width: "10.5%", targets: 7 },
			{ width: "10.5%", targets: 8 },
			{ width: "10.5%", targets: 9 },
			{ width: "10.5%", targets: 10 },
			{ width: "10.5%", targets: 11 }
		],
        "footerCallback": function(row, data, start, end, display) {
            var api = this.api();

            // Sumar el saldo anterior (índice 8)
            var totalSaldoAnterior = api.column(8, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);  // Asegúrate de que la columna del saldo anterior esté correctamente indexada
            }, 0);

            // Sumar las entradas (índice 9)
            var totalEntrada = api.column(9, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);
            }, 0);

            // Sumar las salidas (índice 10)
            var totalSalida = api.column(10, { page: 'current' }).data().reduce(function(a, b) {
                return a + parseFloat(b || 0);
            }, 0);

            var total = (totalSaldoAnterior + totalEntrada) - totalSalida;

            // Mostrar los totales en el footer
            $('#anterior-footer-movimiento').html(formatNumber(totalSaldoAnterior)); // Mostrar el saldo anterior
            $('#entrada-footer-movimiento').html(formatNumber(totalEntrada));
            $('#salida-footer-movimiento').html(formatNumber(totalSalida));
            $('#total-footer-movimiento').html(formatNumber(total));
        },
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Movimientos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_movimientos();

                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Movimientos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_movimientos();
                    //registro_inventario();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Movimientos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 10, 11, 12, 13]
                },
            },
            {
                extend: 'pdf',
                footer: true,
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                pageSize: 'LEGAL',  // Cambiar a 'LEGAL' para tamaño de papel legal
                title: 'Reporte Movimientos',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' +
                    convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
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

    // Inicializar tooltips después de cada redibujado de la tabla
	$('#dataTablaMovimientos').on('draw.dt', function() {
		$('[data-toggle="tooltip"]').tooltip();
	});

    table_movimientos.search('').draw();
    table_movimientos.order([0, 'desc'])
    $('#buscar').focus();

    //transferencia_producto_dataTable("#dataTablaMovimientos tbody",table_movimientos);
}

//TRANSFERIR PRODUCTO/BODEGA
var transferencia_producto_dataTable = function(tbody, table) {

    $(tbody).off("click", "button.table_transferencia");
    $(tbody).on("click", "button.table_transferencia", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formTransferencia #productos_id').val(data.productos_id);
        $('#formTransferencia #nameProduct').html(data.producto);

        $('#modal_transferencia_producto').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    })

};

$("#putEditarBodega").click(function() {
    var form = $("#formTransferencia");
    var respuesta = form.children('.RespuestaAjax');
    var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';
    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formTransferencia').serialize(),
        beforeSend: function() {
            $('#modal_transferencia_producto').modal({
                show: false,
                keyboard: false,
                backdrop: 'static'
            });

        },
        success: function(data) {
            $('#modal_transferencia_producto').modal('toggle');
            respuesta.html(data);
        }
    })
});
//TRANSFERIR PRODUCTO/BODEGA

function getAlmacen() {
    var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_movimientos #almacen').html("");
            $('#form_main_movimientos #almacen').html(data);
            $('#form_main_movimientos #almacen').selectpicker('refresh');

            $('#formMovimientoInventario #almacen_modal').html("");
            $('#formMovimientoInventario #almacen_modal').html(data);
            $('#formMovimientoInventario #almacen_modal').selectpicker('refresh');
        }
    });
}

function getAlmacenModal() {
    var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formMovimientos #almacen_modal').html("");
            $('#formMovimientos #almacen_modal').html(data);
            $('#formMovimientos #almacen_modal').selectpicker('refresh');
        }
    });
}

// Llamar a la función cuando cambie el producto seleccionado
$('#formMovimientos #movimiento_producto').change(function() {
    var producto_id = $(this).val();
    getLotesProductos(producto_id);
});

function getLotesProductos(producto_id) {
    var url = '<?php echo SERVERURL;?>core/getLotesProductos.php';

    $.ajax({
        type: "POST",
        url: url,
        data: { producto_id: producto_id }, // Enviar el producto seleccionado
        async: true,
        success: function(data) {
            $('#formMovimientos #movimiento_lote').html("");
            $('#formMovimientos #movimiento_lote').html(data);
            $('#formMovimientos #movimiento_lote').selectpicker('refresh');
        }
    });
}

//INIICO OBTENER EL TIPO DE PRODUCTO
function getTipoProductos() {
    var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_movimientos #inventario_tipo_productos_id').html("");
            $('#form_main_movimientos #inventario_tipo_productos_id').html(data);
            $('#form_main_movimientos #inventario_tipo_productos_id').selectpicker('refresh');
        }
    });
}

function getTipoProductosModal() {
    var url = '<?php echo SERVERURL;?>core/getTipoProductoMovimientosModal.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formMovimientos #movimientos_tipo_producto_id').html("");
            $('#formMovimientos #movimientos_tipo_producto_id').html(data);
            $('#formMovimientos #movimientos_tipo_producto_id').selectpicker('refresh');
        }
    });
}
//FIN OBTENER EL TIPO DE PRODUCTO

function getProductoOperacion() {
    var url = '<?php echo SERVERURL;?>core/getOperacion.php';

    $.ajax({
        type: "POST",
        url: url,
        success: function(data) {
            $('#formMovimientos #movimiento_operacion').html("");
            $('#formMovimientos #movimiento_operacion').html(data);
            $('#formMovimientos #movimiento_operacion').selectpicker('refresh');

            $('#formMovimientoInventario #movimiento_producto').html("");
            $('#formMovimientoInventario #movimiento_producto').html(data);
            $('#formMovimientoInventario #movimiento_producto').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $('#form_main_movimientos #inventario_tipo_productos_id').on('change', function() {
        var tipo_producto_id;

        if ($('#form_main_movimientos #inventario_tipo_productos_id').val() == "" || $(
                '#form_main_movimientos #inventario_tipo_productos_id').val() == null) {
            tipo_producto_id = 1;
        } else {
            tipo_producto_id = $('#form_main_movimientos #inventario_tipo_productos_id').val();
        }

        getProductosMovimientos(tipo_producto_id);
        return false;
    });

    $('#formMovimientos #movimientos_tipo_producto_id').on('change', function() {
        var tipo_producto_id;

        if ($('#formMovimientos #movimientos_tipo_producto_id').val() == "" || $(
                '#formMovimientos #movimientos_tipo_producto_id').val() == null) {
            tipo_producto_id = 1;
        } else {
            tipo_producto_id = $('#formMovimientos #movimientos_tipo_producto_id').val();
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
            $('#form_main_movimientos #producto_movimiento_filtro').html("");
            $('#form_main_movimientos #producto_movimiento_filtro').html(data);
            $('#form_main_movimientos #producto_movimiento_filtro').selectpicker('refresh');

            $('#formMovimientos #movimiento_producto').html("");
            $('#formMovimientos #movimiento_producto').html(data);
            $('#formMovimientos #movimiento_producto').selectpicker('refresh');
        }
    });
}

function getClientes() {
    var url = '<?php echo SERVERURL;?>core/getClientesHostProductos.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_movimientos #cliente_movimiento_filtro').html("");
            $('#form_main_movimientos #cliente_movimiento_filtro').html(data);
            $('#form_main_movimientos #cliente_movimiento_filtro').selectpicker('refresh');

            $('#formMovimientoInventario #cliente_movimientos').html("");
            $('#formMovimientoInventario #cliente_movimientos').html(data);
            $('#formMovimientoInventario #cliente_movimientos').selectpicker('refresh');
        }
    });
}

function getClientesModal() {
    var url = '<?php echo SERVERURL;?>core/getClientesHostProductosModal.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formMovimientos #cliente_movimientos').html("");
            $('#formMovimientos #cliente_movimientos').html(data);
            $('#formMovimientos #cliente_movimientos').selectpicker('refresh');
        }
    });
}

//INICIO FORMULARIO MOVIMIENTOS
function modal_movimientos() {
    $('#formMovimientos').attr({
        'data-form': 'save'
    });
    $('#formMovimientos').attr({
        'action': '<?php echo SERVERURL; ?>ajax/agregarMovimientoProductosAjax.php'
    });
    $('#formMovimientos')[0].reset();
    $('#formMovimientos #proceso_movimientos').val("Registro");
    $('#modal_movimientos').show();
    funciones();
    $('#modal_movimientos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN FORMULARIO MOVIMIENTOS

$(document).ready(function() {
    $("#modal_buscar_productos_movimientos").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_productos_movimientos #buscar').focus();
    });
});

$(document).ready(function() {
    $("#modal_movimientos").on('shown.bs.modal', function() {
        $(this).find('#formularioMovimientos #movimiento_categoria').focus();
    });
});

$(document).ready(function() {
    $("#modal_transferencia_producto").on('shown.bs.modal', function() {
        $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });
});


$('#movimientos').on('click', function() {
    if (registro === true) {
        registro = false;
        $('#movimientos').removeClass('active');
        $('#main_inventario').show();
        $('#movimiento_inventario').hide();
        $('#registroMovimientos').addClass('active');
    }
});

$('#registroMovimientos').on('click', function() {
    if (registro === true) {
        $('#registroMovimientos').removeClass('active');
        $('#main_inventario').hide();
        $('#movimiento_inventario').show();
        $('#movimientos').addClass('active');
    }
});

function registro_inventario() {
    registro = true;
    $('#movimiento_inventario').show();
    $('#main_inventario').hide();
    $('#registroMovimientos').removeClass('active');
    $('#movimientos').addClass('active');
}

const BusquedaProducto = (barcode) => {
    var url = '<?php echo SERVERURL;?>core/buscar_producto.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: { 
            barcode: 
            barcode 
        }, // Enviamos barcode correctamente
        dataType: 'json', // Asegura que la respuesta se interprete como JSON
        success: function(registro) {
            if (registro.success) {
                $('#formMovimientos #movimientos_tipo_producto_id').val(registro.tipo_producto_id).selectpicker('refresh');
                $('#formMovimientos #movimiento_producto').val(registro.productos_id).selectpicker('refresh'); 
                $('#formMovimientos #almacen_modal').val('').selectpicker('refresh')
                $('#formMovimientos #movimiento_cantidad').focus(); 
            } else {
                showNotify('error', 'Error', registro.message);
            }
        },
        error: function() {
            showNotify('error', 'Error', 'Hubo un problema en la comunicación con el servidor');
        }
    });
};

$('#formMovimientos #produto_barcode').on('keypress', (event) => {
    if (event.which === 13) { 
        event.preventDefault(); 

        let barcode = $(event.target).val().trim();

        if (barcode.length === 0) {
            showNotify('error', 'Error', 'Lo sentimos, debe ingresar un nombre de producto, o escanear un código de barras');

            $('#formMovimientos #produto_barcode').focus();
            return;
        }

        // Validar si se seleccionó algún radio button
        if ($('input[name="movimiento_operacion"]:checked').length === 0) {
            showNotify('error', 'Error', 'Debe seleccionar un tipo de operación (Entrada o Salida)');

            $('input[name="movimiento_operacion"]').first().focus();
            return;
        }

        BusquedaProducto(barcode);
    }
});

$("#modal_movimientos").on('shown.bs.modal', function() {
    $(this).find('#formMovimientos #produto_barcode').focus();
});

$(function() {
    // Función para habilitar los campos según tipo de operación seleccionado
    $("input[name='movimiento_operacion'], label[for='entrada'], label[for='salida']").click(function() {
        var tipoOperacion = $("input[name='movimiento_operacion']:checked").val();
        var barcode = $('#formMovimientos #produto_barcode').val().trim(); // Obtener el código de barras

        if (tipoOperacion) {
            // Habilitar el campo Cliente solo si la operación es 'salida'
            $('#cliente_movimientos').prop('disabled', tipoOperacion !== 'salida');

            // Enfocar en el campo Producto
            $('#produto_barcode').focus();

            // Actualizar el campo proceso_movimientos
            $('#proceso_movimientos').val(tipoOperacion === 'entrada' ? 'Operación: Entrada' : 'Operación: Salida');

            // Si hay un código de barras, llamar a la función
            if (barcode.length > 0) {
                BusquedaProducto(barcode);
            }
        } else {
            // Si no hay selección, establecer el valor predeterminado
            $('#proceso_movimientos').val('Selecciona una operación');
        }
    });
});
</script>