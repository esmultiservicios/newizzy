<script>
/* =========================================================
   IZZY 6.0 | PRIVILEGIOS
   - Listados propios con DIV/Cards en la vista principal y modales
   - Detalle / Miniatura
   - Excel XLSX real
   - PDF con preview getDataUrl()
   - Acciones directas sobre el registro visible
   ========================================================= */

var PRIVILEGIOS_STORAGE_VISTA = 'izzy.privilegios.tipo_vista';
var PRIVILEGIOS_MOBILE_QUERY = '(max-width: 767.98px)';

var privilegiosState = {
    registros: [],
    filtrados: [],
    pagina: 1,
    porPagina: 10,
    porPaginaDetalle: 10,
    porPaginaMiniatura: 6,
    vista: 'detalle',
    vistaPreferida: 'detalle',
    busqueda: '',
    cargando: false
};

var privilegiosAccess = {
    menu: crearEstadoAcceso({
        tipo: 'menu',
        modal: '#modal_registrar_menuaccesos',
        form: '#formMenuAccesos',
        prefijo: 'menuAccess',
        endpointLista: '<?php echo SERVERURL;?>core/llenarDataTableMenuAccesos.php',
        endpointToggle: '<?php echo SERVERURL;?>core/asignarMenuAcceso.php',
        parametroId: 'menu_id',
        titulo: 'Menús',
        subtitulo: 'Menús disponibles para el privilegio seleccionado',
        exportTitle: 'PRIVILEGIOS - MENÚS',
        campos: [
            { key: 'descripcion', label: 'Menú' }
        ]
    }),
    submenu: crearEstadoAcceso({
        tipo: 'submenu',
        modal: '#modal_registrar_submenuaccesos',
        form: '#formSubMenuAccesos',
        prefijo: 'submenuAccess',
        endpointLista: '<?php echo SERVERURL;?>core/llenarDataTableSubMenuAccesos.php',
        endpointToggle: '<?php echo SERVERURL;?>core/asignarSubMenuAcceso.php',
        parametroId: 'submenu_id',
        titulo: 'Submenús',
        subtitulo: 'Submenús de nivel 1 disponibles para el privilegio seleccionado',
        exportTitle: 'PRIVILEGIOS - SUBMENÚS',
        campos: [
            { key: 'descripcion_padre', label: 'Menú' },
            { key: 'descripcion', label: 'Submenú' }
        ]
    }),
    submenu1: crearEstadoAcceso({
        tipo: 'submenu1',
        modal: '#modal_registrar_submenu1accesos',
        form: '#formSubMenu1Accesos',
        prefijo: 'submenu1Access',
        endpointLista: '<?php echo SERVERURL;?>core/llenarDataTableSubMenu1Accesos.php',
        endpointToggle: '<?php echo SERVERURL;?>core/asignarSubMenu1Acceso.php',
        parametroId: 'submenu1_id',
        titulo: 'Submenús Nivel 2',
        subtitulo: 'Submenús de nivel 2 disponibles para el privilegio seleccionado',
        exportTitle: 'PRIVILEGIOS - SUBMENÚS NIVEL 2',
        campos: [
            { key: 'descripcion', label: 'Submenú' },
            { key: 'submenu_descripcion', label: 'Submenú Nivel 2' }
        ]
    })
};

function crearEstadoAcceso(config) {
    config.registros = [];
    config.filtrados = [];
    config.pagina = 1;
    config.porPagina = 10;
    config.porPaginaDetalle = 10;
    config.porPaginaMiniatura = 6;
    config.vista = 'detalle';
    config.vistaPreferida = 'detalle';
    config.busqueda = '';
    config.privilegioId = '';
    config.privilegioNombre = '';
    config.cargando = false;
    config.storageView = 'izzy.privilegios.acceso.' + config.tipo + '.vista';
    return config;
}

