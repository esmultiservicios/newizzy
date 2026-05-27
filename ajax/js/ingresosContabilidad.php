<script>
$(() => {
  // Inicializar
  listar_ingresos_contabilidad();
  getClientesIngresos();
  getCuentaIngresos();
  getEmpresaIngresos();

  // Buscar
  $('#formMainIngresosContabilidad #search').on("click", function (e) {
    e.preventDefault();
    listar_ingresos_contabilidad();
  });

  // Limpiar (reset)
  $('#formMainIngresosContabilidad').on('reset', function () {
    $(this).find('.selectpicker').val('').selectpicker('refresh');
    listar_ingresos_contabilidad();
  });

  // Cálculo automático de totales
  const camposCalculo = ["#subtotal_ingresos", "#isv_ingresos", "#descuento_ingresos", "#nc_ingresos"];
  camposCalculo.forEach(campo => {
    $("#formIngresosContables " + campo).on("keyup change", function () {
      if (parseFloat($(this).val()) < 0) {
        $(this).val(0);
        showNotify("warning", "Advertencia", "Los valores no pueden ser negativos");
      }
      calcularTotalIngreso();
    });
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
    // En tu reporte de ventas usas 'es-HN' y te da 1,234.56; replicamos
    return Number(n).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  } catch (e) {
    var s = (Number(n) || 0).toFixed(2);
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }
}
function moneyRender(data, type) {
  var n = toNumber(data);
  if (type === 'display') {
    var color = n < 0 ? 'red' : 'green';
    // IMPORTANTE: hereda tipografías para mantener tamaño uniforme
    return '<span style="color:'+color+';font-size:inherit;font-weight:inherit;line-height:inherit">L ' + formatMoney(n) + '</span>';
  }
  return n; // ordenar/filtrar con número crudo
}

// Totales del footer
var total_ingreso_footer = function () {
  var fechai = $("#formMainIngresosContabilidad #fechai").val();
  var fechaf = $("#formMainIngresosContabilidad #fechaf").val();

  $.ajax({
    url: '<?php echo SERVERURL;?>core/totalIngresoFooter.php',
    type: "POST",
    data: { "fechai": fechai, "fechaf": fechaf }
  })
  .done(function (data) {
    data = JSON.parse(data || "{}");
    $("#total-footer-ingreso").html('L ' + formatMoney(data.total));
    $("#subtotal-i").html('L ' + formatMoney(data.subtotal));
    $("#impuesto-i").html('L ' + formatMoney(data.impuesto));
    $("#descuento-i").html('L ' + formatMoney(data.descuento));
    $("#nc-i").html('L ' + formatMoney(data.nc));
  })
  .fail(function () {
    console.log("Error al cargar totales del footer");
  });
};

// ===== DataTable principal =====
var listar_ingresos_contabilidad = function () {
  var estado = $("#formMainIngresosContabilidad #estado_ingresos").val() || 1;
  var fechai = $("#formMainIngresosContabilidad #fechai").val();
  var fechaf = $("#formMainIngresosContabilidad #fechaf").val();

  if (!fechai || !fechaf) {
    showNotify("error", "Error", "Debe seleccionar un rango de fechas");
    return;
  }

  // Limpia estado guardado que puede forzar otro orden
  try {
    var _dtKey = 'DataTables_' + 'dataTableIngresosContabilidad' + '_' + window.location.pathname;
    localStorage.removeItem(_dtKey);
  } catch (e) { /* ignore */ }

  // Overlay de carga
  var $cardBody = $('#dataTableIngresosContabilidad').closest('.card').find('.card-body');
  $cardBody.append('<div class="overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>');

  var table_ingresos_contabilidad = $("#dataTableIngresosContabilidad").DataTable({
    destroy: true,
    stateSave: false,       // evita que “recuerde” un orden viejo
    orderMulti: false,
    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableIngresosContabilidad.php",
      data: { "fechai": fechai, "fechaf": fechaf, "estado": estado },
      dataSrc: function (json) {
        $cardBody.find('.overlay').remove();
        if (!json || !json.data) return [];
        if (json.data.length === 0) {
          showNotify("warning", "Advertencia", "No se encontraron registros con los filtros aplicados");
        }
        return json.data;
      },
      error: function (xhr) {
        $cardBody.find('.overlay').remove();
        showNotify("error", "Error", "No se pudieron cargar los datos");
        console.error("Error en AJAX:", xhr.responseText);
      }
    },
    columns: [
      { data: "fecha_registro" },           // YYYY-MM-DD HH:mm:ss (ordena perfecto como string)
      { data: "tipo_ingreso" },
      { data: "ingresos_id" },
      { data: "fecha" },
      { data: "nombre" },
      { data: "cliente" },
      { data: "factura" },
      { data: "subtotal",  className: "dt-body-right", render: moneyRender },
      { data: "impuesto",  className: "dt-body-right", render: moneyRender },
      { data: "descuento", className: "dt-body-right", render: moneyRender },
      { data: "total",     className: "dt-body-right", render: moneyRender },
      { data: "observacion" },
      {
        data: "estado",
        render: function (data, type) {
          if (type !== 'display') return data;
          var ok = parseInt(data, 10) === 1;
          var icon = ok ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
          var cls  = ok ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';
          return '<span class="'+cls+'" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">'+icon+(ok?'Activo':'Inactivo')+'</span>';
        }
      },
      { defaultContent: "<button class='table_editar btn ocultar'><span class='fas fa-edit'></span>Editar</button>" },
      { defaultContent: "<button class='table_reportes print_gastos btn btn-success btn ocultar'><span class='fas fa-file-download fa-lg'></span>Reporte</button>" },
      { defaultContent: "<button class='table_cancelar anular_ingreso btn btn-danger btn ocultar'><span class='fas fa-ban'></span> Anular</button>" }
    ],
    // Última fecha primero
    order: [[0, "desc"]],
    lengthMenu: lengthMenu10,
    bDestroy: true,
    language: idioma_español,
    dom: dom,
    columnDefs: [
      { width: "7.69%", targets: 0 },
      { width: "7.69%", targets: 1 },
      { width: "7.69%", targets: 2 },
      { width: "7.69%", targets: 3 },
      { width: "7.69%", targets: 4 },
      { width: "7.69%", targets: 5 },
      { width: "7.69%", targets: 6 },
      { width: "7.69%", targets: 7 },
      { width: "7.69%", targets: 8 },
      { width: "7.69%", targets: 9 },
      { width: "7.69%", targets: 10 },
      { width: "7.69%", targets: 11 },
      { width: "7.69%", targets: 12 },
      { width: "7.69%", targets: 13 }
    ],
    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Registro Ingresos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function () { listar_ingresos_contabilidad(); }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
        titleAttr: 'Agregar Ingresos',
        className: 'table_crear btn btn-primary ocultar',
        action: function () { modal_ingresos_contabilidad(); }
      },
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Registro Ingresos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] },
        className: 'table_reportes btn btn-success ocultar'
      },
      {
        extend: 'pdf',
        footer: true,
        text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
        titleAttr: 'PDF',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Reporte Registro Ingresos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        className: 'table_reportes btn btn-danger ocultar',
        exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] },
        customize: function (doc) {
          if (typeof imagen !== 'undefined' && imagen) {
            doc.content.splice(0, 0, { image: imagen, width: 100, height: 45, margin: [0,0,0,12] });
          }
        }
      }
    ],
    initComplete: function () {
      this.api().order([0,'desc']).draw();   // refuerza
      $('#buscar').focus();
    },
    drawCallback: function () {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      edit_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      view_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      anular_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      total_ingreso_footer();
    }
  });

  table_ingresos_contabilidad.search('').draw();
  $('#buscar').focus();
};

