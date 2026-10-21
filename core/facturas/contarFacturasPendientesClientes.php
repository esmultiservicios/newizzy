<?php
$peticionAjax = true;
require_once __DIR__ . "/../configGenerales.php";
require_once __DIR__ . "/../mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$users_id = intval($_SESSION['users_id_sd']);

// 1. Obtener server_customers_id del usuario
$conexionPrincipal = $insMainModel->connection();
$queryUsuario = "SELECT server_customers_id FROM users WHERE users_id = ?";
$stmtUsuario = $conexionPrincipal->prepare($queryUsuario);
$stmtUsuario->bind_param("i", $users_id);
$stmtUsuario->execute();
$resultUsuario = $stmtUsuario->get_result();

if ($resultUsuario->num_rows == 0) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Usuario no encontrado'
    ]);
    exit();
}

$usuarioData = $resultUsuario->fetch_assoc();
$serverCustomersId = $usuarioData['server_customers_id'];
$stmtUsuario->close();

// 2. Conectar a la base de datos del cliente
$configCliente = [
    'host' => SERVER,
    'user' => USER,
    'pass' => PASS,
    'name' => DB_MAIN
];

$conexionCliente = $insMainModel->connectToDatabase($configCliente);

if (!$conexionCliente) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit();
}

// 3. Obtener cliente_id
$queryCliente = "SELECT clientes_id FROM server_customers WHERE server_customers_id = ?";
$stmtCliente = $conexionCliente->prepare($queryCliente);
$stmtCliente->bind_param("i", $serverCustomersId);
$stmtCliente->execute();
$resultCliente = $stmtCliente->get_result();

if ($resultCliente->num_rows == 0) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Cliente no encontrado'
    ]);
    exit();
}            

$clienteData = $resultCliente->fetch_assoc();
$clientes_id = $clienteData['clientes_id'];
$stmtCliente->close();

// 4. Contar facturas pendientes:
// - Facturas electrónicas (documento_id = 1) con estado = 3 (Crédito)
// - Proformas (documento_id = 4) que tengan registros en cobrar_clientes con estado = 1 (Pendiente)
$query = "SELECT 
    (
        SELECT COUNT(*) 
        FROM facturas f
        INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
        INNER JOIN documento d ON sf.documento_id = d.documento_id
        WHERE f.clientes_id = ? 
        AND f.estado = '3' 
        AND d.documento_id = 1
    ) + 
    (
        SELECT COUNT(DISTINCT f.facturas_id)
        FROM facturas f
        INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
        INNER JOIN documento d ON sf.documento_id = d.documento_id
        INNER JOIN cobrar_clientes cc ON f.facturas_id = cc.facturas_id
        WHERE f.clientes_id = ?
        AND d.documento_id = 4
        AND cc.estado = 1
    ) AS total_pendientes";

$stmt = $conexionCliente->prepare($query);
$stmt->bind_param("ii", $clientes_id, $clientes_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

/*
 * Avisos de cobranza de IZZY, no de las cuentas por cobrar internas.
 * Regla compartida con el login: estado=1 en DB_MAIN y día 16 para
 * el mes actual. Se muestra el saldo pendiente sin alterarlo.
 */
$avisoFacturacion = null;
$cuentaCodigo = (string) $serverCustomersId;

try {
    $hoy = new DateTimeImmutable('now', new DateTimeZone('America/Tegucigalpa'));
    $dia = (int) $hoy->format('j');
    $periodo = $hoy->format('Y-m');
    $inicioMes = $hoy->format('Y-m-01');

    $consultaSaldo = $conexionCliente->prepare(
        "SELECT COUNT(*) AS pendientes,
                SUM(CASE WHEN DATE(cc.fecha) < ? THEN 1 ELSE 0 END) AS anteriores,
                SUM(CASE WHEN DATE(cc.fecha) >= ? AND DATE(cc.fecha) < DATE_ADD(?, INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS del_mes
         FROM cobrar_clientes cc
         INNER JOIN server_customers sc ON sc.clientes_id = cc.clientes_id
         WHERE sc.server_customers_id = ? AND sc.validar <> 2 AND cc.estado = 1"
    );
    if ($consultaSaldo) {
        $consultaSaldo->bind_param('sssi', $inicioMes, $inicioMes, $inicioMes, $serverCustomersId);
        $consultaSaldo->execute();
        $saldo = $consultaSaldo->get_result()->fetch_assoc() ?: [];
        $consultaSaldo->close();

        $pendientes = (int) ($saldo['pendientes'] ?? 0);
        $anteriores = (int) ($saldo['anteriores'] ?? 0);
        $delMes = (int) ($saldo['del_mes'] ?? 0);

        if ($pendientes > 0) {
            if ($anteriores > 0 || $dia >= 16) {
                $etapa = '16';
                $titulo = 'Pago vencido';
                $mensaje = 'Su cuenta presenta pagos vencidos. Consulte Detalles de Facturación para regularizarla.';
                $tipo = 'vencido';
            } elseif ($dia >= 15) {
                $etapa = '15';
                $titulo = 'Último día para pagar';
                $mensaje = 'Hoy es el último día del plazo de pago. Revise su factura pendiente en Detalles de Facturación.';
                $tipo = 'recordatorio';
            } elseif ($dia >= 10) {
                $etapa = '10';
                $titulo = 'Recordatorio de pago';
                $mensaje = 'Tiene una factura pendiente. Recuerde pagar a más tardar el día 15.';
                $tipo = 'recordatorio';
            } elseif ($dia >= 5) {
                $etapa = '5';
                $titulo = 'Pago pendiente';
                $mensaje = 'Le recordamos que tiene una factura pendiente de pago. El plazo vence el día 15.';
                $tipo = 'recordatorio';
            } elseif ($dia === 1 && $delMes > 0) {
                $etapa = '1';
                $titulo = 'Factura del mes disponible';
                $mensaje = 'La factura de este mes está disponible. Puede consultarla en Detalles de Facturación.';
                $tipo = 'factura';
            } else {
                $etapa = 'pendiente';
                $titulo = 'Facturación disponible';
                $mensaje = 'Tiene documentos pendientes. Consulte Detalles de Facturación.';
                $tipo = 'informacion';
            }

            $avisoFacturacion = [
                'tipo' => $tipo,
                'etapa' => $etapa,
                'periodo' => $periodo,
                'titulo' => $titulo,
                'mensaje' => $mensaje
            ];
        }
    }
} catch (Throwable $error) {
    error_log('IZZY recordatorio facturación: ' . $error->getMessage());
}

echo json_encode([
    'type' => 'success',
    'total_pendientes' => (int) $data['total_pendientes'],
    'cuenta_codigo' => $cuentaCodigo,
    'aviso_facturacion' => $avisoFacturacion
]);
exit();