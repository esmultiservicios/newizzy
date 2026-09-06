<script>
$(() => {
    GetEstadoBotonFirma();

    $('#form_main_empresa #search').on("click", function (e) {
        e.preventDefault();
        empresaState.pagina = 1;
        aplicarFiltrosEmpresa();
    });

    $('#form_main_empresa').on('reset', function () {
        $(this).find('.selectpicker')
            .val('')
            .selectpicker('refresh');

        $('#filtro_empresa_general').val('');

        setTimeout(function () {
            empresaState.pagina = 1;
            aplicarFiltrosEmpresa();
        }, 100);
    });

    const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';

    const cfgs = [
        { drop: '#logoDropArea',  input: '#logotipo',        preview: '#logoPreview',  info: '#logoInfo',  maxMB: 2 },
        { drop: '#firmaDropArea', input: '#firma_documento', preview: '#firmaPreview', info: '#firmaInfo', maxMB: 2 }
    ];

    let lastAreaCtx = null;

    cfgs.forEach(setupUploader);

    document.addEventListener('paste', function (e) {
        const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items || [];
        let file = null;

        for (let i = 0; i < items.length; i++) {
            if (items[i].kind === 'file' && items[i].type.startsWith('image/')) {
                file = items[i].getAsFile();
                break;
            }
        }

        if (!file) return;

        e.preventDefault();

        const ctx = lastAreaCtx || getFirstAvailableCtx();

        if (!ctx) return;

        const dt = new DataTransfer();
        dt.items.add(file);

        handleFiles(dt.files, ctx);
    });

    function getFirstAvailableCtx() {
        for (const c of cfgs) {
            const drop = document.querySelector(c.drop);
            const input = document.querySelector(c.input);
            const preview = document.querySelector(c.preview);
            const info = document.querySelector(c.info);

            if (drop && input && preview && info) {
                return { drop, input, preview, info, maxMB: c.maxMB };
            }
        }

        return null;
    }

    function setupUploader({ drop, input, preview, info, maxMB }) {
        const dropArea = document.querySelector(drop);
        const fileInput = document.querySelector(input);
        const previewEl = document.querySelector(preview);
        const infoEl = document.querySelector(info);

        if (!dropArea || !fileInput || !previewEl || !infoEl) return;

        if (fileInput.dataset.initialized) return;

        fileInput.dataset.initialized = 'true';

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev =>
            dropArea.addEventListener(ev, preventDefaults, false)
        );

        ['dragenter', 'dragover'].forEach(ev =>
            dropArea.addEventListener(ev, () => dropArea.classList.add('drag-over'), false)
        );

        ['dragleave', 'drop'].forEach(ev =>
            dropArea.addEventListener(ev, () => dropArea.classList.remove('drag-over'), false)
        );

        dropArea.addEventListener('drop', e => {
            const files = e.dataTransfer?.files || [];

            if (files.length) {
                handleFiles(files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
            }
        });

        ['mouseenter', 'focusin'].forEach(ev => {
            dropArea.addEventListener(ev, () => {
                lastAreaCtx = { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB };
            });
        });

        fileInput.addEventListener('change', e => {
            handleFiles(e.target.files, { drop: dropArea, input: fileInput, preview: previewEl, info: infoEl, maxMB });
        });

        const chooseBtn = dropArea.querySelector('.btn-file-chooser');
        const selectLink = dropArea.querySelector('.select-file-text');

        const openChooser = (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileInput.click();
        };

        if (chooseBtn) {
            chooseBtn.addEventListener('click', openChooser);
        }

        if (selectLink) {
            selectLink.addEventListener('click', openChooser);
            selectLink.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    openChooser(e);
                }
            });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    function handleFiles(fileList, ctx) {
        if (!ctx || !fileList || !fileList.length) return;

        const { input, preview, info, maxMB } = ctx;
        const file = fileList[0];

        if (!file.type.startsWith('image/')) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Error', 'El archivo debe ser una imagen (JPG, PNG, GIF)');
            } else {
                alert('El archivo debe ser una imagen (JPG, PNG, GIF)');
            }

            resetField(ctx);
            return;
        }

        if (file.size > maxMB * 1024 * 1024) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Error', 'La imagen no debe exceder ' + maxMB + 'MB');
            } else {
                alert('La imagen no debe exceder ' + maxMB + 'MB');
            }

            resetField(ctx);
            return;
        }

        info.textContent = `${file.name} (${formatFileSize(file.size)})`;

        const reader = new FileReader();

        reader.onload = function (e) {
            preview.innerHTML = '';

            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            img.className = 'img-thumbnail';
            img.style.maxWidth = '200px';
            img.style.maxHeight = '200px';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-image';
            removeBtn.title = 'Eliminar imagen';
            removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
            removeBtn.style.position = 'absolute';
            removeBtn.style.top = '5px';
            removeBtn.style.right = '5px';
            removeBtn.style.background = 'rgba(220,53,69,.95)';
            removeBtn.style.color = '#fff';
            removeBtn.style.border = 'none';
            removeBtn.style.borderRadius = '50%';
            removeBtn.style.width = '32px';
            removeBtn.style.height = '32px';
            removeBtn.style.display = 'flex';
            removeBtn.style.alignItems = 'center';
            removeBtn.style.justifyContent = 'center';
            removeBtn.style.boxShadow = '0 2px 6px rgba(0,0,0,.18)';

            removeBtn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                resetField(ctx);
            });

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);

            preview.appendChild(wrapper);
            preview.style.display = 'block';
        };

        reader.readAsDataURL(file);
    }

    function resetField(ctx) {
        const { input, preview, info } = ctx;

        input.value = '';
        preview.innerHTML = '';
        preview.style.display = 'none';
        info.textContent = 'Ningún archivo seleccionado';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
});

/* =========================================================
   EMPRESA | LISTADO EN DIVS + DETALLE / MINIATURA
   ========================================================= */

var EMPRESA_STORAGE_VISTA = 'izzy.empresa.tipo_vista';
var EMPRESA_STORAGE_FILTROS = 'izzy.empresa.filtros.visible';
var EMPRESA_STORAGE_KPIS = 'izzy.empresa.kpis.visible';

var empresaState = {
    registros: [],
    filtrados: [],
    pagina: 1,
    porPagina: 10,
    porPaginaDetalle: 10,
    porPaginaMiniatura: 6,
    vista: 'detalle',
    busqueda: ''
};

