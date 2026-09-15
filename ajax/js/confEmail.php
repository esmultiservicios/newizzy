<script>

/* =========================================================
   IZZY | XLSX - BORDES COMPLETOS EN RANGOS COMBINADOS
   Mantiene intacto el contenido del reporte y completa las
   celdas internas de mergeCells con el estilo ya existente.
   ========================================================= */
if (typeof window.izzyExcelCompletarBordesCombinados !== 'function') {
    window.izzyExcelCompletarBordesCombinados = function (xmlTexto) {
        if (
            !xmlTexto ||
            typeof DOMParser === 'undefined' ||
            typeof XMLSerializer === 'undefined'
        ) {
            return xmlTexto;
        }

        try {
            var declaracion = '';
            var matchDeclaracion = String(xmlTexto).match(
                /^\s*(<\?xml[^>]*\?>)/
            );

            if (matchDeclaracion) {
                declaracion = matchDeclaracion[1];
            }

            var parser = new DOMParser();
            var documento = parser.parseFromString(
                String(xmlTexto),
                'application/xml'
            );

            if (documento.getElementsByTagName('parsererror').length) {
                return xmlTexto;
            }

            var namespaceUri =
                documento.documentElement.namespaceURI ||
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

            var sheetData =
                documento.getElementsByTagName('sheetData')[0];

            var mergeCells =
                documento.getElementsByTagName('mergeCells')[0];

            if (!sheetData || !mergeCells) {
                return xmlTexto;
            }

            function columnaNumero(letras) {
                var total = 0;
                var texto = String(letras || '').toUpperCase();

                for (var i = 0; i < texto.length; i++) {
                    total = (total * 26) +
                        (texto.charCodeAt(i) - 64);
                }

                return total;
            }

            function columnaLetras(numero) {
                var resultado = '';
                var n = numero;

                while (n > 0) {
                    var resto = (n - 1) % 26;
                    resultado =
                        String.fromCharCode(65 + resto) +
                        resultado;
                    n = Math.floor((n - 1) / 26);
                }

                return resultado;
            }

            function parseReferencia(ref) {
                var match = String(ref || '').match(
                    /^([A-Z]+)(\d+)$/
                );

                if (!match) {
                    return null;
                }

                return {
                    col: columnaNumero(match[1]),
                    row: parseInt(match[2], 10)
                };
            }

            function obtenerFila(numeroFila) {
                var filas = sheetData.getElementsByTagName('row');

                for (var i = 0; i < filas.length; i++) {
                    if (
                        parseInt(
                            filas[i].getAttribute('r'),
                            10
                        ) === numeroFila
                    ) {
                        return filas[i];
                    }
                }

                var nuevaFila = documento.createElementNS(
                    namespaceUri,
                    'row'
                );

                nuevaFila.setAttribute(
                    'r',
                    String(numeroFila)
                );

                var insertada = false;

                for (var j = 0; j < filas.length; j++) {
                    var actual = parseInt(
                        filas[j].getAttribute('r'),
                        10
                    );

                    if (actual > numeroFila) {
                        sheetData.insertBefore(
                            nuevaFila,
                            filas[j]
                        );
                        insertada = true;
                        break;
                    }
                }

                if (!insertada) {
                    sheetData.appendChild(nuevaFila);
                }

                return nuevaFila;
            }

            function buscarCelda(fila, referencia) {
                var celdas = fila.getElementsByTagName('c');

                for (var i = 0; i < celdas.length; i++) {
                    if (
                        celdas[i].getAttribute('r') ===
                        referencia
                    ) {
                        return celdas[i];
                    }
                }

                return null;
            }

            function insertarCeldaOrdenada(fila, celda, colNumero) {
                var celdas = fila.getElementsByTagName('c');

                for (var i = 0; i < celdas.length; i++) {
                    var refActual = parseReferencia(
                        celdas[i].getAttribute('r')
                    );

                    if (
                        refActual &&
                        refActual.col > colNumero
                    ) {
                        fila.insertBefore(
                            celda,
                            celdas[i]
                        );
                        return;
                    }
                }

                fila.appendChild(celda);
            }

            var merges = Array.prototype.slice.call(
                mergeCells.getElementsByTagName('mergeCell')
            );

            merges.forEach(function (merge) {
                var ref = merge.getAttribute('ref') || '';
                var partes = ref.split(':');

                if (partes.length !== 2) {
                    return;
                }

                var inicio = parseReferencia(partes[0]);
                var fin = parseReferencia(partes[1]);

                if (!inicio || !fin) {
                    return;
                }

                /* Elimina combinaciones inválidas como I3:I3. */
                if (
                    inicio.col === fin.col &&
                    inicio.row === fin.row
                ) {
                    mergeCells.removeChild(merge);
                    return;
                }

                var filaInicio = obtenerFila(inicio.row);
                var celdaInicio = buscarCelda(
                    filaInicio,
                    columnaLetras(inicio.col) +
                    inicio.row
                );

                if (!celdaInicio) {
                    return;
                }

                var estilo = celdaInicio.getAttribute('s');

                /*
                 * Completa todo el rango con el mismo estilo
                 * ya definido por el reporte. No crea estilos nuevos.
                 */
                for (
                    var filaNumero = inicio.row;
                    filaNumero <= fin.row;
                    filaNumero++
                ) {
                    var fila = obtenerFila(filaNumero);

                    for (
                        var colNumero = inicio.col;
                        colNumero <= fin.col;
                        colNumero++
                    ) {
                        var referencia =
                            columnaLetras(colNumero) +
                            filaNumero;

                        var celda = buscarCelda(
                            fila,
                            referencia
                        );

                        if (!celda) {
                            celda = documento.createElementNS(
                                namespaceUri,
                                'c'
                            );

                            celda.setAttribute(
                                'r',
                                referencia
                            );

                            if (
                                estilo !== null &&
                                estilo !== ''
                            ) {
                                celda.setAttribute(
                                    's',
                                    estilo
                                );
                            }

                            insertarCeldaOrdenada(
                                fila,
                                celda,
                                colNumero
                            );
                        }
                    }
                }
            });

            var mergeFinales =
                mergeCells.getElementsByTagName('mergeCell');

            mergeCells.setAttribute(
                'count',
                String(mergeFinales.length)
            );

            var serializado =
                new XMLSerializer().serializeToString(
                    documento.documentElement
                );

            return declaracion
                ? declaracion + serializado
                : serializado;

        } catch (error) {
            console.error(
                'No se pudieron completar los bordes del XLSX:',
                error
            );

            return xmlTexto;
        }
    };
}


