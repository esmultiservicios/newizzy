<?php
    //llenarDataTableDetalleVentas.php
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";

    // Instanciar mainModel
    $insMainModel = new mainModel();

    // Validar sesión primero
    $validacion = $insMainModel->validarSesion();
    if($validacion['error']) {
        return $insMainModel->showNotification([
            "title" => "Error de sesión",
            "text" => $validacion['mensaje'],
            "type" => "error",
            "funcion" => "window.location.href = '".$validacion['redireccion']."'"
        ]);
    }

    /*
        FILTRO DE ESTADO DE FACTURA / PROFORMA
        -----------------------------------------------------------
        Regla:
        - Por defecto muestra activas.
        - Activas = estado IN (2,3)
            2 = Contado / Pagada
            3 = Crédito
        - Anuladas = estado 4

        Se aceptan varios nombres por compatibilidad:
        - categoria_factura
        - tipo_factura_reporte
        - estado_factura
        - factura_estado

        También se envía factura:
        - 1 = Electrónica
        - 4 = Proforma
    */

    $categoria_factura = 1;

    if (isset($_POST['categoria_factura']) && $_POST['categoria_factura'] !== '') {
        $categoria_factura = (int)$_POST['categoria_factura'];
    } elseif (isset($_POST['tipo_factura_reporte']) && $_POST['tipo_factura_reporte'] !== '') {
        $categoria_factura = (int)$_POST['tipo_factura_reporte'];
    } elseif (isset($_POST['estado_factura']) && $_POST['estado_factura'] !== '') {
        $categoria_factura = (int)$_POST['estado_factura'];
    } elseif (isset($_POST['factura_estado']) && $_POST['factura_estado'] !== '') {
        $categoria_factura = (int)$_POST['factura_estado'];
    }

    if ($categoria_factura === 2 || $categoria_factura === 4) {
        $estado_sql = "4";
        $estado_modo = "anuladas";
    } else {
        $estado_sql = "2,3";
        $estado_modo = "activas";
    }

    $factura = isset($_POST['factura']) && $_POST['factura'] !== '' ? (int)$_POST['factura'] : 1;

    if ($factura !== 4) {
        $factura = 1;
    }

    $datos = [
        "fechai" => isset($_POST['fechai']) ? $_POST['fechai'] : "",
        "fechaf" => isset($_POST['fechaf']) ? $_POST['fechaf'] : "",
        "productos_id" => isset($_POST['productos_id']) ? $_POST['productos_id'] : "",
        "colaboradores_id" => isset($_POST['colaboradores_id']) ? $_POST['colaboradores_id'] : "",
        "empresa_id_sd" => $_SESSION['empresa_id_sd'],

        // Filtros para que mainModel::GetDetalleVentas los use en el WHERE
        "factura" => $factura,
        "documento_id" => $factura,
        "categoria_factura" => $categoria_factura,
        "tipo_factura_reporte" => $categoria_factura,
        "estado_factura" => $categoria_factura,
        "estado_sql" => $estado_sql,
        "estado_modo" => $estado_modo
    ];

    $result = $insMainModel->GetDetalleVentas($datos);

    $data = array();

    if ($result) {
        while($row = $result->fetch_assoc()) {

            /*
                Filtro de seguridad adicional:
                Si GetDetalleVentas devuelve estado/Estado/factura_estado,
                también lo filtramos aquí.

                Si no devuelve estado, el filtro debe hacerse en el SQL de
                mainModel::GetDetalleVentas usando $datos['estado_sql'].
            */
            $estado_row = null;

            if (isset($row['estado'])) {
                $estado_row = (int)$row['estado'];
            } elseif (isset($row['Estado'])) {
                $estado_row = (int)$row['Estado'];
            } elseif (isset($row['factura_estado'])) {
                $estado_row = (int)$row['factura_estado'];
            }

            if ($estado_row !== null) {
                if ($estado_modo === "anuladas" && $estado_row !== 4) {
                    continue;
                }

                if ($estado_modo === "activas" && $estado_row === 4) {
                    continue;
                }
            }

            $documento_id_row = null;

            if (isset($row['documento_id'])) {
                $documento_id_row = (int)$row['documento_id'];
            } elseif (isset($row['Documento'])) {
                $documento_id_row = (int)$row['Documento'];
            } elseif (isset($row['tipo_documento_id'])) {
                $documento_id_row = (int)$row['tipo_documento_id'];
            }

            if ($documento_id_row !== null) {
                if ($factura === 4 && $documento_id_row !== 4) {
                    continue;
                }

                if ($factura === 1 && $documento_id_row === 4) {
                    continue;
                }
            }

            $data[] = array(
                "numero" => isset($row['numero']) ? $row['numero'] : "",
                "Producto" => isset($row['Producto']) ? $row['Producto'] : "",
                "Precio" => isset($row['Precio']) ? $row['Precio'] : 0,
                "Cantidad" => isset($row['Cantidad']) ? $row['Cantidad'] : 0,
                "ISV" => isset($row['ISV']) ? $row['ISV'] : 0,
                "Descuento" => isset($row['Descuento']) ? $row['Descuento'] : 0,
                "Total" => isset($row['Total']) ? $row['Total'] : 0,
                "Vendedor" => isset($row['Vendedor']) ? $row['Vendedor'] : "",
                "Cliente" => isset($row['Cliente']) ? $row['Cliente'] : "",
                "Fecha" => isset($row['Fecha']) ? $row['Fecha'] : "",
                "estado" => $estado_row,
                "documento_id" => $documento_id_row
            );
        }
    }

    $response = array(
        "echo" => 1,
        "totalrecords" => count($data),
        "totaldisplayrecords" => count($data),
        "data" => $data
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);