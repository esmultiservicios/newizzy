<script>
var inventarioResumenRowsCache = [];

(function () {
  function inicializarInventarioTransferencia() {
    transferenciaInicializarEventosUI();
    inventario_transferencia();
    getTipoProductos();
    getAlmacen();

    $('#form_main_movimientos_transferencia').off('submit.inventarioTransferencia');
    $('#form_main_movimientos_transferencia').on('submit.inventarioTransferencia', function(e){
      e.preventDefault();
      inventario_transferencia();
    });

    $('#form_main_movimientos_transferencia').off('reset.inventarioTransferencia');
    $('#form_main_movimientos_transferencia').on('reset.inventarioTransferencia', function(){
      var form = this;

      setTimeout(function(){
        $(form).find('.selectpicker')
          .val('')
          .selectpicker('refresh');

        getProductosMovimientos(0);
        inventario_transferencia();
      }, 100);
    });

    $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').off('changed.bs.select.inventarioCategoria change.inventarioCategoria');
    $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').on('changed.bs.select.inventarioCategoria change.inventarioCategoria', function(){
      var categoria_id = $('#form_main_movimientos_transferencia #inventario_tipo_productos_id').val() || 0;

      getProductosMovimientos(categoria_id);

      setTimeout(function(){
        inventario_transferencia();
      }, 250);
    });

    $('#form_main_movimientos_transferencia #inventario_productos_id, #form_main_movimientos_transferencia #almacen').off('changed.bs.select.inventarioFiltro change.inventarioFiltro');
    $('#form_main_movimientos_transferencia #inventario_productos_id, #form_main_movimientos_transferencia #almacen').on('changed.bs.select.inventarioFiltro change.inventarioFiltro', function(){
      inventario_transferencia();
    });

    $('#btn_ver_resumen_inventario').off('click.inventarioResumen');
    $('#btn_ver_resumen_inventario').on('click.inventarioResumen', function(){
      mostrarVistaResumenInventario();
    });

    $('#btn_volver_inventario').off('click.inventarioResumen');
    $('#btn_volver_inventario').on('click.inventarioResumen', function(){
      mostrarVistaInventarioPrincipal();
    });

    $('#btn_actualizar_resumen_inventario').off('click.inventarioResumen');
    $('#btn_actualizar_resumen_inventario').on('click.inventarioResumen', function(){
      cargarResumenInventario();
    });

    $('#inventario_tipo_valorizacion').off('changed.bs.select.inventarioResumen change.inventarioResumen');
    $('#inventario_tipo_valorizacion').on('changed.bs.select.inventarioResumen change.inventarioResumen', function(){
      construirTablaResumenInventario(inventarioResumenRowsCache);
    });

    $('#btn_ver_historico_vendido').off('click.inventarioHistorico');
    $('#btn_ver_historico_vendido').on('click.inventarioHistorico', function(){
      $('#cardHistoricoVendidoInventario').show();
      cargarHistoricoVendidoInventario();

      setTimeout(function(){
        var card = document.getElementById('cardHistoricoVendidoInventario');
        if (card) {
          card.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      }, 200);
    });

    $("#modal_transferencia_producto").off('shown.bs.modal.inventarioTransferencia');
    $("#modal_transferencia_producto").on('shown.bs.modal.inventarioTransferencia', function(){
      $(this).find('#formTransferencia #cantidad_movimiento').focus();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarInventarioTransferencia);
  } else {
    inicializarInventarioTransferencia();
  }
})();

/* =========================================================
   UTILIDADES
   ========================================================= */

function toNumber(val){
  if (val == null) return 0;
  if (typeof val === 'number') return val;
  return parseFloat(String(val).replace(/[^\d.-]/g,'')) || 0;
}

function formatNumber(n){
  try{
    return Number(n).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }catch(e){
    var s = (Number(n) || 0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}

function formatMoney(n){
  return 'L. ' + formatNumber(n);
}

function inventarioValor(valor, textoDefault) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return textoDefault !== undefined ? textoDefault : 'No registrado';
  }

  return String(valor).trim();
}

