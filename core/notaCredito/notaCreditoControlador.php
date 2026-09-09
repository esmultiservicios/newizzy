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

        if ((int)($sec['documento_estado'] ?? 0) !== 1) {
            throw new Exception('El documento Nota de Crédito está inactivo. Actívelo antes de emitir.');
        }

        $incremento = (int)($sec['incremento'] ?? 0);
        if ($incremento <= 0) {
            throw new Exception('La secuencia de Nota de Crédito tiene un incremento inválido.');
        }

        $rangoInicial = (int)($sec['rango_inicial'] ?? 0);
        $rangoFinal = (int)($sec['rango_final'] ?? 0);
        if ($rangoInicial <= 0 || $rangoFinal <= 0 || $rangoInicial > $rangoFinal) {
            throw new Exception('La secuencia de Nota de Crédito tiene un rango autorizado inválido.');
        }

        $hoy = date('Y-m-d');
        $activacion = (string)($sec['fecha_activacion'] ?? '');
        $limite = (string)($sec['fecha_limite'] ?? '');

        if ($activacion === '' || $limite === '') {
            throw new Exception('La secuencia de Nota de Crédito no tiene fechas de vigencia válidas.');
        }

        if ($hoy < $activacion) {
            throw new Exception('La secuencia de Nota de Crédito todavía no está activa por fecha.');
        }

        if ($hoy > $limite) {
            throw new Exception('La secuencia de Nota de Crédito está vencida.');
        }
    }

    private function secuenciasVigentes(mysqli $cn, int $empresaId): array
    {
        $documento = notaCreditoModelo::obtenerDocumentoNotaCredito($cn);
        if (!$documento) {
            throw new Exception('No existe el documento Nota de Crédito (documento_id = 2). Créelo primero en el catálogo de documentos.');
        }

        if ((int)$documento['estado'] !== 1) {
            throw new Exception('El documento Nota de Crédito está inactivo. Actívelo antes de intentar emitir una Nota de Crédito.');
        }

        $secuencias = notaCreditoModelo::obtenerSecuenciasNotaCredito($cn, $empresaId);
        if (count($secuencias) === 0) {
            throw new Exception('No existe una secuencia activa para Nota de Crédito. Configúrela primero en Secuencia de Facturación.');
        }

        $vigentes = [];
        $errores = [];
        foreach ($secuencias as $sec) {
            try {
                $this->validarSecuencia($sec);
                $vigentes[] = $sec;
            } catch (Throwable $e) {
                $errores[] = $e->getMessage();
            }
        }

        if (count($vigentes) === 0) {
            $mensaje = count($errores) > 0 ? $errores[0] : 'No hay una secuencia vigente para Nota de Crédito.';
            throw new Exception($mensaje);
        }

        return $vigentes;
    }

    private function seleccionarNumero(mysqli $cn, int $empresaId, array $secuencias): array
    {
        /*
         * 1) Primero se buscan correlativos fallidos del documento 2.
         * 2) Un fallido SOLO puede reutilizarse si pertenece al rango de una
         *    secuencia actualmente activa y vigente.
         * 3) Si ya existe en notas_credito, el fallido es obsoleto y se elimina.
         */
        $fallidos = notaCreditoModelo::obtenerFallidosNotaCredito($cn, $empresaId);
        foreach ($fallidos as $fallido) {
            $numero = (int)$fallido['numero'];
            if ($numero <= 0) {
                continue;
            }

            foreach ($secuencias as $sec) {
                $inicial = (int)$sec['rango_inicial'];
                $final = (int)$sec['rango_final'];
                if ($numero < $inicial || $numero > $final) {
                    continue;
                }

                if (notaCreditoModelo::existeNumeroNotaDocumento($cn, $empresaId, $numero)) {
                    notaCreditoModelo::eliminarNumeroFallido($cn, $empresaId, $numero);
                    continue 2;
                }

                return [
                    'secuencia' => $sec,
                    'numero' => $numero,
                    'desde_fallida' => true,
                    'nuevo_siguiente' => (int)$sec['siguiente']
                ];
            }
        }

        /* Si no hay fallidos reutilizables, tomar el siguiente correlativo válido. */
        foreach ($secuencias as $sec) {
            $incremento = max(1, (int)$sec['incremento']);
            $numero = max((int)$sec['siguiente'], (int)$sec['rango_inicial']);
            $rangoFinal = (int)$sec['rango_final'];

            while ($numero <= $rangoFinal && notaCreditoModelo::existeNumeroNotaDocumento($cn, $empresaId, $numero)) {
                $numero += $incremento;
            }

            if ($numero <= $rangoFinal) {
                return [
                    'secuencia' => $sec,
                    'numero' => $numero,
                    'desde_fallida' => false,
                    'nuevo_siguiente' => $numero + $incremento
                ];
            }
        }

        throw new Exception('La secuencia de Nota de Crédito agotó su rango autorizado.');
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

        // No abrir/preparar el módulo si el documento 2 o su secuencia fiscal no están disponibles.
        $this->secuenciasVigentes($cn, $empresaId);

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
        $numero = 0;
        $numeroReservado = false;
        $desdeFallida = false;
        $sec = null;

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

            $secuencias = $this->secuenciasVigentes($cn, $empresaId);
            $numeroData = $this->seleccionarNumero($cn, $empresaId, $secuencias);
            $sec = $numeroData['secuencia'];
            $numero = (int)$numeroData['numero'];
            $nuevoSiguiente = (int)$numeroData['nuevo_siguiente'];
            $desdeFallida = !empty($numeroData['desde_fallida']);

            $detallesActuales = notaCreditoModelo::obtenerDetallesFactura($cn, $empresaId, $facturaId);
            $mapa = [];

            foreach ($detallesActuales as $d) {
                $mapa[(int)$d['facturas_detalle_id']] = $d;
            }

            $detalleGuardar = [];
            $detallesProcesados = [];
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

                if (isset($detallesProcesados[$detalleId])) {
                    throw new Exception('El mismo detalle de factura fue enviado más de una vez. Actualice la pantalla e intente nuevamente.');
                }
                $detallesProcesados[$detalleId] = true;

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
             * RESERVA DEL CORRELATIVO
             * -----------------------
             * secuencia_facturacion es MyISAM, por eso no puede participar en
             * el rollback InnoDB de la NC. Bajo GET_LOCK hacemos esto:
             * - si viene de secuencia_factura_fallida, NO movemos siguiente;
             * - si es un correlativo nuevo, avanzamos siguiente ANTES de emitir;
             * - si luego falla la NC, el número reservado se guarda en fallida;
             * - la próxima emisión siempre intenta recuperar fallidos primero.
             */
            if (!$desdeFallida) {
                notaCreditoModelo::actualizarSiguienteSecuencia(
                    $cn,
                    (int)$sec['secuencia_facturacion_id'],
                    $nuevoSiguiente
                );
                $numeroReservado = true;
            }

            // Última defensa antes de persistir: nunca emitir un número repetido.
            if (notaCreditoModelo::existeNumeroNotaDocumento($cn, $empresaId, $numero)) {
                if ($desdeFallida) {
                    notaCreditoModelo::eliminarNumeroFallido($cn, $empresaId, $numero);
                }
                throw new Exception('El correlativo seleccionado ya existe en Nota de Crédito. No se emitió ningún documento; intente nuevamente.');
            }

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

            // La NC ya existe: desde aquí ninguna tarea auxiliar debe convertir
            // una emisión correcta en un error visible para el usuario.
            if ($desdeFallida) {
                try {
                    notaCreditoModelo::eliminarNumeroFallido($cn, $empresaId, $numero);
                } catch (Throwable $eFallidaLimpieza) {
                    error_log('NC emitida; no se pudo limpiar correlativo fallido ' . $numero . ': ' . $eFallidaLimpieza->getMessage());
                }
            }

            try {
                notaCreditoModelo::marcarSecuenciaActualizada($cn, $notaId);
            } catch (Throwable $eMarcaSec) {
                error_log('NC emitida; no se pudo marcar secuencia_actualizada. NC=' . $notaId . ' Error=' . $eMarcaSec->getMessage());
            }

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

            /*
             * Si el correlativo NUEVO ya fue reservado en la secuencia MyISAM
             * pero la NC no llegó a existir, conservarlo para reintento.
             * Si ya existe una NC con ese número, jamás se marca como fallido.
             */
            if ($numeroReservado && $numero > 0 && is_array($sec)) {
                try {
                    $yaEmitido = notaCreditoModelo::existeNumeroNotaDocumento($cn, $empresaId, $numero);
                    if (!$yaEmitido) {
                        notaCreditoModelo::guardarNumeroFallido($cn, $empresaId, $numero);
                    }
                } catch (Throwable $eFallida) {
                    error_log('No se pudo guardar correlativo fallido NC ' . $numero . ': ' . $eFallida->getMessage());
                }
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
