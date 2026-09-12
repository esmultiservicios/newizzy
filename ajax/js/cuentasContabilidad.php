<script>
$(() => {
    inicializarVistaCuentas();
    listar_cuentas_contabilidad();

    $('#formMainCuentasContabilidad #search').off('click');
    $('#formMainCuentasContabilidad #search').on('click', function(e) {
        e.preventDefault();
        listar_cuentas_contabilidad();
    });

    $('#formMainCuentasContabilidad #buscar_cuenta').off('keyup');
    $('#formMainCuentasContabilidad #buscar_cuenta').on('keyup', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            listar_cuentas_contabilidad();
        }
    });

    $('#formMainCuentasContabilidad #estado_cuentasContabilidad, #formMainCuentasContabilidad #tipo_cuenta, #formMainCuentasContabilidad #tipo_saldo, #formMainCuentasContabilidad #orden_cuentas').off('changed.bs.select change');
    $('#formMainCuentasContabilidad #estado_cuentasContabilidad, #formMainCuentasContabilidad #tipo_cuenta, #formMainCuentasContabilidad #tipo_saldo, #formMainCuentasContabilidad #orden_cuentas').on('changed.bs.select change', function() {
        listar_cuentas_contabilidad();
    });

    $('#formMainCuentasContabilidad').off('reset');
    $('#formMainCuentasContabilidad').on('reset', function() {
        var form = this;

        setTimeout(function() {
            $(form).find('#buscar_cuenta').val('');
            $(form).find('#estado_cuentasContabilidad').selectpicker('val', '');
            $(form).find('#tipo_cuenta').selectpicker('val', '');
            $(form).find('#tipo_saldo').selectpicker('val', '');
            $(form).find('#orden_cuentas').selectpicker('val', 'neto_desc');
            $(form).find('.selectpicker').selectpicker('refresh');

            listar_cuentas_contabilidad();
        }, 0);
    });

    $('#cuentas-container').off('click', '.js-acciones-toggle');
    $('#cuentas-container').on('click', '.js-acciones-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $dropdown = $btn.closest('.acciones-dropdown');
        var $menu = $dropdown.find('.acciones-menu').first();
        var estaAbierto = $menu.hasClass('show');

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        if (!estaAbierto) {
            $dropdown.addClass('show');
            $menu.addClass('show');
            $btn.attr('aria-expanded', 'true');
        }
    });

    $(document).off('click.cuentasAcciones');
    $(document).on('click.cuentasAcciones', function() {
        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');
    });

    $('#cuentas-container').off('click', '.acciones-menu');
    $('#cuentas-container').on('click', '.acciones-menu', function(e) {
        e.stopPropagation();
    });

    $('#cuentas-container').off('click', '.table_editar');
    $('#cuentas-container').on('click', '.table_editar', function(e) {
        e.preventDefault();

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        var cuentas_id = $(this).data('id');
        editar_cuenta(cuentas_id);
    });

    $('#cuentas-container').off('click', '.table_eliminar');
    $('#cuentas-container').on('click', '.table_eliminar', function(e) {
        e.preventDefault();

        $('.acciones-menu').removeClass('show');
        $('.js-acciones-toggle').attr('aria-expanded', 'false');
        $('.acciones-dropdown').removeClass('show');

        var cuentas_id = $(this).data('id');
        var nombreCuenta = $(this).data('nombre');

        eliminar_cuenta(cuentas_id, nombreCuenta);
    });

    $('#formCuentasContables #cuentas_activo').off('change');
    $('#formCuentasContables #cuentas_activo').on('change', function() {
        actualizarLabelEstadoCuenta();
    });

    $('#modalCuentascontables').off('shown.bs.modal.cuentas');
    $('#modalCuentascontables').on('shown.bs.modal.cuentas', function() {
        $(this).find('#formCuentasContables #cuenta_nombre').focus();
    });
});

