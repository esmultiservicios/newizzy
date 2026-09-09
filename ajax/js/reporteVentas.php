<script>
  $(() => {
    getFacturador();
    getVendedores();
    GetProductos();

    restaurarTipoFacturaReporteVentas();

    // IMPORTANTE:
    // Primero cargamos el combo de categoría y hasta después listamos.
    // Antes se llamaba listar_reporte_ventas() antes de que el select tuviera opciones,
    // por eso a veces el filtro quedaba vacío y el backend podía devolver anuladas.
    getReporteFactura(function () {
      restaurarCategoriaFacturaReporteVentas();
      setCategoriaFacturaDefaultActivas();
      refrescarSelectpickersReporteVentas();
      listar_reporte_ventas();
    });

    $('#form_main_ventas #factura_reporte, #form_main_ventas #tipo_factura_reporte')
      .off('changed.bs.select.reporteVentasMemoria change.reporteVentasMemoria')
      .on('changed.bs.select.reporteVentasMemoria change.reporteVentasMemoria', function () {
        guardarFiltrosReporteVentas();
      });

    $('#form_main_ventas #search').on('click', function (e) {
      e.preventDefault();
      setCategoriaFacturaDefaultActivas();
      guardarFiltrosReporteVentas();
      listar_reporte_ventas();
    });

    $('#form_main_ventas').on('reset', function () {
      var form = this;

      setTimeout(function () {
        $(form).find('.selectpicker').val('').selectpicker('refresh');
        $('#form_main_ventas #factura_reporte').val('1');
        $('#form_main_ventas #tipo_factura_reporte').val('1');
        guardarFiltrosReporteVentas();
        refrescarSelectpickersReporteVentas();
        listar_reporte_ventas();
      }, 50);
    });
  });

  function customRound(number) {
    var truncated = Math.floor(number * 100) / 100;
    var secondDecimal = Math.floor((number * 100) % 10);
    return (secondDecimal >= 5)
      ? parseFloat((truncated + 0.01).toFixed(2))
      : parseFloat(truncated.toFixed(2));
  }



/* =========================================================
   MEMORIA DE FILTROS - REPORTE DE VENTAS
   ---------------------------------------------------------
   Guarda el último Tipo Factura usado:
   1 = Electrónica
   4 = Proforma

   También guarda Categoría Factura:
   1 = Activas
   2 = Anuladas
========================================================= */
var RV_STORAGE_TIPO_FACTURA = 'izzy_reporte_ventas_tipo_factura';
var RV_STORAGE_CATEGORIA_FACTURA = 'izzy_reporte_ventas_categoria_factura';

function refrescarSelectpickersReporteVentas() {
    if ($.fn.selectpicker) {
        $('#form_main_ventas .selectpicker').selectpicker('refresh');
    }
}

function normalizarTipoFacturaReporte(valor) {
    valor = parseInt(valor, 10);
    return (valor === 4) ? 4 : 1;
}

function normalizarCategoriaFacturaReporte(valor) {
    valor = parseInt(valor, 10);
    return (valor === 2) ? 2 : 1;
}

function restaurarTipoFacturaReporteVentas() {
    var guardado = '1';

    try {
        guardado = localStorage.getItem(RV_STORAGE_TIPO_FACTURA) || '1';
    } catch (e) {
        guardado = '1';
    }

    $('#form_main_ventas #factura_reporte').val(String(normalizarTipoFacturaReporte(guardado)));
    refrescarSelectpickersReporteVentas();
}

function restaurarCategoriaFacturaReporteVentas() {
    var guardado = '1';

    try {
        guardado = localStorage.getItem(RV_STORAGE_CATEGORIA_FACTURA) || '1';
    } catch (e) {
        guardado = '1';
    }

    $('#form_main_ventas #tipo_factura_reporte').val(String(normalizarCategoriaFacturaReporte(guardado)));
    refrescarSelectpickersReporteVentas();
}

function guardarFiltrosReporteVentas() {
    var tipoFactura = getTipoFacturaReporte();
    var categoriaFactura = getCategoriaFacturaReporte();

    try {
        localStorage.setItem(RV_STORAGE_TIPO_FACTURA, String(tipoFactura));
        localStorage.setItem(RV_STORAGE_CATEGORIA_FACTURA, String(categoriaFactura));
    } catch (e) {}
}

function getTipoFacturaReporte() {
    return normalizarTipoFacturaReporte($('#form_main_ventas #factura_reporte').val() || 1);
}

function registroReporteEsAnulado(row) {
    if (!row) {
        return false;
    }

    var estadoFactura = parseInt(row.estado || row.factura_estado || row.Estado || 0, 10);
    var estadoProforma = parseInt(row.proforma_estado || row.estado_proforma || 0, 10);

    return estadoFactura === 4 || estadoProforma === 4;
}

function filtrarDatosReporteVentasCliente(json) {
    var data = (json && json.data) ? json.data : [];
    var categoria = getCategoriaFacturaReporte();
    var tipoFactura = getTipoFacturaReporte();

    return data.filter(function (row) {
        var doc = parseInt(row.documento_id || row.documento || row.tipo_documento_id || 0, 10);
        var anulada = registroReporteEsAnulado(row);

        // Seguridad visual: aunque el PHP mande anuladas, no se muestran en Activas.
        if (categoria === 2) {
            if (!anulada) {
                return false;
            }
        } else {
            if (anulada) {
                return false;
            }
        }

        // Seguridad visual por Tipo Factura si el backend no filtra.
        if (doc > 0) {
            if (tipoFactura === 4 && doc !== 4) {
                return false;
            }

            if (tipoFactura === 1 && doc === 4) {
                return false;
            }
        }

        return true;
    });
}

/* =========================================================
   FILTRO CATEGORÍA FACTURA
   ---------------------------------------------------------
   1 = Activas  => estados 2 y 3
   2 = Anuladas => estado 4
   Por defecto SIEMPRE debe consultar Activas.
========================================================= */
function setCategoriaFacturaDefaultActivas() {
    var $select = $('#form_main_ventas #tipo_factura_reporte');

    if (!$select.val() || $select.val() === '' || $select.val() === null) {
        $select.val('1');
    }

    if ($.fn.selectpicker) {
        $select.selectpicker('refresh');
    }
}

function getCategoriaFacturaReporte() {
    var valor = $('#form_main_ventas #tipo_factura_reporte').val();

    if (valor === undefined || valor === null || valor === '') {
        return 1;
    }

    valor = parseInt(valor, 10);

    if (isNaN(valor) || (valor !== 1 && valor !== 2)) {
        return 1;
    }

    return valor;
}

function facturaEstaAnuladaReporte(row) {
    return registroReporteEsAnulado(row);
}

/* =========================================================
   HEADER Y FOOTER DINÁMICO - REPORTE DE VENTAS
   ========================================================= */

   function construirHeaderFooterDataTablaReporteVentas() {
    var $tabla = $("#dataTablaReporteVentas");

    $tabla.empty();

    $tabla.append(
        '<thead>' +
            '<tr>' +
                '<th>Acciones</th>' +
                '<th>Fecha</th>' +
                '<th>Tipo</th>' +
                '<th>Cliente</th>' +
                '<th>Factura</th>' +
                '<th>SubTotal</th>' +
                '<th>ISV</th>' +
                '<th>Descuento</th>' +
                '<th>Total Ventas</th>' +
                '<th>Ganancia</th>' +
                '<th>Vendedor</th>' +
                '<th>Facturador</th>' +
            '</tr>' +
        '</thead>' +
        '<tfoot class="bg-secondary">' +
            '<tr>' +
                '<td colspan="5">Total</td>' +
                '<td id="subtotal-i"></td>' +
                '<td id="impuesto-i"></td>' +
                '<td id="descuento-i"></td>' +
                '<td id="total-footer-ingreso"></td>' +
                '<td id="ganancia"></td>' +
                '<td colspan="2"></td>' +
            '</tr>' +
        '</tfoot>'
    );
}


/* =========================================================
   LISTADO - REPORTE DE VENTAS
   ========================================================= */

