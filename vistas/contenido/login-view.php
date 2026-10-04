<?php
    $peticionAjax = true;
    require_once "././core/configAPP.php";

    $serverUrlSafe = htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8');
    $loginHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $loginHost = preg_replace('/:\d+$/', '', $loginHost);
    $isDemoLogin = ($loginHost === 'demo.izzycloud.app' || str_starts_with($loginHost, 'demo.'));
    $demoUser = $isDemoLogin ? 'admin@izzycloud.app' : '';
    $demoPass = $isDemoLogin ? 'admin' : '';

    // Evita que el navegador reutilice una vista antigua del login después de publicar cambios.
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }

    // Cache-busting estable: el navegador conserva el archivo mientras no cambie y lo
    // vuelve a descargar automáticamente cuando el CSS o el logo son actualizados.
    $styleLoginFile = __DIR__ . '/../plantilla/css/style_login.css';
    $styleLoginVersion = is_file($styleLoginFile) ? (string) filemtime($styleLoginFile) : '1.0.13';

    $loginLogoFile = __DIR__ . '/../plantilla/img/logo.svg';
    $loginLogoVersion = is_file($loginLogoFile) ? (string) filemtime($loginLogoFile) : '1.0.13';
?>

<link href="<?php echo $serverUrlSafe; ?>ajax/bootstrap/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo $serverUrlSafe; ?>ajax/bootstrap/css/bootstrap-select.min.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo $serverUrlSafe; ?>ajax/sweetalert/sweetalert.css" rel="stylesheet" crossorigin="anonymous" />
<link href="<?php echo $serverUrlSafe; ?>vistas/plantilla/css/notyf.min.css" rel="stylesheet" />
<link href="<?php echo $serverUrlSafe; ?>vistas/plantilla/css/style_login.css?v=<?php echo rawurlencode($styleLoginVersion); ?>" rel="stylesheet" crossorigin="anonymous" data-izzy-login-style-version="<?php echo htmlspecialchars($styleLoginVersion, ENT_QUOTES, 'UTF-8'); ?>" />

