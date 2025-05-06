<script>
$(document).ready(function() {
    cleanQuote();
    getVigencia();
});
//INICIO COTIZACIONES

function resetRow() {
    row = 0;
}

$(document).ready(function() {
    $("#quoteForm #QuoteItem").on('keypress', '.product-bar-code', function(event) {
        //EVALUAMOS EL ENTER event.which == '13'
        if (event.which === 10 || event.which === 13) {
            event.preventDefault();
            $(".product-bar-code").focus();
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();
            var icon_search = 0;

            if ($("#quoteForm #QuoteItem #bar-code-id_" + row_index).val() != "") {
                var url = '<?php echo SERVERURL;?>core/getProdcutoBarCode.php';
                var element = $("#quoteForm #QuoteItem #bar-code-id_" + row_index).val().split('*');
                var cantidad = element[0];
                var barcode = element[1];

                if (!element[1]) {
                    barcode = cantidad;
                    cantidad = 1;
                }

                if (!cantidad) {
                    cantidad = 1;
                }

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: 'barcode=' + barcode,

                    async: false,

                    success: function(registro) {

                        var valores = eval(registro);

                        if (valores[0]) {

                            $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val(
                                valores[0]);

                            $("#quoteForm #QuoteItem #priceQuote_" + row_index).val(valores[
                                1]);

                            $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val(
                                valores[2]);

                            $("#quoteForm #QuoteItem #isvQuote_" + row_index).val(valores[
                                3]);

                            $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val(
                                cantidad);

                            $("#quoteForm #QuoteItem #bar-code-id_" + row_index).val(
                                barcode);
                            $("#quoteForm #QuoteItem #cantidad_mayoreoQuote_" + row_index)
                                .val(valores[4]);
                            $("#quoteForm #QuoteItem #precio_realQuote_" + row_index).val(
                                valores[1]);
                            $("#quoteForm #QuoteItem #precio_mayoreoQuote_" + row_index)
                                .val(valores[5]);


                            var impuesto_venta = parseFloat($(
                                    '#quoteForm #QuoteItem #isvQuote_' + row_index)
                                .val());

                            var cantidad1 = parseFloat($(
                                    '#quoteForm #QuoteItem #quantityQuote_' + row_index)
                                .val());

                            var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' +
                                row_index).val());

                            var total = parseFloat($('#quoteForm #QuoteItem #totalQuote_' +
                                row_index).val());



                            var isv = 0;

                            var isv_total = 0;

                            var porcentaje_isv = 0;

                            var porcentaje_calculo = 0;

                            var isv_neto = 0;



                            if (impuesto_venta == 1) {

                                porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") /
                                    100);



                                if (total == "" || total == 0) {

                                    porcentaje_calculo = (parseFloat(precio) * parseFloat(
                                        cantidad1) * porcentaje_isv).toFixed(2);

                                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index)
                                        .val(porcentaje_calculo);

                                } else {

                                    isv_total = parseFloat($('#quoteForm #taxAmountQuote')
                                        .val());

                                    porcentaje_calculo = (parseFloat(precio) * parseFloat(
                                        cantidad1) * porcentaje_isv).toFixed(2);

                                    isv_neto = parseFloat(isv_total) + parseFloat(
                                        porcentaje_calculo);

                                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index)
                                        .val(porcentaje_calculo);

                                }

                            }



                            addRowQuote();

                            if (row_index > 0) {
                                var icon_search = row_index - 1;
                            }

                            $("#quoteForm #QuoteItem #icon-search-bar_" + row_index).hide();
                            $("#quoteForm #QuoteItem #icon-search-bar_" + icon_search)
                                .hide();

                            calculateTotalQuote();

                        } else {
                            showNotify('error', 'Error', 'Producto no encontrado, por favor corregir');
                            $("#quoteForm #QuoteItem #bar-code-id_" + row_index).val("");
                        }
                    }
                });
            }
        }
    });
});



$(document).ready(function() {

    $("#quoteForm #QuoteItem").on('keypress', '.product-bar-code', function(event) {

        var row_index = $(this).closest("tr").index();

        var col_index = $(this).closest("td").index();



        //TECLA MAS

        if (event.which === 43) {

            if ($("#quoteForm #QuoteItem #bar-code-id_" + row_index).val() != "" && $(
                    "#quoteForm #QuoteItem #productNameQuote_" + row_index).val() != "") {

                event.preventDefault();

                var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();

                if (!cantidad) {

                    cantidad = 1;

                }

                cantidad++;

                if (cantidad > 0) {

                    $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val(cantidad);

                    //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS					

                    if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index)
                            .val()) != 0 && parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' +
                            row_index).val()) != 0) {

                        //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO

                        if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >=
                            parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index)
                                .val())) {

                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                                    '#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index)
                                .val());

                        } else {

                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                                '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

                        }

                    } else {

                        $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                            '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

                    }

                }

            }

            var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());

            var cantidad1 = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());

            var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val());

            var total = parseFloat($('#quoteForm #QuoteItem #totalQuote_' + row_index).val());



            var isv = 0;

            var isv_total = 0;

            var porcentaje_isv = 0;

            var porcentaje_calculo = 0;

            var isv_neto = 0;

            if (impuesto_venta == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);

                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

                }

            }

            calculateTotalQuote();

        }

        //TECLA MENOS

        if (event.which === 45) {

            if ($("#quoteForm #QuoteItem #bar-code-id_" + row_index).val() != "" && $(
                    "#quoteForm #QuoteItem #productNameQuote_" + row_index).val() != "") {

                event.preventDefault();

                var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();


                if (!cantidad) {

                    cantidad = 1;

                }

                cantidad--;


                if (cantidad > 0) {

                    $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val(cantidad);

                    //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS

                    if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index)
                            .val()) != 0 && parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' +
                            row_index).val()) != 0) {

                        //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO

                        if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >=
                            parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index)
                                .val())) {

                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                                    '#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index)
                                .val());

                        } else {

                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                                '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

                        }

                    } else {

                        $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                            '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

                    }

                }

            }

            var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());

            var cantidad1 = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());

            var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val());

            var total = parseFloat($('#quoteForm #QuoteItem #totalQuote_' + row_index).val());



            var isv = 0;

            var isv_total = 0;

            var porcentaje_isv = 0;

            var porcentaje_calculo = 0;

            var isv_neto = 0;



            if (impuesto_venta == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);



                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

                }

            }

            calculateTotalQuote();
        }

    });

});

