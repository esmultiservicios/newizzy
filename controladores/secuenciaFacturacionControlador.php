<?php
if($peticionAjax){
    require_once "../modelos/secuenciaFacturacionModelo.php";
}else{
    require_once "./modelos/secuenciaFacturacionModelo.php";
}

class secuenciaFacturacionControlador extends secuenciaFacturacionModelo{

    public function agregar_secuencia_facturacion_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $empresa_id       = (int)mainModel::cleanString($_POST['empresa_secuencia'] ?? 0);
        $documento_id     = (int)mainModel::cleanString($_POST['documento_secuencia'] ?? 0);
        $cai              = mainModel::cleanString($_POST['cai_secuencia'] ?? '');
        $prefijo          = mainModel::cleanString($_POST['prefijo_secuencia'] ?? '');
        $relleno          = (int)mainModel::cleanString($_POST['relleno_secuencia'] ?? 0);
        $incremento       = (int)mainModel::cleanString($_POST['incremento_secuencia'] ?? 0);
        $siguiente        = (int)mainModel::cleanString($_POST['siguiente_secuencia'] ?? 0);
        $rango_inicial    = mainModel::cleanString($_POST['rango_inicial_secuencia'] ?? '');
        $rango_final      = mainModel::cleanString($_POST['rango_final_secuencia'] ?? '');
        $fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia'] ?? '');
        $fecha_limite     = mainModel::cleanString($_POST['fecha_limite_secuencia'] ?? '');
        $usuario          = (int)($_SESSION['colaborador_id_sd'] ?? 0);
        $fecha_registro   = date("Y-m-d H:i:s");
        $activo           = isset($_POST['estado_secuencia']) && $_POST['estado_secuencia'] === 'on' ? 1 : 0;

        $error = $this->validarDatosSecuencia($empresa_id, $documento_id, $relleno, $incremento, $siguiente, $rango_inicial, $rango_final, $fecha_activacion, $fecha_limite, $activo, true);
        if($error !== null){
            return $error;
        }

