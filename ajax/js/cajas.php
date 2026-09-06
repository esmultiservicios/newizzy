<script>
// caja.js - LISTADO MODERNIZADO SIN DATATABLE
var cajasState = {
    registros: [],
    filtrados: [],
    pagina: 1,
    porPagina: 10,
    porPaginaDetalle: 10,
    porPaginaMiniatura: 6,
    vista: 'detalle',
    busqueda: '',
    loading: false
};

var CAJAS_STORAGE_VISTA = 'izzy.cajas.tipo_vista';
var CAJAS_STORAGE_FILTROS = 'izzy.cajas.filtros.visible';
var CAJAS_STORAGE_KPIS = 'izzy.cajas.kpis.visible';

$(() => {
    inicializarCajasUI();
    inicializarDropdownAccionesCajas();

    $("#formMainCajas #estado_cajas").val(0);

    if ($.fn.selectpicker) {
        $('#formMainCajas #estado_cajas').selectpicker('refresh');
    }

    listar_registro_cajas();

    $('#formMainCajas')
        .off('submit.cajas')
        .on('submit.cajas', function (e) {
            e.preventDefault();
            cajasState.pagina = 1;
            listar_registro_cajas();
        });

    $('#formMainCajas')
        .off('reset.cajas')
        .on('reset.cajas', function () {
            setTimeout(function () {
                $("#formMainCajas #estado_cajas").val(0);

                if ($.fn.selectpicker) {
                    $('#formMainCajas #estado_cajas')
                        .selectpicker('val', '0')
                        .selectpicker('refresh');
                }

                var hoy = new Date().toISOString().split('T')[0];
                $("#formMainCajas #fecha_cajas").val(hoy);
                $("#formMainCajas #fecha_cajas_f").val(hoy);

                cajasState.busqueda = '';
                $('#buscarCajas').val('');
                cajasState.pagina = 1;

                listar_registro_cajas();
            }, 80);
        });

    $('#formMainCajas #estado_cajas, #formMainCajas #fecha_cajas, #formMainCajas #fecha_cajas_f')
        .off('change.cajas')
        .on('change.cajas', function () {
            cajasState.pagina = 1;
            listar_registro_cajas();
        });

    $('#btnGananciaPeriodo')
        .off('click.cajas')
        .on('click.cajas', function () {
            cargarDesgloseGananciaCaja(0, 'periodo');
        });

    $('#btnRetirosPeriodo')
        .off('click.cajas')
        .on('click.cajas', function () {
            cargarDetalleRetirosCaja(0, 'periodo');
        });

    $('#btnCuadreDia')
        .off('click.cajas')
        .on('click.cajas', function () {
            cargarCuadreDiaCaja(0, 'periodo');
        });

    $('#btnActualizarCuadreDia')
        .off('click.cajas')
        .on('click.cajas', function () {
            refrescarCuadreDiaCaja();
        });

    $('#btnImprimirCuadreDia')
        .off('click.cajas')
        .on('click.cajas', function () {
            imprimirCuadreDiaCaja();
        });

    $('#btnActualizarCajas')
        .off('click.cajas')
        .on('click.cajas', listar_registro_cajas);

    $('#btnExcelCajas')
        .off('click.cajas')
        .on('click.cajas', exportarCajasExcelPremium);

    $('#btnPdfCajas')
        .off('click.cajas')
        .on('click.cajas', previsualizarCajasPdfPremium);

    $('#buscarCajas')
        .off('input.cajas')
        .on('input.cajas', function () {
            cajasState.busqueda = String($(this).val() || '').trim().toLowerCase();
            cajasState.pagina = 1;
            aplicarFiltroCajas();
        });

    $('#cajasPageSize')
        .off('change.cajas')
        .on('change.cajas', function () {
            var valor = parseInt($(this).val(), 10);

            cajasState.porPagina = isNaN(valor) || valor <= 0
                ? (cajasState.vista === 'miniatura' ? 6 : 10)
                : valor;

            if (cajasState.vista === 'miniatura') {
                cajasState.porPaginaMiniatura = cajasState.porPagina;
            } else {
                cajasState.porPaginaDetalle = cajasState.porPagina;
            }

            cajasState.pagina = 1;
            renderCajas();
        });

    $('.cajas-view-btn')
        .off('click.cajasVista')
        .on('click.cajasVista', function () {
            cambiarVistaCajas($(this).data('view'));
        });

    comprobante_cajas_dataTable();
    cerrar_registro_cajas_dataTable();
    desglose_ganancia_caja_dataTable();
    retiro_caja_dataTable();
    detalle_retiros_caja_dataTable();
    cuadre_dia_caja_dataTable();
});

/* =========================================================
   UTILIDADES
   ========================================================= */
function parseMonto(valor) {
    if (typeof valor === 'string') {
        valor = valor.replace(/L\./g, '').replace(/,/g, '').trim();
    }

    valor = parseFloat(valor || 0);
    return isNaN(valor) ? 0 : valor;
}

