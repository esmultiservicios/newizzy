<?php
header('Content-Type: application/json; charset=utf-8');
$peticionAjax = true;

function responderImpresora($codigo, $exito, $mensaje) {
    http_response_code($codigo);
    echo json_encode(['success' => $exito, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/configGenerales.php';
    require_once __DIR__ . '/mainModel.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderImpresora(405, false, 'Método no permitido.');
    }

    $modelo = new mainModel();
    $sesion = $modelo->validarSesion();
    if (!is_array($sesion) || !empty($sesion['error'])) {
        responderImpresora(401, false, 'Sesión inválida. Inicie sesión nuevamente.');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_VALIDATE_INT);
    if ($id === null || $id === false || $id < 0 || !in_array($estado, [0, 1], true)) {
        responderImpresora(422, false, 'Identificador o estado inválido.');
    }

    $db = $modelo->connection();
    if ($db instanceof mysqli) {
        $consulta = $db->prepare('SELECT tipo FROM impresora WHERE impresora_id = ?');
        if (!$consulta) { throw new RuntimeException($db->error); }
        $consulta->bind_param('i', $id);
        if (!$consulta->execute()) { throw new RuntimeException($consulta->error); }
        $consulta->bind_result($tipo);
        $existe = $consulta->fetch();
        $consulta->close();
        if (!$existe) { responderImpresora(404, false, 'Configuración no encontrada.'); }

        // La tabla impresora es MyISAM: ejecutar actualizaciones comprobadas sin transacciones ficticias.
        if ($estado === 1 && in_array((int) $tipo, [6, 7], true)) {
            $otro = (int) $tipo === 6 ? 7 : 6;
            $stmt = $db->prepare('UPDATE impresora SET estado = 0 WHERE tipo = ?');
            if (!$stmt) { throw new RuntimeException($db->error); }
            $stmt->bind_param('i', $otro);
            if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
            $stmt->close();
        }

        $stmt = $db->prepare('UPDATE impresora SET estado = ?, fecha_registro = NOW() WHERE impresora_id = ?');
        if (!$stmt) { throw new RuntimeException($db->error); }
        $stmt->bind_param('ii', $estado, $id);
        if (!$stmt->execute()) { throw new RuntimeException($stmt->error); }
        $stmt->close();
    } elseif ($db instanceof PDO) {
        $consulta = $db->prepare('SELECT tipo FROM impresora WHERE impresora_id = ?');
        $consulta->execute([$id]);
        $tipo = $consulta->fetchColumn();
        if ($tipo === false) { responderImpresora(404, false, 'Configuración no encontrada.'); }

        if ($estado === 1 && in_array((int) $tipo, [6, 7], true)) {
            $otro = (int) $tipo === 6 ? 7 : 6;
            $stmt = $db->prepare('UPDATE impresora SET estado = 0 WHERE tipo = ?');
            if (!$stmt->execute([$otro])) { throw new RuntimeException('No se pudo desactivar el formato alternativo.'); }
        }
        $stmt = $db->prepare('UPDATE impresora SET estado = ?, fecha_registro = NOW() WHERE impresora_id = ?');
        if (!$stmt->execute([$estado, $id])) { throw new RuntimeException('No se pudo guardar el estado.'); }
    } else {
        throw new RuntimeException('La conexión del modelo no es mysqli ni PDO.');
    }

    responderImpresora(200, true, 'Configuración actualizada correctamente.');
} catch (Throwable $error) {
    error_log('editarImpresora.php: ' . $error->getMessage());
    responderImpresora(500, false, 'No fue posible guardar el cambio. Consulte el registro PHP.');
}
