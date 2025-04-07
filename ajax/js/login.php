<script>
// Funciones globales
function sf(ID) {
    document.getElementById(ID).focus();
}

function redireccionar() {
    window.location = "../vistas/index.php";
}

$(document).ready(function() {
    // Generar PIN aleatorio
    $("#generate_pin_link").click(function(e) {
        e.preventDefault();
        $("#pin_value").text(Math.floor(Math.random() * 10000));
    });

    // Validación de cliente/PIN
    $('#inputCliente').on('input', function() {
        if ($(this).val().length === 8) $('#inputPin').focus();
    });

    // Validación de email/password
    var timeout;
    $("#inputEmail, #inputPassword").on("input blur", function() {
        clearTimeout(timeout);
        var email = $("#inputEmail").val();
        var password = $("#inputPassword").val();

        timeout = setTimeout(function() {
            if (email && password) {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo SERVERURL; ?>core/getValidUserSesion.php',
                    data: { email: email, pass: password },
                    success: function(resp) {
                        $("#groupDB").toggle(resp === "1");
                        if (resp === "1") $("#inputCliente").focus();
                        else $("#inputCliente, #inputPin").val("");
                    },
                    error: function() {
                        $("#groupDB").hide();
                        $("#inputCliente, #inputPin").val("");
                        $(".RespuestaAjax").html("Error de autenticación");
                    }
                });
            } else {
                $("#groupDB").hide();
                if (!email) $("#inputCliente").val("");
                if (!password) $("#inputPin").val("");
            }
        }, 300);
    });

    // Login form
    $("#loginform").submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '<?php echo SERVERURL; ?>ajax/iniciarSesionAjax.php',
            data: $(this).serialize(),
            beforeSend: function() {
                showLoading("Por favor espere...");
            },
            success: function(resp) {
                var datos = eval(resp);
                if (datos[0]) {
                    setTimeout(() => window.location = datos[0], 1200);
                } else {
                    handleLoginError(datos[1]);                    
                }
            },
            error: function() {
                showNotify('error', 'Error Inesperado', "Ocurrió un error inesperado");
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

    // Funciones auxiliares
    function handleLoginError(errorType) {
        const errors = {
            "ErrorS": "Usuario o contraseña incorrectos",
            "ErrorP": "Problemas con el pago",
            "ErrorVacio": "Campos vacíos",
            "ErrorPinInvalido": "PIN inválido",
            "ErrorC": "Cuenta no encontrada, por favor realice el registro en el sistema"
        };

        showNotify('error', 'Error', errors[errorType] || "Error desconocido");
    }
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
            user_empresa: empresa,
            user_name: nombre,
            user_telefono: telefono,
            email: email,
            user_pass: pass1
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
            hideLoading(); // Asegurar que el loading se cierre
        }
    });
});
</script>