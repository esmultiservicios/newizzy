<script>
$(() => {
    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpleado();
    inicializarContratoUI();
    listar_contratos();
    $('#form_main_contrato #estado').val(1);
    $('#form_main_contrato #estado').selectpicker('refresh');

	$('#form_main_contrato #search').on("click", function(e) {
        e.preventDefault();
        listar_contratos();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_contrato').on('reset', function() {
        var form = this;
        setTimeout(function() {
            $(form).find('.selectpicker').val('').selectpicker('refresh');
            $('#form_main_contrato #estado').val(1).selectpicker('refresh');
            listar_contratos();
        }, 0);
    });	    
});

/* =========================================================
   IZZY 6.0 | CONTRATOS - UI DIVS
   ========================================================= */

var CONTRATO_MOBILE_QUERY = '(max-width: 767.98px)';
var CONTRATO_STORAGE_VISTA = 'izzy.contrato.tipo_vista';

var contratoUI = {
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

function contratoEsMovil() {
    return window.matchMedia
        ? window.matchMedia(CONTRATO_MOBILE_QUERY).matches
        : $(window).width() <= 767;
}

function contratoValor(value, fallback) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return fallback === undefined ? 'No registrado' : fallback;
    }
    return String(value).trim();
}

function contratoEscape(value) {
    return contratoValor(value, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function contratoNumero(value) {
    var n = parseFloat(String(value == null ? 0 : value).replace(/[^\d.-]/g, ''));
    return isFinite(n) ? n : 0;
}

function contratoMoney(value) {
    return 'L ' + contratoNumero(value).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function contratoIniciales(nombre) {
    var partes = contratoValor(nombre, '?').split(/\s+/).filter(Boolean).slice(0,2);
    return partes.map(function(p){ return p.charAt(0).toUpperCase(); }).join('') || '?';
}

function contratoConfigurarPanel(btn, contenido, key) {
    var visible = true;
    try {
        var saved = localStorage.getItem(key);
        if (saved !== null) visible = saved === '1';
    } catch (e) {}

    function sync() {
        $(contenido).toggle(visible);
        $(btn).attr('aria-expanded', visible ? 'true' : 'false');
        $(btn).find('span').text(visible ? 'Ocultar' : 'Mostrar');
        $(btn).find('i')
            .toggleClass('fa-chevron-up', visible)
            .toggleClass('fa-chevron-down', !visible);
    }

    sync();

    $(btn).off('click.contratoPanel').on('click.contratoPanel', function() {
        visible = !visible;
        $(contenido).stop(true,true)[visible ? 'slideDown' : 'slideUp'](160);
        sync();
        try { localStorage.setItem(key, visible ? '1' : '0'); } catch (e) {}
    });
}

function contratoSincronizarPageSize() {
    var mini = contratoUI.view === 'miniatura';
    var opciones = mini ? [6,12,18,30] : [10,25,50,100];
    var preferido = mini ? contratoUI.pageSizeMiniatura : contratoUI.pageSizeDetalle;

    if (opciones.indexOf(preferido) === -1) preferido = opciones[0];

    var $select = $('#contratoPageSize').empty();
    opciones.forEach(function(n){
        $select.append($('<option></option>').val(n).text(n));
    });

    contratoUI.pageSize = preferido;
    $select.val(String(preferido));
}

function contratoSincronizarVista() {
    var movil = contratoEsMovil();

    $('.contrato-view-btn[data-view="detalle"]')
        .toggleClass('d-none', movil)
        .prop('disabled', movil)
        .attr('aria-hidden', movil ? 'true' : 'false');

    $('.contrato-view-btn').removeClass('active').attr('aria-pressed','false');
    $('.contrato-view-btn[data-view="'+contratoUI.view+'"]')
        .addClass('active').attr('aria-pressed','true');
}

function contratoFiltrar() {
    var q = $.trim(contratoUI.search || '').toLowerCase();

    contratoUI.filtered = !q ? contratoUI.rows.slice() : contratoUI.rows.filter(function(row) {
        return [
            row.contrato_id, row.tipo_empleado, row.empleado, row.tipo_contrato,
            row.pago_planificado, row.salario, row.fecha_inicio, row.fecha_fin,
            row.notas, Number(row.estado) === 1 ? 'activo' : 'inactivo'
        ].map(function(v){
            return contratoValor(v,'').toLowerCase();
        }).join(' ').indexOf(q) !== -1;
    });

    contratoActualizarKpis();
}

function contratoActualizarKpis() {
    var activos=0, inactivos=0, salario=0;

    contratoUI.filtered.forEach(function(row){
        if (Number(row.estado) === 1) activos++;
        else inactivos++;
        salario += contratoNumero(row.salario);
    });

    $('#contratoKpiTotal').text(contratoUI.filtered.length);
    $('#contratoKpiActivos').text(activos);
    $('#contratoKpiInactivos').text(inactivos);
    $('#contratoKpiSalario,#contratoTotalListado').text(contratoMoney(salario));
}

function contratoEstadoBadge(estado) {
    return Number(estado) === 1
        ? '<span class="contrato-status contrato-status-activo"><i class="fas fa-check-circle"></i> Activo</span>'
        : '<span class="contrato-status contrato-status-inactivo"><i class="fas fa-times-circle"></i> Inactivo</span>';
}

function contratoAcciones(row,index) {
    return '' +
        '<div class="dropdown acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i><span>Acciones</span>' +
            '</button>' +
            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar js-contrato-editar" data-index="'+index+'">' +
                    '<span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span>' +
                    '<span class="accion-label">Editar</span>' +
                '</button>' +
                '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar ocultar js-contrato-eliminar" data-index="'+index+'">' +
                    '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span>' +
                    '<span class="accion-label">Eliminar</span>' +
                '</button>' +
            '</div>' +
        '</div>';
}

function contratoRenderDetalle(rows,offset) {
    var html = '' +
        '<div class="contrato-detail-header">' +
            '<div>Empleado</div><div>Contrato</div><div>Pago</div><div>Salario</div>' +
            '<div>Vigencia</div><div>Notas</div><div>Estado</div><div>Acciones</div>' +
        '</div>';

    rows.forEach(function(row,i){
        var idx=offset+i;
        html += '' +
            '<article class="contrato-detail-row">' +
                '<div class="contrato-cell">' +
                    '<span class="contrato-cell-label">Empleado</span>' +
                    '<div class="contrato-person">' +
                        '<span class="contrato-avatar">'+contratoEscape(contratoIniciales(row.empleado))+'</span>' +
                        '<div><strong>'+contratoEscape(contratoValor(row.empleado,'Sin empleado'))+'</strong>' +
                        '<small>'+contratoEscape(contratoValor(row.tipo_empleado,'Sin tipo'))+'</small></div>' +
                    '</div>' +
                '</div>' +
                '<div class="contrato-cell"><span class="contrato-cell-label">Contrato</span>' +
                    '<div class="contrato-stack"><strong>#'+contratoEscape(row.contrato_id)+'</strong>' +
                    '<span>'+contratoEscape(contratoValor(row.tipo_contrato,'Sin tipo de contrato'))+'</span></div>' +
                '</div>' +
                '<div class="contrato-cell"><span class="contrato-cell-label">Pago</span>' +
                    '<strong>'+contratoEscape(contratoValor(row.pago_planificado,'No definido'))+'</strong>' +
                '</div>' +
                '<div class="contrato-cell contrato-money"><span class="contrato-cell-label">Salario</span>' +
                    '<strong>'+contratoMoney(row.salario)+'</strong>' +
                '</div>' +
                '<div class="contrato-cell"><span class="contrato-cell-label">Vigencia</span>' +
                    '<div class="contrato-stack"><span><b>Inicio:</b> '+contratoEscape(contratoValor(row.fecha_inicio,'N/A'))+'</span>' +
                    '<span><b>Fin:</b> '+contratoEscape(contratoValor(row.fecha_fin,'Sin fecha'))+'</span></div>' +
                '</div>' +
                '<div class="contrato-cell"><span class="contrato-cell-label">Notas</span>' +
                    '<span class="contrato-notas">'+contratoEscape(contratoValor(row.notas,'Sin notas'))+'</span>' +
                '</div>' +
                '<div class="contrato-cell contrato-center"><span class="contrato-cell-label">Estado</span>' +
                    contratoEstadoBadge(row.estado) +
                '</div>' +
                '<div class="contrato-cell contrato-center"><span class="contrato-cell-label">Acciones</span>' +
                    contratoAcciones(row,idx) +
                '</div>' +
            '</article>';
    });

    return html;
}

function contratoRenderMiniatura(rows,offset) {
    var html='<div class="contrato-mini-grid">';

    rows.forEach(function(row,i){
        var idx=offset+i;
        html += '' +
            '<article class="contrato-mini-card">' +
                '<div class="contrato-mini-topline"></div>' +
                '<div class="contrato-mini-header">' +
                    '<span class="contrato-avatar contrato-avatar-large">'+contratoEscape(contratoIniciales(row.empleado))+'</span>' +
                    '<div class="contrato-mini-title">' +
                        '<h4>'+contratoEscape(contratoValor(row.empleado,'Sin empleado'))+'</h4>' +
                        '<span>'+contratoEscape(contratoValor(row.tipo_empleado,'Sin tipo'))+'</span>' +
                    '</div>' +
                    contratoEstadoBadge(row.estado) +
                '</div>' +
                '<div class="contrato-mini-body">' +
                    '<div class="contrato-mini-field"><span>Código</span><strong>#'+contratoEscape(row.contrato_id)+'</strong></div>' +
                    '<div class="contrato-mini-field"><span>Tipo Contrato</span><strong>'+contratoEscape(contratoValor(row.tipo_contrato,'No definido'))+'</strong></div>' +
                    '<div class="contrato-mini-field"><span>Pago</span><strong>'+contratoEscape(contratoValor(row.pago_planificado,'No definido'))+'</strong></div>' +
                    '<div class="contrato-mini-field"><span>Salario</span><strong class="contrato-text-money">'+contratoMoney(row.salario)+'</strong></div>' +
                    '<div class="contrato-mini-field"><span>Fecha Inicio</span><strong>'+contratoEscape(contratoValor(row.fecha_inicio,'N/A'))+'</strong></div>' +
                    '<div class="contrato-mini-field"><span>Fecha Fin</span><strong>'+contratoEscape(contratoValor(row.fecha_fin,'Sin fecha'))+'</strong></div>' +
                    '<div class="contrato-mini-field contrato-mini-field-full"><span>Notas</span><strong>'+contratoEscape(contratoValor(row.notas,'Sin notas'))+'</strong></div>' +
                '</div>' +
                '<div class="contrato-mini-footer">'+contratoAcciones(row,idx)+'</div>' +
            '</article>';
    });

    return html+'</div>';
}

function contratoRenderPaginacion(totalPages) {
    var current=contratoUI.page, html='';

    function b(label,page,disabled,active,icon){
        return '<button type="button" class="contrato-page-btn'+(active?' active':'')+'" data-page="'+page+'"'+(disabled?' disabled':'')+'>'+
            (icon?'<i class="'+icon+' mr-1"></i>':'')+label+'</button>';
    }

    html += b('Inicio',1,current===1,false,'fas fa-angle-double-left');
    html += b('Anterior',current-1,current===1,false,'fas fa-angle-left');

    var from=Math.max(1,current-2), to=Math.min(totalPages,from+4);
    from=Math.max(1,to-4);

    for(var p=from;p<=to;p++) html += b(String(p),p,false,p===current,'');

    html += b('Siguiente',current+1,current===totalPages,false,'fas fa-angle-right');
    html += b('Final',totalPages,current===totalPages,false,'fas fa-angle-double-right');

    $('#contratoPaginacion').html(html);
}

function contratoRender() {
    var rows=contratoUI.filtered||[];

    if(contratoUI.loading){
        $('#contratoListado').html('<div class="contrato-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando contratos</strong><span>Consultando información...</span></div>');
        $('#contratoInfo').text('0 registros');
        $('#contratoPaginacion').empty();
        return;
    }

    if(!rows.length){
        $('#contratoListado').html('<div class="contrato-state"><i class="fas fa-file-contract"></i><strong>Sin contratos</strong><span>No se encontraron registros con los filtros actuales.</span></div>');
        $('#contratoInfo').text('0 registros');
        $('#contratoPaginacion').empty();
        return;
    }

    var pages=Math.max(1,Math.ceil(rows.length/contratoUI.pageSize));
    if(contratoUI.page>pages) contratoUI.page=pages;

    var offset=(contratoUI.page-1)*contratoUI.pageSize;
    var pageRows=rows.slice(offset,offset+contratoUI.pageSize);

    $('#contratoListado')
        .removeClass('vista-detalle vista-miniatura')
        .addClass('vista-'+contratoUI.view)
        .html(contratoUI.view==='miniatura'
            ? contratoRenderMiniatura(pageRows,offset)
            : contratoRenderDetalle(pageRows,offset));

    $('#contratoInfo').text('Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros');
    contratoRenderPaginacion(pages);

    if(typeof getPermisosTipoUsuarioAccesosTable==='function' && typeof getPrivilegioTipoUsuario==='function'){
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
}

var listar_contratos = function() {
    var estado = $("#form_main_contrato #estado").val() === "" ? 1 : $("#form_main_contrato #estado").val();
    var tipo_contrato = $("#form_main_contrato #tipo_contrato").val();
    var pago_planificado = $("#form_main_contrato #pago_planificado").val();
    var tipo_empleado = $("#form_main_contrato #tipo_empleado").val();

    contratoUI.loading=true;
    contratoRender();

    $.ajax({
        method:"POST",
        url:"<?php echo SERVERURL;?>core/llenarDataTableContratos.php",
        dataType:"json",
        data:{
            estado:estado,
            tipo_contrato:tipo_contrato,
            pago_planificado:pago_planificado,
            tipo_empleado:tipo_empleado
        }
    }).done(function(json){
        contratoUI.rows=(json&&Array.isArray(json.data))?json.data:[];
        contratoUI.search=$('#buscarContrato').val()||'';
        contratoUI.page=1;
        contratoUI.loading=false;
        contratoFiltrar();
        contratoRender();
    }).fail(function(xhr){
        contratoUI.rows=[];
        contratoUI.filtered=[];
        contratoUI.loading=false;
        contratoActualizarKpis();
        contratoRender();
        console.error('Error contratos:',xhr.responseText);
        showNotify('error','Error','No se pudo cargar el listado de contratos.');
    });
};

function editar_contrato_ui(data) {
var url = '<?php echo SERVERURL;?>core/editarContratos.php';
        $('#formContrato')[0].reset();
        $('#formContrato #contrato_id').val(data.contrato_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formContrato').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                $('#reg_contrato').hide();
                $('#edi_contrato').show();
                $('#delete_contrato').hide();
                $('#formContrato #contrato_colaborador_id').val(valores[0]);
                $('#formContrato #contrato_colaborador_id').selectpicker('refresh');
                $('#formContrato #colaborador_id').val(valores[0]);
                $('#formContrato #contrato_tipo_contrato_id').val(valores[1]);
                $('#formContrato #contrato_tipo_contrato_id').selectpicker('refresh');
                $('#formContrato #contrato_pago_planificado_id').val(valores[2]);
                $('#formContrato #contrato_pago_planificado_id').selectpicker('refresh');
                $('#formContrato #contrato_tipo_empleado_id').val(valores[3]);
                $('#formContrato #contrato_tipo_empleado_id').selectpicker('refresh');
                $('#formContrato #contrato_salario').val(valores[4]);
                $('#formContrato #contrato_fecha_inicio').val(valores[5]);
                $('#formContrato #contrato_fecha_fin').val(valores[6]);
                $('#formContrato #contrato_notas').val(valores[7]);
                $('#formContrato #contrato_salario_mensual').val(valores[9]);

                if (valores[8] == 1) {
                    $('#formContrato #contrato_activo').attr('checked', true);
                } else {
                    $('#formContrato #contrato_activo').attr('checked', false);
                }

                //HABILITAR OBJETOS				
                $('#formContrato #contrato_tipo_contrato_id').attr('disabled', false);
                $('#formContrato #contrato_pago_planificado_id').attr('disabled', false);
                $('#formContrato #contrato_tipo_empleado_id').attr('disabled', false);
                $('#formContrato #contrato_fecha_inicio').attr('readonly', false);
                $('#formContrato #contrato_fecha_fin').attr('readonly', false);
                $('#formContrato #contrato_notas').attr('readonly', false);
                $('#formContrato #contrato_activo').attr('disabled', false);

                //DESHABILITATR OBJETOS
                $('#formContrato #contrato_colaborador_id').attr('disabled', true);
                $('#formContrato #contrato_salario_mensual').attr('readonly', true);
                $('#formContrato #contrato_salario').attr('readonly', true);
                $('#formContrato #buscar_contrato_empleado').hide();

                $('#formContrato #proceso_contrato').val("Editar");

                $('#modal_registrar_contrato').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
}

function eliminar_contrato_ui(data) {
var contrato_id = data.contrato_id;
        var nombreEmpleado = data.empleado; 
        
        // Construir el mensaje de confirmación con HTML
        var mensajeHTML = `¿Desea eliminar permanentemente el contrato?<br><br>
                        <strong>Empleado:</strong> ${nombreEmpleado}`;
        
        swal({
            title: "Confirmar eliminación",
            content: {
                element: "span",
                attributes: {
                    innerHTML: mensajeHTML
                }
            },
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn-light"
                },
                confirm: {
                    text: "Sí, eliminar",
                    value: true,
                    className: "btn-danger",
                    closeModal: false
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
               
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarContratosAjax.php',
                    data: {
                        contrato_id: contrato_id
                    },
                    dataType: 'json', // Esperamos respuesta JSON
                    before: function(){
                        // Mostrar carga mientras se procesa
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        
                        if(response.status === "success") {
                            showNotify("success", response.title, response.message);
                            listar_contratos();                    
                        } else {
                            showNotify("error", response.title, response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
}

function contratoExcelEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function contratoExcelCol(index) {
    var name='';
    while(index>=0){
        name=String.fromCharCode((index%26)+65)+name;
        index=Math.floor(index/26)-1;
    }
    return name;
}

function contratoExcelCell(ref,value,style) {
    return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+contratoExcelEscape(value)+'</t></is></c>';
}

function contratoGenerarExcel() {
    var rows=contratoUI.filtered||[];

    if(!rows.length){
        showNotify('warning','Sin información','No hay contratos para exportar.');
        return;
    }

    if(typeof JSZip==='undefined'){
        showNotify('error','Excel no disponible','JSZip no está disponible.');
        return;
    }

    var headers=['Código','Tipo Empleado','Empleado','Tipo Contrato','Pago Planificado','Salario','Fecha Inicio','Fecha Fin','Notas','Estado'];
    var sheetRows=[];

    sheetRows.push('<row r="1" ht="30" customHeight="1">'+contratoExcelCell('A1','IZZY • REPORTE DE CONTRATOS',1)+'</row>');
    sheetRows.push('<row r="2">'+contratoExcelCell('A2','Generado: '+new Date().toLocaleDateString('es-HN')+' • Registros: '+rows.length,2)+'</row>');
    sheetRows.push('<row r="4" ht="26" customHeight="1">'+headers.map(function(h,i){return contratoExcelCell(contratoExcelCol(i)+'4',h,3);}).join('')+'</row>');

    rows.forEach(function(r,i){
        var rr=5+i;
        var vals=[
            r.contrato_id,r.tipo_empleado,r.empleado,r.tipo_contrato,r.pago_planificado,
            contratoMoney(r.salario),r.fecha_inicio,r.fecha_fin,r.notas,
            Number(r.estado)===1?'Activo':'Inactivo'
        ];
        sheetRows.push('<row r="'+rr+'">'+vals.map(function(v,c){return contratoExcelCell(contratoExcelCol(c)+rr,v,4);}).join('')+'</row>');
    });

    var lastRow=Math.max(4,4+rows.length);

    var sheetXml=
        '<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
            '<dimension ref="A1:J'+lastRow+'"/>'+
            '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'+
            '<cols><col min="1" max="1" width="11" customWidth="1"/><col min="2" max="5" width="20" customWidth="1"/><col min="6" max="6" width="16" customWidth="1"/><col min="7" max="8" width="16" customWidth="1"/><col min="9" max="9" width="30" customWidth="1"/><col min="10" max="10" width="13" customWidth="1"/></cols>'+
            '<sheetData>'+sheetRows.join('')+'</sheetData>'+
            '<autoFilter ref="A4:J'+lastRow+'"/>'+
            '<mergeCells count="2"><mergeCell ref="A1:J1"/><mergeCell ref="A2:J2"/></mergeCells>'+
        '</worksheet>';

    var stylesXml=
        '<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'+
            '<fonts count="5"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts>'+
            '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill></fills>'+
            '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders>'+
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'+
            '<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs>'+
            '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'+
        '</styleSheet>';

    var workbookXml='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Contratos" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var workbookRels='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var rootRels='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var contentTypes='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';

    var zip=new JSZip();
    zip.file('[Content_Types].xml',contentTypes);
    zip.folder('_rels').file('.rels',rootRels);
    zip.folder('xl').file('workbook.xml',workbookXml);
    zip.folder('xl').file('styles.xml',stylesXml);
    zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels);
    zip.folder('xl').folder('worksheets').file('sheet1.xml',sheetXml);

    var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
    var promise=typeof zip.generateAsync==='function'
        ? zip.generateAsync(opts)
        : (typeof zip.generate==='function'
            ? Promise.resolve(zip.generate(opts))
            : Promise.reject(new Error('JSZip no soportado')));

    promise.then(function(blob){
        var url=URL.createObjectURL(blob);
        var a=document.createElement('a');
        a.href=url;
        a.download='Reporte_Contratos.xlsx';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function(){URL.revokeObjectURL(url);},1000);
    }).catch(function(error){
        console.error(error);
        showNotify('error','Error','No se pudo generar el Excel.');
    });
}

function contratoObtenerLogoPdf(callback) {
    if(typeof imagen==='string' && imagen.indexOf('data:image/')===0){
        callback(imagen);
        return;
    }

    $.ajax({
        type:'GET',
        url:'<?php echo SERVERURL;?>core/get_image.php',
        dataType:'text',
        timeout:15000
    }).done(function(url){
        url=$.trim(url||'');
        if(!url){callback(null);return;}

        var img=new Image();
        img.crossOrigin='Anonymous';
        img.onload=function(){
            try{
                var canvas=document.createElement('canvas');
                canvas.width=img.naturalWidth||img.width;
                canvas.height=img.naturalHeight||img.height;
                canvas.getContext('2d').drawImage(img,0,0);
                imagen=canvas.toDataURL('image/png');
                callback(imagen);
            }catch(e){callback(null);}
        };
        img.onerror=function(){callback(null);};
        img.src=url;
    }).fail(function(){callback(null);});
}

function contratoGenerarPdf() {
    var rows=contratoUI.filtered||[];

    if(!rows.length){
        showNotify('warning','Sin información','No hay contratos para mostrar en PDF.');
        return;
    }

    if(typeof pdfMake==='undefined' || typeof abrirModalPdfPublico!=='function'){
        showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');
        return;
    }

    contratoObtenerLogoPdf(function(logo){
        var body=[[
            {text:'CÓDIGO',style:'th'},
            {text:'EMPLEADO',style:'th'},
            {text:'TIPO CONTRATO',style:'th'},
            {text:'PAGO',style:'th'},
            {text:'SALARIO',style:'th'},
            {text:'VIGENCIA',style:'th'},
            {text:'ESTADO',style:'th'}
        ]];

        rows.forEach(function(r,i){
            var fill=i%2===0?'#FFFFFF':'#F7F9FC';
            body.push([
                {text:String(r.contrato_id||''),style:'td',fillColor:fill},
                {text:contratoValor(r.empleado,''),style:'td',fillColor:fill},
                {text:contratoValor(r.tipo_contrato,''),style:'td',fillColor:fill},
                {text:contratoValor(r.pago_planificado,''),style:'td',fillColor:fill},
                {text:contratoMoney(r.salario),style:'td',fillColor:fill,alignment:'right'},
                {text:contratoValor(r.fecha_inicio,'')+' → '+contratoValor(r.fecha_fin,'Sin fecha'),style:'td',fillColor:fill},
                {text:Number(r.estado)===1?'Activo':'Inactivo',style:'td',fillColor:fill,color:Number(r.estado)===1?'#14804A':'#C9372C',bold:true}
            ]);
        });

        var logoCell=logo
            ? {table:{widths:['*'],body:[[{image:logo,fit:[64,38],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]}
            : {text:'IZZY',bold:true,fontSize:18,color:'#17324D',alignment:'center',fillColor:'#FFFFFF',margin:[8,14,8,14]};

        var doc={
            pageSize:'LETTER',
            pageOrientation:'landscape',
            pageMargins:[28,28,28,34],
            header:function(){
                return {margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};
            },
            footer:function(page,pages){
                return {margin:[28,8,28,0],columns:[
                    {text:'IZZY • Contratos',fontSize:7,color:'#7A869A'},
                    {text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}
                ]};
            },
            content:[
                {table:{widths:[100,'*',160],body:[[
                    logoCell,
                    {stack:[
                        {text:'REPORTE DE CONTRATOS',bold:true,fontSize:16,color:'#FFFFFF'},
                        {text:'Contratos laborales y condiciones de pago',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}
                    ],fillColor:'#17324D',margin:[0,10,0,10]},
                    {stack:[
                        {text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},
                        {text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},
                        {text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}
                    ],fillColor:'#17324D',margin:[0,10,12,10]}
                ]]},layout:'noBorders',margin:[0,0,0,12]},
                {table:{headerRows:1,widths:[50,145,120,85,80,120,62],body:body},margin:[0,0,0,0],layout:{
                    hLineColor:function(){return '#DDE3EA';},
                    vLineColor:function(){return '#DDE3EA';},
                    hLineWidth:function(){return .55;},
                    vLineWidth:function(){return .55;},
                    paddingLeft:function(){return 5;},
                    paddingRight:function(){return 5;},
                    paddingTop:function(){return 6;},
                    paddingBottom:function(){return 6;}
                }}
            ],
            styles:{
                th:{fontSize:6.1,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},
                td:{fontSize:6.1,color:'#253858',noWrap:false}
            }
        };

        pdfMake.createPdf(doc).getDataUrl(function(url){
            abrirModalPdfPublico(url,'Reporte de Contratos','Reporte_Contratos.pdf');
        });
    });
}

function inicializarContratoUI() {
    contratoConfigurarPanel('#btnToggleFiltrosContrato','#contratoFiltrosContenido','izzy.contrato.filtros.visible');
    contratoConfigurarPanel('#btnToggleKpisContrato','#contratoKpisContenido','izzy.contrato.kpis.visible');

    var saved='detalle';
    try{ saved=localStorage.getItem(CONTRATO_STORAGE_VISTA)||'detalle'; }catch(e){}

    contratoUI.preferredView=saved==='miniatura'?'miniatura':'detalle';
    contratoUI.view=contratoEsMovil()?'miniatura':contratoUI.preferredView;
    contratoSincronizarPageSize();
    contratoSincronizarVista();

    $('#buscarContrato').off('input.contratoUI').on('input.contratoUI',function(){
        contratoUI.search=this.value||'';
        contratoUI.page=1;
        contratoFiltrar();
        contratoRender();
    });

    $('#limpiarBuscarContrato').off('click.contratoUI').on('click.contratoUI',function(){
        $('#buscarContrato').val('').focus();
        contratoUI.search='';
        contratoUI.page=1;
        contratoFiltrar();
        contratoRender();
    });

    $('#contratoPageSize').off('change.contratoUI').on('change.contratoUI',function(){
        var n=parseInt(this.value,10);
        if(!n)return;
        contratoUI.pageSize=n;
        if(contratoUI.view==='miniatura') contratoUI.pageSizeMiniatura=n;
        else contratoUI.pageSizeDetalle=n;
        contratoUI.page=1;
        contratoRender();
    });

    $('.contrato-view-btn').off('click.contratoUI').on('click.contratoUI',function(){
        var vista=$(this).data('view');

        contratoUI.view=contratoEsMovil()
            ? 'miniatura'
            : (vista==='miniatura'?'miniatura':'detalle');

        if(!contratoEsMovil()){
            contratoUI.preferredView=contratoUI.view;
            try{localStorage.setItem(CONTRATO_STORAGE_VISTA,contratoUI.preferredView);}catch(e){}
        }

        contratoUI.page=1;
        contratoSincronizarPageSize();
        contratoSincronizarVista();
        contratoRender();
    });

    $('#contratoPaginacion').off('click.contratoUI','.contrato-page-btn').on('click.contratoUI','.contrato-page-btn',function(){
        if(this.disabled)return;
        var page=parseInt($(this).data('page'),10);
        if(!page)return;
        contratoUI.page=page;
        contratoRender();
    });

    $('#contratoListado')
        .off('click.contratoUI','.js-contrato-editar')
        .on('click.contratoUI','.js-contrato-editar',function(){
            var row=contratoUI.filtered[parseInt($(this).data('index'),10)];
            if(row) editar_contrato_ui(row);
        })
        .off('click.contratoDelete','.js-contrato-eliminar')
        .on('click.contratoDelete','.js-contrato-eliminar',function(){
            var row=contratoUI.filtered[parseInt($(this).data('index'),10)];
            if(row) eliminar_contrato_ui(row);
        });

    $('#btnContratoActualizar').off('click.contratoUI').on('click.contratoUI',listar_contratos);
    $('#btnContratoIngresar').off('click.contratoUI').on('click.contratoUI',modal_contratos);
    $('#btnContratoExcel').off('click.contratoUI').on('click.contratoUI',contratoGenerarExcel);
    $('#btnContratoPdf').off('click.contratoUI').on('click.contratoUI',contratoGenerarPdf);

    $(window).off('resize.contratoUI orientationchange.contratoUI').on('resize.contratoUI orientationchange.contratoUI',function(){
        var target=contratoEsMovil()?'miniatura':contratoUI.preferredView;

        if(contratoUI.view!==target){
            contratoUI.view=target;
            contratoUI.page=1;
            contratoSincronizarPageSize();
            contratoSincronizarVista();
            contratoRender();
        }else{
            contratoSincronizarVista();
        }
    });
}



/*INICIO FORMULARIO CONTRATOS*/
function modal_contratos() {
    $('#formContrato')[0].reset();
    $('#reg_contrato').show();
    $('#edi_contrato').hide();
    $('#delete_contrato').hide();

    //HABILITAR OBJETOS
    $('#formContrato #contrato_colaborador_id').attr('disabled', false);
    $('#formContrato #contrato_tipo_contrato_id').attr('disabled', false);
    $('#formContrato #contrato_pago_planificado_id').attr('disabled', false);
    $('#formContrato #contrato_tipo_empleado_id').attr('disabled', false);
    $('#formContrato #contrato_salario').attr('readonly', true);
    $('#formContrato #contrato_fecha_inicio').attr('readonly', false);
    $('#formContrato #contrato_fecha_fin').attr('disabled', false);
    $('#formContrato #contrato_notas').attr('readonly', false);
    $('#formContrato #contrato_activo').attr('disabled', false);
    $('#formContrato #contrato_salario_mensual').attr('readonly', false);
    $('#formContrato #buscar_contrato_empleado').show();

    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpleado();

    $('#formContrato #proceso_contrato').val("Registro");

    $('#modal_registrar_contrato').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

$('#formContrato').on('submit', function(e) {
    e.preventDefault();

    // 1. Refrescar todos los selectpickers para asegurar sincronización
    $('.selectpicker').selectpicker('refresh');

    // 2. Construir objeto de datos manualmente (versión mejorada)
    const getValue = (selector) => $(selector).val();
    const isChecked = (selector) => $(selector).is(':checked') ? 1 : 0;
    
    const formData = {
        contrato_id: getValue('#contrato_id'),
        contrato_colaborador_id: getValue('#contrato_colaborador_id'),
        contrato_tipo_contrato_id: getValue('#contrato_tipo_contrato_id'),
        contrato_pago_planificado_id: getValue('#contrato_pago_planificado_id'),
        contrato_tipo_empleado_id: getValue('#contrato_tipo_empleado_id'),
        contrato_salario_mensual: getValue('#contrato_salario_mensual'),
        contrato_salario: getValue('#contrato_salario'),
        contrato_fecha_inicio: getValue('#contrato_fecha_inicio'),
        contrato_fecha_fin: getValue('#contrato_fecha_fin') || null, // Manejo explícito de valores vacíos
        contrato_notas: getValue('#contrato_notas'),
        contrato_activo: isChecked('#contrato_activo'),
        // Campos adicionales si existen
        calculo_semanal: isChecked('#calculo_semanal')
    };

    // 3. Validación básica en cliente (opcional)
    const requiredFields = ['contrato_colaborador_id', 'contrato_tipo_contrato_id', 'contrato_salario_mensual'];
    const missingFields = requiredFields.filter(field => !formData[field]);
    
    if (missingFields.length > 0) {
        showNotify('error', 'Error', `Faltan campos requeridos: ${missingFields.join(', ')}`);
        return;
    }

    // 4. Determinar si es creación o edición
    const isEdit = !!(formData.contrato_id && String(formData.contrato_id).trim() !== '' && formData.contrato_id !== '0');
    const url = isEdit ? '<?php echo SERVERURL;?>ajax/modificarContratosAjax.php' 
                      : '<?php echo SERVERURL;?>ajax/addContratosAjax.php';

    // 5. Configuración de SweetAlert dinámica
    swal({
        title: isEdit ? "¿Actualizar contrato?" : "¿Registrar nuevo contrato?",
        text: isEdit ? "Confirma los cambios del contrato" : "Confirma que deseas registrar este nuevo contrato",
        icon: "info",
        buttons: {
            cancel: { text: "Cancelar", visible: true, className: "btn-light" },
            confirm: { 
                text: isEdit ? "Sí, actualizar" : "Sí, registrar",
            }
        },
        dangerMode: false,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (willConfirm) {
            // 6. Deshabilitar botón durante el envío
            const submitBtn = $(this).find('[type="submit"]');
            const originalBtnHtml = submitBtn.html();
            submitBtn.prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            // 7. Enviar datos
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: "json",
                success: function(response) {
                    // Restaurar botón
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                    
                    if (response?.status === "success") {
                        showNotify("success", response.title, response.message);
                        
                        // Ejecutar funciones callback si existen
                        if (response.funcion) {
                            try {
                                new Function(response.funcion)(); // Más seguro que eval()
                            } catch (e) {
                                console.error("Error ejecutando función:", e);
                            }
                        }
                        
                        // Limpiar formulario si es creación exitosa
                        if (!isEdit && response.clearForm) {
                            $('#formContrato')[0].reset();
                            $('.selectpicker').selectpicker('refresh');
                        }
                        
                        // Cerrar modal si es necesario
                        if (response.modal) {
                            $('#modal_registrar_contrato').modal('hide');
                        }
                    } else {
                        showNotify("error", response?.title || "Error", response?.message || "Error desconocido");
                        
                        // Resaltar campos con error si existen
                        if (response?.missing_fields) {
                            $('.is-invalid').removeClass('is-invalid');
                            response.missing_fields.forEach(field => {
                                $(`[name="${field}"], #${field}`).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function(xhr) {
                    // Restaurar botón
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                    
                    // Manejo mejorado de errores
                    let errorMsg = "Error al procesar la solicitud";
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        errorMsg = errorResponse.message || errorMsg;
                    } catch (e) {
                        console.error("Error parsing response:", e);
                    }
                    
                    showNotify("error", "Error de conexión", errorMsg);
                    console.error("Detalles del error:", xhr.responseText);
                }
            });
        }
    });
});
/*FIN FORMULARIO CONTRATOS*/

$(document).ready(function() {
    $("#modal_registrar_contrato").on('shown.bs.modal', function() {
        $(this).find('#formContrato #puesto').focus();
    });
});

$('#formContrato #label_contrato_activo').html("Activo");

$('#formContrato .switch').change(function() {
    if ($('input[name=contrato_activo]').is(':checked')) {
        $('#formContrato #label_contrato_activo').html("Activo");
        return true;
    } else {
        $('#formContrato #label_contrato_activo').html("Inactivo");
        return false;
    }
});

$('#formContrato #label_calculo_semanal').html("Inactivo");

$('#formContrato .switch').change(function() {
    if ($('input[name=calculo_semanal]').is(':checked')) {
        $('#formContrato #label_calculo_semanal').html("Activo");
        return true;
    } else {
        $('#formContrato #label_calculo_semanal').html("Inactivo");
        return false;
    }
});

function getTipoContrato() {
    var url = '<?php echo SERVERURL;?>core/getTipoContrato.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#form_main_contrato #tipo_contrato').html("");
            $('#form_main_contrato #tipo_contrato').html(data);
            $('#form_main_contrato #tipo_contrato').selectpicker('refresh');

            $('#formContrato #contrato_tipo_contrato_id').html("");
            $('#formContrato #contrato_tipo_contrato_id').html(data);
            $('#formContrato #contrato_tipo_contrato_id').selectpicker('refresh');
        }
    });
}

function getPagoPlanificado() {
    var url = '<?php echo SERVERURL;?>core/getPagoPlanificado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#form_main_contrato #pago_planificado').html("");
            $('#form_main_contrato #pago_planificado').html(data);
            $('#form_main_contrato #pago_planificado').selectpicker('refresh');

            $('#formContrato #contrato_pago_planificado_id').html("");
            $('#formContrato #contrato_pago_planificado_id').html(data);
            $('#formContrato #contrato_pago_planificado_id').selectpicker('refresh');
        }
    });
}

function getTipoEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getTipoEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_contrato #tipo_empleado').html("");
            $('#form_main_contrato #tipo_empleado').html(data);
            $('#form_main_contrato #tipo_empleado').selectpicker('refresh');

            $('#formContrato #contrato_tipo_empleado_id').html("");
            $('#formContrato #contrato_tipo_empleado_id').html(data);
            $('#formContrato #contrato_tipo_empleado_id').selectpicker('refresh');
        }
    });
}

//INICIO FORMULARIO CONRATO
function getEmpleado() {
    var url = '<?php echo SERVERURL;?>core/getEmpleadoContrato.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {

            $('#formContrato #contrato_colaborador_id').html("");
            $('#formContrato #contrato_colaborador_id').html(data);
            $('#formContrato #contrato_colaborador_id').selectpicker('refresh');
        }
    });
}
// FIN FORMULARIO CONTRATO

