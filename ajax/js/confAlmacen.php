<script>
/* =========================================================
   IZZY 6.0 | CONFIGURACIÓN DE ALMACÉN
   Listado DIV/Cards con Detalle + Miniatura.
   Mantiene endpoints, permisos, modal y reglas existentes.
   ========================================================= */

var ALMACEN_MOBILE_QUERY = '(max-width: 767.98px)';
var ALMACEN_STORAGE_VISTA = 'izzy.almacen.tipo_vista';

var almacenUI = {
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

function almacenEsMovil() {
    return window.matchMedia
        ? window.matchMedia(ALMACEN_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function almacenValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(value).trim();
}

function almacenEscape(value) {
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

function almacenNotificar(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    }
}

function almacenConfigurarPanel(btn, contenido, key) {
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

    $(btn).off('click.almacenPanel').on('click.almacenPanel', function() {
        visible = !visible;
        $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();

        try {
            localStorage.setItem(key, visible ? '1' : '0');
        } catch (e) {}
    });
}

function almacenEstadoActivo(row) {
    return parseInt(row && row.estado, 10) === 1;
}

function almacenFacturarCeroActivo(row) {
    var value = row ? row.facturarCero : null;

    if (value === true || value === 1) {
        return true;
    }

    var text = String(value == null ? '' : value).trim().toLowerCase();

    return text === '1' ||
        text === 'si' ||
        text === 'sí' ||
        text === 'true' ||
        text === 'activo' ||
        text === 'permitido';
}

function almacenEstadoHtml(row) {
    if (almacenEstadoActivo(row)) {
        return '<span class="almacen-status-badge almacen-status-active">' +
            '<i class="fas fa-check-circle"></i>Activo</span>';
    }

    return '<span class="almacen-status-badge almacen-status-inactive">' +
        '<i class="fas fa-times-circle"></i>Inactivo</span>';
}

function almacenFacturarCeroHtml(row) {
    if (almacenFacturarCeroActivo(row)) {
        return '<span class="almacen-zero-badge almacen-zero-active">' +
            '<i class="fas fa-check-circle"></i>Sí</span>';
    }

    return '<span class="almacen-zero-badge almacen-zero-inactive">' +
        '<i class="fas fa-minus-circle"></i>No</span>';
}

function almacenSincronizarPageSize() {
    var mini = almacenUI.view === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var preferido = mini ? almacenUI.pageSizeMiniatura : almacenUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) {
        preferido = opciones[0];
    }

    var $select = $('#almacenPageSize').empty();

    opciones.forEach(function(n) {
        $select.append($('<option></option>').val(n).text(n));
    });

    almacenUI.pageSize = preferido;
    $select.val(String(preferido));
}

function almacenSincronizarVista() {
    var movil = almacenEsMovil();

    if (movil) {
        almacenUI.view = 'miniatura';
    }

    $('.almacen-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.almacen-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.almacen-view-btn[data-view="' + almacenUI.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function almacenFiltrar() {
    var q = $.trim(almacenUI.search || '').toLowerCase();

    almacenUI.filtered = !q
        ? almacenUI.rows.slice()
        : almacenUI.rows.filter(function(row) {
            var estadoTexto = almacenEstadoActivo(row) ? 'activo' : 'inactivo';
            var ceroTexto = almacenFacturarCeroActivo(row) ? 'si sí permitido facturar cero' : 'no';

            return [
                row.almacen_id,
                row.empresa,
                row.almacen,
                row.facturarCero,
                row.ubicacion,
                estadoTexto,
                ceroTexto
            ].map(function(v) {
                return almacenValor(v, '').toLowerCase();
            }).join(' ').indexOf(q) !== -1;
        });

    almacenActualizarResumen();
}

function almacenActualizarResumen() {
    var activos = 0;
    var facturanCero = 0;
    var empresas = {};

    almacenUI.filtered.forEach(function(row) {
        if (almacenEstadoActivo(row)) {
            activos++;
        }

        if (almacenFacturarCeroActivo(row)) {
            facturanCero++;
        }

        var empresa = $.trim(almacenValor(row.empresa, ''));
        if (empresa) {
            empresas[empresa.toLowerCase()] = true;
        }
    });

    $('#almacen-card-total').text(almacenUI.filtered.length);
    $('#almacen-card-activos').text(activos);
    $('#almacen-card-cero').text(facturanCero);
    $('#almacen-card-empresas').text(Object.keys(empresas).length);
}

function almacenAccionesHtml(row) {
    var id = almacenEscape(almacenValor(row.almacen_id, ''));

    return '' +
        '<div class="dropdown acciones-dropdown almacen-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar" data-almacen-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar" data-almacen-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function almacenRenderDetalle(rows) {
    var html = '' +
        '<div class="almacen-detail-header">' +
            '<div>Acciones</div>' +
            '<div>Empresa</div>' +
            '<div>Almacén</div>' +
            '<div>Facturar en cero</div>' +
            '<div>Ubicación</div>' +
            '<div>Estado</div>' +
        '</div>';

    rows.forEach(function(row) {
        html += '' +
            '<article class="almacen-detail-row">' +
                '<div class="almacen-cell almacen-actions-cell">' +
                    '<span class="almacen-cell-label">Acciones</span>' +
                    almacenAccionesHtml(row) +
                '</div>' +

                '<div class="almacen-cell">' +
                    '<span class="almacen-cell-label">Empresa</span>' +
                    '<div class="almacen-main-info">' +
                        '<span class="almacen-main-icon"><i class="fas fa-building"></i></span>' +
                        '<strong>' + almacenEscape(almacenValor(row.empresa, 'Sin empresa')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="almacen-cell">' +
                    '<span class="almacen-cell-label">Almacén</span>' +
                    '<div class="almacen-name-box">' +
                        '<i class="fas fa-warehouse"></i>' +
                        '<strong>' + almacenEscape(almacenValor(row.almacen, 'Sin nombre')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="almacen-cell almacen-center">' +
                    '<span class="almacen-cell-label">Facturar en cero</span>' +
                    almacenFacturarCeroHtml(row) +
                '</div>' +

                '<div class="almacen-cell">' +
                    '<span class="almacen-cell-label">Ubicación</span>' +
                    '<div class="almacen-location-box">' +
                        '<i class="fas fa-map-marker-alt"></i>' +
                        '<span>' + almacenEscape(almacenValor(row.ubicacion, 'Sin ubicación')) + '</span>' +
                    '</div>' +
                '</div>' +

                '<div class="almacen-cell almacen-center">' +
                    '<span class="almacen-cell-label">Estado</span>' +
                    almacenEstadoHtml(row) +
                '</div>' +
            '</article>';
    });

    return html;
}

function almacenRenderMiniatura(rows) {
    var html = '<div class="almacen-mini-grid">';

    rows.forEach(function(row) {
        html += '' +
            '<article class="almacen-mini-card">' +
                '<div class="almacen-mini-topline"></div>' +

                '<div class="almacen-mini-header">' +
                    '<span class="almacen-mini-icon"><i class="fas fa-warehouse"></i></span>' +
                    '<div class="almacen-mini-title">' +
                        '<h4>' + almacenEscape(almacenValor(row.almacen, 'Sin nombre')) + '</h4>' +
                        '<span><i class="fas fa-building mr-1"></i>' +
                            almacenEscape(almacenValor(row.empresa, 'Sin empresa')) +
                        '</span>' +
                    '</div>' +
                    almacenEstadoHtml(row) +
                '</div>' +

                '<div class="almacen-mini-body">' +
                    '<div class="almacen-mini-field">' +
                        '<span><i class="fas fa-file-invoice-dollar"></i> Facturar en cero</span>' +
                        almacenFacturarCeroHtml(row) +
                    '</div>' +

                    '<div class="almacen-mini-field">' +
                        '<span><i class="fas fa-hashtag"></i> Código</span>' +
                        '<strong>' + almacenEscape(almacenValor(row.almacen_id, 'Sin código')) + '</strong>' +
                    '</div>' +

                    '<div class="almacen-mini-field almacen-mini-field-full">' +
                        '<span><i class="fas fa-map-marker-alt"></i> Ubicación</span>' +
                        '<strong>' + almacenEscape(almacenValor(row.ubicacion, 'Sin ubicación')) + '</strong>' +
                    '</div>' +
                '</div>' +

                '<div class="almacen-mini-footer">' +
                    almacenAccionesHtml(row) +
                '</div>' +
            '</article>';
    });

    return html + '</div>';
}

function almacenRenderPaginacion(totalPages) {
    var current = almacenUI.page;
    var html = '';

    function button(label, page, disabled, active, icon) {
        return '<button type="button" class="almacen-page-btn' + (active ? ' active' : '') +
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

    $('#almacenPaginacion').html(html);
}

function almacenAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function almacenRender() {
    var rows = almacenUI.filtered || [];

    if (almacenUI.loading) {
        $('#almacenListado').html(
            '<div class="almacen-state"><i class="fas fa-spinner fa-spin"></i>' +
            '<strong>Cargando almacenes</strong><span>Consultando información...</span></div>'
        );
        $('#almacenInfo').text('0 registros');
        $('#almacenPaginacion').empty();
        almacenAplicarPermisos();
        return;
    }

    if (!rows.length) {
        $('#almacenListado').html(
            '<div class="almacen-state"><i class="fas fa-warehouse"></i>' +
            '<strong>Sin registros</strong><span>No se encontraron almacenes con los filtros actuales.</span></div>'
        );
        $('#almacenInfo').text('0 registros');
        $('#almacenPaginacion').empty();
        almacenAplicarPermisos();
        return;
    }

    var pages = Math.max(1, Math.ceil(rows.length / almacenUI.pageSize));

    if (almacenUI.page > pages) {
        almacenUI.page = pages;
    }

    var offset = (almacenUI.page - 1) * almacenUI.pageSize;
    var pageRows = rows.slice(offset, offset + almacenUI.pageSize);

    $('#almacenListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-' + almacenUI.view)
        .html(
            almacenUI.view === 'miniatura'
                ? almacenRenderMiniatura(pageRows)
                : almacenRenderDetalle(pageRows)
        );

    $('#almacenInfo').text(
        'Mostrando ' + (offset + 1) + ' a ' +
        Math.min(offset + pageRows.length, rows.length) +
        ' de ' + rows.length + ' registros'
    );

    almacenRenderPaginacion(pages);
    almacenAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function almacenBuscarFilaPorId(id) {
    var idTexto = String(id == null ? '' : id);
    var row = null;

    almacenUI.rows.some(function(item) {
        if (String(item.almacen_id) === idTexto) {
            row = item;
            return true;
        }

        return false;
    });

    return row;
}

/* =========================================================
   CARGA DEL LISTADO
   ========================================================= */

var listar_almacen = function() {
    var estado = $('#form_main_almacen #estado_almacen').val();

    almacenUI.loading = true;
    almacenRender();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableAlmacen.php',
        dataType: 'json',
        data: {
            estado: estado
        }
    }).done(function(json) {
        almacenUI.rows = json && Array.isArray(json.data) ? json.data : [];
        almacenUI.search = $('#buscarAlmacenListado').val() || '';
        almacenUI.page = 1;
        almacenUI.loading = false;

        almacenFiltrar();
        almacenRender();

        if (!almacenUI.rows.length) {
            almacenNotificar('warning', 'Sin registros', 'No se encontraron almacenes con los filtros aplicados.');
        }
    }).fail(function(xhr) {
        almacenUI.rows = [];
        almacenUI.filtered = [];
        almacenUI.loading = false;

        almacenActualizarResumen();
        almacenRender();

        almacenNotificar('error', 'Error', 'No se pudo cargar el listado de almacenes.');
        console.error('Error en AJAX Almacén:', xhr.responseText);
    });
};

/* =========================================================
   ACCIONES | EDITAR / ELIMINAR
   ========================================================= */

function almacenSincronizarSelect($select, value) {
    if (!$select || !$select.length) {
        return;
    }

    if (value !== undefined) {
        $select.val(value);
    }

    $select.trigger('change');
}

function almacenEditar(row) {
    if (!row) {
        almacenNotificar('error', 'Error', 'No fue posible identificar el almacén seleccionado.');
        return;
    }

    $('#formAlmacen #almacen_id').val(row.almacen_id);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/editarAlmacen.php',
        data: $('#formAlmacen').serialize()
    }).done(function(registro) {
        var valores;

        try {
            valores = typeof registro === 'string' ? eval(registro) : registro;
        } catch (e) {
            almacenNotificar('error', 'Error', 'No fue posible procesar la información del almacén.');
            console.error('Respuesta inválida al editar almacén:', registro, e);
            return;
        }

        $('#formAlmacen').attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarAlmacenAjax.php'
        });

        $('#formAlmacen')[0].reset();
        $('#formAlmacen #almacen_id').val(row.almacen_id);

        $('#reg_almacen').hide();
        $('#edi_almacen').show();
        $('#delete_almacen').hide();

        $('#formAlmacen #pro_almacen').val('Editar Almacén');
        $('#formAlmacen #almacen_almacen').val(valores[1]);

        almacenSincronizarSelect($('#formAlmacen #ubicacion_almacen'), valores[0]);
        almacenSincronizarSelect($('#formAlmacen #almacen_empresa_id'), valores[3]);

        var activo = parseInt(valores[2], 10) === 1;
        var facturarCero = parseInt(valores[4], 10) === 1;

        $('#formAlmacen #almacen_activo').prop('checked', activo);
        $('#formAlmacen #label_almacen_activo').html(activo ? 'Activo' : 'Inactivo');

        $('#formAlmacen #facturar_cero').prop('checked', facturarCero);
        $('#formAlmacen #cero').prop('checked', facturarCero);
        $('#formAlmacen #label_facturar_cero').html(facturarCero ? 'Si' : 'No');

        $('#formAlmacen #almacen_almacen').prop('readonly', false);
        $('#formAlmacen #ubicacion_almacen').prop('disabled', true);
        $('#formAlmacen #almacen_activo').prop('disabled', true);
        $('#formAlmacen #almacen_empresa_id').prop('disabled', true);

        $('#modal_almacen').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }).fail(function(xhr) {
        almacenNotificar('error', 'Error', 'No se pudo cargar la información del almacén.');
        console.error('Error al editar almacén:', xhr.responseText);
    });
}

function almacenEliminar(row) {
    if (!row) {
        almacenNotificar('error', 'Error', 'No fue posible identificar el almacén seleccionado.');
        return;
    }

    if (typeof Swal === 'undefined') {
        almacenNotificar('error', 'Error', 'SweetAlert2 no está disponible.');
        return;
    }

    Swal.fire({
        title: 'Confirmar eliminación',
        html: '¿Desea eliminar permanentemente el almacén?<br><br>' +
            '<strong>Nombre:</strong> ' + almacenEscape(almacenValor(row.almacen, 'Sin nombre')),
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
            title: 'Eliminando almacén',
            text: 'Espere mientras se procesa la solicitud...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarAlmacenesAjax.php',
            data: {
                almacen_id: row.almacen_id
            },
            dataType: 'json'
        }).done(function(response) {
            Swal.close();

            if (response && response.status === 'success') {
                almacenNotificar(
                    'success',
                    response.title || 'Éxito',
                    response.message || 'Almacén eliminado correctamente.'
                );
                listar_almacen();
            } else {
                almacenNotificar(
                    'error',
                    response && response.title ? response.title : 'Error',
                    response && response.message ? response.message : 'No fue posible eliminar el almacén.'
                );
            }
        }).fail(function(xhr) {
            Swal.close();
            almacenNotificar('error', 'Error', 'Ocurrió un error al procesar la solicitud.');
            console.error('Error al eliminar almacén:', xhr.responseText);
        });
    });
}

