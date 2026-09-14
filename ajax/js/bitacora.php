<script>
/* =========================================================
   IZZY 6.0 | BITÁCORA
   Listado DIV/Cards siguiendo la misma lógica visual de Egresos.
   ========================================================= */

var BITACORA_MOBILE_QUERY = '(max-width: 767.98px)';
var BITACORA_STORAGE_VISTA = 'izzy.bitacora.tipo_vista';

var bitacoraUI = {
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

function bitacoraEsMovil() {
    return window.matchMedia
        ? window.matchMedia(BITACORA_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function bitacoraValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function bitacoraEscape(value) {
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

function bitacoraNotificar(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    }
}

function bitacoraConfigurarPanel(btn, contenido, key) {
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

    $(btn).off('click.bitacoraPanel').on('click.bitacoraPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function bitacoraSincronizarPageSize() {
    var mini = bitacoraUI.view === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? bitacoraUI.pageSizeMiniatura : bitacoraUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    var $select = $('#bitacoraPageSize').empty();

    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });

    bitacoraUI.pageSize = preferido;
    $select.val(String(preferido));
}

function bitacoraSincronizarVista() {
    var movil = bitacoraEsMovil();

    if (movil) {
        bitacoraUI.view = 'miniatura';
    }

    $('.bitacora-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.bitacora-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.bitacora-view-btn[data-view="' + bitacoraUI.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function bitacoraFiltrar() {
    var q = $.trim(bitacoraUI.search || '').toLowerCase();

    bitacoraUI.filtered = !q
        ? bitacoraUI.rows.slice()
        : bitacoraUI.rows.filter(function(row) {
            return [
                row.bitacoraFecha,
                row.bitacoraHoraInicio,
                row.bitacoraHoraFinal,
                row.bitacoraTipo,
                row.colaborador
            ].map(function(v) {
                return bitacoraValor(v, '').toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    bitacoraActualizarResumen();
}

function bitacoraActualizarResumen() {
    var colaboradores = {};
    var tipos = {};
    var conHoraFinal = 0;

    bitacoraUI.filtered.forEach(function(row) {
        var colaborador = bitacoraValor(row.colaborador, '').toLowerCase();
        var tipo = bitacoraValor(row.bitacoraTipo, '').toLowerCase();
        var horaFinal = bitacoraValor(row.bitacoraHoraFinal, '');

        if (colaborador) {
            colaboradores[colaborador] = true;
        }

        if (tipo) {
            tipos[tipo] = true;
        }

        if (horaFinal) {
            conHoraFinal++;
        }
    });

    $('#bitacora-card-registros').text(bitacoraUI.filtered.length);
    $('#bitacora-card-colaboradores').text(Object.keys(colaboradores).length);
    $('#bitacora-card-tipos').text(Object.keys(tipos).length);
    $('#bitacora-card-finalizados').text(conHoraFinal);
}

function bitacoraRenderDetalle(rows) {
    var html =
        '<div class="bitacora-detail-header">' +
            '<div>Fecha</div>' +
            '<div>Hora Inicio</div>' +
            '<div>Hora Fin</div>' +
            '<div>Tipo</div>' +
            '<div>Colaborador</div>' +
        '</div>';

    rows.forEach(function(row) {
        html +=
            '<article class="bitacora-detail-row">' +
                '<div class="bitacora-cell">' +
                    '<span class="bitacora-cell-label">Fecha</span>' +
                    '<div class="bitacora-main-info">' +
                        '<span class="bitacora-main-icon"><i class="fas fa-calendar-alt"></i></span>' +
                        '<strong>' + bitacoraEscape(bitacoraValor(row.bitacoraFecha, 'Sin fecha')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="bitacora-cell">' +
                    '<span class="bitacora-cell-label">Hora Inicio</span>' +
                    '<span class="bitacora-time-badge bitacora-time-start"><i class="fas fa-play-circle"></i>' +
                        bitacoraEscape(bitacoraValor(row.bitacoraHoraInicio, 'Sin hora')) +
                    '</span>' +
                '</div>' +

                '<div class="bitacora-cell">' +
                    '<span class="bitacora-cell-label">Hora Fin</span>' +
                    '<span class="bitacora-time-badge bitacora-time-end"><i class="fas fa-stop-circle"></i>' +
                        bitacoraEscape(bitacoraValor(row.bitacoraHoraFinal, 'Sin hora final')) +
                    '</span>' +
                '</div>' +

                '<div class="bitacora-cell">' +
                    '<span class="bitacora-cell-label">Tipo</span>' +
                    '<span class="bitacora-type-badge"><i class="fas fa-tag"></i>' +
                        bitacoraEscape(bitacoraValor(row.bitacoraTipo, 'Sin tipo')) +
                    '</span>' +
                '</div>' +

                '<div class="bitacora-cell">' +
                    '<span class="bitacora-cell-label">Colaborador</span>' +
                    '<div class="bitacora-colaborador-text">' +
                        '<i class="fas fa-user"></i>' +
                        '<span>' + bitacoraEscape(bitacoraValor(row.colaborador, 'Sin colaborador')) + '</span>' +
                    '</div>' +
                '</div>' +
            '</article>';
    });

    return html;
}

function bitacoraRenderMiniatura(rows) {
    var html = '<div class="bitacora-mini-grid">';

    rows.forEach(function(row) {
        html +=
            '<article class="bitacora-mini-card">' +
                '<div class="bitacora-mini-topline"></div>' +
                '<div class="bitacora-mini-header">' +
                    '<div class="bitacora-mini-title">' +
                        '<h4>' + bitacoraEscape(bitacoraValor(row.colaborador, 'Sin colaborador')) + '</h4>' +
                        '<span><i class="fas fa-calendar-alt mr-1"></i>' + bitacoraEscape(bitacoraValor(row.bitacoraFecha, 'Sin fecha')) + '</span>' +
                    '</div>' +
                    '<span class="bitacora-mini-icon"><i class="fas fa-clipboard-list"></i></span>' +
                '</div>' +

                '<div class="bitacora-mini-meta">' +
                    '<span class="bitacora-type-badge"><i class="fas fa-tag"></i>' +
                        bitacoraEscape(bitacoraValor(row.bitacoraTipo, 'Sin tipo')) +
                    '</span>' +
                '</div>' +

                '<div class="bitacora-mini-body">' +
                    '<div class="bitacora-mini-field">' +
                        '<span>Hora Inicio</span>' +
                        '<strong><i class="fas fa-play-circle mr-1"></i>' + bitacoraEscape(bitacoraValor(row.bitacoraHoraInicio, 'Sin hora')) + '</strong>' +
                    '</div>' +
                    '<div class="bitacora-mini-field">' +
                        '<span>Hora Fin</span>' +
                        '<strong><i class="fas fa-stop-circle mr-1"></i>' + bitacoraEscape(bitacoraValor(row.bitacoraHoraFinal, 'Sin hora final')) + '</strong>' +
                    '</div>' +
                '</div>' +
            '</article>';
    });

    return html + '</div>';
}

function bitacoraRenderPaginacion(totalPages) {
    var current = bitacoraUI.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="bitacora-page-btn' + (active ? ' active' : '') +
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

    $('#bitacoraPaginacion').html(html);
}

function bitacoraRender() {
    var rows = bitacoraUI.filtered || [];

    if (bitacoraUI.loading) {
        $('#bitacoraListado').html(
            '<div class="bitacora-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando bitácora</strong><span>Consultando información...</span></div>'
        );
        $('#bitacoraInfo').text('0 registros');
        $('#bitacoraPaginacion').empty();
        return;
    }

    if (!rows.length) {
        $('#bitacoraListado').html(
            '<div class="bitacora-state"><i class="fas fa-clipboard-list"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron registros con los filtros actuales.</span></div>'
        );
        $('#bitacoraInfo').text('0 registros');
        $('#bitacoraPaginacion').empty();
        return;
    }

    var pages = Math.max(1, Math.ceil(rows.length / bitacoraUI.pageSize));

    if (bitacoraUI.page > pages) {
        bitacoraUI.page = pages;
    }

    var offset = (bitacoraUI.page - 1) * bitacoraUI.pageSize;
    var pageRows = rows.slice(offset, offset + bitacoraUI.pageSize);

    $('#bitacoraListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + bitacoraUI.view)
        .html(
            bitacoraUI.view === 'miniatura'
                ? bitacoraRenderMiniatura(pageRows)
                : bitacoraRenderDetalle(pageRows)
        );

    $('#bitacoraInfo').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    bitacoraRenderPaginacion(pages);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

var listar_bitacora = function() {
    var fechai = $('#formMainBitacora #fechai').val();
    var fechaf = $('#formMainBitacora #fechaf').val();

    if (!fechai || !fechaf) {
        bitacoraNotificar('error', 'Error', 'Debe seleccionar un rango de fechas');
        return;
    }

    bitacoraUI.loading = true;
    bitacoraRender();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableBitacora.php',
        dataType: 'json',
        data: {
            fechai: fechai,
            fechaf: fechaf
        }
    }).done(function(json) {
        bitacoraUI.rows = json && Array.isArray(json.data) ? json.data : [];
        bitacoraUI.search = $('#buscarBitacoraListado').val() || '';
        bitacoraUI.page = 1;
        bitacoraUI.loading = false;

        bitacoraFiltrar();
        bitacoraRender();

        if (!bitacoraUI.rows.length) {
            bitacoraNotificar('warning', 'Advertencia', 'No se encontraron registros con los filtros aplicados');
        }
    }).fail(function(xhr) {
        bitacoraUI.rows = [];
        bitacoraUI.filtered = [];
        bitacoraUI.loading = false;

        bitacoraActualizarResumen();
        bitacoraRender();

        bitacoraNotificar('error', 'Error', 'No se pudo cargar la bitácora');
        console.error('Error en AJAX Bitácora:', xhr.responseText);
    });
};

function bitacoraExcelEscape(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function bitacoraExcelCol(index) {
    var name = '';

    while (index >= 0) {
        name = String.fromCharCode((index % 26) + 65) + name;
        index = Math.floor(index / 26) - 1;
    }

    return name;
}

function bitacoraExcelCell(ref, value, style) {
    return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
        bitacoraExcelEscape(value) + '</t></is></c>';
}

function bitacoraGenerarExcel() {
    var rows = bitacoraUI.filtered || [];

    if (!rows.length) {
        bitacoraNotificar('warning', 'Sin información', 'No hay registros para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        bitacoraNotificar('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    var headers = ['Fecha', 'Hora Inicio', 'Hora Fin', 'Tipo', 'Colaborador'];
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' +
        bitacoraExcelCell('A1', 'IZZY • REPORTE DE BITÁCORA', 1) + '</row>');

    sheetRows.push('<row r="2">' +
        bitacoraExcelCell(
            'A2',
            'Período: ' + $('#fechai').val() + ' a ' + $('#fechaf').val() +
            ' • Registros: ' + rows.length,
            2
        ) + '</row>');

    sheetRows.push('<row r="3">' +
        bitacoraExcelCell(
            'A3',
            'Búsqueda: ' + ($.trim($('#buscarBitacoraListado').val()) || 'Sin búsqueda'),
            2
        ) + '</row>');

    sheetRows.push('<row r="5" ht="28" customHeight="1">' +
        headers.map(function(h, i) {
            return bitacoraExcelCell(bitacoraExcelCol(i) + '5', h, 3);
        }).join('') +
    '</row>');

    rows.forEach(function(row, i) {
        var rr = 6 + i;
        var values = [
            bitacoraValor(row.bitacoraFecha, ''),
            bitacoraValor(row.bitacoraHoraInicio, ''),
            bitacoraValor(row.bitacoraHoraFinal, ''),
            bitacoraValor(row.bitacoraTipo, ''),
            bitacoraValor(row.colaborador, '')
        ];

        sheetRows.push('<row r="' + rr + '">' +
            values.map(function(value, c) {
                return bitacoraExcelCell(bitacoraExcelCol(c) + rr, value, 4);
            }).join('') +
        '</row>');
    });

    var totalRow = 6 + rows.length;

    sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
        bitacoraExcelCell('A' + totalRow, 'TOTAL DE REGISTROS: ' + rows.length, 5) +
    '</row>');

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:E' + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="1" width="20" customWidth="1"/>' +
                '<col min="2" max="2" width="18" customWidth="1"/>' +
                '<col min="3" max="3" width="18" customWidth="1"/>' +
                '<col min="4" max="4" width="26" customWidth="1"/>' +
                '<col min="5" max="5" width="36" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A5:E' + (totalRow - 1) + '"/>' +
            '<mergeCells count="4">' +
                '<mergeCell ref="A1:E1"/>' +
                '<mergeCell ref="A2:E2"/>' +
                '<mergeCell ref="A3:E3"/>' +
                '<mergeCell ref="A' + totalRow + ':E' + totalRow + '"/>' +
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
            '<sheets><sheet name="Bitacora" sheetId="1" r:id="rId1"/></sheets>' +
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
        a.download = 'Reporte_Bitacora.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }).catch(function(error) {
        console.error(error);
        bitacoraNotificar('error', 'Error', 'No se pudo generar el Excel.');
    });
}

function bitacoraObtenerLogoPdf(callback) {
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

function bitacoraGenerarPdf() {
    var rows = bitacoraUI.filtered || [];

    if (!rows.length) {
        bitacoraNotificar('warning', 'Sin información', 'No hay registros para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        bitacoraNotificar('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    bitacoraObtenerLogoPdf(function(logo) {
        var body = [[
            { text: 'FECHA', style: 'th' },
            { text: 'HORA INICIO', style: 'th' },
            { text: 'HORA FIN', style: 'th' },
            { text: 'TIPO', style: 'th' },
            { text: 'COLABORADOR', style: 'th' }
        ]];

        rows.forEach(function(row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                { text: bitacoraValor(row.bitacoraFecha, ''), style: 'td', fillColor: fill },
                { text: bitacoraValor(row.bitacoraHoraInicio, ''), style: 'td', fillColor: fill },
                { text: bitacoraValor(row.bitacoraHoraFinal, ''), style: 'td', fillColor: fill },
                { text: bitacoraValor(row.bitacoraTipo, ''), style: 'td', fillColor: fill },
                { text: bitacoraValor(row.colaborador, ''), style: 'td', fillColor: fill }
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
        var busqueda = $.trim($('#buscarBitacoraListado').val()) || 'Sin búsqueda';

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
                        { text: 'IZZY • Bitácora', fontSize: 7, color: '#7A869A' },
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
                                    { text: 'BITÁCORA', bold: true, fontSize: 16, color: '#FFFFFF' },
                                    { text: 'Registro de actividad del sistema por período', fontSize: 8, color: '#D8E5F0', margin: [0, 2, 0, 0] }
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
                        widths: [100, 90, 90, 145, '*'],
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
                'Bitácora',
                'Reporte_Bitacora.pdf'
            );
        });
    });
}

function inicializarBitacoraUI() {
    bitacoraConfigurarPanel(
        '#btnToggleFiltrosBitacora',
        '#bitacoraFiltrosContenido',
        'izzy.bitacora.filtros.visible'
    );

    bitacoraConfigurarPanel(
        '#btnToggleKpisBitacora',
        '#bitacoraKpisContenido',
        'izzy.bitacora.kpis.visible'
    );

    var saved = 'detalle';

    try {
        saved = localStorage.getItem(BITACORA_STORAGE_VISTA) || 'detalle';
    } catch (e) {}

    bitacoraUI.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
    bitacoraUI.view = bitacoraEsMovil() ? 'miniatura' : bitacoraUI.preferredView;

    bitacoraSincronizarPageSize();
    bitacoraSincronizarVista();

    $('#buscarBitacoraListado')
        .off('input.bitacoraUI')
        .on('input.bitacoraUI', function() {
            bitacoraUI.search = this.value || '';
            bitacoraUI.page = 1;
            bitacoraFiltrar();
            bitacoraRender();
        });

    $('#limpiarBuscarBitacoraListado')
        .off('click.bitacoraUI')
        .on('click.bitacoraUI', function() {
            $('#buscarBitacoraListado').val('').focus();
            bitacoraUI.search = '';
            bitacoraUI.page = 1;
            bitacoraFiltrar();
            bitacoraRender();
        });

    $('#bitacoraPageSize')
        .off('change.bitacoraUI')
        .on('change.bitacoraUI', function() {
            var n = parseInt(this.value, 10);

            if (!n) {
                return;
            }

            bitacoraUI.pageSize = n;

            if (bitacoraUI.view === 'miniatura') {
                bitacoraUI.pageSizeMiniatura = n;
            } else {
                bitacoraUI.pageSizeDetalle = n;
            }

            bitacoraUI.page = 1;
            bitacoraRender();
        });

    $('.bitacora-view-btn')
        .off('click.bitacoraUI')
        .on('click.bitacoraUI', function() {
            var vista = $(this).data('view');

            bitacoraUI.view = bitacoraEsMovil()
                ? 'miniatura'
                : (vista === 'miniatura' ? 'miniatura' : 'detalle');

            if (!bitacoraEsMovil()) {
                bitacoraUI.preferredView = bitacoraUI.view;

                try {
                    localStorage.setItem(BITACORA_STORAGE_VISTA, bitacoraUI.preferredView);
                } catch (e) {}
            }

            bitacoraUI.page = 1;
            bitacoraSincronizarPageSize();
            bitacoraSincronizarVista();
            bitacoraRender();
        });

    $('#bitacoraPaginacion')
        .off('click.bitacoraUI', '.bitacora-page-btn')
        .on('click.bitacoraUI', '.bitacora-page-btn', function() {
            if (this.disabled) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!page) {
                return;
            }

            bitacoraUI.page = page;
            bitacoraRender();
        });

    $('#btnBitacoraActualizar')
        .off('click.bitacoraUI')
        .on('click.bitacoraUI', listar_bitacora);

    $('#btnBitacoraExcel')
        .off('click.bitacoraUI')
        .on('click.bitacoraUI', bitacoraGenerarExcel);

    $('#btnBitacoraPdf')
        .off('click.bitacoraUI')
        .on('click.bitacoraUI', bitacoraGenerarPdf);

    $(window)
        .off('resize.bitacoraUI orientationchange.bitacoraUI')
        .on('resize.bitacoraUI orientationchange.bitacoraUI', function() {
            var target = bitacoraEsMovil() ? 'miniatura' : bitacoraUI.preferredView;

            if (bitacoraUI.view !== target) {
                bitacoraUI.view = target;
                bitacoraUI.page = 1;
                bitacoraSincronizarPageSize();
                bitacoraSincronizarVista();
                bitacoraRender();
            } else {
                bitacoraSincronizarVista();
            }
        });
}

$(() => {
    inicializarBitacoraUI();
    listar_bitacora();

    $('#formMainBitacora #search').on('click', function(e) {
        e.preventDefault();
        listar_bitacora();
    });

    $('#formMainBitacora').on('reset', function() {
        setTimeout(function() {
            $('#buscarBitacoraListado').val('');
            bitacoraUI.search = '';
            bitacoraUI.page = 1;
            listar_bitacora();
        }, 0);
    });
});
</script>
