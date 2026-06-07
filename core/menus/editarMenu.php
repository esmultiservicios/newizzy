<?php
// core/menus/editarMenu.php

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

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
$tipo = isset($_POST["tipo"]) ? MenuSyncHelper::normalizarTipo($_POST["tipo"]) : "";
$nombre = isset($_POST["nombre"]) ? trim((string)$_POST["nombre"]) : "";
$descripcion = isset($_POST["descripcion"]) ? trim((string)$_POST["descripcion"]) : "";
$icono = isset($_POST["icono"]) ? trim((string)$_POST["icono"]) : "";
$orden = isset($_POST["orden"]) && is_numeric($_POST["orden"]) ? (int)$_POST["orden"] : 0;
$dependencia = isset($_POST["dependencia"]) && $_POST["dependencia"] !== "" ? (int)$_POST["dependencia"] : 0;
$visible = isset($_POST["visible"]) ? (int)$_POST["visible"] : 1;
$visible = ($visible === 1) ? 1 : 0;
$fechaRegistro = date("Y-m-d H:i:s");

if ($id <= 0) {
    MenuSyncHelper::respuesta("error", "Error", "No se recibió un ID válido.");
}

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
$oldName = "";

try {
    $conexionPrincipal = MenuSyncHelper::conectarPrincipal($insMainModel);

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    MenuSyncHelper::asegurarEstructuraMenus($conexionPrincipal);

    $oldName = MenuSyncHelper::obtenerNombreActual(
        $conexionPrincipal,
        $config["tabla"],
        $config["id_field"],
        $id
    );

    if ($oldName === "") {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Registro no encontrado", "El {$config["nombre_tipo"]} que intenta editar no existe.");
    }

    if (!MenuSyncHelper::validarDependencia($conexionPrincipal, $config, $tipo, $dependencia)) {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Dependencia inválida", "La dependencia seleccionada no existe.");
    }

    if (MenuSyncHelper::existeDuplicado($conexionPrincipal, $tipo, $id, $nombre, $dependencia)) {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Advertencia", "Ya existe un registro con este nombre y dependencia.");
    }

    MenuSyncHelper::actualizarRegistro(
        $conexionPrincipal,
        $tipo,
        $id,
        $dependencia,
        $nombre,
        $descripcion,
        $icono,
        $orden,
        $visible
    );

    if ($tipo === "menu" && $oldName !== $nombre) {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "guardar_o_actualizar_modulo_lista_blanca")) {
            $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
        }

        if (method_exists($insMainModel, "eliminar_modulo_lista_blanca")) {
            $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $oldName);
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
                $id,
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
        ? "Elemento actualizado correctamente y sincronizado en las bases de clientes activas."
        : "Elemento actualizado en la base principal, pero algunas bases de clientes no pudieron sincronizarse.";

    MenuSyncHelper::respuesta("success", "Operación exitosa", $mensaje, [
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