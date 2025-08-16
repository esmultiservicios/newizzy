<?php
// core/cerrarProforma.php
$peticionAjax = true;
require_once __DIR__ . '/configGenerales.php';
require_once __DIR__ . '/mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $mainModel = new mainModel();

    // Validar sesión
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error']) && $validacion['error']) {
        echo json_encode(["success" => false, "message" => $validacion['mensaje']]); exit;
    }

    if (!isset($_POST['facturas_id'])) {
        echo json_encode(["success" => false, "message" => "Parámetro facturas_id requerido."]); exit;
    }

    $empresa_id  = $_SESSION['empresa_id_sd'];
    $facturas_id = (int)$_POST['facturas_id'];

    $db = $mainModel->connection(); // <- NO estático

    // 1) Verificar que sea PROFORMA y que esté Abierta (fp.estado = 0)
    $sqlCheck = "
        SELECT d.documento_id, IFNULL(fp.estado, -1) AS proforma_estado
        FROM facturas f
        INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
        INNER JOIN documento d            ON sf.documento_id = d.documento_id
        LEFT JOIN facturas_proforma fp    ON fp.facturas_id = f.facturas_id AND fp.empresa_id = f.empresa_id
        WHERE f.facturas_id = ? AND f.empresa_id = ?
        LIMIT 1
    ";
    $res = $mainModel->ejecutar_consulta_simple_preparada($sqlCheck, "ii", [$facturas_id, $empresa_id]);
    if (!$res || $res->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Factura no encontrada."]); exit;
    }
    $row = $res->fetch_assoc();

    if ((int)$row['documento_id'] !== 4) {
        echo json_encode(["success" => false, "message" => "El documento no es una proforma."]); exit;
    }
    if ((int)$row['proforma_estado'] !== 0) {
        echo json_encode(["success" => false, "message" => "La proforma ya está cerrada o no existe."]); exit;
    }

    // 2) Cerrar proforma: facturas_proforma.estado 0 -> 1
    $sqlUpdProforma = "UPDATE facturas_proforma
                          SET estado = 1
                        WHERE facturas_id = ? AND empresa_id = ? AND estado = 0";
    $ok1 = $mainModel->ejecutar_consulta_simple_preparada($sqlUpdProforma, "ii", [$facturas_id, $empresa_id]);
    if (!$ok1 || $db->affected_rows === 0) {
        echo json_encode(["success" => false, "message" => "No se pudo cerrar la proforma."]); exit;
    }

    // 3) cobrar_clientes: estado 1 -> 2 y saldo = 0 (si existe fila)
    $sqlUpdCobrar = "UPDATE cobrar_clientes
                        SET estado = 2, saldo = 0
                      WHERE facturas_id = ? AND empresa_id = ? AND estado = 1";
    $mainModel->ejecutar_consulta_simple_preparada($sqlUpdCobrar, "ii", [$facturas_id, $empresa_id]);
    // Nota: aunque no afecte filas, no es error (puede no existir registro).

    echo json_encode(["success" => true, "message" => "Proforma cerrada correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
