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

    // Soportar form + JSON
    $raw      = file_get_contents('php://input');
    $asJson   = json_decode($raw, true);
    $action   = $_POST['action'] ?? ($asJson['action'] ?? null);
    $payload  = $asJson['data']   ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode(['status'=>false,'message'=>'Acción no especificada']);
        exit;
    }

    $m = new facturasRestauranteModelo();

    switch ($action) {

        /* ======= DATA CATÁLOGO / ISV / MESAS ======= */
        case 'loadISV':
            echo json_encode(['status'=>true,'isv'=>$m->obtenerISVTiposPublico()]);
            break;

        case 'loadMesas':
            echo json_encode(['status'=>true,'mesas'=>$m->obtenerMesas()]);
            break;

        case 'saveMesa':
            $numero    = $_POST['numero']    ?? '';
            $capacidad = $_POST['capacidad'] ?? 4;
            $ubicacion = $_POST['ubicacion'] ?? 'Interior';
            echo json_encode($m->guardarMesa($numero, intval($capacidad), $ubicacion));
            break;

        case 'loadCategorias':
            echo json_encode(['status'=>true,'categorias'=>$m->obtenerCategoriasProductos()]);
            break;

        case 'loadProductos':
            echo json_encode(['status'=>true,'productos'=>$m->obtenerProductos()]);
            break;

        case 'loadClientes':
            echo json_encode(['status'=>true,'clientes'=>$m->obtenerClientes()]);
            break;

        /* ======= NUEVO: GUARDAR CAT/PROD/CLI ======= */
        case 'saveCategoria':
            $nombre = trim($_POST['nombre'] ?? ($payload['nombre'] ?? ''));
            echo json_encode($m->guardarCategoria($nombre));
            break;

        case 'saveProductoBasico':
            // data llega como JSON -> $payload
            if (!$payload) {
                http_response_code(400);
                echo json_encode(['status'=>false,'message'=>'Datos de producto inválidos']);
                break;
            }
            echo json_encode($m->guardarProductoBasico($payload));
            break;

        case 'saveClienteBasico':
            // data llega como JSON -> $payload
            if (!$payload) {
                // aceptar también por POST form-url-encoded
                $payload = [
                    'nombre'           => $_POST['nombre'] ?? '',
                    'rtn'              => $_POST['rtn'] ?? '',
                    'fecha'            => $_POST['fecha'] ?? date('Y-m-d'),
                    'departamentos_id' => intval($_POST['departamentos_id'] ?? 0),
                    'municipios_id'    => intval($_POST['municipios_id'] ?? 0),
                    'localidad'        => $_POST['localidad'] ?? '',
                    'telefono'         => $_POST['telefono'] ?? '',
                    'correo'           => $_POST['correo'] ?? '',
                    'estado'           => intval($_POST['estado'] ?? 1),
                ];
            }
            echo json_encode($m->guardarClienteBasico($payload));
            break;

        /* ======= FACTURA / COMANDA ======= */
        case 'loadFacturaMesa':
            $mesa_id = intval($_POST['mesa_id'] ?? 0);
            if ($mesa_id<=0) {
                echo json_encode(['status'=>false,'message'=>'Mesa inválida']); break;
            }
            $factura = $m->obtenerFacturaMesa($mesa_id);
            if (!$factura) {
                http_response_code(404);
                echo json_encode(['status'=>false,'message'=>'No hay factura activa para esta mesa']);
                break;
            }
            echo json_encode([
                'status'=>true,
                'factura'=>$factura,
                'mesa'=>[
                    'id'        => intval($factura['mesa_id']),
                    'numero'    => $factura['numero_mesa'],
                    'capacidad' => intval($factura['capacidad_mesa']),
                    'ubicacion' => $factura['ubicacion_mesa'],
                    'estado'    => 'ocupada'
                ],
                'items'=>$m->obtenerDetallesFactura(intval($factura['facturas_id']))
            ]);
            break;

        case 'saveFactura':   // crea (borrador o pagada)
            if (!$payload) {
                $payload = [
                    'mesa_id'       => intval($_POST['mesa_id'] ?? 0),
                    'cliente_id'    => intval($_POST['cliente_id'] ?? 0),
                    'items'         => json_decode($_POST['items'] ?? '[]', true),
                    'metodo_pago'   => $_POST['metodo_pago'] ?? '',
                    'observaciones' => $_POST['observaciones'] ?? '',
                ];
            }
            $r = $m->guardarFactura($payload);
            echo json_encode($r);
            break;

        case 'updateFactura': // agrega items y/o paga
            if (!$payload || !isset($payload['factura_id'])) {
                echo json_encode(['status'=>false,'message'=>'Datos inválidos']); break;
            }
            echo json_encode($m->actualizarFactura($payload));
            break;

        case 'closeFactura':
            $factura_id = intval($_POST['factura_id'] ?? 0);
            echo json_encode($m->cerrarFactura($factura_id));
            break;

        /* ======= COCINA ======= */
        case 'enviarComandaCocina':
            $data = $payload ?? [
                'factura_id'    => intval($_POST['factura_id'] ?? 0),
                'mesa'          => $_POST['mesa'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'items'         => json_decode($_POST['items'] ?? '[]', true),
            ];
            echo json_encode($m->enviarComandaCocina($data));
            break;

        case 'marcarComandaPreparada':
            echo json_encode($m->marcarComandaPreparada(intval($_POST['comanda_id'] ?? 0)));
            break;

        case 'marcarComandaUrgente':
            echo json_encode($m->marcarComandaUrgente(intval($_POST['factura_id'] ?? 0), !!($_POST['urgente'] ?? false)));
            break;

        case 'marcarComandaEnPreparacion':
            echo json_encode($m->marcarComandaEnPreparacion(intval($_POST['factura_id'] ?? 0)));
            break;

        default:
            http_response_code(400);
            echo json_encode(['status'=>false,'message'=>'Acción no válida']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>false,'message'=>$e->getMessage()]);
}
