<?php
// anularFactura.php
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

function cerrarStmtSeguro($stmt) {
    if ($stmt) {
        $stmt->close();
    }
}

function limitarTextoAnulacion($texto, $limite) {
    $texto = (string)$texto;

    if (function_exists('mb_substr')) {
        return mb_substr($texto, 0, $limite, 'UTF-8');
    }

    return substr($texto, 0, $limite);
}

function ejecutarUpdateAnulacion($cn, $sql, $types = '', $params = []) {
    $stmt = $cn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando actualización: " . $cn->error);
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Error ejecutando actualización: " . $error);
    }

    $affected = $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

function obtenerEnteroConsultaAnulacion($cn, $sql, $types = '', $params = [], $campo = '') {
    $stmt = $cn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $cn->error);
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Error ejecutando consulta: " . $error);
    }

    $res = $stmt->get_result();
    $valor = 0;

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($campo !== '' && isset($row[$campo])) {
            $valor = (int)$row[$campo];
        } else {
            $primero = reset($row);
            $valor = (int)$primero;
        }
    }

    $stmt->close();
    return $valor;
}

function buscarProformaAnulacion($cn, $facturas_id, $empresa_id, $secuencia_facturacion_id, $numero_factura) {
    $sql = "
        SELECT
            facturas_proforma_id,
            facturas_id,
            numero,
            estado
        FROM facturas_proforma
        WHERE facturas_id = ?
           OR (
                empresa_id = ?
            AND secuencia_facturacion_id = ?
            AND numero = ?
           )
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $cn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando consulta de proforma: " . $cn->error);
    }

    $stmt->bind_param("iiii", $facturas_id, $empresa_id, $secuencia_facturacion_id, $numero_factura);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Error consultando la proforma: " . $error);
    }

    $res = $stmt->get_result();
    $proforma = null;

    if ($res && $res->num_rows > 0) {
        $proforma = $res->fetch_assoc();
    }

    $stmt->close();
    return $proforma;
}

function construirVariantesDocumentoAnulacion($facturas_id, $numero_factura, $no_factura, $documento_nombre, $documento_id) {
    $variantes = [];

    $agregar = function($valor) use (&$variantes) {
        $valor = trim((string)$valor);
        if ($valor !== '' && !in_array($valor, $variantes, true)) {
            $variantes[] = $valor;
        }
    };

    $no_factura = trim((string)$no_factura);
    $documento_nombre = trim((string)$documento_nombre);

    // Formato que ya usa el sistema para salidas de inventario.
    $agregar("Factura " . $facturas_id);
    $agregar("Factura " . $numero_factura);

    // Proformas actuales o futuras.
    $agregar("Proforma " . $facturas_id);
    $agregar("Proforma " . $numero_factura);
    $agregar("PROFORMA " . $facturas_id);
    $agregar("PROFORMA " . $numero_factura);

    // Número completo con prefijo.
    $agregar($no_factura);
    $agregar("Factura " . $no_factura);
    $agregar("Proforma " . $no_factura);
    $agregar("PROFORMA " . $no_factura);

    // Nombre del documento desde secuencia/documento si viene disponible.
    if ($documento_nombre !== '') {
        $agregar($documento_nombre . " " . $facturas_id);
        $agregar($documento_nombre . " " . $numero_factura);
        $agregar($documento_nombre . " " . $no_factura);
    }

    // Compatibilidad para documento_id = 4.
    if ((int)$documento_id === 4) {
        $agregar("Factura Proforma " . $facturas_id);
        $agregar("Factura Proforma " . $numero_factura);
        $agregar("Factura Proforma " . $no_factura);
    }

    return $variantes;
}

function obtenerSalidasInventarioAnulacion($cn, $empresa_id, $variantesDocumento) {
    if (empty($variantesDocumento)) {
        return [];
    }

    $condiciones = [];
    $types = "i";
    $params = [$empresa_id];

    foreach ($variantesDocumento as $doc) {
        $condiciones[] = "documento = ?";
        $types .= "s";
        $params[] = $doc;

        $condiciones[] = "documento LIKE ? ESCAPE '\\\\'";
        $types .= "s";
        $params[] = $doc . "\\_%";
    }

    $sql = "
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
          AND (" . implode(" OR ", $condiciones) . ")
        ORDER BY movimientos_id ASC
    ";

    $stmt = $cn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando consulta de salidas de inventario: " . $cn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Error consultando salidas de inventario: " . $error);
    }

    $res = $stmt->get_result();
    $salidas = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $salidas[] = $row;
        }
    }

    $stmt->close();
    return $salidas;
}

