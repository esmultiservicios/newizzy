<script>
/* =========================================================
   COMPATIBILIDAD SELECTPICKER
   ---------------------------------------------------------
   Evita errores cuando bootstrap-select no está cargado o se
   carga después del JS de facturación.
   No cambia la lógica existente: si selectpicker existe, usa
   el plugin; si no existe, usa el select normal.
========================================================= */
if (window.jQuery && !$.fn.selectpicker) {
    $.fn.selectpicker = function (accion, valor) {
        if (accion === 'val') {
            if (arguments.length > 1) {
                this.val(valor);
                return this;
            }

            return this.val();
        }

        if (accion === 'refresh' || accion === 'render' || accion === 'mobile' || accion === 'destroy' || accion === undefined) {
            return this;
        }

        return this;
    };
}

//FAactura.php - js de las facturas escritorio
$(() => {
     // Evento para el botón de Generar Reporte
    $('#formulario_busqueda_cotizaciones').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_cotizaciones();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_busqueda_cotizaciones')[0].reset();
        $('#formulario_busqueda_cotizaciones .selectpicker').selectpicker('refresh');
        listar_busqueda_cotizaciones();
    });  

    // Evento para el botón de Generar Reporte
    $('#formulario_busqueda_cuentas_cobrar_clientes').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_cuentas_por_cobrar_clientes();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_busqueda_cuentas_cobrar_clientes')[0].reset();
        $('#formulario_busqueda_cuentas_cobrar_clientes .selectpicker').selectpicker('refresh');
        listar_busqueda_cuentas_por_cobrar_clientes();
    });   

    // Evento para el botón de Generar Reporte
    $('#formulario_bill_draft').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_bill_draf();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_bill_draft')[0].reset();
        $('#formulario_bill_draft .selectpicker').selectpicker('refresh');
        listar_busqueda_bill_draf();
    });

    // Evento para el botón de Generar Reporte
    $('#formulario_bill').on('submit', function(e) {
        e.preventDefault();
        listar_busqueda_bill();
    });

    // Evento para el botón de Limpiar Filtros
    $('#btn-limpiar-filtros').on('click', function() {
        $('#formulario_bill')[0].reset();
        $('#formulario_bill .selectpicker').selectpicker('refresh');
        listar_busqueda_bill();
    });

});

var row = 0;

$(() => {
    getCajero();
    getConsumidorFinal();
    cargarEstadoCajaUsuario(false).always(function () {
        validarAperturaCajaUsuario();
    });
    getBanco();
    getTotalFacturasDisponibles();
    getReporteCotizacion();
    getReporteFactura();
    getEstadoFacturaCredito();
    getCollaboradoresModalPagoFacturas();
    getFacturador();
    getVendedores();
    getClientesFacturasCXC();
});


/* =========================================================
   CONSULTAR APERTURA DE CAJA
   ---------------------------------------------------------
   IMPORTANTE:
   Esta función se deja igual para no afectar nada existente.
   Devuelve solo el estado de la caja.
========================================================= */

var izzyCajaUsuarioCache = { cargado: false, estado: 2, apertura_id: 0, solicitud: null };

function cargarEstadoCajaUsuario(forzar) {
    var url = '<?php echo SERVERURL;?>core/getAperturaCajaUsuario.php';

    if (izzyCajaUsuarioCache.solicitud && forzar !== true) {
        return izzyCajaUsuarioCache.solicitud;
    }

    izzyCajaUsuarioCache.solicitud = $.ajax({
        type: 'POST',
        url: url
    }).done(function(registro) {
        try {
            var valores = (typeof registro === 'string') ? JSON.parse(registro) : registro;
            izzyCajaUsuarioCache.estado = parseInt(valores[0] || 2, 10);
            izzyCajaUsuarioCache.apertura_id = parseInt(valores[1] || 0, 10);
            izzyCajaUsuarioCache.cargado = true;
        } catch (e) {
            izzyCajaUsuarioCache.estado = 2;
            izzyCajaUsuarioCache.apertura_id = 0;
        }
    }).always(function() {
        izzyCajaUsuarioCache.solicitud = null;
    });

    return izzyCajaUsuarioCache.solicitud;
}

function getConsultarAperturaCaja() {
    if (!izzyCajaUsuarioCache.cargado) {
        cargarEstadoCajaUsuario(false);
    }

    return izzyCajaUsuarioCache.estado;
}

/* =========================================================
   CONSULTAR APERTURA_ID DE CAJA DEL USUARIO
   ---------------------------------------------------------
   Esta función usa el mismo PHP:
   core/getAperturaCajaUsuario.php

   Pero devuelve SOLO el apertura_id.
   No afecta getConsultarAperturaCaja().
========================================================= */

function getAperturaIdCajaUsuario() {
    if (!izzyCajaUsuarioCache.cargado) {
        cargarEstadoCajaUsuario(false);
    }

    return isNaN(izzyCajaUsuarioCache.apertura_id) ? 0 : izzyCajaUsuarioCache.apertura_id;
}

/* =========================================================
   SINCRONIZAR UI DESPUÉS DE ABRIR/CERRAR CAJA
   ---------------------------------------------------------
   El estado de caja se mantiene en caché para no bloquear la UI.
   Después de una apertura o cierre exitoso se debe invalidar ese
   caché y consultar nuevamente antes de habilitar los botones.
========================================================= */
function sincronizarEstadoCajaFacturacion() {
    izzyCajaUsuarioCache.cargado = false;

    return cargarEstadoCajaUsuario(true).always(function () {
        if (typeof validarAperturaCajaUsuario === 'function') {
            validarAperturaCajaUsuario();
        }

        if (typeof getCajero === 'function') {
            getCajero();
        }

        if (typeof getTotalFacturasDisponibles === 'function') {
            getTotalFacturasDisponibles();
        }
    });
}

if (!window.__sincronizacionEstadoCajaFacturaRegistrada) {
    window.__sincronizacionEstadoCajaFacturaRegistrada = true;

    $(document)
        .off('ajaxComplete.sincronizarCajaFactura')
        .on('ajaxComplete.sincronizarCajaFactura', function (event, xhr, settings) {
            var url = String((settings && settings.url) || '').toLowerCase();
            var cambioEstadoCaja =
                url.indexOf('addaperturacajaajax.php') !== -1 ||
                url.indexOf('addcierrecajafacturasajax.php') !== -1;

            if (!cambioEstadoCaja) {
                return;
            }

            // El formulario general ya mostró la notificación. Aquí solamente
            // sincronizamos el estado confirmado por el servidor y la interfaz.
            setTimeout(function () {
                sincronizarEstadoCajaFacturacion();
            }, 150);
        });
}

/* =========================================================
   INICIO - RETIRO DE CAJA DIRECTO DESDE FACTURACIÓN
   Botón: #btn_retiro_caja

   Este botón NO debe abrir el modal directamente desde HTML.
   Primero valida si hay caja abierta, obtiene apertura_id,
   carga saldos y luego abre el mismo modal #modalRetiroCaja.
========================================================= */

$(document)
    .off('click.cajaFacturaRetiroDirecto', '#btn_retiro_caja')
    .on('click.cajaFacturaRetiroDirecto', '#btn_retiro_caja', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        abrirRetiroCajaDirectoDesdeFactura();
    });

function abrirRetiroCajaDirectoDesdeFactura() {
    if (!izzyCajaUsuarioCache.cargado) {
        cargarEstadoCajaUsuario(false).done(function () {
            abrirRetiroCajaDirectoDesdeFactura();
        });
        return;
    }

    /*
      Validamos con tu función original.
      Si retorna 2, significa que no hay caja abierta.
    */
    if (typeof getConsultarAperturaCaja === 'function') {
        if (getConsultarAperturaCaja() == 2) {
            showNotify(
                'error',
                'Caja cerrada',
                'Debe aperturar caja antes de realizar un retiro.'
            );

            return;
        }
    }

    /*
      Obtenemos el apertura_id usando la nueva función.
      Esta consulta el mismo PHP, pero toma la posición [1].
    */
    var apertura_id = 0;

    if (typeof getAperturaIdCajaUsuario === 'function') {
        apertura_id = getAperturaIdCajaUsuario();
    }

    if (apertura_id <= 0) {
        showNotify(
            'error',
            'Caja no encontrada',
            'No se pudo obtener la apertura activa para realizar el retiro.'
        );

        return;
    }

    /*
      Abrimos el mismo modal que ya funciona desde la tabla.
      Esta función ya carga los saldos con cargarSaldoRetiroCajaFactura().
    */
    abrirRetiroCajaDesdeFactura({
        apertura_id: apertura_id,
        estado: 1
    });
}

/* =========================================================
   FIN - RETIRO DE CAJA DIRECTO DESDE FACTURACIÓN
========================================================= */

/* =========================================================
   INICIO - CAJA DESDE FACTURACIÓN
   Este bloque pertenece a FACTURAS.
   Permite ver y operar la caja desde la vista de facturación.

   Incluye:
   - Modal principal de caja desde facturación
   - DataTable de cajas
   - Retiro de caja
   - Detalle de retiros
   - Reintegro total/parcial
   - Desglose de ganancia
   - Cierre de caja usando el mismo formulario/modal de Caja
   - Comprobante de caja

   IMPORTANTE:
   - Mensajes con showNotify.
   - El cierre de caja no usa swal; abre el modal #modal_apertura_caja.
   - Los eventos del dropdown usan captura nativa para evitar
     que otro JS bloquee el click antes de llegar al tbody.
   ========================================================= */


/* =========================================================
   INICIO - EVENTOS PRINCIPALES DEL MODAL CAJA FACTURA
   ========================================================= */

   $('#btn_ver_caja_factura').off('click.cajaFacturaAbrir').on('click.cajaFacturaAbrir', function () {
    asegurarModalesCajaFacturaEstaticos();

    $('#modalCajaFactura').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });

    cargarCajaFactura();
});

$('#formCajaFactura').off('submit.cajaFacturaFiltro').on('submit.cajaFacturaFiltro', function (e) {
    e.preventDefault();
    cargarCajaFactura();
});

$('#btnActualizarCajaFactura').off('click.cajaFacturaActualizar').on('click.cajaFacturaActualizar', function () {
    cargarCajaFactura();
});

$('#estado_caja_factura').off('change.cajaFacturaEstado').on('change.cajaFacturaEstado', function () {
    cargarCajaFactura();
});

$('#fecha_caja_factura_i').off('change.cajaFacturaFechaI').on('change.cajaFacturaFechaI', function () {
    cargarCajaFactura();
});

$('#fecha_caja_factura_f').off('change.cajaFacturaFechaF').on('change.cajaFacturaFechaF', function () {
    cargarCajaFactura();
});

/* =========================================================
   FIN - EVENTOS PRINCIPALES DEL MODAL CAJA FACTURA
   ========================================================= */


/* =========================================================
   INICIO - HELPERS GENERALES DE CAJA FACTURACIÓN
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

function renderMonedaColor(data, type) {
    var valor = parseMonto(data);
    var number = formatoMoneda(valor);

    if (type === 'display') {
        var color = valor < 0 ? '#dc2626' : '#008000';
        return '<span style="color:' + color + '; font-weight:700; white-space:nowrap;">' + number + '</span>';
    }

    return valor;
}

function parseMontoCajaFactura(valor) {
    return parseMonto(valor);
}

function formatoMonedaCajaFactura(valor) {
    return formatoMoneda(valor);
}

function renderMonedaColorCajaFactura(data, type) {
    return renderMonedaColor(data, type);
}

function esCajaActivaFactura(row) {
    return row && parseInt(row.estado || 0) === 1;
}

function obtenerFilaCajaFacturaPorBoton(boton) {
    var $tr = $(boton).closest('tr');

    if ($tr.hasClass('child')) {
        $tr = $tr.prev();
    }

    if (!$.fn.DataTable.isDataTable('#dataTableCajaFactura')) {
        return null;
    }

    return $('#dataTableCajaFactura').DataTable().row($tr).data();
}

function obtenerFechasCajaFacturacion() {
    var fechai = $('#fecha_caja_factura_i').val();
    var fechaf = $('#fecha_caja_factura_f').val();

    if (!fechai) {
        fechai = new Date().toISOString().split('T')[0];
    }

    if (!fechaf) {
        fechaf = fechai;
    }

    return {
        fechai: fechai,
        fechaf: fechaf
    };
}

function ocultarMenuAccionesCajaFactura(boton) {
    var $dropdown = $(boton).closest('.dropdown');
    $dropdown.find('.dropdown-menu').removeClass('show');
    $dropdown.find('.js-acciones-toggle').attr('aria-expanded', 'false');
}

function aplicarModalEstaticoCajaFactura(selector) {
    var $modal = $(selector);

    if ($modal.length === 0) {
        return;
    }

    $modal.attr('data-backdrop', 'static');
    $modal.attr('data-keyboard', 'false');

    var instancia = $modal.data('bs.modal');

    if (instancia && instancia._config) {
        instancia._config.backdrop = 'static';
        instancia._config.keyboard = false;
    }
}

function asegurarModalesCajaFacturaEstaticos() {
    aplicarModalEstaticoCajaFactura('#modalCajaFactura');
    aplicarModalEstaticoCajaFactura('#modalRetiroCaja');
    aplicarModalEstaticoCajaFactura('#modal_apertura_caja');
    aplicarModalEstaticoCajaFactura('#modalDetalleRetirosCaja');
    aplicarModalEstaticoCajaFactura('#modalReintegroRetiroCaja');
    aplicarModalEstaticoCajaFactura('#modalDesgloseGananciaCaja');
    aplicarModalEstaticoCajaFactura('#modalCuadreDiaCaja');
}

function refrescarCajaFacturaDespuesDeRetiro(origen) {
    var ahora = new Date().getTime();

    if (window.__refrescandoCajaFacturaRetiro) {
        return;
    }

    if (window.__ultimoRefreshCajaFacturaRetiro && (ahora - window.__ultimoRefreshCajaFacturaRetiro) < 1200) {
        return;
    }

    window.__refrescandoCajaFacturaRetiro = true;
    window.__ultimoRefreshCajaFacturaRetiro = ahora;

    setTimeout(function () {
        if (typeof cargarCajaFactura === 'function') {
            cargarCajaFactura();
        }

        if (typeof validarAperturaCajaUsuario === 'function') {
            validarAperturaCajaUsuario();
        }

        if (typeof getCajero === 'function') {
            getCajero();
        }

        if (typeof listar_registro_cajas === 'function') {
            listar_registro_cajas();
        }

        if ($('#modalDetalleRetirosCaja').hasClass('show')) {
            refrescarDetalleRetirosCaja();
        }

        if ($('#modalDesgloseGananciaCaja').hasClass('show')) {
            refrescarDesgloseGananciaCaja();
        }

        setTimeout(function () {
            window.__refrescandoCajaFacturaRetiro = false;
        }, 1200);
    }, 250);
}

if (!window.__controlModalesCajaFacturaRegistrado) {
    window.__controlModalesCajaFacturaRegistrado = true;

    $(document).ready(function () {
        asegurarModalesCajaFacturaEstaticos();
    });

    $(document).on('show.bs.modal.cajaFacturaEstatico', '#modalCajaFactura, #modalRetiroCaja, #modal_apertura_caja, #modalDetalleRetirosCaja, #modalReintegroRetiroCaja, #modalDesgloseGananciaCaja, #modalCuadreDiaCaja', function () {
        aplicarModalEstaticoCajaFactura(this);
    });

    // IMPORTANTE:
    // No refrescamos al cerrar #modalRetiroCaja, porque el retiro ya dispara
    // el refresco desde ajaxComplete. Si se refresca también en hidden.bs.modal,
    // la tabla dataTableCajaFactura se recarga dos veces.
    $(document).off('hidden.bs.modal.cajaFacturaRetiroRefresh', '#modalRetiroCaja');

    $(document).ajaxComplete(function (event, xhr, settings) {
        var url = '';

        if (settings && settings.url) {
            url = String(settings.url).toLowerCase();
        }

        if (
            url.indexOf('addretirocaja') !== -1 ||
            url.indexOf('registrarretirocaja') !== -1 ||
            url.indexOf('guardarretir') !== -1 ||
            url.indexOf('guardar_retiro') !== -1
        ) {
            refrescarCajaFacturaDespuesDeRetiro('ajaxRetiroCaja');
        }
    });
}

/* =========================================================
   FIN - HELPERS GENERALES DE CAJA FACTURACIÓN
   ========================================================= */


/* =========================================================
   INICIO - EVENTOS NATIVOS DE ACCIONES CAJA FACTURA
   Estos eventos usan captura para que no los bloquee otro JS.
   ========================================================= */

if (!window.__eventosCajaFacturaRegistrados) {
    window.__eventosCajaFacturaRegistrados = true;

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-retirar');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        ocultarMenuAccionesCajaFactura(boton);

        var data = obtenerFilaCajaFacturaPorBoton(boton);

        if (!data || !esCajaActivaFactura(data)) {
            showNotify('error', 'Caja no disponible', 'Solo puede retirar dinero de una caja activa.');
            return;
        }

        abrirRetiroCajaDesdeFactura(data);
    }, true);

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-cerrar');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        ocultarMenuAccionesCajaFactura(boton);

        var data = obtenerFilaCajaFacturaPorBoton(boton);

        if (!data || !esCajaActivaFactura(data)) {
            showNotify('error', 'Caja cerrada', 'La caja seleccionada ya está cerrada.');
            return;
        }

        cerrarCajaDesdeFactura(data);
    }, true);

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-comprobante');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        ocultarMenuAccionesCajaFactura(boton);

        var data = obtenerFilaCajaFacturaPorBoton(boton);

        if (!data || !data.apertura_id) {
            showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja.');
            return;
        }

        if (typeof printComprobanteCajas === 'function') {
            printComprobanteCajas(data.apertura_id);
        } else {
            showNotify('error', 'Función no disponible', 'No está disponible la función para imprimir comprobante.');
        }
    }, true);

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-retiros-detalle');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        ocultarMenuAccionesCajaFactura(boton);

        var data = obtenerFilaCajaFacturaPorBoton(boton);

        if (!data || !data.apertura_id) {
            showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja.');
            return;
        }

        cargarDetalleRetirosCaja(data.apertura_id, 'caja');
    }, true);

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-ganancia');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        ocultarMenuAccionesCajaFactura(boton);

        var data = obtenerFilaCajaFacturaPorBoton(boton);

        if (!data || !data.apertura_id) {
            showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja.');
            return;
        }

        cargarDesgloseGananciaCaja(data.apertura_id, 'caja');
    }, true);
}

/* =========================================================
   FIN - EVENTOS NATIVOS DE ACCIONES CAJA FACTURA
   ========================================================= */


/* =========================================================
   INICIO - ESTILOS DATATABLE CAJA FACTURA
   ========================================================= */

   function aplicarEstilosCajaFactura() {
    if ($('#styleCajaFacturaDataTable').length > 0) {
        return;
    }

    $('head').append(
        '<style id="styleCajaFacturaDataTable">' +
            '#dataTableCajaFactura {' +
                'width: 100% !important;' +
            '}' +

            '#dataTableCajaFactura thead th {' +
                'font-size: 13px !important;' +
                'font-weight: 700 !important;' +
                'vertical-align: middle !important;' +
                'white-space: nowrap !important;' +
            '}' +

            '#dataTableCajaFactura tbody td {' +
                'font-size: 13px !important;' +
                'vertical-align: middle !important;' +
            '}' +

            '#dataTableCajaFactura tbody td.cf-col-monto {' +
                'font-size: 13px !important;' +
                'font-weight: 600 !important;' +
                'text-align: right !important;' +
                'white-space: nowrap !important;' +
            '}' +

            '#dataTableCajaFactura .cf-monto-tabla {' +
                'display: inline-block !important;' +
                'font-size: 13px !important;' +
                'font-weight: 600 !important;' +
                'line-height: 1.2 !important;' +
                'min-width: 85px !important;' +
                'text-align: right !important;' +
            '}' +

            '#dataTableCajaFactura .cf-monto-positivo {' +
                'color: #008000 !important;' +
            '}' +

            '#dataTableCajaFactura .cf-monto-negativo {' +
                'color: #dc3545 !important;' +
            '}' +

            '#dataTableCajaFactura .cf-monto-cero {' +
                'color: #2d2d2d !important;' +
            '}' +

            '#dataTableCajaFactura tfoot th {' +
                'font-size: 13px !important;' +
                'font-weight: 700 !important;' +
                'vertical-align: middle !important;' +
                'white-space: nowrap !important;' +
                'background: #ffffff !important;' +
            '}' +

            '#dataTableCajaFactura tfoot th.cf-total-monto {' +
                'font-size: 13px !important;' +
                'font-weight: 700 !important;' +
                'text-align: right !important;' +
            '}' +

            '#dataTableCajaFactura .cf-total-tabla {' +
                'display: inline-block !important;' +
                'font-size: 13px !important;' +
                'font-weight: 700 !important;' +
                'line-height: 1.2 !important;' +
                'min-width: 85px !important;' +
                'text-align: right !important;' +
                'color: #2d2d2d !important;' +
            '}' +

            '#dataTableCajaFactura .acciones-caja-wrap {' +
                'display: flex !important;' +
                'align-items: center !important;' +
                'justify-content: center !important;' +
                'gap: 6px !important;' +
            '}' +
        '</style>'
    );
}

/* =========================================================
   FIN - ESTILOS DATATABLE CAJA FACTURA
   ========================================================= */


/* =========================================================
   INICIO - FUNCIONES DE MONEDA CAJA FACTURA
   ========================================================= */

function parseMontoCajaFactura(valor) {
    if (valor === null || valor === undefined || valor === '') {
        return 0;
    }

    if (typeof valor === 'number') {
        return valor;
    }

    valor = valor.toString();

    valor = valor
        .replace(/L\./g, '')
        .replace(/,/g, '')
        .replace(/<[^>]*>/g, '')
        .trim();

    var numero = parseFloat(valor);

    if (isNaN(numero)) {
        return 0;
    }

    return numero;
}

function formatoMonedaCajaFactura(valor) {
    var numero = parseMontoCajaFactura(valor);

    return 'L. ' + numero.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderMonedaColorCajaFactura(data, type) {
    var valor = parseMontoCajaFactura(data);

    if (type !== 'display') {
        return valor;
    }

    var claseColor = 'cf-monto-cero';

    if (valor > 0) {
        claseColor = 'cf-monto-positivo';
    } else if (valor < 0) {
        claseColor = 'cf-monto-negativo';
    }

    return '<span class="cf-monto-tabla ' + claseColor + '">' + formatoMonedaCajaFactura(valor) + '</span>';
}

/* =========================================================
   FIN - FUNCIONES DE MONEDA CAJA FACTURA
   ========================================================= */


/* =========================================================
   INICIO - HEADER Y FOOTER DATATABLE CAJA FACTURA
   ========================================================= */

function construirHeaderFooterCajaFactura() {
    var $tabla = $('#dataTableCajaFactura');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Usuario</th>' +
                '<th>Factura Inicial</th>' +
                '<th>Factura Final</th>' +
                '<th>Monto Apertura</th>' +
                '<th>Venta del Día</th>' +
                '<th>Retiro Caja</th>' +
                '<th>Neto Caja</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right">Totales:</th>' +
                '<th id="cf_total_apertura" class="cf-total-monto"><span class="cf-total-tabla">L. 0.00</span></th>' +
                '<th id="cf_total_venta" class="cf-total-monto"><span class="cf-total-tabla">L. 0.00</span></th>' +
                '<th id="cf_total_retiro" class="cf-total-monto"><span class="cf-total-tabla">L. 0.00</span></th>' +
                '<th id="cf_total_neto" class="cf-total-monto"><span class="cf-total-tabla">L. 0.00</span></th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   FIN - HEADER Y FOOTER DATATABLE CAJA FACTURA
   ========================================================= */


/* =========================================================
   INICIO - CARGAR DATATABLE CAJA DESDE FACTURACIÓN
   ========================================================= */

function cargarCajaFactura() {
    aplicarEstilosCajaFactura();

    var fechai = $('#fecha_caja_factura_i').val();
    var fechaf = $('#fecha_caja_factura_f').val();
    var estado = $('#estado_caja_factura').val();

    if (!fechai) {
        fechai = new Date().toISOString().split('T')[0];
        $('#fecha_caja_factura_i').val(fechai);
    }

    if (!fechaf) {
        fechaf = fechai;
        $('#fecha_caja_factura_f').val(fechaf);
    }

    if (!estado) {
        estado = 0;
        $('#estado_caja_factura').val(estado);
    }

    if ($.fn.DataTable.isDataTable('#dataTableCajaFactura')) {
        $('#dataTableCajaFactura').DataTable().clear().destroy();
    }

    construirHeaderFooterCajaFactura();

    $('#dataTableCajaFactura').DataTable({
        destroy: true,
        autoWidth: false,
        responsive: false,
        stateSave: false,
        bDestroy: true,
        language: idioma_español,
        lengthMenu: lengthMenu,
        dom: dom,

        ajax: {
            method: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTableCajaDisponibles.php',
            dataType: 'json',
            data: {
                fechai: fechai,
                fechaf: fechaf,
                estado: estado,
                solo_mi_caja: 1,
                origen: 'facturacion'
            },
            error: function (xhr) {
                                showNotify('error', 'Error de comunicación', 'No se pudo cargar la información de caja.');
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center align-middle',
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }

                    var activa = esCajaActivaFactura(row);

                    var badgeEstado = activa
                        ? '<span class="badge-estado-caja badge-caja-abierta"><i class="fas fa-circle"></i> Abierta</span>'
                        : '<span class="badge-estado-caja badge-caja-cerrada"><i class="fas fa-lock"></i> Cerrada</span>';

                    var accionesCaja = '';

                    if (activa) {
                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-cerrar btn-cf-cerrar">' +
                                '<span class="accion-icon accion-icon-success">' +
                                    '<i class="fas fa-lock"></i>' +
                                '</span>' +
                                '<span class="accion-label">Cerrar caja</span>' +
                            '</button>';

                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-retiro btn-cf-retirar">' +
                                '<span class="accion-icon accion-icon-warning">' +
                                    '<i class="fas fa-money-bill-wave"></i>' +
                                '</span>' +
                                '<span class="accion-label">Retirar dinero</span>' +
                            '</button>';

                    } else {
                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-cerrada" disabled>' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-lock"></i>' +
                                '</span>' +
                                '<span class="accion-label">Caja cerrada</span>' +
                            '</button>';

                        accionesCaja +=
                            '<button type="button" class="dropdown-item accion-item accion-no-retiro" disabled>' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-ban"></i>' +
                                '</span>' +
                                '<span class="accion-label">Retiro no disponible</span>' +
                            '</button>';
                    }

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-comprobante btn-cf-comprobante">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="far fa-file-pdf"></i>' +
                            '</span>' +
                            '<span class="accion-label">Comprobante</span>' +
                        '</button>';

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-retiros-detalle btn-cf-retiros-detalle">' +
                            '<span class="accion-icon accion-icon-warning">' +
                                '<i class="fas fa-list-ul"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver retiros</span>' +
                        '</button>';

                    accionesCaja +=
                        '<button type="button" class="dropdown-item accion-item accion-ganancia btn-cf-ganancia">' +
                            '<span class="accion-icon accion-icon-primary">' +
                                '<i class="fas fa-chart-line"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver ganancia</span>' +
                        '</button>';

                    return '' +
                        '<div class="acciones-caja-wrap">' +
                            '<div class="dropdown acciones-dropdown">' +
                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +
                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                    accionesCaja +
                                '</div>' +
                            '</div>' +
                            badgeEstado +
                        '</div>';
                }
            },
            { data: 'fecha' },
            { data: 'usuario' },
            { data: 'factura_inicial' },
            { data: 'factura_final' },
            {
                data: 'monto_apertura',
                render: function (data, type) {
                    return renderMonedaColorCajaFactura(data, type);
                }
            },
            {
                data: 'importe_venta',
                render: function (data, type) {
                    return renderMonedaColorCajaFactura(data, type);
                }
            },
            {
                data: 'retiro_caja',
                render: function (data, type) {
                    return renderMonedaColorCajaFactura(data, type);
                }
            },
            {
                data: 'neto',
                render: function (data, type) {
                    return renderMonedaColorCajaFactura(data, type);
                }
            }
        ],

        columnDefs: [
            {
                targets: [5, 6, 7, 8],
                className: 'text-right text-nowrap cf-col-monto'
            },
            {
                targets: [0, 1, 3, 4],
                className: 'text-center text-nowrap'
            },
            {
                targets: [2],
                className: 'text-nowrap'
            }
        ],

        createdRow: function (row, data) {
            if (esCajaActivaFactura(data)) {
                $(row).addClass('fila-caja-abierta');
            } else {
                $(row).addClass('fila-caja-cerrada');
            }
        },

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Caja desde Facturación',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function () {
                    cargarCajaFactura();
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Caja desde Facturación',
                className: 'table_reportes btn btn-success ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Caja desde Facturación',
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function (doc) {
                    if (typeof imagen !== 'undefined' && imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],

        footerCallback: function () {
            var api = this.api();

            var totalApertura = api.column(5, { page: 'current' }).data().reduce(function (a, b) {
                return parseMontoCajaFactura(a) + parseMontoCajaFactura(b);
            }, 0);

            var totalVenta = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                return parseMontoCajaFactura(a) + parseMontoCajaFactura(b);
            }, 0);

            var totalRetiro = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                return parseMontoCajaFactura(a) + parseMontoCajaFactura(b);
            }, 0);

            var totalNeto = api.column(8, { page: 'current' }).data().reduce(function (a, b) {
                return parseMontoCajaFactura(a) + parseMontoCajaFactura(b);
            }, 0);

            $('#cf_total_apertura').html('<span class="cf-total-tabla">' + formatoMonedaCajaFactura(totalApertura) + '</span>');
            $('#cf_total_venta').html('<span class="cf-total-tabla">' + formatoMonedaCajaFactura(totalVenta) + '</span>');
            $('#cf_total_retiro').html('<span class="cf-total-tabla">' + formatoMonedaCajaFactura(totalRetiro) + '</span>');
            $('#cf_total_neto').html('<span class="cf-total-tabla">' + formatoMonedaCajaFactura(totalNeto) + '</span>');
        },

        drawCallback: function () {
            if (typeof getPermisosTipoUsuarioAccesosTable === 'function' && typeof getPrivilegioTipoUsuario === 'function') {
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }

            if (typeof cerrarDropdownAcciones === 'function') {
                cerrarDropdownAcciones();
            }

            $('[title]').tooltip({
                container: 'body',
                placement: 'top'
            });
        }
    });

    $('#dataTableCajaFactura').DataTable().search('').draw();
}

/* =========================================================
   FIN - CARGAR DATATABLE CAJA DESDE FACTURACIÓN
   ========================================================= */


/* =========================================================
   INICIO - ABRIR RETIRO DE CAJA DESDE FACTURACIÓN
   ========================================================= */

   function abrirRetiroCajaDesdeFactura(data) {
    if ($('#modalRetiroCaja').length === 0 || $('#formRetiroCaja').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No se encontró el modal de retiro de caja en esta vista.');
        return;
    }

    if (!data || !data.apertura_id) {
        showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja.');
        return;
    }

    $('#formRetiroCaja')[0].reset();

    $('#retiro_apertura_id').val(data.apertura_id);

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

    $('#retiro_total_retirar_text').html(formatoMonedaCajaFactura(0));
    $('#retiro_saldo_final_efectivo_text').html(formatoMonedaCajaFactura(0));
    $('#retiro_saldo_final_transferencia_text').html(formatoMonedaCajaFactura(0));
    $('#retiro_saldo_final_text').html(formatoMonedaCajaFactura(0));

    $('#retiro_mensaje_validacion').hide().html('');

    $('#retiro_box_efectivo').removeClass('retiro-input-error');
    $('#retiro_box_transferencia').removeClass('retiro-input-error');

    $('#retiro_monto_efectivo').val('').prop('disabled', true);
    $('#retiro_monto_transferencia').val('').prop('disabled', true);

    $('#btn_guardar_retiro_caja').prop('disabled', true);

    if ($.fn.selectpicker) {
        $('#retiro_categoria_gastos_id').selectpicker('val', '');
        $('#retiro_categoria_gastos_id').selectpicker('refresh');
    }

    cargarSaldoRetiroCajaFactura(data.apertura_id, function () {
        $('#retiro_monto_efectivo').prop('disabled', false);
        $('#retiro_monto_transferencia').prop('disabled', false);

        validarRetiroCajaFactura(false);
    });

    $('#modalRetiroCaja')
        .off('shown.bs.modal.cajaFactura')
        .on('shown.bs.modal.cajaFactura', function () {
            setTimeout(function () {
                $('#retiro_monto_efectivo').trigger('focus').select();
            }, 150);
        });

    aplicarModalEstaticoCajaFactura('#modalRetiroCaja');

    $('#modalRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

function cargarSaldoRetiroCajaFactura(apertura_id, callback) {
    apertura_id = parseInt(apertura_id || $('#retiro_apertura_id').val() || 0);

    if (apertura_id <= 0) {
        showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja para consultar el saldo.');
        $('#modalRetiroCaja').modal('hide');
        return;
    }

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/getSaldoRetiroCaja.php',
        dataType: 'json',
        data: {
            apertura_id: apertura_id,
            solo_mi_caja: 1,
            origen: 'facturacion'
        },
        success: function (response) {
            if (!response || !response.success) {
                                showNotify(
                    'error',
                    'Error',
                    response && response.message ? response.message : 'No se pudo obtener el saldo disponible para retiro.'
                );

                $('#modalRetiroCaja').modal('hide');
                return;
            }

            var saldoEfectivo = parseMontoCajaFactura(response.saldo_efectivo);
            var saldoTransferencia = parseMontoCajaFactura(response.saldo_transferencia);
            var saldoTotal = parseMontoCajaFactura(response.saldo_disponible);

            $('#retiro_apertura_id').val(response.apertura_id || apertura_id);

            $('#retiro_saldo_efectivo').val(saldoEfectivo.toFixed(2));
            $('#retiro_saldo_transferencia').val(saldoTransferencia.toFixed(2));
            $('#retiro_saldo_actual').val(saldoTotal.toFixed(2));

            $('#retiro_saldo_efectivo_text').html(formatoMonedaCajaFactura(saldoEfectivo));
            $('#retiro_saldo_transferencia_text').html(formatoMonedaCajaFactura(saldoTransferencia));
            $('#retiro_saldo_actual_text').html(formatoMonedaCajaFactura(saldoTotal));

            $('#retiro_max_efectivo_text').html(formatoMonedaCajaFactura(saldoEfectivo));
            $('#retiro_max_transferencia_text').html(formatoMonedaCajaFactura(saldoTransferencia));

            $('#retiro_monto_efectivo').attr('max', saldoEfectivo.toFixed(2));
            $('#retiro_monto_transferencia').attr('max', saldoTransferencia.toFixed(2));

            actualizarResumenRetiroCajaFactura();

            if (typeof callback === 'function') {
                callback(response);
            }
        },
        error: function (xhr) {
                        showNotify('error', 'Error', 'Error de comunicación al obtener el saldo disponible.');
            $('#modalRetiroCaja').modal('hide');
        }
    });
}

