<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header("Content-Type: application/json");

try {
    $insMainModel = new mainModel();
    $conexion = $insMainModel->connection(); // << CORREGIDO

    // Consulta todos los puestos activos
    $stmt = $conexion->prepare("SELECT puestos_id, nombre FROM puestos WHERE estado = 1 ORDER BY nombre ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $puestos = [];
    while ($row = $result->fetch_assoc()) {
        $puestos[] = [
            'puestos_id' => $row['puestos_id'],
            'nombre' => $row['nombre']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $puestos
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los puestos',
        'error' => $e->getMessage()
    ]);
}
