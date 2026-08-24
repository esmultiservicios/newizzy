<?php
// core/cocina/cocinaPublicaAjax.php
declare(strict_types=1);
$peticionAjax=true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/cocinaTokenService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

function cocinaJson(array $data,int $status=200): void { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function cocinaTokenHeader(): string {
    $v=$_SERVER['HTTP_X_COCINA_TOKEN'] ?? '';
    if($v===''){
        $auth=$_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if(preg_match('/^Bearer\s+(.+)$/i',$auth,$m)) $v=$m[1];
    }
    return trim((string)$v);
}
function cocinaDeviceHeader(): string {
    return trim((string)($_SERVER['HTTP_X_COCINA_DEVICE'] ?? ''));
}
function hasTable(mysqli $db,string $name): bool { $st=$db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1'); $st->bind_param('s',$name); $st->execute(); $r=$st->get_result(); $ok=$r&&$r->num_rows>0; $st->close(); return $ok; }
function listComandas(mysqli $db,int $empresa): array {
    if(!hasTable($db,'factura_comanda_items')){
        throw new RuntimeException('Falta la tabla de comandas incrementales factura_comanda_items.');
    }

    // La consulta queda limitada a:
    // 1) empresa resuelta desde el token;
    // 2) estación Cocina;
    // 3) estados todavía visibles en preparación;
    // 4) únicamente ítems registrados HOY. Una comanda vieja no reaparece al día siguiente.
    // No expone precios, totales, caja ni pagos.
    $sql="
        SELECT
            COALESCE(fc.id,0) AS comanda_id,
            fci.factura_id,
            COALESCE(fc.mesa_id,0) AS mesa_id,
            COALESCE(fc.servicio_tipo,IF(COALESCE(fc.mesa_id,0)>0,'mesa','llevar')) AS servicio_tipo,
            CASE
                WHEN SUM(fci.estado='urgente')>0 THEN 'urgente'
                WHEN SUM(fci.estado='en_preparacion')>0 THEN 'en_preparacion'
                ELSE 'pendiente'
            END AS estado,
            MIN(fci.fecha_registro) AS fecha_registro,
            COALESCE(fc.comentarios_cocina,'') AS comentarios_cocina,
            COALESCE(f.notas,'') AS observaciones,
            COALESCE(cli.nombre,'Consumidor Final') AS cliente_nombre,
            COALESCE(m.numero,'') AS mesa_numero,
            p.productos_id,
            p.nombre AS producto_nombre,
            SUM(fci.cantidad) AS cantidad
        FROM factura_comanda_items fci
        INNER JOIN facturas f
            ON f.facturas_id=fci.factura_id
           AND f.empresa_id=?
        INNER JOIN productos p
            ON p.productos_id=fci.productos_id
        LEFT JOIN factura_comanda fc
            ON fc.factura_id=fci.factura_id
        LEFT JOIN clientes cli
            ON cli.clientes_id=f.clientes_id
        LEFT JOIN mesas m
            ON m.mesa_id=fc.mesa_id
        WHERE LOWER(TRIM(fci.estacion))='cocina'
          AND fci.estado IN ('pendiente','en_preparacion','urgente')
          AND DATE(fci.fecha_registro)=CURDATE()
        GROUP BY
            fc.id,fci.factura_id,fc.mesa_id,fc.servicio_tipo,
            fc.comentarios_cocina,f.notas,cli.nombre,m.numero,
            p.productos_id,p.nombre
        ORDER BY MIN(fci.fecha_registro) ASC,fci.factura_id ASC,p.nombre ASC
    ";

    $st=$db->prepare($sql);
    if(!$st) throw new RuntimeException('No se pudo preparar el listado de Cocina: '.$db->error);

    $st->bind_param('i',$empresa);
    $st->execute();
    $rs=$st->get_result();
    $out=[];

    while($r=$rs->fetch_assoc()){
        $fid=(int)$r['factura_id'];

        if(!isset($out[$fid])){
            $fechaRegistro=(string)($r['fecha_registro']??'');
            $timestamp=$fechaRegistro!==''?strtotime($fechaRegistro):false;

            $mesaNumero=trim((string)($r['mesa_numero']??''));
            $mesaId=(int)($r['mesa_id']??0);

            $out[$fid]=[
                // Conserva los nombres que ya utiliza cocina.js actual.
                'comanda_id'=>(int)($r['comanda_id']??0),
                'id'=>(int)($r['comanda_id']??0),
                'factura_id'=>$fid,
                'mesa'=>$mesaNumero!==''?$mesaNumero:($mesaId>0?(string)$mesaId:''),
                'mesa_id'=>$mesaId,
                'servicio_tipo'=>(string)($r['servicio_tipo']??''),
                'estado'=>(string)($r['estado']??'pendiente'),
                'urgente'=>((string)($r['estado']??'')==='urgente'),
                'fecha'=>$timestamp?date('d/m/Y',$timestamp):'',
                'hora'=>$timestamp?date('H:i',$timestamp):'',
                'cliente_nombre'=>(string)($r['cliente_nombre']??'Consumidor Final'),
                'comentarios_cocina'=>(string)($r['comentarios_cocina']??''),
                'observaciones'=>(string)($r['observaciones']??''),
                'items'=>[]
            ];
        }

        $qty=(float)$r['cantidad'];
        $qtyValue=(abs($qty-round($qty))<0.00001)
            ? (int)round($qty)
            : (float)rtrim(rtrim(number_format($qty,3,'.',''),'0'),'.');

        $out[$fid]['items'][]=[
            'productos_id'=>(int)$r['productos_id'],
            'id'=>(int)$r['productos_id'],
            'nombre'=>(string)$r['producto_nombre'],
            'cantidad'=>$qtyValue
        ];
    }

    $st->close();
    return array_values($out);
}

function updateKitchenState(mysqli $db,int $empresa,int $factura,string $action,bool $urgent=false): void {
    if($factura<=0) throw new InvalidArgumentException('Comanda inválida.');
    $db->begin_transaction();
    try{
        if($action==='enPreparacion') $sql="UPDATE factura_comanda_items fci INNER JOIN facturas f ON f.facturas_id=fci.factura_id SET fci.estado='en_preparacion',fci.fecha_actualizacion=NOW() WHERE fci.factura_id=? AND f.empresa_id=? AND LOWER(TRIM(fci.estacion))='cocina' AND fci.estado IN ('pendiente','urgente','en_preparacion')";
        elseif($action==='finalizar') $sql="UPDATE factura_comanda_items fci INNER JOIN facturas f ON f.facturas_id=fci.factura_id SET fci.estado='preparada',fci.fecha_actualizacion=NOW() WHERE fci.factura_id=? AND f.empresa_id=? AND LOWER(TRIM(fci.estacion))='cocina' AND fci.estado IN ('pendiente','urgente','en_preparacion')";
        else $sql=$urgent
            ? "UPDATE factura_comanda_items fci INNER JOIN facturas f ON f.facturas_id=fci.factura_id SET fci.estado='urgente',fci.fecha_actualizacion=NOW() WHERE fci.factura_id=? AND f.empresa_id=? AND LOWER(TRIM(fci.estacion))='cocina' AND fci.estado IN ('pendiente','urgente','en_preparacion')"
            : "UPDATE factura_comanda_items fci INNER JOIN facturas f ON f.facturas_id=fci.factura_id SET fci.estado='pendiente',fci.fecha_actualizacion=NOW() WHERE fci.factura_id=? AND f.empresa_id=? AND LOWER(TRIM(fci.estacion))='cocina' AND fci.estado='urgente'";
        $st=$db->prepare($sql); if(!$st) throw new RuntimeException('No se pudo actualizar Cocina.'); $st->bind_param('ii',$factura,$empresa); $st->execute(); $affected=$st->affected_rows; $st->close();
        if($affected===0 && $action!=='urgente') { /* idempotente: no convertirlo en error */ }
        $db->commit();
    }catch(Throwable $e){ $db->rollback(); throw $e; }
}

try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') cocinaJson(['status'=>false,'message'=>'Método no permitido.'],405);
    $ctx=CocinaTokenService::resolve(cocinaTokenHeader(),cocinaDeviceHeader()); $db=$ctx['tenant']; $empresa=(int)$ctx['empresa_id']; $action=trim((string)($_POST['action']??''));
    if($action==='listar'){
        $comandas=listComandas($db,$empresa);
        $accessId=(int)($ctx['access']['acceso_id']??0);
        foreach(CocinaTokenService::getKitchenTests($accessId) as $t){
            $ts=strtotime((string)($t['fecha_registro']??''));
            $comandas[]=[
                'comanda_id'=>'test-'.(int)$t['prueba_id'],
                'id'=>'test-'.(int)$t['prueba_id'],
                'factura_id'=>'PRUEBA',
                'mesa'=>'',
                'mesa_id'=>0,
                'servicio_tipo'=>'prueba',
                'estado'=>'pendiente',
                'urgente'=>false,
                'es_prueba'=>1,
                'fecha'=>$ts?date('d/m/Y',$ts):date('d/m/Y'),
                'hora'=>$ts?date('H:i',$ts):date('H:i'),
                'cliente_nombre'=>'IZZY',
                'comentarios_cocina'=>'Esta tarjeta confirma que la Pantalla de Cocina está recibiendo información correctamente.',
                'observaciones'=>'',
                'items'=>[['productos_id'=>0,'id'=>0,'nombre'=>(string)($t['mensaje']??'Prueba de conexión IZZY'),'cantidad'=>1]]
            ];
        }
        cocinaJson(['status'=>true,'comandas'=>$comandas,'config'=>['etiqueta_cocina'=>(string)$ctx['config']['etiqueta_cocina'],'flujo_cocina'=>(string)$ctx['config']['flujo_cocina']]]);
    }
    if(!in_array($action,['enPreparacion','urgente','finalizar'],true)) cocinaJson(['status'=>false,'message'=>'Acción no permitida para Pantalla Cocina.'],403);
    $factura=(int)($_POST['factura_id']??0); updateKitchenState($db,$empresa,$factura,$action,((string)($_POST['urgente']??'0')==='1'));
    cocinaJson(['status'=>true,'message'=>'Comanda actualizada.']);
}catch(DomainException $e){ $code=$e->getCode(); cocinaJson(['status'=>false,'message'=>$e->getMessage()],in_array($code,[401,403,404],true)?$code:401); }
catch(InvalidArgumentException $e){ cocinaJson(['status'=>false,'message'=>$e->getMessage()],422); }
catch(Throwable $e){ error_log('[CocinaPublica] '.$e->getMessage()); cocinaJson(['status'=>false,'message'=>'No fue posible procesar la solicitud de Cocina.'],500); }
