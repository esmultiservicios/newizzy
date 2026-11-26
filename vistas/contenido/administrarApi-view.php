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
            <p>Accesos entrantes, sesiones móviles y administración de conexiones salientes con proveedores externos.</p></div>
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
        <button class="api-tab" type="button" data-api-tab="external"><i class="fas fa-paper-plane"></i> Servicios externos</button>
        <button class="api-tab" type="button" data-api-tab="authorizations"><i class="fas fa-user-shield"></i> Empresas autorizadas</button>
        <button class="api-tab" type="button" data-api-tab="routes"><i class="fas fa-code"></i> Rutas API</button>
        <button class="api-tab" type="button" data-api-tab="sessions"><i class="fas fa-mobile-alt"></i> Sesiones móviles</button>
    </div>

    <section class="api-panel is-active" data-api-panel="integrations">
        <div class="api-panel-heading"><div><h2>Accesos externos a IZZY</h2><p>Autorice consultas o escrituras permitidas para una empresa IZZY; cada credencial queda ligada a una sola empresa.</p></div>
            <button type="button" class="btn btn-primary" id="api_new_integration"><i class="fas fa-plus"></i> Nuevo acceso externo</button></div>
        <div class="api-integration-directions"><article><i class="fas fa-arrow-right"></i><div><strong>De otro sistema hacia IZZY</strong><span>Estas credenciales permiten que un sistema externo solicite tokens para una empresa autorizada.</span></div></article><article><i class="fas fa-arrow-left"></i><div><strong>De IZZY hacia un proveedor externo</strong><span>Las conexiones salientes se administran en «Servicios externos». Por ahora se permite configurar y comprobar ZYNKO, sin activar envíos documentales.</span></div></article></div>
        <div class="api-integration-notice"><i class="fas fa-info-circle"></i><span>Estas credenciales identifican integraciones servidor a servidor. Las rutas móviles de login, refresh, perfil, cierre y menús siguen usando tokens individuales de usuario.</span></div>
        <div class="api-integration-quickstart"><div><strong><i class="fas fa-link"></i> URL para obtener token</strong><code><?php echo htmlspecialchars($apiBase,ENT_QUOTES,'UTF-8'); ?>integrations/token.php</code></div><div><strong><i class="fas fa-shield-alt"></i> URL para comprobar integración</strong><code><?php echo htmlspecialchars($apiBase,ENT_QUOTES,'UTF-8'); ?>integrations/me.php</code></div><p>Ambas direcciones son comunes; IZZY identifica automáticamente la empresa por su credencial. Nunca se envía un nombre de base de datos desde la integración.</p></div>
        <div class="api-integration-quickstart">
            <?php require_once dirname(__DIR__, 2).'/core/api/ApiModuleCatalog.php'; foreach (IzzyApiModuleCatalog::items() as $apiModule): $apiKey=htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>
            <div><strong><i class="<?php echo htmlspecialchars($apiModule['icono'],ENT_QUOTES,'UTF-8'); ?>"></i> <?php echo htmlspecialchars($apiModule['nombre'],ENT_QUOTES,'UTF-8'); ?></strong><span class="api-inline-method">GET · <?php echo $apiKey; ?>:read</span><code><?php echo htmlspecialchars($apiBase,ENT_QUOTES,'UTF-8'); ?>data/<?php echo $apiKey; ?>.php</code><?php if(in_array($apiModule['clave'],['clientes','proveedores'],true)): ?><span class="api-inline-method is-write">POST / PATCH · <?php echo $apiKey; ?>:write</span><small>POST para crear · PATCH con <code>?id=N</code> para editar · Cuerpo JSON</small><?php endif; ?></div>
            <?php endforeach; ?>
            <p>Las rutas son comunes para APK e integraciones autorizadas; el token determina la empresa. Solo clientes y proveedores admiten POST y PATCH; facturas, productos, empresa y secuencias continúan como consulta GET. Ninguna lectura de secuencia reserva números fiscales.</p>
        </div>
        <div class="api-results-toolbar" data-api-results="integrations" aria-label="Herramientas de resultados">
