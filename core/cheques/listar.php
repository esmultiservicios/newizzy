<?php
require_once __DIR__ . '/_bootstrap.php';

$sesion = chequesSesion();
$db = chequesDb();
chequesValidarInstalacion($db);

$fechai = isset($_POST['fechai']) ? trim($_POST['fechai']) : date('Y-m-01');
$fechaf = isset($_POST['fechaf']) ? trim($_POST['fechaf']) : date('Y-m-d');
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$cuentaId = isset($_POST['cuentas_id']) ? (int)$_POST['cuentas_id'] : 0;
$proveedorId = isset($_POST['proveedores_id']) ? (int)$_POST['proveedores_id'] : 0;
$categoriaId = isset($_POST['categoria_gastos_id']) ? (int)$_POST['categoria_gastos_id'] : 0;

if (!chequesFechaValida($fechai)) $fechai = date('Y-m-01');
if (!chequesFechaValida($fechaf)) $fechaf = date('Y-m-d');

if ($fechai > $fechaf) {
    $tmp = $fechai;
    $fechai = $fechaf;
    $fechaf = $tmp;
}

$sql = "
    SELECT
        ch.cheque_id,
        ch.numero_cheque,
        ch.cuentas_id,
        ch.proveedores_id,
        ch.empresa_id,
        ch.tipo_pago_id,
        ch.egresos_id,
        ch.categoria_gastos_id,
        ch.movimientos_cuentas_id,
        ch.movimiento_reintegro_id,
        ch.fecha,
        ch.factura,
        ch.importe,
        ch.observacion,
        ch.estado,
        ch.colaboradores_id,
        ch.fecha_registro,
        ch.fecha_anulacion,
        ch.colaboradores_anula_id,
        ch.motivo_anulacion,
        COALESCE(p.nombre, 'Sin proveedor') AS proveedor,
        COALESCE(c.codigo, '') AS cuenta_codigo,
        COALESCE(c.nombre, 'Cuenta no encontrada') AS cuenta_nombre,
        COALESCE(cg.nombre, 'Sin categoría') AS categoria
    FROM cheque ch
    LEFT JOIN proveedores p ON p.proveedores_id = ch.proveedores_id
    LEFT JOIN cuentas c ON c.cuentas_id = ch.cuentas_id
    LEFT JOIN categoria_gastos cg ON cg.categoria_gastos_id = ch.categoria_gastos_id
    WHERE ch.empresa_id = ?
      AND ch.fecha >= ?
      AND ch.fecha <= ?
";

$types = 'iss';
$params = [$sesion['empresa_id'], $fechai, $fechaf];

if ($estado === '0' || $estado === '1') {
    $sql .= " AND ch.estado = ?";
    $types .= 'i';
    $params[] = (int)$estado;
}
if ($cuentaId > 0) {
    $sql .= " AND ch.cuentas_id = ?";
    $types .= 'i';
    $params[] = $cuentaId;
}
if ($proveedorId > 0) {
    $sql .= " AND ch.proveedores_id = ?";
    $types .= 'i';
    $params[] = $proveedorId;
}
if ($categoriaId > 0) {
    $sql .= " AND ch.categoria_gastos_id = ?";
    $types .= 'i';
    $params[] = $categoriaId;
}

$sql .= " ORDER BY ch.fecha DESC, ch.cheque_id DESC";

$stmt = $db->prepare($sql);
if (!$stmt) {
    chequesJson(['success'=>false,'title'=>'Error','message'=>'No se pudo preparar la consulta de cheques.'],500);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['cheque_id'] = (int)$row['cheque_id'];
    $row['cuentas_id'] = (int)$row['cuentas_id'];
    $row['proveedores_id'] = (int)$row['proveedores_id'];
    $row['categoria_gastos_id'] = (int)$row['categoria_gastos_id'];
    $row['estado'] = (int)$row['estado'];
    $row['importe_valor'] = round((float)$row['importe'], 2);
    $row['importe'] = 'L. ' . number_format((float)$row['importe'], 2);
    $data[] = $row;
}
$stmt->close();

chequesJson([
    'success' => true,
    'data' => $data,
    'totalrecords' => count($data)
]);
