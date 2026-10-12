<?php
// modelos/correoModelo.php

if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class correoModelo extends mainModel{

    private function contieneValorEnmascaradoModelo($valor){
        return strpos((string)$valor, '****') !== false;
    }

    /**
     * Convierte credenciales antiguas en texto plano al formato cifrado actual.
     * Las credenciales ya cifradas se conservan sin cifrarlas dos veces.
     */
    private function protegerCredencialCorreo($valor) {
        $valor = (string)$valor;
        if ($valor === '') return '';
        if (strpos($valor, '****') !== false) {
            throw new RuntimeException('No se puede guardar una credencial enmascarada.');
        }

        $decoded = base64_decode($valor, true);
        if ($decoded !== false && $decoded !== '') {
            $key = hash('sha256', SECRET_KEY);
            $iv = substr(hash('sha256', SECRET_IV), 0, 16);
            $texto = openssl_decrypt($decoded, METHOD, $key, 0, $iv);
            if ($texto !== false && $texto !== '' && mainModel::encryption($texto) === $valor) {
                return $valor;
            }
        }
        return mainModel::encryption($valor);
    }

    protected function edit_correo_modelo($datos) {
        $conexion = mainModel::connection();

        try {
            $conexion->autocommit(false);

            $correo_id = (int)$datos['correo_id'];

            $stmtActual = $conexion->prepare("
                SELECT 
                    password, 
                    tenant_id,
                    client_id,
                    client_secret
                FROM correo
                WHERE correo_id = ?
                LIMIT 1
            ");

            if (!$stmtActual) {
                throw new Exception($conexion->error);
            }

            $stmtActual->bind_param("i", $correo_id);
            $stmtActual->execute();
            $resultadoActual = $stmtActual->get_result();

            if (!$resultadoActual || $resultadoActual->num_rows <= 0) {
                throw new Exception("No se encontró la configuración de correo.");
            }

            $rowActual = $resultadoActual->fetch_assoc();

            $passwordFinal = $rowActual['password'];
            $tenantIdFinal = $rowActual['tenant_id'];
            $clientIdFinal = $rowActual['client_id'];
            $clientSecretFinal = $rowActual['client_secret'];

            // Migra los secretos antiguos incluso cuando el usuario no los modifica.
            $passwordFinal = $this->protegerCredencialCorreo($passwordFinal);
            $tenantIdFinal = $this->protegerCredencialCorreo($tenantIdFinal);
            $clientIdFinal = $this->protegerCredencialCorreo($clientIdFinal);
            $clientSecretFinal = $this->protegerCredencialCorreo($clientSecretFinal);

            /*
                Password SMTP:
                Si viene vacío, conserva el actual.
            */
            if (isset($datos['password']) && trim($datos['password']) !== "") {
                if (!$this->contieneValorEnmascaradoModelo($datos['password'])) {
                    $passwordFinal = $this->protegerCredencialCorreo($datos['password']);
                }
            }

            /*
                Tenant ID:
                Si viene vacío o enmascarado con ****, conserva el valor real actual.
                Si viene completo, actualiza.
            */
            if (
                isset($datos['tenant_id']) &&
                trim($datos['tenant_id']) !== "" &&
                !$this->contieneValorEnmascaradoModelo($datos['tenant_id'])
            ) {
                $tenantIdFinal = $this->protegerCredencialCorreo(trim($datos['tenant_id']));
            }

            /*
                Client ID:
                Si viene vacío o enmascarado con ****, conserva el valor real actual.
                Si viene completo, actualiza.
            */
            if (
                isset($datos['client_id']) &&
                trim($datos['client_id']) !== "" &&
                !$this->contieneValorEnmascaradoModelo($datos['client_id'])
            ) {
                $clientIdFinal = $this->protegerCredencialCorreo(trim($datos['client_id']));
            }

            /*
                Client Secret:
                Nunca se muestra.
                Si viene vacío, conserva el actual.
                Si viene nuevo, actualiza.
            */
            if (isset($datos['client_secret']) && trim($datos['client_secret']) !== "") {
                if (!$this->contieneValorEnmascaradoModelo($datos['client_secret'])) {
                    $clientSecretFinal = $this->protegerCredencialCorreo($datos['client_secret']);
                }
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

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param("ssssisssssii",
                $datos['metodo_envio'],
                $datos['server'],
                $datos['correo'],
                $passwordFinal,
                $datos['port'],
                $datos['smtp_secure'],
                $tenantIdFinal,
                $clientIdFinal,
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

        } catch (Throwable $e) {
            $conexion->rollback();
            error_log('Correo - actualización segura: ' . $e->getMessage());
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

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

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

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param("s", $correo);
            $stmt->execute();

            return $stmt->get_result();

        } catch (Exception $e) {
            return false;
        }
    }
}