<div class="api-results-action"><button type="button" class="btn btn-secondary api-export-excel" title="Exportar resultados a Excel"><i class="fas fa-file-excel"></i> Excel</button><button type="button" class="btn btn-secondary api-export-pdf" title="Visualizar PDF"><i class="fas fa-file-pdf"></i> PDF</button></div>
<div class="api-results-view" role="group" aria-label="Vista"><button type="button" class="api-view-option is-active" data-view="detail" aria-label="Vista detalle" title="Vista detalle"><i class="fas fa-list"></i><span>Detalle</span></button><button type="button" class="api-view-option" data-view="tiles" aria-label="Vista miniaturas" title="Vista miniaturas"><i class="fas fa-th-large"></i><span>Miniatura</span></button></div>
<div class="api-results-size-label">Mostrar <div class="api-size-control"><button type="button" class="api-size-toggle" aria-label="Cantidad de registros" aria-haspopup="listbox" aria-expanded="false"><span class="api-size-current">10</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button><div class="api-size-menu" role="listbox" aria-label="Registros por página" hidden><button type="button" role="option" data-size="10" aria-selected="true">10</button><button type="button" role="option" data-size="25">25</button><button type="button" role="option" data-size="50">50</button><button type="button" role="option" data-size="all">Todos (0)</button></div></div> registros</div>
<label class="api-results-search" aria-label="Buscar integraciones"><span class="api-search-icon"><i class="fas fa-search"></i></span><input type="search" class="api-results-query" placeholder="Buscar integraciones…"></label>
</div>
        <div class="api-integration-list" id="api_integrations_rows" aria-live="polite"><div class="api-loading">Cargando integraciones…</div></div>
        <nav class="api-results-pagination" data-api-pagination="integrations" aria-label="Paginación de integraciones"></nav>
        <div class="api-footnote"><i class="fas fa-lock"></i> El secreto no se guarda en texto legible. Las rutas protegidas para servidor se habilitarán conforme se publiquen sus endpoints y alcances.</div>
    </section>

    <section class="api-panel" data-api-panel="external">
        <div class="api-panel-heading"><div><h2>Servicios API externos</h2><p>Conexiones salientes de IZZY. Exclusivas del administrador principal; los tokens nunca se muestran en pantalla.</p></div><button type="button" class="btn btn-secondary" id="api_ext_diagnostics" title="Comprueba tablas y cifrado, sin exponer secretos"><i class="fas fa-stethoscope"></i> Diagnóstico</button><button type="button" class="btn btn-primary" id="api_ext_refresh"><i class="fas fa-sync"></i> Actualizar</button></div>
        <div class="api-integration-notice"><i class="fas fa-shield-alt"></i><span>Los envíos automáticos de documentos permanecen desactivados. Primero valide credenciales y rutas con el proveedor.</span></div>
        <h3 class="api-route-section-title">Conexiones registradas</h3>
        <div class="api-results-toolbar" data-api-results="ext" aria-label="Herramientas de resultados">
<div class="api-results-action"><button type="button" class="btn btn-primary" id="api_ext_new"><i class="fas fa-plus"></i> Nueva conexión</button><button type="button" class="btn btn-secondary api-export-excel" title="Exportar resultados a Excel"><i class="fas fa-file-excel"></i> Excel</button><button type="button" class="btn btn-secondary api-export-pdf" title="Visualizar PDF"><i class="fas fa-file-pdf"></i> PDF</button></div>
<div class="api-results-view" role="group" aria-label="Vista"><button type="button" class="api-view-option is-active" data-view="detail" aria-label="Vista detalle" title="Vista detalle"><i class="fas fa-list"></i><span>Detalle</span></button><button type="button" class="api-view-option" data-view="tiles" aria-label="Vista miniaturas" title="Vista miniaturas"><i class="fas fa-th-large"></i><span>Miniatura</span></button></div>
<div class="api-results-size-label">Mostrar <div class="api-size-control"><button type="button" class="api-size-toggle" aria-label="Cantidad de registros" aria-haspopup="listbox" aria-expanded="false"><span class="api-size-current">10</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button><div class="api-size-menu" role="listbox" aria-label="Registros por página" hidden><button type="button" role="option" data-size="10" aria-selected="true">10</button><button type="button" role="option" data-size="25">25</button><button type="button" role="option" data-size="50">50</button><button type="button" role="option" data-size="all">Todos (0)</button></div></div> registros</div>
<label class="api-results-search" aria-label="Buscar conexiones"><span class="api-search-icon"><i class="fas fa-search"></i></span><input type="search" class="api-results-query" placeholder="Buscar conexiones…"></label>
</div>
        <div id="api_ext_rows" class="api-integration-list" aria-live="polite"><div class="api-loading">Seleccione la pestaña para cargar las conexiones.</div></div>
        <nav class="api-results-pagination" data-api-pagination="ext" aria-label="Paginación de conexiones"></nav>
        <div id="api_ext_checks_section" hidden>
        <h3 class="api-route-section-title" id="api_ext_checks_heading">Historial técnico de comprobaciones</h3>
        <div class="api-check-toolbar"><label class="api-results-search" aria-label="Buscar comprobaciones"><span class="api-search-icon"><i class="fas fa-search"></i></span><input id="api_ext_checks_search" type="search" placeholder="Buscar comprobaciones..."></label><label class="api-check-size">Mostrar <select id="api_ext_checks_limit" data-izzy-page-size aria-label="Registros de comprobaciones por página"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="0">Todos</option></select> registros</label></div>
        <div id="api_ext_checks" class="api-integration-list"><div class="api-empty-state"><i class="fas fa-history"></i><strong>Sin comprobaciones registradas</strong><span>Seleccione «Historial» en una conexión para consultar los resultados técnicos.</span></div></div>
        <nav class="api-results-pagination api-check-pagination" aria-label="Paginación de comprobaciones"><span id="api_ext_checks_count" class="api-page-summary">Mostrando 0 a 0 de 0 registros</span><div id="api_ext_checks_pages" class="api-page-buttons"></div></nav>
        </div>
    </section>

    <section class="api-panel" data-api-panel="authorizations">
        <div class="api-panel-heading"><div><h2>Autorización de API por empresa</h2><p>Solo usted puede habilitar o suspender integraciones externas; sin aprobación no se emiten tokens.</p></div><button type="button" class="btn btn-primary" id="api_refresh_authorizations"><i class="fas fa-sync-alt"></i> Actualizar</button></div>
        <div class="api-authorizations-toolbar">
            
            <select id="api_authorizations_system" aria-label="Filtrar por sistema"><option value="1" selected>IZZY</option><option value="all">Todos los sistemas</option></select>
            <select id="api_authorizations_status" aria-label="Filtrar acceso API"><option value="active" selected>Empresas activas</option><option value="approved">Activas autorizadas</option><option value="denied">Activas no autorizadas</option><option value="inactive">Empresas inactivas</option><option value="all">Todas las empresas</option></select>
        </div>
        <div class="api-authorizations-count" id="api_authorizations_count" aria-live="polite"></div>
        <div class="api-results-toolbar" data-api-results="authorizations" aria-label="Herramientas de resultados">