function inicializarEmpresaModulo() {
    inicializarEmpresaUI();
    inicializarStackDropdownEmpresa();

    $('#btn_actualizar_empresa')
        .off('click.empresa')
        .on('click.empresa', function () {
            listar_empresa();
        });

    $('#btn_ingresar_empresa')
        .off('click.empresa')
        .on('click.empresa', function () {
            modal_empresa();
        });

    $('#btn_excel_empresa')
        .off('click.empresa')
        .on('click.empresa', exportarEmpresaExcelPremium);

    $('#btn_pdf_empresa')
        .off('click.empresa')
        .on('click.empresa', previsualizarEmpresaPdfPremium);

    $('#empresa_page_size')
        .off('change.empresa')
        .on('change.empresa', function () {
            var valor = parseInt($(this).val(), 10);

            empresaState.porPagina = isNaN(valor) || valor <= 0
                ? (empresaState.vista === 'miniatura' ? 6 : 10)
                : valor;

            if (empresaState.vista === 'miniatura') {
                empresaState.porPaginaMiniatura = empresaState.porPagina;
            } else {
                empresaState.porPaginaDetalle = empresaState.porPagina;
            }

            empresaState.pagina = 1;
            renderEmpresa();
        });

    $('.empresa-view-btn')
        .off('click.empresaVista')
        .on('click.empresaVista', function () {
            cambiarVistaEmpresa($(this).data('view'));
        });

    $('#buscar_empresa_listado')
        .off('input.empresa')
        .on('input.empresa', function () {
            empresaState.busqueda = String($(this).val() || '').trim().toLowerCase();
            empresaState.pagina = 1;
            aplicarFiltrosEmpresa();
        });

    $(document)
        .off('click.empresaEditar', '#empresaListado .table_editar')
        .on('click.empresaEditar', '#empresaListado .table_editar', function () {
            var id = $(this).closest('[data-id]').data('id');
            editarEmpresaPorId(id);
        });

    $(document)
        .off('click.empresaEliminar', '#empresaListado .table_eliminar')
        .on('click.empresaEliminar', '#empresaListado .table_eliminar', function () {
            var id = $(this).closest('[data-id]').data('id');
            eliminarEmpresaPorId(id);
        });

    listar_empresa();
}

/* =========================================================
   STACKING DEL DROPDOWN DE ACCIONES
   Mantiene la fila/card con acciones por encima del resto.
   ========================================================= */
function limpiarStackDropdownEmpresa() {
    $('#empresaListado .empresa-detail-row, #empresaListado .empresa-mini-card')
        .removeClass('empresa-dropdown-open');
}

function activarStackDropdownEmpresa($dropdown) {
    limpiarStackDropdownEmpresa();

    if (!$dropdown || !$dropdown.length) {
        return;
    }

    $dropdown
        .closest('.empresa-detail-row, .empresa-mini-card')
        .addClass('empresa-dropdown-open');
}

function cerrarDropdownsEmpresaExcepto($actual) {
    $('#empresaListado .acciones-dropdown').each(function () {
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
            /* Limpieza manual abajo como respaldo. */
        }

        $btn.attr('aria-expanded', 'false');
        $dropdown.removeClass('show');
        $menu.removeClass('show');
        limpiarDireccionDropdownEmpresa($dropdown);

        /* Solo se baja la fila/card cuyo menú se cerró. */
        $dropdown
            .closest('.empresa-detail-row, .empresa-mini-card')
            .removeClass('empresa-dropdown-open');
    });
}

/* =========================================================
   DIRECCIÓN ADAPTATIVA DEL DROPDOWN
   Prioridad: abajo -> arriba -> derecha -> izquierda.
   Bootstrap/Popper conserva el ajuste final contra viewport.
   ========================================================= */
function limpiarDireccionDropdownEmpresa($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    $dropdown.removeClass('dropup dropright dropleft');
    $dropdown.children('.dropdown-menu')
        .removeClass('dropdown-menu-right')
        .removeAttr('x-placement data-popper-placement')
        .css({ top: '', left: '', right: '', bottom: '', transform: '' });
}

