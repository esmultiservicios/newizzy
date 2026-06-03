<script>
  //ingresosContabilidad.php - este es el js
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
  } catch (e) { }

  // Overlay de carga
  var $cardBody = $('#dataTableIngresosContabilidad').closest('.card').find('.card-body');
  $cardBody.append('<div class="overlay"><i class="fas fa-2x fa-sync-alt fa-spin"></i></div>');

  var table_ingresos_contabilidad = $("#dataTableIngresosContabilidad").DataTable({
    destroy: true,
    stateSave: false,
    orderMulti: false,

    ajax: {
      method: "POST",
      url: "<?php echo SERVERURL;?>core/llenarDataTableIngresosContabilidad.php",
      data: {
        "fechai": fechai,
        "fechaf": fechaf,
        "estado": estado
      },
      dataSrc: function (json) {
        $cardBody.find('.overlay').remove();

        if (!json || !json.data) {
          return [];
        }

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
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center align-middle",
        render: function (data, type, row) {
          if (type !== "display") {
            return "";
          }

          var estadoIngreso = parseInt(row.estado, 10);
          var ingresoActivo = estadoIngreso === 1;

          var accionesIngreso = "";

          accionesIngreso +=
            '<button type="button" class="dropdown-item accion-item accion-editar table_editar">' +
              '<span class="accion-icon accion-icon-primary">' +
                '<i class="fas fa-edit"></i>' +
              '</span>' +
              '<span class="accion-label">Editar</span>' +
            '</button>';

          accionesIngreso +=
            '<button type="button" class="dropdown-item accion-item accion-imprimir table_reportes print_gastos">' +
              '<span class="accion-icon accion-icon-success">' +
                '<i class="fas fa-file-download"></i>' +
              '</span>' +
              '<span class="accion-label">Reporte</span>' +
            '</button>';

          if (ingresoActivo) {
            accionesIngreso +=
              '<button type="button" class="dropdown-item accion-item accion-anular table_cancelar anular_ingreso">' +
                '<span class="accion-icon accion-icon-danger">' +
                  '<i class="fas fa-ban"></i>' +
                '</span>' +
                '<span class="accion-label">Reversar</span>' +
              '</button>';
          } else {
            accionesIngreso +=
              '<button type="button" class="dropdown-item accion-item accion-anulado" disabled>' +
                '<span class="accion-icon accion-icon-eliminar">' +
                  '<i class="fas fa-ban"></i>' +
                '</span>' +
                '<span class="accion-label">Ingreso inactivo</span>' +
              '</button>';
          }

          return '' +
            '<div class="dropdown acciones-dropdown">' +
              '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
              '</button>' +
              '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                accionesIngreso +
              '</div>' +
            '</div>';
        }
      },
      { data: "fecha_registro" },
      { data: "tipo_ingreso" },
      { data: "ingresos_id" },
      { data: "fecha" },
      { data: "nombre" },
      { data: "cliente" },
      { data: "factura" },
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
        data: "total",
        className: "dt-body-right",
        render: moneyRender
      },
      { data: "observacion" },
      {
        data: "estado",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          var ok = parseInt(data, 10) === 1;
          var icon = ok ? '<i class="fas fa-check-circle mr-1"></i>' : '<i class="fas fa-times-circle mr-1"></i>';
          var cls = ok ? 'badge badge-pill badge-success' : 'badge badge-pill badge-danger';

          return '<span class="' + cls + '" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">' +
                    icon +
                    (ok ? 'Activo' : 'Inactivo') +
                 '</span>';
        }
      }
    ],

    order: [[1, "desc"]],

    lengthMenu: lengthMenu10,
    bDestroy: true,
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
      { targets: 1, width: "7.69%" },
      { targets: 2, width: "7.69%" },
      { targets: 3, width: "7.69%" },
      { targets: 4, width: "7.69%" },
      { targets: 5, width: "7.69%" },
      { targets: 6, width: "7.69%" },
      { targets: 7, width: "7.69%" },
      { targets: 8, width: "7.69%", className: "text-right text-nowrap" },
      { targets: 9, width: "7.69%", className: "text-right text-nowrap" },
      { targets: 10, width: "7.69%", className: "text-right text-nowrap" },
      { targets: 11, width: "7.69%", className: "text-right text-nowrap" },
      { targets: 12, width: "7.69%" },
      { targets: 13, width: "7.69%", className: "text-center text-nowrap" }
    ],

    buttons: [
      {
        text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
        titleAttr: 'Actualizar Registro Ingresos',
        className: 'table_actualizar btn btn-secondary ocultar',
        action: function () {
          listar_ingresos_contabilidad();
        }
      },
      {
        text: '<i class="fas fas fa-plus fa-lg crear"></i> Ingresar',
        titleAttr: 'Agregar Ingresos',
        className: 'table_crear btn btn-primary ocultar',
        action: function () {
          modal_ingresos_contabilidad();
        }
      },
      {
        extend: 'excelHtml5',
        footer: true,
        text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
        titleAttr: 'Excel',
        title: 'Reporte Registro Ingresos',
        messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
        messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
        },
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
        exportOptions: {
          columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
        },
        customize: function (doc) {
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

    initComplete: function () {
      this.api().order([1, 'desc']).draw();
      $('#buscar').focus();
    },

    drawCallback: function () {
      getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

      cerrarDropdownAcciones();

      $('[title]').tooltip({
        container: "body",
        placement: "top"
      });

      edit_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      view_reporte_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      anular_ingresos_dataTable("#dataTableIngresosContabilidad tbody", table_ingresos_contabilidad);
      total_ingreso_footer();
    }
  });

  table_ingresos_contabilidad.search('').draw();

  $('#buscar').focus();
};