// ===== Acciones de la tabla =====
var anular_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.anular_ingreso");
  $(tbody).on("click", "button.anular_ingreso", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const rowData = table.row($btn.parents("tr")).data();
    if (!rowData) { showNotify("error","Error","No se pudo obtener la fila seleccionada."); return; }

    const ingresos_id = rowData.ingresos_id;
    const content = document.createElement("div");
    content.innerHTML = `
      <p style="margin:0 0 6px 0;">Se marcará como <b>ANULADO</b> y se registrará un <b>EGRESO espejo</b>.</p>
      <p style="margin:0;"><b>Esto NO es una compra</b>; es un <u>egreso por anulación del ingreso</u>.</p>`;

    swal({
      title: "¿Anular ingreso?",
      content: content,
      icon: "warning",
      buttons: { cancel: { text: "Cancelar", visible: true }, confirm: { text: "Sí, anular" } },
      dangerMode: true, closeOnEsc: false, closeOnClickOutside: false
    }).then(function (ok) {
      if (!ok) return;

      const cuentaId = parseInt(rowData.cuentas_id ?? rowData.cuenta_ingresos ?? 0, 10) || 0;
      const payload = {
        ingresos_id: ingresos_id,
        cuenta_ingresos: cuentaId,
        fecha_ingresos: rowData.fecha,
        factura_ingresos: rowData.factura,
        subtotal_ingresos: toNumber(rowData.subtotal_raw ?? rowData.subtotal),
        isv_ingresos:      toNumber(rowData.impuesto_raw ?? rowData.impuesto),
        descuento_ingresos:toNumber(rowData.descuento_raw ?? rowData.descuento),
        nc_ingresos:       toNumber(rowData.nc_raw ?? rowData.nc),
        total_ingresos:    toNumber(rowData.total_raw ?? rowData.total),
        observacion_ingresos: `[ANULACIÓN] Egreso espejo por anulación del ingreso ID ${ingresos_id}` +
                               (rowData.observacion ? ` | Obs: ${rowData.observacion}` : ""),
        clientes_id: rowData.clientes_id ?? 0,
        proveedor_anulacion_id: 1
      };

      if (!payload.cuenta_ingresos) { showNotify("error","Error","No se pudo determinar la cuenta contable."); return; }

      $.ajax({
        url: "<?php echo SERVERURL;?>ajax/cancelIngresoContabilidadAjax.php",
        type: "POST",
        data: payload,
        beforeSend: function () { $btn.prop("disabled", true); },
        success: function () {
          listar_ingresos_contabilidad();
          total_ingreso_footer();
          showNotify("success", "Ingreso anulado", "Se anuló el ingreso y se registró el egreso espejo.");
        },
        error: function (xhr) {
          showNotify("error", "Error", "No se pudo anular el ingreso: " + xhr.statusText);
        },
        complete: function () { $btn.prop("disabled", false); }
      });
    });
  });
};