function formatoMoneda(valor) {
    valor = parseMonto(valor);

    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function esCajaActiva(row) {
    return row && (
        parseInt(row.estado, 10) === 1 ||
        String(row.caja || '').toLowerCase() === 'activa'
    );
}

function cajaEscape(valor) {
    return String(valor === null || typeof valor === 'undefined' ? '' : valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cajaValor(valor, defecto) {
    if (valor === null || typeof valor === 'undefined' || String(valor).trim() === '') {
        return defecto || 'No registrado';
    }

    return String(valor).trim();
}

function cajaNormalizar(valor) {
    return String(valor || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

/* =========================================================
   PERSISTENCIA / VISTAS
   ========================================================= */
function inicializarCajasUI() {
    configurarToggleCajas(
        '#btnToggleFiltrosCajas',
        '#cajasFiltrosContenido',
        CAJAS_STORAGE_FILTROS
    );

    configurarToggleCajas(
        '#btnToggleKpisCajas',
        '#cajasKpisContenido',
        CAJAS_STORAGE_KPIS
    );

    try {
        cajasState.vista = localStorage.getItem(CAJAS_STORAGE_VISTA) === 'miniatura'
            ? 'miniatura'
            : 'detalle';
    } catch (error) {
        cajasState.vista = 'detalle';
    }

    actualizarBotonesVistaCajas();
    sincronizarPageSizeCajas();
}

function configurarToggleCajas(buttonSelector, contentSelector, storageKey) {
    var $button = $(buttonSelector);
    var $content = $(contentSelector);

    if (!$button.length || !$content.length) {
        return;
    }

    var visible = true;

    try {
        var stored = localStorage.getItem(storageKey);

        if (stored !== null) {
            visible = stored === '1';
        }
    } catch (error) {
        visible = true;
    }

    function aplicarEstado(guardar) {
        $content.toggle(visible);

        $button.attr('aria-expanded', visible ? 'true' : 'false');
        $button.find('span').text(visible ? 'Ocultar' : 'Mostrar');
        $button.find('i')
            .toggleClass('fa-chevron-up', visible)
            .toggleClass('fa-chevron-down', !visible);

        if (guardar) {
            try {
                localStorage.setItem(storageKey, visible ? '1' : '0');
            } catch (error) {
                console.warn('No se pudo guardar el estado de la sección:', error);
            }
        }
    }

    aplicarEstado(false);

    $button
        .off('click.cajasToggle')
        .on('click.cajasToggle', function () {
            visible = !visible;

            $content.stop(true, true)[visible ? 'slideDown' : 'slideUp'](180);
            aplicarEstado(true);
        });
}

function cambiarVistaCajas(vista) {
    cajasState.vista = vista === 'miniatura' ? 'miniatura' : 'detalle';

    try {
        localStorage.setItem(CAJAS_STORAGE_VISTA, cajasState.vista);
    } catch (error) {
        console.warn('No se pudo guardar la vista de cajas:', error);
    }

    actualizarBotonesVistaCajas();
    sincronizarPageSizeCajas();
    cajasState.pagina = 1;
    renderCajas();
}

function actualizarBotonesVistaCajas() {
    $('.cajas-view-btn')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.cajas-view-btn[data-view="' + cajasState.vista + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function sincronizarPageSizeCajas() {
    var miniatura = cajasState.vista === 'miniatura';
    var opciones = miniatura
        ? [6, 12, 18, 30]
        : [10, 25, 50, 100];

    var seleccionado = miniatura
        ? cajasState.porPaginaMiniatura
        : cajasState.porPaginaDetalle;

    var $select = $('#cajasPageSize');
    $select.empty();

    opciones.forEach(function (valor) {
        $select.append(
            $('<option></option>')
                .attr('value', valor)
                .text(valor)
        );
    });

    cajasState.porPagina = opciones.indexOf(seleccionado) !== -1
        ? seleccionado
        : opciones[0];

    $select.val(String(cajasState.porPagina));
}

/* =========================================================
   CARGA / FILTRO / KPIs
   ========================================================= */
var listar_registro_cajas = function () {
    if (cajasState.loading) {
        return;
    }

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();
    var estado = $("#formMainCajas #estado_cajas").val();

    cajasState.loading = true;

    $.ajax({
        method: 'POST',
        url: '<?php echo SERVERURL;?>core/llenarDataTableCajaDisponibles.php',
        dataType: 'json',
        data: {
            fechai: fechai,
            fechaf: fechaf,
            estado: estado
        },
        success: function (response) {
            cajasState.registros = response && Array.isArray(response.data)
                ? response.data
                : [];

            cajasState.pagina = 1;
            aplicarFiltroCajas();
        },
        error: function (xhr) {
            console.error('Error al cargar cajas:', xhr.responseText);

            cajasState.registros = [];
            cajasState.filtrados = [];
            actualizarKpisCajas();
            renderCajas();

            if (typeof showNotify === 'function') {
                showNotify(
                    'error',
                    'Error',
                    'No se pudo cargar el registro de cajas.'
                );
            }
        },
        complete: function () {
            cajasState.loading = false;
        }
    });
};

function aplicarFiltroCajas() {
    var busqueda = cajaNormalizar(cajasState.busqueda);

    cajasState.filtrados = cajasState.registros.filter(function (row) {
        if (!busqueda) {
            return true;
        }

        var texto = cajaNormalizar([
            row.apertura_id,
            row.fecha,
            row.usuario,
            row.factura_inicial,
            row.factura_final,
            row.caja,
            esCajaActiva(row) ? 'abierta activa' : 'cerrada inactiva',
            formatoMoneda(row.monto_apertura),
            formatoMoneda(row.importe_venta),
            formatoMoneda(row.retiro_caja),
            formatoMoneda(row.neto)
        ].join(' '));

        return texto.indexOf(busqueda) !== -1;
    });

    actualizarKpisCajas();
    renderCajas();
}

function actualizarKpisCajas() {
    var rows = cajasState.filtrados || [];
    var abiertas = 0;
    var ventas = 0;
    var neto = 0;

    rows.forEach(function (row) {
        if (esCajaActiva(row)) {
            abiertas++;
        }

        ventas += parseMonto(row.importe_venta);
        neto += parseMonto(row.neto);
    });

    $('#cajasKpiRegistros').text(rows.length);
    $('#cajasKpiAbiertas').text(abiertas);
    $('#cajasKpiVentas').text(formatoMoneda(ventas));
    $('#cajasKpiNeto').text(formatoMoneda(neto));
}

/* =========================================================
   RENDER DIVs
   ========================================================= */
function renderCajas() {
    var total = cajasState.filtrados.length;
    var totalPaginas = Math.max(1, Math.ceil(total / cajasState.porPagina));

    if (cajasState.pagina > totalPaginas) {
        cajasState.pagina = totalPaginas;
    }

    var inicio = (cajasState.pagina - 1) * cajasState.porPagina;
    var fin = Math.min(inicio + cajasState.porPagina, total);
    var paginaRows = cajasState.filtrados.slice(inicio, fin);
    var html = '';

    var $listado = $('#cajasListado');

    $listado
        .toggleClass('vista-detalle', cajasState.vista === 'detalle')
        .toggleClass('vista-miniatura', cajasState.vista === 'miniatura');

    if (cajasState.vista === 'detalle' && total > 0) {
        html += construirHeaderCajasDetalle();
    }

    paginaRows.forEach(function (row) {
        html += cajasState.vista === 'miniatura'
            ? construirMiniaturaCaja(row)
            : construirFilaCajaDetalle(row);
    });

    $listado.html(html);
    $('#cajasVacio').toggle(total === 0);

    $('#cajasInfo').text(
        total > 0
            ? 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + total + ' registros'
            : 'Mostrando 0 registros'
    );

    renderPaginacionCajas(totalPaginas);

    if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
        typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }

    if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
    }
}

function construirHeaderCajasDetalle() {
    return '' +
        '<div class="cajas-detail-header">' +
            '<div>Acciones</div>' +
            '<div>Fecha / Estado</div>' +
            '<div>Usuario</div>' +
            '<div>Facturación</div>' +
            '<div>Apertura</div>' +
            '<div>Venta del día</div>' +
            '<div>Retiros</div>' +
            '<div>Neto caja</div>' +
        '</div>';
}

function construirAccionesCaja(row) {
    var activa = esCajaActiva(row);
    var acciones = '';

    if (activa) {
        acciones +=
            '<button type="button" class="dropdown-item accion-item accion-cerrar table_crear table_cerrar_caja">' +
                '<span class="accion-icon accion-icon-success"><i class="fas fa-lock"></i></span>' +
                '<span class="accion-label">Cerrar caja</span>' +
            '</button>';

        acciones +=
            '<button type="button" class="dropdown-item accion-item accion-retiro table_retiro_caja">' +
                '<span class="accion-icon accion-icon-warning"><i class="fas fa-money-bill-wave"></i></span>' +
                '<span class="accion-label">Retirar dinero</span>' +
            '</button>';
    } else {
        acciones +=
            '<button type="button" class="dropdown-item accion-item accion-cerrada" disabled>' +
                '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-lock"></i></span>' +
                '<span class="accion-label">Caja cerrada</span>' +
            '</button>';

        acciones +=
            '<button type="button" class="dropdown-item accion-item accion-no-retiro" disabled>' +
                '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span>' +
                '<span class="accion-label">Retiro no disponible</span>' +
            '</button>';
    }

    acciones +=
        '<button type="button" class="dropdown-item accion-item accion-comprobante table_reportes table_comprobante_caja">' +
            '<span class="accion-icon accion-icon-danger"><i class="far fa-file-pdf"></i></span>' +
            '<span class="accion-label">Comprobante</span>' +
        '</button>';

    acciones +=
        '<button type="button" class="dropdown-item accion-item accion-retiros-detalle table_detalle_retiros_caja">' +
            '<span class="accion-icon accion-icon-warning"><i class="fas fa-list-ul"></i></span>' +
            '<span class="accion-label">Ver retiros</span>' +
        '</button>';

    acciones +=
        '<button type="button" class="dropdown-item accion-item accion-ganancia table_ganancia">' +
            '<span class="accion-icon accion-icon-primary"><i class="fas fa-chart-line"></i></span>' +
            '<span class="accion-label">Ver ganancia</span>' +
        '</button>';

    acciones +=
        '<button type="button" class="dropdown-item accion-item accion-cuadre-dia table_cuadre_dia">' +
            '<span class="accion-icon accion-icon-success"><i class="fas fa-balance-scale"></i></span>' +
            '<span class="accion-label">Cuadre del día</span>' +
        '</button>';

    return '' +
        '<div class="dropdown acciones-dropdown cajas-actions-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                acciones +
            '</div>' +
        '</div>';
}

function construirBadgeEstadoCaja(row) {
    var activa = esCajaActiva(row);

    return '' +
        '<span class="cajas-status-badge ' + (activa ? 'is-open' : 'is-closed') + '">' +
            '<i class="fas ' + (activa ? 'fa-circle' : 'fa-lock') + '"></i>' +
            (activa ? 'Abierta' : 'Cerrada') +
        '</span>';
}

function construirFilaCajaDetalle(row) {
    return '' +
        '<article class="cajas-detail-row" data-id="' + cajaEscape(row.apertura_id) + '">' +
            '<div class="cajas-detail-cell cajas-actions-cell">' +
                construirAccionesCaja(row) +
            '</div>' +

            '<div class="cajas-detail-cell">' +
                '<div class="cajas-stack">' +
                    '<strong><i class="far fa-calendar-alt mr-1"></i>' + cajaEscape(cajaValor(row.fecha)) + '</strong>' +
                    construirBadgeEstadoCaja(row) +
                '</div>' +
            '</div>' +

            '<div class="cajas-detail-cell">' +
                '<div class="cajas-data-line">' +
                    '<i class="fas fa-user"></i>' +
                    '<span>' + cajaEscape(cajaValor(row.usuario)) + '</span>' +
                '</div>' +
            '</div>' +

            '<div class="cajas-detail-cell">' +
                '<div class="cajas-stack">' +
                    '<span><small>Inicial</small><strong>' + cajaEscape(cajaValor(row.factura_inicial)) + '</strong></span>' +
                    '<span><small>Final</small><strong>' + cajaEscape(cajaValor(row.factura_final, 'Sin cierre')) + '</strong></span>' +
                '</div>' +
            '</div>' +

            '<div class="cajas-detail-cell cajas-money-cell">' +
                '<strong>' + cajaEscape(formatoMoneda(row.monto_apertura)) + '</strong>' +
            '</div>' +

            '<div class="cajas-detail-cell cajas-money-cell is-positive">' +
                '<strong>' + cajaEscape(formatoMoneda(row.importe_venta)) + '</strong>' +
            '</div>' +

            '<div class="cajas-detail-cell cajas-money-cell is-withdraw">' +
                '<strong>' + cajaEscape(formatoMoneda(row.retiro_caja)) + '</strong>' +
            '</div>' +

            '<div class="cajas-detail-cell cajas-money-cell ' + (parseMonto(row.neto) < 0 ? 'is-negative' : 'is-net') + '">' +
                '<strong>' + cajaEscape(formatoMoneda(row.neto)) + '</strong>' +
            '</div>' +
        '</article>';
}

function construirMiniaturaCaja(row) {
    return '' +
        '<article class="cajas-mini-card" data-id="' + cajaEscape(row.apertura_id) + '">' +
            '<div class="cajas-mini-topline"></div>' +

            '<div class="cajas-mini-header">' +
                '<div class="cajas-mini-identity">' +
                    '<div class="cajas-mini-icon"><i class="fas fa-cash-register"></i></div>' +
                    '<div>' +
                        '<h4>Caja #' + cajaEscape(row.apertura_id) + '</h4>' +
                        '<span><i class="far fa-calendar-alt mr-1"></i>' + cajaEscape(cajaValor(row.fecha)) + '</span>' +
                    '</div>' +
                '</div>' +
                construirBadgeEstadoCaja(row) +
            '</div>' +

            '<div class="cajas-mini-user">' +
                '<i class="fas fa-user mr-1"></i>' +
                '<strong>' + cajaEscape(cajaValor(row.usuario)) + '</strong>' +
            '</div>' +

            '<div class="cajas-mini-grid">' +
                '<div><small>Apertura</small><strong>' + cajaEscape(formatoMoneda(row.monto_apertura)) + '</strong></div>' +
                '<div><small>Venta</small><strong class="text-success">' + cajaEscape(formatoMoneda(row.importe_venta)) + '</strong></div>' +
                '<div><small>Retiros</small><strong class="text-warning">' + cajaEscape(formatoMoneda(row.retiro_caja)) + '</strong></div>' +
                '<div><small>Neto</small><strong class="' + (parseMonto(row.neto) < 0 ? 'text-danger' : 'text-success') + '">' + cajaEscape(formatoMoneda(row.neto)) + '</strong></div>' +
            '</div>' +

            '<div class="cajas-mini-invoices">' +
                '<span><small>Factura inicial</small><strong>' + cajaEscape(cajaValor(row.factura_inicial)) + '</strong></span>' +
                '<span><small>Factura final</small><strong>' + cajaEscape(cajaValor(row.factura_final, 'Sin cierre')) + '</strong></span>' +
            '</div>' +

            '<div class="cajas-mini-footer">' +
                construirAccionesCaja(row) +
            '</div>' +
        '</article>';
}

/* =========================================================
   PAGINACIÓN
   ========================================================= */
function renderPaginacionCajas(totalPaginas) {
    var pagina = cajasState.pagina;
    var html = '';

    html += crearBotonPaginaCaja('Inicio', 'fa-angle-double-left', 1, pagina <= 1);
    html += crearBotonPaginaCaja('Anterior', 'fa-angle-left', Math.max(1, pagina - 1), pagina <= 1);

    var desde = Math.max(1, pagina - 2);
    var hasta = Math.min(totalPaginas, desde + 4);

    if (hasta - desde < 4) {
        desde = Math.max(1, hasta - 4);
    }

    for (var i = desde; i <= hasta; i++) {
        html += '<button type="button" class="cajas-page-btn cajas-page-number ' +
            (i === pagina ? 'active' : '') +
            '" data-page="' + i + '">' + i + '</button>';
    }

    html += crearBotonPaginaCaja('Siguiente', 'fa-angle-right', Math.min(totalPaginas, pagina + 1), pagina >= totalPaginas);
    html += crearBotonPaginaCaja('Final', 'fa-angle-double-right', totalPaginas, pagina >= totalPaginas);

    $('#cajasPaginacion').html(html);

    $('#cajasPaginacion .cajas-page-btn')
        .off('click.cajasPage')
        .on('click.cajasPage', function () {
            if ($(this).prop('disabled')) {
                return;
            }

            cajasState.pagina = parseInt($(this).data('page'), 10) || 1;
            renderCajas();
        });
}

function crearBotonPaginaCaja(texto, icono, pagina, disabled) {
    return '' +
        '<button type="button" class="cajas-page-btn" data-page="' + pagina + '" ' +
            (disabled ? 'disabled' : '') + '>' +
            '<i class="fas ' + icono + '"></i>' +
            '<span>' + texto + '</span>' +
        '</button>';
}

function obtenerCajaPorId(id) {
    return cajasState.registros.find(function (row) {
        return String(row.apertura_id) === String(id);
    }) || null;
}

function obtenerCajaDesdeBoton(boton) {
    var id = $(boton).closest('[data-id]').data('id');
    return obtenerCajaPorId(id);
}

/* =========================================================
   ACCIONES - SE CONSERVAN LOS NOMBRES EXISTENTES
   ========================================================= */
var comprobante_cajas_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasCerrar', '.table_cerrar_caja')
        .on('click.cajasCerrar', '.table_cerrar_caja', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró la apertura de caja.');
                return;
            }

            if (!esCajaActiva(data)) {
                showNotify('error', 'Error', 'Esta caja ya está cerrada. No se puede cerrar nuevamente.');
                return;
            }

            var url = '<?php echo SERVERURL;?>core/editarCajas.php';

            $('#formAperturaCaja #apertura_id').val(data.apertura_id);

            $.ajax({
                type: 'POST',
                url: url,
                data: $('#formAperturaCaja').serialize(),
                success: function (registro) {
                    var valores = eval(registro);

                    $('#formAperturaCaja').attr({ 'data-form': 'update' });
                    $('#formAperturaCaja').attr({ 'action': '<?php echo SERVERURL;?>ajax/addCierreCajaAjax.php' });
                    $('#formAperturaCaja')[0].reset();

                    $('#open_caja').hide();
                    $('#close_caja').show();

                    $('#formAperturaCaja #usuario_apertura').val(valores[0]);
                    $('#formAperturaCaja #monto_apertura').val(valores[1]);
                    $('#formAperturaCaja #fecha_apertura').val(valores[2]);
                    $('#formAperturaCaja #colaboradores_id_apertura').val(valores[3]);

                    $('#formAperturaCaja #usuario_apertura').attr('readonly', true);
                    $('#formAperturaCaja #monto_apertura').attr('readonly', true);
                    $('#formAperturaCaja #fecha_apertura').attr('readonly', true);

                    $('#formAperturaCaja #proceso_aperturaCaja').val('Cerrar Caja');

                    $('#modal_apertura_caja').modal({
                        show: true,
                        keyboard: false,
                        backdrop: 'static'
                    });
                }
            });
        });
};

var cerrar_registro_cajas_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasComprobante', '.table_comprobante_caja')
        .on('click.cajasComprobante', '.table_comprobante_caja', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró la apertura de caja.');
                return;
            }

            printComprobanteCajas(data.apertura_id);
        });
};

