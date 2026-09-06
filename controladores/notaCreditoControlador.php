<?php
// Ubicación: controladores/notaCreditoControlador.php
require_once __DIR__ . '/../modelos/notaCreditoModelo.php';

class notaCreditoControlador extends notaCreditoModelo
{
    private const DOCUMENTO_NOTA_CREDITO = 2;
    private const TOLERANCIA = 0.01;

    private function empresaId(): int
    {
        return (int)($_SESSION['empresa_id_sd'] ?? 0);
    }

    private function usuarioId(): int
    {
        return (int)($_SESSION['colaborador_id_sd'] ?? 0);
    }

    private function validarContexto(): void
    {
        if ($this->empresaId() <= 0 || $this->usuarioId() <= 0) {
            throw new Exception('La sesión no contiene una empresa o usuario válido.');
        }
    }

    private function formatoNumero(string $prefijo, int $numero, int $relleno): string
    {
        $correlativo = $relleno > 0
            ? str_pad((string)$numero, $relleno, '0', STR_PAD_LEFT)
            : (string)$numero;

        return trim($prefijo) . $correlativo;
    }

    private function obtenerLock(mysqli $cn, int $empresaId): string
    {
        $nombre = 'izzy_nc_seq_' . $empresaId . '_' . self::DOCUMENTO_NOTA_CREDITO;
        $row = notaCreditoModelo::fetchOne(
            $cn,
            "SELECT GET_LOCK(?, 15) AS obtenido",
            's',
            [$nombre]
        );

        if (!$row || (int)$row['obtenido'] !== 1) {
            throw new Exception('La secuencia de Nota de Crédito está siendo utilizada por otro proceso. Intente nuevamente.');
        }

        return $nombre;
    }

    private function liberarLock(mysqli $cn, ?string $nombre): void
    {
        if (!$nombre) {
            return;
        }

        try {
            notaCreditoModelo::fetchOne($cn, "SELECT RELEASE_LOCK(?) AS liberado", 's', [$nombre]);
        } catch (Throwable $e) {
            error_log('No se pudo liberar lock Nota de Crédito: ' . $e->getMessage());
        }
    }

    private function validarFacturaElegible(array $factura): void
    {
        if ((int)($factura['estado'] ?? 0) === 4) {
            throw new Exception('No se puede emitir una Nota de Crédito sobre una factura anulada.');
        }

        if ((int)($factura['documento_id'] ?? 0) !== 1) {
            throw new Exception('La Nota de Crédito solo puede aplicarse a una Factura Electrónica emitida. Las proformas no son elegibles.');
        }

        if ((float)($factura['importe'] ?? 0) <= 0) {
            throw new Exception('La factura no tiene un importe válido para acreditar.');
        }
    }

    private function validarSecuencia(array $sec): void
    {
        if ((int)($sec['documento_id'] ?? 0) !== self::DOCUMENTO_NOTA_CREDITO) {
            throw new Exception('La secuencia activa no corresponde a Nota de Crédito.');
        }

        if ((int)($sec['incremento'] ?? 1) !== 1) {
            throw new Exception('Para Nota de Crédito el Incremento de la secuencia debe ser 1 para mantener un correlativo continuo sin saltos.');
        }

        $hoy = date('Y-m-d');
        $activacion = (string)($sec['fecha_activacion'] ?? '');
        $limite = (string)($sec['fecha_limite'] ?? '');

        if ($activacion !== '' && $hoy < $activacion) {
            throw new Exception('La secuencia de Nota de Crédito todavía no está activa por fecha.');
        }

        if ($limite !== '' && $hoy > $limite) {
            throw new Exception('La secuencia de Nota de Crédito está vencida.');
        }
    }

    private function siguienteDisponible(mysqli $cn, array $sec): array
    {
        $empresaId = (int)$sec['empresa_id'];
        $secuenciaId = (int)$sec['secuencia_facturacion_id'];
        $incremento = max(1, (int)$sec['incremento']);
        $numero = (int)$sec['siguiente'];
        $rangoInicial = (int)$sec['rango_inicial'];
        $rangoFinal = (int)$sec['rango_final'];

        if ($numero < $rangoInicial) {
            $numero = $rangoInicial;
        }

        /*
         * AUTORREPARACIÓN:
         * Si el proceso anterior alcanzó a emitir la NC pero se cayó antes
         * de actualizar secuencia_facturacion, el número ya existe.
         * Se avanza únicamente sobre números REALMENTE emitidos.
         * Así no se duplica y tampoco se crea un salto artificial.
         */
        while (
            $numero <= $rangoFinal &&
            notaCreditoModelo::existeNumeroNota($cn, $empresaId, $secuenciaId, $numero)
        ) {
            $numero += $incremento;
        }

        if ($numero > $rangoFinal) {
            throw new Exception('La secuencia de Nota de Crédito agotó su rango autorizado.');
        }

        return [
            'numero' => $numero,
            'nuevo_siguiente' => $numero + $incremento
        ];
    }

