<script>
// reporteCompras.php
// IZZY | Reporte de Compras - DIV / KPI / Excel / PDF

$(() => {
    rcmpAplicarVistaResponsiveInicial();
    getReporteCompras();

    $('#form_main_compras #tipo_compras_reporte').val(1);
    try {
        $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
    } catch (e) {}

    $('#form_main_compras').off('submit.reporteCompras');
    $('#form_main_compras').on('submit.reporteCompras', function(e) {
        e.preventDefault();
        listar_reporte_compras();
    });

    $('#form_main_compras').off('reset.reporteCompras');
    $('#form_main_compras').on('reset.reporteCompras', function() {
        var $form = $(this);

        setTimeout(function() {
            try {
                $form.find('.selectpicker').selectpicker('refresh');
            } catch (e) {}

            $('#form_main_compras #tipo_compras_reporte').val(1);

            try {
                $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
            } catch (e) {}

            listar_reporte_compras();
        }, 100);
    });

    $('#rcmpBtnActualizar').off('click.rcmp').on('click.rcmp', listar_reporte_compras);

    $('#rcmpPageSize').off('change.rcmp').on('change.rcmp', function() {
        RCMP.pageSize = parseInt(this.value, 10) || 10;
        RCMP.page = 1;
        renderReporteCompras();
    });

    $('#rcmpSearch').off('input.rcmp').on('input.rcmp', function() {
        RCMP.search = this.value || '';
        RCMP.page = 1;
        renderReporteCompras();
    });

    $('#rcmpSearchClear').off('click.rcmp').on('click.rcmp', function() {
        $('#rcmpSearch').val('').focus();
        RCMP.search = '';
        RCMP.page = 1;
        renderReporteCompras();
    });

    $('[data-rcmp-view]').off('click.rcmp').on('click.rcmp', function() {
        $('[data-rcmp-view]').removeClass('active');
        $(this).addClass('active');

        RCMP.view = $(this).attr('data-rcmp-view') === 'miniatura'
            ? 'miniatura'
            : 'detalle';

        RCMP.page = 1;
        renderReporteCompras();
    });

    $('#rcmpBtnExcel').off('click.rcmp').on('click.rcmp', exportarReporteComprasExcel);
    $('#rcmpBtnPdf').off('click.rcmp').on('click.rcmp', exportarReporteComprasPdf);

    $(document)
        .off('click.rcmpToggle', '.rv-toggle-section')
        .on('click.rcmpToggle', '.rv-toggle-section', function() {
            var $button = $(this);
            var $target = $($button.attr('data-target'));

            if (!$target.length) return;

            var ocultar = $target.is(':visible');

            $target.stop(true, true).slideToggle(160);

            $button.find('span').text(ocultar ? 'Mostrar' : 'Ocultar');
            $button.find('i')
                .toggleClass('fa-chevron-up', !ocultar)
                .toggleClass('fa-chevron-down', ocultar);
        });

    $(document)
        .off('click.rcmpAction', '.rcmp-action')
        .on('click.rcmpAction', '.rcmp-action', function(e) {
            e.preventDefault();

            var index = parseInt($(this).attr('data-index'), 10);
            var action = $(this).attr('data-action');
            var row = RCMP.filtered[index];

            if (!row) {
                showNotify('error', 'Error', 'No se pudo obtener la compra seleccionada.');
                return false;
            }

            ejecutarAccionReporteCompra(action, row);
            return false;
        });

    listar_reporte_compras();
});


/* =========================================================
   ESTADO / HELPERS
   ========================================================= */

var RCMP = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    view: 'detalle',
    search: ''
};

function rcmpEsMovil() {
    return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
}

function rcmpSincronizarBotonesVista() {
    $('[data-rcmp-view]').removeClass('active').attr('aria-pressed', 'false');
    $('[data-rcmp-view="' + RCMP.view + '"]').addClass('active').attr('aria-pressed', 'true');
}

function rcmpAplicarVistaResponsiveInicial() {
    if (rcmpEsMovil()) {
        RCMP.view = 'miniatura';
    }
    rcmpSincronizarBotonesVista();
}

var rcmpResponsiveTimer = null;
$(window)
    .off('resize.rcmpResponsive orientationchange.rcmpResponsive')
    .on('resize.rcmpResponsive orientationchange.rcmpResponsive', function() {
        clearTimeout(rcmpResponsiveTimer);
        rcmpResponsiveTimer = setTimeout(function() {
            if (rcmpEsMovil() && RCMP.view !== 'miniatura') {
                RCMP.view = 'miniatura';
                RCMP.page = 1;
                rcmpSincronizarBotonesVista();
                renderReporteCompras();
            }
        }, 120);
    });