function actualizarResumenRetiroCajaFactura() {
    var saldoEfectivo = parseMontoCajaFactura($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMontoCajaFactura($('#retiro_saldo_transferencia').val());

    var montoEfectivo = parseMontoCajaFactura($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMontoCajaFactura($('#retiro_monto_transferencia').val());

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

    $('#retiro_total_retirar_text').html(formatoMonedaCajaFactura(totalRetirar));
    $('#retiro_saldo_final_efectivo_text').html(formatoMonedaCajaFactura(saldoFinalEfectivo));
    $('#retiro_saldo_final_transferencia_text').html(formatoMonedaCajaFactura(saldoFinalTransferencia));
    $('#retiro_saldo_final_text').html(formatoMonedaCajaFactura(saldoFinalTotal));
}

function validarRetiroCajaFactura(mostrarMensaje) {
    var saldoEfectivo = parseMontoCajaFactura($('#retiro_saldo_efectivo').val());
    var saldoTransferencia = parseMontoCajaFactura($('#retiro_saldo_transferencia').val());

    var montoEfectivo = parseMontoCajaFactura($('#retiro_monto_efectivo').val());
    var montoTransferencia = parseMontoCajaFactura($('#retiro_monto_transferencia').val());

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

    actualizarResumenRetiroCajaFactura();

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

$(document)
    .off('input.cajaFacturaRetiro change.cajaFacturaRetiro keyup.cajaFacturaRetiro', '#retiro_monto_efectivo, #retiro_monto_transferencia')
    .on('input.cajaFacturaRetiro change.cajaFacturaRetiro keyup.cajaFacturaRetiro', '#retiro_monto_efectivo, #retiro_monto_transferencia', function () {
        validarRetiroCajaFactura(false);
    });

$(document)
    .off('change.cajaFacturaRetiroCategoria', '#retiro_categoria_gastos_id')
    .on('change.cajaFacturaRetiroCategoria', '#retiro_categoria_gastos_id', function () {
        validarRetiroCajaFactura(false);
    });

$(document)
    .off('click.validacionRetiroCajaFactura', '#btn_guardar_retiro_caja')
    .on('click.validacionRetiroCajaFactura', '#btn_guardar_retiro_caja', function (e) {
        if (!validarRetiroCajaFactura(true)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        return true;
    });

$(document)
    .off('submit.validacionRetiroCajaFactura', '#formRetiroCaja')
    .on('submit.validacionRetiroCajaFactura', '#formRetiroCaja', function (e) {
        if (!validarRetiroCajaFactura(true)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        return true;
    });

/* =========================================================
   FIN - ABRIR RETIRO DE CAJA DESDE FACTURACIÓN
   ========================================================= */

/* =========================================================
   INICIO - CUADRE DEL DÍA DESDE FACTURACIÓN
   ---------------------------------------------------------
   ESTE BLOQUE VA EN EL JS DE FACTURACIÓN.

   ¿Qué hace?
   1. Agrega el botón "Cuadre del día" al dropdown de acciones
      de la tabla #dataTableCajaFactura.
   2. Abre el modal público #modalCuadreDiaCaja.
   3. Consulta core/caja/getCuadreDiaCaja.php.
   4. Envía solo_mi_caja=1 y origen=facturacion para que respete
      la caja del usuario desde facturación.
   5. Usa directamente showNotify(), no crea otro método de notificación.
   6. No modifica HTML ni CSS.

   REQUISITOS:
   - El modal #modalCuadreDiaCaja ya debe estar cargado en la vista.
   - Deben existir los IDs del modal:
     cd_total_cobrado, cd_inversion_reposicion, cd_gastos_total,
     cd_total_final_esperado, cd_tabla_gastos, cd_tabla_inversiones, etc.
   ========================================================= */


/* =========================================================
   HELPERS DEL CUADRE DESDE FACTURACIÓN
   ========================================================= */

   function parseMontoCuadreCajaFactura(valor) {
    if (typeof parseMontoCajaFactura === 'function') {
        return parseMontoCajaFactura(valor);
    }

    if (typeof parseMonto === 'function') {
        return parseMonto(valor);
    }

    if (typeof valor === 'string') {
        valor = valor.replace(/L\./g, '').replace(/,/g, '').trim();
    }

    valor = parseFloat(valor || 0);
    return isNaN(valor) ? 0 : valor;
}

function formatoMonedaCuadreCajaFactura(valor) {
    if (typeof formatoMonedaCajaFactura === 'function') {
        return formatoMonedaCajaFactura(valor);
    }

    if (typeof formatoMoneda === 'function') {
        return formatoMoneda(valor);
    }

    valor = parseMontoCuadreCajaFactura(valor);

    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function setTextoCuadreDiaFactura(selector, valor) {
    if ($(selector).length === 0) {
        return;
    }

    $(selector).html(formatoMonedaCuadreCajaFactura(valor));
}

function escaparTextoCuadreDiaFactura(texto) {
    return $('<div>').text(texto || '').html();
}

function obtenerFechasCuadreCajaFactura() {
    var fechai = $('#fecha_caja_factura_i').val();
    var fechaf = $('#fecha_caja_factura_f').val();

    if (!fechai) {
        fechai = new Date().toISOString().split('T')[0];
        $('#fecha_caja_factura_i').val(fechai);
    }

    if (!fechaf) {
        fechaf = fechai;
        $('#fecha_caja_factura_f').val(fechaf);
    }

    return {
        fechai: fechai,
        fechaf: fechaf
    };
}

/* =========================================================
   AGREGA EL BOTÓN "CUADRE DEL DÍA" EN EL DROPDOWN
   ---------------------------------------------------------
   No se toca la función cargarCajaFactura().
   Como no querés modificar el render original, este método
   inserta el botón después de "Ver ganancia".
   ========================================================= */

function agregarBotonCuadreDiaCajaFactura() {
    $('#dataTableCajaFactura .acciones-menu').each(function () {
        var $menu = $(this);

        if ($menu.find('.btn-cf-cuadre-dia').length > 0) {
            return;
        }

        var botonCuadre = ''
            + '<button type="button" class="dropdown-item accion-item accion-cuadre-dia btn-cf-cuadre-dia">'
            + '    <span class="accion-icon accion-icon-success">'
            + '        <i class="fas fa-balance-scale"></i>'
            + '    </span>'
            + '    <span class="accion-label">Cuadre del día</span>'
            + '</button>';

        var $botonGanancia = $menu.find('.btn-cf-ganancia').last();

        if ($botonGanancia.length > 0) {
            $botonGanancia.after(botonCuadre);
        } else {
            $menu.append(botonCuadre);
        }
    });
}

/* =========================================================
   CARGAR CUADRE DEL DÍA DESDE FACTURACIÓN
   ---------------------------------------------------------
   modo = 'caja'     => consulta una apertura específica.
   modo = 'periodo'  => consulta por rango de fechas.
   ========================================================= */

function cargarCuadreDiaCajaFactura(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechas = obtenerFechasCuadreCajaFactura();
    var fechai = fechas.fechai;
    var fechaf = fechas.fechaf;

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

    if ($('#modalCuadreDiaCaja').length === 0) {
        showNotify('error', 'Error', 'No existe el modal #modalCuadreDiaCaja en esta vista.');
        return;
    }

    $('#modalCuadreDiaCaja').data('modo', modo);
    $('#modalCuadreDiaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        $('#cd_contexto_caja').html('Desde ' + fechai + ' hasta ' + fechaf);
    } else {
        $('#cd_contexto_caja').html('Apertura de caja #' + apertura_id);
    }

    setTextoCuadreDiaFactura('#cd_total_cobrado', 0);
    setTextoCuadreDiaFactura('#cd_inversion_reposicion', 0);
    setTextoCuadreDiaFactura('#cd_gastos_total', 0);
    setTextoCuadreDiaFactura('#cd_total_final_esperado', 0);
    setTextoCuadreDiaFactura('#cd_total_final_esperado_tabla', 0);
    setTextoCuadreDiaFactura('#cd_isv_factura_normal_sar', 0);
    setTextoCuadreDiaFactura('#cd_isv_proforma_informativo', 0);
    setTextoCuadreDiaFactura('#cd_isv_total_detalle', 0);

    $('#cd_tabla_gastos tbody').html(
        '<tr><td colspan="3" class="text-center text-muted">Cargando.</td></tr>'
    );

    $('#cd_tabla_inversiones tbody').html(
        '<tr><td colspan="3" class="text-center text-muted">Cargando.</td></tr>'
    );

    if (typeof aplicarModalEstaticoCajaFactura === 'function') {
        aplicarModalEstaticoCajaFactura('#modalCuadreDiaCaja');
    }

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
            fechaf: fechaf,
            solo_mi_caja: 1,
            origen: 'facturacion'
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

            renderCuadreDiaCajaFactura(resumen, gastos, inversiones);

            $('#modalCuadreDiaCaja').modal('handleUpdate');
        },
        error: function (xhr) {
                        showNotify('error', 'Error', 'Error de comunicación al cargar el cuadre del día.');
        }
    });
}

function refrescarCuadreDiaCajaFactura() {
    var apertura_id = parseInt($('#modalCuadreDiaCaja').data('apertura_id') || 0);
    var modo = $('#modalCuadreDiaCaja').data('modo') || 'periodo';

    cargarCuadreDiaCajaFactura(apertura_id, modo);
}

/* =========================================================
   RENDER DEL RESUMEN DEL CUADRE
   ---------------------------------------------------------
   OJO:
   - La tarjeta principal "Inversión / reposición" muestra el costo
     de productos vendidos o inversión sugerida.
   - En la fórmula del efectivo solo se resta inversión manual en efectivo
     si realmente se retiró de caja.
   ========================================================= */

   function renderCuadreDiaCajaFactura(resumen, gastos, inversiones) {
    resumen = resumen || {};
    gastos = gastos || [];
    inversiones = inversiones || [];

    var efectivo = parseMontoCuadreCajaFactura(resumen.efectivo || 0);
    var transferencia = parseMontoCuadreCajaFactura(resumen.transferencia || 0);
    var tarjeta = parseMontoCuadreCajaFactura(resumen.tarjeta || 0);
    var cheque = parseMontoCuadreCajaFactura(resumen.cheque || 0);
    var montoApertura = parseMontoCuadreCajaFactura(resumen.monto_apertura || 0);

    var inversionReposicion = parseMontoCuadreCajaFactura(resumen.inversion_reposicion || 0);
    var inversionManualRegistrada = parseMontoCuadreCajaFactura(resumen.inversion_manual_registrada || 0);
    var inversionMostrar = inversionReposicion > 0 ? inversionReposicion : inversionManualRegistrada;

    var gastosTotal = parseMontoCuadreCajaFactura(resumen.gastos_total || 0);

    var efectivoEsperado = parseMontoCuadreCajaFactura(resumen.efectivo_esperado || 0);
    var transferenciaEsperada = parseMontoCuadreCajaFactura(resumen.transferencia_esperada || 0);
    var tarjetaEsperada = parseMontoCuadreCajaFactura(resumen.tarjeta_esperada || 0);
    var chequeEsperado = parseMontoCuadreCajaFactura(resumen.cheque_esperado || 0);
    var totalFinalEsperado = parseMontoCuadreCajaFactura(resumen.total_final_esperado || 0);

    var isvFacturaNormalSar = parseMontoCuadreCajaFactura(
        resumen.isv_factura_normal_sar !== undefined
            ? resumen.isv_factura_normal_sar
            : resumen.isv_sar_factura_normal || 0
    );

    var isvProformaInformativo = parseMontoCuadreCajaFactura(
        resumen.isv_proforma_informativo !== undefined
            ? resumen.isv_proforma_informativo
            : resumen.isv_proforma || 0
    );

    var isvTotalDetalle = parseMontoCuadreCajaFactura(resumen.isv_total_detalle || 0);

    var inversionManualEfectivo = parseMontoCuadreCajaFactura(resumen.inversion_manual_efectivo || 0);
    var gastosEfectivo = parseMontoCuadreCajaFactura(resumen.gastos_efectivo || 0);

    setTextoCuadreDiaFactura('#cd_total_cobrado', resumen.total_cobrado || 0);
    setTextoCuadreDiaFactura('#cd_inversion_reposicion', inversionMostrar);
    setTextoCuadreDiaFactura('#cd_gastos_total', gastosTotal);
    setTextoCuadreDiaFactura('#cd_total_final_esperado', totalFinalEsperado);
    setTextoCuadreDiaFactura('#cd_total_final_esperado_tabla', totalFinalEsperado);

    setTextoCuadreDiaFactura('#cd_isv_factura_normal_sar', isvFacturaNormalSar);
    setTextoCuadreDiaFactura('#cd_isv_proforma_informativo', isvProformaInformativo);
    setTextoCuadreDiaFactura('#cd_isv_total_detalle', isvTotalDetalle);

    setTextoCuadreDiaFactura('#cd_efectivo', efectivo);
    setTextoCuadreDiaFactura('#cd_transferencia', transferencia);
    setTextoCuadreDiaFactura('#cd_tarjeta', tarjeta);
    setTextoCuadreDiaFactura('#cd_cheque', cheque);
    setTextoCuadreDiaFactura('#cd_monto_apertura', montoApertura);

    setTextoCuadreDiaFactura('#cd_efectivo_esperado', efectivoEsperado);
    setTextoCuadreDiaFactura('#cd_transferencia_esperada', transferenciaEsperada);
    setTextoCuadreDiaFactura('#cd_tarjeta_esperada', tarjetaEsperada);
    setTextoCuadreDiaFactura('#cd_cheque_esperado', chequeEsperado);

    setTextoCuadreDiaFactura('#cd_formula_efectivo', efectivo);
    setTextoCuadreDiaFactura('#cd_formula_apertura', montoApertura);
    setTextoCuadreDiaFactura('#cd_formula_inversion', inversionManualEfectivo);
    setTextoCuadreDiaFactura('#cd_formula_gastos_efectivo', gastosEfectivo);
    setTextoCuadreDiaFactura('#cd_formula_resultado', efectivoEsperado);

    renderTablaGastosCuadreDiaCajaFactura(gastos);
    renderTablaInversionesCuadreDiaCajaFactura(inversiones, inversionReposicion);
}

/* =========================================================
   TABLA DE GASTOS / RETIROS
   ========================================================= */

function renderTablaGastosCuadreDiaCajaFactura(gastos) {
    var html = '';

    if (!gastos || gastos.length === 0) {
        html = '<tr><td colspan="3" class="text-center text-muted">No hay gastos/retiros registrados.</td></tr>';
        $('#cd_tabla_gastos tbody').html(html);
        return;
    }

    gastos.forEach(function (item) {
        html += ''
            + '<tr>'
            + '    <td>' + escaparTextoCuadreDiaFactura(item.tipo || '') + '</td>'
            + '    <td>' + escaparTextoCuadreDiaFactura(item.cuenta || '') + '</td>'
            + '    <td class="text-right font-weight-bold">' + formatoMonedaCuadreCajaFactura(item.monto || 0) + '</td>'
            + '</tr>';
    });

    $('#cd_tabla_gastos tbody').html(html);
}

/* =========================================================
   TABLA DE INVERSIÓN / REPOSICIÓN
   ========================================================= */

function renderTablaInversionesCuadreDiaCajaFactura(inversiones, inversionReposicion) {
    var html = '';

    if (!inversiones || inversiones.length === 0) {
        if (parseMontoCuadreCajaFactura(inversionReposicion) > 0) {
            html = ''
                + '<tr>'
                + '    <td colspan="3" class="text-center text-muted">'
                + '        No hay inversión manual registrada. Se muestra el costo de productos vendidos como inversión/reposición sugerida.'
                + '    </td>'
                + '</tr>';
        } else {
            html = '<tr><td colspan="3" class="text-center text-muted">No hay inversión/reposición registrada.</td></tr>';
        }

        $('#cd_tabla_inversiones tbody').html(html);
        return;
    }

    inversiones.forEach(function (item) {
        html += ''
            + '<tr>'
            + '    <td>' + escaparTextoCuadreDiaFactura(item.tipo || '') + '</td>'
            + '    <td>' + escaparTextoCuadreDiaFactura(item.cuenta || '') + '</td>'
            + '    <td class="text-right font-weight-bold">' + formatoMonedaCuadreCajaFactura(item.monto || 0) + '</td>'
            + '</tr>';
    });

    $('#cd_tabla_inversiones tbody').html(html);
}

/* =========================================================
   IMPRIMIR / TICKET DEL CUADRE
   ========================================================= */

function imprimirCuadreDiaCajaFactura() {
    var contenido = document.getElementById('cd_ticket_area');

    if (!contenido) {
        showNotify('error', 'Error', 'No se encontró el área del ticket del cuadre.');
        return;
    }

    var ventana = window.open('', '_blank', 'width=900,height=700');

    if (!ventana) {
        showNotify('error', 'Error', 'El navegador bloqueó la ventana de impresión.');
        return;
    }

    ventana.document.open();
    ventana.document.write(
        '<html>' +
            '<head>' +
                '<title>Cuadre del día</title>' +
                '<style>' +
                    'body{font-family:Arial,sans-serif;font-size:13px;color:#111827;padding:20px;}' +
                    '.card{border:1px solid #ddd;margin-bottom:12px;}' +
                    '.card-body{padding:12px;}' +
                    '.card-header{padding:10px 12px;border-bottom:1px solid #ddd;font-weight:bold;}' +
                    '.row{display:flex;flex-wrap:wrap;margin:0 -6px;}' +
                    '[class*="col-"]{box-sizing:border-box;padding:0 6px;}' +
                    '.col-lg-3,.col-md-6{width:25%;}' +
                    '.col-lg-6{width:50%;}' +
                    '.col-lg-12{width:100%;}' +
                    '.mb-3{margin-bottom:12px;}' +
                    '.table{width:100%;border-collapse:collapse;}' +
                    '.table th,.table td{border-bottom:1px solid #ddd;padding:6px;}' +
                    '.text-right{text-align:right;}' +
                    '.text-center{text-align:center;}' +
                    '.font-weight-bold{font-weight:bold;}' +
                    '.text-success{color:#16a34a;}' +
                    '.text-danger{color:#dc2626;}' +
                    '.text-warning{color:#d97706;}' +
                    '.text-primary{color:#2563eb;}' +
                    '.text-muted{color:#6b7280;}' +
                    '.alert{border:1px solid #bfdbfe;background:#eff6ff;padding:10px;margin-bottom:12px;}' +
                    'button,.modal-footer{display:none!important;}' +
                    '@media print{body{padding:0;} .card{break-inside:avoid;}}' +
                '</style>' +
            '</head>' +
            '<body>' +
                contenido.innerHTML +
            '</body>' +
        '</html>'
    );
    ventana.document.close();

    setTimeout(function () {
        ventana.focus();
        ventana.print();
    }, 400);
}

/* =========================================================
   EVENTOS DEL CUADRE DESDE FACTURACIÓN
   ========================================================= */

if (!window.__eventosCuadreCajaFacturaRegistrados) {
    window.__eventosCuadreCajaFacturaRegistrados = true;

    document.addEventListener('click', function (e) {
        var boton = e.target.closest('#dataTableCajaFactura .btn-cf-cuadre-dia');

        if (!boton) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (typeof ocultarMenuAccionesCajaFactura === 'function') {
            ocultarMenuAccionesCajaFactura(boton);
        }

        var data = null;

        if (typeof obtenerFilaCajaFacturaPorBoton === 'function') {
            data = obtenerFilaCajaFacturaPorBoton(boton);
        } else if ($.fn.DataTable.isDataTable('#dataTableCajaFactura')) {
            var $tr = $(boton).closest('tr');

            if ($tr.hasClass('child')) {
                $tr = $tr.prev();
            }

            data = $('#dataTableCajaFactura').DataTable().row($tr).data();
        }

        if (!data || !data.apertura_id) {
            showNotify('error', 'Error', 'No se encontró la apertura de caja.');
            return;
        }

        cargarCuadreDiaCajaFactura(data.apertura_id, 'caja');
    }, true);

    $(document)
        .off('click.cuadreCajaFacturaActualizar', '#btnActualizarCuadreDia')
        .on('click.cuadreCajaFacturaActualizar', '#btnActualizarCuadreDia', function () {
            refrescarCuadreDiaCajaFactura();
        });

    $(document)
        .off('click.cuadreCajaFacturaImprimir', '#btnImprimirCuadreDia')
        .on('click.cuadreCajaFacturaImprimir', '#btnImprimirCuadreDia', function () {
            imprimirCuadreDiaCajaFactura();
        });

    $(document)
        .off('click.cuadreCajaFacturaPeriodo', '#btnCuadreDia')
        .on('click.cuadreCajaFacturaPeriodo', '#btnCuadreDia', function () {
            cargarCuadreDiaCajaFactura(0, 'periodo');
        });

    $(document)
        .off('draw.dt.cuadreCajaFactura', '#dataTableCajaFactura')
        .on('draw.dt.cuadreCajaFactura', '#dataTableCajaFactura', function () {
            agregarBotonCuadreDiaCajaFactura();
        });

    $(document)
        .off('shown.bs.modal.cuadreCajaFactura', '#modalCajaFactura')
        .on('shown.bs.modal.cuadreCajaFactura', '#modalCajaFactura', function () {
            setTimeout(function () {
                agregarBotonCuadreDiaCajaFactura();
            }, 250);
        });

    $(document)
        .off('show.bs.modal.cuadreCajaFacturaEstatico', '#modalCuadreDiaCaja')
        .on('show.bs.modal.cuadreCajaFacturaEstatico', '#modalCuadreDiaCaja', function () {
            if (typeof aplicarModalEstaticoCajaFactura === 'function') {
                aplicarModalEstaticoCajaFactura('#modalCuadreDiaCaja');
            }
        });

    $(document).ajaxComplete(function (event, xhr, settings) {
        var url = '';

        if (settings && settings.url) {
            url = String(settings.url).toLowerCase();
        }

        if (
            $('#modalCuadreDiaCaja').hasClass('show') &&
            (
                url.indexOf('addretirocaja') !== -1 ||
                url.indexOf('registrarretirocaja') !== -1 ||
                url.indexOf('guardarretir') !== -1 ||
                url.indexOf('guardar_retiro') !== -1 ||
                url.indexOf('reintegrarretirocaja') !== -1
            )
        ) {
            setTimeout(function () {
                refrescarCuadreDiaCajaFactura();
            }, 400);
        }
    });
}

/* =========================================================
   INTENTO INICIAL
   ---------------------------------------------------------
   Por si la tabla ya está dibujada cuando este bloque carga.
   ========================================================= */

setTimeout(function () {
    agregarBotonCuadreDiaCajaFactura();
}, 600);

/* =========================================================
   FIN - CUADRE DEL DÍA DESDE FACTURACIÓN
   ========================================================= */

/* =========================================================
   INICIO - CERRAR CAJA DESDE FACTURACIÓN
   Este bloque pertenece a FACTURAS.

   Funciona igual que el módulo Caja:
   1. Llama core/editarCajas.php.
   2. Llena #formAperturaCaja.
   3. Cambia el formulario a modo Cerrar Caja.
   4. Abre #modal_apertura_caja.
   5. El cierre real lo hace el submit normal del formulario.
   ========================================================= */

function cerrarCajaDesdeFactura(data) {
    if (!data || !data.apertura_id) {
        showNotify('error', 'Apertura no encontrada', 'No se encontró la apertura de caja.');
        return;
    }

    if (!esCajaActivaFactura(data)) {
        showNotify('error', 'Caja cerrada', 'La caja seleccionada ya está cerrada.');
        return;
    }

    if ($('#formAperturaCaja').length === 0) {
        showNotify('error', 'Formulario no encontrado', 'No se encontró el formulario de apertura/cierre de caja en esta vista.');
        return;
    }

    if ($('#modal_apertura_caja').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No se encontró el modal de apertura/cierre de caja en esta vista.');
        return;
    }

    prepararFormularioCierreCajaDesdeFactura(data);
}

function prepararFormularioCierreCajaDesdeFactura(data) {
    var url = '<?php echo SERVERURL;?>core/editarCajas.php';

    $('#formAperturaCaja #apertura_id').val(data.apertura_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formAperturaCaja').serialize(),
        dataType: 'text',
        success: function (registro) {
            var valores = null;

            try {
                valores = eval(registro);
            } catch (e) {
                                showNotify('error', 'Respuesta inválida', 'No se pudo leer la información de la caja.');
                return;
            }

            if (!valores || valores.length < 4) {
                                showNotify('error', 'Datos incompletos', 'No se recibieron los datos necesarios para cerrar la caja.');
                return;
            }

            $('#formAperturaCaja').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/addCierreCajaAjax.php'
            });

            $('#formAperturaCaja')[0].reset();

            if ($('#open_caja').length > 0) {
                $('#open_caja').hide();
            }

            if ($('#close_caja').length > 0) {
                $('#close_caja').show();
            }

            $('#formAperturaCaja #apertura_id').val(data.apertura_id);
            $('#formAperturaCaja #usuario_apertura').val(valores[0]);
            $('#formAperturaCaja #monto_apertura').val(valores[1]);
            $('#formAperturaCaja #fecha_apertura').val(valores[2]);
            $('#formAperturaCaja #colaboradores_id_apertura').val(valores[3]);

            $('#formAperturaCaja #usuario_apertura').attr('readonly', true);
            $('#formAperturaCaja #monto_apertura').attr('readonly', true);
            $('#formAperturaCaja #fecha_apertura').attr('readonly', true);

            $('#formAperturaCaja #proceso_aperturaCaja').val('Cerrar Caja');

            $('#modal_apertura_caja')
                .off('hidden.bs.modal.cajaFacturaCierre')
                .on('hidden.bs.modal.cajaFacturaCierre', function () {
                    if (typeof cargarCajaFactura === 'function') {
                        cargarCajaFactura();
                    }

                    if (typeof validarAperturaCajaUsuario === 'function') {
                        validarAperturaCajaUsuario();
                    }

                    if (typeof getCajero === 'function') {
                        getCajero();
                    }

                    if (typeof listar_registro_cajas === 'function') {
                        listar_registro_cajas();
                    }
                });

            aplicarModalEstaticoCajaFactura('#modal_apertura_caja');

            $('#modal_apertura_caja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
                        showNotify('error', 'Error de comunicación', 'No se pudo obtener la información de la caja para cerrar.');
        }
    });
}

/* =========================================================
   FIN - CERRAR CAJA DESDE FACTURACIÓN
   ========================================================= */


/* =========================================================
   INICIO - CARGAR DETALLE RETIROS CAJA
   ========================================================= */

function cargarDetalleRetirosCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechas = obtenerFechasCajaFacturacion();
    var fechai = fechas.fechai;
    var fechaf = fechas.fechaf;

    if (!modo) {
        modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';
    }

    if (modo === 'caja' && apertura_id <= 0) {
        showNotify('error', 'Apertura inválida', 'No se recibió una apertura válida.');
        return;
    }

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Fechas requeridas', 'Debe seleccionar fecha inicial y fecha final.');
            return;
        }
    }

    if ($('#modalDetalleRetirosCaja').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No existe el modal modalDetalleRetirosCaja en esta vista.');
        return;
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

    if ($.fn.DataTable.isDataTable('#dataTableDetalleRetirosCaja')) {
        $('#dataTableDetalleRetirosCaja').DataTable().clear().destroy();
    }

    construirHeaderFooterDetalleRetirosCaja();

    aplicarModalEstaticoCajaFactura('#modalDetalleRetirosCaja');

    $('#modalDetalleRetirosCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });

    setTimeout(function () {
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/caja/getRetirosCaja.php',
            dataType: 'json',
            data: {
                apertura_id: apertura_id,
                modo: modo,
                fechai: fechai,
                fechaf: fechaf,
                solo_mi_caja: 1,
                origen: 'facturacion'
            },
            success: function (response) {
                if (!response || !response.success) {
                                        showNotify('error', 'No se pudo cargar', response && response.message ? response.message : 'No se pudo cargar el detalle de retiros.');
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
                $('#modalDetalleRetirosCaja').modal('handleUpdate');
            },
            error: function (xhr) {
                                showNotify('error', 'Error de comunicación', 'Error de comunicación al cargar los retiros de caja.');
            }
        });
    }, 150);
}

function refrescarDetalleRetirosCaja() {
    var apertura_id = parseInt($('#modalDetalleRetirosCaja').data('apertura_id') || $('#dr_apertura_id').val() || 0);
    var modo = $('#modalDetalleRetirosCaja').data('modo') || 'caja';

    cargarDetalleRetirosCaja(apertura_id, modo);
}

/* =========================================================
   FIN - CARGAR DETALLE RETIROS CAJA
   ========================================================= */


/* =========================================================
   INICIO - DATATABLE DETALLE RETIROS CAJA
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
                className: 'text-center text-nowrap',
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
                data: 'apertura_id',
                className: 'text-center text-nowrap',
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
            { data: 'fecha' },
            { data: 'motivo' },
            { data: 'observacion' },
            { data: 'cuenta' },
            { data: 'factura_egreso' },
            {
                data: 'monto',
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: 'estado_label',
                className: 'text-center',
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
            { data: 'fecha_registro' }
        ],
        columnDefs: [
            {
                targets: [7],
                className: 'text-right text-nowrap'
            },
            {
                targets: [0, 1, 2, 6, 8, 9],
                className: 'text-center text-nowrap'
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
            $('.btn-reintegrar-retiro').off('click.cajaFacturaReintegro').on('click.cajaFacturaReintegro', function () {
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
   FIN - DATATABLE DETALLE RETIROS CAJA
   ========================================================= */


/* =========================================================
   INICIO - REINTEGRO DE RETIROS
   ========================================================= */

function abrirModalReintegroRetiroCaja(caja_retiros_id, apertura_id, monto) {
    caja_retiros_id = parseInt(caja_retiros_id || 0);
    apertura_id = parseInt(apertura_id || 0);
    monto = parseMonto(monto);

    if (caja_retiros_id <= 0 || apertura_id <= 0 || monto <= 0) {
        showNotify('error', 'Datos inválidos', 'No se pudo cargar la información del retiro.');
        return;
    }

    if ($('#modalReintegroRetiroCaja').length === 0 || $('#formReintegroRetiroCaja').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No existe el modal de reintegro de retiro en esta vista.');
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
        .off('shown.bs.modal.cajaFactura')
        .on('shown.bs.modal.cajaFactura', function () {
            setTimeout(function () {
                $('#reintegro_monto').trigger('focus').select();
            }, 150);
        });

    aplicarModalEstaticoCajaFactura('#modalReintegroRetiroCaja');

    $('#modalReintegroRetiroCaja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formReintegroRetiroCaja').off('submit.cajaFacturaReintegro').on('submit.cajaFacturaReintegro', function (e) {
    e.preventDefault();

    var montoActual = parseMonto($('#reintegro_monto_actual').val());
    var montoReintegro = parseMonto($('#reintegro_monto').val());

    if (montoReintegro <= 0) {
        showNotify('error', 'Monto inválido', 'Ingrese un monto válido para reintegrar.');
        return;
    }

    if (montoReintegro > montoActual) {
        showNotify('error', 'Monto inválido', 'El monto a reintegrar no puede ser mayor al retiro actual.');
        return;
    }

    $('#btnGuardarReintegroRetiroCaja').prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/caja/reintegrarRetiroCaja.php',
        dataType: 'json',
        data: $('#formReintegroRetiroCaja').serialize() + '&solo_mi_caja=1&origen=facturacion',
        success: function (response) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);

            if (!response.success) {
                showNotify('error', 'No se pudo reintegrar', response.message || 'No se pudo realizar el reintegro.');
                return;
            }

            $('#modalReintegroRetiroCaja').modal('hide');

            showNotify('success', 'Reintegro registrado', response.message || 'Reintegro registrado correctamente.');

            refrescarDetalleRetirosCaja();

            if (typeof cargarCajaFactura === 'function') {
                cargarCajaFactura();
            }

            if (typeof validarAperturaCajaUsuario === 'function') {
                validarAperturaCajaUsuario();
            }

            if ($('#modalDesgloseGananciaCaja').hasClass('show')) {
                refrescarDesgloseGananciaCaja();
            }
        },
        error: function (xhr) {
            $('#btnGuardarReintegroRetiroCaja').prop('disabled', false);
                        showNotify('error', 'Error de comunicación', 'Error de comunicación al registrar el reintegro.');
        }
    });
});

