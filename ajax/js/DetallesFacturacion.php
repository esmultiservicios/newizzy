<script>
$(() => {
    construirEstructuraTablaFacturas();
    inicializarFechasFacturas();

    const dataTableFacturas = inicializarDataTableFacturas();

    $('#form-filtros-facturas').off('submit');
    $('#form-filtros-facturas').on('submit', function(e) {
        e.preventDefault();
        dataTableFacturas.ajax.reload(null, true);
    });

    $('#numero_factura').off('keyup');
    $('#numero_factura').on('keyup', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            dataTableFacturas.ajax.reload(null, true);
        }
    });

    $('#tipo_factura, #estado_factura').off('changed.bs.select.detallesFacturacion change.detallesFacturacion');

    $('#tipo_factura, #estado_factura').on('changed.bs.select.detallesFacturacion', function(e, clickedIndex) {
        e.preventDefault();

        var valorSeleccionado = $(this).val();

        if (clickedIndex !== undefined && this.options && this.options[clickedIndex]) {
            valorSeleccionado = this.options[clickedIndex].value;
        }

        fijarValorSelectFactura(this, valorSeleccionado);

        setTimeout(function() {
            dataTableFacturas.ajax.reload(null, true);
        }, 80);
    });

    $('#tipo_factura, #estado_factura').on('change.detallesFacturacion', function() {
        if ($(this).data('selectpicker')) {
            return;
        }

        fijarValorSelectFactura(this, $(this).val());
        dataTableFacturas.ajax.reload(null, true);
    });

    $('#btn-limpiar-filtros').off('click');
    $('#btn-limpiar-filtros').on('click', function() {
        limpiarFiltrosFacturas();
        dataTableFacturas.ajax.reload(null, true);
    });

    $('#btn-actualizar-facturas').off('click');
    $('#btn-actualizar-facturas').on('click', function() {
        dataTableFacturas.ajax.reload(null, false);
    });

    $('#dataTableFacturas').off('click', '.js-acciones-toggle');
    $('#dataTableFacturas').on('click', '.js-acciones-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const $dropdown = $btn.closest('.acciones-dropdown');
        const $menu = $dropdown.find('.acciones-menu').first();
        const estaAbierto = $menu.hasClass('show');

        cerrarMenusAccionesFacturas();

        if (!estaAbierto) {
            $dropdown.addClass('show');
            $menu.addClass('show');
            $btn.attr('aria-expanded', 'true');
        }
    });

    $(document).off('click.facturasAcciones');
    $(document).on('click.facturasAcciones', function() {
        cerrarMenusAccionesFacturas();
    });

    $('#dataTableFacturas').off('click', '.acciones-menu');
    $('#dataTableFacturas').on('click', '.acciones-menu', function(e) {
        e.stopPropagation();
    });

    $('#dataTableFacturas').off('click', '.btn-detalle');
    $('#dataTableFacturas').on('click', '.btn-detalle', function(e) {
        e.preventDefault();

        cerrarMenusAccionesFacturas();

        const facturaId = $(this).data('id');
        cargarDetalleFactura(facturaId);

        $('#modalDetalleFactura').modal({
            show: true,
            backdrop: 'static',
            keyboard: false
        });
    });

    $('#dataTableFacturas').off('click', '.btn-imprimir');
    $('#dataTableFacturas').on('click', '.btn-imprimir', function(e) {
        e.preventDefault();

        cerrarMenusAccionesFacturas();

        const facturaId = $(this).data('id');
        imprimirFactura(facturaId);
    });

    $('#dataTableFacturas').off('click', '.btn-pagar');
    $('#dataTableFacturas').on('click', '.btn-pagar', function(e) {
        e.preventDefault();

        cerrarMenusAccionesFacturas();

        const facturaId = $(this).data('id');

        swal({
            title: "¿Pagar Factura?",
            text: "¿Desea proceder con el pago de esta factura?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "Sí, pagar"
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((willPay) => {
            if (willPay) {
                pagarFactura(facturaId, dataTableFacturas);
            }
        });
    });

    $(document).off('click', '.btn-retry-detalle');
    $(document).on('click', '.btn-retry-detalle', function() {
        const facturaId = $(this).data('id');
        cargarDetalleFactura(facturaId);
    });
});

