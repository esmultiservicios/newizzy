<?php
// core/facturas/agregarFacturaRecurrente.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
    }

    $mainModel  = new mainModel();
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error']) && $validacion['error']) {
        echo json_encode(['ok'=>false,'msg'=>$validacion['mensaje']]); exit;
    }

    $empresa_id = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $usuario_id = (int)($_SESSION['colaborador_id_sd'] ?? 0);

    if ($empresa_id<=0 || $usuario_id<=0) {
        echo json_encode(['ok'=>false,'msg'=>'Sesión inválida']); exit;
    }

    if (!isset($_POST['data'])) {
        echo json_encode(['ok'=>false,'msg'=>'Payload vacío']); exit;
    }

    $data = json_decode($_POST['data'], true);
    if (!$data) {
        echo json_encode(['ok'=>false,'msg'=>'JSON inválido']); exit;
    }

    // Datos básicos
    $clientes_id      = (int)($data['clientes_id'] ?? 0);
    $colaboradores_id = (int)($data['colaboradores_id'] ?? 0);
    $notas            = $mainModel->cleanString($data['notas'] ?? '');
    $fecha_dolar      = $mainModel->cleanString($data['fecha_dolar'] ?? date('Y-m-d'));
    $tipo_documento   = (int)($data['tipo_documento'] ?? 0); // 0 normal, 1 proforma
    $tipo_factura     = (int)($data['tipo_factura'] ?? 2);  // 1 contado, 2 crédito
    $start_at         = $mainModel->cleanString($data['start_at'] ?? date('Y-m-d H:i:s'));
    $periodicidad     = $mainModel->cleanString($data['periodicidad'] ?? 'monthly');
    $until_at         = !empty($data['until']) ? $mainModel->cleanString($data['until']) : null;

    if ($clientes_id<=0 || $colaboradores_id<=0) {
        echo json_encode(['ok'=>false,'msg'=>'Cliente o colaborador inválido']); exit;
    }

    $cn = $mainModel->connection();
    $cn->begin_transaction();

    // Insert encabezado
    $sql = "INSERT INTO facturas_recurrentes
        (empresa_id, clientes_id, colaboradores_id, tipo_documento, tipo_factura,
         notas, fecha_dolar, exoneracion_orden, exoneracion_constancia, exoneracion_sag,
         exoneracion_orden_interno, periodicidad, start_at, next_run_at, until_at,
         estado, usuario_crea, fecha_crea)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,NOW())";

    $stmt = $cn->prepare($sql);
    $next_run = $start_at; // la primera vez = start_at
    $stmt->bind_param("iiiisssssssssisi",
        $empresa_id, $clientes_id, $colaboradores_id, $tipo_documento, $tipo_factura,
        $notas, $fecha_dolar,
        $data['exoneracion_orden'], $data['exoneracion_constancia'],
        $data['exoneracion_sag'], $data['exoneracion_orden_interno'],
        $periodicidad, $start_at, $next_run, $until_at,
        $usuario_id
    );
    if (!$stmt->execute()) {
        $cn->rollback();
        throw new Exception("Error al guardar encabezado: ".$stmt->error);
    }
    $rec_id = $stmt->insert_id;
    $stmt->close();

    // Insert detalle
    if (!empty($data['detalle']) && is_array($data['detalle'])) {
        $sqlDet = "INSERT INTO facturas_recurrentes_detalle
            (rec_id, productos_id, producto, cantidad, precio, descuento, isv_valor, medida, almacen_id)
            VALUES (?,?,?,?,?,?,?,?,?)";
        $stmtDet = $cn->prepare($sqlDet);
        foreach ($data['detalle'] as $det) {
            $stmtDet->bind_param("iissdddsi",
                $rec_id,
                $det['productos_id'],
                $det['producto'],
                $det['cantidad'],
                $det['precio'],
                $det['descuento'],
                $det['isv_valor'],
                $det['medida'],
                $det['almacen_id']
            );
            if (!$stmtDet->execute()) {
                $cn->rollback();
                throw new Exception("Error al guardar detalle: ".$stmtDet->error);
            }
        }
        $stmtDet->close();
    }

    $cn->commit();
    echo json_encode(['ok'=>true,'msg'=>'Factura recurrente creada','rec_id'=>$rec_id]); exit;

} catch (Throwable $e) {
    if (isset($cn)) $cn->rollback();
    echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]); exit;
}