<script>
/* =========================================================
   IZZY | DETALLES DE FACTURACIÓN
   Listado DIV / Grid / Flex + KPI + Select2 + Excel + PDF
   ========================================================= */

var DF_MOBILE_QUERY = '(max-width: 767.98px)';
var DF_STORAGE_VIEW = 'izzy.detallesFacturacion.tipo_vista';
var DF_STORAGE_FILTERS = 'izzy.detallesFacturacion.filtros_visible';
var DF_STORAGE_KPI = 'izzy.detallesFacturacion.kpi_visible';

var DF = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    view: 'detalle',
    preferredView: 'detalle',
    search: '',
    loading: false,
    request: null,
    logoDataUrl: null,
    empresaNombre: '',
    marcaResuelta: false
};

var DFModal = {
    facturaId: null,
    factura: null,
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    view: 'detalle',
    search: '',
    loading: false
};

$(() => {
    inicializarFechasFacturas();
    inicializarSelect2DetallesFacturacion();
    inicializarPanelesDetallesFacturacion();
    restaurarVistaDetallesFacturacion();
    sincronizarVistaResponsiveDetallesFacturacion();
    enlazarEventosDetallesFacturacion();
    cargarFacturas(true);
});

function enlazarEventosDetallesFacturacion() {
    $('#form-filtros-facturas')
        .off('submit.detallesFacturacion')
        .on('submit.detallesFacturacion', function(e) {
            e.preventDefault();
            DF.page = 1;
            cargarFacturas(true);
        });

    $('#numero_factura')
        .off('keyup.detallesFacturacion')
        .on('keyup.detallesFacturacion', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                DF.page = 1;
                cargarFacturas(true);
            }
        });

    $('#tipo_factura, #estado_factura')
        .off('change.detallesFacturacion')
        .on('change.detallesFacturacion', function() {
            DF.page = 1;
            cargarFacturas(true);
        });

    $('#btn-limpiar-filtros')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', function() {
            limpiarFiltrosFacturas();
            DF.page = 1;
            cargarFacturas(true);
        });

    $('#btn-actualizar-facturas')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', function() {
            cargarFacturas(false);
        });

    $('#dfPageSize')
        .off('change.detallesFacturacion')
        .on('change.detallesFacturacion', function() {
            var value = parseInt($(this).val(), 10);
            DF.pageSize = (!isNaN(value) && value > 0) ? value : 10;
            DF.page = 1;
            renderFacturas();
        });

    $('#dfListadoSearch')
        .off('input.detallesFacturacion')
        .on('input.detallesFacturacion', function() {
            DF.search = String($(this).val() || '');
            DF.page = 1;
            aplicarBusquedaFacturas();
        });

    $('#dfListadoSearchClear')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', function() {
            $('#dfListadoSearch').val('').focus();
            DF.search = '';
            DF.page = 1;
            aplicarBusquedaFacturas();
        });

    $('[data-df-view]')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', function() {
            var requested = String($(this).attr('data-df-view') || 'detalle');

            if (esMovilDetallesFacturacion()) {
                requested = 'miniatura';
            }

            DF.view = requested === 'miniatura' ? 'miniatura' : 'detalle';
            DF.preferredView = DF.view;
            DF.page = 1;

            if (!esMovilDetallesFacturacion()) {
                try {
                    localStorage.setItem(DF_STORAGE_VIEW, DF.preferredView);
                } catch (e) {}
            }

            sincronizarBotonesVistaDetallesFacturacion();
            renderFacturas();
        });

    $('#btn-excel-facturas')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', exportarDetallesFacturacionExcel);

    $('#btn-pdf-facturas')
        .off('click.detallesFacturacion')
        .on('click.detallesFacturacion', exportarDetallesFacturacionPdf);

    $('#dfModalPageSize')
        .off('change.detallesFacturacionModal')
        .on('change.detallesFacturacionModal', function() {
            var value = parseInt($(this).val(), 10);
            DFModal.pageSize = (!isNaN(value) && value > 0) ? value : 10;
            DFModal.page = 1;
            renderDetalleFacturaModal();
        });

    $('#dfModalSearch')
        .off('input.detallesFacturacionModal')
        .on('input.detallesFacturacionModal', function() {
            DFModal.search = String($(this).val() || '');
            DFModal.page = 1;
            aplicarBusquedaDetalleFacturaModal();
        });

    $('#dfModalSearchClear')
        .off('click.detallesFacturacionModal')
        .on('click.detallesFacturacionModal', function() {
            $('#dfModalSearch').val('').focus();
            DFModal.search = '';
            DFModal.page = 1;
            aplicarBusquedaDetalleFacturaModal();
        });

    $('[data-df-modal-view]')
        .off('click.detallesFacturacionModal')
        .on('click.detallesFacturacionModal', function() {
            DFModal.view = String($(this).attr('data-df-modal-view') || 'detalle') === 'miniatura'
                ? 'miniatura'
                : 'detalle';
            DFModal.page = 1;
            sincronizarBotonesVistaDetalleFacturaModal();
            renderDetalleFacturaModal();
        });

    $('#dfModalExcel')
        .off('click.detallesFacturacionModal')
        .on('click.detallesFacturacionModal', exportarDetalleFacturaModalExcel);

    $('#dfModalPdf')
        .off('click.detallesFacturacionModal')
        .on('click.detallesFacturacionModal', exportarDetalleFacturaModalPdf);

    $('#dfModalPagination')
        .off('click.detallesFacturacionModal', '.df-page-btn')
        .on('click.detallesFacturacionModal', '.df-page-btn', function() {
            if ($(this).prop('disabled')) return;
            var page = parseInt($(this).attr('data-page'), 10);
            var pages = totalPaginasDetalleFacturaModal();
            if (isNaN(page)) return;
            DFModal.page = Math.min(Math.max(page, 1), Math.max(pages, 1));
            renderDetalleFacturaModal();
        });

    $('#modalDetalleFactura')
        .off('shown.bs.modal.detallesFacturacion hidden.bs.modal.detallesFacturacion')
        .on('shown.bs.modal.detallesFacturacion', function() {
            inicializarSelect2DetallesFacturacion();
            sincronizarBotonesVistaDetalleFacturaModal();
        })
        .on('hidden.bs.modal.detallesFacturacion', function() {
            resetDetalleFacturaModal();
        });

    $(document)
        .off('click.detallesFacturacionToggle', '.df-toggle-section')
        .on('click.detallesFacturacionToggle', '.df-toggle-section', function() {
            var $button = $(this);
            var target = String($button.attr('data-target') || '');
            var $target = $(target);

            if (!$target.length) {
                return;
            }

            var isVisible = $target.is(':visible');
            $target.stop(true, true)[isVisible ? 'slideUp' : 'slideDown'](160);
            actualizarBotonPanelDetallesFacturacion($button, !isVisible);

            try {
                if (target === '#dfFiltrosBody') {
                    localStorage.setItem(DF_STORAGE_FILTERS, !isVisible ? '1' : '0');
                }

                if (target === '#dfKpiBody') {
                    localStorage.setItem(DF_STORAGE_KPI, !isVisible ? '1' : '0');
                }
            } catch (e) {}
        });

    /* Dropdown de Acciones: mismo patrón adaptativo usado en Usuarios. */
    inicializarDropdownAccionesFacturas();

    $('#facturasListado')
        .off('click.detallesFacturacionDetalle', '.btn-detalle')
        .on('click.detallesFacturacionDetalle', '.btn-detalle', function(e) {
            e.preventDefault();
            cerrarMenusAccionesFacturas();

            var facturaId = $(this).data('id');
            cargarDetalleFactura(facturaId);

            $('#modalDetalleFactura').modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
        });

    $('#facturasListado')
        .off('click.detallesFacturacionImprimir', '.btn-imprimir')
        .on('click.detallesFacturacionImprimir', '.btn-imprimir', function(e) {
            e.preventDefault();
            cerrarMenusAccionesFacturas();
            imprimirFactura($(this).data('id'));
        });

    $('#facturasListado')
        .off('click.detallesFacturacionPagar', '.btn-pagar')
        .on('click.detallesFacturacionPagar', '.btn-pagar', function(e) {
            e.preventDefault();
            cerrarMenusAccionesFacturas();

            var facturaId = $(this).data('id');

            if (typeof swal !== 'function') {
                mostrarNotificacionFactura('error', 'Confirmación no disponible', 'No se encontró swal cargado en la plantilla.');
                return;
            }

            swal({
                title: '¿Pagar Factura?',
                text: '¿Desea proceder con el pago de esta factura?',
                icon: 'warning',
                buttons: {
                    cancel: {
                        text: 'Cancelar',
                        visible: true
                    },
                    confirm: {
                        text: 'Sí, pagar'
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then(function(willPay) {
                if (willPay) {
                    pagarFactura(facturaId);
                }
            });
        });

    $('#dfPagination')
        .off('click.detallesFacturacion', '.df-page-btn')
        .on('click.detallesFacturacion', '.df-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).attr('data-page'), 10);
            var pages = totalPaginasDetallesFacturacion();

            if (isNaN(page)) {
                return;
            }

            DF.page = Math.min(Math.max(page, 1), Math.max(pages, 1));
            renderFacturas();
        });

    $(document)
        .off('click.detallesFacturacionRetry', '.btn-retry-detalle')
        .on('click.detallesFacturacionRetry', '.btn-retry-detalle', function() {
            cargarDetalleFactura($(this).data('id'));
        });

    $(window)
        .off('resize.detallesFacturacion')
        .on('resize.detallesFacturacion', function() {
            sincronizarVistaResponsiveDetallesFacturacion();
        });
}

/* =========================================================
   SELECT2 ÚNICO
   ========================================================= */
function inicializarSelect2DetallesFacturacion() {
    /*
     * Igual que Usuarios: Select2 se usa en los filtros del formulario.
     * Los selectores "Mostrar X registros" permanecen nativos para conservar
     * exactamente la altura, flecha y alineación del estándar de Usuarios.
     */
    var $filters = $('#tipo_factura, #estado_factura');

    if ($.fn.izzySelect2Bridge) {
        $filters.izzySelect2Bridge('refresh');
        return;
    }

    if (typeof $.fn.select2 === 'function') {
        $filters.each(function() {
            var $select = $(this);

            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
        });
    }
}

function refrescarSelectDetallesFacturacion($select) {
    if (!$select || !$select.length) {
        return;
    }

    if ($.fn.izzySelect2Bridge) {
        $select.izzySelect2Bridge('refresh');
    } else if (typeof $.fn.select2 === 'function') {
        $select.trigger('change.select2');
    }
}

/* =========================================================
   PANELES FILTROS / KPI
   ========================================================= */
function inicializarPanelesDetallesFacturacion() {
    aplicarEstadoPanelDetallesFacturacion('#dfFiltrosBody', DF_STORAGE_FILTERS, true);
    aplicarEstadoPanelDetallesFacturacion('#dfKpiBody', DF_STORAGE_KPI, true);
}

function aplicarEstadoPanelDetallesFacturacion(target, storageKey, defaultVisible) {
    var visible = defaultVisible;

    try {
        var saved = localStorage.getItem(storageKey);
        if (saved !== null) {
            visible = saved === '1';
        }
    } catch (e) {}

    var $target = $(target);
    var $button = $('.df-toggle-section[data-target="' + target + '"]');

    $target.toggle(visible);
    actualizarBotonPanelDetallesFacturacion($button, visible);
}

function actualizarBotonPanelDetallesFacturacion($button, visible) {
    if (!$button || !$button.length) {
        return;
    }

    $button.attr('aria-expanded', visible ? 'true' : 'false');
    $button.find('span').text(visible ? 'Ocultar' : 'Mostrar');
    $button.find('i')
        .toggleClass('fa-chevron-up', visible)
        .toggleClass('fa-chevron-down', !visible);
}

/* =========================================================
   VISTA / ESTADO
   ========================================================= */
function esMovilDetallesFacturacion() {
    return window.matchMedia
        ? window.matchMedia(DF_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function restaurarVistaDetallesFacturacion() {
    var saved = 'detalle';

    try {
        saved = localStorage.getItem(DF_STORAGE_VIEW) || 'detalle';
    } catch (e) {}

    DF.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
    DF.view = esMovilDetallesFacturacion() ? 'miniatura' : DF.preferredView;
    sincronizarBotonesVistaDetallesFacturacion();
}

function sincronizarVistaResponsiveDetallesFacturacion() {
    var mobile = esMovilDetallesFacturacion();
    var $detailButton = $('[data-df-view="detalle"]');

    $detailButton
        .prop('disabled', mobile)
        .toggleClass('d-none', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    var targetView = mobile ? 'miniatura' : DF.preferredView;

    if (DF.view !== targetView) {
        DF.view = targetView;
        DF.page = 1;
        sincronizarBotonesVistaDetallesFacturacion();
        renderFacturas();
    }
}

function sincronizarBotonesVistaDetallesFacturacion() {
    $('[data-df-view]').removeClass('active').attr('aria-pressed', 'false');
    $('[data-df-view="' + DF.view + '"]').addClass('active').attr('aria-pressed', 'true');
}

/* =========================================================
   DATOS / FILTROS / BÚSQUEDA
   ========================================================= */
function normalizarFiltroFactura(valor) {
    valor = (valor === null || valor === undefined) ? '' : String(valor).trim();
    return (valor === '' || valor === 'todos') ? '' : valor;
}

function inicializarFechasFacturas() {
    $('#fecha_inicio').val('');
    $('#fecha_fin').val('');
    $('#tipo_factura').val('todos');
    $('#estado_factura').val('todos');
}

function limpiarFiltrosFacturas() {
    var form = $('#form-filtros-facturas')[0];

    if (form) {
        form.reset();
    }

    inicializarFechasFacturas();
    $('#numero_factura').val('');
    refrescarSelectDetallesFacturacion($('#tipo_factura'));
    refrescarSelectDetallesFacturacion($('#estado_factura'));
}

function cargarFacturas(resetPage) {
    if (resetPage !== false) {
        DF.page = 1;
    }

    if (DF.request && typeof DF.request.abort === 'function') {
        DF.request.abort();
    }

    DF.loading = true;
    renderCargaFacturas();

    DF.request = $.ajax({
        url: '<?php echo SERVERURL; ?>core/DetallesFacturacion/DetallesFacturacion.php',
        type: 'POST',
        dataType: 'json',
        data: {
            fecha_inicio: $('#fecha_inicio').val(),
            fecha_fin: $('#fecha_fin').val(),
            tipo_factura: normalizarFiltroFactura($('#tipo_factura').val()),
            estado_factura: normalizarFiltroFactura($('#estado_factura').val()),
            numero_factura: $('#numero_factura').val(),
            draw: 1,
            start: 0,
            length: -1,
            search: { value: '', regex: false },
            order: [{ column: 1, dir: 'desc' }],
            columns: [
                { data: 'facturas_id', searchable: false, orderable: false, search: { value: '', regex: false } },
                { data: 'fecha', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'numero', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'cliente', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'tipo_documento', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'estado', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'subtotal', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'isv', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'descuento', searchable: true, orderable: true, search: { value: '', regex: false } },
                { data: 'total', searchable: true, orderable: true, search: { value: '', regex: false } }
            ]
        }
    })
    .done(function(response) {
        if (response && response.type && response.type === 'error') {
            DF.rows = [];
            DF.loading = false;
            mostrarNotificacionFactura(
                'error',
                response.title || 'Error',
                response.message || 'No se pudieron cargar las facturas.'
            );
            aplicarBusquedaFacturas();
            return;
        }

        DF.rows = response && Array.isArray(response.data) ? response.data : [];
        DF.loading = false;
        aplicarBusquedaFacturas();
    })
    .fail(function(xhr, status) {
        if (status === 'abort') {
            return;
        }

        console.error('Error al cargar facturas:', xhr.responseText);
        DF.rows = [];
        DF.loading = false;
        aplicarBusquedaFacturas();
        mostrarNotificacionFactura('error', 'Error', 'No se pudieron cargar las facturas.');
    })
    .always(function() {
        DF.request = null;
    });
}

function normalizarBusquedaFactura(value) {
    return String(value === null || value === undefined ? '' : value)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function textoBusquedaFactura(row) {
    return normalizarBusquedaFactura([
        row.fecha,
        row.numero,
        row.cliente,
        row.tipo_documento,
        row.estado_texto,
        row.subtotal,
        row.isv,
        row.descuento,
        row.total,
        formatMoneyFactura(row.subtotal),
        formatMoneyFactura(row.isv),
        formatMoneyFactura(row.descuento),
        formatMoneyFactura(row.total)
    ].join(' '));
}

function aplicarBusquedaFacturas() {
    var query = normalizarBusquedaFactura(DF.search);

    DF.filtered = !query
        ? DF.rows.slice()
        : DF.rows.filter(function(row) {
            return textoBusquedaFactura(row).indexOf(query) !== -1;
        });

    var pages = totalPaginasDetallesFacturacion();

    if (DF.page > pages) {
        DF.page = Math.max(pages, 1);
    }

    actualizarKpisFacturas();
    renderFacturas();
}

/* =========================================================
   MONEDA
   ========================================================= */
function facturaNumero(value) {
    if (typeof value === 'number') {
        return isFinite(value) ? value : 0;
    }

    var raw = String(value === null || value === undefined ? '' : value)
        .replace(/L\./gi, '')
        .replace(/L/gi, '')
        .replace(/,/g, '')
        .trim();

    var number = parseFloat(raw || 0);
    return isNaN(number) ? 0 : number;
}

function formatMoneyFactura(amount) {
    return 'L. ' + facturaNumero(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function actualizarKpisFacturas() {
    var rows = DF.filtered;
    var total = 0;
    var isv = 0;
    var descuento = 0;

    rows.forEach(function(row) {
        total += facturaNumero(row.total);
        isv += facturaNumero(row.isv);
        descuento += facturaNumero(row.descuento);
    });

    $('#dfKpiFacturas').text(rows.length.toLocaleString('en-US'));
    $('#dfKpiTotal').text(formatMoneyFactura(total));
    $('#dfKpiIsv').text(formatMoneyFactura(isv));
    $('#dfKpiDescuento').text(formatMoneyFactura(descuento));
}

/* =========================================================
   RENDER DIV / GRID / FLEX
   ========================================================= */
function renderCargaFacturas() {
    $('#facturasEmpty').hide();
    $('#facturasListado')
        .removeClass('df-view-detalle df-view-miniatura')
        .addClass('df-view-' + DF.view)
        .html(
            '<div class="df-modal-loading">' +
                '<div class="spinner-border text-primary" role="status" aria-hidden="true"></div>' +
                '<div class="mt-2">Cargando facturas...</div>' +
            '</div>'
        );

    $('#dfResultsInfo').text('Cargando registros...');
    $('#dfPagination').empty();
}

function renderFacturas() {
    if (DF.loading) {
        return;
    }

    var $root = $('#facturasListado');
    var rows = obtenerPaginaFacturas();

    $root
        .removeClass('df-view-detalle df-view-miniatura')
        .addClass('df-view-' + DF.view);

    if (!DF.filtered.length) {
        $root.empty();
        $('#facturasEmpty').css('display', 'flex');
        $('#dfResultsInfo').text('Mostrando 0 registros');
        $('#dfPagination').empty();
        return;
    }

    $('#facturasEmpty').hide();

    if (DF.view === 'miniatura') {
        $root.html(rows.map(renderFacturaMiniatura).join(''));
    } else {
        var summary = dfResumenExportacion();

        $root.html(
            '<div class="df-detail-table">' +
                '<div class="df-detail-header df-detail-grid">' +
                    '<div>Acciones</div>' +
                    '<div>Fecha</div>' +
                    '<div>Número</div>' +
                    '<div>Cliente</div>' +
                    '<div>Tipo</div>' +
                    '<div>Estado</div>' +
                    '<div>Subtotal</div>' +
                    '<div>ISV</div>' +
                    '<div>Descuento</div>' +
                    '<div>Total</div>' +
                '</div>' +
                '<div class="df-detail-body">' + rows.map(renderFacturaDetalle).join('') + '</div>' +
                '<div class="df-detail-total df-detail-grid">' +
                    '<div class="df-detail-total-label">TOTALES GENERALES</div>' +
                    '<div class="df-detail-total-money">' + formatMoneyFactura(summary.subtotal) + '</div>' +
                    '<div class="df-detail-total-money">' + formatMoneyFactura(summary.isv) + '</div>' +
                    '<div class="df-detail-total-money">' + formatMoneyFactura(summary.descuento) + '</div>' +
                    '<div class="df-detail-total-money is-grand-total">' + formatMoneyFactura(summary.total) + '</div>' +
                '</div>' +
            '</div>'
        );
    }

    actualizarInfoResultadosFacturas();
    renderPaginacionFacturas();
}

function renderFacturaDetalle(row) {
    return '' +
        '<article class="df-detail-row df-detail-grid">' +
            dfCell('Acciones', renderAccionesFactura(row), 'is-actions') +
            dfCell('Fecha', escapeHtmlFactura(row.fecha || '—'), 'is-date') +
            dfCell('Número', '<span class="factura-numero">' + escapeHtmlFactura(row.numero || '—') + '</span>', 'is-number') +
            dfCell('Cliente', '<span class="factura-cliente">' + escapeHtmlFactura(row.cliente || '—') + '</span>', 'is-client') +
            dfCell('Tipo', renderTipoFactura(row.tipo_documento), 'is-type') +
            dfCell('Estado', renderEstadoFactura(row), 'is-status') +
            dfCell('Subtotal', formatMoneyFactura(row.subtotal), 'is-money') +
            dfCell('ISV', formatMoneyFactura(row.isv), 'is-money') +
            dfCell('Descuento', formatMoneyFactura(row.descuento), 'is-money') +
            dfCell('Total', '<strong>' + formatMoneyFactura(row.total) + '</strong>', 'is-money is-total') +
        '</article>';
}

function dfCell(label, value, className) {
    return '<div class="df-detail-cell ' + (className || '') + '" data-label="' + escapeHtmlFactura(label) + '">' +
        (value === null || value === undefined || value === '' ? '—' : value) +
    '</div>';
}

function renderFacturaMiniatura(row) {
    return '' +
        '<article class="df-mini-card">' +
            '<div class="df-mini-header">' +
                '<div class="df-mini-title">' +
                    '<strong>' + escapeHtmlFactura(row.numero || 'Sin número') + '</strong>' +
                    '<small>' + escapeHtmlFactura(row.cliente || 'Sin cliente') + '</small>' +
                '</div>' +
                '<div class="df-mini-actions">' + renderAccionesFactura(row) + '</div>' +
            '</div>' +
            '<div class="df-mini-body">' +
                dfMiniField('Fecha', escapeHtmlFactura(row.fecha || '—'), false) +
                dfMiniField('Tipo', escapeHtmlFactura(row.tipo_documento || '—'), false) +
                dfMiniField('Subtotal', formatMoneyFactura(row.subtotal), true) +
                dfMiniField('ISV', formatMoneyFactura(row.isv), true) +
                dfMiniField('Descuento', formatMoneyFactura(row.descuento), true) +
                dfMiniField('Total', formatMoneyFactura(row.total), true) +
            '</div>' +
            '<div class="df-mini-footer">' +
                '<div>' + renderEstadoFactura(row) + '</div>' +
                '<strong class="factura-total">' + formatMoneyFactura(row.total) + '</strong>' +
            '</div>' +
        '</article>';
}

function dfMiniField(label, value, money) {
    return '<div class="df-mini-field">' +
        '<span>' + escapeHtmlFactura(label) + '</span>' +
        '<strong class="' + (money ? 'is-money' : '') + '">' + value + '</strong>' +
    '</div>';
}

function renderTipoFactura(value) {
    var text = String(value || '—');
    var normalized = normalizarBusquedaFactura(text);
    var icon = normalized.indexOf('credito') !== -1
        ? 'fas fa-hand-holding-usd'
        : 'fas fa-money-bill-wave';

    return '<span class="badge-factura badge-factura-tipo">' +
        '<i class="' + icon + '"></i>' +
        escapeHtmlFactura(text) +
    '</span>';
}

function obtenerPaginaFacturas() {
    var start = (DF.page - 1) * DF.pageSize;
    return DF.filtered.slice(start, start + DF.pageSize);
}

function totalPaginasDetallesFacturacion() {
    return Math.max(1, Math.ceil(DF.filtered.length / DF.pageSize));
}

function actualizarInfoResultadosFacturas() {
    var total = DF.filtered.length;

    if (!total) {
        $('#dfResultsInfo').text('Mostrando 0 registros');
        return;
    }

    var start = ((DF.page - 1) * DF.pageSize) + 1;
    var end = Math.min(DF.page * DF.pageSize, total);

    $('#dfResultsInfo').text(
        'Mostrando ' + start.toLocaleString('en-US') +
        ' a ' + end.toLocaleString('en-US') +
        ' de ' + total.toLocaleString('en-US') +
        ' registros'
    );
}

function renderPaginacionFacturas() {
    var pages = totalPaginasDetallesFacturacion();
    var current = Math.min(Math.max(DF.page, 1), pages);
    var buttons = [];

    buttons.push(dfPageButton(1, 'Inicio', 'fas fa-angle-double-left', current === 1));
    buttons.push(dfPageButton(current - 1, 'Anterior', 'fas fa-angle-left', current === 1));

    var start = Math.max(1, current - 2);
    var end = Math.min(pages, current + 2);

    if (end - start < 4) {
        if (start === 1) {
            end = Math.min(pages, 5);
        } else if (end === pages) {
            start = Math.max(1, pages - 4);
        }
    }

    for (var page = start; page <= end; page++) {
        buttons.push(
            '<button type="button" class="df-page-btn ' + (page === current ? 'active' : '') + '" data-page="' + page + '" ' +
                (page === current ? 'aria-current="page"' : '') + '>' + page + '</button>'
        );
    }

    buttons.push(dfPageButton(current + 1, 'Siguiente', 'fas fa-angle-right', current === pages, true));
    buttons.push(dfPageButton(pages, 'Final', 'fas fa-angle-double-right', current === pages, true));

    $('#dfPagination').html(buttons.join(''));
}

function dfPageButton(page, label, icon, disabled, iconRight) {
    var iconHtml = '<i class="' + icon + '"></i>';
    var labelHtml = '<span class="df-page-label-long">' + escapeHtmlFactura(label) + '</span>';

    return '<button type="button" class="df-page-btn" data-page="' + page + '" ' +
        (disabled ? 'disabled' : '') + ' aria-label="' + escapeHtmlFactura(label) + '">' +
        (iconRight ? labelHtml + iconHtml : iconHtml + labelHtml) +
    '</button>';
}

/* =========================================================
   ESTADOS / ACCIONES
   ========================================================= */
function textoEstadoFacturaPlano(row) {
    var estadoNum = parseInt((row && row.estado) || 0, 10);
    var documentoId = parseInt((row && row.documento_id) || 0, 10);
    var pagosRealizados = parseInt((row && row.pagos_realizados) || 0, 10);
    var registrado = row && row.estado_texto ? String(row.estado_texto).trim() : '';

    if (registrado) {
        return registrado;
    }

    switch (estadoNum) {
        case 1:
            return documentoId === 4 ? 'Pendiente de pago' : 'Borrador';
        case 2:
            return 'Pagada al contado';
        case 3:
            return pagosRealizados > 0 ? 'Crédito con abono' : 'Crédito pendiente';
        case 4:
            return 'Anulada / Cancelada';
        default:
            return 'Sin estado';
    }
}

function renderEstadoFactura(row) {
    var estadoNum = parseInt(row.estado || 0, 10);
    var documentoId = parseInt(row.documento_id || 0, 10);
    var pagosRealizados = parseInt(row.pagos_realizados || 0, 10);
    var textoBase = textoEstadoFacturaPlano(row);
    var clase = 'badge-factura-secondary';
    var icono = 'fas fa-file-alt';
    var texto = textoBase;

    switch (estadoNum) {
        case 1:
            if (documentoId === 4) {
                clase = 'badge-factura-warning';
                icono = 'fas fa-clock';
                texto = textoBase || 'Pendiente de pago';
            } else {
                clase = 'badge-factura-secondary';
                icono = 'fas fa-file-alt';
                texto = textoBase || 'Borrador';
            }
            break;

        case 2:
            clase = 'badge-factura-success';
            icono = 'fas fa-check-circle';
            texto = textoBase || 'Pagada al contado';
            break;

        case 3:
            clase = 'badge-factura-warning';
            icono = pagosRealizados > 0 ? 'fas fa-hand-holding-usd' : 'fas fa-clock';
            texto = textoBase || (pagosRealizados > 0 ? 'Crédito con abono' : 'Crédito pendiente');
            break;

        case 4:
            clase = 'badge-factura-danger';
            icono = 'fas fa-times-circle';
            texto = textoBase || 'Anulada / Cancelada';
            break;

        default:
            clase = 'badge-factura-secondary';
            icono = 'fas fa-question-circle';
            texto = textoBase || 'Sin estado';
            break;
    }

    return '<span class="badge-factura ' + clase + '">' +
        '<i class="' + icono + '"></i>' +
        escapeHtmlFactura(texto) +
    '</span>';
}

function renderAccionesFactura(row) {
    var facturaId = escapeHtmlFactura(row.facturas_id);
    var puedePagar = row.estado == '3' || (
        parseInt(row.documento_id || 0, 10) === 4 &&
        (row.estado == '1' || parseInt(row.tiene_pendiente || 0, 10) > 0)
    );

    var html = '' +
        '<div class="dropdown acciones-dropdown factura-acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu factura-acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item btn-detalle" data-id="' + facturaId + '">' +
                    '<span class="accion-icon accion-icon-info"><i class="fas fa-eye"></i></span>' +
                    '<span class="accion-label">Ver detalle</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item btn-imprimir" data-id="' + facturaId + '">' +
                    '<span class="accion-icon accion-icon-primary"><i class="fas fa-print"></i></span>' +
                    '<span class="accion-label">Imprimir</span>' +
                '</button>';

    if (puedePagar) {
        html += '' +
            '<button type="button" class="dropdown-item accion-item btn-pagar" data-id="' + facturaId + '">' +
                '<span class="accion-icon accion-icon-success"><i class="fas fa-money-bill-wave"></i></span>' +
                '<span class="accion-label">Pagar</span>' +
            '</button>';
    }

    html += '</div></div>';
    return html;
}

/* =========================================================
   DETALLES DE FACTURACIÓN | DROPDOWN DE ACCIONES ADAPTATIVO
   Mismo patrón utilizado en Usuarios:
   - Posición fija según espacio disponible.
   - No queda recortado por cards / grid / overflow.
   - Cierra con clic externo, scroll, resize, ESC y modal.
   ========================================================= */
var facturasDropdownActivo = null;

function facturasObtenerBotonDropdown($dropdown) {
    return $dropdown.children('.js-acciones-toggle').first();
}

function facturasMedirDropdown($menu) {
    var menu = $menu && $menu.length ? $menu[0] : null;
    if (!menu) return { width: 205, height: 150 };

    var teniaShow = $menu.hasClass('show');
    var cssText = menu.style.cssText;

    $menu.addClass('show');
    menu.style.setProperty('display', 'block', 'important');
    menu.style.setProperty('visibility', 'hidden', 'important');
    menu.style.setProperty('position', 'fixed', 'important');
    menu.style.setProperty('top', '0px', 'important');
    menu.style.setProperty('left', '0px', 'important');
    menu.style.setProperty('right', 'auto', 'important');
    menu.style.setProperty('bottom', 'auto', 'important');
    menu.style.setProperty('transform', 'none', 'important');

    var rect = menu.getBoundingClientRect();

    menu.style.cssText = cssText;
    if (!teniaShow) $menu.removeClass('show');

    return {
        width: Math.max(rect.width || 0, 205),
        height: Math.max(rect.height || 0, 1)
    };
}

function facturasLimpiarDropdown($dropdown) {
    if (!$dropdown || !$dropdown.length) return;

    var $menu = $dropdown.children('.factura-acciones-menu').first();
    var menu = $menu[0];
    var $row = $dropdown.closest('.df-detail-row, .df-mini-card');

    $dropdown.removeClass('show dropup dropright dropleft');
    $menu
        .removeClass('show dropdown-menu-right')
        .removeAttr('x-placement data-popper-placement data-df-placement');

    if (menu) {
        [
            'display','visibility','position','top','left','right','bottom',
            'transform','z-index','max-height','overflow-y'
        ].forEach(function(prop) {
            menu.style.removeProperty(prop);
        });
    }

    facturasObtenerBotonDropdown($dropdown).attr('aria-expanded', 'false');
    $row.removeClass('df-dropdown-open');

    if ($row.length) $row[0].style.removeProperty('transform');

    if (
        facturasDropdownActivo &&
        facturasDropdownActivo.length &&
        facturasDropdownActivo.is($dropdown)
    ) {
        facturasDropdownActivo = null;
    }
}

function cerrarMenusAccionesFacturas($excepto) {
    $('#facturasListado .factura-acciones-dropdown').each(function() {
        var $dropdown = $(this);
        if ($excepto && $excepto.length && $dropdown.is($excepto)) return;
        facturasLimpiarDropdown($dropdown);
    });
}

function facturasPosicionarDropdown($dropdown) {
    var $button = facturasObtenerBotonDropdown($dropdown);
    var $menu = $dropdown.children('.factura-acciones-menu').first();
    if (!$button.length || !$menu.length) return false;

    var button = $button[0];
    var menu = $menu[0];
    var rect = button.getBoundingClientRect();
    var size = facturasMedirDropdown($menu);
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var margin = 10;
    var gap = 7;

    var abajo = viewportHeight - rect.bottom - margin;
    var arriba = rect.top - margin;
    var derecha = viewportWidth - rect.right - margin;
    var izquierda = rect.left - margin;
    var top;
    var left;
    var placement;

    if (abajo >= size.height + gap) {
        top = rect.bottom + gap;
        placement = 'bottom';
    } else if (arriba >= size.height + gap) {
        top = rect.top - size.height - gap;
        placement = 'top';
    } else if (derecha >= size.width + gap) {
        left = rect.right + gap;
        top = rect.top;
        placement = 'right';
    } else if (izquierda >= size.width + gap) {
        left = rect.left - size.width - gap;
        top = rect.top;
        placement = 'left';
    } else {
        top = arriba > abajo ? rect.top - size.height - gap : rect.bottom + gap;
        placement = arriba > abajo ? 'top-clamped' : 'bottom-clamped';
    }

    if (typeof left === 'undefined') {
        left = rect.left;
        if (left + size.width > viewportWidth - margin) {
            left = rect.right - size.width;
        }
    }

    left = Math.max(
        margin,
        Math.min(left, Math.max(margin, viewportWidth - size.width - margin))
    );

    top = Math.max(
        margin,
        Math.min(
            top,
            Math.max(
                margin,
                viewportHeight -
                Math.min(size.height, viewportHeight - margin * 2) -
                margin
            )
        )
    );

    menu.style.setProperty('display', 'block', 'important');
    menu.style.setProperty('visibility', 'visible', 'important');
    menu.style.setProperty('position', 'fixed', 'important');
    menu.style.setProperty('left', Math.round(left) + 'px', 'important');
    menu.style.setProperty('top', Math.round(top) + 'px', 'important');
    menu.style.setProperty('right', 'auto', 'important');
    menu.style.setProperty('bottom', 'auto', 'important');
    menu.style.setProperty('transform', 'none', 'important');
    menu.style.setProperty('z-index', '1985', 'important');
    menu.style.setProperty(
        'max-height',
        Math.max(90, viewportHeight - margin * 2) + 'px',
        'important'
    );
    menu.style.setProperty('overflow-y', 'auto', 'important');
    menu.setAttribute('data-df-placement', placement);

    $menu.addClass('show');
    $button.attr('aria-expanded', 'true');

    var $row = $dropdown.closest('.df-detail-row, .df-mini-card');
    $row.addClass('df-dropdown-open');

    if ($row.length) {
        $row[0].style.setProperty('transform', 'none', 'important');
    }

    facturasDropdownActivo = $dropdown;
    return true;
}

function inicializarDropdownAccionesFacturas() {
    if (
        window.IZZYActionDropdown &&
        window.IZZYActionDropdown.isGlobalManager
    ) {
        return;
    }

    var $root = $('#facturasListado').first();
    if (!$root.length) return;

    $root
        .off(
            'click.detallesFacturacionDropdown',
            '.factura-acciones-dropdown .js-acciones-toggle'
        )
        .on(
            'click.detallesFacturacionDropdown',
            '.factura-acciones-dropdown .js-acciones-toggle',
            function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var $dropdown = $(this).closest('.factura-acciones-dropdown');
                var estabaAbierto = $dropdown
                    .children('.factura-acciones-menu')
                    .hasClass('show');

                cerrarMenusAccionesFacturas($dropdown);

                if (estabaAbierto) {
                    facturasLimpiarDropdown($dropdown);
                    return;
                }

                facturasLimpiarDropdown($dropdown);
                facturasPosicionarDropdown($dropdown);
            }
        );

    $root
        .off(
            'click.detallesFacturacionDropdownItem',
            '.factura-acciones-dropdown .dropdown-item, ' +
            '.factura-acciones-dropdown .accion-item'
        )
        .on(
            'click.detallesFacturacionDropdownItem',
            '.factura-acciones-dropdown .dropdown-item, ' +
            '.factura-acciones-dropdown .accion-item',
            function() {
                var $dropdown = $(this).closest('.factura-acciones-dropdown');
                window.setTimeout(function() {
                    facturasLimpiarDropdown($dropdown);
                }, 0);
            }
        );

    $(document)
        .off('click.detallesFacturacionDropdownOutside')
        .on('click.detallesFacturacionDropdownOutside', function(e) {
            if (!$(e.target).closest('.factura-acciones-dropdown').length) {
                cerrarMenusAccionesFacturas();
            }
        });

    $(document)
        .off('show.bs.modal.detallesFacturacionDropdown')
        .on('show.bs.modal.detallesFacturacionDropdown', function() {
            cerrarMenusAccionesFacturas();
        });

    $(document)
        .off('keydown.detallesFacturacionDropdown')
        .on('keydown.detallesFacturacionDropdown', function(e) {
            if (e.key === 'Escape') cerrarMenusAccionesFacturas();
        });

    $(window)
        .off(
            'resize.detallesFacturacionDropdown ' +
            'scroll.detallesFacturacionDropdown'
        )
        .on(
            'resize.detallesFacturacionDropdown ' +
            'scroll.detallesFacturacionDropdown',
            function() {
                cerrarMenusAccionesFacturas();
            }
        );
}

/* =========================================================
   DETALLE DE FACTURA
   ========================================================= */
function cargarDetalleFactura(facturaId) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/DetallesFacturacion/DetallesFacturacion.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId
        },
        beforeSend: function() {
            resetDetalleFacturaModal();
            DFModal.facturaId = facturaId;
            DFModal.loading = true;
            $('#numero-factura-modal, #fecha-factura, #cliente-factura, #tipo-factura, #subtotal-factura, #total-factura, #notas-factura').text('');
            $('#estado-factura').html('');
            renderCargaDetalleFacturaModal();
        },
        success: function(response) {
            if (!response || response.type !== 'success' || !response.data || response.data.length === 0) {
                mostrarNotificacionFactura('error', 'Error', 'No se encontraron datos de la factura.');
                $('#modalDetalleFactura').modal('hide');
                return;
            }

            var factura = response.data[0];
            DFModal.facturaId = facturaId;
            DFModal.factura = factura;

            $('#numero-factura-modal').text(factura.numero || '');
            $('#fecha-factura').text(factura.fecha || '');
            $('#cliente-factura').text(factura.cliente || '');
            $('#tipo-factura').text(factura.tipo_documento || '');
            $('#estado-factura').html(renderEstadoFactura(factura));
            $('#subtotal-factura').text(formatMoneyFactura(factura.subtotal || 0));
            $('#total-factura').text(formatMoneyFactura(factura.total || 0));
            $('#notas-factura').text(factura.notas || 'No hay notas');

            cargarLineasDetalleFactura(facturaId, factura);

            $('#btn-imprimir-factura')
                .off('click.detallesFacturacion')
                .on('click.detallesFacturacion', function() {
                    imprimirFactura(facturaId);
                });
        },
        error: function(xhr) {
            console.error('Error al cargar factura:', xhr.responseText);
            mostrarNotificacionFactura('error', 'Error', 'Ocurrió un error al cargar el detalle de la factura.');
            $('#modalDetalleFactura').modal('hide');
        }
    });
}

function cargarLineasDetalleFactura(facturaId, factura) {
    DFModal.loading = true;
    renderCargaDetalleFacturaModal();

    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getDetalleFactura.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId,
            db_name: factura.db_name || '<?php echo DB_MAIN; ?>'
        },
        success: function(response) {
            DFModal.loading = false;

            if (response.type === 'success' && response.data && response.data.length > 0) {
                DFModal.rows = response.data.map(normalizarLineaDetalleFacturaModal);
                DFModal.page = 1;
                aplicarBusquedaDetalleFacturaModal();
            } else {
                DFModal.rows = [];
                DFModal.filtered = [];
                renderDetalleFacturaModal();
            }
        },
        error: function(xhr) {
            DFModal.loading = false;
            DFModal.rows = [];
            DFModal.filtered = [];
            console.error('Error al cargar detalles:', xhr.responseText);
            $('#dfModalEmpty').hide();
            $('#detalle-factura-body').html(
                '<div class="df-modal-empty text-danger">' +
                    '<i class="fas fa-exclamation-triangle"></i>' +
                    '<strong>Error al cargar el detalle</strong>' +
                    '<span>No fue posible consultar los productos de la factura.</span>' +
                    '<button type="button" class="btn btn-outline-primary btn-sm mt-3 btn-retry-detalle" data-id="' + escapeHtmlFactura(facturaId) + '">' +
                        '<i class="fas fa-sync-alt mr-1"></i> Reintentar' +
                    '</button>' +
                '</div>'
            );
            $('#dfModalResultsInfo').text('No se pudieron cargar los registros');
            $('#dfModalPagination').empty();
        }
    });
}

