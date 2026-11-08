<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start(['name' => 'SD']);
if (empty($_SESSION['csrf_administrar_api'])) $_SESSION['csrf_administrar_api'] = bin2hex(random_bytes(32));
$apiCsrf = htmlspecialchars($_SESSION['csrf_administrar_api'], ENT_QUOTES, 'UTF-8');
$apiBase = htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $apiBase; ?>vistas/plantilla/css/administrarApi.css?v=<?php echo @filemtime(__DIR__ . '/../plantilla/css/administrarApi.css') ?: time(); ?>">
<div class="container-fluid izzy-api-page" id="izzy_api_admin" data-csrf="<?php echo $apiCsrf; ?>">
    <div class="api-page-heading">
        <div class="api-heading-icon"><i class="fas fa-code"></i></div>
        <div><span class="api-eyebrow">SISTEMA · API</span><h1>Administración de API</h1>
            <p>Gestione integraciones, credenciales y sesiones de la aplicación móvil.</p></div>
    </div>

    <section class="api-notice" role="note">
        <i class="fas fa-shield-alt"></i><div><strong>Credenciales seguras</strong>
        <span>El secreto de integración se muestra una sola vez. La aplicación móvil usa tokens de usuario emitidos al iniciar sesión; no necesita una clave compartida dentro del APK.</span></div>
    </section>

    <div class="api-tabs" role="tablist">
        <button class="api-tab is-active" type="button" data-api-tab="integrations"><i class="fas fa-plug"></i> Integraciones</button>
        <button class="api-tab" type="button" data-api-tab="sessions"><i class="fas fa-mobile-alt"></i> Sesiones móviles</button>
    </div>

    <section class="api-panel is-active" data-api-panel="integrations">
        <div class="api-panel-heading"><div><h2>Integraciones de servidor</h2><p>Para sistemas externos que consuman endpoints autorizados.</p></div>
            <button type="button" class="btn btn-primary" id="api_add_integration"><i class="fas fa-plus"></i> Nueva integración</button></div>
        <div class="api-table-wrap"><table class="api-table"><thead><tr><th>Nombre</th><th>Client ID</th><th>Alcances</th><th>Expira</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody id="api_integrations_rows"><tr><td colspan="6" class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando integraciones…</td></tr></tbody></table></div>
        <div class="api-footnote"><i class="fas fa-info-circle"></i> Los alcances disponibles se habilitarán conforme se publiquen endpoints de integración.</div>
    </section>

    <section class="api-panel" data-api-panel="sessions">
        <div class="api-panel-heading"><div><h2>Sesiones de la aplicación móvil</h2><p>Revocar una sesión también invalida su token de renovación.</p></div>
            <button type="button" class="btn btn-outline-primary" id="api_refresh_sessions"><i class="fas fa-sync-alt"></i> Actualizar</button></div>
        <div class="api-table-wrap"><table class="api-table"><thead><tr><th>Usuario ID</th><th>Empresa</th><th>Base de datos</th><th>Creada</th><th>Vence</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody id="api_sessions_rows"><tr><td colspan="7" class="api-loading">Seleccione esta pestaña para cargar las sesiones.</td></tr></tbody></table></div>
        <div class="api-footnote"><i class="fas fa-clock"></i> Se muestran las últimas 100 sesiones registradas.</div>
    </section>
</div>

<div class="modal fade" id="api_integration_modal" tabindex="-1" role="dialog" aria-labelledby="api_integration_title" aria-hidden="true" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content api-modal-content">
        <div class="modal-header"><div><h5 class="modal-title" id="api_integration_title"><i class="fas fa-plug"></i> Crear integración</h5><p>Defina el nombre y los alcances iniciales.</p></div><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button></div>
        <form id="api_integration_form"><div class="modal-body">
            <div class="form-group"><label for="api_integration_name">Nombre de la integración</label><input class="form-control" id="api_integration_name" maxlength="100" required placeholder="Ej. Portal de reportes"></div>
            <div class="form-group"><label>Alcances autorizados</label>
                <label class="api-scope"><input type="checkbox" name="api_scope" value="profile:read" checked><span><strong>Perfil</strong><small>Consultar perfil asociado a credenciales.</small></span></label>
                <label class="api-scope"><input type="checkbox" name="api_scope" value="menus:read" checked><span><strong>Menús</strong><small>Consultar estructura habilitada para el usuario.</small></span></label>
            </div>
            <div class="form-group"><label for="api_integration_expiry">Expiración (opcional)</label><input class="form-control" type="datetime-local" id="api_integration_expiry"><small class="form-text text-muted">Sin fecha, la credencial permanece activa hasta revocarla.</small></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button><button type="submit" class="btn btn-primary" id="api_create_submit"><i class="fas fa-key"></i> Crear credencial</button></div></form>
    </div></div>
</div>

<div class="modal fade" id="api_secret_modal" tabindex="-1" role="dialog" aria-labelledby="api_secret_title" aria-hidden="true" data-backdrop="static" data-keyboard="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content api-modal-content">
        <div class="modal-header"><div><h5 class="modal-title" id="api_secret_title"><i class="fas fa-key"></i> Guarde el secreto ahora</h5><p>Por seguridad, no volverá a mostrarse.</p></div><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button></div>
        <div class="modal-body"><label>Client ID</label><div class="api-secret-row"><code id="api_secret_client_id"></code><button type="button" class="btn btn-outline-primary api-copy" data-copy="api_secret_client_id"><i class="far fa-copy"></i></button></div>
            <label>Client Secret</label><div class="api-secret-row"><code id="api_secret_value"></code><button type="button" class="btn btn-outline-primary api-copy" data-copy="api_secret_value"><i class="far fa-copy"></i></button></div>
            <div class="api-secret-warning"><i class="fas fa-exclamation-triangle"></i> Guárdelo en el servidor consumidor. IZZY conserva únicamente el hash del secreto.</div>
        </div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-check"></i> Entendido</button></div>
    </div></div>
</div>