function normalizarFiltroFactura(valor) {
    valor = (valor === null || valor === undefined) ? '' : String(valor).trim();

    if (valor === 'todos') {
        return '';
    }

    return valor;
}

function fijarValorSelectFactura(selector, valor) {
    var $select = $(selector);

    if ($select.length === 0) {
        return;
    }

    valor = (valor === null || valor === undefined || valor === '') ? 'todos' : String(valor);

    $select.val(valor);

    var textoSeleccionado = $select.find('option:selected').text();

    if (textoSeleccionado === '') {
        textoSeleccionado = valor === 'todos' ? 'Todos' : valor;
    }

    if ($.fn.selectpicker && $select.data('selectpicker')) {
        $select.selectpicker('val', valor);
        $select.selectpicker('render');

        var $bootstrapSelect = $select.closest('.bootstrap-select');
        var $textoBoton = $bootstrapSelect.find('.filter-option-inner-inner').first();

        if ($textoBoton.length > 0) {
            $textoBoton.text(textoSeleccionado);
        }
    }
}

function refrescarSelectsFiltrosFacturas() {
    fijarValorSelectFactura('#tipo_factura', $('#tipo_factura').val() || 'todos');
    fijarValorSelectFactura('#estado_factura', $('#estado_factura').val() || 'todos');
}

function construirEstructuraTablaFacturas() {
    const $tabla = $('#dataTableFacturas');

    if ($tabla.length === 0) {
        return;
    }

    if ($.fn.DataTable.isDataTable('#dataTableFacturas')) {
        $tabla.DataTable().clear().destroy();
    }

    $tabla.empty();

    $tabla.html(
        '<thead>' +
            '<tr>' +
                '<th>ID</th>' +
                '<th>Fecha</th>' +
                '<th>Número</th>' +
                '<th>Cliente</th>' +
                '<th>Tipo</th>' +
                '<th>Estado</th>' +
                '<th>Subtotal</th>' +
                '<th>ISV</th>' +
                '<th>Descuento</th>' +
                '<th>Total</th>' +
                '<th>Acciones</th>' +
            '</tr>' +
        '</thead>' +
        '<tbody></tbody>' +
        '<tfoot>' +
            '<tr>' +
                '<th colspan="11">' +
                    '<div class="facturas-footer-resumen">' +
                        '<span class="facturas-footer-item">' +
                            '<i class="fas fa-file-invoice-dollar"></i> Facturas: <strong id="factFooterTotal">0</strong>' +
                        '</span>' +

                        '<span class="facturas-footer-item text-success">' +
                            '<i class="fas fa-money-bill-wave"></i> Total: <strong id="factFooterMonto">L. 0.00</strong>' +
                        '</span>' +

                        '<span class="facturas-footer-item text-primary">' +
                            '<i class="fas fa-receipt"></i> ISV: <strong id="factFooterISV">L. 0.00</strong>' +
                        '</span>' +

                        '<span class="facturas-footer-item text-danger">' +
                            '<i class="fas fa-tags"></i> Descuento: <strong id="factFooterDescuento">L. 0.00</strong>' +
                        '</span>' +
                    '</div>' +
                '</th>' +
            '</tr>' +
        '</tfoot>'
    );
}

