<script>
    $(() => {
        cleanQuote();
        getVigencia();
    });
    //INICIO COTIZACIONES

    function resetRow() {
        row = 0;
    }

    var row = 0;

    /* =========================================================
       HELPERS VISUALES PRODUCTOS - COTIZACIÓN
       ---------------------------------------------------------
       Se usan únicamente en el DataTable de búsqueda de productos.
       Mantienen botones normales de DataTables y muestran el saldo
       como badge sin agrandar el precio.
    ========================================================= */

    function cotizacionNormalizarNumeroTablaProducto(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return 0;
        }

        if (typeof valor === 'number') {
            return isNaN(valor) ? 0 : valor;
        }

        valor = String(valor)
            .replace(/L\./g, '')
            .replace(/L/g, '')
            .replace(/,/g, '')
            .replace(/<[^>]*>/g, '')
            .trim();

        var numero = parseFloat(valor);

        return isNaN(numero) ? 0 : numero;
    }

    function cotizacionFormatoNumeroTablaProducto(valor) {
        valor = cotizacionNormalizarNumeroTablaProducto(valor);

        return valor.toLocaleString('es-HN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function cotizacionRenderBadgeSaldoProducto(data, type) {
        var saldo = cotizacionNormalizarNumeroTablaProducto(data);

        if (type !== 'display') {
            return saldo;
        }

        var clase = 'producto-saldo-badge producto-saldo-ok';
        var icono = 'fa-check-circle';
        var texto = 'Disponible';

        if (saldo <= 0) {
            clase = 'producto-saldo-badge producto-saldo-danger';
            icono = 'fa-times-circle';
            texto = 'Sin saldo';
        } else if (saldo <= 5) {
            clase = 'producto-saldo-badge producto-saldo-warning';
            icono = 'fa-exclamation-circle';
            texto = 'Saldo bajo';
        }

        return ''
            + '<div class="producto-saldo-wrap">'
            + '    <span class="' + clase + '">'
            + '        <i class="fas ' + icono + '"></i> ' + cotizacionFormatoNumeroTablaProducto(saldo)
            + '    </span>'
            + '    <small>' + texto + '</small>'
            + '</div>';
    }

    function cotizacionRenderPrecioProductoNormal(data, type) {
        var precio = cotizacionNormalizarNumeroTablaProducto(data);

        if (type !== 'display') {
            return precio;
        }

        var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(precio || 0);

        return '<span style="color:green; font-size:inherit !important; font-weight:inherit !important; line-height:inherit !important;">' + number + '</span>';
    }

    function cotizacionAplicarEstilosProductosBusqueda() {
        if ($('#styleProductosBusquedaCotizacion').length > 0) {
            return;
        }

        $('head').append(
            '<style id="styleProductosBusquedaCotizacion">' +
                '.producto-saldo-wrap{' +
                    'display:flex;' +
                    'flex-direction:column;' +
                    'align-items:center;' +
                    'justify-content:center;' +
                    'gap:3px;' +
                '}' +
                '.producto-saldo-wrap small{' +
                    'font-size:12px;' +
                    'font-weight:700;' +
                    'line-height:1.1;' +
                '}' +
                '.producto-saldo-badge{' +
                    'display:inline-flex;' +
                    'align-items:center;' +
                    'justify-content:center;' +
                    'gap:6px;' +
                    'border-radius:999px;' +
                    'padding:5px 11px;' +
                    'font-size:14px;' +
                    'font-weight:800;' +
                    'line-height:1;' +
                    'white-space:nowrap;' +
                '}' +
                '.producto-saldo-ok{' +
                    'background:#dcfce7;' +
                    'border:1px solid #86efac;' +
                    'color:#15803d;' +
                '}' +
                '.producto-saldo-warning{' +
                    'background:#fef3c7;' +
                    'border:1px solid #fde68a;' +
                    'color:#b45309;' +
                '}' +
                '.producto-saldo-danger{' +
                    'background:#fee2e2;' +
                    'border:1px solid #fecaca;' +
                    'color:#b91c1c;' +
                '}' +
                '#DatatableProductosBusquedaCotizacion tbody td:nth-child(8), #DatatableProductosBusquedaCotizacion tbody td:nth-child(8) span{' +
                    'font-size:inherit !important;' +
                    'font-weight:inherit !important;' +
                    'line-height:inherit !important;' +
                '}' +
            '</style>'
        );
    }

    function cotizacionConstruirHeaderProductosBusqueda() {
        var $tabla = $('#DatatableProductosBusquedaCotizacion');

        if ($tabla.length === 0) {
            return;
        }

        $tabla.find('thead').remove();

        $tabla.prepend(
            '<thead>' +
                '<tr>' +
                    '<th>Seleccione</th>' +
                    '<th>Imagen</th>' +
                    '<th>Bar Code</th>' +
                    '<th>Producto</th>' +
                    '<th>Saldo</th>' +
                    '<th>Medida</th>' +
                    '<th>Tipo Producto</th>' +
                    '<th>Venta</th>' +
                    '<th>Bodega</th>' +
                '</tr>' +
            '</thead>'
        );
    }


    $(() => {
        $("#quoteForm #QuoteItem").on('keypress', '.product-bar-code', function(event) {
            //EVALUAMOS EL ENTER event.which == '13'
            if (event.which === 10 || event.which === 13) {
                event.preventDefault();
                $(".product-bar-code").focus();
                var row_index = $(this).closest("tr").index();
                var col_index = $(this).closest("td").index();
                var icon_search = 0;

                if ($("#quoteForm #QuoteItem #bar-code-id_" + row_index).val() != "") {
                    var url = '<?php echo SERVERURL; ?>core/getProdcutoBarCode.php';
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
                                $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val(valores[0]);
                                $("#quoteForm #QuoteItem #priceQuote_" + row_index).val(valores[1]);
                                $("#quoteForm #QuoteItem #productosQuote_id_" + row_index).val(valores[2]);
                                $("#quoteForm #QuoteItem #isvQuote_" + row_index).val(valores[3]);
                                setISVFlagsQuoteRow(row_index, valores[3], valores[6], valores[7]);
                                limpiarISVMontosQuoteRow(row_index);
                                $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val(cantidad);
                                $("#quoteForm #QuoteItem #bar-code-id_" + row_index).val(barcode);
                                $("#quoteForm #QuoteItem #cantidad_mayoreoQuote_" + row_index).val(valores[4]);
                                $("#quoteForm #QuoteItem #precio_realQuote_" + row_index).val(valores[1]);
                                $("#quoteForm #QuoteItem #precio_mayoreoQuote_" + row_index).val(valores[5]);

                                var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());
                                var cantidad1 = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index) .val());
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
                                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);
                                        isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                                    } else {
                                        isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);
                                        isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                                    }
                                }

                                addRowQuote();
                                if (row_index > 0) {
                                    var icon_search = row_index - 1;
                                }

                                $("#quoteForm #QuoteItem #icon-search-bar_" + row_index).hide();
                                $("#quoteForm #QuoteItem #icon-search-bar_" + icon_search).hide();

                                recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
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

    $(() => {
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
                        if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 && parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' +
                                row_index).val()) != 0) {

                            //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO
                            if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {
                                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());
                            } else {
                                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($( '#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
                            }
                        } else {
                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
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
                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);
                        isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                    } else {
                        isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);

                        isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                    }
                }

                recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
            }

            //TECLA MENOS
            if (event.which === 45) {
                if ($("#quoteForm #QuoteItem #bar-code-id_" + row_index).val() != "" && $("#quoteForm #QuoteItem #productNameQuote_" + row_index).val() != "") {
                    event.preventDefault();

                    var cantidad = $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val();

                    if (!cantidad) {
                        cantidad = 1;
                    }

                    cantidad--;

                    if (cantidad > 0) {
                        $("#quoteForm #QuoteItem #quantityQuote_" + row_index).val(cantidad);

                        //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS
                        if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 && parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val()) != 0) {

                            //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO
                            if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {
                                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());
                            } else {
                                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
                            }
                        } else {
                            $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
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
                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);

                        isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                    } else {
                        isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                        porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad1) * porcentaje_isv).toFixed(2);

                        isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);
                        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                    }
                }
                recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
            }
        });
    });
    //FIN COTIZACIONES

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


    /* =========================================================
       INICIO - HELPERS ISV COTIZACIÓN 15% / 18%
       ========================================================= */

    function normalizarNumeroQuote(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return 0;
        }

        if (typeof valor === 'number') {
            return isNaN(valor) ? 0 : valor;
        }

        valor = String(valor)
            .replace(/L\./g, '')
            .replace(/L/g, '')
            .replace(/,/g, '')
            .trim();

        var numero = parseFloat(valor);
        return isNaN(numero) ? 0 : numero;
    }

    function formatearPorcentajeLabelISVCotizacion(valor) {
        valor = normalizarNumeroQuote(valor);

        if (valor <= 0) {
            return '';
        }

        if (Number.isInteger(valor)) {
            return valor.toString();
        }

        return valor.toFixed(2).replace(/\.?0+$/, '');
    }

    var cacheISVCotizacion = typeof cacheISVCotizacion !== 'undefined' ? cacheISVCotizacion : {};

    function fetchISVPercentSyncCotizacion(isv_id) {
        isv_id = parseInt(isv_id, 10);

        if (!isv_id || isv_id <= 0) {
            return 0;
        }

        if (cacheISVCotizacion[isv_id] !== undefined) {
            return cacheISVCotizacion[isv_id];
        }

        var porcentaje = 0;

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>core/getISV.php',
            data: {
                isv_id: isv_id
            },
            dataType: 'json',
            async: false,
            success: function(response) {
                if (response && response.success === true && response.valor !== undefined) {
                    porcentaje = normalizarNumeroQuote(response.valor);
                } else if (response && response.valor !== undefined) {
                    porcentaje = normalizarNumeroQuote(response.valor);
                } else if ($.isArray(response) && response.length > 0) {
                    porcentaje = normalizarNumeroQuote(response[0]);
                } else if (typeof response === 'number' || typeof response === 'string') {
                    porcentaje = normalizarNumeroQuote(response);
                }
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                porcentaje = 0;
            }
        });

        cacheISVCotizacion[isv_id] = porcentaje;

        return porcentaje;
    }

    function obtenerPorcentajeISVCotizacionPorId(isv_id) {
        var porcentaje = 0;

        /*
           Prioridad correcta:
           1) Consultar core/getISV.php por isv_id.
           2) Usar únicamente el campo isv.valor de la tabla isv.
           3) No quemar 15, 18 ni 20 en el JS.
        */
        porcentaje = normalizarNumeroQuote(fetchISVPercentSyncCotizacion(isv_id));

        return porcentaje;
    }

    function actualizarLabelsISVCotizacion() {
        var porcentajeISV1 = obtenerPorcentajeISVCotizacionPorId(1);
        var porcentajeISV2 = obtenerPorcentajeISVCotizacionPorId(2);

        var textoISV1 = porcentajeISV1 > 0 ? 'ISV ' + formatearPorcentajeLabelISVCotizacion(porcentajeISV1) + '%' : 'ISV';
        var textoISV2 = porcentajeISV2 > 0 ? 'ISV ' + formatearPorcentajeLabelISVCotizacion(porcentajeISV2) + '%' : 'ISV';

        if ($('#taxAmountQuoteFooter').length) {
            $('#taxAmountQuoteFooter').closest('.metric').find('label').text(textoISV1 + ':');
        }

        if ($('#taxAmountQuoteFooter18').length) {
            $('#taxAmountQuoteFooter18').closest('.metric').find('label').text(textoISV2 + ':');
        }

        if ($('#taxAmountQuote').length) {
            $('#taxAmountQuote').closest('.metric').find('label').text(textoISV1 + ':');
        }

        if ($('#taxAmountQuote18').length) {
            $('#taxAmountQuote18').closest('.metric').find('label').text(textoISV2 + ':');
        }

        if ($('#preview-isv15').length) {
            $('#preview-isv15').closest('.d-flex').find('span').text(textoISV1);
        }

        if ($('#preview-isv18').length) {
            $('#preview-isv18').closest('.d-flex').find('span').text(textoISV2);
        }
    }

    $(() => {
        setTimeout(function() {
            actualizarLabelsISVCotizacion();
        }, 300);
    });

    function limpiarISVMontosQuoteRow(row_index) {
        if ($('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).length) {
            $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val('0.00');
        }

        if ($('#quoteForm #QuoteItem #valorQuote_isv1_' + row_index).length) {
            $('#quoteForm #QuoteItem #valorQuote_isv1_' + row_index).val('0.00');
        }
    }

    function setISVFlagsQuoteRow(row_index, impuesto_venta, isv1, isv2) {
        impuesto_venta = parseInt(impuesto_venta || 0, 10);
        isv1 = parseInt(isv1 || 0, 10) === 1 ? 1 : 0;
        isv2 = parseInt(isv2 || 0, 10) === 1 ? 1 : 0;

        if (impuesto_venta !== 1) {
            isv1 = 0;
            isv2 = 0;
        }

        if (impuesto_venta === 1 && isv1 === 0 && isv2 === 0) {
            isv1 = 1;
        }

        if (isv1 === 1 && isv2 === 1) {
            isv2 = 0;
        }

        $('#quoteForm #QuoteItem #isvQuote_' + row_index).val(impuesto_venta);
        $('#quoteForm #QuoteItem #isv1_flagQuote_' + row_index).val(isv1);
        $('#quoteForm #QuoteItem #isv2_flagQuote_' + row_index).val(isv2);
    }

    function setISVQuoteDesdeProducto(row_index, producto) {
        producto = producto || {};

        var impuesto_venta = producto.impuesto_venta;

        if (impuesto_venta === undefined || impuesto_venta === null || impuesto_venta === '') {
            impuesto_venta = producto.isv_venta;
        }

        if (impuesto_venta === undefined || impuesto_venta === null || impuesto_venta === '') {
            impuesto_venta = producto.isv;
        }

        impuesto_venta = parseInt(impuesto_venta || 0, 10);

        var isv1 = parseInt(producto.isv1 || producto.isv_1 || producto.isv15 || producto.isv_15 || 0, 10) === 1 ? 1 : 0;
        var isv2 = parseInt(producto.isv2 || producto.isv_2 || producto.isv18 || producto.isv_18 || 0, 10) === 1 ? 1 : 0;

        /*
           Respaldo visual:
           Si el JSON trae alguna descripción como "ISV 18%", "Sí 18%" o similar,
           se detecta para marcar el ISV correcto aunque el campo venga con otro nombre.
        */
        var textoISV = [
            producto.isv_venta_texto,
            producto.isv_venta_nombre,
            producto.tipo_isv,
            producto.nombre_isv,
            producto.impuesto,
            producto.impuesto_nombre,
            producto.descripcion_isv,
            producto.isv_texto,
            producto.isv_label
        ].join(' ').toLowerCase();

        if (textoISV.indexOf('18') !== -1) {
            isv1 = 0;
            isv2 = 1;
        } else if (textoISV.indexOf('15') !== -1) {
            isv1 = 1;
            isv2 = 0;
        }

        setISVFlagsQuoteRow(row_index, impuesto_venta, isv1, isv2);
        limpiarISVMontosQuoteRow(row_index);
    }

    function recalcularISVQuoteRow(row_index) {
        row_index = parseInt(row_index, 10);

        if (isNaN(row_index) || row_index < 0) {
            return;
        }

        if ($('#quoteForm #QuoteItem #priceQuote_' + row_index).length === 0) {
            return;
        }

        var grava = parseInt($('#quoteForm #QuoteItem #isvQuote_' + row_index).val() || 0, 10);
        var flag1 = parseInt($('#quoteForm #QuoteItem #isv1_flagQuote_' + row_index).val() || 0, 10) === 1;
        var flag2 = parseInt($('#quoteForm #QuoteItem #isv2_flagQuote_' + row_index).val() || 0, 10) === 1;

        if (grava !== 1) {
            flag1 = false;
            flag2 = false;
            $('#quoteForm #QuoteItem #isv1_flagQuote_' + row_index).val(0);
            $('#quoteForm #QuoteItem #isv2_flagQuote_' + row_index).val(0);
        }

        if (grava === 1 && !flag1 && !flag2) {
            flag1 = true;
            $('#quoteForm #QuoteItem #isv1_flagQuote_' + row_index).val(1);
        }

        if (flag1 && flag2) {
            flag2 = false;
            $('#quoteForm #QuoteItem #isv2_flagQuote_' + row_index).val(0);
        }

        var cantidad = normalizarNumeroQuote($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());
        var precio = normalizarNumeroQuote($('#quoteForm #QuoteItem #priceQuote_' + row_index).val());
        var descuento = normalizarNumeroQuote($('#quoteForm #QuoteItem #discountQuote_' + row_index).val());

        if (cantidad <= 0) {
            cantidad = 1;
        }

        var base = (precio * cantidad) - descuento;

        if (base < 0) {
            base = 0;
        }

        var valorISV1 = 0;
        var valorISV2 = 0;

        if (grava === 1 && flag1) {
            valorISV1 = base * (obtenerPorcentajeISVCotizacionPorId(1) / 100);
        }

        if (grava === 1 && flag2) {
            valorISV2 = base * (obtenerPorcentajeISVCotizacionPorId(2) / 100);
        }

        $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(valorISV1.toFixed(2));
        $('#quoteForm #QuoteItem #valorQuote_isv1_' + row_index).val(valorISV2.toFixed(2));
    }

    function recalcularISVCotizacionActual(row_index) {
        if (row_index !== null && row_index !== undefined && row_index !== '') {
            recalcularISVQuoteRow(row_index);
            return;
        }

        $("[id^='priceQuote_']").each(function() {
            var id = $(this).attr('id').replace('priceQuote_', '');
            recalcularISVQuoteRow(id);
        });
    }

    function recalcularTodosISVCotizacion() {
        $("[id^='priceQuote_']").each(function() {
            var id = $(this).attr('id').replace('priceQuote_', '');
            recalcularISVQuoteRow(id);
        });
    }

    /* =========================================================
       FIN - HELPERS ISV COTIZACIÓN 15% / 18%
       ========================================================= */

    //INICIO COTIZACION
    //INICIO CAMBIAR PRECIO A PRODUCTO EN FACTURACION
    $(() => {
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

            if (e.which === 114) { //TECLA F3 - Descuento en cotización
                var row_index = $(this).closest("tr").index();

                abrirDescuentoCotizacion(row_index);

                e.preventDefault();
            }

            if (e.which === 115) { // TECLA F4 - Modificar precio en cotización
                var row_index = $(this).closest("tr").index();

                abrirEditarPrecioCotizacion(row_index);

                e.preventDefault();
            }
        });
    });

    /* =========================================================
       INICIO - ANULAR BOTÓN VIEJO DE MODIFICAR PRECIO COTIZACIÓN
       ========================================================= */
    $("#reg_modificar_precio_quote").off("click");
    /* =========================================================
       FIN - ANULAR BOTÓN VIEJO DE MODIFICAR PRECIO COTIZACIÓN
       ========================================================= */

    /* =========================================================
       DESCUENTO VIEJO DE COTIZACIÓN ANULADO
       Ahora se usa el modal uniforme #modalDescuentoFacturacion
       con la función abrirDescuentoCotizacion().
       ========================================================= */
    $("#reg_DescuentoQuote").off("click");


    //INICIO BUSQUEDA PRODUCTOS COTIZACION
    $(() => {
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
        cotizacionAplicarEstilosProductosBusqueda();
        cotizacionConstruirHeaderProductosBusqueda();

        var bodega = $("#formulario_busqueda_productos_facturacion #almacen").val() === "" ? 1 : $("#formulario_busqueda_productos_facturacion #almacen").val();

        var table_productos_cotizacion_buscar = $("#DatatableProductosBusquedaCotizacion").DataTable({
            "destroy": true,
            "processing": true,
            "deferRender": true,
            "ajax": {
                "method": "POST",
                "url": "<?php echo SERVERURL; ?>core/llenarDataTableProductosCotizacion.php",
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
                    "orderable": false,
                    "render": function(data, type, row, meta) {
                        var defaultImageUrl = '<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png';
                        var imageUrl = data ? '<?php echo SERVERURL; ?>vistas/plantilla/img/products/' + data : defaultImageUrl;
                        var safeTitle = (row && row.nombre) ? String(row.nombre).replace(/"/g,'&quot;') : 'Imagen';

                        var imgHtml = '<img class="table-image mr-2" src="' + imageUrl + '" alt="' + safeTitle + '" style="cursor:pointer;">';

                        var btnHtml =
                            '<button type="button" class="btn btn-light btn-icon btn-xs btn-zoom iv-trigger"' +
                            ' data-iv-src="' + imageUrl + '"' +
                            ' data-iv-fallback="' + defaultImageUrl + '"' +
                            ' data-iv-title="' + safeTitle + '"' +
                            ' title="Ver imagen grande">' +
                            '<i class="fas fa-search-plus"></i>' +
                            '</button>';

                        return '<div class="d-flex align-items-center">' + imgHtml + btnHtml +
                            '</div>';
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
                    "render": function(data, type) {
                        return cotizacionRenderBadgeSaldoProducto(data, type);
                    }
                },
                {
                    "data": "medida"
                },
                {
                    "data": "tipo_producto_nombre"
                },
                {
                    "data": "precio_venta",
                    "render": function(data, type) {
                        return cotizacionRenderPrecioProductoNormal(data, type);
                    }
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        if (row.almacen === null || row.almacen === "" || row.almacen === undefined) {
                            return "Sin bodega";
                        }

                        return row.almacen;
                    }
                }
            ],
            "lengthMenu": lengthMenu,
            "stateSave": true,
            "bDestroy": true,
            "responsive": true,
            "language": idioma_español,
            "dom": dom,
            "columnDefs": [
                {
                    width: "2%",
                    targets: 0
                },
                {
                    width: "14%",
                    targets: 1
                },
                {
                    width: "14%",
                    targets: 2
                },
                {
                    width: "18%",
                    targets: 3
                },
                {
                    width: "12%",
                    targets: 4,
                    className: "text-center"
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
                    width: "10%",
                    targets: 7,
                    className: "text-right"
                },
                {
                    width: "12%",
                    targets: 8
                }
            ],
            "buttons": [
                {
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

        view_productos_busqueda_cotizacion_dataTable("#DatatableProductosBusquedaCotizacion tbody", table_productos_cotizacion_buscar);
    }

    var view_productos_busqueda_cotizacion_dataTable = function(tbody, table) { //resp1
        $(tbody).off("click", "button.table_view, td img");
        $(tbody).on("click", "button.table_view, td img", function(e) {
            e.preventDefault();

            if ($("#quoteForm #cliente_id").val() != "" && $("#quoteForm #cliente").val() != "" && $("#quoteForm #colaborador_id").val() != "" && $("#quoteForm #colaborador").val() != "") {
                var data = table.row($(this).parents("tr")).data();

                $('#quoteForm #QuoteItem #productosQuote_id_' + row).val(data.productos_id);
                $('#quoteForm #QuoteItem #bar-code-id_' + row).val(data.barCode);
                $('#quoteForm #QuoteItem #productNameQuote_' + row).val(data.nombre);
                $('#quoteForm #QuoteItem #quantityQuote_' + row).val(1);
                $('#quoteForm #QuoteItem #quantityQuote_' + row).focus();
                $('#quoteForm #QuoteItem #priceQuote_' + row).val(data.precio_venta);
                $('#quoteForm #QuoteItem #discountQuote_' + row).val(0);
                $('#quoteForm #QuoteItem #isvQuote_' + row).val(data.impuesto_venta);
                setISVQuoteDesdeProducto(row, data);
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
                    if ($('#quoteForm #taxAmountQuote').val() == "" || $('#quoteForm #taxAmountQuote').val() == 0) {
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
                recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
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
                "url": "<?php echo SERVERURL; ?>core/llenarDataTableClientes.php"
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

        view_clientes_busqueda_cotizacion_dataTable("#DatatableClientesBusquedaFactura tbody", table_clientes_cotizacion_buscar);
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
                "url": "<?php echo SERVERURL; ?>core/llenarDataTableColaboradores.php"
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

        view_colaboradores_busqueda_cotizacion_dataTable("#DatatableColaboradoresBusquedaFactura tbody", table_colaboradores_buscar_cotizacion);
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
    $(() => {
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

    $(() => {
        $("#quoteForm #QuoteItem").on('blur', '.buscar_cantidad', function() {
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();

            var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());
            var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());

            //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS
            if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 &&
                parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val()) != 0) {

                //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO
                if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {
                    $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());
                } else {
                    $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
                }
            } else {
                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
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
                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv).toFixed(2);
                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                } else {
                    isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv).toFixed(2);
                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                }
            }

            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });
    });

    $(() => {
        $("#quoteForm #QuoteItem").on('keyup', '.buscar_cantidad', function() {
            var row_index = $(this).closest("tr").index();
            var col_index = $(this).closest("td").index();
            var impuesto_venta = parseFloat($('#quoteForm #QuoteItem #isvQuote_' + row_index).val());
            var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val());

            //EVALUAMOS ANTES QUE LA CANTIDAD DE MAYOREO Y EL PRECIO DE MAYOREO NO ESTEN VACIOS
            if (parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val()) != 0 && parseFloat($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val()) != 0) {
                //SI LA CANTIDAD A VENDER ES MAYOR O IGUAL A LA CANTIDAD DE MAYOREO PERMITIDA, SE CAMBIA EL PRECIO POR EL PRECIO DE MAYOREO

                if (parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) >= parseFloat($('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row_index).val())) {
                    $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row_index).val());
                } else {
                    $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
                }
            } else {
                $('#quoteForm #QuoteItem #priceQuote_' + row_index).val($('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val());
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
                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv).toFixed(2);
                    isv_neto = parseFloat(porcentaje_calculo).toFixed(2);
                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                } else {
                    isv_total = parseFloat($('#quoteForm #taxAmountQuote').val());
                    porcentaje_calculo = (parseFloat(precio) * parseFloat(cantidad) * porcentaje_isv).toFixed(2);

                    isv_neto = parseFloat(isv_total) + parseFloat(porcentaje_calculo);

                    $('#quoteForm #QuoteItem #valorQuote_isv_' + row_index).val(porcentaje_calculo);
                }
            }

            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });
    });

    // INICIO DETALLES COTIZACION
    function generarFilaCotizacion(count) {
        let htmlRow = '<tr>';
        htmlRow += '<td><input class="itemRowQuote" type="checkbox"></td>';

        // Columna CÓDIGO + hidden inputs
        htmlRow += '<td>';
        htmlRow += '  <input type="hidden" name="referenciaProductoQuote[]" id="referenciaProductoQuote_' + count + '" class="form-control" placeholder="Referencia Producto Precio" autocomplete="off">';
        htmlRow += '  <input type="hidden" name="isvQuote[]" id="isvQuote_' + count + '" class="form-control" placeholder="Producto ISV" autocomplete="off">';

        // ISV id=1 (ej. 15%)
        htmlRow += '  <input type="hidden" name="valorQuote_isv[]" id="valorQuote_isv_' + count + '" class="form-control" placeholder="Valor ISV (id=1)" autocomplete="off">';
        // ISV id=2 (ej. 18%)
        htmlRow += '  <input type="hidden" name="valorQuote_isv1[]" id="valorQuote_isv1_' + count + '" class="form-control" placeholder="Valor ISV2 (id=2)" autocomplete="off">';
        // Flags por fila (qué ISV aplica aquí)
        htmlRow += '  <input type="hidden" name="isv1_flagQuote[]" id="isv1_flagQuote_' + count + '" value="0">';
        htmlRow += '  <input type="hidden" name="isv2_flagQuote[]" id="isv2_flagQuote_' + count + '" value="0">';

        htmlRow += '  <input type="hidden" name="productosQuote_id[]" id="productosQuote_id_' + count + '" class="form-control inputfield-details1" placeholder="Código del Producto" autocomplete="off">';

        htmlRow += '  <div class="input-group mb-3">';
        htmlRow += '    <div class="input-group-prepend">';
        htmlRow += '      <button type="button" data-toggle="modal" class="btn btn-link buscar_productos_quote p-0" data-toggle="tooltip" data-placement="top" title="Búsqueda de Productos" id="icon-search-bar_' + count + '">';
        htmlRow += '        <i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i>';
        htmlRow += '      </button>';
        htmlRow += '    </div>';
        htmlRow += '    <input type="text" name="bar-code-id[]" id="bar-code-id_' + count + '" class="form-control product-bar-code inputfield-details1" placeholder="Código del Producto" autocomplete="off">';
        htmlRow += '  </div>';
        htmlRow += '</td>';

        // Columna DESCRIPCIÓN (span visible + input hidden)
        htmlRow += '<td>';
        htmlRow += '  <input type="hidden" name="productNameQuote[]" id="productNameQuote_' + count + '" placeholder="Descripción del Producto" readonly class="form-control inputfield-details1" autocomplete="off">';
        htmlRow += '  <span id="productNameQuote_text_' + count + '" class="product-description">Descripción del Producto</span>';
        htmlRow += '</td>';

        // Columna CANTIDAD
        htmlRow += '<td>';
        htmlRow += '  <input type="number" name="quantityQuote[]" id="quantityQuote_' + count + '" step="0.01" placeholder="Cantidad" class="buscar_cantidad form-control inputfield-details" autocomplete="off">';
        htmlRow += '  <input type="hidden" name="cantidad_mayoreoQuote[]" id="cantidad_mayoreoQuote_' + count + '" step="0.01" placeholder="Cantidad Mayoreo" class="buscar_cantidad form-control inputfield-details" autocomplete="off">';
        htmlRow += '</td>';

        // Columna PRECIO
        htmlRow += '<td>';
        htmlRow += '  <div class="input-group mb-3">';
        htmlRow += '    <input type="number" name="priceQuote[]" id="priceQuote_' + count + '" class="form-control" step="0.01" placeholder="Precio" readonly autocomplete="off">';
        htmlRow += '    <div id="suggestions_producto_' + count + '" class="suggestions"></div>';
        htmlRow += '    <div class="input-group-append">';
        htmlRow += '      <a data-toggle="modal" href="#" class="btn btn-outline-success">';
        htmlRow += '        <div class="sb-nav-link-icon"></div><i class="aplicar_precio_cotizacion fas fa-plus fa-lg"></i>';
        htmlRow += '      </a>';
        htmlRow += '    </div>';
        htmlRow += '  </div>';
        htmlRow += '  <input type="hidden" name="precio_mayoreoQuote[]" id="precio_mayoreoQuote_' + count + '" step="0.01" placeholder="Precio Mayoreo" class="form-control inputfield-details" readonly autocomplete="off">';
        htmlRow += '  <input type="hidden" name="precio_realQuote[]" id="precio_realQuote_' + count + '" placeholder="Precio Real" class="form-control inputfield-details" readonly autocomplete="off">';
        htmlRow += '</td>';

        // Columna DESCUENTO
        htmlRow += '<td>';
        htmlRow += '  <div class="input-group mb-3">';
        htmlRow += '    <input type="number" name="discountQuote[]" id="discountQuote_' + count + '" class="form-control" step="0.01" placeholder="Descuento" readonly autocomplete="off">';
        htmlRow += '    <div id="suggestions_producto_' + count + '" class="suggestions"></div>';
        htmlRow += '    <div class="input-group-append">';
        htmlRow += '      <a data-toggle="modal" href="#" class="btn btn-outline-success">';
        htmlRow += '        <div class="sb-nav-link-icon"></div><i class="aplicar_descuento_cotizacion fas fa-plus fa-lg"></i>';
        htmlRow += '      </a>';
        htmlRow += '    </div>';
        htmlRow += '  </div>';
        htmlRow += '</td>';

        // Columna TOTAL
        htmlRow += '<td><input type="number" name="totalQuote[]" id="totalQuote_' + count + '" placeholder="Total" class="form-control total inputfield-details" readonly autocomplete="off" step="0.01"></td>';

        htmlRow += '</tr>';
        return htmlRow;
    }
    // FIN DETALLES COTIZACION

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

    $(() => {
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
                recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
            } else {
                showNotify('error', 'Error', 'Lo sentimos debe seleccionar un fila antes de intentar eliminarla');
            }
        });

        $(document).on('blur', "[id^=quantityQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('keyup', "[id^=quantityQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('blur', "[id^=priceQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('keyup', "[id^=priceQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('blur', "[id^=discountQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('keyup', "[id^=discountQuote_]", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
        });

        $(document).on('blur', "#taxRateQuote", function() {
            recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
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

    function setValorCotizacion(selector, valor) {
        var valorFormateado = parseFloat(normalizarNumeroQuote(valor)).toFixed(2);

        $(selector).each(function() {
            $(this).val(valorFormateado);
            $(this).attr('value', valorFormateado);

            if ($(this).is('textarea')) {
                $(this).text(valorFormateado);
            }
        });
    }

    function calculateTotalQuote() {
        if (typeof actualizarLabelsISVCotizacion === 'function') {
            actualizarLabelsISVCotizacion();
        }

        var totalAmount = 0;
        var totalDiscount = 0;
        var totalISV15 = 0;
        var totalISV18 = 0;
        var totalGeneral = 0;

        $('#quoteForm #QuoteItem [id^="priceQuote_"]').each(function() {
            var id = $(this).attr('id').replace('priceQuote_', '');

            var productos_id = $('#quoteForm #QuoteItem #productosQuote_id_' + id).val();
            var price = normalizarNumeroQuote($('#quoteForm #QuoteItem #priceQuote_' + id).val());
            var discount = normalizarNumeroQuote($('#quoteForm #QuoteItem #discountQuote_' + id).val());
            var quantity = normalizarNumeroQuote($('#quoteForm #QuoteItem #quantityQuote_' + id).val());

            if (!productos_id) {
                $('#quoteForm #QuoteItem #totalQuote_' + id).val('0.00');
                $('#quoteForm #QuoteItem #valorQuote_isv_' + id).val('0.00');
                $('#quoteForm #QuoteItem #valorQuote_isv1_' + id).val('0.00');
                return;
            }

            if (!quantity || quantity <= 0) {
                quantity = 1;
            }

            recalcularISVQuoteRow(id);

            var isv15Linea = normalizarNumeroQuote($('#quoteForm #QuoteItem #valorQuote_isv_' + id).val());
            var isv18Linea = normalizarNumeroQuote($('#quoteForm #QuoteItem #valorQuote_isv1_' + id).val());
            var total = price * quantity;

            $('#quoteForm #QuoteItem #totalQuote_' + id).val(parseFloat(total).toFixed(2));

            totalAmount += total;
            totalGeneral += total;
            totalISV15 += isv15Linea;
            totalISV18 += isv18Linea;
            totalDiscount += discount;
        });

        var totalDespuesImpuesto = (parseFloat(totalAmount) + parseFloat(totalISV15) + parseFloat(totalISV18)) - parseFloat(totalDiscount);

        /*
          IMPORTANTE:
          En tu footer visible el ISV 18% se llama:
          id="taxAmountQuoteFooter18"
          name="taxAmountQuoteFooter18"

          Antes el JS estaba intentando llenar:
          #taxAmountQuote18Footer

          Por eso el cálculo podía existir, pero el campo visible quedaba en 0.00.
        */

        setValorCotizacion('#subTotalQuote, #subTotalQuoteFooter, [name="subTotalQuote"], [name="subTotalQuoteFooter"]', totalAmount);
        setValorCotizacion('#taxDescuentoQuote, #taxDescuentoFooter, [name="taxDescuentoQuote"], [name="taxDescuentoFooter"]', totalDiscount);
        setValorCotizacion('#subTotalImporteQuote, [name="subTotalImporteQuote"]', totalGeneral);

        setValorCotizacion('#taxAmountQuote, #taxAmountQuoteFooter, [name="taxAmountQuote"], [name="taxAmountQuoteFooter"]', totalISV15);

        setValorCotizacion(
            '#taxAmountQuote18, #taxAmountQuoteFooter18, #taxAmountQuote18Footer, [name="taxAmountQuote18"], [name="taxAmountQuoteFooter18"], [name="taxAmountQuote18Footer"]',
            totalISV18
        );

        setValorCotizacion('#totalAftertaxQuote, #totalAftertaxQuoteFooter, [name="totalAftertaxQuote"], [name="totalAftertaxQuoteFooter"]', totalDespuesImpuesto);
    }

    function cleanFooterValueQuote() {
        $('#subTotalQuoteFooter').val("");
        $('#taxAmountQuoteFooter').val("");
        $('#taxAmountQuoteFooter18').val("");
        $('#totalAftertaxQuoteFooter').val("");
    }

    //FIN CALCULO DETALLES COTIZACION

    //FIN COTIZACION

    $(() => {
        $("#modalDescuentoFacturacion").on('shown.bs.modal', function() {
            $(this).find('#formDescuentoFacturacion #porcentaje_descuento_fact').focus();
        });

        $("#modalModificarPrecioCotizaciones").on('shown.bs.modal', function() {
            $(this).find('#formModificarPrecioCotizaciones #referencia_modificar_precio_fact').focus();
        });

        $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function() {
            $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
        });  
        
        $("#modal_buscar_colaboradores_facturacion").on('shown.bs.modal', function() {
            $(this).find('#formulario_busqueda_colaboradores_facturacion #buscar').focus();
        });        
    });


    function getVigencia() {
        var url = '<?php echo SERVERURL; ?>core/getVigencia.php';

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

    // INICIO DESCUENTO PRODUCTO EN COTIZACION CON MODAL UNIFORME DE FACTURACIÓN
    function abrirDescuentoCotizacion(row_index) {
        row_index = parseInt(row_index, 10);

        if (Number.isNaN(row_index) || row_index < 0) {
            showNotify('error', 'Error', 'No se pudo identificar la línea del producto.');
            return;
        }

        if ($('#formDescuentoFacturacion').length === 0 || $('#modalDescuentoFacturacion').length === 0) {
            showNotify('error', 'Error', 'No se encontró el modal de descuento de facturación en esta vista.');
            return;
        }

        if ($('#quoteForm #cliente_id').val() == '' || $('#quoteForm #cliente').val() == '') {
            showNotify('error', 'Error', 'Debe seleccionar un cliente antes de continuar.');
            return;
        }

        if ($('#quoteForm #QuoteItem #productosQuote_id_' + row_index).val() == '') {
            showNotify('error', 'Error', 'Debe seleccionar un producto antes de aplicar descuento.');
            return;
        }

        $('#formDescuentoFacturacion')[0].reset();

        var col_index = $('#quoteForm #QuoteItem #discountQuote_' + row_index).closest('td').index();
        var productos_id = $('#quoteForm #QuoteItem #productosQuote_id_' + row_index).val();
        var producto = $('#quoteForm #QuoteItem #productNameQuote_' + row_index).val();
        var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val()) || 0;
        var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) || 1;
        var descuentoActual = parseFloat($('#quoteForm #QuoteItem #discountQuote_' + row_index).val()) || 0;
        var total = precio * cantidad;

        $('#formDescuentoFacturacion #descuento_productos_id').val(productos_id);
        $('#formDescuentoFacturacion #row_index').val(row_index);
        $('#formDescuentoFacturacion #col_index').val(col_index);
        $('#formDescuentoFacturacion #pro_descuento_fact').val('Aplicar Descuento');
        $('#formDescuentoFacturacion #producto_descuento_fact').val(producto);
        $('#formDescuentoFacturacion #precio_descuento_fact').val(total.toFixed(2));
        $('#formDescuentoFacturacion #descuento_fact').val(descuentoActual.toFixed(2));

        if (total > 0 && descuentoActual > 0) {
            $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(((descuentoActual / total) * 100).toFixed(2));
        } else {
            $('#formDescuentoFacturacion #porcentaje_descuento_fact').val('0.00');
        }

        $('#modalDescuentoFacturacion').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }

    $(() => {
        $('#modal_buscar_productos_cotizacion').on('shown.bs.modal', function() {
            $(this).find('#formulario_busqueda_productos_cotizacion #buscar').focus();
        });

        $('#modalDescuentoFacturacion').on('shown.bs.modal', function() {
            $(this).find('#formDescuentoFacturacion #porcentaje_descuento_fact').focus().select();
        });

        $('#quoteForm #QuoteItem').off('click.descuentoCotizacion', '.aplicar_descuento_cotizacion');
        $('#quoteForm #QuoteItem').on('click.descuentoCotizacion', '.aplicar_descuento_cotizacion', function(e) {
            e.preventDefault();
            var row_index = $(this).closest('tr').index();
            abrirDescuentoCotizacion(row_index);
        });

        $('#formDescuentoFacturacion #porcentaje_descuento_fact').off('keyup.descuentoCotizacion input.descuentoCotizacion');
        $('#formDescuentoFacturacion #porcentaje_descuento_fact').on('keyup.descuentoCotizacion input.descuentoCotizacion', function() {
            var total = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val()) || 0;
            var porcentaje = parseFloat($(this).val()) || 0;
            var descuento = total * (porcentaje / 100);

            $('#formDescuentoFacturacion #descuento_fact').val(descuento.toFixed(2));
        });

        $('#formDescuentoFacturacion #descuento_fact').off('keyup.descuentoCotizacion input.descuentoCotizacion');
        $('#formDescuentoFacturacion #descuento_fact').on('keyup.descuentoCotizacion input.descuentoCotizacion', function() {
            var total = parseFloat($('#formDescuentoFacturacion #precio_descuento_fact').val()) || 0;
            var descuento = parseFloat($(this).val()) || 0;
            var porcentaje = total > 0 ? (descuento / total) * 100 : 0;

            $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(porcentaje.toFixed(2));
        });
    });

    $('#reg_DescuentoFacturacion').off('click.descuentoCotizacion');
    $('#reg_DescuentoFacturacion').on('click.descuentoCotizacion', function(e) {
        e.preventDefault();

        var row_index = $('#formDescuentoFacturacion #row_index').val();
        var descuento = parseFloat($('#formDescuentoFacturacion #descuento_fact').val()) || 0;
        var precio = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val()) || 0;
        var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) || 1;
        var impuesto_venta = $('#quoteForm #QuoteItem #isvQuote_' + row_index).val();

        var total_sin_descuento = precio * cantidad;
        var total_con_descuento = total_sin_descuento - descuento;

        if (descuento < 0) {
            showNotify('warning', 'Advertencia', 'El descuento no puede ser negativo.');
            $('#formDescuentoFacturacion #descuento_fact').focus().select();
            return;
        }

        if (total_con_descuento < 0) {
            showNotify('warning', 'Advertencia', 'El valor del descuento es mayor al precio total del artículo, por favor corregir');
            $('#formDescuentoFacturacion #descuento_fact').focus().select();
            return;
        }

        $('#quoteForm #QuoteItem #discountQuote_' + row_index).val(descuento.toFixed(2));

        recalcularISVQuoteRow(row_index);

        $('#modalDescuentoFacturacion').modal('hide');
        recalcularISVCotizacionActual(typeof row_index !== 'undefined' ? row_index : (typeof row !== 'undefined' ? row : null));
                calculateTotalQuote();
    });
    // FIN DESCUENTO PRODUCTO EN COTIZACION CON MODAL UNIFORME DE FACTURACIÓN

    //INICIO MODIFICAR PRECIO EN PRODUCTO COTIZACIONES
    $(() => {
        $("#quoteForm #QuoteItem").off('click.precioCotizacion', '.aplicar_precio_cotizacion');
        $("#quoteForm #QuoteItem").on('click.precioCotizacion', '.aplicar_precio_cotizacion', function(e) {
            e.preventDefault();

            var row_index = $(this).closest("tr").index();

            abrirEditarPrecioCotizacion(row_index);
        });
    });
    //FIN MODIFICAR PRECIO EN PRODUCTO COTIZACIONES
