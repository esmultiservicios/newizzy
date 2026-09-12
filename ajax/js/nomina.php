<script>
var sueldo_diario = 0;

$(() => {
    getTipoContrato();
    getPagoPlanificado();
    getTipoEmpleado();
    getEmpresa();    
    getEmpleado();    
    getTipoNomina();
    getCuentaNominas();
    getEmpleadoVales();
    listar_vales();
    listar_nominas();

    $('#form_main_nominas #estado_nomina').val(0);
    $('#form_main_nominas #estado_nomina').selectpicker('refresh');

    $('#form_main_nominas #search').on("click", function(e) {
        e.preventDefault();
        listar_nominas();
    });

    // Evento para el botón de Limpiar (reset)
    $('#form_main_nominas').on('reset', function() {
        $(this).find('.selectpicker').val('').selectpicker('refresh');
        listar_nominas();
    });	   
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - NÓMINAS
   ========================================================= */

   function construirHeaderFooterDataTableNomina() {
    var $tabla = $("#dataTableNomina");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Código</th>' +
                '<th>Detalle</th>' +
                '<th>Empresa</th>' +
                '<th>Fecha Inicio</th>' +
                '<th>Fecha Fin</th>' +
                '<th>Importe</th>' +
                '<th>Notas</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="1">Total</td>' +
                '<td colspan="5"></td>' +
                '<td id="neto_importe"></td>' +
                '<td colspan="2"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* ============================
   LISTADO DE NÓMINAS
   ============================ */


/* =========================================================
   IZZY 6.0 | NÓMINA UI DIVS
   ========================================================= */
var NOMINA_MOBILE_QUERY = '(max-width: 767.98px)';

var nominaUI = {rows:[],filtered:[],page:1,pageSize:10,pageSizeDetalle:10,pageSizeMiniatura:6,view:'detalle',preferredView:'detalle',search:'',loading:false};
var nominaDetalleUI = {rows:[],filtered:[],page:1,pageSize:10,pageSizeDetalle:10,pageSizeMiniatura:6,view:'detalle',preferredView:'detalle',search:'',loading:false};
var nominaValesUI = {rows:[],filtered:[],page:1,pageSize:6,search:'',loading:false};

function nominaNum(v){ return parseFloat(String(v == null ? 0 : v).replace(/[^\d.-]/g,'')) || 0; }
function nominaMoney(v){ return 'L ' + nominaNum(v).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function nominaVal(v,d){ if(v===null||v===undefined||String(v).trim()==='') return d===undefined?'No registrado':d; return String(v).trim(); }
function nominaEsc(v){ return nominaVal(v,'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
function nominaEsMovil(){ return window.matchMedia ? window.matchMedia(NOMINA_MOBILE_QUERY).matches : $(window).width()<=767; }

function nominaPanel(btn, body, key){
    var visible=true;
    try{ var s=localStorage.getItem(key); if(s!==null) visible=s==='1'; }catch(e){}
    function sync(){
        $(body).toggle(visible);
        $(btn).attr('aria-expanded',visible?'true':'false');
        $(btn).find('span').text(visible?'Ocultar':'Mostrar');
        $(btn).find('i').toggleClass('fa-chevron-up',visible).toggleClass('fa-chevron-down',!visible);
    }
    sync();
    $(btn).off('click.nominaPanel').on('click.nominaPanel',function(){
        visible=!visible;
        $(body).stop(true,true)[visible?'slideDown':'slideUp'](160);
        sync();
        try{localStorage.setItem(key,visible?'1':'0');}catch(e){}
    });
}

function nominaOpcionesPage($select, ui){
    var mini=ui.view==='miniatura';
    var ops=mini?[6,12,18,30]:[10,25,50,100];
    var pref=mini?ui.pageSizeMiniatura:ui.pageSizeDetalle;
    if(ops.indexOf(pref)<0) pref=ops[0];
    $select.empty();
    ops.forEach(function(n){$select.append($('<option></option>').val(n).text(n));});
    ui.pageSize=pref;
    $select.val(String(pref));
}

function nominaEstadoBadge(estado){
    return Number(estado)===1
        ? '<span class="nomina-status nomina-status-ok"><i class="fas fa-check-circle"></i> Generada</span>'
        : '<span class="nomina-status nomina-status-pending"><i class="fas fa-clock"></i> Sin Generar</span>';
}

function nominaInitials(nombre){
    var p=nominaVal(nombre,'?').split(/\s+/).filter(Boolean).slice(0,2);
    return p.map(function(x){return x.charAt(0).toUpperCase();}).join('') || '?';
}

function nominaPaginacion(container,totalPages,current){
    var h='';
    function b(label,p,disabled,active,icon){
        return '<button type="button" class="nomina-page-btn'+(active?' active':'')+'" data-page="'+p+'"'+(disabled?' disabled':'')+'>'+(icon?'<i class="'+icon+' mr-1"></i>':'')+label+'</button>';
    }
    h+=b('Inicio',1,current===1,false,'fas fa-angle-double-left');
    h+=b('Anterior',current-1,current===1,false,'fas fa-angle-left');
    var from=Math.max(1,current-2),to=Math.min(totalPages,from+4); from=Math.max(1,to-4);
    for(var p=from;p<=to;p++) h+=b(String(p),p,false,p===current,'');
    h+=b('Siguiente',current+1,current===totalPages,false,'fas fa-angle-right');
    h+=b('Final',totalPages,current===totalPages,false,'fas fa-angle-double-right');
    $(container).html(h);
}

function nominaMainActions(row,index){
    return '<div class="dropdown acciones-dropdown">'+
      '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle"><i class="fas fa-cog"></i><span>Acciones</span></button>'+
      '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
        '<button type="button" class="dropdown-item accion-item nomina-ui-generar ocultar" data-index="'+index+'"><span class="accion-icon"><i class="fas fa-users-cog"></i></span><span class="accion-label">Generar Nómina</span></button>'+
        '<button type="button" class="dropdown-item accion-item nomina-ui-voucher ocultar" data-index="'+index+'"><span class="accion-icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="accion-label">Voucher de Pago</span></button>'+
        '<button type="button" class="dropdown-item accion-item nomina-ui-libro ocultar" data-index="'+index+'"><span class="accion-icon"><i class="fas fa-book"></i></span><span class="accion-label">Libro de Salarios</span></button>'+
        '<div class="dropdown-divider"></div>'+
        '<button type="button" class="dropdown-item accion-item nomina-ui-crear ocultar" data-index="'+index+'"><span class="accion-icon"><i class="fas fa-folder-plus"></i></span><span class="accion-label">Crear / Empleados</span></button>'+
        '<button type="button" class="dropdown-item accion-item accion-editar table_editar nomina-ui-editar ocultar" data-index="'+index+'"><span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span><span class="accion-label">Editar</span></button>'+
        '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar nomina-ui-eliminar ocultar" data-index="'+index+'"><span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span><span class="accion-label">Eliminar</span></button>'+
      '</div></div>';
}

function nominaDetalleActions(row,index){
    return '<div class="dropdown acciones-dropdown">'+
      '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle"><i class="fas fa-cog"></i><span>Acciones</span></button>'+
      '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
        '<button type="button" class="dropdown-item accion-item accion-editar table_editar nomina-detalle-ui-editar ocultar" data-index="'+index+'"><span class="accion-icon accion-icon-editar"><i class="fas fa-edit"></i></span><span class="accion-label">Editar</span></button>'+
        '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar nomina-detalle-ui-eliminar ocultar" data-index="'+index+'"><span class="accion-icon accion-icon-eliminar"><i class="fas fa-trash-alt"></i></span><span class="accion-label">Eliminar</span></button>'+
      '</div></div>';
}

function nominaActualizarKpis(rows){
    var gen=0,pend=0,total=0;
    rows.forEach(function(r){ Number(r.estado)===1?gen++:pend++; total+=nominaNum(r.importe); });
    $('#nominaKpiRegistros').text(rows.length);
    $('#nominaKpiGeneradas').text(gen);
    $('#nominaKpiPendientes').text(pend);
    $('#nominaKpiImporte,#nominaTotalListado').text(nominaMoney(total));
}

function nominaDetalleActualizarKpis(rows){
    var ing=0,egr=0,net=0;
    rows.forEach(function(r){ ing+=nominaNum(r.neto_ingresos); egr+=nominaNum(r.neto_egresos); net+=nominaNum(r.neto); });
    $('#nominaDetalleKpiRegistros').text(rows.length);
    $('#nominaDetalleKpiIngresos,#nominaDetalleTotalIngresos').text(nominaMoney(ing));
    $('#nominaDetalleKpiEgresos,#nominaDetalleTotalEgresos').text(nominaMoney(egr));
    $('#nominaDetalleKpiNeto,#nominaDetalleTotalNeto').text(nominaMoney(net));
}

function nominaFiltrarMain(){
    var q=$.trim(nominaUI.search||'').toLowerCase();
    nominaUI.filtered=!q?nominaUI.rows.slice():nominaUI.rows.filter(function(r){
        return [r.nomina_id,r.detalle,r.empresa,r.fecha_inicio,r.fecha_fin,r.importe,r.notas,Number(r.estado)===1?'generada':'sin generar']
            .map(function(v){return nominaVal(v,'').toLowerCase();}).join(' ').indexOf(q)!==-1;
    });
    nominaActualizarKpis(nominaUI.filtered);
}

function nominaFiltrarDetalle(){
    var q=$.trim(nominaDetalleUI.search||'').toLowerCase();
    nominaDetalleUI.filtered=!q?nominaDetalleUI.rows.slice():nominaDetalleUI.rows.filter(function(r){
        return [r.nomina_id,r.contrato,r.empresa,r.empleado,r.neto_ingresos,r.neto_egresos,r.neto,r.notas,Number(r.estado)===1?'generada':'sin generar']
            .map(function(v){return nominaVal(v,'').toLowerCase();}).join(' ').indexOf(q)!==-1;
    });
    nominaDetalleActualizarKpis(nominaDetalleUI.filtered);
}

function nominaRenderMain(){
    var rows=nominaUI.filtered||[];
    if(nominaUI.loading){ $('#nominaListado').html('<div class="nomina-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando nóminas</strong><span>Consultando información...</span></div>'); return; }
    if(!rows.length){ $('#nominaListado').html('<div class="nomina-state"><i class="fas fa-file-invoice-dollar"></i><strong>Sin nóminas</strong><span>No hay registros con los criterios actuales.</span></div>'); $('#nominaInfo').text('0 registros'); $('#nominaPaginacion').empty(); return; }

    var pages=Math.max(1,Math.ceil(rows.length/nominaUI.pageSize)); if(nominaUI.page>pages)nominaUI.page=pages;
    var start=(nominaUI.page-1)*nominaUI.pageSize, pageRows=rows.slice(start,start+nominaUI.pageSize), html='';

    if(nominaUI.view==='detalle'){
        html='<div class="nomina-detail-header"><div>Nómina</div><div>Empresa / Período</div><div>Importe</div><div>Notas</div><div>Estado</div><div>Acciones</div></div>';
        pageRows.forEach(function(r,i){
            var idx=start+i;
            html+='<article class="nomina-detail-row">'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Nómina</span><div class="nomina-main-title"><span class="nomina-code">#'+nominaEsc(r.nomina_id)+'</span><div><strong>'+nominaEsc(nominaVal(r.detalle,'Sin detalle'))+'</strong><small>Código de nómina '+nominaEsc(r.nomina_id)+'</small></div></div></div>'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Empresa / Período</span><div class="nomina-stack"><strong>'+nominaEsc(nominaVal(r.empresa,'Sin empresa'))+'</strong><span><i class="fas fa-calendar-alt mr-1"></i>'+nominaEsc(nominaVal(r.fecha_inicio,'N/A'))+' → '+nominaEsc(nominaVal(r.fecha_fin,'N/A'))+'</span></div></div>'+
              '<div class="nomina-cell nomina-money-cell"><span class="nomina-cell-label">Importe</span><strong>'+nominaMoney(r.importe)+'</strong></div>'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Notas</span><span class="nomina-notes">'+nominaEsc(nominaVal(r.notas,'Sin notas'))+'</span></div>'+
              '<div class="nomina-cell nomina-center"><span class="nomina-cell-label">Estado</span>'+nominaEstadoBadge(r.estado)+'</div>'+
              '<div class="nomina-cell nomina-center"><span class="nomina-cell-label">Acciones</span>'+nominaMainActions(r,idx)+'</div>'+
            '</article>';
        });
    }else{
        html='<div class="nomina-mini-grid">';
        pageRows.forEach(function(r,i){
            var idx=start+i;
            html+='<article class="nomina-mini-card"><div class="nomina-mini-topline"></div>'+
              '<div class="nomina-mini-header"><span class="nomina-mini-icon"><i class="fas fa-file-invoice-dollar"></i></span><div class="nomina-mini-title"><h4>'+nominaEsc(nominaVal(r.detalle,'Sin detalle'))+'</h4><span>#'+nominaEsc(r.nomina_id)+'</span></div>'+nominaEstadoBadge(r.estado)+'</div>'+
              '<div class="nomina-mini-body">'+
                '<div class="nomina-mini-field"><span>Empresa</span><strong>'+nominaEsc(nominaVal(r.empresa,'Sin empresa'))+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Importe</span><strong>'+nominaMoney(r.importe)+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Fecha Inicio</span><strong>'+nominaEsc(nominaVal(r.fecha_inicio,'N/A'))+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Fecha Fin</span><strong>'+nominaEsc(nominaVal(r.fecha_fin,'N/A'))+'</strong></div>'+
                '<div class="nomina-mini-field nomina-mini-field-full"><span>Notas</span><strong>'+nominaEsc(nominaVal(r.notas,'Sin notas'))+'</strong></div>'+
              '</div><div class="nomina-mini-footer">'+nominaMainActions(r,idx)+'</div></article>';
        });
        html+='</div>';
    }

    $('#nominaListado').removeClass('vista-detalle vista-miniatura').addClass('vista-'+nominaUI.view).html(html);
    $('#nominaInfo').text('Mostrando '+(start+1)+' a '+Math.min(start+pageRows.length,rows.length)+' de '+rows.length+' registros');
    nominaPaginacion('#nominaPaginacion',pages,nominaUI.page);
    if(typeof getPermisosTipoUsuarioAccesosTable==='function'&&typeof getPrivilegioTipoUsuario==='function') getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
}

function nominaRenderDetalle(){
    var rows=nominaDetalleUI.filtered||[];
    if(nominaDetalleUI.loading){ $('#nominaDetalleListado').html('<div class="nomina-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando empleados</strong><span>Consultando detalle de nómina...</span></div>'); return; }
    if(!rows.length){ $('#nominaDetalleListado').html('<div class="nomina-state"><i class="fas fa-users"></i><strong>Sin empleados</strong><span>No hay registros con los filtros actuales.</span></div>'); $('#nominaDetalleInfo').text('0 registros'); $('#nominaDetallePaginacion').empty(); return; }

    var pages=Math.max(1,Math.ceil(rows.length/nominaDetalleUI.pageSize)); if(nominaDetalleUI.page>pages)nominaDetalleUI.page=pages;
    var start=(nominaDetalleUI.page-1)*nominaDetalleUI.pageSize,pageRows=rows.slice(start,start+nominaDetalleUI.pageSize),html='';

    if(nominaDetalleUI.view==='detalle'){
        html='<div class="nomina-employee-header"><div>Empleado</div><div>Nómina / Contrato</div><div>Empresa</div><div>Ingresos</div><div>Egresos</div><div>Neto</div><div>Estado</div><div>Acciones</div></div>';
        pageRows.forEach(function(r,i){
            var idx=start+i;
            html+='<article class="nomina-employee-row">'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Empleado</span><div class="nomina-employee-identity"><span class="nomina-avatar">'+nominaEsc(nominaInitials(r.empleado))+'</span><div><strong>'+nominaEsc(nominaVal(r.empleado,'Sin empleado'))+'</strong><small>'+nominaEsc(nominaVal(r.notas,'Sin notas'))+'</small></div></div></div>'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Nómina / Contrato</span><div class="nomina-stack"><strong>Nómina #'+nominaEsc(r.nomina_id)+'</strong><span>'+nominaEsc(nominaVal(r.contrato,'Sin contrato'))+'</span></div></div>'+
              '<div class="nomina-cell"><span class="nomina-cell-label">Empresa</span>'+nominaEsc(nominaVal(r.empresa,'Sin empresa'))+'</div>'+
              '<div class="nomina-cell nomina-money-success"><span class="nomina-cell-label">Ingresos</span><strong>'+nominaMoney(r.neto_ingresos)+'</strong></div>'+
              '<div class="nomina-cell nomina-money-danger"><span class="nomina-cell-label">Egresos</span><strong>'+nominaMoney(r.neto_egresos)+'</strong></div>'+
              '<div class="nomina-cell nomina-money-cell"><span class="nomina-cell-label">Neto</span><strong>'+nominaMoney(r.neto)+'</strong></div>'+
              '<div class="nomina-cell nomina-center"><span class="nomina-cell-label">Estado</span>'+nominaEstadoBadge(r.estado)+'</div>'+
              '<div class="nomina-cell nomina-center"><span class="nomina-cell-label">Acciones</span>'+nominaDetalleActions(r,idx)+'</div>'+
            '</article>';
        });
    }else{
        html='<div class="nomina-mini-grid">';
        pageRows.forEach(function(r,i){
            var idx=start+i;
            html+='<article class="nomina-mini-card nomina-employee-mini"><div class="nomina-mini-topline"></div>'+
              '<div class="nomina-mini-header"><span class="nomina-avatar nomina-avatar-large">'+nominaEsc(nominaInitials(r.empleado))+'</span><div class="nomina-mini-title"><h4>'+nominaEsc(nominaVal(r.empleado,'Sin empleado'))+'</h4><span>'+nominaEsc(nominaVal(r.contrato,'Sin contrato'))+'</span></div>'+nominaEstadoBadge(r.estado)+'</div>'+
              '<div class="nomina-mini-body">'+
                '<div class="nomina-mini-field"><span>Empresa</span><strong>'+nominaEsc(nominaVal(r.empresa,'Sin empresa'))+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Nómina</span><strong>#'+nominaEsc(r.nomina_id)+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Ingresos</span><strong class="nomina-money-success">'+nominaMoney(r.neto_ingresos)+'</strong></div>'+
                '<div class="nomina-mini-field"><span>Egresos</span><strong class="nomina-money-danger">'+nominaMoney(r.neto_egresos)+'</strong></div>'+
                '<div class="nomina-mini-field nomina-mini-field-full"><span>Neto</span><strong>'+nominaMoney(r.neto)+'</strong></div>'+
                '<div class="nomina-mini-field nomina-mini-field-full"><span>Notas</span><strong>'+nominaEsc(nominaVal(r.notas,'Sin notas'))+'</strong></div>'+
              '</div><div class="nomina-mini-footer">'+nominaDetalleActions(r,idx)+'</div></article>';
        });
        html+='</div>';
    }

    $('#nominaDetalleListado').removeClass('vista-detalle vista-miniatura').addClass('vista-'+nominaDetalleUI.view).html(html);
    $('#nominaDetalleInfo').text('Mostrando '+(start+1)+' a '+Math.min(start+pageRows.length,rows.length)+' de '+rows.length+' registros');
    nominaPaginacion('#nominaDetallePaginacion',pages,nominaDetalleUI.page);
    if(typeof getPermisosTipoUsuarioAccesosTable==='function'&&typeof getPrivilegioTipoUsuario==='function') getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
}


var listar_nominas = function() {
    var estado = $("#form_main_nominas #estado_nomina").val() || 0;
    var tipo_contrato_id = $("#form_main_nominas #tipo_contrato_nomina").val() || 0;

    nominaUI.loading=true;
    nominaRenderMain();

    $.ajax({
        method:"POST",
        url:"<?php echo SERVERURL;?>core/llenarDataTableNomina.php",
        dataType:"json",
        data:{estado:estado,tipo_contrato_id:tipo_contrato_id}
    }).done(function(json){
        nominaUI.rows=(json&&Array.isArray(json.data))?json.data:[];
        nominaUI.search=$('#buscarNomina').val()||'';
        nominaUI.page=1;
        nominaUI.loading=false;
        nominaFiltrarMain();
        nominaRenderMain();
    }).fail(function(xhr){
        nominaUI.rows=[]; nominaUI.filtered=[]; nominaUI.loading=false;
        nominaActualizarKpis([]);
        nominaRenderMain();
        console.error('Error nómina:',xhr.responseText);
        showNotify('error','Error','No se pudo cargar la nómina.');
    });
};

function nominaUIAccionGenerar(data) {
if ($('#form_main_nominas #estado_nomina').val() == 0) {
            // CONFIRMACIÓN (sí/no) → swal
            swal({
                title: "¿Estas seguro?",
                text: "¿Desea generar esta nomina?",
                icon: "warning",
                buttons: {
                    cancel: { text: "Cancelar", visible: true },
                    confirm: { text: "¡Sí, generar la nómina!" }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then((ok) => {
                if (ok === true) {
                    genearNomina(data.nomina_id, data.empresa_id);
                }
            });
        } else {
            showNotify('error', 'Error', 'Lo sentimos, esta nomina ya ha sido generada');
        }
}

function nominaUIAccionCrearDetalle(data) {
$('#formNominaDetalles #nomina_id').val(data.nomina_id);
        $('#formNominaDetalles #nominad_numero').val(data.nomina_id);
        $("#form_main_nominas_detalles #nomina_id").val(data.nomina_id);
        $('#formNominaDetalles #nominad_detalle').val(data.detalle);
        $('#formNominaDetalles #pago_planificado_id').val(data.pago_planificado_id);
        $('#form_main_nominas_detalles #estado_nomina_detalles').val(data.estado).selectpicker('refresh');
        $('#form_main_nominas_detalles #fecha_inicio').val(data.fecha_inicio);
        $('#form_main_nominas_detalles #fecha_fin').val(data.fecha_fin);

        $("#nomina_principal").hide();
        $("#nomina_detalles").show();
        listar_nominas_detalles();
}

function nominaUIAccionVoucher(data) {
if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintVoucherPago(data.nomina_id);
        }
}

function nominaUIAccionLibro(data) {
if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintLibroSalarios(data.nomina_id);
        }
}

function nominaUIAccionEditar(data) {
var url = '<?php echo SERVERURL;?>core/editarNominas.php';
        $('#formNomina #nomina_id').val(data.nomina_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNomina').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar el FORM para UPDATE (se envía por submit ajax abajo)
                $('#formNomina').attr({'data-form': 'update'});
                $('#formNomina').attr({'action': '<?php echo SERVERURL;?>ajax/modificarNominaAjax.php'});

                // UI
                $('#formNomina')[0].reset();
                $('#reg_nomina').hide();
                $('#edi_nomina').show();
                $('#delete_nomina').hide();

                // Cargar valores
                $('#formNomina #nomina_detale').val(valores[0]);
                $('#formNomina #nomina_pago_planificado_id').val(valores[1]).selectpicker('refresh');
                $('#formNomina #nomina_empresa_id').val(valores[2]).selectpicker('refresh');
                $('#formNomina #nomina_fecha_inicio').val(valores[3]);
                $('#formNomina #nomina_fecha_fin').val(valores[4]);
                $('#formNomina #nomina_importe').val(valores[5]);
                $('#formNomina #nomina_notas').val(valores[6]);
                $('#formNomina #tipo_nomina').val(valores[8]).selectpicker('refresh');
                $('#formNomina #pago_nomina').val(valores[9]).selectpicker('refresh');

                if (data.estado == 1) {
                    $('#edi_nomina').attr('disabled', true);
                    $('#formNomina #nomina_activo').prop('checked', true);
                    $('#formNomina #label_nomina_activo').html("Generada");
                } else {
                    $('#edi_nomina').attr('disabled', false);
                    $('#formNomina #nomina_activo').prop('checked', false);
                    $('#formNomina #label_nomina_activo').html("Sin Generar");
                }

                // Habilitar campos
                $('#formNomina #nomina_detale').prop('disabled', false);
                $('#formNomina #nomina_pago_planificado_id').prop('disabled', false);
                $('#formNomina #nomina_empresa_id').prop('disabled', false);
                $('#formNomina #tipo_nomina').prop('disabled', false);
                $('#formNomina #nomina_fecha_inicio').prop('readonly', false);
                $('#formNomina #nomina_fecha_fin').prop('readonly', false);
                $('#formNomina #nomina_importe').prop('readonly', false);
                $('#formNomina #nomina_notas').prop('disabled', false);
                $('#formNomina #search_nomina_notas_start').prop('disabled', false);
                $('#formNomina #search_nomina_notas_stop').prop('disabled', false);
                $('#formNomina #nomina_activo').prop('disabled', false);
                $('#formNomina #estado_nomina').show();

                $('#formNomina #proceso_nomina').val("Editar");

                $('#modal_registrar_nomina').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
}

function nominaUIAccionEliminar(data) {
var nomina_id = data.nomina_id;
        var detalleNomina = data.detalle; 
        
        var mensajeHTML = `¿Desea eliminar permanentemente la nomina?<br><br>
                        <strong>Nomina:</strong> ${detalleNomina}<br>
                        <strong>Número Nomina:</strong> ${nomina_id}`;
        
        // CONFIRMACIÓN → swal
        swal({
            title: "Confirmar eliminación",
            content: { element: "span", attributes: { innerHTML: mensajeHTML } },
            icon: "warning",
            buttons: {
                cancel: { text: "Cancelar", value: null, visible: true, className: "btn-light" },
                confirm: { text: "Sí, eliminar", value: true, className: "btn-danger", closeModal: false }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarNominaAjax.php',
                    data: { nomina_id: nomina_id },
                    dataType: 'json',
                    beforeSend: function(){
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        if(response.status === "success") {
                            showNotify("success", response.title || "Éxito", response.message || "Eliminado correctamente");
                            table.ajax.reload(null, false);
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title || "Error", response.message || "No se pudo eliminar");
                        }
                    },
                    error: function() {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });
}

function nominaDetalleUIAccionEditar(data) {
var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar FORM para UPDATE
                $('#formNominaDetalles').attr({ 'data-form': 'update' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').show();
                $('#delete_nominaD').hide();

                // Mapear valores
                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[31]);

                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[31]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                var salario_hora = (valores[37] == 1) ? salario_diario / 8 : salario_diario / 6;

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(valores[24]);
                $('#formNominaDetalles #nominad_neto_egreso').val(valores[25]);
                $('#formNominaDetalles #nominad_neto').val(valores[26]);
                $('#formNominaDetalles #nominad_detalle').val(valores[36]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);
                $('#formNominaDetalles #nominad_vale').val(valores[30]);

                $('#formNominaDetalles #hrse25_valor').val(valores[32]);
                $('#formNominaDetalles #hrse50_valor').val(valores[33]);
                $('#formNominaDetalles #hrse75_valor').val(valores[34]);
                $('#formNominaDetalles #hrse100_valor').val(valores[35]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#edi_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#edi_nominaD').attr('disabled', false);
                }

                // Habilitar/Deshabilitar
                $('#formNominaDetalles #nominad_retroactivo').prop('readonly', false);
                $('#formNominaDetalles #nominad_bono').prop('readonly', false);
                $('#formNominaDetalles #nominad_otros_ingresos').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas25').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas50').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas75').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas100').prop('readonly', false);
                $('#formNominaDetalles #nominad_deducciones').prop('readonly', false);
                $('#formNominaDetalles #nominad_prestamo').prop('readonly', false);
                $('#formNominaDetalles #nominad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nominad_rap').prop('readonly', false);
                $('#formNominaDetalles #nominad_isr').prop('readonly', false);
                $('#formNominaDetalles #nominad_incapacidad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nomina_detalles_notas').prop('readonly', false);
                $('#formNominaDetalles #estado_nomina_detalles').show();

                $('#formNominaDetalles #nominad_empleados').prop('disabled', true);
                $('#formNominaDetalles #nominad_neto_ingreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto_egreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto').prop('readonly', true);
                $('#formNominaDetalles #nomina_detalles_activo').prop('disabled', true);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Editar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
}

function nominaDetalleUIAccionEliminar(data) {
var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {                
                var valores = eval(registro);

                // Configurar FORM para DELETE
                $('#formNominaDetalles').attr({ 'data-form': 'delete' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').hide();
                $('#delete_nominaD').show();

                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[3]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[4]);
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                let salario_hora = (parseFloat(salario_diario) / 8).toFixed(2);

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(salario_hora);

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(parseFloat(valores[24]).toFixed(2));
                $('#formNominaDetalles #nominad_neto_egreso').val(parseFloat(valores[25]).toFixed(2));
                $('#formNominaDetalles #nominad_neto').val(parseFloat(valores[26]).toFixed(2));
                $('#formNominaDetalles #nominad_detalle').val(valores[27]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#delete_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#delete_nominaD').attr('disabled', false);
                }

                $('#formNominaDetalles #estado_nomina_detalles').show();

                // Deshabilitar campos en vista de eliminación
                $('#formNominaDetalles input, #formNominaDetalles textarea, #formNominaDetalles select').prop('readonly', true).prop('disabled', true);
                $('#delete_nominaD').prop('disabled', false);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Eliminar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
}

// Dentro de tu archivo JS, deja este handler tal cual
var generar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.nomina_generar");
    $(tbody).on("click", "a.nomina_generar", function() {
        var data = table.row($(this).parents("tr")).data();

        if ($('#form_main_nominas #estado_nomina').val() == 0) {
            // CONFIRMACIÓN (sí/no) → swal
            swal({
                title: "¿Estas seguro?",
                text: "¿Desea generar esta nomina?",
                icon: "warning",
                buttons: {
                    cancel: { text: "Cancelar", visible: true },
                    confirm: { text: "¡Sí, generar la nómina!" }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then((ok) => {
                if (ok === true) {
                    genearNomina(data.nomina_id, data.empresa_id);
                }
            });
        } else {
            showNotify('error', 'Error', 'Lo sentimos, esta nomina ya ha sido generada');
        }
    });
};

// Llamada AJAX que espera JSON y, en éxito, muestra swal con 3 botones
// Voucher/Libro no cierran; solo "Cerrar" cierra. Se permite pulsarlos varias veces.
function genearNomina(nomina_id, empresa_id) {
    var url = '<?php echo SERVERURL; ?>core/generarNomina.php';

    $.ajax({
        type: "POST",
        url: url,
        data: { nomina_id: nomina_id, empresa_id: empresa_id },
        dataType: 'json',
        cache: false
    })
    .done(function(res){
        if (Number(res.status) === 1) {
            if (typeof listar_nominas === 'function') listar_nominas();

            // Abre el diálogo y engancha handlers sobre los botones del swal.
            function abrirDialogoImpresion(id, title, message){
                swal({
                    title: title || 'Nómina generada',
                    text:  message || 'La nómina se generó correctamente.',
                    icon:  'success',
                    buttons: {
                        voucher: { 
                            text: 'Imprimir Vouchers', 
                            value: 'voucher', 
                            className: 'btn btn-primary',
                            closeModal: false // mantener abierto
                        },
                        libro:   { 
                            text: 'Libro de Salarios', 
                            value: 'libro', 
                            className: 'btn btn-success',
                            closeModal: false // mantener abierto
                        },
                        cancel:  { 
                            text: 'Cerrar', 
                            value: null, 
                            visible: true, 
                            className: 'btn btn-light'
                            // (por defecto cierra)
                        }
                    },
                    closeOnClickOutside: false,
                    closeOnEsc: false
                });

                // Espera a que el DOM del swal exista y engancha eventos.
                setTimeout(function(){
                    // Evita duplicados con namespace
                    $(document)
                        .off('click.swalVoucher', '.swal-button--voucher')
                        .on('click.swalVoucher',  '.swal-button--voucher', function(e){
                            e.preventDefault();
                            if (typeof PrintVoucherPago === 'function') {
                                PrintVoucherPago(id);
                            }
                            // Quita el spinner y re-activa los botones
                            if (typeof swal.stopLoading === 'function') swal.stopLoading();
                        });

                    $(document)
                        .off('click.swalLibro', '.swal-button--libro')
                        .on('click.swalLibro',  '.swal-button--libro', function(e){
                            e.preventDefault();
                            if (typeof PrintLibroSalarios === 'function') {
                                PrintLibroSalarios(id);
                            }
                            if (typeof swal.stopLoading === 'function') swal.stopLoading();
                        });
                }, 0);
            }

            abrirDialogoImpresion(res.nomina_id, res.title, res.message);

        } else if (Number(res.status) === 6) {
            showNotify('warning', res.title || 'Advertencia', res.message || 'Configura la cuenta de la nómina.');
        } else if (Number(res.status) === 8) {
            showNotify('warning', res.title || 'Sin empleados', res.message || 'Agrega empleados al detalle antes de generar.');
        } else {
            showNotify('error', res.title || 'Error', res.message || 'No se pudo generar la nómina.');
        }
    })
    .fail(function(xhr){
        let msg = 'Error de conexión al generar la nómina.';
        try {
            const j = JSON.parse(xhr.responseText);
            if (j && j.message) msg = j.message;
        } catch(e){}
        showNotify('error', 'Error', msg);
    });
}

var crear_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_agregar");
    $(tbody).on("click", "button.nomina_agregar", function() {
        var data = table.row($(this).parents("tr")).data();
        $('#formNominaDetalles #nomina_id').val(data.nomina_id);
        $('#formNominaDetalles #nominad_numero').val(data.nomina_id);
        $("#form_main_nominas_detalles #nomina_id").val(data.nomina_id);
        $('#formNominaDetalles #nominad_detalle').val(data.detalle);
        $('#formNominaDetalles #pago_planificado_id').val(data.pago_planificado_id);
        $('#form_main_nominas_detalles #estado_nomina_detalles').val(data.estado).selectpicker('refresh');
        $('#form_main_nominas_detalles #fecha_inicio').val(data.fecha_inicio);
        $('#form_main_nominas_detalles #fecha_fin').val(data.fecha_fin);

        $("#nomina_principal").hide();
        $("#nomina_detalles").show();
        listar_nominas_detalles();
    });
};

var voucher_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.voucher_pago");
    $(tbody).on("click", "a.voucher_pago", function() {
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintVoucherPago(data.nomina_id);
        }
    });
};

var libro_salarios_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "a.consolidado");
    $(tbody).on("click", "a.consolidado", function() {
        var data = table.row($(this).parents("tr")).data();
        if (data.estado == 0) {
            showNotify('error', 'Error', 'Lo sentimos, la nomina no esta generada no se puede mostrar el reporte');
        } else {
            PrintLibroSalarios(data.nomina_id);
        }
    });
};

function PrintVoucherPago(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Voucher_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>",
    }; 
    viewReport(params, "Vocher de Pago");   
}

function PrintLibroSalarios(nomina_id){
    params = {
        "id": nomina_id,
        "type": "Libro_salario_izzy",
        "db": "<?php echo $GLOBALS['db']; ?>",
    }; 
    viewReport(params, "Libro de Salarios");
}

/* ============================
   EDITAR NÓMINA (usa el FORM)
   ============================ */
var editar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_editar");
    $(tbody).on("click", "button.nomina_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominas.php';
        $('#formNomina #nomina_id').val(data.nomina_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNomina').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar el FORM para UPDATE (se envía por submit ajax abajo)
                $('#formNomina').attr({'data-form': 'update'});
                $('#formNomina').attr({'action': '<?php echo SERVERURL;?>ajax/modificarNominaAjax.php'});

                // UI
                $('#formNomina')[0].reset();
                $('#reg_nomina').hide();
                $('#edi_nomina').show();
                $('#delete_nomina').hide();

                // Cargar valores
                $('#formNomina #nomina_detale').val(valores[0]);
                $('#formNomina #nomina_pago_planificado_id').val(valores[1]).selectpicker('refresh');
                $('#formNomina #nomina_empresa_id').val(valores[2]).selectpicker('refresh');
                $('#formNomina #nomina_fecha_inicio').val(valores[3]);
                $('#formNomina #nomina_fecha_fin').val(valores[4]);
                $('#formNomina #nomina_importe').val(valores[5]);
                $('#formNomina #nomina_notas').val(valores[6]);
                $('#formNomina #tipo_nomina').val(valores[8]).selectpicker('refresh');
                $('#formNomina #pago_nomina').val(valores[9]).selectpicker('refresh');

                if (data.estado == 1) {
                    $('#edi_nomina').attr('disabled', true);
                    $('#formNomina #nomina_activo').prop('checked', true);
                    $('#formNomina #label_nomina_activo').html("Generada");
                } else {
                    $('#edi_nomina').attr('disabled', false);
                    $('#formNomina #nomina_activo').prop('checked', false);
                    $('#formNomina #label_nomina_activo').html("Sin Generar");
                }

                // Habilitar campos
                $('#formNomina #nomina_detale').prop('disabled', false);
                $('#formNomina #nomina_pago_planificado_id').prop('disabled', false);
                $('#formNomina #nomina_empresa_id').prop('disabled', false);
                $('#formNomina #tipo_nomina').prop('disabled', false);
                $('#formNomina #nomina_fecha_inicio').prop('readonly', false);
                $('#formNomina #nomina_fecha_fin').prop('readonly', false);
                $('#formNomina #nomina_importe').prop('readonly', false);
                $('#formNomina #nomina_notas').prop('disabled', false);
                $('#formNomina #search_nomina_notas_start').prop('disabled', false);
                $('#formNomina #search_nomina_notas_stop').prop('disabled', false);
                $('#formNomina #nomina_activo').prop('disabled', false);
                $('#formNomina #estado_nomina').show();

                $('#formNomina #proceso_nomina').val("Editar");

                $('#modal_registrar_nomina').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var eliminar_nominas_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_eliminar");
    $(tbody).on("click", "button.nomina_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();

        var nomina_id = data.nomina_id;
        var detalleNomina = data.detalle; 
        
        var mensajeHTML = `¿Desea eliminar permanentemente la nomina?<br><br>
                        <strong>Nomina:</strong> ${detalleNomina}<br>
                        <strong>Número Nomina:</strong> ${nomina_id}`;
        
        // CONFIRMACIÓN → swal
        swal({
            title: "Confirmar eliminación",
            content: { element: "span", attributes: { innerHTML: mensajeHTML } },
            icon: "warning",
            buttons: {
                cancel: { text: "Cancelar", value: null, visible: true, className: "btn-light" },
                confirm: { text: "Sí, eliminar", value: true, className: "btn-danger", closeModal: false }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmar) => {
            if (confirmar) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL;?>ajax/eliminarNominaAjax.php',
                    data: { nomina_id: nomina_id },
                    dataType: 'json',
                    beforeSend: function(){
                        showLoading("Eliminando registro...");
                    },
                    success: function(response) {
                        swal.close();
                        if(response.status === "success") {
                            showNotify("success", response.title || "Éxito", response.message || "Eliminado correctamente");
                            table.ajax.reload(null, false);
                            table.search('').draw();                    
                        } else {
                            showNotify("error", response.title || "Error", response.message || "No se pudo eliminar");
                        }
                    },
                    error: function() {
                        swal.close();
                        showNotify("error", "Error", "Ocurrió un error al procesar la solicitud");
                    }
                });
            }
        });		
    });
};

/* ============================
   MODAL NÓMINAS (abre y prepara el form)
   ============================ */
function modal_nominas() {
    $('#formNomina').attr({ 'data-form': 'save' });
    $('#formNomina').attr({ 'action': '<?php echo SERVERURL;?>ajax/addNominaAjax.php' });

    $('#formNomina')[0].reset();
    $('#reg_nomina').show();
    $('#edi_nomina').hide();
    $('#delete_nomina').hide();

    $('#formNomina #nomina_empresa_id').val(1).selectpicker('refresh');
    $('#formNomina #tipo_nomina').val(1).selectpicker('refresh');

    $("#formNomina #grupo_salario").hide();

    $('#formNomina #nomina_detale').prop('disabled', false);
    $('#formNomina #nomina_pago_planificado_id').prop('disabled', false);
    $('#formNomina #nomina_empresa_id').prop('disabled', false);
    $('#formNomina #tipo_nomina').prop('disabled', false);
    $('#formNomina #nomina_fecha_inicio').prop('readonly', false);
    $('#formNomina #nomina_fecha_fin').prop('readonly', false);
    $('#formNomina #nomina_importe').prop('readonly', false);
    $('#formNomina #nomina_notas').prop('disabled', false);
    $('#formNomina #search_nomina_notas_start').prop('disabled', false);
    $('#formNomina #search_nomina_notas_stop').prop('disabled', false);
    $('#formNomina #nomina_activo').prop('disabled', false);
    $('#formNomina #estado_nomina').hide();

    $('#formNomina #proceso_nomina').val("Registro Nomina Empleados");

    $('#modal_registrar_nomina').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/* ============================
   SUBMIT AJAX #formNomina (JSON)
   ============================ */
$(document).off('submit', '#formNomina').on('submit', '#formNomina', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addNominaAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_nomina') : (modo === 'delete') ? $('#delete_nomina') : $('#reg_nomina');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modal_registrar_nomina').modal('hide');
            $form[0].reset();
            $form.find('.selectpicker').selectpicker('refresh');

            if (res.run) { try { eval(res.run); } catch(e){} }

            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function (xhr) {
        let msg = 'Error de conexión. Intenta de nuevo.';
        try {
            const json = JSON.parse(xhr.responseText);
            if (json && json.message) msg = json.message;
        } catch(e){}
        showNotify('error', 'Error', msg);
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   MODAL VALES (abre y prepara)
   ============================ */
function modal_vales() {
    $('#formVales').attr({ 'data-form': 'save' });
    $('#formVales').attr({ 'action': '<?php echo SERVERURL;?>ajax/addValesAjax.php' });
    $('#formVales')[0].reset();

    $('#reg_vale').show();
    $('#edi_vale').hide();
    $('#delete_vale').hide();

    $('#formVales #vale_empleado').prop('disabled', false);
    $('#formVales #vale_monto').prop('disabled', false);
    $('#formVales #vale_notas').prop('disabled', false);

    $('#formVales #proceso_vale').val("Registro Vale Empleados");

    $('#modalRegistrarVales').modal({
        show: true,
        keyboard: false,
        backdrop: 'static'
    });
}

/* ============================
   MODAL NOMINA DETALLES (ABRIR NUEVO)
   ============================ */
function modalNominasDetalles() {
    if ($('#form_main_nominas #estado_nomina').val() == 0) {
        $('#formNominaDetalles').attr({ 'data-form': 'save' });
        $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/addNominaDetallesAjax.php' });

        var nomina_id = $('#formNominaDetalles #nomina_id').val();
        var numero_nomima = $('#formNominaDetalles #nominad_numero').val();
        var detalle = $('#formNominaDetalles #nominad_detalle').val();

        $('#formNominaDetalles')[0].reset();
        getEmpleado();

        $('#formNominaDetalles #nomina_id').val(nomina_id);
        $('#formNominaDetalles #nominad_numero').val(numero_nomima);
        $('#formNominaDetalles #nominad_detalle').val(detalle);

        $('#formNominaDetalles #fecha_inicio').val($('#form_main_nominas_detalles #fecha_inicio').val());
        $('#formNominaDetalles #fecha_fin').val($('#form_main_nominas_detalles #fecha_fin').val());

        $('#reg_nominaD').show();
        $('#edi_nominaD').hide();
        $('#delete_nominaD').hide();

        // Habilitar campos
        $('#formNominaDetalles #nominad_empleados').prop('disabled', false);
        $('#formNominaDetalles #nominad_retroactivo').prop('readonly', false);
        $('#formNominaDetalles #nominad_bono').prop('readonly', false);
        $('#formNominaDetalles #nominad_otros_ingresos').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas25').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas50').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas75').prop('readonly', false);
        $('#formNominaDetalles #nominad_horas100').prop('readonly', false);
        $('#formNominaDetalles #nominad_deducciones').prop('readonly', false);
        $('#formNominaDetalles #nominad_prestamo').prop('readonly', false);
        $('#formNominaDetalles #nominad_ihss').prop('readonly', false);
        $('#formNominaDetalles #nominad_rap').prop('readonly', false);
        $('#formNominaDetalles #nominad_isr').prop('readonly', false);
        $('#formNominaDetalles #nominad_incapacidad_ihss').prop('readonly', false);
        $('#formNominaDetalles #nomina_detalles_notas').prop('readonly', false);
        $('#formNominaDetalles #nominad_neto_ingreso').prop('readonly', true);
        $('#formNominaDetalles #nominad_neto_egreso').prop('readonly', true);
        $('#formNominaDetalles #nominad_neto').prop('readonly', true);
        $('#formNominaDetalles #nomina_detalles_activo').prop('disabled', false);
        $('#formNominaDetalles #estado_nomina_detalles').hide();

        $('#formNominaDetalles #proceso_nomina_detalles').val("Registro");

        $('#modal_registrar_nomina_detalles').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    } else {
        showNotify('error', 'Error', 'Lo sentimos, esta nomina ya ha sido generada, no puede agregar más empleados');
    }
}

/* ============================
   SUBMIT AJAX #formNominaDetalles (JSON)
   Sirve para save/update/delete según data-form
   ============================ */
$(document).off('submit', '#formNominaDetalles').on('submit', '#formNominaDetalles', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addNominaDetallesAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_nominaD') : (modo === 'delete') ? $('#delete_nominaD') : $('#reg_nominaD');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modal_registrar_nomina_detalles').modal('hide');
            if (res.run) { try { eval(res.run); } catch(e){} }
            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function () {
        showNotify('error', 'Error', 'Error de conexión. Intenta de nuevo.');
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   SUBMIT AJAX #formVales (JSON)
   ============================ */
$(document).off('submit', '#formVales').on('submit', '#formVales', function (e) {
    e.preventDefault();

    var $form = $(this);
    var url = $form.attr('action') || '<?php echo SERVERURL;?>ajax/addValesAjax.php';
    var modo = ($form.attr('data-form') || 'save').toLowerCase();
    var $btn = (modo === 'update') ? $('#edi_vale') : (modo === 'delete') ? $('#delete_vale') : $('#reg_vale');

    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    $btn.prop('disabled', true).append(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: url,
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        cache: false
    })
    .done(function (res) {
        if (res.status === 'success') {
            $('#modalRegistrarVales').modal('hide');
            listar_vales();
            // NOTIFICACIÓN → showNotify
            showNotify('success', res.title || '¡Listo!', res.message || 'Operación realizada correctamente.');
        } else if (res.status === 'unauthorized') {
            showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');
            if (res.redirect) { setTimeout(function(){ window.location.href = res.redirect; }, 1200); }
        } else {
            var extra = (res.missing && res.missing.length) ? " Faltan: " + res.missing.join(', ') : '';
            showNotify('error', res.title || 'Error', (res.message || 'Operación no realizada.') + extra);
        }
    })
    .fail(function () {
        showNotify('error', 'Error', 'Error de conexión. Intenta de nuevo.');
    })
    .always(function () {
        $btn.prop('disabled', false).find('.spinner-border').remove();
    });
});

/* ============================
   FOCUS AL ABRIR MODAL NOMINA
   ============================ */
$(() => {
    $("#modal_registrar_nomina").on('shown.bs.modal', function() {
        $(this).find('#formNomina #nomina_detale').focus();
    });
});

$('#formNomina #label_nomina_activo').html("Sin Generar");

$('#formNomina .switch').change(function() {
    if ($('input[name=nomina_activo]').is(':checked')) {
        $('#formNomina #label_nomina_activo').html("Generada");
        return true;
    } else {
        $('#formNomina #label_nomina_activo').html("Sin Generar");
        return false;
    }
});

$('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");

$('#formNominaDetalles .switch').change(function() {
    if ($('input[name=nomina_detalles_activo]').is(':checked')) {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Generada");
        return true;
    } else {
        $('#formNominaDetalles #label_nomina_detalles_activo').html("Sin Generar");
        return false;
    }
});

/* ============================
   CARGAS DE SELECTS
   ============================ */
function getTipoNomina() {
    var url = '<?php echo SERVERURL;?>core/getTipoNomina.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #tipo_nomina').html("").html(data).selectpicker('refresh');
            $('#formNomina #tipo_nomina').val(1).selectpicker('refresh');
        }
    });
}

function getTipoContrato() {
    var url = '<?php echo SERVERURL;?>core/getTipoContrato.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#form_main_nominas #tipo_contrato_nomina').html("").html(data).selectpicker('refresh');
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
            $('#form_main_nominas #pago_planificado_nomina').html("").html(data).selectpicker('refresh');
            $('#formNomina #nomina_pago_planificado_id').html("").html(data).selectpicker('refresh');
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
            $('#form_main_contrato #tipo_empleado').html("").html(data).selectpicker('refresh');
        }
    });
}

