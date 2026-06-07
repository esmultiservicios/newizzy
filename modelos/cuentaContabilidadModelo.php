<?php
// cuentaContabilidadModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class cuentaContabilidadModelo extends mainModel{
    protected function agregar_cuenta_contabilidad_modelo($datos){
        $conexion = mainModel::connection();

        $cuentas_id = mainModel::correlativo("cuentas_id", "cuentas");

        if((int)$datos['es_inversion'] === 1){
            $conexion->query("UPDATE cuentas SET es_inversion = 0") or die($conexion->error);
        }

        $insert = "INSERT INTO cuentas (
            cuentas_id,
            codigo,
            nombre,
            estado,
            fecha_registro,
            es_inversion
        ) VALUES (
            '$cuentas_id',
            '".$datos['codigo']."',
            '".$datos['nombre']."',
            '".$datos['estado']."',
            '".$datos['fecha_registro']."',
            '".$datos['es_inversion']."'
        )";

        $sql = $conexion->query($insert) or die($conexion->error);

        return $sql;
    }

    protected function valid_cuenta_contable_modelo($nombre){
        $query = "SELECT cuentas_id FROM cuentas WHERE nombre = '$nombre'";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

        return $sql;
    }

    protected function edit_cuentas_contabilidad_modelo($datos){
        $conexion = mainModel::connection();

        if((int)$datos['es_inversion'] === 1){
            $conexion->query("UPDATE cuentas SET es_inversion = 0 WHERE cuentas_id <> '".$datos['cuentas_id']."'") or die($conexion->error);
        }

        $update = "UPDATE cuentas
        SET 
            nombre = '".$datos['nombre']."',
            estado = '".$datos['estado']."',
            es_inversion = '".$datos['es_inversion']."'
        WHERE cuentas_id = '".$datos['cuentas_id']."'";

        $sql = $conexion->query($update) or die($conexion->error);

        return $sql;
    }

    protected function delete_cuenta_contabilidad_modelo($cuentas_id){
        $delete = "DELETE FROM cuentas WHERE cuentas_id = '$cuentas_id'";

        $sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

        return $sql;
    }

    protected function valid_cuenta_contable_movimientos_modelo($cuentas_id){
        $query = "SELECT movimientos_cuentas_id FROM movimientos_cuentas WHERE cuentas_id = '$cuentas_id'";

        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

        return $sql;
    }

    protected function getTotalCuentasRegistradas() {
        try {
            $conexion = $this->connection();

            $query = "SELECT COUNT(cuentas_id) AS total FROM cuentas WHERE estado = 1";

            $resultado = $conexion->query($query);

            if (!$resultado) {
                throw new Exception("Error al contar cuentas contables: " . $conexion->error);
            }

            $fila = $resultado->fetch_assoc();

            return (int)$fila['total'];

        } catch (Exception $e) {
            error_log("Error en getTotalCuentasRegistradas: " . $e->getMessage());
            return 0;
        }
    }
}