<div class="izzy-login-page">
    <section class="izzy-login-shell">

        <aside class="izzy-login-brand" aria-label="Información de IZZY">
            <div class="brand-header">
                <a class="brand-logo" href="https://izzycloud.app/" target="_blank" rel="noopener" aria-label="Abrir sitio web de IZZY">
                    <img src="<?php echo $serverUrlSafe; ?>vistas/plantilla/img/logo.svg?v=<?php echo rawurlencode($loginLogoVersion); ?>" alt="IZZY">
                </a>

                <a class="brand-website" href="https://izzycloud.app/" target="_blank" rel="noopener">
                    <i class="fas fa-globe-americas" aria-hidden="true"></i>
                    <span>Sitio web</span>
                </a>
            </div>

            <div class="brand-main">
                <span class="brand-kicker">SIMPLIFICA · CONTROLA · CRECE</span>

                <h2>Tu negocio,<br><strong>más simple con IZZY.</strong></h2>

                <p class="brand-summary">
                    Una plataforma para facturar, controlar inventario, vender, atender clientes
                    y administrar tu operación desde un solo lugar.
                </p>

                <div class="brand-feature-grid" aria-label="Beneficios principales">
                    <article class="brand-feature">
                        <span class="feature-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></span>
                        <div>
                            <strong>Factura con orden</strong>
                            <small>Vende, cobra y consulta tu operación con claridad.</small>
                        </div>
                    </article>

                    <article class="brand-feature">
                        <span class="feature-icon"><i class="fas fa-boxes" aria-hidden="true"></i></span>
                        <div>
                            <strong>Controla inventario</strong>
                            <small>Productos, existencias y movimientos disponibles cuando los necesitas.</small>
                        </div>
                    </article>

                    <article class="brand-feature">
                        <span class="feature-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                        <div>
                            <strong>Decide mejor</strong>
                            <small>Reportes y datos para entender lo que pasa en tu negocio.</small>
                        </div>
                    </article>

                    <article class="brand-feature">
                        <span class="feature-icon"><i class="fas fa-mobile-alt" aria-hidden="true"></i></span>
                        <div>
                            <strong>Trabaja donde estés</strong>
                            <small>Computadora, tablet o móvil con una experiencia adaptable.</small>
                        </div>
                    </article>
                </div>

                <div class="brand-businesses">
                    <span>Ideal para</span>
                    <div class="brand-business-list">
                        <b><i class="fas fa-store" aria-hidden="true"></i><span>Tiendas</span></b>
                        <b><i class="fas fa-utensils" aria-hidden="true"></i><span>Restaurantes</span></b>
                        <b><i class="fas fa-tools" aria-hidden="true"></i><span>Servicios</span></b>
                        <b><i class="fas fa-building" aria-hidden="true"></i><span>PYMES</span></b>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                <span>Una solución de <strong>ES MULTISERVICIOS</strong></span>
                <span>© <?php echo date("Y"); ?> IZZY</span>
            </div>
        </aside>

        <main class="izzy-login-access">
            <div class="auth-mobile-logo">
                <a href="https://izzycloud.app/" target="_blank" rel="noopener" aria-label="Abrir sitio web de IZZY">
                    <img src="<?php echo $serverUrlSafe; ?>vistas/plantilla/img/logo.svg?v=<?php echo rawurlencode($loginLogoVersion); ?>" alt="IZZY">
                </a>
            </div>

            <div id="logreg-forms">

                <!-- LOGIN -->
                <form class="form-signin auth-view<?php echo $isDemoLogin ? ' is-demo-login' : ''; ?>" id="loginform" action="" method="POST" autocomplete="off" data-demo="<?php echo $isDemoLogin ? '1' : '0'; ?>">
                    <div class="auth-heading">
                        <span class="auth-kicker"><?php echo $isDemoLogin ? 'DEMO IZZY' : 'BIENVENIDO'; ?></span>
                        <h1>Iniciar sesión</h1>
                        <p>
                            <?php if ($isDemoLogin): ?>
                                <strong>Modo demo activo.</strong> Las credenciales están listas para que explores IZZY.
                            <?php else: ?>
                                Accede a tu cuenta <strong class="izzy-word">IZZY</strong> con tu <strong>correo electrónico</strong> y <strong>contraseña</strong>.
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="auth-form-stack">
                        <label class="auth-control">
                            <span class="auth-control-label">Correo electrónico</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                <input type="email" id="inputEmail" name="inputEmail"
                                       value="<?php echo htmlspecialchars($demoUser, ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="tu@correo.com"
                                       required autofocus tabindex="1"
                                       autocomplete="username">
                            </span>
                        </label>

                        <div class="auth-password-row">
                            <label class="auth-control auth-password-control">
                                <span class="auth-control-label">Contraseña</span>
                                <span class="auth-input has-action">
                                    <span class="auth-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                    <input type="password" id="inputPassword" name="inputPassword"
                                           value="<?php echo htmlspecialchars($demoPass, ENT_QUOTES, 'UTF-8'); ?>"
                                           placeholder="Ingresa tu contraseña"
                                           required tabindex="2"
                                           autocomplete="current-password">
                                    <button id="show_password" class="auth-password-button" type="button" tabindex="3" aria-label="Mostrar u ocultar contraseña">
                                        <span id="icon" class="fa fa-eye-slash icon" aria-hidden="true"></span>
                                    </button>
                                </span>
                            </label>

                            <div class="auth-client-option" id="groupDB">
                                <button type="button" class="auth-client-inline" id="clientAccessTrigger" tabindex="4" aria-label="Agregar cliente y PIN" disabled aria-disabled="true">
                                    <span class="auth-client-inline-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                                    <span class="auth-client-inline-copy">
                                        <small id="clientAccessState">Validá tus credenciales</small>
                                        <strong>Cliente / PIN</strong>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="RespuestaAjax" aria-live="polite"></div>

                        <button class="auth-button auth-button-primary" type="submit" id="enviar" tabindex="6">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                            <span>Iniciar sesión</span>
                        </button>

                        <div class="auth-link-row">
                            <a href="#" id="forgot_pswd" tabindex="7" class="auth-forgot-link">
                                <span class="auth-forgot-icon"><i class="fas fa-key" aria-hidden="true"></i></span>
                                <span>
                                    <small>¿Problemas para ingresar?</small>
                                    <strong>Recuperar contraseña</strong>
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="auth-account-block">
                        <div class="auth-divider"><span>¿Aún no tienes cuenta?</span></div>

                        <button class="auth-button auth-button-create" type="button" id="btn-signup">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            <span>Crear mi cuenta IZZY</span>
                        </button>
                    </div>

                    <div class="auth-extra-actions">
                        <a class="auth-extra-card demo" href="https://demo.izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-play" aria-hidden="true"></i></span>
                            <span>
                                <small>Prueba <span class="izzy-word">IZZY</span> antes de registrarte</small>
                                <strong>Probar demo <span class="izzy-word">IZZY</span></strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>

                        <a class="auth-extra-card website" href="https://izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-globe-americas" aria-hidden="true"></i></span>
                            <span>
                                <small>Descubre todo lo que ofrece <span class="izzy-word">IZZY</span></small>
                                <strong>Visitar sitio web</strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="auth-security-note">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <span>Acceso seguro para clientes IZZY.</span>
                    </div>

                    <div class="izzy-client-modal" id="clientPinModal" aria-hidden="true">
                        <div class="izzy-client-modal-backdrop" data-client-modal-close></div>

                        <section class="izzy-client-modal-dialog"
                                 role="dialog"
                                 aria-modal="true"
                                 aria-labelledby="clientPinModalTitle">
                            <button type="button"
                                    class="izzy-modal-close"
                                    id="clientPinModalClose"
                                    data-client-modal-close
                                    aria-label="Cerrar">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>

                            <div class="izzy-modal-head">
                                <span class="izzy-modal-icon">
                                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <span class="izzy-modal-kicker">ACCESO DE CLIENTE</span>
                                    <h2 id="clientPinModalTitle">Cliente y PIN</h2>
                                    <p>Ingresa el <strong>Cliente</strong> y el <strong>PIN</strong> para validar este acceso e <strong>iniciar sesión directamente</strong>.</p>
                                </div>
                            </div>

                            <div class="izzy-modal-fields">
                                <label class="auth-control">
                                    <span class="auth-control-label">Cliente</span>
                                    <span class="auth-input">
                                        <span class="auth-input-icon">
                                            <i class="fas fa-building" aria-hidden="true"></i>
                                        </span>
                                        <input type="number"
                                               id="inputCliente"
                                               name="inputCliente"
                                               placeholder="Ingresa el cliente"
                                               inputmode="numeric"
                                               autocomplete="off">
                                    </span>
                                </label>

                                <label class="auth-control">
                                    <span class="auth-control-label">PIN</span>
                                    <span class="auth-input has-action">
                                        <span class="auth-input-icon">
                                            <i class="fas fa-key" aria-hidden="true"></i>
                                        </span>
                                        <input type="password"
                                               id="inputPin"
                                               name="inputPin"
                                               placeholder="Ingresa el PIN"
                                               inputmode="numeric"
                                               autocomplete="one-time-code">
                                        <button type="button"
                                                class="auth-password-button"
                                                id="show_client_pin"
                                                aria-label="Mostrar PIN">
                                            <span id="icon_client_pin" class="fa fa-eye-slash icon" aria-hidden="true"></span>
                                        </button>
                                    </span>
                                </label>
                            </div>

                            <div class="izzy-modal-note">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span>Si no necesitas <strong>Cliente/PIN</strong>, cierra esta ventana y usa <strong>“Iniciar sesión”</strong> normalmente.</span>
                            </div>

                            <div class="izzy-modal-actions">
                                <button type="button"
                                        class="auth-button auth-button-primary"
                                        id="validateClientPin">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    <span>Validar e iniciar sesión</span>
                                </button>

                                <button type="button"
                                        class="auth-return-button"
                                        data-client-modal-close>
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                    <span>Cancelar</span>
                                </button>
                            </div>
                        </section>
                    </div>

                </form>

                <!-- RECUPERACIÓN -->
                <form class="form-reset auth-view" id="forgot_form" autocomplete="off">
                    <div class="auth-view-symbol" aria-hidden="true">
                        <span><i class="fas fa-unlock-alt"></i></span>
                        <div><strong>Recupera tu acceso</strong><small>Proceso seguro de recuperación IZZY</small></div>
                    </div>
                    <div class="auth-heading">
                        <span class="auth-kicker">RECUPERA TU ACCESO</span>
                        <h1>Restablecer contraseña</h1>
                        <p>Ingresa tu <strong>correo electrónico</strong> y te enviaremos las instrucciones para <strong>recuperar tu acceso</strong>.</p>
                    </div>

                    <div class="auth-form-stack">
                        <label class="auth-control">
                            <span class="auth-control-label">Correo electrónico</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                                <input type="email" id="usu_forgot" name="usu_forgot"
                                       placeholder="tu@correo.com"
                                       required autofocus tabindex="1"
                                       autocomplete="email">
                            </span>
                        </label>

                        <div class="RespuestaAjax" aria-live="polite"></div>

                        <button class="auth-button auth-button-primary" type="submit" tabindex="2">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                            <span>Enviar instrucciones</span>
                        </button>

                        <button class="auth-return-button" type="button" id="cancel_reset" tabindex="3">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            <span>Volver a iniciar sesión</span>
                        </button>
                    </div>
                    <div class="auth-explore-intro"><i class="fas fa-compass" aria-hidden="true"></i><span><strong>Mientras recuperas tu acceso</strong><small>También puedes conocer <strong class="izzy-word">IZZY</strong> y explorar la experiencia demo.</small></span></div>
                    <div class="auth-extra-actions auth-extra-actions-compact">
                        <a class="auth-extra-card demo" href="https://demo.izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-play" aria-hidden="true"></i></span>
                            <span>
                                <small>Prueba <span class="izzy-word">IZZY</span> antes de registrarte</small>
                                <strong>Probar demo <span class="izzy-word">IZZY</span></strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>

                        <a class="auth-extra-card website" href="https://izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-globe-americas" aria-hidden="true"></i></span>
                            <span>
                                <small>Descubre todo lo que ofrece <span class="izzy-word">IZZY</span></small>
                                <strong>Visitar sitio web</strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                </form>

                <!-- REGISTRO -->
                <form class="form-signup auth-view" id="form_registro" autocomplete="off">
                    <div class="auth-heading auth-heading-register">
                        <span class="auth-kicker">CREA TU CUENTA</span>
                        <h1>Comienza con IZZY</h1>
                        <p>Completa tus datos para crear tu cuenta. <strong class="izzy-word">IZZY</strong> mantiene el proceso simple, claro y seguro.</p>
                    </div>

                    <div class="registration-grid">
                        <label class="auth-control span-2">
                            <span class="auth-control-label">Empresa o nombre personal</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-building" aria-hidden="true"></i></span>
                                <input type="text" id="user_empresa" name="user_empresa"
                                       placeholder="Nombre de tu negocio o tu nombre"
                                       required autofocus tabindex="1" autocomplete="organization">
                            </span>
                        </label>

                        <label class="auth-control auth-register-name span-2">
                            <span class="auth-control-label">Nombre completo</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-user" aria-hidden="true"></i></span>
                                <input type="text" id="user_name" name="user_name"
                                       placeholder="Tu nombre"
                                       required tabindex="2" autocomplete="name">
                            </span>
                        </label>

                        <label class="auth-control auth-register-phone span-2">
                            <span class="auth-control-label">Teléfono</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-phone" aria-hidden="true"></i></span>
                                <input type="tel" id="user_telefono" name="user_telefono"
                                       placeholder="8-12 dígitos"
                                       required tabindex="3" autocomplete="tel" inputmode="numeric">
                            </span>
                        </label>

                        <label class="auth-control span-2">
                            <span class="auth-control-label">Correo electrónico</span>
                            <span class="auth-input">
                                <span class="auth-input-icon"><i class="fas fa-at" aria-hidden="true"></i></span>
                                <input type="email" id="mail" name="email"
                                       placeholder="tu@correo.com"
                                       required tabindex="4" autocomplete="email">
                            </span>
                        </label>

                        <label class="auth-control auth-register-password">
                            <span class="auth-control-label">Contraseña</span>
                            <span class="auth-input has-action">
                                <span class="auth-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                <input type="password" id="user-pass" name="user-pass"
                                       placeholder="Mínimo 8 caracteres"
                                       required tabindex="5" autocomplete="new-password">
                                <button id="show_password1" class="auth-password-button" type="button" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                                    <span id="icon1" class="fa fa-eye-slash icon" aria-hidden="true"></span>
                                </button>
                            </span>
                        </label>

                        <label class="auth-control auth-register-password-confirm">
                            <span class="auth-control-label">Confirmar contraseña</span>
                            <span class="auth-input has-action">
                                <span class="auth-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                                <input type="password" id="user-repeatpass"
                                       placeholder="Repite tu contraseña"
                                       required tabindex="6" autocomplete="new-password">
                                <button id="show_password2" class="auth-password-button" type="button" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
                                    <span id="icon2" class="fa fa-eye-slash icon" aria-hidden="true"></span>
                                </button>
                            </span>
                        </label>

                        <div class="auth-password-info span-2">
                            <span><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                            <div>
                                <strong>Protege tu cuenta</strong>
                                <small>Usa al menos 8 caracteres y evita contraseñas fáciles de adivinar.</small>
                            </div>
                        </div>

                        <button class="auth-button auth-button-primary span-2" type="button" id="registrarse" tabindex="7">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            <span>Crear cuenta IZZY</span>
                        </button>

                        <button class="auth-return-button span-2" type="button" id="cancel_signup" tabindex="8">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            <span>Volver a iniciar sesión</span>
                        </button>
                    </div>
                    <div class="auth-extra-actions auth-extra-actions-compact">
                        <a class="auth-extra-card demo" href="https://demo.izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-play" aria-hidden="true"></i></span>
                            <span>
                                <small>Prueba <span class="izzy-word">IZZY</span> antes de registrarte</small>
                                <strong>Probar demo <span class="izzy-word">IZZY</span></strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>

                        <a class="auth-extra-card website" href="https://izzycloud.app/" target="_blank" rel="noopener">
                            <span class="auth-extra-icon"><i class="fas fa-globe-americas" aria-hidden="true"></i></span>
                            <span>
                                <small>Descubre todo lo que ofrece <span class="izzy-word">IZZY</span></small>
                                <strong>Visitar sitio web</strong>
                            </span>
                            <i class="fas fa-arrow-right auth-extra-arrow" aria-hidden="true"></i>
                        </a>
                    </div>
                </form>

                <div class="auth-footer-mobile">
                    <span>© 2020 - <?php echo date("Y"); ?> IZZY</span>
                    <span>Una solución de ES MULTISERVICIOS</span>
                </div>
            </div>
        </main>
    </section>
