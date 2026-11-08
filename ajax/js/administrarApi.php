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

        require_once dirname(__DIR__, 2) . '/core/api/ApiAdminService.php';
        $service = new IzzyApiAdminService();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'list_sessions') $respond(true, 'Sesiones móviles cargadas.', ['sesiones' => $service->listarSesiones()]);
        if ($action === 'revoke_session') {
            $service->revocarSesion((int)($_POST['id'] ?? 0));
            $respond(true, 'Sesión móvil revocada. El dispositivo tendrá que iniciar sesión nuevamente.');
        }
        $respond(false, 'Acción no reconocida.', [], 400);
    } catch (InvalidArgumentException $e) {
        $respond(false, $e->getMessage(), [], 422);
    } catch (Throwable $e) {
        error_log('Administración API IZZY: ' . $e->getMessage());
        $respond(false, 'No se pudo completar la operación. Verifique la instalación de las tablas de autenticación.', [], 500);
    }
}
?>
<script>
(function ($) {
    'use strict';
    if (!$ || !$('#izzy_api_admin').length) return;
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
    function loadSessions(){
        $('#api_sessions_rows').html('<tr><td colspan="7" class="api-loading"><i class="fas fa-spinner fa-spin"></i> Cargando sesiones…</td></tr>');
        post({action:'list_sessions'}).done(response=>{
            const rows=response.datos.sesiones||[];
            if(!rows.length){$('#api_sessions_rows').html('<tr><td colspan="7" class="api-loading">Todavía no hay sesiones móviles registradas.</td></tr>');return;}
            $('#api_sessions_rows').html(rows.map(row=>{
                const expired=row.expires_at&&new Date(String(row.expires_at).replace(' ','T'))<new Date();
                const action=row.revoked_at||expired?'—':'<button type="button" class="btn btn-sm btn-danger api-row-action" data-session-id="'+Number(row.id)+'"><i class="fas fa-ban"></i> Revocar</button>';
                return '<tr><td>'+Number(row.users_id)+'</td><td>'+Number(row.empresa_id)+'</td><td><code>'+esc(row.db_cliente)+'</code></td><td>'+formatDate(row.created_at)+'</td><td>'+formatDate(row.expires_at)+'</td><td>'+sessionState(row)+'</td><td>'+action+'</td></tr>';
            }).join(''));
        }).fail(xhr=>$('#api_sessions_rows').html('<tr><td colspan="7" class="api-loading">'+esc(xhr.responseJSON?.mensaje||'No se pudieron cargar las sesiones.')+'</td></tr>'));
    }
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
    $('[data-api-tab]').on('click',function(){
        const name=$(this).data('api-tab');$('[data-api-tab]').removeClass('is-active');$(this).addClass('is-active');$('[data-api-panel]').removeClass('is-active').filter('[data-api-panel="'+name+'"]').addClass('is-active');
        if(name==='sessions')loadSessions();
    });
})(window.jQuery);
</script>