function inicializarDataTableFacturas() {
    return $('#dataTableFacturas').DataTable({
        responsive: true,
        processing: true,
        serverSide: false,
        autoWidth: false,
        destroy: true,
        ajax: {
            url: '<?php echo SERVERURL; ?>core/DetallesFacturacion/DetallesFacturacion.php',
            type: 'POST',
            dataType: 'json',
            data: function(d) {
                d.fecha_inicio = $('#fecha_inicio').val();
                d.fecha_fin = $('#fecha_fin').val();
                d.tipo_factura = normalizarFiltroFactura($('#tipo_factura').val());
                d.estado_factura = normalizarFiltroFactura($('#estado_factura').val());
                d.numero_factura = $('#numero_factura').val();
            },
            dataSrc: function(json) {
                if (json && json.type && json.type === 'error') {
                    mostrarNotificacionFactura('error', json.title || 'Error', json.message || 'No se pudieron cargar las facturas.');
                    return [];
                }

                return json && json.data ? json.data : [];
            },
            error: function(xhr) {
                console.error('Error al cargar facturas:', xhr.responseText);
                mostrarNotificacionFactura('error', 'Error', 'No se pudieron cargar las facturas.');
            }
        },
        columns: [
            {
                data: 'facturas_id',
                visible: false,
                searchable: false
            },
            {
                data: 'fecha',
                className: 'text-nowrap'
            },
            {
                data: 'numero',
                render: function(data, type) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    return '<span class="factura-numero">' + escapeHtmlFactura(data || '') + '</span>';
                }
            },
            {
                data: 'cliente',
                render: function(data, type) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    return '<span class="factura-cliente">' + escapeHtmlFactura(data || '') + '</span>';
                }
            },
            {
                data: 'tipo_documento',
                render: function(data, type) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    const tipo = String(data || '').toLowerCase();
                    const icon = tipo === 'crédito' || tipo === 'credito' ? 'fas fa-hand-holding-usd' : 'fas fa-money-bill-wave';

                    return '<span class="badge-factura badge-factura-tipo">' +
                                '<i class="' + icon + '"></i>' +
                                escapeHtmlFactura(data || '') +
                           '</span>';
                }
            },
            {
                data: 'estado',
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return row.estado_texto || '';
                    }

                    return renderEstadoFactura(row);
                }
            },
            {
                data: 'subtotal',
                className: 'text-right text-nowrap',
                render: function(data) {
                    return formatMoneyFactura(data);
                }
            },
            {
                data: 'isv',
                className: 'text-right text-nowrap',
                render: function(data) {
                    return formatMoneyFactura(data);
                }
            },
            {
                data: 'descuento',
                className: 'text-right text-nowrap',
                render: function(data) {
                    return formatMoneyFactura(data);
                }
            },
            {
                data: 'total',
                className: 'text-right text-nowrap',
                render: function(data, type) {
                    if (type !== 'display') {
                        return parseFloat(data || 0);
                    }

                    return '<strong class="factura-total">' + formatMoneyFactura(data) + '</strong>';
                }
            },
            {
                data: null,
                className: 'text-center',
                orderable: false,
                searchable: false,
                width: '120px',
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return '';
                    }

                    return renderAccionesFactura(row);
                }
            }
        ],
        order: [[1, 'desc']],
        language: {
            decimal: '',
            emptyTable: 'No hay datos disponibles',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            infoPostFix: '',
            thousands: ',',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing: 'Procesando...',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron registros coincidentes',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            },
            aria: {
                sortAscending: ': activar para ordenar ascendente',
                sortDescending: ': activar para ordenar descendente'
            }
        },
        dom:
            "<'row factura-dt-toolbar'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row factura-dt-footer'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-control form-control-sm');
            actualizarFooterFacturas(this.api());
        },
        drawCallback: function() {
            cerrarMenusAccionesFacturas();
            actualizarFooterFacturas(this.api());
        }
    });
}

function inicializarFechasFacturas() {
    /*
        Importante:
        Este historial debe mostrar TODAS las facturas del cliente por defecto.
        Antes se cargaban solo los últimos 30 días, por eso facturas antiguas
        como una del 01/06/2026 no aparecían cuando el filtro iniciaba en 06/06/2026.

        Dejamos las fechas vacías para que el backend no filtre por rango
        hasta que el usuario seleccione fechas manualmente.
    */
    $('#fecha_inicio').val('');
    $('#fecha_fin').val('');
    fijarValorSelectFactura('#tipo_factura', 'todos');
    fijarValorSelectFactura('#estado_factura', 'todos');
}