var desglose_ganancia_caja_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasGanancia', '.table_ganancia')
        .on('click.cajasGanancia', '.table_ganancia', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró el código de apertura de caja.');
                return;
            }

            cargarDesgloseGananciaCaja(data.apertura_id, 'caja');
        });
};

var retiro_caja_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasRetiro', '.table_retiro_caja')
        .on('click.cajasRetiro', '.table_retiro_caja', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró la apertura de caja.');
                return;
            }

            if (!esCajaActiva(data)) {
                showNotify('error', 'Error', 'Solo puede retirar dinero de una caja activa.');
                return;
            }

            abrirModalRetiroCaja(data.apertura_id);
        });
};

var detalle_retiros_caja_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasDetalleRetiros', '.table_detalle_retiros_caja')
        .on('click.cajasDetalleRetiros', '.table_detalle_retiros_caja', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró la apertura de caja.');
                return;
            }

            cargarDetalleRetirosCaja(data.apertura_id, 'caja');
        });
};

var cuadre_dia_caja_dataTable = function () {
    $('#cajasListado')
        .off('click.cajasCuadre', '.table_cuadre_dia')
        .on('click.cajasCuadre', '.table_cuadre_dia', function () {
            var data = obtenerCajaDesdeBoton(this);

            if (!data || !data.apertura_id) {
                showNotify('error', 'Error', 'No se encontró la apertura de caja.');
                return;
            }

            cargarCuadreDiaCaja(data.apertura_id, 'caja');
        });
};

function abrirModalRetiroCaja(apertura_id) {
    apertura_id = parseInt(apertura_id || 0);

    if (apertura_id <= 0) {
        showNotify('error', 'Error', 'No se encontró la apertura de caja.');
        return;
    }

    $('#formRetiroCaja')[0].reset();

    $('#retiro_apertura_id').val(apertura_id);
    $('#retiro_saldo_actual').val('0.00');
    $('#retiro_saldo_final').val('0.00');
    $('#retiro_saldo_efectivo').val('0.00');
    $('#retiro_saldo_transferencia').val('0.00');
    $('#retiro_saldo_final_efectivo').val('0.00');
    $('#retiro_saldo_final_transferencia').val('0.00');
    $('#retiro_monto').val('0.00');

    $('#retiro_saldo_efectivo_text').html('Cargando...');
    $('#retiro_saldo_transferencia_text').html('Cargando...');
    $('#retiro_saldo_actual_text').html('Cargando...');
    $('#retiro_max_efectivo_text').html('Cargando...');
    $('#retiro_max_transferencia_text').html('Cargando...');
    $('#retiro_total_retirar_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_efectivo_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_transferencia_text').html(formatoMoneda(0));
    $('#retiro_saldo_final_text').html(formatoMoneda(0));

    $('#retiro_box_efectivo').removeClass('retiro-input-error');
    $('#retiro_box_transferencia').removeClass('retiro-input-error');

    $('#retiro_monto_efectivo').val('').prop('disabled', true);
    $('#retiro_monto_transferencia').val('').prop('disabled', true);
    $('#btn_guardar_retiro_caja').prop('disabled', true);

    if ($.fn.selectpicker) {
        $('#retiro_categoria_gastos_id').selectpicker('val', '');
        $('#retiro_categoria_gastos_id').selectpicker('refresh');
    }

    cargarSaldoRetiroCaja(function () {
        $('#retiro_monto_efectivo').prop('disabled', false);
        $('#retiro_monto_transferencia').prop('disabled', false);
        validarRetiroCaja(false);
    });

    $('#modalRetiroCaja')
        .off('shown.bs.modal')
        .on('shown.bs.modal', function () {
            setTimeout(function () {
                $('#retiro_monto_efectivo').trigger('focus').select();
            }, 150);
        });

    $('#modalRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cargarSaldoRetiroCaja(callback) {
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getSaldoRetiroCaja.php',
        dataType: 'json',
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo obtener el saldo disponible para retiro.');
                $('#modalRetiroCaja').modal('hide');
                return;
            }

            var saldoEfectivo = parseMonto(response.saldo_efectivo);
            var saldoTransferencia = parseMonto(response.saldo_transferencia);
            var saldoTotal = parseMonto(response.saldo_disponible);

            $('#retiro_apertura_id').val(response.apertura_id || $('#retiro_apertura_id').val());
            $('#retiro_saldo_efectivo').val(saldoEfectivo.toFixed(2));
            $('#retiro_saldo_transferencia').val(saldoTransferencia.toFixed(2));
            $('#retiro_saldo_actual').val(saldoTotal.toFixed(2));

            $('#retiro_saldo_efectivo_text').html(formatoMoneda(saldoEfectivo));
            $('#retiro_saldo_transferencia_text').html(formatoMoneda(saldoTransferencia));
            $('#retiro_saldo_actual_text').html(formatoMoneda(saldoTotal));
            $('#retiro_max_efectivo_text').html(formatoMoneda(saldoEfectivo));
            $('#retiro_max_transferencia_text').html(formatoMoneda(saldoTransferencia));

            $('#retiro_monto_efectivo').attr('max', saldoEfectivo.toFixed(2));
            $('#retiro_monto_transferencia').attr('max', saldoTransferencia.toFixed(2));

            actualizarResumenRetiroCaja();

            if (typeof callback === 'function') {
                callback(response);
            }
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al obtener el saldo disponible.');
            $('#modalRetiroCaja').modal('hide');
        }
    });
}

