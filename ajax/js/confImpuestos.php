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

(function($){
    'use strict';

    var IMPUESTOS_STORAGE_VISTA = 'izzy.confImpuestos.vista';

    var impuestosState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        search: '',
        tipo: '',
        view: 'detalle',
        preferredView: 'detalle'
    };

    $(document).ready(function(){
        impuestosInicializarVista();
        impuestosInicializarSelect2();
        impuestosBindEventos();
        listar_impuestos_contabilidad();
    });

    function impuestosEsc(v){
        return $('<div>').text(v == null ? '' : String(v)).html();
    }

    function impuestosNumero(v){
        var n = parseFloat(String(v == null ? '' : v).replace('%','').replace(',','.'));
        return isNaN(n) ? 0 : n;
    }

    function impuestosFormato(v){
        return impuestosNumero(v).toFixed(2)+'%';
    }

    function impuestosEsMovil(){
        return window.matchMedia
            ? window.matchMedia('(max-width: 767.98px)').matches
            : $(window).width() <= 767;
    }

    function impuestosPrepararSelect2($select, options){
        if(!$select.length || !$select.is('select') || typeof $.fn.select2 !== 'function'){
            return;
        }

        if($select.hasClass('select2-hidden-accessible')){
            $select.select2('destroy');
        }

        $select.select2($.extend({
            width:'100%',
            minimumResultsForSearch:0,
            allowClear:false,
            language:{
                noResults:function(){return 'No se encontraron resultados';},
                searching:function(){return 'Buscando...';}
            }
        },options || {}));
    }

    function impuestosInicializarSelect2(){
        impuestosPrepararSelect2($('#impuestosFiltroTipo'), {
            placeholder:'Todos los impuestos'
        });

        var $tipoIsv = $('#formImpuestos #tipo_isv');
        var $modal = $('#modalImpuestos');

        if($tipoIsv.is('select')){
            impuestosPrepararSelect2(
                $tipoIsv,
                {
                    placeholder:'Seleccione un impuesto',
                    dropdownParent:$modal.length ? $modal : $(document.body)
                }
            );
        }
    }

    function impuestosInicializarVista(){
        var saved='detalle';

        try{
            saved=localStorage.getItem(IMPUESTOS_STORAGE_VISTA)||'detalle';
        }catch(e){}

        impuestosState.preferredView=saved==='miniatura'?'miniatura':'detalle';
        impuestosState.view=impuestosEsMovil()?'miniatura':impuestosState.preferredView;

        impuestosSincronizarPageSize();
        impuestosActualizarBotonesVista();
    }

    function impuestosSincronizarPageSize(){
        var mini=impuestosState.view==='miniatura';
        var opciones=mini?[6,12,18,30]:[10,25,50,100];
        var preferido=mini?impuestosState.pageSizeMiniatura:impuestosState.pageSizeDetalle;

        if(opciones.indexOf(preferido)===-1){
            preferido=opciones[0];
        }

        impuestosState.pageSize=preferido;

        var $select=$('#impuestosPageSize').empty();

        opciones.forEach(function(n){
            $select.append($('<option>').val(n).text(n));
        });

        $select.val(String(preferido));
    }

    function impuestosActualizarBotonesVista(){
        var movil=impuestosEsMovil();

        if(movil){
            impuestosState.view='miniatura';
        }

        $('.impuestos-view-btn[data-view="detalle"]')
            .toggleClass('d-none',movil)
            .prop('disabled',movil)
            .attr('aria-hidden',movil?'true':'false');

        $('.impuestos-view-btn')
            .removeClass('active')
            .attr('aria-pressed','false');

        $('.impuestos-view-btn[data-view="'+impuestosState.view+'"]')
            .addClass('active')
            .attr('aria-pressed','true');

        $('#impuestosListado')
            .removeClass('vista-detalle vista-miniatura')
            .addClass('vista-'+impuestosState.view);

        $('.impuestos-detail-header').toggle(impuestosState.view==='detalle'&&!movil);
    }

    function impuestosCambiarVista(vista){
        if(impuestosEsMovil()){
            impuestosState.view='miniatura';
        }else{
            impuestosState.view=vista==='miniatura'?'miniatura':'detalle';
            impuestosState.preferredView=impuestosState.view;

            try{
                localStorage.setItem(IMPUESTOS_STORAGE_VISTA,impuestosState.preferredView);
            }catch(e){}
        }

        impuestosState.page=1;
        impuestosSincronizarPageSize();
        impuestosActualizarBotonesVista();
        impuestosRender();
    }

    function impuestosNormalizarRespuesta(resp){
        if(Array.isArray(resp)){
            return resp;
        }

        if(resp && resp.success === false){
            throw new Error(resp.message || 'No se pudieron cargar los impuestos configurados.');
        }

        if(resp && Array.isArray(resp.data)){
            return resp.data;
        }

        throw new Error('La respuesta de impuestos no contiene un arreglo de datos válido.');
    }

    function impuestosCargarFiltro(){
        var current=$('#impuestosFiltroTipo').val()||'';
        var tipos={};

        impuestosState.rows.forEach(function(row){
            var nombre=$.trim(String(row.tipo_isv_nombre==null?'':row.tipo_isv_nombre));
            if(nombre){
                tipos[nombre]=true;
            }
        });

        var $select=$('#impuestosFiltroTipo').empty();
        $select.append($('<option>').val('').text('Todos'));

        Object.keys(tipos).sort(function(a,b){
            return a.localeCompare(b,'es',{sensitivity:'base'});
        }).forEach(function(nombre){
            $select.append($('<option>').val(nombre).text(nombre));
        });

        if(current && tipos[current]){
            $select.val(current);
        }

        impuestosInicializarSelect2();

        if(current && tipos[current]){
            $select.val(current).trigger('change.select2');
        }
    }

    function listar_impuestos_contabilidad(){
        $('#impuestosListado').html(
            '<div class="impuestos-empty">'+
                '<i class="fas fa-spinner fa-spin"></i>'+
                '<strong>Cargando impuestos</strong>'+
                '<span>Consultando información...</span>'+
            '</div>'
        );

        $.ajax({
            method:'POST',
            url:'<?php echo SERVERURL;?>core/llenarDataTableConfImpuestos.php',
            dataType:'json',
            cache:false
        }).done(function(resp){
            try{
                impuestosState.rows=impuestosNormalizarRespuesta(resp);
                impuestosCargarFiltro();
                impuestosFiltrar();
            }catch(error){
                impuestosState.rows=[];
                impuestosState.filtered=[];
                impuestosActualizarKpis();
                impuestosRender();

                showNotify(
                    'error',
                    'Error al cargar impuestos',
                    error && error.message
                        ? error.message
                        : 'No se pudieron obtener los impuestos configurados.'
                );

                console.error('Respuesta inválida de impuestos:', resp, error);
            }
        }).fail(function(xhr){
            impuestosState.rows=[];
            impuestosState.filtered=[];
            impuestosActualizarKpis();
            impuestosRender();

            var mensaje='No se pudieron obtener los impuestos configurados.';

            if(xhr && xhr.responseJSON && xhr.responseJSON.message){
                mensaje=xhr.responseJSON.message;
            }

            showNotify(
                'error',
                'Error al cargar impuestos',
                mensaje
            );

            console.error('Error cargando impuestos:', xhr ? xhr.responseText : xhr);
        });
    }

    window.listar_impuestos_contabilidad=listar_impuestos_contabilidad;

    function impuestosFiltrar(){
        var q=$.trim(impuestosState.search||'').toLowerCase();
        var tipo=$.trim(impuestosState.tipo||'').toLowerCase();

        impuestosState.filtered=impuestosState.rows.filter(function(row){
            var nombre=$.trim(String(row.tipo_isv_nombre==null?'':row.tipo_isv_nombre));

            if(tipo && nombre.toLowerCase()!==tipo){
                return false;
            }

            if(!q){
                return true;
            }

            var texto=[
                row.tipo_isv_nombre,
                row.valor,
                row.isv_id
            ].map(function(v){
                return String(v==null?'':v).toLowerCase();
            }).join(' ');

            return texto.indexOf(q)!==-1;
        });

        impuestosState.page=1;
        impuestosActualizarKpis();
        impuestosRender();
    }

    function impuestosActualizarKpis(){
        var rows=impuestosState.filtered||[];
        var valores=rows.map(function(row){return impuestosNumero(row.valor);});
        var suma=valores.reduce(function(a,b){return a+b;},0);

        $('#impuestosKpiRegistros').text(rows.length);
        $('#impuestosKpiPromedio').text(rows.length?(suma/rows.length).toFixed(2)+'%':'0.00%');
        $('#impuestosKpiMaximo').text(rows.length?Math.max.apply(null,valores).toFixed(2)+'%':'0.00%');
        $('#impuestosKpiMinimo').text(rows.length?Math.min.apply(null,valores).toFixed(2)+'%':'0.00%');
    }

    function impuestosAcciones(row,index){
        return ''+
            '<div class="dropdown acciones-dropdown">'+
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">'+
                    '<i class="fas fa-cog"></i>'+
                    '<span>Acciones</span>'+
                '</button>'+
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
                    '<button type="button" class="dropdown-item accion-item accion-editar js-impuestos-editar ocultar" data-index="'+index+'">'+
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>'+
                        '<span class="accion-label">Editar</span>'+
                    '</button>'+
                '</div>'+
            '</div>';
    }

    function impuestosRenderDetalle(pageRows,offset){
        var html='';

        pageRows.forEach(function(row,i){
            var idx=offset+i;

            html+=
                '<article class="impuestos-detail-row">'+
                    '<div class="impuestos-detail-cell impuestos-actions-cell">'+
                        '<span class="impuestos-cell-label">Acciones</span>'+
                        impuestosAcciones(row,idx)+
                    '</div>'+
                    '<div class="impuestos-detail-cell impuestos-name-cell">'+
                        '<span class="impuestos-cell-label">Impuesto</span>'+
                        '<span class="impuestos-name-icon"><i class="fas fa-percentage"></i></span>'+
                        '<strong>'+impuestosEsc(row.tipo_isv_nombre||'Sin impuesto')+'</strong>'+
                    '</div>'+
                    '<div class="impuestos-detail-cell">'+
                        '<span class="impuestos-cell-label">Valor</span>'+
                        '<strong class="impuestos-value">'+impuestosFormato(row.valor)+'</strong>'+
                    '</div>'+
                '</article>';
        });

        return html;
    }

    function impuestosRenderMiniatura(pageRows,offset){
        var html='<div class="impuestos-mini-grid">';

        pageRows.forEach(function(row,i){
            var idx=offset+i;

            html+=
                '<article class="impuestos-mini-card">'+
                    '<div class="impuestos-mini-line"></div>'+
                    '<div class="impuestos-mini-head">'+
                        '<span class="impuestos-name-icon"><i class="fas fa-percentage"></i></span>'+
                        '<div class="impuestos-mini-title">'+
                            '<span>Impuesto</span>'+
                            '<h4>'+impuestosEsc(row.tipo_isv_nombre||'Sin impuesto')+'</h4>'+
                        '</div>'+
                        '<span class="impuestos-mini-value">'+impuestosFormato(row.valor)+'</span>'+
                    '</div>'+
                    '<div class="impuestos-mini-footer">'+
                        impuestosAcciones(row,idx)+
                    '</div>'+
                '</article>';
        });

        return html+'</div>';
    }

    function impuestosRender(){
        impuestosActualizarBotonesVista();

        var rows=impuestosState.filtered||[];

        if(!rows.length){
            $('#impuestosListado').html(
                '<div class="impuestos-empty">'+
                    '<i class="fas fa-percentage"></i>'+
                    '<strong>Sin impuestos</strong>'+
                    '<span>No se encontraron registros con los filtros actuales.</span>'+
                '</div>'
            );

            $('#impuestosInfo').text('0 registros');
            $('#impuestosPaginacion').empty();
            return;
        }

        var totalPages=Math.max(1,Math.ceil(rows.length/impuestosState.pageSize));

        if(impuestosState.page>totalPages){
            impuestosState.page=totalPages;
        }

        var offset=(impuestosState.page-1)*impuestosState.pageSize;
        var pageRows=rows.slice(offset,offset+impuestosState.pageSize);

        $('#impuestosListado').html(
            impuestosState.view==='miniatura'
                ? impuestosRenderMiniatura(pageRows,offset)
                : impuestosRenderDetalle(pageRows,offset)
        );

        $('#impuestosInfo').text(
            'Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros'
        );

        impuestosRenderPaginacion(totalPages);

        if(typeof getPermisosTipoUsuarioAccesosTable==='function' &&
           typeof getPrivilegioTipoUsuario==='function'){
            try{
                getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
            }catch(e){}
        }

        if(typeof cerrarDropdownAcciones==='function'){
            cerrarDropdownAcciones();
        }
    }

    function impuestosRenderPaginacion(totalPages){
        var current=impuestosState.page;
        var html='';

        function btn(label,page,disabled,active,icon){
            return '<button type="button" class="impuestos-page-btn'+(active?' active':'')+'" data-page="'+page+'" '+(disabled?'disabled':'')+'>'+
                (icon?'<i class="'+icon+'"></i>':'')+
                '<span>'+label+'</span>'+
            '</button>';
        }

        html+=btn('Inicio',1,current===1,false,'fas fa-angle-double-left');
        html+=btn('Anterior',current-1,current===1,false,'fas fa-angle-left');

        var from=Math.max(1,current-2);
        var to=Math.min(totalPages,from+4);
        from=Math.max(1,to-4);

        for(var p=from;p<=to;p++){
            html+=btn(String(p),p,false,p===current,'');
        }

        html+=btn('Siguiente',current+1,current===totalPages,false,'fas fa-angle-right');
        html+=btn('Final',totalPages,current===totalPages,false,'fas fa-angle-double-right');

        $('#impuestosPaginacion').html(html);
    }

    function impuestosParseEditar(registro){
        if(Array.isArray(registro)){
            return registro;
        }

        if(typeof registro==='string'){
            try{
                var json=JSON.parse(registro);
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

    function impuestosEditar(row){
        if(!row || !row.isv_id){
            showNotify('warning','Registro inválido','No se pudo identificar el impuesto seleccionado.');
            return;
        }

        var $form=$('#formImpuestos');

        $form.find('#isv_id').val(row.isv_id);

        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/editarImpuestos.php',
            data:$form.serialize()
        }).done(function(registro){
            var valores=impuestosParseEditar(registro);

            if(!valores){
                showNotify('error','Respuesta inválida','No se pudo interpretar la información del impuesto.');
                return;
            }

            $form.attr({
                'data-form':'update',
                'action':'<?php echo SERVERURL;?>ajax/modificarImpuestos.php'
            });

            if($form[0]){
                $form[0].reset();
            }

            $('#reg_catProd').hide();
            $('#edi_catProd').show();
            $('#delete_catProd').hide();

            $form.find('#pro_impuestos').val('Editar');
            $form.find('#tipo_isv').val(valores[1]);
            $form.find('#valor').val(valores[3]);

            impuestosInicializarSelect2();

            var $tipoIsv = $form.find('#tipo_isv');
            $tipoIsv.val(valores[1]);

            if($tipoIsv.is('select')){
                $tipoIsv.trigger('change.select2');
            }

            $('#modalImpuestos').modal({
                show:true,
                keyboard:true,
                backdrop:'static'
            });
        }).fail(function(xhr){
            showNotify('error','Error al editar','No se pudo cargar el impuesto seleccionado.');
            console.error(xhr.responseText);
        });
    }

    function impuestosLogoPdf(callback){
        if(typeof imagen==='string' && imagen.indexOf('data:image/')===0){
            callback(imagen);
            return;
        }

        $.ajax({
            type:'GET',
            url:'<?php echo SERVERURL;?>core/get_image.php',
            dataType:'text',
            timeout:15000
        }).done(function(src){
            src=$.trim(src||'');

            if(!src){
                callback(null);
                return;
            }

            var img=new Image();
            img.crossOrigin='Anonymous';

            img.onload=function(){
                try{
                    var canvas=document.createElement('canvas');
                    canvas.width=img.naturalWidth||img.width;
                    canvas.height=img.naturalHeight||img.height;
                    canvas.getContext('2d').drawImage(img,0,0);
                    callback(canvas.toDataURL('image/png'));
                }catch(e){
                    callback(null);
                }
            };

            img.onerror=function(){callback(null);};
            img.src=src;
        }).fail(function(){
            callback(null);
        });
    }

    function impuestosExportPdf(){
        var rows=impuestosState.filtered||[];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay impuestos para exportar.');
            return;
        }

        if(typeof pdfMake==='undefined' || typeof abrirModalPdfPublico!=='function'){
            showNotify('error','PDF no disponible','No están disponibles los componentes necesarios para generar el PDF.');
            return;
        }

        impuestosLogoPdf(function(logoData){
            var body=[[
                {text:'IMPUESTO',style:'th'},
                {text:'VALOR',style:'th'}
            ]];

            rows.forEach(function(row,i){
                var fill=i%2===0?'#FFFFFF':'#F7F9FC';

                body.push([
                    {text:String(row.tipo_isv_nombre||''),style:'td',fillColor:fill},
                    {text:impuestosFormato(row.valor),style:'tdNumber',fillColor:fill}
                ]);
            });

            body.push([
                {text:'TOTAL DE IMPUESTOS',style:'totalLabel',fillColor:'#EAF1F7'},
                {text:String(rows.length),style:'totalValue',fillColor:'#EAF1F7'}
            ]);

            var logoPlate=logoData
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

            var doc={
                pageSize:'LETTER',
                pageOrientation:'landscape',
                pageMargins:[28,28,28,34],

                header:function(){
                    return {
                        margin:[28,12,28,0],
                        canvas:[{
                            type:'line',
                            x1:0,y1:0,x2:736,y2:0,
                            lineWidth:2,
                            lineColor:'#0EA5A8'
                        }]
                    };
                },

                footer:function(currentPage,pageCount){
                    return {
                        margin:[28,8,28,0],
                        columns:[
                            {
                                text:'IZZY • Impuestos',
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
                                            text:'REPORTE DE IMPUESTOS',
                                            bold:true,
                                            fontSize:16,
                                            color:'#FFFFFF'
                                        },
                                        {
                                            text:'Tipos de impuesto y valores configurados',
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
                        text:'Impuesto: '+($('#impuestosFiltroTipo option:selected').text()||'Todos')+
                             '   |   Búsqueda: '+(impuestosState.search||'Sin búsqueda'),
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
                    th:{fontSize:6.5,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},
                    td:{fontSize:7,color:'#253858',noWrap:false},
                    tdNumber:{fontSize:7,color:'#253858',alignment:'right'},
                    totalLabel:{fontSize:7.5,bold:true,color:'#17324D'},
                    totalValue:{fontSize:7.5,bold:true,color:'#17324D',alignment:'right'}
                },

                defaultStyle:{font:'Roboto'}
            };

            pdfMake.createPdf(doc).getDataUrl(function(url){
                abrirModalPdfPublico(
                    url,
                    'Reporte de Impuestos',
                    'Reporte_Impuestos.pdf'
                );
            });
        });
    }

    function impuestosExcelEscape(v){
        return String(v==null?'':v)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    function impuestosExcelCell(ref,value,style){
        return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+impuestosExcelEscape(value)+'</t></is></c>';
    }

    function impuestosExportExcel(){
        var rows=impuestosState.filtered||[];

        if(!rows.length){
            showNotify('warning','Sin datos','No hay impuestos para exportar.');
            return;
        }

        if(typeof JSZip==='undefined'){
            showNotify('error','Excel no disponible','JSZip no está disponible.');
            return;
        }

        var sheetRows=[];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">'+
                impuestosExcelCell('A1','IZZY • IMPUESTOS',1)+
            '</row>'
        );

        sheetRows.push(
            '<row r="2">'+
                impuestosExcelCell(
                    'A2',
                    'Impuesto: '+($('#impuestosFiltroTipo option:selected').text()||'Todos')+
                    ' • Registros: '+rows.length+
                    ' • Generado: '+new Date().toLocaleDateString('es-HN'),
                    2
                )+
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">'+
                impuestosExcelCell('A4','IMPUESTO',3)+
                impuestosExcelCell('B4','VALOR',3)+
            '</row>'
        );

        rows.forEach(function(row,i){
            var r=5+i;

            sheetRows.push(
                '<row r="'+r+'">'+
                    impuestosExcelCell('A'+r,row.tipo_isv_nombre||'',4)+
                    impuestosExcelCell('B'+r,impuestosFormato(row.valor),4)+
                '</row>'
            );
        });

        var totalRow=5+rows.length;

        sheetRows.push(
            '<row r="'+totalRow+'">'+
                impuestosExcelCell('A'+totalRow,'TOTAL DE IMPUESTOS',5)+
                impuestosExcelCell('B'+totalRow,String(rows.length),5)+
            '</row>'
        );

        var worksheetXml=
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
                '<dimension ref="A1:B'+totalRow+'"/>'+
                '<sheetViews>'+
                    '<sheetView workbookViewId="0" showGridLines="0">'+
                        '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>'+
                    '</sheetView>'+
                '</sheetViews>'+
                '<cols>'+
                    '<col min="1" max="1" width="44" customWidth="1"/>'+
                    '<col min="2" max="2" width="18" customWidth="1"/>'+
                '</cols>'+
                '<sheetData>'+sheetRows.join('')+'</sheetData>'+
                '<autoFilter ref="A4:B'+(totalRow-1)+'"/>'+
                '<mergeCells count="2">'+
                    '<mergeCell ref="A1:B1"/>'+
                    '<mergeCell ref="A2:B2"/>'+
                '</mergeCells>'+
            '</worksheet>';

        var stylesXml=
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

        var workbookXml=
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'+
                '<sheets><sheet name="Impuestos" sheetId="1" r:id="rId1"/></sheets>'+
            '</workbook>';

        var workbookRels=
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'+
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'+
            '</Relationships>';

        var rootRels=
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'+
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'+
            '</Relationships>';

        var contentTypes=
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'+
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'+
                '<Default Extension="xml" ContentType="application/xml"/>'+
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'+
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'+
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'+
            '</Types>';

        var zip=new JSZip();

        zip.file('[Content_Types].xml',contentTypes);
        zip.folder('_rels').file('.rels',rootRels);
        zip.folder('xl').file('workbook.xml',workbookXml);
        zip.folder('xl').file('styles.xml',stylesXml);
        zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels);
        zip.folder('xl').folder('worksheets').file('sheet1.xml',window.izzyExcelCompletarBordesCombinados(worksheetXml));

        var options={
            type:'blob',
            mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            compression:'DEFLATE'
        };

        var promise=typeof zip.generateAsync==='function'
            ? zip.generateAsync(options)
            : Promise.resolve(zip.generate(options));

        promise.then(function(blob){
            var url=URL.createObjectURL(blob);
            var a=document.createElement('a');

            a.href=url;
            a.download='Reporte_Impuestos.xlsx';
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

    function impuestosToggleSection($btn){
        var target=$btn.data('target');
        var $body=$(target);
        var visible=$body.is(':visible');

        $body.stop(true,true).slideToggle(160);

        $btn.attr('aria-expanded',visible?'false':'true');
        $btn.find('span').text(visible?'Mostrar':'Ocultar');
        $btn.find('i')
            .toggleClass('fa-chevron-up',!visible)
            .toggleClass('fa-chevron-down',visible);
    }

    function impuestosBindEventos(){
        $(document)
            .off('submit.impuestos','#form_main_Impuestos')
            .on('submit.impuestos','#form_main_Impuestos',function(e){
                e.preventDefault();
                impuestosState.tipo=$('#impuestosFiltroTipo').val()||'';
                impuestosFiltrar();
            })

            .off('reset.impuestos','#form_main_Impuestos')
            .on('reset.impuestos','#form_main_Impuestos',function(){
                window.setTimeout(function(){
                    impuestosState.tipo='';
                    $('#impuestosFiltroTipo').val('').trigger('change.select2');
                    impuestosFiltrar();
                },0);
            })

            .off('click.impuestos','#btnImpuestosActualizar')
            .on('click.impuestos','#btnImpuestosActualizar',listar_impuestos_contabilidad)

            .off('click.impuestos','#btnImpuestosExcel')
            .on('click.impuestos','#btnImpuestosExcel',impuestosExportExcel)

            .off('click.impuestos','#btnImpuestosPdf')
            .on('click.impuestos','#btnImpuestosPdf',impuestosExportPdf)

            .off('input.impuestos','#impuestosBuscar')
            .on('input.impuestos','#impuestosBuscar',function(){
                impuestosState.search=this.value||'';
                impuestosFiltrar();
            })

            .off('click.impuestos','#impuestosBuscarLimpiar')
            .on('click.impuestos','#impuestosBuscarLimpiar',function(){
                $('#impuestosBuscar').val('').focus();
                impuestosState.search='';
                impuestosFiltrar();
            })

            .off('change.impuestos','#impuestosPageSize')
            .on('change.impuestos','#impuestosPageSize',function(){
                var n=parseInt(this.value,10);

                if(!n){
                    return;
                }

                impuestosState.pageSize=n;

                if(impuestosState.view==='miniatura'){
                    impuestosState.pageSizeMiniatura=n;
                }else{
                    impuestosState.pageSizeDetalle=n;
                }

                impuestosState.page=1;
                impuestosRender();
            })

            .off('click.impuestos','.impuestos-view-btn')
            .on('click.impuestos','.impuestos-view-btn',function(){
                impuestosCambiarVista($(this).data('view'));
            })

            .off('click.impuestos','#impuestosPaginacion .impuestos-page-btn')
            .on('click.impuestos','#impuestosPaginacion .impuestos-page-btn',function(){
                if(this.disabled){
                    return;
                }

                var page=parseInt($(this).data('page'),10);

                if(page){
                    impuestosState.page=page;
                    impuestosRender();
                }
            })

            .off('click.impuestos','#impuestosListado .js-impuestos-editar')
            .on('click.impuestos','#impuestosListado .js-impuestos-editar',function(){
                var idx=parseInt($(this).data('index'),10);
                impuestosEditar(impuestosState.filtered[idx]);
            })

            .off('click.impuestos','.impuestos-toggle-section')
            .on('click.impuestos','.impuestos-toggle-section',function(){
                impuestosToggleSection($(this));
            });

        $('#modalImpuestos')
            .off('shown.bs.modal.impuestos')
            .on('shown.bs.modal.impuestos',function(){
                impuestosInicializarSelect2();
                $(this).find('#formImpuestos #valor').trigger('focus');
            });

        $(window)
            .off('resize.impuestos orientationchange.impuestos')
            .on('resize.impuestos orientationchange.impuestos',function(){
                var target=impuestosEsMovil()?'miniatura':impuestosState.preferredView;

                if(impuestosState.view!==target){
                    impuestosState.view=target;
                    impuestosState.page=1;
                    impuestosSincronizarPageSize();
                    impuestosRender();
                }

                impuestosActualizarBotonesVista();
            });
    }

})(jQuery);
</script>