function privilegiosEsMovil() {
    return window.matchMedia
        ? window.matchMedia(PRIVILEGIOS_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function privilegiosEscape(valor) {
    if (valor === null || valor === undefined) {
        return '';
    }

    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function privilegiosTexto(valor, fallback) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }

    return String(valor).trim();
}

function privilegiosEstadoActivo(valor) {
    return parseInt(valor, 10) === 1;
}

function privilegiosAsignado(valor) {
    if (valor === true || valor === 1) {
        return true;
    }

    var texto = String(valor == null ? '' : valor).trim().toLowerCase();
    return texto === '1' || texto === 'true' || texto === 'si' || texto === 'sí' || texto === 'asignado';
}

function privilegiosNormalizarRespuesta(response) {
    if (Array.isArray(response)) {
        return response;
    }

    if (response && Array.isArray(response.data)) {
        return response.data;
    }

    if (response && Array.isArray(response.aaData)) {
        return response.aaData;
    }

    if (response && response.result && Array.isArray(response.result.data)) {
        return response.result.data;
    }

    return [];
}

function privilegiosNotificar(tipo, titulo, mensaje) {
    if (typeof showNotify === 'function') {
        showNotify(tipo, titulo, mensaje);
    }
}

function privilegiosConfigurarPanel(buttonSelector, contentSelector, storageKey) {
    var $button = $(buttonSelector);
    var $content = $(contentSelector);

    if (!$button.length || !$content.length) {
        return;
    }

    var visible = true;

    try {
        var guardado = localStorage.getItem(storageKey);
        if (guardado !== null) {
            visible = guardado === '1';
        }
    } catch (error) {}

    function sincronizar() {
        $content.toggle(visible);
        $button.attr('aria-expanded', visible ? 'true' : 'false');
        $button.find('span').text(visible ? 'Ocultar' : 'Mostrar');
        $button.find('i')
            .toggleClass('fa-chevron-up', visible)
            .toggleClass('fa-chevron-down', !visible);
    }

    sincronizar();

    $button.off('click.privilegiosPanel').on('click.privilegiosPanel', function () {
        visible = !visible;
        $content.stop(true, true)[visible ? 'slideDown' : 'slideUp'](180);
        sincronizar();

        try {
            localStorage.setItem(storageKey, visible ? '1' : '0');
        } catch (error) {}
    });
}

/* =========================================================
   VISTA PRINCIPAL
   ========================================================= */

function privilegiosInicializarVista() {
    var guardada = 'detalle';

    try {
        guardada = localStorage.getItem(PRIVILEGIOS_STORAGE_VISTA) || 'detalle';
    } catch (error) {}

    privilegiosState.vistaPreferida = guardada === 'miniatura' ? 'miniatura' : 'detalle';
    privilegiosState.vista = privilegiosEsMovil() ? 'miniatura' : privilegiosState.vistaPreferida;
    privilegiosSincronizarVista();
    privilegiosSincronizarPageSize();
}

function privilegiosSincronizarVista() {
    var movil = privilegiosEsMovil();

    if (movil) {
        privilegiosState.vista = 'miniatura';
    }

    $('.privilegios-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.privilegios-view-btn').removeClass('active').attr('aria-pressed', 'false');
    $('.privilegios-view-btn[data-view="' + privilegiosState.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function privilegiosSincronizarPageSize() {
    var mini = privilegiosState.vista === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
    var seleccionado = mini ? privilegiosState.porPaginaMiniatura : privilegiosState.porPaginaDetalle;

    if (opciones.indexOf(seleccionado) === -1) {
        seleccionado = opciones[0];
    }

    var $select = $('#privilegiosPageSize').empty();

    opciones.forEach(function (valor) {
        $select.append($('<option></option>').val(valor).text(valor));
    });

    privilegiosState.porPagina = seleccionado;
    $select.val(String(seleccionado));
}

function listar_privilegio() {
    var estado = $('#form_main_privilegios #estado_privilegios').val();

    privilegiosState.cargando = true;
    privilegiosRender();

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTablePrivilegio.php',
        dataType: 'json',
        cache: false,
        data: { estado: estado }
    })
    .done(function (response) {
        privilegiosState.registros = privilegiosNormalizarRespuesta(response);
        privilegiosState.cargando = false;
        privilegiosState.pagina = 1;
        privilegiosAplicarFiltro();

        if (response && response.success === false) {
            privilegiosNotificar('error', 'Error', response.message || 'No se pudo cargar el listado de privilegios.');
        }
    })
    .fail(function (xhr) {
        privilegiosState.registros = [];
        privilegiosState.filtrados = [];
        privilegiosState.cargando = false;
        privilegiosActualizarKpis();
        privilegiosRender();
        privilegiosNotificar('error', 'Error', 'No se pudo cargar el listado de privilegios.');
        console.error('Error al cargar privilegios:', xhr.responseText);
    });
}

function privilegiosAplicarFiltro() {
    var busqueda = String(privilegiosState.busqueda || '').toLowerCase().trim();

    privilegiosState.filtrados = privilegiosState.registros.filter(function (row) {
        if (!busqueda) {
            return true;
        }

        var texto = [
            row.nombre,
            privilegiosEstadoActivo(row.estado) ? 'activo' : 'inactivo',
            row.menus_asignados,
            row.submenus_asignados,
            row.submenus1_asignados
        ].join(' ').toLowerCase();

        return texto.indexOf(busqueda) !== -1;
    });

    privilegiosActualizarKpis();
    privilegiosRender();
}

function privilegiosActualizarKpis() {
    var rows = privilegiosState.filtrados || [];
    var activos = 0;
    var menus = 0;
    var submenus = 0;

    rows.forEach(function (row) {
        if (privilegiosEstadoActivo(row.estado)) {
            activos++;
        }

        menus += parseInt(row.menus_asignados, 10) || 0;
        submenus += (parseInt(row.submenus_asignados, 10) || 0) + (parseInt(row.submenus1_asignados, 10) || 0);
    });

    $('#privilegiosKpiTotal').text(rows.length);
    $('#privilegiosKpiActivos').text(activos);
    $('#privilegiosKpiMenus').text(menus);
    $('#privilegiosKpiSubmenus').text(submenus);
}

function privilegiosEstadoHtml(row) {
    return privilegiosEstadoActivo(row.estado)
        ? '<span class="privilegios-status-badge is-active"><i class="fas fa-check-circle"></i>Activo</span>'
        : '<span class="privilegios-status-badge is-inactive"><i class="fas fa-times-circle"></i>Inactivo</span>';
}

function privilegiosAsignadosHtml(valor, icono) {
    var total = parseInt(valor, 10) || 0;

    return '<span class="privilegios-assigned-pill">' +
        '<i class="' + icono + '"></i>' +
        '<strong>' + total + '</strong>' +
        '<span>' + (total === 1 ? 'asignado' : 'asignados') + '</span>' +
    '</span>';
}

function privilegiosAccionesHtml(row) {
    var id = privilegiosEscape(row.privilegio_id);

    return '' +
        '<div class="dropdown acciones-dropdown privilegios-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-ver table_accesos ocultar js-privilegio-accion" data-action="menu" data-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-ver"><i class="fas fa-bars"></i></span>' +
                    '<span class="accion-label">Asignar Menú</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-ver table_accesos ocultar js-privilegio-accion" data-action="submenu" data-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-ver"><i class="fas fa-stream"></i></span>' +
                    '<span class="accion-label">Asignar Submenú</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-ver table_accesos ocultar js-privilegio-accion" data-action="submenu1" data-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-ver"><i class="fas fa-sitemap"></i></span>' +
                    '<span class="accion-label">Asignar Submenú 1</span>' +
                '</button>' +
                '<div class="dropdown-divider"></div>' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar js-privilegio-accion" data-action="editar" data-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar js-privilegio-accion" data-action="eliminar" data-id="' + id + '">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function privilegiosRenderDetalle(rows) {
    var html = '' +
        '<div class="privilegios-detail-header">' +
            '<div>Acciones</div>' +
            '<div>Privilegio</div>' +
            '<div>Estado</div>' +
            '<div>Menús</div>' +
            '<div>Submenús</div>' +
            '<div>Submenús 1</div>' +
        '</div>';

    rows.forEach(function (row) {
        html += '' +
            '<article class="privilegios-detail-row" data-id="' + privilegiosEscape(row.privilegio_id) + '">' +
                '<div class="privilegios-detail-cell privilegios-actions-cell">' +
                    '<span class="privilegios-cell-label">Acciones</span>' +
                    privilegiosAccionesHtml(row) +
                '</div>' +
                '<div class="privilegios-detail-cell">' +
                    '<span class="privilegios-cell-label">Privilegio</span>' +
                    '<div class="privilegios-name">' +
                        '<span class="privilegios-name-icon"><i class="fas fa-user-shield"></i></span>' +
                        '<strong>' + privilegiosEscape(privilegiosTexto(row.nombre, 'Sin nombre')) + '</strong>' +
                    '</div>' +
                '</div>' +
                '<div class="privilegios-detail-cell privilegios-center-cell">' +
                    '<span class="privilegios-cell-label">Estado</span>' +
                    privilegiosEstadoHtml(row) +
                '</div>' +
                '<div class="privilegios-detail-cell privilegios-center-cell">' +
                    '<span class="privilegios-cell-label">Menús</span>' +
                    privilegiosAsignadosHtml(row.menus_asignados, 'fas fa-bars') +
                '</div>' +
                '<div class="privilegios-detail-cell privilegios-center-cell">' +
                    '<span class="privilegios-cell-label">Submenús</span>' +
                    privilegiosAsignadosHtml(row.submenus_asignados, 'fas fa-stream') +
                '</div>' +
                '<div class="privilegios-detail-cell privilegios-center-cell">' +
                    '<span class="privilegios-cell-label">Submenús 1</span>' +
                    privilegiosAsignadosHtml(row.submenus1_asignados, 'fas fa-sitemap') +
                '</div>' +
            '</article>';
    });

    return html;
}

function privilegiosRenderMiniatura(rows) {
    var html = '';

    rows.forEach(function (row) {
        html += '' +
            '<article class="privilegios-mini-card" data-id="' + privilegiosEscape(row.privilegio_id) + '">' +
                '<div class="privilegios-mini-topline"></div>' +
                '<div class="privilegios-mini-header">' +
                    '<div class="privilegios-mini-title-wrap">' +
                        '<span class="privilegios-name-icon"><i class="fas fa-user-shield"></i></span>' +
                        '<div><h4>' + privilegiosEscape(privilegiosTexto(row.nombre, 'Sin nombre')) + '</h4></div>' +
                    '</div>' +
                    privilegiosEstadoHtml(row) +
                '</div>' +
                '<div class="privilegios-mini-body">' +
                    '<div class="privilegios-mini-stat"><span>Menús</span><strong>' + (parseInt(row.menus_asignados, 10) || 0) + '</strong></div>' +
                    '<div class="privilegios-mini-stat"><span>Submenús</span><strong>' + (parseInt(row.submenus_asignados, 10) || 0) + '</strong></div>' +
                    '<div class="privilegios-mini-stat"><span>Submenús 1</span><strong>' + (parseInt(row.submenus1_asignados, 10) || 0) + '</strong></div>' +
                '</div>' +
                '<div class="privilegios-mini-footer">' + privilegiosAccionesHtml(row) + '</div>' +
            '</article>';
    });

    return html;
}

function privilegiosRenderPaginacion(totalPaginas) {
    var actual = privilegiosState.pagina;
    var html = '';

    function boton(texto, pagina, disabled, active, icono, iconoDerecha) {
        html += '<button type="button" class="privilegios-page-btn' + (active ? ' active' : '') + '" data-page="' + pagina + '"' + (disabled ? ' disabled' : '') + '>';
        if (icono && !iconoDerecha) html += '<i class="' + icono + '"></i>';
        html += '<span>' + texto + '</span>';
        if (icono && iconoDerecha) html += '<i class="' + icono + '"></i>';
        html += '</button>';
    }

    boton('Inicio', 1, actual === 1, false, 'fas fa-angle-double-left', false);
    boton('Anterior', actual - 1, actual === 1, false, 'fas fa-angle-left', false);

    var desde = Math.max(1, actual - 2);
    var hasta = Math.min(totalPaginas, desde + 4);
    desde = Math.max(1, hasta - 4);

    for (var pagina = desde; pagina <= hasta; pagina++) {
        boton(String(pagina), pagina, false, pagina === actual, '', false);
    }

    boton('Siguiente', actual + 1, actual === totalPaginas, false, 'fas fa-angle-right', true);
    boton('Final', totalPaginas, actual === totalPaginas, false, 'fas fa-angle-double-right', true);

    $('#privilegiosPaginacion').html(html);
}

function privilegiosAplicarPermisos() {
    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' && typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function privilegiosRender() {
    var $listado = $('#privilegiosListado');

    if (privilegiosState.cargando) {
        $listado.removeClass('vista-detalle vista-miniatura').html(
            '<div class="privilegios-state-box"><i class="fas fa-spinner fa-spin"></i><div><strong>Cargando privilegios...</strong><small>Espere un momento.</small></div></div>'
        );
        $('#privilegiosVacio').hide();
        $('#privilegiosInfo').text('Mostrando 0 registros');
        $('#privilegiosPaginacion').empty();
        return;
    }

    var total = privilegiosState.filtrados.length;
    var totalPaginas = Math.max(1, Math.ceil(total / privilegiosState.porPagina));

    if (privilegiosState.pagina > totalPaginas) {
        privilegiosState.pagina = totalPaginas;
    }

    var inicio = (privilegiosState.pagina - 1) * privilegiosState.porPagina;
    var fin = Math.min(inicio + privilegiosState.porPagina, total);
    var rows = privilegiosState.filtrados.slice(inicio, fin);

    $listado
        .toggleClass('vista-detalle', privilegiosState.vista === 'detalle')
        .toggleClass('vista-miniatura', privilegiosState.vista === 'miniatura')
        .html(total ? (privilegiosState.vista === 'detalle' ? privilegiosRenderDetalle(rows) : privilegiosRenderMiniatura(rows)) : '');

    $('#privilegiosVacio').toggle(total === 0);
    $('#privilegiosInfo').text(
        total > 0
            ? 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
            : 'Mostrando 0 registros'
    );

    if (total > 0) {
        privilegiosRenderPaginacion(totalPaginas);
    } else {
        $('#privilegiosPaginacion').empty();
    }

    privilegiosAplicarPermisos();

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function privilegiosBuscarPorId(id) {
    var key = String(id == null ? '' : id);
    var resultado = null;

    privilegiosState.registros.some(function (row) {
        if (String(row.privilegio_id) === key) {
            resultado = row;
            return true;
        }
        return false;
    });

    return resultado;
}

function privilegiosCambiarVista(vista) {
    var siguiente = vista === 'miniatura' ? 'miniatura' : 'detalle';

    if (privilegiosEsMovil()) {
        siguiente = 'miniatura';
    }

    privilegiosState.vista = siguiente;

    if (!privilegiosEsMovil()) {
        privilegiosState.vistaPreferida = siguiente;
        try { localStorage.setItem(PRIVILEGIOS_STORAGE_VISTA, siguiente); } catch (error) {}
    }

    privilegiosState.pagina = 1;
    privilegiosSincronizarVista();
    privilegiosSincronizarPageSize();
    privilegiosRender();
}

/* =========================================================
   ACCIONES PRINCIPALES
   ========================================================= */

function privilegiosAbrirAccesos(tipo, row) {
    var config = privilegiosAccess[tipo];

    if (!config || !row) {
        privilegiosNotificar('error', 'Error', 'No fue posible identificar el privilegio seleccionado.');
        return;
    }

    config.privilegioId = row.privilegio_id;
    config.privilegioNombre = privilegiosTexto(row.nombre, 'Sin nombre');
    config.pagina = 1;
    config.busqueda = '';

    $(config.form + ' #privilegio_id_accesos').val(row.privilegio_id);
    $(config.form).attr({
        'data-form': 'save',
        action: tipo === 'menu'
            ? '<?php echo SERVERURL;?>ajax/addMenuAccesosAjax.php'
            : (tipo === 'submenu'
                ? '<?php echo SERVERURL;?>ajax/addSubMenuAccesosAjax.php'
                : '<?php echo SERVERURL;?>ajax/addSubMenu1AccesosAjax.php')
    });

    $(config.modal + ' .priv-access-context-name').text(config.privilegioNombre);
    $(config.modal + ' .modal-title .priv-access-title-text').text(config.titulo + ': ' + config.privilegioNombre);
    $('#' + config.prefijo + 'Search').val('');

    privilegiosAccesoSincronizarVista(config);
    privilegiosAccesoSincronizarPageSize(config);
    listarAccesosPrivilegio(tipo);

    $(config.modal).modal({
        show: true,
        keyboard: true,
        backdrop: 'static'
    });
}

function privilegiosEditar(row) {
    if (!row) {
        privilegiosNotificar('error', 'Error', 'No fue posible identificar el privilegio seleccionado.');
        return;
    }

    $('#formPrivilegios #privilegio_id_').val(row.privilegio_id);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/editarPrivilegios.php',
        data: $('#formPrivilegios').serialize()
    })
    .done(function (registro) {
        var valores;

        try {
            valores = typeof registro === 'string' ? eval(registro) : registro;
        } catch (error) {
            privilegiosNotificar('error', 'Error', 'No se pudo procesar la información del privilegio.');
            console.error('Respuesta inválida al editar privilegio:', registro, error);
            return;
        }

        $('#formPrivilegios').attr({
            'data-form': 'update',
            action: '<?php echo SERVERURL;?>ajax/modificarPrivilegioAjax.php'
        });

        $('#formPrivilegios')[0].reset();
        $('#formPrivilegios #privilegio_id_').val(row.privilegio_id);
        $('#formPrivilegios #privilegios_nombre').val(valores[0]);
        $('#formPrivilegios #privilegio_activo').prop('checked', parseInt(valores[1], 10) === 1);
        $('#formPrivilegios #label_privilegio_activo').text(parseInt(valores[1], 10) === 1 ? 'Activo' : 'Inactivo');

        $('#reg_privilegios').hide();
        $('#edi_privilegios').show();
        $('#delete_privilegios').hide();
        $('#formPrivilegios #privilegios_nombre').prop('readonly', false);
        $('#formPrivilegios #privilegio_activo').prop('disabled', false);
        $('#formPrivilegios #estado_privilegios').show();
        $('#formPrivilegios #proceso_privilegios').val('Editar');

        $('#modal_registrar_privilegios').modal({
            show: true,
            keyboard: true,
            backdrop: 'static'
        });
    })
    .fail(function (xhr) {
        privilegiosNotificar('error', 'Error', 'No se pudo cargar la información del privilegio.');
        console.error('Error al editar privilegio:', xhr.responseText);
    });
}

function privilegiosCerrarAlertaEliminar() {
    if (typeof swal === 'function' && typeof swal.close === 'function') {
        swal.close();
        return;
    }
}

function privilegiosEjecutarEliminacion(row) {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>ajax/eliminarPrivilegiosAjax.php',
        dataType: 'json',
        data: { privilegio_id: row.privilegio_id },
        beforeSend: function () {
            if (typeof showLoading === 'function') {
                showLoading('Eliminando registro...');
            }
        }
    })
    .done(function (response) {
        privilegiosCerrarAlertaEliminar();

        if (response && response.status === 'success') {
            privilegiosNotificar(
                'success',
                response.title || 'Éxito',
                response.message || 'Privilegio eliminado correctamente.'
            );
            listar_privilegio();
            return;
        }

        privilegiosNotificar(
            'error',
            response && response.title ? response.title : 'Error',
            response && response.message ? response.message : 'No se pudo eliminar el privilegio.'
        );
    })
    .fail(function (xhr) {
        privilegiosCerrarAlertaEliminar();
        privilegiosNotificar('error', 'Error', 'Ocurrió un error al procesar la solicitud.');
        console.error('Error al eliminar privilegio:', xhr.responseText);
    });
}