//FIN COTIZACIONES

//FIN BARCODE

$("#quoteForm #help_factura").on("click", function(e) {
    modalAyudaCotizacion();
    e.preventDefault();
});

function modalAyudaCotizacion() {
    $('#modalAyudaQuote').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cleanQuote() {
    $('#quoteForm #vendedor-customers-quote').html("");
    $('#quoteForm #comentario-customers-quote').html("");
    getConsumidorFinal();
    getCajero();
    $('#quoteForm #notesQuote').html("");
}

//INICIO COTIZACION

//INICIO CAMBIAR PRECIO A PRODUCTO EN FACTURACION
$(document).ready(function() {
    $('#quoteForm #QuoteItem').on("keydown", '.product-bar-code', function(e) {
        if (e.which === 112) { //TECLA F1
            //modalLogin();
            modalAyudaCotizacion();
            e.preventDefault();
        }

        //INICIO BUSQUEDA PRODUCTO EN FACTURACION
        if (e.which === 113) { //TECLA F2
            listar_productos_cotizacion_buscar();
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();

            $('#formulario_busqueda_productos_cotizacion #row').val(row_index);
            $('#formulario_busqueda_productos_cotizacion #col').val(col_index);

            $('#modal_buscar_productos_cotizacion').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
            e.preventDefault();
        }

        //FIN BUSQUEDA PRODUCTO EN FACTURACION

        if (e.which === 114) { //TECLA F3
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();

            $('#formDescuentoCotizaciones #row_index').val(row_index);
            $('#formDescuentoCotizaciones #col_index').val(col_index);

            if ($("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val() != "") {
                $('#formDescuentoCotizaciones')[0].reset();
                var productos_id = $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val();
                var producto = $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val();
                var precio = $("#quoteForm #QuoteItem #precio_realQuote_" + row_index).val();
                var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();

                $('#formDescuentoCotizaciones #descuento_productos_id').val(productos_id);
                $('#formDescuentoCotizaciones #producto_descuento_fact').val(producto);
                $('#formDescuentoCotizaciones #precio_descuento_fact').val(precio);
                $('#formDescuentoCotizaciones #descuento_cantidad').val(cantidad);

                $('#formDescuentoCotizaciones #pro_descuento_fact').val("Registrar");

                $('#modalDescuentoCotizaciones').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }

            e.preventDefault();
        }


        if (e.which === 115) { //TECLA F4
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();
            $('#formModificarPrecioCotizaciones #row_index').val(row_index);
            $('#formModificarPrecioCotizaciones #col_index').val(col_index);

            if ($("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val() != "") {
                $('#formModificarPrecioCotizaciones')[0].reset();
                var clientes_id = $("#quoteForm #cliente_id").val();
                var fecha = $("#quoteForm #fecha").val();
                var productos_id = $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val();
                var producto = $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val();
                var precio = $("#quoteForm #QuoteItem #precio_realQuote_" + row_index).val();

                $('#formModificarPrecioCotizaciones #modificar_precio_fecha').val(fecha);
                $('#formModificarPrecioCotizaciones #modificar_precio_clientes_id').val(clientes_id);
                $('#formModificarPrecioCotizaciones #modificar_precio_productos_id').val(productos_id);
                $('#formModificarPrecioCotizaciones #producto_modificar_precio_fact').val(producto);

                $('#formModificarPrecioCotizaciones #pro_modificar_precio').val("Registrar");


                $('#modalModificarPrecioCotizaciones').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }

            e.preventDefault();
        }
    });
});


$("#reg_modificar_precio_quote").on("click", function(e) {
    e.preventDefault();
    var row_index = $('#formModificarPrecioCotizaciones #row_index').val();
    var col_index = $('#formModificarPrecioCotizaciones #col_index').val();

    var referencia = $('#formModificarPrecioCotizaciones #referencia_modificar_precio_fact').val();
    var precio = parseFloat($('#formModificarPrecioCotizaciones #precio_modificar_precio_fact').val()).toFixed(
        2);
    var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();
    var impuesto_venta = $("#quoteForm #QuoteItem #isvQuote_" + row_index).val();
    var descuento = parseFloat($('#formDescuentoCotizaciones #descuento_fact').val()).toFixed(2);

    var isv = 0;
    var isv_total = 0;
    var porcentaje_isv = 0;
    var porcentaje_calculo = 0;
    var isv_neto = 0;
    var total_ = (precio * cantidad) - descuento;

    if (impuesto_venta == 1) {
        porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);
        if ($('#quoteForm #taxAmountQuote').val() == "" || $('#quoteForm #taxAmountQuote').val() == 0) {
            porcentaje_calculo = (parseFloat(total_) * porcentaje_isv).toFixed(2);
            isv_neto = porcentaje_calculo;
            $('#quoteForm #taxAmountQuote').val(porcentaje_calculo);
            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
        } else {
            isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
            porcentaje_calculo = (parseFloat(total_) * porcentaje_isv).toFixed(2);
            isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
            $('#quoteForm #taxAmountQuote').val(isv_neto);
            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
        }
    }

    $("#quoteForm #QuoteItem #priceQuote_" + row_index).val(precio);
    $("#quoteForm #QuoteItem #referenciaProductoQuote_" + row_index).val(referencia);
    $('#modalModificarPrecioCotizaciones').modal('hide');

    calculateTotalQuote();
});



//INICIO DESCUENTO PRODUCTO EN COTIZACIONES

$(document).ready(function() {

    $("#formDescuentoCotizaciones #porcentaje_descuento_fact").on("keyup", function() {

        var precio;
        var porcentaje;
        var descuento;
        var total_descuento;
        var cantidad;



        if ($("#formDescuentoCotizaciones #porcentaje_descuento_fact").val()) {

            precio = parseFloat($('#formDescuentoCotizaciones #precio_descuento_fact').val());
            porcentaje = parseFloat($('#formDescuentoCotizaciones #porcentaje_descuento_fact').val());
            cantidad = parseFloat($('#formDescuentoCotizaciones #descuento_cantidad').val());
            descuento = precio * (porcentaje / 100);
            total_descuento = descuento * cantidad;



            $('#formDescuentoCotizaciones #descuento_fact').val(parseFloat(total_descuento).toFixed(2));

        } else {

            $('#formDescuentoCotizaciones #descuento_fact').val(0);

        }

    });



    $("#formDescuentoCotizaciones #descuento_fact").on("keyup", function() {

        var precio;

        var descuento_fact;



        if ($("#formDescuentoCotizaciones #descuento_fact").val() != "") {

            precio = parseFloat($('#formDescuentoCotizaciones #precio_descuento_fact').val());

            descuento_fact = parseFloat($('#formDescuentoCotizaciones #descuento_fact').val());



            $('#formDescuentoCotizaciones #porcentaje_descuento_fact').val(parseFloat((descuento_fact /
                precio) * 100).toFixed(2));

        } else {

            $('#formDescuentoCotizaciones #porcentaje_descuento_fact').val(0);

        }

    });

});



$("#reg_DescuentoQuote").on("click", function(e) {

    e.preventDefault();

    var row_index = $('#formDescuentoCotizaciones #row_index').val();

    var col_index = $('#formDescuentoCotizaciones #col_index').val();



    var descuento = parseFloat($('#formDescuentoCotizaciones #descuento_fact').val()).toFixed(2);

    var precio = $("#quoteForm #QuoteItem #priceQuote_" + row_index).val();

    var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();

    var impuesto_venta = $("#quoteForm #QuoteItem #isvQuote_" + row_index).val();



    $("#quoteForm #QuoteItem #discountQuote_" + row_index).val(descuento);



    var isv = 0;

    var isv_total = 0;

    var porcentaje_isv = 0;

    var porcentaje_calculo = 0;

    var isv_neto = 0;

    var total_ = (precio * cantidad) - descuento;



    if (impuesto_venta == 1) {

        porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);

        if ($('#quoteForm #taxAmountQuote').val() == "" || $('#quoteForm #taxAmountQuote').val() == 0) {

            porcentaje_calculo = (parseFloat(total_) * porcentaje_isv).toFixed(2);

            isv_neto = porcentaje_calculo;

            $('#quoteForm #taxAmountQuote').val(porcentaje_calculo);

            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

        } else {

            isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());

            porcentaje_calculo = (parseFloat(total_) * porcentaje_isv).toFixed(2);

            isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

            $('#quoteForm #taxAmountQuote').val(isv_neto);

            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

        }

    }

    $('#modalDescuentoCotizaciones').modal('hide');

    calculateTotalQuote();

});

