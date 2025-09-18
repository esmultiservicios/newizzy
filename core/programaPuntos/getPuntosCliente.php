<?php
// core/getPuntosCliente.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
    }

    if (!isset($_POST['facturas_id'])) {
        echo json_encode(['success'=>false,'message'=>'Parámetro facturas_id requerido']); exit;
    }

    $mainModel = new mainModel();
    $db = $mainModel->connection();
    $facturas_id = (int)$_POST['facturas_id'];

    // Traer cliente de la factura + puntos + programa
    $sql = "
        SELECT 
            c.clientes_id                         AS cliente_id,
            c.nombre                              AS cliente,
            IFNULL(pc.total_puntos,0)             AS total_puntos,
            IFNULL(pc.programa_puntos_id, 0)      AS programa_puntos_id,
            pp.tipo_calculo,
            pp.monto,
            pp.porcentaje,
            IFNULL(pp.activo,0)                   AS activo
        FROM facturas f
        INNER JOIN clientes c         ON c.clientes_id = f.clientes_id
        LEFT JOIN puntos_cliente pc   ON pc.cliente_id = c.clientes_id
        LEFT JOIN programa_puntos pp  ON pp.id = pc.programa_puntos_id
        WHERE f.facturas_id = ?
        LIMIT 1
    ";

    $st = $db->prepare($sql);
    $st->bind_param('i', $facturas_id);
    $st->execute();
    $rs = $st->get_result();

    if (!$rs || $rs->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Factura/cliente no encontrado']); exit;
    }

    $row = $rs->fetch_assoc();

    // ===== Equivalencia de redención =====
    // En ausencia de un campo dedicado, asumimos 1 punto = 1.00 L.
    // Si luego agregas un campo "valor_por_punto" en programa_puntos,
    // léelo aquí y reemplaza la constante.
    $valor_por_punto = 1.00;

    $resp = [
        'success'            => true,
        'cliente_id'         => (int)$row['cliente_id'],
        'cliente'            => $row['cliente'],
        'puntos_disponibles' => (float)$row['total_puntos'],
        'programa'           => [
            'id'          => (int)$row['programa_puntos_id'],
            'tipo'        => $row['tipo_calculo'],   // 'monto' | 'porcentaje' | NULL
            'monto'       => isset($row['monto']) ? (float)$row['monto'] : null,
            'porcentaje'  => isset($row['porcentaje']) ? (float)$row['porcentaje'] : null,
            'activo'      => (int)$row['activo']
        ],
        'valor_por_punto'    => (float)$valor_por_punto
    ];

    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}