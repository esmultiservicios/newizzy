<script>
// reporteCotizacion.php
// IZZY | Reporte de Cotizaciones - DIV / KPI / Excel / PDF

$(() => {
    getReporteCotizacion();

    $('#form_main_cotizaciones #tipo_cotizacion_reporte').val(1);
    try { $('#form_main_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh'); } catch (e) {}

    $('#form_main_cotizaciones').off('submit.reporteCotizaciones').on('submit.reporteCotizaciones', function(e) {
        e.preventDefault();
        listar_reporte_cotizaciones();
    });

    $('#form_main_cotizaciones').off('reset.reporteCotizaciones').on('reset.reporteCotizaciones', function() {
        var $form = $(this);
        setTimeout(function() {
            $('#form_main_cotizaciones #tipo_cotizacion_reporte').val(1);
            try { $form.find('.selectpicker').selectpicker('refresh'); } catch (e) {}
            listar_reporte_cotizaciones();
        }, 100);
    });

    $('#rcBtnActualizar').off('click.rc').on('click.rc', listar_reporte_cotizaciones);
    $('#rcPageSize').off('change.rc').on('change.rc', function() {
        RC.pageSize = parseInt(this.value, 10) || 10;
        RC.page = 1;
        renderReporteCotizaciones();
    });
    $('#rcSearch').off('input.rc').on('input.rc', function() {
        RC.search = this.value || '';
        RC.page = 1;
        renderReporteCotizaciones();
    });
    $('#rcSearchClear').off('click.rc').on('click.rc', function() {
        $('#rcSearch').val('').focus();
        RC.search = '';
        RC.page = 1;
        renderReporteCotizaciones();
    });
    $('[data-rc-view]').off('click.rc').on('click.rc', function() {
        $('[data-rc-view]').removeClass('active');
        $(this).addClass('active');
        RC.view = $(this).attr('data-rc-view') === 'miniatura' ? 'miniatura' : 'detalle';
        RC.page = 1;
        renderReporteCotizaciones();
    });
    $('#rcBtnExcel').off('click.rc').on('click.rc', exportarReporteCotizacionesExcel);
    $('#rcBtnPdf').off('click.rc').on('click.rc', exportarReporteCotizacionesPdf);

    $(document).off('click.rcToggle', '.rv-toggle-section').on('click.rcToggle', '.rv-toggle-section', function() {
        var $button = $(this), $target = $($button.attr('data-target'));
        if (!$target.length) return;
        var ocultar = $target.is(':visible');
        $target.stop(true, true).slideToggle(160);
        $button.find('span').text(ocultar ? 'Mostrar' : 'Ocultar');
        $button.find('i').toggleClass('fa-chevron-up', !ocultar).toggleClass('fa-chevron-down', ocultar);
    });

    $(document).off('click.rcActions', '.rc-action').on('click.rcActions', '.rc-action', function(e) {
        e.preventDefault();
        var index = parseInt($(this).attr('data-index'), 10);
        var row = RC.filtered[index];
        if (!row) {
            showNotify('error', 'Error', 'No se pudo obtener la cotización seleccionada.');
            return false;
        }
        ejecutarAccionReporteCotizacion($(this).attr('data-action'), row);
        return false;
    });

    listar_reporte_cotizaciones();
});

var RC = { rows: [], filtered: [], page: 1, pageSize: 10, view: 'detalle', search: '' };

