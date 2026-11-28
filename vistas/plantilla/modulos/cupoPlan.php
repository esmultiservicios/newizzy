<?php
$quotaRoutes = [
    'facturas' => ['facturas', 'facturasModelo', 'getTotalFacturasRegistradas', 'Facturas del mes'],
    'productos' => ['productos', 'productosModelo', 'getTotalProductosRegistrados', 'Productos'],
    'clientes' => ['clientes', 'clientesModelo', 'getTotalClientesRegistrados', 'Clientes'],
    'empresa' => ['empresas', 'empresaModelo', 'getTotalEmpresasRegistradas', 'Empresas'],
    'users' => ['usuarios', 'usuarioModelo', 'getTotalUsuarios', 'Usuarios activos'],
    'proveedores' => ['proveedores', 'proveedoresModelo', 'getTotalProveedoresRegistrados', 'Proveedores'],
    'colaboradores' => ['colaboradores', 'colaboradorModelo', 'getTotalColaboradoresRegistrados', 'Colaboradores'],
    'confAlmacen' => ['almacenes', 'almacenModelo', 'getTotalAlmacenesRegistrados', 'Almacenes'],
    'confCategoria' => ['categorias', 'categoriaProductosModelo', 'getTotalCategoriasRegistrados', 'Categorías'],
    'confUbicacion' => ['ubicaciones', 'ubicacionModelo', 'getTotalUbicacionesRegistrados', 'Ubicaciones'],
    'facturaCompras' => ['compras', 'comprasModelo', 'getTotalComprasRegistradas', 'Compras'],
    'cotizacion' => ['cotizaciones', 'cotizacionModelo', 'getTotalCotizacionesRegistradas', 'Cotizaciones'],
    'contrato' => ['contratos', 'contratoModelo', 'getTotalContratosRegistrados', 'Contratos'],
    'cuentasContabilidad' => ['cuentas', 'cuentaContabilidadModelo', 'getTotalCuentasRegistradas', 'Cuentas contables'],
    'ingresosContabilidad' => ['ingresos', 'ingresosContabilidadModelo', 'getTotalIngresosRegistrados', 'Ingresos'],
    'gastosContabilidad' => ['egresos', 'egresosContabilidadModelo', 'getTotalEgresosRegistrados', 'Egresos'],
    'secuencia' => ['secuencias', 'secuenciaFacturacionModelo', 'getTotalSecuenciaRegistradas', 'Secuencias'],
    'confTipoPago' => ['tipo_pago', 'tipoPagoModelo', 'getTotalTipoPagoRegistrados', 'Tipos de pago'],
];
$quotaRoute = $ruta[0] ?? '';
if (!isset($quotaRoutes[$quotaRoute]) || basename($vistasR) !== $quotaRoute.'-view.php') {
    return;
}
[$quotaKey, $quotaModel, $quotaMethod, $quotaLabel] = $quotaRoutes[$quotaRoute];
$quotaConfig = (new mainModel())->getPlanConfiguracionMainModel();
if (!isset($quotaConfig[$quotaKey])) {
    return;
}
require_once __DIR__.'/../../../modelos/'.$quotaModel.'.php';
$quotaCounter = new $quotaModel();
$quotaUsed = (int)$quotaCounter->$quotaMethod();
$quotaLimit = max(0, (int)$quotaConfig[$quotaKey]);
if ($quotaKey === 'usuarios') {
    $quotaLimit += max(0, (int)$quotaCounter->getTotalUsuariosExtras());
}
?>
<div class="px-3 pt-3 d-flex flex-wrap align-items-center" role="status">
    <span class="badge badge-pill <?= $quotaUsed >= $quotaLimit ? 'badge-warning' : 'badge-info' ?> p-2 mr-2">
        <?= htmlspecialchars($quotaLabel, ENT_QUOTES, 'UTF-8') ?> · <?= $quotaUsed ?> / <?= $quotaLimit ?> usados
    </span>
    <small><?= max(0, $quotaLimit - $quotaUsed) ?> disponibles según el plan</small>
</div>
