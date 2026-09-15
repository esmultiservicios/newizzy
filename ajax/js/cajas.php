<script>

/* =========================================================
   IZZY | XLSX - BORDES COMPLETOS EN RANGOS COMBINADOS
   Mantiene intacto el contenido del reporte y completa las
   celdas internas de mergeCells con el estilo ya existente.
   ========================================================= */
if (typeof window.izzyExcelCompletarBordesCombinados !== 'function') {
    window.izzyExcelCompletarBordesCombinados = function (xmlTexto) {
        if (
            !xmlTexto ||
            typeof DOMParser === 'undefined' ||
            typeof XMLSerializer === 'undefined'
        ) {
            return xmlTexto;
        }

        try {
            var declaracion = '';
            var matchDeclaracion = String(xmlTexto).match(
                /^\s*(<\?xml[^>]*\?>)/
            );

            if (matchDeclaracion) {
                declaracion = matchDeclaracion[1];
            }

            var parser = new DOMParser();
            var documento = parser.parseFromString(
                String(xmlTexto),
                'application/xml'
            );

            if (documento.getElementsByTagName('parsererror').length) {
                return xmlTexto;
            }

            var namespaceUri =
                documento.documentElement.namespaceURI ||
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

            var sheetData =
                documento.getElementsByTagName('sheetData')[0];

            var mergeCells =
                documento.getElementsByTagName('mergeCells')[0];

            if (!sheetData || !mergeCells) {
                return xmlTexto;
            }

            function columnaNumero(letras) {
                var total = 0;
                var texto = String(letras || '').toUpperCase();

                for (var i = 0; i < texto.length; i++) {
                    total = (total * 26) +
                        (texto.charCodeAt(i) - 64);
                }

                return total;
            }

            function columnaLetras(numero) {
                var resultado = '';
                var n = numero;

                while (n > 0) {
                    var resto = (n - 1) % 26;
                    resultado =
                        String.fromCharCode(65 + resto) +
                        resultado;
                    n = Math.floor((n - 1) / 26);
                }

                return resultado;
            }

            function parseReferencia(ref) {
                var match = String(ref || '').match(
                    /^([A-Z]+)(\d+)$/
                );

                if (!match) {
                    return null;
                }

                return {
                    col: columnaNumero(match[1]),
                    row: parseInt(match[2], 10)
                };
            }

            function obtenerFila(numeroFila) {
                var filas = sheetData.getElementsByTagName('row');

                for (var i = 0; i < filas.length; i++) {
                    if (
                        parseInt(
                            filas[i].getAttribute('r'),
                            10
                        ) === numeroFila
                    ) {
                        return filas[i];
                    }
                }

                var nuevaFila = documento.createElementNS(
                    namespaceUri,
                    'row'
                );

                nuevaFila.setAttribute(
                    'r',
                    String(numeroFila)
                );

                var insertada = false;

                for (var j = 0; j < filas.length; j++) {
                    var actual = parseInt(
                        filas[j].getAttribute('r'),
                        10
                    );

                    if (actual > numeroFila) {
                        sheetData.insertBefore(
                            nuevaFila,
                            filas[j]
                        );
                        insertada = true;
                        break;
                    }
                }

                if (!insertada) {
                    sheetData.appendChild(nuevaFila);
                }

                return nuevaFila;
            }

            function buscarCelda(fila, referencia) {
                var celdas = fila.getElementsByTagName('c');

                for (var i = 0; i < celdas.length; i++) {
                    if (
                        celdas[i].getAttribute('r') ===
                        referencia
                    ) {
                        return celdas[i];
                    }
                }

                return null;
            }

            function insertarCeldaOrdenada(fila, celda, colNumero) {
                var celdas = fila.getElementsByTagName('c');

                for (var i = 0; i < celdas.length; i++) {
                    var refActual = parseReferencia(
                        celdas[i].getAttribute('r')
                    );

                    if (
                        refActual &&
                        refActual.col > colNumero
                    ) {
                        fila.insertBefore(
                            celda,
                            celdas[i]
                        );
                        return;
                    }
                }

                fila.appendChild(celda);
            }

            var merges = Array.prototype.slice.call(
                mergeCells.getElementsByTagName('mergeCell')
            );

            merges.forEach(function (merge) {
                var ref = merge.getAttribute('ref') || '';
                var partes = ref.split(':');

                if (partes.length !== 2) {
                    return;
                }

                var inicio = parseReferencia(partes[0]);
                var fin = parseReferencia(partes[1]);

                if (!inicio || !fin) {
                    return;
                }

                /* Elimina combinaciones inválidas como I3:I3. */
                if (
                    inicio.col === fin.col &&
                    inicio.row === fin.row
                ) {
                    mergeCells.removeChild(merge);
                    return;
                }

                var filaInicio = obtenerFila(inicio.row);
                var celdaInicio = buscarCelda(
                    filaInicio,
                    columnaLetras(inicio.col) +
                    inicio.row
                );

                if (!celdaInicio) {
                    return;
                }

                var estilo = celdaInicio.getAttribute('s');

                /*
                 * Completa todo el rango con el mismo estilo
                 * ya definido por el reporte. No crea estilos nuevos.
                 */
                for (
                    var filaNumero = inicio.row;
                    filaNumero <= fin.row;
                    filaNumero++
                ) {
                    var fila = obtenerFila(filaNumero);

                    for (
                        var colNumero = inicio.col;
                        colNumero <= fin.col;
                        colNumero++
                    ) {
                        var referencia =
                            columnaLetras(colNumero) +
                            filaNumero;

                        var celda = buscarCelda(
                            fila,
                            referencia
                        );

                        if (!celda) {
                            celda = documento.createElementNS(
                                namespaceUri,
                                'c'
                            );

                            celda.setAttribute(
                                'r',
                                referencia
                            );

                            if (
                                estilo !== null &&
                                estilo !== ''
                            ) {
                                celda.setAttribute(
                                    's',
                                    estilo
                                );
                            }

                            insertarCeldaOrdenada(
                                fila,
                                celda,
                                colNumero
                            );
                        }
                    }
                }
            });

            var mergeFinales =
                mergeCells.getElementsByTagName('mergeCell');

            mergeCells.setAttribute(
                'count',
                String(mergeFinales.length)
            );

            var serializado =
                new XMLSerializer().serializeToString(
                    documento.documentElement
                );

            return declaracion
                ? declaracion + serializado
                : serializado;

        } catch (error) {
            console.error(
                'No se pudieron completar los bordes del XLSX:',
                error
            );

            return xmlTexto;
        }
    };
}

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
var CAJAS_MOBILE_QUERY = '(max-width: 767.98px)';
var cajasVistaPreferida = 'detalle';

