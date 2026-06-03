<?php
// core/caja/getCategoriasGastosRetiroCaja.php
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida',
            'data' => []
        ]);
        exit;
    }
}

$sql = "
    SELECT categoria_gastos_id, nombre
    FROM categoria_gastos
    WHERE estado = 1
    ORDER BY nombre ASC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'categoria_gastos_id' => (int)$row['categoria_gastos_id'],
            'nombre' => $row['nombre']
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $data
]);