$('#formContrato #contrato_notas').keyup(function() {
    var max_chars = 254;
    var chars = $(this).val().length;
    var diff = max_chars - chars;

    $('#formContrato #charNum_contrato_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
});

function caracteresEstadoContrato() {
    var max_chars = 254;
    var chars = $('#formContrato #contrato_notas').val().length;
    var diff = max_chars - chars;

    $('#formContrato #charNum_contrato_notas').html(diff + ' Caracteres');

    if (diff == 0) {
        return false;
    }
}

//INICIO GRABACIONES POR VOZ
$(document).ready(function() {
    //INICIO FORMULARIO ATENCIONES EXPEDIENTE CLINICO
    $('#formContrato #search_contrato_notas_stop').hide();

    var recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.lang = "es";

    $('#formContrato #search_contrato_notas_start').on('click', function(event) {
        $('#formContrato #search_contrato_notas_start').hide();
        $('#formContrato #search_contrato_notas_stop').show();

        recognition.start();

        recognition.onresult = function(event) {
            finalResult = '';
            var valor_anterior = $('#formContrato #contrato_notas').val();
            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    if (valor_anterior != "") {
                        $('#formContrato #contrato_notas').val(valor_anterior + ' ' + finalResult);
                        caracteresEstadoContrato();
                    } else {
                        $('#formContrato #contrato_notas').val(finalResult);
                        caracteresEstadoContrato();
                    }
                }
            }
        };
        return false;
    });

    $('#formContrato #search_contrato_notas_stop').on("click", function(event) {
        $('#formContrato #search_contrato_notas_start').show();
        $('#formContrato #search_contrato_notas_stop').hide();
        recognition.stop();
    });

    /*###############################################################################################################################*/
});

