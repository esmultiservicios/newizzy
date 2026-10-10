<?php
// Ubicación: core/notaCredito/creditoFavorService.php
// Servicio auditable para consultar y aplicar crédito a favor generado por NC.
// Aplica a facturas fiscales de contado o crédito. La aplicación real ocurre
// únicamente al confirmar un pago; crear la factura nunca consume el saldo.
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
        if (!$st) {
            throw new Exception('No se pudo preparar la consulta de crédito a favor: ' . $cn->error);
        }

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
        if (!$st) {
            throw new Exception('No se pudo preparar la consulta de créditos disponibles: ' . $cn->error);
        }

        self::bind($st, $types, $params);

        if (!$st->execute()) {
            $e = $st->error;
            $st->close();
            throw new Exception('No se pudieron consultar los créditos disponibles: ' . $e);
        }

        $rs = $st->get_result();
        $out = [];

        while ($rs && ($r = $rs->fetch_assoc())) {
            $out[] = $r;
        }

        $st->close();
        return $out;
    }

    private static function exec(mysqli $cn, string $sql, string $types = '', array $params = []): void
    {
        $st = $cn->prepare($sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la aplicación del crédito: ' . $cn->error);
        }

        self::bind($st, $types, $params);

        if (!$st->execute()) {
            $e = $st->error;
            $st->close();
            throw new Exception('No se pudo aplicar el crédito a favor: ' . $e);
        }

        $st->close();
    }

    private static function obtenerFacturaDestino(mysqli $cn, int $empresaId, int $facturaDestinoId): ?array
    {
        return self::one(
            $cn,
            "SELECT
                f.facturas_id,
                f.clientes_id,
                f.tipo_factura,
                ROUND(f.importe, 2) AS importe,
                f.estado,
                COALESCE(sf.documento_id, 1) AS documento_id
             FROM facturas f
             LEFT JOIN secuencia_facturacion sf
                ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
             WHERE f.facturas_id = ?
               AND f.empresa_id = ?
             LIMIT 1",
            'ii',
            [$facturaDestinoId, $empresaId]
        );
    }

    private static function obtenerCxc(mysqli $cn, int $empresaId, int $clienteId, int $facturaDestinoId): ?array
    {
        return self::one(
            $cn,
            "SELECT cobrar_clientes_id, saldo, estado
             FROM cobrar_clientes
             WHERE empresa_id = ?
               AND clientes_id = ?
               AND facturas_id = ?
             LIMIT 1",
            'iii',
            [$empresaId, $clienteId, $facturaDestinoId]
        );
    }

    private static function obtenerCreditosDisponibles(
        mysqli $cn,
        int $empresaId,
        int $clienteId,
        int $facturaDestinoId
    ): array {
        /*
         * credito_favor es la fuente de verdad del saldo generado por cada NC.
         * Solo se resta lo que ya fue aplicado con estado=1 a facturas posteriores.
         *
         * Resultado:
         * - NC nunca usada: disponible = credito_favor completo.
         * - NC parcialmente usada: disponible = remanente.
         * - NC totalmente usada: no vuelve a aparecer.
         */
        return self::all(
            $cn,
            "SELECT
                nc.nota_credito_id,
                nc.numero_completo,
                nc.fecha,
                nc.motivo,
                nc.facturas_id AS factura_origen_id,
                nc.importe_factura_original,
                nc.credito_favor,
                nc.fecha_registro,
                f.fecha AS factura_origen_fecha,
                f.number AS factura_origen_correlativo,
                sf.prefijo AS factura_origen_prefijo,
                sf.relleno AS factura_origen_relleno,
                GREATEST(
                    0,
                    nc.credito_favor -
                    COALESCE(
                        SUM(CASE WHEN a.estado = 1 THEN a.importe ELSE 0 END),
                        0
                    )
                ) AS disponible
             FROM notas_credito nc
             LEFT JOIN notas_credito_aplicaciones a
                ON a.nota_credito_id = nc.nota_credito_id
             LEFT JOIN facturas f
                ON f.facturas_id = nc.facturas_id
               AND f.empresa_id = nc.empresa_id
             LEFT JOIN secuencia_facturacion sf
                ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
             WHERE nc.empresa_id = ?
               AND nc.clientes_id = ?
               AND nc.estado = 1
               AND nc.credito_favor > 0.005
               AND nc.facturas_id <> ?
             GROUP BY
                nc.nota_credito_id,
                nc.numero_completo,
                nc.fecha,
                nc.motivo,
                nc.facturas_id,
                nc.importe_factura_original,
                nc.credito_favor,
                nc.fecha_registro,
                f.fecha,
                f.number,
                sf.prefijo,
                sf.relleno
             HAVING disponible > 0.005
             ORDER BY nc.fecha_registro ASC, nc.nota_credito_id ASC",
            'iii',
            [$empresaId, $clienteId, $facturaDestinoId]
        );
    }

    /**
     * Resumen para el modal de pagos. No modifica datos.
     */
    public static function resumenDisponible(
        mysqli $cn,
        int $empresaId,
        int $facturaDestinoId
    ): array {
        $vacio = [
            'facturas_id' => $facturaDestinoId,
            'clientes_id' => 0,
            'total_factura' => 0.0,
            'saldo_base' => 0.0,
            'credito_disponible' => 0.0,
            'credito_aplicable' => 0.0,
            'total_cobrar' => 0.0,
            'cantidad_notas' => 0,
            'notas' => []
        ];

        if ($empresaId <= 0 || $facturaDestinoId <= 0) {
            return $vacio;
        }

        $factura = self::obtenerFacturaDestino($cn, $empresaId, $facturaDestinoId);

        // El saldo a favor de NC se usa únicamente contra factura fiscal.
        if (!$factura || (int)$factura['documento_id'] !== 1) {
            if ($factura) {
                $vacio['clientes_id'] = (int)$factura['clientes_id'];
                $vacio['total_factura'] = round((float)$factura['importe'], 2);
                $vacio['saldo_base'] = $vacio['total_factura'];
                $vacio['total_cobrar'] = $vacio['total_factura'];
            }
            return $vacio;
        }

        $clienteId = (int)$factura['clientes_id'];
        $totalFactura = round(max(0, (float)$factura['importe']), 2);
        $cxc = self::obtenerCxc($cn, $empresaId, $clienteId, $facturaDestinoId);

        $saldoBase = $cxc
            ? round(max(0, (float)$cxc['saldo']), 2)
            : $totalFactura;

        $creditos = self::obtenerCreditosDisponibles(
            $cn,
            $empresaId,
            $clienteId,
            $facturaDestinoId
        );

        $restante = $saldoBase;
        $totalDisponible = 0.0;
        $totalAplicable = 0.0;
        $notas = [];

        foreach ($creditos as $credito) {
            $disponible = round(max(0, (float)$credito['disponible']), 2);
            $totalDisponible += $disponible;

            $aplicar = $restante > self::TOLERANCIA
                ? round(min($restante, $disponible), 2)
                : 0.0;

            if ($aplicar > self::TOLERANCIA) {
                $restante = round(max(0, $restante - $aplicar), 2);
                $totalAplicable += $aplicar;
            }

            $prefijoOrigen = trim((string)($credito['factura_origen_prefijo'] ?? ''));
            $rellenoOrigen = (int)($credito['factura_origen_relleno'] ?? 0);
            $correlativoOrigen = (int)($credito['factura_origen_correlativo'] ?? 0);

            if ($correlativoOrigen > 0) {
                $numeroOrigen = $prefijoOrigen . (
                    $rellenoOrigen > 0
                        ? str_pad((string)$correlativoOrigen, $rellenoOrigen, '0', STR_PAD_LEFT)
                        : (string)$correlativoOrigen
                );
            } else {
                $numeroOrigen = '#' . (int)($credito['factura_origen_id'] ?? 0);
            }

            $notas[] = [
                'nota_credito_id' => (int)$credito['nota_credito_id'],
                'numero' => (string)($credito['numero_completo'] ?: ('NC #' . $credito['nota_credito_id'])),
                'fecha' => (string)($credito['fecha'] ?: $credito['fecha_registro']),
                'motivo' => (string)($credito['motivo'] ?? ''),
                'factura_origen_id' => (int)($credito['factura_origen_id'] ?? 0),
                'factura_origen_numero' => $numeroOrigen,
                'factura_origen_fecha' => (string)($credito['factura_origen_fecha'] ?? ''),
                'importe_factura_original' => round((float)($credito['importe_factura_original'] ?? 0), 2),
                'credito_favor' => round((float)$credito['credito_favor'], 2),
                'disponible' => $disponible,
                'aplicar' => $aplicar
            ];
        }

        return [
            'facturas_id' => $facturaDestinoId,
            'clientes_id' => $clienteId,
            'total_factura' => $totalFactura,
            'saldo_base' => $saldoBase,
            'credito_disponible' => round($totalDisponible, 2),
            'credito_aplicable' => round($totalAplicable, 2),
            'total_cobrar' => round(max(0, $saldoBase - $totalAplicable), 2),
            'cantidad_notas' => count(array_filter($notas, function ($n) {
                return (float)$n['aplicar'] > self::TOLERANCIA;
            })),
            'notas' => $notas
        ];
    }

    /**
     * Aplica crédito disponible en FIFO.
     *
     * $confirmarDesdePago=false mantiene compatibilidad con llamadas antiguas
     * realizadas al crear la factura, pero evita consumir el crédito antes
     * de que el usuario confirme el pago.
     */
    public static function aplicarDisponible(
        mysqli $cn,
        int $empresaId,
        int $clienteId,
        int $facturaDestinoId,
        int $usuarioId,
        bool $confirmarDesdePago = false
    ): array {
        if ($empresaId <= 0 || $clienteId <= 0 || $facturaDestinoId <= 0) {
            return [
                'aplicado' => 0.0,
                'saldo' => null,
                'saldo_antes' => null,
                'aplicaciones_ids' => []
            ];
        }

        $resumen = self::resumenDisponible($cn, $empresaId, $facturaDestinoId);

        if (!$confirmarDesdePago) {
            return [
                'aplicado' => 0.0,
                'saldo' => $resumen['saldo_base'],
                'saldo_antes' => $resumen['saldo_base'],
                'disponible' => $resumen['credito_aplicable'],
                'aplicaciones_ids' => []
            ];
        }

        $factura = self::obtenerFacturaDestino($cn, $empresaId, $facturaDestinoId);
        if (
            !$factura ||
            (int)$factura['documento_id'] !== 1 ||
            (int)$factura['clientes_id'] !== $clienteId
        ) {
            return [
                'aplicado' => 0.0,
                'saldo' => null,
                'saldo_antes' => null,
                'aplicaciones_ids' => []
            ];
        }

        $lockName = 'izzy_nc_credit_' . $empresaId . '_' . $clienteId;
        $lock = self::one($cn, "SELECT GET_LOCK(?, 15) AS ok", 's', [$lockName]);

        if (!$lock || (int)$lock['ok'] !== 1) {
            throw new Exception('El saldo a favor del cliente está siendo utilizado por otro proceso.');
        }

        try {
            $cxc = self::obtenerCxc($cn, $empresaId, $clienteId, $facturaDestinoId);
            if (!$cxc) {
                return [
                    'aplicado' => 0.0,
                    'saldo' => null,
                    'saldo_antes' => null,
                    'aplicaciones_ids' => []
                ];
            }

            $saldoInicial = round(max(0, (float)$cxc['saldo']), 2);
            $saldo = $saldoInicial;

            if ($saldo <= self::TOLERANCIA) {
                return [
                    'aplicado' => 0.0,
                    'saldo' => 0.0,
                    'saldo_antes' => $saldoInicial,
                    'aplicaciones_ids' => []
                ];
            }

            $creditos = self::obtenerCreditosDisponibles(
                $cn,
                $empresaId,
                $clienteId,
                $facturaDestinoId
            );

            $totalAplicado = 0.0;
            $aplicacionesIds = [];

            foreach ($creditos as $credito) {
                if ($saldo <= self::TOLERANCIA) break;

                $disponible = round(max(0, (float)$credito['disponible']), 2);
                if ($disponible <= self::TOLERANCIA) continue;

                $notaId = (int)$credito['nota_credito_id'];
                $usar = round(min($saldo, $disponible), 2);
                $saldoAntes = $saldo;
                $saldoDespues = round(max(0, $saldoAntes - $usar), 2);
                $estadoCxc = $saldoDespues <= self::TOLERANCIA ? 2 : 1;

                $existente = self::one(
                    $cn,
                    "SELECT nota_credito_aplicacion_id, estado
                     FROM notas_credito_aplicaciones
                     WHERE nota_credito_id = ?
                       AND facturas_id_destino = ?
                     LIMIT 1",
                    'ii',
                    [$notaId, $facturaDestinoId]
                );

                // Una aplicación activa ya fue descontada del disponible por la consulta.
                if ($existente && (int)$existente['estado'] === 1) {
                    continue;
                }

                // Si existe un intento no activo, se elimina antes de reconstruirlo.
                if ($existente) {
                    self::exec(
                        $cn,
                        "DELETE FROM notas_credito_aplicaciones
                         WHERE nota_credito_aplicacion_id = ?",
                        'i',
                        [(int)$existente['nota_credito_aplicacion_id']]
                    );
                }

                self::exec(
                    $cn,
                    "INSERT INTO notas_credito_aplicaciones
                     (nota_credito_id, empresa_id, clientes_id, facturas_id_destino, cobrar_clientes_id,
                      importe, saldo_antes, saldo_despues, estado, usuario, fecha_registro)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())",
                    'iiiiidddi',
                    [
                        $notaId,
                        $empresaId,
                        $clienteId,
                        $facturaDestinoId,
                        (int)$cxc['cobrar_clientes_id'],
                        $usar,
                        $saldoAntes,
                        $saldoDespues,
                        $usuarioId
                    ]
                );

                $aplicacionesIds[] = (int)$cn->insert_id;

                self::exec(
                    $cn,
                    "UPDATE cobrar_clientes
                     SET saldo = ?, estado = ?
                     WHERE cobrar_clientes_id = ?",
                    'dii',
                    [$saldoDespues, $estadoCxc, (int)$cxc['cobrar_clientes_id']]
                );

                $saldo = $saldoDespues;
                $totalAplicado += $usar;
            }

            if ($saldo <= self::TOLERANCIA && $totalAplicado > self::TOLERANCIA) {
                self::exec(
                    $cn,
                    "UPDATE facturas SET estado = 2 WHERE facturas_id = ? AND empresa_id = ?",
                    'ii',
                    [$facturaDestinoId, $empresaId]
                );
            }

            return [
                'aplicado' => round($totalAplicado, 2),
                'saldo' => round($saldo, 2),
                'saldo_antes' => $saldoInicial,
                'aplicaciones_ids' => $aplicacionesIds
            ];
        } finally {
            try {
                self::one($cn, "SELECT RELEASE_LOCK(?) AS ok", 's', [$lockName]);
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * Restaura exclusivamente las aplicaciones creadas durante un intento
     * de pago que luego falló.
     */
    public static function revertirAplicacionesPago(
        mysqli $cn,
        int $empresaId,
        int $clienteId,
        int $facturaDestinoId,
        array $aplicacionesIds,
        ?float $saldoAntes
    ): void {
        $ids = array_values(array_filter(array_map('intval', $aplicacionesIds)));

        if (
            $empresaId <= 0 ||
            $clienteId <= 0 ||
            $facturaDestinoId <= 0 ||
            !$ids ||
            $saldoAntes === null
        ) {
            return;
        }

        $cxc = self::obtenerCxc($cn, $empresaId, $clienteId, $facturaDestinoId);
        if ($cxc) {
            $saldoRestaurado = round(max(0, (float)$saldoAntes), 2);
            $estado = $saldoRestaurado <= self::TOLERANCIA ? 2 : 1;

            self::exec(
                $cn,
                "UPDATE cobrar_clientes
                 SET saldo = ?, estado = ?
                 WHERE cobrar_clientes_id = ?",
                'dii',
                [$saldoRestaurado, $estado, (int)$cxc['cobrar_clientes_id']]
            );

            if ($saldoRestaurado > self::TOLERANCIA) {
                self::exec(
                    $cn,
                    "UPDATE facturas SET estado = 1 WHERE facturas_id = ? AND empresa_id = ?",
                    'ii',
                    [$facturaDestinoId, $empresaId]
                );
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        self::exec(
            $cn,
            "DELETE FROM notas_credito_aplicaciones
             WHERE nota_credito_aplicacion_id IN ($placeholders)",
            $types,
            $ids
        );
    }
}
