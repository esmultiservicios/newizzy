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
  if (val == null) return 0;
  if (typeof val === 'number') return val;

  return parseFloat(String(val).replace(/[^\d.-]/g, '')) || 0;
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
    $(this).find('.selectpicker').val('').selectpicker('refresh');
    listar_gastos_contabilidad();
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
var listar_gastos_contabilidad = function() {
  var estado = $("#formMainGastosContabilidad #estado_egresos").val() || 1;
  var fechai = $("#formMainGastosContabilidad #fechai").val();
  var fechaf = $("#formMainGastosContabilidad #fechaf").val();

  try {
    var _dtKey = 'DataTables_' + 'dataTableGastosContabilidad' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  } catch (e) { }

  var table_gastos_contabilidad = $("#dataTableGastosContabilidad").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,

    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableEgresosContabilidad.php",
      data: {
        fechai: fechai,
        fechaf: fechaf,
        estado: estado
      },
      dataSrc: function(json) {
        if (!json || !json.data) {
          actualizarCardsEgresosDesdeData({ data: [] });
          return [];
        }

        actualizarCardsEgresosDesdeData(json);

        if (json.data.length === 0) {
          showNotify("warning", "Advertencia", "No se encontraron registros con los filtros aplicados");
        }

        return json.data;
      },
      error: function(xhr) {
        actualizarCardsEgresosDesdeData({ data: [] });
        showNotify("error", "Error", "No se pudieron cargar los datos de egresos");
        console.error("Error en AJAX egresos:", xhr.responseText);
      }
    },

    columns: [
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center align-middle",
        render: function(data, type, row) {
          if (type !== "display") {
            return "";
          }

          var estadoGasto = parseInt(row.estado, 10);
          var gastoActivo = estadoGasto === 1;

          var accionesGasto = "";

          accionesGasto +=
            '<button type="button" class="dropdown-item accion-item accion-editar table_editar">' +
              '<span class="accion-icon accion-icon-primary">' +
                '<i class="fas fa-edit"></i>' +
              '</span>' +
              '<span class="accion-label">Editar</span>' +
            '</button>';

          accionesGasto +=
            '<button type="button" class="dropdown-item accion-item accion-imprimir table_reportes print_gastos">' +
              '<span class="accion-icon accion-icon-success">' +
                '<i class="fas fa-file-download"></i>' +
              '</span>' +
              '<span class="accion-label">Reporte</span>' +
            '</button>';

          if (gastoActivo) {
            accionesGasto +=
              '<button type="button" class="dropdown-item accion-item accion-anular table_cancelar anular_factura">' +
                '<span class="accion-icon accion-icon-danger">' +
                  '<i class="fas fa-ban"></i>' +
                '</span>' +
                '<span class="accion-label">Reversar</span>' +
              '</button>';
          } else {
            accionesGasto +=
              '<button type="button" class="dropdown-item accion-item accion-anulado" disabled>' +
                '<span class="accion-icon accion-icon-eliminar">' +
                  '<i class="fas fa-ban"></i>' +
                '</span>' +
                '<span class="accion-label">Gasto anulado</span>' +
              '</button>';
          }

          return '' +
            '<div class="dropdown acciones-dropdown">' +
              '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
              '</button>' +
              '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                accionesGasto +
              '</div>' +
            '</div>';
        }
      },
      {
        data: "fecha_registro",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoInfo(data, "Registro del egreso", "fas fa-clock");
        }
      },
      {
        data: "egresos_id",
        className: "text-center align-middle",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoChip("" + data, "fas fa-hashtag");
        }
      },
      {
        data: "categoria",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoChip(data || "Sin categoría", "fas fa-layer-group");
        }
      },
      {
        data: "fecha",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoInfo(data, "Fecha factura", "fas fa-calendar-day");
        }
      },
      {
        data: "nombre",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoInfo(data, "Forma de pago / cuenta", "fas fa-wallet");
        }
      },
      {
        data: "proveedor",
        render: function(data, type) {
          if (type !== 'display') return data;
          return renderEgresoInfo(data || "Sin proveedor", "Proveedor", "fas fa-truck");
        }
      },
      {
        data: "factura",
        render: function(data, type, row) {
          if (type !== 'display') {
            return data;
          }

          var numeroFactura = data ? escapeHtmlEgresos(data) : 'Sin factura';
          var icono = '';

          if (row.factura_pdf && row.factura_pdf !== '') {
            icono = '' +
              '<a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/' + encodeURIComponent(row.factura_pdf) + '" ' +
                 'target="_blank" ' +
                 'class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center factura-btn" ' +
                 'title="Ver/Descargar PDF" data-toggle="tooltip">' +
                 '<i class="fas fa-file-pdf"></i>' +
              '</a>';
          }

          return '' +
            '<div class="egresos-factura-box">' +
              '<span class="egresos-info-chip"><i class="fas fa-file-invoice-dollar mr-1"></i>' + numeroFactura + '</span>' +
              icono +
            '</div>';
        }
      },
      {
        data: "subtotal",
        className: "dt-body-right text-right",
        render: moneyRenderEgreso
      },
      {
        data: "impuesto",
        className: "dt-body-right text-right",
        render: moneyRenderEgreso
      },
      {
        data: "descuento",
        className: "dt-body-right text-right",
        render: moneyRenderEgreso
      },
      {
        data: "nc",
        className: "dt-body-right text-right",
        render: moneyRenderEgreso
      },
      {
        data: "total",
        className: "dt-body-right text-right",
        render: moneyRenderEgreso
      },
      {
        data: "observacion",
        render: function(data, type) {
          if (type !== 'display') return data;
          var texto = data && $.trim(data) !== '' ? data : 'Sin observación';
          return '<div class="egresos-observacion">' + escapeHtmlEgresos(texto) + '</div>';
        }
      },
      {
        data: "estado",
        className: "text-center align-middle",
        render: renderEstadoEgreso
      }
    ],

    order: [[1, 'desc']],

    lengthMenu: lengthMenu10,
    language: idioma_español,
    dom: dom,

    columnDefs: [
      {
        targets: 0,
        width: "8%",
        orderable: false,
        searchable: false,
        className: "text-center text-nowrap align-middle"
      },
      { targets: 1, width: "7.14%" },
      { targets: 2, width: "7.14%" },
      { targets: 3, width: "7.14%" },
      { targets: 4, width: "7.14%" },
      { targets: 5, width: "7.14%" },
      { targets: 6, width: "7.14%" },
      { targets: 7, width: "7.14%" },
      { targets: 8, width: "7.14%", className: "text-right text-nowrap" },
      { targets: 9, width: "7.14%", className: "text-right text-nowrap" },
      { targets: 10, width: "7.14%", className: "text-right text-nowrap" },
      { targets: 11, width: "7.14%", className: "text-right text-nowrap" },
      { targets: 12, width: "7.14%", className: "text-right text-nowrap" },
      { targets: 13, width: "7.14%" },
      { targets: 14, width: "7.14%", className: "text-center text-nowrap" }
    ],

    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Registro Gastos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() {
          listar_gastos_contabilidad();
        }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
        titleAttr: 'Agregar Egresos',
        className: 'table_crear btn btn-primary ocultar',
        action: function() {
          modal_egresos_contabilidad();
        }
      },
      {
        text: '<i class="fas fa-layer-group fa-lg crear"></i> Categorías',
        titleAttr: 'Categorías',
        className: 'table_crear btn btn-primary ocultar',
        action: function() {
          modal_categorias_contabilidad();
        }
      },
      {
        text: '<i class="fas fa-layer-group fa-lg crear"></i> Reporte',
        titleAttr: 'Reporte Categorías',
        className: 'table_crear btn btn-primary ocultar',
        action: function() {
          modal_reporte_categorias_contabilidad();
        }
      },
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Registro Gastos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
        },
        className: 'table_reportes btn btn-success ocultar'
      },
      {
        extend: 'pdfHtml5',
        footer: true,
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Reporte Registro Gastos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
        },
        customize: function(doc) {
          if (typeof imagen !== 'undefined' && imagen) {
            doc.content.splice(0, 0, {
              image: imagen,
              width: 100,
              height: 45,
              margin: [0, 0, 0, 12]
            });
          }
        }
      }
    ],

    initComplete: function() {
      this.api().order([1, 'desc']).draw();
      $('#buscar').focus();
    },

    drawCallback: function() {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

      cerrarDropdownAcciones();

      $('[data-toggle="tooltip"]').tooltip({
        container: "body",
        placement: "top"
      });
    }
  });

  $('#dataTableGastosContabilidad').on('draw.dt', function() {
    $('[data-toggle="tooltip"]').tooltip({
      container: "body",
      placement: "top"
    });
  });

  table_gastos_contabilidad.search('').draw();

  $('#buscar').focus();

  edit_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  view_reporte_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  anular_gastos_dataTable("#dataTableGastosContabilidad tbody", table_gastos_contabilidad);
  total_gastos_footer();
};

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
  $("#modalCategoriasEgresos").on('shown.bs.modal', function() {
    $(this).find('#formCategoriaEgresos #categoria').focus();
  });

  $("#modalReporteCategorias").on('shown.bs.modal', function() {
    $(this).find('#formularioReporteCategorias #buscar').focus();
  });

  $("#modalUpdateCategoriasEgresos").on('shown.bs.modal', function() {
    $(this).find('#formUpdateCategoriaEgresos #categoria').focus();
  });

  $("#modal_registrar_proveedores").on('shown.bs.modal', function() {
    $(this).find('#formProveedores #nombre_proveedores').focus();
  });
});

