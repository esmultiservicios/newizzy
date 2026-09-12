<script>
var MOVIMIENTOS_MOBILE_QUERY = '(max-width: 767.98px)';
var MOVIMIENTOS_STORAGE_VISTA = 'izzy.movimientosContabilidad.tipo_vista';

var movimientosUI = {
    rows: [],
    filtered: [],
    page: 1,
    pageSize: 10,
    pageSizeDetalle: 10,
    pageSizeMiniatura: 6,
    view: 'detalle',
    preferredView: 'detalle',
    search: '',
    loading: false
};

$(() => {
    inicializarMovimientosUI();
    listar_movimientos_contabilidad();

    $('#formMainMovimientosContabilidad').on('submit', function(e){
        e.preventDefault();
        listar_movimientos_contabilidad();
    });

    $('#formMainMovimientosContabilidad').on('reset', function(){
        var form=this;
        setTimeout(function(){
            $(form).find('.selectpicker').val('').selectpicker('refresh');
            listar_movimientos_contabilidad();
        },0);
    });

    $('#formMainMovimientosContabilidad input').on('keypress', function(e){
        if(e.which===13){
            e.preventDefault();
            listar_movimientos_contabilidad();
        }
    });
});

function movimientosEsMovil(){
    return window.matchMedia ? window.matchMedia(MOVIMIENTOS_MOBILE_QUERY).matches : $(window).width()<=767;
}