//FIN DESCUENTO PRODUCTO EN FACTURACION

//FIN CAMBIAR PRECIO A PRODUCTO EN COTIZACIONES



//INICIO BUSQUEDA PRODUCTOS COTIZACION

$(document).ready(function() {

    $("#quoteForm #QuoteItem").on('click', '.buscar_productos_quote', function(e) {
        e.preventDefault();
        listar_productos_cotizacion_buscar();
        var row_index = $(this).closest("tr").index();
        var col_index = $(this).closest("td").index();
        $('#formulario_busqueda_productos_facturacion #row').val(row_index);
        $('#formulario_busqueda_productos_facturacion #col').val(col_index);
        console.log('row_index', row_index)

        $('#modal_buscar_productos_cotizacion').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
});

var listar_productos_cotizacion_buscar = function() {
    var bodega = $("#formulario_busqueda_productos_facturacion #almacen").val() === "" ? 1 : $(
        "#formulario_busqueda_productos_facturacion #almacen").val();

    var table_productos_cotizacion_buscar = $("#DatatableProductosBusquedaCotizacion").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableProductosCotizacion.php",
            "data": {
                "bodega": bodega
            }
        },
        "columns": [
            {
                "defaultContent": "<button class='table_view btn btn-secondary ocultar'><span class='fas fa-cart-plus fa-lg'></span></button>"
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
                "data": "barCode"
            },
            {
                "data": "nombre"
            },
            {
                "data": "cantidad",
                render: function(data, type) {
                    if (data == null) {
                        data = 0;
                    }

                    var number = $.fn.dataTable.render
                        .number(',', '.', 2, '')
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
                "data": "medida"
            },
            {
                "data": "tipo_producto_nombre"
            },
            {
                "data": "precio_venta",
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
                "data": null,
                "render": function(data, type, row) {
                    // Mostramos 'Sin bodega' si almacen es null o vacío
                    if (row.almacen === null || row.almacen === "" || row.almacen === undefined) {
                        return "Sin bodega";
                    } else {
                        return row.almacen;
                    }
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "responsive": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "2%",
                targets: 0
            },
            {
                width: "17%",
                targets: 1
            },
            {
                width: "17%",
                targets: 2
            },
            {
                width: "10%",
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
                width: "12%",
                targets: 6
            },
            {
                width: "12%",
                targets: 7
            },
            {
                width: "12%",
                targets: 8
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {

                    listar_productos_cotizacion_buscar();
                }
            },

            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_productos();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });

    table_productos_cotizacion_buscar.search('').draw();
    $('#buscar').focus();


    view_productos_busqueda_cotizacion_dataTable("#DatatableProductosBusquedaCotizacion tbody",
        table_productos_cotizacion_buscar);

}

var row = 0;

var view_productos_busqueda_cotizacion_dataTable = function(tbody, table) { //resp1
    $(tbody).off("click", "button.table_view, td img");
    $(tbody).on("click", "button.table_view, td img", function(e) {
        e.preventDefault();

        if ($("#quoteForm #cliente_id").val() != "" && $("#quoteForm #cliente").val() != "" && $(
                "#quoteForm #colaborador_id").val() != "" && $("#quoteForm #colaborador").val() != "") {
            var data= table.row($(this).parents("tr")).data();

            $('#quoteForm #QuoteItem #productosQuote_id_' + row).val(data.productos_id);
            $('#quoteForm #QuoteItem #bar-code-id_' + row).val(data.barCode);
            $('#quoteForm #QuoteItem #productNameQuote_' + row).val(data.nombre);
            $('#quoteForm #QuoteItem #quantityQuote_' + row).val(1);
            $('#quoteForm #QuoteItem #quantityQuote_' + row).focus();
            $('#quoteForm #QuoteItem #priceQuote_' + row).val(data.precio_venta);
            $('#quoteForm #QuoteItem #discountQuote_' + row).val(0);
            $('#quoteForm #QuoteItem #isvQuote_' + row).val(data.impuesto_venta);
            $('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row).val(data.precio_mayoreo);
            $('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row).val(data.cantidad_mayoreo);
            $('#quoteForm #QuoteItem #precio_realQuote_' + row).val(data.precio_venta);

            actualizarTextoProductoQuote(row, data.nombre, data.medida);
            var isv = 0;
            var isv_total = 0;
            var porcentaje_isv = 0;
            var porcentaje_calculo = 0;
            var isv_neto = 0;

            if (data.impuesto_venta == 1) {
                porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);
                if ($('#quoteForm #taxAmountQuote').val() == "" || $('#quoteForm #taxAmountQuote').val() ==
                    0) {
                    porcentaje_calculo = (parseFloat(data.precio_venta) * porcentaje_isv).toFixed(2);
                    isv_neto = porcentaje_calculo;
                    $('#quoteForm #taxAmountQuote').val(porcentaje_calculo);
                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row).val(porcentaje_calculo);
                } else {
                    isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                    porcentaje_calculo = (parseFloat(data.precio_venta) * porcentaje_isv).toFixed(2);
                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                    $('#quoteForm #taxAmountQuote').val(isv_neto);
                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row).val(porcentaje_calculo);
                }
            }
            calculateTotalQuote();
            addRowQuote();

            if (row > 0) {
                var icon_search = row - 1;
            }
			

            $('#modal_buscar_productos_cotizacion').modal('hide');
            row++;
        } else {
            showNotify('error', 'Error', 'Lo sentimos no se puede seleccionar un producto, por favor antes de continuar, verifique que los siguientes campos: clientes, vendedor no se encuentren vacíos');
        }

        e.preventDefault();
    });
}

//FIN BUSQUEDA PRODUCTOS COTIZACION



//INICIO CLIENTES COTIZACION

$("#quoteForm #add_cliente").on("click", function(e) {

    e.preventDefault();

    searchCustomersQuote();

});



function searchCustomersQuote() {

    listar_clientes_cotizacion_buscar();

    $('#modal_buscar_clientes_facturacion').modal({

        show: true,

        keyboard: false,

        backdrop: 'static'

    });

}

//FIN CLIENTES COTIZACION



//INICIO BUSQUEDA CLIENTES EN COTIZACION

$('#quoteForm #buscar_clientes').on('click', function(e) {

    e.preventDefault();

    listar_clientes_cotizacion_buscar();

    $('#modal_buscar_clientes_facturacion').modal({

        show: true,

        keyboard: false,

        backdrop: 'static'

    });

});



var listar_clientes_cotizacion_buscar = function() {

    var table_clientes_cotizacion_buscar = $("#DatatableClientesBusquedaFactura").DataTable({

        "destroy": true,

        "ajax": {

            "method": "POST",

            "url": "<?php echo SERVERURL;?>core/llenarDataTableClientes.php"

        },

        "columns": [

            {
                "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-copy'></span></button>"
            },

            {
                "data": "cliente"
            },

            {
                "data": "rtn"
            },

            {
                "data": "telefono"
            },

            {
                "data": "correo"
            }

        ],

        "pageLength": 5,

        "lengthMenu": lengthMenu,

        "stateSave": true,

        "bDestroy": true,

        "language": idioma_español,

        "dom": dom,

        "buttons": [

            {

                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',

                titleAttr: 'Actualizar Clientes',

                className: 'table_actualizar btn btn-secondary ocultar',

                action: function() {

                    listar_clientes_factura_buscar();

                }

            },

            {

                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',

                titleAttr: 'Agregar Clientes',

                className: 'table_crear btn btn-primary ocultar',

                action: function() {

                    modal_clientes();

                }

            }

        ],

        "drawCallback": function(settings) {

            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

        }

    });

    table_clientes_cotizacion_buscar.search('').draw();

    $('#buscar').focus();



    view_clientes_busqueda_cotizacion_dataTable("#DatatableClientesBusquedaFactura tbody",
        table_clientes_cotizacion_buscar);

}



var view_clientes_busqueda_cotizacion_dataTable = function(tbody, table) {

    $(tbody).off("click", "button.table_view");

    $(tbody).on("click", "button.table_view", function(e) {

        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        $('#quoteForm #cliente_id').val(data.clientes_id);

        $('#quoteForm #cliente').val(data.cliente);

        $('#quoteForm #client-customers-quote').html("<b>Cliente:</b> " + data.cliente);

        $('#quoteForm #rtn-customers-quote').html("<b>RTN:</b> " + data.rtn);

        $('#modal_buscar_clientes_facturacion').modal('hide');

    });

}

//FIN BUSQUEDA CLIENTES EN COTIZACION



//INICIO BUSQUEDA COLABORADORES EN COTIZACION

function serchColaboradoresQuote() {

    listar_colaboradores_buscar_cotizacion();

    $('#modal_buscar_colaboradores_facturacion').modal({

        show: true,

        keyboard: false,

        backdrop: 'static'

    });

}



$('#quoteForm #add_vendedor').on('click', function(e) {

    e.preventDefault();

    serchColaboradoresQuote();

});



var listar_colaboradores_buscar_cotizacion = function() {

    var table_colaboradores_buscar_cotizacion = $("#DatatableColaboradoresBusquedaFactura").DataTable({

        "destroy": true,

        "ajax": {

            "method": "POST",

            "url": "<?php echo SERVERURL;?>core/llenarDataTableColaboradores.php"

        },

        "columns": [

            {
                "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-copy'></span></button>"
            },

            {
                "data": "colaborador"
            },

            {
                "data": "identidad"
            },

            {
                "data": "telefono"
            }

        ],

        "pageLength": 5,

        "lengthMenu": lengthMenu,

        "stateSave": true,

        "bDestroy": true,

        "language": idioma_español,

        "dom": dom,

        "columnDefs": [

            {
                width: "25%",
                targets: 0
            },

            {
                width: "25%",
                targets: 1
            },

            {
                width: "25%",
                targets: 2
            },

            {
                width: "25%",
                targets: 3
            }

        ],

        "buttons": [

            {

                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',

                titleAttr: 'Actualizar Productos',

                className: 'table_actualizar btn btn-secondary ocultar',

                action: function() {

                    listar_colaboradores_buscar_cotizacion();

                }

            },

            {

                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',

                titleAttr: 'Agregar Productos',

                className: 'table_crear btn btn-primary ocultar',

                action: function() {

                    modal_colaboradores();

                }

            }

        ],

        "drawCallback": function(settings) {

            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

        }

    });

    table_colaboradores_buscar_cotizacion.search('').draw();

    $('#buscar').focus();



    view_colaboradores_busqueda_cotizacion_dataTable("#DatatableColaboradoresBusquedaFactura tbody",
        table_colaboradores_buscar_cotizacion);

}



var view_colaboradores_busqueda_cotizacion_dataTable = function(tbody, table) {

    $(tbody).off("click", "button.table_view");

    $(tbody).on("click", "button.table_view", function(e) {

        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        $('#quoteForm #colaborador_id').val(data.colaborador_id);

        $('#quoteForm #colaborador').val(data.colaborador);

        $('#quoteForm #colaborador').val(data.colaborador);

        $('#quoteForm #vendedor-customers-quote').html("<b>Vendedor:</b> " + data.colaborador);

        $('#modal_buscar_colaboradores_facturacion').modal('hide');

    });

}

//FIN BUSQUEDA COLABORADORES EN COTIZACION

$(document).ready(function() {

    $('#view_quote').on("keydown", function(e) {

        if (e.which === 117) { //TECLA F6 (COBRAR)

            $("#quoteForm").submit();

            e.preventDefault();

        }


        if (e.which === 118) { //TECLA F7 (CLIENTES)

            searchCustomersBill();

            e.preventDefault();

        }



        if (e.which === 119) { //TECLA F8 (Colaboradores)

            serchColaboradoresBill();

            e.preventDefault();

        }



        if (e.which === 120) { //TECLA F9 (COMENTARIO)

            addComentarioQuote();

            e.preventDefault();

        }

    });

});

//INICIO COMENTARIO CONTIZACION

function addComentarioQuote() {
    swal({
        title: "¿Estas seguro?",
        text: "¿Desea agregar un comentario a la factura?",
        content: {
            element: "input",
            attributes: {
                placeholder: "Comentario",
                type: "text",
            },
        },
        icon: "warning",
        buttons: {
            cancel: "Cancelar",
            confirm: {
                text: "¡Sí, agregar comentario!",
                closeModal: false,
            },
        },
        dangerMode: true,
        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera         
    }).then((value) => {
        if (value === null || value.trim() === "") {
            showNotify('error', 'Error', '¡Necesita escribir algo!');
            return false;
        }
        $("#quoteForm #notesQuote").val(inputValue);
        $("#quoteForm #comentario-customers-quote").html("<b> Comentario:</b> " + inputValue);
    });
}

//INICIO ADD COMENTARIO FACTURAS

$("#quoteForm #notasQuote").on("click", function(e) {

    addComentarioQuote();

    e.preventDefault();

});

//FIN COMENTARIO CONTIZACION



$(document).ready(function() {

    $("#quoteForm #QuoteItem").on('blur', '.buscar_cantidad', function() {

        var row_index = $(this).closest("tr").index();

        var col_index = $(this).closest("td").index();



        var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());

        var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());



        //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS

        if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 &&
            parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val()) != 0) {

            //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO

            if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat(
                    $('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {

                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                    '#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());

            } else {

                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                    '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

            }

        } else {

            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

        }



        var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val());

        var total = parseFloat($('#quoteForm #QuoteItem #total_' + row_index).val());

        var descuento = parseFloat($('#quoteForm #QuoteItem #discountQuote_' + row_index).val());

        $('#quoteForm #QuoteItem #discountQuote_' + row_index).val(cantidad * descuento);



        var isv = 0;

        var isv_total = 0;

        var porcentaje_isv = 0;

        var porcentaje_calculo = 0;

        var isv_neto = 0;



        if (impuesto_venta == 1) {

            porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);

            if (total == "" || total == 0) {

                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                    .toFixed(2);

                isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

            } else {

                isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());

                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                    .toFixed(2);

                isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

            }

        }



        calculateTotalQuote();

    });

});



