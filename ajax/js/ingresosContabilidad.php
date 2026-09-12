<script>
  // ingresosContabilidad.php - este es el js
$(() => {
  // Inicializar interfaz IZZY 6.0
  inicializarIngresosUI();
  listar_ingresos_contabilidad();

  if (typeof getClientesIngresos === "function") {
    getClientesIngresos();
  }

  getCuentaIngresos();
  getEmpresaIngresos();

  // Buscar
  $('#formMainIngresosContabilidad #search').on("click", function (e) {
    e.preventDefault();
    listar_ingresos_contabilidad();
  });

  // Limpiar (reset)
  $('#formMainIngresosContabilidad').on('reset', function () {
    var $form = $(this);

    setTimeout(function () {
      $form.find('.selectpicker').val('').selectpicker('refresh');
      listar_ingresos_contabilidad();
    }, 0);
  });

  // =========================================================
  // CÁLCULO AUTOMÁTICO DE TOTALES
  // IMPORTANTE:
  // - No se modifica lo que el usuario escribe.
  // - Si escribe 10, queda 10.
  // - Solo se recalcula el campo total_ingresos.
  // =========================================================
  const camposCalculo = ["#subtotal_ingresos", "#isv_ingresos", "#descuento_ingresos", "#nc_ingresos"];

  camposCalculo.forEach(campo => {
    $("#formIngresosContables " + campo).off("input change blur keyup");

    $("#formIngresosContables " + campo).on("input change", function () {
      calcularTotalIngreso();
    });

    $("#formIngresosContables " + campo).on("blur", function () {
      var valor = parseFloat($(this).val());

      if (!isNaN(valor) && valor < 0) {
        $(this).val(0);
        showNotify("warning", "Advertencia", "Los valores no pueden ser negativos");
      }

      calcularTotalIngreso();
    });
  });

  // =========================================================
  // RESUMEN PREMIUM DE CUENTA EN FOOTER DEL MODAL
  // =========================================================
  $(document).off("changed.bs.select change", "#formIngresosContables #cuenta_ingresos");
  $(document).on("changed.bs.select change", "#formIngresosContables #cuenta_ingresos", function () {
    actualizarResumenFooterCuentaIngreso();
  });

  $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function () {
    $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
  });

  $("#modalIngresosContables").on('shown.bs.modal', function () {
    actualizarResumenFooterCuentaIngreso();

    setTimeout(function () {
      actualizarResumenFooterCuentaIngreso();
    }, 150);

    $(this).find('#formIngresosContables #recibide_ingresos').focus();
  });

  $("#modalIngresosContables").on('hidden.bs.modal', function () {
    limpiarResumenFooterCuentaIngreso();
  });
});

// ===== Utilidades =====
function calcularTotalIngreso() {
  const form = "#formIngresosContables ";

  const subtotal  = parseFloat($(form + "#subtotal_ingresos").val())  || 0;
  const isv       = parseFloat($(form + "#isv_ingresos").val())       || 0;
  const descuento = parseFloat($(form + "#descuento_ingresos").val()) || 0;
  const nc        = parseFloat($(form + "#nc_ingresos").val())        || 0;

  const total = subtotal + isv - descuento - nc;

  $(form + "#total_ingresos").val(total.toFixed(2));
}

function debounce(func, wait) {
  let timeout;

  return function () {
    const context = this, args = arguments;

    clearTimeout(timeout);

    timeout = setTimeout(() => func.apply(context, args), wait);
  };
}

function buscarClientes(searchText) {
  return $.ajax({
    type: "POST",
    url: "<?php echo SERVERURL;?>core/buscar_clientes.php",
    data: { searchText: searchText },
    dataType: "html"
  });
}

// Formateo de dinero — miles con coma y decimales con punto
function toNumber(val) {
  if (val == null) return 0;
  if (typeof val === "number") return val;

  return parseFloat(String(val).replace(/[^\d.-]/g, "")) || 0;
}

function formatMoney(n) {
  try {
    return Number(n).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  } catch (e) {
    var s = (Number(n) || 0).toFixed(2);

    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }
}

function moneyRender(data, type) {
  var n = toNumber(data);

  if (type === 'display') {
    var color = n < 0 ? 'red' : 'green';

    return '<span style="color:' + color + ';font-size:inherit;font-weight:inherit;line-height:inherit">L ' + formatMoney(n) + '</span>';
  }

  return n;
}

function escapeHtmlIngresos(value) {
  if (value == null) return "";

  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}


// =========================================================
// ESTILOS SEGUROS PARA TABLA DE INGRESOS
// ---------------------------------------------------------
// Evita que cualquier texto largo se salga de su columna.
// Aplica wrap general, pero mantiene acciones, estados y montos controlados.
// =========================================================
function inyectarEstilosTablaIngresos() {
  if (document.getElementById('ingresos-datatable-wrap-fix-style')) {
    return;
  }

  var style = document.createElement('style');
  style.id = 'ingresos-datatable-wrap-fix-style';
  style.type = 'text/css';
  style.appendChild(document.createTextNode(`
    #dataTableIngresosContabilidad {
      table-layout: fixed !important;
      width: 100% !important;
    }

    #dataTableIngresosContabilidad th,
    #dataTableIngresosContabilidad td {
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      vertical-align: middle !important;
    }

    #dataTableIngresosContabilidad th *,
    #dataTableIngresosContabilidad td * {
      max-width: 100% !important;
      box-sizing: border-box !important;
    }

    #dataTableIngresosContabilidad .ingresos-info-box,
    #dataTableIngresosContabilidad .ingresos-info-main,
    #dataTableIngresosContabilidad .ingresos-info-muted,
    #dataTableIngresosContabilidad .ingresos-info-chip,
    #dataTableIngresosContabilidad .ingresos-observacion,
    #dataTableIngresosContabilidad .ingresos-factura-texto,
    #dataTableIngresosContabilidad .ingresos-type-badge {
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      min-width: 0 !important;
      max-width: 100% !important;
      line-height: 1.25 !important;
    }

    #dataTableIngresosContabilidad .ingresos-info-box {
      width: 100% !important;
      min-width: 0 !important;
    }

    #dataTableIngresosContabilidad .ingresos-money-badge,
    #dataTableIngresosContabilidad .ingresos-status-badge {
      white-space: nowrap !important;
      max-width: 100% !important;
      display: inline-block !important;
    }

    #dataTableIngresosContabilidad td:first-child,
    #dataTableIngresosContabilidad th:first-child,
    #dataTableIngresosContabilidad .acciones-dropdown,
    #dataTableIngresosContabilidad .btn-acciones {
      white-space: nowrap !important;
      overflow-wrap: normal !important;
      word-break: normal !important;
    }

    #dataTableIngresosContabilidad td:nth-child(8),
    #dataTableIngresosContabilidad th:nth-child(8) {
      min-width: 170px !important;
      max-width: 230px !important;
      width: 170px !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
    }

    .ingresos-factura-box {
      display: flex !important;
      align-items: flex-start !important;
      justify-content: flex-start !important;
      gap: .45rem !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      white-space: normal !important;
      overflow: visible !important;
    }

    .ingresos-factura-numero {
      flex: 1 1 auto !important;
      min-width: 0 !important;
      max-width: 100% !important;
      display: inline-flex !important;
      align-items: flex-start !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      line-height: 1.25 !important;
      text-align: left !important;
    }

    .ingresos-factura-numero i {
      flex: 0 0 auto !important;
      margin-top: .12rem !important;
    }
  `));

  document.head.appendChild(style);
}

function limpiarTextoSelectPremium(texto) {
  if (texto == null) return "";

  texto = String(texto);

  texto = texto.replace(/\s+/g, " ").trim();

  if (
    texto === "" ||
    texto.toLowerCase() === "seleccione" ||
    texto.toLowerCase() === "seleccione cuenta" ||
    texto.toLowerCase() === "nothing selected"
  ) {
    return "";
  }

  return texto;
}

function obtenerTextoSelectPremium(selector) {
  var $select = $(selector);
  var texto = "";

  if (!$select.length) {
    return "";
  }

  var $option = $select.find("option:selected");

  if ($option.length) {
    texto = $option.text();
  }

  texto = limpiarTextoSelectPremium(texto);

  if (!texto && $select.hasClass("selectpicker")) {
    var $button = $select.parent(".bootstrap-select").find(".filter-option-inner-inner");

    if ($button.length) {
      texto = limpiarTextoSelectPremium($button.text());
    }
  }

  return texto;
}

