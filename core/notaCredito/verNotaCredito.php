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
function eNc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$facturaNumero = trim((string)$nota['factura_prefijo']) . str_pad((string)$nota['factura_number'], (int)$nota['factura_relleno'], '0', STR_PAD_LEFT);
$empresa = [];
try {
    $conexion = mainModel::connection();
    $empresaId = (int)($_SESSION['empresa_id_sd'] ?? $_SESSION['empresa_id'] ?? 0);
    if ($empresaId <= 0 && isset($nota['empresa_id'])) { $empresaId = (int)$nota['empresa_id']; }
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
$nombreEmpresa = trim((string)($empresa['nombre'] ?? $empresa['razon_social'] ?? ''));
$logoUrl = '';
$logoArchivo = trim((string)($empresa['logotipo'] ?? ''));
// No convertir una ruta de base de datos arbitraria en recurso remoto.
if ($logoArchivo !== '' && !preg_match('/^(?:https?:|data:|\/\/)/i', $logoArchivo)) {
    $logoArchivo = ltrim(str_replace('\\', '/', $logoArchivo), '/');
    if (strpos($logoArchivo, '..') === false) {
        $archivoReal = dirname(__DIR__, 2) . '/' . $logoArchivo;
        if (is_file($archivoReal)) {
            $logoUrl = rtrim((string)SERVERURL, '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $logoArchivo)));
        }
    }
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nota de Crédito <?php echo eNc($nota['numero_completo']); ?></title>
<style>
*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;color:#10264c;background:#edf2f7;margin:0;padding:28px 14px;font-size:13px}
.sheet{max-width:900px;margin:auto;background:#fff;border:1px solid #dce6ee;border-radius:13px;overflow:hidden;box-shadow:0 12px 36px #10264c13}
.brand{display:flex;justify-content:space-between;gap:18px;align-items:center;padding:25px 30px 19px;border-top:5px solid #0ba8ba}
.emisor{display:flex;align-items:center;gap:14px}.logo{width:82px;height:70px;object-fit:contain}.marca{font-size:19px;font-weight:800;letter-spacing:.3px}.emisor-meta{font-size:11px;color:#62748b;line-height:1.65;margin-top:5px}
.doc-id{text-align:right}.doc-id h1{font-size:21px;margin:0 0 6px;color:#10264c}.doc-id strong{font-size:16px}.muted{color:#65758e}.sub{font-size:11px;margin-top:5px}
.division{height:1px;background:#e1e8ef;margin:0 30px 17px}
.info{display:grid;grid-template-columns:repeat(3,1fr);gap:11px;padding:0 30px 18px}
.box{background:#f7f9fc;border:1px solid #e2e9f0;border-radius:9px;padding:12px;min-width:0}.box small{display:block;color:#70819a;font-size:10px;text-transform:uppercase;font-weight:800;margin-bottom:6px;letter-spacing:.3px}.box strong{overflow-wrap:anywhere}
.reason{margin:0 30px 18px;padding:12px 14px;background:#fff8e8;border-left:4px solid #eaa21a;border-radius:5px}
.lines{padding:0 30px 18px}.line{display:grid;grid-template-columns:2fr repeat(4,1fr);gap:8px;border-bottom:1px solid #e2e9ef;padding:12px 8px;align-items:center}.line.headline{font-weight:800;background:#10264c;color:#fff;border-radius:6px;padding:12px 8px}.money{text-align:right;white-space:nowrap}.totals{margin:0 30px 24px auto;max-width:390px}.totals div{display:flex;justify-content:space-between;gap:15px;padding:7px 0}.totals .grand{border-top:2px solid #10264c;font-size:15px;font-weight:800;margin-top:3px;padding-top:13px}.credit{color:#078c61;font-weight:700}
.foot{margin:0 30px;padding:17px 0 20px;border-top:1px solid #dde5ed;display:flex;justify-content:space-between;gap:25px;line-height:1.6;font-size:11px;color:#65758e}.foot strong{color:#10264c}.foot .foot-right{text-align:right}
.actions{padding:15px 30px;background:#f6f8fb;text-align:right}.actions button{background:#10264c;color:#fff;border:0;border-radius:7px;padding:11px 17px;cursor:pointer;font-weight:700}
@media(max-width:700px){body{padding:8px}.brand{padding:19px;flex-direction:column;align-items:flex-start}.doc-id{text-align:left}.division{margin:0 19px 15px}.info{grid-template-columns:1fr;padding:0 19px 18px}.reason{margin:0 19px 17px}.lines{padding:0 19px 17px}.line{grid-template-columns:1fr 1fr;gap:7px}.line>div:first-child{grid-column:1/-1}.line.headline{display:none}.money{text-align:left}.totals{margin:0 19px 22px}.foot{margin:0 19px;flex-direction:column;gap:5px}.foot .foot-right{text-align:left}}
@media print{@page{size:auto;margin:12mm}body{background:#fff;padding:0;-webkit-print-color-adjust:exact;print-color-adjust:exact}.sheet{max-width:none;border:0;border-radius:0;box-shadow:none}.actions{display:none}.line,.box,.reason,.totals{break-inside:avoid}}
</style></head><body><div class="sheet">
<div class="brand"><div class="emisor">
<?php if ($logoUrl !== ''): ?><img class="logo" src="<?php echo eNc($logoUrl); ?>" alt="Logotipo de la empresa"><?php endif; ?>
<div><div class="marca"><?php echo eNc($nombreEmpresa !== '' ? $nombreEmpresa : 'EMPRESA EMISORA'); ?></div><div class="emisor-meta">
<?php if (!empty($empresa['razon_social']) && $empresa['razon_social'] !== $nombreEmpresa): ?><?php echo eNc($empresa['razon_social']); ?><br><?php endif; ?>
<?php if (!empty($empresa['rtn'])): ?>RTN: <?php echo eNc($empresa['rtn']); ?><br><?php endif; ?>
<?php if (!empty($empresa['ubicacion'])): ?><?php echo eNc($empresa['ubicacion']); ?><br><?php endif; ?>
<?php echo eNc(implode(' · ', array_filter([$empresa['telefono'] ?? '',$empresa['celular'] ?? '',$empresa['correo'] ?? '']))); ?>
</div></div></div><div class="doc-id"><h1>NOTA DE CRÉDITO</h1><strong><?php echo eNc($nota['numero_completo']); ?></strong><div class="sub muted">Fecha de emisión: <?php echo eNc($nota['fecha']); ?></div><div class="sub muted">Documento complementario de factura</div></div></div>
<div class="division"></div>
<div class="info"><div class="box"><small>Cliente</small><strong><?php echo eNc($nota['cliente']); ?></strong><div class="muted">RTN: <?php echo eNc($nota['rtn']); ?></div></div><div class="box"><small>Factura relacionada</small><strong><?php echo eNc($facturaNumero); ?></strong></div><div class="box"><small>CAI Nota de Crédito</small><strong><?php echo eNc($nota['nc_cai']); ?></strong></div></div>
<div class="reason"><strong>Motivo:</strong> <?php echo eNc($nota['motivo']); ?></div>
<div class="lines"><div class="line headline"><div>Concepto</div><div>Base</div><div>ISV 15%</div><div>ISV 18%</div><div>Total</div></div>
<?php foreach ($nota['detalle'] as $d): ?><div class="line"><div><strong><?php echo eNc($d['producto']); ?></strong></div><div class="money">L <?php echo number_format((float)$d['base_acreditada'],2); ?></div><div class="money">L <?php echo number_format((float)$d['isv15_acreditado'],2); ?></div><div class="money">L <?php echo number_format((float)$d['isv18_acreditado'],2); ?></div><div class="money"><strong>L <?php echo number_format((float)$d['total_acreditado'],2); ?></strong></div></div><?php endforeach; ?></div>
<div class="totals"><div><span>Base acreditada</span><strong>L <?php echo number_format((float)$nota['base_acreditada'],2); ?></strong></div><div><span>ISV 15%</span><strong>L <?php echo number_format((float)$nota['isv15_acreditado'],2); ?></strong></div><div><span>ISV 18%</span><strong>L <?php echo number_format((float)$nota['isv18_acreditado'],2); ?></strong></div><div class="grand"><span>Total Nota de Crédito</span><span>L <?php echo number_format((float)$nota['total_acreditado'],2); ?></span></div><?php if ((float)($nota['credito_favor'] ?? 0)>0): ?><div class="credit"><span>Crédito a favor registrado</span><strong>L <?php echo number_format((float)$nota['credito_favor'],2); ?></strong></div><?php endif; ?></div>
<div class="foot"><div><strong><?php echo eNc($nombreEmpresa !== '' ? $nombreEmpresa : 'Documento fiscal'); ?></strong><div><?php echo eNc($empresa['eslogan'] ?? ''); ?></div><div>Nota de Crédito vinculada a la factura <?php echo eNc($facturaNumero); ?>.</div></div><div class="foot-right"><strong>Documento emitido por IZZY</strong><div>El crédito debe aplicarse o devolverse mediante un registro contable independiente.</div><div>Conserve este documento para sus registros.</div></div></div>
<div class="actions"><button type="button" onclick="window.print()">Imprimir / Guardar PDF</button></div>
</div></body></html>