<div class="api-results-action"><button type="button" class="btn btn-secondary api-export-excel" title="Exportar resultados a Excel"><i class="fas fa-file-excel"></i> Excel</button><button type="button" class="btn btn-secondary api-export-pdf" title="Visualizar PDF"><i class="fas fa-file-pdf"></i> PDF</button></div>
<div class="api-results-view" role="group" aria-label="Vista"><button type="button" class="api-view-option is-active" data-view="detail" aria-label="Vista detalle" title="Vista detalle"><i class="fas fa-list"></i><span>Detalle</span></button><button type="button" class="api-view-option" data-view="tiles" aria-label="Vista miniaturas" title="Vista miniaturas"><i class="fas fa-th-large"></i><span>Miniatura</span></button></div>
<div class="api-results-size-label">Mostrar <div class="api-size-control"><button type="button" class="api-size-toggle" aria-label="Cantidad de registros" aria-haspopup="listbox" aria-expanded="false"><span class="api-size-current">10</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button><div class="api-size-menu" role="listbox" aria-label="Registros por página" hidden><button type="button" role="option" data-size="10" aria-selected="true">10</button><button type="button" role="option" data-size="25">25</button><button type="button" role="option" data-size="50">50</button><button type="button" role="option" data-size="all">Todos (0)</button></div></div> registros</div>
<label class="api-results-search" aria-label="Buscar empresas"><span class="api-search-icon"><i class="fas fa-search"></i></span><input type="search" class="api-results-query" placeholder="Buscar empresas…"></label>
</div>
        <div class="api-integration-list" id="api_authorizations_rows"><div class="api-loading">Cargando empresas…</div></div>
        <nav class="api-results-pagination" data-api-pagination="authorizations" aria-label="Paginación de empresas"></nav>
    </section>

    <section class="api-panel" data-api-panel="routes">
        <div class="api-panel-heading"><div><h2>Directorio completo de API v1</h2><p>Rutas de la APK, credenciales externas y recursos compartidos con permisos independientes.</p></div>
            <span class="api-status is-active"><i class="fas fa-code-branch"></i> izzy_apiv1</span></div>
        <div class="api-base-url"><span>Base URL</span><code><?php echo htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8'); ?></code></div>
        <div class="api-token-lifecycle" aria-label="Duración de tokens">
            <div><i class="fas fa-key"></i><span>Access token<strong>60 minutos</strong></span></div>
            <div><i class="fas fa-sync-alt"></i><span>Refresh token<strong>30 días, con rotación</strong></span></div>
            <div><i class="fas fa-database"></i><span>Almacenamiento<strong>Solo hash en DB_MAIN</strong></span></div>
        </div>
        <h3 class="api-route-section-title"><i class="fas fa-mobile-alt"></i> Autenticación y navegación de la APK</h3>
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
                <details><summary>Ver solicitud</summary><p><strong>¿Cuándo se utiliza?</strong> Cuando caduca el access token de la APK, esta ruta permite pedir nuevos tokens sin solicitar otra vez la contraseña.</p><pre>{
  "refresh_token": "TOKEN_REFRESH_ACTUAL"
}</pre><p><strong>Importante:</strong> cada refresh token solo puede usarse una vez. Al renovarlo, la APK debe sustituir el anterior por el nuevo. Si alguien intenta reutilizar uno que ya fue reemplazado, IZZY revoca los tokens asociados a esa sesión por seguridad y el usuario deberá iniciar sesión otra vez.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-get">GET</span><strong>/auth/me.php</strong><button type="button" class="api-auth-tag auth-bearer" data-api-info="Requiere enviar el access token vigente en el encabezado Authorization: Bearer.">Bearer requerido</button></div>
                <small>Consulta el usuario, empresa, plan y permisos vigentes en el servidor.</small>
                <details><summary>Ver encabezado</summary><pre>Authorization: Bearer &lt;access_token&gt;</pre><p>Si la cuenta se desactiva o el token vence/revoca, responde HTTP 401.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-post">POST</span><strong>/auth/logout.php</strong><button type="button" class="api-auth-tag auth-either" data-api-info="Puede enviar un access token vigente como Bearer o el refresh token cuando el access ya venció.">Bearer o refresh</button></div>
                <small>Revoca access y refresh tokens de la sesión actual.</small>
                <details><summary>Ver formas de cierre</summary><p><strong>Opción 1 · Token de acceso vigente.</strong> Envíe la solicitud POST incluyendo el encabezado:</p><pre>Authorization: Bearer TOKEN_DE_ACCESO</pre><p><strong>Opción 2 · Token de acceso vencido.</strong> La APK puede cerrar la sesión enviando el refresh token actual en el cuerpo JSON:</p><pre>{
  "refresh_token": "TOKEN_REFRESH_ACTUAL"
}</pre><p>En cualquiera de las dos opciones, después de la respuesta la APK debe borrar los tokens guardados localmente. El servidor invalida los tokens de esa sesión. No introduzca el texto del ejemplo literalmente: utilice el valor real recibido al iniciar sesión o renovar.</p></details>
            </article>
            <article class="api-route-card api-route-detail">
                <div class="api-route-top"><span class="api-method method-get">GET</span><strong>/navigation/menus.php</strong><button type="button" class="api-auth-tag auth-bearer" data-api-info="Requiere enviar el access token vigente en Authorization: Bearer. El servidor limita el menú por plan y privilegio.">Bearer requerido</button></div>
                <small>Devuelve menús, submenús y nivel 2 permitidos por plan y privilegio.</small>
                <details><summary>Ver encabezado y reglas</summary><pre>Authorization: Bearer &lt;access_token&gt;</pre><p>El móvil puede usar la respuesta para dibujar la navegación; el servidor debe volver a validar los permisos en cada endpoint funcional.</p></details>
            </article>
        </div>
        <h3 class="api-route-section-title"><i class="fas fa-plug"></i> Autenticación de integraciones externas</h3>
        <div class="api-route-grid">
            <article class="api-route-card api-resource-card"><div class="api-route-top"><span class="api-method method-post">POST</span><strong>/integrations/token.php</strong><span class="api-auth-tag auth-public">Client ID + Secret</span></div><small>Genera el Bearer para un servidor externo autorizado. El secreto no se incluye en la APK.</small><details class="api-route-write-example"><summary>Ver solicitud y respuesta</summary><p>Solo debe llamarse desde un servidor seguro de la integración.</p><pre>{"client_id":"SU_CLIENT_ID","client_secret":"SU_CLIENT_SECRET"}</pre><p>Enviar <code>Content-Type: application/json</code>. Conserve el token obtenido en el servidor; nunca publique el secreto en una APK.</p></details></article>
            <article class="api-route-card api-resource-card"><div class="api-route-top"><span class="api-method method-get">GET</span><strong>/integrations/me.php</strong><span class="api-auth-tag auth-bearer">Bearer externo</span></div><small>Comprueba la integración, empresa vinculada y sus alcances vigentes.</small><details class="api-route-write-example"><summary>Ver encabezado y permisos</summary><pre>Authorization: Bearer TOKEN_DE_INTEGRACION</pre><p>La respuesta identifica la integración, su empresa autorizada y los alcances concedidos. Los alcances <code>clientes:write</code> y <code>proveedores:write</code> son independientes de los permisos de consulta.</p></details></article>
        </div>
        <h3 class="api-route-section-title"><i class="fas fa-database"></i> Recursos de datos para APK e integraciones</h3>
        <div class="api-route-grid">
            <?php
            require_once dirname(__DIR__, 2).'/core/api/ApiModuleCatalog.php';
            foreach (IzzyApiModuleCatalog::items() as $apiModule):
            ?>
            <article class="api-route-card api-resource-card">
                <div class="api-route-top"><span class="api-method method-get">GET</span><strong>/data/<?php echo htmlspecialchars($apiModule['clave'], ENT_QUOTES, 'UTF-8'); ?>.php</strong><span class="api-auth-tag auth-bearer">Bearer + permisos</span></div>
                <small><?php echo htmlspecialchars($apiModule['descripcion'], ENT_QUOTES, 'UTF-8'); ?> Alcance: <code><?php echo htmlspecialchars($apiModule['clave'], ENT_QUOTES, 'UTF-8'); ?>:read</code>.</small>
                <div class="api-route-params"><i class="fas fa-filter"></i> GET: <code>?pagina=1&amp;limite=25</code> o <code>?id=N</code>.</div>
                <?php if(in_array($apiModule['clave'],['clientes','proveedores'],true)): ?>
                <div class="api-route-write"><span class="api-method method-post">POST</span><span class="api-method method-patch">PATCH</span><strong>Crear / editar</strong><small>Alcance <code><?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>:write</code></small></div>
                <details class="api-route-write-example"><summary>Ver solicitudes GET, POST y PATCH</summary><p><strong>GET:</strong> consulta por página o ID; usa el alcance <code><?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>:read</code>.</p><pre>GET /data/<?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>.php?pagina=1&amp;limite=25</pre><p><strong>POST:</strong> crea sin ID. <strong>PATCH:</strong> edita con <code>?id=N</code>. Ambos requieren <code><?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>:write</code> para integraciones externas.</p><pre>Authorization: Bearer TOKEN