var listar_reporte_ventas = function () {
    try {
        var _dtKey = 'DataTables_' + 'dataTablaReporteVentas' + '_' + window.location.pathname;
        localStorage.removeItem(_dtKey);
    } catch (e) {}

    setCategoriaFacturaDefaultActivas();
    let tipo_factura_reporte = getCategoriaFacturaReporte();
    let factura = getTipoFacturaReporte();
    guardarFiltrosReporteVentas();

    var fechai = $("#form_main_ventas #fechai").val();
    var fechaf = $("#form_main_ventas #fechaf").val();
    var facturador = $("#form_main_ventas #facturador").val();
    var vendedor = $("#form_main_ventas #vendedor").val();

    if ($.fn.DataTable.isDataTable("#dataTablaReporteVentas")) {
        $("#dataTablaReporteVentas").DataTable().clear().destroy();
    }

    construirHeaderFooterDataTablaReporteVentas();

    var table_reporteVentas = $("#dataTablaReporteVentas").DataTable({
        destroy: true,
        footer: true,
        stateSave: false,
        orderMulti: false,

        ajax: {
            method: "POST",
            url: "<?php echo SERVERURL;?>core/llenarDataTableReporteVentas.php",
            data: {
                "tipo_factura_reporte": tipo_factura_reporte,
                "categoria_factura": tipo_factura_reporte,
                "estado_factura": tipo_factura_reporte,
                "facturador": facturador,
                "vendedor": vendedor,
                "fechai": fechai,
                "fechaf": fechaf,
                "factura": factura
            },
            dataSrc: function (json) {
                return filtrarDatosReporteVentasCliente(json);
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

                    var acciones = '';

                    acciones +=
                        '<button type="button" class="dropdown-item accion-item table_reportes detalle_factura">' +
                            '<span class="accion-icon accion-icon-primary">' +
                                '<i class="fas fa-search"></i>' +
                            '</span>' +
                            '<span class="accion-label">Ver detalle</span>' +
                        '</button>';

                    acciones +=
                        '<button type="button" class="dropdown-item accion-item table_reportes print_factura">' +
                            '<span class="accion-icon accion-icon-success">' +
                                '<i class="fas fa-file-download"></i>' +
                            '</span>' +
                            '<span class="accion-label">Factura</span>' +
                        '</button>';

                    acciones +=
                        '<button type="button" class="dropdown-item accion-item table_reportes print_comprobante">' +
                            '<span class="accion-icon accion-icon-success">' +
                                '<i class="far fa-file-pdf"></i>' +
                            '</span>' +
                            '<span class="accion-label">Comprobante</span>' +
                        '</button>';

                    if (!facturaEstaAnuladaReporte(row)) {
                        acciones +=
                            '<button type="button" class="dropdown-item accion-item table_reportes email_factura">' +
                                '<span class="accion-icon accion-icon-secondary">' +
                                    '<i class="fas fa-paper-plane"></i>' +
                                '</span>' +
                                '<span class="accion-label">Enviar</span>' +
                            '</button>';

                        acciones +=
                            '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar cancelar_factura">' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-ban"></i>' +
                                '</span>' +
                                '<span class="accion-label">Anular</span>' +
                            '</button>';
                    } else {
                        acciones +=
                            '<button type="button" class="dropdown-item accion-item accion-eliminar" disabled>' +
                                '<span class="accion-icon accion-icon-eliminar">' +
                                    '<i class="fas fa-ban"></i>' +
                                '</span>' +
                                '<span class="accion-label">Factura anulada</span>' +
                            '</button>';
                    }

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +
                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +
                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +
                                acciones +
                            '</div>' +
                        '</div>';
                }
            },
            {
                data: "fecha"
            },
            {
                data: "tipo_documento",
                render: function (data, type) {
                    if (type !== 'display') return data;

                    var icon = data === 'Crédito'
                        ? '<i class="fas fa-clock mr-1"></i>'
                        : '<i class="fas fa-check-circle mr-1"></i>';

                    var badgeClass = data === 'Crédito'
                        ? 'badge badge-pill badge-warning'
                        : 'badge badge-pill badge-success';

                    return '<span class="' + badgeClass + '" style="font-size:.95rem;padding:.5em .8em;font-weight:600;">' +
                        icon +
                        data +
                    '</span>';
                }
            },
            {
                data: "cliente"
            },
            {
                data: {
                    display: 'numero',
                    _: 'numero',
                    filter: 'numero',
                    sort: 'numero_sort'
                },
                render: function (data, type, row) {
                    if (type !== 'display') return data;

                    if (parseInt(row.documento_id, 10) === 4) {
                        const est = parseInt(row.proforma_estado, 10);
                        const isAnulada = facturaEstaAnuladaReporte(row);
                        const isCerrada = (est === 2);

                        const badge = isAnulada
                            ? '<span class="badge badge-danger ml-2">Anulada</span>'
                            : (isCerrada
                                ? '<span class="badge badge-secondary ml-2">Cerrada</span>'
                                : '<span class="badge badge-info ml-2">Abierta</span>');

                        const cerrarBtn = (!isAnulada && !isCerrada)
                            ? `<button class="btn btn-sm btn-danger ml-2 cerrar_proforma"
                                 data-toggle="tooltip" data-placement="top" title="Cerrar proforma">
                                 <i class="fas fa-times-circle"></i>
                               </button>`
                            : '';

                        return `
                            <div class="d-flex align-items-center flex-nowrap" style="gap:.5rem;white-space:nowrap;">
                                <span>${row.numero}</span>
                                ${badge}
                                ${cerrarBtn}
                            </div>`;
                    }

                    return row.numero;
                }
            },
            {
                data: "subtotal",
                render: moneyCell
            },
            {
                data: "isv",
                render: moneyCell
            },
            {
                data: "descuento",
                render: moneyCell
            },
            {
                data: "total",
                render: function (data, type, row) {
                    const total = parseFloat(row.total) || 0;
                    const pagado = parseFloat(row.monto_pagado || 0);

                    const numberFormatted = 'L ' + total.toLocaleString('es-HN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    if (type !== 'display') return numberFormatted;

                    if (facturaEstaAnuladaReporte(row)) {
                        return `
                            <div class="total-container" style="display:flex;flex-direction:column;align-items:flex-end;">
                                <div style="background:#fff;border-left:6px solid #dc3545;padding:8px 12px;border-radius:.5rem;box-shadow:0 1px 5px rgba(0,0,0,.08);font-size:1.1em;font-weight:bold;color:#212529;">
                                    ${numberFormatted}
                                </div>
                                <div class="status-badge bg-danger text-white"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Factura anulada"
                                    style="font-size:.75em;padding:.4em .8em;border-radius:999px;display:inline-block;line-height:1.2;margin-top:6px;font-weight:600;letter-spacing:.2px;text-transform:uppercase;white-space:nowrap;min-width:fit-content;">
                                    <i class="fas fa-ban mr-1"></i>Anulada
                                </div>
                            </div>`;
                    }

                    if (parseInt(row.documento_id, 10) === 4) {
                        const est = parseInt(row.proforma_estado, 10);
                        const isCerrada = (est === 2);
                        const estado = isCerrada ? 'Cerrada' : 'Abierta';
                        const colorHex = isCerrada ? '#6c757d' : '#17a2b8';
                        const badgeCls = isCerrada ? 'bg-secondary' : 'bg-info';
                        const icon = isCerrada
                            ? '<i class="fas fa-lock mr-1"></i>'
                            : '<i class="fas fa-folder-open mr-1"></i>';

                        return `
                            <div class="total-container" style="display:flex;flex-direction:column;align-items:flex-end;">
                                <div style="background:#fff;border-left:6px solid ${colorHex};padding:8px 12px;border-radius:.5rem;box-shadow:0 1px 5px rgba(0,0,0,.08);font-size:1.1em;font-weight:bold;color:#212529;">
                                    ${numberFormatted}
                                </div>
                                <div class="status-badge ${badgeCls} text-white"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Estado de la proforma: ${estado}"
                                    style="font-size:.75em;padding:.4em .8em;border-radius:999px;display:inline-block;line-height:1.2;margin-top:6px;font-weight:600;letter-spacing:.2px;text-transform:uppercase;white-space:nowrap;min-width:fit-content;">
                                    ${icon}${estado}
                                </div>
                            </div>`;
                    }

                    let estado = 'Pendiente';
                    let badgeColor = 'bg-danger';
                    let estadoClass = 'text-white';
                    let icon = '<i class="fas fa-exclamation-circle mr-1"></i>';

                    if (pagado >= total - 0.01) {
                        estado = 'Pagado';
                        badgeColor = 'bg-success';
                        icon = '<i class="fas fa-check-circle mr-1"></i>';
                    } else if (pagado > 0 && pagado < total) {
                        estado = 'Abonado';
                        badgeColor = 'bg-secondary';
                        icon = '<i class="fas fa-check-double mr-1"></i>';
                    }

                    const borde = (badgeColor === 'bg-success')
                        ? '#28a745'
                        : (badgeColor === 'bg-secondary')
                            ? '#6c757d'
                            : '#dc3545';

                    return `
                        <div class="total-container" style="display:flex;flex-direction:column;align-items:flex-end;min-width:140px;">
                            <div style="background:#fff;border-left:6px solid ${borde};padding:8px 12px;border-radius:.5rem;box-shadow:0 1px 5px rgba(0,0,0,.08);font-size:1.1em;font-weight:bold;color:#212529;min-width:110px;text-align:right;">
                                ${numberFormatted}
                            </div>
                            <div class="status-badge ${badgeColor} ${estadoClass}"
                                title="${pagado > 0 ? ('Pagado: L ' + pagado.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : 'Sin pagos registrados'}"
                                style="font-size:.7em;padding:.4em .8em;border-radius:999px;display:inline-block;line-height:1.2;margin-top:5px;white-space:nowrap;font-weight:600;letter-spacing:0;min-width:fit-content;text-align:center;max-width:none;">
                                ${icon}${estado}
                            </div>
                        </div>`;
                }
            },
            {
                data: "ganancia",
                render: moneyCell
            },
            {
                data: "vendedor"
            },
            {
                data: "facturador"
            }
        ],

        order: [[4, "desc"]],

        columnDefs: [
            {
                targets: 0,
                width: "8%",
                orderable: false,
                searchable: false,
                className: "text-center text-nowrap align-middle"
            },
            {
                targets: 4,
                type: 'num'
            }
        ],

        lengthMenu: lengthMenu10,
        bDestroy: true,
        language: idioma_español,
        dom: dom,

        footerCallback: function (row, data) {
            var totalSubtotal = data.reduce((acc, r) => acc + (parseFloat(r.subtotal) || 0), 0);
            var totalIsv = data.reduce((acc, r) => acc + (parseFloat(r.isv) || 0), 0);
            var totalDescuento = data.reduce((acc, r) => acc + (parseFloat(r.descuento) || 0), 0);
            var totalVentas = data.reduce((acc, r) => acc + (parseFloat(r.total) || 0), 0);
            var totalGanancia = data.reduce((acc, r) => acc + (parseFloat(r.ganancia) || 0), 0);

            var fmt = new Intl.NumberFormat('es-HN', {
                style: 'currency',
                currency: 'HNL',
                minimumFractionDigits: 2
            });

            $('#subtotal-i').html(fmt.format(totalSubtotal));
            $('#impuesto-i').html(fmt.format(totalIsv));
            $('#descuento-i').html(fmt.format(totalDescuento));
            $('#total-footer-ingreso').html(fmt.format(totalVentas));
            $('#ganancia').html(fmt.format(totalGanancia));
        },

        buttons: [
            {
                text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
                titleAttr: 'Actualizar Reporte de Ventas',
                className: 'table_actualizar btn btn-secondary ocultar',
                action: function () {
                    listar_reporte_ventas();
                }
            },
            {
                text: '<i class="fas fa-search fa-lg crear"></i> Reporte de Pagos',
                titleAttr: 'Reporte de Pagos',
                className: 'table_crear btn btn-primary ocultar',
                action: function () {
                    modal_pagos_cliente();
                }
            },
            {
                text: '<i class="fas fa-search fa-lg crear"></i> Detalle Ventas',
                titleAttr: 'Detalle Ventas',
                className: 'table_crear btn btn-primary ocultar',
                action: function () {
                    modal_detalles();
                }
            },
            {
                extend: 'excelHtml5',
                footer: true,
                text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
                titleAttr: 'Excel',
                title: 'Reporte de Ventas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
                className: 'table_reportes btn btn-success ocultar'
            },
            {
                extend: 'pdf',
                footer: true,
                orientation: 'landscape',
                text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
                titleAttr: 'PDF',
                pageSize: 'LETTER',
                title: 'Reporte de Ventas',
                messageTop: 'Fecha desde: ' + convertDateFormat(fechai) + ' Fecha hasta: ' + convertDateFormat(fechaf),
                messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
                className: 'table_reportes btn btn-danger ocultar',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
                customize: function (doc) {
                    if (imagen) {
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

        drawCallback: function () {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());

            if (typeof cerrarDropdownAcciones === "function") {
                cerrarDropdownAcciones();
            }

            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    table_reporteVentas.search('').draw();
    $('#buscar').focus();

    view_detalle_factura_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_correo_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_reporte_comprobante_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_anular_facturas_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
    view_cerrar_proforma_dataTable("#dataTablaReporteVentas tbody", table_reporteVentas);
};

  function moneyCell(data, type) {
    var number = $.fn.dataTable.render.number(',', '.', 2, 'L ').display(data);
    if (type === 'display') {
      let color = (parseFloat(data) < 0) ? 'red' : 'green';
      return '<span style="color:' + color + '">' + number + '</span>';
    }
    return number;
  }

  // Cerrar Proforma
  var view_cerrar_proforma_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.cerrar_proforma");
    $(tbody).on("click", "button.cerrar_proforma", function (e) {
      e.preventDefault();
      const data = table.row($(this).parents("tr")).data();
      cerrarProforma(data.facturas_proforma_id, data.facturas_id, data.numero);
    });
  };

  function cerrarProforma(facturas_proforma_id, facturas_id, numero) {
    swal({
      title: "Cerrar proforma",
      text: `Esta acción cierra la proforma ${numero} y la cuenta por cobrar asociada.\n¿Desea continuar?`,
      content: { element: "input", attributes: { placeholder: "Comentario (opcional)", type: "text" } },
      icon: "warning",
      buttons: { cancel: "Cancelar", confirm: { text: "Sí, cerrar", closeModal: false } },
      dangerMode: true, closeOnEsc: false, closeOnClickOutside: false
    }).then((comentario) => {
      if (comentario === null) return;
      $.ajax({
        url: '<?php echo SERVERURL; ?>core/facturas/cerrarProforma.php',
        type: 'POST',
        dataType: 'json',
        data: {
          facturas_proforma_id: facturas_proforma_id,
          facturas_id: facturas_id,
          comentario: comentario || ''
        },
        success: function (r) {
          swal.close();
          if (r && r.success) {
            showNotify('success', r.title || 'Éxito', r.message || 'Proforma cerrada.');
            listar_reporte_ventas(); // refresca y quita el botón
          } else {
            showNotify('error', r.title || 'Error', (r && r.message) ? r.message : 'No se pudo cerrar la proforma.');
          }
        },
        error: function (xhr) {
          swal.close();
          showNotify('error', 'Error', xhr.responseText || 'Error de red.');
        }
      });
    });
  }

  var view_detalle_factura_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.detalle_factura");
    $(tbody).on("click", "button.detalle_factura", function (e) {
      e.preventDefault();
      var data = table.row($(this).parents("tr")).data();
      mostrarDetalleFactura(data.facturas_id);
    });
  };

  function mostrarDetalleFactura(facturas_id) {
    var $modal = $('#modalDetalleFactura');
    $modal.modal('show');

    $.ajax({
      url: '<?php echo SERVERURL; ?>core/getDetalleFacturaReporteVentas.php',
      type: 'POST',
      data: { facturas_id },
      dataType: 'json',
      success: function (response) {
        if (response.success && response.data) {
          var factura = response.data.cabecera;
          var detalles = response.data.detalle;

          $modal.find('#numero-factura-modal').text(factura.numero_factura || 'N/A');
          $modal.find('#fecha-factura').text(factura.fecha || 'N/A');
          $modal.find('#cliente-factura').text(factura.cliente || 'N/A');
          $modal.find('#tipo-factura').text(factura.tipo_factura || 'N/A');

          var estadoNum = parseInt(factura.estado) || 0;
          var estadoBadge = '';
          switch (estadoNum) {
            case 2: estadoBadge = 'badge-success">Pagada'; break;
            case 3: estadoBadge = 'badge-warning text-dark">Crédito'; break;
            case 4: estadoBadge = 'badge-danger">Anulada'; break;
            default: estadoBadge = 'badge-secondary">Pendiente';
          }
          $modal.find('#estado-factura').html('<span class="badge badge-pill ' + estadoBadge + '</span>');

          $modal.find('#subtotal-factura').text(formatMoney(factura.subtotal || 0));
          $modal.find('#total-factura').text(formatMoney(factura.total || 0));
          $modal.find('#notas-factura').text(factura.notas || 'No hay notas');

          var detalleHtml = '';
          if (detalles && detalles.length > 0) {
            detalles.forEach(function (item) {
              detalleHtml += `
                <tr>
                  <td>${item.producto || 'Producto no especificado'}</td>
                  <td class="text-center">${item.cantidad || 0} ${item.medida || ''}</td>
                  <td class="text-right">${formatMoney(item.precio || 0)}</td>
                  <td class="text-right">${formatMoney(item.isv_valor || 0)}</td>
                  <td class="text-right">${formatMoney(item.descuento || 0)}</td>
                  <td class="text-right">${formatMoney(item.subtotal || 0)}</td>
                </tr>`;
            });
          } else {
            detalleHtml = `
              <tr>
                <td colspan="6" class="text-center text-muted py-4">
                  No se encontraron detalles para esta factura
                </td>
              </tr>`;
          }

          $modal.find('#detalle-factura-body').html(detalleHtml);

          $modal.find('#btn-imprimir-factura').off('click').on('click', function () {
            if (typeof printBillReporteVentas === 'function') {
              printBillReporteVentas(facturas_id);
            }
          });

        } else {
          $modal.find('.modal-body').html(`
            <div class="alert alert-danger">
              ${response.message || 'Error al cargar los detalles'}
            </div>`);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error en AJAX:', error, xhr.responseText);
        $modal.find('.modal-body').html(`
          <div class="alert alert-danger">
            Error al cargar los datos: ${error}
            <button class="btn btn-sm btn-outline-primary mt-2" onclick="mostrarDetalleFactura(${facturas_id})">
              <i class="fas fa-sync-alt"></i> Reintentar
            </button>
          </div>`);
      }
    });
  }

  function formatMoney(amount) {
    try {
      var number = parseFloat(amount) || 0;
      return 'L ' + number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    } catch (e) {
      return 'L 0.00';
    }
  }

  var view_correo_facturas_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.email_factura");

    $(tbody).on("click", "button.email_factura", function (e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.facturas_id) {
            showNotify('error', 'Error', 'No se pudo obtener la factura seleccionada.');
            return false;
        }

        swal({
            title: "Enviar factura",
            text: "¿Desea enviar la factura No. " + data.numero + " por correo electrónico?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true,
                    closeModal: true
                },
                confirm: {
                    text: "Sí, enviar",
                    closeModal: true
                }
            },
            dangerMode: false,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((confirmado) => {
            if (!confirmado) {
                return false;
            }

            showNotify(
                'info',
                'Enviando',
                'Enviando factura por correo, por favor espere...'
            );

            mailBill(data.facturas_id);
        });
    });
};

  var view_reporte_facturas_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.print_factura");
    $(tbody).on("click", "button.print_factura", function (e) {
      e.preventDefault();
      var data = table.row($(this).parents("tr")).data();
      printBillReporteVentas(data.facturas_id);
    });
  };

  var view_reporte_comprobante_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.print_comprobante");
    $(tbody).on("click", "button.print_comprobante", function (e) {
      e.preventDefault();
      var data = table.row($(this).parents("tr")).data();
      printBillComprobanteReporteVentas(data.facturas_id);
    });
  };

  var view_anular_facturas_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.cancelar_factura");
    $(tbody).on("click", "button.cancelar_factura", function (e) {
        e.preventDefault();

        var data = table.row($(this).parents("tr")).data();

        if (!data || !data.facturas_id) {
            showNotify('error', 'Error', 'No se pudo obtener la factura seleccionada');
            return false;
        }

        if (facturaEstaAnuladaReporte(data)) {
            showNotify('info', 'Factura anulada', 'Esta factura ya está anulada.');
            return false;
        }

        if (typeof validarAdminSistema !== 'function') {
            showNotify('error', 'Validación no disponible', 'No está cargado el JS de autenticación administrativa.');
            return false;
        }

        var facturaId = data.facturas_id;
        var numeroFactura = data.number || data.numero || data.factura || data.numero_factura || data.facturas_id;

        validarAdminSistema(function (permitido) {
            if (permitido !== true) {
                return;
            }

            anularFacturas(facturaId);
        }, {
            mensaje: 'Para anular esta factura debe validar un administrador.',
            modulo: 'Facturación',
            accion: 'Anular factura',
            referencia_id: facturaId,
            referencia_texto: numeroFactura,
            motivo: 'Validación requerida para anular factura'
        });

        return false;
    });
};

  function getReporteFactura(callback) {
    $.ajax({
      type: "POST",
      url: '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php',
      async: true,
      success: function (data) {
        $('#form_main_ventas #tipo_factura_reporte').html(data);
        restaurarCategoriaFacturaReporteVentas();
        setCategoriaFacturaDefaultActivas();

        if (typeof callback === 'function') {
          callback();
        }
      },
      error: function () {
        setCategoriaFacturaDefaultActivas();

        if (typeof callback === 'function') {
          callback();
        }
      }
    });
  }

  function getFacturador() {
    $.ajax({
      type: "POST",
      url: '<?php echo SERVERURL;?>core/getFacturador.php',
      async: true,
      success: function (data) {
        $('#form_main_ventas #facturador').html(data).selectpicker('refresh');
      }
    });
  }

  function getVendedores() {
    $.ajax({
      type: "POST",
      url: '<?php echo SERVERURL;?>core/getColaboradores.php',
      async: true,
      success: function (data) {
        $('#form_main_ventas #vendedor').html(data).selectpicker('refresh');
        $('#FormDetalleVentas #DetalleVendedores').html(data).selectpicker('refresh');
      }
    });
  }

  function GetProductos() {
    $.ajax({
      type: "POST",
      url: '<?php echo SERVERURL;?>core/getProductos.php',
      async: true,
      success: function (data) {
        $('#FormDetalleVentas #DetallesProductos').html(data).selectpicker('refresh');
      }
    });
  }

  function modal_detalles() {
    getVendedores();
    GetProductos();
    ListarDetalleVenas();
    $('#ModalDetalleVentas').modal({ show: true, keyboard: false, backdrop: 'static' });
  }

 var ListarDetalleVenas = function () {
    var fechai = $("#FormDetalleVentas #DetallesFechai").val();
    var fechaf = $("#FormDetalleVentas #DetallesFechaf").val();
    var productos_id = $("#FormDetalleVentas #DetallesProductos").val();
    var colaboradores_id = $("#FormDetalleVentas #DetalleVendedores").val();

    var table_puestos = $("#DatatableDetalleVentas").DataTable({
      destroy: true,
      ajax: {
        method: "POST",
        url: "<?php echo SERVERURL;?>core/llenarDataTableDetalleVentas.php",
        data: {
          "fechai": fechai,
          "fechaf": fechaf,
          "productos_id": productos_id,
          "colaboradores_id": colaboradores_id,
          "tipo_factura_reporte": getCategoriaFacturaReporte(),
          "categoria_factura": getCategoriaFacturaReporte(),
          "estado_factura": getCategoriaFacturaReporte(),
          "factura": getTipoFacturaReporte()
        },
        dataSrc: function (json) {
          return filtrarDatosReporteVentasCliente(json);
        }
      },
      columns: [
        { data: "Fecha" },
        { data: "Producto" },
        { data: "numero" },
        { data: "Cliente" },
        { data: "Precio",    render: moneyCell },
        { data: "Cantidad" },
        { data: "ISV",       render: moneyCell },
        { data: "Descuento", render: moneyCell },
        { data: "Total",     render: moneyCell },
        { data: "Vendedor" }
      ],
      lengthMenu: lengthMenu,
      stateSave: true,
      bDestroy: true,
      language: idioma_español,
      dom: dom,
      footerCallback: function (row, data) {
        var totalPrecio = 0, totalCantidad = 0, totalISV = 0, totalDescuento = 0, totalTotal = 0;
        data.forEach(function (r) {
          totalPrecio   += parseFloat(r.Precio)   || 0;
          totalCantidad += parseFloat(r.Cantidad) || 0;
          totalISV      += parseFloat(r.ISV)      || 0;
          totalDescuento+= parseFloat(r.Descuento)|| 0;
          totalTotal    += parseFloat(r.Total)    || 0;
        });
        var fmt = new Intl.NumberFormat('es-HN',{style:'currency',currency:'HNL',minimumFractionDigits:2});
        $('#total-precio').html(fmt.format(totalPrecio));
        $('#total-cantidad').html(fmt.format(totalCantidad));
        $('#total-isv').html(fmt.format(totalISV));
        $('#total-descuento').html(fmt.format(totalDescuento));
        $('#total-total').html(fmt.format(totalTotal));
      },
      columnDefs: [
        { width: "5%",  targets: 0 },
        { width: "85%", targets: 1 },
        { width: "5%",  targets: 2 },
        { width: "5%",  targets: 3 }
      ],
      buttons: [
        {
          text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
          titleAttr: 'Actualizar Puestos',
          className: 'table_actualizar btn btn-secondary ocultar',
          action: function () { ListarDetalleVenas(); }
        },
        {
          extend: 'excelHtml5',
          text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
          titleAttr: 'Excel',
          title: 'Reporte Detalle de Ventas',
          messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
          className: 'table_reportes btn btn-success ocultar'
        },
        {
          extend: 'pdf',
          orientation: 'landscape',
          text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
          titleAttr: 'PDF',
          title: 'Reporte Detalle de Ventas',
          messageBottom: 'Fecha de Reporte: ' + convertDateFormat(today()),
          className: 'table_reportes btn btn-danger ocultar',
          customize: function (doc) {
            if (imagen) {
              doc.content.splice(1, 0, { margin:[0,0,0,12], alignment:'left', image: imagen, width:100, height:45 });
            }
          }
        }
      ],
      drawCallback: function () {
        getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
      }
    });

    table_puestos.search('').draw();
    $('#buscar').focus();
  };

