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

    var BANCOS_STORAGE_VISTA = 'izzy.confBancos.vista';

    var bancosState = {
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
        bancosInicializarVista();
        bancosInicializarSelect2();
        bancosBindEventos();
        listar_banco_contabilidad();
        bancosActualizarLabelEstado();
    });

    function bancosEsc(value){
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function bancosEsMovil(){
        return window.matchMedia
            ? window.matchMedia('(max-width: 767.98px)').matches
            : $(window).width() <= 767;
    }

    function bancosPrepararSelect2($select, options){
        if(!$select.length || typeof $.fn.select2 !== 'function'){
            return;
        }

        window.izzySelect2LimpiarControl($select);

        if($select.hasClass('select2-hidden-accessible')){
            $select.select2('destroy');
        }

        $select.select2($.extend({
            width:'100%',
            minimumResultsForSearch:0,
            allowClear:false,
            language:{
                noResults:function(){return 'No se encontraron resultados';},
                searching:function(){return 'Buscando...';},
                inputTooShort:function(){return 'Escriba para buscar';}
            }
        },options || {}));
    }

    function bancosInicializarSelect2(){
        bancosPrepararSelect2(
            $('#estado_conf_Bancos'),
            {placeholder:'Todos los estados'}
        );
    }

    function bancosInicializarVista(){
        var saved = 'detalle';

        try{
            saved = localStorage.getItem(BANCOS_STORAGE_VISTA) || 'detalle';
        }catch(e){}

        bancosState.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
        bancosState.view = bancosEsMovil() ? 'miniatura' : bancosState.preferredView;

        bancosSincronizarPageSize();
        bancosActualizarBotonesVista();
    }

    function bancosSincronizarPageSize(){
        var mini = bancosState.view === 'miniatura';
        var opciones = mini ? [6,12,18,30] : [10,25,50,100];
        var preferido = mini ? bancosState.pageSizeMiniatura : bancosState.pageSizeDetalle;

        if(opciones.indexOf(preferido) === -1){
            preferido = opciones[0];
        }

        bancosState.pageSize = preferido;

        var $select = $('#bancosPageSize').empty();

        opciones.forEach(function(n){
            $select.append($('<option>').val(n).text(n));
        });

        $select.val(String(preferido));
    }

    function bancosActualizarBotonesVista(){
        var movil = bancosEsMovil();

        if(movil){
            bancosState.view = 'miniatura';
        }

        $('.bancos-view-btn[data-view="detalle"]')
            .toggleClass('d-none',movil)
            .prop('disabled',movil)
            .attr('aria-hidden',movil ? 'true' : 'false');

        $('.bancos-view-btn')
            .removeClass('active')
            .attr('aria-pressed','false');

        $('.bancos-view-btn[data-view="'+bancosState.view+'"]')
            .addClass('active')
            .attr('aria-pressed','true');

        $('#bancosListado')
            .removeClass('vista-detalle vista-miniatura')
            .addClass('vista-'+bancosState.view);

        $('.bancos-detail-header').toggle(bancosState.view === 'detalle' && !movil);
    }

    function bancosCambiarVista(vista){
        if(bancosEsMovil()){
            bancosState.view = 'miniatura';
        }else{
            bancosState.view = vista === 'miniatura' ? 'miniatura' : 'detalle';
            bancosState.preferredView = bancosState.view;

            try{
                localStorage.setItem(BANCOS_STORAGE_VISTA,bancosState.preferredView);
            }catch(e){}
        }

        bancosState.page = 1;
        bancosSincronizarPageSize();
        bancosActualizarBotonesVista();
        bancosRender();
    }

    function bancosNormalizarRespuesta(resp){
        if(Array.isArray(resp)){
            return resp;
        }

        if(resp && Array.isArray(resp.data)){
            return resp.data;
        }

        return [];
    }

    function listar_banco_contabilidad(){
        $('#bancosListado').html(
            '<div class="bancos-empty">'+
                '<i class="fas fa-spinner fa-spin"></i>'+
                '<strong>Cargando bancos</strong>'+
                '<span>Consultando información...</span>'+
            '</div>'
        );

        $.ajax({
            method:'POST',
            url:'<?php echo SERVERURL;?>core/llenarDataTableConfBanco.php',
            data:{
                estado:bancosState.estado
            },
            dataType:'json'
        }).done(function(resp){
            bancosState.rows = bancosNormalizarRespuesta(resp);
            bancosFiltrar();
        }).fail(function(xhr){
            bancosState.rows = [];
            bancosState.filtered = [];
            bancosActualizarKpis();
            bancosRender();

            showNotify(
                'error',
                'Error al cargar bancos',
                'No se pudieron obtener los bancos registrados.'
            );

            console.error(xhr.responseText);
        });
    }

    window.listar_banco_contabilidad = listar_banco_contabilidad;

    function bancosFiltrar(){
        var q = $.trim(bancosState.search || '').toLowerCase();

        bancosState.filtered = !q
            ? bancosState.rows.slice()
            : bancosState.rows.filter(function(row){
                var texto = [
                    row.nombre,
                    parseInt(row.estado,10) === 1 ? 'activo' : 'inactivo',
                    row.banco_id
                ].map(function(v){
                    return String(v == null ? '' : v).toLowerCase();
                }).join(' ');

                return texto.indexOf(q) !== -1;
            });

        bancosState.page = 1;
        bancosActualizarKpis();
        bancosRender();
    }

    function bancosActualizarKpis(){
        var activos = 0;
        var inactivos = 0;

        bancosState.filtered.forEach(function(row){
            if(parseInt(row.estado,10) === 1){
                activos++;
            }else{
                inactivos++;
            }
        });

        $('#bancosKpiRegistros').text(bancosState.filtered.length);
        $('#bancosKpiActivos').text(activos);
        $('#bancosKpiInactivos').text(inactivos);
    }

    function bancosBadgeEstado(row){
        var activo = parseInt(row.estado,10) === 1;

        return '<span class="bancos-status '+(activo ? 'is-active' : 'is-inactive')+'">'+
            '<i class="fas '+(activo ? 'fa-check-circle' : 'fa-times-circle')+'"></i>'+
            '<span>'+(activo ? 'Activo' : 'Inactivo')+'</span>'+
        '</span>';
    }

    function bancosAcciones(row,index){
        return ''+
            '<div class="dropdown acciones-dropdown">'+
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">'+
                    '<i class="fas fa-cog"></i>'+
                    '<span>Acciones</span>'+
                '</button>'+
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
                    '<button type="button" class="dropdown-item accion-item accion-editar js-bancos-editar ocultar" data-index="'+index+'">'+
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>'+
                        '<span class="accion-label">Editar</span>'+
                    '</button>'+
                    '<button type="button" class="dropdown-item accion-item accion-eliminar js-bancos-eliminar ocultar" data-index="'+index+'">'+
                        '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>'+
                        '<span class="accion-label">Eliminar</span>'+
                    '</button>'+
                '</div>'+
            '</div>';
    }

    function bancosRenderDetalle(pageRows,offset){
        var html = '';

        pageRows.forEach(function(row,i){
            var idx = offset+i;

            html +=
                '<article class="bancos-detail-row">'+
                    '<div class="bancos-detail-cell bancos-actions-cell">'+
                        '<span class="bancos-cell-label">Acciones</span>'+
                        bancosAcciones(row,idx)+
                    '</div>'+
                    '<div class="bancos-detail-cell bancos-name-cell">'+
                        '<span class="bancos-cell-label">Banco</span>'+
                        '<span class="bancos-name-icon"><i class="fas fa-university"></i></span>'+
                        '<strong>'+bancosEsc(row.nombre || 'Sin nombre')+'</strong>'+
                    '</div>'+
                    '<div class="bancos-detail-cell">'+
                        '<span class="bancos-cell-label">Estado</span>'+
                        bancosBadgeEstado(row)+
                    '</div>'+
                '</article>';
        });

        return html;
    }

    function bancosRenderMiniatura(pageRows,offset){
        var html = '<div class="bancos-mini-grid">';

        pageRows.forEach(function(row,i){
            var idx = offset+i;

            html +=
                '<article class="bancos-mini-card">'+
                    '<div class="bancos-mini-line"></div>'+
                    '<div class="bancos-mini-head">'+
                        '<span class="bancos-name-icon"><i class="fas fa-university"></i></span>'+
                        '<div class="bancos-mini-title">'+
                            '<span>Banco</span>'+
                            '<h4>'+bancosEsc(row.nombre || 'Sin nombre')+'</h4>'+
                        '</div>'+
                        bancosBadgeEstado(row)+
                    '</div>'+
                    '<div class="bancos-mini-footer">'+
                        bancosAcciones(row,idx)+
                    '</div>'+
                '</article>';
        });

        return html+'</div>';
    }

    function bancosRender(){
        bancosActualizarBotonesVista();

        var rows = bancosState.filtered || [];

        if(!rows.length){
            $('#bancosListado').html(
                '<div class="bancos-empty">'+
                    '<i class="fas fa-university"></i>'+
                    '<strong>Sin bancos</strong>'+
                    '<span>No se encontraron registros con los filtros actuales.</span>'+
                '</div>'
            );

            $('#bancosInfo').text('0 registros');
            $('#bancosPaginacion').empty();
            return;
        }

        var totalPages = Math.max(1,Math.ceil(rows.length/bancosState.pageSize));

        if(bancosState.page > totalPages){
            bancosState.page = totalPages;
        }

        var offset = (bancosState.page-1)*bancosState.pageSize;
        var pageRows = rows.slice(offset,offset+bancosState.pageSize);

        $('#bancosListado').html(
            bancosState.view === 'miniatura'
                ? bancosRenderMiniatura(pageRows,offset)
                : bancosRenderDetalle(pageRows,offset)
        );

        $('#bancosInfo').text(
            'Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros'
        );

        bancosRenderPaginacion(totalPages);

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

    function bancosRenderPaginacion(totalPages){
        var current = bancosState.page;
        var html = '';

        function btn(label,page,disabled,active,icon){
            return '<button type="button" class="bancos-page-btn'+(active ? ' active' : '')+'" data-page="'+page+'" '+(disabled ? 'disabled' : '')+'>'+
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

        $('#bancosPaginacion').html(html);
    }

    function bancosParseEditar(registro){
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

    function bancosEditar(row){
        if(!row || !row.banco_id){
            showNotify('warning','Registro inválido','No se pudo identificar el banco seleccionado.');
            return;
        }

        var $form = $('#formBancos');

        $form.find('#banco_id').val(row.banco_id);

        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/editarBancos.php',
            data:$form.serialize()
        }).done(function(registro){
            var valores = bancosParseEditar(registro);

            if(!valores){
                showNotify('error','Respuesta inválida','No se pudo interpretar la información del banco.');
                return;
            }

            $form.attr({
                'data-form':'update',
                'action':'<?php echo SERVERURL;?>ajax/modificarBankAjax.php'
            });

            if($form[0]){
                $form[0].reset();
            }

            $('#reg_banco').hide();
            $('#edi_banco').show();
            $('#delete_banco').hide();

            $form.find('#pro_bancos').val('Editar');
            $form.find('#confbanco').val(valores[0]);

            $form.find('#confbanco_activo')
                .prop('checked',parseInt(valores[1],10) === 1)
                .prop('disabled',false);

            $form.find('#confbanco').prop('disabled',false);
            $('#estado_bancos').show();

            bancosActualizarLabelEstado();

            $('#modalConfBancos').modal({
                show:true,
                keyboard:true,
                backdrop:'static'
            });
        }).fail(function(xhr){
            showNotify('error','Error al editar','No se pudo cargar el banco seleccionado.');
            console.error(xhr.responseText);
        });
    }

    function bancosEliminar(row){
        if(!row || !row.banco_id){
            showNotify('warning','Registro inválido','No se pudo identificar el banco seleccionado.');
            return;
        }

        var mensajeHTML =
            '¿Desea eliminar permanentemente el banco?<br><br>'+
            '<strong>Nombre:</strong> '+bancosEsc(row.nombre || '');

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
                url:'<?php echo SERVERURL;?>ajax/eliminarBancosAjax.php',
                data:{
                    banco_id:row.banco_id
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
                    showNotify(
                        'success',
                        response.title || 'Eliminado',
                        response.message || 'El banco fue eliminado correctamente.'
                    );

                    listar_banco_contabilidad();
                }else{
                    showNotify(
                        'error',
                        response && response.title ? response.title : 'Error',
                        response && response.message ? response.message : 'No se pudo eliminar el banco.'
                    );
                }
            }).fail(function(xhr){
                swal.close();
                showNotify('error','Error','Ocurrió un error al procesar la solicitud.');
                console.error(xhr.responseText);
            });
        });
    }

    function modalBancos(){
        var $form = $('#formBancos');

        $form.attr({
            'data-form':'save',
            'action':'<?php echo SERVERURL;?>ajax/addBankAjax.php'
        });

        if($form[0]){
            $form[0].reset();
        }

        $form.find('#pro_bancos').val('Registro');

        $('#reg_banco').show();
        $('#edi_banco').hide();
        $('#delete_banco').hide();

        $form.find('#confbanco').prop('readonly',false);
        $form.find('#confbanco_activo').prop('disabled',false);
        $('#estado_bancos').hide();

        bancosActualizarLabelEstado();

        $('#modalConfBancos').modal({
            show:true,
            keyboard:true,
            backdrop:'static'
        });
    }

    window.modalBancos = modalBancos;

    function bancosActualizarLabelEstado(){
        var activo = $('#formBancos #confbanco_activo').is(':checked');

        $('#formBancos #label_confbanco_activo').html(activo ? 'Activo' : 'Inactivo');
    }

    function bancosLogoPdf(callback){
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

    function bancosExportPdf(){
        var rows = bancosState.filtered || [];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay bancos para exportar.');
            return;
        }

        if(typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function'){
            showNotify('error','PDF no disponible','No están disponibles los componentes necesarios para generar el PDF.');
            return;
        }

        bancosLogoPdf(function(logoData){
            var body = [[
                {text:'BANCO',style:'th'},
                {text:'ESTADO',style:'th'}
            ]];

            rows.forEach(function(row,i){
                var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
                var activo = parseInt(row.estado,10) === 1;

                body.push([
                    {text:String(row.nombre || ''),style:'td',fillColor:fill},
                    {text:activo ? 'Activo' : 'Inactivo',style:activo ? 'tdSuccess' : 'tdDanger',fillColor:fill}
                ]);
            });

            body.push([
                {text:'TOTAL DE BANCOS',style:'totalLabel',fillColor:'#EAF1F7'},
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
                                text:'IZZY • Bancos',
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
                                            text:'REPORTE DE BANCOS',
                                            bold:true,
                                            fontSize:16,
                                            color:'#FFFFFF'
                                        },
                                        {
                                            text:'Instituciones bancarias configuradas',
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
                        text:'Estado: '+($('#estado_conf_Bancos option:selected').text() || 'Todos')+
                             '   |   Búsqueda: '+(bancosState.search || 'Sin búsqueda'),
                        fontSize:7,
                        color:'#5E6C84',
                        fillColor:'#F7F9FC',
                        margin:[8,7,8,7]
                    },

                    {
                        table:{
                            widths:['*',120],
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
                    'Reporte de Bancos',
                    'Reporte_Bancos.pdf'
                );
            });
        });
    }

    function bancosExcelEscape(v){
        return String(v == null ? '' : v)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    function bancosExcelCell(ref,value,style){
        return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+bancosExcelEscape(value)+'</t></is></c>';
    }

    function bancosExportExcel(){
        var rows = bancosState.filtered || [];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay bancos para exportar.');
            return;
        }

        if(typeof JSZip === 'undefined'){
            showNotify('error','Excel no disponible','JSZip no está disponible.');
            return;
        }

        var sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">'+
                bancosExcelCell('A1','IZZY • BANCOS',1)+
            '</row>'
        );

        sheetRows.push(
            '<row r="2">'+
                bancosExcelCell(
                    'A2',
                    'Estado: '+($('#estado_conf_Bancos option:selected').text() || 'Todos')+
                    ' • Registros: '+rows.length+
                    ' • Generado: '+new Date().toLocaleDateString('es-HN'),
                    2
                )+
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">'+
                bancosExcelCell('A4','BANCO',3)+
                bancosExcelCell('B4','ESTADO',3)+
            '</row>'
        );

        rows.forEach(function(row,i){
            var r = 5+i;

            sheetRows.push(
                '<row r="'+r+'">'+
                    bancosExcelCell('A'+r,row.nombre || '',4)+
                    bancosExcelCell('B'+r,parseInt(row.estado,10) === 1 ? 'Activo' : 'Inactivo',4)+
                '</row>'
            );
        });

        var totalRow = 5+rows.length;

        sheetRows.push(
            '<row r="'+totalRow+'">'+
                bancosExcelCell('A'+totalRow,'TOTAL DE BANCOS',5)+
                bancosExcelCell('B'+totalRow,String(rows.length),5)+
            '</row>'
        );

        var worksheetXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
                '<dimension ref="A1:B'+totalRow+'"/>'+
                '<sheetViews>'+
                    '<sheetView workbookViewId="0" showGridLines="0">'+
                        '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'+
                    '</sheetView>'+
                '</sheetViews>'+
                '<cols>'+
                    '<col min="1" max="1" width="48" customWidth="1"/>'+
                    '<col min="2" max="2" width="18" customWidth="1"/>'+
                '</cols>'+
                '<sheetData>'+sheetRows.join('')+'</sheetData>'+
                '<autoFilter ref="A4:B'+(totalRow-1)+'"/>'+
                '<mergeCells count="2">'+
                    '<mergeCell ref="A1:B1"/>'+
                    '<mergeCell ref="A2:B2"/>'+
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
                '<sheets><sheet name="Bancos" sheetId="1" r:id="rId1"/></sheets>'+
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
            a.download = 'Reporte_Bancos.xlsx';
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

    function bancosToggleSection($btn){
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

    function bancosBindEventos(){
        $(document)
            .off('submit.bancos','#form_main_Bancos')
            .on('submit.bancos','#form_main_Bancos',function(e){
                e.preventDefault();
                bancosState.estado = $('#estado_conf_Bancos').val() || '';
                bancosState.page = 1;
                listar_banco_contabilidad();
            })

            .off('reset.bancos','#form_main_Bancos')
            .on('reset.bancos','#form_main_Bancos',function(){
                window.setTimeout(function(){
                    bancosState.estado = '';
                    $('#estado_conf_Bancos').val('').trigger('change.select2');
                    bancosState.page = 1;
                    listar_banco_contabilidad();
                },0);
            })

            .off('click.bancos','#btnBancosActualizar')
            .on('click.bancos','#btnBancosActualizar',listar_banco_contabilidad)

            .off('click.bancos','#btnBancosIngresar')
            .on('click.bancos','#btnBancosIngresar',modalBancos)

            .off('click.bancos','#btnBancosExcel')
            .on('click.bancos','#btnBancosExcel',bancosExportExcel)

            .off('click.bancos','#btnBancosPdf')
            .on('click.bancos','#btnBancosPdf',bancosExportPdf)

            .off('input.bancos','#bancosBuscar')
            .on('input.bancos','#bancosBuscar',function(){
                bancosState.search = this.value || '';
                bancosFiltrar();
            })

            .off('click.bancos','#bancosBuscarLimpiar')
            .on('click.bancos','#bancosBuscarLimpiar',function(){
                $('#bancosBuscar').val('').focus();
                bancosState.search = '';
                bancosFiltrar();
            })

            .off('change.bancos','#bancosPageSize')
            .on('change.bancos','#bancosPageSize',function(){
                var n = parseInt(this.value,10);

                if(!n){
                    return;
                }

                bancosState.pageSize = n;

                if(bancosState.view === 'miniatura'){
                    bancosState.pageSizeMiniatura = n;
                }else{
                    bancosState.pageSizeDetalle = n;
                }

                bancosState.page = 1;
                bancosRender();
            })

            .off('click.bancos','.bancos-view-btn')
            .on('click.bancos','.bancos-view-btn',function(){
                bancosCambiarVista($(this).data('view'));
            })

            .off('click.bancos','#bancosPaginacion .bancos-page-btn')
            .on('click.bancos','#bancosPaginacion .bancos-page-btn',function(){
                if(this.disabled){
                    return;
                }

                var page = parseInt($(this).data('page'),10);

                if(page){
                    bancosState.page = page;
                    bancosRender();
                }
            })

            .off('click.bancos','#bancosListado .js-bancos-editar')
            .on('click.bancos','#bancosListado .js-bancos-editar',function(){
                var idx = parseInt($(this).data('index'),10);
                bancosEditar(bancosState.filtered[idx]);
            })

            .off('click.bancos','#bancosListado .js-bancos-eliminar')
            .on('click.bancos','#bancosListado .js-bancos-eliminar',function(){
                var idx = parseInt($(this).data('index'),10);
                bancosEliminar(bancosState.filtered[idx]);
            })

            .off('click.bancos','.bancos-toggle-section')
            .on('click.bancos','.bancos-toggle-section',function(){
                bancosToggleSection($(this));
            })

            .off('change.bancos','#formBancos #confbanco_activo')
            .on('change.bancos','#formBancos #confbanco_activo',bancosActualizarLabelEstado);

        $('#modalConfBancos')
            .off('shown.bs.modal.bancos')
            .on('shown.bs.modal.bancos',function(){
                $(this).find('#formBancos #confbanco').trigger('focus');
            });

        $(window)
            .off('resize.bancos orientationchange.bancos')
            .on('resize.bancos orientationchange.bancos',function(){
                var target = bancosEsMovil() ? 'miniatura' : bancosState.preferredView;

                if(bancosState.view !== target){
                    bancosState.view = target;
                    bancosState.page = 1;
                    bancosSincronizarPageSize();
                    bancosRender();
                }

                bancosActualizarBotonesVista();
            });
    }

})(jQuery);
</script>
