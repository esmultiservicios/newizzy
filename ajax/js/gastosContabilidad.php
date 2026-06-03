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
    data = JSON.parse(data || "{}");

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

      { data: "fecha_registro" },
      { data: "egresos_id" },
      { data: "categoria" },
      { data: "fecha" },
      { data: "nombre" },
      { data: "proveedor" },

      {
        data: "factura",
        render: function(data, type, row) {
          if (type !== 'display') {
            return data;
          }

          let numeroFactura = data ? data : '';
          let icono = '';

          if (row.factura_pdf && row.factura_pdf !== '') {
            icono = `
              <a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/${row.factura_pdf}"
                 target="_blank"
                 class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center factura-btn"
                 title="Ver/Descargar PDF" data-toggle="tooltip">
                 <i class="fas fa-file-pdf"></i>
              </a>`;
          }

          return `
            <div class="d-flex justify-content-between align-items-center w-100">
              <span>${numeroFactura}</span>
              ${icono}
            </div>`;
        }
      },

      {
        data: "subtotal",
        className: "dt-body-right",
        render: moneyRender
      },
      {
        data: "impuesto",
        className: "dt-body-right",
        render: moneyRender
      },
      {
        data: "descuento",
        className: "dt-body-right",
        render: moneyRender
      },
      {
        data: "nc",
        className: "dt-body-right",
        render: moneyRender
      },
      {
        data: "total",
        className: "dt-body-right",
        render: moneyRender
      },
      { data: "observacion" },

      {
        data: "estado",
        render: function(data, type) {
          if (type !== 'display') {
            return data;
          }

          var ok = parseInt(data, 10) === 1;
          var icon = ok ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
          var cls  = ok ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';

          return '<span class="' + cls + '" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">' +
                    icon +
                    (ok ? 'Activo' : 'Inactivo') +
                 '</span>';
        }
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
      {
        targets: 1,
        width: "7.14%"
      },
      {
        targets: 2,
        width: "7.14%"
      },
      {
        targets: 3,
        width: "7.14%"
      },
      {
        targets: 4,
        width: "7.14%"
      },
      {
        targets: 5,
        width: "7.14%"
      },
      {
        targets: 6,
        width: "7.14%"
      },
      {
        targets: 7,
        width: "7.14%"
      },
      {
        targets: 8,
        width: "7.14%",
        className: "text-right text-nowrap"
      },
      {
        targets: 9,
        width: "7.14%",
        className: "text-right text-nowrap"
      },
      {
        targets: 10,
        width: "7.14%",
        className: "text-right text-nowrap"
      },
      {
        targets: 11,
        width: "7.14%",
        className: "text-right text-nowrap"
      },
      {
        targets: 12,
        width: "7.14%",
        className: "text-right text-nowrap"
      },
      {
        targets: 13,
        width: "7.14%"
      },
      {
        targets: 14,
        width: "7.14%",
        className: "text-center text-nowrap"
      }
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

  $(tbody).on("click", "button.table_editar", function() {
    var data = table.row($(this).parents("tr")).data();
    var urlEditar = '<?php echo SERVERURL;?>core/editarGastos.php';

    $('#formEgresosContables #egresos_id').val(data.egresos_id);
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
            `<option value="${p.proveedores_id}" data-subtext="${p.rtn || 'Sin RTN o Identidad'}">${p.nombre}</option>`
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
        data: $form.serialize(),
        success: function(registro) {
          var v = eval(registro);

          $form.attr({
            'data-form': 'update',
            'action': '<?php echo SERVERURL;?>ajax/modificarGastosAjax.php'
          })[0].reset();

          $('#reg_egresosContabilidad').hide();
          $('#edi_egresosContabilidad').show();
          $('#delete_egresosContabilidad').hide();

          $form.find('#pro_egresos_contabilidad').val("Editar Egresos");

          // Fecha bloqueada y sin remember
          var fechaReg = v[3];
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
            })();
          });

          $form.find('#factura_egresos').val(v[4]);
          $form.find('#subtotal_egresos').val(v[5]);
          $form.find('#isv_egresos').val(v[6]);
          $form.find('#descuento_egresos').val(v[7]);
          $form.find('#nc_egresos').val(v[8]);
          $form.find('#total_egresos').val(v[9]);
          $form.find('#observacion_egresos').val(v[10]);

          setSelectpickerByValueOrText($prov,   (v[0] || data.proveedores_id || data.proveedor_egresos), data.proveedor);
          setSelectpickerByValueOrText($cuenta, (v[1] || data.cuentas_id     || data.cuenta_egresos),    data.nombre_cuenta);
          setSelectpickerByValueOrText($emp,    (v[2] || data.empresa_id     || data.empresa_egresos),   data.nombre_empresa);

          if ($cat.length) {
            var catIdFromRow = data.categoria_gastos_id || data.categoria_id || '';
            var catNameFromRow = data.categoria || '';

            setSelectpickerByValueOrText($cat, catIdFromRow, catNameFromRow);
          }

          if (v[11] && v[11] !== '') {
            $('#filePreview').html(`
              <div class="existing-file d-flex align-items-center p-2 border rounded bg-light">
                <i class="fas fa-file-pdf fa-2x text-danger mr-3"></i>
                <div class="flex-grow-1">
                  <div class="small text-muted">Archivo actual</div>
                  <div class="font-weight-bold">${v[11]}</div>
                </div>
                <div class="btn-group ml-2">
                  <a href="<?php echo SERVERURL; ?>vistas/plantilla/gastos/${v[11]}"
                     target="_blank"
                     class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i> Ver/Descargar PDF
                  </a>
                  <button type="button" class="btn btn-secondary btn-sm" id="removeFile">
                    <i class="fas fa-exchange-alt mr-1"></i> Cambiar archivo
                  </button>
                </div>
              </div>
            `).show();

            $('#fileInfo').text('Archivo actual: ' + v[11]);

            $('#removeFile').on('click', function() {
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
          showNotify("error", "Error", "No se pudieron cargar los datos del gasto");
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
  var url = '<?php echo SERVERURL; ?>core/generaGastos.php?egresos_id=' + egresos_id;
  window.open(url);
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
  getCategoriaGastos();

  $('#formEgresosContables').removeClass('modo-editar');

  $('#formEgresosContables #filePreview').hide();
  $('#formEgresosContables #fileInfo').text('Ningún archivo seleccionado');
  $('#formEgresosContables #factura_pdf').val('');

  setupFileUpload();

  $('#formEgresosContables').attr({
    'data-form': 'save',
    'action': '<?php echo SERVERURL;?>ajax/addEgresoContabilidadAjax.php'
  });

  $('#formEgresosContables')[0].reset();

  $('#formEgresosContables select.selectpicker').val('').selectpicker('refresh');
  $('#formEgresosContables input[type="text"], #formEgresosContables input[type="number"], #formEgresosContables textarea').val('');

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

    var $f = $('#formEgresosContables #fecha_egresos');

    if ($f.length) {
      $f.val(remembered)
        .prop('defaultValue', remembered)
        .attr('value', remembered)
        .trigger('change');
    }
  }, 0);

  $('#reg_egresosContabilidad').show();
  $('#edi_egresosContabilidad').hide();
  $('#delete_egresosContabilidad').hide();

  $('#formEgresosContables #cuenta_codigo').prop("readonly", false);
  $('#formEgresosContables #cuenta_nombre').prop("readonly", false);
  $('#formEgresosContables #cuentas_activo').prop('disabled', false).prop('checked', false);

  $('#formEgresosContables #buscar_cuenta_egresos').show();
  $('#formEgresosContables #buscar_empresa_egresos').show();

  $('#formEgresosContables #cuenta_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  $('#formEgresosContables #empresa_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  $('#formEgresosContables #proveedor_egresos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');

  if ($('#formEgresosContables #categoria_gastos').length) {
    $('#formEgresosContables #categoria_gastos').prop('disabled', false).selectpicker('refresh').selectpicker('val', '');
  }

  $('#formEgresosContables #subtotal_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #isv_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #descuento_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #nc_egresos').prop('disabled', false).val('');
  $('#formEgresosContables #total_egresos').prop('disabled', false).val('0.00');

  $('#formEgresosContables #pro_egresos_contabilidad').val("Registrar Egresos");

  $('#filePreview').html('').hide();
  $('#fileInfo').text('Ningún archivo seleccionado');
  $('#factura_pdf').val('');

  setupFileUpload();
  inicializarCalculoEgresos();
  calcularTotalEgreso();

  $('#modalEgresosContables').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

// Al resetear el form limpia el PDF y recalcula
$(document).on('reset', '#formEgresosContables', function() {
  resetPdfUI();

  setTimeout(function() {
    inicializarCalculoEgresos();
    calcularTotalEgreso();
  }, 0);
});

// ===============================
//  Modal categorías
// ===============================
function modal_categorias_contabilidad() {
  $('#formCategoriaEgresos')[0].reset();
  $('#regCategoriaEgresos').show();
  listar_categoria_egresos();

  $('#modalCategoriasEgresos').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

$("#formCategoriaEgresos").on('submit', function(e) {
  e.preventDefault();

  var form = $(this);
  var categoria = $('#categoria').val().trim();
  var url = '<?php echo SERVERURL;?>ajax/addCategoriaEgresos.php';
  var formData = form.serialize();

  swal({
    title: "¿Estás seguro?",
    text: "¿Desea registrar la categoría: " + categoria + "?",
    icon: "warning",
    buttons: {
      cancel: {
        text: "Cancelar",
        visible: true
      },
      confirm: {
        text: "¡Sí, registrar!"
      }
    },
    dangerMode: true,
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
          $('#formCategoriaEgresos')[0].reset();
          listar_categoria_egresos();
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

function modal_editar_categorias_contabilidad(categoria_gastos_id, categoria) {
  $('#formUpdateCategoriaEgresos').attr({
    'data-form': 'update',
    'action': '<?php echo SERVERURL;?>ajax/modificarCategoriaEgresos.php'
  })[0].reset();

  $('#ediCategoriaEgresos').show();

  $('#formUpdateCategoriaEgresos #categoria_gastos_id').val(categoria_gastos_id);
  $('#formUpdateCategoriaEgresos #categoria').val(categoria);
  $('#formUpdateCategoriaEgresos #pro_categoriaEgresos').val("Editar Categorias");

  $('#modalUpdateCategoriasEgresos').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });

  $('#formUpdateCategoriaEgresos').off('submit').on('submit', function(e) {
    e.preventDefault();

    var form = $(this);
    var url = form.attr('action');
    var formData = form.serialize();
    var categoria = $('#formUpdateCategoriaEgresos #categoria').val().trim();

    swal({
      title: "¿Estás seguro?",
      text: "¿Desea actualizar la categoría a: " + categoria + "?",
      icon: "warning",
      buttons: {
        cancel: {
          text: "Cancelar",
          visible: true
        },
        confirm: {
          text: "¡Sí, actualizar!"
        }
      },
      dangerMode: true,
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

            if (r.function) {
              try {
                eval(r.function);
              } catch (e) { }
            }

            listar_categoria_egresos();
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
}

function modal_reporte_categorias_contabilidad() {
  listar_reporte_categoria_egresos();

  $('#modalReporteCategorias').modal({
    show: true,
    keyboard: false,
    backdrop: 'static'
  });
}

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
//  Lista de categorías
// ===============================
var listar_categoria_egresos = function() {
  var table_categoria_egresos = $("#DatatableCategoriaEgresos").DataTable({
    destroy: true,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL; ?>core/llenarDataTableCategoriaEgresos.php"
    },
    columns: [
      { data: "nombre" },
      { defaultContent: "<button class='table_editar btn btn-dark ocultar'><span class='fas fa-edit fa-lg'></span>Editar</button>" },
      { defaultContent: "<button class='table_eliminar btn btn-dark ocultar'><span class='fa fa-trash fa-lg'></span>Eliminar</button>" }
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
        width: "15%",
        targets: 1
      },
      {
        width: "15%",
        targets: 2
      }
    ],
    autoWidth: false,
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
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Categoría Egresos',
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-success ocultar',
        exportOptions: {
          columns: [0]
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
          columns: [0]
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

  table_categoria_egresos.search('').draw();

  $('#buscar').focus();

  edit_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
  delete_categoria_gastos_dataTable("#DatatableCategoriaEgresos tbody", table_categoria_egresos);
};

var edit_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_editar");

  $(tbody).on("click", "button.table_editar", function(e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    modal_editar_categorias_contabilidad(data.categoria_gastos_id, data.nombre);
  });
};

var delete_categoria_gastos_dataTable = function(tbody, table) {
  $(tbody).off("click", "button.table_eliminar");

  $(tbody).on("click", "button.table_eliminar", function(e) {
    e.preventDefault();

    var data = table.row($(this).parents("tr")).data();

    swal({
      title: "¿Estas seguro?",
      text: "¿Desea eliminar la categoria: " + data.nombre + "?",
      icon: "warning",
      buttons: {
        cancel: {
          text: "Cancelar",
          visible: true
        },
        confirm: {
          text: "¡Sí, eliminar la categoria!"
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
  var url = '<?php echo SERVERURL;?>core/deleteCategoriaGastos.php';

  $.ajax({
    type: "POST",
    url: url,
    dataType: "json",
    data: {
      categoria_gastos_id: categoria_gastos_id,
      categoria: categoria
    },
    success: function(r) {
      if (r && r.success) {
        showNotify('success', r.title || 'Éxito', r.text || 'Categoría eliminada correctamente');
        listar_categoria_egresos();
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