function actualizarResumenRetiroCaja() {
    var saldoEfectivo = parseMonto($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMonto($('#retiro_saldo_transferencia').val());
    var montoEfectivo = parseMonto($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMonto($('#retiro_monto_transferencia').val());

    var totalRetirar = montoEfectivo + montoTransferencia;
    var saldoFinalEfectivo = saldoEfectivo - montoEfectivo;
    var saldoFinalTransferencia = saldoTransferencia - montoTransferencia;

    if (saldoFinalEfectivo < 0) {
        saldoFinalEfectivo = 0;
    }

    if (saldoFinalTransferencia < 0) {
        saldoFinalTransferencia = 0;
    }

    var saldoFinalTotal = saldoFinalEfectivo + saldoFinalTransferencia;

    $('#retiro_monto').val(totalRetirar.toFixed(2));
    $('#retiro_saldo_final_efectivo').val(saldoFinalEfectivo.toFixed(2));
    $('#retiro_saldo_final_transferencia').val(saldoFinalTransferencia.toFixed(2));
    $('#retiro_saldo_final').val(saldoFinalTotal.toFixed(2));

    $('#retiro_total_retirar_text').html(formatoMoneda(totalRetirar));
    $('#retiro_saldo_final_efectivo_text').html(formatoMoneda(saldoFinalEfectivo));
    $('#retiro_saldo_final_transferencia_text').html(formatoMoneda(saldoFinalTransferencia));
    $('#retiro_saldo_final_text').html(formatoMoneda(saldoFinalTotal));
}

function validarRetiroCaja(mostrarMensaje) {
    var saldoEfectivo = parseMonto($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMonto($('#retiro_saldo_transferencia').val());
    var montoEfectivo = parseMonto($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMonto($('#retiro_monto_transferencia').val());
    var categoria = parseInt($('#retiro_categoria_gastos_id').val() || 0);
    var errores = [];

    $('#retiro_box_efectivo').removeClass('retiro-input-error');
    $('#retiro_box_transferencia').removeClass('retiro-input-error');

    if (montoEfectivo < 0 || montoTransferencia < 0) {
        errores.push('Los montos no pueden ser negativos.');
    }

    if (montoEfectivo <= 0 && montoTransferencia <= 0) {
        errores.push('Ingrese un monto en efectivo, transferencia o ambos.');
    }

    if (montoEfectivo > saldoEfectivo) {
        errores.push('El retiro de efectivo no puede ser mayor al efectivo disponible.');
        $('#retiro_box_efectivo').addClass('retiro-input-error');
    }

    if (montoTransferencia > saldoTransferencia) {
        errores.push('El retiro de transferencia no puede ser mayor al saldo disponible por transferencia.');
        $('#retiro_box_transferencia').addClass('retiro-input-error');
    }

    if (categoria <= 0) {
        errores.push('Seleccione la categoría del retiro.');
    }

    actualizarResumenRetiroCaja();

    if (errores.length > 0) {
        $('#btn_guardar_retiro_caja').prop('disabled', false);

        if (mostrarMensaje === true) {
            showNotify('error', 'Error', errores[0]);
        }

        return false;
    }

    $('#btn_guardar_retiro_caja').prop('disabled', false);
    return true;
}

$('#retiro_monto_efectivo, #retiro_monto_transferencia')
    .off('input change keyup')
    .on('input change keyup', function () {
        validarRetiroCaja(false);
    });

$('#retiro_categoria_gastos_id')
    .off('change')
    .on('change', function () {
        validarRetiroCaja(false);
    });

$('#btn_guardar_retiro_caja')
    .off('click.validacionRetiroCaja')
    .on('click.validacionRetiroCaja', function (e) {
        if (!validarRetiroCaja(true)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    });

$('#formRetiroCaja')
    .off('submit.validacionRetiroCaja')
    .on('submit.validacionRetiroCaja', function (e) {
        if (!validarRetiroCaja(true)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        return true;
    });

/* =========================================================
   EXCEL PREMIUM
   ========================================================= */
function cajasExportRows() {
    return cajasState.filtrados.map(function (row) {
        return {
            fecha: cajaValor(row.fecha),
            usuario: cajaValor(row.usuario),
            facturaInicial: cajaValor(row.factura_inicial),
            facturaFinal: cajaValor(row.factura_final, 'Sin cierre'),
            apertura: parseMonto(row.monto_apertura),
            venta: parseMonto(row.importe_venta),
            retiro: parseMonto(row.retiro_caja),
            neto: parseMonto(row.neto),
            estado: esCajaActiva(row) ? 'Abierta' : 'Cerrada'
        };
    });
}

function cajasXmlEscape(value) {
    return String(value === null || typeof value === 'undefined' ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
}

function cajasExcelCol(index) {
    var name = '';
    var n = index + 1;

    while (n > 0) {
        var mod = (n - 1) % 26;
        name = String.fromCharCode(65 + mod) + name;
        n = Math.floor((n - 1) / 26);
    }

    return name;
}

function cajasExcelCell(ref, value, styleId, numeric) {
    if (numeric) {
        var numero = Number(value);

        if (!isNaN(numero)) {
            return '<c r="' + ref + '" s="' + styleId + '"><v>' + numero + '</v></c>';
        }
    }

    return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
        '<is><t>' + cajasXmlEscape(value) + '</t></is>' +
    '</c>';
}

function cajasDescargarBlob(blob, nombre) {
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');

    link.href = url;
    link.download = nombre;

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(function () {
        URL.revokeObjectURL(url);
    }, 1000);
}

function exportarCajasExcelPremium() {
    var rows = cajasExportRows();

    if (!rows.length) {
        showNotify('warning', 'Sin información', 'No hay cajas para exportar.');
        return;
    }

    if (typeof JSZip === 'undefined') {
        showNotify('error', 'Excel no disponible', 'No se encontró JSZip para generar el archivo XLSX.');
        return;
    }

    var headers = [
        'Fecha',
        'Usuario',
        'Factura Inicial',
        'Factura Final',
        'Monto Apertura',
        'Venta del Día',
        'Retiro Caja',
        'Neto Caja',
        'Estado'
    ];

    var totalApertura = rows.reduce(function (acc, row) { return acc + row.apertura; }, 0);
    var totalVenta = rows.reduce(function (acc, row) { return acc + row.venta; }, 0);
    var totalRetiro = rows.reduce(function (acc, row) { return acc + row.retiro; }, 0);
    var totalNeto = rows.reduce(function (acc, row) { return acc + row.neto; }, 0);
    var abiertas = rows.filter(function (row) { return row.estado === 'Abierta'; }).length;

    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            cajasExcelCell('A1', 'IZZY • REPORTE DE CAJAS', 1, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            cajasExcelCell(
                'A2',
                'Control de aperturas, ventas, retiros y neto • Generado: ' +
                new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3">' +
            cajasExcelCell('A3', 'REGISTROS', 6, false) +
            cajasExcelCell('C3', 'CAJAS ABIERTAS', 6, false) +
            cajasExcelCell('E3', 'VENTA', 6, false) +
            cajasExcelCell('G3', 'RETIROS', 6, false) +
            cajasExcelCell('I3', 'NETO', 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="27" customHeight="1">' +
            cajasExcelCell('A4', rows.length, 7, true) +
            cajasExcelCell('C4', abiertas, 7, true) +
            cajasExcelCell('E4', totalVenta, 11, true) +
            cajasExcelCell('G4', totalRetiro, 11, true) +
            cajasExcelCell('I4', totalNeto, 11, true) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6">' +
            cajasExcelCell('A6', 'Detalle de cajas filtradas', 8, false) +
        '</row>'
    );

    var headerCells = headers.map(function (header, index) {
        return cajasExcelCell(
            cajasExcelCol(index) + headerRow,
            header,
            3,
            false
        );
    }).join('');

    sheetRows.push(
        '<row r="' + headerRow + '" ht="28" customHeight="1">' +
            headerCells +
        '</row>'
    );

    rows.forEach(function (row, rowIndex) {
        var excelRow = firstDataRow + rowIndex;
        var valores = [
            row.fecha,
            row.usuario,
            row.facturaInicial,
            row.facturaFinal,
            row.apertura,
            row.venta,
            row.retiro,
            row.neto,
            row.estado
        ];

        var cells = valores.map(function (value, colIndex) {
            var numeric = colIndex >= 4 && colIndex <= 7;
            var style = numeric ? 11 : 4;

            if (colIndex === 8) {
                style = value === 'Abierta' ? 9 : 10;
            }

            return cajasExcelCell(
                cajasExcelCol(colIndex) + excelRow,
                value,
                style,
                numeric
            );
        }).join('');

        sheetRows.push(
            '<row r="' + excelRow + '" ht="28" customHeight="1">' +
                cells +
            '</row>'
        );
    });

    var sheetXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:I' + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
            '</sheetView></sheetViews>' +
            '<cols>' +
                '<col min="1" max="1" width="16" customWidth="1"/>' +
                '<col min="2" max="2" width="28" customWidth="1"/>' +
                '<col min="3" max="4" width="20" customWidth="1"/>' +
                '<col min="5" max="8" width="18" customWidth="1"/>' +
                '<col min="9" max="9" width="14" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A7:I' + lastRow + '"/>' +
            '<mergeCells count="12">' +
                '<mergeCell ref="A1:I1"/>' +
                '<mergeCell ref="A2:I2"/>' +
                '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                '<mergeCell ref="G3:H3"/><mergeCell ref="G4:H4"/>' +
                '<mergeCell ref="I3:I3"/><mergeCell ref="I4:I4"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;L. &quot;#,##0.00"/></numFmts>' +
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
                '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
            '</borders>' +
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
            '<cellXfs count="12">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Cajas" sheetId="1" r:id="rId1"/></sheets>' +
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
        cajasDescargarBlob(blob, 'Reporte_Cajas.xlsx');
    }).catch(function (error) {
        console.error(error);
        showNotify('error', 'Error', 'No se pudo generar el archivo Excel.');
    });
}

/* =========================================================
   PDF PREMIUM SEGÚN VISTA
   ========================================================= */
function cajasPdfDato(label, value, color) {
    return {
        stack: [
            {
                text: String(label || '').toUpperCase(),
                fontSize: 6.2,
                bold: true,
                color: '#6B778C',
                margin: [0, 0, 0, 2]
            },
            {
                text: String(
                    value === null ||
                    typeof value === 'undefined' ||
                    value === ''
                        ? '—'
                        : value
                ),
                fontSize: 7.8,
                bold: true,
                color: color || '#172B4D'
            }
        ]
    };
}

function cajasPdfFiltroTexto() {
    var estado = $('#estado_cajas option:selected').text() || 'Todas';
    var fechaInicial = $('#fecha_cajas').val() || '';
    var fechaFinal = $('#fecha_cajas_f').val() || '';

    return 'Estado: ' + estado +
        '   |   Desde: ' + (fechaInicial || '—') +
        '   |   Hasta: ' + (fechaFinal || '—');
}

function cajasPdfEncabezadoPremium(rows) {
    var totalVentas = rows.reduce(function (acc, row) {
        return acc + row.venta;
    }, 0);

    var totalRetiros = rows.reduce(function (acc, row) {
        return acc + row.retiro;
    }, 0);

    var totalNeto = rows.reduce(function (acc, row) {
        return acc + row.neto;
    }, 0);

    var abiertas = rows.filter(function (row) {
        return row.estado === 'Abierta';
    }).length;

    var logoCell;

    if (typeof imagen !== 'undefined' && imagen) {
        logoCell = {
            image: imagen,
            width: 52,
            height: 24,
            alignment: 'center',
            margin: [0, 2, 0, 0]
        };
    } else {
        logoCell = {
            text: 'IZZY',
            fontSize: 16,
            bold: true,
            color: '#FFFFFF',
            alignment: 'center',
            margin: [0, 5, 0, 0]
        };
    }

    var header = {
        table: {
            widths: [72, '*', 155],
            body: [[
                {
                    border: [false, false, false, false],
                    fillColor: '#17324D',
                    margin: [12, 10, 0, 10],
                    stack: [logoCell]
                },
                {
                    border: [false, false, false, false],
                    fillColor: '#17324D',
                    margin: [0, 10, 0, 10],
                    stack: [
                        {
                            text: 'REPORTE DE CAJAS',
                            fontSize: 16,
                            bold: true,
                            color: '#FFFFFF'
                        },
                        {
                            text: 'Control de aperturas, ventas, retiros y neto de caja',
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
                            text: new Date().toLocaleDateString('es-HN'),
                            fontSize: 9,
                            bold: true,
                            color: '#FFFFFF',
                            alignment: 'right',
                            margin: [0, 3, 0, 0]
                        },
                        {
                            text: rows.length + ' registro(s) filtrado(s)',
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
            hLineWidth: function () { return 0; },
            vLineWidth: function () { return 0; }
        },
        margin: [0, 0, 0, 10]
    };

    var filtros = {
        table: {
            widths: ['*'],
            body: [[{
                text: 'Filtros aplicados: ' + cajasPdfFiltroTexto(),
                fontSize: 6.8,
                color: '#52627A',
                margin: [10, 7, 10, 7],
                fillColor: '#F7F9FC'
            }]]
        },
        layout: {
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return 0.6; },
            vLineWidth: function () { return 0.6; }
        },
        margin: [0, 0, 0, 10]
    };

    var resumen = {
        table: {
            widths: ['*', '*', '*', '*', '*'],
            body: [[
                {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        {text: 'REGISTROS', fontSize: 6.3, bold: true, color: '#6B778C'},
                        {text: String(rows.length), fontSize: 13, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                    ]
                },
                {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        {text: 'CAJAS ABIERTAS', fontSize: 6.3, bold: true, color: '#6B778C'},
                        {text: String(abiertas), fontSize: 13, bold: true, color: '#172B4D', margin: [0, 2, 0, 0]}
                    ]
                },
                {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        {text: 'VENTA', fontSize: 6.3, bold: true, color: '#6B778C'},
                        {text: formatoMoneda(totalVentas), fontSize: 11, bold: true, color: '#14804A', margin: [0, 2, 0, 0]}
                    ]
                },
                {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        {text: 'RETIROS', fontSize: 6.3, bold: true, color: '#6B778C'},
                        {text: formatoMoneda(totalRetiros), fontSize: 11, bold: true, color: '#B66A00', margin: [0, 2, 0, 0]}
                    ]
                },
                {
                    fillColor: '#F7F9FC',
                    margin: [8, 7, 8, 7],
                    stack: [
                        {text: 'NETO', fontSize: 6.3, bold: true, color: '#6B778C'},
                        {
                            text: formatoMoneda(totalNeto),
                            fontSize: 11,
                            bold: true,
                            color: totalNeto < 0 ? '#C9372C' : '#14804A',
                            margin: [0, 2, 0, 0]
                        }
                    ]
                }
            ]]
        },
        layout: {
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return 0.6; },
            vLineWidth: function () { return 0.6; }
        },
        margin: [0, 0, 0, 12]
    };

    return [header, filtros, resumen];
}

function cajasPdfContenidoDetalle(rows) {
    var body = [[
        {text: 'FECHA', style: 'th', fillColor: '#17324D'},
        {text: 'USUARIO', style: 'th', fillColor: '#17324D'},
        {text: 'FACTURA INICIAL', style: 'th', fillColor: '#17324D'},
        {text: 'FACTURA FINAL', style: 'th', fillColor: '#17324D'},
        {text: 'APERTURA', style: 'th', fillColor: '#17324D'},
        {text: 'VENTA', style: 'th', fillColor: '#17324D'},
        {text: 'RETIROS', style: 'th', fillColor: '#17324D'},
        {text: 'NETO', style: 'th', fillColor: '#17324D'},
        {text: 'ESTADO', style: 'th', fillColor: '#17324D'}
    ]];

    rows.forEach(function (row, index) {
        var fill = index % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

        body.push([
            {text: row.fecha, style: 'tdCenter', fillColor: fill},
            {text: row.usuario, style: 'tdStrong', fillColor: fill},
            {text: row.facturaInicial, style: 'tdCenter', fillColor: fill},
            {text: row.facturaFinal, style: 'tdCenter', fillColor: fill},
            {text: formatoMoneda(row.apertura), style: 'tdMoney', fillColor: fill},
            {text: formatoMoneda(row.venta), style: 'tdMoney', color: '#14804A', fillColor: fill},
            {text: formatoMoneda(row.retiro), style: 'tdMoney', color: '#B66A00', fillColor: fill},
            {
                text: formatoMoneda(row.neto),
                style: 'tdMoney',
                color: row.neto < 0 ? '#C9372C' : '#14804A',
                bold: true,
                fillColor: fill
            },
            {
                text: row.estado,
                style: 'tdCenter',
                color: row.estado === 'Abierta' ? '#14804A' : '#C9372C',
                bold: true,
                fillColor: fill
            }
        ]);
    });

    return [
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
                widths: [62, 104, 72, 72, 68, 68, 68, 68, 50],
                body: body
            },
            layout: {
                hLineColor: function () { return '#DDE3EA'; },
                vLineColor: function () { return '#DDE3EA'; },
                hLineWidth: function () { return 0.55; },
                vLineWidth: function () { return 0.55; },
                paddingLeft: function () { return 5; },
                paddingRight: function () { return 5; },
                paddingTop: function () { return 6; },
                paddingBottom: function () { return 6; }
            }
        }
    ];
}

function cajasPdfMiniCard(row) {
    var estadoColor = row.estado === 'Abierta' ? '#14804A' : '#C9372C';

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
                                        text: row.usuario,
                                        fontSize: 10,
                                        bold: true,
                                        color: '#172B4D'
                                    },
                                    {
                                        text: 'Caja • ' + row.fecha,
                                        fontSize: 7,
                                        color: '#6B778C',
                                        margin: [0, 2, 0, 0]
                                    }
                                ]
                            },
                            {
                                width: 'auto',
                                table: {
                                    body: [[{
                                        text: row.estado,
                                        fontSize: 6.8,
                                        bold: true,
                                        color: estadoColor,
                                        fillColor: row.estado === 'Abierta' ? '#E9F9EF' : '#FFF0EF',
                                        margin: [6, 3, 6, 3]
                                    }]]
                                },
                                layout: {
                                    hLineColor: function () {
                                        return row.estado === 'Abierta' ? '#BFE8CF' : '#F2C3BF';
                                    },
                                    vLineColor: function () {
                                        return row.estado === 'Abierta' ? '#BFE8CF' : '#F2C3BF';
                                    },
                                    hLineWidth: function () { return 0.6; },
                                    vLineWidth: function () { return 0.6; }
                                }
                            }
                        ]
                    },
                    {
                        canvas: [{
                            type: 'line',
                            x1: 0,
                            y1: 0,
                            x2: 250,
                            y2: 0,
                            lineWidth: 0.6,
                            lineColor: '#DDE3EA'
                        }],
                        margin: [0, 7, 0, 7]
                    },
                    {
                        columns: [
                            {
                                width: '50%',
                                stack: [
                                    cajasPdfDato('Factura inicial', row.facturaInicial),
                                    {
                                        margin: [0, 8, 0, 0],
                                        stack: [
                                            cajasPdfDato(
                                                'Apertura',
                                                formatoMoneda(row.apertura)
                                            )
                                        ]
                                    },
                                    {
                                        margin: [0, 8, 0, 0],
                                        stack: [
                                            cajasPdfDato(
                                                'Retiros',
                                                formatoMoneda(row.retiro),
                                                '#B66A00'
                                            )
                                        ]
                                    }
                                ]
                            },
                            {
                                width: '50%',
                                stack: [
                                    cajasPdfDato('Factura final', row.facturaFinal),
                                    {
                                        margin: [0, 8, 0, 0],
                                        stack: [
                                            cajasPdfDato(
                                                'Venta',
                                                formatoMoneda(row.venta),
                                                '#14804A'
                                            )
                                        ]
                                    },
                                    {
                                        margin: [0, 8, 0, 0],
                                        stack: [
                                            cajasPdfDato(
                                                'Neto',
                                                formatoMoneda(row.neto),
                                                row.neto < 0
                                                    ? '#C9372C'
                                                    : '#14804A'
                                            )
                                        ]
                                    }
                                ]
                            }
                        ]
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

function cajasPdfContenidoMiniatura(rows) {
    var contenido = [
        {
            text: 'VISTA MINIATURA',
            fontSize: 7,
            bold: true,
            color: '#17324D',
            margin: [0, 1, 0, 7]
        }
    ];

    for (var i = 0; i < rows.length; i += 2) {
        contenido.push({
            columns: [
                {
                    width: '*',
                    stack: [cajasPdfMiniCard(rows[i])]
                },
                {
                    width: 10,
                    text: ''
                },
                rows[i + 1]
                    ? {
                        width: '*',
                        stack: [cajasPdfMiniCard(rows[i + 1])]
                    }
                    : {
                        width: '*',
                        text: ''
                    }
            ],
            margin: [0, 0, 0, 9]
        });
    }

    return contenido;
}

function previsualizarCajasPdfPremium() {
    var rows = cajasExportRows();

    if (!rows.length) {
        showNotify(
            'warning',
            'Sin información',
            'No hay cajas para exportar.'
        );
        return;
    }

    if (typeof pdfMake === 'undefined') {
        showNotify(
            'error',
            'PDF no disponible',
            'No se encontró pdfMake.'
        );
        return;
    }

    if (typeof abrirModalPdfPublico !== 'function') {
        showNotify(
            'error',
            'Visor PDF no disponible',
            'No se encontró el modal PDF público.'
        );
        return;
    }

    var esMiniatura = cajasState.vista === 'miniatura';

    var contenido = cajasPdfEncabezadoPremium(rows).concat(
        esMiniatura
            ? cajasPdfContenidoMiniatura(rows)
            : cajasPdfContenidoDetalle(rows)
    );

    var docDefinition = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [28, 28, 28, 34],

        header: function () {
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

        footer: function (currentPage, pageCount) {
            return {
                margin: [28, 8, 28, 0],
                columns: [
                    {
                        text: 'IZZY • Registro de Cajas',
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

        content: contenido,

        styles: {
            th: {
                fontSize: 6.2,
                bold: true,
                color: '#FFFFFF',
                alignment: 'center',
                margin: [0, 1, 0, 1]
            },
            tdStrong: {
                fontSize: 6.5,
                bold: true,
                color: '#172B4D',
                alignment: 'left'
            },
            tdCenter: {
                fontSize: 6.4,
                color: '#253858',
                alignment: 'center'
            },
            tdMoney: {
                fontSize: 6.4,
                color: '#253858',
                alignment: 'right'
            }
        },

        defaultStyle: {
            fontSize: 8,
            color: '#253858'
        }
    };

    var pdf = pdfMake.createPdf(docDefinition);
    var nombre = 'Reporte_Cajas.pdf';

    if (typeof pdf.getDataUrl === 'function') {
        pdf.getDataUrl(function (dataUrl) {
            abrirModalPdfPublico(
                dataUrl,
                'Reporte de Cajas',
                nombre
            );
        });
        return;
    }

    if (typeof pdf.getBase64 === 'function') {
        pdf.getBase64(function (base64) {
            abrirModalPdfPublico(
                'data:application/pdf;base64,' + base64,
                'Reporte de Cajas',
                nombre
            );
        });
        return;
    }

    showNotify(
        'error',
        'PDF no disponible',
        'La versión actual de pdfMake no permite una vista previa compatible.'
    );
}

/* =========================================================
   CUADRE DEL DÍA / PERÍODO
   ========================================================= */
function setTextoCuadreDia(selector, valor) {
    $(selector).html(formatoMoneda(valor));
}

function escaparTextoCuadreDia(texto) {
    return $('<div>').text(texto || '').html();
}

function cargarCuadreDiaCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = $('#modalCuadreDiaCaja').data('modo') || 'periodo';
    }

    if (modo === 'caja' && apertura_id <= 0) {
        showNotify('error', 'Error', 'No se recibió una apertura válida.');
        return;
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $('#modalCuadreDiaCaja').data('modo', modo);
    $('#modalCuadreDiaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        $('#modalCuadreDiaCajaLabel').html('<i class="fas fa-balance-scale mr-1"></i> Cuadre del período');
        $('#cd_contexto_caja').html('Desde ' + fechai + ' hasta ' + fechaf);
    } else {
        $('#modalCuadreDiaCajaLabel').html('<i class="fas fa-balance-scale mr-1"></i> Cuadre del día');
        $('#cd_contexto_caja').html('Apertura de caja #' + apertura_id);
    }

    $('#cd_total_cobrado').html('Cargando...');
    $('#cd_inversion_reposicion').html('Cargando...');
    $('#cd_gastos_total').html('Cargando...');
    $('#cd_total_final_esperado').html('Cargando...');

    $('#cd_efectivo').html('Cargando...');
    $('#cd_transferencia').html('Cargando...');
    $('#cd_tarjeta').html('Cargando...');
    $('#cd_cheque').html('Cargando...');
    $('#cd_monto_apertura').html('Cargando...');

    $('#cd_efectivo_esperado').html('Cargando...');
    $('#cd_transferencia_esperada').html('Cargando...');
    $('#cd_tarjeta_esperada').html('Cargando...');
    $('#cd_cheque_esperado').html('Cargando...');
    $('#cd_total_final_esperado_tabla').html('Cargando...');

    $('#cd_formula_efectivo').html('Cargando...');
    $('#cd_formula_apertura').html('Cargando...');
    $('#cd_formula_inversion').html('Cargando...');
    $('#cd_formula_gastos_efectivo').html('Cargando...');
    $('#cd_formula_resultado').html('Cargando...');
    $('#cd_isv_factura_normal_sar').html('Cargando...');
    $('#cd_isv_proforma_informativo').html('Cargando...');
    $('#cd_isv_total_detalle').html('Cargando...');

    $('#cd_tabla_gastos tbody').html(
        '<tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>'
    );

    $('#cd_tabla_inversiones tbody').html(
        '<tr><td colspan="3" class="text-center text-muted">Cargando...</td></tr>'
    );

    $('#modalCuadreDiaCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getCuadreDiaCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        success: function (response) {
            if (!response || !response.success) {
                showNotify(
                    'error',
                    'Error',
                    response && response.message ? response.message : 'No se pudo cargar el cuadre del día.'
                );
                return;
            }

            var resumen = response.resumen || {};
            var gastos = response.gastos || [];
            var inversiones = response.inversiones || [];

            renderCuadreDiaCaja(resumen, gastos, inversiones);
            $('#modalCuadreDiaCaja').modal('handleUpdate');
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar el cuadre del día.');
        }
    });
}

function refrescarCuadreDiaCaja() {
    var apertura_id = parseInt($('#modalCuadreDiaCaja').data('apertura_id') || 0);
    var modo = $('#modalCuadreDiaCaja').data('modo') || 'periodo';

    cargarCuadreDiaCaja(apertura_id, modo);
}

function renderCuadreDiaCaja(resumen, gastos, inversiones) {
    var totalCobrado = parseMonto(resumen.total_cobrado || 0);
    var montoApertura = parseMonto(resumen.monto_apertura || 0);

    var efectivo = parseMonto(resumen.efectivo || 0);
    var transferencia = parseMonto(resumen.transferencia || 0);
    var tarjeta = parseMonto(resumen.tarjeta || 0);
    var cheque = parseMonto(resumen.cheque || 0);

    var inversionTotal = parseMonto(resumen.inversion_total_considerada || resumen.inversion_reposicion || 0);
    var inversionSugerida = parseMonto(resumen.inversion_sugerida || resumen.inversion_reposicion || 0);
    var inversionManual = parseMonto(resumen.inversion_manual_registrada || 0);
    var inversionPendiente = parseMonto(resumen.inversion_pendiente || 0);
    var inversionNoCubierta = parseMonto(resumen.inversion_no_cubierta || 0);

    var gastosTotal = parseMonto(resumen.gastos_total || 0);

    var gastosEfectivo = parseMonto(resumen.gastos_efectivo || 0);
    var gastosTransferencia = parseMonto(resumen.gastos_transferencia || 0);
    var gastosTarjeta = parseMonto(resumen.gastos_tarjeta || 0);
    var gastosCheque = parseMonto(resumen.gastos_cheque || 0);

    var inversionEfectivo = parseMonto(resumen.inversion_efectivo || 0);
    var inversionTransferencia = parseMonto(resumen.inversion_transferencia || 0);
    var inversionTarjeta = parseMonto(resumen.inversion_tarjeta || 0);
    var inversionCheque = parseMonto(resumen.inversion_cheque || 0);

    var efectivoEsperado = parseMonto(resumen.efectivo_esperado || 0);
    var transferenciaEsperada = parseMonto(resumen.transferencia_esperada || 0);
    var tarjetaEsperada = parseMonto(resumen.tarjeta_esperada || 0);
    var chequeEsperado = parseMonto(resumen.cheque_esperado || 0);
    var totalFinalEsperado = parseMonto(resumen.total_final_esperado || 0);

    // =====================================================
    // VALORES ISV - DIRECTAMENTE DEL PHP, SIN MODIFICAR
    // =====================================================
    var isvFacturaNormalSar = parseMonto(resumen.isv_factura_normal_sar || 0);
    var isvProformaInformativo = parseMonto(resumen.isv_proforma_informativo || 0);
    var isvTotalDetalle = parseMonto(resumen.isv_total_detalle || 0);

    // SOLO REDONDEAMOS PARA MOSTRAR - NO MODIFICAMOS LA LÓGICA
    isvFacturaNormalSar = Math.round(isvFacturaNormalSar * 100) / 100;
    isvProformaInformativo = Math.round(isvProformaInformativo * 100) / 100;
    isvTotalDetalle = Math.round(isvTotalDetalle * 100) / 100;

    // MOSTRAMOS LOS VALORES DIRECTAMENTE SIN NINGUNA MODIFICACIÓN
    setTextoCuadreDia('#cd_total_cobrado', totalCobrado);
    setTextoCuadreDia('#cd_inversion_reposicion', inversionTotal);
    setTextoCuadreDia('#cd_gastos_total', gastosTotal);
    setTextoCuadreDia('#cd_total_final_esperado', totalFinalEsperado);
    setTextoCuadreDia('#cd_isv_factura_normal_sar', isvFacturaNormalSar);
    setTextoCuadreDia('#cd_isv_proforma_informativo', isvProformaInformativo);
    setTextoCuadreDia('#cd_isv_total_detalle', isvTotalDetalle);

    setTextoCuadreDia('#cd_efectivo', efectivo);
    setTextoCuadreDia('#cd_transferencia', transferencia);
    setTextoCuadreDia('#cd_tarjeta', tarjeta);
    setTextoCuadreDia('#cd_cheque', cheque);
    setTextoCuadreDia('#cd_monto_apertura', montoApertura);

    setTextoCuadreDia('#cd_efectivo_esperado', efectivoEsperado);
    setTextoCuadreDia('#cd_transferencia_esperada', transferenciaEsperada);
    setTextoCuadreDia('#cd_tarjeta_esperada', tarjetaEsperada);
    setTextoCuadreDia('#cd_cheque_esperado', chequeEsperado);
    setTextoCuadreDia('#cd_total_final_esperado_tabla', totalFinalEsperado);

    setTextoCuadreDia('#cd_formula_efectivo', efectivo);
    setTextoCuadreDia('#cd_formula_apertura', montoApertura);
    setTextoCuadreDia('#cd_formula_inversion', inversionEfectivo);
    setTextoCuadreDia('#cd_formula_gastos_efectivo', gastosEfectivo);
    setTextoCuadreDia('#cd_formula_resultado', efectivoEsperado);

    $('#cd_inversion_reposicion')
        .closest('.card')
        .find('small')
        .html(
            'Reposición sugerida: <strong>' + formatoMoneda(inversionSugerida) + '</strong>. ' +
            'Manual registrada: <strong>' + formatoMoneda(inversionManual) + '</strong>.'
        );

    $('#cd_total_final_esperado')
        .closest('.card')
        .find('small')
        .html('Total cobrado + apertura - inversión/reposición - gastos/retiros.');

    $('#cd_formula_resultado')
        .closest('.d-flex')
        .find('span')
        .html('= Efectivo esperado después de reposición');

    var htmlGastos = '';

    if (!gastos || gastos.length <= 0) {
        htmlGastos = '<tr><td colspan="3" class="text-center text-muted">No hay gastos o retiros registrados.</td></tr>';
    } else {
        gastos.forEach(function (item) {
            htmlGastos += '' +
                '<tr>' +
                    '<td>' + escaparTextoCuadreDia(item.tipo || '') + '</td>' +
                    '<td>' + escaparTextoCuadreDia(item.cuenta || '') + '</td>' +
                    '<td class="text-right font-weight-bold">' + formatoMoneda(item.monto || 0) + '</td>' +
                '</tr>';
        });
    }

    $('#cd_tabla_gastos tbody').html(htmlGastos);

    var htmlInversiones = '';

    if (!inversiones || inversiones.length <= 0) {
        htmlInversiones = '' +
            '<tr>' +
                '<td>Reposición sugerida</td>' +
                '<td>Inventario vendido</td>' +
                '<td class="text-right font-weight-bold">' + formatoMoneda(inversionSugerida) + '</td>' +
            '</tr>';
    } else {
        inversiones.forEach(function (item) {
            htmlInversiones += '' +
                '<tr>' +
                    '<td>' + escaparTextoCuadreDia(item.tipo || '') + '</td>' +
                    '<td>' + escaparTextoCuadreDia(item.cuenta || '') + '</td>' +
                    '<td class="text-right font-weight-bold">' + formatoMoneda(item.monto || 0) + '</td>' +
                '</tr>';
        });
    }

    if (inversionPendiente > 0 && inversionManual > 0) {
        htmlInversiones += '' +
            '<tr>' +
                '<td>Reposición pendiente</td>' +
                '<td>Inventario vendido</td>' +
                '<td class="text-right font-weight-bold">' + formatoMoneda(inversionPendiente) + '</td>' +
            '</tr>';
    }

    if (inversionNoCubierta > 0) {
        htmlInversiones += '' +
            '<tr>' +
                '<td>Reposición no cubierta</td>' +
                '<td>No hay suficiente cobro para cubrir todo el costo</td>' +
                '<td class="text-right font-weight-bold text-danger">' + formatoMoneda(inversionNoCubierta) + '</td>' +
            '</tr>';
    }

    $('#cd_tabla_inversiones tbody').html(htmlInversiones);

    var resumenFormula = '' +
        '<div class="alert alert-light border mt-3 mb-0">' +
            '<strong>Lectura rápida:</strong> de ' + formatoMoneda(totalCobrado) +
            ' cobrado, se separan ' + formatoMoneda(inversionTotal) +
            ' para inversión/reposición y ' + formatoMoneda(gastosTotal) +
            ' en gastos/retiros. Resultado esperado: <strong>' + formatoMoneda(totalFinalEsperado) + '</strong>.' +
        '</div>';

    if ($('#cd_resumen_formula').length === 0) {
        $('#cd_formula_resultado').closest('.card').after('<div id="cd_resumen_formula"></div>');
    }

    $('#cd_resumen_formula').html(resumenFormula);
}

function imprimirCuadreDiaCaja() {
    var contenido = document.getElementById('cd_ticket_area');

    if (!contenido) {
        showNotify('error', 'Error', 'No se encontró el contenido del cuadre para imprimir.');
        return;
    }

    var ventana = window.open('', '_blank', 'width=900,height=700');

    if (!ventana) {
        showNotify('error', 'Error', 'El navegador bloqueó la ventana de impresión.');
        return;
    }

    ventana.document.write('<html><head><title>Cuadre del Día</title>');
    ventana.document.write('<style>');
    ventana.document.write('body{font-family:Arial,sans-serif;font-size:12px;color:#111;}');
    ventana.document.write('.alert{border:1px solid #9ec5fe;padding:8px;margin-bottom:10px;}');
    ventana.document.write('.row{display:block;}');
    ventana.document.write('.card{border:1px solid #ddd;margin-bottom:10px;}');
    ventana.document.write('.card-header{font-weight:bold;background:#f3f4f6;padding:6px;}');
    ventana.document.write('.card-body{padding:8px;}');
    ventana.document.write('table{width:100%;border-collapse:collapse;margin-bottom:8px;}');
    ventana.document.write('th,td{border:1px solid #ddd;padding:5px;}');
    ventana.document.write('.text-right{text-align:right;} .font-weight-bold{font-weight:bold;} .text-success{color:#198754;} .text-primary{color:#0d6efd;} .text-danger{color:#dc3545;} .text-warning{color:#b7791f;}');
    ventana.document.write('@media print{button{display:none;}}');
    ventana.document.write('</style>');
    ventana.document.write('</head><body>');
    ventana.document.write('<h3>Cuadre del Día</h3>');
    ventana.document.write('<p>' + ($('#cd_contexto_caja').text() || '') + '</p>');
    ventana.document.write(contenido.innerHTML);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();

    setTimeout(function () {
        ventana.print();
    }, 300);
}

/* =========================================================
   DETALLE RETIROS CAJA
   ========================================================= */
function cargarDetalleRetirosCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';
    }

    if (modo === 'caja' && apertura_id <= 0) {
        showNotify('error', 'Error', 'No se recibió una apertura válida.');
        return;
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $('#dr_apertura_id').val(apertura_id);
    $('#modalDetalleRetirosCaja').data('modo', modo);
    $('#modalDetalleRetirosCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        $('#dr_contexto_caja').html('Desde ' + fechai + ' hasta ' + fechaf);
    } else {
        $('#dr_contexto_caja').html('Apertura de caja #' + apertura_id);
    }

    $('#dr_total_retiros').html('Cargando...');
    $('#dr_estado_caja').html('Cargando...');
    $('#dr_accion_permitida').html('Cargando...');

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getRetirosCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        success: function (response) {
            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo cargar el detalle de retiros.');
                return;
            }

            var resumen = response.resumen || {};
            var detalles = response.data || [];

            $('#dr_total_retiros').html(formatoMoneda(resumen.total_retiros || 0));

            if (modo === 'periodo') {
                $('#dr_estado_caja').html('<span class="badge badge-info">Período</span>');
                $('#dr_accion_permitida').html('<span class="badge badge-warning">Depende de cada caja</span>');
            } else {
                if (parseInt(resumen.estado_caja || 0) === 1) {
                    $('#dr_estado_caja').html('<span class="badge badge-success">Abierta</span>');
                    $('#dr_accion_permitida').html('<span class="badge badge-success">Puede reintegrar</span>');
                } else {
                    $('#dr_estado_caja').html('<span class="badge badge-secondary">Cerrada</span>');
                    $('#dr_accion_permitida').html('<span class="badge badge-danger">No puede reintegrar</span>');
                }
            }

            cargarTablaDetalleRetirosCaja(detalles);

            $('#modalDetalleRetirosCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar los retiros de caja.');
        }
    });
}

