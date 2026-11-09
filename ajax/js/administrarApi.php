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
        if (in_array($action, ['list_integrations', 'create_integration', 'revoke_integration', 'list_authorizations', 'set_authorization'], true)) {
            require_once dirname(__DIR__, 2) . '/core/api/ApiIntegrationService.php';
            $integrations = new IzzyApiIntegrationService();
        }
        if ($action === 'list_integrations') $respond(true, 'Integraciones cargadas.', ['integraciones' => $integrations->listar()]);
        if ($action === 'list_authorizations') $respond(true, 'Empresas cargadas.', ['empresas' => $integrations->autorizaciones()]);
        if ($action === 'set_authorization') {
            $integrations->autorizar((int)($_POST['cliente_id'] ?? 0), (int)($_POST['habilitada'] ?? 0)===1, (int)($_SESSION['users_id_sd'] ?? 0));
            $respond(true,'Autorización de API actualizada.');
        }
        if ($action === 'create_integration') {
            $scopes=$_POST['scopes'] ?? [];
            if (!is_array($scopes)) $respond(false,'Alcances inválidos.',[],422);
            $created = $integrations->crear((string)($_POST['name'] ?? ''), (int)($_POST['expires_in'] ?? 90), (int)($_SESSION['users_id_sd'] ?? 0), (int)($_POST['cliente_id'] ?? 0), $scopes);
            $respond(true, 'Integración creada. Copie el secreto ahora; no se volverá a mostrar.', ['integracion' => $created]);
        }
        if ($action === 'revoke_integration') {
            $integrations->revocar((int)($_POST['id'] ?? 0));
            $respond(true, 'Credencial revocada.');
        }
        $respond(false, 'Acción no reconocida.', [], 400);
    } catch (InvalidArgumentException $e) {
        $respond(false, $e->getMessage(), [], 422);
    } catch (DomainException $e) {
        $respond(false, $e->getMessage(), [], 403);
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
    const integrationTokenUrl='<?php echo htmlspecialchars(rtrim(SERVERURL, '/'), ENT_QUOTES, 'UTF-8'); ?>/api/v1/integrations/token.php';
    const esc=value=>$('<span>').text(value==null?'':String(value)).html();
    const formatDate=value=>value?esc(String(value).replace('T',' ').replace('.000000','')):'—';
    function notify(message,type){
        if(typeof window.showNotify==='function')window.showNotify(type||'success',type==='error'?'Error':'API',message);
        else if(window.izzyNotifyPremium)window.izzyNotifyPremium.show(type||'success',type==='error'?'Error':'API',message);
        else if(typeof window.swal==='function')window.swal({title:type==='error'?'Error':'API',text:message,icon:type==='error'?'error':'success',button:'Entendido'});
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
    let authorizedCompanies=[];
    function renderAuthorizations(){
        const query=String($('#api_authorizations_search').val()||'').trim().toLocaleLowerCase('es');
        const status=String($('#api_authorizations_status').val()||'active');
        const system=String($('#api_authorizations_system').val()||'1');
        const rows=authorizedCompanies.filter(row=>{
            const enabled=Number(row.habilitada)===1,active=Number(row.estado)===1;
            const matches=!query||[row.nombre_empresa,row.codigo_cliente,row.server_customers_id,row.db,row.sistema_nombre].some(value=>String(value||'').toLocaleLowerCase('es').includes(query));
            return matches&&(system==='all'||String(row.sistema_id)===system)&&(status==='all'||(status==='active'&&active)||(status==='approved'&&active&&enabled)||(status==='denied'&&active&&!enabled)||(status==='inactive'&&!active));
        });
        $('#api_authorizations_count').text('Mostrando '+rows.length+' de '+authorizedCompanies.length+' empresas · Sistema: '+($('#api_authorizations_system option:selected').text()||'IZZY'));
        $('#api_authorizations_rows').html(rows.map((row,index)=>{
            const enabled=Number(row.habilitada)===1,active=Number(row.estado)===1;
            const isIzzy=Number(row.sistema_id)===1;
            const name=esc(row.nombre_empresa||'Empresa sin nombre');
            return '<article class="api-record-card api-company-card"><div class="api-record-main"><span class="api-record-icon api-record-number">'+(index+1)+'</span><div class="api-record-title"><strong>'+name+'</strong><span>Código '+esc(row.codigo_cliente||row.server_customers_id)+' · <code>'+esc(row.db)+'</code></span><span class="api-system-chip '+(isIzzy?'is-izzy':'is-other')+'"><i class="fas fa-cubes"></i> '+esc(row.sistema_nombre||'Sin sistema')+'</span></div></div><div class="api-record-meta"><div><small>Estado empresa</small><span class="api-status '+(active?'is-active':'is-expired')+'"><i class="fas '+(active?'fa-check-circle':'fa-pause-circle')+'"></i> '+(active?'Activa':'Inactiva')+'</span></div><div><small>Acceso API</small><span class="api-status '+(enabled?'is-active':'is-revoked')+'"><i class="fas '+(enabled?'fa-shield-alt':'fa-ban')+'"></i> '+(enabled?'Autorizado':'Denegado')+'</span></div></div><div class="api-record-actions">'+(!isIzzy&&!enabled?'<span class="api-system-unavailable"><i class="fas fa-lock"></i> API solo IZZY</span>':'<button type="button" class="btn '+(enabled?'btn-danger':'btn-primary')+'" data-api-grant="'+Number(row.server_customers_id)+'" data-api-enable="'+(enabled?0:1)+'" '+(!active&&!enabled?'disabled':'')+'><i class="fas '+(enabled?'fa-ban':'fa-shield-alt')+'"></i> '+(enabled?'Suspender':'Autorizar')+'</button>')+'</div></article>';
        }).join('')||'<div class="api-empty-state"><i class="fas fa-search"></i><strong>Sin resultados</strong><span>Pruebe con otro nombre, código o estado.</span></div>');
    }
    function loadAuthorizations(){
        $('#api_authorizations_rows').html('<div class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando empresas…</div>');
        return post({action:'list_authorizations'}).done(response=>{
            authorizedCompanies=response.datos.empresas||[];
            const systemSelect=$('#api_authorizations_system');
            const selectedSystem=String(systemSelect.val()||'1');
            const systems=new Map();
            authorizedCompanies.forEach(company=>systems.set(String(company.sistema_id),String(company.sistema_nombre||'Sin sistema')));
            systemSelect.empty();
            Array.from(systems.entries()).sort((a,b)=>Number(a[0])-Number(b[0])).forEach(([id,label])=>systemSelect.append($('<option>').val(id).text(label)));
            systemSelect.append($('<option>').val('all').text('Todos los sistemas'));
            systemSelect.val(systems.has(selectedSystem)||selectedSystem==='all'?selectedSystem:(systems.has('1')?'1':'all'));
            if(systemSelect.hasClass('select2-hidden-accessible'))systemSelect.trigger('change.select2');
            const select=$('#api_integration_customer');
            const eligible=authorizedCompanies.filter(x=>Number(x.habilitada)===1&&Number(x.estado)===1&&Number(x.sistema_id)===1);
            select.empty().append($('<option>').val('').text(eligible.length?'Seleccione una empresa autorizada':'No hay empresas autorizadas'));
            eligible.forEach(x=>select.append($('<option>').val(x.server_customers_id).text((x.nombre_empresa||'Empresa sin nombre')+' · '+(x.codigo_cliente||x.server_customers_id))));
            $('#api_no_authorized_hint').prop('hidden',eligible.length>0);
            $('#api_create_integration').prop('disabled',eligible.length===0);
            if(select.hasClass('select2-hidden-accessible'))select.trigger('change.select2');
            renderAuthorizations();
        }).fail(xhr=>$('#api_authorizations_rows').html('<div class="api-error-state">'+esc(xhr.responseJSON?.mensaje||'No se pudieron cargar las empresas.')+'</div>'));
    }
    $('#api_refresh_authorizations').on('click',loadAuthorizations);
    $('#api_authorizations_search').on('input',renderAuthorizations);
    $('#api_authorizations_status').on('change',renderAuthorizations);
    $('#api_authorizations_system').on('change',renderAuthorizations);
    if($.fn.select2)$('#api_authorizations_status').select2({width:'100%',minimumResultsForSearch:Infinity});
    if($.fn.select2)$('#api_authorizations_system').select2({width:'100%',minimumResultsForSearch:Infinity});
    function confirmAuthorization(title,description,company,enable){
        if(typeof window.swal==='function'){
            return window.swal({title,text:company+' — '+description,icon:'warning',buttons:{cancel:{text:'Cancelar',value:null,visible:true,className:'izzy-swal-cancel'},confirm:{text:enable?'Sí, autorizar':'Sí, suspender',value:true,visible:true,className:enable?'izzy-swal-approve':'izzy-swal-danger'}},dangerMode:!enable,closeOnClickOutside:false,closeOnEsc:true}).then(result=>result===true);
        }
        // Confirmación accesible de respaldo cuando el swal clásico no está disponible.
        return new Promise(resolve=>{
            const dialog=$('#api_confirm_dialog'),accept=$('#api_confirm_accept');
            $('#api_confirm_title').text(title);
            $('#api_confirm_description').text(description);
            $('#api_confirm_company').text(company);
            accept.removeClass('btn-primary btn-danger').addClass(enable?'btn-primary':'btn-danger').html('<i class="fas fa-'+(enable?'shield-alt':'ban')+'"></i> '+(enable?'Autorizar empresa':'Suspender acceso'));
            dialog.addClass('is-open').attr('aria-hidden','false');
            $('body').addClass('api-modal-open');
            const finish=confirmed=>{dialog.removeClass('is-open').attr('aria-hidden','true');$('body').removeClass('api-modal-open');accept.off('click.apiConfirm');dialog.off('click.apiConfirm');$(document).off('keydown.apiConfirm');resolve(confirmed);};
            accept.one('click.apiConfirm',()=>finish(true));
            dialog.on('click.apiConfirm','[data-api-confirm-cancel]',()=>finish(false));
            $(document).on('keydown.apiConfirm',event=>{if(event.key==='Escape')finish(false);});
            setTimeout(()=>dialog.find('[data-api-confirm-cancel]').last().trigger('focus'),20);
        });
    }
    $('#api_authorizations_rows').on('click','[data-api-grant]',function(){
        const id=Number($(this).data('api-grant')),enable=Number($(this).data('api-enable'));
        const row=authorizedCompanies.find(item=>Number(item.server_customers_id)===id);
        if(!row){notify('No se encontró la empresa seleccionada. Actualice el listado.','error');return;}
        const company=String(row.nombre_empresa||'Empresa')+' · '+String(row.codigo_cliente||id);
        confirmAuthorization(enable?'¿Autorizar el acceso API?':'¿Suspender el acceso API?',enable?'La empresa podrá crear credenciales de integración y solicitar tokens.':'Sus tokens de integración activos quedarán invalidados.',company,enable).then(confirmed=>{
            if(!confirmed)return;
            const button=$(this);button.prop('disabled',true);
            post({action:'set_authorization',cliente_id:id,habilitada:enable}).done(response=>{notify(response.mensaje,'success');loadAuthorizations();loadIntegrations();}).fail(xhr=>{notify(xhr.responseJSON?.mensaje||'No se pudo actualizar.','error');button.prop('disabled',false);});
        });
    });
    function loadIntegrations(){
        $('#api_integrations_rows').html('<div class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando integraciones…</div>');
        post({action:'list_integrations'}).done(response=>{
            const rows=response.datos.integraciones||[];
            if(!rows.length){$('#api_integrations_rows').html('<div class="api-empty-state"><i class="fas fa-plug"></i><strong>No hay integraciones registradas</strong><span>Use “Nueva integración” para emitir una credencial de servidor.</span></div>');return;}
            $('#api_integrations_rows').html(rows.map(row=>{
                const revoked=!!row.revoked_at,expired=row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date();
                const action=revoked||expired?'<span class="api-no-action">—</span>':'<button type="button" class="btn btn-danger api-row-action" data-integration-id="'+Number(row.id)+'"><i class="fas fa-ban"></i> Revocar</button>';
                return '<article class="api-record-card api-integration-card"><div class="api-record-main"><span class="api-record-icon"><i class="fas fa-plug"></i></span><div class="api-record-title"><strong>'+esc(row.nombre)+'</strong><span>'+esc(row.nombre_empresa)+' · Client ID <code>'+esc(row.client_id)+'</code></span><span>URL: <code>'+esc(integrationTokenUrl)+'</code></span><span>Permisos: '+esc(row.scopes||'No configurados')+'</span></div></div><div class="api-record-meta"><div><small>Creada</small><span>'+formatDate(row.created_at)+'</span></div><div><small>Expira</small><span>'+formatDate(row.expires_at)+'</span></div><div><small>Estado</small>'+integrationState(row)+'</div></div><div class="api-record-actions">'+action+'</div></article>';
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
    const integrationModal=$('#api_integration_modal'),secretModal=$('#api_secret_modal'),routeModal=$('#api_route_modal');
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
    $('#api_new_integration').on('click',()=>{
        $('#api_integration_form')[0].reset();
        openModal(integrationModal,'#api_integration_customer');
        $('#api_no_authorized_hint').prop('hidden',true);
        $('#api_create_integration').prop('disabled',true);
        const customerSelect=$('#api_integration_customer');
        if($.fn.select2&&!customerSelect.hasClass('select2-hidden-accessible'))customerSelect.select2({width:'100%',dropdownParent:integrationModal,placeholder:'Seleccione una empresa',language:{noResults:()=> 'No hay empresas autorizadas'}});
        loadAuthorizations().fail(xhr=>{notify(xhr.responseJSON?.mensaje||'No se pudieron consultar las empresas autorizadas.','error');$('#api_no_authorized_hint').prop('hidden',false);});
        setTimeout(prepararSelectVencimiento,30);
    });
    $('#api_go_authorizations').on('click',()=>{closeModal(integrationModal);$('[data-api-tab="authorizations"]').trigger('click');});
    $('[data-api-modal-close]').on('click',()=>closeModal(integrationModal));
    $('[data-api-secret-close]').on('click',()=>closeModal(secretModal));
    $('[data-api-route-close]').on('click',()=>closeModal(routeModal));
    $('.api-route-grid').on('click','.api-route-detail summary',function(event){
        event.preventDefault();
        const details=$(this).closest('details'),card=details.closest('.api-route-card');
        // Nunca abrir <details> en la cuadrícula: mantiene las tarjetas inmóviles.
        details.prop('open',false);
        $('#api_route_modal_title').text(card.find('.api-route-top strong').first().text());
        const source=details.clone();source.removeAttr('open');source.find('summary').remove();
        $('#api_route_modal_body').empty().append(source.children());
        openModal(routeModal,'[data-api-route-close]');
    });
    $(document).on('keydown',event=>{if(event.key==='Escape'){if(routeModal.hasClass('is-open'))closeModal(routeModal);else if(secretModal.hasClass('is-open'))closeModal(secretModal);else if(integrationModal.hasClass('is-open'))closeModal(integrationModal);}});
    $('#api_integration_form').on('submit',function(event){
        event.preventDefault();if(!$('#api_integration_customer').val()){notify('Primero seleccione una empresa activa y autorizada.','error');return;}const button=$('#api_create_integration');button.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Creando…');
        const scopes=$('[name="api_scope"]:checked').map(function(){return this.value;}).get();
        post({action:'create_integration',name:$('#api_integration_name').val(),expires_in:$('#api_integration_expiry').val(),cliente_id:$('#api_integration_customer').val(),scopes}).done(response=>{
            const item=response.datos.integracion||{};$('#api_created_client_id').text(item.client_id||'');$('#api_created_client_secret').text(item.client_secret||'');closeModal(integrationModal);openModal(secretModal,'[data-copy-api-value="client"]');notify(response.mensaje,'success');loadIntegrations();
        }).fail(xhr=>notify(xhr.responseJSON?.mensaje||'No se pudo crear la integración.','error')).always(()=>button.prop('disabled',false).html('<i class="fas fa-key"></i> Crear credenciales'));
    });
    $('[data-copy-api-value]').on('click',function(){const id=$(this).data('copy-api-value')==='client'?'#api_created_client_id':'#api_created_client_secret',value=$(id).text();if(navigator.clipboard)navigator.clipboard.writeText(value).then(()=>notify('Dato copiado.','success'));else{const field=document.createElement('textarea');field.value=value;document.body.appendChild(field);field.select();document.execCommand('copy');field.remove();notify('Dato copiado.','success');}});
    $('#api_integrations_rows').on('click','[data-integration-id]',function(){
        const button=this,id=Number($(this).data('integration-id'));
        confirmAuthorization('¿Revocar esta credencial?','La integración quedará inactiva y dejará de emitir tokens.','Credencial #'+id,false).then(confirmed=>{if(!confirmed)return;$(button).prop('disabled',true);post({action:'revoke_integration',id}).done(response=>{notify(response.mensaje,'success');loadIntegrations();}).fail(xhr=>{notify(xhr.responseJSON?.mensaje||'No se pudo revocar la integración.','error');$(button).prop('disabled',false);});});
    });
    $('#api_refresh_sessions').on('click',loadSessions);
    $('#api_sessions_rows').on('click','[data-session-id]',function(){
        const button=this,id=Number($(this).data('session-id'));
        confirmAuthorization('¿Revocar esta sesión?','Se invalidarán el token de acceso y su renovación.','Sesión móvil #'+id,false).then(confirmed=>{
            if(!confirmed)return;
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
        if(typeof window.swal==='function')window.swal({title,text:description,icon:'info',button:'Entendido'});
        else notify(description,'info');
    });
    $('[data-api-tab]').on('click',function(){
        const name=$(this).data('api-tab');$('[data-api-tab]').removeClass('is-active');$(this).addClass('is-active');$('[data-api-panel]').removeClass('is-active').filter('[data-api-panel="'+name+'"]').addClass('is-active');
        if(name==='sessions')loadSessions();if(name==='integrations')loadIntegrations();if(name==='authorizations')loadAuthorizations();
    });
    loadIntegrations();
})(window.jQuery);
</script>
