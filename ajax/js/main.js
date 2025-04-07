$('.FormularioAjax').submit(function (e) {
	e.preventDefault();

	var form = $(this);
	var tipo = form.attr('data-form');
	var action = form.attr('action');
	var method = form.attr('method');
	var respuesta = form.children('.RespuestaAjax');

	// Deshabilitar el botón antes de hacer la solicitud AJAX
	form.find('button[type="submit"]').prop('disabled', true);

	var msjError = "<script>swal({title: 'Ocurrió un error inesperado', text: 'Por favor, intenta de nuevo', icon: 'error', dangerMode: true, closeOnEsc: false, closeOnClickOutside: false});</script>";
	var formdata = new FormData(this);

	var textoAlerta;
	var type;
	var classButtom;

	if (tipo == "save") {
		textoAlerta = "Los datos que enviarás quedarán almacenados en el sistema";
		type = "info";
		classButtom = "btn-primary";
	} else if (tipo == "delete") {
		textoAlerta = "Los datos serán eliminados completamente del sistema";
		type = "warning";
		classButtom = "btn-warning";
	} else if (tipo == "update") {
		textoAlerta = "Los datos del sistema serán actualizados";
		type = "info";
		classButtom = "btn-info";
	} else {
		textoAlerta = "¿Quieres realizar la operación solicitada?";
		type = "warning";		
		classButtom = "btn-warning";
	}

	swal({
		title: "¿Estás seguro?",
		text: textoAlerta,
		icon: type,
		buttons: {
			cancel: {
				text: "Cancelar",
				visible: true,
				closeModal: true
			},
			confirm: {
				text: "Aceptar",
				className: classButtom,
				closeModal: false
			}
		},
		dangerMode: false,
		closeOnEsc: false, // Desactiva el cierre con la tecla Esc
		closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera		
	}).then((isConfirm) => {
		if (isConfirm) {
			swal.stopLoading();
			swal.close();
	
			$.ajax({
				type: method,
				url: action,
				data: formdata ? formdata : form.serialize(),
				cache: false,
				contentType: false,
				processData: false,
				xhr: () => {
					const xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", (evt) => {
						if (evt.lengthComputable) {
							let percentComplete = parseInt((evt.loaded / evt.total) * 100);
							if (percentComplete < 100) {
								respuesta.html(`
									<p class="text-center">Procesando... (${percentComplete}%)</p>
									<div class="progress progress-striped active">
										<div class="progress-bar progress-bar-info" style="width: ${percentComplete}%;"></div>
									</div>
								`);
							} else {
								respuesta.html('<p class="text-center"></p>');
							}
						}
					}, false);
					return xhr;
				},
				success: (data) => {
					respuesta.html(data);
					form.find('button[type="submit"]').prop('disabled', false);
				},
				error: () => {
					respuesta.html(msjError);
				}
			});
		} else {
			form.find('button[type="submit"]').prop('disabled', false);
		}
	});
});
//FIN LOGIN FORM

const notyf = new Notyf({
    position: {
        x: 'right',
        y: 'top',
    },
    dismissible: true, // Permite cerrar manualmente
    closeOnClick: true, // Cierra al hacer clic
    types: [
        {
            type: 'warning',
            background: 'orange',
            duration: 5000, // 5 segundos
            icon: {
                className: 'fas fa-exclamation-triangle fa-lg',
                tagName: 'i',
                color: 'white',
            },
            closeIcon: {
                className: 'fas fa-times',
                color: 'white',
                tagName: 'span',
                position: 'right',
				style: 'margin-right: 15px;'
            }
        },
        {
            type: 'error',
            background: 'indianred',
            duration: 10000, // 10 segundos (más tiempo para errores)
            dismissible: true,
            icon: {
                className: 'fas fa-times-circle fa-lg',
                tagName: 'i',
                color: 'white'
            },
            closeIcon: {
                className: 'fas fa-times',
                color: 'white',
                tagName: 'span',
                position: 'right'
            }
        },
        {
            type: 'info',
            background: '#1e88e5',
            duration: 5000, // 5 segundos
            dismissible: true,
            icon: {
                className: 'fas fa-info-circle fa-lg',
                tagName: 'i',
                color: 'white'
            },
            closeIcon: {
                className: 'fas fa-times',
                color: 'white',
                tagName: 'span',
                position: 'right'
            }
        },
        {
            type: 'success',
            background: '#4caf50',
            duration: 5000, // 5 segundos
            dismissible: true,
            icon: {
                className: 'fas fa-check-circle fa-lg',
                tagName: 'i',
                color: 'white'
            },
            closeIcon: {
                className: 'fas fa-times',
                color: 'white',
                tagName: 'span',
                position: 'right'
            }
        },
        {
            type: 'loading',
            background: '#3498db',  // Azul profesional
            duration: 5000,         //5 segundos
            icon: {
                className: 'fas fa-circle-notch fa-spin', // Icono giratorio
                tagName: 'i',
                color: 'white'
            },
            dismissible: false,     // No se puede cerrar manualmente
            closeIcon: false        // Sin botón de cerrar
        }        
    ]
});

