<?php
// productosControlador.php
if ($peticionAjax) {
    require_once '../modelos/productosModelo.php';
} else {
    require_once './modelos/productosModelo.php';
}

class productosControlador extends productosModelo
{
    private function textoISVPost($campo, $fallback)
    {
        $texto = isset($_POST[$campo]) ? trim(strip_tags((string)$_POST[$campo])) : '';
        return $texto !== '' ? $texto : $fallback;
    }

    private function normalizarISVProducto(&$isv1, &$isv2)
    {
        $calcular = isset($_POST['producto_isv_factura']);

        if (!$calcular) {
            $isv1 = 0;
            $isv2 = 0;
            return null;
        }

        // Si el formulario llega sin tipo por un navegador/cache antiguo,
        // se conserva el comportamiento solicitado: ISV 1 por defecto.
        if ($isv1 === 0 && $isv2 === 0) {
            $isv1 = 1;
        }

        if ($isv1 === 1 && $isv2 === 1) {
            $texto1 = $this->textoISVPost('producto_isv1_texto', 'ISV 1');
            $texto2 = $this->textoISVPost('producto_isv2_texto', 'ISV 2');
            return mainModel::showNotification([
                "title" => "Error en ISV",
                "text"  => "Solo puede seleccionar un tipo de ISV ({$texto1} o {$texto2})",
                "type"  => "error"
            ]);
        }

        return null;
    }