// ===============================
//  Modal categorías
// ===============================
function modal_categorias_contabilidad() {
  var $formCategoria = $('#formCategoriaEgresos');

  if ($formCategoria.length > 0) {
    $formCategoria[0].reset();
  }

  $('#formCategoriaEgresos #categoria_gastos_id').val('');
  $('#formCategoriaEgresos #es_inversion').prop('checked', false);
  $('#regCategoriaEgresos').show();

  listar_categoria_egresos();

  $('#modalCategoriasEgresos').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  $('#modalCategoriasEgresos').off('shown.bs.modal.categorias');
  $('#modalCategoriasEgresos').on('shown.bs.modal.categorias', function() {
    $('#formCategoriaEgresos #categoria').focus();

    setTimeout(function() {
      if ($.fn.DataTable.isDataTable('#DatatableCategoriaEgresos')) {
        $('#DatatableCategoriaEgresos').DataTable().columns.adjust().draw(false);
      }
    }, 200);
  });
}

// ===============================
//  Construir header/footer Categorías desde JS
// ===============================
function construirEstructuraTablaCategoriaEgresos() {
  var $tabla = $('#DatatableCategoriaEgresos');

  if ($tabla.length === 0) {
    showNotify('error', 'Error', 'No se encontró la tabla #DatatableCategoriaEgresos.');
    return false;
  }

  if ($.fn.DataTable.isDataTable('#DatatableCategoriaEgresos')) {
    $tabla.DataTable().clear().destroy();
  }

  $tabla.empty();

  $tabla.html(
    '<thead>' +
      '<tr>' +
        '<th data-title="Categoría">Categoría</th>' +
        '<th data-title="Tipo">Tipo</th>' +
        '<th data-title="Estado">Estado</th>' +
        '<th data-title="Fecha">Fecha</th>' +
        '<th data-title="Acciones">Acciones</th>' +
      '</tr>' +
    '</thead>' +
    '<tbody></tbody>' +
    '<tfoot>' +
      '<tr>' +
        '<th colspan="5">' +
          '<div class="categoria-footer-resumen">' +
            '<span class="categoria-footer-item">' +
              '<i class="fas fa-layer-group"></i> Total: <strong id="catFooterTotal">0</strong>' +
            '</span>' +
            '<span class="categoria-footer-item text-success">' +
              '<i class="fas fa-check-circle"></i> Activas: <strong id="catFooterActivas">0</strong>' +
            '</span>' +
            '<span class="categoria-footer-item text-danger">' +
              '<i class="fas fa-times-circle"></i> Inactivas: <strong id="catFooterInactivas">0</strong>' +
            '</span>' +
            '<span class="categoria-footer-item text-primary">' +
              '<i class="fas fa-seedling"></i> Inversión: <strong id="catFooterInversion">Ninguna</strong>' +
            '</span>' +
          '</div>' +
        '</th>' +
      '</tr>' +
    '</tfoot>'
  );

  return true;
}

