<script>

/* =========================================================
   IZZY | XLSX - BORDES COMPLETOS EN RANGOS COMBINADOS
   Conserva estilos existentes y completa todas las celdas
   del rango combinado para evitar contornos incompletos.
   ========================================================= */
if (typeof window.izzyExcelCompletarBordesCombinados !== 'function') {
    window.izzyExcelCompletarBordesCombinados = function (xmlTexto) {
        if (!xmlTexto || typeof DOMParser === 'undefined' || typeof XMLSerializer === 'undefined') return xmlTexto;
        try {
            var declaracion = '';
            var matchDeclaracion = String(xmlTexto).match(/^\s*(<\?xml[^>]*\?>)/);
            if (matchDeclaracion) declaracion = matchDeclaracion[1];

            var parser = new DOMParser();
            var documento = parser.parseFromString(String(xmlTexto), 'application/xml');
            if (documento.getElementsByTagName('parsererror').length) return xmlTexto;

            var namespaceUri = documento.documentElement.namespaceURI || 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            var sheetData = documento.getElementsByTagName('sheetData')[0];
            var mergeCells = documento.getElementsByTagName('mergeCells')[0];
            if (!sheetData || !mergeCells) return xmlTexto;

            function columnaNumero(letras) {
                var total = 0, texto = String(letras || '').toUpperCase();
                for (var i = 0; i < texto.length; i++) total = (total * 26) + (texto.charCodeAt(i) - 64);
                return total;
            }
            function columnaLetras(numero) {
                var resultado = '', n = numero;
                while (n > 0) {
                    var resto = (n - 1) % 26;
                    resultado = String.fromCharCode(65 + resto) + resultado;
                    n = Math.floor((n - 1) / 26);
                }
                return resultado;
            }
            function parseReferencia(ref) {
                var match = String(ref || '').match(/^([A-Z]+)(\d+)$/);
                return match ? {col: columnaNumero(match[1]), row: parseInt(match[2], 10)} : null;
            }
            function obtenerFila(numeroFila) {
                var filas = sheetData.getElementsByTagName('row');
                for (var i = 0; i < filas.length; i++) {
                    if (parseInt(filas[i].getAttribute('r'), 10) === numeroFila) return filas[i];
                }
                var nuevaFila = documento.createElementNS(namespaceUri, 'row');
                nuevaFila.setAttribute('r', String(numeroFila));
                var insertada = false;
                for (var j = 0; j < filas.length; j++) {
                    var actual = parseInt(filas[j].getAttribute('r'), 10);
                    if (actual > numeroFila) {
                        sheetData.insertBefore(nuevaFila, filas[j]);
                        insertada = true;
                        break;
                    }
                }
                if (!insertada) sheetData.appendChild(nuevaFila);
                return nuevaFila;
            }
            function buscarCelda(fila, referencia) {
                var celdas = fila.getElementsByTagName('c');
                for (var i = 0; i < celdas.length; i++) {
                    if (celdas[i].getAttribute('r') === referencia) return celdas[i];
                }
                return null;
            }
            function insertarCeldaOrdenada(fila, celda, colNumero) {
                var celdas = fila.getElementsByTagName('c');
                for (var i = 0; i < celdas.length; i++) {
                    var refActual = parseReferencia(celdas[i].getAttribute('r'));
                    if (refActual && refActual.col > colNumero) {
                        fila.insertBefore(celda, celdas[i]);
                        return;
                    }
                }
                fila.appendChild(celda);
            }

            var merges = Array.prototype.slice.call(mergeCells.getElementsByTagName('mergeCell'));
            merges.forEach(function (merge) {
                var partes = String(merge.getAttribute('ref') || '').split(':');
                if (partes.length !== 2) return;
                var inicio = parseReferencia(partes[0]);
                var fin = parseReferencia(partes[1]);
                if (!inicio || !fin) return;

                if (inicio.col === fin.col && inicio.row === fin.row) {
                    mergeCells.removeChild(merge);
                    return;
                }

                var filaInicio = obtenerFila(inicio.row);
                var celdaInicio = buscarCelda(filaInicio, columnaLetras(inicio.col) + inicio.row);
                if (!celdaInicio) return;
                var estilo = celdaInicio.getAttribute('s');

                for (var filaNumero = inicio.row; filaNumero <= fin.row; filaNumero++) {
                    var fila = obtenerFila(filaNumero);
                    for (var colNumero = inicio.col; colNumero <= fin.col; colNumero++) {
                        var referencia = columnaLetras(colNumero) + filaNumero;
                        var celda = buscarCelda(fila, referencia);
                        if (!celda) {
                            celda = documento.createElementNS(namespaceUri, 'c');
                            celda.setAttribute('r', referencia);
                            if (estilo !== null && estilo !== '') celda.setAttribute('s', estilo);
                            insertarCeldaOrdenada(fila, celda, colNumero);
                        }
                    }
                }
            });

            var mergeFinales = mergeCells.getElementsByTagName('mergeCell');
            mergeCells.setAttribute('count', String(mergeFinales.length));
            var serializado = new XMLSerializer().serializeToString(documento.documentElement);
            return declaracion ? declaracion + serializado : serializado;
        } catch (error) {
            console.error('No se pudieron completar los bordes del XLSX:', error);
            return xmlTexto;
        }
    };
}