/* =========================================================
   IZZY 6.0 | CAPA VISUAL MODERNA - Configurar Correos
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

var correoModernConfig = {"table":"#dataTableConfCorreos","key":"correo_id","title":"Configurar Correos","exportTitle":"Reporte Correos","fields":[{"key":"tipo_correo","label":"Tipo correo","icon":"fas fa-envelope-open-text","type":"text"},{"key":"metodo_envio","label":"Método","icon":"fas fa-paper-plane","type":"method"},{"key":"server","label":"Servidor","icon":"fas fa-server","type":"text"},{"key":"correo","label":"Correo","icon":"fas fa-at","type":"text"},{"key":"port","label":"Puerto","icon":"fas fa-network-wired","type":"text"},{"key":"smtp_secure","label":"SMTP Secure","icon":"fas fa-shield-alt","type":"text"},{"key":"graph_user","label":"Graph User","icon":"fab fa-microsoft","type":"text"}],"actions":[{"label":"Editar","icon":"fas fa-edit","target":"button.table_editar","classes":"accion-editar table_editar ocultar"}],"kpis":[{"id":"total","label":"Configuraciones","desc":"Total de correos configurados","icon":"fas fa-envelope","color":"blue","calc":{"type":"count"}},{"id":"smtp","label":"SMTP","desc":"Configuraciones mediante SMTP","icon":"fas fa-server","color":"teal","calc":{"type":"eqUpper","key":"metodo_envio","value":"SMTP"}},{"id":"graph","label":"Microsoft Graph","desc":"Configuraciones mediante Graph","icon":"fab fa-microsoft","color":"purple","calc":{"type":"eqUpper","key":"metodo_envio","value":"GRAPH"}},{"id":"activos","label":"Activos","desc":"Configuraciones activas","icon":"fas fa-check-circle","color":"green","calc":{"type":"eq","key":"estado","value":"1"}}],"storageView":"izzy.confEmail.tipo_vista"};

var correoModern = {
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

function correoModernEsMovil() {
    return window.matchMedia
        ? window.matchMedia('(max-width: 767.98px)').matches
        : $(window).width() <= 767;
}

function correoModernEscape(value) {
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

function correoModernTexto(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function correoModernEstado(value) {
    return parseInt(value, 10) === 1;
}

function correoModernFormatoCampo(row, field, forExport) {
    var value = row ? row[field.key] : null;
    var text = correoModernTexto(value, 'No registrado');

    if (field.type === 'status') {
        if (forExport) {
            return correoModernEstado(value) ? 'Activo' : 'Inactivo';
        }

        return correoModernEstado(value)
            ? '<span class="correo-config-status-badge correo-config-status-active"><i class="fas fa-check-circle"></i>Activo</span>'
            : '<span class="correo-config-status-badge correo-config-status-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
    }

    if (field.type === 'programType') {
        var programType = String(value || '').toLowerCase();
        var programText = programType === 'monto'
            ? 'Por Monto'
            : (programType === 'porcentaje' ? 'Por Porcentaje' : text);

        return forExport ? programText : correoModernEscape(programText);
    }

    if (field.type === 'money') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return (forExport ? 'L ' : '<strong>L ') + correoModernEscape(text) + (forExport ? '' : '</strong>');
    }

    if (field.type === 'percent') {
        if (text === 'No registrado') {
            return forExport ? '' : '<span>No registrado</span>';
        }

        return correoModernEscape(text) + '%';
    }

    if (field.type === 'method') {
        var method = String(value || 'SMTP').toUpperCase();

        if (forExport) {
            return method;
        }

        return method === 'GRAPH'
            ? '<span class="correo-config-method-badge correo-config-method-graph"><i class="fab fa-microsoft"></i>GRAPH</span>'
            : '<span class="correo-config-method-badge correo-config-method-smtp"><i class="fas fa-server"></i>SMTP</span>';
    }

    if (field.type === 'assigned') {
        var assigned = parseInt(value, 10) || 0;

        if (forExport) {
            return String(assigned);
        }

        return '<span class="correo-config-assigned-badge">' + assigned + ' asignados</span>';
    }

    if (field.type === 'emailTech') {
        var graphUser = correoModernTexto(row.graph_user, 'No configurado');
        var tenant = correoModernTexto(row.tenant_id, 'No configurado');
        var client = correoModernTexto(row.client_id, 'No configurado');
        var sent = parseInt(row.save_to_sent_items || 0, 10) === 1 ? 'Sí' : 'No';

        if (forExport) {
            return 'Graph: ' + graphUser + ' | Tenant: ' + tenant +
                ' | Client: ' + client + ' | Guardar enviados: ' + sent;
        }

        return '<div class="correo-config-tech-lines">' +
            '<span><strong>Graph:</strong> ' + correoModernEscape(graphUser) + '</span>' +
            '<span><strong>Tenant:</strong> ' + correoModernEscape(tenant) + '</span>' +
            '<span><strong>Client:</strong> ' + correoModernEscape(client) + '</span>' +
            '<span><strong>Guardar enviados:</strong> ' + sent + '</span>' +
        '</div>';
    }

    return forExport ? text : correoModernEscape(text);
}

function correoModernClaseIconoAccion(action) {
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

function correoModernAcciones(row) {
    var key = correoModernEscape(correoModernTexto(row[correoModernConfig.key], ''));
    var html = '' +
        '<div class="dropdown acciones-dropdown correo-config-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">';

    correoModernConfig.actions.forEach(function(action) {
        html += '<button type="button" class="dropdown-item accion-item modern-action-bridge ' +
            correoModernEscape(action.classes || '') + '" ' +
            'data-record-key="' + key + '" ' +
            'data-target-selector="' + correoModernEscape(action.target) + '">' +
            '<span class="accion-icon ' + correoModernClaseIconoAccion(action) + '"><i class="' + correoModernEscape(action.icon) + '"></i></span>' +
            '<span class="accion-label">' + correoModernEscape(action.label) + '</span>' +
        '</button>';
    });

    html += '</div></div>';
    return html;
}


function correoModernDetalleTecnico(row) {
    var metodo = String(row && row.metodo_envio ? row.metodo_envio : 'SMTP').toUpperCase();
    var guardar = parseInt(row && row.save_to_sent_items ? row.save_to_sent_items : 0, 10) === 1
        ? 'Sí, guardar copia'
        : 'No guardar copia';

    var items = [
        ['Tipo de correo', correoModernTexto(row.tipo_correo, 'No configurado')],
        ['Método', metodo],
        ['Correo emisor', correoModernTexto(row.correo, 'No configurado')],
        ['Servidor', correoModernTexto(row.server, 'No configurado')],
        ['Puerto', correoModernTexto(row.port, 'No configurado')],
        ['SMTP Secure', correoModernTexto(row.smtp_secure, 'No configurado')],
        ['Graph User', correoModernTexto(row.graph_user, 'No configurado')],
        ['Tenant ID', correoModernTexto(row.tenant_id, 'No configurado')],
        ['Client ID', correoModernTexto(row.client_id, 'No configurado')],
        ['Client Secret', 'Guardado de forma segura'],
        ['Guardar enviados', guardar],
        ['Estado', parseInt(row && row.estado ? row.estado : 0, 10) === 1 ? 'Activo' : 'Inactivo']
    ];

    var html = '' +
        '<div class="correo-config-expanded-card">' +
            '<div class="correo-config-expanded-header">' +
                '<div>' +
                    '<h5><i class="fas fa-info-circle mr-1"></i>Detalle de configuración</h5>' +
                    '<p>Información técnica completa de esta configuración de correo.</p>' +
                '</div>' +
                '<span class="correo-config-method-badge ' +
                    (metodo === 'GRAPH' ? 'correo-config-method-graph' : 'correo-config-method-smtp') + '">' +
                    (metodo === 'GRAPH' ? '<i class="fab fa-microsoft"></i>' : '<i class="fas fa-server"></i>') +
                    correoModernEscape(metodo) +
                '</span>' +
            '</div>' +
            '<div class="correo-config-expanded-grid">';

    items.forEach(function(item) {
        html += '' +
            '<div class="correo-config-expanded-item">' +
                '<span>' + correoModernEscape(item[0]) + '</span>' +
                '<strong>' + correoModernEscape(item[1]) + '</strong>' +
            '</div>';
    });

    html += '</div></div>';
    return html;
}

function correoModernBotonDetalle(row) {
    var key = correoModernEscape(correoModernTexto(row[correoModernConfig.key], ''));

    return '' +
        '<button type="button" class="correo-config-toggle-detail" ' +
            'data-record-key="' + key + '" aria-expanded="false" title="Mostrar detalle">' +
            '<i class="fas fa-plus"></i>' +
        '</button>';
}

function correoModernControlesFila(row) {
    return '<div class="correo-config-row-actions">' +
        correoModernBotonDetalle(row) +
        correoModernAcciones(row) +
    '</div>';
}

function correoModernFiltrar() {
    var q = $.trim(correoModern.search || '').toLowerCase();

    correoModern.filtered = !q
        ? correoModern.rows.slice()
        : correoModern.rows.filter(function(row) {
            return Object.keys(row || {}).map(function(k) {
                var value = row[k];

                if (value === null || value === undefined || typeof value === 'object') {
                    return '';
                }

                return String(value).toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    correoModernActualizarKpis();
}

function correoModernCalcularKpi(calc) {
    if (!calc || calc.type === 'count') {
        return correoModern.filtered.length;
    }

    if (calc.type === 'eq') {
        return correoModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]) === String(calc.value);
        }).length;
    }

    if (calc.type === 'eqUpper') {
        return correoModern.filtered.filter(function(row) {
            return String(row[calc.key] == null ? '' : row[calc.key]).toUpperCase() === String(calc.value).toUpperCase();
        }).length;
    }

    if (calc.type === 'unique') {
        var unique = {};

        correoModern.filtered.forEach(function(row) {
            var value = $.trim(String(row[calc.key] == null ? '' : row[calc.key]));

            if (value) {
                unique[value.toLowerCase()] = true;
            }
        });

        return Object.keys(unique).length;
    }

    if (calc.type === 'sum') {
        return correoModern.filtered.reduce(function(total, row) {
            return total + (parseInt(row[calc.key], 10) || 0);
        }, 0);
    }

    if (calc.type === 'sumkeys') {
        return correoModern.filtered.reduce(function(total, row) {
            return total + (calc.keys || []).reduce(function(subtotal, keyName) {
                return subtotal + (parseInt(row[keyName], 10) || 0);
            }, 0);
        }, 0);
    }

    return 0;
}

function correoModernActualizarKpis() {
    correoModernConfig.kpis.forEach(function(kpi) {
        $('#correo-config-kpi-' + kpi.id).text(correoModernCalcularKpi(kpi.calc));
    });
}

function correoModernRenderDetalle(rows) {
    var html = '<div class="correo-config-detail-header"><div>Acciones</div>';

    correoModernConfig.fields.forEach(function(field) {
        html += '<div>' + correoModernEscape(field.label) + '</div>';
    });

    html += '</div>';

    rows.forEach(function(row) {
        var recordKey = correoModernEscape(correoModernTexto(row[correoModernConfig.key], ''));

        html += '<article class="correo-config-detail-row" data-record-key="' + recordKey + '">' +
            '<div class="correo-config-cell correo-config-actions-cell">' +
                '<span class="correo-config-cell-label">Acciones</span>' +
                correoModernControlesFila(row) +
            '</div>';

        correoModernConfig.fields.forEach(function(field) {
            var contenidoCampo;

            if (field.type === 'status') {
                contenidoCampo = '<div class="correo-config-status-only">' + correoModernFormatoCampo(row, field, false) + '</div>';
            } else if (field.type === 'method') {
                /* El badge de método ya incluye su propio icono; no duplicar iconos. */
                contenidoCampo = '<div class="correo-config-field-main correo-config-field-method">' +
                    '<div class="correo-config-field-value">' + correoModernFormatoCampo(row, field, false) + '</div>' +
                '</div>';
            } else {
                contenidoCampo = '<div class="correo-config-field-main">' +
                    '<i class="' + correoModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    '<div class="correo-config-field-value">' + correoModernFormatoCampo(row, field, false) + '</div>' +
                '</div>';
            }

            html += '<div class="correo-config-cell' + (field.type === 'status' ? ' correo-config-status-cell' : '') + '">' +
                '<span class="correo-config-cell-label">' + correoModernEscape(field.label) + '</span>' +
                '<div class="correo-config-cell-content">' + contenidoCampo + '</div>' +
            '</div>';
        });

        html += '</article>' +
            '<div class="correo-config-expanded-detail" data-detail-for="' + recordKey + '" hidden>' +
                correoModernDetalleTecnico(row) +
            '</div>';
    });

    return html;
}