function pintarHeaderCategorias() {
  var titulos = ['Categoría', 'Tipo', 'Estado', 'Fecha', 'Acciones'];

  $('#DatatableCategoriaEgresos thead th').each(function(index) {
    var titulo = titulos[index] || $(this).data('title') || '';

    $(this)
      .attr('data-title', titulo)
      .css({
        'color': '#ffffff',
        'font-weight': '800',
        'font-size': '13px',
        'letter-spacing': '.02em',
        'text-align': index === 0 ? 'left' : 'center',
        'vertical-align': 'middle',
        'white-space': 'nowrap'
      });

    $(this).html(
      '<span class="cat-header-title" style="color:#ffffff !important;font-weight:800 !important;display:inline-block;">' +
        titulo +
      '</span>'
    );
  });
}

// ===============================
//  DataTable Categorías
// ===============================
var listar_categoria_egresos = function() {
  if (!construirEstructuraTablaCategoriaEgresos()) {
    return;
  }

  var table_categoria_egresos = $('#DatatableCategoriaEgresos').DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,
    bDestroy: true,
    autoWidth: false,

    ajax: {
      method: 'POST',
      url: '<?php echo SERVERURL; ?>core/llenarDataTableCategoriaEgresos.php'
    },

    columns: [
      {
        data: 'nombre',
        title: 'Categoría',
        className: 'align-middle',
        render: function(data, type) {
          if (type !== 'display') {
            return data;
          }

          return '' +
            '<div style="font-weight:800;color:#0f172a;">' +
              '<i class="fas fa-tag mr-1 text-primary"></i>' +
              data +
            '</div>';
        }
      },
      {
        data: 'es_inversion',
        title: 'Tipo',
        className: 'text-center align-middle',
        render: function(data, type) {
          var inversion = parseInt(data || 0, 10) === 1;

          if (type !== 'display') {
            return inversion ? 'Inversión' : 'Gasto normal';
          }

          if (inversion) {
            return '<span class="badge-cat-inversion"><i class="fas fa-seedling"></i> Inversión</span>';
          }

          return '<span class="badge-cat-normal"><i class="fas fa-receipt"></i> Gasto normal</span>';
        }
      },
      {
        data: 'estado',
        title: 'Estado',
        className: 'text-center align-middle',
        render: function(data, type) {
          var activo = parseInt(data || 0, 10) === 1;

          if (type !== 'display') {
            return activo ? 'Activo' : 'Inactivo';
          }

          if (activo) {
            return '<span class="badge-cat-activa"><i class="fas fa-check-circle"></i> Activo</span>';
          }

          return '<span class="badge-cat-inactiva"><i class="fas fa-times-circle"></i> Inactivo</span>';
        }
      },
      {
        data: 'date_write',
        title: 'Fecha',
        className: 'align-middle',
        render: function(data, type) {
          return data || '';
        }
      },
      {
        data: null,
        title: 'Acciones',
        orderable: false,
        searchable: false,
        className: 'text-center align-middle',
        render: function(data, type, row) {
          if (type !== 'display') {
            return '';
          }

          var inversion = parseInt(row.es_inversion || 0, 10) === 1;
          var activa = parseInt(row.estado || 0, 10) === 1;

          var badgeEstado = activa
            ? '<span class="badge-cat-activa"><i class="fas fa-circle"></i> Activa</span>'
            : '<span class="badge-cat-inactiva"><i class="fas fa-lock"></i> Inactiva</span>';

          var accionesCategoria = '';

          accionesCategoria +=
            '<button type="button" class="dropdown-item accion-item accion-editar table_editar">' +
              '<span class="accion-icon accion-icon-primary">' +
                '<i class="fas fa-edit"></i>' +
              '</span>' +
              '<span class="accion-label">Editar</span>' +
            '</button>';

          if (inversion) {
            accionesCategoria +=
              '<button type="button" class="dropdown-item accion-item accion-inversion table_inversion">' +
                '<span class="accion-icon accion-icon-eliminar">' +
                  '<i class="fas fa-times-circle"></i>' +
                '</span>' +
                '<span class="accion-label">Quitar inversión</span>' +
              '</button>';
          } else {
            accionesCategoria +=
              '<button type="button" class="dropdown-item accion-item accion-inversion table_inversion">' +
                '<span class="accion-icon accion-icon-success">' +
                  '<i class="fas fa-seedling"></i>' +
                '</span>' +
                '<span class="accion-label">Marcar inversión</span>' +
              '</button>';
          }

          if (activa) {
            accionesCategoria +=
              '<button type="button" class="dropdown-item accion-item accion-inactivar table_estado">' +
                '<span class="accion-icon accion-icon-warning">' +
                  '<i class="fas fa-toggle-off"></i>' +
                '</span>' +
                '<span class="accion-label">Inactivar</span>' +
              '</button>';
          } else {
            accionesCategoria +=
              '<button type="button" class="dropdown-item accion-item accion-activar table_estado">' +
                '<span class="accion-icon accion-icon-success">' +
                  '<i class="fas fa-toggle-on"></i>' +
                '</span>' +
                '<span class="accion-label">Activar</span>' +
              '</button>';
          }

          accionesCategoria +=
            '<button type="button" class="dropdown-item accion-item accion-eliminar table_eliminar">' +
              '<span class="accion-icon accion-icon-danger">' +
                '<i class="fas fa-trash-alt"></i>' +
              '</span>' +
              '<span class="accion-label">Eliminar</span>' +
            '</button>';

          return '' +
            '<div class="acciones-caja-wrap">' +
              '<div class="dropdown acciones-dropdown">' +
                '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                  '<i class="fas fa-cog"></i>' +
                  '<span>Acciones</span>' +
                '</button>' +
                '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                  accionesCategoria +
                '</div>' +
              '</div>' +
              badgeEstado +
            '</div>';
        }
      }
    ],

    order: [[0, 'asc']],
    lengthMenu: lengthMenu,
    language: idioma_español,
    dom: dom,

    columnDefs: [
      { width: '35%', targets: 0 },
      { width: '15%', targets: 1 },
      { width: '15%', targets: 2 },
      { width: '20%', targets: 3 },
      {
        width: '15%',
        targets: 4,
        orderable: false,
        searchable: false,
        className: 'text-center text-nowrap align-middle'
      }
    ],

    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Categoría Egresos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() {
          listar_categoria_egresos();
        }
      },
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Categoría Egresos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: {
          columns: [0, 1, 2, 3],
          format: {
            body: function(data) {
              return String(data).replace(/<[^>]*>/g, '').trim();
            },
            footer: function(data) {
              return String(data).replace(/<[^>]*>/g, '').trim();
            }
          }
        }
      },
      {
        extend: 'pdf',
        footer: true,
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte Categoría Egresos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: {
          columns: [0, 1, 2, 3],
          format: {
            body: function(data) {
              return String(data).replace(/<[^>]*>/g, '').trim();
            },
            footer: function(data) {
              return String(data).replace(/<[^>]*>/g, '').trim();
            }
          }
        },
        customize: function(doc) {
          if (typeof imagen !== 'undefined' && imagen) {
            doc.content.splice(1, 0, {
              margin: [0, 0, 0, 12],
              alignment: 'left',
              image: imagen,
              width: 100,
              height: 45
            });
          }
        }
      }
    ],

    initComplete: function() {
      var api = this.api();

      pintarHeaderCategorias(api.table().header());
      api.columns.adjust();
      $('#formCategoriaEgresos #categoria').focus();
    },

    headerCallback: function(thead) {
      pintarHeaderCategorias(thead);
    },

    drawCallback: function() {
      var api = this.api();
      var data = api.rows({ search: 'applied' }).data();

      var total = 0;
      var activas = 0;
      var inactivas = 0;
      var inversionNombre = 'Ninguna';

      for (var i = 0; i < data.length; i++) {
        total++;

        if (parseInt(data[i].estado || 0, 10) === 1) {
          activas++;
        } else {
          inactivas++;
        }

        if (parseInt(data[i].es_inversion || 0, 10) === 1) {
          inversionNombre = data[i].nombre || 'Asignada';
        }
      }

      $('#catFooterTotal').html(total);
      $('#catFooterActivas').html(activas);
      $('#catFooterInactivas').html(inactivas);
      $('#catFooterInversion').html(inversionNombre);

      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

      if (typeof cerrarDropdownAcciones === 'function') {
        cerrarDropdownAcciones();
      }

      $('[data-toggle="tooltip"], [title]').tooltip({
        container: 'body',
        placement: 'top'
      });
    }
  });

  table_categoria_egresos.search('').draw();

  edit_categoria_gastos_dataTable('#DatatableCategoriaEgresos tbody', table_categoria_egresos);
  delete_categoria_gastos_dataTable('#DatatableCategoriaEgresos tbody', table_categoria_egresos);
  set_inversion_categoria_gastos_dataTable('#DatatableCategoriaEgresos tbody', table_categoria_egresos);
  cambiar_estado_categoria_gastos_dataTable('#DatatableCategoriaEgresos tbody', table_categoria_egresos);
};

