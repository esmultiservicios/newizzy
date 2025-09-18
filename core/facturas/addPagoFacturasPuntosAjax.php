<?php
// ajax/addPagoFacturasPuntosAjax.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $mainModel = new mainModel();

    // Validar sesión
    $valid = $mainModel->validarSesion();
    if (!empty($valid['error']) && $valid['error']) {
        echo json_encode(['success'=>false,'message'=>$valid['mensaje']]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
    }

    // Campos desde el form de Puntos
    $facturas_id     = isset($_POST['factura_id_puntos']) ? (int)$_POST['factura_id_puntos'] : 0;
    $fecha           = isset($_POST['fecha_puntos']) ? $_POST['fecha_puntos'] : date('Y-m-d');
    $usuario_recibe  = isset($_POST['usuario_puntos']) ? trim($_POST['usuario_puntos']) : '';
    $puntos_usar     = isset($_POST['puntos_a_usar']) ? (int)$_POST['puntos_a_usar'] : 0;
    $importe_puntos  = isset($_POST['importe_puntos']) ? (float)$_POST['importe_puntos'] : 0.0;
    $origen_pago     = isset($_POST['origen_pago']) ? $_POST['origen_pago'] : '0';
    $tipo_factura    = isset($_POST['tipo_factura']) ? (int)$_POST['tipo_factura'] : 1;
    $empresa_id      = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

    if ($facturas_id <= 0 || $puntos_usar <= 0 || $importe_puntos <= 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos para procesar puntos.']); exit;
    }

    $db = $mainModel->connection();
    $db->begin_transaction();

    // Obtener cliente y saldo de la factura
    $sqlInfo = "
        SELECT f.clientes_id, IFNULL(cc.saldo,0) AS saldo
        FROM facturas f
        LEFT JOIN cobrar_clientes cc ON cc.facturas_id = f.facturas_id AND (cc.empresa_id = ? OR ? = 0)
        WHERE f.facturas_id = ?
        LIMIT 1
    ";
    $st = $db->prepare($sqlInfo);
    $st->bind_param('iii', $empresa_id, $empresa_id, $facturas_id);
    $st->execute();
    $rs = $st->get_result();
    if(!$rs || $rs->num_rows===0){
        $db->rollback();
        echo json_encode(['success'=>false,'message'=>'Factura no encontrada.']); exit;
    }
    $row = $rs->fetch_assoc();
    $cliente_id   = (int)$row['clientes_id'];
    $saldo_actual = (float)$row['saldo'];

    // Verificar puntos disponibles
    $sqlPts = "SELECT total_puntos FROM puntos_cliente WHERE cliente_id = ? LIMIT 1 FOR UPDATE";
    $st2 = $db->prepare($sqlPts);
    $st2->bind_param('i', $cliente_id);
    $st2->execute();
    $rs2 = $st2->get_result();

    $total_pts = 0.0;
    if($rs2 && $rs2->num_rows>0){
        $total_pts = (float)$rs2->fetch_assoc()['total_puntos'];
    } else {
        // Si no tiene fila, no puede redimir.
        $db->rollback();
        echo json_encode(['success'=>false,'message'=>'El cliente no tiene puntos acumulados.']); exit;
    }

    if ($puntos_usar > $total_pts) {
        $db->rollback();
        echo json_encode(['success'=>false,'message'=>'Puntos insuficientes.']); exit;
    }

    // No permitir redimir más del saldo
    if ($importe_puntos > $saldo_actual) {
        $importe_puntos = $saldo_actual; // cap
    }

    // 1) Descontar puntos
    $sqlUpdatePts = "UPDATE puntos_cliente 
                        SET total_puntos = total_puntos - ?, fecha_actualizacion = NOW()
                      WHERE cliente_id = ? AND total_puntos >= ?";
    $st3 = $db->prepare($sqlUpdatePts);
    $ptsFloat = (float)$puntos_usar;
    $st3->bind_param('dii', $ptsFloat, $cliente_id, $puntos_usar);
    $st3->execute();
    if ($db->affected_rows <= 0) {
        $db->rollback();
        echo json_encode(['success'=>false,'message'=>'No se pudo descontar puntos.']); exit;
    }

    // 2) Aplicar al saldo
    $sqlUpdSaldo = "UPDATE cobrar_clientes 
                       SET saldo = GREATEST(saldo - ?, 0),
                           estado = CASE WHEN (saldo - ?) <= 0 THEN 2 ELSE estado END
                     WHERE facturas_id = ? " . ($empresa_id ? "AND empresa_id = ?" : "");
    if ($empresa_id) {
        $st4 = $db->prepare($sqlUpdSaldo);
        $st4->bind_param('ddii', $importe_puntos, $importe_puntos, $facturas_id, $empresa_id);
    } else {
        $st4 = $db->prepare($sqlUpdSaldo);
        $st4->bind_param('ddi', $importe_puntos, $importe_puntos, $facturas_id);
    }
    $st4->execute();

    // 3) (Opcional) Insertar en tu tabla de pagos/historial:
    //    Reemplaza 'pagos_facturas' por tu tabla real y adapta los campos.
    /*
    $sqlInsPago = "INSERT INTO pagos_facturas
                   (facturas_id, fecha, metodo, importe, usuario_id, origen_pago, observacion)
                   VALUES (?, ?, 'PUNTOS', ?, ?, ?, ?)";
    $st5 = $db->prepare($sqlInsPago);
    $obs = 'Redención de '.$puntos_usar.' puntos';
    $usuario_id = (int)$usuario_recibe ?: NULL;
    $st5->bind_param('isdiss', $facturas_id, $fecha, $importe_puntos, $usuario_id, $origen_pago, $obs);
    $st5->execute();
    */

    // Nuevo saldo para devolver al frontend
    $sqlSaldo = "SELECT IFNULL(saldo,0) AS saldo FROM cobrar_clientes WHERE facturas_id = ? " . ($empresa_id ? "AND empresa_id = ?" : "") . " LIMIT 1";
    if ($empresa_id) {
        $st6 = $db->prepare($sqlSaldo);
        $st6->bind_param('ii', $facturas_id, $empresa_id);
    } else {
        $st6 = $db->prepare($sqlSaldo);
        $st6->bind_param('i', $facturas_id);
    }
    $st6->execute();
    $rs6 = $st6->get_result();
    $nuevo_saldo = 0.0;
    if ($rs6 && $rs6->num_rows>0) {
        $nuevo_saldo = (float)$rs6->fetch_assoc()['saldo'];
    }

    // Puntos restantes
    $sqlRest = "SELECT total_puntos FROM puntos_cliente WHERE cliente_id = ? LIMIT 1";
    $st7 = $db->prepare($sqlRest);
    $st7->bind_param('i', $cliente_id);
    $st7->execute();
    $rs7 = $st7->get_result();
    $pts_restantes = 0.0;
    if ($rs7 && $rs7->num_rows>0) {
        $pts_restantes = (float)$rs7->fetch_assoc()['total_puntos'];
    }

    $db->commit();

    echo json_encode([
        'success'          => true,
        'message'          => 'Pago con puntos aplicado correctamente.',
        'importe_aplicado' => (float)$importe_puntos,
        'nuevo_saldo'      => (float)$nuevo_saldo,
        'puntos_restantes' => (float)$pts_restantes
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->errno===0) { /* noop */ }
    if (isset($db)) { $db->rollback(); }
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}