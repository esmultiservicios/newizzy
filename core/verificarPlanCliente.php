<?php
//verificarPlanCliente.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

if (!isset($_POST['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado']);
    exit;
}

$clienteId = $mainModel->cleanString($_POST['cliente_id']);

// Consulta preparada para verificar el plan del cliente
$query = "SELECT sc.server_customers_id, sc.planes_id, sc.sistema_id, sc.db,
                 IFNULL(p.user_extra, 0) as user_extra,
                 c.nombre as cliente_nombre
          FROM server_customers sc
          JOIN clientes c ON sc.clientes_id = c.clientes_id
          LEFT JOIN plan p ON p.plan_id = 1
          WHERE sc.clientes_id = ? AND sc.estado = 1
          LIMIT 1";

$stmt = $mainModel->connection()->prepare($query);
$stmt->bind_param("i", $clienteId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'exists' => true,
        'data' => $data
    ]);
} else {
    echo json_encode([
        'success' => true,
        'exists' => false
    ]);
}
$stmt->close();