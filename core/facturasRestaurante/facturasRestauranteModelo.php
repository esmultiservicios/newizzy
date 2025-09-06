<?php
// core/facturasRestaurante/facturasRestauranteModelo.php
require_once __DIR__ . '/../mainModel.php';

class facturasRestauranteModelo extends mainModel {

    /* ===== Helpers de sesión ===== */
    protected function empresaId()     { return intval($_SESSION['empresa_id_sd'] ?? 1); }
    protected function usuarioId()     { return intval($_SESSION['users_id_sd']    ?? 1); }
    protected function colaboradorId() { return intval($_SESSION['colaborador_id_sd'] ?? 1); }
    protected function aperturaId()    { return intval($_SESSION['apertura_id_sd'] ?? 0); } // 0 si no hay caja abierta

    /* ===== Sanitizador ===== */
    public function cleanString($str){ return parent::cleanString($str); }

    /* ===== ISV ===== */
    protected function getISVActivosMap() {
        $map = [1=>0.0, 2=>0.0];
        $rs = $this->ejecutar_consulta_simple("SELECT isv_tipo_id, valor FROM isv WHERE activar=1");
        if ($rs) while($r=$rs->fetch_assoc()){ $map[intval($r['isv_tipo_id'])] = floatval($r['valor']); }
        return $map; // porcentajes
    }

    public function obtenerISVTiposPublico() {
        $m = $this->getISVActivosMap();
        return [['id'=>1,'valor'=>$m[1]??0],['id'=>2,'valor'=>$m[2]??0]];
    }

    /* ===== Mesas ===== */
    public function obtenerMesas() {
        $sql="SELECT mesa_id AS id, numero, capacidad, ubicacion, estado 
              FROM mesas 
              WHERE empresa_id = ".$this->empresaId()."
              ORDER BY numero+0, numero ASC";
        $rs = $this->ejecutar_consulta_simple($sql);
        $out=[];
        while($r=$rs->fetch_assoc()){
            $out[]=[
                'id'=>intval($r['id']),
                'numero'=>$r['numero'],
                'capacidad'=>intval($r['capacidad']),
                'ubicacion'=>$r['ubicacion'],
                'estado'=>$r['estado'],
            ];
        }
        return $out;
    }

