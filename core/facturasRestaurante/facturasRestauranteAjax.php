<?php
// core/facturasRestaurante/facturasRestauranteAjax.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/facturasRestauranteModelo.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status'=>false,'message'=>'Método no permitido']);
        exit;
    }

    // Soportar form-data + JSON (raw)
    $raw     = file_get_contents('php://input');
    $asJson  = json_decode($raw, true);
    $action  = $_POST['action'] ?? ($asJson['action'] ?? null);
    $payload = $asJson['data']   ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode(['status'=>false,'message'=>'Acción no especificada']);
        exit;
    }

    // Helper para leer valores en form o JSON con default
    $in = function(string $key, $default=null) use ($payload) {
        if (isset($_POST[$key])) return $_POST[$key];
        if (is_array($payload) && array_key_exists($key, $payload)) return $payload[$key];
        return $default;
    };

    // -------- Helpers locales (normalización/validación) ----------
    $toBool = function($v, $default=0){
        if (is_null($v)) return $default ? 1 : 0;
        if (is_bool($v)) return $v ? 1 : 0;
        $s = strtolower((string)$v);
        return in_array($s, ['1','true','sí','si','on','yes'], true) ? 1 : 0;
    };
    $onlyTime = function($t){
        if ($t === null || $t === '') return null;
        // soporta "HH:MM" y "HH:MM:SS"
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $t)) {
            return strlen($t)===5 ? $t.':00' : $t;
        }
        // soporta "YYYY-MM-DD HH:MM" o con segundos
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T](\d{2}:\d{2}(:\d{2})?)$/', $t, $m)) {
            return strlen($m[1])===5 ? $m[1].':00' : $m[1];
        }
        return null;
    };
    $onlyDate = function($d){
        if ($d === null || $d === '') return null;
        // soporta "YYYY-MM-DD"
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
        // soporta "YYYY-MM-DDTHH:MM(:SS)"
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}(:\d{2})?$/', $d, $m)) return $m[1];
        return null;
    };
    $normDiasSemana = function($csv){
        if ($csv===null || $csv==='') return null;
        $allow = ['mon','tue','wed','thu','fri','sat','sun'];
        $parts = array_filter(array_map('trim', explode(',', strtolower((string)$csv))));
        $parts = array_values(array_unique(array_intersect($parts, $allow)));
        return count($parts) ? implode(',', $parts) : null;
    };
    $normTipoDesc = function($v){
        $v = strtoupper(trim((string)$v));
        return in_array($v, ['PORC','MONTO'], true) ? $v : 'PORC';
    };
    $normAplicaA = function($v){
        $v = strtoupper(trim((string)$v));
        return in_array($v, ['PRODUCTO','CATEGORIA','TODOS'], true) ? $v : 'PRODUCTO';
    };

    $m = new facturasRestauranteModelo();

    switch ($action) {

        /* ============================================================
         * ======= DATA CATÁLOGO / ISV / MESAS ========================
         * ============================================================ */
        case 'loadISV':
            echo json_encode(['status'=>true,'isv'=>$m->obtenerISVTiposPublico()]);
            break;

        case 'loadMesas':
            echo json_encode(['status'=>true,'mesas'=>$m->obtenerMesas()]);
            break;

        case 'saveMesa':
            $numero    = $in('numero', '');
            $capacidad = intval($in('capacidad', 4));
            $ubicacion = $in('ubicacion', 'Interior');
            echo json_encode($m->guardarMesa($numero, $capacidad, $ubicacion));
            break;

        case 'updateMesa':
            $mesa_id   = intval($in('mesa_id', 0));
            $numero    = $in('numero', '');
            $capacidad = intval($in('capacidad', 4));
            $ubicacion = $in('ubicacion', 'Interior');
            echo json_encode($m->actualizarMesa($mesa_id, $numero, $capacidad, $ubicacion));
            break;

        case 'loadCategorias':
            // opcional: 'cocina' | 'barra' | 'todas'
            $estacion = strtolower((string)$in('estacion', 'todas'));
            if (!in_array($estacion, ['cocina','barra','todas'], true)) {
                $estacion = 'todas';
            }
            echo json_encode(['status'=>true,'categorias'=>$m->obtenerCategoriasProductos($estacion)]);
            break;

        case 'loadProductos':
            // Puedes filtrar por restaurante=1 dentro del modelo si lo deseas
            echo json_encode(['status'=>true,'productos'=>$m->obtenerProductos()]);
            break;

        case 'loadClientes':
            echo json_encode(['status'=>true,'clientes'=>$m->obtenerClientes()]);
            break;

        /* ============================================================
         * ======= GUARDAR / ACTUALIZAR CAT/PROD/CLI =================
         * ============================================================ */

        case 'saveCategoria':
            $nombre   = trim((string)$in('nombre', ''));
            $estacion = strtolower(trim((string)$in('estacion', 'ninguna')));
            if (!in_array($estacion, ['ninguna','cocina','barra'], true)) { $estacion = 'ninguna'; }
            echo json_encode($m->guardarCategoria($nombre, $estacion));
            break;

        case 'updateCategoria':
            $categoria_id = intval($in('categoria_id', 0));
            $nombre       = trim((string)$in('nombre', ''));
            $estacion     = strtolower(trim((string)$in('estacion', 'ninguna')));
            if (!in_array($estacion, ['ninguna','cocina','barra'], true)) { $estacion = 'ninguna'; }
            echo json_encode($m->actualizarCategoria($categoria_id, $nombre, $estacion));
            break;

        case 'saveProductoBasico':
            // Para FormData, los datos vienen en $_POST
            $payload = [
                'nombre'        => (string)($_POST['nombre'] ?? ''),
                'descripcion'   => (string)($_POST['descripcion'] ?? ''),
                'categoria_id'  => intval($_POST['categoria_id'] ?? 0),
                'precio_venta'  => floatval($_POST['precio_venta'] ?? 0),
                'isv1'          => intval($_POST['isv1'] ?? 0),
                'isv2'          => intval($_POST['isv2'] ?? 0),
                'restaurante'   => intval($_POST['restaurante'] ?? 0), // opcional
            ];
            echo json_encode($m->guardarProductoBasico($payload));
            break;

        case 'updateProductoBasico':
            // Para FormData, los datos vienen en $_POST
            $payload = [
                'productos_id'  => intval($_POST['productos_id'] ?? $_POST['producto_id'] ?? 0),
                'nombre'        => (string)($_POST['nombre'] ?? ''),
                'descripcion'   => (string)($_POST['descripcion'] ?? ''),
                'categoria_id'  => intval($_POST['categoria_id'] ?? 0),
                'precio_venta'  => floatval($_POST['precio_venta'] ?? 0),
                'isv1'          => intval($_POST['isv1'] ?? 0),
                'isv2'          => intval($_POST['isv2'] ?? 0),
                'restaurante'   => intval($_POST['restaurante'] ?? 0), // opcional
            ];
            echo json_encode($m->actualizarProductoBasico($payload));
            break;

        case 'saveClienteBasico':
            if (!$payload) {
                $payload = [
                    'nombre'           => (string)$in('nombre',''),
                    'rtn'              => (string)$in('rtn',''),
                    'fecha'            => (string)$in('fecha', date('Y-m-d')),
                    'departamentos_id' => intval($in('departamentos_id',0)),
                    'municipios_id'    => intval($in('municipios_id',0)),
                    'localidad'        => (string)$in('localidad',''),
                    'telefono'         => (string)$in('telefono',''),
                    'correo'           => (string)$in('correo',''),
                    'estado'           => intval($in('estado',1)),
                ];
            }
            echo json_encode($m->guardarClienteBasico($payload));
            break;

        case 'updateClienteBasico':
            if ((!$payload || !isset($payload['clientes_id'])) && !isset($_POST['clientes_id'])) {
                http_response_code(400);
                echo json_encode(['status'=>false,'message'=>'Datos de cliente inválidos']);
                break;
            }
            if (!$payload) {
                $payload = [
                    'clientes_id'      => intval($in('clientes_id',0)),
                    'nombre'           => (string)$in('nombre',''),
                    'rtn'              => (string)$in('rtn',''),
                    'fecha'            => (string)$in('fecha', date('Y-m-d')),
                    'departamentos_id' => intval($in('departamentos_id',0)),
                    'municipios_id'    => intval($in('municipios_id',0)),
                    'localidad'        => (string)$in('localidad',''),
                    'telefono'         => (string)$in('telefono',''),
                    'correo'           => (string)$in('correo',''),
                    'estado'           => intval($in('estado',1)),
                    'empresa'          => (string)$in('empresa',''),
                    'eslogan'          => (string)$in('eslogan',''),
                    'otra_informacion' => (string)$in('otra_informacion',''),
                    'whatsapp'         => (string)$in('whatsapp',''),
                ];
            }
            echo json_encode($m->actualizarClienteBasico($payload));
            break;

        /* ============================================================
         * ====================  PROMOCIONES  =========================
         * ============================================================ */

        // Listado mínimo para selects {promo_id, nombre}
        case 'loadPromocionesMin':
            echo json_encode([
                'status'=>true,
                'promociones'=>$m->obtenerPromocionesMin() // Implementar en modelo
            ]);
            break;

        // Crear promoción
        case 'savePromocion': {
            // admite JSON o form
            $empresa_id = intval($in('empresa_id', 0)) ?: (isset($_SESSION['empresa_id']) ? intval($_SESSION['empresa_id']) : 0);
            $nombre     = trim((string)$in('nombre',''));
            if ($nombre===''){
                echo json_encode(['status'=>false,'message'=>'El nombre es obligatorio']); break;
            }
            $tipo_descuento = $normTipoDesc($in('tipo_descuento','PORC'));
            $valor          = floatval($in('valor',0));
            $fecha_inicio   = $onlyDate($in('fecha_inicio',null));
            $fecha_fin      = $onlyDate($in('fecha_fin',null));
            if(!$fecha_inicio || !$fecha_fin){
                echo json_encode(['status'=>false,'message'=>'Fecha inicio y fin son obligatorias']); break;
            }
            $hora_inicio    = $onlyTime($in('hora_inicio',null));
            $hora_fin       = $onlyTime($in('hora_fin',null));
            $dias_semana    = $normDiasSemana($in('dias_semana',null));
            $prioridad      = intval($in('prioridad',0));
            $aplica_a       = $normAplicaA($in('aplica_a','PRODUCTO'));
            $acumula        = $toBool($in('acumula_con_mayoreo',0));
            $estado         = $toBool($in('estado',1));
            $descripcion    = (string)$in('descripcion', '');

            $data = compact(
                'empresa_id','nombre','descripcion','tipo_descuento','valor',
                'fecha_inicio','fecha_fin','hora_inicio','hora_fin','dias_semana',
                'prioridad','aplica_a','acumula','estado'
            );
            // Mapea clave del modelo si usa "acumula_con_mayoreo"
            $data['acumula_con_mayoreo'] = $data['acumula']; unset($data['acumula']);

            echo json_encode($m->guardarPromocion($data)); // Implementar en modelo
            break;
        }

        // Actualizar promoción
        case 'updatePromocion': {
            $promo_id = intval($in('promo_id',0));
            if ($promo_id<=0){ echo json_encode(['status'=>false,'message'=>'Promoción inválida']); break; }

            $fields = [];
            if (($v=$in('empresa_id', null))!==null)  $fields['empresa_id']=intval($v);
            if (($v=$in('nombre', null))!==null)      $fields['nombre']=trim((string)$v);
            if (($v=$in('descripcion', null))!==null) $fields['descripcion']=(string)$v;
            if (($v=$in('tipo_descuento', null))!==null) $fields['tipo_descuento']=$normTipoDesc($v);
            if (($v=$in('valor', null))!==null)       $fields['valor']=floatval($v);
            if (($v=$in('fecha_inicio', null))!==null) $fields['fecha_inicio']=$onlyDate($v);
            if (($v=$in('fecha_fin', null))!==null)     $fields['fecha_fin']=$onlyDate($v);
            if (($v=$in('hora_inicio', null))!==null)   $fields['hora_inicio']=$onlyTime($v);
            if (($v=$in('hora_fin', null))!==null)      $fields['hora_fin']=$onlyTime($v);
            if (($v=$in('dias_semana', null))!==null)   $fields['dias_semana']=$normDiasSemana($v);
            if (($v=$in('prioridad', null))!==null)     $fields['prioridad']=intval($v);
            if (($v=$in('aplica_a', null))!==null)      $fields['aplica_a']=$normAplicaA($v);
            if (($v=$in('acumula_con_mayoreo', null))!==null) $fields['acumula_con_mayoreo']=$toBool($v);
            if (($v=$in('estado', null))!==null)        $fields['estado']=$toBool($v);

            echo json_encode($m->actualizarPromocion($promo_id, $fields)); // Implementar en modelo
            break;
        }

        // Productos asignados a una promo
        case 'loadPromoProductos': {
            $promo_id = intval($in('promo_id',0));
            if ($promo_id<=0){ echo json_encode(['status'=>false,'message'=>'Promoción inválida']); break; }
            echo json_encode([
                'status'=>true,
                'items'=>$m->obtenerProductosDePromo($promo_id) // Implementar en modelo
            ]);
            break;
        }

        // Asignar productos a una promo (multi)
        case 'assignPromoProductos': {
            $promo_id = intval($in('promo_id',0));
            $productos_ids = $in('productos_ids', []);
            if ($promo_id<=0){ echo json_encode(['status'=>false,'message'=>'Promoción inválida']); break; }
            if (!is_array($productos_ids) || !count($productos_ids)){
                echo json_encode(['status'=>false,'message'=>'Seleccione al menos un producto']); break;
            }
            $productos_ids = array_values(array_filter(array_map('intval', $productos_ids)));
            echo json_encode($m->asignarProductosAPromo($promo_id, $productos_ids)); // Implementar en modelo
            break;
        }

        // Quitar 1 producto de la promo
        case 'removePromoProducto': {
            $promo_id    = intval($in('promo_id',0));
            $producto_id = intval($in('producto_id',0));
            if ($promo_id<=0 || $producto_id<=0){
                echo json_encode(['status'=>false,'message'=>'Datos inválidos']); break;
            }
            echo json_encode($m->quitarProductoDePromo($promo_id, $producto_id)); // Implementar en modelo
            break;
        }

        // Categorías asignadas a una promo
        case 'loadPromoCategorias': {
            $promo_id = intval($in('promo_id',0));
            if ($promo_id<=0){ echo json_encode(['status'=>false,'message'=>'Promoción inválida']); break; }
            echo json_encode([
                'status'=>true,
                'items'=>$m->obtenerCategoriasDePromo($promo_id) // Implementar en modelo
            ]);
            break;
        }

        // Asignar categorías a una promo (multi)
        case 'assignPromoCategorias': {
            $promo_id = intval($in('promo_id',0));
            $categorias_ids = $in('categorias_ids', []);
            if ($promo_id<=0){ echo json_encode(['status'=>false,'message'=>'Promoción inválida']); break; }
            if (!is_array($categorias_ids) || !count($categorias_ids)){
                echo json_encode(['status'=>false,'message'=>'Seleccione al menos una categoría']); break;
            }
            $categorias_ids = array_values(array_filter(array_map('intval', $categorias_ids)));
            echo json_encode($m->asignarCategoriasAPromo($promo_id, $categorias_ids)); // Implementar en modelo
            break;
        }

        // Quitar 1 categoría de la promo
        case 'removePromoCategoria': {
            $promo_id     = intval($in('promo_id',0));
            $categoria_id = intval($in('categoria_id',0));
            if ($promo_id<=0 || $categoria_id<=0){
                echo json_encode(['status'=>false,'message'=>'Datos inválidos']); break;
            }
            echo json_encode($m->quitarCategoriaDePromo($promo_id, $categoria_id)); // Implementar en modelo
            break;
        }

        /* ============================================================
         * ======= FACTURA / COMANDA =================================
         * ============================================================ */
        case 'loadFacturaMesa':
            $mesa_id = intval($in('mesa_id', 0));
            if ($mesa_id <= 0) {
                echo json_encode(['status' => false, 'message' => 'Mesa inválida']);
                break;
            }
        
            $factura = $m->obtenerFacturaMesa($mesa_id);
        
            if (!$factura) {
                echo json_encode([
                    'status'  => false,
                    'message' => 'No hay factura activa para esta mesa',
                    // opcional: devuelve la mesa (si tienes método). Si no, quita este bloque.
                    // 'mesa' => $m->obtenerMesa($mesa_id) ?: null,
                    'items'   => []
                ]);
                break;
            }
        
            echo json_encode([
                'status' => true,
                'factura' => $factura,
                'mesa' => [
                    'id'        => intval($factura['mesa_id']),
                    'numero'    => $factura['numero_mesa'],
                    'capacidad' => intval($factura['capacidad_mesa']),
                    'ubicacion' => $factura['ubicacion_mesa'],
                    'estado'    => 'ocupada'
                ],
                'items' => $m->obtenerDetallesFactura(intval($factura['facturas_id']))
            ]);
            break;        

        // === GUARDAR FACTURA (NUEVA) ===
        case 'saveFactura': {
            // El JS te manda JSON: { action:'saveFactura', data:{...} }
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!$payload) { // fallback por si viniera como x-www-form-urlencoded
                $payload = ['data' => $_POST];
            }
            $data = $payload['data'] ?? [];

            // Normalizamos extras de restaurante
            //  - servicio_tipo: 'mesa' | 'llevar'  (default: 'llevar')
            //  - mesa_id: entero (0 si no hay mesa)
            $servicioTipo = (isset($data['servicio_tipo']) && $data['servicio_tipo'] === 'mesa') ? 'mesa' : 'llevar';
            $mesaId       = !empty($data['mesa_id']) ? (int)$data['mesa_id'] : 0;

            // Pasamos TODO el payload original al modelo,
            // y además mesa/servicio para la factura_comanda
            $resp = $m->guardarFactura($data, $mesaId, $servicioTipo);
            echo json_encode($resp);
            exit;
        }

        // === EDITAR/ACTUALIZAR FACTURA EXISTENTE ===
        case 'updateFactura': {
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!$payload) {
                $payload = ['data' => $_POST];
            }
            $data = $payload['data'] ?? [];

            $servicioTipo = (isset($data['servicio_tipo']) && $data['servicio_tipo'] === 'mesa') ? 'mesa' : 'llevar';
            $mesaId       = !empty($data['mesa_id']) ? (int)$data['mesa_id'] : 0;

            $resp = $m->actualizarFactura($data, $mesaId, $servicioTipo);
            echo json_encode($resp);
            exit;
        }

        case 'closeFactura':
            $factura_id = intval($in('factura_id', 0));
            echo json_encode($m->cerrarFactura($factura_id));
            break;

        /* ============================================================
         * ======= COCINA =============================================
         * ============================================================ */

        case 'loadComandasCocina':
            // opcionalmente puedes filtrar por estado: pendiente|en_preparacion|preparada|urgente|completada
            $estado = $in('estado', null);
            echo json_encode([
                'status'   => true,
                'comandas' => $m->obtenerComandasCocina($estado)
            ]);
            break;

        case 'enviarComandaCocina':
            $data = $payload ?? [
                'factura_id'    => intval($in('factura_id',0)),
                'mesa'          => (string)$in('mesa',''),
                'observaciones' => (string)$in('observaciones',''),
                'items'         => json_decode((string)$in('items','[]'), true),
            ];
            echo json_encode($m->enviarComandaCocina($data));
            break;

        case 'marcarComandaPreparada':
            // Acepta comanda_id o factura_id (compatibilidad con diferentes llamadas del front)
            $fid = intval($in('factura_id', 0));
            if ($fid<=0) { $fid = intval($in('comanda_id', 0)); }
            echo json_encode($m->marcarComandaPreparada($fid));
            break;

        case 'marcarComandaUrgente':
            $urg = $in('urgente', false);
            $urgente = is_bool($urg) ? $urg : (in_array(strtolower((string)$urg), ['1','true','sí','si','on','yes'], true));
            echo json_encode($m->marcarComandaUrgente(intval($in('factura_id', 0)), $urgente));
            break;

        case 'marcarComandaEnPreparacion':
            echo json_encode($m->marcarComandaEnPreparacion(intval($in('factura_id', 0))));
            break;

        /* ============================================================
         * ======= COMBOS (MAESTRO / DETALLE / REGLAS) ================
         * ============================================================ */
        case 'loadCombos':
            echo json_encode(['status'=>true,'combos'=>$m->obtenerCombos()]);
            break;

        case 'loadComboDetalle':
            $combo_id = intval($in('combo_id', 0));
            if ($combo_id<=0) {
                echo json_encode(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            $detalle = $m->obtenerComboDetalle($combo_id); // hijos (obligatorio/opcional, cantidades, merma, unidad, precio_extra)
            echo json_encode(['status'=>true,'combo_detalle'=>$detalle]);
            break;

        // NUEVO: cargar reglas por categoría (max_seleccion) del combo
        case 'loadComboCategoriaReglas':
            $combo_id = intval($in('combo_id', 0));
            if ($combo_id<=0) {
                echo json_encode(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            echo json_encode(['status'=>true,'reglas'=>$m->obtenerComboCategoriaReglas($combo_id)]);
            break;

        // Guardar combo + receta + reglas por categoría
        case 'saveCombo':
            if (!$payload && !isset($_POST['productos_id'])) {
                http_response_code(400);
                echo json_encode(['status'=>false,'message'=>'Datos de combo inválidos']);
                break;
            }
            if (!$payload) {
                $payload = [
                    'productos_id' => intval($in('productos_id',0)),       // producto PADRE
                    'activo'       => intval($in('activo',1)) ? 1 : 0,
                    'precio_venta' => ($in('precio_venta', null) === null ? null : floatval($in('precio_venta',0))), // NULL o decimal
                    'version'      => intval($in('version',1)),           // opcional
                    // items: [{productos_id, cantidad_por_porcion, unidad, merma_pct, obligatorio, precio_extra, orden}]
                    'items'        => json_decode((string)$in('items','[]'), true),
                    // reglas: [{categoria_id, max_seleccion}]
                    'reglas'       => json_decode((string)$in('reglas','[]'), true),
                ];
            }
            echo json_encode($m->guardarCombo($payload));
            break;

        // Actualizar combo (parcial o total)
        case 'updateCombo':
            if ((!$payload || !isset($payload['combo_id'])) && !isset($_POST['combo_id'])) {
                http_response_code(400);
                echo json_encode(['status'=>false,'message'=>'Datos de combo inválidos']);
                break;
            }
            if (!$payload) {
                $payload = [
                    'combo_id'     => intval($in('combo_id',0)),
                    'productos_id' => ($in('productos_id', null) !== null) ? intval($in('productos_id',0)) : null,
                    'activo'       => (isset($_POST['activo']) || (is_array($payload) && array_key_exists('activo', $payload)))
                                      ? (intval($in('activo',1)) ? 1 : 0) : null,
                    'precio_venta' => ($in('precio_venta', '__omit__') === '__omit__') ? '__omit__'
                                      : (($in('precio_venta', null) === null) ? null : floatval($in('precio_venta',0))),
                    'version'      => ($in('version', null) !== null) ? intval($in('version',1)) : null,
                    // Si se envía items/reglas, se reemplaza la receta/reglas actuales (transacción en el modelo)
                    'items'        => (isset($_POST['items']) ? json_decode((string)$in('items','[]'), true) : ($payload['items'] ?? null)),
                    'reglas'       => (isset($_POST['reglas']) ? json_decode((string)$in('reglas','[]'), true) : ($payload['reglas'] ?? null)),
                ];
            }
            echo json_encode($m->actualizarCombo($payload));
            break;

        case 'deleteCombo':
            $combo_id = intval($in('combo_id', 0));
            if ($combo_id<=0) {
                echo json_encode(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            echo json_encode($m->eliminarCombo($combo_id));
            break;

        // NUEVO: calcular disponibilidad del combo según inventario de hijos obligatorios
        case 'calcComboDisponibilidad':
            $combo_id  = intval($in('combo_id', 0));
            $cantidad  = max(1, intval($in('cantidad', 1))); // opcional: para validar si hay stock para N combos
            if ($combo_id<=0) {
                echo json_encode(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            echo json_encode($m->calcularDisponibilidadCombo($combo_id, $cantidad));
            break;

        /* ============================================================
         * ======= OBTENER PROMOCIONES VIGENTES PARA PRODUCTOS ========
         * ============================================================ */
        case 'loadPromocionesVigentesProductos':
            echo json_encode(['status'=>true,'promociones'=>$m->obtenerPromocionesVigentesProductos()]);
            break;            

        default:
            http_response_code(400);
            echo json_encode(['status'=>false,'message'=>'Acción no válida']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
}