function refrescarDetalleRetirosCaja() {
    var apertura_id = parseInt($('#modalDetalleRetirosCaja').data('apertura_id') || $('#dr_apertura_id').val() || 0);
    var modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';

    cargarDetalleRetirosCaja(apertura_id, modo);
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLE RETIROS CAJA
   ========================================================= */
function construirHeaderFooterDetalleRetirosCaja() {
    var $tabla = $('#dataTableDetalleRetirosCaja');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Caja</th>' +
                '<th>Fecha</th>' +
                '<th>Motivo</th>' +
                '<th>Observación</th>' +
                '<th>Cuenta</th>' +
                '<th>Egreso</th>' +
                '<th>Monto</th>' +
                '<th>Estado</th>' +
                '<th>Registrado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="7" class="text-right">Total activo:</th>' +
                '<th id="dr_footer_total">L. 0.00</th>' +
                '<th colspan="2"></th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   DATATABLE - DETALLE RETIROS CAJA
   ========================================================= */
function cargarTablaDetalleRetirosCaja(detalles) {
    if ($.fn.DataTable.isDataTable('#dataTableDetalleRetirosCaja')) {
        $('#dataTableDetalleRetirosCaja').DataTable().clear().destroy();
    }

    construirHeaderFooterDetalleRetirosCaja();

    $('#dataTableDetalleRetirosCaja').DataTable({
        destroy: true,
        autoWidth: false,
        data: detalles,
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }

                    if (parseInt(row.puede_reintegrar || 0) === 1) {
                        return '' +
                            '<button type="button" class="btn btn-sm btn-success btn-reintegrar-retiro" ' +
                                'data-caja-retiros-id="' + row.caja_retiros_id + '" ' +
                                'data-apertura-id="' + row.apertura_id + '" ' +
                                'data-monto="' + row.monto + '">' +
                                '<i class="fas fa-undo-alt"></i> Reintegrar' +
                            '</button>';
                    }

                    return '<span class="badge badge-secondary">No disponible</span>';
                }
            },
            {
                data: "apertura_id",
                className: "text-center text-nowrap",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    var estadoCaja = parseInt(row.estado_caja || 0) === 1
                        ? '<span class="badge badge-success ml-1">Abierta</span>'
                        : '<span class="badge badge-secondary ml-1">Cerrada</span>';

                    return '#' + data + ' ' + estadoCaja;
                }
            },
            { data: "fecha" },
            { data: "motivo" },
            { data: "observacion" },
            { data: "cuenta" },
            { data: "factura_egreso" },
            {
                data: "monto",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "estado_label",
                className: "text-center",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    if (parseInt(row.estado || 0) === 1) {
                        return '<span class="badge badge-success">Activo</span>';
                    }

                    return '<span class="badge badge-danger">Anulado</span>';
                }
            },
            { data: "fecha_registro" }
        ],
        columnDefs: [
            {
                targets: [7],
                className: "text-right text-nowrap"
            },
            {
                targets: [0, 1, 2, 6, 8, 9],
                className: "text-center text-nowrap"
            }
        ],
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Detalle de Retiros de Caja',
                className: 'btn btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Detalle de Retiros de Caja',
                className: 'btn btn-danger'
            }
        ],
        footerCallback: function () {
            var api = this.api();

            var total = api.rows({ page: 'current' }).data().reduce(function (acum, row) {
                if (parseInt(row.estado || 0) === 1) {
                    return acum + parseMonto(row.monto);
                }

                return acum;
            }, 0);

            $('#dr_footer_total').html('<span>' + formatoMoneda(total) + '</span>');
        },
        drawCallback: function () {
            $('.btn-reintegrar-retiro').off('click').on('click', function () {
                abrirModalReintegroRetiroCaja(
                    $(this).data('caja-retiros-id'),
                    $(this).data('apertura-id'),
                    $(this).data('monto')
                );
            });
        }
    });
}