// =============================
// PAGOS POR CLIENTE (FIX DEFINITIVO)
// =============================
var dtPagosCliente = null;

function listar_pagos_cliente(){
  const $m = $('#ModalPagosCliente');

  if (!dtPagosCliente) {
    dtPagosCliente = $("#DataTablePagosCliente").DataTable({
      destroy: true,
      processing: true,
      deferRender: true,
      stateSave: false,
      ajax: {
        method: "POST",
        url: "<?php echo SERVERURL; ?>core/facturas/llenarDataTablePagosCliente.php",
        // ¡LEER SIEMPRE DESDE EL DOM EN CADA REQUEST!
        data: function(d){
          d.fechai     = $m.find('#PagosFechai').val();
          d.fechaf     = $m.find('#PagosFechaf').val();
          d.cliente_id = $m.find('#ClientePagos').val() || '';
        },
        dataSrc: function(json){
          // útil para ver qué está regresando el PHP:
          // console.log('RESP PAGOS:', json);
          return json.data || [];
        }
      },
      columns: [
        { defaultContent: "<button class='table_reportes print_factura_pagos btn btn-success'><span class='fas fa-file-download fa-lg'></span> Factura</button>" },        
        { data: "fecha_pago" },
        { data: "numero" },
        { data: "fecha_factura" },
        { data: "total_factura", render: d => moneyFmt(d) },
        { data: "aplicado",      render: d => moneyFmt(d) },
        { data: "efectivo",      render: d => moneyFmt(d) },
        { data: "tarjeta",       render: d => moneyFmt(d) },
        { data: "cambio",        render: d => moneyFmt(d) },
        { data: "metodo" },
        { data: "tipo" },
        {
          data: "estado",
          render: d => {
            let cls = "badge-secondary";
            let icon = "fa-circle";
            let text = d || "";

            if (d === "Pagado") {
              cls = "badge-success";
              icon = "fa-check-circle";
            } else if (d === "Cancelado") {
              cls = "badge-danger";
              icon = "fa-times-circle";
            } else if (d === "Pendiente") {
              cls = "badge-warning";
              icon = "fa-exclamation-circle";
            }

            return `
              <span class="badge badge-pill ${cls}">
                <i class="fas ${icon} mr-1"></i> ${text}
              </span>
            `;
          }
        },
        { data: "usuario" }
      ],
      order: [[1,'desc']],
      lengthMenu: typeof lengthMenu10 !== 'undefined' ? lengthMenu10 : [[10,25,50,-1],[10,25,50,"Todos"]],
      language: typeof idioma_español !== 'undefined' ? idioma_español : {},
      dom: typeof dom !== 'undefined' ? dom : 'Bfrtip',
      buttons: [
        {
          text: '<i class="fas fa-sync-alt fa-lg"></i> Actualizar',
          className: 'table_actualizar btn btn-secondary ocultar',
          action: function(){ dtPagosCliente.ajax.reload(); }
        },
        {
          extend: 'excelHtml5',
          text: '<i class="fas fa-file-excel fa-lg"></i> Excel',
          className: 'table_reportes btn btn-success ocultar',
          title: 'Pagos del Cliente',
          exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12] }
        },
        {
          extend: 'pdfHtml5',
          text: '<i class="fas fa-file-pdf fa-lg"></i> PDF',
          className: 'table_reportes btn btn-danger ocultar',
          orientation: 'landscape',
          pageSize: 'LETTER',
          title: 'Pagos del Cliente',
          exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12] }
        }
      ],
      footerCallback: function(row, data){
          var totalFactura = 0, totalAplicado = 0, totalEfectivo = 0, totalTarjeta = 0, totalCambio = 0;
          
          data.forEach(function (r) {
              totalFactura += parseFloat(r.total_factura) || 0;
              totalAplicado += parseFloat(r.aplicado) || 0;
              totalEfectivo += parseFloat(r.efectivo) || 0;
              totalTarjeta += parseFloat(r.tarjeta) || 0;
              totalCambio += parseFloat(r.cambio) || 0;
          });
          
          var fmt = new Intl.NumberFormat('es-HN', {style: 'currency', currency: 'HNL', minimumFractionDigits: 2});
          
          $('#pg_total_factura').html(fmt.format(totalFactura));
          $('#pg_aplicado').html(fmt.format(totalAplicado));
          $('#pg_efectivo').html(fmt.format(totalEfectivo));
          $('#pg_tarjeta').html(fmt.format(totalTarjeta));
          $('#pg_cambio').html(fmt.format(totalCambio));
      },
      drawCallback: function(){
        if (typeof getPermisosTipoUsuarioAccesosTable === 'function'){
          getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
      }
    });

    view_reporte_facturas_pagos_dataTable("#DataTablePagosCliente tbody", dtPagosCliente);
  } else {
    // para re-consultar con los filtros actuales
    dtPagosCliente.ajax.reload();
  }
}

