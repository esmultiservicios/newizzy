<?php
// core/facturasRestaurante/facturasRestauranteAjax.php
declare(strict_types=1);

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/facturasRestauranteModelo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ============================
 *  Helpers de Entrada / Normalización
 * ============================ */
final class AjaxHelper
{
    /** @var array|null */
    public static $payload = null;

    /** Lee del POST o del payload JSON */
    public static function in(string $key, $default = null) {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (is_array(self::$payload) && array_key_exists($key, self::$payload)) {
            return self::$payload[$key];
        }
        return $default;
    }

    /** Convierte a 0/1 según truthy “humano” */
    public static function toBool($v, int $default = 0): int {
        if ($v === null) return $default ? 1 : 0;
        if (is_bool($v)) return $v ? 1 : 0;
        $s = strtolower((string)$v);
        return in_array($s, ['1','true','sí','si','on','yes'], true) ? 1 : 0;
    }

    /** Devuelve “HH:MM:SS” o null */
    public static function onlyTime($t): ?string {
        if ($t === null || $t === '') return null;
        $s = (string)$t;
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s)) {
            return (strlen($s) === 5) ? $s . ':00' : $s;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T](\d{2}:\d{2}(:\d{2})?)$/', $s, $m)) {
            return (strlen($m[1]) === 5) ? $m[1] . ':00' : $m[1];
        }
        return null;
    }

    /** Devuelve “YYYY-MM-DD” o null */
    public static function onlyDate($d): ?string {
        if ($d === null || $d === '') return null;
        $s = (string)$d;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}(:\d{2})?$/', $s, $m)) return $m[1];
        return null;
    }

    /** Normaliza csv de días (mon,tue,...) */
    public static function normDiasSemana($csv): ?string {
        if ($csv === null || $csv === '') return null;
        $allow = ['mon','tue','wed','thu','fri','sat','sun'];
        $parts = array_filter(array_map('trim', explode(',', strtolower((string)$csv))));
        $parts = array_values(array_unique(array_intersect($parts, $allow)));
        return count($parts) ? implode(',', $parts) : null;
    }

    /** PORC|MONTO */
    public static function normTipoDesc($v): string {
        $v = strtoupper(trim((string)$v));
        return in_array($v, ['PORC','MONTO'], true) ? $v : 'PORC';
    }

    /** PRODUCTO|CATEGORIA|TODOS */
    public static function normAplicaA($v): string {
        $v = strtoupper(trim((string)$v));
        return in_array($v, ['PRODUCTO','CATEGORIA','TODOS'], true) ? $v : 'PRODUCTO';
    }

    /** Lee body crudo y configura payload + action */
    public static function bootstrapPayloadAndAction(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        self::$payload = (is_array($json) && isset($json['data']) && is_array($json['data'])) ? $json['data'] : ($json ?? null);

        $action = $_POST['action'] ?? null;
        if (!$action && is_array($json) && isset($json['action'])) {
            $action = $json['action'];
        }
        return [$raw, $json, $action];
    }

    /** Respuesta JSON estándar */
    public static function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    /** Forzar método POST */
    public static function assertPost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            self::json(['status' => false, 'message' => 'Método no permitido'], 405);
            exit;
        }
    }
}

/* --- Protección de sesión para AJAX --- */
$validacion = mainModel::validarSesion(); // ['error'=>bool,'mensaje'=>..., 'redireccion'=>...]
if (!empty($validacion['error'])) {
    AjaxHelper::json([
        'status'   => false,
        'message'  => $validacion['mensaje'] ?? 'Sesión expirada',
        'redirect' => $validacion['redireccion'] ?? ((defined('SERVERURL') ? SERVERURL : '/') . 'login/')
    ], 401);
    exit;
}

