<?php
if($peticionAjax){
    require_once "../core/configAPP.php";
    require_once "../core/mainModel.php"; // Incluir la clase mainModel para la conexión
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

    public function __construct() {

    }

    public function decryptionEmail($string){
        $key = hash('sha256', SECRET_KEY);
        $iv = substr(hash('sha256', SECRET_IV), 0, 16);
        $output = openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);

        return $output;
    }    

    public function enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id, $archivos_adjuntos = []) {
        ini_set('max_execution_time', 300); // Establece el tiempo máximo de ejecución a 300 segundos (5 minutos)

        $mail = new PHPMailer(true);

        // Instanciar la clase mainModel
        $mainModel = new mainModel();
        // Usamos la instancia para llamar a la conexión
        $conexion = $mainModel->connection(); 

        // Consultamos el correo de donde enviaremos la información
        $query = "SELECT server, correo, password FROM correo WHERE correo_tipo_id = ? LIMIT 1";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $correo_tipo_id);
        $stmt->execute();
        $resultadoCorreos = $stmt->get_result();

        $correo_empresa = '';
        $pass_empresa = '';
        $de_empresa = '';
        $smtp = '';
        $nombre = '';
        $logotipo = '';			
        $ubicacion = '';
        $telefono = '';	
        $sitioweb = '';		
        $correo = '';
        $rtn = '';
        $numero = '';
        $parte1 = '';
        $parte2 = '';

        if ($resultadoCorreos->num_rows > 0) {
            //CONSULTAMOS LOS DATOS DE LA EMPRESA PARA ENVIAR EL CORREO
            $row = $resultadoCorreos->fetch_assoc();
            $smtp = $row['server']; 
            $correo_empresa = $row['correo'];
            $pass_empresa = $this->decryptionEmail($row['password']);
            
            // Consultamos el nombre de la empresa
            $queryEmpresa = "SELECT nombre, logotipo, ubicacion, telefono, sitioweb, correo, rtn FROM empresa WHERE empresa_id = ? AND estado = 1 LIMIT 1";
            $stmtEmpresa = $conexion->prepare($queryEmpresa);
            $stmtEmpresa->bind_param("i", $empresa_id);
            $stmtEmpresa->execute();
            $resultadoEmpresa = $stmtEmpresa->get_result();

            if ($resultadoEmpresa->num_rows > 0) {
                $rowEmpresa = $resultadoEmpresa->fetch_assoc();
                $de_empresa = $rowEmpresa['nombre'];
                $nombre = $rowEmpresa['nombre'];
                $logotipo = $rowEmpresa['logotipo'];		
                $ubicacion = $rowEmpresa['ubicacion'];
                $numero_formateado = "";

                $numero = $rowEmpresa['telefono'];
                
                if($numero != "") {
                    $parte1 = substr($numero, 0, 4);
                    $parte2 = substr($numero, 4);
                    $numero_formateado = '+504 ' . $parte1 . '-' . $parte2;
                }

                $telefono = $numero_formateado;	
                $sitioweb = $rowEmpresa['sitioweb'];		
                $correo = $rowEmpresa['correo'];
                $rtn = $rowEmpresa['rtn'];        
            } else {
                $de_empresa = "CLINICARE";
                $nombre = "CLINICARE";
                $logotipo = "logo.png";	
                $ubicacion = "Col. Monte Carlo, 6-7 , 22 AVENIDA B Casa #17 San Pedro Sula, Cortes";
                $telefono = "+504 25035517";	
                $sitioweb = "https://clinicarehn.com";	
                $correo = "clinicare@clinicarehn.com";
                $rtn = "05019021318813";                
            }

            $datos_empresa = [
                "empresa" => strtoupper(trim($nombre)),
                "logotipo" => $logotipo,				
                "ubicacion" => $ubicacion,
                "telefono" => $telefono,				
                "sitioweb" => $sitioweb,				
                "correo" => $correo,
                "rtn" => $rtn		
            ];            

            try {
                // Configuración del servidor de correo saliente (SMTP)
                $mail->isSMTP();
                $mail->SMTPKeepAlive = true;
                $mail->Host          = $smtp; // Cambiar por el servidor de correo saliente
                $mail->SMTPAuth      = true;
                $mail->Username      = $correo_empresa; // Cambiar por tu correo electrónico
                $mail->Password      = $pass_empresa; // Cambiar por tu contraseña de correo
                $mail->SMTPSecure    = PHPMailer::ENCRYPTION_STARTTLS; // SSL - 587
                $mail->Port          = 587; // SSL

                // Configuración del correo
                $mail->setFrom($correo_empresa, $de_empresa);
                $mail->isHTML(true);
                // Especificamos el conjunto de caracteres para el mensaje y los encabezados
                $mail->CharSet = 'UTF-8';
                $mail->ContentType = 'text/html; charset=UTF-8';
        
                foreach ($destinatarios as $email => $nombre) {
                    $mail->addAddress($email, $nombre);

                    // Agregar destinatarios en copia oculta (Bcc)
                    foreach ($bccDestinatarios as $bccEmail => $bccNombre) {
                        $mail->addBCC($bccEmail, $bccNombre);
                    }                       
                
                    // Asunto y cuerpo del correo con la plantilla HTML
                    $mail->Subject = $asunto;
    
                    // Cuerpo del mensaje utilizando la plantilla
                    $htmlMensaje = $this->getCorreoPlantilla($asunto, $mensaje, $datos_empresa);
    
                    $mail->Body = $htmlMensaje;
                    $mail->Encoding = 'base64';
        
                    // Adjuntar archivos
                    foreach ($archivos_adjuntos as $archivo) {
                        $mail->addAttachment($archivo);
                    }

                    $success = false; // Variable para almacenar el estado del envío

                    if ($mail->send()) {                                    
                        $success = true; // Envío exitoso
                    } else {
                        echo 'Error al enviar el correo: ' . $mail->ErrorInfo;
                    }                    
                    
                    // Limpiar los destinatarios y adjuntos
                    $mail->clearAddresses();
                    $mail->ClearAttachments();
                    
                    // Retornar el resultado después de limpiar
                    return $success ? 1 : 0;
                } 
            } catch (Exception $e) {
                echo 'Error al enviar el correo: ' . $e->getMessage();
            }            
        }
    }

    public function obtenerDatosEmpresa($empresa_id) {
        // Instanciar la clase mainModel
        $mainModel = new mainModel();
        // Usamos la instancia para llamar a la conexión
        $conexion = $mainModel->connection(); 
    
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
                  WHERE empresa_id = ? AND estado = 1
                  LIMIT 1";
    
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
    
        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        } else {
            return [
                'empresa_id' => 0,
                'nombre' => 'Empresa Desconocida',
                'razon_social' => '',
                'correo' => 'soporte@tudominio.com',
                'logotipo' => '',
                'ubicacion' => '',
                'telefono' => '',
                'celular' => '',
                'facebook' => '',
                'sitioweb' => '',
                'eslogan' => ''
            ];
        }
    }    

    public function getCorreoPlantilla($asunto, $mensaje, $datos_empresa) {
        // Datos de tu empresa
        $nombreEmpresa = $datos_empresa['empresa'];
        $direccionEmpresa = $datos_empresa['ubicacion'];
        $telefonoEmpresa = $datos_empresa['telefono'];
        $rtnEmpresa = $datos_empresa['rtn'];
        $sitioWebEmpresa = $datos_empresa['sitioweb'];
        $urlLogoEmpresa = SERVERURL."vistas/plantilla/img/logos/".$datos_empresa['logotipo'];
    
        // Encabezado del correo
        $encabezado = '
            <div style="background-color: #f2f2f2; padding: 20px; text-align: center;">
                <img src="'.$urlLogoEmpresa.'" alt="Logo de '.$nombreEmpresa.'" style="max-width: 70%;">
                <h1>'.$nombreEmpresa.'</h1>
                <p>'.$direccionEmpresa.'</p>
                <p>Teléfono: '.$telefonoEmpresa.'</p>
                <p>RTN: '.$rtnEmpresa.'</p>
                <p>Sitio Web: '.$sitioWebEmpresa.'</p>
            </div>';
    
        // Pie de página del correo
        $pieDePagina = '<div style="background-color: #f2f2f2; padding: 20px; text-align: center;">
            <p><b>Este correo fue enviado por '.$nombreEmpresa.', por favor no respondas a este correo</b>.</p>
        </div>';
    
        // Cuerpo del mensaje
        $htmlMensaje = '<html>
        <head>
        <title>'.$asunto.'</title>
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