/* =========================================================
   REINTEGRO DE RETIROS
   ========================================================= */
function abrirModalReintegroRetiroCaja(caja_retiros_id, apertura_id, monto) {
    caja_retiros_id = parseInt(caja_retiros_id || 0);
    apertura_id = parseInt(apertura_id || 0);
    monto = parseMonto(monto);

    if (caja_retiros_id <= 0 || apertura_id <= 0 || monto <= 0) {
        showNotify('error', 'Error', 'No se pudo cargar la información del retiro.');
        return;
    }

    $('#formReintegroRetiroCaja')[0].reset();

    $('#reintegro_caja_retiros_id').val(caja_retiros_id);
    $('#reintegro_apertura_id').val(apertura_id);
    $('#reintegro_monto_actual').val(monto.toFixed(2));
    $('#reintegro_monto_actual_text').html(formatoMoneda(monto));

    $('#reintegro_monto')
        .attr('max', monto.toFixed(2))
        .val('')
        .prop('readonly', false)
        .prop('disabled', false);

    $('#modalReintegroRetiroCaja')
        .off('shown.bs.modal')
        .on('shown.bs.modal', function () {
            setTimeout(function () {
                $('#reintegro_monto').trigger('focus').select();
            }, 150);
        });

    $('#modalReintegroRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formReintegroRetiroCaja').off('submit').on('submit', function (e) {
    e.preventDefault();

    var apertura_id = parseInt($('#reintegro_apertura_id').val() || 0);
    var montoActual = parseMonto($('#reintegro_monto_actual').val());
    var montoReintegro = parseMonto($('#reintegro_monto').val());

    if (montoReintegro <= 0) {
        showNotify('error', 'Error', 'Ingrese un monto válido para reintegrar.');
        return;
    }

    if (montoReintegro > montoActual) {
        showNotify('error', 'Error', 'El monto a reintegrar no puede ser mayor al retiro actual.');
        return;
    }

    $('#btnGuardarReintegroRetiroCaja').prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/reintegrarRetiroCaja.php',
        dataType: 'json',
        data: $('#formReintegroRetiroCaja').serialize(),
        success: function (response) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);

            if (!response.success) {
                showNotify('error', 'Error', response.message || 'No se pudo realizar el reintegro.');
                return;
            }

            $('#modalReintegroRetiroCaja').modal('hide');

            showNotify('success', 'Éxito', response.message || 'Reintegro registrado correctamente.');

            refrescarDetalleRetirosCaja();
            listar_registro_cajas();

            if ($('#modalDesgloseGananciaCaja').hasClass('show')) {
                refrescarDesgloseGananciaCaja();
            }

            if ($('#modalCuadreDiaCaja').hasClass('show')) {
                refrescarCuadreDiaCaja();
            }
        },
        error: function (xhr) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al registrar el reintegro.');
        }
    });
});

