<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class secuenciaFacturacionModelo extends mainModel{

    protected function agregar_secuencia_facturacion_modelo($datos){
        $secuencia_facturacion_id  = mainModel::correlativo("secuencia_facturacion_id ", "secuencia_facturacion");
        $insert = "INSERT INTO secuencia_facturacion VALUES(
            '$secuencia_facturacion_id',
            '".$datos['empresa_id']."',
            '".$datos['cai']."',
            '".$datos['prefijo']."',
            '".$datos['relleno']."',
            '".$datos['incremento']."',
            '".$datos['siguiente']."',
            '".$datos['rango_inicial']."',
            '".$datos['rango_final']."',
            '".$datos['fecha_activacion']."',
            '".$datos['fecha_limite']."',
            '".$datos['activo']."',
            '".$datos['usuario']."',
            '".$datos['fecha_registro']."',
            '".$datos['documento_id']."'
        )";

        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        return $sql;
    }

    protected function valid_secuencia_facturacion($empresa_id, $documento_id){
        $query = "SELECT secuencia_facturacion_id 
                  FROM secuencia_facturacion 
                  WHERE activo = 1 
                    AND empresa_id = '$empresa_id' 
                    AND documento_id = '$documento_id'";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }

    /** Valida otra secuencia activa distinta a la indicada (para edición) */
    protected function valid_secuencia_activa_otra($empresa_id, $documento_id, $exclude_id){
        $query = "SELECT secuencia_facturacion_id 
                  FROM secuencia_facturacion 
                  WHERE activo = 1 
                    AND empresa_id = '$empresa_id' 
                    AND documento_id = '$documento_id'
                    AND secuencia_facturacion_id <> '$exclude_id'
                  LIMIT 1";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }

    /** Devuelve la secuencia por ID (con empresa y documento) */
    protected function get_secuencia_por_id($secuencia_facturacion_id){
        $query = "SELECT secuencia_facturacion_id, empresa_id, documento_id, siguiente, activo
                  FROM secuencia_facturacion
                  WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'
                  LIMIT 1";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }

    /** Verifica si 'number' ya fue usado en facturas para esa empresa/documento */
    protected function existe_factura_con_numero($empresa_id, $documento_id, $numero){
        $numero = (int)$numero;
        $query = "SELECT f.facturas_id, f.estado
                  FROM facturas f
                  INNER JOIN secuencia_facturacion s 
                      ON s.secuencia_facturacion_id = f.secuencia_facturacion_id
                  WHERE s.empresa_id = '$empresa_id'
                    AND s.documento_id = '$documento_id'
                    AND f.number = '$numero'
                  LIMIT 1";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }

    protected function edit_secuencia_facturacion_modelo($datos){
        $update = "UPDATE secuencia_facturacion
        SET 
            cai = '".$datos['cai']."',
            prefijo = '".$datos['prefijo']."',
            relleno = '".$datos['relleno']."',
            incremento = '".$datos['incremento']."',
            siguiente = '".$datos['siguiente']."',
            rango_inicial = '".$datos['rango_inicial']."',
            rango_final = '".$datos['rango_final']."',
            fecha_activacion = '".$datos['fecha_activacion']."',
            fecha_limite = '".$datos['fecha_limite']."',
            activo = '".$datos['activo']."'
        WHERE secuencia_facturacion_id = '".$datos['secuencia_facturacion_id']."'";
        
        $sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        return $sql;
    }

    protected function delete_secuencia_facturacion_modelo($secuencia_facturacion_id){
        $delete = "DELETE FROM secuencia_facturacion WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";
        $sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
        return $sql;
    }

    protected function valid_secuencia_facturacion_facturas($secuencia_facturacion_id){
        $query = "SELECT facturas_id FROM facturas WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }

    protected function getTotalSecuenciaRegistradas() {
        try {
            $conexion = $this->connection();
            $query = "SELECT COUNT(secuencia_facturacion_id) AS total FROM secuencia_facturacion WHERE activo = 1";
            $resultado = $conexion->query($query);
            if (!$resultado) {
                throw new Exception("Error al contar secuencias de facturacion: " . $conexion->error);
            }
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
        } catch (Exception $e) {
            error_log("Error en getTotalSecuenciaRegistradas: " . $e->getMessage());
            return 0;
        }
    }
}