<?php
// core/facturas/getClientes.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

// (Opcional) validar sesión
if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();
    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida',
            'data'    => []
        ]);
        exit;
    }
}

$sql = "SELECT clientes_id, nombre, rtn
        FROM clientes
        WHERE estado = 1
        ORDER BY nombre ASC";

$res = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'clientes_id' => (int)$row['clientes_id'],
            'nombre'      => $row['nombre'],
            'rtn'         => $row['rtn'] ?? null,
        ];
    }
}

echo json_encode([
    'success' => true,
    'data'    => $data
]);
