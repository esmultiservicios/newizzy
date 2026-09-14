<script>
/* =========================================================
   IZZY 6.0 | PUESTOS
   Listado DIV/Cards con detalle y miniatura.
   Mantiene endpoints, permisos y flujo de edición/eliminación.
   ========================================================= */

var PUESTOS_MOBILE_QUERY = '(max-width: 767.98px)';
var PUESTOS_STORAGE_VISTA = 'izzy.puestos.tipo_vista';

var puestosUI = {
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

function puestosEsMovil() {
    return window.matchMedia
        ? window.matchMedia(PUESTOS_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function puestosValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function puestosEscape(value) {
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

function puestosNotificar(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    }
}

function puestosConfigurarPanel(btn, contenido, key) {
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

    $(btn).off('click.puestosPanel').on('click.puestosPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function puestosSincronizarPageSize() {
    var mini = puestosUI.view === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? puestosUI.pageSizeMiniatura : puestosUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    var $select = $('#puestosPageSize').empty();

    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });

    puestosUI.pageSize = preferido;
    $select.val(String(preferido));
}

function puestosSincronizarVista() {
    var movil = puestosEsMovil();

    if (movil) {
        puestosUI.view = 'miniatura';
    }

    $('.puestos-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.puestos-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.puestos-view-btn[data-view="' + puestosUI.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function puestosEstadoActivo(row) {
    return parseInt(row && row.estado, 10) === 1;
}

function puestosFiltrar() {
    var q = $.trim(puestosUI.search || '').toLowerCase();

    puestosUI.filtered = !q
        ? puestosUI.rows.slice()
        : puestosUI.rows.filter(function(row) {
            var estadoTexto = puestosEstadoActivo(row) ? 'activo' : 'inactivo';

            return [
                row.puestos_id,
                row.nombre,
                estadoTexto
            ].map(function(v) {
                return puestosValor(v, '').toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    puestosActualizarResumen();
}

function puestosActualizarResumen() {
    var activos = 0;
    var inactivos = 0;

    puestosUI.filtered.forEach(function(row) {
        if (puestosEstadoActivo(row)) {
            activos++;
        } else {
            inactivos++;
        }
    });

    $('#puestos-card-total').text(puestosUI.filtered.length);
    $('#puestos-card-activos').text(activos);
    $('#puestos-card-inactivos').text(inactivos);
}

function puestosEstadoHtml(row) {
    if (puestosEstadoActivo(row)) {
        return '<span class="puestos-status-badge puestos-status-active">' +
            '<i class="fas fa-check-circle"></i>Activo</span>';
    }

    return '<span class="puestos-status-badge puestos-status-inactive">' +
        '<i class="fas fa-times-circle"></i>Inactivo</span>';
}

function puestosAccionesHtml(row) {
    var id = puestosEscape(puestosValor(row.puestos_id, ''));

    return '' +
        '<div class="dropdown acciones-dropdown puestos-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar" data-puesto-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar" data-puesto-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function puestosRenderDetalle(rows) {
    var html = '' +
        '<div class="puestos-detail-header">' +
            '<div>Acciones</div>' +
            '<div>Código</div>' +
            '<div>Puesto</div>' +
            '<div>Estado</div>' +
        '</div>';

    rows.forEach(function(row) {
        html += '' +
            '<article class="puestos-detail-row">' +
                '<div class="puestos-cell puestos-actions-cell">' +
                    '<span class="puestos-cell-label">Acciones</span>' +
                    puestosAccionesHtml(row) +
                '</div>' +

                '<div class="puestos-cell">' +
                    '<span class="puestos-cell-label">Código</span>' +
                    '<span class="puestos-code-badge"><i class="fas fa-hashtag"></i>' +
                        puestosEscape(puestosValor(row.puestos_id, 'Sin código')) +
                    '</span>' +
                '</div>' +

                '<div class="puestos-cell">' +
                    '<span class="puestos-cell-label">Puesto</span>' +
                    '<div class="puestos-main-info">' +
                        '<span class="puestos-main-icon"><i class="fas fa-user-tag"></i></span>' +
                        '<strong>' + puestosEscape(puestosValor(row.nombre, 'Sin nombre')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="puestos-cell puestos-status-cell">' +
                    '<span class="puestos-cell-label">Estado</span>' +
                    puestosEstadoHtml(row) +
                '</div>' +
            '</article>';
    });

    return html;
}

function puestosRenderMiniatura(rows) {
    var html = '<div class="puestos-mini-grid">';

    rows.forEach(function(row) {
        html += '' +
            '<article class="puestos-mini-card">' +
                '<div class="puestos-mini-topline"></div>' +
                '<div class="puestos-mini-header">' +
                    '<div class="puestos-mini-title">' +
                        '<h4>' + puestosEscape(puestosValor(row.nombre, 'Sin nombre')) + '</h4>' +
                        '<span><i class="fas fa-hashtag mr-1"></i>Código ' +
                            puestosEscape(puestosValor(row.puestos_id, 'Sin código')) +
                        '</span>' +
                    '</div>' +
                    '<span class="puestos-mini-icon"><i class="fas fa-user-tag"></i></span>' +
                '</div>' +

                '<div class="puestos-mini-body">' +
                    '<div class="puestos-mini-field puestos-mini-field-full">' +
                        '<span>Estado</span>' +
                        puestosEstadoHtml(row) +
                    '</div>' +
                '</div>' +

                '<div class="puestos-mini-footer">' +
                    puestosAccionesHtml(row) +
                '</div>' +
            '</article>';
    });

    return html + '</div>';
}

function puestosRenderPaginacion(totalPages) {
    var current = puestosUI.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="puestos-page-btn' + (active ? ' active' : '') +
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

    $('#puestosPaginacion').html(html);
}


function puestosAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function puestosRender() {
    var rows = puestosUI.filtered || [];

    if (puestosUI.loading) {
        $('#puestosListado').html(
            '<div class="puestos-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando puestos</strong><span>Consultando información...</span></div>'
        );
        $('#puestosInfo').text('0 registros');
        $('#puestosPaginacion').empty();
        puestosAplicarPermisos();
        return;
    }

    if (!rows.length) {
        $('#puestosListado').html(
            '<div class="puestos-state"><i class="fas fa-briefcase"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron puestos con los filtros actuales.</span></div>'
        );
        $('#puestosInfo').text('0 registros');
        $('#puestosPaginacion').empty();
        puestosAplicarPermisos();
        return;
    }

    var pages = Math.max(1, Math.ceil(rows.length / puestosUI.pageSize));

    if (puestosUI.page > pages) {
        puestosUI.page = pages;
    }

    var offset = (puestosUI.page - 1) * puestosUI.pageSize;
    var pageRows = rows.slice(offset, offset + puestosUI.pageSize);

    $('#puestosListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + puestosUI.view)
        .html(
            puestosUI.view === 'miniatura'
                ? puestosRenderMiniatura(pageRows)
                : puestosRenderDetalle(pageRows)
        );

    $('#puestosInfo').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    puestosRenderPaginacion(pages);

    puestosAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function puestosBuscarFilaPorId(id) {
    var idTexto = String(id == null ? '' : id);
    var row = null;

    puestosUI.rows.some(function(item) {
        if (String(item.puestos_id) === idTexto) {
            row = item;
            return true;
        }
        return false;
    });

    return row;
}

var listar_puestos = function() {
    var estado = $('#form_main_puestos #estado_puestos').val();

    puestosUI.loading = true;
    puestosRender();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTablePuestos.php',
        dataType: 'json',
        data: {
            estado: estado
        }
    }).done(function(json) {
        puestosUI.rows = json && Array.isArray(json.data) ? json.data : [];
        puestosUI.search = $('#buscarPuestosListado').val() || '';
        puestosUI.page = 1;
        puestosUI.loading = false;

        puestosFiltrar();
        puestosRender();

        if (!puestosUI.rows.length) {
            puestosNotificar('warning', 'Sin registros', 'No se encontraron puestos con los filtros aplicados.');
        }
    }).fail(function(xhr) {
        puestosUI.rows = [];
        puestosUI.filtered = [];
        puestosUI.loading = false;

        puestosActualizarResumen();
        puestosRender();

        puestosNotificar('error', 'Error', 'No se pudo cargar el listado de puestos.');
        console.error('Error en AJAX Puestos:', xhr.responseText);
    });
};

/* =========================================================
   ACCIONES | EDITAR / ELIMINAR
   ========================================================= */

function puestosEditar(row) {
    if (!row) {
        puestosNotificar('error', 'Error', 'No fue posible identificar el puesto seleccionado.');
        return;
    }

    var url = '<?php echo SERVERURL;?>core/editarPuestos.php';

    $('#formPuestos #puestos_id').val(row.puestos_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formPuestos').serialize(),
        success: function(registro) {
            var valores;

            try {
                valores = typeof registro === 'string' ? eval(registro) : registro;
            } catch (e) {
                puestosNotificar('error', 'Error', 'No fue posible procesar la información del puesto.');
                console.error('Respuesta inválida al editar puesto:', registro, e);
                return;
            }

            $('#formPuestos').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/modificarPuestosAjax.php'
            });

            $('#formPuestos')[0].reset();
            $('#formPuestos #puestos_id').val(row.puestos_id);
            $('#reg_puestos').hide();
            $('#edi_puestos').show();
            $('#delete_puestos').hide();
            $('#formPuestos #puesto').val(valores[0]);
            $('#formPuestos #puestos_activo').prop('checked', parseInt(valores[1], 10) === 1);

            $('#formPuestos #puesto').prop('readonly', false);
            $('#formPuestos #puestos_activo').prop('disabled', false);
            $('#formPuestos #estado_puestos').show();

            $('#formPuestos #proceso_puestos').val('Editar');
            $('#formPuestos #label_puestos_activo').html(parseInt(valores[1], 10) === 1 ? 'Activo' : 'Inactivo');

            $('#modal_registrar_puestos').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr) {
            puestosNotificar('error', 'Error', 'No se pudo cargar la información del puesto.');
            console.error('Error al editar puesto:', xhr.responseText);
        }
    });
}

function puestosEliminar(row) {
    if (!row) {
        puestosNotificar('error', 'Error', 'No fue posible identificar el puesto seleccionado.');
        return;
    }

    if (typeof Swal === 'undefined') {
        puestosNotificar('error', 'Error', 'SweetAlert2 no está disponible.');
        return;
    }

    Swal.fire({
        title: 'Confirmar eliminación',
        html: '¿Desea eliminar permanentemente el puesto?<br><br>' +
            '<strong>Nombre:</strong> ' + puestosEscape(puestosValor(row.nombre, 'Sin nombre')),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancelar',
        confirmButtonColor: '#c9372c',
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then(function(result) {
        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Eliminando puesto',
            text: 'Espere mientras se procesa la solicitud...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarPuestosAjax.php',
            data: {
                puestos_id: row.puestos_id
            },
            dataType: 'json'
        }).done(function(response) {
            Swal.close();

            if (response && response.status === 'success') {
                puestosNotificar('success', response.title || 'Éxito', response.message || 'Puesto eliminado correctamente.');
                listar_puestos();
            } else {
                puestosNotificar(
                    'error',
                    response && response.title ? response.title : 'Error',
                    response && response.message ? response.message : 'No fue posible eliminar el puesto.'
                );
            }
        }).fail(function(xhr) {
            Swal.close();
            puestosNotificar('error', 'Error', 'Ocurrió un error al procesar la solicitud.');
            console.error('Error al eliminar puesto:', xhr.responseText);
        });
    });
}

/* =========================================================
   EXCEL PROFESIONAL
   ========================================================= */

function puestosExcelEscape(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function puestosExcelCol(index) {
    var name = '';

    while (index >= 0) {
        name = String.fromCharCode((index % 26) + 65) + name;
        index = Math.floor(index / 26) - 1;
    }

    return name;
}

function puestosExcelCell(ref, value, style) {
    return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
        puestosExcelEscape(value) + '</t></is></c>';
}

function puestosGenerarExcel() {
    var rows = puestosUI.filtered || [];

    if (!rows.length) {
        puestosNotificar('warning', 'Sin información', 'No hay puestos para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        puestosNotificar('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    var headers = ['Código', 'Puesto', 'Estado'];
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' +
        puestosExcelCell('A1', 'IZZY • REPORTE DE PUESTOS', 1) + '</row>');

    sheetRows.push('<row r="2">' +
        puestosExcelCell(
            'A2',
            'Estado filtrado: ' + puestosFiltroEstadoTexto() + ' • Registros: ' + rows.length,
            2
        ) + '</row>');

    sheetRows.push('<row r="3">' +
        puestosExcelCell(
            'A3',
            'Búsqueda: ' + ($.trim($('#buscarPuestosListado').val()) || 'Sin búsqueda'),
            2
        ) + '</row>');

    sheetRows.push('<row r="5" ht="28" customHeight="1">' +
        headers.map(function(h, i) {
            return puestosExcelCell(puestosExcelCol(i) + '5', h, 3);
        }).join('') +
    '</row>');

    rows.forEach(function(row, i) {
        var rr = 6 + i;
        var values = [
            puestosValor(row.puestos_id, ''),
            puestosValor(row.nombre, ''),
            puestosEstadoActivo(row) ? 'Activo' : 'Inactivo'
        ];

        sheetRows.push('<row r="' + rr + '">' +
            values.map(function(value, c) {
                return puestosExcelCell(puestosExcelCol(c) + rr, value, 4);
            }).join('') +
        '</row>');
    });

    var totalRow = 6 + rows.length;

    sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
        puestosExcelCell('A' + totalRow, 'TOTAL DE PUESTOS: ' + rows.length, 5) +
    '</row>');

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:C' + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="1" width="16" customWidth="1"/>' +
                '<col min="2" max="2" width="42" customWidth="1"/>' +
                '<col min="3" max="3" width="18" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A5:C' + (totalRow - 1) + '"/>' +
            '<mergeCells count="4">' +
                '<mergeCell ref="A1:C1"/>' +
                '<mergeCell ref="A2:C2"/>' +
                '<mergeCell ref="A3:C3"/>' +
                '<mergeCell ref="A' + totalRow + ':C' + totalRow + '"/>' +
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
            '<sheets><sheet name="Puestos" sheetId="1" r:id="rId1"/></sheets>' +
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
        a.download = 'Reporte_Puestos.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }).catch(function(error) {
        console.error(error);
        puestosNotificar('error', 'Error', 'No se pudo generar el Excel.');
    });
}

/* =========================================================
   PDF CON PREVIEW
   ========================================================= */

function puestosObtenerLogoPdf(callback) {
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

function puestosFiltroEstadoTexto() {
    var estado = $('#form_main_puestos #estado_puestos').val();

    if (String(estado) === '1') {
        return 'Activo';
    }

    if (String(estado) === '0') {
        return 'Inactivo';
    }

    return 'Todos';
}

function puestosGenerarPdf() {
    var rows = puestosUI.filtered || [];

    if (!rows.length) {
        puestosNotificar('warning', 'Sin información', 'No hay puestos para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        puestosNotificar('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    puestosObtenerLogoPdf(function(logo) {
        var body = [[
            { text: 'CÓDIGO', style: 'th' },
            { text: 'PUESTO', style: 'th' },
            { text: 'ESTADO', style: 'th' }
        ]];

        rows.forEach(function(row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                { text: puestosValor(row.puestos_id, ''), style: 'td', fillColor: fill, alignment: 'center' },
                { text: puestosValor(row.nombre, ''), style: 'td', fillColor: fill },
                { text: puestosEstadoActivo(row) ? 'Activo' : 'Inactivo', style: 'td', fillColor: fill, alignment: 'center' }
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

        var busqueda = $.trim($('#buscarPuestosListado').val()) || 'Sin búsqueda';

        var doc = {
            pageSize: 'LETTER',
            pageOrientation: 'portrait',
            pageMargins: [28, 28, 28, 34],

            header: function() {
                return {
                    margin: [28, 12, 28, 0],
                    canvas: [{
                        type: 'line',
                        x1: 0,
                        y1: 0,
                        x2: 556,
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
                        { text: 'IZZY • Puestos', fontSize: 7, color: '#7A869A' },
                        { text: 'Página ' + page + ' de ' + pages, fontSize: 7, color: '#7A869A', alignment: 'right' }
                    ]
                };
            },

            content: [
                {
                    table: {
                        widths: [92, '*', 125],
                        body: [[
                            logoCell,
                            {
                                stack: [
                                    { text: 'PUESTOS', bold: true, fontSize: 16, color: '#FFFFFF' },
                                    { text: 'Catálogo de puestos registrados en el sistema', fontSize: 8, color: '#D8E5F0', margin: [0, 2, 0, 0] }
                                ],
                                fillColor: '#17324D',
                                margin: [0, 10, 0, 10]
                            },
                            {
                                stack: [
                                    { text: 'REPORTE EJECUTIVO', bold: true, fontSize: 6.5, color: '#72E2E5', alignment: 'right' },
                                    { text: new Date().toLocaleDateString('es-HN'), bold: true, fontSize: 9, color: '#FFFFFF', alignment: 'right' },
                                    { text: rows.length + ' puesto(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right' }
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
                            text: 'Estado: ' + puestosFiltroEstadoTexto() + '   |   Búsqueda: ' + busqueda,
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
                        widths: [80, '*', 90],
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
                        paddingTop: function() { return 5; },
                        paddingBottom: function() { return 5; }
                    }
                }
            ],

            styles: {
                th: {
                    fontSize: 8,
                    bold: true,
                    color: '#FFFFFF',
                    fillColor: '#0EA5A8',
                    alignment: 'center'
                },
                td: {
                    fontSize: 8,
                    color: '#253858',
                    noWrap: false
                }
            },

            defaultStyle: {
                fontSize: 8
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url) {
            abrirModalPdfPublico(
                url,
                'Puestos',
                'Reporte_Puestos.pdf'
            );
        });
    });
}

/* =========================================================
   INICIALIZACIÓN DE UI Y EVENTOS
   ========================================================= */

function inicializarPuestosUI() {
    puestosConfigurarPanel(
        '#btnToggleFiltrosPuestos',
        '#puestosFiltrosContenido',
        'izzy.puestos.filtros.visible'
    );

    puestosConfigurarPanel(
        '#btnToggleKpisPuestos',
        '#puestosKpisContenido',
        'izzy.puestos.kpis.visible'
    );

    var saved = 'detalle';

    try {
        saved = localStorage.getItem(PUESTOS_STORAGE_VISTA) || 'detalle';
    } catch (e) {}

    puestosUI.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
    puestosUI.view = puestosEsMovil() ? 'miniatura' : puestosUI.preferredView;

    puestosSincronizarPageSize();
    puestosSincronizarVista();

    $('#buscarPuestosListado')
        .off('input.puestosUI')
        .on('input.puestosUI', function() {
            puestosUI.search = this.value || '';
            puestosUI.page = 1;
            puestosFiltrar();
            puestosRender();
        });

    $('#limpiarBuscarPuestosListado')
        .off('click.puestosUI')
        .on('click.puestosUI', function() {
            $('#buscarPuestosListado').val('').focus();
            puestosUI.search = '';
            puestosUI.page = 1;
            puestosFiltrar();
            puestosRender();
        });

    $('#puestosPageSize')
        .off('change.puestosUI')
        .on('change.puestosUI', function() {
            var n = parseInt(this.value, 10);

            if (!n) {
                return;
            }

            puestosUI.pageSize = n;

            if (puestosUI.view === 'miniatura') {
                puestosUI.pageSizeMiniatura = n;
            } else {
                puestosUI.pageSizeDetalle = n;
            }

            puestosUI.page = 1;
            puestosRender();
        });

    $('.puestos-view-btn')
        .off('click.puestosUI')
        .on('click.puestosUI', function() {
            var vista = $(this).data('view');

            puestosUI.view = puestosEsMovil()
                ? 'miniatura'
                : (vista === 'miniatura' ? 'miniatura' : 'detalle');

            if (!puestosEsMovil()) {
                puestosUI.preferredView = puestosUI.view;

                try {
                    localStorage.setItem(PUESTOS_STORAGE_VISTA, puestosUI.preferredView);
                } catch (e) {}
            }

            puestosUI.page = 1;
            puestosSincronizarPageSize();
            puestosSincronizarVista();
            puestosRender();
        });

    $('#puestosPaginacion')
        .off('click.puestosUI', '.puestos-page-btn')
        .on('click.puestosUI', '.puestos-page-btn', function() {
            if (this.disabled) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!page) {
                return;
            }

            puestosUI.page = page;
            puestosRender();
        });

    $('#btnPuestosActualizar')
        .off('click.puestosUI')
        .on('click.puestosUI', listar_puestos);

    $('#btnPuestosIngresar')
        .off('click.puestosUI')
        .on('click.puestosUI', function() {
            if (typeof modal_puestos === 'function') {
                modal_puestos();
            } else {
                puestosNotificar('error', 'Error', 'No está disponible el formulario para ingresar puestos.');
            }
        });

    $('#btnPuestosExcel')
        .off('click.puestosUI')
        .on('click.puestosUI', puestosGenerarExcel);

    $('#btnPuestosPdf')
        .off('click.puestosUI')
        .on('click.puestosUI', puestosGenerarPdf);

    $('#puestosListado')
        .off('click.puestosUI', '.table_editar')
        .on('click.puestosUI', '.table_editar', function() {
            puestosEditar(puestosBuscarFilaPorId($(this).attr('data-puesto-id')));
        });

    $('#puestosListado')
        .off('click.puestosUI', '.table_eliminar')
        .on('click.puestosUI', '.table_eliminar', function() {
            puestosEliminar(puestosBuscarFilaPorId($(this).attr('data-puesto-id')));
        });

    $(window)
        .off('resize.puestosUI orientationchange.puestosUI')
        .on('resize.puestosUI orientationchange.puestosUI', function() {
            var target = puestosEsMovil() ? 'miniatura' : puestosUI.preferredView;

            if (puestosUI.view !== target) {
                puestosUI.view = target;
                puestosUI.page = 1;
                puestosSincronizarPageSize();
                puestosSincronizarVista();
                puestosRender();
            } else {
                puestosSincronizarVista();
            }
        });
}

$(document).ready(function() {
    inicializarPuestosUI();
    listar_puestos();

    $('#form_main_puestos #search').off('click.puestosFiltro').on('click.puestosFiltro', function(e) {
        e.preventDefault();
        listar_puestos();
    });

    $('#form_main_puestos').off('reset.puestosFiltro').on('reset.puestosFiltro', function() {
        setTimeout(function() {
            $('#form_main_puestos #estado_puestos').val('').trigger('change');
            $('#buscarPuestosListado').val('');
            puestosUI.search = '';
            puestosUI.page = 1;
            listar_puestos();
        }, 0);
    });

    $('#modal_registrar_puestos').off('shown.bs.modal.puestos').on('shown.bs.modal.puestos', function() {
        $(this).find('#formPuestos #puesto').focus();
    });

    $('#formPuestos #label_puestos_activo').html('Activo');

    $('#formPuestos .switch').off('change.puestos').on('change.puestos', function() {
        var activo = $('#formPuestos input[name=puestos_activo]').is(':checked');
        $('#formPuestos #label_puestos_activo').html(activo ? 'Activo' : 'Inactivo');
    });
});
</script>