$(document).ready(function() {

    $("#quoteForm #QuoteItem").on('keyup', '.buscar_cantidad', function() {

        var row_index = $(this).closest("tr").index();

        var col_index = $(this).closest("td").index();



        var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());

        var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());



        //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS

        if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 &&
            parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val()) != 0) {

            //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO

            if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat(
                    $('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {

                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                    '#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());

            } else {

                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                    '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

            }

        } else {

            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($(
                '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());

        }



        var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val());

        var total = parseFloat($('#quoteForm #QuoteItem #totalQuote_' + row_index).val());

        var descuento = parseFloat($('#quoteForm #QuoteItem #discountQuote_' + row_index).val());

        $('#quoteForm #QuoteItem #discountQuote_' + row_index).val(cantidad * descuento);



        var isv = 0;

        var isv_total = 0;

        var porcentaje_isv = 0;

        var porcentaje_calculo = 0;

        var isv_neto = 0;



        if (impuesto_venta == 1) {

            porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);

            if (total == "" || total == 0) {

                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                    .toFixed(2);

                isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

            } else {

                isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());

                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                    .toFixed(2);

                isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);

            }

        }

        calculateTotalQuote();

    });

});

//INICIO DETALLES COTIZACION
function generarFilaCotizacion(count) {
    let htmlRow = '<tr>';
    htmlRow += '<td><input class="itemRowQuote" type="checkbox"></td>';
    htmlRow += '<td><input type="hidden" name="referenciaProductoQuote[]" id="referenciaProductoQuote_' + count + '" class="form-control" placeholder="Referencia Producto Precio" autocomplete="off">';
    htmlRow += '<input type="hidden" name="isvQuote[]" id="isvQuote_' + count + '" class="form-control" placeholder="Producto ISV" autocomplete="off">';
    htmlRow += '<input type="hidden" name="valorQuote_isv[]" id="valorQuote_isv_' + count + '" class="form-control" placeholder="Valor ISV" autocomplete="off">';
    htmlRow += '<input type="hidden" name="productosQuote_id[]" id="productosQuote_id_' + count + '" class="form-control inputfield-details1" placeholder="Código del Producto" autocomplete="off">';
    htmlRow += '<div class="input-group mb-3"><div class="input-group-prepend">';
    htmlRow += '<button type="button" data-toggle="modal" class="btn btn-link buscar_productos_quote p-0" data-toggle="tooltip" data-placement="top" title="Búsqueda de Productos" id="icon-search-bar_' + count + '">';
    htmlRow += '<i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i></button></div>';
    htmlRow += '<input type="text" name="bar-code-id[]" id="bar-code-id_' + count + '" class="form-control product-bar-code inputfield-details1" placeholder="Código del Producto" autocomplete="off"></div></td>';
    
    // Descripción de producto como texto (span) con input oculto para el valor
    htmlRow += '<td>';
    htmlRow += '<input type="hidden" name="productNameQuote[]" id="productNameQuote_' + count + '" autocomplete="off">';
    htmlRow += '<span id="productNameQuote_text_' + count + '" class="product-description">Descripción del Producto</span>';
    htmlRow += '</td>';
    
    htmlRow += '<td><input type="number" name="quantityQuote[]" id="quantityQuote_' + count + '" step="0.01" placeholder="Cantidad" class="buscar_cantidad form-control inputfield-details" autocomplete="off">';
    htmlRow += '<input type="hidden" name="cantidad_mayoreoQuote[]" id="cantidad_mayoreoQuote_' + count + '" step="0.01" placeholder="Cantidad Mayoreo" class="buscar_cantidad form-control inputfield-details" autocomplete="off"></td>';
    
    htmlRow += '<td><div class="input-group mb-3"><input type="number" name="priceQuote[]" id="priceQuote_' + count + '" class="form-control" step="0.01" placeholder="Precio" readonly autocomplete="off">';
    htmlRow += '<div id="suggestions_producto_0" class="suggestions"></div>';
    htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><div class="sb-nav-link-icon"></div><i class="aplicar_precio_cotizacion fas fa-plus fa-lg"></i></a></div></div>';
    htmlRow += '<input type="hidden" name="precio_mayoreoQuote[]" id="precio_mayoreoQuote_' + count + '" step="0.01" placeholder="Precio Mayoreo" class="form-control inputfield-details" readonly autocomplete="off">';
    htmlRow += '<input type="hidden" name="precio_realQuote[]" id="precio_realQuote_' + count + '" placeholder="Precio Real" class="form-control inputfield-details" readonly autocomplete="off"></td>';
    
    htmlRow += '<td><div class="input-group mb-3"><input type="number" name="discountQuote[]" id="discountQuote_' + count + '" class="form-control" step="0.01" placeholder="Descuento" readonly autocomplete="off">';
    htmlRow += '<div id="suggestions_producto_0" class="suggestions"></div>';
    htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><div class="sb-nav-link-icon"></div><i class="aplicar_descuento_cotizacion fas fa-plus fa-lg"></i></a></div></div></td>';
    
    htmlRow += '<td><input type="number" name="totalQuote[]" id="totalQuote_' + count + '" placeholder="Total" class="form-control total inputfield-details" readonly autocomplete="off" step="0.01"></td>';
    htmlRow += '</tr>';
    return htmlRow;
}

