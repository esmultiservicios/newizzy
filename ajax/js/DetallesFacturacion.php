<script>
// Código JavaScript actualizado y mejorado
$(() => {
    // Función para formatear dinero
    function formatMoney(amount) {
        return 'L. ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Función para mostrar notificaciones
    function showNotify(type, title, message) {
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            confirmButtonText: 'Aceptar'
        });
    }

    // Función para imprimir factura
    function imprimirFactura(facturaId) {
        const params = {
            "id": facturaId,
            "type": "Factura_carta_izzy",
            "db": "<?php echo DB_MAIN; ?>"
        };
        viewReport(params); 
    }

    // Inicializar DataTable
    const dataTableFacturas = $('#dataTableFacturas').DataTable({
        responsive: true,
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?php echo SERVERURL; ?>core/DetallesFacturacion.php',
            type: 'POST'
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
                    if (type === 'display') {
                        let badgeClass, icon, text;
                        const esProforma = row.documento_id == 4;
                        
                        if (esProforma) {
                            if (row.tiene_pendiente == 1) {
                                badgeClass = 'badge badge-pill badge-warning text-dark';
                                icon = '<i class="fas fa-clock mr-1"></i>';
                                text = 'Pendiente de pago';
                            } else {
                                badgeClass = 'badge badge-pill badge-info';
                                icon = '<i class="fas fa-file-invoice mr-1"></i>';
                                text = 'Proforma cerrada';
                            }
                        } else {
                            const estadoNum = parseInt(data, 10);
                            
                            switch (estadoNum) {
                                case 2:
                                    badgeClass = 'badge badge-pill badge-success';
                                    icon = '<i class="fas fa-check-circle mr-1"></i>';
                                    text = row.estado_texto;
                                    break;
                                case 3:
                                    badgeClass = 'badge badge-pill badge-warning text-dark';
                                    icon = '<i class="fas fa-clock mr-1"></i>';
                                    text = row.estado_texto;
                                    break;
                                case 4:
                                    badgeClass = 'badge badge-pill badge-danger';
                                    icon = '<i class="fas fa-times-circle mr-1"></i>';
                                    text = row.estado_texto;
                                    break;
                                default:
                                    badgeClass = 'badge badge-pill badge-secondary';
                                    icon = '<i class="fas fa-file-alt mr-1"></i>';
                                    text = row.estado_texto;
                            }
                        }
                        
                        return `<span class="${badgeClass}" style="font-size: 0.95rem; padding: 0.5em 0.8em; font-weight: 600;">
                                    ${icon}${text}
                                </span>`;
                    }
                    return row.estado_texto;
                }
            },
            { 
                data: 'subtotal',
                render: function(data) {
                    return `<span class="text-nowrap">${formatMoney(data)}</span>`;
                }
            },
            { 
                data: 'isv',
                render: function(data) {
                    return `<span class="text-nowrap">${formatMoney(data)}</span>`;
                }
            },
            { 
                data: 'descuento',
                render: function(data) {
                    return `<span class="text-nowrap">${formatMoney(data)}</span>`;
                }
            },
            { 
                data: 'total',
                render: function(data) {
                    return `<strong class="text-nowrap">${formatMoney(data)}</strong>`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-info btn-sm btn-detalle mr-1" title="Ver Detalle" data-id="${row.facturas_id}">
                            <i class="fas fa-eye mr-1"></i> Ver
                        </button>
                        <button class="btn btn-primary btn-sm btn-imprimir mr-1" title="Imprimir Factura" 
                                data-id="${row.facturas_id}" data-db="${row.db_name}">
                            <i class="fas fa-print mr-1"></i> Imprimir
                        </button>`;
                    
                    // Mostrar botón de pago para:
                    // 1. Facturas al crédito (estado = 3) O
                    // 2. Proformas (documento_id = 4) que tengan pendiente en cobrar_clientes (tiene_pendiente > 0)
                    if (row.estado == '3' || (row.documento_id == 4 && row.tiene_pendiente > 0)) {
                        buttons += `
                            <button class="btn btn-success btn-sm btn-pagar" title="Pagar Factura" data-id="${row.facturas_id}">
                                <i class="fas fa-money-bill-wave mr-1"></i> Pagar
                            </button>`;
                    }
                    
                    return `<div class="btn-group btn-group-sm">${buttons}</div>`;
                },
                orderable: false
            }
        ],
        order: [[1, 'desc']],
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar ascendente",
                "sortDescending": ": activar para ordenar descendente"
            }
        },
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
        dataTableFacturas.ajax.url('<?php echo SERVERURL; ?>core/DetallesFacturacion.php?' + $(this).serialize()).load();
    });

    // Limpiar filtros
    $('#btn-limpiar-filtros').click(function() {
        $('#form-filtros-facturas')[0].reset();
        if ($.fn.selectpicker) {
            $('select').selectpicker('refresh');
        }
        dataTableFacturas.ajax.url('<?php echo SERVERURL; ?>core/DetallesFacturacion.php').load();
    });

    // Ver detalle de factura
    $(document).on('click', '.btn-detalle', function() {
        const facturaId = $(this).data('id');
        cargarDetalleFactura(facturaId);
        $('#modalDetalleFactura').modal({
            backdrop: 'static',
            keyboard: false
        });
    });

    // Imprimir factura
    $(document).on('click', '.btn-imprimir', function() {
        const facturaId = $(this).data('id');
        imprimirFactura(facturaId);
    });

    // Pagar factura (crédito o proforma)
    $(document).on('click', '.btn-pagar', function() {
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
                pagarFactura(facturaId);
            }
        });
    });

    // Función para pagar factura
    function pagarFactura(facturaId) {
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
                    showNotify('success', 'Éxito', response.message);
                    dataTableFacturas.ajax.reload(null, false);
                } else {
                    showNotify('error', 'Error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showNotify('error', 'Error', 'Ocurrió un error al procesar el pago');
                console.error('Error al pagar factura:', error);
            }
        });
    }

    // Cargar detalle de factura
    function cargarDetalleFactura(facturaId) {
        $.ajax({
            url: '<?php echo SERVERURL; ?>core/DetallesFacturacion.php',
            type: 'POST',
            dataType: 'json',
            data: { facturas_id: facturaId },
            success: function(response) {
                if (response.type === 'success' && response.data && response.data.length > 0) {
                    const factura = response.data[0];   
                    const facturaEncontrada = response.data.find(factura => factura.facturas_id === facturaId);
                    var estadoNum;

                    if (facturaEncontrada) {
                        // Llenar datos generales
                        $('#numero-factura-modal').text(facturaEncontrada.numero);
                        $('#fecha-factura').text(facturaEncontrada.fecha);
                        $('#cliente-factura').text(facturaEncontrada.cliente);
                        $('#tipo-factura').text(facturaEncontrada.tipo_documento);
                        estadoNum = parseInt(facturaEncontrada.estado, 10);
                        
                        // Estado con badge
                        let estadoBadge = '';
                        const esProforma = facturaEncontrada.documento_id == 4;

                        if (esProforma) {
                            estadoBadge = '<span class="badge badge-pill badge-warning text-dark">Pendiente de pago</span>';
                        } else {
                            switch(estadoNum) {
                                case 2:
                                    estadoBadge = '<span class="badge badge-pill badge-success">Pagada</span>';
                                    break;
                                case 3:
                                    estadoBadge = '<span class="badge badge-pill badge-warning text-dark">Crédito</span>';
                                    break;
                                case 4:
                                    estadoBadge = '<span class="badge badge-pill badge-danger">Cancelada</span>';
                                    break;
                                default:
                                    estadoBadge = '<span class="badge badge-pill badge-secondary">Borrador</span>';
                            }
                        }
                        $('#estado-factura').html(estadoBadge);
                        
                        $('#subtotal-factura').text(formatMoney(factura.subtotal));
                        $('#total-factura').text(formatMoney(factura.total));
                        $('#notas-factura').text(factura.notas || 'No hay notas');
                        
                        // Mostrar indicador de carga
                        $('#detalle-factura-body').html(`
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                    <p class="mt-2">Cargando detalles...</p>
                                </td>
                            </tr>
                        `);
                        
                        // Obtener detalles de la factura
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
                                    detalleResponse.data.forEach(item => {
                                        const subtotal = item.cantidad * item.precio;
                                        detalleHtml += `
                                            <tr>
                                                <td>${item.producto || 'Servicio'}</td>
                                                <td class="text-center">${item.cantidad} ${item.medida || ''}</td>
                                                <td class="text-right">${formatMoney(item.precio)}</td>
                                                <td class="text-right">${formatMoney(item.isv_valor || 0)}</td>
                                                <td class="text-right">${formatMoney(item.descuento || 0)}</td>
                                                <td class="text-right">${formatMoney(subtotal)}</td>
                                            </tr>`;
                                    });
                                    
                                    // Agregar totales
                                    detalleHtml += `
                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                            <td colspan="3" class="text-right">${formatMoney(factura.subtotal)}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right"><strong>ISV:</strong></td>
                                            <td colspan="3" class="text-right">${formatMoney(factura.isv)}</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right"><strong>Descuento:</strong></td>
                                            <td colspan="3" class="text-right">${formatMoney(factura.descuento)}</td>
                                        </tr>
                                        <tr class="bg-primary text-white">
                                            <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                                            <td colspan="3" class="text-right"><strong>${formatMoney(factura.total)}</strong></td>
                                        </tr>`;
                                    
                                    $('#detalle-factura-body').html(detalleHtml);
                                } else {
                                    $('#detalle-factura-body').html(`
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                                No se encontraron detalles para esta factura
                                            </td>
                                        </tr>`);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error al cargar detalles:', error);
                                $('#detalle-factura-body').html(`
                                    <tr>
                                        <td colspan="6" class="text-center text-danger py-4">
                                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                            Error al cargar los detalles de la factura<br>
                                            <small class="text-muted mt-2">Error: ${error}</small><br>
                                            <button class="btn btn-outline-primary btn-sm mt-3 btn-retry-detalle" data-id="${facturaId}">
                                                <i class="fas fa-sync-alt mr-1"></i> Reintentar
                                            </button>
                                        </td>
                                    </tr>`);
                            }
                        });
                        
                        // Configurar botón de impresión
                        $('#btn-imprimir-factura').off('click').on('click', function() {
                            imprimirFactura(facturaId);
                        });
                    } else {
                        showNotify('error', 'Error', 'No se encontraron datos de la factura');
                        $('#modalDetalleFactura').modal('hide');
                    }
                } else {
                    showNotify('error', 'Error', 'No se encontraron datos de la factura');
                    $('#modalDetalleFactura').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la carga:', error);
                showNotify('error', 'Error', 'Ocurrió un error al cargar el detalle de la factura');
                $('#modalDetalleFactura').modal('hide');
            }
        });
    }
    
    // Reintentar cargar detalles
    $(document).on('click', '.btn-retry-detalle', function() {
        const facturaId = $(this).data('id');
        cargarDetalleFactura(facturaId);
    });

    // Establecer fechas por defecto (últimos 30 días)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    $('#fecha_inicio').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#fecha_fin').val(today.toISOString().split('T')[0]);
    
    // Cargar datos iniciales
    dataTableFacturas.ajax.reload(null, false);
});
</script>