try {
    AjaxHelper::assertPost();

    // Cargar payload y acción
    [, $asJson, $action] = AjaxHelper::bootstrapPayloadAndAction();

    if (!$action) {
        AjaxHelper::json(['status' => false, 'message' => 'Acción no especificada'], 400);
        exit;
    }

    $m = new facturasRestauranteModelo();

    switch ($action) {
        /* ============================================================
         * ======= DATA CATÁLOGO / ISV / MESAS ========================
         * ============================================================ */
        case 'loadISV':
            AjaxHelper::json(['status' => true, 'isv' => $m->obtenerISVTiposPublico()]);
            break;

        case 'loadMesas':
            AjaxHelper::json(['status' => true, 'mesas' => $m->obtenerMesas()]);
            break;

        case 'saveMesa': {
            $numero    = (string) AjaxHelper::in('numero', '');
            $capacidad = (int) AjaxHelper::in('capacidad', 4);
            $ubicacion = (string) AjaxHelper::in('ubicacion', 'Interior');
            AjaxHelper::json($m->guardarMesa($numero, $capacidad, $ubicacion));
            break;
        }

        case 'updateMesa': {
            $mesa_id   = (int) AjaxHelper::in('mesa_id', 0);
            $numero    = (string) AjaxHelper::in('numero', '');
            $capacidad = (int) AjaxHelper::in('capacidad', 4);
            $ubicacion = (string) AjaxHelper::in('ubicacion', 'Interior');
            $estado = (string) AjaxHelper::in('estado', '');
            AjaxHelper::json($m->actualizarMesa($mesa_id, $numero, $capacidad, $ubicacion, $estado));
            break;
        }

        case 'reservarMesa': {
            $data = [
                'mesa_id'       => (int) AjaxHelper::in('mesa_id',0),
                'clientes_id'   => (int) AjaxHelper::in('clientes_id',0),
                'fecha_reserva' => (string) AjaxHelper::in('fecha_reserva', date('Y-m-d')),
                'hora_reserva'  => (string) AjaxHelper::in('hora_reserva', date('H:i:s')),
                'personas'      => (int) AjaxHelper::in('personas',1),
                'notas'         => (string) AjaxHelper::in('notas',''),
            ];
            AjaxHelper::json($m->reservarMesa($data));
            break;
        }

        case 'cancelarReservaMesa': {
            AjaxHelper::json($m->cancelarReservaMesa((int)AjaxHelper::in('mesa_id',0)));
            break;
        }

        case 'loadCategorias': {
            $estacion = strtolower((string) AjaxHelper::in('estacion', 'todas'));
            if (!in_array($estacion, ['cocina','barra','todas'], true)) $estacion = 'todas';
            AjaxHelper::json(['status' => true, 'categorias' => $m->obtenerCategoriasProductos($estacion)]);
            break;
        }

        case 'loadProductos':
            AjaxHelper::json(['status' => true, 'productos' => $m->obtenerProductos()]);
            break;

        case 'loadClientes':
            AjaxHelper::json(['status' => true, 'clientes' => $m->obtenerClientes()]);
            break;

        /* ============================================================
         * ======= GUARDAR / ACTUALIZAR CAT/PROD/CLI =================
         * ============================================================ */
        case 'saveCategoria': {
            $nombre   = trim((string) AjaxHelper::in('nombre', ''));
            $estacion = strtolower(trim((string) AjaxHelper::in('estacion', '')));
        
            if ($nombre === '') {
                AjaxHelper::json(['status'=>false,'message'=>'Nombre requerido']); break;
            }
            if (!in_array($estacion, ['cocina','barra'], true)) {
                AjaxHelper::json(['status'=>false,'message'=>'Seleccione estación (cocina o barra)']); break;
            }
        
            AjaxHelper::json($m->guardarCategoria($nombre, $estacion));
            break;
        }
        
        case 'updateCategoria': {
            $categoria_id = (int) AjaxHelper::in('categoria_id', 0);
            $nombre       = trim((string) AjaxHelper::in('nombre', ''));
            $estacion     = strtolower(trim((string) AjaxHelper::in('estacion', '')));
        
            if ($categoria_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'ID de categoría inválido']); break;
            }
            if ($nombre === '') {
                AjaxHelper::json(['status'=>false,'message'=>'Nombre requerido']); break;
            }
            if (!in_array($estacion, ['cocina','barra'], true)) {
                AjaxHelper::json(['status'=>false,'message'=>'Seleccione estación (cocina o barra)']); break;
            }
        
            AjaxHelper::json($m->actualizarCategoria($categoria_id, $nombre, $estacion));
            break;
        }        

        case 'saveProductoBasico': {
            $payload = AjaxHelper::$payload;
            if (!$payload || !is_array($payload)) {
                $payload = [
                    'nombre'       => (string) AjaxHelper::in('nombre',''),
                    'descripcion'  => (string) AjaxHelper::in('descripcion',''),
                    'categoria_id' => (int) AjaxHelper::in('categoria_id',0),
                    'precio_venta' => (float) AjaxHelper::in('precio_venta',0),
                    'isv1'         => (int) AjaxHelper::in('isv1',0),
                    'isv2'         => (int) AjaxHelper::in('isv2',0),
                    'restaurante'  => (int) AjaxHelper::in('restaurante',1),
                    'estacion'     => (string) AjaxHelper::in('estacion',''),
                ];
            }
            AjaxHelper::json($m->guardarProductoBasico($payload));
            break;
        }

        case 'updateProductoBasico': {
            $payload = AjaxHelper::$payload;
            if (!$payload || !is_array($payload)) {
                $payload = [
                    'productos_id' => (int) AjaxHelper::in('productos_id', (int) AjaxHelper::in('producto_id', 0)),
                    'nombre'       => (string) AjaxHelper::in('nombre',''),
                    'descripcion'  => (string) AjaxHelper::in('descripcion',''),
                    'categoria_id' => (int) AjaxHelper::in('categoria_id',0),
                    'precio_venta' => (float) AjaxHelper::in('precio_venta',0),
                    'isv1'         => (int) AjaxHelper::in('isv1',0),
                    'isv2'         => (int) AjaxHelper::in('isv2',0),
                    'restaurante'  => (int) AjaxHelper::in('restaurante',1),
                    'estacion'     => (string) AjaxHelper::in('estacion',''),
                ];
            }
            AjaxHelper::json($m->actualizarProductoBasico($payload));
            break;
        }

        case 'saveClienteBasico': {
            $payload = AjaxHelper::$payload;
            if (!$payload || !is_array($payload)) {
                $payload = [
                    'nombre'           => (string) AjaxHelper::in('nombre',''),
                    'rtn'              => (string) AjaxHelper::in('rtn',''),
                    'fecha'            => (string) AjaxHelper::in('fecha', date('Y-m-d')),
                    'departamentos_id' => (int) AjaxHelper::in('departamentos_id',0),
                    'municipios_id'    => (int) AjaxHelper::in('municipios_id',0),
                    'localidad'        => (string) AjaxHelper::in('localidad',''),
                    'telefono'         => (string) AjaxHelper::in('telefono',''),
                    'correo'           => (string) AjaxHelper::in('correo',''),
                    'estado'           => (int) AjaxHelper::in('estado',1),
                ];
            }
            AjaxHelper::json($m->guardarClienteBasico($payload));
            break;
        }

        case 'updateClienteBasico': {
            $payload = AjaxHelper::$payload;
            $cliIdFromPost = AjaxHelper::in('clientes_id', null);
            if ((!$payload || !isset($payload['clientes_id'])) && $cliIdFromPost === null) {
                AjaxHelper::json(['status'=>false,'message'=>'Datos de cliente inválidos'], 400);
                break;
            }
            if (!$payload || !is_array($payload)) {
                $payload = [
                    'clientes_id'      => (int) AjaxHelper::in('clientes_id',0),
                    'nombre'           => (string) AjaxHelper::in('nombre',''),
                    'rtn'              => (string) AjaxHelper::in('rtn',''),
                    'fecha'            => (string) AjaxHelper::in('fecha', date('Y-m-d')),
                    'departamentos_id' => (int) AjaxHelper::in('departamentos_id',0),
                    'municipios_id'    => (int) AjaxHelper::in('municipios_id',0),
                    'localidad'        => (string) AjaxHelper::in('localidad',''),
                    'telefono'         => (string) AjaxHelper::in('telefono',''),
                    'correo'           => (string) AjaxHelper::in('correo',''),
                    'estado'           => (int) AjaxHelper::in('estado',1),
                    'empresa'          => (string) AjaxHelper::in('empresa',''),
                    'eslogan'          => (string) AjaxHelper::in('eslogan',''),
                    'otra_informacion' => (string) AjaxHelper::in('otra_informacion',''),
                    'whatsapp'         => (string) AjaxHelper::in('whatsapp',''),
                ];
            }
            AjaxHelper::json($m->actualizarClienteBasico($payload));
            break;
        }

        /* ============================================================
         * ====================  PROMOCIONES  =========================
         * ============================================================ */
        case 'loadPromociones':
            AjaxHelper::json(['status'=>true,'promociones'=>$m->obtenerPromociones()]);
            break;

        case 'loadPromocionesMin':
            AjaxHelper::json(['status'=>true,'promociones'=>$m->obtenerPromocionesMin()]);
            break;

        case 'savePromocion': {
            $empresa_id     = (int) AjaxHelper::in('empresa_id', 0);
            if ($empresa_id === 0 && isset($_SESSION['empresa_id'])) {
                $empresa_id = (int) $_SESSION['empresa_id'];
            }
            $nombre         = trim((string) AjaxHelper::in('nombre',''));
            if ($nombre === '') {
                AjaxHelper::json(['status'=>false,'message'=>'El nombre es obligatorio']);
                break;
            }
            $tipo_descuento = AjaxHelper::normTipoDesc(AjaxHelper::in('tipo_descuento','PORC'));
            $valor          = (float) AjaxHelper::in('valor',0);
            $fecha_inicio   = AjaxHelper::onlyDate(AjaxHelper::in('fecha_inicio',null));
            $fecha_fin      = AjaxHelper::onlyDate(AjaxHelper::in('fecha_fin',null));
            if (!$fecha_inicio || !$fecha_fin) {
                AjaxHelper::json(['status'=>false,'message'=>'Fecha inicio y fin son obligatorias']);
                break;
            }
            $hora_inicio    = AjaxHelper::onlyTime(AjaxHelper::in('hora_inicio',null));
            $hora_fin       = AjaxHelper::onlyTime(AjaxHelper::in('hora_fin',null));
            $dias_semana    = AjaxHelper::normDiasSemana(AjaxHelper::in('dias_semana',null));
            $prioridad      = (int) AjaxHelper::in('prioridad',0);
            $aplica_a       = AjaxHelper::normAplicaA(AjaxHelper::in('aplica_a','PRODUCTO'));
            $acumula        = AjaxHelper::toBool(AjaxHelper::in('acumula_con_mayoreo',0));
            $estado         = AjaxHelper::toBool(AjaxHelper::in('estado',1));
            $descripcion    = (string) AjaxHelper::in('descripcion', '');

            $data = [
                'empresa_id' => $empresa_id,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tipo_descuento' => $tipo_descuento,
                'valor' => $valor,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'dias_semana' => $dias_semana,
                'prioridad' => $prioridad,
                'aplica_a' => $aplica_a,
                'acumula_con_mayoreo' => $acumula,
                'estado' => $estado
            ];

            AjaxHelper::json($m->guardarPromocion($data));
            break;
        }

        case 'updatePromocion': {
            $promo_id = (int) AjaxHelper::in('promo_id',0);
            if ($promo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Promoción inválida']);
                break;
            }

            $fields = [];
            $v = AjaxHelper::in('empresa_id', null);        if ($v !== null) $fields['empresa_id'] = (int)$v;
            $v = AjaxHelper::in('nombre', null);            if ($v !== null) $fields['nombre'] = trim((string)$v);
            $v = AjaxHelper::in('descripcion', null);       if ($v !== null) $fields['descripcion'] = (string)$v;
            $v = AjaxHelper::in('tipo_descuento', null);    if ($v !== null) $fields['tipo_descuento'] = AjaxHelper::normTipoDesc($v);
            $v = AjaxHelper::in('valor', null);             if ($v !== null) $fields['valor'] = (float)$v;
            $v = AjaxHelper::in('fecha_inicio', null);      if ($v !== null) $fields['fecha_inicio'] = AjaxHelper::onlyDate($v);
            $v = AjaxHelper::in('fecha_fin', null);         if ($v !== null) $fields['fecha_fin'] = AjaxHelper::onlyDate($v);
            $v = AjaxHelper::in('hora_inicio', null);       if ($v !== null) $fields['hora_inicio'] = AjaxHelper::onlyTime($v);
            $v = AjaxHelper::in('hora_fin', null);          if ($v !== null) $fields['hora_fin'] = AjaxHelper::onlyTime($v);
            $v = AjaxHelper::in('dias_semana', null);       if ($v !== null) $fields['dias_semana'] = AjaxHelper::normDiasSemana($v);
            $v = AjaxHelper::in('prioridad', null);         if ($v !== null) $fields['prioridad'] = (int)$v;
            $v = AjaxHelper::in('aplica_a', null);          if ($v !== null) $fields['aplica_a'] = AjaxHelper::normAplicaA($v);
            $v = AjaxHelper::in('acumula_con_mayoreo', null); if ($v !== null) $fields['acumula_con_mayoreo'] = AjaxHelper::toBool($v);
            $v = AjaxHelper::in('estado', null);            if ($v !== null) $fields['estado'] = AjaxHelper::toBool($v);

            AjaxHelper::json($m->actualizarPromocion($promo_id, $fields));
            break;
        }

        case 'loadPromoProductos': {
            $promo_id = (int) AjaxHelper::in('promo_id',0);
            if ($promo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Promoción inválida']);
                break;
            }
            AjaxHelper::json(['status'=>true,'items'=>$m->obtenerProductosDePromo($promo_id)]);
            break;
        }

        case 'assignPromoProductos': {
            $promo_id = (int) AjaxHelper::in('promo_id',0);
            $productos_ids = AjaxHelper::in('productos_ids', []);
            if ($promo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Promoción inválida']);
                break;
            }
            if (!is_array($productos_ids) || !count($productos_ids)) {
                AjaxHelper::json(['status'=>false,'message'=>'Seleccione al menos un producto']);
                break;
            }
            $productos_ids = array_values(array_filter(array_map('intval', $productos_ids)));
            AjaxHelper::json($m->asignarProductosAPromo($promo_id, $productos_ids));
            break;
        }

        case 'removePromoProducto': {
            $promo_id    = (int) AjaxHelper::in('promo_id',0);
            $producto_id = (int) AjaxHelper::in('producto_id',0);
            if ($promo_id <= 0 || $producto_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Datos inválidos']);
                break;
            }
            AjaxHelper::json($m->quitarProductoDePromo($promo_id, $producto_id));
            break;
        }

        case 'loadPromoCategorias': {
            $promo_id = (int) AjaxHelper::in('promo_id',0);
            if ($promo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Promoción inválida']);
                break;
            }
            AjaxHelper::json(['status'=>true,'items'=>$m->obtenerCategoriasDePromo($promo_id)]);
            break;
        }

        case 'assignPromoCategorias': {
            $promo_id = (int) AjaxHelper::in('promo_id',0);
            $categorias_ids = AjaxHelper::in('categorias_ids', []);
           	if ($promo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Promoción inválida']);
                break;
            }
            if (!is_array($categorias_ids) || !count($categorias_ids)) {
                AjaxHelper::json(['status'=>false,'message'=>'Seleccione al menos una categoría']);
                break;
            }
            $categorias_ids = array_values(array_filter(array_map('intval', $categorias_ids)));
            AjaxHelper::json($m->asignarCategoriasAPromo($promo_id, $categorias_ids));
            break;
        }

        case 'removePromoCategoria': {
            $promo_id     = (int) AjaxHelper::in('promo_id',0);
            $categoria_id = (int) AjaxHelper::in('categoria_id',0);
            if ($promo_id <= 0 || $categoria_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Datos inválidos']);
                break;
            }
            AjaxHelper::json($m->quitarCategoriaDePromo($promo_id, $categoria_id));
            break;
        }

        case 'loadConfiguracionOperacion':
            AjaxHelper::json(['status'=>true,'config'=>$m->obtenerConfiguracionOperacion()]);
            break;

        case 'saveConfiguracionOperacion': {
            $usarMesas = AjaxHelper::toBool(AjaxHelper::in('usar_mesas',1),1);
            $usarComandas = AjaxHelper::toBool(AjaxHelper::in('usar_comandas',1),1);
            $etiquetaCocina = trim((string)AjaxHelper::in('etiqueta_cocina','Cocina'));
            $etiquetaBarra = trim((string)AjaxHelper::in('etiqueta_barra','Barra'));
            $destinoComanda = strtolower(trim((string)AjaxHelper::in('destino_comanda','pantalla')));
            $momentoTicket = strtolower(trim((string)AjaxHelper::in('momento_ticket','enviar')));
            $flujoCocina = strtolower(trim((string)AjaxHelper::in('flujo_cocina','pasos')));
            $solicitarClaveGestion = AjaxHelper::toBool(AjaxHelper::in('solicitar_clave_gestion',1),1);
            $permitirFacturasCredito = AjaxHelper::toBool(AjaxHelper::in('permitir_facturas_credito',0),0);
            AjaxHelper::json($m->guardarConfiguracionOperacion(
                $usarMesas,
                $usarComandas,
                $etiquetaCocina,
                $etiquetaBarra,
                $destinoComanda,
                $momentoTicket,
                $flujoCocina,
                $solicitarClaveGestion,
                $permitirFacturasCredito
            ));
            break;
        }

        case 'loadCuentasAbiertas':
            AjaxHelper::json(['status'=>true,'cuentas'=>$m->obtenerCuentasAbiertas()]);
            break;

        case 'loadCuentaAbierta': {
            $factura_id=(int)AjaxHelper::in('factura_id',0);
            $cuenta=$m->obtenerCuentaAbiertaPorId($factura_id);
            if(!$cuenta){ AjaxHelper::json(['status'=>false,'message'=>'La cuenta ya no está abierta o no existe']); break; }
            AjaxHelper::json(['status'=>true,'cuenta'=>$cuenta]);
            break;
        }

        case 'cerrarCuentaOperativa': {
            $factura_id=(int)AjaxHelper::in('factura_id',0);
            if($factura_id<=0){ AjaxHelper::json(['status'=>false,'message'=>'Cuenta inválida']); break; }
            AjaxHelper::json(['status'=>$m->cerrarContextoCuenta($factura_id)]);
            break;
        }

        /* ============================================================
         * ======= FACTURA / COMANDA =================================
         * ============================================================ */
        case 'loadFacturaMesa': {
            $mesa_id = (int) AjaxHelper::in('mesa_id', 0);
            if ($mesa_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Mesa inválida']);
                break;
            }
            $factura = $m->obtenerFacturaMesa($mesa_id);
            if (!$factura) {
                AjaxHelper::json([
                    'status'  => false,
                    'message' => 'No hay factura activa para esta mesa',
                    'items'   => []
                ]);
                break;
            }
            AjaxHelper::json([
                'status' => true,
                'factura' => $factura,
                'mesa' => [
                    'id'        => (int) $factura['mesa_id'],
                    'numero'    => $factura['numero_mesa'],
                    'capacidad' => (int) $factura['capacidad_mesa'],
                    'ubicacion' => $factura['ubicacion_mesa'],
                    'estado'    => 'ocupada'
                ],
                'items' => $m->obtenerDetallesFactura((int) $factura['facturas_id'])
            ]);
            break;
        }

        case 'saveFactura': {
            // { action:'saveFactura', data:{...} }
            $data = is_array($asJson) ? ($asJson['data'] ?? []) : $_POST;
            $servicioTipo = (isset($data['servicio_tipo']) && $data['servicio_tipo'] === 'mesa') ? 'mesa' : 'llevar';
            $mesaId       = !empty($data['mesa_id']) ? (int)$data['mesa_id'] : 0;
            AjaxHelper::json($m->guardarFactura($data, $mesaId, $servicioTipo));
            break;
        }

        case 'updateFactura': {
            $data = is_array($asJson) ? ($asJson['data'] ?? []) : $_POST;
            $servicioTipo = (isset($data['servicio_tipo']) && $data['servicio_tipo'] === 'mesa') ? 'mesa' : 'llevar';
            $mesaId       = !empty($data['mesa_id']) ? (int)$data['mesa_id'] : 0;
            AjaxHelper::json($m->actualizarFactura($data, $mesaId, $servicioTipo));
            break;
        }

        case 'closeFactura':
            $factura_id = (int) AjaxHelper::in('factura_id', 0);
            AjaxHelper::json($m->cerrarFactura($factura_id));
            break;

        /* =======================
        Cargar comandas SOLO de cocina
        ======================= */
        case 'loadComandasCocina': {
            try {
                $comandas = $m->getComandasPorEstacion('cocina'); // <- SOLO cocina
                $config = $m->obtenerConfiguracionOperacion();
                AjaxHelper::json(['status'=>true, 'comandas'=>$comandas, 'config'=>$config]);
            } catch (Throwable $e) {
                AjaxHelper::json(['status'=>false, 'message'=>$e->getMessage()]);
            }
            break;
        }

        case 'enviarComandaCocina': {
            $data = AjaxHelper::$payload;
            if (!$data || !is_array($data)) {
                $data = [
                    'factura_id'    => (int) AjaxHelper::in('factura_id',0),
                    'mesa'          => (string) AjaxHelper::in('mesa',''),
                    'observaciones' => (string) AjaxHelper::in('observaciones',''),
                    'items'         => json_decode((string) AjaxHelper::in('items','[]'), true),
                ];
            }
            AjaxHelper::json($m->enviarComandaCocina($data));
            break;
        }

        /* =======================
        Marcar COMPLETADA (por comanda_id)
        ======================= */
        case 'marcarComandaPreparada': {
            $comanda_id = (int) AjaxHelper::in('comanda_id', 0);
            if ($comanda_id <= 0) { AjaxHelper::json(['status'=>false,'message'=>'Comanda inválida']); break; }

            $ok = $m->updateComandaEstadoById($comanda_id, 'preparada');
            AjaxHelper::json($ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar']);
            break;
        }

        /* =======================
        Toggle URGENTE (por factura)
        ======================= */
        case 'marcarComandaUrgente': {
            $factura_id = (int) AjaxHelper::in('factura_id', 0);
            $urgente    = (int) AjaxHelper::in('urgente', 0) === 1;
            if ($factura_id <= 0) { AjaxHelper::json(['status'=>false,'message'=>'Factura inválida']); break; }

            $nuevoEstado = $urgente ? 'urgente' : 'pendiente';
            $ok = $m->updateComandaEstadoByFactura($factura_id, $nuevoEstado);
            AjaxHelper::json($ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar']);
            break;
        }

        /* =======================
        Marcar EN PREPARACIÓN (por factura)
        ======================= */
        case 'marcarComandaEnPreparacion': {
            $factura_id = (int) AjaxHelper::in('factura_id', 0);
            if ($factura_id <= 0) { AjaxHelper::json(['status'=>false,'message'=>'Factura inválida']); break; }

            $ok = $m->updateComandaEstadoByFactura($factura_id, 'en_preparacion');
            AjaxHelper::json($ok ? ['status'=>true] : ['status'=>false,'message'=>'No se pudo actualizar']);
            break;
        }

        /* ============================================================
         * ======= COMBOS (MAESTRO / DETALLE / REGLAS) ================
         * ============================================================ */
        case 'loadCombos':
            AjaxHelper::json(['status'=>true,'combos'=>$m->obtenerCombos()]);
            break;

        case 'loadComboDetalle': {
            $combo_id = (int) AjaxHelper::in('combo_id', 0);
            if ($combo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            $detalle = $m->obtenerComboDetalle($combo_id);
            AjaxHelper::json(['status'=>true,'combo_detalle'=>$detalle]);
            break;
        }

        case 'loadComboCategoriaReglas': {
            $combo_id = (int) AjaxHelper::in('combo_id', 0);
            if ($combo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            AjaxHelper::json(['status'=>true,'reglas'=>$m->obtenerComboCategoriaReglas($combo_id)]);
            break;
        }

        case 'saveCombo': {
            $payload = AjaxHelper::$payload;
            if (!$payload && !isset($_POST['productos_id'])) {
                AjaxHelper::json(['status'=>false,'message'=>'Datos de combo inválidos'], 400);
                break;
            }
            if (!$payload || !is_array($payload)) {
                $payload = [
                    'productos_id' => (int) AjaxHelper::in('productos_id',0),
                    'activo'       => (AjaxHelper::in('activo',1) ? 1 : 0),
                    'precio_venta' => (AjaxHelper::in('precio_venta', null) === null ? null : (float) AjaxHelper::in('precio_venta',0)),
                    'version'      => (int) AjaxHelper::in('version',1),
                    'items'        => json_decode((string) AjaxHelper::in('items','[]'), true),
                    'reglas'       => json_decode((string) AjaxHelper::in('reglas','[]'), true),
                ];
            }
            AjaxHelper::json($m->guardarCombo($payload));
            break;
        }

        case 'updateCombo': {
            $payload = AjaxHelper::$payload;
            $comboIdFromPost = AjaxHelper::in('combo_id', null);
            if ((!$payload || !isset($payload['combo_id'])) && $comboIdFromPost === null) {
                AjaxHelper::json(['status'=>false,'message'=>'Datos de combo inválidos'], 400);
                break;
            }
            if (!$payload || !is_array($payload)) {
                $precioParam = AjaxHelper::in('precio_venta', '__omit__');
                $payload = [
                    'combo_id'     => (int) AjaxHelper::in('combo_id',0),
                    'productos_id' => (AjaxHelper::in('productos_id', null) !== null) ? (int) AjaxHelper::in('productos_id',0) : null,
                    'activo'       => (isset($_POST['activo']) ? ((int) AjaxHelper::in('activo',1) ? 1 : 0) : null),
                    'precio_venta' => ($precioParam === '__omit__') ? '__omit__' : ((AjaxHelper::in('precio_venta', null) === null) ? null : (float) AjaxHelper::in('precio_venta',0)),
                    'version'      => (AjaxHelper::in('version', null) !== null) ? (int) AjaxHelper::in('version',1) : null,
                    'items'        => (isset($_POST['items']) ? json_decode((string) AjaxHelper::in('items','[]'), true) : null),
                    'reglas'       => (isset($_POST['reglas']) ? json_decode((string) AjaxHelper::in('reglas','[]'), true) : null),
                ];
            }
            AjaxHelper::json($m->actualizarCombo($payload));
            break;
        }

        case 'deleteCombo': {
            $combo_id = (int) AjaxHelper::in('combo_id', 0);
            if ($combo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            AjaxHelper::json($m->eliminarCombo($combo_id));
            break;
        }

        case 'calcComboDisponibilidad': {
            $combo_id  = (int) AjaxHelper::in('combo_id', 0);
            $cantidad  = max(1, (int) AjaxHelper::in('cantidad', 1));
            if ($combo_id <= 0) {
                AjaxHelper::json(['status'=>false,'message'=>'Combo inválido']);
                break;
            }
            AjaxHelper::json($m->calcularDisponibilidadCombo($combo_id, $cantidad));
            break;
        }

        /* ============================================================
         * ======= OBTENER PROMOCIONES VIGENTES PARA PRODUCTOS ========
         * ============================================================ */
        case 'loadPromocionesVigentesProductos':
            AjaxHelper::json(['status'=>true,'promociones'=>$m->obtenerPromocionesVigentesProductos()]);
            break;

        /* ============================================================
         * ======= M ESAS rápidas / flujo corto =======================
         * ============================================================ */
        case 'ocuparMesa': {
            $mesa_id = (int) AjaxHelper::in('mesa_id', 0);
            if ($mesa_id <= 0) throw new Exception('Mesa inválida');
            $m->setMesaEstado($mesa_id, true);
            AjaxHelper::json(['ok' => true]);
            break;
        }

        case 'liberarMesa': {
            $mesa_id = (int) AjaxHelper::in('mesa_id', 0);
            if ($mesa_id <= 0) throw new Exception('Mesa inválida');
            $res = $m->liberarMesaConservandoCuenta($mesa_id);
            AjaxHelper::json(['ok'=>!empty($res['status']),'status'=>!empty($res['status']),'message'=>$res['message']??'','cuenta_conservada'=>!empty($res['cuenta_conservada']),'factura_id'=>(int)($res['factura_id']??0)]);
            break;
        }

        case 'getFacturaMesaAbierta': {
            $mesa_id = (int) AjaxHelper::in('mesa_id', 0);
            if ($mesa_id <= 0) throw new Exception('Mesa inválida');
            [$factura, $detalle] = $m->getFacturaAbiertaPorMesa($mesa_id);
            AjaxHelper::json(['ok' => (bool)$factura, 'factura' => $factura, 'detalle' => $detalle]);
            break;
        }

        case 'registrarComandaCocina': {
            $factura_id  = (int) AjaxHelper::in('factura_id', 0);
            $mesa_id     = (int) AjaxHelper::in('mesa_id', 0);
            $comentarios = trim((string) AjaxHelper::in('comentarios', ''));
        
            if ($factura_id <= 0) {
                AjaxHelper::json(['ok'=>false,'msg'=>'Factura inválida']);
                break;
            }
        
            $servicio = ((string) AjaxHelper::in('servicio', $mesa_id > 0 ? 'mesa' : 'llevar')) === 'mesa' ? 'mesa' : 'llevar';
            $res = $m->registrarNuevosItemsComanda($factura_id, $mesa_id, $comentarios, $servicio);
        
            if (!is_array($res) || empty($res['status'])) {
                AjaxHelper::json([
                    'ok'  => false,
                    'msg' => $res['message'] ?? 'No se pudo registrar la comanda'
                ]);
            } else {
                AjaxHelper::json([
                    'ok'                 => true,
                    'factura_comanda_id' => (int)($res['factura_comanda_id'] ?? 0)
                ]);
            }
            break;
        }        

        case 'guardarFacturaRestaurante': {
            $servicio      = ((string) AjaxHelper::in('servicio', 'mesa')) === 'llevar' ? 'llevar' : 'mesa';
            $mesa_id       = (int) AjaxHelper::in('mesa_id', 0);
            $factura_id    = (int) AjaxHelper::in('factura_id', 0);
            $clientes_id   = (int) AjaxHelper::in('clientes_id', 0);
            $tipo_factura  = ((int)AjaxHelper::in('tipo_factura',1) === 2) ? 2 : 1;
            $observaciones = trim((string) AjaxHelper::in('observaciones', ''));
            $detalle       = json_decode((string) AjaxHelper::in('detalle', '[]'), true);
            $enviarComanda = AjaxHelper::toBool(AjaxHelper::in('enviar_comanda', 1), 1);

            if (!is_array($detalle) || !count($detalle)) {
                AjaxHelper::json(['ok'=>false,'msg'=>'Detalle vacío']);
                break;
            }
            if ($servicio === 'mesa' && $mesa_id <= 0) {
                AjaxHelper::json(['ok'=>false,'msg'=>'Debe seleccionar una mesa antes de enviar a cocina']);
                break;
            }

            // Si ya existe una cuenta abierta, actualizarla en lugar de crear otra factura.
            if ($factura_id > 0) {
                $resActualizar = $m->actualizarCuentaBorrador([
                    'factura_id'    => $factura_id,
                    'cliente_id'    => $clientes_id,
                    'tipo_factura'  => $tipo_factura,
                    'items'         => $detalle,
                    'observaciones' => $observaciones,
                    'mesa_id'       => $mesa_id,
                    'servicio_tipo' => $servicio
                ], $mesa_id, $servicio);
                if (!is_array($resActualizar) || empty($resActualizar['status'])) {
                    AjaxHelper::json(['ok'=>false,'msg'=>$resActualizar['message'] ?? 'No se pudo actualizar la cuenta']);
                    break;
                }
                if ($mesa_id > 0) $m->consumirReservaMesa($mesa_id);
                // Guardar cuenta y enviar a preparación son procesos separados.
                // Si se solicita comanda, solo se registran las cantidades NUEVAS aún no enviadas.
                $nuevos = 0;
                if ($enviarComanda) {
                    $rc = $m->registrarNuevosItemsComanda($factura_id, $mesa_id, $observaciones, $servicio);
                    if (!is_array($rc) || empty($rc['status'])) {
                        AjaxHelper::json(['ok'=>false,'msg'=>$rc['message'] ?? 'La cuenta se actualizó, pero no se pudo enviar la comanda']);
                        break;
                    }
                    $nuevos = (int)($rc['nuevos'] ?? 0);
                }
                AjaxHelper::json(['ok'=>true,'factura_id'=>$factura_id,'updated'=>true,'nuevos_comanda'=>$nuevos]);
                break;
            }

            $importe = 0.0;
            foreach ($detalle as $d) {
                $cant   = (int)($d['cantidad'] ?? 1);
                $precio = (float)($d['precio'] ?? 0);
                $importe += $cant * $precio;
            }

            $empresa_id     = (int)($_SESSION['empresa_id_sd'] ?? $_SESSION['empresa_id'] ?? 1);
            $usuario_id     = (int)($_SESSION['users_id_sd'] ?? $_SESSION['usuario'] ?? 1);
            $colaborador_id = (int)($_SESSION['colaborador_id_sd'] ?? $_SESSION['colaboradores_id'] ?? $_SESSION['colaborador_id'] ?? 1);

            $apertura_id = 1;
            if (method_exists($m, 'obtenerAperturaCajaActiva')) {
                $tmp = (int)$m->obtenerAperturaCajaActiva($colaborador_id, $empresa_id);
                if ($tmp > 0) $apertura_id = $tmp;
            }

            $params = [
                'clientes_id'              => $clientes_id,
                'secuencia_facturacion_id' => 1,
                'apertura_id'              => $apertura_id,
                'number'                   => 0,
                'tipo_factura'             => $tipo_factura,
                'colaboradores_id'         => $colaborador_id,
                'notas'                    => $observaciones,
                'usuario'                  => $usuario_id,
                'empresa_id'               => $empresa_id,
                'detalle'                  => $detalle
            ];

            $resCrear = $m->crearFacturaBorrador($params);
            if (!is_array($resCrear) || empty($resCrear['status'])) {
                AjaxHelper::json(['ok'=>false,'msg'=>($resCrear['message'] ?? 'No se pudo crear la factura')]);
                break;
            }

            $factura_id = (int)($resCrear['factura_id'] ?? 0);
            if ($factura_id <= 0) {
                AjaxHelper::json(['ok'=>false,'msg'=>'No se obtuvo el ID de la factura']);
                break;
            }

            $ctxCuenta = $m->guardarContextoCuenta($factura_id, $mesa_id, $servicio);
            if (!is_array($ctxCuenta) || empty($ctxCuenta['status'])) {
                AjaxHelper::json(['ok'=>false,'msg'=>$ctxCuenta['message'] ?? 'No se pudo registrar la cuenta abierta']);
                break;
            }

            if ($servicio === 'mesa' && $mesa_id) {
                $m->setMesaEstado($mesa_id, true);
                $m->consumirReservaMesa($mesa_id);
            }

            $nuevos = 0;
            if ($enviarComanda) {
                $rc = $m->registrarNuevosItemsComanda($factura_id, $mesa_id, $observaciones, $servicio);
                if (!is_array($rc) || empty($rc['status'])) {
                    AjaxHelper::json(['ok'=>false,'msg'=>$rc['message'] ?? 'La cuenta se guardó, pero no se pudo enviar la comanda']);
                    break;
                }
                $nuevos = (int)($rc['nuevos'] ?? 0);
            }

            AjaxHelper::json(['ok' => true, 'factura_id' => $factura_id, 'updated'=>false,'nuevos_comanda'=>$nuevos]);
            break;
        }

        case 'finalizarParaLlevar': {
            $clientes_id   = (int) AjaxHelper::in('clientes_id', 0);
            $observaciones = trim((string) AjaxHelper::in('observaciones', ''));
            $detalle       = json_decode((string) AjaxHelper::in('detalle', '[]'), true);

            if (!is_array($detalle) || !count($detalle)) {
                AjaxHelper::json(['ok'=>false,'msg'=>'Detalle vacío']);
                break;
            }

            $importe = 0.0;
            foreach ($detalle as $d) {
                $importe += ((int)($d['cantidad'] ?? 1)) * ((float)($d['precio'] ?? 0));
            }

            $empresa_id     = (int)($_SESSION['empresa_id'] ?? 1);
            $usuario_id     = (int)($_SESSION['usuario'] ?? 1);
            $colaborador_id = (int)($_SESSION['colaboradores_id'] ?? $_SESSION['colaborador_id'] ?? 1);

            $apertura_id = 1;
            if (method_exists($m, 'obtenerAperturaCajaActiva')) {
                $tmp = (int)$m->obtenerAperturaCajaActiva($colaborador_id, $empresa_id);
                if ($tmp > 0) $apertura_id = $tmp;
            }

            $params = [
                'clientes_id'              => $clientes_id,
                'secuencia_facturacion_id' => 1,
                'apertura_id'              => $apertura_id,
                'number'                   => 0,
                'tipo_factura'             => 1, // contado
                'colaboradores_id'         => $colaborador_id,
                'notas'                    => $observaciones,
                'usuario'                  => $usuario_id,
                'empresa_id'               => $empresa_id,
                'detalle'                  => $detalle
            ];

            $resB = $m->crearFacturaBorrador($params);
            $factura_id = is_array($resB) ? (int)($resB['factura_id'] ?? 0) : (int)$resB;

            if ($factura_id <= 0) {
                $msg = is_array($resB) ? ($resB['message'] ?? 'No se pudo crear factura') : 'No se obtuvo factura_id';
                AjaxHelper::json(['ok'=>false,'msg'=>$msg]);
                break;
            }

            // Tomar el importe calculado por el modelo (incluye ISV configurado por producto).
            $rsTotal = $m->ejecutar_consulta_simple_preparada(
                "SELECT importe FROM facturas WHERE facturas_id=?", "i", [$factura_id]
            );
            $importe = ($rsTotal && $rsTotal->num_rows) ? (float)$rsTotal->fetch_assoc()['importe'] : 0.0;

            // Registrar pago primero; solo marcar pagada si el pago fue exitoso
            // Pago contado
            $tipo_pago    = (int) AjaxHelper::in('tipo_pago', 1);
            $efectivo     = (float) AjaxHelper::in('efectivo', 0);
            $tarjeta      = (float) AjaxHelper::in('tarjeta', 0);
            $cambio       = (float) AjaxHelper::in('cambio', 0);
            $tipo_pago_id = (int) AjaxHelper::in('tipo_pago_id', 1);
            $banco_id     = (int) AjaxHelper::in('banco_id', 0);
            $referencia    = trim((string) AjaxHelper::in('referencia', ''));

            $resP = $m->crearPagoContado([
                'facturas_id' => $factura_id,
                'tipo_pago'   => $tipo_pago,
                'importe'     => $importe,
                'efectivo'    => $efectivo,
                'cambio'      => $cambio,
                'tarjeta'     => $tarjeta,
                'usuario'     => $usuario_id,
                'empresa_id'  => $empresa_id,
                'tipo_pago_id'=> $tipo_pago_id,
                'banco_id'    => $banco_id,
                'referencia'   => $referencia
            ]);

            if (!$resP || (is_array($resP) && empty($resP['status']))) {
                $msg = is_array($resP) ? ($resP['message'] ?? 'No se pudo registrar el pago') : 'No se pudo registrar el pago';
                AjaxHelper::json(['ok'=>false,'msg'=>$msg]);
                break;
            }

            $m->marcarFacturaEstado($factura_id, 2);

            // Comanda opcional
            $forzar_cocina = ((string) AjaxHelper::in('forzar_cocina','0') === '1');
            if ($forzar_cocina) {
                $m->registrarComandaCocina($factura_id, 0, $observaciones, 'llevar');
            }

            AjaxHelper::json(['ok'=>true,'factura_id'=>$factura_id]);
            break;
        }

        case 'cobrarFacturaMesa': {
            $mesa_id       = (int) AjaxHelper::in('mesa_id', 0);
            $factura_id    = (int) AjaxHelper::in('factura_id', 0);
            $tipo_pago     = (int) AjaxHelper::in('tipo_pago', 1);
            $efectivo      = (float) AjaxHelper::in('efectivo', 0);
            $tarjeta       = (float) AjaxHelper::in('tarjeta', 0);
            $cambio        = (float) AjaxHelper::in('cambio', 0);
            $tipo_pago_id  = (int) AjaxHelper::in('tipo_pago_id', 1);
            $banco_id      = (int) AjaxHelper::in('banco_id', 0);
            $referencia    = trim((string) AjaxHelper::in('referencia', ''));
            $liberar_mesa  = AjaxHelper::toBool(AjaxHelper::in('liberar_mesa', 1), 1);

            if ($factura_id <= 0) {
                $row = $m->getFacturaMesaAbierta($mesa_id);
                if (is_array($row) && !empty($row['ok']) && !empty($row['factura']['facturas_id'])) {
                    $factura_id = (int) $row['factura']['facturas_id'];
                }
            }
            if ($factura_id <= 0) {
                AjaxHelper::json(['ok'=>false,'msg'=>'No se encontró factura abierta para cobrar']);
                break;
            }

            $rsImp = $m->ejecutar_consulta_simple_preparada(
                "SELECT importe, estado FROM facturas WHERE facturas_id=?",
                "i", [$factura_id]
            );
            if (!$rsImp || !$rsImp->num_rows) {
                AjaxHelper::json(['ok'=>false,'msg'=>'Factura no encontrada']);
                break;
            }
            $fact = $rsImp->fetch_assoc();
            if ((int)$fact['estado'] !== 1) {
                AjaxHelper::json(['ok'=>false,'msg'=>'La factura ya no está abierta para cobro']);
                break;
            }
            $importe = (float)$fact['importe'];

            $resP = $m->crearPagoContado([
                'facturas_id' => $factura_id,
                'tipo_pago'   => $tipo_pago,
                'importe'     => $importe,
                'efectivo'    => $efectivo,
                'cambio'      => $cambio,
                'tarjeta'     => $tarjeta,
                'tipo_pago_id'=> $tipo_pago_id,
                'banco_id'    => $banco_id,
                'referencia'  => $referencia
            ]);

            if (!$resP || (is_array($resP) && empty($resP['status']))) {
                $msg = is_array($resP) ? ($resP['message'] ?? 'No se pudo registrar el pago') : 'No se pudo registrar el pago';
                AjaxHelper::json(['ok'=>false,'msg'=>$msg]);
                break;
            }

            $m->marcarFacturaEstado($factura_id, 2);
            if ($mesa_id > 0 && $liberar_mesa) {
                $m->setMesaEstado($mesa_id, false);
            }

            AjaxHelper::json([
                'ok'=>true,
                'factura_id'=>$factura_id,
                'mesa_liberada'=>(bool)$liberar_mesa
            ]);
            break;
        }

        case 'listarComandasPorEstacion': {
            // recibe estacion = cocina | barra
            $estacion = strtolower(trim((string) AjaxHelper::in('estacion', 'cocina')));
            if ($estacion !== 'barra') $estacion = 'cocina';
        
            try {
                $data = $m->listarComandasPorEstacion($estacion);
                AjaxHelper::json(['ok' => true, 'estacion' => $estacion, 'data' => $data]);
            } catch (Throwable $e) {
                AjaxHelper::json(['ok' => false, 'msg' => $e->getMessage()]);
            }
            break;
        }        

        default:
            AjaxHelper::json(['status'=>false,'message'=>'Acción no válida'], 400);
    }

} catch (Throwable $e) {
    AjaxHelper::json(['status'=>false,'message'=>$e->getMessage()], 500);
}
