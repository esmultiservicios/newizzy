<?php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$inicioProceso = microtime(true);

function responderAnulacionFactura($success, $message, $extra = []) {
    $base = [
        "success" => (bool)$success,
        "status" => (bool)$success,
        "message" => $message
    ];

    echo json_encode(array_merge($base, $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    date_default_timezone_set('America/Tegucigalpa');

    $validacion = $insMainModel->validarSesion();

    if ($validacion['error']) {
        responderAnulacionFactura(false, $validacion['mensaje'], [
            "redirect" => $validacion['redireccion']
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderAnulacionFactura(false, 'Método no permitido');
    }

    $facturas_id = isset($_POST['facturas_id']) ? (int)$_POST['facturas_id'] : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($facturas_id <= 0) {
        responderAnulacionFactura(false, 'Factura inválida');
    }

    if ($comentario === '') {
        responderAnulacionFactura(false, 'Debe ingresar un comentario para anular la factura');
    }

    $cn = $insMainModel->connection();

    if (!$cn) {
        responderAnulacionFactura(false, 'No se pudo conectar a la base de datos');
    }

    $cn->set_charset("utf8mb4");
    $cn->begin_transaction();

    /*
        1. Consultar factura.

        Importante:
        NO usamos FOR UPDATE aquí para luego llamar otro método que abra otra conexión.
        En este archivo anulamos la factura directamente con la misma conexión.
    */
    $sqlFactura = "
        SELECT
            f.facturas_id,
            f.clientes_id,
            f.secuencia_facturacion_id,
            f.number,
            f.estado,
            f.empresa_id,
            sf.prefijo,
            sf.relleno
        FROM facturas f
        LEFT JOIN secuencia_facturacion sf
            ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
        WHERE f.facturas_id = ?
        LIMIT 1
    ";

    $stmtFactura = $cn->prepare($sqlFactura);

    if (!$stmtFactura) {
        throw new Exception("Error preparando consulta de factura: " . $cn->error);
    }

    $stmtFactura->bind_param("i", $facturas_id);

    if (!$stmtFactura->execute()) {
        throw new Exception("Error consultando la factura: " . $stmtFactura->error);
    }

    $resultFactura = $stmtFactura->get_result();

    if (!$resultFactura || $resultFactura->num_rows <= 0) {
        throw new Exception("No se encontró la factura");
    }

    $factura = $resultFactura->fetch_assoc();
    $stmtFactura->close();

    $clientes_id = (int)$factura['clientes_id'];
    $empresa_id = (int)$factura['empresa_id'];
    $numero_factura = (int)$factura['number'];
    $estado_factura = (int)$factura['estado'];

    if ($estado_factura === 4) {
        throw new Exception("Esta factura ya está anulada");
    }

    /*
        2. Armar número completo de factura.

        Ejemplo:
        prefijo = 000-001-01-
        number = 114
        relleno = 8
        resultado = 000-001-01-00000114
    */
    $prefijo = isset($factura['prefijo']) ? trim((string)$factura['prefijo']) : '';
    $relleno = isset($factura['relleno']) ? (int)$factura['relleno'] : 0;

    if ($relleno > 0) {
        $no_factura = $prefijo . str_pad($numero_factura, $relleno, "0", STR_PAD_LEFT);
    } else {
        $no_factura = $prefijo . $numero_factura;
    }

    if (trim($no_factura) === '') {
        $no_factura = (string)$numero_factura;
    }

    /*
        3. Documentos de salida que puede tener movimientos.

        Tu facturación actual guarda:
        Factura {facturas_id}

        Y productos relacionados:
        Factura {facturas_id}_0
        Factura {facturas_id}_1

        Ojo:
        El _ en LIKE es comodín, por eso se escapa.
    */
    $documentoFacturaPorId = "Factura " . $facturas_id;
    $documentoFacturaPorIdLike = "Factura " . $facturas_id . "\\_%";

    $documentoFacturaPorNumero = (string)$numero_factura;
    $documentoFacturaCompleta = (string)$no_factura;

    $documentoFacturaTextoNumero = "Factura " . $numero_factura;
    $documentoFacturaTextoCompleta = "Factura " . $no_factura;

    /*
        4. Documento de entrada por anulación.
    */
    $documentoAnulacion = "ANULA " . $no_factura;

    if (strlen($documentoAnulacion) > 50) {
        $documentoAnulacion = substr($documentoAnulacion, 0, 50);
    }

    /*
        5. Validar devolución previa.
    */
    $sqlValidarDevolucion = "
        SELECT movimientos_id
        FROM movimientos
        WHERE empresa_id = ?
          AND documento = ?
          AND cantidad_entrada > 0
        LIMIT 1
    ";

    $stmtValidarDevolucion = $cn->prepare($sqlValidarDevolucion);

    if (!$stmtValidarDevolucion) {
        throw new Exception("Error preparando validación de devolución: " . $cn->error);
    }

    $stmtValidarDevolucion->bind_param("is", $empresa_id, $documentoAnulacion);

    if (!$stmtValidarDevolucion->execute()) {
        throw new Exception("Error validando devolución de inventario: " . $stmtValidarDevolucion->error);
    }

    $resultValidarDevolucion = $stmtValidarDevolucion->get_result();

    if ($resultValidarDevolucion && $resultValidarDevolucion->num_rows > 0) {
        throw new Exception("Esta factura ya tiene devolución de inventario registrada");
    }

    $stmtValidarDevolucion->close();

    /*
        6. Buscar salidas originales de inventario.
    */
    $sqlSalidas = "
        SELECT
            movimientos_id,
            productos_id,
            documento,
            cantidad_salida,
            almacen_id,
            lote_id
        FROM movimientos
        WHERE empresa_id = ?
          AND cantidad_salida > 0
          AND (
                documento = ?
             OR documento LIKE ? ESCAPE '\\\\'
             OR documento = ?
             OR documento = ?
             OR documento = ?
             OR documento = ?
          )
        ORDER BY movimientos_id ASC
    ";

    $stmtSalidas = $cn->prepare($sqlSalidas);

    if (!$stmtSalidas) {
        throw new Exception("Error preparando consulta de movimientos de salida: " . $cn->error);
    }

    $stmtSalidas->bind_param(
        "issssss",
        $empresa_id,
        $documentoFacturaPorId,
        $documentoFacturaPorIdLike,
        $documentoFacturaPorNumero,
        $documentoFacturaCompleta,
        $documentoFacturaTextoNumero,
        $documentoFacturaTextoCompleta
    );

    if (!$stmtSalidas->execute()) {
        throw new Exception("Error consultando movimientos de salida: " . $stmtSalidas->error);
    }

    $resultSalidas = $stmtSalidas->get_result();

    /*
        7. Anular factura directamente usando la misma conexión.

        Evita el bloqueo/lentitud de:
        SELECT ... FOR UPDATE
        +
        anular_factura() con otra conexión.
    */
    $sqlAnularFactura = "
        UPDATE facturas
        SET estado = 4
        WHERE facturas_id = ?
          AND empresa_id = ?
          AND estado <> 4
        LIMIT 1
    ";

    $stmtAnularFactura = $cn->prepare($sqlAnularFactura);

    if (!$stmtAnularFactura) {
        throw new Exception("Error preparando anulación de factura: " . $cn->error);
    }

    $stmtAnularFactura->bind_param("ii", $facturas_id, $empresa_id);

    if (!$stmtAnularFactura->execute()) {
        throw new Exception("Error anulando factura: " . $stmtAnularFactura->error);
    }

    if ($stmtAnularFactura->affected_rows <= 0) {
        throw new Exception("La factura no se pudo anular o ya estaba anulada");
    }

    $stmtAnularFactura->close();

    /*
        8. Preparar correlativo de movimientos una sola vez.

        Tu movimientos_id no es AUTO_INCREMENT.
        Por eso se toma el último y se incrementa en memoria.
    */
    $nuevo_movimientos_id = 1;

    $sqlUltimoMovimiento = "
        SELECT movimientos_id
        FROM movimientos
        ORDER BY movimientos_id DESC
        LIMIT 1
        FOR UPDATE
    ";

    $resultUltimoMovimiento = $cn->query($sqlUltimoMovimiento);

    if (!$resultUltimoMovimiento) {
        throw new Exception("Error consultando último movimiento: " . $cn->error);
    }

    if ($resultUltimoMovimiento->num_rows > 0) {
        $rowUltimoMovimiento = $resultUltimoMovimiento->fetch_assoc();
        $nuevo_movimientos_id = ((int)$rowUltimoMovimiento['movimientos_id']) + 1;
    }

    /*
        9. Devolver inventario.
    */
    $totalProductosDevueltos = 0;
    $totalMovimientosDevueltos = 0;

    if ($resultSalidas && $resultSalidas->num_rows > 0) {
        while ($salida = $resultSalidas->fetch_assoc()) {
            $productos_id = (int)$salida['productos_id'];
            $cantidad_devolver = (int)$salida['cantidad_salida'];
            $almacen_id = (int)$salida['almacen_id'];
            $lote_id = (int)$salida['lote_id'];

            if ($productos_id <= 0 || $cantidad_devolver <= 0) {
                continue;
            }

            $sqlSaldo = "
                SELECT saldo
                FROM movimientos
                WHERE productos_id = ?
                  AND almacen_id = ?
                  AND lote_id = ?
                  AND empresa_id = ?
                ORDER BY movimientos_id DESC
                LIMIT 1
            ";

            $stmtSaldo = $cn->prepare($sqlSaldo);

            if (!$stmtSaldo) {
                throw new Exception("Error preparando consulta de saldo: " . $cn->error);
            }

            $stmtSaldo->bind_param("iiii", $productos_id, $almacen_id, $lote_id, $empresa_id);

            if (!$stmtSaldo->execute()) {
                throw new Exception("Error consultando saldo actual: " . $stmtSaldo->error);
            }

            $resultSaldo = $stmtSaldo->get_result();

            $saldo_actual = 0;

            if ($resultSaldo && $resultSaldo->num_rows > 0) {
                $rowSaldo = $resultSaldo->fetch_assoc();
                $saldo_actual = (int)$rowSaldo['saldo'];
            }

            $stmtSaldo->close();

            $nuevo_saldo = $saldo_actual + $cantidad_devolver;

            $comentarioMovimiento = "Entrada por anulación de factura " . $no_factura . ". Comentario: " . $comentario;

            if (strlen($comentarioMovimiento) > 254) {
                $comentarioMovimiento = substr($comentarioMovimiento, 0, 254);
            }

            $fechaRegistro = date("Y-m-d H:i:s");

            $sqlInsertMovimiento = "
                INSERT INTO movimientos (
                    movimientos_id,
                    productos_id,
                    documento,
                    cantidad_entrada,
                    cantidad_salida,
                    saldo,
                    empresa_id,
                    fecha_registro,
                    clientes_id,
                    comentario,
                    almacen_id,
                    lote_id
                ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    0,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmtInsertMovimiento = $cn->prepare($sqlInsertMovimiento);

            if (!$stmtInsertMovimiento) {
                throw new Exception("Error preparando entrada por anulación: " . $cn->error);
            }

            $stmtInsertMovimiento->bind_param(
                "iisiiisisii",
                $nuevo_movimientos_id,
                $productos_id,
                $documentoAnulacion,
                $cantidad_devolver,
                $nuevo_saldo,
                $empresa_id,
                $fechaRegistro,
                $clientes_id,
                $comentarioMovimiento,
                $almacen_id,
                $lote_id
            );

            if (!$stmtInsertMovimiento->execute()) {
                throw new Exception("Error registrando entrada por anulación: " . $stmtInsertMovimiento->error);
            }

            $stmtInsertMovimiento->close();

            $nuevo_movimientos_id++;

            $totalProductosDevueltos += $cantidad_devolver;
            $totalMovimientosDevueltos++;
        }
    }

    if ($stmtSalidas) {
        $stmtSalidas->close();
    }

    /*
        10. Anular pago.

        Se deja con tus métodos actuales porque no compartiste la estructura
        de pagos. Ya no debería quedar bloqueado por la factura porque la
        anulación de factura se hizo en la misma conexión.
    */
    $pago_anulado = false;
    $pago_encontrado = false;

    $resultPagos = $insMainModel->valid_pago_factura($facturas_id);

    if ($resultPagos && $resultPagos->num_rows > 0) {
        $pago_encontrado = true;
        $insMainModel->anular_pago_factura($facturas_id);
        $pago_anulado = true;
    }

    /*
        11. Historial.
    */
    $observacion = "El número de factura " . $no_factura . " ha sido anulada correctamente segun comentario: " . $comentario;

    if ($totalMovimientosDevueltos > 0) {
        $observacion .= ". Inventario devuelto: " . $totalProductosDevueltos . " unidades en " . $totalMovimientosDevueltos . " movimiento(s).";
    } else {
        $observacion .= ". No se encontraron movimientos de salida para devolver inventario.";
    }

    if ($pago_encontrado) {
        $observacion .= ". Pago anulado correctamente.";
    }

    $datos = [
        "modulo" => "Facturación",
        "colaboradores_id" => $_SESSION['colaborador_id_sd'],
        "status" => "Anulada",
        "observacion" => $observacion,
    ];

    $insMainModel->guardarHistorial($datos);

    $cn->commit();

    $duracion = round(microtime(true) - $inicioProceso, 4);

    responderAnulacionFactura(true, "La factura ha sido anulada correctamente", [
        "facturas_id" => $facturas_id,
        "factura" => $no_factura,
        "inventario_devuelto" => $totalMovimientosDevueltos > 0,
        "total_movimientos_devueltos" => $totalMovimientosDevueltos,
        "total_productos_devueltos" => $totalProductosDevueltos,
        "pago_encontrado" => $pago_encontrado,
        "pago_anulado" => $pago_anulado,
        "duracion_segundos" => $duracion
    ]);

} catch (Throwable $e) {
    if (isset($cn) && $cn) {
        $cn->rollback();
    }

    error_log("Error en anularFactura.php: " . $e->getMessage());

    $duracion = round(microtime(true) - $inicioProceso, 4);

    responderAnulacionFactura(false, "Error: " . $e->getMessage(), [
        "duracion_segundos" => $duracion
    ]);
}