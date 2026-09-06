<script>
var usuariosState = {
    registros: [],
    filtrados: [],
    pagina: 1,
    porPagina: 10,
    porPaginaDetalle: 10,
    porPaginaMiniatura: 6,
    vista: 'detalle',
    busqueda: '',
    empresas: {},
    empresasCargadas: false
};

var USUARIOS_STORAGE_VISTA = 'izzy.users.tipo_vista';

$(document).ready(function () {
    inicializarVistaUsuarios();
    inicializarDropdownAccionesUsuarios();
    listar_usuarios();
    getTipoUsuario();
    getPrivilegio();
    getEmpresaUsers();
    getColaboradoresUsuario();
    getPuestosColaboradoresUsuarios();

    $('#form_main_usuarios').on('submit', function (e) {
        e.preventDefault();
        usuariosState.pagina = 1;
        listar_usuarios();
    });

    $('#form_main_usuarios').on('reset', function () {
        setTimeout(function () {
            $('#form_main_usuarios .selectpicker')
                .val('')
                .selectpicker('refresh');

            usuariosState.pagina = 1;
            listar_usuarios();
        }, 0);
    });

    $('#buscarUsuarios').on('input', function () {
        usuariosState.busqueda = $(this).val();
        usuariosState.pagina = 1;
        aplicarFiltroUsuarios();
    });

    $('#usuariosPageSize').on('change', function () {
        var valor = parseInt($(this).val(), 10);

        usuariosState.porPagina = isNaN(valor) || valor <= 0
            ? (usuariosState.vista === 'miniatura' ? 6 : 10)
            : valor;

        if (usuariosState.vista === 'miniatura') {
            usuariosState.porPaginaMiniatura = usuariosState.porPagina;
        } else {
            usuariosState.porPaginaDetalle = usuariosState.porPagina;
        }

        usuariosState.pagina = 1;
        renderUsuarios();
    });

    $('.usuarios-view-btn')
        .off('click.usuariosVista')
        .on('click.usuariosVista', function () {
            cambiarVistaUsuarios($(this).data('view'));
        });

    $('#btnActualizarUsuarios').on('click', listar_usuarios);
    $('#btnNuevoUsuario').on('click', modal_usuarios);
    $('#btnExcelUsuarios').on('click', exportarUsuariosExcelPremium);
    $('#btnPdfUsuarios').on('click', previsualizarUsuariosPdfPremium);

    configurarToggleUsuarios(
        '#btnToggleFiltrosUsuarios',
        '#usuariosFiltrosContenido',
        'izzy.users.filtros.visible'
    );

    configurarToggleUsuarios(
        '#btnToggleResumenUsuarios',
        '#usuariosResumenContenido',
        'izzy.users.resumen.visible'
    );

    $('#filtroTipoUsuario, #filtroPrivilegioUsuario')
        .off('changed.bs.select.usuarios change.usuarios')
        .on('changed.bs.select.usuarios change.usuarios', function () {
            usuariosState.pagina = 1;
            aplicarFiltroUsuarios();
        });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tab = $(e.target).attr('href');
        $('#es_nuevo_colaborador').val(tab === '#nuevo' ? '1' : '0');
    });

    $('#formUsers #colaboradores_id').on('changed.bs.select', function () {
        var colaboradorId = $(this).val();

        if (colaboradorId) {
            obtenerInfoColaborador(colaboradorId);
        } else {
            $('#info_colaborador').hide();
        }
    });

    $('#formUsers .switch, #formUsers #estado_usuario').on('change', function () {
        $('#label_usuarios_activo').text(
            $('#estado_usuario').is(':checked') ? 'Activo' : 'Inactivo'
        );
    });

    $('#btnNuevoPuesto').on('click', function () {
        modal_puestos();
    });

    $('#usuariosListado').on('click', '.reset_password_usuario', function () {
        var id = $(this).closest('[data-id]').data('id');
        confirmarResetUsuario(id);
    });

    $('#usuariosListado').on('click', '.table_editar', function () {
        var id = $(this).closest('[data-id]').data('id');
        editarUsuarioPorId(id);
    });

    $('#usuariosListado').on('click', '.table_eliminar', function () {
        var id = $(this).closest('[data-id]').data('id');
        eliminarUsuarioPorId(id);
    });
});


function inicializarVistaUsuarios() {
    var vistaGuardada = 'detalle';

    try {
        vistaGuardada = localStorage.getItem(USUARIOS_STORAGE_VISTA) || 'detalle';
    } catch (error) {
        vistaGuardada = 'detalle';
    }

    usuariosState.vista = vistaGuardada === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    actualizarBotonesVistaUsuarios();
    sincronizarPageSizeUsuarios();
}

function cambiarVistaUsuarios(vista) {
    usuariosState.vista = vista === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    try {
        localStorage.setItem(USUARIOS_STORAGE_VISTA, usuariosState.vista);
    } catch (error) {
        console.warn('No se pudo guardar el tipo de vista de usuarios:', error);
    }

    actualizarBotonesVistaUsuarios();
    sincronizarPageSizeUsuarios();
    usuariosState.pagina = 1;
    renderUsuarios();
}

