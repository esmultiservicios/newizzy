<script>
  // ingresosContabilidad.php - este es el js
$(() => {
  // Inicializar
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
  $cardBody.find('.overlay').remove();
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
          actualizarCardsIngresosDesdeData({ data: [] });
          return [];
        }

        actualizarCardsIngresosDesdeData(json);

        if (json.data.length === 0) {
          showNotify("warning", "Advertencia", "No se encontraron registros con los filtros aplicados");
        }

        return json.data;
      },
      error: function (xhr) {
        $cardBody.find('.overlay').remove();

        actualizarCardsIngresosDesdeData({ data: [] });

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
      {
        data: "fecha_registro",
        render: function (data, type, row) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoInfo(data, "Registro del ingreso", "fas fa-clock");
        }
      },
      {
        data: "tipo_ingreso",
        className: "text-center align-middle",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoTipo(data);
        }
      },
      {
        data: "ingresos_id",
        className: "text-center align-middle",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoChip("" + data, "fas fa-hashtag");
        }
      },
      {
        data: "fecha",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoInfo(data, "Fecha factura", "fas fa-calendar-day");
        }
      },
      {
        data: "nombre",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoInfo(data, "Forma de pago / cuenta", "fas fa-wallet");
        }
      },
      {
        data: "cliente",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoInfo(data || "Sin cliente", "Recibí de", "fas fa-user");
        }
      },
      {
        data: "factura",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          return renderIngresoChip(data || "Sin factura", "fas fa-file-invoice-dollar");
        }
      },
      {
        data: "subtotal",
        className: "dt-body-right text-right",
        render: moneyRenderIngreso
      },
      {
        data: "impuesto",
        className: "dt-body-right text-right",
        render: moneyRenderIngreso
      },
      {
        data: "descuento",
        className: "dt-body-right text-right",
        render: moneyRenderIngreso
      },
      {
        data: "total",
        className: "dt-body-right text-right",
        render: moneyRenderIngreso
      },
      {
        data: "observacion",
        render: function (data, type) {
          if (type !== "display") {
            return data;
          }

          var texto = data && $.trim(data) !== "" ? data : "Sin observación";

          return '<div class="ingresos-observacion">' + escapeHtmlIngresos(texto) + '</div>';
        }
      },
      {
        data: "estado",
        className: "text-center align-middle",
        render: renderEstadoIngreso
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
      { targets: 1, width: "8%" },
      { targets: 2, width: "9%" },
      { targets: 3, width: "7%" },
      { targets: 4, width: "8%" },
      { targets: 5, width: "9%" },
      { targets: 6, width: "10%" },
      { targets: 7, width: "9%" },
      { targets: 8, width: "7%", className: "text-right text-nowrap" },
      { targets: 9, width: "7%", className: "text-right text-nowrap" },
      { targets: 10, width: "7%", className: "text-right text-nowrap" },
      { targets: 11, width: "7%", className: "text-right text-nowrap" },
      { targets: 12, width: "10%" },
      { targets: 13, width: "7%", className: "text-center text-nowrap" }
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