/* =========================================================
   FIN - REINTEGRO DE RETIROS
   ========================================================= */


/* =========================================================
   INICIO - DESGLOSE GANANCIA CAJA
   ========================================================= */

 /* =========================================================
   INICIO - DESGLOSE GANANCIA CAJA
   ========================================================= */
function cargarDesgloseGananciaCaja(apertura_id, modo) {
    apertura_id = parseInt(apertura_id || 0);

    var fechas = obtenerFechasCajaFacturacion();
    var fechai = fechas.fechai;
    var fechaf = fechas.fechaf;

    if (!modo) {
        modo = 'caja';
    }

    if ($('#modalDesgloseGananciaCaja').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No existe el modal de desglose de ganancia en esta vista.');
        return;
    }

    $('#dg_apertura_id').val(apertura_id);
    $('#dg_modo').val(modo);
    $('#modalDesgloseGananciaCaja').data('modo', modo);
    $('#modalDesgloseGananciaCaja').data('apertura_id', apertura_id);

    if (modo === 'periodo') {
        if (fechai === '' || fechaf === '') {
            showNotify('error', 'Fechas requeridas', 'Debe seleccionar fecha inicial y fecha final.');
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
            fechaf: fechaf,
            solo_mi_caja: 1,
            origen: 'facturacion'
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
            $('#dg_costo_productos_2').html('Cargando...');
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

            var totalVendido = parseMonto(resumen.total_vendido || resumen.total_cobrado || 0);
            var totalCobrado = parseMonto(resumen.total_cobrado || totalVendido || 0);
            var pendienteCobro = parseMonto(resumen.pendiente_cobro || 0);

            if (pendienteCobro < 0.01 && pendienteCobro > -0.01) {
                pendienteCobro = 0;
            }

            var otrosIngresos = parseMonto(resumen.otros_ingresos || resumen.total_ingresos_registrados || 0);
            var totalGastos = parseMonto(resumen.total_gastos_reales || resumen.total_gastos || 0);
            var totalEgresosRegistrados = parseMonto(resumen.total_egresos_registrados || 0);
            var totalInversionApartada = parseMonto(resumen.total_inversion_apartada || resumen.egreso_inversion_apartada || 0);

            var retiroCajaPendiente = parseMonto(resumen.retiro_caja_pendiente || 0);
            var retiroCajaTotal = parseMonto(resumen.retiro_caja_total || resumen.retiro_caja || 0);
            var retiroCajaConvertido = parseMonto(resumen.retiro_caja_convertido_gasto || 0);

            var netoDisponible = parseMonto(resumen.neto_disponible || 0);
            var netoTotalFacturado = parseMonto(resumen.neto_total_facturado || 0);

            if (netoTotalFacturado === 0 && (netoDisponible > 0 || pendienteCobro > 0)) {
                netoTotalFacturado = netoDisponible + pendienteCobro;
            }

            var efectivo = parseMonto(resumen.efectivo || 0);
            var transferencia = parseMonto(resumen.transferencia || 0);
            var tarjeta = parseMonto(resumen.tarjeta || 0);
            var cheque = parseMonto(resumen.cheque || 0);

            var montoApertura = parseMonto(resumen.monto_apertura || 0);
            var efectivoEsperadoCaja = parseMonto(resumen.efectivo_esperado_caja || resumen.efectivo_esperado || 0);

            var costoProductos = parseMonto(resumen.costo_productos_vendidos || 0);
            var totalVendidoDetalle = parseMonto(resumen.total_vendido_detalle || resumen.venta_base_productos || 0);
            var gananciaBruta = parseMonto(resumen.ganancia_bruta || resumen.ganancia_productos || 0);

            var dineroRecomendadoGuardar = parseMonto(resumen.dinero_recomendado_guardar || costoProductos || 0);
            var dineroDespuesReponer = parseMonto(resumen.dinero_despues_reponer || 0);

            var porcentajeCosto = parseMonto(resumen.porcentaje_costo || 0);
            var porcentajeGanancia = parseMonto(resumen.porcentaje_ganancia || 0);
            var diferenciaConciliacion = parseMonto(resumen.diferencia_conciliacion || 0);

            var isvFacturaNormalSar = parseMonto(
                resumen.isv_factura_normal_sar !== undefined
                    ? resumen.isv_factura_normal_sar
                    : (
                        resumen.isv_sar_factura_normal !== undefined
                            ? resumen.isv_sar_factura_normal
                            : 0
                    )
            );

            var isvProformaInformativo = parseMonto(
                resumen.isv_proforma_informativo !== undefined
                    ? resumen.isv_proforma_informativo
                    : (
                        resumen.isv_proforma !== undefined
                            ? resumen.isv_proforma
                            : 0
                    )
            );

            var isvTotalDetalle = parseMonto(resumen.isv_total_detalle || 0);

            isvFacturaNormalSar = Math.round(isvFacturaNormalSar * 100) / 100;
            isvProformaInformativo = Math.round(isvProformaInformativo * 100) / 100;
            isvTotalDetalle = Math.round(isvTotalDetalle * 100) / 100;

            $('#dg_total_vendido').html(formatoMoneda(totalCobrado));
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

            if ($('#dg_costo_productos_2').length > 0) {
                $('#dg_costo_productos_2').html(formatoMoneda(costoProductos));
            }

            $('#dg_ganancia_bruta').html(formatoMoneda(gananciaBruta));
            $('#dg_dinero_recomendado_guardar').html(formatoMoneda(dineroRecomendadoGuardar));

            if ($('#dg_dinero_despues_reponer').length > 0) {
                $('#dg_dinero_despues_reponer').html(formatoMoneda(dineroDespuesReponer));
            }

            $('#dg_porcentaje_costo').html(porcentajeCosto.toFixed(2) + '%');
            $('#dg_porcentaje_ganancia').html(porcentajeGanancia.toFixed(2) + '%');
            $('#dg_diferencia_conciliacion').html(formatoMoneda(diferenciaConciliacion));

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

            if (typeof aplicarModalEstaticoCajaFactura === 'function') {
                aplicarModalEstaticoCajaFactura('#modalDesgloseGananciaCaja');
                aplicarModalEstaticoCajaFactura('#modalCuadreDiaCaja');
            }

            $('#modalDesgloseGananciaCaja').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function (xhr) {
                        showNotify('error', 'Error de comunicación', 'Error de comunicación al cargar el desglose de ganancia.');
        }
    });
}

function refrescarDesgloseGananciaCaja() {
    var apertura_id = parseInt($('#dg_apertura_id').val() || $('#modalDesgloseGananciaCaja').data('apertura_id') || 0);
    var modo = $('#dg_modo').val() || $('#modalDesgloseGananciaCaja').data('modo') || 'caja';

    cargarDesgloseGananciaCaja(apertura_id, modo);
}

/* =========================================================
   FIN - DESGLOSE GANANCIA CAJA
   ========================================================= */

function refrescarDesgloseGananciaCaja() {
    var apertura_id = parseInt($('#dg_apertura_id').val() || $('#modalDesgloseGananciaCaja').data('apertura_id') || 0);
    var modo = $('#dg_modo').val() || $('#modalDesgloseGananciaCaja').data('modo') || 'caja';

    cargarDesgloseGananciaCaja(apertura_id, modo);
}

/* =========================================================
   FIN - DESGLOSE GANANCIA CAJA
   ========================================================= */


/* =========================================================
   INICIO - DATATABLE DETALLE GANANCIA CAJA
   ========================================================= */

function construirHeaderFooterDetalleGananciaCaja() {
    var $tabla = $('#dataTableDetalleGananciaCaja');

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Factura</th>' +
                '<th>Producto</th>' +
                '<th>Cantidad</th>' +
                '<th>Costo Unit.</th>' +
                '<th>Precio Venta</th>' +
                '<th>Total Costo</th>' +
                '<th>Total Venta</th>' +
                '<th>Ganancia</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right">Totales:</th>' +
                '<th id="dg_footer_total_costo">L. 0.00</th>' +
                '<th id="dg_footer_total_venta">L. 0.00</th>' +
                '<th id="dg_footer_total_ganancia">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

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
            { data: 'factura' },
            { data: 'producto' },
            { data: 'cantidad' },
            {
                data: 'costo_unitario',
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: 'precio_venta',
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: 'total_costo',
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: 'total_venta',
                render: function (data, type) {
                    return type === 'display' ? formatoMoneda(data) : parseMonto(data);
                }
            },
            {
                data: 'ganancia',
                render: function (data, type) {
                    return renderMonedaColor(data, type);
                }
            }
        ],
        columnDefs: [
            {
                targets: [3, 4, 5, 6, 7],
                className: 'text-right text-nowrap'
            },
            {
                targets: [0, 2],
                className: 'text-center text-nowrap'
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
                title: 'Detalle de Ganancia',
                className: 'btn btn-success'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                orientation: 'landscape',
                title: 'Detalle de Ganancia',
                className: 'btn btn-danger'
            }
        ],
        footerCallback: function () {
            var api = this.api();

            var totalCosto = api.column(5, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalVenta = api.column(6, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            var totalGanancia = api.column(7, { page: 'current' }).data().reduce(function (a, b) {
                return parseMonto(a) + parseMonto(b);
            }, 0);

            $('#dg_footer_total_costo').html('<span>' + formatoMoneda(totalCosto) + '</span>');
            $('#dg_footer_total_venta').html('<span>' + formatoMoneda(totalVenta) + '</span>');
            $('#dg_footer_total_ganancia').html('<span>' + formatoMoneda(totalGanancia) + '</span>');
        }
    });
}

/* =========================================================
   FIN - DATATABLE DETALLE GANANCIA CAJA
   ========================================================= */


/* =========================================================
   FIN - CAJA DESDE FACTURACIÓN
   ========================================================= */


function getClientesFacturasCXC() {
    var url = '<?php echo SERVERURL; ?>core/getClientesCXC.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').html("");
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').html(data);
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').selectpicker('refresh');
        }
    });
}

function getFacturador() {
    var url = '<?php echo SERVERURL; ?>core/getFacturador.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_bill #facturador').html("");
            $('#formulario_bill #facturador').html(data);
            $('#formulario_bill #facturador').selectpicker('refresh');
        }
    });
}

function getVendedores() {
    var url = '<?php echo SERVERURL; ?>core/getColaboradores.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#formulario_bill #vendedor').html("");
            $('#formulario_bill #vendedor').html(data);
            $('#formulario_bill #vendedor').selectpicker('refresh');
        }
    });
}

//INICIO CONSULTA FACTURAS BORRADOR
$(() => {
    $("#modal_buscar_bill_draft").on('shown.bs.modal', function() {
        $(this).find('#formulario_bill_draft #buscar').focus();
    });
});
//FIN CONSULTA FACTURAS BORRADOR

//INIICO CONSULTA DE FACTURAS
$(() => {
    $("#modal_buscar_bill").on('shown.bs.modal', function() {
        $(this).find('#formulario_bill #buscar').focus();
    });
});

$('#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #facturador').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #vendedor').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #fechai').on("change", function(e) {
    listar_busqueda_bill();
});

$('#formulario_bill #fechaf').on("change", function(e) {
    listar_busqueda_bill();
});
//FIN CONSULTA DE FACTURAS

//INICIO CUENTAS POR COBRAR CLIENTES
$('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});


$('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

$('#formulario_busqueda_cuentas_cobrar_clientes #fechai').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

$('#formulario_busqueda_cuentas_cobrar_clientes #fechaf').on("change", function(e) {
    listar_busqueda_cuentas_por_cobrar_clientes();
});

//FIN CUENTAS POR COBRAR CLIENTES

function resetRow() {
    $("#invoice-form #bill_row, #bill_row").val(0);
}

$('#formulario_busqueda_productos_facturacion #almacen_facturas').on('change', function() {
    listar_productos_factura_buscar();
});

//INICIO BUSQUEDA FROMULARIO CLIENTES FACTURACION
$("#invoice-form #add_cliente").on("click", function(e) {
    e.preventDefault();
    searchCustomersBill();
});

function searchCustomersBill() {
    listar_clientes_factura_buscar();
    $('#modal_buscar_clientes_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$("#invoice-form #btn_apertura").on("click", function(e) {
    e.preventDefault();
    formAperturaBill();
});

function formAperturaBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Aperturar Caja");
    $('#open_caja').show();
    $('#close_caja').hide();
    $('#formAperturaCaja #monto_apertura_grupo').show();

    $('#formAperturaCaja').attr({
        'data-form': 'save'
    });
    $('#formAperturaCaja').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addAperturaCajaAjax.php'
    });

    $('#modal_apertura_caja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#reg_factura').off('click').on('click', function(e) {
    e.preventDefault();
    ProcesarFactura();
});

function obtenerTotalFacturaSeguro() {
    calculateTotalFacturas();

    var total = normalizarNumeroFactura($('#invoice-form #totalAftertax').val());

    if (total <= 0) {
        total = normalizarNumeroFactura($('#invoice-form #totalAftertaxFooter').val());
    }

    if (total <= 0) {
        total = normalizarNumeroFactura($('#totalAftertax').val());
    }

    if (total <= 0) {
        total = normalizarNumeroFactura($('#totalAftertaxFooter').val());
    }

    if (total <= 0) {
        var subtotal = normalizarNumeroFactura($('#invoice-form #subTotal').val());
        var isv15 = normalizarNumeroFactura($('#invoice-form #taxAmount').val());
        var isv18 = normalizarNumeroFactura($('#invoice-form #taxAmount18').val());
        var descuento = normalizarNumeroFactura($('#invoice-form #taxDescuento').val());

        total = (subtotal + isv15 + isv18) - descuento;
    }

    return total;
}

function validarFacturaAntesDeEnviar() {
    calculateTotalFacturas();

    if (getConsultarAperturaCaja() == 2) {
        showNotify('error', 'Error', 'Lo sentimos debe aperturar la caja antes de continuar');
        return false;
    }

    if ($("#invoice-form #cliente_id").val() === "" || $("#invoice-form #cliente").val() === "") {
        showNotify('error', 'Error', 'Debe seleccionar un cliente antes de continuar');
        return false;
    }

    if ($("#invoice-form #colaborador_id").val() === "" || $("#invoice-form #colaborador").val() === "") {
        showNotify('error', 'Error', 'Debe seleccionar un vendedor antes de continuar');
        return false;
    }

    if (!hayDetalleFactura()) {
        showNotify('error', 'Error', 'Debe agregar al menos un producto antes de continuar');
        return false;
    }

    var total = obtenerTotalFacturaSeguro();

    if (total <= 0) {
        showNotify('error', 'Error', 'El total de la factura debe ser mayor a cero');
        return false;
    }

    return true;
}

/* =========================================================
   CONTROL DE BLOQUEO DE BOTONES FACTURA
   ---------------------------------------------------------
   Problema corregido:
   - Guardar/Registrar deshabilitaban los botones antes de abrir
     el modal de confirmación del formulario Ajax.
   - Si el usuario presionaba Cancelar en ese modal, no había AJAX,
     por eso nunca se ejecutaba ajaxComplete y los botones quedaban
     bloqueados.

   Solución:
   - Se mantiene el bloqueo mientras está el modal/confirmación.
   - Si el usuario cancela y no arrancó AJAX, se desbloquea.
   - Si el usuario confirma y sí arranca AJAX, se desbloquea hasta
     ajaxComplete/ajaxError de addFacturaAjax.php/addFacturaOpenAjax.php.
========================================================= */

window.__facturaProcesando = false;
window.__facturaAjaxEnCurso = false;
window.__facturaWatchdogCancel = null;

function setEstadoProcesandoFactura(activo) {
    window.__facturaProcesando = activo === true;

    var $botonesFactura = $('#reg_factura, #guardar_factura');

    $botonesFactura.prop('disabled', window.__facturaProcesando);

    if (window.__facturaProcesando) {
        $botonesFactura.addClass('disabled');
    } else {
        $botonesFactura.removeClass('disabled');
    }
}

function esAjaxFacturaUrl(url) {
    url = String(url || '').toLowerCase();

    return (
        url.indexOf('addfacturaajax.php') !== -1 ||
        url.indexOf('addfacturaopenajax.php') !== -1
    );
}

function hayModalConfirmacionFacturaVisible() {
    return (
        $('.swal-overlay--show-modal:visible').length > 0 ||
        $('.swal-modal:visible').length > 0 ||
        $('.swal2-container:visible').length > 0 ||
        $('.bootbox.modal.show:visible').length > 0 ||
        $('.modal.show:visible').filter(function () {
            var id = String($(this).attr('id') || '').toLowerCase();
            return id.indexOf('confirm') !== -1 || id.indexOf('alert') !== -1 || id.indexOf('swal') !== -1;
        }).length > 0
    );
}

function desbloquearFacturaSiNoHayAjax(origen) {
    setTimeout(function () {
        if (window.__facturaAjaxEnCurso === true) {
            return;
        }

        setEstadoProcesandoFactura(false);
    }, 180);
}

function iniciarVigilanciaCancelacionFactura() {
    if (window.__facturaWatchdogCancel) {
        clearInterval(window.__facturaWatchdogCancel);
        window.__facturaWatchdogCancel = null;
    }

    var intentos = 0;

    window.__facturaWatchdogCancel = setInterval(function () {
        intentos++;

        if (window.__facturaAjaxEnCurso === true) {
            clearInterval(window.__facturaWatchdogCancel);
            window.__facturaWatchdogCancel = null;
            return;
        }

        if (window.__facturaProcesando === true && !hayModalConfirmacionFacturaVisible() && intentos >= 2) {
            clearInterval(window.__facturaWatchdogCancel);
            window.__facturaWatchdogCancel = null;
            setEstadoProcesandoFactura(false);
            return;
        }

        if (intentos >= 60) {
            clearInterval(window.__facturaWatchdogCancel);
            window.__facturaWatchdogCancel = null;

            if (window.__facturaAjaxEnCurso !== true) {
                setEstadoProcesandoFactura(false);
            }
        }
    }, 250);
}

$(document)
    .off('ajaxSend.facturaProcesando ajaxComplete.facturaProcesando ajaxError.facturaProcesando')
    .on('ajaxSend.facturaProcesando', function (event, xhr, settings) {
        var url = settings && settings.url ? settings.url : '';

        if (esAjaxFacturaUrl(url)) {
            window.__facturaAjaxEnCurso = true;
            setEstadoProcesandoFactura(true);
        }
    })
    .on('ajaxComplete.facturaProcesando ajaxError.facturaProcesando', function (event, xhr, settings) {
        var url = settings && settings.url ? settings.url : '';

        if (esAjaxFacturaUrl(url)) {
            window.__facturaAjaxEnCurso = false;
            setEstadoProcesandoFactura(false);

            if (window.__facturaWatchdogCancel) {
                clearInterval(window.__facturaWatchdogCancel);
                window.__facturaWatchdogCancel = null;
            }
        }
    });

$(document)
    .off('click.facturaCancelarConfirmacion')
    .on('click.facturaCancelarConfirmacion', '.swal-button--cancel, .swal2-cancel, .bootbox .btn-secondary, .bootbox .btn-default, [data-dismiss="modal"]', function () {
        if (window.__facturaProcesando === true && window.__facturaAjaxEnCurso !== true) {
            desbloquearFacturaSiNoHayAjax('cancel-click');
        }
    });

$(document)
    .off('keyup.facturaCancelarConfirmacion')
    .on('keyup.facturaCancelarConfirmacion', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if (window.__facturaProcesando === true && window.__facturaAjaxEnCurso !== true) {
                desbloquearFacturaSiNoHayAjax('escape');
            }
        }
    });

function prepararEnvioFacturaAjax(actionUrl) {
    setEstadoProcesandoFactura(true);

    $('#invoice-form').attr({
        'data-form': 'save'
    });

    $('#invoice-form').attr({
        'action': actionUrl
    });

    iniciarVigilanciaCancelacionFactura();
    $('#invoice-form').trigger('submit');
}

function ProcesarFactura(){
    if (window.__facturaProcesando === true) {
        showNotify('warning', 'Proceso en curso', 'La factura ya se está procesando. Espere un momento.');
        return;
    }

    if (!validarFacturaAntesDeEnviar()) {
        setEstadoProcesandoFactura(false);
        return;
    }

    prepararEnvioFacturaAjax('<?php echo SERVERURL; ?>ajax/addFacturaAjax.php');
}

$('#reg_factura').off('click').on('click', function(e) {
    e.preventDefault();
    ProcesarFactura();
});

$('#guardar_factura').off('click').on('click', function(e) {
    e.preventDefault();
    GuardarFactura();
});

function GuardarFactura(){
    if (window.__facturaProcesando === true) {
        showNotify('warning', 'Proceso en curso', 'La factura ya se está procesando. Espere un momento.');
        return;
    }

    if (!validarFacturaAntesDeEnviar()) {
        setEstadoProcesandoFactura(false);
        return;
    }

    prepararEnvioFacturaAjax('<?php echo SERVERURL; ?>ajax/addFacturaOpenAjax.php');
}

$("#invoice-form #btn_cierre").on("click", function(e) {
    e.preventDefault();
    formCierreBill();
});

function formCierreBill() {
    $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
    $('#open_caja').hide();
    $('#close_caja').show();
    $('#formAperturaCaja #monto_apertura_grupo').hide();

    $('#formAperturaCaja').attr({
        'data-form': 'save'
    });
    $('#formAperturaCaja').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addCierreCajaFacturasAjax.php'
    });

    $('#modal_apertura_caja').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}
//FIN BUSQUEDA FROMULARIO CLIENTES FACTURACION	

//INICIO INVOICES
//INICIO BUSQUEDA CLIENTES EN FACTURACION
$('#invoice-form #buscar_clientes').on('click', function(e) {
    e.preventDefault();
    listar_clientes_factura_buscar();
    $('#modal_buscar_clientes_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var listar_clientes_factura_buscar = function() {
    var table_clientes_factura_buscar = $("#DatatableClientesBusquedaFactura").DataTable({
        "destroy": true,
        "processing": true,
        "deferRender": true,
        "pageLength": 10,
        "searchDelay": 350,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableClientes.php"
        },
        "columns": [
            {
                "defaultContent": "<button type='button' class='table_view btn btn-primary ocultar' title='Seleccionar cliente'><span class='fas fa-copy'></span></button>"
            },
            {
                "defaultContent": "<button type='button' class='table_edit btn btn-warning ocultar' title='Editar cliente'><span class='fas fa-edit'></span></button>"
            },
            {
                "data": "cliente"
            },
            {
                "data": "rtn"
            },
            {
                "data": "correo"
            },
            {
                "data": "telefono"
            }
        ],
        "columnDefs": [
            {
                "targets": [0, 1],
                "orderable": false,
                "searchable": false,
                "className": "text-center text-nowrap"
            },
            {
                "targets": [2, 3, 4, 5],
                "className": "align-middle"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Clientes',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_clientes_factura_buscar();
                }
            },
            {
                text: '<i class="fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Clientes',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_clientes();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            $('[title]').tooltip({
                container: "body",
                placement: "top"
            });
        }
    });

    table_clientes_factura_buscar.search('').draw();

    setTimeout(function() {
        $('#modal_buscar_clientes_facturacion .dataTables_filter input').trigger('focus');
    }, 200);

    view_clientes_busqueda_factura_dataTable(
        "#DatatableClientesBusquedaFactura tbody",
        table_clientes_factura_buscar
    );
};

var view_clientes_busqueda_factura_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_view");
    $(tbody).on("click", "button.table_view", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data) {
            showNotify('error', 'Error', 'No se pudo obtener la información del cliente.');
            return;
        }

        $('#invoice-form #cliente_id').val(data.clientes_id);
        $('#invoice-form #cliente').val(data.cliente);
        $('#invoice-form #client-customers-bill').html("<b>Cliente: </b> " + data.cliente);
        $('#invoice-form #rtn-customers-bill').html("<b>RTN: </b>" + data.rtn);

        $('#modal_buscar_clientes_facturacion').modal('hide');
    });

    $(tbody).off("click", "button.table_edit");
    $(tbody).on("click", "button.table_edit", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data) {
            showNotify('error', 'Error', 'No se pudo obtener la información del cliente.');
            return;
        }

        abrirEditarClienteDesdeFacturacion(data);
    });
};

function abrirEditarClienteDesdeFacturacion(data) {
    if (!data || !data.clientes_id) {
        showNotify('error', 'Error', 'No se pudo obtener el código del cliente.');
        return;
    }

    if ($('#modal_registrar_clientes').length === 0 || $('#formClientes').length === 0) {
        showNotify('error', 'Error', 'No se encontró el modal de clientes en esta vista.');
        return;
    }

    var url = '<?php echo SERVERURL;?>core/editarClientes.php';

    $('#formClientes #clientes_id').val(data.clientes_id);

    $.ajax({
        type: 'POST',
        url: url,
        data: $('#formClientes').serialize(),
        dataType: 'json',
        success: function(respuesta) {
            $('#formClientes').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/modificarClientesAjax.php'
            }).trigger('reset');

            $('#formClientes #clientes_id').val(data.clientes_id);

            $('#reg_cliente').hide();
            $('#edi_cliente').show();

            if ($('#delete_cliente').length > 0) {
                $('#delete_cliente').hide();
            }

            $('#formClientes #nombre_clientes').val(respuesta.nombre || '');
            $('#formClientes #identidad_clientes').val(respuesta.rtn || '');
            $('#formClientes #fecha_clientes').attr('disabled', true).val(respuesta.fecha || '');

            $('#formClientes #departamento_cliente').val(respuesta.departamentos_id || '');

            if ($.fn.selectpicker) {
                $('#formClientes #departamento_cliente').selectpicker('refresh');
            }

            if (typeof getMunicipiosClientes === 'function') {
                getMunicipiosClientes(respuesta.municipios_id);
            }

            setTimeout(function() {
                $('#formClientes #municipio_cliente').val(respuesta.municipios_id || '');

                if ($.fn.selectpicker) {
                    $('#formClientes #municipio_cliente').selectpicker('refresh');
                }
            }, 250);

            $('#formClientes #dirección_clientes').val(respuesta.localidad || '');
            $('#formClientes #telefono_clientes').val(respuesta.telefono || '');
            $('#formClientes #correo_clientes').val(respuesta.correo || '');
            $('#formClientes #clientes_activo').prop('checked', respuesta.estado == 1);

            $('#card_puntos_cliente').show();

            var puntos = respuesta.puntos || 0;
            $('#puntos_acumulados').val(puntos);

            var fechaActualizacion = 'No existe';

            if (respuesta.ultima_actualizacion && respuesta.ultima_actualizacion !== 'No existe') {
                var fecha = new Date(respuesta.ultima_actualizacion);

                if (!isNaN(fecha.getTime())) {
                    fechaActualizacion = fecha.toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }

            $('#puntos_ultima_actualizacion').val(fechaActualizacion);

            if (puntos == 0) {
                $('#puntos_acumulados').removeClass('text-success').addClass('text-muted');
            } else {
                $('#puntos_acumulados').removeClass('text-muted').addClass('text-success');
            }

            if (fechaActualizacion === 'No existe') {
                $('#puntos_ultima_actualizacion').addClass('text-muted');
            } else {
                $('#puntos_ultima_actualizacion').removeClass('text-muted');
            }

            $('#btn_ver_historial_puntos').off('click').on('click', function() {
                $('#modal_historial_puntos').modal('show');

                if (typeof cargarHistorialPuntos === 'function') {
                    cargarHistorialPuntos(data.clientes_id);
                }
            });

            $('#formClientes #nombre_clientes').attr('readonly', false);

            $('#formClientes #departamento_cliente').attr('disabled', false);
            $('#formClientes #municipio_cliente').attr('disabled', false);
            $('#formClientes #dirección_clientes').attr('disabled', false);

            $('#formClientes #telefono_clientes').attr('readonly', false);
            $('#formClientes #correo_clientes').attr('readonly', false);
            $('#formClientes #clientes_activo').attr('disabled', false);

            $('#formClientes #identidad_clientes').attr('readonly', true);
            $('#formClientes #fecha_clientes').attr('readonly', true);

            $('#formClientes #grupo_editar_rtn_clientes').show();
            $('#formClientes #grupo_editar_rtn').show();

            $('#formClientes #proceso_clientes').val('Editar');

            $('#modal_registrar_clientes .modal-title').html(
                '<i class="fas fa-user-edit mr-2"></i>Editar Cliente'
            );

            $('#modal_registrar_clientes')
                .off('shown.bs.modal.editarClienteFactura')
                .on('shown.bs.modal.editarClienteFactura', function() {
                    setTimeout(function() {
                        $('#formClientes #nombre_clientes').trigger('focus').select();
                    }, 150);
                });

            $('#modal_registrar_clientes')
                .off('hidden.bs.modal.editarClienteFactura')
                .on('hidden.bs.modal.editarClienteFactura', function() {
                    $('#formClientes #fecha_clientes').attr('disabled', false);
                    $('#formClientes #fecha_clientes').attr('readonly', false);

                    if ($('#modal_buscar_clientes_facturacion').hasClass('show')) {
                        listar_clientes_factura_buscar();

                        setTimeout(function() {
                            $('#modal_buscar_clientes_facturacion .dataTables_filter input').trigger('focus');
                        }, 300);
                    }
                });

            $('#modal_registrar_clientes').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        },
        error: function(xhr, status, error) {
                        showNotify('error', 'Error', 'No se pudieron cargar los datos del cliente.');
            $('#modal_registrar_clientes').modal('hide');
        }
    });
}
//FIN BUSQUEDA CLIENTES EN FACTURACION