/* =========================================================
   INICIO - EDITAR PRECIO EN COTIZACIÓN
   Usa el mismo modal público de facturación:
   #editarPrecioModal
   ========================================================= */

function asegurarPreviewPrecioCotizacion() {
    var $preview = $('#precio-total-preview');

    if ($preview.length && $preview.is('input')) {
        $preview.replaceWith(
            '<div id="precio-total-preview" class="alert alert-light border mb-0" style="line-height:1.6;">' +
                '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
                '<div class="d-flex justify-content-between"><span>ISV</span><strong id="preview-isv15">L. 0.00</strong></div>' +
                '<div class="d-flex justify-content-between"><span>ISV</span><strong id="preview-isv18">L. 0.00</strong></div>' +
                '<hr class="my-2">' +
                '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>' +
            '</div>'
        );
    } else if ($preview.length && !$('#preview-subtotal').length) {
        $preview.html(
            '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
            '<div class="d-flex justify-content-between"><span>ISV</span><strong id="preview-isv15">L. 0.00</strong></div>' +
            '<div class="d-flex justify-content-between"><span>ISV</span><strong id="preview-isv18">L. 0.00</strong></div>' +
            '<hr class="my-2">' +
            '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>'
        );
    }
}

function formatoPreviewPrecioCotizacion(valor) {
    valor = parseFloat(valor || 0);

    return valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function setPreviewPrecioCotizacion(subtotal, isv15, isv18, total) {
    asegurarPreviewPrecioCotizacion();

    $('#preview-subtotal').text('L. ' + formatoPreviewPrecioCotizacion(subtotal));
    $('#preview-isv15').text('L. ' + formatoPreviewPrecioCotizacion(isv15));
    $('#preview-isv18').text('L. ' + formatoPreviewPrecioCotizacion(isv18));
    $('#preview-total').text('L. ' + formatoPreviewPrecioCotizacion(total));

    if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
        $('#precio-total-preview').val(
            'Subtotal: L. ' + formatoPreviewPrecioCotizacion(subtotal) +
            ' | ISV id=1: L. ' + formatoPreviewPrecioCotizacion(isv15) +
            ' | ISV id=2: L. ' + formatoPreviewPrecioCotizacion(isv18) +
            ' | Total: L. ' + formatoPreviewPrecioCotizacion(total)
        );
    }
}