// Variable global para controlar la notificación de carga
let loadingNotification = null;

/**
 * Muestra una notificación de carga
 * @param {string} message - Mensaje a mostrar durante la carga
 */
function showLoading(message = "Procesando, por favor espere...") {
    // Mostrar nueva notificación
    loadingNotification = notyf.open({
        type: 'loading',
        message: message
    });
}

/**
 * Muestra una notificación estilizada al usuario.
 * @param {string} title - El título de la notificación (ej: 'Éxito', 'Error', 'Advertencia')
 * @param {string} message - El mensaje detallado a mostrar (ej: 'Los datos se guardaron correctamente')
 * @param {'success'|'error'|'warning'|'info'} type - Tipo de notificación (valores válidos: 'success', 'error', 'warning', 'info')
 * @example
 * // Muestra una notificación de éxito
 * showNotify('Éxito', 'Los datos se guardaron correctamente', 'success');
 * @example
 * // Muestra una notificación de error
 * showNotify('Error', 'No se pudo conectar al servidor', 'error');
 */
function showNotify(type, title, message) {
    const validTypes = ['success', 'error', 'warning', 'info', 'loading'];
    
    if (validTypes.includes(type)) {
        notyf.open({
            type: type,
            message: `<strong>${title}</strong><br>${message}`,
            settings: {
                ripple: true,
                allowHtml: true,
            }
        });
    } else {
        console.error('Tipo de notificación no válido');
    }
}

//INICIO BUSCAR DATOS EN TABLA
$(document).ready(function () {
	$("#formBuscarColaboradores #colaborador_id").on("keyup", function () {
		var value = $(this).val().toLowerCase();
		$("#myTable tr").filter(function () {
			$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
});
//FIN BUSCAR DATOS EN TABLA
/*############################################################################################################################################################################################*/
/*############################################################################################################################################################################################*/
/*INICIO DE FUNCIONES PARA ESTABLECER EL FOCUS PARA LAS VENTANAS MODALES*/
$(document).ready(function () {
	$("#modalCuentascontables").on('shown.bs.modal', function () {
		$(this).find('#formCuentasContables #cuenta_codigo').focus();
	});
});

$(document).ready(function () {
	$("#modal_registrar_productos").on('shown.bs.modal', function () {
		$(this).find('#formProductos #bar_code_product').focus();
	});
});

$(document).ready(function () {
	$("#modal_buscar_empresa").on('shown.bs.modal', function () {
		$(this).find('#formulario_busqueda_empreasa #buscar').focus();
	});
});

$(document).ready(function () {
	$("#ModalContraseña").on('shown.bs.modal', function () {
		$(this).find('#form-cambiarcontra #contranaterior').focus();
	});
});

$(document).ready(function () {
	$("#modal_pagos").on('shown.bs.modal', function () {
		$(this).find('#formEfectivoBill #efectivo_bill').focus();
	});
});

$(document).ready(function () {
	$("#modal_pagos").on('shown.bs.modal', function () {
		$(this).find('#formTarjetaBill #cr_bill').focus();
	});
});

$(document).ready(function () {
	$("#modal_pagos").on('shown.bs.modal', function () {
		$(this).find('#formTransferenciaBill #bk_nm').focus();
	});
});

$(document).ready(function () {
	$("#modal_apertura_caja").on('shown.bs.modal', function () {
		$(this).find('#formTransferenciaBill #bk_nm').focus();
	});
});

$(document).ready(function () {
	$("#modalConfTipoPago").on('shown.bs.modal', function () {
		$(this).find('#formConfTipoPago #confTipoPago').focus();
	});
});
/*FIN DE FUNCIONES PARA ESTABLECER EL FOCUS PARA LAS VENTANAS MODALES*/


$('#formProductos #descripcion').keyup(function () {
	var max_chars = 100;
	var chars = $(this).val().length;
	var diff = max_chars - chars;

	$('#formProductos #charNum_descripcion').html(diff + ' Caracteres');

	if (diff == 0) {
		return false;
	}
});

$('#invoice-form #notes').keyup(function () {
	var max_chars = 255;
	var chars = $(this).val().length;
	var diff = max_chars - chars;

	$('#invoice-form #charNum_notas').html(diff + ' Caracteres');

	if (diff == 0) {
		return false;
	}
});

$(function () {
	$('[data-toggle="tooltip"]').tooltip({
		trigger: "hover"
	});
  });

//INICIO MENU FORM PAGOS FACTURAS
$(document).ready(function () {
	$(".menu-toggle2").hide();

	//Menu Toggle Script
	$("#menu-toggle1").click(function (e) {
		e.preventDefault();
		$("#wrapper").toggleClass("toggled");
	});

	$("#menu-toggle2").click(function (e) {
		e.preventDefault();
		$("#wrapper").toggleClass("toggled");
	});

	// For highlighting activated tabs
	$("#tab1").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab1").addClass("active1");
		$("#tab1").removeClass("bg-light");
	});

	$("#tab2").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab2").addClass("active1");
		$("#tab2").removeClass("bg-light");
	});

	$("#tab3").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab3").addClass("active1");
		$("#tab3").removeClass("bg-light");
	});

	$("#tab4").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab4").addClass("active1");
		$("#tab4").removeClass("bg-light");
	});
})