function escapeHtml(text) {
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

function cleanNumber(numStr) {
    if (numStr === null || numStr === undefined || numStr === '') {
        return '0';
    }

    if (typeof numStr === 'number') {
        return String(numStr);
    }

    var normalized = String(numStr)
        .replace(/,/g, '')
        .trim();

    var match = normalized.match(/-?\d+(?:\.\d+)?/);

    return match ? match[0] : '0';
}

function formatCurrency(value) {
    var n = parseFloat(value);

    if (isNaN(n)) {
        n = 0;
    }

    return 'L. ' + n.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function actualizarLabelEstadoCuenta() {
    if ($('#formCuentasContables #cuentas_activo').is(':checked')) {
        $('#formCuentasContables #label_cuentas_activo').html('Activo');
    } else {
        $('#formCuentasContables #label_cuentas_activo').html('Inactivo');
    }
}

function badgeEstadoCuenta(estado) {
    var activo = parseInt(estado || 0, 10) === 1;

    if (activo) {
        return '<span class="badge-cuenta-activa"><i class="fas fa-check-circle"></i> Activa</span>';
    }

    return '<span class="badge-cuenta-inactiva"><i class="fas fa-times-circle"></i> Inactiva</span>';
}

function badgeInversionCuenta(es_inversion) {
    var inversion = parseInt(es_inversion || 0, 10) === 1;

    if (inversion) {
        return '<span class="badge-cuenta-inversion"><i class="fas fa-seedling"></i> Inversión</span>';
    }

    return '<span class="badge-cuenta-normal"><i class="fas fa-wallet"></i> Normal</span>';
}

function renderAccionesCuenta(cuenta) {
    var cuentas_id = escapeHtml(cuenta.cuentas_id);
    var nombre = escapeHtml(cuenta.nombre);

    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +

            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar" data-id="' + cuentas_id + '">' +
                    '<span class="accion-icon accion-icon-primary">' +
                        '<i class="fas fa-edit"></i>' +
                    '</span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar" data-id="' + cuentas_id + '" data-nombre="' + nombre + '">' +
                    '<span class="accion-icon accion-icon-danger">' +
                        '<i class="fas fa-trash-alt"></i>' +
                    '</span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}


var CUENTAS_STORAGE_VISTA = 'izzy.cuentasContabilidad.tipo_vista';
var cuentasVistaActual = 'miniatura';
var cuentasUltimosDatos = [];
var cuentasDatosFiltrados = [];
var cuentasPaginaActual = 1;
var cuentasPageSizeDetalle = 10;
var cuentasPageSizeMiniatura = 6;
var cuentasPageSizeActual = 6;
var cuentasBusquedaListado = '';

function cuentasEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function cuentasConfigurarPanel(btn, contenido, key) {
    var visible = true;

    try {
        var saved = localStorage.getItem(key);
        if (saved !== null) {
            visible = saved === '1';
        }
    } catch (e) {}

    function sync() {
        $(contenido).toggle(visible);
        $(btn).attr('aria-expanded', visible ? 'true' : 'false');
        $(btn).find('span').text(visible ? 'Ocultar' : 'Mostrar');
        $(btn).find('i')
            .toggleClass('fa-chevron-up', visible)
            .toggleClass('fa-chevron-down', !visible);
    }

    sync();

    $(btn).off('click.cuentasPanel').on('click.cuentasPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function cuentasEstadoVista() {
    var mobile = cuentasEsMovil();

    if (mobile) {
        cuentasVistaActual = 'miniatura';
    }

    $('.cuentas-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile);

    $('.cuentas-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.cuentas-view-btn[data-view="' + cuentasVistaActual + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');

    if (cuentasVistaActual === 'detalle' && !mobile) {
        $('#cuentas-container').addClass('d-none');
        $('#cuentas-detalle-container').removeClass('d-none');
    } else {
        $('#cuentas-detalle-container').addClass('d-none');
        $('#cuentas-container').removeClass('d-none');
    }
}

function renderDetalleCuentas(data) {
    data = Array.isArray(data) ? data : [];

    if (!data.length) {
        $('#cuentas-detalle-container').html(
            '<div class="cuentas-empty-state">' +
                '<i class="fas fa-wallet"></i>' +
                '<strong>Sin cuentas</strong>' +
                '<span>No hay información disponible con los filtros seleccionados.</span>' +
            '</div>'
        );
        return;
    }

    var html = '' +
        '<div class="cuentas-detail-header">' +
            '<div>Cuenta</div>' +
            '<div>Saldo Anterior</div>' +
            '<div>Ingresos</div>' +
            '<div>Egresos</div>' +
            '<div>Saldo Cierre</div>' +
            '<div>Saldo Total</div>' +
            '<div>Estado / Tipo</div>' +
            '<div>Acciones</div>' +
        '</div>';

    data.forEach(function(cuenta) {
        var saldoNeto = parseFloat(cleanNumber(cuenta.neto));
        var nombre = escapeHtml(cuenta.nombre);
        var codigo = escapeHtml(cuenta.codigo || 'Sin código');

        html += '' +
            '<article class="cuentas-detail-row ' + (saldoNeto >= 0 ? 'positive-balance' : 'negative-balance') + '">' +
                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Cuenta</span>' +
                    '<div class="cuentas-detail-main">' +
                        '<span class="cuentas-detail-icon"><i class="fas fa-wallet"></i></span>' +
                        '<div>' +
                            '<strong>' + nombre + '</strong>' +
                            '<small><i class="fas fa-hashtag mr-1"></i>' + codigo + '</small>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Saldo Anterior</span>' +
                    '<strong class="cuentas-money">' + escapeHtml(cuenta.saldo_anterior) + '</strong>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Ingresos</span>' +
                    '<strong class="cuentas-money text-success">' + escapeHtml(cuenta.ingreso) + '</strong>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Egresos</span>' +
                    '<strong class="cuentas-money text-danger">' + escapeHtml(cuenta.egreso) + '</strong>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Saldo Cierre</span>' +
                    '<strong class="cuentas-money">' + escapeHtml(cuenta.saldo_cierre) + '</strong>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Saldo Total</span>' +
                    '<strong class="cuentas-money ' + (saldoNeto >= 0 ? 'text-success' : 'text-danger') + '">' +
                        escapeHtml(cuenta.neto) +
                    '</strong>' +
                    '<small class="cuentas-current-trace" title="Último movimiento procesado">' +
                        (cuenta.ultimo_movimiento_id ? 'Mov. #' + escapeHtml(cuenta.ultimo_movimiento_id) : '') +
                    '</small>' +
                '</div>' +

                '<div class="cuentas-detail-cell">' +
                    '<span class="cuentas-detail-label">Estado / Tipo</span>' +
                    '<div class="cuentas-detail-badges">' +
                        badgeEstadoCuenta(cuenta.estado) +
                        badgeInversionCuenta(cuenta.es_inversion) +
                    '</div>' +
                '</div>' +

                '<div class="cuentas-detail-cell cuentas-detail-actions">' +
                    '<span class="cuentas-detail-label">Acciones</span>' +
                    renderAccionesCuenta(cuenta) +
                '</div>' +
            '</article>';
    });

    $('#cuentas-detalle-container').html(html);
}


function cuentasNumero(value) {
    var n = parseFloat(cleanNumber(value));
    return isNaN(n) ? 0 : n;
}

function cuentasFormato(value) {
    return 'L. ' + cuentasNumero(value).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function cuentasSincronizarPageSize() {
    var mini = cuentasVistaActual === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? cuentasPageSizeMiniatura : cuentasPageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    cuentasPageSizeActual = preferido;

    var $select = $('#cuentasPageSize').empty();
    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });
    $select.val(String(preferido));
}

function cuentasAplicarBusquedaListado() {
    var q = $.trim(cuentasBusquedaListado || '').toLowerCase();

    cuentasDatosFiltrados = !q
        ? cuentasUltimosDatos.slice()
        : cuentasUltimosDatos.filter(function(cuenta) {
            var texto = [
                cuenta.nombre, cuenta.codigo, cuenta.saldo_anterior, cuenta.ingreso,
                cuenta.egreso, cuenta.saldo_cierre, cuenta.neto,
                Number(cuenta.estado) === 1 ? 'activa activo' : 'inactiva inactivo',
                Number(cuenta.es_inversion) === 1 ? 'inversion inversión' : 'normal'
            ].map(function(v) {
                return String(v == null ? '' : v).toLowerCase();
            }).join(' ');

            return texto.indexOf(q) !== -1;
        });

    cuentasPaginaActual = 1;
    cuentasActualizarResumenGeneral();
    cuentasRenderActual();
}

function cuentasActualizarResumenGeneral() {
    var totalAnterior = 0, totalIngresos = 0, totalEgresos = 0, totalCierre = 0, totalActual = 0;

    cuentasDatosFiltrados.forEach(function(cuenta) {
        totalAnterior += cuentasNumero(cuenta.saldo_anterior);
        totalIngresos += cuentasNumero(cuenta.ingreso);
        totalEgresos += cuentasNumero(cuenta.egreso);
        totalCierre += cuentasNumero(cuenta.saldo_cierre);
        totalActual += cuentasNumero(cuenta.neto);
    });

    $('#cuentasKpiTotal').text(cuentasDatosFiltrados.length);
    $('#cuentasKpiIngresos').text(cuentasFormato(totalIngresos));
    $('#cuentasKpiEgresos').text(cuentasFormato(totalEgresos));
    $('#cuentasKpiSaldoActual').text(cuentasFormato(totalActual));

    $('#cuentasTotalSaldoAnterior').text(cuentasFormato(totalAnterior));
    $('#cuentasTotalIngresos').text(cuentasFormato(totalIngresos));
    $('#cuentasTotalEgresos').text(cuentasFormato(totalEgresos));
    $('#cuentasTotalSaldoCierre').text(cuentasFormato(totalCierre));
    $('#cuentasTotalSaldoActual').text(cuentasFormato(totalActual));
}

function cuentasPaginacion(totalPages) {
    var html = '';
    var current = cuentasPaginaActual;

    function b(label, page, disabled, active, icon) {
        return '<button type="button" class="cuentas-page-btn' + (active ? ' active' : '') +
            '" data-page="' + page + '"' + (disabled ? ' disabled' : '') + '>' +
            (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
        '</button>';
    }

    html += b('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
    html += b('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

    var from = Math.max(1, current - 2);
    var to = Math.min(totalPages, from + 4);
    from = Math.max(1, to - 4);

    for (var p = from; p <= to; p++) {
        html += b(String(p), p, false, p === current, '');
    }

    html += b('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
    html += b('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

    $('#cuentasPaginacion').html(html);
}

function cuentasRenderMiniaturaPaginada(rows) {
    if (!rows.length) {
        $("#cuentas-container").html(
            '<div class="col-12">' +
                '<div class="alert alert-warning mb-0">' +
                    '<i class="fas fa-exclamation-triangle mr-1"></i> No se encontraron cuentas.' +
                '</div>' +
            '</div>'
        );
        return;
    }

    let html = '';

    rows.forEach(function(cuenta) {
        const saldoNeto = parseFloat(cleanNumber(cuenta.neto));
        const saldoClass = saldoNeto >= 0 ? 'positive-balance' : 'negative-balance';
        const inversionClass = parseInt(cuenta.es_inversion || 0, 10) === 1 ? 'account-investment' : '';

        const nombre = escapeHtml(cuenta.nombre);
        const codigo = escapeHtml(cuenta.codigo || 'Sin código');

        html += `
        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4">
            <div class="card h-100 card-account ${saldoClass} ${inversionClass}">
                <div class="cuenta-card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="cuenta-card-title-wrap">
                            <h5 class="cuenta-card-title text-truncate" title="${nombre}">
                                ${nombre}
                            </h5>

                            <span class="cuenta-card-code">
                                <i class="fas fa-hashtag"></i>
                                ${codigo}
                            </span>
                        </div>

                        <div class="cuenta-badges-wrap">
                            ${badgeEstadoCuenta(cuenta.estado)}
                            ${badgeInversionCuenta(cuenta.es_inversion)}
                        </div>
                    </div>
                </div>

                <div class="cuenta-card-body">
                    <div class="cuenta-row">
                        <span class="cuenta-row-label">
                            <i class="fas fa-history"></i>
                            Saldo Anterior
                        </span>
                        <span class="cuenta-row-value">${escapeHtml(cuenta.saldo_anterior)}</span>
                    </div>

                    <div class="cuenta-row">
                        <span class="cuenta-row-label">
                            <i class="fas fa-arrow-down"></i>
                            Ingresos
                        </span>
                        <span class="cuenta-row-value text-success">${escapeHtml(cuenta.ingreso)}</span>
                    </div>

                    <div class="cuenta-row">
                        <span class="cuenta-row-label">
                            <i class="fas fa-arrow-up"></i>
                            Egresos
                        </span>
                        <span class="cuenta-row-value text-danger">${escapeHtml(cuenta.egreso)}</span>
                    </div>

                    <div class="cuenta-divider"></div>

                    <div class="cuenta-row">
                        <span class="cuenta-row-label">
                            <i class="fas fa-balance-scale"></i>
                            Saldo Cierre
                        </span>
                        <span class="cuenta-row-value">${escapeHtml(cuenta.saldo_cierre)}</span>
                    </div>

                    <div class="cuenta-total-box d-flex justify-content-between align-items-center">
                        <span class="cuenta-total-label">
                            <i class="fas fa-wallet mr-1"></i>
                            Saldo Total
                        </span>

                        <span class="cuenta-total-value ${saldoNeto >= 0 ? 'text-success' : 'text-danger'}">
                            ${escapeHtml(cuenta.neto)}
                        </span>
                    </div>
                </div>

                <div class="cuenta-card-footer">
                    ${renderAccionesCuenta(cuenta)}
                </div>
            </div>
        </div>`;
    });

    $("#cuentas-container").html(html);

    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        container: 'body'
    });
}

function cuentasRenderActual() {
    var rows = cuentasDatosFiltrados || [];
    var totalPages = Math.max(1, Math.ceil(rows.length / cuentasPageSizeActual));

    if (cuentasPaginaActual > totalPages) {
        cuentasPaginaActual = totalPages;
    }

    var offset = (cuentasPaginaActual - 1) * cuentasPageSizeActual;
    var pageRows = rows.slice(offset, offset + cuentasPageSizeActual);

    if (cuentasVistaActual === 'detalle' && !cuentasEsMovil()) {
        renderDetalleCuentas(pageRows);
    } else {
        cuentasVistaActual = 'miniatura';
        cuentasRenderMiniaturaPaginada(pageRows);
    }

    var hasta = rows.length ? Math.min(offset + pageRows.length, rows.length) : 0;
    var desde = rows.length ? offset + 1 : 0;

    $('#cuentasInfo').text(
        rows.length
            ? 'Mostrando ' + desde + ' a ' + hasta + ' de ' + rows.length + ' registros'
            : '0 registros'
    );

    cuentasPaginacion(totalPages);
    cuentasEstadoVista();

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function cuentasExportarExcel() {
    var rows = cuentasDatosFiltrados || [];

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay cuentas para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function col(i) {
        var n = '';
        while (i >= 0) {
            n = String.fromCharCode((i % 26) + 65) + n;
            i = Math.floor(i / 26) - 1;
        }
        return n;
    }

    function cell(ref, v, style) {
        return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' + esc(v) + '</t></is></c>';
    }

    var headers = ['Código','Cuenta','Saldo Anterior','Ingresos','Egresos','Saldo Cierre','Saldo Total','Estado','Tipo'];
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' + cell('A1','IZZY • REPORTE DE CUENTAS',1) + '</row>');
    sheetRows.push('<row r="2">' + cell('A2','Generado: ' + new Date().toLocaleDateString('es-HN') + ' • Registros: ' + rows.length,2) + '</row>');
    sheetRows.push('<row r="4" ht="26" customHeight="1">' + headers.map(function(h,i){return cell(col(i)+'4',h,3);}).join('') + '</row>');

    rows.forEach(function(r,i) {
        var rr = 5 + i;
        var vals = [
            r.codigo, r.nombre, r.saldo_anterior, r.ingreso, r.egreso,
            r.saldo_cierre, r.neto,
            Number(r.estado) === 1 ? 'Activa' : 'Inactiva',
            Number(r.es_inversion) === 1 ? 'Inversión' : 'Normal'
        ];
        sheetRows.push('<row r="' + rr + '">' + vals.map(function(v,c){return cell(col(c)+rr,v,4);}).join('') + '</row>');
    });

    var lastRow = Math.max(4, 4 + rows.length);
    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
        '<dimension ref="A1:I' + lastRow + '"/>' +
        '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' +
        '<cols><col min="1" max="1" width="14" customWidth="1"/><col min="2" max="2" width="30" customWidth="1"/><col min="3" max="7" width="18" customWidth="1"/><col min="8" max="9" width="14" customWidth="1"/></cols>' +
        '<sheetData>' + sheetRows.join('') + '</sheetData>' +
        '<autoFilter ref="A4:I' + lastRow + '"/>' +
        '<mergeCells count="2"><mergeCell ref="A1:I1"/><mergeCell ref="A2:I2"/></mergeCells>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
        '<fonts count="5"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts>' +
        '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill></fills>' +
        '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders>' +
        '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
        '<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs>' +
        '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

    var workbookXml = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Cuentas" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var workbookRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var rootRels = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var contentTypes = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';

    var zip = new JSZip();
    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbookXml);
    zip.folder('xl').file('styles.xml', stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

    var opts = {type:'blob', mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', compression:'DEFLATE'};
    var promise = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(opts)
        : Promise.resolve(zip.generate(opts));

    promise.then(function(blob) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'Reporte_Cuentas.xlsx';
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function(){ URL.revokeObjectURL(url); },1000);
    }).catch(function(e) {
        console.error(e);
        showNotify('error','Excel','No se pudo generar el Excel.');
    });
}

function cuentasObtenerLogoPdf(callback) {
    if (typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
        callback(imagen);
        return;
    }

    $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL;?>core/get_image.php',
        dataType: 'text',
        timeout: 15000
    }).done(function(url) {
        url = $.trim(url || '');

        if (!url) {
            callback(null);
            return;
        }

        var img = new Image();
        img.crossOrigin = 'Anonymous';

        img.onload = function() {
            try {
                var canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                canvas.getContext('2d').drawImage(img, 0, 0);

                imagen = canvas.toDataURL('image/png');
                callback(imagen);
            } catch (e) {
                callback(null);
            }
        };

        img.onerror = function() {
            callback(null);
        };

        img.src = url;
    }).fail(function() {
        callback(null);
    });
}

function cuentasExportarPdf() {
    var rows = cuentasDatosFiltrados || [];

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay cuentas para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        showNotify('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    cuentasObtenerLogoPdf(function(logo) {
        var totalAnterior = 0;
        var totalIngresos = 0;
        var totalEgresos = 0;
        var totalCierre = 0;
        var totalActual = 0;

        rows.forEach(function(r) {
            totalAnterior += cuentasNumero(r.saldo_anterior);
            totalIngresos += cuentasNumero(r.ingreso);
            totalEgresos += cuentasNumero(r.egreso);
            totalCierre += cuentasNumero(r.saldo_cierre);
            totalActual += cuentasNumero(r.neto);
        });

        var body = [[
            {text:'CÓDIGO',style:'th'},
            {text:'CUENTA',style:'th'},
            {text:'SALDO ANT.',style:'th'},
            {text:'INGRESOS',style:'th'},
            {text:'EGRESOS',style:'th'},
            {text:'SALDO CIERRE',style:'th'},
            {text:'SALDO TOTAL',style:'th'},
            {text:'ESTADO',style:'th'},
            {text:'TIPO',style:'th'}
        ]];

        rows.forEach(function(r, i) {
            var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                {text:String(r.codigo || ''),style:'td',fillColor:fill},
                {text:String(r.nombre || ''),style:'td',fillColor:fill},
                {text:String(r.saldo_anterior || ''),style:'tdn',fillColor:fill},
                {text:String(r.ingreso || ''),style:'tdn',fillColor:fill,color:'#14804A'},
                {text:String(r.egreso || ''),style:'tdn',fillColor:fill,color:'#C9372C'},
                {text:String(r.saldo_cierre || ''),style:'tdn',fillColor:fill},
                {text:String(r.neto || ''),style:'tdn',fillColor:fill,bold:true,color:cuentasNumero(r.neto) >= 0 ? '#14804A' : '#C9372C'},
                {text:Number(r.estado) === 1 ? 'Activa' : 'Inactiva',style:'td',fillColor:fill},
                {text:Number(r.es_inversion) === 1 ? 'Inversión' : 'Normal',style:'td',fillColor:fill}
            ]);
        });

        /* Total al pie del reporte */
        body.push([
            {text:'TOTALES',colSpan:2,style:'totalLabel',fillColor:'#EAF1F7'},
            {},
            {text:cuentasFormato(totalAnterior),style:'totalMoney',fillColor:'#EAF1F7'},
            {text:cuentasFormato(totalIngresos),style:'totalMoneySuccess',fillColor:'#EAF1F7'},
            {text:cuentasFormato(totalEgresos),style:'totalMoneyDanger',fillColor:'#EAF1F7'},
            {text:cuentasFormato(totalCierre),style:'totalMoney',fillColor:'#EAF1F7'},
            {text:cuentasFormato(totalActual),style:'totalMoneyCurrent',fillColor:'#EAF1F7'},
            {text:'',fillColor:'#EAF1F7'},
            {text:'',fillColor:'#EAF1F7'}
        ]);

        var logoCell = logo
            ? {
                table: {
                    widths: ['*'],
                    body: [[{
                        image: logo,
                        fit: [74, 44],
                        alignment: 'center',
                        margin: [7, 5, 7, 5],
                        fillColor: '#FFFFFF'
                    }]]
                },
                layout: 'noBorders',
                fillColor: '#17324D',
                margin: [8, 7, 8, 7]
            }
            : {
                text: 'IZZY',
                bold: true,
                fontSize: 18,
                color: '#17324D',
                alignment: 'center',
                fillColor: '#FFFFFF',
                margin: [8, 14, 8, 14]
            };

        var filtroEstado = $('#estado_cuentas option:selected').text() || 'Todos';
        var filtroTipo = $('#tipo_cuenta option:selected').text() || 'Todos';
        var desde = $('#fechai').val() || '';
        var hasta = $('#fechaf').val() || '';

        var kpis = {
            table: {
                widths: ['*','*','*','*'],
                body: [[
                    {
                        stack:[
                            {text:'CUENTAS',fontSize:6.2,bold:true,color:'#6B778C'},
                            {text:String(rows.length),fontSize:12,bold:true,color:'#17324D',margin:[0,2,0,0]}
                        ],
                        fillColor:'#F7F9FC',margin:[8,7,8,7]
                    },
                    {
                        stack:[
                            {text:'INGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},
                            {text:cuentasFormato(totalIngresos),fontSize:11,bold:true,color:'#14804A',margin:[0,2,0,0]}
                        ],
                        fillColor:'#F7F9FC',margin:[8,7,8,7]
                    },
                    {
                        stack:[
                            {text:'EGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},
                            {text:cuentasFormato(totalEgresos),fontSize:11,bold:true,color:'#C9372C',margin:[0,2,0,0]}
                        ],
                        fillColor:'#F7F9FC',margin:[8,7,8,7]
                    },
                    {
                        stack:[
                            {text:'SALDO TOTAL',fontSize:6.2,bold:true,color:'#6B778C'},
                            {text:cuentasFormato(totalActual),fontSize:11,bold:true,color:'#6554C0',margin:[0,2,0,0]}
                        ],
                        fillColor:'#F7F9FC',margin:[8,7,8,7]
                    }
                ]]
            },
            layout: {
                hLineColor:function(){ return '#DDE3EA'; },
                vLineColor:function(){ return '#DDE3EA'; },
                hLineWidth:function(){ return .5; },
                vLineWidth:function(){ return .5; }
            },
            margin:[0,0,0,10]
        };

        var doc = {
            pageSize:'LETTER',
            pageOrientation:'landscape',
            pageMargins:[28,28,28,34],

            header:function() {
                return {
                    margin:[28,12,28,0],
                    canvas:[{
                        type:'line',
                        x1:0,y1:0,x2:736,y2:0,
                        lineWidth:2,
                        lineColor:'#0EA5A8'
                    }]
                };
            },

            footer:function(page,pages) {
                return {
                    margin:[28,8,28,0],
                    columns:[
                        {text:'IZZY • Reporte de Cuentas',fontSize:7,color:'#7A869A'},
                        {text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}
                    ]
                };
            },

            content:[
                {
                    table:{
                        widths:[110,'*',160],
                        body:[[
                            logoCell,
                            {
                                stack:[
                                    {text:'REPORTE DE CUENTAS',bold:true,fontSize:16,color:'#FFFFFF'},
                                    {text:'Resumen contable, movimientos y saldos actuales',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}
                                ],
                                fillColor:'#17324D',
                                margin:[0,10,0,10]
                            },
                            {
                                stack:[
                                    {text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},
                                    {text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},
                                    {text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}
                                ],
                                fillColor:'#17324D',
                                margin:[0,10,12,10]
                            }
                        ]]
                    },
                    layout:'noBorders',
                    margin:[0,0,0,10]
                },

                {
                    table:{
                        widths:['*'],
                        body:[[
                            {
                                text:
                                    'Estado: '+filtroEstado+
                                    '   |   Tipo: '+filtroTipo+
                                    '   |   Período: '+desde+' a '+hasta+
                                    '   |   Búsqueda: '+($.trim($('#buscarCuentasListado').val()) || 'Sin búsqueda'),
                                fontSize:7,
                                color:'#52627A',
                                fillColor:'#F7F9FC',
                                margin:[8,6,8,6]
                            }
                        ]]
                    },
                    layout:'lightHorizontalLines',
                    margin:[0,0,0,10]
                },

                kpis,

                {
                    table:{
                        headerRows:1,
                        widths:[48,120,72,72,72,78,78,58,58],
                        body:body
                    },
                    margin:[0,0,0,0],
                    layout:{
                        hLineColor:function(){return '#DDE3EA';},
                        vLineColor:function(){return '#DDE3EA';},
                        hLineWidth:function(){return .55;},
                        vLineWidth:function(){return .55;},
                        paddingLeft:function(){return 4;},
                        paddingRight:function(){return 4;},
                        paddingTop:function(){return 5;},
                        paddingBottom:function(){return 5;}
                    }
                }
            ],

            styles:{
                th:{
                    fontSize:5.9,
                    bold:true,
                    color:'#FFFFFF',
                    fillColor:'#17324D',
                    alignment:'center'
                },
                td:{
                    fontSize:6.1,
                    color:'#253858',
                    noWrap:false
                },
                tdn:{
                    fontSize:6.1,
                    color:'#253858',
                    alignment:'right',
                    noWrap:false
                },
                totalLabel:{
                    fontSize:6.4,
                    bold:true,
                    color:'#17324D'
                },
                totalMoney:{
                    fontSize:6.4,
                    bold:true,
                    color:'#17324D',
                    alignment:'right'
                },
                totalMoneySuccess:{
                    fontSize:6.4,
                    bold:true,
                    color:'#14804A',
                    alignment:'right'
                },
                totalMoneyDanger:{
                    fontSize:6.4,
                    bold:true,
                    color:'#C9372C',
                    alignment:'right'
                },
                totalMoneyCurrent:{
                    fontSize:6.4,
                    bold:true,
                    color:'#6554C0',
                    alignment:'right'
                }
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url) {
            abrirModalPdfPublico(
                url,
                'Reporte de Cuentas',
                'Reporte_Cuentas.pdf'
            );
        });
    });
}

function inicializarVistaCuentas() {
    cuentasConfigurarPanel(
        '#btnToggleFiltrosCuentas',
        '#cuentasFiltrosContenido',
        'izzy.cuentasContabilidad.filtros.visible'
    );

    cuentasConfigurarPanel(
        '#btnToggleKpisCuentas',
        '#cuentasKpisContenido',
        'izzy.cuentasContabilidad.kpis.visible'
    );

    var saved = 'miniatura';

    try {
        saved = localStorage.getItem(CUENTAS_STORAGE_VISTA) || 'miniatura';
    } catch (e) {}

    cuentasVistaActual = saved === 'detalle' ? 'detalle' : 'miniatura';

    if (cuentasEsMovil()) {
        cuentasVistaActual = 'miniatura';
    }

    cuentasSincronizarPageSize();
    cuentasEstadoVista();

    $('#buscarCuentasListado').off('input.cuentasListado').on('input.cuentasListado', function(){
        cuentasBusquedaListado = this.value || '';
        cuentasAplicarBusquedaListado();
    });

    $('#limpiarBuscarCuentasListado').off('click.cuentasListado').on('click.cuentasListado', function(){
        $('#buscarCuentasListado').val('').focus();
        cuentasBusquedaListado = '';
        cuentasAplicarBusquedaListado();
    });

    $('#cuentasPageSize').off('change.cuentasListado').on('change.cuentasListado', function(){
        var n = parseInt(this.value,10);
        if(!n) return;
        cuentasPageSizeActual = n;
        if(cuentasVistaActual === 'miniatura') cuentasPageSizeMiniatura = n;
        else cuentasPageSizeDetalle = n;
        cuentasPaginaActual = 1;
        cuentasRenderActual();
    });

    $('#cuentasPaginacion').off('click.cuentasListado','.cuentas-page-btn').on('click.cuentasListado','.cuentas-page-btn',function(){
        if(this.disabled) return;
        var page = parseInt($(this).data('page'),10);
        if(!page) return;
        cuentasPaginaActual = page;
        cuentasRenderActual();
    });

    $('#btnCuentasExcel').off('click.cuentasExport').on('click.cuentasExport', cuentasExportarExcel);
    $('#btnCuentasPdf').off('click.cuentasExport').on('click.cuentasExport', cuentasExportarPdf);

    $('.cuentas-view-btn')
        .off('click.cuentasVista')
        .on('click.cuentasVista', function() {
            var requested = $(this).data('view');

            cuentasVistaActual = cuentasEsMovil()
                ? 'miniatura'
                : (requested === 'detalle' ? 'detalle' : 'miniatura');

            if (!cuentasEsMovil()) {
                try {
                    localStorage.setItem(CUENTAS_STORAGE_VISTA, cuentasVistaActual);
                } catch (e) {}
            }

            cuentasPaginaActual = 1;
            cuentasSincronizarPageSize();
            cuentasEstadoVista();
            cuentasRenderActual();
        });

    $(window)
        .off('resize.cuentasVista orientationchange.cuentasVista')
        .on('resize.cuentasVista orientationchange.cuentasVista', function() {
            if (cuentasEsMovil()) {
                cuentasVistaActual = 'miniatura';
            } else {
                try {
                    var savedView = localStorage.getItem(CUENTAS_STORAGE_VISTA) || 'miniatura';
                    cuentasVistaActual = savedView === 'detalle' ? 'detalle' : 'miniatura';
                } catch (e) {}
            }

            cuentasPaginaActual = 1;
            cuentasSincronizarPageSize();
            cuentasEstadoVista();
            cuentasRenderActual();
        });
}

var listar_cuentas_contabilidad = function() {
    var fechai = $("#formMainCuentasContabilidad #fechai").val();
    var fechaf = $("#formMainCuentasContabilidad #fechaf").val();
    var estado = $('#formMainCuentasContabilidad #estado_cuentasContabilidad').val();
    var buscar = $('#formMainCuentasContabilidad #buscar_cuenta').val();
    var tipo_cuenta = $('#formMainCuentasContabilidad #tipo_cuenta').val();
    var tipo_saldo = $('#formMainCuentasContabilidad #tipo_saldo').val();
    var orden_cuentas = $('#formMainCuentasContabilidad #orden_cuentas').val();

    $.ajax({
        method: "POST",
        url: "<?php echo SERVERURL;?>core/llenarDataTableCuentas.php",
        data: {
            fechai: fechai,
            fechaf: fechaf,
            estado: estado,
            buscar: buscar,
            tipo_cuenta: tipo_cuenta,
            tipo_saldo: tipo_saldo,
            orden_cuentas: orden_cuentas
        },
        dataType: "json",
        beforeSend: function() {
            $("#cuentas-container").html(
                '<div class="col-12 text-center py-5">' +
                    '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>' +
                    '<p class="mt-3 mb-0 text-muted font-weight-bold">Cargando cuentas...</p>' +
                '</div>'
            );
        },
        success: function(response) {
            cuentasUltimosDatos = response && Array.isArray(response.data) ? response.data : [];
            cuentasBusquedaListado = $('#buscarCuentasListado').val() || '';
            cuentasDatosFiltrados = cuentasUltimosDatos.slice();
            cuentasPaginaActual = 1;
            cuentasAplicarBusquedaListado();

            if (response.data && response.data.length > 0) {
                let html = '';
                
                response.data.forEach(function(cuenta) {
                    const saldoNeto = parseFloat(cleanNumber(cuenta.neto));
                    const saldoClass = saldoNeto >= 0 ? 'positive-balance' : 'negative-balance';
                    const inversionClass = parseInt(cuenta.es_inversion || 0, 10) === 1 ? 'account-investment' : '';

                    const nombre = escapeHtml(cuenta.nombre);
                    const codigo = escapeHtml(cuenta.codigo || 'Sin código');
                    
                    html += `
                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100 card-account ${saldoClass} ${inversionClass}">
                            <div class="cuenta-card-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="cuenta-card-title-wrap">
                                        <h5 class="cuenta-card-title text-truncate" title="${nombre}">
                                            ${nombre}
                                        </h5>

                                        <span class="cuenta-card-code">
                                            <i class="fas fa-hashtag"></i>
                                            ${codigo}
                                        </span>
                                    </div>

                                    <div class="cuenta-badges-wrap">
                                        ${badgeEstadoCuenta(cuenta.estado)}
                                        ${badgeInversionCuenta(cuenta.es_inversion)}
                                    </div>
                                </div>
                            </div>

                            <div class="cuenta-card-body">
                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-history"></i>
                                        Saldo Anterior
                                    </span>
                                    <span class="cuenta-row-value">${cuenta.saldo_anterior}</span>
                                </div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-arrow-down"></i>
                                        Ingresos
                                    </span>
                                    <span class="cuenta-row-value text-success">${cuenta.ingreso}</span>
                                </div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-arrow-up"></i>
                                        Egresos
                                    </span>
                                    <span class="cuenta-row-value text-danger">${cuenta.egreso}</span>
                                </div>

                                <div class="cuenta-divider"></div>

                                <div class="cuenta-row">
                                    <span class="cuenta-row-label">
                                        <i class="fas fa-balance-scale"></i>
                                        Saldo Cierre
                                    </span>
                                    <span class="cuenta-row-value">${cuenta.saldo_cierre}</span>
                                </div>

                                <div class="cuenta-total-box d-flex justify-content-between align-items-center">
                                    <span class="cuenta-total-label">
                                        <i class="fas fa-wallet mr-1"></i>
                                        Saldo Total
                                    </span>

                                    <span class="cuenta-total-value ${saldoNeto >= 0 ? 'text-success' : 'text-danger'}">
                                        ${cuenta.neto}
                                    </span>
                                </div>
                            </div>

                            <div class="cuenta-card-footer">
                                ${renderAccionesCuenta(cuenta)}
                            </div>
                        </div>
                    </div>`;
                });
                
                cuentasRenderActual();

                $('[data-toggle="tooltip"]').tooltip({
                    container: "body",
                    placement: "top"
                });
            } else {
                cuentasUltimosDatos = [];
                cuentasDatosFiltrados = [];
                cuentasPaginaActual = 1;
                cuentasActualizarResumenGeneral();
                cuentasRenderActual();
                $("#cuentas-container").html(
                    '<div class="col-12 text-center py-5">' +
                        '<i class="fas fa-box-open fa-3x mb-3 text-muted"></i>' +
                        '<h4 class="text-muted mb-1">No se encontraron cuentas</h4>' +
                        '<p class="text-muted mb-0">No hay información disponible con los filtros seleccionados.</p>' +
                    '</div>'
                );
            }
        },
        error: function(xhr) {
            console.error("Error al cargar cuentas:", xhr.responseText);

            $("#cuentas-container").html(
                '<div class="col-12">' +
                    '<div class="alert alert-danger mb-0">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                        'Error al cargar las cuentas. Intente nuevamente.' +
                    '</div>' +
                '</div>'
            );
        }
    });
};

function editar_cuenta(cuentas_id) {
    var url = '<?php echo SERVERURL;?>core/editarCuentasContabilidad.php';

    $('#formCuentasContables #cuentas_id').val(cuentas_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            cuentas_id: cuentas_id
        },
        dataType: 'json',
        success: function(valores) {
            $('#formCuentasContables').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/modificarCuentaContabilidadAjax.php'
            });

            $('#formCuentasContables')[0].reset();

            $('#reg_cuentas').hide();
            $('#edi_cuentas').show();
            $('#delete_cuentas').hide();

            $('#formCuentasContables #cuentas_id').val(valores[0]);
            $('#formCuentasContables #cuenta_codigo').val(valores[1]);
            $('#formCuentasContables #cuenta_nombre').val(valores[2]);

            $('#formCuentasContables #cuentas_activo')
                .prop('checked', parseInt(valores[3] || 0, 10) === 1)
                .prop('disabled', false);

            $('#formCuentasContables #es_inversion')
                .prop('checked', parseInt(valores[4] || 0, 10) === 1);

            actualizarLabelEstadoCuenta();

            $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
            $('#formCuentasContables #estado_cuentas_contables').show();
            $('#formCuentasContables #cuenta_codigo').attr("readonly", true);
            $('#formCuentasContables #pro_cuentas').val("Editar");
            
            $('#modalCuentascontables').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr) {
            console.error("Error al editar cuenta:", xhr.responseText);
            showNotify("error", "Error", "No se pudieron cargar los datos de la cuenta.");
        }
    });
}

function eliminar_cuenta(cuentas_id, nombreCuenta) {
    var mensajeHTML = `¿Desea eliminar permanentemente la cuenta?<br><br>
                    <strong>Nombre:</strong> ${escapeHtml(nombreCuenta)}`;
    
    swal({
        title: "Confirmar eliminación",
        content: {
            element: "span",
            attributes: {
                innerHTML: mensajeHTML
            }
        },
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                value: null,
                visible: true,
                className: "btn-light"
            },
            confirm: {
                text: "Sí, eliminar",
                value: true,
                className: "btn-danger",
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((confirmar) => {
        if (confirmar) {
            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>ajax/eliminarCuentaContabilidadAjax.php',
                data: {
                    cuentas_id: cuentas_id
                },
                dataType: 'json',
                beforeSend: function() {
                    swal({
                        title: "Eliminando...",
                        text: "Por favor espere",
                        icon: "info",
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    });
                },
                success: function(response) {
                    swal.close();
                    
                    if (response.status === "success") {
                        swal({
                            title: response.title,
                            text: response.message,
                            icon: "success",
                            timer: 2000,
                            buttons: false
                        });

                        listar_cuentas_contabilidad();
                    } else {
                        swal({
                            title: response.title,
                            text: response.message,
                            icon: "error"
                        });
                    }
                },
                error: function(xhr) {
                    swal.close();

                    console.error("Error al eliminar cuenta:", xhr.responseText);

                    swal({
                        title: "Error",
                        text: "Ocurrió un error al procesar la solicitud",
                        icon: "error"
                    });
                }
            });
        }
    });        
}

function modal_cuentas_contables() {
    $('#formCuentasContables').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL;?>ajax/addCuentasContablesAjax.php'
    });

    $('#formCuentasContables')[0].reset();

    $('#formCuentasContables #cuentas_id').val('');
    $('#formCuentasContables #es_inversion').prop('checked', false);
    $('#formCuentasContables #cuentas_activo').prop('checked', true).prop('disabled', false);

    actualizarLabelEstadoCuenta();

    $('#reg_cuentas').show();
    $('#edi_cuentas').hide();
    $('#delete_cuentas').hide();

    $('#formCuentasContables #cuenta_codigo').attr("readonly", false);
    $('#formCuentasContables #cuenta_nombre').attr("readonly", false);
    $('#formCuentasContables #estado_cuentas_contables').hide();
    $('#formCuentasContables #pro_cuentas').val("Registro");

    $('#modalCuentascontables').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
</script>