function movimientosEscape(value){
    return String(value==null?'':value)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function movimientosNumero(value){
    if(typeof value==='number') return isFinite(value)?value:0;
    var normalized=String(value==null?'':value).replace(/,/g,'').trim();
    var match=normalized.match(/-?\d+(?:\.\d+)?/);
    return match?parseFloat(match[0]):0;
}

function movimientosMoney(value){
    return 'L ' + movimientosNumero(value).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function movimientosTipo(row){
    var ingreso=movimientosNumero(row.ingreso);
    var egreso=movimientosNumero(row.egreso);
    if(ingreso>0) return 'Ingreso';
    if(egreso>0) return 'Egreso';
    return 'Sin movimiento';
}

function movimientosTipoBadge(row){
    var tipo=movimientosTipo(row);
    if(tipo==='Ingreso') return '<span class="badge-movimiento badge-movimiento-ingreso"><i class="fas fa-arrow-down"></i> Ingreso</span>';
    if(tipo==='Egreso') return '<span class="badge-movimiento badge-movimiento-egreso"><i class="fas fa-arrow-up"></i> Egreso</span>';
    return '<span class="badge-movimiento badge-movimiento-neutro"><i class="fas fa-minus-circle"></i> Sin movimiento</span>';
}

function movimientosInversionBadge(row){
    return parseInt(row.es_inversion||0,10)===1
        ? '<span class="badge-cuenta-inversion"><i class="fas fa-seedling"></i> Inversión</span>'
        : '';
}

function movimientosConfigurarPanel(btn, contenido, key){
    var visible=true;
    try{ var saved=localStorage.getItem(key); if(saved!==null) visible=saved==='1'; }catch(e){}
    function sync(){
        $(contenido).toggle(visible);
        $(btn).attr('aria-expanded',visible?'true':'false');
        $(btn).find('span').text(visible?'Ocultar':'Mostrar');
        $(btn).find('i').toggleClass('fa-chevron-up',visible).toggleClass('fa-chevron-down',!visible);
    }
    sync();
    $(btn).off('click.movPanel').on('click.movPanel',function(){
        visible=!visible;
        $(contenido).stop(true,true)[visible?'slideDown':'slideUp'](160);
        sync();
        try{localStorage.setItem(key,visible?'1':'0');}catch(e){}
    });
}

function movimientosSincronizarPageSize(){
    var mini=movimientosUI.view==='miniatura';
    var opts=mini?[6,12,18,30]:[10,25,50,100];
    var pref=mini?movimientosUI.pageSizeMiniatura:movimientosUI.pageSizeDetalle;
    if(opts.indexOf(pref)===-1) pref=opts[0];
    movimientosUI.pageSize=pref;
    var $s=$('#movimientosPageSize').empty();
    opts.forEach(function(n){$s.append($('<option></option>').val(n).text(n));});
    $s.val(String(pref));
}

function movimientosSincronizarVista(){
    var mobile=movimientosEsMovil();
    $('.movimientos-view-btn[data-view="detalle"]').toggleClass('d-none',mobile).prop('disabled',mobile);
    $('.movimientos-view-btn').removeClass('active').attr('aria-pressed','false');
    $('.movimientos-view-btn[data-view="'+movimientosUI.view+'"]').addClass('active').attr('aria-pressed','true');
}

function movimientosFiltrarLocal(){
    var q=$.trim(movimientosUI.search||'').toLowerCase();
    movimientosUI.filtered=!q?movimientosUI.rows.slice():movimientosUI.rows.filter(function(r){
        return [r.fecha,r.codigo,r.nombre,movimientosTipo(r),r.ingreso,r.egreso,r.saldo,parseInt(r.es_inversion||0,10)===1?'inversion inversión':'']
            .map(function(v){return String(v==null?'':v).toLowerCase();}).join(' ').indexOf(q)!==-1;
    });
    movimientosActualizarResumen();
}

function movimientosCalcularResumen(rows){
    var totalIngreso=0,totalEgreso=0,cuentas={},ultimoSaldoPorCuenta={};
    rows.forEach(function(row,index){
        var codigo=String(row.codigo||'');
        var ingreso=movimientosNumero(row.ingreso),egreso=movimientosNumero(row.egreso),saldo=movimientosNumero(row.saldo);
        var fecha=String(row.fecha||'');
        totalIngreso+=ingreso; totalEgreso+=egreso;
        if(codigo!==''){
            cuentas[codigo]=true;
            var actual=ultimoSaldoPorCuenta[codigo];
            if(!actual || fecha>actual.fecha || (fecha===actual.fecha && index>actual.index)){
                ultimoSaldoPorCuenta[codigo]={fecha:fecha,saldo:saldo,index:index};
            }
        }
    });
    var saldoFinal=0;
    Object.keys(ultimoSaldoPorCuenta).forEach(function(c){saldoFinal+=movimientosNumero(ultimoSaldoPorCuenta[c].saldo);});
    return {ingresos:totalIngreso,egresos:totalEgreso,balance:totalIngreso-totalEgreso,saldoFinal:saldoFinal,movimientos:rows.length,cuentas:Object.keys(cuentas).length};
}

function movimientosActualizarResumen(){
    var r=movimientosCalcularResumen(movimientosUI.filtered);
    $('#resumen_total_ingresos,#movimientosTotalIngresos').text(movimientosMoney(r.ingresos));
    $('#resumen_total_egresos,#movimientosTotalEgresos').text(movimientosMoney(r.egresos));
    $('#resumen_balance_periodo,#movimientosTotalBalance').text(movimientosMoney(r.balance));
    $('#resumen_saldo_final,#movimientosTotalSaldo').text(movimientosMoney(r.saldoFinal));
    $('#resumen_movimientos').text(r.movimientos+(r.movimientos===1?' movimiento':' movimientos'));
    $('#resumen_cuentas').text(r.cuentas+(r.cuentas===1?' cuenta':' cuentas'));
}

function movimientosRenderDetalle(rows){
    var html='<div class="movimientos-detail-header"><div>Fecha</div><div>Cuenta</div><div>Nombre</div><div>Tipo</div><div>Ingreso</div><div>Egreso</div><div>Saldo</div></div>';
    rows.forEach(function(r){
        var saldo=movimientosNumero(r.saldo);
        html+='<article class="movimientos-detail-row">'+
            '<div class="movimientos-cell"><span class="movimientos-cell-label">Fecha</span><strong>'+movimientosEscape(r.fecha||'')+'</strong></div>'+
            '<div class="movimientos-cell"><span class="movimientos-cell-label">Cuenta</span><strong>'+movimientosEscape(r.codigo||'Sin código')+'</strong></div>'+
            '<div class="movimientos-cell"><span class="movimientos-cell-label">Nombre</span><div class="movimientos-account"><strong>'+movimientosEscape(r.nombre||'Sin nombre')+'</strong>'+movimientosInversionBadge(r)+'</div></div>'+
            '<div class="movimientos-cell movimientos-center"><span class="movimientos-cell-label">Tipo</span>'+movimientosTipoBadge(r)+'</div>'+
            '<div class="movimientos-cell movimientos-money text-success"><span class="movimientos-cell-label">Ingreso</span><strong>'+movimientosMoney(r.ingreso)+'</strong></div>'+
            '<div class="movimientos-cell movimientos-money text-danger"><span class="movimientos-cell-label">Egreso</span><strong>'+movimientosMoney(r.egreso)+'</strong></div>'+
            '<div class="movimientos-cell movimientos-money '+(saldo<0?'text-danger':saldo>0?'text-success':'movimientos-neutral')+'"><span class="movimientos-cell-label">Saldo</span><strong>'+movimientosMoney(r.saldo)+'</strong></div>'+
        '</article>';
    });
    return html;
}

function movimientosRenderMiniatura(rows){
    var html='<div class="movimientos-mini-grid">';
    rows.forEach(function(r){
        var saldo=movimientosNumero(r.saldo);
        html+='<article class="movimientos-mini-card">'+
            '<div class="movimientos-mini-topline '+(movimientosTipo(r)==='Egreso'?'is-egreso':movimientosTipo(r)==='Ingreso'?'is-ingreso':'is-neutral')+'"></div>'+
            '<div class="movimientos-mini-header"><div class="movimientos-mini-title"><h4>'+movimientosEscape(r.nombre||'Sin nombre')+'</h4><span><i class="fas fa-hashtag mr-1"></i>'+movimientosEscape(r.codigo||'Sin código')+'</span></div>'+movimientosTipoBadge(r)+'</div>'+
            '<div class="movimientos-mini-body">'+
                '<div class="movimientos-mini-field"><span>Fecha</span><strong>'+movimientosEscape(r.fecha||'')+'</strong></div>'+
                '<div class="movimientos-mini-field"><span>Tipo Cuenta</span><strong>'+(parseInt(r.es_inversion||0,10)===1?'Inversión':'Normal')+'</strong></div>'+
                '<div class="movimientos-mini-field"><span>Ingreso</span><strong class="text-success">'+movimientosMoney(r.ingreso)+'</strong></div>'+
                '<div class="movimientos-mini-field"><span>Egreso</span><strong class="text-danger">'+movimientosMoney(r.egreso)+'</strong></div>'+
                '<div class="movimientos-mini-field movimientos-mini-field-full"><span>Saldo</span><strong class="'+(saldo<0?'text-danger':saldo>0?'text-success':'')+'">'+movimientosMoney(r.saldo)+'</strong></div>'+
            '</div>'+movimientosInversionBadge(r)+
        '</article>';
    });
    return html+'</div>';
}

function movimientosRenderPaginacion(totalPages){
    var current=movimientosUI.page,html='';
    function b(label,page,disabled,active,icon){return '<button type="button" class="movimientos-page-btn'+(active?' active':'')+'" data-page="'+page+'"'+(disabled?' disabled':'')+'>'+(icon?'<i class="'+icon+' mr-1"></i>':'')+label+'</button>';}
    html+=b('Inicio',1,current===1,false,'fas fa-angle-double-left');
    html+=b('Anterior',current-1,current===1,false,'fas fa-angle-left');
    var from=Math.max(1,current-2),to=Math.min(totalPages,from+4); from=Math.max(1,to-4);
    for(var p=from;p<=to;p++) html+=b(String(p),p,false,p===current,'');
    html+=b('Siguiente',current+1,current===totalPages,false,'fas fa-angle-right');
    html+=b('Final',totalPages,current===totalPages,false,'fas fa-angle-double-right');
    $('#movimientosPaginacion').html(html);
}

function movimientosRender(){
    if(movimientosUI.loading){
        $('#movimientosListado').html('<div class="movimientos-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando movimientos</strong><span>Consultando información...</span></div>');
        $('#movimientosInfo').text('0 registros'); $('#movimientosPaginacion').empty(); return;
    }
    var rows=movimientosUI.filtered||[];
    if(!rows.length){
        $('#movimientosListado').html('<div class="movimientos-state"><i class="fas fa-exchange-alt"></i><strong>Sin movimientos</strong><span>No se encontraron registros con los filtros actuales.</span></div>');
        $('#movimientosInfo').text('0 registros'); $('#movimientosPaginacion').empty(); return;
    }
    var pages=Math.max(1,Math.ceil(rows.length/movimientosUI.pageSize));
    if(movimientosUI.page>pages) movimientosUI.page=pages;
    var offset=(movimientosUI.page-1)*movimientosUI.pageSize;
    var pageRows=rows.slice(offset,offset+movimientosUI.pageSize);
    $('#movimientosListado').removeClass('vista-detalle vista-miniatura').addClass('vista-'+movimientosUI.view)
        .html(movimientosUI.view==='miniatura'?movimientosRenderMiniatura(pageRows):movimientosRenderDetalle(pageRows));
    $('#movimientosInfo').text('Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros');
    movimientosRenderPaginacion(pages);
    if(typeof getPermisosTipoUsuarioAccesosTable==='function' && typeof getPrivilegioTipoUsuario==='function') getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
}

var listar_movimientos_contabilidad=function(){
    movimientosUI.loading=true; movimientosRender();
    $.ajax({
        method:'POST',
        url:'<?php echo SERVERURL;?>core/llenarDataTableMovimientosCuentasContabilidad.php',
        dataType:'json',
        data:{
            fechai:$('#fechai').val(), fechaf:$('#fechaf').val(), cuenta_busqueda:$('#cuenta_busqueda').val(),
            tipo_movimiento:$('#tipo_movimiento').val(), monto_desde:$('#monto_desde').val(), monto_hasta:$('#monto_hasta').val()
        }
    }).done(function(resp){
        movimientosUI.rows=resp&&Array.isArray(resp.data)?resp.data:[];
        movimientosUI.search=$('#buscarMovimientos').val()||'';
        movimientosUI.page=1; movimientosUI.loading=false;
        movimientosFiltrarLocal(); movimientosRender();
        $('#cuenta_busqueda').focus();
    }).fail(function(xhr){
        movimientosUI.rows=[]; movimientosUI.filtered=[]; movimientosUI.loading=false;
        movimientosActualizarResumen(); movimientosRender();
        console.error('Error movimientos contabilidad:',xhr.responseText);
        if(typeof showNotify==='function') showNotify('error','Error','No se pudo cargar el listado de movimientos contables.');
    });
};

function movimientosExcelEscape(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function movimientosExcelCol(i){var n='';while(i>=0){n=String.fromCharCode((i%26)+65)+n;i=Math.floor(i/26)-1;}return n;}
function movimientosExcelCell(ref,v,style){return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+movimientosExcelEscape(v)+'</t></is></c>';}

function movimientosExportarExcel(){
    var rows=movimientosUI.filtered||[];
    if(!rows.length){showNotify('warning','Sin información','No hay movimientos para exportar.');return;}
    if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','JSZip no está disponible.');return;}
    var resumen=movimientosCalcularResumen(rows);
    var headers=['Fecha','Cuenta','Nombre','Tipo','Ingreso','Egreso','Saldo'];
    var sheetRows=[];
    sheetRows.push('<row r="1" ht="30" customHeight="1">'+movimientosExcelCell('A1','IZZY • REPORTE DE MOVIMIENTOS CONTABLES',1)+'</row>');
    sheetRows.push('<row r="2">'+movimientosExcelCell('A2','Período: '+($('#fechai').val()||'')+' a '+($('#fechaf').val()||'')+' • Registros: '+rows.length,2)+'</row>');
    sheetRows.push('<row r="3">'+movimientosExcelCell('A3','Ingresos: '+movimientosMoney(resumen.ingresos)+' | Egresos: '+movimientosMoney(resumen.egresos)+' | Balance: '+movimientosMoney(resumen.balance)+' | Saldo final: '+movimientosMoney(resumen.saldoFinal),2)+'</row>');
    sheetRows.push('<row r="5" ht="26" customHeight="1">'+headers.map(function(h,i){return movimientosExcelCell(movimientosExcelCol(i)+'5',h,3);}).join('')+'</row>');
    rows.forEach(function(r,i){var rr=6+i;var vals=[r.fecha,r.codigo,r.nombre,movimientosTipo(r),movimientosMoney(r.ingreso),movimientosMoney(r.egreso),movimientosMoney(r.saldo)];sheetRows.push('<row r="'+rr+'">'+vals.map(function(v,c){return movimientosExcelCell(movimientosExcelCol(c)+rr,v,4);}).join('')+'</row>');});
    var totalRow=6+rows.length;
    sheetRows.push('<row r="'+totalRow+'">'+movimientosExcelCell('A'+totalRow,'TOTALES',5)+movimientosExcelCell('B'+totalRow,'',5)+movimientosExcelCell('C'+totalRow,'',5)+movimientosExcelCell('D'+totalRow,'',5)+movimientosExcelCell('E'+totalRow,movimientosMoney(resumen.ingresos),5)+movimientosExcelCell('F'+totalRow,movimientosMoney(resumen.egresos),5)+movimientosExcelCell('G'+totalRow,movimientosMoney(resumen.saldoFinal),5)+'</row>');
    var sheetXml='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:G'+totalRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="2" width="15" customWidth="1"/><col min="3" max="3" width="30" customWidth="1"/><col min="4" max="4" width="18" customWidth="1"/><col min="5" max="7" width="18" customWidth="1"/></cols><sheetData>'+sheetRows.join('')+'</sheetData><autoFilter ref="A5:G'+(totalRow-1)+'"/><mergeCells count="3"><mergeCell ref="A1:G1"/><mergeCell ref="A2:G2"/><mergeCell ref="A3:G3"/></mergeCells></worksheet>';
    var stylesXml='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="6"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF17324D"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    var workbookXml='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Movimientos" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var workbookRels='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var rootRels='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var contentTypes='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    var zip=new JSZip();zip.file('[Content_Types].xml',contentTypes);zip.folder('_rels').file('.rels',rootRels);zip.folder('xl').file('workbook.xml',workbookXml);zip.folder('xl').file('styles.xml',stylesXml);zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels);zip.folder('xl').folder('worksheets').file('sheet1.xml',sheetXml);
    var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
    var promise=typeof zip.generateAsync==='function'?zip.generateAsync(opts):(typeof zip.generate==='function'?Promise.resolve(zip.generate(opts)):Promise.reject(new Error('JSZip no soportado')));
    promise.then(function(blob){var url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='Reporte_Movimientos_Contables.xlsx';document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(url);},1000);}).catch(function(e){console.error(e);showNotify('error','Error','No se pudo generar el Excel.');});
}

function movimientosObtenerLogoPdf(callback){
    if(typeof imagen==='string' && imagen.indexOf('data:image/')===0){callback(imagen);return;}
    $.ajax({type:'GET',url:'<?php echo SERVERURL;?>core/get_image.php',dataType:'text',timeout:15000}).done(function(url){
        url=$.trim(url||'');if(!url){callback(null);return;}var img=new Image();img.crossOrigin='Anonymous';
        img.onload=function(){try{var c=document.createElement('canvas');c.width=img.naturalWidth||img.width;c.height=img.naturalHeight||img.height;c.getContext('2d').drawImage(img,0,0);imagen=c.toDataURL('image/png');callback(imagen);}catch(e){callback(null);}};
        img.onerror=function(){callback(null);};img.src=url;
    }).fail(function(){callback(null);});
}

function movimientosExportarPdf(){
    var rows=movimientosUI.filtered||[];
    if(!rows.length){showNotify('warning','Sin información','No hay movimientos para mostrar en PDF.');return;}
    if(typeof pdfMake==='undefined'||typeof abrirModalPdfPublico!=='function'){showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');return;}
    var resumen=movimientosCalcularResumen(rows);
    movimientosObtenerLogoPdf(function(logo){
        var body=[[{text:'FECHA',style:'th'},{text:'CUENTA',style:'th'},{text:'NOMBRE',style:'th'},{text:'TIPO',style:'th'},{text:'INGRESO',style:'th'},{text:'EGRESO',style:'th'},{text:'SALDO',style:'th'}]];
        rows.forEach(function(r,i){var fill=i%2===0?'#FFFFFF':'#F7F9FC',saldo=movimientosNumero(r.saldo);body.push([
            {text:String(r.fecha||''),style:'td',fillColor:fill},{text:String(r.codigo||''),style:'td',fillColor:fill},{text:String(r.nombre||''),style:'td',fillColor:fill},{text:movimientosTipo(r),style:'td',fillColor:fill},
            {text:movimientosMoney(r.ingreso),style:'tdn',fillColor:fill,color:'#14804A'},{text:movimientosMoney(r.egreso),style:'tdn',fillColor:fill,color:'#C9372C'},{text:movimientosMoney(r.saldo),style:'tdn',fillColor:fill,color:saldo<0?'#C9372C':saldo>0?'#14804A':'#253858'}
        ]);});
        body.push([{text:'TOTALES',colSpan:4,style:'totalLabel',fillColor:'#EAF1F7'},{},{},{},{text:movimientosMoney(resumen.ingresos),style:'totalSuccess',fillColor:'#EAF1F7'},{text:movimientosMoney(resumen.egresos),style:'totalDanger',fillColor:'#EAF1F7'},{text:movimientosMoney(resumen.saldoFinal),style:'totalCurrent',fillColor:'#EAF1F7'}]);
        var logoCell=logo?{table:{widths:['*'],body:[[{image:logo,fit:[72,42],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]}:{text:'IZZY',bold:true,fontSize:18,color:'#17324D',alignment:'center',fillColor:'#FFFFFF',margin:[8,14,8,14]};
        var kpis={table:{widths:['*','*','*','*'],body:[[
            {stack:[{text:'INGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},{text:movimientosMoney(resumen.ingresos),fontSize:11,bold:true,color:'#14804A',margin:[0,2,0,0]}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'EGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},{text:movimientosMoney(resumen.egresos),fontSize:11,bold:true,color:'#C9372C',margin:[0,2,0,0]}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'BALANCE',fontSize:6.2,bold:true,color:'#6B778C'},{text:movimientosMoney(resumen.balance),fontSize:11,bold:true,color:'#17324D',margin:[0,2,0,0]}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'SALDO FINAL',fontSize:6.2,bold:true,color:'#6B778C'},{text:movimientosMoney(resumen.saldoFinal),fontSize:11,bold:true,color:'#6554C0',margin:[0,2,0,0]}],fillColor:'#F7F9FC',margin:[8,7,8,7]}
        ]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .5;},vLineWidth:function(){return .5;}},margin:[0,0,0,10]};
        var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],header:function(){return {margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(page,pages){return {margin:[28,8,28,0],columns:[{text:'IZZY • Movimientos Contables',fontSize:7,color:'#7A869A'},{text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[
            {table:{widths:[110,'*',160],body:[[logoCell,{stack:[{text:'REPORTE DE MOVIMIENTOS CONTABLES',bold:true,fontSize:16,color:'#FFFFFF'},{text:'Ingresos, egresos y saldo por cuenta',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}],fillColor:'#17324D',margin:[0,10,0,10]},{stack:[{text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},{text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}],fillColor:'#17324D',margin:[0,10,12,10]}]]},layout:'noBorders',margin:[0,0,0,10]},
            {table:{widths:['*'],body:[[{text:'Período: '+($('#fechai').val()||'')+' a '+($('#fechaf').val()||'')+'   |   Cuenta: '+($('#cuenta_busqueda').val()||'Todas')+'   |   Tipo: '+($('#tipo_movimiento option:selected').text()||'Todos')+'   |   Búsqueda: '+($.trim($('#buscarMovimientos').val())||'Sin búsqueda'),fontSize:7,color:'#52627A',fillColor:'#F7F9FC',margin:[8,6,8,6]}]]},layout:'lightHorizontalLines',margin:[0,0,0,10]},
            kpis,
            {table:{headerRows:1,widths:[70,75,150,80,95,95,95],body:body},margin:[0,0,0,0],layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 5;},paddingRight:function(){return 5;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}}}
        ],styles:{th:{fontSize:6,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},td:{fontSize:6.1,color:'#253858',noWrap:false},tdn:{fontSize:6.1,color:'#253858',alignment:'right',noWrap:false},totalLabel:{fontSize:6.4,bold:true,color:'#17324D'},totalSuccess:{fontSize:6.4,bold:true,color:'#14804A',alignment:'right'},totalDanger:{fontSize:6.4,bold:true,color:'#C9372C',alignment:'right'},totalCurrent:{fontSize:6.4,bold:true,color:'#6554C0',alignment:'right'}}};
        pdfMake.createPdf(doc).getDataUrl(function(url){abrirModalPdfPublico(url,'Reporte de Movimientos Contables','Reporte_Movimientos_Contables.pdf');});
    });
}

function inicializarMovimientosUI(){
    movimientosConfigurarPanel('#btnToggleFiltrosMovimientos','#movimientosFiltrosContenido','izzy.movimientosContabilidad.filtros.visible');
    movimientosConfigurarPanel('#btnToggleKpisMovimientos','#movimientosKpisContenido','izzy.movimientosContabilidad.kpis.visible');
    var saved='detalle';try{saved=localStorage.getItem(MOVIMIENTOS_STORAGE_VISTA)||'detalle';}catch(e){}
    movimientosUI.preferredView=saved==='miniatura'?'miniatura':'detalle';
    movimientosUI.view=movimientosEsMovil()?'miniatura':movimientosUI.preferredView;
    movimientosSincronizarPageSize(); movimientosSincronizarVista();
    $('#buscarMovimientos').off('input.movUI').on('input.movUI',function(){movimientosUI.search=this.value||'';movimientosUI.page=1;movimientosFiltrarLocal();movimientosRender();});
    $('#limpiarBuscarMovimientos').off('click.movUI').on('click.movUI',function(){$('#buscarMovimientos').val('').focus();movimientosUI.search='';movimientosUI.page=1;movimientosFiltrarLocal();movimientosRender();});
    $('#movimientosPageSize').off('change.movUI').on('change.movUI',function(){var n=parseInt(this.value,10);if(!n)return;movimientosUI.pageSize=n;if(movimientosUI.view==='miniatura')movimientosUI.pageSizeMiniatura=n;else movimientosUI.pageSizeDetalle=n;movimientosUI.page=1;movimientosRender();});
    $('.movimientos-view-btn').off('click.movUI').on('click.movUI',function(){var v=$(this).data('view');movimientosUI.view=movimientosEsMovil()?'miniatura':(v==='miniatura'?'miniatura':'detalle');if(!movimientosEsMovil()){movimientosUI.preferredView=movimientosUI.view;try{localStorage.setItem(MOVIMIENTOS_STORAGE_VISTA,movimientosUI.preferredView);}catch(e){}}movimientosUI.page=1;movimientosSincronizarPageSize();movimientosSincronizarVista();movimientosRender();});
    $('#movimientosPaginacion').off('click.movUI','.movimientos-page-btn').on('click.movUI','.movimientos-page-btn',function(){if(this.disabled)return;var p=parseInt($(this).data('page'),10);if(!p)return;movimientosUI.page=p;movimientosRender();});
    $('#btnMovimientosActualizar').off('click.movUI').on('click.movUI',listar_movimientos_contabilidad);
    $('#btnMovimientosExcel').off('click.movUI').on('click.movUI',movimientosExportarExcel);
    $('#btnMovimientosPdf').off('click.movUI').on('click.movUI',movimientosExportarPdf);
    $(window).off('resize.movUI orientationchange.movUI').on('resize.movUI orientationchange.movUI',function(){var target=movimientosEsMovil()?'miniatura':movimientosUI.preferredView;if(movimientosUI.view!==target){movimientosUI.view=target;movimientosUI.page=1;movimientosSincronizarPageSize();movimientosRender();}movimientosSincronizarVista();});
}
// FIN ACCIONES FORMULARIO MOVIMIENTOS CONTABLES
</script>
