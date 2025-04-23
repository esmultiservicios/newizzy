<?php
if ($peticionAjax) {
    require_once '../modelos/empresaModelo.php';
} else {
    require_once './modelos/empresaModelo.php';
}

class empresaControlador extends empresaModelo
{
    public function agregar_empresa_controlador()
    {
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

        // Limpieza de datos
        $razon_social = mainModel::cleanString($_POST['empresa_razon_social']);
        $empresa = is_array($_POST['empresa_empresa']) ? implode(',', $_POST['empresa_empresa']) : mainModel::cleanString($_POST['empresa_empresa']);
        $rtn = is_array($_POST['rtn_empresa']) ? implode(',', $_POST['rtn_empresa']) : mainModel::cleanString($_POST['rtn_empresa']);
        $otra_informacion = mainModel::cleanString($_POST['empresa_otra_informacion']);
        $eslogan = mainModel::cleanString($_POST['empresa_eslogan']);
        $correo = mainModel::cleanString($_POST['correo_empresa']);
        $telefono = is_array($_POST['telefono_empresa']) ? implode(',', $_POST['telefono_empresa']) : mainModel::cleanString($_POST['telefono_empresa']);
        $celular = is_array($_POST['empresa_celular']) ? implode(',', $_POST['empresa_celular']) : mainModel::cleanString($_POST['empresa_celular']);
        $ubicacion = is_array($_POST['direccion_empresa']) ? implode(',', $_POST['direccion_empresa']) : mainModel::cleanString($_POST['direccion_empresa']);
        $horario = is_array($_POST['horario_empresa']) ? implode(',', $_POST['horario_empresa']) : mainModel::cleanString($_POST['horario_empresa']);
        $facebook = is_array($_POST['facebook_empresa']) ? implode(',', $_POST['facebook_empresa']) : mainModel::cleanString($_POST['facebook_empresa']);
        $sitioweb = is_array($_POST['sitioweb_empresa']) ? implode(',', $_POST['sitioweb_empresa']) : mainModel::cleanString($_POST['sitioweb_empresa']);
    
        // Manejo de imágenes
        $imageFilename = $this->procesarImagen('logotipo', 'logo_');
        $imageFilenameFirma = $this->procesarImagen('firma_documento', 'firma_');
    
        $datos = [
            'logotipo' => $imageFilename,
            'firma_documento' => $imageFilenameFirma,
            'razon_social' => $razon_social,
            'empresa' => $empresa,
            'rtn' => $rtn,
            'otra_informacion' => $otra_informacion,
            'eslogan' => $eslogan,
            'correo' => $correo,
            'telefono' => $telefono,
            'celular' => $celular,
            'ubicacion' => $ubicacion,
            'usuario' => $_SESSION['colaborador_id_sd'],
            'estado' => 1,
            'horario' => $horario,
            'facebook' => $facebook,
            'sitioweb' => $sitioweb,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'MostrarFirma' => 1,
        ];
    
        // Validar límite de perfiles
        $limite = $this->validarLimitePerfiles();
        if ($limite !== true) {
            return $limite;
        }
    
        // Validar empresa existente
        if (empresaModelo::valid_empresa_modelo($rtn)->num_rows > 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Registro ya existe",
                "text" => "Lo sentimos, este RTN ya está registrado",
                "form" => "formEmpresa"
            ]);
        }
    
        // Registrar empresa
        if (!empresaModelo::agregar_empresa_modelo($datos)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo registrar la empresa",
                "form" => "formEmpresa"
            ]);
        }
    
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Empresa registrada correctamente",
            "form" => "formEmpresa",
            "funcion" => "listar_empresa();"
        ]);
    }
    
    public function edit_empresa_controlador()
    {
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

        $empresa_id = $_POST['empresa_id'];
        
        // Obtener datos actuales
        $empresaActual = empresaModelo::getImage($empresa_id)->fetch_assoc();
        
        // Procesar imágenes
        $imageFilename = $this->procesarImagenEdicion('logotipo', 'logo_', $empresaActual['logotipo']);
        $imageFilenameFirma = $this->procesarImagenEdicion('firma_documento', 'firma_', $empresaActual['firma_documento']);
        
        // Preparar datos
        $datos = [
            'logotipo' => $imageFilename,
            'firma_documento' => $imageFilenameFirma,
            'empresa_id' => $empresa_id,
            'razon_social' => mainModel::cleanString($_POST['empresa_razon_social']),
            'empresa' => mainModel::cleanString($_POST['empresa_empresa']),
            'rtn' => mainModel::cleanString($_POST['rtn_empresa']),
            'otra_informacion' => mainModel::cleanString($_POST['empresa_otra_informacion']),
            'eslogan' => mainModel::cleanString($_POST['empresa_eslogan']),
            'correo' => mainModel::cleanStringStrtolower($_POST['correo_empresa']),
            'telefono' => mainModel::cleanString($_POST['telefono_empresa']),
            'celular' => mainModel::cleanString($_POST['empresa_celular']),
            'ubicacion' => mainModel::cleanString($_POST['direccion_empresa']),
            'usuario' => $_SESSION['colaborador_id_sd'],
            'horario' => mainModel::cleanString($_POST['horario_empresa']),
            'facebook' => mainModel::cleanString($_POST['facebook_empresa']),
            'sitioweb' => mainModel::cleanString($_POST['sitioweb_empresa']),
            'estado' => isset($_POST['empresa_activo']) ? $_POST['empresa_activo'] : 2,
            'cargarLogo' => !empty($_FILES['logotipo']['tmp_name']),
            'cargarFirma' => !empty($_FILES['firma_documento']['tmp_name'])
        ];

        if (!empresaModelo::edit_empresa_modelo($datos)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo actualizar la empresa",
                "form" => "formEmpresa"
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Empresa actualizada correctamente",
            "form" => "formEmpresa",
            "funcion" => "listar_empresa();"
        ]);
    }

    public function delete_empresa_controlador()
    {
        $empresa_id = $_POST['empresa_id'];

        // Validar si la empresa tiene datos asociados
        if (empresaModelo::valid_user_secuencia_user($empresa_id)->num_rows > 0) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "No se puede eliminar",
                "text" => "La empresa tiene información asociada"
            ]);
        }

        // Eliminar imágenes
        $empresa = empresaModelo::getImage($empresa_id)->fetch_assoc();
        $this->eliminarImagenSiExiste($empresa['logotipo']);
        $this->eliminarImagenSiExiste($empresa['firma_documento']);

        if (!empresaModelo::delete_empresa_modelo($empresa_id)) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo eliminar la empresa"
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Eliminación exitosa",
            "text" => "Empresa eliminada correctamente",
            "funcion" => "listar_empresa();"
        ]);
    }

    // Métodos auxiliares privados
    
    private function validarLimitePerfiles()
    {
        $cantidadPerfilesPlan = empresaModelo::cantidad_perfiles_modelo()->fetch_assoc();
        $cantidadPerfilesPermitidos = $cantidadPerfilesPlan ? (int)$cantidadPerfilesPlan['perfiles'] : 1;
        
        $cantidadPerfilesRegistradosData = empresaModelo::getTotalEmpresasRegistradas()->fetch_assoc();
        $cantidadPerfilesRegistrados = $cantidadPerfilesRegistradosData ? (int)$cantidadPerfilesRegistradosData['total'] : 0;

        if ($cantidadPerfilesRegistrados >= $cantidadPerfilesPermitidos) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Límite alcanzado",
                "text" => "Su plan solo permite $cantidadPerfilesPermitidos perfiles. Contáctese con ventas para ampliar."
            ]);
        }
        
        return true;
    }

    private function procesarImagen($campo, $prefijo)
    {
        if (empty($_FILES[$campo]['tmp_name'])) {
            return $campo === 'logotipo' ? 'image_preview.png' : '';
        }

        $digits = 3;
        $valor = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
        $directorio = '../vistas/plantilla/img/logos/';
        $extension = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
        $nombreArchivo = $prefijo . $valor . '.' . $extension;
        $ruta = $directorio . $nombreArchivo;

        // Asegurar nombre único
        while (file_exists($ruta)) {
            $valor = rand(pow(10, $digits - 1), pow(10, $digits) - 1);
            $nombreArchivo = $prefijo . $valor . '.' . $extension;
            $ruta = $directorio . $nombreArchivo;
        }

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $ruta)) {
            return $nombreArchivo;
        }

        return $campo === 'logotipo' ? 'image_preview.png' : '';
    }

    private function procesarImagenEdicion($campo, $prefijo, $imagenActual)
    {
        if (empty($_FILES[$campo]['tmp_name'])) {
            return $imagenActual;
        }

        // Eliminar imagen anterior si no es la predeterminada
        if ($imagenActual && ($campo !== 'logotipo' || $imagenActual !== 'image_preview.png')) {
            $this->eliminarImagenSiExiste($imagenActual);
        }

        return $this->procesarImagen($campo, $prefijo);
    }

    private function eliminarImagenSiExiste($nombreArchivo)
    {
        if ($nombreArchivo && $nombreArchivo !== 'image_preview.png') {
            $ruta = '../vistas/plantilla/img/logos/' . $nombreArchivo;
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
    }
}