    public function obtenerFacturaParaNota(int $facturaId): array
    {
        $this->validarContexto();
        $cn = mainModel::connection();
        $empresaId = $this->empresaId();

        $factura = notaCreditoModelo::obtenerFactura($cn, $empresaId, $facturaId);

        if (!$factura) {
            throw new Exception('No se encontró la factura solicitada.');
        }

        $this->validarFacturaElegible($factura);

        $detalles = notaCreditoModelo::obtenerDetallesFactura($cn, $empresaId, $facturaId);
        $totalAcreditado = notaCreditoModelo::obtenerTotalAcreditadoFactura($cn, $empresaId, $facturaId);

        $detalleRespuesta = [];
        foreach ($detalles as $d) {
            $baseOriginal = round((float)$d['base_original'], 4);
            $basePrevia = round((float)$d['base_acreditada_previa'], 4);
            $baseDisponible = round(max(0, $baseOriginal - $basePrevia), 4);

            $isv15Original = round((float)$d['isv_valor'], 4);
            $isv18Original = round((float)$d['isv_valor1'], 4);
            $isv15Previo = round((float)$d['isv15_acreditado_previo'], 4);
            $isv18Previo = round((float)$d['isv18_acreditado_previo'], 4);

            $detalleRespuesta[] = [
                'facturas_detalle_id' => (int)$d['facturas_detalle_id'],
                'productos_id' => (int)$d['productos_id'],
                'producto' => (string)$d['producto'],
                'cantidad' => (float)$d['cantidad'],
                'precio' => (float)$d['precio'],
                'descuento' => (float)$d['descuento'],
                'base_original' => $baseOriginal,
                'base_acreditada_previa' => $basePrevia,
                'base_disponible' => $baseDisponible,
                'isv15_original' => $isv15Original,
                'isv18_original' => $isv18Original,
                'isv15_disponible' => round(max(0, $isv15Original - $isv15Previo), 4),
                'isv18_disponible' => round(max(0, $isv18Original - $isv18Previo), 4)
            ];
        }

        $numeroFactura = $this->formatoNumero(
            (string)($factura['prefijo'] ?? ''),
            (int)$factura['number'],
            (int)($factura['relleno'] ?? 0)
        );

        return [
            'factura' => [
                'facturas_id' => (int)$factura['facturas_id'],
                'numero' => $numeroFactura,
                'cliente' => (string)$factura['cliente'],
                'rtn' => (string)$factura['rtn'],
                'fecha' => (string)$factura['fecha'],
                'importe' => round((float)$factura['importe'], 4),
                'total_acreditado' => $totalAcreditado,
                'disponible' => round(max(0, (float)$factura['importe'] - $totalAcreditado), 4)
            ],
            'detalle' => $detalleRespuesta,
            'notas' => notaCreditoModelo::listarNotasFactura($cn, $empresaId, $facturaId)
        ];
    }