// ===============================
//  Limpiar formulario categoría
// ===============================
$(document).off('click', '#btnLimpiarCategoriaEgresos');
$(document).on('click', '#btnLimpiarCategoriaEgresos', function() {
  var $formCategoria = $('#formCategoriaEgresos');

  if ($formCategoria.length > 0) {
    $formCategoria[0].reset();
  }

  $('#formCategoriaEgresos #categoria_gastos_id').val('');
  $('#formCategoriaEgresos #es_inversion').prop('checked', false);
  $('#formCategoriaEgresos #categoria').focus();
});

// ===============================
//  Registrar categoría
// ===============================
$(document).off('submit', '#formCategoriaEgresos');
$(document).on('submit', '#formCategoriaEgresos', function(e) {
  e.preventDefault();

  var form = $(this);
  var categoria = $('#formCategoriaEgresos #categoria').val().trim();
  var url = '<?php echo SERVERURL;?>ajax/addCategoriaEgresos.php';
  var formData = form.serialize();

  if (categoria === '') {
    showNotify('warning', 'Campo requerido', 'Debe ingresar el nombre de la categoría.');
    $('#formCategoriaEgresos #categoria').focus();
    return;
  }

  swal({
    title: '¿Estás seguro?',
    text: '¿Desea registrar la categoría: ' + categoria + '?',
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'Cancelar',
        visible: true
      },
      confirm: {
        text: '¡Sí, registrar!'
      }
    },
    dangerMode: false,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function(ok) {
    if (!ok) return;

    $.ajax({
      type: 'POST',
      url: url,
      data: formData,
      dataType: 'json',
      success: function(r) {
        if (r && r.success) {
          showNotify('success', r.title || 'Éxito', r.text || 'Operación realizada correctamente');

          if ($('#formCategoriaEgresos').length > 0) {
            $('#formCategoriaEgresos')[0].reset();
          }

          $('#formCategoriaEgresos #categoria_gastos_id').val('');
          $('#formCategoriaEgresos #es_inversion').prop('checked', false);

          listar_categoria_egresos();
          getCategoriaGastos();

          $('#formCategoriaEgresos #categoria').focus();
        } else {
          showNotify('error', r.title || 'Error', r.text || 'Ocurrió un error');
        }
      },
      error: function(xhr) {
        showNotify('error', 'Error', 'Error en la conexión: ' + xhr.statusText);
      }
    });
  });
});