//INICIO BUSQUEDA COLABORADORES EN FACTURACION
function serchColaboradoresBill() {
    listar_colaboradores_buscar_factura();
    $('#modal_buscar_colaboradores_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#invoice-form #add_vendedor').on('click', function(e) {
    e.preventDefault();
    serchColaboradoresBill();
});

var listar_colaboradores_buscar_factura = function() {
    var table_colaboradores_buscar_factura = $("#DatatableColaboradoresBusquedaFactura").DataTable({
        "destroy": true,
        "processing": true,
        "deferRender": true,
        "pageLength": 10,
        "searchDelay": 350,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableColaboradoresFacturas.php"
        },
        "columns": [{
                "defaultContent": "<button class='table_view btn btn-primary ocultar'><span class='fas fa-copy'></span></button>"
            },
            {
                "data": "colaborador"
            },
            {
                "data": "identidad"
            },
            {
                "data": "telefono"
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [{
                width: "25%",
                targets: 0
            },
            {
                width: "25%",
                targets: 1
            },
            {
                width: "25%",
                targets: 2
            },
            {
                width: "25%",
                targets: 3
            }
        ],
        "buttons": [{
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_colaboradores_buscar_factura();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_colaboradores();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });
    table_colaboradores_buscar_factura.search('').draw();
    $('#buscar').focus();

    view_colaboradores_busqueda_factura_dataTable("#DatatableColaboradoresBusquedaFactura tbody",
        table_colaboradores_buscar_factura);
}

var view_colaboradores_busqueda_factura_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_view");
    $(tbody).on("click", "button.table_view", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#invoice-form #colaborador_id').val(data.colaborador_id);
        $('#invoice-form #colaborador').val(data.colaborador);
        $('#invoice-form #colaborador').val(data.colaborador);
        $('#invoice-form #vendedor-customers-bill').html("<b>Vendedor: </b> " + data.colaborador);
        $('#modal_buscar_colaboradores_facturacion').modal('hide');
    });
}
//FIN BUSQUEDA COLABORADORES EN FACTURACION

//INICIO BUSQUEDA PRODUCTOS FACTURA
$(() => {
    $("#invoice-form #invoiceItem").on('click', '.buscar_productos', function(e) {
        e.preventDefault();
        listar_productos_factura_buscar();
        var row_index = obtenerRowIndexFacturaDesdeElemento(this);
        var col_index = $(this).closest("td").index();

        $('#formulario_busqueda_productos_facturacion #row').val(row_index);
        $('#formulario_busqueda_productos_facturacion #col').val(col_index);
        $('#modal_buscar_productos_facturacion').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    });
});

function renderSaldoProductoBusquedaFactura(data, type) {
    var cantidad = parseFloat(data || 0);

    if (isNaN(cantidad)) {
        cantidad = 0;
    }

    if (type !== 'display') {
        return cantidad;
    }

    var number = $.fn.dataTable.render.number(',', '.', 2, '').display(cantidad);
    var color = '#15803d';
    var fondo = '#dcfce7';
    var borde = '#86efac';
    var icono = 'fa-check-circle';
    var texto = 'Disponible';

    if (cantidad <= 0) {
        color = '#b91c1c';
        fondo = '#fee2e2';
        borde = '#fecaca';
        icono = 'fa-exclamation-triangle';
        texto = 'Sin saldo';
    } else if (cantidad <= 5) {
        color = '#b45309';
        fondo = '#fef3c7';
        borde = '#fde68a';
        icono = 'fa-exclamation-circle';
        texto = 'Saldo bajo';
    }

    return ''
        + '<span style="display:inline-flex;align-items:center;gap:6px;border:1px solid ' + borde + ';background:' + fondo + ';color:' + color + ';border-radius:999px;padding:6px 10px;font-weight:800;white-space:nowrap;">'
        + '  <i class="fas ' + icono + '"></i>'
        + '  <span>' + number + '</span>'
        + '</span>'
        + '<small style="display:block;color:' + color + ';font-weight:700;margin-top:3px;white-space:nowrap;">' + texto + '</small>';
}

var listar_productos_factura_buscar = function() {
    // Bodega seleccionada (si viene vacío, usa 1)
    var bodega = $("#formulario_busqueda_productos_facturacion #almacen_facturas").val();
    bodega = (bodega === "" || bodega == null) ? 1 : bodega;

    var table_productos_factura_buscar = $("#DatatableProductosBusquedaFactura").DataTable({
        destroy: true,
        processing: true,
        deferRender: true,
        pageLength: 10,
        searchDelay: 350,
        "ajax": {
            "method": "POST",
            "url": "<?php echo SERVERURL; ?>core/llenarDataTableProductosFacturas.php",
            "data": { "bodega": bodega }
        },
        "columns": [
            { "defaultContent": "<button class='table_view btn btn-secondary ocultar'><span class='fas fa-cart-plus fa-lg'></span></button>" },
            {
                "data": "image",
                "orderable": false,
                "render": function (data, type, row, meta) {
                    var defaultImageUrl = '<?php echo SERVERURL; ?>vistas/plantilla/img/products/image_preview.png';
                    var imageUrl = data ? '<?php echo SERVERURL; ?>vistas/plantilla/img/products/' + data : defaultImageUrl;
                    var safeTitle = (row && row.nombre) ? String(row.nombre).replace(/"/g, '&quot;') : 'Imagen';
                    return ''
                    + '<div class="d-flex align-items-center">'
                    +   '<img class="table-image mr-2" src="' + imageUrl + '" alt="' + safeTitle + '" style="cursor:pointer;">'
                    +   '<button type="button" class="btn btn-light btn-icon btn-xs btn-zoom iv-trigger"'
                    +     ' data-iv-src="' + imageUrl + '"'
                    +     ' data-iv-fallback="' + defaultImageUrl + '"'
                    +     ' data-iv-title="' + safeTitle + '"'
                    +     ' title="Ver imagen grande">'
                    +     '<i class="fas fa-search-plus"></i>'
                    +   '</button>'
                    + '</div>';
                }
            },
            { "data": "barCode" },
            { "data": "nombre" },
            {
                "data": "cantidad",
                render: function(data, type) {
                    return renderSaldoProductoBusquedaFactura(data, type);
                },
            },
            { "data": "medida" },
            { "data": "tipo_producto_nombre" },
            {
                "data": "precio_venta",
                render: function(data, type) {
                    var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data || 0);

                    if (type === 'display') {
                        var color = (parseFloat(data || 0) < 0) ? 'red' : 'green';
                        return '<span style="color:' + color + ';">' + number + '</span>';
                    }

                    return parseFloat(data || 0);
                },
            },
            {
                // *** BODEGA ***
                // Tu PHP manda: almacen_facturas (nombre) y almacen_id (id).
                // Además, los servicios (tipo_producto_id = 2) no tienen bodega.
                "data": null,
                "render": function(data, type, row) {
                    var esServicio = String(row.tipo_producto_id || '') === '2';
                    if (esServicio) return "Sin bodega";

                    var nombreBodega = (row.almacen_facturas || '').toString().trim();
                    var idBodega = (row.almacen_id == null || row.almacen_id === '') ? 0 : parseInt(row.almacen_id, 10);

                    if (idBodega > 0 && nombreBodega !== "") {
                        return nombreBodega;
                    }
                    return "Sin bodega";
                }
            }
        ],
        "lengthMenu": lengthMenu,
        "stateSave": true,
        "bDestroy": true,
        "responsive": true,
        "language": idioma_español,
        "dom": dom,
        "columnDefs": [
            { width: "2%",  targets: 0 },
            { width: "17%", targets: 1 },
            { width: "17%", targets: 2 },
            { width: "10%", targets: 3 },
            { width: "10%", targets: 4 },
            { width: "10%", targets: 5 },
            { width: "12%", targets: 6 },
            { width: "12%", targets: 7 },
            { width: "12%", targets: 8 }
        ],
        "buttons": [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Productos',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_productos_factura_buscar();
                }
            },
            {
                text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
                titleAttr: 'Agregar Productos',
                className: 'table_crear btn btn-primary ocultar',
                action: function() {
                    modal_productos();
                }
            }
        ],
        "drawCallback": function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    });

    table_productos_factura_buscar.search('').draw();
    $('#buscar').focus();

    view_productos_busqueda_factura_dataTable("#DatatableProductosBusquedaFactura tbody", table_productos_factura_buscar);
};

var view_productos_busqueda_factura_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_view, td img");
  $(tbody).on("click", "button.table_view, td img", async function(e) {
    e.preventDefault();

    var row = parseInt($("#formulario_busqueda_productos_facturacion #row").val() || $("#invoice-form #bill_row").val() || '0', 10);
    row = asegurarFilaFacturaDisponible(row);

    // Caja abierta
    if (getConsultarAperturaCaja() == 2) {
      showNotify('error', 'Error', 'Lo sentimos debe aperturar la caja antes de continuar');
      return;
    }

    getTotalFacturasDisponibles();

    // Validar cliente y vendedor
    if (
      $("#invoice-form #cliente_id").val()     !== "" &&
      $("#invoice-form #cliente").val()        !== "" &&
      $("#invoice-form #colaborador_id").val() !== "" &&
      $("#invoice-form #colaborador").val()    !== ""
    ) {
      var data = table.row($(this).parents("tr")).data() || {};

      // Normalizaciones
      var tipoProductoId = Number(data.tipo_producto_id || 0);           // 1=producto, 2=servicio
      var esServicio     = (tipoProductoId === 2);
      var almacenId      = (data.almacen_id == null || data.almacen_id === '') ? 0 : parseInt(data.almacen_id, 10);
      var existencia     = parseFloat(data.cantidad || 0);

      // ===== Validación de inventario (solo productos con bodega válida) =====
      if (!esServicio && almacenId > 0) {
        var facturar_cero = !!(await facturarEnCeroAlmacen(almacenId));
        if (existencia <= 0 && !facturar_cero) {
          showNotify('error', 'Error', 'No se puede facturar este producto con inventario en cero');
          return false;
        }
      }

      // ===== Set de campos básicos de la fila =====
      $('#invoice-form #invoiceItem #productos_id_'        + row).val(data.productos_id);
      $('#invoice-form #invoiceItem #bar-code-id_'         + row).val(data.barCode || '');
      $('#invoice-form #invoiceItem #productName_'         + row).val(data.nombre || '');
      $('#invoice-form #invoiceItem #quantity_'            + row).val(1).focus();
      $('#invoice-form #invoiceItem #price_'               + row).val(data.precio_venta || 0);
      $('#invoice-form #invoiceItem #discount_'            + row).val(0);
      $('#invoice-form #invoiceItem #isv_'                 + row).val(parseInt(data.impuesto_venta || 0, 10)); // 1=grava, 0=no
      $('#invoice-form #invoiceItem #precio_mayoreo_'      + row).val(data.precio_mayoreo || 0);
      $('#invoice-form #invoiceItem #cantidad_mayoreo_'    + row).val(data.cantidad_mayoreo || 0);
      $('#invoice-form #invoiceItem #medida_'              + row).val(data.medida || '');
      $('#invoice-form #invoiceItem #bodega_'              + row).val(almacenId || '');
      $('#invoice-form #invoiceItem #precio_real_'         + row).val(data.precio_venta || 0);

      // FLAGS por línea (desde PHP): 1 si aplica ese ISV
      var dataGravaISV = parseInt(data.impuesto_venta || data.isv_venta || 0, 10) === 1;
      var dataFlagISV1 = parseInt(data.isv1 || 0, 10) === 1 ? 1 : 0;
      var dataFlagISV2 = parseInt(data.isv2 || 0, 10) === 1 ? 1 : 0;

      // Si grava, pero no trae isv1/isv2, usar ISV id=1 por defecto.
      if (dataGravaISV && dataFlagISV1 === 0 && dataFlagISV2 === 0) {
        dataFlagISV1 = 1;
      }

      if ($('#isv1_flag_' + row).length) $('#isv1_flag_' + row).val(dataFlagISV1);
      if ($('#isv2_flag_' + row).length) $('#isv2_flag_' + row).val(dataFlagISV2);

      // Inicializa montos ISV de la línea
      if ($('#valor_isv_'  + row).length)  $('#valor_isv_'  + row).val('0.00'); // ISV id=1 (p.e. 15%)
      if ($('#valor_isv1_' + row).length)  $('#valor_isv1_' + row).val('0.00'); // ISV id=2 (p.e. 18%)

      // Texto visible de la línea (si lo usas)
      if (typeof actualizarTextoProducto === 'function') {
        actualizarTextoProducto(row, data.nombre || '', data.medida || '');
      }

      // ===== Calcular ISV de la línea (usa flags + % actuales de tabla isv) =====
      await recalcISVForRow(row); // escribe valor_isv_# y valor_isv1_#

      // Totales y nueva fila vacía final
      calculateTotalFacturas();
      asegurarFilaVaciaFinalFactura();

      // UI: ocultar iconos de búsqueda en la fila actual y la anterior
      if (row > 0) {
        var icon_search = row - 1;
        $("#invoice-form #invoiceItem #icon-search-bar_" + icon_search).hide();
      }
      $("#invoice-form #invoiceItem #icon-search-bar_" + row).hide();

      // Cerrar modal
      $('#modal_buscar_productos_facturacion').modal('hide');

      // (El incremento real de bill_row lo maneja addRowFacturas en tu flujo)
      row++;

    } else {
      showNotify('error', 'Error', 'Lo sentimos no se puede seleccionar un producto, por favor verifica cliente y vendedor.');
    }
  });
};
//FIN BUSQUEDA PRODUCTOS FACTURA

/* ===== helper: actualiza price_# por regla de mayoreo ===== */
function actualizarPrecioPorMayoreo(row_index){
  var cm = parseFloat($('#invoice-form #invoiceItem #cantidad_mayoreo_' + row_index).val()) || 0;
  var pm = parseFloat($('#invoice-form #invoiceItem #precio_mayoreo_' + row_index).val()) || 0;
  var q  = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 0;
  var pr = parseFloat($('#invoice-form #invoiceItem #precio_real_' + row_index).val()) || 0;

  var nuevo = (cm > 0 && pm > 0 && q >= cm) ? pm : pr;
  $('#invoice-form #invoiceItem #price_' + row_index).val(nuevo);
}

/* ===== listener único: blur + keyup en cantidad ===== */
$(() => {
  $("#invoice-form #invoiceItem").on('blur keyup', '.buscar_cantidad', function() {
    var row_index = obtenerRowIndexFacturaDesdeElemento(this);

    // 1) Precio mayoreo vs real
    actualizarPrecioPorMayoreo(row_index);

    // 2) Recalcula ISV por línea y totales
    (async () => {
      await recalcularISVLinea(row_index);
      calculateTotalFacturas();
    })();
  });
});

function generarFilaFactura(count) {
  count = parseInt(count || 0, 10);
  if (isNaN(count) || count < 0) {
    count = 0;
  }

  let htmlRow = '<tr data-row-id="' + count + '">';

  htmlRow += '<td><input class="itemRow" id="itemRow_' + count + '" type="checkbox"></td>';

  // --- HIDDENS de control/valores ---
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="referenciaProducto[]" id="referenciaProducto_' + count + '" class="form-control" placeholder="Referencia Producto Precio" autocomplete="off">';
  htmlRow += '<input type="hidden" name="isv[]" id="isv_' + count + '" class="form-control" placeholder="Producto ISV (1=grava,0=no)" autocomplete="off">';
  // ISV1 (id=1) porcentaje (ej. 15.00)
  htmlRow += '<input type="hidden" name="valor_isv[]" id="valor_isv_' + count + '" class="form-control" placeholder="Valor ISV (id=1)" autocomplete="off">';
  // ISV2 (id=2) porcentaje (ej. 18.00)  <<--- NUEVO
  htmlRow += '<input type="hidden" name="valor_isv1[]" id="valor_isv1_' + count + '" class="form-control" placeholder="Valor ISV2 (id=2)" autocomplete="off">';
  htmlRow += '<input type="hidden" name="facturas_detalle_id[]" id="facturas_detalle_id_' + count + '" class="form-control" placeholder="Código Producto" autocomplete="off">';
  htmlRow += '<input type="hidden" name="productos_id[]" id="productos_id_' + count + '" class="form-control inputfield-details1" placeholder="Código del Producto" autocomplete="off">';

  htmlRow += '<input type="hidden" name="isv1_flag[]" id="isv1_flag_' + count + '" value="0">';
  htmlRow += '<input type="hidden" name="isv2_flag[]" id="isv2_flag_' + count + '" value="0">';

  // Código / búsqueda
  htmlRow += '<div class="input-group mb-3"><div class="input-group-prepend">';
  htmlRow += '<button type="button" class="btn btn-link buscar_productos p-0" data-toggle="tooltip" title="Búsqueda de Productos" id="icon-search-bar_' + count + '">';
  htmlRow += '<i class="fas fa-search icon-color" style="font-size: 0.875rem;"></i></button></div>';
  htmlRow += '<input type="text" name="bar-code-id[]" id="bar-code-id_' + count + '" class="form-control product-bar-code inputfield-details1" placeholder="Código del Producto" autocomplete="off"></div>';
  htmlRow += '</td>';

  // Descripción (texto visible + hidden)
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="productName[]" id="productName_' + count + '" autocomplete="off">';
  htmlRow += '<span id="productName_text_' + count + '" class="product-description">Descripción del Producto</span>';
  htmlRow += '</td>';

  // Cantidad
  htmlRow += '<td>';
  htmlRow += '<input type="number" name="quantity[]" id="quantity_' + count + '" step="0.01" placeholder="Cantidad" class="buscar_cantidad form-control inputfield-details" autocomplete="off">';
  htmlRow += '<input type="hidden" name="cantidad_mayoreo[]" id="cantidad_mayoreo_' + count + '" step="0.01" class="form-control inputfield-details" autocomplete="off">';
  htmlRow += '</td>';

  // Medida (texto visible + hidden)
  htmlRow += '<td>';
  htmlRow += '<input type="hidden" name="medida[]" id="medida_' + count + '" autocomplete="off">';
  htmlRow += '<span id="medida_text_' + count + '" class="medida-description">Medida</span>';
  htmlRow += '<input type="hidden" name="bodega[]" id="bodega_' + count + '" class="form-control buscar_bodega" autocomplete="off">';
  htmlRow += '</td>';

  // Precio
  htmlRow += '<td>';
  htmlRow += '<div class="input-group mb-3">';
  htmlRow += '<input type="number" name="price[]" id="price_' + count + '" class="form-control" step="0.01" placeholder="Precio" readonly autocomplete="off">';
  htmlRow += '<div id="suggestions_producto_' + count + '" class="suggestions"></div>';
  htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><i class="aplicar_precio fas fa-plus fa-lg"></i></a></div>';
  htmlRow += '</div>';
  htmlRow += '<input type="hidden" name="pprecio_mayoreo[]" id="precio_mayoreo_' + count + '" class="form-control inputfield-details" readonly autocomplete="off">';
  htmlRow += '<input type="hidden" name="precio_real[]" id="precio_real_' + count + '" class="form-control inputfield-details" readonly autocomplete="off">';
  htmlRow += '</td>';

  // Descuento
  htmlRow += '<td>';
  htmlRow += '<div class="input-group mb-3">';
  htmlRow += '<input type="number" name="discount[]" id="discount_' + count + '" class="form-control" step="0.01" placeholder="Descuento" readonly autocomplete="off">';
  htmlRow += '<div class="input-group-append"><a data-toggle="modal" href="#" class="btn btn-outline-success"><i class="aplicar_descuento fas fa-plus fa-lg"></i></a></div>';
  htmlRow += '</div>';
  htmlRow += '</td>';

  // Total línea (incluye impuestos)
  htmlRow += '<td><input type="number" name="total[]" id="total_' + count + '" placeholder="Total" class="form-control total inputfield-details" readonly autocomplete="off" step="0.01"></td>';

  htmlRow += '</tr>';
  return htmlRow;
}

function limpiarTablaFactura() {
    $("#invoice-form #invoiceItem > tbody").empty();
    let count = 0;
    $('#invoiceItem').append(generarFilaFactura(count));
    $('#invoice-form #bill_row, #bill_row').val(count);
    $("#invoice-form .tableFixHead").scrollTop($(document).height());
    enfocarFilaFactura(count);
}

function limpiarTablaFacturaDetalles(count) {
    count = parseInt(count || 0, 10);
    if (isNaN(count) || count < 0) {
        count = 0;
    }

    $("#invoice-form #invoiceItem > tbody").empty();
    $('#invoiceItem').append(generarFilaFactura(count));
    $('#invoice-form #bill_row, #bill_row').val(count);
    $("#invoice-form .tableFixHead").scrollTop($(document).height());
    enfocarFilaFactura(count);
}

function addRowFacturas() {
    let count = obtenerSiguienteIndiceFilaFactura();
    $('#invoiceItem').append(generarFilaFactura(count));
    $('#invoice-form #bill_row, #bill_row').val(count);
    $("#invoice-form .tableFixHead").scrollTop($(document).height());
    enfocarFilaFactura(count);
    return count;
}

// Función para actualizar la descripción y medida cuando se carga un producto
// (Esta función debe ser llamada desde donde se carga la información del producto)
function actualizarTextoProducto(index, nombreProducto, medidaProducto) {
    // Actualizar inputs ocultos
    $("#productName_" + index).val(nombreProducto);
    $("#medida_" + index).val(medidaProducto);
    
    // Actualizar textos visibles
    $("#productName_text_" + index).text(nombreProducto || "Descripción del Producto");
    $("#medida_text_" + index).text(medidaProducto || "Medida");
}

/* =========================================================
   CONTROL SEGURO DE FILAS DE FACTURA
   ---------------------------------------------------------
   No usamos .closest("tr").index() para identificar la fila,
   porque al borrar filas el índice visual cambia, pero los IDs
   reales siguen siendo productos_id_#, quantity_#, price_#, etc.
   Estas funciones mantienen el correlativo estable y evitan que
   al quitar una línea se escriba en la fila equivocada o se congele
   el flujo de agregar/quitar productos.
========================================================= */
function obtenerRowIndexFacturaDesdeElemento(elemento) {
    var $tr = $(elemento).closest('tr');
    var rowIndex = parseInt($tr.attr('data-row-id') || $tr.data('row-id'), 10);

    if (!isNaN(rowIndex) && rowIndex >= 0) {
        return rowIndex;
    }

    var $campo = $tr.find('[id^="productos_id_"], [id^="bar-code-id_"], [id^="quantity_"], [id^="price_"], [id^="discount_"], [id^="total_"]').first();

    if ($campo.length > 0) {
        var id = String($campo.attr('id') || '');
        var match = id.match(/_(\d+)$/);

        if (match && match[1] !== undefined) {
            rowIndex = parseInt(match[1], 10);

            if (!isNaN(rowIndex) && rowIndex >= 0) {
                $tr.attr('data-row-id', rowIndex);
                return rowIndex;
            }
        }
    }

    rowIndex = $tr.index();
    return (!isNaN(rowIndex) && rowIndex >= 0) ? rowIndex : 0;
}

function obtenerMayorIndiceFilaFactura() {
    var mayor = -1;

    $('#invoice-form #invoiceItem [id^="productos_id_"]').each(function () {
        var id = String($(this).attr('id') || '');
        var match = id.match(/_(\d+)$/);

        if (match && match[1] !== undefined) {
            var numero = parseInt(match[1], 10);

            if (!isNaN(numero) && numero > mayor) {
                mayor = numero;
            }
        }
    });

    return mayor;
}

function sincronizarBillRowFactura() {
    var mayor = obtenerMayorIndiceFilaFactura();

    if (mayor < 0) {
        mayor = 0;
    }

    $('#invoice-form #bill_row, #bill_row').val(mayor);
    return mayor;
}

function obtenerSiguienteIndiceFilaFactura() {
    var mayor = obtenerMayorIndiceFilaFactura();
    return mayor < 0 ? 0 : mayor + 1;
}

function filaFacturaTieneProducto(rowIndex) {
    var pid = $('#invoice-form #invoiceItem #productos_id_' + rowIndex).val();
    var barcode = $('#invoice-form #invoiceItem #bar-code-id_' + rowIndex).val();
    var nombre = $('#invoice-form #invoiceItem #productName_' + rowIndex).val();
    var precio = normalizarNumeroFactura($('#invoice-form #invoiceItem #price_' + rowIndex).val());

    return !!(
        (pid && pid !== '' && pid !== '0') ||
        (barcode && barcode !== '') ||
        (nombre && nombre !== '' && nombre !== 'Descripción del Producto') ||
        precio > 0
    );
}

function asegurarFilaFacturaDisponible(rowIndex) {
    rowIndex = parseInt(rowIndex, 10);

    if (!isNaN(rowIndex) && rowIndex >= 0 && $('#invoice-form #invoiceItem #productos_id_' + rowIndex).length > 0) {
        return rowIndex;
    }

    var primeraLibre = null;

    $('#invoice-form #invoiceItem [id^="productos_id_"]').each(function () {
        if (primeraLibre !== null) {
            return;
        }

        var id = String($(this).attr('id') || '');
        var match = id.match(/_(\d+)$/);

        if (match && match[1] !== undefined) {
            var numero = parseInt(match[1], 10);

            if (!filaFacturaTieneProducto(numero)) {
                primeraLibre = numero;
            }
        }
    });

    if (primeraLibre !== null) {
        return primeraLibre;
    }

    return addRowFacturas();
}

function asegurarFilaVaciaFinalFactura() {
    var tieneFilaVacia = false;

    $('#invoice-form #invoiceItem [id^="productos_id_"]').each(function () {
        var id = String($(this).attr('id') || '');
        var match = id.match(/_(\d+)$/);

        if (match && match[1] !== undefined) {
            var numero = parseInt(match[1], 10);

            if (!filaFacturaTieneProducto(numero)) {
                tieneFilaVacia = true;
                return false;
            }
        }
    });

    if (!tieneFilaVacia) {
        addRowFacturas();
    }
}

function enfocarFilaFactura(rowIndex) {
    setTimeout(function () {
        $('#invoice-form #invoiceItem #bar-code-id_' + rowIndex).trigger('focus').select();
    }, 50);
}


$(() => {
    $("#invoice-form #invoiceItem #bar-code-id_0").focus();

    $(document).on('click', '#checkAll', function() {
        $(".itemRow").prop("checked", this.checked);
    });
    $(document).on('click', '.itemRow', function() {
        if ($('.itemRow:checked').length == $('.itemRow').length) {
            $('#checkAll').prop('checked', true);
        } else {
            $('#checkAll').prop('checked', false);
        }
    });
    var count = $(".itemRow").length;
    $(document).on('click', '#addRows', function() {
        if ($("#invoice-form #cliente").val() != "") {
            addRowFacturas();
        } else {
            showNotify('error', 'Error', 'Lo sentimos no puede agregar más filas, debe seleccionar un usuario antes de poder continuar');
        }
    });
    $(document).on('click', '#removeRows', function() {
        if ($('.itemRow').is(':checked')) {
            $(".itemRow:checked").each(function() {
                $(this).closest('tr').remove();
                count--;
            });

            $('#checkAll').prop('checked', false);

            if ($('#invoice-form #invoiceItem tbody tr').length === 0) {
                $('#invoiceItem').append(generarFilaFactura(0));
                $('#invoice-form #bill_row, #bill_row').val(0);
                enfocarFilaFactura(0);
            } else {
                sincronizarBillRowFactura();
                asegurarFilaVaciaFinalFactura();
            }

            calculateTotalFacturas();
        } else {
            showNotify('error', 'Error', 'Lo sentimos debe seleccionar una fila antes de intentar eliminarla');
        }
    });
    $(document).on('blur', "[id^=quantity_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=quantity_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "[id^=price_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=price_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "[id^=discount_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('keyup', "[id^=discount_]", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "#taxRate", function() {
        calculateTotalFacturas();
    });
    $(document).on('blur', "#amountPaid", function() {
        var amountPaid = $(this).val();
        var totalAftertax = $('#totalAftertax').val();
        if (amountPaid && totalAftertax) {
            totalAftertax = totalAftertax - amountPaid;
            $('#amountDue').val(totalAftertax);
        } else {
            $('#amountDue').val(totalAftertax);
        }
    });
    $(document).on('click', '.deleteInvoice', function() {
        var id = $(this).attr("id");
        if (confirm("Are you sure you want to remove this?")) {
            $.ajax({
                url: "action.php",
                method: "POST",
                dataType: "json",
                data: {
                    id: id,
                    action: 'delete_invoice'
                },
                success: function(response) {
                    if (response.status == 1) {
                        $('#' + id).closest("tr").remove();
                    }
                }
            });
        } else {
            return false;
        }
    });
});

// =====================
// Utilidades
// =====================

var cacheISVFacturaEscritorio = {};

function normalizarNumeroFactura(valor) {
    valor = String(valor || '0')
        .replace(/L/g, '')
        .replace(/\s/g, '')
        .replace(/[^\d.,-]/g, '');

    if (valor === '') return 0;

    // Caso: 7,355.40 formato USA
    if (valor.includes(',') && valor.includes('.')) {
        valor = valor.replace(/,/g, '');
    }
    // Caso: 7355,40 formato latino
    else if (valor.includes(',') && !valor.includes('.')) {
        valor = valor.replace(/,/g, '.');
    }
    // Caso: 15.00 queda igual, NO quitar el punto
    else {
        valor = valor;
    }

    var numero = parseFloat(valor);
    return isNaN(numero) ? 0 : numero;
}

function fetchISVPercentSync(isv_id) {
    isv_id = parseInt(isv_id, 10);

    if (!isv_id || isv_id <= 0) {
        return 0;
    }

    if (cacheISVFacturaEscritorio[isv_id] !== undefined) {
        return cacheISVFacturaEscritorio[isv_id];
    }

    // Valor seguro inmediato mientras la consulta se resuelve en segundo plano.
    var porcentaje = (isv_id === 1 ? 15 : (isv_id === 2 ? 18 : 0));
    cacheISVFacturaEscritorio[isv_id] = porcentaje;

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>core/getISV.php',
        data: {
            isv_id: isv_id
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success === true && response.valor !== undefined) {
                porcentaje = normalizarNumeroFactura(response.valor);
            } else if (response && response.valor !== undefined) {
                porcentaje = normalizarNumeroFactura(response.valor);
            } else if ($.isArray(response) && response.length > 0) {
                porcentaje = normalizarNumeroFactura(response[0]);
            } else if (typeof response === 'number' || typeof response === 'string') {
                porcentaje = normalizarNumeroFactura(response);
            }

            cacheISVFacturaEscritorio[isv_id] = porcentaje;
            actualizarLabelsISVFactura();
        },
        error: function(xhr) {
            // Conserva el valor seguro almacenado sin bloquear la UI.
        }
    });

    return porcentaje;
}

function fetchISVPercent(isv_id) {
    return new Promise(function (resolve) {
        var porcentaje = fetchISVPercentSync(isv_id);
        resolve(porcentaje);
    });
}

/* =========================================================
   INICIO - LABELS DINÁMICOS ISV FACTURACIÓN
   ---------------------------------------------------------
   Actualiza los textos del footer:
   - ISV id=1 según tabla isv.valor
   - ISV id=2 según tabla isv.valor

   Ejemplo:
   isv_id 1 = 15.00 => ISV 15%:
   isv_id 2 = 20.00 => ISV 20%:
   ========================================================= */

   function formatearPorcentajeLabelISVFactura(valor) {
    valor = normalizarNumeroFactura(valor);

    if (valor <= 0) {
        return '';
    }

    if (Number.isInteger(valor)) {
        return valor.toString();
    }

    return valor.toFixed(2).replace(/\.?0+$/, '');
}

function actualizarLabelsISVFactura() {
    var porcentajeISV1 = 0;
    var porcentajeISV2 = 0;

    if (typeof fetchISVPercentSync === 'function') {
        porcentajeISV1 = fetchISVPercentSync(1);
        porcentajeISV2 = fetchISVPercentSync(2);
    }

    var textoISV1 = 'ISV';
    var textoISV2 = 'ISV';

    if (porcentajeISV1 > 0) {
        textoISV1 = 'ISV ' + formatearPorcentajeLabelISVFactura(porcentajeISV1) + '%';
    }

    if (porcentajeISV2 > 0) {
        textoISV2 = 'ISV ' + formatearPorcentajeLabelISVFactura(porcentajeISV2) + '%';
    }

    /*
      No necesitamos cambiar el HTML.
      Buscamos el label según el textarea del footer.
    */
    if ($('#taxAmountFooter').length) {
        $('#taxAmountFooter').closest('.metric').find('label').text(textoISV1 + ':');
    }

    if ($('#taxAmountFooter18').length) {
        $('#taxAmountFooter18').closest('.metric').find('label').text(textoISV2 + ':');
    }

    /*
      Por si existen labels o contenedores similares fuera del footer.
      No afecta si no existen.
    */
    if ($('#taxAmount').length) {
        $('#taxAmount').closest('.metric').find('label').text(textoISV1 + ':');
    }

    if ($('#taxAmount18').length) {
        $('#taxAmount18').closest('.metric').find('label').text(textoISV2 + ':');
    }
}

/* Ejecutar al cargar la vista */
$(() => {
    setTimeout(function () {
        actualizarLabelsISVFactura();
    }, 300);
});

/* =========================================================
   FIN - LABELS DINÁMICOS ISV FACTURACIÓN
   ========================================================= */


/* ===================================================
   CONFIG ISV PROFORMA
   ---------------------------------------------------
   Regla final:
   - Factura normal: calcula ISV según producto.
   - Proforma: calcula ISV según producto SOLO si
     config_id = 6 / "Activar ISV Proforma" tiene activar = 1.
   =================================================== */
window.IZZY_PROFORMA_APLICA_ISV = 0;
window.IZZY_PROFORMA_APLICA_ISV_CARGADO = false;

function facturaActualEsProformaISV() {
    var $proforma = $('#invoice-form #facturas_proforma');

    if (!$proforma.length) {
        return false;
    }

    var tipoInput = String($proforma.attr('type') || '').toLowerCase();

    /*
      Para checkbox/radio se usa SOLO checked.
      No se valida por val(), porque un checkbox puede tener value="1"
      aunque esté apagado.
    */
    if (tipoInput === 'checkbox' || tipoInput === 'radio') {
        return $proforma.is(':checked') === true;
    }

    return String($proforma.val() || '0') === '1';
}

function consultarConfigISVProformaFactura(forzarRecarga) {
    if (window.IZZY_PROFORMA_APLICA_ISV_CARGADO === true && forzarRecarga !== true) {
        return $.Deferred().resolve().promise();
    }

    return $.ajax({
        type: 'GET',
        url: '<?php echo SERVERURL; ?>core/facturas/getIsvConfig.php',
        dataType: 'json',
        cache: false,
        success: function (response) {
            var aplica = 0;

            if (response) {
                if (response.proforma_aplica_isv !== undefined) {
                    aplica = parseInt(response.proforma_aplica_isv || 0, 10);
                } else if (response.activar_isv_proforma !== undefined) {
                    aplica = parseInt(response.activar_isv_proforma || 2, 10) === 1 ? 1 : 0;
                } else if (response.activar !== undefined) {
                    aplica = parseInt(response.activar || 2, 10) === 1 ? 1 : 0;
                }
            }

            window.IZZY_PROFORMA_APLICA_ISV = aplica === 1 ? 1 : 0;
            window.IZZY_PROFORMA_APLICA_ISV_CARGADO = true;

                    },
        error: function (xhr) {
            
            window.IZZY_PROFORMA_APLICA_ISV = 0;
            window.IZZY_PROFORMA_APLICA_ISV_CARGADO = true;
        }
    });
}

function proformaPermiteCalcularISV(forzarRecarga) {
    consultarConfigISVProformaFactura(forzarRecarga === true);

    return parseInt(window.IZZY_PROFORMA_APLICA_ISV || 0, 10) === 1;
}

function documentoActualPermiteCalcularISV(forzarRecarga) {
    if (!facturaActualEsProformaISV()) {
        return true;
    }

    return proformaPermiteCalcularISV(forzarRecarga === true);
}

async function recalcularTodasLineasISVFactura(forzarRecargaConfig) {
    if (forzarRecargaConfig === true) {
        window.IZZY_PROFORMA_APLICA_ISV_CARGADO = false;
        await consultarConfigISVProformaFactura(true);
    } else {
        await consultarConfigISVProformaFactura(false);
    }

    var totalFilas = parseInt($('#bill_row').val() || 0, 10);

    if (isNaN(totalFilas)) {
        totalFilas = 0;
    }

    for (var i = 0; i <= totalFilas; i++) {
        if ($('#price_' + i).length && $('#productos_id_' + i).length && $('#productos_id_' + i).val() !== '') {
            await recalcISVForRow(i);
        }
    }

    calculateTotalFacturas();
}

$(function () {
    consultarConfigISVProformaFactura(true);

    $(document)
        .off('change.isvProformaDocumento', '#invoice-form #facturas_proforma')
        .on('change.isvProformaDocumento', '#invoice-form #facturas_proforma', function () {
            recalcularTodasLineasISVFactura(true);
        });
});


