<?php	
    $peticionAjax = true;
    require_once "././core/configAPP.php";
?>

<link href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/css/bootstrap-select.min.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/sweetalert/sweetalert.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/notyf.min.css" rel="stylesheet" />
<link href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/style_login.css" rel="stylesheet" crossorigin="anonymous" />    

<div id="logreg-forms">
    <!-- ===================== FORMULARIO DE INICIO DE SESIÓN ===================== -->
    <form class="form-signin" id="loginform" action="" method="POST" autocomplete="off">
        <h1 class="h3 mb-3 font-weight-normal" style="text-align: center">Iniciar Sesión</h1>
        <p class="text-center text-muted small">Accede a tu cuenta con tu correo electrónico y contraseña</p>

        <div style="text-align: center;">
            <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logo.svg" width="100%">
        </div>

        <br />

        <!-- Campo Correo Electrónico -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text boton"><i class="fas fa-envelope-square"></i></span>
            </div>
            <input type="email" id="inputEmail" name="inputEmail" class="form-control" 
                   placeholder="Correo electrónico" 
                   required autofocus tabindex="1"
                   data-toggle="tooltip" data-placement="top" 
                   title="Ingresa el correo electrónico con el que te registraste">
        </div>

        <!-- Campo Contraseña -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text boton"><i class="fa fa-lock"></i></span>
            </div>
            <input type="password" id="inputPassword" name="inputPassword" class="form-control" 
                   placeholder="Contraseña" 
                   required tabindex="2"
                   data-toggle="tooltip" data-placement="top" 
                   title="Ingresa tu contraseña. Debe tener al menos 8 caracteres">
            <div class="input-group-append">
                <button id="show_password" class="btn btn-primary boton" type="button" tabindex="3"
                        data-toggle="tooltip" data-placement="top" title="Mostrar/Ocultar contraseña">
                    <span id="icon" class="fa fa-eye-slash icon"></span>
                </button>
            </div>
        </div>

        <!-- Campos adicionales para clientes (ocultos inicialmente) -->
        <div class="input-group mb-3" id="groupDB" style="display: none;">
            <div class="input-group-prepend">
                <span class="input-group-text boton"><i class="fas fa-user"></i></span>
            </div>
            <input type="number" class="form-control" value="" placeholder="Cliente" 
                   aria-label="Cliente" id="inputCliente" name="inputCliente" tabindex="4"
                   data-toggle="tooltip" data-placement="top" 
                   title="Número de cliente asignado por el sistema">
            <div class="input-group-append">
                <span class="input-group-text boton"><i class="fas fa-key"></i></span>
            </div>
            <input type="number" class="form-control" value="" placeholder="PIN" 
                   aria-label="PIN" id="inputPin" name="inputPin" tabindex="5"
                   data-toggle="tooltip" data-placement="top" 
                   title="Código PIN de seguridad">
        </div>

        <div class="RespuestaAjax"></div>

        <button class="btn btn-primary btn-block boton" type="submit" id="enviar" tabindex="6"
            data-toggle="tooltip" data-placement="top" 
            title="Haz clic aquí para acceder a tu cuenta con tus credenciales">
            <i class="fas fa-sign-in-alt fa-lg"></i> Iniciar Sesión
        </button>
        
        <a style="text-decoration:none;" class="ancla" href="#" id="forgot_pswd" tabindex="7"
           data-toggle="tooltip" data-placement="top" 
           title="¿No recuerdas tu contraseña? Haz clic aquí para restablecerla">
            ¿Olvidó su contraseña?
        </a>
        
        <hr>
        
        <button class="btn btn-primary btn-block" type="button" id="btn-signup"
                data-toggle="tooltip" data-placement="top" 
                title="¿Eres nuevo? Regístrate para acceder a todos nuestros servicios">
            <i class="fas fa-user-plus"></i> Comienza tu experiencia. Regístrate ahora.
        </button>
    </form>

    <!-- ===================== FORMULARIO DE RECUPERACIÓN DE CONTRASEÑA ===================== -->
    <form class="form-reset" id="forgot_form" autocomplete="off">
        <h1 class="h3 mb-3 font-weight-normal" style="text-align: center">Restablecer Contraseña</h1>
        <p class="text-center text-muted small">Ingresa tu correo electrónico para recibir instrucciones</p>

        <div style="text-align: center;">
            <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logo.svg" width="100%">
        </div>

        <br />

        <!-- Campo Correo Electrónico -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text boton"><i class="fas fa-envelope-square"></i></span>
            </div>
            <input type="email" class="form-control" placeholder="Correo electrónico" 
                   required autofocus name="usu_forgot" id="usu_forgot" tabindex="1"
                   data-toggle="tooltip" data-placement="top" 
                   title="Ingresa el correo electrónico asociado a tu cuenta">
        </div>

        <div class="RespuestaAjax"></div>

        <button class="btn btn-primary btn-block boton" type="submit" tabindex="2">
            <i class='fas fa-sync-alt fa-lg'></i> Restablecer
        </button>
        
        <a style="text-decoration:none;" href="#" id="cancel_reset" tabindex="3"
           data-toggle="tooltip" data-placement="top" title="Volver al formulario de inicio de sesión">
            <i class="fas fa-angle-left"></i> Atrás
        </a>
    </form>

    <!-- ===================== FORMULARIO DE REGISTRO ===================== -->
    <form class="form-signup" id="form_registro" autocomplete="off">
        <h1 class="h3 mb-3 font-weight-normal text-center">Registro de Nuevo Cliente</h1>
        <p class="text-center text-muted small">Completa tus datos para crear una nueva cuenta</p>

        <div class="text-center">
            <img src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/img/logo.svg" width="100%">
        </div>

        <br />        

        <!-- Campo Empresa -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa-solid fa-building"></i></span>
            </div>
            <input type="text" id="user_empresa" name="user_empresa" class="form-control" 
                   placeholder="Empresa o nombre personal" 
                   required autofocus tabindex="1"
                   data-toggle="tooltip" data-placement="top" 
                   title="Nombre de tu empresa o tu nombre personal si eres individuo">
        </div>

        <!-- Nombre Completo -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
            </div>
            <input type="text" id="user_name" name="user_name" class="form-control" 
                   placeholder="Nombre completo" 
                   required tabindex="2"
                   data-toggle="tooltip" data-placement="top" 
                   title="Tu nombre completo como aparece en documentos oficiales">
        </div>

        <!-- Teléfono -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
            </div>
            <input type="number" id="user_telefono" name="user_telefono" class="form-control"
                   placeholder="Teléfono (8 dígitos)" 
                   required tabindex="3"
                   data-toggle="tooltip" data-placement="top" 
                   title="Número de teléfono móvil o fijo (sin guiones ni espacios)">
        </div>

        <!-- Correo Electrónico -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-at"></i></span>
            </div>
            <input type="email" class="form-control" placeholder="Correo electrónico" 
                   id="mail" name="email" required tabindex="4" 
                   data-toggle="tooltip" data-placement="top" 
                   title="Ingresa un correo electrónico válido. Será tu usuario para acceder al sistema">
            <div class="input-group-append">
                <span class="input-group-text">@ejemplo.com</span>
            </div>
        </div>

        <!-- Contraseña -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
            </div>
            <input type="password" id="user-pass" name="user-pass" class="form-control" 
                   placeholder="Contraseña (mín. 8 caracteres)" 
                   required tabindex="5"
                   data-toggle="tooltip" data-placement="top" 
                   title="Crea una contraseña segura con al menos 8 caracteres, incluyendo mayúsculas, minúsculas y números">
            <div class="input-group-append">
                <button id="show_password1" class="btn btn-primary" type="button"
                        data-toggle="tooltip" data-placement="top" title="Mostrar/Ocultar contraseña">
                    <span id="icon1" class="fa fa-eye-slash icon"></span>
                </button>
            </div>
        </div>

        <!-- Confirmar Contraseña -->
        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fa fa-lock"></i></span>
            </div>
            <input type="password" id="user-repeatpass" class="form-control" 
                   placeholder="Confirmar contraseña" 
                   required tabindex="6"
                   data-toggle="tooltip" data-placement="top" 
                   title="Vuelve a escribir tu contraseña para confirmarla">
            <div class="input-group-append">
                <button id="show_password2" class="btn btn-primary" type="button"
                        data-toggle="tooltip" data-placement="top" title="Mostrar/Ocultar contraseña">
                    <span id="icon2" class="fa fa-eye-slash icon"></span>
                </button>
            </div>
        </div>

        <button class="btn btn-primary btn-block" type="button" id="registrarse" tabindex="7"
                data-toggle="tooltip" data-placement="top" 
                title="Haz clic aquí para completar tu registro">
            <i class="fas fa-user-plus"></i> Completar Registro
        </button>
        
        <a class="text-decoration-none" href="#" id="cancel_signup" tabindex="8"
           data-toggle="tooltip" data-placement="top" title="Volver al formulario de inicio de sesión">
            <i class="fas fa-angle-left"></i> Volver al inicio
        </a>
    </form>
    
    <!-- Copyright -->
    <div class="footer-copyright text-center py-3">
        © 2021 - <?php echo date("Y");?> Copyright:
        <div style="text-align: center;">
            <p class="navbar-text">Todos los derechos reservados</p>
        </div>        
    </div>
