<script>

/* =========================================================
   IZZY | SELECT2 ÚNICO
   Normaliza selects dinámicos sin depender de otro componente.
   ========================================================= */
if (typeof window.izzySelect2LimpiarControl !== 'function') {
    window.izzySelect2LimpiarControl = function ($select) {
        if (!$select || !$select.length) {
            return;
        }

        $select.each(function () {
            var $el = $(this);

            if (!$el.is('select')) {
                return;
            }

            /*
             * Si quedó un wrapper visual anterior alrededor del
             * select, recuperar el <select> original antes de usar
             * Select2. Se detecta por estructura, no por librería.
             */
            var $parent = $el.parent();

            if (
                $parent.is('div') &&
                $parent.children('button.dropdown-toggle').length &&
                $parent.children('.dropdown-menu').length
            ) {
                $el.insertBefore($parent);
                $parent.remove();
            }

            var clases = String($el.attr('class') || '')
                .split(/\s+/)
                .filter(function (clase) {
                    if (!clase) {
                        return false;
                    }

                    var normalizada = clase.toLowerCase();

                    return (
                        normalizada.indexOf('picker') === -1 &&
                        normalizada !== 'bs-select-hidden'
                    );
                });

            $el.attr('class', clases.join(' '));

            if ($el.attr('tabindex') === '-98') {
                $el.removeAttr('tabindex');
            }
        });
    };
}

if (typeof window.izzySoloSelect2 !== 'function') {
    window.izzySoloSelect2 = function ($select, options) {
        if (!$select || !$select.length) {
            return;
        }

        window.izzySelect2LimpiarControl($select);

        if (typeof $.fn.select2 !== 'function') {
            return;
        }

        $select.each(function () {
            var $el = $(this);

            if (!$el.is('select')) {
                return;
            }

            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }

            var config = $.extend(
                {
                    width: '100%',
                    minimumResultsForSearch: 0,
                    allowClear: false
                },
                options || {}
            );

            if (!config.dropdownParent) {
                var $modal = $el.closest('.modal');

                if ($modal.length) {
                    config.dropdownParent = $modal;
                }
            }

            $el.select2(config);
        });
    };
}

if (typeof window.izzySoloRefreshSelect2 !== 'function') {
    window.izzySoloRefreshSelect2 = function ($select, options) {
        if (!$select || !$select.length) {
            return;
        }

        window.izzySoloSelect2($select, options);
        $select.trigger('change.select2');
    };
}


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

