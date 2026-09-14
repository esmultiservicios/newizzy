<script>

/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Impresora
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

var impresoraModernConfig = {"table":"#dataTableConfImpresora","key":"impresora_id","title":"Impresora","exportTitle":"Reporte Impresora","fields":[{"key":"descripcion","label":"Descripción","icon":"fas fa-print","type":"text"},{"key":"estado","label":"Estado","icon":"fas fa-toggle-on","type":"status"}],"actions":[{"label":"Cambiar estado","icon":"fas fa-toggle-on","target":"button.table_impresora","classes":"table_impresora table_editar"}],"kpis":[{"id":"total","label":"Impresoras","desc":"Configuraciones encontradas","icon":"fas fa-print","color":"blue","calc":{"type":"count"}},{"id":"activos","label":"Activas","desc":"Impresoras activas","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"estado","value":"1"}},{"id":"inactivos","label":"Inactivas","desc":"Impresoras inactivas","icon":"fas fa-times-circle","color":"orange","calc":{"type":"eq","key":"estado","value":"0"}}],"storageView":"izzy.confImpresora.tipo_vista"};

var impresoraModern = {
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

function impresoraModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function impresoraModernEscape(value) {
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

function impresoraModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function impresoraModernEstado(value) {
    if (value === true || value === 1) {
        return true;
    }

    var normalizado = String(value == null ? '' : value).trim().toLowerCase();

    return normalizado === '1' ||
        normalizado === 'activo' ||
        normalizado === 'activado' ||
        normalizado === 'true' ||
        normalizado === 'si' ||
        normalizado === 'sí';
}

function impresoraModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = impresoraModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return impresoraModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return impresoraModernEstado(value)
            ? '<span class="impresora-config-status-badge impresora-config-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="impresora-config-status-badge impresora-config-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : impresoraModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + impresoraModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return impresoraModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="impresora-config-method-badge impresora-config-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="impresora-config-method-badge impresora-config-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="impresora-config-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = impresoraModernTexto(row.graph_user, 'No configurado');
        var tenant = impresoraModernTexto(row.tenant_id, 'No configurado');
        var client = impresoraModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="impresora-config-tech-lines">' +
            '<span><strong>Graph:</strong> ' + impresoraModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + impresoraModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + impresoraModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : impresoraModernEscape(text);
}

function impresoraModernClaseIconoAccion(action) {
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

function impresoraModernAcciones(row) {
    var key = impresoraModernEscape(impresoraModernTexto(row[impresoraModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown impresora-config-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    impresoraModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            impresoraModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + impresoraModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + impresoraModernClaseIconoAccion(action) + '"><i class="' + impresoraModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + impresoraModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}

function impresoraModernFiltrar() {
    var q = $.trim(impresoraModern.search || '').toLowerCase();

    impresoraModern.filtered = !q
        ? impresoraModern.rows.slice()
        : impresoraModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    impresoraModernActualizarKpis();
}

function impresoraModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return impresoraModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return impresoraModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return impresoraModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        impresoraModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return impresoraModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return impresoraModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function impresoraModernActualizarKpis() {
    impresoraModernConfig.kpis.forEach(function(kpi) {
        $('#impresora-config-kpi-' + kpi.id).text(impresoraModernCalcularKpi(kpi.calc));
    });
}

function impresoraModernRenderDetalle(rows) {
    var html = '<div class="impresora-config-detail-header"><div>Acciones</div>';

    impresoraModernConfig.fields.forEach(function(field) {
        html += '<div>' + impresoraModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = impresoraModernEscape(impresoraModernTexto(row[impresoraModernConfig.key], ''));

        html += '<article class="impresora-config-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="impresora-config-cell impresora-config-actions-cell">' +
                '<span class="impresora-config-cell-label">Acciones</span>' +
                impresoraModernAcciones(row) +
            '</div>';

        impresoraModernConfig.fields.forEach(function(field) {
            var contenidoCampo = field.type === 'status'
                ? '<div class="impresora-config-status-only">' + impresoraModernFormatoCampo(row, field, false) + '</div>'
                : '<div class="impresora-config-field-main">' +
                    '<i class="' + impresoraModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="impresora-config-field-value">' + impresoraModernFormatoCampo(row, field, false) + '</div>' +
                  '</div>';

            html += '<div class="impresora-config-cell' + (field.type === 'status' ? ' impresora-config-status-cell' : '') + '">' +
                '<span class="impresora-config-cell-label">' + impresoraModernEscape(field.label) + '</span>' +
                '<div class="impresora-config-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>';
    });

    return html;
}

function impresoraModernRenderMiniatura(rows) {
    var html = '<div class="impresora-config-mini-grid">';
    var primary = impresoraModernConfig.fields[0];
    var statusField = null;

    impresoraModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = impresoraModernEscape(impresoraModernTexto(row[impresoraModernConfig.key], ''));
        var primaryValue = primary ? impresoraModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? impresoraModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="impresora-config-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="impresora-config-mini-topline"></div>' +
            '<div class="impresora-config-mini-header">' +
                '<span class="impresora-config-mini-icon"><i class="' + impresoraModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="impresora-config-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + impresoraModernEscape(impresoraModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="impresora-config-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="impresora-config-mini-body">';

        impresoraModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="impresora-config-mini-field">' +
                '<span><i class="' + impresoraModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    impresoraModernEscape(field.label) + '</span>' +
                '<div>' + impresoraModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="impresora-config-mini-footer">' + impresoraModernAcciones(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function impresoraModernRenderPaginacion(totalPages) {
    var current = impresoraModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="impresora-config-page-btn' + (active ? ' active' : '') +
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

    $('#impresora-config-pagination').html(html);
}

function impresoraModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function impresoraModernRender() {
    var rows = impresoraModern.filtered || [];

    if (impresoraModern.loading) {
        $('#impresora-config-listado').html(
            '<div class="impresora-config-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#impresora-config-info').text('0 registros');
        $('#impresora-config-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#impresora-config-listado').html(
            '<div class="impresora-config-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#impresora-config-info').text('0 registros');
        $('#impresora-config-pagination').empty();
        impresoraModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / impresoraModern.pageSize));

    if (impresoraModern.page > totalPages) {
        impresoraModern.page = totalPages;
    }

    var offset = (impresoraModern.page - 1) * impresoraModern.pageSize;
    var pageRows = rows.slice(offset, offset + impresoraModern.pageSize);

    $('#impresora-config-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + impresoraModern.view)
        .html(
            impresoraModern.view === 'miniatura'
                ? impresoraModernRenderMiniatura(pageRows)
                : impresoraModernRenderDetalle(pageRows)
        );

    $('#impresora-config-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    impresoraModernRenderPaginacion(totalPages);
    impresoraModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function impresoraModernSyncPageSize() {
    var mini = impresoraModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? impresoraModern.pageSizeMiniatura : impresoraModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#impresora-config-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    impresoraModern.pageSize = selected;
    $select.val(String(selected));
}

function impresoraModernSyncView() {
    var mobile = impresoraModernEsMovil();

    if (mobile) {
        impresoraModern.view = 'miniatura';
    }

    $('.impresora-config-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.impresora-config-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.impresora-config-view-btn[data-view="' + impresoraModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function impresoraModernConfigurarPanel(button, content, storageKey) {
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

    $(button).off('click.impresoraModernPanel').on('click.impresoraModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function impresoraModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableConfImpresora")) {
        return null;
    }

    var api = $("#dataTableConfImpresora").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["impresora_id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function impresoraModernEjecutarAccion(recordKey, targetSelector) {
    var row = impresoraModernEncontrarFilaEnDataTable(recordKey);

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

function impresoraModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function impresoraModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function impresoraModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = impresoraModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function impresoraModernDescargarBlob(blob, nombreArchivo) {
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

function impresoraModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function impresoraModernDatosExcel() {
    return (impresoraModern.filtered || []).map(function(row) {
        return impresoraModernConfig.fields.map(function(field) {
            return impresoraModernFormatoCampo(row, field, true);
        });
    });
}

function impresoraModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = impresoraModernConfig.fields || [];
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
    var lastCol = impresoraModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            impresoraModernExcelCell(
                'A1',
                'IZZY • ' + String(impresoraModernConfig.exportTitle || impresoraModernConfig.title || 'REPORTE').toUpperCase(),
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            impresoraModernExcelCell(
                'A2',
                'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        impresoraModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        impresoraModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += impresoraModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += impresoraModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += impresoraModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += impresoraModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            impresoraModernExcelCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return impresoraModernExcelCell(
            impresoraModernExcelColName(index) + headerRow,
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

            return impresoraModernExcelCell(
                impresoraModernExcelColName(colIndex) + excelRow,
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

function impresoraModernExportarExcel() {
    var rows = impresoraModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = impresoraModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(impresoraModernConfig.exportTitle || impresoraModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            impresoraModernDescargarBlob(
                blob,
                nombre + '_' + impresoraModernFechaArchivo() + '.xlsx'
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

function impresoraModernPdfObtenerLogo(callback) {
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

function impresoraModernPdfLogoPlate(logoDataUrl) {
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

function impresoraModernPdfDetalle(rows) {
    var fields = impresoraModernConfig.fields;
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
            var value = String(impresoraModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = impresoraModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
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

function impresoraModernPdfTarjeta(row) {
    var fields = impresoraModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(impresoraModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(impresoraModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && impresoraModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(impresoraModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
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

function impresoraModernExportarPdf() {
    var rows = impresoraModern.filtered || [];

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
        impresoraModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            impresoraModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#impresora-config-search').val() || '').trim();
    var filtrosTexto = 'Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = impresoraModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: impresoraModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Configuración de impresoras del sistema", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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

    var kpis = impresoraModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(impresoraModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (impresoraModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [impresoraModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [impresoraModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [impresoraModernPdfDetalle(rows)];
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
                { text: 'IZZY • ' + impresoraModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: impresoraModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = impresoraModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, impresoraModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(impresoraModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            impresoraModern.preferredView = savedView;
            impresoraModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableConfImpresora")
        .off('xhr.dt.impresoraModern')
        .on('xhr.dt.impresoraModern', function(e, settings, json) {
            impresoraModern.rows = json && Array.isArray(json.data) ? json.data : [];
            impresoraModern.loading = false;
            impresoraModern.page = 1;
            impresoraModernFiltrar();
            impresoraModernRender();
        });

    impresoraModernSyncView();
    impresoraModernSyncPageSize();
    impresoraModernRender();

    if ($('#impresora-config-toggle-filtros').length) {
        impresoraModernConfigurarPanel(
            '#impresora-config-toggle-filtros',
            '#impresora-config-filtros-contenido',
            'izzy.confImpresora.panel.filtros'
        );
    }

    impresoraModernConfigurarPanel(
        '#impresora-config-toggle-kpis',
        '#impresora-config-kpis-contenido',
        'izzy.confImpresora.panel.kpis'
    );

    $('#impresora-config-btn-refresh').off('click.impresoraModern').on('click.impresoraModern', function() {
        if (typeof getImpresora === 'function') {
            impresoraModern.loading = true;
            impresoraModernRender();
            getImpresora();
        }
    });

    $('#impresora-config-btn-excel').off('click.impresoraModern').on('click.impresoraModern', impresoraModernExportarExcel);
    $('#impresora-config-btn-pdf').off('click.impresoraModern').on('click.impresoraModern', impresoraModernExportarPdf);

    $('#impresora-config-page-size').off('change.impresoraModern').on('change.impresoraModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (impresoraModern.view === 'miniatura') {
            impresoraModern.pageSizeMiniatura = value;
        } else {
            impresoraModern.pageSizeDetalle = value;
        }

        impresoraModern.pageSize = value;
        impresoraModern.page = 1;
        impresoraModernRender();
    });

    $('.impresora-config-view-btn').off('click.impresoraModern').on('click.impresoraModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (next === 'detalle' && impresoraModernEsMovil()) {
            return;
        }

        impresoraModern.view = next;
        impresoraModern.page = 1;

        if (!impresoraModernEsMovil()) {
            impresoraModern.preferredView = next;

            try {
                localStorage.setItem(impresoraModernConfig.storageView, impresoraModern.preferredView);
            } catch (e) {}
        }

        impresoraModernSyncView();
        impresoraModernSyncPageSize();
        impresoraModernRender();
    });

    $('#impresora-config-search').off('input.impresoraModern').on('input.impresoraModern', function() {
        impresoraModern.search = $(this).val() || '';
        impresoraModern.page = 1;
        impresoraModernFiltrar();
        impresoraModernRender();
    });

    $('#impresora-config-search-clear').off('click.impresoraModern').on('click.impresoraModern', function() {
        $('#impresora-config-search').val('').focus();
        impresoraModern.search = '';
        impresoraModern.page = 1;
        impresoraModernFiltrar();
        impresoraModernRender();
    });

    $('#impresora-config-pagination')
        .off('click.impresoraModern', '.impresora-config-page-btn')
        .on('click.impresoraModern', '.impresora-config-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                impresoraModern.page = page;
                impresoraModernRender();

                var target = document.querySelector('.impresora-config-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#impresora-config-listado')
        .off('click.impresoraModern', '.modern-action-bridge')
        .on('click.impresoraModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            impresoraModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#impresora-config-listado')
        .off('click.impresoraModernDropdown', '.js-acciones-toggle')
        .on('click.impresoraModernDropdown', '.js-acciones-toggle', function() {
            $('.impresora-config-detail-row, .impresora-config-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.impresora-config-detail-row, .impresora-config-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.impresoraModernDropdownClose')
        .on('click.impresoraModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.impresora-config-detail-row, .impresora-config-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.impresoraModern')
        .on('resize.impresoraModern', function() {
            var previous = impresoraModern.view;

            if (impresoraModernEsMovil()) {
                impresoraModern.view = 'miniatura';
            } else {
                impresoraModern.view = impresoraModern.preferredView;
            }

            if (previous !== impresoraModern.view) {
                impresoraModern.page = 1;
                impresoraModernSyncPageSize();
            }

            impresoraModernSyncView();
            impresoraModernRender();
        });
});


$(document).ready(function() {
	getImpresora();
});

/* =========================================================
   HEADER DINÁMICO - IMPRESORA
   ========================================================= */
   function construirHeaderDataTableConfImpresora() {
    var $tabla = $("#dataTableConfImpresora");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Descripción</th>' +
                '<th>Activo</th>' +
            '</tr>' +
        '</thead>'
    );
}

//CONFIGURACION DE IMPRESORA    
var getImpresora = function() {
    var impresora_id;
    var activo;
    var descripcion;

    if ($.fn.DataTable.isDataTable("#dataTableConfImpresora")) {
        $("#dataTableConfImpresora").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfImpresora();

    var table_impresora = $("#dataTableConfImpresora").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableImpresora.php",
            "data": {
                "impresora_id": impresora_id,
                "descripcion": descripcion,
                "activo": activo
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

                                '<button type="button" class="dropdown-item accion-item accion-editar table_impresora table_editar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                            '</div>' +
                        '</div>';
                }
            },
            {
                "data": "descripcion"
            },
            {
                "data": "activo"
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
                width: "68%",
                targets: 1,
                className: "text-center"
            },
            {
                width: "20%",
                targets: 2,
                className: "text-center"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    getImpresora();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Reporte',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2]
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

    table_impresora.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    updateStatus("#dataTableConfImpresora tbody", table_impresora);
}
//FIN 

//CAMBIAR EL ESTATUS DE CONFIGURACION
var updateStatus = function(tbody, table){
	$(tbody).off("click", "button.table_impresora");
	$(tbody).on("click", "button.table_impresora", function(){
		var data = table.row( $(this).parents("tr") ).data();	
		window.izzySwalLegacy({
			title: "¿Desea cambiar el estado?",
			icon: "info",
			buttons: {
				confirm: {
					text: "Activado!",
					value: true,
					visible: true
				},
				cancel: {
					text: "Desactivado!",
					value: false,
					visible: true
				}
			},
			closeOnEsc: false, // Desactiva el cierre con la tecla Esc
			closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera 
		}).then((isConfirm) => {
			if (isConfirm) {
				showNotify('success', 'Estado de Impresora', 'Activado');
				editarImpresora(data.impresora_id, 1);
			} else {
				showNotify('success', 'Estado de Impresora', 'Desactivado');
				editarImpresora(data.impresora_id, 0);
			}
		});
	})
};

function editarImpresora(id, estado) {
    var url = '<?php echo SERVERURL; ?>core/editarImpresora.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            id: id,
            estado: estado
        },
        success: function (response) {
            // Convertir la respuesta en un objeto JSON si no está ya parseada
            var data = typeof response === 'object' ? response : JSON.parse(response);

            if (data.success) {
                impresoraModern.rows.forEach(function (row) {
                    if (String(row.impresora_id) === String(id)) {
                        row.estado = String(estado);
                        row.activo = parseInt(estado, 10) === 1 ? 'Activado' : 'Desactivado';
                    }
                });
                impresoraModernFiltrar();
                impresoraModernRender();

                showNotify('success', 'Éxito', data.message); // Mensaje del backend
                getImpresora(); // Confirmar el estado real desde el backend
            } else {
                showNotify('error', 'Error', data.message); // Mensaje del backend
            }
        },
        error: function () {
            showNotify('error', 'Error', 'Hubo un problema con la conexión al servidor. Por favor, inténtelo de nuevo.');
        }
    });
}
</script>