</div>

<!-- Scripts -->
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/query/jquery-3.5.1.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/popper/popper.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/bootstrap/js/bootstrap-select.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/sweetalert/sweetalert.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/librerias/notyf.min.js" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/js/login-toggle.js" crossorigin="anonymous"></script>

<!-- Inicialización de Tooltips y Notificaciones -->
<script>
// Inicializar tooltips de Bootstrap
$(function () {
    $('[data-toggle="tooltip"]').tooltip({
        delay: { "show": 300, "hide": 100 },
        trigger: 'hover focus'
    });
});

// Configuración de Notyf (notificaciones)
const notyf = new Notyf({
    position: { x: 'right', y: 'top' },
    dismissible: true,
    closeOnClick: true,
    types: [
        {
            type: 'warning',
            background: 'orange',
            duration: 5000,
            icon: { className: 'fas fa-exclamation-triangle fa-lg', tagName: 'i', color: 'white' },
            closeIcon: { className: 'fas fa-times', color: 'white', tagName: 'span', position: 'right' }
        },
        {
            type: 'error',
            background: 'indianred',
            duration: 10000,
            dismissible: true,
            icon: { className: 'fas fa-times-circle fa-lg', tagName: 'i', color: 'white' },
            closeIcon: { className: 'fas fa-times', color: 'white', tagName: 'span', position: 'right' }
        },
        {
            type: 'info',
            background: '#1e88e5',
            duration: 5000,
            dismissible: true,
            icon: { className: 'fas fa-info-circle fa-lg', tagName: 'i', color: 'white' },
            closeIcon: { className: 'fas fa-times', color: 'white', tagName: 'span', position: 'right' }
        },
        {
            type: 'success',
            background: '#4caf50',
            duration: 5000,
            dismissible: true,
            icon: { className: 'fas fa-check-circle fa-lg', tagName: 'i', color: 'white' },
            closeIcon: { className: 'fas fa-times', color: 'white', tagName: 'span', position: 'right' }
        },
        {
            type: 'loading',
            background: '#3498db',
            duration: 5000,
            icon: { className: 'fas fa-circle-notch fa-spin', tagName: 'i', color: 'white' },
            dismissible: false,
            closeIcon: false
        }        
    ]
});

let loadingNotification = null;

function showLoading(message = "Procesando, por favor espere...") {
    loadingNotification = notyf.open({
        type: 'loading',
        message: message
    });
}

function showNotify(type, title, message) {
    const validTypes = ['success', 'error', 'warning', 'info', 'loading'];
    
    if (validTypes.includes(type)) {
        notyf.open({
            type: type,
            message: `<strong>${title}</strong><br>${message}`,
            settings: { ripple: true, allowHtml: true }
        });
    } else {
        console.error('Tipo de notificación no válido');
    }
}
</script>

<?php
    require_once "./ajax/js/login.php";
?>