/* =========================================================
   DESGLOSE GANANCIA CAJA - CORREGIDO
   ========================================================= */
function cargarDesgloseGananciaCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechai = $("#formMainCajas #fecha_cajas").val();
    var fechaf = $("#formMainCajas #fecha_cajas_f").val();

    if (!modo) {
        modo = 'caja';
    }

    $('#dg_apertura_id').val(apertura_id);
    $('#dg_modo').val(modo);
    $('#modalDesgloseGananciaCaja').data('modo', modo);
    $('#modalDesgloseGananciaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Error', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getDesgloseGananciaCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            modo: modo,
            fechai: fechai,
            fechaf: fechaf
        },
        beforeSend: function () {
            if (modo === 'periodo') {
                $('#titulo_modal_ganancia').html('Resumen de caja y ganancia del período');
                $('#dg_contexto_consulta').html('Desde ' + fechai + ' hasta ' + fechaf);
            } else {
                $('#titulo_modal_ganancia').html('Resumen de caja y ganancia');
                $('#dg_contexto_consulta').html('Apertura de caja #' + apertura_id);
            }

            $('#dg_total_vendido').html('Cargando...');
            $('#dg_pendiente_cobro').html('Cargando...');
            $('#dg_otros_ingresos').html('Cargando...');
            $('#dg_total_gastos').html('Cargando...');
            $('#dg_total_egresos_registrados').html('Cargando...');
            $('#dg_total_inversion_apartada').html('Cargando...');
            $('#dg_retiro_caja_pendiente').html('Cargando...');
            $('#dg_neto_disponible').html('Cargando...');
            $('#dg_neto_total_facturado').html('Cargando...');

            $('#dg_efectivo').html('Cargando...');
            $('#dg_transferencia').html('Cargando...');
            $('#dg_tarjeta').html('Cargando...');
            $('#dg_cheque').html('Cargando...');

            $('#dg_monto_apertura').html('Cargando...');
            $('#dg_efectivo_caja').html('Cargando...');
            $('#dg_retiro_caja_total').html('Cargando...');
            $('#dg_retiro_caja_convertido').html('Cargando...');
            $('#dg_efectivo_esperado_caja').html('Cargando...');

            $('#dg_total_vendido_detalle').html('Cargando...');
            $('#dg_costo_productos').html('Cargando...');
            $('#dg_ganancia_bruta').html('Cargando...');
            $('#dg_dinero_recomendado_guardar').html('Cargando...');
            $('#dg_dinero_despues_reponer').html('Cargando...');
            $('#dg_porcentaje_costo').html('0.00%');
            $('#dg_porcentaje_ganancia').html('0.00%');
            $('#dg_diferencia_conciliacion').html('Cargando...');
            $('#dg_isv_factura_normal_sar').html('Cargando...');
            $('#dg_isv_proforma_informativo').html('Cargando...');
            $('#dg_isv_total_detalle').html('Cargando...');
        },
        success: function (response) {
            if (!response || !response.success) {
                showNotify(
                    'error',
                    'Error',
                    response && response.message ? response.message : 'No se pudo cargar el desglose de ganancia.'
                );
                return;
            }

            var resumen = response.resumen || {};
            var detalles = response.detalles || [];

            var totalVendido = parseMonto(resumen.total_vendido || resumen.total_cobrado);
            var pendienteCobro = parseMonto(resumen.pendiente_cobro);
            var otrosIngresos = parseMonto(resumen.otros_ingresos);
            var totalGastos = parseMonto(resumen.total_gastos_reales || resumen.total_gastos);
            var totalEgresosRegistrados = parseMonto(resumen.total_egresos_registrados);
            var totalInversionApartada = parseMonto(resumen.total_inversion_apartada);
            var retiroCajaPendiente = parseMonto(resumen.retiro_caja_pendiente);
            var netoDisponible = parseMonto(resumen.neto_disponible);
            var netoTotalFacturado = parseMonto(resumen.neto_total_facturado);

            if (netoTotalFacturado <= 0) {
                netoTotalFacturado = netoDisponible + pendienteCobro;
            }

            var efectivo = parseMonto(resumen.efectivo);
            var transferencia = parseMonto(resumen.transferencia);
            var tarjeta = parseMonto(resumen.tarjeta);
            var cheque = parseMonto(resumen.cheque);

            var montoApertura = parseMonto(resumen.monto_apertura);
            var retiroCajaTotal = parseMonto(resumen.retiro_caja_total || resumen.retiro_caja);
            var retiroCajaConvertido = parseMonto(resumen.retiro_caja_convertido_gasto);
            var efectivoEsperadoCaja = parseMonto(resumen.efectivo_esperado_caja);

            var totalVendidoDetalle = parseMonto(resumen.total_vendido_detalle);
            var costoProductos = parseMonto(resumen.costo_productos_vendidos);
            var gananciaBruta = parseMonto(resumen.ganancia_bruta);
            var dineroRecomendadoGuardar = parseMonto(resumen.dinero_recomendado_guardar);
            var dineroDespuesReponer = parseMonto(resumen.dinero_despues_reponer);
            var porcentajeCosto = parseMonto(resumen.porcentaje_costo);
            var porcentajeGanancia = parseMonto(resumen.porcentaje_ganancia);
            var diferenciaConciliacion = parseMonto(resumen.diferencia_conciliacion);

            // =====================================================
            // VALORES ISV - DIRECTAMENTE DEL PHP, SIN MODIFICAR
            // =====================================================
            var isvFacturaNormalSar = parseMonto(resumen.isv_factura_normal_sar || 0);
            var isvProformaInformativo = parseMonto(resumen.isv_proforma_informativo || 0);
            var isvTotalDetalle = parseMonto(resumen.isv_total_detalle || 0);

            // SOLO REDONDEAMOS PARA MOSTRAR - NO MODIFICAMOS LA LÓGICA
            isvFacturaNormalSar = Math.round(isvFacturaNormalSar * 100) / 100;
            isvProformaInformativo = Math.round(isvProformaInformativo * 100) / 100;
            isvTotalDetalle = Math.round(isvTotalDetalle * 100) / 100;

            // MOSTRAMOS LOS VALORES DIRECTAMENTE SIN NINGUNA MODIFICACIÓN
            $('#dg_total_vendido').html(formatoMoneda(totalVendido));
            $('#dg_pendiente_cobro').html(formatoMoneda(pendienteCobro));
            $('#dg_otros_ingresos').html(formatoMoneda(otrosIngresos));
            $('#dg_total_gastos').html(formatoMoneda(totalGastos));
            $('#dg_total_egresos_registrados').html(formatoMoneda(totalEgresosRegistrados));
            $('#dg_total_inversion_apartada').html(formatoMoneda(totalInversionApartada));
            $('#dg_retiro_caja_pendiente').html(formatoMoneda(retiroCajaPendiente));
            $('#dg_neto_disponible').html(formatoMoneda(netoDisponible));
            $('#dg_neto_total_facturado').html(formatoMoneda(netoTotalFacturado));

            $('#dg_efectivo').html(formatoMoneda(efectivo));
            $('#dg_transferencia').html(formatoMoneda(transferencia));
            $('#dg_tarjeta').html(formatoMoneda(tarjeta));
            $('#dg_cheque').html(formatoMoneda(cheque));

            $('#dg_monto_apertura').html(formatoMoneda(montoApertura));
            $('#dg_efectivo_caja').html(formatoMoneda(efectivo));
            $('#dg_retiro_caja_total').html(formatoMoneda(retiroCajaTotal));
            $('#dg_retiro_caja_convertido').html(formatoMoneda(retiroCajaConvertido));
            $('#dg_efectivo_esperado_caja').html(formatoMoneda(efectivoEsperadoCaja));

            $('#dg_total_vendido_detalle').html(formatoMoneda(totalVendidoDetalle));
            $('#dg_costo_productos').html(formatoMoneda(costoProductos));
            $('#dg_ganancia_bruta').html(formatoMoneda(gananciaBruta));
            $('#dg_dinero_recomendado_guardar').html(formatoMoneda(dineroRecomendadoGuardar));
            $('#dg_dinero_despues_reponer').html(formatoMoneda(dineroDespuesReponer));
            $('#dg_porcentaje_costo').html(porcentajeCosto.toFixed(2) + '%');
            $('#dg_porcentaje_ganancia').html(porcentajeGanancia.toFixed(2) + '%');
            $('#dg_diferencia_conciliacion').html(formatoMoneda(diferenciaConciliacion));
            
            // ISV - VALORES DIRECTOS DEL PHP
            $('#dg_isv_factura_normal_sar').html(formatoMoneda(isvFacturaNormalSar));
            $('#dg_isv_proforma_informativo').html(formatoMoneda(isvProformaInformativo));
            $('#dg_isv_total_detalle').html(formatoMoneda(isvTotalDetalle));

            var textoRegla = '';

            if (pendienteCobro > 0) {
                textoRegla = 'Hay facturas pendientes de cobrar. Por eso el neto disponible puede ser menor que el total facturado.';
            } else if (totalInversionApartada > 0) {
                textoRegla = 'Hay egresos marcados como inversión/reposición. Salen de caja, pero no se cuentan como gasto real.';
            } else if (retiroCajaConvertido > 0) {
                textoRegla = 'Esta caja ya tiene retiros convertidos en gasto. Por eso no se restan doble en el neto.';
            } else {
                textoRegla = 'Los retiros pendientes todavía no son egreso. Por eso sí se restan del neto disponible.';
            }

            $('#dg_regla_retiros').html(textoRegla);

            cargarTablaDetalleGananciaCaja(detalles);

            $('#modalDesgloseGananciaCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            showNotify('error', 'Error', 'Error de comunicación al cargar el desglose de ganancia.');
        }
    });
}