function normalizarNumeroReporteCotizaciones(valor) {
    if (valor === null || valor === undefined || valor === '') return 0;
    valor = String(valor).replace(/<[^>]*>/g, '').replace(/L\./g, '').replace(/L/g, '').replace(/,/g, '').trim();
    var numero = parseFloat(valor);
    return isNaN(numero) ? 0 : numero;
}
function rcMoney(valor) {
    return 'L. ' + normalizarNumeroReporteCotizaciones(valor).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function rcEsc(valor) { return $('<div>').text(valor === null || valor === undefined ? '' : String(valor)).html(); }
function rcRows(response) {
    if (typeof response === 'string') { try { response = JSON.parse(response); } catch (e) { return []; } }
    if (Array.isArray(response)) return response;
    if (response && Array.isArray(response.data)) return response.data;
    if (response && Array.isArray(response.aaData)) return response.aaData;
    return [];
}
function rcSearchText(row) { try { return JSON.stringify(row || {}).toLowerCase(); } catch (e) { return ''; } }
function rcFilter() {
    var q = String(RC.search || '').trim().toLowerCase();
    RC.filtered = !q ? RC.rows.slice() : RC.rows.filter(function(row) { return rcSearchText(row).indexOf(q) !== -1; });
    var pages = Math.max(1, Math.ceil(RC.filtered.length / RC.pageSize));
    RC.page = Math.max(1, Math.min(RC.page, pages));
}
function rcTotals(rows) {
    return rows.reduce(function(acc, row) {
        acc.subtotal += normalizarNumeroReporteCotizaciones(row.subtotal);
        acc.isv += normalizarNumeroReporteCotizaciones(row.isv);
        acc.descuento += normalizarNumeroReporteCotizaciones(row.descuento);
        acc.total += normalizarNumeroReporteCotizaciones(row.total);
        return acc;
    }, { subtotal: 0, isv: 0, descuento: 0, total: 0 });
}
function rcTypeBadge(row) {
    var texto = row.tipo_documento || '';
    var credito = texto === 'Crédito';
    return '<span class="badge badge-pill ' + (credito ? 'badge-warning' : 'badge-success') + '"><i class="fas ' + (credito ? 'fa-clock' : 'fa-check-circle') + ' mr-1"></i>' + rcEsc(texto) + '</span>';
}
function rcEmpty() { return '<div class="rv-empty"><i class="fas fa-inbox"></i><strong>Sin registros</strong><span>No hay información que coincida con los criterios actuales.</span></div>'; }
function rcMiniField(label, value) { return '<div class="rv-mini-field"><span>' + rcEsc(label) + '</span><strong>' + value + '</strong></div>'; }

function rcDropdown(row, index) {
    return '<div class="dropdown acciones-dropdown">' +
        '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog"></i><span>Acciones</span></button>' +
        '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
        '<button type="button" class="dropdown-item accion-item table_reportes rc-action" data-action="print" data-index="' + index + '"><span class="accion-icon accion-icon-success"><i class="fas fa-file-download"></i></span><span class="accion-label">Cotización</span></button>' +
        '<button type="button" class="dropdown-item accion-item table_reportes rc-action" data-action="email" data-index="' + index + '"><span class="accion-icon accion-icon-secondary"><i class="fas fa-paper-plane"></i></span><span class="accion-label">Enviar</span></button>' +
        '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar rc-action" data-action="anular" data-index="' + index + '"><span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span><span class="accion-label">Anular</span></button>' +
        '</div></div>';
}

function ejecutarAccionReporteCotizacion(action, data) {
    if (!data || !data.cotizacion_id) {
        showNotify('error', 'Error', 'No se pudo obtener la cotización seleccionada.');
        return;
    }
    if (action === 'print') { printQuote(data.cotizacion_id); return; }
    if (action === 'email') {
        swal({ title: 'Enviar cotización', text: '¿Desea enviar esta cotización por correo electrónico?', icon: 'warning', buttons: { cancel: { text: 'Cancelar', visible: true, closeModal: true }, confirm: { text: 'Sí, enviar', closeModal: true } }, dangerMode: false, closeOnEsc: false, closeOnClickOutside: false })
        .then(function(confirmado) { if (confirmado) mailQuote(data.cotizacion_id); });
        return;
    }
    if (action === 'anular') {
        if (typeof validarAdminSistema !== 'function') {
            showNotify('error', 'Validación no disponible', 'No está cargado el JS de autenticación administrativa.');
            return;
        }
        var numeroCotizacion = data.number || data.numero || data.cotizacion || data.numero_cotizacion || data.cotizacion_id;
        validarAdminSistema(function(permitido) {
            if (permitido === true) anularCotizacion(data.cotizacion_id);
        }, { mensaje: 'Para anular esta cotización debe validar un administrador.', modulo: 'Cotizaciones', accion: 'Anular cotización', referencia_id: data.cotizacion_id, referencia_texto: numeroCotizacion, motivo: 'Validación requerida para anular cotización' });
    }
}

function renderReporteCotizaciones() {
    rcFilter();
    var start = (RC.page - 1) * RC.pageSize;
    var pageRows = RC.filtered.slice(start, start + RC.pageSize);
    var html = '';
    var grid = '135px 105px 110px minmax(220px,1.7fr) minmax(160px,1.2fr) 120px 105px 115px 135px';

    if (!pageRows.length) html = rcEmpty();
    else if (RC.view === 'miniatura') {
        html = pageRows.map(function(row, idx) {
            var index = start + idx;
            return '<div class="rv-mini-card"><div class="rv-mini-head"><div><div class="rv-mini-title">' + rcEsc(row.cliente || 'Sin cliente') + '</div><span class="rv-mini-sub">' + rcEsc(row.numero || '') + ' • ' + rcEsc(row.fecha || '') + '</span></div>' + rcDropdown(row, index) + '</div>' +
                '<div class="rv-mini-body">' + rcMiniField('Tipo', rcTypeBadge(row)) + rcMiniField('Subtotal', rcMoney(row.subtotal)) + rcMiniField('ISV', rcMoney(row.isv)) + rcMiniField('Descuento', rcMoney(row.descuento)) + rcMiniField('Total', '<span class="rc-mini-total">' + rcMoney(row.total) + '</span>') + '</div></div>';
        }).join('');
    } else {
        var headers = ['Acciones','Fecha','Tipo','Cliente','Cotización','Subtotal','ISV','Descuento','Total'];
        html = '<div class="rv-detail-header" style="grid-template-columns:' + grid + '">' + headers.map(function(h){ return '<div class="rv-cell">' + h + '</div>'; }).join('') + '</div>';
        html += pageRows.map(function(row, idx) {
            var index = start + idx;
            return '<div class="rv-detail-row" style="grid-template-columns:' + grid + '">' +
                '<div class="rv-cell rv-actions-cell" data-label="Acciones">' + rcDropdown(row, index) + '</div>' +
                '<div class="rv-cell" data-label="Fecha">' + rcEsc(row.fecha || '') + '</div>' +
                '<div class="rv-cell" data-label="Tipo">' + rcTypeBadge(row) + '</div>' +
                '<div class="rv-cell" data-label="Cliente"><strong>' + rcEsc(row.cliente || '') + '</strong></div>' +
                '<div class="rv-cell" data-label="Cotización">' + rcEsc(row.numero || '') + '</div>' +
                '<div class="rv-cell rv-money" data-label="Subtotal">' + rcMoney(row.subtotal) + '</div>' +
                '<div class="rv-cell rv-money" data-label="ISV">' + rcMoney(row.isv) + '</div>' +
                '<div class="rv-cell rv-money" data-label="Descuento">' + rcMoney(row.descuento) + '</div>' +
                '<div class="rv-cell rv-money rc-total-cell" data-label="Total"><strong>' + rcMoney(row.total) + '</strong></div>' +
            '</div>';
        }).join('');
    }

    $('#rcListado').toggleClass('rv-mini', RC.view === 'miniatura').html(html);

    var totals = rcTotals(RC.filtered);
    var promedio = RC.filtered.length ? totals.total / RC.filtered.length : 0;
    $('#rcKpiRegistros').text(RC.filtered.length);
    $('#rcKpiSubtotal').text(rcMoney(totals.subtotal));
    $('#rcKpiIsv').text(rcMoney(totals.isv));
    $('#rcKpiDescuento').text(rcMoney(totals.descuento));
    $('#rcKpiTotal').text(rcMoney(totals.total));
    $('#rcKpiPromedio').text(rcMoney(promedio));
    renderTotalesReporteCotizaciones(totals);

    $('#rcInfo').text(RC.filtered.length ? 'Mostrando ' + (start + 1) + ' a ' + Math.min(start + pageRows.length, RC.filtered.length) + ' de ' + RC.filtered.length + ' registros' : '0 registros');
    renderPaginacionReporteCotizaciones();
    try { getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario()); } catch (e) {}
    if (typeof cerrarDropdownAcciones === 'function') cerrarDropdownAcciones();
}