function normalizarLineaDetalleFacturaModal(item) {
    item = item || {};
    var cantidad = facturaNumero(item.cantidad);
    var precio = facturaNumero(item.precio);

    return {
        producto: String(item.producto || 'Servicio'),
        cantidad: cantidad,
        cantidadTexto: String((item.cantidad || '0') + ' ' + (item.medida || '')).trim(),
        medida: String(item.medida || ''),
        precio: precio,
        isv: facturaNumero(item.isv_valor || 0),
        descuento: facturaNumero(item.descuento || 0),
        subtotal: cantidad * precio
    };
}

function resetDetalleFacturaModal() {
    DFModal.facturaId = null;
    DFModal.factura = null;
    DFModal.rows = [];
    DFModal.filtered = [];
    DFModal.page = 1;
    DFModal.pageSize = 10;
    DFModal.view = 'detalle';
    DFModal.search = '';
    DFModal.loading = false;

    $('#dfModalSearch').val('');
    $('#dfModalPageSize').val('10');
    sincronizarBotonesVistaDetalleFacturaModal();
    $('#dfModalEmpty').hide();
    $('#detalle-factura-body').empty().removeClass('df-modal-view-miniatura').addClass('df-modal-view-detalle');
    $('#dfModalResultsInfo').text('Mostrando 0 registros');
    $('#dfModalPagination').empty();
}

