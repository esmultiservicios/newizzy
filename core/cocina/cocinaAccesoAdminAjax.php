<?php
// core/cocina/cocinaAccesoAdminAjax.php
// Administración autenticada del enlace independiente de Cocina.
declare(strict_types=1);

$peticionAjax=true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/cocinaTokenService.php';

if(session_status()===PHP_SESSION_NONE){
    session_start(['name'=>'SD']);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function cocinaAdminOut(array $data,int $status=200): void {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function cocinaAdminSesion(): array {
    $validacion=mainModel::validarSesion();
    if(!empty($validacion['error'])){
        cocinaAdminOut([
            'status'=>false,
            'message'=>$validacion['mensaje']??'Sesión requerida.'
        ],401);
    }

    $empresaSesion=(int)($_SESSION['empresa_id_sd']??0);
    if($empresaSesion<=0){
        cocinaAdminOut(['status'=>false,'message'=>'No se pudo resolver la empresa activa de la sesión.'],403);
    }

    $context=trim((string)($_POST['context']??''));
    if($context===''){
        cocinaAdminOut(['status'=>false,'message'=>'No se recibió el contexto seguro de Cocina. Recargue el Restaurante e intente nuevamente.'],403);
    }

    try{
        $ctx=CocinaTokenService::resolveAdminContext($context,session_id(),$empresaSesion);
        $tenantDb=(string)$ctx['db'];
        $serverCustomerId=CocinaTokenService::serverCustomerForTenantDb($tenantDb);
        // ID 0 es válido únicamente para la propia BD principal de IZZY.
        if($serverCustomerId<0) throw new RuntimeException('Cliente principal inválido.');
        return [$serverCustomerId,$empresaSesion,$tenantDb];
    }catch(DomainException $e){
        error_log('[CocinaAdmin][Contexto] '.$e->getMessage());
        cocinaAdminOut(['status'=>false,'message'=>$e->getMessage()],403);
    }catch(Throwable $e){
        error_log('[CocinaAdmin][Vinculo] '.$e->getMessage());
        cocinaAdminOut(['status'=>false,'message'=>$e->getMessage()],403);
    }
}

function cocinaAdminLink(string $token): string { return rtrim((string)SERVERURL,'/').'/cocina/'.$token.'/'; }
function cocinaAdminBaseLink(): string { return rtrim((string)SERVERURL,'/').'/cocina/'; }

try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
        cocinaAdminOut(['status'=>false,'message'=>'Método no permitido.'],405);
    }

    [$serverCustomerId,$empresaId,$tenantDb]=cocinaAdminSesion();
    $actionRaw=trim((string)($_POST['action']??''));
    // Normalizamos para soportar versiones anteriores/nuevas del JS sin debilitar la seguridad.
    $action=strtolower((string)preg_replace('/[^a-z]/i','',$actionRaw));

    if(in_array($action,['load','cargar','loadaccess','cargaracceso'],true)){
        $row=CocinaTokenService::getAdminAccess($serverCustomerId,$empresaId);
        $token=$row?CocinaTokenService::decryptToken((string)($row['token_cifrado']??'')):'';
        cocinaAdminOut(['status'=>true,'config'=>[
            'activo'=>$row?(int)$row['activo']:0,
            'enlace'=>$token!==''?cocinaAdminLink($token):'',
            'tiene_token'=>$row?1:0,
            'fecha_regeneracion'=>$row['fecha_regeneracion']??null,
            'enlace_tv'=>cocinaAdminBaseLink()
        ]]);
    }

    if(in_array($action,['save','guardar','saveaccess','guardaracceso'],true)){
        $active=((string)($_POST['activo']??'0')==='1');
        $row=CocinaTokenService::saveActive($serverCustomerId,$empresaId,$active,$tenantDb);
        $token=CocinaTokenService::decryptToken((string)($row['token_cifrado']??''));
        cocinaAdminOut(['status'=>true,'config'=>[
            'activo'=>$active?1:0,
            'enlace'=>$token!==''?cocinaAdminLink($token):'',
            'tiene_token'=>1,
            'enlace_tv'=>cocinaAdminBaseLink()
        ]]);
    }

    if(in_array($action,['linkdevice','linktv','vinculardispositivo','vinculartv'],true)){
        $code=trim((string)($_POST['codigo']??''));
        $name=trim((string)($_POST['nombre']??''));
        CocinaTokenService::linkPairingCode($serverCustomerId,$empresaId,$code,$name);
        cocinaAdminOut([
            'status'=>true,
            'message'=>'Pantalla vinculada correctamente. En unos segundos abrirá Cocina automáticamente.',
            'dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)
        ]);
    }

    if(in_array($action,['listdevices','devices','list','listar','listardispositivos','cargardispositivos'],true)){
        cocinaAdminOut([
            'status'=>true,
            'dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)
        ]);
    }

    if(in_array($action,['unlinkdevice','unlinktv','desvinculardispositivo','desvinculartv'],true)){
        $deviceId=(int)($_POST['dispositivo_id']??0);
        CocinaTokenService::unlinkDevice($serverCustomerId,$empresaId,$deviceId);
        cocinaAdminOut([
            'status'=>true,
            'message'=>'Pantalla desvinculada correctamente.',
            'dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)
        ]);
    }

    if(in_array($action,['archivardispositivo','ocultardispositivo','archivedevice','hidedevice'],true)){
        $deviceId=(int)($_POST['dispositivo_id']??0);
        CocinaTokenService::archiveDeviceFromView($serverCustomerId,$empresaId,$deviceId);
        cocinaAdminOut(['status'=>true,'message'=>'Pantalla quitada de la vista. El historial se conserva.','dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)]);
    }

    if(in_array($action,['restaurardispositivo','mostrardispositivo','restoredevice','showdevice'],true)){
        $deviceId=(int)($_POST['dispositivo_id']??0);
        CocinaTokenService::restoreDeviceToView($serverCustomerId,$empresaId,$deviceId);
        cocinaAdminOut(['status'=>true,'message'=>'Pantalla restaurada en la vista.','dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)]);
    }

    if(in_array($action,['regenerate','regenerar','regenerateaccess','regeneraracceso'],true)){
        $r=CocinaTokenService::regenerate($serverCustomerId,$empresaId,$tenantDb);
        cocinaAdminOut([
            'status'=>true,
            'message'=>'Acceso de Cocina regenerado. El enlace anterior ya no es válido.',
            'config'=>[
                'activo'=>1,
                'enlace'=>cocinaAdminLink((string)$r['token']),
                'tiene_token'=>1,
                'enlace_tv'=>cocinaAdminBaseLink()
            ]
        ]);
    }

    error_log('[CocinaAdmin][Action] Acción no reconocida: '.($actionRaw!==''?$actionRaw:'(vacía)'));

    if(in_array($action,['renamedevice','renombrardispositivo','renombrarpantalla'],true)){
        $deviceId=(int)($_POST['dispositivo_id']??0);
        $name=trim((string)($_POST['nombre']??''));
        CocinaTokenService::renameDevice($serverCustomerId,$empresaId,$deviceId,$name);
        cocinaAdminOut(['status'=>true,'message'=>'Nombre de la pantalla actualizado.','dispositivos'=>CocinaTokenService::listDevices($serverCustomerId,$empresaId)]);
    }

    if(in_array($action,['sendtest','testkitchen','probarcocina','enviarprueba'],true)){
        CocinaTokenService::sendKitchenTest($serverCustomerId,$empresaId,trim((string)($_POST['mensaje']??'Prueba de conexión IZZY')));
        cocinaAdminOut(['status'=>true,'message'=>'Prueba enviada. Debe aparecer en las pantallas vinculadas en unos segundos.']);
    }

    if(in_array($action,['recordhistory','registrarhistorial'],true)){
        $changes=json_decode((string)($_POST['cambios']??'{}'),true); if(!is_array($changes)) $changes=[];
        CocinaTokenService::recordConfigHistory(
            $serverCustomerId,$empresaId,
            (int)($_SESSION['users_id_sd']??0),(int)($_SESSION['colaborador_id_sd']??0),
            trim((string)($_POST['categoria']??'general')),
            trim((string)($_POST['resumen']??'Configuración actualizada')),
            $changes
        );
        cocinaAdminOut(['status'=>true]);
    }

    if(in_array($action,['listhistory','historial','cargarhistorial'],true)){
        cocinaAdminOut(['status'=>true,'historial'=>CocinaTokenService::listConfigHistory($serverCustomerId,$empresaId,20)]);
    }

    cocinaAdminOut(['status'=>false,'message'=>'Acción administrativa no permitida.'],403);
}catch(Throwable $e){
    error_log('[CocinaAdmin] '.$e->getMessage());
    cocinaAdminOut(['status'=>false,'message'=>$e->getMessage()],500);
}