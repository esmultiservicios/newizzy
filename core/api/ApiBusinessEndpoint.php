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
    if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){
        header('Allow: GET');
        izzyResponderCatalogo(405,'Por seguridad, este recurso solo admite GET hasta completar las reglas de escritura de IZZY.');
    }
    try{
        require_once __DIR__.'/ApiBusinessContext.php';
        require_once __DIR__.'/ApiCatalogService.php';
        $identity=IzzyApiBusinessContext::identificar($modulo,'read');
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