function privilegiosConfirmarEliminar(row) {
    if (!row) {
        privilegiosNotificar('error', 'Error', 'No fue posible identificar el privilegio seleccionado.');
        return;
    }

    var nombrePrivilegio = privilegiosEscape(row.nombre || 'Sin nombre');
    var mensajeHTML = '¿Desea eliminar permanentemente el privilegio?<br><br>' +
        '<strong>Nombre:</strong> ' + nombrePrivilegio;

    /*
     * IZZY actualmente expone la confirmación como swal(...).
     * Se usa primero esa API porque es la que ya utiliza el sistema.
     */
    if (typeof swal === 'function') {
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
        }).then(function (confirmar) {
            if (confirmar === true) {
                privilegiosEjecutarEliminacion(row);
            }
        });
        return;
    }

    privilegiosNotificar(
        'error',
        'Confirmación no disponible',
        'No se encontró la librería de confirmación cargada en la plantilla.'
    );
}

function modal_privilegios() {
    $('#formPrivilegios').attr({
        'data-form': 'save',
        action: '<?php echo SERVERURL;?>ajax/agregarPrivilegiosAjax.php'
    });

    $('#formPrivilegios')[0].reset();
    $('#formPrivilegios #privilegio_id_').val('');
    $('#reg_privilegios').show();
    $('#edi_privilegios').hide();
    $('#delete_privilegios').hide();
    $('#formPrivilegios #privilegios_nombre').prop('readonly', false);
    $('#formPrivilegios #privilegio_activo').prop('disabled', false).prop('checked', true);
    $('#formPrivilegios #label_privilegio_activo').text('Activo');
    $('#formPrivilegios #estado_privilegios').hide();
    $('#formPrivilegios #proceso_privilegios').val('Registro');

    $('#modal_registrar_privilegios').modal({
        show: true,
        keyboard: true,
        backdrop: 'static'
    });
}

