/* =========================================================
   IZZY | COMPRAS PREMIUM
   Comportamiento visual aislado.
   No modifica cálculos ni proceso de registro.
   ========================================================= */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    function vistaComprasActiva() {
        return $('#view_purchase #purchase-form').length > 0;
    }

    /*
     * El bloque "Detalle de la compra" debe permanecer siempre
     * en el fondo del área de detalle, igual que en Facturas
     * y Cotización. Por eso nunca se oculta al agregar productos.
     */
    function mantenerDetalleVisible() {
        var $shell = $('#view_purchase .purchase-detail-shell');

        if (!$shell.length) {
            return;
        }

        $shell.removeClass('has-products');
    }

    /*
     * Si el usuario selecciona TODAS las filas y pulsa Quitar,
     * se protege siempre la primera fila.
     *
     * El handler original de facturaCompras.php sigue haciendo
     * la eliminación normal de las demás filas.
     */
    function protegerPrimeraFilaEnEliminacionMasiva(event) {
        var boton = event.target.closest('#removeRowsPurchase');

        if (!boton || !vistaComprasActiva()) {
            return;
        }

        var $filas = $('#purchaseItem tbody tr');
        var $seleccionadas = $filas.find('.itemRowPurchase:checked');

        if ($filas.length === 0 || $seleccionadas.length === 0) {
            return;
        }

        if ($seleccionadas.length >= $filas.length) {
            var $primeraFila = $filas.first();
            var $primerCheck = $primeraFila.find('.itemRowPurchase').first();

            $primerCheck.prop('checked', false).removeAttr('checked');

            $('#checkAllPurchase')
                .prop('checked', false)
                .removeAttr('checked');

            /*
             * No detenemos el evento.
             * El código original continuará y eliminará únicamente
             * las filas que siguen seleccionadas.
             */
        }
    }


    /* =========================================================
       IZZY v6.66 | SEPARADORES DE MILES EN DETALLE
       El valor de cada input type=number permanece SIN COMAS.
       Se muestra una capa visual no interactiva al perder foco.
       ========================================================= */
    function iniciarSeparadoresMiles(tabla, campos) {
        var selector = campos.map(function (campo) {
            return '#' + tabla + ' input[id^="' + campo + '_"]';
        }).join(', ');
        var $tabla = $('#' + tabla);
        if (!$tabla.length) return;

        function convertir(valor) {
            var texto = String(valor == null ? '' : valor).trim();
            if (!texto) return '';
            var numero = Number(texto.replace(/,/g, ''));
            if (!Number.isFinite(numero)) return '';
            var decimales = texto.indexOf('.') >= 0
                ? Math.min(texto.split('.')[1].length, 4) : 2;
            return numero.toLocaleString('en-US', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales
            });
        }

        function asegurarPresentador(input) {
            var $input = $(input);
            var $marco = $input.parent('.izzy-miles-marco');
            if (!$marco.length) {
                $input.wrap('<span class="izzy-miles-marco"></span>');
                $marco = $input.parent();
                $marco.css({
                    position: 'relative', display: 'flex', alignItems: 'stretch',
                    flex: '1 1 0', width: '100%', minWidth: '0', maxWidth: '100%'
                });
                $input.css({width: '100%', minWidth: '0', flex: '1 1 auto'});
                $('<span class="izzy-miles-texto" aria-hidden="true"></span>')
                    .css({
                        position: 'absolute', left: '0', right: '0', top: '0', bottom: '0',
                        display: 'none', alignItems: 'center', justifyContent: 'center',
                        color: '#26384a', fontSize: '12px', fontWeight: '700',
                        whiteSpace: 'nowrap', pointerEvents: 'none', zIndex: 2,
                        overflow: 'hidden'
                    }).appendTo($marco);
            }
            return $marco;
        }

        function actualizar(input) {
            if (!input || !input.isConnected) return;
            var $input = $(input);
            var $marco = asegurarPresentador(input);
            var $texto = $marco.children('.izzy-miles-texto');
            var enEdicion = document.activeElement === input && !input.readOnly;
            var nuevo = convertir($input.val());
            if (enEdicion || !nuevo) {
                $input.css({'color': '', '-webkit-text-fill-color': ''});
                $texto.hide();
                return;
            }
            if ($texto.text() !== nuevo) $texto.text(nuevo);
            $texto.css('display', 'flex');
            $input.css({'color': 'transparent', '-webkit-text-fill-color': 'transparent'});
        }

        function refrescar() {
            $tabla.find(selector.replace(new RegExp('#' + tabla + ' ', 'g'), '')).each(function () {
                actualizar(this);
            });
        }

        $(document)
            .off('focusin.izzyMiles' + tabla, selector)
            .on('focusin.izzyMiles' + tabla, selector, function () {
                if (!this.readOnly) {
                    $(this).css({'color': '', '-webkit-text-fill-color': ''});
                    $(this).parent('.izzy-miles-marco').children('.izzy-miles-texto').hide();
                }
            })
            .off('focusout.izzyMiles' + tabla, selector)
            .on('focusout.izzyMiles' + tabla, selector, function () {
                actualizar(this);
            });

        refrescar();
        window.setInterval(refrescar, 500);
    }


    /* =========================================================
       IZZY v6.67 | Separadores en footer (solo visuales)
       Los textarea readonly conservan su valor original.
       ========================================================= */
    function iniciarMilesFooter(ids) {
        function formato(valor) {
            var limpio = String(valor == null ? '' : valor).trim()
                .replace(/\s/g, '').replace(/,/g, '');
            if (!limpio) return '';
            var numero = Number(limpio);
            if (!Number.isFinite(numero)) return '';
            var decimales = limpio.indexOf('.') >= 0
                ? Math.min(limpio.split('.')[1].length, 4) : 2;
            return numero.toLocaleString('en-US', {
                minimumFractionDigits: decimales,
                maximumFractionDigits: decimales
            });
        }

        function actualizar() {
            ids.forEach(function (id) {
                var input = document.getElementById(id);
                if (!input || !input.readOnly) return;

                var $input = $(input);
                var $grupo = $input.closest('.footer1 .input-group');
                if (!$grupo.length) return;

                $grupo.css('position', 'relative');
                var $visual = $grupo.children('.izzy-footer-miles[data-for="' + id + '"]');
                if (!$visual.length) {
                    $visual = $('<span class="izzy-footer-miles" aria-hidden="true"></span>')
                        .attr('data-for', id)
                        .css({
                            position: 'absolute', top: 0, bottom: 0, right: 0,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            textAlign: 'center', whiteSpace: 'nowrap', overflow: 'hidden',
                            pointerEvents: 'none', zIndex: 3,
                            color: '#40566a', fontSize: '13px', fontWeight: '800'
                        })
                        .appendTo($grupo);
                }

                var numero = formato($input.val());
                if (!numero) {
                    $visual.hide();
                    input.style.removeProperty('color');
                    input.style.removeProperty('-webkit-text-fill-color');
                    return;
                }

                var inicio = $grupo.find('.input-group-prepend').outerWidth() || 40;
                $visual.css('left', inicio + 'px');
                if ($visual.text() !== numero) $visual.text(numero);
                $visual.show();
                input.style.setProperty('color', 'transparent', 'important');
                input.style.setProperty('-webkit-text-fill-color', 'transparent', 'important');
            });
        }

        actualizar();
        window.setInterval(actualizar, 350);
    }

    function iniciar() {
        if (!vistaComprasActiva()) {
            return;
        }

        mantenerDetalleVisible();
        if (!window.__izzyMilesPurchaseStarted) {
            window.__izzyMilesPurchaseStarted = true;
            iniciarSeparadoresMiles("purchaseItem", ["pricePurchase", "isvPurchaseWrite", "discountPurchase", "totalPurchase"]);
            iniciarMilesFooter([
                'subTotalFooterPurchase', 'taxDescuentoFooterPurchase',
                'taxAmountFooterPurchase', 'taxAmountFooterPurchase18',
                'totalAftertaxFooterPurchase'
            ]);
        }

        /*
         * Capture = se ejecuta antes del handler delegado existente
         * en facturaCompras.php.
         */
        document.removeEventListener(
            'click',
            protegerPrimeraFilaEnEliminacionMasiva,
            true
        );

        document.addEventListener(
            'click',
            protegerPrimeraFilaEnEliminacionMasiva,
            true
        );

        /*
         * Si otro script agrega clases al contenedor durante la carga,
         * aseguramos que el fondo de detalle continúe visible.
         */
        window.setInterval(function () {
            if (vistaComprasActiva()) {
                mantenerDetalleVisible();
            }
        }, 300);
    }

    iniciar();

    $(iniciar);

})(window, document, window.jQuery);