        if($activo === 1 && secuenciaFacturacionModelo::valid_secuencia_facturacion($empresa_id, $documento_id)->num_rows > 0){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Secuencia activa existente",
                "text" => "Ya existe una secuencia activa para ese documento en la empresa seleccionada. Desactive la anterior antes de registrar otra."
            ]);
        }

        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        if($activo === 1 && isset($planConfig['secuencias'])){
            $limiteSecuencias = (int)$planConfig['secuencias'];
            if($limiteSecuencias === 0){
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan actual no permite registrar secuencias de facturación."
                ]);
            }
            if((int)secuenciaFacturacionModelo::getTotalSecuenciaRegistradas() >= $limiteSecuencias){
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Límite de secuencias de facturación alcanzado (Máximo: {$limiteSecuencias}). Actualice su plan para registrar una nueva secuencia."
                ]);
            }
        }

        $documento = $this->obtenerDocumento($documento_id);
        if($this->documentoUsaFacturas($documento_id, $documento['nombre'])){
            $existe = secuenciaFacturacionModelo::existe_factura_con_numero($empresa_id, $documento_id, $siguiente);
            if($existe && $existe->num_rows > 0){
                $row = $existe->fetch_assoc();
                return mainModel::showNotification([
                    "type" => "warning",
                    "title" => "Número ya utilizado",
                    "text" => "El número {$siguiente} ya fue utilizado en una factura con estado: ".$this->estadoFacturaTexto((int)$row['estado']).". No puede reutilizarse."
                ]);
            }
        }

        $datos = [
            "empresa_id" => $empresa_id,
            "documento_id" => $documento_id,
            "cai" => $cai,
            "prefijo" => $prefijo,
            "relleno" => $relleno,
            "incremento" => $incremento,
            "siguiente" => $siguiente,
            "rango_inicial" => $rango_inicial,
            "rango_final" => $rango_final,
            "fecha_activacion" => $fecha_activacion,
            "fecha_limite" => $fecha_limite,
            "activo" => $activo,
            "usuario" => $usuario,
            "fecha_registro" => $fecha_registro
        ];

        if(!secuenciaFacturacionModelo::agregar_secuencia_facturacion_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar la secuencia de facturación.",
                "type" => "error"
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Secuencia de facturación registrada correctamente.",
            "form" => "formSecuencia",
            "funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();"
        ]);
    }

    public function edit_secuencia_facturacion_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $secuencia_facturacion_id = (int)mainModel::cleanString($_POST['secuencia_facturacion_id'] ?? 0);
        $cai              = mainModel::cleanString($_POST['cai_secuencia'] ?? '');
        $prefijo          = mainModel::cleanString($_POST['prefijo_secuencia'] ?? '');
        $relleno          = (int)mainModel::cleanString($_POST['relleno_secuencia'] ?? 0);
        $incremento       = (int)mainModel::cleanString($_POST['incremento_secuencia'] ?? 0);
        $siguiente        = (int)mainModel::cleanString($_POST['siguiente_secuencia'] ?? 0);
        $rango_inicial    = mainModel::cleanString($_POST['rango_inicial_secuencia'] ?? '');
        $rango_final      = mainModel::cleanString($_POST['rango_final_secuencia'] ?? '');
        $fecha_activacion = mainModel::cleanString($_POST['fecha_activacion_secuencia'] ?? '');
        $fecha_limite     = mainModel::cleanString($_POST['fecha_limite_secuencia'] ?? '');
        $activo           = isset($_POST['estado_secuencia']) && $_POST['estado_secuencia'] === 'on' ? 1 : 0;

        if($secuencia_facturacion_id <= 0){
            return mainModel::showNotification(["title"=>"Error", "text"=>"Secuencia no válida.", "type"=>"error"]);
        }

        $info = secuenciaFacturacionModelo::get_secuencia_por_id($secuencia_facturacion_id);
        if(!$info || $info->num_rows === 0){
            return mainModel::showNotification(["title"=>"Error", "text"=>"Secuencia no encontrada.", "type"=>"error"]);
        }

        $rowInfo = $info->fetch_assoc();
        $empresa_id = (int)$rowInfo['empresa_id'];
        $documento_id = (int)$rowInfo['documento_id'];

        $error = $this->validarDatosSecuencia($empresa_id, $documento_id, $relleno, $incremento, $siguiente, $rango_inicial, $rango_final, $fecha_activacion, $fecha_limite, $activo, false);
        if($error !== null){
            return $error;
        }

        if($activo === 1){
            $otra = secuenciaFacturacionModelo::valid_secuencia_activa_otra($empresa_id, $documento_id, $secuencia_facturacion_id);
            if($otra && $otra->num_rows > 0){
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Regla de activación",
                    "text" => "Ya existe otra secuencia activa para este documento en la empresa. Debe desactivar la otra antes de activar esta."
                ]);
            }
        }

        $documento = $this->obtenerDocumento($documento_id, false);
        if($documento && $this->documentoUsaFacturas($documento_id, $documento['nombre'])){
            $existe = secuenciaFacturacionModelo::existe_factura_con_numero($empresa_id, $documento_id, $siguiente);
            if($existe && $existe->num_rows > 0){
                $row = $existe->fetch_assoc();
                return mainModel::showNotification([
                    "type" => "warning",
                    "title" => "Número ya utilizado",
                    "text" => "El número {$siguiente} ya fue utilizado en una factura con estado: ".$this->estadoFacturaTexto((int)$row['estado']).". No puede reutilizarse."
                ]);
            }
        }

        $datos = [
            "secuencia_facturacion_id" => $secuencia_facturacion_id,
            "cai" => $cai,
            "prefijo" => $prefijo,
            "relleno" => $relleno,
            "incremento" => $incremento,
            "siguiente" => $siguiente,
            "rango_inicial" => $rango_inicial,
            "rango_final" => $rango_final,
            "fecha_activacion" => $fecha_activacion,
            "fecha_limite" => $fecha_limite,
            "activo" => $activo
        ];

        if(!secuenciaFacturacionModelo::edit_secuencia_facturacion_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo actualizar la secuencia de facturación.",
                "type" => "error"
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Secuencia de facturación actualizada correctamente.",
            "form" => "formSecuencia",
            "funcion" => "listar_secuencia_facturacion();getEmpresaSecuencia();getDocumentoSecuencia();"
        ]);
    }

    public function delete_secuencia_facturacion_controlador(){
        $secuencia_facturacion_id = (int)($_POST['secuencia_facturacion_id'] ?? 0);

        if($secuencia_facturacion_id <= 0){
            $this->jsonDelete("error", "Error", "Secuencia no válida.");
        }

        $info = secuenciaFacturacionModelo::get_secuencia_por_id($secuencia_facturacion_id);
        if(!$info || $info->num_rows === 0){
            $this->jsonDelete("error", "Error", "Secuencia no encontrada.");
        }

        if(secuenciaFacturacionModelo::valid_secuencia_facturacion_facturas($secuencia_facturacion_id)->num_rows > 0){
            $this->jsonDelete("error", "No se puede eliminar", "La secuencia tiene documentos de facturación asociados y debe conservarse para mantener la trazabilidad.");
        }

        if(!secuenciaFacturacionModelo::delete_secuencia_facturacion_modelo($secuencia_facturacion_id)){
            $this->jsonDelete("error", "Error", "No se pudo eliminar la secuencia.");
        }

        $this->jsonDelete("success", "Eliminado", "Secuencia de facturación eliminada correctamente.");
    }

    private function validarDatosSecuencia($empresa_id, $documento_id, $relleno, $incremento, $siguiente, $rango_inicial, $rango_final, $fecha_activacion, $fecha_limite, $activo, $esNueva){
        if($empresa_id <= 0){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Dato requerido", "text"=>"Seleccione una empresa."]);
        }

        if($documento_id <= 0){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Dato requerido", "text"=>"Seleccione un documento."]);
        }

        $documento = $this->obtenerDocumento($documento_id, false);
        if(!$documento){
            return mainModel::showNotification(["type"=>"error", "title"=>"Documento no válido", "text"=>"El documento seleccionado no existe."]);
        }

        if($esNueva && (int)$documento['estado'] !== 1){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Documento inactivo", "text"=>"El documento seleccionado está inactivo. Actívelo desde Documentos antes de crear una secuencia."]);
        }

        if($relleno <= 0 || $relleno > 20){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Relleno no válido", "text"=>"El relleno debe ser un número entre 1 y 20."]);
        }

        if($incremento <= 0){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Incremento no válido", "text"=>"El incremento debe ser mayor que cero."]);
        }

        if(!preg_match('/^\d+$/', (string)$rango_inicial) || !preg_match('/^\d+$/', (string)$rango_final)){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Rango no válido", "text"=>"El rango inicial y final deben contener únicamente números."]);
        }

        $inicio = (int)$rango_inicial;
        $fin = (int)$rango_final;
        if($fin < $inicio){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Rango no válido", "text"=>"El rango final no puede ser menor que el rango inicial."]);
        }

        if($siguiente < $inicio){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Correlativo no válido", "text"=>"El número siguiente no puede ser menor que el rango inicial."]);
        }

        if($activo === 1 && $siguiente > $fin){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Rango agotado", "text"=>"Una secuencia activa no puede tener el número siguiente fuera del rango autorizado. Desactívela o corrija el correlativo."]);
        }

        if(!$this->fechaValida($fecha_activacion) || !$this->fechaValida($fecha_limite)){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Fecha no válida", "text"=>"Ingrese fechas de activación y límite válidas."]);
        }

        if(strtotime($fecha_limite) < strtotime($fecha_activacion)){
            return mainModel::showNotification(["type"=>"warning", "title"=>"Vigencia no válida", "text"=>"La fecha límite no puede ser anterior a la fecha de activación."]);
        }

        return null;
    }

    private function obtenerDocumento($documento_id, $exigirActivo = true){
        $result = secuenciaFacturacionModelo::get_documento_por_id($documento_id);
        if(!$result || $result->num_rows === 0){
            return false;
        }
        $documento = $result->fetch_assoc();
        if($exigirActivo && (int)$documento['estado'] !== 1){
            return false;
        }
        return $documento;
    }

    private function documentoUsaFacturas($documento_id, $nombre){
        if(in_array((int)$documento_id, [1, 4], true)){
            return true;
        }
        $nombre = $this->normalizarTexto($nombre);
        return strpos($nombre, 'factura') !== false || strpos($nombre, 'proforma') !== false;
    }

    private function normalizarTexto($texto){
        $texto = mb_strtolower((string)$texto, 'UTF-8');
        return strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    }

    private function fechaValida($fecha){
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    private function estadoFacturaTexto($estadoInt){
        switch((int)$estadoInt){
            case 1: return "Borrador";
            case 2: return "Pagada";
            case 3: return "Crédito";
            case 4: return "Cancelada";
            default: return "Desconocido ({$estadoInt})";
        }
    }

    private function jsonDelete($status, $title, $message){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status"=>$status, "title"=>$title, "message"=>$message], JSON_UNESCAPED_UNICODE);
        exit();
    }
}
