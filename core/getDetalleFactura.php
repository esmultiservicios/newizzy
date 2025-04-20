<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

// Verificar si se recibieron los parámetros necesarios
if(!isset($_POST['facturas_id']) || empty($_POST['facturas_id'])) {
    echo json_encode([
        'type' => 'error',
        'mensaje' => 'ID de factura no proporcionado',
        'data' => []
    ]);
    exit;
}

// Obtener parámetros
$facturas_id = intval($_POST['facturas_id']);
$db_name = isset($_POST['db_name']) && !empty($_POST['db_name']) ? $_POST['db_name'] : DB_MAIN;

try {
    // Conexión a la base de datos
    $conexion = new mysqli(SERVER, USER, PASS, $db_name);
    
    if($conexion->connect_error) {
        echo json_encode([
            'type' => 'error',
            'mensaje' => 'Error de conexión a la base de datos',
            'error' => $conexion->connect_error,
            'data' => []
        ]);
        exit;
    }
    
    // Consulta para obtener los detalles de la factura
    $query = "SELECT fd.*, p.nombre AS producto, m.nombre AS medida 
              FROM facturas_detalles fd
              LEFT JOIN productos p ON fd.productos_id = p.productos_id 
              LEFT JOIN medida m ON p.medida_id = m.medida_id
              WHERE fd.facturas_id = ?";
    
    $stmt = $conexion->prepare($query);
    
    if($stmt) {
        $stmt->bind_param("i", $facturas_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if($resultado) {
            $detalles = [];
            while($fila = $resultado->fetch_assoc()) {
                $detalles[] = [
                    'facturas_id' => $fila['facturas_id'],
                    'producto' => $fila['producto'] ?? 'Servicio/Producto',
                    'cantidad' => $fila['cantidad'],
                    'precio' => $fila['precio'],
                    'isv_valor' => $fila['isv_valor'],
                    'descuento' => $fila['descuento'],
                    'medida' => $fila['medida'] ?? ''
                ];
            }
            
            echo json_encode([
                'type' => 'success',
                'mensaje' => 'Detalles obtenidos correctamente',
                'data' => $detalles
            ]);
        } else {
            echo json_encode([
                'type' => 'error',
                'mensaje' => 'Error al obtener los detalles',
                'error' => $conexion->error,
                'data' => []
            ]);
        }
        
        $stmt->close();
    } else {
        echo json_encode([
            'type' => 'error',
            'mensaje' => 'Error al preparar la consulta',
            'error' => $conexion->error,
            'data' => []
        ]);
    }
    
    $conexion->close();
} catch(Exception $e) {
    echo json_encode([
        'type' => 'error',
        'mensaje' => 'Excepción al procesar la solicitud',
        'error' => $e->getMessage(),
        'data' => []
    ]);
}