// ===============================
//  Modal editar categoría
// ===============================
function modal_editar_categorias_contabilidad(categoria_gastos_id, categoria, es_inversion) {
  if ($('#formUpdateCategoriaEgresos').length > 0) {
    $('#formUpdateCategoriaEgresos').attr({
      'data-form': 'update',
      'action': '<?php echo SERVERURL;?>ajax/modificarCategoriaEgresos.php'
    })[0].reset();
  }

  $('#ediCategoriaEgresos').show();

  $('#formUpdateCategoriaEgresos #categoria_gastos_id').val(categoria_gastos_id);
  $('#formUpdateCategoriaEgresos #categoria').val(categoria);
  $('#formUpdateCategoriaEgresos #es_inversion').prop('checked', parseInt(es_inversion || 0, 10) === 1);

  $('#modalUpdateCategoriasEgresos').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  $('#modalUpdateCategoriasEgresos').off('shown.bs.modal.categoriasUpdate');
  $('#modalUpdateCategoriasEgresos').on('shown.bs.modal.categoriasUpdate', function() {
    $('#formUpdateCategoriaEgresos #categoria').focus();
  });
}

// ===============================
//  Modificar categoría
// ===============================
$(document).off('submit', '#formUpdateCategoriaEgresos');
$(document).on('submit', '#formUpdateCategoriaEgresos', function(e) {
  e.preventDefault();

  var form = $(this);
  var url = form.attr('action');
  var formData = form.serialize();
  var categoria = $('#formUpdateCategoriaEgresos #categoria').val().trim();

  if (categoria === '') {
    showNotify('warning', 'Campo requerido', 'Debe ingresar el nombre de la categoría.');
    $('#formUpdateCategoriaEgresos #categoria').focus();
    return;
  }

  swal({
    title: '¿Estás seguro?',
    text: '¿Desea actualizar la categoría a: ' + categoria + '?',
    icon: 'warning',
    buttons: {
      cancel: {
        text: 'Cancelar',
        visible: true
      },
      confirm: {
        text: '¡Sí, actualizar!'
      }
    },
    dangerMode: false,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then(function(ok) {
    if (!ok) return;

    $.ajax({
      type: 'POST',
      url: url,
      data: formData,
      dataType: 'json',
      success: function(r) {
        if (r && r.success) {
          showNotify('success', r.title || 'Éxito', r.text || 'Cambios guardados correctamente');

          $('#modalUpdateCategoriasEgresos').modal('hide');

          listar_categoria_egresos();
          getCategoriaGastos();
        } else {
          showNotify('error', r.title || 'Error', r.text || 'Ocurrió un error al actualizar');

          if (r.redirect) {
            window.location.href = r.redirect;
          }
        }
      },
      error: function(xhr) {
        showNotify('error', 'Error', 'Error en la conexión: ' + xhr.statusText);
      }
    });
  });
});

// ===============================
//  Acción editar categoría
// ===============================
var edit_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off('click', 'button.table_editar');

  $(tbody).on('click', 'button.table_editar', function(e) {
    e.preventDefault();

    var data = table.row($(this).parents('tr')).data();

    modal_editar_categorias_contabilidad(
      data.categoria_gastos_id,
      data.nombre,
      data.es_inversion
    );
  });
};

// ===============================
//  Acción inversión categoría
// ===============================
var set_inversion_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off('click', 'button.table_inversion');

  $(tbody).on('click', 'button.table_inversion', function(e) {
    e.preventDefault();

    var data = table.row($(this).parents('tr')).data();
    var esInversionActual = parseInt(data.es_inversion || 0, 10) === 1;
    var nuevoValor = esInversionActual ? 0 : 1;

    var mensaje = nuevoValor === 1
      ? '¿Desea marcar esta categoría como inversión? Si existe otra marcada, se quitará automáticamente.'
      : '¿Desea quitar la marca de inversión a esta categoría?';

    swal({
      title: 'Confirmar cambio',
      text: mensaje,
      icon: 'warning',
      buttons: {
        cancel: {
          text: 'Cancelar',
          visible: true
        },
        confirm: {
          text: 'Sí, confirmar'
        }
      },
      dangerMode: false,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then(function(ok) {
      if (!ok) return;

      cambiarInversionCategoriaGastos(data.categoria_gastos_id, nuevoValor);
    });
  });
};

function cambiarInversionCategoriaGastos(categoria_gastos_id, es_inversion) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/setInversionCategoriaGastos.php',
    dataType: 'json',
    data: {
      categoria_gastos_id: categoria_gastos_id,
      es_inversion: es_inversion
    },
    success: function(r) {
      if (r && r.success) {
        showNotify('success', r.title || 'Éxito', r.text || 'Cambio aplicado correctamente');

        listar_categoria_egresos();
        getCategoriaGastos();
      } else {
        showNotify('error', r.title || 'Error', r.text || 'No se pudo aplicar el cambio');
      }
    },
    error: function(xhr) {
      showNotify('error', 'Error', 'Error en la conexión: ' + xhr.statusText);
    }
  });
}

