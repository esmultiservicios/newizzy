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
        }
    ]
});

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
    const validTypes = ['success', 'error', 'warning', 'info'];

    if (validTypes.includes(type)) {
        notyf.open({
            type: type,
            message: `<strong>${title}</strong><br>${message}`,
            settings: {
                ripple: true,
                allowHtml: true, // Permite HTML
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