<?php
// core/correo/sendEmail.php

if (!isset($peticionAjax)) {
    $peticionAjax = false;
}

require_once __DIR__ . '/../configAPP.php';
require_once __DIR__ . '/../mainModel.php';

require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class sendEmail {

    public function __construct() {

    }

    public function decryptionEmail($string) {
        if (empty($string)) {
            return "";
        }

        $key = hash('sha256', SECRET_KEY);
        $iv = substr(hash('sha256', SECRET_IV), 0, 16);
        $output = openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);

        return $output !== false ? $output : "";
    }

    private function obtenerConexion() {
        $mainModel = new mainModel();
        return $mainModel->connection();
    }

    private function limpiarEmail($email) {
        return trim(strtolower($email));
    }

    private function validarCorreo($email) {
        $email = $this->limpiarEmail($email);
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function normalizarMetodoEnvio($metodo_envio) {
        $metodo_envio = strtoupper(trim($metodo_envio));

        if ($metodo_envio !== "GRAPH" && $metodo_envio !== "SMTP") {
            return "SMTP";
        }

        return $metodo_envio;
    }

    private function obtenerConfiguracionCorreo($correo_tipo_id) {
        $conexion = $this->obtenerConexion();

        $correo_tipo_id = (int)$correo_tipo_id;

        $query = "SELECT 
                    correo_id,
                    correo_tipo_id,
                    metodo_envio,
                    server,
                    correo,
                    password,
                    port,
                    smtp_secure,
                    tenant_id,
                    client_id,
                    client_secret,
                    graph_user,
                    save_to_sent_items,
                    estado
                  FROM correo
                  WHERE correo_tipo_id = ?
                    AND estado = 1
                  LIMIT 1";

        $stmt = $conexion->prepare($query);

        if (!$stmt) {
            return [
                "success" => false,
                "data" => null,
                "message" => "Error preparando consulta de correo: " . $conexion->error
            ];
        }

        $stmt->bind_param("i", $correo_tipo_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            return [
                "success" => false,
                "data" => null,
                "message" => "No existe configuración de correo activa para el tipo indicado."
            ];
        }

        $row = $resultado->fetch_assoc();
        $row["metodo_envio"] = $this->normalizarMetodoEnvio($row["metodo_envio"]);

        return [
            "success" => true,
            "data" => $row,
            "message" => ""
        ];
    }

    private function obtenerConfiguracionCorreoPorId($correo_id) {
        $conexion = $this->obtenerConexion();

        $correo_id = (int)$correo_id;

        $query = "SELECT 
                    correo_id,
                    correo_tipo_id,
                    metodo_envio,
                    server,
                    correo,
                    password,
                    port,
                    smtp_secure,
                    tenant_id,
                    client_id,
                    client_secret,
                    graph_user,
                    save_to_sent_items,
                    estado
                  FROM correo
                  WHERE correo_id = ?
                    AND estado = 1
                  LIMIT 1";

        $stmt = $conexion->prepare($query);

        if (!$stmt) {
            return [
                "success" => false,
                "data" => null,
                "message" => "Error preparando consulta de correo: " . $conexion->error
            ];
        }

        $stmt->bind_param("i", $correo_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            return [
                "success" => false,
                "data" => null,
                "message" => "No existe configuración de correo activa para el ID indicado."
            ];
        }

        $row = $resultado->fetch_assoc();
        $row["metodo_envio"] = $this->normalizarMetodoEnvio($row["metodo_envio"]);

        return [
            "success" => true,
            "data" => $row,
            "message" => ""
        ];
    }

    private function datosEmpresaDefault() {
        return [
            "empresa" => "CLINICARE",
            "logotipo" => "logo.png",
            "ubicacion" => "Col. Monte Carlo, 6-7 , 22 AVENIDA B Casa #17 San Pedro Sula, Cortes",
            "telefono" => "+504 25035517",
            "sitioweb" => "https://clinicarehn.com",
            "correo" => "clinicare@clinicarehn.com",
            "rtn" => "05019021318813"
        ];
    }

    private function obtenerDatosEmpresaPlantilla($empresa_id) {
        $conexion = $this->obtenerConexion();

        $empresa_id = (int)$empresa_id;

        if ($empresa_id <= 0) {
            return $this->datosEmpresaDefault();
        }

        $query = "SELECT 
                    nombre,
                    logotipo,
                    ubicacion,
                    telefono,
                    sitioweb,
                    correo,
                    rtn
                  FROM empresa
                  WHERE empresa_id = ?
                    AND estado = 1
                  LIMIT 1";

        $stmt = $conexion->prepare($query);

        if (!$stmt) {
            return $this->datosEmpresaDefault();
        }

        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $rowEmpresa = $resultado->fetch_assoc();

            $numero_formateado = "";
            $numero = trim($rowEmpresa["telefono"]);

            if ($numero != "") {
                $numero_limpio = preg_replace('/[^0-9]/', '', $numero);

                if (strlen($numero_limpio) == 8) {
                    $parte1 = substr($numero_limpio, 0, 4);
                    $parte2 = substr($numero_limpio, 4);
                    $numero_formateado = "+504 " . $parte1 . "-" . $parte2;
                } else {
                    $numero_formateado = $numero;
                }
            }

            return [
                "empresa" => strtoupper(trim($rowEmpresa["nombre"])),
                "logotipo" => $rowEmpresa["logotipo"],
                "ubicacion" => $rowEmpresa["ubicacion"],
                "telefono" => $numero_formateado,
                "sitioweb" => $rowEmpresa["sitioweb"],
                "correo" => $rowEmpresa["correo"],
                "rtn" => $rowEmpresa["rtn"]
            ];
        }

        return $this->datosEmpresaDefault();
    }

    private function obtenerNombreRemitente($datos_empresa) {
        if (isset($datos_empresa["empresa"]) && trim($datos_empresa["empresa"]) != "") {
            return trim($datos_empresa["empresa"]);
        }

        return "Sistema";
    }

    public function enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id, $archivos_adjuntos = []) {
        ini_set('max_execution_time', 300);

        $configResult = $this->obtenerConfiguracionCorreo($correo_tipo_id);

        if (!$configResult["success"]) {
            echo $configResult["message"];
            return 0;
        }

        $configCorreo = $configResult["data"];

        $datos_empresa = $this->obtenerDatosEmpresaPlantilla($empresa_id);
        $htmlMensaje = $this->getCorreoPlantilla($asunto, $mensaje, $datos_empresa);

        if ($configCorreo["metodo_envio"] === "GRAPH") {
            $resultado = $this->enviarCorreoGraph(
                $configCorreo,
                $destinatarios,
                $bccDestinatarios,
                $asunto,
                $htmlMensaje,
                $archivos_adjuntos
            );

            if ($resultado["success"]) {
                return 1;
            }

            echo $resultado["message"];
            return 0;
        }

        $resultado = $this->enviarCorreoSMTP(
            $configCorreo,
            $destinatarios,
            $bccDestinatarios,
            $asunto,
            $htmlMensaje,
            $datos_empresa,
            $archivos_adjuntos
        );

        if ($resultado["success"]) {
            return 1;
        }

        echo $resultado["message"];
        return 0;
    }

    private function enviarCorreoSMTP($configCorreo, $destinatarios, $bccDestinatarios, $asunto, $htmlMensaje, $datos_empresa, $archivos_adjuntos = []) {
        $mail = new PHPMailer(true);

        try {
            $server = trim($configCorreo["server"]);
            $correo_empresa = trim($configCorreo["correo"]);
            $password_guardado = trim($configCorreo["password"]);
            $pass_empresa = $this->decryptionEmail($password_guardado);

            if (empty($pass_empresa)) {
                $pass_empresa = $password_guardado;
            }

            $port = (int)$configCorreo["port"];
            $smtp_secure = strtolower(trim($configCorreo["smtp_secure"]));

            if ($port <= 0) {
                $port = 587;
            }

            if (empty($smtp_secure)) {
                $smtp_secure = "tls";
            }

            if (!$this->validarCorreo($correo_empresa)) {
                return [
                    "success" => false,
                    "message" => "El correo remitente SMTP no es válido."
                ];
            }

            $mail->isSMTP();
            $mail->SMTPKeepAlive = false;
            $mail->Host = $server;
            $mail->SMTPAuth = true;
            $mail->Username = $correo_empresa;
            $mail->Password = $pass_empresa;

            if ($smtp_secure === "ssl") {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = $port;
            $mail->CharSet = "UTF-8";
            $mail->ContentType = "text/html; charset=UTF-8";
            $mail->Encoding = "base64";
            $mail->isHTML(true);

            $mail->setFrom($correo_empresa, $this->obtenerNombreRemitente($datos_empresa));

            foreach ($destinatarios as $email => $nombre) {
                if ($this->validarCorreo($email)) {
                    $mail->addAddress($this->limpiarEmail($email), trim($nombre));
                }
            }

            foreach ($bccDestinatarios as $bccEmail => $bccNombre) {
                if ($this->validarCorreo($bccEmail)) {
                    $mail->addBCC($this->limpiarEmail($bccEmail), trim($bccNombre));
                }
            }

            if (count($mail->getToAddresses()) <= 0) {
                return [
                    "success" => false,
                    "message" => "No hay destinatarios válidos para enviar el correo."
                ];
            }

            $mail->Subject = $asunto;
            $mail->Body = $htmlMensaje;

            foreach ($archivos_adjuntos as $archivo) {
                if (!empty($archivo) && file_exists($archivo) && is_readable($archivo)) {
                    $mail->addAttachment($archivo);
                }
            }

            if ($mail->send()) {
                $mail->clearAddresses();
                $mail->clearBCCs();
                $mail->clearAttachments();

                return [
                    "success" => true,
                    "message" => "Correo enviado correctamente por SMTP."
                ];
            }

            return [
                "success" => false,
                "message" => "Error al enviar el correo SMTP: " . $mail->ErrorInfo
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al enviar el correo SMTP: " . $e->getMessage()
            ];
        }
    }

    private function prepararDestinatariosGraph($destinatarios) {
        $lista = [];

        foreach ($destinatarios as $email => $nombre) {
            $email = $this->limpiarEmail($email);

            if ($this->validarCorreo($email)) {
                $lista[] = [
                    "emailAddress" => [
                        "address" => $email,
                        "name" => trim($nombre)
                    ]
                ];
            }
        }

        return $lista;
    }

    private function prepararAdjuntosGraph($archivos_adjuntos) {
        $adjuntos = [];

        foreach ($archivos_adjuntos as $archivo) {
            if (empty($archivo)) {
                continue;
            }

            if (!file_exists($archivo)) {
                continue;
            }

            if (!is_readable($archivo)) {
                continue;
            }

            $contenido = file_get_contents($archivo);

            if ($contenido === false) {
                continue;
            }

            $mimeType = mime_content_type($archivo);

            if (empty($mimeType)) {
                $mimeType = "application/octet-stream";
            }

            $adjuntos[] = [
                "@odata.type" => "#microsoft.graph.fileAttachment",
                "name" => basename($archivo),
                "contentType" => $mimeType,
                "contentBytes" => base64_encode($contenido)
            ];
        }

        return $adjuntos;
    }

    private function ejecutarCurl($url, $method = "GET", $headers = [], $body = null) {
        $ch = curl_init();

        if (!$ch) {
            return [
                "success" => false,
                "status" => 0,
                "body" => "",
                "error" => "No se pudo inicializar cURL."
            ];
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                "success" => false,
                "status" => 0,
                "body" => "",
                "error" => $error
            ];
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseBody = substr($response, $headerSize);

        curl_close($ch);

        return [
            "success" => ($status >= 200 && $status < 300),
            "status" => $status,
            "body" => $responseBody,
            "error" => ""
        ];
    }

    private function obtenerClientSecret($client_secret_guardado) {
        $client_secret_guardado = trim($client_secret_guardado);

        if (empty($client_secret_guardado)) {
            return "";
        }

        $client_secret_descifrado = $this->decryptionEmail($client_secret_guardado);

        if (!empty($client_secret_descifrado)) {
            return $client_secret_descifrado;
        }

        return $client_secret_guardado;
    }

    private function obtenerAccessTokenGraph($tenant_id, $client_id, $client_secret) {
        $tenant_id = trim($tenant_id);
        $client_id = trim($client_id);
        $client_secret = trim($client_secret);

        if (empty($tenant_id) || empty($client_id) || empty($client_secret)) {
            return [
                "success" => false,
                "access_token" => "",
                "message" => "Faltan tenant_id, client_id o client_secret para Microsoft Graph."
            ];
        }

        $url = "https://login.microsoftonline.com/" . rawurlencode($tenant_id) . "/oauth2/v2.0/token";

        $body = http_build_query([
            "client_id" => $client_id,
            "scope" => "https://graph.microsoft.com/.default",
            "client_secret" => $client_secret,
            "grant_type" => "client_credentials"
        ]);

        $response = $this->ejecutarCurl(
            $url,
            "POST",
            [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            $body
        );

        if (!$response["success"]) {
            return [
                "success" => false,
                "access_token" => "",
                "message" => "Error obteniendo token de Microsoft Graph. HTTP " . $response["status"] . " - " . $response["body"] . " " . $response["error"]
            ];
        }

        $json = json_decode($response["body"], true);

        if (!isset($json["access_token"])) {
            return [
                "success" => false,
                "access_token" => "",
                "message" => "Microsoft no devolvió access_token. Respuesta: " . $response["body"]
            ];
        }

        return [
            "success" => true,
            "access_token" => $json["access_token"],
            "message" => ""
        ];
    }

    private function enviarCorreoGraph($configCorreo, $destinatarios, $bccDestinatarios, $asunto, $htmlMensaje, $archivos_adjuntos = []) {
        $tenant_id = trim($configCorreo["tenant_id"]);
        $client_id = trim($configCorreo["client_id"]);
        $client_secret = $this->obtenerClientSecret($configCorreo["client_secret"]);

        $graph_user = trim($configCorreo["graph_user"]);

        if (empty($graph_user)) {
            $graph_user = trim($configCorreo["correo"]);
        }

        if (!$this->validarCorreo($graph_user)) {
            return [
                "success" => false,
                "message" => "El correo emisor graph_user no es válido."
            ];
        }

        $toRecipients = $this->prepararDestinatariosGraph($destinatarios);
        $bccRecipients = $this->prepararDestinatariosGraph($bccDestinatarios);

        if (empty($toRecipients)) {
            return [
                "success" => false,
                "message" => "No hay destinatarios válidos para enviar por Graph."
            ];
        }

        $token = $this->obtenerAccessTokenGraph($tenant_id, $client_id, $client_secret);

        if (!$token["success"]) {
            return [
                "success" => false,
                "message" => $token["message"]
            ];
        }

        $message = [
            "subject" => $asunto,
            "body" => [
                "contentType" => "HTML",
                "content" => $htmlMensaje
            ],
            "toRecipients" => $toRecipients
        ];

        if (!empty($bccRecipients)) {
            $message["bccRecipients"] = $bccRecipients;
        }

        $adjuntos = $this->prepararAdjuntosGraph($archivos_adjuntos);

        if (!empty($adjuntos)) {
            $message["attachments"] = $adjuntos;
        }

        $saveToSentItems = true;

        if (isset($configCorreo["save_to_sent_items"])) {
            $saveToSentItems = ((int)$configCorreo["save_to_sent_items"] === 1);
        }

        $payload = [
            "message" => $message,
            "saveToSentItems" => $saveToSentItems
        ];

        $url = "https://graph.microsoft.com/v1.0/users/" . rawurlencode($graph_user) . "/sendMail";

        $response = $this->ejecutarCurl(
            $url,
            "POST",
            [
                "Authorization: Bearer " . $token["access_token"],
                "Content-Type: application/json"
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        if ($response["status"] === 202) {
            return [
                "success" => true,
                "message" => "Correo enviado correctamente por Microsoft Graph."
            ];
        }

        return [
            "success" => false,
            "message" => "Error enviando correo por Microsoft Graph. HTTP " . $response["status"] . " - " . $response["body"]
        ];
    }

    public function testingMail($servidor, $correo, $contraseña, $puerto, $SMTPSecure, $CharSet = "UTF-8") {
        $mail = new PHPMailer(true);

        try {
            $servidor = trim($servidor);
            $correo = trim($correo);
            $contraseña = trim($contraseña);
            $puerto = (int)$puerto;
            $SMTPSecure = strtolower(trim($SMTPSecure));

            if (!$this->validarCorreo($correo)) {
                return "El correo no es válido.";
            }

            if ($puerto <= 0) {
                $puerto = 587;
            }

            if (empty($SMTPSecure)) {
                $SMTPSecure = "tls";
            }

            $password_descifrado = $this->decryptionEmail($contraseña);

            if (!empty($password_descifrado)) {
                $contraseña = $password_descifrado;
            }

            $mail->isSMTP();
            $mail->Host = $servidor;
            $mail->SMTPAuth = true;
            $mail->Username = $correo;
            $mail->Password = $contraseña;

            if ($SMTPSecure === "ssl") {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = $puerto;
            $mail->CharSet = $CharSet;
            $mail->isHTML(true);
            $mail->setFrom($correo, "Prueba SMTP");
            $mail->addAddress($correo, "Prueba SMTP");
            $mail->Subject = "Prueba de correo SMTP";
            $mail->Body = "
                <p>Esta es una prueba de envío SMTP.</p>
                <p>Si recibió este mensaje, la configuración SMTP funciona correctamente.</p>
            ";

            if ($mail->send()) {
                return "1";
            }

            return "Error SMTP: " . $mail->ErrorInfo;

        } catch (Exception $e) {
            return "Error SMTP: " . $e->getMessage();
        }
    }

    public function testingConfiguracion($datos) {
        $metodo_envio = isset($datos["metodo_envio"]) ? strtoupper(trim($datos["metodo_envio"])) : "SMTP";
        $correo_id = isset($datos["correo_id"]) ? (int)$datos["correo_id"] : 0;
    
        if ($metodo_envio !== "GRAPH" && $metodo_envio !== "SMTP") {
            return "Método de envío no válido.";
        }
    
        $configActual = null;
    
        if ($correo_id > 0) {
            $configResult = $this->obtenerConfiguracionCorreoPorId($correo_id);
    
            if ($configResult["success"]) {
                $configActual = $configResult["data"];
            }
        }
    
        if ($metodo_envio === "SMTP") {
            $server = isset($datos["server"]) ? trim($datos["server"]) : "";
            $correo = isset($datos["correo"]) ? trim($datos["correo"]) : "";
            $password = isset($datos["password"]) ? trim($datos["password"]) : "";
            $port = isset($datos["port"]) ? (int)$datos["port"] : 587;
            $smtp_secure = isset($datos["smtp_secure"]) ? trim($datos["smtp_secure"]) : "tls";
    
            if (empty($server) && $configActual) {
                $server = $configActual["server"];
            }
    
            if (empty($correo) && $configActual) {
                $correo = $configActual["correo"];
            }
    
            if (empty($password) && $configActual) {
                $password = $configActual["password"];
            }
    
            if ($port <= 0 && $configActual) {
                $port = (int)$configActual["port"];
            }
    
            if (empty($smtp_secure) && $configActual) {
                $smtp_secure = $configActual["smtp_secure"];
            }
    
            if (empty($server)) {
                return "Debe ingresar el servidor SMTP.";
            }
    
            if (!$this->validarCorreo($correo)) {
                return "Debe ingresar un correo SMTP válido.";
            }
    
            if (empty($password)) {
                return "Debe ingresar la contraseña SMTP o tener una guardada para realizar la prueba.";
            }
    
            return $this->testingMail($server, $correo, $password, $port, $smtp_secure, "UTF-8");
        }
    
        $correo = isset($datos["correo"]) ? trim($datos["correo"]) : "";
        $tenant_id = isset($datos["tenant_id"]) ? trim($datos["tenant_id"]) : "";
        $client_id = isset($datos["client_id"]) ? trim($datos["client_id"]) : "";
        $client_secret = isset($datos["client_secret"]) ? trim($datos["client_secret"]) : "";
        $graph_user = isset($datos["graph_user"]) ? trim($datos["graph_user"]) : "";
        $save_to_sent_items = isset($datos["save_to_sent_items"]) ? (int)$datos["save_to_sent_items"] : 1;
    
        if (empty($correo) && $configActual) {
            $correo = $configActual["correo"];
        }
    
        if (empty($tenant_id) && $configActual) {
            $tenant_id = $configActual["tenant_id"];
        }
    
        if (empty($client_id) && $configActual) {
            $client_id = $configActual["client_id"];
        }
    
        if (empty($client_secret) && $configActual) {
            $client_secret = $configActual["client_secret"];
        }
    
        if (empty($graph_user) && $configActual) {
            $graph_user = $configActual["graph_user"];
        }
    
        if (!$this->validarCorreo($correo)) {
            return "Debe ingresar un correo emisor válido.";
        }
    
        if (empty($graph_user)) {
            $graph_user = $correo;
        }
    
        if (!$this->validarCorreo($graph_user)) {
            return "Debe ingresar un Graph User válido.";
        }
    
        if (empty($tenant_id)) {
            return "Debe ingresar el Tenant ID.";
        }
    
        if (empty($client_id)) {
            return "Debe ingresar el Client ID.";
        }
    
        if (empty($client_secret)) {
            return "Debe ingresar el Client Secret VALUE o tener uno guardado para realizar la prueba.";
        }
    
        $configCorreo = [
            "tenant_id" => $tenant_id,
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "graph_user" => $graph_user,
            "correo" => $correo,
            "save_to_sent_items" => $save_to_sent_items
        ];
    
        $destinatarios = [
            $correo => "Prueba Microsoft Graph"
        ];
    
        $bccDestinatarios = [];
    
        $asunto = "Prueba Microsoft Graph API";
    
        $htmlMensaje = '
            <html>
                <head>
                    <title>Prueba Microsoft Graph API</title>
                    <meta charset="UTF-8">
                </head>
                <body>
                    <div style="padding: 20px;">
                        <h2>Prueba Microsoft Graph API</h2>
                        <p>Esta es una prueba de envío usando Microsoft Graph API con OAuth.</p>
                        <p>Si recibió este correo, la configuración funciona correctamente.</p>
                    </div>
                </body>
            </html>
        ';
    
        $resultado = $this->enviarCorreoGraph(
            $configCorreo,
            $destinatarios,
            $bccDestinatarios,
            $asunto,
            $htmlMensaje,
            []
        );
    
        return $resultado["success"] ? "1" : $resultado["message"];
    }

    public function testingMailGraph($correo_tipo_id, $empresa_id, $correo_destino = "") {
        return $this->testingMailPorTipo($correo_tipo_id, $empresa_id, $correo_destino);
    }

    public function testingMailPorTipo($correo_tipo_id, $empresa_id, $correo_destino = "") {
        $configResult = $this->obtenerConfiguracionCorreo($correo_tipo_id);

        if (!$configResult["success"]) {
            return $configResult["message"];
        }

        $configCorreo = $configResult["data"];

        if (empty($correo_destino)) {
            $correo_destino = $configCorreo["correo"];
        }

        if (!$this->validarCorreo($correo_destino)) {
            return "El correo destino no es válido.";
        }

        $destinatarios = [
            $correo_destino => "Prueba de correo"
        ];

        $bccDestinatarios = [];

        $asunto = "Prueba de envío de correo - " . $configCorreo["metodo_envio"];

        $mensaje = '
            <div style="padding: 20px;">
                <p>Esta es una prueba de envío de correo desde el sistema.</p>
                <p><b>Método utilizado:</b> '.$configCorreo["metodo_envio"].'</p>
                <p>Si recibió este correo, la configuración está funcionando correctamente.</p>
            </div>
        ';

        $resultado = $this->enviarCorreo(
            $destinatarios,
            $bccDestinatarios,
            $asunto,
            $mensaje,
            $correo_tipo_id,
            $empresa_id,
            []
        );

        return $resultado == 1 ? "1" : "0";
    }

    public function obtenerDatosEmpresa($empresa_id) {
        $conexion = $this->obtenerConexion();

        $empresa_id = (int)$empresa_id;

        $query = "SELECT 
                    empresa_id,
                    nombre,
                    razon_social,
                    correo,
                    logotipo,
                    ubicacion,
                    telefono,
                    celular,
                    facebook,
                    sitioweb,
                    eslogan
                  FROM empresa
                  WHERE empresa_id = ?
                    AND estado = 1
                  LIMIT 1";

        $stmt = $conexion->prepare($query);

        if (!$stmt) {
            return [
                "empresa_id" => 0,
                "nombre" => "Empresa Desconocida",
                "razon_social" => "",
                "correo" => "soporte@tudominio.com",
                "logotipo" => "",
                "ubicacion" => "",
                "telefono" => "",
                "celular" => "",
                "facebook" => "",
                "sitioweb" => "",
                "eslogan" => ""
            ];
        }

        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return [
            "empresa_id" => 0,
            "nombre" => "Empresa Desconocida",
            "razon_social" => "",
            "correo" => "soporte@tudominio.com",
            "logotipo" => "",
            "ubicacion" => "",
            "telefono" => "",
            "celular" => "",
            "facebook" => "",
            "sitioweb" => "",
            "eslogan" => ""
        ];
    }

    public function getCorreoPlantilla($asunto, $mensaje, $datos_empresa) {
        $nombreEmpresa = $datos_empresa["empresa"];
        $direccionEmpresa = $datos_empresa["ubicacion"];
        $telefonoEmpresa = $datos_empresa["telefono"];
        $rtnEmpresa = $datos_empresa["rtn"];
        $sitioWebEmpresa = $datos_empresa["sitioweb"];
        $logotipoEmpresa = $datos_empresa["logotipo"];

        $urlLogoEmpresa = SERVERURL . "vistas/plantilla/img/logos/" . $logotipoEmpresa;

        $encabezado = '
            <div style="background-color: #f2f2f2; padding: 20px; text-align: center;">
                <img src="'.$urlLogoEmpresa.'" alt="Logo de '.$nombreEmpresa.'" style="max-width: 70%;">
                <h1>'.$nombreEmpresa.'</h1>
                <p>'.$direccionEmpresa.'</p>
                <p>Teléfono: '.$telefonoEmpresa.'</p>
                <p>RTN: '.$rtnEmpresa.'</p>
                <p>Sitio Web: '.$sitioWebEmpresa.'</p>
            </div>';

        $pieDePagina = '
            <div style="background-color: #f2f2f2; padding: 20px; text-align: center;">
                <p><b>Este correo fue enviado por '.$nombreEmpresa.', por favor no respondas a este correo</b>.</p>
            </div>';

        $htmlMensaje = '
            <html>
                <head>
                    <title>'.$asunto.'</title>
                    <meta charset="UTF-8">
                </head>
                <body>
                    '.$encabezado.'
                    <div style="padding: 20px;">
                        <h1>'.$asunto.'</h1>
                        '.$mensaje.'
                    </div>
                    '.$pieDePagina.'
                </body>
            </html>';

        return $htmlMensaje;
    }
}