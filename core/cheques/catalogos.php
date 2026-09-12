<?php
require_once __DIR__ . '/_bootstrap.php';

$sesion = chequesSesion();
$db = chequesDb();
chequesValidarInstalacion($db);

$proveedores = [];
$res = $db->query("
    SELECT proveedores_id, nombre
    FROM proveedores
    WHERE estado = 1
    ORDER BY nombre ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $proveedores[] = [
            'proveedores_id' => (int)$row['proveedores_id'],
            'nombre' => $row['nombre']
        ];
    }
}

$categorias = [];
$res = $db->query("
    SELECT categoria_gastos_id, nombre, es_inversion
    FROM categoria_gastos
    WHERE estado = 1
    ORDER BY nombre ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $categorias[] = [
            'categoria_gastos_id' => (int)$row['categoria_gastos_id'],
            'nombre' => $row['nombre'],
            'es_inversion' => isset($row['es_inversion']) ? (int)$row['es_inversion'] : 0
        ];
    }
}

$cuentas = [];
$res = $db->query("
    SELECT cuentas_id, codigo, nombre, estado, es_inversion
    FROM cuentas
    WHERE estado = 1
    ORDER BY nombre ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cuentaId = (int)$row['cuentas_id'];
        $cuentas[] = [
            'cuentas_id' => $cuentaId,
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'es_inversion' => isset($row['es_inversion']) ? (int)$row['es_inversion'] : 0,
            'saldo' => chequesObtenerSaldoActual($db, $cuentaId, $sesion['empresa_id'])
        ];
    }
}

$cuentaCheque = chequesCuentaTipoPago($db, 4, $sesion['empresa_id']);

chequesJson([
    'success' => true,
    'proveedores' => $proveedores,
    'categorias' => $categorias,
    'cuentas' => $cuentas,
    'cuenta_cheque' => $cuentaCheque
]);
