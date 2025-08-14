<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$response = [
    'type' => 'error',
    'title' => 'Error',
    'message' => 'Error desconocido',
    'pago_id' => null,
    'estado' => false
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos');
    }
    
    $required = ['facturaId', 'efectivo', 'transferencia', 'tarjeta', 'cambio'];
    foreach ($required as $f) {
        if (!isset($data[$f])) {
            throw new Exception("Campo requerido faltante: $f");
        }
    }

    $efectivo = max(0, floatval($data['efectivo']));
    $transferencia = max(0, floatval($data['transferencia']));
    $tarjeta = max(0, floatval($data['tarjeta']));
    $cambio = max(0, floatval($data['cambio']));

    $cn = $mainModel->connection();
    $cn->begin_transaction();

    // Validar factura
    $qFactura = "SELECT importe, estado FROM facturas WHERE facturas_id = ? FOR UPDATE";
    $st = $cn->prepare($qFactura);
    $st->bind_param("i", $data['facturaId']);
    if (!$st->execute()) throw new Exception('Error al consultar la factura: ' . $st->error);
    $res = $st->get_result();
    if ($res->num_rows === 0) throw new Exception('Factura no encontrada');

    $row = $res->fetch_assoc();
    $totalFactura = floatval($row['importe']);
    $estadoFactura = intval($row['estado']); // 1=registrada; 2=pagada, etc. (ajusta a tu catálogo)
    $st->close();

    if ($estadoFactura == 2) {
        throw new Exception('La factura ya está pagada');
    }

    $totalPago = $efectivo + $transferencia + $tarjeta;
    if ($totalPago + 0.0001 < $totalFactura) {
        throw new Exception('El pago no cubre el total de la factura');
    }

    // Insertar pago principal
    $qPago = "INSERT INTO pagos (
        facturas_id, 
        fecha, 
        efectivo, 
        tarjeta, 
        cambio, 
        estado,
        transferencia
    ) VALUES (?, NOW(), ?, ?, ?, 1, ?)";

    $stp = $cn->prepare($qPago);
    $stp->bind_param("idddd", $data['facturaId'], $efectivo, $tarjeta, $cambio, $transferencia);
    if (!$stp->execute()) throw new Exception('Error al registrar el pago: ' . $stp->error);
    $pagoId = $cn->insert_id;
    $stp->close();

    // Detalle tarjeta (tipo 2)
    if ($tarjeta > 0) {
        $qDetT = "INSERT INTO pagos_detalles (pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1)
                  VALUES (?, 2, 0, ?, 'Pago con tarjeta')";
        $stt = $cn->prepare($qDetT);
        $stt->bind_param("id", $pagoId, $tarjeta);
        if (!$stt->execute()) throw new Exception('Error al registrar detalle de tarjeta: ' . $stt->error);
        $stt->close();
    }

    // Detalle transferencia (tipo 3)
    if ($transferencia > 0) {
        $qDetTr = "INSERT INTO pagos_detalles (pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1)
                   VALUES (?, 3, 0, ?, 'Pago por transferencia')";
        $sttr = $cn->prepare($qDetTr);
        $sttr->bind_param("id", $pagoId, $transferencia);
        if (!$sttr->execute()) throw new Exception('Error al registrar detalle de transferencia: ' . $sttr->error);
        $sttr->close();
    }

    // Actualizar estado factura a pagada
    $qUpd = "UPDATE facturas 
             SET estado = 2, fecha_pago = NOW()
             WHERE facturas_id = ?";
    $stu = $cn->prepare($qUpd);
    $stu->bind_param("i", $data['facturaId']);
    if (!$stu->execute()) throw new Exception('Error al actualizar la factura: ' . $stu->error);
    $stu->close();

    $cn->commit();

    echo json_encode([
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Pago registrado correctamente',
        'pago_id' => $pagoId,
        'success' => true,
        'estado' => true
    ]);
    exit;

} catch (Exception $e) {
    if (isset($cn)) $cn->rollback();
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'success' => false,
        'estado' => false
    ]);
    exit;
}