function renderTotalesReporteCotizaciones(totals) {
    if (RC.view === 'miniatura') {
        $('#rcTotales').html('<div class="rv-total-mini"><div class="rv-total-chip"><span>Subtotal</span><strong>' + rcMoney(totals.subtotal) + '</strong></div><div class="rv-total-chip"><span>ISV</span><strong>' + rcMoney(totals.isv) + '</strong></div><div class="rv-total-chip"><span>Descuento</span><strong>' + rcMoney(totals.descuento) + '</strong></div><div class="rv-total-chip"><span>Total</span><strong>' + rcMoney(totals.total) + '</strong></div></div>');
        return;
    }
    var grid = '135px 105px 110px minmax(220px,1.7fr) minmax(160px,1.2fr) 120px 105px 115px 135px';
    $('#rcTotales').html('<div class="rv-total-detail rc-total-main-grid" style="grid-template-columns:' + grid + '"><div class="rc-total-main-label">TOTALES GENERALES</div><div class="rv-cell rv-money rc-total-value">' + rcMoney(totals.subtotal) + '</div><div class="rv-cell rv-money rc-total-value">' + rcMoney(totals.isv) + '</div><div class="rv-cell rv-money rc-total-value">' + rcMoney(totals.descuento) + '</div><div class="rv-cell rv-money rc-total-value rc-total-highlight">' + rcMoney(totals.total) + '</div></div>');
}

