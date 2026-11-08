<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start(['name' => 'SD']);
if (empty($_SESSION['csrf_administrar_api'])) $_SESSION['csrf_administrar_api'] = bin2hex(random_bytes(32));
$apiCsrf = htmlspecialchars($_SESSION['csrf_administrar_api'], ENT_QUOTES, 'UTF-8');
$apiBase = rtrim(SERVERURL, '/') . '/api/v1/';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>vistas/plantilla/css/administrarApi.css?v=<?php echo @filemtime(__DIR__ . '/../plantilla/css/administrarApi.css') ?: time(); ?>">
<div class="container-fluid izzy-api-page" id="izzy_api_admin" data-csrf="<?php echo $apiCsrf; ?>">
    <div class="api-page-heading">
        <div class="api-heading-icon"><i class="fas fa-code"></i></div>
        <div><span class="api-eyebrow">SISTEMA / API</span><h1>API y accesos</h1>
            <p>Integraciones externas y sesiones móviles.</p></div>
    </div>

    <section class="api-identity-card">
        <div class="api-identity-main"><div class="api-identity-icon"><i class="fas fa-fingerprint"></i></div><div>
            <span class="api-eyebrow">IDENTIFICADOR ÚNICO DE ESTA VERSIÓN</span>
            <strong id="api_identifier">izzy_apiv1</strong>
            <small>Identifica la API y su versión. No es una contraseña ni reemplaza el inicio de sesión.</small>
        </div></div>
        <button type="button" class="btn btn-primary" id="api_copy_identifier"><i class="far fa-copy"></i> Copiar ID</button>
    </section>

    <section class="api-notice" role="note">
        <i class="fas fa-shield-alt"></i><div><strong>Autenticación móvil por usuario</strong>
        <span>La app móvil usa tokens individuales de usuario. Las credenciales de Integraciones son solo para sistemas externos en servidor; nunca van dentro del APK.</span></div>
    </section>

    <div class="api-tabs" role="tablist">
        <button class="api-tab is-active" type="button" data-api-tab="integrations"><i class="fas fa-plug"></i> Integraciones</button>
        <button class="api-tab" type="button" data-api-tab="routes"><i class="fas fa-code"></i> Rutas API</button>
        <button class="api-tab" type="button" data-api-tab="sessions"><i class="fas fa-mobile-alt"></i> Sesiones móviles</button>
    </div>

    <section class="api-panel is-active" data-api-panel="integrations">
        <div class="api-panel-heading"><div><h2>Integraciones externas</h2><p>Credenciales para que un sistema de servidor se identifique ante endpoints IZZY autorizados.</p></div>
            <button type="button" class="btn btn-primary" id="api_new_integration"><i class="fas fa-plus"></i> Nueva integración</button></div>
        <div class="api-integration-notice"><i class="fas fa-info-circle"></i><span>Estas credenciales identifican integraciones servidor a servidor. Las rutas móviles de login, refresh, perfil, cierre y menús siguen usando tokens individuales de usuario.</span></div>
        <div class="api-integration-list" id="api_integrations_rows" aria-live="polite"><div class="api-loading">Cargando integraciones…</div></div>
        <div class="api-footnote"><i class="fas fa-lock"></i> El secreto no se guarda en texto legible. Las rutas protegidas para servidor se habilitarán conforme se publiquen sus endpoints y alcances.</div>
    </section>

    <section class="api-panel" data-api-panel="routes">
        <div class="api-panel-heading"><div><h2>Rutas de la aplicación móvil</h2><p>Estas rutas usan la autenticación por usuario y no son registros de integración.</p></div>
            <span class="api-status is-active"><i class="fas fa-code-branch"></i> izzy_apiv1</span></div>
        <div class="api-base-url"><span>Base URL</span><code><?php echo htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8'); ?></code></div>
        <div class="api-token-lifecycle" aria-label="Duración de tokens">
            <div><i class="fas fa-key"></i><span>Access token<strong>60 minutos</strong></span></div>
            <div><i class="fas fa-sync-alt"></i><span>Refresh token<strong>30 días, con rotación</strong></span></div>
            <div><i class="fas fa-database"></i><span>Almacenamiento<strong>Solo hash en DB_MAIN</strong></span></div>
        </div>
        <div class="api-route-grid">
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-post">POST</span><strong>/auth/login.php</strong><button type="button" class="api-auth-tag auth-public" data-api-info="No necesita un token previo: valida las credenciales de usuario para iniciar sesión. La conexión siempre debe usar HTTPS.">Pública · HTTPS</button></div>
                <small>Valida correo o usuario y contraseña. Devuelve tokens individuales y el perfil inicial.</small>
                <details><summary>Ver solicitud y respuesta</summary><pre>{
  "correo": "usuario@cliente.com",
  "password": "contraseña"
}</pre><p>También acepta <code>usuario</code> en lugar de <code>correo</code>. La respuesta entrega <code>access_token</code>, <code>refresh_token</code>, <code>usuario</code>, <code>cliente</code> y <code>permisos</code>.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-post">POST</span><strong>/auth/refresh.php</strong><button type="button" class="api-auth-tag auth-refresh" data-api-info="Envía el refresh token vigente para rotar y renovar los tokens de la sesión móvil.">Refresh token</button></div>
                <small>Rota el refresh token y devuelve un nuevo par. Cada refresh token se usa una sola vez.</small>
                <details><summary>Ver solicitud</summary><pre>{ "refresh_token": "izzyr_…" }</pre><p>Si se reutiliza un token ya rotado, el servidor revoca la familia de sesión.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-get">GET</span><strong>/auth/me.php</strong><button type="button" class="api-auth-tag auth-bearer" data-api-info="Requiere enviar el access token vigente en el encabezado Authorization: Bearer.">Bearer requerido</button></div>
                <small>Consulta el usuario, empresa, plan y permisos vigentes en el servidor.</small>
                <details><summary>Ver encabezado</summary><pre>Authorization: Bearer &lt;access_token&gt;</pre><p>Si la cuenta se desactiva o el token vence/revoca, responde HTTP 401.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-post">POST</span><strong>/auth/logout.php</strong><button type="button" class="api-auth-tag auth-either" data-api-info="Puede enviar un access token vigente como Bearer o el refresh token cuando el access ya venció.">Bearer o refresh</button></div>
                <small>Revoca access y refresh tokens de la sesión actual.</small>
                <details><summary>Ver formas de cierre</summary><pre>Authorization: Bearer &lt;access_token&gt;</pre><p>Si el access token ya venció, también acepta <code>{ "refresh_token": "izzyr_…" }</code> como JSON.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-get">GET</span><strong>/navigation/menus.php</strong><button type="button" class="api-auth-tag auth-bearer" data-api-info="Requiere enviar el access token vigente en Authorization: Bearer. El servidor limita el menú por plan y privilegio.">Bearer requerido</button></div>
                <small>Devuelve menús, submenús y nivel 2 permitidos por plan y privilegio.</small>
                <details><summary>Ver encabezado y reglas</summary><pre>Authorization: Bearer &lt;access_token&gt;</pre><p>El móvil puede usar la respuesta para dibujar la navegación; el servidor debe volver a validar los permisos en cada endpoint funcional.</p></details>
            </article>
        </div>
        <section class="api-mobile-flow" aria-labelledby="api_mobile_flow_title">
            <div><span class="api-eyebrow">ORDEN DE CONSUMO</span><h3 id="api_mobile_flow_title">Flujo recomendado para la app</h3>
                <p>La aplicación conserva los tokens de forma segura en el dispositivo y solicita cada recurso protegido con el access token vigente.</p></div>
            <ol>
                <li><strong>Iniciar sesión</strong><span>Enviar usuario/correo y contraseña a <code>auth/login.php</code>.</span></li>
                <li><strong>Cargar perfil y menú</strong><span>Consultar <code>auth/me.php</code> y <code>navigation/menus.php</code> con Bearer.</span></li>
                <li><strong>Renovar al vencer</strong><span>Enviar el refresh token a <code>auth/refresh.php</code>, guardar el nuevo par y reintentar la llamada.</span></li>
                <li><strong>Cerrar sesión</strong><span>Llamar <code>auth/logout.php</code> y borrar ambos tokens del dispositivo.</span></li>
            </ol>
        </section>
        <div class="api-footnote"><i class="fas fa-info-circle"></i> <code>izzy_apiv1</code> identifica la versión; no es una clave de autenticación. Las rutas de negocio se irán agregando con autorización por usuario, plan y privilegio.</div>
    </section>

    <section class="api-panel" data-api-panel="sessions">
        <div class="api-panel-heading"><div><h2>Sesiones de la aplicación móvil</h2><p>Revocar una sesión también invalida su refresh token asociado.</p></div>
            <button type="button" class="btn btn-primary" id="api_refresh_sessions"><i class="fas fa-sync-alt"></i> Actualizar</button></div>
        <div class="api-session-list" id="api_sessions_rows" aria-live="polite"><div class="api-loading">Abra esta pestaña para cargar las sesiones.</div></div>
        <div class="api-footnote"><i class="fas fa-clock"></i> Se muestran las últimas 100 sesiones emitidas por <code>api/v1/auth/login.php</code>. Revocar aquí invalida también la renovación automática del dispositivo.</div>
    </section>

    <div class="api-modal-backdrop" id="api_integration_modal" aria-hidden="true">
        <section class="api-modal" role="dialog" aria-modal="true" aria-labelledby="api_integration_modal_title">
            <header><div><span class="api-eyebrow">CREDENCIAL DE SERVIDOR</span><h2 id="api_integration_modal_title">Nueva integración</h2></div><button type="button" class="api-modal-close" data-api-modal-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
            <form id="api_integration_form" autocomplete="off">
                <label for="api_integration_name">Nombre de la integración</label>
                <input id="api_integration_name" name="name" type="text" maxlength="100" placeholder="Ej. Sistema de reportes" required>
                <label for="api_integration_expiry">Vencimiento</label>
                <select id="api_integration_expiry" name="expires_in" data-placeholder="Seleccione vencimiento" required><option value="">Seleccione vencimiento</option><option value="30">30 días</option><option value="90">90 días</option><option value="365">1 año</option><option value="0">Sin vencimiento</option></select>
                <p>El Client ID y el secreto aparecerán una sola vez; guárdelos en un servidor. El registro no habilita acceso hasta que publiquemos rutas servidor a servidor con sus alcances.</p>
                <div class="api-modal-actions"><button type="button" class="btn btn-secondary" data-api-modal-close>Cancelar</button><button type="submit" class="btn btn-primary" id="api_create_integration"><i class="fas fa-key"></i> Crear credenciales</button></div>
            </form>
        </section>
    </div>
    <div class="api-modal-backdrop" id="api_secret_modal" aria-hidden="true">
        <section class="api-modal api-secret-modal" role="dialog" aria-modal="true" aria-labelledby="api_secret_title">
            <header><div><span class="api-eyebrow">GUARDE ESTOS DATOS AHORA</span><h2 id="api_secret_title">Integración creada</h2></div><button type="button" class="api-modal-close" data-api-secret-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
            <div class="api-secret-content"><p>El secreto solo se muestra en este momento. Si lo pierde, revoque esta credencial y cree otra.</p><label>Client ID</label><div class="api-copy-field"><code id="api_created_client_id"></code><button type="button" data-copy-api-value="client"><i class="far fa-copy"></i></button></div><label>Client Secret</label><div class="api-copy-field"><code id="api_created_client_secret"></code><button type="button" data-copy-api-value="secret"><i class="far fa-copy"></i></button></div></div>
            <div class="api-modal-actions"><button type="button" class="btn btn-primary" data-api-secret-close><i class="fas fa-check"></i> Entendido</button></div>
        </section>
    </div>
</div>
