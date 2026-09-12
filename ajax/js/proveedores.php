<script>
/* =========================================================
   IZZY 6.0 | PROVEEDORES
   Listado DIV + Detalle/Miniatura + KPIs + Excel/PDF.
   No modifica lógica de negocio, endpoints ni permisos globales.
   ========================================================= */
(function ($) {
    'use strict';

    var PROVEEDORES_MOBILE_QUERY = '(max-width: 767.98px)';
    var PROVEEDORES_STORAGE_VISTA = 'izzy.proveedores.tipo_vista';
    var PROVEEDORES_STORAGE_FILTROS = 'izzy.proveedores.filtros.visible';
    var PROVEEDORES_STORAGE_KPIS = 'izzy.proveedores.kpis.visible';

    var proveedoresPermisosDataTablePromise = null;

    var proveedoresState = {
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

    function proveedoresParseJsonSeguro(valor, respaldo) {
        if (valor !== null && typeof valor === 'object') return valor;
        try {
            return JSON.parse(valor || '');
        } catch (error) {
            return respaldo;
        }
    }

    function aplicarPermisosProveedoresDataTableAsync() {
        if (!proveedoresPermisosDataTablePromise) {
            proveedoresPermisosDataTablePromise = $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>core/getPrivilegioUsuarioTipo.php',
                timeout: 30000
            }).then(function (respuestaTipoUsuario) {
                var tipoUsuario = proveedoresParseJsonSeguro(respuestaTipoUsuario, []);

                return $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>core/getTipoUsuarioAccesos.php',
                    data: { permisos_tipo_user_id: tipoUsuario[0] },
                    timeout: 30000
                });
            }).then(function (respuestaPermisos) {
                return proveedoresParseJsonSeguro(respuestaPermisos, []);
            }).catch(function (error) {
                proveedoresPermisosDataTablePromise = null;
                throw error;
            });
        }

        return proveedoresPermisosDataTablePromise.then(function (permisos) {
            permisos.forEach(function (permiso) {
                var $controles = $('.table_' + permiso.tipo_permiso);
                var habilitado = Number(permiso.estado) === 1;
                $controles.toggle(habilitado).prop('disabled', !habilitado);
            });
        }).catch(function (error) {
            console.error('No se pudieron aplicar los permisos de Proveedores.', error);
        });
    }

    function proveedoresEsMovil() {
        return window.matchMedia
            ? window.matchMedia(PROVEEDORES_MOBILE_QUERY).matches
            : $(window).width() <= 767;
    }

    function proveedoresEscape(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function proveedoresValor(value, fallback) {
        var text = value == null ? '' : String(value).trim();
        return text !== '' ? text : (fallback || 'No registrado');
    }

    function proveedorPorId(id) {
        return (proveedoresState.rows || []).find(function (row) {
            return String(row.proveedores_id) === String(id);
        }) || null;
    }

    function configurarPanelProveedores(buttonSelector, contentSelector, storageKey, defaultVisible) {
        var $button = $(buttonSelector);
        var $content = $(contentSelector);
        var visible = defaultVisible;

        try {
            var stored = localStorage.getItem(storageKey);
            if (stored !== null) visible = stored === '1';
        } catch (e) {}

        function paint() {
            $content.toggle(visible);
            $button.attr('aria-expanded', visible ? 'true' : 'false');
            $button.find('span').text(visible ? 'Ocultar' : 'Mostrar');
            $button.find('i')
                .toggleClass('fa-chevron-up', visible)
                .toggleClass('fa-chevron-down', !visible);
        }

        paint();

        $button.off('click.proveedoresPanel').on('click.proveedoresPanel', function () {
            visible = !visible;
            $content.stop(true, true)[visible ? 'slideDown' : 'slideUp'](170);
            paint();

            try {
                localStorage.setItem(storageKey, visible ? '1' : '0');
            } catch (e) {}
        });
    }

    function inicializarVistaProveedores() {
        var saved = 'detalle';

        try {
            saved = localStorage.getItem(PROVEEDORES_STORAGE_VISTA) || 'detalle';
        } catch (e) {}

        proveedoresState.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
        proveedoresState.view = proveedoresEsMovil()
            ? 'miniatura'
            : proveedoresState.preferredView;

        actualizarBotonesVistaProveedores();
        sincronizarPageSizeProveedores();
    }

    function actualizarDisponibilidadVistaProveedores() {
        var movil = proveedoresEsMovil();

        $('.proveedores-view-btn[data-view="detalle"]')
            .prop('disabled', movil)
            .toggleClass('d-none', movil)
            .attr('aria-hidden', movil ? 'true' : 'false');
    }

    function actualizarBotonesVistaProveedores() {
        actualizarDisponibilidadVistaProveedores();

        $('.proveedores-view-btn')
            .removeClass('active')
            .attr('aria-pressed', 'false');

        $('.proveedores-view-btn[data-view="' + proveedoresState.view + '"]')
            .addClass('active')
            .attr('aria-pressed', 'true');
    }

    function cambiarVistaProveedores(vista) {
        proveedoresState.view = proveedoresEsMovil()
            ? 'miniatura'
            : (vista === 'miniatura' ? 'miniatura' : 'detalle');

        if (!proveedoresEsMovil()) {
            proveedoresState.preferredView = proveedoresState.view;
            try {
                localStorage.setItem(PROVEEDORES_STORAGE_VISTA, proveedoresState.preferredView);
            } catch (e) {}
        }

        proveedoresState.page = 1;
        actualizarBotonesVistaProveedores();
        sincronizarPageSizeProveedores();
        renderProveedores();
    }

    function sincronizarPageSizeProveedores() {
        var mini = proveedoresState.view === 'miniatura';
        var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
        var preferido = mini
            ? proveedoresState.pageSizeMiniatura
            : proveedoresState.pageSizeDetalle;

        if (opciones.indexOf(preferido) === -1) preferido = opciones[0];

        var $select = $('#proveedoresPageSize');
        $select.empty();

        opciones.forEach(function (n) {
            $select.append($('<option></option>').val(n).text(n));
        });

        proveedoresState.pageSize = preferido;
        $select.val(String(preferido));
    }

    function extraerRowsProveedores(response) {
        if (Array.isArray(response)) return response;
        if (response && Array.isArray(response.data)) return response.data;
        if (response && response.aaData && Array.isArray(response.aaData)) return response.aaData;
        return [];
    }

    function listar_proveedores() {
        var estadoSeleccionado = $('#form_main_proveedores #estado_proveedores').val();
        var estado = (
            estadoSeleccionado === null ||
            estadoSeleccionado === undefined ||
            estadoSeleccionado === ''
        ) ? 1 : estadoSeleccionado;

        proveedoresState.loading = true;
        renderEstadoProveedores('loading');

        $.ajax({
            method: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTableProveedores.php',
            data: { estado: estado },
            dataType: 'json',
            timeout: 30000
        }).done(function (response) {
            proveedoresState.rows = extraerRowsProveedores(response);
            proveedoresState.page = 1;
            proveedoresState.loading = false;
            aplicarFiltroProveedores();
            aplicarPermisosProveedoresDataTableAsync();
        }).fail(function (xhr) {
            proveedoresState.rows = [];
            proveedoresState.filtered = [];
            proveedoresState.loading = false;
            renderEstadoProveedores('error');

            if (typeof showNotify === 'function') {
                showNotify(
                    'error',
                    'Error',
                    'No se pudo cargar el directorio de proveedores. Intente nuevamente.'
                );
            }

            console.error('Error cargando proveedores:', xhr.responseText || xhr.statusText);
        });
    }

    function aplicarFiltroProveedores() {
        var term = String(proveedoresState.search || '').trim().toLowerCase();

        proveedoresState.filtered = (proveedoresState.rows || []).filter(function (row) {
            if (!term) return true;

            var text = [
                row.proveedor,
                row.rtn,
                row.telefono,
                row.correo,
                row.departamento,
                row.municipio,
                Number(row.estado) === 1 ? 'activo' : 'inactivo'
            ].join(' ').toLowerCase();

            return text.indexOf(term) !== -1;
        });

        actualizarKpisProveedores();
        renderProveedores();
    }

    function actualizarKpisProveedores() {
        var rows = proveedoresState.filtered || [];
        var conRtn = 0;
        var conTelefono = 0;
        var conCorreo = 0;

        rows.forEach(function (row) {
            if (String(row.rtn || '').trim()) conRtn++;
            if (String(row.telefono || '').trim()) conTelefono++;
            if (String(row.correo || '').trim()) conCorreo++;
        });

        $('#proveedoresKpiRegistros').text(rows.length);
        $('#proveedoresKpiRtn').text(conRtn);
        $('#proveedoresKpiTelefono').text(conTelefono);
        $('#proveedoresKpiCorreo').text(conCorreo);
    }

    function renderEstadoProveedores(tipo) {
        var config = {
            loading: {
                icon: 'fas fa-spinner fa-spin',
                title: 'Cargando proveedores',
                text: 'Espere mientras consultamos la información.'
            },
            empty: {
                icon: 'fas fa-inbox',
                title: 'Sin registros',
                text: 'No se encontraron proveedores con los criterios actuales.'
            },
            error: {
                icon: 'fas fa-exclamation-circle',
                title: 'No fue posible cargar los datos',
                text: 'Revise la conexión e intente nuevamente.'
            }
        }[tipo] || {};

        $('#proveedoresListado').html(
            '<div class="proveedores-state">' +
                '<i class="' + config.icon + '"></i>' +
                '<strong>' + proveedoresEscape(config.title) + '</strong>' +
                '<span>' + proveedoresEscape(config.text) + '</span>' +
            '</div>'
        );

        $('#proveedoresInfo').text('0 registros');
        $('#proveedoresPaginacion').empty();
    }

    function statusProveedor(row) {
        var activo = Number(row.estado) === 1;
        return '<span class="proveedores-status ' +
            (activo ? 'proveedores-status-activo' : 'proveedores-status-inactivo') + '">' +
            '<i class="fas ' + (activo ? 'fa-check-circle' : 'fa-times-circle') + '"></i>' +
            (activo ? 'Activo' : 'Inactivo') +
        '</span>';
    }

    function accionesProveedor(row) {
        return '' +
            '<div class="dropdown proveedores-actions acciones-dropdown">' +
                '<button type="button" class="btn btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                    '<i class="fas fa-cog"></i><span>Acciones</span>' +
                '</button>' +
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                    '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar" data-id="' + proveedoresEscape(row.proveedores_id) + '">' +
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                        '<span class="accion-label">Editar</span>' +
                    '</button>' +
                    '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar" data-id="' + proveedoresEscape(row.proveedores_id) + '">' +
                        '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                        '<span class="accion-label">Eliminar</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
    }

    function renderDetalleProveedores(rows) {
        var html = '' +
            '<div class="proveedores-detail-header">' +
                '<div>Proveedor</div>' +
                '<div>RTN</div>' +
                '<div>Teléfono</div>' +
                '<div>Correo</div>' +
                '<div>Departamento</div>' +
                '<div>Municipio</div>' +
                '<div>Estado</div>' +
                '<div>Acciones</div>' +
            '</div>';

        rows.forEach(function (row) {
            html += '' +
                '<article class="proveedores-detail-row" data-id="' + proveedoresEscape(row.proveedores_id) + '">' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Proveedor</span><span class="proveedores-name">' + proveedoresEscape(proveedoresValor(row.proveedor)) + '</span></div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">RTN</span>' + proveedoresEscape(proveedoresValor(row.rtn)) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Teléfono</span>' + proveedoresEscape(proveedoresValor(row.telefono)) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Correo</span>' + proveedoresEscape(proveedoresValor(row.correo)) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Departamento</span>' + proveedoresEscape(proveedoresValor(row.departamento)) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Municipio</span>' + proveedoresEscape(proveedoresValor(row.municipio)) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Estado</span>' + statusProveedor(row) + '</div>' +
                    '<div class="proveedores-cell"><span class="proveedores-cell-label">Acciones</span>' + accionesProveedor(row) + '</div>' +
                '</article>';
        });

        return html;
    }

    function renderMiniaturaProveedores(rows) {
        var html = '<div class="proveedores-mini-grid">';

        rows.forEach(function (row) {
            html += '' +
                '<article class="proveedores-mini-card" data-id="' + proveedoresEscape(row.proveedores_id) + '">' +
                    '<div class="proveedores-mini-head">' +
                        '<div class="proveedores-mini-title">' +
                            '<h6>' + proveedoresEscape(proveedoresValor(row.proveedor)) + '</h6>' +
                            '<small>RTN: ' + proveedoresEscape(proveedoresValor(row.rtn)) + '</small>' +
                        '</div>' +
                        statusProveedor(row) +
                    '</div>' +
                    '<div class="proveedores-mini-body">' +
                        '<div class="proveedores-mini-item"><i class="fas fa-phone-alt"></i><span>' + proveedoresEscape(proveedoresValor(row.telefono)) + '</span></div>' +
                        '<div class="proveedores-mini-item"><i class="fas fa-envelope"></i><span>' + proveedoresEscape(proveedoresValor(row.correo)) + '</span></div>' +
                        '<div class="proveedores-mini-item"><i class="fas fa-map-marker-alt"></i><span>' +
                            proveedoresEscape(proveedoresValor(row.departamento)) + ' / ' +
                            proveedoresEscape(proveedoresValor(row.municipio)) +
                        '</span></div>' +
                    '</div>' +
                    '<div class="proveedores-mini-footer">' + accionesProveedor(row) + '</div>' +
                '</article>';
        });

        return html + '</div>';
    }

    function renderProveedores() {
        if (proveedoresState.loading) {
            renderEstadoProveedores('loading');
            return;
        }

        var rows = proveedoresState.filtered || [];

        if (!rows.length) {
            renderEstadoProveedores('empty');
            aplicarPermisosProveedoresDataTableAsync();
            return;
        }

        var totalPages = Math.max(1, Math.ceil(rows.length / proveedoresState.pageSize));
        if (proveedoresState.page > totalPages) proveedoresState.page = totalPages;
        if (proveedoresState.page < 1) proveedoresState.page = 1;

        var start = (proveedoresState.page - 1) * proveedoresState.pageSize;
        var pageRows = rows.slice(start, start + proveedoresState.pageSize);

        var $list = $('#proveedoresListado');
        $list
            .removeClass('vista-detalle vista-miniatura')
            .addClass('vista-' + proveedoresState.view)
            .html(
                proveedoresState.view === 'miniatura'
                    ? renderMiniaturaProveedores(pageRows)
                    : renderDetalleProveedores(pageRows)
            );

        var end = Math.min(start + pageRows.length, rows.length);
        $('#proveedoresInfo').text(
            'Mostrando ' + (start + 1) + ' a ' + end + ' de ' + rows.length + ' registros'
        );

        renderPaginacionProveedores(totalPages);
        aplicarPermisosProveedoresDataTableAsync();

        if (typeof cerrarDropdownAcciones === 'function') {
            cerrarDropdownAcciones();
        }
    }

    function renderPaginacionProveedores(totalPages) {
        var current = proveedoresState.page;
        var html = '';

        function btn(label, page, disabled, active, icon) {
            return '<button type="button" class="proveedores-page-btn' + (active ? ' active' : '') + '"' +
                ' data-page="' + page + '"' +
                (disabled ? ' disabled' : '') + '>' +
                (icon ? '<i class="' + icon + ' mr-1"></i>' : '') +
                label +
            '</button>';
        }

        html += btn('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
        html += btn('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

        var from = Math.max(1, current - 2);
        var to = Math.min(totalPages, from + 4);
        from = Math.max(1, to - 4);

        for (var p = from; p <= to; p++) {
            html += btn(String(p), p, false, p === current, '');
        }

        html += btn('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
        html += btn('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

        $('#proveedoresPaginacion').html(html);
    }

    function editarProveedor(id) {
        var data = proveedorPorId(id);
        if (!data) {
            showNotify('error', 'Error', 'No se encontró la información del proveedor seleccionado.');
            return;
        }

        var url = '<?php echo SERVERURL;?>core/editarProveedores.php';
        $('#formProveedores #proveedores_id').val(data.proveedores_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formProveedores').serialize(),
            success: function (registro) {
                var valores = proveedoresParseJsonSeguro(registro, null);

                if (!Array.isArray(valores)) {
                    try {
                        valores = eval(registro);
                    } catch (e) {
                        valores = null;
                    }
                }

                if (!Array.isArray(valores)) {
                    showNotify('error', 'Error', 'La información del proveedor no pudo ser interpretada.');
                    return;
                }

                $('#formProveedores').attr('data-form', 'update');
                $('#formProveedores').attr('action', '<?php echo SERVERURL;?>ajax/modificarProveedoresAjax.php');
                $('#formProveedores')[0].reset();

                $('#reg_proveedor').hide();
                $('#edi_proveedor').show();
                $('#delete_proveedor').hide();

                $('#formProveedores #nombre_proveedores').val(valores[0]);
                $('#formProveedores #rtn_proveedores').val(valores[1]);
                $('#formProveedores #fecha_proveedores').attr('disabled', true).val(valores[2]);
                $('#formProveedores #departamento_proveedores').val(valores[3]).selectpicker('refresh');
                getMunicipiosProveedores(valores[4]);
                $('#formProveedores #municipio_proveedores').val(valores[4]).selectpicker('refresh');
                $('#formProveedores #dirección_proveedores').val(valores[5]);
                $('#formProveedores #telefono_proveedores').val(valores[6]);
                $('#formProveedores #correo_proveedores').val(valores[7]);

                $('#formProveedores #proveedores_activo').prop('checked', Number(valores[8]) === 1);

                $('#formProveedores #nombre_proveedores').attr('readonly', false);
                $('#formProveedores #apellido_proveedores').attr('readonly', false);
                $('#formProveedores #departamento_proveedores').attr('disabled', false);
                $('#formProveedores #municipio_proveedores').attr('disabled', false);
                $('#formProveedores #dirección_proveedores').attr('disabled', false);
                $('#formProveedores #telefono_proveedores').attr('readonly', false);
                $('#formProveedores #correo_proveedores').attr('readonly', false);
                $('#formProveedores #proveedores_activo').attr('disabled', false);
                $('#formProveedores #estado_proveedores').show();
                $('#formProveedores #grupo_editar_rtn').show();
                $('#formProveedores #rtn_proveedores').attr('readonly', true);
                $('#formProveedores #proceso_proveedores').val('Editar');

                $('#modal_registrar_proveedores').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            },
            error: function () {
                showNotify('error', 'Error', 'No se pudo cargar el proveedor para edición.');
            }
        });
    }

    function eliminarProveedor(id) {
        var data = proveedorPorId(id);
        if (!data) {
            showNotify('error', 'Error', 'No se encontró la información del proveedor seleccionado.');
            return;
        }

        var nombre = proveedoresValor(data.proveedor);
        var rtn = proveedoresValor(data.rtn);

        var span = document.createElement('span');
        span.innerHTML =
            '¿Desea eliminar permanentemente al proveedor?<br><br>' +
            '<strong>Nombre:</strong> ' + proveedoresEscape(nombre) + '<br>' +
            '<strong>RTN:</strong> ' + proveedoresEscape(rtn);

        swal({
            title: 'Confirmar eliminación',
            content: span,
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
        }).then(function (confirmar) {
            if (!confirmar) return;

            if (typeof showLoading === 'function') {
                showLoading('Eliminando registro...');
            }

            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL;?>ajax/eliminarProveedoresAjax.php',
                data: { proveedores_id: data.proveedores_id },
                dataType: 'json',
                success: function (response) {
                    swal.close();

                    if (response.status === 'success') {
                        showNotify('success', response.title, response.message);
                        proveedoresState.search = '';
                        $('#buscarProveedoresListado').val('');
                        listar_proveedores();
                    } else {
                        showNotify('error', response.title, response.message);
                    }
                },
                error: function () {
                    swal.close();
                    showNotify('error', 'Error', 'Ocurrió un error al procesar la solicitud.');
                }
            });
        });
    }

    function proveedoresExcelXmlEscape(value) {
        return String(value === null || typeof value === 'undefined' ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function proveedoresExcelColName(index) {
        var name = '';

        while (index >= 0) {
            name = String.fromCharCode((index % 26) + 65) + name;
            index = Math.floor(index / 26) - 1;
        }

        return name;
    }

    function proveedoresExcelCell(ref, value, styleId, numeric) {
        if (numeric) {
            var numberValue = Number(value);

            if (!isFinite(numberValue)) {
                numberValue = 0;
            }

            return '<c r="' + ref + '" s="' + styleId + '" t="n"><v>' +
                numberValue +
            '</v></c>';
        }

        var texto = proveedoresExcelXmlEscape(value);
        var raw = String(value === null || typeof value === 'undefined' ? '' : value);
        var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

        return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
            '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
    }

    function proveedoresGenerarXlsx(rows) {
        if (typeof JSZip === 'undefined') {
            return null;
        }

        var headers = [
            'Proveedor',
            'RTN',
            'Teléfono',
            'Correo',
            'Departamento',
            'Municipio',
            'Estado'
        ];

        var totalConRtn = rows.filter(function(row) {
            return String(row.rtn || '').trim() !== '';
        }).length;

        var totalConTelefono = rows.filter(function(row) {
            return String(row.telefono || '').trim() !== '';
        }).length;

        var totalConCorreo = rows.filter(function(row) {
            return String(row.correo || '').trim() !== '';
        }).length;

        var lastCol = 'G';
        var headerRow = 7;
        var firstDataRow = 8;
        var lastRow = Math.max(headerRow, headerRow + rows.length);
        var sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">' +
                proveedoresExcelCell('A1', 'IZZY • REPORTE DE PROVEEDORES', 1, false) +
            '</row>'
        );

        sheetRows.push(
            '<row r="2" ht="20" customHeight="1">' +
                proveedoresExcelCell(
                    'A2',
                    'Directorio y estado general de proveedores • Generado: ' +
                    new Date().toLocaleDateString('es-HN'),
                    2,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="3" ht="18" customHeight="1">' +
                proveedoresExcelCell('A3', 'REGISTROS', 6, false) +
                proveedoresExcelCell('C3', 'CON RTN', 6, false) +
                proveedoresExcelCell('E3', 'CON TELÉFONO', 6, false) +
                proveedoresExcelCell('G3', 'CON CORREO', 6, false) +
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">' +
                proveedoresExcelCell('A4', rows.length, 7, true) +
                proveedoresExcelCell('C4', totalConRtn, 7, true) +
                proveedoresExcelCell('E4', totalConTelefono, 7, true) +
                proveedoresExcelCell('G4', totalConCorreo, 7, true) +
            '</row>'
        );

        sheetRows.push(
            '<row r="5" ht="18" customHeight="1">' +
                proveedoresExcelCell(
                    'A5',
                    'Filtros: Estado ' +
                    ($('#estado_proveedores option:selected').text() || 'Todos') +
                    ' | Búsqueda: ' +
                    ($.trim($('#buscarProveedoresListado').val()) || 'Sin búsqueda'),
                    8,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="6" ht="18" customHeight="1">' +
                proveedoresExcelCell('A6', 'Detalle de proveedores filtrados', 8, false) +
            '</row>'
        );

        var headerCells = headers.map(function(header, index) {
            return proveedoresExcelCell(
                proveedoresExcelColName(index) + headerRow,
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
            var values = [
                proveedoresValor(row.proveedor, ''),
                proveedoresValor(row.rtn, ''),
                proveedoresValor(row.telefono, ''),
                proveedoresValor(row.correo, ''),
                proveedoresValor(row.departamento, ''),
                proveedoresValor(row.municipio, ''),
                Number(row.estado) === 1 ? 'Activo' : 'Inactivo'
            ];

            var cells = values.map(function(value, colIndex) {
                var style = 4;

                if (colIndex === 6) {
                    style = String(value).toLowerCase() === 'activo' ? 9 : 10;
                }

                return proveedoresExcelCell(
                    proveedoresExcelColName(colIndex) + excelRow,
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
                '<cols>' +
                    '<col min="1" max="1" width="30" customWidth="1"/>' +
                    '<col min="2" max="2" width="19" customWidth="1"/>' +
                    '<col min="3" max="3" width="17" customWidth="1"/>' +
                    '<col min="4" max="4" width="32" customWidth="1"/>' +
                    '<col min="5" max="6" width="20" customWidth="1"/>' +
                    '<col min="7" max="7" width="14" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join('') + '</sheetData>' +
                '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
                '<mergeCells count="10">' +
                    '<mergeCell ref="A1:G1"/>' +
                    '<mergeCell ref="A2:G2"/>' +
                    '<mergeCell ref="A3:B3"/>' +
                    '<mergeCell ref="A4:B4"/>' +
                    '<mergeCell ref="C3:D3"/>' +
                    '<mergeCell ref="C4:D4"/>' +
                    '<mergeCell ref="E3:F3"/>' +
                    '<mergeCell ref="E4:F4"/>' +
                    '<mergeCell ref="G3:G3"/>' +
                    '<mergeCell ref="G4:G4"/>' +
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
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/><bgColor indexed="64"/></patternFill></fill>' +
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
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
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
                '<sheets><sheet name="Proveedores" sheetId="1" r:id="rId1"/></sheets>' +
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
                console.error('Error al generar XLSX de Proveedores con JSZip legado:', errorGenerate);
                return Promise.reject(errorGenerate);
            }
        }

        return Promise.reject(
            new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().')
        );
    }

    function exportarProveedoresExcel() {
        var rows = proveedoresState.filtered || [];

        if (!rows.length) {
            showNotify('warning', 'Sin información', 'No hay proveedores para exportar.');
            return;
        }

        var promesaXlsx = proveedoresGenerarXlsx(rows);

        if (!promesaXlsx) {
            showNotify(
                'error',
                'Excel no disponible',
                'JSZip no está disponible en esta pantalla.'
            );
            return;
        }

        promesaXlsx
            .then(function(blob) {
                var url = URL.createObjectURL(blob);
                var anchor = document.createElement('a');

                anchor.href = url;
                anchor.download = 'Reporte_Proveedores.xlsx';
                document.body.appendChild(anchor);
                anchor.click();
                document.body.removeChild(anchor);

                window.setTimeout(function() {
                    URL.revokeObjectURL(url);
                }, 1000);
            })
            .catch(function(error) {
                console.error('Error al generar XLSX de Proveedores:', error);
                showNotify(
                    'error',
                    'Error al generar Excel',
                    'No se pudo generar el archivo Excel.'
                );
            });
    }

    function proveedoresObtenerLogoPdf(callback) {
        if (typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
            callback(imagen);
            return;
        }

        $.ajax({
            type: 'GET',
            url: '<?php echo SERVERURL;?>core/get_image.php',
            dataType: 'text',
            timeout: 15000
        }).done(function(imageUrl) {
            imageUrl = $.trim(imageUrl || '');

            if (!imageUrl) {
                showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
                return;
            }

            var img = new Image();
            img.crossOrigin = 'Anonymous';

            img.onload = function() {
                try {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');

                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    ctx.drawImage(img, 0, 0);

                    var dataUrl = canvas.toDataURL('image/png');

                    if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                        throw new Error('El logo no pudo convertirse a Data URL.');
                    }

                    imagen = dataUrl;
                    callback(dataUrl);
                } catch (error) {
                    console.error('Error preparando logo PDF Proveedores:', error);
                    showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
                }
            };

            img.onerror = function() {
                showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
            };

            img.src = imageUrl;
        }).fail(function(xhr) {
            console.error('Error obteniendo logo PDF Proveedores:', xhr.responseText);
            showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
        });
    }

    function previsualizarProveedoresPdf() {
        var rows = proveedoresState.filtered || [];

        if (!rows.length) {
            showNotify('warning', 'Sin información', 'No hay proveedores para mostrar en PDF.');
            return;
        }

        if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
            showNotify(
                'error',
                'PDF no disponible',
                'No se encontraron los componentes necesarios para previsualizar el PDF.'
            );
            return;
        }

        proveedoresObtenerLogoPdf(function(logoDataUrl) {
            var conRtn = rows.filter(function(r) {
                return String(r.rtn || '').trim() !== '';
            }).length;

            var conTelefono = rows.filter(function(r) {
                return String(r.telefono || '').trim() !== '';
            }).length;

            var conCorreo = rows.filter(function(r) {
                return String(r.correo || '').trim() !== '';
            }).length;

            var content = [
                {
                    table: {
                        widths: [100, '*', 160],
                        body: [[
                            {
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
                                },
                                fillColor: '#17324D',
                                margin: [8, 7, 8, 7]
                            },
                            {
                                stack: [
                                    {
                                        text: 'REPORTE DE PROVEEDORES',
                                        bold: true,
                                        fontSize: 16,
                                        color: '#FFFFFF'
                                    },
                                    {
                                        text: 'Directorio y estado general de proveedores',
                                        fontSize: 7.5,
                                        color: '#D8E5F0',
                                        margin: [0, 2, 0, 0]
                                    }
                                ],
                                fillColor: '#17324D',
                                margin: [0, 10, 0, 10]
                            },
                            {
                                stack: [
                                    {
                                        text: 'REPORTE EJECUTIVO',
                                        bold: true,
                                        fontSize: 6.5,
                                        color: '#72E2E5',
                                        alignment: 'right'
                                    },
                                    {
                                        text: new Date().toLocaleDateString('es-HN'),
                                        bold: true,
                                        fontSize: 9,
                                        color: '#FFFFFF',
                                        alignment: 'right'
                                    },
                                    {
                                        text: rows.length + ' registro(s) filtrado(s)',
                                        fontSize: 6.5,
                                        color: '#D8E5F0',
                                        alignment: 'right'
                                    }
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
                            text:
                                'Filtros aplicados: Estado ' +
                                ($('#estado_proveedores option:selected').text() || 'Todos') +
                                ' | Búsqueda: ' +
                                ($.trim($('#buscarProveedoresListado').val()) || 'Sin búsqueda'),
                            fontSize: 7,
                            color: '#52627A',
                            fillColor: '#F7F9FC',
                            margin: [8, 7, 8, 7]
                        }]]
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return .6; },
                        vLineWidth: function() { return .6; }
                    },
                    margin: [0, 0, 0, 10]
                },
                {
                    table: {
                        widths: ['*', '*', '*', '*'],
                        body: [[
                            {
                                stack: [
                                    {text: 'REGISTROS', fontSize: 6.3, bold: true, color: '#6B778C'},
                                    {text: String(rows.length), fontSize: 13, bold: true, color: '#172B4D'}
                                ],
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7]
                            },
                            {
                                stack: [
                                    {text: 'CON RTN', fontSize: 6.3, bold: true, color: '#6B778C'},
                                    {text: String(conRtn), fontSize: 13, bold: true, color: '#14804A'}
                                ],
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7]
                            },
                            {
                                stack: [
                                    {text: 'CON TELÉFONO', fontSize: 6.3, bold: true, color: '#6B778C'},
                                    {text: String(conTelefono), fontSize: 13, bold: true, color: '#2F9DDD'}
                                ],
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7]
                            },
                            {
                                stack: [
                                    {text: 'CON CORREO', fontSize: 6.3, bold: true, color: '#6B778C'},
                                    {text: String(conCorreo), fontSize: 13, bold: true, color: '#6554C0'}
                                ],
                                fillColor: '#F7F9FC',
                                margin: [8, 7, 8, 7]
                            }
                        ]]
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return .6; },
                        vLineWidth: function() { return .6; }
                    },
                    margin: [0, 0, 0, 12]
                }
            ];

            if (proveedoresState.view === 'miniatura') {
                content.push({
                    text: 'VISTA MINIATURA',
                    bold: true,
                    fontSize: 7,
                    color: '#17324D',
                    margin: [0, 0, 0, 7]
                });

                for (var i = 0; i < rows.length; i += 2) {
                    var makeCard = function(r) {
                        return {
                            table: {
                                widths: ['*'],
                                body: [[{
                                    stack: [
                                        {
                                            text: proveedoresValor(r.proveedor, 'Sin nombre'),
                                            bold: true,
                                            fontSize: 10,
                                            color: '#172B4D'
                                        },
                                        {
                                            text: 'RTN: ' + proveedoresValor(r.rtn),
                                            fontSize: 7,
                                            color: '#6B778C',
                                            margin: [0, 2, 0, 5]
                                        },
                                        {
                                            text: 'Teléfono: ' + proveedoresValor(r.telefono),
                                            fontSize: 7,
                                            color: '#253858'
                                        },
                                        {
                                            text: 'Correo: ' + proveedoresValor(r.correo),
                                            fontSize: 7,
                                            color: '#253858',
                                            margin: [0, 2, 0, 0]
                                        },
                                        {
                                            text:
                                                'Ubicación: ' +
                                                proveedoresValor(r.departamento, '') +
                                                ' / ' +
                                                proveedoresValor(r.municipio, ''),
                                            fontSize: 7,
                                            color: '#253858',
                                            margin: [0, 2, 0, 0]
                                        },
                                        {
                                            text: Number(r.estado) === 1 ? 'Activo' : 'Inactivo',
                                            fontSize: 7,
                                            bold: true,
                                            color: Number(r.estado) === 1 ? '#14804A' : '#C9372C',
                                            margin: [0, 4, 0, 0]
                                        }
                                    ],
                                    margin: [9, 8, 9, 8]
                                }]]
                            },
                            layout: {
                                hLineColor: function() { return '#DDE3EA'; },
                                vLineColor: function() { return '#DDE3EA'; },
                                hLineWidth: function() { return .6; },
                                vLineWidth: function() { return .6; }
                            }
                        };
                    };

                    content.push({
                        columns: [
                            {width: '*', stack: [makeCard(rows[i])]},
                            {width: 10, text: ''},
                            rows[i + 1]
                                ? {width: '*', stack: [makeCard(rows[i + 1])]}
                                : {width: '*', text: ''}
                        ],
                        margin: [0, 0, 0, 8]
                    });
                }
            } else {
                content.push({
                    text: 'VISTA DETALLE',
                    bold: true,
                    fontSize: 7,
                    color: '#17324D',
                    margin: [0, 0, 0, 7]
                });

                var body = [[
                    {text: 'PROVEEDOR', style: 'th', fillColor: '#17324D'},
                    {text: 'RTN', style: 'th', fillColor: '#17324D'},
                    {text: 'TELÉFONO', style: 'th', fillColor: '#17324D'},
                    {text: 'CORREO', style: 'th', fillColor: '#17324D'},
                    {text: 'DEPARTAMENTO', style: 'th', fillColor: '#17324D'},
                    {text: 'MUNICIPIO', style: 'th', fillColor: '#17324D'},
                    {text: 'ESTADO', style: 'th', fillColor: '#17324D'}
                ]];

                rows.forEach(function(r, index) {
                    var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

                    body.push([
                        {text: proveedoresValor(r.proveedor, ''), style: 'td', fillColor: fill},
                        {text: proveedoresValor(r.rtn), style: 'td', fillColor: fill},
                        {text: proveedoresValor(r.telefono), style: 'td', fillColor: fill},
                        {text: proveedoresValor(r.correo), style: 'td', fillColor: fill},
                        {text: proveedoresValor(r.departamento, ''), style: 'td', fillColor: fill},
                        {text: proveedoresValor(r.municipio, ''), style: 'td', fillColor: fill},
                        {
                            text: Number(r.estado) === 1 ? 'Activo' : 'Inactivo',
                            style: 'tdCenter',
                            bold: true,
                            color: Number(r.estado) === 1 ? '#14804A' : '#C9372C',
                            fillColor: fill
                        }
                    ]);
                });

                content.push({
                    table: {
                        headerRows: 1,
                        widths: [120, 80, 75, '*', 90, 90, 55],
                        body: body
                    },
                    layout: {
                        hLineColor: function() { return '#DDE3EA'; },
                        vLineColor: function() { return '#DDE3EA'; },
                        hLineWidth: function() { return .55; },
                        vLineWidth: function() { return .55; },
                        paddingLeft: function() { return 5; },
                        paddingRight: function() { return 5; },
                        paddingTop: function() { return 6; },
                        paddingBottom: function() { return 6; }
                    }
                });
            }

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
                                text: 'IZZY • Proveedores',
                                fontSize: 7,
                                color: '#7A869A'
                            },
                            {
                                text: 'Página ' + currentPage + ' de ' + pageCount,
                                fontSize: 7,
                                color: '#7A869A',
                                alignment: 'right'
                            }
                        ]
                    };
                },
                content: content,
                styles: {
                    th: {
                        fontSize: 6.2,
                        bold: true,
                        color: '#FFFFFF',
                        alignment: 'center'
                    },
                    td: {
                        fontSize: 6.3,
                        color: '#253858'
                    },
                    tdCenter: {
                        fontSize: 6.3,
                        color: '#253858',
                        alignment: 'center'
                    }
                }
            };

            /*
             * Compatibilidad: getDataUrl está soportado por la versión
             * de pdfMake que ya utiliza Clientes en esta instalación.
             * No usar getBlob(): en esta versión no existe.
             */
            pdfMake.createPdf(doc).getDataUrl(function(url) {
                abrirModalPdfPublico(
                    url,
                    'Reporte de Proveedores',
                    'Reporte_Proveedores.pdf'
                );
            });
        });
    }

    function inicializarEventosProveedores() {
        $('#form_main_proveedores')
            .off('submit.proveedores')
            .on('submit.proveedores', function (e) {
                e.preventDefault();
                proveedoresState.page = 1;
                listar_proveedores();
            });

        $('#form_main_proveedores')
            .off('reset.proveedores')
            .on('reset.proveedores', function () {
                var form = this;

                window.setTimeout(function () {
                    $(form).find('.selectpicker').val('').selectpicker('refresh');
                    $('#estado_proveedores').val('1').selectpicker('refresh');
                    proveedoresState.search = '';
                    $('#buscarProveedoresListado').val('');
                    proveedoresState.page = 1;
                    listar_proveedores();
                }, 0);
            });

        $('#buscarProveedoresListado')
            .off('input.proveedores')
            .on('input.proveedores', function () {
                proveedoresState.search = $(this).val();
                proveedoresState.page = 1;
                aplicarFiltroProveedores();
            });

        $('#limpiarBuscarProveedores')
            .off('click.proveedores')
            .on('click.proveedores', function () {
                proveedoresState.search = '';
                $('#buscarProveedoresListado').val('').focus();
                proveedoresState.page = 1;
                aplicarFiltroProveedores();
            });

        $('#proveedoresPageSize')
            .off('change.proveedores')
            .on('change.proveedores', function () {
                var n = parseInt($(this).val(), 10);
                if (!n || n < 1) return;

                proveedoresState.pageSize = n;

                if (proveedoresState.view === 'miniatura') {
                    proveedoresState.pageSizeMiniatura = n;
                } else {
                    proveedoresState.pageSizeDetalle = n;
                }

                proveedoresState.page = 1;
                renderProveedores();
            });

        $('.proveedores-view-btn')
            .off('click.proveedores')
            .on('click.proveedores', function () {
                cambiarVistaProveedores($(this).data('view'));
            });

        $('#proveedoresPaginacion')
            .off('click.proveedores', '.proveedores-page-btn')
            .on('click.proveedores', '.proveedores-page-btn', function () {
                if (this.disabled) return;
                var p = parseInt($(this).attr('data-page'), 10);
                if (!p) return;
                proveedoresState.page = p;
                renderProveedores();
            });

        $('#proveedoresListado')
            .off('click.proveedoresEditar', '.table_editar')
            .on('click.proveedoresEditar', '.table_editar', function (e) {
                e.preventDefault();
                editarProveedor($(this).attr('data-id'));
            });

        $('#proveedoresListado')
            .off('click.proveedoresEliminar', '.table_eliminar')
            .on('click.proveedoresEliminar', '.table_eliminar', function (e) {
                e.preventDefault();
                eliminarProveedor($(this).attr('data-id'));
            });

        $('#btnActualizarProveedores')
            .off('click.proveedores')
            .on('click.proveedores', listar_proveedores);

        $('#btnNuevoProveedor')
            .off('click.proveedores')
            .on('click.proveedores', function () {
                if (typeof modal_proveedores === 'function') {
                    modal_proveedores();
                } else {
                    showNotify('error', 'Error', 'No se encontró la función para registrar proveedores.');
                }
            });

        $('#btnExcelProveedores')
            .off('click.proveedores')
            .on('click.proveedores', exportarProveedoresExcel);

        $('#btnPdfProveedores')
            .off('click.proveedores')
            .on('click.proveedores', previsualizarProveedoresPdf);

        var resizeTimer = null;

        $(window)
            .off('resize.proveedoresResponsive orientationchange.proveedoresResponsive')
            .on('resize.proveedoresResponsive orientationchange.proveedoresResponsive', function () {
                clearTimeout(resizeTimer);

                resizeTimer = setTimeout(function () {
                    var objetivo = proveedoresEsMovil()
                        ? 'miniatura'
                        : proveedoresState.preferredView;

                    actualizarDisponibilidadVistaProveedores();

                    if (proveedoresState.view !== objetivo) {
                        proveedoresState.view = objetivo;
                        proveedoresState.page = 1;
                        actualizarBotonesVistaProveedores();
                        sincronizarPageSizeProveedores();
                        renderProveedores();
                    }
                }, 120);
            });
    }

    function getEstadoProveedores() {
        var url = '<?php echo SERVERURL;?>core/getEstado.php';

        return $.ajax({
            type: 'POST',
            url: url,
            async: true,
            success: function (data) {
                $('#form_main_proveedores #estado_proveedores').html(data);
                $('#form_main_proveedores #estado_proveedores').val('1');

                if ($.fn.selectpicker) {
                    $('#form_main_proveedores #estado_proveedores').selectpicker('refresh');
                }
            }
        });
    }

    $(function () {
        configurarPanelProveedores(
            '#btnToggleFiltrosProveedores',
            '#proveedoresFiltrosContenido',
            PROVEEDORES_STORAGE_FILTROS,
            true
        );

        configurarPanelProveedores(
            '#btnToggleKpisProveedores',
            '#proveedoresKpisContenido',
            PROVEEDORES_STORAGE_KPIS,
            true
        );

        inicializarVistaProveedores();
        inicializarEventosProveedores();

        if (typeof getDepartamentoProveedores === 'function') {
            getDepartamentoProveedores();
        }

        getEstadoProveedores().always(function () {
            var $estado = $('#form_main_proveedores #estado_proveedores');

            if ($estado.val() === null || $estado.val() === undefined || $estado.val() === '') {
                $estado.val('1');
                if ($.fn.selectpicker) $estado.selectpicker('refresh');
            }

            listar_proveedores();
        });
    });

    /* Exposición controlada para código legado que ya llama listar_proveedores(). */
    window.listar_proveedores = listar_proveedores;
})(jQuery);


