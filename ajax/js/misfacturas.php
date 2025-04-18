<script>
// Código JavaScript actualizado
$(document).ready(function() {
    // Formatear dinero
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Cargar contador de facturas pendientes
    function cargarContadorFacturasPendientes() {
        $.ajax({
            url: '<?php echo SERVERURL; ?>core/misFacturas.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'contar_pendientes'
            },
            success: function(response) {
                if (response.type === 'success') {
                    const badge = $('#badge-facturas-pendientes');
                    if (response.total_pendientes > 0) {
                        badge.text(response.total_pendientes).show();
                        // Agregar animación para llamar la atención
                        badge.addClass('animate__animated animate__headShake animate__infinite');
                        setTimeout(() => {
                            badge.removeClass('animate__animated animate__headShake animate__infinite');
                        }, 2000);
                    } else {
                        badge.hide();
                    }
                }
            },
            error: function() {
                console.error('Error al cargar contador de facturas pendientes');
            }
        });
    }

    // Llama a esta función cada 5 minutos para mantener actualizado el contador
    setInterval(cargarContadorFacturasPendientes, 300000); // 300000 ms = 5 minutos

    // Función para imprimir factura con formato adecuado
    function imprimirFactura(facturaId) {
        params = {
            "id": facturaId,
            "type": "Factura_carta_izzy",
            "db": "<?php echo DB_MAIN; ?>"
        };
                        
        // Llamar a la función para mostrar el reporte
        viewReport(params); 
    }

    // Inicializar DataTable
    const dataTableFacturas = $('#dataTableFacturas').DataTable({
        responsive: true,
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?php echo SERVERURL; ?>core/misFacturas.php',
            type: 'POST',
            dataSrc: function(json) {
                if (json.type && json.title && json.message) {
                    showNotify(json.type, json.title, json.message);
                }
                return json.data || [];
            },
            error: function(xhr) {
                showNotify('error', 'Error', 'Ocurrió un error al cargar las facturas');
                return [];
            }
        },
        columns: [
            { data: 'facturas_id', visible: false },
            { data: 'fecha' },
            { data: 'numero' },
            { data: 'cliente' },
            { data: 'tipo_documento' },
            { 
                data: 'estado',
                render: function(data, type, row) {
                    let badgeClass = '';
                    let icon = '';
                    switch(data) {
                        case '2': // Pagada
                            badgeClass = 'badge-success bg-success';
                            icon = '<i class="fas fa-check-circle mr-1"></i>';
                            break;
                        case '3': // Crédito
                            badgeClass = 'badge-warning bg-warning text-dark';
                            icon = '<i class="fas fa-clock mr-1"></i>';
                            break;
                        case '4': // Cancelada
                            badgeClass = 'badge-danger bg-danger';
                            icon = '<i class="fas fa-times-circle mr-1"></i>';
                            break;
                        default: // Borrador
                            badgeClass = 'badge-secondary bg-secondary';
                            icon = '<i class="fas fa-file-alt mr-1"></i>';
                    }
                    return `<span class="badge rounded-pill ${badgeClass} p-2">${icon}${row.estado_texto}</span>`;
                }
            },
            { 
                data: 'subtotal',
                render: formatMoney
            },
            { 
                data: 'isv',
                render: formatMoney
            },
            { 
                data: 'descuento',
                render: formatMoney
            },
            { 
                data: 'total',
                render: function(data) {
                    return `<strong>${formatMoney(data)}</strong>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-info btn-sm btn-detalle" title="Ver Detalle" data-id="${row.facturas_id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-primary btn-sm btn-imprimir" title="Imprimir Factura" 
                                data-id="${row.facturas_id}" data-db="${row.db_name}">
                            <i class="fas fa-print"></i>
                        </button>`;
                    
                    if (row.estado == '3') {
                        buttons += `
                            <button class="btn btn-success btn-sm btn-pagar" title="Pagar Factura" data-id="${row.facturas_id}">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>`;
                    }
                    
                    return `<div class="btn-group">${buttons}</div>`;
                },
                orderable: false
            }
        ],
        order: [[1, 'desc']],
        language: idioma_español,
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-control form-control-sm');
        }
    });

    // Buscar facturas al enviar el formulario
    $('#form-filtros-facturas').on('submit', function(e) {
        e.preventDefault();
        dataTableFacturas.ajax.url('<?php echo SERVERURL; ?>core/misFacturas.php?' + $(this).serialize()).load();
    });

    // Limpiar filtros
    $('#btn-limpiar-filtros').click(function() {
        $('#form-filtros-facturas')[0].reset();
        dataTableFacturas.ajax.url('<?php echo SERVERURL; ?>core/misFacturas.php').load();
    });

    // Ver detalle de factura
    $(document).on('click', '.btn-detalle', function() {
        const facturaId = $(this).data('id');
        cargarDetalleFactura(facturaId);
        $('#modalDetalleFactura').modal('show');
    });

    // Imprimir factura con el nuevo método
    $(document).on('click', '.btn-imprimir', function() {
        const facturaId = $(this).data('id');
        imprimirFactura(facturaId);
    });

    // Pagar factura (crédito)
    $(document).on('click', '.btn-pagar', function() {
        const facturaId = $(this).data('id');
        Swal.fire({
            title: 'Pagar Factura',
            text: 'Esta funcionalidad está en desarrollo',
            icon: 'info',
            confirmButtonText: 'Entendido'
        });
    });

    // Cargar detalle de factura
    function cargarDetalleFactura(facturaId) {
        $.ajax({
            url: '<?php echo SERVERURL; ?>core/misFacturas.php',
            type: 'POST',
            dataType: 'json',
            data: { 
                facturas_id: facturaId,
                action: 'obtener_factura'  // Agregamos action para diferenciar la petición
            },
            success: function(response) {
                if (response.type === 'success') {
                    const factura = response.data[0];
                    
                    // Llenar datos generales
                    $('#numero-factura-modal').text(factura.numero);
                    $('#fecha-factura').text(factura.fecha);
                    $('#cliente-factura').text(factura.cliente);
                    $('#tipo-factura').text(factura.tipo_documento);
                    $('#estado-factura').text(factura.estado_texto);
                    $('#subtotal-factura').text(formatMoney(factura.subtotal));
                    $('#total-factura').text(formatMoney(factura.total));
                    $('#notas-factura').text(factura.notas || 'No hay notas');
                    
                    // Obtener detalles de la factura
                    $.ajax({
                        url: '<?php echo SERVERURL; ?>core/getDetalleFactura.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { 
                            facturas_id: facturaId,
                            db_name: factura.db_name  // Asegurarse de pasar db_name
                        },
                        success: function(detalleResponse) {
                            if (detalleResponse.type === 'success' && detalleResponse.data && detalleResponse.data.length > 0) {
                                let detalleHtml = '';
                                detalleResponse.data.forEach(item => {
                                    detalleHtml += `
                                        <tr>
                                            <td>${item.producto || 'Servicio'}</td>
                                            <td>${item.cantidad} ${item.medida || ''}</td>
                                            <td>${formatMoney(item.precio)}</td>
                                            <td>${formatMoney(item.isv_valor || 0)}</td>
                                            <td>${formatMoney(item.descuento || 0)}</td>
                                            <td>${formatMoney(item.cantidad * item.precio)}</td>
                                        </tr>`;
                                });
                                $('#detalle-factura-body').html(detalleHtml);
                            } else {
                                $('#detalle-factura-body').html('<tr><td colspan="6" class="text-center">No se encontraron detalles para esta factura</td></tr>');
                                console.error('Error o sin datos:', detalleResponse);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error al cargar detalles:', xhr, status, error);
                            $('#detalle-factura-body').html('<tr><td colspan="6" class="text-center">Error al cargar detalles</td></tr>');
                        }
                    });
                    
                    // Configurar botón de impresión
                    $('#btn-imprimir-factura').off('click').on('click', function() {
                        imprimirFactura(facturaId);
                    });
                } else {
                    showNotify(response.type, response.title, response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la carga:', xhr, status, error);
                showNotify('error', 'Error', 'Ocurrió un error al cargar el detalle de la factura');
            }
        });
    }

    // Establecer fechas por defecto (últimos 30 días)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $('#fecha_inicio').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#fecha_fin').val(today.toISOString().split('T')[0]);
    
    // Cargar datos iniciales
    dataTableFacturas.ajax.reload();
    cargarContadorFacturasPendientes();
});
</script>