// ===============================
//  Acción estado categoría
// ===============================
var cambiar_estado_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off('click', 'button.table_estado');

  $(tbody).on('click', 'button.table_estado', function(e) {
    e.preventDefault();

    var data = table.row($(this).parents('tr')).data();
    var estadoActual = parseInt(data.estado || 0, 10);
    var nuevoEstado = estadoActual === 1 ? 0 : 1;

    swal({
      title: 'Confirmar cambio',
      text: '¿Desea ' + (nuevoEstado === 1 ? 'activar' : 'inactivar') + ' la categoría: ' + data.nombre + '?',
      icon: 'warning',
      buttons: {
        cancel: {
          text: 'Cancelar',
          visible: true
        },
        confirm: {
          text: 'Sí, confirmar'
        }
      },
      dangerMode: false,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then(function(ok) {
      if (!ok) return;

      cambiarEstadoCategoriaGastos(data.categoria_gastos_id, nuevoEstado);
    });
  });
};

function cambiarEstadoCategoriaGastos(categoria_gastos_id, estado) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/cambiarEstadoCategoriaGastos.php',
    dataType: 'json',
    data: {
      categoria_gastos_id: categoria_gastos_id,
      estado: estado
    },
    success: function(r) {
      if (r && r.success) {
        showNotify('success', r.title || 'Éxito', r.text || 'Estado actualizado correctamente');

        listar_categoria_egresos();
        getCategoriaGastos();
      } else {
        showNotify('error', r.title || 'Error', r.text || 'No se pudo actualizar el estado');
      }
    },
    error: function(xhr) {
      showNotify('error', 'Error', 'Error en la conexión: ' + xhr.statusText);
    }
  });
}