/* =========================================================
   LISTADOS DE ACCESOS EN MODALES - DIV/CARDS
   ========================================================= */

function privilegiosAccesoInicializarVista(config) {
    var guardada = 'detalle';

    try {
        guardada = localStorage.getItem(config.storageView) || 'detalle';
    } catch (error) {}

    config.vistaPreferida = guardada === 'miniatura' ? 'miniatura' : 'detalle';
    config.vista = privilegiosEsMovil() ? 'miniatura' : config.vistaPreferida;
    privilegiosAccesoSincronizarVista(config);
    privilegiosAccesoSincronizarPageSize(config);
}

function privilegiosAccesoSincronizarVista(config) {
    var movil = privilegiosEsMovil();
    var selector = config.modal + ' .priv-access-view-btn';

    if (movil) {
        config.vista = 'miniatura';
    }

    $(selector + '[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $(selector).removeClass('active').attr('aria-pressed', 'false');
    $(selector + '[data-view="' + config.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function privilegiosAccesoSincronizarPageSize(config) {
    var mini = config.vista === 'miniatura';
    var opciones = mini ? [6, 12, 18, 30] : [10, 20, 50, 100];
    var seleccionado = mini ? config.porPaginaMiniatura : config.porPaginaDetalle;

    if (opciones.indexOf(seleccionado) === -1) {
        seleccionado = opciones[0];
    }

    var $select = $('#' + config.prefijo + 'PageSize').empty();

    opciones.forEach(function (valor) {
        $select.append($('<option></option>').val(valor).text(valor));
    });

    config.porPagina = seleccionado;
    $select.val(String(seleccionado));
}

function listarAccesosPrivilegio(tipo) {
    var config = privilegiosAccess[tipo];

    if (!config) {
        return;
    }

    config.cargando = true;
    privilegiosAccesoRender(config);

    $.ajax({
        type: 'POST',
        url: config.endpointLista,
        dataType: 'json',
        cache: false,
        data: { privilegio_id_accesos: config.privilegioId }
    })
    .done(function (response) {
        config.registros = privilegiosNormalizarRespuesta(response).map(function (row) {
            row._asignado = privilegiosAsignado(row.asignado);
            return row;
        });
        config.cargando = false;
        config.pagina = 1;
        privilegiosAccesoAplicarFiltro(config);
    })
    .fail(function (xhr) {
        config.registros = [];
        config.filtrados = [];
        config.cargando = false;
        privilegiosAccesoRender(config);
        privilegiosNotificar('error', 'Error', 'No se pudo cargar ' + config.titulo.toLowerCase() + '.');
        console.error('Error al cargar accesos ' + tipo + ':', xhr.responseText);
    });
}

function listar_menuaccesos() {
    listarAccesosPrivilegio('menu');
}

function listar_submenuaccesos() {
    listarAccesosPrivilegio('submenu');
}

function listar_submenu1accesos() {
    listarAccesosPrivilegio('submenu1');
}

function privilegiosAccesoAplicarFiltro(config) {
    var busqueda = String(config.busqueda || '').toLowerCase().trim();

    config.filtrados = config.registros.filter(function (row) {
        if (!busqueda) {
            return true;
        }

        var valores = config.campos.map(function (campo) {
            return row[campo.key];
        });

        valores.push(row._asignado ? 'asignado' : 'no asignado');
        return valores.join(' ').toLowerCase().indexOf(busqueda) !== -1;
    });

    privilegiosAccesoRender(config);
}

function privilegiosAccesoEstadoHtml(row) {
    return row._asignado
        ? '<span class="priv-access-assigned-badge"><i class="fas fa-check-circle"></i>Asignado</span>'
        : '<span class="priv-access-unassigned-badge"><i class="fas fa-minus-circle"></i>No asignado</span>';
}

function privilegiosAccesoBotonHtml(config, row) {
    var id = row[config.parametroId];

    return '<button type="button" class="btn ' + (row._asignado ? 'btn-danger' : 'btn-success') + ' priv-access-action-btn js-priv-access-toggle" ' +
        'data-type="' + config.tipo + '" data-item-id="' + privilegiosEscape(id) + '" data-assigned="' + (row._asignado ? '1' : '0') + '">' +
        (row._asignado
            ? '<i class="fas fa-times"></i><span>Quitar</span>'
            : '<i class="fas fa-plus"></i><span>Asignar</span>') +
    '</button>';
}

function privilegiosAccesoColumnClass(config) {
    return config.tipo === 'menu' ? 'cols-menu' : (config.tipo === 'submenu' ? 'cols-submenu' : 'cols-submenu1');
}

function privilegiosAccesoRenderDetalle(config, rows, inicio) {
    var clase = privilegiosAccesoColumnClass(config);
    var headers = ['#'].concat(config.campos.map(function (campo) { return campo.label; })).concat(['Estado', 'Acción']);
    var html = '<div class="priv-access-detail-header ' + clase + '">';

    headers.forEach(function (header) {
        html += '<div>' + privilegiosEscape(header) + '</div>';
    });

    html += '</div>';

    rows.forEach(function (row, index) {
        html += '<article class="priv-access-detail-row ' + clase + '">';
        html += '<div class="priv-access-cell center"><span class="priv-access-mobile-label">#</span>' + (inicio + index + 1) + '</div>';

        config.campos.forEach(function (campo) {
            html += '<div class="priv-access-cell"><span class="priv-access-mobile-label">' + privilegiosEscape(campo.label) + '</span>' +
                privilegiosEscape(privilegiosTexto(row[campo.key], 'No registrado')) + '</div>';
        });

        html += '<div class="priv-access-cell center"><span class="priv-access-mobile-label">Estado</span>' + privilegiosAccesoEstadoHtml(row) + '</div>';
        html += '<div class="priv-access-cell center"><span class="priv-access-mobile-label">Acción</span>' + privilegiosAccesoBotonHtml(config, row) + '</div>';
        html += '</article>';
    });

    return html;
}

function privilegiosAccesoRenderMiniatura(config, rows) {
    var html = '';

    rows.forEach(function (row) {
        var titulo = privilegiosTexto(row[config.campos[config.campos.length - 1].key], 'Sin nombre');

        html += '<article class="priv-access-mini-card">' +
            '<div class="priv-access-mini-header">' +
                '<h5>' + privilegiosEscape(titulo) + '</h5>' +
                privilegiosAccesoEstadoHtml(row) +
            '</div>' +
            '<div class="priv-access-mini-body">';

        config.campos.forEach(function (campo) {
            html += '<div class="priv-access-mini-field"><span>' + privilegiosEscape(campo.label) + '</span><strong>' +
                privilegiosEscape(privilegiosTexto(row[campo.key], 'No registrado')) + '</strong></div>';
        });

        html += '</div><div class="priv-access-mini-footer">' + privilegiosAccesoBotonHtml(config, row) + '</div></article>';
    });

    return html;
}

function privilegiosAccesoRenderPaginacion(config, totalPaginas) {
    var actual = config.pagina;
    var html = '';

    function boton(texto, pagina, disabled, active, icono, derecha) {
        html += '<button type="button" class="priv-access-page-btn' + (active ? ' active' : '') + '" data-type="' + config.tipo + '" data-page="' + pagina + '"' + (disabled ? ' disabled' : '') + '>';
        if (icono && !derecha) html += '<i class="' + icono + '"></i>';
        html += '<span>' + texto + '</span>';
        if (icono && derecha) html += '<i class="' + icono + '"></i>';
        html += '</button>';
    }

    boton('Inicio', 1, actual === 1, false, 'fas fa-angle-double-left', false);
    boton('Anterior', actual - 1, actual === 1, false, 'fas fa-angle-left', false);

    var desde = Math.max(1, actual - 2);
    var hasta = Math.min(totalPaginas, desde + 4);
    desde = Math.max(1, hasta - 4);

    for (var p = desde; p <= hasta; p++) {
        boton(String(p), p, false, p === actual, '', false);
    }

    boton('Siguiente', actual + 1, actual === totalPaginas, false, 'fas fa-angle-right', true);
    boton('Final', totalPaginas, actual === totalPaginas, false, 'fas fa-angle-double-right', true);

    $('#' + config.prefijo + 'Pagination').html(html);
}

function privilegiosAccesoRender(config) {
    var $listado = $('#' + config.prefijo + 'Listado');
    var total = config.filtrados.length;
    var asignados = config.filtrados.filter(function (row) { return row._asignado; }).length;

    $(config.modal + ' .priv-access-context-count').html('<i class="fas fa-check-circle"></i>' + asignados + ' asignados');

    if (config.cargando) {
        $listado.removeClass('vista-detalle vista-miniatura').html(
            '<div class="priv-access-empty"><i class="fas fa-spinner fa-spin"></i><span>Cargando ' + privilegiosEscape(config.titulo.toLowerCase()) + '...</span></div>'
        );
        $('#' + config.prefijo + 'Info').text('0 registros');
        $('#' + config.prefijo + 'Pagination').empty();
        return;
    }

    var totalPaginas = Math.max(1, Math.ceil(total / config.porPagina));

    if (config.pagina > totalPaginas) {
        config.pagina = totalPaginas;
    }

    var inicio = (config.pagina - 1) * config.porPagina;
    var fin = Math.min(inicio + config.porPagina, total);
    var rows = config.filtrados.slice(inicio, fin);

    $listado
        .toggleClass('vista-detalle', config.vista === 'detalle')
        .toggleClass('vista-miniatura', config.vista === 'miniatura')
        .html(
            total
                ? (config.vista === 'detalle'
                    ? privilegiosAccesoRenderDetalle(config, rows, inicio)
                    : privilegiosAccesoRenderMiniatura(config, rows))
                : '<div class="priv-access-empty"><i class="fas fa-info-circle"></i><span>No hay registros disponibles.</span></div>'
        );

    $('#' + config.prefijo + 'Info').text(
        total > 0
            ? 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
            : 'Mostrando 0 registros'
    );

    if (total > 0) {
        privilegiosAccesoRenderPaginacion(config, totalPaginas);
    } else {
        $('#' + config.prefijo + 'Pagination').empty();
    }

    privilegiosAplicarPermisos();
}

function privilegiosAccesoToggle(tipo, itemId, asignadoActual) {
    var config = privilegiosAccess[tipo];

    if (!config || !itemId || !config.privilegioId) {
        privilegiosNotificar('error', 'Datos incompletos', 'No se pudo determinar el registro o el privilegio.');
        return;
    }

    var nuevoEstado = asignadoActual ? 0 : 1;
    var data = {
        privilegio_id: config.privilegioId,
        estado: nuevoEstado
    };

    data[config.parametroId] = itemId;

    $.ajax({
        type: 'POST',
        url: config.endpointToggle,
        dataType: 'json',
        data: data
    })
    .done(function (response) {
        privilegiosNotificar(
            response && response.type ? response.type : 'success',
            response && response.title ? response.title : 'Actualizado',
            response && response.message ? response.message : 'La asignación fue actualizada correctamente.'
        );

        listarAccesosPrivilegio(tipo);
        listar_privilegio();
    })
    .fail(function (xhr) {
        privilegiosNotificar('error', 'Error', 'No se pudo procesar la asignación.');
        console.error('Error al actualizar acceso:', xhr.responseText);
    });
}

/* =========================================================
   EXCEL XLSX REAL - MISMA ESTRUCTURA ESTABLE DEL SISTEMA
   ========================================================= */

function privilegiosXmlEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function privilegiosExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function privilegiosExcelCell(ref, value, styleId) {
    var texto = privilegiosXmlEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr"><is><t' + preserve + '>' + texto + '</t></is></c>';
}

function privilegiosDescargarBlob(blob, nombreArchivo) {
    var enlace = document.createElement('a');
    var url = URL.createObjectURL(blob);

    enlace.href = url;
    enlace.download = nombreArchivo;
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);

    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
}

function privilegiosFechaArchivo() {
    var fecha = new Date();
    return fecha.getFullYear() + '-' +
        String(fecha.getMonth() + 1).padStart(2, '0') + '-' +
        String(fecha.getDate()).padStart(2, '0');
}

function privilegiosGenerarXlsx(opciones) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var headers = opciones.headers || [];
    var rows = opciones.rows || [];
    var lastCol = privilegiosExcelColName(Math.max(0, headers.length - 1));
    var headerRow = 5;
    var firstDataRow = 6;
    var lastDataRow = Math.max(headerRow, headerRow + rows.length);
    var totalRow = lastDataRow + 1;
    var sheetRows = [];

    sheetRows.push('<row r="1" ht="30" customHeight="1">' + privilegiosExcelCell('A1', opciones.title, 1) + '</row>');
    sheetRows.push('<row r="2" ht="20" customHeight="1">' + privilegiosExcelCell('A2', opciones.subtitle, 2) + '</row>');
    sheetRows.push('<row r="3" ht="20" customHeight="1">' + privilegiosExcelCell('A3', opciones.context || '', 2) + '</row>');
    sheetRows.push('<row r="4"></row>');

    var headerCells = headers.map(function (header, index) {
        return privilegiosExcelCell(privilegiosExcelColName(index) + headerRow, header, 3);
    }).join('');

    sheetRows.push('<row r="' + headerRow + '" ht="26" customHeight="1">' + headerCells + '</row>');

    rows.forEach(function (row, rowIndex) {
        var excelRow = firstDataRow + rowIndex;
        var style = rowIndex % 2 === 0 ? 4 : 5;
        var cells = row.map(function (value, colIndex) {
            return privilegiosExcelCell(privilegiosExcelColName(colIndex) + excelRow, value, style);
        }).join('');

        sheetRows.push('<row r="' + excelRow + '" ht="22" customHeight="1">' + cells + '</row>');
    });

    sheetRows.push(
        '<row r="' + totalRow + '" ht="22" customHeight="1">' +
            privilegiosExcelCell('A' + totalRow, 'TOTAL DE REGISTROS', 6) +
            privilegiosExcelCell('B' + totalRow, String(rows.length), 6) +
        '</row>'
    );

    var colsXml = '';
    headers.forEach(function (_, index) {
        var width = opciones.widths && opciones.widths[index] ? opciones.widths[index] : 22;
        colsXml += '<col min="' + (index + 1) + '" max="' + (index + 1) + '" width="' + width + '" customWidth="1"/>';
    });

    var merges = [
        '<mergeCell ref="A1:' + lastCol + '1"/>',
        '<mergeCell ref="A2:' + lastCol + '2"/>',
        '<mergeCell ref="A3:' + lastCol + '3"/>'
    ];

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + totalRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' +
                '<selection pane="bottomLeft" activeCell="A6" sqref="A6"/>' +
            '</sheetView></sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' + colsXml + '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + lastDataRow + '"/>' +
            '<mergeCells count="3">' + merges.join('') + '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="6">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="5">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="7">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="' + privilegiosXmlEscape(opciones.sheetName || 'Reporte') + '" sheetId="1" r:id="rId1"/></sheets>' +
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
            return Promise.reject(errorGenerate);
        }
    }

    return Promise.reject(new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().'));
}

