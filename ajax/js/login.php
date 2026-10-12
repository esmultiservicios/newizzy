<script>
// Funciones globales
function sf(ID) {
    document.getElementById(ID).focus();
}

function redireccionar() {
    window.location = "../vistas/index.php";
}

// Si el navegador restaura esta pantalla desde el Back/Forward Cache (bfcache),
// se solicita una carga real al servidor para no mostrar CSS/HTML de una versión anterior.
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        window.location.reload();
    }
});

$(document).ready(function() {
    if (window.izzySyncClientAccess) { window.izzySyncClientAccess(false); }

    // Recordar únicamente el correo en LOCAL y PRODUCCIÓN.
    // En DEMO la opción no se renderiza y nunca se persiste información.
    var rememberEmailKey = 'izzy_login_email';
    var isDemoLogin = $('#loginform').attr('data-demo') === '1';

    if (!isDemoLogin && $('#rememberEmail').length) {
        try {
            var rememberedEmail = window.localStorage.getItem(rememberEmailKey) || '';
            if (rememberedEmail) {
                $('#inputEmail').val(rememberedEmail);
                $('#rememberEmail').prop('checked', true);
            }
        } catch (storageError) {
            // El login continúa aunque el navegador bloquee localStorage.
        }
    }

    function syncRememberedEmail() {
        if (isDemoLogin || !$('#rememberEmail').length) return;
        try {
            if ($('#rememberEmail').is(':checked')) {
                window.localStorage.setItem(rememberEmailKey, $.trim($('#inputEmail').val()));
                selectedRememberEmail = $.trim($('#inputEmail').val()).toLowerCase();
            } else {
                window.localStorage.removeItem(rememberEmailKey);
            }
        } catch (storageError) {}
    }

    // El check pertenece al correo seleccionado, no a cualquier correo escrito después.
    // La referencia se actualiza cuando el usuario marca el check manualmente.
    var selectedRememberEmail = $.trim($('#inputEmail').val()).toLowerCase();

    $('#rememberEmail').on('change.izzyRemember', function() {
        if ($(this).is(':checked')) {
            selectedRememberEmail = $.trim($('#inputEmail').val()).toLowerCase();
        } else {
            selectedRememberEmail = '';
            try { window.localStorage.removeItem(rememberEmailKey); } catch (storageError) {}
        }
    });

    $('#inputEmail').on('input.izzyRemember change.izzyRemember', function() {
        if (isDemoLogin || !$('#rememberEmail').is(':checked')) return;

        var currentEmail = $.trim($(this).val()).toLowerCase();
        if (currentEmail !== selectedRememberEmail) {
            $('#rememberEmail').prop('checked', false).trigger('change.izzyRemember');
        }
    });

    // UX premium: foco limpio y accesos externos sin alterar la lógica existente.
    if ($("#inputEmail").is(":visible")) {
        $("#inputEmail").trigger("focus");
    }

    $("#btn-signup").on("click.izzyPremium", function() {
        window.setTimeout(function() {
            $("#user_empresa").trigger("focus");
        }, 100);
    });

    $("#forgot_pswd").on("click.izzyPremium", function() {
        window.setTimeout(function() {
            $("#usu_forgot").trigger("focus");
        }, 100);
    });

    $("#cancel_signup, #cancel_reset").on("click.izzyPremium", function() {
        window.setTimeout(function() {
            $("#inputEmail").trigger("focus");
        }, 100);
    });

    // Generar PIN aleatorio
    $("#generate_pin_link").click(function(e) {
        e.preventDefault();
        $("#pin_value").text(Math.floor(Math.random() * 10000));
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
                        if (window.izzySyncClientAccess) { window.izzySyncClientAccess(false); }
                    } else if (resp.success && resp.show_db) {
                        if (window.izzySyncClientAccess) { window.izzySyncClientAccess(true); }
                    } else {
                        if (window.izzySyncClientAccess) { window.izzySyncClientAccess(false); }
                        $("#inputCliente, #inputPin").val("");
                    }
                },
                error: function(xhr, status, error) {
                    if (window.izzySyncClientAccess) { window.izzySyncClientAccess(false); }
                    $("#inputCliente, #inputPin").val("");
                    $(".RespuestaAjax").hide().empty();
                    if (typeof showNotify === "function") {
                        showNotify('error', 'Conexión', 'No fue posible validar el acceso en este momento.');
                    }
                }
            });
        } else {
            if (window.izzySyncClientAccess) { window.izzySyncClientAccess(false); }
            if (!email) $("#inputCliente").val("");
            if (!password) $("#inputPin").val("");
        }
    });

    $("#loginform").submit(function(e) {
        e.preventDefault();

        if (!izzyValidateForm($('#loginform'), true)) return false;

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
                    syncRememberedEmail();
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
                                    izzyResetAuthValidation();
                                    $("#form_registro").show();
                                    $("#loginform").hide();
                                    izzyResetAuthValidation();
                                    swal.close();
                                }, 1000);
                                break;

                            case "explore":
                                // El sitio público ya está disponible.
                                window.open('https://izzycloud.app/', '_blank', 'noopener');
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
                $("#inputEmail").focus();
            }
        });

        return false;
    });

    // Reset password
    $("#forgot_form").submit(function(e) {
        e.preventDefault();
        if (!izzyValidateForm($('#forgot_form'), true)) return false;
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

// Validación consistente entre inicio de sesión, recuperación y registro.
// La validación visual no envía solicitudes ni muestra notificaciones.
function izzyFieldResult($input) {
    const value = String($input.val() || '').trim();
    const id = $input.attr('id');
    if (!value) return {valid: false, message: 'Este campo es obligatorio.'};
    if (id === 'inputEmail' || id === 'usu_forgot' || id === 'mail') {
        return {valid: isValidEmail(value), message: 'Ingresa un correo electrónico válido.'};
    }
    if (id === 'user_telefono') {
        return {valid: isValidPhone(value), message: 'Ingresa un teléfono de 8 a 12 dígitos.'};
    }
    if (id === 'user-pass' && value.length < 8) {
        return {valid: false, message: 'Usa al menos 8 caracteres.'};
    }
    if (id === 'user-repeatpass') {
        if (value.length < 8) return {valid: false, message: 'Usa al menos 8 caracteres.'};
        if (value !== String($('#user-pass').val() || '')) {
            return {valid: false, message: 'Las contraseñas no coinciden.'};
        }
    }
    return {valid: true, message: ''};
}

function izzyMarkField($input, valid, message) {
    // Estado visual únicamente: nunca insertar mensajes que alteren el alto del formulario.
    const $control = $input.closest('.auth-control');
    if (!$control.length) return;
    $control.removeClass('izzy-field-invalid izzy-field-valid')
        .addClass(valid ? 'izzy-field-valid' : 'izzy-field-invalid');
    $input.attr('aria-invalid', valid ? 'false' : 'true');
    // Limpiar mensajes que pudieran existir de versiones anteriores.
    $control.children('.izzy-field-message').remove();
}

function izzyClearFormState($form) {
    $form.find('.auth-control').removeClass('izzy-field-invalid izzy-field-valid');
    $form.find('input').removeAttr('aria-invalid');
    $form.find('.izzy-field-message').remove();
}

function isDemoAuthForm() {
    return $('#loginform').attr('data-demo') === '1';
}

// Cada transición comienza con formularios nuevos, sin datos ni validaciones anteriores.
function izzyResetAuthValidation() {
    const $remember = $('#rememberEmail');
    const shouldRemember = !isDemoAuthForm() && $remember.length && $remember.is(':checked');
    let savedEmail = '';
    if (shouldRemember) {
        try { savedEmail = window.localStorage.getItem('izzy_login_email') || ''; } catch (ignored) {}
    }

    ['#loginform', '#form_registro', '#forgot_form'].forEach(function(selector) {
        const $form = $(selector);
        const form = $form.get(0);
        if (!form) return;
        form.reset();
        $form.find('input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"])').val('');
        izzyClearFormState($form);
        $form.find('input').removeClass('is-invalid is-valid');
        $form.find('.RespuestaAjax').empty().hide();
    });
    if ($remember.length) $remember.prop('checked', !!shouldRemember);
    if (shouldRemember) $('#inputEmail').val(savedEmail);
    $('#inputCliente, #inputPin').val('');
    $('#clientPinModal input').removeClass('is-invalid is-valid').removeAttr('aria-invalid');
    $('#clientPinModal .auth-control').removeClass('izzy-field-invalid izzy-field-valid');
    $('#user-pass, #user-repeatpass, #inputPassword, #inputPin').attr('type', 'password');
    $('#show_password #icon, #show_client_pin #icon_client_pin').removeClass('fa-eye').addClass('fa-eye-slash');
}

// Limpiar antes del cambio de vista, preservando únicamente el correo recordado.
// El resto de las transiciones están gestionadas por los controladores existentes.
$(document).on('click.izzyFreshAuthView',
    '#btn-signup, #forgot_pswd, #cancel_signup, #cancel_reset',
    function() {
        izzyResetAuthValidation();
    }
);

function izzyValidateForm($form, focusInvalid) {
    let first = null;
    $form.find('input[required]').each(function() {
        const $input = $(this);
        const result = izzyFieldResult($input);
        izzyMarkField($input, result.valid, result.message);
        if (!result.valid && !first) first = $input;
    });
    if (first && focusInvalid) first.trigger('focus');
    return !first;
}

$(document).on('input.izzyField change.izzyField',
    '#loginform input[required], #forgot_form input[required], #form_registro input[required]',
    function() {
        const $input = $(this);
        if (!$input.closest('form').is(':visible')) return;
        const $control = $input.closest('.auth-control');
        const value = String($input.val() || '').trim();
        if (!value && !$control.hasClass('izzy-field-invalid')) return;
        const result = izzyFieldResult($input);
        izzyMarkField($input, result.valid, result.message);
        if (this.id === 'user-pass' && $('#user-repeatpass').val()) {
            const $confirm = $('#user-repeatpass');
            const confirmation = izzyFieldResult($confirm);
            izzyMarkField($confirm, confirmation.valid, confirmation.message);
        }
    }
);
// No validar por blur: al cambiar de vista el navegador dispara blur
// sobre el campo anterior y puede restaurar un estado rojo ya limpiado.
// Los errores aparecen al enviar y se actualizan mientras se escribe.

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
$(document).on("click.izzyRegistro", "#registrarse", function(e) {
    e.preventDefault();
    procesarRegistroIzzy();
});

$(document).on("submit.izzyRegistro", "#form_registro", function(e) {
    e.preventDefault();
    procesarRegistroIzzy();
});

function procesarRegistroIzzy() {
    
    // Validar campos
    const empresa = $('#user_empresa').val().trim();
    const nombre = $('#user_name').val().trim();
    const telefono = $('#user_telefono').val().trim();
    const email = $('#mail').val().trim();
    const pass1 = $('#user-pass').val();
    const pass2 = $('#user-repeatpass').val();
    const clientes_id = 0;
    const sistema_id = 1;
    const planes_id = 6; // Plan Gratis para nuevos registros
    const eslogan = '';
    const otra_informacion = '';
    const celular = '';
    const ubicacion = '';
    const validar = 0;
    const rtn = '';
    
    // Validar todo el formulario antes de realizar cualquier solicitud AJAX.
    const campos = [
        { selector: '#user_empresa', valido: empresa.length > 0, mensaje: 'El nombre de la empresa es obligatorio' },
        { selector: '#user_name', valido: nombre.length > 0, mensaje: 'El nombre es obligatorio' },
        { selector: '#user_telefono', valido: isValidPhone(telefono), mensaje: 'Ingrese un teléfono válido (8-12 dígitos)' },
        { selector: '#mail', valido: isValidEmail(email), mensaje: 'Ingrese un correo electrónico válido' },
        { selector: '#user-pass', valido: pass1.length >= 8, mensaje: 'La contraseña debe tener al menos 8 caracteres' },
        { selector: '#user-repeatpass', valido: pass2.length >= 8 && pass1 === pass2, mensaje: 'Las contraseñas no coinciden o la confirmación está vacía' }
    ];

    let primerError = null;
    campos.forEach(function(campo) {
        const $input = $(campo.selector);
        const value = String($input.val() || '').trim();
        const message = value ? campo.mensaje : 'Este campo es obligatorio.';
        izzyMarkField($input, campo.valido, campo.valido ? '' : message);
        if (!campo.valido && !primerError) primerError = $input;
    });
    if (primerError) {
        primerError.trigger('focus');
        return false;
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
                izzyClearFormState($('#form_registro'));
                
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
}

// ==========================================================
// IZZY AUTH PREMIUM · comportamiento visual y accesible
// ==========================================================
(function () {
    function showLoginView() {
        izzyResetAuthValidation();
        $('#forgot_form, #form_registro').hide();
        $('#loginform').show();
        window.setTimeout(function () {
            $('#inputEmail').trigger('focus');
        }, 80);
    }

    function syncClientAccess(show) {
        const $form = $('#loginform');
        const $group = $('#groupDB');
        const $button = $('#clientAccessTrigger');
        const $state = $('#clientAccessState');
        const isDemo = $form.attr('data-demo') === '1';

        if (isDemo) {
            $group.hide();
            $button.prop('disabled', true).attr('aria-disabled', 'true').removeClass('is-enabled');
            $('#inputCliente, #inputPin').val('');
            closeClientPinModal();
            return;
        }

        $group.css('display', 'flex');
        $button.prop('disabled', !show).attr('aria-disabled', show ? 'false' : 'true');
        $button.toggleClass('is-enabled', !!show);
        $state.text(show ? 'Acceso administrativo disponible' : 'Validá tus credenciales');

        if (!show) {
            $('#inputCliente, #inputPin').val('');
            closeClientPinModal();
        }
    }

    window.izzySyncClientAccess = syncClientAccess;

    $('#cancel_signup, #cancel_reset')
        .off('click.izzyAuthReturn')
        .on('click.izzyAuthReturn', function (e) {
            e.preventDefault();
            showLoginView();
        });

    $('#btn-signup')
        .off('click.izzyAuthFocus')
        .on('click.izzyAuthFocus', function () {
            window.setTimeout(function () {
                $('#user_empresa').trigger('focus');
            }, 100);
        });

    $('#forgot_pswd')
        .off('click.izzyAuthFocus')
        .on('click.izzyAuthFocus', function () {
            window.setTimeout(function () {
                $('#usu_forgot').trigger('focus');
            }, 100);
        });

    function bindPasswordToggle(buttonSelector, inputSelector, iconSelector) {
        $(document)
            .off('click.izzyPassword', buttonSelector)
            .on('click.izzyPassword', buttonSelector, function (e) {
                e.preventDefault();

                const $input = $(inputSelector);
                const $icon = $(iconSelector);
                const reveal = $input.attr('type') === 'password';

                $input.attr('type', reveal ? 'text' : 'password');
                $icon.toggleClass('fa-eye-slash', !reveal);
                $icon.toggleClass('fa-eye', reveal);

                $(this).attr(
                    'aria-label',
                    reveal ? 'Ocultar contraseña' : 'Mostrar contraseña'
                );

                $input.trigger('focus');
            });
    }

    bindPasswordToggle('#show_password', '#inputPassword', '#icon');
    bindPasswordToggle('#show_password1', '#user-pass', '#icon1');
    bindPasswordToggle('#show_password2', '#user-repeatpass', '#icon2');
    bindPasswordToggle('#show_client_pin', '#inputPin', '#icon_client_pin');

    function openClientPinModal() {
        const $modal = $('#clientPinModal');
        $modal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('izzy-modal-open');

        window.setTimeout(function () {
            $('#inputCliente').trigger('focus');
        }, 80);
    }

    function closeClientPinModal() {
        const $modal = $('#clientPinModal');
        $modal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('izzy-modal-open');
    }

    window.closeClientPinModal = closeClientPinModal;

    $('#clientAccessTrigger')
        .off('click.izzyClientModal')
        .on('click.izzyClientModal', function (e) {
            e.preventDefault();
            if ($(this).prop('disabled')) return;
            openClientPinModal();
        });

    $('[data-client-modal-close]')
        .off('click.izzyClientModal')
        .on('click.izzyClientModal', function (e) {
            e.preventDefault();
            closeClientPinModal();
        });

    $(document)
        .off('keydown.izzyClientModal')
        .on('keydown.izzyClientModal', function (e) {
            if (e.key === 'Escape' && $('#clientPinModal').hasClass('is-open')) {
                closeClientPinModal();
            }
        });

    $('#inputCliente').off('input.izzyClientPin').on('input.izzyClientPin', function () {
        if ($(this).val().length >= 8) {
            $('#inputPin').trigger('focus');
        }
    });

    $('#validateClientPin')
        .off('click.izzyClientPin')
        .on('click.izzyClientPin', function (e) {
            e.preventDefault();

            const cliente = $.trim($('#inputCliente').val());
            const pin = $.trim($('#inputPin').val());

            if (!cliente || !pin) {
                if (typeof showNotify === 'function') {
                    showNotify('warning', 'Datos requeridos', 'Ingresa el cliente y el PIN para continuar.');
                }
                if (!cliente) {
                    $('#inputCliente').trigger('focus');
                } else {
                    $('#inputPin').trigger('focus');
                }
                return;
            }

            closeClientPinModal();
            $('#loginform').trigger('submit');
        });
})();




</script>