function renderCargaDetalleFacturaModal() {
    $('#dfModalEmpty').hide();
    $('#detalle-factura-body')
        .removeClass('df-modal-view-detalle df-modal-view-miniatura')
        .addClass('df-modal-view-' + DFModal.view)
        .html(
            '<div class="df-modal-loading">' +
                '<div class="spinner-border text-primary" role="status" aria-hidden="true"></div>' +
                '<div class="mt-2">Cargando detalles...</div>' +
            '</div>'
        );
    $('#dfModalResultsInfo').text('Cargando registros...');
    $('#dfModalPagination').empty();
}

function sincronizarBotonesVistaDetalleFacturaModal() {
    $('[data-df-modal-view]').removeClass('active').attr('aria-pressed', 'false');
    $('[data-df-modal-view="' + DFModal.view + '"]').addClass('active').attr('aria-pressed', 'true');
}

function aplicarBusquedaDetalleFacturaModal() {
    var query = normalizarBusquedaFactura(DFModal.search);

    DFModal.filtered = !query
        ? DFModal.rows.slice()
        : DFModal.rows.filter(function(item) {
            return normalizarBusquedaFactura([
                item.producto,
                item.cantidadTexto,
                item.precio,
                item.isv,
                item.descuento,
                item.subtotal,
                formatMoneyFactura(item.precio),
                formatMoneyFactura(item.isv),
                formatMoneyFactura(item.descuento),
                formatMoneyFactura(item.subtotal)
            ].join(' ')).indexOf(query) !== -1;
        });

    var pages = totalPaginasDetalleFacturaModal();
    if (DFModal.page > pages) DFModal.page = Math.max(1, pages);
    renderDetalleFacturaModal();
}

