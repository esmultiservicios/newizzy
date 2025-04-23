<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$users_id = intval($_SESSION['users_id_sd']);

// 1. Obtener server_customers_id del usuario
$conexionPrincipal = $mainModel->connection();
$queryUsuario = "SELECT server_customers_id FROM users WHERE users_id = ?";
$stmtUsuario = $conexionPrincipal->prepare($queryUsuario);
$stmtUsuario->bind_param("i", $users_id);
$stmtUsuario->execute();
$resultUsuario = $stmtUsuario->get_result();

if ($resultUsuario->num_rows == 0) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Usuario no encontrado'
    ]);
    exit();
}

$usuarioData = $resultUsuario->fetch_assoc();
$serverCustomersId = $usuarioData['server_customers_id'];
$stmtUsuario->close();

// 2. Conectar a la base de datos del cliente
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

// 3. Obtener cliente_id
$queryCliente = "SELECT clientes_id FROM server_customers WHERE server_customers_id = ?";
$stmtCliente = $conexionCliente->prepare($queryCliente);
$stmtCliente->bind_param("i", $serverCustomersId);
$stmtCliente->execute();
$resultCliente = $stmtCliente->get_result();

if ($resultCliente->num_rows == 0) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Cliente no encontrado'
    ]);
    exit();
}            

$clienteData = $resultCliente->fetch_assoc();
$clientes_id = $clienteData['clientes_id'];
$stmtCliente->close();

// 4. Contar facturas pendientes (estado = 3)
$query = "SELECT COUNT(*) as total_pendientes FROM facturas WHERE clientes_id = ? AND estado = '3'";
$stmt = $conexionCliente->prepare($query);
$stmt->bind_param("i", $clientes_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'type' => 'success',
    'total_pendientes' => $data['total_pendientes']
]);
exit();