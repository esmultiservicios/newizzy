<?php
// core/llenarDataTableCobrarClientes.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    echo $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text"  => $validacion['mensaje'],
        "type"  => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
    exit;
}

$datos = [
    "estado"        => $_POST['estado'],
    "clientes_id"   => $_POST['clientes_id'],
    "fechai"        => $_POST['fechai'],
    "fechaf"        => $_POST['fechaf'],
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
];

$result = $insMainModel->getCuentasporCobrarClientes($datos);

$data = [];
$totalCredito = 0.0;
$totalAbono   = 0.0;
$totalPend    = 0.0;

while ($row = $result->fetch_assoc()) {
    // Abonos de la factura
    $resAb = $insMainModel->getAbonosCobrarClientes($row['facturas_id']);
    $rowAb = $resAb ? $resAb->fetch_assoc() : null;

    $abono   = isset($rowAb['total']) && $rowAb['total'] !== null ? (float)$rowAb['total'] : 0.0;
    $credito = (float)$row['importe'];
    $saldo   = $credito - $abono;

    $totalCredito += $credito;
    $totalAbono   += $abono;
    $totalPend    += $saldo;

    $numero_ordenamiento = (int)$row['number'];

    $data[] = [
        "cobrar_clientes_id"   => (int)$row['cobrar_clientes_id'],
        "facturas_id"          => (int)$row['facturas_id'],
        "fecha"                => $row['fecha'],
        "cliente"              => $row['cliente'],
        "numero"               => $row['numero'],
        "numero_ordenamiento"  => $numero_ordenamiento,
        "credito"              => round($credito, 2),
        "abono"                => round($abono, 2),
        "saldo"                => round($saldo, 2),
        "estado"               => (int)$row['estado'],
        "tipo_factura"         => (int)$row['tipo_factura'],
        "total_credito"        => round($totalCredito, 2),
        "total_abono"          => round($totalAbono, 2),
        "total_pendiente"      => round($totalPend, 2),
        "vendedor"             => $row['vendedor'],
    ];
}

echo json_encode([
    "echo"                  => 1,
    "totalrecords"          => count($data),
    "totaldisplayrecords"   => count($data),
    "data"                  => $data
], JSON_UNESCAPED_UNICODE);