function obtenerPaginaDetalleFacturaModal() {
    var start = (DFModal.page - 1) * DFModal.pageSize;
    return DFModal.filtered.slice(start, start + DFModal.pageSize);
}

function totalPaginasDetalleFacturaModal() {
    return Math.max(1, Math.ceil(DFModal.filtered.length / DFModal.pageSize));
}

function renderDetalleFacturaModal() {
    if (DFModal.loading) return;

    var $root = $('#detalle-factura-body');
    var rows = obtenerPaginaDetalleFacturaModal();

    $root
        .removeClass('df-modal-view-detalle df-modal-view-miniatura')
        .addClass('df-modal-view-' + DFModal.view);

    if (!DFModal.filtered.length) {
        $root.empty();
        $('#dfModalEmpty').css('display', 'flex');
        $('#dfModalResultsInfo').text('Mostrando 0 registros');
        $('#dfModalPagination').empty();
        return;
    }

    $('#dfModalEmpty').hide();

    if (DFModal.view === 'miniatura') {
        $root.html(
            '<div class="df-modal-mini-grid">' + rows.map(renderLineaDetalleFacturaMiniatura).join('') + '</div>' +
            dfModalTotals(DFModal.factura || {})
        );
    } else {
        $root.html(
            '<div class="df-modal-lines">' +
                '<div class="df-modal-lines-header">' +
                    '<div>Producto / Servicio</div>' +
                    '<div>Cantidad</div>' +
                    '<div>Precio unitario</div>' +
                    '<div>ISV</div>' +
                    '<div>Descuento</div>' +
                    '<div>Subtotal</div>' +
                '</div>' +
                '<div class="df-modal-lines-body">' + rows.map(renderLineaDetalleFacturaDetalle).join('') + '</div>' +
                dfModalTotals(DFModal.factura || {}) +
            '</div>'
        );
    }

    actualizarInfoDetalleFacturaModal();
    renderPaginacionDetalleFacturaModal();
}

