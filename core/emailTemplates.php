<?php
if($peticionAjax){
    require_once "../core/configAPP.php";
}else{
    require_once "./core/configAPP.php";    
}

class emailTemplates {
    public function __construct() {}

    private function plantillaBase($titulo, $contenido, $datosEmpresa) {
        $loginUrl = SERVERURL . "login";
        $year = date('Y');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo} | {$datosEmpresa['nombre']}</title>
    <style>
        /* RESET BÁSICO */
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background-color: #f5f7fa;
        }
        
        /* CONTENEDOR PRINCIPAL */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        /* CABECERA */
        .email-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            padding: 30px 20px;
            text-align: center;
            position: relative;
            color: white;
        }
        
        .email-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
        }
        
        .email-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .email-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: inherit;
        }
        
        .email-eslogan {
            font-style: italic;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 8px;
        }
        
        /* CONTENIDO */
        .email-content {
            padding: 30px;
            line-height: 1.6;
        }
        
        .email-content h2 {
            color: #2c3e50;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .email-content p {
            margin-bottom: 16px;
            font-size: 15px;
        }
        
        /* SECCIÓN DESTACADA */
        .email-highlight {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .email-highlight p {
            margin: 0;
            font-size: 14px;
        }
        
        .email-highlight strong {
            color: #2c3e50;
        }
        
        /* BOTÓN */
        .email-button {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 15px 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .email-button:hover {
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* PIE DE PÁGINA */
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
        
        .contact-info {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }
        
        .contact-item i {
            color: #3498db;
            font-size: 14px;
        }
        
        .social-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-links a {
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            color: #2980b9;
            transform: translateY(-2px);
        }
        
        .copyright {
            margin-top: 20px;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="{$datosEmpresa['url_logo']}" alt="{$datosEmpresa['nombre']}" class="email-logo">
            <h1 class="email-title">{$datosEmpresa['nombre']}</h1>
            <p class="email-eslogan">{$datosEmpresa['eslogan']}</p>
        </div>
        
        <div class="email-content">
            {$contenido}
        </div>
        
        <div class="email-footer">
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{$datosEmpresa['ubicacion']}</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>{$datosEmpresa['telefono']}</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>{$datosEmpresa['celular']}</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>{$datosEmpresa['correo']}</span>
                </div>
            </div>
            
            <div class="social-links">
                <a href="{$datosEmpresa['sitioweb']}"><i class="fas fa-globe"></i> Sitio Web</a>
                <a href="{$datosEmpresa['facebook']}"><i class="fab fa-facebook-f"></i> Facebook</a>
            </div>
            
            <p class="copyright">
                © {$year} {$datosEmpresa['nombre']} · Todos los derechos reservados
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    public function plantillaBienvenida($datosUsuario, $datosEmpresa) {
        $contenido = <<<HTML
            <h2>¡Bienvenido/a, {$datosUsuario['nombre']}!</h2>
            
            <p>Gracias por registrarte en nuestra plataforma. Estamos encantados de tenerte con nosotros.</p>
            
            <p>A continuación encontrarás los detalles de acceso a tu cuenta:</p>
            
            <div class="email-highlight">
                <p><strong>Empresa:</strong> {$datosUsuario['empresa']}</p>
                <p><strong>Usuario:</strong> {$datosUsuario['username']}</p>
                <p><strong>Correo electrónico:</strong> {$datosUsuario['email']}</p>
                <p><strong>Base de datos asignada:</strong> {$datosUsuario['nombre_db']}</p>
                <p><strong>Contraseña temporal:</strong> {$datosUsuario['password']}</p>
            </div>
            
            <p style="text-align: center;">
                <a href="{SERVERURL}/login" class="email-button">Acceder al Sistema</a>
            </p>
            
            <p>Por seguridad, te recomendamos cambiar tu contraseña después de iniciar sesión por primera vez.</p>
            
            <p>Si tienes alguna pregunta o necesitas asistencia, no dudes en contactar a nuestro equipo de soporte.</p>
            
            <p>Atentamente,<br>El equipo de {$datosEmpresa['nombre']}</p>
HTML;

        return $this->plantillaBase("Bienvenido", $contenido, $datosEmpresa);
    }

    // Método para plantilla genérica
    public function plantillaGenerica($titulo, $mensaje, $datosEmpresa, $accion = null) {
        $contenido = <<<HTML
            <h2>{$titulo}</h2>
            
            <p>{$mensaje}</p>
            
            {$this->generarBotonAccion($accion, $datosEmpresa)}
            
            <p>Si tienes alguna pregunta o no reconoces esta acción, por favor contacta con nuestro equipo de soporte.</p>
HTML;

        return $this->plantillaBase($titulo, $contenido, $datosEmpresa);
    }

    private function generarBotonAccion($accion, $datosEmpresa) {
        if (!$accion) return '';
        
        return <<<HTML
            <p style="text-align: center;">
                <a href="{$accion['url']}" class="email-button">{$accion['texto']}</a>
            </p>
HTML;
    }
}