    public function registrarNota(array $payload): array
    {
        $this->validarContexto();

        $empresaId = $this->empresaId();
        $usuarioId = $this->usuarioId();
        $facturaId = (int)($payload['facturas_id'] ?? 0);
        $motivo = trim((string)($payload['motivo'] ?? ''));
        $origen = trim((string)($payload['origen'] ?? 'escritorio'));
        $lineas = $payload['detalle'] ?? [];

        if ($facturaId <= 0) {
            throw new Exception('No se recibió una factura válida.');
        }

        if ($motivo === '') {
            throw new Exception('El motivo de la Nota de Crédito es obligatorio.');
        }

        if (mb_strlen($motivo) > 500) {
            $motivo = mb_substr($motivo, 0, 500);
        }

        if (!is_array($lineas) || count($lineas) === 0) {
            throw new Exception('Debe indicar al menos un monto a acreditar.');
        }

        $cn = mainModel::connection();
        $lockNombre = null;
        $notaId = 0;
        $numeroCompleto = '';
        $warningCxC = '';

        try {
            /*
             * secuencia_facturacion es MyISAM en la estructura actual de IZZY.
             * FOR UPDATE no basta para MyISAM.
             * GET_LOCK serializa la emisión entre peticiones y evita duplicados.
             */
            $lockNombre = $this->obtenerLock($cn, $empresaId);

            $factura = notaCreditoModelo::obtenerFactura($cn, $empresaId, $facturaId);
            if (!$factura) {
                throw new Exception('La factura seleccionada ya no existe.');
            }

            $this->validarFacturaElegible($factura);

            $sec = notaCreditoModelo::obtenerSecuenciaNotaCredito($cn, $empresaId);
            if (!$sec) {
                throw new Exception('No existe una secuencia activa para Nota de Crédito. Configúrela primero en Secuencia de Facturación.');
            }

            $this->validarSecuencia($sec);

            $numeroData = $this->siguienteDisponible($cn, $sec);
            $numero = (int)$numeroData['numero'];
            $nuevoSiguiente = (int)$numeroData['nuevo_siguiente'];

            $detallesActuales = notaCreditoModelo::obtenerDetallesFactura($cn, $empresaId, $facturaId);
            $mapa = [];

            foreach ($detallesActuales as $d) {
                $mapa[(int)$d['facturas_detalle_id']] = $d;
            }

            $detalleGuardar = [];
            $baseTotal = 0.0;
            $isv15Total = 0.0;
            $isv18Total = 0.0;

            foreach ($lineas as $solicitud) {
                $detalleId = (int)($solicitud['facturas_detalle_id'] ?? 0);
                $baseSolicitada = round((float)($solicitud['base_acreditar'] ?? 0), 4);

                if ($detalleId <= 0 || $baseSolicitada <= 0) {
                    continue;
                }

                if (!isset($mapa[$detalleId])) {
                    throw new Exception('Uno de los detalles seleccionados no pertenece a la factura.');
                }

                $d = $mapa[$detalleId];

                $baseOriginal = round((float)$d['base_original'], 4);
                $basePrevia = round((float)$d['base_acreditada_previa'], 4);
                $baseDisponible = round(max(0, $baseOriginal - $basePrevia), 4);

                if ($baseSolicitada - $baseDisponible > self::TOLERANCIA) {
                    throw new Exception('El monto solicitado supera el saldo acreditable del producto: ' . $d['producto']);
                }

                $baseSolicitada = min($baseSolicitada, $baseDisponible);

                $isv15Original = round((float)$d['isv_valor'], 4);
                $isv18Original = round((float)$d['isv_valor1'], 4);
                $isv15Previo = round((float)$d['isv15_acreditado_previo'], 4);
                $isv18Previo = round((float)$d['isv18_acreditado_previo'], 4);

                $esCierreLinea = abs($baseSolicitada - $baseDisponible) <= self::TOLERANCIA;

                if ($esCierreLinea) {
                    $isv15 = round(max(0, $isv15Original - $isv15Previo), 4);
                    $isv18 = round(max(0, $isv18Original - $isv18Previo), 4);
                } else {
                    $factor = $baseOriginal > 0 ? ($baseSolicitada / $baseOriginal) : 0;
                    $isv15 = round($isv15Original * $factor, 4);
                    $isv18 = round($isv18Original * $factor, 4);
                }

                $totalLinea = round($baseSolicitada + $isv15 + $isv18, 4);

                $detalleGuardar[] = [
                    'facturas_detalle_id' => $detalleId,
                    'productos_id' => (int)$d['productos_id'],
                    'producto' => (string)$d['producto'],
                    'cantidad_original' => (float)$d['cantidad'],
                    'precio_original' => (float)$d['precio'],
                    'descuento_original' => (float)$d['descuento'],
                    'base_original' => $baseOriginal,
                    'isv15_original' => $isv15Original,
                    'isv18_original' => $isv18Original,
                    'base_acreditada' => $baseSolicitada,
                    'isv15_acreditado' => $isv15,
                    'isv18_acreditado' => $isv18,
                    'total_acreditado' => $totalLinea
                ];

                $baseTotal += $baseSolicitada;
                $isv15Total += $isv15;
                $isv18Total += $isv18;
            }

            if (count($detalleGuardar) === 0) {
                throw new Exception('Ingrese un monto mayor a cero en al menos una línea.');
            }

            $baseTotal = round($baseTotal, 4);
            $isv15Total = round($isv15Total, 4);
            $isv18Total = round($isv18Total, 4);
            $totalNc = round($baseTotal + $isv15Total + $isv18Total, 4);

            $totalAcreditadoAnterior = notaCreditoModelo::obtenerTotalAcreditadoFactura($cn, $empresaId, $facturaId);
            $importeFactura = round((float)$factura['importe'], 4);
            $disponibleFactura = round(max(0, $importeFactura - $totalAcreditadoAnterior), 4);

            if ($totalNc - $disponibleFactura > self::TOLERANCIA) {
                throw new Exception('La Nota de Crédito supera el importe aún disponible de la factura.');
            }

            $numeroCompleto = $this->formatoNumero(
                (string)$sec['prefijo'],
                $numero,
                (int)$sec['relleno']
            );

            $fechaRegistro = date('Y-m-d H:i:s');
            $fecha = date('Y-m-d');

            /*
             * Las tablas de NC son InnoDB.
             * La secuencia MyISAM se incrementa DESPUÉS del commit de la NC:
             * - si falla detalle/cabecera, no se consumió correlativo;
             * - si la app cae después del commit pero antes de actualizar secuencia,
             *   la siguiente emisión detecta el número existente y se autorrepara.
             */
            $cn->begin_transaction();

            $notaId = notaCreditoModelo::insertarCabecera($cn, [
                'empresa_id' => $empresaId,
                'facturas_id' => $facturaId,
                'clientes_id' => (int)$factura['clientes_id'],
                'secuencia_facturacion_id' => (int)$sec['secuencia_facturacion_id'],
                'number' => $numero,
                'prefijo' => (string)$sec['prefijo'],
                'relleno' => (int)$sec['relleno'],
                'numero_completo' => $numeroCompleto,
                'fecha' => $fecha,
                'motivo' => $motivo,
                'base_acreditada' => $baseTotal,
                'isv15_acreditado' => $isv15Total,
                'isv18_acreditado' => $isv18Total,
                'total_acreditado' => $totalNc,
                'importe_factura_original' => $importeFactura,
                'total_acreditado_anterior' => $totalAcreditadoAnterior,
                'colaboradores_id' => $usuarioId,
                'usuario' => $usuarioId,
                'origen' => $origen === '' ? 'escritorio' : mb_substr($origen, 0, 30),
                'fecha_registro' => $fechaRegistro
            ]);

            foreach ($detalleGuardar as $detalle) {
                $detalle['nota_credito_id'] = $notaId;
                $detalle['fecha_registro'] = $fechaRegistro;
                notaCreditoModelo::insertarDetalle($cn, $detalle);
            }

            $cn->commit();

            // Consumir secuencia SOLO después de una NC emitida y persistida.
            notaCreditoModelo::actualizarSiguienteSecuencia(
                $cn,
                (int)$sec['secuencia_facturacion_id'],
                $nuevoSiguiente
            );
            notaCreditoModelo::marcarSecuenciaActualizada($cn, $notaId);

            /*
             * CxC es MyISAM en IZZY. No se mezcla con el commit fiscal:
             * una falla auxiliar NO debe borrar una NC ya emitida.
             * Se registra cxc_aplicada para permitir detectar/reparar.
             */
            try {
                $cxc = notaCreditoModelo::aplicarCxC($cn, $empresaId, $facturaId, $totalNc);
                notaCreditoModelo::actualizarResultadoCxC($cn, $notaId, $cxc);
            } catch (Throwable $eCxC) {
                $warningCxC = ' La Nota de Crédito fue emitida, pero la cuenta por cobrar requiere revisión.';
                error_log('NC emitida con CxC pendiente. NC=' . $notaId . ' Error=' . $eCxC->getMessage());
            }

            try {
                mainModel::guardarHistorial([
                    'modulo' => 'Nota de Crédito',
                    'colaboradores_id' => $usuarioId,
                    'status' => 'Registro',
                    'observacion' => 'Se emitió Nota de Crédito ' . $numeroCompleto .
                        ' sobre la factura ID ' . $facturaId .
                        ' por L ' . number_format($totalNc, 2, '.', ','),
                    'fecha_registro' => $fechaRegistro
                ]);
            } catch (Throwable $eHist) {
                error_log('No se pudo guardar historial NC: ' . $eHist->getMessage());
            }

            return [
                'nota_credito_id' => $notaId,
                'numero' => $numeroCompleto,
                'total' => $totalNc,
                'warning' => trim($warningCxC)
            ];
        } catch (Throwable $e) {
            try {
                $cn->rollback();
            } catch (Throwable $eRollback) {
            }

            throw $e;
        } finally {
            $this->liberarLock($cn, $lockNombre);
        }
    }

    public function listarNotas(int $facturaId): array
    {
        $this->validarContexto();
        return notaCreditoModelo::listarNotasFactura(
            mainModel::connection(),
            $this->empresaId(),
            $facturaId
        );
    }

    public function obtenerNotaPorId(int $notaId): array
    {
        $this->validarContexto();
        $nota = notaCreditoModelo::obtenerNota(
            mainModel::connection(),
            $this->empresaId(),
            $notaId
        );

        if (!$nota) {
            throw new Exception('No se encontró la Nota de Crédito.');
        }

        return $nota;
    }
}
