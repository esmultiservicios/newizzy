/* =========================================================
   IZZY | FACTURACIÓN - UI PREMIUM COMPLEMENTARIA
   ---------------------------------------------------------
   Alcance:
   - Recordar Contado / Crédito en este navegador.
   - Mejorar visualmente el modal de cambio a Crédito.
   - Separadores de miles SOLO visuales en Precio/Descuento/Total.
     Los inputs originales conservan sus valores numéricos crudos
     para no alterar cálculos, AJAX ni backend.
   ========================================================= */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    var STORAGE_RECORDAR = 'izzy_factura_recordar_tipo';
    var STORAGE_TIPO = 'izzy_factura_tipo_preferido';

    function vistaFacturasActiva() {
        return $('#view_bill').length > 0;
    }

    function storageDisponible() {
        try {
            var clave = '__izzy_storage_test__';
            window.localStorage.setItem(clave, '1');
            window.localStorage.removeItem(clave);
            return true;
        } catch (e) {
            return false;
        }
    }

    function recordarActivo() {
        if (!storageDisponible()) return false;
        return window.localStorage.getItem(STORAGE_RECORDAR) === '1';
    }

    function tipoGuardado() {
        if (!recordarActivo()) return '';
        var tipo = String(window.localStorage.getItem(STORAGE_TIPO) || '').toLowerCase();
        return (tipo === 'credito' || tipo === 'contado') ? tipo : '';
    }

    function guardarPreferenciaTipo(tipo) {
        if (!storageDisponible()) return;

        if (!recordarActivo()) {
            return;
        }

        tipo = String(tipo || '').toLowerCase();
        if (tipo !== 'credito' && tipo !== 'contado') return;

        window.localStorage.setItem(STORAGE_TIPO, tipo);
    }

    function actualizarRecordatorio(activo, tipo) {
        if (!storageDisponible()) return;

        if (activo === true) {
            window.localStorage.setItem(STORAGE_RECORDAR, '1');

            tipo = String(tipo || '').toLowerCase();
            if (tipo === 'credito' || tipo === 'contado') {
                window.localStorage.setItem(STORAGE_TIPO, tipo);
            }
        } else {
            window.localStorage.removeItem(STORAGE_RECORDAR);
            window.localStorage.removeItem(STORAGE_TIPO);
        }
    }

    function tipoFacturaActualUI() {
        if ($('#btn-tipo-credito').hasClass('active')) return 'credito';
        return 'contado';
    }

    function construirRecordatorioModal() {
        var $modal = $('#confirmTipoFactura');
        if (!$modal.length || $modal.find('#recordar_tipo_factura_wrap').length) {
            return;
        }

        var html = ''
            + '<div id="recordar_tipo_factura_wrap" class="tipo-factura-recordar">'
            + '  <div class="tipo-factura-recordar-icon"><i class="fas fa-history"></i></div>'
            + '  <div class="tipo-factura-recordar-copy">'
            + '    <strong>Recordar esta elección</strong>'
            + '    <small>Al activarlo, IZZY abrirá futuras facturas con el último tipo seleccionado. Si luego cambia a Contado, también se recordará automáticamente.</small>'
            + '  </div>'
            + '  <label class="tipo-factura-recordar-switch" for="recordar_tipo_factura">'
            + '    <input type="checkbox" id="recordar_tipo_factura" autocomplete="off">'
            + '    <span></span>'
            + '  </label>'
            + '</div>';

        $modal.find('.modal-body').prepend(html);

        $modal.find('.modal-footer .btn-secondary')
            .html('<i class="fas fa-times mr-1"></i> Cancelar');

        $('#confirmarCambioTipo')
            .html('<i class="fas fa-check mr-1"></i> Sí, cambiar');
    }

    function sincronizarCheckRecordatorio() {
        $('#recordar_tipo_factura').prop('checked', recordarActivo());
    }

    function aplicarTipoRecordado() {
        var tipo = tipoGuardado();
        if (!tipo || typeof window.setTipoFactura !== 'function') {
            return;
        }

        window.setTipoFactura(tipo);
    }

    function limpiarFormatoMonedaDuplicado() {
        $('#invoiceItem .izzy-money-display').remove();
        $('#invoiceItem .izzy-money-raw').removeClass('izzy-money-raw');
        $('#invoiceItem .izzy-money-readonly-wrap').each(function () {
            var $wrap = $(this);
            var $input = $wrap.children('input').first();
            if ($input.length) {
                $wrap.replaceWith($input);
            }
        });
    }

    function moverFechaDolarAJuntoBotones() {
        var $fecha = $('#fecha_dolar');
        var $facturas = $('#BillReports');

        if (!$fecha.length || !$facturas.length || $('.factura-fecha-dolar-inline').length) {
            return;
        }

        var $grupo = $fecha.closest('.input-group');
        var $columna = $fecha.closest('.col-12.col-md-6');
        var $botonera = $facturas.parent();

        if (!$grupo.length || !$botonera.length) {
            return;
        }

        var $inline = $('<div class="factura-fecha-dolar-inline" aria-label="Fecha Cambio Dólar"></div>');
        $grupo.detach().appendTo($inline);
        $botonera.append($inline);

        if ($columna.length) {
            $columna.remove();
        }
    }

    function prepararViewportDetalleFactura() {
        var $viewport = $('#invoiceItem').closest('.form-group.row.table-responsive-xl');
        if (!$viewport.length) return;

        $viewport.addClass('detalle-factura-scroll');

        if (!$viewport.children('.detalle-factura-watermark').length) {
            $viewport.append(
                '<div class="detalle-factura-watermark" aria-hidden="true">' +
                    '<i class="fas fa-receipt"></i>' +
                    '<strong>Detalle de la factura</strong>' +
                    '<span>Agregue productos o servicios por código o búsqueda. El encabezado permanece fijo y las acciones siempre quedan visibles debajo.</span>' +
                '</div>'
            );
        }

        actualizarWatermarkDetalle();
    }

    function actualizarWatermarkDetalle() {
        var $watermark = $('.detalle-factura-watermark');
        if (!$watermark.length) return;

        var tieneProducto = false;

        $('#invoiceItem tbody tr').each(function () {
            var $fila = $(this);
            var productoId = String($fila.find('[id^="productos_id_"]').val() || '').trim();
            var codigo = String($fila.find('[id^="bar-code-id_"]').val() || '').trim();
            var descripcion = String($fila.find('[id^="productName_"]').val() || '').trim();

            if (productoId !== '' || codigo !== '' || (descripcion !== '' && descripcion !== 'Descripción del Producto')) {
                tieneProducto = true;
                return false;
            }
        });

        $watermark.toggleClass('d-none', tieneProducto);
    }

    function observarFilasFactura() {
        var tabla = document.querySelector('#invoiceItem tbody');
        if (!tabla || !window.MutationObserver) return;

        var observer = new MutationObserver(function () {
            window.setTimeout(function () {
                actualizarWatermarkDetalle();
            }, 0);
        });

        observer.observe(tabla, {
            childList: true,
            subtree: false
        });
    }


    /* =========================================================
       FORMATO DE MILES SEGURO
       ---------------------------------------------------------
       No escribe comas dentro de los inputs usados por cálculos.
       Solo superpone una representación visual cuando el campo
       no tiene foco.
       ========================================================= */
    function parseNumeroVisual(valor) {
        var texto = String(valor === null || valor === undefined ? '' : valor).trim();
        if (texto === '') return null;

        texto = texto.replace(/\s/g, '').replace(/,/g, '');
        var numero = Number(texto);
        return Number.isFinite(numero) ? numero : null;
    }

    function formatoMilesVisual(valor, decimales) {
        var numero = parseNumeroVisual(valor);
        if (numero === null) return '';

        return numero.toLocaleString('en-US', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales
        });
    }

    function obtenerDecimalesMoneda($input) {
        var id = String($input.attr('id') || '');
        return id.indexOf('total_') === 0 ? 4 : 2;
    }

    function asegurarHostMoneda($input) {
        if (!$input.length) return $();

        var $host = $input.parent();

        /* Precio y descuento viven en .input-group y necesitan reservar
           el botón +. Total vive directamente en su TD. */
        if ($input.closest('.input-group').length) {
            $host = $input.closest('.input-group');
        }

        $host.addClass('izzy-money-host');
        return $host;
    }

    function obtenerDisplayMoneda($input) {
        var id = String($input.attr('id') || '');
        if (!id) return $();

        var $host = asegurarHostMoneda($input);
        var $display = $host.children('.izzy-money-display-safe[data-for="' + id + '"]');

        if (!$display.length) {
            $display = $('<span class="izzy-money-display-safe" aria-hidden="true"></span>')
                .attr('data-for', id);

            if (id.indexOf('total_') === 0) {
                $display.addClass('is-total');
            }

            $host.append($display);
        }

        return $display;
    }

    function mostrarFormatoMoneda($input) {
        if (!$input.length || document.activeElement === $input.get(0)) return;

        var texto = formatoMilesVisual($input.val(), obtenerDecimalesMoneda($input));
        var $display = obtenerDisplayMoneda($input);

        if (texto === '') {
            $display.hide();
            $input.removeClass('izzy-money-concealed');
            return;
        }

        /* En input-group, no cubrir el botón +. */
        if ($input.closest('.input-group').length) {
            var anchoBoton = $input.closest('.input-group').find('.input-group-append').outerWidth() || 0;
            $display.css('right', anchoBoton + 'px');
        } else {
            $display.css('right', '0');
        }

        $display.text(texto).show();
        $input.removeClass('izzy-money-editing').addClass('izzy-money-concealed');
    }

    function editarMoneda($input) {
        var id = String($input.attr('id') || '');
        var $host = asegurarHostMoneda($input);

        $host.children('.izzy-money-display-safe[data-for="' + id + '"]').hide();
        $input.removeClass('izzy-money-concealed').addClass('izzy-money-editing');
    }

    function refrescarFormatoMonedas() {
        if (!vistaFacturasActiva()) return;

        $('#invoiceItem input[id^="price_"], #invoiceItem input[id^="discount_"], #invoiceItem input[id^="total_"]').each(function () {
            mostrarFormatoMoneda($(this));
        });
    }

    function iniciarFormatoMonedasSeguro() {
        refrescarFormatoMonedas();

        $(document)
            .off('focus.izzyMoneySafe', '#invoiceItem input[id^="price_"], #invoiceItem input[id^="discount_"]')
            .on('focus.izzyMoneySafe', '#invoiceItem input[id^="price_"], #invoiceItem input[id^="discount_"]', function () {
                editarMoneda($(this));
            });

        $(document)
            .off('blur.izzyMoneySafe', '#invoiceItem input[id^="price_"], #invoiceItem input[id^="discount_"]')
            .on('blur.izzyMoneySafe', '#invoiceItem input[id^="price_"], #invoiceItem input[id^="discount_"]', function () {
                var $input = $(this);
                window.setTimeout(function () {
                    mostrarFormatoMoneda($input);
                    refrescarFormatoMonedas();
                }, 0);
            });

        /* Las filas y valores cambian dinámicamente. Se refresca únicamente
           la capa visual; nunca se toca el valor numérico real. */
        window.setInterval(function () {
            if (vistaFacturasActiva()) {
                refrescarFormatoMonedas();
            }
        }, 350);
    }


    function aplicarLayoutFacturaInmediato() {
        if (!vistaFacturasActiva()) return;

        limpiarFormatoMonedaDuplicado();
        moverFechaDolarAJuntoBotones();
        prepararViewportDetalleFactura();
        actualizarWatermarkDetalle();
    }

    /* Evita el cambio visual entre la primera pintura y el ready.
       El script se carga con defer, por lo que el DOM ya está parseado. */
    aplicarLayoutFacturaInmediato();

    $(function () {
        if (!vistaFacturasActiva()) return;

        construirRecordatorioModal();
        limpiarFormatoMonedaDuplicado();
        moverFechaDolarAJuntoBotones();
        prepararViewportDetalleFactura();
        observarFilasFactura();
        iniciarFormatoMonedasSeguro();

        $(document)
            .off('input.izzyDetalleVacio change.izzyDetalleVacio', '#invoiceItem input')
            .on('input.izzyDetalleVacio change.izzyDetalleVacio', '#invoiceItem input', function () {
                window.setTimeout(actualizarWatermarkDetalle, 0);
            });

        window.setTimeout(function () {
            aplicarTipoRecordado();
            limpiarFormatoMonedaDuplicado();
            moverFechaDolarAJuntoBotones();
            prepararViewportDetalleFactura();
        }, 120);

        $('#confirmTipoFactura')
            .off('show.bs.modal.izzyRecordarTipo')
            .on('show.bs.modal.izzyRecordarTipo', function () {
                construirRecordatorioModal();
                sincronizarCheckRecordatorio();
            });

        $(document)
            .off('change.izzyRecordarTipo', '#recordar_tipo_factura')
            .on('change.izzyRecordarTipo', '#recordar_tipo_factura', function () {
                var activo = $(this).is(':checked');
                var tipo = $('#confirmTipoFactura').data('next') || tipoFacturaActualUI();
                actualizarRecordatorio(activo, tipo);
            });

        $(document)
            .off('click.izzyGuardarCreditoRecordado', '#confirmarCambioTipo')
            .on('click.izzyGuardarCreditoRecordado', '#confirmarCambioTipo', function () {
                var activo = $('#recordar_tipo_factura').is(':checked');
                actualizarRecordatorio(activo, 'credito');
            });

        $(document)
            .off('click.izzyGuardarContadoRecordado', '#btn-tipo-contado')
            .on('click.izzyGuardarContadoRecordado', '#btn-tipo-contado', function () {
                window.setTimeout(function () {
                    guardarPreferenciaTipo('contado');
                }, 0);
            });

        $(document)
            .off('click.izzyGuardarCreditoDirecto', '#btn-tipo-credito')
            .on('click.izzyGuardarCreditoDirecto', '#btn-tipo-credito', function () {
                if (tipoFacturaActualUI() === 'credito') {
                    guardarPreferenciaTipo('credito');
                }
            });

        $(document)
            .off('click.izzyQuitarFocusBoton', '#view_bill .btn')
            .on('click.izzyQuitarFocusBoton', '#view_bill .btn', function () {
                var boton = this;
                window.setTimeout(function () {
                    try { boton.blur(); } catch (e) {}
                }, 0);
            });
    });

})(window, document, window.jQuery);
