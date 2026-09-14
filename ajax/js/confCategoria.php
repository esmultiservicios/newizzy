<script>

/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Categoría Productos
   Mantiene la lógica y endpoints existentes; el DataTable
   original queda únicamente como fuente interna oculta.
   ========================================================= */

if (typeof window.izzySwalLegacy !== 'function') {
    window.izzySwalLegacy = function(options) {
        options = options || {};

        /*
         * IZZY already exposes the confirmation API as swal(...).
         * Keep that API first so existing behavior is not changed.
         */
        if (typeof swal === 'function') {
            return swal(options);
        }

        if (typeof showNotify === 'function') {
            showNotify('error', 'Confirmación no disponible', 'No se encontró swal cargado en la plantilla.');
        }

        return Promise.resolve(false);
    };
}

var categoriaModernConfig = {"table":"#dataTableConfCategorias","key":"categoria_id","title":"Categoría Productos","exportTitle":"Reporte Categoría Productos","fields":[{"key":"nombre","label":"Categoría","icon":"fas fa-tag","type":"text"},{"key":"estado","label":"Estado","icon":"fas fa-toggle-on","type":"status"}],"actions":[{"label":"Editar","icon":"fas fa-edit","target":"button.table_editar","classes":"accion-editar table_editar ocultar"},{"label":"Eliminar","icon":"fas fa-trash-alt","target":"button.table_eliminar","classes":"accion-eliminar table_eliminar ocultar"}],"kpis":[{"id":"total","label":"Categorías","desc":"Total de categorías encontradas","icon":"fas fa-tags","color":"blue","calc":{"type":"count"}},{"id":"activos","label":"Activas","desc":"Categorías activas","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"estado","value":"1"}},{"id":"inactivos","label":"Inactivas","desc":"Categorías inactivas","icon":"fas fa-times-circle","color":"orange","calc":{"type":"eq","key":"estado","value":"0"}}],"storageView":"izzy.confCategoria.tipo_vista"};

var categoriaModern = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    pageSizeDetalle: 10,
    pageSizeMiniatura: 6,
    view: 'detalle',
    preferredView: 'detalle',
    search: '',
    loading: true
};

function categoriaModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function categoriaModernEscape(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function categoriaModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function categoriaModernEstado(value) {
    return parseInt(value, 10) === 1;
}

function categoriaModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = categoriaModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return categoriaModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return categoriaModernEstado(value)
            ? '<span class="categoria-productos-status-badge categoria-productos-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="categoria-productos-status-badge categoria-productos-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : categoriaModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + categoriaModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return categoriaModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="categoria-productos-method-badge categoria-productos-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="categoria-productos-method-badge categoria-productos-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="categoria-productos-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = categoriaModernTexto(row.graph_user, 'No configurado');
        var tenant = categoriaModernTexto(row.tenant_id, 'No configurado');
        var client = categoriaModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="categoria-productos-tech-lines">' +
            '<span><strong>Graph:</strong> ' + categoriaModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + categoriaModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + categoriaModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : categoriaModernEscape(text);
}

function categoriaModernClaseIconoAccion(action) {
    var classes = String(action && action.classes ? action.classes : '');
    var label = String(action && action.label ? action.label : '').toLowerCase();

    if (classes.indexOf('accion-editar') !== -1 || label.indexOf('editar') !== -1) {
        return 'accion-icon-editar';
    }

    if (classes.indexOf('accion-eliminar') !== -1 || label.indexOf('eliminar') !== -1) {
        return 'accion-icon-eliminar';
    }

    if (label.indexOf('hist') !== -1 || label.indexOf('asignar') !== -1 || label.indexOf('cambiar') !== -1) {
        return 'accion-icon-ver';
    }

    return 'accion-icon-ver';
}

function categoriaModernAcciones(row) {
    var key = categoriaModernEscape(categoriaModernTexto(row[categoriaModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown categoria-productos-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    categoriaModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            categoriaModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + categoriaModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + categoriaModernClaseIconoAccion(action) + '"><i class="' + categoriaModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + categoriaModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}

function categoriaModernFiltrar() {
    var q = $.trim(categoriaModern.search || '').toLowerCase();

    categoriaModern.filtered = !q
        ? categoriaModern.rows.slice()
        : categoriaModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    categoriaModernActualizarKpis();
}

function categoriaModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return categoriaModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return categoriaModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return categoriaModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        categoriaModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return categoriaModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return categoriaModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function categoriaModernActualizarKpis() {
    categoriaModernConfig.kpis.forEach(function(kpi) {
        $('#categoria-productos-kpi-' + kpi.id).text(categoriaModernCalcularKpi(kpi.calc));
    });
}

function categoriaModernRenderDetalle(rows) {
    var html = '<div class="categoria-productos-detail-header"><div>Acciones</div>';

    categoriaModernConfig.fields.forEach(function(field) {
        html += '<div>' + categoriaModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = categoriaModernEscape(categoriaModernTexto(row[categoriaModernConfig.key], ''));

        html += '<article class="categoria-productos-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="categoria-productos-cell categoria-productos-actions-cell">' +
                '<span class="categoria-productos-cell-label">Acciones</span>' +
                categoriaModernAcciones(row) +
            '</div>';

        categoriaModernConfig.fields.forEach(function(field) {
            var contenidoCampo = field.type === 'status'
                ? '<div class="categoria-productos-status-only">' + categoriaModernFormatoCampo(row, field, false) + '</div>'
                : '<div class="categoria-productos-field-main">' +
                    '<i class="' + categoriaModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="categoria-productos-field-value">' + categoriaModernFormatoCampo(row, field, false) + '</div>' +
                  '</div>';

            html += '<div class="categoria-productos-cell' + (field.type === 'status' ? ' categoria-productos-status-cell' : '') + '">' +
                '<span class="categoria-productos-cell-label">' + categoriaModernEscape(field.label) + '</span>' +
                '<div class="categoria-productos-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>';
    });

    return html;
}

function categoriaModernRenderMiniatura(rows) {
    var html = '<div class="categoria-productos-mini-grid">';
    var primary = categoriaModernConfig.fields[0];
    var statusField = null;

    categoriaModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = categoriaModernEscape(categoriaModernTexto(row[categoriaModernConfig.key], ''));
        var primaryValue = primary ? categoriaModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? categoriaModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="categoria-productos-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="categoria-productos-mini-topline"></div>' +
            '<div class="categoria-productos-mini-header">' +
                '<span class="categoria-productos-mini-icon"><i class="' + categoriaModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="categoria-productos-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + categoriaModernEscape(categoriaModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="categoria-productos-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="categoria-productos-mini-body">';

        categoriaModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="categoria-productos-mini-field">' +
                '<span><i class="' + categoriaModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    categoriaModernEscape(field.label) + '</span>' +
                '<div>' + categoriaModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="categoria-productos-mini-footer">' + categoriaModernAcciones(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function categoriaModernRenderPaginacion(totalPages) {
    var current = categoriaModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="categoria-productos-page-btn' + (active ? ' active' : '') +
            '" data-page="' + page + '"' + (disabled ? ' disabled' : '') + '>' +
            (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
        '</button>';
    }

    html += button('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
    html += button('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

    var from = Math.max(1, current - 2);
    var to = Math.min(totalPages, from + 4);
    from = Math.max(1, to - 4);

    for (var page = from; page <= to; page++) {
        html += button(String(page), page, false, page === current, '');
    }

    html += button('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
    html += button('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

    $('#categoria-productos-pagination').html(html);
}

function categoriaModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function categoriaModernRender() {
    var rows = categoriaModern.filtered || [];

    if (categoriaModern.loading) {
        $('#categoria-productos-listado').html(
            '<div class="categoria-productos-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#categoria-productos-info').text('0 registros');
        $('#categoria-productos-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#categoria-productos-listado').html(
            '<div class="categoria-productos-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#categoria-productos-info').text('0 registros');
        $('#categoria-productos-pagination').empty();
        categoriaModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / categoriaModern.pageSize));

    if (categoriaModern.page > totalPages) {
        categoriaModern.page = totalPages;
    }

    var offset = (categoriaModern.page - 1) * categoriaModern.pageSize;
    var pageRows = rows.slice(offset, offset + categoriaModern.pageSize);

    $('#categoria-productos-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + categoriaModern.view)
        .html(
            categoriaModern.view === 'miniatura'
                ? categoriaModernRenderMiniatura(pageRows)
                : categoriaModernRenderDetalle(pageRows)
        );

    $('#categoria-productos-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    categoriaModernRenderPaginacion(totalPages);
    categoriaModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function categoriaModernSyncPageSize() {
    var mini = categoriaModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? categoriaModern.pageSizeMiniatura : categoriaModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#categoria-productos-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    categoriaModern.pageSize = selected;
    $select.val(String(selected));
}

function categoriaModernSyncView() {
    var mobile = categoriaModernEsMovil();

    if (mobile) {
        categoriaModern.view = 'miniatura';
    }

    $('.categoria-productos-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.categoria-productos-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.categoria-productos-view-btn[data-view="' + categoriaModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function categoriaModernConfigurarPanel(button, content, storageKey) {
    var visible = true;

    try {
        var saved = localStorage.getItem(storageKey);
        if (saved !== null) {
            visible = saved === '1';
        }
    } catch (e) {}

    function sync() {
        $(content).toggle(visible);
        $(button).attr('aria-expanded', visible ? 'true' : 'false');
        $(button).find('span').text(visible ? 'Ocultar' : 'Mostrar');
        $(button).find('i')
            .toggleClass('fa-chevron-up', visible)
            .toggleClass('fa-chevron-down', !visible);
    }

    sync();

    $(button).off('click.categoriaModernPanel').on('click.categoriaModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function categoriaModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableConfCategorias")) {
        return null;
    }

    var api = $("#dataTableConfCategorias").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["categoria_id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function categoriaModernEjecutarAccion(recordKey, targetSelector) {
    var row = categoriaModernEncontrarFilaEnDataTable(recordKey);

    if (!row) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Error', 'No fue posible identificar el registro seleccionado.');
        }
        return;
    }

    var node = row.node();

    if (!node) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Error', 'No fue posible ejecutar la acción seleccionada.');
        }
        return;
    }

    var $target = $(node).find(targetSelector).first();

    if (!$target.length) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Acción no disponible', 'La acción seleccionada no está disponible para este registro.');
        }
        return;
    }

    $target.trigger('click');
}

function categoriaModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function categoriaModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function categoriaModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = categoriaModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function categoriaModernDescargarBlob(blob, nombreArchivo) {
    var enlace = document.createElement('a');
    var url = URL.createObjectURL(blob);

    enlace.href = url;
    enlace.download = nombreArchivo;
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);

    setTimeout(function() {
        URL.revokeObjectURL(url);
    }, 1000);
}

function categoriaModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function categoriaModernDatosExcel() {
    return (categoriaModern.filtered || []).map(function(row) {
        return categoriaModernConfig.fields.map(function(field) {
            return categoriaModernFormatoCampo(row, field, true);
        });
    });
}

function categoriaModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = categoriaModernConfig.fields || [];
    var headers = fields.map(function(field) {
        return field.label;
    });

    if (!headers.length) {
        return Promise.reject(new Error('No hay columnas configuradas para exportar.'));
    }

    var statusIndex = -1;

    fields.some(function(field, index) {
        if (field.type === 'status') {
            statusIndex = index;
            return true;
        }
        return false;
    });

    var totalActivos = statusIndex >= 0
        ? rows.filter(function(row) {
            return String(row[statusIndex] || '').toLowerCase() === 'activo';
        }).length
        : 0;

    var totalInactivos = statusIndex >= 0 ? rows.length - totalActivos : 0;
    var lastCol = categoriaModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            categoriaModernExcelCell(
                'A1',
                'IZZY • ' + String(categoriaModernConfig.exportTitle || categoriaModernConfig.title || 'REPORTE').toUpperCase(),
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            categoriaModernExcelCell(
                'A2',
                'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        categoriaModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        categoriaModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += categoriaModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += categoriaModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += categoriaModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += categoriaModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            categoriaModernExcelCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return categoriaModernExcelCell(
            categoriaModernExcelColName(index) + headerRow,
            header,
            3,
            false
        );
    }).join('');

    sheetRows.push(
        '<row r="' + headerRow + '" ht="26" customHeight="1">' +
            headerCells +
        '</row>'
    );

    rows.forEach(function(row, rowIndex) {
        var excelRow = firstDataRow + rowIndex;

        var cells = row.map(function(value, colIndex) {
            var style = 4;

            if (colIndex === statusIndex) {
                style = String(value || '').toLowerCase() === 'activo' ? 9 : 10;
            }

            return categoriaModernExcelCell(
                categoriaModernExcelColName(colIndex) + excelRow,
                value,
                style,
                false
            );
        }).join('');

        sheetRows.push(
            '<row r="' + excelRow + '" ht="22" customHeight="1">' +
                cells +
            '</row>'
        );
    });

    var colsXml = '';

    headers.forEach(function(header, index) {
        var width = Math.max(14, Math.min(42, String(header || '').length + 16));

        fields[index] && fields[index].type === 'emailTech'
            ? width = 42
            : width;

        colsXml += '<col min="' + (index + 1) + '" max="' + (index + 1) +
            '" width="' + width + '" customWidth="1"/>';
    });

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
            '<sheetViews>' +
                '<sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView>' +
            '</sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' + colsXml + '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
            '<mergeCells count="2">' +
                '<mergeCell ref="A1:' + lastCol + '1"/>' +
                '<mergeCell ref="A2:' + lastCol + '2"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="7">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="7">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/><bgColor indexed="64"/></patternFill></fill>' +
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
            '<cellStyleXfs count="1">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' +
            '</cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" ' +
                'Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" ' +
                'Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" ' +
                'Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var contentTypes =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
            '<Default Extension="xml" ContentType="application/xml"/>' +
            '<Override PartName="/xl/workbook.xml" ' +
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
            '<Override PartName="/xl/worksheets/sheet1.xml" ' +
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
            '<Override PartName="/xl/styles.xml" ' +
                'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
        '</Types>';

    var zip = new JSZip();

    zip.file('[Content_Types].xml', contentTypes);
    zip.folder('_rels').file('.rels', rootRels);
    zip.folder('xl').file('workbook.xml', workbookXml);
    zip.folder('xl').file('styles.xml', stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

    var opcionesZip = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    if (typeof zip.generateAsync === 'function') {
        return zip.generateAsync(opcionesZip);
    }

    if (typeof zip.generate === 'function') {
        try {
            return Promise.resolve(zip.generate(opcionesZip));
        } catch (errorGenerate) {
            console.error('Error al generar XLSX con JSZip legado:', errorGenerate);
            return Promise.reject(errorGenerate);
        }
    }

    return Promise.reject(
        new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().')
    );
}

function categoriaModernExportarExcel() {
    var rows = categoriaModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = categoriaModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(categoriaModernConfig.exportTitle || categoriaModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            categoriaModernDescargarBlob(
                blob,
                nombre + '_' + categoriaModernFechaArchivo() + '.xlsx'
            );
        })
        .catch(function(error) {
            console.error('Error al generar XLSX:', error);

            if (typeof showNotify === 'function') {
                showNotify(
                    'error',
                    'Error al generar Excel',
                    'No se pudo generar el archivo Excel.'
                );
            }
        });
}

function categoriaModernPdfObtenerLogo(callback) {
    function convertirLogo(source) {
        source = String(source || '').trim();

        if (!source) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
            }
            return;
        }

        if (source.indexOf('data:image/') === 0) {
            callback(source);
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
                var dataUrl = canvas.toDataURL('image/png');
                try { imagen = dataUrl; } catch (e) {}
                callback(dataUrl);
            } catch (error) {
                console.error('Error preparando logo PDF:', error);
                if (typeof showNotify === 'function') {
                    showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
                }
            }
        };

        img.onerror = function() {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
            }
        };

        img.src = source;
    }

    if (typeof imagen !== 'undefined' && imagen) {
        convertirLogo(imagen);
        return;
    }

    $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL;?>core/get_image.php',
        dataType: 'text',
        timeout: 15000
    }).done(function(imageUrl) {
        convertirLogo(imageUrl);
    }).fail(function(xhr) {
        console.error('Error obteniendo logo PDF:', xhr.responseText);
        if (typeof showNotify === 'function') {
            showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
        }
    });
}

function categoriaModernPdfLogoPlate(logoDataUrl) {
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

function categoriaModernPdfDetalle(rows) {
    var fields = categoriaModernConfig.fields;
    var header = fields.map(function(field) {
        return {
            text: field.label,
            bold: true,
            color: '#FFFFFF',
            fillColor: '#17324D'
        };
    });
    var body = [header];

    rows.forEach(function(row, index) {
        var fill = index % 2 === 0 ? '#F7F9FC' : '#FFFFFF';

        body.push(fields.map(function(field) {
            var value = String(categoriaModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = categoriaModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
                cell.bold = true;
            }

            return cell;
        }));
    });

    return {
        table: {
            headerRows: 1,
            widths: fields.map(function() { return '*'; }),
            body: body
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .6; },
            vLineWidth: function() { return .6; },
            paddingLeft: function() { return 5; },
            paddingRight: function() { return 5; },
            paddingTop: function() { return 5; },
            paddingBottom: function() { return 5; }
        },
        fontSize: 7.4
    };
}

function categoriaModernPdfTarjeta(row) {
    var fields = categoriaModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(categoriaModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(categoriaModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && categoriaModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(categoriaModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
            ]
        });
    });

    return {
        table: {
            widths: ['*'],
            body: [[{
                margin: [10, 9, 10, 9],
                stack: [
                    {
                        columns: [
                            { width: '*', text: title, bold: true, fontSize: 10.5, color: '#172B4D' },
                            statusField ? { width: 62, text: estado, bold: true, fontSize: 7.2, color: estadoColor, alignment: 'right' } : { width: 1, text: '' }
                        ]
                    },
                    {
                        canvas: [{ type: 'line', x1: 0, y1: 0, x2: 330, y2: 0, lineWidth: .6, lineColor: '#DDE3EA' }],
                        margin: [0, 7, 0, 7]
                    },
                    { stack: detailStack }
                ]
            }]]
        },
        layout: {
            hLineColor: function() { return '#DDE3EA'; },
            vLineColor: function() { return '#DDE3EA'; },
            hLineWidth: function() { return .7; },
            vLineWidth: function() { return .7; }
        }
    };
}

