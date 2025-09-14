<?php
// core/llenarDataTableCobrarClientes.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=UTF-8');

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    echo json_encode([
        "echo" => 1,
        "totalrecords" => 0,
        "totaldisplayrecords" => 0,
        "data" => [],
        "notification" => $insMainModel->showNotification([
            "title"   => "Error de sesión",
            "text"    => $validacion['mensaje'],
            "type"    => "error",
            "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
        ]),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ====== ENTRADAS (con defaults para la 1a carga) ======
$estado      = isset($_POST['estado']) ? (int)$_POST['estado'] : 1; // 1=Pendiente
$clientes_id = isset($_POST['clientes_id']) ? trim($_POST['clientes_id']) : ""; // "" = TODOS
$fechai      = isset($_POST['fechai']) ? $_POST['fechai'] : date('Y-m-01');
$fechaf      = isset($_POST['fechaf']) ? $_POST['fechaf'] : date('Y-m-d');

// Si el select está en "Seleccione", llegará "" o "0". Lo tratamos como "sin filtro".
$cli = (is_numeric($clientes_id) ? (int)$clientes_id : 0);
if ($cli <= 0) { $clientes_id = ""; }

// Armar datos para el modelo
$datos = [
    "estado"        => $estado,
    "clientes_id"   => $clientes_id, // "" = sin filtro
    "fechai"        => $fechai,
    "fechaf"        => $fechaf,
    "empresa_id_sd" => $_SESSION['empresa_id_sd'] ?? 0,
];

// ====== CONSULTA ======
$result = $insMainModel->getCuentasporCobrarClientes($datos);

// ====== ARMAR RESPUESTA PARA DATATABLE ======
$data = [];
$totalCredito = 0.0;
$totalAbono   = 0.0;
$totalPend    = 0.0;

if ($result) {
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

        $data[] = [
            "cobrar_clientes_id"   => (int)$row['cobrar_clientes_id'],
            "facturas_id"          => (int)$row['facturas_id'],
            "fecha"                => $row['fecha'],
            "cliente"              => $row['cliente'],
            "numero"               => $row['numero'],
            "numero_ordenamiento"  => (int)$row['number'],
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
}

// Respuesta JSON
echo json_encode([
    "echo"                  => 1,
    "totalrecords"          => count($data),
    "totaldisplayrecords"   => count($data),
    "data"                  => $data
], JSON_UNESCAPED_UNICODE);