function inventarioEscape(valor) {
  return inventarioValor(valor, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function inventarioNumeroBadge(valor, tipo) {
  var numero = toNumber(valor);
  var clase = 'inventario-number-badge';

  if (tipo === 'anterior') {
    clase += numero > 0 ? ' inventario-badge-anterior-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'entrada') {
    clase += numero > 0 ? ' inventario-badge-entrada-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'salida') {
    clase += numero > 0 ? ' inventario-badge-salida-ok' : ' inventario-badge-neutral';
  }

  if (tipo === 'saldo') {
    clase += numero > 0 ? ' inventario-badge-saldo-ok' : ' inventario-badge-saldo-empty';
  }

  return '<span class="' + clase + '">' + formatNumber(numero) + '</span>';
}

function inventarioDineroBadge(valor, tipo) {
  var numero = toNumber(valor);
  var clase = 'inventario-number-badge';

  if (tipo === 'valor') {
    clase += numero > 0 ? ' inventario-badge-entrada-ok' : ' inventario-badge-neutral';
  } else {
    clase += numero > 0 ? ' inventario-badge-saldo-ok' : ' inventario-badge-neutral';
  }

  return '<span class="' + clase + '">' + formatMoney(numero) + '</span>';
}

function inventarioActualizarResumen(rows) {
  rows = rows || [];

  var totalEntrada = 0;
  var totalSalida = 0;
  var totalSaldo = 0;

  rows.forEach(function(item) {
    totalEntrada += toNumber(item.entrada);
    totalSalida += toNumber(item.salida);
    totalSaldo += toNumber(item.saldo);
  });

  $('#inventario_total_registros').text(rows.length);
  $('#inventario_total_entrada').text(formatNumber(totalEntrada));
  $('#inventario_total_salida').text(formatNumber(totalSalida));
  $('#inventario_total_saldo').text(formatNumber(totalSaldo));
}

function inventarioActualizarResumenDataTable(api) {
  var rows = [];

  api.rows({ search: 'applied' }).every(function(){
    rows.push(this.data());
  });

  inventarioActualizarResumen(rows);
}

function obtenerFiltrosInventario() {
  var form = $("#form_main_movimientos_transferencia");

  return {
    categoria_id: form.find("#inventario_tipo_productos_id").val() || '',
    productos_id: form.find("#inventario_productos_id").val() || '',
    bodega: form.find("#almacen").val() || ''
  };
}

/* =========================================================
   HEADERS DATATABLE
   ========================================================= */

function construirHeaderDataTableInventarioTransferencia() {
  var $tabla = $("#dataTablaMovimientos");

  $tabla.find('thead').remove();

  $tabla.prepend(
    '<thead>' +
      '<tr>' +
        '<th>Acciones</th>' +
        '<th>Producto</th>' +
        '<th>Lote / Bodega</th>' +
        '<th>Último Movimiento</th>' +
        '<th>Anterior</th>' +
        '<th>Entrada</th>' +
        '<th>Salida</th>' +
        '<th>Saldo</th>' +
      '</tr>' +
    '</thead>'
  );
}

function construirHeaderDataTableResumenInventario() {
  var $tabla = $("#dataTablaResumenInventario");

  $tabla.find('thead').remove();

  $tabla.prepend(
    '<thead>' +
      '<tr>' +
        '<th>Producto</th>' +
        '<th>Categoría</th>' +
        '<th>Bodega</th>' +
        '<th>Precio usado</th>' +
        '<th>Existencia</th>' +
        '<th>Valor disponible</th>' +
      '</tr>' +
    '</thead>'
  );
}

function construirHeaderDataTableHistoricoVendidoInventario() {
  var $tabla = $("#dataTablaHistoricoVendidoInventario");

  $tabla.find('thead').remove();

  $tabla.prepend(
    '<thead>' +
      '<tr>' +
        '<th>Producto</th>' +
        '<th>Código</th>' +
        '<th>Categoría</th>' +
        '<th>Cantidad vendida</th>' +
        '<th>Total vendido</th>' +
      '</tr>' +
    '</thead>'
  );
}

/* =========================================================
   INVENTARIO TRANSFERENCIA
   ========================================================= */


var TRANSFERENCIA_MOBILE_QUERY = '(max-width: 767.98px)';
var TRANSFERENCIA_STORAGE_VISTA = 'izzy.transferencia.tipo_vista';

var transferenciaUI = {
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

function transferenciaEsMovil() {
  return window.matchMedia
    ? window.matchMedia(TRANSFERENCIA_MOBILE_QUERY).matches
    : $(window).width() <= 767;
}

function transferenciaConfigurarPanel(btn, contenido, key) {
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

  $(btn).off('click.transferenciaPanel').on('click.transferenciaPanel', function() {
    visible = !visible;
    $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
    sync();
    try { localStorage.setItem(key, visible ? '1' : '0'); } catch (e) {}
  });
}

function transferenciaInicializarVista() {
  var saved = 'detalle';
  try { saved = localStorage.getItem(TRANSFERENCIA_STORAGE_VISTA) || 'detalle'; } catch (e) {}

  transferenciaUI.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
  transferenciaUI.view = transferenciaEsMovil() ? 'miniatura' : transferenciaUI.preferredView;

  transferenciaActualizarVistaUI();
  transferenciaSincronizarPageSize();
}

function transferenciaActualizarVistaUI() {
  var movil = transferenciaEsMovil();

  $('.transferencia-view-btn[data-view="detalle"]')
    .toggleClass('d-none', movil)
    .prop('disabled', movil);

  $('.transferencia-view-btn')
    .removeClass('active')
    .attr('aria-pressed', 'false');

  $('.transferencia-view-btn[data-view="' + transferenciaUI.view + '"]')
    .addClass('active')
    .attr('aria-pressed', 'true');
}

function transferenciaSincronizarPageSize() {
  var mini = transferenciaUI.view === 'miniatura';
  var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
  var preferido = mini ? transferenciaUI.pageSizeMiniatura : transferenciaUI.pageSizeDetalle;
  if (opciones.indexOf(preferido) === -1) preferido = opciones[0];

  var $select = $('#transferenciaPageSize').empty();
  opciones.forEach(function(n) {
    $select.append($('<option></option>').val(n).text(n));
  });

  transferenciaUI.pageSize = preferido;
  $select.val(String(preferido));
}

function transferenciaTextoRow(row) {
  return [
    row.producto, row.barCode, row.medida, row.numero_lote, row.bodega,
    row.fecha_registro, row.movimientos_id, row.saldo_anterior,
    row.entrada, row.salida, row.saldo
  ].map(function(v) {
    return inventarioValor(v, '').toLowerCase();
  }).join(' ');
}

function transferenciaFiltrar(rows) {
  var q = $.trim(transferenciaUI.search || '').toLowerCase();
  if (!q) return rows.slice();

  return rows.filter(function(row) {
    return transferenciaTextoRow(row).indexOf(q) !== -1;
  });
}

function transferenciaImagenUrl(row) {
  var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';
  var image = row.image || row.imagen || '';

  if (!image) return fallback;
  if (/^https?:\/\//i.test(image)) return image;

  return '<?php echo SERVERURL;?>vistas/plantilla/img/products/' + image;
}

function transferenciaTipoProducto(row) {
  return toNumber(row.superior) > 0
    ? '<span class="transferencia-product-badge transferencia-product-compuesto"><i class="fas fa-project-diagram"></i> Compuesto</span>'
    : '<span class="transferencia-product-badge transferencia-product-normal"><i class="fas fa-box"></i> Normal</span>';
}

function transferenciaAcciones(row, index) {
  return '' +
    '<div class="dropdown acciones-dropdown">' +
      '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle table_read ocultar">' +
        '<i class="fas fa-cog"></i><span>Acciones</span>' +
      '</button>' +
      '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
        '<button type="button" class="dropdown-item accion-item js-transferencia-fecha table_change_date ocultar" data-index="' + index + '">' +
          '<span class="accion-icon accion-icon-editar"><i class="fas fa-calendar-alt"></i></span>' +
          '<span class="accion-label">Cambiar fecha</span>' +
        '</button>' +
        '<button type="button" class="dropdown-item accion-item js-transferencia-producto table_transferencia ocultar" data-index="' + index + '">' +
          '<span class="accion-icon accion-icon-editar"><i class="fas fa-exchange-alt"></i></span>' +
          '<span class="accion-label">Transferir</span>' +
        '</button>' +
      '</div>' +
    '</div>';
}

function transferenciaAbrirTransferir(row) {
  $('#formTransferencia')[0].reset();

  if (toNumber(row.superior) > 0) {
    showNotify('error', 'Error', 'No se puede hacer transferencia de producto que depende de otro inventario');
    return false;
  }

  $('#formTransferencia #productos_id').val(row.productos_id);
  $('#formTransferencia #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + row.producto);
  $('#formTransferencia #id_bodega_actual').val(row.id_bodega);
  $('#formTransferencia #lote_id_productos').val(row.lote_id);
  $('#formTransferencia #empresa_id_productos').val(row.empresa_id);

  $('#modal_transferencia_producto').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function transferenciaAbrirFecha(row) {
  $('#formTransferenciaCambiarFecha')[0].reset();

  $('#formTransferenciaCambiarFecha #productos_id').val(row.productos_id);
  $('#formTransferenciaCambiarFecha #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + row.producto);
  $('#formTransferenciaCambiarFecha #id_bodega_actual').val(row.id_bodega);
  $('#formTransferenciaCambiarFecha #cantidad_productos').val(toNumber(row.saldo));
  $('#formTransferenciaCambiarFecha #empresa_id_productos').val(row.empresa_id);
  $('#formTransferenciaCambiarFecha #lote_id_productos').val(row.lote_id);

  $('#modalCambiarFechaProducto').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function transferenciaActualizarTotales(rows) {
  var anterior = 0, entrada = 0, salida = 0, saldo = 0;

  rows.forEach(function(row) {
    anterior += toNumber(row.saldo_anterior);
    entrada += toNumber(row.entrada);
    salida += toNumber(row.salida);
    saldo += toNumber(row.saldo);
  });

  $('#transferencia_total_anterior_listado').text(formatNumber(anterior));
  $('#transferencia_total_entrada_listado').text(formatNumber(entrada));
  $('#transferencia_total_salida_listado').text(formatNumber(salida));
  $('#transferencia_total_saldo_listado').text(formatNumber(saldo));
}

function transferenciaRenderDetalle(rows, offset) {
  var html = '' +
    '<div class="transferencia-detail-header">' +
      '<div>Producto</div>' +
      '<div>Lote / Bodega</div>' +
      '<div>Último Movimiento</div>' +
      '<div>Anterior</div>' +
      '<div>Entrada</div>' +
      '<div>Salida</div>' +
      '<div>Saldo</div>' +
      '<div>Acciones</div>' +
    '</div>';

  rows.forEach(function(row, i) {
    var idx = offset + i;
    var imageUrl = transferenciaImagenUrl(row);
    var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

    html += '' +
      '<article class="transferencia-detail-row">' +
        '<div class="transferencia-cell">' +
          '<span class="transferencia-cell-label">Producto</span>' +
          '<div class="transferencia-product-box">' +
            '<a href="#" class="iv-trigger transferencia-product-image" ' +
               'data-iv-src="' + inventarioEscape(imageUrl) + '" ' +
               'data-iv-fallback="' + inventarioEscape(fallback) + '" ' +
               'data-iv-title="' + inventarioEscape(inventarioValor(row.producto, 'Producto')) + '">' +
              '<img src="' + inventarioEscape(imageUrl) + '" alt="' + inventarioEscape(inventarioValor(row.producto, 'Producto')) + '" ' +
                   'onerror="this.onerror=null;this.src=\'' + fallback + '\';">' +
              '<span class="transferencia-image-overlay">' +
                '<span class="transferencia-image-overlay-icon"><i class="fas fa-search-plus"></i></span>' +
                '<span class="transferencia-image-overlay-text">Ver imagen</span>' +
              '</span>' +
            '</a>' +
            '<div class="transferencia-product-info">' +
              '<strong>' + inventarioEscape(inventarioValor(row.producto, 'Sin producto')) + '</strong>' +
              '<small><i class="fas fa-barcode mr-1"></i>' + inventarioEscape(inventarioValor(row.barCode, 'Sin código')) + '</small>' +
              '<small><i class="fas fa-ruler-combined mr-1"></i>' + inventarioEscape(inventarioValor(row.medida, 'Sin medida')) + '</small>' +
              transferenciaTipoProducto(row) +
            '</div>' +
          '</div>' +
        '</div>' +

        '<div class="transferencia-cell">' +
          '<span class="transferencia-cell-label">Lote / Bodega</span>' +
          '<div class="transferencia-stack">' +
            '<span><b>Lote:</b> ' + inventarioEscape(inventarioValor(row.numero_lote, 'No especificado')) + '</span>' +
            '<span><b>Bodega:</b> ' + inventarioEscape(inventarioValor(row.bodega, 'Sin bodega')) + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="transferencia-cell">' +
          '<span class="transferencia-cell-label">Último Movimiento</span>' +
          '<div class="transferencia-stack">' +
            '<span><b>Fecha:</b> ' + inventarioEscape(inventarioValor(row.fecha_registro, 'No registrada')) + '</span>' +
            '<span><b>ID:</b> ' + inventarioEscape(inventarioValor(row.movimientos_id, 'Sin ID')) + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="transferencia-cell transferencia-number-cell">' + inventarioNumeroBadge(row.saldo_anterior, 'anterior') + '</div>' +
        '<div class="transferencia-cell transferencia-number-cell">' + inventarioNumeroBadge(row.entrada, 'entrada') + '</div>' +
        '<div class="transferencia-cell transferencia-number-cell">' + inventarioNumeroBadge(row.salida, 'salida') + '</div>' +
        '<div class="transferencia-cell transferencia-number-cell">' + inventarioNumeroBadge(row.saldo, 'saldo') + '</div>' +

        '<div class="transferencia-cell transferencia-actions-cell">' +
          '<span class="transferencia-cell-label">Acciones</span>' +
          transferenciaAcciones(row, idx) +
        '</div>' +
      '</article>';
  });

  return html;
}

function transferenciaRenderMiniatura(rows, offset) {
  var html = '<div class="transferencia-mini-grid">';

  rows.forEach(function(row, i) {
    var idx = offset + i;
    var imageUrl = transferenciaImagenUrl(row);
    var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

    html += '' +
      '<article class="transferencia-mini-card">' +
        '<div class="transferencia-mini-topline"></div>' +
        '<div class="transferencia-mini-header">' +
          '<a href="#" class="iv-trigger transferencia-mini-image" ' +
             'data-iv-src="' + inventarioEscape(imageUrl) + '" ' +
             'data-iv-fallback="' + inventarioEscape(fallback) + '" ' +
             'data-iv-title="' + inventarioEscape(inventarioValor(row.producto, 'Producto')) + '">' +
            '<img src="' + inventarioEscape(imageUrl) + '" alt="' + inventarioEscape(inventarioValor(row.producto, 'Producto')) + '" ' +
                 'onerror="this.onerror=null;this.src=\'' + fallback + '\';">' +
            '<span class="transferencia-image-overlay">' +
              '<span class="transferencia-image-overlay-icon"><i class="fas fa-search-plus"></i></span>' +
              '<span class="transferencia-image-overlay-text">Ver imagen</span>' +
            '</span>' +
          '</a>' +
          '<div class="transferencia-mini-identity">' +
            '<h4>' + inventarioEscape(inventarioValor(row.producto, 'Sin producto')) + '</h4>' +
            '<div class="transferencia-mini-meta">' +
              '<span><i class="fas fa-barcode"></i>' + inventarioEscape(inventarioValor(row.barCode, 'Sin código')) + '</span>' +
              '<span><i class="fas fa-ruler-combined"></i>' + inventarioEscape(inventarioValor(row.medida, 'Sin medida')) + '</span>' +
            '</div>' +
            transferenciaTipoProducto(row) +
          '</div>' +
        '</div>' +

        '<div class="transferencia-mini-body">' +
          '<div class="transferencia-mini-field"><span>Lote</span><strong>' + inventarioEscape(inventarioValor(row.numero_lote, 'No especificado')) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Bodega</span><strong>' + inventarioEscape(inventarioValor(row.bodega, 'Sin bodega')) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Última fecha</span><strong>' + inventarioEscape(inventarioValor(row.fecha_registro, 'No registrada')) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Movimiento</span><strong>' + inventarioEscape(inventarioValor(row.movimientos_id, 'Sin ID')) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Anterior</span><strong>' + formatNumber(toNumber(row.saldo_anterior)) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Entrada</span><strong class="transferencia-text-success">' + formatNumber(toNumber(row.entrada)) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Salida</span><strong class="transferencia-text-danger">' + formatNumber(toNumber(row.salida)) + '</strong></div>' +
          '<div class="transferencia-mini-field"><span>Saldo</span><strong>' + formatNumber(toNumber(row.saldo)) + '</strong></div>' +
        '</div>' +

        '<div class="transferencia-mini-footer">' +
          transferenciaAcciones(row, idx) +
        '</div>' +
      '</article>';
  });

  return html + '</div>';
}

function transferenciaRenderPaginacion(totalPages) {
  var current = transferenciaUI.page;
  var html = '';

  function btn(label, page, disabled, active, icon) {
    return '<button type="button" class="transferencia-page-btn' + (active ? ' active' : '') + '" data-page="' + page + '"' +
      (disabled ? ' disabled' : '') + '>' +
      (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
    '</button>';
  }

  html += btn('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
  html += btn('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

  var from = Math.max(1, current - 2);
  var to = Math.min(totalPages, from + 4);
  from = Math.max(1, to - 4);

  for (var p = from; p <= to; p++) {
    html += btn(String(p), p, false, p === current, '');
  }

  html += btn('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
  html += btn('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

  $('#transferenciaPaginacion').html(html);
}

function transferenciaRender() {
  var rows = transferenciaUI.filtered || [];

  if (transferenciaUI.loading) {
    $('#transferenciaListado').html('<div class="transferencia-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando inventario</strong><span>Espere mientras consultamos los registros.</span></div>');
    $('#transferenciaInfo').text('0 registros');
    $('#transferenciaPaginacion').empty();
    return;
  }

  if (!rows.length) {
    $('#transferenciaListado').html('<div class="transferencia-state"><i class="fas fa-box-open"></i><strong>Sin registros</strong><span>No se encontraron productos con los filtros actuales.</span></div>');
    $('#transferenciaInfo').text('0 registros');
    $('#transferenciaPaginacion').empty();
    return;
  }

  var pages = Math.max(1, Math.ceil(rows.length / transferenciaUI.pageSize));
  if (transferenciaUI.page > pages) transferenciaUI.page = pages;

  var offset = (transferenciaUI.page - 1) * transferenciaUI.pageSize;
  var pageRows = rows.slice(offset, offset + transferenciaUI.pageSize);

  $('#transferenciaListado')
    .removeClass('vista-detalle vista-miniatura')
    .addClass('vista-' + transferenciaUI.view)
    .html(
      transferenciaUI.view === 'miniatura'
        ? transferenciaRenderMiniatura(pageRows, offset)
        : transferenciaRenderDetalle(pageRows, offset)
    );

  var end = Math.min(offset + pageRows.length, rows.length);
  $('#transferenciaInfo').text('Mostrando ' + (offset + 1) + ' a ' + end + ' de ' + rows.length + ' registros');
  transferenciaRenderPaginacion(pages);

  if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
      typeof getPrivilegioTipoUsuario === 'function') {
    getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
  }
}

function inventario_transferencia() {
  var filtros = obtenerFiltrosInventario();

  transferenciaUI.loading = true;
  transferenciaRender();

  $.ajax({
    method: 'POST',
    url: '<?php echo SERVERURL;?>core/inventario/llenarDataTableInventarioTransferencia.php',
    dataType: 'json',
    data: {
      categoria_id: filtros.categoria_id,
      bodega: filtros.bodega,
      productos_id: filtros.productos_id
    },
    timeout: 30000
  }).done(function(json) {
    transferenciaUI.rows = json && Array.isArray(json.data) ? json.data : [];
    transferenciaUI.search = $('#buscar_transferencia_general').val() || '';
    transferenciaUI.filtered = transferenciaFiltrar(transferenciaUI.rows);
    transferenciaUI.page = 1;
    transferenciaUI.loading = false;

    inventarioActualizarResumen(transferenciaUI.filtered);
    transferenciaActualizarTotales(transferenciaUI.filtered);
    transferenciaRender();
  }).fail(function(xhr) {
    transferenciaUI.rows = [];
    transferenciaUI.filtered = [];
    transferenciaUI.loading = false;
    inventarioActualizarResumen([]);
    transferenciaActualizarTotales([]);
    transferenciaRender();

    console.error('Error inventario transferencia:', xhr.responseText);
    if (typeof showNotify === 'function') {
      showNotify('error', 'Error', 'No se pudo cargar el inventario.');
    }
  });
}

/* =========================================================
   EXPORTACIONES PRINCIPALES
   ========================================================= */

function transferenciaExcelEscape(value) {
  return String(value === null || value === undefined ? '' : value)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function transferenciaExcelCol(index) {
  var name = '';
  while (index >= 0) {
    name = String.fromCharCode((index % 26) + 65) + name;
    index = Math.floor(index / 26) - 1;
  }
  return name;
}

function transferenciaExcelCell(ref, value, style, numeric) {
  if (numeric) {
    var n = Number(value);
    if (!isFinite(n)) n = 0;
    return '<c r="' + ref + '" s="' + style + '" t="n"><v>' + n + '</v></c>';
  }

  return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
    transferenciaExcelEscape(value) + '</t></is></c>';
}

function transferenciaGenerarXlsx(rows) {
  if (typeof JSZip === 'undefined') return null;

  var totalEntrada = 0, totalSalida = 0, totalSaldo = 0;
  rows.forEach(function(r) {
    totalEntrada += toNumber(r.entrada);
    totalSalida += toNumber(r.salida);
    totalSaldo += toNumber(r.saldo);
  });

  var headers = ['Producto','Código','Medida','Lote','Bodega','Último movimiento','Anterior','Entrada','Salida','Saldo'];
  var sheetRows = [];

  sheetRows.push('<row r="1" ht="30" customHeight="1">' + transferenciaExcelCell('A1','IZZY • REPORTE DE INVENTARIO',1,false) + '</row>');
  sheetRows.push('<row r="2" ht="20" customHeight="1">' + transferenciaExcelCell('A2','Existencias, lotes, bodegas y movimientos • Generado: ' + new Date().toLocaleDateString('es-HN'),2,false) + '</row>');
  sheetRows.push('<row r="3">' +
    transferenciaExcelCell('A3','REGISTROS',6,false) +
    transferenciaExcelCell('D3','ENTRADAS',6,false) +
    transferenciaExcelCell('G3','SALIDAS',6,false) +
    transferenciaExcelCell('I3','SALDO',6,false) +
  '</row>');
  sheetRows.push('<row r="4" ht="24" customHeight="1">' +
    transferenciaExcelCell('A4',rows.length,7,true) +
    transferenciaExcelCell('D4',totalEntrada,11,true) +
    transferenciaExcelCell('G4',totalSalida,11,true) +
    transferenciaExcelCell('I4',totalSaldo,11,true) +
  '</row>');

  var filtros = obtenerFiltrosInventario();
  sheetRows.push('<row r="5">' +
    transferenciaExcelCell('A5',
      'Filtros: Categoría ' + ($('#inventario_tipo_productos_id option:selected').text() || 'Todas') +
      ' | Producto ' + ($('#inventario_productos_id option:selected').text() || 'Todos') +
      ' | Almacén ' + ($('#almacen option:selected').text() || 'Todos') +
      ' | Búsqueda ' + ($.trim($('#buscar_transferencia_general').val()) || 'Sin búsqueda'),
      8,false) +
  '</row>');

  sheetRows.push('<row r="6">' + transferenciaExcelCell('A6','Detalle de inventario filtrado',8,false) + '</row>');

  sheetRows.push('<row r="7" ht="26" customHeight="1">' +
    headers.map(function(h,i){ return transferenciaExcelCell(transferenciaExcelCol(i)+'7',h,3,false); }).join('') +
  '</row>');

  rows.forEach(function(r, i) {
    var rr = 8 + i;
    var values = [
      inventarioValor(r.producto,''),
      inventarioValor(r.barCode,''),
      inventarioValor(r.medida,''),
      inventarioValor(r.numero_lote,'No especificado'),
      inventarioValor(r.bodega,'Sin bodega'),
      inventarioValor(r.fecha_registro,'No registrada'),
      toNumber(r.saldo_anterior),
      toNumber(r.entrada),
      toNumber(r.salida),
      toNumber(r.saldo)
    ];

    sheetRows.push('<row r="' + rr + '" ht="22" customHeight="1">' +
      values.map(function(v,c){
        return transferenciaExcelCell(transferenciaExcelCol(c)+rr,v,c>=6?11:4,c>=6);
      }).join('') +
    '</row>');
  });

  var lastRow = Math.max(7, 7 + rows.length);

  var sheetXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
      '<dimension ref="A1:J' + lastRow + '"/>' +
      '<sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' +
      '<cols>' +
        '<col min="1" max="1" width="28" customWidth="1"/><col min="2" max="3" width="18" customWidth="1"/>' +
        '<col min="4" max="6" width="22" customWidth="1"/><col min="7" max="10" width="15" customWidth="1"/>' +
      '</cols>' +
      '<sheetData>' + sheetRows.join('') + '</sheetData>' +
      '<autoFilter ref="A7:J' + lastRow + '"/>' +
      '<mergeCells count="10">' +
        '<mergeCell ref="A1:J1"/><mergeCell ref="A2:J2"/>' +
        '<mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/>' +
        '<mergeCell ref="D3:F3"/><mergeCell ref="D4:F4"/>' +
        '<mergeCell ref="G3:H3"/><mergeCell ref="G4:H4"/>' +
        '<mergeCell ref="I3:J3"/><mergeCell ref="I4:J4"/>' +
      '</mergeCells>' +
    '</worksheet>';

  var stylesXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
      '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>' +
      '<fonts count="7">' +
        '<font><sz val="10"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
        '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
        '<font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
        '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
      '</fonts>' +
      '<fills count="5">' +
        '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
        '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
      '</fills>' +
      '<borders count="2">' +
        '<border><left/><right/><top/><bottom/><diagonal/></border>' +
        '<border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border>' +
      '</borders>' +
      '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
      '<cellXfs count="12">' +
        '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
        '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/>' +
        '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0"/>' +
        '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"/>' +
        '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0"/>' +
        '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0"/>' +
        '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0"/>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"/>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0"/>' +
        '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf>' +
      '</cellXfs>' +
      '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
    '</styleSheet>';

  var workbookXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
      '<sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets>' +
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
  zip.folder('xl').folder('worksheets').file('sheet1.xml', sheetXml);

  var opts = {type:'blob', mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', compression:'DEFLATE'};

  if (typeof zip.generateAsync === 'function') return zip.generateAsync(opts);
  if (typeof zip.generate === 'function') return Promise.resolve(zip.generate(opts));

  return Promise.reject(new Error('JSZip no soportado.'));
}

function transferenciaExportarExcel() {
  var rows = transferenciaUI.filtered || [];
  if (!rows.length) {
    showNotify('warning','Sin información','No hay registros para exportar.');
    return;
  }

  var promise = transferenciaGenerarXlsx(rows);
  if (!promise) {
    showNotify('error','Excel no disponible','JSZip no está disponible.');
    return;
  }

  promise.then(function(blob) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'Reporte_Inventario.xlsx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function(){ URL.revokeObjectURL(url); }, 1000);
  }).catch(function(error) {
    console.error(error);
    showNotify('error','Error','No se pudo generar el Excel.');
  });
}

function transferenciaObtenerLogoPdf(callback) {
  if (typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
    callback(imagen);
    return;
  }

  $.ajax({
    type:'GET',
    url:'<?php echo SERVERURL;?>core/get_image.php',
    dataType:'text',
    timeout:15000
  }).done(function(url) {
    url = $.trim(url || '');
    if (!url) {
      showNotify('error','Logo no disponible','No se pudo obtener el logo.');
      return;
    }

    var img = new Image();
    img.crossOrigin = 'Anonymous';
    img.onload = function() {
      try {
        var canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth || img.width;
        canvas.height = img.naturalHeight || img.height;
        canvas.getContext('2d').drawImage(img,0,0);
        imagen = canvas.toDataURL('image/png');
        callback(imagen);
      } catch (e) {
        showNotify('error','Logo no disponible','No se pudo preparar el logo.');
      }
    };
    img.onerror = function() {
      showNotify('error','Logo no disponible','No se pudo cargar el logo.');
    };
    img.src = url;
  }).fail(function() {
    showNotify('error','Logo no disponible','No se pudo obtener el logo.');
  });
}

function transferenciaExportarPdf() {
  var rows = transferenciaUI.filtered || [];

  if (!rows.length) {
    showNotify('warning','Sin información','No hay registros para mostrar en PDF.');
    return;
  }

  if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
    showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');
    return;
  }

  transferenciaObtenerLogoPdf(function(logo) {
    var totalEntrada = 0, totalSalida = 0, totalSaldo = 0;
    rows.forEach(function(r) {
      totalEntrada += toNumber(r.entrada);
      totalSalida += toNumber(r.salida);
      totalSaldo += toNumber(r.saldo);
    });

    var body = [[
      {text:'PRODUCTO',style:'th',fillColor:'#17324D'},
      {text:'LOTE / BODEGA',style:'th',fillColor:'#17324D'},
      {text:'ÚLTIMO MOV.',style:'th',fillColor:'#17324D'},
      {text:'ANTERIOR',style:'th',fillColor:'#17324D'},
      {text:'ENTRADA',style:'th',fillColor:'#17324D'},
      {text:'SALIDA',style:'th',fillColor:'#17324D'},
      {text:'SALDO',style:'th',fillColor:'#17324D'}
    ]];

    rows.forEach(function(r, i) {
      var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';
      body.push([
        {text:inventarioValor(r.producto,'') + '\n' + inventarioValor(r.barCode,'Sin código'),style:'td',fillColor:fill},
        {text:inventarioValor(r.numero_lote,'No especificado') + '\n' + inventarioValor(r.bodega,'Sin bodega'),style:'td',fillColor:fill},
        {text:inventarioValor(r.fecha_registro,'No registrada') + '\n#' + inventarioValor(r.movimientos_id,'Sin ID'),style:'td',fillColor:fill},
        {text:formatNumber(toNumber(r.saldo_anterior)),style:'tdn',fillColor:fill},
        {text:formatNumber(toNumber(r.entrada)),style:'tdn',color:'#14804A',fillColor:fill},
        {text:formatNumber(toNumber(r.salida)),style:'tdn',color:'#C9372C',fillColor:fill},
        {text:formatNumber(toNumber(r.saldo)),style:'tdn',fillColor:fill}
      ]);
    });

    var doc = {
      pageSize:'LETTER',
      pageOrientation:'landscape',
      pageMargins:[28,28,28,34],
      header:function(){
        return {margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};
      },
      footer:function(page,pages){
        return {margin:[28,8,28,0],columns:[
          {text:'IZZY • Inventario',fontSize:7,color:'#7A869A'},
          {text:'Página ' + page + ' de ' + pages,fontSize:7,color:'#7A869A',alignment:'right'}
        ]};
      },
      content:[
        {
          table:{widths:[100,'*',160],body:[[
            {table:{widths:['*'],body:[[{image:logo,fit:[62,36],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]},
            {stack:[
              {text:'REPORTE DE INVENTARIO',bold:true,fontSize:16,color:'#FFFFFF'},
              {text:'Existencias, lotes, bodegas y movimientos',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}
            ],fillColor:'#17324D',margin:[0,10,0,10]},
            {stack:[
              {text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},
              {text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},
              {text:rows.length + ' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}
            ],fillColor:'#17324D',margin:[0,10,12,10]}
          ]]},
          layout:'noBorders',
          margin:[0,0,0,10]
        },
        {
          table:{widths:['*'],body:[[
            {text:
              'Filtros: Categoría ' + ($('#inventario_tipo_productos_id option:selected').text() || 'Todas') +
              ' | Producto ' + ($('#inventario_productos_id option:selected').text() || 'Todos') +
              ' | Almacén ' + ($('#almacen option:selected').text() || 'Todos') +
              ' | Búsqueda ' + ($.trim($('#buscar_transferencia_general').val()) || 'Sin búsqueda'),
             fontSize:7,color:'#52627A',fillColor:'#F7F9FC',margin:[8,7,8,7]}
          ]]},
          layout:'lightHorizontalLines',
          margin:[0,0,0,10]
        },
        {
          table:{widths:['*','*','*','*'],body:[[
            {stack:[{text:'REGISTROS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:13,bold:true,color:'#172B4D'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'ENTRADAS',fontSize:6.3,bold:true,color:'#6B778C'},{text:formatNumber(totalEntrada),fontSize:13,bold:true,color:'#14804A'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'SALIDAS',fontSize:6.3,bold:true,color:'#6B778C'},{text:formatNumber(totalSalida),fontSize:13,bold:true,color:'#C9372C'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},
            {stack:[{text:'SALDO',fontSize:6.3,bold:true,color:'#6B778C'},{text:formatNumber(totalSaldo),fontSize:13,bold:true,color:'#6554C0'}],fillColor:'#F7F9FC',margin:[8,7,8,7]}
          ]]},
          layout:'lightHorizontalLines',
          margin:[0,0,0,12]
        },
        {text:'VISTA DETALLE',bold:true,fontSize:7,color:'#17324D',margin:[0,0,0,7]},
        {
          table:{headerRows:1,widths:[150,120,105,70,70,70,70],body:body},
          layout:{
            hLineColor:function(){return '#DDE3EA';},
            vLineColor:function(){return '#DDE3EA';},
            hLineWidth:function(){return .55;},
            vLineWidth:function(){return .55;},
            paddingLeft:function(){return 5;},
            paddingRight:function(){return 5;},
            paddingTop:function(){return 6;},
            paddingBottom:function(){return 6;}
          }
        }
      ],
      styles:{
        th:{fontSize:6.2,bold:true,color:'#FFFFFF',alignment:'center'},
        td:{fontSize:6.2,color:'#253858'},
        tdn:{fontSize:6.2,color:'#253858',alignment:'right'}
      }
    };

    pdfMake.createPdf(doc).getDataUrl(function(url) {
      abrirModalPdfPublico(url,'Reporte de Inventario','Reporte_Inventario.pdf');
    });
  });
}

function transferenciaInicializarEventosUI() {
  transferenciaConfigurarPanel('#btnToggleFiltrosTransferencia','#transferenciaFiltrosContenido','izzy.transferencia.filtros.visible');
  transferenciaConfigurarPanel('#btnToggleKpisTransferencia','#transferenciaKpisContenido','izzy.transferencia.kpis.visible');
  transferenciaInicializarVista();

  $('#buscar_transferencia_general').off('input.transferenciaUI').on('input.transferenciaUI',function(){
    transferenciaUI.search = $(this).val() || '';
    transferenciaUI.filtered = transferenciaFiltrar(transferenciaUI.rows || []);
    transferenciaUI.page = 1;
    inventarioActualizarResumen(transferenciaUI.filtered);
    transferenciaActualizarTotales(transferenciaUI.filtered);
    transferenciaRender();
  });

  $('#limpiarBuscarTransferencia').off('click.transferenciaUI').on('click.transferenciaUI',function(){
    transferenciaUI.search = '';
    $('#buscar_transferencia_general').val('').focus();
    transferenciaUI.filtered = transferenciaFiltrar(transferenciaUI.rows || []);
    transferenciaUI.page = 1;
    inventarioActualizarResumen(transferenciaUI.filtered);
    transferenciaActualizarTotales(transferenciaUI.filtered);
    transferenciaRender();
  });

  $('#transferenciaPageSize').off('change.transferenciaUI').on('change.transferenciaUI',function(){
    var n = parseInt($(this).val(),10);
    if (!n) return;
    transferenciaUI.pageSize = n;
    if (transferenciaUI.view === 'miniatura') transferenciaUI.pageSizeMiniatura = n;
    else transferenciaUI.pageSizeDetalle = n;
    transferenciaUI.page = 1;
    transferenciaRender();
  });

  $('.transferencia-view-btn').off('click.transferenciaUI').on('click.transferenciaUI',function(){
    var vista = $(this).data('view');
    transferenciaUI.view = transferenciaEsMovil() ? 'miniatura' : (vista === 'miniatura' ? 'miniatura' : 'detalle');

    if (!transferenciaEsMovil()) {
      transferenciaUI.preferredView = transferenciaUI.view;
      try { localStorage.setItem(TRANSFERENCIA_STORAGE_VISTA, transferenciaUI.preferredView); } catch (e) {}
    }

    transferenciaUI.page = 1;
    transferenciaActualizarVistaUI();
    transferenciaSincronizarPageSize();
    transferenciaRender();
  });

  $('#transferenciaPaginacion').off('click.transferenciaUI','.transferencia-page-btn').on('click.transferenciaUI','.transferencia-page-btn',function(){
    if (this.disabled) return;
    var p = parseInt($(this).data('page'),10);
    if (!p) return;
    transferenciaUI.page = p;
    transferenciaRender();
  });

  $('#transferenciaListado')
    .off('click.transferenciaUI','.js-transferencia-producto')
    .on('click.transferenciaUI','.js-transferencia-producto',function(){
      var row = transferenciaUI.filtered[parseInt($(this).data('index'),10)];
      if (row) transferenciaAbrirTransferir(row);
    })
    .off('click.transferenciaFecha','.js-transferencia-fecha')
    .on('click.transferenciaFecha','.js-transferencia-fecha',function(){
      var row = transferenciaUI.filtered[parseInt($(this).data('index'),10)];
      if (row) transferenciaAbrirFecha(row);
    });

  $('#btnActualizarTransferencia').off('click.transferenciaUI').on('click.transferenciaUI',inventario_transferencia);
  $('#btnResumenTransferencia').off('click.transferenciaUI').on('click.transferenciaUI',mostrarVistaResumenInventario);
  $('#btnExcelTransferencia').off('click.transferenciaUI').on('click.transferenciaUI',transferenciaExportarExcel);
  $('#btnPdfTransferencia').off('click.transferenciaUI').on('click.transferenciaUI',transferenciaExportarPdf);

  $(window).off('resize.transferenciaUI orientationchange.transferenciaUI').on('resize.transferenciaUI orientationchange.transferenciaUI',function(){
    var objetivo = transferenciaEsMovil() ? 'miniatura' : transferenciaUI.preferredView;
    if (transferenciaUI.view !== objetivo) {
      transferenciaUI.view = objetivo;
      transferenciaUI.page = 1;
      transferenciaActualizarVistaUI();
      transferenciaSincronizarPageSize();
      transferenciaRender();
    } else {
      transferenciaActualizarVistaUI();
    }
  });
}

/* =========================================================
   RESUMEN INVENTARIO
   ========================================================= */

function mostrarVistaResumenInventario() {
  $('#vistaInventarioPrincipal').hide();
  $('#vistaResumenInventario').show();

  $('#inventario_tipo_valorizacion').selectpicker('refresh');

  cargarResumenInventario();
  cargarHistoricoVendidoInventario();
}

function mostrarVistaInventarioPrincipal() {
  $('#vistaResumenInventario').hide();
  $('#vistaInventarioPrincipal').show();

  setTimeout(function(){
    transferenciaActualizarVistaUI();
    transferenciaRender();
  }, 80);
}

function cargarResumenInventario() {
  var filtros = obtenerFiltrosInventario();

  $.ajax({
    type: "POST",
    url: "<?php echo SERVERURL;?>core/inventario/llenarDataTableInventarioTransferencia.php",
    dataType: "json",
    data: {
      categoria_id: filtros.categoria_id,
      bodega: filtros.bodega,
      productos_id: filtros.productos_id
    },
    beforeSend: function(){
      $('#resumen_total_productos').text('...');
      $('#resumen_total_unidades').text('...');
      $('#resumen_valor_disponible').text('...');
    },
    success: function(json){
      var rows = [];

      if (json && json.data) {
        rows = json.data;
      }

      inventarioResumenRowsCache = rows;
      construirTablaResumenInventario(rows);
    },
    error: function(){
      inventarioResumenRowsCache = [];
      construirTablaResumenInventario([]);

      if (typeof showNotify === 'function') {
        showNotify('error', 'Error', 'No se pudo cargar el resumen de inventario');
      }
    }
  });
}

function construirTablaResumenInventario(rows) {
  rows = rows || [];

  var tipoValorizacion = $('#inventario_tipo_valorizacion').val() || 'venta';
  var textoValorizacion = tipoValorizacion === 'costo'
    ? 'Calculado por costo del producto'
    : 'Calculado por precio de venta';

  $('#resumen_texto_valorizacion').text(textoValorizacion);

  var detalle = [];
  var totalUnidades = 0;
  var totalValor = 0;

  rows.forEach(function(row){
    var existencia = toNumber(row.saldo);
    if (existencia <= 0) return;

    var precioVenta = toNumber(row.precio_venta);
    var precioCosto = toNumber(row.precio_compra);
    var precioUsado = tipoValorizacion === 'costo' ? precioCosto : precioVenta;
    var valorDisponible = existencia * precioUsado;

    totalUnidades += existencia;
    totalValor += valorDisponible;

    detalle.push({
      producto: row.producto,
      barCode: row.barCode,
      categoria: row.categoria || 'Sin categoría',
      bodega: row.bodega || 'Sin bodega',
      existencia: existencia,
      precio_usado: precioUsado,
      valor_disponible: valorDisponible,
      image: row.image || row.imagen || ''
    });
  });

  $('#resumen_total_productos').text(detalle.length);
  $('#resumen_total_unidades').text(formatNumber(totalUnidades));
  $('#resumen_valor_disponible').text(formatMoney(totalValor));

  var html = '<div class="transferencia-summary-grid">';

  detalle.forEach(function(row) {
    var imageUrl = transferenciaImagenUrl(row);
    var fallback = '<?php echo SERVERURL;?>vistas/plantilla/img/products/image_preview.png';

    html += '' +
      '<article class="transferencia-summary-card">' +
        '<a href="#" class="iv-trigger transferencia-summary-image" data-iv-src="' + inventarioEscape(imageUrl) + '" data-iv-fallback="' + inventarioEscape(fallback) + '" data-iv-title="' + inventarioEscape(inventarioValor(row.producto,'Producto')) + '">' +
          '<img src="' + inventarioEscape(imageUrl) + '" alt="' + inventarioEscape(inventarioValor(row.producto,'Producto')) + '" onerror="this.onerror=null;this.src=\'' + fallback + '\';">' +
          '<span class="transferencia-image-overlay"><span class="transferencia-image-overlay-icon"><i class="fas fa-search-plus"></i></span><span class="transferencia-image-overlay-text">Ver imagen</span></span>' +
        '</a>' +
        '<div class="transferencia-summary-copy">' +
          '<h4>' + inventarioEscape(inventarioValor(row.producto,'Sin producto')) + '</h4>' +
          '<p><i class="fas fa-barcode mr-1"></i>' + inventarioEscape(inventarioValor(row.barCode,'Sin código')) + '</p>' +
          '<div class="transferencia-summary-meta">' +
            '<span><b>Categoría:</b> ' + inventarioEscape(row.categoria) + '</span>' +
            '<span><b>Bodega:</b> ' + inventarioEscape(row.bodega) + '</span>' +
            '<span><b>Precio:</b> ' + formatMoney(row.precio_usado) + '</span>' +
            '<span><b>Existencia:</b> ' + formatNumber(row.existencia) + '</span>' +
            '<span class="transferencia-summary-total"><b>Valor:</b> ' + formatMoney(row.valor_disponible) + '</span>' +
          '</div>' +
        '</div>' +
      '</article>';
  });

  html += '</div>';

  if (!detalle.length) {
    html = '<div class="transferencia-state"><i class="fas fa-box-open"></i><strong>Sin existencias</strong><span>No hay productos con existencia disponible.</span></div>';
  }

  $('#resumenInventarioListado').html(html);
  $('#resumenInventarioInfo').text(detalle.length + ' registro(s)');
  $('#resumenInventarioPaginacion').empty();
}

/* =========================================================
   HISTÓRICO VENDIDO
   ========================================================= */

function cargarHistoricoVendidoInventario() {
  var filtros = obtenerFiltrosInventario();

  $('#cardHistoricoVendidoInventario').show();

  $.ajax({
    type: "POST",
    url: "<?php echo SERVERURL;?>core/inventario/getHistoricoVendidoInventario.php",
    dataType: "json",
    data: {
      categoria_id: filtros.categoria_id,
      bodega: filtros.bodega,
      productos_id: filtros.productos_id
    },
    beforeSend: function(){
      $('#resumen_total_historico_vendido').text('...');
    },
    success: function(json){
      var rows = [];

      if (json && json.data) {
        rows = json.data;
      }

      construirTablaHistoricoVendidoInventario(rows);

      if (json && json.resumen) {
        $('#resumen_total_historico_vendido').text(formatMoney(toNumber(json.resumen.total_vendido)));
      } else {
        $('#resumen_total_historico_vendido').text(formatMoney(0));
      }

      setTimeout(function(){
        if ($.fn.DataTable.isDataTable("#dataTablaHistoricoVendidoInventario")) {
          $("#dataTablaHistoricoVendidoInventario").DataTable().columns.adjust();
        }
      }, 150);
    },
    error: function(xhr){
      construirTablaHistoricoVendidoInventario([]);
      $('#resumen_total_historico_vendido').text(formatMoney(0));

      if (typeof showNotify === 'function') {
        showNotify('error', 'Error', 'No se pudo cargar el histórico vendido');
      }

      console.log('Error histórico vendido:', xhr.responseText);
    }
  });
}

function construirTablaHistoricoVendidoInventario(rows) {
  rows = rows || [];

  var totalCantidad = 0;
  var totalVendido = 0;

  rows.forEach(function(row){
    totalCantidad += toNumber(row.cantidad_vendida);
    totalVendido += toNumber(row.total_vendido);
  });

  var html = '<div class="transferencia-historico-grid">';

  rows.forEach(function(row) {
    html += '' +
      '<article class="transferencia-historico-card">' +
        '<div class="transferencia-historico-icon"><i class="fas fa-receipt"></i></div>' +
        '<div class="transferencia-historico-copy">' +
          '<h4>' + inventarioEscape(inventarioValor(row.producto,'Sin producto')) + '</h4>' +
          '<div class="transferencia-historico-meta">' +
            '<span><b>Código:</b> ' + inventarioEscape(inventarioValor(row.barCode,'Sin código')) + '</span>' +
            '<span><b>Categoría:</b> ' + inventarioEscape(inventarioValor(row.categoria,'Sin categoría')) + '</span>' +
            '<span><b>Cantidad:</b> ' + formatNumber(toNumber(row.cantidad_vendida)) + '</span>' +
            '<span class="transferencia-historico-total"><b>Total:</b> ' + formatMoney(toNumber(row.total_vendido)) + '</span>' +
          '</div>' +
        '</div>' +
      '</article>';
  });

  html += '</div>';

  if (!rows.length) {
    html = '<div class="transferencia-state"><i class="fas fa-receipt"></i><strong>Sin histórico</strong><span>No se encontraron productos vendidos.</span></div>';
  }

  $('#historicoInventarioListado').html(html);
  $('#historicoInventarioInfo').text(rows.length + ' registro(s)');
  $('#historicoInventarioPaginacion').empty();

  $('#resumen_total_historico_vendido').text(formatMoney(totalVendido));
}

/* =========================================================
   TRANSFERIR PRODUCTO / BODEGA
   ========================================================= */

var transferencia_producto_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_transferencia");

  $(tbody).on("click", "button.table_transferencia", function() {
    var data = table.row($(this).parents("tr")).data();

    $('#formTransferencia')[0].reset();

    if (toNumber(data.superior) > 0) {
      showNotify('error', 'Error', 'No se puede hacer transferencia de producto que depende de otro inventario');
      return false;
    }

    $('#formTransferencia #productos_id').val(data.productos_id);
    $('#formTransferencia #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferencia #id_bodega_actual').val(data.id_bodega);
    $('#formTransferencia #lote_id_productos').val(data.lote_id);
    $('#formTransferencia #empresa_id_productos').val(data.empresa_id);

    $('#modal_transferencia_producto').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });
  });
};

$('#formTransferencia').off('submit.inventarioTransferencia');
$('#formTransferencia').on('submit.inventarioTransferencia', function(e){
  e.preventDefault();

  var form = $("#formTransferencia");
  var respuesta = form.children('.RespuestaAjax');

  swal({
    title: "¿Estas seguro?",
    text: "¿Desea transferir este producto?",
    icon: "warning",
    buttons: {
      cancel: {
        text: "Cancelar",
        visible: true
      },
      confirm: {
        text: "¡Sí, transferir el producto!"
      }
    },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then((willConfirm) => {
    if (willConfirm) {
      var url = '<?php echo SERVERURL;?>ajax/modificarBodegaProductosAjax.php';

      $.ajax({
        type: 'POST',
        url: url,
        data: form.serialize(),
        beforeSend: function(){
          $('#modal_transferencia_producto').modal({
            show: false,
            keyboard: false,
            backdrop: 'static'
          });
        },
        success: function(data){
          respuesta.html(data);
        }
      });
    }
  });
});

/* =========================================================
   CAMBIAR FECHA DE CADUCIDAD
   ========================================================= */

var cambiarVencimientoProducto_dataTable = function(tbody, table){
  $(tbody).off("click", "button.table_change_date");

  $(tbody).on("click", "button.table_change_date", function(){
    var data = table.row($(this).parents("tr")).data();

    $('#formTransferenciaCambiarFecha')[0].reset();

    $('#formTransferenciaCambiarFecha #productos_id').val(data.productos_id);
    $('#formTransferenciaCambiarFecha #nameProduct').html("<b style='color:#007bff;font-size:16px;text-transform:uppercase;'>Producto:</b> " + data.producto);
    $('#formTransferenciaCambiarFecha #id_bodega_actual').val(data.id_bodega);
    $('#formTransferenciaCambiarFecha #cantidad_productos').val(toNumber(data.saldo));
    $('#formTransferenciaCambiarFecha #empresa_id_productos').val(data.empresa_id);
    $('#formTransferenciaCambiarFecha #lote_id_productos').val(data.lote_id);

    $('#modalCambiarFechaProducto').modal({
      show: true,
      keyboard: false,
      backdrop: 'static'
    });
  });
};

$('#EditarFechaVencimiento').off('click.inventarioFecha');
$('#EditarFechaVencimiento').on('click.inventarioFecha', function(e){
  e.preventDefault();

  var form = $("#formTransferenciaCambiarFecha");
  var respuesta = form.children('.RespuestaAjax');

  if (form[0].checkValidity()) {
    var url = '<?php echo SERVERURL;?>ajax/modificarFechaVencimientoProductosAjax.php';

    $.ajax({
      type: 'POST',
      url: url,
      data: form.serialize(),
      beforeSend: function(){
        $('#modalCambiarFechaProducto').modal({
          show: false,
          keyboard: false,
          backdrop: 'static'
        });
      },
      success: function(data){
        respuesta.html(data);
      }
    });
  } else {
    form[0].reportValidity();
  }
});

/* =========================================================
   CARGA DE COMBOS
   ========================================================= */

function getTipoProductos(){
  var url = '<?php echo SERVERURL;?>core/inventario/getTipoProductoMovimientosInventario.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data){
      $('#form_main_movimientos_transferencia #inventario_tipo_productos_id')
        .html(data)
        .val('0')
        .selectpicker('refresh');

      getProductosMovimientos(0);
    },
    error: function(xhr){
      $('#form_main_movimientos_transferencia #inventario_tipo_productos_id')
        .html('<option value="">Error al cargar categorías</option>')
        .val('')
        .selectpicker('refresh');

      console.log('Error categorías inventario:', xhr.responseText);
    }
  });
}

function getProductosMovimientos(categoria_id){
  var url = '<?php echo SERVERURL; ?>core/inventario/getProductosMovimientosCategoriaInventario.php';

  $('#form_main_movimientos_transferencia #inventario_productos_id')
    .html('<option value="">Cargando productos...</option>')
    .val('')
    .selectpicker('refresh');

  $.ajax({
    type: "POST",
    url: url,
    data: {
      categoria_id: categoria_id
    },
    success: function(data){
      $('#form_main_movimientos_transferencia #inventario_productos_id')
        .html(data)
        .val('0')
        .selectpicker('refresh');
    },
    error: function(xhr){
      $('#form_main_movimientos_transferencia #inventario_productos_id')
        .html('<option value="">Error al cargar productos</option>')
        .val('')
        .selectpicker('refresh');

      console.log('Error productos inventario:', xhr.responseText);
    }
  });
}

function getAlmacen(){
  var url = '<?php echo SERVERURL;?>core/inventario/getAlmacenInventario.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data){
      $('#form_main_movimientos_transferencia #almacen')
        .html(data)
        .val('0')
        .selectpicker('refresh');

      if ($('#formTransferencia #id_bodega').length) {
        $('#formTransferencia #id_bodega')
          .html(data)
          .selectpicker('refresh');
      }
    },
    error: function(xhr){
      $('#form_main_movimientos_transferencia #almacen')
        .html('<option value="">Error al cargar almacenes</option>')
        .val('')
        .selectpicker('refresh');

      console.log('Error almacenes inventario:', xhr.responseText);
    }
  });
}
</script>