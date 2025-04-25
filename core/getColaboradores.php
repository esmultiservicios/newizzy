<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header("Content-Type: application/json");

try {
    $insMainModel = new mainModel();
    $conexion = $insMainModel->connection();

    // Consulta todos los colaboradores activos (ajusta la consulta si es necesario)
    $stmt = $conexion->prepare("SELECT colaboradores_id, nombre, identidad FROM colaboradores WHERE estado = 1 ORDER BY nombre ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $colaboradores = [];
    while ($row = $result->fetch_assoc()) {
        $colaboradores[] = [
            'colaboradores_id' => $row['colaboradores_id'],
            'nombre' => $row['nombre'],
            'identidad' => $row['identidad']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $colaboradores
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los colaboradores',
        'error' => $e->getMessage()
    ]);
}
