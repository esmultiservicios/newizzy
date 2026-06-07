<?php
if ($peticionAjax) {
    require_once "../modelos/cotizacionModelo.php";
} else {
    require_once "./modelos/cotizacionModelo.php";
}

class cotizacionControlador extends cotizacionModelo
{
    private function normalizarMontoCotizacion($valor)
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        $valor = str_replace(['L.', 'L', ',', ' '], '', (string)$valor);

        if (!is_numeric($valor)) {
            return 0;
        }

        return (float)$valor;
    }

    public function agregar_cotizacion_controlador()
    {
        $validacion = mainModel::validarSesion();

        if ($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
            ]);
        }

        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();

        if (isset($planConfig['cotizaciones'])) {
            $limiteCotizaciones = (int)$planConfig['cotizaciones'];

            if ($limiteCotizaciones === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan no incluye la creación de cotizaciones."
                ]);
            }

            $totalRegistradas = (int)$this->getTotalCotizacionesRegistradas();

            if ($totalRegistradas >= $limiteCotizaciones) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Ha excedido el límite mensual de cotizaciones (Máximo: $limiteCotizaciones)."
                ]);
            }
        }

        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];

        $clientes_id = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        $colaborador_id = isset($_POST['colaborador_id']) ? (int)$_POST['colaborador_id'] : 0;
        $notas = isset($_POST['notesQuote']) ? mainModel::cleanStringConverterCase($_POST['notesQuote']) : '';
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date("Y-m-d");
        $fecha_dolar = isset($_POST['fecha_dolar']) ? $_POST['fecha_dolar'] : date("Y-m-d");
        $fecha_registro = date("Y-m-d H:i:s");

        $estado = 1;
        $cotizacion_id = mainModel::correlativo("cotizacion_id", "cotizacion");
        $numero = mainModel::correlativo("number", "cotizacion");
        $tipo_factura = 1;

        $vigencia_quote = isset($_POST['vigencia_quote']) ? ($_POST['vigencia_quote'] == "" ? 0 : (int)$_POST['vigencia_quote']) : 0;

        if ($clientes_id <= 0 || $colaborador_id <= 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error en registros",
                "text" => "El cliente y el vendedor no pueden quedar en blanco, por favor corregir"
            ]);
        }

        if (
            !isset($_POST['productNameQuote']) ||
            !isset($_POST['productosQuote_id']) ||
            !isset($_POST['quantityQuote']) ||
            !isset($_POST['priceQuote'])
        ) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error en registros",
                "text" => "No ha seleccionado productos en el detalle de la cotización, debe seleccionar al menos un producto"
            ]);
        }

        $item = count($_POST['productNameQuote']);
        $productos_validos = 0;

        for ($i = 0; $i < $item; $i++) {
            $producto_id_validar = isset($_POST['productosQuote_id'][$i]) ? trim($_POST['productosQuote_id'][$i]) : '';
            $producto_nombre_validar = isset($_POST['productNameQuote'][$i]) ? trim($_POST['productNameQuote'][$i]) : '';
            $cantidad_validar = isset($_POST['quantityQuote'][$i]) ? $this->normalizarMontoCotizacion($_POST['quantityQuote'][$i]) : 0;
            $precio_validar = isset($_POST['priceQuote'][$i]) ? $this->normalizarMontoCotizacion($_POST['priceQuote'][$i]) : 0;

            if ($producto_id_validar !== '' && $producto_nombre_validar !== '' && $cantidad_validar > 0 && $precio_validar >= 0) {
                $productos_validos++;
            }
        }

        if ($productos_validos <= 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error en registros",
                "text" => "No ha seleccionado productos válidos en el detalle de la cotización"
            ]);
        }

        $datos = [
            "cotizacion_id" => $cotizacion_id,
            "clientes_id" => $clientes_id,
            "tipo_factura" => $tipo_factura,
            "numero" => $numero,
            "colaboradores_id" => $colaborador_id,
            "importe" => 0,
            "notas" => $notas,
            "fecha" => $fecha,
            "estado" => $estado,
            "usuario" => $usuario,
            "fecha_registro" => $fecha_registro,
            "empresa" => $empresa_id,
            "vigencia_quote" => $vigencia_quote,
            "fecha_dolar" => $fecha_dolar
        ];

        $query = $this->agregar_cotizacion_modelo($datos);

        if (!$query) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo procesar la solicitud de cotización"
            ]);
        }

        $total_valor = 0;
        $descuentos = 0;
        $isv_neto = 0;

        for ($i = 0; $i < $item; $i++) {
            $productos_id = isset($_POST['productosQuote_id'][$i]) ? trim($_POST['productosQuote_id'][$i]) : '';
            $productName = isset($_POST['productNameQuote'][$i]) ? trim($_POST['productNameQuote'][$i]) : '';
            $quantity = isset($_POST['quantityQuote'][$i]) ? $this->normalizarMontoCotizacion($_POST['quantityQuote'][$i]) : 0;
            $price = isset($_POST['priceQuote'][$i]) ? $this->normalizarMontoCotizacion($_POST['priceQuote'][$i]) : 0;
            $discount = isset($_POST['discountQuote'][$i]) ? $this->normalizarMontoCotizacion($_POST['discountQuote'][$i]) : 0;

            /*
                Ahora se guarda separado:
                valorQuote_isv[]  -> cotizacion_detalles.isv_valor
                valorQuote_isv1[] -> cotizacion_detalles.isv_valor1
            */
            $isv_1 = isset($_POST['valorQuote_isv'][$i]) ? $this->normalizarMontoCotizacion($_POST['valorQuote_isv'][$i]) : 0;
            $isv_2 = isset($_POST['valorQuote_isv1'][$i]) ? $this->normalizarMontoCotizacion($_POST['valorQuote_isv1'][$i]) : 0;

            if ($productos_id !== '' && $productName !== '' && $quantity > 0 && $price >= 0) {
                $datos_detalles_cotizacion = [
                    "cotizacion_id" => $cotizacion_id,
                    "productos_id" => (int)$productos_id,
                    "cantidad" => $quantity,
                    "precio" => $price,
                    "isv_valor" => $isv_1,
                    "isv_valor1" => $isv_2,
                    "descuento" => $discount
                ];

                $detalle_guardado = $this->agregar_detalle_cotizacion($datos_detalles_cotizacion);

                if (!$detalle_guardado) {
                    return mainModel::showNotification([
                        "type" => "error",
                        "title" => "Error",
                        "text" => "No se pudo registrar uno de los detalles de la cotización"
                    ]);
                }

                $total_valor += ($price * $quantity);
                $descuentos += $discount;
                $isv_neto += ($isv_1 + $isv_2);
            }
        }

        $total_despues_isv = ($total_valor + $isv_neto) - $descuentos;

        $datos_factura = [
            "cotizacion_id" => $cotizacion_id,
            "importe" => $total_despues_isv
        ];

        $this->actualizar_cotizacion_importe($datos_factura);

        mainModel::guardarHistorial([
            "modulo" => 'Cotizaciones',
            "colaboradores_id" => $usuario,
            "status" => "Registro",
            "observacion" => "Se registró la cotización #{$numero}",
            "fecha_registro" => $fecha_registro
        ]);

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "La cotización se ha registrado correctamente",
            "form" => "quoteForm",
            "funcion" => "limpiarTablaQuote();printQuote(" . $cotizacion_id . ");mailQuote(" . $cotizacion_id . ");getConsumidorFinal();getCajero();cleanFooterValueQuote();resetRow();"
        ]);
    }

    public function cancelar_cotizacion_controlador()
    {
        $validacion = mainModel::validarSesion();

        if ($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
            ]);
        }

        $cotizacion_id = isset($_POST['cotizacion_id']) ? (int)$_POST['cotizacion_id'] : 0;

        if ($cotizacion_id <= 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se recibió la cotización a cancelar"
            ]);
        }

        $campos = ['number'];
        $tabla = "cotizacion";
        $condicion = "cotizacion_id = {$cotizacion_id}";

        $cotizacion = mainModel::consultar_tabla($tabla, $campos, $condicion);
        $numero = isset($cotizacion[0]['number']) ? $cotizacion[0]['number'] : 'desconocido';

        if (!$this->cancelar_cotizacion_modelo($cotizacion_id)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo cancelar la cotización"
            ]);
        }

        mainModel::guardarHistorial([
            "modulo" => 'Cotizaciones',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Cancelación",
            "observacion" => "Se canceló la cotización #{$numero}",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Cancelación exitosa",
            "text" => "La cotización ha sido cancelada correctamente",
            "funcion" => "listar_cotizaciones();"
        ]);
    }
}