<?php
// Ubicación: core/notaCredito/verNotaCredito.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/../../controladores/notaCreditoControlador.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_name('SD'); session_start(); }
$mainModel = new mainModel();
$validacion = $mainModel->validarSesion();
if (!empty($validacion['error'])) { http_response_code(401); exit('Sesión inválida.'); }
try {
    $notaId = (int)($_GET['nota_credito_id'] ?? 0);
    $ctrl = new notaCreditoControlador();
    $nota = $ctrl->obtenerNotaPorId($notaId);
} catch (Throwable $e) {
    http_response_code(400);
    exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
if (isset($_GET['pdf']) && $_GET['pdf'] === '1') { ob_start(); }
function eNc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$facturaNumero = trim((string)$nota['factura_prefijo']) . str_pad((string)$nota['factura_number'], (int)$nota['factura_relleno'], '0', STR_PAD_LEFT);
$empresa = [];
try {
    $conexion = $mainModel->connection();
    $empresaId = (int)($nota['empresa_id'] ?? $_SESSION['empresa_id_sd'] ?? $_SESSION['empresa_id'] ?? 0);
    if ($empresaId > 0) {
        $consulta = $conexion->prepare('SELECT nombre, razon_social, rtn, ubicacion, telefono, celular, correo, sitioweb, eslogan, logotipo FROM empresa WHERE empresa_id = ? LIMIT 1');
        if ($consulta) {
            $consulta->bind_param('i', $empresaId);
            $consulta->execute();
            $empresa = $consulta->get_result()->fetch_assoc() ?: [];
            $consulta->close();
        }
    }
} catch (Throwable $e) { error_log('NC · Datos de emisor no disponibles: '.$e->getMessage()); }
$nombreEmpresa = trim((string)($empresa['nombre'] ?? ''));
if ($nombreEmpresa === '') { $nombreEmpresa = trim((string)($empresa['razon_social'] ?? '')); }
$logoUrl = '';
$logoArchivo = basename(str_replace('\\', '/', trim((string)($empresa['logotipo'] ?? ''))));
// Misma ubicación de imágenes empresariales que utiliza el reporte de gastos.
if ($logoArchivo !== '' && $logoArchivo !== '.' && $logoArchivo !== '..') {
    $logoRelativo = 'vistas/plantilla/img/enterprise/' . $logoArchivo;
    if (is_file(dirname(__DIR__, 2) . '/' . $logoRelativo)) {
        $logoRuta = dirname(__DIR__, 2) . '/' . $logoRelativo;
        $tipoMime = strtolower(pathinfo($logoRuta, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
        $logoUrl = 'data:' . $tipoMime . ';base64,' . base64_encode(file_get_contents($logoRuta));
    }
}
$formatoNc = 'carta';
try {
    $resultadoFormatoNc = $mainModel->connection()->query('SELECT tipo FROM impresora WHERE tipo IN (6,7) AND estado=1 ORDER BY tipo DESC LIMIT 1');
    if ($resultadoFormatoNc && ($filaFormatoNc = $resultadoFormatoNc->fetch_assoc())) {
        $formatoNc = ((int)$filaFormatoNc['tipo'] === 7) ? 'ticket' : 'carta';
    }
} catch (Throwable $e) { error_log('Formato NC: '.$e->getMessage()); }
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nota de Crédito <?php echo eNc($nota['numero_completo']); ?></title>
<style>
/* Estilos compartidos: la hoja conserva espacio interno real. */
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    background: #fff;
    color: #172c49;
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 10px;
    line-height: 1.5;
}
@page { size: letter portrait; margin: 12mm 12mm; }
.sheet { width: 100%; max-width: 100%; margin: 0 auto; padding: 0 5mm; }
.brand { display: table; table-layout: fixed; width: 100%; border-top: 4px solid #1098ea; border-bottom: 1px solid #d9e2ec; padding: 12px 0 14px; margin-bottom: 14px; }
.emisor, .doc-id { display: table-cell; vertical-align: top; width: 50%; }
.emisor { padding-right: 14px; }
.doc-id { text-align: right; }
.logo { display: block; max-width: 85px; max-height: 65px; margin-bottom: 6px; }
.marca { font-size: 16px; font-weight: bold; overflow-wrap: break-word; }
.emisor-meta { font-size: 9px; color: #50647b; line-height: 1.55; margin-top: 4px; overflow-wrap: break-word; }
.doc-id h1 { font-size: 18px; letter-spacing: .4px; margin: 0 0 6px; color: #0b1f52; }
.doc-id strong { font-size: 14px; }
.sub { font-size: 9px; margin-top: 5px; }
.muted { color: #596f89; }
.division { display: none; }
.info { display: table; table-layout: fixed; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.box { display: table-cell; vertical-align: top; width: 33.333%; border: 1px solid #dbe4ee; padding: 8px; overflow-wrap: break-word; }
.box small { display: block; color: #526a85; text-transform: uppercase; font-size: 8px; font-weight: bold; letter-spacing: .4px; margin-bottom: 5px; }
.box strong { font-size: 9px; overflow-wrap: anywhere; }
.box:last-child strong { font-size: 7.5px; }
.reason { padding: 9px 11px; margin: 0 0 14px; background: #f4f7fb; border-left: 3px solid #1098ea; overflow-wrap: break-word; }
.lines { width: 100%; margin-bottom: 13px; }
.line { display: table; table-layout: fixed; width: 100%; border-bottom: 1px solid #dce5ee; page-break-inside: avoid; }
.line > div { display: table-cell; vertical-align: middle; width: 16%; padding: 8px 4px; font-size: 8px; overflow-wrap: break-word; }
.line > div:first-child { width: 36%; }
.line.headline { background: #0b1f52; color: #fff; font-weight: bold; }
.money { text-align: right; }
.totals { width: 58%; margin: 10px 0 16px auto; page-break-inside: avoid; }
.totals > div { display: table; table-layout: fixed; width: 100%; padding: 5px 0; border-bottom: 1px solid #e6edf3; }
.totals > div > span, .totals > div > strong { display: table-cell; vertical-align: middle; width: 50%; overflow-wrap: break-word; }
.totals > div > strong, .totals > div > span:last-child { text-align: right; }
.totals .grand { font-size: 12px; font-weight: bold; color: #0b1f52; border-top: 2px solid #0b1f52; border-bottom: 0; padding-top: 10px; }
.credit { color: #067d59; font-weight: bold; }
.foot { display: table; table-layout: fixed; width: 100%; border-top: 1px solid #dce5ef; padding: 12px 0 0; color: #536981; font-size: 9px; page-break-inside: avoid; }
.foot > div { display: table-cell; width: 50%; vertical-align: top; overflow-wrap: break-word; }
.foot strong { color: #0b1f52; }
.foot-right { text-align: right; padding-left: 10px; }
.brand, .box, .reason, .line, .totals, .foot { page-break-inside: avoid; }

/* Formato carta: limitar el ancho a la zona imprimible sin afectar ticket. */
body.formato-carta .sheet {
    width: 184mm;
    max-width: 184mm;
    margin: 0 auto;
    padding: 0;
}
body.formato-carta .brand,
body.formato-carta .info,
body.formato-carta .lines,
body.formato-carta .line,
body.formato-carta .foot {
    width: 100%;
    max-width: 100%;
    table-layout: fixed;
}
body.formato-carta .emisor { width: 53%; padding-right: 5mm; }
body.formato-carta .doc-id { width: 47%; padding-left: 3mm; }
body.formato-carta .doc-id h1 { font-size: 15px; }
body.formato-carta .info { border-spacing: 2px 0; }
body.formato-carta .box { width: 33.333%; padding: 7px 4px; }
body.formato-carta .box strong { overflow-wrap: anywhere; word-wrap: break-word; }
body.formato-carta .line > div { width: 16%; padding: 7px 2px; font-size: 7.5px; }
body.formato-carta .line > div:first-child { width: 36%; }
body.formato-carta .totals { width: 88mm; max-width: 88mm; padding-right: 2mm; }
body.formato-carta .totals > div > span:first-child { width: 57%; }
body.formato-carta .totals > div > strong,
body.formato-carta .totals > div > span:last-child { width: 43%; text-align: right; }
body.formato-carta .foot > div { width: 50%; }
body.formato-carta .foot-right { padding-left: 4mm; }

/* Ticket 80 mm: 6 mm de margen físico y 3 mm de resguardo interno. */
body.formato-ticket { font-size: 9px; line-height: 1.5; }
/* Ancho físico seguro: papel 80 mm - 12 mm de márgenes PDF - 6 mm de resguardo. */
body.formato-ticket .sheet {
    width: 62mm;
    max-width: 62mm;
    margin: 0 auto;
    padding: 0 3mm;
    overflow: hidden;
}
body.formato-ticket .brand { display: block; text-align: center; padding: 6px 0 9px; margin-bottom: 9px; border-top: 0; border-bottom: 1px dashed #7d8b9b; }
body.formato-ticket .emisor, body.formato-ticket .doc-id { display: block; width: 100%; text-align: center; padding: 0; }
body.formato-ticket .logo { display: none; }
body.formato-ticket .marca { font-size: 12px; }
body.formato-ticket .emisor-meta { font-size: 9px; overflow-wrap: anywhere; }
body.formato-ticket .doc-id h1 { font-size: 13px; margin: 9px 0 2px; }
body.formato-ticket .doc-id strong { font-size: 11px; }
body.formato-ticket .sub { font-size: 9px; margin-top: 2px; }
body.formato-ticket .info { display: block; border-spacing: 0; margin-bottom: 8px; }
body.formato-ticket .box { display: block; width: 100%; border: 0; border-bottom: 1px dashed #cbd5e1; padding: 6px 0; }
body.formato-ticket .box small { font-size: 8px; margin: 0 0 2px; }
body.formato-ticket .box strong { font-size: 9px; overflow-wrap: anywhere; }
body.formato-ticket .reason { padding: 7px 8px; margin-bottom: 8px; font-size: 9px; }
body.formato-ticket .line.headline { display: none; }
body.formato-ticket .line { display: block; padding: 7px 0; border-bottom: 1px dashed #aebdca; }
body.formato-ticket .line > div, body.formato-ticket .line > div:first-child { display: block; width: 100%; padding: 2px 0; text-align: left; font-size: 9px; }
body.formato-ticket .line > div:first-child { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
body.formato-ticket .line > div:nth-child(2):before { content: 'Base: '; }
body.formato-ticket .line > div:nth-child(3):before { content: 'ISV 15%: '; }
body.formato-ticket .line > div:nth-child(4):before { content: 'ISV 18%: '; }
body.formato-ticket .line > div:nth-child(5):before { content: 'Total: '; }
body.formato-ticket .totals {
    width: 100%;
    max-width: 100%;
    margin: 8px 0;
    padding-right: 2mm;
    font-size: 8px;
}
body.formato-ticket .totals > div > span:first-child {
    width: 57%;
    padding-right: 2mm;
    overflow-wrap: break-word;
}
body.formato-ticket .totals > div > strong,
body.formato-ticket .totals > div > span:last-child {
    width: 43%;
    text-align: right;
    font-size: 8px;
    white-space: nowrap;
}
body.formato-ticket .totals .grand { font-size: 9px; }
body.formato-ticket .totals .grand > span:last-child { font-size: 9px; }
body.formato-ticket .line,
body.formato-ticket .info,
body.formato-ticket .foot { max-width: 100%; }
body.formato-ticket .foot { display: block; text-align: center; font-size: 8px; padding-top: 8px; }
body.formato-ticket .foot > div { display: block; width: 100%; text-align: center; margin-bottom: 6px; padding: 0; }
</style></head><body class="formato-<?php echo $formatoNc; ?>">
<div class="sheet">
<div class="brand">
<div class="emisor">
<?php if ($formatoNc === 'carta' && $logoUrl !== ''): ?>
<img class="logo" src="<?php echo eNc($logoUrl); ?>" alt="Logotipo de la empresa">
<?php endif; ?>
<div>
<div class="marca">
<?php echo eNc($nombreEmpresa !== '' ? $nombreEmpresa : 'EMPRESA EMISORA'); ?>
</div>
<div class="emisor-meta">
<?php if (!empty($empresa['razon_social']) && $empresa['razon_social'] !== $nombreEmpresa): ?>
<?php echo eNc($empresa['razon_social']); ?>
<br>
<?php endif; ?>
<?php if (!empty($empresa['rtn'])): ?>RTN: <?php echo eNc($empresa['rtn']); ?>
<br>
<?php endif; ?>
<?php if (!empty($empresa['ubicacion'])): ?>
<?php echo eNc($empresa['ubicacion']); ?>
<br>
<?php endif; ?>
<?php echo eNc(implode(' · ', array_filter([$empresa['telefono'] ?? '',$empresa['celular'] ?? '',$empresa['correo'] ?? '']))); ?>
</div>
</div>
</div>
<div class="doc-id"><h1>NOTA DE CRÉDITO</h1>
<strong>
<?php echo eNc($nota['numero_completo']); ?>
</strong>
<div class="sub muted">Fecha de emisión: <?php echo eNc($nota['fecha']); ?>
</div>
<div class="sub muted">Documento complementario de factura</div>
</div>
</div>
<div class="division">
</div>
<div class="info">
<div class="box">
<small>Cliente</small>
<strong>
<?php echo eNc($nota['cliente']); ?>
</strong>
<div class="muted">RTN: <?php echo eNc($nota['rtn']); ?>
</div>
</div>
<div class="box">
<small>Factura relacionada</small>
<strong>
<?php echo eNc($facturaNumero); ?>
</strong>
</div>
<div class="box">
<small>CAI Nota de Crédito</small>
<strong>
<?php echo eNc($nota['nc_cai']); ?>
</strong>
</div>
</div>
<div class="reason">
<strong>Motivo:</strong> <?php echo eNc($nota['motivo']); ?>
</div>
<div class="lines">
<div class="line headline">
<div>Concepto</div>
<div>Base</div>
<div>ISV 15%</div>
<div>ISV 18%</div>
<div>Total</div>
</div>
<?php foreach ($nota['detalle'] as $indice => $d): ?>
<div class="line">
<div>
<strong>
<?php echo (int)$indice + 1; ?>. <?php echo eNc($d['producto']); ?>
</strong>
</div>
<div class="money">L <?php echo number_format((float)$d['base_acreditada'],2); ?>
</div>
<div class="money">L <?php echo number_format((float)$d['isv15_acreditado'],2); ?>
</div>
<div class="money">L <?php echo number_format((float)$d['isv18_acreditado'],2); ?>
</div>
<div class="money">
<strong>L <?php echo number_format((float)$d['total_acreditado'],2); ?>
</strong>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="totals">
<div>
<span>Base acreditada</span>
<strong>L <?php echo number_format((float)$nota['base_acreditada'],2); ?>
</strong>
</div>
<div>
<span>ISV 15%</span>
<strong>L <?php echo number_format((float)$nota['isv15_acreditado'],2); ?>
</strong>
</div>
<div>
<span>ISV 18%</span>
<strong>L <?php echo number_format((float)$nota['isv18_acreditado'],2); ?>
</strong>
</div>
<div class="grand">
<span>Total Nota de Crédito</span>
<span>L <?php echo number_format((float)$nota['total_acreditado'],2); ?>
</span>
</div>
<?php if ((float)($nota['credito_favor'] ?? 0)>0): ?>
<div class="credit">
<span>Crédito a favor registrado</span>
<strong>L <?php echo number_format((float)$nota['credito_favor'],2); ?>
</strong>
</div>
<?php endif; ?>
</div>
<div class="foot">
<div>
<strong>
<?php echo eNc($nombreEmpresa !== '' ? $nombreEmpresa : 'Documento fiscal'); ?>
</strong>
<div>
<?php echo eNc($empresa['eslogan'] ?? ''); ?>
</div>
<div>Nota de Crédito vinculada a la factura <?php echo eNc($facturaNumero); ?>.</div>
</div>
<div class="foot-right">
<strong>Documento emitido por IZZY</strong>
<div>El crédito debe aplicarse o devolverse mediante un registro contable independiente.</div>
<div>Conserve este documento para sus registros.</div>
</div>
</div>

</div>
</body>
</html>

<?php
// Generar el PDF real con la dependencia Dompdf ya instalada en IZZY.
if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
    $htmlNc = ob_get_clean();
    // Dompdf interpreta @page antes de las clases del body: definirlo según el formato activo.
    if ($formatoNc === 'ticket') {
        $htmlNc = str_replace('@page { size: letter portrait; margin: 12mm 12mm; }',
            '@page { size: 80mm 300mm; margin: 5mm 6mm; }', $htmlNc);
    }
    require_once __DIR__ . '/../dompdf/vendor/autoload.php';
    $opcionesNc = new \Dompdf\Options();
    $opcionesNc->set('isRemoteEnabled', false);
    $opcionesNc->set('isHtml5ParserEnabled', true);
    $dompdfNc = new \Dompdf\Dompdf($opcionesNc);
    $dompdfNc->loadHtml($htmlNc, 'UTF-8');
    // Ticket conserva su configuración; carta usa márgenes laterales de 12 mm.
    $dompdfNc->setPaper($formatoNc === 'ticket' ? [0, 0, 226.77, 850.39] : 'letter', 'portrait');
    // El tamaño físico se configura en Dompdf, no solo con CSS.
    $dompdfNc->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Nota_de_Credito.pdf"');
    echo $dompdfNc->output();
}