function actualizarResumenFooterCuentaIngreso() {
  var cuentaTexto = obtenerTextoSelectPremium("#formIngresosContables #cuenta_ingresos");
  var $box = $("#footerCuentaIngresosResumen");
  var $text = $("#footerCuentaIngresosTexto");

  if (!$box.length || !$text.length) {
    return;
  }

  if (cuentaTexto !== "") {
    $box.removeClass("is-empty");
    $text.html(escapeHtmlIngresos(cuentaTexto));
  } else {
    $box.addClass("is-empty");
    $text.html("Seleccione una cuenta contable");
  }
}

function limpiarResumenFooterCuentaIngreso() {
  var $box = $("#footerCuentaIngresosResumen");
  var $text = $("#footerCuentaIngresosTexto");

  if (!$box.length || !$text.length) {
    return;
  }

  $box.addClass("is-empty");
  $text.html("Seleccione una cuenta contable");
}

function renderIngresoInfo(main, muted, iconClass) {
  var textoMain = escapeHtmlIngresos(main || "");
  var textoMuted = escapeHtmlIngresos(muted || "");

  return '' +
    '<div class="ingresos-info-box">' +
      '<div class="ingresos-info-main">' +
        (iconClass ? '<i class="' + iconClass + ' mr-1"></i>' : '') +
        textoMain +
      '</div>' +
      (textoMuted !== '' ? '<div class="ingresos-info-muted">' + textoMuted + '</div>' : '') +
    '</div>';
}

function renderIngresoChip(value, iconClass) {
  var texto = escapeHtmlIngresos(value || "Sin dato");

  return '' +
    '<span class="ingresos-info-chip">' +
      (iconClass ? '<i class="' + iconClass + ' mr-1"></i>' : '') +
      texto +
    '</span>';
}

function renderIngresoTipo(value) {
  var texto = escapeHtmlIngresos(value || "Otro");
  var normalizado = texto.toLowerCase();
  var clase = "ingresos-type-otro";
  var icono = "fas fa-tag";

  if (normalizado.indexOf("ventas") !== -1) {
    clase = "ingresos-type-ventas";
    icono = "fas fa-cash-register";
  } else if (normalizado.indexOf("manual") !== -1) {
    clase = "ingresos-type-manual";
    icono = "fas fa-keyboard";
  }

  return '' +
    '<span class="ingresos-type-badge ' + clase + '">' +
      '<i class="' + icono + '"></i>' +
      texto +
    '</span>';
}

function moneyRenderIngreso(data, type, row, meta) {
  var n = toNumber(data);

  if (type !== "display") {
    return n;
  }

  var clase = "ingresos-money-neutral";

  if (meta && meta.col === 8) {
    clase = "ingresos-money-subtotal";
  } else if (meta && meta.col === 9) {
    clase = "ingresos-money-impuesto";
  } else if (meta && meta.col === 10) {
    clase = "ingresos-money-descuento";
  } else if (meta && meta.col === 11) {
    clase = "ingresos-money-total";
  }

  return '<span class="ingresos-money-badge ' + clase + '">L ' + formatMoney(n) + '</span>';
}

function renderEstadoIngreso(data, type) {
  if (type !== "display") {
    return data;
  }

  var ok = parseInt(data, 10) === 1;
  var icon = ok ? "fas fa-check-circle" : "fas fa-times-circle";
  var cls = ok ? "ingresos-status-active" : "ingresos-status-inactive";
  var text = ok ? "Activo" : "Inactivo";

  return '' +
    '<span class="ingresos-status-badge ' + cls + '">' +
      '<i class="' + icon + '"></i>' +
      text +
    '</span>';
}

function actualizarCardsIngresosDesdeData(json) {
  var registros = 0;
  var subtotal = 0;
  var impuesto = 0;
  var total = 0;

  if (json && json.data && json.data.length > 0) {
    registros = json.data.length;

    json.data.forEach(function (item) {
      subtotal += toNumber(item.subtotal_raw != null ? item.subtotal_raw : item.subtotal);
      impuesto += toNumber(item.impuesto_raw != null ? item.impuesto_raw : item.impuesto);
      total += toNumber(item.total_raw != null ? item.total_raw : item.total);
    });
  }

  $("#ingresos-card-registros").html(registros);
  $("#ingresos-card-subtotal").html("L " + formatMoney(subtotal));
  $("#ingresos-card-impuesto").html("L " + formatMoney(impuesto));
  $("#ingresos-card-total").html("L " + formatMoney(total));
}

// Totales del footer
var total_ingreso_footer = function () {
  var fechai = $("#formMainIngresosContabilidad #fechai").val();
  var fechaf = $("#formMainIngresosContabilidad #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalIngresoFooter.php',
    type: "POST",
    data: {
      "fechai": fechai,
      "fechaf": fechaf
    }
  })
  .done(function (data) {
    try {
      data = typeof data === "string" ? JSON.parse(data || "{}") : data;

      $("#total-footer-ingreso").html('L ' + formatMoney(data.total));
      $("#subtotal-i").html('L ' + formatMoney(data.subtotal));
      $("#impuesto-i").html('L ' + formatMoney(data.impuesto));
      $("#descuento-i").html('L ' + formatMoney(data.descuento));
      $("#nc-i").html('L ' + formatMoney(data.nc));
    } catch (e) {
      console.error("Error al procesar totales del footer:", e, data);
    }
  })
  .fail(function () {
    console.log("Error al cargar totales del footer");
  });
};

// =========================================================
// IZZY 6.0 | INGRESOS - LISTADO DIV / DETALLE / MINIATURA
// =========================================================
var INGRESOS_MOBILE_QUERY = '(max-width: 767.98px)';
var INGRESOS_STORAGE_VISTA = 'izzy.ingresosContabilidad.tipo_vista';