/* ===================================================
   Recalcular ISV por línea
   - Lee: quantity_{row}, price_{row}, discount_{row}
   - Lee flags: isv1_flag_{row}, isv2_flag_{row}
   - Respeta: isv_{row} (1 = grava, 0 = exento)
   - Escribe: valor_isv_{row} (id=1), valor_isv1_{row} (id=2)
   =================================================== */
  async function recalcISVForRow(row){
    const grava   = parseInt($('#invoice-form #invoiceItem #isv_' + row).val() || '0', 10);
    const qty     = parseFloat($('#invoice-form #invoiceItem #quantity_' + row).val()  || '1') || 1;
    const price   = parseFloat($('#invoice-form #invoiceItem #price_'    + row).val()  || '0') || 0;
    const disc    = parseFloat($('#invoice-form #invoiceItem #discount_' + row).val()  || '0') || 0;

    /*
     * Si es proforma y la configuración 'Activar ISV Proforma' está apagada,
     * la línea queda sin ISV aunque el producto tenga ISV activo.
     */
    if (typeof documentoActualPermiteCalcularISV === 'function' && documentoActualPermiteCalcularISV(false) === false) {
        if ($('#valor_isv_'  + row).length)  $('#valor_isv_'  + row).val('0.00');
        if ($('#valor_isv1_' + row).length)  $('#valor_isv1_' + row).val('0.00');
        return;
    }

    // Base neta (no negativa)
    let base = (price * qty) - disc;
    if (base < 0) base = 0;

    // Flags de ISV (1/0)
    var flag1 = parseInt($('#isv1_flag_' + row).val() || '0', 10) === 1;
    var flag2 = parseInt($('#isv2_flag_' + row).val() || '0', 10) === 1;

    /*
     * Si el producto grava ISV pero no trae marcado isv1/isv2,
     * se aplica ISV id=1 por defecto. Esto evita que proforma quite el ISV
     * cuando Activar ISV Proforma = 1 y el producto solo trae isv_venta/impuesto_venta.
     */
    if (grava === 1 && flag1 === false && flag2 === false) {
        flag1 = true;
    }

    let val1 = 0, val2 = 0;

    if (grava === 1 && flag1){
        const p1 = await fetchISVPercent(1);           // ej 15
        val1 = base * ( (parseFloat(p1)||0) / 100.0 );  // a decimal
    }
    if (grava === 1 && flag2){
        const p2 = await fetchISVPercent(2);           // ej 18
        val2 = base * ( (parseFloat(p2)||0) / 100.0 );
    }

    if ($('#valor_isv_'  + row).length)  $('#valor_isv_'  + row).val(val1.toFixed(2));
    if ($('#valor_isv1_' + row).length)  $('#valor_isv1_' + row).val(val2.toFixed(2));
}

// =====================
// Recalculador general
// =====================
function calculateTotalFacturas() {
    if (typeof actualizarLabelsISVFactura === 'function') {
        actualizarLabelsISVFactura();
    }

    var totalAmount   = 0; // suma de (precio * cantidad) SIN ISV y SIN descuento
    var totalGeneral  = 0; // alias del anterior en tu código
    var totalDiscount = 0;
    var totalISV1     = 0; // suma de valor_isv_*  => ISV id=1
    var totalISV2     = 0; // suma de valor_isv1_* => ISV id=2

    $("[id^='price_']").each(function() {
        var id = $(this).attr('id').replace("price_", '');

        var price     = parseFloat($('#price_' + id).val()) || 0;
        var discount  = parseFloat($('#discount_' + id).val()) || 0;
        var quantity  = parseFloat($('#quantity_' + id).val()) || 1;

        var isv1_line = parseFloat($('#valor_isv_' + id).val()) || 0;
        var isv2_line = parseFloat($('#valor_isv1_' + id).val()) || 0;

        var lineBase = price * quantity; // sin ISV, sin descuento

        $('#total_' + id).val(customRound(lineBase - discount).toFixed(4));

        totalAmount   += lineBase;
        totalGeneral  += lineBase;
        totalDiscount += discount;
        totalISV1     += isv1_line;
        totalISV2     += isv2_line;
    });

    // Subtotales internos sin formato
    $('#subTotal').val(parseFloat(totalAmount).toFixed(2));
    $('#taxDescuento').val(parseFloat(totalDiscount).toFixed(2));
    $('#taxAmount').val(parseFloat(totalISV1).toFixed(2));

    if ($('#taxAmount18').length) {
        $('#taxAmount18').val(parseFloat(totalISV2).toFixed(2));
    }

    // Formato visual para footer
    var fmt = function(n) {
        return parseFloat(n || 0)
            .toFixed(2)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    };

    $('#subTotalFooter').val(fmt(totalAmount));
    $('#taxDescuentoFooter').val(fmt(totalDiscount));
    $('#taxAmountFooter').val(fmt(totalISV1));

    if ($('#taxAmountFooter18').length) {
        $('#taxAmountFooter18').val(fmt(totalISV2));
    }

    // Total general = Subtotal + ISV id=1 + ISV id=2 - descuentos
    var total = (totalAmount + totalISV1 + totalISV2) - totalDiscount;

    $('#subTotalImporte').val(fmt(totalGeneral));
    $('#totalAftertax').val(fmt(total));
    $('#totalAftertaxFooter').val(fmt(total));

    // Conversión si aplica
    var totalAftertax = parseFloat(total) || 0;
    var cambioDolar = parseFloat($('#cambioBill').val()) || 1;

    $('#totalHNLBill').val(customRound(parseFloat(totalAftertax * cambioDolar).toFixed(2)));
}

function customRound(number) {
    var truncated = Math.floor(number * 100) / 100;
    var secondDecimal = Math.floor((number * 100) % 10);
    if (secondDecimal >= 5) {
        return parseFloat((truncated + 0.01).toFixed(2));
    } else {
        return parseFloat(truncated.toFixed(2));
    }
}

function cleanFooterValueBill() {
    $('#subTotalFooter').val("");
    $('#taxDescuentoFooter').val("");
    $('#taxAmountFooter').val("");
    if ($('#taxAmountFooter18').length) $('#taxAmountFooter18').val("");
    $('#totalAftertaxFooter').val("");
}
//FIN INVOICE BILL

/*INICIO BARCODE*/
//INICIO FACTURAS
function redondearEnteroCercano(numero) {
    var entero = Math.floor(numero); // Obtenemos la parte entera del número
    var decimal = numero - entero; // Obtenemos la parte decimal

    if (decimal < 0.5) {
        return entero; // Redondeamos hacia abajo si la parte decimal es menor que 0.5
    } else {
        return entero + 1; // Redondeamos hacia arriba si la parte decimal es mayor o igual a 0.5
    }
}

$(() => {
  $("#invoice-form #invoiceItem").on('keypress', '.product-bar-code', function (event) {
    const row_index = obtenerRowIndexFacturaDesdeElemento(this);
    const code = event.which || event.keyCode;

    if (code === 10 || code === 13) { // Enter
      event.preventDefault();
      manejarPresionEnter(row_index); // ya NO pasamos event dentro
      return;
    }
    if (code === 43 || code === 45) { // + / -
      event.preventDefault();
      manejarPresionTeclaMasMenos(code, row_index);
    }
  });
});

async function manejarPresionEnter(row_index) {
  const barCodeInput = $("#invoice-form #invoiceItem #bar-code-id_" + row_index);
  let barcode = barCodeInput.val().trim();
  if (barcode === "") return;

  const url = '<?php echo SERVERURL; ?>core/getProductoBarCode.php';

  // Permitir "cantidad*barcode"
  let cantidad = 1;
  let barcodeValue = barcode;
  if (barcode.includes('*')) {
    const parts = barcode.split('*');
    cantidad = parseFloat(parts[0]) || 1;
    barcodeValue = parts[1] || '';
  }

  $.ajax({
    type: 'POST',
    url: url,
    data: 'barcode=' + encodeURIComponent(barcodeValue),
    async: true,
    success: async function (registro) {
      getTotalFacturasDisponibles();

      let producto = {};
      try { producto = JSON.parse(registro); } catch (e) { producto = {}; }

      // Producto válido
      if (!producto || !producto.nombre) {
        showNotify('error', 'Error', 'Producto no encontrado, por favor corregir');
        barCodeInput.val("");
        return;
      }

      // Si no es servicio: validar bodega/saldo
      if (String(producto.tipo_producto_id) !== "2") {
        if (producto.almacen_id === null || producto.almacen_id === "") {
          swal({
            title: "Error",
            content: {
              element: "span",
              attributes: {
                innerHTML:
                  "Lo sentimos, el producto no está asignado a una bodega. Por favor, " +
                  "<a href='<?php echo SERVERURL; ?>inventario/' " +
                  "style='color: blue; text-decoration: none;' " +
                  "onmouseover='this.style.color=`purple`' onmouseout='this.style.color=`blue`' " +
                  "onmousedown='this.style.color=`purple`' target='_blank'>ingrese el movimiento</a> " +
                  "de este registro antes de continuar."
              }
            },
            icon: "error",
            buttons: { confirm: { text: "Aceptar" } },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
          });
          return;
        }

        const facturar_cero = await facturarEnCeroAlmacen(producto.almacen_id);
        if ((parseFloat(producto.saldo || 0) <= 0) && (facturar_cero == 'false' || facturar_cero === false)) {
          showNotify('error', 'Error', 'No se puede facturar este producto inventario en cero');
          return;
        }
      }

      // Cantidad si venía en "cantidad*barcode"
      $("#invoice-form #invoiceItem #quantity_" + row_index).val(cantidad);
      $("#invoice-form #invoiceItem #bar-code-id_" + row_index).val(barcodeValue);

      // Setear datos base
      $("#invoice-form #invoiceItem #productName_" + row_index).val(producto.nombre);
      $("#invoice-form #invoiceItem #productName_text_" + row_index).html(producto.nombre);

      $("#invoice-form #invoiceItem #price_" + row_index).val(producto.precio_venta);
      $("#invoice-form #invoiceItem #precio_real_" + row_index).val(producto.precio_venta);
      $("#invoice-form #invoiceItem #productos_id_" + row_index).val(producto.productos_id);

      // ISV: grava 1/0
      $("#invoice-form #invoiceItem #isv_" + row_index).val(producto.impuesto_venta);

      // FLAGS por línea (cuál ISV aplica)
      var productoGravaISV = parseInt(producto.impuesto_venta || producto.isv_venta || 0, 10) === 1;
      var productoFlagISV1 = parseInt(producto.isv1 || 0, 10) === 1 ? 1 : 0;
      var productoFlagISV2 = parseInt(producto.isv2 || 0, 10) === 1 ? 1 : 0;

      // Si grava, pero no trae isv1/isv2, usar ISV id=1 por defecto.
      if (productoGravaISV && productoFlagISV1 === 0 && productoFlagISV2 === 0) {
        productoFlagISV1 = 1;
      }

      if ($('#isv1_flag_' + row_index).length) $('#isv1_flag_' + row_index).val(productoFlagISV1);
      if ($('#isv2_flag_' + row_index).length) $('#isv2_flag_' + row_index).val(productoFlagISV2);

      // % por línea (estos son PORCENTAJES que trae el PHP; ej 15.00 / 18.00)
      // Debes tener inputs hidden por fila: porc_isv_# y porc_isv1_#
      if ($('#porc_isv_' + row_index).length)  $('#porc_isv_' + row_index).val(Number(producto.valor_isv  || 0).toFixed(2));
      if ($('#porc_isv1_' + row_index).length) $('#porc_isv1_' + row_index).val(Number(producto.valor_isv1 || 0).toFixed(2));

      // Montos de ISV (monto $). Los reiniciamos; recalc los calculará.
      if ($('#valor_isv_' + row_index).length)  $('#valor_isv_' + row_index).val('0.00');
      if ($('#valor_isv1_' + row_index).length) $('#valor_isv1_' + row_index).val('0.00');

      // Mayoreo (si la cantidad del código supera el umbral)
      (function actualizarPrecioPorMayoreo() {
        const cm = parseFloat(producto.cantidad_mayoreo || 0);
        const pm = parseFloat(producto.precio_mayoreo || 0);
        const q  = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 0;
        const pr = parseFloat($('#invoice-form #invoiceItem #precio_real_' + row_index).val()) || 0;
        const nuevo = (cm > 0 && pm > 0 && q >= cm) ? pm : pr;
        $('#invoice-form #invoiceItem #price_' + row_index).val(nuevo);
      })();

      // Otros campos
      $("#invoice-form #invoiceItem #cantidad_mayoreo_" + row_index).val(producto.cantidad_mayoreo);
      $("#invoice-form #invoiceItem #precio_mayoreo_"   + row_index).val(producto.precio_mayoreo);
      $('#invoice-form #invoiceItem #bodega_'           + row_index).val(producto.almacen_id);
      $('#invoice-form #invoiceItem #medida_'           + row_index).val(producto.medida || 'Und');

      // Calcula montos ISV por línea con lo que acabamos de setear
      await recalcISVForRow(row_index);

      // UI
      asegurarFilaVaciaFinalFactura();
      const icon_search = (row_index > 0) ? (row_index - 1) : 0;
      $("#invoice-form #invoiceItem #icon-search-bar_" + row_index).hide();
      $("#invoice-form #invoiceItem #icon-search-bar_" + icon_search).hide();

      calculateTotalFacturas();
    }
  });
}

function manejarPresionTeclaMasMenos(codigoTecla, row_index) {
  // 1) Actualizar cantidad
  var $cant = $("#invoice-form #invoiceItem #quantity_" + row_index);
  var cant  = parseFloat($cant.val()) || 1;
  cant = (codigoTecla === 43) ? (cant + 1) : Math.max(cant - 1, 1);
  $cant.val(cant);

  // 2) Actualizar precio por regla de mayoreo (usa tu función existente)
  actualizarPrecioPorMayoreo(row_index);

  // 3) Recalcular ISV de la línea y totales
  (async () => {
    await recalcularISVLinea(row_index); // alias que llama a recalcISVForRow(row_index)
    calculateTotalFacturas();
  })();
}

$(() => {
    $('#view_bill').on("keydown", function(e) {
        if (e.which === 118) { //TECLA F7 (COBRAR)
            ProcesarFactura();
            e.preventDefault();
        }

        if (e.which === 119) { //TECLA F8 (CLIENTES)
            searchCustomersBill();
            e.preventDefault();
        }

        if (e.which === 120) { //TECLA F9 (Colaboradores)
            serchColaboradoresBill();
            e.preventDefault();
        }

        if (e.which === 121) { //TECLA F10 (APERTURAR CAJA)
            e.preventDefault();
            if (getConsultarAperturaCaja() == 2) {
                formAperturaBill();
            } else {
                showNotify('warning', 'Caja abierta', 'La caja se encuentra abierta');
            }
        }

        if (e.which === 122) { //TECLA F11 (CERRAR CAJA)			
            e.preventDefault();
            if (getConsultarAperturaCaja() != 2) {
                formCierreBill()
            } else {
                showNotify('warning', 'Caja cerrada', 'La caja se encuentra cerrada');
            }
        }
    });
});

//INICIO ADD TASA DE CAMBIO
$(() => {
    $("#modalTasaCambio").on('shown.bs.modal', function() {
        $(this).find('#formTasaCambio #tasa_compra').focus();
    });
});

$("#invoice-form #addCambio").on("click", function(e) {
    e.preventDefault();
    $('#modalTasaCambio #pro_tasaCambio').val("Registro");


    $('#formTasaCambio').attr({
        'data-form': 'save'
    });
    $('#formTasaCambio').attr({
        'action': '<?php echo SERVERURL; ?>ajax/addTasaCambioAjax.php'
    });

    $('#modalTasaCambio').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

$("#formTasaCambio").on("submit", function(e) {
    var compra = $('#formTasaCambio #tasa_compra').val();
    var totalUSD = $('#invoice-form #totalAftertax').val();
    var totalHND = 0;

    if (compra > 0) {
        totalHND = totalUSD * compra;
    }

    $('#invoice-form #totalHNLBill').val(parseFloat(totalHND).toFixed(2));

    e.preventDefault();
});
//FIN ADD TASA DE CAMBIO

function modalAyuda() {
    $('#modalAyuda').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

// Abrir modal con F1 en cualquier momento
$(document).on('keydown', function (e) {
    if (e.key === 'F1') {
    e.preventDefault(); // Evita la ayuda del navegador
    $('#modalAyuda').modal('show');
    }
});

//INICIO AYUDA
$("#invoice-form #help_factura").on("click", function(e) {
    modalAyuda();
    e.preventDefault();
});

// ===== DESCUENTO PRODUCTO EN FACTURACION: bloque unificado más abajo =====


// ===== INICIO CAMBIAR PRECIO A PRODUCTO EN FACTURACION =====
$(() => {
  $('#invoice-form #invoiceItem').on("keydown", '.product-bar-code', function(e) {
    if (e.which === 112) { // F1
      modalAyuda();
      e.preventDefault();
    }

    if (e.which === 113) { // F2
      alert('Me haz presionado');
      e.preventDefault();
    }

    if (e.which === 114) { // F3 -> buscar producto
      listar_productos_factura_buscar();
      var row_index = obtenerRowIndexFacturaDesdeElemento(this);
      var col_index = $(this).closest("td").index();

      $('#formulario_busqueda_productos_facturacion #row').val(row_index);
      $('#formulario_busqueda_productos_facturacion #col').val(col_index);
      $('#modal_buscar_productos_facturacion').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
      });
      e.preventDefault();
    }

    if (e.which === 115) { // F4 -> descuento por producto
      var row_index = obtenerRowIndexFacturaDesdeElemento(this);
      var col_index = $(this).closest("td").index();

      $('#formDescuentoFacturacion #row_index').val(row_index);
      $('#formDescuentoFacturacion #col_index').val(col_index);

      if ($("#invoice-form #invoiceItem #productos_id_" + row_index).val() != "") {
        $('#formDescuentoFacturacion')[0].reset();
        var productos_id = $("#invoice-form #invoiceItem #productos_id_" + row_index).val();
        var producto     = $("#invoice-form #invoiceItem #productName_" + row_index).val();
        var precio       = $("#invoice-form #invoiceItem #price_" + row_index).val();

        $('#formDescuentoFacturacion #descuento_productos_id').val(productos_id);
        $('#formDescuentoFacturacion #producto_descuento_fact').val(producto);
        $('#formDescuentoFacturacion #precio_descuento_fact').val(precio);

        $('#formDescuentoFacturacion #pro_descuento_fact').val("Registrar");

        getPermisosTipoUsuarioAccesosForms(getPrivilegioTipoUsuario());

        $('#modalDescuentoFacturacion').modal({
          show: true,
          keyboard: false,
          backdrop: 'static'
        });
      }
      e.preventDefault();
    }

    if (e.which === 117) { // F6 -> modificar precio
      var row_index = obtenerRowIndexFacturaDesdeElemento(this);
      abrirEditarPrecioFacturaEscritorio(row_index);
      e.preventDefault();
    }
  });
});

// ======================================================
// EDITAR PRECIO - MODAL NUEVO COMPARTIDO CON FACTURA MÓVIL
// Modal requerido: #editarPrecioModal
// Campos requeridos:
//   #producto-precio-index
//   #nuevo-precio-producto
//   #precio-total-preview o los spans:
//   #preview-subtotal, #preview-isv15, #preview-isv18, #preview-total
//   #guardar-precio
// ======================================================
function asegurarPreviewPrecioFacturaEscritorio() {
  var $preview = $('#precio-total-preview');

  if ($preview.length && $preview.is('input')) {
    $preview.replaceWith(
      '<div id="precio-total-preview" class="alert alert-light border mb-0" style="line-height:1.6;">' +
        '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
        '<div class="d-flex justify-content-between"><span>ISV 15%</span><strong id="preview-isv15">L. 0.00</strong></div>' +
        '<div class="d-flex justify-content-between"><span>ISV 18%</span><strong id="preview-isv18">L. 0.00</strong></div>' +
        '<hr class="my-2">' +
        '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>' +
      '</div>'
    );
  } else if (!$preview.length) {
    return;
  } else if (!$('#preview-subtotal').length) {
    $preview.html(
      '<div class="d-flex justify-content-between"><span>Subtotal</span><strong id="preview-subtotal">L. 0.00</strong></div>' +
      '<div class="d-flex justify-content-between"><span>ISV 15%</span><strong id="preview-isv15">L. 0.00</strong></div>' +
      '<div class="d-flex justify-content-between"><span>ISV 18%</span><strong id="preview-isv18">L. 0.00</strong></div>' +
      '<hr class="my-2">' +
      '<div class="d-flex justify-content-between font-weight-bold"><span>Total</span><strong id="preview-total">L. 0.00</strong></div>'
    );
  }
}

function formatMoneyPreviewFacturaEscritorio(value) {
  value = parseFloat(value || 0);
  return value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function setPrecioPreviewFacturaEscritorio(subtotal, isv15, isv18, total) {
  asegurarPreviewPrecioFacturaEscritorio();

  subtotal = parseFloat(subtotal || 0);
  isv15 = parseFloat(isv15 || 0);
  isv18 = parseFloat(isv18 || 0);
  total = parseFloat(total || 0);

  if ($('#preview-subtotal').length) $('#preview-subtotal').text('L. ' + formatMoneyPreviewFacturaEscritorio(subtotal));
  if ($('#preview-isv15').length) $('#preview-isv15').text('L. ' + formatMoneyPreviewFacturaEscritorio(isv15));
  if ($('#preview-isv18').length) $('#preview-isv18').text('L. ' + formatMoneyPreviewFacturaEscritorio(isv18));
  if ($('#preview-total').length) $('#preview-total').text('L. ' + formatMoneyPreviewFacturaEscritorio(total));

  // Compatibilidad con el modal viejo donde la vista previa era un input.
  if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
    $('#precio-total-preview').val(
      'Subtotal: L. ' + formatMoneyPreviewFacturaEscritorio(subtotal) +
      ' | ISV 15%: L. ' + formatMoneyPreviewFacturaEscritorio(isv15) +
      ' | ISV 18%: L. ' + formatMoneyPreviewFacturaEscritorio(isv18) +
      ' | Total: L. ' + formatMoneyPreviewFacturaEscritorio(total)
    );
  }
}

function limpiarPrecioPreviewFacturaEscritorio() {
  asegurarPreviewPrecioFacturaEscritorio();

  if ($('#preview-subtotal').length) $('#preview-subtotal').text('L. 0.00');
  if ($('#preview-isv15').length) $('#preview-isv15').text('L. 0.00');
  if ($('#preview-isv18').length) $('#preview-isv18').text('L. 0.00');
  if ($('#preview-total').length) $('#preview-total').text('L. 0.00');

  if ($('#precio-total-preview').length && $('#precio-total-preview').is('input')) {
    $('#precio-total-preview').val('');
  }
}

function obtenerPorcentajeISVFacturaEscritorio(isvId) {
  var porcentaje = 0;

  if (typeof fetchISVPercentSync === 'function') {
    porcentaje = parseFloat(fetchISVPercentSync(isvId)) || 0;
  }

  if (porcentaje <= 0 && typeof getPorcentajeISV === 'function' && isvId === 1) {
    porcentaje = parseFloat(getPorcentajeISV('Facturas')) || 0;
  }

  // Fallback seguro para no romper vista previa si la consulta global no existe.
  if (porcentaje <= 0) {
    if (parseInt(isvId, 10) === 1) porcentaje = 15;
    if (parseInt(isvId, 10) === 2) porcentaje = 18;
  }

  return porcentaje;
}

function abrirEditarPrecioFacturaEscritorio(row_index) {
  // Si no se pasó row_index, intentar obtener la fila activa
  if (row_index === undefined || row_index === null) {
    // Buscar la fila que tiene el foco o la última fila con producto
    var $focusedRow = $('#invoice-form #invoiceItem tbody tr:has(input:focus)');
    if ($focusedRow.length) {
      row_index = $focusedRow.index();
    } else {
      // Buscar la última fila que tiene un producto cargado
      var totalFilas = parseInt($('#bill_row').val(), 10) || 0;
      for (var i = totalFilas; i >= 0; i--) {
        var pid = $('#productos_id_' + i).val();
        if (pid && pid !== '' && pid !== '0') {
          row_index = i;
          break;
        }
      }
    }
  }
  
  row_index = parseInt(row_index, 10);
  
  if (isNaN(row_index) || row_index < 0) {
    showNotify('error', 'Error', 'No se pudo identificar la línea del producto');
    return;
  }

  var productos_id = $('#invoice-form #invoiceItem #productos_id_' + row_index).val();
  var producto = $('#invoice-form #invoiceItem #productName_' + row_index).val();
  var precioActual = parseFloat($('#invoice-form #invoiceItem #price_' + row_index).val()) || 0;

  if (!productos_id || productos_id === '' || productos_id === '0') {
    var barcode = $('#bar-code-id_' + row_index).val();
    if (barcode && barcode !== '') {
      showNotify('warning', 'Atención', 'El producto se agregó por código, pero falta el ID. Por favor, selecciónelo nuevamente de la lista.');
    } else {
      showNotify('error', 'Error', 'Debe seleccionar un producto antes de modificar el precio');
    }
    return;
  }

  $('#producto-precio-index').val(row_index);
  $('#nuevo-precio-producto').val(precioActual > 0 ? precioActual.toFixed(2) : '');

  if ($('#editarPrecioModalLabel').length) {
    $('#editarPrecioModalLabel').html('<i class="fas fa-dollar-sign"></i> Editar Precio');
  }

  actualizarVistaNuevoPrecioFacturaEscritorio();

  $('#editarPrecioModal').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function actualizarVistaNuevoPrecioFacturaEscritorio() {
  var row_index = parseInt($('#producto-precio-index').val(), 10);
  var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

  if (Number.isNaN(row_index) || row_index < 0 || nuevoPrecio <= 0) {
    limpiarPrecioPreviewFacturaEscritorio();
    return;
  }

  var cantidad = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 1;
  var descuento = parseFloat($('#invoice-form #invoiceItem #discount_' + row_index).val()) || 0;
  var grava = parseInt($('#invoice-form #invoiceItem #isv_' + row_index).val() || '0', 10);
  var flag1 = parseInt($('#isv1_flag_' + row_index).val() || '0', 10) === 1;
  var flag2 = parseInt($('#isv2_flag_' + row_index).val() || '0', 10) === 1;

  var subtotal = (nuevoPrecio * cantidad) - descuento;
  if (subtotal < 0) subtotal = 0;

  var isv15 = 0;
  var isv18 = 0;

  if (grava === 1 && flag1) {
    var p1 = obtenerPorcentajeISVFacturaEscritorio(1);
    isv15 = subtotal * (p1 / 100);
  }

  if (grava === 1 && flag2) {
    var p2 = obtenerPorcentajeISVFacturaEscritorio(2);
    isv18 = subtotal * (p2 / 100);
  }

  var total = subtotal + isv15 + isv18;

  setPrecioPreviewFacturaEscritorio(subtotal, isv15, isv18, total);
}

function guardarPrecioFacturaEscritorio() {
  var row_index = parseInt($('#producto-precio-index').val(), 10);
  var nuevoPrecio = parseFloat($('#nuevo-precio-producto').val()) || 0;

  if (Number.isNaN(row_index) || row_index < 0) {
    showNotify('error', 'Error', 'No se pudo identificar la línea del producto');
    return;
  }

  if (nuevoPrecio <= 0) {
    showNotify('warning', 'Advertencia', 'El precio debe ser mayor a cero');
    $('#nuevo-precio-producto').focus();
    return;
  }

  var cantidad = parseFloat($('#invoice-form #invoiceItem #quantity_' + row_index).val()) || 1;
  var descuentoActual = parseFloat($('#invoice-form #invoiceItem #discount_' + row_index).val()) || 0;
  var totalLinea = nuevoPrecio * cantidad;

  if (descuentoActual > totalLinea) {
    showNotify('warning', 'Advertencia', 'El descuento actual es mayor al nuevo precio total. Ajuste primero el descuento.');
    return;
  }

  $('#invoice-form #invoiceItem #price_' + row_index).val(nuevoPrecio.toFixed(2));
  $('#invoice-form #invoiceItem #precio_real_' + row_index).val(nuevoPrecio.toFixed(2));

  (async () => {
    await recalcularISVLinea(row_index);
    calculateTotalFacturas();
    $('#editarPrecioModal').modal('hide');
    showNotify('success', 'Éxito', 'Precio actualizado correctamente');
  })();
}

$(document).off('input.editarPrecioFacturaEscritorio keyup.editarPrecioFacturaEscritorio change.editarPrecioFacturaEscritorio', '#nuevo-precio-producto');
$(document).on('input.editarPrecioFacturaEscritorio keyup.editarPrecioFacturaEscritorio change.editarPrecioFacturaEscritorio', '#nuevo-precio-producto', function () {
  actualizarVistaNuevoPrecioFacturaEscritorio();
});

$(document).off('click.guardarPrecioFacturaEscritorio', '#guardar-precio');
$(document).on('click.guardarPrecioFacturaEscritorio', '#guardar-precio', function (e) {
  e.preventDefault();
  guardarPrecioFacturaEscritorio();
});

$(document).off('keydown.guardarPrecioFacturaEscritorio', '#editarPrecioModal #nuevo-precio-producto');
$(document).on('keydown.guardarPrecioFacturaEscritorio', '#editarPrecioModal #nuevo-precio-producto', function (e) {
  if (e.which === 13) {
    e.preventDefault();
    guardarPrecioFacturaEscritorio();
  }
});

$('#editarPrecioModal').off('shown.bs.modal.editarPrecioFacturaEscritorio');
$('#editarPrecioModal').on('shown.bs.modal.editarPrecioFacturaEscritorio', function () {
  $('#nuevo-precio-producto').trigger('focus').select();
});

$('#editarPrecioModal').off('hidden.bs.modal.editarPrecioFacturaEscritorio');
$('#editarPrecioModal').on('hidden.bs.modal.editarPrecioFacturaEscritorio', function () {
  if ($('#editar-precio-form').length && $('#editar-precio-form')[0]) {
    $('#editar-precio-form')[0].reset();
  }

  limpiarPrecioPreviewFacturaEscritorio();
  $('#producto-precio-index').val('');
});
// ===== FIN CAMBIAR PRECIO A PRODUCTO EN FACTURACION =====
//FIN FACTURAS

function facturarEnCeroAlmacen(almacen_id) {
    var url = '<?php echo SERVERURL; ?>core/getFacturarCeroAlmacen.php';
    return $.ajax({
        type: 'POST',
        url: url,
        data: {almacen_id: almacen_id},
        dataType: 'json'
    }).then(function(response) {
        return !!(response && response.success && response.facturar_cero);
    }, function() {
        return false;
    });
}

// ======================================================
// INICIO ESTADOS PROFORMA + REBAJAR INVENTARIO PROFORMA
// ======================================================
//
// IMPORTANTE:
// En tu HTML el checkbox de inventario es:
// id="proforma_bajar_inventario"
// name="proforma_bajar_inventario"
//
// Este bloque consulta:
// core/facturas/facturaMovil.php?getConfigFacturaMovil=1
//
// Respuesta esperada:
// {
//   success: true,
//   proforma_activa: 1,
//   proforma_rebajar_inventario: 1
// }
// ======================================================

function normalizarActivoFactura(valor) {
    return valor === true || valor === 1 || valor === '1';
}

function getCheckBajarInventarioProforma() {
    // Principal: este es el ID que tienes en tu HTML actual.
    var $check = $('#invoice-form #proforma_bajar_inventario');

    // Compatibilidad por si en otra pantalla lo tienes con el nombre anterior.
    if (!$check.length) {
        $check = $('#invoice-form #bajar_inventario_proforma');
    }

    return $check;
}

function actualizarUIProformaFacturaNormal(activo, rebajarInventarioDefault) {
    activo = normalizarActivoFactura(activo);
    rebajarInventarioDefault = normalizarActivoFactura(rebajarInventarioDefault);

    var $proforma = $('#invoice-form #facturas_proforma');
    var $labelProforma = $('#invoice-form #label_facturas_proforma');

    if (!$proforma.length) {
                return;
    }

    // Forzado real del checkbox Proforma
    $proforma.prop('checked', activo);

    if ($proforma[0]) {
        $proforma[0].checked = activo;
    }

    $proforma.val(activo ? 1 : 0);

    if (activo) {
        $proforma.attr('checked', 'checked');
    } else {
        $proforma.removeAttr('checked');
    }

    // Label visual Proforma
    $labelProforma
        .removeClass('badge-light badge-secondary badge-info text-white')
        .addClass(activo ? 'badge-info text-white' : 'badge-light')
        .html(activo ? 'Sí' : 'No');

    // Mostrar/Ocultar y aplicar default de rebajar inventario
    mostrarOpcionesInventarioProforma(activo, rebajarInventarioDefault);

    }

function mostrarOpcionesInventarioProforma(mostrar, rebajarInventarioDefault) {
    mostrar = normalizarActivoFactura(mostrar);
    rebajarInventarioDefault = normalizarActivoFactura(rebajarInventarioDefault);

    var $container = $('#invoice-form #proforma_rebajar_inventario_container');
    var $check = getCheckBajarInventarioProforma();
    var $label = $('#invoice-form #label_bajar_inventario_proforma');

    if (!$container.length) {
                return;
    }

    if (mostrar) {
        $container.attr('style', 'display:flex !important;');

        if ($check.length) {
            $check.prop('checked', rebajarInventarioDefault);

            if ($check[0]) {
                $check[0].checked = rebajarInventarioDefault;
            }

            $check.val(rebajarInventarioDefault ? 1 : 0);

            if (rebajarInventarioDefault) {
                $check.attr('checked', 'checked');
            } else {
                $check.removeAttr('checked');
            }
        }

        $label
            .removeClass('badge-success badge-light text-white')
            .addClass(rebajarInventarioDefault ? 'badge-success text-white' : 'badge-light')
            .html(rebajarInventarioDefault ? 'Sí' : 'No');

    } else {
        $container.attr('style', 'display:none !important;');

        if ($check.length) {
            $check.prop('checked', false);

            if ($check[0]) {
                $check[0].checked = false;
            }

            $check.removeAttr('checked');
            $check.val(0);
        }

        $label
            .removeClass('badge-success text-white')
            .addClass('badge-light')
            .html('No');
    }
}

function actualizarLabelBajarInventarioProforma() {
    var $check = getCheckBajarInventarioProforma();
    var activo = $check.is(':checked');

    $check.val(activo ? 1 : 0);

    $('#invoice-form #label_bajar_inventario_proforma')
        .removeClass('badge-success badge-light text-white')
        .addClass(activo ? 'badge-success text-white' : 'badge-light')
        .html(activo ? 'Sí' : 'No');
}

function consultarConfigProformaFacturaNormal() {
    var url = '<?php echo SERVERURL; ?>core/facturas/facturaMovil.php?getConfigFacturaMovil=1';

    $.ajax({
        type: 'GET',
        url: url,
        dataType: 'json',
        cache: false
    }).done(function (res) {
        
        var proformaActiva = 0;
        var rebajarInventarioProforma = 0;

        if (res && res.success === true) {
            proformaActiva = parseInt(res.proforma_activa || 0, 10);
            rebajarInventarioProforma = parseInt(res.proforma_rebajar_inventario || 0, 10);
        }

        actualizarUIProformaFacturaNormal(
            proformaActiva === 1,
            rebajarInventarioProforma === 1
        );

        if (typeof recalcularTodasLineasISVFactura === 'function') {
            recalcularTodasLineasISVFactura(true);
        }

    }).fail(function (xhr) {
        
        // Si falla la consulta, por seguridad queda apagado visualmente.
        actualizarUIProformaFacturaNormal(false, false);
    });
}

$(function () {
    // Estado inicial visual
    actualizarUIProformaFacturaNormal(false, false);

    // Consultar configuración real
    consultarConfigProformaFacturaNormal();

    // Cambio manual de Proforma
    $(document).off('change.facturaProforma', '#invoice-form #facturas_proforma');
    $(document).on('change.facturaProforma', '#invoice-form #facturas_proforma', function () {
        var activo = $(this).is(':checked');

        // Cuando el usuario lo activa manualmente, respetamos cómo esté el check de inventario.
        var rebajarActual = getCheckBajarInventarioProforma().is(':checked');

        actualizarUIProformaFacturaNormal(activo, rebajarActual);

        if (typeof recalcularTodasLineasISVFactura === 'function') {
            recalcularTodasLineasISVFactura(true);
        }
    });

    // Cambio manual de rebajar inventario
    $(document).off('change.bajarInventarioProforma', '#invoice-form #proforma_bajar_inventario, #invoice-form #bajar_inventario_proforma');
    $(document).on('change.bajarInventarioProforma', '#invoice-form #proforma_bajar_inventario, #invoice-form #bajar_inventario_proforma', function () {
        actualizarLabelBajarInventarioProforma();
    });
});

// ======================================================
// FIN ESTADOS PROFORMA + REBAJAR INVENTARIO PROFORMA
// ======================================================

$(() => {
    $("#modalDescuentoFacturacion").on('shown.bs.modal', function() {
        $(this).find('#formDescuentoFacturacion #porcentaje_descuento_fact').focus();
    });

    $("#modal_buscar_productos_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_productos_facturacion #buscar').focus();
    });

    $("#modal_buscar_colaboradores_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_colaboradores_facturacion #buscar').focus();
    });

    $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
    });
});

