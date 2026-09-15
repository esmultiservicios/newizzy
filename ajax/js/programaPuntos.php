<script>

/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Programa de Puntos
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

var programaPuntosModernConfig = {"table":"#dataTableProgramaPuntos","key":"id","title":"Programa de Puntos","exportTitle":"Reporte Programa de Puntos","fields":[{"key":"nombre","label":"Programa","icon":"fas fa-gift","type":"text"},{"key":"tipo_calculo","label":"Tipo cálculo","icon":"fas fa-calculator","type":"programType"},{"key":"monto","label":"Monto","icon":"fas fa-money-bill-wave","type":"money"},{"key":"porcentaje","label":"Porcentaje","icon":"fas fa-percentage","type":"percent"},{"key":"activo","label":"Estado","icon":"fas fa-toggle-on","type":"status"},{"key":"fecha_creacion","label":"Fecha creación","icon":"fas fa-calendar-alt","type":"text"}],"actions":[{"label":"Histórico","icon":"fas fa-history","target":".ver-historico","classes":"accion-historico"},{"label":"Editar","icon":"fas fa-edit","target":"button.table_editar","classes":"accion-editar table_editar ocultar"},{"label":"Eliminar","icon":"fas fa-trash-alt","target":"button.table_eliminar","classes":"accion-eliminar table_eliminar ocultar"}],"kpis":[{"id":"total","label":"Programas","desc":"Total de programas encontrados","icon":"fas fa-list-ol","color":"blue","calc":{"type":"count"}},{"id":"activos","label":"Activos","desc":"Programas actualmente activos","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"activo","value":"1"}},{"id":"monto","label":"Por monto","desc":"Cálculo configurado por monto","icon":"fas fa-coins","color":"teal","calc":{"type":"eq","key":"tipo_calculo","value":"monto"}},{"id":"porcentaje","label":"Por porcentaje","desc":"Cálculo configurado por porcentaje","icon":"fas fa-percentage","color":"purple","calc":{"type":"eq","key":"tipo_calculo","value":"porcentaje"}}],"storageView":"izzy.programaPuntos.tipo_vista"};

var programaPuntosModern = {
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

function programaPuntosModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function programaPuntosModernEscape(value) {
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

function programaPuntosModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function programaPuntosModernEstado(value) {
    return parseInt(value, 10) === 1;
}

function programaPuntosModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = programaPuntosModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return programaPuntosModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return programaPuntosModernEstado(value)
            ? '<span class="programa-puntos-status-badge programa-puntos-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="programa-puntos-status-badge programa-puntos-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : programaPuntosModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + programaPuntosModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return programaPuntosModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="programa-puntos-method-badge programa-puntos-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="programa-puntos-method-badge programa-puntos-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="programa-puntos-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = programaPuntosModernTexto(row.graph_user, 'No configurado');
        var tenant = programaPuntosModernTexto(row.tenant_id, 'No configurado');
        var client = programaPuntosModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="programa-puntos-tech-lines">' +
            '<span><strong>Graph:</strong> ' + programaPuntosModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + programaPuntosModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + programaPuntosModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : programaPuntosModernEscape(text);
}

function programaPuntosModernClaseIconoAccion(action) {
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

function programaPuntosModernAcciones(row) {
    var key = programaPuntosModernEscape(programaPuntosModernTexto(row[programaPuntosModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown programa-puntos-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    programaPuntosModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            programaPuntosModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + programaPuntosModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + programaPuntosModernClaseIconoAccion(action) + '"><i class="' + programaPuntosModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + programaPuntosModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}

function programaPuntosModernFiltrar() {
    var q = $.trim(programaPuntosModern.search || '').toLowerCase();

    programaPuntosModern.filtered = !q
        ? programaPuntosModern.rows.slice()
        : programaPuntosModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    programaPuntosModernActualizarKpis();
}

function programaPuntosModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return programaPuntosModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return programaPuntosModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return programaPuntosModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        programaPuntosModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return programaPuntosModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return programaPuntosModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function programaPuntosModernActualizarKpis() {
    programaPuntosModernConfig.kpis.forEach(function(kpi) {
        $('#programa-puntos-kpi-' + kpi.id).text(programaPuntosModernCalcularKpi(kpi.calc));
    });
}

function programaPuntosModernRenderDetalle(rows) {
    var html = '<div class="programa-puntos-detail-header"><div>Acciones</div>';

    programaPuntosModernConfig.fields.forEach(function(field) {
        html += '<div>' + programaPuntosModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = programaPuntosModernEscape(programaPuntosModernTexto(row[programaPuntosModernConfig.key], ''));

        html += '<article class="programa-puntos-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="programa-puntos-cell programa-puntos-actions-cell">' +
                '<span class="programa-puntos-cell-label">Acciones</span>' +
                programaPuntosModernAcciones(row) +
            '</div>';

        programaPuntosModernConfig.fields.forEach(function(field) {
            var contenidoCampo = field.type === 'status'
                ? '<div class="programa-puntos-status-only">' + programaPuntosModernFormatoCampo(row, field, false) + '</div>'
                : '<div class="programa-puntos-field-main">' +
                    '<i class="' + programaPuntosModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="programa-puntos-field-value">' + programaPuntosModernFormatoCampo(row, field, false) + '</div>' +
                  '</div>';

            html += '<div class="programa-puntos-cell' + (field.type === 'status' ? ' programa-puntos-status-cell' : '') + '">' +
                '<span class="programa-puntos-cell-label">' + programaPuntosModernEscape(field.label) + '</span>' +
                '<div class="programa-puntos-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>';
    });

    return html;
}

function programaPuntosModernRenderMiniatura(rows) {
    var html = '<div class="programa-puntos-mini-grid">';
    var primary = programaPuntosModernConfig.fields[0];
    var statusField = null;

    programaPuntosModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = programaPuntosModernEscape(programaPuntosModernTexto(row[programaPuntosModernConfig.key], ''));
        var primaryValue = primary ? programaPuntosModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? programaPuntosModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="programa-puntos-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="programa-puntos-mini-topline"></div>' +
            '<div class="programa-puntos-mini-header">' +
                '<span class="programa-puntos-mini-icon"><i class="' + programaPuntosModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="programa-puntos-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + programaPuntosModernEscape(programaPuntosModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="programa-puntos-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="programa-puntos-mini-body">';

        programaPuntosModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="programa-puntos-mini-field">' +
                '<span><i class="' + programaPuntosModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    programaPuntosModernEscape(field.label) + '</span>' +
                '<div>' + programaPuntosModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="programa-puntos-mini-footer">' + programaPuntosModernAcciones(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function programaPuntosModernRenderPaginacion(totalPages) {
    var current = programaPuntosModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="programa-puntos-page-btn' + (active ? ' active' : '') +
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

    $('#programa-puntos-pagination').html(html);
}

function programaPuntosModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function programaPuntosModernRender() {
    var rows = programaPuntosModern.filtered || [];

    if (programaPuntosModern.loading) {
        $('#programa-puntos-listado').html(
            '<div class="programa-puntos-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#programa-puntos-info').text('0 registros');
        $('#programa-puntos-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#programa-puntos-listado').html(
            '<div class="programa-puntos-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#programa-puntos-info').text('0 registros');
        $('#programa-puntos-pagination').empty();
        programaPuntosModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / programaPuntosModern.pageSize));

    if (programaPuntosModern.page > totalPages) {
        programaPuntosModern.page = totalPages;
    }

    var offset = (programaPuntosModern.page - 1) * programaPuntosModern.pageSize;
    var pageRows = rows.slice(offset, offset + programaPuntosModern.pageSize);

    $('#programa-puntos-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + programaPuntosModern.view)
        .html(
            programaPuntosModern.view === 'miniatura'
                ? programaPuntosModernRenderMiniatura(pageRows)
                : programaPuntosModernRenderDetalle(pageRows)
        );

    $('#programa-puntos-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    programaPuntosModernRenderPaginacion(totalPages);
    programaPuntosModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function programaPuntosModernSyncPageSize() {
    var mini = programaPuntosModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? programaPuntosModern.pageSizeMiniatura : programaPuntosModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#programa-puntos-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    programaPuntosModern.pageSize = selected;
    $select.val(String(selected));
}

function programaPuntosModernSyncView() {
    var mobile = programaPuntosModernEsMovil();

    if (mobile) {
        programaPuntosModern.view = 'miniatura';
    }

    $('.programa-puntos-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.programa-puntos-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.programa-puntos-view-btn[data-view="' + programaPuntosModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function programaPuntosModernConfigurarPanel(button, content, storageKey) {
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

    $(button).off('click.programaPuntosModernPanel').on('click.programaPuntosModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function programaPuntosModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableProgramaPuntos")) {
        return null;
    }

    var api = $("#dataTableProgramaPuntos").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function programaPuntosModernEjecutarAccion(recordKey, targetSelector) {
    var row = programaPuntosModernEncontrarFilaEnDataTable(recordKey);

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

function programaPuntosModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function programaPuntosModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function programaPuntosModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = programaPuntosModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function programaPuntosModernDescargarBlob(blob, nombreArchivo) {
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

function programaPuntosModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function programaPuntosModernDatosExcel() {
    return (programaPuntosModern.filtered || []).map(function(row) {
        return programaPuntosModernConfig.fields.map(function(field) {
            return programaPuntosModernFormatoCampo(row, field, true);
        });
    });
}

function programaPuntosModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = programaPuntosModernConfig.fields || [];
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
    var lastCol = programaPuntosModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    var tituloFilaExcel =
        programaPuntosModernExcelCell(
            'A1',
            'IZZY • ' + String(programaPuntosModernConfig.exportTitle || programaPuntosModernConfig.title || 'REPORTE').toUpperCase(),
            1,
            false
        );

    for (var tituloCol = 1; tituloCol < headers.length; tituloCol++) {
        tituloFilaExcel += programaPuntosModernExcelCell(
            programaPuntosModernExcelColName(tituloCol) + '1',
            '',
            1,
            false
        );
    }

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            tituloFilaExcel +
        '</row>'
    );

    var subtituloFilaExcel =
        programaPuntosModernExcelCell(
            'A2',
            'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
            2,
            false
        );

    for (var subtituloCol = 1; subtituloCol < headers.length; subtituloCol++) {
        subtituloFilaExcel += programaPuntosModernExcelCell(
            programaPuntosModernExcelColName(subtituloCol) + '2',
            '',
            2,
            false
        );
    }

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            subtituloFilaExcel +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        programaPuntosModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        programaPuntosModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += programaPuntosModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += programaPuntosModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += programaPuntosModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += programaPuntosModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    var detalleTituloFila =
        programaPuntosModernExcelCell(
            'A6',
            'Detalle de registros filtrados',
            8,
            false
        );

    for (var detalleCol = 1; detalleCol < headers.length; detalleCol++) {
        detalleTituloFila += programaPuntosModernExcelCell(
            programaPuntosModernExcelColName(detalleCol) + '6',
            '',
            8,
            false
        );
    }

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            detalleTituloFila +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return programaPuntosModernExcelCell(
            programaPuntosModernExcelColName(index) + headerRow,
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

            return programaPuntosModernExcelCell(
                programaPuntosModernExcelColName(colIndex) + excelRow,
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
            '<mergeCells count="3">' +
                '<mergeCell ref="A1:' + lastCol + '1"/>' +
                '<mergeCell ref="A2:' + lastCol + '2"/>' +
                '<mergeCell ref="A6:' + lastCol + '6"/>' +
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
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
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

function programaPuntosModernExportarExcel() {
    var rows = programaPuntosModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = programaPuntosModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(programaPuntosModernConfig.exportTitle || programaPuntosModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            programaPuntosModernDescargarBlob(
                blob,
                nombre + '_' + programaPuntosModernFechaArchivo() + '.xlsx'
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

function programaPuntosModernPdfObtenerLogo(callback) {
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

function programaPuntosModernPdfLogoPlate(logoDataUrl) {
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

function programaPuntosModernPdfDetalle(rows) {
    var fields = programaPuntosModernConfig.fields;
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
            var value = String(programaPuntosModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = programaPuntosModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
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

function programaPuntosModernPdfTarjeta(row) {
    var fields = programaPuntosModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(programaPuntosModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(programaPuntosModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && programaPuntosModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(programaPuntosModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
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

function programaPuntosModernExportarPdf() {
    var rows = programaPuntosModern.filtered || [];

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
        programaPuntosModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            programaPuntosModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#programa-puntos-search').val() || '').trim();
    var filtroEstado = $("#estado_programa_puntos").length
        ? ($("#estado_programa_puntos" + ' option:selected').text() || 'Todos')
        : 'Todos';
    var filtrosTexto = 'Estado: ' + filtroEstado + '   |   Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = programaPuntosModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: programaPuntosModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Configuración y reglas del programa de puntos", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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

    var kpis = programaPuntosModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(programaPuntosModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (programaPuntosModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [programaPuntosModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [programaPuntosModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [programaPuntosModernPdfDetalle(rows)];
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
                { text: 'IZZY • ' + programaPuntosModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: programaPuntosModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = programaPuntosModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, programaPuntosModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(programaPuntosModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            programaPuntosModern.preferredView = savedView;
            programaPuntosModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableProgramaPuntos")
        .off('xhr.dt.programaPuntosModern')
        .on('xhr.dt.programaPuntosModern', function(e, settings, json) {
            programaPuntosModern.rows = json && Array.isArray(json.data) ? json.data : [];
            programaPuntosModern.loading = false;
            programaPuntosModern.page = 1;
            programaPuntosModernFiltrar();
            programaPuntosModernRender();
        });

    programaPuntosModernSyncView();
    programaPuntosModernSyncPageSize();
    programaPuntosModernRender();

    if ($('#programa-puntos-toggle-filtros').length) {
        programaPuntosModernConfigurarPanel(
            '#programa-puntos-toggle-filtros',
            '#programa-puntos-filtros-contenido',
            'izzy.programaPuntos.panel.filtros'
        );
    }

    programaPuntosModernConfigurarPanel(
        '#programa-puntos-toggle-kpis',
        '#programa-puntos-kpis-contenido',
        'izzy.programaPuntos.panel.kpis'
    );

    $('#programa-puntos-btn-refresh').off('click.programaPuntosModern').on('click.programaPuntosModern', function() {
        if (typeof listar_programa_puntos === 'function') {
            programaPuntosModern.loading = true;
            programaPuntosModernRender();
            listar_programa_puntos();
        }
    });

    $('#programa-puntos-btn-create').off('click.programaPuntosModern').on('click.programaPuntosModern', function() {
        if (typeof modal_programa_puntos === 'function') {
            modal_programa_puntos();
        }
    });

    $('#programa-puntos-btn-excel').off('click.programaPuntosModern').on('click.programaPuntosModern', programaPuntosModernExportarExcel);
    $('#programa-puntos-btn-pdf').off('click.programaPuntosModern').on('click.programaPuntosModern', programaPuntosModernExportarPdf);

    $('#programa-puntos-page-size').off('change.programaPuntosModern').on('change.programaPuntosModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (programaPuntosModern.view === 'miniatura') {
            programaPuntosModern.pageSizeMiniatura = value;
        } else {
            programaPuntosModern.pageSizeDetalle = value;
        }

        programaPuntosModern.pageSize = value;
        programaPuntosModern.page = 1;
        programaPuntosModernRender();
    });

    $('.programa-puntos-view-btn').off('click.programaPuntosModern').on('click.programaPuntosModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (next === 'detalle' && programaPuntosModernEsMovil()) {
            return;
        }

        programaPuntosModern.view = programaPuntosModernEsMovil()
            ? 'miniatura'
            : next;

        if (!programaPuntosModernEsMovil()) {
            programaPuntosModern.preferredView = programaPuntosModern.view;

            try {
                localStorage.setItem(
                    programaPuntosModernConfig.storageView,
                    programaPuntosModern.preferredView
                );
            } catch (e) {}
        }

        programaPuntosModern.page = 1;
        programaPuntosModernSyncView();
        programaPuntosModernSyncPageSize();
        programaPuntosModernRender();
    });

    $('#programa-puntos-search').off('input.programaPuntosModern').on('input.programaPuntosModern', function() {
        programaPuntosModern.search = $(this).val() || '';
        programaPuntosModern.page = 1;
        programaPuntosModernFiltrar();
        programaPuntosModernRender();
    });

    $('#programa-puntos-search-clear').off('click.programaPuntosModern').on('click.programaPuntosModern', function() {
        $('#programa-puntos-search').val('').focus();
        programaPuntosModern.search = '';
        programaPuntosModern.page = 1;
        programaPuntosModernFiltrar();
        programaPuntosModernRender();
    });

    $('#programa-puntos-pagination')
        .off('click.programaPuntosModern', '.programa-puntos-page-btn')
        .on('click.programaPuntosModern', '.programa-puntos-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                programaPuntosModern.page = page;
                programaPuntosModernRender();

                var target = document.querySelector('.programa-puntos-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#programa-puntos-listado')
        .off('click.programaPuntosModern', '.modern-action-bridge')
        .on('click.programaPuntosModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            programaPuntosModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#programa-puntos-listado')
        .off('click.programaPuntosModernDropdown', '.js-acciones-toggle')
        .on('click.programaPuntosModernDropdown', '.js-acciones-toggle', function() {
            $('.programa-puntos-detail-row, .programa-puntos-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.programa-puntos-detail-row, .programa-puntos-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.programaPuntosModernDropdownClose')
        .on('click.programaPuntosModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.programa-puntos-detail-row, .programa-puntos-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.programaPuntosModern orientationchange.programaPuntosModern')
        .on('resize.programaPuntosModern orientationchange.programaPuntosModern', function() {
            var previous = programaPuntosModern.view;

            if (programaPuntosModernEsMovil()) {
                programaPuntosModern.view = 'miniatura';
            } else {
                programaPuntosModern.view = programaPuntosModern.preferredView;
            }

            if (previous !== programaPuntosModern.view) {
                programaPuntosModern.page = 1;
                programaPuntosModernSyncPageSize();
            }

            programaPuntosModernSyncView();
            programaPuntosModernRender();
        });
});


// Declarar la variable global para DataTable
var table_programa_puntos;

$(function() {
    listar_programa_puntos();
    initCalculoEventos();

    $("#modalProgramaPuntos").on('shown.bs.modal', function() {
        $(this).find('#formProgramaPuntos #nombre').focus();
    });
});

$('#form_main_programa_puntos').on('submit', function(e) {
    e.preventDefault();
    listar_programa_puntos();
});

$('#form_main_programa_puntos #estado_programa_puntos').on('change', function() {
    listar_programa_puntos();
});

/* =========================================================
   HEADER DINÁMICO - PROGRAMA DE PUNTOS
   ========================================================= */
   function construirHeaderDataTableProgramaPuntos() {
    var $tabla = $("#dataTableProgramaPuntos");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Nombre</th>' +
                '<th>Tipo Cálculo</th>' +
                '<th>Monto</th>' +
                '<th>Porcentaje</th>' +
                '<th>Estado</th>' +
                '<th>Fecha Creación</th>' +
            '</tr>' +
        '</thead>'
    );
}

var listar_programa_puntos = function() {
    let estado = $('#form_main_programa_puntos #estado_programa_puntos').val();

    if ($.fn.DataTable.isDataTable("#dataTableProgramaPuntos")) {
        $("#dataTableProgramaPuntos").DataTable().clear().destroy();
    }

    construirHeaderDataTableProgramaPuntos();

    table_programa_puntos = $("#dataTableProgramaPuntos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/programaPuntos/llenarDataTableProgramaPuntos.php",
            "data": {
                estado: estado
            },
            "dataSrc": "data"
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
                "data": "nombre",
                "render": function(data, type, row) {
                    return `<a href="#" class="ver-historico" data-id="${row.id}" data-toggle="tooltip" data-placement="top" title="Ver histórico de puntos" style="color: #3498db !important; background-color: transparent !important; text-decoration: none !important;">${data}</a>`;
                }
            },
            {
                "data": "tipo_calculo"
            },
            {
                "data": "monto",
                "className": "text-right"
            },
            {
                "data": "porcentaje",
                "className": "text-right"
            },
            {
                "data": "activo",
                "render": function(data, type, row) {
                    const iconSize = "1.25em";

                    if (data == 1) {
                        return '<span class="status-badge status-active"><i class="fas fa-check-circle" style="font-size: ' + iconSize + '"></i> ACTIVO</span>';
                    } else {
                        return '<span class="status-badge status-inactive"><i class="fas fa-times-circle" style="font-size: ' + iconSize + '"></i> INACTIVO</span>';
                    }
                }
            },
            {
                "data": "fecha_creacion",
                "render": function(data, type, row) {
                    moment.locale('es');
                    return moment(data).format('dddd D [de] MMMM [de] YYYY');
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
                width: "28%",
                targets: 1
            },
            {
                width: "12%",
                targets: 2
            },
            {
                width: "10%",
                targets: 3,
                className: "text-right text-nowrap"
            },
            {
                width: "10%",
                targets: 4,
                className: "text-right text-nowrap"
            },
            {
                width: "12%",
                targets: 5,
                className: "text-center text-nowrap"
            },
            {
                width: "18%",
                targets: 6
            }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Programa de Puntos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_programa_puntos();
                }
            },
            {
                text: '<i class="fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Programa de Puntos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_programa_puntos();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Programa de Puntos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte de Programa de Puntos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6]
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

            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    $('#dataTableProgramaPuntos').off('draw.dt').on('draw.dt', function() {
        $('[data-toggle="tooltip"]').tooltip();
    });

    table_programa_puntos.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    editar_programa_puntos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
    eliminar_programa_puntos_dataTable("#dataTableProgramaPuntos tbody", table_programa_puntos);
};

function formatNumber(num) {
    if (num % 1 === 0) {
        return num.toLocaleString('es-HN');
    } else {
        return num.toLocaleString('es-HN', { 
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function initCalculoEventos() {
    $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
    
    $("#tipo_calculo").on("change", function() {
        $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
        
        if (this.value === "monto") {
            $("#calculo_monto").show();
            $("#ejemplo_calculo").show();
            let montoActual = parseFloat($("#monto").val()) || 25;
            let montoDoble = montoActual * 2;
            $("#ejemploTexto").html(`Si se define que por cada <strong>${formatNumber(montoActual)}</strong> Lempiras se acumula 1 punto, entonces por consumir <strong>${formatNumber(montoDoble)}</strong> Lempiras se acumulan 2 puntos.`);
			$("#monto").focus();
        } else if (this.value === "porcentaje") {
            $("#calculo_porcentaje").show();
            $("#ejemplo_calculo").show();
            let porcentajeActual = parseFloat($("#porcentaje").val()) || 10;
            let montoConsumido = 5000;
            let puntos = (montoConsumido * porcentajeActual) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de <strong>${porcentajeActual}%</strong>, entonces por consumir <strong>${formatNumber(montoConsumido)}</strong> Lempiras se acumulan <strong>${formatNumber(puntos)}</strong> puntos.`);
			$("#porcentaje").focus();
        }
    });

    $("#monto").on("input", function() {
        if ($("#tipo_calculo").val() === "monto") {
            let monto = parseFloat($(this).val()) || 25;
            let montoDoble = monto * 2;
            $("#ejemploTexto").html(`Si se define que por cada <strong>${formatNumber(monto)}</strong> Lempiras se acumula 1 punto, entonces por consumir <strong>${formatNumber(montoDoble)}</strong> Lempiras se acumulan 2 puntos.`);
            $("#ejemplo_calculo").show();
        }
    });

    $("#porcentaje").on("input", function() {
        if ($("#tipo_calculo").val() === "porcentaje") {
            let porcentaje = parseFloat($(this).val()) || 10;
            let montoConsumido = 5000;
            let puntos = (montoConsumido * porcentaje) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de <strong>${porcentaje}%</strong>, entonces por consumir <strong>${formatNumber(montoConsumido)}</strong> Lempiras se acumulan <strong>${formatNumber(puntos)}</strong> puntos.`);
            $("#ejemplo_calculo").show();
        }
    });
}

function modal_programa_puntos() {
    $('#formProgramaPuntos')[0].reset();
    $("#calculo_monto, #calculo_porcentaje, #ejemplo_calculo").hide();
    $('#reg_ProgramaPuntos').show();
    $('#edi_ProgramaPuntos').hide();
    $('#delete_ProgramaPuntos').hide();
    
    $('#modalProgramaPuntos').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formProgramaPuntos').on('submit', function(e) {
    e.preventDefault();
    
    // Validación básica
    if ($('#nombre').val().trim() === '') {
        showNotify("error", "Error", "El nombre del programa es requerido");
        return;
    }
    
    // Determinar acción (registrar o editar)
    var isRegister = $('#reg_ProgramaPuntos').is(':visible');
    var url = isRegister 
        ? '<?php echo SERVERURL;?>core/programaPuntos/agregarProgramaPuntos.php'
        : '<?php echo SERVERURL;?>core/programaPuntos/editarProgramaPuntos.php';
    var actionText = isRegister ? 'crear' : 'actualizar';
    
    // Asegurar que el estado se envíe correctamente
    var estado = $('#ProgramaPuntos_activo').is(':checked') ? 1 : 0;
    $(this).append('<input type="hidden" name="estado" value="' + estado + '">');
    
    window.izzySwalLegacy({
        title: "¿Estás seguro?",
        text: "¿Desea " + actionText + " este programa de puntos?",
        icon: "warning",
        buttons: {
            cancel: { text: "Cancelar", visible: true },
            confirm: { text: "¡Sí, continuar!" }
        },
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm) {
            $.ajax({
                type: 'POST',
                url: url,
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.estado === true) {
                        showNotify(response.type, response.title, response.message);
                        $('#formProgramaPuntos')[0].reset();
                        $('#modalProgramaPuntos').modal('hide');
                        table_programa_puntos.ajax.reload(null, false);
                        table_programa_puntos.search('').page.len(-1).draw(false);
                    } else {
                        showNotify("error", response.title, response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    console.error("Error en la solicitud AJAX:", error);
                }
            });
        }
    });
});

var editar_programa_puntos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");
    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        
        $('#formProgramaPuntos #programa_puntos_id').val(data.id);
        $('#formProgramaPuntos').attr('data-form', 'update');
        $('#formProgramaPuntos').attr('action', '<?php echo SERVERURL;?>core/programaPuntos/editarProgramaPuntos.php');
        
        $('#reg_ProgramaPuntos').hide();
        $('#edi_ProgramaPuntos').show();
        $('#delete_ProgramaPuntos').hide();
        
        $('#formProgramaPuntos #nombre').val(data.nombre);
        $('#formProgramaPuntos #tipo_calculo').val(data.tipo_calculo).trigger('change').trigger("change");
        $('#formProgramaPuntos #monto').val(data.monto).trigger("input");
        $('#formProgramaPuntos #porcentaje').val(data.porcentaje).trigger("input");
        $('#formProgramaPuntos #estado').val(data.activo).trigger('change');
        
        if(data.tipo_calculo === "monto") {
            $("#calculo_monto").show();
            $("#ejemplo_calculo").show();
            let montoDoble = parseFloat(data.monto) * 2 || 50;
            $("#ejemploTexto").html(`Si se define que por cada ${formatNumber(data.monto)} Lempiras se acumula 1 punto, entonces por consumir ${formatNumber(montoDoble)} Lempiras se acumulan 2 puntos.`);
        } else if(data.tipo_calculo === "porcentaje") {
            $("#calculo_porcentaje").show();
            $("#ejemplo_calculo").show();
            let montoConsumido = 5000;
            let puntos = (montoConsumido * parseFloat(data.porcentaje)) / 100;
            $("#ejemploTexto").html(`Si se define un porcentaje de ${data.porcentaje}%, entonces por consumir ${formatNumber(montoConsumido)} Lempiras se acumulan ${formatNumber(puntos)} puntos.`);
        }
        
        if(data.activo == 1) {
            $('#formProgramaPuntos #ProgramaPuntos_activo').prop('checked', true);
            $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Activo");
        } else {
            $('#formProgramaPuntos #ProgramaPuntos_activo').prop('checked', false);
            $('#formProgramaPuntos #label_ProgramaPuntos_activo').html("Inactivo");
        }
        
        $('#modalProgramaPuntos').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
};

var eliminar_programa_puntos_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_eliminar");
    $(tbody).on("click", "button.table_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        
        var mensajeHTML = `¿Desea eliminar permanentemente el programa de puntos?<br><br>
                        <strong>Programa:</strong> ${data.nombre}<br>
                        <strong>Tipo Calculo:</strong> ${data.tipo_calculo}`;
        
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
                    url: '<?php echo SERVERURL;?>core/programaPuntos/eliminarProgramaPuntos.php',
                    data: {
                        programa_puntos_id: data.id
                    },
                    dataType: 'json',
                    success: function(response) {                       
                        if(response.estado === true) {
                            showNotify("success", response.title || "Éxito", response.message);
                            table_programa_puntos.ajax.reload(null, false);
                            table_programa_puntos.search('').page.len(-1).draw(false);                    
                        } else {
                            showNotify("error", response.title || "Error", response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                        console.error("Error en la solicitud AJAX:", error);
                    },
                    complete: function() {
                        Swal.close();
                    }
                });
            }
        });
    });
};

$('#dataTableProgramaPuntos').on('click', '.ver-historico', function(e) {
    e.preventDefault();
    const programaPuntosId = $(this).data('id');
    const programaNombre = $(this).text();
    
    // Destruir DataTable si ya existe
    if ($.fn.DataTable.isDataTable('#tablaHistoricoPuntos')) {
        $('#tablaHistoricoPuntos').DataTable().destroy();
        $('#tablaHistoricoPuntos tbody').empty();
    }
    
    $('#modalHistoricoPuntosLabel').text(`Historial de Puntos - ${programaNombre}`);
    $('#modalHistoricoPuntos').modal({
        show: true,
        keyboard: false
    });
    
    $('#fecha-actualizacion').text('cargando...');
    
    $.ajax({
        url: '<?php echo SERVERURL;?>core/programaPuntos/llenarDataTableHistoricoPuntos.php',
        type: 'POST',
        data: { programa_puntos_id: programaPuntosId },
        dataType: 'json',
        success: function(response) {
            $('#tablaHistoricoPuntos tbody').empty();
            
            if(response.data && response.data.length > 0) {
                response.data.forEach(function(item) {
                    $('#tablaHistoricoPuntos tbody').append(`
                        <tr>
                            <td>${item.cliente}</td>
                            <td><span class="badge ${item.tipo_movimiento === 'Acumulación' ? 'badge-success' : 'badge-danger'}">${item.tipo_movimiento}</span></td>
                            <td class="text-right">${item.puntos}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.fecha}</td>
                        </tr>
                    `);
                });
                
                $('#fecha-actualizacion').text(response.ultima_actualizacion);
                
                // Inicializar DataTable después de agregar los datos
                $('#tablaHistoricoPuntos').DataTable({
                    "language": idioma_español,
                    "dom": '<"top"f>rt<"bottom"lip><"clear">',
                    "pageLength": 10,
                    "order": [[4, "desc"]],
                    "columnDefs": [
                        { width: "25%", targets: 0 },
                        { width: "15%", targets: 1 },
                        { width: "10%", targets: 2 },
                        { width: "30%", targets: 3 },
                        { width: "20%", targets: 4 }
                    ],
                    "initComplete": function() {
                        // Asegurar que las tooltips funcionen
                        $('[data-toggle="tooltip"]').tooltip();
                    }
                });
            } else {
                $('#tablaHistoricoPuntos tbody').append(`
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de historial para este programa</td>
                    </tr>
                `);
                $('#fecha-actualizacion').text('No disponible');
            }
        },
        error: function() {
            $('#tablaHistoricoPuntos tbody').append(`
                <tr>
                    <td colspan="5" class="text-center text-danger">Error al cargar el historial</td>
                </tr>
            `);
            $('#fecha-actualizacion').text('Error');
        }
    });
});

$('#modalHistoricoPuntos').on('hidden.bs.modal', function() {
    // Destruir DataTable al cerrar el modal
    if ($.fn.DataTable.isDataTable('#tablaHistoricoPuntos')) {
        $('#tablaHistoricoPuntos').DataTable().destroy();
    }
    $('#tablaHistoricoPuntos tbody').empty();
});
</script>