function renderLineaDetalleFacturaDetalle(item) {
    return '<article class="df-modal-line-row">' +
        dfModalLineCell('Producto / Servicio', escapeHtmlFactura(item.producto), 'is-product') +
        dfModalLineCell('Cantidad', escapeHtmlFactura(item.cantidadTexto), 'is-quantity') +
        dfModalLineCell('Precio unitario', formatMoneyFactura(item.precio), 'is-money') +
        dfModalLineCell('ISV', formatMoneyFactura(item.isv), 'is-money') +
        dfModalLineCell('Descuento', formatMoneyFactura(item.descuento), 'is-money') +
        dfModalLineCell('Subtotal', formatMoneyFactura(item.subtotal), 'is-money is-line-total') +
    '</article>';
}

function renderLineaDetalleFacturaMiniatura(item) {
    return '' +
        '<article class="df-modal-mini-card">' +
            '<div class="df-modal-mini-header">' +
                '<strong>' + escapeHtmlFactura(item.producto) + '</strong>' +
                '<span>' + formatMoneyFactura(item.subtotal) + '</span>' +
            '</div>' +
            '<div class="df-modal-mini-body">' +
                dfModalMiniField('Cantidad', escapeHtmlFactura(item.cantidadTexto)) +
                dfModalMiniField('Precio unitario', formatMoneyFactura(item.precio)) +
                dfModalMiniField('ISV', formatMoneyFactura(item.isv)) +
                dfModalMiniField('Descuento', formatMoneyFactura(item.descuento)) +
            '</div>' +
        '</article>';
}

function dfModalMiniField(label, value) {
    return '<div class="df-modal-mini-field"><span>' + escapeHtmlFactura(label) + '</span><strong>' + value + '</strong></div>';
}

function dfModalLineCell(label, value, className) {
    return '<div class="df-modal-line-cell ' + (className || '') + '" data-label="' + escapeHtmlFactura(label) + '">' + value + '</div>';
}

function dfModalTotalCell(label, value, highlight) {
    return '' +
        '<div class="df-modal-total-cell ' + (highlight ? 'is-grand-total' : '') + '">' +
            '<span>' + escapeHtmlFactura(label) + '</span>' +
            '<strong>' + value + '</strong>' +
        '</div>';
}

function dfModalTotals(factura) {
    return '' +
        '<div class="df-modal-totals-grid">' +
            '<div class="df-modal-totals-label">TOTALES GENERALES</div>' +
            dfModalTotalCell('Subtotal', formatMoneyFactura(factura.subtotal || 0), false) +
            dfModalTotalCell('ISV', formatMoneyFactura(factura.isv || 0), false) +
            dfModalTotalCell('Descuento', formatMoneyFactura(factura.descuento || 0), false) +
            dfModalTotalCell('Total', formatMoneyFactura(factura.total || 0), true) +
        '</div>';
}

function actualizarInfoDetalleFacturaModal() {
    var total = DFModal.filtered.length;
    if (!total) {
        $('#dfModalResultsInfo').text('Mostrando 0 registros');
        return;
    }

    var start = ((DFModal.page - 1) * DFModal.pageSize) + 1;
    var end = Math.min(DFModal.page * DFModal.pageSize, total);
    $('#dfModalResultsInfo').text(
        'Mostrando ' + start.toLocaleString('en-US') +
        ' a ' + end.toLocaleString('en-US') +
        ' de ' + total.toLocaleString('en-US') +
        ' registros'
    );
}

function renderPaginacionDetalleFacturaModal() {
    var pages = totalPaginasDetalleFacturaModal();
    var current = Math.min(Math.max(DFModal.page, 1), pages);
    var buttons = [];

    buttons.push(dfPageButton(1, 'Inicio', 'fas fa-angle-double-left', current === 1));
    buttons.push(dfPageButton(current - 1, 'Anterior', 'fas fa-angle-left', current === 1));

    var start = Math.max(1, current - 2);
    var end = Math.min(pages, current + 2);

    if (end - start < 4) {
        if (start === 1) end = Math.min(pages, 5);
        else if (end === pages) start = Math.max(1, pages - 4);
    }

    for (var page = start; page <= end; page++) {
        buttons.push(
            '<button type="button" class="df-page-btn ' + (page === current ? 'active' : '') + '" data-page="' + page + '" ' +
                (page === current ? 'aria-current="page"' : '') + '>' + page + '</button>'
        );
    }

    buttons.push(dfPageButton(current + 1, 'Siguiente', 'fas fa-angle-right', current === pages, true));
    buttons.push(dfPageButton(pages, 'Final', 'fas fa-angle-double-right', current === pages, true));
    $('#dfModalPagination').html(buttons.join(''));
}

