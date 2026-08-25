<?php
// core/facturasRestaurante/facturasRestauranteModelo.php
require_once __DIR__ . '/../mainModel.php';

class facturasRestauranteModelo extends mainModel {

    /* ===== Helpers de sesión ===== */
    protected function empresaId()     { return intval($_SESSION['empresa_id_sd'] ?? 1); }
    protected function usuarioId()     { return intval($_SESSION['users_id_sd']    ?? 1); }
    protected function colaboradorId() { return intval($_SESSION['colaborador_id_sd'] ?? 1); }
    protected function aperturaId()    { return intval($_SESSION['apertura_id_sd'] ?? 0); } // 0 si no hay caja abierta

    /* ===== Cache columnas (para manejar categoria.estacion opcional) ===== */
    private $columnCache = [];
    protected function hasColumn(string $table, string $column): bool {
        $k = $table.'.'.$column;
        if (isset($this->columnCache[$k])) return $this->columnCache[$k];

        // Usar INFORMATION_SCHEMA con prepared statements (SHOW no soporta placeholders)
        $sql = "SELECT 1
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                LIMIT 1";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "ss", [$table, $column]);
        $ok = ($rs && $rs->num_rows > 0);

        $this->columnCache[$k] = $ok;
        return $ok;
    }

    /** Cache de existencia de tablas opcionales del módulo */
    private $tableCache = [];
    protected function hasTable(string $table): bool {
        if (isset($this->tableCache[$table])) return $this->tableCache[$table];
        $rs = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1",
            "s", [$table]
        );
        $ok = ($rs && $rs->num_rows > 0);
        $this->tableCache[$table] = $ok;
        return $ok;
    }

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
        $empresaId = $this->empresaId();

        // La ocupación real se deriva de una factura ABIERTA asociada a la mesa.
        // Esto corrige estados viejos que podían quedar guardados como "ocupada" aunque ya no existiera cuenta abierta.
        $abiertas = [];
        if ($this->hasTable('factura_restaurante_cuentas')) {
            $qa = $this->ejecutar_consulta_simple_preparada(
                "SELECT DISTINCT rc.mesa_id
                 FROM factura_restaurante_cuentas rc
                 INNER JOIN facturas f ON f.facturas_id=rc.factura_id
                 WHERE rc.empresa_id=? AND rc.estado='abierta' AND rc.mesa_id>0 AND f.estado=1
                   AND DATE(COALESCE(rc.fecha_actualizacion, rc.fecha_registro)) = CURDATE()",
                "i", [$empresaId]
            );
            if ($qa) while($a=$qa->fetch_assoc()) $abiertas[(int)$a['mesa_id']] = true;
        }

        $reservas=[];
        if ($this->hasTable('mesas_reservas')) {
            $rr = $this->ejecutar_consulta_simple_preparada(
                "SELECT r.mesa_reserva_id, r.mesa_id, r.clientes_id, r.fecha_reserva, r.hora_reserva,
                        r.personas, r.notas, c.nombre AS cliente_nombre, c.rtn AS cliente_rtn
                 FROM mesas_reservas r
                 LEFT JOIN clientes c ON c.clientes_id=r.clientes_id
                 WHERE r.empresa_id=? AND r.estado='activa'",
                "i", [$empresaId]
            );
            if ($rr) while($q=$rr->fetch_assoc()) $reservas[(int)$q['mesa_id']]=$q;
        }

        $rs = $this->ejecutar_consulta_simple_preparada(
            "SELECT mesa_id AS id, numero, capacidad, ubicacion, estado
             FROM mesas
             WHERE empresa_id = ?
             ORDER BY numero+0, numero ASC",
            "i", [$empresaId]
        );

        $out=[];
        if (!$rs) return $out;

        while($r=$rs->fetch_assoc()){
            $id=(int)$r['id'];
            $res=$reservas[$id] ?? null;
            $estadoBD=strtolower(trim((string)($r['estado'] ?? 'disponible')));
            $tieneCuenta=!empty($abiertas[$id]);

            if ($estadoBD === 'mantenimiento') {
                $estado = 'mantenimiento';
            } elseif ($tieneCuenta) {
                $estado = 'ocupada';
            } elseif ($res) {
                $estado = 'reservada';
            } else {
                $estado = 'disponible';
            }

            // Sincronizar solamente estados operativos viejos. Nunca tocar mantenimiento aquí.
            if ($estadoBD !== 'mantenimiento' && $estadoBD !== $estado) {
                $this->ejecutar_consulta_simple_preparada(
                    "UPDATE mesas SET estado=? WHERE mesa_id=? AND empresa_id=?",
                    "sii", [$estado,$id,$empresaId]
                );
            }

            $out[]=[
                'id'=>$id,
                'numero'=>$r['numero'],
                'capacidad'=>(int)$r['capacidad'],
                'ubicacion'=>$r['ubicacion'],
                'estado'=>$estado,
                'tiene_cuenta_abierta'=>$tieneCuenta ? 1 : 0,
                'reserva'=>$res ? [
                    'mesa_reserva_id'=>(int)$res['mesa_reserva_id'],
                    'clientes_id'=>(int)$res['clientes_id'],
                    'cliente_nombre'=>$res['cliente_nombre'] ?? '',
                    'cliente_rtn'=>$res['cliente_rtn'] ?? '',
                    'fecha_reserva'=>$res['fecha_reserva'] ?? '',
                    'hora_reserva'=>$res['hora_reserva'] ?? '',
                    'personas'=>(int)($res['personas'] ?? 0),
                    'notas'=>$res['notas'] ?? ''
                ] : null,
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

    public function actualizarMesa($mesa_id,$numero,$capacidad,$ubicacion,$estado=''){
        $mesa_id = intval($mesa_id);
        $numero  = $this->cleanString($numero);
        $capacidad = max(1, intval($capacidad));
        $ubicacion = $this->cleanString($ubicacion);
        $estado = strtolower(trim((string)$estado));
        if($mesa_id<=0 || $numero===''){ return ['status'=>false,'message'=>'Datos de mesa inválidos']; }

        $empresaId = $this->empresaId();
        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM mesas WHERE numero=? AND empresa_id=? AND mesa_id<>?",
            "sii", [$numero,$empresaId,$mesa_id]
        );
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'Ya existe otra mesa con ese número']; }

        // El estado ocupado/reservado se deriva de la cuenta/reserva. Solo permitimos
        // cambiar manualmente disponible/mantenimiento desde edición.
        $estadoManual = in_array($estado, ['disponible','mantenimiento'], true) ? $estado : '';
        $cnn = $this->connection();
        if ($estadoManual !== '') {
            $stmt = $cnn->prepare("UPDATE mesas SET numero=?, capacidad=?, ubicacion=?, estado=? WHERE mesa_id=? AND empresa_id=?");
            if (!$stmt) return ['status'=>false,'message'=>'No se pudo preparar la actualización de la mesa'];
            $stmt->bind_param('sissii', $numero, $capacidad, $ubicacion, $estadoManual, $mesa_id, $empresaId);
        } else {
            $stmt = $cnn->prepare("UPDATE mesas SET numero=?, capacidad=?, ubicacion=? WHERE mesa_id=? AND empresa_id=?");
            if (!$stmt) return ['status'=>false,'message'=>'No se pudo preparar la actualización de la mesa'];
            $stmt->bind_param('sisii', $numero, $capacidad, $ubicacion, $mesa_id, $empresaId);
        }
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        return $ok ? ['status'=>true,'message'=>'Mesa actualizada correctamente'] : ['status'=>false,'message'=>$err ?: 'No se pudo actualizar la mesa'];
    }
    

    /* ===== Catálogo ===== */

    /** Solo categorías con productos restaurante=1; incluye estacion SI existe la columna */
    public function obtenerCategoriasProductos($estacion = 'todas'){
        // ¿La tabla categoria tiene columna 'estacion'?
        $tieneEst = $this->hasColumn('categoria','estacion');
    
        // Valor a devolver en 'estacion'
        $selEst = $tieneEst
            ? "LOWER(TRIM(COALESCE(NULLIF(c.estacion,''),'ninguna')))"
            : "'ninguna'";
    
        // WHERE base
        $where = "WHERE c.estado = 1";
    
        // Filtrar por estación según el parámetro
        if ($tieneEst) {
            if ($estacion === 'cocina') {
                $where .= " AND LOWER(TRIM(c.estacion)) = 'cocina'";
            } elseif ($estacion === 'barra') {
                $where .= " AND LOWER(TRIM(c.estacion)) = 'barra'";
            } elseif ($estacion === 'todas') {
                // IMPORTANT: incluir solo cocina y barra, excluir 'ninguna'
                $where .= " AND LOWER(TRIM(c.estacion)) IN ('cocina','barra')";
            }
            // cualquier otro valor/por defecto no agrega filtro extra
        }
    
        $sql = "SELECT
                    c.categoria_id AS id,
                    c.nombre,
                    {$selEst} AS estacion
                FROM categoria AS c
                {$where}
                ORDER BY c.nombre ASC";
    
        $rs = $this->ejecutar_consulta_simple($sql);
    
        $out = [];
        while ($r = $rs->fetch_assoc()){
            $est = strtolower(trim($r['estacion'] ?? 'ninguna'));
            if (!in_array($est, ['cocina','barra','ninguna'], true)) {
                $est = 'ninguna';
            }
            $out[] = [
                'id'       => (int)$r['id'],
                'nombre'   => $r['nombre'],
                'estacion' => $est,
            ];
        }
        return $out;
    }    
         
    /** Guardar categoría (requiere estacion cocina|barra si existe la columna) */
    public function guardarCategoria($nombre, $estacion){
        $nombre   = $this->cleanString($nombre);
        $estacion = strtolower($this->cleanString($estacion));

        if ($nombre === '') {
            return ['status'=>false,'message'=>'Nombre requerido'];
        }
        if (!in_array($estacion, ['cocina','barra'], true)) {
            return ['status'=>false,'message'=>'Seleccione estación válida (cocina o barra)'];
        }

        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM categoria WHERE nombre=? LIMIT 1", "s", [$nombre]
        );
        if ($dup && $dup->num_rows) {
            return ['status'=>false,'message'=>'La categoría ya existe'];
        }

        $id = mainModel::correlativo("categoria_id","categoria");

        if ($this->hasColumn('categoria','estacion')) {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO categoria(categoria_id,nombre,estacion,estado,fecha_registro) 
                VALUES(?, ?, ?, 1, NOW())",
                "iss",
                [$id,$nombre,$estacion]
            );
        } else {
            // Si tu esquema SIEMPRE tiene columna 'estacion', esto no ocurrirá,
            // pero lo dejamos por compatibilidad.
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO categoria(categoria_id,nombre,estado,fecha_registro) 
                VALUES(?, ?, 1, NOW())",
                "is",
                [$id,$nombre]
            );
        }

        return $ok ? ['status'=>true,'categoria_id'=>$id]
                : ['status'=>false,'message'=>'No se pudo guardar la categoría'];
    }

    /** Actualizar categoría con mensajes claros y sin falsos errores */
    public function actualizarCategoria($categoria_id, $nombre, $estacion){
        $categoria_id = intval($categoria_id);
        $nombre   = $this->cleanString($nombre);
        $estacion = strtolower($this->cleanString($estacion));

        if ($categoria_id <= 0 || $nombre === '') {
            return ['status'=>false,'message'=>'Datos inválidos'];
        }
        $usaEstacion = $this->hasColumn('categoria','estacion');
        if ($usaEstacion && !in_array($estacion, ['cocina','barra'], true)) {
            return ['status'=>false,'message'=>'Seleccione estación válida (cocina o barra)'];
        }

        // 1) Cargar valores actuales para detectar "sin cambios"
        $curr = $this->ejecutar_consulta_simple_preparada(
            $usaEstacion
                ? "SELECT nombre, estacion FROM categoria WHERE categoria_id=? LIMIT 1"
                : "SELECT nombre FROM categoria WHERE categoria_id=? LIMIT 1",
            "i",
            [$categoria_id]
        );
        if (!$curr || !$curr->num_rows) {
            return ['status'=>false,'message'=>'Categoría no encontrada'];
        }
        $row = $curr->fetch_assoc();
        if ($usaEstacion) {
            $actualIgual = (strcasecmp($row['nombre'] ?? '', $nombre) === 0)
                        && (strcasecmp($row['estacion'] ?? '', $estacion) === 0);
        } else {
            $actualIgual = (strcasecmp($row['nombre'] ?? '', $nombre) === 0);
        }
        if ($actualIgual) {
            // Nada cambió: éxito “silencioso”
            return ['status'=>true,'message'=>'Sin cambios'];
        }

        // 2) Chequear duplicado EXCLUYENDO el propio ID
        if ($usaEstacion) {
            $dup = $this->ejecutar_consulta_simple_preparada(
                "SELECT 1 FROM categoria WHERE nombre=? AND estacion=? AND categoria_id<>? LIMIT 1",
                "ssi",
                [$nombre, $estacion, $categoria_id]
            );
        } else {
            $dup = $this->ejecutar_consulta_simple_preparada(
                "SELECT 1 FROM categoria WHERE nombre=? AND categoria_id<>? LIMIT 1",
                "si",
                [$nombre, $categoria_id]
            );
        }
        if ($dup && $dup->num_rows) {
            return [
                'status'=>false,
                'message'=>'Ya existe una categoría con ese nombre' . ($usaEstacion ? ' en esa estación' : '')
            ];
        }

        // 3) Ejecutar UPDATE
        if ($usaEstacion) {
            $res = $this->ejecutar_consulta_simple_preparada(
                "UPDATE categoria SET nombre=?, estacion=? WHERE categoria_id=?",
                "ssi",
                [$nombre, $estacion, $categoria_id]
            );
        } else {
            $res = $this->ejecutar_consulta_simple_preparada(
                "UPDATE categoria SET nombre=? WHERE categoria_id=?",
                "si",
                [$nombre, $categoria_id]
            );
        }

        // 4) Si falló el UPDATE, re-verifica por si es un choque de índice único
        if ($res === false) {
            if ($usaEstacion) {
                $dup2 = $this->ejecutar_consulta_simple_preparada(
                    "SELECT 1 FROM categoria WHERE nombre=? AND estacion=? AND categoria_id<>? LIMIT 1",
                    "ssi",
                    [$nombre, $estacion, $categoria_id]
                );
            } else {
                $dup2 = $this->ejecutar_consulta_simple_preparada(
                    "SELECT 1 FROM categoria WHERE nombre=? AND categoria_id<>? LIMIT 1",
                    "si",
                    [$nombre, $categoria_id]
                );
            }
            if ($dup2 && $dup2->num_rows) {
                return [
                    'status'=>false,
                    'message'=>'Ya existe una categoría con ese nombre' . ($usaEstacion ? ' en esa estación' : '')
                ];
            }
            return ['status'=>false,'message'=>'No se pudo actualizar la categoría'];
        }

        // 5) Éxito (aunque affected_rows sea 0, ya filtramos “sin cambios” arriba)
        return ['status'=>true,'message'=>'Categoría actualizada'];
    }
    /** Heurística de estación cuando no está definida */
    protected function esParaCocinaHeuristica($catNombre, $prodNombre){
        $s = mb_strtolower($catNombre.' '.$prodNombre,'UTF-8');
        $keysCocina = ['comida','plato','menu','menú','almuerzo','desayuno','cena','pollo','carne','pescado','sopa','pasta','arroz','taco','tamal','ensalada','frito','asado','parrilla','horno'];
        $keysNo    = ['refresco','soda','gaseosa','bebida','agua','cerveza','vino','licor','café','cafe','té','te','postre','snack'];
        foreach($keysNo as $k){ if(strpos($s,$k)!==false) return false; }
        foreach($keysCocina as $k){ if(strpos($s,$k)!==false) return true; }
        return false;
    }

    /** Trae productos restaurante=1 + flags ISV y estación por categoría si existe */
    public function obtenerProductos(){
        $tieneEstCat  = $this->hasColumn('categoria','estacion');
        $tieneEstProd = $this->hasColumn('productos','estacion');
        $catEstSel    = $tieneEstCat ? "LOWER(TRIM(COALESCE(NULLIF(c.estacion,''),'ninguna')))" : "'ninguna'";
        $prodEstSel   = $tieneEstProd ? "LOWER(TRIM(COALESCE(NULLIF(p.estacion,''), NULLIF(c.estacion,''), 'ninguna')))" : $catEstSel;

        $sql="SELECT p.productos_id, p.nombre, p.descripcion, p.precio_venta,
                     p.cantidad_mayoreo, p.precio_mayoreo, p.file_name, p.categoria_id,
                     p.isv1, p.isv2, p.barCode, p.almacen_id, p.medida_id,
                     COALESCE(me.nombre, 'Und') AS medida,
                     c.nombre AS categoria_nombre,
                     $catEstSel AS categoria_estacion,
                     $prodEstSel AS producto_estacion
              FROM productos p
              INNER JOIN categoria c ON c.categoria_id = p.categoria_id
              LEFT JOIN medida me ON me.medida_id = p.medida_id
              WHERE p.estado=1 AND p.restaurante=1
              ORDER BY p.nombre ASC";
        $rs=$this->ejecutar_consulta_simple($sql);
        $out=[];
        if (!$rs) return $out;
        while($r=$rs->fetch_assoc()){
            $est = strtolower(trim($r['producto_estacion'] ?? 'ninguna'));
            if(!in_array($est,['ninguna','cocina','barra'],true)) $est='ninguna';
            $catEst = strtolower(trim($r['categoria_estacion'] ?? 'ninguna'));
            if(!in_array($catEst,['ninguna','cocina','barra'],true)) $catEst='ninguna';

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
                'barCode'          => $r['barCode'] ?? '',
                'almacen_id'       => intval($r['almacen_id'] ?? 0),
                'medida_id'        => intval($r['medida_id'] ?? 0),
                'medida'           => $r['medida'] ?? 'Und',
                'estacion'         => $est,
                'categoria_estacion'=> $catEst,
                'para_cocina'      => $est === 'cocina' ? 1 : 0
            ];
        }
        return $out;
    }    

    /** Guardar producto básico para restaurante (con imagen OPCIONAL en la misma petición) */
    public function guardarProductoBasico($data){
        // Lee campos de $data (que ahora viene de $_POST)
        $nombre   = $this->cleanString($data['nombre'] ?? '');
        $desc     = $this->cleanString($data['descripcion'] ?? '');
        $catId    = intval($data['categoria_id'] ?? 0);
        $precio   = floatval($data['precio_venta'] ?? 0);
        $isv1     = intval($data['isv1'] ?? 0) ? 1 : 0;
        $isv2     = intval($data['isv2'] ?? 0) ? 1 : 0;
        $estacion = strtolower(trim($this->cleanString($data['estacion'] ?? '')));
        if (!in_array($estacion, ['cocina','barra'], true)) $estacion = '';

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
        $file_name         = '';                     // se actualizará si viene imagen
        $empresa_id        = $this->empresaId();
        $colaborador_id    = $this->colaboradorId();
        $id_producto_sup   = 0;
        $restaurante       = 1;

        // Insertar
        $ok = $this->ejecutar_consulta_simple_preparada(
            "INSERT INTO productos(
                productos_id, barCode, almacen_id, medida_id, categoria_id, nombre, descripcion,
                tipo_producto_id, precio_compra, porcentaje_venta, precio_venta,
                cantidad_mayoreo, precio_mayoreo, cantidad_minima, cantidad_maxima,
                estado, isv_venta, isv_compra, colaborador_id, file_name, empresa_id,
                fecha_registro, id_producto_superior, restaurante, isv1, isv2
            ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
            "isiiissidddddiiiiiisiiiii",
            [
                $productos_id, $barCode, $almacen_id, $medida_id, $catId, $nombre, $desc,
                $tipo_producto_id, $precio_compra, $porcentaje_venta, $precio,
                $cantidad_mayoreo, $precio_mayoreo, $cantidad_minima, $cantidad_maxima,
                $estado, $isv_venta, $isv_compra, $colaborador_id, $file_name, $empresa_id,
                $id_producto_sup, $restaurante, $isv1, $isv2
            ]
        );

        if(!$ok){
            return ['status'=>false,'message'=>'No se pudo guardar el producto'];
        }

        if ($estacion !== '') {
            if (!$this->hasColumn('productos','estacion')) {
                return ['status'=>false,'message'=>'Falta la columna productos.estacion. Ejecute el SQL de actualización del módulo Restaurante.'];
            }

            $okEstacion = $this->ejecutar_consulta_simple_preparada(
                "UPDATE productos SET estacion=? WHERE productos_id=? AND empresa_id=?",
                "sii", [$estacion,$productos_id,$empresa_id]
            );
            if ($okEstacion === false) {
                return ['status'=>false,'message'=>'El producto se creó, pero no se pudo guardar su estación. Revise productos.estacion.'];
            }
        }

        // ===== Imagen opcional (misma petición) =====
        $imagenGuardada = false; 
        $nombreArchivo = '';
        
        if (!empty($_FILES['imagen_producto']) && is_uploaded_file($_FILES['imagen_producto']['tmp_name'])) {
            $tmp  = $_FILES['imagen_producto']['tmp_name'];
            $orig = $_FILES['imagen_producto']['name'];
            $size = intval($_FILES['imagen_producto']['size'] ?? 0);

            // Validaciones
            if ($size > 0 && $size <= 2*1024*1024) { // 2MB
                $fi   = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($fi, $tmp) ?: '';
                finfo_close($fi);

                // Aceptados
                $ext = '';
                if (strpos($mime,'image/')===0) {
                    $ext = strtolower(substr($mime, 6)); // png, jpeg, webp...
                    if ($ext === 'jpeg') $ext = 'jpg';
                } else {
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                }
                
                if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                    $baseDir = dirname(__DIR__,2).'/vistas/plantilla/img/products';
                    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
                    
                    if (is_dir($baseDir) && is_writable($baseDir)) {
                        $nombreArchivo = 'prod_'.$productos_id.'_'.date('YmdHis').'.'.$ext;
                        $dest = rtrim($baseDir,'/').'/'.$nombreArchivo;
                        
                        if (@move_uploaded_file($tmp, $dest)) {
                            $this->ejecutar_consulta_simple_preparada(
                                "UPDATE productos SET file_name=? WHERE productos_id=?",
                                "si",
                                [$nombreArchivo, $productos_id]
                            );
                            $imagenGuardada = true;
                        }
                    }
                }
            }
        }

        return [
            'status'=>true,
            'producto_id'=>$productos_id,
            'image_saved'=>$imagenGuardada,
            'file_name'=>$nombreArchivo,
            'estacion'=>$estacion
        ];
    }

    /** Actualizar producto básico (con imagen OPCIONAL en la misma petición) */
    public function actualizarProductoBasico($data){
        $productos_id = intval($data['productos_id'] ?? $data['producto_id'] ?? 0);
        $nombre   = $this->cleanString($data['nombre'] ?? '');
        $desc     = $this->cleanString($data['descripcion'] ?? '');
        $catId    = intval($data['categoria_id'] ?? 0);
        $precio   = floatval($data['precio_venta'] ?? 0);
        $isv1     = intval($data['isv1'] ?? 0) ? 1 : 0;
        $isv2     = intval($data['isv2'] ?? 0) ? 1 : 0;
        $estacion = strtolower(trim($this->cleanString($data['estacion'] ?? '')));
        if (!in_array($estacion, ['cocina','barra'], true)) $estacion = '';

        if($productos_id<=0){ return ['status'=>false,'message'=>'ID de producto inválido']; }
        if($nombre==='' || $catId<=0){ return ['status'=>false,'message'=>'Nombre y categoría son obligatorios']; }

        $isv_venta      = ($isv1||$isv2) ? 1 : 2;
        $empresa_id     = $this->empresaId();
        $colaborador_id = $this->colaboradorId();

        // ===== UPDATE de campos básicos + estación en una sola operación
        $cnn  = $this->connection();
        $tieneEstacionProducto = $this->hasColumn('productos','estacion');

        if ($estacion !== '' && !$tieneEstacionProducto) {
            return ['status'=>false,'message'=>'Falta la columna productos.estacion. Ejecute el SQL de actualización del módulo Restaurante.'];
        }

        if ($tieneEstacionProducto) {
            if ($estacion === '') {
                $estacion = 'cocina';
            }
            $sqlU = "UPDATE productos
                     SET categoria_id=?, nombre=?, descripcion=?, precio_venta=?,
                         isv_venta=?, isv1=?, isv2=?, colaborador_id=?, empresa_id=?, estacion=?
                     WHERE productos_id=? AND empresa_id=?";
            $stmt = $cnn->prepare($sqlU);
            if(!$stmt){
                return ['status'=>false,'message'=>'No se pudo preparar la actualización del producto'];
            }
            $stmt->bind_param(
                "issdiiiiisii",
                $catId, $nombre, $desc, $precio,
                $isv_venta, $isv1, $isv2, $colaborador_id, $empresa_id, $estacion,
                $productos_id, $empresa_id
            );
        } else {
            $sqlU = "UPDATE productos
                     SET categoria_id=?, nombre=?, descripcion=?, precio_venta=?,
                         isv_venta=?, isv1=?, isv2=?, colaborador_id=?, empresa_id=?
                     WHERE productos_id=? AND empresa_id=?";
            $stmt = $cnn->prepare($sqlU);
            if(!$stmt){
                return ['status'=>false,'message'=>'No se pudo preparar la actualización del producto'];
            }
            $stmt->bind_param(
                "issdiiiiiii",
                $catId, $nombre, $desc, $precio,
                $isv_venta, $isv1, $isv2, $colaborador_id, $empresa_id,
                $productos_id, $empresa_id
            );
        }

        $execOk = $stmt->execute();
        if(!$execOk){
            $msg = $stmt->error ?: 'No se pudo actualizar el producto';
            $stmt->close();
            return ['status'=>false,'message'=>$msg];
        }
        // No importa si affected_rows==0 (mismos valores); la ejecución fue correcta
        $stmt->close();

        // ===== Imagen opcional (misma petición): si llega nueva, guarda y borra la anterior
        $imagenGuardada = false; 
        $nombreArchivo  = '';
        
        if (!empty($_FILES['imagen_producto']) && is_uploaded_file($_FILES['imagen_producto']['tmp_name'])) {
            // obtener nombre anterior de forma segura
            $oldStmt = $cnn->prepare("SELECT file_name FROM productos WHERE productos_id=?");
            $oldStmt->bind_param("i", $productos_id);
            $oldStmt->execute();
            $res = $oldStmt->get_result();
            $oldName = ($res && $res->num_rows) ? ($res->fetch_assoc()['file_name'] ?? '') : '';
            $oldStmt->close();

            $tmp  = $_FILES['imagen_producto']['tmp_name'];
            $orig = $_FILES['imagen_producto']['name'];
            $size = intval($_FILES['imagen_producto']['size'] ?? 0);

            if ($size > 0 && $size <= 2*1024*1024) { // 2MB
                $fi   = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($fi, $tmp) ?: '';
                finfo_close($fi);

                // bloquear extensiones peligrosas
                if (preg_match('/\.(php|phtml|phar)$/i', (string)$orig)) {
                    return ['status'=>false,'message'=>'Extensión de archivo no permitida'];
                }

                $ext = '';
                if (strpos($mime,'image/')===0) {
                    $ext = strtolower(substr($mime, 6)); // png, jpeg, webp...
                    if ($ext === 'jpeg') $ext = 'jpg';
                } else {
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                }

                if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                    $baseDir = dirname(__DIR__,2).'/vistas/plantilla/img/products';
                    if (!is_dir($baseDir)) @mkdir($baseDir, 0775, true);
                    
                    if (is_dir($baseDir) && is_writable($baseDir)) {
                        $nombreArchivo = 'prod_'.$productos_id.'_'.date('YmdHis').'.'.$ext;
                        $dest = rtrim($baseDir,'/').'/'.$nombreArchivo;
                        
                        if (@move_uploaded_file($tmp, $dest)) {
                            @chmod($dest, 0644);

                            // borra anterior si existe
                            if ($oldName) {
                                $oldPath = rtrim($baseDir,'/').'/'.$oldName;
                                if (is_file($oldPath)) @unlink($oldPath);
                            }
                            
                            // actualizar file_name solo cuando realmente hay nueva imagen
                            $upImg = $cnn->prepare("UPDATE productos SET file_name=? WHERE productos_id=?");
                            $upImg->bind_param("si", $nombreArchivo, $productos_id);
                            $upImg->execute();
                            $upImg->close();

                            $imagenGuardada = true;
                        }
                    }
                }
            }
        }

        return [
            'status'=>true,
            'producto_id'=>$productos_id,
            'image_saved'=>$imagenGuardada,
            'file_name'=>$nombreArchivo
        ];
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
    
        // Campos corporativos
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
            "isssiisssiissss",
            [
                $clientes_id, $nombre, $rtn, $fecha, $dept, $mun, $localidad,
                $telefono, $correo, $estado, $this->colaboradorId(),
                $empresaTxt, $eslogan, $otraInfo, $whatsapp
            ]
        );
    
        return $ok ? ['status'=>true,'cliente'=>['clientes_id'=>$clientes_id,'nombre'=>$nombre,'rtn'=>$rtn]]
                   : ['status'=>false,'message'=>'No se pudo guardar el cliente'];
    }

    /** Actualizar cliente básico */
    public function actualizarClienteBasico($data){
        $clientes_id = intval($data['clientes_id'] ?? 0);
        $nombre  = $this->cleanString($data['nombre'] ?? '');
        if($clientes_id<=0 || $nombre===''){ return ['status'=>false,'message'=>'Datos inválidos']; }

        $rtn       = $this->cleanString($data['rtn'] ?? '');
        $fecha     = $this->cleanString($data['fecha'] ?? date('Y-m-d'));
        $dept      = intval($data['departamentos_id'] ?? 0);
        $mun       = intval($data['municipios_id'] ?? 0);
        $localidad = $this->cleanString($data['localidad'] ?? '');
        $telefono  = $this->cleanString($data['telefono'] ?? '');
        $correo    = $this->cleanString($data['correo'] ?? '');
        $estado    = intval($data['estado'] ?? 1);
        $empresaTxt= $this->cleanString($data['empresa'] ?? '');
        $eslogan   = $this->cleanString($data['eslogan'] ?? '');
        $otraInfo  = $this->cleanString($data['otra_informacion'] ?? '');
        $whatsapp  = $this->cleanString($data['whatsapp'] ?? '');

        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE clientes SET
                nombre=?, rtn=?, fecha=?, departamentos_id=?, municipios_id=?, localidad=?,
                telefono=?, correo=?, estado=?, colaboradores_id=?, empresa=?, eslogan=?, otra_informacion=?, whatsapp=?
             WHERE clientes_id=?",
            "sssiisssiissssi",
            [
                $nombre, $rtn, $fecha, $dept, $mun, $localidad,
                $telefono, $correo, $estado, $this->colaboradorId(),
                $empresaTxt, $eslogan, $otraInfo, $whatsapp,
                $clientes_id
            ]
        );
        return $ok ? ['status'=>true,'message'=>'Cliente actualizado'] : ['status'=>false,'message'=>'No se pudo actualizar el cliente'];
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
        $mesa_id=(int)$mesa_id;
        if($mesa_id<=0) return null;

        $empresa=$this->empresaId();

        // Una mesa solo puede recuperar una cuenta operativa del DÍA ACTUAL.
        // Los borradores/contextos viejos se conservan para trazabilidad, pero jamás
        // deben reaparecer automáticamente al seleccionar hoy una mesa disponible.
        $sql="SELECT
                f.*,
                rc.mesa_id,
                m.numero AS numero_mesa, m.capacidad AS capacidad_mesa, m.ubicacion AS ubicacion_mesa,
                c.clientes_id AS cliente_id, c.nombre AS cliente_nombre, c.rtn AS cliente_identificacion
              FROM facturas f
              INNER JOIN factura_restaurante_cuentas rc
                ON rc.factura_id=f.facturas_id
               AND rc.empresa_id=f.empresa_id
               AND rc.estado='abierta'
              INNER JOIN mesas m
                ON m.mesa_id=rc.mesa_id
               AND m.empresa_id=rc.empresa_id
              INNER JOIN clientes c ON c.clientes_id=f.clientes_id
              WHERE rc.mesa_id=?
                AND rc.empresa_id=?
                AND rc.servicio_tipo='mesa'
                AND f.estado=1
                AND DATE(COALESCE(rc.fecha_actualizacion,rc.fecha_registro))=CURDATE()
              ORDER BY COALESCE(rc.fecha_actualizacion,rc.fecha_registro) DESC, f.facturas_id DESC
              LIMIT 1";
        $rs=$this->ejecutar_consulta_simple_preparada($sql,"ii",[$mesa_id,$empresa]);
        return $rs && $rs->num_rows ? $rs->fetch_assoc() : null;
    }

    public function obtenerDetallesFactura($factura_id){
        $sql="SELECT fd.facturas_detalle_id, fd.productos_id, fd.cantidad, fd.precio, fd.isv_valor, fd.descuento, fd.medida,
                     p.nombre AS nombre_producto, p.descripcion AS descripcion_producto, p.isv1, p.isv2
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
                'medida'             =>$r['medida'],
                'isv1'               =>intval($r['isv1']),
                'isv2'               =>intval($r['isv2'])
            ];
        }
        return $out;
    }

    /* ===== Guardar / Actualizar factura ===== */

    protected function calcularTotalesDesdeItems(array $items){
        if(empty($items)) return ['subtotal'=>0,'imp1'=>0,'imp2'=>0,'total'=>0];

        // aceptar producto_id o productos_id
        $ids = [];
        foreach ($items as $i) {
            $ids[] = intval($i['producto_id'] ?? $i['productos_id'] ?? 0);
        }
        $ids = array_values(array_filter($ids));
        if (empty($ids)) return ['subtotal'=>0,'imp1'=>0,'imp2'=>0,'total'=>0];

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
            $pid=intval($it['producto_id'] ?? $it['productos_id'] ?? 0);
            $qty=floatval($it['cantidad'] ?? 0);
            $pu=floatval($it['precio'] ?? 0);
            if ($pid<=0 || $qty<=0) continue;
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

    /** Crea una factura como borrador o pagada (con comanda mesa/para llevar) */
    public function guardarFactura($data, $mesaId = 0, $servicioTipo = 'llevar'){
        $cnn = $this->connection();
        $numeroReservado = null;

        // Normaliza mesa/servicio por si vienen en $data
        $mesaId = intval($mesaId ?: ($data['mesa_id'] ?? 0));
        $servicioTipo = strtolower(trim((string)$servicioTipo));
        if (!in_array($servicioTipo, ['mesa','llevar'], true)) {
            $servicioTipo = ((($data['servicio_tipo'] ?? '') === 'mesa') ? 'mesa' : 'llevar');
        }

        try {
            $cliente_id= intval($data['cliente_id'] ?? 0);
            $items     = is_array($data['items'] ?? null) ? $data['items'] : [];
            $metodo    = trim((string)($data['metodo_pago'] ?? ''));
            $notas     = $this->cleanString($data['observaciones'] ?? '');

            if (empty($items)) return ['status'=>false,'message'=>'No hay items'];

            $tot = $this->calcularTotalesDesdeItems($items);

            // Reservar número
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
            $ok = $this->ejecutar_consulta_simple_preparada($q,"iiiiiiidssiiis",
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
            $ids = [];
            foreach ($items as $i) { $ids[] = intval($i['producto_id'] ?? $i['productos_id'] ?? 0); }
            $ids = array_values(array_filter($ids));
            $flags=[];
            if (!empty($ids)) {
                $place = implode(',', array_fill(0,count($ids),'?'));
                $types = str_repeat('i', count($ids));
                $rs = $this->ejecutar_consulta_simple_preparada("SELECT productos_id,isv1,isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids);
                while($r=$rs->fetch_assoc()){
                    $flags[intval($r['productos_id'])]=['isv1'=>intval($r['isv1'])==1,'isv2'=>intval($r['isv2'])==1];
                }
            }
            foreach($items as $it){
                $pid=intval($it['producto_id'] ?? $it['productos_id'] ?? 0);
                $qty=max(1,intval($it['cantidad'] ?? 0));
                $pu=floatval($it['precio'] ?? 0);
                if ($pid<=0 || $qty<=0) continue;
                $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $taxUnit = ($f['isv1']?($pu*$r1):0)+($f['isv2']?($pu*$r2):0);
                $isv_line = $taxUnit*$qty;
                $det_id = mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles(facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                    VALUES(?, ?, ?, ?, ?, ?, 0, 'UNIDAD')",
                    "iiiidd", [$det_id,$factura_id,$pid,$qty,$pu,$isv_line]
                );
            }

            // ===================== COMANDA PARA COCINA/BARRA =====================
            // Creamos/actualizamos la comanda si hay mesa O si es "para llevar".
            // (para llevar se registra con mesa_id = 0; la vista de cocina lo sabe por servicio_tipo)
            if ($mesaId > 0 || $servicioTipo === 'llevar') {

                // ¿ya existe comanda ligada a esta factura?
                $existe = $this->ejecutar_consulta_simple_preparada(
                    "SELECT id FROM factura_comanda WHERE factura_id=?",
                    "i",
                    [$factura_id]
                );

                if ($existe && $existe->num_rows) {
                    // UPDATE incluye servicio_tipo
                    $this->ejecutar_consulta_simple_preparada(
                        "UPDATE factura_comanda 
                        SET mesa_id=?, comentarios_cocina=?, servicio_tipo=? 
                        WHERE factura_id=?",
                        "issi",
                        [$mesaId, $notas, $servicioTipo, $factura_id]
                    );
                } else {
                    // INSERT con servicio_tipo
                    $cid = mainModel::correlativo("id","factura_comanda");
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO factura_comanda 
                            (id, factura_id, mesa_id, comentarios_cocina, estado, servicio_tipo, fecha_registro) 
                        VALUES (?, ?, NULLIF(?,0), ?, 'pendiente', ?, NOW())",
                        "iiiss",
                        [$cid, $factura_id, $mesaId, $notas, $servicioTipo]
                    );
                }

                // Si es en mesa, marcamos la mesa como ocupada
                if ($mesaId > 0) {
                    $this->ejecutar_consulta_simple_preparada(
                        "UPDATE mesas SET estado='ocupada' WHERE mesa_id=?",
                        "i",
                        [$mesaId]
                    );
                }
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

    /** Actualiza items y puede registrar pago (mantiene comanda mesa/para llevar) */
    public function actualizarFactura($data, $mesaId = 0, $servicioTipo = 'llevar'){
        $cnn = $this->connection();

        // Normaliza mesa/servicio por si vienen en $data
        $mesaId = intval($mesaId ?: ($data['mesa_id'] ?? 0));
        $servicioTipo = strtolower(trim((string)$servicioTipo));
        if (!in_array($servicioTipo, ['mesa','llevar'], true)) {
            $servicioTipo = ((($data['servicio_tipo'] ?? '') === 'mesa') ? 'mesa' : 'llevar');
        }

        try{
            $factura_id = intval($data['factura_id']);
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

            // ===================== COMANDA PARA COCINA/BARRA =====================
            if ($mesaId > 0 || $servicioTipo === 'llevar') {

                $existe = $this->ejecutar_consulta_simple_preparada(
                    "SELECT id FROM factura_comanda WHERE factura_id=?",
                    "i",
                    [$factura_id]
                );

                if ($existe && $existe->num_rows) {
                    $this->ejecutar_consulta_simple_preparada(
                        "UPDATE factura_comanda 
                        SET mesa_id=?, comentarios_cocina=?, servicio_tipo=? 
                        WHERE factura_id=?",
                        "issi",
                        [$mesaId, $notas, $servicioTipo, $factura_id]
                    );
                } else {
                    $cid = mainModel::correlativo("id","factura_comanda");
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO factura_comanda 
                            (id, factura_id, mesa_id, comentarios_cocina, estado, servicio_tipo, fecha_registro) 
                        VALUES (?, ?, NULLIF(?,0), ?, 'pendiente', ?, NOW())",
                        "iiiss",
                        [$cid, $factura_id, $mesaId, $notas, $servicioTipo]
                    );
                }

                if ($mesaId > 0) {
                    $this->ejecutar_consulta_simple_preparada(
                        "UPDATE mesas SET estado='ocupada' WHERE mesa_id=?",
                        "i",
                        [$mesaId]
                    );
                }
            }

            // Reemplazar detalles
            $this->ejecutar_consulta_simple_preparada("DELETE FROM facturas_detalles WHERE facturas_id=?","i",[$factura_id]);

            $isv=$this->getISVActivosMap(); $r1=($isv[1]??0)/100.0; $r2=($isv[2]??0)/100.0;
            $ids = [];
            foreach ($items as $i) { $ids[] = intval($i['producto_id'] ?? $i['productos_id'] ?? 0); }
            $ids = array_values(array_filter($ids));
            $flags=[];
            if (!empty($ids)) {
                $place = implode(',', array_fill(0,count($ids),'?')); $types = str_repeat('i', count($ids));
                $rs2 = $this->ejecutar_consulta_simple_preparada("SELECT productos_id,isv1,isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids);
                while($r=$rs2->fetch_assoc()){ $flags[intval($r['productos_id'])]=['isv1'=>intval($r['isv1'])==1,'isv2'=>intval($r['isv2'])==1]; }
            }

            foreach($items as $it){
                $pid=intval($it['producto_id'] ?? $it['productos_id'] ?? 0);
                $qty=max(1,intval($it['cantidad'] ?? 0));
                $pu=floatval($it['precio'] ?? 0);
                if ($pid<=0 || $qty<=0) continue;
                $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $taxUnit = ($f['isv1']?($pu*$r1):0)+($f['isv2']?($pu*$r2):0);
                $isv_line = $taxUnit*$qty;
                $det_id = mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles(facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                    VALUES(?, ?, ?, ?, ?, ?, 0, 'UNIDAD')",
                    "iiiidd", [$det_id,$factura_id,$pid,$qty,$pu,$isv_line]
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

            // liberar mesa si la tenía (la cuenta es la fuente operativa; comanda es solo preparación)
            $rm = $this->ejecutar_consulta_simple_preparada(
                "SELECT mesa_id FROM factura_restaurante_cuentas WHERE factura_id=? AND empresa_id=? LIMIT 1",
                "ii",[$factura_id,$this->empresaId()]
            );
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

            // Si se cancela antes del pago, retirar cualquier orden pendiente
            // de la pantalla de preparación.
            //
            // IMPORTANTE:
            // factura_comanda es una tabla histórica que en instalaciones existentes
            // puede tener estado como ENUM con los valores ya usados por el sistema
            // (pendiente, en_preparacion, urgente, preparada). No debemos escribir
            // "cancelada" porque MySQL puede lanzar:
            // Data truncated for column 'estado'.
            //
            // "preparada" es el estado terminal ya soportado por esa estructura y,
            // junto con factura.estado=4 + el contexto cerrado, evita que la orden
            // vuelva a aparecer como pendiente sin alterar el esquema existente.
            if($nuevo===4){
                $this->ejecutar_consulta_simple_preparada(
                    "UPDATE factura_comanda
                     SET estado='preparada'
                     WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')",
                    "i",[$factura_id]
                );

                $this->ejecutar_consulta_simple_preparada(
                    "UPDATE factura_comanda_items
                     SET estado='preparada', fecha_actualizacion=NOW()
                     WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')",
                    "i",[$factura_id]
                );
            }

            $contextoCerrado = $this->cerrarContextoCuenta($factura_id);
            if(!$contextoCerrado){
                return [
                    'status'=>false,
                    'message'=>'La factura fue actualizada, pero no se pudo cerrar el contexto operativo de la cuenta.'
                ];
            }

            return [
                'status'=>true,
                'message'=>($nuevo===4 ? 'Cuenta cerrada y cancelada correctamente' : 'Cuenta cerrada correctamente')
            ];
        }catch(Throwable $e){
            return ['status'=>false,'message'=>'Error: '.$e->getMessage()];
        }
    }

    /* ===== Cocina ===== */

    /** Listado para la pantalla de cocina */
    public function obtenerComandasCocina($estado=null){
        // estados visibles por defecto (dejamos fuera 'completada')
        $permitidos = ['pendiente','en_preparacion','preparada','urgente','completada'];
        $filtroEstado = null;
        $params = [$this->empresaId()];
        $types  = "i";

        $sql = "SELECT 
                    fc.id            AS comanda_id,
                    fc.factura_id,
                    fc.mesa_id,
                    fc.estado,
                    fc.comentarios_cocina,
                    fc.fecha_registro,
                    f.notas,
                    c.nombre         AS cliente_nombre,
                    m.numero         AS mesa_numero
                FROM factura_comanda fc
                INNER JOIN facturas f   ON f.facturas_id = fc.factura_id
                LEFT  JOIN clientes c   ON c.clientes_id = f.clientes_id
                LEFT  JOIN mesas m      ON m.mesa_id     = fc.mesa_id
                WHERE f.empresa_id = ? ";

        // por defecto ocultamos completadas; si piden explicitamente, se muestran
        if ($estado !== null) {
            $estado = strtolower(trim((string)$estado));
            if (in_array($estado, $permitidos, true)) {
                $sql   .= " AND fc.estado = ? ";
                $types .= "s";
                $params[] = $estado;
            }
        } else {
            $sql .= " AND fc.estado <> 'completada' ";
        }

        // ordenar: urgentes primero, luego pendientes, luego en preparación, luego preparadas; por fecha asc
        $sql .= " ORDER BY 
                    CASE fc.estado 
                        WHEN 'urgente'        THEN 0
                        WHEN 'pendiente'      THEN 1
                        WHEN 'en_preparacion' THEN 2
                        WHEN 'preparada'      THEN 3
                        ELSE 4
                    END,
                    fc.fecha_registro ASC";

        $rs = $this->ejecutar_consulta_simple_preparada($sql, $types, $params);

        $rows = [];
        $facturas = [];
        while($r = $rs->fetch_assoc()){
            $rows[] = $r;
            $facturas[] = intval($r['factura_id']);
        }

        // mapear items de cada factura de una sola vez
        $itemsMap = [];
        if (!empty($facturas)) {
            $facturas = array_values(array_unique($facturas));
            $place = implode(',', array_fill(0, count($facturas), '?'));
            $types = str_repeat('i', count($facturas));
            $qr = "SELECT fd.facturas_id, fd.cantidad, p.nombre
                   FROM facturas_detalles fd
                   INNER JOIN productos p ON p.productos_id = fd.productos_id
                   WHERE fd.facturas_id IN ($place)
                   ORDER BY fd.facturas_id, p.nombre";
            $rd = $this->ejecutar_consulta_simple_preparada($qr, $types, $facturas);
            while($d = $rd->fetch_assoc()){
                $fid = intval($d['facturas_id']);
                if (!isset($itemsMap[$fid])) $itemsMap[$fid] = [];
                $itemsMap[$fid][] = [
                    'nombre'   => $d['nombre'],
                    'cantidad' => floatval($d['cantidad'])
                ];
            }
        }

        // armar salida para el front
        $out = [];
        foreach($rows as $r){
            $fid  = intval($r['factura_id']);
            $hora = $r['fecha_registro'] ? date('H:i', strtotime($r['fecha_registro'])) : '';
            $est  = (string)$r['estado'];
            $out[] = [
                // MUY IMPORTANTE: el front usa este id como factura_id en los POST
                'id'                 => $fid,
                'mesa'               => $r['mesa_numero'] ?? null,
                'cliente_nombre'     => $r['cliente_nombre'] ?? 'Sin nombre',
                'hora'               => $hora,
                'estado'             => $est,
                'urgente'            => ($est === 'urgente') ? 1 : 0,
                'observaciones'      => $r['notas'] ?? '',
                'comentarios_cocina' => $r['comentarios_cocina'] ?? '',
                'items'              => $itemsMap[$fid] ?? []
            ];
        }

        return $out;
    }

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
        $factura_id=intval($factura_id);
        $ok1 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado='preparada' WHERE factura_id=?","i",[$factura_id]);
        $ok2 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda_items SET estado='preparada' WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')","i",[$factura_id]);
        return ($ok1 && $ok2) ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }
    public function marcarComandaUrgente($factura_id,$urgente){
        $factura_id=intval($factura_id);
        $estado = $urgente ? 'urgente' : 'pendiente';
        $ok1 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado=? WHERE factura_id=?","si",[$estado,$factura_id]);
        $ok2 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda_items SET estado=? WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')","si",[$estado,$factura_id]);
        return ($ok1 && $ok2) ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }
    public function marcarComandaEnPreparacion($factura_id){
        $factura_id=intval($factura_id);
        $ok1 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda SET estado='en_preparacion' WHERE factura_id=?","i",[$factura_id]);
        $ok2 = $this->ejecutar_consulta_simple_preparada("UPDATE factura_comanda_items SET estado='en_preparacion' WHERE factura_id=? AND estado IN ('pendiente','urgente')","i",[$factura_id]);
        return ($ok1 && $ok2) ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar'];
    }

    /* SOLO items cuya estación operativa del PRODUCTO coincide con $estacion.
       categoria.estacion se conserva únicamente como respaldo para datos antiguos. */
    public function getComandasPorEstacion(string $estacion = 'cocina'): array {
        $estacion = strtolower(trim($estacion));
        if(!in_array($estacion,['cocina','barra'],true)) $estacion='cocina';

        $sql = "SELECT
                    fc.id AS comanda_id,
                    fc.factura_id,
                    fc.mesa_id,
                    fci.estado AS estado,
                    fc.fecha_registro,
                    fc.comentarios_cocina,
                    f.notas,
                    COALESCE(cli.nombre,'Consumidor Final') cliente_nombre,
                    COALESCE(m.numero,'') mesa_numero,
                    fci.productos_id,
                    SUM(fci.cantidad) cantidad,
                    p.nombre producto_nombre
                FROM factura_comanda fc
                INNER JOIN facturas f ON f.facturas_id=fc.factura_id
                LEFT JOIN clientes cli ON cli.clientes_id=f.clientes_id
                LEFT JOIN mesas m ON m.mesa_id=fc.mesa_id
                INNER JOIN factura_comanda_items fci ON fci.factura_id=fc.factura_id
                INNER JOIN productos p ON p.productos_id=fci.productos_id
                WHERE f.empresa_id=?
                  AND fci.estacion=?
                  AND fci.estado IN ('pendiente','en_preparacion','urgente')
                GROUP BY fc.id,fc.factura_id,fc.mesa_id,fci.estado,fc.fecha_registro,
                         fc.comentarios_cocina,f.notas,cli.nombre,m.numero,
                         fci.productos_id,p.nombre
                ORDER BY fc.fecha_registro ASC, fc.id ASC, p.nombre ASC";

        $rs=$this->ejecutar_consulta_simple_preparada($sql,"is",[$this->empresaId(),$estacion]);
        $map=[];
        if($rs) while($r=$rs->fetch_assoc()){
            $fid=(int)$r['factura_id'];
            if(!isset($map[$fid])){
                $map[$fid]=[
                    'id'=>$fid,
                    'factura_id'=>$fid,
                    'comanda_id'=>(int)$r['comanda_id'],
                    'mesa'=>$r['mesa_numero']!==''?$r['mesa_numero']:null,
                    'cliente_nombre'=>$r['cliente_nombre']??'Consumidor Final',
                    'hora'=>$r['fecha_registro']?date('H:i',strtotime($r['fecha_registro'])):'',
                    'estado'=>$r['estado']??'pendiente',
                    'urgente'=>($r['estado']==='urgente')?1:0,
                    'observaciones'=>$r['notas']??'',
                    'comentarios_cocina'=>$r['comentarios_cocina']??'',
                    'items'=>[]
                ];
            }
            $map[$fid]['items'][]=[
                'nombre'=>$r['producto_nombre'],
                'cantidad'=>(float)$r['cantidad']
            ];
        }
        return array_values($map);
    }

    /* Update por FACTURA (para: en_preparacion, urgente/pendiente) */
    public function updateComandaEstadoByFactura(int $factura_id, string $nuevoEstado): bool {
        $factura_id   = max(0, $factura_id);
        $nuevoEstado  = $this->cleanString($nuevoEstado);
        if ($factura_id <= 0) return false;

        $ok1 = (bool)$this->ejecutar_consulta_simple_preparada(
            "UPDATE factura_comanda
             SET estado=?, fecha_actualizacion=NOW()
             WHERE factura_id=?",
            "si", [$nuevoEstado,$factura_id]
        );

        // La pantalla consume factura_comanda_items. Mantener ambas capas sincronizadas.
        $ok2 = (bool)$this->ejecutar_consulta_simple_preparada(
            "UPDATE factura_comanda_items
             SET estado=?
             WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')",
            "si", [$nuevoEstado,$factura_id]
        );
        return $ok1 && $ok2;
    }

    /* Update por COMANDA (para: preparada) */
    public function updateComandaEstadoById(int $comanda_id, string $nuevoEstado): bool {
        $comanda_id   = max(0, $comanda_id);
        $nuevoEstado  = $this->cleanString($nuevoEstado);
        if ($comanda_id <= 0) return false;

        $rf=$this->ejecutar_consulta_simple_preparada(
            "SELECT factura_id FROM factura_comanda WHERE id=? LIMIT 1",
            "i",[$comanda_id]
        );
        if(!$rf || !$rf->num_rows) return false;
        $factura_id=(int)$rf->fetch_assoc()['factura_id'];

        $ok1=(bool)$this->ejecutar_consulta_simple_preparada(
            "UPDATE factura_comanda
             SET estado=?, fecha_actualizacion=NOW()
             WHERE id=?",
            "si",[$nuevoEstado,$comanda_id]
        );
        $ok2=(bool)$this->ejecutar_consulta_simple_preparada(
            "UPDATE factura_comanda_items
             SET estado=?
             WHERE factura_id=? AND estado IN ('pendiente','en_preparacion','urgente')",
            "si",[$nuevoEstado,$factura_id]
        );
        return $ok1 && $ok2;
    }

    /* ============================================================
     * ===================== COMBOS ===============================
     * ============================================================ */

    /** Lista de combos por empresa (lee del producto maestro) */
    public function obtenerCombos(){
        $sql = "SELECT c.combo_id, c.productos_id, c.activo,
                       COALESCE(c.precio_venta, p.precio_venta) AS combo_precio,
                       p.nombre AS combo_nombre
                FROM combos c
                INNER JOIN productos p ON p.productos_id = c.productos_id
                WHERE p.empresa_id = ?
                ORDER BY c.combo_id DESC";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$this->empresaId()]);
        $out = [];
        while($r = $rs->fetch_assoc()){
            $rsd = $this->ejecutar_consulta_simple_preparada(
                "SELECT COUNT(*) AS total FROM combo_detalle WHERE combo_id=?","i",[intval($r['combo_id'])]
            );
            $det = $rsd && $rsd->num_rows ? intval($rsd->fetch_assoc()['total']) : 0;

            $out[] = [
                'combo_id'      => intval($r['combo_id']),
                'productos_id'  => intval($r['productos_id']),
                'combo_nombre'  => $r['combo_nombre'],
                'combo_precio'  => floatval($r['combo_precio']),
                'activo'        => intval($r['activo']) ? 1 : 0,
                'items_count'   => $det
            ];
        }
        return $out;
    }

    /** Reglas por categoría (max_seleccion) de un combo */
    public function obtenerComboCategoriaReglas($combo_id){
        $combo_id = intval($combo_id);
        $sql = "SELECT r.combo_categoria_regla_id, r.categoria_id, r.max_seleccion,
                       c.nombre AS categoria_nombre
                FROM combo_categoria_regla r
                LEFT JOIN categoria c ON c.categoria_id = r.categoria_id
                WHERE r.combo_id = ?
                ORDER BY c.nombre ASC";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$combo_id]);
        $out = [];
        while($r = $rs->fetch_assoc()){
            $out[] = [
                'combo_categoria_regla_id' => intval($r['combo_categoria_regla_id']),
                'categoria_id'             => intval($r['categoria_id']),
                'categoria_nombre'         => $r['categoria_nombre'] ?? '',
                'max_seleccion'            => intval($r['max_seleccion']),
            ];
        }
        return $out;
    }

    /** Detalle de un combo (componentes) – nueva estructura */
    public function obtenerComboDetalle($combo_id){
        $combo_id = intval($combo_id);

        // Cabecera (con precio_venta del combo si lo definiste en combos)
        $cab = $this->ejecutar_consulta_simple_preparada(
            "SELECT c.combo_id, c.productos_id, c.activo,
                    COALESCE(c.precio_venta, p.precio_venta) AS combo_precio,
                    p.nombre AS combo_nombre
             FROM combos c
             INNER JOIN productos p ON p.productos_id = c.productos_id
             WHERE c.combo_id=?","i",[$combo_id]
        );
        if(!$cab || !$cab->num_rows) return null;
        $head = $cab->fetch_assoc();

        // Detalle (receta)
        $sql="SELECT d.combo_detalle_id, d.productos_id,
                     pr.nombre, pr.precio_venta,
                     d.cantidad_por_porcion, d.unidad, d.merma_pct, d.obligatorio,
                     d.precio_extra, d.version, d.orden
              FROM combo_detalle d
              INNER JOIN productos pr ON pr.productos_id = d.productos_id
              WHERE d.combo_id = ?
              ORDER BY d.obligatorio DESC, d.orden ASC, pr.nombre ASC";
        $rs = $this->ejecutar_consulta_simple_preparada($sql,"i",[$combo_id]);

        $det=[];
        while($r=$rs->fetch_assoc()){
            $det[]=[
                'combo_detalle_id'    => intval($r['combo_detalle_id']),
                'productos_id'        => intval($r['productos_id']),
                'nombre'              => $r['nombre'],
                'precio_venta'        => floatval($r['precio_venta']),
                'cantidad_por_porcion'=> floatval($r['cantidad_por_porcion']),
                'unidad'              => $r['unidad'],
                'merma_pct'           => floatval($r['merma_pct']),
                'obligatorio'         => intval($r['obligatorio']) ? 1 : 0,
                'precio_extra'        => floatval($r['precio_extra']),
                'version'             => intval($r['version']),
                'orden'               => intval($r['orden'])
            ];
        }

        // Puedes devolver también cabecera si la necesitas
        // return ['cabecera'=>$head, 'detalle'=>$det];
        return $det; // como ya usabas
    }

    /** Valida receta y reglas antes de guardar/editar un combo. */
    protected function validarComboPayload(int $productoPadre, array $items, array $reglas): array {
        if($productoPadre<=0) return ['status'=>false,'message'=>'Producto combo inválido'];
        if(!$items) return ['status'=>false,'message'=>'El combo debe tener al menos un componente'];

        $vistos=[]; $opcionalesPorCategoria=[];
        foreach($items as $it){
            $pid=(int)($it['productos_id']??0);
            $cant=(float)($it['cantidad_por_porcion']??$it['cantidad']??0);
            $merma=(float)($it['merma_pct']??0);
            $extra=(float)($it['precio_extra']??0);
            $obligatorio=((int)($it['obligatorio']??1)===1);

            if($pid<=0) return ['status'=>false,'message'=>'Hay un componente sin producto'];
            if($pid===$productoPadre) return ['status'=>false,'message'=>'El producto maestro no puede ser componente de sí mismo'];
            if(isset($vistos[$pid])) return ['status'=>false,'message'=>'No se permiten componentes repetidos'];
            if($cant<=0) return ['status'=>false,'message'=>'La cantidad de cada componente debe ser mayor a 0'];
            if($merma<0 || $merma>100) return ['status'=>false,'message'=>'La merma debe estar entre 0 y 100%'];
            if($extra<0) return ['status'=>false,'message'=>'El precio extra no puede ser negativo'];

            $rp=$this->ejecutar_consulta_simple_preparada(
                "SELECT categoria_id FROM productos WHERE productos_id=? AND empresa_id=? AND estado=1 LIMIT 1",
                "ii",[$pid,$this->empresaId()]
            );
            if(!$rp || !$rp->num_rows) return ['status'=>false,'message'=>'Un componente no existe, está inactivo o pertenece a otra empresa'];
            $cat=(int)$rp->fetch_assoc()['categoria_id'];
            if(!$obligatorio) $opcionalesPorCategoria[$cat]=($opcionalesPorCategoria[$cat]??0)+1;
            $vistos[$pid]=1;
        }

        $cats=[];
        foreach($reglas as $rg){
            $cat=(int)($rg['categoria_id']??0);
            $max=max(1,(int)($rg['max_seleccion']??1));
            if($cat<=0) return ['status'=>false,'message'=>'Hay una regla sin categoría'];
            if(isset($cats[$cat])) return ['status'=>false,'message'=>'No se puede repetir una categoría en las reglas'];
            $rc=$this->ejecutar_consulta_simple_preparada("SELECT categoria_id FROM categoria WHERE categoria_id=? AND estado=1 LIMIT 1","i",[$cat]);
            if(!$rc || !$rc->num_rows) return ['status'=>false,'message'=>'Una categoría de regla no existe o está inactiva'];
            $disponibles=(int)($opcionalesPorCategoria[$cat]??0);
            if($disponibles<=0) return ['status'=>false,'message'=>'Una regla no tiene componentes opcionales de esa categoría'];
            if($max>$disponibles) return ['status'=>false,'message'=>'Una regla permite más selecciones que opciones disponibles'];
            $cats[$cat]=1;
        }
        return ['status'=>true];
    }

    /** Crear combo + receta + reglas por categoría (transaccional) */
    public function guardarCombo($payload){
        $prod_combo   = intval($payload['productos_id'] ?? 0);
        $activo       = intval($payload['activo'] ?? 1) ? 1 : 0;
        $precio_combo = array_key_exists('precio_venta', $payload) ? $payload['precio_venta'] : '__omit__'; // null o decimal o __omit__
        $version      = intval($payload['version'] ?? 1);
        $items        = is_array($payload['items']  ?? null) ? $payload['items']  : [];
        $reglas       = is_array($payload['reglas'] ?? null) ? $payload['reglas'] : [];

        if($prod_combo<=0){ return ['status'=>false,'message'=>'Producto combo inválido']; }

        $validacionCombo=$this->validarComboPayload($prod_combo,$items,$reglas);
        if(empty($validacionCombo['status'])) return $validacionCombo;

        // verificar producto dueño
        $chk = $this->ejecutar_consulta_simple_preparada(
            "SELECT productos_id FROM productos WHERE productos_id=? AND empresa_id=? LIMIT 1",
            "ii", [$prod_combo,$this->empresaId()]
        );
        if(!$chk || !$chk->num_rows){ return ['status'=>false,'message'=>'El producto combo no pertenece a la empresa']; }

        // evitar duplicado
        $dup = $this->ejecutar_consulta_simple_preparada("SELECT combo_id FROM combos WHERE productos_id=? LIMIT 1","i",[$prod_combo]);
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'Ya existe un combo para ese producto']; }

        $cnn = $this->connection();
        try{
            $cnn->begin_transaction();

            $combo_id = mainModel::correlativo("combo_id","combos");

            // Insert encabezado
            if ($precio_combo === '__omit__') {
                $ok = $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO combos(combo_id, productos_id, activo, version_actual, fecha_creacion, fecha_actualizacion)
                     VALUES(?, ?, ?, ?, NOW(), NOW())",
                    "iiii", [$combo_id,$prod_combo,$activo,$version]
                );
            } else {
                // precio_venta puede ser NULL
                $ok = $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO combos(combo_id, productos_id, activo, precio_venta, version_actual, fecha_creacion, fecha_actualizacion)
                     VALUES(?, ?, ?, ?, ?, NOW(), NOW())",
                    "iiidi",
                    [$combo_id,$prod_combo,$activo,
                     ($precio_combo===null ? null : floatval($precio_combo)),
                     $version]
                );
            }
            if(!$ok){ throw new Exception("No se pudo crear el combo"); }

            // Insert detalle (receta)
            foreach($items as $idx=>$it){
                $cd_id   = mainModel::correlativo("combo_detalle_id","combo_detalle");
                $prod_id = intval($it['productos_id'] ?? 0);
                if($prod_id<=0) continue;

                $cant  = floatval($it['cantidad_por_porcion'] ?? $it['cantidad'] ?? 1);
                $unidad= $this->cleanString($it['unidad'] ?? null);
                $merma = floatval($it['merma_pct'] ?? 0);
                $obli  = intval($it['obligatorio'] ?? (isset($it['es_opcional']) ? (intval($it['es_opcional'])?0:1) : 1)) ? 1 : 0;
                $extra = floatval($it['precio_extra'] ?? 0);
                $ord   = intval($it['orden'] ?? ($idx+1));
                $ver   = intval($it['version'] ?? $version);

                $q = "INSERT INTO combo_detalle(
                        combo_detalle_id, combo_id, productos_id, cantidad_por_porcion, unidad,
                        merma_pct, obligatorio, precio_extra, version, orden, fecha_registro
                      ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $this->ejecutar_consulta_simple_preparada(
                    $q, "iiidsdidii",
                    [$cd_id,$combo_id,$prod_id,$cant,$unidad,$merma,$obli,$extra,$ver,$ord]
                );
            }

            // Insert reglas por categoría (si llegan)
            foreach($reglas as $rg){
                $catId = intval($rg['categoria_id'] ?? 0);
                $max   = max(1, intval($rg['max_seleccion'] ?? 1));
                if ($catId<=0) continue;
                $rid = mainModel::correlativo("combo_categoria_regla_id","combo_categoria_regla");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO combo_categoria_regla(combo_categoria_regla_id, combo_id, categoria_id, max_seleccion, fecha_creacion)
                     VALUES(?, ?, ?, ?, NOW())",
                    "iiii", [$rid,$combo_id,$catId,$max]
                );
            }

            $cnn->commit();
            return ['status'=>true,'message'=>'Combo creado','combo_id'=>$combo_id];

        }catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al guardar combo: '.$e->getMessage()];
        }
    }

    /** Actualizar combo (puede reemplazar receta y reglas completas) */
    public function actualizarCombo($payload){
        $combo_id    = intval($payload['combo_id'] ?? 0);
        if($combo_id<=0){ return ['status'=>false,'message'=>'Combo inválido']; }

        $prod_combo  = array_key_exists('productos_id',$payload) ? intval($payload['productos_id']) : null;
        $activo      = array_key_exists('activo',$payload)       ? (intval($payload['activo'])?1:0) : null;
        $precioCombo = array_key_exists('precio_venta',$payload) ? $payload['precio_venta'] : '__omit__'; // null, decimal o __omit__
        $version     = array_key_exists('version',$payload)      ? intval($payload['version']) : null;

        $items       = array_key_exists('items',$payload)  ? (is_array($payload['items'])  ? $payload['items']  : null) : null;
        $reglas      = array_key_exists('reglas',$payload) ? (is_array($payload['reglas']) ? $payload['reglas'] : null) : null;

        $cnn = $this->connection();
        try{
            $cnn->begin_transaction();

            // Validaciones/duplicados si cambia el producto padre
            if($prod_combo !== null){
                $chk = $this->ejecutar_consulta_simple_preparada(
                    "SELECT productos_id FROM productos WHERE productos_id=? AND empresa_id=? LIMIT 1",
                    "ii", [$prod_combo,$this->empresaId()]
                );
                if(!$chk || !$chk->num_rows){ throw new Exception('El producto combo no pertenece a la empresa'); }

                $dup = $this->ejecutar_consulta_simple_preparada(
                    "SELECT combo_id FROM combos WHERE productos_id=? AND combo_id<>? LIMIT 1","ii",[$prod_combo,$combo_id]);
                if($dup && $dup->num_rows){ throw new Exception('Ese producto ya está asignado a otro combo'); }
            }

            // UPDATE dinámico en combos
            $sets=[]; $types=''; $vals=[];
            if($prod_combo !== null){ $sets[]="productos_id=?";  $types.='i'; $vals[]=$prod_combo; }
            if($activo     !== null){ $sets[]="activo=?";        $types.='i'; $vals[]=$activo; }
            if($version    !== null){ $sets[]="version_actual=?";$types.='i'; $vals[]=$version; }
            if($precioCombo !== '__omit__'){
                $sets[]="precio_venta=?"; $types.='d'; $vals[] = ($precioCombo===null ? null : floatval($precioCombo));
            }
            if(!empty($sets)){
                $sets[]="fecha_actualizacion=NOW()";
                $types.='i'; $vals[]=$combo_id;
                $q="UPDATE combos SET ".implode(',', $sets)." WHERE combo_id=?";
                $this->ejecutar_consulta_simple_preparada($q,$types,$vals);
            }

            // Reemplazo total del detalle si llega 'items'
            if($items !== null){
                $this->ejecutar_consulta_simple_preparada("DELETE FROM combo_detalle WHERE combo_id=?","i",[$combo_id]);
                $ver = $version ?? 1;
                foreach($items as $idx=>$it){
                    $cd_id   = mainModel::correlativo("combo_detalle_id","combo_detalle");
                    $prod_id = intval($it['productos_id'] ?? 0);
                    if($prod_id<=0) continue;

                    $cant  = floatval($it['cantidad_por_porcion'] ?? $it['cantidad'] ?? 1);
                    $unidad= $this->cleanString($it['unidad'] ?? null);
                    $merma = floatval($it['merma_pct'] ?? 0);
                    $obli  = intval($it['obligatorio'] ?? (isset($it['es_opcional']) ? (intval($it['es_opcional'])?0:1) : 1)) ? 1 : 0;
                    $extra = floatval($it['precio_extra'] ?? 0);
                    $ord   = intval($it['orden'] ?? ($idx+1));
                    $verIt = intval($it['version'] ?? $ver);

                    $q = "INSERT INTO combo_detalle(
                            combo_detalle_id, combo_id, productos_id, cantidad_por_porcion, unidad,
                            merma_pct, obligatorio, precio_extra, version, orden, fecha_registro
                          ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $this->ejecutar_consulta_simple_preparada(
                        $q, "iiidsdidii",
                        [$cd_id,$combo_id,$prod_id,$cant,$unidad,$merma,$obli,$extra,$verIt,$ord]
                    );
                }
            }

            // Reglas por categoría (reemplazo total si llegan)
            if($reglas !== null){
                $this->ejecutar_consulta_simple_preparada("DELETE FROM combo_categoria_regla WHERE combo_id=?","i",[$combo_id]);
                foreach($reglas as $rg){
                    $catId = intval($rg['categoria_id'] ?? 0);
                    $max   = max(1, intval($rg['max_seleccion'] ?? 1));
                    if ($catId<=0) continue;
                    $rid = mainModel::correlativo("combo_categoria_regla_id","combo_categoria_regla");
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO combo_categoria_regla(combo_categoria_regla_id, combo_id, categoria_id, max_seleccion, fecha_creacion)
                         VALUES(?, ?, ?, ?, NOW())",
                        "iiii", [$rid,$combo_id,$catId,$max]
                    );
                }
            }

            $cnn->commit();
            return ['status'=>true,'message'=>'Combo actualizado','combo_id'=>$combo_id];

        }catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al actualizar combo: '.$e->getMessage()];
        }
    }

    /** Eliminar combo (por FK se borran detalle y reglas) */
    public function eliminarCombo($combo_id){
        $combo_id = intval($combo_id);
        if($combo_id<=0) return ['status'=>false,'message'=>'Combo inválido'];

        $cnn = $this->connection();
        try{
            $cnn->begin_transaction();
            // Basta borrar el encabezado; detalle y reglas caen por ON DELETE CASCADE
            $this->ejecutar_consulta_simple_preparada("DELETE FROM combos WHERE combo_id=?","i",[$combo_id]);
            $cnn->commit();
            return ['status'=>true,'message'=>'Combo eliminado'];
        }catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al eliminar combo: '.$e->getMessage()];
        }
    }

    /** Disponibilidad del combo según hijos OBLIGATORIOS (backflush) usando 'movimientos' */
    public function calcularDisponibilidadCombo($combo_id, $cantidadSolicitada = 1){
        $combo_id = intval($combo_id);
        $cantidadSolicitada = max(1, intval($cantidadSolicitada));

        // 1) Receta obligatoria
        $sql = "SELECT d.productos_id,
                       d.cantidad_por_porcion,
                       d.merma_pct
                FROM combo_detalle d
                WHERE d.combo_id = ? AND d.obligatorio = 1";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$combo_id]);
        if(!$rs || !$rs->num_rows){
            return ['status'=>false,'message'=>'El combo no tiene componentes obligatorios'];
        }

        $insumos = [];
        while($r=$rs->fetch_assoc()){
            $pid   = intval($r['productos_id']);
            $cant  = (float)$r['cantidad_por_porcion'];
            $merma = (float)$r['merma_pct'];
            $consumoEfectivo = $cant * (1.0 + ($merma/100.0));
            if ($consumoEfectivo <= 0) $consumoEfectivo = $cant; // seguridad
            $insumos[$pid] = $consumoEfectivo; // map pid => consumo
        }
        if (empty($insumos)){
            return ['status'=>false,'message'=>'Receta inválida'];
        }

        // 2) Saldos actuales desde 'movimientos' (último movimiento por producto y empresa)
        $ids = array_keys($insumos);
        $place = implode(',', array_fill(0,count($ids),'?'));
        $types = str_repeat('i', count($ids));

        $params = $ids;
        array_unshift($params, $this->empresaId());
        $typesAll = 'i'.$types;

        $qSaldo = "
            SELECT m.productos_id, m.saldo
            FROM movimientos m
            INNER JOIN (
                SELECT productos_id, MAX(movimientos_id) AS mid
                FROM movimientos
                WHERE empresa_id = ?
                  AND productos_id IN ($place)
                GROUP BY productos_id
            ) ult
              ON ult.productos_id = m.productos_id
             AND ult.mid = m.movimientos_id
        ";

        $rsStk = $this->ejecutar_consulta_simple_preparada($qSaldo, $typesAll, $params);
        $stockMap = [];
        if ($rsStk) {
            while($s = $rsStk->fetch_assoc()){
                $stockMap[(int)$s['productos_id']] = (float)$s['saldo'];
            }
        }

        // 3) Combos posibles = piso( MIN (stock(pid) / consumo(pid)) )
        $posibles = PHP_INT_MAX;
        foreach($insumos as $pid => $consumo){
            $stk = $stockMap[$pid] ?? 0.0;
            $porciones = ($consumo > 0) ? floor($stk / $consumo) : 0;
            if ($porciones < $posibles) $posibles = $porciones;
        }
        if ($posibles < 0 || $posibles === PHP_INT_MAX) $posibles = 0;

        return [
            'status'        => true,
            'disponibles'   => (int)$posibles,
            'alcanza_para'  => ($posibles >= $cantidadSolicitada) ? 'si' : 'no',
            'solicitados'   => $cantidadSolicitada
        ];
    }

    /* ============================================================
     * ====================  PROMOCIONES  =========================
     * ============================================================ */

    /** Obtener promociones completas de la empresa actual para gestión */
    public function obtenerPromociones() {
        $sql = "SELECT promo_id, nombre, descripcion, tipo_descuento, valor,
                       fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana,
                       prioridad, aplica_a, acumula_con_mayoreo, estado
                FROM promociones
                WHERE empresa_id = ?
                ORDER BY estado DESC, prioridad DESC, fecha_fin DESC, nombre ASC";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$this->empresaId()]);
        $out = [];
        if ($rs) {
            while ($r = $rs->fetch_assoc()) {
                $out[] = [
                    'promo_id' => intval($r['promo_id']),
                    'nombre' => $r['nombre'],
                    'descripcion' => $r['descripcion'] ?? '',
                    'tipo_descuento' => $r['tipo_descuento'],
                    'valor' => floatval($r['valor']),
                    'fecha_inicio' => $r['fecha_inicio'],
                    'fecha_fin' => $r['fecha_fin'],
                    'hora_inicio' => $r['hora_inicio'],
                    'hora_fin' => $r['hora_fin'],
                    'dias_semana' => $r['dias_semana'],
                    'prioridad' => intval($r['prioridad']),
                    'aplica_a' => $r['aplica_a'],
                    'acumula_con_mayoreo' => intval($r['acumula_con_mayoreo']),
                    'estado' => intval($r['estado'])
                ];
            }
        }
        return $out;
    }

    /** Obtener listado mínimo de promociones para selects */
    public function obtenerPromocionesMin() {
        $sql = "SELECT promo_id, nombre 
                FROM promociones 
                WHERE empresa_id = ? AND estado = 1
                ORDER BY nombre ASC";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$this->empresaId()]);
        $out = [];
        while($r = $rs->fetch_assoc()) {
            $out[] = [
                'promo_id' => intval($r['promo_id']),
                'nombre' => $r['nombre']
            ];
        }
        return $out;
    }

    /** Guardar una nueva promoción */
    public function guardarPromocion($data) {
        $empresa_id = $this->empresaId();
        $nombre = $this->cleanString($data['nombre'] ?? '');
        $descripcion = $this->cleanString($data['descripcion'] ?? '');
        $tipo_descuento = $this->cleanString($data['tipo_descuento'] ?? 'PORC');
        $valor = floatval($data['valor'] ?? 0);
        $fecha_inicio = $this->cleanString($data['fecha_inicio'] ?? '');
        $fecha_fin = $this->cleanString($data['fecha_fin'] ?? '');
        $hora_inicio = $this->cleanString($data['hora_inicio'] ?? null);
        $hora_fin = $this->cleanString($data['hora_fin'] ?? null);
        $dias_semana = $this->cleanString($data['dias_semana'] ?? null);
        $prioridad = intval($data['prioridad'] ?? 0);
        $aplica_a = $this->cleanString($data['aplica_a'] ?? 'PRODUCTO');
        $acumula_con_mayoreo = intval($data['acumula_con_mayoreo'] ?? 0) ? 1 : 0;
        $estado = intval($data['estado'] ?? 1) ? 1 : 0;
        $creado_por = $this->colaboradorId();

        // Validaciones básicas
        if (empty($nombre)) {
            return ['status' => false, 'message' => 'El nombre es obligatorio'];
        }
        if (empty($fecha_inicio) || empty($fecha_fin)) {
            return ['status' => false, 'message' => 'Las fechas de inicio y fin son obligatorias'];
        }

        try {
            $promo_id = mainModel::correlativo("promo_id", "promociones");
            
            $sql = "INSERT INTO promociones (
                promo_id, empresa_id, nombre, descripcion, tipo_descuento, valor,
                fecha_inicio, fecha_fin, hora_inicio, hora_fin, dias_semana,
                prioridad, aplica_a, acumula_con_mayoreo, estado, creado_por, creado_en
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $ok = $this->ejecutar_consulta_simple_preparada(
                $sql, 
                "iisssdssssiisiii", 
                [
                    $promo_id, $empresa_id, $nombre, $descripcion, $tipo_descuento, $valor,
                    $fecha_inicio, $fecha_fin, $hora_inicio, $hora_fin, $dias_semana,
                    $prioridad, $aplica_a, $acumula_con_mayoreo, $estado, $creado_por
                ]
            );
            
            if ($ok) {
                return ['status' => true, 'message' => 'Promoción guardada', 'promo_id' => $promo_id];
            } else {
                return ['status' => false, 'message' => 'No se pudo guardar la promoción'];
            }
        } catch (Throwable $e) {
            return ['status' => false, 'message' => 'Error al guardar promoción: ' . $e->getMessage()];
        }
    }

    /** Actualizar una promoción existente */
    public function actualizarPromocion($promo_id, $fields) {
        $promo_id = intval($promo_id);
        if ($promo_id <= 0) {
            return ['status' => false, 'message' => 'ID de promoción inválido'];
        }

        // Construir la consulta dinámicamente
        $sets = [];
        $types = '';
        $values = [];
        
        if (isset($fields['nombre'])) {
            $sets[] = "nombre = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['nombre']);
        }
        
        if (isset($fields['descripcion'])) {
            $sets[] = "descripcion = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['descripcion']);
        }
        
        if (isset($fields['tipo_descuento'])) {
            $sets[] = "tipo_descuento = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['tipo_descuento']);
        }
        
        if (isset($fields['valor'])) {
            $sets[] = "valor = ?";
            $types .= 'd';
            $values[] = floatval($fields['valor']);
        }
        
        if (isset($fields['fecha_inicio'])) {
            $sets[] = "fecha_inicio = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['fecha_inicio']);
        }
        
        if (isset($fields['fecha_fin'])) {
            $sets[] = "fecha_fin = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['fecha_fin']);
        }
        
        if (isset($fields['hora_inicio'])) {
            $sets[] = "hora_inicio = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['hora_inicio']);
        }
        
        if (isset($fields['hora_fin'])) {
            $sets[] = "hora_fin = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['hora_fin']);
        }
        
        if (isset($fields['dias_semana'])) {
            $sets[] = "dias_semana = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['dias_semana']);
        }
        
        if (isset($fields['prioridad'])) {
            $sets[] = "prioridad = ?";
            $types .= 'i';
            $values[] = intval($fields['prioridad']);
        }
        
        if (isset($fields['aplica_a'])) {
            $sets[] = "aplica_a = ?";
            $types .= 's';
            $values[] = $this->cleanString($fields['aplica_a']);
        }
        
        if (isset($fields['acumula_con_mayoreo'])) {
            $sets[] = "acumula_con_mayoreo = ?";
            $types .= 'i';
            $values[] = intval($fields['acumula_con_mayoreo']) ? 1 : 0;
        }
        
        if (isset($fields['estado'])) {
            $sets[] = "estado = ?";
            $types .= 'i';
            $values[] = intval($fields['estado']) ? 1 : 0;
        }
        
        if (empty($sets)) {
            return ['status' => false, 'message' => 'No se proporcionaron campos para actualizar'];
        }
        
        $types .= 'i'; // Para el ID al final
        $values[] = $promo_id;
        
        $sql = "UPDATE promociones SET " . implode(', ', $sets) . " WHERE promo_id = ? AND empresa_id = " . $this->empresaId();
        
        $ok = $this->ejecutar_consulta_simple_preparada($sql, $types, $values);
        
        if ($ok) {
            return ['status' => true, 'message' => 'Promoción actualizada'];
        } else {
            return ['status' => false, 'message' => 'No se pudo actualizar la promoción'];
        }
    }

    /** Obtener productos asignados a una promoción */
    public function obtenerProductosDePromo($promo_id) {
        $promo_id = intval($promo_id);
        if ($promo_id <= 0) {
            return [];
        }
        
        $sql = "SELECT pp.producto_id, p.nombre, p.precio_venta
                FROM promo_productos pp
                INNER JOIN productos p ON p.productos_id = pp.producto_id
                WHERE pp.promo_id = ?
                ORDER BY p.nombre ASC";
        
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$promo_id]);
        $out = [];
        
        while ($r = $rs->fetch_assoc()) {
            $out[] = [
                'producto_id' => intval($r['producto_id']),
                'nombre' => $r['nombre'],
                'precio_venta' => floatval($r['precio_venta'])
            ];
        }
        
        return $out;
    }

    /** Asignar productos a una promoción */
    public function asignarProductosAPromo($promo_id, $productos_ids) {
        $promo_id = intval($promo_id);
        if ($promo_id <= 0) {
            return ['status' => false, 'message' => 'Promoción inválida'];
        }
        
        if (empty($productos_ids)) {
            return ['status' => false, 'message' => 'No se proporcionaron productos'];
        }
        
        try {
            $cnn = $this->connection();
            $cnn->begin_transaction();
            
            // Eliminar asignaciones existentes
            $this->ejecutar_consulta_simple_preparada(
                "DELETE FROM promo_productos WHERE promo_id = ?",
                "i", [$promo_id]
            );
            
            // Insertar nuevas asignaciones
            foreach ($productos_ids as $producto_id) {
                $producto_id = intval($producto_id);
                if ($producto_id > 0) {
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO promo_productos (promo_id, producto_id) VALUES (?, ?)",
                        "ii", [$promo_id, $producto_id]
                    );
                }
            }
            
            $cnn->commit();
            return ['status' => true, 'message' => 'Productos asignados correctamente'];
            
        } catch (Throwable $e) {
            $cnn->rollback();
            return ['status' => false, 'message' => 'Error al asignar productos: ' . $e->getMessage()];
        }
    }

    /** Quitar un producto de una promoción */
    public function quitarProductoDePromo($promo_id, $producto_id) {
        $promo_id = intval($promo_id);
        $producto_id = intval($producto_id);
        
        if ($promo_id <= 0 || $producto_id <= 0) {
            return ['status' => false, 'message' => 'Datos inválidos'];
        }
        
        $ok = $this->ejecutar_consulta_simple_preparada(
            "DELETE FROM promo_productos WHERE promo_id = ? AND producto_id = ?",
            "ii", [$promo_id, $producto_id]
        );
        
        if ($ok) {
            return ['status' => true, 'message' => 'Producto eliminado de la promoción'];
        } else {
            return ['status' => false, 'message' => 'No se pudo eliminar el producto de la promoción'];
        }
    }

    /** Obtener categorías asignadas a una promoción */
    public function obtenerCategoriasDePromo($promo_id) {
        $promo_id = intval($promo_id);
        if ($promo_id <= 0) {
            return [];
        }
        
        $sql = "SELECT pc.categoria_id, c.nombre
                FROM promo_categorias pc
                INNER JOIN categoria c ON c.categoria_id = pc.categoria_id
                WHERE pc.promo_id = ?
                ORDER BY c.nombre ASC";
        
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$promo_id]);
        $out = [];
        
        while ($r = $rs->fetch_assoc()) {
            $out[] = [
                'categoria_id' => intval($r['categoria_id']),
                'nombre' => $r['nombre']
            ];
        }
        
        return $out;
    }

    /** Asignar categorías a una promoción */
    public function asignarCategoriasAPromo($promo_id, $categorias_ids) {
        $promo_id = intval($promo_id);
        if ($promo_id <= 0) {
            return ['status' => false, 'message' => 'Promoción inválida'];
        }
        
        if (empty($categorias_ids)) {
            return ['status' => false, 'message' => 'No se proporcionaron categorías'];
        }
        
        try {
            $cnn = $this->connection();
            $cnn->begin_transaction();
            
            // Eliminar asignaciones existentes
            $this->ejecutar_consulta_simple_preparada(
                "DELETE FROM promo_categorias WHERE promo_id = ?",
                "i", [$promo_id]
            );
            
            // Insertar nuevas asignaciones
            foreach ($categorias_ids as $categoria_id) {
                $categoria_id = intval($categoria_id);
                if ($categoria_id > 0) {
                    $this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO promo_categorias (promo_id, categoria_id) VALUES (?, ?)",
                        "ii", [$promo_id, $categoria_id]
                    );
                }
            }
            
            $cnn->commit();
            return ['status' => true, 'message' => 'Categorías asignadas correctamente'];
            
        } catch (Throwable $e) {
            $cnn->rollback();
            return ['status' => false, 'message' => 'Error al asignar categorías: ' . $e->getMessage()];
        }
    }

    /** Quitar una categoría de una promoción */
    public function quitarCategoriaDePromo($promo_id, $categoria_id) {
        $promo_id = intval($promo_id);
        $categoria_id = intval($categoria_id);
        
        if ($promo_id <= 0 || $categoria_id <= 0) {
            return ['status' => false, 'message' => 'Datos inválidos'];
        }
        
        $ok = $this->ejecutar_consulta_simple_preparada(
            "DELETE FROM promo_categorias WHERE promo_id = ? AND categoria_id = ?",
            "ii", [$promo_id, $categoria_id]
        );
        
        if ($ok) {
            return ['status' => true, 'message' => 'Categoría eliminada de la promoción'];
        } else {
            return ['status' => false, 'message' => 'No se pudo eliminar la categoría de la promoción'];
        }
    }

    /** Obtener, para cada producto, la promoción vigente con MAYOR prioridad
     *  e incluir fecha/hora de fin para el contador en el front. */
    public function obtenerPromocionesVigentesProductos() {
        $sql = "SELECT
                    x.producto_id,
                    x.promo_nombre,
                    x.tipo_descuento,
                    x.valor,
                    x.prioridad,
                    x.fecha_fin,
                    x.hora_fin
                FROM (
                    -- 1) promos por producto
                    SELECT 
                        pp.producto_id,
                        pr.nombre AS promo_nombre,
                        pr.tipo_descuento,
                        pr.valor,
                        pr.prioridad,
                        pr.fecha_fin,
                        pr.hora_fin
                    FROM promo_productos pp
                    INNER JOIN promociones pr ON pr.promo_id = pp.promo_id
                    WHERE pr.empresa_id = ?
                    AND pr.estado = 1
                    AND CURDATE() BETWEEN pr.fecha_inicio AND pr.fecha_fin
                    AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
                    AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())), pr.dias_semana) > 0)

                    UNION ALL

                    -- 2) promos por categoría, proyectadas a sus productos
                    SELECT
                        p.productos_id AS producto_id,
                        pr.nombre AS promo_nombre,
                        pr.tipo_descuento,
                        pr.valor,
                        pr.prioridad,
                        pr.fecha_fin,
                        pr.hora_fin
                    FROM productos p
                    INNER JOIN promo_categorias pc ON pc.categoria_id = p.categoria_id
                    INNER JOIN promociones pr       ON pr.promo_id = pc.promo_id
                    WHERE pr.empresa_id = ?
                    AND pr.estado = 1
                    AND CURDATE() BETWEEN pr.fecha_inicio AND pr.fecha_fin
                    AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
                    AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())), pr.dias_semana) > 0)
                ) x
                JOIN (
                    -- elegir la promo de mayor prioridad por producto
                    SELECT producto_id, MAX(prioridad) AS maxp
                    FROM (
                        SELECT pp.producto_id, pr.prioridad
                        FROM promo_productos pp
                        INNER JOIN promociones pr ON pr.promo_id = pp.promo_id
                        WHERE pr.empresa_id = ?
                        AND pr.estado = 1
                        AND CURDATE() BETWEEN pr.fecha_inicio AND pr.fecha_fin
                        AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
                        AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())), pr.dias_semana) > 0)
                        UNION ALL
                        SELECT p.productos_id, pr.prioridad
                        FROM productos p
                        INNER JOIN promo_categorias pc ON pc.categoria_id = p.categoria_id
                        INNER JOIN promociones pr ON pr.promo_id = pc.promo_id
                        WHERE pr.empresa_id = ?
                        AND pr.estado = 1
                        AND CURDATE() BETWEEN pr.fecha_inicio AND pr.fecha_fin
                        AND (pr.hora_inicio IS NULL OR pr.hora_fin IS NULL OR TIME(NOW()) BETWEEN pr.hora_inicio AND pr.hora_fin)
                        AND (pr.dias_semana IS NULL OR FIND_IN_SET(LOWER(DAYNAME(NOW())), pr.dias_semana) > 0)
                    ) t
                    GROUP BY producto_id
                ) pick ON pick.producto_id = x.producto_id AND pick.maxp = x.prioridad";

        $rs = $this->ejecutar_consulta_simple_preparada($sql, "iiii", [
            $this->empresaId(), $this->empresaId(), $this->empresaId(), $this->empresaId()
        ]);

        $promos = [];
        while ($r = $rs->fetch_assoc()) {
            $pid = (int)$r['producto_id'];
            $fecha_fin = (string)$r['fecha_fin'];
            $hora_fin  = $r['hora_fin'] ? (string)$r['hora_fin'] : '23:59:59';
            $fin_iso   = trim($fecha_fin . ' ' . $hora_fin);

            $promos[$pid] = [
                'nombre'         => $r['promo_nombre'],
                'tipo_descuento' => $r['tipo_descuento'],
                'valor'          => (float)$r['valor'],
                'prioridad'      => (int)$r['prioridad'],
                'fecha_fin'      => $fecha_fin,
                'hora_fin'       => $r['hora_fin'],
                'fin_iso'        => $fin_iso
            ];
        }
        return $promos;
    }
    
    /** Crear factura en BORRADOR (estado=1) + detalle, sin usar $db externo */
    public function crearFacturaBorrador(array $params){
        // $params: clientes_id, secuencia_facturacion_id, apertura_id, number,
        // tipo_factura, colaboradores_id, notas, (opcional importe),
        // usuario, empresa_id, detalle (array con productos_id,cantidad,precio, isv_valor?,descuento?,medida?)
        $cnn = $this->connection();

        // Normalizar detalle de entrada
        $detalle = is_array($params['detalle'] ?? null) ? $params['detalle'] : [];
        if (empty($detalle)) {
            return ['status'=>false,'message'=>'Detalle vacío'];
        }

        // Calcular totales desde items (para “importe”)
        $itemsCalc = [];
        foreach($detalle as $d){
            $itemsCalc[] = [
                'productos_id' => intval($d['productos_id'] ?? 0),
                'cantidad'     => floatval($d['cantidad'] ?? 0),
                'precio'       => floatval($d['precio'] ?? 0),
            ];
        }
        $tot = $this->calcularTotalesDesdeItems($itemsCalc);

        // Campos base
        $clientes_id = intval($params['clientes_id'] ?? 0);
        $secuencia_facturacion_id = intval($params['secuencia_facturacion_id'] ?? 0);
        $apertura_id = intval($params['apertura_id'] ?? $this->aperturaId());
        $number = intval($params['number'] ?? 0);
        $tipo_factura = intval($params['tipo_factura'] ?? 1); // 1=Contado
        $colaboradores_id = intval($params['colaboradores_id'] ?? $this->colaboradorId());
        $importe = isset($params['importe']) ? floatval($params['importe']) : floatval($tot['total']);
        $notas = $this->cleanString($params['notas'] ?? '');
        $usuario = intval($params['usuario'] ?? $this->usuarioId());
        $empresa_id = intval($params['empresa_id'] ?? $this->empresaId());
        $hoy = date('Y-m-d');

        try{
            $cnn->begin_transaction();

            // facturas_id no es autoincrement — usamos correlativo
            $factura_id = mainModel::correlativo("facturas_id","facturas");

            // ⚠️ FIX 1: tipos y placeholders alineados (13 ?)
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO facturas(
                    facturas_id, clientes_id, secuencia_facturacion_id, apertura_id, number,
                    tipo_factura, colaboradores_id, importe, notas, fecha, estado,
                    usuario, empresa_id, fecha_registro, fecha_dolar,
                    no_orden, constancia, identificativo_sag, numero_interno
                ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), ?, NULL, NULL, NULL, NULL)",
                /* i i i i i i i d  s  s  i  i  s */
                "iiiiiiidssiis",
                [
                    $factura_id, $clientes_id, $secuencia_facturacion_id, $apertura_id, $number,
                    $tipo_factura, $colaboradores_id, $importe, $notas, $hoy,
                    $usuario, $empresa_id, $hoy
                ]
            );
            if(!$ok){ throw new Exception("No se pudo insertar la factura"); }

            // ===== Flags de ISV por producto =====
            $ids = array_values(array_filter(array_map(function($d){ return intval($d['productos_id'] ?? 0); }, $detalle)));
            $flags = [];
            if (!empty($ids)){
                $place = implode(',', array_fill(0,count($ids),'?'));
                $types = str_repeat('i', count($ids));
                $rs = $this->ejecutar_consulta_simple_preparada(
                    "SELECT productos_id, isv1, isv2 FROM productos WHERE productos_id IN ($place)", $types, $ids
                );
                while($r=$rs->fetch_assoc()){
                    $flags[intval($r['productos_id'])] = [
                        'isv1' => intval($r['isv1'])==1,
                        'isv2' => intval($r['isv2'])==1
                    ];
                }
            }
            $isv = $this->getISVActivosMap(); $r1 = ($isv[1]??0)/100.0; $r2 = ($isv[2]??0)/100.0;

            // ===== Detalle =====
            foreach($detalle as $d){
                $pid = intval($d['productos_id'] ?? 0);
                $qty = floatval($d['cantidad'] ?? 0);
                $pu  = floatval($d['precio']   ?? 0);
                if ($pid<=0 || $qty<=0) continue;

                $f = $flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $isvUnit = ($f['isv1']?($pu*$r1):0) + ($f['isv2']?($pu*$r2):0);
                $isvVal  = $isvUnit * $qty;

                $det_id = mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles
                        (facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                    VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                    "iiiiddis",
                    [
                        $det_id, $factura_id, $pid, $qty, $pu,
                        (isset($d['isv_valor']) ? floatval($d['isv_valor']) : $isvVal),
                        (isset($d['descuento']) ? floatval($d['descuento']) : 0),
                        (isset($d['medida']) ? (string)$d['medida'] : 'UNIDAD')
                    ]
                );
            }

            $cnn->commit();
            return ['status'=>true, 'factura_id'=>$factura_id];

        }catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al crear borrador: '.$e->getMessage()];
        }
    }

    /**
     * Registra/actualiza la comanda para cocina ligada a una factura.
     * - Si ya existe registro en factura_comanda para esa factura: UPDATE (mesa_id, comentarios, fecha_actualizacion)
     * - Si no existe: INSERT (id, factura_id, mesa_id, comentarios, estado='pendiente', fecha_registro=NOW())
     *
     * @param int    $factura_id   ID de la factura (obligatorio)
     * @param int    $mesa_id      ID de la mesa (0 = para llevar)
     * @param string $comentarios  Observaciones/comentarios para cocina
     * @param string $servicio     (opcional, ignorado; se mantiene por compatibilidad)
     * @return array               ['status'=>bool, 'factura_comanda_id'=>int, 'message'=>string?]
     */
    public function registrarComandaCocina($factura_id, $mesa_id = 0, $comentarios = '', $servicio = null){
        $cnn = $this->connection();

        $factura_id  = intval($factura_id);
        $mesa_id     = intval($mesa_id);
        $comentarios = $this->cleanString((string)$comentarios);

        if ($factura_id <= 0) {
            return ['status'=>false, 'message'=>'Factura inválida', 'factura_comanda_id'=>0];
        }

        try {
            $cnn->begin_transaction();

            // Validar que la factura exista
            $rsF = $this->ejecutar_consulta_simple_preparada(
                "SELECT facturas_id FROM facturas WHERE facturas_id=?",
                "i",
                [$factura_id]
            );
            if (!$rsF || !$rsF->num_rows) {
                $cnn->rollback();
                return ['status'=>false, 'message'=>'Factura no encontrada', 'factura_comanda_id'=>0];
            }

            // ¿Existe ya una comanda para esta factura?
            $rsC = $this->ejecutar_consulta_simple_preparada(
                "SELECT id FROM factura_comanda WHERE factura_id=?",
                "i",
                [$factura_id]
            );

            if ($rsC && $rsC->num_rows) {
                // UPDATE existente (sin servicio_tipo)
                $row = $rsC->fetch_assoc();
                $cid = intval($row['id']);

                $okU = $this->ejecutar_consulta_simple_preparada(
                    "UPDATE factura_comanda
                    SET mesa_id=NULLIF(?,0),
                        comentarios_cocina=?,
                        servicio_tipo=?,
                        estado=CASE WHEN estado='preparada' THEN 'pendiente' ELSE estado END,
                        fecha_actualizacion=NOW()
                    WHERE factura_id=?",
                    "issi",
                    [$mesa_id, $comentarios, (($servicio==='mesa')?'mesa':'llevar'), $factura_id]
                );
                if (!$okU) {
                    $cnn->rollback();
                    return ['status'=>false, 'message'=>'No se pudo actualizar la comanda', 'factura_comanda_id'=>0];
                }

                $cnn->commit();
                return ['status'=>true, 'factura_comanda_id'=>$cid];

            } else {
                // INSERT nuevo con servicio_tipo
                $cid = mainModel::correlativo("id","factura_comanda");

                $okI = $this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO factura_comanda
                    (id, factura_id, mesa_id, comentarios_cocina, estado, servicio_tipo, fecha_registro)
                    VALUES (?, ?, NULLIF(?,0), ?, 'pendiente', ?, NOW())",
                    "iiiss",
                    [$cid, $factura_id, $mesa_id, $comentarios, (($servicio==='mesa')?'mesa':'llevar')]
                );
                if (!$okI) {
                    $cnn->rollback();
                    return ['status'=>false, 'message'=>'No se pudo crear la comanda', 'factura_comanda_id'=>0];
                }

                $cnn->commit();
                return ['status'=>true, 'factura_comanda_id'=>$cid];
            }

        } catch (Throwable $e) {
            $cnn->rollback();
            return ['status'=>false, 'message'=>$e->getMessage(), 'factura_comanda_id'=>0];
        }
    }

    /** Cambiar estado de mesa a ocupada/disponible – sin $db externo */
    public function setMesaEstado($mesa_id, $ocupada = true){
        $mesa_id = intval($mesa_id);
        if ($mesa_id <= 0) return ['status'=>false,'message'=>'Mesa inválida'];

        if (!$ocupada) {
            [$factura] = $this->getFacturaAbiertaPorMesa($mesa_id);
            if ($factura) {
                return ['status'=>false,'message'=>'La mesa tiene una cuenta abierta. Cóbrela o cierre la cuenta antes de liberarla.'];
            }
        }

        $estado = $ocupada ? 'ocupada' : 'disponible';
        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE mesas SET estado=? WHERE mesa_id=? AND empresa_id=?",
            "sii", [$estado, $mesa_id, $this->empresaId()]
        );
        return $ok ? ['status'=>true,'message'=>$ocupada?'Mesa ocupada':'Mesa liberada'] : ['status'=>false,'message'=>'No se pudo actualizar la mesa'];
    }

    /** Libera la mesa sin borrar la cuenta abierta. */
    public function liberarMesaConservandoCuenta(int $mesa_id): array {
        $mesa_id=(int)$mesa_id;
        if($mesa_id<=0) return ['status'=>false,'message'=>'Mesa inválida'];
        $empresa=$this->empresaId();
        $rs=$this->ejecutar_consulta_simple_preparada(
            "SELECT rc.factura_id FROM factura_restaurante_cuentas rc
             INNER JOIN facturas f ON f.facturas_id=rc.factura_id AND f.empresa_id=rc.empresa_id
             WHERE rc.empresa_id=? AND rc.mesa_id=? AND rc.estado='abierta' AND f.estado=1
               AND DATE(COALESCE(rc.fecha_actualizacion,rc.fecha_registro))=CURDATE()
             ORDER BY COALESCE(rc.fecha_actualizacion,rc.fecha_registro) DESC,rc.factura_id DESC LIMIT 1",
            "ii",[$empresa,$mesa_id]
        );
        $facturaId=0;
        if($rs && $rs->num_rows){
            $facturaId=(int)$rs->fetch_assoc()['factura_id'];
            $ok=$this->ejecutar_consulta_simple_preparada(
                "UPDATE factura_restaurante_cuentas SET mesa_id=0,servicio_tipo='llevar',fecha_actualizacion=NOW() WHERE factura_id=? AND empresa_id=? AND estado='abierta'",
                "ii",[$facturaId,$empresa]
            );
            if(!$ok) return ['status'=>false,'message'=>'No se pudo desvincular la cuenta de la mesa'];
        }
        // Liberar mesa debe ser IDEMPOTENTE.
        // Después de registrar el pago, otro tramo del flujo puede haberla dejado
        // disponible antes de llegar aquí. En ese caso UPDATE afectaría 0 filas,
        // pero la mesa YA está correctamente liberada y no debe mostrarse warning.
        $stmtMesa=$cnn->prepare(
            "UPDATE mesas SET estado='disponible' WHERE mesa_id=? AND empresa_id=?"
        );
        if(!$stmtMesa){
            return ['status'=>false,'message'=>'No se pudo preparar la liberación de la mesa'];
        }
        $stmtMesa->bind_param("ii",$mesa_id,$empresa);
        $execMesa=$stmtMesa->execute();
        $errorMesa=$stmtMesa->error;
        $stmtMesa->close();

        if(!$execMesa){
            return ['status'=>false,'message'=>'No se pudo liberar la mesa'.($errorMesa?': '.$errorMesa:'')];
        }

        // Confirmar el estado final en BD. Si ya estaba disponible, también es éxito.
        $rsMesa=$this->ejecutar_consulta_simple_preparada(
            "SELECT estado FROM mesas WHERE mesa_id=? AND empresa_id=? LIMIT 1",
            "ii",[$mesa_id,$empresa]
        );
        if(!$rsMesa || !$rsMesa->num_rows){
            return ['status'=>false,'message'=>'No se encontró la mesa después de intentar liberarla'];
        }
        $estadoMesa=strtolower(trim((string)$rsMesa->fetch_assoc()['estado']));
        if($estadoMesa!=='disponible'){
            return ['status'=>false,'message'=>'La mesa no quedó disponible'];
        }

        return [
            'status'=>true,
            'message'=>$facturaId>0?'Mesa liberada; la cuenta continúa abierta':'Mesa liberada',
            'cuenta_conservada'=>$facturaId>0,
            'factura_id'=>$facturaId,
            'ya_estaba_disponible'=>true
        ];
    }

    /** Traer última factura ABIERTA (estado=1) por mesa + su detalle – sin $db externo */
    public function getFacturaAbiertaPorMesa($mesa_id){
        $mesa_id = intval($mesa_id);
        if ($mesa_id <= 0) return [null, []];

        // La cuenta abierta se apoya en su contexto operativo, no en la comanda.
        $sql = "SELECT f.*
                FROM facturas f
                INNER JOIN factura_restaurante_cuentas rc
                  ON rc.factura_id=f.facturas_id AND rc.empresa_id=f.empresa_id AND rc.estado='abierta'
                WHERE rc.mesa_id = ? AND rc.servicio_tipo='mesa' AND f.estado = 1
                  AND DATE(COALESCE(rc.fecha_actualizacion, rc.fecha_registro)) = CURDATE()
                ORDER BY f.facturas_id DESC
                LIMIT 1";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$mesa_id]);
        if (!$rs || !$rs->num_rows) return [null, []];

        $factura = $rs->fetch_assoc();
        $factura_id = intval($factura['facturas_id']);

        // Reusar helper existente para detalle
        $detalle = $this->obtenerDetallesFactura($factura_id);

        return [$factura, $detalle];
    }