function privilegiosExportarCsvFallback(headers, rows, fileName) {
    var contenido = [headers].concat(rows).map(function (row) {
        return row.map(function (value) {
            return '"' + String(value == null ? '' : value).replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\r\n');

    privilegiosDescargarBlob(
        new Blob(['\ufeff' + contenido], { type: 'text/csv;charset=utf-8;' }),
        fileName.replace(/\.xlsx$/i, '.csv')
    );
}

function privilegiosEjecutarExcel(opciones) {
    if (!opciones.rows.length) {
        privilegiosNotificar('warning', 'Sin datos', 'No hay registros para exportar.');
        return;
    }

    var promesa = privilegiosGenerarXlsx(opciones);

    if (!promesa) {
        privilegiosExportarCsvFallback(opciones.headers, opciones.rows, opciones.fileName);
        return;
    }

    promesa
        .then(function (blob) {
            privilegiosDescargarBlob(blob, opciones.fileName);
        })
        .catch(function (error) {
            console.error('Error al generar Excel:', error);
            privilegiosNotificar('error', 'Error al generar Excel', 'No se pudo generar el archivo Excel.');
        });
}

function privilegiosExportarExcelPrincipal() {
    var rows = privilegiosState.filtrados.map(function (row) {
        return [
            privilegiosTexto(row.nombre, ''),
            privilegiosEstadoActivo(row.estado) ? 'Activo' : 'Inactivo',
            String(parseInt(row.menus_asignados, 10) || 0),
            String(parseInt(row.submenus_asignados, 10) || 0),
            String(parseInt(row.submenus1_asignados, 10) || 0)
        ];
    });

    privilegiosEjecutarExcel({
        title: 'IZZY • REPORTE DE PRIVILEGIOS',
        subtitle: 'Administración de privilegios y accesos • Generado: ' + new Date().toLocaleDateString('es-HN'),
        context: 'Estado: ' + ($('#estado_privilegios option:selected').text() || 'Todos') + ' • Búsqueda: ' + ($('#buscarPrivilegios').val() || 'Sin búsqueda'),
        headers: ['Privilegio', 'Estado', 'Menús', 'Submenús', 'Submenús 1'],
        rows: rows,
        widths: [28, 15, 16, 16, 18],
        sheetName: 'Privilegios',
        fileName: 'Reporte_Privilegios_' + privilegiosFechaArchivo() + '.xlsx'
    });
}

function privilegiosAccessDatosExport(config) {
    var headers = config.campos.map(function (campo) { return campo.label; }).concat(['Estado']);
    var rows = config.filtrados.map(function (row) {
        var salida = config.campos.map(function (campo) {
            return privilegiosTexto(row[campo.key], '');
        });
        salida.push(row._asignado ? 'Asignado' : 'No asignado');
        return salida;
    });

    return { headers: headers, rows: rows };
}

function privilegiosExportarExcelAcceso(tipo) {
    var config = privilegiosAccess[tipo];
    var data = privilegiosAccessDatosExport(config);

    privilegiosEjecutarExcel({
        title: 'IZZY • ' + config.exportTitle,
        subtitle: config.subtitulo + ' • Generado: ' + new Date().toLocaleDateString('es-HN'),
        context: 'Privilegio: ' + config.privilegioNombre + ' • Búsqueda: ' + ($('#' + config.prefijo + 'Search').val() || 'Sin búsqueda'),
        headers: data.headers,
        rows: data.rows,
        widths: config.tipo === 'menu' ? [34, 18] : [28, 34, 18],
        sheetName: config.tipo === 'menu' ? 'Menus' : (config.tipo === 'submenu' ? 'Submenus' : 'Submenus Nivel 2'),
        fileName: 'Reporte_' + config.tipo + '_' + privilegiosFechaArchivo() + '.xlsx'
    });
}

/* =========================================================
   PDF EJECUTIVO + PREVIEW
   ========================================================= */

function privilegiosObtenerLogoPdf(callback) {
    if (typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
        callback(imagen);
        return;
    }

    $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL;?>core/get_image.php',
        dataType: 'text',
        timeout: 15000
    })
    .done(function (url) {
        url = $.trim(url || '');

        if (!url) {
            callback(null);
            return;
        }

        var img = new Image();
        img.crossOrigin = 'Anonymous';

        img.onload = function () {
            try {
                var canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                canvas.getContext('2d').drawImage(img, 0, 0);
                callback(canvas.toDataURL('image/png'));
            } catch (error) {
                callback(null);
            }
        };

        img.onerror = function () { callback(null); };
        img.src = url;
    })
    .fail(function () { callback(null); });
}

function privilegiosPdfLogoPlate(logoDataUrl) {
    if (!logoDataUrl) {
        return {
            text: 'IZZY',
            fontSize: 16,
            bold: true,
            color: '#FFFFFF',
            alignment: 'center',
            margin: [0, 4, 0, 0]
        };
    }

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
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return .5; },
            vLineWidth: function () { return .5; },
            paddingLeft: function () { return 0; },
            paddingRight: function () { return 0; },
            paddingTop: function () { return 0; },
            paddingBottom: function () { return 0; }
        }
    };
}