function validarDevolucionPreviaAnulacion($cn, $empresa_id, $documentoAnulacion) {
    $sql = "
        SELECT movimientos_id
        FROM movimientos
        WHERE empresa_id = ?
          AND documento = ?
          AND cantidad_entrada > 0
        LIMIT 1
    ";

    $stmt = $cn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando validación de devolución: " . $cn->error);
    }

    $stmt->bind_param("is", $empresa_id, $documentoAnulacion);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("Error validando devolución de inventario: " . $error);
    }

    $res = $stmt->get_result();
    $existe = ($res && $res->num_rows > 0);

    $stmt->close();
    return $existe;
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
    $comentario = isset($_POST['comentario']) ? trim((string)$_POST['comentario']) : '';

    if ($facturas_id <= 0) {
        responderAnulacionFactura(false, 'Factura inválida');
    }

    if ($comentario === '') {
        responderAnulacionFactura(false, 'Debe ingresar un comentario para anular la factura');
    }

    $comentario = limitarTextoAnulacion($comentario, 180);

    $cn = $insMainModel->connection();

    if (!$cn) {
        responderAnulacionFactura(false, 'No se pudo conectar a la base de datos');
    }

    $cn->set_charset("utf8mb4");
    $cn->begin_transaction();

    /*
        1. Consultar y bloquear factura.
        Se incluye documento_id para saber si es proforma.
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
            sf.relleno,
            sf.documento_id,
            COALESCE(d.nombre, '') AS documento_nombre
        FROM facturas f
        LEFT JOIN secuencia_facturacion sf
            ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
        LEFT JOIN documento d
            ON d.documento_id = sf.documento_id
        WHERE f.facturas_id = ?
        LIMIT 1
        FOR UPDATE
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
    $secuencia_facturacion_id = (int)$factura['secuencia_facturacion_id'];
    $numero_factura = (int)$factura['number'];
    $estado_factura = (int)$factura['estado'];
    $documento_id = isset($factura['documento_id']) ? (int)$factura['documento_id'] : 0;
    $documento_nombre = isset($factura['documento_nombre']) ? trim((string)$factura['documento_nombre']) : '';

    $proforma = buscarProformaAnulacion($cn, $facturas_id, $empresa_id, $secuencia_facturacion_id, $numero_factura);
    $es_proforma = ((int)$documento_id === 4 || $proforma !== null);
    $estado_proforma = ($proforma !== null && isset($proforma['estado'])) ? (int)$proforma['estado'] : null;

    if ($estado_factura === 4 && (!$es_proforma || $estado_proforma === 4 || $proforma === null)) {
        throw new Exception("Esta factura ya está anulada");
    }

    /*
        2. Armar número completo.
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
        3. Documento de entrada por anulación.
        Para proformas queda como ANULA PROFORMA00000487 si el prefijo ya es PROFORMA.
    */
    $documentoAnulacion = "ANULA " . $no_factura;
    $documentoAnulacion = limitarTextoAnulacion($documentoAnulacion, 50);

    $variantesDocumento = construirVariantesDocumentoAnulacion(
        $facturas_id,
        $numero_factura,
        $no_factura,
        $documento_nombre,
        $documento_id
    );

    /*
        4. Buscar salidas originales y validar si ya se devolvió antes.
        Si ya existe devolución previa NO se vuelve a devolver, pero sí se permite
        terminar de anular factura/proforma para corregir estados incompletos.
    */
    $salidasInventario = obtenerSalidasInventarioAnulacion($cn, $empresa_id, $variantesDocumento);
    $devolucionPrevia = validarDevolucionPreviaAnulacion($cn, $empresa_id, $documentoAnulacion);

    /*
        5. Anular factura.
    */
    $factura_anulada = false;

    if ($estado_factura !== 4) {
        $afectadasFactura = ejecutarUpdateAnulacion(
            $cn,
            "UPDATE facturas SET estado = 4 WHERE facturas_id = ? AND empresa_id = ? AND estado <> 4 LIMIT 1",
            "ii",
            [$facturas_id, $empresa_id]
        );

        $factura_anulada = ($afectadasFactura > 0);
    }

    /*
        6. Anular/sincronizar proforma si existe.
        No se elimina la proforma; se marca como estado 4 para que el reporte pueda filtrarla.
    */
    $proforma_anulada = false;
    $proforma_encontrada = ($proforma !== null);

    if ($proforma_encontrada) {
        $facturas_proforma_id = (int)$proforma['facturas_proforma_id'];

        $afectadasProforma = ejecutarUpdateAnulacion(
            $cn,
            "UPDATE facturas_proforma SET estado = 4 WHERE facturas_proforma_id = ? AND empresa_id = ? AND estado <> 4 LIMIT 1",
            "ii",
            [$facturas_proforma_id, $empresa_id]
        );

        $proforma_anulada = ($afectadasProforma > 0);
    } elseif ($es_proforma) {
        // Si por algún motivo no existe en facturas_proforma, no detenemos la anulación.
        $proforma_anulada = false;
    }

    /*
        7. Preparar correlativo de movimientos.
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
        8. Devolver inventario solo si existieron salidas y no se devolvió antes.
    */
    $totalProductosDevueltos = 0;
    $totalMovimientosDevueltos = 0;

    if (!$devolucionPrevia && !empty($salidasInventario)) {
        foreach ($salidasInventario as $salida) {
            $productos_id = (int)$salida['productos_id'];
            $cantidad_devolver = (float)$salida['cantidad_salida'];
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
                FOR UPDATE
            ";

            $stmtSaldo = $cn->prepare($sqlSaldo);

            if (!$stmtSaldo) {
                throw new Exception("Error preparando consulta de saldo: " . $cn->error);
            }

            $stmtSaldo->bind_param("iiii", $productos_id, $almacen_id, $lote_id, $empresa_id);

            if (!$stmtSaldo->execute()) {
                $error = $stmtSaldo->error;
                $stmtSaldo->close();
                throw new Exception("Error consultando saldo actual: " . $error);
            }

            $resultSaldo = $stmtSaldo->get_result();
            $saldo_actual = 0;

            if ($resultSaldo && $resultSaldo->num_rows > 0) {
                $rowSaldo = $resultSaldo->fetch_assoc();
                $saldo_actual = (float)$rowSaldo['saldo'];
            }

            $stmtSaldo->close();

            $nuevo_saldo = $saldo_actual + $cantidad_devolver;

            $comentarioMovimiento = "Entrada por anulación de " . ($es_proforma ? "proforma " : "factura ") . $no_factura . ". Comentario: " . $comentario;
            $comentarioMovimiento = limitarTextoAnulacion($comentarioMovimiento, 254);

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
                "iisddisisii",
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
                $error = $stmtInsertMovimiento->error;
                $stmtInsertMovimiento->close();
                throw new Exception("Error registrando entrada por anulación: " . $error);
            }

            $stmtInsertMovimiento->close();

            $nuevo_movimientos_id++;
            $totalProductosDevueltos += $cantidad_devolver;
            $totalMovimientosDevueltos++;
        }
    }

    /*
        9. Anular pagos activos directamente en la misma transacción.
        En tu flujo pagos.estado = 1 representa activo.
    */
    $pagos_anulados = 0;

    $pagos_anulados = ejecutarUpdateAnulacion(
        $cn,
        "UPDATE pagos SET estado = 2 WHERE facturas_id = ? AND estado = 1",
        "i",
        [$facturas_id]
    );

    /*
        10. Cerrar/limpiar cuenta por cobrar asociada si existe.
        No se elimina; se marca como saldada/cerrada para no dejar saldo vivo.
    */
    $cxc_actualizadas = 0;

    $cxc_actualizadas = ejecutarUpdateAnulacion(
        $cn,
        "UPDATE cobrar_clientes SET estado = 2, saldo = 0 WHERE facturas_id = ? AND empresa_id = ?",
        "ii",
        [$facturas_id, $empresa_id]
    );

    /*
        11. Historial.
    */
    $tipoTexto = $es_proforma ? "proforma" : "factura";

    $observacion = "El número de " . $tipoTexto . " " . $no_factura . " ha sido anulado correctamente según comentario: " . $comentario;

    if ($devolucionPrevia) {
        $observacion .= ". La devolución de inventario ya existía previamente y no se duplicó.";
    } elseif ($totalMovimientosDevueltos > 0) {
        $observacion .= ". Inventario devuelto: " . $totalProductosDevueltos . " unidades en " . $totalMovimientosDevueltos . " movimiento(s).";
    } else {
        $observacion .= ". No se encontraron movimientos de salida para devolver inventario.";
    }

    if ($pagos_anulados > 0) {
        $observacion .= ". Pagos anulados: " . $pagos_anulados . ".";
    }

    if ($cxc_actualizadas > 0) {
        $observacion .= ". Cuenta por cobrar actualizada.";
    }

    $datosHistorial = [
        "modulo" => "Facturación",
        "colaboradores_id" => $_SESSION['colaborador_id_sd'],
        "status" => "Anulada",
        "observacion" => $observacion,
    ];

    $insMainModel->guardarHistorial($datosHistorial);

    $cn->commit();

    $duracion = round(microtime(true) - $inicioProceso, 4);

    responderAnulacionFactura(true, ucfirst($tipoTexto) . " anulada correctamente", [
        "facturas_id" => $facturas_id,
        "factura" => $no_factura,
        "documento_id" => $documento_id,
        "es_proforma" => $es_proforma,
        "factura_anulada" => $factura_anulada,
        "proforma_encontrada" => $proforma_encontrada,
        "proforma_anulada" => $proforma_anulada,
        "inventario_tenia_salida" => !empty($salidasInventario),
        "inventario_devuelto" => $totalMovimientosDevueltos > 0,
        "devolucion_previa" => $devolucionPrevia,
        "total_movimientos_devueltos" => $totalMovimientosDevueltos,
        "total_productos_devueltos" => $totalProductosDevueltos,
        "pagos_anulados" => $pagos_anulados,
        "cxc_actualizadas" => $cxc_actualizadas,
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