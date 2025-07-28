<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/facturasRestauranteModelo.php';

header('Content-Type: application/json');

try {
    $modelo = new facturasRestauranteModelo();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    if (!isset($_POST['action'])) {
        throw new Exception('Acción no especificada', 400);
    }

    $action = $modelo->cleanString($_POST['action']);

    switch ($action) {
        case 'loadMesas':
            $mesas = $modelo->obtenerMesas();
            echo json_encode([
                'status' => true,
                'mesas' => $mesas
            ]);
            break;
            
        case 'saveMesa':
            if (!isset($_POST['numero']) || !isset($_POST['ubicacion'])) {
                throw new Exception('Datos incompletos para crear mesa', 400);
            }

            $numero = $modelo->cleanString($_POST['numero']);
            $capacidad = isset($_POST['capacidad']) ? intval($_POST['capacidad']) : 4;
            $ubicacion = $modelo->cleanString($_POST['ubicacion']);
            
            if (empty($numero)) {
                throw new Exception('El número de mesa es requerido', 400);
            }
            
            $result = $modelo->guardarMesa($numero, $capacidad, $ubicacion);
            echo json_encode($result);
            break;
            
        case 'loadCategorias':
            $categorias = $modelo->obtenerCategoriasProductos();
            echo json_encode([
                'status' => true,
                'categorias' => $categorias
            ]);
            break;
            
        case 'loadProductos':
            $productos = $modelo->obtenerProductos();
            echo json_encode([
                'status' => true,
                'productos' => $productos
            ]);
            break;
            
        case 'loadClientes':
            $clientes = $modelo->obtenerClientes();
            echo json_encode([
                'status' => true,
                'clientes' => $clientes
            ]);
            break;
            
        case 'loadFacturaMesa':
            if (!isset($_POST['mesa_id'])) {
                throw new Exception('ID de mesa no especificado', 400);
            }

            $mesa_id = intval($_POST['mesa_id']);
            
            if ($mesa_id <= 0) {
                throw new Exception('ID de mesa inválido', 400);
            }
            
            $factura = $modelo->obtenerFacturaMesa($mesa_id);
            
            if ($factura) {
                echo json_encode([
                    'status' => true,
                    'factura' => $factura,
                    'mesa' => [
                        'id' => $factura['mesa_id'],
                        'numero' => $factura['numero_mesa'],
                        'capacidad' => $factura['capacidad_mesa'],
                        'ubicacion' => $factura['ubicacion_mesa'],
                        'estado' => 'ocupada'
                    ],
                    'items' => $modelo->obtenerDetallesFactura($factura['facturas_id'])
                ]);
            } else {
                throw new Exception('No se encontró factura activa para esta mesa', 404);
            }
            break;
            
        case 'saveFactura':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['data'])) {
                throw new Exception('Datos de factura inválidos', 400);
            }
            
            $result = $modelo->guardarFactura($data['data']);
            echo json_encode($result);
            break;
            
        case 'updateFactura':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['data']) || !isset($data['data']['factura_id'])) {
                throw new Exception('Datos de factura inválidos', 400);
            }
            
            $result = $modelo->actualizarFactura($data['data']);
            echo json_encode($result);
            break;
            
        case 'closeFactura':
            if (!isset($_POST['factura_id'])) {
                throw new Exception('ID de factura no especificado', 400);
            }

            $factura_id = intval($_POST['factura_id']);
            
            if ($factura_id <= 0) {
                throw new Exception('ID de factura inválido', 400);
            }
            
            $result = $modelo->cerrarFactura($factura_id);
            echo json_encode($result);
            break;

        case 'loadComandasCocina':
            $comandas = $modelo->obtenerComandasCocina();
            echo json_encode([
                'status' => true,
                'comandas' => $comandas
            ]);
            break;
            
        case 'marcarComandaPreparada':
            if (!isset($_POST['comanda_id'])) {
                throw new Exception('ID de comanda no especificado', 400);
            }
        
            $comanda_id = intval($_POST['comanda_id']);
            $result = $modelo->marcarComandaPreparada($comanda_id);
            echo json_encode($result);
            break;
            
        case 'marcarComandaUrgente':
            if (!isset($_POST['factura_id']) || !isset($_POST['urgente'])) {
                throw new Exception('Datos incompletos para marcar comanda', 400);
            }
        
            $factura_id = intval($_POST['factura_id']);
            $urgente = filter_var($_POST['urgente'], FILTER_VALIDATE_BOOLEAN);
            $result = $modelo->marcarComandaUrgente($factura_id, $urgente);
            echo json_encode($result);
            break;
            
        case 'marcarComandaEnPreparacion':
            if (!isset($_POST['factura_id'])) {
                throw new Exception('ID de factura no especificado', 400);
            }
        
            $factura_id = intval($_POST['factura_id']);
            $result = $modelo->marcarComandaEnPreparacion($factura_id);
            echo json_encode($result);
            break;            
            
        default:
            throw new Exception('Acción no válida', 400);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'error' => true,
        'code' => $e->getCode()
    ]);
}