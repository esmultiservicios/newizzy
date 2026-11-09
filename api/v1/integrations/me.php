<?php
/** Verifica que la integración y su autorización de empresa continúan vigentes. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
$reply=static function(int $status,array $data): void {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;};
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') {header('Allow: GET');$reply(405,['ok'=>false,'mensaje'=>'Método no permitido.']);}
if (empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off') $reply(403,['ok'=>false,'mensaje'=>'HTTPS requerido.']);
$authorization='';
foreach (['HTTP_AUTHORIZATION','REDIRECT_HTTP_AUTHORIZATION','Authorization'] as $key) {
    if (!empty($_SERVER[$key])&&is_string($_SERVER[$key])) {$authorization=trim($_SERVER[$key]);break;}
}
if ($authorization===''&&function_exists('getallheaders')) foreach(getallheaders() as $key=>$value) {
    if (strcasecmp((string)$key,'Authorization')===0&&is_string($value)) {$authorization=trim($value);break;}
}
if (!preg_match('/^Bearer\s+(izzya_[a-f0-9]{64})$/iD',$authorization,$match)) $reply(401,['ok'=>false,'mensaje'=>'Bearer requerido.']);
try {
    require_once dirname(__DIR__,3).'/core/api/ApiClientAuthService.php';
    $context=(new IzzyApiClientAuthService())->autenticarToken($match[1],'');
    $reply(200,['ok'=>true,'mensaje'=>'Integración activa.','data'=>[
        'server_customers_id'=>$context['server_customers_id'],
        'scopes'=>preg_split('/\s+/',trim($context['scopes']))
    ]]);
} catch (DomainException $e) {$reply(401,['ok'=>false,'mensaje'=>'Token inválido, vencido o sin autorización.']);}
catch (Throwable $e) {error_log('IZZY API integration me: '.$e->getMessage());$reply(500,['ok'=>false,'mensaje'=>'No se pudo validar la integración.']);}
