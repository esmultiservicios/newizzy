<?php
// core/menus/eliminarMenu.php

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

if ($id <= 0) {
    MenuSyncHelper::respuesta("error", "Error", "No se recibió un ID válido.");
}

if ($tipo === "") {
    MenuSyncHelper::respuesta("error", "Error", "No se recibió un tipo de menú válido.");
}

$config = MenuSyncHelper::obtenerConfig($tipo);

$conexionPrincipal = null;
$erroresClientes = [];
$nombreModulo = "";

try {
    $conexionPrincipal = MenuSyncHelper::conectarPrincipal($insMainModel);

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    MenuSyncHelper::asegurarEstructuraMenus($conexionPrincipal);

    $nombreModulo = MenuSyncHelper::obtenerNombreActual(
        $conexionPrincipal,
        $config["tabla"],
        $config["id_field"],
        $id
    );

    if ($nombreModulo === "") {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Registro no encontrado", "El {$config["nombre_tipo"]} que intenta eliminar no existe.");
    }

    if (MenuSyncHelper::tieneDependencias($conexionPrincipal, $tipo, $id)) {
        $conexionPrincipal->rollback();
        MenuSyncHelper::respuesta("warning", "Advertencia", "No se puede eliminar este {$config["nombre_tipo"]} porque tiene elementos dependientes.");
    }

    MenuSyncHelper::eliminarRegistro($conexionPrincipal, $tipo, $id);

    if ($tipo === "menu" && $nombreModulo !== "") {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "eliminar_modulo_lista_blanca")) {
            $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $nombreModulo);
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

            MenuSyncHelper::eliminarRegistro($connCliente, $tipo, $id);

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
        ? "Registro eliminado correctamente y sincronizado en las bases de clientes activas."
        : "Registro eliminado en la base principal, pero algunas bases de clientes no pudieron sincronizarse.";

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