function renderPaginacionReporteCotizaciones() {
    var pages = Math.max(1, Math.ceil(RC.filtered.length / RC.pageSize)), html = '';
    function b(label, page, disabled, active) { html += '<button type="button" data-page="' + page + '" ' + (disabled ? 'disabled ' : '') + 'class="' + (active ? 'active' : '') + '">' + label + '</button>'; }
    b('<i class="fas fa-angle-double-left"></i> Inicio', 1, RC.page === 1, false);
    b('<i class="fas fa-angle-left"></i> Anterior', RC.page - 1, RC.page === 1, false);
    var from = Math.max(1, RC.page - 2), to = Math.min(pages, from + 4); from = Math.max(1, to - 4);
    for (var p = from; p <= to; p++) b(String(p), p, false, p === RC.page);
    b('Siguiente <i class="fas fa-angle-right"></i>', RC.page + 1, RC.page === pages, false);
    b('Final <i class="fas fa-angle-double-right"></i>', pages, RC.page === pages, false);
    $('#rcPagination').html(html).off('click.rc', 'button[data-page]').on('click.rc', 'button[data-page]', function() {
        if (this.disabled || $(this).hasClass('active')) return;
        RC.page = parseInt($(this).attr('data-page'), 10) || 1;
        renderReporteCotizaciones();
    });
}

var listar_reporte_cotizaciones = function() {
    var tipo = $('#form_main_cotizaciones #tipo_cotizacion_reporte').val();
    if (tipo === null || tipo === '') tipo = 1;
    var fechai = $('#form_main_cotizaciones #fechai').val();
    var fechaf = $('#form_main_cotizaciones #fechaf').val();
    $('#rcListado').removeClass('rv-mini').html('<div class="rv-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando reporte...</div>');
    $.ajax({ method: 'POST', url: '<?php echo SERVERURL;?>core/llenarDataTableReporteCotizaciones.php', data: { tipo_cotizacion_reporte: tipo, fechai: fechai, fechaf: fechaf }, dataType: 'json' })
    .done(function(response) { RC.rows = rcRows(response); RC.page = 1; renderReporteCotizaciones(); })
    .fail(function(xhr) { RC.rows = []; RC.filtered = []; renderReporteCotizaciones(); showNotify('error', 'Error', xhr.responseText || 'No fue posible cargar el reporte de cotizaciones.'); });
};

function anularCotizacion(cotizacion_id) {
    swal({ title: '¿Está seguro?', text: '¿Desea anular la cotización: # ' + getNumeroCotizacion(cotizacion_id) + '?', icon: 'warning', buttons: { cancel: { text: 'Cancelar', visible: true }, confirm: { text: '¡Sí, anular la cotización!', closeModal: false } }, dangerMode: true, closeOnEsc: false, closeOnClickOutside: false })
    .then(function(willConfirm) { if (willConfirm === true) anular(cotizacion_id); });
}
function anular(cotizacion_id) {
    $.ajax({ type: 'POST', url: '<?php echo SERVERURL; ?>core/anularCotizacion.php', async: true, data: { cotizacion_id: cotizacion_id } })
    .done(function(data) {
        swal.close();
        if (data == 1) { showNotify('success', 'Success', 'La cotización ha sido anulada con éxito'); listar_reporte_cotizaciones(); }
        else showNotify('error', 'Error', 'La cotización no se pudo anular');
    })
    .fail(function(xhr) { swal.close(); showNotify('error', 'Error', xhr.responseText || 'Hubo un problema al anular la cotización'); });
}