function rcmpNum(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    value = String(value)
        .replace(/<[^>]*>/g, '')
        .replace(/L\./g, '')
        .replace(/L/g, '')
        .replace(/,/g, '')
        .trim();

    var number = parseFloat(value);
    return isNaN(number) ? 0 : number;
}

function rcmpMoney(value) {
    return 'L. ' + rcmpNum(value).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function rcmpEsc(value) {
    return $('<div>').text(
        value === null || value === undefined ? '' : String(value)
    ).html();
}

function rcmpRows(response) {
    if (typeof response === 'string') {
        try {
            response = JSON.parse(response);
        } catch (e) {
            return [];
        }
    }

    if (Array.isArray(response)) return response;
    if (response && Array.isArray(response.data)) return response.data;
    if (response && Array.isArray(response.aaData)) return response.aaData;

    return [];
}

function rcmpSearchText(row) {
    try {
        return JSON.stringify(row || {}).toLowerCase();
    } catch (e) {
        return '';
    }
}

function rcmpFilter() {
    var q = String(RCMP.search || '').trim().toLowerCase();

    RCMP.filtered = !q
        ? RCMP.rows.slice()
        : RCMP.rows.filter(function(row) {
            return rcmpSearchText(row).indexOf(q) !== -1;
        });

    var pages = Math.max(1, Math.ceil(RCMP.filtered.length / RCMP.pageSize));

    if (RCMP.page > pages) RCMP.page = pages;
    if (RCMP.page < 1) RCMP.page = 1;
}

function rcmpTotals(rows) {
    return rows.reduce(function(acc, row) {
        acc.subtotal += rcmpNum(row.subtotal);
        acc.isv += rcmpNum(row.isv);
        acc.descuento += rcmpNum(row.descuento);
        acc.total += rcmpNum(row.total);
        return acc;
    }, {
        subtotal: 0,
        isv: 0,
        descuento: 0,
        total: 0
    });
}

function rcmpTypeBadge(row) {
    var text = row.tipo_documento || '';
    var credit = text === 'Crédito';

    return '<span class="badge badge-pill ' + (credit ? 'badge-warning' : 'badge-success') + '">' +
        '<i class="fas ' + (credit ? 'fa-clock' : 'fa-check-circle') + ' mr-1"></i>' +
        rcmpEsc(text) +
    '</span>';
}

function rcmpMiniField(label, value) {
    return '' +
        '<div class="rv-mini-field">' +
            '<span>' + rcmpEsc(label) + '</span>' +
            '<strong>' + value + '</strong>' +
        '</div>';
}

function rcmpEmpty() {
    return '' +
        '<div class="rv-empty">' +
            '<i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong>' +
            '<span>No hay información que coincida con los criterios actuales.</span>' +
        '</div>';
}


/* =========================================================
   ACCIONES
   ========================================================= */

function rcmpDropdown(row, index) {
    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +

            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item table_reportes rcmp-action" data-action="print" data-index="' + index + '">' +
                    '<span class="accion-icon accion-icon-success"><i class="fas fa-file-download"></i></span>' +
                    '<span class="accion-label">Factura</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar rcmp-action" data-action="anular" data-index="' + index + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span>' +
                    '<span class="accion-label">Anular</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function ejecutarAccionReporteCompra(action, data) {
    if (!data || !data.compras_id) {
        showNotify('error', 'Error', 'No se pudo obtener la compra seleccionada.');
        return;
    }

    if (action === 'print') {
        printPurchase(data.compras_id);
        return;
    }

    if (action === 'anular') {
        if (typeof validarAdminSistema !== 'function') {
            showNotify(
                'error',
                'Validación no disponible',
                'No está cargado el JS de autenticación administrativa.'
            );
            return;
        }

        var compraId = data.compras_id;
        var numeroCompra =
            data.number ||
            data.numero ||
            data.compra ||
            data.numero_compra ||
            data.factura_compra ||
            data.compras_id;

        validarAdminSistema(function(permitido) {
            if (permitido !== true) return;

            anularCompra(compraId);
        }, {
            mensaje: 'Para anular esta compra debe validar un administrador.',
            modulo: 'Compras',
            accion: 'Anular compra',
            referencia_id: compraId,
            referencia_texto: numeroCompra,
            motivo: 'Validación requerida para anular compra'
        });
    }
}


/* =========================================================
   RENDER
   ========================================================= */

function renderReporteCompras() {
    rcmpFilter();

    var start = (RCMP.page - 1) * RCMP.pageSize;
    var pageRows = RCMP.filtered.slice(start, start + RCMP.pageSize);

    var grid =
        '118px 90px 95px minmax(110px,.9fr) minmax(170px,1.45fr) ' +
        'minmax(130px,1.05fr) 100px 92px 100px 115px';

    var html = '';

    if (!pageRows.length) {
        html = rcmpEmpty();
    } else if (RCMP.view === 'miniatura') {
        html = pageRows.map(function(row, idx) {
            var index = start + idx;

            return '' +
                '<div class="rv-mini-card">' +
                    '<div class="rv-mini-head">' +
                        '<div>' +
                            '<div class="rv-mini-title">' + rcmpEsc(row.proveedor || 'Sin proveedor') + '</div>' +
                            '<span class="rv-mini-sub">' +
                                rcmpEsc(row.numero || '') + ' • ' + rcmpEsc(row.fecha || '') +
                            '</span>' +
                        '</div>' +
                        rcmpDropdown(row, index) +
                    '</div>' +

                    '<div class="rv-mini-body">' +
                        rcmpMiniField('Tipo', rcmpTypeBadge(row)) +
                        rcmpMiniField('Cuenta', rcmpEsc(row.cuenta || '')) +
                        rcmpMiniField('Subtotal', rcmpMoney(row.subtotal)) +
                        rcmpMiniField('ISV', rcmpMoney(row.isv)) +
                        rcmpMiniField('Descuento', rcmpMoney(row.descuento)) +
                        rcmpMiniField('Total', '<span class="rcmp-mini-total">' + rcmpMoney(row.total) + '</span>') +
                    '</div>' +
                '</div>';
        }).join('');
    } else {
        var headers = [
            'Acciones',
            'Fecha',
            'Tipo',
            'Cuenta',
            'Proveedor',
            'Número',
            'Subtotal',
            'ISV',
            'Descuento',
            'Total'
        ];

        html =
            '<div class="rv-detail-header" style="grid-template-columns:' + grid + '">' +
                headers.map(function(header) {
                    return '<div class="rv-cell">' + header + '</div>';
                }).join('') +
            '</div>';

        html += pageRows.map(function(row, idx) {
            var index = start + idx;

            return '' +
                '<div class="rv-detail-row" style="grid-template-columns:' + grid + '">' +
                    '<div class="rv-cell rv-actions-cell" data-label="Acciones">' +
                        rcmpDropdown(row, index) +
                    '</div>' +

                    '<div class="rv-cell" data-label="Fecha">' +
                        rcmpEsc(row.fecha || '') +
                    '</div>' +

                    '<div class="rv-cell" data-label="Tipo">' +
                        rcmpTypeBadge(row) +
                    '</div>' +

                    '<div class="rv-cell" data-label="Cuenta">' +
                        rcmpEsc(row.cuenta || '') +
                    '</div>' +

                    '<div class="rv-cell" data-label="Proveedor">' +
                        '<strong>' + rcmpEsc(row.proveedor || '') + '</strong>' +
                    '</div>' +

                    '<div class="rv-cell" data-label="Número">' +
                        rcmpEsc(row.numero || '') +
                    '</div>' +

                    '<div class="rv-cell rv-money" data-label="Subtotal">' +
                        rcmpMoney(row.subtotal) +
                    '</div>' +

                    '<div class="rv-cell rv-money" data-label="ISV">' +
                        rcmpMoney(row.isv) +
                    '</div>' +

                    '<div class="rv-cell rv-money" data-label="Descuento">' +
                        rcmpMoney(row.descuento) +
                    '</div>' +

                    '<div class="rv-cell rv-money rcmp-total-cell" data-label="Total">' +
                        '<strong>' + rcmpMoney(row.total) + '</strong>' +
                    '</div>' +
                '</div>';
        }).join('');
    }

    $('#rcmpListado')
        .toggleClass('rv-mini', RCMP.view === 'miniatura')
        .html(html);

    var totals = rcmpTotals(RCMP.filtered);
    var promedio = RCMP.filtered.length
        ? totals.total / RCMP.filtered.length
        : 0;

    $('#rcmpKpiRegistros').text(RCMP.filtered.length);
    $('#rcmpKpiSubtotal').text(rcmpMoney(totals.subtotal));
    $('#rcmpKpiIsv').text(rcmpMoney(totals.isv));
    $('#rcmpKpiDescuento').text(rcmpMoney(totals.descuento));
    $('#rcmpKpiTotal').text(rcmpMoney(totals.total));
    $('#rcmpKpiPromedio').text(rcmpMoney(promedio));

    renderTotalesReporteCompras(totals);

    $('#rcmpInfo').text(
        RCMP.filtered.length
            ? 'Mostrando ' +
              (start + 1) +
              ' a ' +
              Math.min(start + pageRows.length, RCMP.filtered.length) +
              ' de ' +
              RCMP.filtered.length +
              ' registros'
            : '0 registros'
    );

    renderPaginacionReporteCompras();

    try {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    } catch (e) {}

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function renderTotalesReporteCompras(totals) {
    if (RCMP.view === 'miniatura') {
        $('#rcmpTotales').html(
            '<div class="rv-total-mini">' +
                '<div class="rv-total-chip"><span>Subtotal</span><strong>' + rcmpMoney(totals.subtotal) + '</strong></div>' +
                '<div class="rv-total-chip"><span>ISV</span><strong>' + rcmpMoney(totals.isv) + '</strong></div>' +
                '<div class="rv-total-chip"><span>Descuento</span><strong>' + rcmpMoney(totals.descuento) + '</strong></div>' +
                '<div class="rv-total-chip"><span>Total</span><strong>' + rcmpMoney(totals.total) + '</strong></div>' +
            '</div>'
        );
        return;
    }

    var grid =
        '118px 90px 95px minmax(110px,.9fr) minmax(170px,1.45fr) ' +
        'minmax(130px,1.05fr) 100px 92px 100px 115px';

    $('#rcmpTotales').html(
        '<div class="rv-total-detail rcmp-total-main-grid" style="grid-template-columns:' + grid + '">' +
            '<div class="rcmp-total-main-label">TOTALES GENERALES</div>' +
            '<div class="rv-cell rv-money rcmp-total-value">' + rcmpMoney(totals.subtotal) + '</div>' +
            '<div class="rv-cell rv-money rcmp-total-value">' + rcmpMoney(totals.isv) + '</div>' +
            '<div class="rv-cell rv-money rcmp-total-value">' + rcmpMoney(totals.descuento) + '</div>' +
            '<div class="rv-cell rv-money rcmp-total-value rcmp-total-highlight">' + rcmpMoney(totals.total) + '</div>' +
        '</div>'
    );
}

function renderPaginacionReporteCompras() {
    var pages = Math.max(1, Math.ceil(RCMP.filtered.length / RCMP.pageSize));
    var html = '';

    function addButton(label, page, disabled, active) {
        html +=
            '<button type="button" data-page="' + page + '" ' +
            (disabled ? 'disabled ' : '') +
            'class="' + (active ? 'active' : '') + '">' +
                label +
            '</button>';
    }

    addButton('<i class="fas fa-angle-double-left"></i> Inicio', 1, RCMP.page === 1, false);
    addButton('<i class="fas fa-angle-left"></i> Anterior', RCMP.page - 1, RCMP.page === 1, false);

    var from = Math.max(1, RCMP.page - 2);
    var to = Math.min(pages, from + 4);

    from = Math.max(1, to - 4);

    for (var page = from; page <= to; page++) {
        addButton(String(page), page, false, page === RCMP.page);
    }

    addButton('Siguiente <i class="fas fa-angle-right"></i>', RCMP.page + 1, RCMP.page === pages, false);
    addButton('Final <i class="fas fa-angle-double-right"></i>', pages, RCMP.page === pages, false);

    $('#rcmpPagination')
        .html(html)
        .off('click.rcmp', 'button[data-page]')
        .on('click.rcmp', 'button[data-page]', function() {
            if (this.disabled || $(this).hasClass('active')) return;

            RCMP.page = parseInt($(this).attr('data-page'), 10) || 1;
            renderReporteCompras();
        });
}


/* =========================================================
   AJAX
   ========================================================= */

var listar_reporte_compras = function() {
    var tipo_compra_reporte = $('#form_main_compras #tipo_compras_reporte').val();

    if (tipo_compra_reporte === null || tipo_compra_reporte === '') {
        tipo_compra_reporte = 1;
    }

    var fechai = $('#form_main_compras #fechai').val();
    var fechaf = $('#form_main_compras #fechaf').val();

    $('#rcmpListado')
        .removeClass('rv-mini')
        .html(
            '<div class="rv-loading">' +
                '<i class="fas fa-spinner fa-spin mr-1"></i>' +
                'Cargando reporte...' +
            '</div>'
        );

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableReporteCompras.php',
        data: {
            tipo_compra_reporte: tipo_compra_reporte,
            fechai: fechai,
            fechaf: fechaf
        },
        dataType: 'json'
    })
    .done(function(response) {
        RCMP.rows = rcmpRows(response);
        RCMP.page = 1;
        renderReporteCompras();
    })
    .fail(function(xhr) {
        RCMP.rows = [];
        RCMP.filtered = [];
        renderReporteCompras();

        showNotify(
            'error',
            'Error',
            xhr.responseText || 'No fue posible cargar el reporte de compras.'
        );
    });
};