function privilegiosAbrirPdf(opciones) {
    if (!opciones.rows.length) {
        privilegiosNotificar('warning', 'Sin datos', 'No hay registros para mostrar en PDF.');
        return;
    }

    if (typeof pdfMake === 'undefined') {
        privilegiosNotificar('error', 'PDF no disponible', 'No se encontró pdfMake.');
        return;
    }

    if (typeof abrirModalPdfPublico !== 'function') {
        privilegiosNotificar('error', 'Visor PDF no disponible', 'No se encontró el modal PDF público.');
        return;
    }

    privilegiosObtenerLogoPdf(function (logoDataUrl) {
        var body = [opciones.headers.map(function (header) {
            return { text: header.toUpperCase(), style: 'th', fillColor: '#17324D' };
        })];

        opciones.rows.forEach(function (row, index) {
            var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
            body.push(row.map(function (value) {
                return { text: String(value == null || value === '' ? '—' : value), fillColor: fill };
            }));
        });

        var metricas = opciones.metrics || [];
        var resumen = null;

        if (metricas.length) {
            var celdasMetricas = metricas.map(function (metrica) {
                return {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        { text: metrica.label.toUpperCase(), fontSize: 6.3, bold: true, color: '#6B778C' },
                        { text: String(metrica.value), fontSize: 13, bold: true, color: '#172B4D', margin: [0, 2, 0, 0] }
                    ]
                };
            });

            resumen = {
                table: {
                    widths: metricas.map(function () { return '*'; }),
                    body: [celdasMetricas]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .6; },
                    vLineWidth: function () { return .6; }
                },
                margin: [0, 0, 0, 12]
            };
        }

        var contenido = [
            {
                table: {
                    widths: [100, '*', 150],
                    body: [[
                        { border: [false, false, false, false], fillColor: '#17324D', margin: [12, 10, 0, 10], stack: [privilegiosPdfLogoPlate(logoDataUrl)] },
                        { border: [false, false, false, false], fillColor: '#17324D', margin: [0, 10, 0, 10], stack: [
                            { text: opciones.title, fontSize: 16, bold: true, color: '#FFFFFF' },
                            { text: opciones.subtitle, fontSize: 7.5, color: '#D8E5F0', margin: [0, 2, 0, 0] }
                        ] },
                        { border: [false, false, false, false], fillColor: '#17324D', margin: [0, 10, 12, 10], stack: [
                            { text: 'REPORTE EJECUTIVO', fontSize: 6.5, bold: true, color: '#72E2E5', alignment: 'right' },
                            { text: new Date().toLocaleDateString('es-HN'), fontSize: 9, bold: true, color: '#FFFFFF', alignment: 'right', margin: [0, 3, 0, 0] },
                            { text: opciones.rows.length + ' registro(s) filtrado(s)', fontSize: 6.5, color: '#D8E5F0', alignment: 'right', margin: [0, 2, 0, 0] }
                        ] }
                    ]]
                },
                layout: { hLineWidth: function () { return 0; }, vLineWidth: function () { return 0; } },
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ['*'],
                    body: [[{
                        text: opciones.context,
                        fontSize: 6.8,
                        color: '#52627A',
                        margin: [10, 7, 10, 7],
                        fillColor: '#F7F9FC'
                    }]]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .6; },
                    vLineWidth: function () { return .6; }
                },
                margin: [0, 0, 0, 10]
            }
        ];

        if (resumen) {
            contenido.push(resumen);
        }

        contenido.push({
            text: opciones.sectionLabel || 'VISTA DETALLE',
            fontSize: 7,
            bold: true,
            color: '#17324D',
            margin: [0, 1, 0, 7]
        });

        contenido.push({
            table: {
                headerRows: 1,
                widths: opciones.widths,
                body: body
            },
            layout: {
                hLineColor: function () { return '#DDE3EA'; },
                vLineColor: function () { return '#DDE3EA'; },
                hLineWidth: function () { return .55; },
                vLineWidth: function () { return .55; },
                paddingLeft: function () { return 5; },
                paddingRight: function () { return 5; },
                paddingTop: function () { return 6; },
                paddingBottom: function () { return 6; }
            }
        });

        var doc = {
            pageSize: 'LETTER',
            pageOrientation: 'landscape',
            pageMargins: [28, 28, 28, 34],
            header: function () {
                return {
                    margin: [28, 12, 28, 0],
                    canvas: [{ type: 'line', x1: 0, y1: 0, x2: 736, y2: 0, lineWidth: 2, lineColor: '#0EA5A8' }]
                };
            },
            footer: function (currentPage, pageCount) {
                return {
                    margin: [28, 8, 28, 0],
                    columns: [
                        { text: opciones.footer, fontSize: 7, color: '#7A869A' },
                        { text: 'Página ' + currentPage + ' de ' + pageCount, fontSize: 7, color: '#7A869A', alignment: 'right' }
                    ]
                };
            },
            content: contenido,
            styles: {
                th: { fontSize: 6.4, bold: true, color: '#FFFFFF', alignment: 'center' }
            },
            defaultStyle: { fontSize: 8, color: '#253858' }
        };

        var pdf = pdfMake.createPdf(doc);

        if (typeof pdf.getDataUrl === 'function') {
            pdf.getDataUrl(function (url) {
                abrirModalPdfPublico(url, opciones.modalTitle, opciones.fileName);
            });
            return;
        }

        if (typeof pdf.getBase64 === 'function') {
            pdf.getBase64(function (base64) {
                abrirModalPdfPublico('data:application/pdf;base64,' + base64, opciones.modalTitle, opciones.fileName);
            });
            return;
        }

        privilegiosNotificar('error', 'PDF no disponible', 'La versión actual de pdfMake no permite previsualización compatible.');
    });
}

