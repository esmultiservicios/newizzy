<?php
if ($peticionAjax) {
    require_once '../modelos/empresaModelo.php';
} else {
    require_once './modelos/empresaModelo.php';
}

/**
 * Se asume que ya tienes definida:
 * define('ENTERPRISE_PATH', '/vistas/plantilla/img/enterprise/');
 * y que el directorio tiene permisos de escritura.
 */

class empresaControlador extends empresaModelo
{
    /* =========================================================
       UTILIDADES DE ARCHIVOS (NOMBRES CORTOS Y ÚNICOS)
       ========================================================= */
    /**
     * Genera un nombre corto y único con prefijo y misma extensión.
     * Ej.: logo_a1b2c3.png
     */
    private function generarNombreCortoUnico(string $prefijo, string $extension, string $destinoBase): string
    {
        $extension = ltrim(strtolower($extension), '.');
        do {
            // id corto de 6 chars base16 (aleatorio)
            $id = substr(bin2hex(random_bytes(4)), 0, 6);
            $nombre = "{$prefijo}_{$id}.{$extension}";
        } while (file_exists($destinoBase . $nombre));
        return $nombre;
    }

    /**
     * Sube imagen con validaciones de tipo/tamaño y genera nombre corto único.
     * Retorna el nombre de archivo guardado. Si no hay archivo y $defaultSiNoHay
     * es true, retorna 'image_preview.png', si no, retorna ''.
     */
    private function subirImagenUnica(string $inputName, string $prefijo, bool $defaultSiNoHay = true): string
    {
        if (empty($_FILES[$inputName]['name'])) {
            return $defaultSiNoHay ? 'image_preview.png' : '';
        }

        // Validaciones
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type     = $_FILES[$inputName]['type'] ?? '';
        $file_size     = $_FILES[$inputName]['size'] ?? 0;

        if (!in_array($file_type, $allowed_types)) {
            return $defaultSiNoHay ? 'image_preview.png' : '';
        }

        if ($file_size > 2 * 1024 * 1024) { // 2MB
            return $defaultSiNoHay ? 'image_preview.png' : '';
        }

        $destinoBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . ENTERPRISE_PATH;
        if (!is_dir($destinoBase)) {
            @mkdir($destinoBase, 0775, true);
        }

        // Obtener extensión del nombre original
        $original = basename($_FILES[$inputName]['name']);
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        if ($ext === '') {
            // fallback simple por tipo MIME
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $ext = $map[$file_type] ?? 'jpg';
        }

        $nombreFinal = $this->generarNombreCortoUnico($prefijo, $ext, $destinoBase);
        $rutaFinal   = $destinoBase . $nombreFinal;

        if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $rutaFinal)) {
            return $defaultSiNoHay ? 'image_preview.png' : '';
        }

        return $nombreFinal;
    }

    /**
     * Elimina físicamente una imagen si existe y no es la imagen por defecto.
     */
    private function eliminarFisicoSiAplica(?string $nombreArchivo): void
    {
        if (empty($nombreArchivo) || $nombreArchivo === 'image_preview.png') return;
        $ruta = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . ENTERPRISE_PATH . $nombreArchivo;
        if (file_exists($ruta)) @unlink($ruta);
    }

    /* =========================================================
       CONTROLADORES
       ========================================================= */

    public function agregar_empresa_controlador()
    {
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            $resp = mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
            echo $resp;
            exit;
        }

        // Limpieza de datos
        $razon_social     = mainModel::cleanString($_POST['empresa_razon_social']);
        $empresa          = is_array($_POST['empresa_empresa']) ? implode(',', $_POST['empresa_empresa']) : mainModel::cleanString($_POST['empresa_empresa']);
        $rtn              = is_array($_POST['rtn_empresa']) ? implode(',', $_POST['rtn_empresa']) : mainModel::cleanString($_POST['rtn_empresa']);
        $otra_informacion = mainModel::cleanString($_POST['empresa_otra_informacion']);
        $eslogan          = mainModel::cleanString($_POST['empresa_eslogan']);
        $correo           = mainModel::cleanString($_POST['correo_empresa']);
        $telefono         = is_array($_POST['telefono_empresa']) ? implode(',', $_POST['telefono_empresa']) : mainModel::cleanString($_POST['telefono_empresa']);
        $celular          = is_array($_POST['empresa_celular']) ? implode(',', $_POST['empresa_celular']) : mainModel::cleanString($_POST['empresa_celular']);
        $ubicacion        = is_array($_POST['direccion_empresa']) ? implode(',', $_POST['direccion_empresa']) : mainModel::cleanString($_POST['direccion_empresa']);
        $horario          = is_array($_POST['horario_empresa']) ? implode(',', $_POST['horario_empresa']) : mainModel::cleanString($_POST['horario_empresa']);
        $facebook         = is_array($_POST['facebook_empresa']) ? implode(',', $_POST['facebook_empresa']) : mainModel::cleanString($_POST['facebook_empresa']);
        $sitioweb         = is_array($_POST['sitioweb_empresa']) ? implode(',', $_POST['sitioweb_empresa']) : mainModel::cleanString($_POST['sitioweb_empresa']);

        /* ===== Validaciones previas (RTN y plan) ===== */
        // RTN único (return + exit para cortar el flujo)
        $rtnExiste = empresaModelo::valid_empresa_modelo($rtn);
        if ($rtnExiste && $rtnExiste->num_rows > 0) {
            $resp = mainModel::showNotification([
                "type"  => "error",
                "title" => "Registro ya existe",
                "text"  => "Lo sentimos, este RTN ya está registrado",
                "form"  => "formEmpresa"
            ]);
            echo $resp;
            exit;
        }

        // Validación por plan
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();

        if (isset($planConfig['empresas'])) {
            $limiteEmpresas = (int)$planConfig['empresas'];

            if ($limiteEmpresas === 0) {
                $resp = $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Acceso restringido",
                    "text"  => "Su plan actual no permite registrar empresas."
                ]);
                echo $resp;
                exit;
            }

            $totalRegistrados = (int)empresaModelo::getTotalEmpresasRegistradas();
            if ($totalRegistrados >= $limiteEmpresas) {
                $resp = $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Límite alcanzado",
                    "text"  => "Límite de empresas alcanzado (Máximo: $limiteEmpresas). Actualiza tu plan."
                ]);
                echo $resp;
                exit;
            }
        }

        /* ===== Subida de archivos con nombre único corto ===== */
        $logo_file  = $this->subirImagenUnica('logotipo', 'logo', true);   // default "image_preview.png" si no hay archivo
        $firma_file = $this->subirImagenUnica('firma_documento', 'firma', false); // default '' si no hay archivo

        $datos = [
            'logotipo'         => $logo_file,
            'firma_documento'  => $firma_file,
            'razon_social'     => $razon_social,
            'empresa'          => $empresa,
            'rtn'              => $rtn,
            'otra_informacion' => $otra_informacion,
            'eslogan'          => $eslogan,
            'correo'           => $correo,
            'telefono'         => $telefono,
            'celular'          => $celular,
            'ubicacion'        => $ubicacion,
            'usuario'          => $_SESSION['colaborador_id_sd'],
            'estado'           => 1,
            'horario'          => $horario,
            'facebook'         => $facebook,
            'sitioweb'         => $sitioweb,
            'fecha_registro'   => date('Y-m-d H:i:s'),
            'MostrarFirma'     => 1,
        ];

        // Registrar empresa
        if (!empresaModelo::agregar_empresa_modelo($datos)) {
            // Si falla el insert, limpiar archivos subidos
            $this->eliminarFisicoSiAplica($logo_file);
            $this->eliminarFisicoSiAplica($firma_file);

            $resp = mainModel::showNotification([
                "type"  => "error",
                "title" => "Error",
                "text"  => "No se pudo registrar la empresa",
                "form"  => "formEmpresa"
            ]);
            echo $resp;
            exit;
        }

        $resp = mainModel::showNotification([
            "type"    => "success",
            "title"   => "Registro exitoso",
            "text"    => "Empresa registrada correctamente",
            "form"    => "formEmpresa",
            "funcion" => "listar_empresa();"
        ]);
        echo $resp;
        exit;
    }

    public function edit_empresa_controlador()
    {
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            $resp = mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
            echo $resp;
            exit;
        }

        $empresa_id = $_POST['empresa_id'];

        // Obtener datos actuales
        $empresaActual = empresaModelo::getImage($empresa_id)->fetch_assoc();
        $logoActual  = $empresaActual['logotipo'] ?? 'image_preview.png';
        $firmaActual = $empresaActual['firma_documento'] ?? '';

        // Validar RTN duplicado en edición (excluyendo el actual)
        $rtnNuevo = mainModel::cleanString($_POST['rtn_empresa']);
        if (!empty($rtnNuevo)) {
            $existe = empresaModelo::valid_empresa_modelo($rtnNuevo);
            if ($existe && $existe->num_rows > 0) {
                // Si el RTN encontrado no pertenece a esta empresa, bloquear
                $fila = $existe->fetch_assoc();
                if (isset($fila['empresa_id']) && (int)$fila['empresa_id'] !== (int)$empresa_id) {
                    $resp = mainModel::showNotification([
                        "type"  => "error",
                        "title" => "RTN duplicado",
                        "text"  => "El RTN ingresado ya está registrado en otra empresa",
                        "form"  => "formEmpresa"
                    ]);
                    echo $resp;
                    exit;
                }
            }
        }

        // Procesar nuevas imágenes SOLO si vienen
        $subioLogo  = !empty($_FILES['logotipo']['name']);
        $subioFirma = !empty($_FILES['firma_documento']['name']);

        $logoNuevo  = $logoActual;
        $firmaNueva = $firmaActual;

        // Subir primero, y si falla el update, borrar (rollback)
        $archivosNuevos = [];

        if ($subioLogo) {
            $tmp = $this->subirImagenUnica('logotipo', 'logo', true);
            if ($tmp === 'image_preview.png') {
                $resp = mainModel::showNotification([
                    "title" => "Error en imagen",
                    "text"  => "No se pudo procesar el logotipo",
                    "type"  => "error"
                ]);
                echo $resp;
                exit;
            }
            $logoNuevo = $tmp;
            $archivosNuevos[] = $logoNuevo;
        }

        if ($subioFirma) {
            $tmp = $this->subirImagenUnica('firma_documento', 'firma', false);
            if ($tmp === '' && !empty($_FILES['firma_documento']['name'])) {
                // venía archivo pero falló la subida
                foreach ($archivosNuevos as $nf) $this->eliminarFisicoSiAplica($nf);

                $resp = mainModel::showNotification([
                    "title" => "Error en imagen",
                    "text"  => "No se pudo procesar la imagen de la firma",
                    "type"  => "error"
                ]);
                echo $resp;
                exit;
            }
            if ($tmp !== '') {
                $firmaNueva = $tmp;
                $archivosNuevos[] = $firmaNueva;
            }
        }

        // Preparar datos
        $datos = [
            'empresa_id'       => $empresa_id,
            'logotipo'         => $logoNuevo,
            'firma_documento'  => $firmaNueva,
            'razon_social'     => mainModel::cleanString($_POST['empresa_razon_social']),
            'empresa'          => mainModel::cleanString($_POST['empresa_empresa']),
            'rtn'              => $rtnNuevo,
            'otra_informacion' => mainModel::cleanString($_POST['empresa_otra_informacion']),
            'eslogan'          => mainModel::cleanString($_POST['empresa_eslogan']),
            'correo'           => mainModel::cleanStringStrtolower($_POST['correo_empresa']),
            'telefono'         => mainModel::cleanString($_POST['telefono_empresa']),
            'celular'          => mainModel::cleanString($_POST['empresa_celular']),
            'ubicacion'        => mainModel::cleanString($_POST['direccion_empresa']),
            'usuario'          => $_SESSION['colaborador_id_sd'],
            'horario'          => mainModel::cleanString($_POST['horario_empresa']),
            'facebook'         => mainModel::cleanString($_POST['facebook_empresa']),
            'sitioweb'         => mainModel::cleanString($_POST['sitioweb_empresa']),
            'estado'           => isset($_POST['empresa_activo']) ? $_POST['empresa_activo'] : 2,
            // banderas (por si tu modelo las usa)
            'cargarLogo'       => $subioLogo,
            'cargarFirma'      => $subioFirma
        ];

        // Actualizar
        if (!empresaModelo::edit_empresa_modelo($datos)) {
            // Rollback de archivos nuevos
            foreach ($archivosNuevos as $nf) $this->eliminarFisicoSiAplica($nf);

            $resp = mainModel::showNotification([
                "type"  => "error",
                "title" => "Error",
                "text"  => "No se pudo actualizar la empresa",
                "form"  => "formEmpresa"
            ]);
            echo $resp;
            exit;
        }

        // Éxito: eliminar anteriores si fueron reemplazados
        if ($subioLogo && $logoNuevo !== $logoActual) {
            $this->eliminarFisicoSiAplica($logoActual);
        }
        if ($subioFirma && $firmaNueva !== $firmaActual) {
            $this->eliminarFisicoSiAplica($firmaActual);
        }

        $resp = mainModel::showNotification([
            "type"    => "success",
            "title"   => "Actualización exitosa",
            "text"    => "Empresa actualizada correctamente",
            "form"    => "formEmpresa",
            "funcion" => "listar_empresa();CleanEnterpriseImage();"
        ]);
        echo $resp;
        exit;
    }

    public function delete_empresa_controlador()
    {
        // Responder siempre JSON
        header('Content-Type: application/json; charset=UTF-8');
    
        // (Opcional pero recomendado) Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            echo json_encode([
                "status"  => "error",
                "title"   => "Error de sesión",
                "message" => $validacion['mensaje']
            ]);
            exit;
        }
    
        // Validar parámetro
        if (!isset($_POST['empresa_id']) || !is_numeric($_POST['empresa_id'])) {
            echo json_encode([
                "status"  => "error",
                "title"   => "Solicitud inválida",
                "message" => "Falta el ID de la empresa."
            ]);
            exit;
        }
    
        $empresa_id = (int)$_POST['empresa_id'];
    
        // Validar dependencias/relaciones
        if (empresaModelo::valid_user_secuencia_user($empresa_id)->num_rows > 0) {
            echo json_encode([
                "status"  => "error",
                "title"   => "No se puede eliminar",
                "message" => "La empresa tiene información asociada"
            ]);
            exit;
        }
    
        // Obtener archivos actuales ANTES de borrar en DB
        $logo  = null;
        $firma = null;
        $empresaRes = empresaModelo::getImage($empresa_id);
        if ($empresaRes && $empresaRes->num_rows > 0) {
            $row   = $empresaRes->fetch_assoc();
            $logo  = $row['logotipo'] ?? null;
            $firma = $row['firma_documento'] ?? null;
        } else {
            echo json_encode([
                "status"  => "error",
                "title"   => "No encontrado",
                "message" => "La empresa no existe o ya fue eliminada."
            ]);
            exit;
        }
    
        // Eliminar en DB
        if (!empresaModelo::delete_empresa_modelo($empresa_id)) {
            echo json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo eliminar la empresa"
            ]);
            exit;
        }
    
        // Eliminar archivos físicos (en ENTERPRISE_PATH)
        $this->eliminarFisicoSiAplica($logo);
        $this->eliminarFisicoSiAplica($firma);
    
        echo json_encode([
            "status"  => "success",
            "title"   => "Eliminación exitosa",
            "message" => "Empresa eliminada correctamente"
        ]);
        exit;
    }    
}