(function($){
    'use strict';

    var TIPO_PAGO_STORAGE_VISTA = 'izzy.confTipoPago.vista';

    var tipoPagoState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        search: '',
        estado: '',
        view: 'detalle',
        preferredView: 'detalle'
    };

    $(document).ready(function(){
        tipoPagoInicializarVista();
        tipoPagoInicializarSelect2();
        tipoPagoBindEventos();
        listar_tipo_pago_contabilidad();
        getCuentaTipoPago();
        getTipoCuenta();
        tipoPagoActualizarLabelEstado();
    });

    function tipoPagoEsc(value){
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function tipoPagoEsMovil(){
        return window.matchMedia
            ? window.matchMedia('(max-width: 767.98px)').matches
            : $(window).width() <= 767;
    }

    function tipoPagoPrepararSelect2($select, options){
        if(!$select.length || typeof $.fn.select2 !== 'function'){
            return;
        }

        window.izzySelect2LimpiarControl($select);

        if($select.hasClass('select2-hidden-accessible')){
            $select.select2('destroy');
        }

        $select.select2($.extend({
            width: '100%',
            minimumResultsForSearch: 0,
            allowClear: false,
            language: {
                noResults: function(){ return 'No se encontraron resultados'; },
                searching: function(){ return 'Buscando...'; },
                inputTooShort: function(){ return 'Escriba para buscar'; }
            }
        }, options || {}));
    }

    function tipoPagoInicializarSelect2(){
        tipoPagoPrepararSelect2(
            $('#estado_conf_tipoPagos'),
            {
                placeholder: 'Todos los estados'
            }
        );

        var $modal = $('#modalConfTipoPago');

        tipoPagoPrepararSelect2(
            $('#formConfTipoPago #confCuentaTipoPago'),
            {
                placeholder: 'Seleccione una cuenta',
                dropdownParent: $modal.length ? $modal : $(document.body)
            }
        );

        tipoPagoPrepararSelect2(
            $('#formConfTipoPago #confTipoCuenta'),
            {
                placeholder: 'Seleccione un tipo de cuenta',
                dropdownParent: $modal.length ? $modal : $(document.body)
            }
        );
    }

    function tipoPagoInicializarVista(){
        var saved = 'detalle';

        try{
            saved = localStorage.getItem(TIPO_PAGO_STORAGE_VISTA) || 'detalle';
        }catch(e){}

        tipoPagoState.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
        tipoPagoState.view = tipoPagoEsMovil() ? 'miniatura' : tipoPagoState.preferredView;

        tipoPagoSincronizarPageSize();
        tipoPagoActualizarBotonesVista();
    }

    function tipoPagoSincronizarPageSize(){
        var mini = tipoPagoState.view === 'miniatura';
        var opciones = mini ? [6,12,18,30] : [10,25,50,100];
        var preferido = mini ? tipoPagoState.pageSizeMiniatura : tipoPagoState.pageSizeDetalle;

        if(opciones.indexOf(preferido) === -1){
            preferido = opciones[0];
        }

        tipoPagoState.pageSize = preferido;

        var $select = $('#tipoPagoPageSize').empty();

        opciones.forEach(function(n){
            $select.append($('<option>').val(n).text(n));
        });

        $select.val(String(preferido));
    }

    function tipoPagoActualizarBotonesVista(){
        var movil = tipoPagoEsMovil();

        if(movil){
            tipoPagoState.view = 'miniatura';
        }

        $('.tipopago-view-btn[data-view="detalle"]')
            .toggleClass('d-none', movil)
            .prop('disabled', movil)
            .attr('aria-hidden', movil ? 'true' : 'false');

        $('.tipopago-view-btn')
            .removeClass('active')
            .attr('aria-pressed','false');

        $('.tipopago-view-btn[data-view="'+tipoPagoState.view+'"]')
            .addClass('active')
            .attr('aria-pressed','true');

        $('#tipoPagoListado')
            .removeClass('vista-detalle vista-miniatura')
            .addClass('vista-'+tipoPagoState.view);

        $('.tipopago-detail-header').toggle(tipoPagoState.view === 'detalle' && !movil);
    }

    function tipoPagoCambiarVista(vista){
        if(tipoPagoEsMovil()){
            tipoPagoState.view = 'miniatura';
        }else{
            tipoPagoState.view = vista === 'miniatura' ? 'miniatura' : 'detalle';
            tipoPagoState.preferredView = tipoPagoState.view;

            try{
                localStorage.setItem(TIPO_PAGO_STORAGE_VISTA, tipoPagoState.preferredView);
            }catch(e){}
        }

        tipoPagoState.page = 1;
        tipoPagoSincronizarPageSize();
        tipoPagoActualizarBotonesVista();
        tipoPagoRender();
    }

    function tipoPagoNormalizarRespuesta(resp){
        if(Array.isArray(resp)){
            return resp;
        }

        if(resp && Array.isArray(resp.data)){
            return resp.data;
        }

        return [];
    }

    function listar_tipo_pago_contabilidad(){
        $('#tipoPagoListado').html(
            '<div class="tipopago-empty">'+
                '<i class="fas fa-spinner fa-spin"></i>'+
                '<strong>Cargando tipos de pago</strong>'+
                '<span>Consultando información...</span>'+
            '</div>'
        );

        $.ajax({
            method: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTableConfTipoPago.php',
            data: {
                estado: tipoPagoState.estado
            },
            dataType: 'json'
        }).done(function(resp){
            tipoPagoState.rows = tipoPagoNormalizarRespuesta(resp);
            tipoPagoFiltrar();
        }).fail(function(xhr){
            tipoPagoState.rows = [];
            tipoPagoState.filtered = [];
            tipoPagoActualizarKpis();
            tipoPagoRender();

            showNotify(
                'error',
                'Error al cargar tipos de pago',
                'No se pudieron obtener los tipos de pago registrados.'
            );

            console.error(xhr.responseText);
        });
    }

    window.listar_tipo_pago_contabilidad = listar_tipo_pago_contabilidad;

    function tipoPagoFiltrar(){
        var q = $.trim(tipoPagoState.search || '').toLowerCase();

        tipoPagoState.filtered = !q
            ? tipoPagoState.rows.slice()
            : tipoPagoState.rows.filter(function(row){
                var texto = [
                    row.nombre,
                    row.codigo,
                    row.cuenta,
                    parseInt(row.estado,10) === 1 ? 'activo' : 'inactivo',
                    row.tipo_pago_id
                ].map(function(v){
                    return String(v == null ? '' : v).toLowerCase();
                }).join(' ');

                return texto.indexOf(q) !== -1;
            });

        tipoPagoState.page = 1;
        tipoPagoActualizarKpis();
        tipoPagoRender();
    }

    function tipoPagoActualizarKpis(){
        var activos = 0;
        var inactivos = 0;
        var cuentas = {};

        tipoPagoState.filtered.forEach(function(row){
            if(parseInt(row.estado,10) === 1){
                activos++;
            }else{
                inactivos++;
            }

            var cuenta = $.trim(String(row.cuenta == null ? '' : row.cuenta));

            if(cuenta && cuenta.toLowerCase() !== 'sin cuenta' && cuenta !== '0'){
                cuentas[cuenta.toLowerCase()] = true;
            }
        });

        $('#tipoPagoKpiRegistros').text(tipoPagoState.filtered.length);
        $('#tipoPagoKpiActivos').text(activos);
        $('#tipoPagoKpiInactivos').text(inactivos);
        $('#tipoPagoKpiCuentas').text(Object.keys(cuentas).length);
    }

    function tipoPagoBadgeEstado(row){
        var activo = parseInt(row.estado,10) === 1;

        return '<span class="tipopago-status '+(activo ? 'is-active' : 'is-inactive')+'">'+
            '<i class="fas '+(activo ? 'fa-check-circle' : 'fa-times-circle')+'"></i>'+
            '<span>'+(activo ? 'Activo' : 'Inactivo')+'</span>'+
        '</span>';
    }

    function tipoPagoAcciones(row, index){
        return ''+
            '<div class="dropdown acciones-dropdown">'+
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">'+
                    '<i class="fas fa-cog"></i>'+
                    '<span>Acciones</span>'+
                '</button>'+
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
                    '<button type="button" class="dropdown-item accion-item accion-editar js-tipopago-editar ocultar" data-index="'+index+'">'+
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>'+
                        '<span class="accion-label">Editar</span>'+
                    '</button>'+
                    '<button type="button" class="dropdown-item accion-item accion-eliminar js-tipopago-eliminar ocultar" data-index="'+index+'">'+
                        '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>'+
                        '<span class="accion-label">Eliminar</span>'+
                    '</button>'+
                '</div>'+
            '</div>';
    }

    function tipoPagoRenderDetalle(pageRows, offset){
        var html = '';

        pageRows.forEach(function(row, i){
            var idx = offset+i;

            html +=
                '<article class="tipopago-detail-row">'+
                    '<div class="tipopago-detail-cell tipopago-actions-cell">'+
                        '<span class="tipopago-cell-label">Acciones</span>'+
                        tipoPagoAcciones(row,idx)+
                    '</div>'+
                    '<div class="tipopago-detail-cell tipopago-name-cell">'+
                        '<span class="tipopago-cell-label">Nombre</span>'+
                        '<span class="tipopago-name-icon"><i class="fas fa-credit-card"></i></span>'+
                        '<strong>'+tipoPagoEsc(row.nombre || 'Sin nombre')+'</strong>'+
                    '</div>'+
                    '<div class="tipopago-detail-cell">'+
                        '<span class="tipopago-cell-label">Código</span>'+
                        '<strong>'+tipoPagoEsc(row.codigo || 'Sin código')+'</strong>'+
                    '</div>'+
                    '<div class="tipopago-detail-cell">'+
                        '<span class="tipopago-cell-label">Cuenta</span>'+
                        '<div class="tipopago-account-copy">'+
                            '<i class="fas fa-wallet"></i>'+
                            '<span>'+tipoPagoEsc(row.cuenta || 'Sin cuenta configurada')+'</span>'+
                        '</div>'+
                    '</div>'+
                    '<div class="tipopago-detail-cell">'+
                        '<span class="tipopago-cell-label">Estado</span>'+
                        tipoPagoBadgeEstado(row)+
                    '</div>'+
                '</article>';
        });

        return html;
    }

    function tipoPagoRenderMiniatura(pageRows, offset){
        var html = '<div class="tipopago-mini-grid">';

        pageRows.forEach(function(row, i){
            var idx = offset+i;

            html +=
                '<article class="tipopago-mini-card">'+
                    '<div class="tipopago-mini-line"></div>'+
                    '<div class="tipopago-mini-head">'+
                        '<span class="tipopago-name-icon"><i class="fas fa-credit-card"></i></span>'+
                        '<div class="tipopago-mini-title">'+
                            '<span>Tipo de pago</span>'+
                            '<h4>'+tipoPagoEsc(row.nombre || 'Sin nombre')+'</h4>'+
                            '<small>'+tipoPagoEsc(row.codigo || 'Sin código')+'</small>'+
                        '</div>'+
                        tipoPagoBadgeEstado(row)+
                    '</div>'+
                    '<div class="tipopago-mini-body">'+
                        '<span>Cuenta contable</span>'+
                        '<strong>'+tipoPagoEsc(row.cuenta || 'Sin cuenta configurada')+'</strong>'+
                    '</div>'+
                    '<div class="tipopago-mini-footer">'+
                        tipoPagoAcciones(row,idx)+
                    '</div>'+
                '</article>';
        });

        return html+'</div>';
    }

    function tipoPagoRender(){
        tipoPagoActualizarBotonesVista();

        var rows = tipoPagoState.filtered || [];

        if(!rows.length){
            $('#tipoPagoListado').html(
                '<div class="tipopago-empty">'+
                    '<i class="fas fa-credit-card"></i>'+
                    '<strong>Sin tipos de pago</strong>'+
                    '<span>No se encontraron registros con los filtros actuales.</span>'+
                '</div>'
            );

            $('#tipoPagoInfo').text('0 registros');
            $('#tipoPagoPaginacion').empty();
            return;
        }

        var totalPages = Math.max(1,Math.ceil(rows.length/tipoPagoState.pageSize));

        if(tipoPagoState.page > totalPages){
            tipoPagoState.page = totalPages;
        }

        var offset = (tipoPagoState.page-1)*tipoPagoState.pageSize;
        var pageRows = rows.slice(offset,offset+tipoPagoState.pageSize);

        $('#tipoPagoListado').html(
            tipoPagoState.view === 'miniatura'
                ? tipoPagoRenderMiniatura(pageRows,offset)
                : tipoPagoRenderDetalle(pageRows,offset)
        );

        $('#tipoPagoInfo').text(
            'Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros'
        );

        tipoPagoRenderPaginacion(totalPages);

        if(typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
           typeof getPrivilegioTipoUsuario === 'function'){
            try{
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }catch(e){}
        }

        if(typeof cerrarDropdownAcciones === 'function'){
            cerrarDropdownAcciones();
        }
    }

    function tipoPagoRenderPaginacion(totalPages){
        var current = tipoPagoState.page;
        var html = '';

        function btn(label,page,disabled,active,icon){
            return '<button type="button" class="tipopago-page-btn'+(active ? ' active' : '')+'" data-page="'+page+'" '+(disabled ? 'disabled' : '')+'>'+
                (icon ? '<i class="'+icon+'"></i>' : '')+
                '<span>'+label+'</span>'+
            '</button>';
        }

        html += btn('Inicio',1,current===1,false,'fas fa-angle-double-left');
        html += btn('Anterior',current-1,current===1,false,'fas fa-angle-left');

        var from = Math.max(1,current-2);
        var to = Math.min(totalPages,from+4);
        from = Math.max(1,to-4);

        for(var p=from;p<=to;p++){
            html += btn(String(p),p,false,p===current,'');
        }

        html += btn('Siguiente',current+1,current===totalPages,false,'fas fa-angle-right');
        html += btn('Final',totalPages,current===totalPages,false,'fas fa-angle-double-right');

        $('#tipoPagoPaginacion').html(html);
    }

    function tipoPagoParseEditar(registro){
        if(Array.isArray(registro)){
            return registro;
        }

        if(typeof registro === 'string'){
            try{
                var json = JSON.parse(registro);
                if(Array.isArray(json)){
                    return json;
                }
            }catch(e){}

            try{
                return eval(registro);
            }catch(e){}
        }

        return null;
    }

    function tipoPagoEditar(row){
        if(!row || !row.tipo_pago_id){
            showNotify('warning','Registro inválido','No se pudo identificar el tipo de pago seleccionado.');
            return;
        }

        var $form = $('#formConfTipoPago');

        if($form[0]){
            $form[0].reset();
        }

        $form.find('#tipo_pago_id').val(row.tipo_pago_id);

        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/editarTipoPago.php',
            data:$form.serialize()
        }).done(function(registro){
            var valores = tipoPagoParseEditar(registro);

            if(!valores){
                showNotify('error','Respuesta inválida','No se pudo interpretar la información del tipo de pago.');
                return;
            }

            $form.attr({
                'data-form':'update',
                'action':'<?php echo SERVERURL;?>ajax/modificarTipoPagoAjax.php'
            });

            $('#reg_formTipoPago').hide();
            $('#edi_formTipoPago').show();
            $('#delete_formTipoPago').hide();

            $form.find('#pro_tipoPago').val('Editar');
            $form.find('#confTipoPago').val(valores[0]);
            $form.find('#confCuentaTipoPago').val(valores[1]);
            $form.find('#confTipoCuenta').val(valores[3]);

            $form.find('#confTipoPago_activo')
                .prop('checked', parseInt(valores[2],10) === 1)
                .prop('disabled', false);

            $form.find('#confCuentaTipoPago').prop('disabled', true);
            $form.find('#confTipoCuenta').prop('disabled', true);
            $form.find('#confTipoPago').prop('readonly', false);

            $('#buscar_confCuentaTipoPago').show();
            $('#estado_tipo_pago').show();

            tipoPagoInicializarSelect2();

            $form.find('#confCuentaTipoPago').val(valores[1]).trigger('change.select2');
            $form.find('#confTipoCuenta').val(valores[3]).trigger('change.select2');

            tipoPagoActualizarLabelEstado();

            $('#modalConfTipoPago').modal({
                show:true,
                keyboard:true,
                backdrop:'static'
            });
        }).fail(function(xhr){
            showNotify('error','Error al editar','No se pudo cargar el tipo de pago seleccionado.');
            console.error(xhr.responseText);
        });
    }

    function tipoPagoEliminar(row){
        if(!row || !row.tipo_pago_id){
            showNotify('warning','Registro inválido','No se pudo identificar el tipo de pago seleccionado.');
            return;
        }

        var mensajeHTML =
            '¿Desea eliminar permanentemente el tipo de pago?<br><br>'+
            '<strong>Nombre:</strong> '+tipoPagoEsc(row.nombre || '');

        swal({
            title:'Confirmar eliminación',
            content:{
                element:'span',
                attributes:{
                    innerHTML:mensajeHTML
                }
            },
            icon:'warning',
            buttons:{
                cancel:{
                    text:'Cancelar',
                    value:null,
                    visible:true,
                    className:'btn-light'
                },
                confirm:{
                    text:'Sí, eliminar',
                    value:true,
                    className:'btn-danger',
                    closeModal:false
                }
            },
            dangerMode:true,
            closeOnEsc:true,
            closeOnClickOutside:false
        }).then(function(confirmar){
            if(!confirmar){
                return;
            }

            $.ajax({
                type:'POST',
                url:'<?php echo SERVERURL;?>ajax/eliminarTipoPagoAjax.php',
                data:{
                    tipo_pago_id:row.tipo_pago_id
                },
                dataType:'json',
                beforeSend:function(){
                    if(typeof showLoading === 'function'){
                        showLoading('Eliminando registro...');
                    }
                }
            }).done(function(response){
                swal.close();

                if(response && response.status === 'success'){
                    showNotify('success',response.title || 'Eliminado',response.message || 'El tipo de pago fue eliminado correctamente.');
                    listar_tipo_pago_contabilidad();
                }else{
                    showNotify(
                        'error',
                        response && response.title ? response.title : 'Error',
                        response && response.message ? response.message : 'No se pudo eliminar el tipo de pago.'
                    );
                }
            }).fail(function(xhr){
                swal.close();
                showNotify('error','Error','Ocurrió un error al procesar la solicitud.');
                console.error(xhr.responseText);
            });
        });
    }

    function modalTipoPago(){
        var $form = $('#formConfTipoPago');

        $form.attr({
            'data-form':'save',
            'action':'<?php echo SERVERURL;?>ajax/addTipoPagoAjax.php'
        });

        if($form[0]){
            $form[0].reset();
        }

        $form.find('#pro_tipoPago').val('Registro');
        $('#reg_formTipoPago').show();
        $('#edi_formTipoPago').hide();
        $('#delete_formTipoPago').hide();

        $form.find('#confTipoPago').prop('readonly',false);
        $form.find('#confCuentaTipoPago').prop('disabled',false);
        $form.find('#confTipoPago_activo').prop('disabled',false);
        $form.find('#confTipoCuenta').prop('disabled',false);

        $('#buscar_confCuentaTipoPago').show();
        $('#estado_tipo_pago').hide();

        tipoPagoInicializarSelect2();

        $form.find('#confCuentaTipoPago').val('').trigger('change.select2');
        $form.find('#confTipoCuenta').val('').trigger('change.select2');

        tipoPagoActualizarLabelEstado();

        $('#modalConfTipoPago').modal({
            show:true,
            keyboard:true,
            backdrop:'static'
        });
    }

    window.modalTipoPago = modalTipoPago;

    function getCuentaTipoPago(){
        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/getCuenta.php',
            async:true
        }).done(function(data){
            var $select = $('#formConfTipoPago #confCuentaTipoPago');

            $select.html(data);
            tipoPagoPrepararSelect2(
                $select,
                {
                    placeholder:'Seleccione una cuenta',
                    dropdownParent: $('#modalConfTipoPago').length ? $('#modalConfTipoPago') : $(document.body)
                }
            );
        }).fail(function(xhr){
            showNotify('error','Error al cargar cuentas','No se pudieron cargar las cuentas contables disponibles.');
            console.error(xhr.responseText);
        });
    }

    window.getCuentaTipoPago = getCuentaTipoPago;

    function getTipoCuenta(){
        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/getTipoCuenta.php',
            async:true
        }).done(function(data){
            var $select = $('#formConfTipoPago #confTipoCuenta');

            $select.html(data);
            tipoPagoPrepararSelect2(
                $select,
                {
                    placeholder:'Seleccione un tipo de cuenta',
                    dropdownParent: $('#modalConfTipoPago').length ? $('#modalConfTipoPago') : $(document.body)
                }
            );
        }).fail(function(xhr){
            showNotify('error','Error al cargar tipos de cuenta','No se pudieron cargar los tipos de cuenta disponibles.');
            console.error(xhr.responseText);
        });
    }

    window.getTipoCuenta = getTipoCuenta;

    function tipoPagoActualizarLabelEstado(){
        var activo = $('#formConfTipoPago #confTipoPago_activo').is(':checked');

        $('#formConfTipoPago #label_confTipoPago_activo').html(activo ? 'Activo' : 'Inactivo');
    }

    function tipoPagoLogoPdf(callback){
        if(typeof imagen === 'string' && imagen.indexOf('data:image/') === 0){
            callback(imagen);
            return;
        }

        $.ajax({
            type:'GET',
            url:'<?php echo SERVERURL;?>core/get_image.php',
            dataType:'text',
            timeout:15000
        }).done(function(src){
            src = $.trim(src || '');

            if(!src){
                callback(null);
                return;
            }

            var img = new Image();
            img.crossOrigin = 'Anonymous';

            img.onload = function(){
                try{
                    var canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    canvas.getContext('2d').drawImage(img,0,0);
                    callback(canvas.toDataURL('image/png'));
                }catch(e){
                    callback(null);
                }
            };

            img.onerror = function(){
                callback(null);
            };

            img.src = src;
        }).fail(function(){
            callback(null);
        });
    }

    function tipoPagoExportPdf(){
        var rows = tipoPagoState.filtered || [];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay tipos de pago para exportar.');
            return;
        }

        if(typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function'){
            showNotify('error','PDF no disponible','No están disponibles los componentes necesarios para generar el PDF.');
            return;
        }

        tipoPagoLogoPdf(function(logoData){
            var body = [[
                {text:'NOMBRE',style:'th'},
                {text:'CÓDIGO',style:'th'},
                {text:'CUENTA',style:'th'},
                {text:'ESTADO',style:'th'}
            ]];

            rows.forEach(function(row,i){
                var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
                var activo = parseInt(row.estado,10) === 1;

                body.push([
                    {text:String(row.nombre || ''),style:'td',fillColor:fill},
                    {text:String(row.codigo || ''),style:'td',fillColor:fill},
                    {text:String(row.cuenta || ''),style:'td',fillColor:fill},
                    {text:activo ? 'Activo' : 'Inactivo',style:activo ? 'tdSuccess' : 'tdDanger',fillColor:fill}
                ]);
            });

            body.push([
                {text:'TOTAL DE TIPOS DE PAGO',style:'totalLabel',fillColor:'#EAF1F7',colSpan:3},
                {},
                {},
                {text:String(rows.length),style:'totalValue',fillColor:'#EAF1F7'}
            ]);

            var logoPlate = logoData
                ? {
                    table:{
                        widths:['*'],
                        body:[[
                            {
                                image:logoData,
                                fit:[78,44],
                                alignment:'center',
                                margin:[6,5,6,5],
                                fillColor:'#FFFFFF'
                            }
                        ]]
                    },
                    layout:'noBorders',
                    fillColor:'#17324D',
                    margin:[8,7,8,7]
                }
                : {
                    text:'IZZY',
                    bold:true,
                    fontSize:18,
                    color:'#17324D',
                    alignment:'center',
                    fillColor:'#FFFFFF',
                    margin:[8,14,8,14]
                };

            var doc = {
                pageSize:'LETTER',
                pageOrientation:'landscape',
                pageMargins:[28,28,28,34],

                header:function(){
                    return {
                        margin:[28,12,28,0],
                        canvas:[
                            {
                                type:'line',
                                x1:0,
                                y1:0,
                                x2:736,
                                y2:0,
                                lineWidth:2,
                                lineColor:'#0EA5A8'
                            }
                        ]
                    };
                },

                footer:function(currentPage,pageCount){
                    return {
                        margin:[28,8,28,0],
                        columns:[
                            {
                                text:'IZZY • Tipo de Pago',
                                fontSize:7,
                                color:'#7A869A'
                            },
                            {
                                text:'Página '+currentPage+' de '+pageCount,
                                fontSize:7,
                                color:'#7A869A',
                                alignment:'right'
                            }
                        ]
                    };
                },

                content:[
                    {
                        table:{
                            widths:[100,'*',150],
                            body:[[
                                logoPlate,
                                {
                                    stack:[
                                        {
                                            text:'REPORTE DE TIPOS DE PAGO',
                                            bold:true,
                                            fontSize:16,
                                            color:'#FFFFFF'
                                        },
                                        {
                                            text:'Medios de pago y cuentas contables configuradas',
                                            fontSize:8,
                                            color:'#D8E5F0',
                                            margin:[0,2,0,0]
                                        }
                                    ],
                                    fillColor:'#17324D',
                                    margin:[0,10,0,10]
                                },
                                {
                                    stack:[
                                        {
                                            text:'REPORTE EJECUTIVO',
                                            bold:true,
                                            fontSize:6.5,
                                            color:'#72E2E5',
                                            alignment:'right'
                                        },
                                        {
                                            text:new Date().toLocaleDateString('es-HN'),
                                            bold:true,
                                            fontSize:9,
                                            color:'#FFFFFF',
                                            alignment:'right'
                                        },
                                        {
                                            text:rows.length+' registro(s)',
                                            fontSize:6.5,
                                            color:'#D8E5F0',
                                            alignment:'right'
                                        }
                                    ],
                                    fillColor:'#17324D',
                                    margin:[0,10,12,10]
                                }
                            ]]
                        },
                        layout:'noBorders',
                        margin:[0,0,0,10]
                    },
                    {
                        text:'Estado: '+($('#estado_conf_tipoPagos option:selected').text() || 'Todos')+
                             '   |   Búsqueda: '+(tipoPagoState.search || 'Sin búsqueda'),
                        fontSize:7,
                        color:'#5E6C84',
                        fillColor:'#F7F9FC',
                        margin:[8,7,8,7]
                    },
                    {
                        table:{
                            widths:['*',110,'*',82],
                            headerRows:1,
                            body:body
                        },
                        layout:{
                            hLineColor:function(){return '#DDE3EA';},
                            vLineColor:function(){return '#DDE3EA';},
                            hLineWidth:function(){return .55;},
                            vLineWidth:function(){return .55;},
                            paddingLeft:function(){return 4;},
                            paddingRight:function(){return 4;},
                            paddingTop:function(){return 5;},
                            paddingBottom:function(){return 5;}
                        }
                    }
                ],

                styles:{
                    th:{
                        fontSize:6.5,
                        bold:true,
                        color:'#FFFFFF',
                        fillColor:'#17324D',
                        alignment:'center'
                    },
                    td:{
                        fontSize:7,
                        color:'#253858',
                        noWrap:false
                    },
                    tdSuccess:{
                        fontSize:7,
                        color:'#14804A',
                        bold:true
                    },
                    tdDanger:{
                        fontSize:7,
                        color:'#C9372C',
                        bold:true
                    },
                    totalLabel:{
                        fontSize:7.5,
                        bold:true,
                        color:'#17324D'
                    },
                    totalValue:{
                        fontSize:7.5,
                        bold:true,
                        color:'#17324D',
                        alignment:'right'
                    }
                },

                defaultStyle:{
                    font:'Roboto'
                }
            };

            pdfMake.createPdf(doc).getDataUrl(function(url){
                abrirModalPdfPublico(
                    url,
                    'Reporte de Tipos de Pago',
                    'Reporte_Tipos_Pago.pdf'
                );
            });
        });
    }

    function tipoPagoExcelEscape(v){
        return String(v == null ? '' : v)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    function tipoPagoExcelCell(ref,value,style){
        return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+tipoPagoExcelEscape(value)+'</t></is></c>';
    }

    function tipoPagoExportExcel(){
        var rows = tipoPagoState.filtered || [];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay tipos de pago para exportar.');
            return;
        }

        if(typeof JSZip === 'undefined'){
            showNotify('error','Excel no disponible','JSZip no está disponible.');
            return;
        }

        var sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">'+
                tipoPagoExcelCell('A1','IZZY • TIPOS DE PAGO',1)+
            '</row>'
        );

        sheetRows.push(
            '<row r="2">'+
                tipoPagoExcelCell(
                    'A2',
                    'Estado: '+($('#estado_conf_tipoPagos option:selected').text() || 'Todos')+
                    ' • Registros: '+rows.length+
                    ' • Generado: '+new Date().toLocaleDateString('es-HN'),
                    2
                )+
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">'+
                tipoPagoExcelCell('A4','NOMBRE',3)+
                tipoPagoExcelCell('B4','CÓDIGO',3)+
                tipoPagoExcelCell('C4','CUENTA',3)+
                tipoPagoExcelCell('D4','ESTADO',3)+
            '</row>'
        );

        rows.forEach(function(row,i){
            var r = 5+i;

            sheetRows.push(
                '<row r="'+r+'">'+
                    tipoPagoExcelCell('A'+r,row.nombre || '',4)+
                    tipoPagoExcelCell('B'+r,row.codigo || '',4)+
                    tipoPagoExcelCell('C'+r,row.cuenta || '',4)+
                    tipoPagoExcelCell('D'+r,parseInt(row.estado,10) === 1 ? 'Activo' : 'Inactivo',4)+
                '</row>'
            );
        });

        var totalRow = 5+rows.length;

        sheetRows.push(
            '<row r="'+totalRow+'">'+
                tipoPagoExcelCell('A'+totalRow,'TOTAL DE TIPOS DE PAGO',5)+
                tipoPagoExcelCell('B'+totalRow,'',5)+
                tipoPagoExcelCell('C'+totalRow,'',5)+
                tipoPagoExcelCell('D'+totalRow,String(rows.length),5)+
            '</row>'
        );

        var worksheetXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
                '<dimension ref="A1:D'+totalRow+'"/>'+
                '<sheetViews>'+
                    '<sheetView workbookViewId="0" showGridLines="0">'+
                        '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'+
                    '</sheetView>'+
                '</sheetViews>'+
                '<cols>'+
                    '<col min="1" max="1" width="30" customWidth="1"/>'+
                    '<col min="2" max="2" width="18" customWidth="1"/>'+
                    '<col min="3" max="3" width="40" customWidth="1"/>'+
                    '<col min="4" max="4" width="14" customWidth="1"/>'+
                '</cols>'+
                '<sheetData>'+sheetRows.join('')+'</sheetData>'+
                '<autoFilter ref="A4:D'+(totalRow-1)+'"/>'+
                '<mergeCells count="2">'+
                    '<mergeCell ref="A1:D1"/>'+
                    '<mergeCell ref="A2:D2"/>'+
                '</mergeCells>'+
            '</worksheet>';

        var stylesXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
                '<fonts count="6">'+
                    '<font><sz val="10"/><name val="Calibri"/></font>'+
                    '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'+
                    '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>'+
                    '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'+
                    '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>'+
                    '<font><b/><sz val="10"/><color rgb="FF17324D"/><name val="Calibri"/></font>'+
                '</fonts>'+
                '<fills count="5">'+
                    '<fill><patternFill patternType="none"/></fill>'+
                    '<fill><patternFill patternType="gray125"/></fill>'+
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>'+
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>'+
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill>'+
                '</fills>'+
                '<borders count="2">'+
                    '<border><left/><right/><top/><bottom/><diagonal/></border>'+
                    '<border>'+
                        '<left style="thin"><color rgb="FFDDE3EA"/></left>'+
                        '<right style="thin"><color rgb="FFDDE3EA"/></right>'+
                        '<top style="thin"><color rgb="FFDDE3EA"/></top>'+
                        '<bottom style="thin"><color rgb="FFDDE3EA"/></bottom>'+
                        '<diagonal/>'+
                    '</border>'+
                '</borders>'+
                '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'+
                '<cellXfs count="6">'+
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'+
                    '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/>'+
                    '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>'+
                    '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'+
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'+
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'+
                '</cellXfs>'+
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'+
            '</styleSheet>';

        var workbookXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'+
                '<sheets><sheet name="Tipos de Pago" sheetId="1" r:id="rId1"/></sheets>'+
            '</workbook>';

        var workbookRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'+
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'+
            '</Relationships>';

        var rootRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'+
            '</Relationships>';

        var contentTypes =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'+
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'+
                '<Default Extension="xml" ContentType="application/xml"/>'+
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'+
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'+
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'+
            '</Types>';

        var zip = new JSZip();

        zip.file('[Content_Types].xml',contentTypes);
        zip.folder('_rels').file('.rels',rootRels);
        zip.folder('xl').file('workbook.xml',workbookXml);
        zip.folder('xl').file('styles.xml',stylesXml);
        zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels);
        zip.folder('xl').folder('worksheets').file('sheet1.xml',window.izzyExcelCompletarBordesCombinados(worksheetXml));

        var options = {
            type:'blob',
            mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            compression:'DEFLATE'
        };

        var promise = typeof zip.generateAsync === 'function'
            ? zip.generateAsync(options)
            : Promise.resolve(zip.generate(options));

        promise.then(function(blob){
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');

            a.href = url;
            a.download = 'Reporte_Tipos_Pago.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();

            setTimeout(function(){
                URL.revokeObjectURL(url);
            },1000);
        }).catch(function(error){
            console.error(error);
            showNotify('error','Excel','No se pudo generar el archivo Excel.');
        });
    }

    function tipoPagoToggleSection($btn){
        var target = $btn.data('target');
        var $body = $(target);
        var visible = $body.is(':visible');

        $body.stop(true,true).slideToggle(160);

        $btn.attr('aria-expanded',visible ? 'false' : 'true');
        $btn.find('span').text(visible ? 'Mostrar' : 'Ocultar');
        $btn.find('i')
            .toggleClass('fa-chevron-up',!visible)
            .toggleClass('fa-chevron-down',visible);
    }

    function tipoPagoBindEventos(){
        $(document)
            .off('submit.tipopago','#form_main_conf_tipoPagos')
            .on('submit.tipopago','#form_main_conf_tipoPagos',function(e){
                e.preventDefault();
                tipoPagoState.estado = $('#estado_conf_tipoPagos').val() || '';
                tipoPagoState.page = 1;
                listar_tipo_pago_contabilidad();
            })

            .off('reset.tipopago','#form_main_conf_tipoPagos')
            .on('reset.tipopago','#form_main_conf_tipoPagos',function(){
                window.setTimeout(function(){
                    tipoPagoState.estado = '';
                    $('#estado_conf_tipoPagos').val('').trigger('change.select2');
                    tipoPagoState.page = 1;
                    listar_tipo_pago_contabilidad();
                },0);
            })

            .off('click.tipopago','#btnTipoPagoActualizar')
            .on('click.tipopago','#btnTipoPagoActualizar',listar_tipo_pago_contabilidad)

            .off('click.tipopago','#btnTipoPagoIngresar')
            .on('click.tipopago','#btnTipoPagoIngresar',modalTipoPago)

            .off('click.tipopago','#btnTipoPagoExcel')
            .on('click.tipopago','#btnTipoPagoExcel',tipoPagoExportExcel)

            .off('click.tipopago','#btnTipoPagoPdf')
            .on('click.tipopago','#btnTipoPagoPdf',tipoPagoExportPdf)

            .off('input.tipopago','#tipoPagoBuscar')
            .on('input.tipopago','#tipoPagoBuscar',function(){
                tipoPagoState.search = this.value || '';
                tipoPagoFiltrar();
            })

            .off('click.tipopago','#tipoPagoBuscarLimpiar')
            .on('click.tipopago','#tipoPagoBuscarLimpiar',function(){
                $('#tipoPagoBuscar').val('').focus();
                tipoPagoState.search = '';
                tipoPagoFiltrar();
            })

            .off('change.tipopago','#tipoPagoPageSize')
            .on('change.tipopago','#tipoPagoPageSize',function(){
                var n = parseInt(this.value,10);

                if(!n){
                    return;
                }

                tipoPagoState.pageSize = n;

                if(tipoPagoState.view === 'miniatura'){
                    tipoPagoState.pageSizeMiniatura = n;
                }else{
                    tipoPagoState.pageSizeDetalle = n;
                }

                tipoPagoState.page = 1;
                tipoPagoRender();
            })

            .off('click.tipopago','.tipopago-view-btn')
            .on('click.tipopago','.tipopago-view-btn',function(){
                tipoPagoCambiarVista($(this).data('view'));
            })

            .off('click.tipopago','#tipoPagoPaginacion .tipopago-page-btn')
            .on('click.tipopago','#tipoPagoPaginacion .tipopago-page-btn',function(){
                if(this.disabled){
                    return;
                }

                var page = parseInt($(this).data('page'),10);

                if(page){
                    tipoPagoState.page = page;
                    tipoPagoRender();
                }
            })

            .off('click.tipopago','#tipoPagoListado .js-tipopago-editar')
            .on('click.tipopago','#tipoPagoListado .js-tipopago-editar',function(){
                var idx = parseInt($(this).data('index'),10);
                tipoPagoEditar(tipoPagoState.filtered[idx]);
            })

            .off('click.tipopago','#tipoPagoListado .js-tipopago-eliminar')
            .on('click.tipopago','#tipoPagoListado .js-tipopago-eliminar',function(){
                var idx = parseInt($(this).data('index'),10);
                tipoPagoEliminar(tipoPagoState.filtered[idx]);
            })

            .off('click.tipopago','.tipopago-toggle-section')
            .on('click.tipopago','.tipopago-toggle-section',function(){
                tipoPagoToggleSection($(this));
            })

            .off('change.tipopago','#formConfTipoPago #confTipoPago_activo')
            .on('change.tipopago','#formConfTipoPago #confTipoPago_activo',tipoPagoActualizarLabelEstado);

        $('#modalConfTipoPago')
            .off('shown.bs.modal.tipopago')
            .on('shown.bs.modal.tipopago',function(){
                tipoPagoInicializarSelect2();
            });

        $(window)
            .off('resize.tipopago orientationchange.tipopago')
            .on('resize.tipopago orientationchange.tipopago',function(){
                var target = tipoPagoEsMovil() ? 'miniatura' : tipoPagoState.preferredView;

                if(tipoPagoState.view !== target){
                    tipoPagoState.view = target;
                    tipoPagoState.page = 1;
                    tipoPagoSincronizarPageSize();
                    tipoPagoRender();
                }

                tipoPagoActualizarBotonesVista();
            });
    }

})(jQuery);
</script>
