<?php
// Ubicación: core/notaCredito/verNotaCredito.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/../../controladores/notaCreditoControlador.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SD');
    session_start();
}


$mainModel = new mainModel();
$validacion = $mainModel->validarSesion();

if (!empty($validacion['error'])) {
    http_response_code(401);
    exit('Sesión inválida.');
}

try {
    $notaId = (int)($_GET['nota_credito_id'] ?? 0);
    $ctrl = new notaCreditoControlador();
    $nota = $ctrl->obtenerNotaPorId($notaId);
} catch (Throwable $e) {
    http_response_code(400);
    exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

function eNc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$facturaNumero = trim((string)$nota['factura_prefijo']) .
    str_pad((string)$nota['factura_number'], (int)$nota['factura_relleno'], '0', STR_PAD_LEFT);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nota de Crédito <?php echo eNc($nota['numero_completo']); ?></title>
<style>
body{font-family:Arial,sans-serif;color:#172b4d;background:#eef2f6;margin:0;padding:24px}
.sheet{max-width:900px;margin:auto;background:#fff;border:1px solid #dfe4ea;border-radius:12px;overflow:hidden}
.head{padding:24px 28px;border-top:5px solid #0ea5a8;display:flex;justify-content:space-between;gap:20px}
.head h1{margin:0;font-size:24px}.muted{color:#6b778c}.num{font-size:18px;font-weight:700;text-align:right}
.info{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:0 28px 20px}
.box{background:#f7f9fc;border:1px solid #e2e7ed;border-radius:8px;padding:12px}.box small{display:block;color:#6b778c;font-weight:700;text-transform:uppercase;font-size:11px;margin-bottom:5px}
.reason{margin:0 28px 20px;padding:14px;background:#fff8e6;border-left:4px solid #f5a623;border-radius:6px}
.lines{padding:0 28px 22px}.line{display:grid;grid-template-columns:2fr repeat(4,1fr);gap:8px;border-bottom:1px solid #e6eaef;padding:10px 0;align-items:center}.line.headline{font-weight:700;background:#172b4d;color:#fff;padding:10px;border-radius:6px}
.money{text-align:right}.totals{margin:0 28px 28px auto;width:360px}.totals div{display:flex;justify-content:space-between;padding:7px 0}.totals .grand{font-size:18px;font-weight:700;border-top:2px solid #172b4d;margin-top:4px;padding-top:12px}
.actions{padding:18px 28px;background:#f7f9fc;text-align:right}.actions button{background:#172b4d;color:#fff;border:0;border-radius:6px;padding:10px 18px;cursor:pointer}
@media(max-width:700px){body{padding:8px}.head{flex-direction:column}.num{text-align:left}.info{grid-template-columns:1fr}.line{grid-template-columns:1fr}.line.headline{display:none}.money{text-align:left}.totals{width:auto;margin:0 28px 28px}}
@media print{body{background:#fff;padding:0}.sheet{border:0;max-width:none}.actions{display:none}}
</style>
</head>
<body>
<div class="sheet">
  <div class="head">
    <div>
      <h1>NOTA DE CRÉDITO</h1>
      <div class="muted">Documento complementario de factura emitida</div>
    </div>
    <div class="num">
      <?php echo eNc($nota['numero_completo']); ?><br>
      <small class="muted"><?php echo eNc($nota['fecha']); ?></small>
    </div>
  </div>

  <div class="info">
    <div class="box"><small>Cliente</small><strong><?php echo eNc($nota['cliente']); ?></strong><br><span class="muted"><?php echo eNc($nota['rtn']); ?></span></div>
    <div class="box"><small>Factura relacionada</small><strong><?php echo eNc($facturaNumero); ?></strong></div>
    <div class="box"><small>CAI factura</small><strong><?php echo eNc($nota['cai']); ?></strong></div>
  </div>

  <div class="reason"><strong>Motivo:</strong> <?php echo eNc($nota['motivo']); ?></div>

  <div class="lines">
    <div class="line headline"><div>Concepto</div><div>Base</div><div>ISV 15%</div><div>ISV 18%</div><div>Total</div></div>
    <?php foreach ($nota['detalle'] as $d): ?>
    <div class="line">
      <div><strong><?php echo eNc($d['producto']); ?></strong></div>
      <div class="money">L <?php echo number_format((float)$d['base_acreditada'],2); ?></div>
      <div class="money">L <?php echo number_format((float)$d['isv15_acreditado'],2); ?></div>
      <div class="money">L <?php echo number_format((float)$d['isv18_acreditado'],2); ?></div>
      <div class="money"><strong>L <?php echo number_format((float)$d['total_acreditado'],2); ?></strong></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="totals">
    <div><span>Base acreditada</span><strong>L <?php echo number_format((float)$nota['base_acreditada'],2); ?></strong></div>
    <div><span>ISV 15%</span><strong>L <?php echo number_format((float)$nota['isv15_acreditado'],2); ?></strong></div>
    <div><span>ISV 18%</span><strong>L <?php echo number_format((float)$nota['isv18_acreditado'],2); ?></strong></div>
    <div class="grand"><span>Total Nota de Crédito</span><span>L <?php echo number_format((float)$nota['total_acreditado'],2); ?></span></div>
  </div>

  <div class="actions"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
</div>
</body>
</html>