function limpiarFiltrosFacturas() {
    $('#form-filtros-facturas')[0].reset();

    inicializarFechasFacturas();

    $('#numero_factura').val('');
    fijarValorSelectFactura('#tipo_factura', 'todos');
    fijarValorSelectFactura('#estado_factura', 'todos');
}

function actualizarFooterFacturas(api) {
    const rows = api.rows({ search: 'applied' }).data().toArray();

    let total = 0;
    let isv = 0;
    let descuento = 0;

    rows.forEach(function(row) {
        total += parseFloat(row.total || 0);
        isv += parseFloat(row.isv || 0);
        descuento += parseFloat(row.descuento || 0);
    });

    $('#factFooterTotal').text(rows.length);
    $('#factFooterMonto').text(formatMoneyFactura(total));
    $('#factFooterISV').text(formatMoneyFactura(isv));
    $('#factFooterDescuento').text(formatMoneyFactura(descuento));
}

function renderEstadoFactura(row) {
    const estadoNum = parseInt(row.estado || 0, 10);
    const documentoId = parseInt(row.documento_id || 0, 10);
    const pagosRealizados = parseInt(row.pagos_realizados || 0, 10);
    const textoBase = row.estado_texto || 'Sin estado';

    let clase = 'badge-factura-secondary';
    let icono = 'fas fa-file-alt';
    let texto = textoBase;

    switch (estadoNum) {
        case 1:
            if (documentoId === 4) {
                clase = 'badge-factura-warning';
                icono = 'fas fa-clock';
                texto = textoBase || 'Pendiente de pago';
            } else {
                clase = 'badge-factura-secondary';
                icono = 'fas fa-file-alt';
                texto = textoBase || 'Borrador';
            }
            break;

        case 2:
            clase = 'badge-factura-success';
            icono = 'fas fa-check-circle';
            texto = textoBase || 'Pagada al contado';
            break;

        case 3:
            clase = 'badge-factura-warning';
            icono = pagosRealizados > 0 ? 'fas fa-hand-holding-usd' : 'fas fa-clock';
            texto = textoBase || (pagosRealizados > 0 ? 'Crédito con abono' : 'Crédito pendiente');
            break;

        case 4:
            clase = 'badge-factura-danger';
            icono = 'fas fa-times-circle';
            texto = textoBase || 'Anulada / Cancelada';
            break;

        default:
            clase = 'badge-factura-secondary';
            icono = 'fas fa-question-circle';
            texto = textoBase || 'Sin estado';
            break;
    }

    return '<span class="badge-factura ' + clase + '">' +
                '<i class="' + icono + '"></i>' +
                escapeHtmlFactura(texto) +
           '</span>';
}

function renderAccionesFactura(row) {
    const facturaId = escapeHtmlFactura(row.facturas_id);
    const puedePagar = row.estado == '3' || (parseInt(row.documento_id || 0, 10) === 4 && (row.estado == '1' || parseInt(row.tiene_pendiente || 0, 10) > 0));

    let html = '' +
        '<div class="dropdown acciones-dropdown factura-acciones-dropdown">' +
            '<button type="button" class="btn btn-sm btn-acciones js-acciones-toggle" aria-haspopup="true" aria-expanded="false">' +
                '<i class="fas fa-cog"></i>' +
                '<span>Acciones</span>' +
            '</button>' +

            '<div class="dropdown-menu dropdown-menu-right acciones-menu factura-acciones-menu">' +
                '<button type="button" class="dropdown-item accion-item btn-detalle" data-id="' + facturaId + '">' +
                    '<span class="accion-icon accion-icon-info">' +
                        '<i class="fas fa-eye"></i>' +
                    '</span>' +
                    '<span class="accion-label">Ver detalle</span>' +
                '</button>' +

                '<button type="button" class="dropdown-item accion-item btn-imprimir" data-id="' + facturaId + '">' +
                    '<span class="accion-icon accion-icon-primary">' +
                        '<i class="fas fa-print"></i>' +
                    '</span>' +
                    '<span class="accion-label">Imprimir</span>' +
                '</button>';

    if (puedePagar) {
        html += '' +
                '<button type="button" class="dropdown-item accion-item btn-pagar" data-id="' + facturaId + '">' +
                    '<span class="accion-icon accion-icon-success">' +
                        '<i class="fas fa-money-bill-wave"></i>' +
                    '</span>' +
                    '<span class="accion-label">Pagar</span>' +
                '</button>';
    }

    html += '' +
            '</div>' +
        '</div>';

    return html;
}

