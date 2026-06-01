<?php
// controladores/correoControlador.php

if($peticionAjax){
    require_once "../modelos/correoModelo.php";
}else{
    require_once "./modelos/correoModelo.php";
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

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Destinatario registrado correctamente",
            "form" => "formDestinatarios",
            "funcion" => "listar_destinatarios();"
        ]);
    }
}