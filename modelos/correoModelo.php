<?php
// modelos/correoModelo.php

if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class correoModelo extends mainModel{

    protected function edit_correo_modelo($datos) {
        $conexion = mainModel::connection();

        try {
            $conexion->autocommit(false);

            $correo_id = (int)$datos['correo_id'];

            $stmtActual = $conexion->prepare("
                SELECT password, client_secret
                FROM correo
                WHERE correo_id = ?
                LIMIT 1
            ");

            $stmtActual->bind_param("i", $correo_id);
            $stmtActual->execute();
            $resultadoActual = $stmtActual->get_result();

            if (!$resultadoActual || $resultadoActual->num_rows <= 0) {
                throw new Exception("No se encontró la configuración de correo.");
            }

            $rowActual = $resultadoActual->fetch_assoc();

            $passwordFinal = $rowActual['password'];
            $clientSecretFinal = $rowActual['client_secret'];

            if (isset($datos['password']) && trim($datos['password']) !== "") {
                $passwordFinal = $datos['password'];
            }

            if (isset($datos['client_secret']) && trim($datos['client_secret']) !== "") {
                $clientSecretFinal = $datos['client_secret'];
            }

            $stmt = $conexion->prepare("
                UPDATE correo SET
                    metodo_envio = ?,
                    server = ?,
                    correo = ?,
                    password = ?,
                    port = ?,
                    smtp_secure = ?,
                    tenant_id = ?,
                    client_id = ?,
                    client_secret = ?,
                    graph_user = ?,
                    save_to_sent_items = ?
                WHERE correo_id = ?
            ");

            $stmt->bind_param("ssssisssssii",
                $datos['metodo_envio'],
                $datos['server'],
                $datos['correo'],
                $passwordFinal,
                $datos['port'],
                $datos['smtp_secure'],
                $datos['tenant_id'],
                $datos['client_id'],
                $clientSecretFinal,
                $datos['graph_user'],
                $datos['save_to_sent_items'],
                $datos['correo_id']
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $conexion->commit();
            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function agregar_destinatarios_modelo($datos) {
        $conexion = mainModel::connection();

        try {
            $conexion->autocommit(false);

            $notificaciones_id = mainModel::correlativo("notificaciones_id", "notificaciones");

            $stmt = $conexion->prepare("
                INSERT INTO notificaciones (
                    notificaciones_id,
                    correo,
                    nombre,
                    activo
                ) VALUES (?, ?, ?, ?)
            ");

            $activo = 1;

            $stmt->bind_param("issi",
                $notificaciones_id,
                $datos['correo'],
                $datos['nombre'],
                $activo
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $conexion->commit();

            return $notificaciones_id;

        } catch (Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function valid_pdestinatarios_modelo($correo) {
        $conexion = mainModel::connection();

        try {
            $stmt = $conexion->prepare("
                SELECT notificaciones_id
                FROM notificaciones
                WHERE correo = ?
                LIMIT 1
            ");

            $stmt->bind_param("s", $correo);
            $stmt->execute();

            return $stmt->get_result();

        } catch (Exception $e) {
            return false;
        }
    }
}