Content-Type: application/json

{"nombre":"Registro de prueba","rtn":"","telefono":""}</pre><p>En PATCH envíe solo los campos que desea cambiar. Primero valide las escrituras en DEMO.</p></details>
                <?php else: ?><small class="api-route-readonly">Solo consulta GET · Escritura pendiente de reglas de negocio.</small><details class="api-route-write-example"><summary>Ver solicitud y respuesta</summary><pre>GET /data/<?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>.php?pagina=1&amp;limite=25
Authorization: Bearer TOKEN</pre><p>Para consultar un registro use <code>?id=N</code>. Se aplica el permiso <code><?php echo htmlspecialchars($apiModule['clave'],ENT_QUOTES,'UTF-8'); ?>:read</code>. La respuesta JSON incluye <code>ok</code>, <code>mensaje</code> y <code>data</code>. No se permite escritura en este módulo.</p></details><?php endif; ?>
            </article>
            <?php endforeach; ?>
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
        <div class="api-results-toolbar" data-api-results="sessions" aria-label="Herramientas de resultados">
<div class="api-results-action"><button type="button" class="btn btn-secondary api-export-excel" title="Exportar resultados a Excel"><i class="fas fa-file-excel"></i> Excel</button><button type="button" class="btn btn-secondary api-export-pdf" title="Visualizar PDF"><i class="fas fa-file-pdf"></i> PDF</button></div>
<div class="api-results-view" role="group" aria-label="Vista"><button type="button" class="api-view-option is-active" data-view="detail" aria-label="Vista detalle" title="Vista detalle"><i class="fas fa-list"></i><span>Detalle</span></button><button type="button" class="api-view-option" data-view="tiles" aria-label="Vista miniaturas" title="Vista miniaturas"><i class="fas fa-th-large"></i><span>Miniatura</span></button></div>
<div class="api-results-size-label">Mostrar <div class="api-size-control"><button type="button" class="api-size-toggle" aria-label="Cantidad de registros" aria-haspopup="listbox" aria-expanded="false"><span class="api-size-current">10</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button><div class="api-size-menu" role="listbox" aria-label="Registros por página" hidden><button type="button" role="option" data-size="10" aria-selected="true">10</button><button type="button" role="option" data-size="25">25</button><button type="button" role="option" data-size="50">50</button><button type="button" role="option" data-size="all">Todos (0)</button></div></div> registros</div>
<label class="api-results-search" aria-label="Buscar sesiones"><span class="api-search-icon"><i class="fas fa-search"></i></span><input type="search" class="api-results-query" placeholder="Buscar sesiones…"></label>
</div>
        <div class="api-session-list" id="api_sessions_rows" aria-live="polite"><div class="api-loading">Abra esta pestaña para cargar las sesiones.</div></div>
        <nav class="api-results-pagination" data-api-pagination="sessions" aria-label="Paginación de sesiones"></nav>
        <div class="api-footnote"><i class="fas fa-clock"></i> Se muestran las últimas 100 sesiones emitidas por <code>api/v1/auth/login.php</code>. Revocar aquí invalida también la renovación automática del dispositivo.</div>
    </section>

    <dialog id="api_wa_dialog" class="izzy-dialog api-wa-dialog" aria-labelledby="api_wa_title">
    <header class="izzy-dialog__header">
        <div class="izzy-dialog__heading">
            <h2 class="izzy-dialog__title" id="api_wa_title"><i class="fab fa-whatsapp mr-2" aria-hidden="true"></i>Prueba de WhatsApp</h2>
            <p class="izzy-dialog__subtitle" id="api_wa_connection"></p>
        </div>
        <button type="button" class="izzy-dialog__close" data-izzy-dialog-close aria-label="Cerrar"><i class="fas fa-times" aria-hidden="true"></i></button>
    </header>
    <div class="izzy-dialog__body">
        <p class="api-wa-note" id="api_wa_server"></p>
        <p class="api-wa-note">Use solo destinatarios de prueba autorizados en el servidor. Si el resultado es incierto, reintente la misma operación desde el historial para evitar duplicados.</p>
        <form id="api_wa_form" class="api-wa-form">
            <fieldset class="api-wa-mode">
                <legend class="izzy-label">Modo de envío</legend>
                <label><input type="radio" name="mode" value="single" checked> Individual</label>
                <label><input type="radio" name="mode" value="bulk"> Masivo</label>
            </fieldset>
            <div class="izzy-field">
                <label class="izzy-label" for="api_wa_recipients">Destinatarios internacionales</label>
                <textarea class="form-control izzy-textarea" id="api_wa_recipients" name="recipients" rows="2" maxlength="1900" aria-describedby="api_wa_recipients_hint" required></textarea>
                <small class="izzy-field-hint" id="api_wa_recipients_hint">Ingrese un número completo con código de país.</small>
            </div>
            <div class="izzy-field">
                <label class="izzy-label" for="api_wa_message">Mensaje</label>
                <textarea class="form-control izzy-textarea" id="api_wa_message" name="message" rows="3" maxlength="4096" required></textarea>
            </div>
            <div class="izzy-field">
                <label class="izzy-label" for="api_wa_consent_reference">Referencia de autorización de los destinatarios</label>
                <input class="form-control izzy-input" id="api_wa_consent_reference" name="consent_reference" maxlength="200" placeholder="Ej. Referencia del registro interno de autorizaciones" required>
            </div>
            <label class="api-wa-consent"><input type="checkbox" name="consent" value="1" required> Confirmo que todos los destinatarios autorizaron esta prueba de WhatsApp.</label>
        </form>
        <p class="api-wa-feedback" id="api_wa_feedback" role="status"></p>
        <section aria-labelledby="api_wa_history_title">
            <h3 class="api-route-section-title" id="api_wa_history_title">Historial de envíos de prueba</h3>
            <div class="api-integration-list" id="api_wa_history"></div>
        </section>
    </div>
    <footer class="izzy-dialog__footer">
        <button type="button" class="izzy-button" data-izzy-dialog-close><i class="fas fa-times" aria-hidden="true"></i>Cerrar</button>
        <button type="submit" form="api_wa_form" class="izzy-button izzy-button--primary" id="api_wa_submit"><i class="fas fa-paper-plane" aria-hidden="true"></i>Enviar prueba</button>
    </footer>