function rcXml(v) { return String(v === null || v === undefined ? '' : v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&apos;'); }
function rcCol(i) { var n=i+1,s=''; while(n>0){var m=(n-1)%26;s=String.fromCharCode(65+m)+s;n=Math.floor((n-1)/26);} return s; }
function rcCell(ref,v,style,numeric){ if(numeric){var x=Number(v);if(!isNaN(x)) return '<c r="'+ref+'" s="'+style+'"><v>'+x+'</v></c>'; } return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+rcXml(v)+'</t></is></c>'; }
function rcDownload(blob,name){var a=document.createElement('a'),u=URL.createObjectURL(blob);a.href=u;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(u);},1000);}

function exportarReporteCotizacionesExcel(){
    var rows=RC.filtered.map(function(r){return[r.fecha||'',r.tipo_documento||'',r.cliente||'',r.numero||'',normalizarNumeroReporteCotizaciones(r.subtotal),normalizarNumeroReporteCotizaciones(r.isv),normalizarNumeroReporteCotizaciones(r.descuento),normalizarNumeroReporteCotizaciones(r.total)];});
    if(!rows.length){showNotify('warning','Sin datos','No hay registros para exportar.');return;}
    if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','No se encontró JSZip.');return;}
    var headers=['Fecha','Tipo','Cliente','Cotización','Subtotal','ISV','Descuento','Total'], totalCols=[4,5,6,7], sums={};
    totalCols.forEach(function(c){sums[c]=rows.reduce(function(a,r){return a+normalizarNumeroReporteCotizaciones(r[c]);},0);});
    var first=8,totalRow=first+rows.length,last=rcCol(headers.length-1),sr=[];
    sr.push('<row r="1" ht="30" customHeight="1">'+rcCell('A1','IZZY • REPORTE DE COTIZACIONES',1,false)+'</row>');
    sr.push('<row r="2" ht="20" customHeight="1">'+rcCell('A2','Cotizaciones, impuestos, descuentos y total • Generado: '+new Date().toLocaleDateString('es-HN'),2,false)+'</row>');
    sr.push('<row r="3" ht="18" customHeight="1">'+rcCell('A3','REGISTROS',6,false)+rcCell('E3','TOTAL GENERAL',6,false)+'</row>');
    sr.push('<row r="4" ht="26" customHeight="1">'+rcCell('A4',rows.length,7,true)+rcCell('E4',sums[7],10,true)+'</row><row r="5"></row>');
    sr.push('<row r="6">'+rcCell('A6','Detalle de registros filtrados',8,false)+'</row>');
    sr.push('<row r="7" ht="26" customHeight="1">'+headers.map(function(h,i){return rcCell(rcCol(i)+'7',h,3,false);}).join('')+'</row>');
    rows.forEach(function(r,ri){var rr=first+ri;sr.push('<row r="'+rr+'" ht="22" customHeight="1">'+r.map(function(v,ci){var num=totalCols.indexOf(ci)!==-1;return rcCell(rcCol(ci)+rr,v,num?5:4,num);}).join('')+'</row>');});
    var tc=rcCell('A'+totalRow,'TOTALES GENERALES',9,false);totalCols.forEach(function(c){tc+=rcCell(rcCol(c)+totalRow,sums[c],10,true);});sr.push('<row r="'+totalRow+'" ht="30" customHeight="1">'+tc+'</row>');
    var cols=headers.map(function(h,i){var w=i===2?30:(i===3?22:Math.min(34,Math.max(13,String(h).length+7)));return'<col min="'+(i+1)+'" max="'+(i+1)+'" width="'+w+'" customWidth="1"/>';}).join('');
    var sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'+last+totalRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols>'+cols+'</cols><sheetData>'+sr.join('')+'</sheetData><autoFilter ref="A7:'+last+(7+rows.length)+'"/><mergeCells count="3"><mergeCell ref="A1:'+last+'1"/><mergeCell ref="A2:'+last+'2"/><mergeCell ref="A'+totalRow+':D'+totalRow+'"/></mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/></worksheet>';
    var styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="L. #,##0.00"/></numFmts><fonts count="8"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0"/><xf numFmtId="0" fontId="2" fillId="4" borderId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="4" fillId="0" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="0"/><xf numFmtId="0" fontId="6" fillId="4" borderId="0"/><xf numFmtId="0" fontId="7" fillId="0" borderId="0"/><xf numFmtId="0" fontId="7" fillId="4" borderId="1"/><xf numFmtId="164" fontId="7" fillId="4" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    var workbook='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var rels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var root='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var types='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    var zip=new JSZip();zip.file('[Content_Types].xml',types);zip.folder('_rels').file('.rels',root);zip.folder('xl').file('workbook.xml',workbook);zip.folder('xl').file('styles.xml',styles);zip.folder('xl').folder('_rels').file('workbook.xml.rels',rels);zip.folder('xl').folder('worksheets').file('sheet1.xml',sheet);
    var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};var promise=typeof zip.generateAsync==='function'?zip.generateAsync(opts):Promise.resolve(zip.generate(opts));promise.then(function(blob){rcDownload(blob,'Reporte_Cotizaciones_'+new Date().toISOString().slice(0,10)+'.xlsx');}).catch(function(e){console.error(e);showNotify('error','Excel','No se pudo generar el archivo Excel.');});
}

