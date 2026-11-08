<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start(['name' => 'SD']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    $respond = static function (bool $ok, string $message, array $data = [], int $status = 200): void {
        http_response_code($status);
        echo json_encode(['ok' => $ok, 'mensaje' => $message, 'datos' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };
    try {
        $peticionAjax = true;
        require_once dirname(__DIR__, 2) . '/core/configGenerales.php';
        require_once dirname(__DIR__, 2) . '/core/mainModel.php';
        $session = mainModel::validarSesion();
        if (!empty($session['error'])) $respond(false, 'La sesión venció. Inicie sesión nuevamente.', [], 401);
        if ((int)($_SESSION['empresa_id_sd'] ?? 0) !== 1) $respond(false, 'Esta administración está disponible para el equipo principal de IZZY.', [], 403);

        $main = (new mainModel())->connectionDBLocal(DB_MAIN);
        $privilegeId = (int)($_SESSION['privilegio_sd'] ?? 0);
        $stmt = $main->prepare('SELECT nombre FROM privilegio WHERE privilegio_id=? LIMIT 1');
        $stmt->bind_param('i', $privilegeId); $stmt->execute(); $role = $stmt->get_result()->fetch_assoc(); $stmt->close(); $main->close();
        if (!in_array(trim((string)($role['nombre'] ?? '')), ['Administrador', 'Super Administrador'], true)) $respond(false, 'No tiene autorización para administrar la API.', [], 403);

        $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '');
        if ($csrf === '' || !hash_equals((string)($_SESSION['csrf_administrar_api'] ?? ''), $csrf)) $respond(false, 'La solicitud expiró. Recargue la página e intente nuevamente.', [], 419);

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'list_sessions') {
            require_once dirname(__DIR__, 2) . '/core/api/ApiAdminService.php';
            $service = new IzzyApiAdminService();
            $respond(true, 'Sesiones móviles cargadas.', ['sesiones' => $service->listarSesiones()]);
        }
        if ($action === 'revoke_session') {
            require_once dirname(__DIR__, 2) . '/core/api/ApiAdminService.php';
            $service = new IzzyApiAdminService();
            $service->revocarSesion((int)($_POST['id'] ?? 0));
            $respond(true, 'Sesión móvil revocada. El dispositivo tendrá que iniciar sesión nuevamente.');
        }
        if (in_array($action, ['list_integrations', 'create_integration', 'revoke_integration'], true)) {
            require_once dirname(__DIR__, 2) . '/core/api/ApiIntegrationService.php';
            $integrations = new IzzyApiIntegrationService();
        }
        if ($action === 'list_integrations') $respond(true, 'Integraciones cargadas.', ['integraciones' => $integrations->listar()]);
        if ($action === 'create_integration') {
            $created = $integrations->crear((string)($_POST['name'] ?? ''), (int)($_POST['expires_in'] ?? 90), (int)($_SESSION['users_id_sd'] ?? 0));
            $respond(true, 'Integración creada. Copie el secreto ahora; no se volverá a mostrar.', ['integracion' => $created]);
        }
        if ($action === 'revoke_integration') {
            $integrations->revocar((int)($_POST['id'] ?? 0));
            $respond(true, 'Credencial revocada.');
        }
        $respond(false, 'Acción no reconocida.', [], 400);
    } catch (InvalidArgumentException $e) {
        $respond(false, $e->getMessage(), [], 422);
    } catch (Throwable $e) {
        error_log('Administración API IZZY: ' . $e->getMessage());
        $message = in_array((string)($action ?? ''), ['list_integrations', 'create_integration', 'revoke_integration'], true)
            ? 'No se pudo completar la operación. Verifique en DB_MAIN la tabla api_integrations del script DB_CAMBIOS_API_20261108.sql.'
            : 'No se pudo completar la operación. Verifique la instalación de las tablas de autenticación.';
        $respond(false, $message, [], 500);
    }
}
?>
<script>
(function ($) {
    'use strict';
    if (!$ || !$('#izzy_api_admin').length) return;
    $('body').addClass('izzy-api-view');
    const csrf=String($('#izzy_api_admin').data('csrf')||'');
    const endpoint='<?php echo htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8'); ?>ajax/js/administrarApi.php';
    const esc=value=>$('<span>').text(value==null?'':String(value)).html();
    const formatDate=value=>value?esc(String(value).replace('T',' ').replace('.000000','')):'—';
    function notify(message,type){
        if(typeof window.showNotify==='function')window.showNotify(type||'success',type==='error'?'Error':'API',message);
        else if(window.izzyNotifyPremium)window.izzyNotifyPremium.show(type||'success',type==='error'?'Error':'API',message);
        else if(window.Swal)Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:2800,icon:type||'success',title:message});
        else console.error(message);
    }
    function post(data){
        data.csrf=csrf;
        return $.ajax({url:endpoint,method:'POST',dataType:'json',data,headers:{'X-CSRF-Token':csrf}}).then(response=>{
            if(!response||!response.ok)return $.Deferred().reject({responseJSON:response}).promise();
            return response;
        });
    }
    function sessionState(row){
        if(row.revoked_at)return '<span class="api-status is-revoked"><i class="fas fa-ban"></i> Revocada</span>';
        if(row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date())return '<span class="api-status is-expired"><i class="fas fa-clock"></i> Vencida</span>';
        return '<span class="api-status is-active"><i class="fas fa-check-circle"></i> Activa</span>';
    }
    function integrationState(row){
        if(row.revoked_at)return '<span class="api-status is-revoked"><i class="fas fa-ban"></i> Revocada</span>';
        if(row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date())return '<span class="api-status is-expired"><i class="fas fa-clock"></i> Vencida</span>';
        return '<span class="api-status is-active"><i class="fas fa-check-circle"></i> Activa</span>';
    }
    function loadIntegrations(){
        $('#api_integrations_rows').html('<div class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando integraciones…</div>');
        post({action:'list_integrations'}).done(response=>{
            const rows=response.datos.integraciones||[];
            if(!rows.length){$('#api_integrations_rows').html('<div class="api-empty-state"><i class="fas fa-plug"></i><strong>No hay integraciones registradas</strong><span>Use “Nueva integración” para emitir una credencial de servidor.</span></div>');return;}
            $('#api_integrations_rows').html(rows.map(row=>{
                const revoked=!!row.revoked_at,expired=row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date();
                const action=revoked||expired?'<span class="api-no-action">—</span>':'<button type="button" class="btn btn-danger api-row-action" data-integration-id="'+Number(row.id)+'"><i class="fas fa-ban"></i> Revocar</button>';
                return '<article class="api-record-card api-integration-card"><div class="api-record-main"><span class="api-record-icon"><i class="fas fa-plug"></i></span><div class="api-record-title"><strong>'+esc(row.nombre)+'</strong><span>Client ID <code>'+esc(row.client_id)+'</code></span></div></div><div class="api-record-meta"><div><small>Creada</small><span>'+formatDate(row.created_at)+'</span></div><div><small>Expira</small><span>'+formatDate(row.expires_at)+'</span></div><div><small>Estado</small>'+integrationState(row)+'</div></div><div class="api-record-actions">'+action+'</div></article>';
            }).join(''));
        }).fail(xhr=>$('#api_integrations_rows').html('<div class="api-error-state"><i class="fas fa-triangle-exclamation"></i><strong>No se pudieron cargar las integraciones</strong><span>'+esc(xhr.responseJSON?.mensaje||'Verifique la conexión e instalación de la tabla api_integrations.')+'</span></div>'));
    }
    function loadSessions(){
        $('#api_sessions_rows').html('<div class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando sesiones…</div>');
        post({action:'list_sessions'}).done(response=>{
            const rows=response.datos.sesiones||[];
            if(!rows.length){$('#api_sessions_rows').html('<div class="api-empty-state"><i class="fas fa-mobile-alt"></i><strong>No hay sesiones móviles</strong><span>Las sesiones aparecerán cuando un usuario inicie sesión desde la aplicación.</span></div>');return;}
            $('#api_sessions_rows').html(rows.map(row=>{
                const expired=row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date();
                const action=row.revoked_at||expired?'<span class="api-no-action">—</span>':'<button type="button" class="btn btn-danger api-row-action" data-session-id="'+Number(row.id)+'"><i class="fas fa-ban"></i> Revocar</button>';
                return '<article class="api-record-card api-session-card"><div class="api-record-main"><span class="api-record-icon"><i class="fas fa-mobile-alt"></i></span><div class="api-record-title"><strong>Usuario '+Number(row.users_id)+'</strong><span>Empresa '+Number(row.empresa_id)+' · <code>'+esc(row.db_cliente)+'</code></span></div></div><div class="api-record-meta"><div><small>Creada</small><span>'+formatDate(row.created_at)+'</span></div><div><small>Access vence</small><span>'+formatDate(row.expires_at)+'</span></div><div><small>Estado</small>'+sessionState(row)+'</div></div><div class="api-record-actions">'+action+'</div></article>';
            }).join(''));
        }).fail(xhr=>$('#api_sessions_rows').html('<div class="api-error-state"><i class="fas fa-triangle-exclamation"></i><strong>No se pudieron cargar las sesiones</strong><span>'+esc(xhr.responseJSON?.mensaje||'Verifique la conexión e instalación de las tablas de autenticación.')+'</span></div>'));
    }
    const integrationModal=$('#api_integration_modal'),secretModal=$('#api_secret_modal');
    function openModal(modal,focusSelector){modal.addClass('is-open').attr('aria-hidden','false');$('body').addClass('api-modal-open');if(focusSelector)setTimeout(()=>modal.find(focusSelector).trigger('focus'),30);}
    function closeModal(modal){modal.removeClass('is-open').attr('aria-hidden','true');if(!$('.api-modal-backdrop.is-open').length)$('body').removeClass('api-modal-open');}
    function prepararSelectVencimiento(){
        const select=$('#api_integration_expiry');
        if(!select.length)return;
        if(select.hasClass('select2-hidden-accessible'))select.select2('destroy');
        if($.fn.select2){
            select.select2({width:'100%',dropdownParent:integrationModal,minimumResultsForSearch:Infinity,allowClear:false,placeholder:'Seleccione vencimiento',language:{noResults:()=> 'No hay opciones disponibles'}});
            select.val('90').trigger('change.select2');
        }else{
            select.val('90');
        }
    }
    $('#api_new_integration').on('click',()=>{ $('#api_integration_form')[0].reset();openModal(integrationModal,'#api_integration_name');setTimeout(prepararSelectVencimiento,30); });
    $('[data-api-modal-close]').on('click',()=>closeModal(integrationModal));
    $('[data-api-secret-close]').on('click',()=>closeModal(secretModal));
    $(document).on('keydown',event=>{if(event.key==='Escape'){if(secretModal.hasClass('is-open'))closeModal(secretModal);else if(integrationModal.hasClass('is-open'))closeModal(integrationModal);}});
    $('#api_integration_form').on('submit',function(event){
        event.preventDefault();const button=$('#api_create_integration');button.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Creando…');
        post({action:'create_integration',name:$('#api_integration_name').val(),expires_in:$('#api_integration_expiry').val()}).done(response=>{
            const item=response.datos.integracion||{};$('#api_created_client_id').text(item.client_id||'');$('#api_created_client_secret').text(item.client_secret||'');closeModal(integrationModal);openModal(secretModal,'[data-copy-api-value="client"]');notify(response.mensaje,'success');loadIntegrations();
        }).fail(xhr=>notify(xhr.responseJSON?.mensaje||'No se pudo crear la integración.','error')).always(()=>button.prop('disabled',false).html('<i class="fas fa-key"></i> Crear credenciales'));
    });
    $('[data-copy-api-value]').on('click',function(){const id=$(this).data('copy-api-value')==='client'?'#api_created_client_id':'#api_created_client_secret',value=$(id).text();if(navigator.clipboard)navigator.clipboard.writeText(value).then(()=>notify('Dato copiado.','success'));else{const field=document.createElement('textarea');field.value=value;document.body.appendChild(field);field.select();document.execCommand('copy');field.remove();notify('Dato copiado.','success');}});
    $('#api_integrations_rows').on('click','[data-integration-id]',function(){
        const button=this,id=Number($(this).data('integration-id'));
        if(!window.Swal){notify('No se cargó el diálogo de confirmación. Recargue la página.','error');return;}
        Swal.fire({title:'¿Revocar esta credencial?',text:'La integración quedará inactiva y ya no podrá utilizarse cuando se habilite su endpoint.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, revocar',cancelButtonText:'Cancelar',confirmButtonColor:'#e94b3c'}).then(result=>{if(!result.isConfirmed)return;$(button).prop('disabled',true);post({action:'revoke_integration',id}).done(response=>{notify(response.mensaje,'success');loadIntegrations();}).fail(xhr=>{notify(xhr.responseJSON?.mensaje||'No se pudo revocar la integración.','error');$(button).prop('disabled',false);});});
    });
    $('#api_refresh_sessions').on('click',loadSessions);
    $('#api_sessions_rows').on('click','[data-session-id]',function(){
        const button=this,id=Number($(this).data('session-id'));
        if(!window.Swal){notify('No se cargó el diálogo de confirmación. Recargue la página.','error');return;}
        Swal.fire({title:'¿Revocar esta sesión?',text:'Se invalidarán el token de acceso y su renovación.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, revocar',cancelButtonText:'Cancelar',confirmButtonColor:'#e94b3c'}).then(result=>{
            if(!result.isConfirmed)return;
            $(button).prop('disabled',true);
            post({action:'revoke_session',id}).done(response=>{notify(response.mensaje,'success');loadSessions();}).fail(xhr=>{notify(xhr.responseJSON?.mensaje||'No se pudo revocar la sesión.','error');$(button).prop('disabled',false);});
        });
    });
    $('#api_copy_identifier').on('click',function(){
        const value=$('#api_identifier').text();
        if(navigator.clipboard)navigator.clipboard.writeText(value).then(()=>notify('Identificador copiado.','success'));
        else{const field=document.createElement('textarea');field.value=value;document.body.appendChild(field);field.select();document.execCommand('copy');field.remove();notify('Identificador copiado.','success');}
    });
    $('.api-route-grid').on('click','[data-api-info]',function(){
        const title=$(this).text().trim(),description=String($(this).attr('data-api-info')||'');
        if(window.Swal)Swal.fire({title,text:description,icon:'info',confirmButtonText:'Entendido',confirmButtonColor:'#168bd0'});
        else notify(description,'info');
    });
    $('[data-api-tab]').on('click',function(){
        const name=$(this).data('api-tab');$('[data-api-tab]').removeClass('is-active');$(this).addClass('is-active');$('[data-api-panel]').removeClass('is-active').filter('[data-api-panel="'+name+'"]').addClass('is-active');
        if(name==='sessions')loadSessions();if(name==='integrations')loadIntegrations();
    });
    loadIntegrations();
})(window.jQuery);
</script>