var ingresosUI = {
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

function ingresosEsMovil() {
  return window.matchMedia ? window.matchMedia(INGRESOS_MOBILE_QUERY).matches : $(window).width() <= 767;
}

function ingresosValor(v, fallback) {
  if (v === null || v === undefined || String(v).trim() === '') return fallback === undefined ? 'Sin dato' : fallback;
  return String(v).trim();
}

function ingresosEstadoBadge(estado) {
  return parseInt(estado,10) === 1
    ? '<span class="ingresos-status-badge ingresos-status-active"><i class="fas fa-check-circle"></i> Activo</span>'
    : '<span class="ingresos-status-badge ingresos-status-inactive"><i class="fas fa-times-circle"></i> Inactivo</span>';
}

function ingresosTipoBadge(tipo) {
  var txt = ingresosValor(tipo,'Otro');
  var n = txt.toLowerCase();
  var cls='ingresos-type-otro', icon='fas fa-tag';
  if(n.indexOf('ventas')!==-1){cls='ingresos-type-ventas';icon='fas fa-cash-register';}
  else if(n.indexOf('manual')!==-1){cls='ingresos-type-manual';icon='fas fa-keyboard';}
  return '<span class="ingresos-type-badge '+cls+'"><i class="'+icon+'"></i>'+escapeHtmlIngresos(txt)+'</span>';
}

function ingresosAcciones(row,index) {
  var activo=parseInt(row.estado,10)===1;
  var reversar = activo
    ? '<button type="button" class="dropdown-item accion-item accion-anular table_cancelar anular_ingreso js-ingreso-reversar" data-index="'+index+'"><span class="accion-icon accion-icon-danger"><i class="fas fa-ban"></i></span><span class="accion-label">Reversar</span></button>'
    : '<button type="button" class="dropdown-item accion-item accion-anulado" disabled><span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span><span class="accion-label">Ingreso inactivo</span></button>';
  return '<div class="dropdown acciones-dropdown">'+
    '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog"></i><span>Acciones</span></button>'+
    '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
      '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar js-ingreso-editar" data-index="'+index+'"><span class="accion-icon accion-icon-primary"><i class="fas fa-edit"></i></span><span class="accion-label">Editar</span></button>'+
      '<button type="button" class="dropdown-item accion-item accion-imprimir table_reportes print_gastos js-ingreso-reporte" data-index="'+index+'"><span class="accion-icon accion-icon-success"><i class="fas fa-file-download"></i></span><span class="accion-label">Reporte</span></button>'+
      reversar+
    '</div></div>';
}

function ingresosConfigurarPanel(btn,contenido,key) {
  var visible=true;
  try { var s=localStorage.getItem(key); if(s!==null) visible=s==='1'; } catch(e){}
  function sync(){
    $(contenido).toggle(visible);
    $(btn).attr('aria-expanded',visible?'true':'false');
    $(btn).find('span').text(visible?'Ocultar':'Mostrar');
    $(btn).find('i').toggleClass('fa-chevron-up',visible).toggleClass('fa-chevron-down',!visible);
  }
  sync();
  $(btn).off('click.ingresosPanel').on('click.ingresosPanel',function(){
    visible=!visible; $(contenido).stop(true,true)[visible?'slideDown':'slideUp'](160); sync();
    try{localStorage.setItem(key,visible?'1':'0');}catch(e){}
  });
}

function ingresosSincronizarPageSize(){
  var mini=ingresosUI.view==='miniatura';
  var opts=mini?[6,12,18,30]:[10,25,50,100];
  var pref=mini?ingresosUI.pageSizeMiniatura:ingresosUI.pageSizeDetalle;
  if(opts.indexOf(pref)===-1) pref=opts[0];
  var $s=$('#ingresosPageSize').empty();
  opts.forEach(function(n){$s.append($('<option></option>').val(n).text(n));});
  ingresosUI.pageSize=pref; $s.val(String(pref));
}

function ingresosSincronizarVista(){
  var mobile=ingresosEsMovil();
  $('.ingresos-view-btn[data-view="detalle"]').toggleClass('d-none',mobile).prop('disabled',mobile).attr('aria-hidden',mobile?'true':'false');
  $('.ingresos-view-btn').removeClass('active').attr('aria-pressed','false');
  $('.ingresos-view-btn[data-view="'+ingresosUI.view+'"]').addClass('active').attr('aria-pressed','true');
}

function ingresosFiltrar(){
  var q=$.trim(ingresosUI.search||'').toLowerCase();
  ingresosUI.filtered=!q?ingresosUI.rows.slice():ingresosUI.rows.filter(function(r){
    return [r.fecha_registro,r.tipo_ingreso,r.ingresos_id,r.fecha,r.nombre,r.cliente,r.factura,r.subtotal,r.impuesto,r.descuento,r.total,r.observacion,parseInt(r.estado,10)===1?'activo':'inactivo']
      .map(function(v){return ingresosValor(v,'').toLowerCase();}).join(' ').indexOf(q)!==-1;
  });
  ingresosActualizarResumen();
}

function ingresosActualizarResumen(){
  var subtotal=0, impuesto=0, descuento=0, total=0;
  ingresosUI.filtered.forEach(function(r){
    subtotal+=toNumber(r.subtotal_raw!=null?r.subtotal_raw:r.subtotal);
    impuesto+=toNumber(r.impuesto_raw!=null?r.impuesto_raw:r.impuesto);
    descuento+=toNumber(r.descuento_raw!=null?r.descuento_raw:r.descuento);
    total+=toNumber(r.total_raw!=null?r.total_raw:r.total);
  });
  $('#ingresos-card-registros').text(ingresosUI.filtered.length);
  $('#ingresos-card-subtotal').text('L '+formatMoney(subtotal));
  $('#ingresos-card-impuesto').text('L '+formatMoney(impuesto));
  $('#ingresos-card-total').text('L '+formatMoney(total));
  $('#ingresosTotalSubtotal').text('L '+formatMoney(subtotal));
  $('#ingresosTotalImpuesto').text('L '+formatMoney(impuesto));
  $('#ingresosTotalDescuento').text('L '+formatMoney(descuento));
  $('#ingresosTotalGeneral').text('L '+formatMoney(total));
}

function ingresosMoney(value,cls){
  return '<strong class="ingresos-money '+(cls||'')+'">L '+formatMoney(toNumber(value))+'</strong>';
}

function ingresosRenderDetalle(rows,offset){
  var html='<div class="ingresos-detail-header"><div>Acciones</div><div>Ingreso</div><div>Tipo / Pago</div><div>Cliente / Factura</div><div>Subtotal</div><div>Impuesto</div><div>Descuento</div><div>Total</div><div>Estado</div></div>';
  rows.forEach(function(r,i){var idx=offset+i;html+=''+
    '<article class="ingresos-detail-row">'+
      '<div class="ingresos-cell ingresos-actions-cell"><span class="ingresos-cell-label">Acciones</span>'+ingresosAcciones(r,idx)+'</div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Ingreso</span><div class="ingresos-primary"><strong>#'+escapeHtmlIngresos(r.ingresos_id)+'</strong><span>'+escapeHtmlIngresos(ingresosValor(r.fecha_registro,'Sin fecha'))+'</span><small>Factura: '+escapeHtmlIngresos(ingresosValor(r.fecha,'Sin fecha'))+'</small></div></div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Tipo / Pago</span>'+ingresosTipoBadge(r.tipo_ingreso)+'<small class="ingresos-block-muted">'+escapeHtmlIngresos(ingresosValor(r.nombre,'Sin forma de pago'))+'</small></div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Cliente / Factura</span><div class="ingresos-primary"><strong>'+escapeHtmlIngresos(ingresosValor(r.cliente,'Sin cliente'))+'</strong><span>'+escapeHtmlIngresos(ingresosValor(r.factura,'Sin factura'))+'</span><small>'+escapeHtmlIngresos(ingresosValor(r.observacion,'Sin observación'))+'</small></div></div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Subtotal</span>'+ingresosMoney(r.subtotal,'')+'</div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Impuesto</span>'+ingresosMoney(r.impuesto,'ingresos-money-info')+'</div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Descuento</span>'+ingresosMoney(r.descuento,'ingresos-money-warning')+'</div>'+
      '<div class="ingresos-cell"><span class="ingresos-cell-label">Total</span>'+ingresosMoney(r.total,'ingresos-money-success')+'</div>'+
      '<div class="ingresos-cell ingresos-center"><span class="ingresos-cell-label">Estado</span>'+ingresosEstadoBadge(r.estado)+'</div>'+
    '</article>';});
  return html;
}

function ingresosRenderMiniatura(rows,offset){
  var html='<div class="ingresos-mini-grid">';
  rows.forEach(function(r,i){var idx=offset+i;html+=''+
    '<article class="ingresos-mini-card">'+
      '<div class="ingresos-mini-topline"></div>'+
      '<div class="ingresos-mini-header"><div class="ingresos-mini-title"><h4>Ingreso #'+escapeHtmlIngresos(r.ingresos_id)+'</h4><span>'+escapeHtmlIngresos(ingresosValor(r.fecha_registro,'Sin fecha'))+'</span></div>'+ingresosEstadoBadge(r.estado)+'</div>'+
      '<div class="ingresos-mini-meta">'+ingresosTipoBadge(r.tipo_ingreso)+'<span><i class="fas fa-wallet mr-1"></i>'+escapeHtmlIngresos(ingresosValor(r.nombre,'Sin forma de pago'))+'</span></div>'+
      '<div class="ingresos-mini-body">'+
        '<div class="ingresos-mini-field"><span>Recibí de</span><strong>'+escapeHtmlIngresos(ingresosValor(r.cliente,'Sin cliente'))+'</strong></div>'+
        '<div class="ingresos-mini-field"><span>Factura</span><strong>'+escapeHtmlIngresos(ingresosValor(r.factura,'Sin factura'))+'</strong></div>'+
        '<div class="ingresos-mini-field"><span>Subtotal</span><strong>L '+formatMoney(toNumber(r.subtotal))+'</strong></div>'+
        '<div class="ingresos-mini-field"><span>Impuesto</span><strong>L '+formatMoney(toNumber(r.impuesto))+'</strong></div>'+
        '<div class="ingresos-mini-field"><span>Descuento</span><strong>L '+formatMoney(toNumber(r.descuento))+'</strong></div>'+
        '<div class="ingresos-mini-field"><span>Total</span><strong class="text-success">L '+formatMoney(toNumber(r.total))+'</strong></div>'+
        '<div class="ingresos-mini-field ingresos-mini-field-full"><span>Observación</span><strong>'+escapeHtmlIngresos(ingresosValor(r.observacion,'Sin observación'))+'</strong></div>'+
      '</div><div class="ingresos-mini-footer">'+ingresosAcciones(r,idx)+'</div></article>';});
  return html+'</div>';
}

function ingresosRenderPaginacion(totalPages){
  var c=ingresosUI.page,html='';
  function b(label,page,disabled,active,icon){return '<button type="button" class="ingresos-page-btn'+(active?' active':'')+'" data-page="'+page+'"'+(disabled?' disabled':'')+'>'+(icon?'<i class="'+icon+' mr-1"></i>':'')+label+'</button>';}
  html+=b('Inicio',1,c===1,false,'fas fa-angle-double-left');html+=b('Anterior',c-1,c===1,false,'fas fa-angle-left');
  var from=Math.max(1,c-2),to=Math.min(totalPages,from+4);from=Math.max(1,to-4);for(var p=from;p<=to;p++)html+=b(String(p),p,false,p===c,'');
  html+=b('Siguiente',c+1,c===totalPages,false,'fas fa-angle-right');html+=b('Final',totalPages,c===totalPages,false,'fas fa-angle-double-right');
  $('#ingresosPaginacion').html(html);
}

function ingresosRender(){
  var rows=ingresosUI.filtered||[];
  if(ingresosUI.loading){$('#ingresosListado').html('<div class="ingresos-state"><i class="fas fa-spinner fa-spin"></i><strong>Cargando ingresos</strong><span>Consultando información...</span></div>');$('#ingresosInfo').text('0 registros');$('#ingresosPaginacion').empty();return;}
  if(!rows.length){$('#ingresosListado').html('<div class="ingresos-state"><i class="fas fa-hand-holding-usd"></i><strong>Sin ingresos</strong><span>No se encontraron registros con los filtros actuales.</span></div>');$('#ingresosInfo').text('0 registros');$('#ingresosPaginacion').empty();return;}
  var pages=Math.max(1,Math.ceil(rows.length/ingresosUI.pageSize));if(ingresosUI.page>pages)ingresosUI.page=pages;
  var offset=(ingresosUI.page-1)*ingresosUI.pageSize,pageRows=rows.slice(offset,offset+ingresosUI.pageSize);
  $('#ingresosListado').removeClass('vista-detalle vista-miniatura').addClass('vista-'+ingresosUI.view).html(ingresosUI.view==='miniatura'?ingresosRenderMiniatura(pageRows,offset):ingresosRenderDetalle(pageRows,offset));
  $('#ingresosInfo').text('Mostrando '+(offset+1)+' a '+Math.min(offset+pageRows.length,rows.length)+' de '+rows.length+' registros');ingresosRenderPaginacion(pages);
  if(typeof getPermisosTipoUsuarioAccesosTable==='function'&&typeof getPrivilegioTipoUsuario==='function')getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
}

var listar_ingresos_contabilidad = function(){
  var estado=$('#formMainIngresosContabilidad #estado_ingresos').val()||1,fechai=$('#formMainIngresosContabilidad #fechai').val(),fechaf=$('#formMainIngresosContabilidad #fechaf').val();
  if(!fechai||!fechaf){showNotify('error','Error','Debe seleccionar un rango de fechas');return;}
  ingresosUI.loading=true;ingresosRender();
  $.ajax({method:'POST',url:'<?php echo SERVERURL;?>core/llenarDataTableIngresosContabilidad.php',dataType:'json',data:{fechai:fechai,fechaf:fechaf,estado:estado}})
    .done(function(json){ingresosUI.rows=json&&Array.isArray(json.data)?json.data:[];ingresosUI.search=$('#buscarIngresosListado').val()||'';ingresosUI.page=1;ingresosUI.loading=false;ingresosFiltrar();ingresosRender();if(!ingresosUI.rows.length)showNotify('warning','Advertencia','No se encontraron registros con los filtros aplicados');})
    .fail(function(xhr){ingresosUI.rows=[];ingresosUI.filtered=[];ingresosUI.loading=false;ingresosActualizarResumen();ingresosRender();showNotify('error','Error','No se pudieron cargar los datos');console.error('Error en AJAX:',xhr.responseText);});
};

function editarIngresoUI(data){
e.preventDefault();
if (!data || !data.ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    var ingresos_id = data.ingresos_id;
    var urlEditar = '<?php echo SERVERURL;?>core/editarIngresos.php';

    $('#formIngresosContables #ingresos_id').val(ingresos_id);

    $.ajax({
      url: "<?php echo SERVERURL; ?>core/getClientes.php",
      type: "POST",
      dataType: "json",
      beforeSend: function () {
        $('#formIngresosContables #recibide_ingresos')
          .html('<option value="">Cargando clientes...</option>')
          .selectpicker('refresh');
      }
    }).done(function (response) {
      const $form = $('#formIngresosContables');
      const $sel  = $form.find('#recibide_ingresos');

      $sel.empty();

      if (response && response.success && response.data && response.data.length > 0) {
        $sel.append('<option value="">Seleccione cliente</option>');

        response.data.forEach(function (c) {
          $sel.append(
            '<option value="' + escapeHtmlIngresos(c.clientes_id) + '" data-subtext="' + escapeHtmlIngresos(c.rtn || 'Sin RTN o Identidad') + '">' +
              escapeHtmlIngresos(c.nombre) +
            '</option>'
          );
        });

        $sel.selectpicker('refresh');
      } else {
        $sel.append('<option value="">No hay clientes disponibles</option>');
        $sel.selectpicker('refresh');
      }

      $.ajax({
        type: 'POST',
        url: urlEditar,
        dataType: 'json',
        data: {
          ingresos_id: ingresos_id
        },
        beforeSend: function () {
          $form.find('#pro_ingresos_contabilidad').val("Cargando...");
        },
        success: function (registro) {
          if (!registro || registro.success !== true || !registro.data) {
            var mensaje = registro && registro.message ? registro.message : "No se pudieron cargar los datos del ingreso.";
            showNotify("error", "Error", mensaje);
            console.error("Respuesta inválida de editarIngresos.php:", registro);
            return;
          }

          var v = registro.data;

          $form.attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarIngresosAjax.php'
          });

          if ($form[0]) {
            $form[0].reset();
          }

          $('#formIngresosContables #ingresos_id').val(ingresos_id);

          $('#reg_ingresosContabilidad').hide();
          $('#edi_ingresosContabilidad').show();
          $('#delete_ingresosContabilidad').hide();

          $form.find('#pro_ingresos_contabilidad').val("Editar");

          var fechaReg = v.fecha || "";
          var $fecha = $form.find('#fecha_ingresos');

          $form.addClass('modo-editar');

          $fecha.removeClass('remembered-highlight')
            .off('change.__remember change')
            .removeAttr('data-remember data-rem-key')
            .val(fechaReg)
            .prop('disabled', true);

          $fecha.closest('.col-md-3').find('.remember-hint').remove();

          $('#modalIngresosContables').one('shown.bs.modal', function () {
            var i = 0;

            (function keep() {
              var $f = $('#formIngresosContables #fecha_ingresos');

              $f.off('change.__remember change')
                .val(fechaReg)
                .attr('value', fechaReg)
                .prop('defaultValue', fechaReg);

              if (++i < 8) {
                setTimeout(keep, 60);
              }

              actualizarResumenFooterCuentaIngreso();
            })();
          });

          $form.find('#factura_ingresos').val(v.factura || "");
          $form.find('#subtotal_ingresos').val(v.subtotal || "0.00");
          $form.find('#isv_ingresos').val(v.impuesto || "0.00");
          $form.find('#descuento_ingresos').val(v.descuento || "0.00");
          $form.find('#nc_ingresos').val(v.nc || "0.00");
          $form.find('#total_ingresos').val(v.total || "0.00");
          $form.find('#observacion_ingresos').val(v.observacion || "");

          $form.find('#cuenta_ingresos').val(v.cuentas_id || "").selectpicker('refresh');
          $form.find('#empresa_ingresos').val(v.empresa_id || "").selectpicker('refresh');

          actualizarResumenFooterCuentaIngreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaIngreso();
          }, 150);

          var clienteId    = (v.clientes_id != null && v.clientes_id !== '') ? String(v.clientes_id) : String(data.clientes_id || data.cliente_id || '');
          var clienteTexto = v.recibide || data.cliente || (clienteId ? ('Cliente #' + clienteId) : 'Cliente');

          if (clienteId) {
            if ($sel.find('option[value="' + clienteId + '"]').length === 0) {
              $sel.append('<option value="' + escapeHtmlIngresos(clienteId) + '">' + escapeHtmlIngresos(clienteTexto) + '</option>');
            }

            $sel.selectpicker('val', clienteId);
          } else {
            $sel.selectpicker('val', '');
          }

          $sel.prop('disabled', true).selectpicker('refresh');

          $form.find('#cuenta_ingresos').prop('disabled', true).selectpicker('refresh');
          $form.find('#empresa_ingresos').prop('disabled', true).selectpicker('refresh');

          actualizarResumenFooterCuentaIngreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaIngreso();
          }, 150);

          $form.find('#subtotal_ingresos, #isv_ingresos, #descuento_ingresos, #nc_ingresos, #total_ingresos').prop('disabled', true);

          $form.find('#buscar_cuenta_ingresos, #buscar_empresa_ingresos').hide();

          calcularTotalIngreso();

          $('#modalIngresosContables').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
          });
        },
        error: function (xhr) {
          console.error('Error al cargar datos del ingreso:', xhr.responseText);
          showNotify("error", "Error", "No se pudieron cargar los datos del ingreso. Revise la consola.");
        }
      });
    }).fail(function (xhr) {
      console.error('Error al cargar clientes:', xhr.responseText);

      $('#formIngresosContables #recibide_ingresos')
        .html('<option value="">Error al cargar clientes</option>')
        .selectpicker('refresh');

      showNotify("error", "Error", "No se pudieron cargar los clientes");
    });
}