$(".menu-toggle1").on("click", function (e) {
	e.preventDefault();
	$(".menu-toggle1").hide();
	$(".menu-toggle2").show();
	$("#modal_pagos #sidebar-wrapper").hide();
});

$(".menu-toggle2").on("click", function (e) {
	e.preventDefault();
	$(".menu-toggle2").hide();
	$(".menu-toggle1").show();
	$("#modal_pagos #sidebar-wrapper").show();
});


$(document).ready(function () {
	//INICIO PRINT COMPROBANTE
	$('#modal_pagos #label_print_comprobant').html("No");

	$('#modal_pagos .switch').change(function () {
		if ($('input[name=comprobante_print_switch]').is(':checked')) {
			$('#modal_pagos #label_print_comprobant').html("Si");
			$('.comprobante_print_value').val(1);
			return true;
		}
		else {
			$('#modal_pagos #label_print_comprobant').html("No");
			$('.comprobante_print_value').val(0);
			return false;
		}
	});
	//FIN PRINT COMPROBANTE
});

//INICIO PAGOS MULTIPLES FACTURA
$('#modal_pagos #label_pagos_multiples').html("No");

$('#modal_pagos .switch').change(function () {
	if ($('input[name=pagos_multiples_switch]').is(':checked')) {
		$('#modal_pagos #label_pagos_multiples').html("Si");
		$('#pagos_multiples_switch').val(1);
		$('#formEfectivoBill #pago_efectivo').prop('disabled', false);
		$('.multiple_pago').val(1);
		$('#formTarjetaBill #monto_efectivo_tarjeta').show();
		return true;
	} else {
		$('#modal_pagos #label_pagos_multiples').html("No");
		$('#pagos_multiples_switch').val(0);
		$('.multiple_pago').val(0);
		$('#formEfectivoBill #pago_efectivo').prop('disabled', true)
		$('#formTarjetaBill #monto_efectivo_tarjeta').hide();
		return false;
	}
});
//FIN PAGOS MULTIPLES FACTURA

//FIN MENU FACTURAS

//INICIO MENU COMPRAS
$(document).ready(function () {
	$(".menu-toggle2Purchase").hide();

	//Menu Toggle Script
	$("#menu-toggle1Purchase").click(function (e) {
		e.preventDefault();
		$("#wrapper").toggleClass("toggled");
	});

	$("#menu-toggle2Purchase").click(function (e) {
		e.preventDefault();
		$("#wrapper").toggleClass("toggled");
	});

	// For highlighting activated tabs
	$("#tab1Purchase").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab1Purchase").addClass("active1");
		$("#tab1Purchase").removeClass("bg-light");
	});

	$("#tab2Purchase").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab2Purchase").addClass("active1");
		$("#tab2Purchase").removeClass("bg-light");
	});

	$("#tab3Purchase").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab3Purchase").addClass("active1");
		$("#tab3Purchase").removeClass("bg-light");
	});

	$("#tab4Purchase").click(function () {
		$(".tabs").removeClass("active1");
		$(".tabs").addClass("bg-light");
		$("#tab4Purchase").addClass("active1");
		$("#tab4Purchase").removeClass("bg-light");
	});
})
//FIN MENU FORM PAGOS FACTURAS