//INICIO EDITAR RTN PROVEEDORES
//SE LLAMA AL MODAL CUANDO PRESIONAMOS EN EDITAR RTN EN CLIENTES
$('#formProveedores #grupo_editar_rtn').on('click', function(e) {
    e.preventDefault();

    $('#formEditarRTNProveedores')[0].reset();
    $('#formEditarRTNProveedores #pro_proveedores').val("Editar");
    $('#formEditarRTNProveedores #proveedores_id').val($('#formProveedores #proveedores_id').val());
    $('#formEditarRTNProveedores #proveedor').val($('#formProveedores #nombre_proveedores').val());
    $('#modalEditarRTNProveedores').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$(document).ready(function() {
    $("#modalEditarRTNProveedores").on('shown.bs.modal', function() {
        $(this).find('#formEditarRTNProveedores #rtn_proveedor').focus();
    });
});

$('#editar_rtn_proveedores').on('click', function(e) {
    e.preventDefault();

    editRTNProvider($('#formEditarRTNProveedores #proveedores_id').val(), $(
        '#formEditarRTNProveedores #rtn_proveedor').val());
});

function editRTNProvider(proveedores_id, rtn) {
    getNombreProveedor(proveedores_id).then(function(nombreProveedor) {
        return swal({
            title: "¿Estas seguro?",
            text: "¿Desea editar el RTN para el proveedor: " + nombreProveedor + "?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true,
                    closeModal: true
                },
                confirm: {
                    text: "¡Sí, deseo editarlo!",
                    className: "btn-primary"
                }
            },
            closeOnClickOutside: false,
            closeOnEsc: false
        });
    }).then(function(willConfirm) {
        if (willConfirm === true) {
            editRTNProveedor(proveedores_id, rtn);
        }
    }).catch(function() {
        showNotify('error', 'Error', 'No se pudo consultar la información del proveedor');
    });
}

