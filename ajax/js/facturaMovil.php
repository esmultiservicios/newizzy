<script>
    $(document).ready(function() {
        // Variables globales
        let productosAgregados = [];
        let currentFacturaId = null;
        let secuenciaFactura = null;

        // Cargar datos iniciales
        cargarClientes();
        cargarVendedores();
        cargarProductos();
        obtenerSecuenciaFactura();

        // Configurar eventos
        $('#agregar-producto').click(agregarProducto);
        $('#procesar-factura').click(procesarFactura);
        $('#cancelar-factura').click(cancelarFactura);
        $('#efectivo-pago').on('input', calcularCambio);
        $('#registrar-pago').click(registrarPago);

        // Función para cargar clientes
        function cargarClientes() {
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/getClientes.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#cliente-select').empty();
                    $('#cliente-select').append('<option value="">Seleccione un cliente</option>');
                    $.each(data, function(index, cliente) {
                        $('#cliente-select').append(`<option value="${cliente.clientes_id}">${cliente.nombre} - ${cliente.rtn || 'Sin RTN'}</option>`);
                    });
                    $('#cliente-select').selectpicker('refresh');
                },
                error: function() {
                    showNotify("error", "Error", "No se pudieron cargar los clientes");
                }
            });
        }

        // Función para cargar vendedores
        function cargarVendedores() {
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/getVendedores.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#vendedor-select').empty();
                    $('#vendedor-select').append('<option value="">Seleccione un vendedor</option>');
                    $.each(data, function(index, vendedor) {
                        $('#vendedor-select').append(`<option value="${vendedor.colaboradores_id}">${vendedor.nombre}</option>`);
                    });
                    $('#vendedor-select').selectpicker('refresh');
                },
                error: function() {
                    showNotify("error", "Error", "No se pudieron cargar los vendedores");
                }
            });
        }

        // Función para cargar productos
        function cargarProductos() {
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/getProductos.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#producto-select').empty();
                    $('#producto-select').append('<option value="">Seleccione un producto</option>');
                    $.each(data, function(index, producto) {
                        $('#producto-select').append(`<option value="${producto.productos_id}" data-precio="${producto.precio_venta}" data-isv="${producto.isv_venta}">${producto.nombre} - L. ${parseFloat(producto.precio_venta).toFixed(2)}</option>`);
                    });
                    $('#producto-select').selectpicker('refresh');
                },
                error: function() {
                    showNotify("error", "Error", "No se pudieron cargar los productos");
                }
            });
        }

        // Función para obtener secuencia de factura
        function obtenerSecuenciaFactura() {
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/getSecuenciaFactura.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    secuenciaFactura = data;
                    $('#factura-number').text(`${data.prefijo}${String(data.siguiente).padStart(data.relleno, '0')}`);
                },
                error: function() {
                    showNotify("error", "Error", "No se pudo obtener la secuencia de facturación");
                }
            });
        }

        // Función para agregar producto
        function agregarProducto() {
            const productoId = $('#producto-select').val();
            const productoText = $('#producto-select option:selected').text();
            const precio = parseFloat($('#producto-select option:selected').data('precio'));
            const cantidad = parseInt($('#cantidad').val()) || 1;
            const descuento = parseFloat($('#descuento').val()) || 0;
            const isv = $('#producto-select option:selected').data('isv') == 1 ? precio * 0.15 : 0;

            if (!productoId) {
                showNotify("warning", "Advertencia", "Seleccione un producto");
                return;
            }

            // Verificar si el producto ya está agregado
            const index = productosAgregados.findIndex(p => p.productoId == productoId);
            
            if (index >= 0) {
                // Actualizar cantidad si ya existe
                productosAgregados[index].cantidad += cantidad;
                productosAgregados[index].descuento += descuento;
            } else {
                // Agregar nuevo producto
                productosAgregados.push({
                    productoId,
                    productoText,
                    precio,
                    cantidad,
                    descuento,
                    isv
                });
            }

            // Limpiar campos
            $('#producto-select').val('').selectpicker('refresh');
            $('#cantidad').val(1);
            $('#descuento').val(0);

            // Actualizar vista
            actualizarListaProductos();
            calcularTotales();
        }

        // Función para actualizar lista de productos
        function actualizarListaProductos() {
            const $container = $('#productos-agregados');
            $container.empty();

            if (productosAgregados.length === 0) {
                $container.append('<div class="alert alert-info">No hay productos agregados</div>');
                return;
            }

            productosAgregados.forEach((producto, index) => {
                const subtotal = (producto.precio * producto.cantidad) - producto.descuento;
                const isvTotal = producto.isv * producto.cantidad;
                const total = subtotal + isvTotal;

                $container.append(`
                    <div class="product-item" data-index="${index}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">${producto.productoText}</h6>
                            <button type="button" class="btn btn-sm btn-danger btn-eliminar-producto">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Precio: L. ${producto.precio.toFixed(2)}</small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Cantidad: ${producto.cantidad}</small>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-6">
                                <small class="text-muted">Descuento: L. ${producto.descuento.toFixed(2)}</small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">ISV: L. ${isvTotal.toFixed(2)}</small>
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <strong>Subtotal: L. ${subtotal.toFixed(2)}</strong>
                        </div>
                    </div>
                `);
            });

            // Configurar evento para botones de eliminar
            $('.btn-eliminar-producto').click(function() {
                const index = $(this).closest('.product-item').data('index');
                productosAgregados.splice(index, 1);
                actualizarListaProductos();
                calcularTotales();
            });
        }

        // Función para calcular totales
        function calcularTotales() {
            let subtotal = 0;
            let totalDescuento = 0;
            let totalIsv = 0;

            productosAgregados.forEach(producto => {
                subtotal += producto.precio * producto.cantidad;
                totalDescuento += producto.descuento;
                totalIsv += producto.isv * producto.cantidad;
            });

            const total = (subtotal - totalDescuento) + totalIsv;

            $('#subtotal').text(`L. ${(subtotal - totalDescuento).toFixed(2)}`);
            $('#isv').text(`L. ${totalIsv.toFixed(2)}`);
            $('#total-descuento').text(`L. ${totalDescuento.toFixed(2)}`);
            $('#total').text(`L. ${total.toFixed(2)}`);
        }

        // Función para procesar factura
        function procesarFactura(e) {
            e.preventDefault();

            // Validaciones
            if ($('#cliente-select').val() === null || $('#vendedor-select').val() === null) {
                showNotify("warning", "Advertencia", "Seleccione cliente y vendedor");
                return;
            }

            if (productosAgregados.length === 0) {
                showNotify("warning", "Advertencia", "Agregue al menos un producto");
                return;
            }

            const tipoFactura = $('input[name="tipo-factura"]:checked').val();
            const datos = {
                clienteId: $('#cliente-select').val(),
                vendedorId: $('#vendedor-select').val(),
                tipoFactura: tipoFactura,
                productos: productosAgregados,
                notas: $('#notas').val(),
                secuenciaId: secuenciaFactura.secuencia_facturacion_id,
                prefijo: secuenciaFactura.prefijo,
                numero: secuenciaFactura.siguiente
            };

            // Mostrar carga
            showNotify("info", "Procesando factura", "Por favor espere...", true);

            // Enviar datos al servidor
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/procesarFactura.php',
                type: 'POST',
                dataType: 'json',
                data: JSON.stringify(datos),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        currentFacturaId = response.factura_id;
                        
                        if (tipoFactura == 1) { // Contado
                            $('#factura-id-pago').val(response.factura_id);
                            $('#monto-pago').val(response.total);
                            $('#efectivo-pago').val('');
                            $('#cambio-pago').val('');
                            $('#tarjeta-pago').val(0);
                            
                            const pagoModal = new bootstrap.Modal(document.getElementById('pagoModal'));
                            pagoModal.show();
                        } else { // Crédito
                            showNotify("success", "Éxito", "Factura registrada correctamente");
                            resetearFormulario();
                        }
                    } else {
                        showNotify("error", "Error", response.message || 'Error al procesar la factura');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Error al procesar la factura';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showNotify("error", "Error", errorMsg);
                }
            });
        }

        // Función para registrar pago
        function registrarPago() {
            const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
            const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
            const totalPago = efectivo + tarjeta;
            const montoFactura = parseFloat($('#monto-pago').val());
            
            if (totalPago < montoFactura) {
                showNotify("warning", "Advertencia", "El pago no cubre el total de la factura");
                return;
            }

            const datos = {
                facturaId: $('#factura-id-pago').val(),
                efectivo: efectivo,
                tarjeta: tarjeta,
                cambio: parseFloat($('#cambio-pago').val()) || 0
            };

            // Mostrar carga
            showNotify("info", "Registrando pago", "Por favor espere...", true);

            // Enviar datos al servidor
            $.ajax({
                url: '<?php echo SERVERURL;?>core/facturas/registrarPago.php',
                type: 'POST',
                dataType: 'json',
                data: JSON.stringify(datos),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        $('#pagoModal').modal('hide');
                        showNotify("success", "Éxito", "Pago registrado correctamente");
                        resetearFormulario();
                    } else {
                        showNotify("error", "Error", response.message || 'Error al registrar el pago');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Error al registrar el pago';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showNotify("error", "Error", errorMsg);
                }
            });
        }

        // Función para calcular cambio
        function calcularCambio() {
            const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
            const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
            const totalPago = efectivo + tarjeta;
            const montoFactura = parseFloat($('#monto-pago').val());
            
            if (totalPago >= montoFactura) {
                $('#cambio-pago').val((totalPago - montoFactura).toFixed(2));
            } else {
                $('#cambio-pago').val('0.00');
            }
        }

        // Función para cancelar factura
        function cancelarFactura() {
            // Crear un diálogo de confirmación personalizado
            const confirmDialog = `
                <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title">¿Cancelar factura?</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Se perderán todos los datos ingresados</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, continuar</button>
                                <button type="button" class="btn btn-danger" id="confirmCancel">Sí, cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar el modal al DOM
            $('body').append(confirmDialog);
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('confirmCancelModal'));
            modal.show();
            
            // Configurar evento para el botón de confirmación
            $('#confirmCancel').click(function() {
                modal.hide();
                resetearFormulario();
                $('#confirmCancelModal').remove();
            });
            
            // Eliminar el modal cuando se cierre
            $('#confirmCancelModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }

        // Función para resetear formulario
        function resetearFormulario() {
            productosAgregados = [];
            currentFacturaId = null;
            
            $('#cliente-select').val('').selectpicker('refresh');
            $('#vendedor-select').val('').selectpicker('refresh');
            $('#producto-select').val('').selectpicker('refresh');
            $('#cantidad').val(1);
            $('#descuento').val(0);
            $('#notas').val('');
            $('#contado').prop('checked', true);
            
            actualizarListaProductos();
            calcularTotales();
            
            // Obtener nueva secuencia de factura
            obtenerSecuenciaFactura();
        }
    });
</script>