</div>

<script src="<?php echo $serverUrlSafe; ?>ajax/query/jquery-3.5.1.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>ajax/popper/popper.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>ajax/bootstrap/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>ajax/bootstrap/js/bootstrap-select.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>ajax/sweetalert/sweetalert.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>ajax/librerias/notyf.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $serverUrlSafe; ?>vistas/plantilla/js/login-toggle.js" crossorigin="anonymous"></script>

<script>
const notyf = new Notyf({
    position: { x: 'right', y: 'top' },
    dismissible: true,
    closeOnClick: true,
    duration: 5000,
    types: [
        {
            type: 'warning',
            background: '#c78300',
            duration: 5500,
            icon: { className: 'fas fa-exclamation-triangle', tagName: 'i', color: 'white' }
        },
        {
            type: 'error',
            background: '#c93f4c',
            duration: 8000,
            icon: { className: 'fas fa-times-circle', tagName: 'i', color: 'white' }
        },
        {
            type: 'info',
            background: '#087fae',
            duration: 5000,
            icon: { className: 'fas fa-info-circle', tagName: 'i', color: 'white' }
        },
        {
            type: 'success',
            background: '#169b62',
            duration: 5000,
            icon: { className: 'fas fa-check-circle', tagName: 'i', color: 'white' }
        },
        {
            type: 'loading',
            background: '#0b3b70',
            duration: 5000,
            dismissible: false,
            icon: { className: 'fas fa-circle-notch fa-spin', tagName: 'i', color: 'white' }
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
    const normalizedType = validTypes.includes(type) ? type : 'info';
    const safeTitle = $('<div>').text(title || '').html();
    const safeMessage = $('<div>').text(message || '').html();

    notyf.open({
        type: normalizedType,
        message: `<strong>${safeTitle}</strong>${safeMessage ? `<br>${safeMessage}` : ''}`,
        settings: { ripple: false, allowHtml: true }
    });
}

$(function () {
    if ($('#inputEmail').is(':visible')) {
        $('#inputEmail').trigger('focus');
    }
});
</script>

<?php
    require_once "./ajax/js/login.php";
?>
