<?php
if($peticionAjax){
    require_once "../modelos/colaboradorModelo.php";
    require_once "../core/correo/NotificationService.php";
}else{
    require_once "./modelos/colaboradorModelo.php";
    require_once "./core/correo/NotificationService.php";
}

class colaboradorControlador extends colaboradorModelo{

    private function notificarColaborador($titulo, $resumen, array $detalles = [], array $cambios = [], $tipo = 'audit', $empresaId = 0){
        try {
            $service = new NotificationService();
            $dbActual = $service->currentDbName();

            if ($dbActual === '') {
                return ['sent' => false, 'count' => 0, 'message' => 'Base de datos actual no identificada.'];
            }

            return $service->notifyAdmins(
                $dbActual,
                (int)$empresaId,
                'IZZY · '.$titulo,
                $resumen,
                $detalles,
                $cambios,
                $tipo
            );
        } catch (Throwable $e) {
            error_log('Colaborador - notificación: '.$e->getMessage());
            return ['sent' => false, 'count' => 0, 'message' => $e->getMessage()];
        }
    }

    private function obtenerColaboradorNotificacion($colaboradorId){
        $conexion = null;
        $stmt = null;

        try {
            $conexion = mainModel::connection();
            $stmt = $conexion->prepare("\n                SELECT\n                    c.colaboradores_id,\n                    c.nombre,\n                    c.estado,\n                    c.empresa_id,\n                    c.puestos_id,\n                    c.fecha_ingreso,\n                    c.fecha_egreso,\n                    COALESCE(e.nombre, CONCAT('Empresa #', c.empresa_id)) AS empresa,\n                    COALESCE(p.nombre, CONCAT('Puesto #', c.puestos_id)) AS puesto\n                FROM colaboradores c\n                LEFT JOIN empresa e ON e.empresa_id = c.empresa_id\n                LEFT JOIN puestos p ON p.puestos_id = c.puestos_id\n                WHERE c.colaboradores_id = ?\n                LIMIT 1\n            ");

            if (!$stmt) {
                return null;
            }

            $id = (int)$colaboradorId;
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            return $resultado ? $resultado->fetch_assoc() : null;
        } catch (Throwable $e) {
            error_log('Colaborador - lectura para notificación: '.$e->getMessage());
            return null;
        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }

    private function nombreEmpresaNotificacion($empresaId){
        $conexion = null;
        $stmt = null;

        try {
            $conexion = mainModel::connection();
            $stmt = $conexion->prepare("SELECT nombre FROM empresa WHERE empresa_id = ? LIMIT 1");
            if (!$stmt) return 'Empresa #'.(int)$empresaId;

            $id = (int)$empresaId;
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row && trim((string)$row['nombre']) !== '' ? trim((string)$row['nombre']) : 'Empresa #'.$id;
        } catch (Throwable $e) {
            return 'Empresa #'.(int)$empresaId;
        } finally {
            if ($stmt) $stmt->close();
        }
    }

    private function nombrePuestoNotificacion($puestoId){
        $conexion = null;
        $stmt = null;

        try {
            $conexion = mainModel::connection();
            $stmt = $conexion->prepare("SELECT nombre FROM puestos WHERE puestos_id = ? LIMIT 1");
            if (!$stmt) return 'Puesto #'.(int)$puestoId;

            $id = (int)$puestoId;
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            return $row && trim((string)$row['nombre']) !== '' ? trim((string)$row['nombre']) : 'Puesto #'.$id;
        } catch (Throwable $e) {
            return 'Puesto #'.(int)$puestoId;
        } finally {
            if ($stmt) $stmt->close();
        }
    }

    public function agregar_colaborador_controlador(){
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
        
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);      
        $identidad = mainModel::cleanString($_POST['identidad_colaborador']);    
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        $puesto = mainModel::cleanString($_POST['puesto_colaborador']);            
        $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
        $fecha_egreso = mainModel::cleanString($_POST['fecha_egreso_colaborador']);
        $empresa_id = $_SESSION['empresa_id_sd'];
    
        $fecha_registro = date("Y-m-d H:i:s");    
        $estado = 1;
    
        // Si la identidad está vacía, generamos una única
        if (empty($identidad) || $identidad == "0") {
            do {
                $identidad = "C-" . rand(10000000, 99999999); // Puedes ajustar el formato
            } while (colaboradorModelo::valid_colaborador_modelo($identidad)->num_rows > 0);
        }
    
        $datos = [
            "nombre" => $nombre,              
            "identidad" => $identidad,
            "telefono" => $telefono,                
            "puesto" => $puesto,                
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,    
            "empresa" => $empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso                
        ];
    
        // Validamos si existe el registro
        if (colaboradorModelo::valid_colaborador_modelo($identidad)->num_rows > 0){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "No se puede registrar",
                "message" => "La identidad {$identidad} del colaborador {$nombre}, ya existe"
            ]);
            exit();                
        }

        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        
        // Solo evaluar si existe configuración de plan
        if (isset($planConfig['colaboradores'])) {
            $limiteColaboradores = (int)$planConfig['colaboradores']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
            
            // Caso 1: Límite es 0 (bloquear)
            if ($limiteColaboradores === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan actual no permite registrar colaboradores."
                ]);
            }
            
            // Caso 2: Si tiene límite > 0, validar disponibilidad
            $totalRegistrados = (int)colaboradorModelo::getTotalColaboradoresRegistrados();
            
            if ($totalRegistrados >= $limiteColaboradores) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Límite de colaboradores alcanzado (Máximo: $limiteColaboradores). Actualiza tu plan."
                ]);
            }
        }   
    
        if (!colaboradorModelo::agregar_colaborador_modelo($datos)) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se puede registrar el colaborador {$nombre}"
            ]);
            exit();
        }

        $this->notificarColaborador(
            'Colaborador creado',
            'Se registró un nuevo colaborador.',
            [
                'Colaborador' => $nombre,
                'Empresa' => $this->nombreEmpresaNotificacion($empresa_id),
                'Puesto' => $this->nombrePuestoNotificacion($puesto),
                'Estado' => 'Activo',
                'Fecha de ingreso' => $fecha_ingreso !== '' ? $fecha_ingreso : 'No registrada'
            ],
            [],
            'success',
            (int)$empresa_id
        );
    
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Colaborador {$nombre} registrado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();listar_colaboradores_buscar_factura();listar_colaboradores_buscar_cotizacion();"
        ]);        
    }
    
    
    public function editar_colaborador_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);             
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
        $fecha_egreso = mainModel::cleanString($_POST['fecha_egreso_colaborador']);
        $identidad = mainModel::cleanString($_POST['identidad_colaborador']);
        $colaborador_empresa_id = mainModel::cleanString($_POST['colaborador_empresa_id']);

        if(isset($_POST['puesto_colaborador'])){
            if($_POST['puesto_colaborador'] == ""){
                $puesto = 0;
            }else{
                $puesto = mainModel::cleanStringConverterCase($_POST['puesto_colaborador']);
            }
        }else{
            $puesto = 0;
        }           
        
        $estado = isset($_POST['colaboradores_activo']) ? 1 : 0;
        $anterior = $this->obtenerColaboradorNotificacion($colaborador_id);
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "telefono" => $telefono,                
            "puesto" => $puesto,
            "estado" => $estado,
            "empresa_id" => $colaborador_empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso        
        ];

        if(!colaboradorModelo::editar_colaborador_modelo($datos)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se puede editar el colaborador {$nombre}"
            ]);
            exit();
        }

        $cambios = [];
        if ($anterior) {
            if (trim((string)$anterior['nombre']) !== trim((string)$nombre)) {
                $cambios['Colaborador'] = ['anterior' => $anterior['nombre'], 'nuevo' => $nombre];
            }
            if ((int)$anterior['empresa_id'] !== (int)$colaborador_empresa_id) {
                $cambios['Empresa'] = [
                    'anterior' => $anterior['empresa'],
                    'nuevo' => $this->nombreEmpresaNotificacion($colaborador_empresa_id)
                ];
            }
            if ((int)$anterior['puestos_id'] !== (int)$puesto) {
                $cambios['Puesto'] = [
                    'anterior' => $anterior['puesto'],
                    'nuevo' => $this->nombrePuestoNotificacion($puesto)
                ];
            }
            if ((int)$anterior['estado'] !== (int)$estado) {
                $cambios['Estado'] = [
                    'anterior' => (int)$anterior['estado'] === 1 ? 'Activo' : 'Inactivo',
                    'nuevo' => (int)$estado === 1 ? 'Activo' : 'Inactivo'
                ];
            }
            if ((string)$anterior['fecha_ingreso'] !== (string)$fecha_ingreso) {
                $cambios['Fecha de ingreso'] = ['anterior' => $anterior['fecha_ingreso'], 'nuevo' => $fecha_ingreso];
            }
            if ((string)$anterior['fecha_egreso'] !== (string)$fecha_egreso) {
                $cambios['Fecha de egreso'] = ['anterior' => $anterior['fecha_egreso'], 'nuevo' => $fecha_egreso];
            }
        }

        $this->notificarColaborador(
            'Colaborador actualizado',
            'Se actualizaron los datos de un colaborador.',
            [
                'Colaborador' => $nombre,
                'Empresa' => $this->nombreEmpresaNotificacion($colaborador_empresa_id)
            ],
            $cambios,
            'info',
            (int)$colaborador_empresa_id
        );

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Colaborador {$nombre} registrado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
        ]); 
    }
    
    public function editar_colaborador_perfil_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);               
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        
        $fecha_registro = date("Y-m-d H:i:s");    
        $anterior = $this->obtenerColaboradorNotificacion($colaborador_id);
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "telefono" => $telefono,
        ];

        if(!colaboradorModelo::editar_colaborador_perfil_modelo($datos)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "no se puede editar el colaborador {$nombre}"
            ]);
            exit();
        }

        $cambios = [];
        if ($anterior && trim((string)$anterior['nombre']) !== trim((string)$nombre)) {
            $cambios['Colaborador'] = ['anterior' => $anterior['nombre'], 'nuevo' => $nombre];
        }

        $empresaId = $anterior ? (int)$anterior['empresa_id'] : (int)($_SESSION['empresa_id_sd'] ?? 0);
        $this->notificarColaborador(
            'Perfil de colaborador actualizado',
            'Se actualizaron datos del perfil de un colaborador.',
            ['Colaborador' => $nombre],
            $cambios,
            'info',
            $empresaId
        );

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Colaborador {$nombre} editado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
        ]);         
    }        
    
    public function delete_colaborador_controlador() {
        $colaborador_id = $_POST['colaborador_id'];        
        $anterior = $this->obtenerColaboradorNotificacion($colaborador_id);
        
        // Validar si el colaborador existe
        $result_valid = colaboradorModelo::valid_colaborador_bitacora($colaborador_id);
        
        if (empty($result_valid)) {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Colaborador no encontrado"
            ]);
            exit();
        }
    
        // Verificar si tiene registros asociados
        if($result_valid->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "title" => "Registro con información asociada",
                "message" => "No se puede eliminar porque tiene registros en bitácora"
            ]);
            exit();                
        }
        
        // Intentar eliminar
        $query = colaboradorModelo::delete_colaborador_modelo($colaborador_id);
                                
        if($query) {
            if ($anterior) {
                $this->notificarColaborador(
                    'Colaborador eliminado',
                    'Se eliminó un colaborador.',
                    [
                        'Colaborador' => $anterior['nombre'],
                        'Empresa' => $anterior['empresa'],
                        'Puesto' => $anterior['puesto']
                    ],
                    [],
                    'warning',
                    (int)$anterior['empresa_id']
                );
            }

            echo json_encode([
                "status" => "success",
                "title" => "Eliminado",
                "message" => "Colaborador eliminado correctamente",                    
                "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el colaborador"                    
            ]);
        }
        exit();
    }
}