function getEmpresa() {
    $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST",
        dataType: "json",
        success: function(response) {
            const select = $('#formNomina #nomina_empresa_id');
            select.empty();
            
            if(response.success) {
                response.data.forEach(empresa => {
                    select.append(`<option value="${empresa.empresa_id}">${empresa.nombre}</option>`);
                });
                if(response.data.length > 0) {
                    select.val(1);
                    select.selectpicker('refresh');
                }
            } else {
                select.append('<option value="">No hay empresas disponibles</option>');
                showNotify("warning", "Advertencia", response.message || "No se encontraron empresas");
            }
            select.selectpicker('refresh');
        },
        error: function() {
            showNotify("error", "Error", "Error de conexión al cargar empresas");
            $('#formNomina #nomina_empresa_id').html('<option value="">Error al cargar</option>').selectpicker('refresh');
        }
    });
}

function getEmpleado() {
  var url = '<?php echo SERVERURL;?>core/getEmpleado.php';
  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data) {
      // prepend el placeholder en todos los combos de empleados
      var opciones = '<option value="">Seleccione</option>' + data;

      // Modal Nomina Detalles (alta/edición)
      $('#formNominaDetalles #nominad_empleados')
        .html(opciones).selectpicker('refresh');

      // Filtro en la vista de detalles (ESTE ES EL QUE TE FALTABA)
      $('#form_main_nominas_detalles #detalle_nomina_empleado')
        .html(opciones).selectpicker('refresh');

      // Por si quieres también mantener este otro (vales)
      $('#formVales #vale_empleado')
        .html(opciones).selectpicker('refresh');
    },
    error: function(){
      showNotify('error', 'Error', 'Error de conexión al cargar empleados');
    }
  });
}

