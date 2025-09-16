<?php
// core/facturas/cerrarProforma.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

require_once '../configGenerales.php';
require_once '../mainModel.php';

try {
    $insMainModel = new mainModel();

    // Validar sesión
    $validacion = $insMainModel->validarSesion();
    if ($validacion['error']) {
        echo json_encode([
            'success' => false,
            'title'   => 'Sesión inválida',
            'message' => $validacion['mensaje']
        ]);
        exit;
    }

    // Entrada
    $facturas_proforma_id = isset($_POST['facturas_proforma_id']) ? (int)$_POST['facturas_proforma_id'] : 0;
    $facturas_id          = isset($_POST['facturas_id']) ? (int)$_POST['facturas_id'] : 0;
    $comentario           = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    $cn = $insMainModel->connection();
    if (!$cn) {
        echo json_encode(['success' => false, 'title'=>'Error de conexión', 'message'=>'No se pudo conectar a la base de datos.']);
        exit;
    }

    // Resolver IDs si falta alguno
    if ($facturas_proforma_id <= 0 && $facturas_id > 0) {
        $q = $cn->prepare("SELECT facturas_proforma_id FROM facturas_proforma WHERE facturas_id = ? ORDER BY facturas_proforma_id DESC LIMIT 1");
        $q->bind_param('i', $facturas_id);
        $q->execute();
        $r = $q->get_result();
        if ($r && $row = $r->fetch_assoc()) $facturas_proforma_id = (int)$row['facturas_proforma_id'];
        if ($r) $r->free();
        $q->close();
    } elseif ($facturas_id <= 0 && $facturas_proforma_id > 0) {
        $q = $cn->prepare("SELECT facturas_id FROM facturas_proforma WHERE facturas_proforma_id = ? LIMIT 1");
        $q->bind_param('i', $facturas_proforma_id);
        $q->execute();
        $r = $q->get_result();
        if ($r && $row = $r->fetch_assoc()) $facturas_id = (int)$row['facturas_id'];
        if ($r) $r->free();
        $q->close();
    }

    if ($facturas_proforma_id <= 0 && $facturas_id <= 0) {
        echo json_encode([
            'success' => false,
            'title'   => 'Datos incompletos',
            'message' => 'Debe enviar facturas_proforma_id o facturas_id.'
        ]);
        exit;
    }

    // NOTA: MyISAM no soporta transacciones; se usa igualmente para compatibilidad
    @$cn->begin_transaction();

    // 1) Cerrar proforma => estado = 2 (CERRADA)
    $afectadas_proforma = 0;
    if ($facturas_proforma_id > 0) {
        $sql1 = "UPDATE facturas_proforma SET estado = 2 WHERE facturas_proforma_id = ?";
        $stmt1 = $cn->prepare($sql1);
        $stmt1->bind_param('i', $facturas_proforma_id);
        $stmt1->execute();
        $afectadas_proforma = $stmt1->affected_rows;
        $stmt1->close();
    } elseif ($facturas_id > 0) {
        $sql1b = "UPDATE facturas_proforma SET estado = 2 WHERE facturas_id = ?";
        $stmt1b = $cn->prepare($sql1b);
        $stmt1b->bind_param('i', $facturas_id);
        $stmt1b->execute();
        $afectadas_proforma = $stmt1b->affected_rows;
        $stmt1b->close();
    }

    // 2) Cerrar cuenta por cobrar relacionada (saldo=0, estado=2)
    $afectadas_cxc = 0;
    if ($facturas_id > 0) {
        $sql2 = "UPDATE cobrar_clientes SET saldo = 0, estado = 2 WHERE facturas_id = ? AND estado IN (1,2)";
        $stmt2 = $cn->prepare($sql2);
        $stmt2->bind_param('i', $facturas_id);
        $stmt2->execute();
        $afectadas_cxc = $stmt2->affected_rows;
        $stmt2->close();
    }

    // Log
    $log = "Cierre de proforma";
    if ($facturas_proforma_id > 0) $log .= " ID:$facturas_proforma_id";
    if ($facturas_id > 0)          $log .= " (Factura ID:$facturas_id)";
    if ($comentario !== '')        $log .= ". Nota: ".$comentario;
    $insMainModel->guardar_historial_accesos($log);

    @$cn->commit();

    echo json_encode([
        'success' => true,
        'title'   => 'Proforma cerrada',
        'message' => 'Se marcó la proforma como CERRADA y se cerró la cuenta por cobrar.',
        'affected_rows' => [
            'facturas_proforma' => $afectadas_proforma,
            'cobrar_clientes'   => $afectadas_cxc
        ]
    ]);

} catch (Throwable $e) {
    if (isset($cn) && $cn) { @$cn->rollback(); }
    echo json_encode([
        'success' => false,
        'title'   => 'Error',
        'message' => $e->getMessage()
    ]);
}