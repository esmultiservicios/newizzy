<?php
// core/facturas/getIsvConfig.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

try {
    $mainModel = new mainModel();
    $db = $mainModel->connection();
    if (!$db) {
        throw new Exception('Sin conexión a BD');
    }

    // Traer valores para isv_id 1 y 2 (si no existen, devolver 0)
    $rate1_value = 0.00; // porcentaje (ej. 15.00)
    $rate2_value = 0.00;

    $sql = "SELECT isv_id, valor
            FROM isv
            WHERE activar = 1 AND isv_id IN (1,2)";
    $rs = $mainModel->ejecutar_consulta_simple($sql);
    while ($row = $rs->fetch_assoc()) {
        if ((int)$row['isv_id'] === 1) $rate1_value = (float)$row['valor'];
        if ((int)$row['isv_id'] === 2) $rate2_value = (float)$row['valor'];
    }

    // Convertir a decimales (0.15, 0.18)
    $rate1 = $rate1_value / 100.0;
    $rate2 = $rate2_value / 100.0;

    // Etiquetas para mostrarlas tal cual en HTML
    $label1 = 'ISV ' . rtrim(rtrim(number_format($rate1_value, 2, '.', ''), '0'), '.') . '%';
    $label2 = 'ISV ' . rtrim(rtrim(number_format($rate2_value, 2, '.', ''), '0'), '.') . '%';

    echo json_encode([
        'rate1_value' => number_format($rate1_value, 2, '.', ''), // "15.00"
        'rate2_value' => number_format($rate2_value, 2, '.', ''), // "18.00"
        'rate1'       => $rate1,                                   // 0.15
        'rate2'       => $rate2,                                   // 0.18
        'label1'      => $label1,                                  // "ISV 15%"
        'label2'      => $label2                                   // "ISV 18%"
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'rate1_value' => "0.00",
        'rate2_value' => "0.00",
        'rate1'       => 0,
        'rate2'       => 0,
        'label1'      => 'ISV 0%',
        'label2'      => 'ISV 0%',
        'error'       => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
