<?php
// core/facturas/cerrarProforma.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Método no permitido','estado'=>false]); exit;
    }

    $mainModel  = new mainModel();
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error']) && $validacion['error']) {
        echo json_encode(['success'=>false,'type'=>'error','title'=>'Error de sesión','message'=>$validacion['mensaje'],'estado'=>false]); exit;
    }

    $empresa_id           = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $facturas_proforma_id = isset($_POST['facturas_proforma_id']) ? (int)$_POST['facturas_proforma_id'] : 0;
    $facturas_id          = isset($_POST['facturas_id'])          ? (int)$_POST['facturas_id']          : 0;

    if ($empresa_id <= 0) {
        echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Empresa inválida','estado'=>false]); exit;
    }
    if ($facturas_proforma_id <= 0 && $facturas_id <= 0) {
        echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Falta facturas_proforma_id o facturas_id','estado'=>false]); exit;
    }

    $cn = $mainModel->connection();
    $cn->begin_transaction();

    // Si no vino facturas_proforma_id, deducirlo
    if ($facturas_proforma_id <= 0) {
        $qFind = "SELECT facturas_proforma_id, facturas_id, estado
                  FROM facturas_proforma
                  WHERE facturas_id = ? AND empresa_id = ?
                  ORDER BY facturas_proforma_id DESC
                  LIMIT 1";
        $st = $cn->prepare($qFind);
        $st->bind_param("ii", $facturas_id, $empresa_id);
        if (!$st->execute()) { $cn->rollback(); throw new Exception('Error al buscar proforma'); }
        $rs = $st->get_result();
        if ($rs->num_rows === 0) {
            $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'No existe proforma para esta factura','estado'=>false]); exit;
        }
        $pfRow = $rs->fetch_assoc();
        $st->close();

        $facturas_proforma_id = (int)$pfRow['facturas_proforma_id'];
        if ($facturas_id <= 0) $facturas_id = (int)$pfRow['facturas_id'];
    }

    // Confirmar que la factura es PROFORMA
    $qDoc = "SELECT d.documento_id
             FROM facturas f
             INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
             INNER JOIN documento d            ON sf.documento_id = d.documento_id
             WHERE f.facturas_id = ? AND f.empresa_id = ?
             LIMIT 1";
    $st = $cn->prepare($qDoc);
    $st->bind_param("ii", $facturas_id, $empresa_id);
    if (!$st->execute()) { $cn->rollback(); throw new Exception('Error al validar documento'); }
    $rs = $st->get_result();
    if ($rs->num_rows === 0) { $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Factura no encontrada','estado'=>false]); exit; }
    $doc = $rs->fetch_assoc();
    $st->close();
    if ((int)$doc['documento_id'] !== 4) {
        $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'El documento no es una proforma','estado'=>false]); exit;
    }

    // Traer estado actual de la proforma
    $qPf = "SELECT facturas_id, estado
            FROM facturas_proforma
            WHERE facturas_proforma_id = ? AND empresa_id = ?
            LIMIT 1";
    $st = $cn->prepare($qPf);
    $st->bind_param("ii", $facturas_proforma_id, $empresa_id);
    if (!$st->execute()) { $cn->rollback(); throw new Exception('Error al consultar proforma'); }
    $rs = $st->get_result();
    if ($rs->num_rows === 0) { $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Proforma no encontrada','estado'=>false]); exit; }
    $pf = $rs->fetch_assoc();
    $st->close();

    if ((int)$pf['estado'] !== 0) {
        $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'La proforma ya está cerrada','estado'=>false]); exit;
    }

    // Cerrar proforma 0 -> 1
    $qUpd = "UPDATE facturas_proforma
             SET estado = 1
             WHERE facturas_proforma_id = ? AND empresa_id = ? AND estado = 0";
    $st = $cn->prepare($qUpd);
    $st->bind_param("ii", $facturas_proforma_id, $empresa_id);
    if (!$st->execute()) { $cn->rollback(); throw new Exception('Error al cerrar proforma'); }
    if ($st->affected_rows === 0) {
        $cn->rollback(); echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'No se pudo cerrar la proforma','estado'=>false]); exit;
    }
    $st->close();

    // cobrar_clientes: 1 -> 2 y saldo = 0 (si existe)
    $fact_id_for_cobro = (int)$pf['facturas_id'];
    $qCob = "UPDATE cobrar_clientes
             SET estado = 2, saldo = 0
             WHERE facturas_id = ? AND empresa_id = ? AND estado = 1";
    $st = $cn->prepare($qCob);
    $st->bind_param("ii", $fact_id_for_cobro, $empresa_id);
    if (!$st->execute()) { $cn->rollback(); throw new Exception('Error al actualizar cobrar_clientes'); }
    $st->close();

    $cn->commit();
    echo json_encode(['success'=>true,'type'=>'success','title'=>'Éxito','message'=>'Proforma cerrada correctamente','estado'=>true]); exit;

} catch (Throwable $e) {
    if (isset($cn)) { $cn->rollback(); }
    echo json_encode(['success'=>false,'type'=>'error','title'=>'Error','message'=>'Error: '.$e->getMessage(),'estado'=>false]); exit;
}
