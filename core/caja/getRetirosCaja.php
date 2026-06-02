<?php
// core/caja/getRetirosCaja.php
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
            'data' => [],
            'resumen' => []
        ]);
        exit;
    }
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

$modo = isset($_POST['modo']) ? trim($_POST['modo']) : 'caja';
$apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : 0;
$fechai = isset($_POST['fechai']) ? trim($_POST['fechai']) : date('Y-m-d');
$fechaf = isset($_POST['fechaf']) ? trim($_POST['fechaf']) : date('Y-m-d');

if ($modo !== 'caja' && $modo !== 'periodo') {
    $modo = 'caja';
}

if ($empresa_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar la empresa de la sesión.',
        'data' => [],
        'resumen' => []
    ]);
    exit;
}

if ($modo === 'caja' && $apertura_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió una apertura de caja válida.',
        'data' => [],
        'resumen' => []
    ]);
    exit;
}

if ($fechai === '') {
    $fechai = date('Y-m-d');
}

if ($fechaf === '') {
    $fechaf = $fechai;
}

$estado_caja = 0;

if ($modo === 'caja') {
    $sqlApertura = "
        SELECT apertura_id, estado
        FROM apertura
        WHERE apertura_id = '$apertura_id'
          AND empresa_id = '$empresa_id'
        LIMIT 1
    ";

    $resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

    if (!$resApertura || $resApertura->num_rows <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La apertura de caja no existe o no pertenece a la empresa actual.',
            'data' => [],
            'resumen' => []
        ]);
        exit;
    }

    $rowApertura = $resApertura->fetch_assoc();
    $estado_caja = (int)$rowApertura['estado'];
}

$where = " cr.empresa_id = '$empresa_id' ";

if ($modo === 'caja') {
    $where .= " AND cr.apertura_id = '$apertura_id' ";
} else {
    $where .= " AND cr.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

$sql = "
    SELECT
        cr.caja_retiros_id,
        cr.apertura_id,
        cr.egresos_id,
        cr.cuentas_id,
        cr.empresa_id,
        cr.monto,
        cr.motivo,
        cr.observacion,
        cr.estado,
        cr.colaboradores_id,
        cr.fecha,
        cr.fecha_registro,
        a.estado AS estado_caja,
        c.nombre AS cuenta,
        e.factura AS factura_egreso,
        e.total AS total_egreso
    FROM caja_retiros cr
    INNER JOIN apertura a
        ON a.apertura_id = cr.apertura_id
       AND a.empresa_id = cr.empresa_id
    LEFT JOIN cuentas c
        ON c.cuentas_id = cr.cuentas_id
    LEFT JOIN egresos e
        ON e.egresos_id = cr.egresos_id
       AND e.empresa_id = cr.empresa_id
    WHERE $where
    ORDER BY cr.caja_retiros_id DESC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];
$total_retiros = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $estado = (int)$row['estado'];
        $estadoCajaFila = (int)$row['estado_caja'];
        $monto = (float)$row['monto'];

        if ($estado === 1) {
            $total_retiros += $monto;
        }

        $puede_reintegrar = ($estadoCajaFila === 1 && $estado === 1 && $monto > 0) ? 1 : 0;

        $data[] = [
            'caja_retiros_id' => (int)$row['caja_retiros_id'],
            'apertura_id' => (int)$row['apertura_id'],
            'egresos_id' => (int)$row['egresos_id'],
            'cuentas_id' => (int)$row['cuentas_id'],
            'empresa_id' => (int)$row['empresa_id'],
            'monto' => number_format($monto, 2, '.', ''),
            'motivo' => $row['motivo'],
            'observacion' => $row['observacion'],
            'estado' => $estado,
            'estado_label' => $estado === 1 ? 'Activo' : 'Anulado',
            'estado_caja' => $estadoCajaFila,
            'colaboradores_id' => (int)$row['colaboradores_id'],
            'fecha' => $row['fecha'],
            'fecha_registro' => $row['fecha_registro'],
            'cuenta' => $row['cuenta'] ?? '',
            'factura_egreso' => $row['factura_egreso'] ?? '',
            'total_egreso' => number_format((float)($row['total_egreso'] ?? 0), 2, '.', ''),
            'puede_reintegrar' => $puede_reintegrar
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Retiros cargados correctamente.',
    'resumen' => [
        'modo' => $modo,
        'apertura_id' => $apertura_id,
        'estado_caja' => $estado_caja,
        'fechai' => $fechai,
        'fechaf' => $fechaf,
        'total_retiros' => number_format($total_retiros, 2, '.', '')
    ],
    'data' => $data
]);