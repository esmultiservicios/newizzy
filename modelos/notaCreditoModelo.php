<?php
// Ubicación: modelos/notaCreditoModelo.php
require_once __DIR__ . '/../core/mainModel.php';

class notaCreditoModelo extends mainModel
{
    private static function bindParams(mysqli_stmt $stmt, string $types, array $params): void
    {
        if ($types === '' || count($params) === 0) {
            return;
        }

        $refs = [$types];

        foreach ($params as $key => $value) {
            $params[$key] = $value;
            $refs[] = &$params[$key];
        }

        if (!call_user_func_array([$stmt, 'bind_param'], $refs)) {
            throw new Exception('No se pudieron asociar los parámetros de la consulta.');
        }
    }

    public static function fetchOne(mysqli $cn, string $sql, string $types = '', array $params = []): ?array
    {
        $stmt = $cn->prepare($sql);
        if (!$stmt) {
            throw new Exception('No se pudo preparar la consulta: ' . $cn->error);
        }

        self::bindParams($stmt, $types, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Error al ejecutar la consulta: ' . $error);
        }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public static function fetchAll(mysqli $cn, string $sql, string $types = '', array $params = []): array
    {
        $stmt = $cn->prepare($sql);
        if (!$stmt) {
            throw new Exception('No se pudo preparar la consulta: ' . $cn->error);
        }

        self::bindParams($stmt, $types, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Error al ejecutar la consulta: ' . $error);
        }
        $res = $stmt->get_result();
        $rows = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public static function exec(mysqli $cn, string $sql, string $types = '', array $params = []): bool
    {
        $stmt = $cn->prepare($sql);
        if (!$stmt) {
            throw new Exception('No se pudo preparar la operación: ' . $cn->error);
        }

        self::bindParams($stmt, $types, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Error al ejecutar la operación: ' . $error);
        }

        $stmt->close();
        return true;
    }

    public static function obtenerFactura(mysqli $cn, int $empresaId, int $facturaId): ?array
    {
        return self::fetchOne(
            $cn,
            "SELECT
                f.facturas_id,
                f.clientes_id,
                f.secuencia_facturacion_id,
                f.number,
                f.tipo_factura,
                f.importe,
                f.notas,
                f.fecha,
                f.estado,
                f.empresa_id,
                c.nombre AS cliente,
                c.rtn,
                sf.prefijo,
                sf.relleno,
                sf.documento_id,
                d.nombre AS documento
             FROM facturas f
             INNER JOIN clientes c ON c.clientes_id = f.clientes_id
             INNER JOIN secuencia_facturacion sf ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
             LEFT JOIN documento d ON d.documento_id = sf.documento_id
             WHERE f.facturas_id = ?
               AND f.empresa_id = ?
             LIMIT 1",
            'ii',
            [$facturaId, $empresaId]
        );
    }

    public static function obtenerDetallesFactura(mysqli $cn, int $empresaId, int $facturaId): array
    {
        return self::fetchAll(
            $cn,
            "SELECT
                fd.facturas_detalle_id,
                fd.facturas_id,
                fd.productos_id,
                COALESCE(p.nombre, CONCAT('Producto #', fd.productos_id)) AS producto,
                fd.cantidad,
                fd.precio,
                fd.descuento,
                fd.isv_valor,
                fd.isv_valor1,
                GREATEST(0, (fd.precio * fd.cantidad) - fd.descuento) AS base_original,
                COALESCE(SUM(CASE WHEN nc.estado = 1 THEN ncd.base_acreditada ELSE 0 END), 0) AS base_acreditada_previa,
                COALESCE(SUM(CASE WHEN nc.estado = 1 THEN ncd.isv15_acreditado ELSE 0 END), 0) AS isv15_acreditado_previo,
                COALESCE(SUM(CASE WHEN nc.estado = 1 THEN ncd.isv18_acreditado ELSE 0 END), 0) AS isv18_acreditado_previo,
                COALESCE(SUM(CASE WHEN nc.estado = 1 THEN ncd.total_acreditado ELSE 0 END), 0) AS total_acreditado_previo
             FROM facturas_detalles fd
             LEFT JOIN productos p ON p.productos_id = fd.productos_id
             LEFT JOIN notas_credito_detalle ncd ON ncd.facturas_detalle_id = fd.facturas_detalle_id
             LEFT JOIN notas_credito nc
                    ON nc.nota_credito_id = ncd.nota_credito_id
                   AND nc.empresa_id = ?
             WHERE fd.facturas_id = ?
             GROUP BY
                fd.facturas_detalle_id,
                fd.facturas_id,
                fd.productos_id,
                p.nombre,
                fd.cantidad,
                fd.precio,
                fd.descuento,
                fd.isv_valor,
                fd.isv_valor1
             ORDER BY fd.facturas_detalle_id ASC",
            'ii',
            [$empresaId, $facturaId]
        );
    }

    public static function obtenerTotalAcreditadoFactura(mysqli $cn, int $empresaId, int $facturaId): float
    {
        $row = self::fetchOne(
            $cn,
            "SELECT COALESCE(SUM(total_acreditado), 0) AS total
             FROM notas_credito
             WHERE empresa_id = ?
               AND facturas_id = ?
               AND estado = 1",
            'ii',
            [$empresaId, $facturaId]
        );

        return round((float)($row['total'] ?? 0), 4);
    }

    public static function obtenerSecuenciaNotaCredito(mysqli $cn, int $empresaId): ?array
    {
        return self::fetchOne(
            $cn,
            "SELECT
                sf.secuencia_facturacion_id,
                sf.empresa_id,
                sf.documento_id,
                sf.cai,
                sf.prefijo,
                sf.relleno,
                sf.incremento,
                sf.siguiente,
                sf.rango_inicial,
                sf.rango_final,
                sf.fecha_activacion,
                sf.fecha_limite,
                sf.activo,
                d.nombre AS documento_nombre,
                d.estado AS documento_estado
             FROM secuencia_facturacion sf
             INNER JOIN documento d ON d.documento_id = sf.documento_id
             WHERE sf.empresa_id = ?
               AND sf.documento_id = 2
               AND sf.activo = 1
               AND d.estado = 1
             ORDER BY sf.fecha_activacion DESC, sf.secuencia_facturacion_id DESC
             LIMIT 1",
            'i',
            [$empresaId]
        );
    }

    public static function existeNumeroNota(mysqli $cn, int $empresaId, int $secuenciaId, int $numero): bool
    {
        $row = self::fetchOne(
            $cn,
            "SELECT nota_credito_id
             FROM notas_credito
             WHERE empresa_id = ?
               AND secuencia_facturacion_id = ?
               AND number = ?
             LIMIT 1",
            'iii',
            [$empresaId, $secuenciaId, $numero]
        );

        return $row !== null;
    }

    public static function insertarCabecera(mysqli $cn, array $d): int
    {
        $stmt = $cn->prepare(
            "INSERT INTO notas_credito (
                empresa_id, facturas_id, clientes_id,
                secuencia_facturacion_id, documento_id, number,
                prefijo, relleno, numero_completo, fecha, motivo,
                base_acreditada, isv15_acreditado, isv18_acreditado, total_acreditado,
                importe_factura_original, total_acreditado_anterior,
                cxc_aplicada, cxc_saldo_antes, cxc_saldo_despues, credito_favor,
                secuencia_actualizada, estado, colaboradores_id, usuario, origen, fecha_registro
             ) VALUES (
                ?, ?, ?, ?, 2, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                0, 0, 0, 0,
                0, 1, ?, ?, ?, ?
             )"
        );

        if (!$stmt) {
            throw new Exception('No se pudo preparar la Nota de Crédito: ' . $cn->error);
        }

        $types = 'iiiiisisssddddddiiss';
        $stmt->bind_param(
            $types,
            $d['empresa_id'],
            $d['facturas_id'],
            $d['clientes_id'],
            $d['secuencia_facturacion_id'],
            $d['number'],
            $d['prefijo'],
            $d['relleno'],
            $d['numero_completo'],
            $d['fecha'],
            $d['motivo'],
            $d['base_acreditada'],
            $d['isv15_acreditado'],
            $d['isv18_acreditado'],
            $d['total_acreditado'],
            $d['importe_factura_original'],
            $d['total_acreditado_anterior'],
            $d['colaboradores_id'],
            $d['usuario'],
            $d['origen'],
            $d['fecha_registro']
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('No se pudo registrar la Nota de Crédito: ' . $error);
        }

        $id = (int)$stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public static function insertarDetalle(mysqli $cn, array $d): int
    {
        $stmt = $cn->prepare(
            "INSERT INTO notas_credito_detalle (
                nota_credito_id, facturas_detalle_id, productos_id, producto,
                cantidad_original, precio_original, descuento_original,
                base_original, isv15_original, isv18_original,
                base_acreditada, isv15_acreditado, isv18_acreditado, total_acreditado,
                fecha_registro
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('No se pudo preparar el detalle de la Nota de Crédito: ' . $cn->error);
        }
        $stmt->bind_param(
            'iiisdddddddddds',
            $d['nota_credito_id'],
            $d['facturas_detalle_id'],
            $d['productos_id'],
            $d['producto'],
            $d['cantidad_original'],
            $d['precio_original'],
            $d['descuento_original'],
            $d['base_original'],
            $d['isv15_original'],
            $d['isv18_original'],
            $d['base_acreditada'],
            $d['isv15_acreditado'],
            $d['isv18_acreditado'],
            $d['total_acreditado'],
            $d['fecha_registro']
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('No se pudo registrar el detalle de la Nota de Crédito: ' . $error);
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function actualizarSiguienteSecuencia(mysqli $cn, int $secuenciaId, int $nuevoSiguiente): bool
    {
        return self::exec(
            $cn,
            "UPDATE secuencia_facturacion
             SET siguiente = ?
             WHERE secuencia_facturacion_id = ?
             LIMIT 1",
            'ii',
            [$nuevoSiguiente, $secuenciaId]
        );
    }

    public static function marcarSecuenciaActualizada(mysqli $cn, int $notaId): void
    {
        self::exec(
            $cn,
            "UPDATE notas_credito
             SET secuencia_actualizada = 1
             WHERE nota_credito_id = ?",
            'i',
            [$notaId]
        );
    }

    public static function aplicarCxC(mysqli $cn, int $empresaId, int $facturaId, float $totalNc): array
    {
        $row = self::fetchOne(
            $cn,
            "SELECT cobrar_clientes_id, saldo, estado
             FROM cobrar_clientes
             WHERE empresa_id = ?
               AND facturas_id = ?
             LIMIT 1",
            'ii',
            [$empresaId, $facturaId]
        );

        if (!$row) {
            return [
                'aplicada' => 1,
                'saldo_antes' => 0.0,
                'saldo_despues' => 0.0,
                'credito_favor' => round(max(0, $totalNc), 4)
            ];
        }

        $saldoAntes = round((float)$row['saldo'], 4);
        $saldoDespues = round(max(0, $saldoAntes - $totalNc), 4);
        $creditoFavor = round(max(0, $totalNc - $saldoAntes), 4);
        $estado = $saldoDespues <= 0.00005 ? 2 : 1;

        self::exec(
            $cn,
            "UPDATE cobrar_clientes
             SET saldo = ?, estado = ?
             WHERE cobrar_clientes_id = ?
             LIMIT 1",
            'dii',
            [$saldoDespues, $estado, (int)$row['cobrar_clientes_id']]
        );

        return [
            'aplicada' => 1,
            'saldo_antes' => $saldoAntes,
            'saldo_despues' => $saldoDespues,
            'credito_favor' => $creditoFavor
        ];
    }

    public static function actualizarResultadoCxC(mysqli $cn, int $notaId, array $cxc): void
    {
        self::exec(
            $cn,
            "UPDATE notas_credito
             SET cxc_aplicada = ?,
                 cxc_saldo_antes = ?,
                 cxc_saldo_despues = ?,
                 credito_favor = ?
             WHERE nota_credito_id = ?",
            'idddi',
            [
                (int)($cxc['aplicada'] ?? 0),
                (float)($cxc['saldo_antes'] ?? 0),
                (float)($cxc['saldo_despues'] ?? 0),
                (float)($cxc['credito_favor'] ?? 0),
                $notaId
            ]
        );
    }

    public static function listarNotasFactura(mysqli $cn, int $empresaId, int $facturaId): array
    {
        return self::fetchAll(
            $cn,
            "SELECT
                nota_credito_id,
                numero_completo,
                fecha,
                motivo,
                base_acreditada,
                isv15_acreditado,
                isv18_acreditado,
                total_acreditado,
                cxc_aplicada,
                cxc_saldo_antes,
                cxc_saldo_despues,
                credito_favor,
                estado,
                fecha_registro
             FROM notas_credito
             WHERE empresa_id = ?
               AND facturas_id = ?
             ORDER BY nota_credito_id DESC",
            'ii',
            [$empresaId, $facturaId]
        );
    }

    public static function obtenerNota(mysqli $cn, int $empresaId, int $notaId): ?array
    {
        $cabecera = self::fetchOne(
            $cn,
            "SELECT
                nc.*,
                c.nombre AS cliente,
                c.rtn,
                f.number AS factura_number,
                sf_factura.prefijo AS factura_prefijo,
                sf_factura.relleno AS factura_relleno,
                sf_nc.cai AS nc_cai,
                sf_nc.prefijo AS nc_prefijo,
                sf_nc.relleno AS nc_relleno,
                sf_nc.rango_inicial AS nc_rango_inicial,
                sf_nc.rango_final AS nc_rango_final,
                sf_nc.fecha_limite AS nc_fecha_limite
             FROM notas_credito nc
             INNER JOIN clientes c ON c.clientes_id = nc.clientes_id
             INNER JOIN facturas f ON f.facturas_id = nc.facturas_id
             INNER JOIN secuencia_facturacion sf_factura ON sf_factura.secuencia_facturacion_id = f.secuencia_facturacion_id
             INNER JOIN secuencia_facturacion sf_nc ON sf_nc.secuencia_facturacion_id = nc.secuencia_facturacion_id
             WHERE nc.nota_credito_id = ?
               AND nc.empresa_id = ?
             LIMIT 1",
            'ii',
            [$notaId, $empresaId]
        );

        if (!$cabecera) {
            return null;
        }

        $cabecera['detalle'] = self::fetchAll(
            $cn,
            "SELECT *
             FROM notas_credito_detalle
             WHERE nota_credito_id = ?
             ORDER BY nota_credito_detalle_id ASC",
            'i',
            [$notaId]
        );

        return $cabecera;
    }

    public static function obtenerDocumentoNotaCredito(mysqli $cn): ?array
    {
        return self::fetchOne(
            $cn,
            "SELECT documento_id, nombre, estado
             FROM documento
             WHERE documento_id = 2
             LIMIT 1"
        );
    }

    public static function obtenerMovimientosSalidaFactura(
        mysqli $cn,
        int $empresaId,
        int $facturaId,
        ?int $productoId = null,
        bool $incluirRelacionados = false
    ): array {
        $documentoBase = 'Factura ' . $facturaId;
        $sql = "SELECT
                    movimientos_id,
                    productos_id,
                    cantidad_salida,
                    empresa_id,
                    clientes_id,
                    almacen_id,
                    lote_id,
                    documento,
                    comentario
                FROM movimientos
                WHERE empresa_id = ?
                  AND cantidad_salida > 0
                  AND comentario = 'Salida de inventario por venta'";
        $types = 'i';
        $params = [$empresaId];

        if ($incluirRelacionados) {
            $sql .= " AND (documento = ? OR LEFT(documento, CHAR_LENGTH(?) + 1) = CONCAT(?, '_'))";
            $types .= 'sss';
            $params[] = $documentoBase;
            $params[] = $documentoBase;
            $params[] = $documentoBase;
        } else {
            $sql .= " AND documento = ?";
            $types .= 's';
            $params[] = $documentoBase;
        }

        if ($productoId !== null && $productoId > 0) {
            $sql .= " AND productos_id = ?";
            $types .= 'i';
            $params[] = $productoId;
        }

        $sql .= " ORDER BY movimientos_id ASC";
        return self::fetchAll($cn, $sql, $types, $params);
    }

    public static function cantidadInventarioDevueltaMovimiento(mysqli $cn, int $movimientoOrigenId): float
    {
        $row = self::fetchOne(
            $cn,
            "SELECT COALESCE(SUM(cantidad_devuelta), 0) AS total
             FROM notas_credito_inventario
             WHERE movimiento_origen_id = ?
               AND estado = 1",
            'i',
            [$movimientoOrigenId]
        );

        return round((float)($row['total'] ?? 0), 4);
    }

    public static function obtenerTrazaInventario(mysqli $cn, int $notaId, int $movimientoOrigenId): ?array
    {
        return self::fetchOne(
            $cn,
            "SELECT *
             FROM notas_credito_inventario
             WHERE nota_credito_id = ?
               AND movimiento_origen_id = ?
             LIMIT 1",
            'ii',
            [$notaId, $movimientoOrigenId]
        );
    }

    public static function crearTrazaInventario(mysqli $cn, array $d): int
    {
        $stmt = $cn->prepare(
            "INSERT INTO notas_credito_inventario
             (nota_credito_id, nota_credito_detalle_id, movimiento_origen_id,
              movimiento_devolucion_id, empresa_id, clientes_id, productos_id,
              almacen_id, lote_id, cantidad_devuelta, estado, fecha_registro)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 0, NOW())"
        );
        if (!$stmt) {
            throw new Exception('No se pudo preparar la trazabilidad de inventario: ' . $cn->error);
        }
        $stmt->bind_param(
            'iiiiiiiid',
            $d['nota_credito_id'],
            $d['nota_credito_detalle_id'],
            $d['movimiento_origen_id'],
            $d['empresa_id'],
            $d['clientes_id'],
            $d['productos_id'],
            $d['almacen_id'],
            $d['lote_id'],
            $d['cantidad_devuelta']
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('No se pudo registrar la trazabilidad de inventario: ' . $error);
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public static function buscarMovimientoDevolucion(mysqli $cn, string $documento, int $productoId, int $almacenId, int $loteId): ?array
    {
        return self::fetchOne(
            $cn,
            "SELECT movimientos_id, cantidad_entrada
             FROM movimientos
             WHERE documento = ?
               AND productos_id = ?
               AND almacen_id = ?
               AND lote_id = ?
               AND cantidad_entrada > 0
             ORDER BY movimientos_id DESC
             LIMIT 1",
            'siii',
            [$documento, $productoId, $almacenId, $loteId]
        );
    }

    public static function registrarEntradaInventarioNota(mysqli $cn, array $origen, float $cantidad, string $documento): int
    {
        $productoId = (int)$origen['productos_id'];
        $empresaId = (int)$origen['empresa_id'];
        $clienteId = (int)$origen['clientes_id'];
        $almacenId = (int)$origen['almacen_id'];
        $loteId = (int)$origen['lote_id'];

        $saldoRow = self::fetchOne(
            $cn,
            "SELECT COALESCE(SUM(cantidad_entrada),0)-COALESCE(SUM(cantidad_salida),0) AS saldo
             FROM movimientos
             WHERE productos_id = ? AND empresa_id = ? AND almacen_id = ?",
            'iii',
            [$productoId, $empresaId, $almacenId]
        );
        $saldoNuevo = round((float)($saldoRow['saldo'] ?? 0) + $cantidad, 4);
        $comentario = 'Entrada por Nota de Crédito';

        $stmt = $cn->prepare(
            "INSERT INTO movimientos
             (productos_id, documento, cantidad_entrada, cantidad_salida, saldo,
              empresa_id, fecha_registro, clientes_id, comentario, almacen_id, lote_id)
             VALUES (?, ?, ?, 0, ?, ?, NOW(), ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('No se pudo preparar la devolución de inventario: ' . $cn->error);
        }
        $stmt->bind_param(
            'isddiisii',
            $productoId,
            $documento,
            $cantidad,
            $saldoNuevo,
            $empresaId,
            $clienteId,
            $comentario,
            $almacenId,
            $loteId
        );
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('No se pudo devolver el producto al inventario: ' . $error);
        }
        $movimientoId = (int)$stmt->insert_id;
        $stmt->close();

        if ($loteId > 0) {
            self::exec(
                $cn,
                "UPDATE lotes
                 SET cantidad = cantidad + ?,
                     estado = CASE
                         WHEN fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE() THEN 'Activo'
                         ELSE estado
                     END
                 WHERE lote_id = ? AND productos_id = ? AND empresa_id = ?",
                'diii',
                [$cantidad, $loteId, $productoId, $empresaId]
            );
        }

        return $movimientoId;
    }

    public static function completarTrazaInventario(mysqli $cn, int $trazaId, int $movimientoDevolucionId): void
    {
        self::exec(
            $cn,
            "UPDATE notas_credito_inventario
             SET movimiento_devolucion_id = ?, estado = 1
             WHERE nota_credito_inventario_id = ?",
            'ii',
            [$movimientoDevolucionId, $trazaId]
        );
    }

}