function refrescarDesgloseGananciaCaja() {
    var apertura_id = parseInt($('#dg_apertura_id').val() || $('#modalDesgloseGananciaCaja').data('apertura_id') || 0);
    var modo = $('#dg_modo').val() || $('#modalDesgloseGananciaCaja').data('modo') || 'caja';

    cargarDesgloseGananciaCaja(apertura_id, modo);
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLE GANANCIA CAJA
   ========================================================= */
function construirHeaderFooterDetalleGananciaCaja() {
    var $tabla = $('#dataTableDetalleGananciaCaja');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Factura</th>' +
                '<th>Tipo</th>' +
                '<th>Producto</th>' +
                '<th>Cantidad</th>' +
                '<th>Costo Unit.</th>' +
                '<th>Precio Venta</th>' +
                '<th>ISV</th>' +
                '<th>Total Costo</th>' +
                '<th>Total Venta</th>' +
                '<th>Total c/ISV</th>' +
                '<th>Ganancia</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="6" class="text-right">Totales:</th>' +
                '<th id="dg_footer_total_isv">L. 0.00</th>' +
                '<th id="dg_footer_total_costo">L. 0.00</th>' +
                '<th id="dg_footer_total_venta">L. 0.00</th>' +
                '<th id="dg_footer_total_con_isv">L. 0.00</th>' +
                '<th id="dg_footer_total_ganancia">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   DATATABLE - DETALLE GANANCIA CAJA
   ========================================================= */
function cargarTablaDetalleGananciaCaja(detalles) {
    if ($.fn.DataTable.isDataTable('#dataTableDetalleGananciaCaja')) {
        $('#dataTableDetalleGananciaCaja').DataTable().clear().destroy();
    }

    construirHeaderFooterDetalleGananciaCaja();

    $('#dataTableDetalleGananciaCaja').DataTable({
        destroy: true,
        autoWidth: false,
        data: detalles,
        columns: [
            { data: "factura" },
            { data: "tipo_documento" },
            { data: "producto" },
            { data: "cantidad" },
            {
                data: "costo_unitario",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "precio_venta",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "isv_detalle",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "total_costo",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "total_venta",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "total_con_isv",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: "ganancia",
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            }
        ],
        columnDefs: [
            {
                targets: [3],
                className: "text-center text-nowrap"
            },
            {
                targets: [4, 5, 6, 7, 8, 9, 10],
                className: "text-right text-nowrap"
            }
        ],
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Detalle de Ganancia de Caja',
                className: 'btn btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Detalle de Ganancia de Caja',
                className: 'btn btn-danger'
            }
        ],
        footerCallback: function () {
            var api = this.api();

            var totalIsv = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalCosto = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalVenta = api.column(8, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalConIsv = api.column(9, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalGanancia = api.column(10, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            $('#dg_footer_total_isv').html('<span>' + formatoMoneda(totalIsv) + '</span>');
            $('#dg_footer_total_costo').html('<span>' + formatoMoneda(totalCosto) + '</span>');
            $('#dg_footer_total_venta').html('<span>' + formatoMoneda(totalVenta) + '</span>');
            $('#dg_footer_total_con_isv').html('<span>' + formatoMoneda(totalConIsv) + '</span>');
            $('#dg_footer_total_ganancia').html('<span>' + formatoMoneda(totalGanancia) + '</span>');
        }
    });
}

/* =========================================================
   CAJAS | DROPDOWN DE ACCIONES ADAPTATIVO
   Funciona en vista detalle y miniatura.
   - sólo un menú abierto;
   - eleva únicamente su fila/card;
   - decide abajo / arriba / derecha / izquierda según el viewport.
   ========================================================= */
function limpiarDireccionDropdownCajas($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    $dropdown.removeClass('dropup dropright dropleft');
    $dropdown.children('.dropdown-menu')
        .removeClass('dropdown-menu-right')
        .removeAttr('x-placement data-popper-placement')
        .css({ top: '', left: '', right: '', bottom: '', transform: '' });
}

function medirMenuDropdownCajas($menu) {
    var menu = $menu && $menu.length ? $menu[0] : null;

    if (!menu) {
        return { width: 220, height: 220 };
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
        width: Math.max(rect.width || 0, 220),
        height: Math.max(rect.height || 0, 1)
    };
}

function prepararDireccionDropdownCajas($dropdown) {
    if (!$dropdown || !$dropdown.length) {
        return;
    }

    var $button = $dropdown.children('.js-acciones-toggle');
    var $menu = $dropdown.children('.dropdown-menu');
    var button = $button.length ? $button[0] : null;

    if (!button || !$menu.length) {
        return;
    }

    limpiarDireccionDropdownCajas($dropdown);

    var rect = button.getBoundingClientRect();
    var menuSize = medirMenuDropdownCajas($menu);
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

function cerrarDropdownsCajasExcepto($actual) {
    $('#cajasListado .cajas-actions-dropdown').each(function () {
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
        limpiarDireccionDropdownCajas($dropdown);
        $dropdown.closest('.cajas-detail-row, .cajas-mini-card').removeClass('cajas-dropdown-open');
    });
}

function inicializarDropdownAccionesCajas() {
    $('#cajasListado')
        .off('click.cajasDropdownAdaptativo', '.cajas-actions-dropdown .js-acciones-toggle')
        .on('click.cajasDropdownAdaptativo', '.cajas-actions-dropdown .js-acciones-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            var $button = $(this);
            var $dropdown = $button.closest('.cajas-actions-dropdown');
            var $menu = $dropdown.children('.dropdown-menu');
            var estabaAbierto = $menu.hasClass('show');

            if (typeof $.fn.dropdown !== 'function') {
                return;
            }

            cerrarDropdownsCajasExcepto($dropdown);

            if (estabaAbierto) {
                try {
                    $button.dropdown('hide');
                } catch (error) {
                    $dropdown.removeClass('show');
                    $menu.removeClass('show');
                }

                $button.attr('aria-expanded', 'false');
                limpiarDireccionDropdownCajas($dropdown);
                $dropdown.closest('.cajas-detail-row, .cajas-mini-card').removeClass('cajas-dropdown-open');
                return;
            }

            try {
                prepararDireccionDropdownCajas($dropdown);

                $button.dropdown({
                    boundary: 'viewport',
                    flip: true,
                    offset: '0,6'
                });

                $button.dropdown('show');
                $dropdown.closest('.cajas-detail-row, .cajas-mini-card').addClass('cajas-dropdown-open');
            } catch (error) {
                console.error('No se pudo abrir el dropdown de acciones de Cajas:', error);
            }
        });

    $(document)
        .off('shown.bs.dropdown.cajasDropdownAdaptativo', '#cajasListado .cajas-actions-dropdown')
        .on('shown.bs.dropdown.cajasDropdownAdaptativo', '#cajasListado .cajas-actions-dropdown', function () {
            var $dropdown = $(this);
            cerrarDropdownsCajasExcepto($dropdown);
            $dropdown.closest('.cajas-detail-row, .cajas-mini-card').addClass('cajas-dropdown-open');
        })
        .off('hidden.bs.dropdown.cajasDropdownAdaptativo', '#cajasListado .cajas-actions-dropdown')
        .on('hidden.bs.dropdown.cajasDropdownAdaptativo', '#cajasListado .cajas-actions-dropdown', function () {
            var $dropdown = $(this);
            limpiarDireccionDropdownCajas($dropdown);
            $dropdown.closest('.cajas-detail-row, .cajas-mini-card').removeClass('cajas-dropdown-open');
        });
}

</script>