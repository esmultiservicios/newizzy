<?php
// core/facturas/getIsvConfig.php
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

try {
    /*
        IMPORTANTE:
        No usamos $mainModel->connection() aquí porque en algunos entornos
        puede provocar:
        mysqli::select_db(): Argument #1 ($database) must be of type string, mysqli given

        Para este endpoint basta con ejecutar_consulta_simple(), igual que otros PHP del core.
    */
    $mainModel = new mainModel();

    /*
        Configuración de proforma:
        accion  = 'Activar ISV Proforma'
        config_id = 6
        activar = 1 Sí / 2 No

        Regla:
        - Si activar = 1, la proforma calcula ISV según el producto.
        - Si activar = 2 o no existe, la proforma queda sin ISV.
    */
    $activar_isv_proforma = 2;
    $proforma_aplica_isv = 0;

    $sqlProforma = "
        SELECT activar
        FROM config
        WHERE TRIM(accion) = 'Activar ISV Proforma'
           OR config_id = 6
        ORDER BY CASE WHEN TRIM(accion) = 'Activar ISV Proforma' THEN 0 ELSE 1 END
        LIMIT 1
    ";

    $rsProforma = $mainModel->ejecutar_consulta_simple($sqlProforma);

    if ($rsProforma && $rsProforma->num_rows > 0) {
        $rowProforma = $rsProforma->fetch_assoc();
        $activar_isv_proforma = (int)($rowProforma['activar'] ?? 2);
        $proforma_aplica_isv = ($activar_isv_proforma === 1) ? 1 : 0;
    }

    // Traer valores para isv_id 1 y 2. Si no existen, devolver 0.
    $rate1_value = 0.00;
    $rate2_value = 0.00;

    $sqlISV = "
        SELECT isv_id, valor
        FROM isv
        WHERE activar = 1
          AND isv_id IN (1, 2)
    ";

    $rsISV = $mainModel->ejecutar_consulta_simple($sqlISV);

    if ($rsISV) {
        while ($row = $rsISV->fetch_assoc()) {
            if ((int)$row['isv_id'] === 1) {
                $rate1_value = (float)$row['valor'];
            }

            if ((int)$row['isv_id'] === 2) {
                $rate2_value = (float)$row['valor'];
            }
        }
    }

    $rate1 = $rate1_value / 100.0;
    $rate2 = $rate2_value / 100.0;

    $label1 = 'ISV ' . rtrim(rtrim(number_format($rate1_value, 2, '.', ''), '0'), '.') . '%';
    $label2 = 'ISV ' . rtrim(rtrim(number_format($rate2_value, 2, '.', ''), '0'), '.') . '%';

    echo json_encode([
        'success' => true,
        'rate1_value' => number_format($rate1_value, 2, '.', ''),
        'rate2_value' => number_format($rate2_value, 2, '.', ''),
        'rate1' => $rate1,
        'rate2' => $rate2,
        'label1' => $label1,
        'label2' => $label2,
        'activar_isv_proforma' => $activar_isv_proforma,
        'proforma_aplica_isv' => $proforma_aplica_isv
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'rate1_value' => '0.00',
        'rate2_value' => '0.00',
        'rate1' => 0,
        'rate2' => 0,
        'label1' => 'ISV 0%',
        'label2' => 'ISV 0%',
        'activar_isv_proforma' => 2,
        'proforma_aplica_isv' => 0,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