// ===== Acciones de la tabla: EDITAR (fecha bloqueada y sin "remember") =====
var edit_reporte_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.table_editar");
  $(tbody).on("click", "button.table_editar", function () {
    var data = table.row($(this).parents("tr")).data();
    var url  = '<?php echo SERVERURL;?>core/editarIngresos.php';

    // set id al form
    $('#formIngresosContables #ingresos_id').val(data.ingresos_id);

    // 1) Cargar clientes y luego el ingreso
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
      const $form  = $('#formIngresosContables');
      const $sel   = $form.find('#recibide_ingresos');

      $sel.empty();
      if (response.success && response.data.length > 0) {
        $sel.append('<option value="">Seleccione cliente</option>');
        response.data.forEach(function(c){
          $sel.append(
            `<option value="${c.clientes_id}" data-subtext="${c.rtn || 'Sin RTN o Identidad'}">${c.nombre}</option>`
          );
        });
        $sel.selectpicker('refresh');

        // 2) Cargar el registro del ingreso
        $.ajax({
          type: 'POST',
          url: url,
          data: $form.serialize(),
          success: function (registro) {
            var v = eval(registro); 
            // v[0]=clientes_id, v[11]=recibide (texto), resto según tu PHP

            // preparar UPDATE
            $form.attr({
              'data-form': 'update',
              'action': '<?php echo SERVERURL;?>ajax/modificarIngresosAjax.php'
            })[0].reset();

            // Botones
            $('#reg_ingresosContabilidad').hide();
            $('#edi_ingresosContabilidad').show();
            $('#delete_ingresosContabilidad').hide();
            $form.find('#pro_ingresos_contabilidad').val("Editar");

            // === FECHA bloqueada y sin remember ===
            var fechaReg = v[3];
            var $fecha = $form.find('#fecha_ingresos');
            $form.addClass('modo-editar');
            $fecha.removeClass('remembered-highlight')
                  .off('change.__remember change')
                  .removeAttr('data-remember data-rem-key')
                  .val(fechaReg)
                  .prop('disabled', true);
            $fecha.closest('.col-md-3').find('.remember-hint').remove();

            $('#modalIngresosContables').one('shown.bs.modal', function(){
              var i = 0;
              (function keep(){
                var $f = $('#formIngresosContables #fecha_ingresos');
                $f.off('change.__remember change')
                  .val(fechaReg)
                  .attr('value', fechaReg)
                  .prop('defaultValue', fechaReg);
                if (++i < 8) setTimeout(keep, 60);
              })();
            });

            // ==== Resto de campos ====
            $form.find('#factura_ingresos').val(v[4]);
            $form.find('#subtotal_ingresos').val(v[5]);
            $form.find('#isv_ingresos').val(v[6]);
            $form.find('#descuento_ingresos').val(v[7]);
            $form.find('#nc_ingresos').val(v[8]);
            $form.find('#total_ingresos').val(v[9]);
            $form.find('#observacion_ingresos').val(v[10]);

            // selects
            $form.find('#cuenta_ingresos').val(v[1]).selectpicker('refresh');
            $form.find('#empresa_ingresos').val(v[2]).selectpicker('refresh');

            // ===== Cliente: usar ID de v[0] y texto de v[11] como fallback =====
            var clienteId    = (v[0] != null && v[0] !== '') ? String(v[0]) : String(data.clientes_id || data.cliente_id || '');
            var clienteTexto = v[11] || data.cliente || (clienteId ? ('Cliente #' + clienteId) : 'Cliente');

            if (clienteId) {
              // si no existe la opción (cliente inactivo, etc.), inyectarla
              if ($sel.find('option[value="'+clienteId+'"]').length === 0) {
                $sel.append('<option value="'+clienteId+'">'+clienteTexto+'</option>');
              }
              // setear valor con la API de bootstrap-select
              $sel.selectpicker('val', clienteId);
            } else {
              $sel.selectpicker('val', '');
            }

            // Deshabilitar y refrescar para que quede bloqueado visualmente
            $sel.prop('disabled', true).selectpicker('refresh');

            // Deshabilitar otros campos no editables
            $form.find('#cuenta_ingresos').prop('disabled', true).selectpicker('refresh');
            $form.find('#empresa_ingresos').prop('disabled', true).selectpicker('refresh');
            $form.find('#subtotal_ingresos, #isv_ingresos, #descuento_ingresos, #nc_ingresos, #total_ingresos').prop('disabled', true);
            $form.find('#buscar_cuenta_ingresos, #buscar_empresa_ingresos').hide();

            // Abrir modal
            $('#modalIngresosContables').modal({ show: true, keyboard: false, backdrop: 'static' });
          },
          error: function (xhr) {
            console.error('Error al cargar datos del ingreso:', xhr.responseText);
            showNotify("error", "Error", "No se pudieron cargar los datos del ingreso");
          }
        });

      } else {
        $sel.append('<option value="">No hay clientes disponibles</option>').selectpicker('refresh');
        showNotify("warning", "Advertencia", "No hay clientes disponibles para seleccionar");
      }
    }).fail(function (xhr) {
      console.error('Error al cargar clientes:', xhr.responseText);
      $('#formIngresosContables #recibide_ingresos')
        .html('<option value="">Error al cargar clientes</option>')
        .selectpicker('refresh');
      showNotify("error", "Error", "No se pudieron cargar los clientes");
    });
  });
};