var view_reporte_facturas_pagos_dataTable = function (tbody, table) {
    $(tbody).off("click", "button.print_factura_pagos");
    $(tbody).on("click", "button.print_factura_pagos", function (e) {
      e.preventDefault();
      var data = table.row($(this).parents("tr")).data();
      printBillReporteVentas(data.facturas_id);
    });
  };

  
function modal_pagos_cliente(){
  const $m = $('#ModalPagosCliente');

  // Fechas por defecto (mes actual)
  const hoy = new Date();
  const y = hoy.getFullYear(), m = hoy.getMonth();
  const firstDay = new Date(y, m, 1).toISOString().slice(0,10);
  const today    = new Date().toISOString().slice(0,10);

  $m.find('#PagosFechai').val(firstDay);
  $m.find('#PagosFechaf').val(today);

  // Cargar clientes y, cuando termine, hacer la primer consulta
  getClientesPagos();
  setTimeout(() => listar_pagos_cliente(), 150); // primer fetch

  // Botones
  $m.off('click', '#btnFiltrarPagosCliente').on('click', '#btnFiltrarPagosCliente', function(){
    listar_pagos_cliente();
  });

  $m.off('click', '#btnLimpiarPagosCliente').on('click', '#btnLimpiarPagosCliente', function(){
    $m.find('#ClientePagos').val('');
    if ($.fn.selectpicker) $m.find('#ClientePagos').selectpicker('refresh');
    listar_pagos_cliente();
  });

  $m.modal({show:true, keyboard:false, backdrop:'static'});
}