function actualizarBotonesVistaUsuarios() {
    $('.usuarios-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.usuarios-view-btn[data-view="' + usuariosState.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function sincronizarPageSizeUsuarios() {
    var $select = $('#usuariosPageSize');
    var miniatura = usuariosState.vista === 'miniatura';
    var opciones = miniatura
        ? [6, 12, 18, 30]
        : [10, 25, 50, 100];

    var seleccionado = miniatura
        ? usuariosState.porPaginaMiniatura
        : usuariosState.porPaginaDetalle;

    $select.empty();

    opciones.forEach(function (valor) {
        $select.append(
            $('<option></option>')
                .attr('value', valor)
                .text(valor)
        );
    });

    usuariosState.porPagina = opciones.indexOf(seleccionado) !== -1
        ? seleccionado
        : opciones[0];

    $select.val(String(usuariosState.porPagina));
}

function configurarToggleUsuarios(buttonSelector, contentSelector, storageKey) {
    var $button = $(buttonSelector);
    var $content = $(contentSelector);

    if (!$button.length || !$content.length) {
        return;
    }

    function obtenerEstadoGuardado() {
        if (!storageKey) {
            return null;
        }

        try {
            var valor = localStorage.getItem(storageKey);

            if (valor === null) {
                return null;
            }

            return valor === '1';
        } catch (error) {
            console.warn('No se pudo leer el estado de la sección:', storageKey, error);
            return null;
        }
    }

    function guardarEstado(mostrar) {
        if (!storageKey) {
            return;
        }

        try {
            localStorage.setItem(storageKey, mostrar ? '1' : '0');
        } catch (error) {
            console.warn('No se pudo guardar el estado de la sección:', storageKey, error);
        }
    }

    function actualizarEstado(mostrar) {
        var $icon = $button.find('i');
        var $label = $button.find('span');

        $button.attr('aria-expanded', mostrar ? 'true' : 'false');
        $label.text(mostrar ? 'Ocultar' : 'Mostrar');
        $icon
            .toggleClass('fa-chevron-up', mostrar)
            .toggleClass('fa-chevron-down', !mostrar);
    }

    var estadoGuardado = obtenerEstadoGuardado();
    var mostrarInicial = estadoGuardado !== null
        ? estadoGuardado
        : $content.is(':visible');

    $content.toggle(mostrarInicial);
    actualizarEstado(mostrarInicial);

    $button.off('click.usuariosToggle').on('click.usuariosToggle', function () {
        var mostrar = !$content.is(':visible');

        $content.stop(true, true)[mostrar ? 'slideDown' : 'slideUp'](180);
        actualizarEstado(mostrar);
        guardarEstado(mostrar);
    });
}

function actualizarResumenUsuarios() {
    var rows = usuariosState.filtrados || [];
    var activos = 0;
    var inactivos = 0;
    var administradores = 0;

    rows.forEach(function (usuario) {
        var estado = parseInt(usuario.estado, 10);
        var tipo = String(usuario.tipo_usuario || '').toLowerCase();
        var privilegio = String(usuario.privilegio || '').toLowerCase();

        if (estado === 1) {
            activos++;
        } else {
            inactivos++;
        }

        if (
            tipo.indexOf('administrador') !== -1 ||
            privilegio.indexOf('administrador') !== -1
        ) {
            administradores++;
        }
    });

    $('#usuariosKpiRegistros').text(rows.length);
    $('#usuariosKpiActivos').text(activos);
    $('#usuariosKpiInactivos').text(inactivos);
    $('#usuariosKpiAdministradores').text(administradores);
}

function actualizarFiltrosCatalogoUsuarios() {
    var tipos = {};
    var privilegios = {};

    (usuariosState.registros || []).forEach(function (usuario) {
        var tipo = String(usuario.tipo_usuario || '').trim();
        var privilegio = String(usuario.privilegio || '').trim();

        if (tipo) {
            tipos[tipo] = true;
        }

        if (privilegio) {
            privilegios[privilegio] = true;
        }
    });

    function poblar(selector, valores) {
        var $select = $(selector);
        var valorActual = $select.val() || '';

        $select.empty().append('<option value="">Todos</option>');

        Object.keys(valores)
            .sort(function (a, b) {
                return a.localeCompare(b, 'es', { sensitivity: 'base' });
            })
            .forEach(function (valor) {
                $select.append(
                    $('<option></option>')
                        .attr('value', valor)
                        .text(valor)
                );
            });

        if (valorActual && valores[valorActual]) {
            $select.val(valorActual);
        } else {
            $select.val('');
        }

        if ($.fn.selectpicker && $select.hasClass('selectpicker')) {
            $select.selectpicker('refresh');
        }
    }

    poblar('#filtroTipoUsuario', tipos);
    poblar('#filtroPrivilegioUsuario', privilegios);
}

function escaparHtml(valor) {
    return $('<div>')
        .text(valor == null ? '' : String(valor))
        .html();
}

function usuarioPorId(id) {
    return usuariosState.registros.find(function (usuario) {
        return String(usuario.users_id) === String(id);
    });
}

function resolverEmpresaUsuario(usuario) {
    var empresaActual = usuario && usuario.empresa != null
        ? String(usuario.empresa).trim()
        : '';

    var empresaInvalida =
        empresaActual === '' ||
        empresaActual.toLowerCase() === 'no disponible';

    if (!empresaInvalida) {
        return empresaActual;
    }

    var empresaId = usuario && usuario.empresa_id != null
        ? String(usuario.empresa_id)
        : '';

    if (empresaId && usuariosState.empresas[empresaId]) {
        return usuariosState.empresas[empresaId];
    }

    /*
     * Si el contexto actual solo expone una empresa, esa es la empresa
     * disponible para los usuarios de este listado. Esto cubre DB_MAIN
     * cuando getUsuarios() no devuelve empresa_id/alias de empresa.
     */
    var nombresEmpresa = Object.keys(usuariosState.empresas).map(function (id) {
        return usuariosState.empresas[id];
    });

    if (nombresEmpresa.length === 1) {
        return nombresEmpresa[0];
    }

    return 'No disponible';
}

function normalizarEmpresasUsuarios() {
    usuariosState.registros.forEach(function (usuario) {
        usuario.empresa = resolverEmpresaUsuario(usuario);
    });
}

function listar_usuarios() {
    var estado = $('#estado_usuarios').val();

    $('#usuariosListado').html(
        '<div class="text-center py-5">' +
            '<i class="fas fa-spinner fa-spin fa-2x"></i>' +
            '<div class="mt-2">Cargando usuarios...</div>' +
        '</div>'
    );

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/llenarDataTableUsuarios.php',
        data: {
            estado: estado
        },
        dataType: 'json',
        cache: false
    })
    .done(function (response) {
        var registros = [];

        if (Array.isArray(response)) {
            registros = response;
        } else if (response && Array.isArray(response.data)) {
            registros = response.data;
        } else if (response && Array.isArray(response.aaData)) {
            registros = response.aaData;
        } else if (
            response &&
            response.result &&
            Array.isArray(response.result.data)
        ) {
            registros = response.result.data;
        }

        usuariosState.registros = registros;
        normalizarEmpresasUsuarios();
        actualizarFiltrosCatalogoUsuarios();
        usuariosState.pagina = 1;
        aplicarFiltroUsuarios();

        if (response && response.success === false) {
            showNotify(
                'error',
                'Error',
                response.message || 'No se pudo cargar el listado de usuarios'
            );
        }
    })
    .fail(function (xhr) {
        usuariosState.registros = [];
        usuariosState.filtrados = [];
        renderUsuarios();

        var mensaje = 'No se pudo cargar el listado de usuarios';

        if (
            xhr &&
            xhr.responseJSON &&
            xhr.responseJSON.message
        ) {
            mensaje = xhr.responseJSON.message;
        }

        showNotify('error', 'Error', mensaje);
    });
}

function aplicarFiltroUsuarios() {
    var busqueda = (usuariosState.busqueda || '')
        .toLowerCase()
        .trim();
    var tipoFiltro = String($('#filtroTipoUsuario').val() || '').trim();
    var privilegioFiltro = String($('#filtroPrivilegioUsuario').val() || '').trim();

    usuariosState.filtrados = usuariosState.registros.filter(function (usuario) {
        var tipoUsuario = String(usuario.tipo_usuario || '').trim();
        var privilegioUsuario = String(usuario.privilegio || '').trim();

        if (tipoFiltro && tipoUsuario !== tipoFiltro) {
            return false;
        }

        if (privilegioFiltro && privilegioUsuario !== privilegioFiltro) {
            return false;
        }

        if (!busqueda) {
            return true;
        }

        var texto = [
            usuario.colaborador,
            usuario.correo,
            tipoUsuario,
            privilegioUsuario,
            resolverEmpresaUsuario(usuario),
            parseInt(usuario.estado, 10) === 1 ? 'activo' : 'inactivo'
        ].join(' ').toLowerCase();

        return texto.indexOf(busqueda) !== -1;
    });

    actualizarResumenUsuarios();
    renderUsuarios();
}

