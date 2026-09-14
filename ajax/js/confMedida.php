<script>

/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Medidas
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

var medidaModernConfig = {"table":"#dataTableConfMedidas","key":"medida_id","title":"Medidas","exportTitle":"Reporte Medidas","fields":[{"key":"nombre","label":"Medida","icon":"fas fa-ruler","type":"text"},{"key":"descripcion","label":"Descripción","icon":"fas fa-align-left","type":"text"},{"key":"estado","label":"Estado","icon":"fas fa-toggle-on","type":"status"}],"actions":[{"label":"Editar","icon":"fas fa-edit","target":"button.table_editar","classes":"accion-editar table_editar ocultar"},{"label":"Eliminar","icon":"fas fa-trash-alt","target":"button.table_eliminar","classes":"accion-eliminar table_eliminar ocultar"}],"kpis":[{"id":"total","label":"Medidas","desc":"Total de medidas encontradas","icon":"fas fa-ruler-combined","color":"blue","calc":{"type":"count"}},{"id":"activos","label":"Activas","desc":"Medidas activas","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"estado","value":"1"}},{"id":"inactivos","label":"Inactivas","desc":"Medidas inactivas","icon":"fas fa-times-circle","color":"orange","calc":{"type":"eq","key":"estado","value":"0"}}],"storageView":"izzy.confMedida.tipo_vista"};

var medidaModern = {
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

function medidaModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function medidaModernEscape(value) {
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

function medidaModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function medidaModernEstado(value) {
    return parseInt(value, 10) === 1;
}

function medidaModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = medidaModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return medidaModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return medidaModernEstado(value)
            ? '<span class="medidas-config-status-badge medidas-config-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="medidas-config-status-badge medidas-config-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : medidaModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + medidaModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return medidaModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="medidas-config-method-badge medidas-config-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="medidas-config-method-badge medidas-config-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="medidas-config-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = medidaModernTexto(row.graph_user, 'No configurado');
        var tenant = medidaModernTexto(row.tenant_id, 'No configurado');
        var client = medidaModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="medidas-config-tech-lines">' +
            '<span><strong>Graph:</strong> ' + medidaModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + medidaModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + medidaModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : medidaModernEscape(text);
}

function medidaModernClaseIconoAccion(action) {
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

function medidaModernAcciones(row) {
    var key = medidaModernEscape(medidaModernTexto(row[medidaModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown medidas-config-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    medidaModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            medidaModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + medidaModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + medidaModernClaseIconoAccion(action) + '"><i class="' + medidaModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + medidaModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}

function medidaModernFiltrar() {
    var q = $.trim(medidaModern.search || '').toLowerCase();

    medidaModern.filtered = !q
        ? medidaModern.rows.slice()
        : medidaModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    medidaModernActualizarKpis();
}

function medidaModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return medidaModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return medidaModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return medidaModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        medidaModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return medidaModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return medidaModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function medidaModernActualizarKpis() {
    medidaModernConfig.kpis.forEach(function(kpi) {
        $('#medidas-config-kpi-' + kpi.id).text(medidaModernCalcularKpi(kpi.calc));
    });
}

function medidaModernRenderDetalle(rows) {
    var html = '<div class="medidas-config-detail-header"><div>Acciones</div>';

    medidaModernConfig.fields.forEach(function(field) {
        html += '<div>' + medidaModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = medidaModernEscape(medidaModernTexto(row[medidaModernConfig.key], ''));

        html += '<article class="medidas-config-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="medidas-config-cell medidas-config-actions-cell">' +
                '<span class="medidas-config-cell-label">Acciones</span>' +
                medidaModernAcciones(row) +
            '</div>';

        medidaModernConfig.fields.forEach(function(field) {
            var contenidoCampo = field.type === 'status'
                ? '<div class="medidas-config-status-only">' + medidaModernFormatoCampo(row, field, false) + '</div>'
                : '<div class="medidas-config-field-main">' +
                    '<i class="' + medidaModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="medidas-config-field-value">' + medidaModernFormatoCampo(row, field, false) + '</div>' +
                  '</div>';

            html += '<div class="medidas-config-cell' + (field.type === 'status' ? ' medidas-config-status-cell' : '') + '">' +
                '<span class="medidas-config-cell-label">' + medidaModernEscape(field.label) + '</span>' +
                '<div class="medidas-config-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>';
    });

    return html;
}

function medidaModernRenderMiniatura(rows) {
    var html = '<div class="medidas-config-mini-grid">';
    var primary = medidaModernConfig.fields[0];
    var statusField = null;

    medidaModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = medidaModernEscape(medidaModernTexto(row[medidaModernConfig.key], ''));
        var primaryValue = primary ? medidaModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? medidaModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="medidas-config-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="medidas-config-mini-topline"></div>' +
            '<div class="medidas-config-mini-header">' +
                '<span class="medidas-config-mini-icon"><i class="' + medidaModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="medidas-config-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + medidaModernEscape(medidaModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="medidas-config-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="medidas-config-mini-body">';

        medidaModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="medidas-config-mini-field">' +
                '<span><i class="' + medidaModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    medidaModernEscape(field.label) + '</span>' +
                '<div>' + medidaModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="medidas-config-mini-footer">' + medidaModernAcciones(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function medidaModernRenderPaginacion(totalPages) {
    var current = medidaModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="medidas-config-page-btn' + (active ? ' active' : '') +
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

    $('#medidas-config-pagination').html(html);
}

function medidaModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function medidaModernRender() {
    var rows = medidaModern.filtered || [];

    if (medidaModern.loading) {
        $('#medidas-config-listado').html(
            '<div class="medidas-config-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#medidas-config-info').text('0 registros');
        $('#medidas-config-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#medidas-config-listado').html(
            '<div class="medidas-config-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#medidas-config-info').text('0 registros');
        $('#medidas-config-pagination').empty();
        medidaModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / medidaModern.pageSize));

    if (medidaModern.page > totalPages) {
        medidaModern.page = totalPages;
    }

    var offset = (medidaModern.page - 1) * medidaModern.pageSize;
    var pageRows = rows.slice(offset, offset + medidaModern.pageSize);

    $('#medidas-config-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + medidaModern.view)
        .html(
            medidaModern.view === 'miniatura'
                ? medidaModernRenderMiniatura(pageRows)
                : medidaModernRenderDetalle(pageRows)
        );

    $('#medidas-config-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    medidaModernRenderPaginacion(totalPages);
    medidaModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function medidaModernSyncPageSize() {
    var mini = medidaModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? medidaModern.pageSizeMiniatura : medidaModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#medidas-config-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    medidaModern.pageSize = selected;
    $select.val(String(selected));
}

function medidaModernSyncView() {
    var mobile = medidaModernEsMovil();

    if (mobile) {
        medidaModern.view = 'miniatura';
    }

    $('.medidas-config-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.medidas-config-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.medidas-config-view-btn[data-view="' + medidaModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function medidaModernConfigurarPanel(button, content, storageKey) {
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

    $(button).off('click.medidaModernPanel').on('click.medidaModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function medidaModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableConfMedidas")) {
        return null;
    }

    var api = $("#dataTableConfMedidas").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["medida_id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function medidaModernEjecutarAccion(recordKey, targetSelector) {
    var row = medidaModernEncontrarFilaEnDataTable(recordKey);

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

function medidaModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function medidaModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function medidaModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = medidaModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function medidaModernDescargarBlob(blob, nombreArchivo) {
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

function medidaModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function medidaModernDatosExcel() {
    return (medidaModern.filtered || []).map(function(row) {
        return medidaModernConfig.fields.map(function(field) {
            return medidaModernFormatoCampo(row, field, true);
        });
    });
}

function medidaModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = medidaModernConfig.fields || [];
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
    var lastCol = medidaModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            medidaModernExcelCell(
                'A1',
                'IZZY • ' + String(medidaModernConfig.exportTitle || medidaModernConfig.title || 'REPORTE').toUpperCase(),
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            medidaModernExcelCell(
                'A2',
                'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        medidaModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        medidaModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += medidaModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += medidaModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += medidaModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += medidaModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            medidaModernExcelCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return medidaModernExcelCell(
            medidaModernExcelColName(index) + headerRow,
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

            return medidaModernExcelCell(
                medidaModernExcelColName(colIndex) + excelRow,
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

function medidaModernExportarExcel() {
    var rows = medidaModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = medidaModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(medidaModernConfig.exportTitle || medidaModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            medidaModernDescargarBlob(
                blob,
                nombre + '_' + medidaModernFechaArchivo() + '.xlsx'
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

function medidaModernPdfObtenerLogo(callback) {
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

function medidaModernPdfLogoPlate(logoDataUrl) {
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

function medidaModernPdfDetalle(rows) {
    var fields = medidaModernConfig.fields;
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
            var value = String(medidaModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = medidaModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
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

function medidaModernPdfTarjeta(row) {
    var fields = medidaModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(medidaModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(medidaModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && medidaModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(medidaModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
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

function medidaModernExportarPdf() {
    var rows = medidaModern.filtered || [];

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
        medidaModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            medidaModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#medidas-config-search').val() || '').trim();
    var filtroEstado = $("#estado_medidas").length
        ? ($("#estado_medidas" + ' option:selected').text() || 'Todos')
        : 'Todos';
    var filtrosTexto = 'Estado: ' + filtroEstado + '   |   Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = medidaModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: medidaModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Catálogo de unidades y medidas", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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

    var kpis = medidaModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(medidaModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (medidaModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [medidaModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [medidaModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [medidaModernPdfDetalle(rows)];
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
                { text: 'IZZY • ' + medidaModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: medidaModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = medidaModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, medidaModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(medidaModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            medidaModern.preferredView = savedView;
            medidaModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableConfMedidas")
        .off('xhr.dt.medidaModern')
        .on('xhr.dt.medidaModern', function(e, settings, json) {
            medidaModern.rows = json && Array.isArray(json.data) ? json.data : [];
            medidaModern.loading = false;
            medidaModern.page = 1;
            medidaModernFiltrar();
            medidaModernRender();
        });

    medidaModernSyncView();
    medidaModernSyncPageSize();
    medidaModernRender();

    if ($('#medidas-config-toggle-filtros').length) {
        medidaModernConfigurarPanel(
            '#medidas-config-toggle-filtros',
            '#medidas-config-filtros-contenido',
            'izzy.confMedida.panel.filtros'
        );
    }

    medidaModernConfigurarPanel(
        '#medidas-config-toggle-kpis',
        '#medidas-config-kpis-contenido',
        'izzy.confMedida.panel.kpis'
    );

    $('#medidas-config-btn-refresh').off('click.medidaModern').on('click.medidaModern', function() {
        if (typeof listar_medidas === 'function') {
            medidaModern.loading = true;
            medidaModernRender();
            listar_medidas();
        }
    });

    $('#medidas-config-btn-create').off('click.medidaModern').on('click.medidaModern', function() {
        if (typeof modalMedidas === 'function') {
            modalMedidas();
        }
    });

    $('#medidas-config-btn-excel').off('click.medidaModern').on('click.medidaModern', medidaModernExportarExcel);
    $('#medidas-config-btn-pdf').off('click.medidaModern').on('click.medidaModern', medidaModernExportarPdf);

    $('#medidas-config-page-size').off('change.medidaModern').on('change.medidaModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (medidaModern.view === 'miniatura') {
            medidaModern.pageSizeMiniatura = value;
        } else {
            medidaModern.pageSizeDetalle = value;
        }

        medidaModern.pageSize = value;
        medidaModern.page = 1;
        medidaModernRender();
    });

    $('.medidas-config-view-btn').off('click.medidaModern').on('click.medidaModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (next === 'detalle' && medidaModernEsMovil()) {
            return;
        }

        medidaModern.view = next;
        medidaModern.page = 1;

        if (!medidaModernEsMovil()) {
            medidaModern.preferredView = next;

            try {
                localStorage.setItem(medidaModernConfig.storageView, medidaModern.preferredView);
            } catch (e) {}
        }

        medidaModernSyncView();
        medidaModernSyncPageSize();
        medidaModernRender();
    });

    $('#medidas-config-search').off('input.medidaModern').on('input.medidaModern', function() {
        medidaModern.search = $(this).val() || '';
        medidaModern.page = 1;
        medidaModernFiltrar();
        medidaModernRender();
    });

    $('#medidas-config-search-clear').off('click.medidaModern').on('click.medidaModern', function() {
        $('#medidas-config-search').val('').focus();
        medidaModern.search = '';
        medidaModern.page = 1;
        medidaModernFiltrar();
        medidaModernRender();
    });

    $('#medidas-config-pagination')
        .off('click.medidaModern', '.medidas-config-page-btn')
        .on('click.medidaModern', '.medidas-config-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                medidaModern.page = page;
                medidaModernRender();

                var target = document.querySelector('.medidas-config-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#medidas-config-listado')
        .off('click.medidaModern', '.modern-action-bridge')
        .on('click.medidaModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            medidaModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#medidas-config-listado')
        .off('click.medidaModernDropdown', '.js-acciones-toggle')
        .on('click.medidaModernDropdown', '.js-acciones-toggle', function() {
            $('.medidas-config-detail-row, .medidas-config-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.medidas-config-detail-row, .medidas-config-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.medidaModernDropdownClose')
        .on('click.medidaModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.medidas-config-detail-row, .medidas-config-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.medidaModern')
        .on('resize.medidaModern', function() {
            var previous = medidaModern.view;

            if (medidaModernEsMovil()) {
                medidaModern.view = 'miniatura';
            } else {
                medidaModern.view = medidaModern.preferredView;
            }

            if (previous !== medidaModern.view) {
                medidaModern.page = 1;
                medidaModernSyncPageSize();
            }

            medidaModernSyncView();
            medidaModernRender();
        });
});


$(document).ready(function() {
    listar_medidas(); 

	$('#form_main_medidas #search').on("click", function (e) {
		e.preventDefault();
		listar_medidas();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_medidas').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('select') // Usa `this` para referenciar el formulario actual
			.val('')
			.trigger('change');

			listar_medidas();
	});	
});

/* =========================================================
   HEADER DINÁMICO - MEDIDAS
   ========================================================= */
   function construirHeaderDataTableConfMedidas() {
    var $tabla = $("#dataTableConfMedidas");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Medida</th>' +
                '<th>Descripción</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO MEDIDAS
var listar_medidas = function() {
    var estado = $('#form_main_medidas #estado_medidas').val();

    if ($.fn.DataTable.isDataTable("#dataTableConfMedidas")) {
        $("#dataTableConfMedidas").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfMedidas();

    var table_medidas = $("#dataTableConfMedidas").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableMedida.php",
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
            {"data": "nombre"},
            {"data": "descripcion"},
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
                width: "10%",
                targets: 0,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                width: "20%",
                targets: 1
            },
            {
                width: "60%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3,
                className: "text-center text-nowrap align-middle"
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Medidas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_medidas();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Medidas',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalMedidas();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Medidas',
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
                title: 'Reporte Medidas',
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

    table_medidas.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    edit_medidas_dataTable("#dataTableConfMedidas tbody", table_medidas);
    delete_medidas_dataTable("#dataTableConfMedidas tbody", table_medidas);
}

var edit_medidas_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar");
	$(tbody).on("click", "button.table_editar", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarMedidas.php';
		$('#formMedidas #medida_id').val(data.medida_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formMedidas').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formMedidas').attr({ 'data-form': 'update' });
				$('#formMedidas').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarMedidasAjax.php' });
				$('#formMedidas')[0].reset();
				$('#reg_medidas').hide();
				$('#edi_medidas').show();
				$('#delete_medidas').hide();
				$('#formMedidas #pro_medidas').val("Editar");
				$('#formMedidas #medidas_medidas').val(valores[0]);
				$('#formMedidas #descripcion_medidas').val(valores[1]);

				if(valores[2] == 1){
					$('#formMedidas #medidas_activo').attr('checked', true);
				}else{
					$('#formMedidas #medidas_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formMedidas #medidas_medidas').attr('readonly', false);
				$('#formMedidas #descripcion_medidas').attr('readonly', false);
				$('#formMedidas #medidas_activo').attr('disabled', false);
				$('#formMedidas #estado_medidas').show();

				//DESHABIITAR OBJETOS
				$('#formMedidas #medidas_medidas').attr('readonly', true);

				$('#modal_medidas').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var delete_medidas_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar");
	$(tbody).on("click", "button.table_eliminar", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var medida_id = data.medida_id;
        var nombreMedida = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente la medida?<br><br>
                        <strong>Nombre:</strong> ${nombreMedida}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarMedidasAjax.php',
                    data: {
                        medida_id: medida_id
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
//FIN MEDIDAS

//INICIO FORMULARIO MEDIDAS
function modalMedidas(){
	$('#formMedidas').attr({ 'data-form': 'save' });
	$('#formMedidas').attr({ 'action': '<?php echo SERVERURL; ?>ajax/agregarMedidasAjax.php' });
	$('#formMedidas')[0].reset();
	$('#formMedidas #pro_medidas').val("Registro");
	$('#reg_medidas').show();
	$('#edi_medidas').hide();
	$('#delete_medidas').hide();

	//HABILITAR OBJETOS
	$('#formMedidas #medidas_medidas').attr('readonly', false);
	$('#formMedidas #descripcion_medidas').attr('readonly', false);
	$('#formMedidas #medidas_activo').attr('disabled', false);
	$('#formMedidas #estado_medidas').hide();

	$('#modal_medidas').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
//FIN FORMULARIO MEDIDAS

$(document).ready(function(){
    $("#modal_medidas").on('shown.bs.modal', function(){
        $(this).find('#formMedidas #medidas_medidas').focus();
    });
});

$('#formMedidas #label_medidas_activo').html("Activo");
	
$('#formMedidas .switch').change(function(){    
    if($('input[name=medidas_activo]').is(':checked')){
        $('#formMedidas #label_medidas_activo').html("Activo");
        return true;
    }
    else{
        $('#formMedidas #label_medidas_activo').html("Inactivo");
        return false;
    }
});	
</script>