function getEmpleadoVales() {
    var url = '<?php echo SERVERURL;?>core/getEmpleado.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formVales #vale_empleado').html("").html(data).selectpicker('refresh');
        }
    });
}

/* ============================
   VOLVER A LISTA DETALLES
   ============================ */
$("#volver_nomina").on("click", function(e) {
    e.preventDefault();
    $("#nomina_detalles").hide();
    $("#nomina_principal").show();
});

/* =========================================================
   HEADER Y FOOTER DINÁMICO - DETALLES DE NÓMINA
   ========================================================= */

   function construirHeaderFooterDataTableNominaDetalles() {
    var $tabla = $("#dataTableNominaDetalles");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Nomina</th>' +
                '<th>Contrato</th>' +
                '<th>Empresa</th>' +
                '<th>Empleado</th>' +
                '<th>Neto Ingresos</th>' +
                '<th>Neto Egresos</th>' +
                '<th>Neto</th>' +
                '<th>Notas</th>' +
                '<th>Estado</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="1">Total</td>' +
                '<td colspan="4"></td>' +
                '<td id="neto_ingreso"></td>' +
                '<td id="neto_egreso"></td>' +
                '<td id="neto"></td>' +
                '<td colspan="2"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* ============================
   LISTADO DETALLES DE NÓMINA
   ============================ */

var listar_nominas_detalles = function() {
    var estado = $("#form_main_nominas_detalles #estado_nomina_detalles").val() || 0;
    var empleado = $("#form_main_nominas_detalles #detalle_nomina_empleado").val() || 0;
    var nomina_id = $("#form_main_nominas_detalles #nomina_id").val() || 0;

    $("#nominad_neto_ingreso1,#nominad_neto_egreso1,#nominad_neto1").val("");

    nominaDetalleUI.loading=true;
    nominaRenderDetalle();

    $.ajax({
        method:"POST",
        url:"<?php echo SERVERURL;?>core/llenarDataTableNominaDetalles.php",
        dataType:"json",
        data:{estado:estado,empleado:empleado,nomina_id:nomina_id}
    }).done(function(json){
        nominaDetalleUI.rows=(json&&Array.isArray(json.data))?json.data:[];
        nominaDetalleUI.search=$('#buscarNominaDetalle').val()||'';
        nominaDetalleUI.page=1;
        nominaDetalleUI.loading=false;
        nominaFiltrarDetalle();
        nominaRenderDetalle();
    }).fail(function(xhr){
        nominaDetalleUI.rows=[]; nominaDetalleUI.filtered=[]; nominaDetalleUI.loading=false;
        nominaDetalleActualizarKpis([]);
        nominaRenderDetalle();
        console.error('Error detalle nómina:',xhr.responseText);
        showNotify('error','Error','No se pudo cargar el detalle de nómina.');
    });
};

/* ============================
   EDITAR DETALLES (usa el FORM)
   ============================ */
var editar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_editar");
    $(tbody).on("click", "button.nomina_detalles_editar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {
                var valores = eval(registro);

                // Configurar FORM para UPDATE
                $('#formNominaDetalles').attr({ 'data-form': 'update' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/modificarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').show();
                $('#delete_nominaD').hide();

                // Mapear valores
                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[31]);

                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[31]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                var salario_hora = (valores[37] == 1) ? salario_diario / 8 : salario_diario / 6;

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(valores[24]);
                $('#formNominaDetalles #nominad_neto_egreso').val(valores[25]);
                $('#formNominaDetalles #nominad_neto').val(valores[26]);
                $('#formNominaDetalles #nominad_detalle').val(valores[36]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);
                $('#formNominaDetalles #nominad_vale').val(valores[30]);

                $('#formNominaDetalles #hrse25_valor').val(valores[32]);
                $('#formNominaDetalles #hrse50_valor').val(valores[33]);
                $('#formNominaDetalles #hrse75_valor').val(valores[34]);
                $('#formNominaDetalles #hrse100_valor').val(valores[35]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#edi_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#edi_nominaD').attr('disabled', false);
                }

                // Habilitar/Deshabilitar
                $('#formNominaDetalles #nominad_retroactivo').prop('readonly', false);
                $('#formNominaDetalles #nominad_bono').prop('readonly', false);
                $('#formNominaDetalles #nominad_otros_ingresos').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas25').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas50').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas75').prop('readonly', false);
                $('#formNominaDetalles #nominad_horas100').prop('readonly', false);
                $('#formNominaDetalles #nominad_deducciones').prop('readonly', false);
                $('#formNominaDetalles #nominad_prestamo').prop('readonly', false);
                $('#formNominaDetalles #nominad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nominad_rap').prop('readonly', false);
                $('#formNominaDetalles #nominad_isr').prop('readonly', false);
                $('#formNominaDetalles #nominad_incapacidad_ihss').prop('readonly', false);
                $('#formNominaDetalles #nomina_detalles_notas').prop('readonly', false);
                $('#formNominaDetalles #estado_nomina_detalles').show();

                $('#formNominaDetalles #nominad_empleados').prop('disabled', true);
                $('#formNominaDetalles #nominad_neto_ingreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto_egreso').prop('readonly', true);
                $('#formNominaDetalles #nominad_neto').prop('readonly', true);
                $('#formNominaDetalles #nomina_detalles_activo').prop('disabled', true);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Editar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

var eliminar_nominas_detalles_dataTable = function(tbody, table) {
    $(tbody).off("click", "button.nomina_detalles_eliminar");
    $(tbody).on("click", "button.nomina_detalles_eliminar", function() {
        var data = table.row($(this).parents("tr")).data();
        var url = '<?php echo SERVERURL;?>core/editarNominasDetalles.php';
        $('#formNominaDetalles #nomina_detalles_id').val(data.nomina_detalles_id);

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#formNominaDetalles').serialize(),
            success: function(registro) {                
                var valores = eval(registro);

                // Configurar FORM para DELETE
                $('#formNominaDetalles').attr({ 'data-form': 'delete' });
                $('#formNominaDetalles').attr({ 'action': '<?php echo SERVERURL;?>ajax/eliminarNominaDetallesAjax.php' });

                $('#formNominaDetalles')[0].reset();
                $('#reg_nominaD').hide();
                $('#edi_nominaD').hide();
                $('#delete_nominaD').show();

                $('#formNominaDetalles #nomina_id').val(valores[0]);
                $('#formNominaDetalles #nomina_detalles_id').val(valores[1]);
                $('#formNominaDetalles #pago_planificado_id').val(valores[2]);
                $('#formNominaDetalles #colaboradores_id').val(valores[3]).selectpicker('refresh');
                $('#formNominaDetalles #nominad_numero').val(valores[0]);
                $('#formNominaDetalles #nominad_empleados').val(valores[4]);
                $('#formNominaDetalles #nominad_puesto').val(valores[5]);
                $('#formNominaDetalles #nominad_identidad').val(valores[6]);
                $('#formNominaDetalles #nominad_contrato_id').val(valores[7]);
                $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[8]);
                $('#formNominaDetalles #nominad_salario').val(parseFloat(valores[9]).toFixed(2));

                let salario_diario = (valores[9] / 30).toFixed(2);
                let salario_hora = (parseFloat(salario_diario) / 8).toFixed(2);

                $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario);
                $('#formNominaDetalles #nominad_sueldo_hora').val(salario_hora);

                $('#formNominaDetalles #nominad_diast').val(valores[10]);
                $('#formNominaDetalles #nominad_retroactivo').val(valores[11]);
                $('#formNominaDetalles #nominad_bono').val(valores[12]);
                $('#formNominaDetalles #nominad_otros_ingresos').val(valores[13]);
                $('#formNominaDetalles #nominad_horas25').val(valores[14]);
                $('#formNominaDetalles #nominad_horas50').val(valores[15]);
                $('#formNominaDetalles #nominad_horas75').val(valores[16]);
                $('#formNominaDetalles #nominad_horas100').val(valores[17]);
                $('#formNominaDetalles #nominad_deducciones').val(valores[18]);
                $('#formNominaDetalles #nominad_prestamo').val(valores[19]);
                $('#formNominaDetalles #nominad_ihss').val(valores[20]);
                $('#formNominaDetalles #nominad_rap').val(valores[21]);
                $('#formNominaDetalles #nominad_isr').val(valores[22]);
                $('#formNominaDetalles #nominad_vales').val(valores[30]);
                $('#formNominaDetalles #nominad_incapacidad_ihss').val(valores[23]);
                $('#formNominaDetalles #nominad_neto_ingreso').val(parseFloat(valores[24]).toFixed(2));
                $('#formNominaDetalles #nominad_neto_egreso').val(parseFloat(valores[25]).toFixed(2));
                $('#formNominaDetalles #nominad_neto').val(parseFloat(valores[26]).toFixed(2));
                $('#formNominaDetalles #nominad_detalle').val(valores[27]);
                $('#formNominaDetalles #nomina_detalles_notas').val(valores[28]);

                calculoNomina();

                if (valores[29] == 1) {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', true);
                    $('#delete_nominaD').attr('disabled', true);
                } else {
                    $('#formNominaDetalles #nomina_detalles_activo').prop('checked', false);
                    $('#delete_nominaD').attr('disabled', false);
                }

                $('#formNominaDetalles #estado_nomina_detalles').show();

                // Deshabilitar campos en vista de eliminación
                $('#formNominaDetalles input, #formNominaDetalles textarea, #formNominaDetalles select').prop('readonly', true).prop('disabled', true);
                $('#delete_nominaD').prop('disabled', false);

                $('#formNominaDetalles #proceso_nomina_detalles').val("Eliminar");

                $('#modal_registrar_nomina_detalles').modal({
                    show: true,
                    keyboard: false,
                    backdrop: 'static'
                });
            }
        });
    });
};

