<?php
// controladores/correoControlador.php

if($peticionAjax){
    require_once "../modelos/correoModelo.php";
    require_once "../core/correo/NotificationService.php";
}else{
    require_once "./modelos/correoModelo.php";
    require_once "./core/correo/NotificationService.php";
}

class correoControlador extends correoModelo{

    private function usuarioPuedeEditarCorreo(){
        $privilegio = isset($_SESSION['privilegio_sd']) ? (int)$_SESSION['privilegio_sd'] : 0;

        /*
            1 = Super Administrador
            2 = Administrador
            3 = Reseller
            4 = Clientes
            5 = Contabilidad
        */
        return in_array($privilegio, [1, 2], true);
    }

    private function contieneValorEnmascarado($valor){
        return strpos((string)$valor, '****') !== false;
    }

    private function notificarCorreo($titulo, $resumen, array $detalles = [], array $cambios = [], $tipo = 'security'){
        try {
            $service = new NotificationService();
            $dbActual = $service->currentDbName();

            if ($dbActual === '') {
                return ['sent' => false, 'count' => 0, 'message' => 'Base de datos actual no identificada.'];
            }

            return $service->notifyAdmins(
                $dbActual,
                isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0,
                'IZZY · '.$titulo,
                $resumen,
                $detalles,
                $cambios,
                $tipo
            );
        } catch (Throwable $e) {
            error_log('Correo - notificación: '.$e->getMessage());
            return ['sent' => false, 'count' => 0, 'message' => $e->getMessage()];
        }
    }

    private function obtenerConfiguracionCorreoAuditoria($correoId){
        $conexion = null;
        $stmt = null;

        try {
            $conexion = mainModel::connection();
            $stmt = $conexion->prepare("\n                SELECT\n                    correo_id, metodo_envio, server, correo, port, smtp_secure,\n                    graph_user, save_to_sent_items\n                FROM correo\n                WHERE correo_id = ?\n                LIMIT 1\n            ");

            if (!$stmt) return null;

            $id = (int)$correoId;
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            return $resultado ? $resultado->fetch_assoc() : null;
        } catch (Throwable $e) {
            error_log('Correo - lectura anterior para auditoría: '.$e->getMessage());
            return null;
        } finally {
            if ($stmt) $stmt->close();
        }
    }