function renderUsuarios() {
    var total = usuariosState.filtrados.length;
    var porPagina = usuariosState.porPagina;
    var totalPaginas = Math.max(1, Math.ceil(total / porPagina));

    if (usuariosState.pagina > totalPaginas) {
        usuariosState.pagina = totalPaginas;
    }

    var inicio = (usuariosState.pagina - 1) * porPagina;
    var fin = Math.min(inicio + porPagina, total);
    var registrosPagina = usuariosState.filtrados.slice(inicio, fin);
    var html = '';
    var $listado = $('#usuariosListado');

    $listado
        .toggleClass('vista-detalle', usuariosState.vista === 'detalle')
        .toggleClass('vista-miniatura', usuariosState.vista === 'miniatura');

    if (total > 0) {
        if (usuariosState.vista === 'detalle') {
            html += construirHeaderUsuarios();

            registrosPagina.forEach(function (usuario) {
                html += construirFilaUsuario(usuario);
            });
        } else {
            registrosPagina.forEach(function (usuario) {
                html += construirMiniaturaUsuario(usuario);
            });
        }
    }

    $listado.html(html);
    $('#usuariosVacio').toggle(total === 0);

    $('#usuariosInfo').text(
        total > 0
            ? 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
            : 'Mostrando 0 registros'
    );

    renderPaginacionUsuarios(totalPaginas);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function construirHeaderUsuarios() {
    return '' +
        '<div class="usuario-row usuario-row-header">' +
            '<div>Acciones</div>' +
            '<div>Colaborador</div>' +
            '<div>Correo</div>' +
            '<div>Tipo Usuario</div>' +
            '<div>Privilegio</div>' +
            '<div>Empresa</div>' +
            '<div>Estado</div>' +
        '</div>';
}

function construirFilaUsuario(usuario) {
    var activo = String(usuario.estado) === '1';

    return '' +
        '<div class="usuario-row" data-id="' + escaparHtml(usuario.users_id) + '">' +
            construirAccionesUsuario() +
            celdaUsuario('Colaborador', usuario.colaborador) +
            celdaUsuario('Correo', usuario.correo) +
            celdaUsuario('Tipo Usuario', usuario.tipo_usuario) +
            celdaUsuario('Privilegio', usuario.privilegio) +
            celdaUsuario('Empresa', resolverEmpresaUsuario(usuario)) +
            construirEstadoUsuario(activo) +
        '</div>';
}


function construirMiniaturaUsuario(usuario) {
    var activo = String(usuario.estado) === '1';
    var estadoClase = activo ? 'badge-success' : 'badge-danger';
    var estadoIcono = activo ? 'fa-check-circle' : 'fa-times-circle';
    var estadoTexto = activo ? 'Activo' : 'Inactivo';

    var colaborador = usuario.colaborador || 'No disponible';
    var correo = usuario.correo || 'No disponible';
    var tipoUsuario = usuario.tipo_usuario || 'No disponible';
    var privilegio = usuario.privilegio || 'No disponible';
    var empresa = resolverEmpresaUsuario(usuario);

    return '' +
        '<article class="usuario-mini-card" data-id="' + escaparHtml(usuario.users_id) + '">' +
            '<div class="usuario-mini-topline"></div>' +

            '<div class="usuario-mini-header">' +
                '<div class="usuario-mini-avatar">' +
                    '<i class="fas fa-user-cog"></i>' +
                '</div>' +

                '<div class="usuario-mini-identidad">' +
                    '<div class="usuario-mini-title-row">' +
                        '<h4>' + escaparHtml(colaborador) + '</h4>' +
                        '<span class="badge badge-pill ' + estadoClase + '">' +
                            '<i class="fas ' + estadoIcono + ' mr-1"></i>' +
                            estadoTexto +
                        '</span>' +
                    '</div>' +

                    '<div class="usuario-mini-correo">' +
                        '<i class="fas fa-envelope"></i>' +
                        '<span>' + escaparHtml(correo) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="usuario-mini-body">' +
                '<div class="usuario-mini-field">' +
                    '<span><i class="fas fa-user-tag"></i> Tipo Usuario</span>' +
                    '<strong>' + escaparHtml(tipoUsuario) + '</strong>' +
                '</div>' +

                '<div class="usuario-mini-field">' +
                    '<span><i class="fas fa-user-shield"></i> Privilegio</span>' +
                    '<strong>' + escaparHtml(privilegio) + '</strong>' +
                '</div>' +

                '<div class="usuario-mini-field usuario-mini-field-full">' +
                    '<span><i class="fas fa-building"></i> Empresa</span>' +
                    '<strong>' + escaparHtml(empresa) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="usuario-mini-footer">' +
                construirAccionesUsuario() +
            '</div>' +
        '</article>';
}

function construirAccionesUsuario() {
    return '' +
        '<div class="usuario-acciones">' +
            '<span class="usuario-cell-label">Acciones</span>' +
            '<div class="dropdown acciones-dropdown">' +
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle dropdown-toggle" data-toggle="dropdown">' +
                    '<i class="fas fa-cog"></i> ' +
                    '<span>Acciones</span>' +
                '</button>' +
                '<div class="dropdown-menu acciones-menu">' +
                    '<button type="button" class="dropdown-item accion-item table_actualizar reset_password_usuario ocultar">' +
                        '<span class="accion-icon accion-icon-secondary">' +
                            '<i class="fas fa-sync-alt"></i>' +
                        '</span> ' +
                        '<span class="accion-label">Restablecer</span>' +
                    '</button>' +
                    '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                        '<span class="accion-icon accion-icon-editar">' +
                            '<i class="fas fa-edit"></i>' +
                        '</span> ' +
                        '<span class="accion-label">Editar</span>' +
                    '</button>' +
                    '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                        '<span class="accion-icon accion-icon-eliminar">' +
                            '<i class="fas fa-trash-alt"></i>' +
                        '</span> ' +
                        '<span class="accion-label">Eliminar</span>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
}

function celdaUsuario(label, valor) {
    var texto = valor;

    if (texto === null || texto === undefined || String(texto).trim() === '') {
        texto = 'No disponible';
    }

    return '' +
        '<div>' +
            '<span class="usuario-cell-label">' + escaparHtml(label) + '</span>' +
            '<div class="usuario-cell-value">' + escaparHtml(texto) + '</div>' +
        '</div>';
}

function construirEstadoUsuario(activo) {
    var clase = activo ? 'badge-success' : 'badge-danger';
    var icono = activo ? 'fa-check-circle' : 'fa-times-circle';
    var texto = activo ? 'Activo' : 'Inactivo';

    return '' +
        '<div>' +
            '<span class="usuario-cell-label">Estado</span>' +
            '<span class="badge badge-pill ' + clase + '">' +
                '<i class="fas ' + icono + ' mr-1"></i>' +
                texto +
            '</span>' +
        '</div>';
}

function renderPaginacionUsuarios(totalPaginas) {
    var paginaActual = usuariosState.pagina;
    var html = '';

    function agregarItem(texto, destino, deshabilitado, activo) {
        html += '' +
            '<li class="page-item ' +
                (deshabilitado ? 'disabled ' : '') +
                (activo ? 'active' : '') +
            '">' +
                '<a class="page-link" data-page="' + destino + '">' +
                    texto +
                '</a>' +
            '</li>';
    }

    agregarItem(
        '<i class="fas fa-angle-double-left"></i> Inicio',
        1,
        paginaActual === 1,
        false
    );

    agregarItem(
        '<i class="fas fa-angle-left"></i> Anterior',
        paginaActual - 1,
        paginaActual === 1,
        false
    );

    var desde = Math.max(1, paginaActual - 2);
    var hasta = Math.min(totalPaginas, desde + 4);
    desde = Math.max(1, hasta - 4);

    for (var pagina = desde; pagina <= hasta; pagina++) {
        agregarItem(
            pagina,
            pagina,
            false,
            pagina === paginaActual
        );
    }

    agregarItem(
        'Siguiente <i class="fas fa-angle-right"></i>',
        paginaActual + 1,
        paginaActual === totalPaginas,
        false
    );

    agregarItem(
        'Final <i class="fas fa-angle-double-right"></i>',
        totalPaginas,
        paginaActual === totalPaginas,
        false
    );

    $('#usuariosPaginacion')
        .html(html)
        .off('click', 'a[data-page]')
        .on('click', 'a[data-page]', function (e) {
            e.preventDefault();

            var $item = $(this).parent();

            if ($item.hasClass('disabled') || $item.hasClass('active')) {
                return;
            }

            usuariosState.pagina = parseInt($(this).data('page'), 10);
            renderUsuarios();
        });
}

function confirmarResetUsuario(id) {
    var usuario = usuarioPorId(id);

    if (!usuario) {
        return;
    }

    swal({
        title: '¿Está seguro?',
        text: '¿Desea resetear la contraseña al usuario: ' + usuario.colaborador + '?',
        icon: 'warning',
        buttons: {
            cancel: {
                text: 'Cancelar',
                visible: true,
                className: 'btn-light'
            },
            confirm: {
                text: '¡Sí, resetear!',
                value: true,
                className: 'btn-primary',
                closeModal: false
            }
        },
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then(function (confirmado) {
        if (confirmado) {
            resetearContra(
                usuario.users_id,
                usuario.server_customers_id
            );
        }
    });
}

function resetearContra(users_id, server_customers_id) {
    if (typeof showLoading === 'function') {
        showLoading('Reseteando contraseña...');
    }

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>ajax/resetearContrasenaAjax.php',
        data: {
            users_id: users_id,
            server_customers_id: server_customers_id
        },
        dataType: 'json'
    })
    .done(function (response) {
        swal.close();

        showNotify(
            response.status === 'success' ? 'success' : 'error',
            response.title,
            response.message
        );
    })
    .fail(function () {
        swal.close();
        showNotify(
            'error',
            'Error',
            'Error de conexión al resetear contraseña'
        );
    });
}

function editarUsuarioPorId(id) {
    var usuario = usuarioPorId(id);

    if (!usuario) {
        return;
    }

    $('#formUsers #usuarios_id').val(usuario.users_id);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/editarUsuarios.php',
        data: {
            users_id: usuario.users_id
        },
        dataType: 'json'
    })
    .done(function (response) {
        if (!response.success) {
            showNotify(
                'error',
                'Error',
                response.message || 'Error al cargar datos del usuario'
            );
            return;
        }

        $('#formUsers').attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL; ?>ajax/modificarUsersAjax.php'
        });

        $('#reg_usuario').hide();
        $('#edi_usuario').show();
        $('#es_nuevo_colaborador').val('0');
        $('#existente-tab').tab('show');

        $('#formUsers #colaboradores_id')
            .val(response.data.colaboradores_id)
            .selectpicker('refresh');

        $('#info_nombre').text(
            response.data.nombre_completo ||
            response.data.nombre ||
            'No especificado'
        );

        $('#info_identidad').text(
            response.data.identidad || 'No especificado'
        );

        $('#info_telefono').text(
            response.data.telefono || 'No especificado'
        );

        $('#info_fecha_ingreso').text(
            response.data.fecha_ingreso || 'No especificado'
        );

        $('#info_estado').html(
            response.data.estado_colaborador == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>'
        );

        $('#info_colaborador').show();
        $('#correo_usuario').val(response.data.correo);

        $('#empresa_usuario')
            .val(response.data.empresa_id)
            .selectpicker('refresh');

        $('#tipo_user')
            .val(response.data.tipo_user_id)
            .selectpicker('refresh');

        $('#privilegio_id')
            .val(response.data.privilegio_id)
            .selectpicker('refresh');

        $('#server_customers_id').val(response.data.server_customers_id);

        $('#estado_usuario').prop(
            'checked',
            response.data.estado == 1
        );

        $('#label_usuarios_activo').text(
            response.data.estado == 1 ? 'Activo' : 'Inactivo'
        );

        $('#modal_registrar_usuarios').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    })
    .fail(function () {
        showNotify(
            'error',
            'Error',
            'Error de conexión al cargar datos del usuario'
        );
    });
}