/* ============================
   UTILIDADES DE CÁLCULO
   ============================ */
function pagoPlanificado(pago_planificado) {
    var diasTrabajadosMap = { 1: 7, 2: 15, 3: 30 };
    return diasTrabajadosMap[pago_planificado] || 0;
}

function obtenerDatosEmpleado(colaboradores_id) {
    var url = '<?php echo SERVERURL;?>core/getDatosEmpleado.php';

    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: 'colaboradores_id=' + colaboradores_id,
        success: function(data) {
            var valores = JSON.parse(data);
            var validar_semanal = valores[9];

            var valor_dividir = pagoPlanificado(valores[6]);
            var salario = parseFloat(valores[3]);
            var salario_diario = salario / 30;
            var salario_hora = (valores[5] == 1) ? salario_diario / 8 : salario_diario / 6;

            // Asignar valores
            $('#formNominaDetalles #nominad_puesto').val(valores[0]);
            $('#formNominaDetalles #nominad_identidad').val(valores[1]);
            $('#formNominaDetalles #nominad_contrato_id').val(valores[2]);
            $('#formNominaDetalles #nominad_salario').val(parseFloat(salario).toFixed(2));
            $('#formNominaDetalles #nominad_fecha_ingreso').val(valores[4]);
            $('#formNominaDetalles #nominad_sueldo_diario').val(salario_diario.toFixed(2));
            $('#formNominaDetalles #nominad_sueldo_hora').val(parseFloat(salario_hora).toFixed(2));
            $('#formNominaDetalles #nominad_vale').val(valores[7]);
            $('#formNominaDetalles #salario').val(parseFloat(valores[8]).toFixed(2));
            $('#formNominaDetalles #validar_semanal').val(valores[9]);
            $('#formNominaDetalles #pago_planificado_id').val(valor_dividir);

            var fecha_inicio = $('#formNominaDetalles #fecha_inicio').val();
            var fecha_fin = $('#formNominaDetalles #fecha_fin').val();

            $('#formNominaDetalles #nominad_diast').val(ObtenerDiasTrabajados(colaboradores_id, fecha_inicio, fecha_fin));
            calculoNomina();
        }
    });
}

