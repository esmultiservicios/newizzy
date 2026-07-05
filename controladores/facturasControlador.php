<?php
// facturasControlador.php - Factura Escritorio
if($peticionAjax){
    require_once "../modelos/facturasModelo.php";
}else{
    require_once "./modelos/facturasModelo.php";
}

class facturasControlador extends facturasModelo {

    /* ===========================
     * SECUENCIA: N° FACTURA (con FOR UPDATE y recuperación de fallidos)
     * =========================== */
    protected function obtenerNumeroFactura($empresa_id, $documento_id, $conexion = null) {
        /*
         * Regla:
         * 1) Primero intenta usar secuencia_factura_fallida.
         * 2) Antes de usar un número fallido, valida si ya existe en facturas.
         *    Si existe, lo elimina de secuencia_factura_fallida y busca otro.
         * 3) Si no hay fallidos válidos, toma el siguiente de secuencia_facturacion.
         * 4) Si el siguiente ya existe en facturas, lo salta y avanza hasta encontrar uno libre.
         */
        $conexionLocal = false;

        try {
            if($conexion === null) {
                $conexion = mainModel::connection();
                $conexionLocal = true;
            }

            if($conexionLocal) {
                $conexion->begin_transaction();
            }

            // Bloquear secuencia activa del documento
            $sqlSec = "SELECT secuencia_facturacion_id, prefijo, siguiente, rango_final, incremento, relleno
                       FROM secuencia_facturacion
                       WHERE empresa_id = ? AND documento_id = ? AND activo = 1
                       LIMIT 1 FOR UPDATE";
            $stmtSec = $conexion->prepare($sqlSec);
            if(!$stmtSec){
                if($conexionLocal) $conexion->rollback();
                return ['error'=>true, 'mensaje'=>'Error al preparar secuencia: '.$conexion->error];
            }

            $stmtSec->bind_param("ii", $empresa_id, $documento_id);
            $stmtSec->execute();
            $resSec = $stmtSec->get_result();

            if(!$resSec || $resSec->num_rows === 0){
                $stmtSec->close();
                if($conexionLocal) $conexion->rollback();
                return ['error'=>true, 'mensaje'=>'No se encontró secuencia activa'];
            }

            $sec = $resSec->fetch_assoc();
            $stmtSec->close();

            $secuenciaId = (int)$sec['secuencia_facturacion_id'];

            // Helper local: verifica si el número ya existe en facturas para esta empresa/secuencia.
            $numeroExiste = function($numero) use ($conexion, $empresa_id, $secuenciaId) {
                $numero = (int)$numero;
                $stmtExiste = $conexion->prepare(
                    "SELECT facturas_id
                     FROM facturas
                     WHERE empresa_id = ?
                       AND secuencia_facturacion_id = ?
                       AND number = ?
                     LIMIT 1"
                );

                if(!$stmtExiste){
                    // Si no puede validar, por seguridad se considera existente.
                    error_log("No se pudo validar duplicado de factura: ".$conexion->error);
                    return true;
                }

                $stmtExiste->bind_param("iii", $empresa_id, $secuenciaId, $numero);
                $stmtExiste->execute();
                $resExiste = $stmtExiste->get_result();
                $existe = ($resExiste && $resExiste->num_rows > 0);
                $stmtExiste->close();

                return $existe;
            };

            // 1) Procesar números fallidos, eliminando los que ya existen en facturas.
            while(true){
                $sqlFallido = "SELECT numero
                               FROM secuencia_factura_fallida
                               WHERE empresa_id = ? AND documento_id = ?
                               ORDER BY numero ASC
                               LIMIT 1 FOR UPDATE";
                $stmtFallido = $conexion->prepare($sqlFallido);
                if(!$stmtFallido){
                    if($conexionLocal) $conexion->rollback();
                    return ['error'=>true, 'mensaje'=>'Error al preparar secuencia fallida: '.$conexion->error];
                }

                $stmtFallido->bind_param("ii", $empresa_id, $documento_id);
                $stmtFallido->execute();
                $resFallido = $stmtFallido->get_result();

                if(!$resFallido || $resFallido->num_rows === 0){
                    $stmtFallido->close();
                    break;
                }

                $rowFallido = $resFallido->fetch_assoc();
                $numeroFallido = (int)$rowFallido['numero'];
                $stmtFallido->close();

                // Siempre se elimina el fallido que se está evaluando.
                $stmtDel = $conexion->prepare(
                    "DELETE FROM secuencia_factura_fallida
                     WHERE empresa_id = ? AND documento_id = ? AND numero = ?"
                );
                if($stmtDel){
                    $stmtDel->bind_param("iii", $empresa_id, $documento_id, $numeroFallido);
                    $stmtDel->execute();
                    $stmtDel->close();
                }

                if($numeroFallido <= 0){
                    continue;
                }

                if($numeroExiste($numeroFallido)){
                    error_log("Número fallido {$numeroFallido} descartado porque ya existe en facturas. Empresa={$empresa_id}, secuencia={$secuenciaId}");
                    continue;
                }

                if($conexionLocal) $conexion->commit();

                return [
                    'error'=>false,
                    'data'=>[
                        'secuencia_facturacion_id'=>$secuenciaId,
                        'numero'=>$numeroFallido,
                        'prefijo'=>$sec['prefijo'] ?? '',
                        'relleno'=>$sec['relleno'] ?? ''
                    ]
                ];
            }

            // 2) Secuencia normal: saltar cualquier número que ya exista en facturas.
            $siguiente = (int)$sec['siguiente'];
            $rangoFinal = (int)$sec['rango_final'];
            $incremento = (int)$sec['incremento'];

            if($incremento <= 0){
                $incremento = 1;
            }

            while($siguiente <= $rangoFinal){
                if(!$numeroExiste($siguiente)){
                    $nuevo = $siguiente + $incremento;

                    $stmtUpd = $conexion->prepare(
                        "UPDATE secuencia_facturacion
                         SET siguiente = ?
                         WHERE secuencia_facturacion_id = ?"
                    );

                    if(!$stmtUpd){
                        if($conexionLocal) $conexion->rollback();
                        return ['error'=>true, 'mensaje'=>'Error al preparar actualización de secuencia: '.$conexion->error];
                    }

                    $stmtUpd->bind_param("ii", $nuevo, $secuenciaId);

                    if(!$stmtUpd->execute()){
                        $stmtUpd->close();
                        if($conexionLocal) $conexion->rollback();
                        return ['error'=>true, 'mensaje'=>'Error al actualizar secuencia'];
                    }

                    $stmtUpd->close();

                    if($conexionLocal) $conexion->commit();

                    return [
                        'error'=>false,
                        'data'=>[
                            'secuencia_facturacion_id'=>$secuenciaId,
                            'numero'=>$siguiente,
                            'prefijo'=>$sec['prefijo'] ?? '',
                            'relleno'=>$sec['relleno'] ?? ''
                        ]
                    ];
                }

                error_log("Número {$siguiente} saltado porque ya existe en facturas. Empresa={$empresa_id}, secuencia={$secuenciaId}");
                $siguiente += $incremento;
            }

            // Actualizar la secuencia al valor siguiente aunque ya se terminó el rango, para no repetir intentos.
            $stmtFin = $conexion->prepare(
                "UPDATE secuencia_facturacion
                 SET siguiente = ?
                 WHERE secuencia_facturacion_id = ?"
            );
            if($stmtFin){
                $stmtFin->bind_param("ii", $siguiente, $secuenciaId);
                $stmtFin->execute();
                $stmtFin->close();
            }

            if($conexionLocal) $conexion->rollback();

            return ['error'=>true, 'mensaje'=>'Se ha alcanzado el límite del rango de numeración'];

        } catch (Exception $e) {
            if($conexionLocal && isset($conexion)) {
                $conexion->rollback();
            }

            error_log("Error en obtenerNumeroFactura: ".$e->getMessage());

            return [
                'error'=>true,
                'mensaje'=>'Error al generar número de factura: '.$e->getMessage()
            ];
        }
    }

