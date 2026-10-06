<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$contexto = isset($_POST['contexto']) ? trim((string)$_POST['contexto']) : '';

/*
 * El selector del Reporte de Ventas necesita un alcance distinto al selector
 * usado por Facturación. Se mantiene el comportamiento histórico para todos
 * los demás consumidores de este endpoint.
 */
if ($contexto === 'reporte_ventas') {
    $empresaId = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
    $privilegioId = isset($_SESSION['privilegio_sd']) ? (int)$_SESSION['privilegio_sd'] : 0;
    $colaboradorId = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

    $cn = $insMainModel->connection();
    $puedeVerTodosFacturadores = in_array($privilegioId, [1, 2], true);

    /*
     * Contador también tiene acceso global al reporte. Se identifica por el
     * nombre del privilegio para no depender de un ID fijo entre instalaciones.
     */
    if (!$puedeVerTodosFacturadores && $privilegioId > 0) {
        $stmtPrivilegio = $cn->prepare("SELECT nombre FROM privilegio WHERE privilegio_id = ? LIMIT 1");

        if ($stmtPrivilegio) {
            $stmtPrivilegio->bind_param('i', $privilegioId);
            $stmtPrivilegio->execute();
            $resultadoPrivilegio = $stmtPrivilegio->get_result();

            if ($filaPrivilegio = $resultadoPrivilegio->fetch_assoc()) {
                $nombrePrivilegio = function_exists('mb_strtolower')
                    ? mb_strtolower(trim((string)$filaPrivilegio['nombre']), 'UTF-8')
                    : strtolower(trim((string)$filaPrivilegio['nombre']));

                $puedeVerTodosFacturadores = ($nombrePrivilegio === 'contador');
            }

            $resultadoPrivilegio->free();
            $stmtPrivilegio->close();
        }
    }

    if ($puedeVerTodosFacturadores) {
        echo '<option value="">Todos los facturadores</option>';

        $stmt = $cn->prepare("
            SELECT DISTINCT c.colaboradores_id, c.nombre
            FROM facturas f
            INNER JOIN colaboradores c ON c.colaboradores_id = f.usuario
            WHERE f.empresa_id = ?
            ORDER BY c.nombre ASC
        ");

        if ($stmt) {
            $stmt->bind_param('i', $empresaId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo '<option value="'.(int)$row['colaboradores_id'].'">'.htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8').'</option>';
            }

            $result->free();
            $stmt->close();
        }
    } else {
        // Los demás roles solo pueden consultar su propio facturador.
        $stmt = $cn->prepare("
            SELECT c.colaboradores_id, c.nombre
            FROM colaboradores c
            WHERE c.colaboradores_id = ? AND c.empresa_id = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param('ii', $colaboradorId, $empresaId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo '<option value="'.(int)$row['colaboradores_id'].'" selected>'.htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8').'</option>';
            } else {
                echo '<option value="">Sin facturador disponible</option>';
            }

            $result->free();
            $stmt->close();
        } else {
            echo '<option value="">Sin facturador disponible</option>';
        }
    }

    exit;
}

$result = $insMainModel->getFacturador();

if ($result->num_rows > 0) {
    while ($consulta2 = $result->fetch_assoc()) {
        echo '<option value="'.(int)$consulta2['colaboradores_id'].'">'.htmlspecialchars($consulta2['nombre'], ENT_QUOTES, 'UTF-8').'</option>';
    }
} else {
    echo '<option value="">No hay datos que mostrar</option>';
}