// ===============================
//  Acción eliminar categoría
// ===============================
var delete_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off('click', 'button.table_eliminar');

  $(tbody).on('click', 'button.table_eliminar', function(e) {
    e.preventDefault();

    var data = table.row($(this).parents('tr')).data();

    swal({
      title: '¿Estás seguro?',
      text: '¿Desea eliminar la categoría: ' + data.nombre + '?',
      icon: 'warning',
      buttons: {
        cancel: {
          text: 'Cancelar',
          visible: true
        },
        confirm: {
          text: '¡Sí, eliminar!'
        }
      },
      dangerMode: true,
      closeOnEsc: false,
      closeOnClickOutside: false
    }).then(function(ok) {
      if (ok === true) {
        deleteCategoriaGastos(data.categoria_gastos_id, data.nombre);
      }
    });
  });
};

function deleteCategoriaGastos(categoria_gastos_id, categoria) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL;?>core/deleteCategoriaGastos.php',
    dataType: 'json',
    data: {
      categoria_gastos_id: categoria_gastos_id,
      categoria: categoria
    },
    success: function(r) {
      if (r && r.success) {
        showNotify('success', r.title || 'Éxito', r.text || 'Categoría eliminada correctamente');

        listar_categoria_egresos();
        getCategoriaGastos();
      } else {
        showNotify('error', r.title || 'Error', r.text || 'Ocurrió un error al eliminar');
      }
    },
    error: function(xhr) {
      showNotify('error', 'Error', 'Error en la conexión: ' + xhr.statusText);
    }
  });
}

