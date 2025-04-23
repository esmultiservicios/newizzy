<?php
if($peticionAjax) {
    require_once "../modelos/asistenciaModelo.php";
} else {
    require_once "./modelos/asistenciaModelo.php";
}

class asistenciaControlador extends asistenciaModelo {
    public function agregar_asistencia_controlador() {
        // Recuperación y limpieza de datos
        $colaborador = $_POST['asistencia_empleado'];
        $marcarAsistencia_id = $_POST['marcarAsistencia_id'];
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date("Y-m-d H:i:s");
        $hora = ($marcarAsistencia_id == 0) ? $_POST['hora'] : $_POST['horagi'];
        $comentario = mainModel::cleanString($_POST['comentario']);

        // Obtener comentario previo si existe
        $datos_comentario = [
            "colaborador" => $colaborador,
            "fecha" => $fecha
        ];
        
        $result_comentario = asistenciaModelo::getComentarioAsistenciaModelo($datos_comentario)->fetch_assoc();
        $_comentario = $result_comentario['comentario'] ?? "";
        
        $comentario = ($_comentario == "") ? $comentario : $_comentario.' - '.$comentario;

        // Preparar datos para el registro
        $datos = [
            "colaborador" => $colaborador,
            "fecha" => $fecha,
            "horai" => $hora,
            "horaf" => "",
            "comentario" => $comentario,
            "estado" => 0,
            "fecha_registro" => date("Y-m-d H:i:s")
        ];

        // Validar si ya existe registro de entrada
        if(asistenciaModelo::valid_asistencia_horai_modelo($datos)->num_rows == 0) {
            return $this->procesarRegistroEntrada($datos, $marcarAsistencia_id);
        } else {
            return $this->procesarRegistroSalida($datos, $marcarAsistencia_id);
        }
    }

    private function procesarRegistroEntrada($datos, $marcarAsistencia_id) {
        $query = asistenciaModelo::agregar_asistencia_modelo($datos);
        
        if($query) {
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro almacenado",
                "text" => "El registro se ha almacenado correctamente",
                "form" => "formAsistencia",
                "funcion" => "listar_asistencia();getColaboradores();",
                "modal" => ($marcarAsistencia_id == 0) ? "" : "modal_registrar_asistencia"
            ]);
        } else {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Ocurrió un error inesperado",
                "text" => "No hemos podido procesar su solicitud"
            ]);
        }
    }

    private function procesarRegistroSalida($datos, $marcarAsistencia_id) {
        $consultaHoraf = asistenciaModelo::valid_asistencia_horaf_modelo($datos)->fetch_assoc();
        
        if($consultaHoraf['horaf'] == "") {
            $datos_salida = [
                "colaborador" => $datos['colaborador'],
                "fecha" => $datos['fecha'],
                "horai" => "",
                "horaf" => $datos['horai'],
                "estado" => $datos['estado'],
                "comentario" => $datos['comentario'],
                "fecha_registro" => $datos['fecha_registro']
            ];

            $query = asistenciaModelo::update_asistencia_marcaje_modelo($datos_salida);
            
            if($query) {
                return mainModel::showNotification([
                    "type" => "success",
                    "title" => "Registro almacenado",
                    "text" => "El registro se ha almacenado correctamente",
                    "form" => "formAsistencia",
                    "funcion" => "listar_asistencia();getColaboradores();",
                    "modal" => ($marcarAsistencia_id == 0) ? "" : "modal_registrar_asistencia"
                ]);
            } else {
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Ocurrió un error inesperado",
                    "text" => "No hemos podido procesar su solicitud"
                ]);
            }
        } else {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Marcaje completado",
                "text" => "Lo sentimos, su marcaje ha sido completado"
            ]);
        }
    }
}