function exportarDetalleFacturaModalExcel() {
    if (!DFModal.factura || !DFModal.filtered.length) {
        mostrarNotificacionFactura('warning', 'Sin información', 'No hay detalle de factura para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        mostrarNotificacionFactura('error', 'Excel no disponible', 'No se encontró JSZip para generar el archivo XLSX.');
        return;
    }

    var factura = DFModal.factura;
    var headers = ['Producto / Servicio', 'Cantidad', 'Precio unitario', 'ISV', 'Descuento', 'Subtotal'];
    var rows = DFModal.filtered;
    var lastCol = 'F';
    var headerRow = 7;
    var firstDataRow = 8;
    var dataLastRow = firstDataRow + rows.length - 1;
    var totalRow = dataLastRow + 1;
    var sheetRows = [];
    var numero = String(factura.numero || 'Factura');

    sheetRows.push('<row r="1" ht="30" customHeight="1">' + dfExcelCell('A1', 'DETALLE DE FACTURA ' + numero, 1, false) + '</row>');
    sheetRows.push('<row r="2" ht="20" customHeight="1">' + dfExcelCell('A2', 'Cliente: ' + String(factura.cliente || '') + ' • Fecha: ' + String(factura.fecha || '') + ' • Tipo: ' + String(factura.tipo_documento || ''), 2, false) + '</row>');
    sheetRows.push('<row r="3" ht="18" customHeight="1">' +
        dfExcelCell('A3', 'SUBTOTAL', 6, false) +
        dfExcelCell('C3', 'ISV', 6, false) +
        dfExcelCell('E3', 'TOTAL', 6, false) +
    '</row>');
    sheetRows.push('<row r="4" ht="26" customHeight="1">' +
        dfExcelCell('A4', facturaNumero(factura.subtotal), 8, true) +
        dfExcelCell('C4', facturaNumero(factura.isv), 8, true) +
        dfExcelCell('E4', facturaNumero(factura.total), 8, true) +
    '</row>');
    sheetRows.push('<row r="5"></row>');
    sheetRows.push('<row r="6" ht="18" customHeight="1">' + dfExcelCell('A6', 'Detalle de productos / servicios', 6, false) + '</row>');
    sheetRows.push('<row r="' + headerRow + '" ht="26" customHeight="1">' + headers.map(function(header, index) {
        return dfExcelCell(dfExcelCol(index) + headerRow, header, 3, false);
    }).join('') + '</row>');

    rows.forEach(function(item, index) {
        var rr = firstDataRow + index;
        sheetRows.push('<row r="' + rr + '" ht="22" customHeight="1">' +
            dfExcelCell('A' + rr, item.producto, 4, false) +
            dfExcelCell('B' + rr, item.cantidadTexto, 4, false) +
            dfExcelCell('C' + rr, item.precio, 5, true) +
            dfExcelCell('D' + rr, item.isv, 5, true) +
            dfExcelCell('E' + rr, item.descuento, 5, true) +
            dfExcelCell('F' + rr, item.subtotal, 5, true) +
        '</row>');
    });

    sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
        dfExcelCell('A' + totalRow, 'TOTALES GENERALES', 9, false) +
        dfExcelCell('C' + totalRow, facturaNumero(factura.subtotal), 10, true) +
        dfExcelCell('D' + totalRow, facturaNumero(factura.isv), 10, true) +
        dfExcelCell('E' + totalRow, facturaNumero(factura.descuento), 10, true) +
        dfExcelCell('F' + totalRow, facturaNumero(factura.total), 10, true) +
    '</row>');

    var widths = [36, 16, 19, 18, 18, 19];
    var cols = widths.map(function(width, index) {
        return '<col min="' + (index + 1) + '" max="' + (index + 1) + '" width="' + width + '" customWidth="1"/>';
    }).join('');

    var merges = [
        '<mergeCell ref="A1:' + lastCol + '1"/>',
        '<mergeCell ref="A2:' + lastCol + '2"/>',
        '<mergeCell ref="A3:B3"/>',
        '<mergeCell ref="C3:D3"/>',
        '<mergeCell ref="E3:F3"/>',
        '<mergeCell ref="A4:B4"/>',
        '<mergeCell ref="C4:D4"/>',
        '<mergeCell ref="E4:F4"/>',
        '<mergeCell ref="A6:' + lastCol + '6"/>',
        '<mergeCell ref="A' + totalRow + ':B' + totalRow + '"/>'
    ];

    var sheetXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' + cols + '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + dataLastRow + '"/>' +
            '<mergeCells count="' + merges.length + '">' + merges.join('') + '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml = dfExcelStylesDetallesFacturacion();
    var workbookXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Detalle factura" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';
    var workbookRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>';
    var rootRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>';
    var contentTypes = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>';

    var zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbookXml);
    zip.folder('xl').file('styles.xml', typeof window.izzyExcelBordesEstilos === 'function' ? window.izzyExcelBordesEstilos(stylesXml) : stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', typeof window.izzyExcelBordesHoja === 'function' ? window.izzyExcelBordesHoja(sheetXml) : sheetXml);

    var options = {type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', compression: 'DEFLATE'};
    var promise = typeof zip.generateAsync === 'function' ? zip.generateAsync(options) : Promise.resolve(zip.generate(options));

    promise.then(function(blob) {
        descargarBlobDetallesFacturacion(blob, 'Detalle_Factura_' + dfNombreArchivoSeguro(numero) + '_' + fechaArchivoDetallesFacturacion() + '.xlsx');
    }).catch(function(error) {
        console.error('Error XLSX detalle factura:', error);
        mostrarNotificacionFactura('error', 'Error al generar Excel', 'No se pudo generar el archivo Excel del detalle.');
    });
}

function dfExcelStylesDetallesFacturacion() {
    return '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;L. &quot;#,##0.00"/></numFmts>' +
            '<fonts count="8">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="6">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF4FC"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="164" fontId="6" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
                '<xf numFmtId="0" fontId="7" fillId="5" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="164" fontId="7" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';
}

function exportarDetalleFacturaModalPdf() {
    if (!DFModal.factura || !DFModal.filtered.length) {
        mostrarNotificacionFactura('warning', 'Sin información', 'No hay detalle de factura para exportar.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        mostrarNotificacionFactura('error', 'PDF no disponible', 'No se encontraron los componentes necesarios para el PDF.');
        return;
    }

    if (!DF.marcaResuelta) {
        dfObtenerMarcaPdf(function(marca) {
            marca = marca || {};
            DF.logoDataUrl = marca.logoDataUrl || null;
            DF.empresaNombre = String(marca.empresaNombre || 'IZZY').trim() || 'IZZY';
            DF.marcaResuelta = true;
            exportarDetalleFacturaModalPdf();
        });
        return;
    }

    var factura = DFModal.factura;
    var numero = String(factura.numero || 'Factura');
    var logo = dfPdfLogoPlate(DF.logoDataUrl, DF.empresaNombre);
    var body = [[
        {text:'PRODUCTO / SERVICIO', style:'th', fillColor:'#17324D'},
        {text:'CANTIDAD', style:'th', fillColor:'#17324D'},
        {text:'PRECIO UNIT.', style:'th', fillColor:'#17324D'},
        {text:'ISV', style:'th', fillColor:'#17324D'},
        {text:'DESCUENTO', style:'th', fillColor:'#17324D'},
        {text:'SUBTOTAL', style:'th', fillColor:'#17324D'}
    ]];

    DFModal.filtered.forEach(function(item, index) {
        var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
        body.push([
            {text:item.producto, fillColor:fill},
            {text:item.cantidadTexto, fillColor:fill, alignment:'center'},
            {text:formatMoneyFactura(item.precio), fillColor:fill, alignment:'right'},
            {text:formatMoneyFactura(item.isv), fillColor:fill, alignment:'right'},
            {text:formatMoneyFactura(item.descuento), fillColor:fill, alignment:'right'},
            {text:formatMoneyFactura(item.subtotal), fillColor:fill, alignment:'right', bold:true, color:'#087E3F'}
        ]);
    });

    body.push([
        {text:'TOTALES GENERALES', colSpan:2, bold:true, fillColor:'#EAF4FC', color:'#172B4D'}, {},
        {text:formatMoneyFactura(factura.subtotal || 0), bold:true, fillColor:'#EAF4FC', alignment:'right'},
        {text:formatMoneyFactura(factura.isv || 0), bold:true, fillColor:'#EAF4FC', alignment:'right'},
        {text:formatMoneyFactura(factura.descuento || 0), bold:true, fillColor:'#EAF4FC', alignment:'right'},
        {text:formatMoneyFactura(factura.total || 0), bold:true, fillColor:'#EAF4FC', alignment:'right', color:'#087E3F'}
    ]);

    var doc = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [28,28,28,34],
        header: function() {
            return {margin:[28,12,28,0], canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};
        },
        footer: function(currentPage, pageCount) {
            return {
                margin:[28,8,28,0],
                columns:[
                    {text:(DF.empresaNombre || 'IZZY') + ' • Detalle de factura',fontSize:7,color:'#7A869A'},
                    {text:'Página ' + currentPage + ' de ' + pageCount,fontSize:7,color:'#7A869A',alignment:'right'}
                ]
            };
        },
        content: [
            {
                table:{widths:[100,'*',150],body:[[
                    {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[logo]},
                    {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[
                        {text:'DETALLE DE FACTURA ' + numero,fontSize:16,bold:true,color:'#FFFFFF'},
                        {text:'Productos, impuestos, descuentos y total',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}
                    ]},
                    {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,12,10],stack:[
                        {text:'DOCUMENTO',fontSize:6.5,bold:true,color:'#72E2E5',alignment:'right'},
                        {text:String(factura.fecha || ''),fontSize:9,bold:true,color:'#FFFFFF',alignment:'right',margin:[0,3,0,0]},
                        {text:String(factura.tipo_documento || ''),fontSize:6.5,color:'#D8E5F0',alignment:'right',margin:[0,2,0,0]}
                    ]}
                ]]},
                layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},
                margin:[0,0,0,10]
            },
            {
                table:{widths:['*','*','*'],body:[
                    [
                        dfPdfSummaryCell('FECHA', String(factura.fecha || '—')),
                        dfPdfSummaryCell('CLIENTE', String(factura.cliente || '—')),
                        dfPdfSummaryCell('TIPO', String(factura.tipo_documento || '—'))
                    ],
                    [
                        dfPdfSummaryCell('ESTADO', textoEstadoFacturaPlano(factura)),
                        dfPdfSummaryCell('SUBTOTAL', formatMoneyFactura(factura.subtotal || 0)),
                        dfPdfSummaryCell('TOTAL', formatMoneyFactura(factura.total || 0))
                    ]
                ]},
                layout:{
                    hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},
                    hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}
                },
                margin:[0,0,0,12]
            },
            {text:'DETALLE DE PRODUCTOS / SERVICIOS',fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]},
            {
                table:{headerRows:1,widths:['*',72,90,78,82,92],body:body},
                layout:{
                    hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},
                    hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},
                    paddingLeft:function(){return 5;},paddingRight:function(){return 5;},
                    paddingTop:function(){return 6;},paddingBottom:function(){return 6;}
                }
            },
            {text:'NOTAS',fontSize:7,bold:true,color:'#17324D',margin:[0,12,0,4]},
            {text:String(factura.notas || 'No hay notas'),fontSize:8,color:'#52627A'}
        ],
        styles:{th:{fontSize:6.7,bold:true,color:'#FFFFFF',alignment:'center'}},
        defaultStyle:{fontSize:8,color:'#253858'}
    };

    var pdf = pdfMake.createPdf(doc);
    var fileName = 'Detalle_Factura_' + dfNombreArchivoSeguro(numero) + '_' + fechaArchivoDetallesFacturacion() + '.pdf';
    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(url) { abrirModalPdfPublico(url, 'Detalle de Factura ' + numero, fileName); });
    } else if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function(base64) { abrirModalPdfPublico('data:application/pdf;base64,' + base64, 'Detalle de Factura ' + numero, fileName); });
    } else {
        mostrarNotificacionFactura('error', 'PDF no disponible', 'La versión actual de pdfMake no permite previsualización compatible.');
    }
}

