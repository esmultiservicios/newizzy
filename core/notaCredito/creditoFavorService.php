<?php
// Ubicación: core/notaCredito/creditoFavorService.php
// Aplica de forma auditable el saldo a favor de Notas de Crédito a la siguiente
// factura AL CRÉDITO del mismo cliente. No se trata como efectivo ni tarjeta.
require_once __DIR__ . '/../mainModel.php';

final class CreditoFavorService
{
    private const TOLERANCIA = 0.005;

    private static function bind(mysqli_stmt $stmt, string $types, array $params): void
    {
        if ($types === '' || !$params) return;
        $refs = [$types];
        foreach ($params as $k => $v) {
            $params[$k] = $v;
            $refs[] = &$params[$k];
        }
        if (!call_user_func_array([$stmt, 'bind_param'], $refs)) {
            throw new Exception('No se pudieron asociar los parámetros del crédito a favor.');
        }
    }

    private static function one(mysqli $cn, string $sql, string $types = '', array $params = []): ?array
    {
        $st = $cn->prepare($sql);
        if (!$st) throw new Exception('No se pudo preparar la consulta de crédito a favor: ' . $cn->error);
        self::bind($st, $types, $params);
        if (!$st->execute()) {
            $e = $st->error;
            $st->close();
            throw new Exception('No se pudo consultar el crédito a favor: ' . $e);
        }
        $rs = $st->get_result();
        $row = $rs ? $rs->fetch_assoc() : null;
        $st->close();
        return $row ?: null;
    }

    private static function all(mysqli $cn, string $sql, string $types = '', array $params = []): array
    {
        $st = $cn->prepare($sql);
        if (!$st) throw new Exception('No se pudo preparar la consulta de créditos disponibles: ' . $cn->error);
        self::bind($st, $types, $params);
        if (!$st->execute()) {
            $e = $st->error;
            $st->close();
            throw new Exception('No se pudieron consultar los créditos disponibles: ' . $e);
        }
        $rs = $st->get_result();
        $out = [];
        while ($rs && ($r = $rs->fetch_assoc())) $out[] = $r;
        $st->close();
        return $out;
    }

    private static function exec(mysqli $cn, string $sql, string $types = '', array $params = []): void
    {
        $st = $cn->prepare($sql);
        if (!$st) throw new Exception('No se pudo preparar la aplicación del crédito: ' . $cn->error);
        self::bind($st, $types, $params);
        if (!$st->execute()) {
            $e = $st->error;
            $st->close();
            throw new Exception('No se pudo aplicar el crédito a favor: ' . $e);
        }
        $st->close();
    }