/** Marcar estado de factura: 1=Borrador, 2=Pagada, 3=Crédito, 4=Cancelada */
public function marcarFacturaEstado(int $factura_id, int $estado, ?string $notas = null){
    $factura_id = intval($factura_id);
    $estado     = intval($estado);
    if ($factura_id <= 0) return ['status'=>false,'message'=>'ID de factura inválido'];

    if ($notas !== null) {
        $notas = $this->cleanString($notas);
        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE facturas SET estado=?, notas=? WHERE facturas_id=?",
            "isi", [$estado, $notas, $factura_id]
        );
    } else {
        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE facturas SET estado=? WHERE facturas_id=?",
            "ii", [$estado, $factura_id]
        );
    }
    return $ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar el estado de la factura'];
}

/**
 * Crear pago contado (o el que indiques en tipo_pago) + detalle de pago.
 * $data = [
 *   'facturas_id'=>int, 'tipo_pago'=>int(1 contado, 2 crédito), 'importe'=>float,
 *   'efectivo'=>float, 'cambio'=>float, 'tarjeta'=>float,
 *   'usuario'=>int, 'empresa_id'=>int, 'tipo_pago_id'=>int(1 efectivo,2 tarjeta,3 transf),
 *   'banco_id'=>int
 * ]
 */