$("#formNominaDetalles #nominad_empleados").on("change", function() {
    var colaboradores_id = $(this).val();
    obtenerDatosEmpleado(colaboradores_id);
});

function calculoHorasExtras(hora_valor, salario_hora, horas) {
    var porcentaje = parseFloat(horas) / 100;
    if (porcentaje >= 0.25 && porcentaje <= 1) {
        return parseFloat((salario_hora * porcentaje + salario_hora) * (parseFloat(hora_valor) || 0));
    }
    return 0;
}

function calculoNomina() {
    var neto_ingresos = 0;
    var neto_egresos = 0;
    var neto = 0;

    var validar_semanal = parseFloat($('#formNominaDetalles #validar_semanal').val() || 0);

    // INGRESOS
    var dias_trabajadas = parseFloat($('#formNominaDetalles #nominad_diast').val()) || 0;
    var salario_hora = parseFloat($('#formNominaDetalles #nominad_sueldo_hora').val()) || 0;
    var salario_mensual = parseFloat($('#formNominaDetalles #nominad_salario').val()) || 0;

    var hora25  = calculoHorasExtras($('#formNominaDetalles #nominad_horas25').val(),  salario_hora, "25");
    var hora50  = calculoHorasExtras($('#formNominaDetalles #nominad_horas50').val(),  salario_hora, "50");
    var hora75  = calculoHorasExtras($('#formNominaDetalles #nominad_horas75').val(),  salario_hora, "75");
    var hora100 = calculoHorasExtras($('#formNominaDetalles #nominad_horas100').val(), salario_hora, "100");

    $('#formNominaDetalles #hrse25_valor').val(hora25.toFixed(2));
    $('#formNominaDetalles #hrse50_valor').val(hora50.toFixed(2));
    $('#formNominaDetalles #hrse75_valor').val(hora75.toFixed(2));
    $('#formNominaDetalles #hrse100_valor').val(hora100.toFixed(2));

    var retroactivo    = parseFloat($('#formNominaDetalles #nominad_retroactivo').val()) || 0;
    var bono           = parseFloat($('#formNominaDetalles #nominad_bono').val()) || 0;
    var otros_ingresos = parseFloat($('#formNominaDetalles #nominad_otros_ingresos').val()) || 0;

    if (validar_semanal === 1) {
        neto_ingresos = dias_trabajadas * ((salario_mensual / 4) / 7) + retroactivo + bono + otros_ingresos + hora25 + hora50 + hora75 + hora100;
    } else {
        neto_ingresos = dias_trabajadas * (salario_mensual / 30) + retroactivo + bono + otros_ingresos + hora25 + hora50 + hora75 + hora100;
    }

    // EGRESOS
    var deducciones      = parseFloat($('#formNominaDetalles #nominad_deducciones').val()) || 0;
    var prestamo         = parseFloat($('#formNominaDetalles #nominad_prestamo').val()) || 0;
    var ihss             = parseFloat($('#formNominaDetalles #nominad_ihss').val()) || 0;
    var rap              = parseFloat($('#formNominaDetalles #nominad_rap').val()) || 0;
    var isr              = parseFloat($('#formNominaDetalles #nominad_isr').val()) || 0;
    var vales            = parseFloat($('#formNominaDetalles #nominad_vale').val()) || 0;
    var incapacidad_ihss = parseFloat($('#formNominaDetalles #nominad_incapacidad_ihss').val()) || 0;

    neto_egresos = deducciones + prestamo + ihss + rap + isr + incapacidad_ihss + vales;
    neto = neto_ingresos - neto_egresos;

    $('#formNominaDetalles #nominad_neto_ingreso').val(neto_ingresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto_egreso').val(neto_egresos.toFixed(2));
    $('#formNominaDetalles #nominad_neto').val(neto.toFixed(2));

    $('#nominad_neto_ingreso1').val(neto_ingresos.toFixed(2));
    $('#nominad_neto_egreso1').val(neto_egresos.toFixed(2));
    $('#nominad_neto1').val(neto.toFixed(2));
}

function actualizarCampo(selector, valor) { $(selector).val(valor); }

$("#formNominaDetalles #nominad_diast, \
#formNominaDetalles #nominad_retroactivo, \
#formNominaDetalles #nominad_bono, \
#formNominaDetalles #nominad_otros_ingresos, \
#formNominaDetalles #nominad_horas25, \
#formNominaDetalles #nominad_horas50, \
#formNominaDetalles #nominad_horas75, \
#formNominaDetalles #nominad_horas100, \
#formNominaDetalles #nominad_deducciones, \
#formNominaDetalles #nominad_prestamo, \
#formNominaDetalles #nominad_ihss, \
#formNominaDetalles #nominad_rap, \
#formNominaDetalles #nominad_isr, \
#formNominaDetalles #nominad_incapacidad_ihss, \
#formNominaDetalles #nominad_vale").on("keyup change", function() {
    calculoNomina();
});

function ObtenerDiasTrabajados(colaboradores_id, fechaiNomina, fechafNomina) {
    var url = '<?php echo SERVERURL;?>core/getDiasTrabajados.php';
    var dt;

    $.ajax({
        type: 'POST',
        url: url,
        data: { colaboradores_id: colaboradores_id, fechaiNomina: fechaiNomina, fechafNomina: fechafNomina },
        success: function(registro) {
            try {
                var valores = JSON.parse(registro);
                dt = valores[0];
            } catch (error) {
                console.error("Error al procesar la respuesta JSON:", error);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
        },
        async: false // (mantienes sync)
    });

    return dt;
}

function getCuentaNominas() {
    var url = '<?php echo SERVERURL;?>core/getCuenta.php';
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        success: function(data) {
            $('#formNomina #pago_nomina').html("").html(data).selectpicker('refresh');
        }
    });
}