    public static function aplicarDisponible(
        mysqli $cn,
        int $empresaId,
        int $clienteId,
        int $facturaDestinoId,
        int $usuarioId
    ): array {
        if ($empresaId <= 0 || $clienteId <= 0 || $facturaDestinoId <= 0) {
            return ['aplicado' => 0.0, 'saldo' => null];
        }

        // El crédito se aplica automáticamente solo a factura fiscal AL CRÉDITO.
        // En contado debe elegirse explícitamente un medio de pago; no convertimos
        // saldo a favor en efectivo para evitar doble pago.
        $factura = self::one(
            $cn,
            "SELECT f.facturas_id, f.tipo_factura, f.clientes_id, sf.documento_id
             FROM facturas f
             INNER JOIN secuencia_facturacion sf ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
             WHERE f.facturas_id = ? AND f.empresa_id = ? LIMIT 1",
            'ii',
            [$facturaDestinoId, $empresaId]
        );
        if (!$factura || (int)$factura['documento_id'] !== 1 || (int)$factura['tipo_factura'] !== 2 || (int)$factura['clientes_id'] !== $clienteId) {
            return ['aplicado' => 0.0, 'saldo' => null];
        }

        $lockName = 'izzy_nc_credit_' . $empresaId . '_' . $clienteId;
        $lock = self::one($cn, "SELECT GET_LOCK(?, 15) AS ok", 's', [$lockName]);
        if (!$lock || (int)$lock['ok'] !== 1) {
            throw new Exception('El saldo a favor del cliente está siendo utilizado por otro proceso.');
        }

        try {
            $cxc = self::one(
                $cn,
                "SELECT cobrar_clientes_id, saldo, estado
                 FROM cobrar_clientes
                 WHERE empresa_id = ? AND clientes_id = ? AND facturas_id = ?
                 LIMIT 1",
                'iii',
                [$empresaId, $clienteId, $facturaDestinoId]
            );
            if (!$cxc) return ['aplicado' => 0.0, 'saldo' => null];

            $saldo = round(max(0, (float)$cxc['saldo']), 4);
            if ($saldo <= self::TOLERANCIA) return ['aplicado' => 0.0, 'saldo' => 0.0];

            $creditos = self::all(
                $cn,
                "SELECT
                    nc.nota_credito_id,
                    nc.credito_favor,
                    nc.fecha_registro,
                    GREATEST(0, nc.credito_favor - COALESCE(SUM(CASE WHEN a.estado = 1 THEN a.importe ELSE 0 END), 0)) AS disponible
                 FROM notas_credito nc
                 LEFT JOIN notas_credito_aplicaciones a ON a.nota_credito_id = nc.nota_credito_id
                 WHERE nc.empresa_id = ?
                   AND nc.clientes_id = ?
                   AND nc.estado = 1
                   AND nc.credito_favor > 0
                   AND nc.facturas_id <> ?
                 GROUP BY nc.nota_credito_id, nc.credito_favor, nc.fecha_registro
                 HAVING disponible > 0.005
                 ORDER BY nc.fecha_registro ASC, nc.nota_credito_id ASC",
                'iii',
                [$empresaId, $clienteId, $facturaDestinoId]
            );

            $totalAplicado = 0.0;
            foreach ($creditos as $credito) {
                if ($saldo <= self::TOLERANCIA) break;
                $disponible = round(max(0, (float)$credito['disponible']), 4);
                if ($disponible <= self::TOLERANCIA) continue;

                $notaId = (int)$credito['nota_credito_id'];
                $usar = round(min($saldo, $disponible), 4);
                $saldoAntes = $saldo;
                $saldoDespues = round(max(0, $saldoAntes - $usar), 4);
                $estadoCxc = $saldoDespues <= self::TOLERANCIA ? 2 : 1;

                $existente = self::one(
                    $cn,
                    "SELECT nota_credito_aplicacion_id, importe, saldo_antes, saldo_despues, estado
                     FROM notas_credito_aplicaciones
                     WHERE nota_credito_id = ? AND facturas_id_destino = ? LIMIT 1",
                    'ii',
                    [$notaId, $facturaDestinoId]
                );

                if ($existente) {
                    if ((int)$existente['estado'] === 1) continue;
                    // Recuperación de una aplicación interrumpida.
                    $usar = (float)$existente['importe'];
                    $saldoAntes = (float)$existente['saldo_antes'];
                    $saldoDespues = (float)$existente['saldo_despues'];
                    if (abs($saldo - $saldoAntes) <= self::TOLERANCIA) {
                        self::exec(
                            $cn,
                            "UPDATE cobrar_clientes SET saldo = ?, estado = ? WHERE cobrar_clientes_id = ?",
                            'dii',
                            [$saldoDespues, $saldoDespues <= self::TOLERANCIA ? 2 : 1, (int)$cxc['cobrar_clientes_id']]
                        );
                    } elseif (abs($saldo - $saldoDespues) > self::TOLERANCIA) {
                        throw new Exception('Existe una aplicación de crédito pendiente que requiere revisión.');
                    }
                    self::exec(
                        $cn,
                        "UPDATE notas_credito_aplicaciones SET estado = 1 WHERE nota_credito_aplicacion_id = ?",
                        'i',
                        [(int)$existente['nota_credito_aplicacion_id']]
                    );
                    $saldo = $saldoDespues;
                    $totalAplicado += $usar;
                    continue;
                }

                self::exec(
                    $cn,
                    "INSERT INTO notas_credito_aplicaciones
                     (nota_credito_id, empresa_id, clientes_id, facturas_id_destino, cobrar_clientes_id,
                      importe, saldo_antes, saldo_despues, estado, usuario, fecha_registro)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())",
                    'iiiiidddi',
                    [$notaId, $empresaId, $clienteId, $facturaDestinoId, (int)$cxc['cobrar_clientes_id'], $usar, $saldoAntes, $saldoDespues, $usuarioId]
                );
                $appId = (int)$cn->insert_id;

                self::exec(
                    $cn,
                    "UPDATE cobrar_clientes SET saldo = ?, estado = ? WHERE cobrar_clientes_id = ?",
                    'dii',
                    [$saldoDespues, $estadoCxc, (int)$cxc['cobrar_clientes_id']]
                );
                self::exec(
                    $cn,
                    "UPDATE notas_credito_aplicaciones SET estado = 1 WHERE nota_credito_aplicacion_id = ?",
                    'i',
                    [$appId]
                );

                $saldo = $saldoDespues;
                $totalAplicado += $usar;
            }

            return ['aplicado' => round($totalAplicado, 4), 'saldo' => round($saldo, 4)];
        } finally {
            try { self::one($cn, "SELECT RELEASE_LOCK(?) AS ok", 's', [$lockName]); } catch (Throwable $e) {}
        }
    }
}