// helper dinero
function moneyFmt(n){
  const v = parseFloat(n || 0);
  return 'L ' + v.toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function getClientesPagos(){
  $.ajax({
    url: "<?php echo SERVERURL; ?>core/facturas/getClientes.php",
    type: "POST",
    dataType: "json"
  }).done(function(resp){
    const $sel = $('#ModalPagosCliente #ClientePagos');
    $sel.empty();
    if (resp && resp.success && Array.isArray(resp.data) && resp.data.length){
      resp.data.forEach(c => {
        $sel.append(
          `<option value="${c.clientes_id}" data-subtext="${c.rtn ? c.rtn : 'Sin RTN'}">${c.nombre}</option>`
        );
      });
    }else{
      $sel.append('<option value="">Sin clientes</option>');
    }
    if ($.fn.selectpicker) $sel.selectpicker('refresh');
  }).fail(function(){
    const $sel = $('#ModalPagosCliente #ClientePagos');
    $sel.html('<option value="">Error al cargar</option>');
    if ($.fn.selectpicker) $sel.selectpicker('refresh');
  });
}

/* ============================================================
   IZZY | REPORTE DE VENTAS - DIV / KPI / EXPORT PREMIUM
   Sobrescribe únicamente los listados visuales DataTable.
   Mantiene endpoints y acciones existentes.
   ============================================================ */
(function(){
  'use strict';

  var RV = {
    main:{rows:[],filtered:[],page:1,pageSize:10,view:'detalle',search:''},
    detail:{rows:[],filtered:[],page:1,pageSize:10,view:'detalle',search:''},
    payments:{rows:[],filtered:[],page:1,pageSize:10,view:'detalle',search:''}
  };

  function rvEsMovil(){
    return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
  }

  function rvSincronizarBotonesVista(){
    $('.rv-view-btn[data-view]').removeClass('active').attr('aria-pressed','false');
    $('.rv-view-btn[data-view="'+RV.main.view+'"]').addClass('active').attr('aria-pressed','true');

    $('[data-rv-detail-view]').removeClass('active').attr('aria-pressed','false');
    $('[data-rv-detail-view="'+RV.detail.view+'"]').addClass('active').attr('aria-pressed','true');

    $('[data-rv-pay-view]').removeClass('active').attr('aria-pressed','false');
    $('[data-rv-pay-view="'+RV.payments.view+'"]').addClass('active').attr('aria-pressed','true');
  }

  function rvAplicarVistaResponsiveInicial(){
    if(rvEsMovil()){
      RV.main.view='miniatura';
      RV.detail.view='miniatura';
      RV.payments.view='miniatura';
    }
    rvSincronizarBotonesVista();
  }

  rvAplicarVistaResponsiveInicial();

  var rvResponsiveTimer=null;
  $(window)
    .off('resize.rvResponsive orientationchange.rvResponsive')
    .on('resize.rvResponsive orientationchange.rvResponsive',function(){
      clearTimeout(rvResponsiveTimer);
      rvResponsiveTimer=setTimeout(function(){
        if(!rvEsMovil()) return;

        var cambio=false;

        if(RV.main.view!=='miniatura'){
          RV.main.view='miniatura';
          RV.main.page=1;
          cambio=true;
        }
        if(RV.detail.view!=='miniatura'){
          RV.detail.view='miniatura';
          RV.detail.page=1;
          cambio=true;
        }
        if(RV.payments.view!=='miniatura'){
          RV.payments.view='miniatura';
          RV.payments.page=1;
          cambio=true;
        }

        if(cambio){
          rvSincronizarBotonesVista();
          if(RV.main.rows.length) rvRenderMain();
          if(RV.detail.rows.length) rvRenderDetailSales();
          if(RV.payments.rows.length) rvRenderPayments();
        }
      },120);
    });

  function rvNum(v){
    if(typeof v==='string') v=v.replace(/<[^>]*>/g,'').replace(/L\./g,'').replace(/L/g,'').replace(/,/g,'').trim();
    v=parseFloat(v||0); return isNaN(v)?0:v;
  }
  function rvMoney(v){return 'L. '+rvNum(v).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2});}
  function rvEsc(v){return $('<div>').text(v===null||v===undefined?'':String(v)).html();}
  function rvSearchText(r){try{return JSON.stringify(r||{}).toLowerCase();}catch(e){return '';}}
  function rvRows(json){if(typeof json==='string'){try{json=JSON.parse(json);}catch(e){return[];}} return json&&Array.isArray(json.data)?json.data:(Array.isArray(json)?json:[]);}
  function rvEmpty(){return '<div class="rv-empty"><i class="fas fa-inbox"></i><strong>Sin registros</strong><span>No hay información que coincida con los criterios actuales.</span></div>';}
  function rvField(label,value){return '<div class="rv-mini-field"><span>'+rvEsc(label)+'</span><strong>'+value+'</strong></div>';}
  function rvDateFile(){return new Date().toISOString().slice(0,10);}

  function rvFilterState(state){
    var q=String(state.search||'').trim().toLowerCase();
    state.filtered=!q?state.rows.slice():state.rows.filter(function(r){return rvSearchText(r).indexOf(q)!==-1;});
    var pages=Math.max(1,Math.ceil(state.filtered.length/state.pageSize));
    state.page=Math.max(1,Math.min(state.page,pages));
  }

  function rvPagination(state,selector,renderFn){
    var pages=Math.max(1,Math.ceil(state.filtered.length/state.pageSize)), html='';
    function b(label,page,disabled,active){html+='<button type="button" data-page="'+page+'" '+(disabled?'disabled':'')+' class="'+(active?'active':'')+'">'+label+'</button>';}
    b('<i class="fas fa-angle-double-left"></i> Inicio',1,state.page===1,false);
    b('<i class="fas fa-angle-left"></i> Anterior',state.page-1,state.page===1,false);
    var from=Math.max(1,state.page-2),to=Math.min(pages,from+4);from=Math.max(1,to-4);
    for(var p=from;p<=to;p++)b(String(p),p,false,p===state.page);
    b('Siguiente <i class="fas fa-angle-right"></i>',state.page+1,state.page===pages,false);
    b('Final <i class="fas fa-angle-double-right"></i>',pages,state.page===pages,false);
    $(selector).html(html).off('click.rv','button[data-page]').on('click.rv','button[data-page]',function(){
      if(this.disabled||$(this).hasClass('active'))return;
      state.page=parseInt($(this).data('page'),10)||1;renderFn();
    });
  }

  function rvDropdown(r,index){
    var anulada=facturaEstaAnuladaReporte(r);
    var html='<div class="dropdown acciones-dropdown">'+
      '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle"><i class="fas fa-cog"></i><span>Acciones</span></button>'+
      '<div class="dropdown-menu dropdown-menu-right acciones-menu">'+
      '<button type="button" class="dropdown-item accion-item table_reportes rv-action" data-action="detalle" data-index="'+index+'"><span class="accion-icon accion-icon-primary"><i class="fas fa-search"></i></span><span class="accion-label">Ver detalle</span></button>'+
      '<button type="button" class="dropdown-item accion-item table_reportes rv-action" data-action="factura" data-index="'+index+'"><span class="accion-icon accion-icon-success"><i class="fas fa-file-download"></i></span><span class="accion-label">Factura</span></button>'+
      '<button type="button" class="dropdown-item accion-item table_reportes rv-action" data-action="comprobante" data-index="'+index+'"><span class="accion-icon accion-icon-success"><i class="far fa-file-pdf"></i></span><span class="accion-label">Comprobante</span></button>';
    if(!anulada){
      html+='<button type="button" class="dropdown-item accion-item table_reportes rv-action" data-action="email" data-index="'+index+'"><span class="accion-icon accion-icon-secondary"><i class="fas fa-paper-plane"></i></span><span class="accion-label">Enviar</span></button>'+
      '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar rv-action" data-action="anular" data-index="'+index+'"><span class="accion-icon accion-icon-eliminar"><i class="fas fa-ban"></i></span><span class="accion-label">Anular</span></button>';
    }
    html+='</div></div>';
    return html;
  }

  function rvTypeBadge(r){
    var tipo=r.tipo_documento||'';
    var cls=tipo==='Crédito'?'badge-warning':'badge-success';
    var icon=tipo==='Crédito'?'fa-clock':'fa-check-circle';
    return '<span class="badge badge-pill '+cls+'"><i class="fas '+icon+' mr-1"></i>'+rvEsc(tipo)+'</span>';
  }
  function rvNumero(r,index){
    var numero=rvEsc(r.numero||'');
    if(parseInt(r.documento_id,10)===4){
      var est=parseInt(r.proforma_estado,10), anulada=facturaEstaAnuladaReporte(r), cerrada=est===2;
      var badge=anulada?'<span class="badge badge-danger ml-1">Anulada</span>':(cerrada?'<span class="badge badge-secondary ml-1">Cerrada</span>':'<span class="badge badge-info ml-1">Abierta</span>');
      var cerrar=(!anulada&&!cerrada)?'<button type="button" class="btn btn-sm btn-danger ml-1 rv-action" data-action="cerrar-proforma" data-index="'+index+'" title="Cerrar proforma"><i class="fas fa-times-circle"></i></button>':'';
      return '<div class="d-flex align-items-center flex-wrap" style="gap:4px">'+numero+badge+cerrar+'</div>';
    }
    return numero;
  }
  function rvTotalBadge(r){
    var total=rvMoney(r.total);
    if(facturaEstaAnuladaReporte(r))return '<div class="rv-total-status"><strong>'+total+'</strong><span class="badge badge-danger">Anulada</span></div>';
    if(parseInt(r.documento_id,10)===4){
      var cerrada=parseInt(r.proforma_estado,10)===2;
      return '<div class="rv-total-status"><strong>'+total+'</strong><span class="badge '+(cerrada?'badge-secondary':'badge-info')+'">'+(cerrada?'Cerrada':'Abierta')+'</span></div>';
    }
    var pagado=rvNum(r.monto_pagado),t=rvNum(r.total),estado='Pendiente',cls='badge-danger';
    if(pagado>=t-.01){estado='Pagado';cls='badge-success';}else if(pagado>0){estado='Abonado';cls='badge-secondary';}
    return '<div class="rv-total-status"><strong>'+total+'</strong><span class="badge '+cls+'">'+estado+'</span></div>';
  }

  function rvTotals(rows,fields){
    var o={};fields.forEach(function(f){o[f]=rows.reduce(function(a,r){return a+rvNum(r[f]);},0);});return o;
  }
  function rvRenderMain(){
    var s=RV.main;rvFilterState(s);
    var start=(s.page-1)*s.pageSize, pageRows=s.filtered.slice(start,start+s.pageSize), html='';
    var grid='135px 105px 100px minmax(190px,1.55fr) minmax(150px,1.15fr) 108px 96px 105px 132px 108px minmax(120px,.95fr) minmax(120px,.95fr)';
    if(!pageRows.length){html=rvEmpty();}
    else if(s.view==='miniatura'){
      html=pageRows.map(function(r,idx){
        var i=start+idx;
        return '<div class="rv-mini-card"><div class="rv-mini-head"><div><div class="rv-mini-title">'+rvEsc(r.cliente||'Sin cliente')+'</div><span class="rv-mini-sub">'+rvEsc(r.numero||'')+' • '+rvEsc(r.fecha||'')+'</span></div>'+rvDropdown(r,i)+'</div>'+
          '<div class="rv-mini-body">'+rvField('Tipo',rvTypeBadge(r))+rvField('Subtotal',rvMoney(r.subtotal))+rvField('ISV',rvMoney(r.isv))+rvField('Descuento',rvMoney(r.descuento))+rvField('Total',rvTotalBadge(r))+rvField('Ganancia',rvMoney(r.ganancia))+rvField('Vendedor',rvEsc(r.vendedor||''))+rvField('Facturador',rvEsc(r.facturador||''))+'</div></div>';
      }).join('');
    }else{
      var headers=['Acciones','Fecha','Tipo','Cliente','Factura','Subtotal','ISV','Descuento','Total','Ganancia','Vendedor','Facturador'];
      html='<div class="rv-detail-header" style="grid-template-columns:'+grid+'">'+headers.map(function(h){return '<div class="rv-cell">'+h+'</div>';}).join('')+'</div>'+
      pageRows.map(function(r,idx){var i=start+idx;
        return '<div class="rv-detail-row" style="grid-template-columns:'+grid+'">'+
          '<div class="rv-cell rv-actions-cell" data-label="Acciones">'+rvDropdown(r,i)+'</div>'+
          '<div class="rv-cell" data-label="Fecha">'+rvEsc(r.fecha||'')+'</div>'+
          '<div class="rv-cell" data-label="Tipo">'+rvTypeBadge(r)+'</div>'+
          '<div class="rv-cell" data-label="Cliente"><strong>'+rvEsc(r.cliente||'')+'</strong></div>'+
          '<div class="rv-cell" data-label="Factura">'+rvNumero(r,i)+'</div>'+
          '<div class="rv-cell rv-money" data-label="Subtotal">'+rvMoney(r.subtotal)+'</div>'+
          '<div class="rv-cell rv-money" data-label="ISV">'+rvMoney(r.isv)+'</div>'+
          '<div class="rv-cell rv-money" data-label="Descuento">'+rvMoney(r.descuento)+'</div>'+
          '<div class="rv-cell rv-money" data-label="Total">'+rvTotalBadge(r)+'</div>'+
          '<div class="rv-cell rv-money" data-label="Ganancia">'+rvMoney(r.ganancia)+'</div>'+
          '<div class="rv-cell" data-label="Vendedor">'+rvEsc(r.vendedor||'')+'</div>'+
          '<div class="rv-cell" data-label="Facturador">'+rvEsc(r.facturador||'')+'</div>'+
        '</div>';
      }).join('');
    }
    $('#rvListado').toggleClass('rv-mini',s.view==='miniatura').html(html);
    var totals=rvTotals(s.filtered,['subtotal','isv','descuento','total','ganancia']);
    $('#rvKpiRegistros').text(s.filtered.length);
    $('#rvKpiSubtotal').text(rvMoney(totals.subtotal));
    $('#rvKpiIsv').text(rvMoney(totals.isv));
    $('#rvKpiDescuento').text(rvMoney(totals.descuento));
    $('#rvKpiTotal').text(rvMoney(totals.total));
    $('#rvKpiGanancia').text(rvMoney(totals.ganancia));
    rvRenderTotalBar('#rvTotales',s.view,[
      ['Subtotal',totals.subtotal],['ISV',totals.isv],['Descuento',totals.descuento],['Total',totals.total],['Ganancia',totals.ganancia]
    ],12,[5,6,7,8,9]);
    $('#rvInfo').text(s.filtered.length?'Mostrando '+(start+1)+' a '+Math.min(start+pageRows.length,s.filtered.length)+' de '+s.filtered.length+' registros':'0 registros');
    rvPagination(s,'#rvPagination',rvRenderMain);
    try{getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());}catch(e){}
  }

  function rvRenderTotalBar(selector,view,items,colCount,indexes){
    if(view==='miniatura'){
      $(selector).html(
        '<div class="rv-total-mini">'+
          items.map(function(x){
            return '<div class="rv-total-chip"><span>'+rvEsc(x[0])+'</span><strong>'+rvMoney(x[1])+'</strong></div>';
          }).join('')+
        '</div>'
      );
      return;
    }

    var grid=colCount===12
      ? '135px 105px 100px minmax(190px,1.55fr) minmax(150px,1.15fr) 108px 96px 105px 132px 108px minmax(120px,.95fr) minmax(120px,.95fr)'
      : 'repeat('+colCount+',minmax(100px,1fr))';

    if(colCount===12 && items.length===5){
      $(selector).html(
        '<div class="rv-total-detail rv-total-main-grid" style="grid-template-columns:'+grid+'">'+
          '<div class="rv-total-main-label">TOTALES GENERALES</div>'+
          '<div class="rv-cell rv-money rv-total-value">'+rvMoney(items[0][1])+'</div>'+
          '<div class="rv-cell rv-money rv-total-value">'+rvMoney(items[1][1])+'</div>'+
          '<div class="rv-cell rv-money rv-total-value">'+rvMoney(items[2][1])+'</div>'+
          '<div class="rv-cell rv-money rv-total-value rv-total-highlight">'+rvMoney(items[3][1])+'</div>'+
          '<div class="rv-cell rv-money rv-total-value">'+rvMoney(items[4][1])+'</div>'+
          '<div class="rv-cell"></div>'+
          '<div class="rv-cell"></div>'+
        '</div>'
      );
      return;
    }

    var cells=[], map={};
    indexes.forEach(function(v,i){map[v]=items[i];});

    for(var c=0;c<colCount;c++){
      if(c===0)cells.push('<div class="rv-cell rv-total-label-generic">TOTALES GENERALES</div>');
      else if(map[c])cells.push('<div class="rv-cell rv-money rv-total-value">'+rvMoney(map[c][1])+'</div>');
      else cells.push('<div class="rv-cell"></div>');
    }

    $(selector).html('<div class="rv-total-detail" style="grid-template-columns:'+grid+'">'+cells.join('')+'</div>');
  }

  listar_reporte_ventas=function(){
    setCategoriaFacturaDefaultActivas();
    guardarFiltrosReporteVentas();
    var data={
      tipo_factura_reporte:getCategoriaFacturaReporte(),
      categoria_factura:getCategoriaFacturaReporte(),
      estado_factura:getCategoriaFacturaReporte(),
      facturador:$('#form_main_ventas #facturador').val(),
      vendedor:$('#form_main_ventas #vendedor').val(),
      fechai:$('#form_main_ventas #fechai').val(),
      fechaf:$('#form_main_ventas #fechaf').val(),
      factura:getTipoFacturaReporte()
    };
    $('#rvListado').removeClass('rv-mini').html('<div class="rv-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando reporte...</div>');
    $.ajax({type:'POST',url:'<?php echo SERVERURL;?>core/llenarDataTableReporteVentas.php',data:data,dataType:'json'})
      .done(function(json){RV.main.rows=filtrarDatosReporteVentasCliente(json);RV.main.page=1;rvRenderMain();})
      .fail(function(xhr){RV.main.rows=[];RV.main.filtered=[];rvRenderMain();showNotify('error','Error','No fue posible cargar el reporte de ventas.');});
  };

  function rvMainAction(action,r){
    if(!r)return;
    if(action==='detalle')return mostrarDetalleFactura(r.facturas_id);
    if(action==='factura')return printBillReporteVentas(r.facturas_id);
    if(action==='comprobante')return printBillComprobanteReporteVentas(r.facturas_id);
    if(action==='cerrar-proforma')return cerrarProforma(r.facturas_proforma_id,r.facturas_id,r.numero);
    if(action==='email'){
      return swal({title:'Enviar factura',text:'¿Desea enviar la factura No. '+r.numero+' por correo electrónico?',icon:'warning',buttons:{cancel:'Cancelar',confirm:'Sí, enviar'},closeOnEsc:false,closeOnClickOutside:false})
        .then(function(ok){if(ok){showNotify('info','Enviando','Enviando factura por correo, por favor espere...');mailBill(r.facturas_id);}});
    }
    if(action==='anular'){
      if(facturaEstaAnuladaReporte(r))return showNotify('info','Factura anulada','Esta factura ya está anulada.');
      if(typeof validarAdminSistema!=='function')return showNotify('error','Validación no disponible','No está cargado el JS de autenticación administrativa.');
      validarAdminSistema(function(ok){if(ok===true)anularFacturas(r.facturas_id);},{mensaje:'Para anular esta factura debe validar un administrador.',modulo:'Facturación',accion:'Anular factura',referencia_id:r.facturas_id,referencia_texto:r.numero,motivo:'Validación requerida para anular factura'});
    }
  }

  // Detalle de factura individual sin tabla
  mostrarDetalleFactura=function(facturas_id){
    var $modal=$('#modalDetalleFactura');$modal.modal('show');
    $('#detalle-factura-body').html('<div class="rv-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando detalle...</div>');
    $.ajax({url:'<?php echo SERVERURL;?>core/getDetalleFacturaReporteVentas.php',type:'POST',data:{facturas_id:facturas_id},dataType:'json'})
      .done(function(response){
        if(!(response&&response.success&&response.data)){showNotify('error','Error',(response&&response.message)||'No fue posible cargar el detalle.');return;}
        var f=response.data.cabecera||{},det=response.data.detalle||[];
        $modal.find('#numero-factura-modal').text(f.numero_factura||'N/A');$modal.find('#fecha-factura').text(f.fecha||'N/A');$modal.find('#cliente-factura').text(f.cliente||'N/A');$modal.find('#tipo-factura').text(f.tipo_factura||'N/A');
        var estado=parseInt(f.estado,10)||0, e=estado===2?'Pagada':(estado===3?'Crédito':(estado===4?'Anulada':'Pendiente'));
        $modal.find('#estado-factura').text(e);$modal.find('#subtotal-factura').text(rvMoney(f.subtotal));$modal.find('#total-factura').text(rvMoney(f.total));$modal.find('#notas-factura').text(f.notas||'No hay notas');
        if(!det.length){$('#detalle-factura-body').html(rvEmpty());}
        else $('#detalle-factura-body').html(det.map(function(x){return '<div class="rv-invoice-item">'+
          '<div><span>Producto / Servicio</span><strong>'+rvEsc(x.producto||'Producto no especificado')+'</strong></div>'+
          '<div><span>Cantidad</span><strong>'+rvEsc(x.cantidad||0)+' '+rvEsc(x.medida||'')+'</strong></div>'+
          '<div><span>Precio</span><strong>'+rvMoney(x.precio)+'</strong></div>'+
          '<div><span>ISV</span><strong>'+rvMoney(x.isv_valor)+'</strong></div>'+
          '<div><span>Descuento</span><strong>'+rvMoney(x.descuento)+'</strong></div>'+
          '<div><span>Subtotal</span><strong>'+rvMoney(x.subtotal)+'</strong></div></div>';}).join(''));
        $modal.find('#btn-imprimir-factura').off('click.rv').on('click.rv',function(){if(typeof printBillReporteVentas==='function')printBillReporteVentas(facturas_id);});
      }).fail(function(){showNotify('error','Error','No fue posible cargar el detalle de la factura.');});
  };

  // ================= Detalle de Ventas =================
  function rvRenderDetailSales(){
    var s=RV.detail;rvFilterState(s);var start=(s.page-1)*s.pageSize,rows=s.filtered.slice(start,start+s.pageSize),html='';
    var grid='100px minmax(220px,1.7fr) minmax(150px,1.1fr) minmax(180px,1.3fr) 105px 90px 95px 105px 115px minmax(150px,1fr)';
    if(!rows.length)html=rvEmpty();
    else if(s.view==='miniatura')html=rows.map(function(r){return '<div class="rv-mini-card"><div class="rv-mini-head"><div><div class="rv-mini-title">'+rvEsc(r.Producto||'')+'</div><span class="rv-mini-sub">'+rvEsc(r.numero||'')+' • '+rvEsc(r.Fecha||'')+'</span></div></div><div class="rv-mini-body">'+rvField('Cliente',rvEsc(r.Cliente||''))+rvField('Precio',rvMoney(r.Precio))+rvField('Cantidad',rvEsc(r.Cantidad||0))+rvField('ISV',rvMoney(r.ISV))+rvField('Descuento',rvMoney(r.Descuento))+rvField('Total',rvMoney(r.Total))+rvField('Vendedor',rvEsc(r.Vendedor||''))+'</div></div>';}).join('');
    else{
      var h=['Fecha','Producto','Factura','Cliente','Precio','Cantidad','ISV','Descuento','Total','Vendedor'];
      html='<div class="rv-detail-header" style="grid-template-columns:'+grid+'">'+h.map(function(x){return'<div class="rv-cell">'+x+'</div>';}).join('')+'</div>'+
      rows.map(function(r){return '<div class="rv-detail-row" style="grid-template-columns:'+grid+'"><div class="rv-cell" data-label="Fecha">'+rvEsc(r.Fecha||'')+'</div><div class="rv-cell" data-label="Producto"><strong>'+rvEsc(r.Producto||'')+'</strong></div><div class="rv-cell" data-label="Factura">'+rvEsc(r.numero||'')+'</div><div class="rv-cell" data-label="Cliente">'+rvEsc(r.Cliente||'')+'</div><div class="rv-cell rv-money" data-label="Precio">'+rvMoney(r.Precio)+'</div><div class="rv-cell rv-money" data-label="Cantidad">'+rvEsc(r.Cantidad||0)+'</div><div class="rv-cell rv-money" data-label="ISV">'+rvMoney(r.ISV)+'</div><div class="rv-cell rv-money" data-label="Descuento">'+rvMoney(r.Descuento)+'</div><div class="rv-cell rv-money" data-label="Total">'+rvMoney(r.Total)+'</div><div class="rv-cell" data-label="Vendedor">'+rvEsc(r.Vendedor||'')+'</div></div>';}).join('');
    }
    $('#rvDetalleListado').toggleClass('rv-mini',s.view==='miniatura').html(html);
    var t=rvTotals(s.filtered,['Precio','Cantidad','ISV','Descuento','Total']);
    $('#rvDetKpiPrecio').text(rvMoney(t.Precio));$('#rvDetKpiCantidad').text(rvNum(t.Cantidad).toLocaleString('es-HN'));$('#rvDetKpiIsv').text(rvMoney(t.ISV));$('#rvDetKpiDescuento').text(rvMoney(t.Descuento));$('#rvDetKpiTotal').text(rvMoney(t.Total));
    rvRenderTotalBar('#rvDetalleTotales',s.view,[['Precio',t.Precio],['Cantidad',t.Cantidad],['ISV',t.ISV],['Descuento',t.Descuento],['Total',t.Total]],10,[4,5,6,7,8]);
    $('#rvDetInfo').text(s.filtered.length?'Mostrando '+(start+1)+' a '+Math.min(start+rows.length,s.filtered.length)+' de '+s.filtered.length+' registros':'0 registros');
    rvPagination(s,'#rvDetPagination',rvRenderDetailSales);
  }
  ListarDetalleVenas=function(){
    var data={fechai:$('#DetallesFechai').val(),fechaf:$('#DetallesFechaf').val(),productos_id:$('#DetallesProductos').val(),colaboradores_id:$('#DetalleVendedores').val(),tipo_factura_reporte:getCategoriaFacturaReporte(),categoria_factura:getCategoriaFacturaReporte(),estado_factura:getCategoriaFacturaReporte(),factura:getTipoFacturaReporte()};
    $('#rvDetalleListado').html('<div class="rv-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</div>');
    $.ajax({type:'POST',url:'<?php echo SERVERURL;?>core/llenarDataTableDetalleVentas.php',data:data,dataType:'json'})
      .done(function(json){RV.detail.rows=filtrarDatosReporteVentasCliente(json);RV.detail.page=1;rvRenderDetailSales();})
      .fail(function(){RV.detail.rows=[];rvRenderDetailSales();showNotify('error','Error','No fue posible cargar el detalle de ventas.');});
  };

  // ================= Pagos =================
  function rvPaymentBadge(v){var cls=v==='Pagado'?'badge-success':(v==='Cancelado'?'badge-danger':(v==='Pendiente'?'badge-warning':'badge-secondary'));return '<span class="badge badge-pill '+cls+'">'+rvEsc(v||'')+'</span>';}
  function rvRenderPayments(){
    var s=RV.payments;rvFilterState(s);var start=(s.page-1)*s.pageSize,rows=s.filtered.slice(start,start+s.pageSize),html='';
    var grid='120px 100px minmax(150px,1fr) 100px 110px 110px 105px 105px 100px 110px 90px 105px minmax(130px,1fr)';
    if(!rows.length)html=rvEmpty();
    else if(s.view==='miniatura')html=rows.map(function(r,idx){var i=start+idx;return '<div class="rv-mini-card"><div class="rv-mini-head"><div><div class="rv-mini-title">'+rvEsc(r.numero||'')+'</div><span class="rv-mini-sub">'+rvEsc(r.fecha_pago||'')+' • '+rvEsc(r.usuario||'')+'</span></div><button type="button" class="btn btn-success btn-sm rv-pay-print" data-index="'+i+'"><i class="fas fa-file-download mr-1"></i>Factura</button></div><div class="rv-mini-body">'+rvField('Total factura',rvMoney(r.total_factura))+rvField('Aplicado',rvMoney(r.aplicado))+rvField('Efectivo',rvMoney(r.efectivo))+rvField('Tarjeta',rvMoney(r.tarjeta))+rvField('Cambio',rvMoney(r.cambio))+rvField('Método',rvEsc(r.metodo||''))+rvField('Tipo',rvEsc(r.tipo||''))+rvField('Estado',rvPaymentBadge(r.estado))+'</div></div>';}).join('');
    else{
      var h=['Acción','Fecha Pago','Factura','Fecha Factura','Total Factura','Aplicado','Efectivo','Tarjeta','Cambio','Método','Tipo','Estado','Usuario'];
      html='<div class="rv-detail-header" style="grid-template-columns:'+grid+'">'+h.map(function(x){return'<div class="rv-cell">'+x+'</div>';}).join('')+'</div>'+
      rows.map(function(r,idx){var i=start+idx;return '<div class="rv-detail-row" style="grid-template-columns:'+grid+'"><div class="rv-cell" data-label="Acción"><button type="button" class="btn btn-success btn-sm rv-pay-print" data-index="'+i+'"><i class="fas fa-file-download mr-1"></i>Factura</button></div><div class="rv-cell" data-label="Fecha Pago">'+rvEsc(r.fecha_pago||'')+'</div><div class="rv-cell" data-label="Factura">'+rvEsc(r.numero||'')+'</div><div class="rv-cell" data-label="Fecha Factura">'+rvEsc(r.fecha_factura||'')+'</div><div class="rv-cell rv-money" data-label="Total Factura">'+rvMoney(r.total_factura)+'</div><div class="rv-cell rv-money" data-label="Aplicado">'+rvMoney(r.aplicado)+'</div><div class="rv-cell rv-money" data-label="Efectivo">'+rvMoney(r.efectivo)+'</div><div class="rv-cell rv-money" data-label="Tarjeta">'+rvMoney(r.tarjeta)+'</div><div class="rv-cell rv-money" data-label="Cambio">'+rvMoney(r.cambio)+'</div><div class="rv-cell" data-label="Método">'+rvEsc(r.metodo||'')+'</div><div class="rv-cell" data-label="Tipo">'+rvEsc(r.tipo||'')+'</div><div class="rv-cell" data-label="Estado">'+rvPaymentBadge(r.estado)+'</div><div class="rv-cell" data-label="Usuario">'+rvEsc(r.usuario||'')+'</div></div>';}).join('');
    }
    $('#rvPagosListado').toggleClass('rv-mini',s.view==='miniatura').html(html);
    var t=rvTotals(s.filtered,['total_factura','aplicado','efectivo','tarjeta','cambio']);
    $('#rvPagKpiFactura').text(rvMoney(t.total_factura));$('#rvPagKpiAplicado').text(rvMoney(t.aplicado));$('#rvPagKpiEfectivo').text(rvMoney(t.efectivo));$('#rvPagKpiTarjeta').text(rvMoney(t.tarjeta));$('#rvPagKpiCambio').text(rvMoney(t.cambio));
    rvRenderTotalBar('#rvPagosTotales',s.view,[['Total Factura',t.total_factura],['Aplicado',t.aplicado],['Efectivo',t.efectivo],['Tarjeta',t.tarjeta],['Cambio',t.cambio]],13,[4,5,6,7,8]);
    $('#rvPagInfo').text(s.filtered.length?'Mostrando '+(start+1)+' a '+Math.min(start+rows.length,s.filtered.length)+' de '+s.filtered.length+' registros':'0 registros');
    rvPagination(s,'#rvPagPagination',rvRenderPayments);
  }
  listar_pagos_cliente=function(){
    var $m=$('#ModalPagosCliente'),data={fechai:$m.find('#PagosFechai').val(),fechaf:$m.find('#PagosFechaf').val(),cliente_id:$m.find('#ClientePagos').val()||''};
    $('#rvPagosListado').html('<div class="rv-loading"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando...</div>');
    $.ajax({type:'POST',url:'<?php echo SERVERURL;?>core/facturas/llenarDataTablePagosCliente.php',data:data,dataType:'json'})
      .done(function(json){RV.payments.rows=rvRows(json);RV.payments.page=1;rvRenderPayments();})
      .fail(function(){RV.payments.rows=[];rvRenderPayments();showNotify('error','Error','No fue posible cargar los pagos.');});
  };

  // ================= Export XLSX/PDF Premium =================
  function rvXml(v){return String(v===null||v===undefined?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&apos;');}
  function rvCol(i){var n=i+1,s='';while(n>0){var m=(n-1)%26;s=String.fromCharCode(65+m)+s;n=Math.floor((n-1)/26);}return s;}
  function rvCell(ref,v,style,numeric){if(numeric){var x=Number(v);if(!isNaN(x))return'<c r="'+ref+'" s="'+style+'"><v>'+x+'</v></c>';}return'<c r="'+ref+'" s="'+style+'" t="inlineStr"><is><t>'+rvXml(v)+'</t></is></c>';}
  function rvDownload(blob,name){var a=document.createElement('a'),u=URL.createObjectURL(blob);a.href=u;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(u);},1000);}

  function rvExportXlsx(cfg){
    if(typeof JSZip==='undefined'){showNotify('error','Excel no disponible','No se encontró JSZip.');return;}
    if(!cfg.rows.length){showNotify('warning','Sin datos','No hay registros para exportar.');return;}
    var headers=cfg.headers,rows=cfg.rows,numeric=cfg.numeric||[],totalCols=cfg.totalCols||[],last=rvCol(headers.length-1),headerRow=7,first=8,totalRow=first+rows.length,lastRow=totalCols.length?totalRow:headerRow+rows.length;
    var sums={};totalCols.forEach(function(c){sums[c]=rows.reduce(function(a,r){return a+rvNum(r[c]);},0);});
    var sr=[];
    sr.push('<row r="1" ht="30" customHeight="1">'+rvCell('A1','IZZY • '+cfg.title,1,false)+'</row>');
    sr.push('<row r="2" ht="20" customHeight="1">'+rvCell('A2',cfg.subtitle+' • Generado: '+new Date().toLocaleDateString('es-HN'),2,false)+'</row>');
    sr.push('<row r="3" ht="18" customHeight="1">'+rvCell('A3','REGISTROS',6,false)+rvCell(rvCol(Math.floor(headers.length/2))+'3','TOTAL GENERAL',6,false)+'</row>');
    sr.push('<row r="4" ht="26" customHeight="1">'+rvCell('A4',rows.length,7,true)+rvCell(rvCol(Math.floor(headers.length/2))+'4',cfg.grandTotal||0,10,true)+'</row>');
    sr.push('<row r="5"></row><row r="6">'+rvCell('A6','Detalle de registros filtrados',8,false)+'</row>');
    sr.push('<row r="7" ht="26" customHeight="1">'+headers.map(function(h,i){return rvCell(rvCol(i)+'7',h,3,false);}).join('')+'</row>');
    rows.forEach(function(r,ri){var rr=first+ri;sr.push('<row r="'+rr+'" ht="22" customHeight="1">'+r.map(function(v,ci){var isNum=numeric.indexOf(ci)!==-1;return rvCell(rvCol(ci)+rr,v,isNum?5:4,isNum);}).join('')+'</row>');});
    if(totalCols.length){
      var firstTotalCol=Math.min.apply(null,totalCols);
      var cells=rvCell('A'+totalRow,'TOTALES GENERALES',9,false);
      totalCols.forEach(function(c){
        cells+=rvCell(rvCol(c)+totalRow,sums[c],10,true);
      });
      sr.push('<row r="'+totalRow+'" ht="30" customHeight="1">'+cells+'</row>');
    }
    var cols=headers.map(function(h,i){var w=Math.min(34,Math.max(13,String(h).length+7));return'<col min="'+(i+1)+'" max="'+(i+1)+'" width="'+w+'" customWidth="1"/>';}).join('');
    var sheet='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'+last+lastRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols>'+cols+'</cols><sheetData>'+sr.join('')+'</sheetData><autoFilter ref="A7:'+last+(headerRow+rows.length)+'"/><mergeCells count="'+(totalCols.length?'3':'2')+'"><mergeCell ref="A1:'+last+'1"/><mergeCell ref="A2:'+last+'2"/>'+(totalCols.length?'<mergeCell ref="A'+totalRow+':'+rvCol(Math.min.apply(null,totalCols)-1)+totalRow+'"/>':'')+'</mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/></worksheet>';
    var styles='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="L. #,##0.00"/></numFmts><fonts count="8"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0"/><xf numFmtId="0" fontId="2" fillId="4" borderId="0"/><xf numFmtId="0" fontId="3" fillId="3" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="4" fillId="0" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="0"/><xf numFmtId="0" fontId="6" fillId="4" borderId="0"/><xf numFmtId="0" fontId="7" fillId="0" borderId="0"/><xf numFmtId="0" fontId="7" fillId="4" borderId="1"/><xf numFmtId="164" fontId="7" fillId="4" borderId="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    var workbook='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>';
    var rels='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    var root='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    var types='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    var zip=new JSZip();zip.file('[Content_Types].xml',types);zip.folder('_rels').file('.rels',root);zip.folder('xl').file('workbook.xml',workbook);zip.folder('xl').file('styles.xml',styles);zip.folder('xl').folder('_rels').file('workbook.xml.rels',rels);zip.folder('xl').folder('worksheets').file('sheet1.xml',sheet);
    var opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
    var promise=typeof zip.generateAsync==='function'?zip.generateAsync(opts):Promise.resolve(zip.generate(opts));
    promise.then(function(blob){rvDownload(blob,cfg.file+'_'+rvDateFile()+'.xlsx');}).catch(function(e){console.error(e);showNotify('error','Excel','No se pudo generar el archivo Excel.');});
  }

  function rvGetLogo(cb){
    if(typeof imagen!=='undefined'&&imagen){if(String(imagen).indexOf('data:image/')===0)return cb(imagen);}
    $.ajax({type:'GET',url:'<?php echo SERVERURL;?>core/get_image.php',dataType:'text',timeout:10000}).done(function(src){
      src=String(src||'').trim();if(!src)return cb(null);if(src.indexOf('data:image/')===0)return cb(src);
      var im=new Image();im.crossOrigin='Anonymous';im.onload=function(){try{var c=document.createElement('canvas');c.width=im.naturalWidth;c.height=im.naturalHeight;c.getContext('2d').drawImage(im,0,0);cb(c.toDataURL('image/png'));}catch(e){cb(null);}};im.onerror=function(){cb(null);};im.src=src;
    }).fail(function(){cb(null);});
  }

  function rvPdfLogoPlate(logoDataUrl){
    if(!logoDataUrl){
      return {
        table:{
          widths:['*'],
          body:[[{text:'IZZY',fontSize:16,bold:true,color:'#17324D',alignment:'center',margin:[7,8,7,8],fillColor:'#FFFFFF'}]]
        },
        layout:{
          hLineColor:function(){return '#DDE3EA';},
          vLineColor:function(){return '#DDE3EA';},
          hLineWidth:function(){return .5;},
          vLineWidth:function(){return .5;},
          paddingLeft:function(){return 0;},
          paddingRight:function(){return 0;},
          paddingTop:function(){return 0;},
          paddingBottom:function(){return 0;}
        }
      };
    }

    return {
      table:{
        widths:['*'],
        body:[[
          {
            image:logoDataUrl,
            fit:[62,36],
            alignment:'center',
            margin:[7,5,7,5],
            fillColor:'#FFFFFF'
          }
        ]]
      },
      layout:{
        hLineColor:function(){return '#DDE3EA';},
        vLineColor:function(){return '#DDE3EA';},
        hLineWidth:function(){return .5;},
        vLineWidth:function(){return .5;},
        paddingLeft:function(){return 0;},
        paddingRight:function(){return 0;},
        paddingTop:function(){return 0;},
        paddingBottom:function(){return 0;}
      }
    };
  }

  function rvExportPdf(cfg){
    if(!cfg.rows.length)return showNotify('warning','Sin datos','No hay registros para exportar.');
    if(typeof pdfMake==='undefined')return showNotify('error','PDF no disponible','No se encontró pdfMake.');
    rvGetLogo(function(logo){
      var body=[cfg.headers.map(function(h){return{text:h,fillColor:'#17324D',color:'#fff',bold:true,fontSize:6.4,alignment:'center'};})];
      cfg.rows.forEach(function(r,i){body.push(r.map(function(v,c){var isNum=(cfg.numeric||[]).indexOf(c)!==-1;return{text:isNum?rvMoney(v):String(v===undefined?'':v),fillColor:i%2?'#F7F9FC':'#FFFFFF',alignment:isNum?'right':'left',fontSize:6.8};}));});
      if(cfg.totalCols&&cfg.totalCols.length){
        var sums={};
        cfg.totalCols.forEach(function(c){
          sums[c]=cfg.rows.reduce(function(a,r){return a+rvNum(r[c]);},0);
        });

        var firstTotalCol=Math.min.apply(null,cfg.totalCols);
        var tr=[];

        tr.push({
          text:'TOTALES GENERALES',
          colSpan:firstTotalCol,
          bold:true,
          fillColor:'#EAF4FC',
          color:'#17324D',
          fontSize:7,
          margin:[5,4,5,4],
          alignment:'left'
        });

        for(var blank=1;blank<firstTotalCol;blank++){
          tr.push({});
        }

        for(var c=firstTotalCol;c<cfg.headers.length;c++){
          if(sums[c]!==undefined){
            tr.push({
              text:rvMoney(sums[c]),
              bold:true,
              fillColor:c===7?'#E8F7EF':'#EAF4FC',
              color:c===7?'#087F5B':'#17324D',
              alignment:'right',
              margin:[3,4,3,4],
              fontSize:7
            });
          }else{
            tr.push({text:'',fillColor:'#EAF4FC'});
          }
        }

        body.push(tr);
      }
      var doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],content:[
        {table:{widths:[90,'*',130],body:[[
          {fillColor:'#17324D',border:[false,false,false,false],margin:[10,7,4,7],stack:[rvPdfLogoPlate(logo)]},
          {fillColor:'#17324D',border:[false,false,false,false],stack:[{text:cfg.title,color:'#fff',bold:true,fontSize:15},{text:cfg.subtitle,color:'#D8E5F0',fontSize:7.5,margin:[0,2,0,0]}],margin:[0,9,0,9]},
          {fillColor:'#17324D',border:[false,false,false,false],stack:[{text:'REPORTE EJECUTIVO',color:'#72E2E5',bold:true,fontSize:6.5,alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),color:'#fff',bold:true,fontSize:9,alignment:'right',margin:[0,3,0,0]},{text:cfg.rows.length+' registro(s)',color:'#D8E5F0',fontSize:6.5,alignment:'right',margin:[0,2,0,0]}],margin:[0,9,10,9]}
        ]]},layout:'noBorders',margin:[0,0,0,10]},
        {table:{widths:['*','*','*'],body:[[
          {fillColor:'#F7F9FC',stack:[{text:'REGISTROS',fontSize:6.5,bold:true,color:'#6B778C'},{text:String(cfg.rows.length),fontSize:12,bold:true,color:'#172B4D',margin:[0,2,0,0]}],margin:[8,7,8,7]},
          {fillColor:'#F7F9FC',stack:[{text:'TOTAL GENERAL',fontSize:6.5,bold:true,color:'#6B778C'},{text:rvMoney(cfg.grandTotal||0),fontSize:12,bold:true,color:'#172B4D',margin:[0,2,0,0]}],margin:[8,7,8,7]},
          {fillColor:'#F7F9FC',stack:[{text:'FILTROS',fontSize:6.5,bold:true,color:'#6B778C'},{text:cfg.filters||'Sin filtros adicionales',fontSize:7,color:'#42526E',margin:[0,2,0,0]}],margin:[8,7,8,7]}
        ]]},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';}},margin:[0,0,0,12]},
        {table:{headerRows:1,widths:cfg.headers.map(function(h,c){return(c>=cfg.headers.length-5)?58:'*';}),body:body},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 4;},paddingRight:function(){return 4;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}}}
      ],footer:function(p,pc){return{margin:[28,8,28,0],columns:[{text:'IZZY • Reportes',fontSize:7,color:'#7A869A'},{text:'Página '+p+' de '+pc,fontSize:7,color:'#7A869A',alignment:'right'}]};},defaultStyle:{fontSize:7,color:'#253858'}};
      var pdf=pdfMake.createPdf(doc),name=cfg.file+'_'+rvDateFile()+'.pdf';
      if(typeof abrirModalPdfPublico==='function'&&typeof pdf.getDataUrl==='function')pdf.getDataUrl(function(url){abrirModalPdfPublico(url,cfg.title,name);});else pdf.download(name);
    });
  }

  function rvMainExportCfg(){
    var rows=RV.main.filtered.map(function(r){return[r.fecha||'',r.tipo_documento||'',r.cliente||'',r.numero||'',rvNum(r.subtotal),rvNum(r.isv),rvNum(r.descuento),rvNum(r.total),rvNum(r.ganancia),r.vendedor||'',r.facturador||''];});
    return{title:'REPORTE DE VENTAS',subtitle:'Ventas, impuestos, descuentos y ganancia',file:'Reporte_Ventas',headers:['Fecha','Tipo','Cliente','Factura','Subtotal','ISV','Descuento','Total Ventas','Ganancia','Vendedor','Facturador'],rows:rows,numeric:[4,5,6,7,8],totalCols:[4,5,6,7,8],grandTotal:rows.reduce(function(a,r){return a+rvNum(r[7]);},0),filters:'Fechas: '+($('#fechai').val()||'')+' a '+($('#fechaf').val()||'')};
  }
  function rvDetailExportCfg(){
    var rows=RV.detail.filtered.map(function(r){return[r.Fecha||'',r.Producto||'',r.numero||'',r.Cliente||'',rvNum(r.Precio),rvNum(r.Cantidad),rvNum(r.ISV),rvNum(r.Descuento),rvNum(r.Total),r.Vendedor||''];});
    return{title:'DETALLE DE VENTAS',subtitle:'Detalle por producto, factura y vendedor',file:'Detalle_Ventas',headers:['Fecha','Producto','Factura','Cliente','Precio','Cantidad','ISV','Descuento','Total','Vendedor'],rows:rows,numeric:[4,5,6,7,8],totalCols:[4,5,6,7,8],grandTotal:rows.reduce(function(a,r){return a+rvNum(r[8]);},0),filters:'Fechas: '+($('#DetallesFechai').val()||'')+' a '+($('#DetallesFechaf').val()||'')};
  }
  function rvPayExportCfg(){
    var rows=RV.payments.filtered.map(function(r){return[r.fecha_pago||'',r.numero||'',r.fecha_factura||'',rvNum(r.total_factura),rvNum(r.aplicado),rvNum(r.efectivo),rvNum(r.tarjeta),rvNum(r.cambio),r.metodo||'',r.tipo||'',r.estado||'',r.usuario||''];});
    return{title:'REPORTE DE PAGOS',subtitle:'Pagos aplicados por cliente y factura',file:'Reporte_Pagos_Cliente',headers:['Fecha Pago','Factura','Fecha Factura','Total Factura','Aplicado','Efectivo','Tarjeta','Cambio','Método','Tipo','Estado','Usuario'],rows:rows,numeric:[3,4,5,6,7],totalCols:[3,4,5,6,7],grandTotal:rows.reduce(function(a,r){return a+rvNum(r[4]);},0),filters:'Fechas: '+($('#PagosFechai').val()||'')+' a '+($('#PagosFechaf').val()||'')};
  }

  // ================= DOM bindings =================
  $(document).off('click.rvToggle','.rv-toggle-section').on('click.rvToggle','.rv-toggle-section',function(){
    var $b=$(this),$t=$($b.data('target')),hide=$t.is(':visible');$t.stop(true,true).slideToggle(160);$b.find('span').text(hide?'Mostrar':'Ocultar');$b.find('i').toggleClass('fa-chevron-up',!hide).toggleClass('fa-chevron-down',hide);
  });

  $(document).off('click.rvAction','.rv-action').on('click.rvAction','.rv-action',function(e){e.preventDefault();var i=parseInt($(this).data('index'),10),r=RV.main.filtered[i];rvMainAction($(this).data('action'),r);});
  $(document).off('click.rvPay','.rv-pay-print').on('click.rvPay','.rv-pay-print',function(){var r=RV.payments.filtered[parseInt($(this).data('index'),10)];if(r&&r.facturas_id)printBillReporteVentas(r.facturas_id);});

  $('#rvBtnActualizar').off('click.rv').on('click.rv',listar_reporte_ventas);
  $('#rvBtnPagos').off('click.rv').on('click.rv',modal_pagos_cliente);
  $('#rvBtnDetalle').off('click.rv').on('click.rv',modal_detalles);
  $('#rvBtnExcel').off('click.rv').on('click.rv',function(){rvExportXlsx(rvMainExportCfg());});
  $('#rvBtnPdf').off('click.rv').on('click.rv',function(){rvExportPdf(rvMainExportCfg());});

  $('#rvPageSize').off('change.rv').on('change.rv',function(){RV.main.pageSize=parseInt(this.value,10)||10;RV.main.page=1;rvRenderMain();});
  $('#rvSearch').off('input.rv').on('input.rv',function(){RV.main.search=this.value||'';RV.main.page=1;rvRenderMain();});
  $('#rvSearchClear').off('click.rv').on('click.rv',function(){$('#rvSearch').val('').focus();RV.main.search='';RV.main.page=1;rvRenderMain();});
  $('.rv-view-btn[data-view]').off('click.rv').on('click.rv',function(){$('.rv-view-btn[data-view]').removeClass('active');$(this).addClass('active');RV.main.view=$(this).data('view');RV.main.page=1;rvRenderMain();});

  $('#FormDetalleVentas').off('submit.rv').on('submit.rv',function(e){e.preventDefault();ListarDetalleVenas();});
  $('#rvDetActualizar').off('click.rv').on('click.rv',ListarDetalleVenas);
  $('#rvDetExcel').off('click.rv').on('click.rv',function(){rvExportXlsx(rvDetailExportCfg());});
  $('#rvDetPdf').off('click.rv').on('click.rv',function(){rvExportPdf(rvDetailExportCfg());});
  $('#rvDetPageSize').off('change.rv').on('change.rv',function(){RV.detail.pageSize=parseInt(this.value,10)||10;RV.detail.page=1;rvRenderDetailSales();});
  $('#rvDetSearch').off('input.rv').on('input.rv',function(){RV.detail.search=this.value||'';RV.detail.page=1;rvRenderDetailSales();});
  $('#rvDetSearchClear').off('click.rv').on('click.rv',function(){$('#rvDetSearch').val('').focus();RV.detail.search='';RV.detail.page=1;rvRenderDetailSales();});
  $('[data-rv-detail-view]').off('click.rv').on('click.rv',function(){$('[data-rv-detail-view]').removeClass('active');$(this).addClass('active');RV.detail.view=$(this).data('rv-detail-view');RV.detail.page=1;rvRenderDetailSales();});

  $('#rvPagActualizar').off('click.rv').on('click.rv',listar_pagos_cliente);
  $('#rvPagExcel').off('click.rv').on('click.rv',function(){rvExportXlsx(rvPayExportCfg());});
  $('#rvPagPdf').off('click.rv').on('click.rv',function(){rvExportPdf(rvPayExportCfg());});
  $('#rvPagPageSize').off('change.rv').on('change.rv',function(){RV.payments.pageSize=parseInt(this.value,10)||10;RV.payments.page=1;rvRenderPayments();});
  $('#rvPagSearch').off('input.rv').on('input.rv',function(){RV.payments.search=this.value||'';RV.payments.page=1;rvRenderPayments();});
  $('#rvPagSearchClear').off('click.rv').on('click.rv',function(){$('#rvPagSearch').val('').focus();RV.payments.search='';RV.payments.page=1;rvRenderPayments();});
  $('[data-rv-pay-view]').off('click.rv').on('click.rv',function(){$('[data-rv-pay-view]').removeClass('active');$(this).addClass('active');RV.payments.view=$(this).data('rv-pay-view');RV.payments.page=1;rvRenderPayments();});

  rvSincronizarBotonesVista();

  // Reset detail
  $('#FormDetalleVentas').off('reset.rv').on('reset.rv',function(){setTimeout(function(){try{$('#DetallesProductos,#DetalleVendedores').val('').selectpicker('refresh');}catch(e){}ListarDetalleVenas();},50);});

  // Rebind Pagos modal buttons because original implementation referenced DataTable state.
  var originalModalPagos=modal_pagos_cliente;
  modal_pagos_cliente=function(){
    var $m=$('#ModalPagosCliente'),hoy=new Date(),first=new Date(hoy.getFullYear(),hoy.getMonth(),1).toISOString().slice(0,10),today=new Date().toISOString().slice(0,10);
    $m.find('#PagosFechai').val(first);$m.find('#PagosFechaf').val(today);getClientesPagos();
    $m.off('click.rv','#btnFiltrarPagosCliente').on('click.rv','#btnFiltrarPagosCliente',listar_pagos_cliente);
    $m.off('click.rv','#btnLimpiarPagosCliente').on('click.rv','#btnLimpiarPagosCliente',function(){$m.find('#ClientePagos').val('');if($.fn.selectpicker)$m.find('#ClientePagos').selectpicker('refresh');listar_pagos_cliente();});
    $m.modal({show:true,keyboard:false,backdrop:'static'});setTimeout(listar_pagos_cliente,120);
  };

  // Focus search in opened dialogs
  $('.rv-modal').off('shown.bs.modal.rv').on('shown.bs.modal.rv',function(){$(this).find('input[type="search"]:visible').first().focus();});
})();

</script>