<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$reply=static function(int $status,string $message,array $data=[]): never {
    http_response_code($status);
    echo json_encode(['ok'=>$status===200,'message'=>$message]+$data,JSON_UNESCAPED_UNICODE);
    exit;
};
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')$reply(405,'Método no permitido.');
if(session_status()!==PHP_SESSION_ACTIVE)session_start(['name'=>'SD']);
try {
    $peticionAjax=true;
    require_once dirname(__DIR__).'/configGenerales.php';
    require_once dirname(__DIR__).'/mainModel.php';
    require_once __DIR__.'/ExternalProviderService.php';
    if(!empty(mainModel::validarSesion()['error']))$reply(401,'Inicie sesión para compartir documentos.');
    $csrf=(string)($_SESSION['csrf_izzy_document_share']??'');
    if($csrf===''||!hash_equals($csrf,(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'')))$reply(403,'Solicitud inválida.');
    $company=(int)($_SESSION['empresa_id_sd']??0);
    $actor=(int)($_SESSION['users_id_sd']??0);
    $role=(int)($_SESSION['privilegio_sd']??0);
    if(min($company,$actor,$role)<1)$reply(403,'Empresa o usuario no verificado.');
    $type=(string)($_POST['type']??'');
    $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $allowed=['factura'=>['facturas','facturaMovil','facturasRestaurante','reporteVentas'],'cotizacion'=>['cotizacion','reporteCotizacion']];
    $view=(string)($_POST['view']??'');
    if(!$id||!in_array($view,$allowed[$type]??[],true))$reply(422,'Documento o vista inválida.');

    $db=(new mainModel())->connection();
    $access=$db->prepare("SELECT (EXISTS(SELECT 1 FROM menu m JOIN acceso_menu a USING(menu_id) WHERE m.name=? AND a.privilegio_id=? AND a.estado=1)
        OR EXISTS(SELECT 1 FROM submenu m JOIN acceso_submenu a USING(submenu_id) WHERE m.name=? AND a.privilegio_id=? AND a.estado=1)
        OR EXISTS(SELECT 1 FROM submenu1 m JOIN acceso_submenu1 a USING(submenu1_id) WHERE m.name=? AND a.privilegio_id=? AND a.estado=1)) AS permitted");
    $access->bind_param('sisisi',$view,$role,$view,$role,$view,$role);$access->execute();
    if(!(int)$access->get_result()->fetch_assoc()['permitted'])$reply(403,'Sin acceso al módulo de origen.');
    $access->close();
    $database=(string)$db->query('SELECT DATABASE()')->fetch_row()[0];
    $service=new IzzyExternalProviderService();
    $connection=$service->documentConnection($database,$company,$type);
    if(!$connection)$reply(200,'WhatsApp está desactivado para este tipo de documento.',['available'=>false]);

    $sql=$type==='factura'
        ?'SELECT c.telefono FROM facturas f JOIN clientes c USING(clientes_id) JOIN secuencia_facturacion s ON f.secuencia_facturacion_id=s.secuencia_facturacion_id
            WHERE f.facturas_id=? AND f.empresa_id=? AND f.estado IN(2,3) AND f.number>0 AND s.documento_id<>4'
        :'SELECT c.telefono FROM cotizacion f JOIN clientes c USING(clientes_id)
            WHERE f.cotizacion_id=? AND f.empresa_id=? AND f.estado=1';
    $stmt=$db->prepare($sql);$stmt->bind_param('ii',$id,$company);$stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$row)$reply(404,'El documento no está emitido, fue anulado o está fuera de su empresa.');
    $phone=preg_replace('/[\s()+.-]/','',trim((string)$row['telefono']));
    if(!preg_match('/^[1-9][0-9]{9,14}$/D',$phone))$reply(422,'El cliente necesita un teléfono registrado con código internacional.');
    $confirmation=hash_hmac('sha256',implode('|',[$database,$company,$type,$id,$phone]),$csrf);
    if(($_POST['action']??'')==='check')$reply(200,'Documento disponible.',['available'=>true,'recipient'=>'••••'.substr($phone,-4),'confirmation'=>$confirmation]);
    if(($_POST['action']??'')!=='send'||($_POST['consent']??'')!=='1')$reply(422,'Confirme la autorización del cliente.');
    if(($_SESSION['modo_solo_lectura_pago']??'')==='SI')$reply(403,'La cuenta está limitada a consultas.');
    if(!hash_equals($confirmation,(string)($_POST['confirmation']??'')))$reply(409,'El teléfono cambió. Confirme nuevamente el destinatario.');

    $report=(string)SERVERURLWINDOWS;
    if(!filter_var($report,FILTER_VALIDATE_URL)||strtolower((string)parse_url($report,PHP_URL_SCHEME))!=='https'||
        parse_url($report,PHP_URL_USER)!==null||parse_url($report,PHP_URL_FRAGMENT)!==null)
        $reply(503,'El servidor de reportes necesita una URL HTTPS válida.');
    $url=$report.(str_contains($report,'?')?'&':'?').http_build_query([
        'id'=>$id,'type'=>$type==='factura'?'Factura_carta_izzy':'Cotizacion_carta_izzy',
        'db'=>$database,'demo_sistema'=>(string)($_SESSION['demo_sistema']??'NO')
    ]);
    session_write_close();
    $result=$service->dispatchDocument($connection,$database,$company,$type,$id,$actor,$phone,$url);
    $reply($result['ok']?200:502,$result['detail'],['code'=>$result['code']??'']);
} catch (DomainException $error) {
    $reply(409,$error->getMessage());
} catch (Throwable $error) {
    error_log('IZZY WhatsApp documento: '.get_class($error).' / '.(int)$error->getCode());
    $reply(503,'No se pudo completar la solicitud de WhatsApp. Revise la configuración o el historial de ZYNKO.');
}