// ===============================
//  Reporte categorías
// ===============================
var listar_reporte_categoria_egresos = function() {
  var fechai = $("#formularioReporteCategorias #fechai").val();
  var fechaf = $("#formularioReporteCategorias #fechaf").val();

  var table_reporte_categoria = $("#DatatableReporteCategorias").DataTable({
    destroy: true,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL; ?>core/llenarDataTableReporteCategoriaEgresos.php",
      data: {
        fechai: fechai,
        fechaf: fechaf
      }
    },
    columns: [
      { data: "categoria" },
      {
        data: "monto",
        className: "dt-body-right",
        render: moneyRender
      }
    ],
    lengthMenu: lengthMenu,
    stateSave: true,
    bDestroy: true,
    language: idioma_español,
    dom: dom,
    columnDefs: [
      {
        width: "70%",
        targets: 0
      },
      {
        width: "30%",
        targets: 1
      }
    ],
    autoWidth: false,
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Categoría Egresos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function() {
          listar_reporte_categoria_egresos();
        }
      },
      {
        extend: 'excelHtml5',
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Categoría Egresos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: {
          columns: [0, 1]
        }
      },
      {
        extend: 'pdf',
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        title: 'Reporte Categoría Egresos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: {
          columns: [0, 1]
        },
        customize: function(doc) {
          doc.content.splice(1, 0, {
            margin: [0, 0, 0, 12],
            alignment: 'left',
            image: imagen,
            width: 100,
            height: 45
          });
        }
      }
    ],
    drawCallback: function() {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
    }
  });

  table_reporte_categoria.search('').draw();

  $('#buscar').focus();

  total_reporte_categoria_gastos_footer();
};

$('#formularioReporteCategorias #search').on('click', function(e) {
  e.preventDefault();
  listar_reporte_categoria_egresos();
});

var total_reporte_categoria_gastos_footer = function() {
  var fechai = $("#formularioReporteCategorias #fechai").val();
  var fechaf = $("#formularioReporteCategorias #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalReporteCategoriaGastosFooter.php',
    type: "POST",
    data: {
      fechai: fechai,
      fechaf: fechaf
    }
  })
  .done(function(data) {
    data = JSON.parse(data || "{}");
    $("#monto-i").html("L. " + formatMoney(data.monto));
  })
  .fail(function() {
    console.log("total ingreso error");
  });
};

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