// Al cerrar el modal, salir de modo-editar y re-habilitar fecha para "Nuevo"
$(document).on('hidden.bs.modal', '#modalIngresosContables', function(){
  $('#formIngresosContables').removeClass('modo-editar');
  $('#formIngresosContables #fecha_ingresos')
    .prop('disabled', false)
    .removeAttr('data-original-fecha');
});

var view_reporte_ingresos_dataTable = function (tbody, table) {
  $(tbody).off("click", "button.print_gastos");
  $(tbody).on("click", "button.print_gastos", function (e) {
    e.preventDefault();
    var data = table.row($(this).parents("tr")).data();
    printIngresos(data.ingresos_id);
  });
};

function printIngresos(ingresos_id) {
  var url = '<?php echo SERVERURL; ?>core/generaIngresos.php?ingresos_id=' + ingresos_id;
  window.open(url);
}

function modal_ingresos_contabilidad() {
  $('#formIngresosContables').attr({
    'data-form': 'save',
    'action': '<?php echo SERVERURL;?>ajax/addIngresoContabilidadAjax.php'
  });

  // salir de modo edición (el hint vuelve a mostrarse por CSS)
  var $form = $('#formIngresosContables');
  $form.removeClass('modo-editar');

  // reset total del form
  $form[0].reset();
  $form.find('select.selectpicker').val('').selectpicker('refresh');
  $form.find('input[type="text"], input[type="number"], textarea').val('');

  // habilitar fecha y re-vincular el remember para "Nuevo"
  var $f = $form.find('#fecha_ingresos');
  $f.prop('disabled', false)
    .off('change.__remember')
    .on('change.__remember', function () {
      try { localStorage.setItem('ingresos:lastFecha', this.value || ''); } catch(e){}
    });

  // >>> re-aplicar fecha memorizada tras el reset <<<
  setTimeout(function () {
    var remembered = '';
    try { remembered = localStorage.getItem('ingresos:lastFecha') || ''; } catch(e){}
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

  // botones
  $('#reg_ingresosContabilidad').show();
  $('#edi_ingresosContabilidad').hide();
  $('#delete_ingresosContabilidad').hide();

  // habilitar campos (incluye selects con bootstrap-select)
  $('#formIngresosContables #cuenta_codigo').prop("readonly", false);
  $('#formIngresosContables #cuenta_nombre').prop("readonly", false);
  $('#formIngresosContables #cuentas_activo').prop('disabled', false).prop('checked', false);

  // helper para re-habilitar y refrescar selectpicker
  function enablePicker(sel){
    var $el = $form.find(sel);
    $el.prop('disabled', false).removeAttr('disabled'); // por si quedó como atributo
    $el.selectpicker('val', ''); // vuelve al placeholder
    $el.selectpicker('refresh'); // *** IMPORTANTE ***
  }
  enablePicker('#cuenta_ingresos');
  enablePicker('#empresa_ingresos');
  enablePicker('#recibide_ingresos');

  // inputs numéricos
  $('#formIngresosContables #subtotal_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #isv_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #descuento_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #nc_ingresos').prop('disabled', false).val('');
  $('#formIngresosContables #total_ingresos').prop('disabled', false).val('');

  // buscar botones
  $('#formIngresosContables #buscar_cuenta_ingresos').show();
  $('#formIngresosContables #buscar_empresa_ingresos').show();

  $('#formIngresosContables #pro_ingresos_contabilidad').val("Registro");

  $('#modalIngresosContables').modal({ show: true, keyboard: false, backdrop: 'static' });
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
    },
    error: function () {
      showNotify("error", "Error", "No se pudieron cargar las cuentas contables");
    }
  });
}

// Foco en modales
$(document).ready(function () {
  $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function () {
    $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
  });
  $("#modalIngresosContables").on('shown.bs.modal', function () {
    $(this).find('#formIngresosContables #recibide_ingresos').focus();
  });
});

$('#btnNuevoCliente').on('click', function () { modal_clientes(); });

// ===============================
// GENERAR DOCUMENTO AUTOMÁTICO INGRESOS
// Formato: INYYYYMMDDHHMMSS
// ===============================
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
$(document).on('click', '#btnGenerarFacturaIngresos', function(e) {
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
</script>