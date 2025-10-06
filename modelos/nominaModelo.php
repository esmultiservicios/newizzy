<?php
// nominaModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class nominaModelo extends mainModel{

    /*=========================================================
     * INSERTS
     *=========================================================*/

    /*========== INSERT: Nómina -> retorna ID o false ==========*/
    protected function agregar_nomina_modelo($datos){
        $conn = $this->connection();
        $nomina_id = $this->correlativo("nomina_id", "nomina");

        // Acepta 'tipo_nomina' (como viene hoy del controlador) o 'tipo_nomina_id'
        $tipo_nomina_id_val = isset($datos['tipo_nomina_id']) ? $datos['tipo_nomina_id'] : ($datos['tipo_nomina'] ?? 0);

        $insert = "
            INSERT INTO nomina 
                (nomina_id, empresa_id, pago_planificado_id, tipo_nomina_id, fecha_inicio, fecha_fin, detalle, importe, notas, usuario, estado, fecha_registro, cuentas_id)
            VALUES
                (
                    '{$nomina_id}',
                    '{$datos['empresa_id']}',
                    '{$datos['pago_planificado_id']}',
                    '{$tipo_nomina_id_val}',
                    '{$datos['fecha_inicio']}',
                    '{$datos['fecha_fin']}',
                    '{$datos['detalle']}',
                    '{$datos['importe']}',
                    '{$datos['notas']}',
                    '{$datos['usuario']}',
                    '{$datos['estado']}',
                    '{$datos['fecha_registro']}',
                    '{$datos['cuentas_id']}'
                )
        ";

        if(!$conn->query($insert)){
            return false;
        }
        return ($conn->affected_rows > 0) ? $nomina_id : false;
    }

    /*========== INSERT: Detalles Nómina -> retorna ID o false ==========*/
    protected function agregar_nomina_detalles_modelo($datos){
        $conn = $this->connection();
        $nomina_detalles_id = $this->correlativo("nomina_detalles_id", "nomina_detalles");

        $insert = "
            INSERT INTO `nomina_detalles`
                (`nomina_detalles_id`, `nomina_id`, `colaboradores_id`, `salario_mensual`, `dias_trabajados`, `hrse25`, `hrse50`, `hrse75`, `hrse100`, `retroactivo`, `bono`, `otros_ingresos`, `deducciones`, `prestamo`, `ihss`, `rap`, `isr`, `vales`, `incapacidad_ihss`, `neto_ingresos`, `neto_egresos`, `neto`, `usuario`, `estado`, `notas`, `fecha_registro`, `hrse25_valor`, `hrse50_valor`, `hrse75_valor`, `hrse100_valor`, `salario`)
            VALUES (
                '{$nomina_detalles_id}',
                '{$datos['nomina_id']}',
                '{$datos['colaboradores_id']}',
                '{$datos['salario_mensual']}',
                '{$datos['dias_trabajados']}',
                '{$datos['hrse25']}',
                '{$datos['hrse50']}',
                '{$datos['hrse75']}',
                '{$datos['hrse100']}',
                '{$datos['retroactivo']}',
                '{$datos['bono']}',
                '{$datos['otros_ingresos']}',
                '{$datos['deducciones']}',
                '{$datos['prestamo']}',
                '{$datos['ihss']}',
                '{$datos['rap']}',
                '{$datos['isr']}',
                '{$datos['vales']}',
                '{$datos['incapacidad_ihss']}',
                '{$datos['neto_ingresos']}',
                '{$datos['neto_egresos']}',
                '{$datos['neto']}',
                '{$datos['usuario']}',
                '{$datos['estado']}',
                '{$datos['notas']}',
                '{$datos['fecha_registro']}',
                '{$datos['hrse25_valor']}',
                '{$datos['hrse50_valor']}',
                '{$datos['hrse75_valor']}',
                '{$datos['hrse100_valor']}',
                '{$datos['salario']}'
            )
        ";

        if(!$conn->query($insert)){
            return false;
        }
        return ($conn->affected_rows > 0) ? $nomina_detalles_id : false;
    }

    /*========== INSERT: Vale -> retorna ID o false ==========*/
    protected function agregar_vale_modelo($datos){
        $conn = $this->connection();
        $vale_id = $this->correlativo("vale_id", "vale");

        $insert = "
            INSERT INTO `vale`
                (`vale_id`, `nomina_id`, `colaboradores_id`, `monto`, `fecha`, `nota`, `usuario`, `estado`, `empresa_id`, `fecha_registro`)
            VALUES (
                '{$vale_id}',
                '{$datos['nomina_id']}',
                '{$datos['colaboradores_id']}',
                '{$datos['monto']}',
                '{$datos['fecha']}',
                '{$datos['nota']}',
                '{$datos['usuario']}',
                '{$datos['estado']}',
                '{$datos['empresa_id']}',
                '{$datos['fecha_registro']}'
            )
        ";

        if(!$conn->query($insert)){
            return false;
        }
        return ($conn->affected_rows > 0) ? $vale_id : false;
    }

    /*=========================================================
     * VALIDACIONES (devuelven mysqli_result)
     *=========================================================*/

    protected function valid_nomina_modelo($detalle){
        $conn = $this->connection();
        $query = "SELECT nomina_id FROM nomina WHERE estado = 0 AND detalle = '".$detalle."' LIMIT 1";
        return $conn->query($query);
    }

    protected function valid_vale_modelo($colaboradores_id){
        $conn = $this->connection();
        $query = "SELECT vale_id FROM vale WHERE estado = 0 AND colaboradores_id = '".$colaboradores_id."' LIMIT 1";
        return $conn->query($query);
    }

    protected function valid_nomina_detalles_modelo($nomina_id, $colaboradores_id){
        $conn = $this->connection();
        $query = "SELECT nomina_detalles_id FROM nomina_detalles WHERE estado = 0 AND nomina_id = '".$nomina_id."' AND colaboradores_id = '".$colaboradores_id."' LIMIT 1";
        return $conn->query($query);
    }

    /* OJO: Aquí recibe NOMINA_ID para verificar si tiene detalles */
    protected function valid_nomina_detalles_delete_modelo($nomina_id){
        $conn = $this->connection();
        $query = "SELECT nomina_detalles_id FROM nomina_detalles WHERE estado = 0 AND nomina_id = '".$nomina_id."' LIMIT 1";
        return $conn->query($query);
    }

    /*=========================================================
     * UPDATES
     *=========================================================*/

    protected function edit_nomina_detalles_modelo($datos){
        $conn = $this->connection();
        $update = "
            UPDATE nomina_detalles SET
                dias_trabajados   = '{$datos['dias_trabajados']}',
                hrse25            = '{$datos['hrse25']}',
                hrse50            = '{$datos['hrse50']}',
                hrse75            = '{$datos['hrse75']}',
                hrse100           = '{$datos['hrse100']}',
                retroactivo       = '{$datos['retroactivo']}',
                bono              = '{$datos['bono']}',
                otros_ingresos    = '{$datos['otros_ingresos']}',
                deducciones       = '{$datos['deducciones']}',
                prestamo          = '{$datos['prestamo']}',
                ihss              = '{$datos['ihss']}',
                rap               = '{$datos['rap']}',
                isr               = '{$datos['isr']}',
                vales             = '{$datos['vales']}',
                incapacidad_ihss  = '{$datos['incapacidad_ihss']}',
                neto_ingresos     = '{$datos['neto_ingresos']}',
                neto_egresos      = '{$datos['neto_egresos']}',
                neto              = '{$datos['neto']}',
                notas             = '{$datos['notas']}',
                hrse25_valor      = '{$datos['hrse25_valor']}',
                hrse50_valor      = '{$datos['hrse50_valor']}',
                hrse75_valor      = '{$datos['hrse75_valor']}',
                hrse100_valor     = '{$datos['hrse100_valor']}'
            WHERE nomina_detalles_id = '{$datos['nomina_detalles_id']}'
        ";

        if(!$conn->query($update)){
            return false;
        }
        return ($conn->affected_rows >= 0);
    }

    protected function edit_nomina_modelo($datos){
        $conn = $this->connection();
        $update = "
            UPDATE nomina SET
                fecha_inicio = '{$datos['fecha_inicio']}',
                fecha_fin    = '{$datos['fecha_fin']}',
                notas        = '{$datos['notas']}'
            WHERE nomina_id = '{$datos['nomina_id']}'
        ";

        if(!$conn->query($update)){
            return false;
        }
        return ($conn->affected_rows >= 0);
    }

    /*=========================================================
     * DELETES
     *=========================================================*/

    protected function delete_nomina_modelo($nomina_id){
        $conn = $this->connection();
        $delete = "DELETE FROM nomina WHERE nomina_id = '$nomina_id'";
        if(!$conn->query($delete)){
            return false;
        }
        return ($conn->affected_rows > 0);
    }

    protected function delete_nomina_detalles_modelo($nomina_detalles_id){
        $conn = $this->connection();
        $delete = "DELETE FROM nomina_detalles WHERE nomina_detalles_id = '$nomina_detalles_id'";
        if(!$conn->query($delete)){
            return false;
        }
        return ($conn->affected_rows > 0);
    }
}