/* =========================================================
   ANULAR COMPRA
   ========================================================= */

function anularCompra(compras_id) {
    swal({
        title: '¿Está seguro?',
        text: '¿Desea anular la factura de compra: # ' + getNumeroCompra(compras_id) + '?',
        icon: 'warning',
        buttons: {
            cancel: {
                text: 'Cancelar',
                visible: true
            },
            confirm: {
                text: '¡Sí, anular la factura de compra!',
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
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
        async: true,
        data: {
            compras_id: compras_id
        },
        success: function(data) {
            swal.close();

            if (data == 1) {
                showNotify(
                    'success',
                    'Success',
                    'La factura de compra ha sido anulada con éxito'
                );

                listar_reporte_compras();
            } else {
                showNotify(
                    'error',
                    'Error',
                    'La factura de compra no se pudo anular'
                );
            }
        },
        error: function(xhr) {
            swal.close();

            showNotify(
                'error',
                'Error',
                xhr.responseText || 'Hubo un problema al anular la compra'
            );
        }
    });
}


/* =========================================================
   EXCEL XLSX
   ========================================================= */

function rcmpXml(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function rcmpCol(index) {
    var n = index + 1;
    var result = '';

    while (n > 0) {
        var mod = (n - 1) % 26;
        result = String.fromCharCode(65 + mod) + result;
        n = Math.floor((n - 1) / 26);
    }

    return result;
}

function rcmpCell(ref, value, style, numeric) {
    if (numeric) {
        var number = Number(value);

        if (!isNaN(number)) {
            return '<c r="' + ref + '" s="' + style + '"><v>' + number + '</v></c>';
        }
    }

    return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
        rcmpXml(value) +
    '</t></is></c>';
}

function rcmpDownload(blob, filename) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');

    a.href = url;
    a.download = filename;

    document.body.appendChild(a);
    a.click();
    a.remove();

    setTimeout(function() {
        URL.revokeObjectURL(url);
    }, 1000);
}

function exportarReporteComprasExcel() {
    var rows = RCMP.filtered.map(function(row) {
        return [
            row.fecha || '',
            row.tipo_documento || '',
            row.cuenta || '',
            row.proveedor || '',
            row.numero || '',
            rcmpNum(row.subtotal),
            rcmpNum(row.isv),
            rcmpNum(row.descuento),
            rcmpNum(row.total)
        ];
    });

    if (!rows.length) {
        showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        showNotify('error', 'Excel no disponible', 'No se encontró JSZip.');
        return;
    }

    var headers = [
        'Fecha',
        'Tipo',
        'Cuenta',
        'Proveedor',
        'Número',
        'Subtotal',
        'ISV',
        'Descuento',
        'Total'
    ];

    var totalCols = [5, 6, 7, 8];
    var sums = {};

    totalCols.forEach(function(col) {
        sums[col] = rows.reduce(function(acc, row) {
            return acc + rcmpNum(row[col]);
        }, 0);
    });

    var headerRow = 7;
    var firstDataRow = 8;
    var totalRow = firstDataRow + rows.length;
    var lastCol = rcmpCol(headers.length - 1);

    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            rcmpCell('A1', 'IZZY • REPORTE DE COMPRAS', 1, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            rcmpCell(
                'A2',
                'Compras, impuestos, descuentos y total • Generado: ' +
                new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3" ht="18" customHeight="1">' +
            rcmpCell('A3', 'REGISTROS', 6, false) +
            rcmpCell('F3', 'TOTAL GENERAL', 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="26" customHeight="1">' +
            rcmpCell('A4', rows.length, 7, true) +
            rcmpCell('F4', sums[8], 10, true) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6">' +
            rcmpCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="7" ht="26" customHeight="1">' +
            headers.map(function(header, index) {
                return rcmpCell(rcmpCol(index) + '7', header, 3, false);
            }).join('') +
        '</row>'
    );

    rows.forEach(function(row, rowIndex) {
        var r = firstDataRow + rowIndex;

        sheetRows.push(
            '<row r="' + r + '" ht="22" customHeight="1">' +
                row.map(function(value, colIndex) {
                    var numeric = totalCols.indexOf(colIndex) !== -1;

                    return rcmpCell(
                        rcmpCol(colIndex) + r,
                        value,
                        numeric ? 5 : 4,
                        numeric
                    );
                }).join('') +
            '</row>'
        );
    });

    var totalCells =
        rcmpCell('A' + totalRow, 'TOTALES GENERALES', 9, false);

    totalCols.forEach(function(col) {
        totalCells += rcmpCell(
            rcmpCol(col) + totalRow,
            sums[col],
            10,
            true
        );
    });

    sheetRows.push(
        '<row r="' + totalRow + '" ht="30" customHeight="1">' +
            totalCells +
        '</row>'
    );

    var widths = [13, 14, 18, 30, 20, 16, 14, 16, 17];

    var cols = widths.map(function(width, index) {
        return '<col min="' + (index + 1) +
            '" max="' + (index + 1) +
            '" width="' + width +
            '" customWidth="1"/>';
    }).join('');

    var sheet =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + totalRow + '"/>' +
            '<sheetViews>' +
                '<sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                '</sheetView>' +
            '</sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' + cols + '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A7:' + lastCol + (headerRow + rows.length) + '"/>' +
            '<mergeCells count="3">' +
                '<mergeCell ref="A1:' + lastCol + '1"/>' +
                '<mergeCell ref="A2:' + lastCol + '2"/>' +
                '<mergeCell ref="A' + totalRow + ':E' + totalRow + '"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var styles =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<numFmts count="1"><numFmt numFmtId="164" formatCode="L. #,##0.00"/></numFmts>' +
            '<fonts count="8">' +
                '<font><sz val="10"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="5">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border>' +
                    '<left style="thin"><color rgb="FFDDE3EA"/></left>' +
                    '<right style="thin"><color rgb="FFDDE3EA"/></right>' +
                    '<top style="thin"><color rgb="FFDDE3EA"/></top>' +
                    '<bottom style="thin"><color rgb="FFDDE3EA"/></bottom>' +
                    '<diagonal/>' +
                '</border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0"/>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0"/>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="0"/>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="0"/>' +
                '<xf numFmtId="0" fontId="7" fillId="0" borderId="0"/>' +
                '<xf numFmtId="0" fontId="7" fillId="4" borderId="1"/>' +
                '<xf numFmtId="164" fontId="7" fillId="4" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbook =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var rels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var types =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>';

    var zip = new JSZip();

    zip.file('[Content_Types].xml', types);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbook);
    zip.folder('xl').file('styles.xml', styles);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', rels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', sheet);

    var options = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promise =
        typeof zip.generateAsync === 'function'
            ? zip.generateAsync(options)
            : Promise.resolve(zip.generate(options));

    promise
        .then(function(blob) {
            rcmpDownload(
                blob,
                'Reporte_Compras_' + new Date().toISOString().slice(0, 10) + '.xlsx'
            );
        })
        .catch(function(error) {
            console.error(error);
            showNotify('error', 'Excel', 'No se pudo generar el archivo Excel.');
        });
}


/* =========================================================
   PDF PREMIUM
   ========================================================= */

function rcmpGetLogo(callback) {
    if (
        typeof imagen !== 'undefined' &&
        imagen &&
        String(imagen).indexOf('data:image/') === 0
    ) {
        callback(imagen);
        return;
    }

    $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL;?>core/get_image.php',
        dataType: 'text',
        timeout: 10000
    })
    .done(function(src) {
        src = String(src || '').trim();

        if (!src) {
            callback(null);
            return;
        }

        if (src.indexOf('data:image/') === 0) {
            callback(src);
            return;
        }

        var image = new Image();
        image.crossOrigin = 'Anonymous';

        image.onload = function() {
            try {
                var canvas = document.createElement('canvas');

                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;

                canvas.getContext('2d').drawImage(image, 0, 0);

                callback(canvas.toDataURL('image/png'));
            } catch (e) {
                callback(null);
            }
        };

        image.onerror = function() {
            callback(null);
        };

        image.src = src;
    })
    .fail(function() {
        callback(null);
    });
}