function cajasEsPantallaPequena() {
    return window.matchMedia
        ? window.matchMedia(CAJAS_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

$(() => {
    inicializarCajasUI();
    inicializarDropdownAccionesCajas();

    $("#formMainCajas #estado_cajas").val('0').trigger('change.select2');

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
                $("#formMainCajas #estado_cajas").val('0').trigger('change.select2');

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

    $('#buscarCajasLimpiar')
        .off('click.cajas')
        .on('click.cajas', function () {
            $('#buscarCajas').val('').focus();
            cajasState.busqueda = '';
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

    // Los modales de retiros y ganancia trabajan únicamente con DIV/Grid/Flex.
    inicializarListadosDetalleCajaDiv();
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

    var vistaGuardada = 'detalle';

    try {
        vistaGuardada = localStorage.getItem(CAJAS_STORAGE_VISTA) || 'detalle';
    } catch (error) {
        vistaGuardada = 'detalle';
    }

    cajasVistaPreferida = vistaGuardada === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    cajasState.vista = cajasEsPantallaPequena()
        ? 'miniatura'
        : cajasVistaPreferida;

    actualizarBotonesVistaCajas();
    sincronizarPageSizeCajas();

    var cajasResponsiveTimer = null;

    $(window)
        .off('resize.cajasResponsive orientationchange.cajasResponsive')
        .on('resize.cajasResponsive orientationchange.cajasResponsive', function () {
            clearTimeout(cajasResponsiveTimer);

            cajasResponsiveTimer = setTimeout(function () {
                var objetivo = cajasEsPantallaPequena()
                    ? 'miniatura'
                    : cajasVistaPreferida;

                actualizarBotonesVistaCajas();

                if (cajasState.vista === objetivo) {
                    return;
                }

                cajasState.vista = objetivo;
                cajasState.pagina = 1;
                actualizarBotonesVistaCajas();
                sincronizarPageSizeCajas();
                renderCajas();
            }, 120);
        });
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
    var siguiente = vista === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    if (cajasEsPantallaPequena()) {
        siguiente = 'miniatura';
    }

    cajasState.vista = siguiente;

    if (!cajasEsPantallaPequena()) {
        cajasVistaPreferida = siguiente;

        try {
            localStorage.setItem(CAJAS_STORAGE_VISTA, cajasVistaPreferida);
        } catch (error) {
            console.warn('No se pudo guardar la vista de cajas:', error);
        }
    }

    actualizarBotonesVistaCajas();
    sincronizarPageSizeCajas();
    cajasState.pagina = 1;
    renderCajas();
}

function actualizarBotonesVistaCajas() {
    var movil = cajasEsPantallaPequena();

    $('.cajas-view-btn[data-view="detalle"]')
        .prop('disabled', movil)
        .toggleClass('d-none', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

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

    $select.val(String(cajasState.porPagina)).trigger('change.select2');
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

    $('#retiro_categoria_gastos_id').val('').trigger('change.select2');

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
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
            '<mergeCells count="10">' +
                '<mergeCell ref="A1:I1"/>' +
                '<mergeCell ref="A2:I2"/>' +
                '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                '<mergeCell ref="G3:H3"/><mergeCell ref="G4:H4"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
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
                '<fill><patternFill patternType="solid"><fgColor rgb="FF1F354E"/></patternFill></fill>' +
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
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<sheets><sheet name="Cajas" sheetId="1" r:id="rId1"/></sheets>' +
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
    zip.folder('xl').folder('worksheets').file('sheet1.xml', window.izzyExcelCompletarBordesCombinados(sheetXml));

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


function cajasObtenerLogoPdf(callback) {
    function convertirLogo(source) {
        source = String(source || '').trim();

        if (!source) {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
            }
            return;
        }

        if (source.indexOf('data:image/') === 0) {
            callback(source);
            return;
        }

        var img = new Image();
        img.crossOrigin = 'Anonymous';

        img.onload = function () {
            try {
                var canvas = document.createElement('canvas');
                var ctx = canvas.getContext('2d');
                canvas.width = img.naturalWidth || img.width;
                canvas.height = img.naturalHeight || img.height;
                ctx.drawImage(img, 0, 0);
                var dataUrl = canvas.toDataURL('image/png');

                if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                    throw new Error('No se pudo convertir el logo a Data URL.');
                }

                try { imagen = dataUrl; } catch (e) {}
                callback(dataUrl);
            } catch (error) {
                console.error('Error preparando logo PDF:', error);
                if (typeof showNotify === 'function') {
                    showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
                }
            }
        };

        img.onerror = function () {
            if (typeof showNotify === 'function') {
                showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
            }
        };

        img.src = source;
    }

    if (typeof imagen !== 'undefined' && imagen) {
        convertirLogo(imagen);
        return;
    }

    $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL;?>core/get_image.php',
        dataType: 'text',
        timeout: 15000
    }).done(function (imageUrl) {
        convertirLogo(imageUrl);
    }).fail(function (xhr) {
        console.error('Error obteniendo logo PDF:', xhr.responseText);
        if (typeof showNotify === 'function') {
            showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
        }
    });
}

function cajasPdfLogoPlate(logoDataUrl) {
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

    var logoCell = cajasPdfLogoPlate(imagen);

    var header = {
        table: {
            widths: [100, '*', 155],
            body: [[
                {
                    border: [true, true, true, true],
                    fillColor: '#1F354E',
                    margin: [12, 10, 0, 10],
                    stack: [logoCell]
                },
                {
                    border: [true, true, true, true],
                    fillColor: '#1F354E',
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
                    border: [true, true, true, true],
                    fillColor: '#1F354E',
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
            hLineColor: function () { return '#DDE3EA'; },
            vLineColor: function () { return '#DDE3EA'; },
            hLineWidth: function () { return .55; },
            vLineWidth: function () { return .55; }
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
        {text: 'FECHA', style: 'th', fillColor: '#1F354E'},
        {text: 'USUARIO', style: 'th', fillColor: '#1F354E'},
        {text: 'FACTURA INICIAL', style: 'th', fillColor: '#1F354E'},
        {text: 'FACTURA FINAL', style: 'th', fillColor: '#1F354E'},
        {text: 'APERTURA', style: 'th', fillColor: '#1F354E'},
        {text: 'VENTA', style: 'th', fillColor: '#1F354E'},
        {text: 'RETIROS', style: 'th', fillColor: '#1F354E'},
        {text: 'NETO', style: 'th', fillColor: '#1F354E'},
        {text: 'ESTADO', style: 'th', fillColor: '#1F354E'}
    ]];

    rows.forEach(function (row, index) {
        var fill = '#FFFFFF';

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
    if (!(typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/') === 0)) {
        cajasObtenerLogoPdf(function (logoDataUrl) {
            try { imagen = logoDataUrl; } catch (e) {}
            previsualizarCajasPdfPremium();
        });
        return;
    }

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
   DETALLE RETIROS CAJA | LISTADO DIV / GRID / FLEX
   ========================================================= */
var cajasRetirosDetalleUI = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    view: 'detalle',
    search: ''
};

function cajaModalDetalleNormalizar(value) {
    var text = String(value === null || value === undefined ? '' : value)
        .toLowerCase();

    try {
        text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    } catch (error) {}

    return text;
}

function cajaModalDetalleCampo(label, value, className) {
    return '' +
        '<div class="cajas-modal-field ' + (className || '') + '">' +
            '<span class="cajas-modal-field-label">' + cajaEscape(label) + '</span>' +
            '<div class="cajas-modal-field-value">' +
                (value === null || value === undefined || value === '' ? '—' : value) +
            '</div>' +
        '</div>';
}

function cajaModalDetalleCelda(label, value, className) {
    return '' +
        '<div class="cajas-modal-detail-cell ' + (className || '') + '" ' +
            'data-label="' + cajaEscape(label) + '">' +
            '<div class="cajas-modal-detail-value">' +
                (value === null || value === undefined || value === '' ? '—' : value) +
            '</div>' +
        '</div>';
}

function cajaModalDetalleHeader(headers, gridClass) {
    return '' +
        '<div class="cajas-modal-detail-header cajas-modal-detail-grid ' + (gridClass || '') + '">' +
            headers.map(function (header) {
                return '<div>' + cajaEscape(header) + '</div>';
            }).join('') +
        '</div>';
}

function cajaModalDetalleInfo(selector, total, start, end) {
    $(selector).text(
        total === 0
            ? '0 registros'
            : 'Mostrando ' + (start + 1) + ' a ' + end + ' de ' + total + ' registros'
    );
}

function cajaModalDetallePaginacion(selector, state, listKey, totalPages) {
    var $paginacion = $(selector);

    if (!$paginacion.length) {
        return;
    }

    var current = state.page;
    var buttons = [];

    function add(label, page, disabled, active, title) {
        buttons.push(
            '<button type="button" class="' + (active ? 'active' : '') + '" ' +
                'data-caja-modal-list="' + cajaEscape(listKey) + '" ' +
                'data-page="' + page + '" ' +
                (disabled ? 'disabled ' : '') +
                'title="' + cajaEscape(title || '') + '">' +
                label +
            '</button>'
        );
    }

    add(
        '<i class="fas fa-angle-double-left"></i><span class="d-none d-md-inline ml-1">Inicio</span>',
        1,
        current <= 1,
        false,
        'Inicio'
    );

    add(
        '<i class="fas fa-angle-left"></i><span class="d-none d-md-inline ml-1">Anterior</span>',
        current - 1,
        current <= 1,
        false,
        'Anterior'
    );

    var from = Math.max(1, current - 2);
    var to = Math.min(totalPages, from + 4);
    from = Math.max(1, to - 4);

    for (var page = from; page <= to; page++) {
        add(String(page), page, false, page === current, 'Página ' + page);
    }

    add(
        '<span class="d-none d-md-inline mr-1">Siguiente</span><i class="fas fa-angle-right"></i>',
        current + 1,
        current >= totalPages,
        false,
        'Siguiente'
    );

    add(
        '<span class="d-none d-md-inline mr-1">Final</span><i class="fas fa-angle-double-right"></i>',
        totalPages,
        current >= totalPages,
        false,
        'Final'
    );

    $paginacion.html(buttons.join(''));
}

function cajaModalDetalleSincronizarVista(listKey, state) {
    $('.fm-view-btn[data-list="' + listKey + '"]')
        .removeClass('active')
        .attr('aria-pressed', 'false');

    $('.fm-view-btn[data-list="' + listKey + '"][data-view="' + state.view + '"]')
        .addClass('active')
        .attr('aria-pressed', 'true');
}

function cajaModalDetalleFiltrar(state, fields) {
    var query = cajaModalDetalleNormalizar(state.search).trim();

    state.filtered = !query
        ? state.rows.slice()
        : state.rows.filter(function (row) {
            return fields.some(function (field) {
                return cajaModalDetalleNormalizar(row && row[field]).indexOf(query) !== -1;
            });
        });

    var totalPages = Math.max(
        1,
        Math.ceil(state.filtered.length / state.pageSize)
    );

    if (state.page > totalPages) {
        state.page = totalPages;
    }

    if (state.page < 1) {
        state.page = 1;
    }
}

function cajaRetirosDetalleAccion(row) {
    if (parseInt(row.puede_reintegrar || 0, 10) === 1) {
        return '' +
            '<button type="button" class="btn btn-sm btn-success btn-reintegrar-retiro" ' +
                'data-caja-retiros-id="' + cajaEscape(row.caja_retiros_id || '') + '" ' +
                'data-apertura-id="' + cajaEscape(row.apertura_id || '') + '" ' +
                'data-monto="' + cajaEscape(row.monto || 0) + '">' +
                '<i class="fas fa-undo-alt mr-1"></i> Reintegrar' +
            '</button>';
    }

    return '<span class="badge badge-secondary">No disponible</span>';
}

function cajaRetirosDetalleEstadoCaja(row) {
    return parseInt(row.estado_caja || 0, 10) === 1
        ? '<span class="badge badge-success">Abierta</span>'
        : '<span class="badge badge-secondary">Cerrada</span>';
}

function cajaRetirosDetalleEstado(row) {
    return parseInt(row.estado || 0, 10) === 1
        ? '<span class="badge badge-success">Activo</span>'
        : '<span class="badge badge-danger">Anulado</span>';
}

function renderRetirosDetalleCaja() {
    var state = cajasRetirosDetalleUI;
    var $container = $('#dataTableDetalleRetirosCaja');

    if (!$container.length) {
        return;
    }

    cajaModalDetalleFiltrar(
        state,
        [
            'apertura_id',
            'fecha',
            'motivo',
            'observacion',
            'cuenta',
            'factura_egreso',
            'monto',
            'estado_label',
            'fecha_registro'
        ]
    );

    var total = state.filtered.length;
    var totalPages = Math.max(1, Math.ceil(total / state.pageSize));
    var start = (state.page - 1) * state.pageSize;
    var end = Math.min(start + state.pageSize, total);
    var visible = state.filtered.slice(start, end);

    $container
        .removeClass('cajas-modal-vista-detalle cajas-modal-vista-miniatura')
        .addClass(
            'cajas-modal-listado ' +
            (state.view === 'miniatura'
                ? 'cajas-modal-vista-miniatura'
                : 'cajas-modal-vista-detalle')
        );

    if (!visible.length) {
        $container.html(
            '<div class="cajas-modal-empty">' +
                '<i class="fas fa-money-bill-wave"></i>' +
                '<strong>Sin retiros</strong>' +
                '<span>No hay retiros que coincidan con los criterios actuales.</span>' +
            '</div>'
        );
    } else if (state.view === 'miniatura') {
        var miniHtml = '<div class="cajas-modal-mini-grid">';

        visible.forEach(function (row) {
            miniHtml += '' +
                '<article class="cajas-modal-mini-card">' +
                    '<div class="cajas-modal-mini-topline"></div>' +
                    '<div class="cajas-modal-mini-header">' +
                        '<div class="cajas-modal-mini-title">' +
                            '<strong>' + formatoMoneda(row.monto) + '</strong>' +
                            '<span>Caja #' + cajaEscape(row.apertura_id || '—') + '</span>' +
                        '</div>' +
                        cajaRetirosDetalleEstado(row) +
                    '</div>' +
                    '<div class="cajas-modal-mini-body">' +
                        cajaModalDetalleCampo('Fecha', cajaEscape(row.fecha || '—')) +
                        cajaModalDetalleCampo('Motivo', cajaEscape(row.motivo || '—')) +
                        cajaModalDetalleCampo('Cuenta', cajaEscape(row.cuenta || '—')) +
                        cajaModalDetalleCampo('Egreso', cajaEscape(row.factura_egreso || '—')) +
                    '</div>' +
                    '<div class="cajas-modal-mini-footer">' +
                        cajaRetirosDetalleAccion(row) +
                    '</div>' +
                '</article>';
        });

        miniHtml += '</div>';
        $container.html(miniHtml);
    } else {
        var detailHtml = '' +
            '<div class="cajas-modal-detail-table">' +
                cajaModalDetalleHeader(
                    [
                        'Acción',
                        'Caja / Estado',
                        'Fecha',
                        'Motivo',
                        'Observación',
                        'Cuenta',
                        'Egreso',
                        'Monto',
                        'Estado',
                        'Registrado'
                    ],
                    'is-retiros'
                ) +
                '<div class="cajas-modal-detail-body">';

        visible.forEach(function (row) {
            detailHtml += '' +
                '<article class="cajas-modal-detail-row cajas-modal-detail-grid is-retiros">' +
                    cajaModalDetalleCelda(
                        'Acción',
                        cajaRetirosDetalleAccion(row),
                        'is-action'
                    ) +
                    cajaModalDetalleCelda(
                        'Caja / Estado',
                        '<div class="cajas-modal-cell-stack">' +
                            '<strong>Caja #' + cajaEscape(row.apertura_id || '—') + '</strong>' +
                            cajaRetirosDetalleEstadoCaja(row) +
                        '</div>'
                    ) +
                    cajaModalDetalleCelda('Fecha', cajaEscape(row.fecha || '—')) +
                    cajaModalDetalleCelda('Motivo', cajaEscape(row.motivo || '—')) +
                    cajaModalDetalleCelda('Observación', cajaEscape(row.observacion || '—')) +
                    cajaModalDetalleCelda('Cuenta', cajaEscape(row.cuenta || '—')) +
                    cajaModalDetalleCelda('Egreso', cajaEscape(row.factura_egreso || '—')) +
                    cajaModalDetalleCelda(
                        'Monto',
                        '<strong>' + formatoMoneda(row.monto) + '</strong>',
                        'is-money is-withdraw'
                    ) +
                    cajaModalDetalleCelda('Estado', cajaRetirosDetalleEstado(row), 'is-center') +
                    cajaModalDetalleCelda('Registrado', cajaEscape(row.fecha_registro || '—')) +
                '</article>';
        });

        detailHtml += '</div></div>';
        $container.html(detailHtml);
    }

    cajaModalDetalleInfo(
        '#retirosDetalleInfo',
        total,
        start,
        end
    );

    cajaModalDetallePaginacion(
        '#retirosDetallePaginacion',
        state,
        'retirosDetalle',
        totalPages
    );

    cajaModalDetalleSincronizarVista(
        'retirosDetalle',
        state
    );
}

/*
 * Se conserva el nombre público porque cargarDetalleRetirosCaja(),
 * reintegros y refrescos ya lo utilizan. La implementación ahora
 * trabaja exclusivamente con DIV/Grid/Flex.
 */
function cargarTablaDetalleRetirosCaja(detalles) {
    cajasRetirosDetalleUI.rows = Array.isArray(detalles)
        ? detalles.slice()
        : [];

    cajasRetirosDetalleUI.search = String(
        $('#retirosDetalleSearch').val() || ''
    );

    cajasRetirosDetalleUI.page = 1;

    renderRetirosDetalleCaja();
}

function construirHeaderFooterDetalleRetirosCaja() {
    /*
     * Compatibilidad intencional con llamadas antiguas.
     * El contenedor actual es un DIV, por lo que no se crean
     * thead/tfoot ni se inicializa ningún plugin de tabla.
     */
    renderRetirosDetalleCaja();
}

function inicializarListadosDetalleCajaDiv() {
    var retiroView = 'detalle';
    var gananciaView = 'detalle';

    try {
        retiroView = localStorage.getItem('izzy.cajas.retirosDetalle.vista') || 'detalle';
        gananciaView = localStorage.getItem('izzy.cajas.gananciaDetalle.vista') || 'detalle';
    } catch (error) {}

    cajasRetirosDetalleUI.view = retiroView === 'miniatura'
        ? 'miniatura'
        : 'detalle';

    if (typeof cajasGananciaDetalleUI !== 'undefined') {
        cajasGananciaDetalleUI.view = gananciaView === 'miniatura'
            ? 'miniatura'
            : 'detalle';
    }

    $('#retirosDetalleSearch')
        .off('input.cajasModalRetiros')
        .on('input.cajasModalRetiros', function () {
            cajasRetirosDetalleUI.search = String($(this).val() || '');
            cajasRetirosDetalleUI.page = 1;
            renderRetirosDetalleCaja();
        });

    $('#gananciaDetalleSearch')
        .off('input.cajasModalGanancia')
        .on('input.cajasModalGanancia', function () {
            if (typeof cajasGananciaDetalleUI === 'undefined') {
                return;
            }

            cajasGananciaDetalleUI.search = String($(this).val() || '');
            cajasGananciaDetalleUI.page = 1;
            renderGananciaDetalleCaja();
        });

    $('.fm-search-clear[data-list="retirosDetalle"]')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', function () {
            $('#retirosDetalleSearch').val('').focus();
            cajasRetirosDetalleUI.search = '';
            cajasRetirosDetalleUI.page = 1;
            renderRetirosDetalleCaja();
        });

    $('.fm-search-clear[data-list="gananciaDetalle"]')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', function () {
            $('#gananciaDetalleSearch').val('').focus();

            if (typeof cajasGananciaDetalleUI === 'undefined') {
                return;
            }

            cajasGananciaDetalleUI.search = '';
            cajasGananciaDetalleUI.page = 1;
            renderGananciaDetalleCaja();
        });

    $('#retirosDetallePageSize')
        .off('change.cajasModalRetiros')
        .on('change.cajasModalRetiros', function () {
            cajasRetirosDetalleUI.pageSize =
                parseInt($(this).val(), 10) || 10;

            cajasRetirosDetalleUI.page = 1;
            renderRetirosDetalleCaja();
        });

    $('#gananciaDetallePageSize')
        .off('change.cajasModalGanancia')
        .on('change.cajasModalGanancia', function () {
            if (typeof cajasGananciaDetalleUI === 'undefined') {
                return;
            }

            cajasGananciaDetalleUI.pageSize =
                parseInt($(this).val(), 10) || 10;

            cajasGananciaDetalleUI.page = 1;
            renderGananciaDetalleCaja();
        });

    $('.fm-view-btn[data-list="retirosDetalle"]')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', function () {
            cajasRetirosDetalleUI.view =
                $(this).data('view') === 'miniatura'
                    ? 'miniatura'
                    : 'detalle';

            try {
                localStorage.setItem(
                    'izzy.cajas.retirosDetalle.vista',
                    cajasRetirosDetalleUI.view
                );
            } catch (error) {}

            cajasRetirosDetalleUI.page = 1;
            renderRetirosDetalleCaja();
        });

    $('.fm-view-btn[data-list="gananciaDetalle"]')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', function () {
            if (typeof cajasGananciaDetalleUI === 'undefined') {
                return;
            }

            cajasGananciaDetalleUI.view =
                $(this).data('view') === 'miniatura'
                    ? 'miniatura'
                    : 'detalle';

            try {
                localStorage.setItem(
                    'izzy.cajas.gananciaDetalle.vista',
                    cajasGananciaDetalleUI.view
                );
            } catch (error) {}

            cajasGananciaDetalleUI.page = 1;
            renderGananciaDetalleCaja();
        });

    $('#retirosDetallePaginacion')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', 'button[data-page]', function () {
            if ($(this).prop('disabled')) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                cajasRetirosDetalleUI.page = page;
                renderRetirosDetalleCaja();
            }
        });

    $('#gananciaDetallePaginacion')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', 'button[data-page]', function () {
            if (
                $(this).prop('disabled') ||
                typeof cajasGananciaDetalleUI === 'undefined'
            ) {
                return;
            }

            var page = parseInt($(this).data('page'), 10);

            if (!isNaN(page)) {
                cajasGananciaDetalleUI.page = page;
                renderGananciaDetalleCaja();
            }
        });

    $('#dataTableDetalleRetirosCaja')
        .off('click.cajasReintegrar', '.btn-reintegrar-retiro')
        .on('click.cajasReintegrar', '.btn-reintegrar-retiro', function () {
            abrirModalReintegroRetiroCaja(
                $(this).data('caja-retiros-id'),
                $(this).data('apertura-id'),
                $(this).data('monto')
            );
        });

    $('#btnActualizarRetirosDetalleFm')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', refrescarDetalleRetirosCaja);

    $('#btnActualizarGananciaDetalleFm')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', refrescarDesgloseGananciaCaja);

    $('#btnExcelRetirosDetalleFm')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', function () {
            exportarDetalleCajaExcel('retiros');
        });

    $('#btnPdfRetirosDetalleFm')
        .off('click.cajasModalRetiros')
        .on('click.cajasModalRetiros', function () {
            previsualizarDetalleCajaPdf('retiros');
        });

    $('#btnExcelGananciaDetalleFm')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', function () {
            exportarDetalleCajaExcel('ganancia');
        });

    $('#btnPdfGananciaDetalleFm')
        .off('click.cajasModalGanancia')
        .on('click.cajasModalGanancia', function () {
            previsualizarDetalleCajaPdf('ganancia');
        });
}