    /* ===========================
     * MAPEO DE DATOS DE FACTURA
     * =========================== */
    protected function prepararDatosFactura($tipo_factura, $tipo_documento) {
        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];

        // Documentos: 0=Factura Electrónica, 1=Proforma
        $documento_id = ($tipo_documento === "1") ? "4" : "1";
        $documento_nombre = ($tipo_documento === "1") ? "Factura Proforma" : "Factura Electronica";

        // Estado: 2=contado, 3=crédito
        $estado = ($tipo_factura == 1) ? 2 : 3;

        return compact('usuario','empresa_id','documento_id','documento_nombre','estado');
    }

    /* ===========================
     * VALIDACIONES BÁSICAS
     * =========================== */
    protected function validarDatosFormulario() {
        if(empty($_POST['cliente_id']) || empty($_POST['colaborador_id'])) {
            return [
                'error'=>true,
                'notification'=>[
                    "title"=>"Error",
                    "text"=>"El cliente y el vendedor no pueden quedar en blanco",
                    "type"=>"error"
                ]
            ];
        }

        if(empty($_POST['productName']) || empty($_POST['productName'][0])) {
            return [
                'error'=>true,
                'notification'=>[
                    "title"=>"Error",
                    "text"=>"Debe seleccionar por lo menos un producto",
                    "type"=>"error"
                ]
            ];
        }

        return ['error'=>false];
    }


    /* ===========================
     * CONFIG: ISV EN PROFORMA
     * =========================== */
    protected function proformaAplicaISV() {
        /*
         * Regla final:
         * config.accion = 'Activar ISV Proforma' / config_id = 6
         * activar = 1 => la proforma calcula ISV según producto.
         * activar = 2/no existe/error => la proforma fuerza ISV 0.
         *
         * Cache local para no consultar la tabla config por cada línea de producto.
         */
        static $cacheAplica = null;

        if($cacheAplica !== null){
            return $cacheAplica;
        }

        $cacheAplica = false;

        try {
            $cn = mainModel::connection();

            if(!$cn){
                return false;
            }

            $accion = 'Activar ISV Proforma';

            $stmt = $cn->prepare("
                SELECT activar
                FROM config
                WHERE TRIM(accion) = ?
                   OR config_id = 6
                ORDER BY CASE WHEN TRIM(accion) = ? THEN 0 ELSE 1 END
                LIMIT 1
            ");

            if(!$stmt){
                return false;
            }

            $stmt->bind_param("ss", $accion, $accion);
            $stmt->execute();

            $result = $stmt->get_result();

            if($result && $result->num_rows > 0){
                $row = $result->fetch_assoc();
                $cacheAplica = ((int)$row['activar'] === 1);
            }

            $stmt->close();
        } catch (Throwable $e) {
            error_log("Error al consultar config Activar ISV Proforma: ".$e->getMessage());
            $cacheAplica = false;
        }

        return $cacheAplica;
    }

    /* ===========================
     * CONFIG GENERAL FACTURACIÓN
     * =========================== */
    protected function configFacturaActiva($accion, $config_id = 0, $default = false) {
        static $cacheConfig = [];

        $accion = trim((string)$accion);
        $config_id = (int)$config_id;
        $cacheKey = $accion.'|'.$config_id;

        if(isset($cacheConfig[$cacheKey])){
            return $cacheConfig[$cacheKey];
        }

        $activo = (bool)$default;

        try {
            $cn = mainModel::connection();

            if(!$cn){
                $cacheConfig[$cacheKey] = $activo;
                return $activo;
            }

            if($config_id > 0){
                $stmt = $cn->prepare("SELECT activar FROM config WHERE TRIM(accion) = ? OR config_id = ? ORDER BY CASE WHEN TRIM(accion) = ? THEN 0 ELSE 1 END LIMIT 1");

                if(!$stmt){
                    $cacheConfig[$cacheKey] = $activo;
                    return $activo;
                }

                $stmt->bind_param("sis", $accion, $config_id, $accion);
            }else{
                $stmt = $cn->prepare("SELECT activar FROM config WHERE TRIM(accion) = ? LIMIT 1");

                if(!$stmt){
                    $cacheConfig[$cacheKey] = $activo;
                    return $activo;
                }

                $stmt->bind_param("s", $accion);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if($result && $result->num_rows > 0){
                $row = $result->fetch_assoc();
                $activo = ((int)$row['activar'] === 1);
            }

            $stmt->close();
        } catch (Throwable $e) {
            error_log("Error al consultar config {$accion}: ".$e->getMessage());
            $activo = (bool)$default;
        }

        $cacheConfig[$cacheKey] = $activo;
        return $activo;
    }

    /* ===========================
     * CONFIG: REBAJAR INVENTARIO EN PROFORMA
     * =========================== */
    protected function proformaRebajaInventario() {
        /*
         * Regla final del servidor:
         * - Factura normal (documento_id = 1): siempre rebaja inventario.
         * - Proforma (documento_id = 4): rebaja inventario SOLO si
         *   config_id = 4 / 'Activar Rebajar Inventario Proforma' tiene activar = 1.
         *
         * No dependemos del checkbox recibido por POST, porque el origen real
         * de la regla es la tabla config.
         */
        return $this->configFacturaActiva('Activar Rebajar Inventario Proforma', 4, false);
    }

    /* ===========================
     * REGISTRO AUXILIAR DE PROFORMA
     * =========================== */
    protected function guardarFacturaProformaRelacion($facturas_id, $clientes_id, $secuencia_facturacion_id, $numero, $importe, $usuario, $empresa_id, $fecha_creacion) {
        $datosProforma = [
            'facturas_id'              => (int)$facturas_id,
            'clientes_id'              => (int)$clientes_id,
            'secuencia_facturacion_id' => (int)$secuencia_facturacion_id,
            'numero'                   => (int)$numero,
            'importe'                  => number_format((float)$importe, 2, '.', ''),
            'usuario'                  => (int)$usuario,
            'empresa_id'               => (int)$empresa_id,
            'estado'                   => 0,
            'fecha_creacion'           => $fecha_creacion
        ];

        return facturasModelo::agregar_facturas_proforma_modelo($datosProforma);
    }

    /* ===========================
     * PORCENTAJE ISV DESDE BD
     * =========================== */
    protected function obtenerPorcentajeISVDesdeBD($isv_id, $default = 0) {
        static $cachePorcentaje = [];

        $isv_id = (int)$isv_id;

        if($isv_id <= 0){
            return (float)$default;
        }

        if(isset($cachePorcentaje[$isv_id])){
            return (float)$cachePorcentaje[$isv_id];
        }

        $valor = (float)$default;

        try {
            $cn = mainModel::connection();

            if(!$cn){
                $cachePorcentaje[$isv_id] = $valor;
                return $valor;
            }

            $stmt = $cn->prepare("SELECT valor FROM isv WHERE isv_id = ? AND activar = 1 LIMIT 1");

            if(!$stmt){
                $cachePorcentaje[$isv_id] = $valor;
                return $valor;
            }

            $stmt->bind_param("i", $isv_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result && $result->num_rows > 0){
                $row = $result->fetch_assoc();
                $valor = (float)$row['valor'];
            }

            $stmt->close();
        } catch (Throwable $e) {
            error_log("Error al consultar porcentaje ISV {$isv_id}: ".$e->getMessage());
            $valor = (float)$default;
        }

        $cachePorcentaje[$isv_id] = $valor;
        return $valor;
    }

    /* ===========================
     * DETALLE DE FACTURA
     * =========================== */
    protected function procesarDetalleFactura($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id, $bajarInventario = true, $tipo_documento = "0") {
        $total_valor  = 0.0;
        $descuentos   = 0.0;
        $isv15_total  = 0.0;
        $isv18_total  = 0.0;

        /*
         * Factura normal:
         *   Calcula ISV según la configuración del producto.
         *
         * Proforma:
         *   Si config 'Activar ISV Proforma' = 1, calcula ISV según producto.
         *   Si config 'Activar ISV Proforma' = 2/no existe, fuerza ISV 0.
         */
        $aplicarISVDocumento = true;

        if($tipo_documento === "1"){
            $aplicarISVDocumento = $this->proformaAplicaISV();
        }

        for ($i = 0; $i < count($_POST['productName']); $i++) {
            if (
                empty($_POST['productos_id'][$i]) ||
                empty($_POST['productName'][$i]) ||
                $_POST['quantity'][$i] === '' ||
                $_POST['price'][$i] === ''
            ) {
                continue;
            }

            $p = $this->procesarProducto(
                $facturas_id,
                $clientes_id,
                $fecha,
                $fecha_registro,
                $empresa_id,
                $i,
                $bajarInventario,
                $aplicarISVDocumento
            );

            $total_valor += $p['subtotal'];
            $descuentos  += $p['descuento'];
            $isv15_total += $p['isv_valor'];
            $isv18_total += $p['isv_valor1'];
        }

        return [
            'total_valor'       => $total_valor,
            'descuentos'        => $descuentos,
            'isv15_total'       => $isv15_total,
            'isv18_total'       => $isv18_total,
            'total_despues_isv' => ($total_valor - $descuentos) + $isv15_total + $isv18_total,
        ];
    }

    /* ===========================
     * CÁLCULO DE ISV EN SERVIDOR
     * =========================== */
    protected function calcularISVLineaProductoDesdeBD($productos_id, $price, $quantity, $discount, $empresa_id, $isv1Actual = 0, $isv2Actual = 0){
        $cn = mainModel::connection();

        if(!$cn){
            return [
                'isv_valor' => (float)$isv1Actual,
                'isv_valor1' => (float)$isv2Actual
            ];
        }

        $productos_id = (int)$productos_id;
        $empresa_id = (int)$empresa_id;
        $price = (float)$price;
        $quantity = (float)$quantity;
        $discount = (float)$discount;

        if($productos_id <= 0 || $price <= 0 || $quantity <= 0){
            return [
                'isv_valor' => 0,
                'isv_valor1' => 0
            ];
        }

        $sql = "SELECT productos_id, isv_venta, isv1, isv2
                FROM productos
                WHERE productos_id = ?
                  AND empresa_id = ?
                LIMIT 1";

        $stmt = $cn->prepare($sql);
        $stmt->bind_param("ii", $productos_id, $empresa_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if(!$producto){
            return [
                'isv_valor' => (float)$isv1Actual,
                'isv_valor1' => (float)$isv2Actual
            ];
        }

        $isv_venta = (int)($producto['isv_venta'] ?? 0);
        $isv1 = (int)($producto['isv1'] ?? 0);
        $isv2 = (int)($producto['isv2'] ?? 0);

        if($isv_venta !== 1){
            return [
                'isv_valor' => 0,
                'isv_valor1' => 0
            ];
        }

        $base = ($price * $quantity) - $discount;

        if($base < 0){
            $base = 0;
        }

        $valorISV1 = 0;
        $valorISV2 = 0;

        $porcentajeISV1 = $this->obtenerPorcentajeISVDesdeBD(1, 15);
        $porcentajeISV2 = $this->obtenerPorcentajeISVDesdeBD(2, 18);

        // Prioridad: si el producto tiene ISV2, se calcula en isv_valor1.
        // Si grava pero no trae isv1/isv2, se usa ISV id=1 por defecto.
        if($isv2 === 1){
            $valorISV2 = round($base * ($porcentajeISV2 / 100), 2);
        }else if($isv1 === 1 || ($isv1 === 0 && $isv2 === 0)){
            $valorISV1 = round($base * ($porcentajeISV1 / 100), 2);
        }

        return [
            'isv_valor' => $valorISV1,
            'isv_valor1' => $valorISV2
        ];
    }

    /* ===========================
    * OBTENER COSTO DEL PRODUCTO
    * =========================== */
    protected function obtenerCostoProducto($productos_id, $empresa_id){
        $cn = mainModel::connection();

        $productos_id = (int)$productos_id;
        $empresa_id = (int)$empresa_id;

        $sql = "SELECT precio_compra
                FROM productos
                WHERE productos_id = ?
                AND empresa_id = ?
                LIMIT 1";

        $stmt = $cn->prepare($sql);
        $stmt->bind_param("ii", $productos_id, $empresa_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result && $result->num_rows > 0){
            $row = $result->fetch_assoc();
            $stmt->close();

            return number_format((float)$row['precio_compra'], 4, '.', '');
        }

        $stmt->close();

        return number_format(0, 4, '.', '');
    }

    /* ===========================
     * PRODUCTO
     * =========================== */
    protected function procesarProducto($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id, $index, $bajarInventario = true, $aplicarISVDocumento = true) {
        $isv_1 = number_format((float)($_POST['valor_isv'][$index] ?? 0), 4, '.', '');
        $isv_2 = number_format((float)($_POST['valor_isv1'][$index] ?? 0), 4, '.', '');

        $discount       = number_format((float)($_POST['discount'][$index] ?? 0), 4, '.', '');
        $productos_id   = $_POST['productos_id'][$index];
        $quantity       = (float)$_POST['quantity'][$index];
        $price          = number_format((float)$_POST['price'][$index], 4, '.', '');
        $medida         = $_POST['medida'][$index] ?? 'Und';
        $bodega         = $_POST['bodega'][$index] ?? 0;
        $referenciaProd = $_POST['referenciaProducto'][$index] ?? '';
        $price_anterior = number_format((float)($_POST['precio_real'][$index] ?? 0), 4, '.', '');

        /*
         * ISV por documento:
         * - Factura normal: siempre calcula según productos.
         * - Proforma: calcula solo si config 'Activar ISV Proforma' = 1.
         */
        if($aplicarISVDocumento === true){
            // Recalcular ISV en servidor para evitar errores cuando la cantidad es mayor a 1.
            // Respeta isv1/isv2 del producto y calcula sobre: (precio * cantidad) - descuento.
            $isvCalculado = $this->calcularISVLineaProductoDesdeBD(
                $productos_id,
                $price,
                $quantity,
                $discount,
                $empresa_id,
                $isv_1,
                $isv_2
            );

            $isv_1 = number_format((float)$isvCalculado['isv_valor'], 4, '.', '');
            $isv_2 = number_format((float)$isvCalculado['isv_valor1'], 4, '.', '');
        }else{
            $isv_1 = number_format(0, 4, '.', '');
            $isv_2 = number_format(0, 4, '.', '');
        }

        $costo_unitario = $this->obtenerCostoProducto(
            $productos_id,
            $empresa_id
        );
        
        $this->guardarDetalleFactura(
            $facturas_id,
            $productos_id,
            $quantity,
            $price,
            $costo_unitario,
            $isv_1,
            $isv_2,
            $discount,
            $medida
        );

        if ($bajarInventario === true) {
            $this->procesarInventario(
                $facturas_id,
                $clientes_id,
                $productos_id,
                $quantity,
                $bodega,
                $empresa_id,
                $medida
            );
        }

        if ($referenciaProd !== "") {
            $this->registrarCambioPrecio(
                $facturas_id,
                $productos_id,
                $clientes_id,
                $fecha,
                $referenciaProd,
                $price_anterior,
                $price,
                $fecha_registro
            );
        }

        return [
            'subtotal'   => (float)$price * (float)$quantity,
            'descuento'  => (float)$discount,
            'isv_valor'  => (float)$isv_1,
            'isv_valor1' => (float)$isv_2,
        ];
    }

    /* ===========================
     * GUARDA DETALLE
     * =========================== */
    protected function guardarDetalleFactura($facturas_id, $productos_id, $quantity, $price, $costo_unitario, $isv_valor, $isv_valor1, $discount, $medida){
        $datos = [
            "facturas_id"  => $facturas_id,
            "productos_id" => $productos_id,
            "cantidad"     => $quantity,
            "precio"       => $price,
            "isv_valor"    => $isv_valor,
            "isv_valor1"   => $isv_valor1,
            "descuento"    => $discount,
            "medida"       => $medida,
            "costo_unitario" => $costo_unitario,
        ];

        $result = facturasModelo::validDetalleFactura($facturas_id,$productos_id);

        if ($result->num_rows > 0) {
            facturasModelo::actualizar_detalle_facturas($datos);
        } else {
            facturasModelo::agregar_detalle_facturas_modelo($datos);
        }
    }

    /* ===========================
     * INVENTARIO
     * =========================== */
    protected function productoTieneMovimientoInventario($productos_id, $empresa_id, $bodega = 0){
        $cn = mainModel::connection();

        if(!$cn){
            return false;
        }

        $productos_id = (int)$productos_id;
        $empresa_id = (int)$empresa_id;
        $bodega = (int)$bodega;

        if($productos_id <= 0 || $empresa_id <= 0){
            return false;
        }

        if($bodega > 0){
            $sql = "SELECT movimientos_id
                    FROM movimientos
                    WHERE productos_id = ?
                      AND empresa_id = ?
                      AND almacen_id = ?
                    ORDER BY movimientos_id DESC
                    LIMIT 1";

            $stmt = $cn->prepare($sql);
            $stmt->bind_param("iii", $productos_id, $empresa_id, $bodega);
        }else{
            $sql = "SELECT movimientos_id
                    FROM movimientos
                    WHERE productos_id = ?
                      AND empresa_id = ?
                    ORDER BY movimientos_id DESC
                    LIMIT 1";

            $stmt = $cn->prepare($sql);
            $stmt->bind_param("ii", $productos_id, $empresa_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $existe = ($result && $result->num_rows > 0);
        $stmt->close();

        return $existe;
    }

    protected function procesarInventario($facturas_id,$clientes_id,$productos_id,$quantity,$bodega,$empresa_id,$medida){
        $tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);

        if($tipo_producto->num_rows > 0){
            $consulta = $tipo_producto->fetch_assoc();
            $tipo = strtolower(trim($consulta["tipo_producto"] ?? ''));

            // Solo se rebaja inventario para Producto o Insumo.
            // Servicio no debe generar movimiento.
            if(!in_array($tipo, ['producto', 'insumo'], true)){
                return;
            }

            // Si no tiene registro en movimientos, no se intenta rebajar.
            // Esto evita afectar servicios o registros sin inventario inicial.
            if(!$this->productoTieneMovimientoInventario($productos_id, $empresa_id, $bodega)){
                return;
            }

            $this->registrarSalidaInventario(
                $facturas_id,
                $productos_id,
                $clientes_id,
                $quantity,
                $bodega,
                $empresa_id,
                $medida
            );
        }
    }

    protected function registrarSalidaInventario($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida){
        $doc = "Factura ".$facturas_id;

        $datos = [
            "productos_id" => $productos_id,
            "empresa"     => $empresa_id,
            "clientes_id" => $clientes_id ?: 0,
            "comentario"  => "Salida de inventario por venta",
            "almacen_id"  => $bodega ?: 0,
            "cantidad"    => $quantity,
            "empresa_id"  => $empresa_id,
            "documento"   => $doc
        ];

        $salidaInventario = facturasModelo::registrar_salida_lote_modelo($datos);

        if (is_array($salidaInventario) && isset($salidaInventario['status']) && $salidaInventario['status'] === 'error') {
            throw new Exception($salidaInventario['message'] ?? 'No se pudo rebajar el inventario del producto.');
        }

        $this->procesarRelacionProductos(
            $facturas_id,
            $productos_id,
            $clientes_id,
            $quantity,
            $bodega,
            $empresa_id,
            $medida
        );
    }

    protected function debeProcesarProductosRelacionados($medidaName){
        $medidaName = strtolower(trim((string)$medidaName));

        // En venta normal por unidad no se deben rebajar productos padre/hijo.
        // La relación solo se procesa cuando la medida realmente requiere conversión.
        return in_array($medidaName, [
            'ton', 'tons', 'tonelada', 'toneladas',
            'lbs', 'lb', 'libra', 'libras'
        ], true);
    }

    protected function procesarRelacionProductos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida){
        $medidaName = strtolower(trim((string)$medida));

        // Evita el error donde un producto con saldo disponible queda bloqueado
        // por un producto relacionado sin inventario (ejemplo: venta en Und).
        if(!$this->debeProcesarProductosRelacionados($medidaName)){
            return;
        }

        $productoData = facturasModelo::cantidad_producto_modelo($productos_id);

        if(!$productoData || $productoData->num_rows <= 0){
            return;
        }

        $producto = $productoData->fetch_assoc();
        $producto_padre_id = isset($producto['id_producto_superior']) ? (int)$producto['id_producto_superior'] : 0;

        if($producto_padre_id === 0) {
            $this->procesarHijos(
                $facturas_id,
                $productos_id,
                $clientes_id,
                $quantity,
                $bodega,
                $empresa_id,
                $medidaName
            );
        } else {
            $this->procesarPadre(
                $facturas_id,
                $productos_id,
                $clientes_id,
                $quantity,
                $bodega,
                $empresa_id,
                $medidaName
            );
        }
    }

    protected function procesarHijos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName){
        $result = facturasModelo::total_hijos_segun_padre_modelo($productos_id);

        if($result->num_rows > 0){
            $valor = 0;

            while($c = $result->fetch_assoc()){
                $producto_hijo = (int)$c['productos_id'];
                $cantidad = $this->convertirMedida($quantity,$medidaName,true);

                $this->registrarSalidaHijo(
                    $facturas_id,
                    $producto_hijo,
                    $clientes_id,
                    $cantidad,
                    $bodega,
                    $empresa_id,
                    $valor
                );

                $valor++;
            }
        }
    }

    protected function procesarPadre($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName){
        $result = facturasModelo::cantidad_producto_modelo($productos_id);

        if($result->num_rows > 0){
            $valor = 0;

            while($c = $result->fetch_assoc()){
                $producto_padre = (int)$c['id_producto_superior'];
                $cantidad = $this->convertirMedida($quantity,$medidaName,false);

                $this->registrarSalidaPadre(
                    $facturas_id,
                    $producto_padre,
                    $clientes_id,
                    $cantidad,
                    $bodega,
                    $empresa_id,
                    $valor
                );

                $valor++;
            }
        }
    }

    protected function convertirMedida($quantity,$medidaName,$esPadre){
        if($medidaName === "ton") return $quantity * 2204.623;
        if($medidaName === "lbs") return $quantity / 2204.623;

        return $quantity;
    }

    protected function registrarSalidaHijo($facturas_id,$producto_id,$clientes_id,$cantidad,$bodega,$empresa_id,$valor){
        $datos = [
            "productos_id" => $producto_id,
            "empresa"     => $empresa_id,
            "clientes_id" => $clientes_id ?: 0,
            "comentario"  => "Salida de inventario por venta",
            "almacen_id"  => $bodega ?: 0,
            "cantidad"    => $cantidad,
            "empresa_id"  => $empresa_id,
            "documento"   => "Factura ".$facturas_id."_".$valor
        ];

        $salidaInventario = facturasModelo::registrar_salida_lote_modelo($datos);

        if (is_array($salidaInventario) && isset($salidaInventario['status']) && $salidaInventario['status'] === 'error') {
            throw new Exception($salidaInventario['message'] ?? 'No se pudo rebajar el inventario del producto relacionado.');
        }
    }

    protected function registrarSalidaPadre($facturas_id,$producto_id,$clientes_id,$cantidad,$bodega,$empresa_id,$valor){
        $this->registrarSalidaHijo(
            $facturas_id,
            $producto_id,
            $clientes_id,
            $cantidad,
            $bodega,
            $empresa_id,
            $valor
        );
    }

    /* ===========================
     * PRECIOS
     * =========================== */
    protected function registrarCambioPrecio($facturas_id,$productos_id,$clientes_id,$fecha,$referencia,$precio_anterior,$precio_nuevo,$fecha_registro){
        $datos = [
            "facturas_id"      => $facturas_id,
            "productos_id"     => $productos_id,
            "clientes_id"      => $clientes_id,
            "fecha"            => $fecha,
            "referencia"       => $referencia,
            "precio_anterior"  => $precio_anterior,
            "precio_nuevo"     => $precio_nuevo,
            "fecha_registro"   => $fecha_registro
        ];

        $res = facturasModelo::valid_precio_factura_modelo($datos);

        if($res->num_rows == 0) {
            facturasModelo::agregar_precio_factura_clientes($datos);
        }
    }

    /* ===========================
     * CXC
     * =========================== */
    protected function guardarCuentaPorCobrar($clientes_id,$facturas_id,$fecha,$total,$estado,$tipo_factura,$usuario,$fecha_registro,$empresa_id){
        $datos = [
            "clientes_id"    => $clientes_id,
            "facturas_id"    => $facturas_id,
            "fecha"          => $fecha,
            "saldo"          => $total,
            "estado"         => $estado,
            "usuario"        => $usuario,
            "fecha_registro" => $fecha_registro,
            "empresa"        => $empresa_id,
            "tipo_factura"   => $tipo_factura
        ];

        $val = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

        if($val->num_rows == 0){
            $ok = facturasModelo::agregar_cuenta_por_cobrar_clientes($datos);

            if(!$ok){
                error_log("Error CxC factura: ".$facturas_id);
                return false;
            }
        }

        return true;
    }

    /* ===========================
     * HISTORIAL
     * =========================== */
    protected function guardarHistorialFactura($modulo,$status,$observacion){
        $datos = [
            "modulo"           => $modulo,
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status"           => $status,
            "observacion"      => $observacion,
            "fecha_registro"   => date("Y-m-d H:i:s")
        ];

        mainModel::guardarHistorial($datos);
    }

    /* ===========================
     * PAGOS MÚLTIPLES
     * =========================== */
    protected function procesarPagosMultiples($facturas_id,$total_pagado){
        $saldo_cero = facturasModelo::verificar_saldo_cero($facturas_id);

        if($saldo_cero){
            facturasModelo::actualizar_estado_pago_completo($facturas_id);
            return "printBill(".$facturas_id.");";
        }

        return "";
    }

    /* ===========================
    * CADENA JS POST-GUARDADO CENTRALIZADA
    * =========================== */
    protected function armarFuncionesPostGuardado($facturas_id, $tipo_documento, $tipo_factura, $total_factura = 0, $cliente_nombre = "", $funcion_pagos = ""){
        $base = "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
        $resetTipo = "setTipoFactura(\"contado\");";

        $total_factura = number_format((float)$total_factura, 2, '.', '');
        $cliente_nombre_js = json_encode($cliente_nombre, JSON_UNESCAPED_UNICODE);

        /*
        * CONFIG:
        * accion  = Activar Cobro Proforma
        * activar = 1 Sí / 2 No
        */
        $cobrarProforma = false;

        try {
            $cn = mainModel::connection();

            $sqlConfig = "SELECT activar
                        FROM config
                        WHERE accion = 'Activar Cobro Proforma'
                        LIMIT 1";

            $rsConfig = $cn->query($sqlConfig);

            if ($rsConfig && $rsConfig->num_rows > 0) {
                $rowConfig = $rsConfig->fetch_assoc();
                $cobrarProforma = ((int)$rowConfig['activar'] === 1);
            }
        } catch (Exception $e) {
            error_log("Error al validar config Activar Cobro Proforma: ".$e->getMessage());
            $cobrarProforma = false;
        }

        /*
        * PROFORMA:
        * - Contado + config activo: abre modal de pago.
        * - Crédito: imprime proforma.
        * - Config apagado: imprime proforma.
        */
        if($tipo_documento === "1"){
            if((int)$tipo_factura === 1 && $cobrarProforma === true){
                return $base."pago({$facturas_id}, 1, 'facturacion', {$total_factura}, {$cliente_nombre_js});".$resetTipo;
            }

            return $base."printBill({$facturas_id});".$resetTipo;
        }

        /*
        * FACTURA NORMAL CONTADO:
        * Abre modal de pago normal.
        */
        if((int)$tipo_factura === 1){
            return $base."pago({$facturas_id}, 1, 'facturacion', {$total_factura}, {$cliente_nombre_js});getTotalFacturasDisponibles();".$funcion_pagos.$resetTipo;
        }

        /*
        * FACTURA NORMAL CRÉDITO:
        * Solo imprime.
        */
        return $base."printBill({$facturas_id});".$funcion_pagos.$resetTipo;
    }

    /* ===========================
     * AGREGAR FACTURAS
     * =========================== */
    public function agregar_facturas_controlador() {
        try {
            // 0) Sesión
            $validacion = mainModel::validarSesion();

            if ($validacion['error']) {
                return mainModel::showNotification([
                    "title"   => "Error de sesión",
                    "text"    => $validacion['mensaje'],
                    "type"    => "error",
                    "funcion" => "window.location.href = '".$validacion['redireccion']."'"
                ]);
            }

            // 1) Límite del plan
            $mainModel = new mainModel();
            $planConfig = $mainModel->getPlanConfiguracionMainModel();

            if (isset($planConfig['facturas'])) {
                $limite = (int)$planConfig['facturas'];

                if ($limite === 0) {
                        return mainModel::showNotification([
                        "type"  => "error",
                        "title" => "Acceso restringido",
                        "text"  => "Su plan no incluye la creación de facturas."
                    ]);
                }

                $totalReg = (int)facturasModelo::getTotalFacturasRegistradas();

                if ($totalReg >= $limite) {
                        return mainModel::showNotification([
                        "type"  => "error",
                        "title" => "Límite alcanzado",
                        "text"  => "Ha excedido el límite mensual de facturas (Máximo: $limite)."
                    ]);
                }
            }

            // 2) Normalización UI: 1=contado, 0=crédito -> backend: 1/2
            $tipo_factura_input = isset($_POST['facturas_activo']) ? intval($_POST['facturas_activo']) : 1;
            $tipo_factura = ($tipo_factura_input === 1) ? 1 : 2;

            // Proforma
            $tipo_documento_input = isset($_POST['facturas_proforma']) ? intval($_POST['facturas_proforma']) : 0;
            $tipo_documento = ($tipo_documento_input === 1) ? "1" : "0";

            // Inventario:
            // Factura normal/credito: baja siempre.
            // Proforma: baja SOLO si config_id=4 / Activar Rebajar Inventario Proforma = 1.
            $bajarInventario = true;

            if ($tipo_documento === "1") {
                $bajarInventario = $this->proformaRebajaInventario();
            }

            // 3) Datos base
            $datosBasicos = $this->prepararDatosFactura($tipo_factura,$tipo_documento);
            $estado_final = $datosBasicos['estado'];

            // 4) Validación
            $valid = $this->validarDatosFormulario();

            if($valid['error']){
                return mainModel::showNotification($valid['notification']);
            }

            // 5) Datos comunes
            $clientes_id    = $_POST['cliente_id'];
            $colaborador_id = $_POST['colaborador_id'];
            $notas          = mainModel::cleanString($_POST['notesBill']);
            $fecha          = $_POST['fecha'];
            $fecha_dolar    = $_POST['fecha_dolar'];
            $fecha_registro = date("Y-m-d H:i:s");

            // Exoneración
            $exoneracion_orden         = $_POST['exoneracion_orden'] ?? null;
            $exoneracion_constancia    = $_POST['exoneracion_constancia'] ?? null;
            $exoneracion_sag           = $_POST['exoneracion_sag'] ?? null;
            $exoneracion_orden_interno = $_POST['exoneracion_orden_interno'] ?? null;

            // 6) ¿Viene desde borrador?
            $facturas_id = empty($_POST['facturas_id']) ? null : $_POST['facturas_id'];
            $esBorradorPrevio = false;
            $numeroFactura = null;

            if ($facturas_id) {
                $rowF = mainModel::consultar_tabla(
                    'facturas',
                    ['estado','number','secuencia_facturacion_id'],
                    "facturas_id = {$facturas_id}"
                );

                if (!empty($rowF)) {
                    $estado_actual = (int)$rowF[0]['estado'];
                    $esBorradorPrevio = ($estado_actual === 1);
                }
            } else {
                $facturas_id = mainModel::correlativo("facturas_id","facturas");
            }

            // 7) Apertura de caja
            $apertura = facturasModelo::getAperturaIDModelo([
                "colaboradores_id" => $datosBasicos['usuario'],
                "fecha"            => $fecha,
                "estado"           => 1
            ])->fetch_assoc();

            $apertura_id = $apertura['apertura_id'];

            // 8) Tomar número
            $empresa_id   = $_SESSION['empresa_id_sd'];
            $documento_id = ($tipo_documento === "1") ? "4" : "1";

            $numeroFactura = $this->obtenerNumeroFactura(
                $empresa_id,
                $documento_id
            );

            if($numeroFactura['error']){
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => $numeroFactura['mensaje'],
                    "type"  => "error"
                ]);
            }

            // 9) Guardar encabezado con importe provisional
            $datosFactura = [
                "facturas_id"               => $facturas_id,
                "clientes_id"               => $clientes_id,
                "secuencia_facturacion_id"  => $numeroFactura['data']['secuencia_facturacion_id'],
                "apertura_id"               => $apertura_id,
                "tipo_factura"              => $tipo_factura,
                "numero"                    => $numeroFactura['data']['numero'],
                "colaboradores_id"          => $colaborador_id,
                "importe"                   => 0,
                "notas"                     => $notas,
                "fecha"                     => $fecha,
                "estado"                    => $estado_final,
                "usuario"                   => $datosBasicos['usuario'],
                "fecha_registro"            => $fecha_registro,
                "empresa"                   => $datosBasicos['empresa_id'],
                "fecha_dolar"               => $fecha_dolar,
                "exoneracion_orden"         => $exoneracion_orden,
                "exoneracion_constancia"    => $exoneracion_constancia,
                "exoneracion_sag"           => $exoneracion_sag,
                "exoneracion_orden_interno" => $exoneracion_orden_interno
            ];

            $okHead = facturasModelo::guardar_facturas_modelo($datosFactura);

            if(!$okHead){
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "No hemos podido procesar su solicitud",
                    "type"  => "error"
                ]);
            }

            // 10) Detalle + totales + inventario según corresponda
            $totales = $this->procesarDetalleFactura(
                $facturas_id,
                $clientes_id,
                $fecha,
                $fecha_registro,
                $datosBasicos['empresa_id'],
                $bajarInventario,
                $tipo_documento
            );

            // 11) Actualizar importe real
            $okImporte = facturasModelo::actualizar_factura_importe([
                "facturas_id" => $facturas_id,
                "importe"     => $totales['total_despues_isv']
            ]);

            if(!$okImporte){
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "Error al actualizar el importe de la factura",
                    "type"  => "error"
                ]);
            }

            // 12) Si el documento es Proforma, registrar/actualizar también en facturas_proforma
            if ($tipo_documento === "1") {
                $okProforma = $this->guardarFacturaProformaRelacion(
                    $facturas_id,
                    $clientes_id,
                    $numeroFactura['data']['secuencia_facturacion_id'],
                    $numeroFactura['data']['numero'],
                    $totales['total_despues_isv'],
                    $datosBasicos['usuario'],
                    $datosBasicos['empresa_id'],
                    $fecha_registro
                );

                if(!$okProforma){
                        return mainModel::showNotification([
                        "title" => "Error",
                        "text"  => "Error al registrar la proforma en facturas_proforma",
                        "type"  => "error"
                    ]);
                }
            }

            // 13) CxC
            $estado_cuenta = 1;

            $okCxC = $this->guardarCuentaPorCobrar(
                $clientes_id,
                $facturas_id,
                $fecha,
                $totales['total_despues_isv'],
                $estado_cuenta,
                $tipo_factura,
                $datosBasicos['usuario'],
                $fecha_registro,
                $datosBasicos['empresa_id']
            );

            if(!$okCxC){
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "Error al registrar la cuenta por cobrar",
                    "type"  => "error"
                ]);
            }

            // 13) Historial
            $cliente = mainModel::consultar_tabla(
                'clientes',
                ['nombre','rtn'],
                "clientes_id = {$clientes_id}"
            )[0];

            $tipoTxt = ($tipo_factura == 1) ? 'contado' : 'crédito';

            if ($tipo_documento === "1") {
                $tipoTxt = $bajarInventario ? 'proforma con rebaja de inventario' : 'proforma sin rebaja de inventario';
            }

            $this->guardarHistorialFactura(
                'Facturas',
                'Registro',
                "Se registró la factura al {$tipoTxt} para el cliente {$cliente['nombre']} con el RTN {$cliente['rtn']}"
            );

            // 14) Pagos múltiples
            $funcion_pagos = "";

            if(isset($_POST['total_pagado'])) {
                $funcion_pagos = $this->procesarPagosMultiples($facturas_id, $_POST['total_pagado']);
            }

            // 15) Cadena JS de salida
            $cliente_nombre_pago = $cliente['nombre'];

            if (!empty($cliente['rtn'])) {
                $cliente_nombre_pago .= " - " . $cliente['rtn'];
            }

            $funcion = $this->armarFuncionesPostGuardado(
                $facturas_id,
                $tipo_documento,
                $tipo_factura,
                $totales['total_despues_isv'],
                $cliente_nombre_pago,
                $funcion_pagos
            );

            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Registro almacenado",
                "text"    => "El registro se ha almacenado correctamente",
                "form"    => "invoice-form",
                "funcion" => $funcion
            ]);

        } catch (Exception $e) {
            if (isset($numeroFactura['data']['numero'])) {
                $numero = $numeroFactura['data']['numero'];
                $conexion = mainModel::connection();
                $stmt = $conexion->prepare("INSERT IGNORE INTO secuencia_factura_fallida (empresa_id, documento_id, numero) VALUES (?, ?, ?)");
                $documento_id_fallido = isset($documento_id) ? (int)$documento_id : 1;
                $empresa_ctx = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
                $stmt->bind_param("iii", $empresa_ctx, $documento_id_fallido, $numero);
                $stmt->execute();
                $stmt->close();
            }

            error_log("Error en agregar_facturas_controlador: ".$e->getMessage());

            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "Ocurrió un error al procesar la factura: ".$e->getMessage(),
                "type"  => "error"
            ]);
        }
    }

    /* ===========================
     * AGREGAR FACTURAS (BORRADOR)
     * =========================== */
    public function agregar_facturas_open_controlador() {
        if(!isset($_SESSION['user_sd'])) session_start(['name'=>'SD']);

        // 1) Validar sesión
        $validacion = mainModel::validarSesion();

        if ($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        // 2) Normalización UI: 1=contado, 0=crédito -> backend: 1/2
        $tipo_factura_input = isset($_POST['facturas_activo']) ? (int)$_POST['facturas_activo'] : 1;
        $tipo_factura = ($tipo_factura_input === 1) ? 1 : 2;

        // Proforma en borrador
        $tipo_documento_input = isset($_POST['facturas_proforma']) ? (int)$_POST['facturas_proforma'] : 0;
        $tipo_documento = ($tipo_documento_input === 1) ? "1" : "0";

        // Inventario en borrador:
        // Una factura en borrador NO debe rebajar inventario.
        // El inventario se rebaja hasta que el documento se confirma/guarda como factura real.
        // Esto evita que productos queden descontados por ventas no confirmadas.
        $bajarInventario = false;

        // 3) Datos base
        $datosBasicos = $this->prepararDatosFactura($tipo_factura, $tipo_documento);
        $estado_borrador = 1;

        // 4) Validación mínima
        $valid = $this->validarDatosFormulario();

        if($valid['error']) {
            return mainModel::showNotification($valid['notification']);
        }

        // 5) Datos comunes
        $clientes_id    = $_POST['cliente_id'];
        $colaborador_id = $_POST['colaborador_id'];
        $notas          = mainModel::cleanString($_POST['notesBill']);
        $fecha          = $_POST['fecha'];
        $fecha_dolar    = $_POST['fecha_dolar'];
        $fecha_registro = date("Y-m-d H:i:s");

        // Exoneración
        $exoneracion_orden         = $_POST['exoneracion_orden'] ?? null;
        $exoneracion_constancia    = $_POST['exoneracion_constancia'] ?? null;
        $exoneracion_sag           = $_POST['exoneracion_sag'] ?? null;
        $exoneracion_orden_interno = $_POST['exoneracion_orden_interno'] ?? null;

        // correlativo y bandera de edición
        $facturas_id = empty($_POST['facturas_id']) ? mainModel::correlativo("facturas_id","facturas") : $_POST['facturas_id'];
        $Existe = !empty($_POST['facturas_id']);

        // 6) Transacción
        $cn = mainModel::connection();
        $cn->begin_transaction();

        try {
            // 6.1) Apertura de caja
            $apertura = facturasModelo::getAperturaIDModelo([
                "colaboradores_id" => $datosBasicos['usuario'],
                "fecha"            => $fecha,
                "estado"           => 1
            ])->fetch_assoc();

            $apertura_id = $apertura['apertura_id'];

            // 6.2) Obtener secuencia activa sin consumir número
            $documento_id = $datosBasicos['documento_id'];
            $sec_id = null;

            $stmtSec = $cn->prepare("
                SELECT secuencia_facturacion_id 
                FROM secuencia_facturacion 
                WHERE empresa_id = ? AND documento_id = ? AND activo = 1
                LIMIT 1
            ");

            $empresa_id = (int)$datosBasicos['empresa_id'];
            $doc_id_int = (int)$documento_id;

            $stmtSec->bind_param("ii", $empresa_id, $doc_id_int);
            $stmtSec->execute();
            $resSec = $stmtSec->get_result();

            if ($resSec->num_rows === 0) {
                $stmtSec->close();
                $cn->rollback();

                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "No hay secuencia de facturación activa para este documento.",
                    "type"  => "error"
                ]);
            }

            $sec_id = (int)$resSec->fetch_assoc()['secuencia_facturacion_id'];
            $stmtSec->close();

            // 6.3) Guardar encabezado
            $datosFactura = [
                "facturas_id"               => $facturas_id,
                "clientes_id"               => $clientes_id,
                "secuencia_facturacion_id"  => $sec_id,
                "apertura_id"               => $apertura_id,
                "tipo_factura"              => $tipo_factura,
                "numero"                    => 0,
                "colaboradores_id"          => $colaborador_id,
                "importe"                   => 0,
                "notas"                     => $notas,
                "fecha"                     => $fecha,
                "estado"                    => $estado_borrador,
                "usuario"                   => $datosBasicos['usuario'],
                "fecha_registro"            => $fecha_registro,
                "empresa"                   => $empresa_id,
                "fecha_dolar"               => $fecha_dolar,
                "exoneracion_orden"         => $exoneracion_orden,
                "exoneracion_constancia"    => $exoneracion_constancia,
                "exoneracion_sag"           => $exoneracion_sag,
                "exoneracion_orden_interno" => $exoneracion_orden_interno,
            ];

            facturasModelo::guardar_facturas_modelo($datosFactura);

            // 6.4) Detalle + totales
            // En borrador también se respeta la regla de ISV por proforma:
            // config Activar ISV Proforma = 1 calcula ISV; = 2 fuerza ISV 0.
            $totales = $this->procesarDetalleFactura(
                $facturas_id,
                $clientes_id,
                $fecha,
                $fecha_registro,
                $empresa_id,
                $bajarInventario,
                $tipo_documento
            );

            // 6.5) Actualizar importe
            $okImporte = facturasModelo::actualizar_factura_importe([
                "facturas_id" => $facturas_id,
                "importe"     => $totales['total_despues_isv']
            ]);

            if(!$okImporte){
                $cn->rollback();

                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "Error al actualizar el importe de la factura (borrador)",
                    "type"  => "error"
                ]);
            }

            // 6.6) Si el borrador es Proforma, registrar/actualizar también en facturas_proforma
            if ($tipo_documento === "1") {
                $okProforma = $this->guardarFacturaProformaRelacion(
                    $facturas_id,
                    $clientes_id,
                    $sec_id,
                    0,
                    $totales['total_despues_isv'],
                    $datosBasicos['usuario'],
                    $empresa_id,
                    $fecha_registro
                );

                if(!$okProforma){
                    $cn->rollback();

                    return mainModel::showNotification([
                        "title" => "Error",
                        "text"  => "Error al registrar la proforma en facturas_proforma (borrador)",
                        "type"  => "error"
                    ]);
                }
            }

            // 6.7) Borrador: NO CxC

            // 6.8) Commit
            $cn->commit();

            // 6.8) UI
            $tipo_txt = ($tipo_factura === 1 ? 'contado' : 'crédito');
            $funcion = "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";

            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Registro almacenado",
                "text"    => "Factura en borrador registrada al {$tipo_txt}",
                "form"    => "invoice-form",
                "funcion" => $funcion
            ]);

        } catch (Exception $e) {
            if(isset($cn)) $cn->rollback();

            error_log("Error en agregar_facturas_open_controlador: ".$e->getMessage());

            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "Ocurrió un error al procesar la factura en borrador: ".$e->getMessage(),
                "type"  => "error"
            ]);
        }
    }

    /* ===========================
     * CANCELAR FACTURA
     * =========================== */
    public function cancelar_facturas_controlador() {
        $facturas_id = $_POST['facturas_id'];

        $factura = mainModel::consultar_tabla(
            'facturas',
            ['number'],
            "facturas_id = {$facturas_id}"
        );

        $number = $factura[0]['number'] ?? null;

        $ok = facturasModelo::cancelar_facturas_modelo($facturas_id);

        if($ok){
            $this->guardarHistorialFactura(
                'Facturas',
                'Cancelar',
                "Se canceló la factura {$number}"
            );

            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Registro eliminado",
                "text"    => "El registro se ha eliminado correctamente",
                "funcion" => ""
            ]);
        }

        return mainModel::showNotification([
            "title" => "Error",
            "text"  => "No hemos podido procesar su solicitud",
            "type"  => "error"
        ]);
    }
}