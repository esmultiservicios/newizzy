<?php
// core/productos/editarBarcodeAjax.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$out = [
    'success' => false,
    'status'  => false,
    'message' => 'Error desconocido'
];

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!isset($_SESSION['user_sd'])) {
            session_start(['name' => 'SD']);
        } else {
            session_start();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método no permitido');
    }

    $cn = (new mainModel())->connection();

    if (!$cn) {
        throw new Exception('Sin conexión a BD');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $productos_id = isset($_POST['productos_id']) ? intval($_POST['productos_id']) : 0;
    $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';

    if ($productos_id <= 0) {
        throw new Exception('No se recibió el ID del producto');
    }

    if ($barcode === '') {
        throw new Exception('El código de barra es obligatorio');
    }

    if ($barcode === '0') {
        throw new Exception('El código de barra no puede ser cero');
    }

    if (mb_strlen($barcode, 'UTF-8') > 100) {
        throw new Exception('El código de barra no puede superar los 100 caracteres');
    }

    $cn->begin_transaction();

    $sqlProducto = "SELECT productos_id, nombre, barCode, empresa_id
                    FROM productos
                    WHERE productos_id = ?
                    LIMIT 1
                    FOR UPDATE";

    $stmtProducto = $cn->prepare($sqlProducto);
    $stmtProducto->bind_param("i", $productos_id);
    $stmtProducto->execute();
    $resultProducto = $stmtProducto->get_result();

    if ($resultProducto->num_rows === 0) {
        $stmtProducto->close();
        throw new Exception('El producto no existe');
    }

    $producto = $resultProducto->fetch_assoc();
    $stmtProducto->close();

    $empresa_id = intval($producto['empresa_id']);
    $barcode_actual = trim($producto['barCode']);

    if ($barcode_actual === $barcode) {
        $cn->commit();

        echo json_encode([
            'success' => true,
            'status'  => true,
            'message' => 'El código de barra no cambió porque es el mismo que ya tenía el producto',
            'productos_id' => $productos_id,
            'barcode' => $barcode
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sqlExiste = "SELECT productos_id, nombre
                  FROM productos
                  WHERE barCode = ?
                    AND productos_id <> ?
                    AND empresa_id = ?
                  LIMIT 1";

    $stmtExiste = $cn->prepare($sqlExiste);
    $stmtExiste->bind_param("sii", $barcode, $productos_id, $empresa_id);
    $stmtExiste->execute();
    $resultExiste = $stmtExiste->get_result();

    if ($resultExiste->num_rows > 0) {
        $productoDuplicado = $resultExiste->fetch_assoc();
        $stmtExiste->close();

        throw new Exception(
            'El código de barra ya está asignado al producto: ' . $productoDuplicado['nombre']
        );
    }

    $stmtExiste->close();

    $sqlUpdate = "UPDATE productos
                  SET barCode = ?
                  WHERE productos_id = ?
                    AND empresa_id = ?";

    $stmtUpdate = $cn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sii", $barcode, $productos_id, $empresa_id);
    $stmtUpdate->execute();

    if ($stmtUpdate->affected_rows < 1) {
        $stmtUpdate->close();
        throw new Exception('No se pudo actualizar el código de barra');
    }

    $stmtUpdate->close();

    $cn->commit();

    echo json_encode([
        'success' => true,
        'status'  => true,
        'message' => 'Código de barra actualizado correctamente',
        'productos_id' => $productos_id,
        'barcode' => $barcode
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if (isset($cn) && $cn instanceof mysqli) {
        try {
            if ($cn->errno === 0 || $cn->errno > 0) {
                $cn->rollback();
            }
        } catch (Throwable $rollbackError) {
        }
    }

    $out['success'] = false;
    $out['status']  = false;
    $out['message'] = 'Error: ' . $e->getMessage();

    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}