/* ============================
   VALES (tabla + anular)
   ============================ */
var listar_vales = function() {
    nominaValesUI.loading=true;
    nominaRenderVales();

    $.ajax({
        method:"POST",
        url:"<?php echo SERVERURL;?>core/llenarDataTableVales.php",
        dataType:"json"
    }).done(function(json){
        nominaValesUI.rows=(json&&Array.isArray(json.data))?json.data:[];
        nominaValesUI.search=$('#buscarVales').val()||'';
        nominaValesUI.page=1;
        nominaValesUI.loading=false;
        nominaFiltrarVales();
        nominaRenderVales();
    }).fail(function(xhr){
        nominaValesUI.rows=[]; nominaValesUI.filtered=[]; nominaValesUI.loading=false;
        nominaRenderVales();
        console.error('Error vales:',xhr.responseText);
        showNotify('error','Error','No se pudo cargar el historial de vales.');
    });
};

function nominaFiltrarVales(){
    var q=$.trim(nominaValesUI.search||'').toLowerCase();
    nominaValesUI.filtered=!q?nominaValesUI.rows.slice():nominaValesUI.rows.filter(function(r){
        return [r.empleado,r.monto,r.nota].map(function(v){return nominaVal(v,'').toLowerCase();}).join(' ').indexOf(q)!==-1;
    });
}