</dialog>

<div class="api-modal-backdrop" id="api_external_modal" aria-hidden="true">
        <section class="api-modal api-external-modal" role="dialog" aria-modal="true" aria-labelledby="api_external_modal_title">
            <header><div><span class="api-eyebrow">IZZY → PROVEEDOR EXTERNO</span><h2 id="api_external_modal_title"><i class="fas fa-plug"></i> Nueva conexión externa</h2><p class="api-modal-subtitle">Configure una conexión saliente protegida con un proveedor externo.</p></div><button type="button" class="api-modal-close" data-api-external-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
        <form id="api_ext_form" class="api-external-form" autocomplete="off">
            <input type="hidden" name="id" id="api_ext_id" value="0">
            <label>Nombre<input type="text" name="nombre" id="api_ext_name" required maxlength="100" placeholder="Ej. Proveedor de SMS"></label>
            <label>Proveedor<input type="text" name="proveedor" id="api_ext_provider" required maxlength="50" placeholder="Ej. ZYNKO, Proveedor SMS"></label>
            <label>Categoría
                <select name="categoria" id="api_ext_category" class="api-enhanced-select" required aria-label="Categoría del proveedor">
                    <option value=""></option>
                </select>
            </label>
            <label>URL HTTPS<input type="url" name="url_base" id="api_ext_url" required maxlength="255" placeholder="https://api.proveedor.com/v1"></label>
            <label>Autenticación<select name="tipo_auth" id="api_ext_auth" class="api-enhanced-select" required><option value="">Seleccione autenticación</option><option value="bearer">Bearer Token</option><option value="api_key">API Key</option><option value="basic">Basic Auth</option><option value="none">Sin autenticación</option></select></label>
            <label id="api_ext_channel_group">Identificador del recurso (solo cuando sea necesario)<input type="text" name="canal_id" id="api_ext_channel" maxlength="120" placeholder="Opcional, si el proveedor no permite descubrirlo"></label>
            <div class="api-ext-wide api-provider-guide"><i class="fas fa-shield-alt"></i><div><strong>Parámetros del proveedor</strong><small>Las operaciones se administran por conexión en «Operaciones API». ZYNKO utiliza su canal asociado a la credencial y se consulta mediante <code>v1/channels</code>, sin introducir un ID manual. Nunca guarde secretos en parámetros JSON.</small></div></div>
            <label class="api-ext-wide">Parámetros adicionales (JSON, sin secretos)<textarea name="configuracion_json" id="api_ext_config" rows="3" maxlength="4000" spellcheck="false" placeholder='{"sender_id":"ejemplo","region":"hn"}'>{}</textarea><small class="api-editor-note">Opcional. Solo configuraciones públicas/no secretas; las operaciones declarativas se configuran por separado. No se ejecutan automáticamente.</small></label>
            <div class="api-ext-wide api-ext-editor-field"><label for="api_ext_description_editor">Descripción</label>
                <div class="api-rich-editor" role="group" aria-label="Editor enriquecido de descripción">
                    <div class="api-rich-toolbar" role="toolbar" aria-label="Formato de descripción">
                        <button type="button" data-api-format="bold" aria-label="Negrita" title="Negrita"><i class="fas fa-bold"></i></button>
                        <button type="button" data-api-format="italic" aria-label="Cursiva" title="Cursiva"><i class="fas fa-italic"></i></button>
                        <button type="button" data-api-format="insertUnorderedList" aria-label="Lista de viñetas" title="Lista de viñetas"><i class="fas fa-list-ul"></i></button>
                        <button type="button" data-api-format="insertOrderedList" aria-label="Lista numerada" title="Lista numerada"><i class="fas fa-list-ol"></i></button>
                        <button type="button" data-api-format="removeFormat" aria-label="Quitar formato" title="Quitar formato"><i class="fas fa-eraser"></i></button>
                    </div>
                    <div id="api_ext_description_editor" class="api-rich-content" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="Propósito de esta integración"></div>
                    <input type="hidden" name="descripcion" id="api_ext_description" value="">
                </div><small class="api-editor-note">Puede usar negrita, cursiva y listas. Máximo 500 caracteres de contenido almacenado.</small></div>
            <label class="api-ext-wide">Credencial secreta (según autenticación)<input type="password" name="token" id="api_ext_token" autocomplete="new-password" placeholder="Obligatorio para crear; vacío conserva el existente"></label>
            <div class="api-ext-actions"><button type="button" class="btn btn-secondary api-cancel-button" data-api-external-close><i class="fas fa-times-circle"></i> Cancelar</button><button type="button" class="btn btn-warning api-clear-button" id="api_ext_clear"><i class="fas fa-eraser"></i> Limpiar</button><button type="submit" form="api_ext_form" class="btn btn-primary"><i class="fas fa-save"></i> Guardar conexión</button></div>
        </form>
        </section>
    </div>
    <div class="api-modal-backdrop" id="api_operations_modal" aria-hidden="true">
      <section class="api-modal api-external-modal" role="dialog" aria-modal="true" aria-labelledby="api_operations_title">
        <header><div><span class="api-eyebrow">IZZY → OPERACIONES API</span><h2 id="api_operations_title"><i class="fas fa-cogs"></i> Operaciones del proveedor</h2><p class="api-modal-subtitle">Configure las operaciones disponibles del proveedor. Los envíos permanecen desactivados.</p></div><button class="api-modal-close" type="button" data-api-operations-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
        <div class="api-operations-scroll">
          <div id="api_operations_list" class="api-integration-list"><div class="api-empty-state"><i class="fas fa-cogs"></i><strong>Sin operaciones registradas</strong><span>Agregue una operación o cargue un catálogo preconfigurado.</span></div></div>
          <div class="api-operations-actions"><button type="button" class="btn btn-primary" id="api_operations_seed"><i class="fas fa-cloud-download-alt"></i> Cargar operaciones ZYNKO</button></div>
          <form id="api_operation_form" class="api-operation-grid" autocomplete="off">
            <input type="hidden" id="api_operation_connection" name="connection_id"><input type="hidden" id="api_operation_id" name="operation_id" value="0">
            <label>Identificador interno<input required id="api_operation_code" name="codigo" maxlength="80" placeholder="enviar_mensaje"></label>
            <label>Nombre de la operación<input required id="api_operation_name" name="nombre" maxlength="120" placeholder="Enviar mensaje"></label>
            <label>Método HTTP<select id="api_operation_method" name="metodo" required><option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option></select></label>
            <label>Ruta relativa<input required id="api_operation_route" name="ruta" maxlength="512" placeholder="/messages o ?r=v1/channels"></label>
            <label class="api-ext-wide">Parámetros estructurados (JSON, sin secretos)<textarea name="parametros_json" id="api_operation_params" rows="3">{}</textarea></label>
            <label class="api-ext-wide">Respuesta esperada (JSON, sin secretos)<textarea name="respuesta_json" id="api_operation_response" rows="3">{}</textarea></label>
            <label class="api-ext-wide">Descripción<textarea name="descripcion" id="api_operation_description" rows="2" maxlength="2000"></textarea></label>
            <div class="api-ext-wide api-operations-actions"><button type="reset" class="btn btn-warning"><i class="fas fa-eraser"></i> Limpiar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar operación</button></div>
          </form>
          <div class="api-operations-import"><h3><i class="fas fa-file-import"></i> Importar OpenAPI</h3><p>Vista previa segura para OpenAPI 3 en JSON. No realiza conexiones ni importa automáticamente operaciones.</p><textarea id="api_openapi_json" rows="3" placeholder='{"openapi":"3.0.0","paths":{}}'></textarea><button type="button" class="btn btn-secondary" id="api_openapi_preview"><i class="fas fa-search"></i> Analizar especificación</button><div id="api_openapi_result"></div></div>
        </div>
        <footer class="api-operations-footer"><button type="button" class="btn btn-secondary" data-api-operations-close><i class="fas fa-times-circle"></i> Cerrar</button></footer>
      </section>
    </div>
    <div class="api-modal-backdrop" id="api_integration_modal" aria-hidden="true">
        <section class="api-modal" role="dialog" aria-modal="true" aria-labelledby="api_integration_modal_title">
            <header><div><span class="api-eyebrow">OTRO SISTEMA → IZZY</span><h2 id="api_integration_modal_title"><i class="fas fa-key"></i> Nuevo acceso externo a IZZY</h2><p class="api-modal-subtitle">Autorice a un sistema externo para acceder únicamente a su empresa.</p></div><button type="button" class="api-modal-close" data-api-modal-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
            <form id="api_integration_form" autocomplete="off">
                <div class="api-modal-form-body">
                <div class="api-integration-purpose"><i class="fas fa-network-wired"></i><span><strong>Acceso de un sistema externo a IZZY</strong><small>Para un cliente autorizado. No es para configurar pasarelas de pagos, SMS o WhatsApp de otros proveedores.</small></span></div>
                <div class="api-integration-form-grid">
                    <div class="api-integration-field api-integration-field-company"><label for="api_integration_customer"><i class="fas fa-building"></i> Empresa autorizada</label><select id="api_integration_customer" required><option value="">Seleccione una empresa</option></select></div>
                    <div class="api-no-authorized-hint" id="api_no_authorized_hint" hidden><i class="fas fa-info-circle"></i><span>No hay empresas activas con acceso API autorizado. Primero autorice al cliente en <strong>Empresas autorizadas</strong>.</span><button type="button" class="btn btn-primary btn-sm" id="api_go_authorizations"><i class="fas fa-user-shield"></i> Autorizar empresa</button></div>
                    <div class="api-integration-field"><label for="api_integration_name"><i class="fas fa-tag"></i> Nombre de la integración</label><input class="api-integration-text-input" id="api_integration_name" name="name" type="text" maxlength="100" placeholder="Ej. Sistema de reportes" required></div>
                    <div class="api-integration-field"><label for="api_integration_expiry"><i class="fas fa-calendar-alt"></i> Vencimiento</label><select id="api_integration_expiry" name="expires_in" data-placeholder="Seleccione vencimiento" required><option value="">Seleccione vencimiento</option><option value="30">30 días</option><option value="90">90 días</option><option value="365">1 año</option><option value="0">Sin vencimiento</option></select></div>
                </div>
                <label>Alcances autorizados <small>Lectura y escritura controlada para API v1</small></label>
                <div class="api-scopes">
                    <?php
                    require_once dirname(__DIR__, 2).'/core/api/ApiModuleCatalog.php';
                    foreach (IzzyApiModuleCatalog::items() as $apiModule):
                        $checked = in_array($apiModule['clave'], ['clientes', 'productos'], true);
                    ?>
                    <label><input type="checkbox" name="api_scope" value="<?php echo htmlspecialchars($apiModule['clave'].':read', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked ? 'checked' : ''; ?>><i class="<?php echo htmlspecialchars($apiModule['icono'], ENT_QUOTES, 'UTF-8'); ?>"></i><span><?php echo htmlspecialchars($apiModule['nombre'], ENT_QUOTES, 'UTF-8'); ?></span><small>Consultar</small></label>
                    <?php if (in_array($apiModule['clave'], ['clientes', 'proveedores'], true)): ?>
                    <label><input type="checkbox" name="api_scope" value="<?php echo htmlspecialchars($apiModule['clave'].':write', ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-pen"></i><span><?php echo htmlspecialchars($apiModule['nombre'], ENT_QUOTES, 'UTF-8'); ?></span><small>Crear y editar</small></label>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <aside class="api-credentials-alert" role="note"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><div><strong>Importante · Guarde las credenciales al crearlas</strong><p>El Client ID y el Client Secret se muestran una sola vez. Conserve el secreto únicamente en el servidor de su integración, nunca en la APK ni en código público.</p><p><i class="fas fa-lock"></i> Las escrituras de clientes y proveedores requieren marcar expresamente el permiso «Crear y editar». Las credenciales existentes no reciben nuevos permisos automáticamente.</p></div></aside>
                </div>
                <div class="api-modal-actions"><button type="button" class="btn btn-secondary" data-api-modal-close><i class="fas fa-times-circle"></i> Cancelar</button><button type="submit" class="btn btn-primary" id="api_create_integration"><i class="fas fa-key"></i> Crear credenciales</button></div>
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
    <div class="api-modal-backdrop" id="api_route_modal" aria-hidden="true">
        <section class="api-modal api-route-modal" role="dialog" aria-modal="true" aria-labelledby="api_route_modal_title">
            <header><div><span class="api-eyebrow">DOCUMENTACIÓN API</span><h2 id="api_route_modal_title">Detalles de la solicitud</h2></div><button type="button" class="api-modal-close" data-api-route-close aria-label="Cerrar"><i class="fas fa-times"></i></button></header>
            <div class="api-route-modal-body" id="api_route_modal_body"></div>
            <div class="api-modal-actions"><button type="button" class="btn btn-primary" data-api-route-close><i class="fas fa-check"></i> Entendido</button></div>
        </section>
    </div>
    <div class="api-confirm-backdrop" id="api_confirm_dialog" aria-hidden="true">
        <section class="api-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="api_confirm_title" aria-describedby="api_confirm_description">
            <header><span class="api-confirm-icon"><i class="fas fa-shield-alt"></i></span><div><small>CONFIRMACIÓN DE SEGURIDAD</small><h2 id="api_confirm_title">Confirmar operación</h2></div><button type="button" class="api-modal-close" data-api-confirm-cancel aria-label="Cancelar"><i class="fas fa-times"></i></button></header>
            <div class="api-confirm-body"><p id="api_confirm_description"></p><div class="api-confirm-company" id="api_confirm_company"></div><small>Esta operación queda asociada a la empresa seleccionada y requiere confirmación expresa.</small></div>
            <footer><button type="button" class="btn btn-secondary" data-api-confirm-cancel><i class="fas fa-times"></i> Cancelar</button><button type="button" class="btn btn-primary" id="api_confirm_accept"><i class="fas fa-check"></i> Confirmar</button></footer>
        </section>
    </div>
</div>