function privilegiosExportarPdfPrincipal() {
    var rows = privilegiosState.filtrados.map(function (row) {
        return [
            privilegiosTexto(row.nombre, ''),
            privilegiosEstadoActivo(row.estado) ? 'Activo' : 'Inactivo',
            String(parseInt(row.menus_asignados, 10) || 0),
            String(parseInt(row.submenus_asignados, 10) || 0),
            String(parseInt(row.submenus1_asignados, 10) || 0)
        ];
    });

    var activos = rows.filter(function (row) { return row[1] === 'Activo'; }).length;
    var menus = rows.reduce(function (total, row) { return total + (parseInt(row[2], 10) || 0); }, 0);
    var submenus = rows.reduce(function (total, row) { return total + (parseInt(row[3], 10) || 0) + (parseInt(row[4], 10) || 0); }, 0);

    privilegiosAbrirPdf({
        title: 'REPORTE DE PRIVILEGIOS',
        subtitle: 'Administración de privilegios y asignaciones de acceso',
        context: 'Filtros aplicados: Estado: ' + ($('#estado_privilegios option:selected').text() || 'Todos') + '   |   Búsqueda: ' + ($('#buscarPrivilegios').val() || 'Sin búsqueda'),
        headers: ['Privilegio', 'Estado', 'Menús', 'Submenús', 'Submenús 1'],
        rows: rows,
        widths: ['*', 70, 78, 82, 85],
        metrics: [
            { label: 'Registros', value: rows.length },
            { label: 'Activos', value: activos },
            { label: 'Menús', value: menus },
            { label: 'Submenús', value: submenus }
        ],
        sectionLabel: privilegiosState.vista === 'miniatura' ? 'VISTA MINIATURA' : 'VISTA DETALLE',
        footer: 'IZZY • Gestión de Privilegios',
        modalTitle: 'Reporte de Privilegios',
        fileName: 'Reporte_Privilegios_' + privilegiosFechaArchivo() + '.pdf'
    });
}

function privilegiosExportarPdfAcceso(tipo) {
    var config = privilegiosAccess[tipo];
    var data = privilegiosAccessDatosExport(config);
    var asignados = config.filtrados.filter(function (row) { return row._asignado; }).length;

    privilegiosAbrirPdf({
        title: config.exportTitle,
        subtitle: config.subtitulo,
        context: 'Privilegio: ' + config.privilegioNombre + '   |   Búsqueda: ' + ($('#' + config.prefijo + 'Search').val() || 'Sin búsqueda'),
        headers: data.headers,
        rows: data.rows,
        widths: config.tipo === 'menu' ? ['*', 100] : ['*', '*', 100],
        metrics: [
            { label: 'Registros', value: data.rows.length },
            { label: 'Asignados', value: asignados },
            { label: 'No asignados', value: data.rows.length - asignados }
        ],
        sectionLabel: 'PRIVILEGIO: ' + config.privilegioNombre,
        footer: 'IZZY • ' + config.titulo + ' • ' + config.privilegioNombre,
        modalTitle: 'Reporte ' + config.titulo + ' - ' + config.privilegioNombre,
        fileName: 'Reporte_' + config.tipo + '_' + privilegiosFechaArchivo() + '.pdf'
    });
}

/* =========================================================
   EVENTOS
   ========================================================= */