function correoModernRenderMiniatura(rows) {
    var html = '<div class="correo-config-mini-grid">';
    var primary = correoModernConfig.fields[0];
    var statusField = null;

    correoModernConfig.fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    rows.forEach(function(row) {
        var recordKey = correoModernEscape(correoModernTexto(row[correoModernConfig.key], ''));
        var primaryValue = primary ? correoModernFormatoCampo(row, primary, false) : 'Registro';
        var statusHtml = statusField ? correoModernFormatoCampo(row, statusField, false) : '';

        html += '<article class="correo-config-mini-card" data-record-key="' + recordKey + '">' +
            '<div class="correo-config-mini-topline"></div>' +
            '<div class="correo-config-mini-header">' +
                '<span class="correo-config-mini-icon"><i class="' + correoModernEscape(primary && primary.icon ? primary.icon : 'fas fa-list') + '"></i></span>' +
                '<div class="correo-config-mini-title">' +
                    '<h4>' + primaryValue + '</h4>' +
                    '<span>' + correoModernEscape(correoModernConfig.title) + '</span>' +
                '</div>' +
                (statusHtml ? '<div class="correo-config-mini-status">' + statusHtml + '</div>' : '') +
            '</div>' +
            '<div class="correo-config-mini-body">';

        correoModernConfig.fields.slice(1).forEach(function(field) {
            if (field.type === 'status') {
                return;
            }

            html += '<div class="correo-config-mini-field">' +
                '<span><i class="' + correoModernEscape(field.icon || 'fas fa-info-circle') + '"></i>' +
                    correoModernEscape(field.label) + '</span>' +
                '<div>' + correoModernFormatoCampo(row, field, false) + '</div>' +
            '</div>';
        });

        html += '</div>' +
            '<div class="correo-config-mini-expanded" data-detail-for="' + recordKey + '" hidden>' +
                correoModernDetalleTecnico(row) +
            '</div>' +
            '<div class="correo-config-mini-footer">' + correoModernControlesFila(row) + '</div>' +
        '</article>';
    });

    return html + '</div>';
}

function correoModernRenderPaginacion(totalPages) {
    var current = correoModern.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="correo-config-page-btn' + (active ? ' active' : '') +
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

    $('#correo-config-pagination').html(html);
}

function correoModernAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function correoModernRender() {
    var rows = correoModern.filtered || [];

    if (correoModern.loading) {
        $('#correo-config-listado').html(
            '<div class="correo-config-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando información</strong><span>Espere mientras se consultan los registros...</span></div>'
        );
        $('#correo-config-info').text('0 registros');
        $('#correo-config-pagination').empty();
        return;
    }

    if (!rows.length) {
        $('#correo-config-listado').html(
            '<div class="correo-config-state"><i class="fas fa-inbox"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron resultados con los filtros actuales.</span></div>'
        );
        $('#correo-config-info').text('0 registros');
        $('#correo-config-pagination').empty();
        correoModernAplicarPermisos();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / correoModern.pageSize));

    if (correoModern.page > totalPages) {
        correoModern.page = totalPages;
    }

    var offset = (correoModern.page - 1) * correoModern.pageSize;
    var pageRows = rows.slice(offset, offset + correoModern.pageSize);

    $('#correo-config-listado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + correoModern.view)
        .html(
            correoModern.view === 'miniatura'
                ? correoModernRenderMiniatura(pageRows)
                : correoModernRenderDetalle(pageRows)
        );

    $('#correo-config-info').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    correoModernRenderPaginacion(totalPages);
    correoModernAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function correoModernSyncPageSize() {
    var mini = correoModern.view === 'miniatura';
    var options = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var selected = mini ? correoModern.pageSizeMiniatura : correoModern.pageSizeDetalle;

    if (options.indexOf(selected) === -1) {
        selected = options[0];
    }

    var $select = $('#correo-config-page-size').empty();

    options.forEach(function(value) {
        $select.append($('<option></option>').val(value).text(value));
    });

    correoModern.pageSize = selected;
    $select.val(String(selected));
}

function correoModernSyncView() {
    var mobile = correoModernEsMovil();

    if (mobile) {
        correoModern.view = 'miniatura';
    }

    $('.correo-config-view-btn[data-view="detalle"]')
        .toggleClass('d-none', mobile)
        .prop('disabled', mobile)
        .attr('aria-hidden', mobile ? 'true' : 'false');

    $('.correo-config-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.correo-config-view-btn[data-view="' + correoModern.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function correoModernConfigurarPanel(button, content, storageKey) {
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

    $(button).off('click.correoModernPanel').on('click.correoModernPanel', function() {
        visible = !visible;
        $(content).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (e) {}
    });
}

function correoModernEncontrarFilaEnDataTable(recordKey) {
    if (!$.fn.DataTable.isDataTable("#dataTableConfCorreos")) {
        return null;
    }

    var api = $("#dataTableConfCorreos").DataTable();
    var foundIndex = null;

    api.rows().every(function(index) {
        var row = this.data();

        if (row && String(row["correo_id"]) === String(recordKey)) {
            foundIndex = index;
            return false;
        }
    });

    if (foundIndex === null) {
        return null;
    }

    return api.row(foundIndex);
}

function correoModernEjecutarAccion(recordKey, targetSelector) {
    var row = correoModernEncontrarFilaEnDataTable(recordKey);

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

function correoModernExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function correoModernExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function correoModernExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = correoModernExcelEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function correoModernDescargarBlob(blob, nombreArchivo) {
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

function correoModernFechaArchivo() {
    var fecha = new Date();
    var y = fecha.getFullYear();
    var m = String(fecha.getMonth() + 1).padStart(2, '0');
    var d = String(fecha.getDate()).padStart(2, '0');

    return y + m + d;
}

function correoModernDatosExcel() {
    return (correoModern.filtered || []).map(function(row) {
        return correoModernConfig.fields.map(function(field) {
            return correoModernFormatoCampo(row, field, true);
        });
    });
}

function correoModernGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var fields = correoModernConfig.fields || [];
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
    var lastCol = correoModernExcelColName(headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            correoModernExcelCell(
                'A1',
                'IZZY • ' + String(correoModernConfig.exportTitle || correoModernConfig.title || 'REPORTE').toUpperCase(),
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            correoModernExcelCell(
                'A2',
                'Reporte profesional • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    var summaryLabels = '<row r="3" ht="18" customHeight="1">' +
        correoModernExcelCell('A3', 'REGISTROS', 6, false);

    var summaryValues = '<row r="4" ht="26" customHeight="1">' +
        correoModernExcelCell('A4', rows.length, 7, true);

    if (headers.length >= 2 && statusIndex >= 0) {
        summaryLabels += correoModernExcelCell('B3', 'ACTIVOS', 6, false);
        summaryValues += correoModernExcelCell('B4', totalActivos, 7, true);
    }

    if (headers.length >= 3 && statusIndex >= 0) {
        summaryLabels += correoModernExcelCell('C3', 'INACTIVOS', 6, false);
        summaryValues += correoModernExcelCell('C4', totalInactivos, 7, true);
    }

    summaryLabels += '</row>';
    summaryValues += '</row>';

    sheetRows.push(summaryLabels);
    sheetRows.push(summaryValues);
    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            correoModernExcelCell('A6', 'Detalle de registros filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function(header, index) {
        return correoModernExcelCell(
            correoModernExcelColName(index) + headerRow,
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

            return correoModernExcelCell(
                correoModernExcelColName(colIndex) + excelRow,
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

function correoModernExportarExcel() {
    var rows = correoModernDatosExcel();

    if (!rows.length) {
        if (typeof showNotify === 'function') {
            showNotify('warning', 'Sin datos', 'No hay registros para exportar.');
        }
        return;
    }

    var promesaXlsx = correoModernGenerarXlsx(rows);

    if (!promesaXlsx) {
        if (typeof showNotify === 'function') {
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        }
        return;
    }

    promesaXlsx
        .then(function(blob) {
            var nombre = String(correoModernConfig.exportTitle || correoModernConfig.title || 'Reporte')
                .replace(/[^A-Za-z0-9_-]+/g, '_');

            correoModernDescargarBlob(
                blob,
                nombre + '_' + correoModernFechaArchivo() + '.xlsx'
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

function correoModernPdfObtenerLogo(callback) {
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

function correoModernPdfLogoPlate(logoDataUrl) {
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

function correoModernPdfDetalle(rows) {
    var fields = correoModernConfig.fields;
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
            var value = String(correoModernFormatoCampo(row, field, true) || 'No registrado');
            var cell = { text: value, fillColor: fill };

            if (field.type === 'status') {
                cell.color = correoModernEstado(row[field.key]) ? '#14804A' : '#C9372C';
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

function correoModernPdfTarjeta(row) {
    var fields = correoModernConfig.fields;
    var primary = fields[0];
    var statusField = null;

    fields.some(function(field) {
        if (field.type === 'status') {
            statusField = field;
            return true;
        }
        return false;
    });

    var title = primary ? String(correoModernFormatoCampo(row, primary, true) || 'Registro') : 'Registro';
    var estado = statusField ? String(correoModernFormatoCampo(row, statusField, true) || '') : '';
    var estadoColor = statusField && correoModernEstado(row[statusField.key]) ? '#14804A' : '#C9372C';
    var detailFields = fields.filter(function(field, index) {
        return index !== 0 && field.type !== 'status';
    });
    var detailStack = [];

    detailFields.forEach(function(field, index) {
        detailStack.push({
            margin: [0, index ? 7 : 0, 0, 0],
            stack: [
                { text: String(field.label || '').toUpperCase(), fontSize: 6.4, bold: true, color: '#6B778C', margin: [0, 0, 0, 2] },
                { text: String(correoModernFormatoCampo(row, field, true) || 'No registrado'), fontSize: 8, color: '#172B4D' }
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

function correoModernExportarPdf() {
    var rows = correoModern.filtered || [];

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
        correoModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            correoModernExportarPdf();
        });
        return;
    }

    var busqueda = String($('#correo-config-search').val() || '').trim();
    var filtrosTexto = 'Búsqueda: ' + (busqueda || 'Sin búsqueda');
    var logo = correoModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: correoModernConfig.exportTitle.toUpperCase(), fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: "Configuración de métodos y cuentas de correo", fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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

    var kpis = correoModernConfig.kpis.slice(0, 4);
    var resumen = {
        table: {
            widths: kpis.map(function() { return '*'; }),
            body: [kpis.map(function(kpi) {
                    return {
                        fillColor: '#F7F9FC',
                        margin: [8,7,8,7],
                        stack: [
                            { text: String(kpi.label || '').toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                            { text: String(correoModernCalcularKpi(kpi.calc)), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                        ]
                    };
                })]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenidoVista;
    if (correoModern.view === 'miniatura') {
        contenidoVista = [];
        for (var i = 0; i < rows.length; i += 2) {
            contenidoVista.push({
                columns: [
                    { width: '*', stack: [correoModernPdfTarjeta(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [correoModernPdfTarjeta(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        contenidoVista = [correoModernPdfDetalle(rows)];
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
                { text: 'IZZY • ' + correoModernConfig.title, fontSize: 7, color: '#7A869A' },
                { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
            ] };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            { text: correoModern.view === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE', fontSize: 7, bold: true, color: '#17324D', margin: [0,1,0,7] }
        ].concat(contenidoVista),
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = correoModernConfig.exportTitle.replace(/[^A-Za-z0-9_-]+/g, '_') + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl) {
            abrirModalPdfPublico(dataUrl, correoModernConfig.exportTitle, nombre);
        });
        return;
    }

    if (typeof showNotify === 'function') {
        showNotify('error', 'PDF no disponible', 'La versión de pdfMake no permite previsualización compatible.');
    }
}

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(correoModernConfig.storageView);

        if (savedView === 'miniatura' || savedView === 'detalle') {
            correoModern.preferredView = savedView;
            correoModern.view = savedView;
        }
    } catch (e) {}

    $("#dataTableConfCorreos")
        .off('xhr.dt.correoModern')
        .on('xhr.dt.correoModern', function(e, settings, json) {
            correoModern.rows = json && Array.isArray(json.data) ? json.data : [];
            correoModern.loading = false;
            correoModern.page = 1;
            correoModernFiltrar();
            correoModernRender();
        });

    correoModernSyncView();
    correoModernSyncPageSize();
    correoModernRender();

    if ($('#correo-config-toggle-filtros').length) {
        correoModernConfigurarPanel(
            '#correo-config-toggle-filtros',
            '#correo-config-filtros-contenido',
            'izzy.confEmail.panel.filtros'
        );
    }

    correoModernConfigurarPanel(
        '#correo-config-toggle-kpis',
        '#correo-config-kpis-contenido',
        'izzy.confEmail.panel.kpis'
    );

    $('#correo-config-btn-refresh').off('click.correoModern').on('click.correoModern', function() {
        if (typeof listar_correos_configuracion === 'function') {
            correoModern.loading = true;
            correoModernRender();
            listar_correos_configuracion();
        }
    });

    $('#correo-config-btn-create').off('click.correoModern').on('click.correoModern', function() {
        if (typeof modalDestinatarios === 'function') {
            modalDestinatarios();
        }
    });

    $('#correo-config-btn-excel').off('click.correoModern').on('click.correoModern', correoModernExportarExcel);
    $('#correo-config-btn-pdf').off('click.correoModern').on('click.correoModern', correoModernExportarPdf);

    $('#correo-config-page-size').off('change.correoModern').on('change.correoModern', function() {
        var value = parseInt($(this).val(), 10) || 10;

        if (correoModern.view === 'miniatura') {
            correoModern.pageSizeMiniatura = value;
        } else {
            correoModern.pageSizeDetalle = value;
        }

        correoModern.pageSize = value;
        correoModern.page = 1;
        correoModernRender();
    });

    $('.correo-config-view-btn').off('click.correoModern').on('click.correoModern', function() {
        var next = $(this).data('view');

        if (next !== 'detalle' && next !== 'miniatura') {
            return;
        }

        if (next === 'detalle' && correoModernEsMovil()) {
            return;
        }

        correoModern.view = next;
        correoModern.page = 1;

        if (!correoModernEsMovil()) {
            correoModern.preferredView = next;

            try {
                localStorage.setItem(correoModernConfig.storageView, correoModern.preferredView);
            } catch (e) {}
        }

        correoModernSyncView();
        correoModernSyncPageSize();
        correoModernRender();
    });

    $('#correo-config-search').off('input.correoModern').on('input.correoModern', function() {
        correoModern.search = $(this).val() || '';
        correoModern.page = 1;
        correoModernFiltrar();
        correoModernRender();
    });

    $('#correo-config-search-clear').off('click.correoModern').on('click.correoModern', function() {
        $('#correo-config-search').val('').focus();
        correoModern.search = '';
        correoModern.page = 1;
        correoModernFiltrar();
        correoModernRender();
    });

    $('#correo-config-pagination')
        .off('click.correoModern', '.correo-config-page-btn')
        .on('click.correoModern', '.correo-config-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                correoModern.page = page;
                correoModernRender();

                var target = document.querySelector('.correo-config-list-card');

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#correo-config-listado')
        .off('click.correoModern', '.modern-action-bridge')
        .on('click.correoModern', '.modern-action-bridge', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            correoModernEjecutarAccion(
                $button.attr('data-record-key'),
                $button.attr('data-target-selector')
            );
        });

    $('#correo-config-listado')
        .off('click.correoModernDropdown', '.js-acciones-toggle')
        .on('click.correoModernDropdown', '.js-acciones-toggle', function() {
            $('.correo-config-detail-row, .correo-config-mini-card').removeClass('modern-dropdown-open');
            $(this).closest('.correo-config-detail-row, .correo-config-mini-card').addClass('modern-dropdown-open');
        });

    $(document)
        .off('click.correoModernDropdownClose')
        .on('click.correoModernDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.correo-config-detail-row, .correo-config-mini-card').removeClass('modern-dropdown-open');
            }
        });

    $(window)
        .off('resize.correoModern')
        .on('resize.correoModern', function() {
            var previous = correoModern.view;

            if (correoModernEsMovil()) {
                correoModern.view = 'miniatura';
            } else {
                correoModern.view = correoModern.preferredView;
            }

            if (previous !== correoModern.view) {
                correoModern.page = 1;
                correoModernSyncPageSize();
            }

            correoModernSyncView();
            correoModernRender();
        });
});



/* =========================================================
   DETALLE EXPANDIBLE (+) - CONSERVA FUNCIONALIDAD ORIGINAL
   ========================================================= */
$(document)
    .off('click.correoModernDetalle', '#correo-config-listado .correo-config-toggle-detail')
    .on('click.correoModernDetalle', '#correo-config-listado .correo-config-toggle-detail', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        var key = String($button.data('record-key') || '');
        var $scope = $button.closest('.correo-config-mini-card');

        var $detail = $scope.length
            ? $scope.find('.correo-config-mini-expanded[data-detail-for="' + key + '"]').first()
            : $('#correo-config-listado .correo-config-expanded-detail[data-detail-for="' + key + '"]').first();

        if (!$detail.length) {
            return;
        }

        var open = !$detail.prop('hidden');

        if (open) {
            $detail.stop(true, true).slideUp(140, function() {
                $detail.prop('hidden', true).removeAttr('style');
            });
        } else {
            $detail.prop('hidden', false).hide().stop(true, true).slideDown(160);
        }

        $button
            .toggleClass('abierto', !open)
            .attr('aria-expanded', !open ? 'true' : 'false')
            .attr('title', !open ? 'Ocultar detalle' : 'Mostrar detalle');

        $button.find('i')
            .toggleClass('fa-plus', open)
            .toggleClass('fa-minus', !open);
    });

/* =========================================================
   INICIALIZACIÓN DEL MÓDULO - SIN $(document).ready()
   ========================================================= */
function inicializarModuloCorreos() {
    listar_correos_configuracion();
    getSMTPSecure();
    getTipoCorreo();

    $('#formConfEmails #metodoEnvioConfEmail').off('change.confEmailMetodo');
    $('#formConfEmails #metodoEnvioConfEmail').on('change.confEmailMetodo', function() {
        aplicarVistaMetodoCorreo();
    });

    $("#modalRegistrarDestinatarios").off('shown.bs.modal');
    $("#modalRegistrarDestinatarios").on('shown.bs.modal', function() {
        $(this).find('#formDestinatarios #correo').focus();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarModuloCorreos);
} else {
    inicializarModuloCorreos();
}

/* =========================================================
   HEADER DINÁMICO - CORREOS
   ========================================================= */
function construirHeaderDataTableConfCorreos() {
    var $tabla = $("#dataTableConfCorreos");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Tipo Correo</th>' +
                '<th>Método</th>' +
                '<th>Servidor</th>' +
                '<th>Correo</th>' +
                '<th>Puerto</th>' +
                '<th>SMTP Secure</th>' +
                '<th>Graph User</th>' +
            '</tr>' +
        '</thead>'
    );
}

/* =========================================================
   MOSTRAR / OCULTAR CAMPOS SEGÚN MÉTODO
   ========================================================= */
function aplicarVistaMetodoCorreo() {
    var metodo = ($('#formConfEmails #metodoEnvioConfEmail').val() || 'SMTP').toUpperCase();

    if (metodo === 'GRAPH') {
        $('.seccion-smtp').hide();
        $('.seccion-graph').show();

        $('.ayuda-smtp').hide();
        $('.ayuda-graph').show();

        $('#formConfEmails #serverConfEmail').prop('required', false);
        $('#formConfEmails #passConfEmail').prop('required', false);
        $('#formConfEmails #puertoConfEmail').prop('required', false);
        $('#formConfEmails #smtpSecureConfEmail').prop('required', false);

        $('#formConfEmails #tenantIdConfEmail').prop('required', true);
        $('#formConfEmails #clientIdConfEmail').prop('required', true);
        $('#formConfEmails #graphUserConfEmail').prop('required', true);
    } else {
        $('.seccion-smtp').show();
        $('.seccion-graph').hide();

        $('.ayuda-smtp').show();
        $('.ayuda-graph').hide();

        $('#formConfEmails #serverConfEmail').prop('required', true);
        $('#formConfEmails #puertoConfEmail').prop('required', true);
        $('#formConfEmails #smtpSecureConfEmail').prop('required', true);

        $('#formConfEmails #tenantIdConfEmail').prop('required', false);
        $('#formConfEmails #clientIdConfEmail').prop('required', false);
        $('#formConfEmails #clientSecretConfEmail').prop('required', false);
        $('#formConfEmails #graphUserConfEmail').prop('required', false);
    }

    $('#formConfEmails #metodoEnvioConfEmail').trigger('change.select2');
    $('#formConfEmails #smtpSecureConfEmail').trigger('change.select2');
    $('#formConfEmails #saveToSentItemsConfEmail').trigger('change.select2');
}

/* =========================================================
   ALERTA Y PERMISOS DEL MODAL
   ========================================================= */
function insertarAlertaSeguridadCorreo(puedeEditar) {
    $('#alertaCorreoSeguridad').remove();

    var claseModo = puedeEditar ? '' : ' modo-lectura';
    var titulo = puedeEditar ? 'Configuración sensible de correo' : 'Modo solo lectura';
    var texto = puedeEditar
        ? 'Tenant ID y Client ID se muestran parcialmente por seguridad. El Client Secret VALUE y la contraseña SMTP nunca se muestran. Si desea reemplazarlos, escriba un valor nuevo completo.'
        : 'Esta configuración controla el envío de facturas, notificaciones, recuperación de contraseña e inicios de sesión. Su usuario puede ver esta pantalla, pero no tiene permisos para modificarla.';

    var alerta = '' +
        '<div id="alertaCorreoSeguridad" class="alerta-correo-seguridad' + claseModo + '">' +
            '<div class="alerta-icono">' +
                '<i class="fas fa-shield-alt"></i>' +
            '</div>' +
            '<div class="alerta-contenido">' +
                '<h6>' + titulo + '</h6>' +
                '<p>' + texto + '</p>' +
            '</div>' +
        '</div>';

    $('#formConfEmails').prepend(alerta);
}

function aplicarPermisosFormularioCorreo(puedeEditar) {
    puedeEditar = puedeEditar === true || puedeEditar === 1 || puedeEditar === '1';

    insertarAlertaSeguridadCorreo(puedeEditar);

    var $form = $('#formConfEmails');

    $form.find('input, textarea').removeClass('campo-solo-lectura');
    $form.find('select').prop('disabled', false).trigger('change.select2');

    if (puedeEditar) {
        $form.find('input, textarea').prop('readonly', false);
        $('#formConfEmails #tipo_correo_confEmail').prop('disabled', true).trigger('change.select2');

        $('#test_confEmails').show();
        $('#edi_confEmails').show();
    } else {
        $form.find('input, textarea').prop('readonly', true).addClass('campo-solo-lectura');
        $form.find('select').prop('disabled', true).trigger('change.select2');

        $('#test_confEmails').hide();
        $('#edi_confEmails').hide();
    }

    /*
        Estos secretos nunca se muestran.
        Para administradores quedan editables para reemplazo.
        Para usuarios normales quedan solo lectura.
    */
    $('#formConfEmails #passConfEmail').val('');
    $('#formConfEmails #clientSecretConfEmail').val('');

    if (puedeEditar) {
        $('#formConfEmails #passConfEmail').prop('readonly', false).removeClass('campo-solo-lectura');
        $('#formConfEmails #clientSecretConfEmail').prop('readonly', false).removeClass('campo-solo-lectura');
    }
}

/* =========================================================
   UTILIDADES DE PRESENTACIÓN
   ========================================================= */
function textoSeguroCorreo(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return 'No configurado';
    }

    return valor;
}

function formatoDetalleCorreo(row) {
    var metodo = (row.metodo_envio || 'SMTP').toUpperCase();
    var badgeMetodo = metodo === 'GRAPH' ? 'badge-metodo-graph' : 'badge-metodo-smtp';
    var guardar = parseInt(row.save_to_sent_items || 0) === 1 ? 'Sí, guardar copia' : 'No guardar copia';

    return '' +
        '<div class="correo-detalle-premium">' +
            '<div class="correo-detalle-header">' +
                '<div>' +
                    '<h5 class="correo-detalle-title"><i class="fas fa-info-circle mr-1"></i>Detalle de configuración</h5>' +
                    '<p class="correo-detalle-subtitle">Información técnica del correo sin ampliar el tamaño de la tabla principal</p>' +
                '</div>' +
                '<span class="' + badgeMetodo + '">' + metodo + '</span>' +
            '</div>' +

            '<div class="correo-detalle-grid">' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Tipo de correo</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.tipo_correo) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Correo emisor</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.correo) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Servidor</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.server) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Puerto</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.port) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">SMTP Secure</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.smtp_secure) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Graph User</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.graph_user) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Tenant ID</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.tenant_id) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Client ID</div>' +
                    '<div class="correo-detalle-value">' + textoSeguroCorreo(row.client_id) + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Client Secret</div>' +
                    '<div class="correo-detalle-value">Guardado de forma segura</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Guardar enviados</div>' +
                    '<div class="correo-detalle-value">' + guardar + '</div>' +
                '</div>' +

                '<div class="correo-detalle-item">' +
                    '<div class="correo-detalle-label">Estado</div>' +
                    '<div class="correo-detalle-value">' + (parseInt(row.estado || 0) === 1 ? 'Activo' : 'Inactivo') + '</div>' +
                '</div>' +

            '</div>' +
        '</div>';
}

/* =========================================================
   LISTAR CORREOS
   ========================================================= */
var listar_correos_configuracion = function() {

    if ($.fn.DataTable.isDataTable("#dataTableConfCorreos")) {
        $("#dataTableConfCorreos").DataTable().clear().destroy();
    }

    construirHeaderDataTableConfCorreos();

    var table_correos_configuracion = $("#dataTableConfCorreos").DataTable({
        "destroy": true,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/correo/llenarDataTableConfCorreos.php"
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
                        '<div class="correo-acciones-wrap">' +

                            '<button type="button" class="btn-toggle-detalle-correo table_toggle_detalle" title="Mostrar detalle">' +
                                '<i class="fas fa-plus"></i>' +
                            '</button>' +

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
                                '</div>' +
                            '</div>' +

                        '</div>';
                }
            },
            { "data": "tipo_correo" },
            {
                "data": "metodo_envio",
                "className": "text-center text-nowrap",
                "render": function(data, type, row) {
                    var metodo = (data || 'SMTP').toUpperCase();

                    if (metodo === 'GRAPH') {
                        return '<span class="badge badge-success">GRAPH</span>';
                    }

                    return '<span class="badge badge-info">SMTP</span>';
                }
            },
            { "data": "server" },
            { "data": "correo" },
            {
                "data": "port",
                "className": "text-center text-nowrap"
            },
            {
                "data": "smtp_secure",
                "className": "text-center text-nowrap"
            },
            { "data": "graph_user" }
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
            { width: "14%", targets: 1 },
            { width: "10%", targets: 2 },
            { width: "15%", targets: 3 },
            { width: "20%", targets: 4 },
            { width: "8%", targets: 5 },
            { width: "10%", targets: 6 },
            { width: "20%", targets: 7 }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Correos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_correos_configuracion();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg"></i> Registrar Destinatarios',
                titleAttr: 'Agregar Correos para enviar notificaciones',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modalDestinatarios();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte Correos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                title: 'Reporte Correos',
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
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

    table_correos_configuracion.search('').page.len(-1).draw(false);
    $('#buscar').focus();

    toggle_detalle_correos_configuracion_dataTable("#dataTableConfCorreos tbody", table_correos_configuracion);
    edit_correos_configuracion_dataTable("#dataTableConfCorreos tbody", table_correos_configuracion);
}

/* =========================================================
   TOGGLE DETALLE CORREO
   ========================================================= */
var toggle_detalle_correos_configuracion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_toggle_detalle");

    $(tbody).on("click", "button.table_toggle_detalle", function(e) {
        e.preventDefault();

        var boton = $(this);
        var tr = boton.closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');

            boton.removeClass('abierto');
            boton.attr('title', 'Mostrar detalle');
            boton.find('i').removeClass('fa-minus').addClass('fa-plus');
        } else {
            row.child(formatoDetalleCorreo(row.data())).show();
            tr.addClass('shown');

            boton.addClass('abierto');
            boton.attr('title', 'Ocultar detalle');
            boton.find('i').removeClass('fa-plus').addClass('fa-minus');
        }
    });
}

/* =========================================================
   EDITAR CORREO
   ========================================================= */
var edit_correos_configuracion_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_editar");

    $(tbody).on("click", "button.table_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/correo/editarCorreo.php';

        $('#formConfEmails #correo_id').val(data.correo_id);

        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            data: {
                correo_id: data.correo_id
            },
            success: function(valores) {
                if (!valores || valores.success === false) {
                    showNotify('error', 'Error', valores.message || 'No se pudo cargar la configuración del correo');
                    return;
                }

                $('#formConfEmails').attr({
                    'data-form': 'update',
                    'action': '<?php echo SERVERURL;?>ajax/modificarCorreoAjax.php'
                });

                $('#formConfEmails')[0].reset();

                $('#formConfEmails #correo_id').val(valores.correo_id);

                $('#formConfEmails #tipo_correo_confEmail').val(valores.correo_tipo_id);
                $('#formConfEmails #tipo_correo_confEmail').trigger('change.select2');

                $('#formConfEmails #metodoEnvioConfEmail').val(valores.metodo_envio || 'SMTP');
                $('#formConfEmails #metodoEnvioConfEmail').trigger('change.select2');

                $('#formConfEmails #serverConfEmail').val(valores.server || '');
                $('#formConfEmails #correoConfEmail').val(valores.correo || '');
                $('#formConfEmails #passConfEmail').val('');
                $('#formConfEmails #puertoConfEmail').val(valores.port || '');

                $('#formConfEmails #smtpSecureConfEmail').val(valores.smtp_secure || '');
                $('#formConfEmails #smtpSecureConfEmail').trigger('change.select2');

                $('#formConfEmails #tenantIdConfEmail').val(valores.tenant_id || '');
                $('#formConfEmails #clientIdConfEmail').val(valores.client_id || '');
                $('#formConfEmails #clientSecretConfEmail').val('');
                $('#formConfEmails #graphUserConfEmail').val(valores.graph_user || '');

                $('#formConfEmails #saveToSentItemsConfEmail').val(valores.save_to_sent_items || '1');
                $('#formConfEmails #saveToSentItemsConfEmail').trigger('change.select2');

                aplicarVistaMetodoCorreo();
                aplicarPermisosFormularioCorreo(valores.puede_editar);

                $('#modalConfEmails').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            },
            error: function() {
                showNotify('error', 'Error', 'No se pudo consultar la configuración del correo');
            }
        });
    });
}

/* =========================================================
   TEST CORREO
   ========================================================= */
$("#test_confEmails").off("click");
$("#test_confEmails").on("click", function(e) {
    e.preventDefault();
    testEmail();
});

function testEmail() {
    var url = '<?php echo SERVERURL;?>core/correo/testEmail.php';

    /*
        No usamos serialize() porque los campos disabled no se envían.
        Aquí mandamos los valores manualmente para asegurar que el método GRAPH/SMTP llegue siempre.
    */
    var datosTest = {
        correo_id: $('#formConfEmails #correo_id').val(),

        metodoEnvioConfEmail: $('#formConfEmails #metodoEnvioConfEmail').val() || 'SMTP',

        serverConfEmail: $('#formConfEmails #serverConfEmail').val() || '',
        correoConfEmail: $('#formConfEmails #correoConfEmail').val() || '',
        passConfEmail: $('#formConfEmails #passConfEmail').val() || '',
        puertoConfEmail: $('#formConfEmails #puertoConfEmail').val() || '',
        smtpSecureConfEmail: $('#formConfEmails #smtpSecureConfEmail').val() || '',

        tenantIdConfEmail: $('#formConfEmails #tenantIdConfEmail').val() || '',
        clientIdConfEmail: $('#formConfEmails #clientIdConfEmail').val() || '',
        clientSecretConfEmail: $('#formConfEmails #clientSecretConfEmail').val() || '',
        graphUserConfEmail: $('#formConfEmails #graphUserConfEmail').val() || '',
        saveToSentItemsConfEmail: $('#formConfEmails #saveToSentItemsConfEmail').val() || '1'
    };

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: datosTest,
        success: function(data) {
            data = $.trim(data);

            if (data == 1) {
                showNotify('success', 'Success', 'Conexión realizada satisfactoriamente');
            } else {
                showNotify('error', 'Error', data || 'No se pudo realizar la conexión. Verifique la configuración.');
            }
        },
        error: function() {
            showNotify('error', 'Error', 'No se pudo ejecutar la prueba de conexión');
        }
    });
}

/* =========================================================
   CARGAR SMTP SECURE
   ========================================================= */
function getSMTPSecure() {
    var url = '<?php echo SERVERURL;?>core/getSMTPSecure.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formConfEmails #smtpSecureConfEmail').html("");
            $('#formConfEmails #smtpSecureConfEmail').html(data);
            $('#formConfEmails #smtpSecureConfEmail').trigger('change.select2');
        }
    });
}

/* =========================================================
   CARGAR TIPO DE CORREO
   ========================================================= */
function getTipoCorreo() {
    var url = '<?php echo SERVERURL;?>core/getTipoCorreo.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formConfEmails #tipo_correo_confEmail').html("");
            $('#formConfEmails #tipo_correo_confEmail').html(data);
            $('#formConfEmails #tipo_correo_confEmail').trigger('change.select2');
        }
    });
}

/* =========================================================
   DESTINATARIOS DE NOTIFICACIONES
   Misma dinámica visual del resto de IZZY 6.0.
   ========================================================= */

var destinatariosState = {
    registros: [],
    filtrados: [],
    pagina: 1,
    porPagina: 10,
    porPaginaDetalle: 10,
    porPaginaMiniatura: 6,
    vista: 'detalle',
    vistaPreferida: 'detalle',
    busqueda: '',
    loading: false
};

var DESTINATARIOS_STORAGE_VISTA = 'izzy.confEmail.destinatarios.vista';
var DESTINATARIOS_MOBILE_QUERY = '(max-width: 767.98px)';

function destinatariosEsMovil() {
    return window.matchMedia
        ? window.matchMedia(DESTINATARIOS_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function destinatariosEscape(value) {
    return correoModernEscape(value);
}

function destinatariosExtraerRegistros(response) {
    if (Array.isArray(response)) {
        return response;
    }

    if (response && Array.isArray(response.data)) {
        return response.data;
    }

    if (response && Array.isArray(response.aaData)) {
        return response.aaData;
    }

    return [];
}

function destinatariosSincronizarVista() {
    var movil = destinatariosEsMovil();

    destinatariosState.vista = movil
        ? 'miniatura'
        : destinatariosState.vistaPreferida;

    $('.destinatarios-view-btn[data-view="detalle"]')
        .prop('disabled', movil)
        .toggleClass('d-none', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.destinatarios-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.destinatarios-view-btn[data-view="' + destinatariosState.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function destinatariosSincronizarPageSize() {
    var mini = destinatariosState.vista === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var seleccionado = mini
        ? destinatariosState.porPaginaMiniatura
        : destinatariosState.porPaginaDetalle;

    if (opciones.indexOf(seleccionado) === -1) {
        seleccionado = opciones[0];
    }

    var $select = $('#destinatariosPageSize').empty();

    opciones.forEach(function(value) {
        $select.append(
            $('<option></option>')
                .attr('value', value)
                .text(value)
        );
    });

    destinatariosState.porPagina = seleccionado;
    $select.val(String(seleccionado));
}

function destinatariosAplicarFiltro() {
    var q = String(destinatariosState.busqueda || '').trim().toLowerCase();

    destinatariosState.filtrados = !q
        ? destinatariosState.registros.slice()
        : destinatariosState.registros.filter(function(item) {
            return [
                item.nombre,
                item.correo
            ].join(' ').toLowerCase().indexOf(q) !== -1;
        });

    destinatariosState.pagina = 1;
    renderDestinatarios();
}

function destinatariosAcciones(item) {
    return '' +
        '<div class="dropdown acciones-dropdown destinatarios-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle dropdown-toggle" data-toggle="dropdown">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar destinatario-eliminar" ' +
                    'data-id="' + destinatariosEscape(item.notificaciones_id) + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function construirDestinatarioDetalle(item) {
    return '' +
        '<article class="destinatarios-detail-row" data-id="' + destinatariosEscape(item.notificaciones_id) + '">' +
            '<div class="destinatarios-detail-cell destinatarios-actions-cell">' +
                '<span class="destinatarios-cell-label">Acciones</span>' +
                destinatariosAcciones(item) +
            '</div>' +
            '<div class="destinatarios-detail-cell">' +
                '<span class="destinatarios-cell-label">Correo</span>' +
                '<div class="destinatarios-value"><i class="fas fa-envelope"></i><span>' +
                    destinatariosEscape(item.correo || 'No registrado') +
                '</span></div>' +
            '</div>' +
            '<div class="destinatarios-detail-cell">' +
                '<span class="destinatarios-cell-label">Nombre</span>' +
                '<div class="destinatarios-value"><i class="fas fa-user"></i><span>' +
                    destinatariosEscape(item.nombre || 'No registrado') +
                '</span></div>' +
            '</div>' +
        '</article>';
}

function construirDestinatarioMiniatura(item) {
    return '' +
        '<article class="destinatarios-mini-card" data-id="' + destinatariosEscape(item.notificaciones_id) + '">' +
            '<div class="destinatarios-mini-topline"></div>' +
            '<div class="destinatarios-mini-header">' +
                '<span class="destinatarios-mini-icon"><i class="fas fa-user-envelope"></i></span>' +
                '<div class="destinatarios-mini-title">' +
                    '<h4>' + destinatariosEscape(item.nombre || 'Sin nombre') + '</h4>' +
                    '<span>Destinatario de notificaciones</span>' +
                '</div>' +
            '</div>' +
            '<div class="destinatarios-mini-body">' +
                '<div class="destinatarios-mini-field">' +
                    '<span><i class="fas fa-envelope"></i>Correo electrónico</span>' +
                    '<strong>' + destinatariosEscape(item.correo || 'No registrado') + '</strong>' +
                '</div>' +
            '</div>' +
            '<div class="destinatarios-mini-footer">' +
                destinatariosAcciones(item) +
            '</div>' +
        '</article>';
}

function renderPaginacionDestinatarios(totalPaginas) {
    var actual = destinatariosState.pagina;
    var html = '';

    function item(texto, destino, deshabilitado, activo, icono) {
        return '<button type="button" class="destinatarios-page-btn' +
            (activo ? ' active' : '') + '" data-page="' + destino + '"' +
            (deshabilitado ? ' disabled' : '') + '>' +
            (icono ? '<i class="' + icono + '"></i>' : '') +
            '<span>' + texto + '</span>' +
        '</button>';
    }

    html += item('Inicio', 1, actual === 1, false, 'fas fa-angle-double-left');
    html += item('Anterior', actual - 1, actual === 1, false, 'fas fa-angle-left');

    var desde = Math.max(1, actual - 2);
    var hasta = Math.min(totalPaginas, desde + 4);
    desde = Math.max(1, hasta - 4);

    for (var pagina = desde; pagina <= hasta; pagina++) {
        html += item(String(pagina), pagina, false, pagina === actual, '');
    }

    html += item('Siguiente', actual + 1, actual === totalPaginas, false, 'fas fa-angle-right');
    html += item('Final', totalPaginas, actual === totalPaginas, false, 'fas fa-angle-double-right');

    $('#destinatariosPaginacion').html(html);
}

function renderDestinatarios() {
    var rows = destinatariosState.filtrados || [];
    var $listado = $('#destinatariosListado');

    if (!$listado.length) {
        return;
    }

    if (destinatariosState.loading) {
        $listado.html(
            '<div class="destinatarios-state-box">' +
                '<i class="fas fa-spinner fa-spin"></i>' +
                '<div><strong>Cargando destinatarios...</strong><small>Espere un momento.</small></div>' +
            '</div>'
        );
        $('#destinatariosInfo').text('0 registros');
        $('#destinatariosPaginacion').empty();
        return;
    }

    if (!rows.length) {
        $listado.html(
            '<div class="destinatarios-state-box">' +
                '<i class="fas fa-envelope-open"></i>' +
                '<div><strong>Sin destinatarios</strong><small>No hay registros con la búsqueda actual.</small></div>' +
            '</div>'
        );
        $('#destinatariosInfo').text('Mostrando 0 registros');
        $('#destinatariosPaginacion').empty();
        return;
    }

    var totalPaginas = Math.max(1, Math.ceil(rows.length / destinatariosState.porPagina));

    if (destinatariosState.pagina > totalPaginas) {
        destinatariosState.pagina = totalPaginas;
    }

    var inicio = (destinatariosState.pagina - 1) * destinatariosState.porPagina;
    var fin = Math.min(inicio + destinatariosState.porPagina, rows.length);
    var paginaRows = rows.slice(inicio, fin);
    var html = '';

    $listado
        .toggleClass('vista-detalle', destinatariosState.vista === 'detalle')
        .toggleClass('vista-miniatura', destinatariosState.vista === 'miniatura');

    if (destinatariosState.vista === 'detalle') {
        html += '' +
            '<div class="destinatarios-detail-header">' +
                '<div>Acciones</div>' +
                '<div>Correo</div>' +
                '<div>Nombre</div>' +
            '</div>';

        paginaRows.forEach(function(item) {
            html += construirDestinatarioDetalle(item);
        });
    } else {
        html += '<div class="destinatarios-mini-grid">';

        paginaRows.forEach(function(item) {
            html += construirDestinatarioMiniatura(item);
        });

        html += '</div>';
    }

    $listado.html(html);

    $('#destinatariosInfo').text(
        'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + rows.length + ' registros'
    );

    renderPaginacionDestinatarios(totalPaginas);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

var listar_destinatarios = function() {
    destinatariosState.loading = true;
    renderDestinatarios();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableDestinatarios.php',
        dataType: 'json',
        cache: false
    })
    .done(function(response) {
        destinatariosState.registros = destinatariosExtraerRegistros(response);
        destinatariosState.filtrados = destinatariosState.registros.slice();
        destinatariosState.loading = false;
        destinatariosState.pagina = 1;
        destinatariosState.busqueda = $('#buscarDestinatarioListado').val() || '';
        destinatariosAplicarFiltro();
    })
    .fail(function(xhr) {
        destinatariosState.registros = [];
        destinatariosState.filtrados = [];
        destinatariosState.loading = false;
        renderDestinatarios();

        showNotify(
            'error',
            'Error',
            'No se pudo cargar el listado de destinatarios.'
        );

        console.error('Error cargando destinatarios:', xhr.responseText);
    });
};

function modalDestinatarios() {
    if (!$('#modalRegistrarDestinatarios').length || !$('#formDestinatarios').length || !$('#destinatariosListado').length) {
        showNotify(
            'error',
            'Modal no disponible',
            'No se encontró el formulario para administrar destinatarios.'
        );
        return;
    }

    $('#formDestinatarios').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL;?>ajax/addDestinatario.php'
    });

    $('#formDestinatarios').get(0).reset();
    $('#reg_destinatarios').show();

    $('#formDestinatarios #correo').prop('readonly', false);
    $('#formDestinatarios #nombre').prop('readonly', false);
    $('#formDestinatarios #proceso_destinatarios').val('Registro Destinatarios');

    try {
        var guardada = localStorage.getItem(DESTINATARIOS_STORAGE_VISTA);

        if (guardada === 'miniatura' || guardada === 'detalle') {
            destinatariosState.vistaPreferida = guardada;
        }
    } catch (e) {}

    destinatariosSincronizarVista();
    destinatariosSincronizarPageSize();

    $('#modalRegistrarDestinatarios')
        .off('shown.bs.modal.cargarDestinatarios')
        .one('shown.bs.modal.cargarDestinatarios', function() {
            listar_destinatarios();
            $(this).find('#formDestinatarios #correo').trigger('focus');
        })
        .modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
}

function destinatariosEliminarPorId(id) {
    var item = destinatariosState.registros.find(function(row) {
        return String(row.notificaciones_id) === String(id);
    });

    if (!item) {
        showNotify('error', 'Error', 'No fue posible identificar el destinatario.');
        return;
    }

    if (typeof swal !== 'function') {
        showNotify('error', 'Confirmación no disponible', 'No se encontró swal cargado en la plantilla.');
        return;
    }

    var mensajeHTML = '¿Desea eliminar permanentemente este destinatario?<br><br>' +
        '<strong>Nombre:</strong> ' + destinatariosEscape(item.nombre || 'No registrado') + '<br>' +
        '<strong>Correo:</strong> ' + destinatariosEscape(item.correo || 'No registrado');

    swal({
        title: 'Confirmar eliminación',
        content: {
            element: 'span',
            attributes: {
                innerHTML: mensajeHTML
            }
        },
        icon: 'warning',
        buttons: {
            cancel: {
                text: 'Cancelar',
                value: null,
                visible: true,
                className: 'btn-light'
            },
            confirm: {
                text: 'Sí, eliminar',
                value: true,
                className: 'btn-danger',
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then(function(confirmar) {
        if (confirmar !== true) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/deleteDestinatarios.php',
            data: {
                notificaciones_id: item.notificaciones_id
            }
        })
        .done(function(response) {
            if (typeof swal.close === 'function') {
                swal.close();
            }

            if ($.trim(String(response)) === '1') {
                showNotify('success', 'Éxito', 'El destinatario ha sido eliminado correctamente.');
                listar_destinatarios();
                $('#formDestinatarios #correo').trigger('focus');
            } else {
                showNotify('error', 'Error', 'No se pudo eliminar el destinatario.');
            }
        })
        .fail(function(xhr) {
            if (typeof swal.close === 'function') {
                swal.close();
            }
            showNotify('error', 'Error', 'Ocurrió un error al eliminar el destinatario.');
            console.error('Error eliminando destinatario:', xhr.responseText);
        });
    });
}

function elminarDestinatario(notificaciones_id) {
    destinatariosEliminarPorId(notificaciones_id);
}

var eliminar_destinatarios_dataTable = function() {
    /* Compatibilidad: el listado ya no depende de DataTables. */
};

function destinatariosDatosExportar() {
    return (destinatariosState.filtrados || []).map(function(item) {
        return [
            item.correo || '',
            item.nombre || ''
        ];
    });
}

function destinatariosGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var headers = ['Correo', 'Nombre'];
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            correoModernExcelCell('A1', 'IZZY • REPORTE DE DESTINATARIOS', 1, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            correoModernExcelCell(
                'A2',
                'Destinatarios internos de notificaciones • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3" ht="18" customHeight="1">' +
            correoModernExcelCell('A3', 'REGISTROS', 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="26" customHeight="1">' +
            correoModernExcelCell('A4', rows.length, 7, true) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            correoModernExcelCell('A6', 'Detalle de destinatarios', 8, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="7" ht="26" customHeight="1">' +
            correoModernExcelCell('A7', headers[0], 3, false) +
            correoModernExcelCell('B7', headers[1], 3, false) +
        '</row>'
    );

    rows.forEach(function(row, index) {
        var r = firstDataRow + index;

        sheetRows.push(
            '<row r="' + r + '" ht="22" customHeight="1">' +
                correoModernExcelCell('A' + r, row[0], 4, false) +
                correoModernExcelCell('B' + r, row[1], 4, false) +
            '</row>'
        );
    });

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:B' + lastRow + '"/>' +
            '<sheetViews>' +
                '<sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView>' +
            '</sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' +
                '<col min="1" max="1" width="42" customWidth="1"/>' +
                '<col min="2" max="2" width="30" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A7:B' + lastRow + '"/>' +
            '<mergeCells count="2">' +
                '<mergeCell ref="A1:B1"/>' +
                '<mergeCell ref="A2:B2"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    /* Libro XLSX de destinatarios con la misma estructura estable usada en Usuarios. */
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
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"/>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0"/>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0"/>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0"/>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0"/>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="Destinatarios" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels =
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

    var contentTypes =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
    zip.folder('xl').file('styles.xml', stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels', workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml', window.izzyExcelCompletarBordesCombinados(sheetXml));

    var options = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    if (typeof zip.generateAsync === 'function') {
        return zip.generateAsync(options);
    }

    if (typeof zip.generate === 'function') {
        try {
            return Promise.resolve(zip.generate(options));
        } catch (error) {
            return Promise.reject(error);
        }
    }

    return Promise.reject(new Error('JSZip no soportado.'));
}

function exportarDestinatariosExcelPremium() {
    var rows = destinatariosDatosExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin datos', 'No hay destinatarios para exportar.');
        return;
    }

    var promise = destinatariosGenerarXlsx(rows);

    if (!promise) {
        showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    promise
        .then(function(blob) {
            correoModernDescargarBlob(
                blob,
                'Reporte_Destinatarios_' + correoModernFechaArchivo() + '.xlsx'
            );
        })
        .catch(function(error) {
            console.error('Error generando Excel de destinatarios:', error);
            showNotify('error', 'Error al generar Excel', 'No se pudo generar el archivo Excel.');
        });
}

function previsualizarDestinatariosPdfPremium() {
    var rows = destinatariosDatosExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin datos', 'No hay destinatarios para exportar.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        showNotify('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    if (!(typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/') === 0)) {
        correoModernPdfObtenerLogo(function(logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            previsualizarDestinatariosPdfPremium();
        });
        return;
    }

    var busqueda = String($('#buscarDestinatarioListado').val() || '').trim();
    var logo = correoModernPdfLogoPlate(imagen);
    var encabezado = {
        table: { widths: [100, '*', 150], body: [[
            { border: [false,false,false,false], fillColor: '#17324D', margin: [12,10,0,10], stack: [logo] },
            { border: [false,false,false,false], fillColor: '#17324D', margin: [0,10,0,10], stack: [
                { text: 'REPORTE DE DESTINATARIOS', fontSize: 16, bold: true, color: '#FFFFFF' },
                { text: 'Destinatarios internos de notificaciones del sistema', fontSize: 7.5, color: '#D8E5F0', margin: [0,2,0,0] }
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
        table: {
            widths: ['*'],
            body: [[{
                text: 'Filtros aplicados: Búsqueda: ' + (busqueda || 'Sin búsqueda'),
                fontSize: 6.8,
                color: '#52627A',
                margin: [10,7,10,7],
                fillColor: '#F7F9FC'
            }]]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,10]
    };

    var resumen = {
        table: {
            widths: ['*'],
            body: [[{
                fillColor: '#F7F9FC',
                margin: [8,7,8,7],
                stack: [
                    { text: 'REGISTROS', fontSize: 6.3, bold: true, color: '#6B778C' },
                    { text: String(rows.length), fontSize: 13, bold: true, color: '#172B4D', margin: [0,2,0,0] }
                ]
            }]]
        },
        layout: { hLineColor: function() { return '#DDE3EA'; }, vLineColor: function() { return '#DDE3EA'; }, hLineWidth: function() { return .6; }, vLineWidth: function() { return .6; } },
        margin: [0,0,0,12]
    };

    var contenido;

    if (destinatariosState.vista === 'miniatura') {
        contenido = [];

        function card(row) {
            return {
                table: {
                    widths: ['*'],
                    body: [[{
                        margin: [10,9,10,9],
                        stack: [
                            { text: row[1] || 'Sin nombre', fontSize: 10.5, bold: true, color: '#172B4D' },
                            { text: row[0] || 'No registrado', fontSize: 8, color: '#52627A', margin: [0,4,0,0] }
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

        for (var i = 0; i < rows.length; i += 2) {
            contenido.push({
                columns: [
                    { width: '*', stack: [card(rows[i])] },
                    { width: 10, text: '' },
                    rows[i + 1] ? { width: '*', stack: [card(rows[i + 1])] } : { width: '*', text: '' }
                ],
                margin: [0,0,0,9]
            });
        }
    } else {
        var body = [[
            { text: 'CORREO', style: 'th', fillColor: '#17324D' },
            { text: 'NOMBRE', style: 'th', fillColor: '#17324D' }
        ]];

        rows.forEach(function(row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                { text: row[0] || '—', fillColor: fill },
                { text: row[1] || '—', fillColor: fill, bold: true }
            ]);
        });

        contenido = [{
            table: {
                headerRows: 1,
                widths: [330, '*'],
                body: body
            },
            layout: {
                hLineColor: function() { return '#DDE3EA'; },
                vLineColor: function() { return '#DDE3EA'; },
                hLineWidth: function() { return .55; },
                vLineWidth: function() { return .55; },
                paddingLeft: function() { return 6; },
                paddingRight: function() { return 6; },
                paddingTop: function() { return 6; },
                paddingBottom: function() { return 6; }
            }
        }];
    }

    var doc = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [28,28,28,34],
        header: function() {
            return {
                margin: [28,12,28,0],
                canvas: [{ type: 'line', x1: 0, y1: 0, x2: 736, y2: 0, lineWidth: 2, lineColor: '#0EA5A8' }]
            };
        },
        footer: function(currentPage, pageCount) {
            return {
                margin: [28,8,28,0],
                columns: [
                    { text: 'IZZY • Destinatarios', fontSize: 7, color: '#7A869A' },
                    { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
                ]
            };
        },
        content: [
            encabezado,
            filtros,
            resumen,
            {
                text: destinatariosState.vista === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE',
                fontSize: 7,
                bold: true,
                color: '#17324D',
                margin: [0,1,0,7]
            }
        ].concat(contenido),
        styles: {
            th: { fontSize: 7, bold: true, color: '#FFFFFF', alignment: 'center' }
        },
        defaultStyle: { fontSize: 8, color: '#253858' }
    };

    var pdf = pdfMake.createPdf(doc);
    var nombre = 'Reporte_Destinatarios_' + correoModernFechaArchivo() + '.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(url) {
            abrirModalPdfPublico(url, 'Reporte de Destinatarios', nombre);
        });
        return;
    }

    if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function(base64) {
            abrirModalPdfPublico(
                'data:application/pdf;base64,' + base64,
                'Reporte de Destinatarios',
                nombre
            );
        });
        return;
    }

    showNotify('error', 'PDF no disponible', 'La versión actual de pdfMake no permite previsualización compatible.');
}

$(document).ready(function() {
    $('#btnActualizarDestinatarios')
        .off('click.destinatarios')
        .on('click.destinatarios', listar_destinatarios);

    $('#btnExcelDestinatarios')
        .off('click.destinatarios')
        .on('click.destinatarios', exportarDestinatariosExcelPremium);

    $('#btnPdfDestinatarios')
        .off('click.destinatarios')
        .on('click.destinatarios', previsualizarDestinatariosPdfPremium);

    $('#buscarDestinatarioListado')
        .off('input.destinatarios')
        .on('input.destinatarios', function() {
            destinatariosState.busqueda = $(this).val() || '';
            destinatariosAplicarFiltro();
        });

    $('#limpiarBuscarDestinatarioListado')
        .off('click.destinatarios')
        .on('click.destinatarios', function() {
            $('#buscarDestinatarioListado').val('').focus();
            destinatariosState.busqueda = '';
            destinatariosAplicarFiltro();
        });

    $('#destinatariosPageSize')
        .off('change.destinatarios')
        .on('change.destinatarios', function() {
            var value = parseInt($(this).val(), 10);

            if (isNaN(value) || value <= 0) {
                value = destinatariosState.vista === 'miniatura' ? 6 : 10;
            }

            destinatariosState.porPagina = value;

            if (destinatariosState.vista === 'miniatura') {
                destinatariosState.porPaginaMiniatura = value;
            } else {
                destinatariosState.porPaginaDetalle = value;
            }

            destinatariosState.pagina = 1;
            renderDestinatarios();
        });

    $('.destinatarios-view-btn')
        .off('click.destinatarios')
        .on('click.destinatarios', function() {
            var view = $(this).data('view') === 'miniatura' ? 'miniatura' : 'detalle';

            if (destinatariosEsMovil()) {
                view = 'miniatura';
            }

            destinatariosState.vista = view;
            destinatariosState.pagina = 1;

            if (!destinatariosEsMovil()) {
                destinatariosState.vistaPreferida = view;

                try {
                    localStorage.setItem(DESTINATARIOS_STORAGE_VISTA, destinatariosState.vistaPreferida);
                } catch (e) {}
            }

            destinatariosSincronizarVista();
            destinatariosSincronizarPageSize();
            renderDestinatarios();
        });

    $('#destinatariosPaginacion')
        .off('click.destinatarios', '.destinatarios-page-btn')
        .on('click.destinatarios', '.destinatarios-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                destinatariosState.pagina = page;
                renderDestinatarios();
            }
        });

    $('#destinatariosListado')
        .off('click.destinatarios', '.destinatario-eliminar')
        .on('click.destinatarios', '.destinatario-eliminar', function(e) {
            e.preventDefault();
            destinatariosEliminarPorId($(this).data('id'));
        });

    $(window)
        .off('resize.destinatarios orientationchange.destinatarios')
        .on('resize.destinatarios orientationchange.destinatarios', function() {
            var anterior = destinatariosState.vista;

            destinatariosSincronizarVista();

            if (anterior !== destinatariosState.vista) {
                destinatariosState.pagina = 1;
                destinatariosSincronizarPageSize();
                renderDestinatarios();
            }
        });

    $(document)
        .off('ajaxComplete.destinatarios')
        .on('ajaxComplete.destinatarios', function(event, xhr, settings) {
            var url = settings && settings.url ? String(settings.url) : '';

            if (
                url.indexOf('addDestinatario.php') !== -1 &&
                $('#modalRegistrarDestinatarios').hasClass('show')
            ) {
                setTimeout(function() {
                    listar_destinatarios();
                    $('#formDestinatarios #correo').trigger('focus');
                }, 180);
            }
        });
});

</script>
