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
                swal({
                    title: "",
                    text: "Por favor espere...",
                    icon: '<?php echo SERVERURL; ?>vistas/plantilla/img/gif-load.gif',
                    buttons: false,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });
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
                showErrorModal("Error Inesperado", "Ocurrió un error inesperado");
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
                swal({ /* Configuración de carga */ });
            },
            success: function(resp) {
                if (resp == 1) {
                    showSuccessModal("¡Éxito!", "Contraseña reseteada exitosamente");
                } else {
                    showErrorModal("Error", getErrorMessage(resp));
                }
            },
            error: function() {
                showErrorModal("Error", "Problema al procesar la solicitud");
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
            "ErrorC": "Cuenta no encontrada"
        };
        showErrorModal("Error", errors[errorType] || "Error desconocido");
    }

    function showErrorModal(title, message) {
        swal({ /* Configuración del modal de error */ });
    }

    function showSuccessModal(title, message) {
        swal({ /* Configuración del modal de éxito */ });
    }
});
</script>