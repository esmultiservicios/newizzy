<?php
// nominaModelo.php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class nominaModelo extends mainModel
{
    private $ultimoErrorNomina = '';

    protected function ultimo_error_nomina_modelo()
    {
        return $this->ultimoErrorNomina;
    }

    private function setErrorNomina($mensaje)
    {
        $this->ultimoErrorNomina = trim((string)$mensaje);
    }

    private function numero($valor)
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        $valor = str_replace(',', '', trim((string)$valor));
        return is_numeric($valor) ? (float)$valor : 0.0;
    }

    /*=========================================================
     * INSERTS
     *=========================================================*/

    protected function agregar_nomina_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorNomina('');

        $nomina_id = (int)$this->correlativo("nomina_id", "nomina");
        $tipo_nomina_id = (int)($datos['tipo_nomina_id'] ?? $datos['tipo_nomina'] ?? 0);

        $empresa_id = (int)($datos['empresa_id'] ?? 0);
        $pago_planificado_id = (int)($datos['pago_planificado_id'] ?? 0);
        $fecha_inicio = (string)($datos['fecha_inicio'] ?? '');
        $fecha_fin = (string)($datos['fecha_fin'] ?? '');
        $detalle = (string)($datos['detalle'] ?? '');
        $importe = $this->numero($datos['importe'] ?? 0);
        $notas = (string)($datos['notas'] ?? '');
        $usuario = (int)($datos['usuario'] ?? 0);
        $estado = (int)($datos['estado'] ?? 0);
        $fecha_registro = (string)($datos['fecha_registro'] ?? date('Y-m-d H:i:s'));
        $cuentas_id = (int)($datos['cuentas_id'] ?? 0);

        $sql = $conn->prepare(
            "INSERT INTO nomina
            (nomina_id, empresa_id, pago_planificado_id, tipo_nomina_id, fecha_inicio, fecha_fin, detalle, importe, notas, usuario, estado, fecha_registro, cuentas_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }

        $sql->bind_param(
            "iiiisssdsiisi",
            $nomina_id,
            $empresa_id,
            $pago_planificado_id,
            $tipo_nomina_id,
            $fecha_inicio,
            $fecha_fin,
            $detalle,
            $importe,
            $notas,
            $usuario,
            $estado,
            $fecha_registro,
            $cuentas_id
        );

        if (!$sql->execute()) {
            $this->setErrorNomina($sql->error);
            $sql->close();
            return false;
        }

        $ok = $sql->affected_rows > 0;
        $sql->close();

        return $ok ? $nomina_id : false;
    }

    protected function agregar_nomina_detalles_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorNomina('');

        $nomina_detalles_id = (int)$this->correlativo("nomina_detalles_id", "nomina_detalles");

        $nomina_id = (int)($datos['nomina_id'] ?? 0);
        $colaboradores_id = (int)($datos['colaboradores_id'] ?? 0);
        $salario_mensual = $this->numero($datos['salario_mensual'] ?? 0);
        $dias_trabajados = $this->numero($datos['dias_trabajados'] ?? 0);
        $hrse25 = $this->numero($datos['hrse25'] ?? 0);
        $hrse50 = $this->numero($datos['hrse50'] ?? 0);
        $hrse75 = $this->numero($datos['hrse75'] ?? 0);
        $hrse100 = $this->numero($datos['hrse100'] ?? 0);
        $retroactivo = $this->numero($datos['retroactivo'] ?? 0);
        $bono = $this->numero($datos['bono'] ?? 0);
        $otros_ingresos = $this->numero($datos['otros_ingresos'] ?? 0);
        $deducciones = $this->numero($datos['deducciones'] ?? 0);
        $prestamo = $this->numero($datos['prestamo'] ?? 0);
        $ihss = $this->numero($datos['ihss'] ?? 0);
        $rap = $this->numero($datos['rap'] ?? 0);
        $isr = $this->numero($datos['isr'] ?? 0);
        $vales = $this->numero($datos['vales'] ?? 0);
        $incapacidad_ihss = $this->numero($datos['incapacidad_ihss'] ?? 0);
        $neto_ingresos = $this->numero($datos['neto_ingresos'] ?? 0);
        $neto_egresos = $this->numero($datos['neto_egresos'] ?? 0);
        $neto = $this->numero($datos['neto'] ?? 0);
        $usuario = (int)($datos['usuario'] ?? 0);
        $estado = (int)($datos['estado'] ?? 0);
        $notas = (string)($datos['notas'] ?? '');
        $fecha_registro = (string)($datos['fecha_registro'] ?? date('Y-m-d H:i:s'));
        $hrse25_valor = $this->numero($datos['hrse25_valor'] ?? 0);
        $hrse50_valor = $this->numero($datos['hrse50_valor'] ?? 0);
        $hrse75_valor = $this->numero($datos['hrse75_valor'] ?? 0);
        $hrse100_valor = $this->numero($datos['hrse100_valor'] ?? 0);
        $salario = $this->numero($datos['salario'] ?? 0);

        if ($salario <= 0 && $salario_mensual > 0) {
            $salario = $salario_mensual;
        }

        $sql = $conn->prepare(
            "INSERT INTO nomina_detalles
            (nomina_detalles_id, nomina_id, colaboradores_id, salario_mensual, dias_trabajados,
             hrse25, hrse50, hrse75, hrse100, retroactivo, bono, otros_ingresos, deducciones,
             prestamo, ihss, rap, isr, vales, incapacidad_ihss, neto_ingresos, neto_egresos,
             neto, usuario, estado, notas, fecha_registro, hrse25_valor, hrse50_valor,
             hrse75_valor, hrse100_valor, salario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }

        $sql->bind_param(
            "iiidddddddddddddddddddiissddddd",
            $nomina_detalles_id,
            $nomina_id,
            $colaboradores_id,
            $salario_mensual,
            $dias_trabajados,
            $hrse25,
            $hrse50,
            $hrse75,
            $hrse100,
            $retroactivo,
            $bono,
            $otros_ingresos,
            $deducciones,
            $prestamo,
            $ihss,
            $rap,
            $isr,
            $vales,
            $incapacidad_ihss,
            $neto_ingresos,
            $neto_egresos,
            $neto,
            $usuario,
            $estado,
            $notas,
            $fecha_registro,
            $hrse25_valor,
            $hrse50_valor,
            $hrse75_valor,
            $hrse100_valor,
            $salario
        );

        if (!$sql->execute()) {
            $this->setErrorNomina($sql->error);
            $sql->close();
            return false;
        }

        $ok = $sql->affected_rows > 0;
        $sql->close();

        return $ok ? $nomina_detalles_id : false;
    }

    protected function agregar_vale_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorNomina('');

        $vale_id = (int)$this->correlativo("vale_id", "vale");
        $nomina_id = (int)($datos['nomina_id'] ?? 0);
        $colaboradores_id = (int)($datos['colaboradores_id'] ?? 0);
        $monto = $this->numero($datos['monto'] ?? 0);
        $fecha = (string)($datos['fecha'] ?? '');
        $nota = (string)($datos['nota'] ?? '');
        $usuario = (int)($datos['usuario'] ?? 0);
        $estado = (int)($datos['estado'] ?? 0);
        $empresa_id = (int)($datos['empresa_id'] ?? 0);
        $fecha_registro = (string)($datos['fecha_registro'] ?? date('Y-m-d H:i:s'));

        $sql = $conn->prepare(
            "INSERT INTO vale
            (vale_id, nomina_id, colaboradores_id, monto, fecha, nota, usuario, estado, empresa_id, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }

        $sql->bind_param(
            "iiidssiiis",
            $vale_id,
            $nomina_id,
            $colaboradores_id,
            $monto,
            $fecha,
            $nota,
            $usuario,
            $estado,
            $empresa_id,
            $fecha_registro
        );

        if (!$sql->execute()) {
            $this->setErrorNomina($sql->error);
            $sql->close();
            return false;
        }

        $ok = $sql->affected_rows > 0;
        $sql->close();

        return $ok ? $vale_id : false;
    }

    /*=========================================================
     * VALIDACIONES
     *=========================================================*/

    protected function valid_nomina_modelo($detalle)
    {
        $conn = $this->connection();
        $sql = $conn->prepare("SELECT nomina_id FROM nomina WHERE estado = 0 AND detalle = ? LIMIT 1");
        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }
        $sql->bind_param("s", $detalle);
        $sql->execute();
        return $sql->get_result();
    }

    protected function valid_vale_modelo($colaboradores_id)
    {
        $conn = $this->connection();
        $query = "SELECT vale_id FROM vale WHERE estado = 0 AND colaboradores_id = '" . (int)$colaboradores_id . "' LIMIT 1";
        return $conn->query($query);
    }

    protected function valid_nomina_detalles_modelo($nomina_id, $colaboradores_id)
    {
        $conn = $this->connection();
        $sql = $conn->prepare(
            "SELECT nomina_detalles_id
             FROM nomina_detalles
             WHERE estado = 0 AND nomina_id = ? AND colaboradores_id = ?
             LIMIT 1"
        );
        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }
        $nomina_id = (int)$nomina_id;
        $colaboradores_id = (int)$colaboradores_id;
        $sql->bind_param("ii", $nomina_id, $colaboradores_id);
        $sql->execute();
        return $sql->get_result();
    }

    protected function valid_nomina_detalles_delete_modelo($nomina_id)
    {
        $conn = $this->connection();
        $nomina_id = (int)$nomina_id;
        return $conn->query("SELECT nomina_detalles_id FROM nomina_detalles WHERE estado = 0 AND nomina_id = {$nomina_id} LIMIT 1");
    }

    /*=========================================================
     * UPDATES
     *=========================================================*/

    protected function edit_nomina_detalles_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorNomina('');

        $sql = $conn->prepare(
            "UPDATE nomina_detalles SET
                dias_trabajados = ?, hrse25 = ?, hrse50 = ?, hrse75 = ?, hrse100 = ?,
                retroactivo = ?, bono = ?, otros_ingresos = ?, deducciones = ?, prestamo = ?,
                ihss = ?, rap = ?, isr = ?, vales = ?, incapacidad_ihss = ?, neto_ingresos = ?,
                neto_egresos = ?, neto = ?, notas = ?, hrse25_valor = ?, hrse50_valor = ?,
                hrse75_valor = ?, hrse100_valor = ?
             WHERE nomina_detalles_id = ?"
        );

        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }

        $dias = $this->numero($datos['dias_trabajados'] ?? 0);
        $h25 = $this->numero($datos['hrse25'] ?? 0);
        $h50 = $this->numero($datos['hrse50'] ?? 0);
        $h75 = $this->numero($datos['hrse75'] ?? 0);
        $h100 = $this->numero($datos['hrse100'] ?? 0);
        $retro = $this->numero($datos['retroactivo'] ?? 0);
        $bono = $this->numero($datos['bono'] ?? 0);
        $otros = $this->numero($datos['otros_ingresos'] ?? 0);
        $ded = $this->numero($datos['deducciones'] ?? 0);
        $prest = $this->numero($datos['prestamo'] ?? 0);
        $ihss = $this->numero($datos['ihss'] ?? 0);
        $rap = $this->numero($datos['rap'] ?? 0);
        $isr = $this->numero($datos['isr'] ?? 0);
        $vales = $this->numero($datos['vales'] ?? 0);
        $incap = $this->numero($datos['incapacidad_ihss'] ?? 0);
        $netIng = $this->numero($datos['neto_ingresos'] ?? 0);
        $netEgr = $this->numero($datos['neto_egresos'] ?? 0);
        $neto = $this->numero($datos['neto'] ?? 0);
        $notas = (string)($datos['notas'] ?? '');
        $v25 = $this->numero($datos['hrse25_valor'] ?? 0);
        $v50 = $this->numero($datos['hrse50_valor'] ?? 0);
        $v75 = $this->numero($datos['hrse75_valor'] ?? 0);
        $v100 = $this->numero($datos['hrse100_valor'] ?? 0);
        $id = (int)($datos['nomina_detalles_id'] ?? 0);

        $sql->bind_param(
            "ddddddddddddddddddsddddi",
            $dias, $h25, $h50, $h75, $h100, $retro, $bono, $otros, $ded, $prest,
            $ihss, $rap, $isr, $vales, $incap, $netIng, $netEgr, $neto, $notas,
            $v25, $v50, $v75, $v100, $id
        );

        if (!$sql->execute()) {
            $this->setErrorNomina($sql->error);
            $sql->close();
            return false;
        }

        $sql->close();
        return true;
    }

    protected function edit_nomina_modelo($datos)
    {
        $conn = $this->connection();
        $this->setErrorNomina('');

        $sql = $conn->prepare(
            "UPDATE nomina SET fecha_inicio = ?, fecha_fin = ?, notas = ? WHERE nomina_id = ?"
        );

        if (!$sql) {
            $this->setErrorNomina($conn->error);
            return false;
        }

        $fecha_inicio = (string)$datos['fecha_inicio'];
        $fecha_fin = (string)$datos['fecha_fin'];
        $notas = (string)$datos['notas'];
        $nomina_id = (int)$datos['nomina_id'];

        $sql->bind_param("sssi", $fecha_inicio, $fecha_fin, $notas, $nomina_id);

        if (!$sql->execute()) {
            $this->setErrorNomina($sql->error);
            $sql->close();
            return false;
        }

        $sql->close();
        return true;
    }

    /*=========================================================
     * DELETES
     *=========================================================*/

    protected function delete_nomina_modelo($nomina_id)
    {
        $conn = $this->connection();
        $nomina_id = (int)$nomina_id;
        return $conn->query("DELETE FROM nomina WHERE nomina_id = {$nomina_id}");
    }

    protected function delete_nomina_detalles_modelo($nomina_detalles_id)
    {
        $conn = $this->connection();
        $nomina_detalles_id = (int)$nomina_detalles_id;
        return $conn->query("DELETE FROM nomina_detalles WHERE nomina_detalles_id = {$nomina_detalles_id}");
    }
}
