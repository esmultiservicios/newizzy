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
        // Conexiones salientes: reutiliza sesión de administrador global y CSRF anteriores.
        if (str_starts_with($action, 'external_')) {
            require_once dirname(__DIR__,2).'/core/api/ExternalProviderService.php';
            $external=new IzzyExternalProviderService();
            if ($action==='external_diagnostics') $respond(true,'Diagnóstico de dependencias.', $external->diagnostics());
            if ($action==='external_list') $respond(true,'Conexiones salientes.', ['conexiones'=>$external->listing(),'categorias'=>$external->categories()]);
            if ($action==='external_save') {
                $savedId=$external->save($_POST);
                // El catálogo declarativo es auxiliar: si falta la migración v011, NO fingir que el guardado falló.
                $catalogo='no_aplicable';
                if(strtoupper(trim((string)($_POST['proveedor']??'')))==='ZYNKO' && trim((string)($_POST['url_base']??''))==='https://zynkocloud.app/api.php') {
                    try { $external->seedZynkoOperations($savedId);$catalogo='cargado'; }
                    catch(Throwable $catalogError){error_log('IZZY external seed: '.get_class($catalogError));$catalogo='pendiente_migracion_v011';}
                }
                $respond(true,$catalogo==='pendiente_migracion_v011'?'Conexión guardada; falta instalar la tabla de operaciones v011.':'Conexión guardada. Active y pruebe sus permisos antes de utilizarla.', ['id'=>$savedId,'catalogo'=>$catalogo]);
            }
            if ($action==='external_toggle') { $external->toggle((int)($_POST['id']??0),(string)($_POST['activo']??'0')==='1');$respond(true,'Estado actualizado.'); }
            if ($action==='external_archive') { $external->archive((int)($_POST['id']??0));$respond(true,'Conexión archivada.'); }
            if ($action==='external_test') {
                // Verificación estrictamente de lectura: primero canales; historial solo si canales valida.
                $channels=$external->request((int)($_POST['id']??0),'v1/channels');
                $history=null;
                if($channels['ok'])$history=$external->request((int)($_POST['id']??0),'v1/whatsapp/history');
                $passed=$channels['ok'] && $history!==null && $history['ok'];
                $message=!$channels['ok']?'Canales: '.$channels['detalle'].' No se consultó historial porque falló la primera autenticación.'
                    : ($passed?'Canales e historial: autenticación y respuestas JSON confirmadas.':
                       'Canales: HTTP '.$channels['http_status'].' correcto. Historial: '.$history['detalle']);
                $respond($passed,$message,[
                    'http_status'=>$history['http_status']??$channels['http_status'],
                    'canales'=>$channels,'historial'=>$history,
                    'canal'=>$channels['canal'],
                    'detalle'=>$message
                ],$passed?200:502);
            }
            if ($action==='external_read') {
                if(($_POST['tipo']??'')!=='history')$respond(false,'Consulte el estado desde un envío y su UUID.',[],422);
                $result=$external->request((int)($_POST['id']??0),'v1/whatsapp/history',max(1,min(100000,(int)($_POST['page']??1))));
                $respond($result['ok'],$result['detalle'],$result,$result['ok']?200:502);
            }
            if ($action==='external_wa_ready') {
                $external->assertWhatsappTestReady((int)($_POST['id']??0));
                $respond(true,'Pruebas disponibles.');
            }
            if ($action==='external_wa_list') {
                $respond(true,'Envíos de prueba.', ['operations'=>$external->whatsappTests((int)($_POST['id']??0)), 'server_host'=>gethostname() ?: 'No disponible']);
            }
            if ($action==='external_wa_send' || $action==='external_wa_retry') {
                $result=$external->sendWhatsappTest((int)($_POST['id']??0),(string)($_POST['operation']??''),
                    (int)($_SESSION['users_id_sd']??0),$action==='external_wa_send'?$_POST:null);
                $respond(true,$result['detail'] ?: 'Operación registrada.',$result);
            }
            if ($action==='external_wa_status') {
                $result=$external->whatsappTestStatus((int)($_POST['id']??0),(string)($_POST['operation']??''),(string)($_POST['uuid']??''));
                $respond($result['ok'],$result['detalle'],$result,$result['ok']?200:502);
            }
            if ($action==='external_operations') $respond(true,'Operaciones registradas.', ['operaciones'=>$external->operations((int)($_POST['connection_id']??0))]);
            if ($action==='external_operation_save') $respond(true,'Operación guardada desactivada.',['id'=>$external->saveOperation($_POST)]);
            if ($action==='external_seed_zynko') $respond(true,'Catálogo ZYNKO cargado sin activar operaciones.',['agregadas'=>$external->seedZynkoOperations((int)($_POST['connection_id']??0))]);
            if ($action==='external_openapi_preview') $respond(true,'Vista previa de operaciones OpenAPI.', ['operaciones'=>$external->previewOpenApi((string)($_POST['openapi_json']??''))]);
            if ($action==='external_checks') $respond(true,'Resultados de conexión.', ['historial'=>$external->checks((int)($_POST['id']??0))]);
            $respond(false,'Operación no autorizada.',[],400);
        }

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
    } catch (mysqli_sql_exception $e) {
        error_log('Administración API IZZY (SQL): '.$e->getCode().' / '.$e->getSqlState());
        // Codigo SQL seguro para diagnostico; no revelar consultas ni valores privados.
        $sqlCode=(int)$e->getCode();
        if(str_starts_with((string)($action??''),'external_wa_') && in_array($sqlCode,[1146,1054],true)){
            $respond(false,'Las pruebas de WhatsApp necesitan una actualización de instalación.',[
                'code'=>$sqlCode===1146?'WA_TEST_STORAGE_REQUIRED':'WA_TEST_STORAGE_INCOMPATIBLE',
                'migration'=>'DB_Cambios.sql',
            ],503);
        }
        $message=($sqlCode===1406)
            ? 'Un resultado técnico excedió la longitud permitida en la base de datos. Compruebe que se instaló el servicio actualizado.'
            : (($sqlCode===1146||$sqlCode===1054)
                ? 'La estructura SQL de API externas está incompleta en DB_MAIN. Revise el diagnóstico de tablas y columnas.'
                : 'No fue posible registrar la comprobación. Consulte el código SQL en los registros del servidor.');
        $respond(false,$message,['codigo_sql'=>$sqlCode],500);
    } catch (RuntimeException $e) {
        $safe=(string)$e->getMessage();
        if(str_starts_with($safe,'EXTERNAL_KEY_MISSING:')||str_starts_with($safe,'CURL_MISSING:'))$respond(false,$safe,[],503);
        error_log('Administración API IZZY (runtime): '.get_class($e));
        $respond(false,'Error de configuración del servidor. Revise los registros de PHP.',[],500);
    } catch (DomainException $e) {
        $respond(false, $e->getMessage(), [], 403);
    } catch (Throwable $e) {
        error_log('Administración API IZZY: ' . get_class($e));
        $message = in_array((string)($action ?? ''), ['list_integrations', 'create_integration', 'revoke_integration'], true)
            ? 'No se pudo completar la operación. Verifique en DB_MAIN la tabla api_integrations del script DB_Cambios.sql.'
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
        else if(typeof window.swal==='function')window.swal({title:type==='error'?'Error':'API',text:message,icon:type||'success',button:'Entendido'});
        else console.error(message);
    }
    function post(data){
        data.csrf=csrf;
        return $.ajax({url:endpoint,method:'POST',dataType:'json',data,headers:{'X-CSRF-Token':csrf}}).then(response=>{
            if(!response||!response.ok)return $.Deferred().reject({responseJSON:response}).promise();
            return response;
        });
    }
    // UI de conexiones salientes; nunca se devuelve ni se recupera el token guardado.
    function loadExternal(silent=false){
        if(silent!==true)$('#api_ext_rows').html('<div class="api-loading">Cargando conexiones…</div>');
        post({action:'external_list'}).done(r=>{
            const rows=r.datos.conexiones||[];
            $('#api_ext_rows').html(rows.filter(x=>Number(x.archivado)!==1).map(x=>{
                const id=Number(x.id),active=Number(x.activo)===1;
                const item=(attribute,icon,label,extra='')=>'<button type="button" role="menuitem" class="api-external-action-item '+extra+'" '+attribute+'="'+id+'"><i class="fas '+icon+'" aria-hidden="true"></i><span>'+label+'</span></button>';
                const actions=[
                    item('data-ext-edit','fa-edit','Editar','is-edit'),
                    item('data-ext-operations','fa-cogs','Operaciones API','is-operations'),
                    item('data-ext-test','fa-vial','Probar conexión','is-test'),
                    ...(active && String(x.proveedor).trim().toUpperCase()==='ZYNKO' ? [item('data-ext-whatsapp','fa-paper-plane','Prueba de WhatsApp','is-test')] : []),
                    item('data-ext-check','fa-history','Comprobaciones','is-check'),
                    '<button type="button" role="menuitem" class="api-external-action-item is-history" data-ext-read="history" data-id="'+id+'"><i class="fas fa-book" aria-hidden="true"></i><span>Consultar historial ZYNKO</span></button>',
                    '<button type="button" role="menuitem" class="api-external-action-item is-toggle" data-ext-toggle="'+id+'" data-ext-on="'+(active?0:1)+'"><i class="fas '+(active?'fa-ban':'fa-check-circle')+'" aria-hidden="true"></i><span>'+(active?'Desactivar':'Activar')+'</span></button>',
                    item('data-ext-archive','fa-archive','Archivar','is-danger')
                ].join('');
                return '<article class="api-record-card api-external-record"><div class="api-record-main"><span class="api-record-icon"><i class="fas fa-plug"></i></span><div class="api-record-title"><strong>'+esc(x.nombre)+'</strong><span>'+esc(x.proveedor)+' · '+esc(x.categoria)+(String(x.proveedor||'').toUpperCase()==='ZYNKO'?'':(x.canal_id?' · Recurso: '+esc(x.canal_id):''))+'</span><span>Última comprobación: '+formatDate(x.ultima_prueba)+' · '+esc(x.ultimo_resultado||'No comprobado')+'</span></div></div><div class="api-record-actions api-external-record-actions"><span class="api-status '+(active?'is-active':'is-revoked')+'">'+(active?'Activa':'Inactiva')+'</span><div class="api-external-actions-dropdown"><button type="button" class="btn btn-primary api-external-actions-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Acciones para '+esc(x.nombre)+'"><i class="fas fa-ellipsis-v" aria-hidden="true"></i> Acciones <i class="fas fa-chevron-down api-external-actions-chevron" aria-hidden="true"></i></button><div class="api-external-actions-menu" role="menu" hidden>'+actions+'</div></div></div></article>';
            }).join('')||'<div class="api-empty-state"><i class="fas fa-plug"></i><strong>No hay servicios externos registrados</strong><span>Utilice «Nueva conexión» para registrar un proveedor.</span></div>');
            $('#api_ext_rows').data('connections',rows);
            apiExternalCategories=Array.from(new Map([...(r.datos.categorias||[]).map(x=>({value:String(x),label:String(x)})),...rows.map(x=>({value:String(x.categoria||''),label:String(x.categoria||'')}))].filter(x=>x.value).map(x=>[x.value.toLocaleLowerCase(),x])).values());
        }).fail(e=>{if(silent!==true)notify(e.responseJSON?.mensaje||'No se pudieron cargar las conexiones','error');});
    }
    // Menú de acciones contextual: un solo menú abierto y cierre solo del menú.
    function closeExternalActionsMenus(){
        $('#api_ext_rows .api-external-actions-menu').prop('hidden',true);
        $('#api_ext_rows .api-external-actions-trigger').attr('aria-expanded','false');
        $('#api_ext_rows .api-external-actions-dropdown').removeClass('is-open is-up');
        $('#api_ext_rows .api-external-actions-menu').css('max-height','');
    }
    $('#api_ext_rows').on('click','.api-external-actions-trigger',function(event){
        event.preventDefault();event.stopPropagation();
        const group=$(this).closest('.api-external-actions-dropdown'),open=!group.hasClass('is-open');
        closeExternalActionsMenus();
        if(open){
            const menu=group.find('.api-external-actions-menu');
            group.addClass('is-open');menu.prop('hidden',false);
            // Decidir apertura usando espacio real visible y alto del menú ya renderizado.
            const rect=this.getBoundingClientRect();
            const menuHeight=menu[0].getBoundingClientRect().height;
            const spaceBelow=window.innerHeight-rect.bottom-12;
            const spaceAbove=rect.top-12;
            const upward=spaceBelow<menuHeight && spaceAbove>spaceBelow;
            group.toggleClass('is-up',upward);
            menu.css('max-height',Math.max(100,Math.floor((upward?spaceAbove:spaceBelow)-8))+'px');
            $(this).attr('aria-expanded','true');
        }
    }).on('click','.api-external-actions-menu button',function(){closeExternalActionsMenus();});
    $(document).on('click.apiExternalActions',function(event){
        if(!$(event.target).closest('.api-external-actions-dropdown').length)closeExternalActionsMenus();
    }).on('keydown.apiExternalActions',function(event){
        if(event.key==='Escape'&&$('#api_ext_rows .api-external-actions-dropdown.is-open').length){
            closeExternalActionsMenus();event.stopImmediatePropagation();event.preventDefault();
        }
    });
    $('#api_ext_refresh').on('click',loadExternal);
    $('#api_ext_diagnostics').on('click',()=>post({action:'external_diagnostics'}).done(r=>{const x=r.datos||{};const failures=Object.entries(x.tablas||{}).filter(([k,v])=>!v).map(([k])=>k);notify(failures.length?'Faltan tablas en DB_MAIN: '+failures.join(', '):(!x.cifrado_configurado?'Falta IZZY_EXTERNAL_API_ENCRYPTION_KEY en el entorno PHP de IZZY.':'Tablas y cifrado preparados; cURL '+(x.curl_disponible?'disponible':'NO disponible')+'.'),failures.length||!x.cifrado_configurado?'error':'success');}).fail(e=>notify(e.responseJSON?.mensaje||'No se pudo diagnosticar la instalación','error')));

    $('#api_ext_clear').on('click',()=>{$('#api_ext_id').val(0);$('#api_ext_token').val('');});
    $('#api_ext_form').on('submit',function(e){
        e.preventDefault();const descriptionHtml=syncExternalDescription();
        if(descriptionHtml.length>500){notify('La descripción excede los 500 caracteres permitidos.','error');return;}
        const fields=$(this).serializeArray(),data={action:'external_save'};
        fields.forEach(x=>data[x.name]=x.value);
        const submit=$(this).find('[type=submit]').prop('disabled',true);
        post(data).done(r=>{
            notify(r.mensaje);
            const savedId=Number(r.datos?.id||0);
            if(savedId>0)$('#api_ext_id').val(savedId);
            $('#api_ext_token').val('');
            $('#api_external_modal_title').text('Editar conexión externa');
            loadExternal();
            // Conservar el formulario abierto y sus datos para revisar el registro guardado.
        })
        .fail(e=>notify(e.responseJSON?.mensaje||'No se pudo guardar (HTTP '+(e.status||'desconocido')+'). Revise diagnóstico de API externas.','error'))
        .always(()=>submit.prop('disabled',false));
    });
    const checkState={connectionId:0,rows:[],page:1,limit:10};
    function renderExternalChecks(){
        const target=$('#api_ext_checks');
        const rows=checkState.rows;
        $('#api_ext_checks_section').prop('hidden',checkState.connectionId<1);
        const conn=($('#api_ext_rows').data('connections')||[]).find(x=>Number(x.id)===checkState.connectionId);
        $('#api_ext_checks_heading').text('Historial técnico de comprobaciones'+(conn?' · '+conn.nombre:''));
        const query=String($('#api_ext_checks_search').val()||'').trim().toLowerCase();
        const filtered=rows.filter(h=>!query||[h.ruta,h.descripcion,h.http_status,h.created_at].some(v=>String(v||'').toLowerCase().includes(query)));
        const size=checkState.limit===0?Math.max(1,filtered.length):checkState.limit;
        const pages=Math.max(1,Math.ceil(filtered.length/size));
        checkState.page=Math.min(Math.max(1,checkState.page),pages);
        const start=(checkState.page-1)*size;
        target.html(filtered.slice(start,start+size).map(h=>{
            const success=Number(h.exitoso)===1;
            return '<article class="api-record-card api-check-record"><div class="api-record-main"><span class="api-record-icon '+(success?'is-success':'is-error')+'"><i aria-hidden="true" class="fas '+(success?'fa-check-circle':'fa-exclamation-triangle')+'"></i></span><div class="api-record-title"><strong>'+esc(h.ruta)+' · HTTP '+Number(h.http_status)+'</strong><span>'+formatDate(h.created_at)+' · '+esc(h.descripcion)+'</span></div></div><span class="api-status '+(success?'is-active':'is-revoked')+'">'+(success?'Correcta':'Fallida')+'</span></article>';
        }).join('')||'<div class="api-empty-state"><i class="fas fa-history"></i><strong>'+(rows.length?'Sin resultados para la búsqueda':'Sin comprobaciones registradas')+'</strong><span>'+(rows.length?'Cambie los filtros para consultar las comprobaciones.':'Seleccione «Comprobaciones» en una conexión para consultar su historial técnico.')+'</span></div>');
        $('#api_ext_checks_count').text('Mostrando '+(filtered.length?start+1:0)+' a '+Math.min(start+size,filtered.length)+' de '+filtered.length+' registros');
        const controls=$('#api_ext_checks_pages').empty();
        const append=(label,p,disabled,selected)=>controls.append($('<button type="button" class="api-page-button">').text(label).attr('data-check-page',p).prop('disabled',disabled).toggleClass('is-current',!!selected));
        append('« Inicio',1,checkState.page===1,false);
        append('‹ Anterior',checkState.page-1,checkState.page===1,false);
        const first=Math.max(1,Math.min(checkState.page-2,pages-4));
        for(let i=first;i<=Math.min(pages,first+4);i++)append(String(i),i,false,i===checkState.page);
        append('Siguiente ›',checkState.page+1,checkState.page===pages,false);
        append('Final »',pages,checkState.page===pages,false);
    }
    function loadExternalChecks(id,keepPage,silent=false){
        if(!id)return;
        post({action:'external_checks',id}).done(r=>{
            checkState.connectionId=id;checkState.rows=r.datos.historial||[];
            if(!keepPage)checkState.page=1;
            renderExternalChecks();
        }).fail(e=>{if(!silent)notify(e.responseJSON?.mensaje||'No fue posible consultar el historial técnico.','error');});
    }
    $('#api_ext_checks_search').on('input',()=>{checkState.page=1;renderExternalChecks();});
    $('#api_ext_checks_limit').on('change',function(){checkState.limit=Number($(this).val());checkState.page=1;renderExternalChecks();});
    $('#api_ext_checks_pages').on('click','[data-check-page]',function(){checkState.page=Number($(this).attr('data-check-page'))||1;renderExternalChecks();});
    function safeAuthDiagnostic(result){
        if(!result || Number(result.http_status)!==401)return '';
        const allowed={
            authorization_ausente_o_formato:'ZYNKO indica que falta el encabezado Authorization Bearer o tiene un formato incorrecto.',
            formato_credencial_invalido:'ZYNKO indica que el formato de la API Key es inválido.',
            clave_no_encontrada_o_revocada:'ZYNKO indica que no encontró una API Key activa coincidente o que fue revocada.',
            respuesta_no_identificada:'ZYNKO respondió 401, pero el JSON no contiene una de las tres causas conocidas.'
        };
        const reason=String(result.categoria_rechazo_401||'respuesta_no_identificada');
        const integrity=result.integridad_credencial||{};
        const local=[];
        if(integrity.contiene_prefijo_bearer===true)local.push('la credencial guardada incluye Bearer');
        if(integrity.comillas_externas===true)local.push('incluye comillas externas');
        if(integrity.espacios_extremos===true)local.push('tiene espacios al inicio o final');
        if(integrity.caracteres_control===true)local.push('contiene caracteres de control');
        if(integrity.espacios_internos===true && integrity.contiene_prefijo_bearer!==true)local.push('contiene espacios internos');
        if(integrity.descifrado_no_vacio===false)local.push('el descifrado está vacío');
        const transport=result.diagnostico_transporte||{};
        const details=[];
        if(typeof transport.autorizacion_en_solicitud_curl==='boolean')details.push('Authorization en solicitud cURL: '+(transport.autorizacion_en_solicitud_curl?'sí':'no'));
        if(typeof transport.bearer_exacto_en_solicitud_curl==='boolean')details.push('Bearer exacto en cURL: '+(transport.bearer_exacto_en_solicitud_curl?'sí':'no'));
        if(typeof transport.url_esperada_coincide==='boolean')details.push('URL efectiva esperada: '+(transport.url_esperada_coincide?'sí':'no'));
        if(Number.isInteger(transport.redirecciones_seguidas))details.push('Redirecciones seguidas: '+transport.redirecciones_seguidas);
        if(typeof transport.respuesta_redireccion==='boolean')details.push('Respuesta HTTP de redirección: '+(transport.respuesta_redireccion?'sí':'no'));
        const base=(allowed[reason]||allowed.respuesta_no_identificada);
        return base+(local.length?' Revisión local: '+local.join('; ')+'.':'')+(details.length?' Transporte: '+details.join(' · ')+'.':'');
    }
    function safeForbiddenDiagnostic(result){
        if(!result || Number(result.http_status)!==403)return '';
        const labels={
            politica_desactivada:'ZYNKO indica que la política de acceso de la API Key está desactivada.',
            ip_no_autorizada:'ZYNKO rechazó la IP de salida de IZZY. Verificar IP/CIDR registrada, sin modificarla automáticamente.',
            origen_no_autorizado:'ZYNKO rechazó el origen web registrado.',
            https_requerido:'ZYNKO exige HTTPS para el origen recibido.',
            permiso_insuficiente:'ZYNKO indica que faltan permisos para esta consulta.',
            plan_sin_acceso_api:'El plan asignado en ZYNKO no incluye API. Revise el módulo API del plan de la empresa dueña de esta clave.',
            suscripcion_sin_asignar:'La empresa dueña de esta clave no tiene suscripción asignada en ZYNKO.',
            suscripcion_inactiva:'La suscripción de la empresa dueña de esta clave no está vigente en ZYNKO.',
            plan_inactivo:'El plan asignado a la empresa dueña de esta clave está inactivo o no existe en ZYNKO.',
            rechazo_no_identificado:'ZYNKO respondió HTTP 403, pero el mensaje JSON no coincide con las causas conocidas.'
        };
        const category=String(result.categoria_rechazo_403||'rechazo_no_identificado');
        let message=labels[category] || labels.rechazo_no_identificado;
        if(category==='ip_no_autorizada'){
            const info=result.diagnostico_ip||{};
            const validIp=v=>typeof v==='string' && v.length<=50 && /^[0-9a-fA-F:.\/]+$/.test(v);
            const received=validIp(info.received_ip)?info.received_ip:'';
            const allowed=Array.isArray(info.allowed_ips)?info.allowed_ips.filter(validIp).slice(0,30):[];
            const source=info.source==='REMOTE_ADDR'?'REMOTE_ADDR':'sin confirmar';
            if(received)message+=' IP recibida por ZYNKO: '+received+'.';
            else message+=' ZYNKO no devolvió una IP recibida válida.';
            message+=' IP/CIDR autorizadas: '+(allowed.length?allowed.join(', '):'no informadas')+'.';
            message+=' Fuente: '+source+'.';
            message+=' Compare la IP recibida con las entradas autorizadas; si es una IP distinta, revise la salida real del entorno (Laragon o WEB01).';
        }
        return message;
    }
    const waDialog=document.getElementById('api_wa_dialog');
    const waForm=document.getElementById('api_wa_form');
    const waRecipients=document.getElementById('api_wa_recipients');
    const waMessage=document.getElementById('api_wa_message');
    const waCounter=document.getElementById('api_wa_message_count');
    const waMessageLimit=document.getElementById('api_wa_message_limit');
    const $waSetup=$('#api_wa_setup');
    const $waFeedback=$('#api_wa_feedback');
    const $waSubmit=$('#api_wa_submit');
    let waConnection=0, waOperation='', waSending=false, waReady=false, waOpening=false;
    let waReturnFocus=null;

    function newWaOperation(){
        const hex=Array.from(crypto.getRandomValues(new Uint8Array(16)),byte=>byte.toString(16).padStart(2,'0')).join('');
        return hex.replace(/^(........)(....)(....)(....)(............)$/,'$1-$2-$3-$4-$5');
    }
    function waFeedback(message,error=false){
        $waFeedback.text(message).toggleClass('is-error',error);
    }
    function waError(error){
        const data=error.responseJSON?.datos;
        return safeAuthDiagnostic(data) || safeForbiddenDiagnostic(data) ||
            error.responseJSON?.mensaje || 'No se pudo confirmar el resultado. Vuelva a pulsar Enviar prueba con los mismos datos.';
    }
    function updateWaMode(){
        $('#api_wa_recipients_hint').text(waForm.elements.mode.value==='bulk'?
            'Ingrese entre 2 y 100 números, uno por línea, con código de país.':
            'Ingrese un número completo con código de país.');
    }
    function updateWaMessageCount(){
        const length=Array.from(waMessage.value).length;
        const limit=Number(waMessage.dataset.limit);
        const tooLong=length>limit;
        waCounter.textContent=length+' de '+limit;
        waCounter.classList.toggle('is-near-limit',length>=limit*0.9 && !tooLong);
        waCounter.classList.toggle('is-over-limit',tooLong);
        waMessageLimit.hidden=!tooLong;
        if(tooLong)waMessage.setAttribute('aria-invalid','true');
        else waMessage.removeAttribute('aria-invalid');
        waMessage.setCustomValidity(tooLong?waMessageLimit.textContent:'');
        $waSubmit.prop('disabled',!waReady||waSending||tooLong);
        return !tooLong;
    }
    waMessage.addEventListener('input',updateWaMessageCount);

    function checkWaSetup(id){
        waReady=false;
        $waSubmit.prop('disabled',true);
        $waSetup.empty();
        return post({action:'external_wa_ready',id}).done(()=>{
            waReady=true;
            updateWaMessageCount();
        }).fail(error=>{
            const setup=error.responseJSON?.datos?.code;
            const needsMigration=setup==='WA_TEST_STORAGE_REQUIRED';
            $waSetup.append(
                $('<strong>').text(needsMigration?'Activación pendiente':'Pruebas no disponibles'),
                $('<span>').text(needsMigration?'Falta la tabla que guarda los envíos de prueba y evita duplicados. Ejecutá el bloque ZYNKO WhatsApp de DB_Cambios.sql en la base principal de IZZY (DB_MAIN). Después, volvé a abrir este modal.':waError(error))
            );
            if(needsMigration)$waSetup.append(
                $('<small>').text('DB_Cambios.sql · bloque ZYNKO WhatsApp')
            );
        });
    }
    function dispatchWa(data){
        if(waSending||!waReady)return;
        waSending=true;
        $waSubmit.prop('disabled',true);
        waFeedback('Contactando a ZYNKO…');
        post(data).done(r=>{
            const op=r.datos;
            if(op.state==='accepted'){
                waFeedback('En cola en ZYNKO.');
                if(waOperation===op.id){waForm.reset();waRecipients.textContent='';waOperation=newWaOperation();updateWaMode();}
            }else{
                waFeedback(op.detail+' Para reintentar, vuelva a pulsar Enviar prueba con los mismos datos.',true);
            }
        }).fail(error=>waFeedback(waError(error),true)).always(()=>{
            waSending=false;
            updateWaMessageCount();
        });
    }
    $('#api_ext_rows').on('click','[data-ext-whatsapp]',function(){
        if(waOpening)return;
        if(waSending){notify('Todavía hay un envío en proceso.','info');return;}
        const record=($('#api_ext_rows').data('connections')||[]).find(row=>Number(row.id)===Number(this.dataset.extWhatsapp));
        if(!record)return;
        waConnection=Number(record.id);
        waOperation=newWaOperation();
        waForm.reset();
        updateWaMode();
        updateWaMessageCount();
        $('#api_wa_connection').text(record.nombre);
        waRecipients.textContent='';
        waFeedback('');
        waOpening=true;
        const trigger=this;
        trigger.disabled=true;
        trigger.setAttribute('aria-busy','true');
        waReturnFocus=trigger.closest('.api-external-actions-dropdown')?.querySelector('.api-external-actions-trigger') || trigger;
        checkWaSetup(waConnection).always(()=>{
            waOpening=false;
            trigger.disabled=false;
            trigger.removeAttribute('aria-busy');
            if(waDialog.open || !trigger.isConnected)return;
            closeExternalActionsMenus();
            waDialog.showModal();
        });
    });
    waDialog.addEventListener('close',()=>waReturnFocus?.focus({preventScroll:true}));
    waRecipients.addEventListener('input',()=>waRecipients.removeAttribute('aria-invalid'));
    $(waForm).on('change','[name="mode"]',updateWaMode).on('submit',function(event){
        event.preventDefault();
        if(!updateWaMessageCount())return;
        const recipients=waRecipients.innerText.trim();
        if(!recipients || recipients.length>1900){
            waFeedback(recipients?'La lista de números es demasiado larga.':'Ingresá al menos un número internacional.',true);
            waRecipients.setAttribute('aria-invalid','true');
            waRecipients.focus();
            return;
        }
        dispatchWa({action:'external_wa_send',id:waConnection,operation:waOperation,
            ...Object.fromEntries(new FormData(waForm)),recipients});
    });

    const historyDialog=document.getElementById('api_zynko_history');
    const historyStatus=$('#api_zynko_history_status');
    const historyRows=$('#api_zynko_history_rows');
    const historyPrev=$('#api_zynko_history_prev');
    const historyNext=$('#api_zynko_history_next');
    const historyLabels={queued:'En cola',sending:'Enviando',retrying:'Reintentando',sent:'Enviado',
        delivered:'Entregado',read:'Leído',failed:'Fallido',partial:'Parcial',uncertain:'Incierto',pending:'Pendiente'};
    let historyConnection=0, historyPage=1, historyRequest=0;
    function loadZynkoHistory(page){
        const serial=++historyRequest;
        historyStatus.text('Consultando el historial de esta clave en ZYNKO…');
        historyRows.empty();
        historyPrev.add(historyNext).prop('disabled',true);
        post({action:'external_read',id:historyConnection,tipo:'history',page}).done(r=>{
            if(serial!==historyRequest || !historyDialog.open)return;
            const history=r.datos.history;
            historyPage=history.page;
            historyStatus.text(history.total ? history.total+' envíos · Página '+history.page+' de '+history.pages : 'Esta clave todavía no tiene envíos registrados en ZYNKO.');
            historyPrev.prop('disabled',history.page<=1);
            historyNext.prop('disabled',history.page>=history.pages || history.page>=100000);
            for(const item of history.rows){
                const status=historyLabels[item.status] || item.status || 'Sin estado';
                const queue=item.queue && item.queue!==item.status ? ' · Cola: '+(historyLabels[item.queue] || item.queue) : '';
                const main=$('<div class="api-record-title">').append($('<strong>').text(item.recipient || 'Destinatario'),
                    $('<span>').text((item.date ? formatDate(item.date)+' · ' : '')+'UUID '+item.uuid));
                historyRows.append($('<article class="api-record-card">').append(main,$('<span class="api-status">').text(status+queue)));
            }
        }).fail(e=>{
            if(serial!==historyRequest || !historyDialog.open)return;
            const data=e.responseJSON?.datos;
            historyStatus.text(safeAuthDiagnostic(data) || safeForbiddenDiagnostic(data) || e.responseJSON?.mensaje || 'No se pudo consultar ZYNKO.');
        });
    }
    historyDialog.addEventListener('close',()=>{historyRequest++;});
    historyPrev.on('click',()=>loadZynkoHistory(historyPage-1));
    historyNext.on('click',()=>loadZynkoHistory(historyPage+1));
    $('#api_ext_rows').on('click','[data-ext-read]',function(){
        historyConnection=Number($(this).attr('data-id'));
        const connection=($('#api_ext_rows').data('connections')||[]).find(x=>Number(x.id)===historyConnection);
        $('#api_zynko_history_connection').text(connection?.nombre || 'ZYNKO');
        historyDialog.showModal();
        loadZynkoHistory(1);
    });
    const pendingConnectionTests=new Set();
    function connectionTestSummary(tests){
        if(!tests?.canales)return '';
        const history=tests.historial?'Historial HTTP '+Number(tests.historial.http_status):'Historial no consultado';
        return 'Canales HTTP '+Number(tests.canales.http_status)+' · '+history+'. ';
    }

    $('#api_ext_rows').on('click','[data-ext-edit]',function(){
        const id=Number($(this).data('ext-edit')),x=($('#api_ext_rows').data('connections')||[]).find(r=>Number(r.id)===id);
        if (!x)return;$('#api_ext_id').val(id);$('#api_ext_name').val(x.nombre);$('#api_ext_provider').val(x.proveedor||'');$('#api_ext_url').val(x.url_base||'');$('#api_ext_channel').val(x.canal_id||'');$('#api_ext_token').val('');$('#api_ext_config').val(x.configuracion_json||'{}');setExternalCategory(x.categoria||'');setExtSelect('#api_ext_auth',x.tipo_auth||'');
        setExternalDescription(x.descripcion||'');
        const documentTypes=(()=>{try{return JSON.parse(x.configuracion_json||'{}').izzy_document_sharing?.types||[];}catch(_){return [];}})();
        ['factura','cotizacion'].forEach(type=>$('#api_doc_'+type).prop('checked',documentTypes.includes(type)));
        openExternalModal(true);
    }).on('click','[data-ext-toggle],[data-ext-test],[data-ext-check],[data-ext-archive]',function(){
        const b=$(this),id=Number(b.attr('data-ext-toggle')||b.attr('data-ext-test')||b.attr('data-ext-check')||b.attr('data-ext-archive'));
        const action=b.is('[data-ext-toggle]')?'external_toggle':b.is('[data-ext-test]')?'external_test':b.is('[data-ext-check]')?'external_checks':'external_archive';
        if(action==='external_archive'&&!window.confirm('¿Archivar esta conexión? Los datos históricos permanecerán.'))return;
        const isTest=action==='external_test';
        if(isTest){
            if(pendingConnectionTests.has(id))return;
            pendingConnectionTests.add(id);
            b.prop('disabled',true);
        }
        post({action,id,activo:b.attr('data-ext-on')||0}).done(r=>{
            if(action==='external_checks'){
                checkState.connectionId=id;
                checkState.rows=r.datos.historial||[];
                checkState.page=1;
                $('#api_ext_checks_search').val('');
                renderExternalChecks();
                const heading=document.getElementById('api_ext_checks_heading');
                heading.focus({preventScroll:true});
                heading.scrollIntoView({block:'center',behavior:'instant'});
            } else if(isTest){
                const tests=r.datos;
                const summary=connectionTestSummary(tests);
                if(tests?.canal?.canal_detectado){
                    notify(summary+'Canal WhatsApp conectado.', 'success');
                } else {
                    notify(summary+(tests?.canal?.motivo || 'Conviene comprobar el canal WhatsApp antes de enviar.'), 'warning');
                }
                loadExternal(true);
                loadExternalChecks(id,true,true);
            } else {
                notify(r.mensaje);
                loadExternal();
            }
        }).fail(e=>{
            const tests=e.responseJSON?.datos;
            const failedTest=tests?.canales?.ok ? tests.historial : tests?.canales;
            const diagnosis=isTest?(safeAuthDiagnostic(failedTest) || safeForbiddenDiagnostic(failedTest)):'';
            const message=diagnosis || failedTest?.detalle || e.responseJSON?.mensaje || 'Operación no completada.';
            notify((isTest?connectionTestSummary(tests):'')+message,'error');
            if(isTest)loadExternalChecks(id,true,true);
        }).always(()=>{
            if(isTest){
                pendingConnectionTests.delete(id);
                b.prop('disabled',false);
            }
        });
    });
    $('[data-api-tab="external"]').on('click',loadExternal);
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
        const query=String($('[data-api-results="authorizations"] .api-results-query').val()||'').trim().toLocaleLowerCase('es');
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
        }).join('')||'<div class="api-empty-state"><i class="fas fa-building"></i><strong>No hay empresas autorizadas para mostrar</strong><span>Autorice una empresa o cambie los filtros de búsqueda.</span></div>');
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
    
    $('#api_authorizations_status').on('change',renderAuthorizations);
    $('#api_authorizations_system').on('change',renderAuthorizations);
    if($.fn.select2)$('#api_authorizations_status').select2({width:'100%',minimumResultsForSearch:0});
    if($.fn.select2)$('#api_authorizations_system').select2({width:'100%',minimumResultsForSearch:0});
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
    function closeModal(modal){
        if(modal.is('#api_external_modal'))closeExternalDropdowns();
        const focused=document.activeElement;
        if(focused && modal[0] && modal[0].contains(focused))focused.blur();
        modal.removeClass('is-open').attr('aria-hidden','true');
        if(!$('.api-modal-backdrop.is-open').length)$('body').removeClass('api-modal-open');
    }
    function prepararSelectVencimiento(){
        const select=$('#api_integration_expiry');
        if(!select.length)return;
        if(select.hasClass('select2-hidden-accessible'))select.select2('destroy');
        if($.fn.select2){
            select.select2({width:'100%',dropdownParent:integrationModal,minimumResultsForSearch:0,allowClear:false,placeholder:'Seleccione vencimiento',language:{noResults:()=> 'No hay opciones disponibles'}});
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
    $('.api-route-grid').on('click','.api-route-card details summary',function(event){
        event.preventDefault();
        const details=$(this).closest('details'),card=details.closest('.api-route-card');
        // Nunca abrir <details> en la cuadrícula: mantiene las tarjetas inmóviles.
        details.prop('open',false);
        $('#api_route_modal_title').text(card.find('.api-route-top strong').first().text());
        const source=details.clone();source.removeAttr('open');source.find('summary').remove();
        const lead=$('<div class="api-route-modal-intro">').append($('<strong>').text(card.find('.api-route-top strong').first().text())).append($('<p>').text(card.children('small').first().text()));
        const method=card.find('.api-route-top .api-method').first().text().trim();
        const path=card.find('.api-route-top strong').first().text().trim();
        const external=path.indexOf('/integrations/')===0;
        const mobile=path.indexOf('/auth/')===0||path.indexOf('/navigation/')===0;
        const protectedRoute=path!=='/auth/login.php'&&path!=='/auth/refresh.php'&&path!=='/integrations/token.php';
        const guide=$('<section class="api-route-modal-guide">');
        const purpose=$('<article>').append('<h3><i class="fas fa-route"></i> Cómo consumir esta ruta</h3>');
        purpose.append($('<p>').text('Método '+method+' · Dirección '+path));
        purpose.append($('<p>').text(protectedRoute?'Envíe el token Bearer vigente en el encabezado Authorization.':'Solicite esta ruta por HTTPS y envíe las credenciales correspondientes en el cuerpo JSON.'));
        const security=$('<article>').append('<h3><i class="fas fa-shield-alt"></i> Seguridad y alcance</h3>');
        security.append($('<p>').text(external?'Solo para integraciones externas autorizadas. El Client Secret debe permanecer en su servidor.':mobile?'Esta ruta corresponde a sesiones individuales de usuarios de la APK. No utilice aquí Client ID ni Client Secret.':'El token determina la empresa y sus permisos; nunca envíe el nombre de una base de datos.'));
        const response=$('<article>').append('<h3><i class="fas fa-clipboard-check"></i> Qué verificar</h3>');
        response.append($('<p>').text('Compruebe la respuesta HTTP, la autorización y el campo de datos devuelto. Un error de permisos o autenticación no debe convertirse en una operación exitosa.'));
        guide.append(purpose,security,response);
        $('#api_route_modal_body').empty().append(lead).append($('<div class="api-route-modal-example">').append(source.children())).append(guide);
        openModal(routeModal,'[data-api-route-close]');
    });
    $(document).on('keydown',event=>{if(event.key==='Escape'){if($('#api_external_modal').hasClass('is-open'))return; if(routeModal.hasClass('is-open'))closeModal(routeModal);else if(secretModal.hasClass('is-open'))closeModal(secretModal);else if(integrationModal.hasClass('is-open'))closeModal(integrationModal);}});
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
    // Toolbar de resultados compartida: trabaja con las tarjetas existentes (no tablas).
    // Se aplica después de cada render AJAX mediante MutationObserver, sin modificar los eventos de cada tarjeta.
    const listTargets={integrations:'#api_integrations_rows',ext:'#api_ext_rows',authorizations:'#api_authorizations_rows',sessions:'#api_sessions_rows'};
    const resultState={};
    Object.keys(listTargets).forEach(k=>{resultState[k]={page:1,limit:'10',view:'detail',busy:false};});
    function exportRows(key){
        const el=$(listTargets[key]);
        const toolbar=$('[data-api-results="'+key+'"]');
        const filter=String(toolbar.find('.api-results-query').val()||'').trim().toLocaleLowerCase('es');
        return el.children('article.api-record-card').filter(function(){return !filter||$(this).text().toLocaleLowerCase('es').includes(filter);}).toArray();
    }
    function updateResults(key){
        const state=resultState[key],list=$(listTargets[key]);if(!list.length||state.busy)return;
        const toolbar=$('[data-api-results="'+key+'"]');
        const records=exportRows(key),n=records.length;
        const all=list.children('article.api-record-card');
        const count=Number(state.limit)==0?n:Math.max(1,Number(state.limit));
        const pages=Math.max(1,Math.ceil(n/count));state.page=Math.min(Math.max(1,state.page),pages);
        state.busy=true;
        all.hide().attr('aria-hidden','true');
        const first=(state.page-1)*count;$(records.slice(first,first+count)).show().attr('aria-hidden','false');
        list.toggleClass('api-view-tiles',state.view==='tiles');
        toolbar.find('.api-view-option').removeClass('is-active').filter('[data-view="'+state.view+'"]').addClass('is-active');
        toolbar.find('.api-size-menu [data-size="all"]').text('Todos ('+n+')');
        toolbar.find('.api-size-current').text(state.limit==='0'?'Todos ('+n+')':state.limit);
        toolbar.find('.api-size-menu [data-size]').attr('aria-selected','false').filter('[data-size="'+(state.limit==='0'?'all':state.limit)+'"]').attr('aria-selected','true');
        const pagination=$('[data-api-pagination="'+key+'"]');pagination.empty();
        pagination.append($('<span class="api-page-summary">').text(n?'Mostrando '+(first+1)+' a '+Math.min(first+count,n)+' de '+n+' registros':'Mostrando 0 a 0 de 0 registros'));
        const actions=$('<div class="api-page-buttons">');
        const button=(text,page,disabled,selected)=>$('<button type="button">').addClass('api-page-button').toggleClass('is-current',!!selected).attr('data-go-page',page).prop('disabled',disabled).text(text);
        actions.append(button('« Inicio',1,state.page===1,false));
        actions.append(button('‹ Anterior',state.page-1,state.page===1,false));
        const start=Math.max(1,Math.min(state.page-2,pages-4)),end=Math.min(pages,start+4);
        for(let i=start;i<=end;i++)actions.append(button(String(i),i,false,i===state.page));
        actions.append(button('Siguiente ›',state.page+1,state.page===pages,false));
        actions.append(button('Final »',pages,state.page===pages,false));pagination.append(actions);
        state.busy=false;
    }
    Object.keys(listTargets).forEach(key=>{
        const node=document.querySelector(listTargets[key]);if(!node)return;
        const toolbar=$('[data-api-results="'+key+'"]');
        // Selector autónomo: evita duplicidad causada por inicializadores globales de Select2.
        const sizeControl=toolbar.find('.api-size-control');
        const sizeToggle=sizeControl.find('.api-size-toggle');
        const sizeMenu=sizeControl.find('.api-size-menu');
        const closeSizeMenu=()=>{sizeMenu.prop('hidden',true);sizeToggle.attr('aria-expanded','false');};
        sizeToggle.on('click',function(e){
            e.stopPropagation();$('.api-size-menu').not(sizeMenu).prop('hidden',true);
            $('.api-size-toggle').not(sizeToggle).attr('aria-expanded','false');
            const open=sizeMenu.prop('hidden');sizeMenu.prop('hidden',!open);sizeToggle.attr('aria-expanded',String(open));
        });
        sizeControl.on('click','.api-size-menu [data-size]',function(e){
            e.stopPropagation();const value=String($(this).attr('data-size'));
            resultState[key].limit=value==='all'?'0':value;resultState[key].page=1;
            closeSizeMenu();updateResults(key);sizeToggle.trigger('focus');
        });
        sizeControl.on('keydown',function(e){if(e.key==='Escape'){closeSizeMenu();sizeToggle.trigger('focus');}});
        toolbar.on('input','.api-results-query',()=>{resultState[key].page=1;updateResults(key);});
        toolbar.on('click','.api-view-option',function(){resultState[key].view=$(this).attr('data-view')==='tiles'?'tiles':'detail';updateResults(key);});
        $('[data-api-pagination="'+key+'"]').on('click','[data-go-page]',function(){resultState[key].page=Number($(this).attr('data-go-page'))||1;updateResults(key);});
        toolbar.on('click','.api-export-excel',()=>{
            const cards=exportRows(key),data=cards.map(x=>$(x).find('.api-record-title').text().replace(/\s+/g,' ').trim());
            const csv='\ufeff"Registro"\r\n'+data.map(x=>'"'+x.replace(/"/g,'""')+'"').join('\r\n');
            const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'});const url=URL.createObjectURL(blob);
            const a=document.createElement('a');a.href=url;a.download='IZZY_'+key+'.csv';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);
        });
        toolbar.on('click','.api-export-pdf',()=>{
            if(!window.pdfMake||typeof window.pdfMake.createPdf!=='function'){notify('El visor PDF no está disponible en esta pantalla.','error');return;}
            const items=exportRows(key).map(x=>({text:$(x).find('.api-record-title').text().replace(/\s+/g,' ').trim(),margin:[0,5,0,5]}));
            const definition={content:[{text:'IZZY · '+key.toUpperCase(),style:'header'},...items],styles:{header:{fontSize:16,bold:true,margin:[0,0,0,12]}},defaultStyle:{fontSize:9}};
            window.pdfMake.createPdf(definition).open();
        });
        const observer=new MutationObserver(mutations=>{
            if(resultState[key].busy)return;
            // Solo recalcular cuando se agregan/quitan nodos de resultado; ocultar tarjetas no causa mutaciones en childList.
            if(mutations.some(m=>m.type==='childList')){resultState[key].page=1;updateResults(key);}
        });observer.observe(node,{childList:true});updateResults(key);
    });
    $(document).on('click.apiSizeMenu',function(e){if(!$(e.target).closest('.api-size-control').length){$('.api-size-menu').prop('hidden',true);$('.api-size-toggle').attr('aria-expanded','false');}});
    function setExtSelect(selector,value){
        const el=$(selector),val=String(value||'');
        if(val && !el.find('option').filter(function(){return this.value===val;}).length)el.append(new Option(val,val,false,false));
        el.val(val).trigger('change');
    }
    // Select2 con tags: categorías existentes + nuevas categorías administrables.
    const apiDefaultCategories=[{value:'whatsapp',label:'WhatsApp'},{value:'sms',label:'SMS'},
        {value:'correo',label:'Correo electrónico'},{value:'divisas',label:'Divisas'},
        {value:'pagos',label:'Pagos'},{value:'otro',label:'Otro'}];
    let apiExternalCategories=apiDefaultCategories.slice();
    const extCategory=$('#api_ext_category');
    function setExternalCategory(value){
        const v=String(value||'');
        if(v&&!extCategory.find('option').filter(function(){return this.value===v;}).length)extCategory.append(new Option(v,v,false,false));
        extCategory.val(v||null).trigger('change');
    }
    function closeExternalCategoryMenu(){
        if(extCategory.hasClass('select2-hidden-accessible'))extCategory.select2('close');
    }
    function initExternalCategorySelect(){
        if(!$.fn.select2)return;
        if(extCategory.hasClass('select2-hidden-accessible'))extCategory.select2('destroy');
        const current=extCategory.val();extCategory.empty().append(new Option('','',false,false));
        const known=new Set();
        for(const entry of apiExternalCategories){
            if(!entry.value||known.has(entry.value.toLowerCase()))continue;
            known.add(entry.value.toLowerCase());extCategory.append(new Option(entry.label,entry.value,false,false));
        }
        if(current&&!known.has(String(current).toLowerCase()))extCategory.append(new Option(current,current,false,false));
        extCategory.val(current||null);
        extCategory.select2({width:'100%',dropdownParent:extCategory.parent('label'),tags:true,
            placeholder:'Seleccione o escriba una categoría',allowClear:false,
            createTag:function(params){const term=String(params.term||'').trim();
                if(!term||term.length>40)return null;
                return{id:term,text:term,newTag:true};
            },language:{noResults:()=> 'Escriba el nombre de una nueva categoría'}});
    }
    // Editor enriquecido local; el servidor valida el HTML permitiendo solo etiquetas básicas.
    const extDescriptionEditor=$('#api_ext_description_editor'),extDescriptionHidden=$('#api_ext_description');
    function setExternalDescription(html){
        // Parsear sin ejecutar código de descripciones históricas.
        const parser=new DOMParser();const parsed=parser.parseFromString('<div>'+String(html||'')+'</div>','text/html');
        const source=parsed.body.firstElementChild, destination=document.createElement('div');
        const allowed=new Set(['P','BR','STRONG','B','EM','I','UL','OL','LI']);
        function appendClean(from,to){
            for(const child of from.childNodes){
                if(child.nodeType===3){to.appendChild(document.createTextNode(child.textContent));continue;}
                if(child.nodeType!==1 || ['SCRIPT','STYLE','IFRAME','OBJECT','SVG','IMG','FORM','VIDEO','AUDIO'].includes(child.tagName))continue;
                if(allowed.has(child.tagName)){const el=document.createElement(child.tagName.toLowerCase());appendClean(child,el);to.appendChild(el);}
                else appendClean(child,to);
            }
        }
        if(source)appendClean(source,destination);
        extDescriptionEditor.empty().append(destination.childNodes);syncExternalDescription();
    }
    function syncExternalDescription(){
        // Evita copiar HTML de origen externo con atributos, estilos o scripts.
        const scratch=$('<div>').html(extDescriptionEditor.html());
        scratch.find('script,style,iframe,object,embed,svg,form,img,video,audio').remove();
        scratch.find('*').each(function(){
            const element=$(this);const tag=this.tagName.toLowerCase();
            if(!['div','p','br','b','strong','i','em','ul','ol','li'].includes(tag)){element.replaceWith(element.contents());return;}
            [...this.attributes].forEach(a=>this.removeAttribute(a.name));
        });
        let value=scratch.html().replace(/<div>/g,'<p>').replace(/<\/div>/g,'</p>');
        extDescriptionHidden.val(value);
        return value;
    }
    $('#api_external_modal .api-rich-toolbar').on('mousedown','button',e=>e.preventDefault());
    $('#api_external_modal .api-rich-toolbar').on('click','[data-api-format]',function(){
        extDescriptionEditor.trigger('focus');
        document.execCommand($(this).attr('data-api-format'),false,null);
        syncExternalDescription();
    });
    extDescriptionEditor.on('input blur',syncExternalDescription);
    extDescriptionEditor.on('paste',function(e){
        // Pegado en texto plano; formato se aplica solo con controles del editor.
        const clipboard=e.originalEvent&&e.originalEvent.clipboardData;
        if(!clipboard)return;
        e.preventDefault();document.execCommand('insertText',false,clipboard.getData('text/plain'));
    });
    function closeExternalDropdowns(){
        if($('#api_ext_auth').hasClass('select2-hidden-accessible'))$('#api_ext_auth').select2('close');
        closeExternalCategoryMenu();
    }
    function initExtSelects(){
        // Select2 se conserva exclusivamente para autenticación, con el estilo ya usado en IZZY.
        if(!$.fn.select2)return;
        initExternalCategorySelect();
        const auth=$('#api_ext_auth');
        if(auth.hasClass('select2-hidden-accessible'))auth.select2('destroy');
        auth.select2({width:'100%',dropdownParent:auth.parent('label'),minimumResultsForSearch:0,
            dropdownAutoWidth:false,placeholder:'Seleccione autenticación',allowClear:false});
        auth.off('select2:open.apiAuthSearch').on('select2:open.apiAuthSearch',function(){
            const field=auth.parent('label').find('.select2-container--open .select2-search__field');
            field.attr('placeholder','Buscar autenticación...').prop('disabled',false);
            if(field.length)field.trigger('focus');
        });
    }
    // No cerrar Select2 en scroll: Select2 provoca desplazamientos internos al abrirse
    // y ese cierre automático impedía usar categorías o autenticación.

    function openExternalModal(edit){
        if(!edit){$('#api_ext_form')[0].reset();$('#api_ext_id').val(0);$('#api_external_modal_title').text('Nueva conexión externa');setExternalCategory('');setExtSelect('#api_ext_auth','');setExternalDescription('');$('#api_ext_config').val('{}');}
        else $('#api_external_modal_title').text('Editar conexión externa');
        openModal($('#api_external_modal'),'#api_ext_name');initExtSelects();
    }
    $('#api_ext_new').on('click',()=>openExternalModal(false));
    $('[data-api-external-close]').on('click',()=>{closeExternalDropdowns();closeModal($('#api_external_modal'));});
    $('#api_ext_clear').off('click').on('click',()=>openExternalModal(false));
    // Al tocar el fondo, mantener modal abierto y dar confirmación visual suave.
    $('.api-modal-backdrop').on('click.apiOutsideFeedback',function(e){
        if(e.target!==this)return;
        const dialog=$(this).children('.api-modal');
        dialog.removeClass('api-outside-feedback');void dialog[0].offsetWidth;
        dialog.addClass('api-outside-feedback');
    });
    // Evita el efecto de crecimiento incluso cuando Select2 termina de inicializar.
    // Los clics fuera del cuadro NO cierran modales; solo X, Cancelar/Cerrar o Escape.
    $(document).on('keydown.apiModalEscape',e=>{
        if(e.key!=='Escape')return;
        if($('.select2-container--open').length){closeExternalDropdowns();return;}
        const open=$('.api-modal-backdrop.is-open').last();if(open.length)closeModal(open);
    });
    let apiOperationConnection=0;
    function loadOperations(){
        if(!apiOperationConnection)return;
        post({action:'external_operations',connection_id:apiOperationConnection}).done(r=>{
            const rows=r.datos.operaciones||[];
            $('#api_operations_list').data('operations',rows).html(rows.map(x=>'<article class="api-record-card api-operation-card"><div class="api-record-title"><strong>'+esc(x.nombre)+'</strong><span>'+esc(x.metodo)+' · '+esc(x.ruta)+'</span><small><i class="fas fa-lock"></i> Ejecución desactivada</small></div><div class="api-record-actions"><button type="button" class="btn btn-warning" data-op-edit="'+Number(x.id)+'"><i class="fas fa-edit"></i> Editar</button></div></article>').join('')||'<div class="api-empty-state"><i class="fas fa-cogs"></i><strong>Sin operaciones registradas</strong><span>Defina sus operaciones o cargue las conocidas de ZYNKO.</span></div>');
        }).fail(e=>notify(e.responseJSON?.mensaje||'No se pudieron cargar las operaciones','error'));
    }
    $('#api_ext_rows').on('click','[data-ext-operations]',function(){
        apiOperationConnection=Number($(this).attr('data-ext-operations'));
        const conn=($('#api_ext_rows').data('connections')||[]).find(x=>Number(x.id)===apiOperationConnection);
        $('#api_operations_title').text('Operaciones API · '+String(conn?.nombre||''));
        $('#api_operation_form')[0].reset();$('#api_operation_id').val(0);$('#api_operation_connection').val(apiOperationConnection);
        $('#api_operations_seed').toggle(String(conn?.proveedor||'').toUpperCase()==='ZYNKO');
        openModal($('#api_operations_modal'));loadOperations();
    });
    $('[data-api-operations-close]').on('click',()=>closeModal($('#api_operations_modal')));
    $('#api_operations_seed').on('click',()=>post({action:'external_seed_zynko',connection_id:apiOperationConnection}).done(r=>{notify(r.mensaje);loadOperations();}).fail(e=>notify(e.responseJSON?.mensaje||'No se pudo cargar el catálogo ZYNKO','error')));
    $('#api_operations_list').on('click','[data-op-edit]',function(){
        const x=($('#api_operations_list').data('operations')||[]).find(x=>Number(x.id)===Number($(this).attr('data-op-edit')));if(!x)return;
        $('#api_operation_id').val(x.id);$('#api_operation_code').val(x.codigo);$('#api_operation_name').val(x.nombre);$('#api_operation_method').val(x.metodo);$('#api_operation_route').val(x.ruta);$('#api_operation_params').val(x.parametros_json||'{}');$('#api_operation_response').val(x.respuesta_json||'{}');$('#api_operation_description').val(x.descripcion||'');
    });
    $('#api_operation_form').on('reset',function(){setTimeout(()=>{$('#api_operation_id').val(0);$('#api_operation_connection').val(apiOperationConnection);},0);});
    $('#api_operation_form').on('submit',function(e){e.preventDefault();const data={action:'external_operation_save'};$(this).serializeArray().forEach(x=>data[x.name]=x.value);post(data).done(r=>{notify(r.mensaje);$('#api_operation_form')[0].reset();loadOperations();}).fail(e=>notify(e.responseJSON?.mensaje||'No se pudo guardar la operación','error'));});
    $('#api_openapi_preview').on('click',()=>{post({action:'external_openapi_preview',openapi_json:$('#api_openapi_json').val()}).done(r=>$('#api_openapi_result').html('<div class="api-empty-state"><i class="fas fa-file-code"></i><strong>'+Number(r.datos.operaciones.length)+' operaciones encontradas</strong><span>'+esc((r.datos.operaciones||[]).map(x=>x.metodo+' '+x.ruta).slice(0,15).join(' · '))+'</span></div>')).fail(e=>notify(e.responseJSON?.mensaje||'OpenAPI inválido','error'));});
    loadIntegrations();
})(window.jQuery);
</script>
