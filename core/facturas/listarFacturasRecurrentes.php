<?php
// Ubicación: core/facturas/listarFacturasRecurrentes.php
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
    $cn = $mainModel->connection();
    $stmt = $cn->prepare(
        "SELECT fr.rec_id, c.nombre AS cliente,
                IF(fr.tipo_documento = 1, 'Proforma', 'Factura normal') AS documento,
                fr.tipo_factura, fr.notas, fr.start_at, fr.periodicidad,
                fr.next_run_at, fr.until_at, fr.estado, fr.enviar_correo,
                fr.ultimo_facturas_id, fr.last_run_at, fr.ultimo_error,
                COALESCE(rd.cantidad_productos, 0) AS cantidad_productos,
                COALESCE(rd.total_estimado, 0) AS total_estimado
         FROM facturas_recurrentes fr
         INNER JOIN clientes c ON c.clientes_id = fr.clientes_id
         LEFT JOIN (
             SELECT rec_id,
                    COUNT(*) AS cantidad_productos,
                    ROUND(SUM((cantidad * precio) - descuento + isv_valor + isv_valor1), 2) AS total_estimado
             FROM facturas_recurrentes_detalle
             GROUP BY rec_id
         ) rd ON rd.rec_id = fr.rec_id
         WHERE fr.empresa_id = ?
         ORDER BY fr.estado ASC, fr.next_run_at ASC, fr.rec_id DESC"
    );
    $stmt->bind_param('i', $empresaId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $fila['detalle'] = [];
        $datos[] = $fila;
    }
    $stmt->close();

    $detallePorRecurrencia = [];
    $stmt = $cn->prepare(
        "SELECT frd.rec_id, frd.productos_id, frd.producto, frd.cantidad,
                frd.precio, frd.descuento, frd.isv_valor, frd.isv_valor1,
                frd.medida, frd.almacen_id,
                ROUND(frd.cantidad * frd.precio, 2) AS subtotal,
                ROUND((frd.cantidad * frd.precio) - frd.descuento + frd.isv_valor + frd.isv_valor1, 2) AS total_linea
         FROM facturas_recurrentes_detalle frd
         INNER JOIN facturas_recurrentes fr ON fr.rec_id = frd.rec_id
         WHERE fr.empresa_id = ?
         ORDER BY frd.rec_id ASC, frd.rec_detalle_id ASC"
    );
    $stmt->bind_param('i', $empresaId);
    $stmt->execute();
    $resultadoDetalle = $stmt->get_result();
    while ($filaDetalle = $resultadoDetalle->fetch_assoc()) {
        $idRecurrencia = (int)$filaDetalle['rec_id'];
        if (!isset($detallePorRecurrencia[$idRecurrencia])) {
            $detallePorRecurrencia[$idRecurrencia] = [];
        }
        $detallePorRecurrencia[$idRecurrencia][] = $filaDetalle;
    }
    $stmt->close();

    foreach ($datos as &$fila) {
        $fila['detalle'] = $detallePorRecurrencia[(int)$fila['rec_id']] ?? [];
    }
    unset($fila);

    echo json_encode(['ok' => true, 'data' => $datos], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'data' => [], 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