// ===== Acciones de la tabla: REVERSAR =====
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

  $(tbody).on("click", "button.table_editar", function () {
    var data = table.row($(this).parents("tr")).data();
    var url  = '<?php echo SERVERURL;?>core/editarIngresos.php';

    $('#formIngresosContables #ingresos_id').val(data.ingresos_id);

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

      if (response.success && response.data.length > 0) {
        $sel.append('<option value="">Seleccione cliente</option>');

        response.data.forEach(function (c) {
          $sel.append(
            `<option value="${c.clientes_id}" data-subtext="${c.rtn || 'Sin RTN o Identidad'}">${c.nombre}</option>`
          );
        });

        $sel.selectpicker('refresh');

        $.ajax({
          type: 'POST',
          url: url,
          data: $form.serialize(),
          success: function (registro) {
            var v = eval(registro);

            $form.attr({
              'data-form': 'update',
              'action': '<?php echo SERVERURL;?>ajax/modificarIngresosAjax.php'
            })[0].reset();

            $('#reg_ingresosContabilidad').hide();
            $('#edi_ingresosContabilidad').show();
            $('#delete_ingresosContabilidad').hide();

            $form.find('#pro_ingresos_contabilidad').val("Editar");

            var fechaReg = v[3];
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
              })();
            });

            $form.find('#factura_ingresos').val(v[4]);
            $form.find('#subtotal_ingresos').val(v[5]);
            $form.find('#isv_ingresos').val(v[6]);
            $form.find('#descuento_ingresos').val(v[7]);
            $form.find('#nc_ingresos').val(v[8]);
            $form.find('#total_ingresos').val(v[9]);
            $form.find('#observacion_ingresos').val(v[10]);

            $form.find('#cuenta_ingresos').val(v[1]).selectpicker('refresh');
            $form.find('#empresa_ingresos').val(v[2]).selectpicker('refresh');

            var clienteId    = (v[0] != null && v[0] !== '') ? String(v[0]) : String(data.clientes_id || data.cliente_id || '');
            var clienteTexto = v[11] || data.cliente || (clienteId ? ('Cliente #' + clienteId) : 'Cliente');

            if (clienteId) {
              if ($sel.find('option[value="' + clienteId + '"]').length === 0) {
                $sel.append('<option value="' + clienteId + '">' + clienteTexto + '</option>');
              }

              $sel.selectpicker('val', clienteId);
            } else {
              $sel.selectpicker('val', '');
            }

            $sel.prop('disabled', true).selectpicker('refresh');

            $form.find('#cuenta_ingresos').prop('disabled', true).selectpicker('refresh');
            $form.find('#empresa_ingresos').prop('disabled', true).selectpicker('refresh');

            $form.find('#subtotal_ingresos, #isv_ingresos, #descuento_ingresos, #nc_ingresos, #total_ingresos').prop('disabled', true);

            $form.find('#buscar_cuenta_ingresos, #buscar_empresa_ingresos').hide();

            $('#modalIngresosContables').modal({
              show: true,
              keyboard: false,
              backdrop: 'static'
            });
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

$(document).on('hidden.bs.modal', '#modalIngresosContables', function () {
  $('#formIngresosContables').removeClass('modo-editar');

  $('#formIngresosContables #fecha_ingresos')
    .prop('disabled', false)
    .removeAttr('data-original-fecha');
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
  window.open(url, '_blank');
}

function modal_ingresos_contabilidad() {
  $('#formIngresosContables').attr({
    'data-form': 'save',
    'action': '<?php echo SERVERURL;?>ajax/addIngresoContabilidadAjax.php'
  });

  var $form = $('#formIngresosContables');

  $form.removeClass('modo-editar');

  $form[0].reset();

  $form.find('select.selectpicker').val('').selectpicker('refresh');
  $form.find('input[type="text"], input[type="number"], textarea').val('');

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
    },
    error: function () {
      showNotify("error", "Error", "No se pudieron cargar las cuentas contables");
    }
  });
}

$(document).ready(function () {
  $("#modal_buscar_clientes_facturacion").on('shown.bs.modal', function () {
    $(this).find('#formulario_busqueda_clientes_facturacion #buscar').focus();
  });

  $("#modalIngresosContables").on('shown.bs.modal', function () {
    $(this).find('#formIngresosContables #recibide_ingresos').focus();
  });
});

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