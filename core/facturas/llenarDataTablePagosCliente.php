<?php
// core/facturas/llenarDataTablePagosCliente.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    echo json_encode(['echo'=>1,'totalrecords'=>0,'totaldisplayrecords'=>0,'data'=>[]]);
    exit;
}

$empresa_id = $_SESSION['empresa_id_sd'] ?? 0;

$fechai     = $_POST['fechai']      ?? date('Y-m-01');
$fechaf     = $_POST['fechaf']      ?? date('Y-m-d');
$cliente_id = $_POST['cliente_id']  ?? ''; // si viene vacío, traer TODOS

$cn = $insMainModel->connection();
if (!$cn) {
    echo json_encode(['echo'=>1,'totalrecords'=>0,'totaldisplayrecords'=>0,'data'=>[]]);
    exit;
}

/*
  Campos devueltos:
  - pagos_id, fecha_pago, numero, facturas_id, fecha_factura, total_factura
  - aplicado (p.importe), efectivo, tarjeta, cambio
  - metodo, tipo, estado, usuario
*/

$sql = "
    SELECT 
        p.pagos_id,
        DATE_FORMAT(p.fecha,'%d/%m/%Y')     AS fecha_pago,
        f.facturas_id,
        DATE_FORMAT(f.fecha,'%d/%m/%Y')     AS fecha_factura,
        f.importe                            AS total_factura,
        p.importe                            AS aplicado,
        p.efectivo,
        p.tarjeta,
        p.cambio,
        CASE 
            WHEN p.efectivo > 0 AND p.tarjeta = 0 THEN 'Efectivo'
            WHEN p.efectivo = 0 AND p.tarjeta > 0 THEN 'Tarjeta'
            WHEN p.efectivo > 0 AND p.tarjeta > 0 THEN 'Mixto'
            ELSE 'Otro'
        END                                  AS metodo,
        CASE WHEN f.tipo_factura = 1 THEN 'Contado' ELSE 'Crédito' END AS tipo,
        CASE WHEN p.estado = 1 THEN 'Pagado' ELSE 'Cancelado' END      AS estado,
        COALESCE(co.nombre,'—')              AS usuario,
        CONCAT(sf.prefijo, LPAD(f.number, sf.relleno, '0')) AS numero
    FROM pagos p
    INNER JOIN facturas f ON f.facturas_id = p.facturas_id AND f.empresa_id = p.empresa_id
    INNER JOIN secuencia_facturacion sf ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
    LEFT JOIN colaboradores co ON co.colaboradores_id = p.usuario
    WHERE p.empresa_id = ?
      AND p.fecha BETWEEN ? AND ?
";

$params = [$empresa_id, $fechai, $fechaf];
$types  = "iss";

if (!empty($cliente_id)) {
    $sql   .= " AND f.clientes_id = ? ";
    $params[] = (int)$cliente_id;
    $types  .= "i";
}

$sql .= " ORDER BY p.fecha DESC, p.pagos_id DESC ";

$stmt = $cn->prepare($sql);
if (!$stmt) {
    echo json_encode(['echo'=>1,'totalrecords'=>0,'totaldisplayrecords'=>0,'data'=>[]]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rs = $stmt->get_result();

$data = [];
while ($row = $rs->fetch_assoc()) {
    $data[] = [
        'accion'        => '', // la llenamos en JS con botón si quieres
        'fecha_pago'    => $row['fecha_pago'],
        'numero'        => $row['numero'],
        'fecha_factura' => $row['fecha_factura'],
        'total_factura' => (float)$row['total_factura'],
        'aplicado'      => (float)$row['aplicado'],
        'efectivo'      => (float)$row['efectivo'],
        'tarjeta'       => (float)$row['tarjeta'],
        'cambio'        => (float)$row['cambio'],
        'metodo'        => $row['metodo'],
        'tipo'          => $row['tipo'],
        'estado'        => $row['estado'],
        'usuario'       => $row['usuario'],
        'facturas_id'   => (int)$row['facturas_id'],
        'pagos_id'      => (int)$row['pagos_id']
    ];
}

$stmt->close();

echo json_encode([
    'echo'                => 1,
    'totalrecords'        => count($data),
    'totaldisplayrecords' => count($data),
    'data'                => $data
]);