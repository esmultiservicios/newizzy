<?php
/** Consultas paginadas y limitadas al tenant autenticado. Sin consultas a otras DB. */
declare(strict_types=1);

final class IzzyApiCatalogService
{
    private mysqli $db;
    private ?int $empresa;
    private const CATALOGOS=[
        'clientes'=>['tabla'=>'clientes','id'=>'clientes_id','columnas'=>'clientes_id,nombre,rtn,telefono,correo,localidad,estado,fecha_registro'],
        'proveedores'=>['tabla'=>'proveedores','id'=>'proveedores_id','columnas'=>'proveedores_id,nombre,rtn,telefono,correo,localidad,estado,fecha_registro'],
        'productos'=>['tabla'=>'productos','id'=>'productos_id','columnas'=>'productos_id,barCode,nombre,descripcion,precio_venta,precio_compra,almacen_id,medida_id,categoria_id,estado,empresa_id'],
        'facturas'=>['tabla'=>'facturas','id'=>'facturas_id','columnas'=>'facturas_id,clientes_id,secuencia_facturacion_id,number,tipo_factura,importe,fecha,estado,empresa_id,fecha_registro'],
        'empresa'=>['tabla'=>'empresa','id'=>'empresa_id','columnas'=>'empresa_id,razon_social,nombre,eslogan,telefono,celular,correo,rtn,ubicacion,sitioweb,horario,estado'],
        'secuencias'=>['tabla'=>'secuencia_facturacion','id'=>'secuencia_facturacion_id','columnas'=>'secuencia_facturacion_id,empresa_id,cai,prefijo,relleno,incremento,siguiente,rango_inicial,rango_final,fecha_activacion,fecha_limite,activo,documento_id']
    ];

    public function __construct(array $identidad)
    {
        $db=(string)($identidad['db']??'');
        if(!preg_match('/^[a-zA-Z0-9_]+$/D',$db)) throw new DomainException('Base de datos no autorizada.');
        $this->empresa=$identidad['empresa_id']===null?null:(int)$identidad['empresa_id'];
        $peticionAjax=true;
        require_once dirname(__DIR__).'/configGenerales.php';
        require_once dirname(__DIR__).'/mainModel.php';
        $this->db=(new mainModel())->connectionDBLocal($db);
        $this->db->set_charset('utf8mb4');
    }

    public function consultar(string $modulo,int $id,int $pagina,int $limite): array
    {
        if (!isset(self::CATALOGOS[$modulo])) throw new InvalidArgumentException('Módulo no reconocido.');
        $cfg=self::CATALOGOS[$modulo];$pagina=max(1,$pagina);$limite=max(1,min(100,$limite));$offset=($pagina-1)*$limite;
        if($offset>1000000) throw new InvalidArgumentException('Página fuera de rango.');
        $companyScope=in_array($modulo,['productos','facturas','secuencias'],true)&&$this->empresa!==null;
        $where=[];$types='';$values=[];
        if($companyScope || ($modulo==='empresa' && $this->empresa!==null)){$where[]='empresa_id=?';$types.='i';$values[]=$this->empresa;}
        if($id>0){$where[]=$cfg['id'].'=?';$types.='i';$values[]=$id;}
        $condition=$where?' WHERE '.implode(' AND ',$where):'';
        $sql='SELECT '.$cfg['columnas'].' FROM '.$cfg['tabla'].$condition.' ORDER BY '.$cfg['id'].' DESC LIMIT ? OFFSET ?';
        $stmt=$this->db->prepare($sql);if(!$stmt)throw new RuntimeException('No se pudo preparar la consulta.');
        $types.='ii';$values[]=$id>0?1:$limite;$values[]=$id>0?0:$offset;
        $stmt->bind_param($types,...$values);$stmt->execute();$items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
        if($id>0 && !$items)throw new OutOfBoundsException('Registro no encontrado en esta empresa.');
        return ['registros'=>$items,'pagina'=>$pagina,'limite'=>$limite,'siguiente_pagina'=>$id===0&&count($items)===$limite?$pagina+1:null];
    }

    public function detalleFactura(int $facturaId): array
    {
        $encabezado=$this->consultar('facturas',$facturaId,1,1)['registros'][0];
        $stmt=$this->db->prepare('SELECT facturas_detalle_id,productos_id,cantidad,precio,costo_unitario,isv_valor,isv_valor1,descuento,medida FROM facturas_detalles WHERE facturas_id=? ORDER BY facturas_detalle_id LIMIT 300');
        if(!$stmt)throw new RuntimeException('No se pudo consultar el detalle.');
        $stmt->bind_param('i',$facturaId);$stmt->execute();$encabezado['detalles']=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
        return $encabezado;
    }

    public function __destruct(){if(isset($this->db))$this->db->close();}
}
