<?php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class contratoModelo extends mainModel
{
    private $ultimoErrorContrato = '';

    protected function ultimo_error_contrato_modelo()
    {
        return $this->ultimoErrorContrato;
    }

    private function setErrorContrato($mensaje)
    {
        $this->ultimoErrorContrato = trim((string)$mensaje);
    }

    protected function agregar_contrato_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorContrato('');

        $contrato_id = (int)$this->correlativo("contrato_id", "contrato");

        $sql = $conn->prepare(
            "INSERT INTO contrato
            (contrato_id, colaborador_id, tipo_contrato_id, pago_planificado_id,
             tipo_empleado_id, salario_mensual, salario, fecha_inicio, fecha_fin,
             notas, usuario, estado, fecha_registro, semanal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$sql) {
            $this->setErrorContrato($conn->error);
            return false;
        }

        $sql->bind_param(
            "iiiiiddsssiisi",
            $contrato_id, $colaborador_id, $tipo_contrato_id, $pago_planificado_id,
            $tipo_empleado_id, $salario_mensual, $salario, $fecha_inicio, $fecha_fin,
            $notas, $usuario, $estado, $fecha_registro, $semanal
        );

        if (!$sql->execute()) {
            $this->setErrorContrato($sql->error);
            $sql->close();
            return false;
        }

        $ok = $sql->affected_rows > 0;
        $sql->close();
        return $ok;
    }

    protected function valid_contrato_modelo($colaborador_id, $excepto_contrato_id = 0)
    {
        $conn = $this->connection();
        $colaborador_id = (int)$colaborador_id;
        $excepto_contrato_id = (int)$excepto_contrato_id;

        if ($excepto_contrato_id > 0) {
            $sql = $conn->prepare(
                "SELECT contrato_id
                 FROM contrato
                 WHERE colaborador_id = ? AND estado = 1 AND contrato_id <> ?
                 LIMIT 1"
            );
            $sql->bind_param("ii", $colaborador_id, $excepto_contrato_id);
        } else {
            $sql = $conn->prepare(
                "SELECT contrato_id
                 FROM contrato
                 WHERE colaborador_id = ? AND estado = 1
                 LIMIT 1"
            );
            $sql->bind_param("i", $colaborador_id);
        }

        $sql->execute();
        return $sql->get_result();
    }

    protected function edit_contrato_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorContrato('');

        $sql = $conn->prepare(
            "UPDATE contrato SET
                colaborador_id = ?,
                tipo_contrato_id = ?,
                pago_planificado_id = ?,
                tipo_empleado_id = ?,
                salario_mensual = ?,
                salario = ?,
                fecha_inicio = ?,
                fecha_fin = ?,
                notas = ?,
                estado = ?,
                semanal = ?
             WHERE contrato_id = ?"
        );

        if (!$sql) {
            $this->setErrorContrato($conn->error);
            return false;
        }

        $contrato_id = (int)$datos['contrato_id'];
        $colaborador_id = (int)$datos['colaborador_id'];
        $tipo_contrato_id = (int)$datos['tipo_contrato_id'];
        $pago_planificado_id = (int)$datos['pago_planificado_id'];
        $tipo_empleado_id = (int)$datos['tipo_empleado_id'];
        $salario_mensual = (float)$datos['salario_mensual'];
        $salario = (float)$datos['salario'];
        $fecha_inicio = (string)$datos['fecha_inicio'];
        $fecha_fin = (string)$datos['fecha_fin'];
        $notas = (string)$datos['notas'];
        $estado = (int)$datos['estado'];
        $semanal = (int)$datos['semanal'];

        $sql->bind_param(
            "iiiiddsssiii",
            $colaborador_id, $tipo_contrato_id, $pago_planificado_id, $tipo_empleado_id,
            $salario_mensual, $salario, $fecha_inicio, $fecha_fin, $notas,
            $estado, $semanal, $contrato_id
        );

        if (!$sql->execute()) {
            $this->setErrorContrato($sql->error);
            $sql->close();
            return false;
        }

        $sql->close();
        return true;
    }

    protected function delete_contrato_modelo($contrato_id)
    {
        $conn = $this->connection();
        $sql = $conn->prepare("DELETE FROM contrato WHERE contrato_id = ?");
        if (!$sql) {
            $this->setErrorContrato($conn->error);
            return false;
        }
        $contrato_id = (int)$contrato_id;
        $sql->bind_param("i", $contrato_id);
        $ok = $sql->execute();
        if (!$ok) {
            $this->setErrorContrato($sql->error);
        }
        $sql->close();
        return $ok;
    }

    protected function valid_contrato_nomina_modelo($contrato_id)
    {
        $conn = $this->connection();
        $sql = $conn->prepare(
            "SELECT c.contrato_id
             FROM contrato AS c
             INNER JOIN nomina_detalles AS nd ON c.colaborador_id = nd.colaboradores_id
             WHERE c.contrato_id = ?
             LIMIT 1"
        );
        $contrato_id = (int)$contrato_id;
        $sql->bind_param("i", $contrato_id);
        $sql->execute();
        return $sql->get_result();
    }

    protected function getTotalContratosRegistrados()
    {
        try {
            $conn = $this->connection();
            $sql = $conn->prepare("SELECT COUNT(contrato_id) AS total FROM contrato WHERE estado = 1");
            $sql->execute();
            $resultado = $sql->get_result();
            $fila = $resultado->fetch_assoc();
            $sql->close();
            return (int)($fila['total'] ?? 0);
        } catch (Throwable $e) {
            error_log("IZZY getTotalContratosRegistrados: " . $e->getMessage());
            return 0;
        }
    }
}
