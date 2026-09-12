<script>
var COLABORADORES_MOBILE_QUERY = '(max-width: 767.98px)';
var COLABORADORES_STORAGE_VISTA = 'izzy.colaboradores.tipo_vista';

var colaboradoresUI = {
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

$(document).ready(function() {
    inicializarColaboradoresUI();
    listar_colaboradores();
    getEmpresaColaboradores();

    $('#form_main_colaboradores #search').off('click.colaboradores').on('click.colaboradores', function(e) {
        e.preventDefault();
        listar_colaboradores();
    });

    $('#form_main_colaboradores').off('reset.colaboradores').on('reset.colaboradores', function() {
        var form = this;
        setTimeout(function() {
            $(form).find('.selectpicker').val('').selectpicker('refresh');
            listar_colaboradores();
        }, 0);
    });
});

function colaboradoresEsMovil() {
    return window.matchMedia
        ? window.matchMedia(COLABORADORES_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function colaboradoresValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }
    return String(value).trim();
}

function colaboradoresEscape(value) {
    return colaboradoresValor(value, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function colaboradoresIniciales(nombre) {
    var partes = colaboradoresValor(nombre, '?').split(/\s+/).filter(Boolean).slice(0, 2);
    return partes.map(function(parte) {
        return parte.charAt(0).toUpperCase();
    }).join('') || '?';
}

function colaboradoresConfigurarPanel(btn, contenido, key) {
    var visible = true;

    try {
        var saved = localStorage.getItem(key);
        if (saved !== null) visible = saved === '1';
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

    $(btn).off('click.colaboradoresPanel').on('click.colaboradoresPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function colaboradoresSincronizarPageSize() {
    var mini = colaboradoresUI.view === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? colaboradoresUI.pageSizeMiniatura : colaboradoresUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    var $select = $('#colaboradoresPageSize').empty();

    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });

    colaboradoresUI.pageSize = preferido;
    $select.val(String(preferido));
}

function colaboradoresSincronizarVista() {
    var movil = colaboradoresEsMovil();

    $('.colaboradores-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil);

    $('.colaboradores-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.colaboradores-view-btn[data-view="' + colaboradoresUI.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function colaboradoresFiltrar() {
    var q = $.trim(colaboradoresUI.search || '').toLowerCase();

    colaboradoresUI.filtered = !q
        ? colaboradoresUI.rows.slice()
        : colaboradoresUI.rows.filter(function(row) {
            return [
                row.empresa,
                row.colaborador,
                row.identidad,
                row.telefono,
                row.puesto,
                Number(row.estado) === 1 ? 'activo' : 'inactivo'
            ].map(function(v) {
                return colaboradoresValor(v, '').toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    colaboradoresActualizarKpis();
}

function colaboradoresActualizarKpis() {
    var activos = 0;
    var inactivos = 0;
    var empresas = {};

    colaboradoresUI.filtered.forEach(function(row) {
        if (Number(row.estado) === 1) activos++;
        else inactivos++;

        var empresa = colaboradoresValor(row.empresa, '').toLowerCase();
        if (empresa) empresas[empresa] = true;
    });

    $('#colaboradoresKpiTotal').text(colaboradoresUI.filtered.length);
    $('#colaboradoresKpiActivos').text(activos);
    $('#colaboradoresKpiInactivos').text(inactivos);
    $('#colaboradoresKpiEmpresas').text(Object.keys(empresas).length);
}

function colaboradoresEstadoBadge(estado) {
    if (Number(estado) === 1) {
        return '<span class="colaboradores-status colaboradores-status-activo">' +
            '<i class="fas fa-check-circle"></i> Activo</span>';
    }

    return '<span class="colaboradores-status colaboradores-status-inactivo">' +
        '<i class="fas fa-times-circle"></i> Inactivo</span>';
}

function colaboradoresAcciones(row, index) {
    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar js-colaborador-editar" data-index="' + index + '">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar js-colaborador-eliminar" data-index="' + index + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function colaboradoresRenderDetalle(rows, offset) {
    var html = '' +
        '<div class="colaboradores-detail-header">' +
            '<div>Colaborador</div>' +
            '<div>Empresa</div>' +
            '<div>Identidad</div>' +
            '<div>Teléfono</div>' +
            '<div>Puesto</div>' +
            '<div>Estado</div>' +
            '<div>Acciones</div>' +
        '</div>';

    rows.forEach(function(row, i) {
        var index = offset + i;

        html += '' +
            '<article class="colaboradores-detail-row">' +
                '<div class="colaboradores-cell">' +
                    '<span class="colaboradores-cell-label">Colaborador</span>' +
                    '<div class="colaboradores-person">' +
                        '<span class="colaboradores-avatar">' + colaboradoresEscape(colaboradoresIniciales(row.colaborador)) + '</span>' +
                        '<div>' +
                            '<strong>' + colaboradoresEscape(colaboradoresValor(row.colaborador, 'Sin nombre')) + '</strong>' +
                            '<small><i class="fas fa-id-badge mr-1"></i>Colaborador</small>' +
                        '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="colaboradores-cell">' +
                    '<span class="colaboradores-cell-label">Empresa</span>' +
                    '<div class="colaboradores-stack">' +
                        '<strong>' + colaboradoresEscape(colaboradoresValor(row.empresa, 'Sin empresa')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="colaboradores-cell">' +
                    '<span class="colaboradores-cell-label">Identidad</span>' +
                    '<span class="colaboradores-data-chip"><i class="fas fa-id-card"></i>' +
                        colaboradoresEscape(colaboradoresValor(row.identidad, 'No registrada')) +
                    '</span>' +
                '</div>' +

                '<div class="colaboradores-cell">' +
                    '<span class="colaboradores-cell-label">Teléfono</span>' +
                    '<span class="colaboradores-data-chip"><i class="fas fa-phone-alt"></i>' +
                        colaboradoresEscape(colaboradoresValor(row.telefono, 'No registrado')) +
                    '</span>' +
                '</div>' +

                '<div class="colaboradores-cell">' +
                    '<span class="colaboradores-cell-label">Puesto</span>' +
                    '<strong class="colaboradores-puesto">' + colaboradoresEscape(colaboradoresValor(row.puesto, 'Sin puesto')) + '</strong>' +
                '</div>' +

                '<div class="colaboradores-cell colaboradores-center">' +
                    '<span class="colaboradores-cell-label">Estado</span>' +
                    colaboradoresEstadoBadge(row.estado) +
                '</div>' +

                '<div class="colaboradores-cell colaboradores-center">' +
                    '<span class="colaboradores-cell-label">Acciones</span>' +
                    colaboradoresAcciones(row, index) +
                '</div>' +
            '</article>';
    });

    return html;
}

function colaboradoresRenderMiniatura(rows, offset) {
    var html = '<div class="colaboradores-mini-grid">';

    rows.forEach(function(row, i) {
        var index = offset + i;

        html += '' +
            '<article class="colaboradores-mini-card">' +
                '<div class="colaboradores-mini-topline"></div>' +

                '<div class="colaboradores-mini-header">' +
                    '<span class="colaboradores-avatar colaboradores-avatar-large">' +
                        colaboradoresEscape(colaboradoresIniciales(row.colaborador)) +
                    '</span>' +
                    '<div class="colaboradores-mini-title">' +
                        '<h4>' + colaboradoresEscape(colaboradoresValor(row.colaborador, 'Sin nombre')) + '</h4>' +
                        '<span><i class="fas fa-building mr-1"></i>' +
                            colaboradoresEscape(colaboradoresValor(row.empresa, 'Sin empresa')) +
                        '</span>' +
                    '</div>' +
                    colaboradoresEstadoBadge(row.estado) +
                '</div>' +

                '<div class="colaboradores-mini-body">' +
                    '<div class="colaboradores-mini-field">' +
                        '<span>Identidad</span>' +
                        '<strong>' + colaboradoresEscape(colaboradoresValor(row.identidad, 'No registrada')) + '</strong>' +
                    '</div>' +
                    '<div class="colaboradores-mini-field">' +
                        '<span>Teléfono</span>' +
                        '<strong>' + colaboradoresEscape(colaboradoresValor(row.telefono, 'No registrado')) + '</strong>' +
                    '</div>' +
                    '<div class="colaboradores-mini-field colaboradores-mini-field-full">' +
                        '<span>Puesto</span>' +
                        '<strong>' + colaboradoresEscape(colaboradoresValor(row.puesto, 'Sin puesto')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="colaboradores-mini-footer">' +
                    colaboradoresAcciones(row, index) +
                '</div>' +
            '</article>';
    });

    return html + '</div>';
}

function colaboradoresRenderPaginacion(totalPages) {
    var current = colaboradoresUI.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="colaboradores-page-btn' + (active ? ' active' : '') + '" data-page="' + page + '"' +
            (disabled ? ' disabled' : '') + '>' +
            (icon ? '<i class="' + icon + ' mr-1"></i>' : '') +
            label +
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

    $('#colaboradoresPaginacion').html(html);
}

function colaboradoresRender() {
    var rows = colaboradoresUI.filtered || [];

    if (colaboradoresUI.loading) {
        $('#colaboradoresListado').html(
            '<div class="colaboradores-state">' +
                '<i class="fas fa-spinner fa-spin"></i>' +
                '<strong>Cargando colaboradores</strong>' +
                '<span>Espere mientras consultamos la información.</span>' +
            '</div>'
        );
        $('#colaboradoresInfo').text('0 registros');
        $('#colaboradoresPaginacion').empty();
        return;
    }

    if (!rows.length) {
        $('#colaboradoresListado').html(
            '<div class="colaboradores-state">' +
                '<i class="fas fa-users"></i>' +
                '<strong>Sin colaboradores</strong>' +
                '<span>No se encontraron registros con los filtros actuales.</span>' +
            '</div>'
        );
        $('#colaboradoresInfo').text('0 registros');
        $('#colaboradoresPaginacion').empty();
        return;
    }

    var totalPages = Math.max(1, Math.ceil(rows.length / colaboradoresUI.pageSize));

    if (colaboradoresUI.page > totalPages) {
        colaboradoresUI.page = totalPages;
    }

    var offset = (colaboradoresUI.page - 1) * colaboradoresUI.pageSize;
    var pageRows = rows.slice(offset, offset + colaboradoresUI.pageSize);

    $('#colaboradoresListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + colaboradoresUI.view)
        .html(
            colaboradoresUI.view === 'miniatura'
                ? colaboradoresRenderMiniatura(pageRows, offset)
                : colaboradoresRenderDetalle(pageRows, offset)
        );

    var end = Math.min(offset + pageRows.length, rows.length);

    $('#colaboradoresInfo').text(
        'Mostrando ' + (offset + 1) + ' a ' + end + ' de ' + rows.length + ' registros'
    );

    colaboradoresRenderPaginacion(totalPages);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

var listar_colaboradores = function() {
    var estado = $('#form_main_colaboradores #estado_colaboradores').val();

    colaboradoresUI.loading = true;
    colaboradoresRender();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableColaboradores.php',
        dataType: 'json',
        data: {
            estado: estado
        }
    }).done(function(json) {
        colaboradoresUI.rows = json && Array.isArray(json.data) ? json.data : [];
        colaboradoresUI.search = $('#buscarColaboradores').val() || '';
        colaboradoresUI.page = 1;
        colaboradoresUI.loading = false;

        colaboradoresFiltrar();
        colaboradoresRender();
    }).fail(function(xhr) {
        colaboradoresUI.rows = [];
        colaboradoresUI.filtered = [];
        colaboradoresUI.loading = false;

        colaboradoresActualizarKpis();
        colaboradoresRender();

        console.error('Error colaboradores:', xhr.responseText);

        if (typeof showNotify === 'function') {
            showNotify('error', 'Error', 'No se pudo cargar el listado de colaboradores.');
        }
    });
};

function editar_colaborador_ui(data) {
    var url = '<?php echo SERVERURL;?>core/editarColaboradores.php';

    $('#formColaboradores')[0].reset();
    $('#formColaboradores #colaborador_id').val(data.colaborador_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formColaboradores').serialize(),
        success: function(registro) {
            var valores = eval(registro);

            $('#formColaboradores').attr({
                'data-form': 'update'
            });

            $('#formColaboradores').attr({
                'action': '<?php echo SERVERURL;?>ajax/modificarColaboradorAjax.php'
            });

            $('#reg_colaborador').hide();
            $('#edi_colaborador').show();
            $('#delete_colaborador').hide();

            $('#formColaboradores #nombre_colaborador').val(valores[0]);
            $('#formColaboradores #identidad_colaborador').val(valores[1]);
            $('#formColaboradores #telefono_colaborador').val(valores[2]);
            $('#formColaboradores #puesto_colaborador').val(valores[3]);
            $('#formColaboradores #puesto_colaborador').selectpicker('refresh');

            $('#formColaboradores #colaborador_empresa_id').val(valores[4]);
            $('#formColaboradores #colaborador_empresa_id').selectpicker('refresh');

            $('#formColaboradores #fecha_ingreso_colaborador').val(valores[7]);
            $('#formColaboradores #fecha_egreso_colaborador').val(valores[8]);

            if (valores[5] == 1) {
                $('#formColaboradores #colaboradores_activo').prop('checked', true);
            } else {
                $('#formColaboradores #colaboradores_activo').prop('checked', false);
            }

            $('#formColaboradores #nombre_colaborador').prop('readonly', false);
            $('#formColaboradores #identidad_colaborador').prop('readonly', false);
            $('#formColaboradores #telefono_colaborador').prop('readonly', false);
            $('#formColaboradores #estado_colaborador').prop('disabled', false);
            $('#formColaboradores #colaboradores_activo').prop('disabled', false);
            $('#formColaboradores #fecha_ingreso_colaborador').prop('disabled', false);
            $('#formColaboradores #fecha_egreso_colaborador').prop('disabled', false);
            $('#formColaboradores #puesto_colaborador').prop('disabled', false);
            $('#formColaboradores #colaborador_empresa_id').prop('disabled', false);
            $('#formColaboradores #estado_colaboradores').show();

            $('#datosClientes').hide();
            $('#formColaboradores #estado_colaborador').show();

            $('#formColaboradores #proceso_colaboradores').val('Editar');

            $('#modal_registrar_colaboradores').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr, status, error) {
            console.error('Error al editar colaborador:', error);
            showNotify('error', 'Error', 'No se pudo cargar la información del colaborador.');
        }
    });
}

function eliminar_colaborador_ui(data) {
    var colaborador_id = data.colaborador_id;
    var nombre = data.colaborador;
    var empresa = data.empresa;

    var mensajeHTML =
        '¿Desea eliminar permanentemente el usuario?<br><br>' +
        '<strong>Nombre:</strong> ' + colaboradoresEscape(nombre) + '<br>' +
        '<strong>Empresa:</strong> ' + colaboradoresEscape(empresa);

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
        if (!confirmar) return;

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarColaboradorAjax.php',
            data: {
                colaborador_id: colaborador_id
            },
            dataType: 'json',
            beforeSend: function() {
                if (typeof showLoading === 'function') {
                    showLoading('Eliminando registro...');
                }
            },
            success: function(response) {
                swal.close();

                if (response.status === 'success') {
                    showNotify('success', response.title, response.message);
                    listar_colaboradores();
                } else {
                    showNotify('error', response.title, response.message);
                }
            },
            error: function(xhr, status, error) {
                swal.close();
                showNotify('error', 'Error', 'Ocurrió un error al procesar la solicitud');
                console.error('Error en la solicitud AJAX:', error);
            }
        });
    });
}

function colaboradoresExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function colaboradoresExcelCol(index) {
    var name = '';

    while (index >= 0) {
        name = String.fromCharCode((index % 26) + 65) + name;
        index = Math.floor(index / 26) - 1;
    }

    return name;
}

function colaboradoresExcelCell(ref, value, style) {
    return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
        colaboradoresExcelEscape(value) +
        '</t></is></c>';
}

function colaboradoresGenerarExcel() {
    var rows = colaboradoresUI.filtered || [];

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay colaboradores para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
        return;
    }

    var headers = ['Empresa', 'Colaborador', 'Identidad', 'Teléfono', 'Puesto', 'Estado'];
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            colaboradoresExcelCell('A1', 'IZZY • REPORTE DE COLABORADORES', 1) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2">' +
            colaboradoresExcelCell(
                'A2',
                'Generado: ' + new Date().toLocaleDateString('es-HN') +
                ' • Registros: ' + rows.length,
                2
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="26" customHeight="1">' +
            headers.map(function(header, i) {
                return colaboradoresExcelCell(colaboradoresExcelCol(i) + '4', header, 3);
            }).join('') +
        '</row>'
    );

    rows.forEach(function(row, i) {
        var rr = 5 + i;
        var values = [
            colaboradoresValor(row.empresa, ''),
            colaboradoresValor(row.colaborador, ''),
            colaboradoresValor(row.identidad, ''),
            colaboradoresValor(row.telefono, ''),
            colaboradoresValor(row.puesto, ''),
            Number(row.estado) === 1 ? 'Activo' : 'Inactivo'
        ];

        sheetRows.push(
            '<row r="' + rr + '">' +
                values.map(function(value, c) {
                    return colaboradoresExcelCell(colaboradoresExcelCol(c) + rr, value, 4);
                }).join('') +
            '</row>'
        );
    });

    var lastRow = Math.max(4, 4 + rows.length);

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:F' + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="2" width="28" customWidth="1"/>' +
                '<col min="3" max="4" width="20" customWidth="1"/>' +
                '<col min="5" max="5" width="24" customWidth="1"/>' +
                '<col min="6" max="6" width="14" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A4:F' + lastRow + '"/>' +
            '<mergeCells count="2">' +
                '<mergeCell ref="A1:F1"/>' +
                '<mergeCell ref="A2:F2"/>' +
            '</mergeCells>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="5">' +
                '<font><sz val="10"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="4">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
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
            '<cellXfs count="5">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Colaboradores" sheetId="1" r:id="rId1"/></sheets>' +
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

    var options = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promise = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(options)
        : (typeof zip.generate === 'function'
            ? Promise.resolve(zip.generate(options))
            : Promise.reject(new Error('JSZip no soportado')));

    promise.then(function(blob) {
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');

        link.href = url;
        link.download = 'Reporte_Colaboradores.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }).catch(function(error) {
        console.error(error);
        showNotify('error', 'Error', 'No se pudo generar el Excel.');
    });
}

function colaboradoresObtenerLogoPdf(callback) {
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

function colaboradoresGenerarPdf() {
    var rows = colaboradoresUI.filtered || [];

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay colaboradores para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
        showNotify('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
        return;
    }

    colaboradoresObtenerLogoPdf(function(logo) {
        var body = [[
            {text: 'EMPRESA', style: 'th'},
            {text: 'COLABORADOR', style: 'th'},
            {text: 'IDENTIDAD', style: 'th'},
            {text: 'TELÉFONO', style: 'th'},
            {text: 'PUESTO', style: 'th'},
            {text: 'ESTADO', style: 'th'}
        ]];

        rows.forEach(function(row, i) {
            var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

            body.push([
                {text: colaboradoresValor(row.empresa, ''), style: 'td', fillColor: fill},
                {text: colaboradoresValor(row.colaborador, ''), style: 'td', fillColor: fill},
                {text: colaboradoresValor(row.identidad, ''), style: 'td', fillColor: fill},
                {text: colaboradoresValor(row.telefono, ''), style: 'td', fillColor: fill},
                {text: colaboradoresValor(row.puesto, ''), style: 'td', fillColor: fill},
                {
                    text: Number(row.estado) === 1 ? 'Activo' : 'Inactivo',
                    style: 'td',
                    fillColor: fill,
                    color: Number(row.estado) === 1 ? '#14804A' : '#C9372C',
                    bold: true
                }
            ]);
        });

        var headerCells = [];

        if (logo) {
            headerCells.push({
                table: {
                    widths: ['*'],
                    body: [[{
                        image: logo,
                        fit: [64, 38],
                        alignment: 'center',
                        margin: [7, 5, 7, 5],
                        fillColor: '#FFFFFF'
                    }]]
                },
                layout: 'noBorders',
                fillColor: '#17324D',
                margin: [8, 7, 8, 7]
            });
        } else {
            headerCells.push({
                text: 'IZZY',
                bold: true,
                fontSize: 18,
                color: '#17324D',
                alignment: 'center',
                fillColor: '#FFFFFF',
                margin: [8, 14, 8, 14]
            });
        }

        headerCells.push({
            stack: [
                {text: 'REPORTE DE COLABORADORES', bold: true, fontSize: 16, color: '#FFFFFF'},
                {text: 'Directorio laboral y estado de colaboradores', fontSize: 8, color: '#D8E5F0', margin: [0, 2, 0, 0]}
            ],
            fillColor: '#17324D',
            margin: [0, 10, 0, 10]
        });

        headerCells.push({
            stack: [
                {text: 'REPORTE EJECUTIVO', bold: true, fontSize: 6.5, color: '#72E2E5', alignment: 'right'},
                {text: new Date().toLocaleDateString('es-HN'), bold: true, fontSize: 9, color: '#FFFFFF', alignment: 'right'},
                {text: rows.length + ' registro(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right'}
            ],
            fillColor: '#17324D',
            margin: [0, 10, 12, 10]
        });

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
                        {text: 'IZZY • Colaboradores', fontSize: 7, color: '#7A869A'},
                        {text: 'Página ' + page + ' de ' + pages, fontSize: 7, color: '#7A869A', alignment: 'right'}
                    ]
                };
            },

            content: [
                {
                    table: {
                        widths: [100, '*', 160],
                        body: [headerCells]
                    },
                    layout: 'noBorders',
                    margin: [0, 0, 0, 12]
                },
                {
                    table: {
                        widths: ['*'],
                        body: [[{
                            text:
                                'Filtro Estado: ' +
                                ($('#estado_colaboradores option:selected').text() || 'Todos') +
                                ' | Búsqueda: ' +
                                ($.trim($('#buscarColaboradores').val()) || 'Sin búsqueda'),
                            fontSize: 7,
                            color: '#52627A',
                            fillColor: '#F7F9FC',
                            margin: [8, 7, 8, 7]
                        }]]
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 0, 0, 12]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: [125, 150, 105, 90, 120, 70],
                        body: body
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return 0.55; },
                        vLineWidth: function() { return 0.55; },
                        paddingLeft: function() { return 5; },
                        paddingRight: function() { return 5; },
                        paddingTop: function() { return 6; },
                        paddingBottom: function() { return 6; }
                    }
                }
            ],

            styles: {
                th: {
                    fontSize: 6.4,
                    bold: true,
                    color: '#FFFFFF',
                    fillColor: '#17324D',
                    alignment: 'center'
                },
                td: {
                    fontSize: 6.4,
                    color: '#253858'
                }
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url) {
            abrirModalPdfPublico(
                url,
                'Reporte de Colaboradores',
                'Reporte_Colaboradores.pdf'
            );
        });
    });
}

function inicializarColaboradoresUI() {
    colaboradoresConfigurarPanel(
        '#btnToggleFiltrosColaboradores',
        '#colaboradoresFiltrosContenido',
        'izzy.colaboradores.filtros.visible'
    );

    colaboradoresConfigurarPanel(
        '#btnToggleKpisColaboradores',
        '#colaboradoresKpisContenido',
        'izzy.colaboradores.kpis.visible'
    );

    var savedView = 'detalle';

    try {
        savedView = localStorage.getItem(COLABORADORES_STORAGE_VISTA) || 'detalle';
    } catch (e) {}

    colaboradoresUI.preferredView = savedView === 'miniatura' ? 'miniatura' : 'detalle';
    colaboradoresUI.view = colaboradoresEsMovil() ? 'miniatura' : colaboradoresUI.preferredView;

    colaboradoresSincronizarPageSize();
    colaboradoresSincronizarVista();

    $('#buscarColaboradores')
        .off('input.colaboradoresUI')
        .on('input.colaboradoresUI', function() {
            colaboradoresUI.search = this.value || '';
            colaboradoresUI.page = 1;
            colaboradoresFiltrar();
            colaboradoresRender();
        });

    $('#limpiarBuscarColaboradores')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', function() {
            $('#buscarColaboradores').val('').focus();
            colaboradoresUI.search = '';
            colaboradoresUI.page = 1;
            colaboradoresFiltrar();
            colaboradoresRender();
        });

    $('#colaboradoresPageSize')
        .off('change.colaboradoresUI')
        .on('change.colaboradoresUI', function() {
            var n = parseInt(this.value, 10);

            if (!n) return;

            colaboradoresUI.pageSize = n;

            if (colaboradoresUI.view === 'miniatura') {
                colaboradoresUI.pageSizeMiniatura = n;
            } else {
                colaboradoresUI.pageSizeDetalle = n;
            }

            colaboradoresUI.page = 1;
            colaboradoresRender();
        });

    $('.colaboradores-view-btn')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', function() {
            var requested = $(this).data('view');

            colaboradoresUI.view = colaboradoresEsMovil()
                ? 'miniatura'
                : (requested === 'miniatura' ? 'miniatura' : 'detalle');

            if (!colaboradoresEsMovil()) {
                colaboradoresUI.preferredView = colaboradoresUI.view;

                try {
                    localStorage.setItem(
                        COLABORADORES_STORAGE_VISTA,
                        colaboradoresUI.preferredView
                    );
                } catch (e) {}
            }

            colaboradoresUI.page = 1;
            colaboradoresSincronizarPageSize();
            colaboradoresSincronizarVista();
            colaboradoresRender();
        });

    $('#colaboradoresPaginacion')
        .off('click.colaboradoresUI', '.colaboradores-page-btn')
        .on('click.colaboradoresUI', '.colaboradores-page-btn', function() {
            if (this.disabled) return;

            var page = parseInt($(this).data('page'), 10);

            if (!page) return;

            colaboradoresUI.page = page;
            colaboradoresRender();
        });

    $('#colaboradoresListado')
        .off('click.colaboradoresUI', '.js-colaborador-editar')
        .on('click.colaboradoresUI', '.js-colaborador-editar', function() {
            var row = colaboradoresUI.filtered[parseInt($(this).data('index'), 10)];

            if (row) editar_colaborador_ui(row);
        })
        .off('click.colaboradoresDelete', '.js-colaborador-eliminar')
        .on('click.colaboradoresDelete', '.js-colaborador-eliminar', function() {
            var row = colaboradoresUI.filtered[parseInt($(this).data('index'), 10)];

            if (row) eliminar_colaborador_ui(row);
        });

    $('#btnColaboradoresActualizar')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', listar_colaboradores);

    $('#btnColaboradoresIngresar')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', function() {
            if (typeof modal_colaboradores === 'function') {
                modal_colaboradores();
            } else {
                showNotify('error', 'Acción no disponible', 'No se encontró la función para registrar colaboradores.');
            }
        });

    $('#btnColaboradoresExcel')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', colaboradoresGenerarExcel);

    $('#btnColaboradoresPdf')
        .off('click.colaboradoresUI')
        .on('click.colaboradoresUI', colaboradoresGenerarPdf);

    $(window)
        .off('resize.colaboradoresUI orientationchange.colaboradoresUI')
        .on('resize.colaboradoresUI orientationchange.colaboradoresUI', function() {
            var targetView = colaboradoresEsMovil()
                ? 'miniatura'
                : colaboradoresUI.preferredView;

            if (colaboradoresUI.view !== targetView) {
                colaboradoresUI.view = targetView;
                colaboradoresUI.page = 1;
                colaboradoresSincronizarPageSize();
                colaboradoresSincronizarVista();
                colaboradoresRender();
            } else {
                colaboradoresSincronizarVista();
            }
        });
}

$(document).ready(function() {
    $("#modal_registrar_colaboradores").on('shown.bs.modal', function() {
        $(this).find('#formColaboradores #nombre_colaborador').focus();
    });
});

$('#formColaboradores #label_colaboradores_activo').html("Activo");

$('#formColaboradores .switch').change(function() {
    if ($('input[name=colaboradores_activo]').is(':checked')) {
        $('#formColaboradores #label_colaboradores_activo').html("Activo");
        return true;
    } else {
        $('#formColaboradores #label_colaboradores_activo').html("Inactivo");
        return false;
    }
});
</script>