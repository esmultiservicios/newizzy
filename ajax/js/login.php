<script>
// Funciones globales
function sf(ID) {
    document.getElementById(ID).focus();
}

function redireccionar() {
    window.location = "../vistas/index.php";
}

$(document).ready(function() {
    $("#groupDB").hide();
    
    // Generar PIN aleatorio
    $("#generate_pin_link").click(function(e) {
        e.preventDefault();
        $("#pin_value").text(Math.floor(Math.random() * 10000));
    });

    // Validación de cliente/PIN
    $('#inputCliente').on('input', function() {
        if ($(this).val().length === 8) $('#inputPin').focus();
    });

    // Validación inmediata de email/password
    $("#inputEmail, #inputPassword").on("input blur", function() {
        var email = $("#inputEmail").val();
        var password = $("#inputPassword").val();

        if (email && password) {
            $.ajax({
                type: 'POST',
                url: '<?php echo SERVERURL; ?>core/getValidUserSesion.php',
                data: { 
                    email: email, 
                    pass: password 
                },
                dataType: 'json',
                success: function(resp) {
                    $(".RespuestaAjax").hide();

                    if (resp.is_test) {
                        $("#groupDB").hide();
                    } else if (resp.success && resp.show_db) {
                        $("#groupDB").show();
                        $("#inputCliente").focus();
                    } else {
                        $("#groupDB").hide();
                        $("#inputCliente, #inputPin").val("");
                        if (resp.message) {
                            $(".RespuestaAjax").html(resp.message).show();
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $("#groupDB").hide();
                    $("#inputCliente, #inputPin").val("");
                    $(".RespuestaAjax").html("Error en el servidor").show();
                }
            });
        } else {
            $("#groupDB").hide();
            if (!email) $("#inputCliente").val("");
            if (!password) $("#inputPin").val("");
        }
    });

    $("#loginform").submit(function(e) {
        e.preventDefault();

        var url = '<?php echo SERVERURL; ?>ajax/iniciarSesionAjax.php';

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#loginform').serialize(),
            beforeSend: function() {
                showLoading("Por favor espere...");
                $("#loginform #acceso").show();
            },
            success: function(resp) {
                var datos = eval(resp);
                if (datos[0] !== "") {
                    setTimeout(window.location = datos[0], 1200);
                } else if (datos[1] === "ErrorS") {
                    swal({
                    content: {
                        element: "div",
                        attributes: {
                            innerHTML: `
                                <h2 style="color: #d9534f; font-size: 22px; margin-bottom: 15px;">
                                    ⚠️ Error de Autenticación
                                </h2>
                                <p style="font-size: 16px; color: #555;">
                                    <strong>Usuario o contraseña incorrectos.</strong> Por favor, verifique los datos ingresados.
                                </p>
                                <p style="font-size: 16px; color: #555;">
                                    🔑 Asegúrese de que el nombre de usuario y la contraseña sean correctos.
                                </p>
                            `
                        }
                    },
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
                } else if (datos[1] === "ErrorP") {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #f0ad4e; font-size: 22px; margin-bottom: 15px;">
                                        ⚠️ ¡Problemas con el Pago!
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        😕 ¡Oops! Parece que hay un problema con su acceso al sistema debido a un inconveniente con el pago.
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        📅 <strong>Fecha máxima de pago:</strong> El pago debe realizarse antes del <strong>día 15 de cada mes</strong>. A partir del <strong>día 16</strong>, su acceso podría verse restringido si la factura sigue pendiente.
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        No se preocupe, solo necesita ponerse en contacto con nuestro equipo de recaudación de pagos para arreglarlo.
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        💬 Puede escribirnos al 📞 <strong>+504 8913-6844</strong>, ¡y con gusto le ayudaremos! 😊
                                    </p>
                                `
                            }
                        },
                        icon: "warning",
                        dangerMode: true,
                        closeOnEsc: false,
                        closeOnClickOutside: false
                    });
                } else if (datos[1] === "ErrorVacio") {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #d9534f; font-size: 22px; margin-bottom: 15px;">
                                        ⚠️ Error
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        <strong>Lo sentimos</strong>, uno de los dos campos no puede ir en blanco. El sistema requiere tanto el cliente como el PIN para continuar.
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        Si lo desea, puede dejar ambos campos en blanco, y el sistema los ignorará.
                                        <span style="color: #5bc0de;">Por favor, complete los campos para continuar.</span>
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        😓 Lamentamos el inconveniente, y agradecemos su comprensión. 🙏
                                    </p>
                                `
                            }
                        },
                        icon: "error",
                        dangerMode: true,
                        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                    });
                } else if (datos[1] === "ErrorPinInvalido") {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #d9534f; font-size: 22px; margin-bottom: 15px;">
                                        ⚠️ Error
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        <strong>Lo sentimos</strong>, el código del cliente o el PIN son inválidos, o el mismo ha vencido. 
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        Por favor, solicite un nuevo PIN al cliente para continuar con el proceso.
                                        <span style="color: #5bc0de;">Agradecemos su comprensión.</span>
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        😔 Si necesita asistencia adicional, no dude en ponerse en contacto con nuestro soporte. 🙏
                                    </p>
                                `
                            }
                        },
                        icon: "error",
                        dangerMode: true,
                        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                    });
                } else if (datos[1] === "ErrorC") {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #5bc0de; font-size: 22px; margin-bottom: 15px;">
                                        📧 No se encontró una cuenta asociada a este correo electrónico
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        <strong>¿Desea registrarse o explorar nuestros productos?</strong>
                                    </p>
                                `
                            }
                        },
                        icon: "info",
                        buttons: {
                            cancel: "Cerrar",
                            register: {
                                text: "Sí, registrarme!",
                                value: "register",
                            },
                            explore: {
                                text: "Explorar productos",
                                value: "explore",
                            }
                        },
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    })
                    .then((value) => {
                        switch (value) {
                            case "register":
                                // El usuario eligió registrarse, muestra el formulario de registro.
                                setTimeout(function() {
                                    $("#form_registro").show();
                                    $("#loginform").hide();
                                    swal.close();
                                }, 1000);
                                break;

                            case "explore":
                                // El usuario eligió explorar productos, muestra el mensaje de mantenimiento.
                                swal({
                                    content: {
                                        element: "div",
                                        attributes: {
                                            innerHTML: `
                                                <h2 style="color: #f0ad4e; font-size: 22px; margin-bottom: 15px;">
                                                    🔧 Mantenimiento en Curso
                                                </h2>
                                                <p style="font-size: 16px; color: #555;">
                                                    Estamos trabajando para mejorar nuestros servicios. <strong>Disculpa las molestias.</strong>
                                                </p>
                                                <p style="font-size: 16px; color: #555;">
                                                    ⚙️ Agradecemos tu paciencia. ¡Pronto estaremos de vuelta!
                                                </p>
                                            `
                                        }
                                    },
                                    icon: "error",
                                    buttons: {
                                        confirm: {
                                            text: "Aceptar",
                                            closeModal: true,
                                        }
                                    },
                                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                                });
                                break;

                            default:
                                // El usuario eligió cerrar el cuadro de diálogo.
                                swal.close();
                        }
                    });
                } else {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #d9534f; font-size: 22px; margin-bottom: 15px;">
                                        ❌ Error
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        <strong>No se enviaron los datos</strong>, por favor, corrija los errores y vuelva a intentar.
                                    </p>
                                    <p style="font-size: 16px; color: #555;">
                                        ⚠️ Asegúrese de verificar los campos obligatorios y los datos ingresados.
                                    </p>
                                `
                            }
                        },
                        icon: "error",
                        dangerMode: true,
                        closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                        closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                    });
                }
            },
            error: function() {
                swal({
                    content: {
                        element: "div",
                        attributes: {
                            innerHTML: `
                                <h2 style="color: #d9534f; font-size: 22px; margin-bottom: 15px;">
                                    ❗ Error Inesperado
                                </h2>
                                <p style="font-size: 16px; color: #555;">
                                    <strong>Ocurrió un error inesperado</strong>, o tal vez no tenga conexión con el sistema.
                                </p>
                                <p style="font-size: 16px; color: #555;">
                                    🚧 Por favor, intente nuevamente más tarde.
                                </p>
                            `
                        }
                    },
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false, // Desactiva el cierre con la tecla Esc
                    closeOnClickOutside: false // Desactiva el cierre al hacer clic fuera
                });
                $("#loginform #acceso").hide();
                $("#loginform #acceso").html("");
                $("#loginform #usu").focus();
            }
        });

        return false;
    });

    // Reset password
    $("#forgot_form").submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>ajax/resetearContrasenaLoginAjax.php',
            data: $(this).serialize(),
            beforeSend: function() {
                showLoading("Registrando usuario...");
            },
            success: function(resp) {
                if (resp == 1) {
                    showNotify('success', 'Contraseña Reseteada', "Contraseña reseteada exitosamente");
                } else {
                    showNotify('error', 'Error', resp);
                }
            },
            error: function() {
                showNotify('error', 'Error', "Problema al procesar la solicitud");
            }
        });
        return false;
    });

});

// Validación de contraseñas en tiempo real
$('#user-pass, #user-repeatpass').on('blur', function() {
    const pass1 = $('#user-pass').val();
    const pass2 = $('#user-repeatpass').val();
    
    if (pass1 && pass2) {
        if (pass1 !== pass2) {
            $('#user-pass, #user-repeatpass').addClass('is-invalid');
            showNotify('error', 'Error', 'Las contraseñas no coinciden');
        } else {
            $('#user-pass, #user-repeatpass').removeClass('is-invalid');
            if(pass1.length < 8) {
                showNotify('warning', 'Advertencia', 'La contraseña debe tener al menos 8 caracteres');
            }
        }
    }
});
    
// Validación de email en tiempo real
$('#mail').on('blur', function() {
    const email = $(this).val();
    if(email && !isValidEmail(email)) {
        $(this).addClass('is-invalid');
        showNotify('error', 'Error', 'Ingrese un correo electrónico válido');
    } else {
        $(this).removeClass('is-invalid');
    }
});

// Función para validar email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Función para validar teléfono
function isValidPhone(phone) {
    const re = /^[0-9]{8,12}$/;
    return re.test(phone);
}

$('.form-control').on('input change', function () {
    if ($(this).val().trim() !== '') {
        $(this).removeClass('is-invalid');
    }
});

// Empresa
$('#user_empresa').on('input', function () {
    if ($(this).val().trim() !== '') {
        $(this).removeClass('is-invalid');
    }
});

// Nombre
$('#user_name').on('input', function () {
    if ($(this).val().trim() !== '') {
        $(this).removeClass('is-invalid');
    }
});

// Teléfono
$('#user_telefono').on('input', function () {
    if (isValidPhone($(this).val())) {
        $(this).removeClass('is-invalid');
    }
});

// Correo
$('#mail').on('input', function () {
    if (isValidEmail($(this).val())) {
        $(this).removeClass('is-invalid');
    }
});

// Contraseña
$('#user-pass, #user-repeatpass').on('input', function () {
    const pass1 = $('#user-pass').val();
    const pass2 = $('#user-repeatpass').val();

    if (pass1.length >= 8) {
        $('#user-pass').removeClass('is-invalid');
    }

    if (pass1 === pass2 && pass2.length >= 8) {
        $('#user-pass, #user-repeatpass').removeClass('is-invalid');
    }
});


// Proceso de registro
$("#registrarse").click(function(e) {
    e.preventDefault();
    
    // Validar campos
    const empresa = $('#user_empresa').val().trim();
    const nombre = $('#user_name').val().trim();
    const telefono = $('#user_telefono').val().trim();
    const email = $('#mail').val().trim();
    const pass1 = $('#user-pass').val();
    const pass2 = $('#user-repeatpass').val();
    const clientes_id = 0;
    const sistema_id = 1;
    const planes_id = 1;
    const eslogan = '';
    const otra_informacion = '';
    const celular = '';
    const ubicacion = '';
    const validar = 0;
    const rtn = '';
    
    // Resetear clases de error
    $('.form-control').removeClass('is-invalid');
    
    // Validaciones básicas
    if (!empresa) {
        $('#user_empresa').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'El nombre de la empresa es obligatorio');
        return;
    }

    if (!nombre) {
        $('#user_name').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'El nombre es obligatorio');
        return;
    }
    
    if (!telefono || !isValidPhone(telefono)) {
        $('#user_telefono').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'Ingrese un teléfono válido (8-12 dígitos)');
        return;
    }
    
    if (!email || !isValidEmail(email)) {
        $('#mail').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'Ingrese un correo electrónico válido');
        return;
    }
    
    if (!pass1 || pass1.length < 8) {
        $('#user-pass').addClass('is-invalid').focus();
        showNotify('error', 'Error', 'La contraseña debe tener al menos 8 caracteres');
        return;
    }
    
    if (pass1 !== pass2) {
        $('#user-pass, #user-repeatpass').addClass('is-invalid');
        $('#user-repeatpass').focus();
        showNotify('error', 'Error', 'Las contraseñas no coinciden');
        return;
    }
    
    // Enviar datos al servidor
    $.ajax({
        type: 'POST',
        url: '<?php echo SERVERURL; ?>ajax/registrarClienteAutonomoAjax.php',
        dataType: 'json',
        data: {
            clientes_id: clientes_id,
            user_empresa: empresa,
            user_name: nombre,
            user_telefono: telefono,
            email: email,
            user_pass: pass1,
            sistema_id: sistema_id,
            planes_id: planes_id,
            eslogan: eslogan,
            otra_informacion: otra_informacion,
            celular: celular,
            ubicacion: ubicacion,
            validar: validar,
            rtn: rtn,          
        },
        beforeSend: function() {
            showLoading("Registrando usuario...");
        },
        success: function(resp) {           
            if (resp.estado) {
                showNotify(resp.type, resp.title, resp.mensaje);
                
                // Limpiar formulario
                $('#form_registro')[0].reset();
                
                // Redirigir al login después de 2 segundos
                setTimeout(function() {
                    $('#cancel_signup').click(); // Activa el botón de volver al login
                    $('#inputEmail').val(email).focus(); // Rellena el email en el login
                }, 2000);
            } else {
                showNotify(resp.type, resp.title, resp.mensaje);
            }
        },
        error: function(xhr, status, error) {          
            try {
                const errResponse = JSON.parse(xhr.responseText);
                showNotify('error', 'Error', errResponse.mensaje || 'Error en el servidor');
            } catch (e) {
                showNotify('error', 'Error', 'Error de conexión: ' + error);
            }
        },
        complete: function() {
            
        }
    });
});
</script>