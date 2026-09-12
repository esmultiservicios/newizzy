<script>
(function($){
    'use strict';

    var CHEQUES_STORAGE_VISTA = 'izzy.cheques.tipo_vista';
    var chequesState = {
        rows: [],
        filtered: [],
        catalogos: {proveedores:[], categorias:[], cuentas:[], cuenta_cheque:null},
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        view: 'detalle',
        preferredView: 'detalle',
        search: '',
        loading: false
    };

    function chequesEsc(v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    }

    function chequesNum(v) {
        if (typeof v === 'number') return isNaN(v) ? 0 : v;
        var s = String(v == null ? '' : v).replace(/,/g,'').replace(/L\./gi,'').trim();
        var m = s.match(/-?\d+(?:\.\d+)?/);
        return m ? (parseFloat(m[0]) || 0) : 0;
    }

    function chequesMoney(v) {
        return 'L. ' + chequesNum(v).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function chequesMovil() {
        return window.matchMedia ? window.matchMedia('(max-width: 767.98px)').matches : $(window).width() <= 767;
    }

    function chequesPanel(btn, contenido, key) {
        var visible = true;
        try {
            var saved = localStorage.getItem(key);
            if (saved !== null) visible = saved === '1';
        } catch(e){}

        function sync(){
            $(contenido).toggle(visible);
            $(btn).attr('aria-expanded',visible?'true':'false');
            $(btn).find('span').text(visible?'Ocultar':'Mostrar');
            $(btn).find('i').toggleClass('fa-chevron-up',visible).toggleClass('fa-chevron-down',!visible);
        }

        sync();
        $(btn).off('click.chequesPanel').on('click.chequesPanel',function(){
            visible=!visible;
            $(contenido).stop(true,true)[visible?'slideDown':'slideUp'](160);
            sync();
            try{ localStorage.setItem(key,visible?'1':'0'); }catch(e){}
        });
    }

    function refrescarSelect($select, items, valueKey, labelFn, includeAll, allText) {
        if (!$select.length) return;

        var value = $select.val();
        var html = includeAll ? '<option value="">' + chequesEsc(allText || 'Todos') + '</option>' : '';

        (items || []).forEach(function(item){
            html += '<option value="'+chequesEsc(item[valueKey])+'">'+chequesEsc(labelFn(item))+'</option>';
        });

        $select.html(html);
        if (value && $select.find('option[value="'+String(value).replace(/"/g,'\\"')+'"]').length) {
            $select.val(value);
        }

        if ($.fn.selectpicker) $select.selectpicker('refresh');
    }

    function cargarCatalogos(callback) {
        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/cheques/catalogos.php',
            dataType:'json'
        }).done(function(resp){
            if (!resp || resp.success !== true) {
                showNotify('error','No se pudieron cargar catálogos',resp && resp.message ? resp.message : 'Respuesta inválida.');
                return;
            }

            chequesState.catalogos = resp;

            refrescarSelect($('#filtro_cuenta_cheques'),resp.cuentas,'cuentas_id',function(x){return (x.codigo ? x.codigo+' - ' : '')+x.nombre;},true,'Todas');
            refrescarSelect($('#filtro_proveedor_cheques'),resp.proveedores,'proveedores_id',function(x){return x.nombre;},true,'Todos');
            refrescarSelect($('#filtro_categoria_cheques'),resp.categorias,'categoria_gastos_id',function(x){return x.nombre;},true,'Todas');

            refrescarSelect($('#cheque_proveedor'),resp.proveedores,'proveedores_id',function(x){return x.nombre;},false);
            refrescarSelect($('#cheque_categoria'),resp.categorias,'categoria_gastos_id',function(x){return x.nombre;},false);
            refrescarSelect($('#config_cuenta_cheque'),resp.cuentas,'cuentas_id',function(x){return (x.codigo ? x.codigo+' - ' : '')+x.nombre+' ('+chequesMoney(x.saldo)+')';},false);

            var cuenta = resp.cuenta_cheque;
            var $cuenta = $('#cheque_cuenta');
            if (cuenta) {
                $cuenta.html('<option value="'+chequesEsc(cuenta.cuentas_id)+'">'+chequesEsc((cuenta.codigo?cuenta.codigo+' - ':'')+cuenta.cuenta)+'</option>').val(String(cuenta.cuentas_id));
                if ($.fn.selectpicker) $cuenta.selectpicker('refresh');

                $('#cheque_saldo_cuenta').text('Saldo disponible: '+chequesMoney(cuenta.saldo));
                $('#resumenCuentaCheque').text((cuenta.codigo?cuenta.codigo+' - ':'')+cuenta.cuenta);
                $('#resumenSaldoCheque').text(chequesMoney(cuenta.saldo));
                $('#config_cuenta_cheque').val(String(cuenta.cuentas_id));
                if ($.fn.selectpicker) $('#config_cuenta_cheque').selectpicker('refresh');
            } else {
                $cuenta.html('<option value="">No configurada</option>');
                if ($.fn.selectpicker) $cuenta.selectpicker('refresh');
                $('#cheque_saldo_cuenta').text('Saldo disponible: L. 0.00');
                $('#resumenCuentaCheque').text('No configurada');
                $('#resumenSaldoCheque').text('L. 0.00');
            }

            actualizarSaldoDespuesCheque();
            if (typeof callback === 'function') callback(resp);
        }).fail(function(xhr){
            showNotify('error','Error de comunicación','No fue posible cargar los catálogos de cheques.');
            console.error(xhr.responseText);
        });
    }

    function sincronizarPageSize() {
        var mini = chequesState.view === 'miniatura';
        var opciones = mini ? [6,12,18,30] : [10,25,50,100];
        var pref = mini ? chequesState.pageSizeMiniatura : chequesState.pageSizeDetalle;
        if (opciones.indexOf(pref) === -1) pref = opciones[0];
        chequesState.pageSize = pref;

        var $s=$('#chequesPageSize').empty();
        opciones.forEach(function(n){$s.append($('<option>').val(n).text(n));});
        $s.val(String(pref));
    }

    function sincronizarVista() {
        var mobile=chequesMovil();
        if (mobile) chequesState.view='miniatura';

        $('.cheques-view-btn[data-view="detalle"]').toggleClass('d-none',mobile).prop('disabled',mobile);
        $('.cheques-view-btn').removeClass('active').attr('aria-pressed','false');
        $('.cheques-view-btn[data-view="'+chequesState.view+'"]').addClass('active').attr('aria-pressed','true');
    }

    function estadoBadge(row){
        var activo=parseInt(row.estado||0,10)===1;
        return '<span class="cheques-status '+(activo?'cheques-status-activo':'cheques-status-anulado')+'"><i class="fas '+(activo?'fa-check-circle':'fa-ban')+'"></i> '+(activo?'Activo':'Anulado')+'</span>';
    }

    function acciones(row,index){
        var activo=parseInt(row.estado||0,10)===1;
        var items =
            '<button type="button" class="dropdown-item accion-item js-cheque-imprimir" data-index="'+index+'">'+
                '<span class="accion-icon accion-icon-success"><i class="fas fa-print"></i></span><span class="accion-label">Imprimir cheque</span>'+
            '</button>'+
            '<button type="button" class="dropdown-item accion-item js-cheque-comprobante" data-index="'+index+'">'+
                '<span class="accion-icon accion-icon-primary"><i class="fas fa-file-pdf"></i></span><span class="accion-label">Comprobante</span>'+
            '</button>';

        if (activo) {
            items +=
                '<button type="button" class="dropdown-item accion-item js-cheque-anular table_cancelar ocultar" data-index="'+index+'">'+
                    '<span class="accion-icon accion-icon-danger"><i class="fas fa-ban"></i></span><span class="accion-label">Anular cheque</span>'+
                '</button>';
        } else {
            items +=
                '<button type="button" class="dropdown-item accion-item" disabled>'+
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span><span class="accion-label">Cheque anulado</span>'+
                '</button>';
        }

        return '<div class="dropdown acciones-dropdown">'+
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle"><i class="fas fa-cog"></i><span>Acciones</span></button>'+
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+items+'</div>'+
        '</div>';
    }

    function renderDetalle(rows,offset){
        var html='<div class="cheques-detail-header">'+
            '<div>Acciones</div><div>Cheque / Fecha</div><div>Beneficiario</div><div>Cuenta</div>'+
            '<div>Categoría</div><div>Factura</div><div>Importe</div><div>Estado</div></div>';

        rows.forEach(function(row,i){
            var idx=offset+i;
            html+='<article class="cheques-detail-row">'+
                '<div class="cheques-cell cheques-actions-cell"><span class="cheques-cell-label">Acciones</span>'+acciones(row,idx)+'</div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Cheque / Fecha</span><div class="cheques-main"><span class="cheques-main-icon"><i class="fas fa-money-check"></i></span><div><strong>#'+chequesEsc(row.numero_cheque||row.cheque_id)+'</strong><small>'+chequesEsc(row.fecha)+'</small></div></div></div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Beneficiario</span><strong>'+chequesEsc(row.proveedor)+'</strong></div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Cuenta</span><strong>'+chequesEsc((row.cuenta_codigo?row.cuenta_codigo+' - ':'')+row.cuenta_nombre)+'</strong></div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Categoría</span><strong>'+chequesEsc(row.categoria)+'</strong></div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Factura</span>'+chequesEsc(row.factura||'Sin referencia')+'</div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Importe</span><strong class="cheques-money '+(parseInt(row.estado,10)===0?'cheques-money-anulado':'')+'">'+chequesMoney(row.importe_valor)+'</strong></div>'+
                '<div class="cheques-cell"><span class="cheques-cell-label">Estado</span>'+estadoBadge(row)+'</div>'+
            '</article>';
        });
        return html;
    }

    function renderMiniatura(rows,offset){
        var html='<div class="cheques-mini-grid">';
        rows.forEach(function(row,i){
            var idx=offset+i, anulado=parseInt(row.estado||0,10)===0;
            html+='<article class="cheques-mini-card '+(anulado?'anulado':'')+'">'+
                '<div class="cheques-mini-line"></div>'+
                '<div class="cheques-mini-header"><span class="cheques-main-icon"><i class="fas fa-money-check"></i></span><div class="cheques-mini-title"><h4>Cheque #'+chequesEsc(row.numero_cheque||row.cheque_id)+'</h4><span>'+chequesEsc(row.fecha)+'</span></div>'+estadoBadge(row)+'</div>'+
                '<div class="cheques-mini-body">'+
                    '<div class="cheques-mini-field cheques-mini-full"><span>Beneficiario</span><strong>'+chequesEsc(row.proveedor)+'</strong></div>'+
                    '<div class="cheques-mini-field"><span>Cuenta</span><strong>'+chequesEsc((row.cuenta_codigo?row.cuenta_codigo+' - ':'')+row.cuenta_nombre)+'</strong></div>'+
                    '<div class="cheques-mini-field"><span>Categoría</span><strong>'+chequesEsc(row.categoria)+'</strong></div>'+
                    '<div class="cheques-mini-field"><span>Factura</span><strong>'+chequesEsc(row.factura||'Sin referencia')+'</strong></div>'+
                    '<div class="cheques-mini-field"><span>ID Egreso</span><strong>#'+chequesEsc(row.egresos_id||'N/A')+'</strong></div>'+
                    '<div class="cheques-mini-field cheques-mini-full cheques-mini-total"><span>Importe</span><strong>'+chequesMoney(row.importe_valor)+'</strong></div>'+
                    '<div class="cheques-mini-field cheques-mini-full"><span>Observación</span><strong>'+chequesEsc(row.observacion||'Sin observación')+'</strong></div>'+
                    (anulado?'<div class="cheques-mini-field cheques-mini-full"><span>Motivo de anulación</span><strong>'+chequesEsc(row.motivo_anulacion||'No especificado')+'</strong></div>':'')+
                '</div>'+
                '<div class="cheques-mini-footer">'+acciones(row,idx)+'</div>'+
            '</article>';
        });
        return html+'</div>';
    }

    function actualizarResumen(){
        var activos=0, anulados=0, totalActivo=0, totalAnulado=0, totalEmitido=0;
        chequesState.filtered.forEach(function(r){
            var monto=chequesNum(r.importe_valor);
            totalEmitido+=monto;
            if(parseInt(r.estado||0,10)===1){activos++;totalActivo+=monto;}else{anulados++;totalAnulado+=monto;}
        });
        $('#chequesKpiRegistros').text(chequesState.filtered.length);
        $('#chequesKpiActivos').text(activos);
        $('#chequesKpiAnulados').text(anulados);
        $('#chequesKpiTotal,#chequesTotalActivo').text(chequesMoney(totalActivo));
        $('#chequesTotalAnulado').text(chequesMoney(totalAnulado));
        $('#chequesTotalEmitido').text(chequesMoney(totalEmitido));
    }

    function filtrarLocal(){
        var q=$.trim(chequesState.search||'').toLowerCase();
        chequesState.filtered=!q?chequesState.rows.slice():chequesState.rows.filter(function(r){
            return [r.numero_cheque,r.fecha,r.proveedor,r.factura,r.cuenta_codigo,r.cuenta_nombre,r.categoria,r.importe,r.observacion,r.motivo_anulacion,parseInt(r.estado,10)===1?'activo':'anulado']
                .map(function(v){return String(v==null?'':v).toLowerCase();}).join(' ').indexOf(q)!==-1;
        });
        chequesState.page=1;
        actualizarResumen();
        render();
    }

    function paginacion(totalPages){
        var c=chequesState.page, html='';
        function b(label,page,disabled,active,icon){
            return '<button type="button" class="cheques-page-btn'+(active?' active':'')+'" data-page="'+page+'" '+(disabled?'disabled':'')+'>'+(icon?'<i class="'+icon+' mr-1"></i>':'')+label+'</button>';
        }
        html+=b('Inicio',1,c===1,false,'fas fa-angle-double-left');
        html+=b('Anterior',c-1,c===1,false,'fas fa-angle-left');
        var from=Math.max(1,c-2),to=Math.min(totalPages,from+4);from=Math.max(1,to-4);
        for(var p=from;p<=to;p++) html+=b(String(p),p,false,p===c,'');
        html+=b('Siguiente',c+1,c===totalPages,false,'fas fa-angle-right');
        html+=b('Final',totalPages,c===totalPages,false,'fas fa-angle-double-right');
        $('#chequesPaginacion').html(html);
    }

    function render(){
        var rows=chequesState.filtered||[];
        if(chequesState.loading){
            $('#chequesListado').html('<div class="cheques-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando cheques</strong><span>Consultando información...</span></div>');
            $('#chequesInfo').text('0 registros');$('#chequesPaginacion').empty();return;
        }
        if(!rows.length){
            $('#chequesListado').html('<div class="cheques-state"><i class="fas fa-money-check"></i><strong>Sin cheques</strong><span>No se encontraron registros con los filtros actuales.</span></div>');
            $('#chequesInfo').text('0 registros');$('#chequesPaginacion').empty();return;
        }

        var pages=Math.max(1,Math.ceil(rows.length/chequesState.pageSize));
        if(chequesState.page>pages)chequesState.page=pages;
        var offset=(chequesState.page-1)*chequesState.pageSize;
        var pageRows=rows.slice(offset,offset+chequesState.pageSize);

        $('#chequesListado').removeClass('vista-detalle vista-miniatura').addClass('vista-'+chequesState.view)
            .html(chequesState.view==='miniatura'?renderMiniatura(pageRows,offset):renderDetalle(pageRows,offset));

        $('#chequesInfo').text('Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros');
        paginacion(pages);

        if(typeof getPermisosTipoUsuarioAccesosTable==='function'&&typeof getPrivilegioTipoUsuario==='function'){
            try{getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());}catch(e){}
        }
    }

    function listar(){
        chequesState.loading=true;render();
        $.ajax({
            type:'POST',
            url:'<?php echo SERVERURL;?>core/cheques/listar.php',
            dataType:'json',
            data:{
                fechai:$('#fechai').val(),
                fechaf:$('#fechaf').val(),
                estado:$('#estado_cheques').val()||'',
                cuentas_id:$('#filtro_cuenta_cheques').val()||0,
                proveedores_id:$('#filtro_proveedor_cheques').val()||0,
                categoria_gastos_id:$('#filtro_categoria_cheques').val()||0
            }
        }).done(function(resp){
            chequesState.loading=false;
            if(!resp||resp.success!==true){
                chequesState.rows=[];chequesState.filtered=[];actualizarResumen();render();
                showNotify('error','No se pudieron cargar los cheques',resp&&resp.message?resp.message:'Respuesta inválida.');
                return;
            }
            chequesState.rows=Array.isArray(resp.data)?resp.data:[];
            chequesState.search=$('#buscarChequesListado').val()||'';
            filtrarLocal();
        }).fail(function(xhr){
            chequesState.loading=false;chequesState.rows=[];chequesState.filtered=[];actualizarResumen();render();
            showNotify('error','Error de comunicación','No se pudo cargar el módulo de cheques.');
            console.error(xhr.responseText);
        });
    }

    function abrirCheque(){
        cargarCatalogos(function(resp){
            if(!resp.cuenta_cheque){
                showNotify('warning','Cuenta no configurada','Primero configure la cuenta contable que utilizará el tipo de pago Cheque.');
                return;
            }
            $('#formCheque')[0].reset();
            $('#cheque_fecha').val(new Date().toISOString().slice(0,10));
            $('#cheque_proveedor,#cheque_categoria').val('');
            if($.fn.selectpicker)$('#cheque_proveedor,#cheque_categoria').selectpicker('refresh');
            actualizarSaldoDespuesCheque();
            $('#modalCheque').modal({show:true,keyboard:true,backdrop:'static'});
            $('#modalCheque').one('shown.bs.modal',function(){$('#cheque_numero').focus();});
        });
    }

    function actualizarSaldoDespuesCheque(){
        var cuenta=chequesState.catalogos.cuenta_cheque;
        var saldo=cuenta?chequesNum(cuenta.saldo):0;
        var importe=chequesNum($('#cheque_importe').val());
        $('#resumenSaldoDespues').text(chequesMoney(saldo-importe)).toggleClass('text-danger',(saldo-importe)<0);
    }

    function actualizarVistaConfigCuentaCheque(){
        var actual = chequesState.catalogos.cuenta_cheque || null;
        var seleccionId = parseInt($('#config_cuenta_cheque').val() || 0,10);
        var nueva = null;

        (chequesState.catalogos.cuentas || []).some(function(c){
            if(parseInt(c.cuentas_id,10) === seleccionId){
                nueva = c;
                return true;
            }
            return false;
        });

        if(actual){
            $('#configCuentaActualNombre').text(actual.cuenta || 'Cuenta configurada');
            $('#configCuentaActualCodigo').text(actual.codigo ? 'Código: '+actual.codigo : 'Sin código');
            $('#configCuentaActualSaldo').text(chequesMoney(actual.saldo));
        }else{
            $('#configCuentaActualNombre').text('No configurada');
            $('#configCuentaActualCodigo').text('Sin código');
            $('#configCuentaActualSaldo').text('L. 0.00');
        }

        if(nueva){
            $('#configCuentaNuevaNombre').text((nueva.codigo ? nueva.codigo+' - ' : '') + nueva.nombre);
            $('#configCuentaNuevaSaldo').text(chequesMoney(nueva.saldo));
        }else{
            $('#configCuentaNuevaNombre').text('Seleccione una cuenta');
            $('#configCuentaNuevaSaldo').text('L. 0.00');
        }
    }

    function guardarCheque(){
        var $btn=$('#btnGuardarCheque');
        var importe=chequesNum($('#cheque_importe').val());
        var cuenta=chequesState.catalogos.cuenta_cheque;

        if(!cuenta){showNotify('warning','Cuenta no configurada','Configure la cuenta para cheques antes de emitir.');return;}
        if(!$('#cheque_numero').val().trim()){showNotify('warning','Número requerido','Ingrese el número del cheque.');$('#cheque_numero').focus();return;}
        if(!$('#cheque_proveedor').val()){showNotify('warning','Proveedor requerido','Seleccione el proveedor o beneficiario.');return;}
        if(!$('#cheque_categoria').val()){showNotify('warning','Categoría requerida','Seleccione la categoría de gasto.');return;}
        if(importe<=0){showNotify('warning','Importe inválido','Ingrese un importe mayor a cero.');$('#cheque_importe').focus();return;}
        if(importe>chequesNum(cuenta.saldo)){showNotify('warning','Saldo insuficiente','El importe supera el saldo de la cuenta configurada.');return;}

        swal({
            title:'¿Emitir cheque?',
            text:'Se registrará el cheque, el egreso y el débito de la cuenta contable.',
            icon:'warning',
            buttons:{cancel:{text:'Cancelar',visible:true},confirm:{text:'Sí, emitir'}},
            closeOnEsc:false,closeOnClickOutside:false
        }).then(function(ok){
            if(!ok)return;
            $btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Emitiendo...');
            $.ajax({
                type:'POST',
                url:'<?php echo SERVERURL;?>core/cheques/guardar.php',
                dataType:'json',
                data:$('#formCheque').serialize()
            }).done(function(resp){
                if(resp&&resp.success===true){
                    showNotify('success',resp.title||'Cheque emitido',resp.message||'El cheque fue registrado correctamente.');
                    $('#modalCheque').modal('hide');
                    cargarCatalogos();
                    listar();
                }else{
                    showNotify('error',resp&&resp.title?resp.title:'No se pudo emitir',resp&&resp.message?resp.message:'Error desconocido.');
                }
            }).fail(function(xhr){
                showNotify('error','Error de comunicación','No se pudo emitir el cheque.');
                console.error(xhr.responseText);
            }).always(function(){
                $btn.prop('disabled',false).html('<i class="fas fa-save mr-1"></i> Emitir Cheque');
            });
        });
    }


    function abrirCategoria(){
        if (typeof window.modal_categorias_contabilidad !== 'function') {
            showNotify(
                'error',
                'Categorías no disponibles',
                'El modal público de Categorías de Gastos no está cargado en vistasModals.php.'
            );
            return;
        }

        window.modal_categorias_contabilidad();
    }

    function abrirConfigCuenta(){
        if(typeof validarAdminSistema!=='function'){
            showNotify('error','Validación no disponible','No está cargado el componente público de autenticación administrativa.');
            return;
        }

        validarAdminSistema(function(permitido){
            if(permitido!==true)return;
            cargarCatalogos(function(){
                actualizarVistaConfigCuentaCheque();
                $('#modalConfigCuentaCheque').modal({show:true,keyboard:true,backdrop:'static'});
                $('#modalConfigCuentaCheque').one('shown.bs.modal',function(){
                    actualizarVistaConfigCuentaCheque();
                    if($.fn.selectpicker) $('#config_cuenta_cheque').selectpicker('refresh');
                });
            });
        },{
            mensaje:'Para cambiar la cuenta utilizada por Cheques debe validar un administrador.',
            modulo:'Cheques',
            accion:'Cambiar cuenta de cheques',
            referencia_id:'4',
            referencia_texto:'Tipo de pago Cheque',
            motivo:'Cambio de cuenta contable asociada al medio de pago Cheque'
        });
    }

    function guardarConfigCuenta(){
        var cuenta=$('#config_cuenta_cheque').val();
        if(!cuenta){showNotify('warning','Cuenta requerida','Seleccione una cuenta contable.');return;}

        var actual=chequesState.catalogos.cuenta_cheque;
        if(actual && parseInt(actual.cuentas_id,10)===parseInt(cuenta,10)){
            showNotify('info','Sin cambios','La cuenta seleccionada ya es la cuenta configurada para Cheques.');
            return;
        }
        if(typeof AUTH_ADMIN_SISTEMA_TOKEN==='undefined'||!AUTH_ADMIN_SISTEMA_TOKEN){
            showNotify('error','Validación requerida','Vuelva a validar un administrador antes de guardar.');
            return;
        }

        swal({
            title:'¿Cambiar cuenta de cheques?',
            text:'Los nuevos cheques se debitarán de la cuenta seleccionada. Los cheques históricos no serán modificados.',
            icon:'warning',
            buttons:{cancel:{text:'Cancelar',visible:true},confirm:{text:'Sí, cambiar'}},
            closeOnEsc:false,closeOnClickOutside:false
        }).then(function(ok){
            if(!ok)return;
            var $btn=$('#btnGuardarConfigCuentaCheque').prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');
            $.ajax({
                type:'POST',
                url:'<?php echo SERVERURL;?>core/cheques/actualizarCuentaCheque.php',
                dataType:'json',
                data:{token:AUTH_ADMIN_SISTEMA_TOKEN,cuentas_id:cuenta}
            }).done(function(resp){
                if(resp&&resp.success){
                    showNotify('success',resp.title||'Cuenta actualizada',resp.message||'Configuración guardada.');
                    $('#modalConfigCuentaCheque').modal('hide');
                    cargarCatalogos();
                }else showNotify('error',resp&&resp.title?resp.title:'Error',resp&&resp.message?resp.message:'No se pudo guardar.');
            }).fail(function(xhr){showNotify('error','Error de comunicación','No se pudo guardar la configuración.');console.error(xhr.responseText);})
            .always(function(){$btn.prop('disabled',false).html('<i class="fas fa-save mr-1"></i> Guardar Configuración');});
        });
    }

    function anularCheque(row){
        if(!row||parseInt(row.estado||0,10)!==1)return;
        if(typeof validarAdminSistema!=='function'){
            showNotify('error','Validación no disponible','No está cargado el componente de autenticación administrativa.');
            return;
        }

        validarAdminSistema(function(permitido){
            if(permitido!==true)return;

            swal({
                title:'Anular cheque #'+(row.numero_cheque||row.cheque_id),
                text:'Escriba el motivo de la anulación. El egreso quedará inactivo y el importe será reintegrado a la cuenta.',
                content:{element:'input',attributes:{placeholder:'Motivo de la anulación',type:'text'}},
                icon:'warning',
                buttons:{cancel:{text:'Cancelar',visible:true},confirm:{text:'Anular y reintegrar'}},
                dangerMode:true,closeOnEsc:false,closeOnClickOutside:false
            }).then(function(motivo){
                if(motivo===null)return;
                motivo=$.trim(motivo||'');
                if(!motivo){showNotify('warning','Motivo requerido','Debe indicar el motivo de la anulación.');return;}

                $.ajax({
                    type:'POST',
                    url:'<?php echo SERVERURL;?>core/cheques/anular.php',
                    dataType:'json',
                    data:{token:AUTH_ADMIN_SISTEMA_TOKEN,cheque_id:row.cheque_id,motivo:motivo}
                }).done(function(resp){
                    if(resp&&resp.success){
                        showNotify('success',resp.title||'Cheque anulado',resp.message||'Cheque anulado correctamente.');
                        cargarCatalogos();
                        listar();
                    }else showNotify('error',resp&&resp.title?resp.title:'No se pudo anular',resp&&resp.message?resp.message:'Error desconocido.');
                }).fail(function(xhr){showNotify('error','Error de comunicación','No se pudo anular el cheque.');console.error(xhr.responseText);});
            });
        },{
            mensaje:'Anular un cheque reintegra fondos y modifica contabilidad. Valide un administrador.',
            modulo:'Cheques',
            accion:'Anular cheque',
            referencia_id:row.cheque_id,
            referencia_texto:row.numero_cheque||'',
            motivo:'Anulación de cheque con reintegro contable'
        });
    }

    function logoPdf(callback){
        if(typeof imagen==='string'&&imagen.indexOf('data:image/')===0){callback(imagen);return;}
        $.ajax({type:'GET',url:'<?php echo SERVERURL;?>core/get_image.php',dataType:'text',timeout:15000})
        .done(function(url){
            url=$.trim(url||''); if(!url){callback(null);return;}
            var img=new Image();img.crossOrigin='Anonymous';
            img.onload=function(){try{var c=document.createElement('canvas');c.width=img.naturalWidth||img.width;c.height=img.naturalHeight||img.height;c.getContext('2d').drawImage(img,0,0);imagen=c.toDataURL('image/png');callback(imagen);}catch(e){callback(null);}};
            img.onerror=function(){callback(null);};img.src=url;
        }).fail(function(){callback(null);});
    }

    function pdfDocumento(rows,titulo,archivo,soloCheque){
        if(typeof pdfMake==='undefined'||typeof abrirModalPdfPublico!=='function'){showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');return;}
        logoPdf(function(logo){
            var total=0;
            var body=[[{text:'FECHA',style:'th'},{text:'CHEQUE',style:'th'},{text:'BENEFICIARIO',style:'th'},{text:'CUENTA',style:'th'},{text:'CATEGORÍA',style:'th'},{text:'FACTURA',style:'th'},{text:'IMPORTE',style:'th'},{text:'ESTADO',style:'th'}]];
            rows.forEach(function(r,i){
                total+=chequesNum(r.importe_valor);
                var fill=i%2===0?'#FFFFFF':'#F7F9FC';
                body.push([
                    {text:String(r.fecha||''),style:'td',fillColor:fill},
                    {text:String(r.numero_cheque||r.cheque_id||''),style:'td',fillColor:fill},
                    {text:String(r.proveedor||''),style:'td',fillColor:fill},
                    {text:String((r.cuenta_codigo?r.cuenta_codigo+' - ':'')+(r.cuenta_nombre||'')),style:'td',fillColor:fill},
                    {text:String(r.categoria||''),style:'td',fillColor:fill},
                    {text:String(r.factura||''),style:'td',fillColor:fill},
                    {text:chequesMoney(r.importe_valor),style:'tdn',fillColor:fill,bold:true},
                    {text:parseInt(r.estado,10)===1?'Activo':'Anulado',style:'td',fillColor:fill,color:parseInt(r.estado,10)===1?'#14804A':'#C9372C'}
                ]);
            });
            body.push([{text:'TOTAL',colSpan:6,style:'totalLabel',fillColor:'#EAF1F7'},{},{},{},{},{},{text:chequesMoney(total),style:'totalMoney',fillColor:'#EAF1F7'},{text:'',fillColor:'#EAF1F7'}]);

            var logoCell=logo?{table:{widths:['*'],body:[[{image:logo,fit:[74,44],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]}:{text:'IZZY',bold:true,fontSize:18,color:'#17324D',alignment:'center',fillColor:'#FFFFFF',margin:[8,14,8,14]};
            var doc={
                pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],
                header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},
                footer:function(page,pages){return{margin:[28,8,28,0],columns:[{text:'IZZY • Cheques',fontSize:7,color:'#7A869A'},{text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}]};},
                content:[
                    {table:{widths:[100,'*',145],body:[[logoCell,{stack:[{text:titulo,bold:true,fontSize:16,color:'#FFFFFF'},{text:soloCheque?'Comprobante contable de cheque':'Control de emisión y anulación de cheques',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}],fillColor:'#17324D',margin:[0,10,0,10]},{stack:[{text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},{text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}],fillColor:'#17324D',margin:[0,10,12,10]}]]},layout:'noBorders',margin:[0,0,0,10]},
                    {table:{headerRows:1,widths:[58,84,126,100,80,72,72,80],body:body},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return.55;},vLineWidth:function(){return.55;},paddingLeft:function(){return 4;},paddingRight:function(){return 4;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}}}
                ],
                styles:{th:{fontSize:5.8,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},td:{fontSize:6,color:'#253858',noWrap:false},tdn:{fontSize:6,color:'#253858',alignment:'right'},totalLabel:{fontSize:6.3,bold:true,color:'#17324D'},totalMoney:{fontSize:6.3,bold:true,color:'#17324D',alignment:'right'}}
            };
            pdfMake.createPdf(doc).getDataUrl(function(url){abrirModalPdfPublico(url,titulo,archivo);});
        });
    }


    function chequeNumeroALetras(numero) {
        numero = Math.round(chequesNum(numero) * 100) / 100;

        var entero = Math.floor(numero);
        var centavos = Math.round((numero - entero) * 100);

        function unidad(n) {
            return ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'][n] || '';
        }

        function decena(n) {
            var esp = {
                10:'diez',11:'once',12:'doce',13:'trece',14:'catorce',15:'quince',
                20:'veinte',30:'treinta',40:'cuarenta',50:'cincuenta',
                60:'sesenta',70:'setenta',80:'ochenta',90:'noventa'
            };

            if (esp[n]) return esp[n];
            if (n < 10) return unidad(n);
            if (n < 20) return 'dieci' + unidad(n - 10);
            if (n < 30) return 'veinti' + unidad(n - 20);

            var d = Math.floor(n / 10) * 10;
            var u = n % 10;
            return esp[d] + (u ? ' y ' + unidad(u) : '');
        }

        function centena(n) {
            if (n === 100) return 'cien';

            var centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos',
                'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

            var c = Math.floor(n / 100);
            var r = n % 100;

            return (c ? centenas[c] + (r ? ' ' : '') : '') + (r ? decena(r) : '');
        }

        function bloque(n) {
            if (n < 1000) return centena(n);

            if (n < 1000000) {
                var m = Math.floor(n / 1000);
                var r = n % 1000;
                return (m === 1 ? 'mil' : centena(m) + ' mil') + (r ? ' ' + centena(r) : '');
            }

            if (n < 1000000000) {
                var mill = Math.floor(n / 1000000);
                var rem = n % 1000000;
                return (mill === 1 ? 'un millón' : bloque(mill) + ' millones') + (rem ? ' ' + bloque(rem) : '');
            }

            return String(n);
        }

        var texto = entero === 0 ? 'cero' : bloque(entero);
        texto = texto.replace(/\buno mil\b/g,'un mil').replace(/\buno millones\b/g,'un millones');

        return texto.charAt(0).toUpperCase() + texto.slice(1) +
            ' lempiras con ' + String(centavos).padStart(2,'0') + '/100';
    }

    function imprimirCheque(row) {
        if (!row) return;

        if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
            showNotify('error','Impresión no disponible','No están disponibles los componentes para generar el cheque.');
            return;
        }

        logoPdf(function(logo) {
            var monto = chequesNum(row.importe_valor);
            var montoLetras = chequeNumeroALetras(monto);
            var cuenta = (row.cuenta_codigo ? row.cuenta_codigo + ' - ' : '') + (row.cuenta_nombre || '');
            var numero = row.numero_cheque || row.cheque_id || '';
            var fecha = row.fecha || '';
            var anulado = parseInt(row.estado || 0, 10) === 0;

            var headerLeft = logo
                ? {image:logo,fit:[80,40],alignment:'left'}
                : {text:'IZZY',fontSize:18,bold:true,color:'#17324D'};

            var contenido = [
                {
                    columns:[
                        {width:90,stack:[headerLeft]},
                        {
                            width:'*',
                            stack:[
                                {text:'CHEQUE',fontSize:16,bold:true,color:'#17324D',alignment:'center'},
                                {text:cuenta,fontSize:7,color:'#5E6C84',alignment:'center',margin:[0,2,0,0]}
                            ]
                        },
                        {
                            width:130,
                            stack:[
                                {text:'No. '+String(numero),fontSize:12,bold:true,color:'#17324D',alignment:'right'},
                                {text:'Fecha: '+String(fecha),fontSize:8,color:'#52627A',alignment:'right',margin:[0,3,0,0]}
                            ]
                        }
                    ],
                    margin:[0,0,0,12]
                },
                {
                    columns:[
                        {text:'PÁGUESE A LA ORDEN DE:',fontSize:7,bold:true,color:'#6B778C',width:118,margin:[0,5,0,0]},
                        {text:String(row.proveedor || ''),fontSize:12,bold:true,color:'#172B4D',decoration:'underline',decorationStyle:'solid',width:'*'},
                        {text:chequesMoney(monto),fontSize:13,bold:true,color:'#172B4D',alignment:'right',width:110}
                    ],
                    margin:[0,0,0,10]
                },
                {
                    columns:[
                        {text:'LA SUMA DE:',fontSize:7,bold:true,color:'#6B778C',width:75,margin:[0,4,0,0]},
                        {text:montoLetras,fontSize:9,bold:true,color:'#253858',decoration:'underline',width:'*'}
                    ],
                    margin:[0,0,0,10]
                },
                {
                    columns:[
                        {
                            width:'*',
                            stack:[
                                {text:'CONCEPTO',fontSize:7,bold:true,color:'#6B778C'},
                                {text:String(row.observacion || row.categoria || 'Pago mediante cheque'),fontSize:8,color:'#253858',margin:[0,3,0,0]}
                            ]
                        },
                        {
                            width:170,
                            stack:[
                                {text:'____________________________',alignment:'center',color:'#7A869A'},
                                {text:'Firma autorizada',alignment:'center',fontSize:7,color:'#6B778C'}
                            ]
                        }
                    ],
                    margin:[0,3,0,0]
                }
            ];

            if (anulado) {
                contenido.push({
                    text:'ANULADO',
                    absolutePosition:{x:205,y:82},
                    fontSize:34,
                    bold:true,
                    color:'#C9372C',
                    opacity:.22,
                    angle:-18
                });
            }

            var doc = {
                pageSize:{width:540,height:234},
                pageMargins:[24,18,24,16],
                content:[
                    {
                        canvas:[
                            {type:'rect',x:0,y:0,w:492,h:198,r:8,lineWidth:1,lineColor:'#9FB0C3'},
                            {type:'line',x1:0,y1:4,x2:492,y2:4,lineWidth:3,lineColor:'#0EA5A8'}
                        ],
                        absolutePosition:{x:24,y:18}
                    }
                ].concat(contenido),
                defaultStyle:{font:'Roboto'}
            };

            pdfMake.createPdf(doc).getDataUrl(function(url){
                abrirModalPdfPublico(
                    url,
                    'Imprimir Cheque #' + numero,
                    'Cheque_' + numero + '.pdf'
                );
            });
        });
    }

    function exportPdf(){var rows=chequesState.filtered||[];if(!rows.length){showNotify('warning','Sin información','No hay cheques para exportar.');return;}pdfDocumento(rows,'REPORTE DE CHEQUES','Reporte_Cheques.pdf',false);}
    function comprobante(row){pdfDocumento([row],'COMPROBANTE DE CHEQUE','Cheque_'+(row.numero_cheque||row.cheque_id)+'.pdf',true);}

    function exportExcel(){
        var rows=chequesState.filtered||[];
        if(!rows.length){showNotify('warning','Sin información','No hay cheques para exportar.');return;}
        if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','JSZip no está disponible.');return;}

        function esc(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
        function col(i){var n='';while(i>=0){n=String.fromCharCode((i%26)+65)+n;i=Math.floor(i/26)-1;}return n;}
        function cell(ref,v,s){return '<c r="'+ref+'" s="'+s+'" t="inlineStr"><is><t>'+esc(v)+'</t></is></c>';}

        var headers=['Fecha','Cheque','Beneficiario','Cuenta','Categoría','Factura','Importe','Estado','Observación','Fecha Anulación','Motivo Anulación'];
        var rs=[],total=0;
        rs.push('<row r="1" ht="30" customHeight="1">'+cell('A1','IZZY • REPORTE DE CHEQUES',1)+'</row>');
        rs.push('<row r="2">'+cell('A2','Período: '+$('#fechai').val()+' a '+$('#fechaf').val()+' • Registros: '+rows.length,2)+'</row>');
        rs.push('<row r="4" ht="28" customHeight="1">'+headers.map(function(h,i){return cell(col(i)+'4',h,3);}).join('')+'</row>');
        rows.forEach(function(r,i){var rr=5+i;total+=chequesNum(r.importe_valor);var vals=[r.fecha,r.numero_cheque||r.cheque_id,r.proveedor,(r.cuenta_codigo?r.cuenta_codigo+' - ':'')+r.cuenta_nombre,r.categoria,r.factura,chequesMoney(r.importe_valor),parseInt(r.estado,10)===1?'Activo':'Anulado',r.observacion,r.fecha_anulacion,r.motivo_anulacion];rs.push('<row r="'+rr+'">'+vals.map(function(v,c){return cell(col(c)+rr,v,4);}).join('')+'</row>');});
        var tr=5+rows.length;rs.push('<row r="'+tr+'">'+cell('A'+tr,'TOTAL',5)+cell('G'+tr,chequesMoney(total),5)+'</row>');

        var sheet='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:K'+tr+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="2" width="16" customWidth="1"/><col min="3" max="5" width="25" customWidth="1"/><col min="6" max="8" width="18" customWidth="1"/><col min="9" max="11" width="34" customWidth="1"/></cols><sheetData>'+rs.join('')+'</sheetData><autoFilter ref="A4:K'+(tr-1)+'"/><mergeCells count="2"><mergeCell ref="A1:K1"/><mergeCell ref="A2:K2"/></mergeCells></worksheet>';
        var styles='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="6"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF17324D"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        var workbook='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Cheques" sheetId="1" r:id="rId1"/></sheets></workbook>';
        var wr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        var rr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        var ct='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';

        var zip=new JSZip();zip.file('[Content_Types].xml',ct);zip.folder('_rels').file('.rels',rr);zip.folder('xl').file('workbook.xml',workbook);zip.folder('xl').file('styles.xml',styles);zip.folder('xl').folder('_rels').file('workbook.xml.rels',wr);zip.folder('xl').folder('worksheets').file('sheet1.xml',sheet);
        var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
        var promise=typeof zip.generateAsync==='function'?zip.generateAsync(opts):Promise.resolve(zip.generate(opts));
        promise.then(function(blob){var url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='Reporte_Cheques.xlsx';document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(url);},1000);}).catch(function(e){console.error(e);showNotify('error','Excel','No se pudo generar el Excel.');});
    }

    function init(){
        chequesPanel('#btnToggleFiltrosCheques','#chequesFiltrosContenido','izzy.cheques.filtros.visible');
        chequesPanel('#btnToggleKpisCheques','#chequesKpisContenido','izzy.cheques.kpis.visible');

        var saved='detalle';try{saved=localStorage.getItem(CHEQUES_STORAGE_VISTA)||'detalle';}catch(e){}
        chequesState.preferredView=saved==='miniatura'?'miniatura':'detalle';
        chequesState.view=chequesMovil()?'miniatura':chequesState.preferredView;
        sincronizarPageSize();sincronizarVista();

        cargarCatalogos(listar);

        $('#formMainChequesContabilidad').off('submit.cheques').on('submit.cheques',function(e){e.preventDefault();listar();});
        $('#formMainChequesContabilidad').off('reset.cheques').on('reset.cheques',function(){var f=this;setTimeout(function(){$(f).find('.selectpicker').val('').selectpicker('refresh');$('#estado_cheques').val('').selectpicker('refresh');listar();},0);});

        $('#btnChequesActualizar').off('click.cheques').on('click.cheques',function(){cargarCatalogos(listar);});
        $('#btnNuevoCheque').off('click.cheques').on('click.cheques',abrirCheque);
        $('#btnNuevaCategoriaCheque,#btnCategoriaDesdeCheque').off('click.cheques').on('click.cheques',abrirCategoria);

        $(document).off('categoriasGastos:actualizadas.cheques').on('categoriasGastos:actualizadas.cheques',function(){
            cargarCatalogos();
        });
        $('#btnConfigCuentaCheque').off('click.cheques').on('click.cheques',abrirConfigCuenta);
        $('#btnChequesExcel').off('click.cheques').on('click.cheques',exportExcel);
        $('#btnChequesPdf').off('click.cheques').on('click.cheques',exportPdf);

        $('#formCheque').off('submit.cheques').on('submit.cheques',function(e){e.preventDefault();guardarCheque();});
        $('#cheque_importe').off('input.cheques').on('input.cheques',actualizarSaldoDespuesCheque);
        $('#formConfigCuentaCheque').off('submit.cheques').on('submit.cheques',function(e){e.preventDefault();guardarConfigCuenta();});
        $('#config_cuenta_cheque').off('change.chequesConfig').on('change.chequesConfig',actualizarVistaConfigCuentaCheque);

        $('#buscarChequesListado').off('input.cheques').on('input.cheques',function(){chequesState.search=this.value||'';filtrarLocal();});
        $('#limpiarBuscarChequesListado').off('click.cheques').on('click.cheques',function(){$('#buscarChequesListado').val('').focus();chequesState.search='';filtrarLocal();});

        $('#chequesPageSize').off('change.cheques').on('change.cheques',function(){var n=parseInt(this.value,10);if(!n)return;chequesState.pageSize=n;if(chequesState.view==='miniatura')chequesState.pageSizeMiniatura=n;else chequesState.pageSizeDetalle=n;chequesState.page=1;render();});
        $('.cheques-view-btn').off('click.cheques').on('click.cheques',function(){var v=$(this).data('view');chequesState.view=chequesMovil()?'miniatura':(v==='miniatura'?'miniatura':'detalle');if(!chequesMovil()){chequesState.preferredView=chequesState.view;try{localStorage.setItem(CHEQUES_STORAGE_VISTA,chequesState.preferredView);}catch(e){}}chequesState.page=1;sincronizarPageSize();sincronizarVista();render();});
        $('#chequesPaginacion').off('click.cheques','.cheques-page-btn').on('click.cheques','.cheques-page-btn',function(){if(this.disabled)return;var p=parseInt($(this).data('page'),10);if(p){chequesState.page=p;render();}});

        $('#chequesListado').off('click.chequesAnular','.js-cheque-anular').on('click.chequesAnular','.js-cheque-anular',function(){var r=chequesState.filtered[parseInt($(this).data('index'),10)];if(r)anularCheque(r);});
        $('#chequesListado').off('click.chequesPrint','.js-cheque-imprimir').on('click.chequesPrint','.js-cheque-imprimir',function(){var r=chequesState.filtered[parseInt($(this).data('index'),10)];if(r)imprimirCheque(r);});
        $('#chequesListado').off('click.chequesPdf','.js-cheque-comprobante').on('click.chequesPdf','.js-cheque-comprobante',function(){var r=chequesState.filtered[parseInt($(this).data('index'),10)];if(r)comprobante(r);});

        $(window).off('resize.cheques orientationchange.cheques').on('resize.cheques orientationchange.cheques',function(){var target=chequesMovil()?'miniatura':chequesState.preferredView;if(chequesState.view!==target){chequesState.view=target;chequesState.page=1;sincronizarPageSize();render();}sincronizarVista();});
    }

    $(init);
})(jQuery);
</script>
