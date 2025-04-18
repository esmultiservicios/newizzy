<?php
//btenerAsignacionesRecientes.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();


$query = "SELECT 
            sc.server_customers_id,
            sc.clientes_id, 
            c.nombre AS cliente_nombre, 
            c.rtn AS identificacion,
            sc.planes_id,
            sc.validar,
            sc.estado,
            sc.codigo_cliente,
            p.nombre AS plan_nombre,
            sc.sistema_id,
            s.nombre AS sistema_nombre,
            IFNULL(pl.user_extra, 0) AS user_extra,
            IFNULL(pl.fecha_registro, NOW()) AS fecha_registro
          FROM server_customers sc
          JOIN clientes c ON sc.clientes_id = c.clientes_id
          JOIN planes p ON sc.planes_id = p.planes_id
          JOIN sistema s ON sc.sistema_id = s.sistema_id
          LEFT JOIN plan pl ON pl.plan_id = 1
          WHERE sc.estado = 1
          ORDER BY c.nombre ASC";

$result = $mainModel->ejecutar_consulta_simple($query);
$asignaciones = [];

while ($row = $result->fetch_assoc()) {
    $asignaciones[] = [
        'server_customers_id' => $row['server_customers_id'],
        'cliente_id' => $row['clientes_id'],
        'validar' => $row['validar'],
        'estado' => $row['estado'],        
        'cliente' => [
            'nombre' => $row['cliente_nombre'],
            'identificacion' => $row['identificacion'],
            'codigo_cliente' => $row['codigo_cliente']
        ],
        'planes_id' => $row['planes_id'],
        'plan' => [
            'nombre' => $row['plan_nombre']
        ],
        'sistema_id' => $row['sistema_id'],
        'sistema' => [
            'sistema_id' => $row['sistema_id'],
            'nombre' => $row['sistema_nombre']
        ],
        'user_extra' => $row['user_extra'],
        'fecha_registro' => $row['fecha_registro']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $asignaciones
]);