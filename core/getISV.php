<?php
// core/getISV.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=UTF-8');

$insMainModel = new mainModel();

$isv = 0;
$activar = 0;

try {
    /*
        NUEVA FORMA:
        Facturación y cotización mandan isv_id.

        isv_id = 1 => Facturas 15%
        isv_id = 2 => Facturas 18%
        isv_id = 3 => Compras 15%
        isv_id = 4 => Compras 18%

        El porcentaje NO se define en el JS.
        El porcentaje sale de la tabla isv.valor.
    */
    if (isset($_POST['isv_id']) && $_POST['isv_id'] !== '') {
        $isv_id = (int)$_POST['isv_id'];

        $conexion = $insMainModel->connection();

        $stmt = $conexion->prepare("
            SELECT valor, activar
            FROM isv
            WHERE isv_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception($conexion->error);
        }

        $stmt->bind_param("i", $isv_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $consulta = $result->fetch_assoc();

            $isv = isset($consulta['valor']) ? (float)$consulta['valor'] : 0;
            $activar = isset($consulta['activar']) ? (int)$consulta['activar'] : 0;
        }

        $stmt->close();

        echo json_encode([
            "success" => true,
            "valor" => $isv,
            "activar" => $activar,
            0 => $isv,
            1 => $activar
        ]);

        exit();
    }

    /*
        FORMA ANTERIOR:
        Se deja por compatibilidad si otro JS viejo todavía manda:
        documento = Facturas
        documento = Compras
    */
    if (isset($_POST['documento']) && $_POST['documento'] !== '') {
        $documento = $_POST['documento'];

        $result = $insMainModel->getISV($documento);

        if ($result && $result->num_rows > 0) {
            $consulta = $result->fetch_assoc();

            $isv = isset($consulta['valor']) ? (float)$consulta['valor'] : 0;
            $activar = isset($consulta['activar']) ? (int)$consulta['activar'] : 0;
        }

        echo json_encode([
            "success" => true,
            "valor" => $isv,
            "activar" => $activar,
            0 => $isv,
            1 => $activar
        ]);

        exit();
    }

    echo json_encode([
        "success" => false,
        "valor" => 0,
        "activar" => 0,
        "message" => "No se recibió isv_id ni documento"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "valor" => 0,
        "activar" => 0,
        "message" => $e->getMessage()
    ]);
}