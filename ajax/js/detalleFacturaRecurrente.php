<?php
// Ubicación: core/facturas/detalleFacturaRecurrente.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $mainModel = new mainModel();
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error'])) {
        throw new Exception($validacion['mensaje'] ?? 'Sesión inválida.');
    }

    $empresaId = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $recId = (int)($_POST['rec_id'] ?? $_GET['rec_id'] ?? 0);
    if ($empresaId <= 0 || $recId <= 0) {
        throw new Exception('La recurrencia indicada no es válida.');
    }

    $cn = $mainModel->connection();
    $stmt = $cn->prepare(
        "SELECT fr.rec_id, c.nombre AS cliente,
                IF(fr.tipo_documento = 1, 'Proforma', 'Factura normal') AS documento,
                fr.tipo_factura, fr.notas, fr.periodicidad, fr.start_at,
                fr.next_run_at, fr.until_at, fr.estado, fr.enviar_correo,
                fr.last_run_at, fr.ultimo_facturas_id, fr.ultimo_error
         FROM facturas_recurrentes fr
         INNER JOIN clientes c ON c.clientes_id = fr.clientes_id
         WHERE fr.rec_id = ? AND fr.empresa_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $recId, $empresaId);
    $stmt->execute();
    $recurrente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$recurrente) {
        throw new Exception('No se encontró la recurrencia o no pertenece a esta empresa.');
    }

    $stmt = $cn->prepare(
        "SELECT productos_id, producto, cantidad, precio, descuento,
                isv_valor, isv_valor1, medida, almacen_id,
                ROUND(cantidad * precio, 2) AS subtotal,
                ROUND((cantidad * precio) - descuento + isv_valor + isv_valor1, 2) AS total_linea
         FROM facturas_recurrentes_detalle
         WHERE rec_id = ?
         ORDER BY rec_detalle_id ASC"
    );
    $stmt->bind_param('i', $recId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $detalle = [];
    $totales = ['subtotal' => 0, 'descuento' => 0, 'isv' => 0, 'total' => 0];
    while ($fila = $resultado->fetch_assoc()) {
        $detalle[] = $fila;
        $totales['subtotal'] += (float)$fila['subtotal'];
        $totales['descuento'] += (float)$fila['descuento'];
        $totales['isv'] += (float)$fila['isv_valor'] + (float)$fila['isv_valor1'];
        $totales['total'] += (float)$fila['total_linea'];
    }
    $stmt->close();

    foreach ($totales as $clave => $valor) {
        $totales[$clave] = round($valor, 2);
    }

    echo json_encode([
        'ok' => true,
        'recurrente' => $recurrente,
        'detalle' => $detalle,
        'totales' => $totales
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