function dfNombreArchivoSeguro(value) {
    return String(value || 'Factura')
        .replace(/[^a-zA-Z0-9_-]+/g, '_')
        .replace(/^_+|_+$/g, '') || 'Factura';
}

/* =========================================================
   PAGO / IMPRESIÓN
   ========================================================= */
function pagarFactura(facturaId) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/pagarFactura.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId,
            db_name: '<?php echo DB_MAIN; ?>'
        },
        success: function(response) {
            if (response.type === 'success') {
                mostrarNotificacionFactura('success', 'Éxito', response.message || 'Factura pagada correctamente.');
                cargarFacturas(false);
            } else {
                mostrarNotificacionFactura('error', 'Error', response.message || 'No se pudo procesar el pago.');
            }
        },
        error: function(xhr) {
            console.error('Error al pagar factura:', xhr.responseText);
            mostrarNotificacionFactura('error', 'Error', 'Ocurrió un error al procesar el pago.');
        }
    });
}

function imprimirFactura(facturaId) {
    viewReport({
        id: facturaId,
        type: 'Factura_carta_izzy',
        db: '<?php echo DB_MAIN; ?>'
    });
}

/* =========================================================
   EXCEL XLSX - NÚMEROS REALES + L. #,##0.00
   ========================================================= */
function dfExcelCol(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function dfXmlEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function dfExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var number = Number(value);

        if (!isNaN(number)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + number + '</v></c>';
        }
    }

    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' + dfXmlEscape(raw) + '</t></is></c>';
}

function dfExportRows() {
    return DF.filtered.map(function(row) {
        return [
            row.fecha || '',
            row.numero || '',
            row.cliente || '',
            row.tipo_documento || '',
            textoEstadoFacturaPlano(row),
            facturaNumero(row.subtotal),
            facturaNumero(row.isv),
            facturaNumero(row.descuento),
            facturaNumero(row.total)
        ];
    });
}

function dfResumenExportacion() {
    return DF.filtered.reduce(function(acc, row) {
        acc.subtotal += facturaNumero(row.subtotal);
        acc.total += facturaNumero(row.total);
        acc.isv += facturaNumero(row.isv);
        acc.descuento += facturaNumero(row.descuento);
        return acc;
    }, {
        subtotal: 0,
        total: 0,
        isv: 0,
        descuento: 0
    });
}

function exportarDetallesFacturacionExcel() {
    if (!DF.filtered.length) {
        mostrarNotificacionFactura('warning', 'Sin información', 'No hay facturas para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        mostrarNotificacionFactura('error', 'Excel no disponible', 'No se encontró JSZip para generar el archivo XLSX.');
        return;
    }

    var headers = ['Fecha', 'Número', 'Cliente', 'Tipo', 'Estado', 'Subtotal', 'ISV', 'Descuento', 'Total'];
    var rows = dfExportRows();
    var summary = dfResumenExportacion();
    var lastCol = dfExcelCol(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var dataLastRow = firstDataRow + rows.length - 1;
    var totalRow = dataLastRow + 1;
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' + dfExcelCell('A1', 'IZZY • DETALLES DE FACTURACIÓN', 1, false) + '</row>');
    sheetRows.push('<row r="2" ht="20" customHeight="1">' + dfExcelCell('A2', 'Historial, impuestos, descuentos y total • Generado: ' + new Date().toLocaleDateString('es-HN'), 2, false) + '</row>');

    sheetRows.push('<row r="3" ht="18" customHeight="1">' +
        dfExcelCell('A3', 'FACTURAS', 6, false) +
        dfExcelCell('C3', 'TOTAL', 6, false) +
        dfExcelCell('F3', 'ISV', 6, false) +
        dfExcelCell('H3', 'DESCUENTO', 6, false) +
    '</row>');

    sheetRows.push('<row r="4" ht="26" customHeight="1">' +
        dfExcelCell('A4', DF.filtered.length, 7, true) +
        dfExcelCell('C4', summary.total, 8, true) +
        dfExcelCell('F4', summary.isv, 8, true) +
        dfExcelCell('H4', summary.descuento, 8, true) +
    '</row>');

    sheetRows.push('<row r="5"></row>');
    sheetRows.push('<row r="6" ht="18" customHeight="1">' + dfExcelCell('A6', 'Detalle de registros filtrados', 6, false) + '</row>');

    sheetRows.push('<row r="' + headerRow + '" ht="26" customHeight="1">' + headers.map(function(header, index) {
        return dfExcelCell(dfExcelCol(index) + headerRow, header, 3, false);
    }).join('') + '</row>');

    rows.forEach(function(row, rowIndex) {
        var rr = firstDataRow + rowIndex;
        var cells = row.map(function(value, colIndex) {
            var isMoney = colIndex >= 5;
            return dfExcelCell(dfExcelCol(colIndex) + rr, value, isMoney ? 5 : 4, isMoney);
        }).join('');

        sheetRows.push('<row r="' + rr + '" ht="22" customHeight="1">' + cells + '</row>');
    });

    sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
        dfExcelCell('A' + totalRow, 'TOTALES GENERALES', 9, false) +
        dfExcelCell('F' + totalRow, rows.reduce(function(t, r) { return t + facturaNumero(r[5]); }, 0), 10, true) +
        dfExcelCell('G' + totalRow, rows.reduce(function(t, r) { return t + facturaNumero(r[6]); }, 0), 10, true) +
        dfExcelCell('H' + totalRow, rows.reduce(function(t, r) { return t + facturaNumero(r[7]); }, 0), 10, true) +
        dfExcelCell('I' + totalRow, rows.reduce(function(t, r) { return t + facturaNumero(r[8]); }, 0), 10, true) +
    '</row>');

    var widths = [14, 27, 34, 15, 24, 18, 18, 18, 19];
    var cols = widths.map(function(width, index) {
        return '<col min="' + (index + 1) + '" max="' + (index + 1) + '" width="' + width + '" customWidth="1"/>';
    }).join('');

    var merges = [
        '<mergeCell ref="A1:' + lastCol + '1"/>',
        '<mergeCell ref="A2:' + lastCol + '2"/>',
        '<mergeCell ref="A3:B3"/>',
        '<mergeCell ref="C3:E3"/>',
        '<mergeCell ref="F3:G3"/>',
        '<mergeCell ref="H3:I3"/>',
        '<mergeCell ref="A4:B4"/>',
        '<mergeCell ref="C4:E4"/>',
        '<mergeCell ref="F4:G4"/>',
        '<mergeCell ref="H4:I4"/>',
        '<mergeCell ref="A6:' + lastCol + '6"/>',
        '<mergeCell ref="A' + totalRow + ':E' + totalRow + '"/>'
    ];

    var sheetXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' + cols + '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + dataLastRow + '"/>' +
            '<mergeCells count="' + merges.length + '">' + merges.join('') + '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;L. &quot;#,##0.00"/></numFmts>' +
            '<fonts count="8">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="6">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF4FC"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="164" fontId="6" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
                '<xf numFmtId="0" fontId="7" fillId="5" borderId="1" xfId="0" applyBorder="1"/>' +
                '<xf numFmtId="164" fontId="7" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Facturación" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var contentTypes = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>';

    var zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbookXml);
    zip.folder('xl').file(
        'styles.xml',
        typeof window.izzyExcelBordesEstilos === 'function'
            ? window.izzyExcelBordesEstilos(stylesXml)
            : stylesXml
    );
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file(
        'sheet1.xml',
        typeof window.izzyExcelBordesHoja === 'function'
            ? window.izzyExcelBordesHoja(sheetXml)
            : sheetXml
    );

    var options = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promise = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(options)
        : Promise.resolve(zip.generate(options));

    promise
        .then(function(blob) {
            descargarBlobDetallesFacturacion(blob, 'Detalles_Facturacion_' + fechaArchivoDetallesFacturacion() + '.xlsx');
        })
        .catch(function(error) {
            console.error('Error XLSX Detalles Facturación:', error);
            mostrarNotificacionFactura('error', 'Error al generar Excel', 'No se pudo generar el archivo Excel.');
        });
}

/* =========================================================
   PDF PREMIUM - MISMO DINERO / MISMO DISEÑO
   ========================================================= */
function dfObtenerMarcaPdf(callback) {
    if (typeof window.izzyObtenerMarcaPdf === 'function') {
        window.izzyObtenerMarcaPdf(function(marca) {
            marca = marca || {};
            callback({
                logoDataUrl: (
                    typeof marca.logoDataUrl === 'string' &&
                    marca.logoDataUrl.indexOf('data:image/') === 0
                ) ? marca.logoDataUrl : null,
                empresaNombre: String(marca.empresaNombre || 'EMPRESA').trim() || 'EMPRESA'
            });
        });
        return;
    }

    /*
     * Respaldo seguro por si este módulo se ejecuta sin el helper global.
     * No consulta get_image.php otra vez: evita repetir errores/CORS cuando
     * el cliente simplemente no tiene logo configurado.
     */
    var logo = (
        typeof imagen !== 'undefined' &&
        typeof imagen === 'string' &&
        imagen.indexOf('data:image/') === 0
    ) ? imagen : null;

    var empresa = '';
    if (typeof window.izzyNombreEmpresaDesdeDom === 'function') {
        empresa = window.izzyNombreEmpresaDesdeDom();
    }

    if (!empresa) {
        var db = String(
            window.IZZY_DB_ACTUAL ||
            (typeof DB_MAIN !== 'undefined' ? DB_MAIN : '') ||
            ''
        ).trim();

        empresa = db
            .replace(/_izzy$/i, '')
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();
    }

    callback({
        logoDataUrl: logo,
        empresaNombre: empresa || 'EMPRESA'
    });
}

function dfPdfLogoPlate(logoDataUrl, empresaNombre) {
    var tieneLogo = (
        typeof logoDataUrl === 'string' &&
        logoDataUrl.indexOf('data:image/') === 0
    );

    if (tieneLogo) {
        return {
            table: {
                widths: ['*'],
                body: [[{
                    image: logoDataUrl,
                    fit: [62, 36],
                    alignment: 'center',
                    margin: [7, 5, 7, 5],
                    fillColor: '#FFFFFF'
                }]]
            },
            layout: {
                hLineColor: function() { return '#DDE3EA'; },
                vLineColor: function() { return '#DDE3EA'; },
                hLineWidth: function() { return .5; },
                vLineWidth: function() { return .5; },
                paddingLeft: function() { return 0; },
                paddingRight: function() { return 0; },
                paddingTop: function() { return 0; },
                paddingBottom: function() { return 0; }
            }
        };
    }

    var nombre = String(empresaNombre || 'EMPRESA').trim() || 'EMPRESA';
    var fontSize = nombre.length > 32 ? 8.5 : (nombre.length > 22 ? 10 : 12.5);

    return {
        table: {
            widths: ['*'],
            body: [[{
                text: nombre.toUpperCase(),
                fontSize: fontSize,
                bold: true,
                color: '#17324D',
                alignment: 'center',
                margin: [7, 10, 7, 10],
                fillColor: '#FFFFFF'
            }]]
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .5; },
            vLineWidth: function() { return .5; },
            paddingLeft: function() { return 0; },
            paddingRight: function() { return 0; },
            paddingTop: function() { return 0; },
            paddingBottom: function() { return 0; }
        }
    };
}