function editRTNProveedor(proveedores_id, rtn) {
    var url = '<?php echo SERVERURL; ?>core/editRTNProveedor.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'proveedores_id=' + proveedores_id + '&rtn=' + rtn,
        success: function(data) {
            if (data == 1) {
                showNotify('success', 'Success', 'El RTN ha sido actualizado satisfactoriamente');
                listar_proveedores();
                $('#formProveedores #rtn_proveedores').val(rtn);
            } else if (data == 2) {
                showNotify('error', 'Error', 'Error el RTN no se puede actualizar');
            } else if (data == 3) {
                showNotify('error', 'Error', 'El RTN ya existe');
            }
        }
    });
}

function getNombreProveedor(proveedores_id) {
    var url = '<?php echo SERVERURL; ?>core/getNombreProveedor.php';

    return $.ajax({
        type: 'POST',
        url: url,
        data: 'proveedores_id=' + proveedores_id,
        timeout: 30000
    }).then(function(data) {
        var datos = proveedoresParseJsonSeguro(data, []);
        return datos[0] || '';
    });
}
//FIN EDITAR RTN PROVEEDORES
//FIN ACCIONES FROMULARIO PROVEEDORES
/*FIN FORMULARIO PROVEEDORES*/
$(document).ready(function() {
    $("#modal_registrar_proveedores").on('shown.bs.modal', function() {
        $(this).find('#formProveedores #nombre_proveedores').focus();
    });
});

$('#formProveedores #label_proveedores_activo').html("Activo");

$('#formProveedores .switch').change(function() {
    if ($('input[name=proveedores_activo]').is(':checked')) {
        $('#formProveedores #label_proveedores_activo').html("Activo");
        return true;
    } else {
        $('#formProveedores #label_proveedores_activo').html("Inactivo");
        return false;
    }
});

function getEstadoProveedores() {
    var url = '<?php echo SERVERURL;?>core/getEstado.php';

    return $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_proveedores #estado_proveedores').html("");
            $('#form_main_proveedores #estado_proveedores').html(data);
            $('#form_main_proveedores #estado_proveedores').val('1');
            $('#form_main_proveedores #estado_proveedores').selectpicker('refresh');
        }
    });
}
</script>