function medirMenuDropdownEmpresa($menu) {
    var menu = $menu && $menu.length ? $menu[0] : null;
    if (!menu) {
        return { width: 200, height: 120 };
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

function prepararDireccionDropdownEmpresa($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    var $button = $dropdown.children('.js-acciones-toggle');
    var $menu = $dropdown.children('.dropdown-menu');
    var button = $button.length ? $button[0] : null;

    if (!button || !$menu.length) {
        return;
    }

    limpiarDireccionDropdownEmpresa($dropdown);

    var rect = button.getBoundingClientRect();
    var menuSize = medirMenuDropdownEmpresa($menu);
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var margin = 12;
    var gap = 8;

    var espacioAbajo = viewportHeight - rect.bottom - margin;
    var espacioArriba = rect.top - margin;
    var espacioDerecha = viewportWidth - rect.right - margin;
    var espacioIzquierda = rect.left - margin;

    var cabeAbajo = espacioAbajo >= menuSize.height + gap;
    var cabeArriba = espacioArriba >= menuSize.height + gap;
    var cabeDerecha = espacioDerecha >= menuSize.width + gap;
    var cabeIzquierda = espacioIzquierda >= menuSize.width + gap;

    if (cabeAbajo) {
        /* Dropdown normal. */
    } else if (cabeArriba) {
        $dropdown.addClass('dropup');
    } else if (cabeDerecha) {
        $dropdown.addClass('dropright');
    } else if (cabeIzquierda) {
        $dropdown.addClass('dropleft');
    } else if (espacioArriba > espacioAbajo) {
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

function inicializarStackDropdownEmpresa() {
    $(document)
        .off('click.empresaDropdownStack', '#empresaListado .js-acciones-toggle')
        .on('click.empresaDropdownStack', '#empresaListado .js-acciones-toggle', function (event) {
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

            cerrarDropdownsEmpresaExcepto($dropdown);

            if (estabaAbierto) {
                try { $button.dropdown('hide'); } catch (error) {}
                $button.attr('aria-expanded', 'false');
                $dropdown.removeClass('show');
                $menu.removeClass('show');
                limpiarDireccionDropdownEmpresa($dropdown);
                limpiarStackDropdownEmpresa();
                return;
            }

            try {
                prepararDireccionDropdownEmpresa($dropdown);

                $button.dropdown({
                    boundary: 'viewport',
                    flip: true,
                    offset: '0,6'
                });
                $button.dropdown('show');
                activarStackDropdownEmpresa($dropdown);
            } catch (error) {
                console.error('No se pudo abrir el dropdown de acciones de Empresa:', error);
            }
        })
        .off('shown.bs.dropdown.empresaDropdownStack', '#empresaListado .acciones-dropdown')
        .on('shown.bs.dropdown.empresaDropdownStack', '#empresaListado .acciones-dropdown', function () {
            cerrarDropdownsEmpresaExcepto($(this));
            activarStackDropdownEmpresa($(this));
        })
        .off('hidden.bs.dropdown.empresaDropdownStack', '#empresaListado .acciones-dropdown')
        .on('hidden.bs.dropdown.empresaDropdownStack', '#empresaListado .acciones-dropdown', function () {
            var $dropdown = $(this);
            limpiarDireccionDropdownEmpresa($dropdown);
            $dropdown.closest('.empresa-detail-row, .empresa-mini-card').removeClass('empresa-dropdown-open');
        });
}

function inicializarEmpresaUI() {
    var vistaGuardada = 'detalle';

    try {
        vistaGuardada = localStorage.getItem(EMPRESA_STORAGE_VISTA) || 'detalle';
    } catch (error) {
        vistaGuardada = 'detalle';
    }

    empresaState.vista = vistaGuardada === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    actualizarBotonesVistaEmpresa();
    sincronizarPageSizeEmpresa();

    configurarToggleEmpresa(
        '#btn_toggle_filtros_empresa',
        '#contenido_filtros_empresa',
        EMPRESA_STORAGE_FILTROS
    );

    configurarToggleEmpresa(
        '#btn_toggle_kpis_empresa',
        '#contenido_kpis_empresa',
        EMPRESA_STORAGE_KPIS
    );
}

function configurarToggleEmpresa(buttonSelector, contentSelector, storageKey) {
    var visible = true;

    try {
        var stored = localStorage.getItem(storageKey);

        if (stored !== null) {
            visible = stored === '1';
        }
    } catch (error) {
        visible = true;
    }

    function aplicar(guardar) {
        var $button = $(buttonSelector);
        var $content = $(contentSelector);

        $content.toggle(visible);

        $button
            .attr('aria-expanded', visible ? 'true' : 'false')
            .html(
                '<i class="fas fa-chevron-' +
                (visible ? 'up' : 'down') +
                ' mr-1"></i><span>' +
                (visible ? 'Ocultar' : 'Mostrar') +
                '</span>'
            );

        if (guardar) {
            try {
                localStorage.setItem(storageKey, visible ? '1' : '0');
            } catch (error) {
                console.warn('No se pudo guardar la preferencia visual:', error);
            }
        }
    }

    aplicar(false);

    $(document)
        .off('click.empresaToggle', buttonSelector)
        .on('click.empresaToggle', buttonSelector, function (event) {
            event.preventDefault();
            visible = !$(contentSelector).is(':visible');
            aplicar(true);
        });
}

function cambiarVistaEmpresa(vista) {
    empresaState.vista = vista === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    try {
        localStorage.setItem(EMPRESA_STORAGE_VISTA, empresaState.vista);
    } catch (error) {
        console.warn('No se pudo guardar la vista de empresas:', error);
    }

    actualizarBotonesVistaEmpresa();
    sincronizarPageSizeEmpresa();

    empresaState.pagina = 1;
    renderEmpresa();
}

function actualizarBotonesVistaEmpresa() {
    $('.empresa-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.empresa-view-btn[data-view="' + empresaState.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function sincronizarPageSizeEmpresa() {
    var $select = $('#empresa_page_size');
    var miniatura = empresaState.vista === 'miniatura';
    var opciones = miniatura
        ? [6, 12, 18, 30]
        : [10, 25, 50, 100];

    var seleccionado = miniatura
        ? empresaState.porPaginaMiniatura
        : empresaState.porPaginaDetalle;

    $select.empty();

    opciones.forEach(function (valor) {
        $select.append(
            $('<option></option>')
                .attr('value', valor)
                .text(valor)
        );
    });

    empresaState.porPagina = opciones.indexOf(seleccionado) !== -1
        ? seleccionado
        : opciones[0];

    $select.val(String(empresaState.porPagina));
}

function empresaValor(valor, textoDefault) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        return textoDefault || 'No registrado';
    }

    return String(valor).trim();
}

function empresaEscape(valor) {
    return empresaValor(valor, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function empresaTelefonoFormateado(valor) {
    valor = empresaValor(valor, '');

    if (valor === '') {
        return 'No registrado';
    }

    return valor;
}

function empresaFecha(valor) {
    valor = empresaValor(valor, '');

    if (valor === '') {
        return 'No registrada';
    }

    return valor;
}

function empresaUrlLimpia(url) {
    url = empresaValor(url, '');

    if (url === '') {
        return '';
    }

    return url;
}

function empresaEstadoBadge(estado) {
    if (parseInt(estado, 10) === 1) {
        return '' +
            '<span class="empresa-status-badge empresa-status-active">' +
                '<i class="fas fa-check-circle"></i> Activo' +
            '</span>';
    }

    return '' +
        '<span class="empresa-status-badge empresa-status-inactive">' +
            '<i class="fas fa-times-circle"></i> Inactivo' +
        '</span>';
}

function actualizarResumenEmpresa(rows) {
    rows = rows || [];

    var totalActivas = 0;
    var totalContacto = 0;
    var totalWeb = 0;
    var totalFirma = 0;

    rows.forEach(function (item) {
        if (parseInt(item.estado, 10) === 1) {
            totalActivas++;
        }

        if (
            empresaValor(item.telefono, '') !== '' ||
            empresaValor(item.celular, '') !== '' ||
            empresaValor(item.correo, '') !== ''
        ) {
            totalContacto++;
        }

        if (
            empresaValor(item.facebook, '') !== '' ||
            empresaValor(item.sitioweb, '') !== ''
        ) {
            totalWeb++;
        }

        if (parseInt(item.MostrarFirma, 10) === 1) {
            totalFirma++;
        }
    });

    $('#empresa_total_activas').text(totalActivas);
    $('#empresa_total_contacto').text(totalContacto);
    $('#empresa_total_web').text(totalWeb);
    $('#empresa_total_firma').text(totalFirma);
}

var listar_empresa = function () {
    var estado = $('#form_main_empresa #estado_empresa').val();

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableEmpresa.php',
        data: {
            estado: estado
        },
        cache: false,
        dataType: 'text',
        beforeSend: function () {
            $('#empresaListado').addClass('empresa-loading');
        },
        success: function (respuesta) {
            var json = respuesta;

            if (typeof respuesta === 'string') {
                try {
                    json = JSON.parse(respuesta);
                } catch (error) {
                    console.error('Respuesta inválida de llenarDataTableEmpresa.php:', respuesta);
                    showNotify(
                        'error',
                        'Respuesta inválida',
                        'El servidor no devolvió un JSON válido para el listado de empresas.'
                    );
                    return;
                }
            }

            if (json && json.sessionExpired) {
                showNotify(
                    'error',
                    'Sesión finalizada',
                    json.message || 'La sesión ha expirado.'
                );

                if (json.redirect) {
                    setTimeout(function () {
                        window.location.href = json.redirect;
                    }, 900);
                }

                return;
            }

            if (!json || json.ok === false) {
                showNotify(
                    'error',
                    'No se pudo cargar',
                    (json && json.message)
                        ? json.message
                        : 'No se pudo cargar el listado de empresas.'
                );
                return;
            }

            var rows = Array.isArray(json.data)
                ? json.data
                : [];

            empresaState.registros = rows;
            empresaState.pagina = 1;

            aplicarFiltrosEmpresa();
        },
        error: function (xhr) {
            var mensaje = 'No se pudo cargar el listado de empresas.';

            try {
                var jsonError = JSON.parse(xhr.responseText || '{}');
                mensaje = jsonError.message || mensaje;
            } catch (error) {
                if (xhr.responseText) {
                    console.error('Error de servidor en llenarDataTableEmpresa.php:', xhr.responseText);
                }
            }

            empresaState.registros = [];
            empresaState.filtrados = [];
            renderEmpresa();

            showNotify('error', 'Error', mensaje);
        },
        complete: function () {
            $('#empresaListado').removeClass('empresa-loading');
        }
    });
};

function aplicarFiltrosEmpresa() {
    var busquedaFormulario = String(
        $('#filtro_empresa_general').val() || ''
    ).trim().toLowerCase();

    var busquedaListado = String(
        empresaState.busqueda || ''
    ).trim().toLowerCase();

    empresaState.filtrados = empresaState.registros.filter(function (item) {
        var texto = [
            item.nombre,
            item.razon_social,
            item.eslogan,
            item.otra_informacion,
            item.telefono,
            item.celular,
            item.correo,
            item.rtn,
            item.ubicacion,
            item.facebook,
            item.sitioweb,
            item.horario,
            parseInt(item.estado, 10) === 1 ? 'activo' : 'inactivo',
            parseInt(item.MostrarFirma, 10) === 1 ? 'firma visible' : 'firma oculta'
        ].map(function (valor) {
            return empresaValor(valor, '').toLowerCase();
        }).join(' ');

        var coincideFormulario = busquedaFormulario === '' ||
            texto.indexOf(busquedaFormulario) !== -1;

        var coincideListado = busquedaListado === '' ||
            texto.indexOf(busquedaListado) !== -1;

        return coincideFormulario && coincideListado;
    });

    actualizarResumenEmpresa(empresaState.filtrados);
    renderEmpresa();
}

function renderEmpresa() {
    var total = empresaState.filtrados.length;
    var porPagina = empresaState.porPagina;
    var totalPaginas = Math.max(1, Math.ceil(total / porPagina));

    if (empresaState.pagina > totalPaginas) {
        empresaState.pagina = totalPaginas;
    }

    var inicio = (empresaState.pagina - 1) * porPagina;
    var fin = Math.min(inicio + porPagina, total);
    var registrosPagina = empresaState.filtrados.slice(inicio, fin);
    var html = '';
    var $listado = $('#empresaListado');

    $listado
        .toggleClass('vista-detalle', empresaState.vista === 'detalle')
        .toggleClass('vista-miniatura', empresaState.vista === 'miniatura');

    if (total > 0) {
        if (empresaState.vista === 'detalle') {
            html += construirHeaderEmpresa();

            registrosPagina.forEach(function (empresa) {
                html += construirFilaEmpresa(empresa);
            });
        } else {
            registrosPagina.forEach(function (empresa) {
                html += construirMiniaturaEmpresa(empresa);
            });
        }
    }

    $listado.html(html);
    $('#empresaVacio').toggle(total === 0);

    $('#empresaInfo').text(
        total > 0
            ? 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
            : 'Mostrando 0 registros'
    );

    renderPaginacionEmpresa(totalPaginas);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

function construirHeaderEmpresa() {
    return '' +
        '<div class="empresa-detail-header">' +
            '<div>Acciones</div>' +
            '<div>Empresa</div>' +
            '<div>Contacto</div>' +
            '<div>Información fiscal</div>' +
            '<div>Ubicación</div>' +
            '<div>Digital / Horario</div>' +
            '<div>Firma</div>' +
        '</div>';
}

function construirAccionesEmpresa() {
    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function construirFilaEmpresa(row) {
    var ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';
    var defaultLogoUrl = ENTERPRISE_URL + 'image_preview.png';
    var imageUrl = row.image ? (ENTERPRISE_URL + row.image) : defaultLogoUrl;
    var firmaUrl = row.firma_documento ? (ENTERPRISE_URL + row.firma_documento) : defaultLogoUrl;

    var nombre = empresaEscape(row.nombre);
    var razon = empresaEscape(empresaValor(row.razon_social, 'Sin razón social'));
    var telefono = empresaEscape(empresaTelefonoFormateado(row.telefono));
    var celular = empresaEscape(empresaTelefonoFormateado(row.celular));
    var correo = empresaEscape(empresaValor(row.correo, 'No registrado'));
    var rtn = empresaEscape(empresaValor(row.rtn, 'No registrado'));
    var fecha = empresaEscape(empresaFecha(row.fecha_registro));
    var ubicacion = empresaEscape(empresaValor(row.ubicacion, 'No registrada'));
    var facebook = empresaUrlLimpia(row.facebook);
    var sitioweb = empresaUrlLimpia(row.sitioweb);
    var horario = empresaEscape(empresaValor(row.horario, 'No registrado'));
    var mostrarFirma = parseInt(row.MostrarFirma, 10) === 1;

    return '' +
        '<article class="empresa-detail-row" data-id="' + empresaEscape(row.empresa_id) + '">' +
            '<div class="empresa-detail-cell empresa-detail-actions">' +
                construirAccionesEmpresa() +
            '</div>' +

            '<div class="empresa-detail-cell">' +
                '<div class="empresa-main-box">' +
                    '<a href="#" class="iv-trigger empresa-logo-box" ' +
                       'data-iv-src="' + empresaEscape(imageUrl) + '" ' +
                       'data-iv-fallback="' + empresaEscape(defaultLogoUrl) + '" ' +
                       'data-iv-title="' + nombre + '">' +
                        '<img class="empresa-logo-img table-image" src="' + empresaEscape(imageUrl) + '" alt="' + nombre + '">' +
                    '</a>' +
                    '<div class="empresa-main-info">' +
                        '<div class="empresa-title-row">' +
                            '<h6 class="empresa-nombre">' + nombre + '</h6>' +
                            empresaEstadoBadge(row.estado) +
                        '</div>' +
                        '<div class="empresa-razon">' + razon + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-detail-cell">' +
                '<div class="empresa-detail-list">' +
                    '<div><i class="fas fa-phone-alt"></i><span>' + telefono + '</span></div>' +
                    '<div><i class="fas fa-mobile-alt"></i><span>' + celular + '</span></div>' +
                    '<div><i class="fas fa-envelope"></i><span>' + correo + '</span></div>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-detail-cell">' +
                '<div class="empresa-detail-list">' +
                    '<div><i class="fas fa-id-card"></i><span><strong>RTN:</strong> ' + rtn + '</span></div>' +
                    '<div><i class="fas fa-calendar-alt"></i><span><strong>Registro:</strong> ' + fecha + '</span></div>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-detail-cell">' +
                '<div class="empresa-location-box">' +
                    '<i class="fas fa-map-marker-alt"></i>' +
                    '<span>' + ubicacion + '</span>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-detail-cell">' +
                '<div class="empresa-detail-list">' +
                    '<div><i class="fab fa-facebook-f"></i><span>' +
                        (facebook !== '' ? '<a href="' + empresaEscape(facebook) + '" target="_blank">Ver página</a>' : 'No registrado') +
                    '</span></div>' +
                    '<div><i class="fas fa-globe"></i><span>' +
                        (sitioweb !== '' ? '<a href="' + empresaEscape(sitioweb) + '" target="_blank">Abrir sitio</a>' : 'No registrado') +
                    '</span></div>' +
                    '<div><i class="fas fa-clock"></i><span>' + horario + '</span></div>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-detail-cell empresa-firma-cell">' +
                '<a href="#" class="iv-trigger empresa-firma-box" ' +
                   'data-iv-src="' + empresaEscape(firmaUrl) + '" ' +
                   'data-iv-fallback="' + empresaEscape(defaultLogoUrl) + '" ' +
                   'data-iv-title="Firma / Sello">' +
                    '<img class="empresa-firma-img" src="' + empresaEscape(firmaUrl) + '" alt="Firma">' +
                '</a>' +
                '<span class="empresa-firma-badge ' + (mostrarFirma ? 'firma-visible' : 'firma-oculta') + '">' +
                    '<i class="fas ' + (mostrarFirma ? 'fa-check-circle' : 'fa-times-circle') + ' mr-1"></i>' +
                    (mostrarFirma ? 'Visible' : 'Oculta') +
                '</span>' +
            '</div>' +
        '</article>';
}

function construirMiniaturaEmpresa(row) {
    var ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';
    var defaultLogoUrl = ENTERPRISE_URL + 'image_preview.png';
    var imageUrl = row.image ? (ENTERPRISE_URL + row.image) : defaultLogoUrl;

    var nombre = empresaEscape(row.nombre);
    var razon = empresaEscape(empresaValor(row.razon_social, 'Sin razón social'));
    var rtn = empresaEscape(empresaValor(row.rtn, 'No registrado'));
    var correo = empresaEscape(empresaValor(row.correo, 'No registrado'));
    var telefono = empresaEscape(empresaTelefonoFormateado(row.telefono));
    var ubicacion = empresaEscape(empresaValor(row.ubicacion, 'No registrada'));
    var mostrarFirma = parseInt(row.MostrarFirma, 10) === 1;

    return '' +
        '<article class="empresa-mini-card" data-id="' + empresaEscape(row.empresa_id) + '">' +
            '<div class="empresa-mini-topline"></div>' +

            '<div class="empresa-mini-header">' +
                '<a href="#" class="iv-trigger empresa-mini-logo" ' +
                   'data-iv-src="' + empresaEscape(imageUrl) + '" ' +
                   'data-iv-fallback="' + empresaEscape(defaultLogoUrl) + '" ' +
                   'data-iv-title="' + nombre + '">' +
                    '<img src="' + empresaEscape(imageUrl) + '" alt="' + nombre + '">' +
                '</a>' +

                '<div class="empresa-mini-identity">' +
                    '<div class="empresa-mini-title-row">' +
                        '<h4>' + nombre + '</h4>' +
                        empresaEstadoBadge(row.estado) +
                    '</div>' +
                    '<div class="empresa-mini-subtitle">' + razon + '</div>' +
                    '<div class="empresa-mini-rtn"><i class="fas fa-id-card mr-1"></i>RTN: ' + rtn + '</div>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-mini-body">' +
                '<div class="empresa-mini-field">' +
                    '<span><i class="fas fa-envelope"></i> Correo</span>' +
                    '<strong>' + correo + '</strong>' +
                '</div>' +
                '<div class="empresa-mini-field">' +
                    '<span><i class="fas fa-phone-alt"></i> Teléfono</span>' +
                    '<strong>' + telefono + '</strong>' +
                '</div>' +
                '<div class="empresa-mini-field empresa-mini-field-full">' +
                    '<span><i class="fas fa-map-marker-alt"></i> Ubicación</span>' +
                    '<strong>' + ubicacion + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="empresa-mini-footer">' +
                '<span class="empresa-firma-badge ' + (mostrarFirma ? 'firma-visible' : 'firma-oculta') + '">' +
                    '<i class="fas fa-file-signature mr-1"></i>' +
                    (mostrarFirma ? 'Firma visible' : 'Firma oculta') +
                '</span>' +
                construirAccionesEmpresa() +
            '</div>' +
        '</article>';
}

function renderPaginacionEmpresa(totalPaginas) {
    var pagina = empresaState.pagina;
    var html = '';

    html += crearBotonPaginacionEmpresa(
        'Inicio',
        'fas fa-angle-double-left',
        1,
        pagina <= 1
    );

    html += crearBotonPaginacionEmpresa(
        'Anterior',
        'fas fa-angle-left',
        Math.max(1, pagina - 1),
        pagina <= 1
    );

    var inicio = Math.max(1, pagina - 2);
    var fin = Math.min(totalPaginas, inicio + 4);

    if (fin - inicio < 4) {
        inicio = Math.max(1, fin - 4);
    }

    for (var i = inicio; i <= fin; i++) {
        html += '<button type="button" ' +
            'class="empresa-page-btn empresa-page-number ' + (i === pagina ? 'active' : '') + '" ' +
            'data-page="' + i + '">' +
            i +
        '</button>';
    }

    html += crearBotonPaginacionEmpresa(
        'Siguiente',
        'fas fa-angle-right',
        Math.min(totalPaginas, pagina + 1),
        pagina >= totalPaginas
    );

    html += crearBotonPaginacionEmpresa(
        'Final',
        'fas fa-angle-double-right',
        totalPaginas,
        pagina >= totalPaginas
    );

    $('#empresaPaginacion').html(html);

    $('#empresaPaginacion .empresa-page-btn')
        .off('click.empresaPage')
        .on('click.empresaPage', function () {
            if ($(this).prop('disabled')) {
                return;
            }

            empresaState.pagina = parseInt($(this).data('page'), 10) || 1;
            renderEmpresa();
        });
}

function crearBotonPaginacionEmpresa(texto, icono, pagina, disabled) {
    return '<button type="button" ' +
        'class="empresa-page-btn" ' +
        'data-page="' + pagina + '" ' +
        (disabled ? 'disabled' : '') + '>' +
        '<i class="' + icono + '"></i>' +
        '<span>' + texto + '</span>' +
    '</button>';
}

function obtenerEmpresaPorId(id) {
    id = String(id);

    for (var i = 0; i < empresaState.registros.length; i++) {
        if (String(empresaState.registros[i].empresa_id) === id) {
            return empresaState.registros[i];
        }
    }

    return null;
}

function editarEmpresaPorId(id) {
    var data = obtenerEmpresaPorId(id);

    if (!data) {
        showNotify('error', 'Error', 'No se encontró la empresa seleccionada.');
        return;
    }

    var url = '<?php echo SERVERURL;?>core/editarEmpresa.php';

    $('#formEmpresa #empresa_id').val(data.empresa_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formEmpresa').serialize(),
        success: function (registro) {
            var valores = eval(registro);

            $('#formEmpresa').attr('data-form', 'update');
            $('#formEmpresa').attr('action', '<?php echo SERVERURL;?>ajax/modificarEmpreasAjax.php');
            $('#formEmpresa')[0].reset();

            $('#reg_empresa').hide();
            $('#edi_empresa').show();
            $('#delete_empresa').hide();

            $('#formEmpresa #empresa_empresa').val(valores[0]);
            $('#formEmpresa #telefono_empresa').val(valores[1]);
            $('#formEmpresa #correo_empresa').val(valores[2]);
            $('#formEmpresa #rtn_empresa').val(valores[3]);
            $('#formEmpresa #direccion_empresa').val(valores[4]);
            $('#formEmpresa #empresa_razon_social').val(valores[6]);
            $('#formEmpresa #empresa_otra_informacion').val(valores[7]);
            $('#formEmpresa #empresa_eslogan').val(valores[8]);
            $('#formEmpresa #empresa_celular').val(valores[9]);
            $('#facebook_empresa').val(valores[10]);
            $('#sitioweb_empresa').val(valores[11]);
            $('#horario_empresa').val(valores[12]);

            $('#formEmpresa #empresa_activo').prop('checked', valores[5] == 1);

            if (valores[13] && valores[13] !== 'image_preview.png') {
                cargarImagenExistente('logo', valores[13]);
            } else {
                $('#logoPreview').html('').hide();
                $('#logoInfo').text('Ningún archivo seleccionado');
            }

            if (valores[14] && valores[14] !== '') {
                cargarImagenExistente('firma', valores[14]);
            } else {
                $('#firmaPreview').html('').hide();
                $('#firmaInfo').text('Ningún archivo seleccionado');
            }

            $('#formEmpresa #empresa_empresa').prop('readonly', false);
            $('#formEmpresa #rtn_empresa').prop('readonly', false);
            $('#formEmpresa #telefono_empresa').prop('readonly', false);
            $('#formEmpresa #correo_empresa').prop('readonly', false);
            $('#formEmpresa #direccion_empresa').prop('readonly', false);
            $('#formEmpresa #empresa_activo').prop('disabled', false);
            $('#formEmpresa #empresa_razon_social').prop('readonly', false);
            $('#formEmpresa #empresa_otra_informacion').prop('readonly', false);
            $('#formEmpresa #empresa_eslogan').prop('disabled', false);
            $('#formEmpresa #empresa_celular').prop('disabled', false);

            $('#formEmpresa #proceso_empresa').val('Editar');

            $('#modal_registrar_empresa').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        }
    });
}

function eliminarEmpresaPorId(id) {
    var data = obtenerEmpresaPorId(id);

    if (!data) {
        showNotify('error', 'Error', 'No se encontró la empresa seleccionada.');
        return;
    }

    var mensajeHTML =
        '<div style="text-align:left;">' +
            '<p style="margin-bottom:10px;">Esta acción eliminará permanentemente la empresa.</p>' +
            '<strong>Empresa:</strong> ' + empresaEscape(data.nombre) +
        '</div>';

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
                className: 'btn btn-secondary'
            },
            confirm: {
                text: 'Sí, eliminar',
                value: true,
                className: 'btn btn-danger',
                closeModal: false
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then(function (confirmar) {
        if (!confirmar) {
            return;
        }

        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>ajax/eliminarEmpresaAjax.php',
            data: {
                empresa_id: data.empresa_id
            },
            dataType: 'json',
            success: function (response) {
                swal.close();

                if (response && response.status === 'success') {
                    showNotify(
                        'success',
                        response.title || 'Eliminación exitosa',
                        response.message || 'Empresa eliminada correctamente'
                    );

                    listar_empresa();
                } else {
                    showNotify(
                        'error',
                        (response && response.title) || 'Error',
                        (response && response.message) || 'No se pudo eliminar la empresa'
                    );
                }
            },
            error: function (xhr) {
                swal.close();

                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Ocurrió un error al procesar la solicitud';

                showNotify('error', 'Error', msg);
            }
        });
    });
}

/* =========================================================
   REPORTES
   ========================================================= */

function empresaRowsExportar() {
    return empresaState.filtrados.map(function (row) {
        return {
            nombre: empresaValor(row.nombre, 'No registrado'),
            razon: empresaValor(row.razon_social, 'No registrado'),
            rtn: empresaValor(row.rtn, 'No registrado'),
            telefono: empresaValor(row.telefono, 'No registrado'),
            celular: empresaValor(row.celular, 'No registrado'),
            correo: empresaValor(row.correo, 'No registrado'),
            ubicacion: empresaValor(row.ubicacion, 'No registrada'),
            web: empresaValor(row.sitioweb, 'No registrado'),
            facebook: empresaValor(row.facebook, 'No registrado'),
            horario: empresaValor(row.horario, 'No registrado'),
            firma: parseInt(row.MostrarFirma, 10) === 1 ? 'Visible' : 'Oculta',
            estado: parseInt(row.estado, 10) === 1 ? 'Activo' : 'Inactivo'
        };
    });
}

function empresaDescargarBlob(blob, nombre) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');

    a.href = url;
    a.download = nombre;

    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(function () {
        URL.revokeObjectURL(url);
    }, 1000);
}

function empresaXmlEscape(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function empresaExcelCol(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function empresaExcelCell(ref, value, styleId) {
    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t>' + empresaXmlEscape(value) + '</t></is>' +
    '</c>';
}

function exportarEmpresaExcelPremium() {
    var rows = empresaRowsExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay empresas para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        showNotify(
            'error',
            'Excel no disponible',
            'No se encontró JSZip para generar el archivo XLSX.'
        );
        return;
    }

    var headers = [
        'Empresa',
        'Razón Social',
        'RTN',
        'Teléfono',
        'Celular',
        'Correo',
        'Ubicación',
        'Sitio Web',
        'Facebook',
        'Horario',
        'Firma',
        'Estado'
    ];

    var data = rows.map(function (row) {
        return [
            row.nombre,
            row.razon,
            row.rtn,
            row.telefono,
            row.celular,
            row.correo,
            row.ubicacion,
            row.web,
            row.facebook,
            row.horario,
            row.firma,
            row.estado
        ];
    });

    var sheetRows = [];
    var headerRow = 7;

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            empresaExcelCell('A1', 'IZZY • REPORTE DE EMPRESAS', 1) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            empresaExcelCell(
                'A2',
                'Directorio empresarial y configuración fiscal • Generado: ' +
                new Date().toLocaleDateString('es-HN'),
                2
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3">' +
            empresaExcelCell('A3', 'REGISTROS', 6) +
            empresaExcelCell('D3', 'ACTIVAS', 6) +
            empresaExcelCell('G3', 'CON CONTACTO', 6) +
            empresaExcelCell('J3', 'FIRMA VISIBLE', 6) +
        '</row>'
    );

    var activas = rows.filter(function (r) { return r.estado === 'Activo'; }).length;
    var contacto = rows.filter(function (r) {
        return r.telefono !== 'No registrado' ||
               r.celular !== 'No registrado' ||
               r.correo !== 'No registrado';
    }).length;
    var firma = rows.filter(function (r) { return r.firma === 'Visible'; }).length;

    sheetRows.push(
        '<row r="4">' +
            empresaExcelCell('A4', rows.length, 7) +
            empresaExcelCell('D4', activas, 7) +
            empresaExcelCell('G4', contacto, 7) +
            empresaExcelCell('J4', firma, 7) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');
    sheetRows.push(
        '<row r="6">' +
            empresaExcelCell('A6', 'Detalle de empresas filtradas', 8) +
        '</row>'
    );

    var headerCells = headers.map(function (h, i) {
        return empresaExcelCell(empresaExcelCol(i) + headerRow, h, 3);
    }).join('');

    sheetRows.push('<row r="' + headerRow + '">' + headerCells + '</row>');

    data.forEach(function (row, rowIndex) {
        var excelRow = 8 + rowIndex;

        var cells = row.map(function (value, colIndex) {
            var style = 4;

            if (colIndex === 10 || colIndex === 11) {
                style = value === 'Visible' || value === 'Activo' ? 9 : 10;
            }

            return empresaExcelCell(
                empresaExcelCol(colIndex) + excelRow,
                value,
                style
            );
        }).join('');

        sheetRows.push('<row r="' + excelRow + '" ht="38" customHeight="1">' + cells + '</row>');
    });

    var lastRow = Math.max(headerRow, headerRow + data.length);

    var sheetXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:L' + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="2" width="28" customWidth="1"/>' +
                '<col min="3" max="3" width="19" customWidth="1"/>' +
                '<col min="4" max="5" width="16" customWidth="1"/>' +
                '<col min="6" max="6" width="26" customWidth="1"/>' +
                '<col min="7" max="7" width="32" customWidth="1"/>' +
                '<col min="8" max="9" width="25" customWidth="1"/>' +
                '<col min="10" max="10" width="22" customWidth="1"/>' +
                '<col min="11" max="12" width="14" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A7:L' + lastRow + '"/>' +
            '<mergeCells count="10">' +
                '<mergeCell ref="A1:L1"/>' +
                '<mergeCell ref="A2:L2"/>' +
                '<mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/>' +
                '<mergeCell ref="D3:F3"/><mergeCell ref="D4:F4"/>' +
                '<mergeCell ref="G3:I3"/><mergeCell ref="G4:I4"/>' +
                '<mergeCell ref="J3:L3"/><mergeCell ref="J4:L4"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<fonts count="7">' +
                '<font><sz val="10"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="7">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill>' +
            '</fills>' +
            '<borders count="2">' +
                '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                '<border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="11">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Empresas" sheetId="1" r:id="rId1"/></sheets>' +
        '</workbook>';

    var workbookRels =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
        '</Relationships>';

    var rootRels =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
        '</Relationships>';

    var contentTypes =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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

    var opciones = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promesa = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(opciones)
        : Promise.resolve(zip.generate(opciones));

    promesa.then(function (blob) {
        empresaDescargarBlob(blob, 'Reporte_Empresas.xlsx');
    }).catch(function (error) {
        console.error(error);
        showNotify('error', 'Error', 'No se pudo generar el archivo Excel.');
    });
}

function empresaPdfDato(label, value, options) {
    options = options || {};

    return {
        stack: [
            {
                text: String(label || '').toUpperCase(),
                fontSize: 6.8,
                bold: true,
                color: '#6B778C',
                margin: [0, 0, 0, 2]
            },
            {
                text: String(value || '—'),
                fontSize: options.fontSize || 8,
                bold: options.bold !== false,
                color: options.color || '#172B4D'
            }
        ]
    };
}

function empresaPdfTarjeta(row) {
    var estadoColor = row.estado === 'Activo'
        ? '#14804A'
        : '#C9372C';

    var firmaColor = row.firma === 'Visible'
        ? '#14804A'
        : '#6B778C';

    return {
        table: {
            widths: ['*'],
            body: [[{
                margin: [10, 9, 10, 9],
                stack: [
                    {
                        columns: [
                            {
                                width: '*',
                                stack: [
                                    {
                                        text: row.nombre,
                                        bold: true,
                                        fontSize: 10.5,
                                        color: '#172B4D'
                                    },
                                    {
                                        text: row.razon,
                                        fontSize: 7.2,
                                        color: '#6B778C',
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            },
                            {
                                width: 62,
                                alignment: 'right',
                                stack: [
                                    {
                                        text: row.estado,
                                        bold: true,
                                        fontSize: 7.2,
                                        color: estadoColor
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        canvas: [{
                            type: 'line',
                            x1: 0,
                            y1: 0,
                            x2: 330,
                            y2: 0,
                            lineWidth: 0.6,
                            lineColor: '#DDE3EA'
                        }],
                        margin: [0, 7, 0, 7]
                    },
                    {
                        columns: [
                            {
                                width: '33%',
                                stack: [
                                    empresaPdfDato('RTN', row.rtn),
                                    {
                                        margin: [0, 7, 0, 0],
                                        stack: [empresaPdfDato('Teléfono', row.telefono)]
                                    }
                                ]
                            },
                            {
                                width: '34%',
                                stack: [
                                    empresaPdfDato('Correo', row.correo, {
                                        fontSize: 7.4
                                    }),
                                    {
                                        margin: [0, 7, 0, 0],
                                        stack: [empresaPdfDato('Ubicación', row.ubicacion, {
                                            fontSize: 7.2
                                        })]
                                    }
                                ]
                            },
                            {
                                width: '33%',
                                stack: [
                                    empresaPdfDato('Firma', row.firma, {
                                        color: firmaColor
                                    }),
                                    {
                                        margin: [0, 7, 0, 0],
                                        stack: [empresaPdfDato('Horario', row.horario, {
                                            fontSize: 7.2
                                        })]
                                    }
                                ]
                            }
                        ],
                        columnGap: 8
                    }
                ]
            }]]
        },
        layout: {
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return 0.7; },
            vLineWidth: function () { return 0.7; }
        }
    };
}


function empresaPdfDetalle(rows) {
    var body = [[
        {text: 'Empresa', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'RTN', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'Contacto', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'Correo', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'Ubicación', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'Firma', bold: true, color: '#FFFFFF', fillColor: '#17324D'},
        {text: 'Estado', bold: true, color: '#FFFFFF', fillColor: '#17324D'}
    ]];

    rows.forEach(function (row, index) {
        var fill = index % 2 === 0 ? '#F7F9FC' : '#FFFFFF';
        body.push([
            {text: row.nombre || 'No registrado', fillColor: fill},
            {text: row.rtn || 'No registrado', fillColor: fill},
            {text: ((row.telefono || 'No registrado') + '\n' + (row.celular || 'No registrado')), fillColor: fill},
            {text: row.correo || 'No registrado', fillColor: fill},
            {text: row.ubicacion || 'No registrada', fillColor: fill},
            {text: row.firma || 'Oculta', fillColor: fill, color: row.firma === 'Visible' ? '#14804A' : '#6B778C', bold: true},
            {text: row.estado || 'Inactivo', fillColor: fill, color: row.estado === 'Activo' ? '#14804A' : '#C9372C', bold: true}
        ]);
    });

    return {
        table: {
            headerRows: 1,
            widths: [105, 76, 82, 125, '*', 52, 52],
            body: body
        },
        layout: {
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return 0.6; },
            vLineWidth: function () { return 0.6; },
            paddingLeft: function () { return 5; },
            paddingRight: function () { return 5; },
            paddingTop: function () { return 5; },
            paddingBottom: function () { return 5; }
        },
        fontSize: 7.1
    };
}

function previsualizarEmpresaPdfPremium() {
    var rows = empresaRowsExportar();

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay empresas para exportar.');
        return;
    }

    if (typeof pdfMake === 'undefined') {
        showNotify('error', 'PDF no disponible', 'No se encontró pdfMake.');
        return;
    }

    if (typeof abrirModalPdfPublico !== 'function') {
        showNotify('error', 'Visor no disponible', 'No se encontró el modal PDF público.');
        return;
    }

    var activas = rows.filter(function (row) { return row.estado === 'Activo'; }).length;
    var contacto = rows.filter(function (row) {
        return row.telefono !== 'No registrado' || row.celular !== 'No registrado' || row.correo !== 'No registrado';
    }).length;
    var firmaVisible = rows.filter(function (row) { return row.firma === 'Visible'; }).length;

    var filtroEstado = $('#estado_empresa option:selected').text() || 'Todos';
    var busqueda = String($('#filtro_empresa_general').val() || $('#buscar_empresa_listado').val() || '').trim();
    var logo = (typeof imagen !== 'undefined' && imagen)
        ? {image: imagen, width: 50, height: 24, alignment: 'center', margin: [0,1,0,0]}
        : {text:'IZZY', fontSize:16, bold:true, color:'#FFFFFF', alignment:'center', margin:[0,4,0,0]};

    var encabezado = {
        table:{widths:[70,'*',150],body:[[
            {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[logo]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[
                {text:'REPORTE DE EMPRESAS',fontSize:16,bold:true,color:'#FFFFFF'},
                {text:'Directorio empresarial y configuración fiscal',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}
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

    var filtros = {
        table:{widths:['*'],body:[[{text:'Filtros aplicados: Estado: '+filtroEstado+'   |   Búsqueda: '+(busqueda || 'Sin búsqueda'),fontSize:6.8,color:'#52627A',margin:[10,7,10,7],fillColor:'#F7F9FC'}]]},
        layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},
        margin:[0,0,0,10]
    };

    var resumen = {
        table:{widths:['*','*','*','*'],body:[[
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'REGISTROS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ACTIVAS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(activas),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'CON CONTACTO',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(contacto),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'FIRMA VISIBLE',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(firmaVisible),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]}
        ]]},
        layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return 0.6;},vLineWidth:function(){return 0.6;}},
        margin:[0,0,0,12]
    };

    var contenidoVista;
    if (empresaState.vista === 'miniatura') {
        contenidoVista = [];
        for (var i=0;i<rows.length;i+=2) {
            contenidoVista.push({
                columns:[
                    {width:'*',stack:[empresaPdfTarjeta(rows[i])]},
                    {width:10,text:''},
                    rows[i+1]?{width:'*',stack:[empresaPdfTarjeta(rows[i+1])]}:{width:'*',text:''}
                ],
                margin:[0,0,0,9]
            });
        }
    } else {
        contenidoVista = [empresaPdfDetalle(rows)];
    }

    var docDefinition = {
        pageSize:'LETTER',
        pageOrientation:'landscape',
        pageMargins:[28,28,28,34],
        header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},
        footer:function(currentPage,pageCount){return{margin:[28,8,28,0],columns:[
            {text:'IZZY • Empresas',fontSize:7,color:'#7A869A'},
            {text:'Página '+currentPage+' de '+pageCount,fontSize:7,color:'#7A869A',alignment:'right'}
        ]};},
        content:[encabezado,filtros,resumen,{text:empresaState.vista==='miniatura'?'VISTA MINIATURA':'VISTA DETALLE',fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]}].concat(contenidoVista),
        defaultStyle:{fontSize:8,color:'#253858'}
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre='Reporte_Empresas.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function(dataUrl){abrirModalPdfPublico(dataUrl,'Reporte de Empresas',nombre);});
        return;
    }
    if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function(base64){abrirModalPdfPublico('data:application/pdf;base64,'+base64,'Reporte de Empresas',nombre);});
        return;
    }
    showNotify('error','PDF no disponible','La versión de pdfMake no permite previsualización compatible.');
}

/* =========================================================
   MODAL EMPRESA
   ========================================================= */
function modal_empresa() {
    $('#formEmpresa').attr({
        'data-form': 'save'
    });

    $('#formEmpresa').attr({
        'action': '<?php echo SERVERURL;?>ajax/agregarEmpresaAjax.php'
    });

    $('#formEmpresa')[0].reset();

    $('#reg_empresa').show();
    $('#edi_empresa').hide();
    $('#delete_empresa').hide();

    CleanEnterpriseImage();

    $('#formEmpresa #empresa_empresa').attr('readonly', false);
    $('#formEmpresa #rtn_empresa').attr('readonly', false);
    $('#formEmpresa #telefono_empresa').attr('readonly', false);
    $('#formEmpresa #correo_empresa').attr('readonly', false);
    $('#formEmpresa #direccion_empresa').attr('readonly', false);
    $('#formEmpresa #empresa_activo').attr('disabled', false);
    $('#formEmpresa #empresa_razon_social').attr('readonly', false);
    $('#formEmpresa #empresa_otra_informacion').attr('readonly', false);
    $('#formEmpresa #empresa_eslogan').attr('disabled', false);
    $('#formEmpresa #empresa_celular').attr('disabled', false);

    $('#formEmpresa #proceso_empresa').val("Registro");

    $('#modal_registrar_empresa').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cargarImagenExistente(tipo, rutaImagen) {
    const ENTERPRISE_URL = '<?php echo rtrim(SERVERURL, "/") . ENTERPRISE_PATH; ?>';

    const preview = tipo === 'logo' ? $('#logoPreview') : $('#firmaPreview');
    const info = tipo === 'logo' ? $('#logoInfo') : $('#firmaInfo');
    const input = tipo === 'logo' ? $('#logotipo') : $('#firma_documento');

    if (rutaImagen && rutaImagen !== 'image_preview.png' && rutaImagen !== '') {
        const rutaCompleta = ENTERPRISE_URL + rutaImagen;

        preview.html(`
            <div style="position: relative; display: inline-block;">
                <img src="${rutaCompleta}" alt="Imagen existente" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                <button type="button" class="btn-remove-image" title="Eliminar imagen" style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.7); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).show();

        info.text(rutaImagen);

        preview.find('.btn-remove-image').on('click', function (e) {
            e.stopPropagation();
            preview.html('').hide();
            info.text('Ningún archivo seleccionado');
            input.val('');
        });
    } else {
        preview.html('').hide();
        info.text('Ningún archivo seleccionado');
    }
}

$(document).ready(function () {
    $("#modal_registrar_empresa").on('shown.bs.modal', function () {
        $(this).find('#formEmpresa #empresa_razon_social').focus();
    });
});

$('#formEmpresa #label_empresa_activo').html("Activo");

$('#formEmpresa .switch').change(function () {
    if ($('input[name=empresa_activo]').is(':checked')) {
        $('#formEmpresa #label_empresa_activo').html("Activo");
        return true;
    } else {
        $('#formEmpresa #label_empresa_activo').html("Inactivo");
        return false;
    }
});

$('#toggle-firma').on('click', function (e) {
    e.preventDefault();

    const $toggleButton = $(this);
    const estado = $toggleButton.text().includes('Ocultar Firma') ? 0 : 1;

    $.ajax({
        url: '<?php echo SERVERURL;?>core/SaveEstadoFirma.php',
        type: 'POST',
        data: {
            estado: estado
        },
        success: function (response) {
            try {
                const jsonResponse = JSON.parse(response);

                showNotify(jsonResponse.type, jsonResponse.title, jsonResponse.text);

                GetEstadoBotonFirma();
            } catch (error) {
                console.error('Error al analizar la respuesta JSON:', error);
            }
        },
        error: function () {
            $('.RespuestaAjax').html(
                '<p class="text-center text-danger">Hubo un problema al procesar la solicitud. Por favor, inténtelo de nuevo.</p>'
            );
        }
    });
});

function GetEstadoBotonFirma() {
    $.ajax({
        url: '<?php echo SERVERURL;?>core/GetEstadoBotonFirma.php',
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                console.error('Error al obtener el estado de la firma:', response.error);
                return;
            }

            const isFirmaVisible = response.estado === 'visible';
            const $toggleButton = $('#toggle-firma');

            if (isFirmaVisible) {
                $toggleButton.html('<i class="fas fa-eye-slash"></i> Ocultar Firma');
            } else {
                $toggleButton.html('<i class="fas fa-eye"></i> Mostrar Firma');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error al obtener el estado de la firma:', error);
        }
    });
}

/* Inicialización única del módulo Empresa. */
$(function () {
    inicializarEmpresaModulo();
});
</script>