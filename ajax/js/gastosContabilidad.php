<script>
// ===============================
//  Subida de archivo (PDF) - soporta #btnSelectPdf y el markup viejo
// ===============================
function setupFileUpload() {
  const fileDropArea   = document.getElementById('fileDropArea');
  const fileInput      = document.querySelector('.file-upload-input'); // <input type="file">
  const fileInfo       = document.getElementById('fileInfo');
  const filePreview    = document.getElementById('filePreview');
  const selectFileText = document.querySelector('.select-file-text'); // markup viejo (span clickeable)
  const btnSelectPdf   = document.getElementById('btnSelectPdf');     // NUEVO botón

  if (!fileDropArea || !fileInput || fileInput.dataset.initialized) return;
  fileInput.dataset.initialized = "true";

  let isProcessing = false;

  ['dragenter','dragover','dragleave','drop'].forEach(ev => {
    fileDropArea.addEventListener(ev, preventDefaults, false);
  });

  ['dragenter','dragover'].forEach(ev => {
    fileDropArea.addEventListener(ev, highlight, false);
  });

  ['dragleave','drop'].forEach(ev => {
    fileDropArea.addEventListener(ev, unhighlight, false);
  });

  fileDropArea.addEventListener('drop', handleDrop, false);
  document.addEventListener('paste', handlePaste);

  if (selectFileText) {
    selectFileText.addEventListener('click', function (e) {
      e.stopPropagation();
      fileInput.click();
    });
  }

  if (btnSelectPdf) {
    btnSelectPdf.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      fileInput.click();
    });
  }

  fileInput.addEventListener('change', handleFiles, false);

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function highlight() {
    fileDropArea.classList.add('highlight');
  }

  function unhighlight() {
    fileDropArea.classList.remove('highlight');
  }

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;

    processFiles(files);
  }

  function handlePaste(e) {
    const clipboard = e.clipboardData || e.originalEvent.clipboardData;

    if (!clipboard || !clipboard.items) return;

    const items = clipboard.items;
    let file = null;

    for (let i = 0; i < items.length; i++) {
      if (items[i].kind === 'file' && items[i].type === 'application/pdf') {
        file = items[i].getAsFile();
        break;
      }
    }

    if (file) {
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      processFiles(dataTransfer.files);
    }
  }

  function handleFiles(e) {
    if (isProcessing) return;

    isProcessing = true;
    processFiles(e.target.files);
    isProcessing = false;
  }

  function processFiles(files) {
    if (!files || !files.length) return;

    const file = files[0];

    if (file.type !== 'application/pdf') {
      showNotify('error', 'Error', 'Solo se permiten archivos PDF');
      clearFile();
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      showNotify('error', 'Error', 'El archivo no debe exceder los 5MB');
      clearFile();
      return;
    }

    fileInfo.textContent = `Archivo seleccionado: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;

    filePreview.innerHTML = `
      <div class="pdf-preview">
        <i class="fas fa-file-pdf fa-2x" style="color:#dc3545;"></i>
        <p style="margin:0 8px; flex:1;">${file.name}</p>
        <button type="button" class="btn-remove-pdf" title="Eliminar archivo">
          <i class="fas fa-trash-alt"></i>
        </button>
      </div>`;

    filePreview.style.display = 'flex';
    filePreview.style.alignItems = 'center';

    filePreview.querySelector('.btn-remove-pdf').addEventListener('click', clearFile);
  }

  function clearFile() {
    fileInput.value = '';
    fileInfo.textContent = 'Ningún archivo seleccionado';
    filePreview.innerHTML = '';
    filePreview.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', setupFileUpload);

// ===============================
//  Utils formato dinero
// ===============================
function toNumber(val) {
  if (val == null || val === '') return 0;
  if (typeof val === 'number') return val;

  var normalized = String(val).replace(/,/g, '').trim();
  var match = normalized.match(/-?\d+(?:\.\d+)?/);

  return match ? (parseFloat(match[0]) || 0) : 0;
}

function formatMoney(n) {
  try {
    return Number(n).toLocaleString('es-HN', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  } catch (e) {
    var s = (Number(n) || 0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
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

function escapeHtmlEgresos(value) {
  if (value == null) return "";

  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// =========================================================
// ESTILOS SEGUROS PARA TABLA DE EGRESOS
// ---------------------------------------------------------
// Evita que cualquier texto largo se salga de su columna.
// Aplica wrap general, pero mantiene acciones y montos controlados.
// =========================================================
function inyectarEstilosTablaEgresos() {
  if (document.getElementById('egresos-datatable-wrap-fix-style')) {
    return;
  }

  var style = document.createElement('style');
  style.id = 'egresos-datatable-wrap-fix-style';
  style.type = 'text/css';
  style.appendChild(document.createTextNode(`
    #dataTableGastosContabilidad {
      table-layout: fixed !important;
      width: 100% !important;
    }

    #dataTableGastosContabilidad th,
    #dataTableGastosContabilidad td {
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      vertical-align: middle !important;
    }

    #dataTableGastosContabilidad th *,
    #dataTableGastosContabilidad td * {
      max-width: 100% !important;
      box-sizing: border-box !important;
    }

    #dataTableGastosContabilidad .egresos-info-box,
    #dataTableGastosContabilidad .egresos-info-main,
    #dataTableGastosContabilidad .egresos-info-muted,
    #dataTableGastosContabilidad .egresos-info-chip,
    #dataTableGastosContabilidad .egresos-observacion,
    #dataTableGastosContabilidad .egresos-factura-texto {
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      min-width: 0 !important;
      max-width: 100% !important;
      line-height: 1.25 !important;
    }

    #dataTableGastosContabilidad .egresos-info-box {
      width: 100% !important;
      min-width: 0 !important;
    }

    #dataTableGastosContabilidad .egresos-money-badge,
    #dataTableGastosContabilidad .egresos-status-badge {
      white-space: nowrap !important;
      max-width: 100% !important;
      display: inline-block !important;
    }

    #dataTableGastosContabilidad td:first-child,
    #dataTableGastosContabilidad th:first-child,
    #dataTableGastosContabilidad .acciones-dropdown,
    #dataTableGastosContabilidad .btn-acciones {
      white-space: nowrap !important;
      overflow-wrap: normal !important;
      word-break: normal !important;
    }

    #dataTableGastosContabilidad td:nth-child(8),
    #dataTableGastosContabilidad th:nth-child(8) {
      min-width: 170px !important;
      max-width: 230px !important;
      width: 170px !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
    }

    .egresos-factura-box {
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

    .egresos-factura-numero {
      flex: 1 1 auto !important;
      min-width: 0 !important;
      max-width: calc(100% - 40px) !important;
      display: inline-flex !important;
      align-items: flex-start !important;
      white-space: normal !important;
      overflow-wrap: anywhere !important;
      word-break: break-word !important;
      line-height: 1.25 !important;
      text-align: left !important;
    }

    .egresos-factura-numero i {
      flex: 0 0 auto !important;
      margin-top: .12rem !important;
    }

    .egresos-factura-box .factura-btn {
      flex: 0 0 34px !important;
      width: 34px !important;
      min-width: 34px !important;
      height: 34px !important;
      padding: 0 !important;
      margin: 0 !important;
      align-self: flex-start !important;
      border-radius: .5rem !important;
    }
  `));

  document.head.appendChild(style);
}

// =========================================================
// RESUMEN PREMIUM DE CUENTA EN FOOTER DEL MODAL EGRESOS
// =========================================================
function limpiarTextoSelectPremiumEgresos(texto) {
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

function obtenerTextoSelectPremiumEgresos(selector) {
  var $select = $(selector);
  var texto = "";

  if (!$select.length) {
    return "";
  }

  var $option = $select.find("option:selected");

  if ($option.length) {
    texto = $option.text();
  }

  texto = limpiarTextoSelectPremiumEgresos(texto);

  if (!texto && $select.hasClass("selectpicker")) {
    var $button = $select.parent(".bootstrap-select").find(".filter-option-inner-inner");

    if ($button.length) {
      texto = limpiarTextoSelectPremiumEgresos($button.text());
    }
  }

  return texto;
}

function actualizarResumenFooterCuentaEgreso() {
  var cuentaTexto = obtenerTextoSelectPremiumEgresos("#formEgresosContables #cuenta_egresos");
  var $box = $("#footerCuentaEgresosResumen");
  var $text = $("#footerCuentaEgresosTexto");

  if (!$box.length || !$text.length) {
    return;
  }

  if (cuentaTexto !== "") {
    $box.removeClass("is-empty");
    $text.html(escapeHtmlEgresos(cuentaTexto));
  } else {
    $box.addClass("is-empty");
    $text.html("Seleccione una cuenta contable");
  }
}

function limpiarResumenFooterCuentaEgreso() {
  var $box = $("#footerCuentaEgresosResumen");
  var $text = $("#footerCuentaEgresosTexto");

  if (!$box.length || !$text.length) {
    return;
  }

  $box.addClass("is-empty");
  $text.html("Seleccione una cuenta contable");
}

function renderEgresoInfo(main, muted, iconClass) {
  var textoMain = escapeHtmlEgresos(main || "");
  var textoMuted = escapeHtmlEgresos(muted || "");

  return '' +
    '<div class="egresos-info-box">' +
      '<div class="egresos-info-main">' +
        (iconClass ? '<i class="' + iconClass + ' mr-1"></i>' : '') +
        textoMain +
      '</div>' +
      (textoMuted !== '' ? '<div class="egresos-info-muted">' + textoMuted + '</div>' : '') +
    '</div>';
}

function renderEgresoChip(value, iconClass) {
  var texto = escapeHtmlEgresos(value || "Sin dato");

  return '' +
    '<span class="egresos-info-chip">' +
      (iconClass ? '<i class="' + iconClass + ' mr-1"></i>' : '') +
      texto +
    '</span>';
}

function moneyRenderEgreso(data, type, row, meta) {
  var n = toNumber(data);

  if (type !== "display") {
    return n;
  }

  var clase = "egresos-money-neutral";

  if (meta && meta.col === 8) {
    clase = "egresos-money-subtotal";
  } else if (meta && meta.col === 9) {
    clase = "egresos-money-impuesto";
  } else if (meta && meta.col === 10) {
    clase = "egresos-money-descuento";
  } else if (meta && meta.col === 11) {
    clase = "egresos-money-nc";
  } else if (meta && meta.col === 12) {
    clase = "egresos-money-total";
  }

  return '<span class="egresos-money-badge ' + clase + '">L ' + formatMoney(n) + '</span>';
}

function renderEstadoEgreso(data, type) {
  if (type !== "display") {
    return data;
  }

  var ok = parseInt(data, 10) === 1;
  var icon = ok ? "fas fa-check-circle" : "fas fa-times-circle";
  var cls = ok ? "egresos-status-active" : "egresos-status-inactive";
  var text = ok ? "Activo" : "Inactivo";

  return '' +
    '<span class="egresos-status-badge ' + cls + '">' +
      '<i class="' + icon + '"></i>' +
      text +
    '</span>';
}

function actualizarCardsEgresosDesdeData(json) {
  var registros = 0;
  var subtotal = 0;
  var impuesto = 0;
  var total = 0;

  if (json && json.data && json.data.length > 0) {
    registros = json.data.length;

    json.data.forEach(function(item) {
      subtotal += toNumber(item.subtotal_raw != null ? item.subtotal_raw : item.subtotal);
      impuesto += toNumber(item.isv_raw != null ? item.isv_raw : item.impuesto);
      total += toNumber(item.total_raw != null ? item.total_raw : item.total);
    });
  }

  $("#egresos-card-registros").html(registros);
  $("#egresos-card-subtotal").html("L " + formatMoney(subtotal));
  $("#egresos-card-impuesto").html("L " + formatMoney(impuesto));
  $("#egresos-card-total").html("L " + formatMoney(total));
}

// ===============================
//  Cálculo automático de egresos
// ===============================
function calcularTotalEgreso() {
  var form = "#formEgresosContables ";

  var subtotal  = parseFloat($(form + "#subtotal_egresos").val())  || 0;
  var isv       = parseFloat($(form + "#isv_egresos").val())       || 0;
  var descuento = parseFloat($(form + "#descuento_egresos").val()) || 0;
  var nc        = parseFloat($(form + "#nc_egresos").val())        || 0;

  var total = subtotal + isv - descuento - nc;

  $(form + "#total_egresos").val(total.toFixed(2));
}

function inicializarCalculoEgresos() {
  var camposCalculo = [
    "#subtotal_egresos",
    "#isv_egresos",
    "#descuento_egresos",
    "#nc_egresos"
  ];

  camposCalculo.forEach(function (campo) {
    $("#formEgresosContables " + campo).off("input change blur keyup");

    $("#formEgresosContables " + campo).on("input change", function () {
      calcularTotalEgreso();
    });

    $("#formEgresosContables " + campo).on("blur", function () {
      var valor = parseFloat($(this).val());

      if (!isNaN(valor) && valor < 0) {
        $(this).val(0);
        showNotify("warning", "Advertencia", "Los valores no pueden ser negativos");
      }

      calcularTotalEgreso();
    });
  });
}

// ===============================
//  Ready
// ===============================
$(() => {
  inicializarEgresosUI();
  listar_gastos_contabilidad();
  getEmpresaEgresos();
  getCuentaEgresos();
  getProveedorEgresos();
  getCategoriaGastos();
  inicializarCalculoEgresos();

  $('#formMainGastosContabilidad #search').on('click', function(e) {
    e.preventDefault();
    listar_gastos_contabilidad();
  });

  $('#formMainGastosContabilidad').on('reset', function() {
    var $form = $(this);

    setTimeout(function() {
      $form.find('.selectpicker').val('').selectpicker('refresh');
      $form.find('#estado_egresos').val(1).selectpicker('refresh');
      listar_gastos_contabilidad();
    }, 0);
  });

  $(document).off("changed.bs.select change", "#formEgresosContables #cuenta_egresos");
  $(document).on("changed.bs.select change", "#formEgresosContables #cuenta_egresos", function () {
    actualizarResumenFooterCuentaEgreso();
  });

  $("#modalEgresosContables").on('shown.bs.modal', function () {
    actualizarResumenFooterCuentaEgreso();

    setTimeout(function () {
      actualizarResumenFooterCuentaEgreso();
    }, 150);

    $(this).find('#formEgresosContables #proveedor_egresos').focus();
  });

  $("#modalEgresosContables").on('hidden.bs.modal', function () {
    limpiarResumenFooterCuentaEgreso();
  });
});

// ===============================
//  Totales footer
// ===============================
var total_gastos_footer = function() {
  var fechai = $("#formMainGastosContabilidad #fechai").val();
  var fechaf = $("#formMainGastosContabilidad #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalGastosFooter.php',
    type: 'POST',
    data: {
      fechai: fechai,
      fechaf: fechaf
    }
  })
  .done(function(data) {
    try {
      data = typeof data === 'string' ? JSON.parse(data || "{}") : data;
    } catch (e) {
      console.error("Error al procesar totales de gastos:", e, data);
      data = {};
    }

    $("#total-footer-gastos").html('L ' + formatMoney(data.total));
    $("#subtotal-g").html('L ' + formatMoney(data.subtotal));
    $("#impuesto-g").html('L ' + formatMoney(data.impuesto));
    $("#descuento-g").html('L ' + formatMoney(data.descuento));
    $("#nc-g").html('L ' + formatMoney(data.nc));
  })
  .fail(function() {
    console.log("total gastos error");
  });
};

// ===============================
//  DataTable Gastos
// ===============================
/* =========================================================
   IZZY 6.0 | EGRESOS - LISTADO DIV/CARDS
   ========================================================= */

var EGRESOS_MOBILE_QUERY = '(max-width: 767.98px)';
var EGRESOS_STORAGE_VISTA = 'izzy.egresos.tipo_vista';

var egresosUI = {
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

function egresosEsMovil() {
  return window.matchMedia
    ? window.matchMedia(EGRESOS_MOBILE_QUERY).matches
    : $(window).width() <= 767;
}

function egresosValor(value, fallback) {
  if (value === null || value === undefined || String(value).trim() === '') {
    return fallback === undefined ? 'No registrado' : fallback;
  }
  return String(value).trim();
}

function egresosMoney(value) {
  return 'L ' + formatMoney(toNumber(value));
}

function egresosConfigurarPanel(btn, contenido, key) {
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

  $(btn).off('click.egresosPanel').on('click.egresosPanel', function() {
    visible = !visible;
    $(contenido).stop(true, true)[visible ? 'slideDown' : 'slideUp'](160);
    sync();

    try {
      localStorage.setItem(key, visible ? '1' : '0');
    } catch (e) {}
  });
}

function egresosSincronizarPageSize() {
  var mini = egresosUI.view === 'miniatura';
  var opciones = mini ? [6, 12, 18, 30] : [10, 25, 50, 100];
  var preferido = mini ? egresosUI.pageSizeMiniatura : egresosUI.pageSizeDetalle;

  if (opciones.indexOf(preferido) === -1) preferido = opciones[0];

  var $select = $('#egresosPageSize').empty();
  opciones.forEach(function(n) {
    $select.append($('<option></option>').val(n).text(n));
  });

  egresosUI.pageSize = preferido;
  $select.val(String(preferido));
}

function egresosSincronizarVista() {
  var movil = egresosEsMovil();

  if (movil) {
    egresosUI.view = 'miniatura';
  }

  $('.egresos-view-btn[data-view="detalle"]')
    .toggleClass('d-none', movil)
    .prop('disabled', movil)
    .attr('aria-hidden', movil ? 'true' : 'false');

  $('.egresos-view-btn').removeClass('active').attr('aria-pressed', 'false');
  $('.egresos-view-btn[data-view="' + egresosUI.view + '"]')
    .addClass('active')
    .attr('aria-pressed', 'true');
}

function egresosFiltrar() {
  var q = $.trim(egresosUI.search || '').toLowerCase();

  egresosUI.filtered = !q
    ? egresosUI.rows.slice()
    : egresosUI.rows.filter(function(row) {
        return [
          row.fecha_registro,
          row.egresos_id,
          row.categoria,
          row.fecha,
          row.nombre,
          row.proveedor,
          row.factura,
          row.subtotal,
          row.impuesto,
          row.descuento,
          row.nc,
          row.total,
          row.observacion,
          Number(row.estado) === 1 ? 'activo activa' : 'inactivo anulado anulada'
        ].map(function(v) {
          return egresosValor(v, '').toLowerCase();
        }).join(' ').indexOf(q) !== -1;
      });

  egresosActualizarResumen();
}

function egresosActualizarResumen() {
  var subtotal = 0;
  var impuesto = 0;
  var descuento = 0;
  var nc = 0;
  var total = 0;

  egresosUI.filtered.forEach(function(row) {
    subtotal += toNumber(row.subtotal_raw != null ? row.subtotal_raw : row.subtotal);
    impuesto += toNumber(row.isv_raw != null ? row.isv_raw : row.impuesto);
    descuento += toNumber(row.descuento_raw != null ? row.descuento_raw : row.descuento);
    nc += toNumber(row.nc_raw != null ? row.nc_raw : row.nc);
    total += toNumber(row.total_raw != null ? row.total_raw : row.total);
  });

  $('#egresos-card-registros').text(egresosUI.filtered.length);
  $('#egresos-card-subtotal,#egresosTotalSubtotal').text('L ' + formatMoney(subtotal));
  $('#egresos-card-impuesto,#egresosTotalImpuesto').text('L ' + formatMoney(impuesto));
  $('#egresosTotalDescuento').text('L ' + formatMoney(descuento));
  $('#egresosTotalNC').text('L ' + formatMoney(nc));
  $('#egresos-card-total,#egresosTotalGeneral').text('L ' + formatMoney(total));
}

function egresosEstadoBadge(estado) {
  var activo = Number(estado) === 1;

  return '<span class="egresos-status ' + (activo ? 'egresos-status-activo' : 'egresos-status-inactivo') + '">' +
    '<i class="fas ' + (activo ? 'fa-check-circle' : 'fa-times-circle') + '"></i> ' +
    (activo ? 'Activo' : 'Inactivo') +
  '</span>';
}

function egresosFacturaHtml(row) {
  var numero = escapeHtmlEgresos(egresosValor(row.factura, 'Sin factura'));
  var pdf = '';

  if (row.factura_pdf) {
    pdf = '<a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/' +
      encodeURIComponent(row.factura_pdf) +
      '" target="_blank" class="egresos-pdf-link" title="Ver/Descargar PDF">' +
      '<i class="fas fa-file-pdf"></i></a>';
  }

  return '<div class="egresos-factura-inline"><span>' + numero + '</span>' + pdf + '</div>';
}

function egresosAcciones(row, index) {
  var activo = Number(row.estado) === 1;
  var html = '';

  html += '<button type="button" class="dropdown-item accion-item accion-editar table_editar ocultar js-egreso-editar" data-index="' + index + '">' +
    '<span class="accion-icon accion-icon-primary"><i class="fas fa-edit"></i></span>' +
    '<span class="accion-label">Editar</span></button>';

  html += '<button type="button" class="dropdown-item accion-item accion-imprimir table_reportes ocultar js-egreso-reporte" data-index="' + index + '">' +
    '<span class="accion-icon accion-icon-success"><i class="fas fa-file-download"></i></span>' +
    '<span class="accion-label">Reporte</span></button>';

  if (activo) {
    html += '<button type="button" class="dropdown-item accion-item accion-anular table_cancelar ocultar js-egreso-reversar" data-index="' + index + '">' +
      '<span class="accion-icon accion-icon-danger"><i class="fas fa-ban"></i></span>' +
      '<span class="accion-label">Reversar</span></button>';
  } else {
    html += '<button type="button" class="dropdown-item accion-item accion-anulado" disabled>' +
      '<span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span>' +
      '<span class="accion-label">Gasto anulado</span></button>';
  }

  return '<div class="dropdown acciones-dropdown">' +
    '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
      '<i class="fas fa-cog"></i><span>Acciones</span>' +
    '</button>' +
    '<div class="dropdown-menu dropdown-menu-right acciones-menu">' + html + '</div>' +
  '</div>';
}

function egresosRenderDetalle(rows, offset) {
  var html =
    '<div class="egresos-detail-header">' +
      '<div>Acciones</div>' +
      '<div>Egreso</div>' +
      '<div>Categoría</div>' +
      '<div>Fecha Factura</div>' +
      '<div>Pago / Proveedor</div>' +
      '<div>Factura</div>' +
      '<div>Importes</div>' +
      '<div>Estado</div>' +
    '</div>';

  rows.forEach(function(row, i) {
    var idx = offset + i;

    html +=
      '<article class="egresos-detail-row">' +
        '<div class="egresos-cell egresos-actions-cell">' +
          '<span class="egresos-cell-label">Acciones</span>' +
          egresosAcciones(row, idx) +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Egreso</span>' +
          '<div class="egresos-main-info">' +
            '<span class="egresos-main-icon"><i class="fas fa-file-invoice-dollar"></i></span>' +
            '<div><strong>#' + escapeHtmlEgresos(row.egresos_id) + '</strong>' +
            '<small>' + escapeHtmlEgresos(egresosValor(row.fecha_registro, 'Sin fecha de registro')) + '</small></div>' +
          '</div>' +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Categoría</span>' +
          '<strong>' + escapeHtmlEgresos(egresosValor(row.categoria, 'Sin categoría')) + '</strong>' +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Fecha Factura</span>' +
          '<strong>' + escapeHtmlEgresos(egresosValor(row.fecha, 'Sin fecha')) + '</strong>' +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Pago / Proveedor</span>' +
          '<div class="egresos-stack">' +
            '<strong>' + escapeHtmlEgresos(egresosValor(row.nombre, 'Sin forma de pago')) + '</strong>' +
            '<span>' + escapeHtmlEgresos(egresosValor(row.proveedor, 'Sin proveedor')) + '</span>' +
          '</div>' +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Factura</span>' +
          egresosFacturaHtml(row) +
          '<small class="egresos-observacion-text">' + escapeHtmlEgresos(egresosValor(row.observacion, 'Sin observación')) + '</small>' +
        '</div>' +

        '<div class="egresos-cell">' +
          '<span class="egresos-cell-label">Importes</span>' +
          '<div class="egresos-money-stack">' +
            '<span><b>Subtotal:</b> ' + egresosMoney(row.subtotal) + '</span>' +
            '<span><b>ISV:</b> ' + egresosMoney(row.impuesto) + '</span>' +
            '<span><b>Desc.:</b> ' + egresosMoney(row.descuento) + '</span>' +
            '<span><b>NC:</b> ' + egresosMoney(row.nc) + '</span>' +
            '<strong>Total: ' + egresosMoney(row.total) + '</strong>' +
          '</div>' +
        '</div>' +

        '<div class="egresos-cell egresos-center">' +
          '<span class="egresos-cell-label">Estado</span>' +
          egresosEstadoBadge(row.estado) +
        '</div>' +
      '</article>';
  });

  return html;
}

function egresosRenderMiniatura(rows, offset) {
  var html = '<div class="egresos-mini-grid">';

  rows.forEach(function(row, i) {
    var idx = offset + i;

    html +=
      '<article class="egresos-mini-card">' +
        '<div class="egresos-mini-topline"></div>' +

        '<div class="egresos-mini-header">' +
          '<span class="egresos-mini-icon"><i class="fas fa-file-invoice-dollar"></i></span>' +
          '<div class="egresos-mini-title">' +
            '<h4>Egreso #' + escapeHtmlEgresos(row.egresos_id) + '</h4>' +
            '<span>' + escapeHtmlEgresos(egresosValor(row.categoria, 'Sin categoría')) + '</span>' +
          '</div>' +
          egresosEstadoBadge(row.estado) +
        '</div>' +

        '<div class="egresos-mini-body">' +
          '<div class="egresos-mini-field"><span>Fecha Registro</span><strong>' + escapeHtmlEgresos(egresosValor(row.fecha_registro, 'N/A')) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Fecha Factura</span><strong>' + escapeHtmlEgresos(egresosValor(row.fecha, 'N/A')) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Forma de Pago</span><strong>' + escapeHtmlEgresos(egresosValor(row.nombre, 'No definido')) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Proveedor</span><strong>' + escapeHtmlEgresos(egresosValor(row.proveedor, 'Sin proveedor')) + '</strong></div>' +
          '<div class="egresos-mini-field egresos-mini-field-full"><span>Factura</span><strong>' + egresosFacturaHtml(row) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Subtotal</span><strong>' + egresosMoney(row.subtotal) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Impuesto</span><strong>' + egresosMoney(row.impuesto) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Descuento</span><strong>' + egresosMoney(row.descuento) + '</strong></div>' +
          '<div class="egresos-mini-field"><span>Nota Crédito</span><strong>' + egresosMoney(row.nc) + '</strong></div>' +
          '<div class="egresos-mini-field egresos-mini-field-full egresos-mini-total"><span>Total</span><strong>' + egresosMoney(row.total) + '</strong></div>' +
          '<div class="egresos-mini-field egresos-mini-field-full"><span>Observación</span><strong>' + escapeHtmlEgresos(egresosValor(row.observacion, 'Sin observación')) + '</strong></div>' +
        '</div>' +

        '<div class="egresos-mini-footer">' + egresosAcciones(row, idx) + '</div>' +
      '</article>';
  });

  return html + '</div>';
}

function egresosRenderPaginacion(totalPages) {
  var current = egresosUI.page;
  var html = '';

  function b(label, page, disabled, active, icon) {
    return '<button type="button" class="egresos-page-btn' + (active ? ' active' : '') +
      '" data-page="' + page + '"' + (disabled ? ' disabled' : '') + '>' +
      (icon ? '<i class="' + icon + ' mr-1"></i>' : '') + label +
    '</button>';
  }

  html += b('Inicio', 1, current === 1, false, 'fas fa-angle-double-left');
  html += b('Anterior', current - 1, current === 1, false, 'fas fa-angle-left');

  var from = Math.max(1, current - 2);
  var to = Math.min(totalPages, from + 4);
  from = Math.max(1, to - 4);

  for (var p = from; p <= to; p++) {
    html += b(String(p), p, false, p === current, '');
  }

  html += b('Siguiente', current + 1, current === totalPages, false, 'fas fa-angle-right');
  html += b('Final', totalPages, current === totalPages, false, 'fas fa-angle-double-right');

  $('#egresosPaginacion').html(html);
}

function egresosRender() {
  var rows = egresosUI.filtered || [];

  if (egresosUI.loading) {
    $('#egresosListado').html(
      '<div class="egresos-state"><i class="fas fa-spinner fa-spin"></i>' +
      '<strong>Cargando egresos</strong><span>Consultando información...</span></div>'
    );
    $('#egresosInfo').text('0 registros');
    $('#egresosPaginacion').empty();
    return;
  }

  if (!rows.length) {
    $('#egresosListado').html(
      '<div class="egresos-state"><i class="fas fa-file-invoice-dollar"></i>' +
      '<strong>Sin egresos</strong><span>No se encontraron registros con los filtros actuales.</span></div>'
    );
    $('#egresosInfo').text('0 registros');
    $('#egresosPaginacion').empty();
    return;
  }

  var pages = Math.max(1, Math.ceil(rows.length / egresosUI.pageSize));
  if (egresosUI.page > pages) egresosUI.page = pages;

  var offset = (egresosUI.page - 1) * egresosUI.pageSize;
  var pageRows = rows.slice(offset, offset + egresosUI.pageSize);

  $('#egresosListado')
    .removeClass('vista-detalle vista-miniatura')
    .addClass('vista-' + egresosUI.view)
    .html(
      egresosUI.view === 'miniatura'
        ? egresosRenderMiniatura(pageRows, offset)
        : egresosRenderDetalle(pageRows, offset)
    );

  $('#egresosInfo').text(
    'Mostrando ' + (offset + 1) + ' a ' +
    Math.min(offset + pageRows.length, rows.length) +
    ' de ' + rows.length + ' registros'
  );

  egresosRenderPaginacion(pages);

  if (typeof getPermisosTipoUsuarioAccesosTable === 'function' &&
      typeof getPrivilegioTipoUsuario === 'function') {
    getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
  }

  $('[data-toggle="tooltip"]').tooltip({
    container: 'body',
    placement: 'top'
  });
}

var listar_gastos_contabilidad = function() {
  var estado = $("#formMainGastosContabilidad #estado_egresos").val() || 1;
  var fechai = $("#formMainGastosContabilidad #fechai").val();
  var fechaf = $("#formMainGastosContabilidad #fechaf").val();

  if (!fechai || !fechaf) {
    showNotify("error", "Error", "Debe seleccionar un rango de fechas");
    return;
  }

  egresosUI.loading = true;
  egresosRender();

  $.ajax({
    method: "POST",
    url: "<?php echo SERVERURL;?>core/llenarDataTableEgresosContabilidad.php",
    dataType: "json",
    data: {
      fechai: fechai,
      fechaf: fechaf,
      estado: estado
    }
  }).done(function(json) {
    egresosUI.rows = json && Array.isArray(json.data) ? json.data : [];
    egresosUI.search = $('#buscarEgresosListado').val() || '';
    egresosUI.page = 1;
    egresosUI.loading = false;

    egresosFiltrar();
    egresosRender();
    total_gastos_footer();

    if (!egresosUI.rows.length) {
      showNotify("warning", "Advertencia", "No se encontraron registros con los filtros aplicados");
    }
  }).fail(function(xhr) {
    egresosUI.rows = [];
    egresosUI.filtered = [];
    egresosUI.loading = false;

    egresosActualizarResumen();
    egresosRender();

    showNotify("error", "Error", "No se pudieron cargar los datos de egresos");
    console.error("Error en AJAX egresos:", xhr.responseText);
  });
};

function editar_egreso_ui(data) {
  if (!data || !data.egresos_id) {
    showNotify("error", "Error", "No se pudo obtener el ID del egreso.");
    return;
  }

  if (!data || !data.egresos_id) {
        showNotify("error", "Error", "No se pudo obtener el ID del egreso.");
        return;
      }

      var egresos_id = data.egresos_id;
      var urlEditar = '<?php echo SERVERURL;?>core/editarGastos.php';

      $('#formEgresosContables #egresos_id').val(egresos_id);
      resetPdfUI();

      const $form   = $('#formEgresosContables');
      const $prov   = $form.find('#proveedor_egresos');
      const $cuenta = $form.find('#cuenta_egresos');
      const $emp    = $form.find('#empresa_egresos');
      const $cat    = $form.find('#categoria_gastos');

      $prov.html('<option value="">Cargando proveedores...</option>').selectpicker('refresh');
      $cuenta.html('<option value="">Cargando cuentas...</option>').selectpicker('refresh');
      $emp.html('<option value="">Cargando empresas...</option>').selectpicker('refresh');

      if ($cat.length) {
        $cat.html('<option value="">Cargando categorías...</option>').selectpicker('refresh');
      }

      limpiarResumenFooterCuentaEgreso();

      var reqProv = $.ajax({
        url: "<?php echo SERVERURL; ?>core/getProveedores.php",
        type: "POST",
        dataType: "json"
      });

      var reqCta = $.ajax({
        url: "<?php echo SERVERURL; ?>core/getCuenta.php",
        type: "POST"
      });

      var reqEmp = $.ajax({
        url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
        type: "POST"
      });

      var reqCat = $.ajax({
        url: "<?php echo SERVERURL; ?>core/getCategoriaGastos.php",
        type: "POST"
      });

      $.when(reqProv, reqCta, reqEmp, reqCat).done(function(provRes, ctaRes, empRes, catRes) {
        const provJSON = provRes[0];

        $prov.empty();

        if (provJSON && provJSON.success && Array.isArray(provJSON.data) && provJSON.data.length) {
          $prov.append('<option value="">Seleccione proveedor</option>');

          provJSON.data.forEach(function(p) {
            $prov.append(
              '<option value="' + escapeHtmlEgresos(p.proveedores_id) + '" data-subtext="' + escapeHtmlEgresos(p.rtn || 'Sin RTN o Identidad') + '">' +
                escapeHtmlEgresos(p.nombre) +
              '</option>'
            );
          });
        } else {
          $prov.append('<option value="">No hay proveedores disponibles</option>');
        }

        $prov.selectpicker('refresh');

        $cuenta.html(ctaRes[0] || '').selectpicker('refresh');
        $emp.html(empRes[0] || '').selectpicker('refresh');

        if ($cat.length) {
          $cat.html(catRes[0] || '').selectpicker('refresh');
        }

        $.ajax({
          type: 'POST',
          url: urlEditar,
          dataType: 'json',
          data: {
            egresos_id: egresos_id
          },
          beforeSend: function() {
            $form.find('#pro_egresos_contabilidad').val("Cargando...");
          },
          success: function(registro) {
            if (!registro || registro.success !== true || !registro.data) {
              var mensaje = registro && registro.message ? registro.message : "No se pudieron cargar los datos del egreso.";
              showNotify("error", "Error", mensaje);
              console.error("Respuesta inválida de editarGastos.php:", registro);
              return;
            }

            var v = registro.data;

            $form.attr({
              'data-form': 'update',
              'action': '<?php echo SERVERURL;?>ajax/modificarGastosAjax.php'
            });

            if ($form[0]) {
              $form[0].reset();
            }

            $('#formEgresosContables #egresos_id').val(egresos_id);

            $('#reg_egresosContabilidad').hide();
            $('#edi_egresosContabilidad').show();
            $('#delete_egresosContabilidad').hide();

            $form.find('#pro_egresos_contabilidad').val("Editar Egresos");

            var fechaReg = v.fecha || "";
            var $fecha = $form.find('#fecha_egresos');

            $form.addClass('modo-editar');

            $fecha.off()
              .removeAttr('data-remember data-rem-key')
              .val(fechaReg)
              .prop('disabled', true);

            $('#modalEgresosContables').one('shown.bs.modal', function() {
              var i = 0;

              (function keep() {
                var $f = $('#formEgresosContables #fecha_egresos');

                $f.off()
                  .val(fechaReg)
                  .attr('value', fechaReg)
                  .prop('defaultValue', fechaReg);

                if (++i < 8) {
                  setTimeout(keep, 60);
                }

                actualizarResumenFooterCuentaEgreso();
              })();
            });

            $form.find('#factura_egresos').val(v.factura || "");
            $form.find('#subtotal_egresos').val(v.subtotal || "0.00");
            $form.find('#isv_egresos').val(v.impuesto || "0.00");
            $form.find('#descuento_egresos').val(v.descuento || "0.00");
            $form.find('#nc_egresos').val(v.nc || "0.00");
            $form.find('#total_egresos').val(v.total || "0.00");
            $form.find('#observacion_egresos').val(v.observacion || "");

            setSelectpickerByValueOrText($prov,   (v.proveedores_id || data.proveedores_id || data.proveedor_egresos), v.proveedor || data.proveedor);
            setSelectpickerByValueOrText($cuenta, (v.cuentas_id     || data.cuentas_id     || data.cuenta_egresos),    v.nombre_cuenta || data.nombre_cuenta || data.nombre);
            setSelectpickerByValueOrText($emp,    (v.empresa_id     || data.empresa_id     || data.empresa_egresos),   v.nombre_empresa || data.nombre_empresa);

            if ($cat.length) {
              setSelectpickerByValueOrText($cat, (v.categoria_gastos_id || data.categoria_gastos_id || data.categoria_id), v.categoria || data.categoria);
            }

            actualizarResumenFooterCuentaEgreso();

            setTimeout(function () {
              actualizarResumenFooterCuentaEgreso();
            }, 150);

            if (v.factura_pdf && v.factura_pdf !== '') {
              $('#filePreview').html(
                '<div class="existing-file d-flex align-items-center p-2 border rounded bg-light">' +
                  '<i class="fas fa-file-pdf fa-2x text-danger mr-3"></i>' +
                  '<div class="flex-grow-1">' +
                    '<div class="small text-muted">Archivo actual</div>' +
                    '<div class="font-weight-bold">' + escapeHtmlEgresos(v.factura_pdf) + '</div>' +
                  '</div>' +
                  '<div class="btn-group ml-2">' +
                    '<a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/' + encodeURIComponent(v.factura_pdf) + '" target="_blank" class="btn btn-danger btn-sm">' +
                      '<i class="fas fa-file-pdf mr-1"></i> Ver/Descargar PDF' +
                    '</a>' +
                    '<button type="button" class="btn btn-secondary btn-sm" id="removeFile">' +
                      '<i class="fas fa-exchange-alt mr-1"></i> Cambiar archivo' +
                    '</button>' +
                  '</div>' +
                '</div>'
              ).show();

              $('#fileInfo').text('Archivo actual: ' + v.factura_pdf);

              $('#removeFile').off('click').on('click', function() {
                $('#filePreview').hide().html('');
                $('#fileInfo').text('Ningún archivo seleccionado');
                $('#factura_pdf').val('');

                $('<input>').attr({
                  type: 'hidden',
                  name: 'remove_existing_file',
                  value: '1'
                }).appendTo('#formEgresosContables');
              });
            }

            setupFileUpload();

            $prov.prop('disabled', true).selectpicker('refresh');
            $cuenta.prop('disabled', true).selectpicker('refresh');
            $emp.prop('disabled', true).selectpicker('refresh');

            if ($cat.length) {
              $cat.prop('disabled', true).selectpicker('refresh');
            }

            actualizarResumenFooterCuentaEgreso();

            setTimeout(function () {
              actualizarResumenFooterCuentaEgreso();
            }, 150);

            $form.find('#subtotal_egresos, #isv_egresos, #descuento_egresos, #nc_egresos, #total_egresos').prop('disabled', true);
            $form.find('#buscar_cuenta_egresos, #buscar_empresa_egresos').hide();

            $('#modalEgresosContables').modal({
              show: true,
              keyboard: false,
              backdrop: 'static'
            });
          },
          error: function(xhr) {
            console.error('Error al cargar datos del gasto:', xhr.responseText);
            showNotify("error", "Error", "No se pudieron cargar los datos del gasto. Revise la consola.");
          }
        });
      })
      .fail(function(xhr) {
        console.error('Error al cargar catálogos:', xhr.responseText);

        showNotify("error", "Error", "No se pudieron cargar proveedores/cuentas/empresas/categorías");

        $prov.html('<option value="">Error al cargar proveedores</option>').selectpicker('refresh');
        $cuenta.html('<option value="">Error al cargar cuentas</option>').selectpicker('refresh');
        $emp.html('<option value="">Error al cargar empresas</option>').selectpicker('refresh');

        if ($cat.length) {
          $cat.html('<option value="">Error al cargar categorías</option>').selectpicker('refresh');
        }
      });
}

function reversar_egreso_ui(rowData, boton) {
  var $btn = boton ? $(boton) : $();

  if (!rowData) {
    showNotify("error", "Error", "No se pudo obtener el egreso seleccionado.");
    return;
  }

  if (!rowData) {
        showNotify("error", "Error", "No se pudo obtener la fila seleccionada.");
        return;
      }

      const egresos_id = rowData.egresos_id;

      if (!egresos_id) {
        showNotify("error", "Error", "No se pudo obtener el ID del egreso.");
        return;
      }

      const content = document.createElement("div");

      content.innerHTML = `
        <p style="margin:0 0 6px 0;">
          El egreso <b>quedará activo</b>.
        </p>
        <p style="margin:0 0 6px 0;">
          Se registrará un <b>ingreso de reversión</b> por el mismo valor del egreso.
        </p>
        <p style="margin:0;">
          También se registrará el <b>movimiento de cuenta</b> correspondiente.
        </p>`;

      swal({
        title: "¿Reversar egreso?",
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
      }).then(function(ok) {
        if (!ok) return;

        $.ajax({
          url: "<?php echo SERVERURL;?>ajax/cancelEgresoContabilidadAjax.php",
          type: "POST",
          dataType: "html",
          data: {
            egresos_id: egresos_id
          },
          beforeSend: function() {
            $btn.prop("disabled", true);
          },
          success: function(response) {
            console.log("Respuesta reversión egreso:", response);

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
              respuestaTexto.indexOf("no se recibió el egreso") !== -1 ||
              respuestaTexto.indexOf("no se encontró el egreso") !== -1
            ) {
              showNotify("error", "Error", "No se pudo reversar el egreso. Revise la consola o el log del servidor.");
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

            listar_gastos_contabilidad();
            total_gastos_footer();
          },
          error: function(xhr) {
            showNotify("error", "Error", "No se pudo reversar el egreso: " + xhr.statusText);
            console.error("Error al reversar egreso:", xhr.responseText);
          },
          complete: function() {
            $btn.prop("disabled", false);
          }
        });
      });
}

function egresosExcelEscape(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function egresosExcelCol(index) {
  var name = '';

  while (index >= 0) {
    name = String.fromCharCode((index % 26) + 65) + name;
    index = Math.floor(index / 26) - 1;
  }

  return name;
}

function egresosExcelCell(ref, value, style) {
  return '<c r="' + ref + '" s="' + style + '" t="inlineStr"><is><t>' +
    egresosExcelEscape(value) + '</t></is></c>';
}

function egresosGenerarExcel() {
  var rows = egresosUI.filtered || [];

  if (!rows.length) {
    showNotify('warning', 'Sin información', 'No hay egresos para exportar.');
    return;
  }

  if (typeof JSZip === 'undefined') {
    showNotify('error', 'Excel no disponible', 'JSZip no está disponible.');
    return;
  }

  var subtotal = 0, impuesto = 0, descuento = 0, nc = 0, total = 0;

  rows.forEach(function(r) {
    subtotal += toNumber(r.subtotal_raw != null ? r.subtotal_raw : r.subtotal);
    impuesto += toNumber(r.isv_raw != null ? r.isv_raw : r.impuesto);
    descuento += toNumber(r.descuento_raw != null ? r.descuento_raw : r.descuento);
    nc += toNumber(r.nc_raw != null ? r.nc_raw : r.nc);
    total += toNumber(r.total_raw != null ? r.total_raw : r.total);
  });

  var headers = [
    'Fecha Registro','Número','Categoría','Fecha Factura','Forma de Pago',
    'Proveedor','Factura','Subtotal','Impuesto','Descuento','Nota Crédito',
    'Total','Observación','Estado'
  ];

  var sheetRows = [];

  sheetRows.push('<row r="1" ht="30" customHeight="1">' +
    egresosExcelCell('A1', 'IZZY • REPORTE DE EGRESOS', 1) + '</row>');

  sheetRows.push('<row r="2">' +
    egresosExcelCell(
      'A2',
      'Período: ' + $('#fechai').val() + ' a ' + $('#fechaf').val() +
      ' • Registros: ' + rows.length,
      2
    ) + '</row>');

  sheetRows.push('<row r="3">' +
    egresosExcelCell(
      'A3',
      'Subtotal: L ' + formatMoney(subtotal) +
      ' • Impuesto: L ' + formatMoney(impuesto) +
      ' • Descuento: L ' + formatMoney(descuento) +
      ' • Nota Crédito: L ' + formatMoney(nc) +
      ' • Total: L ' + formatMoney(total),
      2
    ) + '</row>');

  sheetRows.push('<row r="5" ht="28" customHeight="1">' +
    headers.map(function(h, i) {
      return egresosExcelCell(egresosExcelCol(i) + '5', h, 3);
    }).join('') +
  '</row>');

  rows.forEach(function(r, i) {
    var rr = 6 + i;
    var vals = [
      r.fecha_registro,
      r.egresos_id,
      r.categoria,
      r.fecha,
      r.nombre,
      r.proveedor,
      r.factura,
      egresosMoney(r.subtotal),
      egresosMoney(r.impuesto),
      egresosMoney(r.descuento),
      egresosMoney(r.nc),
      egresosMoney(r.total),
      r.observacion,
      Number(r.estado) === 1 ? 'Activo' : 'Inactivo'
    ];

    sheetRows.push('<row r="' + rr + '">' +
      vals.map(function(v, c) {
        return egresosExcelCell(egresosExcelCol(c) + rr, v, 4);
      }).join('') +
    '</row>');
  });

  var totalRow = 6 + rows.length;
  var totalVals = [
    'TOTALES','','','','','','',
    'L ' + formatMoney(subtotal),
    'L ' + formatMoney(impuesto),
    'L ' + formatMoney(descuento),
    'L ' + formatMoney(nc),
    'L ' + formatMoney(total),
    '',''
  ];

  sheetRows.push('<row r="' + totalRow + '" ht="24" customHeight="1">' +
    totalVals.map(function(v, c) {
      return egresosExcelCell(egresosExcelCol(c) + totalRow, v, 5);
    }).join('') +
  '</row>');

  var sheetXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
      '<dimension ref="A1:N' + totalRow + '"/>' +
      '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
        '<pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/>' +
      '</sheetView></sheetViews>' +
      '<cols>' +
        '<col min="1" max="1" width="20" customWidth="1"/>' +
        '<col min="2" max="2" width="11" customWidth="1"/>' +
        '<col min="3" max="3" width="22" customWidth="1"/>' +
        '<col min="4" max="4" width="16" customWidth="1"/>' +
        '<col min="5" max="6" width="24" customWidth="1"/>' +
        '<col min="7" max="7" width="22" customWidth="1"/>' +
        '<col min="8" max="12" width="17" customWidth="1"/>' +
        '<col min="13" max="13" width="34" customWidth="1"/>' +
        '<col min="14" max="14" width="13" customWidth="1"/>' +
      '</cols>' +
      '<sheetData>' + sheetRows.join('') + '</sheetData>' +
      '<autoFilter ref="A5:N' + (totalRow - 1) + '"/>' +
      '<mergeCells count="3">' +
        '<mergeCell ref="A1:N1"/>' +
        '<mergeCell ref="A2:N2"/>' +
        '<mergeCell ref="A3:N3"/>' +
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
        '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf>' +
        '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1"/></xf>' +
      '</cellXfs>' +
      '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
    '</styleSheet>';

  var workbookXml =
    '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
      'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
      '<sheets><sheet name="Egresos" sheetId="1" r:id="rId1"/></sheets>' +
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

  var opts = {
    type: 'blob',
    mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    compression: 'DEFLATE'
  };

  var promise = typeof zip.generateAsync === 'function'
    ? zip.generateAsync(opts)
    : (typeof zip.generate === 'function'
      ? Promise.resolve(zip.generate(opts))
      : Promise.reject(new Error('JSZip no soportado')));

  promise.then(function(blob) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');

    a.href = url;
    a.download = 'Reporte_Egresos.xlsx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(function() {
      URL.revokeObjectURL(url);
    }, 1000);
  }).catch(function(error) {
    console.error(error);
    showNotify('error', 'Error', 'No se pudo generar el Excel.');
  });
}

function egresosObtenerLogoPdf(callback) {
  if (typeof imagen === 'string' && imagen.indexOf('data:image/') === 0) {
    callback(imagen);
    return;
  }

  $.ajax({
    type: 'GET',
    url: '<?php echo SERVERURL;?>core/get_image.php',
    dataType: 'text',
    timeout: 15000
  }).done(function(url) {
    url = $.trim(url || '');

    if (!url) {
      callback(null);
      return;
    }

    var img = new Image();
    img.crossOrigin = 'Anonymous';

    img.onload = function() {
      try {
        var canvas = document.createElement('canvas');
        canvas.width = img.naturalWidth || img.width;
        canvas.height = img.naturalHeight || img.height;
        canvas.getContext('2d').drawImage(img, 0, 0);

        imagen = canvas.toDataURL('image/png');
        callback(imagen);
      } catch (e) {
        callback(null);
      }
    };

    img.onerror = function() {
      callback(null);
    };

    img.src = url;
  }).fail(function() {
    callback(null);
  });
}

function egresosGenerarPdf() {
  var rows = egresosUI.filtered || [];

  if (!rows.length) {
    showNotify('warning', 'Sin información', 'No hay egresos para mostrar en PDF.');
    return;
  }

  if (typeof pdfMake === 'undefined' || typeof abrirModalPdfPublico !== 'function') {
    showNotify('error', 'PDF no disponible', 'No están disponibles los componentes del PDF.');
    return;
  }

  var subtotal = 0, impuesto = 0, descuento = 0, nc = 0, total = 0;

  rows.forEach(function(r) {
    subtotal += toNumber(r.subtotal_raw != null ? r.subtotal_raw : r.subtotal);
    impuesto += toNumber(r.isv_raw != null ? r.isv_raw : r.impuesto);
    descuento += toNumber(r.descuento_raw != null ? r.descuento_raw : r.descuento);
    nc += toNumber(r.nc_raw != null ? r.nc_raw : r.nc);
    total += toNumber(r.total_raw != null ? r.total_raw : r.total);
  });

  egresosObtenerLogoPdf(function(logo) {
    var body = [[
      {text:'FECHA REG.',style:'th'},
      {text:'NÚM.',style:'th'},
      {text:'CATEGORÍA',style:'th'},
      {text:'FECHA FACT.',style:'th'},
      {text:'FORMA PAGO',style:'th'},
      {text:'PROVEEDOR',style:'th'},
      {text:'FACTURA',style:'th'},
      {text:'SUBTOTAL',style:'th'},
      {text:'IMPUESTO',style:'th'},
      {text:'DESCUENTO',style:'th'},
      {text:'NOTA CRÉD.',style:'th'},
      {text:'TOTAL',style:'th'}
    ]];

    rows.forEach(function(r, i) {
      var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

      body.push([
        {text:egresosValor(r.fecha_registro,''),style:'td',fillColor:fill},
        {text:String(r.egresos_id || ''),style:'td',fillColor:fill},
        {text:egresosValor(r.categoria,''),style:'td',fillColor:fill},
        {text:egresosValor(r.fecha,''),style:'td',fillColor:fill},
        {text:egresosValor(r.nombre,''),style:'td',fillColor:fill},
        {text:egresosValor(r.proveedor,''),style:'td',fillColor:fill},
        {text:egresosValor(r.factura,''),style:'td',fillColor:fill},
        {text:egresosMoney(r.subtotal),style:'tdn',fillColor:fill},
        {text:egresosMoney(r.impuesto),style:'tdn',fillColor:fill},
        {text:egresosMoney(r.descuento),style:'tdn',fillColor:fill},
        {text:egresosMoney(r.nc),style:'tdn',fillColor:fill},
        {text:egresosMoney(r.total),style:'tdn',fillColor:fill,bold:true,color:'#C9372C'}
      ]);
    });

    body.push([
      {text:'TOTALES',colSpan:7,style:'totalLabel',fillColor:'#EAF1F7'},
      {},{},{},{},{},{},
      {text:'L ' + formatMoney(subtotal),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L ' + formatMoney(impuesto),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L ' + formatMoney(descuento),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L ' + formatMoney(nc),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L ' + formatMoney(total),style:'totalMoneyDanger',fillColor:'#EAF1F7'}
    ]);

    var logoCell = logo
      ? {
          table:{
            widths:['*'],
            body:[[
              {
                image:logo,
                fit:[74,44],
                alignment:'center',
                margin:[7,5,7,5],
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

    var estadoTexto = $('#estado_egresos option:selected').text() || 'Todos';
    var desde = $('#fechai').val() || '';
    var hasta = $('#fechaf').val() || '';
    var busqueda = $.trim($('#buscarEgresosListado').val()) || 'Sin búsqueda';

    var doc = {
      pageSize:'LEGAL',
      pageOrientation:'landscape',
      pageMargins:[28,28,28,34],

      header:function() {
        return {
          margin:[28,12,28,0],
          canvas:[{
            type:'line',
            x1:0,y1:0,x2:952,y2:0,
            lineWidth:2,
            lineColor:'#0EA5A8'
          }]
        };
      },

      footer:function(page,pages) {
        return {
          margin:[28,8,28,0],
          columns:[
            {text:'IZZY • Reporte de Egresos',fontSize:7,color:'#7A869A'},
            {text:'Página ' + page + ' de ' + pages,fontSize:7,color:'#7A869A',alignment:'right'}
          ]
        };
      },

      content:[
        {
          table:{
            widths:[110,'*',175],
            body:[[
              logoCell,
              {
                stack:[
                  {text:'REPORTE DE EGRESOS',bold:true,fontSize:16,color:'#FFFFFF'},
                  {text:'Registro contable de egresos por período',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}
                ],
                fillColor:'#17324D',
                margin:[0,10,0,10]
              },
              {
                stack:[
                  {text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},
                  {text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},
                  {text:rows.length + ' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}
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
          table:{
            widths:['*'],
            body:[[
              {
                text:'Estado: ' + estadoTexto +
                     '   |   Período: ' + desde + ' a ' + hasta +
                     '   |   Búsqueda: ' + busqueda,
                fontSize:7,
                color:'#52627A',
                fillColor:'#F7F9FC',
                margin:[8,6,8,6]
              }
            ]]
          },
          layout:'lightHorizontalLines',
          margin:[0,0,0,10]
        },

        {
          table:{
            widths:['*','*','*','*'],
            body:[[
              {
                stack:[
                  {text:'REGISTROS',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:String(rows.length),fontSize:12,bold:true,color:'#17324D',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'SUBTOTAL',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L ' + formatMoney(subtotal),fontSize:11,bold:true,color:'#17324D',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'IMPUESTO',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L ' + formatMoney(impuesto),fontSize:11,bold:true,color:'#5949BA',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'TOTAL EGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L ' + formatMoney(total),fontSize:11,bold:true,color:'#C9372C',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              }
            ]]
          },
          layout:{
            hLineColor:function(){return '#DDE3EA';},
            vLineColor:function(){return '#DDE3EA';},
            hLineWidth:function(){return .5;},
            vLineWidth:function(){return .5;}
          },
          margin:[0,0,0,10]
        },

        {
          table:{
            headerRows:1,
            widths:[78,44,72,58,86,104,92,68,64,64,64,62],
            body:body
          },
          margin:[0,0,0,0],
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
          fontSize:5.6,
          bold:true,
          color:'#FFFFFF',
          fillColor:'#17324D',
          alignment:'center'
        },
        td:{
          fontSize:5.8,
          color:'#253858',
          noWrap:false
        },
        tdn:{
          fontSize:5.8,
          color:'#253858',
          alignment:'right',
          noWrap:false
        },
        totalLabel:{
          fontSize:6.2,
          bold:true,
          color:'#17324D'
        },
        totalMoney:{
          fontSize:6.2,
          bold:true,
          color:'#17324D',
          alignment:'right'
        },
        totalMoneyDanger:{
          fontSize:6.2,
          bold:true,
          color:'#C9372C',
          alignment:'right'
        }
      }
    };

    pdfMake.createPdf(doc).getDataUrl(function(url) {
      abrirModalPdfPublico(
        url,
        'Reporte de Egresos',
        'Reporte_Egresos.pdf'
      );
    });
  });
}

function inicializarEgresosUI() {
  egresosConfigurarPanel(
    '#btnToggleFiltrosEgresos',
    '#egresosFiltrosContenido',
    'izzy.egresos.filtros.visible'
  );

  egresosConfigurarPanel(
    '#btnToggleKpisEgresos',
    '#egresosKpisContenido',
    'izzy.egresos.kpis.visible'
  );

  var saved = 'detalle';

  try {
    saved = localStorage.getItem(EGRESOS_STORAGE_VISTA) || 'detalle';
  } catch (e) {}

  egresosUI.preferredView = saved === 'miniatura' ? 'miniatura' : 'detalle';
  egresosUI.view = egresosEsMovil() ? 'miniatura' : egresosUI.preferredView;

  egresosSincronizarPageSize();
  egresosSincronizarVista();

  $('#buscarEgresosListado')
    .off('input.egresosUI')
    .on('input.egresosUI', function() {
      egresosUI.search = this.value || '';
      egresosUI.page = 1;
      egresosFiltrar();
      egresosRender();
    });

  $('#limpiarBuscarEgresosListado')
    .off('click.egresosUI')
    .on('click.egresosUI', function() {
      $('#buscarEgresosListado').val('').focus();
      egresosUI.search = '';
      egresosUI.page = 1;
      egresosFiltrar();
      egresosRender();
    });

  $('#egresosPageSize')
    .off('change.egresosUI')
    .on('change.egresosUI', function() {
      var n = parseInt(this.value, 10);
      if (!n) return;

      egresosUI.pageSize = n;

      if (egresosUI.view === 'miniatura') {
        egresosUI.pageSizeMiniatura = n;
      } else {
        egresosUI.pageSizeDetalle = n;
      }

      egresosUI.page = 1;
      egresosRender();
    });

  $('.egresos-view-btn')
    .off('click.egresosUI')
    .on('click.egresosUI', function() {
      var vista = $(this).data('view');

      egresosUI.view = egresosEsMovil()
        ? 'miniatura'
        : (vista === 'miniatura' ? 'miniatura' : 'detalle');

      if (!egresosEsMovil()) {
        egresosUI.preferredView = egresosUI.view;

        try {
          localStorage.setItem(EGRESOS_STORAGE_VISTA, egresosUI.preferredView);
        } catch (e) {}
      }

      egresosUI.page = 1;
      egresosSincronizarPageSize();
      egresosSincronizarVista();
      egresosRender();
    });

  $('#egresosPaginacion')
    .off('click.egresosUI', '.egresos-page-btn')
    .on('click.egresosUI', '.egresos-page-btn', function() {
      if (this.disabled) return;

      var page = parseInt($(this).data('page'), 10);
      if (!page) return;

      egresosUI.page = page;
      egresosRender();
    });

  $('#egresosListado')
    .off('click.egresosEditar', '.js-egreso-editar')
    .on('click.egresosEditar', '.js-egreso-editar', function() {
      var row = egresosUI.filtered[parseInt($(this).data('index'), 10)];
      if (row) editar_egreso_ui(row);
    })
    .off('click.egresosReporte', '.js-egreso-reporte')
    .on('click.egresosReporte', '.js-egreso-reporte', function() {
      var row = egresosUI.filtered[parseInt($(this).data('index'), 10)];
      if (row) printGastos(row.egresos_id);
    })
    .off('click.egresosReversar', '.js-egreso-reversar')
    .on('click.egresosReversar', '.js-egreso-reversar', function() {
      var row = egresosUI.filtered[parseInt($(this).data('index'), 10)];
      if (row) reversar_egreso_ui(row, this);
    });

  $('#btnEgresosActualizar').off('click.egresosUI').on('click.egresosUI', listar_gastos_contabilidad);
  $('#btnEgresosIngresar').off('click.egresosUI').on('click.egresosUI', modal_egresos_contabilidad);
  $('#btnEgresosCategorias').off('click.egresosUI').on('click.egresosUI', abrirCategoriasGastosDesdeEgresos);
$('#btnEgresosExcel').off('click.egresosUI').on('click.egresosUI', egresosGenerarExcel);
  $('#btnEgresosPdf').off('click.egresosUI').on('click.egresosUI', egresosGenerarPdf);

  $(window)
    .off('resize.egresosUI orientationchange.egresosUI')
    .on('resize.egresosUI orientationchange.egresosUI', function() {
      var target = egresosEsMovil() ? 'miniatura' : egresosUI.preferredView;

      if (egresosUI.view !== target) {
        egresosUI.view = target;
        egresosUI.page = 1;
        egresosSincronizarPageSize();
        egresosSincronizarVista();
        egresosRender();
      } else {
        egresosSincronizarVista();
      }
    });
}

// ===============================
//  Reset UI PDF
// ===============================
function resetPdfUI() {
  const $preview = $('#filePreview');
  const $info    = $('#fileInfo');
  const $form    = $('#formEgresosContables');
  const fileInput = document.querySelector('.file-upload-input');

  $preview.stop(true, true).hide().empty();
  $info.text('Ningún archivo seleccionado');

  if (fileInput) {
    fileInput.value = '';
  }

  $form.find('input[name="remove_existing_file"]').remove();
}

// ===============================
//  Helper selectpicker
// ===============================
function setSelectpickerByValueOrText($sel, value, text) {
  $sel.selectpicker('refresh');

  var val = (value !== undefined && value !== null) ? String(value).trim() : '';

  if (val && $sel.find('option[value="' + val + '"]').length) {
    $sel.selectpicker('val', val);
    return true;
  }

  if (text) {
    var target = String(text).trim().toLowerCase();

    var $opt = $sel.find('option').filter(function() {
      return $(this).text().trim().toLowerCase() === target;
    }).first();

    if ($opt.length) {
      $sel.selectpicker('val', $opt.val());
      return true;
    }
  }

  if (val) {
    $sel.append('<option value="' + val + '">' + (text || ('Opción #' + val)) + '</option>');
    $sel.selectpicker('refresh').selectpicker('val', val);
    return true;
  }

  return false;
}

// ===============================
//  Acciones de tabla: editar
// ===============================
var edit_reporte_gastos_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_editar");

  $(tbody).on("click", "button.table_editar", function(e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    if (!data || !data.egresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del egreso.");
      return;
    }

    var egresos_id = data.egresos_id;
    var urlEditar = '<?php echo SERVERURL;?>core/editarGastos.php';

    $('#formEgresosContables #egresos_id').val(egresos_id);
    resetPdfUI();

    const $form   = $('#formEgresosContables');
    const $prov   = $form.find('#proveedor_egresos');
    const $cuenta = $form.find('#cuenta_egresos');
    const $emp    = $form.find('#empresa_egresos');
    const $cat    = $form.find('#categoria_gastos');

    $prov.html('<option value="">Cargando proveedores...</option>').selectpicker('refresh');
    $cuenta.html('<option value="">Cargando cuentas...</option>').selectpicker('refresh');
    $emp.html('<option value="">Cargando empresas...</option>').selectpicker('refresh');

    if ($cat.length) {
      $cat.html('<option value="">Cargando categorías...</option>').selectpicker('refresh');
    }

    limpiarResumenFooterCuentaEgreso();

    var reqProv = $.ajax({
      url: "<?php echo SERVERURL; ?>core/getProveedores.php",
      type: "POST",
      dataType: "json"
    });

    var reqCta = $.ajax({
      url: "<?php echo SERVERURL; ?>core/getCuenta.php",
      type: "POST"
    });

    var reqEmp = $.ajax({
      url: "<?php echo SERVERURL; ?>core/getEmpresa.php",
      type: "POST"
    });

    var reqCat = $.ajax({
      url: "<?php echo SERVERURL; ?>core/getCategoriaGastos.php",
      type: "POST"
    });

    $.when(reqProv, reqCta, reqEmp, reqCat).done(function(provRes, ctaRes, empRes, catRes) {
      const provJSON = provRes[0];

      $prov.empty();

      if (provJSON && provJSON.success && Array.isArray(provJSON.data) && provJSON.data.length) {
        $prov.append('<option value="">Seleccione proveedor</option>');

        provJSON.data.forEach(function(p) {
          $prov.append(
            '<option value="' + escapeHtmlEgresos(p.proveedores_id) + '" data-subtext="' + escapeHtmlEgresos(p.rtn || 'Sin RTN o Identidad') + '">' +
              escapeHtmlEgresos(p.nombre) +
            '</option>'
          );
        });
      } else {
        $prov.append('<option value="">No hay proveedores disponibles</option>');
      }

      $prov.selectpicker('refresh');

      $cuenta.html(ctaRes[0] || '').selectpicker('refresh');
      $emp.html(empRes[0] || '').selectpicker('refresh');

      if ($cat.length) {
        $cat.html(catRes[0] || '').selectpicker('refresh');
      }

      $.ajax({
        type: 'POST',
        url: urlEditar,
        dataType: 'json',
        data: {
          egresos_id: egresos_id
        },
        beforeSend: function() {
          $form.find('#pro_egresos_contabilidad').val("Cargando...");
        },
        success: function(registro) {
          if (!registro || registro.success !== true || !registro.data) {
            var mensaje = registro && registro.message ? registro.message : "No se pudieron cargar los datos del egreso.";
            showNotify("error", "Error", mensaje);
            console.error("Respuesta inválida de editarGastos.php:", registro);
            return;
          }

          var v = registro.data;

          $form.attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarGastosAjax.php'
          });

          if ($form[0]) {
            $form[0].reset();
          }

          $('#formEgresosContables #egresos_id').val(egresos_id);

          $('#reg_egresosContabilidad').hide();
          $('#edi_egresosContabilidad').show();
          $('#delete_egresosContabilidad').hide();

          $form.find('#pro_egresos_contabilidad').val("Editar Egresos");

          var fechaReg = v.fecha || "";
          var $fecha = $form.find('#fecha_egresos');

          $form.addClass('modo-editar');

          $fecha.off()
            .removeAttr('data-remember data-rem-key')
            .val(fechaReg)
            .prop('disabled', true);

          $('#modalEgresosContables').one('shown.bs.modal', function() {
            var i = 0;

            (function keep() {
              var $f = $('#formEgresosContables #fecha_egresos');

              $f.off()
                .val(fechaReg)
                .attr('value', fechaReg)
                .prop('defaultValue', fechaReg);

              if (++i < 8) {
                setTimeout(keep, 60);
              }

              actualizarResumenFooterCuentaEgreso();
            })();
          });

          $form.find('#factura_egresos').val(v.factura || "");
          $form.find('#subtotal_egresos').val(v.subtotal || "0.00");
          $form.find('#isv_egresos').val(v.impuesto || "0.00");
          $form.find('#descuento_egresos').val(v.descuento || "0.00");
          $form.find('#nc_egresos').val(v.nc || "0.00");
          $form.find('#total_egresos').val(v.total || "0.00");
          $form.find('#observacion_egresos').val(v.observacion || "");

          setSelectpickerByValueOrText($prov,   (v.proveedores_id || data.proveedores_id || data.proveedor_egresos), v.proveedor || data.proveedor);
          setSelectpickerByValueOrText($cuenta, (v.cuentas_id     || data.cuentas_id     || data.cuenta_egresos),    v.nombre_cuenta || data.nombre_cuenta || data.nombre);
          setSelectpickerByValueOrText($emp,    (v.empresa_id     || data.empresa_id     || data.empresa_egresos),   v.nombre_empresa || data.nombre_empresa);

          if ($cat.length) {
            setSelectpickerByValueOrText($cat, (v.categoria_gastos_id || data.categoria_gastos_id || data.categoria_id), v.categoria || data.categoria);
          }

          actualizarResumenFooterCuentaEgreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaEgreso();
          }, 150);

          if (v.factura_pdf && v.factura_pdf !== '') {
            $('#filePreview').html(
              '<div class="existing-file d-flex align-items-center p-2 border rounded bg-light">' +
                '<i class="fas fa-file-pdf fa-2x text-danger mr-3"></i>' +
                '<div class="flex-grow-1">' +
                  '<div class="small text-muted">Archivo actual</div>' +
                  '<div class="font-weight-bold">' + escapeHtmlEgresos(v.factura_pdf) + '</div>' +
                '</div>' +
                '<div class="btn-group ml-2">' +
                  '<a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/' + encodeURIComponent(v.factura_pdf) + '" target="_blank" class="btn btn-danger btn-sm">' +
                    '<i class="fas fa-file-pdf mr-1"></i> Ver/Descargar PDF' +
                  '</a>' +
                  '<button type="button" class="btn btn-secondary btn-sm" id="removeFile">' +
                    '<i class="fas fa-exchange-alt mr-1"></i> Cambiar archivo' +
                  '</button>' +
                '</div>' +
              '</div>'
            ).show();

            $('#fileInfo').text('Archivo actual: ' + v.factura_pdf);

            $('#removeFile').off('click').on('click', function() {
              $('#filePreview').hide().html('');
              $('#fileInfo').text('Ningún archivo seleccionado');
              $('#factura_pdf').val('');

              $('<input>').attr({
                type: 'hidden',
                name: 'remove_existing_file',
                value: '1'
              }).appendTo('#formEgresosContables');
            });
          }

          setupFileUpload();

          $prov.prop('disabled', true).selectpicker('refresh');
          $cuenta.prop('disabled', true).selectpicker('refresh');
          $emp.prop('disabled', true).selectpicker('refresh');

          if ($cat.length) {
            $cat.prop('disabled', true).selectpicker('refresh');
          }

          actualizarResumenFooterCuentaEgreso();

          setTimeout(function () {
            actualizarResumenFooterCuentaEgreso();
          }, 150);

          $form.find('#subtotal_egresos, #isv_egresos, #descuento_egresos, #nc_egresos, #total_egresos').prop('disabled', true);
          $form.find('#buscar_cuenta_egresos, #buscar_empresa_egresos').hide();

          $('#modalEgresosContables').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
          });
        },
        error: function(xhr) {
          console.error('Error al cargar datos del gasto:', xhr.responseText);
          showNotify("error", "Error", "No se pudieron cargar los datos del gasto. Revise la consola.");
        }
      });
    })
    .fail(function(xhr) {
      console.error('Error al cargar catálogos:', xhr.responseText);

      showNotify("error", "Error", "No se pudieron cargar proveedores/cuentas/empresas/categorías");

      $prov.html('<option value="">Error al cargar proveedores</option>').selectpicker('refresh');
      $cuenta.html('<option value="">Error al cargar cuentas</option>').selectpicker('refresh');
      $emp.html('<option value="">Error al cargar empresas</option>').selectpicker('refresh');

      if ($cat.length) {
        $cat.html('<option value="">Error al cargar categorías</option>').selectpicker('refresh');
      }
    });
  });
};

$(document).on('hidden.bs.modal', '#modalEgresosContables', function() {
  $(this).removeData('mode');

  $('#formEgresosContables').removeClass('modo-editar');

  $('#formEgresosContables #fecha_egresos')
    .prop('disabled', false)
    .removeAttr('data-original-fecha');

  limpiarResumenFooterCuentaEgreso();
});

// ===============================
//  Acciones de tabla: reporte
// ===============================
var view_reporte_gastos_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.print_gastos");

  $(tbody).on("click", "button.print_gastos", function(e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    printGastos(data.egresos_id);
  });
};

function printGastos(egresos_id) {
  if (!egresos_id) {
    showNotify("error", "Error", "ID de gasto inválido.");
    return;
  }

  var url = '<?php echo SERVERURL; ?>core/generaGastos.php?egresos_id=' + encodeURIComponent(egresos_id);

  abrirDocumentoEnModal(url, 'Registro de Gasto');
}

// ===============================
//  Acciones de tabla: reversar
// ===============================
var anular_gastos_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.anular_factura");

  $(tbody).on("click", "button.anular_factura", function(e) {
    e.preventDefault();

    const $btn = $(this);
    const rowData = table.row($btn.parents("tr")).data();

    if (!rowData) {
      showNotify("error", "Error", "No se pudo obtener la fila seleccionada.");
      return;
    }

    const egresos_id = rowData.egresos_id;

    if (!egresos_id) {
      showNotify("error", "Error", "No se pudo obtener el ID del egreso.");
      return;
    }

    const content = document.createElement("div");

    content.innerHTML = `
      <p style="margin:0 0 6px 0;">
        El egreso <b>quedará activo</b>.
      </p>
      <p style="margin:0 0 6px 0;">
        Se registrará un <b>ingreso de reversión</b> por el mismo valor del egreso.
      </p>
      <p style="margin:0;">
        También se registrará el <b>movimiento de cuenta</b> correspondiente.
      </p>`;

    swal({
      title: "¿Reversar egreso?",
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
    }).then(function(ok) {
      if (!ok) return;

      $.ajax({
        url: "<?php echo SERVERURL;?>ajax/cancelEgresoContabilidadAjax.php",
        type: "POST",
        dataType: "html",
        data: {
          egresos_id: egresos_id
        },
        beforeSend: function() {
          $btn.prop("disabled", true);
        },
        success: function(response) {
          console.log("Respuesta reversión egreso:", response);

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
            respuestaTexto.indexOf("no se recibió el egreso") !== -1 ||
            respuestaTexto.indexOf("no se encontró el egreso") !== -1
          ) {
            showNotify("error", "Error", "No se pudo reversar el egreso. Revise la consola o el log del servidor.");
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

          listar_gastos_contabilidad();
          total_gastos_footer();
        },
        error: function(xhr) {
          showNotify("error", "Error", "No se pudo reversar el egreso: " + xhr.statusText);
          console.error("Error al reversar egreso:", xhr.responseText);
        },
        complete: function() {
          $btn.prop("disabled", false);
        }
      });
    });
  });
};

// ===============================
//  Modal egresos
// ===============================
function modal_egresos_contabilidad() {
  var $form = $('#formEgresosContables');

  if ($form.length === 0) {
    showNotify("error", "Error", "No se encontró el formulario de egresos. Revise que exista #formEgresosContables en el HTML.");
    return;
  }

  getCategoriaGastos();

  $form.removeClass('modo-editar');

  $form.find('#filePreview').hide();
  $form.find('#fileInfo').text('Ningún archivo seleccionado');
  $form.find('#factura_pdf').val('');

  setupFileUpload();

  $form.attr({
    'data-form': 'save',
    'action': '<?php echo SERVERURL;?>ajax/addEgresoContabilidadAjax.php'
  });

  if ($form[0]) {
    $form[0].reset();
  }

  $form.find('select.selectpicker').val('').selectpicker('refresh');
  $form.find('input[type="text"], input[type="number"], textarea').val('');

  limpiarResumenFooterCuentaEgreso();

  setTimeout(function() {
    var remembered = '';

    try {
      remembered = localStorage.getItem('egresos:lastFecha') || '';
    } catch (e) { }

    if (!remembered) {
      var d = new Date();
      var mm = String(d.getMonth() + 1).padStart(2, '0');
      var dd = String(d.getDate()).padStart(2, '0');

      remembered = d.getFullYear() + '-' + mm + '-' + dd;
    }

    var $fecha = $form.find('#fecha_egresos');

    if ($fecha.length) {
      $fecha.val(remembered)
        .prop('defaultValue', remembered)
        .attr('value', remembered)
        .trigger('change');
    }
  }, 0);

  $('#reg_egresosContabilidad').show();
  $('#edi_egresosContabilidad').hide();
  $('#delete_egresosContabilidad').hide();

  $form.find('#cuenta_codigo').prop("readonly", false);
  $form.find('#cuenta_nombre').prop("readonly", false);
  $form.find('#cuentas_activo').prop('disabled', false).prop('checked', false);

  $form.find('#buscar_cuenta_egresos').show();
  $form.find('#buscar_empresa_egresos').show();

  $form.find('#cuenta_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  $form.find('#empresa_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  $form.find('#proveedor_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');

  if ($form.find('#categoria_gastos').length) {
    $form.find('#categoria_gastos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  }

  $form.find('#subtotal_egresos').prop('disabled', false).val('');
  $form.find('#isv_egresos').prop('disabled', false).val('');
  $form.find('#descuento_egresos').prop('disabled', false).val('');
  $form.find('#nc_egresos').prop('disabled', false).val('');
  $form.find('#total_egresos').prop('disabled', false).val('0.00');

  $form.find('#pro_egresos_contabilidad').val("Registrar Egresos");

  $('#filePreview').html('').hide();
  $('#fileInfo').text('Ningún archivo seleccionado');
  $('#factura_pdf').val('');

  setupFileUpload();
  inicializarCalculoEgresos();
  calcularTotalEgreso();
  actualizarResumenFooterCuentaEgreso();

  $('#modalEgresosContables').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

// Al resetear el form limpia el PDF y recalcula
$(document).off('reset', '#formEgresosContables');
$(document).on('reset', '#formEgresosContables', function() {
  resetPdfUI();
  limpiarResumenFooterCuentaEgreso();

  setTimeout(function() {
    inicializarCalculoEgresos();
    calcularTotalEgreso();
    actualizarResumenFooterCuentaEgreso();
  }, 0);
});

// ===============================
//  Cargar catálogos
// ===============================
function getProveedorEgresos() {
  $.ajax({
    url: "<?php echo SERVERURL; ?>core/getProveedores.php",
    type: "POST",
    dataType: "json",
    success: function(response) {
      const select = $('#formEgresosContables #proveedor_egresos');

      select.empty();

      if (response.success) {
        response.data.forEach(function(p) {
          select.append(`<option value="${p.proveedores_id}" data-subtext="${p.rtn || 'Sin RTN o Identidad'}">${p.nombre}</option>`);
        });
      } else {
        select.append('<option value="">No hay colaboradores disponibles</option>');
      }

      select.selectpicker('refresh');
    },
    error: function(xhr) {
      showNotify("error", "Error", "Error de conexión al cargar colaboradores");

      $('#formEgresosContables #proveedor_egresos')
        .html('<option value="">Error al cargar</option>')
        .selectpicker('refresh');
    }
  });
}

function getCategoriaGastos() {
  var url = '<?php echo SERVERURL;?>core/getCategoriaGastos.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data) {
      $('#formEgresosContables #categoria_gastos').html(data).selectpicker('refresh');
      $('#formEgresosContables #categoria_gastos').val(0).selectpicker('refresh');
    }
  });
}

function getCuentaEgresos() {
  var url = '<?php echo SERVERURL;?>core/getCuenta.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data) {
      $('#formEgresosContables #cuenta_egresos').html(data).selectpicker('refresh');

      actualizarResumenFooterCuentaEgreso();

      setTimeout(function () {
        actualizarResumenFooterCuentaEgreso();
      }, 150);
    }
  });
}

function getEmpresaEgresos() {
  var url = '<?php echo SERVERURL;?>core/getEmpresa.php';

  $.ajax({
    type: "POST",
    url: url,
    async: true,
    success: function(data) {
      $('#formEgresosContables #empresa_egresos').html(data).selectpicker('refresh');
    }
  });
}

// ===============================
//  Focos modales
// ===============================
$(document).ready(function() {
$("#modal_registrar_proveedores").on('shown.bs.modal', function() {
    $(this).find('#formProveedores #nombre_proveedores').focus();
  });
});

// =========================================================
// CATEGORÍAS DE GASTOS | MODAL PÚBLICO COMPARTIDO
// ---------------------------------------------------------
// La administración ordinaria de categorías ya NO usa
// DataTable ni modales internos de Egresos.
// El modal global se encuentra en vistasModals.php.
// =========================================================


function abrirCategoriasGastosDesdeEgresos() {
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

// Mantener compatibilidad con cualquier llamada anterior del módulo,
// sin reconstruir el DataTable viejo.
function modal_categorias_contabilidad_egresos() {
  abrirCategoriasGastosDesdeEgresos();
}

// Cada alta/edición/estado/inversión/eliminación del modal público
// vuelve a cargar el select de categorías del formulario de egresos.
$(document)
  .off('categoriasGastos:actualizadas.egresos')
  .on('categoriasGastos:actualizadas.egresos', function() {
    if (typeof getCategoriaGastos === 'function') {
      getCategoriaGastos();
    }
  });

$('#btnNuevoProveedor').on('click', function() {
  modal_proveedores();
});

// ===============================
// GENERAR DOCUMENTO AUTOMÁTICO EGRESOS
// Formato: OUTYYYYMMDDHHMMSS
// ===============================
function generarDocumentoEgresoAutomatico() {
  var fecha = new Date();

  var year = fecha.getFullYear();
  var month = String(fecha.getMonth() + 1).padStart(2, '0');
  var day = String(fecha.getDate()).padStart(2, '0');
  var hour = String(fecha.getHours()).padStart(2, '0');
  var minute = String(fecha.getMinutes()).padStart(2, '0');
  var second = String(fecha.getSeconds()).padStart(2, '0');

  return 'OUT' + year + month + day + hour + minute + second;
}

$(document).off('click', '#btnGenerarFacturaEgresos');

$(document).on('click', '#btnGenerarFacturaEgresos', function(e) {
  e.preventDefault();

  var $input = $('#formEgresosContables #factura_egresos');
  var valorActual = $.trim($input.val());

  if (valorActual !== '') {
    showNotify('warning', 'Advertencia', 'El campo factura ya tiene un valor. Bórrelo si desea generar uno nuevo.');
    $input.focus().select();
    return false;
  }

  var documento = generarDocumentoEgresoAutomatico();

  $input.val(documento).focus().select();

  showNotify('success', 'Documento generado', 'Se generó el número de documento correctamente.');
});

// =========================================================
// EVITAR QUE SCROLL Y FLECHAS CAMBIEN INPUTS NUMBER
// EN FORMULARIOS DE CONTABILIDAD
// =========================================================
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