$('#invoice-form #notesBill').keyup(function() {
    var max_chars = 2000;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#invoice-form #charNum_notasBills').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresNotasBills() {
    var max_chars = 2000;
    var chars = $('#invoice-form #notesBill').val().length;
    var diff = max_chars - chars;

    $('#invoice-form #charNum_notasBills').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

$("#invoice-form #cambiar_valor").on("click", function(e) {
    e.preventDefault();
    $('#invoice-form #cambioBillValor').val(1);
    $('#invoice-form #cambioBill').attr("readonly", false);
    $('#invoice-form #cambioBill').focus();
});

$("#invoice-form #cambioBill").on('keypress', function(event) {
    calculateTotalFacturas();
});

$("#invoice-form #cambioBill").on('keyup', function(event) {
    calculateTotalFacturas();
});

$("#invoice-form #cambioBill").on('blur', function(event) {
    calculateTotalFacturas();
});

//INICIO CONVERTIR COTIZACION EN FACTURAS
$(() => {
    $("#modal_buscar_cotizaciones").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_cotizaciones #buscar').focus();
    });
});

$("#invoice-form #addQuotetoBill").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cotizaciones();

    $('#modal_buscar_cotizaciones').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

// Lista de cotizaciones
var listar_busqueda_cotizaciones = function () {
  var $form = $("#formulario_busqueda_cotizaciones");
  var tipo_cotizacion_reporte = $form.find("#tipo_cotizacion_reporte").val() || 1;
  var fechai = $form.find("#fechai").val();
  var fechaf = $form.find("#fechaf").val();

  var table_busqueda_Cotizaciones = $("#DatatableBusquedaCotizaciones").DataTable({
    destroy: true,
    processing: true,        // <-- muestra indicador y no bloquea la UI
    deferRender: true,       // <-- renderiza bajo demanda (rápido)
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL; ?>core/llenarDataTableReporteCotizaciones.php",
      data: { tipo_cotizacion_reporte: tipo_cotizacion_reporte, fechai: fechai, fechaf: fechaf },
      cache: false
    },
    columns: [
      {
        defaultContent:
          "<button type='button' class='table_view load_quote btn btn-primary ocultar' title='Cargar en factura'>" +
          "<span class='fas fa-play fa-lg'></span></button>"
      },
      {
        defaultContent:
          "<button type='button' class='table_reportes print_cotizaciones btn btn-success ocultar' title='Imprimir'>" +
          "<span class='fas fa-file-download fa-lg'></span></button>"
      },
      { data: "fecha" },
      { data: "tipo_documento" },
      { data: "cliente" },
      { data: "numero" },
      {
        data: "subtotal",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "isv",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "descuento",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      },
      {
        data: "total",
        render: function (d, t) {
          var n = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(d);
          return t === 'display' ? '<span>'+n+'</span>' : n;
        }
      }
    ],
    // si te interesa “lo más reciente primero”
    order: [[5,'desc']], // 5 = columna "numero"
    lengthMenu: lengthMenu,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [{
      text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
      titleAttr: 'Actualizar Cotizaciones',
      className: 'table_actualizar btn btn-secondary ocultar',
      action: function () { listar_busqueda_cotizaciones(); }
    }],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
          typeof getPrivilegioTipoUsuario === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }
    }
  });

  table_busqueda_Cotizaciones.search('').draw();
  $('#buscar').focus();
};

// Cargar cotización en la factura (delegado)
$(document)
  .off('click', '#DatatableBusquedaCotizaciones button.load_quote')
  .on('click', '#DatatableBusquedaCotizaciones button.load_quote', function (e) {
    e.preventDefault();

    const $btn = $(this);
    const originalHtml = $btn.html();
    $btn.prop('disabled', true).html("<span class='spinner-border spinner-border-sm'></span>");

    const table = $('#DatatableBusquedaCotizaciones').DataTable();
    const row = table.row($(this).closest('tr')).data();
    if (!row) { 
      $btn.prop('disabled', false).html(originalHtml); 
      return; 
    }

    $.ajax({
      type: 'POST',
      url: "<?php echo SERVERURL; ?>core/cotizacion/getCotizacionParaFactura.php",
      data: { cotizacion_id: row.cotizacion_id },
      dataType: 'json'
    })
    .done(function (res) {
      if (!res || res.ok !== true) {
        showNotify('error', 'Error', (res && res.msg) ? res.msg : 'No se pudo cargar la cotización');
        return;
      }

      const h = res.header || {};
      const d = Array.isArray(res.detalle) ? res.detalle : [];
      const today = new Date().toISOString().slice(0,10);

      // Oculta el cuerpo de la tabla de factura para evitar reflow continuo
      const $tbody = $("#invoiceItem tbody");
      $tbody.hide();

      // Limpia la tabla y deja 1 fila base
      if (typeof limpiarTablaFacturaDetalles === 'function') limpiarTablaFacturaDetalles(0);

      /* ===== Header (campos ocultos y visibles del formulario) ===== */
      // Es nueva factura, no conservar el id anterior
      $("#invoice-form #facturas_id").val('');

      // Campos ocultos de ids/nombres
      $("#cliente_id").val(h.clientes_id || '');
      $("#cliente").val(h.cliente_nombre || '');
      $("#colaborador_id").val(h.colaboradores_id || '');
      $("#colaborador").val(h.colaborador_nombre || '');
      $("#notesBill").val(h.notas || '');

      // Fechas
      $("#fecha").val(h.fecha || today);
      $("#fecha_dolar").val(h.fecha_dolar || today);

      // Tipo de factura (1=Contado, 2=Crédito)
      if (typeof setTipoFactura === 'function') {
        setTipoFactura(h.tipo_factura == 1 ? 'contado' : 'credito');
      } else {
        $("#facturas_activo").val(h.tipo_factura == 1 ? 1 : 0); // compatibilidad backend
        // (Opcional) si usas botones toggle manuales, pon/quita .active aquí
        try {
          const isContado = (h.tipo_factura == 1);
          $("#btn-tipo-contado").toggleClass('active', isContado)
                                .toggleClass('btn-primary', isContado)
                                .toggleClass('btn-outline-primary', !isContado);
          $("#btn-tipo-credito").toggleClass('active', !isContado)
                                .toggleClass('btn-primary', !isContado)
                                .toggleClass('btn-outline-primary', isContado);
        } catch(_) {}
      }

      // Cabecera “bonita” arriba del formulario
      $("#rtn-customers-bill").text(h.cliente_rtn ? ("RTN: " + h.cliente_rtn) : "");
      $("#client-customers-bill").text(h.cliente_nombre ? ("Cliente: " + h.cliente_nombre) : "");
      $("#vendedor-customers-bill").text(h.colaborador_nombre ? ("Vendedor: " + h.colaborador_nombre) : "");

      /* ===== Detalle ===== */
      // Pre-crear filas necesarias de una sola vez
      if (typeof addRowFacturas === 'function' && d.length > 1) {
        for (let k = 1; k < d.length; k++) addRowFacturas();
      }

      // Rellenar filas
      for (let i = 0; i < d.length; i++) {
        $("#facturas_detalle_id_"+i).val(''); // viene de cotización, no reusar id de detalle
        $("#bar-code-id_"+i).val(d[i].barCode || "");
        $("#productos_id_"+i).val(d[i].productos_id || "");
        $("#productName_"+i).val(d[i].producto || "");
        $("#productName_text_"+i).text(d[i].producto || "Descripción del Producto");

        $("#quantity_"+i).val(d[i].cantidad || 0);
        $("#price_"+i).val(d[i].precio || 0);
        $("#precio_real_"+i).val(d[i].precio || 0);

        $("#isv_"+i).val(d[i].isv_venta || 0);
        $("#valor_isv_"+i).val(d[i].isv_valor || 0);

        $("#cantidad_mayoreo_"+i).val(d[i].cantidad_mayoreo || 0);
        $("#precio_mayoreo_"+i).val(d[i].precio_mayoreo || 0);

        $("#bodega_"+i).val(d[i].almacen_id || "");
        $("#medida_"+i).val(d[i].medida || "");
        $("#medida_text_"+i).text(d[i].medida || "Medida");

        $("#discount_"+i).val(d[i].descuento || 0);
      }

      // Totales (si prefieres usar los calculados en backend al instante)
      if (res.totales) {
        $("#subTotalImporte").val(res.totales.subtotal || 0);
        $("#taxDescuento").val(res.totales.descuento || 0);
        $("#taxAmount").val(res.totales.isv || 0);
        $("#totalAftertax").val(res.totales.total || 0);
      }

      // Recalcular por si tu lógica local maneja ISV/desc. automáticos
      if (typeof calculateTotalFacturas === 'function') calculateTotalFacturas();

      // Agregar una fila vacía extra y enfocar el código
      if (typeof addRowFacturas === 'function') {
        addRowFacturas();
        const next = parseInt($("#bill_row").val(), 10);
        if (!Number.isNaN(next)) $("#bar-code-id_"+next).focus();
      }

      // Mostrar todo de golpe (un solo reflow)
      $tbody.show();

      // Cerrar modal y avisar
      $('#modal_buscar_cotizaciones').modal('hide');
      showNotify('success', 'Cotización cargada', 'Se cargó la cotización en la factura');
    })
    .fail(function (xhr) {
            showNotify('error', 'Error', 'Falló la petición al servidor');
    })
    .always(function(){
      $btn.prop('disabled', false).html(originalHtml);
    });
  });

// Imprimir
$(document)
  .off('click', '#DatatableBusquedaCotizaciones button.print_cotizaciones')
  .on('click', '#DatatableBusquedaCotizaciones button.print_cotizaciones', function (e) {
    e.preventDefault();
    const table = $('#DatatableBusquedaCotizaciones').DataTable();
    const data = table.row($(this).closest('tr')).data();
    if (data && typeof printQuote === 'function') {
      printQuote(data.cotizacion_id);
    }
  });
//FIN CONVERTIR COTIZACION EN FACTURAS

//INICIO CUENTAS POR COBRAR CLIENTES
$(() => {
    $("#modal_buscar_cuentas_cobrar_clientes").on('shown.bs.modal', function() {
        $(this).find('#formulario_busqueda_cuentas_cobrar_clientes #buscar').focus();
    });
});

$("#invoice-form #addPayCustomers").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cuentas_por_cobrar_clientes();

    $('#modal_buscar_cuentas_cobrar_clientes').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

/* =========================================================
   CUENTAS POR COBRAR CLIENTES
   Header/Footer dinámico + Acciones en dropdown
   Números normales, sin tamaño exagerado
   ========================================================= */

   function parseMontoCXC(valor) {
    if (typeof valor === 'string') {
        valor = valor.replace(/L\./g, '').replace(/L/g, '').replace(/,/g, '').trim();
    }

    valor = parseFloat(valor || 0);
    return isNaN(valor) ? 0 : valor;
}

function formatoMonedaCXC(valor) {
    valor = parseMontoCXC(valor);

    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderMonedaColorCXC(data, type) {
    var valor = parseMontoCXC(data);
    var number = formatoMonedaCXC(valor);

    if (type === 'display') {
        var color = valor < 0 ? '#dc2626' : '#008000';

        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:500; white-space:nowrap;">' +
            number +
        '</span>';
    }

    return valor;
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - CXC CLIENTES
   ========================================================= */
function construirHeaderFooterDataTableCXCClientes() {
    var $tabla = $("#DatatableBusquedaCuentasCobrarClientes");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Cliente</th>' +
                '<th>Estado</th>' +
                '<th>Factura</th>' +
                '<th>Crédito</th>' +
                '<th>Abonos</th>' +
                '<th>Saldo</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right" style="font-size:0.95rem; font-weight:600;">Totales:</th>' +
                '<th id="credito-cxc" class="text-right" style="font-size:0.95rem; font-weight:500;">L. 0.00</th>' +
                '<th id="abono-cxc" class="text-right" style="font-size:0.95rem; font-weight:500;">L. 0.00</th>' +
                '<th id="total-footer-cxc" class="text-right" style="font-size:0.95rem; font-weight:500;">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTAR CUENTAS POR COBRAR CLIENTES
   ========================================================= */
var listar_busqueda_cuentas_por_cobrar_clientes = function() {
    var estado = $("#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado").val() === "" 
        ? 1 
        : $("#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado").val();

    var clientes_id = $("#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes").val();
    var fechai = $("#formulario_busqueda_cuentas_cobrar_clientes #fechai").val();
    var fechaf = $("#formulario_busqueda_cuentas_cobrar_clientes #fechaf").val();

    if ($.fn.DataTable.isDataTable("#DatatableBusquedaCuentasCobrarClientes")) {
        $("#DatatableBusquedaCuentasCobrarClientes").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableCXCClientes();

    var table_busqueda_cuentas_por_cobrar_clientes = $("#DatatableBusquedaCuentasCobrarClientes").DataTable({
        destroy: true,
        autoWidth: false,
        responsive: false,
        stateSave: true,
        bDestroy: true,
        pageLength: 10,
        processing: true,
        deferRender: true,
        searchDelay: 350,
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL; ?>core/llenarDataTableCobrarClientes.php",
            dataType: "json",
            data: {
                estado: estado,
                clientes_id: clientes_id,
                fechai: fechai,
                fechaf: fechaf
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    var accionesCXC = "";

                    accionesCXC +=
                        '<button type="button" class="dropdown-item accion-item accion-abonar table_abono">' +
                            '<span class="accion-icon accion-icon-success">' +
                                '<i class="fas fa-cash-register"></i>' +
                            '</span>' +
                            '<span class="accion-label">Registrar abono</span>' +
                        '</button>';

                    accionesCXC +=
                        '<button type="button" class="dropdown-item accion-item accion-abonos table_reportes abono_factura">' +
                            '<span class="accion-icon accion-icon-warning">' +
                                '<i class="fas fa-money-bill-wave"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver abonos</span>' +
                        '</button>';

                    accionesCXC +=
                        '<button type="button" class="dropdown-item accion-item accion-factura table_reportes print_factura">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="fas fa-file-download"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver factura</span>' +
                        '</button>';

                    return '' +
                        '<div class="acciones-caja-wrap">' +
                            '<div class="dropdown acciones-dropdown">' +
                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +
                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                    accionesCXC +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                data: "fecha",
                className: "text-nowrap"
            },
            {
                data: "cliente"
            },
            {
                data: "estado",
                className: "text-center",
                render: function(data, type, row) {
                    if (type === 'display') {
                        var text = data == 1 ? 'Crédito' : 'Contado';

                        var icon = data == 1
                            ? '<i class="fas fa-clock mr-1"></i>'
                            : '<i class="fas fa-check-circle mr-1"></i>';

                        var badgeClass = data == 1
                            ? 'badge badge-pill badge-warning'
                            : 'badge badge-pill badge-success';

                        return '<span class="' + badgeClass + '" style="font-size:0.85rem; padding:0.45em 0.7em; font-weight:500;">' +
                            icon + text +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                data: "numero",
                className: "text-center text-nowrap"
            },
            {
                data: "credito",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaColorCXC(data, type);
                }
            },
            {
                data: "abono",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaColorCXC(data, type);
                }
            },
            {
                data: "saldo",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaColorCXC(data, type);
                }
            }
        ],

        columnDefs: [
            {
                width: "10%",
                targets: 0
            },
            {
                width: "12%",
                targets: 1
            },
            {
                width: "24%",
                targets: 2
            },
            {
                width: "12%",
                targets: 3
            },
            {
                width: "12%",
                targets: 4
            },
            {
                width: "10%",
                targets: 5
            },
            {
                width: "10%",
                targets: 6
            },
            {
                width: "10%",
                targets: 7
            }
        ],

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Cuentas por Cobrar Clientes',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_busqueda_cuentas_por_cobrar_clientes();
                }
            },
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: "Excel",
                title: "Reporte Cuentas por Cobrar Clientes",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-success ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: "pdf",
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: "PDF",
                orientation: "landscape",
                title: "Reporte Cuentas por Cobrar Clientes",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-danger ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7]
                },
                customize: function(doc) {
                    if (typeof imagen !== "undefined" && imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],

        footerCallback: function() {
            var api = this.api();

            var totalCredito = api.column(5, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoCXC(a) + parseMontoCXC(b);
            }, 0);

            var totalAbono = api.column(6, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoCXC(a) + parseMontoCXC(b);
            }, 0);

            var totalSaldo = api.column(7, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoCXC(a) + parseMontoCXC(b);
            }, 0);

            $('#credito-cxc').html(
                '<span style="font-size:0.95rem; font-weight:500; white-space:nowrap;">' +
                    formatoMonedaCXC(totalCredito) +
                '</span>'
            );

            $('#abono-cxc').html(
                '<span style="font-size:0.95rem; font-weight:500; white-space:nowrap;">' +
                    formatoMonedaCXC(totalAbono) +
                '</span>'
            );

            $('#total-footer-cxc').html(
                '<span style="font-size:0.95rem; font-weight:500; white-space:nowrap;">' +
                    formatoMonedaCXC(totalSaldo) +
                '</span>'
            );
        },

        drawCallback: function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            $('[title]').tooltip({
                container: "body",
                placement: "top"
            });
        }
    });

    table_busqueda_cuentas_por_cobrar_clientes.search('').draw();

    $('#buscar').focus();

    registrar_abono_cxc_clientes_dataTable(
        "#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes
    );

    ver_abono_cxc_clientes_dataTable(
        "#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes
    );

    view_reporte_facturas_dataTable(
        "#DatatableBusquedaCuentasCobrarClientes tbody",
        table_busqueda_cuentas_por_cobrar_clientes
    );
};

var view_reporte_facturas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBillReporteVentas(data.facturas_id);
    });
}

var registrar_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.table_abono");

    $(tbody).on("click", "button.table_abono", function(e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (data.estado == 2 || data.saldo <= 0) {
            showNotify('error', 'Error', 'No puede realizar esta accion a las facturas canceladas!');
        } else {
            
            REFRESCAR_CXC_AL_CERRAR_PAGO = true;

            pago(data.facturas_id, 2, 'cxc');
        }
    });
};

var ver_abono_cxc_clientes_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_factura");
    $(tbody).on("click", "button.abono_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxc').modal('show');
        $("#formulario_ver_abono_cxc #abono_facturas_id").val(data.facturas_id);
        listar_AbonosCXC();
    });
}

var ver_abono_cxp_proveedor_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.abono_proveedor");
    $(tbody).on("click", "button.abono_proveedor", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        $('#ver_abono_cxc').modal('show');
        getAbonosCXP(data.compras_id);
    });
}
//FIN CUENTAS POR COBRAR CLIENTES

function getReporteCotizacion() {
    var url = '<?php echo SERVERURL; ?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').html("");
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').html(data);
            $('#formulario_busqueda_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh');
        }
    });
}

$("#formulario_busqueda_cotizaciones #search").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cotizaciones();
});

$("#formulario_busqueda_cuentas_cobrar_clientes #search").on("click", function(e) {
    e.preventDefault();
    listar_busqueda_cuentas_por_cobrar_clientes();
});

function convertirCotizacion(cotizacion_id) {
    var url = '<?php echo SERVERURL; ?>core/convertirCotizacion.php';

    $.ajax({
        type: 'POST',
        url: url,
        data: 'cotizacion_id=' + cotizacion_id,
        success: function(data) {
            var valores = eval(data);

            if (valores[0] == 1) {
                pago(valores[1]);
                listar_busqueda_cotizaciones();
            }
        }
    });
}

//BUSQUEDA DE FACTURAS EN BORRADOR
$("#addDraft").on("click", function(e) {
    e.preventDefault();

    listar_busqueda_bill_draf();

    $('#modal_buscar_bill_draft').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var listar_busqueda_bill_draf = function () {
  var fechai = $("#formulario_bill_draft #fechai").val();
  var fechaf = $("#formulario_bill_draft #fechaf").val();

  var table = $("#DatatableBusquedaBillDraft").DataTable({
    destroy: true,
    processing: true,        // <-- muestra indicador y no bloquea la UI
    deferRender: true,       // <-- renderiza bajo demanda (rápido)
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableFacturasBorrador.php",
      data: { fechai: fechai, fechaf: fechaf }
    },
    columns: [
      { defaultContent: "<button class='table_pay pay btn btn-primary'><span class='fas fa-play fa-lg'></span></button>" },
      { defaultContent: "<button class='table_eliminar eliminar btn btn-danger'><span class='fa fa-trash fa-lg'></span></button>" },
      { data: "fecha" },
      { data: "tipo_documento" },
      { data: "cliente" },
      { data: "numero" },
      {
        data: "subtotal",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "isv",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "descuento",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      },
      {
        data: "total",
        render: function (data, type) {
          var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
          if (type === 'display') return '<span>' + number + '</span>';
          return data;
        }
      }
    ],
    pageLength: 5,
    lengthMenu: lengthMenu,
    stateSave: true,
    language: idioma_español,
    dom: dom,
    buttons: [{
      text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
      titleAttr: 'Actualizar Facturas Borrador',
      className: 'table_actualizar btn btn-secondary',
      action: function () { listar_busqueda_bill_draf(); }
    }],
    drawCallback: function () {
      if (typeof getPermisosTipoUsuarioAccesosTable === 'function') {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }
    }
  });

  table.search('').draw();
  continue_bill_draft_dataTable("#DatatableBusquedaBillDraft tbody", table);
  delete_bill_draft_dataTable("#DatatableBusquedaBillDraft tbody", table);
};

var continue_bill_draft_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.pay").on("click", "button.pay", function (e) {
    e.preventDefault();
    var row = table.row($(this).parents("tr")).data();
    if (!row) return;

    // set factura seleccionada
    $("#invoice-form #facturas_id").val(row.facturas_id);

    $.ajax({
      type: 'POST',
      url: "<?php echo SERVERURL;?>core/facturas/getDraftBills.php",
      data: { facturas_id: row.facturas_id },
      dataType: 'json', // <<--- IMPORTANTE: así recibimos un objeto y no hay que parsear nada
      success: function (r) {
        if (!r || r.type !== 'success') {
          return showNotify('error', 'Error', (r && r.message) ? r.message : 'No se pudo cargar la factura.');
        }

        // ====== ENCABEZADO ======
        var h = r.header || {};

        // Cliente (inputs ocultos + cabecera visible)
        $("#cliente_id").val(h.clientes_id || "");
        $("#cliente").val(h.cliente_nombre || "");
        $("#rtn-customers-bill").text(h.cliente_rtn ? ("RTN: " + h.cliente_rtn) : "");
        $("#client-customers-bill").text(h.cliente_nombre ? ("Cliente: " + h.cliente_nombre) : "");

        // Vendedor (inputs ocultos + cabecera visible)
        $("#colaborador_id").val(h.colaboradores_id || "");
        $("#colaborador").val(h.vendedor_nombre || "");
        $("#vendedor-customers-bill").text(h.vendedor_nombre ? ("Vendedor: " + h.vendedor_nombre) : "");

        // Fecha / fecha dólar / notas
        if (h.fecha) $("#fecha").val(h.fecha);
        if (h.fecha_dolar) $("#fecha_dolar").val(h.fecha_dolar);
        $("#notesBill").val(h.notas || "");

        // Exoneración
        $("#exoneracion_orden").val(h.no_orden || "");
        $("#exoneracion_constancia").val(h.constancia || "");
        $("#exoneracion_sag").val(h.identificativo_sag || "");
        $("#exoneracion_orden_interno").val(h.numero_interno || "");

        // Tipo de factura (tu hidden usa 1=Contado, 0=Crédito; la BD trae 1/2)
        var tf = parseInt(h.tipo_factura, 10);
        if (tf === 2) { // Crédito
          $("#facturas_activo").val(0);
          $("#btn-tipo-credito").addClass("active btn-primary").removeClass("btn-outline-primary");
          $("#btn-tipo-contado").removeClass("active btn-primary").addClass("btn-outline-primary");
        } else { // Contado (default)
          $("#facturas_activo").val(1);
          $("#btn-tipo-contado").addClass("active btn-primary").removeClass("btn-outline-primary");
          $("#btn-tipo-credito").removeClass("active btn-primary").addClass("btn-outline-primary");
        }

        // ====== DETALLE ======
        var datos = Array.isArray(r.detalle) ? r.detalle : [];

        // Limpia la tabla y deja UNA fila base (index 0)
        limpiarTablaFacturaDetalles(0);

        // Rellena filas guardadas
        for (var i = 0; i < datos.length; i++) {
          if (i > 0) addRowFacturas();

          $("#facturas_detalle_id_" + i).val(datos[i]["facturas_detalle_id"] || "");
          $("#bar-code-id_" + i).val(datos[i]["barCode"] || "");
          $("#productos_id_" + i).val(datos[i]["productos_id"] || "");
          $("#productName_" + i).val(datos[i]["producto"] || "");
          $("#productName_text_" + i).text(datos[i]["producto"] || "Descripción del Producto");
          $("#quantity_" + i).val(datos[i]["cantidad"] || 0);
          $("#price_" + i).val(datos[i]["precio"] || 0);
          $("#precio_real_" + i).val(datos[i]["precio"] || 0);
          $("#isv_" + i).val(datos[i]["isv_venta"] || 0);
          $("#valor_isv_" + i).val(datos[i]["isv_valor"] || 0);
          $("#cantidad_mayoreo_" + i).val(datos[i]["cantidad_mayoreo"] || 0);
          $("#precio_mayoreo_" + i).val(datos[i]["precio_venta"] || 0);
          $("#bodega_" + i).val(datos[i]["almacen_id"] || "");
          $("#medida_" + i).val(datos[i]["medida"] || "");
          $("#medida_text_" + i).text(datos[i]["medida"] || "Medida");
          $("#discount_" + i).val(datos[i]["descuento"] || 0);
        }

        // Recalcula importes
        calculateTotalFacturas();

        // Agrega una fila VACÍA extra y deja el cursor en su código
        addRowFacturas();

        // Cierra el modal
        $('#modal_buscar_bill_draft').modal('hide');

        // Notificación bonita
        showNotify('success', 'Borrador', 'Factura cargada correctamente.');
      },
      error: function (xhr) {
        showNotify('error', 'Error', 'Error de comunicación (' + xhr.status + ').');
      }
    });
  });
};

var delete_bill_draft_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.eliminar").on("click", "button.eliminar", function (e) {
    e.preventDefault();
    var row = table.row($(this).parents("tr")).data();
    if (!row) return;
    deleteBillDraft(row.facturas_id);
  });
};

