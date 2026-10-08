<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
$cn = $insMainModel->connection();

header('Content-Type: application/json; charset=UTF-8');

function nomina_fecha_valida($valor) {
    if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $valor);
    return ($dt && $dt->format('Y-m-d') === $valor) ? $valor : '';
}

$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;
$estado = ($estado === 1) ? 1 : 0;
$tipoContratoId = isset($_POST['tipo_contrato_id']) ? (int)$_POST['tipo_contrato_id'] : 0;
$pagoPlanificadoId = isset($_POST['pago_planificado_id']) ? (int)$_POST['pago_planificado_id'] : 0;
$fechaInicio = nomina_fecha_valida($_POST['fecha_inicio'] ?? '');
$fechaFin = nomina_fecha_valida($_POST['fecha_fin'] ?? '');

$condiciones = ["n.estado = {$estado}"];

if ($pagoPlanificadoId > 0) {
    $condiciones[] = "n.pago_planificado_id = {$pagoPlanificadoId}";
}

/*
 * El filtro de fechas trabaja por solapamiento de períodos.
 * Ejemplo: una nómina del 01 al 31 de octubre debe aparecer al consultar
 * del 01 al 08 de octubre porque ambos períodos se cruzan.
 */
if ($fechaInicio !== '' && $fechaFin !== '') {
    if ($fechaInicio > $fechaFin) {
        [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
    }
    $fi = $cn->real_escape_string($fechaInicio);
    $ff = $cn->real_escape_string($fechaFin);
    $condiciones[] = "n.fecha_inicio <= '{$ff}' AND n.fecha_fin >= '{$fi}'";
} elseif ($fechaInicio !== '') {
    $fi = $cn->real_escape_string($fechaInicio);
    $condiciones[] = "n.fecha_fin >= '{$fi}'";
} elseif ($fechaFin !== '') {
    $ff = $cn->real_escape_string($fechaFin);
    $condiciones[] = "n.fecha_inicio <= '{$ff}'";
}

/*
 * Si todavía no se han agregado empleados, la nómina no tiene un contrato
 * contra el cual comparar. En ese estado debe seguir visible para permitir
 * continuar el flujo y agregar sus empleados.
 */
if ($tipoContratoId > 0) {
    $condiciones[] = "(
        NOT EXISTS (
            SELECT 1
            FROM nomina_detalles nd0
            WHERE nd0.nomina_id = n.nomina_id
        )
        OR EXISTS (
            SELECT 1
            FROM nomina_detalles nd1
            INNER JOIN contrato c1 ON c1.colaborador_id = nd1.colaboradores_id
            WHERE nd1.nomina_id = n.nomina_id
              AND c1.estado = 1
              AND c1.tipo_contrato_id = {$tipoContratoId}
        )
    )";
}

$sql = "SELECT
            n.nomina_id,
            e.nombre AS empresa,
            n.fecha_inicio,
            n.fecha_fin,
            CASE
                WHEN n.estado = 0 THEN COALESCE((
                    SELECT SUM(nd2.neto)
                    FROM nomina_detalles nd2
                    WHERE nd2.nomina_id = n.nomina_id
                ), n.importe, 0)
                ELSE n.importe
            END AS importe,
            n.notas,
            n.detalle,
            n.pago_planificado_id,
            n.estado,
            n.empresa_id
        FROM nomina n
        INNER JOIN empresa e ON e.empresa_id = n.empresa_id
        WHERE " . implode(" AND ", $condiciones) . "
        ORDER BY n.fecha_registro DESC, n.nomina_id DESC";

$result = $cn->query($sql);
if (!$result) {
    http_response_code(500);
    echo json_encode([
        'echo' => 1,
        'totalrecords' => 0,
        'totaldisplayrecords' => 0,
        'data' => [],
        'status' => 'error',
        'message' => 'No se pudo consultar el listado de nóminas.'
    ]);
    exit;
}

$data = [];
$netoImporte = 0.0;
while ($row = $result->fetch_assoc()) {
    $importe = (float)($row['importe'] ?? 0);
    $netoImporte += $importe;
    $data[] = [
        'nomina_id' => (int)$row['nomina_id'],
        'empresa' => $row['empresa'],
        'fecha_inicio' => $row['fecha_inicio'],
        'fecha_fin' => $row['fecha_fin'],
        'importe' => $importe,
        'notas' => $row['notas'],
        'detalle' => $row['detalle'],
        'pago_planificado_id' => (int)$row['pago_planificado_id'],
        'estado' => (int)$row['estado'],
        'neto_importe' => $netoImporte,
        'empresa_id' => (int)$row['empresa_id'],
    ];
}

echo json_encode([
    'echo' => 1,
    'totalrecords' => count($data),
    'totaldisplayrecords' => count($data),
    'data' => $data,
    'status' => 'success'
], JSON_UNESCAPED_UNICODE);