/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Permisos / Tipo Usuario
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

var tipoUserModernConfig = {"table":"#dataTableTipoUser","key":"tipo_user_id","title":"Permisos / Tipo Usuario","exportTitle":"Reporte Tipos de Usuario","fields":[{"key":"nombre","label":"Tipo Usuario","icon":"fas fa-user-tag","type":"text"},{"key":"estado","label":"Estado","icon":"fas fa-toggle-on","type":"status"}],"actions":[{"label":"Asignar permisos","icon":"fas fa-users-cog","target":"button.table_permisos","classes":"table_permisos"},{"label":"Editar","icon":"fas fa-edit","target":"button.table_editar1","classes":"accion-editar table_editar1 table_editar"},{"label":"Eliminar","icon":"fas fa-trash-alt","target":"button.table_eliminar1","classes":"accion-eliminar table_eliminar1 table_eliminar"}],"kpis":[{"id":"total","label":"Tipos de usuario","desc":"Registros encontrados","icon":"fas fa-users-cog","color":"blue","calc":{"type":"count"}},{"id":"activos","label":"Activos","desc":"Tipos de usuario activos","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"estado","value":"1"}},{"id":"inactivos","label":"Inactivos","desc":"Tipos de usuario inactivos","icon":"fas fa-times-circle","color":"orange","calc":{"type":"eq","key":"estado","value":"0"}}],"storageView":"izzy.tipoUser.tipo_vista"};

var tipoUserModern = {
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

function tipoUserModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function tipoUserModernEscape(value) {
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

function tipoUserModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function tipoUserModernEstado(value) {
    return parseInt(value, 10) === 1;
}

function tipoUserModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = tipoUserModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return tipoUserModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return tipoUserModernEstado(value)
            ? '<span class="tipo-user-status-badge tipo-user-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="tipo-user-status-badge tipo-user-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : tipoUserModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + tipoUserModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return tipoUserModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="tipo-user-method-badge tipo-user-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="tipo-user-method-badge tipo-user-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="tipo-user-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = tipoUserModernTexto(row.graph_user, 'No configurado');
        var tenant = tipoUserModernTexto(row.tenant_id, 'No configurado');
        var client = tipoUserModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="tipo-user-tech-lines">' +
            '<span><strong>Graph:</strong> ' + tipoUserModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + tipoUserModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + tipoUserModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : tipoUserModernEscape(text);
}

function tipoUserModernClaseIconoAccion(action) {
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

function tipoUserModernAcciones(row) {
    var key = tipoUserModernEscape(tipoUserModernTexto(row[tipoUserModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown tipo-user-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    tipoUserModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            tipoUserModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + tipoUserModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + tipoUserModernClaseIconoAccion(action) + '"><i class="' + tipoUserModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + tipoUserModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}

function tipoUserModernFiltrar() {
    var q = $.trim(tipoUserModern.search || '').toLowerCase();

    tipoUserModern.filtered = !q
        ? tipoUserModern.rows.slice()
        : tipoUserModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    tipoUserModernActualizarKpis();
}

function tipoUserModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return tipoUserModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return tipoUserModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return tipoUserModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        tipoUserModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return tipoUserModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return tipoUserModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function tipoUserModernActualizarKpis() {
    tipoUserModernConfig.kpis.forEach(function(kpi) {
        $('#tipo-user-kpi-' + kpi.id).text(tipoUserModernCalcularKpi(kpi.calc));
    });
}

function tipoUserModernRenderDetalle(rows) {
    var html = '<div class="tipo-user-detail-header"><div>Acciones</div>';

    tipoUserModernConfig.fields.forEach(function(field) {
        html += '<div>' + tipoUserModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = tipoUserModernEscape(tipoUserModernTexto(row[tipoUserModernConfig.key], ''));

        html += '<article class="tipo-user-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="tipo-user-cell tipo-user-actions-cell">' +
                '<span class="tipo-user-cell-label">Acciones</span>' +
                tipoUserModernAcciones(row) +
            '</div>';

        tipoUserModernConfig.fields.forEach(function(field) {
            var contenidoCampo = field.type === 'status'
                ? '<div class="tipo-user-status-only">' + tipoUserModernFormatoCampo(row, field, false) + '</div>'
                : '<div class="tipo-user-field-main">' +
                    '<i class="' + tipoUserModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="tipo-user-field-value">' + tipoUserModernFormatoCampo(row, field, false) + '</div>' +
                  '</div>';

            html += '<div class="tipo-user-cell' + (field.type === 'status' ? ' tipo-user-status-cell' : '') + '">' +
                '<span class="tipo-user-cell-label">' + tipoUserModernEscape(field.label) + '</span>' +
                '<div class="tipo-user-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>';
    });

    return html;
}

function tipoUserModernRenderMiniatura(rows) {
    var html = '<div class="tipo-user-mini-grid">';
    var primary = tipoUserModernConfig.fields[0];
    var statusField = null;

    tipoUserModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = tipoUserModernEscape(tipoUserModernTexto(row[tipoUserModernConfig.key], ''));
        var primaryValue = primary ? tipoUserModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? tipoUserModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="tipo-user-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="tipo-user-mini-topline"></div>' +
            '<div class="tipo-user-mini-header">' +
                '<span class="tipo-user-mini-icon"><i class="' + tipoUserModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="tipo-user-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + tipoUserModernEscape(tipoUserModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="tipo-user-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="tipo-user-mini-body">';

        tipoUserModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="tipo-user-mini-field">' +
                '<span><i class="' + tipoUserModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    tipoUserModernEscape(field.label) + '</span>' +
                '<div>' + tipoUserModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="tipo-user-mini-footer">' + tipoUserModernAcciones(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function tipoUserModernRenderPaginacion(totalPages) {
    var current = tipoUserModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="tipo-user-page-btn' + (active ? ' active' : '') +
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

    $('#tipo-user-pagination').html(html);
}

function tipoUserModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function tipoUserModernRender() {
    var rows = tipoUserModern.filtered || [];

    if (tipoUserModern.loading) {
        $('#tipo-user-listado').html(
            '<div class="tipo-user-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#tipo-user-info').text('0 registros');
        $('#tipo-user-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#tipo-user-listado').html(
            '<div class="tipo-user-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#tipo-user-info').text('0 registros');
        $('#tipo-user-pagination').empty();
        tipoUserModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / tipoUserModern.pageSize));

    if (tipoUserModern.page > totalPages) {
        tipoUserModern.page = totalPages;
    }

    var offset = (tipoUserModern.page - 1) * tipoUserModern.pageSize;
    var pageRows = rows.slice(offset, offset + tipoUserModern.pageSize);

    $('#tipo-user-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + tipoUserModern.view)
        .html(
            tipoUserModern.view === 'miniatura'
                ? tipoUserModernRenderMiniatura(pageRows)
                : tipoUserModernRenderDetalle(pageRows)
        );

    $('#tipo-user-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    tipoUserModernRenderPaginacion(totalPages);
    tipoUserModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function tipoUserModernSyncPageSize() {
    var mini = tipoUserModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? tipoUserModern.pageSizeMiniatura : tipoUserModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#tipo-user-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    tipoUserModern.pageSize = selected;
    $select.val(String(selected));
}

function tipoUserModernSyncView() {
    var mobile = tipoUserModernEsMovil();

    if (mobile) {
        tipoUserModern.view = 'miniatura';
    }

    $('.tipo-user-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.tipo-user-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.tipo-user-view-btn[data-view="' + tipoUserModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function tipoUserModernConfigurarPanel(button, content, storageKey) {
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

    $(button).off('click.tipoUserModernPanel').on('click.tipoUserModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function tipoUserModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableTipoUser")) {
        return null;
    }

    var api = $("#dataTableTipoUser").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["tipo_user_id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function tipoUserModernEjecutarAccion(recordKey, targetSelector) {
    var row = tipoUserModernEncontrarFilaEnDataTable(recordKey);

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

function tipoUserModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function tipoUserModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function tipoUserModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = tipoUserModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function tipoUserModernDescargarBlob(blob, nombreArchivo) {
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

function tipoUserModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function tipoUserModernDatosExcel() {
    return (tipoUserModern.filtered || []).map(function(row) {
        return tipoUserModernConfig.fields.map(function(field) {
            return tipoUserModernFormatoCampo(row, field, true);
        });
    });
}

function tipoUserModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = tipoUserModernConfig.fields || [];
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
    var lastCol = tipoUserModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            tipoUserModernExcelCell(
                'A1',
                'IZZY • ' + String(tipoUserModernConfig.exportTitle || tipoUserModernConfig.title || 'REPORTE').toUpperCase(),
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            tipoUserModernExcelCell(
                'A2',
                'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        tipoUserModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        tipoUserModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += tipoUserModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += tipoUserModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += tipoUserModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += tipoUserModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            tipoUserModernExcelCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return tipoUserModernExcelCell(
            tipoUserModernExcelColName(index) + headerRow,
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

            return tipoUserModernExcelCell(
                tipoUserModernExcelColName(colIndex) + excelRow,
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
    zip.folder('xl').folder('worksheets').file('sheet1.xml', window.izzyExcelCompletarBordesCombinados(sheetXml));

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

function tipoUserModernExportarExcel() {
    var rows = tipoUserModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = tipoUserModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(tipoUserModernConfig.exportTitle || tipoUserModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            tipoUserModernDescargarBlob(
                blob,
                nombre + '_' + tipoUserModernFechaArchivo() + '.xlsx'
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

function tipoUserModernPdfObtenerLogo(callback) {
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

function tipoUserModernPdfLogoPlate(logoDataUrl) {
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

function tipoUserModernPdfDetalle(rows) {
    var fields = tipoUserModernConfig.fields;
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
            var value = String(tipoUserModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = tipoUserModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
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

function tipoUserModernPdfTarjeta(row) {
    var fields = tipoUserModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(tipoUserModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(tipoUserModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && tipoUserModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(tipoUserModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
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

function tipoUserModernExportarPdf() {
    var rows = tipoUserModern.filtered || [];

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
        tipoUserModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            tipoUserModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#tipo-user-search').val() || '').trim();
    var filtroEstado = $("#estado_permisos").length
        ? ($("#estado_permisos" + ' option:selected').text() || 'Todos')
        : 'Todos';
    var filtrosTexto = 'Estado: ' + filtroEstado + '   |   Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = tipoUserModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: tipoUserModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Tipos de usuario y configuración de permisos", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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

    var kpis = tipoUserModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(tipoUserModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (tipoUserModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [tipoUserModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [tipoUserModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [tipoUserModernPdfDetalle(rows)];
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
                { text: 'IZZY • ' + tipoUserModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: tipoUserModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = tipoUserModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, tipoUserModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(tipoUserModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            tipoUserModern.preferredView = savedView;
            tipoUserModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableTipoUser")
        .off('xhr.dt.tipoUserModern')
        .on('xhr.dt.tipoUserModern', function(e, settings, json) {
            tipoUserModern.rows = json && Array.isArray(json.data) ? json.data : [];
            tipoUserModern.loading = false;
            tipoUserModern.page = 1;
            tipoUserModernFiltrar();
            tipoUserModernRender();
        });

    tipoUserModernSyncView();
    tipoUserModernSyncPageSize();
    tipoUserModernRender();

    if ($('#tipo-user-toggle-filtros').length) {
        tipoUserModernConfigurarPanel(
            '#tipo-user-toggle-filtros',
            '#tipo-user-filtros-contenido',
            'izzy.tipoUser.panel.filtros'
        );
    }

    tipoUserModernConfigurarPanel(
        '#tipo-user-toggle-kpis',
        '#tipo-user-kpis-contenido',
        'izzy.tipoUser.panel.kpis'
    );

    $('#tipo-user-btn-refresh').off('click.tipoUserModern').on('click.tipoUserModern', function() {
        if (typeof listar_tipo_usuario === 'function') {
            tipoUserModern.loading = true;
            tipoUserModernRender();
            listar_tipo_usuario();
        }
    });

    $('#tipo-user-btn-create').off('click.tipoUserModern').on('click.tipoUserModern', function() {
        if (typeof modal_tipo_usuarios === 'function') {
            modal_tipo_usuarios();
        }
    });

    $('#tipo-user-btn-excel').off('click.tipoUserModern').on('click.tipoUserModern', tipoUserModernExportarExcel);
    $('#tipo-user-btn-pdf').off('click.tipoUserModern').on('click.tipoUserModern', tipoUserModernExportarPdf);

    $('#tipo-user-page-size').off('change.tipoUserModern').on('change.tipoUserModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (tipoUserModern.view === 'miniatura') {
            tipoUserModern.pageSizeMiniatura = value;
        } else {
            tipoUserModern.pageSizeDetalle = value;
        }

        tipoUserModern.pageSize = value;
        tipoUserModern.page = 1;
        tipoUserModernRender();
    });

    $('.tipo-user-view-btn').off('click.tipoUserModern').on('click.tipoUserModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (tipoUserModernEsMovil()) {
            tipoUserModern.view = 'miniatura';
        } else {
            tipoUserModern.view = next;
            tipoUserModern.preferredView = next;

            try {
                localStorage.setItem(
                    tipoUserModernConfig.storageView,
                    tipoUserModern.preferredView
                );
            } catch (e) {}
        }

        tipoUserModern.page = 1;
        tipoUserModernSyncView();
        tipoUserModernSyncPageSize();
        tipoUserModernRender();
    });

    $('#tipo-user-search').off('input.tipoUserModern').on('input.tipoUserModern', function() {
        tipoUserModern.search = $(this).val() || '';
        tipoUserModern.page = 1;
        tipoUserModernFiltrar();
        tipoUserModernRender();
    });

    $('#tipo-user-search-clear').off('click.tipoUserModern').on('click.tipoUserModern', function() {
        $('#tipo-user-search').val('').focus();
        tipoUserModern.search = '';
        tipoUserModern.page = 1;
        tipoUserModernFiltrar();
        tipoUserModernRender();
    });

    $('#tipo-user-pagination')
        .off('click.tipoUserModern', '.tipo-user-page-btn')
        .on('click.tipoUserModern', '.tipo-user-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                tipoUserModern.page = page;
                tipoUserModernRender();

                var target = document.querySelector('.tipo-user-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#tipo-user-listado')
        .off('click.tipoUserModern', '.modern-action-bridge')
        .on('click.tipoUserModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            tipoUserModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#tipo-user-listado')
        .off('click.tipoUserModernDropdown', '.js-acciones-toggle')
        .on('click.tipoUserModernDropdown', '.js-acciones-toggle', function() {
            $('.tipo-user-detail-row, .tipo-user-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.tipo-user-detail-row, .tipo-user-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.tipoUserModernDropdownClose')
        .on('click.tipoUserModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.tipo-user-detail-row, .tipo-user-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.tipoUserModern orientationchange.tipoUserModern')
        .on('resize.tipoUserModern orientationchange.tipoUserModern', function() {
            var previous = tipoUserModern.view;
            var objetivo = tipoUserModernEsMovil()
                ? 'miniatura'
                : tipoUserModern.preferredView;

            tipoUserModern.view = objetivo;

            if (previous !== objetivo) {
                tipoUserModern.page = 1;
                tipoUserModernSyncPageSize();
            }

            tipoUserModernSyncView();
            tipoUserModernRender();
        });
});


$(document).ready(function() {
    listar_tipo_usuario(); 
	
	$('#form_main_permisos #search').on("click", function (e) {
		e.preventDefault();
		listar_tipo_usuario();
	});

	// Evento para el botón de Limpiar (reset)
	$('#form_main_permisos').on('reset', function () {
		// Limpia y refresca los selects
		$(this).find('select') // Usa `this` para referenciar el formulario actual
			.val('')
			.trigger('change');

			listar_tipo_usuario();
	});
});

/* =========================================================
   HEADER DINÁMICO - TIPO USUARIO
   ========================================================= */
   function construirHeaderDataTableTipoUser() {
    var $tabla = $("#dataTableTipoUser");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Tipo Usuario</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>'
    );
}

//INICIO ACCIONES FROMULARIO TIPO USUARIO
var listar_tipo_usuario = function() {
    var estado = $('#form_main_permisos #estado_permisos').val();

    if ($.fn.DataTable.isDataTable("#dataTableTipoUser")) {
        $("#dataTableTipoUser").DataTable().clear().destroy();
    }

    construirHeaderDataTableTipoUser();

    var table_tipo_usuario = $("#dataTableTipoUser").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL;?>core/llenarDataTableTipoUsuario.php",
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

                                '<button type="button" class="dropdown-item accion-item table_permisos">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-users-cog"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Asignar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-editar table_editar1 table_editar">' +
                                    '<span class="accion-icon accion-icon-editar">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Editar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar1 table_eliminar">' +
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
                titleAttr: 'Actualizar Tipos de Usuario',
                className: 'btn btn-secondary',
                action: function() {
                    listar_tipo_usuario();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Ingresar',
                titleAttr: 'Agregar Tipos de Usuario',
                className: 'btn btn-primary',
                action: function() {
                    modal_tipo_usuarios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Tipos de Usuario',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-success',
                exportOptions: {
                    columns: [1]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Tipos de Usuario',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'btn btn-danger',
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

    table_tipo_usuario.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    permisos_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
    editar_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
    eliminar_tipo_usuario_dataTable("#dataTableTipoUser tbody", table_tipo_usuario);
}

var permisos_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_permisos");
	$(tbody).on("click", "button.table_permisos", function(){
		var data = table.row( $(this).parents("tr") ).data();
		getPermisosControl(data.tipo_user_id, data.nombre);
	});
}

function getPermisosControl(tipo_user_id, nombre){
	var url = '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php';

	$('#formPermisos #permisos_tipo_user_id').val(tipo_user_id);
				
	$.ajax({
		type:'POST',
		url:url,
		data:$('#formPermisos').serialize(),
		success: function(registro){
			valoresTipoAcceso = JSON.parse(registro);

			$('#formPermisos').attr({ 'data-form': 'save' });
			$('#formPermisos').attr({ 'action': '<?php echo SERVERURL;?>ajax/addPermisosAccesosAjax.php' });
			$('#formPermisos')[0].reset();
			$('#formPermisos #permisos_tipo_user_id').val(tipo_user_id);
			$('#formPermisos #pro_permisos').val("Asignar Permisos: " + nombre);
			$('#formPermisos #permisos_nombre').val(nombre);
			
			$('#formPermisos #opcion_guardar').attr('checked', false);
			$('#formPermisos #opcion_editar').attr('checked', false);
			$('#formPermisos #opcion_eliminar').attr('checked', false);
			$('#formPermisos #opcion_consultar').attr('checked', false);
			$('#formPermisos #opcion_imprimir').attr('checked', false);				
			
			for(var i=0; i < valoresTipoAcceso.length; i++){
				if(valoresTipoAcceso[i].estado == 1){
					$('#formPermisos #opcion_' + valoresTipoAcceso[i].tipo_permiso).attr('checked', true);
				}else{
					$('#formPermisos #opcion_' + valoresTipoAcceso[i].tipo_permiso).attr('checked', false);
				}
			}
			
			$('#modal_permisos').modal({
				show:true,
				keyboard: false,
				backdrop:'static'
			});
		}
	});
}

var editar_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_editar1");
	$(tbody).on("click", "button.table_editar1", function(){
		var data = table.row( $(this).parents("tr") ).data();
		var url = '<?php echo SERVERURL;?>core/editarTipoUsuario.php';
		$('#formTipoUsuario #tipo_user_id').val(data.tipo_user_id);

		$.ajax({
			type:'POST',
			url:url,
			data:$('#formTipoUsuario').serialize(),
			success: function(registro){
				var valores = eval(registro);
				$('#formTipoUsuario').attr({ 'data-form': 'update' });
				$('#formTipoUsuario').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarTipoUsuarioAjax.php' });
				$('#formTipoUsuario')[0].reset();
				$('#reg_tipo_usuario').hide();
				$('#edi_tipo_usuario').show();
				$('#delete_tipo_usuario').hide();
				$('#formTipoUsuario #tipo_usuario_nombre').val(valores[0]);

				if(valores[1] == 1){
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', true);
				}else{
					$('#formTipoUsuario #tipo_usuario_activo').attr('checked', false);
				}

				//HABILITAR OBJETOS
				$('#formTipoUsuario #tipo_usuario_nombre').attr('readonly', false);
				$('#formTipoUsuario #tipo_usuario_activo').attr('disabled', false);
				$('#formTipoUsuario #estado_tipo_usuario').show();

				$('#formTipoUsuario #proceso_tipo_usuario').val("Editar");
				$('#modal_registrar_tipoUsuario').modal({
					show:true,
					keyboard: false,
					backdrop:'static'
				});
			}
		});
	});
}

var eliminar_tipo_usuario_dataTable = function(tbody, table){
	$(tbody).off("click", "button.table_eliminar1");
	$(tbody).on("click", "button.table_eliminar1", function(){
		var data = table.row( $(this).parents("tr") ).data();

		var tipo_user_id = data.tipo_user_id;
        var nombreTipoUsuario = data.nombre; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el tipo de usuario?<br><br>
                        <strong>Nombre:</strong> ${nombreTipoUsuario}`;
        
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
                    url: '<?php echo SERVERURL;?>ajax/eliminarTipoUsuariosAjax.php',
                    data: {
                        tipo_user_id: tipo_user_id
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
//FIN ACCIONES FROMULARIO TIPO USUARIO

/*INICIO FORMULARIO TIPO USUARIO*/
function modal_tipo_usuarios(){
	$('#formTipoUsuario').attr({ 'data-form': 'save' });
	$('#formTipoUsuario').attr({ 'action': '<?php echo SERVERURL;?>ajax/agregarTipoUsuarioAjax.php' });
	$('#formTipoUsuario')[0].reset();
	$('#reg_tipo_usuario').show();
	$('#edi_tipo_usuario').hide();
	$('#delete_tipo_usuario').hide();

	//HABILITAR OBJETOS
	$('#formTipoUsuario #tipo_usuario_nombre').attr('readonly', false);
	$('#formTipoUsuario #tipo_usuario_activo').attr('disabled', false);
	$('#formTipoUsuario #estado_tipo_usuario').hide();

	$('#formTipoUsuario #proceso_tipo_usuario').val("Registro");
	$('#modal_registrar_tipoUsuario').modal({
		show:true,
		keyboard: false,
		backdrop:'static'
	});
}
/*FIN FORMULARIO TIPO USAURIO*/

$(document).ready(function(){
    $("#modal_registrar_tipoUsuario").on('shown.bs.modal', function(){
        $(this).find('#formTipoUsuario #tipo_usuario_nombre').focus();
    });
});

$('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
	
$('#formTipoUsuario .switch').change(function(){    
    if($('input[name=tipo_usuario_activo]').is(':checked')){
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
        return true;
    }
    else{
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Inactivo");
        return false;
    }
});	

//INICIO PERMISOS
$('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
	
$('#formTipoUsuario .switch').change(function(){    
    if($('input[name=tipo_usuario_activo]').is(':checked')){
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Activo");
        return true;
    }
    else{
        $('#formTipoUsuario #label_tipo_usuario_activo').html("Inactivo");
        return false;
    }
});	

$('#formPermisos #label_opcion_guardar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_guardar]').is(':checked')){
		$('#formPermisos #label_opcion_guardar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_guardar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_editar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_editar]').is(':checked')){
		$('#formPermisos #label_opcion_editar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_editar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_eliminar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_eliminar]').is(':checked')){
		$('#formPermisos #label_opcion_eliminar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_eliminar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_consultar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_consultar]').is(':checked')){
		$('#formPermisos #label_opcion_consultar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_consultar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_imprimir').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_imprimir]').is(':checked')){
		$('#formPermisos #label_opcion_imprimir').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_imprimir').html("Desactivado");
		return false;
	}
});

$('#formPermisos #label_opcion_crear').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_crear]').is(':checked')){
		$('#formPermisos #label_opcion_crear').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_crear').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_reportes').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_reportes]').is(':checked')){
		$('#formPermisos #label_opcion_reportes').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_reportes').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_actualizar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_actualizar]').is(':checked')){
		$('#formPermisos #label_opcion_actualizar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_actualizar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_view').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_view]').is(':checked')){
		$('#formPermisos #label_opcion_view').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_view').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_pay').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_pay]').is(':checked')){
		$('#formPermisos #label_opcion_pay').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_pay').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_cambiar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_cambiar]').is(':checked')){
		$('#formPermisos #label_opcion_cambiar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_cambiar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_cancelar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_cancelar]').is(':checked')){
		$('#formPermisos #label_opcion_cancelar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_cancelar').html("Desactivado");
		return false;
	}
});		

$('#formPermisos #label_opcion_generar').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_generar]').is(':checked')){
		$('#formPermisos #label_opcion_generar').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_generar').html("Desactivado");
		return false;
	}
});	

$('#formPermisos #label_opcion_sistema').html("Desactivado");

$('#formPermisos .switch').change(function(){    
	if($('input[name=opcion_sistema]').is(':checked')){
		$('#formPermisos #label_opcion_sistema').html("Activado");
		return true;
	}
	else{
		$('#formPermisos #label_opcion_sistema').html("Desactivado");
		return false;
	}
});
//INICIO PERMISOS

</script>