$(".menu-toggle1Purchase").on("click", function (e) {
	e.preventDefault();
	$(".menu-toggle1Purchase").hide();
	$(".menu-toggle2Purchase").show();
	$("#modal_pagosPurchase #sidebar-wrapper").hide();
});

$(".menu-toggle2Purchase").on("click", function (e) {
	e.preventDefault();
	$(".menu-toggle2Purchase").hide();
	$(".menu-toggle1Purchase").show();
	$("#modal_pagosPurchase #sidebar-wrapper").show();
});
//FIN MENU FORM COMPRAS


// INICIO PAGOS
// Función para formatear montos monetarios
function formatCurrency(amount) {
    return 'L ' + parseFloat(amount).toLocaleString('es-HN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Función para inicializar el modal de pagos
function initPaymentModal() {
    const modal = document.getElementById('modal_pagos_unificado');
    if (!modal) return;

    // Elementos del DOM
    const steps = modal.querySelectorAll('.step');
    const stepContents = modal.querySelectorAll('.payment-step');
    const prevBtn = modal.querySelector('.payment-btn-prev');
    const nextBtn = modal.querySelector('.payment-btn-next');
    const completeBtn = modal.querySelector('.payment-btn-complete');
    const methodCards = modal.querySelectorAll('.method-card');
    const paymentDetails = modal.querySelectorAll('.payment-details');
    const progressBar = modal.querySelector('.payment-steps');
    const cashAmountInput = modal.querySelector('#cash_amount');
    const changeAmountDisplay = modal.querySelector('.change-amount');
    const pointsAmountInput = modal.querySelector('#points_amount');
    const convertedAmountDisplay = modal.querySelector('.converted-amount');
    const multiplePaymentsSwitch = modal.querySelector('#pagos_multiples_switch');
    const receiverSelect = modal.querySelector('#payment_receiver');
    
    // Variables de estado
    let currentStep = 1;
    let paymentMethodsUsed = ['cash']; // Efectivo por defecto
    let multiplePayments = false;
    
    // Inicializar selects con bootstrap-select
    function initSelects() {
        $(modal).find('select').selectpicker({
            liveSearch: true,
            style: 'btn-light',
            width: '100%',
            size: 5,
            showTick: true,
            noneSelectedText: 'Seleccione una opción'
        });
    }
    
    // Validar que la suma de montos coincida con el total
    function validatePaymentAmounts() {
        const total = parseFloat(modal.querySelector('.amount').textContent.replace(/[^0-9.-]+/g,"")) || 0;
        let sum = 0;
        
        paymentMethodsUsed.forEach(method => {
            const amountInput = modal.querySelector(`#${method}_amount`);
            if (amountInput) {
                sum += parseFloat(amountInput.value) || 0;
            }
        });
        
        return Math.abs(sum - total) < 0.01; // Permitir pequeñas diferencias por redondeo
    }
    
    // Actualizar UI según paso actual
    function updateStepUI() {
        // Actualizar pasos
        steps.forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            step.classList.toggle('active', stepNum === currentStep);
        });
        
        // Actualizar barra de progreso
        const progressWidth = (currentStep - 1) / 2 * 100;
        progressBar.style.setProperty('--progress-width', `${progressWidth}%`);
        
        // Mostrar/ocultar contenido
        stepContents.forEach(content => {
            const contentStep = parseInt(content.dataset.stepContent);
            content.classList.toggle('active', contentStep === currentStep);
        });
        
        // Actualizar botones
        prevBtn.disabled = currentStep === 1;
        nextBtn.style.display = currentStep === 3 ? 'none' : 'flex';
        completeBtn.style.display = currentStep === 3 ? 'flex' : 'none';
        
        // Actualizar confirmación en paso 3
        if (currentStep === 3) {
            updateConfirmationStep();
        }
        
        // Hacer scroll al principio del modal
        modal.querySelector('.payment-body').scrollTop = 0;
    }
    
    // Actualizar paso de confirmación
    function updateConfirmationStep() {
        // Actualizar métodos usados
        const methodsList = paymentMethodsUsed.map(method => {
            const methodName = modal.querySelector(`.method-card[data-method="${method}"] .method-name`).textContent;
            const amountInput = modal.querySelector(`#${method}_amount`);
            let amount = 0;
            
            if (amountInput) {
                amount = parseFloat(amountInput.value) || 0;
            } else if (method === 'cash') {
                amount = parseFloat(modal.querySelector('.amount').textContent.replace(/[^0-9.-]+/g,"")) || 0;
            }
            
            return {
                name: methodName,
                amount: amount
            };
        });
        
        let methodsHtml = '';
        methodsList.forEach(method => {
            methodsHtml += `<div class="detail">
                <span>${method.name}:</span>
                <span>${formatCurrency(method.amount)}</span>
            </div>`;
        });
        
        // Agregar total
        methodsHtml += `<div class="detail" style="border-top: 1px solid #e9ecef; margin-top: 0.5rem; padding-top: 0.5rem;">
            <span><strong>Total:</strong></span>
            <span><strong>${modal.querySelector('.amount').textContent}</strong></span>
        </div>`;
        
        modal.querySelector('.method-used').innerHTML = methodsHtml;
        
        // Actualizar fecha y hora
        const now = new Date();
        modal.querySelector('.transaction-date').textContent = now.toLocaleString();
        
        // Generar ID de transacción
        const transId = `#PAY-${Math.floor(1000 + Math.random() * 9000)}-${now.getFullYear()}`;
        modal.querySelector('.transaction-id').textContent = transId;
        
        // Actualizar recibido por
        if (receiverSelect) {
            const receiverName = receiverSelect.options[receiverSelect.selectedIndex].text;
            modal.querySelector('.receiver-name').textContent = receiverName;
        }
        
        // Actualizar monto en confirmación
        modal.querySelector('.receipt-amount').textContent = modal.querySelector('.amount').textContent;
    }
    
    // Configurar eventos de los métodos de pago
    methodCards.forEach(card => {
        card.addEventListener('click', () => {
            if (!multiplePayments && paymentMethodsUsed.length > 0 && !paymentMethodsUsed.includes(card.dataset.method)) {
                showNotify('warning', 'Pago único', 'Activa "Pagos múltiples" para combinar métodos');
                return;
            }
            
            // Validar combinación puntos-efectivo
            if (card.dataset.method === 'points' && paymentMethodsUsed.length > 0 && !paymentMethodsUsed.includes('cash')) {
                showNotify('error', 'Restricción', 'Los puntos solo pueden usarse con efectivo');
                return;
            }
            
            if (!paymentMethodsUsed.includes(card.dataset.method)) {
                paymentMethodsUsed.push(card.dataset.method);
                card.classList.add('selected');
                
                // Mostrar detalles del método
                paymentDetails.forEach(detail => detail.style.display = 'none');
                paymentMethodsUsed.forEach(method => {
                    modal.querySelector(`.payment-details[data-method="${method}"]`).style.display = 'block';
                });
            } else if (paymentMethodsUsed.length > 1) {
                // Permitir quitar métodos solo si hay más de uno
                const index = paymentMethodsUsed.indexOf(card.dataset.method);
                paymentMethodsUsed.splice(index, 1);
                card.classList.remove('selected');
                modal.querySelector(`.payment-details[data-method="${card.dataset.method}"]`).style.display = 'none';
            }
        });
    });
    
    // Configurar pagos múltiples
    if (multiplePaymentsSwitch) {
        multiplePaymentsSwitch.addEventListener('change', (e) => {
            multiplePayments = e.target.checked;
            if (multiplePayments) {
                showNotify('info', 'Pagos múltiples', 'Ahora puedes combinar varios métodos de pago');
            } else {
                // Limpiar métodos adicionales si se desactiva
                paymentMethodsUsed = paymentMethodsUsed.slice(0, 1);
                methodCards.forEach(card => {
                    if (!paymentMethodsUsed.includes(card.dataset.method)) {
                        card.classList.remove('selected');
                        modal.querySelector(`.payment-details[data-method="${card.dataset.method}"]`).style.display = 'none';
                    }
                });
            }
        });
    }
    
    // Navegación entre pasos
    nextBtn.addEventListener('click', () => {
        if (currentStep >= 3) return;
        
        // Validar selección de método en paso 1
        if (currentStep === 1 && paymentMethodsUsed.length === 0) {
            showNotify('error', 'Selección requerida', 'Debes seleccionar al menos un método de pago');
            return;
        }
        
        // Validar formularios en paso 2
        if (currentStep === 2) {
            let allValid = true;
            paymentMethodsUsed.forEach(method => {
                const form = modal.querySelector(`#form-${method}`);
                if (!form.checkValidity()) {
                    form.reportValidity();
                    allValid = false;
                }
            });
            
            if (!allValid) return;
            
            // Validar que la suma de montos coincida con el total
            if (!validatePaymentAmounts()) {
                showNotify('error', 'Monto incorrecto', 'La suma de los pagos no coincide con el total');
                return;
            }
        }
        
        currentStep++;
        updateStepUI();
    });
    
    prevBtn.addEventListener('click', () => {
        if (currentStep <= 1) return;
        
        notyf.open({
            type: 'warning',
            message: '<strong>¿Regresar?</strong><br>Los cambios no guardados se perderán',
            duration: 5000,
            dismissible: true,
            buttons: [
                {
                    text: 'Sí, regresar',
                    onClick: function() { 
                        currentStep--;
                        updateStepUI();
                        notyf.dismissAll();
                    }
                },
                {
                    text: 'Cancelar',
                    onClick: function() { notyf.dismissAll(); }
                }
            ]
        });
    });
    
    // Cálculo de cambio para efectivo
    if (cashAmountInput && changeAmountDisplay) {
        cashAmountInput.addEventListener('input', () => {
            const amount = parseFloat(cashAmountInput.value) || 0;
            const total = parseFloat(modal.querySelector('.amount').textContent.replace(/[^0-9.-]+/g,"")) || 0;
            const change = amount - total;
            changeAmountDisplay.textContent = formatCurrency(change);
        });
    }
    
    // Cálculo de conversión de puntos
    if (pointsAmountInput && convertedAmountDisplay) {
        pointsAmountInput.addEventListener('input', () => {
            const points = parseFloat(pointsAmountInput.value) || 0;
            const conversionRate = 0.1; // 1 punto = L 0.10
            const converted = points * conversionRate;
            convertedAmountDisplay.textContent = formatCurrency(converted);
        });
    }
    
    // Finalizar pago
    if (completeBtn) {
        completeBtn.addEventListener('click', () => {
            // Aquí iría la lógica para procesar el pago
            showNotify('success', 'Pago completado', 'La transacción se ha procesado exitosamente');
            $(modal).modal('hide');
        });
    }
    
    // Inicializar componentes
    initSelects();
    updateStepUI();
    
    // CSS para barra de progreso
    const style = document.createElement('style');
    style.textContent = `
        .payment-steps::after {
            width: var(--progress-width, 0%);
        }
        .payment-switch input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .payment-form-control {
            padding-right: 2.5rem !important;
            background-color: transparent !important;
        }
        .currency-symbol, .points-symbol {
            pointer-events: none;
        }
        .step {
            cursor: default !important;
        }
    `;
    document.head.appendChild(style);
}

