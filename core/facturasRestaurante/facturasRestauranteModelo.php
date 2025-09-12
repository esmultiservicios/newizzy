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

    public function actualizarMesa($mesa_id,$numero,$capacidad,$ubicacion){
        $mesa_id = intval($mesa_id);
        $numero  = $this->cleanString($numero);
        $capacidad = intval($capacidad);
        $ubicacion = $this->cleanString($ubicacion);
        if($mesa_id<=0 || $numero===''){ return ['status'=>false,'message'=>'Datos de mesa inválidos']; }

        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM mesas WHERE numero=? AND empresa_id=? AND mesa_id<>?",
            "sii", [$numero,$this->empresaId(),$mesa_id]
        );
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'Ya existe otra mesa con ese número']; }

        $ok = $this->ejecutar_consulta_simple_preparada(
            "UPDATE mesas SET numero=?, capacidad=?, ubicacion=? WHERE mesa_id=?",
            "sisi", [$numero,$capacidad,$ubicacion,$mesa_id]
        );
        return $ok ? ['status'=>true,'message'=>'Mesa actualizada'] : ['status'=>false,'message'=>'No se pudo actualizar la mesa'];
    }
    

    /* ===== Catálogo ===== */

    /** Solo categorías con productos restaurante=1; incluye estacion SI existe la columna */
    public function obtenerCategoriasProductos(){
        // ¿La tabla categoria tiene columna 'estacion'?
        $tieneEst = $this->hasColumn('categoria','estacion');
    
        // Valor a devolver en 'estacion'
        $selEst = $tieneEst
            ? "LOWER(COALESCE(NULLIF(c.estacion,''),'ninguna'))"
            : "'ninguna'";
    
        // WHERE base
        $where = "WHERE c.estado = 1";
        // Si existe la columna estación, excluye 'ninguna'
        if ($tieneEst) {
            $where .= " AND c.estacion IN ('cocina','barra')";
        }
    
        // **Sin INNER JOIN con productos**: debe traer TODAS las categorías activas
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
            $est = strtolower($r['estacion'] ?? 'ninguna');
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
         
    /** Guardar categoría (si no existe la columna estacion, se ignora y se asume 'ninguna') */
    public function guardarCategoria($nombre, $estacion='ninguna'){
        $nombre = $this->cleanString($nombre);
        $estacion = strtolower($this->cleanString($estacion));
        if(!in_array($estacion,['ninguna','cocina','barra'],true)) $estacion='ninguna';
        if($nombre===''){ return ['status'=>false,'message'=>'Nombre requerido']; }

        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM categoria WHERE nombre=? LIMIT 1", "s", [$nombre]
        );
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'La categoría ya existe']; }

        $id = mainModel::correlativo("categoria_id","categoria");

        if ($this->hasColumn('categoria','estacion')) {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO categoria(categoria_id,nombre,estacion,estado,fecha_registro) VALUES(?, ?, ?, 1, NOW())",
                "iss",
                [$id,$nombre,$estacion]
            );
        } else {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "INSERT INTO categoria(categoria_id,nombre,estado,fecha_registro) VALUES(?, ?, 1, NOW())",
                "is",
                [$id,$nombre]
            );
        }
        return $ok ? ['status'=>true,'categoria_id'=>$id] : ['status'=>false,'message'=>'No se pudo guardar la categoría'];
    }

    public function actualizarCategoria($categoria_id, $nombre, $estacion='ninguna'){
        $categoria_id = intval($categoria_id);
        $nombre = $this->cleanString($nombre);
        $estacion = strtolower($this->cleanString($estacion));
        if(!in_array($estacion,['ninguna','cocina','barra'],true)) $estacion='ninguna';

        if($categoria_id<=0 || $nombre===''){ return ['status'=>false,'message'=>'Datos inválidos']; }

        $dup = $this->ejecutar_consulta_simple_preparada(
            "SELECT 1 FROM categoria WHERE nombre=? AND categoria_id<>? LIMIT 1", "si", [$nombre,$categoria_id]
        );
        if($dup && $dup->num_rows){ return ['status'=>false,'message'=>'Ya existe una categoría con ese nombre']; }

        if ($this->hasColumn('categoria','estacion')) {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "UPDATE categoria SET nombre=?, estacion=? WHERE categoria_id=?", "ssi", [$nombre,$estacion,$categoria_id]
            );
        } else {
            $ok = $this->ejecutar_consulta_simple_preparada(
                "UPDATE categoria SET nombre=? WHERE categoria_id=?", "si", [$nombre,$categoria_id]
            );
        }
        return $ok ? ['status'=>true,'message'=>'Categoría actualizada'] : ['status'=>false,'message'=>'No se pudo actualizar la categoría'];
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
        $tieneEst = $this->hasColumn('categoria','estacion');
        $estSel   = $tieneEst ? 'c.estacion' : "'ninguna'";
        $sql="SELECT p.productos_id, p.nombre, p.descripcion, p.precio_venta,
                     p.cantidad_mayoreo, p.precio_mayoreo, p.file_name, p.categoria_id,
                     p.isv1, p.isv2, p.barCode,
                     c.nombre AS categoria_nombre, $estSel AS categoria_estacion
              FROM productos p
              INNER JOIN categoria c ON c.categoria_id = p.categoria_id
              WHERE p.estado=1 AND p.restaurante=1
              ORDER BY p.nombre ASC";
        $rs=$this->ejecutar_consulta_simple($sql);
        $out=[];
        while($r=$rs->fetch_assoc()){
            $catEst = strtolower($r['categoria_estacion'] ?? 'ninguna');
            if(!in_array($catEst,['ninguna','cocina','barra'],true)) $catEst='ninguna';
    
            $para_cocina = 0;
            if ($catEst === 'cocina')      $para_cocina = 1;
            elseif ($catEst === 'barra')   $para_cocina = 0;
            else $para_cocina = $this->esParaCocinaHeuristica($r['categoria_nombre'],$r['nombre']) ? 1 : 0;
    
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
                'estacion'         => $catEst,
                'para_cocina'      => $para_cocina ? 1 : 0
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
            'file_name'=>$nombreArchivo
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

        if($productos_id<=0){ return ['status'=>false,'message'=>'ID de producto inválido']; }
        if($nombre==='' || $catId<=0){ return ['status'=>false,'message'=>'Nombre y categoría son obligatorios']; }

        $isv_venta      = ($isv1||$isv2) ? 1 : 2;
        $empresa_id     = $this->empresaId();
        $colaborador_id = $this->colaboradorId();

        // ===== UPDATE de campos básicos usando prepare/execute directo
        $cnn  = $this->connection();
        $sqlU = "UPDATE productos
                 SET categoria_id=?, nombre=?, descripcion=?, precio_venta=?,
                     isv_venta=?, isv1=?, isv2=?, colaborador_id=?, empresa_id=?
                 WHERE productos_id=?";
        $stmt = $cnn->prepare($sqlU);
        if(!$stmt){
            return ['status'=>false,'message'=>'No se pudo preparar la actualización'];
        }
        $stmt->bind_param(
            "issdiiiiii",
            $catId, $nombre, $desc, $precio,
            $isv_venta, $isv1, $isv2, $colaborador_id, $empresa_id,
            $productos_id
        );
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
                        VALUES (?,?,?,?, 'pendiente', ?, NOW())",
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
                        VALUES (?,?,?,?, 'pendiente', ?, NOW())",
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

    /** Crear combo + receta + reglas por categoría (transaccional) */
    public function guardarCombo($payload){
        $prod_combo   = intval($payload['productos_id'] ?? 0);
        $activo       = intval($payload['activo'] ?? 1) ? 1 : 0;
        $precio_combo = array_key_exists('precio_venta', $payload) ? $payload['precio_venta'] : '__omit__'; // null o decimal o __omit__
        $version      = intval($payload['version'] ?? 1);
        $items        = is_array($payload['items']  ?? null) ? $payload['items']  : [];
        $reglas       = is_array($payload['reglas'] ?? null) ? $payload['reglas'] : [];

        if($prod_combo<=0){ return ['status'=>false,'message'=>'Producto combo inválido']; }

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
}