function categoriaModernExportarPdf() {
    var rows = categoriaModern.filtered || [];

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin información', 'No hay registros para exportar.');
        }
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        if (typeof showNotify === 'function') {
            showNotify('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        }
        return;
    }

    if (!(typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/') === 0)) {
        categoriaModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            categoriaModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#categoria-productos-search').val() || '').trim();
    var filtroEstado = $("#estado_categorias").length
        ? ($("#estado_categorias" + ' option:selected').text() || 'Todos')
        : 'Todos';
    var filtrosTexto = 'Estado: ' + filtroEstado + '   |   Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = categoriaModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: categoriaModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Catálogo de categorías de productos", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
            ] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,12,10], stack: [
                { text: 'REPORTE EJECUTIVO', fontSize: 6.5, bold: true, color: '#72E2E5', alignment: 'right' },
                { text: new Date().toLocaleDateString('es-HN'), fontSize: 9, bold: true, color: '#FFFFFF', alignment: 'right', margin: [0,3,0,0] },
                { text: rows.length + ' registro(s) filtrado(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right', margin: [0,2,0,0] }
            ] }
        ]] },
        layout: { hLineWidth: function() { return 0; }, vLineWidth: function() { return 0; } },
        margin: [0,0,0,10]
    };

    var filtros = {
        table: { widths: ['*'], body: [[{ text: 'Filtros aplicados: ' + filtrosTexto, fontSize: 6.8, color: '#52627A', margin: [10,7,10,7], fillColor: '#F7F9FC' }]] },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,10]
    };

    var kpis = categoriaModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(categoriaModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (categoriaModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [categoriaModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [categoriaModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [categoriaModernPdfDetalle(rows)];
    }

    var docDefinition = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [28,28,28,34],
        header: function() {
            return { margin: [28,12,28,0], canvas: [{ type: 'line', x1: 0, y1: 0, x2: 736, y2: 0, lineWidth: 2, lineColor: '#0EA5A8' }] };
        },
        footer: function(currentPage, pageCount) {
            return { margin: [28,8,28,0], columns: [
                { text: 'IZZY • ' + categoriaModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: categoriaModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = categoriaModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, categoriaModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(categoriaModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            categoriaModern.preferredView = savedView;
            categoriaModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableConfCategorias")
        .off('xhr.dt.categoriaModern')
        .on('xhr.dt.categoriaModern', function(e, settings, json) {
            categoriaModern.rows = json && Array.isArray(json.data) ? json.data : [];
            categoriaModern.loading = false;
            categoriaModern.page = 1;
            categoriaModernFiltrar();
            categoriaModernRender();
        });

    categoriaModernSyncView();
    categoriaModernSyncPageSize();
    categoriaModernRender();

    if ($('#categoria-productos-toggle-filtros').length) {
        categoriaModernConfigurarPanel(
            '#categoria-productos-toggle-filtros',
            '#categoria-productos-filtros-contenido',
            'izzy.confCategoria.panel.filtros'
        );
    }

    categoriaModernConfigurarPanel(
        '#categoria-productos-toggle-kpis',
        '#categoria-productos-kpis-contenido',
        'izzy.confCategoria.panel.kpis'
    );

    $('#categoria-productos-btn-refresh').off('click.categoriaModern').on('click.categoriaModern', function() {
        if (typeof listar_categoria_productos === 'function') {
            categoriaModern.loading = true;
            categoriaModernRender();
            listar_categoria_productos();
        }
    });

    $('#categoria-productos-btn-create').off('click.categoriaModern').on('click.categoriaModern', function() {
        if (typeof modalCategoriaProductos === 'function') {
            modalCategoriaProductos();
        }
    });

    $('#categoria-productos-btn-excel').off('click.categoriaModern').on('click.categoriaModern', categoriaModernExportarExcel);
    $('#categoria-productos-btn-pdf').off('click.categoriaModern').on('click.categoriaModern', categoriaModernExportarPdf);

    $('#categoria-productos-page-size').off('change.categoriaModern').on('change.categoriaModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (categoriaModern.view === 'miniatura') {
            categoriaModern.pageSizeMiniatura = value;
        } else {
            categoriaModern.pageSizeDetalle = value;
        }

        categoriaModern.pageSize = value;
        categoriaModern.page = 1;
        categoriaModernRender();
    });

    $('.categoria-productos-view-btn').off('click.categoriaModern').on('click.categoriaModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (next === 'detalle' && categoriaModernEsMovil()) {
            return;
        }

        categoriaModern.view = next;
        categoriaModern.page = 1;

        if (!categoriaModernEsMovil()) {
            categoriaModern.preferredView = next;

            try {
                localStorage.setItem(categoriaModernConfig.storageView, categoriaModern.preferredView);
            } catch (e) {}
        }

        categoriaModernSyncView();
        categoriaModernSyncPageSize();
        categoriaModernRender();
    });

    $('#categoria-productos-search').off('input.categoriaModern').on('input.categoriaModern', function() {
        categoriaModern.search = $(this).val() || '';
        categoriaModern.page = 1;
        categoriaModernFiltrar();
        categoriaModernRender();
    });

    $('#categoria-productos-search-clear').off('click.categoriaModern').on('click.categoriaModern', function() {
        $('#categoria-productos-search').val('').focus();
        categoriaModern.search = '';
        categoriaModern.page = 1;
        categoriaModernFiltrar();
        categoriaModernRender();
    });

    $('#categoria-productos-pagination')
        .off('click.categoriaModern', '.categoria-productos-page-btn')
        .on('click.categoriaModern', '.categoria-productos-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                categoriaModern.page = page;
                categoriaModernRender();

                var target = document.querySelector('.categoria-productos-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#categoria-productos-listado')
        .off('click.categoriaModern', '.modern-action-bridge')
        .on('click.categoriaModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            categoriaModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#categoria-productos-listado')
        .off('click.categoriaModernDropdown', '.js-acciones-toggle')
        .on('click.categoriaModernDropdown', '.js-acciones-toggle', function() {
            $('.categoria-productos-detail-row, .categoria-productos-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.categoria-productos-detail-row, .categoria-productos-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.categoriaModernDropdownClose')
        .on('click.categoriaModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.categoria-productos-detail-row, .categoria-productos-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.categoriaModern')
        .on('resize.categoriaModern', function() {
            var previous = categoriaModern.view;

            if (categoriaModernEsMovil()) {
                categoriaModern.view = 'miniatura';
            } else {
                categoriaModern.view = categoriaModern.preferredView;
            }

            if (previous !== categoriaModern.view) {
                categoriaModern.page = 1;
                categoriaModernSyncPageSize();
            }

            categoriaModernSyncView();
            categoriaModernRender();
        });
});


$(document).ready(function() {
    listar_categoria_productos();

	$('#form_main_categorias #search').on("click", function (e) {
            e.preventDefault();
            listar_categoria_productos();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_categorias').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('select') // Usa `this` para referenciar el formulario actual
			.val('')
			.trigger('change');

			listar_categoria_productos();
	});    
});

/* =========================================================
   HEADER DINÁMICO - CATEGORÍA PRODUCTOS
   ========================================================= */
   function construirHeaderDataTableConfCategorias() {
    var $tabla = $("#dataTableConfCategorias");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Categoría</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO CONF CATEGORIAS
var listar_categoria_productos = function() {
    var estado = $('#form_main_categorias #estado_categorias').val();

    if ($.fn.DataTable.isDataTable("#dataTableConfCategorias")) {
        $("#dataTableConfCategorias").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfCategorias();

    var table_categoria_productos = $("#dataTableConfCategorias").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableCategoriaProductos.php",
            "data": {
                "estado": estado
            }
        },
        "columns": [
            {
                "data": null,
                "orderable": false,
                "searchable": false,
                "className": "text-center align-middle",
                "render": function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +
                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-trash-alt"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Eliminar</span>' +
                                '</button>' +

                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": "nombre"
            },
            {
                "data": "estado",
                "render": function(data, type, row) {
                    if (type === 'display') {
                        var estadoText = data == 1 ? 'Activo' : 'Inactivo';
                        var icon = data == 1 ?
                            '<i class="fas fa-check-circle mr-1"></i>' :
                            '<i class="fas fa-times-circle mr-1"></i>';
                        var badgeClass = data == 1 ?
                            'badge badge-pill badge-success' :
                            'badge badge-pill badge-danger';

                        return '<span class="' + badgeClass +
                            '" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">' +
                            icon + estadoText + '</span>';
                    }

                    return data;
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            {
                width: "12%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "73%",
                targets: 1
            },
            {
                width: "15%",
                targets: 2,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Categoria Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_categoria_productos();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Categoria Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalCategoriaProductos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Categoria Productos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Categoria Productos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1]
                },
                customize: function(doc) {
                    if (imagen) {
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

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }
        }
    });

    table_categoria_productos.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    edit_categorias_productos_dataTable("#dataTableConfCategorias tbody", table_categoria_productos);
    delete_categorias_productos_dataTable("#dataTableConfCategorias tbody", table_categoria_productos);
}

var edit_categorias_productos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarCategoriaProductos.php';
        $('#formCategoriaProductos #categoria_id').val(data.categoria_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formCategoriaProductos').serialize(),
            success: function(registro) {
                var valores = eval(registro);
                $('#formCategoriaProductos').attr({
                    'data-form': 'update'
                });
                $('#formCategoriaProductos').attr({
                    'action': '<?php echo SERVERURL;?>ajax/modificarCategoriaProductosAjax.php'
                });
                $('#formCategoriaProductos')[0].reset();
                $('#reg_catProd').hide();
                $('#edi_catProd').show();
                $('#delete_catProd').hide();
                $('#formCategoriaProductos #pro_categoria_productos').val("Editar");
                $('#formCategoriaProductos #categoria_productos').val(valores[1]);

                if (valores[2] == 1) {
                    $('#formCategoriaProductos #categoria_producto_activo').attr('checked',
                        true);
                } else {
                    $('#formCategoriaProductos #categoria_producto_activo').attr('checked',
                        false);
                }

                //HABILITAR OBJETOS
                $('#formCategoriaProductos #categoria_productos').attr('readonly', false);
                $('#formCategoriaProductos #categoria_producto_activo').attr('disabled', false);
                $('#formCategoriaProductos #estado_categoria_productos').show();

                $('#modalcategoria_productos').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
}

var delete_categorias_productos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var categoria_id = data.categoria_id;
        var nombreCategoria = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la categoría de producto?<br><br>
                        <strong>Nombre:</strong> ${nombreCategoria}`;
        
        window.izzySwalLegacy({
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarCategoriaProductosAjax.php',
                    data: {
                        categoria_id: categoria_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    beforeSend: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        Swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            table.ajax.reload(null, false); // Recargar tabla sin resetear paginación
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
    });
}
//FIN CONF CATEGORIAS

//INICIO FORMULARIO CATEGORIA PRODUCTOS
function modalCategoriaProductos() {
    $('#formCategoriaProductos').attr({
        'data-form': 'save'
    });
    $('#formCategoriaProductos').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addCategoriaProductosAjax.php'
    });
    $('#formCategoriaProductos')[0].reset();
    $('#formCategoriaProductos #pro_categoria_productos').val("Registro");
    $('#reg_catProd').show();
    $('#edi_catProd').hide();
    $('#delete_catProd').hide();

    //HABILITAR OBJETOS
    $('#formCategoriaProductos #categoria_productos').attr('readonly', false);
    $('#formCategoriaProductos #categoria_producto_activo').attr('disabled', false);
    $('#formCategoriaProductos #estado_categoria_productos').hide();

    $('#modalcategoria_productos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN FORMULARIO CATEGORIA PRODUCTOS

$(document).ready(function() {
    $("#modalcategoria_productos").on('shown.bs.modal', function() {
        $(this).find('#formCategoriaProductos #categoria_productos').focus();
    });
});

$('#formCategoriaProductos #label_categoria_producto_activo').html("Activo");

$('#formCategoriaProductos .switch').change(function() {
    if ($('input[name=categoria_producto_activo]').is(':checked')) {
        $('#formCategoriaProductos #label_categoria_producto_activo').html("Activo");
        return true;
    } else {
        $('#formCategoriaProductos #label_categoria_producto_activo').html("Inactivo");
        return false;
    }
});
</script>