$('#formContrato #contrato_salario_mensual').on("keyup", function(e) {
    ValidarTipoPago(false);
});

$('#formContrato #contrato_pago_planificado_id').on("change", function(e) {
    ValidarTipoPago(false);
});

$('#formContrato #contrato_pago_planificado_id').on("change", function(e) {
    if ($('#formContrato #contrato_pago_planificado_id').val() === "1") {
        $('#formContrato #estado_base_semanal').show();
    } else {
        $('#formContrato #estado_base_semanal').hide();
    }
});

$('#formContrato #calculo_semanal').on("change", function() {
    if ($(this).is(":checked")) {
        ValidarTipoPago(true);
    } else {
        ValidarTipoPago(false);
    }
});

function ValidarTipoPago(semanal) {
    if ($('#formContrato #contrato_pago_planificado_id').val() != "") {
        var valor = 0;

        if ($('#formContrato #contrato_pago_planificado_id').val() == 1) { //SEMANAL
            valor = 7;
        }

        if ($('#formContrato #contrato_pago_planificado_id').val() == 2) { //QUINCENAL
            valor = 15;
        }

        if ($('#formContrato #contrato_pago_planificado_id').val() == 3) { //MENSUAL
            valor = 30;
        }

        var salarioMensual = parseFloat($('#formContrato #contrato_salario_mensual').val());
        var salarioDiario = parseFloat($('#formContrato #contrato_salario_mensual').val()) / parseFloat(30);
        var salario = 0.00;

        if (semanal) {
            salario = parseFloat(salarioMensual) / parseFloat(4);
        } else {
            salario = parseFloat(salarioDiario) * parseFloat(valor);
        }

        $('#formContrato #contrato_salario').val(parseFloat(salario).toFixed(2));
    } else {
        showNotify('error', 'Error', 'Lo sentimos debe seleccionar un pago planificado antes de llenar este valor');
    }
}
</script>