    public function guardarMesa($numero,$capacidad,$ubicacion){
        $numero = $this->cleanString($numero);
        if($numero===''){ return ['status'=>false,'message'=>'Número requerido']; }
    
        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM mesas WHERE numero=? AND empresa_id=?", "si", [$numero,$this->empresaId()]);
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'Ya existe una mesa con ese número']; }
    
        $mesa_id = mainModel::correlativo("mesa_id","mesas");
        $ok = $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO mesas(mesa_id,numero,capacidad,ubicacion,estado,colaborador_id,empresa_id)
             VALUES(?,?,?,?, 'disponible', ?, ?)",
            "isisii",
            [$mesa_id,$numero,intval($capacidad),$ubicacion,$this->colaboradorId(),$this->empresaId()]
        );
        return $ok ? ['status'=>true,'id'=>$mesa_id,'message'=>'Mesa guardada']
                   : ['status'=>false,'message'=>'No se pudo guardar la mesa'];
    }
    

    /* ===== Catálogo ===== */

    /** Solo categorías con productos restaurante=1, e incluye nombre para reglas de cocina */
    public function obtenerCategoriasProductos(){
        $sql="SELECT DISTINCT c.categoria_id AS id, c.nombre
              FROM categoria c
              INNER JOIN productos p ON p.categoria_id=c.categoria_id
              WHERE c.estado=1 AND p.estado=1 AND p.restaurante=1
              ORDER BY c.nombre ASC";
        $rs=$this->ejecutar_consulta_simple($sql);
        $out=[];
        while($r=$rs->fetch_assoc()){ $out[]=['id'=>intval($r['id']),'nombre'=>$r['nombre']]; }
        return $out;
    }

    /** Guardar categoría (estado=1, fecha_registro=NOW) */
    public function guardarCategoria($nombre){
        $nombre = $this->cleanString($nombre);
        if($nombre===''){ return ['status'=>false,'message'=>'Nombre requerido']; }

        // evita duplicados exactos
        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM categoria WHERE nombre=? LIMIT 1", "s", [$nombre]
        );
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'La categoría ya existe']; }

        $id = mainModel::correlativo("categoria_id","categoria");
        $ok = $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO categoria(categoria_id,nombre,estado,fecha_registro) VALUES(?, ?, 1, NOW())",
            "is",
            [$id,$nombre]
        );
        return $ok ? ['status'=>true,'categoria_id'=>$id] : ['status'=>false,'message'=>'No se pudo guardar la categoría'];
    }

    /** Regla heurística para saber si algo va a cocina (sin tocar tu DB) */
    protected function esParaCocina($catNombre, $prodNombre){
        $s = mb_strtolower($catNombre.' '.$prodNombre,'UTF-8');
        $keysCocina = ['comida','plato','menu','menú','almuerzo','desayuno','cena','pollo','carne','pescado','sopa','pasta','arroz','taco','tamal','ensalada','frito','asado','parrilla','horno'];
        $keysNo    = ['refresco','soda','gaseosa','bebida','agua','cerveza','vino','licor','café','cafe','té','te','postre','snack'];
        foreach($keysNo as $k){ if(strpos($s,$k)!==false) return false; }
        foreach($keysCocina as $k){ if(strpos($s,$k)!==false) return true; }
        return false;
    }

    /** Trae productos restaurante=1 + flags ISV y marca si van a cocina (heurística por categoría/nombre) */
    public function obtenerProductos(){
        $sql="SELECT p.productos_id, p.nombre, p.descripcion, p.precio_venta,
                     p.cantidad_mayoreo, p.precio_mayoreo, p.file_name, p.categoria_id,
                     p.isv1, p.isv2, c.nombre AS categoria_nombre
              FROM productos p
              INNER JOIN categoria c ON c.categoria_id = p.categoria_id
              WHERE p.estado=1 AND p.restaurante=1
              ORDER BY p.nombre ASC";
        $rs=$this->ejecutar_consulta_simple($sql);
        $out=[];
        while($r=$rs->fetch_assoc()){
            $out[]=[
                'productos_id'     => intval($r['productos_id']),
                'nombre'           => $r['nombre'],
                'descripcion'      => $r['descripcion'],
                'precio_venta'     => floatval($r['precio_venta']),
                'cantidad_mayoreo' => floatval($r['cantidad_mayoreo']),
                'precio_mayoreo'   => floatval($r['precio_mayoreo']),
                'file_name'        => $r['file_name'],
                'categoria_id'     => intval($r['categoria_id']),
                'isv1'             => intval($r['isv1']),
                'isv2'             => intval($r['isv2']),
                'para_cocina'      => $this->esParaCocina($r['categoria_nombre'],$r['nombre']) ? 1 : 0,
            ];
        }
        return $out;
    }

    /** Guardar producto básico para restaurante */
    public function guardarProductoBasico($data){
        $nombre   = $this->cleanString($data['nombre'] ?? '');
        $desc     = $this->cleanString($data['descripcion'] ?? '');
        $catId    = intval($data['categoria_id'] ?? 0);
        $precio   = floatval($data['precio_venta'] ?? 0);
        $isv1     = intval($data['isv1'] ?? 0) ? 1 : 0;
        $isv2     = intval($data['isv2'] ?? 0) ? 1 : 0;

        if($nombre==='' || $catId<=0){
            return ['status'=>false,'message'=>'Nombre y categoría son obligatorios'];
        }

        $productos_id = mainModel::correlativo("productos_id","productos");

        // Defaults seguros para tu tabla
        $barCode           = '';
        $almacen_id        = 1;
        $medida_id         = 1;
        $tipo_producto_id  = 1; // 1=básico
        $precio_compra     = 0.00;
        $porcentaje_venta  = 0.00;
        $cantidad_mayoreo  = 0.00;
        $precio_mayoreo    = 0.00;
        $cantidad_minima   = 0;
        $cantidad_maxima   = 0;
        $estado            = 1;
        $isv_venta         = ($isv1||$isv2) ? 1 : 2; // 1=Sí, 2=No
        $isv_compra        = 2;                      // por ahora no
        $file_name         = '';                     // se podrá actualizar luego
        $empresa_id        = $this->empresaId();
        $colaborador_id    = $this->colaboradorId();
        $id_producto_sup   = 0;
        $restaurante       = 1;

        $ok = $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO productos(
                productos_id, barCode, almacen_id, medida_id, categoria_id, nombre, descripcion,
                tipo_producto_id, precio_compra, porcentaje_venta, precio_venta,
                cantidad_mayoreo, precio_mayoreo, cantidad_minima, cantidad_maxima,
                estado, isv_venta, isv_compra, colaborador_id, file_name, empresa_id,
                fecha_registro, id_producto_superior, restaurante, isv1, isv2
            ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
            "isiiissiddddddiiiiisiiiii",
            [
                $productos_id, $barCode, $almacen_id, $medida_id, $catId, $nombre, $desc,
                $tipo_producto_id, $precio_compra, $porcentaje_venta, $precio,
                $cantidad_mayoreo, $precio_mayoreo, $cantidad_minima, $cantidad_maxima,
                $estado, $isv_venta, $isv_compra, $colaborador_id, $file_name, $empresa_id,
                $id_producto_sup, $restaurante, $isv1, $isv2
            ]
        );

        return $ok ? ['status'=>true,'producto_id'=>$productos_id]
                   : ['status'=>false,'message'=>'No se pudo guardar el producto'];
    }

    public function obtenerClientes(){
        $sql="SELECT clientes_id, nombre, rtn AS identificacion
              FROM clientes WHERE estado=1 ORDER BY nombre ASC";
        $rs=$this->ejecutar_consulta_simple($sql);
        $out=[];
        while($r=$rs->fetch_assoc()){
            $out[]=[
                'clientes_id'=>intval($r['clientes_id']),
                'nombre'=>$r['nombre'],
                'identificacion'=>$r['identificacion'] ?? ''
            ];
        }
        return $out;
    }

    /** Guardar cliente básico conforme a tu estructura */
    public function guardarClienteBasico($data){
        $nombre  = $this->cleanString($data['nombre'] ?? '');
        if($nombre===''){ return ['status'=>false,'message'=>'Nombre requerido']; }

        $rtn       = $this->cleanString($data['rtn'] ?? '');
        $fecha     = $this->cleanString($data['fecha'] ?? date('Y-m-d'));
        $dept      = intval($data['departamentos_id'] ?? 0);
        $mun       = intval($data['municipios_id'] ?? 0);
        $localidad = $this->cleanString($data['localidad'] ?? '');
        $telefono  = $this->cleanString($data['telefono'] ?? '');
        $correo    = $this->cleanString($data['correo'] ?? '');
        $estado    = intval($data['estado'] ?? 1);

        // Campos “corporativos” presentes en la tabla (los dejamos vacíos)
        $empresaTxt = '';
        $eslogan    = '';
        $otraInfo   = '';
        $whatsapp   = '';

        $clientes_id = mainModel::correlativo("clientes_id","clientes");

        $ok = $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO clientes(
                clientes_id, nombre, rtn, fecha, departamentos_id, municipios_id, localidad,
                telefono, correo, estado, colaboradores_id, fecha_registro,
                empresa, eslogan, otra_informacion, whatsapp
            ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
            "isssiiissiiissss",
            [
                $clientes_id, $nombre, $rtn, $fecha, $dept, $mun, $localidad,
                $telefono, $correo, $estado, $this->colaboradorId(),
                $empresaTxt, $eslogan, $otraInfo, $whatsapp
            ]
        );

        return $ok ? ['status'=>true,'cliente'=>['clientes_id'=>$clientes_id,'nombre'=>$nombre,'rtn'=>$rtn]]
                   : ['status'=>false,'message'=>'No se pudo guardar el cliente'];
    }

    /* ===== Secuencia con condición de carrera ===== */

    protected function reservarNumeroFactura($empresa_id, $documento_id=1){
        $cnn = $this->connection();
        $cnn->begin_transaction();

        // 1) ¿Hay fallidas?
        $stmt = $cnn->prepare("SELECT id, numero FROM secuencia_factura_fallida WHERE empresa_id=? AND documento_id=? ORDER BY numero ASC LIMIT 1 FOR UPDATE");
        $stmt->bind_param("ii",$empresa_id,$documento_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $fallida = $res->fetch_assoc();
        $stmt->close();

        // obtener secuencia activa
        $s = $cnn->prepare("SELECT * FROM secuencia_facturacion WHERE empresa_id=? AND documento_id=? AND activo=1 LIMIT 1 FOR UPDATE");
        $s->bind_param("ii",$empresa_id,$documento_id);
        $s->execute();
        $rs = $s->get_result();
        if(!$rs->num_rows){
            $cnn->rollback();
            return ['error'=>true,'mensaje'=>'No hay secuencia activa'];
        }
        $seq = $rs->fetch_assoc();
        $s->close();

        if ($fallida) {
            $cnn->query("DELETE FROM secuencia_factura_fallida WHERE id=".intval($fallida['id'])." LIMIT 1");
            $cnn->commit();
            return [
                'error'=>false,
                'data'=>[
                    'secuencia_facturacion_id'=>intval($seq['secuencia_facturacion_id']),
                    'numero'  => intval($fallida['numero']),
                    'prefijo' => $seq['prefijo'],
                    'relleno' => intval($seq['relleno'])
                ],
                'fuente'=>'fallida'
            ];
        }

        // 2) Consumir siguiente
        $siguiente = intval($seq['siguiente']);
        if ($siguiente > intval($seq['rango_final'])) {
            $cnn->rollback();
            return ['error'=>true,'mensaje'=>'Se alcanzó el rango final de la secuencia'];
        }

        $nuevo = $siguiente + intval($seq['incremento']);
        $up = $cnn->prepare("UPDATE secuencia_facturacion SET siguiente=? WHERE secuencia_facturacion_id=?");
        $up->bind_param("ii", $nuevo, $seq['secuencia_facturacion_id']);
        if(!$up->execute()){
            $cnn->rollback();
            return ['error'=>true,'mensaje'=>'No se pudo avanzar secuencia'];
        }
        $up->close();
        $cnn->commit();

        return [
            'error'=>false,
            'data'=>[
                'secuencia_facturacion_id'=>intval($seq['secuencia_facturacion_id']),
                'numero'  => $siguiente,
                'prefijo' => $seq['prefijo'],
                'relleno' => intval($seq['relleno'])
            ],
            'fuente'=>'secuencia'
        ];
    }

    protected function registrarNumeroFallido($empresa_id,$documento_id,$numero){
        if($numero<=0) return;
        $id = mainModel::correlativo("id","secuencia_factura_fallida");
        $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO secuencia_factura_fallida(id, empresa_id, documento_id, numero, fecha_registro)
             VALUES(?, ?, ?, ?, NOW())",
            "iiii", [$id,$empresa_id,$documento_id,intval($numero)]
        );
    }

    /* ===== Factura: leer por mesa ===== */
    public function obtenerFacturaMesa($mesa_id){
        $sql="SELECT 
                f.*, 
                fc.mesa_id,
                m.numero AS numero_mesa, m.capacidad AS capacidad_mesa, m.ubicacion AS ubicacion_mesa,
                c.clientes_id AS cliente_id, c.nombre AS cliente_nombre, c.rtn AS cliente_identificacion
              FROM facturas f
              INNER JOIN factura_comanda fc ON fc.factura_id = f.facturas_id
              INNER JOIN mesas m ON m.mesa_id = fc.mesa_id
              LEFT JOIN clientes c ON c.clientes_id = f.clientes_id
              WHERE fc.mesa_id = ? AND f.estado IN (1,2,3)
              ORDER BY f.facturas_id DESC
              LIMIT 1";
        $rs = $this->ejecutar_consulta_simple_preparada($sql,"i",[intval($mesa_id)]);
        return $rs && $rs->num_rows ? $rs->fetch_assoc() : null;
    }

    public function obtenerDetallesFactura($factura_id){
        $sql="SELECT fd.facturas_detalle_id, fd.productos_id, fd.cantidad, fd.precio, fd.isv_valor, fd.descuento, fd.medida,
                     p.nombre AS nombre_producto, p.descripcion AS descripcion_producto
              FROM facturas_detalles fd
              INNER JOIN productos p ON p.productos_id = fd.productos_id
              WHERE fd.facturas_id = ?";
        $rs=$this->ejecutar_consulta_simple_preparada($sql,"i",[intval($factura_id)]);
        $out=[];
        while($r=$rs->fetch_assoc()){
            $out[]=[
                'facturas_detalle_id'=>intval($r['facturas_detalle_id']),
                'productos_id'       =>intval($r['productos_id']),
                'nombre_producto'    =>$r['nombre_producto'],
                'descripcion_producto'=>$r['descripcion_producto']??'',
                'cantidad'           =>floatval($r['cantidad']),
                'precio'             =>floatval($r['precio']),
                'isv_valor'          =>floatval($r['isv_valor']),
                'descuento'          =>floatval($r['descuento']),
                'medida'             =>$r['medida']
            ];
        }
        return $out;
    }

    /* ===== Guardar / Actualizar factura ===== */

    protected function calcularTotalesDesdeItems(array $items){
        if(empty($items)) return ['subtotal'=>0,'imp1'=>0,'imp2'=>0,'total'=>0];

        $ids = array_map(fn($i)=>intval($i['producto_id']), $items);
        $place = implode(',', array_fill(0,count($ids),'?'));
        $types = str_repeat('i', count($ids));
        $rs = $this->ejecutar_consulta_simple_preparada(
            "SELECT productos_id, isv1, isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids);

        $flags=[]; while($r=$rs->fetch_assoc()){
            $flags[intval($r['productos_id'])]=['isv1'=>intval($r['isv1'])==1,'isv2'=>intval($r['isv2'])==1];
        }

        $isv = $this->getISVActivosMap();
        $r1 = ($isv[1]??0)/100.0; $r2 = ($isv[2]??0)/100.0;

        $subtotal=0; $imp1=0; $imp2=0;
        foreach($items as $it){
            $pid=intval($it['producto_id']); $qty=floatval($it['cantidad']); $pu=floatval($it['precio']);
            $subtotal += $qty*$pu;
            $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
            if($f['isv1']) $imp1 += ($pu*$r1)*$qty;
            if($f['isv2']) $imp2 += ($pu*$r2)*$qty;
        }
        return ['subtotal'=>round($subtotal,2), 'imp1'=>round($imp1,2), 'imp2'=>round($imp2,2), 'total'=>round($subtotal+$imp1+$imp2,2)];
    }

    protected function tipoPagoIdDetalle($metodo){
        $m = strtolower(trim((string)$metodo));
        if ($m==='tarjeta') return 2;
        if ($m==='transferencia') return 3;
        return 1; // efectivo
    }

    /** Crea una factura como borrador o pagada (si se indica método de pago) */
    public function guardarFactura($data){
        $cnn = $this->connection();
        $numeroReservado = null;

        try {
            $mesa_id   = intval($data['mesa_id'] ?? 0);
            $cliente_id= intval($data['cliente_id'] ?? 0);
            $items     = is_array($data['items'] ?? null) ? $data['items'] : [];
            $metodo    = trim((string)($data['metodo_pago'] ?? '')); // '', 'efectivo','tarjeta','transferencia'
            $notas     = $this->cleanString($data['observaciones'] ?? '');

            if (empty($items)) return ['status'=>false,'message'=>'No hay items'];

            $tot = $this->calcularTotalesDesdeItems($items);

            // Reservar número (con condición de carrera)
            $res = $this->reservarNumeroFactura($this->empresaId(), 1);
            if($res['error']) return ['status'=>false,'message'=>$res['mensaje']];
            $numeroReservado = intval($res['data']['numero']);

            $cnn->begin_transaction();

            // Insert factura
            $factura_id = mainModel::correlativo("facturas_id","facturas");

            $estado = ($metodo==='') ? 1 : 2; // 1=Borrador, 2=Pagada
            $tipo_factura = 1; // contado
            $hoy = date('Y-m-d');

            $q = "INSERT INTO facturas(
                    facturas_id, clientes_id, secuencia_facturacion_id, apertura_id, number,
                    tipo_factura, colaboradores_id, importe, notas, fecha, estado,
                    usuario, empresa_id, fecha_registro, fecha_dolar,
                    no_orden, constancia, identificativo_sag, numero_interno
                  )
                  VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NULL, NULL, NULL, NULL)";
            $ok = $this->ejecutar_consulta_simple_preparada($q,"iiiiiiidssiisi",
                [
                    $factura_id,
                    $cliente_id,
                    $res['data']['secuencia_facturacion_id'],
                    $this->aperturaId(),
                    $numeroReservado,
                    $tipo_factura,
                    $this->colaboradorId(),
                    $tot['total'],
                    $notas,
                    $hoy,
                    $estado,
                    $this->usuarioId(),
                    $this->empresaId(),
                    $hoy
                ]
            );
            if(!$ok){ throw new Exception("No se pudo crear la factura"); }

            // Detalles
            $isv = $this->getISVActivosMap(); $r1=($isv[1]??0)/100.0; $r2=($isv[2]??0)/100.0;
            $ids = array_map(fn($i)=>intval($i['producto_id']), $items);
            $place = implode(',', array_fill(0,count($ids),'?'));
            $types = str_repeat('i', count($ids));
            $rs = $this->ejecutar_consulta_simple_preparada("SELECT productos_id,isv1,isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids);
            $flags=[]; while($r=$rs->fetch_assoc()){ $flags[intval($r['productos_id'])]=['isv1'=>intval($r['isv1'])==1,'isv2'=>intval($r['isv2'])==1]; }

            foreach($items as $it){
                $pid=intval($it['producto_id']); $qty=max(1,intval($it['cantidad'])); $pu=floatval($it['precio']);
                $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $taxUnit = ($f['isv1']?($pu*$r1):0)+($f['isv2']?($pu*$r2):0);
                $isv_line = $taxUnit*$qty;
                $det_id = mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles(facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                     VALUES(?, ?, ?, ?, ?, ?, 0, 'UNIDAD')",
                    "iiiiid", [$det_id,$factura_id,$pid,$qty,$pu,$isv_line]
                );
            }

            // Mesa
            if ($mesa_id>0) {
                $com_id = mainModel::correlativo("id","factura_comanda");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO factura_comanda(id,factura_id,mesa_id,comentarios_cocina,estado,fecha_registro)
                     VALUES(?, ?, ?, ?, 'pendiente', NOW())",
                    "iiis", [$com_id,$factura_id,$mesa_id,$notas]
                );
                $this->ejecutar_consulta_simple_preparada("UPDATE mesas SET estado='ocupada' WHERE mesa_id=?","i",[$mesa_id]);
            }

            // Pago (si ya viene método)
            if ($metodo!=='') {
                $tipo = 1; // Contado
                $pagos_id = mainModel::correlativo("pagos_id","pagos");
                $efectivo = ($metodo==='efectivo') ? $tot['total'] : 0.00;
                $tarjeta  = ($metodo==='tarjeta' || $metodo==='transferencia') ? $tot['total'] : 0.00;

                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO pagos(pagos_id, facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta, usuario, estado, empresa_id, fecha_registro, contabilizado, referencia_ingreso_id)
                     VALUES(?, ?, ?, CURDATE(), ?, ?, 0, ?, ?, 1, ?, NOW(), 0, NULL)",
                    "iiidddii",
                    [$pagos_id, $factura_id, $tipo, $tot['total'], $efectivo, $tarjeta, $this->usuarioId(), $this->empresaId()]
                );

                $pd_id = mainModel::correlativo("pagos_detalles_id","pagos_detalles");
                $tipo_det = $this->tipoPagoIdDetalle($metodo);
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO pagos_detalles(pagos_detalles_id, pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1, descripcion2, descripcion3)
                     VALUES(?, ?, ?, 0, ?, '', '', '')",
                    "iiid", [$pd_id,$pagos_id,$tipo_det,$tot['total']]
                );
            }

            $cnn->commit();

            return [
                'status'=>true,
                'message'=>'Factura creada',
                'factura'=>[
                    'id'=>$factura_id,
                    'factura_id'=>$factura_id,
                    'number'=>$numeroReservado,
                    'total'=>$tot['total']
                ]
            ];

        } catch (Throwable $e) {
            $cnn->rollback();
            if($numeroReservado){
                $this->registrarNumeroFallido($this->empresaId(),1,$numeroReservado);
            }
            return ['status'=>false,'message'=>'Error al guardar: '.$e->getMessage()];
        }
    }

    /** Actualiza items y puede registrar pago */
    public function actualizarFactura($data){
        $cnn = $this->connection();
        try{
            $factura_id = intval($data['factura_id']);
            $mesa_id    = intval($data['mesa_id'] ?? 0);
            $cliente_id = intval($data['cliente_id'] ?? 0);
            $items      = is_array($data['items'] ?? null) ? $data['items'] : [];
            $metodo     = trim((string)($data['metodo_pago'] ?? ''));
            $notas      = $this->cleanString($data['observaciones'] ?? '');

            if (empty($items)) return ['status'=>false,'message'=>'No hay items'];

            $tot = $this->calcularTotalesDesdeItems($items);

            $cnn->begin_transaction();

            // Estado actual
            $rs = $this->ejecutar_consulta_simple_preparada("SELECT estado FROM facturas WHERE facturas_id=?","i",[$factura_id]);
            if(!$rs || !$rs->num_rows){ throw new Exception("Factura no existe"); }
            $estadoActual = intval($rs->fetch_assoc()['estado']);

            $nuevoEstado = ($metodo==='') ? $estadoActual : 2; // si paga -> Pagada
            $this->ejecutar_consulta_simple_preparada(
                "UPDATE facturas SET clientes_id=?, importe=?, notas=?, estado=? WHERE facturas_id=?",
                "idsii", [$cliente_id, $tot['total'], $notas, $nuevoEstado, $factura_id]
            );

            // Mesa
            if ($mesa_id>0) {
                $chk = $this->ejecutar_consulta_simple_preparada("SELECT id FROM factura_comanda WHERE factura_id=?","i",[$factura_id]);
                if($chk && $chk->num_rows){
                    $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET mesa_id=?, comentarios_cocina=? WHERE factura_id=?","isi",[$mesa_id,$notas,$factura_id]);
                }else{
                    $cid=mainModel::correlativo("id","factura_comanda");
                    $this->ejecutar_consulta_simple_preparada("INSERT INTO factura_comanda(id,factura_id,mesa_id,comentarios_cocina,estado,fecha_registro) VALUES(?, ?, ?, ?, 'pendiente', NOW())","iiis",[$cid,$factura_id,$mesa_id,$notas]);
                }
                $this->ejecutar_consulta_simple_preparada("UPDATE mesas SET estado='ocupada' WHERE mesa_id=?","i",[$mesa_id]);
            }

            // Reemplazar detalles
            $this->ejecutar_consulta_simple_preparada("DELETE FROM facturas_detalles WHERE facturas_id=?","i",[$factura_id]);

            $isv=$this->getISVActivosMap(); $r1=($isv[1]??0)/100.0; $r2=($isv[2]??0)/100.0;
            $ids = array_map(fn($i)=>intval($i['producto_id']), $items);
            $place = implode(',', array_fill(0,count($ids),'?')); $types = str_repeat('i', count($ids));
            $rs2 = $this->ejecutar_consulta_simple_preparada("SELECT productos_id,isv1,isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids);
            $flags=[]; while($r=$rs2->fetch_assoc()){ $flags[intval($r['productos_id'])]=['isv1'=>intval($r['isv1'])==1,'isv2'=>intval($r['isv2'])==1]; }

            foreach($items as $it){
                $pid=intval($it['producto_id']); $qty=max(1,intval($it['cantidad'])); $pu=floatval($it['precio']);
                $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $taxUnit = ($f['isv1']?($pu*$r1):0)+($f['isv2']?($pu*$r2):0);
                $isv_line = $taxUnit*$qty;
                $det_id = mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles(facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                     VALUES(?, ?, ?, ?, ?, ?, 0, 'UNIDAD')",
                    "iiiiid", [$det_id,$factura_id,$pid,$qty,$pu,$isv_line]
                );
            }

            // Si se paga ahora:
            if ($metodo!=='') {
                $tipo = 1; // contado
                $rp = $this->ejecutar_consulta_simple_preparada("SELECT pagos_id FROM pagos WHERE facturas_id=? AND estado=1","i",[$factura_id]);
                if ($rp && $rp->num_rows){
                    $pid = intval($rp->fetch_assoc()['pagos_id']);
                    $efectivo = ($metodo==='efectivo') ? $tot['total'] : 0.00;
                    $tarjeta  = ($metodo==='tarjeta'||$metodo==='transferencia') ? $tot['total'] : 0.00;
                    $this->ejecutar_consulta_simple_preparada(
                        "UPDATE pagos SET tipo_pago=?, importe=?, efectivo=?, tarjeta=?, cambio=0 WHERE pagos_id=?",
                        "idddi", [$tipo,$tot['total'],$efectivo,$tarjeta,$pid]
                    );
                }else{
                    $pid = mainModel::correlativo("pagos_id","pagos");
                    $efectivo = ($metodo==='efectivo') ? $tot['total'] : 0.00;
                    $tarjeta  = ($metodo==='tarjeta'||$metodo==='transferencia') ? $tot['total'] : 0.00;
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO pagos(pagos_id, facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta, usuario, estado, empresa_id, fecha_registro, contabilizado, referencia_ingreso_id)
                         VALUES(?, ?, ?, CURDATE(), ?, ?, 0, ?, ?, 1, ?, NOW(), 0, NULL)",
                        "iiidddii",
                        [$pid, $factura_id, $tipo, $tot['total'], $efectivo, $tarjeta, $this->usuarioId(), $this->empresaId()]
                    );
                }
                $pd_id = mainModel::correlativo("pagos_detalles_id","pagos_detalles");
                $tipo_det = $this->tipoPagoIdDetalle($metodo);
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO pagos_detalles(pagos_detalles_id, pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1, descripcion2, descripcion3)
                     VALUES(?, ?, ?, 0, ?, '', '', '')",
                    "iiid", [$pd_id,$pid,$tipo_det,$tot['total']]
                );
            }

            $cnn->commit();
            return ['status'=>true,'message'=>'Factura actualizada','factura_id'=>$factura_id];

        }catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al actualizar: '.$e->getMessage()];
        }
    }

    public function cerrarFactura($factura_id){
        try{
            $factura_id=intval($factura_id);
            if($factura_id<=0) return ['status'=>false,'message'=>'ID inválido'];

            // liberar mesa si la tenía
            $rm = $this->ejecutar_consulta_simple_preparada("SELECT mesa_id FROM factura_comanda WHERE factura_id=?","i",[$factura_id]);
            if($rm && $rm->num_rows){
                $mesa_id=intval($rm->fetch_assoc()['mesa_id']);
                if($mesa_id>0){
                    $this->ejecutar_consulta_simple_preparada("UPDATE mesas SET estado='disponible' WHERE mesa_id=?","i",[$mesa_id]);
                }
            }
            // si está pagada queda 2; si no, cancelar (4)
            $rp = $this->ejecutar_consulta_simple_preparada("SELECT 1 FROM pagos WHERE facturas_id=? AND estado=1","i",[$factura_id]);
            $nuevo = ($rp && $rp->num_rows) ? 2 : 4;
            $this->ejecutar_consulta_simple_preparada("UPDATE facturas SET estado=? WHERE facturas_id=?","ii",[$nuevo,$factura_id]);

            return ['status'=>true,'message'=>'Factura cerrada'];
        }catch(Throwable $e){
            return ['status'=>false,'message'=>'Error: '.$e->getMessage()];
        }
    }

    /* ===== Cocina ===== */
    public function enviarComandaCocina($data){
        try{
            $factura_id = intval($data['factura_id'] ?? 0);
            $coment     = $this->cleanString($data['observaciones'] ?? '');

            if ($factura_id>0){
                $chk = $this->ejecutar_consulta_simple_preparada("SELECT id FROM factura_comanda WHERE factura_id=?","i",[$factura_id]);
                if($chk && $chk->num_rows){
                    $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET comentarios_cocina=?, estado='pendiente' WHERE factura_id=?","si",[$coment,$factura_id]);
                }else{
                    $cid=mainModel::correlativo("id","factura_comanda");
                    $this->ejecutar_consulta_simple_preparada("INSERT INTO factura_comanda(id, factura_id, mesa_id, comentarios_cocina, estado, fecha_registro)
                        VALUES(?, ?, 0, ?, 'pendiente', NOW())","iis",[$cid,$factura_id,$coment]);
                }
            }
            return ['status'=>true,'message'=>'Comanda enviada'];
        }catch(Throwable $e){
            return ['status'=>false,'message'=>'Error: '.$e->getMessage()];
        }
    }

    public function marcarComandaPreparada($factura_id){
        $ok = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado='preparada' WHERE factura_id=?","i",[intval($factura_id)]);
        return $ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }
    public function marcarComandaUrgente($factura_id,$urgente){
        $estado = $urgente ? 'urgente' : 'pendiente';
        $ok = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado=? WHERE factura_id=?","si",[$estado,intval($factura_id)]);
        return $ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }
    public function marcarComandaEnPreparacion($factura_id){
        $ok = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado='en_preparacion' WHERE factura_id=?","i",[intval($factura_id)]);
        return $ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }
}
