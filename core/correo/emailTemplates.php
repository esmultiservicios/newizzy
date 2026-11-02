<?php
// core/correo/emailTemplates.php

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../configAPP.php';

class emailTemplates {
    public function __construct() {}

    private function esc($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    private function safeUrl($value) {
        $value = trim((string)$value);
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }

    private function plantillaBase($titulo, $contenido, $datosEmpresa, $tipo = 'info') {
        $year = date('Y');
        $nombreEmpresa = trim((string)($datosEmpresa['nombre'] ?? $datosEmpresa['empresa'] ?? 'ES MULTISERVICIOS'));
        $eslogan = trim((string)($datosEmpresa['eslogan'] ?? ''));
        $ubicacion = trim((string)($datosEmpresa['ubicacion'] ?? ''));
        $telefono = trim((string)($datosEmpresa['telefono'] ?? ''));
        $celular = trim((string)($datosEmpresa['celular'] ?? ''));
        $correo = trim((string)($datosEmpresa['correo'] ?? ''));
        $sitioweb = $this->safeUrl($datosEmpresa['sitioweb'] ?? '');
        $urlLogo = $this->safeUrl($datosEmpresa['url_logo'] ?? '');

        $nombreEmpresaHtml = $this->esc($nombreEmpresa);
        $tituloHtml = $this->esc($titulo);
        $esloganHtml = $this->esc($eslogan);
        $ubicacionHtml = $this->esc($ubicacion);
        $telefonoHtml = $this->esc($telefono);
        $celularHtml = $this->esc($celular);
        $correoHtml = $this->esc($correo);
        $sitioHtml = $this->esc($sitioweb);

        $tipo = strtolower(trim((string)$tipo));
        $accent = '#0EA5A8';
        $badge = 'Información';
        if ($tipo === 'success') { $accent = '#14804A'; $badge = 'Confirmación'; }
        elseif ($tipo === 'warning') { $accent = '#B26A00'; $badge = 'Importante'; }
        elseif ($tipo === 'security') { $accent = '#17324D'; $badge = 'Seguridad'; }
        elseif ($tipo === 'billing') { $accent = '#0EA5A8'; $badge = 'Facturación'; }
        elseif ($tipo === 'audit') { $accent = '#5E6C84'; $badge = 'Auditoría'; }

        $logoHtml = '';
        if ($urlLogo !== '') {
            $logoHtml = '<img src="'.$this->esc($urlLogo).'" alt="'.$nombreEmpresaHtml.'" width="142" style="display:block;width:142px;max-width:142px;height:auto;margin:0 auto;border:0;outline:none;text-decoration:none;">';
        } else {
            $logoHtml = '<div style="font-family:Arial Black,Arial,Helvetica,sans-serif;font-size:38px;line-height:1.1;font-weight:900;letter-spacing:-2px;color:#1477EF;text-align:center;">IZZY.</div>';
        }

        $contactos = array();
        if ($telefonoHtml !== '') $contactos[] = 'Tel. '.$telefonoHtml;
        if ($celularHtml !== '') $contactos[] = 'Cel. '.$celularHtml;
        if ($correoHtml !== '') $contactos[] = $correoHtml;
        $contactoHtml = implode('<br>', $contactos);

        $webHtml = $sitioweb !== ''
            ? '<a href="'.$this->esc($sitioweb).'" style="color:#0EA5A8;text-decoration:none;font-weight:700;">'.$sitioHtml.'</a>'
            : '';

        $footerMeta = array_filter(array($ubicacionHtml, $contactoHtml, $webHtml));
        $footerMetaHtml = implode('<br>', $footerMeta);

        return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title>'.$tituloHtml.' | '.$nombreEmpresaHtml.'</title>
<style>
@media only screen and (max-width: 620px) {
  .izzy-shell { width:100% !important; max-width:100% !important; }
  .izzy-pad { padding-left:14px !important; padding-right:14px !important; }
  .izzy-title { font-size:21px !important; line-height:1.3 !important; }
  .izzy-body { font-size:14px !important; line-height:1.55 !important; }
  .izzy-logo-wrap { padding-top:14px !important; padding-bottom:12px !important; }
  .izzy-card { padding:12px !important; }
  .izzy-body img { max-width:100% !important; height:auto !important; }
  .izzy-body a { overflow-wrap:anywhere !important; word-wrap:break-word !important; }
  .izzy-numeric { white-space:nowrap !important; word-break:normal !important; overflow-wrap:normal !important; }
  .izzy-detail-label, .izzy-detail-value { width:auto !important; display:block !important; }
}
</style>
</head>
<body style="margin:0;padding:0;background:#F7F9FC;font-family:Arial,Helvetica,sans-serif;color:#172B4D;-webkit-text-size-adjust:100%;">
<div style="width:100%;margin:0;padding:8px 5px;box-sizing:border-box;background:#F7F9FC;">
<div class="izzy-shell" style="width:100%;max-width:600px;margin:0 auto;box-sizing:border-box;background:#FFFFFF;border:1px solid #DDE3EA;">
<div class="izzy-logo-wrap" style="padding:16px 14px 14px;text-align:center;border-bottom:1px solid #DDE3EA;">'.$logoHtml.'</div>
<div style="height:4px;background:'.$accent.';font-size:0;line-height:0;">&nbsp;</div>
<div class="izzy-pad" style="padding:16px 16px 5px;box-sizing:border-box;">
<span style="display:inline-block;padding:6px 10px;background:#F7F9FC;border:1px solid #DDE3EA;border-radius:999px;color:'.$accent.';font-size:11px;line-height:1.2;font-weight:800;letter-spacing:.7px;text-transform:uppercase;">'.$this->esc($badge).'</span>
<h1 class="izzy-title" style="margin:9px 0 0;font-size:23px;line-height:1.28;color:#17324D;font-weight:800;overflow-wrap:break-word;">'.$tituloHtml.'</h1>
'.($esloganHtml !== '' ? '<p style="margin:7px 0 0;font-size:13px;line-height:1.5;color:#5E6C84;">'.$esloganHtml.'</p>' : '').'
</div>
<div class="izzy-pad izzy-body" style="padding:10px 16px 20px;box-sizing:border-box;max-width:100%;font-size:16px;line-height:1.6;color:#253858;overflow-wrap:anywhere;word-wrap:break-word;">'.$contenido.'</div>
<div style="padding:0 16px;"><div style="height:1px;background:#DDE3EA;font-size:0;line-height:0;">&nbsp;</div></div>
<div class="izzy-pad" style="padding:14px 16px 18px;box-sizing:border-box;background:#F7F9FC;color:#5E6C84;font-size:12px;line-height:1.6;text-align:center;overflow-wrap:anywhere;">
<div style="font-weight:800;color:#17324D;">'.$nombreEmpresaHtml.'</div>
'.($footerMetaHtml !== '' ? '<div style="margin-top:6px;">'.$footerMetaHtml.'</div>' : '').'
<div style="margin-top:12px;color:#6B778C;">Este es un mensaje automático. Por favor, no responda directamente a este correo.</div>
<div style="margin-top:8px;color:#6B778C;">© '.$year.' '.$nombreEmpresaHtml.' · Todos los derechos reservados</div>
</div>
</div>
</div>
</body>
</html>';
    }

    public function plantillaContenido($titulo, $contenidoHtml, $datosEmpresa, $tipo = 'info') {
        return $this->plantillaBase($titulo, $contenidoHtml, $datosEmpresa, $tipo);
    }

    public function plantillaBienvenida($datosUsuario, $datosEmpresa) {
        $loginUrl = rtrim(SERVERURL, '/') . '/login/';
        $nombre = $this->esc($datosUsuario['nombre'] ?? 'Usuario');
        $empresa = $this->esc($datosUsuario['empresa'] ?? '');
        $email = $this->esc($datosUsuario['email'] ?? '');
        $password = $this->esc($datosUsuario['password'] ?? '');

        $contenido = '<p style="margin:0 0 16px;">Hola <strong>'.$nombre.'</strong>,</p>
<p style="margin:0 0 18px;">Tu cuenta en IZZY fue creada correctamente. Estas credenciales son personales y fueron enviadas únicamente a tu correo.</p>
<div class="izzy-card" style="padding:18px;background:#F7F9FC;border:1px solid #DDE3EA;border-left:4px solid #0EA5A8;border-radius:8px;">
<div style="margin-bottom:10px;"><strong style="color:#17324D;">Empresa</strong><br>'.$empresa.'</div>
<div style="margin-bottom:10px;"><strong style="color:#17324D;">Usuario</strong><br>'.$email.'</div>
<div><strong style="color:#17324D;">Contraseña temporal</strong><br><span style="font-family:Consolas,Monaco,monospace;font-weight:700;">'.$password.'</span></div>
</div>
<p style="margin:18px 0 0;">Por seguridad, cambia tu contraseña después del primer acceso.</p>
<p style="margin:22px 0 0;text-align:center;"><a href="'.$this->esc($loginUrl).'" style="display:inline-block;padding:12px 22px;background:#17324D;color:#FFFFFF;text-decoration:none;border-radius:7px;font-weight:800;">Ingresar a IZZY</a></p>';

        return $this->plantillaBase('Bienvenido a IZZY', $contenido, $datosEmpresa, 'security');
    }

    public function plantillaGenerica($titulo, $mensaje, $datosEmpresa, $accion = null, $tipo = 'info') {
        $contenido = $mensaje;
        if ($accion && !empty($accion['url']) && !empty($accion['texto'])) {
            $contenido .= '<p style="margin:22px 0 0;text-align:center;"><a href="'.$this->esc($accion['url']).'" style="display:inline-block;padding:12px 22px;background:#17324D;color:#FFFFFF;text-decoration:none;border-radius:7px;font-weight:800;">'.$this->esc($accion['texto']).'</a></p>';
        }
        return $this->plantillaBase($titulo, $contenido, $datosEmpresa, $tipo);
    }
}
