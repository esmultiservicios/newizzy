<?php
if($peticionAjax){
    require_once "../modelos/egresosContabilidadModelo.php";
}else{
    require_once "./modelos/egresosContabilidadModelo.php";
}

class egresosContabilidadControlador extends egresosContabilidadModelo{

    /**
     * Construye un nombre de archivo PDF seguro basado en proveedor + factura.
     * Formato principal: factura_<slugProveedor>_<slugFactura>.pdf
     * En caso de colisión: factura_<slugProveedor>_<slugFactura>_<egresosid>.pdf
     * - Normaliza (minúsculas, sin acentos, solo [a-z0-9_-])
     * - Sustituye espacios por guiones bajos
     * - Recorta a 60 chars cada parte para prolijidad
     */
    private function buildPdfFileName($egresos_id, $proveedorNombre, $factura){
        $slug = function($txt){
            $txt = (string)$txt;
            $txt = trim($txt);
            $txt = mb_strtolower($txt, 'UTF-8');

            // Quitar acentos / ñ
            $replacements = [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
                'ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u',
                'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
                'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n',
            ];
            $txt = strtr($txt, $replacements);

            // Espacios -> _
            $txt = preg_replace('/\s+/', '_', $txt);

            // Solo a-z 0-9 _ -
            $txt = preg_replace('/[^a-z0-9_-]/', '', $txt);

            // Limitar longitud
            $txt = substr($txt, 0, 60);

            return ($txt === '' ? 'sin_dato' : $txt);
        };

        $prov = $slug($proveedorNombre);
        $fac  = $slug($factura);

        $baseDir   = '../vistas/plantilla/gastos/';
        $baseName  = "factura_{$prov}_{$fac}";
        $finalName = $baseName . '.pdf';

        // Si existe, agrega sufijo con ID para evitar colisión
        if (file_exists($baseDir . $finalName)) {
            $finalName = "{$baseName}_{$egresos_id}.pdf";
        }

        return $finalName;
    }

    public function agregar_egresos_contabilidad_controlador(){
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
        
        $proveedores_id = $_POST['proveedor_egresos'];
        $cuentas_id = mainModel::cleanString($_POST['cuenta_egresos']);
        $empresa_id = $_SESSION['empresa_id_sd'];
        $tipo_egreso = 2;//GASTOS
        $fecha = $_POST['fecha_egresos'];
        $factura = mainModel::cleanString($_POST['factura_egresos']);
        $subtotal = mainModel::cleanString($_POST['subtotal_egresos'] === "" ? 0 : $_POST['subtotal_egresos']);
        $isv = mainModel::cleanString($_POST['isv_egresos'] === "" ? 0 : $_POST['isv_egresos']);
        $descuento = mainModel::cleanString($_POST['descuento_egresos'] === "" ? 0 : $_POST['descuento_egresos']);
        $nc = mainModel::cleanString($_POST['nc_egresos'] === "" ? 0 : $_POST['nc_egresos']);
        $total = mainModel::cleanString($_POST['total_egresos'] === "" ? 0 : $_POST['total_egresos']);
        $observacion = mainModel::cleanString($_POST['observacion_egresos']);
        
        $categoria_gastos = (isset($_POST['categoria_gastos']) && $_POST['categoria_gastos'] !== '' && is_numeric($_POST['categoria_gastos']))
            ? (int) mainModel::cleanString($_POST['categoria_gastos'])
            : 0;
        
        $estado = 1;
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");    
        $egresos_id = mainModel::correlativo("egresos_id", "egresos");

        // Obtener el nombre del proveedor (la implementarás en el modelo)
        $proveedorNombre = egresosContabilidadModelo::getProveedorNombreById($proveedores_id);

        // Manejar la carga del archivo PDF
        $factura_pdf = '';
        if(isset($_FILES['factura_pdf']) && $_FILES['factura_pdf']['error'] === UPLOAD_ERR_OK) {
            $archivo = $_FILES['factura_pdf'];
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            
            // Validar que sea PDF
            if(strtolower($extension) !== 'pdf') {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Solo se permiten archivos PDF",
                    "type" => "error"
                ]);
            }
            
