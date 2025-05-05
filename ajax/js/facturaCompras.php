<script>
    $(document).ready(function () {
        getBancoPurchase();
        getColaboradorCompras();
        getMedida(0);
        getProveedores();
        getColaboradores();
        getAlmacenProductos(0);
        getCuentasProveedores();
        setRecordatorio();
    });

    function getColaboradorCompras() {
        var url = '<?php echo SERVERURL;?>core/editarUsarioSistema.php';

        $.ajax({
            type: 'POST',
            url: url,
            success: function (valores) {
                var datos = eval(valores);
                $('#purchase-form #colaborador_id').val(datos[0]);
                $('#purchase-form #colaborador').val(datos[1]);
                $('#purchase-form #facturaPurchase').focus();
                return false;
            }
        });
    }

    $('#purchase-form #tipoPurchase').on("change", function () {
        if ($(this).is(":checked")) {
            $('#purchase-form .recordatorio').hide();
        } else {
            $('#purchase-form .recordatorio').show();
        }
    });

    function setRecordatorio() {
        var selectRecordatorio = $("#recordatorio");

        // Agregar la opción sin valor por default
        selectRecordatorio.append("<option value=''>Seleccionar un recordatorio mensual</option>");

        for (var i = 1; i <= 31; i++) {
            selectRecordatorio.append("<option value='" + i + "'>" + i + " de cada mes</option>");
        }

        var diaActual = new Date().getDate();
        selectRecordatorio.val(diaActual);
        selectRecordatorio.selectpicker('refresh'); // Actualizar el Bootstrap Select
    }

    //INICIO PURCHARSE BILL
    $(document).ready(function () {
        $("#modal_buscar_productos_compras").on('shown.bs.modal', function () {
            $(this).find('#formulario_busqueda_productos_compras #buscar').focus();
        });
    });

    //INICIO BUSQUEDA PRODUCTOS COMPRAS
    $(document).ready(function () {
        $("#purchase-form #purchaseItem").on('click', '.buscar_productos_purchase', function (e) {
            e.preventDefault();
            listar_productos_compras_buscar();
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();


            $('#formulario_busqueda_productos_compras #row').val(row_index);
            $('#formulario_busqueda_productos_compras #col').val(col_index);
            $('#modal_buscar_productos_compras').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        });

    });

    $('#formulario_busqueda_productos_compras #almacen').on('change', function () {
        listar_productos_compras_buscar();
    });

    var listar_productos_compras_buscar = function () {
        var bodega = $("#formulario_busqueda_productos_compras #almacen").val();

        var table_productos_compras_buscar = $("#DatatableProductosBusquedaCompra").DataTable({
            "destroy": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL;?>core/llenarDataTableProductosCompras.php",
                "data": {
                    "bodega": bodega
                }
            },
            "columns": [{
                    "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-cart-plus'></span></button>"
                },
                {
                    "data": "image",
                    "render": function (data, type, row, meta) {
                        var defaultImageUrl =
                            '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
                        var imageUrl = data ?
                            '<?php echo SERVERURL;?>vistas/plantilla/img/products/' +
                            data : defaultImageUrl;

                        var imageHtml = '<img class="table-image" src="' + imageUrl +
                            '" alt="Image Preview" height="100px" width="100px"/>';

                        var cell = $('td:eq(' + meta.col + ')', meta.settings.oInstance.api().row(
                            meta
                            .row).node());

                        var img = new Image();

                        img.onload = function () {
                            // La imagen se cargó correctamente, actualizar la imagen en la celda
                            $('.table-image', cell).attr('src', imageUrl);
                        };

                        img.onerror = function () {
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
                    "data": "medida"
                },
                {
                    "data": "tipo_producto"
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
                }
            ],
            "buttons": [{
                    text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                    titleAttr: 'Actualizar Productos',
                    className: 'table_actualizar btn btn-secondary ocultar',
                    action: function () {

                        listar_productos_compras_buscar();
                    }
                },

                {
                    text: '<i class="fas fas fa-plus fa-lg crear"></i> Crear',
                    titleAttr: 'Agregar Productos',
                    className: 'table_crear btn btn-primary ocultar',
                    action: function () {
                        modal_productos();
                    }
                }
            ],
            "drawCallback": function (settings) {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }
        });

        table_productos_compras_buscar.search('').draw();

        $('#buscar').focus();

        view_productos_busqueda_compras_dataTable("#DatatableProductosBusquedaCompra tbody",
            table_productos_compras_buscar);
    }

    function resetRowPurchase() {
        row = 0;
        $("#purchase-form #pucharse_row").val(0);
    }

    var row = 0;
    var view_productos_busqueda_compras_dataTable = function (tbody, table) {
        $(tbody).off("click", "button.table_view, td img");
        $(tbody).on("click", "button.table_view, td img", function (e) {

            e.preventDefault();

            /*if ($("#purchase-form #facturaPurchase").val() != "" && $("#purchase-form #proveedores_id").val() !=
                "" && $("#purchase-form #proveedor").val() != "" && $("#purchase-form #colaborador_id").val() !=
                "" && $("#purchase-form #colaborador").val() != "") {*/

            var data = table.row($(this).parents("tr")).data();

            //var row = $('#formulario_busqueda_productos_facturacion #row').val();

            $('#purchase-form #purchaseItem #bar-code-idPurchase_' + row).val(data.barCode);
            $('#purchase-form #purchaseItem #productos_idPurchase_' + row).val(data.productos_id);
            $('#purchase-form #purchaseItem #productNamePurchase_' + row).val(data.nombre);
            $('#purchase-form #purchaseItem #quantityPurchase_' + row).val(1);
            $('#purchase-form #purchaseItem #quantityPurchase_' + row).focus();
            //$('#purchase-form #purchaseItem #pricePurchase_' + row).val(data.precio_compra);
            $('#purchase-form #purchaseItem #medidaPurchase_' + row).val(data.medida);
            $('#purchase-form #purchaseItem #bodegaPurchase_' + row).val(data.almacen_id);
            $('#purchase-form #purchaseItem #discountPurchase_' + row).val(0);
            $('#purchase-form #purchaseItem #isvPurchase_' + row).val(data.isv_compra);

            var isv = 0;
            var isv_total = 0;
            var porcentaje_isv = 0;
            var porcentaje_calculo = 0;
            var isv_neto = 0;

            if (data.isv_compra == 1) {
                porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);
                if ($('#purchase-form #taxAmountPurchase').val() == "" || $(
                        '#purchase-form #taxAmountPurchase').val() == 0) {
                    porcentaje_calculo = (parseFloat(data.precio_compra) * porcentaje_isv).toFixed(2);
                    isv_neto = porcentaje_calculo;
                    $('#purchase-form #taxAmountPurchase').val(porcentaje_calculo);
                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row).val(porcentaje_calculo);
                } else {
                    isv_total = parseFloat($('#purchase-form #taxAmountPurchase').val());
                    porcentaje_calculo = (parseFloat(data.precio_compra) * porcentaje_isv).toFixed(2);
                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                    $('#purchase-form #taxAmountPurchase').val(isv_neto);
                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row).val(porcentaje_calculo);
                }
            }

            calculateTotalCompras();
            addRowCompras();
            $('#modal_buscar_productos_compras').modal('hide');
            row++;
        });
    }
    //FIN BUSQUEDA PRODUCTOS COMPRAS

    $(document).ready(function () {
        $("#purchase-form #purchaseItem").on('blur', '.buscar_cantidad_purchase', function () {
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();
            var impuesto_compra = parseFloat($('#purchase-form #purchaseItem #isvPurchase_' + row_index)
                .val());
            var cantidad = parseFloat($('#purchase-form #purchaseItem #quantityPurchase_' + row_index)
                .val());
            var precio = parseFloat($('#purchase-form #purchaseItem #pricePurchase_' + row_index)
        .val());
            var total = parseFloat($('#purchase-form #purchaseItem #totalPurchase_' + row_index).val());
            var isv = 0;
            var isv_total = 0;
            var porcentaje_isv = 0;
            var porcentaje_calculo = 0;
            var isv_neto = 0;

            if (impuesto_compra == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);

                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#purchase-form #taxAmountPurchase').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                }

            }



            calculateTotalCompras();

        });

    });



    $(document).ready(function () {

        $("#purchase-form #purchaseItem").on('keyup', '.buscar_cantidad_purchase', function () {

            var row_index = $(this).closest("tr").index();

            var col_index = $(this).closest("td").index();



            var impuesto_compra = parseFloat($('#purchase-form #purchaseItem #isvPurchase_' + row_index)
                .val());

            var cantidad = parseFloat($('#purchase-form #purchaseItem #quantityPurchase_' + row_index)
                .val());

            var precio = parseFloat($('#purchase-form #purchaseItem #pricePurchase_' + row_index)
        .val());

            var total = parseFloat($('#purchase-form #purchaseItem #totalPurchase_' + row_index).val());



            var isv = 0;

            var isv_total = 0;

            var porcentaje_isv = 0;

            var porcentaje_calculo = 0;

            var isv_neto = 0;



            if (impuesto_compra == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);

                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#purchase-form #taxAmountPurchase').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                }

            }



            calculateTotalCompras();

        });

    });



    $(document).ready(function () {

        $("#purchase-form #purchaseItem").on('blur', '.buscar_price_purchase', function () {

            var row_index = $(this).closest("tr").index();

            var col_index = $(this).closest("td").index();



            var impuesto_compra = parseFloat($('#purchase-form #purchaseItem #isvPurchase_' + row_index)
                .val());

            var cantidad = parseFloat($('#purchase-form #purchaseItem #quantityPurchase_' + row_index)
                .val());

            var precio = parseFloat($('#purchase-form #purchaseItem #pricePurchase_' + row_index)
        .val());

            var total = parseFloat($('#purchase-form #purchaseItem #totalPurchase_' + row_index).val());



            var isv = 0;

            var isv_total = 0;

            var porcentaje_isv = 0;

            var porcentaje_calculo = 0;

            var isv_neto = 0;



            if (impuesto_compra == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);

                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#purchase-form #taxAmountPurchase').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                }

            }



            calculateTotalCompras();

        });

    });



    $(document).ready(function () {

        $("#purchase-form #purchaseItem").on('keyup', '.buscar_price_purchase', function () {

            var row_index = $(this).closest("tr").index();

            var col_index = $(this).closest("td").index();



            var impuesto_compra = parseFloat($('#purchase-form #purchaseItem #isvPurchase_' + row_index)
                .val());

            var cantidad = parseFloat($('#purchase-form #purchaseItem #quantityPurchase_' + row_index)
                .val());

            var precio = parseFloat($('#purchase-form #purchaseItem #pricePurchase_' + row_index)
        .val());

            var total = parseFloat($('#purchase-form #purchaseItem #totalPurchase_' + row_index).val());



            var isv = 0;

            var isv_total = 0;

            var porcentaje_isv = 0;

            var porcentaje_calculo = 0;

            var isv_neto = 0;



            if (impuesto_compra == 1) {

                porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);

                if (total == "" || total == 0) {

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                } else {

                    isv_total = parseFloat($('#purchase-form #taxAmountPurchase').val());

                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv)
                        .toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#purchase-form #purchaseItem #valor_isvPurchase_' + row_index).val(
                        porcentaje_calculo);

                }

            }

            calculateTotalCompras();

        });

    });

    function generarFilaCompra(count) {
        let htmlRow = '<tr>';
        htmlRow += '<td><input class="itemRowPurchase" type="checkbox"></td>';

        // Código del producto
        htmlRow += '<td><div class="input-group mb-3"><div class="input-group-prepend">';
        htmlRow +=
            '<button type="button" data-toggle="modal" class="btn btn-link buscar_productos_purchase p-0" data-toggle="tooltip" data-placement="top" title="Búsqueda de Productos" id="icon-search-bar_' +
            count + '">';
        htmlRow += '<i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i></button></div>';
        htmlRow += '<input type="text" name="bar-code-idPurchase[]" id="bar-code-idPurchase_' + count +
            '" class="form-control product-bar-codePurchase inputfield-details1" placeholder="Código del Producto" autocomplete="off"></div></td>';

        // Nombre del producto y campos ocultos
        htmlRow += '<td><div class="input-group mb-3">';
        htmlRow += '<input type="hidden" name="isvPurchase[]" id="isvPurchase_' + count +
            '" class="form-control" autocomplete="off">';
        htmlRow += '<input type="hidden" name="valor_isvPurchase[]" id="valor_isvPurchase_' + count +
            '" class="form-control" autocomplete="off">';
        htmlRow += '<input type="hidden" name="productos_idPurchase[]" id="productos_idPurchase_' + count +
            '" class="form-control" autocomplete="off">';
        htmlRow += '<input type="text" name="productNamePurchase[]" id="productNamePurchase_' + count +
            '" class="form-control" autocomplete="off"></div></td>';

        // Cantidad
        htmlRow += '<td><input type="number" name="quantityPurchase[]" id="quantityPurchase_' + count +
            '" class="buscar_cantidad_purchase form-control" autocomplete="off" step="0.01"></td>';

        // Almacén (select)
        htmlRow += '<td><select id="almacenPurchase_' + count +
            '" name="almacenPurchase[]" class="selectpicker" required title="Almacén" data-live-search="true" data-size="5"></select></td>';

        // Campos ocultos y fecha de vencimiento
        htmlRow += '<td style="display: none;"><input type="hidden" name="medidaPurchase[]" id="medidaPurchase_' +
            count + '" readonly class="form-control buscar_medida_purchase" autocomplete="off">';
        htmlRow += '<input type="hidden" name="bodegaPurchase[]" id="bodegaPurchase_' + count +
            '" class="buscar_bodega_purchase form-control"></td>';

        htmlRow += '<td><input type="date" name="vencimientoPurchase[]" id="vencimientoPurchase_' + count +
            '" class="form-control buscar_medida_purchase" autocomplete="off"></td>';

        // Precio unitario
        htmlRow += '<td><input type="number" name="pricePurchase[]" id="pricePurchase_' + count +
            '" class="buscar_price_purchase form-control" autocomplete="off" step="0.01"></td>';

        // ISV
        htmlRow += '<td><input type="number" name="isvPurchaseWrite[]" id="isvPurchaseWrite_' + count +
            '" class="form-control" autocomplete="off" step="0.01"></td>';

        // Descuento
        htmlRow += '<td><input type="number" name="discountPurchase[]" id="discountPurchase_' + count +
            '" class="form-control" autocomplete="off" step="0.01"></td>';

        // Total
        htmlRow += '<td><input type="number" name="totalPurchase[]" id="totalPurchase_' + count +
            '" class="form-control total" readonly autocomplete="off" step="0.01"></td>';

        htmlRow += '</tr>';
        return htmlRow;
    }

    function limpiarTablaCompras() {
        $("#purchase-form #purchaseItem > tbody").empty();
        let count = 0;
        $('#purchaseItem').append(generarFilaCompra(count));
        $("#purchase-form .tableFixHead").scrollTop($(document).height());
        $("#purchase-form #purchaseItem #bar-code-idPurchase_" + count).focus();
    }

    function addRowCompras() {
        let count = parseInt($("#purchase-form #pucharse_row").val()) + 1;
        $('#purchaseItem').append(generarFilaCompra(count));

        // MOVER SCROLL FACTURA AL FINAL
        $("#purchase-form .tableFixHead").scrollTop($(document).height());
        $("#purchase-form #purchaseItem #bar-code-idPurchase_" + count).focus();

        if (count > 0) {
            let icon_search = count - 1;
            $("#purchase-form #purchaseItem #icon-search-bar_" + icon_search).hide();
        }

        $("#purchase-form #pucharse_row").val(count);

        // Inicializar componentes adicionales
        getMedida(count);
        getAlmacenProductos(count);

        // Asignar el evento 'input' dinámicamente
        $("#bar-code-idPurchase_" + count).on("input", function () {
            checkBarcode(count);
        });
    }

    // Función para manejar el cambio en el código de barras y habilitar/inhabilitar el campo 'Almacén'
    function checkBarcode(count) {
        var barcodeInput = $("#bar-code-idPurchase_" + count).val();
        var almacenSelect = $("#almacenPurchase_" + count);

        if (barcodeInput.trim() !== "") {
            // Si el código de barras tiene algún valor, hacemos el campo 'Almacén' obligatorio
            almacenSelect.attr('required', true);
        } else {
            // Si el código de barras está vacío, quitamos la obligación del campo 'Almacén'
            almacenSelect.removeAttr('required');
        }
    }

    /*
    function addRowCompras() {
        //var count = row + 1;
        var count = parseInt($("#purchase-form #pucharse_row").val()) + 1;
        var htmlRows = '';

        htmlRows += '<tr>';

        htmlRows += '<td><input class="itemRowPurchase" type="checkbox"></td>';

        htmlRows +=
            '<td><div class="input-group mb-3"><div class="input-group-append"><span data-toggle="tooltip" data-placement="top" title="Búsqueda de Productos" id="icon-search-bar_0"><a data-toggle="modal" href="#" class="btn btn-link form-control buscar_productos_purchase"><div class="sb-nav-link-icon"></div><i class="fas fa-search fa-lg icon-color"></i></a></span><input type="text" name="bar-code-idPurchase[]" id="bar-code-idPurchase_' + count + '" class="form-control product-bar-codePurchase inputfield-details1" placeholder="Código del Producto" autocomplete="off"></div></div></td>';

        htmlRows += '<td><div class="input-group mb-3"><input type="hidden" name="isvPurchase[]" id="isvPurchase_' + count +
            '" class="form-control" autocomplete="off"><input type="hidden" name="valor_isvPurchase[]" id="valor_isvPurchase_' +
            count +
            '" class="form-control" autocomplete="off"><input type="hidden" name="productos_idPurchase[]" id="productos_idPurchase_' +
            count +
            '" class="form-control" autocomplete="off"><input type="text" name="productNamePurchase[]" id="productNamePurchase_' +
            count + '" class="form-control" autocomplete="off"></div></td>';

        htmlRows += '<td><input type="number" name="quantityPurchase[]" id="quantityPurchase_' + count +
            '" class="buscar_cantidad_purchase form-control" autocomplete="off" step="0.01"></td>';

        htmlRows += '<td><select id="almacenPurchase_' + count + '" name="almacenPurchase[]" class="selectpicker" title="Almacén" data-live-search="true" required data-size="5"> </select> </td>' ;

        htmlRows += '<td style="display: none;"><input type="hidden" name="medidaPurchase[]" id="medidaPurchase_' + count +
            '" readonly class="form-control buscar_medida_purchase" autocomplete="off"><input type="hidden" name="bodegaPurchase[]" id="bodegaPurchase_' +
            count + '" class="buscar_bodega_purchase form-control"></td>';

        htmlRows += '<td><input type="date" name="vencimientoPurchase[]" id="vencimientoPurchase_' + count +
        '" class="form-control buscar_medida_purchase" autocomplete="off"><input type="hidden" name="bodegaPurchase[]" id="bodegaPurchase_' +
        count + '" class="buscar_bodega_purchase form-control"></td>';        
        

        htmlRows += '<td><input type="number" name="pricePurchase[]" id="pricePurchase_' + count +
            '" class="buscar_price_purchase form-control" autocomplete="off" step="0.01"></td>';

        htmlRows += '<td><input type="number" name="isvPurchaseWrite[]" id="isvPurchaseWrite_' + count +
            '" class=" form-control" autocomplete="off" step="0.01"></td>';

        htmlRows += '<td><input type="number" name="discountPurchase[]" id="discountPurchase_' + count +
            '" class="form-control" autocomplete="off" step="0.01"></td>';

        htmlRows += '<td><input type="number" name="totalPurchase[]" id="totalPurchase_' + count +
            '" class="form-control total" readonly autocomplete="off" step="0.01"></td>';

        htmlRows += '</tr>';

        $('#purchaseItem').append(htmlRows);

        $("#purchase-form #invoiceItem #icon-search-bar_" + icon_search).hide();
        $("#purchase-form #invoiceItem #icon-search-bar_" + icon_search).hide();

        $("#purchase-form #pucharse_row").val(count);

        getMedida(count);
        getAlmacenProductos(count);
    }*/

    $(document).ready(function () {

        $(document).on('click', '#checkAllPurchase', function () {

            $(".itemRowPurchase").attr("checked", this.checked);

        });

        $(document).on('click', '.itemRowPurchase', function () {

            if ($('.itemRowPurchase:checked').length == $('.Purchase').length) {

                $('#checkAllPurchase').attr('checked', true);

            } else {

                $('#checkAllPurchase').attr('checked', false);

            }

        });

        var count = $(".itemRowPurchase").length;

        $(document).on('click', '#addRowsPurchase', function () {
            if ($("#purchase-form #proveedor").val() != "") {
                addRowCompras();
            } else {
                showNotify('error', 'Error',
                    'Lo sentimos no puede agregar más filas, debe seleccionar un usuario antes de poder continuar'
                    );
            }
        });

        $(document).on('click', '#removeRowsPurchase', function () {

            if ($('.itemRowPurchase ').is(':checked')) {

                $(".itemRowPurchase:checked").each(function () {

                    $(this).closest('tr').remove();

                    count--;

                });

                $('#checkAllPurchase').attr('checked', false);

                calculateTotalCompras();

            } else {
                showNotify('error', 'Error',
                    'Lo sentimos debe seleccionar un fila antes de intentar eliminarla');
            }
        });

        $(document).on('blur', "[id^=quantityPurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('keyup', "[id^=quantityPurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('blur', "[id^=pricePurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('keyup', "[id^=pricePurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('blur', "[id^=discountPurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('keyup', "[id^=discountPurchase_]", function () {
            calculateTotalCompras();
        });

        $(document).on('keyup', "[id^=isvPurchaseWrite_]", function () {
            calculateTotalCompras();
        });

        $(document).on('blur', "[id^=isvPurchaseWrite_]", function () {
            calculateTotalCompras();
        });

        $(document).on('blur', "#taxRatePurchase", function () {
            calculateTotalCompras();
        });

        $(document).on('blur', "#amountPaidPurchase", function () {

            var amountPaid = $(this).val();

            var totalAftertax = $('#totalAftertaxPurchase').val();

            if (amountPaid && totalAftertax) {

                totalAftertax = totalAftertax - amountPaid;

                $('#amountDuePurchase').val(totalAftertax);

            } else {

                $('#amountDuePurchase').val(totalAftertax);

            }

        });

        $(document).on('click', '.deleteInvoicePurchase', function () {

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
                    success: function (response) {
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


    function calculateTotalCompras() {
        var totalAmount = 0;
        var totalDiscount = 0;
        var totalISV = 0;
        var totalGeneral = 0;
        var isv = 0;

        $("[id^='pricePurchase_']").each(function () {
            var id = $(this).attr('id');
            id = id.replace("pricePurchase_", '');
            var price = $('#pricePurchase_' + id).val();
            var isv_calculo = $('#valor_isvPurchase_' + id).val();
            var discount = $('#discountPurchase_' + id).val();
            var quantity = $('#quantityPurchase_' + id).val();
            var isv = $('#isvPurchaseWrite_' + id).val();

            console.log(isv + "<br/>");

            if (!discount) {
                discount = 0;
            }

            if (!quantity) {
                quantity = 1;
            }

            if (!isv_calculo) {
                isv_calculo = 0;
            }

            if (!isv) {
                isv = 0;
            }

            var total = ((price * quantity) + parseFloat(isv) - discount);
            var total1 = (price * quantity);
            $('#totalPurchase_' + id).val(parseFloat(total));
            totalAmount += total1;
            totalGeneral += (price * quantity);
            totalISV += parseFloat(isv);

            console.log(totalISV + "<br/>");

            totalDiscount += parseFloat(discount);
        });

        $('#subTotalPurchase').val(parseFloat(totalAmount).toFixed(2));
        $('#subTotalFooterPurchase').val(parseFloat(totalAmount).toFixed(2));
        $('#taxDescuentoPurchase').val(parseFloat(totalDiscount).toFixed(2));
        $('#taxDescuentoFooterPurchase').val(parseFloat(totalDiscount).toFixed(2));

        var taxRate = $("#taxRatePurchase").val();
        var subTotal = $('#subTotalPurchase').val();

        if (subTotal) {
            $('#subTotalImportePurchase').val(parseFloat(totalGeneral).toFixed(2));
            $('#taxAmountPurchase').val(parseFloat(totalISV).toFixed(2));

            $('#taxAmountFooterPurchase').val(parseFloat(totalISV).toFixed(2));

            subTotal = (parseFloat(subTotal) + parseFloat($('#taxAmountPurchase').val())) - parseFloat(totalDiscount);

            $('#totalAftertaxPurchase').val(parseFloat(subTotal).toFixed(2));

            $('#totalAftertaxFooterPurchase').val(parseFloat(subTotal).toFixed(2));

            var amountPaid = $('#amountPaidPurchase').val();

            var totalAftertax = $('#totalAftertaxPurchase').val();

            if (amountPaid && totalAftertax) {

                totalAftertax = totalAftertax - amountPaid;

                $('#amountDuePurchase').val(totalAftertax);

            } else {

                $('#amountDuePurchase').val(subTotal);

            }

        }

    }

    function cleanFooterValuePurchase() {
        $('#subTotalFooterPurchase').val("");
        $('#taxDescuentoFooterPurchase').val("");
        $('#taxAmountFooterPurchase').val("");
        $('#totalAftertaxFooterPurchase').val("");
    }


    $('#purchase-form #notesPurchase').keyup(function () {
        var max_chars = 2000;
        var chars = $(this).val().length;
        var diff = max_chars - chars;

        $('#purchase-form #charNum_notasPurchase').html(diff + ' Caracteres');

        if (diff == 0) {
            return false;
        }

    });

    function caracteresNotasCompras() {
        var max_chars = 2000;
        var chars = $('#purchase-form #notesPurchase').val().length;
        var diff = max_chars - chars;
        $('#purchase-form #charNum_notasPurchase').html(diff + ' Caracteres');
        if (diff == 0) {
            return false;
        }
    }

    $('#purchase-form #label_tipoPurchase').html("Contado");

    $('#purchase-form .switch').change(function () {
        if ($('input[name=tipoPurchase]').is(':checked')) {
            $('#purchase-form #label_tipoPurchase').html("Contado");
            return true;
        } else {
            $('#purchase-form #label_tipoPurchase').html("Crédito");
            return false;
        }
    });

    function getProveedores() {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/getProveedores.php",
            type: "POST",
            dataType: "json",
            success: function (response) {
                const select = $('#purchase-form #proveedor');
                select.empty();

                if (response.success) {
                    response.data.forEach(proveedor => {
                        select.append(`
                        <option value="${proveedor.proveedores_id}" 
                                data-subtext="${proveedor.rtn || 'Sin RTN'}">
                            ${proveedor.nombre}
                        </option>
                    `);
                    });

                    // Agrega este evento para actualizar el campo oculto
                    select.on('change', function () {
                        $('#proveedores_id').val($(this).val());
                    });
                } else {
                    select.append('<option value="">No hay proveedores disponibles</option>');
                }

                select.selectpicker('refresh');
            },
            error: function (xhr) {
                showNotify("error", "Error", "Error de conexión al cargar proveedores");
                $('#purchase-form #proveedor').html('<option value="">Error al cargar</option>');
                $('#purchase-form #proveedor').selectpicker('refresh');
            }
        });
    }

    function getAlmacenProductos(index) {
        var url = '<?php echo SERVERURL;?>core/getAlmacenCompras.php';

        $.ajax({
            type: "POST",
            url: url,
            async: true,
            success: function (data) {
                $('#purchase-form #almacenPurchase_' + index).html("");
                $('#purchase-form #almacenPurchase_' + index).html(data);
                $('#purchase-form #almacenPurchase_' + index).selectpicker('refresh');
            }
        });
    }


    $("#purchase-form #proveedor").on('change', function () {
        $('#purchase-form #proveedores_id').val($('#purchase-form #proveedor').val());
    });

    function getColaboradores() {
        $.ajax({
            url: "<?php echo SERVERURL; ?>core/getColaboradores.php",
            type: "POST",
            dataType: "json",
            success: function (response) {
                const select = $('#purchase-form #colaborador');
                select.empty();

                if (response.success) {
                    response.data.forEach(colaborador => {
                        select.append(`
                        <option value="${colaborador.colaboradores_id}" 
                                data-subtext="${colaborador.identidad || 'Sin identidad'}">
                            ${colaborador.nombre}
                        </option>
                    `);
                    });

                    // Seleccionar usuario del sistema
                    const usuarioSistema = getuUuarioSistema().toString().trim();

                    select.val(usuarioSistema);
                    select.selectpicker('refresh');

                } else {
                    select.append('<option value="">No hay colaboradores disponibles</option>');
                    select.selectpicker('refresh');
                }
            },
            error: function (xhr) {
                showNotify("error", "Error", "Error de conexión al cargar colaboradores");
                $('#purchase-form #colaborador').html('<option value="">Error al cargar</option>');
                $('#purchase-form #colaborador').selectpicker('refresh');
            }
        });
    }

    function getuUuarioSistema() {
        var url = '<?php echo SERVERURL;?>core/editarUsarioSistema.php';
        var UsuarioSistema = '';

        $.ajax({
            type: 'POST',
            url: url,
            async: false,
            success: function (valores) {
                try {
                    var datos = JSON.parse(valores);
                    UsuarioSistema = datos[0];
                } catch (e) {
                    console.error("Error al parsear JSON del usuario del sistema:", valores);
                }
            }
        });

        return UsuarioSistema;
    }


    $("#purchase-form #colaborador").on('change', function () {
        $('#purchase-form #colaborador_id').val($('#purchase-form #colaborador').val());
    });


    //INICIO INGRESO POR ESCANER
    $(document).ready(function () {
        $("#purchase-form #purchaseItem").on('keydown', '.product-bar-codePurchase', function (event) {

            var row_index = $(this).closest("tr").index();

            if (event.which === 10 || event.which === 13) {
                manejarPresionEnterCompras(row_index);
            }

            if (event.which === 43 || event.which === 45) {
                manejarPresionTeclaMasMenosCompras(event.which, row_index);
            }
        });
    });

    function manejarPresionEnterCompras(row_index) {
        event.preventDefault();

        $(".product-bar-codePurchase").focus();

        var barCodeInput = $("#purchase-form #purchaseItem #bar-code-idPurchase_" + row_index);
        var barcode = barCodeInput.val();

        if (barcode !== "") {
            var url = '<?php echo SERVERURL;?>core/getProductoBarCode.php';
            var element = barcode.split('*');
            var cantidad = element[0] || 1;
            var barcodeValue = element[1] || cantidad;

            $.ajax({
                type: 'POST',
                url: url,
                data: 'barcode=' + barcodeValue,
                async: false,
                success: function (registro) {
                    var valores = eval(registro);

                    if (valores[0]) {
                        $("#purchase-form #purchaseItem #bar-code-idPurchase_" + row_index).val(barcode);

                        // Verificar si el valor ingresado contiene un '*'
                        if (barcode.includes('*')) {
                            var parts = barcode.split('*');
                            var cantidad = parseFloat(parts[0]) || 1;
                            var nuevoBarcode = parts[1];

                            // Asignar la cantidad y el código del producto a los campos correspondientes
                            $("#purchase-form #purchaseItem #quantityPurchase_" + row_index).val(cantidad);
                            $("#purchase-form #purchaseItem #bar-code-idPurchase_" + row_index).val(
                                nuevoBarcode);
                        } else {
                            // Si no hay '*', asumir que la cantidad es 1 y el código es el valor ingresado
                            $("#purchase-form #purchaseItem #quantityPurchase_" + row_index).val(1);
                            $("#purchase-form #purchaseItem #bar-code-idPurchase_" + row_index).val(
                            barcode);
                        }

                        $("#purchase-form #purchaseItem #productNamePurchase_" + row_index).val(valores[0]);
                        $("#purchase-form #purchaseItem #pricePurchase_" + row_index).val(valores[1]);
                        $("#purchase-form #purchaseItem #precio_real_" + row_index).val(valores[1]);
                        $("#purchase-form #purchaseItem #productos_idPurchase_" + row_index).val(valores[
                        2]);
                        $("#purchase-form #purchaseItem #isvPurchaseWrite_" + row_index).val(valores[3]);
                        $('#purchase-form #purchaseItem #medidaPurchase_' + row_index).val(valores[8]);
                        $('#purchase-form #purchaseItem #discountPurchase_' + row_index).val(0);

                        var impuesto_venta = parseFloat($(
                            '#purchase-form #purchaseItem #isvPurchaseWrite_' +
                            row_index).val());
                        var cantidad1 = parseFloat($('#purchase-form #purchaseItem #quantityPurchase_' +
                            row_index).val());
                        var precio = parseFloat($('#purchase-form #purchaseItem #pricePurchase_' +
                                row_index)
                            .val());
                        var total = parseFloat($('#purchase-form #purchaseItem #totalPurchase_' + row_index)
                            .val());

                        var isv = 0;
                        var isv_total = 0;
                        var porcentaje_isv = 0;
                        var porcentaje_calculo = 0;
                        var isv_neto = 0;

                        if (impuesto_venta == 1) {
                            porcentaje_isv = parseFloat(getPorcentajeISV("Compras") / 100);

                            if (total == "" || total == 0) {
                                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) *
                                    porcentaje_isv).toFixed(2);
                                isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                                $('#purchase-form #purchaseItem #isvPurchaseWrite_' + row_index).val(
                                    porcentaje_calculo);
                            } else {
                                isv_total = parseFloat($('#purchaseItem-form #taxAmount').val());
                                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) *
                                    porcentaje_isv).toFixed(2);
                                isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                                $('#purchase-form #purchaseItem #isvPurchaseWrite_' + row_index).val(
                                    porcentaje_calculo);
                            }
                        }

                        addRowCompras();

                        if (row_index > 0) {
                            var icon_search = row_index - 1;
                        }

                        $("#purchase-form #purchaseItem #icon-search-bar_" + row_index).hide();
                        $("#purchase-form #purchaseItem #icon-search-bar_" + icon_search).hide();

                        calculateTotalCompras();
                    } else {
                        showNotify('error', 'Error', 'Producto no encontrado, por favor corregir');
                        $("#purchase-form #purchaseItem #bar-code-id_" + row_index).val("");
                    }
                }
            });
        }
    }

    function facturarEnCeroAlmacen(almacen_id) {

        var url = '<?php echo SERVERURL; ?>core/getFacturarCeroAlmacen.php';
        var estado = true;

        $.ajax({
            type: 'POST',
            url: url,
            data: 'almacen_id=' + almacen_id,
            async: false,
            success: function (res) {
                estado = res;
            }
        });
        return estado;
    }

    function manejarPresionTeclaMasMenosCompras(codigoTecla, row_index) {
        event.preventDefault();
        var cantidadInput = $("#purchase-form #itemRowPurchase #quantity_" + row_index);
        var cantidad = parseFloat(cantidadInput.val()) || 1;

        if (codigoTecla === 43) { // Tecla de suma
            cantidad++;
        } else if (codigoTecla === 45) { // Tecla de resta
            cantidad = Math.max(cantidad - 1, 1);
        }

        cantidadInput.val(cantidad);

        var impuesto_venta = parseFloat($('#purchase-form #itemRowPurchase #isv_' + row_index).val());
        var cantidad1 = parseFloat($('#purchase-form #itemRowPurchase #quantity_' + row_index).val());
        var precio = parseFloat($('#purchase-form #itemRowPurchase #price_' + row_index).val());
        var total = parseFloat($('#purchase-form #itemRowPurchase #total_' + row_index).val());

        var isv = 0;
        var isv_total = 0;
        var porcentaje_isv = 0;
        var porcentaje_calculo = 0;
        var isv_neto = 0;

        if (impuesto_venta == 1) {
            porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);

            if (total == "" || total == 0) {
                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);
                isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                $('#purchase-form #itemRowPurchase #valor_isv_' + row_index).val(porcentaje_calculo);
            } else {
                isv_total = parseFloat($('#purchase-form #taxAmount').val());
                porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);
                isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                $('#purchase-form #itemRowPurchase #valor_isv_' + row_index).val(porcentaje_calculo);
            }
        }

        calculateTotalFacturas();
    }
    //FIN INGRESO POR ESCANER
</script>