function eliminarUsuarioPorId(id) {
    var usuario = usuarioPorId(id);

    if (!usuario) {
        return;
    }

    swal({
        title: 'Confirmar eliminación',
        text: '¿Desea eliminar permanentemente el usuario ' + usuario.colaborador + '?',
        icon: 'warning',
        buttons: {
            cancel: {
                text: 'Cancelar',
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
    }).then(function (confirmado) {
        if (!confirmado) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>ajax/eliminarUsersAjax.php',
            data: {
                users_id: usuario.users_id
            },
            dataType: 'json'
        })
        .done(function (response) {
            swal.close();

            showNotify(
                response.status === 'success' ? 'success' : 'error',
                response.title,
                response.message
            );

            if (response.status === 'success') {
                listar_usuarios();
            }
        })
        .fail(function () {
            swal.close();
            showNotify(
                'error',
                'Error',
                'Ocurrió un error al procesar la solicitud'
            );
        });
    });
}

function modal_usuarios() {
    $('#formUsers')[0].reset();

    $('#empresa_usuario, #privilegio_id, #tipo_user, #puesto_colaborador')
        .val('')
        .selectpicker('refresh');

    $('#colaboradores_id')
        .val('')
        .selectpicker('refresh');

    $('#info_colaborador').hide();
    $('#es_nuevo_colaborador').val('0');
    $('#existente-tab').tab('show');

    $('#formUsers').attr({
        'data-form': 'save',
        'action': '<?php echo SERVERURL; ?>ajax/agregarUsuarioAjax.php'
    });

    $('#reg_usuario').show();
    $('#edi_usuario').hide();

    $('#fecha_ingreso_colaborador').val(
        new Date().toISOString().split('T')[0]
    );

    $('#estado_usuario').prop('checked', true);
    $('#label_usuarios_activo').text('Activo');

    $('#modal_registrar_usuarios').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function obtenerInfoColaborador(colaboradorId) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getColaboradorInfo.php',
        type: 'POST',
        data: {
            colaborador_id: colaboradorId
        },
        dataType: 'json'
    })
    .done(function (response) {
        if (response.success) {
            $('#info_nombre').text(
                response.data.nombre_completo ||
                response.data.nombre ||
                'No especificado'
            );

            $('#info_identidad').text(
                response.data.identidad || 'No especificado'
            );

            $('#info_telefono').text(
                response.data.telefono || 'No especificado'
            );

            $('#info_fecha_ingreso').text(
                response.data.fecha_ingreso || 'No especificado'
            );

            $('#info_estado').html(
                response.data.estado == 1
                    ? '<span class="badge badge-success">Activo</span>'
                    : '<span class="badge badge-danger">Inactivo</span>'
            );

            $('#info_colaborador').show();
        } else {
            $('#info_colaborador').hide();
            showNotify(
                'error',
                'Error',
                response.message || 'Error al obtener información del colaborador'
            );
        }
    })
    .fail(function () {
        $('#info_colaborador').hide();
        showNotify(
            'error',
            'Error',
            'Error de conexión al obtener información del colaborador'
        );
    });
}

function getTipoUsuario() {
    cargarSelectUsuario(
        '<?php echo SERVERURL; ?>core/getTipoUsuario.php',
        '#tipo_user',
        'tipo_user_id',
        'nombre',
        'tipos de usuario'
    );
}

function getPrivilegio() {
    cargarSelectUsuario(
        '<?php echo SERVERURL; ?>core/getPrivilegio.php',
        '#privilegio_id',
        'privilegio_id',
        'nombre',
        'privilegios'
    );
}

function getEmpresaUsers() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getEmpresa.php',
        type: 'POST',
        dataType: 'json'
    })
    .done(function (response) {
        var $select = $('#formUsers #empresa_usuario');

        $select.empty();
        usuariosState.empresas = {};
        usuariosState.empresasCargadas = true;

        if (response.success && Array.isArray(response.data)) {
            response.data.forEach(function (item) {
                var id = String(item.empresa_id);
                var nombre = item.nombre || 'Empresa';

                usuariosState.empresas[id] = nombre;

                $select.append(
                    $('<option>', {
                        value: item.empresa_id,
                        text: nombre
                    })
                );
            });
        } else {
            $select.append(
                $('<option>', {
                    value: '',
                    text: 'No hay empresas disponibles'
                })
            );
        }

        $select.selectpicker('refresh');

        /*
         * La carga de empresas y usuarios ocurre en paralelo. Cuando ya
         * tenemos el catálogo, corregimos cualquier "No disponible" que
         * haya llegado antes desde el listado.
         */
        normalizarEmpresasUsuarios();
        aplicarFiltroUsuarios();
    })
    .fail(function () {
        usuariosState.empresasCargadas = true;

        showNotify(
            'error',
            'Error',
            'Error de conexión al cargar empresas'
        );
    });
}

function getPuestosColaboradoresUsuarios() {
    cargarSelectUsuario(
        '<?php echo SERVERURL; ?>core/getPuestoColaboradores.php',
        '#puesto_colaborador',
        'puestos_id',
        'nombre',
        'puestos'
    );
}

function cargarSelectUsuario(url, selector, idKey, textKey, nombre) {
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json'
    })
    .done(function (response) {
        var $select = $('#formUsers ' + selector);
        $select.empty();

        if (response.success && Array.isArray(response.data)) {
            response.data.forEach(function (item) {
                $select.append(
                    $('<option>', {
                        value: item[idKey],
                        text: item[textKey]
                    })
                );
            });
        } else {
            $select.append(
                $('<option>', {
                    value: '',
                    text: 'No hay ' + nombre + ' disponibles'
                })
            );
        }

        $select.selectpicker('refresh');
    })
    .fail(function () {
        showNotify(
            'error',
            'Error',
            'Error de conexión al cargar ' + nombre
        );
    });
}

function getColaboradoresUsuario() {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getColaboradores.php',
        type: 'POST',
        dataType: 'json'
    })
    .done(function (response) {
        var $select = $('#formUsers #colaboradores_id');
        $select.empty();

        if (response.success && Array.isArray(response.data)) {
            response.data.forEach(function (colaborador) {
                var $option = $('<option>', {
                    value: colaborador.colaboradores_id,
                    text: colaborador.nombre
                });

                $option.attr(
                    'data-subtext',
                    colaborador.identidad || 'Sin RTN o Identidad'
                );

                $select.append($option);
            });
        } else {
            $select.append(
                '<option value="">No hay colaboradores disponibles</option>'
            );
        }

        $select.selectpicker('refresh');
    })
    .fail(function () {
        showNotify(
            'error',
            'Error',
            'Error de conexión al cargar colaboradores'
        );
    });
}

/* =========================================================
   EXPORTACIÓN DE USUARIOS
   - Listado basado en DIVs
   - XLSX real siguiendo la misma estructura estable usada en Secuencia
   - CSV como respaldo si JSZip no está disponible
   - Código legible y mantenible
   ========================================================= */

function datosUsuariosExportar() {
    return usuariosState.filtrados.map(function (usuario) {
        return [
            usuario.colaborador || '',
            usuario.correo || '',
            usuario.tipo_usuario || '',
            usuario.privilegio || '',
            resolverEmpresaUsuario(usuario),
            String(usuario.estado) === '1' ? 'Activo' : 'Inactivo'
        ];
    });
}

function descargarBlob(blob, nombreArchivo) {
    var enlace = document.createElement('a');
    var url = URL.createObjectURL(blob);

    enlace.href = url;
    enlace.download = nombreArchivo;

    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);

    setTimeout(function () {
        URL.revokeObjectURL(url);
    }, 1000);
}

function usuariosXmlEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function usuariosExcelColName(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function usuariosExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    var texto = usuariosXmlEscape(value);
    var raw = String(value === null || value === undefined ? '' : value);
    var preserve = /^\s|\s$/.test(raw) ? ' xml:space="preserve"' : '';

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t' + preserve + '>' + texto + '</t></is>' +
        '</c>';
}