// Función para abrir el modal con datos específicos
function openPaymentModal(type, amount, customer, id) {
    const modal = $('#modal_pagos_unificado');
    
    // Configurar según tipo (factura/compra)
    if (type === 'factura') {
        modal.find('#factura-options').show();
        modal.find('.method-card.premium').show();
        modal.find('input[name="tipo_operacion"]').val('factura');
        modal.find('#factura_id').val(id);
    } else {
        modal.find('#factura-options').hide();
        modal.find('.method-card.premium').hide();
        modal.find('input[name="tipo_operacion"]').val('compra');
        modal.find('#compra_id').val(id);
    }
    
    // Establecer datos
    modal.find('#customer-name-payment').text(customer);
    modal.find('#customer_payment_id').val(id);
    modal.find('.amount').text(formatCurrency(amount));
    
    // Resetear modal
    modal.find('.step').removeClass('active');
    modal.find('.step[data-step="1"]').addClass('active');
    modal.find('.payment-step').removeClass('active');
    modal.find('.payment-step[data-step-content="1"]').addClass('active');
    modal.find('.payment-btn-prev').prop('disabled', true);
    modal.find('.payment-btn-next').show();
    modal.find('.payment-btn-complete').hide();
    
    // Seleccionar efectivo por defecto
    modal.find('.method-card').removeClass('selected');
    modal.find('.method-card[data-method="cash"]').addClass('selected');
    modal.find('.payment-details').hide();
    modal.find('.payment-details[data-method="cash"]').show();
    
    // Resetear pagos múltiples
    modal.find('#pagos_multiples_switch').prop('checked', false).trigger('change');
    
    // Mostrar modal
    modal.modal('show');
    
    // Inicializar si no está inicializado
    if (!modal.data('initialized')) {
        initPaymentModal();
        modal.data('initialized', true);
    }
}

// Uso: openPaymentModal('factura', 1250.00, 'Cliente Ejemplo', 12345);
//FIN PAGOS

