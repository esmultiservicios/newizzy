<script>
//facturaMovil.php
$(() => {
    // ===============================
    // VARIABLES GLOBALES
    // ===============================
    let productosAgregados = [];        // Array para almacenar productos agregados a la factura
    let currentFacturaId = null;        // ID de la factura actual en proceso
    let secuenciaFactura = null;        // Secuencia de facturación del SAR
    let facturasDisponibles = 0;        // Número de facturas disponibles del SAR
    let lastState = null;               // Último estado del contador de facturas
    let lastFacturasCount = null; // Nueva variable para trackear el count
    let currentProductPrice = 0;        // Precio del producto actual seleccionado
    let cajaAbierta = false;            // Estado de la caja (abierta/cerrada)
    let aperturaInfo = null;            // Información de apertura de caja

    // Formateador de números para montos en lempiras
    const formatter = new Intl.NumberFormat('es-HN', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // ===============================
    // 1. CARGAR DATOS INICIALES
    // ===============================
    cargarClientes();                   // Cargar lista de clientes
    cargarVendedores();                 // Cargar lista de vendedores
    cargarProductos();                  // Cargar lista de productos
    obtenerSecuenciaFactura();          // Obtener secuencia de facturación
    getTotalFacturasDisponibles();      // Obtener facturas disponibles del SAR
    getCajero();                        // Obtener información del cajero
    verificarAperturaCaja();            // Verificar estado de la caja

    // ===============================
    // 2. EVENTOS PRINCIPALES
    // ===============================
    $('#agregar-producto').click(agregarProducto);
    $('#procesar-factura-top, #procesar-factura-bottom').click(procesarFactura);
    $('#cancelar-factura-top, #cancelar-factura-bottom').click(cancelarFactura);
    $('#efectivo-pago, #transferencia-pago, #tarjeta-pago').on('input', calcularCambio);
    $('#registrar-pago').click(registrarPago);
    $('#guardar-descuento').click(guardarDescuento);
    $('#nuevo-descuento-monto').on('input', actualizarDescuentoDesdeMonto);
    $('#nuevo-descuento-porcentaje').on('input', actualizarDescuentoDesdePorcentaje);
    
    // Evento para búsqueda por código de barras
    $('#codigo-barra').on('change keyup paste', function() {
        const codigo = $(this).val().trim();
        if (codigo.length > 3) {
            buscarProductoPorCodigo(codigo);
        }
    });

    // Evento para botón de apertura/cierre de caja
    $(document).on('click', '#btn-apertura-caja', function() {
        var mode = $(this).data('mode');
        if (mode === 'abrir') {
            formAperturaBill();
        } else {
            formCierreBill();
        }
    });

    // Evento cuando se cierra el modal de apertura de caja
    $('#modal_apertura_caja').on('hidden.bs.modal', function() {
        verificarAperturaCaja();
    });

    // ===============================
    // 3. FUNCIONES DE CAJA
    // ===============================

    /**
     * Consulta el estado de apertura de caja del usuario
     * @returns {number} 1 = caja abierta, 2 = caja cerrada
     */
    function getConsultarAperturaCaja() {
        var url = '<?php echo SERVERURL; ?>core/getAperturaCajaUsuario.php';
        var estado_apertura = 2; // Por defecto cerrada

        $.ajax({
            type: 'POST',
            url: url,
            async: false,
            success: function(registro) {
                try {
                    var valores = JSON.parse(registro);
                    estado_apertura = valores[0];
                } catch(e) {
                    console.error("Error parsing apertura caja response:", e);
                    estado_apertura = 2;
                }
            },
            error: function() {
                console.error("Error en AJAX getAperturaCajaUsuario");
                estado_apertura = 2;
            }
        });
        return estado_apertura;
    }

    /**
     * Verifica y actualiza el estado de la caja
     */
    function verificarAperturaCaja() {
        var estado = Number(getConsultarAperturaCaja());
        cajaAbierta = (estado === 1);

        // Actualizar botón de apertura/cierre
        var $btn = $('#btn-apertura-caja');
        if ($btn.length) {
            if (cajaAbierta) {
                $btn.removeClass('btn-primary').addClass('btn-warning')
                    .html('<i class="fas fa-lock mr-1"></i> Cerrar Caja')
                    .data('mode', 'cerrar');
            } else {
                $btn.removeClass('btn-warning').addClass('btn-primary')
                    .html('<i class="fas fa-lock-open mr-1"></i> Aperturar Caja')
                    .data('mode', 'abrir');
            }
        }

        // Actualizar UI según estado de caja
        toggleUIForCajaAbierta(cajaAbierta);
        getTotalFacturasDisponibles(); // Actualizar contador SAR
    }

    /**
     * Habilita/deshabilita la UI según el estado de la caja
     * @param {boolean} abierta - Estado de la caja
     */
    function toggleUIForCajaAbierta(abierta) {
        var disable = !abierta;

        // Habilitar/deshabilitar elementos
        $('#agregar-producto').prop('disabled', disable);
        $('#procesar-factura-top, #procesar-factura-bottom').prop('disabled', disable);
        $('#cancelar-factura-top, #cancelar-factura-bottom').prop('disabled', disable);
        
        $('#cliente-select, #vendedor-select, #producto-select, #cantidad, #descuento, #codigo-barra, #notas')
            .prop('disabled', disable);

        // Actualizar selects
        if ($('#cliente-select').hasClass('selectpicker')) $('#cliente-select').selectpicker('refresh');
        if ($('#vendedor-select').hasClass('selectpicker')) $('#vendedor-select').selectpicker('refresh');
        if ($('#producto-select').hasClass('selectpicker')) $('#producto-select').selectpicker('refresh');

        // Actualizar botones de factura
        if (disable) {
            $('#procesar-factura-top, #procesar-factura-bottom')
                .removeClass('btn-success')
                .addClass('btn-outline-danger')
                .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
        } else {
            $('#procesar-factura-top, #procesar-factura-bottom')
                .removeClass('btn-outline-danger')
                .addClass('btn-success')
                .html('<i class="fas fa-save mr-1"></i> Registrar Factura');
        }
    }

    /**
     * Muestra el modal para aperturar caja
     */
    function formAperturaBill() {
        $('#formAperturaCaja #proceso_aperturaCaja').val("Aperturar Caja");
        $('#open_caja').show();
        $('#close_caja').hide();
        $('#formAperturaCaja #monto_apertura_grupo').show();

        $('#formAperturaCaja').attr({'data-form': 'save'});
        $('#formAperturaCaja').attr({'action': '<?php echo SERVERURL; ?>ajax/addAperturaCajaAjax.php'});

        $('#modal_apertura_caja').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }

    /**
     * Muestra el modal para cerrar caja
     */
    function formCierreBill() {
        $('#formAperturaCaja #proceso_aperturaCaja').val("Cerrar Caja");
        $('#open_caja').hide();
        $('#close_caja').show();
        $('#formAperturaCaja #monto_apertura_grupo').hide();

        $('#formAperturaCaja').attr({'data-form': 'save'});
        $('#formAperturaCaja').attr({'action': '<?php echo SERVERURL; ?>ajax/addCierreCajaFacturasAjax.php'});

        $('#modal_apertura_caja').modal({
            show: true,
            keyboard: false,
            backdrop: 'static'
        });
    }

    // ===============================
    // 4. FUNCIONES DE FACTURAS DISPONIBLES
    // ===============================

    /**
     * Obtiene el total de facturas disponibles del SAR
     */
    function getTotalFacturasDisponibles() {
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>core/getTotalFacturasDisponibles.php?_=' + new Date().getTime(),
            dataType: 'json',
            success: function(datos) {
                facturasDisponibles = datos.facturasPendientes;
                updateCounterUI(datos);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error en AJAX:', textStatus, errorThrown);
                showErrorState();
            }
        });
    }

    /**
     * Actualiza la UI del contador de facturas
     * @param {Object} datos - Datos de facturas disponibles
     */
    function updateCounterUI(datos) {
        const { facturasPendientes, contador, fechaLimite } = datos;
        const counter = $("#factura-counter");
        const daysLeft = parseInt(contador);
        
        console.log('Comparando - LastState:', lastState, 'LastCount:', lastFacturasCount, 'NewCount:', facturasPendientes);
        
        // Determinar el estado actual
        const currentState = getCurrentState(facturasPendientes, daysLeft, fechaLimite);
        
        // Solo actualizar si cambió el estado O el número de facturas
        if (currentState !== lastState || facturasPendientes !== lastFacturasCount) {
            lastState = currentState;
            lastFacturasCount = facturasPendientes; // Guardar el nuevo count
            
            console.log('Actualizando UI - Estado:', currentState, 'Facturas:', facturasPendientes);
            
            // Forzar reinicio de animación
            counter.removeClass('state-change');
            void counter[0].offsetWidth; // Truco para reiniciar animación
            
            // Aplicar cambios
            counter.addClass('state-change');
            
            // Actualizar contenido después de un pequeño delay para que se vea la animación
            setTimeout(() => {
                const config = getStateConfig(currentState, facturasPendientes, daysLeft, fechaLimite);
                counter.html(`<i class="${config.icon}"></i> <span class="counter-value">${config.text}</span>`)
                    .removeClass('counter-normal counter-warning counter-danger counter-expired counter-blocked counter-no-config')
                    .addClass(config.class);
                
                // Animación adicional para cambios importantes
                if (lastState !== currentState) {
                    counter.addClass('counter-update');
                    setTimeout(() => counter.removeClass('counter-update'), 1000);
                }
            }, 300);
        }
        
        updateButtonsState(facturasPendientes, fechaLimite, daysLeft);
    }

    /**
     * Determina el estado actual basado en facturas disponibles y días restantes
     * @param {number} facturasPendientes - Facturas disponibles
     * @param {number} daysLeft - Días restantes para vencimiento
     * @param {string} fechaLimite - Fecha límite de autorización
     * @returns {string} Estado actual
     */
    function getCurrentState(facturasPendientes, daysLeft, fechaLimite) {
        if (!fechaLimite || fechaLimite.trim() === "Sin definir") return 'no-config';
        if (facturasPendientes < 0) return 'blocked';
        if (daysLeft < 0) return 'expired';
        if (daysLeft <= 5) return 'danger';
        if (facturasPendientes <= 9) return 'danger';
        if (facturasPendientes <= 30) return 'warning';
        return 'normal';
    }

    /**
     * Obtiene la configuración para mostrar según el estado
     * @param {string} state - Estado actual
     * @param {number} facturasPendientes - Facturas disponibles
     * @param {number} daysLeft - Días restantes
     * @param {string} fechaLimite - Fecha límite
     * @returns {Object} Configuración para mostrar
     */
    function getStateConfig(state, facturasPendientes, daysLeft, fechaLimite) {
        const facturasFormateadas = facturasPendientes.toLocaleString('es-HN');
        const vencimientoMsg = (daysLeft <= 5) ? 
            `<span class="d-block small">
                ${daysLeft < 0 ? 'Autorizaciones vencidas' : 
                 (daysLeft === 0 ? '<strong>Vencen hoy</strong>' : `Vencen en ${daysLeft} día(s)`)}
            </span>` : '';

        const facturasMsg = `<span class="d-block">${facturasFormateadas} facturas</span>`;

        const configs = {
            'normal': { 
                icon: 'fas fa-file-invoice', 
                class: 'counter-normal', 
                text: facturasMsg 
            },
            'warning': { 
                icon: 'fas fa-hourglass-half', 
                class: 'counter-warning', 
                text: facturasMsg + vencimientoMsg 
            },
            'danger': { 
                icon: 'fas fa-exclamation-triangle', 
                class: 'counter-danger', 
                text: facturasMsg + vencimientoMsg 
            },
            'expired': { 
                icon: 'fas fa-calendar-times', 
                class: 'counter-expired', 
                text: `<span class="d-block">Autorizaciones vencidas</span>
                       <span class="d-block small">
                         <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Actualizar</a>
                       </span>`
            },
            'blocked': { 
                icon: 'fas fa-ban', 
                class: 'counter-blocked', 
                text: `<span class="d-block">Límite alcanzado</span>
                       <span class="d-block small">
                         <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Configurar</a>
                       </span>`
            },
            'no-config': { 
                icon: 'fas fa-calendar-times', 
                class: 'counter-no-config', 
                text: `<span class="d-block">Sin fecha límite</span>
                       <span class="d-block small">
                         <a href="<?php echo SERVERURL; ?>secuencia/" target="_blank" class="text-white">Establecer</a>
                       </span>`
            }
        };

        return configs[state] || configs['normal'];
    }

    /**
     * Actualiza el estado de los botones según disponibilidad de facturas y caja
     * @param {number} facturasPendientes - Facturas disponibles
     * @param {string} fechaLimite - Fecha límite
     * @param {number} daysLeft - Días restantes
     */
    function updateButtonsState(facturasPendientes, fechaLimite, daysLeft) {
        const vencimientoPasado = daysLeft < 0;
        const sarDisabled = facturasPendientes <= 0 || !fechaLimite || fechaLimite.trim() === "Sin definir" || vencimientoPasado;

        const disabled = sarDisabled || !cajaAbierta;

        $('#procesar-factura-top, #procesar-factura-bottom').prop('disabled', disabled);
        $('#agregar-producto, #cancelar-factura-top, #cancelar-factura-bottom').prop('disabled', !cajaAbierta);

        if (disabled) {
            if (sarDisabled && cajaAbierta) {
                $('#procesar-factura-top, #procesar-factura-bottom')
                    .removeClass('btn-success')
                    .addClass('btn-outline-danger')
                    .html('<i class="fas fa-ban mr-1"></i> No disponible (Límite SAR)');
            } else if (!cajaAbierta) {
                $('#procesar-factura-top, #procesar-factura-bottom')
                    .removeClass('btn-success')
                    .addClass('btn-outline-danger')
                    .html('<i class="fas fa-ban mr-1"></i> No disponible (Caja cerrada)');
            }
        } else {
            $('#procesar-factura-top, #procesar-factura-bottom')
                .removeClass('btn-outline-danger')
                .addClass('btn-success')
                .html('<i class="fas fa-save mr-1"></i> Registrar Factura');
        }

        // Actualizar botón de caja
        if (cajaAbierta) {
            $('#btn-apertura-caja')
                .removeClass('btn-primary')
                .addClass('btn-warning')
                .html('<i class="fas fa-lock mr-1"></i> Cerrar Caja')
                .data('mode', 'cerrar');
        } else {
            $('#btn-apertura-caja')
                .removeClass('btn-warning')
                .addClass('btn-primary')
                .html('<i class="fas fa-lock-open mr-1"></i> Aperturar Caja')
                .data('mode', 'abrir');
        }
    }

    /**
     * Muestra estado de error en el contador
     */
    function showErrorState() {
        $('#factura-counter').html(
            `<i class="fas fa-exclamation-circle"></i> <span class="counter-value">Error al cargar</span>`
        ).addClass('counter-danger');
    }
    
    /*FIN CONTEO FACTURAS*/

    // ===============================
    // 5. FUNCIONES DE PRODUCTOS Y FACTURACIÓN
    // ===============================

    /**
     * Agrega un producto a la factura actual
     */
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

        const index = productosAgregados.findIndex(p => p.productoId == productoId);
        
        if (index >= 0) {
            swal({
                title: "Producto existente",
                text: "Este producto ya está en la lista. ¿Desea actualizar la cantidad o agregar como nuevo?",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        visible: true
                    },
                    confirm: {
                        text: "¡Si, actualizar!"
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then((result) => {
                if ((result && result.isConfirmed) || result === true) {
                    productosAgregados[index].cantidad += cantidad;
                    productosAgregados[index].descuento += descuento;
                    actualizarListaProductos();
                    calcularTotales();
                } else if (result && result.isDenied) {
                    productosAgregados.push({ productoId, productoText, precio, cantidad, descuento, isv });
                    actualizarListaProductos();
                    calcularTotales();
                } else if (result === true) {
                    productosAgregados[index].cantidad += cantidad;
                    productosAgregados[index].descuento += descuento;
                    actualizarListaProductos();
                    calcularTotales();
                }
            });
        } else {
            productosAgregados.push({ productoId, productoText, precio, cantidad, descuento, isv });
            actualizarListaProductos();
            calcularTotales();
        }

        $('#producto-select').val('').selectpicker('refresh');
        $('#cantidad').val(1);
        $('#descuento').val('').attr('placeholder', '');
        $('#codigo-barra').val('').focus();
    }

    /**
     * Actualiza la lista visual de productos agregados
     */
    function actualizarListaProductos() {
        const $container = $('#productos-agregados');
        $container.empty();

        if (productosAgregados.length === 0) {
            $container.append('<div class="alert alert-info">No hay productos agregados</div>');
            return;
        }

        productosAgregados.forEach((producto, index) => {
            producto.precio = producto.precio || 0;
            producto.cantidad = producto.cantidad || 1;
            producto.descuento = producto.descuento || 0;
            producto.isv = producto.isv || 0;

            const subtotal = (producto.precio * producto.cantidad) - producto.descuento;
            const isvTotal = producto.isv * producto.cantidad;

            $container.append(`
                <div class="product-item" data-index="${index}">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="mb-0">${producto.productoText}</h6>
                    </div>
                    <div class="product-details">
                        <div class="product-detail">
                            <span>Precio:</span>
                            <strong>L. ${formatter.format(producto.precio)}</strong>
                        </div>
                        <div class="product-detail">
                            <div class="d-flex align-items-center justify-content-between">
                                <span>Cantidad:</span>
                                <div class="cantidad-group d-flex align-items-center mt-2">
                                    <button class="btn btn-outline-secondary btn-sm btn-cantidad-minus" type="button" data-index="${index}">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center input-cantidad mx-1" value="${producto.cantidad}" min="1" data-index="${index}" style="width: 50px;">
                                    <button class="btn btn-outline-secondary btn-sm btn-cantidad-plus" type="button" data-index="${index}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-detail">
                            <span>Descuento:</span>
                             <strong>L. ${formatter.format(producto.descuento)}</strong>
                        </div>
                        <div class="product-detail">
                            <span>ISV:</span>
                             <strong>L. ${formatter.format(isvTotal)}</strong>
                        </div>
                    </div>
                    <div class="product-actions">
                        <button type="button" class="btn btn-warning btn-sm btn-edit-product" data-index="${index}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="product-subtotal">
                        Subtotal: L. ${formatter.format(subtotal)}
                    </div>
                </div>
            `);
        });

        $(document).off('click', '.btn-eliminar-producto').on('click', '.btn-eliminar-producto', function() {
            const index = $(this).closest('.product-item').data('index');
            const producto = productosAgregados[index];
            
            if (index === undefined || !producto) {
                showNotify("error", "Error", "No se pudo encontrar el producto para eliminar");
                return;
            }
            
            swal({
                title: "¿Estás seguro?",
                text: `¿Desea eliminar el producto: ${producto.productoText}?`,
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        visible: true
                    },
                    confirm: {
                        text: "¡Si, eliminar!"
                    }
                },
                dangerMode: true,
                closeOnEsc: false,
                closeOnClickOutside: false
            }).then((result) => {
                if ((result && result.isConfirmed) || result === true) {
                    productosAgregados.splice(index, 1);
                    actualizarListaProductos();
                    calcularTotales();
                    showNotify("success", "Éxito", "Producto eliminado correctamente");
                }
            });
        });
    }

    /**
     * Calcula los totales de la factura
     */
    function calcularTotales() {
        let subtotal = 0;
        let totalDescuento = 0;
        let totalIsv = 0;

        productosAgregados.forEach(producto => {
            producto.precio = producto.precio || 0;
            producto.cantidad = producto.cantidad || 1;
            producto.descuento = producto.descuento || 0;
            producto.isv = producto.isv || 0;

            subtotal += producto.precio * producto.cantidad;
            totalDescuento += producto.descuento;
            totalIsv += producto.isv * producto.cantidad;
        });

        const total = (subtotal - totalDescuento) + totalIsv;

        $('#subtotal').text(`L. ${formatter.format(subtotal - totalDescuento)}`);
        $('#isv').text(`L. ${formatter.format(totalIsv)}`);
        $('#total-descuento').text(`L. ${formatter.format(totalDescuento)}`);
        $('#total').text(`L. ${formatter.format(total)}`);
    }

    /**
     * Procesa la factura actual
     * @param {Event} e - Evento del click
     */
    function procesarFactura(e) {
        e.preventDefault();

        if(!cajaAbierta){
            showNotify("warning", "Caja cerrada", "No puedes procesar facturas sin aperturar caja");
            formAperturaBill();
            return;
        }

        if (!$('#cliente-select').val() || !$('#vendedor-select').val()) {
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
            aperturaId: (aperturaInfo && aperturaInfo.apertura_id) ? aperturaInfo.apertura_id : null
        };

        showNotify("info", "Procesando factura", "Por favor espere...", true);

        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/procesarFactura.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify(datos),
            contentType: 'application/json',
            success: function(response) {
                if (response.estado) {
                    currentFacturaId = response.factura_id;
                    
                    if (tipoFactura == 1) {
                        $('#factura-id-pago').val(response.factura_id);
                        $('#monto-pago').val(response.total);
                        $('#efectivo-pago').val('');
                        $('#transferencia-pago').val('');
                        $('#tarjeta-pago').val('');
                        $('#cambio-pago').val('');
                        $('#pagoModal').modal('show');
                    } else {
                        showNotify("success", "Éxito", "Factura registrada correctamente");
                        resetearFormulario();
                    }
                    
                    getTotalFacturasDisponibles();
                } else {
                    showNotify("error", "Error", response.message || 'Error al procesar la factura');
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", (xhr.responseJSON && xhr.responseJSON.message) || 'Error al procesar la factura');
            }
        });
    }

    // ===============================
    // 6. FUNCIONES DE PAGO
    // ===============================

    /**
     * Registra el pago de una factura
     */
    function registrarPago() {
        const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
        const transferencia = parseFloat($('#transferencia-pago').val()) || 0;
        const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
        const totalPago = efectivo + transferencia + tarjeta;
        const montoFactura = parseFloat($('#monto-pago').val()) || 0;
        
        if (totalPago < montoFactura) {
            showNotify("warning", "Advertencia", "El pago no cubre el total de la factura");
            return;
        }

        const cambioDesdeEfectivo = Math.max(0, efectivo - Math.max(0, montoFactura - (transferencia + tarjeta)));
        $('#cambio-pago').val(formatter.format(cambioDesdeEfectivo));

        const datos = {
            facturaId: $('#factura-id-pago').val(),
            efectivo: efectivo,
            transferencia: transferencia,
            tarjeta: tarjeta,
            cambio: cambioDesdeEfectivo
        };

        showNotify("info", "Registrando pago", "Por favor espere...", true);

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
                    getTotalFacturasDisponibles();
                } else {
                    showNotify("error", "Error", response.message || 'Error al registrar el pago');
                }
            },
            error: function(xhr) {
                showNotify("error", "Error", (xhr.responseJSON && xhr.responseJSON.message) || 'Error al registrar el pago');
            }
        });
    }

    /**
     * Calcula el cambio a devolver
     */
    function calcularCambio() {
        const efectivo = parseFloat($('#efectivo-pago').val()) || 0;
        const transferencia = parseFloat($('#transferencia-pago').val()) || 0;
        const tarjeta = parseFloat($('#tarjeta-pago').val()) || 0;
        const montoFactura = parseFloat($('#monto-pago').val()) || 0;

        const cambioDesdeEfectivo = Math.max(0, efectivo - Math.max(0, montoFactura - (transferencia + tarjeta)));
        $('#cambio-pago').val(cambioDesdeEfectivo > 0 ? formatter.format(cambioDesdeEfectivo) : '');
    }

    // ===============================
    // 7. FUNCIONES AUXILIARES
    // ===============================

    /**
     * Carga la lista de clientes desde el servidor
     */
    function cargarClientes() {
        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/getClientes.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#cliente-select').empty().append('<option value="">Seleccione un cliente</option>');
                $.each(data, function(index, cliente) {
                    $('#cliente-select').append(`<option value="${cliente.clientes_id}">${cliente.nombre} - ${cliente.rtn || 'Sin RTN'}</option>`);
                });
                $('#cliente-select').selectpicker('refresh');
                
                if ($(window).width() < 768) {
                    $('.bootstrap-select').addClass('mobile-select');
                    $('.dropdown-menu').addClass('mobile-dropdown');
                }
            },
            error: function() {
                showNotify("error", "Error", "No se pudieron cargar los clientes");
            }
        });
    }

    /**
     * Carga la lista de vendedores desde el servidor
     */
    function cargarVendedores() {
        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/getVendedores.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#vendedor-select').empty().append('<option value="">Seleccione un vendedor</option>');
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

    /**
     * Carga la lista de productos desde el servidor
     */
    function cargarProductos() {
        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/getProductos.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                $('#producto-select').empty().append('<option value="">Seleccione un producto</option>');
                $.each(data, function(index, producto) {
                    $('#producto-select').append(`<option value="${producto.productos_id}" data-precio="${producto.precio_venta}" data-isv="${producto.isv_venta}">${producto.nombre} - L. ${formatter.format(producto.precio_venta)}</option>`);
                });
                $('#producto-select').selectpicker('refresh');
            },
            error: function() {
                showNotify("error", "Error", "No se pudieron cargar los productos");
            }
        });
    }

    /**
     * Obtiene la secuencia de facturación del SAR
     */
    function obtenerSecuenciaFactura() {
        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/getSecuenciaFactura.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                secuenciaFactura = data;
            },
            error: function() {
                showNotify("error", "Error", "No se pudo obtener la secuencia de facturación");
            }
        });
    }

    /**
     * Cancela la factura en proceso
     */
    function cancelarFactura() {
        if (productosAgregados.length === 0) {
            resetearFormulario();
            return;
        }

        swal({
            title: "¿Estás seguro?",
            text: "¿Desea cancelar la factura en proceso? Se perderán todos los datos ingresados",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    visible: true
                },
                confirm: {
                    text: "¡Si, cancelar!"
                }
            },
            dangerMode: true,
            closeOnEsc: false,
            closeOnClickOutside: false
        }).then((result) => {
            if ((result && result.isConfirmed) || result === true) {
                resetearFormulario();
                showNotify("success", "Éxito", "Factura cancelada correctamente");
            }
        });
    }

    /**
     * Resetea el formulario de facturación
     */
    function resetearFormulario() {
        productosAgregados = [];
        currentFacturaId = null;
        $('#cliente-select, #vendedor-select, #producto-select').val('').selectpicker('refresh');
        $('#cantidad').val(1);
        $('#descuento').val('').attr('placeholder', '0.00');
        $('#notas').val('');
        $('#contado').prop('checked', true);
        actualizarListaProductos();
        calcularTotales();
        obtenerSecuenciaFactura();
    }

    /**
     * Busca un producto por código de barras
     * @param {string} codigo - Código de barras del producto
     */
    function buscarProductoPorCodigo(codigo) {
        $.ajax({
            url: '<?php echo SERVERURL;?>core/facturas/buscarProductoPorCodigo.php',
            type: 'POST',
            data: { codigo: codigo },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.producto) {
                    const producto = response.producto;
                    $('#producto-select').val(producto.productos_id).selectpicker('refresh');
                    agregarProducto();
                    $('#codigo-barra').val('').focus();
                } else {
                    showNotify("warning", "Advertencia", "Producto no encontrado");
                }
            },
            error: function() {
                showNotify("error", "Error", "No se pudo buscar el producto");
            }
        });
    }

    // ===============================
    // 8. FUNCIONES DE DESCUENTO
    // ===============================

    /**
     * Actualiza el descuento desde el monto ingresado
     */
    function actualizarDescuentoDesdeMonto() {
        const monto = parseFloat($('#nuevo-descuento-monto').val()) || 0;
        const porcentaje = currentProductPrice > 0 ? (monto / currentProductPrice) * 100 : 0;
        $('#nuevo-descuento-porcentaje').val(porcentaje.toFixed(2));
        $('#descuento-total').val(`L. ${formatter.format(monto)} (${porcentaje.toFixed(2)}%)`);
    }

    /**
     * Actualiza el descuento desde el porcentaje ingresado
     */
    function actualizarDescuentoDesdePorcentaje() {
        const porcentaje = parseFloat($('#nuevo-descuento-porcentaje').val()) || 0;
        const monto = (porcentaje / 100) * currentProductPrice;
        $('#nuevo-descuento-monto').val((monto || 0).toFixed(2));
        $('#descuento-total').val(`L. ${formatter.format(monto || 0)} (${porcentaje.toFixed(2)}%)`);
    }

    /**
     * Guarda el descuento aplicado a un producto
     */
    function guardarDescuento() {
        const index = $('#producto-index').val();
        const nuevoDescuento = parseFloat($('#nuevo-descuento-monto').val()) || 0;
        
        if (index !== null && productosAgregados[index]) {
            const precioTotal = (productosAgregados[index].precio * productosAgregados[index].cantidad) || 0;
            if (nuevoDescuento > precioTotal) {
                showNotify("error", "Error", "El descuento no puede ser mayor al precio total");
                return;
            }
            
            productosAgregados[index].descuento = nuevoDescuento;
            actualizarListaProductos();
            calcularTotales();
            
            $('#editarDescuentoModal').modal('hide');
            showNotify("success", "Éxito", "Descuento actualizado correctamente");
        } else {
            showNotify("error", "Error", "No se pudo actualizar el descuento: producto no encontrado");
        }
    }

    // Eventos para el modal de descuento
    $('#editarDescuentoModal').on('shown.bs.modal', function() {
        $('#descuento-tab .nav-link').first().trigger('click');
        $('#nuevo-descuento-monto').trigger('focus');
    });

    $('#editarDescuentoModal').on('hidden.bs.modal', function() {
        $('#editar-descuento-form')[0].reset();
        $('#descuento-total').val('');
        $('#producto-index').val('');
    });

    // Intervalo para verificar estado de caja y facturas disponibles cada minuto
    setInterval(() => {
        verificarAperturaCaja();
        getTotalFacturasDisponibles();
    }, 60000);
});
</script>