function nominaRenderVales(){
    var rows=nominaValesUI.filtered||[];
    if(nominaValesUI.loading){ $('#nominaValesListado').html('<div class="nomina-state nomina-state-compact"><i class="fas fa-spinner fa-spin"></i><strong>Cargando vales</strong></div>'); return; }
    if(!rows.length){ $('#nominaValesListado').html('<div class="nomina-state nomina-state-compact"><i class="fas fa-ticket-alt"></i><strong>Sin vales</strong><span>No hay registros disponibles.</span></div>'); $('#nominaValesInfo').text('0 registros'); $('#nominaValesPaginacion').empty(); return; }
    var pages=Math.max(1,Math.ceil(rows.length/nominaValesUI.pageSize)); if(nominaValesUI.page>pages)nominaValesUI.page=pages;
    var start=(nominaValesUI.page-1)*nominaValesUI.pageSize,pageRows=rows.slice(start,start+nominaValesUI.pageSize);
    var html='<div class="nomina-vales-grid">';
    pageRows.forEach(function(r,i){
        var idx=start+i;
        html+='<article class="nomina-vale-card"><span class="nomina-vale-icon"><i class="fas fa-ticket-alt"></i></span><div class="nomina-vale-copy"><strong>'+nominaEsc(nominaVal(r.empleado,'Sin empleado'))+'</strong><span>'+nominaEsc(nominaVal(r.nota,'Sin notas'))+'</span></div><div class="nomina-vale-amount">'+nominaMoney(r.monto)+'</div><button type="button" class="btn btn-danger btn-sm anular_vale ocultar" data-index="'+idx+'"><i class="fas fa-ban mr-1"></i>Anular</button></article>';
    });
    html+='</div>';
    $('#nominaValesListado').html(html);
    $('#nominaValesInfo').text('Mostrando '+(start+1)+' a '+Math.min(start+pageRows.length,rows.length)+' de '+rows.length+' registros');
    nominaPaginacion('#nominaValesPaginacion',pages,nominaValesUI.page);
    if(typeof getPermisosTipoUsuarioAccesosTable==='function'&&typeof getPrivilegioTipoUsuario==='function') getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
}

function nominaUIAnularVale(data){
    if(!data||!data.vale_id){ showNotify('error','Error','No se pudo obtener el vale seleccionado'); return; }
    if(typeof validarAdminSistema!=='function'){ showNotify('error','Validación no disponible','No está cargado el JS de autenticación administrativa.'); return; }
    var valeId=data.vale_id, empleado=data.empleado||'Empleado no especificado';
    validarAdminSistema(function(permitido){
        if(permitido===true) anularVale(valeId,empleado);
    },{
        mensaje:'Para anular este vale debe validar un administrador.',
        modulo:'Nómina',
        accion:'Anular vale de nómina',
        referencia_id:valeId,
        referencia_texto:empleado,
        motivo:'Validación requerida para anular vale de nómina'
    });
}

function anularVale(vale_id, empleado) {
    if (!vale_id) {
        showNotify('error', 'Error', 'No se recibió el vale a anular');
        return;
    }

    empleado = empleado || 'Empleado no especificado';

    swal({
        title: "¿Estás seguro?",
        content: {
            element: "span",
            attributes: {
                innerHTML: "¿Desea anular este vale de <strong>" + empleado + "</strong>?"
            }
        },
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancelar",
                visible: true
            },
            confirm: {
                text: "¡Sí, anular el vale!"
            }
        },
        dangerMode: true,
        closeOnEsc: false,
        closeOnClickOutside: false
    }).then((willConfirm) => {
        if (!willConfirm) {
            return;
        }

        var url = '<?php echo SERVERURL;?>core/anularVale.php';

        $.ajax({
            type: "POST",
            url: url,
            data: {
                vale_id: vale_id,
                admin_token: AUTH_ADMIN_SISTEMA_TOKEN || '',
                auditoria_admin_id: AUTH_ADMIN_SISTEMA_AUDITORIA_ID || 0
            },
            dataType: "json",
            cache: false
        })
        .done(function (res) {
            if (res.status === "success") {
                showNotify('success', res.title || 'Éxito', res.message || 'El vale ha sido anulado correctamente');

                if (typeof listar_vales === 'function') {
                    listar_vales();
                }

                if (res.run) {
                    try {
                        eval(res.run);
                    } catch (e) {}
                }
            } else if (res.status === "unauthorized") {
                showNotify('error', res.title || 'Sesión expirada', res.message || 'Debes iniciar sesión nuevamente.');

                if (res.redirect) {
                    setTimeout(function () {
                        window.location.href = res.redirect;
                    }, 1200);
                }
            } else {
                showNotify('error', res.title || 'Error', res.message || 'Lo sentimos, no se puede anular el vale');
            }
        })
        .fail(function () {
            showNotify('error', 'Error', 'Error de conexión al anular el vale');
        });
    });
}

/* ============================
   Texto + Voz (contadores / speech)
   ============================ */
function inicializarContadores(limites) {
    Object.keys(limites).forEach(function(campo) {
        $(document).on('input', '#' + campo, function() {
            actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
        });
        if ($('#' + campo).length) {
            actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
        }
    });
}

function actualizarCaracteres(campo, contadorId, max_chars) {
    var $campo = $('#' + campo);
    if ($campo.length === 0) return;

    var texto = $campo.val() || '';
    var longitudTexto = texto.length;

    if (longitudTexto > max_chars) {
        $campo.val(texto.substring(0, max_chars));
        longitudTexto = max_chars;
    }

    $('#' + contadorId).text(longitudTexto + '/' + max_chars);
}

function inicializarSpeechRecognition(limites) {
    Object.keys(limites).forEach(function(campo) {
        $('#search_' + campo + '_stop').hide();

        var recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        recognition.continuous = true;
        recognition.lang = "es-ES";
        recognition.interimResults = false;

        $(document).on('click', '#search_' + campo + '_start', function(event) {
            $(this).hide();
            $('#search_' + campo + '_stop').show();
            recognition.start();
            event.preventDefault();
        });

        $(document).on('click', '#search_' + campo + '_stop', function(event) {
            recognition.stop();
            $(this).hide();
            $('#search_' + campo + '_start').show();
            event.preventDefault();
        });

        recognition.onresult = function(event) {
            var finalResult = '';
            var $campo = $('#' + campo);
            var valorAnterior = $campo.val() || '';

            for (var i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalResult = event.results[i][0].transcript;
                    var nuevoTexto = (valorAnterior + ' ' + finalResult).trim();

                    if (nuevoTexto.length > limites[campo]) {
                        nuevoTexto = nuevoTexto.substring(0, limites[campo]);
                    }

                    $campo.val(nuevoTexto);
                    actualizarCaracteres(campo, 'charNum_' + campo, limites[campo]);
                }
            }
        };

        recognition.onerror = function(event) {
            console.error('Error en reconocimiento de voz:', event.error);
            $('#search_' + campo + '_stop').hide();
            $('#search_' + campo + '_start').show();
        };
    });
}

$(() => {
    var limites = {
        'nomina_notas': 254,
        'nomina_detalles_notas': 254,
        'vale_notas': 254,
        'nominad_neto': 254
    };

    inicializarContadores(limites);
    inicializarSpeechRecognition(limites);

    $('.modal').on('shown.bs.modal', function() {
        inicializarContadores(limites);
        inicializarSpeechRecognition(limites);
    });
});

/* =========================================================
   EVENTOS UI NÓMINA 6.0
   ========================================================= */