function textoFiltrosDetallesFacturacion() {
    return 'Tipo: ' + ($('#tipo_factura option:selected').text() || 'Todos') +
        ' | Estado: ' + ($('#estado_factura option:selected').text() || 'Todos') +
        ' | Fechas: ' + ($('#fecha_inicio').val() || 'Sin inicio') + ' a ' + ($('#fecha_fin').val() || 'Sin fin') +
        ' | Filtro factura: ' + ($('#numero_factura').val() || 'Sin filtro') +
        ' | Búsqueda: ' + ($('#dfListadoSearch').val() || 'Sin búsqueda');
}

function exportarDetallesFacturacionPdf() {
    if (!DF.filtered.length) {
        mostrarNotificacionFactura(
            'warning',
            'Sin información',
            'No hay facturas para exportar.'
        );
        return;
    }

    if (typeof pdfMake === 'undefined') {
        mostrarNotificacionFactura(
            'error',
            'PDF no disponible',
            'No se encontró pdfMake.'
        );
        return;
    }

    if (typeof abrirModalPdfPublico !== 'function') {
        mostrarNotificacionFactura(
            'error',
            'Visor PDF no disponible',
            'No se encontró el modal PDF público.'
        );
        return;
    }

    if (!DF.marcaResuelta) {
        dfObtenerMarcaPdf(function(marca) {
            marca = marca || {};
            DF.logoDataUrl = marca.logoDataUrl || null;
            DF.empresaNombre = String(marca.empresaNombre || 'EMPRESA').trim() || 'EMPRESA';
            DF.marcaResuelta = true;
            exportarDetallesFacturacionPdf();
        });
        return;
    }

    var rows = dfExportRows();
    var summary = dfResumenExportacion();
    var logo = dfPdfLogoPlate(DF.logoDataUrl, DF.empresaNombre);

    var encabezado = {
        table: {
            widths: [100, '*', 150],
            body: [[
                {
                    border: [false, false, false, false],
                    fillColor: '#17324D',
                    margin: [12, 10, 0, 10],
                    stack: [logo]
                },
                {
                    border: [false, false, false, false],
                    fillColor: '#17324D',
                    margin: [0, 10, 0, 10],
                    stack: [
                        {
                            text: 'REPORTE DETALLES DE FACTURACIÓN',
                            fontSize: 16,
                            bold: true,
                            color: '#FFFFFF'
                        },
                        {
                            text: 'Historial, impuestos, descuentos y total',
                            fontSize: 7.5,
                            color: '#D8E5F0',
                            margin: [0, 2, 0, 0]
                        }
                    ]
                },
                {
                    border: [false, false, false, false],
                    fillColor: '#17324D',
                    margin: [0, 10, 12, 10],
                    stack: [
                        {
                            text: 'REPORTE EJECUTIVO',
                            fontSize: 6.5,
                            bold: true,
                            color: '#72E2E5',
                            alignment: 'right'
                        },
                        {
                            text: new Date().toLocaleDateString('es-HN'),
                            fontSize: 9,
                            bold: true,
                            color: '#FFFFFF',
                            alignment: 'right',
                            margin: [0, 3, 0, 0]
                        },
                        {
                            text: DF.filtered.length + ' registro(s) filtrado(s)',
                            fontSize: 6.5,
                            color: '#D8E5F0',
                            alignment: 'right',
                            margin: [0, 2, 0, 0]
                        }
                    ]
                }
            ]]
        },
        layout: {
            hLineWidth: function() { return 0; },
            vLineWidth: function() { return 0; }
        },
        margin: [0, 0, 0, 10]
    };

    var filtros = {
        table: {
            widths: ['*'],
            body: [[{
                text: 'Filtros aplicados: ' + textoFiltrosDetallesFacturacion(),
                fontSize: 6.8,
                color: '#52627A',
                margin: [10, 7, 10, 7],
                fillColor: '#F7F9FC'
            }]]
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .6; },
            vLineWidth: function() { return .6; }
        },
        margin: [0, 0, 0, 10]
    };

    var resumen = {
        table: {
            widths: ['*', '*', '*', '*'],
            body: [[
                dfPdfSummaryCell('FACTURAS', String(DF.filtered.length)),
                dfPdfSummaryCell('TOTAL', formatMoneyFactura(summary.total)),
                dfPdfSummaryCell('ISV', formatMoneyFactura(summary.isv)),
                dfPdfSummaryCell('DESCUENTO', formatMoneyFactura(summary.descuento))
            ]]
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .6; },
            vLineWidth: function() { return .6; }
        },
        margin: [0, 0, 0, 12]
    };

    var body = [[
        { text: 'FECHA', style: 'th', fillColor: '#17324D' },
        { text: 'NÚMERO', style: 'th', fillColor: '#17324D' },
        { text: 'CLIENTE', style: 'th', fillColor: '#17324D' },
        { text: 'TIPO', style: 'th', fillColor: '#17324D' },
        { text: 'ESTADO', style: 'th', fillColor: '#17324D' },
        { text: 'SUBTOTAL', style: 'th', fillColor: '#17324D' },
        { text: 'ISV', style: 'th', fillColor: '#17324D' },
        { text: 'DESCUENTO', style: 'th', fillColor: '#17324D' },
        { text: 'TOTAL', style: 'th', fillColor: '#17324D' }
    ]];

    rows.forEach(function(row, index) {
        var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

        body.push(row.map(function(value, colIndex) {
            var isMoney = colIndex >= 5;

            return {
                text: isMoney
                    ? formatMoneyFactura(value)
                    : String(value === undefined || value === null ? '' : value),
                fillColor: fill,
                alignment: isMoney ? 'right' : 'left'
            };
        }));
    });

    body.push([
        {
            text: 'TOTALES GENERALES',
            colSpan: 5,
            bold: true,
            color: '#172B4D',
            fillColor: '#EAF4FC',
            alignment: 'left'
        },
        {},
        {},
        {},
        {},
        {
            text: formatMoneyFactura(summary.subtotal),
            bold: true,
            color: '#172B4D',
            fillColor: '#EAF4FC',
            alignment: 'right'
        },
        {
            text: formatMoneyFactura(summary.isv),
            bold: true,
            color: '#172B4D',
            fillColor: '#EAF4FC',
            alignment: 'right'
        },
        {
            text: formatMoneyFactura(summary.descuento),
            bold: true,
            color: '#172B4D',
            fillColor: '#EAF4FC',
            alignment: 'right'
        },
        {
            text: formatMoneyFactura(summary.total),
            bold: true,
            color: '#087E3F',
            fillColor: '#EAF4FC',
            alignment: 'right'
        }
    ]);

    var detalle = {
        table: {
            headerRows: 1,
            widths: [58, 96, '*', 56, 88, 66, 60, 66, 72],
            body: body
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .55; },
            vLineWidth: function() { return .55; },
            paddingLeft: function() { return 5; },
            paddingRight: function() { return 5; },
            paddingTop: function() { return 6; },
            paddingBottom: function() { return 6; }
        }
    };

    var doc = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [28, 28, 28, 34],
        header: function() {
            return {
                margin: [28, 12, 28, 0],
                canvas: [{
                    type: 'line',
                    x1: 0,
                    y1: 0,
                    x2: 736,
                    y2: 0,
                    lineWidth: 2,
                    lineColor: '#0EA5A8'
                }]
            };
        },
        footer: function(currentPage, pageCount) {
            return {
                margin: [28, 8, 28, 0],
                columns: [
                    {
                        text: (DF.empresaNombre || 'EMPRESA') + ' • Detalles de facturación',
                        fontSize: 7,
                        color: '#7A869A'
                    },
                    {
                        text: 'Página ' + currentPage + ' de ' + pageCount,
                        fontSize: 7,
                        color: '#7A869A',
                        alignment: 'right'
                    }
                ]
            };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            {
                text: DF.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE',
                fontSize: 7,
                bold: true,
                color: '#17324D',
                margin: [0, 1, 0, 7]
            },
            detalle
        ],
        styles: {
            th: {
                fontSize: 6.4,
                bold: true,
                color: '#FFFFFF',
                alignment: 'center'
            }
        },
        defaultStyle: {
            fontSize: 8,
            color: '#253858'
        }
    };

    var pdf = pdfMake.createPdf(doc);
    var fileName = 'Detalles_Facturacion_' + fechaArchivoDetallesFacturacion() + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(url) {
            abrirModalPdfPublico(url, 'Reporte Detalles de Facturación', fileName);
        });
        return;
    }

    if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function(base64) {
            abrirModalPdfPublico(
                'data:application/pdf;base64,' + base64,
                'Reporte Detalles de Facturación',
                fileName
            );
        });
        return;
    }

    mostrarNotificacionFactura(
        'error',
        'PDF no disponible',
        'La versión actual de pdfMake no permite previsualización compatible.'
    );
}

function dfPdfSummaryCell(label, value) {
    return {
        fillColor: '#F7F9FC',
        margin: [8, 7, 8, 7],
        stack: [
            { text: label, fontSize: 6.3, bold: true, color: '#6B778C' },
            { text: value, fontSize: 13, bold: true, color: '#172B4D', margin: [0, 2, 0, 0] }
        ]
    };
}

function fechaArchivoDetallesFacturacion() {
    return new Date().toISOString().slice(0, 10);
}

function descargarBlobDetallesFacturacion(blob, fileName) {
    var anchor = document.createElement('a');
    var url = URL.createObjectURL(blob);

    anchor.href = url;
    anchor.download = fileName;
    document.body.appendChild(anchor);
    anchor.click();
    document.body.removeChild(anchor);

    window.setTimeout(function() {
        URL.revokeObjectURL(url);
    }, 1000);
}

/* =========================================================
   UTILIDADES / NOTIFICACIONES
   ========================================================= */
function escapeHtmlFactura(text) {
    if (text === null || text === undefined) {
        return '';
    }

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function mostrarNotificacionFactura(type, title, message) {
    var allowed = ['success', 'error', 'info', 'warning'];
    var safeType = allowed.indexOf(type) !== -1 ? type : 'info';

    if (typeof showNotify === 'function') {
        showNotify(safeType, title, message);
        return;
    }

    console.error('[showNotify no disponible] ' + title + ': ' + message);
}
</script>
