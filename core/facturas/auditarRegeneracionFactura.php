<?php
// core/facturas/auditarRegeneracionFactura.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

function responderAuditoriaRegeneracion($success, $message) {
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['name' => 'SD']);
    }

    $mainModel = new mainModel();
    $validacion = $mainModel->validarSesion();

    if (!empty($validacion['error'])) {
        responderAuditoriaRegeneracion(false, $validacion['mensaje'] ?? 'Sesión inválida.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        responderAuditoriaRegeneracion(false, 'Método no permitido.');
    }

    $facturas_id = isset($_POST['facturas_id']) ? (int)$_POST['facturas_id'] : 0;
    $accion = isset($_POST['accion']) ? trim((string)$_POST['accion']) : '';
    $empresa_id = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $colaborador_id = (int)($_SESSION['colaborador_id_sd'] ?? 0);

    if ($facturas_id <= 0 || $empresa_id <= 0 || $colaborador_id <= 0) {
        responderAuditoriaRegeneracion(false, 'Datos de auditoría inválidos.');
    }

    if ($accion !== 'cargar_plantilla') {
        responderAuditoriaRegeneracion(false, 'Acción de auditoría no permitida.');
    }

    $cn = $mainModel->connection();

    if (!$cn) {
        responderAuditoriaRegeneracion(false, 'No se pudo conectar a la base de datos.');
    }

    // Solo se puede auditar como regeneración una factura realmente anulada
    // y perteneciente a la empresa de la sesión.
    $stmt = $cn->prepare("\n        SELECT f.number, sf.prefijo, sf.relleno\n        FROM facturas f\n        LEFT JOIN secuencia_facturacion sf\n          ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id\n        WHERE f.facturas_id = ?\n          AND f.empresa_id = ?\n          AND f.estado = 4\n        LIMIT 1\n    ");

    if (!$stmt) {
        throw new Exception('No se pudo preparar la validación de la factura.');
    }

    $stmt->bind_param('ii', $facturas_id, $empresa_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        responderAuditoriaRegeneracion(false, 'La factura no existe, no pertenece a la empresa o no está anulada.');
    }

    $row = $res->fetch_assoc();
    $stmt->close();

    $numero = (int)($row['number'] ?? 0);
    $prefijo = trim((string)($row['prefijo'] ?? ''));
    $relleno = (int)($row['relleno'] ?? 0);
    $numeroCompleto = $prefijo . ($relleno > 0
        ? str_pad($numero, $relleno, '0', STR_PAD_LEFT)
        : $numero
    );

    $datosHistorial = [
        'modulo' => 'Facturación',
        'colaboradores_id' => $colaborador_id,
        'status' => 'Regeneración',
        'observacion' => "Se cargó la factura anulada {$numeroCompleto} (ID {$facturas_id}) como plantilla editable para generar una nueva factura."
    ];

    $mainModel->guardarHistorial($datosHistorial);

    responderAuditoriaRegeneracion(true, 'Auditoría registrada correctamente.');

} catch (Throwable $e) {
    error_log('Error en auditarRegeneracionFactura.php: ' . $e->getMessage());
    responderAuditoriaRegeneracion(false, 'No se pudo registrar la auditoría de regeneración.');
}