function limpiarTablaQuote() {
    $("#quoteForm #QuoteItem > tbody").empty();
    let count = 0;
    $('#QuoteItem').append(generarFilaCotizacion(count));
    $("#quoteForm .tableFixHead").scrollTop($(document).height());
    $("#quoteForm #QuoteItem #bar-code-id_" + count).focus();
}

function addRowQuote() {
    let count = row + 1;
    $('#QuoteItem').append(generarFilaCotizacion(count));
    
    // MOVER SCROLL DE COTIZACIÓN AL FINAL
    $("#quoteForm .tableFixHead").scrollTop($(document).height());
    $("#quoteForm #QuoteItem #bar-code-id_" + count).focus();

    if (count > 0) {
        let icon_search = count - 1;
        $("#quoteForm #QuoteItem #icon-search-bar_" + icon_search).hide();
    }
}

// Función para actualizar la descripción cuando se carga un producto
function actualizarTextoProductoQuote(index, nombreProducto) {
    // Actualizar input oculto
    $("#productNameQuote_" + index).val(nombreProducto);
    
    // Actualizar texto visible
    $("#productNameQuote_text_" + index).text(nombreProducto || "Descripción del Producto");
}

//FIN DETALLES COTIZACION

//INICIO CALCULO DETALLES COTIZACION