function cerrarMenusAccionesFacturas() {
    $('.factura-acciones-menu').removeClass('show');
    $('.factura-acciones-dropdown').removeClass('show');
    $('.js-acciones-toggle').attr('aria-expanded', 'false');
}

function cargarDetalleFactura(facturaId) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/DetallesFacturacion/DetallesFacturacion.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId
        },
        beforeSend: function() {
            $('#numero-factura-modal').text('');
            $('#fecha-factura').text('');
            $('#cliente-factura').text('');
            $('#tipo-factura').text('');
            $('#estado-factura').html('');
            $('#subtotal-factura').text('');
            $('#total-factura').text('');
            $('#notas-factura').text('');

            $('#detalle-factura-body').html(
                '<tr>' +
                    '<td colspan="6" class="text-center py-4">' +
                        '<div class="spinner-border text-primary" role="status">' +
                            '<span class="sr-only">Cargando...</span>' +
                        '</div>' +
                        '<p class="mt-2 mb-0">Cargando detalles...</p>' +
                    '</td>' +
                '</tr>'
            );
        },
        success: function(response) {
            if (!response || response.type !== 'success' || !response.data || response.data.length === 0) {
                mostrarNotificacionFactura('error', 'Error', 'No se encontraron datos de la factura.');
                $('#modalDetalleFactura').modal('hide');
                return;
            }

            const factura = response.data[0];

            $('#numero-factura-modal').text(factura.numero || '');
            $('#fecha-factura').text(factura.fecha || '');
            $('#cliente-factura').text(factura.cliente || '');
            $('#tipo-factura').text(factura.tipo_documento || '');
            $('#estado-factura').html(renderEstadoFactura(factura));
            $('#subtotal-factura').text(formatMoneyFactura(factura.subtotal || 0));
            $('#total-factura').text(formatMoneyFactura(factura.total || 0));
            $('#notas-factura').text(factura.notas || 'No hay notas');

            cargarLineasDetalleFactura(facturaId, factura);

            $('#btn-imprimir-factura').off('click');
            $('#btn-imprimir-factura').on('click', function() {
                imprimirFactura(facturaId);
            });
        },
        error: function(xhr) {
            console.error('Error al cargar factura:', xhr.responseText);
            mostrarNotificacionFactura('error', 'Error', 'Ocurrió un error al cargar el detalle de la factura.');
            $('#modalDetalleFactura').modal('hide');
        }
    });
}