function limpiarPreviewPrecioCotizacion() {
    asegurarPreviewPrecioCotizacion();

    $('#preview-subtotal').text('L. 0.00');
    $('#preview-isv15').text('L. 0.00');
    $('#preview-isv18').text('L. 0.00');
    $('#preview-total').text('L. 0.00');

    if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
        $('#precio-total-preview').val('');
    }
}

function obtenerPorcentajeISVCotizacion(isvId) {
    return obtenerPorcentajeISVCotizacionPorId(isvId);
}

function abrirEditarPrecioCotizacion(row_index) {
    row_index = parseInt(row_index, 10);

    if (Number.isNaN(row_index) || row_index < 0) {
        showNotify('error', 'Error', 'No se pudo identificar la línea del producto.');
        return;
    }

    var productos_id = $('#quoteForm #QuoteItem #productosQuote_id_' + row_index).val();
    var producto = $('#quoteForm #QuoteItem #productNameQuote_' + row_index).val();
    var precioActual = parseFloat($('#quoteForm #QuoteItem #priceQuote_' + row_index).val()) || 0;

    if (!productos_id || productos_id === '0') {
        showNotify('error', 'Error', 'Debe seleccionar un producto antes de modificar el precio.');
        return;
    }

    $('#producto-precio-index').val(row_index);
    $('#nuevo-precio-producto').val(precioActual > 0 ? precioActual.toFixed(2) : '');

    if ($('#editarPrecioModalLabel').length) {
        $('#editarPrecioModalLabel').html('<i class="fas fa-dollar-sign"></i> Editar Precio');
    }

    actualizarVistaNuevoPrecioCotizacion();

    $('#editarPrecioModal').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function actualizarVistaNuevoPrecioCotizacion() {
    var row_index = parseInt($('#producto-precio-index').val(), 10);
    var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

    if (Number.isNaN(row_index) || row_index < 0 || nuevoPrecio <= 0) {
        limpiarPreviewPrecioCotizacion();
        return;
    }

    var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) || 1;
    var descuento = parseFloat($('#quoteForm #QuoteItem #discountQuote_' + row_index).val()) || 0;
    var impuestoVenta = parseInt($('#quoteForm #QuoteItem #isvQuote_' + row_index).val() || 0, 10);

    var subtotal = (nuevoPrecio * cantidad) - descuento;

    if (subtotal < 0) {
        subtotal = 0;
    }

    var isv15 = 0;
    var isv18 = 0;

    var flag1 = parseInt($('#quoteForm #QuoteItem #isv1_flagQuote_' + row_index).val() || 0, 10) === 1;
    var flag2 = parseInt($('#quoteForm #QuoteItem #isv2_flagQuote_' + row_index).val() || 0, 10) === 1;

    if (impuestoVenta === 1 && flag1) {
        var porcentaje1 = obtenerPorcentajeISVCotizacionPorId(1);
        isv15 = subtotal * (porcentaje1 / 100);
    }

    if (impuestoVenta === 1 && flag2) {
        var porcentaje2 = obtenerPorcentajeISVCotizacionPorId(2);
        isv18 = subtotal * (porcentaje2 / 100);
    }

    var total = subtotal + isv15 + isv18;

    setPreviewPrecioCotizacion(subtotal, isv15, isv18, total);
}

