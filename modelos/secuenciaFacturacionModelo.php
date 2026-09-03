<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class secuenciaFacturacionModelo extends mainModel{

    protected function agregar_secuencia_facturacion_modelo($datos){
        $conexion = mainModel::connection();
        $secuencia_facturacion_id = (int)mainModel::correlativo("secuencia_facturacion_id", "secuencia_facturacion");

        $sql = "INSERT INTO secuencia_facturacion (
                    secuencia_facturacion_id,
                    empresa_id,
                    cai,
                    prefijo,
                    relleno,
                    incremento,
                    siguiente,
                    rango_inicial,
                    rango_final,
                    fecha_activacion,
                    fecha_limite,
                    activo,
                    colaboradores_id,
                    fecha_registro,
                    documento_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);
        if(!$stmt){
            error_log("Error preparando inserción de secuencia: ".$conexion->error);
            return false;
        }

        $empresa_id = (int)$datos['empresa_id'];
        $cai = (string)$datos['cai'];
        $prefijo = (string)$datos['prefijo'];
        $relleno = (int)$datos['relleno'];
        $incremento = (int)$datos['incremento'];
        $siguiente = (int)$datos['siguiente'];
        $rango_inicial = (string)$datos['rango_inicial'];
        $rango_final = (string)$datos['rango_final'];
        $fecha_activacion = (string)$datos['fecha_activacion'];
        $fecha_limite = (string)$datos['fecha_limite'];
        $activo = (int)$datos['activo'];
        $usuario = (int)$datos['usuario'];
        $fecha_registro = (string)$datos['fecha_registro'];
        $documento_id = (int)$datos['documento_id'];

        $stmt->bind_param(
            "iissiiissssiisi",
            $secuencia_facturacion_id,
            $empresa_id,
            $cai,
            $prefijo,
            $relleno,
            $incremento,
            $siguiente,
            $rango_inicial,
            $rango_final,
            $fecha_activacion,
            $fecha_limite,
            $activo,
            $usuario,
            $fecha_registro,
            $documento_id
        );

        $ok = $stmt->execute();
        if(!$ok){
            error_log("Error insertando secuencia: ".$stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    protected function valid_secuencia_facturacion($empresa_id, $documento_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("SELECT secuencia_facturacion_id FROM secuencia_facturacion WHERE activo = 1 AND empresa_id = ? AND documento_id = ? LIMIT 1");
        $empresa_id = (int)$empresa_id;
        $documento_id = (int)$documento_id;
        $stmt->bind_param("ii", $empresa_id, $documento_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function valid_secuencia_activa_otra($empresa_id, $documento_id, $exclude_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("SELECT secuencia_facturacion_id FROM secuencia_facturacion WHERE activo = 1 AND empresa_id = ? AND documento_id = ? AND secuencia_facturacion_id <> ? LIMIT 1");
        $empresa_id = (int)$empresa_id;
        $documento_id = (int)$documento_id;
        $exclude_id = (int)$exclude_id;
        $stmt->bind_param("iii", $empresa_id, $documento_id, $exclude_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function get_secuencia_por_id($secuencia_facturacion_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("SELECT secuencia_facturacion_id, empresa_id, documento_id, siguiente, rango_inicial, rango_final, incremento, activo FROM secuencia_facturacion WHERE secuencia_facturacion_id = ? LIMIT 1");
        $secuencia_facturacion_id = (int)$secuencia_facturacion_id;
        $stmt->bind_param("i", $secuencia_facturacion_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function get_documento_por_id($documento_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("SELECT documento_id, nombre, estado FROM documento WHERE documento_id = ? LIMIT 1");
        $documento_id = (int)$documento_id;
        $stmt->bind_param("i", $documento_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function existe_factura_con_numero($empresa_id, $documento_id, $numero){
        $conexion = mainModel::connection();
        $numero = (int)$numero;
        $empresa_id = (int)$empresa_id;
        $documento_id = (int)$documento_id;

        $sql = "SELECT f.facturas_id, f.estado
                FROM facturas f
                INNER JOIN secuencia_facturacion s ON s.secuencia_facturacion_id = f.secuencia_facturacion_id
                WHERE s.empresa_id = ? AND s.documento_id = ? AND f.number = ?
                LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $empresa_id, $documento_id, $numero);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function edit_secuencia_facturacion_modelo($datos){
        $conexion = mainModel::connection();
        $sql = "UPDATE secuencia_facturacion SET
                    cai = ?,
                    prefijo = ?,
                    relleno = ?,
                    incremento = ?,
                    siguiente = ?,
                    rango_inicial = ?,
                    rango_final = ?,
                    fecha_activacion = ?,
                    fecha_limite = ?,
                    activo = ?
                WHERE secuencia_facturacion_id = ?";

        $stmt = $conexion->prepare($sql);
        if(!$stmt){
            error_log("Error preparando actualización de secuencia: ".$conexion->error);
            return false;
        }

        $cai = (string)$datos['cai'];
        $prefijo = (string)$datos['prefijo'];
        $relleno = (int)$datos['relleno'];
        $incremento = (int)$datos['incremento'];
        $siguiente = (int)$datos['siguiente'];
        $rango_inicial = (string)$datos['rango_inicial'];
        $rango_final = (string)$datos['rango_final'];
        $fecha_activacion = (string)$datos['fecha_activacion'];
        $fecha_limite = (string)$datos['fecha_limite'];
        $activo = (int)$datos['activo'];
        $id = (int)$datos['secuencia_facturacion_id'];

        $stmt->bind_param(
            "ssiiissssii",
            $cai,
            $prefijo,
            $relleno,
            $incremento,
            $siguiente,
            $rango_inicial,
            $rango_final,
            $fecha_activacion,
            $fecha_limite,
            $activo,
            $id
        );

        $ok = $stmt->execute();
        if(!$ok){
            error_log("Error actualizando secuencia: ".$stmt->error);
        }
        $stmt->close();
        return $ok;
    }

    protected function delete_secuencia_facturacion_modelo($secuencia_facturacion_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("DELETE FROM secuencia_facturacion WHERE secuencia_facturacion_id = ?");
        $id = (int)$secuencia_facturacion_id;
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    protected function valid_secuencia_facturacion_facturas($secuencia_facturacion_id){
        $conexion = mainModel::connection();
        $stmt = $conexion->prepare("SELECT facturas_id FROM facturas WHERE secuencia_facturacion_id = ? LIMIT 1");
        $id = (int)$secuencia_facturacion_id;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();
    }

    protected function getTotalSecuenciaRegistradas(){
        try{
            $conexion = mainModel::connection();
            $resultado = $conexion->query("SELECT COUNT(secuencia_facturacion_id) AS total FROM secuencia_facturacion WHERE activo = 1");
            if(!$resultado){
                throw new Exception("Error al contar secuencias de facturación: ".$conexion->error);
            }
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
        }catch(Exception $e){
            error_log("Error en getTotalSecuenciaRegistradas: ".$e->getMessage());
            return 0;
        }
    }
}
