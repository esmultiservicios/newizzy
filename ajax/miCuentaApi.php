<?php
/** Integraciones de la empresa autenticada. Nunca acepta empresa/DB de POST. */
if (session_status()!==PHP_SESSION_ACTIVE) session_start(['name'=>'SD']);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
$reply=static function(int $status,bool $ok,string $message,array $data=[]): void {
    http_response_code($status);
    echo json_encode(['ok'=>$ok,'mensaje'=>$message,'datos'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
};
try {
    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') {$reply(405,false,'Método no permitido.');}
    $peticionAjax=true;
    require_once dirname(__DIR__).'/core/configGenerales.php';
    require_once dirname(__DIR__).'/core/mainModel.php';
    $session=mainModel::validarSesion();
    if (!empty($session['error'])) $reply(401,false,'Sesión vencida.');
    $csrf=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf']??'');
    if ($csrf==='' || !hash_equals((string)($_SESSION['csrf_api_cuenta']??''),$csrf)) $reply(419,false,'Recargue la página.');
    $id=(int)($_SESSION['server_customers_id']??0);
    $db=(string)($_SESSION['db_cliente']??'');
    $userId=(int)($_SESSION['users_id_sd']??0);
    $privilegeId=(int)($_SESSION['privilegio_sd']??0);
    if ($id<=0 || $userId<=0 || !preg_match('/^[a-zA-Z0-9_]+$/D',$db)) $reply(403,false,'Empresa no autorizada.');
    $main=new mainModel();
    $central=$main->connectionDBLocal(DB_MAIN);
    $stmt=$central->prepare('SELECT db,estado FROM server_customers WHERE server_customers_id=? LIMIT 1');
    $stmt->bind_param('i',$id);$stmt->execute();$customer=$stmt->get_result()->fetch_assoc();$stmt->close();$central->close();
    if (!$customer || (int)$customer['estado']!==1 || !hash_equals((string)$customer['db'],$db)) $reply(403,false,'Empresa no autorizada.');
    $tenant=$main->connectionDBLocal($db);
    $stmt=$tenant->prepare('SELECT p.nombre FROM users u INNER JOIN privilegio p ON p.privilegio_id=u.privilegio_id WHERE u.users_id=? AND u.empresa_id=? AND u.privilegio_id=? AND u.estado=1 LIMIT 1');
    $empresa=(int)($_SESSION['empresa_id_sd']??0);
    $stmt->bind_param('iii',$userId,$empresa,$privilegeId);$stmt->execute();$actor=$stmt->get_result()->fetch_assoc();$stmt->close();$tenant->close();
    $admin=in_array(trim((string)($actor['nombre']??'')),['Administrador','Super Administrador'],true);
    require_once dirname(__DIR__).'/core/api/ApiIntegrationService.php';
    $service=new IzzyApiIntegrationService();
    $allowed=$admin && $service->permitido($id);
    $action=(string)($_POST['action']??'status');
    if ($action==='status') $reply(200,true,'Estado consultado.',['habilitada'=>$allowed]);
    if (!$allowed) $reply(403,false,'IZZY no ha autorizado esta función para su empresa o usuario.');
    if ($action==='list') $reply(200,true,'Integraciones cargadas.',['integraciones'=>$service->listarCliente($id)]);
    if ($action==='create') {
        $scopes=$_POST['scopes']??[];
        if (!is_array($scopes)) $reply(422,false,'Alcances inválidos.');
        $created=$service->crear((string)($_POST['name']??''),(int)($_POST['expires_in']??90),$userId,$id,$scopes);
        $reply(200,true,'Credenciales generadas. Guarde el secreto ahora.',['integracion'=>$created]);
    }
    if ($action==='revoke') {
        $service->revocar((int)($_POST['id']??0),$id);
        $reply(200,true,'Integración revocada.');
    }
    $reply(400,false,'Acción no reconocida.');
} catch (InvalidArgumentException $e) {
    $reply(422,false,$e->getMessage());
} catch (DomainException $e) {
    $reply(403,false,$e->getMessage());
} catch (Throwable $e) {
    error_log('IZZY Mi Cuenta API: '.$e->getMessage());
    $reply(500,false,'No se pudo completar la operación.');
}