/* =========================================================
   EXCEL / PDF DE LOS LISTADOS DIV
   ========================================================= */
function cajaModalDetalleConfigExport(tipo) {
    if (tipo === 'retiros') {
        return {
            title: 'DETALLE DE RETIROS DE CAJA',
            subtitle: 'Retiros, estado, cuenta y trazabilidad',
            file: 'Detalle_Retiros_Caja',
            sheet: 'Retiros',
            rows: cajasRetirosDetalleUI.filtered || [],
            filters: $('#dr_contexto_caja').text() || 'Detalle de retiros',
            headers: [
                'Caja',
                'Fecha',
                'Motivo',
                'Observación',
                'Cuenta',
                'Egreso',
                'Monto',
                'Estado',
                'Registrado'
            ],
            numeric: [6],
            money: [6],
            statusIndex: 7,
            row: function (row) {
                return [
                    row.apertura_id || '',
                    row.fecha || '',
                    row.motivo || '',
                    row.observacion || '',
                    row.cuenta || '',
                    row.factura_egreso || '',
                    parseMonto(row.monto),
                    row.estado_label || (
                        parseInt(row.estado || 0, 10) === 1
                            ? 'Activo'
                            : 'Anulado'
                    ),
                    row.fecha_registro || ''
                ];
            },
            summary: function (rows) {
                var total = rows.reduce(function (sum, row) {
                    return sum + (
                        parseInt(row.estado || 0, 10) === 1
                            ? parseMonto(row.monto)
                            : 0
                    );
                }, 0);

                var cajas = {};

                rows.forEach(function (row) {
                    if (row.apertura_id) {
                        cajas[String(row.apertura_id)] = true;
                    }
                });

                return 'Registros: ' + rows.length +
                    '   |   Total activo: ' + formatoMoneda(total) +
                    '   |   Cajas: ' + Object.keys(cajas).length;
            }
        };
    }

    return {
        title: 'DETALLE DE GANANCIA DE CAJA',
        subtitle: 'Venta, costo, ISV y ganancia por producto',
        file: 'Detalle_Ganancia_Caja',
        sheet: 'Ganancia',
        rows: (
            typeof cajasGananciaDetalleUI !== 'undefined'
                ? cajasGananciaDetalleUI.filtered
                : []
        ) || [],
        filters: $('#dg_contexto_consulta').text() || 'Detalle de ganancia',
        headers: [
            'Factura',
            'Tipo',
            'Producto',
            'Cantidad',
            'Costo Unit.',
            'Precio Venta',
            'ISV',
            'Total Costo',
            'Total Venta',
            'Total c/ISV',
            'Ganancia'
        ],
        numeric: [3, 4, 5, 6, 7, 8, 9, 10],
        money: [4, 5, 6, 7, 8, 9, 10],
        statusIndex: -1,
        row: function (row) {
            return [
                row.factura || '',
                row.tipo_documento || '',
                row.producto || '',
                parseMonto(row.cantidad),
                parseMonto(row.costo_unitario),
                parseMonto(row.precio_venta),
                parseMonto(row.isv_detalle),
                parseMonto(row.total_costo),
                parseMonto(row.total_venta),
                parseMonto(row.total_con_isv),
                parseMonto(row.ganancia)
            ];
        },
        summary: function (rows) {
            var totalVenta = rows.reduce(function (sum, row) {
                return sum + parseMonto(row.total_venta);
            }, 0);

            var totalGanancia = rows.reduce(function (sum, row) {
                return sum + parseMonto(row.ganancia);
            }, 0);

            return 'Líneas: ' + rows.length +
                '   |   Venta: ' + formatoMoneda(totalVenta) +
                '   |   Ganancia: ' + formatoMoneda(totalGanancia);
        }
    };
}