function reversarIngresoUI(data,button){
e.preventDefault();
  var $btn = $(button);
  var rowData = data;
if (!rowData) {
      showNotify("error", "Error", "No se pudo obtener la fila seleccionada.");
      return;
    }

    const ingresos_id = rowData.ingresos_id;

    if (!ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    const content = document.createElement("div");
    content.innerHTML = `
      <p style="margin:0 0 6px 0;">
        El ingreso <b>quedará activo</b>.
      </p>
      <p style="margin:0 0 6px 0;">
        Se registrará un <b>egreso de reversión</b> por el mismo valor del ingreso.
      </p>
      <p style="margin:0;">
        También se registrará el <b>movimiento de cuenta</b> correspondiente.
      </p>
    `;

    swal({
      title: "¿Reversar ingreso?",
      content: content,
      icon: "warning",
      buttons: {
        cancel: {
          text: "Cancelar",
          visible: true
        },
        confirm: {
          text: "Sí, reversar"
        }
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then(function (ok) {
      if (!ok) return;

      $.ajax({
        url: "<?php echo SERVERURL;?>ajax/cancelIngresoContabilidadAjax.php",
        type: "POST",
        dataType: "html",
        data: {
          ingresos_id: ingresos_id,
          proveedor_anulacion_id: 1
        },
        beforeSend: function () {
          $btn.prop("disabled", true);
        },
        success: function (response) {
          console.log("Respuesta reversión ingreso:", response);

          var respuestaTexto = typeof response === "string" ? response.toLowerCase() : "";

          if (
            respuestaTexto.indexOf("fatal error") !== -1 ||
            respuestaTexto.indexOf("parse error") !== -1 ||
            respuestaTexto.indexOf("warning:") !== -1 ||
            respuestaTexto.indexOf("notice:") !== -1 ||
            respuestaTexto.indexOf("undefined index") !== -1 ||
            respuestaTexto.indexOf("uncaught") !== -1 ||
            respuestaTexto.indexOf("no se pudo registrar la reversión") !== -1 ||
            respuestaTexto.indexOf("no se pudo reversar") !== -1 ||
            respuestaTexto.indexOf("no se recibió el ingreso") !== -1 ||
            respuestaTexto.indexOf("no se encontró el ingreso") !== -1
          ) {
            showNotify("error", "Error", "No se pudo reversar el ingreso. Revise la consola o el log del servidor.");
            console.error("Error devuelto por PHP:", response);
            return;
          }

          try {
            if (typeof response === "string" && response.trim() !== "") {
              $("body").append(response);
            }
          } catch (e) {
            console.warn("No se pudo ejecutar la respuesta del servidor:", e);
          }

          listar_ingresos_contabilidad();
          total_ingreso_footer();
        },
        error: function (xhr) {
          showNotify("error", "Error", "No se pudo reversar el ingreso: " + xhr.statusText);
          console.error("Error al reversar ingreso:", xhr.responseText);
        },
        complete: function () {
          $btn.prop("disabled", false);
        }
      });
    });
}

function reporteIngresoUI(data){
e.preventDefault();
if (!data || !data.ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    printIngresos(data.ingresos_id);
}

function ingresosExcelEscape(v){return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function ingresosExcelCol(i){var n='';while(i>=0){n=String.fromCharCode((i%26)+65)+n;i=Math.floor(i/26)-1;}return n;}
function ingresosExcelCell(ref,v,style){return '<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+ingresosExcelEscape(v)+'</t></is></c>';}

function ingresosGenerarExcel(){
  var rows=ingresosUI.filtered||[];if(!rows.length){showNotify('warning','Sin información','No hay ingresos para exportar.');return;}if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','JSZip no está disponible.');return;}
  var subtotal=0,impuesto=0,descuento=0,total=0;rows.forEach(function(r){subtotal+=toNumber(r.subtotal_raw!=null?r.subtotal_raw:r.subtotal);impuesto+=toNumber(r.impuesto_raw!=null?r.impuesto_raw:r.impuesto);descuento+=toNumber(r.descuento_raw!=null?r.descuento_raw:r.descuento);total+=toNumber(r.total_raw!=null?r.total_raw:r.total);});
  var headers=['Fecha Registro','Tipo','Ingreso #','Fecha Factura','Forma de Pago','Recibí de','Número Factura','Subtotal','Impuesto','Descuento','Total','Observación','Estado'],sr=[];
  sr.push('<row r="1" ht="30" customHeight="1">'+ingresosExcelCell('A1','IZZY • REPORTE DE INGRESOS',1)+'</row>');
  sr.push('<row r="2">'+ingresosExcelCell('A2','Período: '+$('#fechai').val()+' a '+$('#fechaf').val()+' • Registros: '+rows.length,2)+'</row>');
  sr.push('<row r="3">'+ingresosExcelCell('A3','Subtotal: L '+formatMoney(subtotal)+' | Impuesto: L '+formatMoney(impuesto)+' | Descuento: L '+formatMoney(descuento)+' | Total: L '+formatMoney(total),2)+'</row>');
  sr.push('<row r="5" ht="26" customHeight="1">'+headers.map(function(h,i){return ingresosExcelCell(ingresosExcelCol(i)+'5',h,3);}).join('')+'</row>');
  rows.forEach(function(r,i){var rr=6+i,vals=[r.fecha_registro,r.tipo_ingreso,r.ingresos_id,r.fecha,r.nombre,r.cliente,r.factura,'L '+formatMoney(toNumber(r.subtotal)),'L '+formatMoney(toNumber(r.impuesto)),'L '+formatMoney(toNumber(r.descuento)),'L '+formatMoney(toNumber(r.total)),r.observacion,parseInt(r.estado,10)===1?'Activo':'Inactivo'];sr.push('<row r="'+rr+'">'+vals.map(function(v,c){return ingresosExcelCell(ingresosExcelCol(c)+rr,v,4);}).join('')+'</row>');});
  var tr=6+rows.length;sr.push('<row r="'+tr+'">'+ingresosExcelCell('A'+tr,'TOTALES',5)+ingresosExcelCell('H'+tr,'L '+formatMoney(subtotal),5)+ingresosExcelCell('I'+tr,'L '+formatMoney(impuesto),5)+ingresosExcelCell('J'+tr,'L '+formatMoney(descuento),5)+ingresosExcelCell('K'+tr,'L '+formatMoney(total),5)+'</row>');
  var sheet='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:M'+tr+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="19" customWidth="1"/><col min="2" max="7" width="18" customWidth="1"/><col min="8" max="11" width="15" customWidth="1"/><col min="12" max="12" width="30" customWidth="1"/><col min="13" max="13" width="13" customWidth="1"/></cols><sheetData>'+sr.join('')+'</sheetData><autoFilter ref="A5:M'+(5+rows.length)+'"/><mergeCells count="3"><mergeCell ref="A1:M1"/><mergeCell ref="A2:M2"/><mergeCell ref="A3:M3"/></mergeCells></worksheet>';
  var styles='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="5"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF1F7"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="6"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
  var wb='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Ingresos" sheetId="1" r:id="rId1"/></sheets></workbook>';
  var wbr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
  var rr='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
  var ct='<'+'?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
  var zip=new JSZip();zip.file('[Content_Types].xml',ct);zip.folder('_rels').file('.rels',rr);zip.folder('xl').file('workbook.xml',wb);zip.folder('xl').file('styles.xml',styles);zip.folder('xl').folder('_rels').file('workbook.xml.rels',wbr);zip.folder('xl').folder('worksheets').file('sheet1.xml',sheet);
  var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'},promise=typeof zip.generateAsync==='function'?zip.generateAsync(opts):Promise.resolve(zip.generate(opts));
  promise.then(function(blob){var url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='Reporte_Ingresos.xlsx';document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(url);},1000);}).catch(function(e){console.error(e);showNotify('error','Error','No se pudo generar el Excel.');});
}

function ingresosObtenerLogoPdf(callback){
  if(typeof imagen==='string'&&imagen.indexOf('data:image/')===0){callback(imagen);return;}
  $.ajax({type:'GET',url:'<?php echo SERVERURL;?>core/get_image.php',dataType:'text',timeout:15000}).done(function(url){url=$.trim(url||'');if(!url){callback(null);return;}var img=new Image();img.crossOrigin='Anonymous';img.onload=function(){try{var c=document.createElement('canvas');c.width=img.naturalWidth||img.width;c.height=img.naturalHeight||img.height;c.getContext('2d').drawImage(img,0,0);imagen=c.toDataURL('image/png');callback(imagen);}catch(e){callback(null);}};img.onerror=function(){callback(null);};img.src=url;}).fail(function(){callback(null);});
}

function ingresosGenerarPdf(){
  var rows=ingresosUI.filtered||[];if(!rows.length){showNotify('warning','Sin información','No hay ingresos para mostrar en PDF.');return;}if(typeof pdfMake==='undefined'||typeof abrirModalPdfPublico!=='function'){showNotify('error','PDF no disponible','No están disponibles los componentes del PDF.');return;}
  ingresosObtenerLogoPdf(function(logo){
    var subtotal=0,impuesto=0,descuento=0,total=0;rows.forEach(function(r){subtotal+=toNumber(r.subtotal);impuesto+=toNumber(r.impuesto);descuento+=toNumber(r.descuento);total+=toNumber(r.total);});
    var body=[[{text:'FECHA',style:'th'},{text:'TIPO',style:'th'},{text:'INGRESO #',style:'th'},{text:'FECHA FACT.',style:'th'},{text:'FORMA PAGO',style:'th'},{text:'RECIBÍ DE',style:'th'},{text:'FACTURA',style:'th'},{text:'SUBTOTAL',style:'th'},{text:'IMPUESTO',style:'th'},{text:'DESCUENTO',style:'th'},{text:'TOTAL',style:'th'}]];
    rows.forEach(function(r,i){var fill=i%2===0?'#FFFFFF':'#F7F9FC';body.push([{text:String(r.fecha_registro||''),style:'td',fillColor:fill},{text:String(r.tipo_ingreso||''),style:'td',fillColor:fill},{text:String(r.ingresos_id||''),style:'td',fillColor:fill},{text:String(r.fecha||''),style:'td',fillColor:fill},{text:String(r.nombre||''),style:'td',fillColor:fill},{text:String(r.cliente||''),style:'td',fillColor:fill},{text:String(r.factura||''),style:'td',fillColor:fill},{text:'L '+formatMoney(toNumber(r.subtotal)),style:'tdn',fillColor:fill},{text:'L '+formatMoney(toNumber(r.impuesto)),style:'tdn',fillColor:fill},{text:'L '+formatMoney(toNumber(r.descuento)),style:'tdn',fillColor:fill},{text:'L '+formatMoney(toNumber(r.total)),style:'tdn',fillColor:fill,bold:true,color:'#14804A'}]);});
    body.push([{text:'TOTALES',colSpan:7,style:'totalLabel',fillColor:'#EAF1F7'},{},{},{},{},{},{},{text:'L '+formatMoney(subtotal),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L '+formatMoney(impuesto),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L '+formatMoney(descuento),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L '+formatMoney(total),style:'totalMoneyCurrent',fillColor:'#EAF1F7'}]);
    var logoCell=logo?{table:{widths:['*'],body:[[{image:logo,fit:[74,44],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]}:{text:'IZZY',bold:true,fontSize:18,color:'#17324D',alignment:'center',fillColor:'#FFFFFF',margin:[8,14,8,14]};
    var doc={pageSize:'LEGAL',pageOrientation:'landscape',pageMargins:[28,28,28,34],header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:952,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(page,pages){return{margin:[28,8,28,0],columns:[{text:'IZZY • Reporte de Ingresos',fontSize:7,color:'#7A869A'},{text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[
      {table:{widths:[110,'*',165],body:[[logoCell,{stack:[{text:'REPORTE DE INGRESOS',bold:true,fontSize:16,color:'#FFFFFF'},{text:'Registro contable de ingresos por período',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}],fillColor:'#17324D',margin:[0,10,0,10]},{stack:[{text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},{text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}],fillColor:'#17324D',margin:[0,10,12,10]}]]},layout:'noBorders',margin:[0,0,0,10]},
      {table:{widths:['*'],body:[[{text:'Estado: '+($('#estado_ingresos option:selected').text()||'Todos')+'   |   Período: '+($('#fechai').val()||'')+' a '+($('#fechaf').val()||'')+'   |   Búsqueda: '+($.trim($('#buscarIngresosListado').val())||'Sin búsqueda'),fontSize:7,color:'#52627A',fillColor:'#F7F9FC',margin:[8,6,8,6]}]]},layout:'lightHorizontalLines',margin:[0,0,0,10]},
      {table:{widths:['*','*','*','*'],body:[[{stack:[{text:'REGISTROS',fontSize:6.2,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:12,bold:true,color:'#17324D'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'SUBTOTAL',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L '+formatMoney(subtotal),fontSize:11,bold:true,color:'#17324D'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'IMPUESTO',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L '+formatMoney(impuesto),fontSize:11,bold:true,color:'#0EA5A8'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'TOTAL',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L '+formatMoney(total),fontSize:11,bold:true,color:'#14804A'}],fillColor:'#F7F9FC',margin:[8,7,8,7]}]]},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return .5;},vLineWidth:function(){return .5;}},margin:[0,0,0,10]},
      {table:{headerRows:1,widths:[74,68,56,66,88,112,88,78,76,76,82],body:body},margin:[0,0,0,0],layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 4;},paddingRight:function(){return 4;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}}}
    ],styles:{th:{fontSize:5.9,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},td:{fontSize:6.1,color:'#253858',noWrap:false},tdn:{fontSize:6.1,color:'#253858',alignment:'right',noWrap:false},totalLabel:{fontSize:6.4,bold:true,color:'#17324D'},totalMoney:{fontSize:6.4,bold:true,color:'#17324D',alignment:'right'},totalMoneyCurrent:{fontSize:6.4,bold:true,color:'#14804A',alignment:'right'}}};
    pdfMake.createPdf(doc).getDataUrl(function(url){abrirModalPdfPublico(url,'Reporte de Ingresos','Reporte_Ingresos.pdf');});
  });
}

function inicializarIngresosUI(){
  ingresosConfigurarPanel('#btnToggleFiltrosIngresos','#ingresosFiltrosContenido','izzy.ingresos.filtros.visible');
  ingresosConfigurarPanel('#btnToggleKpisIngresos','#ingresosKpisContenido','izzy.ingresos.kpis.visible');
  var saved='detalle';try{saved=localStorage.getItem(INGRESOS_STORAGE_VISTA)||'detalle';}catch(e){}
  ingresosUI.preferredView=saved==='miniatura'?'miniatura':'detalle';ingresosUI.view=ingresosEsMovil()?'miniatura':ingresosUI.preferredView;ingresosSincronizarPageSize();ingresosSincronizarVista();
  $('#buscarIngresosListado').off('input.ingresosUI').on('input.ingresosUI',function(){ingresosUI.search=this.value||'';ingresosUI.page=1;ingresosFiltrar();ingresosRender();});
  $('#limpiarBuscarIngresosListado').off('click.ingresosUI').on('click.ingresosUI',function(){$('#buscarIngresosListado').val('').focus();ingresosUI.search='';ingresosUI.page=1;ingresosFiltrar();ingresosRender();});
  $('#ingresosPageSize').off('change.ingresosUI').on('change.ingresosUI',function(){var n=parseInt(this.value,10);if(!n)return;ingresosUI.pageSize=n;if(ingresosUI.view==='miniatura')ingresosUI.pageSizeMiniatura=n;else ingresosUI.pageSizeDetalle=n;ingresosUI.page=1;ingresosRender();});
  $('.ingresos-view-btn').off('click.ingresosUI').on('click.ingresosUI',function(){var v=$(this).data('view');ingresosUI.view=ingresosEsMovil()?'miniatura':(v==='miniatura'?'miniatura':'detalle');if(!ingresosEsMovil()){ingresosUI.preferredView=ingresosUI.view;try{localStorage.setItem(INGRESOS_STORAGE_VISTA,ingresosUI.preferredView);}catch(e){}}ingresosUI.page=1;ingresosSincronizarPageSize();ingresosSincronizarVista();ingresosRender();});
  $('#ingresosPaginacion').off('click.ingresosUI','.ingresos-page-btn').on('click.ingresosUI','.ingresos-page-btn',function(){if(this.disabled)return;var p=parseInt($(this).data('page'),10);if(!p)return;ingresosUI.page=p;ingresosRender();});
  $('#ingresosListado').off('click.ingresosEdit','.js-ingreso-editar').on('click.ingresosEdit','.js-ingreso-editar',function(){var r=ingresosUI.filtered[parseInt($(this).data('index'),10)];if(r)editarIngresoUI(r);}).off('click.ingresosReport','.js-ingreso-reporte').on('click.ingresosReport','.js-ingreso-reporte',function(){var r=ingresosUI.filtered[parseInt($(this).data('index'),10)];if(r)reporteIngresoUI(r);}).off('click.ingresosReverse','.js-ingreso-reversar').on('click.ingresosReverse','.js-ingreso-reversar',function(){var r=ingresosUI.filtered[parseInt($(this).data('index'),10)];if(r)reversarIngresoUI(r,this);});
  $('#btnIngresosActualizar').off('click.ingresosUI').on('click.ingresosUI',listar_ingresos_contabilidad);$('#btnIngresosIngresar').off('click.ingresosUI').on('click.ingresosUI',modal_ingresos_contabilidad);$('#btnIngresosExcel').off('click.ingresosUI').on('click.ingresosUI',ingresosGenerarExcel);$('#btnIngresosPdf').off('click.ingresosUI').on('click.ingresosUI',ingresosGenerarPdf);
  $(window).off('resize.ingresosUI orientationchange.ingresosUI').on('resize.ingresosUI orientationchange.ingresosUI',function(){var target=ingresosEsMovil()?'miniatura':ingresosUI.preferredView;if(ingresosUI.view!==target){ingresosUI.view=target;ingresosUI.page=1;ingresosSincronizarPageSize();ingresosSincronizarVista();ingresosRender();}else ingresosSincronizarVista();});
}

// ===== Acciones de la tabla: REVERSAR =====
var anular_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.anular_ingreso");

  $(tbody).on("click", "button.anular_ingreso", function (e) {
    e.preventDefault();

    const $btn = $(this);
    const rowData = table.row($btn.parents("tr")).data();

    if (!rowData) {
      showNotify("error", "Error", "No se pudo obtener la fila seleccionada.");
      return;
    }

    const ingresos_id = rowData.ingresos_id;

    if (!ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    const content = document.createElement("div");
    content.innerHTML = `
      <p style="margin:0 0 6px 0;">
        El ingreso <b>quedará activo</b>.
      </p>
      <p style="margin:0 0 6px 0;">
        Se registrará un <b>egreso de reversión</b> por el mismo valor del ingreso.
      </p>
      <p style="margin:0;">
        También se registrará el <b>movimiento de cuenta</b> correspondiente.
      </p>
    `;

    swal({
      title: "¿Reversar ingreso?",
      content: content,
      icon: "warning",
      buttons: {
        cancel: {
          text: "Cancelar",
          visible: true
        },
        confirm: {
          text: "Sí, reversar"
        }
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then(function (ok) {
      if (!ok) return;

      $.ajax({
        url: "<?php echo SERVERURL;?>ajax/cancelIngresoContabilidadAjax.php",
        type: "POST",
        dataType: "html",
        data: {
          ingresos_id: ingresos_id,
          proveedor_anulacion_id: 1
        },
        beforeSend: function () {
          $btn.prop("disabled", true);
        },
        success: function (response) {
          console.log("Respuesta reversión ingreso:", response);

          var respuestaTexto = typeof response === "string" ? response.toLowerCase() : "";

          if (
            respuestaTexto.indexOf("fatal error") !== -1 ||
            respuestaTexto.indexOf("parse error") !== -1 ||
            respuestaTexto.indexOf("warning:") !== -1 ||
            respuestaTexto.indexOf("notice:") !== -1 ||
            respuestaTexto.indexOf("undefined index") !== -1 ||
            respuestaTexto.indexOf("uncaught") !== -1 ||
            respuestaTexto.indexOf("no se pudo registrar la reversión") !== -1 ||
            respuestaTexto.indexOf("no se pudo reversar") !== -1 ||
            respuestaTexto.indexOf("no se recibió el ingreso") !== -1 ||
            respuestaTexto.indexOf("no se encontró el ingreso") !== -1
          ) {
            showNotify("error", "Error", "No se pudo reversar el ingreso. Revise la consola o el log del servidor.");
            console.error("Error devuelto por PHP:", response);
            return;
          }

          try {
            if (typeof response === "string" && response.trim() !== "") {
              $("body").append(response);
            }
          } catch (e) {
            console.warn("No se pudo ejecutar la respuesta del servidor:", e);
          }

          listar_ingresos_contabilidad();
          total_ingreso_footer();
        },
        error: function (xhr) {
          showNotify("error", "Error", "No se pudo reversar el ingreso: " + xhr.statusText);
          console.error("Error al reversar ingreso:", xhr.responseText);
        },
        complete: function () {
          $btn.prop("disabled", false);
        }
      });
    });
  });
};

// ===== Acciones de la tabla: EDITAR =====
var edit_reporte_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.table_editar");

  $(tbody).on("click", "button.table_editar", function (e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    if (!data || !data.ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    var ingresos_id = data.ingresos_id;
    var urlEditar = '<?php echo SERVERURL;?>core/editarIngresos.php';

    $('#formIngresosContables #ingresos_id').val(ingresos_id);

    $.ajax({
      url: "<?php echo SERVERURL; ?>core/getClientes.php",
      type: "POST",
      dataType: "json",
      beforeSend: function () {
        $('#formIngresosContables #recibide_ingresos')
          .html('<option value="">Cargando clientes...</option>')
          .selectpicker('refresh');
      }
    }).done(function (response) {
      const $form = $('#formIngresosContables');
      const $sel  = $form.find('#recibide_ingresos');

      $sel.empty();

      if (response && response.success && response.data && response.data.length > 0) {
        $sel.append('<option value="">Seleccione cliente</option>');

        response.data.forEach(function (c) {
          $sel.append(
            '<option value="' + escapeHtmlIngresos(c.clientes_id) + '" data-subtext="' + escapeHtmlIngresos(c.rtn || 'Sin RTN o Identidad') + '">' +
              escapeHtmlIngresos(c.nombre) +
            '</option>'
          );
        });

        $sel.selectpicker('refresh');
      } else {
        $sel.append('<option value="">No hay clientes disponibles</option>');
        $sel.selectpicker('refresh');
      }

      $.ajax({
        type: 'POST',
        url: urlEditar,
        dataType: 'json',
        data: {
          ingresos_id: ingresos_id
        },
        beforeSend: function () {
          $form.find('#pro_ingresos_contabilidad').val("Cargando...");
        },
        success: function (registro) {
          if (!registro || registro.success !== true || !registro.data) {
            var mensaje = registro && registro.message ? registro.message : "No se pudieron cargar los datos del ingreso.";
            showNotify("error", "Error", mensaje);
            console.error("Respuesta inválida de editarIngresos.php:", registro);
            return;
          }

          var v = registro.data;

          $form.attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarIngresosAjax.php'
          });

          if ($form[0]) {
            $form[0].reset();
          }

          $('#formIngresosContables #ingresos_id').val(ingresos_id);

          $('#reg_ingresosContabilidad').hide();
          $('#edi_ingresosContabilidad').show();
          $('#delete_ingresosContabilidad').hide();

          $form.find('#pro_ingresos_contabilidad').val("Editar");

          var fechaReg = v.fecha || "";
          var $fecha = $form.find('#fecha_ingresos');

          $form.addClass('modo-editar');

          $fecha.removeClass('remembered-highlight')
            .off('change.__remember change')
            .removeAttr('data-remember data-rem-key')
            .val(fechaReg)
            .prop('disabled', true);

          $fecha.closest('.col-md-3').find('.remember-hint').remove();

          $('#modalIngresosContables').one('shown.bs.modal', function () {
            var i = 0;

            (function keep() {
              var $f = $('#formIngresosContables #fecha_ingresos');

              $f.off('change.__remember change')
                .val(fechaReg)
                .attr('value', fechaReg)
                .prop('defaultValue', fechaReg);

              if (++i < 8) {
                setTimeout(keep, 60);
              }

              actualizarResumenFooterCuentaIngreso();
            })();
          });

          $form.find('#factura_ingresos').val(v.factura || "");
          $form.find('#subtotal_ingresos').val(v.subtotal || "0.00");
          $form.find('#isv_ingresos').val(v.impuesto || "0.00");
          $form.find('#descuento_ingresos').val(v.descuento || "0.00");
          $form.find('#nc_ingresos').val(v.nc || "0.00");
          $form.find('#total_ingresos').val(v.total || "0.00");
          $form.find('#observacion_ingresos').val(v.observacion || "");

          $form.find('#cuenta_ingresos').val(v.cuentas_id || "").selectpicker('refresh');
          $form.find('#empresa_ingresos').val(v.empresa_id || "").selectpicker('refresh');

          actualizarResumenFooterCuentaIngreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaIngreso();
          }, 150);

          var clienteId    = (v.clientes_id != null && v.clientes_id !== '') ? String(v.clientes_id) : String(data.clientes_id || data.cliente_id || '');
          var clienteTexto = v.recibide || data.cliente || (clienteId ? ('Cliente #' + clienteId) : 'Cliente');

          if (clienteId) {
            if ($sel.find('option[value="' + clienteId + '"]').length === 0) {
              $sel.append('<option value="' + escapeHtmlIngresos(clienteId) + '">' + escapeHtmlIngresos(clienteTexto) + '</option>');
            }

            $sel.selectpicker('val', clienteId);
          } else {
            $sel.selectpicker('val', '');
          }

          $sel.prop('disabled', true).selectpicker('refresh');

          $form.find('#cuenta_ingresos').prop('disabled', true).selectpicker('refresh');
          $form.find('#empresa_ingresos').prop('disabled', true).selectpicker('refresh');

          actualizarResumenFooterCuentaIngreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaIngreso();
          }, 150);

          $form.find('#subtotal_ingresos, #isv_ingresos, #descuento_ingresos, #nc_ingresos, #total_ingresos').prop('disabled', true);

          $form.find('#buscar_cuenta_ingresos, #buscar_empresa_ingresos').hide();

          calcularTotalIngreso();

          $('#modalIngresosContables').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
          });
        },
        error: function (xhr) {
          console.error('Error al cargar datos del ingreso:', xhr.responseText);
          showNotify("error", "Error", "No se pudieron cargar los datos del ingreso. Revise la consola.");
        }
      });
    }).fail(function (xhr) {
      console.error('Error al cargar clientes:', xhr.responseText);

      $('#formIngresosContables #recibide_ingresos')
        .html('<option value="">Error al cargar clientes</option>')
        .selectpicker('refresh');

      showNotify("error", "Error", "No se pudieron cargar los clientes");
    });
  });
};

$(document).on('hidden.bs.modal', '#modalIngresosContables', function () {
  $('#formIngresosContables').removeClass('modo-editar');

  $('#formIngresosContables #fecha_ingresos')
    .prop('disabled', false)
    .removeAttr('data-original-fecha');

  limpiarResumenFooterCuentaIngreso();
});

// ===== Acciones de la tabla: REPORTE =====
var view_reporte_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.print_gastos");

  $(tbody).on("click", "button.print_gastos", function (e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    if (!data || !data.ingresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del ingreso.");
      return;
    }

    printIngresos(data.ingresos_id);
  });
};

function printIngresos(ingresos_id) {
  if (!ingresos_id) {
    showNotify("error", "Error", "ID de ingreso inválido.");
    return;
  }

  var url = '<?php echo SERVERURL; ?>core/generaIngresos.php?ingresos_id=' + encodeURIComponent(ingresos_id);

  abrirDocumentoEnModal(url, 'Registro de Ingreso');
}

function modal_ingresos_contabilidad() {
  $('#formIngresosContables').attr({
    'data-form': 'save',
    'action': '<?php echo SERVERURL;?>ajax/addIngresoContabilidadAjax.php'
  });

  var $form = $('#formIngresosContables');

  $form.removeClass('modo-editar');

  if ($form[0]) {
    $form[0].reset();
  }

  $form.find('select.selectpicker').prop('disabled', false).val('').selectpicker('refresh');
  $form.find('input[type="text"], input[type="number"], textarea').prop('disabled', false).val('');

  limpiarResumenFooterCuentaIngreso();

  var $f = $form.find('#fecha_ingresos');

  $f.prop('disabled', false)
    .off('change.__remember')
    .on('change.__remember', function () {
      try {
        localStorage.setItem('ingresos:lastFecha', this.value || '');
      } catch (e) { }
    });

  setTimeout(function () {
    var remembered = '';

    try {
      remembered = localStorage.getItem('ingresos:lastFecha') || '';
    } catch (e) { }

    if (!remembered) {
      var d = new Date();
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var dd = String(d.getDate()).padStart(2, '0');

      remembered = d.getFullYear() + '-' + mm + '-' + dd;
    }

    if ($f.length) {
      $f.val(remembered)
        .prop('defaultValue', remembered)
        .attr('value', remembered)
        .trigger('change');
    }
  }, 0);

  $('#reg_ingresosContabilidad').show();
  $('#edi_ingresosContabilidad').hide();
  $('#delete_ingresosContabilidad').hide();

  $('#formIngresosContables #cuenta_codigo').prop("readonly", false);
  $('#formIngresosContables #cuenta_nombre').prop("readonly", false);
  $('#formIngresosContables #cuentas_activo').prop('disabled', false).prop('checked', false);

  function enablePicker(sel) {
    var $el = $form.find(sel);

    $el.prop('disabled', false).removeAttr('disabled');
    $el.selectpicker('val', '');
    $el.selectpicker('refresh');
  }

  enablePicker('#cuenta_ingresos');
  enablePicker('#empresa_ingresos');
  enablePicker('#recibide_ingresos');

  $('#formIngresosContables #subtotal_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #isv_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #descuento_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #nc_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #total_ingresos').prop('disabled', false).val('0.00');

  $('#formIngresosContables #buscar_cuenta_ingresos').show();
  $('#formIngresosContables #buscar_empresa_ingresos').show();

  $('#formIngresosContables #pro_ingresos_contabilidad').val("Registro");

  calcularTotalIngreso();
  actualizarResumenFooterCuentaIngreso();

  $('#modalIngresosContables').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

function getEmpresaIngresos() {
  $.ajax({
    type: "POST",
    url: '<?php echo SERVERURL;?>core/getEmpresa.php',
    async: true,
    success: function (data) {
      $('#formIngresosContables #empresa_ingresos').html(data).selectpicker('refresh');
    },
    error: function () {
      showNotify("error", "Error", "No se pudieron cargar las empresas");
    }
  });
}

function getCuentaIngresos() {
  $.ajax({
    type: "POST",
    url: '<?php echo SERVERURL;?>core/getCuenta.php',
    async: true,
    success: function (data) {
      $('#formIngresosContables #cuenta_ingresos').html(data).selectpicker('refresh');

      actualizarResumenFooterCuentaIngreso();

      setTimeout(function () {
        actualizarResumenFooterCuentaIngreso();
      }, 150);
    },
    error: function () {
      showNotify("error", "Error", "No se pudieron cargar las cuentas contables");
    }
  });
}

$('#btnNuevoCliente').on('click', function () {
  modal_clientes();
});

function generarDocumentoIngresoAutomatico() {
  var fecha = new Date();

  var year = fecha.getFullYear();
  var month = String(fecha.getMonth() + 1).padStart(2, '0');
  var day = String(fecha.getDate()).padStart(2, '0');
  var hour = String(fecha.getHours()).padStart(2, '0');
  var minute = String(fecha.getMinutes()).padStart(2, '0');
  var second = String(fecha.getSeconds()).padStart(2, '0');

  return 'IN' + year + month + day + hour + minute + second;
}

$(document).off('click', '#btnGenerarFacturaIngresos');

$(document).on('click', '#btnGenerarFacturaIngresos', function (e) {
  e.preventDefault();

  var $input = $('#formIngresosContables #factura_ingresos');
  var valorActual = $.trim($input.val());

  if (valorActual !== '') {
    showNotify('warning', 'Advertencia', 'El campo factura ya tiene un valor. Bórrelo si desea generar uno nuevo.');
    $input.focus().select();
    return false;
  }

  var documento = generarDocumentoIngresoAutomatico();

  $input.val(documento).focus().select();

  showNotify('success', 'Documento generado', 'Se generó el número de documento correctamente.');
});

$(document).on(
  'wheel',
  '#formIngresosContables input[type="number"], #formEgresosContables input[type="number"]',
  function (e) {
    if (document.activeElement === this) {
      e.preventDefault();
      this.blur();
    }
  }
);

$(document).on(
  'keydown',
  '#formIngresosContables input[type="number"], #formEgresosContables input[type="number"]',
  function (e) {
    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
      e.preventDefault();
    }
  }
);
</script>