function guardarPrecioCotizacion() {
    var row_index = parseInt($('#producto-precio-index').val(), 10);
    var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

    if (Number.isNaN(row_index) || row_index < 0) {
        showNotify('error', 'Error', 'No se pudo identificar la línea del producto.');
        return;
    }

    if (nuevoPrecio <= 0) {
        showNotify('warning', 'Advertencia', 'El precio debe ser mayor a cero.');
        $('#nuevo-precio-producto').focus();
        return;
    }

    var cantidad = parseFloat($('#quoteForm #QuoteItem #quantityQuote_' + row_index).val()) || 1;
    var descuentoActual = parseFloat($('#quoteForm #QuoteItem #discountQuote_' + row_index).val()) || 0;
    var totalLinea = nuevoPrecio * cantidad;

    if (descuentoActual > totalLinea) {
        showNotify('warning', 'Advertencia', 'El descuento actual es mayor al nuevo precio total. Ajuste primero el descuento.');
        return;
    }

    $('#quoteForm #QuoteItem #priceQuote_' + row_index).val(nuevoPrecio.toFixed(2));
    $('#quoteForm #QuoteItem #precio_realQuote_' + row_index).val(nuevoPrecio.toFixed(2));

    recalcularISVQuoteRow(row_index);
    calculateTotalQuote();

    $('#editarPrecioModal').modal('hide');

    showNotify('success', 'Éxito', 'Precio actualizado correctamente.');
}

