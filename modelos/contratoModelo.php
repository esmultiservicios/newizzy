<?php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class contratoModelo extends mainModel {

    protected function agregar_contrato_modelo($datos) {
        $conn = mainModel::connection();
        $contrato_id = mainModel::correlativo("contrato_id", "contrato");
    
        $sql = $conn->prepare("INSERT INTO contrato (
            contrato_id, colaborador_id, tipo_contrato_id, pago_planificado_id,
            tipo_empleado_id, salario_mensual, salario, fecha_inicio, fecha_fin,
            notas, usuario, estado, fecha_registro, semanal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
        $sql->bind_param("ssssssssssssss",
            $contrato_id,
            $datos['colaborador_id'],
            $datos['tipo_contrato_id'],
            $datos['pago_planificado_id'],
            $datos['tipo_empleado_id'],
            $datos['salario_mensual'],
            $datos['salario'],
            $datos['fecha_inicio'],
            $datos['fecha_fin'],
            $datos['notas'],
            $datos['usuario'],
            $datos['estado'],
            $datos['fecha_registro'],
            $datos['semanal']
        );
    
        $result = $sql->execute();
        $sql->close();
        return $result;
    }

    protected function valid_contrato_modelo($colaborador_id) {
        $conn = mainModel::connection();

        $sql = $conn->prepare("SELECT contrato_id FROM contrato WHERE colaborador_id = ? AND estado = 1");
        $sql->bind_param("s", $colaborador_id);
        $sql->execute();

        return $sql->get_result();
    }

    protected function edit_contrato_modelo($datos) {
        $conn = mainModel::connection();

        $sql = $conn->prepare("UPDATE contrato SET
            salario = ?,
            fecha_fin = ?,
            notas = ?,
            estado = ?
            WHERE contrato_id = ?");

        $sql->bind_param("sssss",
            $datos['salario'],
            $datos['fecha_fin'],
            $datos['notas'],
            $datos['estado'],
            $datos['contrato_id']
        );

        $sql->execute();
        return $sql;
    }

    protected function delete_contrato_modelo($contrato_id) {
        $conn = mainModel::connection();

        $sql = $conn->prepare("DELETE FROM contrato WHERE contrato_id = ?");
        $sql->bind_param("s", $contrato_id);
        $sql->execute();

        return $sql;
    }

    protected function valid_contrato_nomina_modelo($contrato_id) {
        $conn = mainModel::connection();

        $sql = $conn->prepare("SELECT c.contrato_id
            FROM contrato AS c
            INNER JOIN nomina_detalles AS nd ON c.colaborador_id = nd.colaboradores_id
            WHERE c.contrato_id = ?");
        $sql->bind_param("s", $contrato_id);
        $sql->execute();

        return $sql->get_result();
    }

    protected function getTotalContratosRegistrados() {
        try {
            $conn = $this->connection();
            $sql = $conn->prepare("SELECT COUNT(contrato_id) AS total FROM contrato WHERE estado = 1");
            $sql->execute();
            $resultado = $sql->get_result();
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
        } catch (Exception $e) {
            error_log("Error en getTotalContratosRegistrados: " . $e->getMessage());
            return 0;
        }
    }
}
