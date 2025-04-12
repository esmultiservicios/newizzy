<?php
if($peticionAjax){
    require_once "../core/configAPP.php";
    require_once "../core/mainModel.php";
}else{
    require_once "./core/configAPP.php";
    require_once "./core/mainModel.php";    
}

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class sendEmail {
    protected $mainModel;

    public function __construct() {
        $this->mainModel = new mainModel();
    }

    public function decryptionEmail($string) {
        $key = hash('sha256', SECRET_KEY);
        $iv = substr(hash('sha256', SECRET_IV), 0, 16);
        return openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);
    }

    private function formatearTelefono($numero) {
        if (empty($numero)) return "";
        $parte1 = substr($numero, 0, 4);
        $parte2 = substr($numero, 4);
        return '+504 ' . $parte1 . '-' . $parte2;
    }

    public function obtenerDatosEmpresa($empresa_id) {
        $consulta = "SELECT razon_social, nombre, eslogan, celular, telefono, correo, 
                    logotipo, rtn, ubicacion, facebook, sitioweb, horario, firma_documento 
                    FROM empresa WHERE empresa_id = '$empresa_id'";
        
        $resultado = $this->mainModel->ejecutar_consulta_simple($consulta);
        
        if ($resultado->num_rows == 0) {
            return $this->datosEmpresaPorDefecto();
        }
        
        $empresa = $resultado->fetch_assoc();
        
        return [
            "empresa_id" => $empresa_id,
            "razon_social" => $empresa['razon_social'],
            "nombre" => strtoupper(trim($empresa['nombre'])),
            "nombre_completo" => $empresa['razon_social'] . " (" . $empresa['nombre'] . ")",
            "eslogan" => $empresa['eslogan'],
            "telefono" => $this->formatearTelefono($empresa['telefono']),
            "celular" => $this->formatearTelefono($empresa['celular']),
            "correo" => $empresa['correo'],
            "logotipo" => $empresa['logotipo'],
            "rtn" => $empresa['rtn'],
            "ubicacion" => $empresa['ubicacion'],
            "facebook" => $empresa['facebook'],
            "sitioweb" => $empresa['sitioweb'],
            "horario" => $empresa['horario'],
            "firma_documento" => $empresa['firma_documento'],
            "url_logo" => SERVERURL."vistas/plantilla/img/logos/".$empresa['logotipo'],
            "url_firma" => SERVERURL."vistas/plantilla/img/firmas/".$empresa['firma_documento']
        ];
    }

    private function datosEmpresaPorDefecto() {
        return [
            "empresa_id" => 0,
            "razon_social" => "Edwin Javier Velasquez Cortes",
            "nombre" => "E.S MULTISERVICIOS",
            "nombre_completo" => "E.S MULTISERVICIOS",
            "eslogan" => "Mas que servicios, construimos soluciones",
            "telefono" => "",
            "celular" => "+504 8913-6844",
            "correo" => "edwin.velasquez@esmultiservicios.com",
            "logotipo" => "logo.png",
            "rtn" => "18041991043390",
            "ubicacion" => "San Jose V Calle: Principal Esquina Opuesta A Canchas De Majoncho Sosa",
            "facebook" => "#",
            "sitioweb" => "#",
            "horario" => "Lunes a Viernes: 8:00 AM - 5:00 PM",
            "firma_documento" => "firma.png",
            "url_logo" => SERVERURL."vistas/plantilla/img/logos/logo.png",
            "url_firma" => SERVERURL."vistas/plantilla/img/firmas/firma.png"
        ];
    }

    public function enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id, $archivos_adjuntos = []) {
        ini_set('max_execution_time', 300);
        $mail = new PHPMailer(true);

        try {
            // 1. Obtener configuración SMTP
            $consultaCorreo = "SELECT server, correo, password FROM correo WHERE correo_tipo_id = '$correo_tipo_id'";
            $resultadoCorreo = $this->mainModel->ejecutar_consulta_simple($consultaCorreo);
            
            if ($resultadoCorreo->num_rows == 0) {
                error_log("No se encontró configuración de correo para tipo: $correo_tipo_id");
                return false;
            }
            
            $configCorreo = $resultadoCorreo->fetch_assoc();
            $smtp = $configCorreo['server'];
            $correo_empresa = $configCorreo['correo'];
            $pass_empresa = $this->decryptionEmail($configCorreo['password']);

            // 2. Obtener datos de la empresa
            $datos_empresa = $this->obtenerDatosEmpresa($empresa_id);

            // 3. Configurar PHPMailer
            $mail->isSMTP();
            $mail->SMTPKeepAlive = true;
            $mail->Host = $smtp;
            $mail->SMTPAuth = true;
            $mail->Username = $correo_empresa;
            $mail->Password = $pass_empresa;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->ContentType = 'text/html; charset=UTF-8';
            $mail->setFrom($correo_empresa, $datos_empresa['nombre_completo']);
            $mail->addReplyTo($datos_empresa['correo'], $datos_empresa['nombre']);

            // 4. Configurar destinatarios
            foreach ($destinatarios as $email => $nombre) {
                $mail->addAddress($email, $nombre);

                foreach ($bccDestinatarios as $bccEmail => $bccNombre) {
                    $mail->addBCC($bccEmail, $bccNombre);
                }

                // 5. Configurar contenido
                $mail->Subject = $asunto;
                $mail->Body = $mensaje;
                $mail->Encoding = 'base64';

                // 6. Adjuntar archivos
                foreach ($archivos_adjuntos as $archivo) {
                    $mail->addAttachment($archivo);
                }

                // 7. Enviar correo
                $success = $mail->send();
                $mail->clearAddresses();
                $mail->ClearAttachments();

                if (!$success) {
                    error_log("Error al enviar correo a $email: " . $mail->ErrorInfo);
                    return false;
                }

                return true;
            }
        } catch (Exception $e) {
            error_log("Excepción al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    public function testingMail($servidor, $correo, $contraseña, $puerto, $SMTPSecure, $CharSet) {
        $mail = new PHPMailer(true);

        try {
            // Configuración SMTP
            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = $servidor;
            $mail->SMTPAuth = true;
            $mail->Username = $correo;
            $mail->Password = $contraseña;
            $mail->SMTPSecure = $SMTPSecure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $puerto;
            $mail->CharSet = $CharSet;
            
            // Opciones SSL para evitar problemas con certificados
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Configuración del correo
            $mail->setFrom($correo, 'Prueba de conexión SMTP');
            $mail->addAddress($correo);
            $mail->Subject = 'Prueba de conexión SMTP';
            $mail->Body = '<h1>Prueba de conexión exitosa</h1><p>Este correo confirma que la configuración SMTP es correcta.</p>';
            $mail->AltBody = 'Prueba de conexión exitosa - Configuración SMTP correcta';

            // Intenta enviar el correo
            if ($mail->send()) {
                return 1;  // Éxito
            } else {
                error_log("Error al enviar prueba: " . $mail->ErrorInfo);
                return 2;  // Fallo
            }
        } catch (Exception $e) {
            error_log("Excepción en prueba SMTP: " . $e->getMessage());
            return 2;  // Fallo
        }
    }    
}