            // Validar tamaño (5MB máximo)
            if($archivo['size'] > 5 * 1024 * 1024) {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "El archivo no debe exceder los 5MB",
                    "type" => "error"
                ]);
            }

            // Nombre de archivo basado en proveedor + factura (con colisión controlada)
            $nombre_archivo = $this->buildPdfFileName($egresos_id, $proveedorNombre, $factura);
            $ruta_destino = '../vistas/plantilla/gastos/' . $nombre_archivo;
            
            if(move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                $factura_pdf = $nombre_archivo;
            } else {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo guardar el archivo PDF",
                    "type" => "error"
                ]);
            }
        }

        $datos = [
            "egresos_id" => $egresos_id,
            "proveedores_id" => $proveedores_id === "" ? 0 : $proveedores_id,
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
            "tipo_egreso" => $tipo_egreso,
            "fecha" => $fecha,
            "factura" => $factura,
            "factura_pdf" => $factura_pdf,
            "subtotal" => $subtotal,
            "isv" => $isv,
            "descuento" => $descuento,
            "nc" => $nc,
            "total" => $total,
            "observacion" => $observacion,
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,
            "colaboradores_id" => $colaboradores_id,
            "categoria_gastos" => $categoria_gastos
        ];
        
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        
        if (isset($planConfig['egresos'])) {
            $limiteEgresos = (int)$planConfig['egresos'];
            
            if ($limiteEgresos === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan no incluye la creación de egresos contables."
                ]);
            }
            
            $totalRegistradas = (int)egresosContabilidadModelo::getTotalEgresosRegistrados();
            
            if ($totalRegistradas >= $limiteEgresos) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Ha excedido el límite mensual de egresos contables (Máximo: $limiteEgresos)."
                ]);
            }
        }

        $resultEgresos = egresosContabilidadModelo::valid_egresos_cuentas_modelo($datos);
        
        if($resultEgresos->num_rows == 0){
            $query = egresosContabilidadModelo::agregar_egresos_contabilidad_modelo($datos);
            
            if($query){
                // Consultar saldo disponible para la cuenta
                $consulta_ingresos_contabilidad = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
                $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) && $consulta_ingresos_contabilidad['saldo'] !== "" ? $consulta_ingresos_contabilidad['saldo'] : 0;
                
                $ingreso = 0;
                $egreso = $total;
                $saldo = $saldo_consulta - $egreso;
                
                // Agregar movimientos de la cuenta
                $datos_movimientos = [
                    "cuentas_id" => $cuentas_id,
                    "empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
                    "fecha" => $fecha,
                    "ingreso" => $ingreso,
                    "egreso" => $egreso,
                    "saldo" => $saldo,
                    "colaboradores_id" => $colaboradores_id,
                    "fecha_registro" => $fecha_registro,                
                ];
                
                egresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);

                // Historial
                mainModel::guardarHistorial([
                    "modulo" => 'Egresos Contabilidad',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                    "status" => "Registro",
                    "observacion" => "Se registró egreso contable ID: {$egresos_id} por {$total}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ]);
            
                return mainModel::showNotification([
                    "type" => "success",
                    "title" => "Registro almacenado",
                    "text" => "El registro se ha almacenado correctamente",
                    "form" => "formEgresosContables",
                    "funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();printGastos(".$egresos_id.");total_gastos_footer();"
                ]);
            }else{
                // Si falla la inserción, eliminar el archivo subido
                if($factura_pdf != '') {
                    $ruta_archivo = '../vistas/plantilla/gastos/' . $factura_pdf;
                    if(file_exists($ruta_archivo)) {
                        unlink($ruta_archivo);
                    }
                }
                
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo registrar el egreso contable",
                    "type" => "error"
                ]);                
            }                
        }else{
            // Si ya existe el registro, eliminar el archivo subido
            if($factura_pdf != '') {
                $ruta_archivo = '../vistas/plantilla/gastos/' . $factura_pdf;
                if(file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
            }
            
            return mainModel::showNotification([
                "title" => "Registro ya existe",
                "text" => "Ya existe un egreso con estos datos",
                "type" => "error"
            ]);                
        }
    }

	public function agregar_categoria_egresos_controlador(){
		$validacion = mainModel::validarSesion();

		if($validacion['error']) {
			echo json_encode([
				'success' => false,
				'title' => 'Error de sesión',
				'text' => $validacion['mensaje'],
				'type' => 'error',
				'redirect' => $validacion['redireccion']
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$categoria = isset($_POST['categoria']) ? mainModel::cleanString($_POST['categoria']) : "";
		$es_inversion = isset($_POST['es_inversion']) ? 1 : 0;

		if($categoria === ""){
			echo json_encode([
				'success' => false,
				'title' => 'Campo requerido',
				'text' => 'Debe ingresar el nombre de la categoría.',
				'type' => 'warning'
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		if(strlen($categoria) > 30){
			echo json_encode([
				'success' => false,
				'title' => 'Nombre muy largo',
				'text' => 'La categoría no puede tener más de 30 caracteres.',
				'type' => 'warning'
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$estado = 1;
		$colaboradores_id = $_SESSION['colaborador_id_sd'];
		$fecha_registro = date("Y-m-d H:i:s");
		$categoria_gastos_id = mainModel::correlativo("categoria_gastos_id", "categoria_gastos");

		$datos = [
			"categoria_gastos_id" => $categoria_gastos_id,
			"nombre" => $categoria,
			"estado" => $estado,
			"usuario" => $colaboradores_id,
			"date_write" => $fecha_registro,
			"es_inversion" => $es_inversion
		];

		$resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_modelo($datos);

		if($resultCategoriaEgresos->num_rows == 0){
			$query = egresosContabilidadModelo::agregar_categoria_egresos_modelo($datos);

			if($query){
				mainModel::guardarHistorial([
					"modulo" => 'Categoría Egresos',
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],
					"status" => "Registro",
					"observacion" => "Se registró categoría de egresos: {$categoria}",
					"fecha_registro" => date("Y-m-d H:i:s")
				]);

				echo json_encode([
					'success' => true,
					'title' => 'Registro almacenado',
					'text' => 'La categoría se ha registrado correctamente.',
					'type' => 'success'
				], JSON_UNESCAPED_UNICODE);
			}else{
				echo json_encode([
					'success' => false,
					'title' => 'Error',
					'text' => 'No se pudo registrar la categoría.',
					'type' => 'error'
				], JSON_UNESCAPED_UNICODE);
			}
		}else{
			echo json_encode([
				'success' => false,
				'title' => 'Registro ya existe',
				'text' => 'Ya existe una categoría con este nombre.',
				'type' => 'error'
			], JSON_UNESCAPED_UNICODE);
		}

		exit();
	}

    public function edit_egresos_contabilidad_controlador(){
        // 1) Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
    
        // 2) POST (sanitiza lo sensible)
        $egresos_id     = isset($_POST['egresos_id']) ? trim($_POST['egresos_id']) : '';
        $proveedores_id = isset($_POST['proveedor_egresos']) ? trim($_POST['proveedor_egresos']) : '';
        $factura        = mainModel::cleanString($_POST['factura_egresos']     ?? '');
        $observacion    = mainModel::cleanString($_POST['observacion_egresos'] ?? '');
        // $fecha         = $_POST['fecha_egresos'] ?? '';  // <- NO USAMOS la fecha enviada
    
        // 3) Obtener fecha y PDF actuales desde BD (la fecha queda CONGELADA en edición)
        $db  = mainModel::connection();
        $eid = $db->real_escape_string($egresos_id);
        $res = $db->query("SELECT fecha, factura_pdf FROM egresos WHERE egresos_id = '$eid' LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se encontró el egreso especificado.",
                "type"  => "error"
            ]);
        }
        $row            = $res->fetch_assoc();
        $fecha_bd       = trim($row['fecha']);          // <- Usaremos SIEMPRE esta fecha en edición
        $archivo_actual = trim($row['factura_pdf'] ?? '');
    
        // 4) Nombre proveedor (para filename)
        $proveedorNombre = egresosContabilidadModelo::getProveedorNombreById($proveedores_id);
    
        // 5) Ruta ABSOLUTA segura a la carpeta de PDFs
        $BASE_DIR = realpath(__DIR__ . '/../vistas/plantilla/gastos');
        if ($BASE_DIR === false) {
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No existe la carpeta de gastos para almacenar PDF.",
                "type"  => "error"
            ]);
        }
    
        // 6) Quitar archivo existente (si lo pidió el usuario)
        if (isset($_POST['remove_existing_file']) && $_POST['remove_existing_file'] === '1' && $archivo_actual !== '') {
            $ruta_actual = $BASE_DIR . DIRECTORY_SEPARATOR . $archivo_actual;
            if (is_file($ruta_actual)) { @unlink($ruta_actual); }
            $archivo_actual = '';
        }
    
        // 7) Subida de nuevo PDF (si viene)
        $nuevo_archivo = $archivo_actual;
        $subio_nuevo   = false;
        $ruta_nuevo    = '';
    
        if (isset($_FILES['factura_pdf']) && is_array($_FILES['factura_pdf']) && $_FILES['factura_pdf']['error'] === UPLOAD_ERR_OK) {
            $archivo    = $_FILES['factura_pdf'];
            $extension  = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
            // Validaciones
            if ($extension !== 'pdf') {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "Solo se permiten archivos PDF.",
                    "type"  => "error"
                ]);
            }
            if ((int)$archivo['size'] > (5 * 1024 * 1024)) {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "El archivo no debe exceder 5MB.",
                    "type"  => "error"
                ]);
            }
    
            // Armar nombre destino (puede coincidir con el actual)
            $nombre_archivo = $this->buildPdfFileName($egresos_id, $proveedorNombre, $factura);
            $nombre_archivo = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_archivo);
            $ruta_destino   = $BASE_DIR . DIRECTORY_SEPARATOR . $nombre_archivo;
    
            // Reemplazo seguro si ya existe con el MISMO nombre
            if (is_file($ruta_destino)) { @unlink($ruta_destino); }
    
            // Mover primero el nuevo archivo
            if (!is_uploaded_file($archivo['tmp_name']) || !move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text"  => "No se pudo guardar el nuevo archivo PDF.",
                    "type"  => "error"
                ]);
            }
    
            // Éxito en disco → quedará como nuevo_archivo
            $nuevo_archivo = $nombre_archivo;
            $ruta_nuevo    = $ruta_destino;
            $subio_nuevo   = true;
        }
    
        // 8) Persistir cambios en BD (fecha congelada = $fecha_bd)
        $datos = [
            "egresos_id"     => $egresos_id,
            "proveedores_id" => $proveedores_id,
            "factura"        => $factura,
            "fecha"          => $fecha_bd,        // <- AQUÍ aseguramos que no cambie
            "observacion"    => $observacion,
            "factura_pdf"    => $nuevo_archivo
        ];
    
        $ok = egresosContabilidadModelo::edit_egresos_contabilidad_modelo($datos);
    
        if ($ok) {
            // 9) Si subimos uno nuevo y el nombre cambió, borra el anterior (si existía)
            if ($subio_nuevo && $archivo_actual !== '' && $archivo_actual !== $nuevo_archivo) {
                $ruta_anterior = $BASE_DIR . DIRECTORY_SEPARATOR . $archivo_actual;
                if (is_file($ruta_anterior)) { @unlink($ruta_anterior); }
            }
    
            // Historial
            mainModel::guardarHistorial([
                "modulo"           => 'Egresos Contabilidad',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status"           => "Edición",
                "observacion"      => "Se editó egreso contable ID: {$egresos_id}",
                "fecha_registro"   => date("Y-m-d H:i:s")
            ]);
    
            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Registro editado",
                "text"    => "Registro editado correctamente",
                "form"    => "formEgresosContables",
                "funcion" => "listar_gastos_contabilidad();getEmpresaEgresos();getCuentaEgresos();getProveedorEgresos();printGastos(".$egresos_id.")",
                "modal"   => "modalEgresosContables"
            ]);
        }
    
        // 10) Rollback de archivo si la BD falló
        if ($subio_nuevo && $ruta_nuevo !== '' && is_file($ruta_nuevo)) {
            @unlink($ruta_nuevo);
        }
    
        return mainModel::showNotification([
            "title" => "Error",
            "text"  => "No se pudo editar el egreso contable.",
            "type"  => "error"
        ]);
    }    

	public function edit_categoria_egresos_contabilidad_controlador() {
		$validacion = mainModel::validarSesion();

		if($validacion['error']) {
			echo json_encode([
				'success' => false,
				'title' => 'Error de sesión',
				'text' => $validacion['mensaje'],
				'type' => 'error',
				'redirect' => $validacion['redireccion']
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$categoria_gastos_id = isset($_POST['categoria_gastos_id']) ? (int)$_POST['categoria_gastos_id'] : 0;
		$categoria = isset($_POST['categoria']) ? mainModel::cleanString($_POST['categoria']) : "";
		$es_inversion = isset($_POST['es_inversion']) ? 1 : 0;

		if($categoria_gastos_id <= 0) {
			echo json_encode([
				'success' => false,
				'title' => 'Error',
				'text' => 'No se recibió la categoría a editar.',
				'type' => 'error'
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		if($categoria === ""){
			echo json_encode([
				'success' => false,
				'title' => 'Campo requerido',
				'text' => 'Debe ingresar el nombre de la categoría.',
				'type' => 'warning'
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		if(strlen($categoria) > 30){
			echo json_encode([
				'success' => false,
				'title' => 'Nombre muy largo',
				'text' => 'La categoría no puede tener más de 30 caracteres.',
				'type' => 'warning'
			], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$datos = [
			"categoria_gastos_id" => $categoria_gastos_id,
			"nombre" => $categoria,
			"es_inversion" => $es_inversion
		];

		$resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_edicion_modelo($datos);

		if($resultCategoriaEgresos->num_rows == 0) {
			$query = egresosContabilidadModelo::edit_categoria_egresos_contabilidad_modelo($datos);

			if($query) {
				mainModel::guardarHistorial([
					"modulo" => 'Categoría Egresos',
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],
					"status" => "Edición",
					"observacion" => "Se editó categoría de egresos ID: {$categoria_gastos_id}",
					"fecha_registro" => date("Y-m-d H:i:s")
				]);

				echo json_encode([
					'success' => true,
					'title' => 'Registro editado',
					'text' => 'La categoría fue editada correctamente.',
					'type' => 'success'
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode([
					'success' => false,
					'title' => 'Error',
					'text' => 'No se pudo editar la categoría.',
					'type' => 'error'
				], JSON_UNESCAPED_UNICODE);
			}
		} else {
			echo json_encode([
				'success' => false,
				'title' => 'Registro ya existe',
				'text' => 'Ya existe otra categoría con este nombre.',
				'type' => 'error'
			], JSON_UNESCAPED_UNICODE);
		}

		exit();
	}

    public function cancel_egresos_contabilidad_controlador(){
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $egresos_id = isset($_POST['egresos_id']) ? mainModel::cleanString($_POST['egresos_id']) : "";
        $empresa_id = $_SESSION['empresa_id_sd'];
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");

        if($egresos_id === "" || !is_numeric($egresos_id)){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se recibió el egreso a reversar.",
                "type"  => "error"
            ]);
        }

        $resultEgreso = egresosContabilidadModelo::obtener_egreso_reversion_modelo($egresos_id, $empresa_id);

        if(!$resultEgreso || $resultEgreso->num_rows === 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se encontró el egreso a reversar.",
                "type"  => "error"
            ]);
        }

        $egresoData = $resultEgreso->fetch_assoc();

        if((int)$egresoData['estado'] !== 1){
            return mainModel::showNotification([
                "title" => "Egreso inactivo",
                "text"  => "Este egreso no está activo y no se puede reversar.",
                "type"  => "warning",
                "funcion" => "listar_gastos_contabilidad();total_gastos_footer();"
            ]);
        }

        $cuentas_id = (int)$egresoData['cuentas_id'];
        $proveedores_id = (int)$egresoData['proveedores_id'];
        $fecha = $egresoData['fecha'];
        $facturaOriginal = $egresoData['factura'];
        $subtotal = (float)$egresoData['subtotal'];
        $descuento = (float)$egresoData['descuento'];
        $nc = (float)$egresoData['nc'];
        $isv = (float)$egresoData['impuesto'];
        $total = (float)$egresoData['total'];
        $observacionOriginal = $egresoData['observacion'];

        if($total <= 0){
            return mainModel::showNotification([
                "title" => "Total inválido",
                "text"  => "No se puede reversar un egreso con total cero.",
                "type"  => "error"
            ]);
        }

        $resultReversion = egresosContabilidadModelo::validar_reversion_egreso_existente_modelo($egresos_id, $empresa_id);

        if($resultReversion && $resultReversion->num_rows > 0){
            return mainModel::showNotification([
                "title" => "Reversión ya registrada",
                "text"  => "Este egreso ya tiene un ingreso de reversión registrado.",
                "type"  => "warning",
                "funcion" => "listar_gastos_contabilidad();total_gastos_footer();"
            ]);
        }

        $consultaSaldo = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id, $empresa_id);
        $saldoAnterior = 0;

        if($consultaSaldo && $consultaSaldo->num_rows > 0){
            $lastSaldo = $consultaSaldo->fetch_assoc();
            $saldoAnterior = isset($lastSaldo['saldo']) ? (float)$lastSaldo['saldo'] : 0;
        }

        $ingresoMov = $total;
        $egresoMov = 0;
        $nuevoSaldo = $saldoAnterior + $ingresoMov;

        $ingresos_id_reversion = mainModel::correlativo("ingresos_id", "ingresos");

        $facturaIngreso = "REV-EGR-".$egresos_id;
        $facturaIngreso = substr($facturaIngreso, 0, 20);

        $nombreProveedor = egresosContabilidadModelo::getProveedorNombreById($proveedores_id);

        $obsIngreso = "Reversión egreso #{$egresos_id}";

        if($facturaOriginal != ""){
            $obsIngreso .= " Factura original: {$facturaOriginal}.";
        }

        if($nombreProveedor != ""){
            $obsIngreso .= " Proveedor: {$nombreProveedor}.";
        }

        if($observacionOriginal != ""){
            $obsIngreso .= " ".$observacionOriginal;
        }

        $obsIngreso = substr($obsIngreso, 0, 150);

        $datosIngreso = [
            "ingresos_id" => $ingresos_id_reversion,
            "cuentas_id" => $cuentas_id,
            "clientes_id" => 0,
            "empresa_id" => $empresa_id,
            "tipo_ingreso" => 2,
            "fecha" => $fecha,
            "factura" => $facturaIngreso,
            "subtotal" => $subtotal,
            "descuento" => $descuento,
            "nc" => $nc,
            "isv" => $isv,
            "total" => $total,
            "observacion" => $obsIngreso,
            "estado" => 1,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ];

        $datosMov = [
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "ingreso" => $ingresoMov,
            "egreso" => $egresoMov,
            "saldo" => $nuevoSaldo,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ];

        $procesar = egresosContabilidadModelo::reversar_egreso_con_ingreso_modelo(
            $datosIngreso,
            $datosMov
        );

        if(!is_array($procesar) || empty($procesar['success'])){
            $mensajeError = isset($procesar['message']) ? $procesar['message'] : "No se pudo registrar la reversión del egreso.";

            return mainModel::showNotification([
                "title" => "Error",
                "text"  => $mensajeError,
                "type"  => "error"
            ]);
        }

        mainModel::guardarHistorial([
            "modulo"           => 'Egresos Contabilidad',
            "colaboradores_id" => $colaboradores_id,
            "status"           => "Reversión",
            "observacion"      => "Egreso ID {$egresos_id} reversado con ingreso ID {$ingresos_id_reversion} por L {$total}.",
            "fecha_registro"   => date("Y-m-d H:i:s")
        ]);

        return mainModel::showNotification([
            "type"    => "success",
            "title"   => "Egreso reversado",
            "text"    => "El egreso quedó activo y se registró el ingreso de reversión con su movimiento de cuenta.",
            "form"    => "formEgresosContables",
            "funcion" => "listar_gastos_contabilidad();total_gastos_footer();",
            "modal"   => "modalEgresosContables"
        ]);
    }
}