function deleteBillDraft(facturas_id) {
  var numeroFactura = '';

  try {
    numeroFactura = getNumeroFactura(facturas_id);
  } catch (e) {
    numeroFactura = facturas_id;
  }

  /*
    IMPORTANTE:
    Cuando SweetAlert se abre encima de un modal Bootstrap,
    Bootstrap puede bloquear el foco y no deja escribir en textarea/input.
    Por eso desactivamos temporalmente enforceFocus y lo restauramos al final.
  */
  var bootstrapEnforceFocusOriginal = null;

  try {
    if ($.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype.enforceFocus) {
      bootstrapEnforceFocusOriginal = $.fn.modal.Constructor.prototype.enforceFocus;
      $.fn.modal.Constructor.prototype.enforceFocus = function () {};
    }

    if ($.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype._enforceFocus) {
      bootstrapEnforceFocusOriginal = $.fn.modal.Constructor.prototype._enforceFocus;
      $.fn.modal.Constructor.prototype._enforceFocus = function () {};
    }
  } catch (e) {}

  function restaurarFocusBootstrap() {
    try {
      if (bootstrapEnforceFocusOriginal) {
        if ($.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype.enforceFocus) {
          $.fn.modal.Constructor.prototype.enforceFocus = bootstrapEnforceFocusOriginal;
        }

        if ($.fn.modal && $.fn.modal.Constructor && $.fn.modal.Constructor.prototype._enforceFocus) {
          $.fn.modal.Constructor.prototype._enforceFocus = bootstrapEnforceFocusOriginal;
        }
      }
    } catch (e) {}
  }

  var textarea = document.createElement('textarea');
  textarea.id = 'motivo_eliminar_borrador';
  textarea.className = 'swal-content__textarea';
  textarea.rows = 4;
  textarea.placeholder = 'Ejemplo: Borrador creado por error, cliente cambió la compra, datos incorrectos...';
  textarea.style.width = '100%';
  textarea.style.minHeight = '105px';
  textarea.style.resize = 'vertical';
  textarea.style.padding = '12px';
  textarea.style.border = '1px solid #d9d9d9';
  textarea.style.borderRadius = '4px';
  textarea.style.outline = 'none';
  textarea.style.fontSize = '14px';

  swal({
    title: "Eliminar factura borrador",
    text: "Escriba la razón por la que desea eliminar la factura borrador # " + numeroFactura + ".",
    icon: "warning",
    content: textarea,
    buttons: {
      cancel: {
        text: "Cancelar",
        visible: true,
        closeModal: true
      },
      confirm: {
        text: "Eliminar borrador",
        closeModal: false
      }
    },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function (willConfirm) {
    if (willConfirm !== true) {
      restaurarFocusBootstrap();
      swal.close();
      return false;
    }

    var motivo = $.trim($('#motivo_eliminar_borrador').val() || '');

    if (motivo.length < 5) {
      swal.stopLoading();

      showNotify(
        'error',
        'Motivo requerido',
        'Debe escribir una razón válida para eliminar la factura borrador.'
      );

      setTimeout(function () {
        $('#motivo_eliminar_borrador').trigger('focus');
      }, 150);

      return false;
    }

    deleteBill(facturas_id, motivo, restaurarFocusBootstrap);
  });

  setTimeout(function () {
    $('#motivo_eliminar_borrador').trigger('focus');
  }, 250);
}

function deleteBill(facturas_id, motivo, callbackFinal) {
  var url = '';

  if (typeof BASE_URL !== 'undefined' && BASE_URL !== '') {
    url = BASE_URL + 'core/deleteBillDraft.php';
  } else {
    url = '<?php echo SERVERURL;?>core/deleteBillDraft.php';
  }

  $.ajax({
    type: 'POST',
    url: url,
    dataType: 'json',
    data: {
      facturas_id: facturas_id,
      motivo: motivo
    },
    beforeSend: function () {
      swal.stopLoading();
    },
    success: function (response) {
      if (typeof callbackFinal === 'function') {
        callbackFinal();
      }

      swal.close();

      if (response && response.success === true) {
        showNotify(
          'success',
          response.title || 'Borrador eliminado',
          response.message || 'La factura borrador ha sido eliminada correctamente.'
        );

        if (typeof listar_busqueda_bill_draf === 'function') {
          listar_busqueda_bill_draf();
        }

        if (typeof listar_busqueda_bill === 'function') {
          listar_busqueda_bill();
        }

        return;
      }

      showNotify(
        'error',
        response && response.title ? response.title : 'Error',
        response && response.message ? response.message : 'La factura borrador no se pudo eliminar.'
      );
    },
    error: function (xhr) {
      if (typeof callbackFinal === 'function') {
        callbackFinal();
      }

      swal.close();

      var mensaje = 'Error de comunicación al eliminar la factura borrador.';

      try {
        var response = JSON.parse(xhr.responseText || '{}');

        if (response && response.message) {
          mensaje = response.message;
        }
      } catch (e) {}

      showNotify('error', 'Error', mensaje);
    }
  });
}

//BUSQUEDA FACTURAS AL CREDITO Y CONTADO
$("#BillReports").on("click", function(e) {
    e.preventDefault();

    listar_busqueda_bill();

    $('#modal_buscar_bill').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
});

var izzyTipoDocumentoCache = null;
var izzyTipoDocumentoSolicitud = null;

function getTipoDocumento(callback) {
    var url = '<?php echo SERVERURL; ?>core/getTipoDocumento.php';

    if (izzyTipoDocumentoCache !== null) {
        return izzyTipoDocumentoCache;
    }

    if (!izzyTipoDocumentoSolicitud) {
    izzyTipoDocumentoSolicitud = $.ajax({
        type: 'POST',
        url: url,
        success: function(response) {
			var datos;
            try {
                datos = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                datos = eval(response);
            }
			izzyTipoDocumentoCache = datos[0];
        },
        error: function(xhr, status, error) {
            izzyTipoDocumentoCache = 'Error en la solicitud';
        },
        complete: function() {
            izzyTipoDocumentoSolicitud = null;
            if (typeof callback === 'function') {
                callback(izzyTipoDocumentoCache);
            }
        }
    });
    } else if (typeof callback === 'function') {
        izzyTipoDocumentoSolicitud.always(function () {
            callback(izzyTipoDocumentoCache);
        });
    }

	return null;
}

/* =========================================================
   BUSCAR FACTURAS CRÉDITO / CONTADO
   Header/Footer dinámico + Acciones en dropdown
   Tabla: #DatatableBusquedaBill
   Form:  #formulario_bill
   Botón acciones igual que CxC
   ========================================================= */

   function parseMontoBill(valor) {
    if (typeof valor === 'string') {
        valor = valor.replace(/L\./g, '').replace(/L/g, '').replace(/,/g, '').trim();
    }

    valor = parseFloat(valor || 0);
    return isNaN(valor) ? 0 : valor;
}

function formatoMonedaBill(valor) {
    valor = parseMontoBill(valor);

    return 'L. ' + valor.toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderMonedaBill(data, type) {
    var valor = parseMontoBill(data);
    var number = formatoMonedaBill(valor);

    if (type === 'display') {
        var color = valor < 0 ? '#dc2626' : '#111827';

        return '<span style="color:' + color + '; font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
            number +
        '</span>';
    }

    return valor;
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - BUSCAR FACTURAS
   ========================================================= */
function construirHeaderFooterDataTableBill() {
    var $tabla = $("#DatatableBusquedaBill");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Tipo</th>' +
                '<th>Cliente</th>' +
                '<th>Factura</th>' +
                '<th>SubTotal</th>' +
                '<th>ISV</th>' +
                '<th>Descuento</th>' +
                '<th>Total</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="5" class="text-right" style="font-size:0.95rem; font-weight:600;">Totales:</th>' +
                '<th id="bill_footer_subtotal" class="text-right" style="font-size:0.95rem; font-weight:400;">L. 0.00</th>' +
                '<th id="bill_footer_isv" class="text-right" style="font-size:0.95rem; font-weight:400;">L. 0.00</th>' +
                '<th id="bill_footer_descuento" class="text-right" style="font-size:0.95rem; font-weight:400;">L. 0.00</th>' +
                '<th id="bill_footer_total" class="text-right" style="font-size:0.95rem; font-weight:400;">L. 0.00</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

/* =========================================================
   LISTAR BÚSQUEDA FACTURAS
   ========================================================= */
var listar_busqueda_bill = function() {
    var tipo_factura_reporte = 1;

    if (
        $("#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte").val() == null ||
        $("#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte").val() == ""
    ) {
        tipo_factura_reporte = 1;
    } else {
        tipo_factura_reporte = $("#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte").val();
    }

    var fechai = $("#formulario_bill #fechai").val();
    var fechaf = $("#formulario_bill #fechaf").val();
    var facturador = $("#formulario_bill #facturador").val();
    var vendedor = $("#formulario_bill #vendedor").val();
    var factura = getTipoDocumento(function () {
        listar_busqueda_bill();
    });

    if (factura === null) {
        return;
    }

    if (factura === "No hay datos que mostrar" || factura === "Error en la solicitud") {
        showNotify('error', 'Error', 'Lo sentimos, hubo un error al obtener la información de la factura.');
        return;
    }

    if ($.fn.DataTable.isDataTable("#DatatableBusquedaBill")) {
        $("#DatatableBusquedaBill").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTableBill();

    var table_busqueda_bill = $("#DatatableBusquedaBill").DataTable({
        destroy: true,
        bDestroy: true,
        processing: true,
        deferRender: true,
        autoWidth: false,
        responsive: false,
        stateSave: true,
        lengthMenu: lengthMenu,
        language: idioma_español,
        dom: dom,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL; ?>core/llenarDataTableReporteVentas.php",
            dataType: "json",
            data: {
                tipo_factura_reporte: tipo_factura_reporte,
                facturador: facturador,
                vendedor: vendedor,
                factura: factura,
                fechai: fechai,
                fechaf: fechaf
            }
        },

        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function(data, type, row) {
                    if (type !== "display") {
                        return "";
                    }

                    var accionesBill = "";

                    accionesBill +=
                        '<button type="button" class="dropdown-item accion-item accion-factura table_reportes print_factura">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="fas fa-file-download"></i>' +
                            '</span>' +
                            '<span class="accion-label">Factura</span>' +
                        '</button>';

                    accionesBill +=
                        '<button type="button" class="dropdown-item accion-item accion-comprobante table_reportes print_comprobante">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="far fa-file-pdf"></i>' +
                            '</span>' +
                            '<span class="accion-label">Comprobante</span>' +
                        '</button>';

                    accionesBill +=
                        '<button type="button" class="dropdown-item accion-item accion-enviar table_reportes email_factura">' +
                            '<span class="accion-icon accion-icon-primary">' +
                                '<i class="fas fa-paper-plane"></i>' +
                            '</span>' +
                            '<span class="accion-label">Enviar</span>' +
                        '</button>';

                    accionesBill +=
                        '<button type="button" class="dropdown-item accion-item accion-anular table_cancelar cancelar_factura">' +
                            '<span class="accion-icon accion-icon-danger">' +
                                '<i class="fas fa-ban"></i>' +
                            '</span>' +
                            '<span class="accion-label">Anular</span>' +
                        '</button>';

                    return '' +
                        '<div class="acciones-caja-wrap">' +
                            '<div class="dropdown acciones-dropdown">' +
                                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                    '<i class="fas fa-cog"></i>' +
                                    '<span>Acciones</span>' +
                                '</button>' +
                                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                    accionesBill +
                                '</div>' +
                            '</div>' +
                        '</div>';
                }
            },
            {
                data: "fecha",
                className: "text-nowrap"
            },
            {
                data: "tipo_documento",
                className: "text-center text-nowrap",
                render: function(data, type, row) {
                    if (type === 'display') {
                        var tipo = data || "";
                        var tipoNormalizado = String(tipo).toLowerCase();

                        var icon = tipoNormalizado === 'crédito' || tipoNormalizado === 'credito'
                            ? '<i class="fas fa-clock mr-1"></i>'
                            : '<i class="fas fa-check-circle mr-1"></i>';

                        var badgeClass = tipoNormalizado === 'crédito' || tipoNormalizado === 'credito'
                            ? 'badge badge-pill badge-warning'
                            : 'badge badge-pill badge-success';

                        return '<span class="' + badgeClass + '" style="font-size:0.85rem; padding:0.45em 0.7em; font-weight:500;">' +
                            icon + tipo +
                        '</span>';
                    }

                    return data;
                }
            },
            {
                data: "cliente"
            },
            {
                data: "numero",
                className: "text-center text-nowrap"
            },
            {
                data: "subtotal",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaBill(data, type);
                }
            },
            {
                data: "isv",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaBill(data, type);
                }
            },
            {
                data: "descuento",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaBill(data, type);
                }
            },
            {
                data: "total",
                className: "text-right text-nowrap",
                render: function(data, type) {
                    return renderMonedaBill(data, type);
                }
            }
        ],

        columnDefs: [
            {
                width: "10%",
                targets: 0
            },
            {
                width: "11%",
                targets: 1
            },
            {
                width: "11%",
                targets: 2
            },
            {
                width: "24%",
                targets: 3
            },
            {
                width: "16%",
                targets: 4
            },
            {
                width: "9%",
                targets: 5
            },
            {
                width: "8%",
                targets: 6
            },
            {
                width: "10%",
                targets: 7
            },
            {
                width: "11%",
                targets: 8
            }
        ],

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Facturas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function() {
                    listar_busqueda_bill();
                }
            },
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: "Excel",
                title: "Reporte Facturas",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-success ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                }
            },
            {
                extend: "pdf",
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: "PDF",
                orientation: "landscape",
                title: "Reporte Facturas",
                messageBottom: "Fecha de Reporte: " + convertDateFormat(today()),
                className: "table_reportes btn btn-danger ocultar",
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function(doc) {
                    if (typeof imagen !== "undefined" && imagen) {
                        doc.content.splice(0, 0, {
                            image: imagen,
                            width: 100,
                            height: 45,
                            margin: [0, 0, 0, 12]
                        });
                    }
                }
            }
        ],

        footerCallback: function() {
            var api = this.api();

            var totalSubtotal = api.column(5, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoBill(a) + parseMontoBill(b);
            }, 0);

            var totalISV = api.column(6, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoBill(a) + parseMontoBill(b);
            }, 0);

            var totalDescuento = api.column(7, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoBill(a) + parseMontoBill(b);
            }, 0);

            var totalGeneral = api.column(8, { page: "current" }).data().reduce(function(a, b) {
                return parseMontoBill(a) + parseMontoBill(b);
            }, 0);

            $("#bill_footer_subtotal").html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatoMonedaBill(totalSubtotal) +
                '</span>'
            );

            $("#bill_footer_isv").html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatoMonedaBill(totalISV) +
                '</span>'
            );

            $("#bill_footer_descuento").html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatoMonedaBill(totalDescuento) +
                '</span>'
            );

            $("#bill_footer_total").html(
                '<span style="font-size:0.95rem; font-weight:400; white-space:nowrap;">' +
                    formatoMonedaBill(totalGeneral) +
                '</span>'
            );

            $("#contador-registros").html(api.rows({ filter: "applied" }).data().length);
        },

        drawCallback: function(settings) {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            $('[title]').tooltip({
                container: "body",
                placement: "top"
            });
        }
    });

    table_busqueda_bill.search('').draw();

    $('#buscar').focus();

    view_correo_bills_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_reporte_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_comoprobante_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
    view_anular_bill_dataTable("#DatatableBusquedaBill tbody", table_busqueda_bill);
};

var view_anular_bill_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.cancelar_factura");
    $(tbody).on("click", "button.cancelar_factura", function (e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.facturas_id) {
            showNotify('error', 'Error', 'No se pudo obtener la factura seleccionada');
            return false;
        }

        if (typeof validarAdminSistema !== 'function') {
            showNotify('error', 'Validación no disponible', 'No está cargado el JS de autenticación administrativa.');
            return false;
        }

        var facturaId = data.facturas_id;
        var numeroFactura = data.number || data.numero || data.factura || data.numero_factura || data.facturas_id;

        validarAdminSistema(function (permitido) {
            if (permitido !== true) {
                return;
            }

            anularFacturas(facturaId);
        }, {
            mensaje: 'Para anular esta factura debe validar un administrador.',
            modulo: 'Facturación',
            accion: 'Anular factura',
            referencia_id: facturaId,
            referencia_texto: numeroFactura,
            motivo: 'Validación requerida para anular factura desde facturación'
        });

        return false;
    });
};

var view_correo_bills_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.email_factura");
    $(tbody).on("click", "button.email_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        mailBill(data.facturas_id);
    });
}

var view_reporte_bill_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        printBill(data.facturas_id);
    });
}

var view_comoprobante_bill_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.print_comprobante");
    $(tbody).on("click", "button.print_comprobante", function(e) {
        e.preventDefault();
        var data = table.row($(this).parents("tr")).data();
        var url_comprobante = '<?php echo SERVERURL; ?>core/generaComprobante.php?facturas_id=' + data
            .facturas_id;
        window.open(url_comprobante);
    });
}

function getReporteFactura() {
    var url = '<?php echo SERVERURL; ?>core/getTipoFacturaReporte.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte').html("");
            $('#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte').html(data);
            $('#formulario_bill #tipo_factura, #tipo_factura_efectivo_reporte').selectpicker('refresh');
        }
    });
}

function getEstadoFacturaCredito() {
    var url = '<?php echo SERVERURL; ?>core/getEstadoFacturaCredito.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').html("");
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').html(data);
            $('#formulario_busqueda_cuentas_cobrar_clientes #cobrar_clientes_estado').selectpicker(
                'refresh');
        }
    });
}

$('#formulario_bill_draft #search').on("click", function(e) {
    e.preventDefault();
    listar_busqueda_bill_draf();
});

$('#formulario_bill #search').on("click", function(e) {
    e.preventDefault();
    listar_busqueda_bill();
});

//INICIO DESCUENTO PRODUCTO EN FACTURACION
$(() => {
    $(() => {
        $("#invoice-form #invoiceItem").off('click.descuentoFacturaEscritorio', '.aplicar_descuento');
        $("#invoice-form #invoiceItem").on('click.descuentoFacturaEscritorio', '.aplicar_descuento', function(e) {
            e.preventDefault();

            // Obtener la fila actual
            var row_index = obtenerRowIndexFacturaDesdeElemento(this);
            
            // Validar si es una fila válida
            if (row_index === undefined || row_index === null || row_index < 0) {
                // Buscar la última fila con producto
                var totalFilas = parseInt($('#bill_row').val(), 10) || 0;
                for (var i = totalFilas; i >= 0; i--) {
                    var pid = $('#productos_id_' + i).val();
                    if (pid && pid !== '' && pid !== '0') {
                        row_index = i;
                        break;
                    }
                }
            }
            
            var productos_id = $("#invoice-form #invoiceItem #productos_id_" + row_index).val();
            var cliente_id = $('#invoice-form #cliente_id').val();

            if (cliente_id === "" || !productos_id || productos_id === "" || productos_id === "0") {
                showNotify('error', 'Error', 'Debe seleccionar un cliente y un producto antes de continuar');
                return;
            }

            var producto = $("#invoice-form #invoiceItem #productName_" + row_index).val();
            var precio = normalizarNumeroFactura($("#invoice-form #invoiceItem #price_" + row_index).val());
            var cantidad = normalizarNumeroFactura($("#invoice-form #invoiceItem #quantity_" + row_index).val());
            var descuentoActual = normalizarNumeroFactura($("#invoice-form #invoiceItem #discount_" + row_index).val());
            var total = precio * cantidad;

            $('#formDescuentoFacturacion')[0].reset();

            $('#formDescuentoFacturacion #row_index').val(row_index);
            $('#formDescuentoFacturacion #col_index').val($(this).closest("td").index());
            $('#formDescuentoFacturacion #descuento_productos_id').val(productos_id);
            $('#formDescuentoFacturacion #producto_descuento_fact').val(producto);
            $('#formDescuentoFacturacion #precio_descuento_fact').val(total.toFixed(2));
            $('#formDescuentoFacturacion #cantidad_descuento_fact').val(cantidad);
            $('#formDescuentoFacturacion #descuento_fact').val(descuentoActual.toFixed(2));

            if (total > 0) {
                $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(((descuentoActual / total) * 100).toFixed(2));
            } else {
                $('#formDescuentoFacturacion #porcentaje_descuento_fact').val('0.00');
            }

            $('#formDescuentoFacturacion #pro_descuento_fact').val("Aplicar Descuento");

            getPermisosTipoUsuarioAccesosForms(getPrivilegioTipoUsuario());

            $('#modalDescuentoFacturacion').modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
        });
    });

    $("#formDescuentoFacturacion #porcentaje_descuento_fact").off("keyup.descuentoFacturaEscritorio change.descuentoFacturaEscritorio");
    $("#formDescuentoFacturacion #porcentaje_descuento_fact").on("keyup.descuentoFacturaEscritorio change.descuentoFacturaEscritorio", function() {
        var total = normalizarNumeroFactura($('#formDescuentoFacturacion #precio_descuento_fact').val());
        var porcentaje = normalizarNumeroFactura($(this).val());

        if (total <= 0 || porcentaje <= 0) {
            $('#formDescuentoFacturacion #descuento_fact').val('0.00');
            return;
        }

        var descuento = total * (porcentaje / 100);
        $('#formDescuentoFacturacion #descuento_fact').val(descuento.toFixed(2));
    });

    $("#formDescuentoFacturacion #descuento_fact").off("keyup.descuentoFacturaEscritorio change.descuentoFacturaEscritorio");
    $("#formDescuentoFacturacion #descuento_fact").on("keyup.descuentoFacturaEscritorio change.descuentoFacturaEscritorio", function() {
        var total = normalizarNumeroFactura($('#formDescuentoFacturacion #precio_descuento_fact').val());
        var descuento = normalizarNumeroFactura($(this).val());

        if (total <= 0 || descuento <= 0) {
            $('#formDescuentoFacturacion #porcentaje_descuento_fact').val('0.00');
            return;
        }

        var porcentaje = (descuento / total) * 100;
        $('#formDescuentoFacturacion #porcentaje_descuento_fact').val(porcentaje.toFixed(2));
    });
});

$("#reg_DescuentoFacturacion").off("click.descuentoFacturaEscritorio");
$("#reg_DescuentoFacturacion").on("click.descuentoFacturaEscritorio", function(e) {
    e.preventDefault();

    var row_index = $('#formDescuentoFacturacion #row_index').val();
    var descuento = normalizarNumeroFactura($('#formDescuentoFacturacion #descuento_fact').val());
    var precio = normalizarNumeroFactura($("#invoice-form #invoiceItem #price_" + row_index).val());
    var cantidad = normalizarNumeroFactura($("#invoice-form #invoiceItem #quantity_" + row_index).val());

    if (row_index === "" || $("#invoice-form #invoiceItem #productos_id_" + row_index).val() === "") {
        showNotify('error', 'Error', 'No se encontró el producto de la línea seleccionada');
        return;
    }

    var total_sin_descuento = precio * cantidad;

    if (descuento < 0) {
        showNotify('warning', 'Advertencia', 'El descuento no puede ser menor a cero');
        return;
    }

    if (descuento > total_sin_descuento) {
        showNotify('warning', 'Advertencia', 'El valor del descuento es mayor al precio total del artículo, por favor corregir');
        return;
    }

    $("#invoice-form #invoiceItem #discount_" + row_index).val(descuento.toFixed(2));

    (async () => {
        await recalcularISVLinea(row_index);
        calculateTotalFacturas();
        $('#modalDescuentoFacturacion').modal('hide');
    })();
});
//FIN DESCUENTO PRODUCTO EN FACTURACION

//INICIO MODIFICAR PRECIO EN PRODUCTO FACTURACION
$(() => {
    $("#invoice-form #invoiceItem").off('click.editarPrecioFacturaEscritorio', '.aplicar_precio');
    $("#invoice-form #invoiceItem").on('click.editarPrecioFacturaEscritorio', '.aplicar_precio', function(e) {
        e.preventDefault();
        var row_index = obtenerRowIndexFacturaDesdeElemento(this);
        abrirEditarPrecioFacturaEscritorio(row_index);
    });
});
//FIN MODIFICAR PRECIO EN PRODUCTO FACTURACION

$(() => {
    // Guardar datos de exoneración en campos ocultos al hacer clic en el botón "Guardar datos"
    $("#guardar_exoneracion").click(function() {
        $("#exoneracion_orden").val($("#modal_exoneracion_orden").val());
        $("#exoneracion_constancia").val($("#modal_exoneracion_constancia").val());
        $("#exoneracion_sag").val($("#modal_exoneracion_sag").val());
        $("#exoneracion_orden_interno").val($("#modal_exoneracion_orden_interno").val());
        
        // Cerrar modal
        $("#exoneracionModal").modal("hide");
        
        // Opcional: Mostrar un indicador visual de que hay datos de exoneración
        if ($("#modal_exoneracion_orden").val() || $("#modal_exoneracion_constancia").val() || 
            $("#modal_exoneracion_sag").val() || $("#modal_exoneracion_orden_interno").val()) {
            $("#btn_exoneracion").removeClass("btn-outline-info").addClass("btn-info");
        } else {
            $("#btn_exoneracion").removeClass("btn-info").addClass("btn-outline-info");
        }
    });
    
    // Cargar datos en el modal cuando se abre
    $("#exoneracionModal").on("show.bs.modal", function() {
        $("#modal_exoneracion_orden").val($("#exoneracion_orden").val());
        $("#modal_exoneracion_constancia").val($("#exoneracion_constancia").val());
        $("#modal_exoneracion_sag").val($("#exoneracion_sag").val());
        $("#modal_exoneracion_orden_interno").val($("#exoneracion_orden_interno").val());
    });

    $('#exoneracionModal').on('shown.bs.modal', function() {
        $('#modal_exoneracion_orden').focus();
    });
});


// === Control de Tipo de Factura con botones ===
// Proforma NO bloquea contado/crédito.
// Puede existir:
// Contado + Proforma
// Crédito + Proforma

let tipoActual = 'contado';

function setTipoFactura(tipo) {
    const isContado = (tipo === 'contado');

    tipoActual = tipo;

    $('#tipo-factura-control').removeClass('proforma-activa');

    $('#btn-tipo-contado')
        .prop('disabled', false)
        .toggleClass('active btn-primary', isContado)
        .toggleClass('btn-outline-primary', !isContado);

    $('#btn-tipo-credito')
        .prop('disabled', false)
        .toggleClass('active btn-primary', !isContado)
        .toggleClass('btn-outline-primary', isContado);

    // Backend actual:
    // 1 = Contado
    // 0 = Crédito
    $('#facturas_activo').val(isContado ? 1 : 0);

    if ($('#label_facturas_activo').length) {
        $('#label_facturas_activo').text(isContado ? 'Contado' : 'Crédito');
    }

    }

// Inicial: siempre Contado seleccionado
$(function () {
    setTipoFactura('contado');
});

// Click en Contado
$(document).off('click.tipoContado', '#btn-tipo-contado');
$(document).on('click.tipoContado', '#btn-tipo-contado', function () {
    setTipoFactura('contado');
});

// Click en Crédito
$(document).off('click.tipoCredito', '#btn-tipo-credito');
$(document).on('click.tipoCredito', '#btn-tipo-credito', function () {
    if (tipoActual === 'contado') {
        $('#confirmTipoFactura')
            .data('next', 'credito')
            .modal({
                show: true,
                keyboard: false,
                backdrop: 'static'
            });
    } else {
        setTipoFactura('credito');
    }
});

// Confirmar Crédito
$(document).off('click.confirmarTipoFactura', '#confirmarCambioTipo');
$(document).on('click.confirmarTipoFactura', '#confirmarCambioTipo', function () {
    const next = $('#confirmTipoFactura').data('next') || 'credito';
    setTipoFactura(next);
    $('#confirmTipoFactura').modal('hide');
});


/*RECURRENCIA EN FACTURAS*/
/* =========== RECURRENCIA EN FACTURAS =========== */

/* Utilidades */
// Utilidad para recalcular ISV de una línea (usada por blur/keyup y +/-)
async function recalcularISVLinea(row_index){
  // llama al nuevo cálculo con caché de ISV 1/2
  await recalcISVForRow(row_index);
}

function _localISOString(dt){
  // datetime-local: "YYYY-MM-DDTHH:mm" (sin zona)
  const pad = n => String(n).padStart(2,'0');
  return dt.getFullYear() + '-' +
         pad(dt.getMonth()+1) + '-' +
         pad(dt.getDate()) + 'T' +
         pad(dt.getHours()) + ':' +
         pad(dt.getMinutes());
}

function fechaLocalRecurrente() {
  var fecha = $('#rec_fecha_inicio').val();
  var hora = $('#rec_hora_inicio').val();
  if (!fecha || !hora) return null;
  var f = fecha.split('-').map(Number);
  var h = hora.split(':').map(Number);
  return new Date(f[0], f[1] - 1, f[2], h[0], h[1] || 0, 0, 0);
}

function sincronizarInicioRecurrente() {
  var fecha = $('#rec_fecha_inicio').val();
  var hora = $('#rec_hora_inicio').val();
  $('#rec_start_at').val(fecha && hora ? fecha + 'T' + hora : '');
}

function formatearFechaRecurrente(fecha) {
  return new Intl.DateTimeFormat('es-HN', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: 'numeric', minute: '2-digit'
  }).format(fecha);
}

function siguienteFechaVistaRecurrente(actual, frecuencia, diaOriginal) {
  var siguiente = new Date(actual.getTime());
  if (frecuencia === 'daily') siguiente.setDate(siguiente.getDate() + 1);
  if (frecuencia === 'weekly') siguiente.setDate(siguiente.getDate() + 7);
  if (frecuencia === 'monthly') {
    var hora = siguiente.getHours(), minuto = siguiente.getMinutes();
    siguiente = new Date(siguiente.getFullYear(), siguiente.getMonth() + 1, 1, hora, minuto, 0, 0);
    var ultimoDia = new Date(siguiente.getFullYear(), siguiente.getMonth() + 1, 0).getDate();
    siguiente.setDate(Math.min(diaOriginal, ultimoDia));
  }
  return siguiente;
}

function actualizarResumenRecurrente() {
  sincronizarInicioRecurrente();
  var inicio = fechaLocalRecurrente();
  var frecuencia = $('#rec_periodicidad').val() || 'monthly';
  var $lista = $('#rec_proximas_fechas').empty();
  if (!inicio || isNaN(inicio.getTime())) {
    $('#rec_resumen_texto').text('Selecciona una fecha y una hora válidas.');
    return;
  }

  var dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
  var horaTexto = new Intl.DateTimeFormat('es-HN', {hour:'numeric', minute:'2-digit'}).format(inicio);
  var texto = '';
  if (frecuencia === 'once') texto = 'Se generará una sola vez: ' + formatearFechaRecurrente(inicio) + '.';
  if (frecuencia === 'daily') texto = 'Se generará todos los días a las ' + horaTexto + ', comenzando el ' + formatearFechaRecurrente(inicio) + '.';
  if (frecuencia === 'weekly') texto = 'Se generará cada ' + dias[inicio.getDay()] + ' a las ' + horaTexto + ', comenzando el ' + formatearFechaRecurrente(inicio) + '.';
  if (frecuencia === 'monthly') texto = 'Se generará el día ' + inicio.getDate() + ' de cada mes a las ' + horaTexto + ', comenzando el ' + formatearFechaRecurrente(inicio) + '.';
  if ($('#rec_sin_fin').is(':checked') && frecuencia !== 'once') texto += ' Continuará hasta que la canceles.';
  if (!$('#rec_sin_fin').is(':checked') && $('#rec_until').val() && frecuencia !== 'once') texto += ' Finalizará el ' + $('#rec_until').val() + '.';
  $('#rec_resumen_texto').text(texto);

  var cantidad = frecuencia === 'once' ? 1 : 4;
  var cursor = new Date(inicio.getTime());
  var hasta = (!$('#rec_sin_fin').is(':checked') && $('#rec_until').val()) ? $('#rec_until').val() : null;
  for (var i = 0; i < cantidad; i++) {
    var fechaIso = cursor.getFullYear() + '-' + String(cursor.getMonth()+1).padStart(2,'0') + '-' + String(cursor.getDate()).padStart(2,'0');
    if (hasta && fechaIso > hasta) break;
    $lista.append('<li>'+escaparTextoRecurrente(formatearFechaRecurrente(cursor))+'</li>');
    cursor = siguienteFechaVistaRecurrente(cursor, frecuencia, inicio.getDate());
  }
  if (!$lista.children().length) $lista.append('<li>La fecha final no permite ninguna generación.</li>');
}

function hayDetalleFactura(){
  const totalFilas = parseInt($('#bill_row').val(), 10) || 0;
  for (let i = 0; i <= totalFilas; i++){
    // Buscar por cualquiera de estos campos
    const pid   = $('#productos_id_'+i).val();
    const pname = $('#productName_'+i).val();
    const qty   = $('#quantity_'+i).val();
    const price = $('#price_'+i).val();
    const barcode = $('#bar-code-id_'+i).val();
    
    // Si tiene código de barras o ID de producto, está cargado
    if((barcode && barcode !== '') || (pid && pid !== '' && pid !== '0')){
      return true;
    }
    if(pname && pname !== '' && pname !== 'Descripción del Producto'){
      return true;
    }
    if(qty && parseFloat(qty) > 0 && price && parseFloat(price) > 0){
      return true;
    }
  }
  return false;
}

function hayEncabezado(){
  return !!($('#cliente_id').val() && $('#colaborador_id').val());
}

/* Abrir modal de recurrencia */
$(document).on('click', '#addRecurringBill', function(){
  // Cada vez que se abre el modal se muestran únicamente las programaciones
  // pendientes/activas. Finalizadas y canceladas quedan disponibles mediante
  // los filtros, pero no saturan el listado principal.
  filtroEstadoRecurrente = '1';

  // Al abrir una nueva programación se habilita nuevamente el guardado.
  $('#confirmRecurring')
    .prop('disabled', false)
    .html('<i class="fas fa-calendar-check mr-1"></i> Guardar recurrencia');

  // Las recurrencias siempre generan cuentas por cobrar al crédito.
  $('#rec_tipo_factura').val('2');

  // Documento por defecto: normal (0)
  $('#rec_tipo_documento').val('0');
  $('#btn-rec-tipo-normal').addClass('btn-primary').removeClass('btn-outline-primary');
  $('#btn-rec-tipo-proforma').addClass('btn-outline-primary').removeClass('btn-primary');

  // Fecha inicio default: ahora + 10min
  var now = new Date();
  now.setMinutes(now.getMinutes()+10);
  var inicioLocal = _localISOString(now).split('T');
  $('#rec_fecha_inicio').val(inicioLocal[0]);
  $('#rec_hora_inicio').val(inicioLocal[1]);
  sincronizarInicioRecurrente();

  // Periodicidad default
  $('#rec_periodicidad').val('monthly');
  $('.rec-frecuencia').removeClass('active').filter('[data-frecuencia="monthly"]').addClass('active');
  $('#rec_until').val('');
  $('#rec_sin_fin').prop('checked', true);
  $('#rec_fin_contenedor').hide();
  $('#rec_enviar_correo').prop('checked', true);

  // Reset spinner/info
  $('#rec_info').show();
  $('#rec_spinner').hide();
  actualizarResumenRecurrente();

  $('#recurringBillModal').modal('show');
  listarFacturasRecurrentes();
});

$(document).on('click', '.rec-frecuencia', function() {
  var frecuencia = String($(this).data('frecuencia'));
  $('#rec_periodicidad').val(frecuencia);
  $('.rec-frecuencia').removeClass('active');
  $(this).addClass('active');
  var esUnaVez = frecuencia === 'once';
  $('#rec_sin_fin').closest('.custom-control').toggle(!esUnaVez);
  $('#rec_fin_contenedor').toggle(!esUnaVez && !$('#rec_sin_fin').is(':checked'));
  actualizarResumenRecurrente();
});

$(document).on('change input', '#rec_fecha_inicio, #rec_hora_inicio, #rec_until', actualizarResumenRecurrente);
$(document).on('change', '#rec_sin_fin', function() {
  $('#rec_fin_contenedor').toggle(!this.checked && $('#rec_periodicidad').val() !== 'once');
  if (this.checked) $('#rec_until').val('');
  actualizarResumenRecurrente();
});

/* Toggle tipo documento (0 normal, 1 proforma) */
$(document).on('click', '#btn-rec-tipo-normal, #btn-rec-tipo-proforma', function(){
  var tipo = String($(this).data('tipo')); // "0" o "1"
  $('#rec_tipo_documento').val(tipo);
  $('#btn-rec-tipo-normal')
    .toggleClass('btn-primary', tipo === "0")
    .toggleClass('btn-outline-primary', tipo !== "0");
  $('#btn-rec-tipo-proforma')
    .toggleClass('btn-primary', tipo === "1")
    .toggleClass('btn-outline-primary', tipo !== "1");
});