/* =========================================================
   EXCEL PROFESIONAL
   ========================================================= */

function almacenExcelEscape(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function almacenExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var raw = String(value == null ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + almacenExcelEscape(raw) + '</t></is>' +
    '</c>';
}

function almacenExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function almacenFiltroEstadoTexto() {
    var estado = $('#form_main_almacen #estado_almacen').val();

    if (String(estado) === '1') {
        return 'Activo';
    }

    if (String(estado) === '0') {
        return 'Inactivo';
    }

    return 'Todos';
}

function almacenGenerarExcel() {
    var rows = almacenUI.filtered || [];

    if (!rows.length) {
        almacenNotificar('warning', 'Sin información', 'No hay almacenes para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        almacenNotificar(
            'error',
            'Excel no disponible',
            'No se encontró JSZip para generar el archivo XLSX.'
        );
        return;
    }

    var totalActivos = rows.filter(function(row) {
        return almacenEstadoActivo(row);
    }).length;

    var totalFacturanCero = rows.filter(function(row) {
        return almacenFacturarCeroActivo(row);
    }).length;

    var empresas = {};
    rows.forEach(function(row) {
        var empresa = $.trim(almacenValor(row.empresa, ''));
        if (empresa) {
            empresas[empresa.toLowerCase()] = true;
        }
    });

    var totalEmpresas = Object.keys(empresas).length;

    var headers = [
        'EMPRESA',
        'ALMACÉN',
        'FACTURAR EN CERO',
        'UBICACIÓN',
        'ESTADO'
    ];

    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            almacenExcelCell(
                'A1',
                'IZZY • REPORTE DE ALMACENES',
                1,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            almacenExcelCell(
                'A2',
                'Configuración, disponibilidad y ubicación de almacenes • Generado: ' +
                new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    /*
     * Resumen ejecutivo.
     * Cada celda de los rangos combinados recibe estilo para que
     * el borde exterior quede completo al abrir el archivo en Excel.
     */
    sheetRows.push(
        '<row r="3" ht="18" customHeight="1">' +
            almacenExcelCell('A3', 'REGISTROS', 6, false) +
            almacenExcelCell('B3', '', 6, false) +
            almacenExcelCell('C3', 'ACTIVOS', 6, false) +
            almacenExcelCell('D3', '', 6, false) +
            almacenExcelCell('E3', 'FACTURAN EN CERO', 6, false) +
            almacenExcelCell('F3', '', 6, false) +
            almacenExcelCell('G3', 'EMPRESAS', 6, false) +
            almacenExcelCell('H3', '', 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="26" customHeight="1">' +
            almacenExcelCell('A4', rows.length, 7, true) +
            almacenExcelCell('B4', '', 7, false) +
            almacenExcelCell('C4', totalActivos, 7, true) +
            almacenExcelCell('D4', '', 7, false) +
            almacenExcelCell('E4', totalFacturanCero, 7, true) +
            almacenExcelCell('F4', '', 7, false) +
            almacenExcelCell('G4', totalEmpresas, 7, true) +
            almacenExcelCell('H4', '', 7, false) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            almacenExcelCell(
                'A6',
                'Detalle de almacenes filtrados',
                8,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="' + headerRow + '" ht="28" customHeight="1">' +
            headers.map(function(header, index) {
                return almacenExcelCell(
                    almacenExcelColName(index) + headerRow,
                    header,
                    3,
                    false
                );
            }).join('') +
        '</row>'
    );

    rows.forEach(function(row, rowIndex) {
        var excelRow = firstDataRow + rowIndex;
        var values = [
            almacenValor(row.empresa, ''),
            almacenValor(row.almacen, ''),
            almacenFacturarCeroActivo(row) ? 'Sí' : 'No',
            almacenValor(row.ubicacion, ''),
            almacenEstadoActivo(row) ? 'Activo' : 'Inactivo'
        ];

        var cells = values.map(function(value, colIndex) {
            var style = 4;

            if (colIndex === 2) {
                style = String(value).toLowerCase() === 'sí' ? 9 : 10;
            }

            if (colIndex === 4) {
                style = String(value).toLowerCase() === 'activo' ? 9 : 10;
            }

            return almacenExcelCell(
                almacenExcelColName(colIndex) + excelRow,
                value,
                style,
                false
            );
        }).join('');

        sheetRows.push(
            '<row r="' + excelRow + '" ht="34" customHeight="1">' +
                cells +
            '</row>'
        );
    });

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:H' + lastRow + '"/>' +
            '<sheetViews>' +
                '<sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView>' +
            '</sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' +
                '<col min="1" max="1" width="28" customWidth="1"/>' +
                '<col min="2" max="2" width="26" customWidth="1"/>' +
                '<col min="3" max="3" width="20" customWidth="1"/>' +
                '<col min="4" max="4" width="38" customWidth="1"/>' +
                '<col min="5" max="5" width="16" customWidth="1"/>' +
                '<col min="6" max="8" width="14" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':E' + lastRow + '"/>' +
            '<mergeCells count="11">' +
                '<mergeCell ref="A1:H1"/>' +
                '<mergeCell ref="A2:H2"/>' +
                '<mergeCell ref="A3:B3"/>' +
                '<mergeCell ref="A4:B4"/>' +
                '<mergeCell ref="C3:D3"/>' +
                '<mergeCell ref="C4:D4"/>' +
                '<mergeCell ref="E3:F3"/>' +
                '<mergeCell ref="E4:F4"/>' +
                '<mergeCell ref="G3:H3"/>' +
                '<mergeCell ref="G4:H4"/>' +
                '<mergeCell ref="A6:E6"/>' +
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
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="Almacenes" sheetId="1" r:id="rId1"/></sheets>' +
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

    var opcionesZip = {
        type: 'blob',
        mimeType:
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promesa;

    if (typeof zip.generateAsync === 'function') {
        promesa = zip.generateAsync(opcionesZip);
    } else if (typeof zip.generate === 'function') {
        try {
            promesa = Promise.resolve(zip.generate(opcionesZip));
        } catch (errorGenerate) {
            console.error(
                'Error al generar XLSX de almacenes con JSZip legado:',
                errorGenerate
            );
            promesa = Promise.reject(errorGenerate);
        }
    } else {
        promesa = Promise.reject(
            new Error(
                'La versión de JSZip cargada no soporta generateAsync() ni generate().'
            )
        );
    }

    promesa
        .then(function(blob) {
            if (!(blob instanceof Blob)) {
                blob = new Blob(
                    [blob],
                    {
                        type:
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    }
                );
            }

            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');

            link.href = url;
            link.download = 'Reporte_Almacenes.xlsx';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setTimeout(function() {
                URL.revokeObjectURL(url);
            }, 1000);
        })
        .catch(function(error) {
            console.error(
                'Error al generar Excel de almacenes:',
                error
            );

            almacenNotificar(
                'error',
                'Error al generar Excel',
                'No se pudo generar el archivo Excel.'
            );
        });
}
/* =========================================================
   PDF CON PREVIEW
   ========================================================= */

function almacenObtenerLogoPdf(callback) {
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

function almacenGenerarPdf() {
    var rows = almacenUI.filtered || [];

    if (!rows.length) {
        almacenNotificar(
            'warning',
            'Sin información',
            'No hay almacenes para mostrar en PDF.'
        );
        return;
    }

    if (
        typeof pdfMake === 'undefined' ||
        typeof abrirModalPdfPublico !== 'function'
    ) {
        almacenNotificar(
            'error',
            'PDF no disponible',
            'No están disponibles los componentes del PDF.'
        );
        return;
    }

    var totalActivos = rows.filter(function(row) {
        return almacenEstadoActivo(row);
    }).length;

    var totalFacturanCero = rows.filter(function(row) {
        return almacenFacturarCeroActivo(row);
    }).length;

    var empresas = {};
    rows.forEach(function(row) {
        var empresa = $.trim(almacenValor(row.empresa, ''));
        if (empresa) {
            empresas[empresa.toLowerCase()] = true;
        }
    });

    var totalEmpresas = Object.keys(empresas).length;
    var busqueda = $.trim($('#buscarAlmacenListado').val()) || 'Sin búsqueda';

    almacenObtenerLogoPdf(function(logo) {
        var logoCell = logo
            ? {
                table: {
                    widths: ['*'],
                    body: [[{
                        image: logo,
                        fit: [76, 42],
                        alignment: 'center',
                        margin: [6, 4, 6, 4],
                        fillColor: '#FFFFFF'
                    }]]
                },
                layout: 'noBorders',
                margin: [10, 8, 8, 8]
            }
            : {
                text: 'IZZY',
                bold: true,
                fontSize: 19,
                color: '#FFFFFF',
                alignment: 'center',
                margin: [10, 18, 8, 18]
            };

        var body = [[
            { text: 'EMPRESA', style: 'th' },
            { text: 'ALMACÉN', style: 'th' },
            { text: 'FACTURAR EN CERO', style: 'th' },
            { text: 'UBICACIÓN', style: 'th' },
            { text: 'ESTADO', style: 'th' }
        ]];

        rows.forEach(function(row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
            var activo = almacenEstadoActivo(row);
            var facturarCero = almacenFacturarCeroActivo(row);

            body.push([
                {
                    text: almacenValor(row.empresa, '—'),
                    style: 'td',
                    fillColor: fill
                },
                {
                    text: almacenValor(row.almacen, '—'),
                    style: 'td',
                    fillColor: fill
                },
                {
                    text: facturarCero ? 'Sí' : 'No',
                    style: 'tdCenter',
                    fillColor: fill,
                    bold: true,
                    color: facturarCero ? '#14804A' : '#C9372C'
                },
                {
                    text: almacenValor(row.ubicacion, '—'),
                    style: 'td',
                    fillColor: fill
                },
                {
                    text: activo ? 'Activo' : 'Inactivo',
                    style: 'tdCenter',
                    fillColor: fill,
                    bold: true,
                    color: activo ? '#14804A' : '#C9372C'
                }
            ]);
        });

        var fechaReporte = new Date().toLocaleDateString('es-HN');

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

            footer: function(currentPage, pageCount) {
                return {
                    margin: [28, 8, 28, 0],
                    columns: [
                        {
                            text: 'IZZY • Configuración de Almacén',
                            fontSize: 7,
                            color: '#7A869A'
                        },
                        {
                            text:
                                'Página ' +
                                currentPage +
                                ' de ' +
                                pageCount,
                            alignment: 'right',
                            fontSize: 7,
                            color: '#7A869A'
                        }
                    ]
                };
            },

            content: [
                {
                    table: {
                        widths: [100, '*', 155],
                        body: [[
                            {
                                border: [false, false, false, false],
                                fillColor: '#17324D',
                                stack: [logoCell]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: '#17324D',
                                margin: [0, 10, 0, 10],
                                stack: [
                                    {
                                        text: 'REPORTE DE ALMACENES',
                                        fontSize: 16,
                                        bold: true,
                                        color: '#FFFFFF'
                                    },
                                    {
                                        text:
                                            'Configuración, disponibilidad y ubicación de almacenes',
                                        fontSize: 7.5,
                                        color: '#D8E5F0',
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            },
                            {
                                border: [false, false, false, false],
                                fillColor: '#17324D',
                                margin: [0, 10, 12, 10],
                                stack: [
                                    {
                                        text: 'REPORTE EJECUTIVO',
                                        fontSize: 6.5,
                                        bold: true,
                                        color: '#72E2E5',
                                        alignment: 'right'
                                    },
                                    {
                                        text: fechaReporte,
                                        fontSize: 9,
                                        bold: true,
                                        color: '#FFFFFF',
                                        alignment: 'right',
                                        margin: [0, 3, 0, 0]
                                    },
                                    {
                                        text:
                                            rows.length +
                                            ' registro(s) filtrado(s)',
                                        fontSize: 6.5,
                                        color: '#D8E5F0',
                                        alignment: 'right',
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineWidth: function() { return 0; },
                        vLineWidth: function() { return 0; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ['*'],
                        body: [[{
                            text:
                                'Filtros aplicados: Estado: ' +
                                almacenFiltroEstadoTexto() +
                                '   |   Búsqueda: ' +
                                busqueda,
                            fontSize: 6.8,
                            color: '#52627A',
                            margin: [10, 7, 10, 7],
                            fillColor: '#F7F9FC'
                        }]]
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ['*', '*', '*', '*'],
                        body: [[
                            {
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7],
                                stack: [
                                    { text: 'REGISTROS', fontSize: 6.3, bold: true, color: '#6B778C' },
                                    { text: String(rows.length), fontSize: 13, bold: true, color: '#172B4D' }
                                ]
                            },
                            {
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7],
                                stack: [
                                    { text: 'ACTIVOS', fontSize: 6.3, bold: true, color: '#6B778C' },
                                    { text: String(totalActivos), fontSize: 13, bold: true, color: '#14804A' }
                                ]
                            },
                            {
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7],
                                stack: [
                                    { text: 'FACTURAN EN CERO', fontSize: 6.3, bold: true, color: '#6B778C' },
                                    { text: String(totalFacturanCero), fontSize: 13, bold: true, color: '#0EA5A8' }
                                ]
                            },
                            {
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7],
                                stack: [
                                    { text: 'EMPRESAS', fontSize: 6.3, bold: true, color: '#6B778C' },
                                    { text: String(totalEmpresas), fontSize: 13, bold: true, color: '#6554C0' }
                                ]
                            }
                        ]]
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return 0.6; },
                        vLineWidth: function() { return 0.6; }
                    },
                    margin: [0, 0, 0, 12]
                },
                {
                    text: 'VISTA DETALLE',
                    fontSize: 7,
                    bold: true,
                    color: '#17324D',
                    margin: [0, 1, 0, 7]
                },
                {
                    table: {
                        headerRows: 1,
                        widths: [120, 115, 88, '*', 70],
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
                    fontSize: 6.2,
                    bold: true,
                    color: '#FFFFFF',
                    fillColor: '#17324D',
                    alignment: 'center'
                },
                td: {
                    fontSize: 6.5,
                    color: '#253858'
                },
                tdCenter: {
                    fontSize: 6.5,
                    color: '#253858',
                    alignment: 'center'
                }
            },

            defaultStyle: {
                fontSize: 8,
                color: '#253858'
            }
        };

        var pdf = pdfMake.createPdf(doc);

        if (typeof pdf.getDataUrl === 'function') {
            pdf.getDataUrl(function(url) {
                abrirModalPdfPublico(
                    url,
                    'Reporte de Almacenes',
                    'Reporte_Almacenes.pdf'
                );
            });
            return;
        }

        if (typeof pdf.getBase64 === 'function') {
            pdf.getBase64(function(base64) {
                abrirModalPdfPublico(
                    'data:application/pdf;base64,' + base64,
                    'Reporte de Almacenes',
                    'Reporte_Almacenes.pdf'
                );
            });
            return;
        }

        almacenNotificar(
            'error',
            'PDF no disponible',
            'La versión actual de pdfMake no permite una vista previa compatible.'
        );
    });
}
/* =========================================================
   MODAL Y CATÁLOGOS EXISTENTES
   ========================================================= */

function modalAlmacen() {
    $('#formAlmacen').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL; ?>ajax/agregarAlmacenAjax.php'
    });

    $('#formAlmacen')[0].reset();
    $('#formAlmacen #pro_almacen').val('Registrar Almacén');
    $('#reg_almacen').show();
    $('#edi_almacen').hide();
    $('#delete_almacen').hide();

    getUbicacionAlmacen();

    if (typeof getAlmacen === 'function') {
        getAlmacen();
    }

    $('#formAlmacen #almacen_almacen').prop('readonly', false);
    $('#formAlmacen #ubicacion_almacen').prop('disabled', false);
    $('#formAlmacen #almacen_activo').prop('disabled', false);
    $('#formAlmacen #almacen_empresa_id').prop('disabled', false);

    $('#modal_almacen').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function getUbicacionAlmacen() {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/getUbicacion.php',
        async: true
    }).done(function(data) {
        var $select = $('#formAlmacen #ubicacion_almacen');
        $select.html(data);
        almacenSincronizarSelect($select);
    }).fail(function(xhr) {
        almacenNotificar('error', 'Error', 'No se pudieron cargar las ubicaciones.');
        console.error('Error al cargar ubicaciones:', xhr.responseText);
    });
}

function getEmpresaAlmacen() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getEmpresa.php',
        type: 'POST',
        dataType: 'json'
    }).done(function(response) {
        var $select = $('#formAlmacen #almacen_empresa_id');
        $select.empty();

        if (response && response.success) {
            response.data.forEach(function(empresa) {
                $select.append(
                    $('<option></option>')
                        .val(empresa.empresa_id)
                        .text(empresa.nombre)
                );
            });

            if (response.data.length > 0) {
                $select.val(1);
            }
        } else {
            $select.append('<option value="">No hay empresas disponibles</option>');
            almacenNotificar(
                'warning',
                'Advertencia',
                response && response.message ? response.message : 'No se encontraron empresas'
            );
        }

        almacenSincronizarSelect($select);
    }).fail(function(xhr) {
        almacenNotificar('error', 'Error', 'Error de conexión al cargar empresas');
        var $select = $('#formAlmacen #almacen_empresa_id');
        $select.html('<option value="">Error al cargar</option>');
        almacenSincronizarSelect($select);
        console.error('Error al cargar empresas:', xhr.responseText);
    });
}

/* =========================================================
   EVENTOS
   ========================================================= */

$(document).ready(function() {
    try {
        var savedView = localStorage.getItem(ALMACEN_STORAGE_VISTA);
        if (savedView === 'miniatura' || savedView === 'detalle') {
            almacenUI.preferredView = savedView;
            almacenUI.view = savedView;
        }
    } catch (e) {}

    almacenSincronizarVista();
    almacenSincronizarPageSize();

    almacenConfigurarPanel(
        '#btnToggleFiltrosAlmacen',
        '#almacenFiltrosContenido',
        'izzy.almacen.panel.filtros'
    );

    almacenConfigurarPanel(
        '#btnToggleKpisAlmacen',
        '#almacenKpisContenido',
        'izzy.almacen.panel.kpis'
    );

    listar_almacen();
    getEmpresaAlmacen();
    getUbicacionAlmacen();

    $('#form_main_almacen #search')
        .off('click.almacenUI')
        .on('click.almacenUI', function(e) {
            e.preventDefault();
            listar_almacen();
        });

    $('#form_main_almacen')
        .off('reset.almacenUI')
        .on('reset.almacenUI', function() {
            var $form = $(this);

            setTimeout(function() {
                almacenSincronizarSelect($form.find('#estado_almacen'), '');
                $('#buscarAlmacenListado').val('');
                almacenUI.search = '';
                listar_almacen();
            }, 0);
        });

    $('#btnAlmacenActualizar')
        .off('click.almacenUI')
        .on('click.almacenUI', listar_almacen);

    $('#btnAlmacenIngresar')
        .off('click.almacenUI')
        .on('click.almacenUI', modalAlmacen);

    $('#btnAlmacenExcel')
        .off('click.almacenUI')
        .on('click.almacenUI', almacenGenerarExcel);

    $('#btnAlmacenPdf')
        .off('click.almacenUI')
        .on('click.almacenUI', almacenGenerarPdf);

    $('#almacenPageSize')
        .off('change.almacenUI')
        .on('change.almacenUI', function() {
            var value = parseInt($(this).val(), 10) || 10;

            if (almacenUI.view === 'miniatura') {
                almacenUI.pageSizeMiniatura = value;
            } else {
                almacenUI.pageSizeDetalle = value;
            }

            almacenUI.pageSize = value;
            almacenUI.page = 1;
            almacenRender();
        });

    $('.almacen-view-btn')
        .off('click.almacenUI')
        .on('click.almacenUI', function() {
            var next = $(this).data('view');

            if (next !== 'detalle' && next !== 'miniatura') {
                return;
            }

            if (next === 'detalle' && almacenEsMovil()) {
                return;
            }

            almacenUI.view = next;
            almacenUI.page = 1;

            if (!almacenEsMovil()) {
                almacenUI.preferredView = next;

                try {
                    localStorage.setItem(ALMACEN_STORAGE_VISTA, almacenUI.preferredView);
                } catch (e) {}
            }

            almacenSincronizarVista();
            almacenSincronizarPageSize();
            almacenRender();
        });

    $('#buscarAlmacenListado')
        .off('input.almacenUI')
        .on('input.almacenUI', function() {
            almacenUI.search = $(this).val() || '';
            almacenUI.page = 1;
            almacenFiltrar();
            almacenRender();
        });

    $('#limpiarBuscarAlmacenListado')
        .off('click.almacenUI')
        .on('click.almacenUI', function() {
            $('#buscarAlmacenListado').val('').focus();
            almacenUI.search = '';
            almacenUI.page = 1;
            almacenFiltrar();
            almacenRender();
        });

    $('#almacenPaginacion')
        .off('click.almacenUI', '.almacen-page-btn')
        .on('click.almacenUI', '.almacen-page-btn', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                almacenUI.page = page;
                almacenRender();

                var target = document.querySelector('.almacen-list-card');
                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });

    $('#almacenListado')
        .off('click.almacenUI', '.table_editar')
        .on('click.almacenUI', '.table_editar', function(e) {
            e.preventDefault();
            almacenEditar(almacenBuscarFilaPorId($(this).data('almacen-id')));
        });

    $('#almacenListado')
        .off('click.almacenUI', '.table_eliminar')
        .on('click.almacenUI', '.table_eliminar', function(e) {
            e.preventDefault();
            almacenEliminar(almacenBuscarFilaPorId($(this).data('almacen-id')));
        });

    $('#almacenListado')
        .off('click.almacenDropdown', '.js-acciones-toggle')
        .on('click.almacenDropdown', '.js-acciones-toggle', function() {
            $('.almacen-detail-row, .almacen-mini-card').removeClass('almacen-dropdown-open');
            $(this).closest('.almacen-detail-row, .almacen-mini-card').addClass('almacen-dropdown-open');
        });

    $(document)
        .off('click.almacenDropdownClose')
        .on('click.almacenDropdownClose', function(e) {
            if (!$(e.target).closest('.acciones-dropdown').length) {
                $('.almacen-detail-row, .almacen-mini-card').removeClass('almacen-dropdown-open');
            }
        });

    $(window)
        .off('resize.almacenUI')
        .on('resize.almacenUI', function() {
            var oldView = almacenUI.view;

            if (almacenEsMovil()) {
                almacenUI.view = 'miniatura';
            } else {
                almacenUI.view = almacenUI.preferredView;
            }

            if (oldView !== almacenUI.view) {
                almacenUI.page = 1;
                almacenSincronizarPageSize();
            }

            almacenSincronizarVista();
            almacenRender();
        });

    $('#modal_almacen').on('shown.bs.modal', function() {
        $(this).find('#formAlmacen #almacen_almacen').focus();
    });
});

/* =========================================================
   SWITCHES EXISTENTES
   ========================================================= */

$('#formAlmacen #label_almacen_activo').html('Activo');

$(document)
    .off('change.almacenActivo', '#formAlmacen input[name="almacen_activo1"]')
    .on('change.almacenActivo', '#formAlmacen input[name="almacen_activo1"]', function() {
        var activo = $(this).is(':checked');

        $('#formAlmacen #label_almacen_activo').html(activo ? 'Activo' : 'Inactivo');
        $('#formAlmacen #almacen_activo').val(activo ? 1 : 0);
        $('#formAlmacen #val_almacen_activo').val(activo ? 1 : 0);
    });

$(document)
    .off('change.almacenCero', '#formAlmacen #facturar_cero')
    .on('change.almacenCero', '#formAlmacen #facturar_cero', function() {
        var activo = $(this).is(':checked');

        $('#formAlmacen #label_facturar_cero').html(activo ? 'Si' : 'No');
        $('#formAlmacen #facturar_cero').val(activo ? 1 : 0);
        $('#formAlmacen #cero').prop('checked', activo).val(activo ? 1 : 0);
    });
</script>
