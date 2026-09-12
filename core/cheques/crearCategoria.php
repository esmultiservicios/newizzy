<?php
require_once __DIR__ . '/_bootstrap.php';

chequesSoloPost();
$sesion = chequesSesion();
$db = chequesDb();

$nombre = chequesTexto($_POST['nombre'] ?? '', 30);
if ($nombre === '') {
    chequesJson(['success'=>false,'title'=>'Nombre requerido','message'=>'Ingrese el nombre de la categoría.']);
}

$stmt = $db->prepare("SELECT categoria_gastos_id FROM categoria_gastos WHERE LOWER(nombre)=LOWER(?) LIMIT 1");
$stmt->bind_param('s', $nombre);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($existe) {
    chequesJson(['success'=>false,'title'=>'Categoría existente','message'=>'Ya existe una categoría con ese nombre.']);
}

try {
    $db->query("LOCK TABLES categoria_gastos WRITE");
    $id = chequesSiguienteId($db, 'categoria_gastos', 'categoria_gastos_id');
    $estado = 1;
    $esInversion = 0;
    $fecha = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        INSERT INTO categoria_gastos
            (categoria_gastos_id, nombre, estado, usuario, date_write, es_inversion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param('isiisi', $id, $nombre, $estado, $sesion['colaboradores_id'], $fecha, $esInversion);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    chequesDesbloquear($db);

    chequesJson([
        'success'=>true,
        'title'=>'Categoría creada',
        'message'=>'La categoría '.$nombre.' fue creada correctamente.',
        'categoria'=>['categoria_gastos_id'=>$id,'nombre'=>$nombre]
    ]);
} catch (Throwable $e) {
    chequesDesbloquear($db);
    chequesJson(['success'=>false,'title'=>'No se pudo crear','message'=>$e->getMessage()],500);
}
