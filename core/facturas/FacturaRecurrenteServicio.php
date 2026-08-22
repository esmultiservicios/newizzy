<?php
// Ubicación: core/facturas/FacturaRecurrenteServicio.php

class FacturaRecurrenteServicio extends facturasControlador
{
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
        $fecha = date('Y-m-d');
        $fechaRegistro = date('Y-m-d H:i:s');

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
                'notas' => (string)$recurrente['notas'],
                'fecha' => $fecha,
                'estado' => 3,
                'usuario' => $usuarioId,
                'fecha_registro' => $fechaRegistro,
                'empresa' => $empresaId,
                'fecha_dolar' => (string)$recurrente['fecha_dolar'],
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