function cargarLineasDetalleFactura(facturaId, factura) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/getDetalleFactura.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId,
            db_name: factura.db_name || '<?php echo DB_MAIN; ?>'
        },
        success: function(detalleResponse) {
            if (detalleResponse.type === 'success' && detalleResponse.data && detalleResponse.data.length > 0) {
                let detalleHtml = '';

                detalleResponse.data.forEach(function(item) {
                    const cantidad = parseFloat(item.cantidad || 0);
                    const precio = parseFloat(item.precio || 0);
                    const subtotal = cantidad * precio;

                    detalleHtml += '' +
                        '<tr>' +
                            '<td>' + escapeHtmlFactura(item.producto || 'Servicio') + '</td>' +
                            '<td class="text-center">' + escapeHtmlFactura(item.cantidad || '0') + ' ' + escapeHtmlFactura(item.medida || '') + '</td>' +
                            '<td class="text-right">' + formatMoneyFactura(item.precio || 0) + '</td>' +
                            '<td class="text-right">' + formatMoneyFactura(item.isv_valor || 0) + '</td>' +
                            '<td class="text-right">' + formatMoneyFactura(item.descuento || 0) + '</td>' +
                            '<td class="text-right">' + formatMoneyFactura(subtotal) + '</td>' +
                        '</tr>';
                });

                detalleHtml += '' +
                    '<tr class="factura-total-row-light">' +
                        '<td colspan="3" class="text-right"><strong>Subtotal:</strong></td>' +
                        '<td colspan="3" class="text-right">' + formatMoneyFactura(factura.subtotal || 0) + '</td>' +
                    '</tr>' +

                    '<tr class="factura-total-row-light">' +
                        '<td colspan="3" class="text-right"><strong>ISV:</strong></td>' +
                        '<td colspan="3" class="text-right">' + formatMoneyFactura(factura.isv || 0) + '</td>' +
                    '</tr>' +

                    '<tr class="factura-total-row-light">' +
                        '<td colspan="3" class="text-right"><strong>Descuento:</strong></td>' +
                        '<td colspan="3" class="text-right">' + formatMoneyFactura(factura.descuento || 0) + '</td>' +
                    '</tr>' +

                    '<tr class="factura-total-row-light">' +
                        '<td colspan="3" class="text-right"><strong class="text-success">TOTAL:</strong></td>' +
                        '<td colspan="3" class="text-right"><strong class="text-success">' + formatMoneyFactura(factura.total || 0) + '</strong></td>' +
                    '</tr>';

                $('#detalle-factura-body').html(detalleHtml);
            } else {
                $('#detalle-factura-body').html(
                    '<tr>' +
                        '<td colspan="6" class="text-center text-muted py-4">' +
                            '<i class="fas fa-info-circle fa-2x mb-2"></i><br>' +
                            'No se encontraron detalles para esta factura' +
                        '</td>' +
                    '</tr>'
                );
            }
        },
        error: function(xhr) {
            console.error('Error al cargar detalles:', xhr.responseText);

            $('#detalle-factura-body').html(
                '<tr>' +
                    '<td colspan="6" class="text-center text-danger py-4">' +
                        '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>' +
                        'Error al cargar los detalles de la factura<br>' +
                        '<button class="btn btn-outline-primary btn-sm mt-3 btn-retry-detalle" data-id="' + facturaId + '">' +
                            '<i class="fas fa-sync-alt mr-1"></i> Reintentar' +
                        '</button>' +
                    '</td>' +
                '</tr>'
            );
        }
    });
}

function pagarFactura(facturaId, dataTableFacturas) {
    $.ajax({
        url: '<?php echo SERVERURL; ?>core/pagarFactura.php',
        type: 'POST',
        dataType: 'json',
        data: {
            facturas_id: facturaId,
            db_name: '<?php echo DB_MAIN; ?>'
        },
        success: function(response) {
            if (response.type === 'success') {
                mostrarNotificacionFactura('success', 'Éxito', response.message || 'Factura pagada correctamente.');
                dataTableFacturas.ajax.reload(null, false);
            } else {
                mostrarNotificacionFactura('error', 'Error', response.message || 'No se pudo procesar el pago.');
            }
        },
        error: function(xhr) {
            console.error('Error al pagar factura:', xhr.responseText);
            mostrarNotificacionFactura('error', 'Error', 'Ocurrió un error al procesar el pago.');
        }
    });
}

function imprimirFactura(facturaId) {
    const params = {
        id: facturaId,
        type: 'Factura_carta_izzy',
        db: '<?php echo DB_MAIN; ?>'
    };

    viewReport(params);
}

function formatMoneyFactura(amount) {
    const n = parseFloat(amount || 0);

    return 'L. ' + n.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function escapeHtmlFactura(text) {
    if (text === null || text === undefined) {
        return '';
    }

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function mostrarNotificacionFactura(type, title, message) {
    if (typeof showNotify === 'function') {
        showNotify(type, title, message);
        return;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    alert(title + ': ' + message);
}
</script>