function exportarDetalleCajaExcel(tipo) {
    var config = cajaModalDetalleConfigExport(tipo);
    var rows = config.rows || [];

    if (!rows.length) {
        showNotify(
            'warning',
            'Sin información',
            'No hay registros para exportar.'
        );
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

    var lastCol = cajasExcelCol(config.headers.length - 1);
    var headerRow = 7;
    var firstDataRow = 8;
    var lastRow = Math.max(headerRow, headerRow + rows.length);
    var sheetRows = [];

    sheetRows.push(
        '<row r="1" ht="30" customHeight="1">' +
            cajasExcelCell('A1', 'IZZY • ' + config.title, 1, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="2" ht="20" customHeight="1">' +
            cajasExcelCell(
                'A2',
                config.subtitle +
                ' • Generado: ' +
                new Date().toLocaleDateString('es-HN'),
                2,
                false
            ) +
        '</row>'
    );

    sheetRows.push(
        '<row r="3" ht="20" customHeight="1">' +
            cajasExcelCell('A3', 'FILTROS: ' + config.filters, 6, false) +
        '</row>'
    );

    sheetRows.push(
        '<row r="4" ht="24" customHeight="1">' +
            cajasExcelCell('A4', config.summary(rows), 7, false) +
        '</row>'
    );

    sheetRows.push('<row r="5"></row>');

    sheetRows.push(
        '<row r="6" ht="20" customHeight="1">' +
            cajasExcelCell('A6', 'VISTA DETALLE', 8, false) +
        '</row>'
    );

    var headerCells = config.headers.map(function (header, index) {
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
        var values = config.row(row);

        var cells = values.map(function (value, colIndex) {
            var isNumeric = config.numeric.indexOf(colIndex) !== -1;
            var isMoney = config.money.indexOf(colIndex) !== -1;
            var style = isMoney
                ? 11
                : (isNumeric ? 12 : 4);

            if (config.statusIndex === colIndex) {
                style = cajaModalDetalleNormalizar(value) === 'activo'
                    ? 9
                    : 10;
            }

            return cajasExcelCell(
                cajasExcelCol(colIndex) + excelRow,
                value,
                style,
                isNumeric
            );
        }).join('');

        sheetRows.push(
            '<row r="' + excelRow + '" ht="28" customHeight="1">' +
                cells +
            '</row>'
        );
    });

    var sheetXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
            '</sheetView></sheetViews>' +
            '<sheetFormatPr defaultRowHeight="15"/>' +
            '<cols>' +
                '<col min="1" max="' + config.headers.length + '" width="22" customWidth="1"/>' +
            '</cols>' +
            '<sheetData>' + sheetRows.join('') + '</sheetData>' +
            '<autoFilter ref="A7:' + lastCol + lastRow + '"/>' +
            '<mergeCells count="5">' +
                '<mergeCell ref="A1:' + lastCol + '1"/>' +
                '<mergeCell ref="A2:' + lastCol + '2"/>' +
                '<mergeCell ref="A3:' + lastCol + '3"/>' +
                '<mergeCell ref="A4:' + lastCol + '4"/>' +
                '<mergeCell ref="A6:' + lastCol + '6"/>' +
            '</mergeCells>' +
            '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
            '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>';

    var stylesXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
            '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;L. &quot;#,##0.00"/></numFmts>' +
            '<fonts count="7">' +
                '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                '<font><b/><sz val="14"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
            '</fonts>' +
            '<fills count="7">' +
                '<fill><patternFill patternType="none"/></fill>' +
                '<fill><patternFill patternType="gray125"/></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF1F354E"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill>' +
                '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill>' +
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
            '<cellXfs count="13">' +
                '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' +
                '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
            '</cellXfs>' +
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
        '</styleSheet>';

    var workbookXml =
        '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
            '<bookViews><workbookView activeTab="0"/></bookViews>' +
            '<sheets><sheet name="' + cajasXmlEscape(config.sheet) + '" sheetId="1" r:id="rId1"/></sheets>' +
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
    zip.folder('xl').folder('worksheets').file(
        'sheet1.xml',
        window.izzyExcelCompletarBordesCombinados(sheetXml)
    );

    var options = {
        type: 'blob',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        compression: 'DEFLATE'
    };

    var promise = typeof zip.generateAsync === 'function'
        ? zip.generateAsync(options)
        : Promise.resolve(zip.generate(options));

    promise
        .then(function (blob) {
            cajasDescargarBlob(blob, config.file + '.xlsx');
        })
        .catch(function (error) {
            console.error(error);
            showNotify(
                'error',
                'Error',
                'No se pudo generar el archivo Excel.'
            );
        });
}

function previsualizarDetalleCajaPdf(tipo) {
    var config = cajaModalDetalleConfigExport(tipo);
    var rows = config.rows || [];

    if (!rows.length) {
        showNotify(
            'warning',
            'Sin información',
            'No hay registros para generar el PDF.'
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
            'No se encontró el visor PDF.'
        );
        return;
    }

    if (
        !(
            typeof imagen !== 'undefined' &&
            typeof imagen === 'string' &&
            imagen.indexOf('data:image/') === 0
        )
    ) {
        cajasObtenerLogoPdf(function (logoDataUrl) {
            try {
                imagen = logoDataUrl;
            } catch (error) {}

            previsualizarDetalleCajaPdf(tipo);
        });

        return;
    }

    var body = [
        config.headers.map(function (header) {
            return {
                text: header,
                fillColor: '#1F354E',
                color: '#FFFFFF',
                bold: true,
                alignment: 'center',
                fontSize: 5.2,
                margin: [2, 3, 2, 3]
            };
        })
    ];

    rows.forEach(function (row) {
        var values = config.row(row);

        body.push(
            values.map(function (value, index) {
                var isMoney = config.money.indexOf(index) !== -1;
                var isNumeric = config.numeric.indexOf(index) !== -1;

                return {
                    text: isMoney
                        ? formatoMoneda(value)
                        : String(value === null || value === undefined ? '' : value),
                    fontSize: 5.1,
                    color: '#253858',
                    alignment: isMoney
                        ? 'right'
                        : (isNumeric ? 'center' : 'left'),
                    margin: [2, 2, 2, 2]
                };
            })
        );
    });

    var docDefinition = {
        pageSize: 'LETTER',
        pageOrientation: 'landscape',
        pageMargins: [24, 26, 24, 34],

        header: function () {
            return {
                margin: [24, 10, 24, 0],
                canvas: [{
                    type: 'line',
                    x1: 0,
                    y1: 0,
                    x2: 744,
                    y2: 0,
                    lineWidth: 2,
                    lineColor: '#0EA5A8'
                }]
            };
        },

        footer: function (currentPage, pageCount) {
            return {
                margin: [24, 8, 24, 0],
                columns: [
                    {
                        text: 'IZZY • ' + config.title,
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

        content: [
            {
                table: {
                    widths: [92, '*', 150],
                    body: [[
                        {
                            border: [true, true, true, true],
                            fillColor: '#1F354E',
                            margin: [10, 8, 0, 8],
                            stack: [cajasPdfLogoPlate(imagen)]
                        },
                        {
                            border: [true, true, true, true],
                            fillColor: '#1F354E',
                            color: '#FFFFFF',
                            margin: [12, 12, 8, 10],
                            stack: [
                                {
                                    text: 'REPORTE EJECUTIVO',
                                    fontSize: 8,
                                    bold: true,
                                    color: '#73D7DB'
                                },
                                {
                                    text: config.title,
                                    fontSize: 14,
                                    bold: true,
                                    margin: [0, 3, 0, 0]
                                }
                            ]
                        },
                        {
                            border: [true, true, true, true],
                            fillColor: '#1F354E',
                            color: '#FFFFFF',
                            alignment: 'right',
                            margin: [8, 12, 12, 10],
                            stack: [
                                {
                                    text: new Date().toLocaleDateString('es-HN'),
                                    fontSize: 8,
                                    bold: true
                                },
                                {
                                    text: rows.length + ' registros',
                                    fontSize: 7,
                                    margin: [0, 4, 0, 0]
                                }
                            ]
                        }
                    ]]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .55; },
                    vLineWidth: function () { return .55; }
                },
                margin: [0, 0, 0, 8]
            },
            {
                table: {
                    widths: ['*'],
                    body: [[{
                        text: 'FILTROS: ' + config.filters,
                        fillColor: '#F7F9FC',
                        color: '#52627A',
                        fontSize: 7,
                        margin: [7, 5, 7, 5]
                    }]]
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .5; },
                    vLineWidth: function () { return .5; }
                },
                margin: [0, 0, 0, 8]
            },
            {
                text: 'VISTA DETALLE',
                fontSize: 8,
                bold: true,
                color: '#172B4D',
                margin: [0, 0, 0, 5]
            },
            {
                table: {
                    headerRows: 1,
                    widths: config.headers.map(function () { return '*'; }),
                    body: body
                },
                layout: {
                    hLineColor: function () { return '#DDE3EA'; },
                    vLineColor: function () { return '#DDE3EA'; },
                    hLineWidth: function () { return .45; },
                    vLineWidth: function () { return .45; },
                    paddingLeft: function () { return 1; },
                    paddingRight: function () { return 1; },
                    paddingTop: function () { return 1; },
                    paddingBottom: function () { return 1; }
                }
            }
        ],

        defaultStyle: {
            fontSize: 7,
            color: '#253858'
        }
    };

    var pdf = pdfMake.createPdf(docDefinition);

    if (typeof pdf.getDataUrl !== 'function') {
        showNotify(
            'error',
            'PDF no disponible',
            'La versión actual de pdfMake no permite una vista previa compatible.'
        );
        return;
    }

    pdf.getDataUrl(function (dataUrl) {
        abrirModalPdfPublico(
            dataUrl,
            config.title,
            config.file + '.pdf'
        );
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
   DETALLE GANANCIA CAJA | LISTADO DIV / GRID / FLEX
   ========================================================= */
var cajasGananciaDetalleUI = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    view: 'detalle',
    search: ''
};

function renderGananciaDetalleCaja() {
    var state = cajasGananciaDetalleUI;
    var $container = $('#dataTableDetalleGananciaCaja');

    if (!$container.length) {
        return;
    }

    cajaModalDetalleFiltrar(
        state,
        [
            'factura',
            'tipo_documento',
            'producto',
            'cantidad',
            'costo_unitario',
            'precio_venta',
            'isv_detalle',
            'total_costo',
            'total_venta',
            'total_con_isv',
            'ganancia'
        ]
    );

    var total = state.filtered.length;
    var totalPages = Math.max(1, Math.ceil(total / state.pageSize));
    var start = (state.page - 1) * state.pageSize;
    var end = Math.min(start + state.pageSize, total);
    var visible = state.filtered.slice(start, end);

    $container
        .removeClass('cajas-modal-vista-detalle cajas-modal-vista-miniatura')
        .addClass(
            'cajas-modal-listado ' +
            (state.view === 'miniatura'
                ? 'cajas-modal-vista-miniatura'
                : 'cajas-modal-vista-detalle')
        );

    if (!visible.length) {
        $container.html(
            '<div class="cajas-modal-empty">' +
                '<i class="fas fa-chart-line"></i>' +
                '<strong>Sin detalle de ganancia</strong>' +
                '<span>No hay productos que coincidan con los criterios actuales.</span>' +
            '</div>'
        );
    } else if (state.view === 'miniatura') {
        var miniHtml = '<div class="cajas-modal-mini-grid">';

        visible.forEach(function (row) {
            var ganancia = parseMonto(row.ganancia);

            miniHtml += '' +
                '<article class="cajas-modal-mini-card">' +
                    '<div class="cajas-modal-mini-topline"></div>' +
                    '<div class="cajas-modal-mini-header">' +
                        '<div class="cajas-modal-mini-title">' +
                            '<strong>' + cajaEscape(row.producto || 'Sin producto') + '</strong>' +
                            '<span>Factura: ' + cajaEscape(row.factura || '—') + '</span>' +
                        '</div>' +
                        '<span class="cajas-modal-gain ' +
                            (ganancia < 0 ? 'is-negative' : 'is-positive') + '">' +
                            formatoMoneda(ganancia) +
                        '</span>' +
                    '</div>' +
                    '<div class="cajas-modal-mini-body">' +
                        cajaModalDetalleCampo('Tipo', cajaEscape(row.tipo_documento || '—')) +
                        cajaModalDetalleCampo('Cantidad', cajaEscape(row.cantidad || 0)) +
                        cajaModalDetalleCampo('Costo unit.', formatoMoneda(row.costo_unitario), 'is-money') +
                        cajaModalDetalleCampo('Precio venta', formatoMoneda(row.precio_venta), 'is-money') +
                        cajaModalDetalleCampo('Total venta', formatoMoneda(row.total_venta), 'is-money') +
                        cajaModalDetalleCampo('ISV', formatoMoneda(row.isv_detalle), 'is-money') +
                    '</div>' +
                '</article>';
        });

        miniHtml += '</div>';
        $container.html(miniHtml);
    } else {
        var detailHtml = '' +
            '<div class="cajas-modal-detail-table">' +
                cajaModalDetalleHeader(
                    [
                        'Factura',
                        'Tipo',
                        'Producto',
                        'Cantidad',
                        'Costo Unit.',
                        'Precio Venta',
                        'ISV',
                        'Total Costo',
                        'Total Venta',
                        'Total c/ISV',
                        'Ganancia'
                    ],
                    'is-ganancia'
                ) +
                '<div class="cajas-modal-detail-body">';

        visible.forEach(function (row) {
            var ganancia = parseMonto(row.ganancia);

            detailHtml += '' +
                '<article class="cajas-modal-detail-row cajas-modal-detail-grid is-ganancia">' +
                    cajaModalDetalleCelda('Factura', cajaEscape(row.factura || '—')) +
                    cajaModalDetalleCelda('Tipo', cajaEscape(row.tipo_documento || '—'), 'is-center') +
                    cajaModalDetalleCelda('Producto', '<strong>' + cajaEscape(row.producto || 'Sin producto') + '</strong>') +
                    cajaModalDetalleCelda('Cantidad', cajaEscape(row.cantidad || 0), 'is-center') +
                    cajaModalDetalleCelda('Costo Unit.', formatoMoneda(row.costo_unitario), 'is-money') +
                    cajaModalDetalleCelda('Precio Venta', formatoMoneda(row.precio_venta), 'is-money') +
                    cajaModalDetalleCelda('ISV', formatoMoneda(row.isv_detalle), 'is-money') +
                    cajaModalDetalleCelda('Total Costo', formatoMoneda(row.total_costo), 'is-money') +
                    cajaModalDetalleCelda('Total Venta', formatoMoneda(row.total_venta), 'is-money is-positive') +
                    cajaModalDetalleCelda('Total c/ISV', formatoMoneda(row.total_con_isv), 'is-money') +
                    cajaModalDetalleCelda(
                        'Ganancia',
                        '<strong>' + formatoMoneda(ganancia) + '</strong>',
                        'is-money ' + (ganancia < 0 ? 'is-negative' : 'is-positive')
                    ) +
                '</article>';
        });

        detailHtml += '</div></div>';
        $container.html(detailHtml);
    }

    cajaModalDetalleInfo(
        '#gananciaDetalleInfo',
        total,
        start,
        end
    );

    cajaModalDetallePaginacion(
        '#gananciaDetallePaginacion',
        state,
        'gananciaDetalle',
        totalPages
    );

    cajaModalDetalleSincronizarVista(
        'gananciaDetalle',
        state
    );
}

/*
 * Se conserva el nombre público usado por cargarDesgloseGananciaCaja().
 * Ya no inicializa ningún plugin de tablas.
 */
function cargarTablaDetalleGananciaCaja(detalles) {
    cajasGananciaDetalleUI.rows = Array.isArray(detalles)
        ? detalles.slice()
        : [];

    cajasGananciaDetalleUI.search = String(
        $('#gananciaDetalleSearch').val() || ''
    );

    cajasGananciaDetalleUI.page = 1;

    renderGananciaDetalleCaja();
}

function construirHeaderFooterDetalleGananciaCaja() {
    /*
     * Compatibilidad con llamadas antiguas.
     * El destino es DIV/Grid/Flex y no una tabla.
     */
    renderGananciaDetalleCaja();
}

/* =========================================================
   CAJAS | DROPDOWN DE ACCIONES ADAPTATIVO
   - Abajo / arriba / derecha / izquierda según espacio real.
   - Nunca queda detrás del contenido ni por encima de un modal.
   - Cierra otros menús, clic externo, scroll, resize, ESC y modal.
   ========================================================= */
var cajasDropdownActivo = null;

function cajasObtenerBoton($dropdown) {
    return $dropdown.children('.js-acciones-toggle').first();
}

function cajasMedirDropdown($menu) {
    var menu = $menu && $menu.length ? $menu[0] : null;
    if (!menu) return { width: 220, height: 160 };

    var teniaShow = $menu.hasClass('show');
    var cssText = menu.style.cssText;
    $menu.addClass('show');
    menu.style.setProperty('display','block','important');
    menu.style.setProperty('visibility','hidden','important');
    menu.style.setProperty('position','fixed','important');
    menu.style.setProperty('top','0px','important');
    menu.style.setProperty('left','0px','important');
    menu.style.setProperty('right','auto','important');
    menu.style.setProperty('bottom','auto','important');
    menu.style.setProperty('transform','none','important');

    var rect = menu.getBoundingClientRect();
    menu.style.cssText = cssText;
    if (!teniaShow) $menu.removeClass('show');

    return {
        width: Math.max(rect.width || 0, 220),
        height: Math.max(rect.height || 0, 1)
    };
}

function cajasLimpiarDropdown($dropdown) {
    if (!$dropdown || !$dropdown.length) return;

    var $menu = $dropdown.children('.dropdown-menu').first();
    var menu = $menu[0];
    var $row = $dropdown.closest('.cajas-detail-row, .cajas-mini-card');

    $dropdown.removeClass('show dropup dropright dropleft');
    $menu.removeClass('show dropdown-menu-right')
        .removeAttr('x-placement data-popper-placement data-izzy-placement');

    if (menu) {
        ['display','visibility','position','top','left','right','bottom','transform','z-index','max-height','overflow-y']
            .forEach(function(prop) { menu.style.removeProperty(prop); });
    }

    cajasObtenerBoton($dropdown).attr('aria-expanded','false');
    $row.removeClass('cajas-dropdown-open');
    if ($row.length) $row[0].style.removeProperty('transform');

    if (cajasDropdownActivo && cajasDropdownActivo.length && cajasDropdownActivo.is($dropdown)) {
        cajasDropdownActivo = null;
    }
}

function cajasCerrarTodosDropdowns($excepto) {
    $('#cajasListado .cajas-actions-dropdown').each(function() {
        var $dropdown = $(this);
        if ($excepto && $excepto.length && $dropdown.is($excepto)) return;
        cajasLimpiarDropdown($dropdown);
    });
}

function cajasPosicionarDropdown($dropdown) {
    var $button = cajasObtenerBoton($dropdown);
    var $menu = $dropdown.children('.dropdown-menu').first();
    if (!$button.length || !$menu.length) return false;

    var button = $button[0];
    var menu = $menu[0];
    var rect = button.getBoundingClientRect();
    var size = cajasMedirDropdown($menu);
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var margin = 10;
    var gap = 7;

    var abajo = viewportHeight - rect.bottom - margin;
    var arriba = rect.top - margin;
    var derecha = viewportWidth - rect.right - margin;
    var izquierda = rect.left - margin;
    var top;
    var left;
    var placement;

    if (abajo >= size.height + gap) {
        top = rect.bottom + gap;
        placement = 'bottom';
    } else if (arriba >= size.height + gap) {
        top = rect.top - size.height - gap;
        placement = 'top';
    } else if (derecha >= size.width + gap) {
        left = rect.right + gap;
        top = rect.top;
        placement = 'right';
    } else if (izquierda >= size.width + gap) {
        left = rect.left - size.width - gap;
        top = rect.top;
        placement = 'left';
    } else {
        top = arriba > abajo ? rect.top - size.height - gap : rect.bottom + gap;
        placement = arriba > abajo ? 'top-clamped' : 'bottom-clamped';
    }

    if (typeof left === 'undefined') {
        left = rect.left;
        if (left + size.width > viewportWidth - margin) {
            left = rect.right - size.width;
        }
    }

    left = Math.max(margin, Math.min(left, Math.max(margin, viewportWidth - size.width - margin)));
    top = Math.max(margin, Math.min(top, Math.max(margin, viewportHeight - Math.min(size.height, viewportHeight - margin * 2) - margin)));

    menu.style.setProperty('display','block','important');
    menu.style.setProperty('visibility','visible','important');
    menu.style.setProperty('position','fixed','important');
    menu.style.setProperty('left',Math.round(left)+'px','important');
    menu.style.setProperty('top',Math.round(top)+'px','important');
    menu.style.setProperty('right','auto','important');
    menu.style.setProperty('bottom','auto','important');
    menu.style.setProperty('transform','none','important');
    /* my_style usa backdrop 1990 y modal 2000: acciones quedan justo debajo. */
    menu.style.setProperty('z-index','1985','important');
    menu.style.setProperty('max-height',Math.max(90, viewportHeight - margin * 2)+'px','important');
    menu.style.setProperty('overflow-y','auto','important');
    menu.setAttribute('data-izzy-placement', placement);

    $menu.addClass('show');
    $button.attr('aria-expanded','true');

    var $row = $dropdown.closest('.cajas-detail-row, .cajas-mini-card');
    $row.addClass('cajas-dropdown-open');
    /* Evita que un hover con transform convierta al padre en containing block del fixed. */
    if ($row.length) $row[0].style.setProperty('transform','none','important');

    cajasDropdownActivo = $dropdown;
    return true;
}

function inicializarDropdownAccionesCajas() {
    // El administrador global de main.php usa portal al <body> y evita conflictos con Bootstrap/Popper.
    if (window.IZZYActionDropdown && window.IZZYActionDropdown.isGlobalManager) return;
    var $root = $('#cajasListado').first();
    if (!$root.length) return;

    $root.off('click.cajasDropdownAdaptativo', '.cajas-actions-dropdown .js-acciones-toggle')
        .on('click.cajasDropdownAdaptativo', '.cajas-actions-dropdown .js-acciones-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var $dropdown = $(this).closest('.cajas-actions-dropdown');
            var estabaAbierto = $dropdown.children('.dropdown-menu').hasClass('show');

            cajasCerrarTodosDropdowns($dropdown);
            if (estabaAbierto) {
                cajasLimpiarDropdown($dropdown);
                return;
            }

            cajasLimpiarDropdown($dropdown);
            cajasPosicionarDropdown($dropdown);
        });

    $root.off('click.cajasDropdownItem', '.cajas-actions-dropdown .dropdown-item, .cajas-actions-dropdown .accion-item')
        .on('click.cajasDropdownItem', '.cajas-actions-dropdown .dropdown-item, .cajas-actions-dropdown .accion-item', function() {
            var $dropdown = $(this).closest('.cajas-actions-dropdown');
            window.setTimeout(function() { cajasLimpiarDropdown($dropdown); }, 0);
        });

    $(document).off('click.cajasDropdownOutside').on('click.cajasDropdownOutside', function(e) {
        if (!$(e.target).closest('.cajas-actions-dropdown').length) cajasCerrarTodosDropdowns();
    });

    $(document).off('show.bs.modal.cajasDropdown').on('show.bs.modal.cajasDropdown', function() {
        cajasCerrarTodosDropdowns();
    });

    $(document).off('keydown.cajasDropdown').on('keydown.cajasDropdown', function(e) {
        if (e.key === 'Escape') cajasCerrarTodosDropdowns();
    });

    $(window).off('resize.cajasDropdown scroll.cajasDropdown')
        .on('resize.cajasDropdown scroll.cajasDropdown', function() { cajasCerrarTodosDropdowns(); });
}
</script>