function rcmpPdfLogoPlate(logoDataUrl) {
    if (!logoDataUrl) {
        return {
            table: {
                widths: ['*'],
                body: [[{
                    text: 'IZZY',
                    fontSize: 16,
                    bold: true,
                    color: '#17324D',
                    alignment: 'center',
                    margin: [7, 8, 7, 8],
                    fillColor: '#FFFFFF'
                }]]
            },
            layout: {
                hLineColor: function() { return '#DDE3EA'; },
                vLineColor: function() { return '#DDE3EA'; },
                hLineWidth: function() { return .5; },
                vLineWidth: function() { return .5; }
            }
        };
    }

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
            vLineWidth: function() { return .5; }
        }
    };
}

function exportarReporteComprasPdf() {
    if (!RCMP.filtered.length) {
        showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        return;
    }

    if (typeof pdfMake === 'undefined') {
        showNotify('error', 'PDF no disponible', 'No se encontró pdfMake.');
        return;
    }

    var totals = rcmpTotals(RCMP.filtered);

    var rows = RCMP.filtered.map(function(row) {
        return [
            row.fecha || '',
            row.tipo_documento || '',
            row.cuenta || '',
            row.proveedor || '',
            row.numero || '',
            rcmpNum(row.subtotal),
            rcmpNum(row.isv),
            rcmpNum(row.descuento),
            rcmpNum(row.total)
        ];
    });

    var headers = [
        'Fecha',
        'Tipo',
        'Cuenta',
        'Proveedor',
        'Número',
        'Subtotal',
        'ISV',
        'Descuento',
        'Total'
    ];

    var numeric = [5, 6, 7, 8];

    rcmpGetLogo(function(logo) {
        var body = [
            headers.map(function(header) {
                return {
                    text: header,
                    fillColor: '#17324D',
                    color: '#FFFFFF',
                    bold: true,
                    fontSize: 7,
                    alignment: 'center',
                    margin: [2, 3, 2, 3]
                };
            })
        ];

        rows.forEach(function(row, rowIndex) {
            body.push(
                row.map(function(value, colIndex) {
                    var money = numeric.indexOf(colIndex) !== -1;

                    return {
                        text: money ? rcmpMoney(value) : String(value || ''),
                        fillColor: rowIndex % 2 ? '#F7F9FC' : '#FFFFFF',
                        alignment: money ? 'right' : 'left',
                        fontSize: 7,
                        margin: [2, 3, 2, 3]
                    };
                })
            );
        });

        body.push([
            {
                text: 'TOTALES GENERALES',
                colSpan: 5,
                bold: true,
                fillColor: '#EAF4FC',
                color: '#17324D',
                fontSize: 7,
                margin: [6, 5, 6, 5],
                alignment: 'left'
            },
            {},
            {},
            {},
            {},
            {
                text: rcmpMoney(totals.subtotal),
                bold: true,
                fillColor: '#EAF4FC',
                alignment: 'right',
                margin: [3, 5, 3, 5]
            },
            {
                text: rcmpMoney(totals.isv),
                bold: true,
                fillColor: '#EAF4FC',
                alignment: 'right',
                margin: [3, 5, 3, 5]
            },
            {
                text: rcmpMoney(totals.descuento),
                bold: true,
                fillColor: '#EAF4FC',
                alignment: 'right',
                margin: [3, 5, 3, 5]
            },
            {
                text: rcmpMoney(totals.total),
                bold: true,
                fillColor: '#E8F7EF',
                color: '#087F5B',
                alignment: 'right',
                margin: [3, 5, 3, 5]
            }
        ]);

        var filters =
            'Tipo: ' +
            ($('#form_main_compras #tipo_compras_reporte option:selected').text() || 'Todos') +
            ' | Fechas: ' +
            ($('#form_main_compras #fechai').val() || '') +
            ' a ' +
            ($('#form_main_compras #fechaf').val() || '');

        var doc = {
            pageSize: 'LETTER',
            pageOrientation: 'landscape',
            pageMargins: [28, 28, 28, 34],

            content: [
                {
                    table: {
                        widths: [100, '*', 135],
                        body: [[
                            {
                                fillColor: '#17324D',
                                border: [false, false, false, false],
                                margin: [10, 7, 4, 7],
                                stack: [rcmpPdfLogoPlate(logo)]
                            },
                            {
                                fillColor: '#17324D',
                                border: [false, false, false, false],
                                stack: [
                                    {
                                        text: 'REPORTE DE COMPRAS',
                                        color: '#FFFFFF',
                                        bold: true,
                                        fontSize: 15
                                    },
                                    {
                                        text: 'Compras, impuestos, descuentos y total',
                                        color: '#D8E5F0',
                                        fontSize: 7.5,
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                margin: [0, 10, 0, 10]
                            },
                            {
                                fillColor: '#17324D',
                                border: [false, false, false, false],
                                stack: [
                                    {
                                        text: 'REPORTE EJECUTIVO',
                                        color: '#72E2E5',
                                        bold: true,
                                        fontSize: 6.5,
                                        alignment: 'right'
                                    },
                                    {
                                        text: new Date().toLocaleDateString('es-HN'),
                                        color: '#FFFFFF',
                                        bold: true,
                                        fontSize: 9,
                                        alignment: 'right',
                                        margin: [0, 3, 0, 0]
                                    },
                                    {
                                        text: RCMP.filtered.length + ' registro(s) filtrado(s)',
                                        color: '#D8E5F0',
                                        fontSize: 6.5,
                                        alignment: 'right',
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                margin: [0, 9, 10, 9]
                            }
                        ]]
                    },
                    layout: 'noBorders',
                    margin: [0, 0, 0, 10]
                },

                {
                    table: {
                        widths: ['*', '*', '*'],
                        body: [[
                            {
                                fillColor: '#F7F9FC',
                                stack: [
                                    {
                                        text: 'REGISTROS',
                                        fontSize: 6.5,
                                        bold: true,
                                        color: '#6B778C'
                                    },
                                    {
                                        text: String(RCMP.filtered.length),
                                        fontSize: 12,
                                        bold: true,
                                        color: '#172B4D',
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                margin: [8, 7, 8, 7]
                            },
                            {
                                fillColor: '#F7F9FC',
                                stack: [
                                    {
                                        text: 'TOTAL GENERAL',
                                        fontSize: 6.5,
                                        bold: true,
                                        color: '#6B778C'
                                    },
                                    {
                                        text: rcmpMoney(totals.total),
                                        fontSize: 12,
                                        bold: true,
                                        color: '#087F5B',
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                margin: [8, 7, 8, 7]
                            },
                            {
                                fillColor: '#F7F9FC',
                                stack: [
                                    {
                                        text: 'FILTROS',
                                        fontSize: 6.5,
                                        bold: true,
                                        color: '#6B778C'
                                    },
                                    {
                                        text: filters,
                                        fontSize: 7,
                                        color: '#42526E',
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                margin: [8, 7, 8, 7]
                            }
                        ]]
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; }
                    },
                    margin: [0, 0, 0, 12]
                },

                {
                    table: {
                        headerRows: 1,
                        widths: [56, 55, 75, '*', 90, 62, 58, 62, 68],
                        body: body
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return .55; },
                        vLineWidth: function() { return .55; },
                        paddingLeft: function() { return 4; },
                        paddingRight: function() { return 4; },
                        paddingTop: function() { return 5; },
                        paddingBottom: function() { return 5; }
                    }
                }
            ],

            footer: function(currentPage, pageCount) {
                return {
                    margin: [28, 8, 28, 0],
                    columns: [
                        {
                            text: 'IZZY • Reportes',
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

            defaultStyle: {
                fontSize: 7,
                color: '#253858'
            }
        };

        var pdf = pdfMake.createPdf(doc);
        var filename =
            'Reporte_Compras_' +
            new Date().toISOString().slice(0, 10) +
            '.pdf';

        if (
            typeof abrirModalPdfPublico === 'function' &&
            typeof pdf.getDataUrl === 'function'
        ) {
            pdf.getDataUrl(function(url) {
                abrirModalPdfPublico(
                    url,
                    'Reporte de Compras',
                    filename
                );
            });
        } else {
            pdf.download(filename);
        }
    });
}


/* =========================================================
   COMBO
   ========================================================= */

function getReporteCompras() {
    var url = '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: 'POST',
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_compras #tipo_compras_reporte').html('');
            $('#form_main_compras #tipo_compras_reporte').html(data);

            try {
                $('#form_main_compras #tipo_compras_reporte').selectpicker('refresh');
            } catch (e) {}
        },
        error: function() {
            showNotify(
                'error',
                'Error',
                'No se pudo cargar el tipo de factura'
            );
        }
    });
}

// FIN REPORTE DE COMPRAS
</script>
