<?php
// Ubicación: core/facturas/FacturaRecurrenteServicio.php

class SecuenciaFiscalNoDisponibleException extends RuntimeException {}

class FacturaRecurrenteServicio extends facturasControlador
{
    public function verificarSecuenciaRecurrente($cn, int $empresaId, int $documentoId, string $fecha): array
    {
        $stmt = $cn->prepare('SELECT secuencia_facturacion_id, siguiente, rango_inicial, rango_final, fecha_activacion, fecha_limite FROM secuencia_facturacion WHERE empresa_id = ? AND documento_id = ? AND activo = 1 ORDER BY secuencia_facturacion_id DESC LIMIT 1');
        if (!$stmt) { throw new RuntimeException('No se pudo consultar la autorización fiscal: '.$cn->error); }
        $stmt->bind_param('ii', $empresaId, $documentoId);
        $stmt->execute();
        $sec = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$sec) { return ['disponible'=>false, 'motivo'=>'No existe secuencia fiscal activa.']; }
        if ($fecha < $sec['fecha_activacion'] || $fecha > $sec['fecha_limite']) {
            return ['disponible'=>false, 'motivo'=>'La secuencia fiscal no está vigente (fecha de activación o vencimiento).'];
        }
        $inicio = (int)$sec['rango_inicial'];
        $fin = (int)$sec['rango_final'];
        if ($inicio <= 0 || $fin < $inicio) {
            return ['disponible'=>false, 'motivo'=>'La secuencia fiscal tiene un rango inválido.'];
        }
        if (max($inicio, (int)$sec['siguiente']) <= $fin) {
            return ['disponible'=>true, 'motivo'=>''];
        }
        // La facturación normal puede reutilizar números fallidos válidos.
        $stmt = $cn->prepare('SELECT 1 FROM secuencia_factura_fallida AS sf WHERE sf.empresa_id = ? AND sf.documento_id = ? AND sf.numero BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM facturas AS f WHERE f.empresa_id = sf.empresa_id AND f.secuencia_facturacion_id = ? AND f.number = sf.numero) LIMIT 1');
        if (!$stmt) { throw new RuntimeException('No se pudo consultar la numeración recuperable.'); }
        $seqId = (int)$sec['secuencia_facturacion_id'];
        $stmt->bind_param('iiiii', $empresaId, $documentoId, $inicio, $fin, $seqId);
        $stmt->execute();
        $hay = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ['disponible'=>$hay, 'motivo'=>$hay ? '' : 'La secuencia fiscal alcanzó el límite de numeración autorizada.'];
    }

    /**
     * Genera una factura al crédito usando la misma secuencia, impuestos,
     * inventario, proforma y CxC del controlador normal.
     * La transacción la controla procesarFacturasRecurrentes.php.
     */
    public function generar(array $recurrente, array $detalle, $conexion)
    {
        $empresaId = (int)$recurrente['empresa_id'];
        $clienteId = (int)$recurrente['clientes_id'];
        $colaboradorId = (int)$recurrente['colaboradores_id'];
        $usuarioId = (int)$recurrente['usuario_crea'];
        $esProforma = ((int)$recurrente['tipo_documento'] === 1);
        $documentoId = $esProforma ? 4 : 1;
        // Una misma fecha real de emisión para encabezado, detalle, CxC y tasa.
        // No utilizar next_run_at como fecha fiscal del documento.
        $zona = new DateTimeZone('America/Tegucigalpa');
        $instanteEmision = new DateTimeImmutable('now', $zona);
        $fecha = (string)($recurrente['fecha_emision'] ?? $instanteEmision->format('Y-m-d'));
        $fechaRegistro = (string)($recurrente['fecha_registro_emision'] ?? $instanteEmision->format('Y-m-d H:i:s'));
        if (!DateTimeImmutable::createFromFormat('!Y-m-d', $fecha, $zona)
            || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fechaRegistro)
            || substr($fechaRegistro, 0, 10) !== $fecha) {
            throw new InvalidArgumentException('Fecha de emisión inconsistente.');
        }

        if ($empresaId <= 0 || $clienteId <= 0 || $colaboradorId <= 0 || empty($detalle)) {
            throw new Exception('La plantilla recurrente no tiene encabezado o detalle válido.');
        }

        // Cada empresa utiliza su propia base de datos; clientes no posee empresa_id.
        $stmtCliente = $conexion->prepare(
            'SELECT clientes_id FROM clientes WHERE clientes_id = ? LIMIT 1'
        );
        if (!$stmtCliente) {
            throw new Exception('No se pudo validar el cliente: '.$conexion->error);
        }
        $stmtCliente->bind_param('i', $clienteId);
        $stmtCliente->execute();
        $clienteExiste = $stmtCliente->get_result();
        $stmtCliente->close();
        if (!$clienteExiste || $clienteExiste->num_rows === 0) {
            throw new Exception('El cliente de la recurrencia ya no está disponible en esta base de datos.');
        }

        // Comprobación anticipada: nunca consumir número si la autorización
        // fiscal venció o ya no hay números disponibles.
        $disponibilidad = $this->verificarSecuenciaRecurrente($conexion, $empresaId, $documentoId, $fecha);
        if (!$disponibilidad['disponible']) {
            throw new SecuenciaFiscalNoDisponibleException($disponibilidad['motivo']);
        }

        // Utiliza el mismo correlativo interno que la facturación normal.
        // No se bloquea facturas desde la conexión de la recurrencia porque
        // el modelo heredado guarda el encabezado mediante su propia conexión.
        $facturasId = (int)$this->correlativo('facturas_id', 'facturas');
        if ($facturasId <= 0) {
            throw new Exception('No se pudo obtener el identificador interno de la factura.');
        }

        // La lógica heredada de facturación utiliza sus propias conexiones.
        // Reservar y confirmar la secuencia en su conexión local evita que la
        // transacción de la recurrencia se bloquee a sí misma.
        $numeroFactura = $this->obtenerNumeroFactura($empresaId, $documentoId);
        if (!empty($numeroFactura['error'])) {
            throw new Exception($numeroFactura['mensaje'] ?? 'No se pudo obtener la secuencia de facturación.');
        }

        $postAnterior = $_POST;
        try {
            $_POST['productName'] = [];
            $_POST['productos_id'] = [];
            $_POST['quantity'] = [];
            $_POST['price'] = [];
            $_POST['discount'] = [];
            $_POST['valor_isv'] = [];
            $_POST['valor_isv1'] = [];
            $_POST['medida'] = [];
            $_POST['bodega'] = [];
            $_POST['referenciaProducto'] = [];
            $_POST['precio_real'] = [];

            foreach ($detalle as $fila) {
                $_POST['productName'][] = (string)$fila['producto'];
                $_POST['productos_id'][] = (int)$fila['productos_id'];
                $_POST['quantity'][] = (float)$fila['cantidad'];
                $_POST['price'][] = (float)$fila['precio'];
                $_POST['discount'][] = (float)$fila['descuento'];
                $_POST['valor_isv'][] = (float)$fila['isv_valor'];
                $_POST['valor_isv1'][] = (float)($fila['isv_valor1'] ?? 0);
                $_POST['medida'][] = (string)($fila['medida'] ?: 'Und');
                $_POST['bodega'][] = (int)($fila['almacen_id'] ?? 0);
                $_POST['referenciaProducto'][] = (string)($fila['referencia_producto'] ?? '');
                $_POST['precio_real'][] = (float)($fila['precio_real'] ?? $fila['precio']);
            }

            $datosFactura = [
                'facturas_id' => $facturasId,
                'clientes_id' => $clienteId,
                'secuencia_facturacion_id' => (int)$numeroFactura['data']['secuencia_facturacion_id'],
                'apertura_id' => 0,
                'tipo_factura' => 2,
                'numero' => (int)$numeroFactura['data']['numero'],
                'colaboradores_id' => $colaboradorId,
                'importe' => 0,
                'notas' => (string)($recurrente['notas'] ?? ''),
                'fecha' => $fecha,
                'estado' => 3,
                'usuario' => $usuarioId,
                'fecha_registro' => $fechaRegistro,
                'empresa' => $empresaId,
                'fecha_dolar' => $fecha,
                'exoneracion_orden' => $recurrente['exoneracion_orden'],
                'exoneracion_constancia' => $recurrente['exoneracion_constancia'],
                'exoneracion_sag' => $recurrente['exoneracion_sag'],
                'exoneracion_orden_interno' => $recurrente['exoneracion_orden_interno']
            ];

            if (!facturasModelo::guardar_facturas_modelo($datosFactura)) {
                throw new Exception('No se pudo guardar el encabezado de la factura recurrente.');
            }

            $bajarInventario = $esProforma ? $this->proformaRebajaInventario() : true;
            $totales = $this->procesarDetalleFactura(
                $facturasId,
                $clienteId,
                $fecha,
                $fechaRegistro,
                $empresaId,
                $bajarInventario,
                $esProforma ? '1' : '0'
            );

            if (!facturasModelo::actualizar_factura_importe([
                'facturas_id' => $facturasId,
                'importe' => $totales['total_despues_isv']
            ])) {
                throw new Exception('No se pudo actualizar el total de la factura recurrente.');
            }

            if ($esProforma && !$this->guardarFacturaProformaRelacion(
                $facturasId,
                $clienteId,
                (int)$numeroFactura['data']['secuencia_facturacion_id'],
                (int)$numeroFactura['data']['numero'],
                (float)$totales['total_despues_isv'],
                $usuarioId,
                $empresaId,
                $fechaRegistro
            )) {
                throw new Exception('No se pudo guardar la relación de la proforma recurrente.');
            }

            if (!$this->guardarCuentaPorCobrar(
                $clienteId,
                $facturasId,
                $fecha,
                (float)$totales['total_despues_isv'],
                1,
                2,
                $usuarioId,
                $fechaRegistro,
                $empresaId
            )) {
                throw new Exception('No se pudo crear la cuenta por cobrar recurrente.');
            }

            return [
                'facturas_id' => $facturasId,
                'numero' => (int)$numeroFactura['data']['numero'],
                'prefijo' => (string)($numeroFactura['data']['prefijo'] ?? ''),
                'relleno' => (int)($numeroFactura['data']['relleno'] ?? 8),
                'total' => (float)$totales['total_despues_isv'],
                'es_proforma' => $esProforma
            ];
        } finally {
            $_POST = $postAnterior;
        }
    }
}
