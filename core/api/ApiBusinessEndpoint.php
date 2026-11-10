<?php
/** Controlador HTTP común; solo expone operaciones con implementación validada. */
declare(strict_types=1);

function izzyResponderCatalogo(int $status,string $message,?array $data=null): void
{
    http_response_code($status);
    echo json_encode(['ok'=>$status<400,'mensaje'=>$message,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function izzyEjecutarCatalogo(string $modulo): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Authorization');
    if(($_SERVER['HTTPS']??'off')==='off')izzyResponderCatalogo(403,'Se requiere HTTPS.');
    $method=(string)($_SERVER['REQUEST_METHOD']??'');
    $writeEnabled=in_array($modulo,['clientes','proveedores'],true);
    $allowed=$writeEnabled?['GET','POST','PATCH']:['GET'];
    if(!in_array($method,$allowed,true)){
        header('Allow: '.implode(', ',$allowed));
        izzyResponderCatalogo(405,'Esta operación aún no está habilitada para este recurso.');
    }
    try{
        require_once __DIR__.'/ApiBusinessContext.php';
        require_once __DIR__.'/ApiCatalogService.php';
        $identity=IzzyApiBusinessContext::identificar($modulo,$method==='GET'?'read':($method==='POST'?'create':'update'));
        if($method!=='GET'){
            $contentType=(string)($_SERVER['CONTENT_TYPE']??'');
            if(stripos($contentType,'application/json')!==0)izzyResponderCatalogo(415,'Debe enviar application/json.');
            if((int)($_SERVER['CONTENT_LENGTH']??0)>16384)izzyResponderCatalogo(413,'Solicitud demasiado grande.');
            $raw=(string)file_get_contents('php://input',false,null,0,16385);
            if(strlen($raw)>16384)izzyResponderCatalogo(413,'Solicitud demasiado grande.');
            $payload=json_decode($raw,true);
            if(!is_array($payload)||array_is_list($payload))izzyResponderCatalogo(400,'JSON de objeto inválido.');
            $id=null;
            if($method==='PATCH'){
                $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
                if(!$id||$id<1)throw new InvalidArgumentException('Para editar indique ?id=N válido.');
            }
            require_once __DIR__.'/ApiCatalogWriteService.php';
            $saved=(new IzzyApiCatalogWriteService($identity))->save($modulo,$payload,$id);
            izzyResponderCatalogo($method==='POST'?201:200,$method==='POST'?'Registro creado.':'Registro actualizado.',$saved);
        }
        $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
        $page=filter_input(INPUT_GET,'pagina',FILTER_VALIDATE_INT);
        $limit=filter_input(INPUT_GET,'limite',FILTER_VALIDATE_INT);
        if(isset($_GET['id'])&&($id===false||$id===null||$id<=0))throw new InvalidArgumentException('ID inválido.');
        $service=new IzzyApiCatalogService($identity);
        if($modulo==='facturas'&&$id>0){
            izzyResponderCatalogo(200,'Factura consultada.',['factura'=>$service->detalleFactura($id)]);
        }
        $rows=$service->consultar($modulo,$id?:0,$page?:1,$limit?:25);
        izzyResponderCatalogo(200,'Consulta completada.',$rows);
    }catch(InvalidArgumentException $e){izzyResponderCatalogo(422,$e->getMessage());}
    catch(OutOfBoundsException $e){izzyResponderCatalogo(404,$e->getMessage());}
    catch(DomainException $e){izzyResponderCatalogo(403,'Autenticación o permisos insuficientes.');}
    catch(Throwable $e){error_log('IZZY API '.$modulo.': '.$e->getMessage());izzyResponderCatalogo(500,'No se pudo consultar el recurso.');}
}
