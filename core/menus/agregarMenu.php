<?php
// core/menus/agregarMenu.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/MenuSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        MenuSyncHelper::respuesta(
            "error",
            "Sesión inválida",
            $validacion['mensaje'] ?? "Sesión inválida"
        );
    }
}

$tipo = isset($_POST["tipo"]) ? MenuSyncHelper::normalizarTipo($_POST["tipo"]) : "";
$nombre = isset($_POST["nombre"]) ? trim((string)$_POST["nombre"]) : "";
$descripcion = isset($_POST["descripcion"]) ? trim((string)$_POST["descripcion"]) : "";
$icono = isset($_POST["icono"]) ? trim((string)$_POST["icono"]) : "";
$orden = isset($_POST["orden"]) && is_numeric($_POST["orden"]) ? (int)$_POST["orden"] : 0;
$dependencia = isset($_POST["dependencia"]) && $_POST["dependencia"] !== "" ? (int)$_POST["dependencia"] : 0;
$visible = isset($_POST["visible"]) ? (int)$_POST["visible"] : 1;
$visible = ($visible === 1) ? 1 : 0;
$fechaRegistro = date("Y-m-d H:i:s");

if ($tipo === "") {
    MenuSyncHelper::respuesta("error", "Error", "Debe seleccionar un tipo de menú válido.");
}

if ($nombre === "") {
    MenuSyncHelper::respuesta("error", "Error", "Debe ingresar el nombre interno del menú.");
}

if ($descripcion === "") {
    MenuSyncHelper::respuesta("error", "Error", "Debe ingresar la descripción del menú.");
}

if ($tipo !== "menu" && $dependencia <= 0) {
    MenuSyncHelper::respuesta("error", "Error", "Debe seleccionar la dependencia del elemento.");
}

$config = MenuSyncHelper::obtenerConfig($tipo);

$conexionPrincipal = null;
$erroresClientes = [];
$nuevoId = 0;

try {
    $conexionPrincipal = MenuSyncHelper::conectarPrincipal($insMainModel);

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    MenuSyncHelper::validarEstructuraMenus($conexionPrincipal);

    if (!MenuSyncHelper::validarDependencia($conexionPrincipal, $config, $tipo, $dependencia)) {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Dependencia inválida", "La dependencia seleccionada no existe.");
    }

    if (MenuSyncHelper::existeDuplicado($conexionPrincipal, $tipo, 0, $nombre, $dependencia)) {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Advertencia", "Este registro ya existe en el sistema.");
    }

    $nuevoId = MenuSyncHelper::obtenerSiguienteId($conexionPrincipal, $config["tabla"], $config["id_field"]);

    MenuSyncHelper::insertarRegistro(
        $conexionPrincipal,
        $tipo,
        $nuevoId,
        $dependencia,
        $nombre,
        $descripcion,
        $icono,
        $orden,
        $fechaRegistro,
        $visible
    );

    if ($tipo === "menu") {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "guardar_o_actualizar_modulo_lista_blanca")) {
            $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
        }
    }

    $basesClientes = MenuSyncHelper::obtenerBasesClientesActivas($conexionPrincipal);

    foreach ($basesClientes as $dbName) {
        $connCliente = null;

        try {
            $connCliente = MenuSyncHelper::conectarCliente($insMainModel, $dbName);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar a la base {$dbName}.";
                continue;
            }

            $connCliente->autocommit(false);

            MenuSyncHelper::upsertRegistro(
                $connCliente,
                $tipo,
                $nuevoId,
                $dependencia,
                $nombre,
                $descripcion,
                $icono,
                $orden,
                $fechaRegistro,
                $visible
            );

            $connCliente->commit();

        } catch (Exception $eCliente) {
            if ($connCliente) {
                $connCliente->rollback();
            }

            $erroresClientes[] = "Error en {$dbName}: " . $eCliente->getMessage();

        } finally {
            if ($connCliente) {
                $connCliente->autocommit(true);
                $connCliente->close();
            }
        }
    }

    $conexionPrincipal->commit();

    $mensaje = empty($erroresClientes)
        ? "Elemento registrado correctamente y sincronizado en las bases de clientes activas."
        : "Elemento registrado en la base principal, pero algunas bases de clientes no pudieron sincronizarse.";

    MenuSyncHelper::respuesta("success", "Operación exitosa", $mensaje, [
        "id" => $nuevoId,
        "warnings" => $erroresClientes
    ]);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    MenuSyncHelper::respuesta("error", "Error", "Error en el servidor: " . $e->getMessage());

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}