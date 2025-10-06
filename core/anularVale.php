<?php
// core/anularVale.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');
ob_start();

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

function json_out($arr){
    $garbage = trim(ob_get_clean());
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1) Validar sesión
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    json_out([
        "status"   => "unauthorized",
        "title"    => "Error de sesión",
        "message"  => $validacion['mensaje'],
        "redirect" => $validacion['redireccion'] ?? null
    ]);
}

// 2) Entrada
$vale_id = isset($_POST['vale_id']) ? (int)$_POST['vale_id'] : 0;
if($vale_id <= 0){
    json_out([
        "status"  => "error",
        "title"   => "Falta ID",
        "message" => "No se pudo identificar el vale a anular."
    ]);
}

// 3) Conexión
try{
    $cn = method_exists('mainModel','staticConnection') ? mainModel::staticConnection() : (new mainModel())->connection();
    if(!($cn instanceof mysqli)) throw new Exception('Conexión inválida.');
}catch(Throwable $e){
    json_out([
        "status"  => "error",
        "title"   => "Conexión",
        "message" => "No fue posible conectar con la base de datos: ".$e->getMessage()
    ]);
}

/*
   Regla solicitada:
   - El vale SOLO puede anularse si NO está asociado a una nómina confirmada (nomina.estado = 1).
   Asumimos tabla `vales` con campos: vale_id, nomina_id, estado (según tu backend).
*/
$nomina_id = null; $nomina_estado = null;
$stmt = $cn->prepare("
    SELECT v.nomina_id, n.estado AS nomina_estado
      FROM vales v
 LEFT JOIN nomina n ON n.nomina_id = v.nomina_id
     WHERE v.vale_id = ?
     LIMIT 1
");
$stmt->bind_param("i", $vale_id);
$stmt->execute();
$stmt->bind_result($nomina_id_db, $nomina_estado_db);
if($stmt->fetch()){
    $nomina_id = $nomina_id_db;
    $nomina_estado = $nomina_estado_db;
}
$stmt->close();

if($nomina_id !== null && (int)$nomina_estado === 1){
    json_out([
        "status"  => "error",
        "title"   => "No permitido",
        "message" => "El vale está ligado a una nómina confirmada y no puede anularse."
    ]);
}

// 4) Ejecutar anulación usando tu modelo (mantiene tu lógica interna)
$ok = false;
try{
    // Si tienes un método que ya maneja estados/auditoría, úsalo:
    if (method_exists($insMainModel, 'anular_vale')) {
        $ok = (bool)$insMainModel->anular_vale($vale_id);
    } else {
        // Fallback genérico (ajusta si tu regla de 'anulado' es distinta)
        $stmtU = $cn->prepare("UPDATE vales SET estado = 0 WHERE vale_id = ? LIMIT 1");
        $stmtU->bind_param("i", $vale_id);
        $ok = $stmtU->execute() && ($stmtU->affected_rows > 0);
        $stmtU->close();
    }
}catch(Throwable $e){
    json_out([
        "status"  => "error",
        "title"   => "Error",
        "message" => "No se pudo anular el vale: ".$e->getMessage()
    ]);
}

if($ok){
    json_out([
        "status"  => "success",
        "title"   => "Éxito",
        "message" => "El vale ha sido anulado correctamente."
    ]);
} else {
    json_out([
        "status"  => "error",
        "title"   => "Error",
        "message" => "Lo sentimos, no se pudo anular el vale."
    ]);
}