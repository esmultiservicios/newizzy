<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if(isset($_POST['action']) && $_POST['action'] == 'get_detalle' && isset($_POST['facturas_id'])) {
    session_start(['name'=>'SD']);
    
    if (!isset($_SESSION['users_id_sd'])) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Error de sesión',
            'message' => 'Usuario no autenticado'
        ]);
        exit();
    }
    
    $facturaId = intval($_POST['facturas_id']);
    $mainModel = new mainModel();
    
    // Conectar a la base de datos del cliente
    $configCliente = [
        'host' => SERVER,
        'user' => USER,
        'pass' => PASS,
        'name' => DB_MAIN
    ];
    
    $conexionCliente = $mainModel->connectToDatabase($configCliente);
    
    if (!$conexionCliente) {
        echo json_encode([
            'type' => 'error',
            'title' => 'Error',
            'message' => 'Error de conexión a la base de datos'
        ]);
        exit();
    }

    // Consulta para obtener el detalle
    $query = "SELECT 
                fd.*, 
                p.nombre as producto, 
                p.medida,
                IFNULL(s.nombre_servicio, '') as servicio
              FROM facturas_detalles fd
              LEFT JOIN productos p ON fd.productos_id = p.productos_id
              LEFT JOIN servicios s ON fd.servicios_id = s.servicios_id
              WHERE fd.facturas_id = ?";
    
    $stmt = $conexionCliente->prepare($query);
    $stmt->bind_param("i", $facturaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $detalles = [];
    while($row = $result->fetch_assoc()) {
        $detalles[] = [
            'nombre' => !empty($row['producto']) ? $row['producto'] : $row['servicio'],
            'cantidad' => $row['cantidad'],
            'precio' => $row['precio'],
            'isv_valor' => $row['isv_valor'],
            'descuento' => $row['descuento'],
            'medida' => $row['medida']
        ];
    }
    
    echo json_encode([
        'type' => 'success',
        'data' => $detalles
    ]);
    exit();
}

echo json_encode([
    'type' => 'error',
    'title' => 'Error',
    'message' => 'Petición inválida'
]);