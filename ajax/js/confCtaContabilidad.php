<script>
(function($){
    'use strict';

    var CONFCTA_STORAGE_VISTA = 'izzy.confCtaContabilidad.vista';

    var confCtaState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        search: '',
        filtroEntidad: '',
        filtroCuenta: '',
        view: 'detalle',
        preferredView: 'detalle'
    };

    $(document).ready(function(){
        confCtaInicializarVista();
        confCtaInicializarSelect2();
        confCtaBindEventos();
        listar_diarios_configuracion();
        getCuentaDiarios();
    });

    function confCtaEsc(value){
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function confCtaEsMovil(){
        return window.matchMedia
            ? window.matchMedia('(max-width: 767.98px)').matches
            : $(window).width() <= 767;
    }

    function confCtaInicializarSelect2(){
        if(typeof $.fn.select2 !== 'function'){
            console.warn('Select2 no está disponible. Los filtros continuarán como select nativo.');
            return;
        }

        $('.confcta-select2').each(function(){
            var $select = $(this);

            if($select.hasClass('select2-hidden-accessible')){
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Seleccione',
                allowClear: false,
                minimumResultsForSearch: 0,
                language: {
                    noResults: function(){ return 'No se encontraron resultados'; },
                    searching: function(){ return 'Buscando...'; },
                    inputTooShort: function(){ return 'Escriba para buscar'; }
                }
            });
        });
    }

    function confCtaInicializarVista(){
        var saved = 'detalle';

        try{
            saved = localStorage.getItem(CONFCTA_STORAGE_VISTA) || 'detalle';
        }catch(e){}

        confCtaState.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
        confCtaState.view = confCtaEsMovil() ? 'miniatura' : confCtaState.preferredView;

        confCtaSincronizarPageSize();
        confCtaActualizarBotonesVista();
    }

    function confCtaSincronizarPageSize(){
        var mini = confCtaState.view === 'miniatura';
        var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
        var preferido = mini ? confCtaState.pageSizeMiniatura : confCtaState.pageSizeDetalle;

        if(opciones.indexOf(preferido) === -1){
            preferido = opciones[0];
        }

        confCtaState.pageSize = preferido;

        var $select = $('#confCtaPageSize').empty();

        opciones.forEach(function(n){
            $select.append($('<option>').val(n).text(n));
        });

        $select.val(String(preferido));
    }

    function confCtaActualizarBotonesVista(){
        var movil = confCtaEsMovil();

        if(movil){
            confCtaState.view = 'miniatura';
        }

        $('.confcta-view-btn[data-view="detalle"]')
            .toggleClass('d-none', movil)
            .prop('disabled', movil)
            .attr('aria-hidden', movil ? 'true' : 'false');

        $('.confcta-view-btn')
            .removeClass('active')
            .attr('aria-pressed', 'false');

        $('.confcta-view-btn[data-view="'+confCtaState.view+'"]')
            .addClass('active')
            .attr('aria-pressed', 'true');

        $('#confCtaListado')
            .removeClass('vista-detalle vista-miniatura')
            .addClass('vista-'+confCtaState.view);

        $('.confcta-detail-header').toggle(confCtaState.view === 'detalle' && !movil);
    }

    function confCtaCambiarVista(vista){
        if(confCtaEsMovil()){
            confCtaState.view = 'miniatura';
        }else{
            confCtaState.view = vista === 'miniatura' ? 'miniatura' : 'detalle';
            confCtaState.preferredView = confCtaState.view;

            try{
                localStorage.setItem(CONFCTA_STORAGE_VISTA, confCtaState.preferredView);
            }catch(e){}
        }

        confCtaState.page = 1;
        confCtaSincronizarPageSize();
        confCtaActualizarBotonesVista();
        confCtaRender();
    }

    function confCtaNormalizarRespuesta(resp){
        if(Array.isArray(resp)){
            return resp;
        }

        if(resp && Array.isArray(resp.data)){
            return resp.data;
        }

        return [];
    }

    function listar_diarios_configuracion(){
        $('#confCtaListado').html(
            '<div class="confcta-empty">'+
                '<i class="fas fa-spinner fa-spin"></i>'+
                '<strong>Cargando configuraciones</strong>'+
                '<span>Consultando información...</span>'+
            '</div>'
        );

        $.ajax({
            method: 'POST',
            url: '<?php echo SERVERURL;?>core/llenarDataTableDiarios.php',
            dataType: 'json'
        }).done(function(resp){
            confCtaState.rows = confCtaNormalizarRespuesta(resp);
            confCtaCargarOpcionesFiltros();
            confCtaFiltrar();
        }).fail(function(xhr){
            confCtaState.rows = [];
            confCtaState.filtered = [];
            confCtaActualizarKpis();
            confCtaRender();

            showNotify(
                'error',
                'Error al cargar configuraciones',
                'No se pudo obtener la configuración de cuentas contables.'
            );

            console.error(xhr.responseText);
        });
    }

    window.listar_diarios_configuracion = listar_diarios_configuracion;

    function confCtaCargarOpcionesFiltros(){
        var entidades = {};
        var cuentas = {};

        confCtaState.rows.forEach(function(row){
            var entidad = $.trim(String(row.diario == null ? '' : row.diario));
            var cuenta = $.trim(String(row.cuenta == null ? '' : row.cuenta));

            if(entidad){
                entidades[entidad] = true;
            }

            if(cuenta){
                cuentas[cuenta] = true;
            }
        });

        var entidadActual = $('#confCtaFiltroEntidad').val() || '';
        var cuentaActual = $('#confCtaFiltroCuenta').val() || '';

        var $entidad = $('#confCtaFiltroEntidad').empty().append(
            $('<option>').val('').text('Todas')
        );

        Object.keys(entidades).sort(function(a,b){
            return a.localeCompare(b, 'es', {sensitivity:'base'});
        }).forEach(function(nombre){
            $entidad.append($('<option>').val(nombre).text(nombre));
        });

        var $cuenta = $('#confCtaFiltroCuenta').empty().append(
            $('<option>').val('').text('Todas')
        );

        Object.keys(cuentas).sort(function(a,b){
            return a.localeCompare(b, 'es', {sensitivity:'base'});
        }).forEach(function(nombre){
            $cuenta.append($('<option>').val(nombre).text(nombre));
        });

        if($entidad.find('option[value="'+entidadActual.replace(/"/g,'\\"')+'"]').length){
            $entidad.val(entidadActual);
        }

        if($cuenta.find('option[value="'+cuentaActual.replace(/"/g,'\\"')+'"]').length){
            $cuenta.val(cuentaActual);
        }

        confCtaInicializarSelect2();

        if(entidadActual){
            $entidad.val(entidadActual).trigger('change.select2');
        }

        if(cuentaActual){
            $cuenta.val(cuentaActual).trigger('change.select2');
        }
    }

    function confCtaFiltrar(){
        var q = $.trim(confCtaState.search || '').toLowerCase();
        var entidad = $.trim(confCtaState.filtroEntidad || '').toLowerCase();
        var cuenta = $.trim(confCtaState.filtroCuenta || '').toLowerCase();

        confCtaState.filtered = confCtaState.rows.filter(function(row){
            var rowEntidad = $.trim(String(row.diario == null ? '' : row.diario));
            var rowCuenta = $.trim(String(row.cuenta == null ? '' : row.cuenta));

            if(entidad && rowEntidad.toLowerCase() !== entidad){
                return false;
            }

            if(cuenta && rowCuenta.toLowerCase() !== cuenta){
                return false;
            }

            if(!q){
                return true;
            }

            var texto = [
                row.diario,
                row.cuenta,
                row.diarios_id
            ].map(function(v){
                return String(v == null ? '' : v).toLowerCase();
            }).join(' ');

            return texto.indexOf(q) !== -1;
        });

        confCtaState.page = 1;
        confCtaActualizarKpis();
        confCtaRender();
    }

    function confCtaActualizarKpis(){
        var total = confCtaState.filtered.length;
        var configuradas = 0;
        var cuentas = {};

        confCtaState.filtered.forEach(function(row){
            var cuenta = $.trim(String(row.cuenta == null ? '' : row.cuenta));

            if(cuenta && cuenta.toLowerCase() !== 'sin cuenta' && cuenta !== '0'){
                configuradas++;
                cuentas[cuenta.toLowerCase()] = true;
            }
        });

        $('#confCtaKpiEntidades').text(total);
        $('#confCtaKpiConfiguradas').text(configuradas);
        $('#confCtaKpiCuentas').text(Object.keys(cuentas).length);
    }

    function confCtaAcciones(row, index){
        return '' +
            '<div class="dropdown acciones-dropdown">' +
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                    '<i class="fas fa-cog"></i>' +
                    '<span>Acciones</span>' +
                '</button>' +
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                    '<button type="button" class="dropdown-item accion-item accion-editar js-confcta-editar ocultar" data-index="'+index+'">' +
                        '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                        '<span class="accion-label">Editar</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
    }

    function confCtaRenderDetalle(pageRows, offset){
        var html = '';

        pageRows.forEach(function(row, i){
            var idx = offset + i;

            html +=
                '<article class="confcta-detail-row">' +
                    '<div class="confcta-detail-cell confcta-actions-cell">' +
                        '<span class="confcta-cell-label">Acciones</span>' +
                        confCtaAcciones(row, idx) +
                    '</div>' +
                    '<div class="confcta-detail-cell confcta-entity-cell">' +
                        '<span class="confcta-cell-label">Entidad</span>' +
                        '<span class="confcta-entity-icon"><i class="fas fa-sitemap"></i></span>' +
                        '<strong>'+confCtaEsc(row.diario || 'Sin entidad')+'</strong>' +
                    '</div>' +
                    '<div class="confcta-detail-cell">' +
                        '<span class="confcta-cell-label">Cuenta</span>' +
                        '<div class="confcta-account-copy">' +
                            '<i class="fas fa-wallet"></i>' +
                            '<span>'+confCtaEsc(row.cuenta || 'Sin cuenta configurada')+'</span>' +
                        '</div>' +
                    '</div>' +
                '</article>';
        });

        return html;
    }

    function confCtaRenderMiniatura(pageRows, offset){
        var html = '<div class="confcta-mini-grid">';

        pageRows.forEach(function(row, i){
            var idx = offset + i;

            html +=
                '<article class="confcta-mini-card">' +
                    '<div class="confcta-mini-line"></div>' +
                    '<div class="confcta-mini-head">' +
                        '<span class="confcta-entity-icon"><i class="fas fa-sitemap"></i></span>' +
                        '<div class="confcta-mini-title">' +
                            '<span>Entidad contable</span>' +
                            '<h4>'+confCtaEsc(row.diario || 'Sin entidad')+'</h4>' +
                        '</div>' +
                    '</div>' +
                    '<div class="confcta-mini-body">' +
                        '<span>Cuenta asociada</span>' +
                        '<strong>'+confCtaEsc(row.cuenta || 'Sin cuenta configurada')+'</strong>' +
                    '</div>' +
                    '<div class="confcta-mini-footer">' +
                        confCtaAcciones(row, idx) +
                    '</div>' +
                '</article>';
        });

        return html + '</div>';
    }

    function confCtaRender(){
        confCtaActualizarBotonesVista();

        var rows = confCtaState.filtered || [];

        if(!rows.length){
            $('#confCtaListado').html(
                '<div class="confcta-empty">' +
                    '<i class="fas fa-wallet"></i>' +
                    '<strong>Sin configuraciones</strong>' +
                    '<span>No se encontraron registros con la búsqueda actual.</span>' +
                '</div>'
            );

            $('#confCtaInfo').text('0 registros');
            $('#confCtaPaginacion').empty();
            return;
        }

        var totalPages = Math.max(1, Math.ceil(rows.length / confCtaState.pageSize));

        if(confCtaState.page > totalPages){
            confCtaState.page = totalPages;
        }

        var offset = (confCtaState.page - 1) * confCtaState.pageSize;
        var pageRows = rows.slice(offset, offset + confCtaState.pageSize);

        $('#confCtaListado').html(
            confCtaState.view === 'miniatura'
                ? confCtaRenderMiniatura(pageRows, offset)
                : confCtaRenderDetalle(pageRows, offset)
        );

        $('#confCtaInfo').text(
            'Mostrando '+(offset + 1)+' a '+Math.min(offset + pageRows.length, rows.length)+' de '+rows.length+' registros'
        );

        confCtaRenderPaginacion(totalPages);

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

    function confCtaRenderPaginacion(totalPages){
        var current = confCtaState.page;
        var html = '';

        function btn(label, page, disabled, active, icon){
            return '<button type="button" class="confcta-page-btn'+(active ? ' active' : '')+'" data-page="'+page+'" '+(disabled ? 'disabled' : '')+'>' +
                (icon ? '<i class="'+icon+'"></i>' : '') +
                '<span>'+label+'</span>' +
            '</button>';
        }

        html += btn('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
        html += btn('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

        var from = Math.max(1, current - 2);
        var to = Math.min(totalPages, from + 4);
        from = Math.max(1, to - 4);

        for(var p = from; p <= to; p++){
            html += btn(String(p), p, false, p === current, '');
        }

        html += btn('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
        html += btn('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

        $('#confCtaPaginacion').html(html);
    }

    function confCtaEditar(row){
        if(!row || !row.diarios_id){
            showNotify('warning', 'Registro inválido', 'No se pudo identificar la configuración seleccionada.');
            return;
        }

        var url = '<?php echo SERVERURL;?>core/editarDiarios.php';

        $('#formConfCuentasEntidades #diarios_id').val(row.diarios_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formConfCuentasEntidades').serialize()
        }).done(function(registro){
            var valores;

            try{
                valores = typeof registro === 'string' ? JSON.parse(registro) : registro;
            }catch(e){
                try{
                    valores = eval(registro);
                }catch(ex){
                    valores = null;
                }
            }

            if(!valores){
                showNotify('error', 'Respuesta inválida', 'No se pudo interpretar la información de la configuración.');
                return;
            }

            $('#formConfCuentasEntidades').attr({
                'data-form': 'update',
                'action': '<?php echo SERVERURL;?>ajax/modificarDiariosAjax.php'
            });

            if($('#formConfCuentasEntidades')[0]){
                $('#formConfCuentasEntidades')[0].reset();
            }

            $('#edi_confEntidades').show();
            $('#formConfCuentasEntidades #pro_ConfCuentasEntidades').val('Editar');
            $('#formConfCuentasEntidades #confEntidad').val(valores[1]);
            $('#formConfCuentasEntidades #confCuenta').val(valores[2]);

            if($.fn.selectpicker){
                $('#formConfCuentasEntidades #confCuenta').selectpicker('refresh');
            }

            $('#formConfCuentasEntidades #buscar_confCuenta').hide();
            $('#formConfCuentasEntidades #confEntidad').prop('disabled', true);

            $('#modalConfEntidades').modal({
                show: true,
                keyboard: true,
                backdrop: 'static'
            });
        }).fail(function(xhr){
            showNotify(
                'error',
                'Error al editar',
                'No se pudo cargar la configuración seleccionada.'
            );

            console.error(xhr.responseText);
        });
    }

    function getCuentaDiarios(){
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL;?>core/getCuenta.php',
            async: true
        }).done(function(data){
            var $select = $('#formConfCuentasEntidades #confCuenta');

            $select.html(data);

            if($.fn.selectpicker){
                $select.selectpicker('refresh');
            }
        }).fail(function(xhr){
            showNotify(
                'error',
                'Error al cargar cuentas',
                'No se pudieron cargar las cuentas contables disponibles.'
            );

            console.error(xhr.responseText);
        });
    }

    window.getCuentaDiarios = getCuentaDiarios;

    function confCtaLogoPdf(callback){
        if(typeof imagen === 'string' && imagen.indexOf('data:image/') === 0){
            callback(imagen);
            return;
        }

        $.ajax({
            type: 'GET',
            url: '<?php echo SERVERURL;?>core/get_image.php',
            dataType: 'text',
            timeout: 15000
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
                    canvas.getContext('2d').drawImage(img, 0, 0);
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

    function confCtaExportPdf(){
        var rows = confCtaState.filtered || [];

        if(!rows.length){
            showNotify('warning', 'Sin datos', 'No hay configuraciones para exportar.');
            return;
        }

        if(typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function'){
            showNotify('error', 'PDF no disponible', 'No están disponibles los componentes necesarios para generar el PDF.');
            return;
        }

        confCtaLogoPdf(function(logoData){
            var body = [[
                {text: 'ENTIDAD', style: 'th'},
                {text: 'CUENTA', style: 'th'}
            ]];

            rows.forEach(function(row, i){
                var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

                body.push([
                    {text: String(row.diario || ''), style: 'td', fillColor: fill},
                    {text: String(row.cuenta || ''), style: 'td', fillColor: fill}
                ]);
            });

            body.push([
                {text: 'TOTAL DE CONFIGURACIONES', style: 'totalLabel', fillColor: '#EAF1F7'},
                {text: String(rows.length), style: 'totalValue', fillColor: '#EAF1F7'}
            ]);

            var logoPlate = logoData
                ? {
                    table: {
                        widths: ['*'],
                        body: [[
                            {
                                image: logoData,
                                fit: [78, 44],
                                alignment: 'center',
                                margin: [6, 5, 6, 5],
                                fillColor: '#FFFFFF'
                            }
                        ]]
                    },
                    layout: 'noBorders',
                    fillColor: '#17324D',
                    margin: [8, 7, 8, 7]
                }
                : {
                    text: 'IZZY',
                    bold: true,
                    fontSize: 18,
                    color: '#17324D',
                    alignment: 'center',
                    fillColor: '#FFFFFF',
                    margin: [8, 14, 8, 14]
                };

            var doc = {
                pageSize: 'LETTER',
                pageOrientation: 'landscape',
                pageMargins: [28, 28, 28, 34],

                header: function(){
                    return {
                        margin: [28, 12, 28, 0],
                        canvas: [
                            {
                                type: 'line',
                                x1: 0,
                                y1: 0,
                                x2: 736,
                                y2: 0,
                                lineWidth: 2,
                                lineColor: '#0EA5A8'
                            }
                        ]
                    };
                },

                footer: function(currentPage, pageCount){
                    return {
                        margin: [28, 8, 28, 0],
                        columns: [
                            {
                                text: 'IZZY • Configuración de Cuenta',
                                fontSize: 7,
                                color: '#7A869A'
                            },
                            {
                                text: 'Página '+currentPage+' de '+pageCount,
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
                            widths: [100, '*', 150],
                            body: [[
                                logoPlate,
                                {
                                    stack: [
                                        {
                                            text: 'REPORTE DE CONFIGURACIÓN DE CUENTAS',
                                            bold: true,
                                            fontSize: 16,
                                            color: '#FFFFFF'
                                        },
                                        {
                                            text: 'Cuentas contables asociadas a entidades',
                                            fontSize: 8,
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
                                            text: rows.length+' registro(s)',
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
                        margin: [0, 0, 0, 12]
                    },

                    {
                        text: 'Entidad: '+(confCtaState.filtroEntidad || 'Todas')+
                              '   |   Cuenta: '+(confCtaState.filtroCuenta || 'Todas')+
                              '   |   Búsqueda: '+(confCtaState.search || 'Sin búsqueda'),
                        fontSize: 7,
                        color: '#5E6C84',
                        fillColor: '#F7F9FC',
                        margin: [8, 7, 8, 7]
                    },

                    {
                        table: {
                            // Mismo ancho útil que el encabezado del reporte.
                            widths: ['*', '*'],
                            headerRows: 1,
                            body: body
                        },
                        layout: {
                            hLineColor: function(){ return '#DDE3EA'; },
                            vLineColor: function(){ return '#DDE3EA'; },
                            hLineWidth: function(){ return 0.55; },
                            vLineWidth: function(){ return 0.55; },
                            paddingLeft: function(){ return 4; },
                            paddingRight: function(){ return 4; },
                            paddingTop: function(){ return 5; },
                            paddingBottom: function(){ return 5; }
                        }
                    }
                ],

                styles: {
                    th: {
                        fontSize: 7,
                        bold: true,
                        color: '#FFFFFF',
                        fillColor: '#17324D',
                        alignment: 'center'
                    },
                    td: {
                        fontSize: 7.5,
                        color: '#253858',
                        noWrap: false
                    },
                    totalLabel: {
                        fontSize: 7.5,
                        bold: true,
                        color: '#17324D'
                    },
                    totalValue: {
                        fontSize: 7.5,
                        bold: true,
                        color: '#17324D',
                        alignment: 'right'
                    }
                },

                defaultStyle: {
                    font: 'Roboto'
                }
            };

            pdfMake.createPdf(doc).getDataUrl(function(url){
                abrirModalPdfPublico(
                    url,
                    'Reporte de Configuración de Cuentas',
                    'Reporte_Configuracion_Cuentas.pdf'
                );
            });
        });
    }

    function confCtaExcelEscape(v){
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function confCtaExcelCol(n){
        var name = '';

        while(n >= 0){
            name = String.fromCharCode((n % 26) + 65) + name;
            n = Math.floor(n / 26) - 1;
        }

        return name;
    }

    function confCtaExcelCell(ref, value, style){
        return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+confCtaExcelEscape(value)+'</t></is></c>';
    }

    function confCtaExportExcel(){
        var rows = confCtaState.filtered || [];

        if(!rows.length){
            showNotify('warning', 'Sin datos', 'No hay configuraciones para exportar.');
            return;
        }

        if(typeof JSZip === 'undefined'){
            showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
            return;
        }

        var sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">'+
                confCtaExcelCell('A1', 'IZZY • CONFIGURACIÓN DE CUENTAS', 1)+
            '</row>'
        );

        sheetRows.push(
            '<row r="2">'+
                confCtaExcelCell(
                    'A2',
                    'Registros: '+rows.length+
                    ' • Entidad: '+(confCtaState.filtroEntidad || 'Todas')+
                    ' • Cuenta: '+(confCtaState.filtroCuenta || 'Todas')+
                    ' • Generado: '+new Date().toLocaleDateString('es-HN'),
                    2
                )+
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">'+
                confCtaExcelCell('A4', 'ENTIDAD', 3)+
                confCtaExcelCell('B4', 'CUENTA', 3)+
            '</row>'
        );

        rows.forEach(function(row, i){
            var r = 5 + i;

            sheetRows.push(
                '<row r="'+r+'">'+
                    confCtaExcelCell('A'+r, row.diario || '', 4)+
                    confCtaExcelCell('B'+r, row.cuenta || '', 4)+
                '</row>'
            );
        });

        var totalRow = 5 + rows.length;

        sheetRows.push(
            '<row r="'+totalRow+'">'+
                confCtaExcelCell('A'+totalRow, 'TOTAL DE CONFIGURACIONES', 5)+
                confCtaExcelCell('B'+totalRow, String(rows.length), 5)+
            '</row>'
        );

        var worksheetXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:B'+totalRow+'"/>' +
                '<sheetViews>' +
                    '<sheetView workbookViewId="0" showGridLines="0">' +
                        '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>' +
                    '</sheetView>' +
                '</sheetViews>' +
                '<cols>' +
                    '<col min="1" max="1" width="36" customWidth="1"/>' +
                    '<col min="2" max="2" width="48" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>'+sheetRows.join('')+'</sheetData>' +
                '<autoFilter ref="A4:B'+(totalRow-1)+'"/>' +
                '<mergeCells count="2">' +
                    '<mergeCell ref="A1:B1"/>' +
                    '<mergeCell ref="A2:B2"/>' +
                '</mergeCells>' +
            '</worksheet>';

        var stylesXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<fonts count="6">' +
                    '<font><sz val="10"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="10"/><color rgb="FF17324D"/><name val="Calibri"/></font>' +
                '</fonts>' +
                '<fills count="5">' +
                    '<fill><patternFill patternType="none"/></fill>' +
                    '<fill><patternFill patternType="gray125"/></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill>' +
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
                '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
                '<cellXfs count="6">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>';

        var workbookXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
                      'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<sheets><sheet name="Configuración" sheetId="1" r:id="rId1"/></sheets>' +
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
        zip.folder('xl').folder('worksheets').file('sheet1.xml', worksheetXml);

        var options = {
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            compression: 'DEFLATE'
        };

        var promise = typeof zip.generateAsync === 'function'
            ? zip.generateAsync(options)
            : Promise.resolve(zip.generate(options));

        promise.then(function(blob){
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');

            a.href = url;
            a.download = 'Reporte_Configuracion_Cuentas.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();

            setTimeout(function(){
                URL.revokeObjectURL(url);
            }, 1000);
        }).catch(function(error){
            console.error(error);
            showNotify('error', 'Excel', 'No se pudo generar el archivo Excel.');
        });
    }

    function confCtaToggleSection($btn){
        var target = $btn.data('target');
        var $body = $(target);
        var visible = $body.is(':visible');

        $body.stop(true, true).slideToggle(160);

        $btn.attr('aria-expanded', visible ? 'false' : 'true');
        $btn.find('span').text(visible ? 'Mostrar' : 'Ocultar');
        $btn.find('i')
            .toggleClass('fa-chevron-up', !visible)
            .toggleClass('fa-chevron-down', visible);
    }

    function confCtaBindEventos(){
        $(document)
            .off('submit.confcta', '#formConfCtaFiltros')
            .on('submit.confcta', '#formConfCtaFiltros', function(e){
                e.preventDefault();
                confCtaState.filtroEntidad = $('#confCtaFiltroEntidad').val() || '';
                confCtaState.filtroCuenta = $('#confCtaFiltroCuenta').val() || '';
                confCtaFiltrar();
            })

            .off('reset.confcta', '#formConfCtaFiltros')
            .on('reset.confcta', '#formConfCtaFiltros', function(){
                window.setTimeout(function(){
                    confCtaState.filtroEntidad = '';
                    confCtaState.filtroCuenta = '';
                    $('#confCtaFiltroEntidad').val('').trigger('change.select2');
                    $('#confCtaFiltroCuenta').val('').trigger('change.select2');
                    confCtaFiltrar();
                }, 0);
            })

            .off('click.confcta', '#btnConfCtaActualizar')
            .on('click.confcta', '#btnConfCtaActualizar', listar_diarios_configuracion)

            .off('click.confcta', '#btnConfCtaExcel')
            .on('click.confcta', '#btnConfCtaExcel', confCtaExportExcel)

            .off('click.confcta', '#btnConfCtaPdf')
            .on('click.confcta', '#btnConfCtaPdf', confCtaExportPdf)

            .off('input.confcta', '#confCtaBuscar')
            .on('input.confcta', '#confCtaBuscar', function(){
                confCtaState.search = this.value || '';
                confCtaFiltrar();
            })

            .off('click.confcta', '#confCtaBuscarLimpiar')
            .on('click.confcta', '#confCtaBuscarLimpiar', function(){
                $('#confCtaBuscar').val('').focus();
                confCtaState.search = '';
                confCtaFiltrar();
            })

            .off('change.confcta', '#confCtaPageSize')
            .on('change.confcta', '#confCtaPageSize', function(){
                var n = parseInt(this.value, 10);

                if(!n){
                    return;
                }

                confCtaState.pageSize = n;

                if(confCtaState.view === 'miniatura'){
                    confCtaState.pageSizeMiniatura = n;
                }else{
                    confCtaState.pageSizeDetalle = n;
                }

                confCtaState.page = 1;
                confCtaRender();
            })

            .off('click.confcta', '.confcta-view-btn')
            .on('click.confcta', '.confcta-view-btn', function(){
                confCtaCambiarVista($(this).data('view'));
            })

            .off('click.confcta', '#confCtaPaginacion .confcta-page-btn')
            .on('click.confcta', '#confCtaPaginacion .confcta-page-btn', function(){
                if(this.disabled){
                    return;
                }

                var page = parseInt($(this).data('page'), 10);

                if(page){
                    confCtaState.page = page;
                    confCtaRender();
                }
            })

            .off('click.confcta', '#confCtaListado .js-confcta-editar')
            .on('click.confcta', '#confCtaListado .js-confcta-editar', function(){
                var idx = parseInt($(this).data('index'), 10);
                var row = confCtaState.filtered[idx];

                confCtaEditar(row);
            })

            .off('click.confcta', '.confcta-toggle-section')
            .on('click.confcta', '.confcta-toggle-section', function(){
                confCtaToggleSection($(this));
            });

        $(window)
            .off('resize.confcta orientationchange.confcta')
            .on('resize.confcta orientationchange.confcta', function(){
                var target = confCtaEsMovil() ? 'miniatura' : confCtaState.preferredView;

                if(confCtaState.view !== target){
                    confCtaState.view = target;
                    confCtaState.page = 1;
                    confCtaSincronizarPageSize();
                    confCtaRender();
                }

                confCtaActualizarBotonesVista();
            });
    }

})(jQuery);
</script>
