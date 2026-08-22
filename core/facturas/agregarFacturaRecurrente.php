<?php
// Ubicación: core/facturas/agregarFacturaRecurrente.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

function responderRecurrencia($ok, $msg, array $extra = [])
{
    echo json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$cn = null;
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderRecurrencia(false, 'Método no permitido.');
    }

    $mainModel = new mainModel();
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error'])) {
        responderRecurrencia(false, $validacion['mensaje'] ?? 'Sesión inválida.');
    }

    $empresaId = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $usuarioId = (int)($_SESSION['colaborador_id_sd'] ?? 0);
    $data = json_decode((string)($_POST['data'] ?? ''), true);
    if ($empresaId <= 0 || $usuarioId <= 0 || !is_array($data)) {
        responderRecurrencia(false, 'Sesión o datos inválidos.');
    }

    $clienteId = (int)($data['clientes_id'] ?? 0);
    $colaboradorId = (int)($data['colaboradores_id'] ?? 0);
    $tipoDocumento = ((int)($data['tipo_documento'] ?? 0) === 1) ? 1 : 0;
    $tipoFactura = 2; // Toda recurrencia se genera al crédito.
    $notas = mb_substr(trim((string)($data['notas'] ?? '')), 0, 255);
    $fechaDolar = (string)($data['fecha_dolar'] ?? date('Y-m-d'));
    $periodicidad = (string)($data['periodicidad'] ?? 'monthly');
    $inicioEntrada = str_replace('T', ' ', (string)($data['start_at'] ?? ''));
    $hasta = trim((string)($data['until'] ?? ''));
    $hasta = $hasta === '' ? null : $hasta;
    $enviarCorreo = ((int)($data['enviar_correo'] ?? 1) === 1) ? 1 : 2;
    $detalle = $data['detalle'] ?? [];

    if ($clienteId <= 0 || $colaboradorId <= 0 || empty($detalle) || !is_array($detalle)) {
        responderRecurrencia(false, 'Debe seleccionar cliente, vendedor y al menos un producto.');
    }
    if (!in_array($periodicidad, ['once', 'daily', 'weekly', 'monthly'], true)) {
        responderRecurrencia(false, 'La periodicidad no es válida.');
    }

    $inicio = DateTime::createFromFormat('Y-m-d H:i', substr($inicioEntrada, 0, 16));
    if (!$inicio) {
        responderRecurrencia(false, 'La fecha inicial no es válida.');
    }
    $inicioSql = $inicio->format('Y-m-d H:i:s');
    $diaMes = (int)$inicio->format('d');
    if ($hasta !== null && $hasta < $inicio->format('Y-m-d')) {
        responderRecurrencia(false, 'La fecha final no puede ser anterior a la primera ejecución.');
    }

    $cn = $mainModel->connection();
    $cn->begin_transaction();

    // En IZZY cada empresa utiliza su propia base de datos; clientes no posee empresa_id.
    $stmtCliente = $cn->prepare('SELECT clientes_id FROM clientes WHERE clientes_id = ? LIMIT 1');
    $stmtCliente->bind_param('i', $clienteId);
    $stmtCliente->execute();
    $clienteValido = $stmtCliente->get_result();
    $stmtCliente->close();
    if (!$clienteValido || $clienteValido->num_rows === 0) {
        throw new Exception('El cliente no existe en la base de datos actual.');
    }

    $exOrden = mb_substr(trim((string)($data['exoneracion_orden'] ?? '')), 0, 100);
    $exConstancia = mb_substr(trim((string)($data['exoneracion_constancia'] ?? '')), 0, 100);
    $exSag = mb_substr(trim((string)($data['exoneracion_sag'] ?? '')), 0, 100);
    $exInterno = mb_substr(trim((string)($data['exoneracion_orden_interno'] ?? '')), 0, 100);
    $nextRun = $inicioSql;

    $stmt = $cn->prepare(
        "INSERT INTO facturas_recurrentes
         (empresa_id, clientes_id, colaboradores_id, tipo_documento, tipo_factura,
          notas, fecha_dolar, exoneracion_orden, exoneracion_constancia, exoneracion_sag,
          exoneracion_orden_interno, periodicidad, dia_mes, start_at, next_run_at, until_at,
          estado, enviar_correo, usuario_crea, fecha_crea)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW())"
    );
    if (!$stmt) {
        throw new Exception('No se pudo preparar la recurrencia: '.$cn->error);
    }
    $stmt->bind_param(
        'iiiiisssssssisssii',
        $empresaId, $clienteId, $colaboradorId, $tipoDocumento, $tipoFactura,
        $notas, $fechaDolar, $exOrden, $exConstancia, $exSag, $exInterno,
        $periodicidad, $diaMes, $inicioSql, $nextRun, $hasta, $enviarCorreo, $usuarioId
    );
    if (!$stmt->execute()) {
        throw new Exception('No se pudo guardar la recurrencia: '.$stmt->error);
    }
    $recId = (int)$stmt->insert_id;
    $stmt->close();

    $stmtDetalle = $cn->prepare(
        "INSERT INTO facturas_recurrentes_detalle
         (rec_id, productos_id, producto, cantidad, precio, descuento, isv_valor,
          isv_valor1, medida, almacen_id, precio_real, referencia_producto)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmtDetalle) {
        throw new Exception('No se pudo preparar el detalle recurrente: '.$cn->error);
    }

    foreach ($detalle as $fila) {
        $productoId = (int)($fila['productos_id'] ?? 0);
        $producto = mb_substr(trim((string)($fila['producto'] ?? '')), 0, 255);
        $cantidad = round((float)($fila['cantidad'] ?? 0), 2);
        $precio = round((float)($fila['precio'] ?? 0), 4);
        $descuento = round((float)($fila['descuento'] ?? 0), 4);
        $isv1 = round((float)($fila['isv_valor'] ?? 0), 4);
        $isv2 = round((float)($fila['isv_valor1'] ?? 0), 4);
        $medida = mb_substr(trim((string)($fila['medida'] ?? 'Und')), 0, 50);
        $almacenId = (int)($fila['almacen_id'] ?? 0);
        $precioReal = round((float)($fila['precio_real'] ?? $precio), 4);
        $referencia = mb_substr(trim((string)($fila['referencia_producto'] ?? '')), 0, 255);

        if ($productoId <= 0 || $producto === '' || $cantidad <= 0 || $precio < 0) {
            throw new Exception('Uno de los productos de la recurrencia no es válido.');
        }

        $stmtDetalle->bind_param(
            'iisdddddsids',
            $recId, $productoId, $producto, $cantidad, $precio, $descuento,
            $isv1, $isv2, $medida, $almacenId, $precioReal, $referencia
        );
        if (!$stmtDetalle->execute()) {
            throw new Exception('No se pudo guardar un producto recurrente: '.$stmtDetalle->error);
        }
    }
    $stmtDetalle->close();
    $cn->commit();

    responderRecurrencia(true, 'Factura recurrente creada correctamente.', ['rec_id' => $recId]);
} catch (Throwable $e) {
    if ($cn) {
        $cn->rollback();
    }
    responderRecurrencia(false, 'Error: '.$e->getMessage());
}