$(document).ready(function () {
    privilegiosInicializarVista();

    Object.keys(privilegiosAccess).forEach(function (tipo) {
        privilegiosAccesoInicializarVista(privilegiosAccess[tipo]);
    });

    privilegiosConfigurarPanel('#btnToggleFiltrosPrivilegios', '#privilegiosFiltrosContenido', 'izzy.privilegios.filtros.visible');
    privilegiosConfigurarPanel('#btnToggleResumenPrivilegios', '#privilegiosResumenContenido', 'izzy.privilegios.resumen.visible');

    listar_privilegio();

    $('#form_main_privilegios').off('submit.privilegios').on('submit.privilegios', function (e) {
        e.preventDefault();
        privilegiosState.pagina = 1;
        listar_privilegio();
    });

    $('#form_main_privilegios').off('reset.privilegios').on('reset.privilegios', function () {
        var $form = $(this);
        setTimeout(function () {
            $form.find('select').val('').trigger('change');
            $('#buscarPrivilegios').val('');
            privilegiosState.busqueda = '';
            privilegiosState.pagina = 1;
            listar_privilegio();
        }, 0);
    });

    $('#buscarPrivilegios').off('input.privilegios').on('input.privilegios', function () {
        privilegiosState.busqueda = $(this).val() || '';
        privilegiosState.pagina = 1;
        privilegiosAplicarFiltro();
    });

    $('#limpiarBuscarPrivilegios').off('click.privilegios').on('click.privilegios', function () {
        $('#buscarPrivilegios').val('').focus();
        privilegiosState.busqueda = '';
        privilegiosState.pagina = 1;
        privilegiosAplicarFiltro();
    });

    $('#privilegiosPageSize').off('change.privilegios').on('change.privilegios', function () {
        var valor = parseInt($(this).val(), 10) || 10;
        privilegiosState.porPagina = valor;

        if (privilegiosState.vista === 'miniatura') {
            privilegiosState.porPaginaMiniatura = valor;
        } else {
            privilegiosState.porPaginaDetalle = valor;
        }

        privilegiosState.pagina = 1;
        privilegiosRender();
    });

    $('.privilegios-view-btn').off('click.privilegios').on('click.privilegios', function () {
        privilegiosCambiarVista($(this).data('view'));
    });

    $('#btnActualizarPrivilegios').off('click.privilegios').on('click.privilegios', listar_privilegio);
    $('#btnNuevoPrivilegio').off('click.privilegios').on('click.privilegios', modal_privilegios);
    $('#btnExcelPrivilegios').off('click.privilegios').on('click.privilegios', privilegiosExportarExcelPrincipal);
    $('#btnPdfPrivilegios').off('click.privilegios').on('click.privilegios', privilegiosExportarPdfPrincipal);

    $('#privilegiosPaginacion').off('click.privilegios', '.privilegios-page-btn').on('click.privilegios', '.privilegios-page-btn', function () {
        if ($(this).prop('disabled')) return;
        var pagina = parseInt($(this).data('page'), 10);
        if (isNaN(pagina)) return;
        privilegiosState.pagina = pagina;
        privilegiosRender();
    });

    $('#privilegiosListado').off('click.privilegios', '.js-privilegio-accion').on('click.privilegios', '.js-privilegio-accion', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var row = privilegiosBuscarPorId($(this).data('id'));
        var action = $(this).data('action');

        if (!row) {
            privilegiosNotificar('error', 'Error', 'No fue posible identificar el privilegio seleccionado.');
            return;
        }

        if (action === 'menu' || action === 'submenu' || action === 'submenu1') {
            privilegiosAbrirAccesos(action, row);
        } else if (action === 'editar') {
            privilegiosEditar(row);
        } else if (action === 'eliminar') {
            privilegiosConfirmarEliminar(row);
        }
    });

    $('#privilegiosListado').off('click.privilegiosDrop', '.js-acciones-toggle').on('click.privilegiosDrop', '.js-acciones-toggle', function () {
        $('.privilegios-detail-row, .privilegios-mini-card').removeClass('is-dropdown-open');
        $(this).closest('.privilegios-detail-row, .privilegios-mini-card').addClass('is-dropdown-open');
    });

    $(document).off('click.privilegiosDropClose').on('click.privilegiosDropClose', function (e) {
        if (!$(e.target).closest('.acciones-dropdown').length) {
            $('.privilegios-detail-row, .privilegios-mini-card').removeClass('is-dropdown-open');
        }
    });

    $(document).off('click.privAccessToggle', '.js-priv-access-toggle').on('click.privAccessToggle', '.js-priv-access-toggle', function (e) {
        e.preventDefault();
        var $btn = $(this);
        privilegiosAccesoToggle(
            String($btn.data('type')),
            $btn.data('item-id'),
            String($btn.data('assigned')) === '1'
        );
    });

    Object.keys(privilegiosAccess).forEach(function (tipo) {
        var config = privilegiosAccess[tipo];
        var prefijo = config.prefijo;

        $('#' + prefijo + 'Refresh').off('click.' + prefijo).on('click.' + prefijo, function () {
            listarAccesosPrivilegio(tipo);
        });

        $('#' + prefijo + 'Excel').off('click.' + prefijo).on('click.' + prefijo, function () {
            privilegiosExportarExcelAcceso(tipo);
        });

        $('#' + prefijo + 'Pdf').off('click.' + prefijo).on('click.' + prefijo, function () {
            privilegiosExportarPdfAcceso(tipo);
        });

        $('#' + prefijo + 'Search').off('input.' + prefijo).on('input.' + prefijo, function () {
            config.busqueda = $(this).val() || '';
            config.pagina = 1;
            privilegiosAccesoAplicarFiltro(config);
        });

        $('#' + prefijo + 'Clear').off('click.' + prefijo).on('click.' + prefijo, function () {
            $('#' + prefijo + 'Search').val('').focus();
            config.busqueda = '';
            config.pagina = 1;
            privilegiosAccesoAplicarFiltro(config);
        });

        $('#' + prefijo + 'PageSize').off('change.' + prefijo).on('change.' + prefijo, function () {
            var valor = parseInt($(this).val(), 10) || 10;
            config.porPagina = valor;

            if (config.vista === 'miniatura') {
                config.porPaginaMiniatura = valor;
            } else {
                config.porPaginaDetalle = valor;
            }

            config.pagina = 1;
            privilegiosAccesoRender(config);
        });

        $(config.modal + ' .priv-access-view-btn').off('click.' + prefijo).on('click.' + prefijo, function () {
            var siguiente = $(this).data('view') === 'miniatura' ? 'miniatura' : 'detalle';
            if (privilegiosEsMovil()) siguiente = 'miniatura';
            config.vista = siguiente;

            if (!privilegiosEsMovil()) {
                config.vistaPreferida = siguiente;
                try { localStorage.setItem(config.storageView, siguiente); } catch (error) {}
            }

            config.pagina = 1;
            privilegiosAccesoSincronizarVista(config);
            privilegiosAccesoSincronizarPageSize(config);
            privilegiosAccesoRender(config);
        });

        $('#' + prefijo + 'Pagination').off('click.' + prefijo, '.priv-access-page-btn').on('click.' + prefijo, '.priv-access-page-btn', function () {
            if ($(this).prop('disabled')) return;
            var pagina = parseInt($(this).data('page'), 10);
            if (isNaN(pagina)) return;
            config.pagina = pagina;
            privilegiosAccesoRender(config);
        });
    });

    $('#modal_registrar_privilegios').off('shown.bs.modal.privilegios').on('shown.bs.modal.privilegios', function () {
        $(this).find('#privilegios_nombre').focus();
    });

    $(document).off('change.privilegiosActivo', '#formPrivilegios #privilegio_activo').on('change.privilegiosActivo', '#formPrivilegios #privilegio_activo', function () {
        $('#formPrivilegios #label_privilegio_activo').text($(this).is(':checked') ? 'Activo' : 'Inactivo');
    });

    var resizeTimer = null;
    $(window).off('resize.privilegios orientationchange.privilegios').on('resize.privilegios orientationchange.privilegios', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            var objetivo = privilegiosEsMovil() ? 'miniatura' : privilegiosState.vistaPreferida;

            if (privilegiosState.vista !== objetivo) {
                privilegiosState.vista = objetivo;
                privilegiosState.pagina = 1;
                privilegiosSincronizarPageSize();
            }

            privilegiosSincronizarVista();
            privilegiosRender();

            Object.keys(privilegiosAccess).forEach(function (tipo) {
                var config = privilegiosAccess[tipo];
                var objetivoAcceso = privilegiosEsMovil() ? 'miniatura' : config.vistaPreferida;

                if (config.vista !== objetivoAcceso) {
                    config.vista = objetivoAcceso;
                    config.pagina = 1;
                    privilegiosAccesoSincronizarPageSize(config);
                }

                privilegiosAccesoSincronizarVista(config);
                privilegiosAccesoRender(config);
            });
        }, 120);
    });
});
</script>