    private function procesarImagenProducto($campo = 'imagen_producto', $imagenActual = 'image_preview.png')
    {
        if (!isset($_FILES[$campo]) || empty($_FILES[$campo]['name'])) {
            return [
                'ok' => true,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => null
            ];
        }

        $upload = $_FILES[$campo];
        $error = isset($upload['error']) ? (int)$upload['error'] : UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            $mensajes = [
                UPLOAD_ERR_INI_SIZE => 'La imagen supera el tamaño permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'La imagen se cargó de forma incompleta',
                UPLOAD_ERR_NO_FILE => 'No se recibió la imagen seleccionada',
                UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene disponible la carpeta temporal de carga',
                UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir la imagen en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión del servidor detuvo la carga de la imagen'
            ];
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => $mensajes[$error] ?? 'No se pudo procesar la imagen seleccionada',
                    'type' => 'error'
                ])
            ];
        }

        $tmp = $upload['tmp_name'] ?? '';
        $size = isset($upload['size']) ? (int)$upload['size'] : 0;
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'La imagen recibida no es un archivo de carga válido',
                    'type' => 'error'
                ])
            ];
        }

        if ($size <= 0 || $size > 2 * 1024 * 1024) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'La imagen debe tener contenido y no exceder 2MB',
                    'type' => 'error'
                ])
            ];
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string)mime_content_type($tmp);
        }
        if ($mime === '') {
            $mime = (string)($upload['type'] ?? '');
        }

        $tipos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];
        if (!isset($tipos[$mime])) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'Solo se permiten imágenes JPG, PNG, GIF o WEBP',
                    'type' => 'error'
                ])
            ];
        }

        $destinoBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . trim(PRODUCT_PATH, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($destinoBase) && !@mkdir($destinoBase, 0775, true)) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'No se pudo crear el directorio de imágenes de productos',
                    'type' => 'error'
                ])
            ];
        }
        if (!is_writable($destinoBase)) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'El directorio de imágenes de productos no tiene permisos de escritura',
                    'type' => 'error'
                ])
            ];
        }

        do {
            try {
                $idcorto = substr(bin2hex(random_bytes(6)), 0, 10);
            } catch (Exception $e) {
                $idcorto = uniqid('', true);
                $idcorto = preg_replace('/[^a-zA-Z0-9]/', '', $idcorto);
            }
            $archivo = 'prod_' . $idcorto . '.' . $tipos[$mime];
            $destino = $destinoBase . $archivo;
        } while (file_exists($destino));

        if (!move_uploaded_file($tmp, $destino)) {
            return [
                'ok' => false,
                'archivo' => $imagenActual,
                'subio' => false,
                'respuesta' => mainModel::showNotification([
                    'title' => 'Error en imagen',
                    'text' => 'No se pudo guardar la imagen en el directorio de productos',
                    'type' => 'error'
                ])
            ];
        }

        return [
            'ok' => true,
            'archivo' => $archivo,
            'subio' => true,
            'respuesta' => null
        ];
    }
    /* =========================
       AGREGAR PRODUCTO
       ========================= */
    public function agregar_productos_controlador()
    {
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $empresa            = $_SESSION['empresa_id_sd'];
        $almacen_id         = 1;
        $medida_id          = mainModel::cleanStringConverterCase($_POST['medida'] ?? 0);
        $producto_superior  = mainModel::cleanString($_POST['producto_superior']) == '' ? 0 : mainModel::cleanString($_POST['producto_superior']);
        $categoria_id       = mainModel::cleanStringConverterCase($_POST['producto_categoria'] ?? 0);
        $tipo_producto      = mainModel::cleanStringConverterCase($_POST['tipo_producto'] ?? 1);
        $nombre             = mainModel::cleanString($_POST['producto']);
        $descripcion        = mainModel::cleanString($_POST['descripcion']);
        $cantidad           = 0;
        $precio_compra      = mainModel::cleanString($_POST['precio_compra'] === '' ? 0 : $_POST['precio_compra']);
        $porcentaje_venta   = mainModel::cleanString($_POST['porcentaje_venta'] === '' ? 0 : $_POST['porcentaje_venta']);
        $precio_venta       = mainModel::cleanString($_POST['precio_venta'] === '' ? 0 : $_POST['precio_venta']);
        $cantidad_mayoreo   = mainModel::cleanString($_POST['cantidad_mayoreo'] === '' ? 3 : $_POST['cantidad_mayoreo']);
        $precio_mayoreo     = mainModel::cleanString($_POST['precio_mayoreo'] === '' ? 0 : $_POST['precio_mayoreo']);
        $cantidad_minima    = mainModel::cleanString($_POST['cantidad_minima'] === '' ? 0 : $_POST['cantidad_minima']);
        $cantidad_maxima    = mainModel::cleanString($_POST['cantidad_maxima'] === '' ? 0 : $_POST['cantidad_maxima']);

        $restaurante = isset($_POST['producto_restaurante']) ? 1 : 0;
        $isv1        = isset($_POST['producto_isv1']) ? 1 : 0;
        $isv2        = isset($_POST['producto_isv2']) ? 1 : 0;

        // Normalizar ISV: con cálculo activo debe existir exactamente un tipo.
        $respuestaISV = $this->normalizarISVProducto($isv1, $isv2);
        if ($respuestaISV !== null) {
            return $respuestaISV;
        }

        // Requeridos
        $requiredFields = [
            'producto'      => 'Nombre del producto',
            'tipo_producto' => 'Tipo de producto',
            'medida'        => 'Medida',
        ];
        $missingFields = [];
        foreach ($requiredFields as $field => $name) {
            if (empty($_POST[$field]) || $_POST[$field] == '0') $missingFields[] = $name;
        }
        if (isset($_POST['tipo_producto'])) {
            $tipoProducto = mainModel::cleanStringConverterCase($_POST['tipo_producto']);
            if ($tipoProducto != 'Servicio' && (empty($_POST['producto_categoria']) || $_POST['producto_categoria'] == '0')) {
                $missingFields[] = 'Categoría';
            }
        }
        if (!empty($missingFields)) {
            return mainModel::showNotification([
                "title" => "Campos requeridos",
                "text"  => "Los siguientes campos son obligatorios: " . implode(", ", $missingFields),
                "type"  => "error"
            ]);
        }

        // Precios válidos
        // Validar que no esté vacío ANTES de convertir
        $precioVentaString = mainModel::cleanString($_POST['precio_venta']);
        if ($precioVentaString === '') {
            return mainModel::showNotification([
                "title" => "Campos requeridos",
                "text"  => "Los siguientes campos son obligatorios: Precio de venta",
                "type"  => "error"
            ]);
        }

        $precioVenta = (float)$precioVentaString;
        if ($precioVenta < 0) {
            return mainModel::showNotification([
                "title" => "Error en precios",
                "text"  => "El precio de venta no puede ser negativo",
                "type"  => "error"
            ]);
        }

        // Código de barras
        if ($_POST['bar_code_product'] == '') {
            $flag_barcode = true;
            while ($flag_barcode) {
                $tmp = mainModel::generarCodigoBarra();
                $result_barcode = productosModelo::valid_bar_code_productos_modelo($tmp, $empresa);
                if ($result_barcode->num_rows == 0) {
                    $bar_code_product = $tmp;
                    $flag_barcode = false;
                }
            }
        } else {
            $bar_code_product = mainModel::cleanString($_POST['bar_code_product']);
        }

        /* =========================
           IMAGEN
           ========================= */
        $imagenProcesada = $this->procesarImagenProducto('imagen_producto', 'image_preview.png');
        if (!$imagenProcesada['ok']) {
            return $imagenProcesada['respuesta'];
        }
        $file = $imagenProcesada['archivo'];

        // Duplicados
        $result = productosModelo::valid_bar_code_productos_modelo($bar_code_product, $empresa);
        if ($result->num_rows > 0) {
            // rollback de imagen única si se subió
            if ($file !== 'image_preview.png') {
                $destinoBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/';
                if (file_exists($destinoBase . $file)) { @unlink($destinoBase . $file); }
            }
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "El código de barra ya existe",
                "type"  => "error"
            ]);
        }

        $result_nombre = productosModelo::valid_nombre_producto_modelo($nombre, $empresa);
        if ($result_nombre->num_rows > 0) {
            if ($file !== 'image_preview.png') {
                $destinoBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/';
                if (file_exists($destinoBase . $file)) { @unlink($destinoBase . $file); }
            }
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "El nombre de producto ya existe",
                "type"  => "error"
            ]);
        }

        // Límite del plan
        $mainModel  = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        if (isset($planConfig['productos'])) {
            $limiteProductos = (int)$planConfig['productos'];
            if ($limiteProductos === 0) {
                return $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Acceso restringido",
                    "text"  => "Su plan actual no permite registrar productos."
                ]);
            }
            $totalRegistrados = (int)productosModelo::getTotalProductosRegistrados();
            if ($totalRegistrados >= $limiteProductos) {
                return $mainModel->showNotification([
                    "type"  => "error",
                    "title" => "Límite alcanzado",
                    "text"  => "Límite de productos alcanzado (Máximo: $limiteProductos). Actualiza tu plan."
                ]);
            }
        }

        $colaborador_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date('Y-m-d H:i:s');
        $estado         = 1; // activo
        $isv_venta      = isset($_POST['producto_isv_factura']) ? 1 : 2;
        $isv_compra     = isset($_POST['producto_isv_compra'])  ? 1 : 2;

        // IMPORTANTE: mantenemos las claves que tu modelo espera
        $datos = [
            'bar_code_product'    => $bar_code_product,
            'almacen_id'          => $almacen_id,
            'medida_id'           => $medida_id,
            'id_producto_superior'=> $producto_superior,
            'categoria_id'        => $categoria_id,
            'tipo_producto'       => $tipo_producto,
            'nombre'              => $nombre,
            'descripcion'         => $descripcion,
            'precio_compra'       => $precio_compra,
            'porcentaje_venta'    => $porcentaje_venta,
            'precio_venta'        => $precio_venta,
            'cantidad_mayoreo'    => $cantidad_mayoreo,
            'precio_mayoreo'      => $precio_mayoreo,
            'cantidad_minima'     => $cantidad_minima,
            'cantidad_maxima'     => $cantidad_maxima,
            'colaborador_id'      => $colaborador_id,
            'fecha_registro'      => $fecha_registro,
            'estado'              => $estado,
            'isv_venta'           => $isv_venta,
            'isv_compra'          => $isv_compra,
            'file'                => $file,
            'empresa'             => $empresa,
            'restaurante'         => $restaurante,
            'isv1'                => $isv1,
            'isv2'                => $isv2,
        ];

        // Insert
        $query = productosModelo::agregar_productos_modelo($datos);
        if (!$query) {
            if ($file !== 'image_preview.png') {
                $destinoBase = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/';
                if (file_exists($destinoBase . $file)) { @unlink($destinoBase . $file); }
            }
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se pudo registrar el producto",
                "type"  => "error"
            ]);
        }

        // Movimientos iniciales si aplica
        $consulta_producto = productosModelo::consultar_codigo_producto($nombre)->fetch_assoc();
        $productos_id      = $consulta_producto['productos_id'] ?? 0;

        $tipo_productos = '';
        $result_tipo = productosModelo::tipo_producto_modelo($productos_id);
        if ($result_tipo && $result_tipo->num_rows > 0) {
            $val2 = $result_tipo->fetch_assoc();
            $tipo_productos = $val2['tipo_producto'] ?? '';
        }

        if ($cantidad > 0 && ($tipo_productos == 'Producto' || $tipo_productos == 'Insumos')) {
            $datos_mov = [
                'productos_id'     => $productos_id,
                'documento'        => 'Creacion de Producto',
                'cantidad_entrada' => $cantidad,
                'cantidad_salida'  => 0,
                'saldo'            => 0,
                'fecha_registro'   => $fecha_registro,
                'empresa'          => $empresa,
                'clientes_id'      => 0,
                'comentario'       => '',
                'almacen_id'       => $almacen_id
            ];
            productosModelo::agregar_movimientos_productos_modelo($datos_mov);
        }

        // Historial
        mainModel::guardarHistorial([
            "modulo"           => 'Productos',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status"           => "Registro",
            "observacion"      => "Se registró el producto {$datos['nombre']} con código {$datos['bar_code_product']}",
            "fecha_registro"   => date("Y-m-d H:i:s")
        ]);

        return mainModel::showNotification([
            "type"    => "success",
            "title"   => "Registro exitoso",
            "text"    => "Producto registrado correctamente",
            "form"    => "formProductos",
            "funcion" => "listar_productos();getProductos();getCategoriaProductos();getTipoProducto();getAlmacen();getMedida(0);getEmpresaProductos();ClenProductImage();"
        ]);
    }

    /* =========================
       EDITAR PRODUCTO
       ========================= */
    public function edit_productos_controlador()
    {
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }        

        // Requeridos
        $required = [
            'productos_id'  => "ID del producto",
            'producto'      => "Nombre del producto",
            'precio_compra' => "Precio de compra",
            'precio_venta'  => "Precio de venta"
        ];
        $missing = [];
        foreach ($required as $k => $label) {
            if (!isset($_POST[$k]) || $_POST[$k] === '') $missing[] = $label;
        }
        if (!empty($missing)) {
            return mainModel::showNotification([
                "title" => "Campos requeridos",
                "text"  => "Faltan los siguientes campos: ".implode(", ", $missing),
                "type"  => "error"
            ]);
        }

        // Datos
        $productos_id     = mainModel::cleanString($_POST['productos_id']);
        $nombre           = mainModel::cleanString($_POST['producto']);
        $descripcion      = mainModel::cleanString($_POST['descripcion'] ?? '');
        $precio_compra    = (float)mainModel::cleanString($_POST['precio_compra']);
        $porcentaje_venta = (float)mainModel::cleanString($_POST['porcentaje_venta'] ?? 0);
        $precio_venta     = (float)mainModel::cleanString($_POST['precio_venta']);
        $precio_mayoreo   = (float)mainModel::cleanString($_POST['precio_mayoreo'] ?? 0);
        $cantidad_mayoreo = (float)mainModel::cleanString($_POST['cantidad_mayoreo'] ?? 0);
        $cantidad_minima  = (int)mainModel::cleanString($_POST['cantidad_minima'] ?? 0);
        $cantidad_maxima  = (int)mainModel::cleanString($_POST['cantidad_maxima'] ?? 0);

        $restaurante = isset($_POST['producto_restaurante']) ? 1 : 0;
        $isv1        = isset($_POST['producto_isv1']) ? 1 : 0;
        $isv2        = isset($_POST['producto_isv2']) ? 1 : 0;

        // Normalizar ISV: con cálculo activo debe existir exactamente un tipo.
        $respuestaISV = $this->normalizarISVProducto($isv1, $isv2);
        if ($respuestaISV !== null) {
            return $respuestaISV;
        }

        if ($precio_compra < 0 || $precio_venta < 0) {
            return mainModel::showNotification([
                "title" => "Error en precios",
                "text"  => "Los precios no pueden ser negativos",
                "type"  => "error"
            ]);
        }

        // Leer imagen actual desde DB (file_name)
        $conn = mainModel::connection();
        $imagenActual = 'image_preview.png';
        $rsActual = $conn->query("SELECT file_name AS file FROM productos WHERE productos_id = '{$productos_id}' LIMIT 1");
        if ($rsActual && $rsActual->num_rows > 0) {
            $row = $rsActual->fetch_assoc();
            if (!empty($row['file'])) $imagenActual = $row['file'];
        }

        // Subida opcional de imagen, conservando la actual si no se seleccionó otra.
        $imagenProcesada = $this->procesarImagenProducto('imagen_producto', $imagenActual);
        if (!$imagenProcesada['ok']) {
            return $imagenProcesada['respuesta'];
        }
        $file = $imagenProcesada['archivo'];
        $subioNueva = (bool)$imagenProcesada['subio'];

        // Nombre duplicado (excluyendo el actual)
        $nombreExistente = productosModelo::valid_nombre_producto_modelo($nombre, $_SESSION['empresa_id_sd']);
        if ($nombreExistente->num_rows > 0) {
            $productoExistente = $nombreExistente->fetch_assoc();
            if (!empty($productoExistente['productos_id']) && (int)$productoExistente['productos_id'] !== (int)$productos_id) {
                if ($subioNueva) {
                    $nuevoPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/' . $file;
                    if (file_exists($nuevoPath)) { @unlink($nuevoPath); }
                }
                return mainModel::showNotification([
                    "title" => "Nombre duplicado",
                    "text"  => "Ya existe un producto con este nombre",
                    "type"  => "error"
                ]);
            }
        }

        // Estados según tu schema (1=activo, 2=inactivo)
        $estado     = isset($_POST['producto_activo']) ? 1 : 2;
        $isv_venta  = isset($_POST['producto_isv_factura']) ? 1 : 2;
        $isv_compra = isset($_POST['producto_isv_compra'])  ? 1 : 2;

        // Mantengo claves esperadas por tu modelo
        $datos = [
            'productos_id'     => $productos_id,
            'nombre'           => $nombre,
            'descripcion'      => $descripcion,
            'precio_compra'    => $precio_compra,
            'porcentaje_venta' => $porcentaje_venta,
            'precio_venta'     => $precio_venta,
            'precio_mayoreo'   => $precio_mayoreo,
            'cantidad_mayoreo'  => $cantidad_mayoreo,
            'cantidad_minima'  => $cantidad_minima,
            'cantidad_maxima'  => $cantidad_maxima,
            'estado'           => $estado,
            'isv_venta'        => $isv_venta,
            'isv_compra'       => $isv_compra,
            'file'             => $file,
            'cargarLogo'       => $subioNueva,
            'restaurante'      => $restaurante,
            'isv1'             => $isv1,
            'isv2'             => $isv2,
        ];

        // Update
        $query = productosModelo::edit_productos_modelo($datos);
        if ($query) {
            // Si hubo nueva imagen, borro la anterior (no borrar el placeholder)
            if ($subioNueva && $imagenActual && $imagenActual !== 'image_preview.png' && $imagenActual !== $file) {
                $old = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/' . $imagenActual;
                if (file_exists($old)) { @unlink($old); }
            }

            mainModel::guardarHistorial([
                "modulo"           => 'Productos',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status"           => "Actualización",
                "observacion"      => "Se actualizó el producto {$nombre} (ID: {$productos_id})",
                "fecha_registro"   => date("Y-m-d H:i:s")
            ]);

            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Producto actualizado",
                "text"    => "El producto se ha actualizado correctamente",
                "funcion" => "listar_productos();getEmpresaProductos();getCategoriaProductos();getAlmacen();getTipoProducto();getMedida(1);"
            ]);
        }

        // rollback si falló y había imagen nueva
        if ($subioNueva) {
            $nuevoPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/' . $file;
            if (file_exists($nuevoPath)) { @unlink($nuevoPath); }
        }

        return mainModel::showNotification([
            "title" => "Error al actualizar",
            "text"  => "No se pudo actualizar el producto",
            "type"  => "error"
        ]);
    }

    /* =========================
       TRANSFERENCIA ENTRE BODEGAS
       ========================= */
    public function edit_bodega_productos_controlador()
    {
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $productos_id  = mainModel::cleanString($_POST['productos_id']);
        $bodega_actual = mainModel::cleanString($_POST['id_bodega_actual']);
        $bodega        = mainModel::cleanString($_POST['id_bodega']);
        $cantidad      = mainModel::cleanString($_POST['cantidad_movimiento']);
        $lote_id       = mainModel::cleanString($_POST['lote_id_productos']);
        $empresa_id    = mainModel::cleanString($_POST['empresa_id_productos']);
        $saldoProducto = 0;

        $comentario = isset($_POST['movimiento_comentario']) ? mainModel::cleanString($_POST['movimiento_comentario']) : '';
        $clientes_id = 0;

        $fecha_registro = date('Y-m-d H:i:s');

        // Verificamos producto hijo
        $result_productos = mainModel::getProductoHijo($productos_id);
        $procesosHijosExitosos = true;

        if ($result_productos->num_rows > 0) {
            while ($consulta = $result_productos->fetch_assoc()) {
                $id_producto_hijo = intval($consulta['productos_id']);
                if ($id_producto_hijo != 0 && $id_producto_hijo != 'null') {
                    $medidaName = strtolower(mainModel::getMedidaProductoPadre($productos_id)->fetch_assoc());
                    if ($medidaName == 'ton') {
                        $quantity = $cantidad * 2204.623;

                        $consultaSaldoProductoHijo = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega_actual)->fetch_assoc();
                        $saldoProductoHijo = doubleval($consultaSaldoProductoHijo['saldo']);
                        $saldoNuevoProductoHijo = $saldoProductoHijo + doubleval($quantity);

                        $datosHijo = [
                            'productos_id'     => $id_producto_hijo,
                            'cantidad_entrada' => $quantity,
                            'cantidad_salida'  => 0,
                            'saldo'            => $saldoNuevoProductoHijo,
                            'fecha_registro'   => $fecha_registro,
                            'empresa'          => $empresa_id,
                            'comentario'       => $comentario,
                            'clientes_id'      => $clientes_id,
                            'almacen_id'       => $bodega,
                            'lote_id'          => $lote_id
                        ];
                        $queryIngreso = mainModel::agregar_movimiento_productos_modelo($datosHijo);
                        if (!$queryIngreso) $procesosHijosExitosos = false;
                    }
                }
            }
        }
        if (!$procesosHijosExitosos) {
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "Error al procesar productos hijos",
                "type"  => "error"
            ]);
        }

        $consultaSaldoBodegaActual = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega_actual)->fetch_assoc();
        $saldoProductoBodegaActual = doubleval($consultaSaldoBodegaActual['saldo']);

        $consultaSaldoBodegaNueva  = mainModel::getSaldoProductosMovimientosBodega($productos_id, $bodega)->fetch_assoc();
        $saldoProductoBodegaNueva  = doubleval($consultaSaldoBodegaNueva['saldo']);

        $saldoBodegaNueva = $saldoProductoBodegaNueva + doubleval($cantidad);

        $datosIngreso = [
            'productos_id'     => $productos_id,
            'cantidad_entrada' => $cantidad,
            'cantidad_salida'  => 0,
            'saldo'            => $saldoBodegaNueva,
            'fecha_registro'   => $fecha_registro,
            'empresa'          => $empresa_id,
            'comentario'       => $comentario,
            'clientes_id'      => $clientes_id,
            'almacen_id'       => $bodega,
            'lote_id'          => $lote_id
        ];
        $queryIngreso = mainModel::agregar_movimiento_productos_modelo($datosIngreso);

        $saldoBodegaActual = $saldoProductoBodegaActual - doubleval($cantidad);

        $datosEgreso = [
            'productos_id'     => $productos_id,
            'cantidad_entrada' => 0,
            'cantidad_salida'  => $cantidad,
            'saldo'            => $saldoBodegaActual,
            'fecha_registro'   => $fecha_registro,
            'empresa'          => $empresa_id,
            'comentario'       => $comentario,
            'clientes_id'      => $clientes_id,
            'almacen_id'       => $bodega_actual,
            'lote_id'          => $lote_id
        ];
        $queryEgreso = mainModel::agregar_movimiento_productos_modelo($datosEgreso);

        if (!$queryEgreso || !$queryIngreso) {
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se pudo completar la transferencia entre bodegas",
                "type"  => "error"
            ]);
        }

        mainModel::guardarHistorial([
            "modulo"           => 'Productos',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status"           => "Transferencia",
            "observacion"      => "Se transfirió producto ID: {$productos_id} de bodega {$bodega_actual} a {$bodega}",
            "fecha_registro"   => date("Y-m-d H:i:s")
        ]);

        return mainModel::showNotification([
            "type"          => "success",
            "title"         => "Transferencia exitosa",
            "text"          => "El movimiento entre bodegas se realizó correctamente",
            "funcion"       => "inventario_transferencia();setValoresProduco();",
            "closeAllModals"=> true
        ]);
    }

    /* =========================
       ELIMINAR PRODUCTO
       ========================= */
    public function delete_productos_controlador()
    {
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"   => "error",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion']
            ]);
            exit();
        }

        if (!isset($_POST['productos_id']) || !is_numeric($_POST['productos_id'])) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "error",
                "title"   => "Solicitud inválida",
                "message" => "Falta el ID del producto."
            ]);
            exit();
        }

        $productos_id = (int)$_POST['productos_id'];
        $conn = mainModel::connection();

        // Traer datos incluyendo el nombre del archivo desde file_name
        $sql = "SELECT nombre, barCode, file_name AS file FROM productos WHERE productos_id = {$productos_id} LIMIT 1";
        $res = $conn->query($sql);
        if (!$res || $res->num_rows === 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "Producto no encontrado"
            ]);
            exit();
        }
        $producto = $res->fetch_assoc();

        $nombre   = $producto['nombre']  ?? '';
        $barcode  = $producto['barCode'] ?? '';
        $filename = $producto['file']    ?? '';

        // Validar movimientos
        if (productosModelo::valid_productos_movimientos($productos_id)->num_rows > 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "error",
                "title"   => "No se puede eliminar",
                "message" => "El producto {$nombre} tiene movimientos asociados"
            ]);
            exit();
        }

        // Borrar en DB
        if (!productosModelo::delete_productos_modelo($productos_id)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo eliminar el producto {$nombre}"
            ]);
            exit();
        }

        // Borrar imagen física (no borrar placeholder)
        $defaultPlaceholder = 'image_preview.png';
        if (!empty($filename) && $filename !== $defaultPlaceholder) {
            $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . trim(PRODUCT_PATH, '/') . '/' . basename($filename);
            if (file_exists($path)) { @unlink($path); }
        }

        // Historial
        if (method_exists('mainModel', 'guardarHistorial')) {
            mainModel::guardarHistorial([
                "modulo"           => 'Productos',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status"           => "Eliminación",
                "observacion"      => "Se eliminó el producto {$nombre}" . ($barcode ? " (Código: {$barcode})" : ""),
                "fecha_registro"   => date("Y-m-d H:i:s")
            ]);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            "status"  => "success",
            "title"   => "Eliminado",
            "message" => "Producto {$nombre} eliminado correctamente"
        ]);
        exit();
    }
}