    public function edit_correo_controlador(){

        if (!$this->usuarioPuedeEditarCorreo()) {
            return mainModel::showNotification([
                "title" => "Acceso restringido",
                "text" => "No tiene permisos para modificar la configuración de correo. Esta configuración controla el envío de facturas, notificaciones, recuperación de contraseña e inicios de sesión.",
                "type" => "error"
            ]);
        }

        $correo_id = isset($_POST['correo_id']) ? (int)$_POST['correo_id'] : 0;

        $metodo_envio = isset($_POST['metodoEnvioConfEmail']) ? strtoupper(mainModel::cleanString($_POST['metodoEnvioConfEmail'])) : "SMTP";
        $serverConfEmail = isset($_POST['serverConfEmail']) ? mainModel::cleanString($_POST['serverConfEmail']) : "";
        $correoConfEmail = isset($_POST['correoConfEmail']) ? mainModel::cleanStringStrtolower($_POST['correoConfEmail']) : "";
        $passConfEmail = isset($_POST['passConfEmail']) ? trim($_POST['passConfEmail']) : "";
        $puertoConfEmail = isset($_POST['puertoConfEmail']) ? (int)$_POST['puertoConfEmail'] : 0;
        $smtpSecureConfEmail = isset($_POST['smtpSecureConfEmail']) ? mainModel::cleanString($_POST['smtpSecureConfEmail']) : "";

        $tenantIdConfEmail = isset($_POST['tenantIdConfEmail']) ? trim($_POST['tenantIdConfEmail']) : "";
        $clientIdConfEmail = isset($_POST['clientIdConfEmail']) ? trim($_POST['clientIdConfEmail']) : "";
        $clientSecretConfEmail = isset($_POST['clientSecretConfEmail']) ? trim($_POST['clientSecretConfEmail']) : "";
        $graphUserConfEmail = isset($_POST['graphUserConfEmail']) ? mainModel::cleanStringStrtolower($_POST['graphUserConfEmail']) : "";
        $saveToSentItemsConfEmail = isset($_POST['saveToSentItemsConfEmail']) ? (int)$_POST['saveToSentItemsConfEmail'] : 1;

        if ($correo_id <= 0) {
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se recibió el ID de configuración de correo",
                "type" => "error"
            ]);
        }

        if ($metodo_envio !== "SMTP" && $metodo_envio !== "GRAPH") {
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "Método de envío no válido",
                "type" => "error"
            ]);
        }

        if ($correoConfEmail == "" || !filter_var($correoConfEmail, FILTER_VALIDATE_EMAIL)) {
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "Debe ingresar un correo válido",
                "type" => "error"
            ]);
        }

        if ($metodo_envio === "SMTP") {
            if ($serverConfEmail == "") {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe ingresar el servidor SMTP",
                    "type" => "error"
                ]);
            }

            if ($puertoConfEmail <= 0) {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe ingresar un puerto SMTP válido",
                    "type" => "error"
                ]);
            }

            if ($smtpSecureConfEmail == "") {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe seleccionar SMTP Secure",
                    "type" => "error"
                ]);
            }

            $tenantIdConfEmail = "";
            $clientIdConfEmail = "";
            $clientSecretConfEmail = "";
            $graphUserConfEmail = "";
            $saveToSentItemsConfEmail = 1;
        }

        if ($metodo_envio === "GRAPH") {
            $serverConfEmail = "graph.microsoft.com";
            $puertoConfEmail = 0;
            $smtpSecureConfEmail = "";

            /*
                Tenant ID y Client ID pueden venir enmascarados.
                Si vienen con ****, el modelo conservará el valor real guardado.
            */
            if ($tenantIdConfEmail == "") {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe ingresar el Tenant ID o conservar el valor actual enmascarado.",
                    "type" => "error"
                ]);
            }

            if ($clientIdConfEmail == "") {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe ingresar el Client ID o conservar el valor actual enmascarado.",
                    "type" => "error"
                ]);
            }

            if ($graphUserConfEmail == "") {
                $graphUserConfEmail = $correoConfEmail;
            }

            if (!filter_var($graphUserConfEmail, FILTER_VALIDATE_EMAIL)) {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Debe ingresar un Graph User válido",
                    "type" => "error"
                ]);
            }

            $saveToSentItemsConfEmail = $saveToSentItemsConfEmail == 1 ? 1 : 0;
        }

        $passwordFinal = "";
        $clientSecretFinal = "";

        if ($passConfEmail !== "") {
            $passwordFinal = mainModel::encryption($passConfEmail);
        }

        if ($clientSecretConfEmail !== "") {
            $clientSecretFinal = mainModel::encryption($clientSecretConfEmail);
        }

        $anterior = $this->obtenerConfiguracionCorreoAuditoria($correo_id);

        $datos = [
            "correo_id" => $correo_id,
            "metodo_envio" => $metodo_envio,
            "server" => $serverConfEmail,
            "correo" => $correoConfEmail,
            "password" => $passwordFinal,
            "port" => $puertoConfEmail,
            "smtp_secure" => $smtpSecureConfEmail,
            "tenant_id" => $tenantIdConfEmail,
            "client_id" => $clientIdConfEmail,
            "client_secret" => $clientSecretFinal,
            "graph_user" => $graphUserConfEmail,
            "save_to_sent_items" => $saveToSentItemsConfEmail
        ];

        if(!correoModelo::edit_correo_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo actualizar el correo",
                "type" => "error"
            ]);
        }

        $cambios = [];
        if ($anterior) {
            $comparaciones = [
                'Método de envío' => [(string)$anterior['metodo_envio'], (string)$metodo_envio],
                'Servidor' => [(string)$anterior['server'], (string)$serverConfEmail],
                'Correo remitente' => [(string)$anterior['correo'], (string)$correoConfEmail],
                'Puerto' => [(string)$anterior['port'], (string)$puertoConfEmail],
                'Seguridad SMTP' => [(string)$anterior['smtp_secure'], (string)$smtpSecureConfEmail],
                'Graph User' => [(string)$anterior['graph_user'], (string)$graphUserConfEmail],
                'Guardar en enviados' => [((int)$anterior['save_to_sent_items'] === 1 ? 'Sí' : 'No'), ((int)$saveToSentItemsConfEmail === 1 ? 'Sí' : 'No')]
            ];

            foreach ($comparaciones as $campo => $valores) {
                if ($valores[0] !== $valores[1]) {
                    $cambios[$campo] = ['anterior' => $valores[0], 'nuevo' => $valores[1]];
                }
            }
        }

        if ($passConfEmail !== '') {
            $cambios['Contraseña SMTP'] = ['anterior' => 'Conservada / protegida', 'nuevo' => 'Actualizada'];
        }
        if ($clientSecretConfEmail !== '') {
            $cambios['Client Secret'] = ['anterior' => 'Conservado / protegido', 'nuevo' => 'Actualizado'];
        }

        $this->notificarCorreo(
            'Configuración de correo actualizada',
            'Se modificó la configuración utilizada para el envío de correos del sistema.',
            [
                'Método de envío' => $metodo_envio,
                'Correo remitente' => $correoConfEmail,
                'Servidor' => $serverConfEmail,
                'Graph User' => $graphUserConfEmail !== '' ? $graphUserConfEmail : 'No aplica'
            ],
            $cambios,
            'security'
        );

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Correo actualizado correctamente",
            "form" => "formConfEmails",
            "funcion" => "listar_correos_configuracion();getSMTPSecure();getTipoCorreo();"
        ]);
    }

    public function registrar_destinatarios_correo_controlador(){
        $correo = mainModel::cleanStringStrtolower($_POST['correo']);
        $nombre = mainModel::cleanString($_POST['nombre']);

        $datos = [
            "correo" => $correo,
            "nombre" => $nombre
        ];

        if(correoModelo::valid_pdestinatarios_modelo($correo)->num_rows > 0){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo registrar el destinatario",
            ]);
        }

        if(!correoModelo::agregar_destinatarios_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar el destinatario",
                "type" => "error"
            ]);
        }

        $this->notificarCorreo(
            'Destinatario de notificaciones agregado',
            'Se agregó un nuevo destinatario para las notificaciones administrativas de esta base de datos.',
            [
                'Nombre' => $nombre,
                'Correo' => $correo,
                'Estado' => 'Activo'
            ],
            [],
            'info'
        );

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Destinatario registrado correctamente",
            "form" => "formDestinatarios",
            "funcion" => "listar_destinatarios();"
        ]);
    }
}
