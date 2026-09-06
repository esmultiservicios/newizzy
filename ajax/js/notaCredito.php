<script>
// Ubicación: Ajax/js/notaCredito.php
(function iniciarNotaCreditoIZZY() {
    'use strict';

    if (!window.jQuery) {
        window.setTimeout(iniciarNotaCreditoIZZY, 50);
        return;
    }

    if (window.__IZZY_NOTA_CREDITO_INICIALIZADA__) {
        return;
    }

    window.__IZZY_NOTA_CREDITO_INICIALIZADA__ = true;

    (function ($) {

    var ncState = {
        factura: null,
        detalle: [],
        notas: [],
        cargando: false,
        emitiendo: false
    };

    function parseRespuestaNc(raw) {
        if (raw && typeof raw === 'object') {
            return raw;
        }

        var text = String(raw === undefined || raw === null ? '' : raw).trim();

        if (!text) {
            throw new Error('El servidor respondió vacío.');
        }

        try {
            return JSON.parse(text);
        } catch (e) {
            // Si PHP imprimió un warning/notice antes del JSON, recuperar
            // el último objeto JSON válido para no convertir un HTTP 200
            // en un falso "Error de comunicación".
            var inicio = text.lastIndexOf('{"success"');

            if (inicio >= 0) {
                var candidato = text.substring(inicio);
                try {
                    return JSON.parse(candidato);
                } catch (e2) {}
            }

            console.error('Respuesta Nota de Crédito no válida:', text);
            throw new Error('El servidor no devolvió una respuesta válida.');
        }
    }

    function moneyNc(value) {
        var n = parseFloat(value || 0);
        if (isNaN(n)) n = 0;
        return 'L ' + n.toLocaleString('es-HN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function numNc(value) {
        var n = parseFloat(value || 0);
        return isNaN(n) ? 0 : n;
    }

    function escapeNc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getRowFacturaNc(button) {
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#DatatableBusquedaBill')) {
            return null;
        }

        var table = $('#DatatableBusquedaBill').DataTable();
        var $tr = $(button).closest('tr');

        if ($tr.hasClass('child')) {
            $tr = $tr.prev();
        }

        return table.row($tr).data();
    }

    $(document)
        .off('click.notaCreditoFactura', '#DatatableBusquedaBill tbody .nota_credito_factura')
        .on('click.notaCreditoFactura', '#DatatableBusquedaBill tbody .nota_credito_factura', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var row = getRowFacturaNc(this);

            if (!row || !row.facturas_id) {
                showNotify('error', 'Error', 'No se pudo identificar la factura.');
                return;
            }

            // Cerrar el dropdown de Acciones antes de abrir un segundo modal.
            // Evita que el menú quede flotando por encima de Nota de Crédito.
            $('.acciones-dropdown.show, .dropdown.show').removeClass('show');
            $('.acciones-menu.show, .dropdown-menu.show').removeClass('show');
            $('.js-acciones-toggle[aria-expanded="true"]').attr('aria-expanded', 'false');

            abrirNotaCredito(row.facturas_id);
        });

    $('#btnNcCreditoTotal').off('click.nc').on('click.nc', function () {
        $('#nc_detalle_listado .izzy-nc-base-input').each(function () {
            var max = numNc($(this).data('max'));
            $(this).val(max.toFixed(4));
        });
        recalcularNc();
    });

    $('#btnNcLimpiar').off('click.nc').on('click.nc', function () {
        $('#nc_detalle_listado .izzy-nc-base-input').val('');
        recalcularNc();
    });

    $('#nc_detalle_listado')
        .off('input.nc change.nc', '.izzy-nc-base-input')
        .on('input.nc change.nc', '.izzy-nc-base-input', function () {
            var max = numNc($(this).data('max'));
            var val = numNc($(this).val());

            if (val < 0) val = 0;
            if (val > max) {
                val = max;
                $(this).val(max.toFixed(4));
            }

            recalcularNc();
        });

    $('#nc_motivo').off('input.nc').on('input.nc', function () {
        $('#nc_motivo_count').text(String($(this).val() || '').length);
    });

    $('#btnEmitirNotaCredito').off('click.nc').on('click.nc', function () {
        prepararEmisionNc();
    });

    function prepararModalNotaCreditoEnBody() {
        var $modal = $('#modalNotaCredito');

        /*
         * IMPORTANTE:
         * Buscar Facturas también es un modal. Si Nota de Crédito queda
         * físicamente dentro de ese modal, ningún z-index puede sacarla por
         * encima porque queda atrapada en el stacking context del padre.
         *
         * La movemos directamente a <body> antes de mostrarla.
         */
        if ($modal.parent()[0] !== document.body) {
            $modal.appendTo(document.body);
        }

        $modal.css({
            'z-index': 20000
        });
    }

    function ordenarBackdropsNotaCredito() {
        var $backdrops = $('.modal-backdrop');

        // Quitar cualquier marca previa para no afectar otros modales.
        $backdrops.removeClass('izzy-nc-backdrop');

        // Marcar ÚNICAMENTE el backdrop creado para Nota de Crédito.
        // No tocamos el backdrop de Buscar Facturas.
        if ($backdrops.length >= 2) {
            $backdrops.last().addClass('izzy-nc-backdrop');
        }

        $('#modalNotaCredito').addClass('izzy-nc-on-top');
    }

    $('#modalNotaCredito')
        .off('show.bs.modal.ncstack shown.bs.modal.ncstack hidden.bs.modal.ncstack')
        .on('show.bs.modal.ncstack', function () {
            prepararModalNotaCreditoEnBody();
        })
        .on('shown.bs.modal.ncstack', function () {
            ordenarBackdropsNotaCredito();
        })
        .on('hidden.bs.modal.ncstack', function () {
            $('#modalNotaCredito').removeClass('izzy-nc-on-top');
            $('.modal-backdrop.izzy-nc-backdrop').removeClass('izzy-nc-backdrop');

            // Buscar Facturas sigue abierto debajo.
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });

    function abrirNotaCredito(facturasId) {
        if (ncState.cargando) return;

        resetNotaCredito();
        ncState.cargando = true;
        $('#nc_detalle_loading').removeClass('d-none');

        prepararModalNotaCreditoEnBody();

        $('#modalNotaCredito').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>core/notaCredito/obtenerFacturaNotaCredito.php',
            dataType: 'text',
            data: {facturas_id: facturasId}
        }).done(function (rawResponse) {
            var response;

            try {
                response = parseRespuestaNc(rawResponse);
            } catch (parseError) {
                showNotify('error', 'Nota de Crédito', parseError.message);
                $('#modalNotaCredito').modal('hide');
                return;
            }

            if (!response || response.success !== true || !response.data) {
                showNotify('error', 'Nota de Crédito', response && response.message ? response.message : 'No se pudo cargar la factura.');
                $('#modalNotaCredito').modal('hide');
                return;
            }

            ncState.factura = response.data.factura || {};
            ncState.detalle = Array.isArray(response.data.detalle) ? response.data.detalle : [];
            ncState.notas = Array.isArray(response.data.notas) ? response.data.notas : [];

            renderCabeceraNc();
            renderDetalleNc();
            renderHistorialNc();
            recalcularNc();
        }).fail(function (xhr) {
            showNotify('error', 'Nota de Crédito', 'Error de comunicación (' + xhr.status + ').');
            $('#modalNotaCredito').modal('hide');
        }).always(function () {
            ncState.cargando = false;
            $('#nc_detalle_loading').addClass('d-none');
        });
    }

    window.IZZYNotaCredito = {
        abrir: abrirNotaCredito
    };

    function resetNotaCredito() {
        ncState.factura = null;
        ncState.detalle = [];
        ncState.notas = [];
        $('#nc_facturas_id').val('');
        $('#nc_motivo').val('');
        $('#nc_motivo_count').text('0');
        $('#nc_detalle_listado').empty();
        $('#nc_historial').empty();
        $('#nc_detalle_empty').addClass('d-none');
        $('#nc_base_total,#nc_isv15_total,#nc_isv18_total,#nc_gran_total').text('L 0.00');
    }

    function renderCabeceraNc() {
        var f = ncState.factura || {};
        $('#nc_facturas_id').val(f.facturas_id || '');
        $('#nc_factura_numero').text(f.numero || '—');
        $('#nc_factura_fecha').text(f.fecha ? 'Fecha: ' + f.fecha : '—');
        $('#nc_cliente').text(f.cliente || '—');
        $('#nc_rtn').text(f.rtn ? 'RTN: ' + f.rtn : 'Sin RTN');
        $('#nc_total_factura').text(moneyNc(f.importe));
        $('#nc_total_previo').text(moneyNc(f.total_acreditado));
        $('#nc_total_disponible').text(moneyNc(f.disponible));
    }

    function renderDetalleNc() {
        var $list = $('#nc_detalle_listado');
        $list.empty();

        var disponibles = ncState.detalle.filter(function (d) {
            return numNc(d.base_disponible) > 0.00005;
        });

        $('#nc_detalle_empty').toggleClass('d-none', disponibles.length > 0);

        if (!disponibles.length) return;

        var html = disponibles.map(function (d) {
            var taxText = [];
            if (numNc(d.isv15_original) > 0) taxText.push('ISV 15%');
            if (numNc(d.isv18_original) > 0) taxText.push('ISV 18%');
            if (!taxText.length) taxText.push('Exento / sin ISV');

            return '' +
                '<article class="izzy-nc-line" data-id="' + escapeNc(d.facturas_detalle_id) + '">' +
                    '<div class="izzy-nc-product">' +
                        '<strong>' + escapeNc(d.producto) + '</strong>' +
                        '<small>Cant. original: ' + escapeNc(d.cantidad) + ' · Precio: ' + moneyNc(d.precio) + ' · ' + taxText.join(' / ') + '</small>' +
                    '</div>' +
                    '<div>' +
                        '<span class="izzy-nc-data-label">Base original</span>' +
                        '<span class="izzy-nc-money">' + moneyNc(d.base_original) + '</span>' +
                    '</div>' +
                    '<div>' +
                        '<span class="izzy-nc-data-label">Ya acreditado</span>' +
                        '<span class="izzy-nc-money">' + moneyNc(d.base_acreditada_previa) + '</span>' +
                    '</div>' +
                    '<div>' +
                        '<span class="izzy-nc-data-label">Disponible</span>' +
                        '<span class="izzy-nc-money available">' + moneyNc(d.base_disponible) + '</span>' +
                    '</div>' +
                    '<div class="izzy-nc-input-wrap">' +
                        '<span class="izzy-nc-data-label">Base a acreditar</span>' +
                        '<div class="input-group input-group-sm">' +
                            '<div class="input-group-prepend"><span class="input-group-text">L</span></div>' +
                            '<input type="number" min="0" step="0.01" class="form-control izzy-nc-base-input" ' +
                                   'data-id="' + escapeNc(d.facturas_detalle_id) + '" ' +
                                   'data-max="' + escapeNc(d.base_disponible) + '" ' +
                                   'data-base-original="' + escapeNc(d.base_original) + '" ' +
                                   'data-isv15-original="' + escapeNc(d.isv15_original) + '" ' +
                                   'data-isv18-original="' + escapeNc(d.isv18_original) + '" ' +
                                   'data-isv15-disponible="' + escapeNc(d.isv15_disponible) + '" ' +
                                   'data-isv18-disponible="' + escapeNc(d.isv18_disponible) + '" placeholder="0.00">' +
                        '</div>' +
                    '</div>' +
                '</article>';
        }).join('');

        $list.html(html);
    }

    function calcularLineaCliente($input) {
        var base = numNc($input.val());
        var max = numNc($input.data('max'));
        var baseOriginal = numNc($input.data('base-original'));
        var isv15Original = numNc($input.data('isv15-original'));
        var isv18Original = numNc($input.data('isv18-original'));
        var isv15Disponible = numNc($input.data('isv15-disponible'));
        var isv18Disponible = numNc($input.data('isv18-disponible'));

        if (base <= 0) return {base:0,isv15:0,isv18:0,total:0};

        var cierre = Math.abs(base - max) <= 0.01;
        var factor = baseOriginal > 0 ? base / baseOriginal : 0;

        var isv15 = cierre ? isv15Disponible : isv15Original * factor;
        var isv18 = cierre ? isv18Disponible : isv18Original * factor;

        return {
            base: base,
            isv15: isv15,
            isv18: isv18,
            total: base + isv15 + isv18
        };
    }

    function recalcularNc() {
        var base = 0, isv15 = 0, isv18 = 0;

        $('#nc_detalle_listado .izzy-nc-base-input').each(function () {
            var x = calcularLineaCliente($(this));
            base += x.base;
            isv15 += x.isv15;
            isv18 += x.isv18;
        });

        $('#nc_base_total').text(moneyNc(base));
        $('#nc_isv15_total').text(moneyNc(isv15));
        $('#nc_isv18_total').text(moneyNc(isv18));
        $('#nc_gran_total').text(moneyNc(base + isv15 + isv18));
    }

    function renderHistorialNc() {
        var $hist = $('#nc_historial');

        if (!ncState.notas.length) {
            $hist.html('<div class="text-muted small">Todavía no existen Notas de Crédito para esta factura.</div>');
            return;
        }

        $hist.html(ncState.notas.map(function (n) {
            return '' +
                '<article class="izzy-nc-history-item">' +
                    '<div>' +
                        '<strong>' + escapeNc(n.numero_completo) + '</strong>' +
                        '<small>' + escapeNc(n.fecha) + ' · ' + escapeNc(n.motivo) + '</small>' +
                    '</div>' +
                    '<div class="izzy-nc-history-total">' + moneyNc(n.total_acreditado) + '</div>' +
                    '<div>' +
                        '<a class="btn btn-info btn-sm" target="_blank" rel="noopener" href="<?php echo SERVERURL; ?>core/notaCredito/verNotaCredito.php?nota_credito_id=' + escapeNc(n.nota_credito_id) + '">' +
                            '<i class="fas fa-eye"></i> Ver' +
                        '</a>' +
                    '</div>' +
                '</article>';
        }).join(''));
    }

    function payloadNc() {
        var detalle = [];

        $('#nc_detalle_listado .izzy-nc-base-input').each(function () {
            var monto = numNc($(this).val());

            if (monto > 0) {
                detalle.push({
                    facturas_detalle_id: parseInt($(this).data('id'), 10),
                    base_acreditar: monto
                });
            }
        });

        return {
            facturas_id: parseInt($('#nc_facturas_id').val(), 10),
            motivo: String($('#nc_motivo').val() || '').trim(),
            origen: 'escritorio',
            detalle: detalle
        };
    }

    function prepararEmisionNc() {
        if (ncState.emitiendo) return;

        var data = payloadNc();

        if (!data.motivo) {
            showNotify('warning', 'Dato requerido', 'Ingrese el motivo de la Nota de Crédito.');
            $('#nc_motivo').trigger('focus');
            return;
        }

        if (!data.detalle.length) {
            showNotify('warning', 'Sin monto', 'Ingrese un monto a acreditar en al menos un concepto.');
            return;
        }

        if (typeof validarAdminSistema !== 'function') {
            showNotify('error', 'Validación no disponible', 'No está cargada la validación administrativa.');
            return;
        }

        var numeroFactura = ncState.factura && ncState.factura.numero ? ncState.factura.numero : data.facturas_id;

        validarAdminSistema(function (permitido) {
            if (permitido !== true) return;
            emitirNc(data);
        }, {
            mensaje: 'Para emitir una Nota de Crédito debe validar un administrador.',
            modulo: 'Facturación',
            accion: 'Emitir Nota de Crédito',
            referencia_id: data.facturas_id,
            referencia_texto: numeroFactura,
            motivo: 'Validación requerida para emitir Nota de Crédito desde facturación'
        });
    }

    function emitirNc(data) {
        ncState.emitiendo = true;
        var $btn = $('#btnEmitirNotaCredito');
        var original = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Emitiendo...');

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>core/notaCredito/registrarNotaCredito.php',
            dataType: 'text',
            data: {data: JSON.stringify(data)}
        }).done(function (rawResponse) {
            var response;

            try {
                response = parseRespuestaNc(rawResponse);
            } catch (parseError) {
                showNotify('error', 'Nota de Crédito', parseError.message);
                return;
            }

            if (!response || response.success !== true) {
                showNotify('error', 'Nota de Crédito', response && response.message ? response.message : 'No se pudo emitir la Nota de Crédito.');
                return;
            }

            var r = response.data || {};
            showNotify(
                r.warning ? 'warning' : 'success',
                'Nota de Crédito emitida',
                (r.numero ? 'Documento ' + r.numero + ' registrado correctamente.' : response.message) +
                (r.warning ? ' ' + r.warning : '')
            );

            $('#modalNotaCredito').modal('hide');

            if (typeof listar_busqueda_bill === 'function') {
                listar_busqueda_bill();
            }

            if (r.nota_credito_id) {
                window.open(
                    '<?php echo SERVERURL; ?>core/notaCredito/verNotaCredito.php?nota_credito_id=' + encodeURIComponent(r.nota_credito_id),
                    '_blank',
                    'noopener'
                );
            }
        }).fail(function (xhr) {
            var mensaje = 'Error de comunicación (' + xhr.status + ').';

            try {
                var json = JSON.parse(xhr.responseText);
                if (json && json.message) mensaje = json.message;
            } catch (e) {}

            showNotify('error', 'Nota de Crédito', mensaje);
        }).always(function () {
            ncState.emitiendo = false;
            $btn.prop('disabled', false).html(original);
        });
    }
    })(window.jQuery);
})();
</script>
