<?php
// core/cocina/cocinaVinculacionAjax.php
// Endpoint público limitado exclusivamente a vincular una pantalla física.
declare(strict_types=1);
$peticionAjax=true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/cocinaTokenService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

function cocinaPairOut(array $data,int $status=200): void {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') cocinaPairOut(['status'=>false,'message'=>'Método no permitido.'],405);
    $action=trim((string)($_POST['action']??''));
    $secret=trim((string)($_POST['device_secret']??''));

    if($action==='crear'){
        $r=CocinaTokenService::createPairing($secret);
        cocinaPairOut(['status'=>true,'codigo'=>$r['codigo'],'expira_segundos'=>$r['expira_segundos']]);
    }
    if($action==='estado'){
        $code=trim((string)($_POST['codigo']??''));
        $r=CocinaTokenService::pairingStatus($code,$secret);
        cocinaPairOut(['status'=>true]+$r);
    }
    cocinaPairOut(['status'=>false,'message'=>'Acción no permitida.'],403);
}catch(InvalidArgumentException $e){ cocinaPairOut(['status'=>false,'message'=>$e->getMessage()],422); }
catch(Throwable $e){ error_log('[CocinaVinculacion] '.$e->getMessage()); cocinaPairOut(['status'=>false,'message'=>$e->getMessage()],500); }