$(() => {
    nominaPanel('#btnToggleFiltrosNomina','#nominaFiltrosContenido','izzy.nomina.filtros.visible');
    nominaPanel('#btnToggleKpisNomina','#nominaKpisContenido','izzy.nomina.kpis.visible');
    nominaPanel('#btnToggleFiltrosNominaDetalle','#nominaDetalleFiltrosContenido','izzy.nomina.detalle.filtros.visible');
    nominaPanel('#btnToggleKpisNominaDetalle','#nominaDetalleKpisContenido','izzy.nomina.detalle.kpis.visible');

    function initView(ui,storage,btnClass,$select){
        var saved='detalle'; try{saved=localStorage.getItem(storage)||'detalle';}catch(e){}
        ui.preferredView=saved==='miniatura'?'miniatura':'detalle';
        ui.view=nominaEsMovil()?'miniatura':ui.preferredView;
        nominaOpcionesPage($select,ui);
        $(btnClass).removeClass('active');
        $(btnClass+'[data-view="'+ui.view+'"]').addClass('active');
        $(btnClass+'[data-view="detalle"]').toggleClass('d-none',nominaEsMovil()).prop('disabled',nominaEsMovil());
    }

    initView(nominaUI,'izzy.nomina.vista','.nomina-view-btn',$('#nominaPageSize'));
    initView(nominaDetalleUI,'izzy.nomina.detalle.vista','.nomina-detalle-view-btn',$('#nominaDetallePageSize'));

    $('#buscarNomina').off('input.nominaUI').on('input.nominaUI',function(){nominaUI.search=this.value;nominaUI.page=1;nominaFiltrarMain();nominaRenderMain();});
    $('#limpiarBuscarNomina').off('click.nominaUI').on('click.nominaUI',function(){$('#buscarNomina').val('').focus();nominaUI.search='';nominaUI.page=1;nominaFiltrarMain();nominaRenderMain();});
    $('#nominaPageSize').off('change.nominaUI').on('change.nominaUI',function(){var n=parseInt(this.value,10)||10;nominaUI.pageSize=n;if(nominaUI.view==='miniatura')nominaUI.pageSizeMiniatura=n;else nominaUI.pageSizeDetalle=n;nominaUI.page=1;nominaRenderMain();});
    $('.nomina-view-btn').off('click.nominaUI').on('click.nominaUI',function(){var v=nominaEsMovil()?'miniatura':($(this).data('view')==='miniatura'?'miniatura':'detalle');nominaUI.view=v;if(!nominaEsMovil()){nominaUI.preferredView=v;try{localStorage.setItem('izzy.nomina.vista',v);}catch(e){}}nominaUI.page=1;nominaOpcionesPage($('#nominaPageSize'),nominaUI);$('.nomina-view-btn').removeClass('active');$('.nomina-view-btn[data-view="'+v+'"]').addClass('active');nominaRenderMain();});
    $('#nominaPaginacion').off('click.nominaUI','.nomina-page-btn').on('click.nominaUI','.nomina-page-btn',function(){if(this.disabled)return;nominaUI.page=parseInt($(this).data('page'),10)||1;nominaRenderMain();});

    $('#buscarNominaDetalle').off('input.nominaUI').on('input.nominaUI',function(){nominaDetalleUI.search=this.value;nominaDetalleUI.page=1;nominaFiltrarDetalle();nominaRenderDetalle();});
    $('#limpiarBuscarNominaDetalle').off('click.nominaUI').on('click.nominaUI',function(){$('#buscarNominaDetalle').val('').focus();nominaDetalleUI.search='';nominaDetalleUI.page=1;nominaFiltrarDetalle();nominaRenderDetalle();});
    $('#nominaDetallePageSize').off('change.nominaUI').on('change.nominaUI',function(){var n=parseInt(this.value,10)||10;nominaDetalleUI.pageSize=n;if(nominaDetalleUI.view==='miniatura')nominaDetalleUI.pageSizeMiniatura=n;else nominaDetalleUI.pageSizeDetalle=n;nominaDetalleUI.page=1;nominaRenderDetalle();});
    $('.nomina-detalle-view-btn').off('click.nominaUI').on('click.nominaUI',function(){var v=nominaEsMovil()?'miniatura':($(this).data('view')==='miniatura'?'miniatura':'detalle');nominaDetalleUI.view=v;if(!nominaEsMovil()){nominaDetalleUI.preferredView=v;try{localStorage.setItem('izzy.nomina.detalle.vista',v);}catch(e){}}nominaDetalleUI.page=1;nominaOpcionesPage($('#nominaDetallePageSize'),nominaDetalleUI);$('.nomina-detalle-view-btn').removeClass('active');$('.nomina-detalle-view-btn[data-view="'+v+'"]').addClass('active');nominaRenderDetalle();});
    $('#nominaDetallePaginacion').off('click.nominaUI','.nomina-page-btn').on('click.nominaUI','.nomina-page-btn',function(){if(this.disabled)return;nominaDetalleUI.page=parseInt($(this).data('page'),10)||1;nominaRenderDetalle();});

    $('#btnNominaActualizar').on('click.nominaUI',listar_nominas);
    $('#btnNominaRegistrar').on('click.nominaUI',modal_nominas);
    $('#btnNominaVales').on('click.nominaUI',modal_vales);
    $('#btnNominaDetalleActualizar').on('click.nominaUI',listar_nominas_detalles);
    $('#btnNominaDetalleAgregar').on('click.nominaUI',modalNominasDetalles);
    $('#btnNominaDetalleFiltrar').on('click.nominaUI',listar_nominas_detalles);
    $('#btnNominaDetalleLimpiar').on('click.nominaUI',function(){$('#detalle_nomina_empleado').val('').selectpicker('refresh');listar_nominas_detalles();});

    $('#nominaListado')
      .on('click.nominaUI','.nomina-ui-generar',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionGenerar(r);})
      .on('click.nominaUI','.nomina-ui-voucher',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionVoucher(r);})
      .on('click.nominaUI','.nomina-ui-libro',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionLibro(r);})
      .on('click.nominaUI','.nomina-ui-crear',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionCrearDetalle(r);})
      .on('click.nominaUI','.nomina-ui-editar',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionEditar(r);})
      .on('click.nominaUI','.nomina-ui-eliminar',function(){var r=nominaUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAccionEliminar(r);});

    $('#nominaDetalleListado')
      .on('click.nominaUI','.nomina-detalle-ui-editar',function(){var r=nominaDetalleUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaDetalleUIAccionEditar(r);})
      .on('click.nominaUI','.nomina-detalle-ui-eliminar',function(){var r=nominaDetalleUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaDetalleUIAccionEliminar(r);});

    $('#buscarVales').on('input.nominaUI',function(){nominaValesUI.search=this.value;nominaValesUI.page=1;nominaFiltrarVales();nominaRenderVales();});
    $('#limpiarBuscarVales').on('click.nominaUI',function(){$('#buscarVales').val('').focus();nominaValesUI.search='';nominaValesUI.page=1;nominaFiltrarVales();nominaRenderVales();});
    $('#nominaValesPaginacion').on('click.nominaUI','.nomina-page-btn',function(){if(this.disabled)return;nominaValesUI.page=parseInt($(this).data('page'),10)||1;nominaRenderVales();});
    $('#nominaValesListado').on('click.nominaUI','.anular_vale',function(){var r=nominaValesUI.filtered[parseInt($(this).data('index'),10)];if(r)nominaUIAnularVale(r);});

    $(window).off('resize.nominaUI orientationchange.nominaUI').on('resize.nominaUI orientationchange.nominaUI',function(){
        var mobile=nominaEsMovil();
        [
          {ui:nominaUI,btn:'.nomina-view-btn',sel:$('#nominaPageSize'),render:nominaRenderMain},
          {ui:nominaDetalleUI,btn:'.nomina-detalle-view-btn',sel:$('#nominaDetallePageSize'),render:nominaRenderDetalle}
        ].forEach(function(x){
            var target=mobile?'miniatura':x.ui.preferredView;
            $(x.btn+'[data-view="detalle"]').toggleClass('d-none',mobile).prop('disabled',mobile);
            if(x.ui.view!==target){x.ui.view=target;x.ui.page=1;nominaOpcionesPage(x.sel,x.ui);$(x.btn).removeClass('active');$(x.btn+'[data-view="'+target+'"]').addClass('active');x.render();}
        });
    });
});

</script>

<script>
/* Exportadores premium se enlazan sobre los datos filtrados actuales. */
(function(){
function escXml(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function colName(i){var n='';while(i>=0){n=String.fromCharCode((i%26)+65)+n;i=Math.floor(i/26)-1;}return n;}
function cell(ref,v,s,numeric){if(numeric){var x=nominaNum(v);return '<c r="'+ref+'" s="'+s+'" t="n"><v>'+x+'</v></c>';}return '<c r="'+ref+'" s="'+s+'" t="inlineStr"><is><t>'+escXml(v)+'</t></is></c>';}
function xlsx(rows,headers,map,file,sheet){
    if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','JSZip no está disponible.');return;}
    var sr=[];
    sr.push('<row r="1" ht="30" customHeight="1">'+cell('A1','IZZY • '+sheet.toUpperCase(),1,false)+'</row>');
    sr.push('<row r="2">'+cell('A2','Generado: '+new Date().toLocaleDateString('es-HN')+' • '+rows.length+' registro(s)',2,false)+'</row>');
    sr.push('<row r="4" ht="26" customHeight="1">'+headers.map(function(h,i){return cell(colName(i)+'4',h,3,false);}).join('')+'</row>');
    rows.forEach(function(r,i){var rr=5+i;sr.push('<row r="'+rr+'">'+map(r).map(function(v,c){return cell(colName(c)+rr,v,4,false);}).join('')+'</row>');});
    var last=colName(headers.length-1),lastRow=Math.max(4,4+rows.length);
    var sheetXml='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'+last+lastRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>'+sr.join('')+'</sheetData><autoFilter ref="A4:'+last+lastRow+'"/><mergeCells count="2"><mergeCell ref="A1:'+last+'1"/><mergeCell ref="A2:'+last+'2"/></mergeCells></worksheet>';
    var styles='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="5"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    var wb='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'+escXml(sheet)+'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var wr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var rr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var ct='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    var z=new JSZip();z.file('[Content_Types].xml',ct);z.folder('_rels').file('.rels',rr);z.folder('xl').file('workbook.xml',wb);z.folder('xl').file('styles.xml',styles);z.folder('xl').folder('_rels').file('workbook.xml.rels',wr);z.folder('xl').folder('worksheets').file('sheet1.xml',sheetXml);
    var opt={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
    var p=typeof z.generateAsync==='function'?z.generateAsync(opt):Promise.resolve(z.generate(opt));
    p.then(function(blob){var u=URL.createObjectURL(blob),a=document.createElement('a');a.href=u;a.download=file;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(u);},1000);}).catch(function(e){console.error(e);showNotify('error','Excel','No se pudo generar el archivo.');});
}
function pdf(rows,title,headers,map,file){
    if(typeof pdfMake==='undefined'||typeof abrirModalPdfPublico!=='function'){showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');return;}
    var body=[headers.map(function(h){return {text:h,bold:true,color:'#fff',fillColor:'#17324D',fontSize:7,alignment:'center'};})];
    rows.forEach(function(r,i){var fill=i%2?'#F7F9FC':'#FFFFFF';body.push(map(r).map(function(v){return {text:String(v==null?'':v),fontSize:7,color:'#253858',fillColor:fill};}));});
    var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,30,28,34],header:function(){return {margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(p,pc){return {margin:[28,8,28,0],columns:[{text:'IZZY • Nómina',fontSize:7,color:'#7A869A'},{text:'Página '+p+' de '+pc,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[{table:{widths:[110,'*',120],body:[[{text:'IZZY',bold:true,fontSize:18,color:'#17324D',fillColor:'#FFFFFF',margin:[10,9,10,9]},{stack:[{text:title.toUpperCase(),bold:true,fontSize:16,color:'#FFFFFF'},{text:'Reporte ejecutivo de nómina',fontSize:8,color:'#D8E5F0'}],fillColor:'#17324D',margin:[8,10,8,10]},{stack:[{text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},{text:rows.length+' registro(s)',fontSize:7,color:'#D8E5F0',alignment:'right'}],fillColor:'#17324D',margin:[8,10,10,10]}]]},layout:'noBorders',margin:[0,0,0,12]},{table:{headerRows:1,widths:Array(headers.length).fill('*'),body:body},layout:'lightHorizontalLines'}]};
    pdfMake.createPdf(doc).getDataUrl(function(url){abrirModalPdfPublico(url,title,file);});
}
$('#btnNominaExcel').off('click.nominaExport').on('click.nominaExport',function(){xlsx(nominaUI.filtered||[],['Código','Detalle','Empresa','Inicio','Fin','Importe','Notas','Estado'],function(r){return [r.nomina_id,r.detalle,r.empresa,r.fecha_inicio,r.fecha_fin,nominaMoney(r.importe),r.notas,Number(r.estado)===1?'Generada':'Sin Generar'];},'Nomina_Empleados.xlsx','Nómina');});
$('#btnNominaPdf').off('click.nominaExport').on('click.nominaExport',function(){pdf(nominaUI.filtered||[],'Reporte de Nómina',['Código','Detalle','Empresa','Inicio','Fin','Importe','Estado'],function(r){return [r.nomina_id,r.detalle,r.empresa,r.fecha_inicio,r.fecha_fin,nominaMoney(r.importe),Number(r.estado)===1?'Generada':'Sin Generar'];},'Nomina_Empleados.pdf');});
$('#btnNominaDetalleExcel').off('click.nominaExport').on('click.nominaExport',function(){xlsx(nominaDetalleUI.filtered||[],['Nómina','Contrato','Empresa','Empleado','Ingresos','Egresos','Neto','Notas','Estado'],function(r){return [r.nomina_id,r.contrato,r.empresa,r.empleado,nominaMoney(r.neto_ingresos),nominaMoney(r.neto_egresos),nominaMoney(r.neto),r.notas,Number(r.estado)===1?'Generada':'Sin Generar'];},'Nomina_Detalle_Empleados.xlsx','Detalle Nómina');});
$('#btnNominaDetallePdf').off('click.nominaExport').on('click.nominaExport',function(){pdf(nominaDetalleUI.filtered||[],'Detalle de Nómina',['Empleado','Contrato','Empresa','Ingresos','Egresos','Neto','Estado'],function(r){return [r.empleado,r.contrato,r.empresa,nominaMoney(r.neto_ingresos),nominaMoney(r.neto_egresos),nominaMoney(r.neto),Number(r.estado)===1?'Generada':'Sin Generar'];},'Nomina_Detalle_Empleados.pdf');});
})();
</script>
