<script>
  $(() => {
    getReporteFactura();
    getFacturador();
    getVendedores();
    GetProductos();
    listar_reporte_ventas();

    $('#form_main_ventas #tipo_factura_reporte').val(1);
    $('#form_main_ventas #tipo_factura_reporte').selectpicker('refresh');

    $('#form_main_ventas #search').on('click', function (e) {
      e.preventDefault();
      listar_reporte_ventas();
    });

    $('#form_main_ventas').on('reset', function () {
      $(this).find('.selectpicker').val('').selectpicker('refresh');
      listar_reporte_ventas();
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

    let tipo_factura_reporte = $("#form_main_ventas #tipo_factura_reporte").val() || 1;
    let factura = $("#form_main_ventas #factura_reporte").val() || 1;

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
                "facturador": facturador,
                "vendedor": vendedor,
                "fechai": fechai,
                "fechaf": fechaf,
                "factura": factura
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

                    return '' +
                        '<div class="dropdown acciones-dropdown">' +

                            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                                '<i class="fas fa-cog"></i>' +
                                '<span>Acciones</span>' +
                            '</button>' +

                            '<div class="dropdown-menu dropdown-menu-right acciones-menu">' +

                                '<button type="button" class="dropdown-item accion-item table_reportes detalle_factura">' +
                                    '<span class="accion-icon accion-icon-primary">' +
                                        '<i class="fas fa-search"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Ver detalle</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes print_factura">' +
                                    '<span class="accion-icon accion-icon-success">' +
                                        '<i class="fas fa-file-download"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Factura</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes print_comprobante">' +
                                    '<span class="accion-icon accion-icon-success">' +
                                        '<i class="far fa-file-pdf"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Comprobante</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item table_reportes email_factura">' +
                                    '<span class="accion-icon accion-icon-secondary">' +
                                        '<i class="fas fa-paper-plane"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Enviar</span>' +
                                '</button>' +

                                '<button type="button" class="dropdown-item accion-item accion-eliminar table_cancelar cancelar_factura">' +
                                    '<span class="accion-icon accion-icon-eliminar">' +
                                        '<i class="fas fa-ban"></i>' +
                                    '</span>' +
                                    '<span class="accion-label">Anular</span>' +
                                '</button>' +

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
                        const isCerrada = (est === 2);

                        const badge = isCerrada
                            ? '<span class="badge badge-secondary ml-2">Cerrada</span>'
                            : '<span class="badge badge-info ml-2">Abierta</span>';

                        const cerrarBtn = !isCerrada
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

      swal({
          title: "Enviar factura",
          text: "¿Desea enviar la factura No. " + data.numero + " por correo electrónico?",
          icon: "warning",
          buttons: {
              cancel: {
                  text: "Cancelar",
                  visible: true
              },
              confirm: {
                  text: "Sí, enviar",
                  closeModal: false
              }
          },
          closeOnEsc: false,
          closeOnClickOutside: false
      }).then((confirmado) => {

          if (!confirmado) {
              return false;
          }

          swal.close();

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

    anularFacturas(data.facturas_id);
  });
};

function anularFacturas(facturas_id) {
  swal({
    title: "¿Está seguro?",
    text: "¿Desea anular la factura: # " + getNumeroFactura(facturas_id) + "?",
    content: {
      element: "input",
      attributes: {
        placeholder: "Comentario",
        type: "text"
      }
    },
    icon: "warning",
    buttons: {
      cancel: "Cancelar",
      confirm: {
        text: "¡Sí, anular la factura!",
        closeModal: false
      }
    },
    dangerMode: true,
    closeOnEsc: false,
    closeOnClickOutside: false
  }).then((value) => {
    if (value === null) {
      swal.close();
      return false;
    }

    if ($.trim(value) === "") {
      showNotify('error', 'Error', '¡Necesita escribir algo!');
      swal.close();
      return false;
    }

    anular(facturas_id, value);
  });
}

function anular(facturas_id, comentario) {
  $.ajax({
    type: 'POST',
    url: '<?php echo SERVERURL; ?>core/anularFactura.php',
    async: true,
    timeout: 45000,
    dataType: 'json',
    data: {
      facturas_id: facturas_id,
      comentario: comentario
    },
    success: function (response) {
      swal.close();

      console.log('Respuesta anularFactura.php:', response);

      if (response && response.success === true) {
        showNotify(
          'success',
          'Success',
          response.message || 'La factura ha sido anulada con éxito'
        );

        listar_reporte_ventas();
      } else {
        showNotify(
          'error',
          'Error',
          response && response.message ? response.message : 'La factura no se puede anular'
        );

        console.warn('Detalle anulación:', response);
      }
    },
    error: function (xhr, status, error) {
      swal.close();

      console.error('Error AJAX anularFactura.php:', {
        status: status,
        error: error,
        responseText: xhr.responseText
      });

      if (status === 'timeout') {
        showNotify(
          'error',
          'Error',
          'La anulación tardó demasiado. Revise si la factura fue anulada antes de intentarlo otra vez.'
        );
      } else {
        showNotify(
          'error',
          'Error',
          'Hubo un problema al anular la factura. Revise la consola o el log del servidor.'
        );
      }
    }
  });
}

  function getReporteFactura() {
    $.ajax({
      type: "POST",
      url: '<?php echo SERVERURL;?>core/getTipoFacturaReporte.php',
      async: true,
      success: function (data) {
        $('#form_main_ventas #tipo_factura_reporte').html(data).selectpicker('refresh');
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
        data: { "fechai": fechai, "fechaf": fechaf, "productos_id": productos_id, "colaboradores_id": colaboradores_id }
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
</script>