public function crearPagoContado(array $data){
    $facturas_id = intval($data['facturas_id'] ?? 0);
    if ($facturas_id <= 0) return ['status'=>false,'message'=>'Factura inválida'];

    $ya = $this->ejecutar_consulta_simple_preparada(
        "SELECT pagos_id FROM pagos WHERE facturas_id=? AND estado=1 LIMIT 1", "i", [$facturas_id]
    );
    if ($ya && $ya->num_rows) return ['status'=>false,'message'=>'La factura ya tiene un pago registrado'];

    $tipo_pago    = intval($data['tipo_pago']    ?? 1);          // 1=Contado
    $importe      = floatval($data['importe']    ?? 0);
    $efectivo     = floatval($data['efectivo']   ?? 0);
    $cambio       = floatval($data['cambio']     ?? 0);
    $tarjeta      = floatval($data['tarjeta']    ?? 0);
    $usuario      = intval($data['usuario']      ?? $this->usuarioId());
    $empresa_id   = intval($data['empresa_id']   ?? $this->empresaId());
    $tipo_pago_id = intval($data['tipo_pago_id'] ?? 1);
    $banco_id     = intval($data['banco_id']     ?? 0);
    $referencia    = $this->cleanString($data['referencia'] ?? '');

    // Crear encabezado de pago
    $pagos_id = mainModel::correlativo("pagos_id","pagos");
    $ok = $this->ejecutar_consulta_simple_preparada(
        "INSERT INTO pagos(
            pagos_id, facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta,
            usuario, estado, empresa_id, fecha_registro, contabilizado, referencia_ingreso_id
        ) VALUES(
            ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 1, ?, NOW(), 0, NULL
        )",
        // 9 parámetros: i i i d d d d i i
        "iiiddddii",
        [$pagos_id, $facturas_id, $tipo_pago, $importe, $efectivo, $cambio, $tarjeta, $usuario, $empresa_id]
    );
    if (!$ok) return ['status'=>false,'message'=>'No se pudo registrar el pago'];

    // Detalle del pago
    $pagos_detalles_id = mainModel::correlativo("pagos_detalles_id","pagos_detalles");
    $desc1=$referencia; $desc2=''; $desc3='';
    $okDet = $this->ejecutar_consulta_simple_preparada(
        "INSERT INTO pagos_detalles(
            pagos_detalles_id, pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1, descripcion2, descripcion3
        ) VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
        // tipos: i i i i d s s s
        "iiiidsss",
        [$pagos_detalles_id, $pagos_id, $tipo_pago_id, $banco_id, $importe, $desc1, $desc2, $desc3]
    );
    if (!$okDet) return ['status'=>false,'message'=>'Pago creado, pero no se pudo registrar el detalle'];

    return ['status'=>true,'pagos_id'=>$pagos_id,'pagos_detalles_id'=>$pagos_detalles_id];
}


    /* ===== Reservas de mesas ===== */
    public function reservarMesa(array $data){
        if (!$this->hasTable('mesas_reservas')) {
            return ['status'=>false,'message'=>'Falta crear la tabla mesas_reservas. Ejecute el SQL incluido con esta fase.'];
        }
        $mesa_id=(int)($data['mesa_id'] ?? 0);
        $cliente_id=(int)($data['clientes_id'] ?? 0);
        $fecha=$this->cleanString($data['fecha_reserva'] ?? date('Y-m-d'));
        $hora=$this->cleanString($data['hora_reserva'] ?? date('H:i:s'));
        $personas=max(1,(int)($data['personas'] ?? 1));
        $notas=$this->cleanString($data['notas'] ?? '');
        if($mesa_id<=0 || $cliente_id<=0) return ['status'=>false,'message'=>'Mesa y cliente son obligatorios'];
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/',$hora)) {
            return ['status'=>false,'message'=>'Fecha u hora de reserva inválida'];
        }
        $momento=strtotime($fecha.' '.$hora);
        if($momento===false || $momento < time()-60) return ['status'=>false,'message'=>'La reserva no puede quedar en una fecha u hora pasada'];

        $mesa=$this->ejecutar_consulta_simple_preparada(
            "SELECT estado, capacidad FROM mesas WHERE mesa_id=? AND empresa_id=? LIMIT 1", "ii", [$mesa_id,$this->empresaId()]
        );
        if(!$mesa || !$mesa->num_rows) return ['status'=>false,'message'=>'Mesa no encontrada'];
        $mesaRow=$mesa->fetch_assoc();
        $estado=strtolower((string)$mesaRow['estado']);
        $capacidad=max(1,(int)$mesaRow['capacidad']);
        if($personas>$capacidad) return ['status'=>false,'message'=>'La cantidad de personas supera la capacidad de la mesa'];
        if($estado==='ocupada' || $estado==='mantenimiento') return ['status'=>false,'message'=>'La mesa no está disponible para reservar'];

        $dup=$this->ejecutar_consulta_simple_preparada(
            "SELECT mesa_reserva_id FROM mesas_reservas WHERE mesa_id=? AND empresa_id=? AND estado='activa' LIMIT 1",
            "ii", [$mesa_id,$this->empresaId()]
        );
        if($dup && $dup->num_rows) return ['status'=>false,'message'=>'La mesa ya tiene una reserva activa'];

        $id=mainModel::correlativo('mesa_reserva_id','mesas_reservas');
        $ok=$this->ejecutar_consulta_simple_preparada(
            "INSERT INTO mesas_reservas(mesa_reserva_id,mesa_id,clientes_id,fecha_reserva,hora_reserva,personas,notas,estado,usuario_id,empresa_id,fecha_registro)
             VALUES(?,?,?,?,?,?,?,'activa',?,?,NOW())",
            "iiissisii", [$id,$mesa_id,$cliente_id,$fecha,$hora,$personas,$notas,$this->usuarioId(),$this->empresaId()]
        );
        if(!$ok) return ['status'=>false,'message'=>'No se pudo registrar la reserva'];
        $this->ejecutar_consulta_simple_preparada("UPDATE mesas SET estado='reservada' WHERE mesa_id=?", "i", [$mesa_id]);
        return ['status'=>true,'message'=>'Mesa reservada correctamente','mesa_reserva_id'=>$id];
    }

    public function cancelarReservaMesa($mesa_id){
        if (!$this->hasTable('mesas_reservas')) return ['status'=>false,'message'=>'No existe la estructura de reservas'];
        $mesa_id=(int)$mesa_id;
        if($mesa_id<=0) return ['status'=>false,'message'=>'Mesa inválida'];
        $this->ejecutar_consulta_simple_preparada(
            "UPDATE mesas_reservas SET estado='cancelada', fecha_actualizacion=NOW() WHERE mesa_id=? AND empresa_id=? AND estado='activa'",
            "ii", [$mesa_id,$this->empresaId()]
        );
        $this->ejecutar_consulta_simple_preparada("UPDATE mesas SET estado='disponible' WHERE mesa_id=?", "i", [$mesa_id]);
        return ['status'=>true,'message'=>'Reserva cancelada'];
    }

    public function consumirReservaMesa($mesa_id){
        if (!$this->hasTable('mesas_reservas')) return true;
        $mesa_id=(int)$mesa_id;
        if($mesa_id<=0) return false;
        $this->ejecutar_consulta_simple_preparada(
            "UPDATE mesas_reservas SET estado='atendida', fecha_actualizacion=NOW() WHERE mesa_id=? AND empresa_id=? AND estado='activa'",
            "ii", [$mesa_id,$this->empresaId()]
        );
        return true;
    }

    /* ===== Mesas (acciones directas para el front) ===== */

    /** Marca una mesa como ocupada */
    public function ocuparMesa($mesa_id){
        $mesa_id = intval($mesa_id);
        if($mesa_id <= 0){
            return ['ok'=>false,'msg'=>'Mesa inválida'];
        }
        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE mesas SET estado='ocupada' WHERE mesa_id=?",
            "i", [$mesa_id]
        );
        return $ok ? ['ok'=>true] : ['ok'=>false,'msg'=>'No se pudo ocupar la mesa'];
    }

    /**
     * Devuelve la última factura "abierta" (estado=1) asociada a la mesa,
     * junto con su detalle, para que el front reconstruya la comanda.
     */
    public function getFacturaMesaAbierta($mesa_id){
        $mesa_id = intval($mesa_id);
        if($mesa_id <= 0){
            return ['ok'=>false,'msg'=>'Mesa inválida'];
        }

        // Buscamos la factura más reciente en estado 1 vinculada a esa mesa
        $sql = "SELECT 
                    f.facturas_id,
                    f.clientes_id,
                    f.importe,
                    f.notas,
                    f.estado,
                    f.number
                FROM facturas f
                INNER JOIN factura_comanda fc ON fc.factura_id = f.facturas_id
                WHERE fc.mesa_id = ?
                  AND f.estado = 1
                ORDER BY f.facturas_id DESC
                LIMIT 1";
        $rs = $this->ejecutar_consulta_simple_preparada($sql, "i", [$mesa_id]);
        if(!$rs || !$rs->num_rows){
            return ['ok'=>false,'msg'=>'No hay factura abierta para esta mesa'];
        }

        $factura = $rs->fetch_assoc();
        $factura_id = intval($factura['facturas_id']);

        // Traemos detalle usando tu helper existente
        $detalle = $this->obtenerDetallesFactura($factura_id);

        // Formato que tu front ya consume
        return [
            'ok'      => true,
            'factura' => [
                'facturas_id' => $factura_id,
                'factura_id'  => $factura_id,
                'clientes_id' => intval($factura['clientes_id']),
                'importe'     => floatval($factura['importe']),
                'notas'       => (string)$factura['notas'],
                'estado'      => intval($factura['estado']),
                'number'      => intval($factura['number'])
            ],
            'detalle' => array_map(function($d){
                return [
                    'facturas_detalle_id' => $d['facturas_detalle_id'],
                    'productos_id'        => $d['productos_id'],
                    'cantidad'            => $d['cantidad'],
                    'precio'              => $d['precio'],
                    'isv_valor'           => $d['isv_valor'],
                    'descuento'           => $d['descuento'],
                    'medida'              => $d['medida'],
                ];
            }, $detalle)
        ];
    } 
    
    /** 
     * Devuelve el ID de la apertura de caja activa.
     * Prioriza lo que tengas en $_SESSION['apertura_id_sd']; si no, consulta BD.
     * Guarda el resultado en sesión para reutilizarlo.
     */
    public function obtenerAperturaCajaActiva(?int $colaborador_id = null, ?int $empresa_id = null): int {
        // 1) Si ya está en sesión, úsalo.
        $aid = intval($_SESSION['apertura_id_sd'] ?? 0);
        if ($aid > 0) return $aid;

        // 2) Defaults desde helpers del modelo
        $colaborador_id = intval($colaborador_id ?? $this->colaboradorId());
        $empresa_id     = intval($empresa_id     ?? $this->empresaId());

        $encontrada = 0;

        /* ===== Intento 1: tabla apertura_caja ===== */
        if ($this->hasColumn('apertura_caja', 'apertura_id')) {
            $where = " WHERE 1=1 ";
            $types = "";
            $vals  = [];

            if ($this->hasColumn('apertura_caja', 'empresa_id')) {
                $where .= " AND empresa_id=?"; $types .= "i"; $vals[] = $empresa_id;
            }
            if ($this->hasColumn('apertura_caja', 'colaboradores_id')) {
                $where .= " AND colaboradores_id=?"; $types .= "i"; $vals[] = $colaborador_id;
            }

            // Abierta: por fecha_cierre NULL o por estado=1
            if ($this->hasColumn('apertura_caja','fecha_cierre')) {
                $where .= " AND (fecha_cierre IS NULL OR fecha_cierre='0000-00-00 00:00:00')";
            } elseif ($this->hasColumn('apertura_caja','estado')) {
                $where .= " AND estado=1";
            }

            $sql = "SELECT apertura_id FROM apertura_caja {$where} ORDER BY apertura_id DESC LIMIT 1";
            $rs = $this->ejecutar_consulta_simple_preparada($sql, $types, $vals);
            if ($rs && $rs->num_rows) {
                $encontrada = intval($rs->fetch_assoc()['apertura_id']);
            }
        }

        /* ===== Intento 2: tabla aperturas ===== */
        if ($encontrada <= 0 && $this->hasColumn('aperturas', 'apertura_id')) {
            $where = " WHERE 1=1 ";
            $types = "";
            $vals  = [];

            if ($this->hasColumn('aperturas', 'empresa_id')) {
                $where .= " AND empresa_id=?"; $types .= "i"; $vals[] = $empresa_id;
            }
            if ($this->hasColumn('aperturas', 'colaboradores_id')) {
                $where .= " AND colaboradores_id=?"; $types .= "i"; $vals[] = $colaborador_id;
            }

            if ($this->hasColumn('aperturas','fecha_cierre')) {
                $where .= " AND (fecha_cierre IS NULL OR fecha_cierre='0000-00-00 00:00:00')";
            } elseif ($this->hasColumn('aperturas','estado')) {
                $where .= " AND estado=1";
            } elseif ($this->hasColumn('aperturas','activo')) {
                $where .= " AND activo=1";
            }

            $sql = "SELECT apertura_id FROM aperturas {$where} ORDER BY apertura_id DESC LIMIT 1";
            $rs = $this->ejecutar_consulta_simple_preparada($sql, $types, $vals);
            if ($rs && $rs->num_rows) {
                $encontrada = intval($rs->fetch_assoc()['apertura_id']);
            }
        }

        if ($encontrada > 0) {
            $_SESSION['apertura_id_sd'] = $encontrada;
        }
        return $encontrada;
    }

    /* ===============================
    * FILTROS POR ESTACIÓN (cocina/barra)
    * =============================== */

    /** Devuelve los items de una factura filtrados por estación (cocina/barra) */
    public function obtenerItemsFacturaPorEstacion(int $factura_id, string $estacion){
        $factura_id = intval($factura_id);
        $estacion   = ($estacion === 'barra') ? 'barra' : 'cocina'; // default cocina

        $sql = "SELECT 
                    fd.facturas_detalle_id,
                    fd.facturas_id,
                    fd.productos_id,
                    fd.cantidad,
                    fd.precio,
                    p.nombre AS producto_nombre,
                    LOWER(TRIM(COALESCE(NULLIF(p.estacion,''), NULLIF(c.estacion,''), 'cocina'))) AS estacion
                FROM facturas_detalles fd
                INNER JOIN productos p  ON p.productos_id = fd.productos_id
                INNER JOIN categoria c  ON c.categoria_id = p.categoria_id
                WHERE fd.facturas_id = ?
                  AND LOWER(TRIM(COALESCE(NULLIF(p.estacion,''), NULLIF(c.estacion,''), 'cocina'))) = ?";

        $rs = $this->ejecutar_consulta_simple_preparada($sql, "is", [$factura_id, $estacion]);
        $out = [];
        while($row = $rs->fetch_assoc()){
            $out[] = [
                'facturas_detalle_id' => (int)$row['facturas_detalle_id'],
                'facturas_id'         => (int)$row['facturas_id'],
                'productos_id'        => (int)$row['productos_id'],
                'cantidad'            => (float)$row['cantidad'],
                'precio'              => (float)$row['precio'],
                'producto_nombre'     => (string)$row['producto_nombre'],
                'estacion'            => (string)$row['estacion'],
            ];
        }
        return $out;
    }

    /** Lista comandas pendientes/en_preparacion para una estación dada, incluyendo items filtrados */
    public function listarComandasPorEstacion(string $estacion){
        $estacion = ($estacion === 'barra') ? 'barra' : 'cocina'; // default cocina

        // Trae encabezados de comanda ligados a factura
        $sql = "SELECT 
                    fc.id                 AS factura_comanda_id,
                    fc.factura_id,
                    fc.mesa_id,
                    fc.comentarios_cocina,
                    fc.estado,
                    fc.fecha_registro,
                    f.clientes_id,
                    f.fecha,
                    f.notas
                FROM factura_comanda fc
                INNER JOIN facturas f ON f.facturas_id = fc.factura_id
                WHERE fc.estado IN ('pendiente','en_preparacion')
                ORDER BY fc.fecha_registro ASC";
        $rs = $this->ejecutar_consulta_simple($sql);

        $out = [];
        while($row = $rs->fetch_assoc()){
            $factura_id = (int)$row['factura_id'];
            // Filtra items por estación
            $items = $this->obtenerItemsFacturaPorEstacion($factura_id, $estacion);
            if (!count($items)) {
                // Si la factura no tiene items de esta estación, no la mostramos
                continue;
            }
            $out[] = [
                'factura_comanda_id' => (int)$row['factura_comanda_id'],
                'factura_id'         => $factura_id,
                'mesa_id'            => (int)$row['mesa_id'],
                'comentarios'        => (string)$row['comentarios_cocina'],
                'estado'             => (string)$row['estado'],
                'fecha_registro'     => (string)$row['fecha_registro'],
                'clientes_id'        => (int)$row['clientes_id'],
                'fecha'              => (string)$row['fecha'],
                'notas'              => (string)$row['notas'],
                'items'              => $items
            ];
        }
        return $out;
    }

    /* ===== Configuración / cuentas abiertas (cierre del módulo) ===== */
    public function obtenerConfiguracionOperacion(): array {
        $cfg = [
            'usar_mesas'=>1,
            'usar_comandas'=>1,
            'etiqueta_cocina'=>'Cocina',
            'etiqueta_barra'=>'Barra',
            'destino_comanda'=>'pantalla',
            'momento_ticket'=>'enviar',
            'flujo_cocina'=>'pasos',
            'solicitar_clave_gestion'=>0,
            'permitir_facturas_credito'=>0
        ];
        if (!$this->hasTable('restaurante_configuracion')) return $cfg;

        $hasEC=$this->hasColumn('restaurante_configuracion','etiqueta_cocina');
        $hasEB=$this->hasColumn('restaurante_configuracion','etiqueta_barra');
        $hasDC=$this->hasColumn('restaurante_configuracion','destino_comanda');
        $hasMT=$this->hasColumn('restaurante_configuracion','momento_ticket');
        $hasFC=$this->hasColumn('restaurante_configuracion','flujo_cocina');
        $hasSCG=$this->hasColumn('restaurante_configuracion','solicitar_clave_gestion');
        $hasPFC=$this->hasColumn('restaurante_configuracion','permitir_facturas_credito');

        $cols='usar_mesas, usar_comandas'
            .($hasEC?', etiqueta_cocina':'')
            .($hasEB?', etiqueta_barra':'')
            .($hasDC?', destino_comanda':'')
            .($hasMT?', momento_ticket':'')
            .($hasFC?', flujo_cocina':'')
            .($hasSCG?', solicitar_clave_gestion':'')
            .($hasPFC?', permitir_facturas_credito':'');

        $rs = $this->ejecutar_consulta_simple_preparada(
            "SELECT {$cols} FROM restaurante_configuracion WHERE empresa_id=? LIMIT 1",
            "i", [$this->empresaId()]
        );

        if ($rs && $rs->num_rows) {
            $r=$rs->fetch_assoc();
            $cfg['usar_mesas']=(int)$r['usar_mesas']===1?1:0;
            $cfg['usar_comandas']=(int)$r['usar_comandas']===1?1:0;

            if($hasEC && trim((string)($r['etiqueta_cocina']??''))!=='') {
                $cfg['etiqueta_cocina']=trim((string)$r['etiqueta_cocina']);
            }
            if($hasEB && trim((string)($r['etiqueta_barra']??''))!=='') {
                $cfg['etiqueta_barra']=trim((string)$r['etiqueta_barra']);
            }

            if($hasDC){
                $destino=strtolower(trim((string)($r['destino_comanda']??'pantalla')));
                if(in_array($destino,['pantalla','ticket','ambos'],true)) $cfg['destino_comanda']=$destino;
            }
            if($hasMT){
                $momento=strtolower(trim((string)($r['momento_ticket']??'enviar')));
                if(in_array($momento,['enviar','cobrar'],true)) $cfg['momento_ticket']=$momento;
            }
            if($hasFC){
                $flujo=strtolower(trim((string)($r['flujo_cocina']??'pasos')));
                if(in_array($flujo,['pasos','directo'],true)) $cfg['flujo_cocina']=$flujo;
            }
            if($hasSCG){
                $cfg['solicitar_clave_gestion']=(int)($r['solicitar_clave_gestion']??0)===1?1:0;
            }
            if($hasPFC){
                $cfg['permitir_facturas_credito']=(int)($r['permitir_facturas_credito']??0)===1?1:0;
            }
        }
        return $cfg;
    }

    public function guardarConfiguracionOperacion(
        int $usarMesas,
        int $usarComandas,
        string $etiquetaCocina='Cocina',
        string $etiquetaBarra='Barra',
        string $destinoComanda='pantalla',
        string $momentoTicket='enviar',
        string $flujoCocina='pasos',
        int $solicitarClaveGestion=1,
        int $permitirFacturasCredito=0
    ): array {
        if (!$this->hasTable('restaurante_configuracion')) {
            return ['status'=>false,'message'=>'Falta ejecutar el SQL de configuración incluido en esta entrega.'];
        }

        $empresa=$this->empresaId();
        $usuario=$this->usuarioId();
        $usarMesas=$usarMesas?1:0;
        $usarComandas=$usarComandas?1:0;
        $etiquetaCocina=trim($etiquetaCocina)!==''?substr(trim($etiquetaCocina),0,30):'Cocina';
        $etiquetaBarra=trim($etiquetaBarra)!==''?substr(trim($etiquetaBarra),0,30):'Barra';

        $destinoComanda=strtolower(trim($destinoComanda));
        if(!in_array($destinoComanda,['pantalla','ticket','ambos'],true)) $destinoComanda='pantalla';

        $momentoTicket=strtolower(trim($momentoTicket));
        if(!in_array($momentoTicket,['enviar','cobrar'],true)) $momentoTicket='enviar';

        $flujoCocina=strtolower(trim($flujoCocina));
        if(!in_array($flujoCocina,['pasos','directo'],true)) $flujoCocina='pasos';
        $solicitarClaveGestion=$solicitarClaveGestion?1:0;
        $permitirFacturasCredito=$permitirFacturasCredito?1:0;

        $hasEC=$this->hasColumn('restaurante_configuracion','etiqueta_cocina');
        $hasEB=$this->hasColumn('restaurante_configuracion','etiqueta_barra');
        $hasDC=$this->hasColumn('restaurante_configuracion','destino_comanda');
        $hasMT=$this->hasColumn('restaurante_configuracion','momento_ticket');
        $hasFC=$this->hasColumn('restaurante_configuracion','flujo_cocina');
        $hasSCG=$this->hasColumn('restaurante_configuracion','solicitar_clave_gestion');
        $hasPFC=$this->hasColumn('restaurante_configuracion','permitir_facturas_credito');

        $ex=$this->ejecutar_consulta_simple_preparada(
            "SELECT empresa_id FROM restaurante_configuracion WHERE empresa_id=? LIMIT 1",
            "i",[$empresa]
        );

        $fullBase = $hasEC && $hasEB && $hasDC && $hasMT && $hasFC;

        if($ex && $ex->num_rows){
            if($fullBase){
                if($hasSCG && $hasPFC){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "UPDATE restaurante_configuracion
                         SET usar_mesas=?, usar_comandas=?, etiqueta_cocina=?, etiqueta_barra=?,
                             destino_comanda=?, momento_ticket=?, flujo_cocina=?,
                             solicitar_clave_gestion=?, permitir_facturas_credito=?,
                             usuario_id=?, fecha_actualizacion=NOW()
                         WHERE empresa_id=?",
                        "iisssssiiii",
                        [$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$solicitarClaveGestion,$permitirFacturasCredito,$usuario,$empresa]
                    );
                } elseif($hasSCG){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "UPDATE restaurante_configuracion
                         SET usar_mesas=?, usar_comandas=?, etiqueta_cocina=?, etiqueta_barra=?,
                             destino_comanda=?, momento_ticket=?, flujo_cocina=?, solicitar_clave_gestion=?,
                             usuario_id=?, fecha_actualizacion=NOW()
                         WHERE empresa_id=?",
                        "iisssssiii",
                        [$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$solicitarClaveGestion,$usuario,$empresa]
                    );
                } elseif($hasPFC){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "UPDATE restaurante_configuracion
                         SET usar_mesas=?, usar_comandas=?, etiqueta_cocina=?, etiqueta_barra=?,
                             destino_comanda=?, momento_ticket=?, flujo_cocina=?, permitir_facturas_credito=?,
                             usuario_id=?, fecha_actualizacion=NOW()
                         WHERE empresa_id=?",
                        "iisssssiii",
                        [$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$permitirFacturasCredito,$usuario,$empresa]
                    );
                } else {
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "UPDATE restaurante_configuracion
                         SET usar_mesas=?, usar_comandas=?, etiqueta_cocina=?, etiqueta_barra=?,
                             destino_comanda=?, momento_ticket=?, flujo_cocina=?,
                             usuario_id=?, fecha_actualizacion=NOW()
                         WHERE empresa_id=?",
                        "iisssssii",
                        [$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$usuario,$empresa]
                    );
                }
            } else {
                $ok=$this->ejecutar_consulta_simple_preparada(
                    "UPDATE restaurante_configuracion
                     SET usar_mesas=?, usar_comandas=?, usuario_id=?, fecha_actualizacion=NOW()
                     WHERE empresa_id=?",
                    "iiii",[$usarMesas,$usarComandas,$usuario,$empresa]
                );
            }
        } else {
            if($fullBase){
                if($hasSCG && $hasPFC){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO restaurante_configuracion
                         (empresa_id,usar_mesas,usar_comandas,etiqueta_cocina,etiqueta_barra,destino_comanda,momento_ticket,flujo_cocina,solicitar_clave_gestion,permitir_facturas_credito,usuario_id,fecha_registro,fecha_actualizacion)
                         VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                        "iiisssssiii",
                        [$empresa,$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$solicitarClaveGestion,$permitirFacturasCredito,$usuario]
                    );
                } elseif($hasSCG){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO restaurante_configuracion
                         (empresa_id,usar_mesas,usar_comandas,etiqueta_cocina,etiqueta_barra,destino_comanda,momento_ticket,flujo_cocina,solicitar_clave_gestion,usuario_id,fecha_registro,fecha_actualizacion)
                         VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                        "iiisssssii",
                        [$empresa,$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$solicitarClaveGestion,$usuario]
                    );
                } elseif($hasPFC){
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO restaurante_configuracion
                         (empresa_id,usar_mesas,usar_comandas,etiqueta_cocina,etiqueta_barra,destino_comanda,momento_ticket,flujo_cocina,permitir_facturas_credito,usuario_id,fecha_registro,fecha_actualizacion)
                         VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                        "iiisssssii",
                        [$empresa,$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$permitirFacturasCredito,$usuario]
                    );
                } else {
                    $ok=$this->ejecutar_consulta_simple_preparada(
                        "INSERT INTO restaurante_configuracion
                         (empresa_id,usar_mesas,usar_comandas,etiqueta_cocina,etiqueta_barra,destino_comanda,momento_ticket,flujo_cocina,usuario_id,fecha_registro,fecha_actualizacion)
                         VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                        "iiisssssi",
                        [$empresa,$usarMesas,$usarComandas,$etiquetaCocina,$etiquetaBarra,$destinoComanda,$momentoTicket,$flujoCocina,$usuario]
                    );
                }
            } else {
                $ok=$this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO restaurante_configuracion
                     (empresa_id,usar_mesas,usar_comandas,usuario_id,fecha_registro,fecha_actualizacion)
                     VALUES(?,?,?,?,NOW(),NOW())",
                    "iiii",[$empresa,$usarMesas,$usarComandas,$usuario]
                );
            }
        }

        return $ok
            ? ['status'=>true,'message'=>'Configuración guardada','config'=>$this->obtenerConfiguracionOperacion()]
            : ['status'=>false,'message'=>'No se pudo guardar la configuración'];
    }


    /**
     * Contexto operativo de una cuenta abierta.
     * Se mantiene separado de factura_comanda para que Guardar cuenta funcione
     * aunque el negocio no use pantallas/comandas.
     */
    public function guardarContextoCuenta(int $facturaId, int $mesaId = 0, string $servicioTipo = 'llevar'): array {
        $facturaId = (int)$facturaId;
        $mesaId = max(0, (int)$mesaId);
        $servicioTipo = $servicioTipo === 'mesa' ? 'mesa' : 'llevar';
        if ($facturaId <= 0) return ['status'=>false,'message'=>'Factura inválida'];

        $empresa = $this->empresaId();
        $usuario = $this->usuarioId();

        $rs = $this->ejecutar_consulta_simple_preparada(
            "SELECT factura_id FROM factura_restaurante_cuentas WHERE factura_id=? AND empresa_id=? LIMIT 1",
            "ii", [$facturaId,$empresa]
        );

        if ($rs && $rs->num_rows) {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "UPDATE factura_restaurante_cuentas
                 SET mesa_id=?, servicio_tipo=?, estado='abierta', usuario_id=?, fecha_actualizacion=NOW()
                 WHERE factura_id=? AND empresa_id=?",
                "isiii", [$mesaId,$servicioTipo,$usuario,$facturaId,$empresa]
            );
        } else {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO factura_restaurante_cuentas
                 (factura_id, mesa_id, servicio_tipo, estado, usuario_id, empresa_id, fecha_registro, fecha_actualizacion)
                 VALUES(?, ?, ?, 'abierta', ?, ?, NOW(), NOW())",
                "iisii", [$facturaId,$mesaId,$servicioTipo,$usuario,$empresa]
            );
        }
        return $ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo guardar el contexto de la cuenta'];
    }

    public function cerrarContextoCuenta(int $facturaId): bool {
        if ($facturaId <= 0) return false;
        return (bool)$this->ejecutar_consulta_simple_preparada(
            "UPDATE factura_restaurante_cuentas SET estado='cerrada', fecha_actualizacion=NOW()
             WHERE factura_id=? AND empresa_id=?",
            "ii", [$facturaId,$this->empresaId()]
        );
    }

    /**
     * Actualiza únicamente el borrador de restaurante.
     * No registra pagos, no numera fiscalmente y no envía comanda.
     */
    public function actualizarCuentaBorrador(array $data, int $mesaId = 0, string $servicioTipo = 'llevar'): array {
        $cnn = $this->connection();
        $facturaId = (int)($data['factura_id'] ?? 0);
        $clienteId = (int)($data['cliente_id'] ?? 0);
        $tipoFactura = ((int)($data['tipo_factura'] ?? 1) === 2) ? 2 : 1;
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $notas = $this->cleanString($data['observaciones'] ?? '');
        $mesaId = max(0,(int)$mesaId);
        $servicioTipo = $servicioTipo === 'mesa' ? 'mesa' : 'llevar';

        if ($facturaId <= 0) return ['status'=>false,'message'=>'Cuenta inválida'];
        if (!$items) return ['status'=>false,'message'=>'No hay productos en la cuenta'];

        try {
            $cnn->begin_transaction();

            $rf = $this->ejecutar_consulta_simple_preparada(
                "SELECT estado FROM facturas WHERE facturas_id=? AND empresa_id=? LIMIT 1",
                "ii", [$facturaId,$this->empresaId()]
            );
            if (!$rf || !$rf->num_rows) throw new Exception('La cuenta no existe');
            if ((int)$rf->fetch_assoc()['estado'] !== 1) throw new Exception('La cuenta ya no está abierta');

            $tot = $this->calcularTotalesDesdeItems($items);

            // IMPORTANTE:
            // Al cobrar una mesa, la cuenta normalmente ya fue guardada/enviada a Cocina.
            // Por eso este UPDATE puede dejar exactamente los mismos valores y afectar 0 filas.
            // Eso NO es un error: la cuenta sigue siendo válida y debe continuar al modal de pago.
            //
            // Usamos mysqli directamente para distinguir:
            // - execute() = true + affected_rows = 0  -> correcto, no hubo cambios.
            // - execute() = false                    -> error SQL real.
            $sqlUpdateCuenta = "UPDATE facturas
                                SET clientes_id=?, tipo_factura=?, importe=?, notas=?
                                WHERE facturas_id=? AND empresa_id=? AND estado=1";
            $stmtUpdateCuenta = $cnn->prepare($sqlUpdateCuenta);
            if (!$stmtUpdateCuenta) {
                throw new Exception('No se pudo preparar la actualización de la cuenta: '.$cnn->error);
            }

            $empresaActual = $this->empresaId();
            $importeActual = (float)$tot['total'];
            $stmtUpdateCuenta->bind_param(
                "iidsii",
                $clienteId,
                $tipoFactura,
                $importeActual,
                $notas,
                $facturaId,
                $empresaActual
            );

            if (!$stmtUpdateCuenta->execute()) {
                $errorUpdate = $stmtUpdateCuenta->error ?: $cnn->error;
                $stmtUpdateCuenta->close();
                throw new Exception('No se pudo actualizar la cuenta'.($errorUpdate ? ': '.$errorUpdate : ''));
            }
            $stmtUpdateCuenta->close();

            $this->ejecutar_consulta_simple_preparada(
                "DELETE FROM facturas_detalles WHERE facturas_id=?","i",[$facturaId]
            );

            $isv=$this->getISVActivosMap();
            $r1=($isv[1]??0)/100.0; $r2=($isv[2]??0)/100.0;
            $ids=[];
            foreach($items as $i){
                $pid=(int)($i['producto_id'] ?? $i['productos_id'] ?? 0);
                if($pid>0) $ids[]=$pid;
            }
            $ids=array_values(array_unique($ids));
            $flags=[];
            if($ids){
                $place=implode(',',array_fill(0,count($ids),'?'));
                $types=str_repeat('i',count($ids));
                $rp=$this->ejecutar_consulta_simple_preparada(
                    "SELECT productos_id,isv1,isv2 FROM productos WHERE productos_id IN ($place)",
                    $types,$ids
                );
                if($rp) while($row=$rp->fetch_assoc()){
                    $flags[(int)$row['productos_id']]=[
                        'isv1'=>(int)$row['isv1']===1,
                        'isv2'=>(int)$row['isv2']===1
                    ];
                }
            }

            foreach($items as $it){
                $pid=(int)($it['producto_id'] ?? $it['productos_id'] ?? 0);
                $qty=max(1,(float)($it['cantidad'] ?? 0));
                $pu=(float)($it['precio'] ?? 0);
                if($pid<=0 || $qty<=0) continue;
                $f=$flags[$pid] ?? ['isv1'=>false,'isv2'=>false];
                $isvLine=((($f['isv1']?$pu*$r1:0)+($f['isv2']?$pu*$r2:0))*$qty);
                $detId=mainModel::correlativo("facturas_detalle_id","facturas_detalles");
                $ins=$this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO facturas_detalles
                     (facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida)
                     VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                    "iiidddds",
                    [
                        $detId,$facturaId,$pid,$qty,$pu,$isvLine,
                        (float)($it['descuento'] ?? 0),
                        (string)($it['medida'] ?? 'UNIDAD')
                    ]
                );
                if(!$ins) throw new Exception('No se pudo actualizar el detalle de la cuenta');
            }

            $ctx=$this->guardarContextoCuenta($facturaId,$mesaId,$servicioTipo);
            if(empty($ctx['status'])) throw new Exception($ctx['message'] ?? 'No se pudo guardar el contexto');

            if($mesaId>0){
                $this->ejecutar_consulta_simple_preparada(
                    "UPDATE mesas SET estado='ocupada' WHERE mesa_id=?","i",[$mesaId]
                );
            }

            $cnn->commit();
            return ['status'=>true,'message'=>'Cuenta actualizada','factura_id'=>$facturaId];
        } catch(Throwable $e){
            $cnn->rollback();
            return ['status'=>false,'message'=>'Error al actualizar: '.$e->getMessage()];
        }
    }

    /**
     * Registra solamente las cantidades NUEVAS enviadas a preparación.
     * Lo ya enviado no vuelve a aparecer en Cocina/Barra.
     */
    public function registrarNuevosItemsComanda(int $facturaId, int $mesaId = 0, string $comentarios = '', string $servicio='llevar'): array {
        if($facturaId<=0) return ['status'=>false,'message'=>'Factura inválida'];
        $servicio=$servicio==='mesa'?'mesa':'llevar';
        $comentarios=$this->cleanString($comentarios);

        try{
            $this->guardarContextoCuenta($facturaId,$mesaId,$servicio);

            // Mantener cabecera de comanda para compatibilidad con pantallas existentes.
            $cab=$this->registrarComandaCocina($facturaId,$mesaId,$comentarios,$servicio);
            if(empty($cab['status'])) return $cab;

            $sql="SELECT fd.productos_id, SUM(fd.cantidad) cantidad,
                        LOWER(TRIM(COALESCE(NULLIF(p.estacion,''),NULLIF(c.estacion,''),'cocina'))) estacion
                  FROM facturas_detalles fd
                  INNER JOIN productos p ON p.productos_id=fd.productos_id
                  LEFT JOIN categoria c ON c.categoria_id=p.categoria_id
                  WHERE fd.facturas_id=?
                  GROUP BY fd.productos_id, estacion";
            $rs=$this->ejecutar_consulta_simple_preparada($sql,"i",[$facturaId]);
            $nuevos=0;

            if($rs) while($row=$rs->fetch_assoc()){
                $pid=(int)$row['productos_id'];
                $actual=(float)$row['cantidad'];
                $est=in_array($row['estacion'],['cocina','barra'],true)?$row['estacion']:'cocina';

                $re=$this->ejecutar_consulta_simple_preparada(
                    "SELECT COALESCE(SUM(cantidad),0) enviada
                     FROM factura_comanda_items
                     WHERE factura_id=? AND productos_id=?",
                    "ii",[$facturaId,$pid]
                );
                $enviada=($re&&$re->num_rows)?(float)$re->fetch_assoc()['enviada']:0.0;
                $delta=$actual-$enviada;
                if($delta<=0.0001) continue;

                $id=mainModel::correlativo("comanda_item_id","factura_comanda_items");
                $ok=$this->ejecutar_consulta_simple_preparada(
                    "INSERT INTO factura_comanda_items
                     (comanda_item_id,factura_id,productos_id,cantidad,estacion,estado,fecha_registro)
                     VALUES(?,?,?,?,?,'pendiente',NOW())",
                    "iiids",[$id,$facturaId,$pid,$delta,$est]
                );
                if(!$ok){
                    return ['status'=>false,'message'=>'No se pudo registrar el producto '.$pid.' en la comanda'];
                }
                $nuevos++;
            }

            return ['status'=>true,'factura_comanda_id'=>(int)($cab['factura_comanda_id']??0),'nuevos'=>$nuevos];
        }catch(Throwable $e){
            return ['status'=>false,'message'=>$e->getMessage()];
        }
    }

    public function obtenerCuentasAbiertas(): array {
        $empresa=$this->empresaId();
        $sql="SELECT f.facturas_id, f.clientes_id, f.tipo_factura, f.importe, f.notas, f.fecha, f.fecha_registro,
                    COALESCE(c.nombre,'Consumidor Final') cliente_nombre,
                    COALESCE(c.rtn,'') cliente_rtn,
                    COALESCE(rc.mesa_id,0) mesa_id,
                    COALESCE(rc.servicio_tipo,'llevar') servicio_tipo,
                    CASE WHEN DATE(COALESCE(rc.fecha_actualizacion,rc.fecha_registro)) < CURDATE() THEN 1 ELSE 0 END es_anterior,
                    COALESCE(m.numero,'') mesa_numero,
                    COUNT(fd.facturas_detalle_id) lineas,
                    COALESCE(SUM(fd.cantidad),0) unidades,
                    COALESCE((SELECT SUM(fci.cantidad) FROM factura_comanda_items fci WHERE fci.factura_id=f.facturas_id),0) enviadas_preparacion
             FROM facturas f
             LEFT JOIN clientes c ON c.clientes_id=f.clientes_id
             INNER JOIN factura_restaurante_cuentas rc
                ON rc.factura_id=f.facturas_id AND rc.empresa_id=f.empresa_id AND rc.estado='abierta'
             LEFT JOIN mesas m ON m.mesa_id=rc.mesa_id
             LEFT JOIN facturas_detalles fd ON fd.facturas_id=f.facturas_id
             WHERE f.empresa_id=? AND f.estado=1
             GROUP BY f.facturas_id,f.clientes_id,f.importe,f.notas,f.fecha,f.fecha_registro,
                      c.nombre,c.rtn,rc.mesa_id,rc.servicio_tipo,rc.fecha_actualizacion,rc.fecha_registro,m.numero
             ORDER BY COALESCE(rc.fecha_actualizacion,rc.fecha_registro) DESC, f.facturas_id DESC";
        $rs=$this->ejecutar_consulta_simple_preparada($sql,'i',[$empresa]);
        $out=[];
        if($rs) while($r=$rs->fetch_assoc()){
            $out[]=[
                'facturas_id'=>(int)$r['facturas_id'],
                'clientes_id'=>(int)$r['clientes_id'],
                'tipo_factura'=>(int)($r['tipo_factura']??1)===2?2:1,
                'cliente_nombre'=>$r['cliente_nombre'],
                'cliente_rtn'=>$r['cliente_rtn'],
                'importe'=>(float)$r['importe'],
                'notas'=>$r['notas']??'',
                'fecha'=>$r['fecha']??'',
                'fecha_registro'=>$r['fecha_registro']??'',
                'mesa_id'=>(int)$r['mesa_id'],
                'mesa_numero'=>$r['mesa_numero']??'',
                'servicio_tipo'=>$r['servicio_tipo']==='mesa'?'mesa':'llevar',
                'es_anterior'=>(int)($r['es_anterior']??0),
                'lineas'=>(int)$r['lineas'],
                'unidades'=>(float)$r['unidades'],
                'enviadas_preparacion'=>(float)$r['enviadas_preparacion']
            ];
        }
        return $out;
    }

    public function obtenerCuentaAbiertaPorId(int $facturaId): ?array {
        if($facturaId<=0) return null;
        $empresa=$this->empresaId();
        $rs=$this->ejecutar_consulta_simple_preparada(
            "SELECT f.facturas_id,f.clientes_id,f.tipo_factura,f.importe,f.notas,f.fecha,
                    COALESCE(c.nombre,'Consumidor Final') cliente_nombre,
                    COALESCE(c.rtn,'') cliente_rtn,
                    COALESCE(rc.mesa_id,0) mesa_id,
                    COALESCE(rc.servicio_tipo,'llevar') servicio_tipo,
                    CASE WHEN DATE(COALESCE(rc.fecha_actualizacion,rc.fecha_registro)) < CURDATE() THEN 1 ELSE 0 END es_anterior,
                    COALESCE(m.numero,'') mesa_numero
             FROM facturas f
             LEFT JOIN clientes c ON c.clientes_id=f.clientes_id
             INNER JOIN factura_restaurante_cuentas rc
                ON rc.factura_id=f.facturas_id AND rc.empresa_id=f.empresa_id AND rc.estado='abierta'
             LEFT JOIN mesas m ON m.mesa_id=rc.mesa_id
             WHERE f.facturas_id=? AND f.empresa_id=? AND f.estado=1 LIMIT 1",
            'ii',[$facturaId,$empresa]
        );
        if(!$rs || !$rs->num_rows) return null;
        $f=$rs->fetch_assoc();
        $f['items']=$this->obtenerDetallesFactura($facturaId);
        return $f;
    }

}