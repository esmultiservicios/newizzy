<script>
/* =========================================================
   IZZY 6.0 | HISTORIAL DE ACCESOS
   Listado DIV/Cards siguiendo la misma lógica visual de Egresos.
   ========================================================= */

var HISTORIAL_MOBILE_QUERY = '(max-width: 767.98px)';
var HISTORIAL_STORAGE_VISTA = 'izzy.historial_accesos.tipo_vista';

var historialUI = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    pageSizeDetalle: 10,
    pageSizeMiniatura: 6,
    view: 'detalle',
    preferredView: 'detalle',
    search: '',
    loading: false
};

function historialEsMovil() {
    return window.matchMedia
        ? window.matchMedia(HISTORIAL_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function historialValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function historialEscape(value) {
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

function historialNotificar(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    }
}

function historialConfigurarPanel(btn, contenido, key) {
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

    $(btn).off('click.historialPanel').on('click.historialPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function historialSincronizarPageSize() {
    var mini = historialUI.view === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? historialUI.pageSizeMiniatura : historialUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    var $select = $('#historialPageSize').empty();

    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });

    historialUI.pageSize = preferido;
    $select.val(String(preferido));
}

function historialSincronizarVista() {
    var movil = historialEsMovil();

    if (movil) {
        historialUI.view = 'miniatura';
    }

    $('.historial-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.historial-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.historial-view-btn[data-view="' + historialUI.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function historialFiltrar() {
    var q = $.trim(historialUI.search || '').toLowerCase();

    historialUI.filtered = !q
        ? historialUI.rows.slice()
        : historialUI.rows.filter(function(row) {
            return [
                row.fecha,
                row.colaborador,
                row.ip,
                row.acceso
            ].map(function(v) {
                return historialValor(v, '').toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    historialActualizarResumen();
}

function historialActualizarResumen() {
    var colaboradores = {};
    var ips = {};

    historialUI.filtered.forEach(function(row) {
        var colaborador = historialValor(row.colaborador, '').toLowerCase();
        var ip = historialValor(row.ip, '').toLowerCase();

        if (colaborador) {
            colaboradores[colaborador] = true;
        }

        if (ip) {
            ips[ip] = true;
        }
    });

    $('#historial-card-accesos').text(historialUI.filtered.length);
    $('#historial-card-colaboradores').text(Object.keys(colaboradores).length);
    $('#historial-card-ips').text(Object.keys(ips).length);
}

function historialRenderDetalle(rows) {
    var html =
        '<div class="historial-detail-header">' +
            '<div>Fecha</div>' +
            '<div>Colaborador</div>' +
            '<div>IP</div>' +
            '<div>Acceso</div>' +
        '</div>';

    rows.forEach(function(row) {
        html +=
            '<article class="historial-detail-row">' +
                '<div class="historial-cell">' +
                    '<span class="historial-cell-label">Fecha</span>' +
                    '<div class="historial-main-info">' +
                        '<span class="historial-main-icon"><i class="fas fa-calendar-alt"></i></span>' +
                        '<strong>' + historialEscape(historialValor(row.fecha, 'Sin fecha')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="historial-cell">' +
                    '<span class="historial-cell-label">Colaborador</span>' +
                    '<div class="historial-main-info">' +
                        '<span class="historial-main-icon"><i class="fas fa-user"></i></span>' +
                        '<strong>' + historialEscape(historialValor(row.colaborador, 'Sin colaborador')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="historial-cell">' +
                    '<span class="historial-cell-label">IP</span>' +
                    '<span class="historial-ip-badge"><i class="fas fa-network-wired"></i>' +
                        historialEscape(historialValor(row.ip, 'Sin IP')) +
                    '</span>' +
                '</div>' +

                '<div class="historial-cell">' +
                    '<span class="historial-cell-label">Acceso</span>' +
                    '<div class="historial-acceso-text">' +
                        '<i class="fas fa-history"></i>' +
                        '<span>' + historialEscape(historialValor(row.acceso, 'Sin detalle')) + '</span>' +
                    '</div>' +
                '</div>' +
            '</article>';
    });

    return html;
}

function historialRenderMiniatura(rows) {
    var html = '<div class="historial-mini-grid">';

    rows.forEach(function(row) {
        html +=
            '<article class="historial-mini-card">' +
                '<div class="historial-mini-topline"></div>' +
                '<div class="historial-mini-header">' +
                    '<div class="historial-mini-title">' +
                        '<h4>' + historialEscape(historialValor(row.colaborador, 'Sin colaborador')) + '</h4>' +
                        '<span><i class="fas fa-calendar-alt mr-1"></i>' + historialEscape(historialValor(row.fecha, 'Sin fecha')) + '</span>' +
                    '</div>' +
                    '<span class="historial-mini-icon"><i class="fas fa-sign-in-alt"></i></span>' +
                '</div>' +

                '<div class="historial-mini-body">' +
                    '<div class="historial-mini-field">' +
                        '<span>IP</span>' +
                        '<strong>' + historialEscape(historialValor(row.ip, 'Sin IP')) + '</strong>' +
                    '</div>' +
                    '<div class="historial-mini-field historial-mini-field-full">' +
                        '<span>Acceso</span>' +
                        '<strong>' + historialEscape(historialValor(row.acceso, 'Sin detalle')) + '</strong>' +
                    '</div>' +
                '</div>' +
            '</article>';
    });

    return html + '</div>';
}

function historialRenderPaginacion(totalPages) {
    var current = historialUI.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="historial-page-btn' + (active ? ' active' : '') +
            '" data-page="' + page + '"' + (disabled ? ' disabled' : '') + '>' +
            (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
        '</button>';
    }

    html += button('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
    html += button('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

    var from = Math.max(1, current - 2);
    var to = Math.min(totalPages, from + 4);
    from = Math.max(1, to - 4);

    for (var p = from; p <= to; p++) {
        html += button(String(p), p, false, p === current, '');
    }

    html += button('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
    html += button('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

    $('#historialPaginacion').html(html);
}

function historialRender() {
    var rows = historialUI.filtered || [];

    if (historialUI.loading) {
        $('#historialListado').html(
            '<div class="historial-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando historial</strong><span>Consultando información...</span></div>'
        );
        $('#historialInfo').text('0 registros');
        $('#historialPaginacion').empty();
        return;
    }

    if (!rows.length) {
        $('#historialListado').html(
            '<div class="historial-state"><i class="fas fa-history"></i>' +
            '<strong>Sin accesos</strong><span>No se encontraron registros con los filtros actuales.</span></div>'
        );
        $('#historialInfo').text('0 registros');
        $('#historialPaginacion').empty();
        return;
    }

    var pages = Math.max(1, Math.ceil(rows.length / historialUI.pageSize));

    if (historialUI.page > pages) {
        historialUI.page = pages;
    }

    var offset = (historialUI.page - 1) * historialUI.pageSize;
    var pageRows = rows.slice(offset, offset + historialUI.pageSize);

    $('#historialListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + historialUI.view)
        .html(
            historialUI.view === 'miniatura'
                ? historialRenderMiniatura(pageRows)
                : historialRenderDetalle(pageRows)
        );

    $('#historialInfo').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    historialRenderPaginacion(pages);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

var listar_historial_accesos = function() {
    var fechai = $('#formMainHistorialAcceso #fechai').val();
    var fechaf = $('#formMainHistorialAcceso #fechaf').val();

    if (!fechai || !fechaf) {
        historialNotificar('error', 'Error', 'Debe seleccionar un rango de fechas');
        return;
    }

    historialUI.loading = true;
    historialRender();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableHistorialAccesos.php',
        dataType: 'json',
        data: {
            fechai: fechai,
            fechaf: fechaf
        }
    }).done(function(json) {
        historialUI.rows = json && Array.isArray(json.data) ? json.data : [];
        historialUI.search = $('#buscarHistorialListado').val() || '';
        historialUI.page = 1;
        historialUI.loading = false;

        historialFiltrar();
        historialRender();

        if (!historialUI.rows.length) {
            historialNotificar('warning', 'Advertencia', 'No se encontraron registros con los filtros aplicados');
        }
    }).fail(function(xhr) {
        historialUI.rows = [];
        historialUI.filtered = [];
        historialUI.loading = false;

        historialActualizarResumen();
        historialRender();

        historialNotificar('error', 'Error', 'No se pudo cargar el historial de accesos');
        console.error('Error en AJAX Historial de Accesos:', xhr.responseText);
    });
};

function historialExcelEscape(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function historialExcelCol(index) {
    var name = '';

    while (index >= 0) {
        name = String.fromCharCode((index % 26) + 65) + name;
        index = Math.floor(index / 26) - 1;
    }

    return name;
}

function historialExcelCell(ref, value, style) {
    return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
        historialExcelEscape(value) + '</t></is></c>';
}

function historialGenerarExcel() {
    var rows = historialUI.filtered || [];

    if (!rows.length) {
        historialNotificar('warning', 'Sin información', 'No hay accesos para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        historialNotificar('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    var headers = ['Fecha', 'Colaborador', 'IP', 'Acceso'];
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' +
        historialExcelCell('A1', 'IZZY • REPORTE DE HISTORIAL DE ACCESOS', 1) + '</row>');

    sheetRows.push('<row r="2">' +
        historialExcelCell(
            'A2',
            'Período: ' + $('#fechai').val() + ' a ' + $('#fechaf').val() +
            ' • Registros: ' + rows.length,
            2
        ) + '</row>');

    sheetRows.push('<row r="3">' +
        historialExcelCell(
            'A3',
            'Búsqueda: ' + ($.trim($('#buscarHistorialListado').val()) || 'Sin búsqueda'),
            2
        ) + '</row>');

    sheetRows.push('<row r="5" ht="28" customHeight="1">' +
        headers.map(function(h, i) {
            return historialExcelCell(historialExcelCol(i) + '5', h, 3);
        }).join('') +
    '</row>');

    rows.forEach(function(row, i) {
        var rr = 6 + i;
        var values = [
            historialValor(row.fecha, ''),
            historialValor(row.colaborador, ''),
            historialValor(row.ip, ''),
            historialValor(row.acceso, '')
        ];

        sheetRows.push('<row r="' + rr + '">' +
            values.map(function(value, c) {
                return historialExcelCell(historialExcelCol(c) + rr, value, 4);
            }).join('') +
        '</row>');
    });

    var totalRow = 6 + rows.length;

    sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
        historialExcelCell('A' + totalRow, 'TOTAL DE ACCESOS: ' + rows.length, 5) +
    '</row>');

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:D' + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="1" width="22" customWidth="1"/>' +
                '<col min="2" max="2" width="32" customWidth="1"/>' +
                '<col min="3" max="3" width="20" customWidth="1"/>' +
                '<col min="4" max="4" width="64" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A5:D' + (totalRow - 1) + '"/>' +
            '<mergeCells count="4">' +
                '<mergeCell ref="A1:D1"/>' +
                '<mergeCell ref="A2:D2"/>' +
                '<mergeCell ref="A3:D3"/>' +
                '<mergeCell ref="A' + totalRow + ':D' + totalRow + '"/>' +
            '</mergeCells>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="6">' +
                '<font><sz val="10"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FF17324D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="5">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill>' +
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
            '<cellXfs count="6">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Historial" sheetId="1" r:id="rId1"/></sheets>' +
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
    zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

    var opts = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promise = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(opts)
        : (typeof zip.generate === 'function'
            ? Promise.resolve(zip.generate(opts))
            : Promise.reject(new Error('JSZip no soportado')));

    promise.then(function(blob) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');

        a.href = url;
        a.download = 'Reporte_Historial_Accesos.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }).catch(function(error) {
        console.error(error);
        historialNotificar('error', 'Error', 'No se pudo generar el Excel.');
    });
}

function historialObtenerLogoPdf(callback) {
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

function historialGenerarPdf() {
    var rows = historialUI.filtered || [];

    if (!rows.length) {
        historialNotificar('warning', 'Sin información', 'No hay accesos para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        historialNotificar('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    historialObtenerLogoPdf(function(logo) {
        var body = [[
            { text: 'FECHA', style: 'th' },
            { text: 'COLABORADOR', style: 'th' },
            { text: 'IP', style: 'th' },
            { text: 'ACCESO', style: 'th' }
        ]];

        rows.forEach(function(row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                { text: historialValor(row.fecha, ''), style: 'td', fillColor: fill },
                { text: historialValor(row.colaborador, ''), style: 'td', fillColor: fill },
                { text: historialValor(row.ip, ''), style: 'td', fillColor: fill },
                { text: historialValor(row.acceso, ''), style: 'td', fillColor: fill }
            ]);
        });

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

        var desde = $('#fechai').val() || '';
        var hasta = $('#fechaf').val() || '';
        var busqueda = $.trim($('#buscarHistorialListado').val()) || 'Sin búsqueda';

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

            footer: function(page, pages) {
                return {
                    margin: [28, 8, 28, 0],
                    columns: [
                        { text: 'IZZY • Historial de Accesos', fontSize: 7, color: '#7A869A' },
                        { text: 'Página ' + page + ' de ' + pages, fontSize: 7, color: '#7A869A', alignment: 'right' }
                    ]
                };
            },

            content: [
                {
                    table: {
                        widths: [100, '*', 150],
                        body: [[
                            logoCell,
                            {
                                stack: [
                                    { text: 'HISTORIAL DE ACCESOS', bold: true, fontSize: 16, color: '#FFFFFF' },
                                    { text: 'Registro de accesos del sistema por período', fontSize: 8, color: '#D8E5F0', margin: [0, 2, 0, 0] }
                                ],
                                fillColor: '#17324D',
                                margin: [0, 10, 0, 10]
                            },
                            {
                                stack: [
                                    { text: 'REPORTE EJECUTIVO', bold: true, fontSize: 6.5, color: '#72E2E5', alignment: 'right' },
                                    { text: new Date().toLocaleDateString('es-HN'), bold: true, fontSize: 9, color: '#FFFFFF', alignment: 'right' },
                                    { text: rows.length + ' registro(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right' }
                                ],
                                fillColor: '#17324D',
                                margin: [0, 10, 12, 10]
                            }
                        ]]
                    },
                    layout: 'noBorders',
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ['*'],
                        body: [[{
                            text: 'Período: ' + desde + ' a ' + hasta + '   |   Búsqueda: ' + busqueda,
                            fontSize: 7,
                            color: '#52627A',
                            fillColor: '#F7F9FC',
                            margin: [8, 6, 8, 6]
                        }]]
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: [115, 175, 105, '*'],
                        body: body,
                        dontBreakRows: true
                    },
                    layout: {
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; },
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        paddingLeft: function() { return 5; },
                        paddingRight: function() { return 5; },
                        paddingTop: function() { return 4; },
                        paddingBottom: function() { return 4; }
                    }
                }
            ],

            styles: {
                th: {
                    fontSize: 7.2,
                    bold: true,
                    color: '#FFFFFF',
                    fillColor: '#0EA5A8',
                    alignment: 'center'
                },
                td: {
                    fontSize: 7,
                    color: '#253858',
                    noWrap: false
                }
            },

            defaultStyle: {
                fontSize: 7
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url) {
            abrirModalPdfPublico(
                url,
                'Historial de Accesos',
                'Reporte_Historial_Accesos.pdf'
            );
        });
    });
}

function inicializarHistorialUI() {
    historialConfigurarPanel(
        '#btnToggleFiltrosHistorial',
        '#historialFiltrosContenido',
        'izzy.historial_accesos.filtros.visible'
    );

    historialConfigurarPanel(
        '#btnToggleKpisHistorial',
        '#historialKpisContenido',
        'izzy.historial_accesos.kpis.visible'
    );

    var saved = 'detalle';

    try {
        saved = localStorage.getItem(HISTORIAL_STORAGE_VISTA) || 'detalle';
    } catch (e) {}

    historialUI.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
    historialUI.view = historialEsMovil() ? 'miniatura' : historialUI.preferredView;

    historialSincronizarPageSize();
    historialSincronizarVista();

    $('#buscarHistorialListado')
        .off('input.historialUI')
        .on('input.historialUI', function() {
            historialUI.search = this.value || '';
            historialUI.page = 1;
            historialFiltrar();
            historialRender();
        });

    $('#limpiarBuscarHistorialListado')
        .off('click.historialUI')
        .on('click.historialUI', function() {
            $('#buscarHistorialListado').val('').focus();
            historialUI.search = '';
            historialUI.page = 1;
            historialFiltrar();
            historialRender();
        });

    $('#historialPageSize')
        .off('change.historialUI')
        .on('change.historialUI', function() {
            var n = parseInt(this.value, 10);

            if (!n) {
                return;
            }

            historialUI.pageSize = n;

            if (historialUI.view === 'miniatura') {
                historialUI.pageSizeMiniatura = n;
            } else {
                historialUI.pageSizeDetalle = n;
            }

            historialUI.page = 1;
            historialRender();
        });

    $('.historial-view-btn')
        .off('click.historialUI')
        .on('click.historialUI', function() {
            var vista = $(this).data('view');

            historialUI.view = historialEsMovil()
                ? 'miniatura'
                : (vista === 'miniatura' ? 'miniatura' : 'detalle');

            if (!historialEsMovil()) {
                historialUI.preferredView = historialUI.view;

                try {
                    localStorage.setItem(HISTORIAL_STORAGE_VISTA, historialUI.preferredView);
                } catch (e) {}
            }

            historialUI.page = 1;
            historialSincronizarPageSize();
            historialSincronizarVista();
            historialRender();
        });

    $('#historialPaginacion')
        .off('click.historialUI', '.historial-page-btn')
        .on('click.historialUI', '.historial-page-btn', function() {
            if (this.disabled) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!page) {
                return;
            }

            historialUI.page = page;
            historialRender();
        });

    $('#btnHistorialActualizar')
        .off('click.historialUI')
        .on('click.historialUI', listar_historial_accesos);

    $('#btnHistorialExcel')
        .off('click.historialUI')
        .on('click.historialUI', historialGenerarExcel);

    $('#btnHistorialPdf')
        .off('click.historialUI')
        .on('click.historialUI', historialGenerarPdf);

    $(window)
        .off('resize.historialUI orientationchange.historialUI')
        .on('resize.historialUI orientationchange.historialUI', function() {
            var target = historialEsMovil() ? 'miniatura' : historialUI.preferredView;

            if (historialUI.view !== target) {
                historialUI.view = target;
                historialUI.page = 1;
                historialSincronizarPageSize();
                historialSincronizarVista();
                historialRender();
            } else {
                historialSincronizarVista();
            }
        });
}

$(() => {
    inicializarHistorialUI();
    listar_historial_accesos();

    $('#formMainHistorialAcceso #search').on('click', function(e) {
        e.preventDefault();
        listar_historial_accesos();
    });

    $('#formMainHistorialAcceso').on('reset', function() {
        setTimeout(function() {
            $('#buscarHistorialListado').val('');
            historialUI.search = '';
            historialUI.page = 1;
            listar_historial_accesos();
        }, 0);
    });
});
</script>