/* Eventos del modal compartido para cotización */
$(document).off('input.editarPrecioCotizacion keyup.editarPrecioCotizacion change.editarPrecioCotizacion', '#nuevo-precio-producto');
$(document).on('input.editarPrecioCotizacion keyup.editarPrecioCotizacion change.editarPrecioCotizacion', '#nuevo-precio-producto', function () {
    if ($('#quoteForm').length && $('#quoteForm').is(':visible')) {
        actualizarVistaNuevoPrecioCotizacion();
    }
});

$(document).off('click.guardarPrecioCotizacion', '#guardar-precio');
$(document).on('click.guardarPrecioCotizacion', '#guardar-precio', function (e) {
    if ($('#quoteForm').length && $('#quoteForm').is(':visible')) {
        e.preventDefault();
        guardarPrecioCotizacion();
    }
});

$(document).off('keydown.guardarPrecioCotizacion', '#editarPrecioModal #nuevo-precio-producto');
$(document).on('keydown.guardarPrecioCotizacion', '#editarPrecioModal #nuevo-precio-producto', function (e) {
    if ($('#quoteForm').length && $('#quoteForm').is(':visible') && e.which === 13) {
        e.preventDefault();
        guardarPrecioCotizacion();
    }
});

$('#editarPrecioModal').off('shown.bs.modal.editarPrecioCotizacion');
$('#editarPrecioModal').on('shown.bs.modal.editarPrecioCotizacion', function () {
    if ($('#quoteForm').length && $('#quoteForm').is(':visible')) {
        $('#nuevo-precio-producto').trigger('focus').select();
    }
});