$(document).ready(function() {

    $("#quoteForm #QuoteItem #bar-code-id_0").focus();



    $(document).on('click', '#checkAllQuote', function() {

        $(".itemRowQuote").attr("checked", this.checked);

    });

    $(document).on('click', '.itemRowQuote', function() {

        if ($('.itemRowQuote:checked').length == $('.Purchase').length) {

            $('#checkAllQuote').attr('checked', true);

        } else {

            $('#checkAllQuote').attr('checked', false);

        }

    });

    var count = $(".itemRowQuote").length;

    $(document).on('click', '#addRowsQuote', function() {

        if ($("#quoteForm #cliente").val() != "") {

            addRowQuote();

        } else {
            showNotify('error', 'Error', 'Lo sentimos no puede agregar más filas, debe seleccionar un cliente antes de poder continuar');
        }
    });

    $(document).on('click', '#removeRowsQuote', function() {

        if ($('.itemRowQuote').is(':checked')) {
            $(".itemRowQuote:checked").each(function() {
                $(this).closest('tr').remove();
                count--;
                console.log('eliminar', count, row)
            });
            $('#checkAllQuote').attr('checked', false);
            calculateTotalQuote();
        } else {
            showNotify('error', 'Error', 'Lo sentimos debe seleccionar un fila antes de intentar eliminarla');
        }
    });

    $(document).on('blur', "[id^=quantityQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('keyup', "[id^=quantityQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('blur', "[id^=priceQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('keyup', "[id^=priceQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('blur', "[id^=discountQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('keyup', "[id^=discountQuote_]", function() {

        calculateTotalQuote();

    });

    $(document).on('blur', "#taxRateQuote", function() {

        calculateTotalQuote();

    });

    $(document).on('blur', "#amountPaidQuote", function() {

        var amountPaid = $(this).val();

        var totalAftertax = $('#totalAftertaxQuote').val();

        if (amountPaid && totalAftertax) {

            totalAftertax = totalAftertax - amountPaid;

            $('#amountDueQuote').val(totalAftertax);

        } else {

            $('#amountDueQuote').val(totalAftertax);

        }

    });

    $(document).on('click', '.deleteInvoiceQuote', function() {

        var id = $(this).attr("id");

        if (confirm("Are you sure you want to remove this?")) {

            $.ajax({

                url: "action.php",

                method: "POST",

                dataType: "json",

                data: {
                    id: id,
                    action: 'delete_invoice'
                },

                success: function(response) {
                    if (response.status == 1) {
                        $('#' + id).closest("tr").remove();
                    }
                }
            });
        } else {
            return false;
        }
    });
});


function calculateTotalQuote() {
    var totalAmount = 0;
    var totalDiscount = 0;
    var totalISV = 0;
    var totalGeneral = 0;

    $("[id^='priceQuote_']").each(function() {
        var id = $(this).attr('id');
        id = id.replace("priceQuote_", '');
        var price = $('#priceQuote_' + id).val();
        var isv_calculo = $('#valorQuote_isv_' + id).val();
        var discount = $('#discountQuote_' + id).val();
        var quantity = $('#quantityQuote_' + id).val();

        if (!discount) {
            discount = 0;
        }

        if (!quantity) {
            quantity = 1;
            discount = 0;
            $('#discountQuote_' + id).val(0);
        }

        if (!isv_calculo) {
            isv_calculo = 0;
        }

        var total = (price * quantity);
        //$('#totalQuote_'+id).val(parseFloat(price*quantity) - parseFloat(discount));	
        $('#totalQuote_' + id).val(parseFloat(total));

        console.log(total)
        //console.log(price*quantity,'-' ,parseFloat(discount))

        totalAmount += total;
        totalGeneral += (price * quantity);
        totalISV += parseFloat(isv_calculo);
        totalDiscount += parseFloat(discount);
    });

    $('#subTotalQuote').val(parseFloat(totalAmount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    $('#subTotalQuoteFooter').val(parseFloat(totalAmount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    $('#taxDescuentoQuote').val(parseFloat(totalDiscount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    $('#taxDescuentoFooter').val(parseFloat(totalDiscount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    var taxRate = $("#taxRateQuote").val();
    var subTotal = totalAmount;

    if (subTotal) {
        $('#subTotalImporteQuote').val(parseFloat(totalGeneral).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        $('#taxAmountQuote').val(parseFloat(totalISV).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        $('#taxAmountQuoteFooter').val(parseFloat(totalISV).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        subTotal = (parseFloat(subTotal) + parseFloat(totalISV)) - parseFloat(totalDiscount);
        $('#totalAftertaxQuote').val(parseFloat(subTotal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        $('#totalAftertaxQuoteFooter').val(parseFloat(subTotal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
        var totalAftertax = $('#totalAftertaxQuote').val();
    }

}


function cleanFooterValueQuote() {
    $('#subTotalQuoteFooter').val("");
    $('#taxAmountQuoteFooter').val("");
    $('#totalAftertaxQuoteFooter').val("");
}

//FIN CALCULO DETALLES COTIZACION

//EDWIN

//FIN COTIZACION



$(document).ready(function() {

    $("#modalDescuentoCotizaciones").on('shown.bs.modal', function() {

        $(this).find('#formDescuentoCotizaciones #porcentaje_descuento_fact').focus();

    });

});



$(document).ready(function() {

    $("#modalModificarPrecioCotizaciones").on('shown.bs.modal', function() {

        $(this).find('#formModificarPrecioCotizaciones #referencia_modificar_precio_fact').focus();

    });

});

$(document).ready(function() {
    $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
    });
});


$(document).ready(function() {
    $("#modal_buscar_colaboradores_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_colaboradores_facturacion #buscar').focus();
    });
});

function getVigencia() {
    var url = '<?php echo SERVERURL;?>core/getVigencia.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#quoteForm #vigencia_quote').html("");
            $('#quoteForm #vigencia_quote').html(data);
        }
    });
}

$(document).ready(function() {
    $("#modal_buscar_productos_cotizacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_productos_cotizacion #buscar').focus();
    });
});

// INICIO DESCUENTO PRODUCTO EN COTIZACION
$(document).ready(function() {
    $("#quoteForm #QuoteItem").on('click', '.aplicar_descuento_cotizacion', function(e) {
        e.preventDefault();
        $('#formDescuentoCotizaciones')[0].reset();

        var row_index = $(this).closest("tr").index();
        var col_index = $(this).closest("td").index();

        if ($('#quoteForm #cliente_id').val() != "" && $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val() != "") {
            $('#formDescuentoCotizaciones #row_index').val(row_index);
            $('#formDescuentoCotizaciones #col_index').val(col_index);

            var productos_id = $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val();
            var producto = $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val();
            var precio = $("#quoteForm #QuoteItem #priceQuote_" + row_index).val();
            var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();
            var total = precio * cantidad;

            $('#formDescuentoCotizaciones #descuento_productos_id').val(productos_id);
            $('#formDescuentoCotizaciones #producto_descuento_fact').val(producto);
            $('#formDescuentoCotizaciones #precio_descuento_fact').val(total); // Usamos el total (precio * cantidad)
            $('#formDescuentoCotizaciones #cantidad_descuento_fact').val(cantidad);

            $('#formDescuentoCotizaciones #pro_descuento_fact').val("Aplicar Descuento");

            $('#modalDescuentoCotizaciones').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        } else {
            showNotify('error', 'Error', 'Debe seleccionar un cliente y un producto antes de continuar');
        }
    });

    // Cálculo del descuento en porcentaje
    $("#formDescuentoCotizaciones #porcentaje_descuento_fact").on("keyup", function() {
        var total = parseFloat($('#formDescuentoCotizaciones #precio_descuento_fact').val());
        var porcentaje = parseFloat($(this).val()) || 0;

        var descuento = total * (porcentaje / 100);
        $('#formDescuentoCotizaciones #descuento_fact').val(descuento.toFixed(2));
    });

    // Cálculo del porcentaje cuando se ingresa el monto directo
    $("#formDescuentoCotizaciones #descuento_fact").on("keyup", function() {
        var total = parseFloat($('#formDescuentoCotizaciones #precio_descuento_fact').val());
        var descuento = parseFloat($(this).val()) || 0;

        var porcentaje = (descuento / total) * 100;
        $('#formDescuentoCotizaciones #porcentaje_descuento_fact').val(porcentaje.toFixed(2));
    });
});

$("#reg_DescuentoFacturacion").on("click", function(e) {
    e.preventDefault();
    var row_index = $('#formDescuentoCotizaciones #row_index').val();
    var col_index = $('#formDescuentoCotizaciones #col_index').val();

    var descuento = parseFloat($('#formDescuentoCotizaciones #descuento_fact').val()) || 0;
    var precio = parseFloat($("#quoteForm #QuoteItem #priceQuote_" + row_index).val());
    var cantidad = parseFloat($("#quoteForm #QuoteItem #quantityQuote_" + row_index).val());
    var impuesto_venta = $("#quoteForm #QuoteItem #isvQuote_" + row_index).val();
    
    // Guardamos el descuento en la fila
    $("#quoteForm #QuoteItem #discountQuote_" + row_index).val(descuento.toFixed(2));

    var total_sin_descuento = precio * cantidad;
    var total_con_descuento = total_sin_descuento - descuento;

    if (total_con_descuento >= 0) {
        // Cálculo de ISV
        if (impuesto_venta == 1) {
            var porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);
            var isv_actual = parseFloat($('#quoteForm #taxAmountQuote').val()) || 0;
            var isv_nuevo = (total_con_descuento * porcentaje_isv).toFixed(2);
            
            // Actualizamos el ISV
            $('#quoteForm #taxAmountQuote').val(parseFloat(isv_actual) + parseFloat(isv_nuevo));
            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(isv_nuevo);
        }

        $('#modalDescuentoCotizaciones').modal('hide');
        calculateTotalQuote();
    } else {
        showNotify('warning', 'Advertencia', 'El valor del descuento es mayor al precio total del artículo, por favor corregir');
    }
});
// FIN DESCUENTO PRODUCTO EN COTIZACION

//INICIO MODIFICAR PRECIO EN PRODUCTO COTIZACIONES
$(document).ready(function() {
    $("#quoteForm #QuoteItem").on('click', '.aplicar_precio_cotizacion', function(e) {
        e.preventDefault();
        $('#formModificarPrecioCotizaciones')[0].reset();

        var row_index = $(this).closest("tr").index();
        var col_index = $(this).closest("td").index();
        $('#formModificarPrecioCotizaciones #row_index').val(row_index);
        $('#formModificarPrecioCotizaciones #col_index').val(col_index);

        if ($("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val() != "") {
            $('#formModificarPrecioCotizaciones')[0].reset();
            var clientes_id = $("#quoteForm #cliente_id").val();
            var fecha = $("#quoteForm #fecha").val();
            var productos_id = $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val();
            var producto = $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val();
            var precio = $("#quoteForm #QuoteItem #precio_realQuote_" + row_index).val();

            $('#formModificarPrecioCotizaciones #modificar_precio_fecha').val(fecha);
            $('#formModificarPrecioCotizaciones #modificar_precio_clientes_id').val(clientes_id);
            $('#formModificarPrecioCotizaciones #modificar_precio_productos_id').val(productos_id);
            $('#formModificarPrecioCotizaciones #producto_modificar_precio_fact').val(producto);

            $('#formModificarPrecioCotizaciones #pro_modificar_precio').val("Registrar");


            $('#modalModificarPrecioCotizaciones').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        } else {
            showNotify('error', 'Error', 'Debe seleccionar un cliente y un producto antes de continuar');
        }
    });
});
//FIN MODIFICAR PRECIO EN PRODUCTO COTIZACIONES
</script>