function usuariosGenerarXlsx(rows) {
    if (typeof JSZip === 'undefined') {
        return null;
    }

    var headers = [
        'Colaborador',
        'Correo',
        'Tipo Usuario',
        'Privilegio',
        'Empresa',
        'Estado'
    ];

    var totalActivos = rows.filter(function (row) {
        return String(row[5] || '').toLowerCase() === 'activo';
    }).length;

    var totalInactivos = rows.length - totalActivos;
    var lastCol = 'F';
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            usuariosExcelCell('A1', 'IZZY • REPORTE DE USUARIOS', 1, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            usuariosExcelCell(
                'A2',
                'Control de accesos, permisos y estado • Generado: ' + new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3" ht="18" customHeight="1">' +
            usuariosExcelCell('A3', 'REGISTROS', 6, false) +
            usuariosExcelCell('C3', 'ACTIVOS', 6, false) +
            usuariosExcelCell('E3', 'INACTIVOS', 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="26" customHeight="1">' +
            usuariosExcelCell('A4', rows.length, 7, true) +
            usuariosExcelCell('C4', totalActivos, 7, true) +
            usuariosExcelCell('E4', totalInactivos, 7, true) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="18" customHeight="1">' +
            usuariosExcelCell('A6', 'Detalle de usuarios filtrados', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function (header, index) {
        return usuariosExcelCell(
            usuariosExcelColName(index) + headerRow,
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

    rows.forEach(function (row, rowIndex) {
        var excelRow = firstDataRow + rowIndex;

        var cells = row.map(function (value, colIndex) {
            var style = 4;

            if (colIndex === 5) {
                style = String(value || '').toLowerCase() === 'activo' ? 9 : 10;
            }

            return usuariosExcelCell(
                usuariosExcelColName(colIndex) + excelRow,
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
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
                '<col min="2" max="2" width="34" customWidth="1"/>' +
                '<col min="3" max="4" width="20" customWidth="1"/>' +
                '<col min="5" max="5" width="26" customWidth="1"/>' +
                '<col min="6" max="6" width="14" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
            '<mergeCells count="8">' +
                '<mergeCell ref="A1:F1"/>' +
                '<mergeCell ref="A2:F2"/>' +
                '<mergeCell ref="A3:B3"/>' +
                '<mergeCell ref="A4:B4"/>' +
                '<mergeCell ref="C3:D3"/>' +
                '<mergeCell ref="C4:D4"/>' +
                '<mergeCell ref="E3:F3"/>' +
                '<mergeCell ref="E4:F4"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="Usuarios" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" ' +
                'Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" ' +
                'Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" ' +
                'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" ' +
                'Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var contentTypes =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
            console.error('Error al generar XLSX de usuarios con JSZip legado:', errorGenerate);
            return Promise.reject(errorGenerate);
        }
    }

    return Promise.reject(
        new Error('La versión de JSZip cargada no soporta generateAsync() ni generate().')
    );
}

function exportarUsuariosExcelPremium() {
    var rows = datosUsuariosExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin datos', 'No hay usuarios para exportar');
        return;
    }

    var promesaXlsx = usuariosGenerarXlsx(rows);

    if (!promesaXlsx) {
        exportarUsuariosCsvFallback(rows);
        return;
    }

    promesaXlsx
        .then(function (blob) {
            descargarBlob(
                blob,
                'Reporte_Usuarios_' + obtenerFechaArchivo() + '.xlsx'
            );
        })
        .catch(function (error) {
            console.error('Error al generar XLSX de usuarios:', error);
            showNotify(
                'error',
                'Error al generar Excel',
                'No se pudo generar el archivo Excel.'
            );
        });
}

function exportarUsuariosCsvFallback(rows) {
    var headers = [
        'Colaborador',
        'Correo',
        'Tipo Usuario',
        'Privilegio',
        'Empresa',
        'Estado'
    ];

    var csv = [headers]
        .concat(rows)
        .map(function (line) {
            return line.map(function (value) {
                var texto = String(
                    value === null || value === undefined ? '' : value
                ).replace(/"/g, '""');

                return '"' + texto + '"';
            }).join(',');
        })
        .join('\r\n');

    var blob = new Blob(
        ['\ufeff' + csv],
        { type: 'text/csv;charset=utf-8;' }
    );

    descargarBlob(
        blob,
        'Reporte_Usuarios_' + obtenerFechaArchivo() + '.csv'
    );

    showNotify(
        'warning',
        'Excel compatible',
        'JSZip no está disponible; se generó un CSV compatible con Excel.'
    );
}


function previsualizarUsuariosPdfPremium() {
    var rows = datosUsuariosExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin datos', 'No hay usuarios para exportar');
        return;
    }

    if (typeof pdfMake === 'undefined') {
        showNotify('error', 'PDF no disponible', 'No se encontró pdfMake.');
        return;
    }

    if (typeof abrirModalPdfPublico !== 'function') {
        showNotify('error', 'Visor PDF no disponible', 'No se encontró el modal PDF público.');
        return;
    }

    var activos = rows.filter(function(row){return row[5] === 'Activo';}).length;
    var inactivos = rows.length - activos;
    var admins = rows.filter(function(row){
        return String(row[2]||'').toLowerCase().indexOf('administrador') !== -1 ||
               String(row[3]||'').toLowerCase().indexOf('administrador') !== -1;
    }).length;

    var filtroTipo = $('#filtroTipoUsuario option:selected').text() || 'Todos';
    var filtroPrivilegio = $('#filtroPrivilegioUsuario option:selected').text() || 'Todos';
    var busqueda = String($('#buscarUsuarios').val() || '').trim();
    var logo = (typeof imagen !== 'undefined' && imagen)
        ? {image:imagen,width:50,height:24,alignment:'center',margin:[0,1,0,0]}
        : {text:'IZZY',fontSize:16,bold:true,color:'#FFFFFF',alignment:'center',margin:[0,4,0,0]};

    var encabezado={
        table:{widths:[70,'*',150],body:[[
            {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[logo]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[
                {text:'REPORTE DE USUARIOS',fontSize:16,bold:true,color:'#FFFFFF'},
                {text:'Control de accesos, permisos, empresas y estado',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}
            ]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,12,10],stack:[
                {text:'REPORTE EJECUTIVO',fontSize:6.5,bold:true,color:'#72E2E5',alignment:'right'},
                {text:new Date().toLocaleDateString('es-HN'),fontSize:9,bold:true,color:'#FFFFFF',alignment:'right',margin:[0,3,0,0]},
                {text:rows.length+' registro(s) filtrado(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right',margin:[0,2,0,0]}
            ]}
        ]]},
        layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},
        margin:[0,0,0,10]
    };

    var filtros={table:{widths:['*'],body:[[{text:'Filtros aplicados: Tipo: '+filtroTipo+'   |   Privilegio: '+filtroPrivilegio+'   |   Búsqueda: '+(busqueda||'Sin búsqueda'),fontSize:6.8,color:'#52627A',margin:[10,7,10,7],fillColor:'#F7F9FC'}]]},
        layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},margin:[0,0,0,10]};

    var resumen={table:{widths:['*','*','*','*'],body:[[
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'REGISTROS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(activos),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'INACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(inactivos),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
        {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ADMINISTRADORES',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(admins),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]}
    ]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},margin:[0,0,0,12]};

    var contenido=[];
    if (usuariosState.vista === 'miniatura') {
        function userCard(row) {
            var activo=row[5]==='Activo';
            return {table:{widths:['*'],body:[[{margin:[10,9,10,9],stack:[
                {columns:[
                    {width:'*',stack:[{text:row[0]||'Sin colaborador',fontSize:10,bold:true,color:'#172B4D'},{text:row[1]||'No registrado',fontSize:7,color:'#6B778C',margin:[0,2,0,0]}]},
                    {width:'auto',text:row[5],fontSize:7,bold:true,color:activo?'#14804A':'#C9372C'}
                ]},
                {canvas:[{type:'line',x1:0,y1:0,x2:250,y2:0,lineWidth:0.6,lineColor:'#DDE3EA'}],margin:[0,7,0,7]},
                {columns:[
                    {width:'50%',stack:[{text:'TIPO',fontSize:6.2,bold:true,color:'#6B778C'},{text:row[2]||'No registrado',fontSize:7.8,bold:true,color:'#172B4D',margin:[0,2,0,0]},{text:'EMPRESA',fontSize:6.2,bold:true,color:'#6B778C',margin:[0,8,0,0]},{text:row[4]||'No disponible',fontSize:7.8,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
                    {width:'50%',stack:[{text:'PRIVILEGIO',fontSize:6.2,bold:true,color:'#6B778C'},{text:row[3]||'No registrado',fontSize:7.8,bold:true,color:'#172B4D',margin:[0,2,0,0]}]}
                ]}
            ]}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.7;},vLineWidth:function(){return 0.7;}}};
        }
        for (var i=0;i<rows.length;i+=2) {
            contenido.push({columns:[
                {width:'*',stack:[userCard(rows[i])]},
                {width:10,text:''},
                rows[i+1]?{width:'*',stack:[userCard(rows[i+1])]}:{width:'*',text:''}
            ],margin:[0,0,0,9]});
        }
    } else {
        var body=[[{text:'COLABORADOR',style:'th',fillColor:'#17324D'},{text:'CORREO',style:'th',fillColor:'#17324D'},{text:'TIPO USUARIO',style:'th',fillColor:'#17324D'},{text:'PRIVILEGIO',style:'th',fillColor:'#17324D'},{text:'EMPRESA',style:'th',fillColor:'#17324D'},{text:'ESTADO',style:'th',fillColor:'#17324D'}]];
        rows.forEach(function(row,index){
            var fill=index%2===0?'#FFFFFF':'#F7F9FC';
            body.push([
                {text:row[0]||'—',fillColor:fill,bold:true},
                {text:row[1]||'—',fillColor:fill},
                {text:row[2]||'—',fillColor:fill},
                {text:row[3]||'—',fillColor:fill},
                {text:row[4]||'—',fillColor:fill},
                {text:row[5]||'—',fillColor:fill,color:row[5]==='Activo'?'#14804A':'#C9372C',bold:true,alignment:'center'}
            ]);
        });
        contenido=[{table:{headerRows:1,widths:[115,165,90,90,120,60],body:body},layout:{
            hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.55;},vLineWidth:function(){return 0.55;},
            paddingLeft:function(){return 5;},paddingRight:function(){return 5;},paddingTop:function(){return 6;},paddingBottom:function(){return 6;}
        }}];
    }

    var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],
        header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},
        footer:function(currentPage,pageCount){return{margin:[28,8,28,0],columns:[{text:'IZZY • Gestión de Usuarios',fontSize:7,color:'#7A869A'},{text:'Página '+currentPage+' de '+pageCount,fontSize:7,color:'#7A869A',alignment:'right'}]};},
        content:[encabezado,filtros,resumen,{text:usuariosState.vista==='miniatura'?'VISTA MINIATURA':'VISTA DETALLE',fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]}].concat(contenido),
        styles:{th:{fontSize:6.4,bold:true,color:'#FFFFFF',alignment:'center'}},
        defaultStyle:{fontSize:8,color:'#253858'}
    };

    var pdf=pdfMake.createPdf(doc);
    var nombreArchivo='Reporte_Usuarios_'+obtenerFechaArchivo()+'.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(url){abrirModalPdfPublico(url,'Reporte de Usuarios',nombreArchivo);});
        return;
    }
    if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function(base64){abrirModalPdfPublico('data:application/pdf;base64,'+base64,'Reporte de Usuarios',nombreArchivo);});
        return;
    }
    showNotify('error','PDF no disponible','La versión actual de pdfMake no permite previsualización compatible.');
}


function construirPdfUsuariosMiniaturaNativo(rows) {
    var anchoPagina = 792;
    var altoPagina = 612;
    var margenX = 34;
    var filasPorPagina = 6;
    var paginas = [];

    for (var inicio = 0; inicio < rows.length; inicio += filasPorPagina) {
        paginas.push(rows.slice(inicio, inicio + filasPorPagina));
    }

    if (!paginas.length) {
        paginas.push([]);
    }

    var objetos = [];
    var paginasObjetos = [];
    var catalogoId = agregarObjetoPdf(objetos, '');
    var paginasId = agregarObjetoPdf(objetos, '');
    var fuenteId = agregarObjetoPdf(
        objetos,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'
    );
    var fuenteBoldId = agregarObjetoPdf(
        objetos,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'
    );

    paginas.forEach(function (filasPagina, indicePagina) {
        var stream = construirContenidoMiniaturaPdfUsuarios(
            filasPagina,
            indicePagina + 1,
            paginas.length,
            anchoPagina,
            altoPagina,
            margenX
        );

        var contenidoId = agregarObjetoPdf(
            objetos,
            '<< /Length ' + longitudUtf8(stream) + ' >>\nstream\n' +
            stream +
            '\nendstream'
        );

        var paginaId = agregarObjetoPdf(
            objetos,
            '<< /Type /Page ' +
            '/Parent ' + paginasId + ' 0 R ' +
            '/MediaBox [0 0 ' + anchoPagina + ' ' + altoPagina + '] ' +
            '/Resources << /Font << ' +
                '/F1 ' + fuenteId + ' 0 R ' +
                '/F2 ' + fuenteBoldId + ' 0 R ' +
            '>> >> ' +
            '/Contents ' + contenidoId + ' 0 R >>'
        );

        paginasObjetos.push(paginaId);
    });

    objetos[paginasId - 1] = '' +
        paginasId + ' 0 obj\n' +
        '<< /Type /Pages /Count ' + paginasObjetos.length + ' /Kids [' +
            paginasObjetos.map(function (id) {
                return id + ' 0 R';
            }).join(' ') +
        '] >>\n' +
        'endobj\n';

    objetos[catalogoId - 1] = '' +
        catalogoId + ' 0 obj\n' +
        '<< /Type /Catalog /Pages ' + paginasId + ' 0 R >>\n' +
        'endobj\n';

    var pdf = '%PDF-1.4\n%âãÏÓ\n';
    var offsets = [0];

    objetos.forEach(function (objeto) {
        offsets.push(longitudUtf8(pdf));
        pdf += objeto;
    });

    var xrefOffset = longitudUtf8(pdf);

    pdf += 'xref\n';
    pdf += '0 ' + (objetos.length + 1) + '\n';
    pdf += '0000000000 65535 f \n';

    for (var i = 1; i <= objetos.length; i++) {
        pdf += rellenarNumeroPdf(offsets[i], 10) + ' 00000 n \n';
    }

    pdf += 'trailer\n';
    pdf += '<< /Size ' + (objetos.length + 1) + ' /Root ' + catalogoId + ' 0 R >>\n';
    pdf += 'startxref\n';
    pdf += xrefOffset + '\n';
    pdf += '%%EOF';

    return new Blob(
        [new TextEncoder().encode(pdf)],
        {type: 'application/pdf'}
    );
}

function construirContenidoMiniaturaPdfUsuarios(
    rows,
    paginaActual,
    totalPaginas,
    anchoPagina,
    altoPagina,
    margenX
) {
    var contenido = '';
    var fecha = new Date().toLocaleDateString('es-HN');
    var yTitulo = altoPagina - 34;

    contenido += pdfColorRelleno(23, 50, 77);
    contenido += pdfRectanguloRelleno(
        margenX,
        yTitulo - 36,
        anchoPagina - (margenX * 2),
        42
    );

    contenido += pdfTexto(
        'IZZY • REPORTE DE USUARIOS',
        margenX + 12,
        yTitulo - 12,
        16,
        true,
        [255, 255, 255]
    );

    contenido += pdfTexto(
        'Vista miniatura • Control de accesos, permisos y estado',
        margenX + 12,
        yTitulo - 29,
        8,
        false,
        [220, 231, 239]
    );

    contenido += pdfTexto(
        'Generado: ' + fecha,
        anchoPagina - margenX - 115,
        yTitulo - 12,
        8,
        false,
        [255, 255, 255]
    );

    var activos = rows.filter(function (row) {
        return row[5] === 'Activo';
    }).length;

    var yKpi = yTitulo - 84;
    contenido += pdfKpi('REGISTROS', rows.length, margenX, yKpi, 150);
    contenido += pdfKpi('ACTIVOS', activos, margenX + 162, yKpi, 150);
    contenido += pdfKpi('INACTIVOS', rows.length - activos, margenX + 324, yKpi, 150);

    var cardWidth = 350;
    var cardHeight = 108;
    var gapX = 16;
    var gapY = 12;
    var inicioY = yKpi - 56;

    rows.forEach(function (row, index) {
        var col = index % 2;
        var fila = Math.floor(index / 2);
        var x = margenX + (col * (cardWidth + gapX));
        var y = inicioY - (fila * (cardHeight + gapY)) - cardHeight;
        var activo = row[5] === 'Activo';

        contenido += pdfColorRelleno(255, 255, 255);
        contenido += pdfRectanguloRelleno(x, y, cardWidth, cardHeight);
        contenido += pdfColorLinea(215, 225, 235);
        contenido += pdfRectanguloLinea(x, y, cardWidth, cardHeight);

        contenido += pdfColorRelleno(32, 201, 151);
        contenido += pdfRectanguloRelleno(x, y + cardHeight - 3, cardWidth, 3);

        contenido += pdfTextoAjustado(
            row[0] || 'Sin colaborador',
            x + 12,
            y + cardHeight - 22,
            225,
            10,
            true,
            [23, 50, 77]
        );

        contenido += pdfTexto(
            activo ? 'ACTIVO' : 'INACTIVO',
            x + cardWidth - 64,
            y + cardHeight - 22,
            7,
            true,
            activo ? [20, 128, 74] : [201, 55, 44]
        );

        contenido += pdfTextoAjustado(
            'Correo: ' + (row[1] || 'No registrado'),
            x + 12,
            y + 61,
            cardWidth - 24,
            7.3,
            false,
            [70, 85, 107]
        );

        contenido += pdfTextoAjustado(
            'Tipo: ' + (row[2] || 'No registrado') + '   |   Privilegio: ' + (row[3] || 'No registrado'),
            x + 12,
            y + 43,
            cardWidth - 24,
            7.3,
            false,
            [70, 85, 107]
        );

        contenido += pdfTextoAjustado(
            'Empresa: ' + (row[4] || 'No disponible'),
            x + 12,
            y + 25,
            cardWidth - 24,
            7.3,
            true,
            [35, 54, 77]
        );
    });

    contenido += pdfTexto(
        'IZZY • Gestión de Usuarios',
        margenX,
        20,
        7,
        false,
        [123, 139, 160]
    );

    contenido += pdfTexto(
        'Página ' + paginaActual + ' de ' + totalPaginas,
        anchoPagina - margenX - 75,
        20,
        7,
        false,
        [123, 139, 160]
    );

    return contenido;
}

function construirPdfUsuariosNativo(rows) {
    var anchoPagina = 792;
    var altoPagina = 612;
    var margenX = 34;
    var margenSuperior = 34;
    var altoFila = 24;
    var encabezados = [
        'Colaborador',
        'Correo',
        'Tipo Usuario',
        'Privilegio',
        'Empresa',
        'Estado'
    ];

    var anchos = [150, 190, 90, 90, 120, 84];
    var filasPorPagina = 16;
    var paginas = [];

    for (var inicio = 0; inicio < rows.length; inicio += filasPorPagina) {
        paginas.push(
            rows.slice(inicio, inicio + filasPorPagina)
        );
    }

    if (!paginas.length) {
        paginas.push([]);
    }

    var objetos = [];
    var paginasObjetos = [];
    var catalogoId = agregarObjetoPdf(objetos, '');
    var paginasId = agregarObjetoPdf(objetos, '');
    var fuenteId = agregarObjetoPdf(
        objetos,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'
    );
    var fuenteBoldId = agregarObjetoPdf(
        objetos,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'
    );

    paginas.forEach(function (filasPagina, indicePagina) {
        var stream = construirContenidoPaginaPdfUsuarios(
            filasPagina,
            indicePagina + 1,
            paginas.length,
            anchoPagina,
            altoPagina,
            margenX,
            margenSuperior,
            altoFila,
            encabezados,
            anchos
        );

        var contenidoId = agregarObjetoPdf(
            objetos,
            '<< /Length ' + longitudUtf8(stream) + ' >>\nstream\n' +
            stream +
            '\nendstream'
        );

        var paginaId = agregarObjetoPdf(
            objetos,
            '<< /Type /Page ' +
            '/Parent ' + paginasId + ' 0 R ' +
            '/MediaBox [0 0 ' + anchoPagina + ' ' + altoPagina + '] ' +
            '/Resources << /Font << ' +
                '/F1 ' + fuenteId + ' 0 R ' +
                '/F2 ' + fuenteBoldId + ' 0 R ' +
            '>> >> ' +
            '/Contents ' + contenidoId + ' 0 R >>'
        );

        paginasObjetos.push(paginaId);
    });

    objetos[paginasId - 1] = '' +
        paginasId + ' 0 obj\n' +
        '<< /Type /Pages /Count ' + paginasObjetos.length + ' /Kids [' +
            paginasObjetos.map(function (id) {
                return id + ' 0 R';
            }).join(' ') +
        '] >>\n' +
        'endobj\n';

    objetos[catalogoId - 1] = '' +
        catalogoId + ' 0 obj\n' +
        '<< /Type /Catalog /Pages ' + paginasId + ' 0 R >>\n' +
        'endobj\n';

    var pdf = '%PDF-1.4\n%âãÏÓ\n';
    var offsets = [0];

    objetos.forEach(function (objeto) {
        offsets.push(longitudUtf8(pdf));
        pdf += objeto;
    });

    var xrefOffset = longitudUtf8(pdf);

    pdf += 'xref\n';
    pdf += '0 ' + (objetos.length + 1) + '\n';
    pdf += '0000000000 65535 f \n';

    for (var i = 1; i <= objetos.length; i++) {
        pdf += rellenarNumeroPdf(offsets[i], 10) + ' 00000 n \n';
    }

    pdf += 'trailer\n';
    pdf += '<< /Size ' + (objetos.length + 1) + ' /Root ' + catalogoId + ' 0 R >>\n';
    pdf += 'startxref\n';
    pdf += xrefOffset + '\n';
    pdf += '%%EOF';

    return new Blob(
        [new TextEncoder().encode(pdf)],
        {
            type: 'application/pdf'
        }
    );
}

function construirContenidoPaginaPdfUsuarios(
    rows,
    paginaActual,
    totalPaginas,
    anchoPagina,
    altoPagina,
    margenX,
    margenSuperior,
    altoFila,
    encabezados,
    anchos
) {
    var contenido = '';
    var fecha = new Date().toLocaleDateString('es-HN');
    var yTitulo = altoPagina - margenSuperior;

    contenido += pdfColorRelleno(23, 50, 77);
    contenido += pdfRectanguloRelleno(
        margenX,
        yTitulo - 36,
        anchoPagina - (margenX * 2),
        42
    );

    contenido += pdfTexto(
        'IZZY • REPORTE DE USUARIOS',
        margenX + 12,
        yTitulo - 12,
        16,
        true,
        [255, 255, 255]
    );

    contenido += pdfTexto(
        'Control de accesos, permisos y estado',
        margenX + 12,
        yTitulo - 29,
        8,
        false,
        [220, 231, 239]
    );

    contenido += pdfTexto(
        'Generado: ' + fecha,
        anchoPagina - margenX - 115,
        yTitulo - 12,
        8,
        false,
        [255, 255, 255]
    );

    var activos = rows.filter(function (row) {
        return row[5] === 'Activo';
    }).length;

    var total = rows.length;
    var yKpi = yTitulo - 84;
    var kpiAncho = 150;

    contenido += pdfKpi('REGISTROS', total, margenX, yKpi, kpiAncho);
    contenido += pdfKpi('ACTIVOS', activos, margenX + 162, yKpi, kpiAncho);
    contenido += pdfKpi('INACTIVOS', total - activos, margenX + 324, yKpi, kpiAncho);

    var yTabla = yKpi - 46;
    var x = margenX;

    encabezados.forEach(function (encabezado, index) {
        contenido += pdfColorRelleno(15, 163, 177);
        contenido += pdfRectanguloRelleno(
            x,
            yTabla,
            anchos[index],
            altoFila
        );

        contenido += pdfTextoAjustado(
            encabezado,
            x + 5,
            yTabla + 8,
            anchos[index] - 10,
            8,
            true,
            [255, 255, 255]
        );

        x += anchos[index];
    });

    rows.forEach(function (row, rowIndex) {
        var y = yTabla - ((rowIndex + 1) * altoFila);
        var fondo = rowIndex % 2 === 0
            ? [247, 249, 251]
            : [255, 255, 255];

        x = margenX;

        row.forEach(function (valor, columnIndex) {
            contenido += pdfColorRelleno(
                fondo[0],
                fondo[1],
                fondo[2]
            );

            contenido += pdfRectanguloRelleno(
                x,
                y,
                anchos[columnIndex],
                altoFila
            );

            contenido += pdfColorLinea(220, 227, 234);
            contenido += pdfRectanguloLinea(
                x,
                y,
                anchos[columnIndex],
                altoFila
            );

            contenido += pdfTextoAjustado(
                valor,
                x + 5,
                y + 8,
                anchos[columnIndex] - 10,
                7.5,
                false,
                [35, 54, 77]
            );

            x += anchos[columnIndex];
        });
    });

    contenido += pdfTexto(
        'IZZY • Gestión de Usuarios',
        margenX,
        20,
        7,
        false,
        [123, 139, 160]
    );

    contenido += pdfTexto(
        'Página ' + paginaActual + ' de ' + totalPaginas,
        anchoPagina - margenX - 75,
        20,
        7,
        false,
        [123, 139, 160]
    );

    return contenido;
}

function pdfKpi(label, value, x, y, width) {
    var contenido = '';

    contenido += pdfColorRelleno(250, 252, 253);
    contenido += pdfRectanguloRelleno(x, y, width, 38);
    contenido += pdfColorLinea(220, 227, 234);
    contenido += pdfRectanguloLinea(x, y, width, 38);

    contenido += pdfTexto(
        label,
        x + 10,
        y + 24,
        7,
        false,
        [108, 122, 137]
    );

    contenido += pdfTexto(
        String(value),
        x + 10,
        y + 8,
        15,
        true,
        [23, 50, 77]
    );

    return contenido;
}

function pdfTexto(texto, x, y, fontSize, bold, color) {
    var fuente = bold ? '/F2' : '/F1';
    var textoSeguro = pdfEscape(texto);

    return '' +
        pdfColorTexto(color[0], color[1], color[2]) +
        'BT\n' +
        fuente + ' ' + fontSize + ' Tf\n' +
        '1 0 0 1 ' + x.toFixed(2) + ' ' + y.toFixed(2) + ' Tm\n' +
        '(' + textoSeguro + ') Tj\n' +
        'ET\n';
}

function pdfTextoAjustado(
    texto,
    x,
    y,
    anchoDisponible,
    fontSize,
    bold,
    color
) {
    var maxCaracteres = Math.max(
        4,
        Math.floor(anchoDisponible / (fontSize * 0.52))
    );

    var textoAjustado = String(texto == null ? '' : texto);

    if (textoAjustado.length > maxCaracteres) {
        textoAjustado = textoAjustado.substring(
            0,
            Math.max(1, maxCaracteres - 3)
        ) + '...';
    }

    return pdfTexto(
        textoAjustado,
        x,
        y,
        fontSize,
        bold,
        color
    );
}

function pdfRectanguloRelleno(x, y, width, height) {
    return '' +
        x.toFixed(2) + ' ' +
        y.toFixed(2) + ' ' +
        width.toFixed(2) + ' ' +
        height.toFixed(2) +
        ' re f\n';
}

function pdfRectanguloLinea(x, y, width, height) {
    return '' +
        x.toFixed(2) + ' ' +
        y.toFixed(2) + ' ' +
        width.toFixed(2) + ' ' +
        height.toFixed(2) +
        ' re S\n';
}

function pdfColorRelleno(r, g, b) {
    return '' +
        (r / 255).toFixed(3) + ' ' +
        (g / 255).toFixed(3) + ' ' +
        (b / 255).toFixed(3) +
        ' rg\n';
}

function pdfColorTexto(r, g, b) {
    return pdfColorRelleno(r, g, b);
}

function pdfColorLinea(r, g, b) {
    return '' +
        (r / 255).toFixed(3) + ' ' +
        (g / 255).toFixed(3) + ' ' +
        (b / 255).toFixed(3) +
        ' RG\n';
}

function pdfEscape(valor) {
    return normalizarTextoPdf(valor)
        .replace(/\\/g, '\\\\')
        .replace(/\(/g, '\\(')
        .replace(/\)/g, '\\)');
}

function normalizarTextoPdf(valor) {
    return String(valor == null ? '' : valor)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[•]/g, '-')
        .replace(/[–—]/g, '-')
        .replace(/[“”]/g, '"')
        .replace(/[‘’]/g, "'")
        .replace(/[^\x20-\x7E]/g, '');
}

function agregarObjetoPdf(objetos, contenido) {
    var id = objetos.length + 1;

    objetos.push(
        id + ' 0 obj\n' +
        contenido + '\n' +
        'endobj\n'
    );

    return id;
}

function rellenarNumeroPdf(numero, longitud) {
    var texto = String(numero);

    while (texto.length < longitud) {
        texto = '0' + texto;
    }

    return texto;
}

function longitudUtf8(texto) {
    return new TextEncoder().encode(texto).length;
}

function obtenerFechaArchivo() {
    return new Date().toISOString().slice(0, 10);
}

/* =========================================================
   USUARIOS | DROPDOWN DE ACCIONES ADAPTATIVO
   Funciona en vista detalle y miniatura.
   ========================================================= */
function limpiarDireccionDropdownUsuarios($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    $dropdown.removeClass('dropup dropright dropleft');
    $dropdown.children('.dropdown-menu')
        .removeClass('dropdown-menu-right')
        .removeAttr('x-placement data-popper-placement')
        .css({ top: '', left: '', right: '', bottom: '', transform: '' });
}

function medirMenuDropdownUsuarios($menu) {
    var menu = $menu && $menu.length ? $menu[0] : null;

    if (!menu) {
        return { width: 200, height: 150 };
    }

    var estilos = {
        display: menu.style.display,
        visibility: menu.style.visibility,
        position: menu.style.position,
        top: menu.style.top,
        left: menu.style.left,
        right: menu.style.right,
        bottom: menu.style.bottom,
        transform: menu.style.transform
    };
    var teniaShow = $menu.hasClass('show');

    $menu.addClass('show').css({
        display: 'block',
        visibility: 'hidden',
        position: 'fixed',
        top: '0',
        left: '0',
        right: 'auto',
        bottom: 'auto',
        transform: 'none'
    });

    var rect = menu.getBoundingClientRect();

    if (!teniaShow) {
        $menu.removeClass('show');
    }

    menu.style.display = estilos.display;
    menu.style.visibility = estilos.visibility;
    menu.style.position = estilos.position;
    menu.style.top = estilos.top;
    menu.style.left = estilos.left;
    menu.style.right = estilos.right;
    menu.style.bottom = estilos.bottom;
    menu.style.transform = estilos.transform;

    return {
        width: Math.max(rect.width || 0, 200),
        height: Math.max(rect.height || 0, 1)
    };
}

function prepararDireccionDropdownUsuarios($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    var $button = $dropdown.children('.js-acciones-toggle');
    var $menu = $dropdown.children('.dropdown-menu');
    var button = $button.length ? $button[0] : null;

    if (!button || !$menu.length) {
        return;
    }

    limpiarDireccionDropdownUsuarios($dropdown);

    var rect = button.getBoundingClientRect();
    var menuSize = medirMenuDropdownUsuarios($menu);
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var margin = 12;
    var gap = 8;

    var abajo = viewportHeight - rect.bottom - margin;
    var arriba = rect.top - margin;
    var derecha = viewportWidth - rect.right - margin;
    var izquierda = rect.left - margin;

    if (abajo >= menuSize.height + gap) {
        // Posición normal: abajo.
    } else if (arriba >= menuSize.height + gap) {
        $dropdown.addClass('dropup');
    } else if (derecha >= menuSize.width + gap) {
        $dropdown.addClass('dropright');
    } else if (izquierda >= menuSize.width + gap) {
        $dropdown.addClass('dropleft');
    } else if (arriba > abajo) {
        $dropdown.addClass('dropup');
    }

    if (!$dropdown.hasClass('dropright') && !$dropdown.hasClass('dropleft')) {
        var desbordaDerecha = rect.left + menuSize.width > viewportWidth - margin;
        var puedeAlinearDerecha = rect.right - menuSize.width >= margin;

        if (desbordaDerecha && puedeAlinearDerecha) {
            $menu.addClass('dropdown-menu-right');
        }
    }
}

function cerrarDropdownsUsuariosExcepto($actual) {
    $('#usuariosListado .acciones-dropdown').each(function () {
        var $dropdown = $(this);
        var $btn = $dropdown.children('.js-acciones-toggle');
        var $menu = $dropdown.children('.dropdown-menu');

        if ($actual && $actual.length && $dropdown.is($actual)) {
            return;
        }

        try {
            if (typeof $.fn.dropdown === 'function' && $menu.hasClass('show')) {
                $btn.dropdown('hide');
            }
        } catch (error) {
            // Respaldo manual abajo.
        }

        $btn.attr('aria-expanded', 'false');
        $dropdown.removeClass('show');
        $menu.removeClass('show');
        limpiarDireccionDropdownUsuarios($dropdown);
        $dropdown.closest('.usuario-row, .usuario-mini-card').removeClass('usuario-dropdown-open');
    });
}

function inicializarDropdownAccionesUsuarios() {
    $('#usuariosListado')
        .off('click.usuariosDropdownAdaptativo', '.acciones-dropdown .js-acciones-toggle')
        .on('click.usuariosDropdownAdaptativo', '.acciones-dropdown .js-acciones-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            var $button = $(this);
            var $dropdown = $button.closest('.acciones-dropdown');
            var $menu = $dropdown.children('.dropdown-menu');
            var estabaAbierto = $menu.hasClass('show');

            if (typeof $.fn.dropdown !== 'function') {
                return;
            }

            cerrarDropdownsUsuariosExcepto($dropdown);

            if (estabaAbierto) {
                try {
                    $button.dropdown('hide');
                } catch (error) {
                    $dropdown.removeClass('show');
                    $menu.removeClass('show');
                }

                $button.attr('aria-expanded', 'false');
                limpiarDireccionDropdownUsuarios($dropdown);
                $dropdown.closest('.usuario-row, .usuario-mini-card').removeClass('usuario-dropdown-open');
                return;
            }

            try {
                prepararDireccionDropdownUsuarios($dropdown);

                $button.dropdown({
                    boundary: 'viewport',
                    flip: true,
                    offset: '0,6'
                });

                $button.dropdown('show');
                $dropdown.closest('.usuario-row, .usuario-mini-card').addClass('usuario-dropdown-open');
            } catch (error) {
                console.error('No se pudo abrir el dropdown de acciones de Usuarios:', error);
            }
        });

    $(document)
        .off('shown.bs.dropdown.usuariosDropdownAdaptativo', '#usuariosListado .acciones-dropdown')
        .on('shown.bs.dropdown.usuariosDropdownAdaptativo', '#usuariosListado .acciones-dropdown', function () {
            var $dropdown = $(this);
            cerrarDropdownsUsuariosExcepto($dropdown);
            $dropdown.closest('.usuario-row, .usuario-mini-card').addClass('usuario-dropdown-open');
        })
        .off('hidden.bs.dropdown.usuariosDropdownAdaptativo', '#usuariosListado .acciones-dropdown')
        .on('hidden.bs.dropdown.usuariosDropdownAdaptativo', '#usuariosListado .acciones-dropdown', function () {
            var $dropdown = $(this);
            limpiarDireccionDropdownUsuarios($dropdown);
            $dropdown.closest('.usuario-row, .usuario-mini-card').removeClass('usuario-dropdown-open');
        });
}

</script>