$('#editarPrecioModal').off('hidden.bs.modal.editarPrecioCotizacion');
$('#editarPrecioModal').on('hidden.bs.modal.editarPrecioCotizacion', function () {
    if ($('#quoteForm').length && $('#quoteForm').is(':visible')) {
        if ($('#editar-precio-form').length && $('#editar-precio-form')[0]) {
            $('#editar-precio-form')[0].reset();
        }

        limpiarPreviewPrecioCotizacion();
        $('#producto-precio-index').val('');
    }
});

/* =========================================================
   FIN - EDITAR PRECIO EN COTIZACIÓN
   ========================================================= */

    /* =========================================================
       IZZY | LISTADOS DIV PREMIUM
       Sin DataTable visible. Mantiene endpoints y acciones.
       ========================================================= */
    function izzyListEscape(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function izzyListNumber(value) {
        if (typeof value === 'string') {
            value = value.replace(/L\./g,'').replace(/L/g,'').replace(/,/g,'').replace(/<[^>]*>/g,'').trim();
        }
        value = parseFloat(value || 0);
        return isNaN(value) ? 0 : value;
    }

    function izzyListMoney(value) {
        return 'L. ' + izzyListNumber(value).toLocaleString('es-HN', {
            minimumFractionDigits:2,
            maximumFractionDigits:2
        });
    }

    function izzyListRows(response) {
        if (typeof response === 'string') {
            try { response = JSON.parse(response); } catch(e) { return []; }
        }
        if (Array.isArray(response)) return response;
        if (response && Array.isArray(response.data)) return response.data;
        if (response && Array.isArray(response.aaData)) return response.aaData;
        return [];
    }

    function izzyListSearchText(row) {
        try { return JSON.stringify(row || {}).toLowerCase(); }
        catch(e) { return ''; }
    }

    function izzyMiniField(label, value) {
        return '<div class="fm-mini-field">' +
            '<span class="fm-mini-field-label">'+izzyListEscape(label)+'</span>' +
            '<span class="fm-mini-field-value">'+value+'</span>' +
        '</div>';
    }

    function izzyEmptyList() {
        return '<div class="fm-empty"><i class="fas fa-inbox"></i><strong>Sin registros</strong><span>No hay información que coincida con los criterios actuales.</span></div>';
    }

    function izzyProductImageHtml(row) {
        var fallback = '<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png';
        var src = row && row.image
            ? '<?php echo SERVERURL; ?>vistas/plantilla/img/products/' + row.image
            : fallback;
        var title = izzyListEscape((row && row.nombre) || 'Producto');

        return '<div class="fm-product-image-wrap">' +
            '<img class="fm-product-image izzy-select-product-image" src="'+src+'" alt="'+title+'">' +
            '<button type="button" class="fm-product-zoom iv-trigger" data-iv-src="'+src+'" data-iv-fallback="'+fallback+'" data-iv-title="'+title+'" title="Ver imagen grande">' +
                '<i class="fas fa-search-plus"></i>' +
            '</button>' +
        '</div>';
    }

    function izzyApplyPermissions() {
        try {
            if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
                typeof getPrivilegioTipoUsuario === 'function') {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }
        } catch(e) {}
    }

    function izzyCreateState(cfg) {
        return $.extend({
            rows:[],
            filtered:[],
            page:1,
            pageSize:10,
            view:'detalle',
            search:'',
            grid:'1fr',
            columns:[],
            renderMini:null,
            renderDetail:null,
            onSelect:null
        }, cfg || {});
    }

    function izzyFilterState(state) {
        var q = String(state.search || '').trim().toLowerCase();
        state.filtered = !q ? state.rows.slice() : state.rows.filter(function(row) {
            return izzyListSearchText(row).indexOf(q) !== -1;
        });
        var pages = Math.max(1, Math.ceil(state.filtered.length / state.pageSize));
        if (state.page > pages) state.page = pages;
        if (state.page < 1) state.page = 1;
    }

    function izzyRenderPagination(state) {
        var pages = Math.max(1, Math.ceil(state.filtered.length / state.pageSize));
        var html = '';
        function btn(label,page,disabled,active) {
            html += '<button type="button" data-page="'+page+'" '+(disabled?'disabled':'')+' class="'+(active?'active':'')+'">'+label+'</button>';
        }
        btn('<i class="fas fa-angle-double-left"></i> Inicio',1,state.page===1,false);
        btn('<i class="fas fa-angle-left"></i> Anterior',state.page-1,state.page===1,false);
        var from = Math.max(1,state.page-2);
        var to = Math.min(pages,from+4);
        from = Math.max(1,to-4);
        for(var p=from;p<=to;p++) btn(String(p),p,false,p===state.page);
        btn('Siguiente <i class="fas fa-angle-right"></i>',state.page+1,state.page===pages,false);
        btn('Final <i class="fas fa-angle-double-right"></i>',pages,state.page===pages,false);

        $(state.pagination).html(html)
            .off('click.izzyList','button[data-page]')
            .on('click.izzyList','button[data-page]',function(){
                if(this.disabled || $(this).hasClass('active')) return;
                state.page = parseInt($(this).attr('data-page'),10) || 1;
                izzyRenderState(state);
            });
    }

    function izzyRenderState(state) {
        izzyFilterState(state);

        var start = (state.page-1) * state.pageSize;
        var pageRows = state.filtered.slice(start,start+state.pageSize);
        var html = '';

        if (!pageRows.length) {
            html = izzyEmptyList();
        } else if (state.view === 'miniatura') {
            html = pageRows.map(function(row,idx){
                return state.renderMini(row,start+idx);
            }).join('');
        } else {
            html = '<div class="fm-detail-header" style="grid-template-columns:'+state.grid+'">' +
                state.columns.map(function(c){return '<div class="fm-cell '+(c.cls||'')+'">'+izzyListEscape(c.label)+'</div>';}).join('') +
            '</div>' +
            pageRows.map(function(row,idx){
                return state.renderDetail(row,start+idx);
            }).join('');
        }

        $(state.list).toggleClass('fm-view-miniatura',state.view==='miniatura').html(html);
        $(state.info).text(state.filtered.length
            ? 'Mostrando '+(start+1)+' a '+Math.min(start+pageRows.length,state.filtered.length)+' de '+state.filtered.length+' registros'
            : '0 registros');

        izzyRenderPagination(state);
        izzyApplyPermissions();

        $(state.modal).find('.fm-view-btn')
            .removeClass('active')
            .filter('[data-view="'+state.view+'"]').addClass('active');
    }

    function izzyBindState(state) {
        $(document)
            .off('input.izzyList',state.searchInput)
            .on('input.izzyList',state.searchInput,function(){
                state.search = $(this).val() || '';
                state.page = 1;
                izzyRenderState(state);
            })
            .off('click.izzyList',state.clearBtn)
            .on('click.izzyList',state.clearBtn,function(){
                $(state.searchInput).val('');
                state.search = '';
                state.page = 1;
                izzyRenderState(state);
                $(state.searchInput).focus();
            })
            .off('change.izzyList',state.pageSizeSelect)
            .on('change.izzyList',state.pageSizeSelect,function(){
                state.pageSize = parseInt($(this).val(),10) || 10;
                state.page = 1;
                izzyRenderState(state);
            })
            .off('click.izzyList',state.modal+' .fm-view-btn')
            .on('click.izzyList',state.modal+' .fm-view-btn',function(){
                state.view = $(this).attr('data-view') === 'miniatura' ? 'miniatura' : 'detalle';
                state.page = 1;
                izzyRenderState(state);
            })
            .off('click.izzyList',state.list+' [data-row-index]')
            .on('click.izzyList',state.list+' [data-row-index]',function(e){
                if ($(e.target).closest('.iv-trigger').length) return;
                var index = parseInt($(this).attr('data-row-index'),10);
                var row = state.filtered[index];
                if (row && typeof state.onSelect === 'function') state.onSelect(row);
            });
    }

    function izzyLoadState(state,url,data,done) {
        $(state.list).removeClass('fm-view-miniatura').html('<div class="fm-loading"><i class="fas fa-spinner fa-spin mr-1"></i> Cargando...</div>');
        $.ajax({
            type:'POST',
            url:url,
            data:data || {},
            cache:false
        }).done(function(response){
            state.rows = izzyListRows(response);
            state.page = 1;
            izzyRenderState(state);
            if (typeof done === 'function') done(state.rows);
        }).fail(function(){
            state.rows = [];
            state.filtered = [];
            izzyRenderState(state);
            if (typeof showNotify === 'function') {
                showNotify('error','Error','No fue posible cargar la información.');
            }
        });
    }


    var izzyCotProductosState;
    var izzyCotClientesState;
    var izzyCotColaboradoresState;


    function izzyCotOrdenarHeaderModal($modal, titulo, subtitulo) {
        var $header = $modal.find('.modal-header').first();

        $modal.addClass('fm-modal');
        $header.addClass('fm-modal-header');

        var $close = $header.find('.close').first().detach();
        var $title = $header.find('.modal-title').first();

        if (!$header.find('.fm-modal-title-wrap').length) {
            var $wrap = $('<div class="fm-modal-title-wrap"></div>');
            $title.detach().appendTo($wrap);
            $wrap.append('<small class="d-block mt-1"></small>');
            $header.empty().append($wrap).append($close);
        }

        $header.find('.modal-title').text(titulo);
        $header.find('.fm-modal-title-wrap small').text(subtitulo);
    }

    function izzyCotPrepareProductoModal() {
        var $modal = $('#modal_buscar_productos_cotizacion');
        var $form = $('#formulario_busqueda_productos_cotizacion');

        izzyCotOrdenarHeaderModal(
            $modal,
            'Buscar Productos - Cotización',
            'Seleccione un producto o servicio para agregarlo a la cotización.'
        );
        $modal.find('.modal-body').addClass('fm-modal-body');

        if (!$('#cotizacionFiltroProductosCard').length) {
            var $select = $form.find('#almacen');
            var $selectVisual = $select.closest('.bootstrap-select');

            if (!$selectVisual.length) {
                $selectVisual = $select;
            } else {
                $selectVisual = $selectVisual.add($select);
            }

            var $card = $(
                '<div class="fm-section-card mb-3" id="cotizacionFiltroProductosCard">' +
                    '<div class="fm-section-header">' +
                        '<div><h6 class="mb-0"><i class="fas fa-filter mr-1"></i>Filtros de productos</h6><small>Use los criterios necesarios y aplique la búsqueda.</small></div>' +
                        '<button type="button" class="btn fm-toggle-filter" id="cotizacionToggleFiltroProductos"><i class="fas fa-chevron-up mr-1"></i><span>Ocultar</span></button>' +
                    '</div>' +
                    '<div class="fm-section-body" id="cotizacionFiltroProductosBody">' +
                        '<div class="fm-filter-row">' +
                            '<div class="fm-filter-field"><label>Bodega</label><div id="cotizacionFiltroBodegaHost"></div></div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );

            $form.prepend($card);
            $('#cotizacionFiltroBodegaHost').append($selectVisual);

            $form.children('.form-group').not('#cotizacionFiltroProductosCard').each(function () {
                if (!$(this).find('input,select,textarea').length) {
                    $(this).remove();
                }
            });

            try {
                $select.selectpicker('refresh');
            } catch (e) {}
        }

        if (!$('#productosCotizacionListado').length) {
            $('#DatatableProductosBusquedaCotizacion').closest('.col-md-12').replaceWith(
                '<div class="fm-directory-card">' +
                    '<div class="fm-list-toolbar">' +
                        '<div class="fm-toolbar-left">' +
                            '<button type="button" id="btnActualizarProductosCot" class="btn btn-info table_actualizar ocultar"><i class="fas fa-sync-alt"></i>Actualizar</button>' +
                            '<button type="button" id="btnNuevoProductoCot" class="btn btn-primary table_crear ocultar"><i class="fas fa-plus"></i>Ingresar</button>' +
                        '</div>' +
                        '<div class="fm-toolbar-right">' +
                            '<label class="fm-page-size">Mostrar <select id="productosCotizacionPageSize" class="form-control form-control-sm fm-page-size-select"><option>10</option><option>25</option><option>50</option><option>100</option></select> registros</label>' +
                            '<div class="fm-view-switch">' +
                                '<button type="button" class="fm-view-btn active" data-view="detalle"><i class="fas fa-list"></i></button>' +
                                '<button type="button" class="fm-view-btn" data-view="miniatura"><i class="fas fa-th-large"></i></button>' +
                            '</div>' +
                            '<div class="fm-search-wrap"><i class="fas fa-search"></i><input type="search" id="productosCotizacionSearch" class="form-control form-control-sm" placeholder="Buscar..."><button type="button" id="productosCotizacionClear" class="fm-search-clear"><i class="fas fa-times"></i></button></div>' +
                        '</div>' +
                    '</div>' +
                    '<div id="productosCotizacionListado" class="fm-listado"></div>' +
                    '<div class="fm-list-footer"><span id="productosCotizacionInfo" class="fm-list-info">0 registros</span><div id="productosCotizacionPaginacion" class="fm-pagination"></div></div>' +
                '</div>'
            );
        }

        $('#cotizacionToggleFiltroProductos').off('click.izzy').on('click.izzy',function(){
            var $body = $('#cotizacionFiltroProductosBody');
            var hidden = $body.is(':visible');
            $body.stop(true,true).slideToggle(160);
            $(this).find('span').text(hidden ? 'Mostrar' : 'Ocultar');
            $(this).find('i').toggleClass('fa-chevron-up',!hidden).toggleClass('fa-chevron-down',hidden);
        });
    }

    function izzyCotSeleccionarProducto(data) {
        if ($("#quoteForm #cliente_id").val() === "" || $("#quoteForm #cliente").val() === "" ||
            $("#quoteForm #colaborador_id").val() === "" || $("#quoteForm #colaborador").val() === "") {
            showNotify('error','Error','Lo sentimos no se puede seleccionar un producto, por favor antes de continuar, verifique que cliente y vendedor no se encuentren vacíos.');
            return;
        }

        $('#quoteForm #QuoteItem #productosQuote_id_' + row).val(data.productos_id);
        $('#quoteForm #QuoteItem #bar-code-id_' + row).val(data.barCode);
        $('#quoteForm #QuoteItem #productNameQuote_' + row).val(data.nombre);
        $('#quoteForm #QuoteItem #quantityQuote_' + row).val(1);
        $('#quoteForm #QuoteItem #quantityQuote_' + row).focus();
        $('#quoteForm #QuoteItem #priceQuote_' + row).val(data.precio_venta);
        $('#quoteForm #QuoteItem #discountQuote_' + row).val(0);
        $('#quoteForm #QuoteItem #isvQuote_' + row).val(data.impuesto_venta);
        setISVQuoteDesdeProducto(row, data);
        $('#quoteForm #QuoteItem #precio_mayoreoQuote_' + row).val(data.precio_mayoreo);
        $('#quoteForm #QuoteItem #cantidad_mayoreoQuote_' + row).val(data.cantidad_mayoreo);
        $('#quoteForm #QuoteItem #precio_realQuote_' + row).val(data.precio_venta);

        actualizarTextoProductoQuote(row, data.nombre, data.medida);

        if (data.impuesto_venta == 1) {
            var porcentaje_isv = parseFloat(getPorcentajeISV("Facturas") / 100);
            var porcentaje_calculo = (parseFloat(data.precio_venta || 0) * porcentaje_isv).toFixed(2);
            var isv_total = parseFloat($('#quoteForm #taxAmountQuote').val() || 0);
            $('#quoteForm #taxAmountQuote').val((isv_total + parseFloat(porcentaje_calculo)).toFixed(2));
            $('#quoteForm #QuoteItem #valorQuote_isv_' + row).val(porcentaje_calculo);
        }

        recalcularISVCotizacionActual(row);
        calculateTotalQuote();
        addRowQuote();
        $('#modal_buscar_productos_cotizacion').modal('hide');
        row++;
    }

    function izzyCotInitStates() {
        izzyCotPrepareProductoModal();

        izzyCotOrdenarHeaderModal(
            $('#modal_buscar_clientes_facturacion'),
            'Buscar Clientes',
            'Seleccione un cliente existente o registre uno nuevo.'
        );
        $('#modal_buscar_clientes_facturacion .modal-body').addClass('fm-modal-body');

        izzyCotOrdenarHeaderModal(
            $('#modal_buscar_colaboradores_facturacion'),
            'Buscar Colaboradores',
            'Seleccione el vendedor responsable de la cotización.'
        );
        $('#modal_buscar_colaboradores_facturacion .modal-body').addClass('fm-modal-body');

        izzyCotProductosState = izzyCreateState({
            modal:'#modal_buscar_productos_cotizacion',
            list:'#productosCotizacionListado',
            info:'#productosCotizacionInfo',
            pagination:'#productosCotizacionPaginacion',
            searchInput:'#productosCotizacionSearch',
            clearBtn:'#productosCotizacionClear',
            pageSizeSelect:'#productosCotizacionPageSize',
            grid:'130px minmax(260px,2fr) minmax(150px,1fr) 110px 100px minmax(140px,1fr) 140px minmax(150px,1fr)',
            columns:[
                {label:'Agregar',cls:'fm-text-center'},
                {label:'Producto'},
                {label:'Código'},
                {label:'Existencia',cls:'fm-text-center'},
                {label:'Medida'},
                {label:'Categoría'},
                {label:'Venta',cls:'fm-text-right'},
                {label:'Bodega'}
            ],
            renderDetail:function(r,i){
                return '<div class="fm-detail-row" style="grid-template-columns:'+this.grid+'" data-row-index="'+i+'">' +
                    '<div class="fm-cell fm-text-center" data-label="Agregar"><button type="button" class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-cart-plus mr-1"></i>Agregar</button></div>' +
                    '<div class="fm-cell" data-label="Producto"><div class="fm-product-card-main">'+izzyProductImageHtml(r)+'<div class="fm-product-copy"><div class="fm-product-name">'+izzyListEscape(r.nombre||'')+'</div><div class="fm-product-kind">'+izzyListEscape(r.tipo_producto_nombre||'Producto')+'</div></div></div></div>' +
                    '<div class="fm-cell" data-label="Código">'+izzyListEscape(r.barCode||'')+'</div>' +
                    '<div class="fm-cell fm-text-center" data-label="Existencia">'+izzyListEscape(r.cantidad||0)+'</div>' +
                    '<div class="fm-cell" data-label="Medida">'+izzyListEscape(r.medida||'')+'</div>' +
                    '<div class="fm-cell" data-label="Categoría">'+izzyListEscape(r.tipo_producto_nombre||'')+'</div>' +
                    '<div class="fm-cell fm-text-right" data-label="Venta"><strong>'+izzyListMoney(r.precio_venta)+'</strong></div>' +
                    '<div class="fm-cell" data-label="Bodega">'+izzyListEscape(r.almacen||'Sin bodega')+'</div>' +
                '</div>';
            },
            renderMini:function(r,i){
                return '<div class="fm-mini-card" data-row-index="'+i+'">' +
                    '<div class="fm-mini-header"><div class="fm-product-card-main">'+izzyProductImageHtml(r)+'<div class="fm-product-copy"><div class="fm-mini-title">'+izzyListEscape(r.nombre||'')+'</div><span class="fm-mini-subtitle">Código: '+izzyListEscape(r.barCode||'')+'</span></div></div>' +
                    '<div class="fm-mini-actions"><button type="button" class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-cart-plus mr-1"></i>Agregar</button></div></div>' +
                    '<div class="fm-mini-body">' +
                        izzyMiniField('Existencia',izzyListEscape(r.cantidad||0)) +
                        izzyMiniField('Medida',izzyListEscape(r.medida||'')) +
                        izzyMiniField('Categoría',izzyListEscape(r.tipo_producto_nombre||'')) +
                        izzyMiniField('Venta','<strong>'+izzyListMoney(r.precio_venta)+'</strong>') +
                        izzyMiniField('Bodega',izzyListEscape(r.almacen||'Sin bodega')) +
                    '</div>' +
                '</div>';
            },
            onSelect:izzyCotSeleccionarProducto
        });
        izzyBindState(izzyCotProductosState);

        izzyCotClientesState = izzyCreateState({
            modal:'#modal_buscar_clientes_facturacion',
            list:'#clientesFacturaListado',
            info:'#clientesFacturaInfo',
            pagination:'#clientesFacturaPaginacion',
            searchInput:'#clientesFacturaSearch',
            clearBtn:'#modal_buscar_clientes_facturacion .fm-search-clear',
            pageSizeSelect:'#clientesFacturaPageSize',
            grid:'150px minmax(240px,2fr) minmax(130px,1fr) minmax(130px,1fr) minmax(220px,1.4fr)',
            columns:[{label:'Seleccionar'},{label:'Cliente'},{label:'RTN'},{label:'Teléfono'},{label:'Correo'}],
            renderDetail:function(r,i){
                return '<div class="fm-detail-row" style="grid-template-columns:'+this.grid+'" data-row-index="'+i+'">' +
                    '<div class="fm-cell" data-label="Seleccionar"><button type="button" class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-copy mr-1"></i>Seleccionar</button></div>' +
                    '<div class="fm-cell" data-label="Cliente"><strong>'+izzyListEscape(r.cliente||'')+'</strong></div>' +
                    '<div class="fm-cell" data-label="RTN">'+izzyListEscape(r.rtn||'')+'</div>' +
                    '<div class="fm-cell" data-label="Teléfono">'+izzyListEscape(r.telefono||'')+'</div>' +
                    '<div class="fm-cell" data-label="Correo">'+izzyListEscape(r.correo||'')+'</div>' +
                '</div>';
            },
            renderMini:function(r,i){
                return '<div class="fm-mini-card" data-row-index="'+i+'"><div class="fm-mini-header"><div><div class="fm-mini-title">'+izzyListEscape(r.cliente||'')+'</div><span class="fm-mini-subtitle">'+izzyListEscape(r.rtn||'')+'</span></div><button class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-copy mr-1"></i>Seleccionar</button></div><div class="fm-mini-body">'+izzyMiniField('Teléfono',izzyListEscape(r.telefono||''))+izzyMiniField('Correo',izzyListEscape(r.correo||''))+'</div></div>';
            },
            onSelect:function(r){
                $('#quoteForm #cliente_id').val(r.clientes_id);
                $('#quoteForm #cliente').val(r.cliente);
                $('#quoteForm #client-customers-quote').html('<b>Cliente:</b> '+izzyListEscape(r.cliente));
                $('#quoteForm #rtn-customers-quote').html('<b>RTN:</b> '+izzyListEscape(r.rtn));
                $('#modal_buscar_clientes_facturacion').modal('hide');
            }
        });
        izzyBindState(izzyCotClientesState);

        izzyCotColaboradoresState = izzyCreateState({
            modal:'#modal_buscar_colaboradores_facturacion',
            list:'#colaboradoresFacturaListado',
            info:'#colaboradoresFacturaInfo',
            pagination:'#colaboradoresFacturaPaginacion',
            searchInput:'#colaboradoresFacturaSearch',
            clearBtn:'#modal_buscar_colaboradores_facturacion .fm-search-clear',
            pageSizeSelect:'#colaboradoresFacturaPageSize',
            grid:'150px minmax(260px,2fr) minmax(160px,1fr) minmax(140px,1fr)',
            columns:[{label:'Seleccionar'},{label:'Colaborador'},{label:'Identidad'},{label:'Teléfono'}],
            renderDetail:function(r,i){
                return '<div class="fm-detail-row" style="grid-template-columns:'+this.grid+'" data-row-index="'+i+'"><div class="fm-cell"><button class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-copy mr-1"></i>Seleccionar</button></div><div class="fm-cell"><strong>'+izzyListEscape(r.colaborador||'')+'</strong></div><div class="fm-cell">'+izzyListEscape(r.identidad||'')+'</div><div class="fm-cell">'+izzyListEscape(r.telefono||'')+'</div></div>';
            },
            renderMini:function(r,i){
                return '<div class="fm-mini-card" data-row-index="'+i+'"><div class="fm-mini-header"><div><div class="fm-mini-title">'+izzyListEscape(r.colaborador||'')+'</div><span class="fm-mini-subtitle">'+izzyListEscape(r.identidad||'')+'</span></div><button class="btn btn-primary btn-sm table_view ocultar"><i class="fas fa-copy mr-1"></i>Seleccionar</button></div><div class="fm-mini-body">'+izzyMiniField('Teléfono',izzyListEscape(r.telefono||''))+'</div></div>';
            },
            onSelect:function(r){
                $('#quoteForm #colaborador_id').val(r.colaborador_id);
                $('#quoteForm #colaborador').val(r.colaborador);
                $('#quoteForm #vendedor-customers-quote').html('<b>Vendedor:</b> '+izzyListEscape(r.colaborador));
                $('#modal_buscar_colaboradores_facturacion').modal('hide');
            }
        });
        izzyBindState(izzyCotColaboradoresState);

        $('#btnActualizarProductosCot').off('click.izzy').on('click.izzy',listar_productos_cotizacion_buscar);
        $('#btnNuevoProductoCot').off('click.izzy').on('click.izzy',function(){ if(typeof modal_productos==='function') modal_productos(); });
        $('#btnActualizarClientesFm').off('click.izzy').on('click.izzy',listar_clientes_cotizacion_buscar);
        $('#btnNuevoClienteFm').off('click.izzy').on('click.izzy',function(){ if(typeof modal_clientes==='function') modal_clientes(); });
        $('#btnActualizarColaboradoresFm').off('click.izzy').on('click.izzy',listar_colaboradores_buscar_cotizacion);
        $('#btnNuevoColaboradorFm').off('click.izzy').on('click.izzy',function(){ if(typeof modal_colaboradores==='function') modal_colaboradores(); });

        $('#formulario_busqueda_productos_cotizacion #almacen').off('change.izzy').on('change.izzy',listar_productos_cotizacion_buscar);
    }

    listar_productos_cotizacion_buscar = function() {
        if (!izzyCotProductosState) izzyCotInitStates();
        var bodega = $('#formulario_busqueda_productos_cotizacion #almacen').val() || 1;
        izzyLoadState(izzyCotProductosState,'<?php echo SERVERURL; ?>core/llenarDataTableProductosCotizacion.php',{bodega:bodega});
    };

    listar_clientes_cotizacion_buscar = function() {
        if (!izzyCotClientesState) izzyCotInitStates();
        izzyLoadState(izzyCotClientesState,'<?php echo SERVERURL; ?>core/llenarDataTableClientes.php',{});
    };

    listar_colaboradores_buscar_cotizacion = function() {
        if (!izzyCotColaboradoresState) izzyCotInitStates();
        izzyLoadState(izzyCotColaboradoresState,'<?php echo SERVERURL; ?>core/llenarDataTableColaboradores.php',{});
    };

    $(function(){
        izzyCotInitStates();

        $('#modal_buscar_productos_cotizacion,#modal_buscar_clientes_facturacion,#modal_buscar_colaboradores_facturacion')
            .off('shown.bs.modal.izzyFocus')
            .on('shown.bs.modal.izzyFocus',function(){
                $(this).find('input[type="search"]:visible').first().focus();
            });
    });

</script>