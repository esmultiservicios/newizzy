<?php
//llenarDataTableCajaDisponibles.php
$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$fechai = isset($_POST['fechai']) ? $_POST['fechai'] : date('Y-m-d');
$fechaf = isset($_POST['fechaf']) ? $_POST['fechaf'] : date('Y-m-d');
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$solo_mi_caja = isset($_POST['solo_mi_caja']) ? (int)$_POST['solo_mi_caja'] : 0;
$origen = isset($_POST['origen']) ? trim($_POST['origen']) : '';

if ($fechai == "") {
    $fechai = date('Y-m-d');
}

if ($fechaf == "") {
    $fechaf = $fechai;
}

$where = "a.empresa_id = '$empresa_id'";

if ($solo_mi_caja === 1 && $origen === 'facturacion') {
    $where .= " AND a.colaboradores_id = '$colaboradores_id'";
}

if ($estado === 0) {
    $where .= " AND (
        (a.estado = 2 AND a.fecha BETWEEN '$fechai' AND '$fechaf')
        OR
        (a.estado = 1)
    )";
} else {
    $where .= " AND a.estado = '$estado'
                AND a.fecha BETWEEN '$fechai' AND '$fechaf'";
}

$sql = "
    SELECT
        a.apertura_id,
        a.fecha,
        a.factura_inicial,
        a.factura_final,
        a.apertura AS monto_apertura,
        a.neto,
        a.estado,
        CASE 
            WHEN a.estado = 1 THEN 'Activa'
            WHEN a.estado = 2 THEN 'Inactiva'
            ELSE 'Desconocida'
        END AS caja,
        COALESCE(c.nombre, '') AS usuario
    FROM apertura a
    LEFT JOIN colaboradores c
        ON c.colaboradores_id = a.colaboradores_id
    WHERE $where
    ORDER BY 
        CASE 
            WHEN a.estado = 1 THEN 1
            WHEN a.estado = 2 THEN 2
            ELSE 3
        END,
        a.fecha DESC,
        a.apertura_id DESC
";

$result = $insMainModel->ejecutar_consulta_simple($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $apertura_id = (int)$row['apertura_id'];

        $result_venta = $insMainModel->getImporteVentaporUsuario($apertura_id);
        $row1 = $result_venta ? $result_venta->fetch_assoc() : null;

        $importe_venta = 0;

        if ($row1 && isset($row1['importe'])) {
            $importe_venta = (float)$row1['importe'];
        }

        $sqlRetiros = "
            SELECT COALESCE(SUM(monto), 0) AS total_retiros
            FROM caja_retiros
            WHERE apertura_id = '$apertura_id'
              AND empresa_id = '$empresa_id'
              AND estado = 1
        ";

        $result_retiros = $insMainModel->ejecutar_consulta_simple($sqlRetiros);
        $row_retiros = $result_retiros ? $result_retiros->fetch_assoc() : null;

        $retiro_caja = 0;

        if ($row_retiros && isset($row_retiros['total_retiros'])) {
            $retiro_caja = (float)$row_retiros['total_retiros'];
        }

        $factura_inicial = "";

        if ($row['factura_inicial'] == "") {
            $result_facturaInicial = $insMainModel->getFacturaInicial($apertura_id);
            $row_facturaInicial = $result_facturaInicial ? $result_facturaInicial->fetch_assoc() : null;

            if ($row_facturaInicial && is_array($row_facturaInicial)) {
                $factura_inicial = $row_facturaInicial['prefijo'] . "" . str_pad($row_facturaInicial['numero'], $row_facturaInicial['relleno'], "0", STR_PAD_LEFT);
            }
        } else {
            $factura_inicial = $row['factura_inicial'];
        }

        $monto_apertura = isset($row['monto_apertura']) ? (float)$row['monto_apertura'] : 0;
        $neto = ($monto_apertura + $importe_venta) - $retiro_caja;

        $data[] = [
            "apertura_id" => $apertura_id,
            "fecha" => $row['fecha'],
            "factura_inicial" => $factura_inicial,
            "factura_final" => $row['factura_final'],
            "caja" => $row['caja'],
            "estado" => (int)$row['estado'],
            "usuario" => $row['usuario'],
            "monto_apertura" => $monto_apertura,
            "importe_venta" => $importe_venta,
            "retiro_caja" => $retiro_caja,
            "neto" => $neto
        ];
    }
}

$arreglo = [
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
];

echo json_encode($arreglo);