/* Guardar recurrencia */
$(document).on('click', '#confirmRecurring', function(){
  // El modal puede abrirse solo para consultar recurrencias, pero para guardar
  // debe existir una factura preparada en la pantalla principal.
  if(!hayEncabezado() && !hayDetalleFactura()){
    showNotify('error','Factura sin preparar','No ha seleccionado los datos de la factura. Seleccione cliente, vendedor y agregue al menos un producto antes de guardar la recurrencia.');
    return;
  }
  if(!hayEncabezado()){
    showNotify('error','Faltan datos','Seleccione el cliente y el vendedor de la factura antes de guardar la recurrencia.');
    return;
  }
  if(!hayDetalleFactura()){
    showNotify('error','Factura sin productos','Agregue al menos un producto a la factura antes de guardar la recurrencia.');
    return;
  }

  sincronizarInicioRecurrente();
  var startAt = $('#rec_start_at').val();
  if(!startAt){
    showNotify('error','Error','Debes indicar la fecha de generación');
    return;
  }

  if ($('#rec_periodicidad').val() !== 'once' && !$('#rec_sin_fin').is(':checked') && !$('#rec_until').val()) {
    showNotify('error','Fecha final requerida','Selecciona hasta cuándo se repetirá o activa "Repetir sin fecha final".');
    return;
  }

  // Construir payload (encabezado)
  var payload = {
    clientes_id: $('#cliente_id').val(),
    colaboradores_id: $('#colaborador_id').val(),
    notas: $('#notesBill').val(),
    fecha_dolar: $('#fecha_dolar').val(),
    tipo_documento: $('#rec_tipo_documento').val(), // "0" normal, "1" proforma
    tipo_factura: 2,                                // Recurrente: siempre al crédito
    start_at: startAt,                              // datetime-local
    periodicidad: $('#rec_periodicidad').val(),     // once/daily/weekly/monthly (según tu modal)
    until: ($('#rec_periodicidad').val() === 'once' || $('#rec_sin_fin').is(':checked')) ? null : ($('#rec_until').val() || null),
    enviar_correo: $('#rec_enviar_correo').is(':checked') ? 1 : 2,
    exoneracion_orden: $('#exoneracion_orden').val() || null,
    exoneracion_constancia: $('#exoneracion_constancia').val() || null,
    exoneracion_sag: $('#exoneracion_sag').val() || null,
    exoneracion_orden_interno: $('#exoneracion_orden_interno').val() || null,
    detalle: []
  };

  // Detalle
  var totalFilas = parseInt($('#bill_row').val(), 10) || 0;
  for (var i = 0; i <= totalFilas; i++) {
    var pid   = $('#productos_id_'+i).val();
    var pname = $('#productName_'+i).val();
    var qty   = $('#quantity_'+i).val();
    var price = $('#price_'+i).val();
    if(!pid || !pname || !qty || !price) continue;

    payload.detalle.push({
      productos_id : pid,
      producto     : pname,
      cantidad     : parseFloat(qty),
      precio       : parseFloat(price),
      descuento    : parseFloat($('#discount_'+i).val() || 0),
      isv_valor    : parseFloat($('#valor_isv_'+i).val() || 0),
      isv_valor1    : parseFloat($('#valor_isv1_'+i).val() || 0),
      medida       : $('#medida_'+i).val() || '',
      almacen_id   : $('#bodega_'+i).val() || ''
      ,precio_real : parseFloat($('#precio_real_'+i).val() || price)
      ,referencia_producto: $('#referenciaProducto_'+i).val() || ''
    });
  }

  if(payload.detalle.length === 0){
    showNotify('error','Sin productos','Debes tener al menos un producto en la factura');
    return;
  }

  // Spinner ON
  $('#rec_info').hide();
  $('#rec_spinner').show();
  $('#confirmRecurring').prop('disabled', true);

  $.ajax({
    type: 'POST',
    url: "<?php echo SERVERURL;?>core/facturas/agregarFacturaRecurrente.php",
    data: { data: JSON.stringify(payload) },
    dataType: 'json'
  })
  .done(function(res){
    if(res && (res.ok === true || res.success === true)){
      // Mantener el modal abierto para que el usuario vea inmediatamente
      // la recurrencia creada y evitar un segundo guardado accidental.
      $('#rec_info').show();
      $('#rec_spinner').hide();
      $('#confirmRecurring')
        .prop('disabled', true)
        .html('<i class="fas fa-check-circle mr-1"></i> Recurrencia guardada');
      showNotify('success','Recurrencia creada','La factura recurrente ha sido guardada');
      listarFacturasRecurrentes();
    }else{
      $('#rec_info').show();
      $('#rec_spinner').hide();
      $('#confirmRecurring').prop('disabled', false);
      var msg = (res && (res.msg || res.message)) ? (res.msg || res.message) : 'No se pudo guardar la recurrencia';
      showNotify('error','Error', msg);
    }
  })
  .fail(function(xhr){
    $('#rec_info').show();
    $('#rec_spinner').hide();
    $('#confirmRecurring').prop('disabled', false);
        showNotify('error','Error','Falló la petición al servidor');
  });
});

function escaparTextoRecurrente(valor) {
  return $('<div>').text(valor == null ? '' : String(valor)).html();
}

function etiquetaPeriodicidadRecurrente(valor) {
  return ({ once:'Una vez', daily:'Diaria', weekly:'Semanal', monthly:'Mensual' })[valor] || valor;
}

function formatearMontoRecurrente(valor) {
  var numero = parseFloat(valor || 0);
  return 'L. ' + numero.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatearFechaRecurrente(valor) {
  if (!valor) return 'Sin próxima fecha';
  var partes = String(valor).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
  if (!partes) return String(valor);
  var fecha = new Date(parseInt(partes[1],10), parseInt(partes[2],10)-1, parseInt(partes[3],10), parseInt(partes[4] || 0,10), parseInt(partes[5] || 0,10));
  return fecha.toLocaleString('es-HN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

var detallesFacturasRecurrentes = {};
var solicitudListadoRecurrentes = null;
var temporizadorListadoRecurrentes = null;
var firmaListadoRecurrentes = '';
var filtroEstadoRecurrente = '1';

function pintarResumenFacturasRecurrentes(resumen) {
  resumen = resumen || {};
  $('#rec_kpi_activas').text(parseInt(resumen.activas || 0, 10).toLocaleString('es-HN'));
  $('#rec_kpi_generadas').text(parseInt(resumen.generadas || 0, 10).toLocaleString('es-HN'));
  $('#rec_kpi_correos').text(parseInt(resumen.correos_enviados || 0, 10).toLocaleString('es-HN'));
  $('#rec_kpi_errores').text(parseInt(resumen.errores || 0, 10).toLocaleString('es-HN'));
  $('#rec_kpi_total').text(formatearMontoRecurrente(resumen.total_facturado || 0));
  $('#rec_kpi_proxima').text(resumen.proxima_generacion ? formatearFechaRecurrente(resumen.proxima_generacion) : 'Sin programación');
  $('#rec_kpi_actualizado').text('Resumen actualizado automáticamente');
}

function aplicarFiltroEstadoRecurrente() {
  var $listado = $('#listaFacturasRecurrentes');
  var $tarjetas = $listado.find('.rec-card');
  var visibles = 0;

  $tarjetas.each(function() {
    var coincide = filtroEstadoRecurrente === 'todas' || String($(this).data('estado')) === filtroEstadoRecurrente;
    $(this).toggle(coincide);
    if (coincide) visibles++;
  });

  $listado.find('.rec-filtro-vacio').remove();
  if ($tarjetas.length && visibles === 0) {
    var nombres = { '1':'pendientes', '2':'canceladas', '3':'finalizadas' };
    var texto = nombres[filtroEstadoRecurrente] || 'con este filtro';
    $listado.append('<div class="rec-filtro-vacio" style="display:block"><i class="far fa-calendar-times fa-2x d-block mb-2"></i>No hay programaciones '+texto+'.</div>');
  }

  $('.rec-filtro-estado').removeClass('active')
    .filter('[data-estado="'+filtroEstadoRecurrente+'"]').addClass('active');
}

function actualizarCantidadesFiltroRecurrente(datos) {
  var cantidades = { '1':0, '2':0, '3':0 };
  (datos || []).forEach(function(item) {
    var estado = String(parseInt(item.estado, 10));
    if (Object.prototype.hasOwnProperty.call(cantidades, estado)) cantidades[estado]++;
  });
  $('#rec_cantidad_pendientes').text(cantidades['1']);
  $('#rec_cantidad_canceladas').text(cantidades['2']);
  $('#rec_cantidad_finalizadas').text(cantidades['3']);
  $('#rec_cantidad_todas').text((datos || []).length);
}

function listarFacturasRecurrentes(opciones) {
  opciones = opciones || {};
  var silencioso = opciones.silencioso === true;
  var $listado = $('#listaFacturasRecurrentes');
  if (!$listado.length) return;
  if (solicitudListadoRecurrentes) return solicitudListadoRecurrentes;

  if (!silencioso) {
    $listado.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando programaciones...</div>');
  }

  solicitudListadoRecurrentes = $.ajax({
    type: 'GET',
    url: '<?php echo SERVERURL;?>core/facturas/listarFacturasRecurrentes.php',
    dataType: 'json',
    cache: false,
    timeout: 15000
  }).done(function(res) {
    if (!res || res.ok !== true) {
      if (silencioso) return;
      var mensajeError = (res && res.msg) ? res.msg : 'El servidor no devolvió una respuesta válida.';
      $listado.html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>'+escaparTextoRecurrente(mensajeError)+'</div>');
      return;
    }

    pintarResumenFacturasRecurrentes(res.resumen);

    if (!Array.isArray(res.data) || res.data.length === 0) {
      if (silencioso && firmaListadoRecurrentes === '[]') return;
      firmaListadoRecurrentes = '[]';
      actualizarCantidadesFiltroRecurrente([]);
      $listado.html('<div class="text-center text-muted py-4"><i class="far fa-calendar-times fa-2x d-block mb-2"></i>No hay programaciones registradas.</div>');
      return;
    }

    var nuevaFirma = JSON.stringify(res.data);
    if (silencioso && nuevaFirma === firmaListadoRecurrentes) {
      return;
    }
    firmaListadoRecurrentes = nuevaFirma;

    var html = '';
    detallesFacturasRecurrentes = {};
    actualizarCantidadesFiltroRecurrente(res.data);
    res.data.forEach(function(item) {
      var estado = parseInt(item.estado, 10);
      var estadoTexto = estado === 1 ? 'Activa' : (estado === 2 ? 'Cancelada' : 'Finalizada');
      var estadoClase = estado === 1 ? 'success' : (estado === 2 ? 'danger' : 'secondary');
      var recId = parseInt(item.rec_id, 10);
      detallesFacturasRecurrentes[recId] = Array.isArray(item.detalle) ? item.detalle : [];
      html += '<div class="rec-card" data-estado="'+estado+'">'
        + '<div class="rec-card-main">'
        + '<div class="d-flex justify-content-between align-items-start">'
        + '<div class="rec-card-title"><i class="fas fa-user mr-1 text-primary"></i>'+escaparTextoRecurrente(item.cliente)+'</div>'
        + '<span class="badge badge-'+estadoClase+'">'+estadoTexto+'</span></div>'
        + '<div class="rec-card-meta">'
        + '<span class="rec-chip"><i class="far fa-file-alt mr-1"></i>'+escaparTextoRecurrente(item.documento)+'</span>'
        + '<span class="rec-chip"><i class="far fa-calendar-alt mr-1"></i>'+escaparTextoRecurrente(etiquetaPeriodicidadRecurrente(item.periodicidad))+'</span>'
        + '<span class="rec-chip"><i class="fas fa-credit-card mr-1"></i>Crédito</span>'
        + '<span class="rec-chip"><i class="fas fa-envelope mr-1"></i>Correo '+(parseInt(item.enviar_correo,10) === 1 ? 'activado' : 'desactivado')+'</span></div>'
        + '<div class="rec-card-datos">'
        + '<div class="rec-dato"><small>Próxima generación</small><strong>'+escaparTextoRecurrente(formatearFechaRecurrente(item.next_run_at))+'</strong></div>'
        + '<div class="rec-dato"><small>Productos</small><strong>'+parseInt(item.cantidad_productos || 0,10)+' producto(s)</strong></div>'
        + '<div class="rec-dato"><small>Total estimado</small><strong>'+escaparTextoRecurrente(formatearMontoRecurrente(item.total_estimado))+'</strong></div></div>'
        + '<div class="rec-card-actions">'
        + '<button type="button" class="btn btn-outline-primary btn-sm ver-detalle-recurrente" data-id="'+recId+'"><i class="fas fa-eye mr-1"></i>Ver detalle</button>'
        + (estado === 1 ? '<button type="button" class="btn btn-outline-danger btn-sm cancelar-recurrente" data-id="'+recId+'"><i class="fas fa-ban mr-1"></i>Cancelar</button>' : '')
        + '</div></div><div class="rec-detalle" id="rec-detalle-'+recId+'"></div></div>';
    });
    $listado.html(html);
    aplicarFiltroEstadoRecurrente();
  }).fail(function(xhr, estado) {
    // Una actualización automática fallida no reemplaza un listado que ya
    // estaba visible. El siguiente ciclo volverá a intentarlo.
    if (silencioso) return;
    var detalle = estado === 'timeout'
      ? 'La consulta tardó demasiado. Verifique el PHP y la base de datos.'
      : 'No se pudieron cargar las programaciones (HTTP '+(xhr.status || 0)+').';
    $listado.html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-1"></i>'+detalle+'</div>');
  }).always(function() {
    solicitudListadoRecurrentes = null;
  });

  return solicitudListadoRecurrentes;
}

$(document).on('click', '.rec-filtro-estado', function() {
  filtroEstadoRecurrente = String($(this).data('estado') || '1');
  aplicarFiltroEstadoRecurrente();
});

function detenerActualizacionRecurrentes() {
  if (temporizadorListadoRecurrentes) {
    clearInterval(temporizadorListadoRecurrentes);
    temporizadorListadoRecurrentes = null;
  }
}

function iniciarActualizacionRecurrentes() {
  detenerActualizacionRecurrentes();
  temporizadorListadoRecurrentes = setInterval(function() {
    if ($('#recurringBillModal').hasClass('show')) {
      listarFacturasRecurrentes({ silencioso: true });
    }
  }, 20000);
}

$('#recurringBillModal')
  .off('shown.bs.modal.recurrentesAuto hidden.bs.modal.recurrentesAuto')
  .on('shown.bs.modal.recurrentesAuto', function() {
    iniciarActualizacionRecurrentes();
  })
  .on('hidden.bs.modal.recurrentesAuto', function() {
    detenerActualizacionRecurrentes();
  });

$(document).on('click', '#recargarRecurrentes', function() {
  listarFacturasRecurrentes();
});

$(document).on('click', '.ver-detalle-recurrente', function() {
  var $boton = $(this);
  var recId = parseInt($boton.data('id'), 10);
  var $detalle = $('#rec-detalle-' + recId);
  if (!recId || !$detalle.length) return;

  if ($detalle.is(':visible')) {
    $detalle.slideUp(150);
    $boton.html('<i class="fas fa-eye mr-1"></i>Ver detalle');
    return;
  }
  $detalle.slideDown(150);
  $boton.html('<i class="fas fa-eye-slash mr-1"></i>Ocultar detalle');
  if ($detalle.data('cargado')) return;

  var productos = detallesFacturasRecurrentes[recId] || [];
  var totalDetalle = 0;
  var html = '';
    if (productos.length === 0) {
      html += '<div class="text-muted">Esta programación no tiene productos guardados.</div>';
    } else {
      html += '<div class="rec-producto font-weight-bold text-muted"><span>Producto</span><span>Cantidad</span><span>Precio</span><span>Total</span></div>';
      productos.forEach(function(producto) {
        totalDetalle += parseFloat(producto.total_linea || 0);
        html += '<div class="rec-producto"><strong>'+escaparTextoRecurrente(producto.producto)+'</strong>'
          + '<span>'+escaparTextoRecurrente(producto.cantidad)+' '+escaparTextoRecurrente(producto.medida || '')+'</span>'
          + '<span>'+escaparTextoRecurrente(formatearMontoRecurrente(producto.precio))+'</span>'
          + '<span>'+escaparTextoRecurrente(formatearMontoRecurrente(producto.total_linea))+'</span></div>';
      });
      html += '<div class="rec-detalle-total">Total programado: '+escaparTextoRecurrente(formatearMontoRecurrente(totalDetalle))+'</div>';
    }
    $detalle.data('cargado', true).html(html);
});

$(document).on('click', '.cancelar-recurrente', function() {
  var recId = parseInt($(this).data('id'), 10);
  if (!recId) {
    showNotify('error', 'Recurrencia inválida', 'No se pudo identificar la recurrencia que desea cancelar.');
    return;
  }

  swal({
    title: 'Cancelar factura recurrente',
    text: '¿Está seguro de cancelar esta programación? Ya no se generarán nuevas facturas de esta recurrencia.',
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'No, mantener activa',
        visible: true,
        closeModal: true
      },
      confirm: {
        text: 'Sí, cancelar',
        closeModal: false
      }
    },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function(confirmarCancelacion) {
    if (confirmarCancelacion !== true) return;

    $.ajax({
      type: 'POST',
      url: '<?php echo SERVERURL;?>core/facturas/cancelarFacturaRecurrente.php',
      dataType: 'json',
      data: { rec_id: recId }
    }).done(function(res) {
      swal.close();
      if (res && res.ok === true) {
        showNotify('success', 'Recurrencia cancelada', res.msg || 'La programación fue cancelada correctamente.');
        listarFacturasRecurrentes();
      } else {
        showNotify('error', 'No se pudo cancelar', (res && res.msg) || 'No se pudo cancelar la recurrencia.');
      }
    }).fail(function() {
      swal.close();
      showNotify('error', 'Error de comunicación', 'No fue posible comunicarse con el servidor para cancelar la recurrencia.');
    });
  });
});

/* ===== Ayuda (lógica) ===== */
(function(){
  // Filtro por texto
  function filterRows(term){
    term = (term || '').toLowerCase();
    $('#tableShortcuts tbody tr').each(function(){
      const txt = $(this).text().toLowerCase();
      $(this).toggle(txt.indexOf(term) > -1);
    });
  }

  // Al abrir: limpiar y enfocar el buscador
  $('#modalAyuda').on('shown.bs.modal', function(){
    $('#helpSearch').val('').trigger('focus');
  });

  // Buscar mientras escribe
  $(document).on('input', '#helpSearch', function(){
    filterRows(this.value);
  });

  // Copiar a portapapeles
  $('#helpCopy').on('click', function(){
    const rows = [];
    $('#tableShortcuts tbody tr:visible').each(function(){
      const tds = $(this).find('td');
      rows.push(
        `${$(tds[0]).text()} — ${$(tds[1]).text()} — ${$(tds[2]).text()}`
      );
    });
    const text = rows.join('\n');
    navigator.clipboard.writeText(text).then(function(){
      if (typeof showNotify === 'function') {
        showNotify('success','Copiado','Atajos copiados al portapapeles');
      }
    }).catch(function(){ /* silencioso */ });
  });

  // Imprimir
  $('#helpPrint').on('click', function(){
    const htmlTable = document.querySelector('#tableShortcuts').outerHTML;
    const w = window.open('', '_blank');
    w.document.write(`
      <html>
        <head>
          <title>Ayuda - Atajos</title>
          <style>
            body{font:14px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:24px}
            h2{margin:0 0 12px}
            table{width:100%; border-collapse:collapse}
            th,td{padding:8px 10px; border-bottom:1px solid #eee; text-align:left}
            kbd{background:#111;color:#fff;padding:2px 6px;border-radius:4px}
          </style>
        </head>
        <body>
          <h2>Atajos de teclado</h2>
          ${htmlTable}
        </body>
      </html>
    `);
    w.document.close(); w.focus(); w.print();
  });
})();

/* =========================================================
   CONFIGURACIÓN DE FACTURA
   ---------------------------------------------------------
   Funciones principales:
   abrirConfigFacturaConValidacionAdmin()
   - Pide validación administrativa.
   - Si permite, abre el modal de configuración.

   abrirModalConfigFactura()
   - Abre #modalConfigFactura.
   - Luego carga configuración.

   cargarConfigFactura()
   - Consulta core/facturas/getConfigFactura.php.

   renderConfigFactura(items)
   - Pinta las opciones dentro del modal.

   guardarConfigFactura()
   - Envía cambios a core/facturas/updateConfigFactura.php.
========================================================= */

function abrirConfigFacturaConValidacionAdmin() {
    if (typeof validarAdminSistema !== 'function') {
        showNotify('error', 'Validación no disponible', 'No está cargado el JS de autenticación administrativa.');
        return;
    }

    validarAdminSistema(function (permitido) {
        if (permitido !== true) {
            return;
        }

        abrirModalConfigFactura();
    }, {
        mensaje: 'Para modificar la configuración de facturación debe validar un administrador.',
        modulo: 'Facturación',
        accion: 'Abrir configuración de facturación',
        referencia_id: '',
        referencia_texto: 'Configuración de factura / caja / proformas / ISV',
        motivo: 'Validación requerida para modificar configuración'
    });
}

function abrirModalConfigFactura() {
    if ($('#modalConfigFactura').length === 0) {
        showNotify('error', 'Modal no encontrado', 'No existe el modal de configuración de factura.');
        return;
    }

    if (!AUTH_ADMIN_SISTEMA_TOKEN || AUTH_ADMIN_SISTEMA_TOKEN === '') {
        showNotify('error', 'Validación requerida', 'Debe validar un administrador antes de abrir la configuración.');
        return;
    }

    $('#modalConfigFactura').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });

    cargarConfigFactura();
}

function cargarConfigFactura() {
    if (!AUTH_ADMIN_SISTEMA_TOKEN || AUTH_ADMIN_SISTEMA_TOKEN === '') {
        showNotify('error', 'Validación requerida', 'Debe validar un administrador antes de cargar la configuración.');
        return;
    }

    $('#config_factura_contenido').html(
        '<div class="config-factura-loading">' +
            '<i class="fas fa-spinner fa-spin"></i> Cargando configuración...' +
        '</div>'
    );

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/facturas/getConfigFactura.php',
        dataType: 'json',
        data: {
            token: AUTH_ADMIN_SISTEMA_TOKEN
        },
        success: function (response) {
            if (!response || response.success !== true) {
                showNotify(
                    'error',
                    'Error',
                    response && response.message ? response.message : 'No se pudo cargar la configuración.'
                );
                return;
            }

            renderConfigFactura(response.config || []);
        },
        error: function (xhr) {
                        showNotify('error', 'Error', 'Error de comunicación al cargar configuración.');
        }
    });
}

function renderConfigFactura(items) {
    if (!items || items.length === 0) {
        $('#config_factura_contenido').html(
            '<div class="config-factura-loading">No hay configuraciones disponibles.</div>'
        );
        return;
    }

    var html = '';

    items.forEach(function (item) {
        var activo = parseInt(item.activar || 2, 10) === 1;
        var checked = activo ? 'checked' : '';
        var estadoTexto = activo ? 'Activo' : 'Inactivo';
        var badgeClass = activo ? 'is-active' : 'is-inactive';
        var textoEstado = activo ? item.activo_texto : item.inactivo_texto;

        var categoria = String(item.categoria || '').toLowerCase();
        var icono = 'fa-cog';

        if (categoria === 'caja') {
            icono = 'fa-cash-register';
        } else if (categoria === 'proformas') {
            icono = 'fa-file-invoice';
        }

        html += ''
            + '<div class="config-factura-item" data-config-id="' + item.config_id + '">'

            + '    <div class="config-factura-item-header">'
            + '        <div class="config-factura-item-title">'
            + '            <div class="config-factura-icon">'
            + '                <i class="fas ' + icono + '"></i>'
            + '            </div>'
            + '            <div>'
            + '                <h6>' + escaparHtmlConfigFactura(item.titulo) + '</h6>'
            + '                <span class="config-factura-meta">' + escaparHtmlConfigFactura(item.categoria) + ' / ID ' + item.config_id + '</span>'
            + '            </div>'
            + '        </div>'
            + '        <span class="config-factura-badge ' + badgeClass + ' config-factura-estado">' + estadoTexto + '</span>'
            + '    </div>'

            + '    <div class="config-factura-descripcion">'
            +          escaparHtmlConfigFactura(item.descripcion)
            + '    </div>'

            + '    <div class="config-factura-explicacion">'
            +          escaparHtmlConfigFactura(textoEstado)
            + '    </div>'

            + '    <div class="config-factura-switch-row">'
            + '        <div class="config-factura-switch-text">'
            + '            <label class="config-factura-switch-label" for="config_factura_' + item.config_id + '">Activar / Desactivar</label>'
            + '            <span class="config-factura-switch-ayuda">Cambie el estado y luego presione Guardar cambios.</span>'
            + '        </div>'

            + '        <label class="config-factura-switch-control" for="config_factura_' + item.config_id + '">'
            + '            <input '
            + '                type="checkbox" '
            + '                class="config-factura-switch" '
            + '                id="config_factura_' + item.config_id + '" '
            + '                data-config-id="' + item.config_id + '" '
            + '                data-activo-texto="' + escaparAtributoConfigFactura(item.activo_texto) + '" '
            + '                data-inactivo-texto="' + escaparAtributoConfigFactura(item.inactivo_texto) + '" '
            +                  checked
            + '            >'
            + '            <span class="config-factura-slider"></span>'
            + '        </label>'
            + '    </div>'

            + '</div>';
    });

    $('#config_factura_contenido').html(html);
}

function escaparHtmlConfigFactura(texto) {
    return $('<div>').text(texto || '').html();
}

function escaparAtributoConfigFactura(texto) {
    return escaparHtmlConfigFactura(texto).replace(/"/g, '&quot;');
}

function obtenerCambiosConfigFactura() {
    var cambios = [];

    $('.config-factura-switch').each(function () {
        var config_id = parseInt($(this).data('config-id') || 0, 10);
        var activar = $(this).is(':checked') ? 1 : 2;

        if (config_id > 0) {
            cambios.push({
                config_id: config_id,
                activar: activar
            });
        }
    });

    return cambios;
}


/* =========================================================
   APLICAR CONFIGURACIÓN EN PANTALLA SIN RECARGAR
   ---------------------------------------------------------
   IDs de config usados:
   3 = Activar proformas
   4 = Rebajar inventario en proforma
   6 = Calcular ISV en proformas

   Esto evita tener que recargar la página después de guardar.
========================================================= */
function obtenerValorCambioConfigFactura(cambios, config_id, valorDefault) {
    config_id = parseInt(config_id || 0, 10);

    for (var i = 0; i < cambios.length; i++) {
        if (parseInt(cambios[i].config_id || 0, 10) === config_id) {
            return parseInt(cambios[i].activar || 2, 10);
        }
    }

    return valorDefault;
}

function forzarEstadoCheckboxFactura(selector, activo) {
    var $check = $(selector);

    if (!$check.length) {
        return;
    }

    activo = activo === true;

    $check.prop('checked', activo);

    if ($check[0]) {
        $check[0].checked = activo;
    }

    $check.val(activo ? 1 : 0);

    if (activo) {
        $check.attr('checked', 'checked');
    } else {
        $check.removeAttr('checked');
    }
}

function aplicarConfigFacturaEnVistaActual(cambios) {
    cambios = cambios || [];

    var valorProforma = obtenerValorCambioConfigFactura(cambios, 3, null);
    var valorRebajarInventario = obtenerValorCambioConfigFactura(cambios, 4, null);
    var valorISVProforma = obtenerValorCambioConfigFactura(cambios, 6, null);

    var proformaActiva = valorProforma === 1;
    var rebajarInventarioActivo = valorRebajarInventario === 1;
    var isvProformaActivo = valorISVProforma === 1;

    window.IZZY_CONFIG_FACTURA_ACTUAL = window.IZZY_CONFIG_FACTURA_ACTUAL || {};

    if (valorProforma !== null) {
        window.IZZY_CONFIG_FACTURA_ACTUAL.proforma_activa = proformaActiva ? 1 : 0;
    }

    if (valorRebajarInventario !== null) {
        window.IZZY_CONFIG_FACTURA_ACTUAL.proforma_rebajar_inventario = rebajarInventarioActivo ? 1 : 0;
    }

    if (valorISVProforma !== null) {
        window.IZZY_CONFIG_FACTURA_ACTUAL.proforma_aplica_isv = isvProformaActivo ? 1 : 0;
        window.IZZY_PROFORMA_APLICA_ISV = isvProformaActivo ? 1 : 0;
        window.IZZY_PROFORMA_APLICA_ISV_CARGADO = true;
    }

    if (valorProforma !== null || valorRebajarInventario !== null) {
        if (typeof actualizarUIProformaFacturaNormal === 'function') {
            actualizarUIProformaFacturaNormal(proformaActiva, rebajarInventarioActivo);
        } else {
            forzarEstadoCheckboxFactura('#invoice-form #facturas_proforma', proformaActiva);

            $('#invoice-form #label_facturas_proforma')
                .removeClass('badge-light badge-secondary badge-info text-white')
                .addClass(proformaActiva ? 'badge-info text-white' : 'badge-light')
                .html(proformaActiva ? 'Sí' : 'No');

            if (proformaActiva) {
                $('#invoice-form #proforma_rebajar_inventario_container').attr('style', 'display:flex !important;');
                forzarEstadoCheckboxFactura('#invoice-form #proforma_bajar_inventario', rebajarInventarioActivo);
                forzarEstadoCheckboxFactura('#invoice-form #bajar_inventario_proforma', rebajarInventarioActivo);
            } else {
                $('#invoice-form #proforma_rebajar_inventario_container').attr('style', 'display:none !important;');
                forzarEstadoCheckboxFactura('#invoice-form #proforma_bajar_inventario', false);
                forzarEstadoCheckboxFactura('#invoice-form #bajar_inventario_proforma', false);
            }

            $('#invoice-form #label_bajar_inventario_proforma')
                .removeClass('badge-success badge-light text-white')
                .addClass((proformaActiva && rebajarInventarioActivo) ? 'badge-success text-white' : 'badge-light')
                .html((proformaActiva && rebajarInventarioActivo) ? 'Sí' : 'No');
        }
    }

    if (valorISVProforma !== null) {
        if (typeof recalcularTodasLineasISVFactura === 'function') {
            recalcularTodasLineasISVFactura(true);
        }

        if (typeof calculateTotal === 'function') {
            calculateTotal();
        }
    }

    $(document).trigger('configFacturaAplicadaEnVista', [{
        proforma_activa: proformaActiva ? 1 : 0,
        proforma_rebajar_inventario: rebajarInventarioActivo ? 1 : 0,
        proforma_aplica_isv: isvProformaActivo ? 1 : 0,
        cambios: cambios
    }]);
}

function guardarConfigFactura() {
    var cambios = obtenerCambiosConfigFactura();

    if (cambios.length === 0) {
        showNotify('error', 'Sin datos', 'No hay configuraciones para guardar.');
        return;
    }

    if (!AUTH_ADMIN_SISTEMA_TOKEN || AUTH_ADMIN_SISTEMA_TOKEN === '') {
        showNotify('error', 'Validación requerida', 'Debe validar un administrador antes de guardar.');
        return;
    }

    $('#btn_guardar_config_factura')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL;?>core/facturas/updateConfigFactura.php',
        dataType: 'json',
        data: {
            token: AUTH_ADMIN_SISTEMA_TOKEN,
            configs: JSON.stringify(cambios)
        },
        success: function (response) {
            $('#btn_guardar_config_factura')
                .prop('disabled', false)
                .html('<i class="far fa-save"></i> Guardar cambios');

            if (!response || response.success !== true) {
                showNotify(
                    'error',
                    'Error',
                    response && response.message ? response.message : 'No se pudo guardar la configuración.'
                );
                return;
            }

            showNotify(
                'success',
                'Configuración actualizada',
                response.message || 'Configuración actualizada correctamente.'
            );

            aplicarConfigFacturaEnVistaActual(cambios);

            cargarConfigFactura();

            if (typeof consultarConfigISVProformaFactura === 'function') {
                window.IZZY_PROFORMA_APLICA_ISV_CARGADO = false;
                consultarConfigISVProformaFactura(true);
            }

            if (typeof aplicarConfiguracionProformaDesdeServidor === 'function') {
                aplicarConfiguracionProformaDesdeServidor();
            }

            setTimeout(function () {
                aplicarConfigFacturaEnVistaActual(cambios);
            }, 150);

            setTimeout(function () {
                aplicarConfigFacturaEnVistaActual(cambios);
            }, 600);
        },
        error: function (xhr) {
            
            $('#btn_guardar_config_factura')
                .prop('disabled', false)
                .html('<i class="far fa-save"></i> Guardar cambios');

            showNotify('error', 'Error', 'Error de comunicación al guardar configuración.');
        }
    });
}

$(document)
    .off('click.configFacturaAbrir', '#btn_config_factura')
    .on('click.configFacturaAbrir', '#btn_config_factura', function () {
        abrirConfigFacturaConValidacionAdmin();
    });

$(document)
    .off('click.configFacturaRecargar', '#btn_recargar_config_factura')
    .on('click.configFacturaRecargar', '#btn_recargar_config_factura', function () {
        cargarConfigFactura();
    });

$(document)
    .off('click.configFacturaGuardar', '#btn_guardar_config_factura')
    .on('click.configFacturaGuardar', '#btn_guardar_config_factura', function () {
        guardarConfigFactura();
    });

$(document)
    .off('change.configFacturaSwitch', '.config-factura-switch')
    .on('change.configFacturaSwitch', '.config-factura-switch', function () {
        var $switch = $(this);
        var $item = $switch.closest('.config-factura-item');
        var activo = $switch.is(':checked');
        var texto = activo ? $switch.data('activo-texto') : $switch.data('inactivo-texto');

        $item.find('.config-factura-estado')
            .removeClass('is-active is-inactive')
            .addClass(activo ? 'is-active' : 'is-inactive')
            .text(activo ? 'Activo' : 'Inactivo');

        $item.find('.config-factura-explicacion').text(texto || '');
    });
</script>