function rcGetLogo(cb){
    if(typeof imagen!=='undefined'&&imagen&&String(imagen).indexOf('data:image/')===0){cb(imagen);return;}
    $.ajax({type:'GET',url:'<?php echo SERVERURL;?>core/get_image.php',dataType:'text',timeout:10000}).done(function(src){src=String(src||'').trim();if(!src){cb(null);return;}if(src.indexOf('data:image/')===0){cb(src);return;}var im=new Image();im.crossOrigin='Anonymous';im.onload=function(){try{var c=document.createElement('canvas');c.width=im.naturalWidth;c.height=im.naturalHeight;c.getContext('2d').drawImage(im,0,0);cb(c.toDataURL('image/png'));}catch(e){cb(null);}};im.onerror=function(){cb(null);};im.src=src;}).fail(function(){cb(null);});
}
function rcPdfLogoPlate(logo){
    var content=logo?{image:logo,fit:[62,36],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}:{text:'IZZY',fontSize:16,bold:true,color:'#17324D',alignment:'center',margin:[7,8,7,8],fillColor:'#FFFFFF'};
    return {table:{widths:['*'],body:[[content]]},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return.5;},vLineWidth:function(){return.5;}}};
}
function exportarReporteCotizacionesPdf(){
    if(!RC.filtered.length){showNotify('warning','Sin datos','No hay registros para exportar.');return;}
    if(typeof pdfMake==='undefined'){showNotify('error','PDF no disponible','No se encontró pdfMake.');return;}
    var totals=rcTotals(RC.filtered),headers=['Fecha','Tipo','Cliente','Cotización','Subtotal','ISV','Descuento','Total'],num=[4,5,6,7];
    var rows=RC.filtered.map(function(r){return[r.fecha||'',r.tipo_documento||'',r.cliente||'',r.numero||'',normalizarNumeroReporteCotizaciones(r.subtotal),normalizarNumeroReporteCotizaciones(r.isv),normalizarNumeroReporteCotizaciones(r.descuento),normalizarNumeroReporteCotizaciones(r.total)];});
    rcGetLogo(function(logo){
        var body=[headers.map(function(h){return{text:h,fillColor:'#17324D',color:'#fff',bold:true,fontSize:7,alignment:'center',margin:[2,3,2,3]};})];
        rows.forEach(function(r,i){body.push(r.map(function(v,c){var m=num.indexOf(c)!==-1;return{text:m?rcMoney(v):String(v||''),fillColor:i%2?'#F7F9FC':'#FFFFFF',alignment:m?'right':'left',fontSize:7,margin:[2,3,2,3]};}));});
        body.push([{text:'TOTALES GENERALES',colSpan:4,bold:true,fillColor:'#EAF4FC',color:'#17324D',fontSize:7,margin:[6,5,6,5],alignment:'left'},{},{},{},{text:rcMoney(totals.subtotal),bold:true,fillColor:'#EAF4FC',alignment:'right',margin:[3,5,3,5]},{text:rcMoney(totals.isv),bold:true,fillColor:'#EAF4FC',alignment:'right',margin:[3,5,3,5]},{text:rcMoney(totals.descuento),bold:true,fillColor:'#EAF4FC',alignment:'right',margin:[3,5,3,5]},{text:rcMoney(totals.total),bold:true,fillColor:'#E8F7EF',color:'#087F5B',alignment:'right',margin:[3,5,3,5]}]);
        var filters='Tipo: '+($('#form_main_cotizaciones #tipo_cotizacion_reporte option:selected').text()||'Todos')+' | Fechas: '+($('#form_main_cotizaciones #fechai').val()||'')+' a '+($('#form_main_cotizaciones #fechaf').val()||'');
        var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],content:[
            {table:{widths:[100,'*',135],body:[[
                {fillColor:'#17324D',border:[false,false,false,false],margin:[10,7,4,7],stack:[rcPdfLogoPlate(logo)]},
                {fillColor:'#17324D',border:[false,false,false,false],stack:[{text:'REPORTE DE COTIZACIONES',color:'#fff',bold:true,fontSize:15},{text:'Cotizaciones, impuestos, descuentos y total',color:'#D8E5F0',fontSize:7.5,margin:[0,2,0,0]}],margin:[0,10,0,10]},
                {fillColor:'#17324D',border:[false,false,false,false],stack:[{text:'REPORTE EJECUTIVO',color:'#72E2E5',bold:true,fontSize:6.5,alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),color:'#fff',bold:true,fontSize:9,alignment:'right',margin:[0,3,0,0]},{text:RC.filtered.length+' registro(s) filtrado(s)',color:'#D8E5F0',fontSize:6.5,alignment:'right',margin:[0,2,0,0]}],margin:[0,9,10,9]}
            ]]},layout:'noBorders',margin:[0,0,0,10]},
            {table:{widths:['*','*','*'],body:[[
                {fillColor:'#F7F9FC',stack:[{text:'REGISTROS',fontSize:6.5,bold:true,color:'#6B778C'},{text:String(RC.filtered.length),fontSize:12,bold:true,color:'#172B4D',margin:[0,2,0,0]}],margin:[8,7,8,7]},
                {fillColor:'#F7F9FC',stack:[{text:'TOTAL GENERAL',fontSize:6.5,bold:true,color:'#6B778C'},{text:rcMoney(totals.total),fontSize:12,bold:true,color:'#087F5B',margin:[0,2,0,0]}],margin:[8,7,8,7]},
                {fillColor:'#F7F9FC',stack:[{text:'FILTROS',fontSize:6.5,bold:true,color:'#6B778C'},{text:filters,fontSize:7,color:'#42526E',margin:[0,2,0,0]}],margin:[8,7,8,7]}
            ]]},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';}},margin:[0,0,0,12]},
            {table:{headerRows:1,widths:[62,62,'*',105,70,65,70,78],body:body},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return.55;},vLineWidth:function(){return.55;},paddingLeft:function(){return4;},paddingRight:function(){return4;},paddingTop:function(){return5;},paddingBottom:function(){return5;}}}
        ],footer:function(p,pc){return{margin:[28,8,28,0],columns:[{text:'IZZY • Reportes',fontSize:7,color:'#7A869A'},{text:'Página '+p+' de '+pc,fontSize:7,color:'#7A869A',alignment:'right'}]};},defaultStyle:{fontSize:7,color:'#253858'}};
        var pdf=pdfMake.createPdf(doc),name='Reporte_Cotizaciones_'+new Date().toISOString().slice(0,10)+'.pdf';
        if(typeof abrirModalPdfPublico==='function'&&typeof pdf.getDataUrl==='function')pdf.getDataUrl(function(url){abrirModalPdfPublico(url,'Reporte de Cotizaciones',name);});else pdf.download(name);
    });
}

function getReporteCotizacion() {
    $.ajax({ type: 'POST', url: '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php', async: true })
    .done(function(data) { $('#form_main_cotizaciones #tipo_cotizacion_reporte').html(data); try { $('#form_main_cotizaciones #tipo_cotizacion_reporte').selectpicker('refresh'); } catch (e) {} })
    .fail(function() { showNotify('error', 'Error', 'No se pudo cargar el tipo de cotización'); });
}
</script>
