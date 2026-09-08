<?php
/* =====================================================================
   API LOCAL DE ADMINISTRACIÓN DE FACTURACIÓN
   Vive en este mismo archivo para mantener el cambio limitado al módulo
   Asignación de Planes. Solo se ejecuta cuando llega ap_api_action.
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ap_api_action'])) {
    $peticionAjax = true;
    header('Content-Type: application/json; charset=utf-8');

    function apApiRespond($success, $message, $data = array(), $status = 200) {
        http_response_code($status);
        echo json_encode(array(
            'success' => (bool)$success,
            'status' => $success ? 'success' : 'error',
            'message' => $message,
            'data' => $data
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    function apApiColumns($conn, $table) {
        $cols = array();
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $res = $conn->query("SHOW COLUMNS FROM `{$safe}`");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cols[$row['Field']] = $row;
            }
        }
        return $cols;
    }

    function apApiTableExists($conn, $table) {
        $safe = $conn->real_escape_string($table);
        $res = $conn->query("SHOW TABLES LIKE '{$safe}'");
        return $res && $res->num_rows > 0;
    }

    function apApiNextId($conn, $table, $idField) {
        $cols = apApiColumns($conn, $table);
        if (isset($cols[$idField]) && stripos((string)$cols[$idField]['Extra'], 'auto_increment') !== false) {
            return null;
        }
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $idField);
        $res = $conn->query("SELECT COALESCE(MAX(`{$safeField}`),0)+1 AS next_id FROM `{$safeTable}`");
        $row = $res ? $res->fetch_assoc() : array('next_id' => 1);
        return (int)$row['next_id'];
    }

    function apApiDateDisplay($value) {
        if (!$value || $value === '0000-00-00') return '';
        $ts = strtotime($value);
        return $ts ? date('d/m/Y', $ts) : $value;
    }

    function apApiNotifyAdmin($dbName, $title, $summary, array $details = array(), array $changes = array(), $type = 'audit', $empresaId = 0) {
        try {
            $svc = new NotificationService();
            $ctx = $svc->clientContextFromDb($dbName);
            return $svc->notifyClientAndMain(
                $dbName,
                $empresaId,
                $ctx['cliente_nombre'] ?? 'Cliente IZZY',
                'IZZY · '.$title,
                $summary,
                $details,
                $changes,
                'IZZY · Auditoría · '.$title,
                $summary,
                $details,
                $changes,
                $type
            );
        } catch (Throwable $e) {
            error_log('Asignación de Planes - notificación '.$title.': '.$e->getMessage());
            return array();
        }
    }

    function apApiNotifyAffected($dbName, $field, $id, $title, $summary, array $changes = array()) {
        try {
            $svc = new NotificationService();
            return $svc->notifyAffectedUsersByField($dbName, 0, $field, $id, 'IZZY · '.$title, $summary, $changes);
        } catch (Throwable $e) {
            error_log('Asignación de Planes - usuarios afectados '.$title.': '.$e->getMessage());
            return array('sent'=>0,'failed'=>0);
        }
    }

    try {
        require_once __DIR__ . '/../../core/configGenerales.php';
        require_once __DIR__ . '/../../core/mainModel.php';
        require_once __DIR__ . '/../../core/correo/NotificationService.php';

        $validacion = mainModel::validarSesion();
        if (!empty($validacion['error'])) {
            apApiRespond(false, isset($validacion['mensaje']) ? $validacion['mensaje'] : 'La sesión no es válida.', array(), 401);
        }

        $serverCustomerId = isset($_POST['server_customers_id']) ? (int)$_POST['server_customers_id'] : 0;
        if ($serverCustomerId <= 0) {
            apApiRespond(false, 'No se recibió el cliente que se desea administrar.', array(), 400);
        }

        $apMainModel = new mainModel();
        $main = $apMainModel->connectionLogin();
        $stmt = $main->prepare('SELECT db FROM server_customers WHERE server_customers_id = ? LIMIT 1');
        if (!$stmt) throw new Exception('No se pudo preparar la consulta del cliente.');
        $stmt->bind_param('i', $serverCustomerId);
        $stmt->execute();
        $rowDb = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $main->close();

        if (!$rowDb || empty($rowDb['db'])) {
            apApiRespond(false, 'El cliente seleccionado no tiene una base de datos configurada.', array(), 404);
        }

        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $rowDb['db']);
        if ($dbName === '') apApiRespond(false, 'El nombre de la base de datos del cliente no es válido.', array(), 400);

        $client = $apMainModel->connectionDBLocal($dbName);
        $action = trim((string)$_POST['ap_api_action']);

        if ($action === 'plan_limits') {
            $planId = isset($_POST['planes_id']) ? (int)$_POST['planes_id'] : 0;
            if ($planId <= 0) {
                $client->close();
                apApiRespond(false, 'No se recibió el plan del cliente.', array(), 400);
            }

            $planRow = null;
            $source = 'cliente';

            if (apApiTableExists($client, 'planes')) {
                $stPlan = $client->prepare('SELECT planes_id, nombre, configuraciones, estado FROM planes WHERE planes_id = ? LIMIT 1');
                if ($stPlan) {
                    $stPlan->bind_param('i', $planId);
                    $stPlan->execute();
                    $planRow = $stPlan->get_result()->fetch_assoc();
                    $stPlan->close();
                }
            }

            /*
             * Respaldo: si la tabla de planes del cliente aún no fue sincronizada,
             * se consulta DB_MAIN. La fuente de verdad sigue siendo planes.configuraciones.
             */
            if (!$planRow) {
                $source = 'principal';
                $mainPlan = $apMainModel->connectionLogin();
                $stPlan = $mainPlan->prepare('SELECT planes_id, nombre, configuraciones, estado FROM planes WHERE planes_id = ? LIMIT 1');
                if ($stPlan) {
                    $stPlan->bind_param('i', $planId);
                    $stPlan->execute();
                    $planRow = $stPlan->get_result()->fetch_assoc();
                    $stPlan->close();
                }
                $mainPlan->close();
            }

            $client->close();

            if (!$planRow) {
                apApiRespond(false, 'No se encontró la configuración del plan asignado.', array('planes_id' => $planId), 404);
            }

            $config = array();
            $rawConfig = isset($planRow['configuraciones']) ? trim((string)$planRow['configuraciones']) : '';
            if ($rawConfig !== '') {
                $decoded = json_decode($rawConfig, true);
                if (is_array($decoded)) $config = $decoded;
            }

            $usuarios = isset($config['usuarios']) && is_numeric($config['usuarios'])
                ? max(0, (int)$config['usuarios'])
                : null;
            $perfiles = isset($config['perfiles']) && is_numeric($config['perfiles'])
                ? max(0, (int)$config['perfiles'])
                : null;

            apApiRespond(true, 'Límites del plan cargados correctamente.', array(
                'source' => $source,
                'plan' => array(
                    'planes_id' => (int)$planRow['planes_id'],
                    'nombre' => (string)$planRow['nombre'],
                    'estado' => (int)$planRow['estado'],
                    'configuraciones' => $config
                ),
                'limites' => array(
                    'usuarios' => $usuarios,
                    /* En IZZY, perfiles representa la cantidad de empresas/puntos de venta del plan. */
                    'empresas' => $perfiles,
                    'perfiles' => $perfiles
                )
            ));
        }

        if ($action === 'billing_catalogs') {
            $empresas = array();
            if (apApiTableExists($client, 'empresa')) {
                $res = $client->query('SELECT * FROM empresa ORDER BY empresa_id ASC');
                if ($res) while ($r = $res->fetch_assoc()) $empresas[] = $r;
            }

            $documentos = array();
            if (apApiTableExists($client, 'documento')) {
                $docCols = apApiColumns($client, 'documento');
                $stateField = isset($docCols['estado']) ? 'estado' : (isset($docCols['activo']) ? 'activo' : null);
                $stateExpr = $stateField ? "d.`{$stateField}`" : '1';
                $hasSeq = apApiTableExists($client, 'secuencia_facturacion');
                if ($hasSeq) {
                    $sql = "SELECT d.documento_id, d.nombre, {$stateExpr} AS estado,
                                   COUNT(sf.secuencia_facturacion_id) AS secuencias_total,
                                   COALESCE(SUM(CASE WHEN sf.activo = 1 THEN 1 ELSE 0 END),0) AS secuencias_activas
                            FROM documento d
                            LEFT JOIN secuencia_facturacion sf ON sf.documento_id = d.documento_id
                            GROUP BY d.documento_id, d.nombre" . ($stateField ? ", d.`{$stateField}`" : '') . "
                            ORDER BY d.nombre ASC";
                } else {
                    $sql = "SELECT d.documento_id, d.nombre, {$stateExpr} AS estado,
                                   0 AS secuencias_total, 0 AS secuencias_activas
                            FROM documento d ORDER BY d.nombre ASC";
                }
                $res = $client->query($sql);
                if (!$res) throw new Exception('No se pudieron consultar los documentos del cliente: ' . $client->error);
                while ($r = $res->fetch_assoc()) $documentos[] = $r;
            }

            $secuencias = array();
            if (apApiTableExists($client, 'secuencia_facturacion')) {
                $sql = "SELECT sf.secuencia_facturacion_id, sf.empresa_id, sf.documento_id,
                               sf.cai, sf.prefijo, sf.relleno, sf.incremento, sf.siguiente,
                               sf.rango_inicial, sf.rango_final, sf.fecha_activacion, sf.fecha_limite,
                               sf.fecha_registro, sf.activo AS estado,
                               COALESCE(e.nombre, CONCAT('Empresa #', sf.empresa_id)) AS empresa,
                               COALESCE(d.nombre, CONCAT('Documento #', sf.documento_id)) AS documento
                        FROM secuencia_facturacion sf
                        LEFT JOIN empresa e ON e.empresa_id = sf.empresa_id
                        LEFT JOIN documento d ON d.documento_id = sf.documento_id
                        ORDER BY sf.activo DESC, sf.fecha_limite ASC, sf.secuencia_facturacion_id DESC";
                $res = $client->query($sql);
                if (!$res) throw new Exception('No se pudieron consultar las secuencias del cliente: ' . $client->error);
                while ($r = $res->fetch_assoc()) {
                    $r['fecha_activacion_raw'] = $r['fecha_activacion'];
                    $r['fecha_limite_raw'] = $r['fecha_limite'];
                    $r['fecha_activacion_display'] = apApiDateDisplay($r['fecha_activacion']);
                    $r['fecha_limite_display'] = apApiDateDisplay($r['fecha_limite']);
                    $secuencias[] = $r;
                }
            }

            $client->close();
            apApiRespond(true, 'Información de facturación cargada correctamente.', array(
                'db' => $dbName,
                'empresas' => $empresas,
                'documentos' => $documentos,
                'secuencias' => $secuencias
            ));
        }

        if ($action === 'save_document') {
            if (!apApiTableExists($client, 'documento')) throw new Exception('La tabla documento no existe en la base del cliente.');
            $id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $documentoAnterior = null;
            if ($id > 0) {
                $stOld = $client->prepare('SELECT * FROM documento WHERE documento_id=? LIMIT 1');
                if ($stOld) { $stOld->bind_param('i',$id); $stOld->execute(); $documentoAnterior=$stOld->get_result()->fetch_assoc(); $stOld->close(); }
            }
            if ($nombre === '') apApiRespond(false, 'Ingrese el nombre del documento.', array(), 400);
            if (mb_strlen($nombre) > 30) apApiRespond(false, 'El nombre del documento no puede superar 30 caracteres.', array(), 400);

            $dup = $client->prepare('SELECT documento_id FROM documento WHERE LOWER(nombre)=LOWER(?) AND documento_id<>? LIMIT 1');
            $dup->bind_param('si', $nombre, $id);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $dup->close();
                apApiRespond(false, 'Ya existe un documento con ese nombre en este cliente.', array(), 409);
            }
            $dup->close();

            $cols = apApiColumns($client, 'documento');
            if ($id > 0) {
                $st = $client->prepare('UPDATE documento SET nombre=? WHERE documento_id=?');
                $st->bind_param('si', $nombre, $id);
                if (!$st->execute()) throw new Exception($st->error);
                $st->close();
            } else {
                $fields = array('nombre'); $values = array('?'); $types = 's'; $params = array($nombre);
                $nextId = apApiNextId($client, 'documento', 'documento_id');
                if ($nextId !== null) { array_unshift($fields, 'documento_id'); array_unshift($values, '?'); $types = 'i'.$types; array_unshift($params, $nextId); }
                if (isset($cols['estado'])) { $fields[]='estado'; $values[]='?'; $types.='i'; $params[]=1; }
                elseif (isset($cols['activo'])) { $fields[]='activo'; $values[]='?'; $types.='i'; $params[]=1; }
                if (isset($cols['fecha_registro'])) { $fields[]='fecha_registro'; $values[]='NOW()'; }
                $sql='INSERT INTO documento (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',$values).')';
                $st=$client->prepare($sql);
                if (!$st) throw new Exception($client->error);
                if ($params) $st->bind_param($types, ...$params);
                if (!$st->execute()) throw new Exception($st->error);
                $id = $nextId !== null ? $nextId : (int)$client->insert_id;
                $st->close();
            }
            $cambiosDocumento = [];
            if ($documentoAnterior && trim((string)($documentoAnterior['nombre'] ?? '')) !== $nombre) {
                $cambiosDocumento['Nombre'] = ['anterior'=>$documentoAnterior['nombre'], 'nuevo'=>$nombre];
            }
            apApiNotifyAdmin($dbName, $documentoAnterior ? 'Documento actualizado' : 'Documento creado', $documentoAnterior ? 'Se actualizó un documento de facturación.' : 'Se creó un nuevo documento de facturación.', ['Documento'=>$nombre, 'ID'=>$id], $cambiosDocumento, $documentoAnterior ? 'info' : 'success');
            $client->close();
            apApiRespond(true, $documentoAnterior ? 'Documento guardado correctamente.' : 'Documento registrado correctamente.', array('documento_id'=>$id));
        }

        if ($action === 'toggle_document') {
            $id=(int)($_POST['documento_id']??0); $estado=(int)($_POST['estado']??0)===1?1:0;
            $docInfo=null; $stDoc=$client->prepare('SELECT * FROM documento WHERE documento_id=? LIMIT 1'); if($stDoc){$stDoc->bind_param('i',$id);$stDoc->execute();$docInfo=$stDoc->get_result()->fetch_assoc();$stDoc->close();}
            $cols=apApiColumns($client,'documento');
            $field=isset($cols['estado'])?'estado':(isset($cols['activo'])?'activo':'');
            if (!$field) throw new Exception('La tabla documento no tiene un campo de estado compatible.');
            $st=$client->prepare("UPDATE documento SET `{$field}`=? WHERE documento_id=?");
            $st->bind_param('ii',$estado,$id); if(!$st->execute()) throw new Exception($st->error); $st->close();
            apApiNotifyAdmin($dbName, 'Estado de documento actualizado', 'Se cambió el estado de un documento de facturación.', ['Documento'=>$docInfo['nombre']??('#'.$id)], ['Estado'=>['anterior'=>!empty($docInfo[$field])?'Activo':'Inactivo','nuevo'=>$estado?'Activo':'Inactivo']], 'info');
            $client->close(); apApiRespond(true,$estado?'Documento activado correctamente.':'Documento desactivado correctamente.');
        }

        if ($action === 'delete_document') {
            $id=(int)($_POST['documento_id']??0);
            $docEliminar=null;
            $stInfo=$client->prepare('SELECT * FROM documento WHERE documento_id=? LIMIT 1');
            if($stInfo){$stInfo->bind_param('i',$id);$stInfo->execute();$docEliminar=$stInfo->get_result()->fetch_assoc();$stInfo->close();}
            if (apApiTableExists($client,'secuencia_facturacion')) {
                $st=$client->prepare('SELECT COUNT(*) AS total FROM secuencia_facturacion WHERE documento_id=?');
                $st->bind_param('i',$id); $st->execute(); $cnt=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
                if($cnt>0) apApiRespond(false,'No se puede eliminar el documento porque tiene secuencias asociadas.',array('secuencias_total'=>$cnt),409);
            }
            $st=$client->prepare('DELETE FROM documento WHERE documento_id=?'); $st->bind_param('i',$id);
            if(!$st->execute()) throw new Exception($st->error); $st->close();
            apApiNotifyAdmin(
                $dbName,
                'Documento eliminado',
                'Se eliminó un documento de facturación.',
                ['Documento'=>$docEliminar['nombre']??('#'.$id),'ID'=>$id],
                [],
                'audit'
            );
            $client->close();
            apApiRespond(true,'Documento eliminado correctamente.');
        }

        if ($action === 'save_sequence') {
            if (!apApiTableExists($client,'secuencia_facturacion')) throw new Exception('La tabla secuencia_facturacion no existe en la base del cliente.');
            $id=(int)($_POST['secuencia_facturacion_id']??0);
            $secuenciaAnterior=null;
            if($id>0){$stOld=$client->prepare('SELECT * FROM secuencia_facturacion WHERE secuencia_facturacion_id=? LIMIT 1');if($stOld){$stOld->bind_param('i',$id);$stOld->execute();$secuenciaAnterior=$stOld->get_result()->fetch_assoc();$stOld->close();}}
            $empresa=(int)($_POST['empresa_secuencia']??0); $documento=(int)($_POST['documento_secuencia']??0);
            $cai=trim((string)($_POST['cai_secuencia']??'')); $prefijo=trim((string)($_POST['prefijo_secuencia']??''));
            $relleno=(int)($_POST['relleno_secuencia']??0); $incremento=(int)($_POST['incremento_secuencia']??0); $siguiente=(int)($_POST['siguiente_secuencia']??0);
            $rin=trim((string)($_POST['rango_inicial_secuencia']??'')); $rfin=trim((string)($_POST['rango_final_secuencia']??''));
            $fini=trim((string)($_POST['fecha_activacion_secuencia']??'')); $flim=trim((string)($_POST['fecha_limite_secuencia']??''));
            $activo=isset($_POST['estado_secuencia']) && ((string)$_POST['estado_secuencia']==='1'||$_POST['estado_secuencia']==='on') ? 1 : 0;
            if($id<=0 && (!$empresa||!$documento||$relleno<=0||$incremento<=0||$rin===''||$rfin===''||$fini===''||$flim==='')) apApiRespond(false,'Complete los campos obligatorios de la secuencia.',array(),400);
            if($id>0){
                $st=$client->prepare('UPDATE secuencia_facturacion SET siguiente=?, activo=? WHERE secuencia_facturacion_id=?');
                $st->bind_param('iii',$siguiente,$activo,$id); if(!$st->execute()) throw new Exception($st->error); $st->close();
            } else {
                $cols=apApiColumns($client,'secuencia_facturacion');
                $fields=array('empresa_id','documento_id','cai','prefijo','relleno','incremento','siguiente','rango_inicial','rango_final','fecha_activacion','fecha_limite','activo');
                $values=array('?','?','?','?','?','?','?','?','?','?','?','?');
                $types='iissiiissssi'; $params=array($empresa,$documento,$cai,$prefijo,$relleno,$incremento,$siguiente,$rin,$rfin,$fini,$flim,$activo);

                /*
                 * La secuencia se registra directamente en la BD del cliente.
                 * En instalaciones IZZY, secuencia_facturacion.colaboradores_id
                 * es obligatorio y representa al colaborador que registra la
                 * configuración. El usuario administrativo de DB_MAIN no tiene
                 * por qué existir con el mismo ID dentro de la BD cliente.
                 *
                 * Para no mezclar IDs entre bases, resolvemos SIEMPRE el
                 * colaborador dentro de la propia BD destino. Se prioriza el
                 * colaborador técnico del sistema (IZZY CLOUD / IZZY /
                 * ES MULTISERVICIOS); si no existe, se usa un colaborador activo
                 * de la empresa seleccionada y, finalmente, cualquier activo.
                 */
                if (isset($cols['colaboradores_id'])) {
                    if (!apApiTableExists($client, 'colaboradores')) {
                        throw new Exception('La secuencia requiere un colaborador, pero la tabla colaboradores no existe en la base del cliente.');
                    }

                    $colaboradorId = 0;
                    $colaboradorNombre = '';
                    $colCols = apApiColumns($client, 'colaboradores');

                    $estadoSql = isset($colCols['estado']) ? ' AND estado = 1' : '';

                    if (isset($colCols['nombre'])) {
                        /* Primero buscamos el colaborador técnico dentro de la
                           misma empresa de la secuencia, cuando la tabla lo permite. */
                        if (isset($colCols['empresa_id']) && $empresa > 0) {
                            $sqlSistemaEmpresa = "SELECT colaboradores_id, nombre
                                                  FROM colaboradores
                                                  WHERE empresa_id = ?
                                                    AND UPPER(TRIM(nombre)) IN ('IZZY CLOUD','IZZY','ES MULTISERVICIOS','ES MULTISERVICIOS S. DE R.L. DE C.V.')" . $estadoSql . "
                                                  ORDER BY CASE UPPER(TRIM(nombre))
                                                      WHEN 'IZZY CLOUD' THEN 1
                                                      WHEN 'IZZY' THEN 2
                                                      WHEN 'ES MULTISERVICIOS' THEN 3
                                                      ELSE 4 END,
                                                      colaboradores_id ASC
                                                  LIMIT 1";
                            $stSistemaEmpresa = $client->prepare($sqlSistemaEmpresa);
                            if (!$stSistemaEmpresa) throw new Exception($client->error);
                            $stSistemaEmpresa->bind_param('i', $empresa);
                            $stSistemaEmpresa->execute();
                            $rsSistemaEmpresa = $stSistemaEmpresa->get_result();
                            if ($rsSistemaEmpresa && ($rowSistemaEmpresa = $rsSistemaEmpresa->fetch_assoc())) {
                                $colaboradorId = (int)$rowSistemaEmpresa['colaboradores_id'];
                                $colaboradorNombre = (string)$rowSistemaEmpresa['nombre'];
                            }
                            $stSistemaEmpresa->close();
                        }

                        /* Si el colaborador técnico existe, pero pertenece a otra
                           empresa, sigue siendo preferible a mezclar un ID de DB_MAIN. */
                        if ($colaboradorId <= 0) {
                            $sqlSistema = "SELECT colaboradores_id, nombre
                                           FROM colaboradores
                                           WHERE UPPER(TRIM(nombre)) IN ('IZZY CLOUD','IZZY','ES MULTISERVICIOS','ES MULTISERVICIOS S. DE R.L. DE C.V.')" . $estadoSql . "
                                           ORDER BY CASE UPPER(TRIM(nombre))
                                               WHEN 'IZZY CLOUD' THEN 1
                                               WHEN 'IZZY' THEN 2
                                               WHEN 'ES MULTISERVICIOS' THEN 3
                                               ELSE 4 END,
                                               colaboradores_id ASC
                                           LIMIT 1";
                            $rsSistema = $client->query($sqlSistema);
                            if ($rsSistema && ($rowSistema = $rsSistema->fetch_assoc())) {
                                $colaboradorId = (int)$rowSistema['colaboradores_id'];
                                $colaboradorNombre = (string)$rowSistema['nombre'];
                            }
                        }
                    }

                    if ($colaboradorId <= 0 && isset($colCols['empresa_id']) && $empresa > 0) {
                        $sqlEmpresa = 'SELECT colaboradores_id' . (isset($colCols['nombre']) ? ', nombre' : '') .
                                      ' FROM colaboradores WHERE empresa_id = ?' . $estadoSql .
                                      ' ORDER BY colaboradores_id ASC LIMIT 1';
                        $stCol = $client->prepare($sqlEmpresa);
                        if (!$stCol) throw new Exception($client->error);
                        $stCol->bind_param('i', $empresa);
                        $stCol->execute();
                        $rsCol = $stCol->get_result();
                        if ($rsCol && ($rowCol = $rsCol->fetch_assoc())) {
                            $colaboradorId = (int)$rowCol['colaboradores_id'];
                            $colaboradorNombre = isset($rowCol['nombre']) ? (string)$rowCol['nombre'] : '';
                        }
                        $stCol->close();
                    }

                    if ($colaboradorId <= 0) {
                        $sqlActivo = 'SELECT colaboradores_id' . (isset($colCols['nombre']) ? ', nombre' : '') .
                                     ' FROM colaboradores WHERE 1=1' . $estadoSql .
                                     ' ORDER BY colaboradores_id ASC LIMIT 1';
                        $rsActivo = $client->query($sqlActivo);
                        if ($rsActivo && ($rowActivo = $rsActivo->fetch_assoc())) {
                            $colaboradorId = (int)$rowActivo['colaboradores_id'];
                            $colaboradorNombre = isset($rowActivo['nombre']) ? (string)$rowActivo['nombre'] : '';
                        }
                    }

                    if ($colaboradorId <= 0) {
                        throw new Exception('La base del cliente no tiene un colaborador activo para registrar la secuencia. Cree primero el colaborador técnico del sistema (por ejemplo, IZZY CLOUD).');
                    }

                    $fields[] = 'colaboradores_id';
                    $values[] = '?';
                    $types .= 'i';
                    $params[] = $colaboradorId;
                }

                $nextId=apApiNextId($client,'secuencia_facturacion','secuencia_facturacion_id');
                if($nextId!==null){array_unshift($fields,'secuencia_facturacion_id');array_unshift($values,'?');$types='i'.$types;array_unshift($params,$nextId);}
                if(isset($cols['fecha_registro'])){$fields[]='fecha_registro';$values[]='NOW()';}
                $sql='INSERT INTO secuencia_facturacion (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',$values).')';
                $st=$client->prepare($sql); if(!$st) throw new Exception($client->error); $st->bind_param($types,...$params); if(!$st->execute()) throw new Exception($st->error); $id=$nextId!==null?$nextId:(int)$client->insert_id; $st->close();
            }
            $seqInfo=null;$stInfo=$client->prepare("SELECT sf.*,COALESCE(e.nombre,CONCAT('Empresa #',sf.empresa_id)) empresa,COALESCE(d.nombre,CONCAT('Documento #',sf.documento_id)) documento FROM secuencia_facturacion sf LEFT JOIN empresa e ON e.empresa_id=sf.empresa_id LEFT JOIN documento d ON d.documento_id=sf.documento_id WHERE sf.secuencia_facturacion_id=? LIMIT 1");if($stInfo){$stInfo->bind_param('i',$id);$stInfo->execute();$seqInfo=$stInfo->get_result()->fetch_assoc();$stInfo->close();}
            $cambiosSeq=[];
            if($secuenciaAnterior){if((int)$secuenciaAnterior['siguiente']!==$siguiente)$cambiosSeq['Siguiente']=['anterior'=>$secuenciaAnterior['siguiente'],'nuevo'=>$siguiente];if((int)$secuenciaAnterior['activo']!==$activo)$cambiosSeq['Estado']=['anterior'=>(int)$secuenciaAnterior['activo']===1?'Activo':'Inactivo','nuevo'=>$activo===1?'Activo':'Inactivo'];}
            apApiNotifyAdmin($dbName,$secuenciaAnterior?'Secuencia actualizada':'Secuencia creada',$secuenciaAnterior?'Se actualizó una secuencia de facturación.':'Se creó una nueva secuencia de facturación.',['Empresa'=>$seqInfo['empresa']??$empresa,'Documento'=>$seqInfo['documento']??$documento,'CAI'=>$seqInfo['cai']??$cai,'Prefijo'=>$seqInfo['prefijo']??$prefijo,'Rango'=>($seqInfo['rango_inicial']??$rin).' - '.($seqInfo['rango_final']??$rfin),'Fecha límite'=>$seqInfo['fecha_limite']??$flim],$cambiosSeq,$secuenciaAnterior?'info':'success',$empresa);
            $client->close(); apApiRespond(true,'Secuencia guardada correctamente.',array('secuencia_facturacion_id'=>$id));
        }


        /* =========================================================
           ACCESOS DEL CLIENTE: PRIVILEGIOS + TIPO DE USUARIO
           Toda la operación se ejecuta dentro de la BD del cliente.
           ========================================================= */
        if ($action === 'access_catalogs') {
            $privilegios = array();
            $tipos = array();
            $permisosPorTipo = array();

            if (apApiTableExists($client, 'privilegio')) {
                $pCols = apApiColumns($client, 'privilegio');
                $fechaExpr = isset($pCols['fecha_registro']) ? 'p.fecha_registro' : 'NULL';
                $estadoExpr = isset($pCols['estado']) ? 'p.estado' : '1';
                $userCount = apApiTableExists($client, 'users')
                    ? "(SELECT COUNT(*) FROM users u WHERE u.privilegio_id = p.privilegio_id)"
                    : "0";
                $menuCount = apApiTableExists($client, 'acceso_menu')
                    ? "(SELECT COUNT(*) FROM acceso_menu am WHERE am.privilegio_id = p.privilegio_id" .
                      (isset(apApiColumns($client, 'acceso_menu')['estado']) ? " AND am.estado = 1" : "") . ")"
                    : "0";
                $subCount = apApiTableExists($client, 'acceso_submenu')
                    ? "(SELECT COUNT(*) FROM acceso_submenu asm WHERE asm.privilegio_id = p.privilegio_id" .
                      (isset(apApiColumns($client, 'acceso_submenu')['estado']) ? " AND asm.estado = 1" : "") . ")"
                    : "0";
                $sub1Count = apApiTableExists($client, 'acceso_submenu1')
                    ? "(SELECT COUNT(*) FROM acceso_submenu1 asm1 WHERE asm1.privilegio_id = p.privilegio_id" .
                      (isset(apApiColumns($client, 'acceso_submenu1')['estado']) ? " AND asm1.estado = 1" : "") . ")"
                    : "0";

                $sql = "SELECT p.privilegio_id, p.nombre, {$estadoExpr} AS estado, {$fechaExpr} AS fecha_registro,
                               {$userCount} AS usuarios_asignados,
                               {$menuCount} AS menus_asignados,
                               {$subCount} AS submenus_asignados,
                               {$sub1Count} AS submenus1_asignados
                        FROM privilegio p
                        ORDER BY p.nombre ASC";
                $res = $client->query($sql);
                if (!$res) throw new Exception('No se pudieron consultar los privilegios: ' . $client->error);
                while ($r = $res->fetch_assoc()) {
                    $r['privilegio_id'] = (int)$r['privilegio_id'];
                    $r['estado'] = ((int)$r['estado'] === 1) ? 1 : 0;
                    $r['usuarios_asignados'] = (int)$r['usuarios_asignados'];
                    $r['menus_asignados'] = (int)$r['menus_asignados'];
                    $r['submenus_asignados'] = (int)$r['submenus_asignados'];
                    $r['submenus1_asignados'] = (int)$r['submenus1_asignados'];
                    $privilegios[] = $r;
                }
            }

            if (apApiTableExists($client, 'tipo_user')) {
                $tCols = apApiColumns($client, 'tipo_user');
                $fechaExpr = isset($tCols['fecha_registro']) ? 't.fecha_registro' : 'NULL';
                $estadoExpr = isset($tCols['estado']) ? 't.estado' : '1';
                $userCount = apApiTableExists($client, 'users')
                    ? "(SELECT COUNT(*) FROM users u WHERE u.tipo_user_id = t.tipo_user_id)"
                    : "0";
                $permCount = apApiTableExists($client, 'permisos')
                    ? "(SELECT COUNT(*) FROM permisos pe WHERE pe.tipo_user_id = t.tipo_user_id AND pe.estado = 1)"
                    : "0";
                $sql = "SELECT t.tipo_user_id, t.nombre, {$estadoExpr} AS estado, {$fechaExpr} AS fecha_registro,
                               {$userCount} AS usuarios_asignados,
                               {$permCount} AS permisos_activos
                        FROM tipo_user t
                        ORDER BY t.nombre ASC";
                $res = $client->query($sql);
                if (!$res) throw new Exception('No se pudieron consultar los tipos de usuario: ' . $client->error);
                while ($r = $res->fetch_assoc()) {
                    $r['tipo_user_id'] = (int)$r['tipo_user_id'];
                    $r['estado'] = ((int)$r['estado'] === 1) ? 1 : 0;
                    $r['usuarios_asignados'] = (int)$r['usuarios_asignados'];
                    $r['permisos_activos'] = (int)$r['permisos_activos'];
                    $tipos[] = $r;
                }
            }

            if (apApiTableExists($client, 'permisos')) {
                $res = $client->query("SELECT tipo_user_id, tipo_permiso, estado FROM permisos ORDER BY tipo_user_id, tipo_permiso");
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $tid = (int)$r['tipo_user_id'];
                        if (!isset($permisosPorTipo[$tid])) $permisosPorTipo[$tid] = array();
                        $permisosPorTipo[$tid][(string)$r['tipo_permiso']] = ((int)$r['estado'] === 1) ? 1 : 0;
                    }
                }
            }

            $client->close();
            apApiRespond(true, 'Privilegios y permisos cargados correctamente.', array(
                'privilegios' => $privilegios,
                'tipos_usuario' => $tipos,
                'permisos' => $permisosPorTipo
            ));
        }

        if ($action === 'save_privilege') {
            if (!apApiTableExists($client, 'privilegio')) {
                throw new Exception('La tabla privilegio no existe en la base del cliente.');
            }
            $id = isset($_POST['privilegio_id']) ? (int)$_POST['privilegio_id'] : 0;
            $privAnterior=null;if($id>0){$stOld=$client->prepare('SELECT * FROM privilegio WHERE privilegio_id=? LIMIT 1');if($stOld){$stOld->bind_param('i',$id);$stOld->execute();$privAnterior=$stOld->get_result()->fetch_assoc();$stOld->close();}}
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $estado = isset($_POST['estado']) && (int)$_POST['estado'] === 1 ? 1 : 0;
            if ($nombre === '') throw new Exception('Ingrese el nombre del privilegio.');
            if (mb_strlen($nombre) > 20) throw new Exception('El nombre del privilegio no puede superar 20 caracteres.');

            $st = $client->prepare('SELECT privilegio_id FROM privilegio WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))' . ($id > 0 ? ' AND privilegio_id <> ?' : '') . ' LIMIT 1');
            if (!$st) throw new Exception($client->error);
            if ($id > 0) $st->bind_param('si', $nombre, $id); else $st->bind_param('s', $nombre);
            $st->execute();
            if ($st->get_result()->num_rows > 0) {
                $st->close();
                $client->close();
                apApiRespond(false, 'Ya existe un privilegio con ese nombre.', array(), 409);
            }
            $st->close();

            $cols = apApiColumns($client, 'privilegio');
            if ($id > 0) {
                $sql = 'UPDATE privilegio SET nombre = ?' . (isset($cols['estado']) ? ', estado = ?' : '') . ' WHERE privilegio_id = ?';
                $st = $client->prepare($sql);
                if (!$st) throw new Exception($client->error);
                if (isset($cols['estado'])) $st->bind_param('sii', $nombre, $estado, $id);
                else $st->bind_param('si', $nombre, $id);
                if (!$st->execute()) throw new Exception($st->error);
                $st->close();
            } else {
                $fields = array('nombre');
                $values = array('?');
                $types = 's';
                $params = array($nombre);
                $nextId = apApiNextId($client, 'privilegio', 'privilegio_id');
                if ($nextId !== null) {
                    array_unshift($fields, 'privilegio_id');
                    array_unshift($values, '?');
                    $types = 'i' . $types;
                    array_unshift($params, $nextId);
                    $id = $nextId;
                }
                if (isset($cols['estado'])) { $fields[] = 'estado'; $values[] = '?'; $types .= 'i'; $params[] = $estado; }
                if (isset($cols['fecha_registro'])) { $fields[] = 'fecha_registro'; $values[] = 'NOW()'; }
                $sql = 'INSERT INTO privilegio (`' . implode('`,`', $fields) . '`) VALUES (' . implode(',', $values) . ')';
                $st = $client->prepare($sql);
                if (!$st) throw new Exception($client->error);
                $st->bind_param($types, ...$params);
                if (!$st->execute()) throw new Exception($st->error);
                if ($id <= 0) $id = (int)$client->insert_id;
                $st->close();
            }
            $cambiosPriv=[];if($privAnterior){if(trim((string)$privAnterior['nombre'])!==$nombre)$cambiosPriv['Privilegio']=['anterior'=>$privAnterior['nombre'],'nuevo'=>$nombre];if(isset($privAnterior['estado'])&&(int)$privAnterior['estado']!==$estado)$cambiosPriv['Estado']=['anterior'=>(int)$privAnterior['estado']===1?'Activo':'Inactivo','nuevo'=>$estado===1?'Activo':'Inactivo'];}
            apApiNotifyAdmin($dbName,$privAnterior?'Privilegio actualizado':'Privilegio creado',$privAnterior?'Se actualizó un privilegio del cliente.':'Se creó un privilegio para el cliente.',['Privilegio'=>$nombre,'ID'=>$id],$cambiosPriv,$privAnterior?'security':'success');
            if($privAnterior) apApiNotifyAffected($dbName,'privilegio_id',$id,'Privilegio actualizado','Se actualizó el privilegio asociado a tu usuario IZZY.',$cambiosPriv);
            $client->close();
            apApiRespond(true, 'Privilegio guardado correctamente.', array('privilegio_id' => $id));
        }

        if ($action === 'delete_privilege') {
            $id = (int)($_POST['privilegio_id'] ?? 0);
            if ($id <= 0) throw new Exception('Privilegio no válido.');
            $privEliminar=null;
            $stInfo=$client->prepare('SELECT * FROM privilegio WHERE privilegio_id=? LIMIT 1');
            if($stInfo){$stInfo->bind_param('i',$id);$stInfo->execute();$privEliminar=$stInfo->get_result()->fetch_assoc();$stInfo->close();}
            if (in_array($id, array(1,2), true)) {
                $client->close();
                apApiRespond(false, 'Este privilegio es base del sistema y no puede eliminarse.', array(), 409);
            }
            if (apApiTableExists($client, 'users')) {
                $st = $client->prepare('SELECT COUNT(*) AS total FROM users WHERE privilegio_id = ?');
                $st->bind_param('i', $id); $st->execute();
                $total = (int)$st->get_result()->fetch_assoc()['total']; $st->close();
                if ($total > 0) {
                    $client->close();
                    apApiRespond(false, 'No se puede eliminar el privilegio porque tiene usuarios asociados.', array('usuarios' => $total), 409);
                }
            }
            foreach (array('acceso_submenu1','acceso_submenu','acceso_menu') as $tbl) {
                if (apApiTableExists($client, $tbl)) {
                    $st = $client->prepare("DELETE FROM `{$tbl}` WHERE privilegio_id = ?");
                    $st->bind_param('i', $id); $st->execute(); $st->close();
                }
            }
            $st = $client->prepare('DELETE FROM privilegio WHERE privilegio_id = ?');
            $st->bind_param('i', $id);
            if (!$st->execute()) throw new Exception($st->error);
            $st->close();
            apApiNotifyAdmin(
                $dbName,
                'Privilegio eliminado',
                'Se eliminó un privilegio del cliente.',
                ['Privilegio'=>$privEliminar['nombre']??('#'.$id),'ID'=>$id],
                [],
                'audit'
            );
            $client->close();
            apApiRespond(true, 'Privilegio eliminado correctamente.');
        }

        if ($action === 'save_type_user') {
            if (!apApiTableExists($client, 'tipo_user')) {
                throw new Exception('La tabla tipo_user no existe en la base del cliente.');
            }
            $id = isset($_POST['tipo_user_id']) ? (int)$_POST['tipo_user_id'] : 0;
            $tipoAnterior=null;if($id>0){$stOld=$client->prepare('SELECT * FROM tipo_user WHERE tipo_user_id=? LIMIT 1');if($stOld){$stOld->bind_param('i',$id);$stOld->execute();$tipoAnterior=$stOld->get_result()->fetch_assoc();$stOld->close();}}
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $estado = isset($_POST['estado']) && (int)$_POST['estado'] === 1 ? 1 : 2;
            if ($nombre === '') throw new Exception('Ingrese el nombre del tipo de usuario.');
            if (mb_strlen($nombre) > 20) throw new Exception('El nombre del tipo de usuario no puede superar 20 caracteres.');

            $st = $client->prepare('SELECT tipo_user_id FROM tipo_user WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))' . ($id > 0 ? ' AND tipo_user_id <> ?' : '') . ' LIMIT 1');
            if (!$st) throw new Exception($client->error);
            if ($id > 0) $st->bind_param('si', $nombre, $id); else $st->bind_param('s', $nombre);
            $st->execute();
            if ($st->get_result()->num_rows > 0) {
                $st->close(); $client->close();
                apApiRespond(false, 'Ya existe un tipo de usuario con ese nombre.', array(), 409);
            }
            $st->close();

            $cols = apApiColumns($client, 'tipo_user');
            if ($id > 0) {
                $sql = 'UPDATE tipo_user SET nombre = ?' . (isset($cols['estado']) ? ', estado = ?' : '') . ' WHERE tipo_user_id = ?';
                $st = $client->prepare($sql);
                if (!$st) throw new Exception($client->error);
                if (isset($cols['estado'])) $st->bind_param('sii', $nombre, $estado, $id);
                else $st->bind_param('si', $nombre, $id);
                if (!$st->execute()) throw new Exception($st->error);
                $st->close();
            } else {
                $fields = array('nombre'); $values = array('?'); $types = 's'; $params = array($nombre);
                $nextId = apApiNextId($client, 'tipo_user', 'tipo_user_id');
                if ($nextId !== null) {
                    array_unshift($fields, 'tipo_user_id'); array_unshift($values, '?');
                    $types = 'i'.$types; array_unshift($params, $nextId); $id = $nextId;
                }
                if (isset($cols['estado'])) { $fields[]='estado'; $values[]='?'; $types.='i'; $params[]=$estado; }
                if (isset($cols['fecha_registro'])) { $fields[]='fecha_registro'; $values[]='NOW()'; }
                $sql='INSERT INTO tipo_user (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',$values).')';
                $st=$client->prepare($sql); if(!$st) throw new Exception($client->error);
                $st->bind_param($types,...$params); if(!$st->execute()) throw new Exception($st->error);
                if($id<=0)$id=(int)$client->insert_id; $st->close();
            }
            $cambiosTipo=[];if($tipoAnterior){if(trim((string)$tipoAnterior['nombre'])!==$nombre)$cambiosTipo['Tipo / permisos']=['anterior'=>$tipoAnterior['nombre'],'nuevo'=>$nombre];if(isset($tipoAnterior['estado'])&&(int)$tipoAnterior['estado']!==$estado)$cambiosTipo['Estado']=['anterior'=>(int)$tipoAnterior['estado']===1?'Activo':'Inactivo','nuevo'=>$estado===1?'Activo':'Inactivo'];}
            apApiNotifyAdmin($dbName,$tipoAnterior?'Tipo de usuario actualizado':'Tipo de usuario creado',$tipoAnterior?'Se actualizó un tipo de usuario/permisos.':'Se creó un tipo de usuario/permisos.',['Tipo'=>$nombre,'ID'=>$id],$cambiosTipo,$tipoAnterior?'security':'success');
            if($tipoAnterior) apApiNotifyAffected($dbName,'tipo_user_id',$id,'Permisos de usuario actualizados','Se actualizó el tipo de permisos asociado a tu usuario IZZY.',$cambiosTipo);
            $client->close();
            apApiRespond(true, 'Tipo de usuario guardado correctamente.', array('tipo_user_id'=>$id));
        }

        if ($action === 'delete_type_user') {
            $id=(int)($_POST['tipo_user_id']??0);
            if($id<=0) throw new Exception('Tipo de usuario no válido.');
            $tipoEliminar=null;
            $stInfo=$client->prepare('SELECT * FROM tipo_user WHERE tipo_user_id=? LIMIT 1');
            if($stInfo){$stInfo->bind_param('i',$id);$stInfo->execute();$tipoEliminar=$stInfo->get_result()->fetch_assoc();$stInfo->close();}
            if(in_array($id,array(1,2),true)){
                $client->close();
                apApiRespond(false,'Este tipo de usuario es base del sistema y no puede eliminarse.',array(),409);
            }
            if(apApiTableExists($client,'users')){
                $st=$client->prepare('SELECT COUNT(*) AS total FROM users WHERE tipo_user_id=?');
                $st->bind_param('i',$id);$st->execute();$total=(int)$st->get_result()->fetch_assoc()['total'];$st->close();
                if($total>0){$client->close();apApiRespond(false,'No se puede eliminar el tipo de usuario porque tiene usuarios asociados.',array('usuarios'=>$total),409);}
            }
            if(apApiTableExists($client,'permisos')){
                $st=$client->prepare('DELETE FROM permisos WHERE tipo_user_id=?');$st->bind_param('i',$id);$st->execute();$st->close();
            }
            $st=$client->prepare('DELETE FROM tipo_user WHERE tipo_user_id=?');$st->bind_param('i',$id);
            if(!$st->execute())throw new Exception($st->error);$st->close();
            apApiNotifyAdmin(
                $dbName,
                'Tipo de usuario eliminado',
                'Se eliminó un tipo de usuario/permisos.',
                ['Tipo'=>$tipoEliminar['nombre']??('#'.$id),'ID'=>$id],
                [],
                'audit'
            );
            $client->close();
            apApiRespond(true,'Tipo de usuario eliminado correctamente.');
        }

        if ($action === 'save_permissions') {
            $tipoId=(int)($_POST['tipo_user_id']??0);
            if($tipoId<=0) throw new Exception('Tipo de usuario no válido.');
            if(!apApiTableExists($client,'permisos')) throw new Exception('La tabla permisos no existe en la base del cliente.');
            $raw = isset($_POST['permissions']) ? $_POST['permissions'] : '{}';
            if (is_string($raw)) $raw = json_decode($raw, true);
            if (!is_array($raw)) $raw = array();
            $permitidos=array('guardar','editar','eliminar','consultar','imprimir','crear','reportes','actualizar','view','pay','cambiar','cancelar','sistema','generar');
            $permisosAntes=[];
            $stAntes=$client->prepare('SELECT tipo_permiso,estado FROM permisos WHERE tipo_user_id=?');
            if($stAntes){
                $stAntes->bind_param('i',$tipoId);$stAntes->execute();$rsAntes=$stAntes->get_result();
                while($rAntes=$rsAntes->fetch_assoc()){if((int)$rAntes['estado']===1)$permisosAntes[]=(string)$rAntes['tipo_permiso'];}
                $stAntes->close();
            }
            $cols=apApiColumns($client,'permisos');
            $pk=null;
            foreach($cols as $field=>$meta){
                if($field==='tipo_user_id'||$field==='tipo_permiso'||$field==='estado'||$field==='fecha_registro') continue;
                if(stripos($field,'_id')!==false){$pk=$field;break;}
            }
            foreach($permitidos as $perm){
                $estado=!empty($raw[$perm])?1:0;
                $st=$client->prepare('SELECT * FROM permisos WHERE tipo_user_id=? AND tipo_permiso=? LIMIT 1');
                if(!$st)throw new Exception($client->error);
                $st->bind_param('is',$tipoId,$perm);$st->execute();$exists=$st->get_result()->num_rows>0;$st->close();
                if($exists){
                    $st=$client->prepare('UPDATE permisos SET estado=? WHERE tipo_user_id=? AND tipo_permiso=?');
                    $st->bind_param('iis',$estado,$tipoId,$perm);
                    if(!$st->execute())throw new Exception($st->error);$st->close();
                }else{
                    $fields=array('tipo_user_id','tipo_permiso','estado');$values=array('?','?','?');$types='isi';$params=array($tipoId,$perm,$estado);
                    if($pk){
                        $next=apApiNextId($client,'permisos',$pk);
                        if($next!==null){array_unshift($fields,$pk);array_unshift($values,'?');$types='i'.$types;array_unshift($params,$next);}
                    }
                    if(isset($cols['fecha_registro'])){$fields[]='fecha_registro';$values[]='NOW()';}
                    $sql='INSERT INTO permisos (`'.implode('`,`',$fields).'`) VALUES ('.implode(',',$values).')';
                    $st=$client->prepare($sql);if(!$st)throw new Exception($client->error);$st->bind_param($types,...$params);
                    if(!$st->execute())throw new Exception($st->error);$st->close();
                }
            }
            $tipoNombre='Tipo #'.$tipoId;$stTipo=$client->prepare('SELECT nombre FROM tipo_user WHERE tipo_user_id=? LIMIT 1');if($stTipo){$stTipo->bind_param('i',$tipoId);$stTipo->execute();$rTipo=$stTipo->get_result()->fetch_assoc();if($rTipo)$tipoNombre=$rTipo['nombre'];$stTipo->close();}
            $permisosActivos=[];foreach($permitidos as $perm){if(!empty($raw[$perm]))$permisosActivos[]=$perm;}
            sort($permisosAntes); sort($permisosActivos);
            $cambiosPerm=['Permisos activos'=>[
                'anterior'=>implode(', ',$permisosAntes)?:'Ninguno',
                'nuevo'=>implode(', ',$permisosActivos)?:'Ninguno'
            ]];
            apApiNotifyAdmin($dbName,'Permisos actualizados','Se actualizó la configuración de permisos de un tipo de usuario.',['Tipo'=>$tipoNombre],$cambiosPerm,'security');
            apApiNotifyAffected($dbName,'tipo_user_id',$tipoId,'Permisos actualizados','Se actualizaron los permisos asociados a tu usuario IZZY.',$cambiosPerm);
            $client->close();
            apApiRespond(true,'Permisos guardados correctamente.');
        }

        if ($action === 'privilege_matrix') {
            $privId=(int)($_POST['privilegio_id']??0);
            $planId=(int)($_POST['planes_id']??0);
            if($privId<=0) throw new Exception('Privilegio no válido.');

            $matrix=array('menus'=>array(),'submenus'=>array(),'submenu1'=>array());

            $catalogName = function($cols) {
                if(isset($cols['name'])) return 'name';
                if(isset($cols['descripcion'])) return 'descripcion';
                if(isset($cols['nombre'])) return 'nombre';
                return null;
            };

            if(apApiTableExists($client,'menu')){
                $mCols=apApiColumns($client,'menu');$nameField=$catalogName($mCols);
                if($nameField){
                    $where=array();$join='';
                    if($planId>0 && apApiTableExists($client,'menu_plan')){
                        $mpCols=apApiColumns($client,'menu_plan');
                        if(isset($mpCols['planes_id'])&&isset($mpCols['menu_id'])){
                            $join=' INNER JOIN menu_plan mp ON mp.menu_id=m.menu_id ';
                            $where[]='mp.planes_id='.(int)$planId;
                            if(isset($mpCols['estado']))$where[]='mp.estado=1';
                        }
                    }
                    if(isset($mCols['estado']))$where[]='m.estado=1';
                    $assignedExpr=apApiTableExists($client,'acceso_menu')
                        ? "EXISTS(SELECT 1 FROM acceso_menu a WHERE a.privilegio_id={$privId} AND a.menu_id=m.menu_id" .
                          (isset(apApiColumns($client,'acceso_menu')['estado']) ? " AND a.estado=1" : "") . ")"
                        : "0";
                    $sql="SELECT DISTINCT m.menu_id AS id,m.`{$nameField}` AS nombre,{$assignedExpr} AS asignado FROM menu m {$join}".
                         (count($where)?' WHERE '.implode(' AND ',$where):'').' ORDER BY m.`'.$nameField.'`';
                    $res=$client->query($sql);if(!$res)throw new Exception($client->error);
                    while($r=$res->fetch_assoc()){$r['id']=(int)$r['id'];$r['asignado']=(int)$r['asignado'];$matrix['menus'][]=$r;}
                }
            }

            if(apApiTableExists($client,'submenu')){
                $sCols=apApiColumns($client,'submenu');$nameField=$catalogName($sCols);
                $mCols=apApiTableExists($client,'menu')?apApiColumns($client,'menu'):array();$mName=$catalogName($mCols);
                if($nameField){
                    $where=array();$join='';
                    if($planId>0 && apApiTableExists($client,'submenu_plan')){
                        $spCols=apApiColumns($client,'submenu_plan');
                        if(isset($spCols['planes_id'])&&isset($spCols['submenu_id'])){
                            $join.=' INNER JOIN submenu_plan sp ON sp.submenu_id=s.submenu_id ';
                            $where[]='sp.planes_id='.(int)$planId;if(isset($spCols['estado']))$where[]='sp.estado=1';
                        }
                    }
                    if($mName&&isset($sCols['menu_id']))$join.=' LEFT JOIN menu m ON m.menu_id=s.menu_id ';
                    if(isset($sCols['estado']))$where[]='s.estado=1';
                    $assignedExpr=apApiTableExists($client,'acceso_submenu')
                        ? "EXISTS(SELECT 1 FROM acceso_submenu a WHERE a.privilegio_id={$privId} AND a.submenu_id=s.submenu_id" .
                          (isset(apApiColumns($client,'acceso_submenu')['estado']) ? " AND a.estado=1" : "") . ")"
                        : "0";
                    $parent=$mName?"COALESCE(m.`{$mName}`,'')":"''";
                    $sql="SELECT DISTINCT s.submenu_id AS id,s.`{$nameField}` AS nombre,{$parent} AS padre,{$assignedExpr} AS asignado FROM submenu s {$join}".
                         (count($where)?' WHERE '.implode(' AND ',$where):'').' ORDER BY padre,s.`'.$nameField.'`';
                    $res=$client->query($sql);if(!$res)throw new Exception($client->error);
                    while($r=$res->fetch_assoc()){$r['id']=(int)$r['id'];$r['asignado']=(int)$r['asignado'];$matrix['submenus'][]=$r;}
                }
            }

            if(apApiTableExists($client,'submenu1')){
                $s1Cols=apApiColumns($client,'submenu1');$nameField=$catalogName($s1Cols);
                $sCols=apApiTableExists($client,'submenu')?apApiColumns($client,'submenu'):array();$sName=$catalogName($sCols);
                if($nameField){
                    $where=array();$join='';
                    if($planId>0 && apApiTableExists($client,'submenu1_plan')){
                        $spCols=apApiColumns($client,'submenu1_plan');
                        if(isset($spCols['planes_id'])&&isset($spCols['submenu1_id'])){
                            $join.=' INNER JOIN submenu1_plan sp1 ON sp1.submenu1_id=s1.submenu1_id ';
                            $where[]='sp1.planes_id='.(int)$planId;if(isset($spCols['estado']))$where[]='sp1.estado=1';
                        }
                    }
                    if($sName&&isset($s1Cols['submenu_id']))$join.=' LEFT JOIN submenu s ON s.submenu_id=s1.submenu_id ';
                    if(isset($s1Cols['estado']))$where[]='s1.estado=1';
                    $assignedExpr=apApiTableExists($client,'acceso_submenu1')
                        ? "EXISTS(SELECT 1 FROM acceso_submenu1 a WHERE a.privilegio_id={$privId} AND a.submenu1_id=s1.submenu1_id" .
                          (isset(apApiColumns($client,'acceso_submenu1')['estado']) ? " AND a.estado=1" : "") . ")"
                        : "0";
                    $parent=$sName?"COALESCE(s.`{$sName}`,'')":"''";
                    $sql="SELECT DISTINCT s1.submenu1_id AS id,s1.`{$nameField}` AS nombre,{$parent} AS padre,{$assignedExpr} AS asignado FROM submenu1 s1 {$join}".
                         (count($where)?' WHERE '.implode(' AND ',$where):'').' ORDER BY padre,s1.`'.$nameField.'`';
                    $res=$client->query($sql);if(!$res)throw new Exception($client->error);
                    while($r=$res->fetch_assoc()){$r['id']=(int)$r['id'];$r['asignado']=(int)$r['asignado'];$matrix['submenu1'][]=$r;}
                }
            }
            $client->close();
            apApiRespond(true,'Accesos del privilegio cargados correctamente.',$matrix);
        }

        if ($action === 'toggle_privilege_access') {
            $privId=(int)($_POST['privilegio_id']??0);
            $itemId=(int)($_POST['item_id']??0);
            $level=(string)($_POST['level']??'');
            $assigned=isset($_POST['assigned'])&&(int)$_POST['assigned']===1?1:0;
            $map=array(
                'menu'=>array('table'=>'acceso_menu','item'=>'menu_id','pk'=>'acceso_menu_id'),
                'submenu'=>array('table'=>'acceso_submenu','item'=>'submenu_id','pk'=>'acceso_submenu_id'),
                'submenu1'=>array('table'=>'acceso_submenu1','item'=>'submenu1_id','pk'=>'acceso_submenu1_id')
            );
            if($privId<=0||$itemId<=0||!isset($map[$level])) throw new Exception('Acceso no válido.');
            $cfg=$map[$level];$table=$cfg['table'];
            if(!apApiTableExists($client,$table)) throw new Exception('La tabla '.$table.' no existe en la base del cliente.');

            /* Mantener jerarquía coherente: al asignar un hijo se habilitan sus
               padres; al quitar un padre se retiran también sus hijos. */
            $ensureAccess = function($tbl,$itemField,$pkField,$itemValue) use ($client,$privId) {
                if(!apApiTableExists($client,$tbl)) return;
                $colsLocal=apApiColumns($client,$tbl);
                $stLocal=$client->prepare("SELECT 1 FROM `{$tbl}` WHERE privilegio_id=? AND `{$itemField}`=? LIMIT 1");
                if(!$stLocal) throw new Exception($client->error);
                $stLocal->bind_param('ii',$privId,$itemValue);$stLocal->execute();$existsLocal=$stLocal->get_result()->num_rows>0;$stLocal->close();
                if($existsLocal){
                    if(isset($colsLocal['estado'])){
                        $stLocal=$client->prepare("UPDATE `{$tbl}` SET estado=1 WHERE privilegio_id=? AND `{$itemField}`=?");
                        $stLocal->bind_param('ii',$privId,$itemValue);if(!$stLocal->execute())throw new Exception($stLocal->error);$stLocal->close();
                    }
                    return;
                }
                $fieldsLocal=array('privilegio_id',$itemField);$valuesLocal=array('?','?');$typesLocal='ii';$paramsLocal=array($privId,$itemValue);
                if(isset($colsLocal['estado'])){$fieldsLocal[]='estado';$valuesLocal[]='?';$typesLocal.='i';$paramsLocal[]=1;}
                if(isset($colsLocal['fecha_registro'])){$fieldsLocal[]='fecha_registro';$valuesLocal[]='NOW()';}
                if(isset($colsLocal[$pkField])){
                    $nextLocal=apApiNextId($client,$tbl,$pkField);
                    if($nextLocal!==null){array_unshift($fieldsLocal,$pkField);array_unshift($valuesLocal,'?');$typesLocal='i'.$typesLocal;array_unshift($paramsLocal,$nextLocal);}
                }
                $sqlLocal="INSERT INTO `{$tbl}` (`".implode('`,`',$fieldsLocal)."`) VALUES (".implode(',',$valuesLocal).")";
                $stLocal=$client->prepare($sqlLocal);if(!$stLocal)throw new Exception($client->error);$stLocal->bind_param($typesLocal,...$paramsLocal);
                if(!$stLocal->execute())throw new Exception($stLocal->error);$stLocal->close();
            };

            if($assigned && $level==='submenu' && apApiTableExists($client,'submenu')){
                $stParent=$client->prepare('SELECT menu_id FROM submenu WHERE submenu_id=? LIMIT 1');
                $stParent->bind_param('i',$itemId);$stParent->execute();$parentRow=$stParent->get_result()->fetch_assoc();$stParent->close();
                if($parentRow && (int)$parentRow['menu_id']>0) $ensureAccess('acceso_menu','menu_id','acceso_menu_id',(int)$parentRow['menu_id']);
            }
            if($assigned && $level==='submenu1' && apApiTableExists($client,'submenu1')){
                $stParent=$client->prepare('SELECT submenu_id FROM submenu1 WHERE submenu1_id=? LIMIT 1');
                $stParent->bind_param('i',$itemId);$stParent->execute();$parentRow=$stParent->get_result()->fetch_assoc();$stParent->close();
                if($parentRow && (int)$parentRow['submenu_id']>0){
                    $subId=(int)$parentRow['submenu_id'];
                    $ensureAccess('acceso_submenu','submenu_id','acceso_submenu_id',$subId);
                    if(apApiTableExists($client,'submenu')){
                        $stMenu=$client->prepare('SELECT menu_id FROM submenu WHERE submenu_id=? LIMIT 1');$stMenu->bind_param('i',$subId);$stMenu->execute();$menuRow=$stMenu->get_result()->fetch_assoc();$stMenu->close();
                        if($menuRow && (int)$menuRow['menu_id']>0)$ensureAccess('acceso_menu','menu_id','acceso_menu_id',(int)$menuRow['menu_id']);
                    }
                }
            }
            if(!$assigned && $level==='menu'){
                if(apApiTableExists($client,'acceso_submenu') && apApiTableExists($client,'submenu')){
                    if(apApiTableExists($client,'acceso_submenu1') && apApiTableExists($client,'submenu1')){
                        $stCascade=$client->prepare('DELETE a1 FROM acceso_submenu1 a1 INNER JOIN submenu1 s1 ON s1.submenu1_id=a1.submenu1_id INNER JOIN submenu s ON s.submenu_id=s1.submenu_id WHERE a1.privilegio_id=? AND s.menu_id=?');
                        if($stCascade){$stCascade->bind_param('ii',$privId,$itemId);$stCascade->execute();$stCascade->close();}
                    }
                    $stCascade=$client->prepare('DELETE a FROM acceso_submenu a INNER JOIN submenu s ON s.submenu_id=a.submenu_id WHERE a.privilegio_id=? AND s.menu_id=?');
                    if($stCascade){$stCascade->bind_param('ii',$privId,$itemId);$stCascade->execute();$stCascade->close();}
                }
            }
            if(!$assigned && $level==='submenu' && apApiTableExists($client,'acceso_submenu1') && apApiTableExists($client,'submenu1')){
                $stCascade=$client->prepare('DELETE a1 FROM acceso_submenu1 a1 INNER JOIN submenu1 s1 ON s1.submenu1_id=a1.submenu1_id WHERE a1.privilegio_id=? AND s1.submenu_id=?');
                if($stCascade){$stCascade->bind_param('ii',$privId,$itemId);$stCascade->execute();$stCascade->close();}
            }

            $cols=apApiColumns($client,$table);
            $st=$client->prepare("SELECT * FROM `{$table}` WHERE privilegio_id=? AND `{$cfg['item']}`=? LIMIT 1");
            if(!$st)throw new Exception($client->error);$st->bind_param('ii',$privId,$itemId);$st->execute();$exists=$st->get_result()->num_rows>0;$st->close();
            if($assigned){
                if($exists){
                    if(isset($cols['estado'])){
                        $st=$client->prepare("UPDATE `{$table}` SET estado=1 WHERE privilegio_id=? AND `{$cfg['item']}`=?");
                        $st->bind_param('ii',$privId,$itemId);if(!$st->execute())throw new Exception($st->error);$st->close();
                    }
                }else{
                    $fields=array('privilegio_id',$cfg['item']);$values=array('?','?');$types='ii';$params=array($privId,$itemId);
                    if(isset($cols['estado'])){$fields[]='estado';$values[]='?';$types.='i';$params[]=1;}
                    if(isset($cols['fecha_registro'])){$fields[]='fecha_registro';$values[]='NOW()';}
                    if(isset($cols[$cfg['pk']])){
                        $next=apApiNextId($client,$table,$cfg['pk']);
                        if($next!==null){array_unshift($fields,$cfg['pk']);array_unshift($values,'?');$types='i'.$types;array_unshift($params,$next);}
                    }
                    $sql="INSERT INTO `{$table}` (`".implode('`,`',$fields)."`) VALUES (".implode(',',$values).")";
                    $st=$client->prepare($sql);if(!$st)throw new Exception($client->error);$st->bind_param($types,...$params);
                    if(!$st->execute())throw new Exception($st->error);$st->close();
                }
            }else{
                $st=$client->prepare("DELETE FROM `{$table}` WHERE privilegio_id=? AND `{$cfg['item']}`=?");
                $st->bind_param('ii',$privId,$itemId);if(!$st->execute())throw new Exception($st->error);$st->close();
            }
            $privNombre='Privilegio #'.$privId;$stPN=$client->prepare('SELECT nombre FROM privilegio WHERE privilegio_id=? LIMIT 1');if($stPN){$stPN->bind_param('i',$privId);$stPN->execute();$rPN=$stPN->get_result()->fetch_assoc();if($rPN)$privNombre=$rPN['nombre'];$stPN->close();}
            $cambiosAcceso=['Acceso '.$level.' #'.$itemId=>['anterior'=>$assigned?'No asignado':'Asignado','nuevo'=>$assigned?'Asignado':'Retirado']];
            apApiNotifyAdmin($dbName,'Acceso de privilegio actualizado','Se modificó la matriz de accesos de un privilegio.',['Privilegio'=>$privNombre],$cambiosAcceso,'security');
            apApiNotifyAffected($dbName,'privilegio_id',$privId,'Accesos actualizados','Se actualizaron los accesos asociados a tu privilegio IZZY.',$cambiosAcceso);
            $client->close();
            apApiRespond(true,$assigned?'Acceso asignado correctamente.':'Acceso retirado correctamente.');
        }

        if ($action === 'delete_sequence') {
            $id=(int)($_POST['secuencia_facturacion_id']??0);
            $seqEliminar=null;
            $sqlInfo='SELECT s.*, COALESCE(e.nombre, CONCAT("Empresa #",s.empresa_id)) AS empresa_nombre, COALESCE(d.nombre, CONCAT("Documento #",s.documento_id)) AS documento_nombre FROM secuencia_facturacion s LEFT JOIN empresa e ON e.empresa_id=s.empresa_id LEFT JOIN documento d ON d.documento_id=s.documento_id WHERE s.secuencia_facturacion_id=? LIMIT 1';
            $stInfo=$client->prepare($sqlInfo);
            if($stInfo){$stInfo->bind_param('i',$id);$stInfo->execute();$seqEliminar=$stInfo->get_result()->fetch_assoc();$stInfo->close();}
            if(apApiTableExists($client,'facturas')){
                $cols=apApiColumns($client,'facturas');
                if(isset($cols['secuencia_facturacion_id'])){
                    $st=$client->prepare('SELECT COUNT(*) AS total FROM facturas WHERE secuencia_facturacion_id=?');$st->bind_param('i',$id);$st->execute();$cnt=(int)$st->get_result()->fetch_assoc()['total'];$st->close();
                    if($cnt>0) apApiRespond(false,'No se puede eliminar esta secuencia porque ya tiene facturas relacionadas.',array('facturas_total'=>$cnt),409);
                }
            }
            $st=$client->prepare('DELETE FROM secuencia_facturacion WHERE secuencia_facturacion_id=?');$st->bind_param('i',$id);if(!$st->execute()) throw new Exception($st->error);$st->close();
            apApiNotifyAdmin(
                $dbName,
                'Secuencia eliminada',
                'Se eliminó una secuencia de facturación.',
                [
                    'Empresa'=>$seqEliminar['empresa_nombre']??'',
                    'Documento'=>$seqEliminar['documento_nombre']??'',
                    'CAI'=>$seqEliminar['cai']??'',
                    'Prefijo'=>$seqEliminar['prefijo']??'',
                    'Rango'=>($seqEliminar['rango_inicial']??'').' - '.($seqEliminar['rango_final']??'')
                ],
                [],
                'audit'
            );
            $client->close();
            apApiRespond(true,'Secuencia eliminada correctamente.');
        }

        $client->close();
        apApiRespond(false, 'Acción no reconocida.', array(), 400);
    } catch (Throwable $e) {
        apApiRespond(false, 'No se pudo completar la operación: ' . $e->getMessage(), array(), 500);
    }
}
?>
<script>
// asignacionPlanes.php
$(window).on("load", function() {
    "use strict";

    /* =========================================================
       RUTAS - ASIGNACIÓN DE PLANES
       ========================================================= */
    const ASIGNAR_PLANES_URLS = {
        obtenerAsignaciones: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerAsignacionesRecientes.php",
        obtenerClientes: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerClientesParaAsignacion.php",
        obtenerPlanes: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerPlanesActivos.php",
        obtenerSistemas: "<?php echo SERVERURL; ?>core/AsignarPlanes/obtenerSistemas.php",
        verificarPlanCliente: "<?php echo SERVERURL; ?>core/AsignarPlanes/verificarPlanCliente.php",
        actualizarPlanCliente: "<?php echo SERVERURL; ?>core/AsignarPlanes/actualizarPlanCliente.php",
        administrarCliente: "<?php echo SERVERURL; ?>core/AsignarPlanes/administrarCliente.php"
    };

    const ASIGNAR_PLANES_STORAGE = {
        filtros: "izzy_asignacion_planes_filtros_visible",
        kpis: "izzy_asignacion_planes_kpis_visible",
        vista: "izzy_asignacion_planes_tipo_vista"
    };

    const asignacionState = {
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        pageSizeDetalle: 10,
        pageSizeMiniatura: 6,
        view: "detalle",
        loading: false
    };

    let asignacionDebounceTimer = null;

    /* =========================================================
       UTILIDADES
       ========================================================= */
    function limpiarHtml(texto) {
        if (texto === null || typeof texto === "undefined") {
            return "";
        }

        return String(texto)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatFechaHora(fecha) {
        if (!fecha) {
            return "No registrada";
        }

        const date = new Date(fecha);

        if (isNaN(date.getTime())) {
            return limpiarHtml(fecha);
        }

        return date.toLocaleDateString("es-HN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }) + " " + date.toLocaleTimeString("es-HN", {
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function normalizarTexto(valor) {
        return String(valor || "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function obtenerNombrePlan(row) {
        return row && row.plan && row.plan.nombre ? String(row.plan.nombre) : "Sin plan";
    }

    function obtenerNombreSistema(row) {
        return row && row.sistema && row.sistema.nombre ? String(row.sistema.nombre) : "Sin sistema";
    }

    function obtenerNombreCliente(row) {
        return row && row.cliente && row.cliente.nombre ? String(row.cliente.nombre) : "Sin cliente";
    }

    function planMeta(planesId) {
        const metas = {
            1: { icon: "fa-rocket", cls: "asignacion-plan-primary" },
            2: { icon: "fa-leaf", cls: "asignacion-plan-info" },
            3: { icon: "fa-check-circle", cls: "asignacion-plan-success" },
            4: { icon: "fa-star-half-alt", cls: "asignacion-plan-warning" },
            5: { icon: "fa-gem", cls: "asignacion-plan-danger" },
            6: { icon: "fa-gift", cls: "asignacion-plan-neutral" }
        };

        return metas[parseInt(planesId, 10)] || {
            icon: "fa-layer-group",
            cls: "asignacion-plan-neutral"
        };
    }

    function sistemaMeta(nombre) {
        switch (String(nombre || "").toUpperCase()) {
            case "CAMI":
                return { icon: "fa-stethoscope", cls: "asignacion-system-cami" };
            case "IZZY":
                return { icon: "fa-store", cls: "asignacion-system-izzy" };
            case "MONISYS":
                return { icon: "fa-chart-line", cls: "asignacion-system-monisys" };
            default:
                return { icon: "fa-cubes", cls: "asignacion-system-default" };
        }
    }

    function actualizarPermisosListado() {
        if (typeof getPermisosTipoUsuarioAccesosTable === "function" &&
            typeof getPrivilegioTipoUsuario === "function") {
            getPermisosTipoUsuarioAccesosTable(getPrivilegioTipoUsuario());
        }
    }

    /* =========================================================
       PERSISTENCIA MOSTRAR / OCULTAR
       ========================================================= */
    function leerEstadoVisible(clave, valorDefault) {
        try {
            const valor = localStorage.getItem(clave);

            if (valor === null) {
                return valorDefault;
            }

            return valor === "1";
        } catch (e) {
            return valorDefault;
        }
    }

    function guardarEstadoVisible(clave, visible) {
        try {
            localStorage.setItem(clave, visible ? "1" : "0");
        } catch (e) {
            // Si localStorage no está disponible, la vista sigue funcionando.
        }
    }

    function actualizarBotonToggle($boton, visible) {
        $boton.html(
            visible
                ? '<i class="fas fa-chevron-up mr-1"></i> Ocultar'
                : '<i class="fas fa-chevron-down mr-1"></i> Mostrar'
        );

        $boton.attr("aria-expanded", visible ? "true" : "false");
    }

    function configurarSeccionPersistente(config) {
        const $boton = $(config.button);
        const $body = $(config.body);

        if (!$boton.length || !$body.length) {
            return;
        }

        let visible = leerEstadoVisible(config.storageKey, true);

        $body.toggle(visible);
        actualizarBotonToggle($boton, visible);

        $boton.off("click.asignacionToggle").on("click.asignacionToggle", function() {
            visible = !visible;

            $body.stop(true, true).slideToggle(180, function() {
                $(this).toggle(visible);
            });

            guardarEstadoVisible(config.storageKey, visible);
            actualizarBotonToggle($boton, visible);
        });
    }

    function inicializarSeccionesPersistentes() {
        configurarSeccionPersistente({
            button: "#btn_toggle_asignacion_filtros",
            body: "#asignacion_filtros_body",
            storageKey: ASIGNAR_PLANES_STORAGE.filtros
        });

        configurarSeccionPersistente({
            button: "#btn_toggle_asignacion_kpis",
            body: "#asignacion_kpis_body",
            storageKey: ASIGNAR_PLANES_STORAGE.kpis
        });
    }

    /* =========================================================
       BOTÓN ACTUALIZAR PLAN
       ========================================================= */
    function obtenerBotonActualizarPlan() {
        let boton = $("#btn-asignar-plan");

        if (boton.length <= 0) {
            boton = $("#formAsignacionPlan").find("button[type='submit']").first();
        }

        return boton;
    }

    function bloquearBotonActualizarPlan() {
        const boton = obtenerBotonActualizarPlan();

        if (boton.length > 0) {
            boton
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin mr-1"></i> Actualizando...');
        }
    }

    function restaurarBotonActualizarPlan() {
        const boton = obtenerBotonActualizarPlan();

        if (boton.length > 0) {
            boton
                .prop("disabled", false)
                .html('<i class="fas fa-sync-alt mr-1"></i> Actualizar Plan');
        }
    }

    /* =========================================================
       RESET FORMULARIO
       ========================================================= */
    function resetFormularioAsignacion() {
        if ($("#formAsignacionPlan").length > 0) {
            $("#formAsignacionPlan")[0].reset();
        }

        $("#server_customers_id").val("");
        $("#user_extra").val(0);

        $("#formAsignacionPlan")
            .removeData("plan-actual-id")
            .removeData("plan-actual-nombre");

        if (typeof $.fn.selectpicker === "function") {
            $("#formAsignacionPlan .selectpicker").selectpicker("refresh");
        }
    }

    /* =========================================================
       CARGA DEL LISTADO
       ========================================================= */
    function cargarAsignaciones(mantenerPagina) {
        if (asignacionState.loading) {
            return;
        }

        asignacionState.loading = true;

        $("#asignacion_loading").removeClass("d-none");
        $("#asignacion_empty").addClass("d-none");

        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerAsignaciones,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (!response || response.success === false) {
                    asignacionState.rows = [];
                    asignacionState.filtered = [];

                    showNotify(
                        "error",
                        "Error",
                        response && response.message
                            ? response.message
                            : "No se pudieron cargar las asignaciones"
                    );

                    aplicarFiltrosYRender();
                    return;
                }

                asignacionState.rows = Array.isArray(response.data)
                    ? response.data
                    : [];

                if (!mantenerPagina) {
                    asignacionState.page = 1;
                }

                sincronizarCatalogosFiltros();
                aplicarFiltrosYRender();
            },
            error: function(xhr) {
                console.error("Error al cargar asignaciones:", xhr.responseText);

                asignacionState.rows = [];
                asignacionState.filtered = [];
                renderAsignaciones();

                showNotify(
                    "error",
                    "Error",
                    "No se pudieron cargar las asignaciones"
                );
            },
            complete: function() {
                asignacionState.loading = false;
                $("#asignacion_loading").addClass("d-none");
            }
        });
    }

    function sincronizarCatalogosFiltros() {
        const planSeleccionado = $("#filtro_plan").val() || "";
        const sistemaSeleccionado = $("#filtro_sistema").val() || "";

        const planes = {};
        const sistemas = {};

        asignacionState.rows.forEach(function(row) {
            const nombrePlan = obtenerNombrePlan(row);
            const nombreSistema = obtenerNombreSistema(row);

            planes[String(row.planes_id)] = nombrePlan;
            sistemas[String(row.sistema_id)] = nombreSistema;
        });

        const $plan = $("#filtro_plan");
        const $sistema = $("#filtro_sistema");

        $plan.empty().append('<option value="">Todos</option>');
        Object.keys(planes)
            .sort(function(a, b) {
                return planes[a].localeCompare(planes[b], "es");
            })
            .forEach(function(id) {
                $plan.append(
                    '<option value="' + limpiarHtml(id) + '">' +
                        limpiarHtml(planes[id]) +
                    '</option>'
                );
            });

        $sistema.empty().append('<option value="">Todos</option>');
        Object.keys(sistemas)
            .sort(function(a, b) {
                return sistemas[a].localeCompare(sistemas[b], "es");
            })
            .forEach(function(id) {
                $sistema.append(
                    '<option value="' + limpiarHtml(id) + '">' +
                        limpiarHtml(sistemas[id]) +
                    '</option>'
                );
            });

        if (planes[planSeleccionado]) {
            $plan.val(planSeleccionado);
        }

        if (sistemas[sistemaSeleccionado]) {
            $sistema.val(sistemaSeleccionado);
        }

        if (typeof $.fn.selectpicker === "function") {
            $plan.selectpicker("refresh");
            $sistema.selectpicker("refresh");
        }
    }

    function aplicarFiltrosYRender() {
        const filtroPlan = String($("#filtro_plan").val() || "");
        const filtroSistema = String($("#filtro_sistema").val() || "");
        const filtroValidar = String($("#filtro_validar").val() || "");
        const filtroDb = String($("#filtro_db").val() || "");
        const busqueda = normalizarTexto($("#buscar_asignacion").val());

        asignacionState.filtered = asignacionState.rows.filter(function(row) {
            if (filtroPlan && String(row.planes_id) !== filtroPlan) {
                return false;
            }

            if (filtroSistema && String(row.sistema_id) !== filtroSistema) {
                return false;
            }

            if (filtroValidar && String(row.validar) !== filtroValidar) {
                return false;
            }

            if (filtroDb && String(parseInt(row.db_disponible, 10) || 0) !== filtroDb) {
                return false;
            }

            if (busqueda) {
                const texto = normalizarTexto([
                    row.server_customers_id,
                    row.cliente_id,
                    obtenerNombreCliente(row),
                    row.cliente && row.cliente.identificacion,
                    row.cliente && row.cliente.codigo_cliente,
                    obtenerNombrePlan(row),
                    obtenerNombreSistema(row),
                    row.user_extra,
                    row.db_cliente,
                    row.db_mensaje
                ].join(" "));

                if (texto.indexOf(busqueda) === -1) {
                    return false;
                }
            }

            return true;
        });

        const totalPaginas = Math.max(
            1,
            Math.ceil(asignacionState.filtered.length / asignacionState.pageSize)
        );

        if (asignacionState.page > totalPaginas) {
            asignacionState.page = totalPaginas;
        }

        actualizarKpis();
        renderAsignaciones();
    }

    /* =========================================================
       KPIs
       ========================================================= */
    function actualizarKpis() {
        const rows = asignacionState.filtered;
        const planes = {};
        let usuariosExtra = 0;
        let basesDisponibles = 0;

        rows.forEach(function(row) {
            planes[String(row.planes_id)] = true;
            usuariosExtra += parseInt(row.user_extra, 10) || 0;

            if (parseInt(row.db_disponible, 10) === 1) {
                basesDisponibles++;
            }
        });

        $("#kpi_asignaciones_total").text(rows.length);
        $("#kpi_planes_uso").text(Object.keys(planes).length);
        $("#kpi_usuarios_extra").text(usuariosExtra);
        $("#kpi_bases_disponibles").text(basesDisponibles);
    }

    /* =========================================================
       RENDER DEL LISTADO POR DIVs
       ========================================================= */
    function renderBotonAccionesCliente(row, planNombre) {
        return '' +
            '<div class="ap-actions-wrap">' +
                '<button type="button" class="btn ap-actions-trigger table_editar ocultar" aria-expanded="false" ' +
                    'data-id="' + limpiarHtml(row.server_customers_id) + '" ' +
                    'data-cliente-id="' + limpiarHtml(row.cliente_id) + '" ' +
                    'data-plan-id="' + limpiarHtml(row.planes_id) + '" ' +
                    'data-plan-nombre="' + limpiarHtml(planNombre) + '" ' +
                    'data-sistema-id="' + limpiarHtml(row.sistema_id) + '" ' +
                    'data-sistema-nombre="' + limpiarHtml(obtenerNombreSistema(row)) + '" ' +
                    'data-user-extra="' + (parseInt(row.user_extra,10)||0) + '" ' +
                    'data-validar="' + limpiarHtml(row.validar) + '" ' +
                    'data-estado="' + limpiarHtml(row.estado) + '" ' +
                    'data-cliente-nombre="' + limpiarHtml(obtenerNombreCliente(row)) + '">' +
                    '<i class="fas fa-cog"></i><span>Acciones</span><i class="fas fa-chevron-down ml-1"></i>' +
                '</button>' +
            '</div>';
    }

    function renderAsignaciones() {
        const $listado = $("#asignacion_listado");
        const $empty = $("#asignacion_empty");

        $listado.empty();

        if (!asignacionState.filtered.length) {
            $empty.removeClass("d-none");
            $("#asignacion_resultado_info").text("0 registros");
            renderPaginacion(0);
            return;
        }

        $empty.addClass("d-none");

        const inicio = (asignacionState.page - 1) * asignacionState.pageSize;
        const fin = Math.min(
            inicio + asignacionState.pageSize,
            asignacionState.filtered.length
        );

        $listado
            .toggleClass("vista-miniatura", asignacionState.view === "miniatura")
            .toggleClass("vista-detalle", asignacionState.view === "detalle");

        /*
         * La vista Detalle conserva encabezados visibles aunque el listado
         * esté construido con DIVs. Esto mantiene la lectura tipo tabla
         * sin volver a depender de DataTable.
         */
        if (asignacionState.view === "detalle") {
            $listado.append(renderEncabezadoDetalle());
        }

        asignacionState.filtered
            .slice(inicio, fin)
            .forEach(function(row) {
                $listado.append(
                    asignacionState.view === "miniatura"
                        ? renderAsignacionMiniatura(row)
                        : renderAsignacionCard(row)
                );
            });

        $("#asignacion_resultado_info").text(
            "Mostrando " + (inicio + 1) + " a " + fin +
            " de " + asignacionState.filtered.length + " registros"
        );

        renderPaginacion(asignacionState.filtered.length);
        actualizarPermisosListado();
    }

    function renderEncabezadoDetalle() {
        return '' +
            '<div class="asignacion-detail-header" role="row">' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-building"></i>' +
                    '<span>Cliente</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-layer-group"></i>' +
                    '<span>Plan y Sistema</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-user-shield"></i>' +
                    '<span>Acceso</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-database"></i>' +
                    '<span>Sincronización</span>' +
                '</div>' +
                '<div class="asignacion-detail-header-cell" role="columnheader">' +
                    '<i class="fas fa-cog"></i>' +
                    '<span>Acciones</span>' +
                '</div>' +
            '</div>';
    }

    function renderAsignacionCard(row) {
        const clienteNombre = limpiarHtml(obtenerNombreCliente(row));
        const identificacion = limpiarHtml(
            row.cliente && row.cliente.identificacion
                ? row.cliente.identificacion
                : "Sin identificación"
        );
        const codigoCliente = limpiarHtml(
            row.cliente && row.cliente.codigo_cliente
                ? row.cliente.codigo_cliente
                : "Sin código"
        );

        const planNombre = limpiarHtml(obtenerNombrePlan(row));
        const sistemaNombre = limpiarHtml(obtenerNombreSistema(row));

        const plan = planMeta(row.planes_id);
        const sistema = sistemaMeta(obtenerNombreSistema(row));

        const userExtra = parseInt(row.user_extra, 10) || 0;
        const validar = parseInt(row.validar, 10) === 1;
        const activo = parseInt(row.estado, 10) === 1;
        const dbDisponible = parseInt(row.db_disponible, 10) === 1;

        const dbNombre = limpiarHtml(row.db_cliente || "No registrada");
        const dbMensaje = limpiarHtml(
            row.db_mensaje || (dbDisponible ? "Disponible" : "No disponible")
        );

        return '' +
            '<article class="asignacion-record-card">' +
                '<div class="asignacion-record-topline"></div>' +

                '<div class="asignacion-record-grid">' +

                    '<section class="asignacion-record-section asignacion-record-client">' +
                        '<div class="asignacion-main-box">' +
                            '<div class="asignacion-main-icon">' +
                                '<i class="fas fa-building"></i>' +
                            '</div>' +
                            '<div class="asignacion-main-content">' +
                                '<div class="asignacion-client-title">' +
                                    '<strong>' + clienteNombre + '</strong>' +
                                    '<span class="asignacion-status-badge ' +
                                        (activo ? 'is-active' : 'is-inactive') + '">' +
                                        '<i class="fas ' +
                                            (activo ? 'fa-check-circle' : 'fa-times-circle') +
                                        '"></i> ' +
                                        (activo ? 'Activo' : 'Inactivo') +
                                    '</span>' +
                                '</div>' +
                                '<small><i class="fas fa-id-card mr-1"></i> RTN: ' + identificacion + '</small>' +
                                '<small><i class="fas fa-barcode mr-1"></i> Código: ' + codigoCliente + '</small>' +
                            '</div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-layer-group"></i> Plan y Sistema' +
                        '</h4>' +
                        '<div class="asignacion-badge-row">' +
                            '<span class="asignacion-plan-badge ' + plan.cls + '">' +
                                '<i class="fas ' + plan.icon + '"></i> ' + planNombre +
                            '</span>' +
                            '<span class="asignacion-system-badge ' + sistema.cls + '">' +
                                '<i class="fas ' + sistema.icon + '"></i> ' + sistemaNombre +
                            '</span>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-user-shield"></i> Acceso' +
                        '</h4>' +
                        '<div class="asignacion-detail-line">' +
                            '<span class="asignacion-detail-icon"><i class="fas fa-user-plus"></i></span>' +
                            '<div><strong>Usuarios extra</strong><span>' +
                                (userExtra > 0 ? '+' + userExtra : 'Ninguno') +
                            '</span></div>' +
                        '</div>' +
                        '<div class="asignacion-detail-line">' +
                            '<span class="asignacion-detail-icon"><i class="fas fa-shield-alt"></i></span>' +
                            '<div><strong>Validar</strong><span>' +
                                (validar ? 'Sí' : 'No') +
                            '</span></div>' +
                        '</div>' +
                    '</section>' +

                    '<section class="asignacion-record-section">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-database"></i> Sincronización' +
                        '</h4>' +
                        '<div class="asignacion-db-name">' +
                            '<i class="fas fa-database mr-1"></i> ' + dbNombre +
                        '</div>' +
                        '<span class="asignacion-db-badge ' +
                            (dbDisponible ? 'is-ok' : 'is-error') + '">' +
                            '<i class="fas ' +
                                (dbDisponible ? 'fa-check-circle' : 'fa-exclamation-triangle') +
                            '"></i> ' +
                            (dbDisponible ? 'Disponible' : 'No disponible') +
                        '</span>' +
                        '<small class="asignacion-db-message">' + dbMensaje + '</small>' +
                        '<small class="asignacion-date">' +
                            '<i class="far fa-clock mr-1"></i> ' +
                            formatFechaHora(row.fecha_registro) +
                        '</small>' +
                    '</section>' +

                    '<section class="asignacion-record-section asignacion-record-actions">' +
                        '<h4 class="asignacion-record-title">' +
                            '<i class="fas fa-cog"></i> Acciones' +
                        '</h4>' +
                        renderBotonAccionesCliente(row, planNombre) +
                    '</section>' +

                '</div>' +
            '</article>';
    }

    function renderAsignacionMiniatura(row) {
        const clienteNombre = limpiarHtml(obtenerNombreCliente(row));
        const identificacion = limpiarHtml(
            row.cliente && row.cliente.identificacion
                ? row.cliente.identificacion
                : "Sin identificación"
        );
        const planNombre = limpiarHtml(obtenerNombrePlan(row));
        const sistemaNombre = limpiarHtml(obtenerNombreSistema(row));
        const plan = planMeta(row.planes_id);
        const sistema = sistemaMeta(obtenerNombreSistema(row));
        const userExtra = parseInt(row.user_extra, 10) || 0;
        const validar = parseInt(row.validar, 10) === 1;
        const activo = parseInt(row.estado, 10) === 1;
        const dbDisponible = parseInt(row.db_disponible, 10) === 1;

        return '' +
            '<article class="asignacion-mini-card">' +
                '<div class="asignacion-mini-topline"></div>' +
                '<div class="asignacion-mini-head">' +
                    '<div class="asignacion-mini-identidad">' +
                        '<div class="asignacion-main-icon"><i class="fas fa-building"></i></div>' +
                        '<div>' +
                            '<strong>' + clienteNombre + '</strong>' +
                            '<small><i class="fas fa-id-card mr-1"></i> RTN: ' + identificacion + '</small>' +
                        '</div>' +
                    '</div>' +
                    '<span class="asignacion-status-badge ' +
                        (activo ? 'is-active' : 'is-inactive') + '">' +
                        '<i class="fas ' +
                            (activo ? 'fa-check-circle' : 'fa-times-circle') +
                        '"></i> ' +
                        (activo ? 'Activo' : 'Inactivo') +
                    '</span>' +
                '</div>' +

                '<div class="asignacion-mini-badges">' +
                    '<span class="asignacion-plan-badge ' + plan.cls + '">' +
                        '<i class="fas ' + plan.icon + '"></i> ' + planNombre +
                    '</span>' +
                    '<span class="asignacion-system-badge ' + sistema.cls + '">' +
                        '<i class="fas ' + sistema.icon + '"></i> ' + sistemaNombre +
                    '</span>' +
                '</div>' +

                '<div class="asignacion-mini-stats">' +
                    '<div><small>Usuarios extra</small><strong>' +
                        (userExtra > 0 ? '+' + userExtra : '0') +
                    '</strong></div>' +
                    '<div><small>Validar</small><strong>' +
                        (validar ? 'Sí' : 'No') +
                    '</strong></div>' +
                    '<div><small>Base cliente</small><strong class="' +
                        (dbDisponible ? 'text-success' : 'text-danger') + '">' +
                        (dbDisponible ? 'Disponible' : 'No disponible') +
                    '</strong></div>' +
                '</div>' +

                '<div class="asignacion-mini-footer">' +
                    '<small><i class="far fa-clock mr-1"></i>' +
                        formatFechaHora(row.fecha_registro) +
                    '</small>' +
                    renderBotonAccionesCliente(row, planNombre) +
                '</div>' +
            '</article>';
    }

    function guardarTipoVista(vista) {
        try {
            localStorage.setItem(ASIGNAR_PLANES_STORAGE.vista, vista);
        } catch (e) {
            // La vista sigue funcionando aunque localStorage no esté disponible.
        }
    }

    function leerTipoVista() {
        try {
            const vista = localStorage.getItem(ASIGNAR_PLANES_STORAGE.vista);
            return vista === "miniatura" ? "miniatura" : "detalle";
        } catch (e) {
            return "detalle";
        }
    }

    function actualizarBotonesVista() {
        $(".asignacion-view-btn").removeClass("active");
        $(".asignacion-view-btn[data-view='" + asignacionState.view + "']")
            .addClass("active");
    }

    function sincronizarTamanoPaginaAsignacion() {
        const $select = $("#asignacion_page_size");
        const esMiniatura = asignacionState.view === "miniatura";
        const opciones = esMiniatura ? [6, 12, 18, 30] : [5, 10, 20, 50];
        const seleccionado = esMiniatura
            ? asignacionState.pageSizeMiniatura
            : asignacionState.pageSizeDetalle;

        $select.empty();

        opciones.forEach(function(valor) {
            $select.append(
                '<option value="' + valor + '">' + valor + '</option>'
            );
        });

        asignacionState.pageSize = opciones.indexOf(seleccionado) !== -1
            ? seleccionado
            : opciones[0];

        $select.val(String(asignacionState.pageSize));
    }

    /* =========================================================
       PAGINACIÓN
       ========================================================= */
    function renderPaginacion(totalRegistros) {
        const $nav = $("#asignacion_paginacion");
        $nav.empty();

        const totalPaginas = Math.max(
            1,
            Math.ceil(totalRegistros / asignacionState.pageSize)
        );

        if (totalRegistros <= 0) {
            return;
        }

        function boton(label, page, disabled, active, icon) {
            return '' +
                '<button type="button" ' +
                    'class="asignacion-page-btn' +
                        (disabled ? ' disabled' : '') +
                        (active ? ' active' : '') + '" ' +
                    'data-page="' + page + '" ' +
                    (disabled ? 'disabled' : '') + '>' +
                    (icon ? '<i class="fas ' + icon + '"></i>' : '') +
                    '<span>' + label + '</span>' +
                '</button>';
        }

        $nav.append(
            boton(
                "Inicio",
                1,
                asignacionState.page === 1,
                false,
                "fa-angle-double-left"
            )
        );

        $nav.append(
            boton(
                "Anterior",
                Math.max(1, asignacionState.page - 1),
                asignacionState.page === 1,
                false,
                "fa-angle-left"
            )
        );

        let desde = Math.max(1, asignacionState.page - 2);
        let hasta = Math.min(totalPaginas, desde + 4);

        desde = Math.max(1, hasta - 4);

        for (let pagina = desde; pagina <= hasta; pagina++) {
            $nav.append(
                boton(
                    String(pagina),
                    pagina,
                    false,
                    pagina === asignacionState.page,
                    ""
                )
            );
        }

        $nav.append(
            boton(
                "Siguiente",
                Math.min(totalPaginas, asignacionState.page + 1),
                asignacionState.page === totalPaginas,
                false,
                "fa-angle-right"
            )
        );

        $nav.append(
            boton(
                "Final",
                totalPaginas,
                asignacionState.page === totalPaginas,
                false,
                "fa-angle-double-right"
            )
        );
    }

    /* =========================================================
       CARGAR CLIENTES
       ========================================================= */
    function cargarClientes() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerClientes,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#cliente_id");

                    select.empty();
                    select.append('<option value="">Seleccione un cliente...</option>');

                    response.data.forEach(function(cliente) {
                        select.append(
                            '<option value="' + cliente.clientes_id + '" ' +
                                'data-subtext="' +
                                limpiarHtml(cliente.identificacion || "Sin identificación") +
                                '">' +
                                limpiarHtml(cliente.nombre) +
                            '</option>'
                        );
                    });

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }
                } else {
                    showNotify(
                        "error",
                        "Error",
                        response.message || "Error al cargar clientes"
                    );
                }
            },
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error de conexión al cargar clientes"
                );
            }
        });
    }

    /* =========================================================
       CARGAR PLANES
       ========================================================= */
    function cargarPlanes() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerPlanes,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#planes_id");

                    select.empty();
                    select.append('<option value="">Seleccione un plan...</option>');

                    response.data.forEach(function(plan) {
                        select.append(
                            '<option value="' + plan.planes_id + '">' +
                                limpiarHtml(plan.nombre) +
                            '</option>'
                        );
                    });

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }
                } else {
                    showNotify(
                        "error",
                        "Error",
                        response.message || "Error al cargar planes"
                    );
                }
            },
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error de conexión al cargar planes"
                );
            }
        });
    }

    /* =========================================================
       CARGAR SISTEMAS
       ========================================================= */
    function cargarSistemas() {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.obtenerSistemas,
            type: "POST",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    const select = $("#sistema_id");

                    select.empty();

                    response.data.forEach(function(sistema) {
                        select.append(
                            '<option value="' + sistema.sistema_id + '">' +
                                limpiarHtml(sistema.nombre) +
                            '</option>'
                        );
                    });

                    if (typeof $.fn.selectpicker === "function") {
                        select.selectpicker("refresh");
                    }

                    select.prop("disabled", true);
                }
            },
            error: function(xhr) {
                console.error("Error al cargar sistemas:", xhr.responseText);
            }
        });
    }

    /* =========================================================
       VERIFICAR PLAN DEL CLIENTE
       ========================================================= */
    function verificarPlanCliente(clienteId, callback) {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.verificarPlanCliente,
            type: "POST",
            data: {
                cliente_id: clienteId
            },
            dataType: "json",
            success: function(response) {
                if (typeof callback === "function") {
                    callback(response);
                }
            },
            error: function() {
                showNotify(
                    "error",
                    "Error",
                    "Error al verificar plan del cliente"
                );
            }
        });
    }

    function aplicarDatosPlanAlFormulario(data) {
        $("#server_customers_id").val(data.server_customers_id || "");
        $("#planes_id").val(data.planes_id || "").selectpicker("refresh");
        $("#sistema_id").val(data.sistema_id || "").selectpicker("refresh");
        $("#user_extra").val(parseInt(data.user_extra, 10) || 0);
        $("#validar").val(data.validar).selectpicker("refresh");
        $("#estado").val(data.estado).selectpicker("refresh");

        const nombrePlan = $("#planes_id option:selected").text().trim();

        $("#formAsignacionPlan")
            .data("plan-actual-id", String(data.planes_id || ""))
            .data("plan-actual-nombre", nombrePlan || "Sin plan");
    }

    /* =========================================================
       ACTUALIZAR PLAN CLIENTE
       ========================================================= */
    function actualizarPlanCliente(formData) {
        $.ajax({
            url: ASIGNAR_PLANES_URLS.actualizarPlanCliente,
            type: "POST",
            data: formData,
            dataType: "json",
            beforeSend: function() {
                bloquearBotonActualizarPlan();
            },
            success: function(response) {
                restaurarBotonActualizarPlan();

                showNotify(
                    response.type || "info",
                    response.title || "Resultado",
                    response.message || "Proceso finalizado"
                );

                if (response.success) {
                    cargarAsignaciones(true);
                    resetFormularioAsignacion();
                }
            },
            error: function(xhr) {
                restaurarBotonActualizarPlan();

                console.error("Error al actualizar plan:", xhr.responseText);

                let mensaje = "Error al actualizar plan";

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }

                showNotify("error", "Error", mensaje);
            }
        });
    }

    /* =========================================================
       CONFIRMACIÓN PREMIUM DE CAMBIO DE PLAN
       ========================================================= */
    function construirContenidoConfirmacion(datos) {
        const contenedor = document.createElement("div");
        contenedor.className = "asignacion-swal-content";

        contenedor.innerHTML =
            '<div class="asignacion-swal-client">' +
                '<div class="asignacion-swal-client-icon">' +
                    '<i class="fas fa-building"></i>' +
                '</div>' +
                '<div class="asignacion-swal-client-info">' +
                    '<small>CLIENTE</small>' +
                    '<strong>' + limpiarHtml(datos.cliente) + '</strong>' +
                    '<span>Revise la información antes de confirmar el cambio.</span>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-change">' +
                '<div class="asignacion-swal-plan-box">' +
                    '<small>PLAN ACTUAL</small>' +
                    '<strong>' + limpiarHtml(datos.planActual) + '</strong>' +
                '</div>' +
                '<div class="asignacion-swal-arrow" aria-hidden="true">' +
                    '<i class="fas fa-arrow-right"></i>' +
                '</div>' +
                '<div class="asignacion-swal-plan-box is-new">' +
                    '<small>NUEVO PLAN</small>' +
                    '<strong>' + limpiarHtml(datos.planNuevo) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-grid">' +
                '<div>' +
                    '<small><i class="fas fa-cubes"></i> Sistema</small>' +
                    '<strong>' + limpiarHtml(datos.sistema) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-user-plus"></i> Usuarios extra</small>' +
                    '<strong>' + limpiarHtml(datos.usuariosExtra) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-shield-alt"></i> Validación</small>' +
                    '<strong>' + limpiarHtml(datos.validar) + '</strong>' +
                '</div>' +
                '<div>' +
                    '<small><i class="fas fa-toggle-on"></i> Estado</small>' +
                    '<strong>' + limpiarHtml(datos.estado) + '</strong>' +
                '</div>' +
            '</div>' +

            '<div class="asignacion-swal-notice">' +
                '<i class="fas fa-sync-alt"></i>' +
                '<div>' +
                    '<strong>¿Qué ocurrirá al confirmar?</strong>' +
                    '<span>IZZY actualizará la asignación y sincronizará el cambio en la base principal y en la base del cliente, conservando la configuración correspondiente al plan seleccionado.</span>' +
                '</div>' +
            '</div>';

        return contenedor;
    }

    function confirmarCambioPlan(datos, callback) {
        if (typeof swal !== "function") {
            callback(true);
            return;
        }

        const confirmacion = swal({
            title: "Confirmar cambio de plan",
            content: construirContenidoConfirmacion(datos),
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancelar",
                    value: null,
                    visible: true,
                    className: "btn btn-secondary",
                    closeModal: true
                },
                confirm: {
                    text: "Sí, actualizar plan",
                    value: true,
                    visible: true,
                    className: "btn btn-primary",
                    closeModal: true
                }
            },
            dangerMode: false,
            closeOnEsc: true,
            closeOnClickOutside: false
        });

        setTimeout(function() {
            $(".asignacion-swal-content")
                .closest(".swal-modal")
                .addClass("asignacion-swal-modal");
        }, 0);

        confirmacion.then(function(confirmado) {
            callback(confirmado === true);
        });
    }

    /* =========================================================
       REPORTES - EXCEL / PDF
       ========================================================= */
    function asignacionFechaReporte() {
        return new Date().toLocaleDateString("es-HN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        });
    }

    function asignacionExportRows() {
        return asignacionState.filtered.map(function(row) {
            return {
                cliente: obtenerNombreCliente(row),
                rtn: row.cliente && row.cliente.identificacion
                    ? row.cliente.identificacion
                    : "Sin identificación",
                plan: obtenerNombrePlan(row),
                sistema: obtenerNombreSistema(row),
                usuariosExtra: parseInt(row.user_extra, 10) || 0,
                validar: parseInt(row.validar, 10) === 1 ? "Sí" : "No",
                baseCliente: parseInt(row.db_disponible, 10) === 1
                    ? "Disponible"
                    : "No disponible",
                estado: parseInt(row.estado, 10) === 1 ? "Activo" : "Inactivo",
                fecha: formatFechaHora(row.fecha_registro)
            };
        });
    }

    function asignacionDescargarBlob(contenido, nombre, tipo, yaEsBlob) {
        const blob = yaEsBlob
            ? contenido
            : new Blob([contenido], {type: tipo});

        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.download = nombre;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    }

    function asignacionXmlEscape(value) {
        return String(value === null || typeof value === "undefined" ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&apos;");
    }

    function asignacionExcelColName(index) {
        let name = "";
        let n = index + 1;

        while (n > 0) {
            const mod = (n - 1) % 26;
            name = String.fromCharCode(65 + mod) + name;
            n = Math.floor((n - 1) / 26);
        }

        return name;
    }

    function asignacionExcelCell(ref, value, styleId, numeric) {
        if (numeric) {
            const numero = Number(value);

            if (!isNaN(numero)) {
                return '<c r="' + ref + '" s="' + styleId + '"><v>' +
                    numero +
                '</v></c>';
            }
        }

        const raw = String(value === null || typeof value === "undefined" ? "" : value);
        const preserve = /^\s|\s$/.test(raw)
            ? ' xml:space="preserve"'
            : "";

        return '<c r="' + ref + '" s="' + styleId + '" t="inlineStr">' +
            '<is><t' + preserve + '>' +
                asignacionXmlEscape(raw) +
            '</t></is></c>';
    }

    function asignacionGenerarXlsx(rows) {
        if (typeof JSZip === "undefined") {
            return null;
        }

        const headers = [
            "Cliente",
            "RTN",
            "Plan",
            "Sistema",
            "Usuarios Extra",
            "Validar",
            "Base Cliente",
            "Estado",
            "Fecha"
        ];

        const totalActivos = rows.filter(function(row) {
            return row.estado === "Activo";
        }).length;

        const totalBases = rows.filter(function(row) {
            return row.baseCliente === "Disponible";
        }).length;

        const totalUsuariosExtra = rows.reduce(function(acc, row) {
            return acc + Number(row.usuariosExtra || 0);
        }, 0);

        const planes = {};
        rows.forEach(function(row) {
            planes[row.plan] = true;
        });

        const dataRows = rows.map(function(row) {
            return [
                row.cliente,
                row.rtn,
                row.plan,
                row.sistema,
                row.usuariosExtra,
                row.validar,
                row.baseCliente,
                row.estado,
                row.fecha
            ];
        });

        const lastCol = asignacionExcelColName(headers.length - 1);
        const headerRow = 7;
        const firstDataRow = 8;
        const lastRow = Math.max(headerRow, headerRow + dataRows.length);
        const sheetRows = [];

        sheetRows.push(
            '<row r="1" ht="30" customHeight="1">' +
                asignacionExcelCell(
                    "A1",
                    "IZZY • REPORTE DE ASIGNACIÓN DE PLANES",
                    1,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="2" ht="20" customHeight="1">' +
                asignacionExcelCell(
                    "A2",
                    "Control de planes, sistemas y sincronización • Generado: " +
                        asignacionFechaReporte(),
                    2,
                    false
                ) +
            '</row>'
        );

        sheetRows.push(
            '<row r="3" ht="18" customHeight="1">' +
                asignacionExcelCell("A3", "REGISTROS", 6, false) +
                asignacionExcelCell("C3", "ACTIVOS", 6, false) +
                asignacionExcelCell("E3", "PLANES EN USO", 6, false) +
                asignacionExcelCell("G3", "BASES DISPONIBLES", 6, false) +
            '</row>'
        );

        sheetRows.push(
            '<row r="4" ht="26" customHeight="1">' +
                asignacionExcelCell("A4", rows.length, 7, true) +
                asignacionExcelCell("C4", totalActivos, 7, true) +
                asignacionExcelCell("E4", Object.keys(planes).length, 7, true) +
                asignacionExcelCell("G4", totalBases, 7, true) +
                asignacionExcelCell("I4", totalUsuariosExtra, 7, true) +
            '</row>'
        );

        sheetRows.push('<row r="5"></row>');
        sheetRows.push(
            '<row r="6" ht="18" customHeight="1">' +
                asignacionExcelCell(
                    "A6",
                    "Detalle de asignaciones filtradas",
                    8,
                    false
                ) +
            '</row>'
        );

        const headerCells = headers.map(function(header, index) {
            return asignacionExcelCell(
                asignacionExcelColName(index) + headerRow,
                header,
                3,
                false
            );
        }).join("");

        sheetRows.push(
            '<row r="' + headerRow + '" ht="26" customHeight="1">' +
                headerCells +
            '</row>'
        );

        dataRows.forEach(function(row, rowIndex) {
            const excelRow = firstDataRow + rowIndex;

            const cells = row.map(function(value, colIndex) {
                const numeric = colIndex === 4;
                let style = 4;

                if (colIndex === 6) {
                    style = value === "Disponible" ? 9 : 10;
                } else if (colIndex === 7) {
                    style = value === "Activo" ? 9 : 10;
                } else if (colIndex === 4) {
                    style = 5;
                }

                return asignacionExcelCell(
                    asignacionExcelColName(colIndex) + excelRow,
                    value,
                    style,
                    numeric
                );
            }).join("");

            sheetRows.push(
                '<row r="' + excelRow + '" ht="22" customHeight="1">' +
                    cells +
                '</row>'
            );
        });

        const sheetXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<dimension ref="A1:' + lastCol + lastRow + '"/>' +
                '<sheetViews><sheetView workbookViewId="0" showGridLines="0">' +
                    '<pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/>' +
                    '<selection pane="bottomLeft" activeCell="A8" sqref="A8"/>' +
                '</sheetView></sheetViews>' +
                '<sheetFormatPr defaultRowHeight="15"/>' +
                '<cols>' +
                    '<col min="1" max="1" width="34" customWidth="1"/>' +
                    '<col min="2" max="2" width="20" customWidth="1"/>' +
                    '<col min="3" max="4" width="19" customWidth="1"/>' +
                    '<col min="5" max="5" width="15" customWidth="1"/>' +
                    '<col min="6" max="6" width="13" customWidth="1"/>' +
                    '<col min="7" max="8" width="19" customWidth="1"/>' +
                    '<col min="9" max="9" width="22" customWidth="1"/>' +
                '</cols>' +
                '<sheetData>' + sheetRows.join("") + '</sheetData>' +
                '<autoFilter ref="A' + headerRow + ':' + lastCol + lastRow + '"/>' +
                '<mergeCells count="10">' +
                    '<mergeCell ref="A1:I1"/>' +
                    '<mergeCell ref="A2:I2"/>' +
                    '<mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/>' +
                    '<mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/>' +
                    '<mergeCell ref="E3:F3"/><mergeCell ref="E4:F4"/>' +
                    '<mergeCell ref="G3:I3"/><mergeCell ref="G4:H4"/>' +
                '</mergeCells>' +
                '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>' +
                '<pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/>' +
            '</worksheet>';

        const stylesXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
                '<fonts count="7">' +
                    '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>' +
                    '<font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' +
                    '<font><sz val="9"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font>' +
                    '<font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font>' +
                '</fonts>' +
                '<fills count="7">' +
                    '<fill><patternFill patternType="none"/></fill>' +
                    '<fill><patternFill patternType="gray125"/></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF17324D"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill>' +
                    '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill>' +
                '</fills>' +
                '<borders count="2">' +
                    '<border><left/><right/><top/><bottom/><diagonal/></border>' +
                    '<border>' +
                        '<left style="thin"><color rgb="FFDDE3EA"/></left>' +
                        '<right style="thin"><color rgb="FFDDE3EA"/></right>' +
                        '<top style="thin"><color rgb="FFDDE3EA"/></top>' +
                        '<bottom style="thin"><color rgb="FFDDE3EA"/></bottom>' +
                        '<diagonal/>' +
                    '</border>' +
                '</borders>' +
                '<cellStyleXfs count="1">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' +
                '</cellStyleXfs>' +
                '<cellXfs count="11">' +
                    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' +
                    '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                    '<xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' +
                '</cellXfs>' +
                '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
            '</styleSheet>';

        const workbookXml =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' +
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<bookViews><workbookView activeTab="0"/></bookViews>' +
                '<sheets><sheet name="Asignaciones" sheetId="1" r:id="rId1"/></sheets>' +
            '</workbook>';

        const workbookRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" ' +
                    'Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" ' +
                    'Target="styles.xml"/>' +
            '</Relationships>';

        const rootRels =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" ' +
                    'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" ' +
                    'Target="xl/workbook.xml"/>' +
            '</Relationships>';

        const contentTypes =
            '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
                '<Default Extension="xml" ContentType="application/xml"/>' +
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
            '</Types>';

        const zip = new JSZip();

        zip.file("[Content_Types].xml", contentTypes);
        zip.folder("_rels").file(".rels", rootRels);
        zip.folder("xl").file("workbook.xml", workbookXml);
        zip.folder("xl").file("styles.xml", stylesXml);
        zip.folder("xl").folder("_rels").file("workbook.xml.rels", workbookRels);
        zip.folder("xl").folder("worksheets").file("sheet1.xml", sheetXml);

        const opciones = {
            type: "blob",
            mimeType: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            compression: "DEFLATE"
        };

        if (typeof zip.generateAsync === "function") {
            return zip.generateAsync(opciones);
        }

        if (typeof zip.generate === "function") {
            try {
                return Promise.resolve(zip.generate(opciones));
            } catch (errorGenerate) {
                return Promise.reject(errorGenerate);
            }
        }

        return Promise.reject(
            new Error("La versión de JSZip no soporta la generación de XLSX.")
        );
    }

    function exportarAsignacionesExcel() {
        if (!asignacionState.filtered.length) {
            showNotify(
                "warning",
                "Sin información",
                "No hay asignaciones para exportar."
            );
            return;
        }

        const rows = asignacionExportRows();
        const promesaXlsx = asignacionGenerarXlsx(rows);

        if (!promesaXlsx) {
            const headers = [
                "Cliente",
                "RTN",
                "Plan",
                "Sistema",
                "Usuarios Extra",
                "Validar",
                "Base Cliente",
                "Estado",
                "Fecha"
            ];

            const csv = [headers].concat(
                rows.map(function(row) {
                    return [
                        row.cliente,
                        row.rtn,
                        row.plan,
                        row.sistema,
                        row.usuariosExtra,
                        row.validar,
                        row.baseCliente,
                        row.estado,
                        row.fecha
                    ];
                })
            ).map(function(line) {
                return line.map(function(value) {
                    const texto = String(
                        value === null || typeof value === "undefined"
                            ? ""
                            : value
                    ).replace(/"/g, '""');

                    return '"' + texto + '"';
                }).join(",");
            }).join("\r\n");

            asignacionDescargarBlob(
                "\ufeff" + csv,
                "Reporte_Asignacion_Planes.csv",
                "text/csv;charset=utf-8;"
            );

            showNotify(
                "warning",
                "Excel compatible",
                "JSZip no está disponible; se generó un CSV compatible con Excel."
            );
            return;
        }

        promesaXlsx
            .then(function(blob) {
                asignacionDescargarBlob(
                    blob,
                    "Reporte_Asignacion_Planes.xlsx",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    true
                );
            })
            .catch(function(error) {
                console.error("Error al generar XLSX:", error);

                showNotify(
                    "error",
                    "Error",
                    "No se pudo generar el archivo Excel."
                );
            });
    }

    function asignacionPdfDato(label, value, color) {
        return {
            stack: [
                {
                    text: String(label || "").toUpperCase(),
                    fontSize: 6.4,
                    bold: true,
                    color: "#6B778C",
                    margin: [0, 0, 0, 2]
                },
                {
                    text: String(
                        value === null || typeof value === "undefined" || value === ""
                            ? "—"
                            : value
                    ),
                    fontSize: 8.1,
                    bold: true,
                    color: color || "#172B4D"
                }
            ]
        };
    }


    function asignacionObtenerLogoPdf(callback) {
        function convertirLogo(source) {
            source = String(source || '').trim();

            if (!source) {
                if (typeof showNotify === 'function') {
                    showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
                }
                return;
            }

            if (source.indexOf('data:image/') === 0) {
                callback(source);
                return;
            }

            var img = new Image();
            img.crossOrigin = 'Anonymous';

            img.onload = function () {
                try {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    canvas.width = img.naturalWidth || img.width;
                    canvas.height = img.naturalHeight || img.height;
                    ctx.drawImage(img, 0, 0);
                    var dataUrl = canvas.toDataURL('image/png');

                    if (!dataUrl || dataUrl.indexOf('data:image/') !== 0) {
                        throw new Error('No se pudo convertir el logo a Data URL.');
                    }

                    try { imagen = dataUrl; } catch (e) {}
                    callback(dataUrl);
                } catch (error) {
                    console.error('Error preparando logo PDF:', error);
                    if (typeof showNotify === 'function') {
                        showNotify('error', 'Logo no disponible', 'No se pudo preparar el logo para el reporte PDF.');
                    }
                }
            };

            img.onerror = function () {
                if (typeof showNotify === 'function') {
                    showNotify('error', 'Logo no disponible', 'No se pudo cargar el logo para el reporte PDF.');
                }
            };

            img.src = source;
        }

        if (typeof imagen !== 'undefined' && imagen) {
            convertirLogo(imagen);
            return;
        }

        $.ajax({
            type: 'GET',
            url: '<?php echo SERVERURL;?>core/get_image.php',
            dataType: 'text',
            timeout: 15000
        }).done(function (imageUrl) {
            convertirLogo(imageUrl);
        }).fail(function (xhr) {
            console.error('Error obteniendo logo PDF:', xhr.responseText);
            if (typeof showNotify === 'function') {
                showNotify('error', 'Logo no disponible', 'No se pudo obtener el logo para el reporte PDF.');
            }
        });
    }

    function asignacionPdfLogoPlate(logoDataUrl) {
        return {
            table: {
                widths: ['*'],
                body: [[{
                    image: logoDataUrl,
                    fit: [62, 36],
                    alignment: 'center',
                    margin: [7, 5, 7, 5],
                    fillColor: '#FFFFFF'
                }]]
            },
            layout: {
                hLineColor: function () { return '#DDE3EA'; },
                vLineColor: function () { return '#DDE3EA'; },
                hLineWidth: function () { return .5; },
                vLineWidth: function () { return .5; },
                paddingLeft: function () { return 0; },
                paddingRight: function () { return 0; },
                paddingTop: function () { return 0; },
                paddingBottom: function () { return 0; }
            }
        };
    }
    function asignacionPdfEncabezado(rows, totalActivos, totalBases, totalUsuariosExtra, totalPlanes) {
        const logo = asignacionPdfLogoPlate(imagen);

        const filtroPlan = $("#filtro_plan option:selected").text() || "Todos";
        const filtroSistema = $("#filtro_sistema option:selected").text() || "Todos";
        const filtroEstado = $("#filtro_estado option:selected").text() || "Todos";

        return [
            {
                table: {
                    widths: [100, "*", 150],
                    body: [[
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [12, 10, 0, 10],
                            stack: [logo]
                        },
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [0, 10, 0, 10],
                            stack: [
                                {text: "REPORTE DE ASIGNACIÓN DE PLANES", fontSize: 16, bold: true, color: "#FFFFFF"},
                                {text: "Control de planes, sistemas, accesos y sincronización de clientes", fontSize: 7.5, color: "#D8E5F0", margin: [0, 2, 0, 0]}
                            ]
                        },
                        {
                            border: [false, false, false, false],
                            fillColor: "#17324D",
                            margin: [0, 10, 12, 10],
                            stack: [
                                {text: "REPORTE EJECUTIVO", fontSize: 6.5, bold: true, color: "#72E2E5", alignment: "right"},
                                {text: asignacionFechaReporte(), fontSize: 9, bold: true, color: "#FFFFFF", alignment: "right", margin: [0, 3, 0, 0]},
                                {text: rows.length + " registro(s) filtrado(s)", fontSize: 6.5, color: "#D8E5F0", alignment: "right", margin: [0, 2, 0, 0]}
                            ]
                        }
                    ]]
                },
                layout: {hLineWidth: function(){return 0;}, vLineWidth: function(){return 0;}},
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*"],
                    body: [[{
                        text: "Filtros aplicados: Plan: " + filtroPlan + "   |   Sistema: " + filtroSistema + "   |   Estado: " + filtroEstado,
                        fontSize: 6.8,
                        color: "#52627A",
                        margin: [10, 7, 10, 7],
                        fillColor: "#F7F9FC"
                    }]]
                },
                layout: {
                    hLineColor: function(){return "#DDE3EA";},
                    vLineColor: function(){return "#DDE3EA";},
                    hLineWidth: function(){return 0.6;},
                    vLineWidth: function(){return 0.6;}
                },
                margin: [0, 0, 0, 10]
            },
            {
                table: {
                    widths: ["*", "*", "*", "*", "*"],
                    body: [[
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"REGISTROS",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(rows.length),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"ACTIVOS",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalActivos),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"PLANES EN USO",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalPlanes),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"USUARIOS EXTRA",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalUsuariosExtra),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]},
                        {fillColor:"#F7F9FC", margin:[8,7,8,7], stack:[{text:"BASES DISPONIBLES",fontSize:6.3,bold:true,color:"#6B778C"},{text:String(totalBases),fontSize:13,bold:true,color:"#172B4D",margin:[0,2,0,0]}]}
                    ]]
                },
                layout: {
                    hLineColor: function(){return "#DDE3EA";},
                    vLineColor: function(){return "#DDE3EA";},
                    hLineWidth: function(){return 0.6;},
                    vLineWidth: function(){return 0.6;}
                },
                margin: [0, 0, 0, 12]
            }
        ];
    }

    function asignacionPdfMiniaturaCard(row) {
        return {
            table: {
                widths: ["*"],
                body: [[{
                    margin: [10, 9, 10, 9],
                    stack: [
                        {
                            columns: [
                                {
                                    width: "*",
                                    stack: [
                                        {
                                            text: row.cliente,
                                            fontSize: 10,
                                            bold: true,
                                            color: "#172B4D"
                                        },
                                        {
                                            text: "RTN: " + row.rtn,
                                            fontSize: 7,
                                            color: "#6B778C",
                                            margin: [0, 2, 0, 0]
                                        }
                                    ]
                                },
                                {
                                    width: "auto",
                                    text: row.estado,
                                    fontSize: 7.2,
                                    bold: true,
                                    color: row.estado === "Activo"
                                        ? "#14804A"
                                        : "#C9372C",
                                    alignment: "right"
                                }
                            ]
                        },
                        {
                            canvas: [{
                                type: "line",
                                x1: 0,
                                y1: 0,
                                x2: 235,
                                y2: 0,
                                lineWidth: 0.6,
                                lineColor: "#DDE3EA"
                            }],
                            margin: [0, 7, 0, 7]
                        },
                        {
                            columns: [
                                {
                                    width: "50%",
                                    stack: [
                                        asignacionPdfDato("Plan", row.plan),
                                        {
                                            margin: [0, 7, 0, 0],
                                            stack: [
                                                asignacionPdfDato("Sistema", row.sistema)
                                            ]
                                        }
                                    ]
                                },
                                {
                                    width: "50%",
                                    stack: [
                                        asignacionPdfDato("Usuarios extra", row.usuariosExtra),
                                        {
                                            margin: [0, 7, 0, 0],
                                            columns: [
                                                {
                                                    width: "45%",
                                                    stack: [
                                                        asignacionPdfDato("Validar", row.validar)
                                                    ]
                                                },
                                                {
                                                    width: "55%",
                                                    stack: [
                                                        asignacionPdfDato(
                                                            "Base cliente",
                                                            row.baseCliente,
                                                            row.baseCliente === "Disponible"
                                                                ? "#14804A"
                                                                : "#C9372C"
                                                        )
                                                    ]
                                                }
                                            ]
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            text: "Actualización: " + row.fecha,
                            fontSize: 6.6,
                            color: "#7A869A",
                            margin: [0, 8, 0, 0]
                        }
                    ]
                }]]
            },
            layout: {
                hLineColor: function() { return "#DDE3EA"; },
                vLineColor: function() { return "#DDE3EA"; },
                hLineWidth: function() { return 0.7; },
                vLineWidth: function() { return 0.7; }
            }
        };
    }

    function asignacionPdfContenidoMiniatura(rows) {
        const contenido = [
            {
                text: "VISTA MINIATURA",
                fontSize: 6.8,
                bold: true,
                color: "#17324D",
                margin: [0, 1, 0, 7]
            }
        ];

        for (let i = 0; i < rows.length; i += 2) {
            const columnas = [
                {
                    width: "*",
                    stack: [asignacionPdfMiniaturaCard(rows[i])]
                }
            ];

            if (rows[i + 1]) {
                columnas.push({width: 10, text: ""});
                columnas.push({
                    width: "*",
                    stack: [asignacionPdfMiniaturaCard(rows[i + 1])]
                });
            } else {
                columnas.push({width: 10, text: ""});
                columnas.push({width: "*", text: ""});
            }

            contenido.push({
                columns: columnas,
                columnGap: 0,
                margin: [0, 0, 0, 9]
            });
        }

        return contenido;
    }

    function asignacionPdfContenidoDetalle(rows) {
        const body = [[
            {text: "CLIENTE", style: "th", fillColor: "#17324D"},
            {text: "RTN", style: "th", fillColor: "#17324D"},
            {text: "PLAN", style: "th", fillColor: "#17324D"},
            {text: "SISTEMA", style: "th", fillColor: "#17324D"},
            {text: "USUARIOS EXTRA", style: "th", fillColor: "#17324D"},
            {text: "VALIDAR", style: "th", fillColor: "#17324D"},
            {text: "BASE CLIENTE", style: "th", fillColor: "#17324D"},
            {text: "ESTADO", style: "th", fillColor: "#17324D"},
            {text: "ACTUALIZACIÓN", style: "th", fillColor: "#17324D"}
        ]];

        rows.forEach(function(row) {
            body.push([
                {text: row.cliente, style: "tdStrong"},
                {text: row.rtn, style: "td"},
                {text: row.plan, style: "td"},
                {text: row.sistema, style: "td"},
                {text: String(row.usuariosExtra), style: "tdCenter"},
                {text: row.validar, style: "tdCenter"},
                {
                    text: row.baseCliente,
                    style: "tdCenter",
                    color: row.baseCliente === "Disponible"
                        ? "#14804A"
                        : "#C9372C",
                    bold: true
                },
                {
                    text: row.estado,
                    style: "tdCenter",
                    color: row.estado === "Activo"
                        ? "#14804A"
                        : "#C9372C",
                    bold: true
                },
                {text: row.fecha, style: "td"}
            ]);
        });

        return [
            {
                text: "VISTA DETALLE",
                fontSize: 6.8,
                bold: true,
                color: "#17324D",
                margin: [0, 1, 0, 7]
            },
            {
                table: {
                    headerRows: 1,
                    widths: [92, 66, 66, 54, 48, 42, 66, 46, "*"],
                    body: body
                },
                layout: {
                    fillColor: function(rowIndex) {
                        if (rowIndex === 0) {
                            return "#17324D";
                        }

                        return rowIndex % 2 === 0
                            ? "#F7F9FC"
                            : "#FFFFFF";
                    },
                    hLineColor: function() { return "#DDE3EA"; },
                    vLineColor: function() { return "#DDE3EA"; },
                    hLineWidth: function() { return 0.6; },
                    vLineWidth: function() { return 0.6; },
                    paddingLeft: function() { return 5; },
                    paddingRight: function() { return 5; },
                    paddingTop: function() { return 5; },
                    paddingBottom: function() { return 5; }
                }
            }
        ];
    }

    function apAbrirVisorPdfPublico(url, titulo, nombreArchivo) {
        if (typeof abrirModalPdfPublico !== "function") {
            apNotify("error", "Visor PDF no disponible", "No se encontró el modal PDF público.");
            return false;
        }
        const $modal = $("#modal_pdf_publico");
        if ($modal.length) {
            if ($modal[0].parentNode !== document.body) document.body.appendChild($modal[0]);
            $modal.addClass("ap-from-assignment");
            $modal.off("show.bs.modal.apPdfLayer shown.bs.modal.apPdfLayer hidden.bs.modal.apPdfLayer")
                .on("show.bs.modal.apPdfLayer shown.bs.modal.apPdfLayer", function(){
                    $(this).css("z-index", "2600");
                    setTimeout(function(){
                        $("body > .modal-backdrop").last().addClass("ap-pdf-backdrop");
                    }, 0);
                })
                .on("hidden.bs.modal.apPdfLayer", function(){
                    $("body > .modal-backdrop.ap-pdf-backdrop").removeClass("ap-pdf-backdrop");
                    if ($("#modalAdministrarCliente.show,#modal_registrar_usuarios.show,#modal_registrar_empresa.show,#modal_registrar_secuencias.show,#modal_documentos_secuencia.show,#modal_registrar_privilegios.show,#modal_registrar_menuaccesos.show,#modal_registrar_submenuaccesos.show,#modal_registrar_submenu1accesos.show,#modal_registrar_tipoUsuario.show,#modal_permisos.show").length) {
                        $(document.body).addClass("modal-open");
                    }
                });
        }
        abrirModalPdfPublico(url, titulo, nombreArchivo);
        setTimeout(function(){ $("body > .modal-backdrop").last().addClass("ap-pdf-backdrop"); }, 20);
        return true;
    }

    function exportarAsignacionesPDF() {
        if (!(typeof imagen !== "undefined" && typeof imagen === "string" && imagen.indexOf("data:image/") === 0)) {
            asignacionObtenerLogoPdf(function(logoDataUrl) {
                try { imagen = logoDataUrl; } catch (e) {}
                exportarAsignacionesPDF();
            });
            return;
        }

        if (!asignacionState.filtered.length) {
            showNotify(
                "warning",
                "Sin información",
                "No hay asignaciones para exportar."
            );
            return;
        }

        if (typeof pdfMake === "undefined") {
            showNotify(
                "warning",
                "PDF no disponible",
                "La librería PDF no está cargada en esta pantalla."
            );
            return;
        }

        const rows = asignacionExportRows();

        const totalActivos = rows.filter(function(row) {
            return row.estado === "Activo";
        }).length;

        const totalBases = rows.filter(function(row) {
            return row.baseCliente === "Disponible";
        }).length;

        const totalUsuariosExtra = rows.reduce(function(acc, row) {
            return acc + Number(row.usuariosExtra || 0);
        }, 0);

        const planes = {};

        rows.forEach(function(row) {
            planes[row.plan] = true;
        });

        const esMiniatura = asignacionState.view === "miniatura";

        const contenido = asignacionPdfEncabezado(
            rows,
            totalActivos,
            totalBases,
            totalUsuariosExtra,
            Object.keys(planes).length
        ).concat(
            esMiniatura
                ? asignacionPdfContenidoMiniatura(rows)
                : asignacionPdfContenidoDetalle(rows)
        );

        const docDefinition = {
            pageSize: "LETTER",
            pageOrientation: "landscape",
            pageMargins: [32, 34, 32, 34],
            header: function() {
                return {
                    margin: [32, 14, 32, 0],
                    canvas: [{
                        type: "line",
                        x1: 0,
                        y1: 0,
                        x2: 724,
                        y2: 0,
                        lineWidth: 2,
                        lineColor: "#0EA5A8"
                    }]
                };
            },
            footer: function(currentPage, pageCount) {
                return {
                    margin: [32, 8, 32, 0],
                    columns: [
                        {
                            text: "IZZY • Asignación de Planes",
                            fontSize: 7,
                            color: "#7A869A"
                        },
                        {
                            text: "Página " + currentPage + " de " + pageCount,
                            fontSize: 7,
                            color: "#7A869A",
                            alignment: "right"
                        }
                    ]
                };
            },
            content: contenido,
            styles: {
                th: {
                    fontSize: 6.4,
                    bold: true,
                    color: "#FFFFFF",
                    alignment: "center"
                },
                td: {
                    fontSize: 6.5,
                    color: "#253858",
                    margin: [0, 1, 0, 1]
                },
                tdStrong: {
                    fontSize: 6.5,
                    bold: true,
                    color: "#172B4D",
                    margin: [0, 1, 0, 1]
                },
                tdCenter: {
                    fontSize: 6.5,
                    color: "#253858",
                    alignment: "center",
                    margin: [0, 1, 0, 1]
                }
            },
            defaultStyle: {
                fontSize: 8,
                color: "#253858"
            }
        };

        const pdf = pdfMake.createPdf(docDefinition);
        const nombrePdf = "Reporte_Asignacion_Planes.pdf";

        if (typeof abrirModalPdfPublico !== "function") {
            showNotify(
                "error",
                "Visor PDF no disponible",
                "No se encontró el modal PDF público."
            );
            return;
        }

        if (typeof pdf.getDataUrl === "function") {
            pdf.getDataUrl(function(dataUrl) {
                apAbrirVisorPdfPublico(
                    dataUrl,
                    "Asignación de Planes",
                    nombrePdf
                );
            });
            return;
        }

        if (typeof pdf.getBase64 === "function") {
            pdf.getBase64(function(base64) {
                apAbrirVisorPdfPublico(
                    "data:application/pdf;base64," + base64,
                    "Asignación de Planes",
                    nombrePdf
                );
            });
            return;
        }

        showNotify(
            "error",
            "PDF no disponible",
            "La versión actual de pdfMake no permite una vista previa compatible."
        );
    }

    /* =========================================================
       EVENTOS - LISTADO
       ========================================================= */
    $("#btn_actualizar_asignaciones")
        .off("click.asignacion")
        .on("click.asignacion", function() {
            cargarAsignaciones(true);
        });

    $("#btn_exportar_asignaciones_excel")
        .off("click.asignacionReporte")
        .on("click.asignacionReporte", exportarAsignacionesExcel);

    $("#btn_exportar_asignaciones_pdf")
        .off("click.asignacionReporte")
        .on("click.asignacionReporte", exportarAsignacionesPDF);

    $("#formFiltrosAsignacion")
        .off("submit.asignacion")
        .on("submit.asignacion", function(e) {
            e.preventDefault();
            asignacionState.page = 1;
            aplicarFiltrosYRender();
        });

    $("#formFiltrosAsignacion")
        .off("reset.asignacion")
        .on("reset.asignacion", function() {
            const form = this;

            setTimeout(function() {
                $(form).find(".selectpicker").val("").selectpicker("refresh");
                asignacionState.page = 1;
                aplicarFiltrosYRender();
            }, 50);
        });

    $("#filtro_plan, #filtro_sistema, #filtro_validar, #filtro_db")
        .off("changed.bs.select.asignacion change.asignacion")
        .on("changed.bs.select.asignacion change.asignacion", function() {
            asignacionState.page = 1;
            aplicarFiltrosYRender();
        });

    $("#buscar_asignacion")
        .off("input.asignacion")
        .on("input.asignacion", function() {
            clearTimeout(asignacionDebounceTimer);

            asignacionDebounceTimer = setTimeout(function() {
                asignacionState.page = 1;
                aplicarFiltrosYRender();
            }, 180);
        });

    $("#asignacion_page_size")
        .off("change.asignacion")
        .on("change.asignacion", function() {
            const value = parseInt($(this).val(), 10);

            asignacionState.pageSize = isNaN(value) || value <= 0
                ? (asignacionState.view === "miniatura" ? 12 : 10)
                : value;

            if (asignacionState.view === "miniatura") {
                asignacionState.pageSizeMiniatura = asignacionState.pageSize;
            } else {
                asignacionState.pageSizeDetalle = asignacionState.pageSize;
            }

            asignacionState.page = 1;
            renderAsignaciones();
        });

    $(".asignacion-view-btn")
        .off("click.asignacionVista")
        .on("click.asignacionVista", function() {
            const vista = String($(this).data("view") || "detalle");

            asignacionState.view = vista === "miniatura"
                ? "miniatura"
                : "detalle";

            guardarTipoVista(asignacionState.view);
            actualizarBotonesVista();
            sincronizarTamanoPaginaAsignacion();
            asignacionState.page = 1;
            renderAsignaciones();
        });

    $("#asignacion_paginacion")
        .off("click.asignacion")
        .on("click.asignacion", "button[data-page]", function() {
            if ($(this).prop("disabled")) {
                return;
            }

            const page = parseInt($(this).data("page"), 10);

            if (!isNaN(page)) {
                asignacionState.page = page;
                renderAsignaciones();
            }
        });

    $(document)
        .off("click.asignacionEditar", ".btn-editar-asignacion")
        .on("click.asignacionEditar", ".btn-editar-asignacion", function() {
            $("#server_customers_id").val($(this).data("id"));
            $("#cliente_id").val($(this).data("cliente-id")).selectpicker("refresh");
            $("#planes_id").val($(this).data("plan-id")).selectpicker("refresh");
            $("#sistema_id").val($(this).data("sistema-id")).selectpicker("refresh");
            $("#user_extra").val($(this).data("user-extra"));
            $("#validar").val($(this).data("validar")).selectpicker("refresh");
            $("#estado").val($(this).data("estado")).selectpicker("refresh");

            $("#formAsignacionPlan")
                .data("plan-actual-id", String($(this).data("plan-id")))
                .data(
                    "plan-actual-nombre",
                    String($(this).data("plan-nombre") || "Sin plan")
                );

            if ($("#div_top").length > 0) {
                $("html, body").animate({
                    scrollTop: $("#div_top").offset().top - 20
                }, 350);
            }
        });

    /* =========================================================
       CAMBIO DE CLIENTE
       ========================================================= */
    $("#cliente_id")
        .off("changed.bs.select.asignacion change.asignacion")
        .on("changed.bs.select.asignacion change.asignacion", function() {
            const clienteId = $(this).val();

            if (!clienteId) {
                $("#server_customers_id").val("");
                $("#user_extra").val(0);

                $("#formAsignacionPlan")
                    .removeData("plan-actual-id")
                    .removeData("plan-actual-nombre");

                return;
            }

            verificarPlanCliente(clienteId, function(response) {
                if (!response || response.success === false) {
                    showNotify(
                        "error",
                        "Error",
                        response && response.message
                            ? response.message
                            : "No se pudo verificar el plan del cliente"
                    );
                    return;
                }

                if (response.exists && response.data) {
                    aplicarDatosPlanAlFormulario(response.data);
                } else {
                    showNotify(
                        "warning",
                        "Sin asignación",
                        "El cliente seleccionado no tiene una asignación de plan registrada."
                    );
                }
            });
        });

    /* =========================================================
       SUBMIT FORMULARIO
       ========================================================= */
    $("#formAsignacionPlan")
        .off("submit.asignacion")
        .on("submit.asignacion", function(e) {
            e.preventDefault();

            const clienteId = $("#cliente_id").val();
            const serverCustomersId = $("#server_customers_id").val();
            const planesId = $("#planes_id").val();
            const userExtra = parseInt($("#user_extra").val(), 10);

            if (!clienteId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Debe seleccionar un cliente"
                );
                return;
            }

            if (!serverCustomersId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "El cliente seleccionado no tiene registro server_customers"
                );
                return;
            }

            if (!planesId) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Debe seleccionar un plan"
                );
                return;
            }

            if (isNaN(userExtra) || userExtra < 0) {
                showNotify(
                    "warning",
                    "Advertencia",
                    "Los usuarios extra no pueden ser negativos"
                );
                return;
            }

            const datos = {
                cliente: $("#cliente_id option:selected").text().trim(),
                planActual:
                    $("#formAsignacionPlan").data("plan-actual-nombre") ||
                    "No determinado",
                planNuevo: $("#planes_id option:selected").text().trim(),
                sistema: $("#sistema_id option:selected").text().trim(),
                usuariosExtra: userExtra === 0 ? "Ninguno" : "+" + userExtra,
                validar: $("#validar option:selected").text().trim(),
                estado: $("#estado option:selected").text().trim()
            };

            confirmarCambioPlan(datos, function(confirmado) {
                if (!confirmado) {
                    return;
                }

                const formData = $("#formAsignacionPlan").serialize();
                actualizarPlanCliente(formData);
            });
        });

    /* =========================================================
       ADMINISTRACIÓN CENTRAL DE CLIENTES
       ========================================================= */
    const apAdminState = {
        serverCustomerId: 0,
        currentPlanId: 0,
        planConfig: {},
        planName: "",
        data: null,
        rows: [],
        filtered: [],
        page: 1,
        pageSize: 10,
        view: window.innerWidth < 768 ? "mini" : "detail",
        collaboratorStatus: "1",
        companyStatus: "1",
        companyView: window.innerWidth < 768 ? "mini" : "detail",
        accessTab: "directory",
        accessLoaded: false,
        accessLoading: false,
        privileges: [],
        types: [],
        permissionsByType: {},
        privilegeStatus: "1",
        privilegeView: window.innerWidth < 768 ? "mini" : "detail",
        privilegeSearch: "",
        typeStatus: "1",
        typeView: window.innerWidth < 768 ? "mini" : "detail",
        typeSearch: ""
    };

    /*
     * Los modales deben vivir directamente bajo <body>.
     * Si permanecen dentro de un contenedor de la plantilla con transform,
     * position:fixed deja de usar el viewport real y el centrado se desplaza.
     */
    const AP_ADMIN_MODAL_SELECTOR = "#modalAdministrarCliente,#modalApColaborador,#modal_registrar_privilegios,#modal_registrar_menuaccesos,#modal_registrar_submenuaccesos,#modal_registrar_submenu1accesos,#modal_registrar_tipoUsuario,#modal_permisos";

    function apMountAdminModalsToBody() {
        $(AP_ADMIN_MODAL_SELECTOR).each(function() {
            if (this.parentNode !== document.body) {
                document.body.appendChild(this);
            }
        });
    }

    apMountAdminModalsToBody();

    function apOpenStaticModal(selector) {
        const $modal = $(selector);

        if (!$modal.length) {
            return;
        }

        // Montar ANTES de inicializar/mostrar Bootstrap evita el reflow visual
        // que hacía aparecer el modal pequeño y luego crecer.
        if ($modal[0].parentNode !== document.body) {
            document.body.appendChild($modal[0]);
        }

        $modal.modal({
            backdrop: "static",
            keyboard: true,
            show: false
        });
        $modal.modal("show");
    }

    function apNotify(type, title, text) {
        if (typeof showNotify === "function") {
            showNotify(type, title, text);
            return;
        }

        const logger = type === "error" ? "error" : (type === "warning" ? "warn" : "log");
        console[logger](title + ": " + text);
    }

    function apConfirm(title, text, callback) {
        if (typeof swal === "function") {
            const confirmacion = swal({
                title: title,
                text: text,
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        value: false,
                        visible: true,
                        className: "ap-swal-btn ap-swal-btn-cancel"
                    },
                    confirm: {
                        text: "Sí, continuar",
                        value: true,
                        visible: true,
                        className: "ap-swal-btn ap-swal-btn-confirm"
                    }
                },
                dangerMode: false,
                closeOnClickOutside: false,
                closeOnEsc: true
            });

            if (confirmacion && typeof confirmacion.then === "function") {
                confirmacion.then(function(valor) { callback(valor === true); });
            }
            return;
        }

        if (typeof Swal !== "undefined" && Swal.fire) {
            Swal.fire({
                title: title,
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, continuar",
                cancelButtonText: "Cancelar",
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
                customClass: {
                    confirmButton: "ap-swal2-btn ap-swal2-btn-confirm",
                    cancelButton: "ap-swal2-btn ap-swal2-btn-cancel"
                },
                buttonsStyling: false
            }).then(function(result) { callback(!!result.isConfirmed); });
            return;
        }

        apNotify("warning", "Confirmación no disponible", "No se encontró SweetAlert para confirmar esta acción.");
        callback(false);
    }

    function apPost(action, data) {
        return $.ajax({
            url: ASIGNAR_PLANES_URLS.administrarCliente,
            method: "POST",
            dataType: "json",
            data: $.extend({ action: action, server_customers_id: apAdminState.serverCustomerId }, data || {})
        });
    }

    function apAjaxError(xhr, context) {
        let mensaje = "";
        try {
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = String(xhr.responseJSON.message);
            } else if (xhr && xhr.responseText) {
                const parsed = JSON.parse(xhr.responseText);
                mensaje = parsed && parsed.message ? String(parsed.message) : "";
            }
        } catch (e) {}

        if (!mensaje) {
            const status = xhr && xhr.status ? " (HTTP " + xhr.status + ")" : "";
            mensaje = "No se pudo completar la operación" + status + ". Verifique la conexión e inténtelo nuevamente.";
        }

        apNotify("error", context || "No se pudo completar", mensaje);
    }

    function apOpenActionsPortal($trigger) {
        let $portal = $("#ap_actions_portal");
        if (!$portal.length) {
            $portal = $('<div id="ap_actions_portal" class="ap-actions-portal" role="menu"></div>').appendTo(document.body);
        }
        const d = $trigger.data();
        const sistemaNombre = String(d.sistemaNombre || "").trim().toUpperCase();
        const esIzzy = sistemaNombre === "IZZY";

        let menuHtml =
            '<button type="button" class="btn-editar-asignacion ap-action-item" role="menuitem">' +
                '<span class="ap-item-icon ap-item-icon-edit"><i class="fas fa-edit"></i></span>' +
                '<span class="ap-item-copy"><strong>Editar plan</strong><small>Plan y acceso del cliente</small></span>' +
            '</button>';

        if (esIzzy) {
            menuHtml +=
                '<button type="button" class="ap-manage-client ap-action-item" role="menuitem">' +
                    '<span class="ap-item-icon ap-item-icon-users"><i class="fas fa-users-cog"></i></span>' +
                    '<span class="ap-item-copy"><strong>Colaboradores y usuarios</strong><small>Administrar accesos IZZY</small></span>' +
                '</button>';
        }

        $portal.html(menuHtml);
        $portal.find("button").each(function() {
            const $b = $(this);
            Object.keys(d).forEach(function(k) { $b.attr("data-" + k.replace(/[A-Z]/g, function(m){return "-"+m.toLowerCase();}), d[k]); });
        });
        $(".ap-actions-trigger").attr("aria-expanded", "false");
        $trigger.attr("aria-expanded", "true");
        $portal.addClass("is-open");
        const rect = $trigger[0].getBoundingClientRect();
        const pw = $portal.outerWidth();
        const ph = $portal.outerHeight();
        const margin = 8;
        let left = rect.right - pw;
        if (left < margin) left = margin;
        if (left + pw > window.innerWidth - margin) left = window.innerWidth - pw - margin;
        let top = rect.bottom + 6;
        if (top + ph > window.innerHeight - margin && rect.top - ph - 6 >= margin) top = rect.top - ph - 6;
        if (top + ph > window.innerHeight - margin) top = Math.max(margin, window.innerHeight - ph - margin);
        $portal.css({ left: Math.round(left) + "px", top: Math.round(top) + "px" });
    }

    function apCloseActionsPortal() {
        $("#ap_actions_portal").removeClass("is-open").removeData("owner");
        $(".ap-actions-trigger").attr("aria-expanded", "false");
    }

    $(document)
        .off("click.apActions", ".ap-actions-trigger")
        .on("click.apActions", ".ap-actions-trigger", function(e) {
            e.preventDefault(); e.stopPropagation();
            const $this = $(this);
            const $portal = $("#ap_actions_portal");
            if ($portal.hasClass("is-open") && String($portal.data("owner")) === String($this.data("id"))) {
                apCloseActionsPortal(); return;
            }
            apOpenActionsPortal($this);
            $("#ap_actions_portal").data("owner", $this.data("id"));
        })
        .off("click.apActionsOutside")
        .on("click.apActionsOutside", function(e) {
            if (!$(e.target).closest("#ap_actions_portal,.ap-actions-trigger,#ap_collab_actions_portal,.ap-collab-actions-trigger").length) { apCloseActionsPortal(); apCloseCollaboratorActionsPortal(); }
        })
        .off("click.apManage", ".ap-manage-client")
        .on("click.apManage", ".ap-manage-client", function() {
            const id = parseInt($(this).data("id"), 10) || 0;
            const sistemaNombre = String($(this).data("sistema-nombre") || "").trim().toUpperCase();
            apCloseActionsPortal();
            if (!id) return;
            if (sistemaNombre !== "IZZY") {
                apNotify("warning", "Administración no disponible", "Por ahora la administración de colaboradores, usuarios y empresas está habilitada únicamente para clientes IZZY.");
                return;
            }
            apAdminState.serverCustomerId = id;
            apAdminState.currentPlanId = parseInt($(this).data("plan-id"), 10) || 0;
            apAdminState.planConfig = {};
            apAdminState.accessLoaded = false;
            apAdminState.privileges = [];
            apAdminState.types = [];
            apAdminState.permissionsByType = {};
            apAdminState.accessTab = "directory";
            $(".ap-access-subtab").removeClass("active")
                .filter('[data-ap-access-tab="directory"]').addClass("active");
            $(".ap-access-subpanel").removeClass("active");
            $("#ap_access_directory").addClass("active");
            apAdminState.planName = String($(this).data("plan-nombre") || "").trim();
            apBillingState.documentos = [];
            apBillingState.secuencias = [];
            apBillingState.empresas = [];
            apBillingState.loading = false;
            apBillingState.sequenceStatus = "1";
            apBillingState.documentStatus = "1";
            apAdminState.collaboratorStatus = "1";
            apAdminState.companyStatus = "1";
            $("#ap_sequence_status_filter [data-sequence-status]").removeClass("active").filter('[data-sequence-status="1"]').addClass("active");
            $("#ap_company_status_filter [data-company-status]").removeClass("active").filter('[data-company-status="1"]').addClass("active");
            $("#ap_collaborator_status_filter [data-collaborator-status]").removeClass("active").filter('[data-collaborator-status="1"]').addClass("active");
            $("#ap_sequence_list").empty();
            $("#ap_total_secuencias,#ap_total_documentos,#ap_total_empresas_facturacion").text("0");
            $("#ap_admin_server_customer_id").val(id);
            apOpenStaticModal("#modalAdministrarCliente");
            apLoadAdmin();
        });

    $(window).off("resize.apActions scroll.apActions").on("resize.apActions scroll.apActions", function(){ apCloseActionsPortal(); apCloseCollaboratorActionsPortal(); });

    $(document).off("select2:open.apAdmin").on("select2:open.apAdmin", function(){
        setTimeout(function(){ const el=document.querySelector(".select2-container--open .select2-search__field"); if(el) el.focus(); }, 0);
    });

    function apLoadExactPlanLimits() {
        const planId = parseInt(apAdminState.currentPlanId, 10) || 0;
        if (!planId) {
            apAdminState.planConfig = {};
            return $.Deferred().resolve().promise();
        }

        return apBillingRequest('plan_limits', { planes_id: planId })
            .done(function(resp) {
                if (!resp || !resp.success || !resp.data) return;

                const plan = resp.data.plan || {};
                const config = plan.configuraciones && typeof plan.configuraciones === 'object'
                    ? plan.configuraciones
                    : {};

                apAdminState.planConfig = config;
                apAdminState.planName = String(plan.nombre || apAdminState.planName || '');

                apAdminState.data = apAdminState.data || {};
                apAdminState.data.plan = $.extend({}, apAdminState.data.plan || {}, plan, {
                    configuraciones: config
                });
                apAdminState.data.configuraciones = config;
                apAdminState.data.limites = $.extend({}, apAdminState.data.limites || {}, {
                    usuarios_plan: resp.data.limites ? resp.data.limites.usuarios : null,
                    empresas_plan: resp.data.limites ? resp.data.limites.empresas : null,
                    perfiles: resp.data.limites ? resp.data.limites.perfiles : null
                });
            })
            .fail(function(xhr) {
                console.warn('No se pudieron obtener los límites exactos desde planes.configuraciones.', xhr && xhr.responseText ? xhr.responseText : xhr);
                apAdminState.planConfig = {};
            });
    }

    function apLoadAdmin() {
        $("#ap_admin_loading").removeClass("d-none");
        $("#ap_admin_content").addClass("d-none");
        return apPost("list").done(function(resp) {
            if (!resp || !resp.success) {
                apNotify("error", "No se pudo cargar", resp && resp.message ? resp.message : "Error consultando el cliente.");
                $("#ap_admin_loading").addClass("d-none");
                $("#ap_admin_content").removeClass("d-none");
                return;
            }

            apAdminState.data = resp.data || {};
            apAdminState.rows = apAdminState.data.colaboradores || [];
            apAdminState.page = 1;

            apLoadExactPlanLimits().always(function() {
                const c = apAdminState.data.cliente || {};
                $("#ap_cliente_nombre").text(c.nombre || "Cliente");
                $("#ap_cliente_rtn").text(c.rtn || "Sin RTN");
                $("#ap_cliente_codigo").text(c.codigo_cliente || "Sin código");
                $("#ap_cliente_db").text(c.db || "Sin base");
                $("#modalAdministrarClienteTitulo").html('<i class="fas fa-users-cog mr-2"></i>Administración · ' + limpiarHtml(c.nombre || "Cliente"));
                apRefreshCounters();
                apFillCatalogs();
                apUpdatePlanLimitUI();
                apApplyFilter();
                apRenderCompanies();
                if (apAdminState.accessTab !== "directory") apLoadAccessData(false);
                if ($("#ap_panel_secuencias").hasClass("active")) apLoadBillingData(false);
                $("#ap_admin_loading").addClass("d-none");
                $("#ap_admin_content").removeClass("d-none");
                setTimeout(function(){ $("#ap_search").trigger("focus"); }, 80);
            });
        }).fail(function(xhr) {
            apAjaxError(xhr, "No se pudo cargar la administración");
            console.error(xhr && xhr.responseText ? xhr.responseText : xhr);
            $("#ap_admin_loading").addClass("d-none");
            $("#ap_admin_content").removeClass("d-none");
        });
    }

    function apRefreshCounters() {
        const users = apAdminState.rows.reduce(function(n, c) { return n + ((c.usuarios || []).length); }, 0);
        $("#ap_total_colaboradores").text(apAdminState.rows.length);
        $("#ap_total_usuarios").text(users);
    }

    function apFillSelect($select, rows, placeholder, selected) {
        let html = '<option value="">' + limpiarHtml(placeholder) + '</option>';
        (rows || []).forEach(function(r) { html += '<option value="' + limpiarHtml(r.id || r.empresa_id) + '">' + limpiarHtml(r.nombre || r.razon_social || "Sin nombre") + '</option>'; });
        $select.html(html);
        if (selected !== undefined && selected !== null) $select.val(String(selected));
    }

    function apInitSelect2($select, modalSelector, placeholder) {
        if (typeof $.fn.select2 !== "function" || !$select.length) return;
        if ($select.hasClass("select2-hidden-accessible")) $select.select2("destroy");
        $select.select2({
            width: "100%",
            placeholder: placeholder || "Seleccione una opción",
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownParent: $(modalSelector)
        });
    }

    function apFillCatalogs() {
        const d = apAdminState.data || {}, cat = d.catalogos || {}, empresas = d.empresas || [];
        apFillSelect($("#ap_colab_puesto"), cat.puestos || [], "Seleccione un puesto");
        apFillSelect($("#ap_colab_empresa"), empresas, "Seleccione una empresa");
        apFillSelect($("#ap_user_empresa"), empresas, "Seleccione una empresa");
        apFillSelect($("#ap_user_privilegio"), cat.privilegios || [], "Seleccione un privilegio");
        apFillSelect($("#ap_user_tipo"), cat.tipos_usuario || [], "Seleccione permisos");
        apInitSelect2($("#ap_colab_puesto"), "#modalApColaborador", "Seleccione un puesto");
        apInitSelect2($("#ap_colab_empresa"), "#modalApColaborador", "Seleccione una empresa");
    }

    function apPlanLimits() {
        return (apAdminState.data && apAdminState.data.limites) || {};
    }

    function apLimitNumber(value) {
        const n = parseInt(value, 10);
        return Number.isFinite(n) && n >= 0 ? n : 0;
    }

    function apParseConfigObject(value) {
        if (!value) return {};
        if (typeof value === "object") return value;
        if (typeof value !== "string") return {};
        try {
            const parsed = JSON.parse(value);
            return parsed && typeof parsed === "object" ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function apNormalizarClaveLimite(value) {
        return String(value == null ? "" : value)
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .replace(/[^a-z0-9]/g, "");
    }

    function apNumeroLimiteValido(value) {
        if (value === null || value === undefined || value === "") return null;
        if (typeof value === "object") return null;
        const raw = String(value).replace(/,/g, "").trim();
        const match = raw.match(/-?\d+/);
        if (!match) return null;
        const n = parseInt(match[0], 10);
        return Number.isFinite(n) && n > 0 ? n : null;
    }

    function apBuscarLimiteEnConfiguracion(source, aliases, depth) {
        if (depth > 6 || source === null || source === undefined) return null;

        if (typeof source === "string") {
            const parsed = apParseConfigObject(source);
            if (parsed && Object.keys(parsed).length) {
                return apBuscarLimiteEnConfiguracion(parsed, aliases, depth + 1);
            }
            return null;
        }

        if (Array.isArray(source)) {
            for (let i = 0; i < source.length; i++) {
                const found = apBuscarLimiteEnConfiguracion(source[i], aliases, depth + 1);
                if (found !== null) return found;
            }
            return null;
        }

        if (typeof source !== "object") return null;

        /*
         * Soporta configuraciones guardadas como objeto directo:
         *   { "Puntos de Venta": 1 }
         *   { "puntos_venta": 1 }
         * y también catálogos tipo:
         *   { clave: "Puntos de Venta", valor: 1 }
         *   { nombre: "Usuarios", cantidad: 3 }
         */
        const labelKeys = ["clave", "key", "nombre", "name", "configuracion", "config", "descripcion", "label", "titulo"];
        const valueKeys = ["valor", "value", "cantidad", "limite", "maximo", "max", "numero", "total"];

        let label = "";
        for (let i = 0; i < labelKeys.length; i++) {
            if (source[labelKeys[i]] !== undefined && source[labelKeys[i]] !== null) {
                label = apNormalizarClaveLimite(source[labelKeys[i]]);
                if (label) break;
            }
        }

        if (label && aliases.indexOf(label) !== -1) {
            for (let i = 0; i < valueKeys.length; i++) {
                if (source[valueKeys[i]] === undefined) continue;
                const n = apNumeroLimiteValido(source[valueKeys[i]]);
                if (n !== null) return n;
            }
        }

        const entries = Object.keys(source);
        for (let i = 0; i < entries.length; i++) {
            const key = entries[i];
            const normalized = apNormalizarClaveLimite(key);
            if (aliases.indexOf(normalized) !== -1) {
                const n = apNumeroLimiteValido(source[key]);
                if (n !== null) return n;
            }
        }

        for (let i = 0; i < entries.length; i++) {
            const value = source[entries[i]];
            if (value && typeof value === "object") {
                const found = apBuscarLimiteEnConfiguracion(value, aliases, depth + 1);
                if (found !== null) return found;
            }
        }

        return null;
    }

    function apFindLimitValue(kind) {
        /*
         * FUENTE DE VERDAD DEL PLAN:
         * planes.configuraciones
         *   usuarios = cantidad máxima de usuarios
         *   perfiles = cantidad máxima de empresas / puntos de venta
         *
         * Primero se leen estas claves exactas. Los aliases quedan únicamente
         * como respaldo para instalaciones antiguas o respuestas incompletas.
         */
        const exactConfig = apAdminState.planConfig && typeof apAdminState.planConfig === "object"
            ? apAdminState.planConfig
            : {};
        const exactKey = kind === "user" ? "usuarios" : "perfiles";
        const exactValue = apNumeroLimiteValido(exactConfig[exactKey]);
        if (exactValue !== null) return exactValue;

        const d = apAdminState.data || {};
        const l = d.limites || {};

        const apiExact = kind === "user" ? l.usuarios_plan : l.empresas_plan;
        const apiExactValue = apNumeroLimiteValido(apiExact);
        if (apiExactValue !== null) return apiExactValue;

        const client = d.cliente || {};
        const plan = d.plan || client.plan || {};

        const aliases = (kind === "user"
            ? [
                "usuarios", "usuario", "usuarioslimite", "limiteusuarios", "usuariospermitidos",
                "maxusuarios", "usuariosmaximos", "maxusers", "userlimit", "cantidadusuarios",
                "totalusuarios"
            ]
            : [
                /* perfiles es la clave real usada por la tabla planes */
                "perfiles", "perfil", "perfileslimite", "limiteperfiles", "perfilespermitidos",
                "maxperfiles", "cantidadperfiles", "totalperfiles",
                /* aliases históricos */
                "empresas", "empresa", "empresaslimite", "limiteempresas", "empresaspermitidas",
                "maxempresas", "empresasmaximas", "maxcompanies", "companylimit", "cantidadempresas",
                "totalempresas", "puntodeventa", "puntosdeventa", "puntosventa", "puntoventa",
                "limitedepuntosdeventa", "limitepuntosdeventa", "limitepuntosventa",
                "puntosdeventapermitidos", "puntosventapermitidos", "maxpuntosdeventa",
                "maxpuntosventa", "cantidadpuntosdeventa", "totalpuntosdeventa"
            ]).map(apNormalizarClaveLimite);

        const sources = [
            exactConfig,
            l,
            l.configuraciones,
            d.configuraciones,
            plan,
            plan.configuraciones,
            client,
            client.configuraciones,
            client.plan,
            client.plan && client.plan.configuraciones
        ];

        for (let i = 0; i < sources.length; i++) {
            const found = apBuscarLimiteEnConfiguracion(sources[i], aliases, 0);
            if (found !== null) return found;
        }

        return null;
    }

    function apCurrentLimitValue(kind) {
        const l = apPlanLimits();
        const explicit = kind === "user" ? l.usuarios_actuales : l.empresas_actuales;
        const parsed = parseInt(explicit, 10);
        if (Number.isFinite(parsed) && parsed >= 0) return parsed;
        if (kind === "user") {
            return apAdminState.rows.reduce(function(total, c) { return total + ((c.usuarios || []).length); }, 0);
        }
        const empresas = (apBillingState && apBillingState.empresas && apBillingState.empresas.length)
            ? apBillingState.empresas
            : ((apAdminState.data && apAdminState.data.empresas) || []);
        return empresas.length;
    }

    function apLimitLabel(current, limit, singular, plural) {
        const actual = Math.max(0, parseInt(current, 10) || 0);
        const max = limit === null || limit === undefined ? null : parseInt(limit, 10);
        if (!Number.isFinite(max) || max <= 0) {
            const noun = actual === 1 ? singular : plural;
            return actual + " " + noun + " · límite no configurado";
        }
        const noun = max === 1 ? singular : plural;
        return actual + " / " + max + " " + noun;
    }

    function apInjectPlanLimitIntoPublicModal($modal, kind) {
        if (!$modal || !$modal.length) return;
        const isUser = kind === "user";
        const current = apCurrentLimitValue(kind);
        const limit = apFindLimitValue(kind);
        const label = apLimitLabel(current, limit, isUser ? "usuario" : "empresa", isUser ? "usuarios" : "empresas");
        const title = isUser ? "Límite de usuarios del plan" : "Límite de empresas del plan";
        const icon = isUser ? "fa-user-shield" : "fa-building";
        const $body = $modal.find(".modal-body").first();
        if (!$body.length) return;
        let $banner = $body.children(".ap-public-plan-limit").first();
        if (!$banner.length) {
            $banner = $('<div class="ap-public-plan-limit"><i class="fas"></i><div><strong></strong><span></span></div></div>');
            $body.prepend($banner);
        }
        $banner.find("i").attr("class", "fas " + icon);
        $banner.find("strong").text(title);
        $banner.find("span").text(label);
    }

    function apCanCreateUser() {
        const l = apPlanLimits();
        if (l.puede_crear_usuario === false) return false;
        const max = apFindLimitValue("user");
        return max === null || apCurrentLimitValue("user") < max;
    }

    function apCanCreateCompany() {
        const l = apPlanLimits();
        if (l.puede_crear_empresa === false) return false;
        const max = apFindLimitValue("company");
        return max === null || apCurrentLimitValue("company") < max;
    }


    /* =========================================================
       FORMULARIOS PÚBLICOS: USUARIO + SECUENCIA + DOCUMENTOS
       Se reutilizan los modales globales de vistasModals.
       ========================================================= */
    function apRefreshSelectpicker($el) {
        if (!$el || !$el.length) return;
        if (typeof $.fn.selectpicker === "function") {
            try { $el.selectpicker("refresh"); } catch (e) {}
        }
    }

    function apFillPublicSelect($select, rows, valueKey, textKey, placeholder, disabledFn) {
        if (!$select.length) return;
        let html = '<option value="">' + limpiarHtml(placeholder || "Seleccione") + '</option>';
        (rows || []).forEach(function(row) {
            const value = row[valueKey] !== undefined ? row[valueKey] : (row.id !== undefined ? row.id : "");
            const text = row[textKey] !== undefined ? row[textKey] : (row.nombre || row.razon_social || "Sin nombre");
            const disabled = typeof disabledFn === "function" && disabledFn(row) ? ' disabled' : '';
            html += '<option value="' + limpiarHtml(value) + '"' + disabled + '>' + limpiarHtml(text) + '</option>';
        });
        $select.html(html);
        apRefreshSelectpicker($select);
    }

    function apPrepareExternalModal(selector) {
        const $modal = $(selector);
        if (!$modal.length) {
            apNotify("error", "Formulario no disponible", "No se encontró el modal público " + selector + ". Verifique que vistasModals esté cargado globalmente.");
            return $();
        }
        if ($modal[0].parentNode !== document.body) document.body.appendChild($modal[0]);
        $modal.addClass("ap-from-assignment").removeClass("fade").attr("data-ap-context", "1");
        $modal.modal({ backdrop: false, keyboard: true, show: false });
        const instance = $modal.data("bs.modal");
        if (instance && instance._config) {
            instance._config.backdrop = false;
            instance._config.keyboard = true;
            instance._config.focus = true;
        }
        return $modal;
    }

    function apPublicUserSetMode(mode) {
        const isNew = mode === "new";
        const $modal = $("#modal_registrar_usuarios");
        $modal.find("#es_nuevo_colaborador").val(isNew ? "1" : "0");
        if (isNew) {
            $modal.find("#nuevo-tab").tab("show");
            setTimeout(function(){ $modal.find("#nombre_colaborador").trigger("focus"); }, 80);
        } else {
            $modal.find("#existente-tab").tab("show");
            setTimeout(function(){ $modal.find("#colaboradores_id").trigger("focus"); }, 80);
        }
    }

    function apPopulatePublicUserModal(preselectedCollaborator) {
        const d = apAdminState.data || {};
        const cat = d.catalogos || {};
        const empresas = d.empresas || [];
        const $modal = $("#modal_registrar_usuarios");
        const $form = $modal.find("#formUsers");
        if (!$form.length) return false;
        apInjectPlanLimitIntoPublicModal($modal, "user");

        if ($form[0]) $form[0].reset();
        $form.attr("data-ap-context", "1").removeClass("FormularioAjax");
        $form.find('[name="server_customers_id"]').val(apAdminState.serverCustomerId);
        $form.find('[name="usuarios_id"]').val("");
        $form.find('[name="es_nuevo_colaborador"]').val("0");
        $modal.find("#reg_usuario").show().prop("disabled", false);
        $modal.find("#edi_usuario").hide();
        $modal.find(".modal-title").first().html('<i class="fas fa-user-plus mr-2"></i>Crear usuario / colaborador');
        $modal.find("#info_colaborador").hide();
        $modal.find("#label_usuarios_activo").text("Activo");
        $modal.find("#estado_usuario").prop("checked", true);
        $modal.find("#btnNuevoPuesto").hide();

        apFillPublicSelect($modal.find("#colaboradores_id"), apAdminState.rows, "colaboradores_id", "nombre", "Seleccione un colaborador", function(c){ return (c.usuarios || []).length > 0; });
        apFillPublicSelect($modal.find("#puesto_colaborador"), cat.puestos || [], "id", "nombre", "Seleccione un puesto");
        apFillPublicSelect($modal.find("#empresa_usuario"), empresas, "empresa_id", "nombre", "Seleccione una empresa");
        apFillPublicSelect($modal.find("#privilegio_id"), cat.privilegios || [], "id", "nombre", "Seleccione un privilegio");
        apFillPublicSelect($modal.find("#tipo_user"), cat.tipos_usuario || [], "id", "nombre", "Seleccione permisos");

        $modal.find("#fecha_ingreso_colaborador").val(new Date().toISOString().slice(0, 10));
        if (preselectedCollaborator) {
            $modal.find("#colaboradores_id").val(String(preselectedCollaborator.colaboradores_id));
            apRefreshSelectpicker($modal.find("#colaboradores_id"));
            $modal.find("#empresa_usuario").val(String(preselectedCollaborator.empresa_id || ""));
            apRefreshSelectpicker($modal.find("#empresa_usuario"));
            apPublicUserSetMode("existing");
            apShowPublicCollaboratorInfo(preselectedCollaborator);
        } else {
            apPublicUserSetMode("existing");
        }
        return true;
    }

    function apShowPublicCollaboratorInfo(c) {
        const $modal = $("#modal_registrar_usuarios");
        if (!c) { $modal.find("#info_colaborador").hide(); return; }
        $modal.find("#info_nombre").text(c.nombre || "—");
        $modal.find("#info_identidad").text(c.identidad || "—");
        $modal.find("#info_telefono").text(c.telefono || "—");
        $modal.find("#info_fecha_ingreso").text(c.fecha_ingreso || "—");
        $modal.find("#info_estado").text(parseInt(c.estado, 10) === 1 ? "Activo" : "Inactivo");
        $modal.find("#info_colaborador").show();
    }

    function apOpenUnifiedUserModal(preselectedCollaborator, editUser) {
        const editing = !!editUser;
        if (!editing && !apCanCreateUser()) {
            const l = apPlanLimits();
            apNotify("warning", "Límite de usuarios alcanzado", "El plan actual no permite crear más usuarios. " + apLimitLabel(apCurrentLimitValue("user"), apFindLimitValue("user"), "usuario", "usuarios") + ".");
            return;
        }

        const $modal = apPrepareExternalModal("#modal_registrar_usuarios");
        if (!$modal.length || !apPopulatePublicUserModal(preselectedCollaborator || null)) return;
        const $form = $modal.find("#formUsers");

        if (editing) {
            $form.find('[name="usuarios_id"]').val(editUser.users_id || "");
            $form.find('[name="es_nuevo_colaborador"]').val("0");
            $modal.find("#existente-tab").tab("show");
            $modal.find("#nuevo-tab").addClass("disabled").attr("aria-disabled", "true").css("pointer-events", "none");
            $modal.find('#colaboradores_id option[value="' + String(preselectedCollaborator.colaboradores_id) + '"]').prop("disabled", false);
            $modal.find("#colaboradores_id").val(String(preselectedCollaborator.colaboradores_id)).prop("disabled", true);
            apRefreshSelectpicker($modal.find("#colaboradores_id"));
            apShowPublicCollaboratorInfo(preselectedCollaborator);
            $modal.find("#correo_usuario").val(editUser.email || "");
            $modal.find("#empresa_usuario").val(String(editUser.empresa_id || preselectedCollaborator.empresa_id || ""));
            $modal.find("#privilegio_id").val(String(editUser.privilegio_id || ""));
            $modal.find("#tipo_user").val(String(editUser.tipo_user_id || ""));
            apRefreshSelectpicker($modal.find("#empresa_usuario"));
            apRefreshSelectpicker($modal.find("#privilegio_id"));
            apRefreshSelectpicker($modal.find("#tipo_user"));
            $modal.find("#estado_usuario").prop("checked", parseInt(editUser.estado, 10) === 1);
            $modal.find("#label_usuarios_activo").text(parseInt(editUser.estado, 10) === 1 ? "Activo" : "Inactivo");
            $modal.find("#reg_usuario").hide();
            $modal.find("#edi_usuario").show().prop("disabled", false);
            $modal.find(".modal-title").first().html('<i class="fas fa-user-edit mr-2"></i>Editar usuario');
        } else {
            $modal.find("#nuevo-tab").removeClass("disabled").removeAttr("aria-disabled").css("pointer-events", "");
            $modal.find("#colaboradores_id").prop("disabled", false);
            apRefreshSelectpicker($modal.find("#colaboradores_id"));
        }

        $modal.modal("show");
        setTimeout(function(){
            $modal.find(editing ? "#correo_usuario" : "#colaboradores_id").trigger("focus");
        }, 80);
    }

    function apValidatePublicUserForm() {
        const $modal = $("#modal_registrar_usuarios");
        const editing = parseInt($modal.find("#usuarios_id").val(), 10) > 0;
        const isNew = !editing && ($modal.find("#nuevo-tab").hasClass("active") || $modal.find("#nuevo").hasClass("active"));
        const email = String($modal.find("#correo_usuario").val() || "").trim();
        const empresa = String($modal.find("#empresa_usuario").val() || "");
        const privilegio = String($modal.find("#privilegio_id").val() || "");
        const tipo = String($modal.find("#tipo_user").val() || "");
        if (!email || !empresa || !privilegio || !tipo) {
            apNotify("warning", "Datos incompletos", "Complete correo, empresa, privilegio y tipo de permisos.");
            return null;
        }
        if (isNew) {
            const nombre = String($modal.find("#nombre_colaborador").val() || "").trim();
            const fecha = String($modal.find("#fecha_ingreso_colaborador").val() || "");
            const puesto = String($modal.find("#puesto_colaborador").val() || "");
            if (!nombre || !fecha || !puesto) {
                apNotify("warning", "Datos del colaborador incompletos", "Para un colaborador nuevo complete nombre, fecha de ingreso y puesto.");
                return null;
            }
        } else {
            const cid = parseInt($modal.find("#colaboradores_id").val(), 10) || 0;
            if (!cid) {
                apNotify("warning", "Seleccione un colaborador", "Seleccione el colaborador al que pertenece el usuario.");
                return null;
            }
            const c = apFindCollab(cid);
            if (!c) {
                apNotify("error", "Colaborador no disponible", "No se encontró el colaborador seleccionado. Actualice la administración e inténtelo nuevamente.");
                return null;
            }
            if (!editing && (c.usuarios || []).length) {
                apNotify("warning", "El colaborador ya tiene usuario", "Edite el usuario existente desde el menú Acciones en lugar de crear otro acceso.");
                return null;
            }
        }
        return { isNew: isNew, editing: editing };
    }

    function apSavePublicUserFlow() {
        const valid = apValidatePublicUserForm();
        if (!valid) return;
        const $modal = $("#modal_registrar_usuarios");
        const $save = $modal.find(valid.editing ? "#edi_usuario" : "#reg_usuario");
        $save.prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

        function restoreButton() {
            $save.prop("disabled", false).html(valid.editing
                ? '<i class="fas fa-sync-alt fa-lg"></i> Actualizar Usuario'
                : '<i class="fas fa-save fa-lg"></i> Registrar Usuario');
        }

        function saveUser(cid, collaboratorName) {
            const userData = {
                users_id: valid.editing ? String($modal.find("#usuarios_id").val() || "") : "",
                colaboradores_id: cid,
                colaborador_nombre: collaboratorName,
                email: String($modal.find("#correo_usuario").val() || "").trim(),
                empresa_id: String($modal.find("#empresa_usuario").val() || ""),
                privilegio_id: String($modal.find("#privilegio_id").val() || ""),
                tipo_user_id: String($modal.find("#tipo_user").val() || ""),
                estado: $modal.find("#estado_usuario").is(":checked") ? "1" : "2"
            };
            apPost("save_user", userData).done(function(r){
                if (!r || !r.success) {
                    apNotify("error", "No se pudo guardar el usuario", r && r.message ? r.message : "No se pudo completar el registro del usuario.");
                    return;
                }
                $modal.modal("hide");
                const title = valid.editing ? "Usuario actualizado" : (valid.isNew ? "Colaborador y usuario creados" : "Usuario creado");
                apNotify("success", title, r.message || "El acceso fue guardado correctamente.");
                apLoadAdmin();
            }).fail(function(xhr){ apAjaxError(xhr, "No se pudo guardar el usuario"); })
              .always(restoreButton);
        }

        if (!valid.isNew) {
            const cid = parseInt($modal.find("#colaboradores_id").val(), 10) || 0;
            const c = apFindCollab(cid);
            saveUser(cid, c ? c.nombre : "");
            return;
        }

        const collaboratorData = {
            colaboradores_id: "",
            nombre: String($modal.find("#nombre_colaborador").val() || "").trim(),
            identidad: String($modal.find("#identidad_colaborador").val() || "").trim(),
            telefono: String($modal.find("#telefono_colaborador").val() || "").trim(),
            fecha_ingreso: String($modal.find("#fecha_ingreso_colaborador").val() || ""),
            puestos_id: String($modal.find("#puesto_colaborador").val() || ""),
            empresa_id: String($modal.find("#empresa_usuario").val() || ""),
            estado: "1"
        };
        apPost("save_collaborator", collaboratorData).done(function(r){
            if (!r || !r.success || !r.data || !r.data.colaboradores_id) {
                apNotify("error", "No se pudo crear el colaborador", r && r.message ? r.message : "No se recibió el identificador del nuevo colaborador.");
                restoreButton();
                return;
            }
            saveUser(parseInt(r.data.colaboradores_id, 10), collaboratorData.nombre);
        }).fail(function(xhr){
            apAjaxError(xhr, "No se pudo crear el colaborador");
            restoreButton();
        });
    }

    const AP_ASSIGNMENT_API_URL = "<?php echo SERVERURL; ?>ajax/js/asignacionPlanes.php";

    const apBillingState = {
        documentos: [],
        secuencias: [],
        empresas: [],
        loading: false,
        sequenceStatus: "1",
        documentStatus: "1",
        sequenceView: window.innerWidth < 768 ? "mini" : "detail"
    };

    function apBillingRequest(action, data) {
        const payload = $.extend({}, data || {}, {
            ap_api_action: action,
            server_customers_id: apAdminState.serverCustomerId
        });
        return $.ajax({
            type: "POST",
            url: AP_ASSIGNMENT_API_URL,
            dataType: "json",
            data: payload
        });
    }

    function apDocumentStatus(doc) {
        return parseInt(doc && doc.estado, 10) === 1;
    }

    function apSequenceStatus(row) {
        return parseInt(row && row.estado, 10) === 1;
    }

    function apPopulateSequenceCompanies() {
        const empresas = apBillingState.empresas.length
            ? apBillingState.empresas
            : ((apAdminState.data && apAdminState.data.empresas) || []);
        const $select = $("#modal_registrar_secuencias #empresa_secuencia");
        if (!$select.length) return;
        const current = String($select.val() || "");
        apFillPublicSelect($select, empresas, "empresa_id", "nombre", "Seleccione una empresa");
        if (current && $select.find('option[value="' + current + '"]').length) {
            $select.val(current);
        } else if (empresas.length === 1) {
            $select.val(String(empresas[0].empresa_id));
        }
        apRefreshSelectpicker($select);
    }

    function apPopulateSequenceDocumentsSelect() {
        const $select = $("#modal_registrar_secuencias #documento_secuencia");
        if (!$select.length) return;
        const current = String($select.val() || "");
        let html = '<option value="">Seleccione</option>';
        apBillingState.documentos.forEach(function(doc){
            if (!apDocumentStatus(doc)) return;
            html += '<option value="' + limpiarHtml(doc.documento_id) + '">' + limpiarHtml(doc.nombre || "Documento") + '</option>';
        });
        $select.html(html);
        if (current && $select.find('option[value="' + current + '"]').length) $select.val(current);
        apRefreshSelectpicker($select);
    }

    function apFormatSequenceNumber(row) {
        const prefijo = String(row.prefijo || "");
        const relleno = Math.max(0, parseInt(row.relleno || 0, 10) || 0);
        const siguiente = String(row.siguiente === null || row.siguiente === undefined ? "" : row.siguiente);
        return prefijo + (relleno > 0 ? siguiente.padStart(relleno, "0") : siguiente);
    }

    function apFilteredSequences() {
        const status = String(apBillingState.sequenceStatus || "1");
        return (apBillingState.secuencias || []).filter(function(row){
            if (status === "all") return true;
            return String(apSequenceStatus(row) ? 1 : 0) === status;
        });
    }

    function apSequenceActionsHtml(id) {
        return '<div class="dropdown ap-sequence-row-actions">' +
            '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Acciones</button>' +
            '<div class="dropdown-menu dropdown-menu-right">' +
                '<button type="button" class="dropdown-item ap-sequence-edit" data-id="' + id + '"><i class="fas fa-edit mr-2"></i>Editar</button>' +
                '<div class="dropdown-divider"></div>' +
                '<button type="button" class="dropdown-item text-danger ap-sequence-delete" data-id="' + id + '"><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>' +
            '</div>' +
        '</div>';
    }

    function apRenderSequenceList() {
        const rows = apFilteredSequences();
        const status = String(apBillingState.sequenceStatus || "1");
        const view = apBillingState.sequenceView === "mini" ? "mini" : "detail";
        const $list = $("#ap_sequence_list");
        const $empty = $("#ap_sequence_empty");
        $("#ap_total_secuencias").text(rows.length);
        $("#ap_total_documentos").text((apBillingState.documentos || []).length);
        $("#ap_total_empresas_facturacion").text((apBillingState.empresas || []).length);
        $("[data-sequence-view]").removeClass("active").filter('[data-sequence-view="' + view + '"]').addClass("active");

        if (!$list.length) return;
        $list.removeClass("is-detail is-mini").addClass(view === "mini" ? "is-mini" : "is-detail").empty();
        $empty.toggleClass("d-none", rows.length > 0 || apBillingState.loading);
        if (!rows.length) {
            $empty.find("strong").text(status === "1" ? "No hay secuencias activas" : (status === "0" ? "No hay secuencias inactivas" : "No hay secuencias registradas"));
            return;
        }

        let html = view === "detail" ? '<div class="ap-sequence-detail-head"><div>Documento / Empresa</div><div>CAI</div><div>Siguiente</div><div>Rango</div><div>Vigencia</div><div>Estado</div><div>Acciones</div></div>' : '';
        rows.forEach(function(row){
            const id = parseInt(row.secuencia_facturacion_id, 10) || 0;
            const active = apSequenceStatus(row);
            const nextNumber = limpiarHtml(apFormatSequenceNumber(row));
            const statusHtml = '<span class="ap-sequence-status ' + (active ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (active ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (active ? 'Activa' : 'Inactiva') + '</span>';
            if (view === "mini") {
                html += '<article class="ap-sequence-mini-card" data-id="' + id + '">' +
                    '<div class="ap-sequence-mini-top"><div class="ap-sequence-card-icon"><i class="fas fa-file-invoice"></i></div><div class="ap-sequence-mini-title"><strong>' + limpiarHtml(row.documento || "Documento") + '</strong><span>' + limpiarHtml(row.empresa || "Empresa") + '</span></div>' + statusHtml + '</div>' +
                    '<div class="ap-sequence-mini-grid">' +
                        '<div><small>CAI</small><span>' + limpiarHtml(row.cai || "Sin CAI") + '</span></div>' +
                        '<div><small>Siguiente</small><span>' + (nextNumber || '—') + '</span></div>' +
                        '<div><small>Rango</small><span>' + limpiarHtml((row.rango_inicial || "—") + ' - ' + (row.rango_final || "—")) + '</span></div>' +
                        '<div><small>Vigencia</small><span>' + limpiarHtml((row.fecha_activacion_display || row.fecha_activacion || "—") + ' → ' + (row.fecha_limite_display || row.fecha_limite || "—")) + '</span></div>' +
                    '</div><div class="ap-sequence-mini-footer">' + apSequenceActionsHtml(id) + '</div></article>';
            } else {
                html += '<article class="ap-sequence-detail-row" data-id="' + id + '">' +
                    '<div class="ap-sequence-detail-main"><strong>' + limpiarHtml(row.documento || "Documento") + '</strong><span>' + limpiarHtml(row.empresa || "Empresa") + '</span></div>' +
                    '<div data-label="CAI">' + limpiarHtml(row.cai || "Sin CAI") + '</div>' +
                    '<div data-label="Siguiente">' + (nextNumber || '—') + '</div>' +
                    '<div data-label="Rango">' + limpiarHtml((row.rango_inicial || "—") + ' - ' + (row.rango_final || "—")) + '</div>' +
                    '<div data-label="Vigencia">' + limpiarHtml((row.fecha_activacion_display || row.fecha_activacion || "—") + ' → ' + (row.fecha_limite_display || row.fecha_limite || "—")) + '</div>' +
                    '<div data-label="Estado">' + statusHtml + '</div><div data-label="Acciones">' + apSequenceActionsHtml(id) + '</div></article>';
            }
        });
        $list.html(html);
    }

    function apEnsureDocumentFilterControls() {
        const $modal = $("#modal_documentos_secuencia");
        const $header = $modal.find(".documentos-list-panel .documentos-header-flex").first();
        if (!$header.length) return;
        let $tools = $header.find(".ap-document-header-tools");
        if (!$tools.length) {
            $tools = $('<div class="ap-document-header-tools"></div>');
            const $refresh = $header.find("#btn_refrescar_documentos_secuencia");
            if ($refresh.length) {
                $refresh.before($tools);
                $tools.append($refresh);
            } else {
                $header.append($tools);
            }
            $tools.prepend(
                '<div class="ap-status-filter ap-document-status-filter" id="ap_document_status_filter" aria-label="Filtrar documentos por estado">' +
                    '<button type="button" data-document-status="1"><i class="fas fa-check-circle mr-1"></i>Activos</button>' +
                    '<button type="button" data-document-status="0"><i class="fas fa-pause-circle mr-1"></i>Inactivos</button>' +
                    '<button type="button" data-document-status="all"><i class="fas fa-list mr-1"></i>Todos</button>' +
                '</div>'
            );
        }
        const status = String(apBillingState.documentStatus || "1");
        $tools.find("[data-document-status]").removeClass("active").filter('[data-document-status="' + status + '"]').addClass("active");
    }

    function apRenderDocumentCatalog() {
        const $modal = $("#modal_documentos_secuencia");
        apEnsureDocumentFilterControls();
        const $list = $modal.find("#documentos_secuencia_listado");
        const $empty = $modal.find("#documentos_secuencia_empty");
        if (!$list.length) return;
        const status = String(apBillingState.documentStatus || "1");
        const rows = (apBillingState.documentos || []).filter(function(doc){
            if (status === "all") return true;
            return String(apDocumentStatus(doc) ? 1 : 0) === status;
        });
        $list.empty();
        $empty.toggleClass("d-none", rows.length > 0 || apBillingState.loading);
        if (!rows.length) {
            $empty.find("strong").text(status === "1" ? "No hay documentos activos" : (status === "0" ? "No hay documentos inactivos" : "No hay documentos registrados"));
            $empty.find("small").text("Cambie el filtro de estado o registre un nuevo documento.");
            return;
        }

        let html = "";
        rows.forEach(function(doc){
            const id = parseInt(doc.documento_id, 10) || 0;
            const name = limpiarHtml(doc.nombre || "Documento");
            const active = apDocumentStatus(doc);
            const activeCount = parseInt(doc.secuencias_activas || 0, 10) || 0;
            const totalCount = parseInt(doc.secuencias_total || 0, 10) || 0;
            const canDelete = totalCount === 0;

            html += '<article class="ap-document-card" data-id="' + id + '">' +
                '<div class="ap-document-icon"><i class="fas fa-file-alt"></i></div>' +
                '<div class="ap-document-info">' +
                    '<div class="ap-document-title-row"><h6>' + name + '</h6>' +
                        '<span class="ap-document-status ' + (active ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (active ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (active ? 'Activo' : 'Inactivo') + '</span>' +
                    '</div>' +
                    '<div class="ap-document-meta">' +
                        '<span><i class="fas fa-hashtag"></i>ID: ' + id + '</span>' +
                        '<span><i class="fas fa-layer-group"></i>' + totalCount + ' secuencia(s)</span>' +
                        '<span><i class="fas fa-check-circle"></i>' + activeCount + ' activa(s)</span>' +
                    '</div>' +
                '</div>' +
                '<div class="dropdown ap-document-actions">' +
                    '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Acciones</button>' +
                    '<div class="dropdown-menu dropdown-menu-right">' +
                        '<button type="button" class="dropdown-item ap-document-edit" data-id="' + id + '"><i class="fas fa-edit mr-2"></i>Editar</button>' +
                        '<button type="button" class="dropdown-item ap-document-state" data-id="' + id + '" data-state="' + (active ? 0 : 1) + '"><i class="fas ' + (active ? 'fa-pause' : 'fa-play') + ' mr-2"></i>' + (active ? 'Desactivar' : 'Activar') + '</button>' +
                        '<div class="dropdown-divider"></div>' +
                        (canDelete
                            ? '<button type="button" class="dropdown-item text-danger ap-document-delete" data-id="' + id + '"><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>'
                            : '<button type="button" class="dropdown-item text-muted" disabled title="Tiene secuencias asociadas"><i class="fas fa-lock mr-2"></i>Eliminar</button>') +
                    '</div>' +
                '</div>' +
            '</article>';
        });
        $list.html(html);
    }

    function apLoadBillingData(renderCatalog) {
        if (!apAdminState.serverCustomerId) return $.Deferred().resolve().promise();
        if (apBillingState.loading) return $.Deferred().resolve().promise();
        apBillingState.loading = true;
        $("#ap_billing_loading").removeClass("d-none");
        const $docLoading = $("#modal_documentos_secuencia #documentos_secuencia_loading");
        if (renderCatalog) $docLoading.removeClass("d-none");

        return apBillingRequest("billing_catalogs").done(function(response){
            if (!response || response.success !== true) {
                apNotify("error", "No se pudo consultar la facturación", response && response.message ? response.message : "No se pudo consultar la base del cliente.");
                return;
            }
            const data = response.data || {};
            apBillingState.empresas = Array.isArray(data.empresas) ? data.empresas : [];
            apBillingState.documentos = Array.isArray(data.documentos) ? data.documentos : [];
            apBillingState.secuencias = Array.isArray(data.secuencias) ? data.secuencias : [];
            apPopulateSequenceCompanies();
            apPopulateSequenceDocumentsSelect();
            apRenderDocumentCatalog();
            apRenderSequenceList();
            apRenderCompanies();
        }).fail(function(xhr){
            apAjaxError(xhr, "No se pudieron consultar las secuencias y documentos del cliente");
        }).always(function(){
            apBillingState.loading = false;
            $("#ap_billing_loading").addClass("d-none");
            $docLoading.addClass("d-none");
            apRenderSequenceList();
        });
    }

    function apResetDocumentForm() {
        const $modal = $("#modal_documentos_secuencia");
        const form = $modal.find("#formDocumentoSecuencia")[0];
        if (form) form.reset();
        $modal.find("#documento_id_secuencia").val("0");
        $modal.find("#documento_form_titulo").text("Nuevo Documento");
        $modal.find("#btn_guardar_documento_secuencia").html('<i class="fas fa-save mr-1"></i> Guardar documento').prop("disabled", false);
        $modal.find("#btn_cancelar_edicion_documento").addClass("d-none");
    }

    function apEditDocument(id) {
        const doc = apBillingState.documentos.find(function(row){ return parseInt(row.documento_id,10) === parseInt(id,10); });
        if (!doc) return apNotify("error", "Documento no disponible", "No se encontró el documento seleccionado.");
        const $modal = $("#modal_documentos_secuencia");
        $modal.find("#documento_id_secuencia").val(doc.documento_id);
        $modal.find("#documento_nombre_secuencia").val(doc.nombre || "").trigger("focus").select();
        $modal.find("#documento_form_titulo").text("Editar Documento");
        $modal.find("#btn_guardar_documento_secuencia").html('<i class="fas fa-save mr-1"></i> Guardar cambios');
        $modal.find("#btn_cancelar_edicion_documento").removeClass("d-none");
    }

    function apSaveDocument() {
        const $modal = $("#modal_documentos_secuencia");
        const id = parseInt($modal.find("#documento_id_secuencia").val(),10) || 0;
        const nombre = String($modal.find("#documento_nombre_secuencia").val() || "").trim();
        if (!nombre) {
            apNotify("warning", "Dato requerido", "Ingrese el nombre del documento.");
            $modal.find("#documento_nombre_secuencia").trigger("focus");
            return;
        }
        const $btn = $modal.find("#btn_guardar_documento_secuencia");
        $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');
        apBillingRequest("save_document", {documento_id:id, nombre:nombre})
            .done(function(r){
                if (!r || !r.success) return apNotify("error", "No se pudo guardar", r && r.message ? r.message : "No se pudo guardar el documento.");
                apNotify("success", "Documento guardado", r.message || "Documento guardado correctamente.");
                apResetDocumentForm();
                apLoadBillingData(true);
            })
            .fail(function(xhr){ apAjaxError(xhr, "No se pudo guardar el documento"); })
            .always(function(){ $btn.prop("disabled", false).html(id ? '<i class="fas fa-save mr-1"></i> Guardar cambios' : '<i class="fas fa-save mr-1"></i> Guardar documento'); });
    }

    function apChangeDocumentState(id, state) {
        const activating = parseInt(state,10) === 1;
        apConfirm(activating ? "Activar documento" : "Desactivar documento",
            activating ? "El documento quedará disponible para crear nuevas secuencias." : "El documento dejará de aparecer en nuevas secuencias, pero conservará su historial.",
            function(ok){
                if (!ok) return;
                apBillingRequest("toggle_document", {documento_id:id, estado:activating ? 1 : 0})
                    .done(function(r){
                        if (!r || !r.success) return apNotify("error", "No se pudo actualizar", r && r.message ? r.message : "No se pudo actualizar el documento.");
                        apNotify("success", "Documento actualizado", r.message || "Estado actualizado correctamente.");
                        apLoadBillingData(true);
                    }).fail(function(xhr){ apAjaxError(xhr, "No se pudo actualizar el documento"); });
            });
    }

    function apDeleteDocument(id) {
        const doc = apBillingState.documentos.find(function(row){ return parseInt(row.documento_id,10) === parseInt(id,10); });
        if (!doc) return;
        const total = parseInt(doc.secuencias_total || 0,10) || 0;
        if (total > 0) return apNotify("warning", "Documento en uso", "No se puede eliminar porque tiene " + total + " secuencia(s) asociada(s). Puede desactivarlo.");
        apConfirm("Eliminar documento", "Se eliminará permanentemente “" + String(doc.nombre || "Documento") + "”.", function(ok){
            if (!ok) return;
            apBillingRequest("delete_document", {documento_id:id})
                .done(function(r){
                    if (!r || !r.success) return apNotify("error", "No se pudo eliminar", r && r.message ? r.message : "No se pudo eliminar el documento.");
                    apNotify("success", "Documento eliminado", r.message || "Documento eliminado correctamente.");
                    apResetDocumentForm();
                    apLoadBillingData(true);
                }).fail(function(xhr){ apAjaxError(xhr, "No se pudo eliminar el documento"); });
        });
    }

    function apPrepareSequenceForm(row) {
        const $modal = $("#modal_registrar_secuencias");
        const $form = $modal.find("#formSecuencia");
        if ($form[0]) $form[0].reset();
        $form.attr("data-ap-context", "1").removeClass("FormularioAjax");
        $modal.find("#empresa_secuencia,#documento_secuencia").prop("disabled", false);
        $modal.find("#cai_secuencia,#prefijo_secuencia,#relleno_secuencia,#incremento_secuencia,#siguiente_secuencia,#rango_inicial_secuencia,#rango_final_secuencia,#fecha_activacion_secuencia,#fecha_limite_secuencia").prop("readonly", false);
        apPopulateSequenceCompanies();
        apPopulateSequenceDocumentsSelect();

        if (!row) {
            $modal.find("#secuencia_facturacion_id").val("");
            $modal.find("#reg_secuencia").show().prop("disabled",false);
            $modal.find("#edi_secuencia").hide();
            $modal.find("#estado_secuencia").prop("checked", true);
            $modal.find("#label_estado_secuencia").text("Activo");
            return;
        }

        $modal.find("#secuencia_facturacion_id").val(row.secuencia_facturacion_id);
        $modal.find("#empresa_secuencia").val(String(row.empresa_id || "")).prop("disabled", true);
        $modal.find("#documento_secuencia").val(String(row.documento_id || "")).prop("disabled", true);
        apRefreshSelectpicker($modal.find("#empresa_secuencia"));
        apRefreshSelectpicker($modal.find("#documento_secuencia"));
        $modal.find("#cai_secuencia").val(row.cai || "");
        $modal.find("#prefijo_secuencia").val(row.prefijo || "");
        $modal.find("#relleno_secuencia").val(row.relleno || "");
        $modal.find("#incremento_secuencia").val(row.incremento || "");
        $modal.find("#siguiente_secuencia").val(row.siguiente || 0);
        $modal.find("#rango_inicial_secuencia").val(row.rango_inicial || "");
        $modal.find("#rango_final_secuencia").val(row.rango_final || "");
        $modal.find("#fecha_activacion_secuencia").val(row.fecha_activacion_raw || row.fecha_activacion || "");
        $modal.find("#fecha_limite_secuencia").val(row.fecha_limite_raw || row.fecha_limite || "");
        $modal.find("#estado_secuencia").prop("checked", apSequenceStatus(row));
        $modal.find("#label_estado_secuencia").text(apSequenceStatus(row) ? "Activo" : "Inactivo");
        $modal.find("#cai_secuencia,#prefijo_secuencia,#relleno_secuencia,#incremento_secuencia,#rango_inicial_secuencia,#rango_final_secuencia,#fecha_activacion_secuencia,#fecha_limite_secuencia").prop("readonly", true);
        $modal.find("#reg_secuencia").hide();
        $modal.find("#edi_secuencia").show().prop("disabled",false);
    }

    function apOpenSequenceModal(row) {
        const $modal = apPrepareExternalModal("#modal_registrar_secuencias");
        if (!$modal.length) return;
        const open = function(){ apPrepareSequenceForm(row || null); $modal.modal("show"); setTimeout(function(){ $modal.find(row ? "#siguiente_secuencia" : "#empresa_secuencia").trigger("focus"); },80); };
        if (!apBillingState.empresas.length && !apBillingState.documentos.length) apLoadBillingData(false).always(open); else open();
    }

    function apSaveSequence() {
        const $modal = $("#modal_registrar_secuencias");
        const id = parseInt($modal.find("#secuencia_facturacion_id").val(),10) || 0;
        const data = {
            secuencia_facturacion_id: id,
            empresa_secuencia: String($modal.find("#empresa_secuencia").val() || ""),
            documento_secuencia: String($modal.find("#documento_secuencia").val() || ""),
            cai_secuencia: String($modal.find("#cai_secuencia").val() || ""),
            prefijo_secuencia: String($modal.find("#prefijo_secuencia").val() || ""),
            relleno_secuencia: String($modal.find("#relleno_secuencia").val() || ""),
            incremento_secuencia: String($modal.find("#incremento_secuencia").val() || ""),
            siguiente_secuencia: String($modal.find("#siguiente_secuencia").val() || "0"),
            rango_inicial_secuencia: String($modal.find("#rango_inicial_secuencia").val() || ""),
            rango_final_secuencia: String($modal.find("#rango_final_secuencia").val() || ""),
            fecha_activacion_secuencia: String($modal.find("#fecha_activacion_secuencia").val() || ""),
            fecha_limite_secuencia: String($modal.find("#fecha_limite_secuencia").val() || ""),
            estado_secuencia: $modal.find("#estado_secuencia").is(":checked") ? "1" : "0"
        };
        const $btn = $modal.find(id ? "#edi_secuencia" : "#reg_secuencia");
        $btn.prop("disabled",true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');
        apBillingRequest("save_sequence", data)
            .done(function(r){
                if (!r || !r.success) return apNotify("error", "No se pudo guardar", r && r.message ? r.message : "No se pudo guardar la secuencia.");
                $modal.modal("hide");
                apNotify("success", "Secuencia guardada", r.message || "Secuencia guardada correctamente.");
                apLoadBillingData(false);
            }).fail(function(xhr){ apAjaxError(xhr,"No se pudo guardar la secuencia"); })
            .always(function(){ $btn.prop("disabled",false).html(id ? '<i class="fas fa-edit mr-1"></i> Confirmar' : '<i class="far fa-save mr-1"></i> Registrar'); });
    }

    function apDeleteSequence(id) {
        const row = apBillingState.secuencias.find(function(x){ return parseInt(x.secuencia_facturacion_id,10) === parseInt(id,10); });
        if (!row) return;
        apConfirm("Eliminar secuencia", "Se eliminará la secuencia de “" + String(row.documento || "Documento") + "” para “" + String(row.empresa || "Empresa") + "”.", function(ok){
            if (!ok) return;
            apBillingRequest("delete_sequence", {secuencia_facturacion_id:id})
                .done(function(r){
                    if (!r || !r.success) return apNotify("error", "No se pudo eliminar", r && r.message ? r.message : "No se pudo eliminar la secuencia.");
                    apNotify("success", "Secuencia eliminada", r.message || "Secuencia eliminada correctamente.");
                    apLoadBillingData(false);
                }).fail(function(xhr){ apAjaxError(xhr,"No se pudo eliminar la secuencia"); });
        });
    }

    function apOpenDocumentsModal() {
        const $modal = apPrepareExternalModal("#modal_documentos_secuencia");
        if (!$modal.length) return;
        $modal.attr("data-ap-server-customer-id", apAdminState.serverCustomerId);
        $modal.find("#formDocumentoSecuencia").attr("data-ap-context", "1");
        apBillingState.documentStatus = "1";
        apEnsureDocumentFilterControls();
        apResetDocumentForm();
        $modal.modal("show");
        apLoadBillingData(true);
        setTimeout(function(){ $modal.find("#documento_nombre_secuencia").trigger("focus"); },80);
    }

    function apUpdatePlanLimitUI() {
        const l = apPlanLimits();
        const usersActual = apCurrentLimitValue("user");
        const usersLimit = apFindLimitValue("user");
        const companiesActual = apCurrentLimitValue("company");
        const companiesLimit = apFindLimitValue("company");

        $("#ap_limite_usuarios_resumen").text(apLimitLabel(usersActual, usersLimit, "usuario", "usuarios"));
        $("#ap_limite_empresas_resumen").text(apLimitLabel(companiesActual, companiesLimit, "empresa", "empresas"));
        $("#ap_company_limit_note").text("Límite según plan: " + apLimitLabel(companiesActual, companiesLimit, "empresa", "empresas"));

        const $company = $("#ap_btn_nueva_empresa");
        const companyBlocked = !apCanCreateCompany();
        $company.prop("disabled", companyBlocked).toggleClass("disabled", companyBlocked);
        if (companyBlocked) {
            $company.attr("title", "Límite de empresas alcanzado según el plan actual");
        } else {
            $company.removeAttr("title");
        }

        const $user = $("#ap_btn_nuevo_usuario");
        const userBlocked = !apCanCreateUser();
        $user.prop("disabled", userBlocked).toggleClass("disabled", userBlocked);
        if (userBlocked) {
            $user.attr("title", "Límite de usuarios alcanzado según el plan actual");
        } else {
            $user.removeAttr("title");
        }
    }

    function apSetStatus(prefix, active, activeText, inactiveText, activeValue, inactiveValue) {
        const $hidden = $("#" + prefix);
        const $toggle = $("#" + prefix + "_toggle");
        const on = !!active;
        $hidden.val(on ? activeValue : inactiveValue);
        $toggle.prop("checked", on);
        $toggle.closest(".ap-status-switch").find(".ap-status-label").text(on ? activeText : inactiveText);
    }

    function apApplyFilter() {
        const q = normalizarTexto($("#ap_search").val());
        const status = String(apAdminState.collaboratorStatus || "1");
        apAdminState.filtered = apAdminState.rows.filter(function(c) {
            const active = parseInt(c.estado, 10) === 1;
            if (status !== "all" && String(active ? 1 : 0) !== status) return false;
            const users = (c.usuarios || []).map(function(u){ return (u.email||"") + " " + (u.empresa_nombre||""); }).join(" ");
            return !q || normalizarTexto([c.nombre,c.identidad,c.telefono,users].join(" ")).indexOf(q) !== -1;
        });
        const max = Math.max(1, Math.ceil(apAdminState.filtered.length / apAdminState.pageSize));
        if (apAdminState.page > max) apAdminState.page = max;
        apRenderCollaborators();
    }

    function apPrimaryUser(c) { return (c.usuarios && c.usuarios.length) ? c.usuarios[0] : null; }

    function apUserAccessLabel(u, kind) {
        if (!u) return "Sin asignar";
        const cat=((apAdminState.data||{}).catalogos||{});
        if (kind === "privilege") {
            const direct=u.privilegio_nombre||u.privilegio||u.nombre_privilegio;
            if (direct) return String(direct);
            const id=parseInt(u.privilegio_id,10)||0;
            const row=(cat.privilegios||[]).find(function(x){return parseInt(x.id||x.privilegio_id,10)===id;});
            return row ? String(row.nombre||"Sin asignar") : "Sin asignar";
        }
        const direct=u.tipo_usuario||u.tipo_user_nombre||u.nombre_tipo_usuario||u.tipo_user;
        if (direct) return String(direct);
        const id=parseInt(u.tipo_user_id,10)||0;
        const row=(cat.tipos_usuario||[]).find(function(x){return parseInt(x.id||x.tipo_user_id,10)===id;});
        return row ? String(row.nombre||"Sin asignar") : "Sin asignar";
    }

    function apRenderCollaboratorActionsButton(c, u, compact) {
        const cls = compact ? "ap-collab-actions-trigger is-mini" : "ap-collab-actions-trigger";
        return '<button type="button" class="btn btn-primary btn-sm ' + cls + '" ' +
            'data-cid="' + c.colaboradores_id + '" data-uid="' + (u ? u.users_id : "") + '" ' +
            'aria-expanded="false">' +
            '<i class="fas fa-cog mr-1"></i><span>Acciones</span><i class="fas fa-chevron-down ml-2"></i>' +
        '</button>';
    }

    function apRenderDetail(c) {
        const u = apPrimaryUser(c);
        const collabActive = parseInt(c.estado,10) === 1;
        return '<article class="ap-collab-detail">' +
            '<div class="ap-collab-main"><div class="ap-avatar"><i class="fas fa-user"></i></div><div><div class="ap-collab-name-row"><strong>' + limpiarHtml(c.nombre) + '</strong><span class="ap-company-status ' + (collabActive ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (collabActive ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (collabActive ? 'Activo' : 'Inactivo') + '</span></div><span class="ap-sub"><i class="fas fa-id-card mr-1"></i>' + limpiarHtml(c.identidad || "Sin identidad") + '</span></div></div>' +
            '<div><span class="ap-sub">Teléfono</span><strong>' + limpiarHtml(c.telefono || "—") + '</strong></div>' +
            '<div class="ap-hide-tablet"><span class="ap-sub">Empresa</span><strong>' + limpiarHtml(u ? (u.empresa_nombre || "Sin empresa") : "Sin usuario") + '</strong></div>' +
            '<div><span class="ap-user-status ' + (u ? 'yes' : 'no') + '"><i class="fas ' + (u ? 'fa-check-circle' : 'fa-user-slash') + '"></i>' + (u ? 'Tiene usuario' : 'Sin usuario') + '</span><span class="ap-sub">' + limpiarHtml(u ? u.email : "Puede crear acceso") + '</span>' + (u ? '<span class="ap-user-access-summary"><i class="fas fa-user-shield"></i>'+limpiarHtml(apUserAccessLabel(u,"privilege"))+' <b>•</b> <i class="fas fa-key"></i>'+limpiarHtml(apUserAccessLabel(u,"type"))+'</span>' : '') + '</div>' +
            '<div class="ap-collab-actions">' + apRenderCollaboratorActionsButton(c, u, false) + '</div></article>';
    }

    function apRenderMini(c) {
        const u = apPrimaryUser(c);
        const collabActive = parseInt(c.estado,10) === 1;
        return '<article class="ap-collab-mini">' +
            '<div class="ap-collab-mini-top"><div><div class="ap-collab-name-row"><h6>' + limpiarHtml(c.nombre) + '</h6><span class="ap-company-status ' + (collabActive ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (collabActive ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (collabActive ? 'Activo' : 'Inactivo') + '</span></div><span class="ap-sub"><i class="fas fa-id-card mr-1"></i>' + limpiarHtml(c.identidad || "Sin identidad") + '</span></div><span class="ap-user-status ' + (u ? 'yes' : 'no') + '"><i class="fas ' + (u ? 'fa-check-circle' : 'fa-user-slash') + ' mr-1"></i>' + (u ? 'Tiene usuario' : 'Sin usuario') + '</span></div>' +
            '<div class="ap-mini-data"><div><small>Teléfono</small><strong>' + limpiarHtml(c.telefono || "—") + '</strong></div><div><small>Ingreso</small><strong>' + limpiarHtml(c.fecha_ingreso || "—") + '</strong></div></div>' +
            '<div class="ap-user-lines">' + (u ? '<div class="ap-user-line"><span><i class="fas fa-envelope mr-1"></i>' + limpiarHtml(u.email) + '</span><span><i class="fas fa-building mr-1"></i>' + limpiarHtml(u.empresa_nombre || "Sin empresa") + '</span></div><div class="ap-user-line ap-user-access-line"><span><i class="fas fa-user-shield mr-1"></i>'+limpiarHtml(apUserAccessLabel(u,"privilege"))+'</span><span><i class="fas fa-key mr-1"></i>'+limpiarHtml(apUserAccessLabel(u,"type"))+'</span></div>' : '<span class="ap-sub">Este colaborador todavía no tiene credenciales de acceso.</span>') + '</div>' +
            '<div class="ap-mini-actions">' + apRenderCollaboratorActionsButton(c, u, true) + '</div>' +
        '</article>';
    }

    function apCloseCollaboratorActionsPortal() {
        $("#ap_collab_actions_portal").removeClass("is-open").removeData("owner");
        $(".ap-collab-actions-trigger").attr("aria-expanded", "false");
    }

    function apOpenCollaboratorActionsPortal($trigger) {
        let $portal = $("#ap_collab_actions_portal");
        if (!$portal.length) {
            $portal = $('<div id="ap_collab_actions_portal" class="ap-actions-portal ap-collab-actions-portal" role="menu"></div>').appendTo(document.body);
        }

        const cid = parseInt($trigger.data("cid"), 10) || 0;
        const uid = parseInt($trigger.data("uid"), 10) || 0;
        const c = apFindCollab(cid);
        const u = c && uid ? apFindUser(c, uid) : null;
        if (!c) {
            apNotify("error", "Colaborador no disponible", "No se encontró el colaborador seleccionado. Actualice el listado e inténtelo nuevamente.");
            return;
        }

        let html =
            '<button type="button" class="ap-action-item ap-edit-collab" data-id="' + cid + '" role="menuitem">' +
                '<span class="ap-item-icon ap-item-icon-users"><i class="fas fa-user-edit"></i></span>' +
                '<span class="ap-item-copy"><strong>Editar colaborador</strong><small>Datos personales, puesto y empresa</small></span>' +
            '</button>';

        if (u) {
            html +=
                '<button type="button" class="ap-action-item ap-edit-user" data-cid="' + cid + '" data-uid="' + u.users_id + '" role="menuitem">' +
                    '<span class="ap-item-icon ap-item-icon-edit"><i class="fas fa-user-shield"></i></span>' +
                    '<span class="ap-item-copy"><strong>Editar usuario</strong><small>Correo, empresa y acceso</small></span>' +
                '</button>' +
                '<button type="button" class="ap-action-item ap-edit-user" data-cid="' + cid + '" data-uid="' + u.users_id + '" role="menuitem">' +
                    '<span class="ap-item-icon ap-item-icon-key"><i class="fas fa-shield-alt"></i></span>' +
                    '<span class="ap-item-copy"><strong>Privilegio y permisos</strong><small>'+limpiarHtml(apUserAccessLabel(u,"privilege"))+' · '+limpiarHtml(apUserAccessLabel(u,"type"))+'</small></span>' +
                '</button>' +
                '<button type="button" class="ap-action-item ap-reset-user" data-uid="' + u.users_id + '" data-email="' + limpiarHtml(u.email) + '" role="menuitem">' +
                    '<span class="ap-item-icon ap-item-icon-key"><i class="fas fa-key"></i></span>' +
                    '<span class="ap-item-copy"><strong>Nueva contraseña</strong><small>Generar y enviar al correo</small></span>' +
                '</button>';
        } else {
            const canCreateUser = apCanCreateUser();
            html +=
                '<button type="button" class="ap-action-item ap-new-user' + (canCreateUser ? '' : ' is-disabled') + '" data-cid="' + cid + '" role="menuitem" ' + (canCreateUser ? '' : 'disabled aria-disabled="true"') + '>' +
                    '<span class="ap-item-icon ap-item-icon-add"><i class="fas fa-user-plus"></i></span>' +
                    '<span class="ap-item-copy"><strong>Crear usuario</strong><small>' + (canCreateUser ? 'Crear acceso para este colaborador' : 'Límite de usuarios alcanzado por el plan') + '</small></span>' +
                '</button>';
        }

        $portal.html(html);
        $(".ap-collab-actions-trigger").attr("aria-expanded", "false");
        $trigger.attr("aria-expanded", "true");
        $portal.addClass("is-open").data("owner", cid);

        const rect = $trigger[0].getBoundingClientRect();
        const pw = $portal.outerWidth();
        const ph = $portal.outerHeight();
        const margin = 8;
        let left = rect.right - pw;
        if (left < margin) left = margin;
        if (left + pw > window.innerWidth - margin) left = window.innerWidth - pw - margin;
        let top = rect.bottom + 6;
        if (top + ph > window.innerHeight - margin && rect.top - ph - 6 >= margin) top = rect.top - ph - 6;
        if (top + ph > window.innerHeight - margin) top = Math.max(margin, window.innerHeight - ph - margin);
        $portal.css({ left: Math.round(left) + "px", top: Math.round(top) + "px" });
    }

    function apRenderCollaborators() {
        const $list = $("#ap_collaborator_list");
        if (window.innerWidth < 768) apAdminState.view = "mini";
        $list.toggleClass("ap-mini", apAdminState.view === "mini").toggleClass("ap-detail", apAdminState.view !== "mini");
        const start = (apAdminState.page - 1) * apAdminState.pageSize;
        const end = Math.min(start + apAdminState.pageSize, apAdminState.filtered.length);
        if (!apAdminState.filtered.length) {
            $list.html('<div class="ap-empty"><i class="fas fa-users-slash"></i><strong class="d-block">No hay colaboradores para mostrar</strong><small>Puede registrar el primer colaborador desde el botón superior.</small></div>');
            $("#ap_result_info").text("0 registros");
            $("#ap_pagination").empty(); return;
        }
        let html = "";
        apAdminState.filtered.slice(start,end).forEach(function(c){ html += apAdminState.view === "mini" ? apRenderMini(c) : apRenderDetail(c); });
        $list.html(html);
        $("#ap_result_info").text("Mostrando " + (start+1) + " a " + end + " de " + apAdminState.filtered.length + " registros");
        apRenderPagination();
    }

    function apRenderPagination() {
        const pages = Math.max(1, Math.ceil(apAdminState.filtered.length / apAdminState.pageSize));
        if (pages <= 1) { $("#ap_pagination").empty(); return; }
        let h = '<button data-page="1" ' + (apAdminState.page===1?'disabled':'') + '><i class="fas fa-angle-double-left"></i><span class="d-none d-md-inline ml-1">Inicio</span></button>';
        h += '<button data-page="' + Math.max(1,apAdminState.page-1) + '" ' + (apAdminState.page===1?'disabled':'') + '><i class="fas fa-angle-left"></i></button>';
        const from = Math.max(1, apAdminState.page-2), to = Math.min(pages, from+4);
        for(let i=from;i<=to;i++) h += '<button data-page="'+i+'" class="'+(i===apAdminState.page?'active':'')+'">'+i+'</button>';
        h += '<button data-page="' + Math.min(pages,apAdminState.page+1) + '" ' + (apAdminState.page===pages?'disabled':'') + '><i class="fas fa-angle-right"></i></button>';
        h += '<button data-page="' + pages + '" ' + (apAdminState.page===pages?'disabled':'') + '><span class="d-none d-md-inline mr-1">Final</span><i class="fas fa-angle-double-right"></i></button>';
        $("#ap_pagination").html(h);
    }

    function apFindCollab(id) { return apAdminState.rows.find(function(c){return parseInt(c.colaboradores_id,10)===parseInt(id,10);}); }
    function apFindUser(c, uid) { return c && (c.usuarios||[]).find(function(u){return parseInt(u.users_id,10)===parseInt(uid,10);}); }

    function apOpenCollaborator(c) {
    $("#ap_form_collaborator")[0].reset();
        $("#ap_colaboradores_id").val(c ? c.colaboradores_id : "");
        $("#ap_collab_modal_title").text(c ? "Editar colaborador" : "Nuevo colaborador");
        if (c) {
            $("#ap_colab_nombre").val(c.nombre); $("#ap_colab_identidad").val(c.identidad); $("#ap_colab_telefono").val(c.telefono);
            $("#ap_colab_fecha").val(c.fecha_ingreso); $("#ap_colab_puesto").val(String(c.puestos_id)).trigger("change.select2"); $("#ap_colab_empresa").val(String(c.empresa_id)).trigger("change.select2");
            apSetStatus("ap_colab_estado", parseInt(c.estado,10) === 1, "Activo", "Inactivo", "1", "2");
        } else {
            $("#ap_colab_fecha").val(new Date().toISOString().slice(0,10));
            $("#ap_colab_puesto,#ap_colab_empresa").val(null).trigger("change.select2");
            apSetStatus("ap_colab_estado", true, "Activo", "Inactivo", "1", "2");
        }
        apOpenStaticModal("#modalApColaborador");
        setTimeout(function(){ $("#ap_colab_nombre").trigger("focus"); },180);
    }

    function apOpenUser(c, u) {
        apOpenUnifiedUserModal(c || null, u || null);
    }

    function apCompanyValue(e, keys) {
        if (!e) return "";
        for (let i = 0; i < keys.length; i++) {
            if (e[keys[i]] !== undefined && e[keys[i]] !== null) return e[keys[i]];
        }
        return "";
    }

    function apResetPublicCompanyMedia($modal) {
        $modal.find("#logotipo,#firma_documento").val("");
        $modal.find("#logoPreview,#firmaPreview").empty();
        $modal.find("#logoInfo,#firmaInfo").text("Ningún archivo seleccionado");
    }

    function apOpenCompany(e) {
        if (!e && !apCanCreateCompany()) {
            const l = apPlanLimits();
            apNotify("warning", "Límite de empresas alcanzado", "El plan actual no permite crear más empresas. " + apLimitLabel(apCurrentLimitValue("company"), apFindLimitValue("company"), "empresa", "empresas") + ". Puede editar las existentes.");
            return;
        }

        const $modal = apPrepareExternalModal("#modal_registrar_empresa");
        if (!$modal.length) return;
        apInjectPlanLimitIntoPublicModal($modal, "company");
        const $form = $modal.find("#formEmpresa");
        if (!$form.length) return apNotify("error", "Formulario no disponible", "No se encontró el formulario público de empresa.");

        if ($form[0]) $form[0].reset();
        $form.attr("data-ap-context", "1").removeClass("FormularioAjax");
        $form.find("#empresa_id").val(e ? e.empresa_id : "");
        $modal.find("#reg_empresa").toggle(!e).prop("disabled", false);
        $modal.find("#edi_empresa").toggle(!!e).prop("disabled", false);
        $modal.find(".modal-title").first().html('<i class="fas fa-building mr-2"></i>' + (e ? 'Editar Empresa' : 'Registro de Empresa'));
        apResetPublicCompanyMedia($modal);

        if (e) {
            $modal.find("#empresa_razon_social").val(apCompanyValue(e,["razon_social","razonSocial"]));
            $modal.find("#empresa_empresa").val(apCompanyValue(e,["nombre","empresa"]));
            $modal.find("#rtn_empresa").val(apCompanyValue(e,["rtn"]));
            $modal.find("#sitioweb_empresa").val(apCompanyValue(e,["sitio_web","sitioweb","web"]));
            $modal.find("#telefono_empresa").val(apCompanyValue(e,["telefono"]));
            $modal.find("#empresa_celular").val(apCompanyValue(e,["celular","whatsapp"]));
            $modal.find("#correo_empresa").val(apCompanyValue(e,["correo","email"]));
            $modal.find("#facebook_empresa").val(apCompanyValue(e,["facebook"]));
            $modal.find("#horario_empresa").val(apCompanyValue(e,["horario"]));
            $modal.find("#empresa_eslogan").val(apCompanyValue(e,["eslogan"]));
            $modal.find("#empresa_otra_informacion").val(apCompanyValue(e,["otra_informacion"]));
            $modal.find("#direccion_empresa").val(apCompanyValue(e,["ubicacion","direccion"]));
            $modal.find("#empresa_activo").prop("checked", parseInt(apCompanyValue(e,["estado","activo"]),10) === 1);

            const logo = String(apCompanyValue(e,["logotipo"]) || "");
            const firma = String(apCompanyValue(e,["firma_documento"]) || "");
            if (logo) $modal.find("#logoInfo").text("Logo actual conservado. Seleccione uno nuevo solo si desea reemplazarlo.");
            if (firma) $modal.find("#firmaInfo").text("Firma actual conservada. Seleccione una nueva solo si desea reemplazarla.");
        } else {
            $modal.find("#empresa_activo").prop("checked", true);
        }

        $modal.modal("show");
        setTimeout(function(){ $modal.find("#empresa_razon_social").trigger("focus"); },80);
    }

    function apSavePublicCompanyFlow() {
        const $modal = $("#modal_registrar_empresa");
        const $form = $modal.find("#formEmpresa");
        const empresaId = parseInt($form.find("#empresa_id").val(),10) || 0;
        const razon = String($form.find("#empresa_razon_social").val() || "").trim();
        const nombre = String($form.find("#empresa_empresa").val() || "").trim();
        const rtn = String($form.find("#rtn_empresa").val() || "").trim();
        if (!razon || !nombre || !rtn) {
            apNotify("warning", "Datos incompletos", "Complete Razón Social, Empresa y RTN.");
            $form.find(!razon ? "#empresa_razon_social" : (!nombre ? "#empresa_empresa" : "#rtn_empresa")).trigger("focus");
            return;
        }

        const $btn = $modal.find(empresaId ? "#edi_empresa" : "#reg_empresa");
        $btn.prop("disabled",true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');
        const fd = new FormData($form[0]);
        fd.append("action", "save_company");
        fd.append("server_customers_id", apAdminState.serverCustomerId);
        fd.append("empresa_id", empresaId || "");
        // Alias usados por administrarCliente.php. Se conservan también los nombres originales del formulario público.
        fd.append("razon_social", razon);
        fd.append("nombre", nombre);
        fd.append("rtn", rtn);
        fd.append("correo", String($form.find("#correo_empresa").val() || "").trim());
        fd.append("telefono", String($form.find("#telefono_empresa").val() || "").trim());
        fd.append("celular", String($form.find("#empresa_celular").val() || "").trim());
        fd.append("ubicacion", String($form.find("#direccion_empresa").val() || "").trim());
        fd.append("sitio_web", String($form.find("#sitioweb_empresa").val() || "").trim());
        fd.append("facebook", String($form.find("#facebook_empresa").val() || "").trim());
        fd.append("horario", String($form.find("#horario_empresa").val() || "").trim());
        fd.append("eslogan", String($form.find("#empresa_eslogan").val() || "").trim());
        fd.append("otra_informacion", String($form.find("#empresa_otra_informacion").val() || "").trim());
        fd.append("estado", $form.find("#empresa_activo").is(":checked") ? "1" : "0");

        $.ajax({
            type:"POST",
            url:ASIGNAR_PLANES_URLS.administrarCliente,
            dataType:"json",
            data:fd,
            processData:false,
            contentType:false
        }).done(function(r){
            if (!r || !r.success) return apNotify("error","No se pudo guardar la empresa",r && r.message ? r.message : "No se pudo completar el cambio.");
            $modal.modal("hide");
            apNotify("success",empresaId ? "Empresa actualizada" : "Empresa creada",r.message || "Empresa guardada correctamente.");
            apLoadAdmin();
            apLoadBillingData(false);
        }).fail(function(xhr){ apAjaxError(xhr,"No se pudo guardar la empresa"); })
          .always(function(){ $btn.prop("disabled",false).html(empresaId ? '<i class="fas fa-edit fa-lg mr-1"></i> Confirmar' : '<i class="far fa-save fa-lg mr-1"></i> Registrar'); });
    }

    function apFilteredCompanies() {
        const allRows = (apBillingState.empresas && apBillingState.empresas.length)
            ? apBillingState.empresas
            : ((apAdminState.data && apAdminState.data.empresas) || []);
        const status = String(apAdminState.companyStatus || "1");
        return allRows.filter(function(e){
            if (status === "all") return true;
            return String(parseInt(e.estado, 10) === 1 ? 1 : 0) === status;
        });
    }

    function apCompanyActionsHtml(id) {
        return '<div class="dropdown ap-company-actions">' +
            '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i>Acciones</button>' +
            '<div class="dropdown-menu dropdown-menu-right">' +
                '<button type="button" class="dropdown-item ap-edit-company" data-id="' + id + '"><i class="fas fa-edit mr-2"></i>Editar</button>' +
            '</div>' +
        '</div>';
    }

    function apRenderCompanies() {
        const rows = apFilteredCompanies();
        const status = String(apAdminState.companyStatus || "1");
        const view = apAdminState.companyView === "mini" ? "mini" : "detail";
        $("[data-company-view]").removeClass("active").filter('[data-company-view="' + view + '"]').addClass("active");

        if (!rows.length) {
            const text = status === "1" ? "No hay empresas activas" : (status === "0" ? "No hay empresas inactivas" : "No hay empresas registradas");
            $("#ap_company_list").removeClass("is-detail is-mini").html('<div class="ap-empty"><i class="fas fa-building"></i><strong class="d-block">' + text + '</strong><small>Puede cambiar el filtro de estado para consultar otros registros.</small></div>');
            return;
        }

        let h = '';
        if (view === "detail") {
            h += '<div class="ap-company-detail-head"><div>Empresa</div><div>Razón social</div><div>RTN</div><div>Correo</div><div>Estado</div><div>Acciones</div></div>';
            rows.forEach(function(e){
                const active = parseInt(e.estado,10) === 1;
                h += '<article class="ap-company-detail-row" data-id="'+e.empresa_id+'">' +
                    '<div class="ap-company-detail-main"><strong>'+limpiarHtml(e.nombre||"Empresa")+'</strong></div>' +
                    '<div data-label="Razón social">'+limpiarHtml(e.razon_social||'—')+'</div>' +
                    '<div data-label="RTN">'+limpiarHtml(e.rtn||'Sin RTN')+'</div>' +
                    '<div data-label="Correo">'+limpiarHtml(e.correo||'Sin correo')+'</div>' +
                    '<div data-label="Estado"><span class="ap-company-status ' + (active ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (active ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (active ? 'Activa' : 'Inactiva') + '</span></div>' +
                    '<div data-label="Acciones">'+apCompanyActionsHtml(e.empresa_id)+'</div>' +
                '</article>';
            });
        } else {
            h='<div class="ap-company-grid">';
            rows.forEach(function(e){
                const active = parseInt(e.estado,10) === 1;
                h += '<article class="ap-company-card" data-id="'+e.empresa_id+'">' +
                    '<div class="ap-company-card-top">' +
                        '<div class="ap-company-card-main"><h6>'+limpiarHtml(e.nombre||"Empresa")+'</h6>' +
                        '<span class="ap-company-status ' + (active ? 'is-active' : 'is-inactive') + '"><i class="fas ' + (active ? 'fa-check-circle' : 'fa-pause-circle') + '"></i>' + (active ? 'Activa' : 'Inactiva') + '</span></div>' +
                        apCompanyActionsHtml(e.empresa_id) +
                    '</div>' +
                    '<div class="ap-company-meta"><span>'+limpiarHtml(e.razon_social||'—')+'</span><span><i class="fas fa-id-card mr-1"></i>'+limpiarHtml(e.rtn||'Sin RTN')+'</span><span><i class="fas fa-envelope mr-1"></i>'+limpiarHtml(e.correo||'Sin correo')+'</span></div>' +
                '</article>';
            });
            h+='</div>';
        }
        $("#ap_company_list").removeClass("is-detail is-mini").addClass(view === "mini" ? "is-mini" : "is-detail").html(h);
    }


    $(document)
      .off("change.apStatus", "#ap_colab_estado_toggle,#ap_user_estado_toggle,#ap_empresa_estado_toggle")
      .on("change.apStatus", "#ap_colab_estado_toggle,#ap_user_estado_toggle,#ap_empresa_estado_toggle", function(){
          if (this.id === "ap_colab_estado_toggle") apSetStatus("ap_colab_estado", this.checked, "Activo", "Inactivo", "1", "2");
          if (this.id === "ap_user_estado_toggle") apSetStatus("ap_user_estado", this.checked, "Activo", "Inactivo", "1", "2");
          if (this.id === "ap_empresa_estado_toggle") apSetStatus("ap_empresa_estado", this.checked, "Activa", "Inactiva", "1", "0");
      });

    $(document)
      .off("click.apCollabActionMenu", ".ap-collab-actions-trigger").on("click.apCollabActionMenu", ".ap-collab-actions-trigger", function(e){
          e.preventDefault();
          e.stopPropagation();
          const $this = $(this);
          const $portal = $("#ap_collab_actions_portal");
          const cid = String($this.data("cid") || "");
          if ($portal.hasClass("is-open") && String($portal.data("owner")) === cid) {
              apCloseCollaboratorActionsPortal();
              return;
          }
          apCloseActionsPortal();
          apOpenCollaboratorActionsPortal($this);
      })
      .off("click.apTabs", ".ap-admin-tab").on("click.apTabs", ".ap-admin-tab", function(){ const tab=$(this).data("ap-tab"); $(".ap-admin-tab").removeClass("active"); $(this).addClass("active"); $(".ap-admin-panel").removeClass("active"); $("#ap_panel_"+tab).addClass("active"); if(tab === "secuencias" || tab === "empresas") apLoadBillingData(false); })
      .off("click.apCollaboratorStatus", "[data-collaborator-status]").on("click.apCollaboratorStatus", "[data-collaborator-status]", function(){ apAdminState.collaboratorStatus=String($(this).data("collaborator-status")); $("[data-collaborator-status]").removeClass("active"); $(this).addClass("active"); apAdminState.page=1; apApplyFilter(); })
      .off("click.apCompanyStatus", "[data-company-status]").on("click.apCompanyStatus", "[data-company-status]", function(){ apAdminState.companyStatus=String($(this).data("company-status")); $("[data-company-status]").removeClass("active"); $(this).addClass("active"); apRenderCompanies(); })
      .off("click.apCompanyView", "[data-company-view]").on("click.apCompanyView", "[data-company-view]", function(){ if(window.innerWidth<768)return; apAdminState.companyView=String($(this).data("company-view"))==="mini"?"mini":"detail"; apRenderCompanies(); })
      .off("click.apSequenceStatus", "[data-sequence-status]").on("click.apSequenceStatus", "[data-sequence-status]", function(){ apBillingState.sequenceStatus=String($(this).data("sequence-status")); $("[data-sequence-status]").removeClass("active"); $(this).addClass("active"); apRenderSequenceList(); })
      .off("click.apSequenceView", "[data-sequence-view]").on("click.apSequenceView", "[data-sequence-view]", function(){ if(window.innerWidth<768)return; apBillingState.sequenceView=String($(this).data("sequence-view"))==="mini"?"mini":"detail"; apRenderSequenceList(); })
      .off("click.apDocumentStatus", "[data-document-status]").on("click.apDocumentStatus", "[data-document-status]", function(){ apBillingState.documentStatus=String($(this).data("document-status")); $("[data-document-status]").removeClass("active"); $(this).addClass("active"); apRenderDocumentCatalog(); })
      .off("input.apSearch", "#ap_search").on("input.apSearch", "#ap_search", function(){ apAdminState.page=1; apApplyFilter(); })
      .off("change.apPageSize", "#ap_page_size").on("change.apPageSize", "#ap_page_size", function(){ apAdminState.pageSize=parseInt(this.value,10)||10; apAdminState.page=1; apRenderCollaborators(); })
      .off("click.apView", "[data-ap-view]").on("click.apView", "[data-ap-view]", function(){ if(window.innerWidth<768)return; apAdminState.view=$(this).data("ap-view"); $("[data-ap-view]").removeClass("active"); $(this).addClass("active"); apRenderCollaborators(); })
      .off("click.apPage", "#ap_pagination button").on("click.apPage", "#ap_pagination button", function(){ if(this.disabled)return; apAdminState.page=parseInt($(this).data("page"),10)||1; apRenderCollaborators(); })
      .off("click.apRefresh", "#ap_btn_actualizar").on("click.apRefresh", "#ap_btn_actualizar", apLoadAdmin)
      .off("click.apNewUnifiedUser", "#ap_btn_nuevo_usuario").on("click.apNewUnifiedUser", "#ap_btn_nuevo_usuario", function(){ apCloseCollaboratorActionsPortal(); apOpenUnifiedUserModal(null); })
      .off("click.apEditCollab", ".ap-edit-collab").on("click.apEditCollab", ".ap-edit-collab", function(){ apCloseCollaboratorActionsPortal(); apOpenCollaborator(apFindCollab($(this).data("id"))); })
      .off("click.apNewUser", ".ap-new-user").on("click.apNewUser", ".ap-new-user", function(){ if(this.disabled || $(this).hasClass("is-disabled")) return; apCloseCollaboratorActionsPortal(); const c=apFindCollab($(this).data("cid")); if(c)apOpenUnifiedUserModal(c); })
      .off("click.apEditUser", ".ap-edit-user").on("click.apEditUser", ".ap-edit-user", function(){ apCloseCollaboratorActionsPortal(); const c=apFindCollab($(this).data("cid")); const u=apFindUser(c,$(this).data("uid")); if(c&&u)apOpenUser(c,u); })
      .off("click.apReset", ".ap-reset-user").on("click.apReset", ".ap-reset-user", function(){
          apCloseCollaboratorActionsPortal();
          const uid = $(this).data("uid");
          const email = $(this).data("email");
          apConfirm(
              "Generar nueva contraseña",
              "Se reemplazará la contraseña actual y la nueva se enviará a " + email + ".",
              function(ok){
                  if (!ok) return;
                  apPost("reset_password", {users_id: uid})
                      .done(function(r){
                          apNotify(
                              r && r.success ? "success" : "error",
                              r && r.success ? "Contraseña restablecida" : "No se pudo restablecer",
                              r && r.message ? r.message : "No se pudo completar el restablecimiento."
                          );
                      })
                      .fail(function(xhr){
                          apAjaxError(xhr, "No se pudo restablecer la contraseña");
                      });
              }
          );
      })
      .off("click.apNewCompany", "#ap_btn_nueva_empresa").on("click.apNewCompany", "#ap_btn_nueva_empresa", function(){ if(this.disabled) return; apOpenCompany(null); })
      .off("click.apEditCompany", ".ap-edit-company").on("click.apEditCompany", ".ap-edit-company", function(){ const id=parseInt($(this).data("id"),10); let e=(apBillingState.empresas||[]).find(function(x){return parseInt(x.empresa_id,10)===id;}); if(!e) e=((apAdminState.data||{}).empresas||[]).find(function(x){return parseInt(x.empresa_id,10)===id;}); if(e) apOpenCompany(e); else apLoadBillingData(false).done(function(){ const row=(apBillingState.empresas||[]).find(function(x){return parseInt(x.empresa_id,10)===id;}); if(row) apOpenCompany(row); }); })
      .off("click.apNewSequence", "#ap_btn_nueva_secuencia").on("click.apNewSequence", "#ap_btn_nueva_secuencia", function(){ apOpenSequenceModal(null); })
      .off("click.apDocuments", "#ap_btn_documentos_secuencia").on("click.apDocuments", "#ap_btn_documentos_secuencia", apOpenDocumentsModal)
      .off("click.apRefreshSequences", "#ap_btn_actualizar_secuencias").on("click.apRefreshSequences", "#ap_btn_actualizar_secuencias", function(){ apLoadBillingData(false); })
      .off("click.apEditSequence", ".ap-sequence-edit").on("click.apEditSequence", ".ap-sequence-edit", function(){ const id=parseInt($(this).data("id"),10)||0; const row=apBillingState.secuencias.find(function(x){return parseInt(x.secuencia_facturacion_id,10)===id;}); if(row) apOpenSequenceModal(row); })
      .off("click.apDeleteSequence", ".ap-sequence-delete").on("click.apDeleteSequence", ".ap-sequence-delete", function(){ apDeleteSequence($(this).data("id")); })
      .off("click.apRefreshDocuments", "#btn_refrescar_documentos_secuencia").on("click.apRefreshDocuments", "#btn_refrescar_documentos_secuencia", function(){ apLoadBillingData(true); })
      .off("click.apCancelDocumentEdit", "#btn_cancelar_edicion_documento").on("click.apCancelDocumentEdit", "#btn_cancelar_edicion_documento", apResetDocumentForm)
      .off("click.apEditDocument", ".ap-document-edit").on("click.apEditDocument", ".ap-document-edit", function(){ apEditDocument($(this).data("id")); })
      .off("click.apDocumentState", ".ap-document-state").on("click.apDocumentState", ".ap-document-state", function(){ apChangeDocumentState($(this).data("id"), $(this).data("state")); })
      .off("click.apDeleteDocument", ".ap-document-delete").on("click.apDeleteDocument", ".ap-document-delete", function(){ apDeleteDocument($(this).data("id")); })
      .off("click.apSequenceDocuments", "#btn_administrar_documentos_desde_modal").on("click.apSequenceDocuments", "#btn_administrar_documentos_desde_modal", function(){
          if ($("#modal_registrar_secuencias").hasClass("show")) $("#modal_registrar_secuencias").modal("hide");
          setTimeout(apOpenDocumentsModal, 40);
      });


    $(document)
        .off("change.apPublicCollab", "#modal_registrar_usuarios #colaboradores_id")
        .on("change.apPublicCollab", "#modal_registrar_usuarios #colaboradores_id", function(){
            const c = apFindCollab(parseInt($(this).val(), 10) || 0);
            apShowPublicCollaboratorInfo(c || null);
            if (c && c.empresa_id) {
                $("#modal_registrar_usuarios #empresa_usuario").val(String(c.empresa_id));
                apRefreshSelectpicker($("#modal_registrar_usuarios #empresa_usuario"));
            }
        })
        .off("shown.bs.tab.apPublicUser", "#modal_registrar_usuarios #existente-tab,#modal_registrar_usuarios #nuevo-tab")
        .on("shown.bs.tab.apPublicUser", "#modal_registrar_usuarios #existente-tab,#modal_registrar_usuarios #nuevo-tab", function(e){
            const isNew = $(e.target).attr("id") === "nuevo-tab";
            $("#modal_registrar_usuarios #es_nuevo_colaborador").val(isNew ? "1" : "0");
            setTimeout(function(){
                $(isNew ? "#modal_registrar_usuarios #nombre_colaborador" : "#modal_registrar_usuarios #colaboradores_id").trigger("focus");
            }, 30);
        })
        .off("hidden.bs.modal.apExternal", "#modal_registrar_usuarios,#modal_registrar_empresa,#modal_registrar_secuencias,#modal_documentos_secuencia,#modal_registrar_privilegios,#modal_registrar_menuaccesos,#modal_registrar_submenuaccesos,#modal_registrar_submenu1accesos,#modal_registrar_tipoUsuario,#modal_permisos")
        .on("hidden.bs.modal.apExternal", "#modal_registrar_usuarios,#modal_registrar_empresa,#modal_registrar_secuencias,#modal_documentos_secuencia,#modal_registrar_privilegios,#modal_registrar_menuaccesos,#modal_registrar_submenuaccesos,#modal_registrar_submenu1accesos,#modal_registrar_tipoUsuario,#modal_permisos", function(){
            const $modal = $(this);
            $modal.removeClass("ap-from-assignment").addClass("fade").removeAttr("data-ap-context");
            $modal.find("#formUsers,#formEmpresa,#formSecuencia").addClass("FormularioAjax").removeAttr("data-ap-context");
            $modal.find("#formDocumentoSecuencia").removeAttr("data-ap-context");
            if ($modal.attr("id") === "modal_registrar_usuarios") {
                $modal.find("#nuevo-tab").removeClass("disabled").removeAttr("aria-disabled").css("pointer-events", "");
                $modal.find("#colaboradores_id").prop("disabled", false);
                apRefreshSelectpicker($modal.find("#colaboradores_id"));
            }
            if ($("#modalAdministrarCliente").hasClass("show")) $(document.body).addClass("modal-open");
        });

    // Intercepta los formularios públicos únicamente cuando fueron abiertos desde Asignación de Planes.
    // Se usa captura para evitar que los handlers globales guarden en la base de la sesión administrativa.
    if (!window.__apPublicFormsCaptureInstalled) {
        window.__apPublicFormsCaptureInstalled = true;
        document.addEventListener("submit", function(e){
            const form = e.target;
            if (!form || form.getAttribute("data-ap-context") !== "1") return;
            if (["formUsers","formEmpresa","formSecuencia","formDocumentoSecuencia"].indexOf(form.id) === -1) return;
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();
            if (form.id === "formUsers") apSavePublicUserFlow();
            if (form.id === "formEmpresa") apSavePublicCompanyFlow();
            if (form.id === "formSecuencia") apSaveSequence();
            if (form.id === "formDocumentoSecuencia") apSaveDocument();
        }, true);
    }

    $("#ap_form_collaborator")
        .off("submit.apSave")
        .on("submit.apSave", function(e){
            e.preventDefault();
            const data = $(this).serializeArray().reduce(function(o, x){ o[x.name] = x.value; return o; }, {});

            apPost("save_collaborator", data)
                .done(function(r){
                    if (!r || !r.success) {
                        apNotify("error", "No se pudo guardar el colaborador", r && r.message ? r.message : "No se pudo completar el registro.");
                        return;
                    }

                    const wasNew = !data.colaboradores_id;
                    const newId = r.data && r.data.colaboradores_id ? parseInt(r.data.colaboradores_id, 10) : 0;

                    $("#modalApColaborador").modal("hide");
                    apNotify("success", wasNew ? "Colaborador creado" : "Colaborador actualizado", r.message || "Cambio realizado correctamente.");

                    apLoadAdmin();
                })
                .fail(function(xhr){
                    apAjaxError(xhr, "No se pudo guardar el colaborador");
                });
        });

    /* =========================================================
       CICLO DE VIDA DE MODALES
       La geometría queda exclusivamente en CSS. JS sólo garantiza
       que el modal esté montado en body y gestiona foco/scroll.
       ========================================================= */
    // Los modales ya se montan antes de abrirse mediante apOpenStaticModal().
    // No se reubican durante show.bs.modal para evitar saltos de tamaño/reflow.

    $("#modalApColaborador")
        .off("hidden.bs.modal.apNested")
        .on("hidden.bs.modal.apNested", function(){
            if ($("#modalAdministrarCliente").hasClass("show")) {
                $(document.body).addClass("modal-open");
                setTimeout(function(){ $("#ap_search").trigger("focus"); }, 80);
            }
        })
        .off("shown.bs.modal.apFocus")
        .on("shown.bs.modal.apFocus", function(){
            const $modal = $(this);
            $modal.find(".modal-body").scrollTop(0);
            setTimeout(function(){
                $modal
                    .find('.modal-body input:not([type="hidden"]):not([readonly]):enabled, .modal-body select:enabled, .modal-body textarea:enabled')
                    .first()
                    .trigger("focus");
            }, 60);
        });

    $("#modalAdministrarCliente")
        .off("shown.bs.modal.apFocus")
        .on("shown.bs.modal.apFocus", function(){
            const $modal = $(this);
            $modal.find(".modal-body").scrollTop(0);
            setTimeout(function(){
                $modal.find(".modal-body").scrollTop(0);
                $("#ap_search").trigger("focus");
            }, 60);
        });

    function apExportRows() {
        const rows = [];
        apAdminState.filtered.forEach(function(c){
            const users = c.usuarios && c.usuarios.length ? c.usuarios : [null];
            users.forEach(function(u){
                rows.push({
                    Colaborador: c.nombre,
                    Identidad: c.identidad || "",
                    Telefono: c.telefono || "",
                    FechaIngreso: c.fecha_ingreso || "",
                    EstadoColaborador: parseInt(c.estado,10) === 1 ? "Activo" : "Inactivo",
                    TieneUsuario: u ? "Sí" : "No",
                    Correo: u ? u.email : "",
                    Empresa: u ? u.empresa_nombre : "",
                    EstadoUsuario: u ? (parseInt(u.estado,10) === 1 ? "Activo" : "Inactivo") : ""
                });
            });
        });
        return rows;
    }

    function apAdminExcelRows(rows) {
        return rows.map(function(r){
            return [r.Colaborador,r.Identidad,r.Telefono,r.FechaIngreso,r.EstadoColaborador,r.TieneUsuario,r.Correo,r.Empresa,r.EstadoUsuario];
        });
    }

    function apAdminGenerarXlsx(rows) {
        if (typeof JSZip === "undefined") return null;
        const data = apAdminExcelRows(rows);
        const headers = ["Colaborador","Identidad","Teléfono","Fecha ingreso","Estado colaborador","Tiene usuario","Correo","Empresa","Estado usuario"];
        const totalActivos = apAdminState.filtered.filter(function(c){return parseInt(c.estado,10)===1;}).length;
        const totalInactivos = apAdminState.filtered.length - totalActivos;
        const totalUsuarios = rows.filter(function(r){return r.TieneUsuario === "Sí";}).length;
        const lastCol = "I", headerRow = 7, firstDataRow = 8, lastRow = Math.max(headerRow, headerRow + data.length);
        const sheetRows = [];

        sheetRows.push('<row r="1" ht="30" customHeight="1">'+asignacionExcelCell('A1','IZZY • ADMINISTRACIÓN DE CLIENTE',1,false)+'</row>');
        const c=(apAdminState.data||{}).cliente||{};
        sheetRows.push('<row r="2" ht="20" customHeight="1">'+asignacionExcelCell('A2',(c.nombre||'Cliente')+' • Colaboradores, usuarios y accesos • Generado: '+new Date().toLocaleDateString('es-HN'),2,false)+'</row>');
        sheetRows.push('<row r="3" ht="18" customHeight="1">'+asignacionExcelCell('A3','COLABORADORES',6,false)+asignacionExcelCell('D3','ACTIVOS',6,false)+asignacionExcelCell('F3','INACTIVOS',6,false)+asignacionExcelCell('H3','USUARIOS',6,false)+'</row>');
        sheetRows.push('<row r="4" ht="26" customHeight="1">'+asignacionExcelCell('A4',apAdminState.filtered.length,7,true)+asignacionExcelCell('D4',totalActivos,7,true)+asignacionExcelCell('F4',totalInactivos,7,true)+asignacionExcelCell('H4',totalUsuarios,7,true)+'</row>');
        sheetRows.push('<row r="5"></row>');
        sheetRows.push('<row r="6" ht="18" customHeight="1">'+asignacionExcelCell('A6','Detalle filtrado · Estado: '+(apAdminState.collaboratorStatus==='1'?'Activos':(apAdminState.collaboratorStatus==='0'?'Inactivos':'Todos')),8,false)+'</row>');
        sheetRows.push('<row r="7" ht="26" customHeight="1">'+headers.map(function(h,i){return asignacionExcelCell(asignacionExcelColName(i)+'7',h,3,false);}).join('')+'</row>');
        data.forEach(function(row,ri){
            const er=firstDataRow+ri;
            sheetRows.push('<row r="'+er+'" ht="22" customHeight="1">'+row.map(function(v,ci){
                let style=4;
                if (ci===4 || ci===8) style=String(v||'').toLowerCase()==='activo'?9:(String(v||'').toLowerCase()==='inactivo'?10:4);
                return asignacionExcelCell(asignacionExcelColName(ci)+er,v,style,false);
            }).join('')+'</row>');
        });
        const sheetXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'+lastCol+lastRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A8" sqref="A8"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols>'+ 
        '<col min="1" max="1" width="30" customWidth="1"/><col min="2" max="2" width="20" customWidth="1"/><col min="3" max="3" width="15" customWidth="1"/><col min="4" max="4" width="16" customWidth="1"/><col min="5" max="6" width="18" customWidth="1"/><col min="7" max="7" width="34" customWidth="1"/><col min="8" max="8" width="26" customWidth="1"/><col min="9" max="9" width="18" customWidth="1"/></cols><sheetData>'+sheetRows.join('')+'</sheetData><autoFilter ref="A7:I'+lastRow+'"/><mergeCells count="10"><mergeCell ref="A1:I1"/><mergeCell ref="A2:I2"/><mergeCell ref="A3:C3"/><mergeCell ref="A4:C4"/><mergeCell ref="D3:E3"/><mergeCell ref="D4:E4"/><mergeCell ref="F3:G3"/><mergeCell ref="F4:G4"/><mergeCell ref="H3:I3"/><mergeCell ref="H4:I4"/></mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/></worksheet>';
        const stylesXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="7"><font><sz val="10"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="7"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        const workbookXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets><sheet name="Colaboradores" sheetId="1" r:id="rId1"/></sheets></workbook>';
        const workbookRels='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        const rootRels='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        const contentTypes='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        const zip=new JSZip(); zip.file('[Content_Types].xml',contentTypes); zip.folder('_rels').file('.rels',rootRels); zip.folder('xl').file('workbook.xml',workbookXml); zip.folder('xl').file('styles.xml',stylesXml); zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels); zip.folder('xl').folder('worksheets').file('sheet1.xml',sheetXml);
        const opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
        if(typeof zip.generateAsync==='function') return zip.generateAsync(opts);
        if(typeof zip.generate==='function') { try{return Promise.resolve(zip.generate(opts));}catch(e){return Promise.reject(e);} }
        return Promise.reject(new Error('La versión de JSZip no soporta la generación de XLSX.'));
    }

    function apExportAdminExcelPremium() {
        const rows=apExportRows();
        if(!rows.length){apNotify('warning','Sin datos','No hay colaboradores para exportar con el filtro actual.');return;}
        const promise=apAdminGenerarXlsx(rows);
        if(!promise){
            let csv='Colaborador,Identidad,Telefono,Fecha ingreso,Estado colaborador,Tiene usuario,Correo,Empresa,Estado usuario\n';
            rows.forEach(function(r){csv+=[r.Colaborador,r.Identidad,r.Telefono,r.FechaIngreso,r.EstadoColaborador,r.TieneUsuario,r.Correo,r.Empresa,r.EstadoUsuario].map(function(v){return '"'+String(v||'').replace(/"/g,'""')+'"';}).join(',')+'\n';});
            asignacionDescargarBlob('\ufeff'+csv,'Administracion_Cliente.csv','text/csv;charset=utf-8;');
            apNotify('warning','Excel compatible','JSZip no está disponible; se generó un CSV compatible con Excel.');
            return;
        }
        const c=(apAdminState.data||{}).cliente||{};
        promise.then(function(blob){asignacionDescargarBlob(blob,'Administracion_Cliente_'+(c.codigo_cliente||c.server_customers_id||'')+'.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',true);})
            .catch(function(err){console.error(err);apNotify('error','Error al generar Excel','No se pudo generar el archivo Excel.');});
    }

    function apAdminPdfTable(rows) {
        const body=[[{text:'COLABORADOR',style:'th',fillColor:'#17324D'},{text:'IDENTIDAD',style:'th',fillColor:'#17324D'},{text:'ESTADO COLAB.',style:'th',fillColor:'#17324D'},{text:'USUARIO',style:'th',fillColor:'#17324D'},{text:'CORREO',style:'th',fillColor:'#17324D'},{text:'EMPRESA',style:'th',fillColor:'#17324D'},{text:'ESTADO USUARIO',style:'th',fillColor:'#17324D'}]];
        rows.forEach(function(r,i){
            const fill=i%2===0?'#FFFFFF':'#F7F9FC';
            body.push([
                {text:r.Colaborador||'—',fillColor:fill,bold:true},
                {text:r.Identidad||'—',fillColor:fill},
                {text:r.EstadoColaborador||'—',fillColor:fill,color:r.EstadoColaborador==='Activo'?'#14804A':'#C9372C',bold:true,alignment:'center'},
                {text:r.TieneUsuario||'—',fillColor:fill,alignment:'center'},
                {text:r.Correo||'—',fillColor:fill},
                {text:r.Empresa||'—',fillColor:fill},
                {text:r.EstadoUsuario||'—',fillColor:fill,color:r.EstadoUsuario==='Activo'?'#14804A':(r.EstadoUsuario==='Inactivo'?'#C9372C':'#6B778C'),bold:true,alignment:'center'}
            ]);
        });
        return {table:{headerRows:1,widths:[120,82,72,52,155,115,72],body:body},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 5;},paddingRight:function(){return 5;},paddingTop:function(){return 6;},paddingBottom:function(){return 6;}}};
    }

    function apExportAdminPdfPremium() {
        if(typeof pdfMake==='undefined'||!pdfMake.createPdf){apNotify('warning','PDF no disponible','La librería PDF no está cargada.');return;}
        const rows=apExportRows();
        if(!rows.length){apNotify('warning','Sin datos','No hay colaboradores para exportar con el filtro actual.');return;}
        if (!(typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/')===0)) {
            asignacionObtenerLogoPdf(function(logoDataUrl){try{imagen=logoDataUrl;}catch(e){} apExportAdminPdfPremium();});
            return;
        }
        const c=(apAdminState.data||{}).cliente||{};
        const totalActivos=apAdminState.filtered.filter(function(x){return parseInt(x.estado,10)===1;}).length;
        const totalInactivos=apAdminState.filtered.length-totalActivos;
        const totalUsuarios=rows.filter(function(r){return r.TieneUsuario==='Sí';}).length;
        const filtroEstado=apAdminState.collaboratorStatus==='1'?'Activos':(apAdminState.collaboratorStatus==='0'?'Inactivos':'Todos');
        const busqueda=String($('#ap_search').val()||'').trim();
        const encabezado={table:{widths:[100,'*',150],body:[[
            {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[asignacionPdfLogoPlate(imagen)]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[{text:'ADMINISTRACIÓN DE CLIENTE',fontSize:16,bold:true,color:'#FFFFFF'},{text:'Colaboradores, usuarios, empresas y accesos IZZY',fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,12,10],stack:[{text:'REPORTE EJECUTIVO',fontSize:6.5,bold:true,color:'#72E2E5',alignment:'right'},{text:asignacionFechaReporte(),fontSize:9,bold:true,color:'#FFFFFF',alignment:'right',margin:[0,3,0,0]},{text:apAdminState.filtered.length+' colaborador(es) filtrado(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right',margin:[0,2,0,0]}]}
        ]]},layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},margin:[0,0,0,10]};
        const clienteInfo={table:{widths:['*'],body:[[{text:(c.nombre||'Cliente')+'   |   RTN: '+(c.rtn||'—')+'   |   Código: '+(c.codigo_cliente||'—')+'   |   Base: '+(c.db||'—'),fontSize:7.2,bold:true,color:'#17324D',margin:[10,7,10,7],fillColor:'#EEF6FC'}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,7]};
        const filtros={table:{widths:['*'],body:[[{text:'Filtros aplicados: Estado: '+filtroEstado+'   |   Búsqueda: '+(busqueda||'Sin búsqueda'),fontSize:6.8,color:'#52627A',margin:[10,7,10,7],fillColor:'#F7F9FC'}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,10]};
        const resumen={table:{widths:['*','*','*','*'],body:[[
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'COLABORADORES',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(apAdminState.filtered.length),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(totalActivos),fontSize:13,bold:true,color:'#14804A',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'INACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(totalInactivos),fontSize:13,bold:true,color:'#C9372C',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'USUARIOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(totalUsuarios),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]}
        ]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,12]};
        const doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(currentPage,pageCount){return{margin:[28,8,28,0],columns:[{text:'IZZY • Administración de Cliente',fontSize:7,color:'#7A869A'},{text:'Página '+currentPage+' de '+pageCount,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[encabezado,clienteInfo,filtros,resumen,{text:apAdminState.view==='mini'?'VISTA MINIATURA':'VISTA DETALLE',fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]},apAdminPdfTable(rows)],styles:{th:{fontSize:6.4,bold:true,color:'#FFFFFF',alignment:'center'}},defaultStyle:{fontSize:8,color:'#253858'}};
        const pdf=pdfMake.createPdf(doc), nombre='Administracion_Cliente_'+(c.codigo_cliente||c.server_customers_id||'')+'.pdf';
        if(typeof pdf.getDataUrl==='function'){pdf.getDataUrl(function(url){apAbrirVisorPdfPublico(url,'Administración de colaboradores y usuarios',nombre);});return;}
        if(typeof pdf.getBase64==='function'){pdf.getBase64(function(base64){apAbrirVisorPdfPublico('data:application/pdf;base64,'+base64,'Administración de colaboradores y usuarios',nombre);});return;}
        apNotify('error','PDF no disponible','La versión actual de pdfMake no permite previsualización compatible.');
    }



    /* =========================================================
       PRIVILEGIOS + TIPOS DE USUARIO / PERMISOS
       Contexto exclusivo de la BD del cliente administrado.
       Los formularios públicos permanecen en vistasModals.
       ========================================================= */
    const AP_PERMISSION_DEFINITIONS = [
        {key:"guardar", label:"Guardar", icon:"fa-save"},
        {key:"editar", label:"Modificar", icon:"fa-edit"},
        {key:"eliminar", label:"Eliminar", icon:"fa-trash"},
        {key:"consultar", label:"Consultar", icon:"fa-search"},
        {key:"imprimir", label:"Imprimir", icon:"fa-print"},
        {key:"crear", label:"Crear", icon:"fa-plus-circle"},
        {key:"reportes", label:"Reportes", icon:"fa-chart-bar"},
        {key:"actualizar", label:"Actualizar", icon:"fa-sync-alt"},
        {key:"view", label:"Seleccionar", icon:"fa-eye"},
        {key:"pay", label:"Cobrar", icon:"fa-money-bill-wave"},
        {key:"cambiar", label:"Cambiar contraseña", icon:"fa-key"},
        {key:"cancelar", label:"Cancelar", icon:"fa-ban"},
        {key:"sistema", label:"Sistema", icon:"fa-desktop"},
        {key:"generar", label:"Generar sistema", icon:"fa-cogs"}
    ];

    function apAccessRequest(action, data) {
        return $.ajax({
            type: "POST",
            url: AP_ASSIGNMENT_API_URL,
            dataType: "json",
            data: $.extend({}, data || {}, {
                ap_api_action: action,
                server_customers_id: apAdminState.serverCustomerId,
                planes_id: apAdminState.currentPlanId
            })
        });
    }

    function apLoadAccessData(showLoading) {
        if (!apAdminState.serverCustomerId || apAdminState.accessLoading) return $.Deferred().reject().promise();
        apAdminState.accessLoading = true;
        if (showLoading !== false) {
            $("#ap_privilege_list,#ap_type_list").html('<div class="ap-loading"><i class="fas fa-spinner fa-spin"></i> Cargando accesos del cliente...</div>');
        }
        return apAccessRequest("access_catalogs")
            .done(function(resp) {
                if (!resp || !resp.success) {
                    apNotify("error", "No se pudieron cargar los accesos", resp && resp.message ? resp.message : "Error consultando privilegios y permisos.");
                    return;
                }
                const data = resp.data || {};
                apAdminState.privileges = Array.isArray(data.privilegios) ? data.privilegios : [];
                apAdminState.types = Array.isArray(data.tipos_usuario) ? data.tipos_usuario : [];
                apAdminState.permissionsByType = data.permisos || {};
                apAdminState.accessLoaded = true;
                apRenderPrivileges();
                apRenderTypes();
            })
            .fail(function(xhr){ apAjaxError(xhr, "No se pudieron cargar privilegios y permisos"); })
            .always(function(){ apAdminState.accessLoading = false; });
    }

    function apFilteredPrivileges() {
        const status=String(apAdminState.privilegeStatus||"1");
        const q=normalizarTexto(apAdminState.privilegeSearch||"");
        return (apAdminState.privileges||[]).filter(function(row){
            if(status!=="all" && String(parseInt(row.estado,10)===1?1:0)!==status) return false;
            if(!q) return true;
            return normalizarTexto([row.nombre,row.usuarios_asignados,row.menus_asignados,row.submenus_asignados,row.submenus1_asignados].join(" ")).indexOf(q)!==-1;
        });
    }

    function apFilteredTypes() {
        const status=String(apAdminState.typeStatus||"1");
        const q=normalizarTexto(apAdminState.typeSearch||"");
        return (apAdminState.types||[]).filter(function(row){
            if(status!=="all" && String(parseInt(row.estado,10)===1?1:0)!==status) return false;
            if(!q) return true;
            const perms=apAdminState.permissionsByType[String(row.tipo_user_id)]||apAdminState.permissionsByType[row.tipo_user_id]||{};
            const enabled=AP_PERMISSION_DEFINITIONS.filter(function(p){return parseInt(perms[p.key],10)===1;}).map(function(p){return p.label;}).join(" ");
            return normalizarTexto([row.nombre,row.usuarios_asignados,enabled].join(" ")).indexOf(q)!==-1;
        });
    }

    function apAccessStatusBadge(active) {
        return '<span class="ap-status-badge '+(active?'is-active':'is-inactive')+'"><i class="fas '+(active?'fa-check-circle':'fa-pause-circle')+'"></i> '+(active?'Activo':'Inactivo')+'</span>';
    }

    function apPrivilegeActions(row) {
        const id=parseInt(row.privilegio_id,10)||0;
        return '<div class="dropdown ap-access-actions">'+
            '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i> Acciones</button>'+
            '<div class="dropdown-menu dropdown-menu-right">'+
                '<button type="button" class="dropdown-item ap-priv-menus" data-id="'+id+'"><i class="fas fa-bars mr-2"></i>Menús</button>'+
                '<button type="button" class="dropdown-item ap-priv-submenus" data-id="'+id+'"><i class="fas fa-list-ul mr-2"></i>Submenús nivel 1</button>'+
                '<button type="button" class="dropdown-item ap-priv-submenus1" data-id="'+id+'"><i class="fas fa-list-ol mr-2"></i>Submenús nivel 2</button>'+
                '<div class="dropdown-divider"></div>'+
                '<button type="button" class="dropdown-item ap-edit-privilege" data-id="'+id+'"><i class="fas fa-edit mr-2"></i>Editar privilegio</button>'+
                '<button type="button" class="dropdown-item text-danger ap-delete-privilege" data-id="'+id+'" '+((id===1||id===2)?'disabled aria-disabled="true"':'')+'><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>'+
            '</div>'+
        '</div>';
    }

    function apTypeActions(row) {
        const id=parseInt(row.tipo_user_id,10)||0;
        return '<div class="dropdown ap-access-actions">'+
            '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-cog mr-1"></i> Acciones</button>'+
            '<div class="dropdown-menu dropdown-menu-right">'+
                '<button type="button" class="dropdown-item ap-configure-permissions" data-id="'+id+'"><i class="fas fa-key mr-2"></i>Configurar permisos</button>'+
                '<button type="button" class="dropdown-item ap-edit-type" data-id="'+id+'"><i class="fas fa-edit mr-2"></i>Editar tipo de usuario</button>'+
                '<div class="dropdown-divider"></div>'+
                '<button type="button" class="dropdown-item text-danger ap-delete-type" data-id="'+id+'" '+((id===1||id===2)?'disabled aria-disabled="true"':'')+'><i class="fas fa-trash-alt mr-2"></i>Eliminar</button>'+
            '</div>'+
        '</div>';
    }

    function apRenderPrivileges() {
        const rows=apFilteredPrivileges();
        const mini=window.innerWidth<768 || apAdminState.privilegeView==="mini";
        let html="";
        rows.forEach(function(row){
            const active=parseInt(row.estado,10)===1;
            if(mini){
                html+='<article class="ap-access-card ap-access-card-mini">'+
                    '<div class="ap-access-card-top"><div class="ap-access-icon"><i class="fas fa-user-shield"></i></div><div class="ap-access-title"><strong>'+limpiarHtml(row.nombre||"Privilegio")+'</strong>'+apAccessStatusBadge(active)+'</div>'+apPrivilegeActions(row)+'</div>'+
                    '<div class="ap-access-metrics">'+
                        '<span><i class="fas fa-bars"></i><strong>'+parseInt(row.menus_asignados||0,10)+'</strong><small>Menús</small></span>'+
                        '<span><i class="fas fa-list-ul"></i><strong>'+parseInt(row.submenus_asignados||0,10)+'</strong><small>Submenús</small></span>'+
                        '<span><i class="fas fa-list-ol"></i><strong>'+parseInt(row.submenus1_asignados||0,10)+'</strong><small>Nivel 2</small></span>'+
                        '<span><i class="fas fa-users"></i><strong>'+parseInt(row.usuarios_asignados||0,10)+'</strong><small>Usuarios</small></span>'+
                    '</div>'+
                '</article>';
            }else{
                html+='<article class="ap-access-row">'+
                    '<div class="ap-access-main"><span class="ap-access-icon"><i class="fas fa-user-shield"></i></span><div><strong>'+limpiarHtml(row.nombre||"Privilegio")+'</strong><small>ID: '+limpiarHtml(row.privilegio_id)+'</small></div></div>'+
                    '<div><small>Estado</small>'+apAccessStatusBadge(active)+'</div>'+
                    '<div><small>Menús</small><strong>'+parseInt(row.menus_asignados||0,10)+'</strong></div>'+
                    '<div><small>Submenús</small><strong>'+parseInt(row.submenus_asignados||0,10)+'</strong></div>'+
                    '<div><small>Nivel 2</small><strong>'+parseInt(row.submenus1_asignados||0,10)+'</strong></div>'+
                    '<div><small>Usuarios</small><strong>'+parseInt(row.usuarios_asignados||0,10)+'</strong></div>'+
                    '<div class="ap-access-row-actions">'+apPrivilegeActions(row)+'</div>'+
                '</article>';
            }
        });
        $("#ap_privilege_list").html(html).toggleClass("is-mini",mini);
        $("#ap_privilege_empty").toggleClass("d-none",rows.length>0);
        $("#ap_privilege_total").text(rows.length);
        $("#ap_privilege_menu_total").text(rows.reduce(function(n,r){return n+(parseInt(r.menus_asignados,10)||0);},0));
        $("#ap_privilege_user_total").text(rows.reduce(function(n,r){return n+(parseInt(r.usuarios_asignados,10)||0);},0));
    }

    function apPermissionBadges(typeId, max) {
        const perms=apAdminState.permissionsByType[String(typeId)]||apAdminState.permissionsByType[typeId]||{};
        const enabled=AP_PERMISSION_DEFINITIONS.filter(function(p){return parseInt(perms[p.key],10)===1;});
        if(!enabled.length) return '<span class="ap-permission-empty">Sin permisos activos</span>';
        let h=enabled.slice(0,max||14).map(function(p){return '<span class="ap-permission-chip"><i class="fas '+p.icon+'"></i>'+limpiarHtml(p.label)+'</span>';}).join("");
        if(max && enabled.length>max)h+='<span class="ap-permission-chip more">+'+(enabled.length-max)+'</span>';
        return h;
    }

    function apRenderTypes() {
        const rows=apFilteredTypes();
        const mini=window.innerWidth<768 || apAdminState.typeView==="mini";
        let html="";
        rows.forEach(function(row){
            const active=parseInt(row.estado,10)===1;
            if(mini){
                html+='<article class="ap-access-card ap-access-card-mini">'+
                    '<div class="ap-access-card-top"><div class="ap-access-icon type"><i class="fas fa-user-tag"></i></div><div class="ap-access-title"><strong>'+limpiarHtml(row.nombre||"Tipo de usuario")+'</strong>'+apAccessStatusBadge(active)+'</div>'+apTypeActions(row)+'</div>'+
                    '<div class="ap-access-meta-line"><span><i class="fas fa-users mr-1"></i>'+parseInt(row.usuarios_asignados||0,10)+' usuario(s)</span><span><i class="fas fa-key mr-1"></i>'+parseInt(row.permisos_activos||0,10)+' / '+AP_PERMISSION_DEFINITIONS.length+' permisos</span></div>'+
                    '<div class="ap-permission-chips">'+apPermissionBadges(row.tipo_user_id,6)+'</div>'+
                '</article>';
            }else{
                html+='<article class="ap-access-row ap-type-row">'+
                    '<div class="ap-access-main"><span class="ap-access-icon type"><i class="fas fa-user-tag"></i></span><div><strong>'+limpiarHtml(row.nombre||"Tipo de usuario")+'</strong><small>ID: '+limpiarHtml(row.tipo_user_id)+'</small></div></div>'+
                    '<div><small>Estado</small>'+apAccessStatusBadge(active)+'</div>'+
                    '<div><small>Permisos activos</small><strong>'+parseInt(row.permisos_activos||0,10)+' / '+AP_PERMISSION_DEFINITIONS.length+'</strong></div>'+
                    '<div><small>Usuarios</small><strong>'+parseInt(row.usuarios_asignados||0,10)+'</strong></div>'+
                    '<div class="ap-type-permission-preview"><small>Permisos</small><div class="ap-permission-chips">'+apPermissionBadges(row.tipo_user_id,4)+'</div></div>'+
                    '<div class="ap-access-row-actions">'+apTypeActions(row)+'</div>'+
                '</article>';
            }
        });
        $("#ap_type_list").html(html).toggleClass("is-mini",mini);
        $("#ap_type_empty").toggleClass("d-none",rows.length>0);
        $("#ap_type_total").text(rows.length);
        $("#ap_type_permission_total").text(rows.reduce(function(n,r){return n+(parseInt(r.permisos_activos,10)||0);},0));
        $("#ap_type_user_total").text(rows.reduce(function(n,r){return n+(parseInt(r.usuarios_asignados,10)||0);},0));
    }

    function apFindPrivilege(id){return (apAdminState.privileges||[]).find(function(r){return parseInt(r.privilegio_id,10)===parseInt(id,10);});}
    function apFindType(id){return (apAdminState.types||[]).find(function(r){return parseInt(r.tipo_user_id,10)===parseInt(id,10);});}

    function apOpenPrivilegeModal(row) {
        const $modal=apPrepareExternalModal("#modal_registrar_privilegios");
        if(!$modal.length)return;
        const $form=$modal.find("#formPrivilegios");
        $form[0].reset();
        $form.find("#privilegio_id_").val(row?row.privilegio_id:"");
        $form.find("#privilegios_nombre").val(row?row.nombre:"");
        $form.find("#privilegio_activo").prop("checked",row?parseInt(row.estado,10)===1:true);
        $modal.find("#label_privilegio_activo").text((row?parseInt(row.estado,10)===1:true)?"Privilegio Activo":"Privilegio Inactivo");
        $modal.find("#reg_privilegios").toggle(!row);
        $modal.find("#edi_privilegios").toggle(!!row);
        $form.attr({"data-form":row?"update":"save",action:""}).removeClass("FormularioAjax");
        $modal.modal("show");
        setTimeout(function(){$form.find("#privilegios_nombre").trigger("focus");},100);
    }

    function apOpenTypeModal(row) {
        const $modal=apPrepareExternalModal("#modal_registrar_tipoUsuario");
        if(!$modal.length)return;
        const $form=$modal.find("#formTipoUsuario");
        $form[0].reset();
        $form.find("#tipo_user_id").val(row?row.tipo_user_id:"");
        $form.find("#tipo_usuario_nombre").val(row?row.nombre:"");
        $form.find("#tipo_usuario_activo").prop("checked",row?parseInt(row.estado,10)===1:true);
        $modal.find("#label_tipo_usuario_activo").text((row?parseInt(row.estado,10)===1:true)?"Tipo de Usuario Activo":"Tipo de Usuario Inactivo");
        $modal.find("#reg_tipo_usuario").toggle(!row);
        $modal.find("#edi_tipo_usuario").toggle(!!row);
        $form.attr({"data-form":row?"update":"save",action:""}).removeClass("FormularioAjax");
        $modal.modal("show");
        setTimeout(function(){$form.find("#tipo_usuario_nombre").trigger("focus");},100);
    }

    function apOpenPermissionsModal(row) {
        if(!row)return;
        const $modal=apPrepareExternalModal("#modal_permisos");
        if(!$modal.length)return;
        const $form=$modal.find("#formPermisos");
        if($form.length&&$form[0])$form[0].reset();
        $form.find("#permisos_tipo_user_id").val(row.tipo_user_id);
        $form.find("#permisos_nombre").val(row.nombre);
        $modal.find(".modal-title").html('<i class="fas fa-key mr-2"></i>Permisos · '+limpiarHtml(row.nombre));
        const perms=apAdminState.permissionsByType[String(row.tipo_user_id)]||apAdminState.permissionsByType[row.tipo_user_id]||{};
        AP_PERMISSION_DEFINITIONS.forEach(function(p){$form.find("#opcion_"+p.key).prop("checked",parseInt(perms[p.key],10)===1);});
        $form.attr({"data-form":"save",action:""}).removeClass("FormularioAjax");
        $modal.modal("show");
    }

    function apAccessMatrixRowsToData(level, rows) {
        return (rows||[]).map(function(r,index){
            return {
                idx:index+1,
                padre:r.padre||"",
                nombre:r.nombre||"",
                asignado:parseInt(r.asignado,10)===1,
                id:r.id
            };
        });
    }

    function apInitAccessMatrixTable(selector, level, rows, privilege) {
        const $table=$(selector).first();
        if(!$table.length)return;
        if($.fn.DataTable && $.fn.DataTable.isDataTable($table[0])){$table.DataTable().clear().destroy();}
        const data=apAccessMatrixRowsToData(level,rows);
        if($.fn.DataTable){
            const cols=[];
            if(level==="menu"){
                cols.push({title:"#",data:"idx"},{title:"Menú",data:"nombre"});
            }else if(level==="submenu"){
                cols.push({title:"#",data:"idx"},{title:"Menú",data:"padre"},{title:"Submenú",data:"nombre"});
            }else{
                cols.push({title:"#",data:"idx"},{title:"Submenú",data:"padre"},{title:"Submenú Nivel 2",data:"nombre"});
            }
            cols.push({title:"Estado",data:null,render:function(d,t,r){return r.asignado?'<span class="badge badge-success">Asignado</span>':'<span class="badge badge-secondary">No asignado</span>';}},
                      {title:"Acciones",data:null,orderable:false,render:function(d,t,r){return '<button type="button" class="btn btn-sm '+(r.asignado?'btn-danger':'btn-success')+' ap-toggle-access" data-level="'+level+'" data-item-id="'+r.id+'" data-privilege-id="'+privilege.privilegio_id+'" data-next="'+(r.asignado?0:1)+'"><i class="fas '+(r.asignado?'fa-times':'fa-plus')+' mr-1"></i>'+(r.asignado?'Quitar':'Asignar')+'</button>';}});
            $table.DataTable({destroy:true,data:data,columns:cols,pageLength:10,lengthMenu:[[10,20,50,-1],[10,20,50,"Todos"]],language:typeof idioma_español!=="undefined"?idioma_español:{},autoWidth:false,responsive:true,dom:"<'row mb-2'<'col-sm-6'l><'col-sm-6'f>>rt<'row mt-2'<'col-sm-5'i><'col-sm-7'p>>"});
        }else{
            let h='<thead><tr><th>#</th>'+(level!=="menu"?'<th>Padre</th>':'')+'<th>'+(level==="menu"?'Menú':level==="submenu"?'Submenú':'Submenú Nivel 2')+'</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
            data.forEach(function(r){h+='<tr><td>'+r.idx+'</td>'+(level!=="menu"?'<td>'+limpiarHtml(r.padre)+'</td>':'')+'<td>'+limpiarHtml(r.nombre)+'</td><td>'+(r.asignado?'Asignado':'No asignado')+'</td><td><button type="button" class="btn btn-sm '+(r.asignado?'btn-danger':'btn-success')+' ap-toggle-access" data-level="'+level+'" data-item-id="'+r.id+'" data-privilege-id="'+privilege.privilegio_id+'" data-next="'+(r.asignado?0:1)+'">'+(r.asignado?'Quitar':'Asignar')+'</button></td></tr>';});
            $table.html(h+'</tbody>');
        }
    }

    function apOpenPrivilegeMatrix(row, level) {
        if(!row)return;
        const map={
            menu:{modal:"#modal_registrar_menuaccesos",table:"#dataTableMenuAccesos",title:"Menús"},
            submenu:{modal:"#modal_registrar_submenuaccesos",table:"#dataTableSubMenuAccesos",title:"Submenús nivel 1"},
            submenu1:{modal:"#modal_registrar_submenu1accesos",table:"#dataTableSubMenu1Accesos",title:"Submenús nivel 2"}
        };
        const cfg=map[level];if(!cfg)return;
        apAccessRequest("privilege_matrix",{privilegio_id:row.privilegio_id})
            .done(function(resp){
                if(!resp||!resp.success){apNotify("error","No se pudieron cargar los accesos",resp&&resp.message?resp.message:"Error consultando la navegación.");return;}
                const $modal=apPrepareExternalModal(cfg.modal);
                if(!$modal.length)return;
                $modal.find(".modal-title").html('<i class="fas fa-list mr-2"></i>'+cfg.title+' · '+limpiarHtml(row.nombre));
                $modal.find("input[name='privilegio_id_accesos'],#privilegio_id_accesos").val(row.privilegio_id);
                const data=level==="menu"?(resp.data.menus||[]):level==="submenu"?(resp.data.submenus||[]):(resp.data.submenu1||[]);
                apInitAccessMatrixTable(cfg.table,level,data,row);
                $modal.modal("show");
            })
            .fail(function(xhr){apAjaxError(xhr,"No se pudieron cargar los accesos del privilegio");});
    }

    function apSavePrivilegeForm($form) {
        const id=parseInt($form.find("#privilegio_id_").val(),10)||0;
        const nombre=String($form.find("#privilegios_nombre").val()||"").trim();
        const estado=$form.find("#privilegio_activo").is(":checked")?1:0;
        if(!nombre){apNotify("warning","Nombre requerido","Ingrese el nombre del privilegio.");return;}
        apAccessRequest("save_privilege",{privilegio_id:id,nombre:nombre,estado:estado})
            .done(function(resp){
                if(!resp||!resp.success){apNotify("error","No se pudo guardar",resp&&resp.message?resp.message:"No se pudo guardar el privilegio.");return;}
                $("#modal_registrar_privilegios").modal("hide");
                apNotify("success","Privilegio guardado",resp.message||"Privilegio guardado correctamente.");
                apLoadAccessData(false);
                apLoadAdmin();
            }).fail(function(xhr){apAjaxError(xhr,"No se pudo guardar el privilegio");});
    }

    function apSaveTypeForm($form) {
        const id=parseInt($form.find("#tipo_user_id").val(),10)||0;
        const nombre=String($form.find("#tipo_usuario_nombre").val()||"").trim();
        const estado=$form.find("#tipo_usuario_activo").is(":checked")?1:0;
        if(!nombre){apNotify("warning","Nombre requerido","Ingrese el nombre del tipo de usuario.");return;}
        apAccessRequest("save_type_user",{tipo_user_id:id,nombre:nombre,estado:estado})
            .done(function(resp){
                if(!resp||!resp.success){apNotify("error","No se pudo guardar",resp&&resp.message?resp.message:"No se pudo guardar el tipo de usuario.");return;}
                $("#modal_registrar_tipoUsuario").modal("hide");
                apNotify("success","Tipo de usuario guardado",resp.message||"Tipo de usuario guardado correctamente.");
                apLoadAccessData(false);
                apLoadAdmin();
            }).fail(function(xhr){apAjaxError(xhr,"No se pudo guardar el tipo de usuario");});
    }

    function apSavePermissionsForm($form) {
        const id=parseInt($form.find("#permisos_tipo_user_id").val(),10)||0;
        if(!id)return apNotify("error","Tipo de usuario no disponible","No se pudo identificar el tipo de usuario.");
        const perms={};
        AP_PERMISSION_DEFINITIONS.forEach(function(p){perms[p.key]=$form.find("#opcion_"+p.key).is(":checked")?1:0;});
        apAccessRequest("save_permissions",{tipo_user_id:id,permissions:JSON.stringify(perms)})
            .done(function(resp){
                if(!resp||!resp.success){apNotify("error","No se pudieron guardar los permisos",resp&&resp.message?resp.message:"Error guardando permisos.");return;}
                $("#modal_permisos").modal("hide");
                apNotify("success","Permisos actualizados",resp.message||"Permisos guardados correctamente.");
                apLoadAccessData(false);
            }).fail(function(xhr){apAjaxError(xhr,"No se pudieron guardar los permisos");});
    }

    function apAccessExportRows(kind) {
        if(kind==="privileges"){
            return apFilteredPrivileges().map(function(r){return [r.nombre||"",parseInt(r.menus_asignados||0,10),parseInt(r.submenus_asignados||0,10),parseInt(r.submenus1_asignados||0,10),parseInt(r.usuarios_asignados||0,10),parseInt(r.estado,10)===1?"Activo":"Inactivo"];});
        }
        return apFilteredTypes().map(function(r){
            const perms=apAdminState.permissionsByType[String(r.tipo_user_id)]||apAdminState.permissionsByType[r.tipo_user_id]||{};
            const enabled=AP_PERMISSION_DEFINITIONS.filter(function(p){return parseInt(perms[p.key],10)===1;}).map(function(p){return p.label;}).join(", ");
            return [r.nombre||"",enabled||"Sin permisos activos",parseInt(r.permisos_activos||0,10),parseInt(r.usuarios_asignados||0,10),r.tipo_user_id||"",parseInt(r.estado,10)===1?"Activo":"Inactivo"];
        });
    }

    function apExportPrivilegesExcel() {
        const rows=apAccessExportRows("privileges");if(!rows.length)return apNotify("warning","Sin datos","No hay privilegios para exportar.");
        const c=(apAdminState.data||{}).cliente||{},active=rows.filter(function(r){return r[5]==="Activo";}).length;
        const p=apGenericXlsx({title:"IZZY • REPORTE DE PRIVILEGIOS",subtitle:(c.nombre||"Cliente")+" • Navegación y accesos",headers:["Privilegio","Menús","Submenús","Nivel 2","Usuarios","Estado"],rows:rows,activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:"Estado: "+(apAdminState.privilegeStatus==="1"?"Activos":apAdminState.privilegeStatus==="0"?"Inactivos":"Todos")+" • Vista: "+(apAdminState.privilegeView==="mini"?"Miniatura":"Detalle"),sheetName:"Privilegios",widths:[30,14,16,14,14,14]});
        if(!p)return apNotify("error","Excel no disponible","JSZip no está disponible.");
        p.then(function(blob){asignacionDescargarBlob(blob,"Privilegios_"+apReportFileSafe(c.nombre)+".xlsx","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",true);}).catch(function(e){console.error(e);apNotify("error","Error al generar Excel","No se pudo generar el reporte de privilegios.");});
    }

    function apExportTypesExcel() {
        const rows=apAccessExportRows("types");if(!rows.length)return apNotify("warning","Sin datos","No hay tipos de usuario para exportar.");
        const c=(apAdminState.data||{}).cliente||{},active=rows.filter(function(r){return r[5]==="Activo";}).length;
        const p=apGenericXlsx({title:"IZZY • REPORTE DE TIPOS DE USUARIO",subtitle:(c.nombre||"Cliente")+" • Permisos de operación",headers:["Tipo de usuario","Permisos activos","Cantidad","Usuarios","ID","Estado"],rows:rows,activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:"Estado: "+(apAdminState.typeStatus==="1"?"Activos":apAdminState.typeStatus==="0"?"Inactivos":"Todos")+" • Vista: "+(apAdminState.typeView==="mini"?"Miniatura":"Detalle"),sheetName:"Permisos",widths:[28,56,14,14,10,14]});
        if(!p)return apNotify("error","Excel no disponible","JSZip no está disponible.");
        p.then(function(blob){asignacionDescargarBlob(blob,"Tipos_Usuario_"+apReportFileSafe(c.nombre)+".xlsx","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",true);}).catch(function(e){console.error(e);apNotify("error","Error al generar Excel","No se pudo generar el reporte de tipos de usuario.");});
    }

    function apExportPrivilegesPdf() {
        const rows=apAccessExportRows("privileges");if(!rows.length)return apNotify("warning","Sin datos","No hay privilegios para exportar.");
        const c=(apAdminState.data||{}).cliente||{},active=rows.filter(function(r){return r[5]==="Activo";}).length;
        apGenericPdf({title:"PRIVILEGIOS Y NAVEGACIÓN",subtitle:"Menús, submenús y accesos por privilegio",headers:["PRIVILEGIO","MENÚS","SUBMENÚS","NIVEL 2","USUARIOS","ESTADO"],rows:rows,activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:"Estado: "+(apAdminState.privilegeStatus==="1"?"Activos":apAdminState.privilegeStatus==="0"?"Inactivos":"Todos"),pdfWidths:[150,70,80,70,70,70],viewLabel:apAdminState.privilegeView==="mini"?"Miniatura":"Detalle",footer:"Privilegios",viewerTitle:"Privilegios del cliente",fileName:"Privilegios_"+apReportFileSafe(c.nombre)+".pdf"});
    }

    function apExportTypesPdf() {
        const rows=apAccessExportRows("types");if(!rows.length)return apNotify("warning","Sin datos","No hay tipos de usuario para exportar.");
        const c=(apAdminState.data||{}).cliente||{},active=rows.filter(function(r){return r[5]==="Activo";}).length;
        apGenericPdf({title:"TIPOS DE USUARIO Y PERMISOS",subtitle:"Permisos operativos configurados por perfil",headers:["TIPO DE USUARIO","PERMISOS ACTIVOS","CANTIDAD","USUARIOS","ID","ESTADO"],rows:rows,activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:"Estado: "+(apAdminState.typeStatus==="1"?"Activos":apAdminState.typeStatus==="0"?"Inactivos":"Todos"),pdfWidths:[115,320,60,60,45,70],viewLabel:apAdminState.typeView==="mini"?"Miniatura":"Detalle",footer:"Tipos de Usuario y Permisos",viewerTitle:"Tipos de usuario y permisos",fileName:"Tipos_Usuario_"+apReportFileSafe(c.nombre)+".pdf"});
    }

    $(document)
        .off("click.apAccessTabs",".ap-access-subtab")
        .on("click.apAccessTabs",".ap-access-subtab",function(){
            const tab=String($(this).data("ap-access-tab")||"directory");
            apAdminState.accessTab=tab;
            $(".ap-access-subtab").removeClass("active").filter('[data-ap-access-tab="'+tab+'"]').addClass("active");
            $(".ap-access-subpanel").removeClass("active");
            $("#ap_access_"+tab).addClass("active");
            if(tab!=="directory" && !apAdminState.accessLoaded)apLoadAccessData(true);
        })
        .off("click.apPrivStatus","#ap_privilege_status_filter [data-privilege-status]")
        .on("click.apPrivStatus","#ap_privilege_status_filter [data-privilege-status]",function(){
            apAdminState.privilegeStatus=String($(this).data("privilege-status"));
            $("#ap_privilege_status_filter button").removeClass("active");$(this).addClass("active");apRenderPrivileges();
        })
        .off("click.apTypeStatus","#ap_type_status_filter [data-type-status]")
        .on("click.apTypeStatus","#ap_type_status_filter [data-type-status]",function(){
            apAdminState.typeStatus=String($(this).data("type-status"));
            $("#ap_type_status_filter button").removeClass("active");$(this).addClass("active");apRenderTypes();
        })
        .off("click.apPrivView","[data-privilege-view]")
        .on("click.apPrivView","[data-privilege-view]",function(){
            apAdminState.privilegeView=String($(this).data("privilege-view"));$("#ap_privilege_view_switch button").removeClass("active");$(this).addClass("active");apRenderPrivileges();
        })
        .off("click.apTypeView","[data-type-view]")
        .on("click.apTypeView","[data-type-view]",function(){
            apAdminState.typeView=String($(this).data("type-view"));$("#ap_type_view_switch button").removeClass("active");$(this).addClass("active");apRenderTypes();
        })
        .off("input.apPrivSearch","#ap_privilege_search")
        .on("input.apPrivSearch","#ap_privilege_search",function(){apAdminState.privilegeSearch=$(this).val()||"";apRenderPrivileges();})
        .off("input.apTypeSearch","#ap_type_search")
        .on("input.apTypeSearch","#ap_type_search",function(){apAdminState.typeSearch=$(this).val()||"";apRenderTypes();})
        .off("click.apNewPrivilege","#ap_btn_nuevo_privilegio")
        .on("click.apNewPrivilege","#ap_btn_nuevo_privilegio",function(){apOpenPrivilegeModal(null);})
        .off("click.apNewType","#ap_btn_nuevo_tipo_usuario")
        .on("click.apNewType","#ap_btn_nuevo_tipo_usuario",function(){apOpenTypeModal(null);})
        .off("click.apEditPrivilege",".ap-edit-privilege")
        .on("click.apEditPrivilege",".ap-edit-privilege",function(){apOpenPrivilegeModal(apFindPrivilege($(this).data("id")));})
        .off("click.apEditType",".ap-edit-type")
        .on("click.apEditType",".ap-edit-type",function(){apOpenTypeModal(apFindType($(this).data("id")));})
        .off("click.apPerms",".ap-configure-permissions")
        .on("click.apPerms",".ap-configure-permissions",function(){apOpenPermissionsModal(apFindType($(this).data("id")));})
        .off("click.apPrivMenus",".ap-priv-menus")
        .on("click.apPrivMenus",".ap-priv-menus",function(){apOpenPrivilegeMatrix(apFindPrivilege($(this).data("id")),"menu");})
        .off("click.apPrivSub",".ap-priv-submenus")
        .on("click.apPrivSub",".ap-priv-submenus",function(){apOpenPrivilegeMatrix(apFindPrivilege($(this).data("id")),"submenu");})
        .off("click.apPrivSub1",".ap-priv-submenus1")
        .on("click.apPrivSub1",".ap-priv-submenus1",function(){apOpenPrivilegeMatrix(apFindPrivilege($(this).data("id")),"submenu1");})
        .off("click.apDeletePrivilege",".ap-delete-privilege")
        .on("click.apDeletePrivilege",".ap-delete-privilege",function(){
            const row=apFindPrivilege($(this).data("id"));if(!row)return;
            apConfirm("Eliminar privilegio","Se eliminará "+row.nombre+". Esta acción solo está disponible si no tiene usuarios asociados.",function(ok){
                if(!ok)return;apAccessRequest("delete_privilege",{privilegio_id:row.privilegio_id}).done(function(resp){if(resp&&resp.success){apNotify("success","Privilegio eliminado",resp.message);apLoadAdmin();}else apNotify("error","No se pudo eliminar",resp&&resp.message?resp.message:"Error.");}).fail(function(xhr){apAjaxError(xhr,"No se pudo eliminar el privilegio");});
            });
        })
        .off("click.apDeleteType",".ap-delete-type")
        .on("click.apDeleteType",".ap-delete-type",function(){
            const row=apFindType($(this).data("id"));if(!row)return;
            apConfirm("Eliminar tipo de usuario","Se eliminará "+row.nombre+" y sus permisos. Solo es posible si no tiene usuarios asociados.",function(ok){
                if(!ok)return;apAccessRequest("delete_type_user",{tipo_user_id:row.tipo_user_id}).done(function(resp){if(resp&&resp.success){apNotify("success","Tipo de usuario eliminado",resp.message);apLoadAdmin();}else apNotify("error","No se pudo eliminar",resp&&resp.message?resp.message:"Error.");}).fail(function(xhr){apAjaxError(xhr,"No se pudo eliminar el tipo de usuario");});
            });
        })
        .off("click.apToggleAccess",".ap-toggle-access")
        .on("click.apToggleAccess",".ap-toggle-access",function(){
            const $b=$(this),level=$b.data("level"),item=$b.data("item-id"),priv=$b.data("privilege-id"),next=parseInt($b.data("next"),10)||0;
            $b.prop("disabled",true);
            apAccessRequest("toggle_privilege_access",{level:level,item_id:item,privilegio_id:priv,assigned:next}).done(function(resp){
                if(!resp||!resp.success){apNotify("error","No se pudo actualizar el acceso",resp&&resp.message?resp.message:"Error.");return;}
                const row=apFindPrivilege(priv);apOpenPrivilegeMatrix(row,level);apLoadAccessData(false);
            }).fail(function(xhr){apAjaxError(xhr,"No se pudo actualizar el acceso");}).always(function(){$b.prop("disabled",false);});
        })
        .off("click.apRefreshPrivileges","#ap_btn_actualizar_privilegios")
        .on("click.apRefreshPrivileges","#ap_btn_actualizar_privilegios",function(){apLoadAccessData(true);})
        .off("click.apRefreshTypes","#ap_btn_actualizar_tipos")
        .on("click.apRefreshTypes","#ap_btn_actualizar_tipos",function(){apLoadAccessData(true);})
        .off("click.apExcelPrivileges","#ap_btn_excel_privilegios")
        .on("click.apExcelPrivileges","#ap_btn_excel_privilegios",apExportPrivilegesExcel)
        .off("click.apPdfPrivileges","#ap_btn_pdf_privilegios")
        .on("click.apPdfPrivileges","#ap_btn_pdf_privilegios",apExportPrivilegesPdf)
        .off("click.apExcelTypes","#ap_btn_excel_tipos")
        .on("click.apExcelTypes","#ap_btn_excel_tipos",apExportTypesExcel)
        .off("click.apPdfTypes","#ap_btn_pdf_tipos")
        .on("click.apPdfTypes","#ap_btn_pdf_tipos",apExportTypesPdf);

    $(document)
        .off("submit.apPrivilegeForm","#formPrivilegios")
        .on("submit.apPrivilegeForm","#formPrivilegios",function(e){e.preventDefault();e.stopImmediatePropagation();apSavePrivilegeForm($(this));return false;})
        .off("submit.apTypeForm","#formTipoUsuario")
        .on("submit.apTypeForm","#formTipoUsuario",function(e){e.preventDefault();e.stopImmediatePropagation();apSaveTypeForm($(this));return false;})
        .off("submit.apPermissionForm","#formPermisos")
        .on("submit.apPermissionForm","#formPermisos",function(e){e.preventDefault();e.stopImmediatePropagation();apSavePermissionsForm($(this));return false;});

    $(document)
        .off("change.apPrivilegeToggle","#privilegio_activo")
        .on("change.apPrivilegeToggle","#privilegio_activo",function(){$("#modal_registrar_privilegios #label_privilegio_activo").text($(this).is(":checked")?"Privilegio Activo":"Privilegio Inactivo");})
        .off("change.apTypeToggle","#tipo_usuario_activo")
        .on("change.apTypeToggle","#tipo_usuario_activo",function(){$("#modal_registrar_tipoUsuario #label_tipo_usuario_activo").text($(this).is(":checked")?"Tipo de Usuario Activo":"Tipo de Usuario Inactivo");});


    function apReportFileSafe(value) {
        return String(value || "Cliente").replace(/[^a-zA-Z0-9_-]+/g, "_").replace(/^_+|_+$/g, "") || "Cliente";
    }

    function apGenericXlsx(config) {
        if (typeof JSZip === "undefined") return null;
        const headers = config.headers || [];
        const rows = config.rows || [];
        const lastCol = asignacionExcelColName(Math.max(0, headers.length - 1));
        const headerRow = 7, firstDataRow = 8, lastRow = Math.max(headerRow, headerRow + rows.length);
        const sheetRows = [];
        sheetRows.push('<row r="1" ht="30" customHeight="1">'+asignacionExcelCell('A1',config.title,1,false)+'</row>');
        sheetRows.push('<row r="2" ht="20" customHeight="1">'+asignacionExcelCell('A2',config.subtitle+' • Generado: '+new Date().toLocaleDateString('es-HN'),2,false)+'</row>');
        sheetRows.push('<row r="3" ht="18" customHeight="1">'+asignacionExcelCell('A3','REGISTROS',6,false)+asignacionExcelCell('C3','ACTIVOS',6,false)+asignacionExcelCell('E3','INACTIVOS',6,false)+'</row>');
        sheetRows.push('<row r="4" ht="26" customHeight="1">'+asignacionExcelCell('A4',rows.length,7,true)+asignacionExcelCell('C4',config.activeCount,7,true)+asignacionExcelCell('E4',config.inactiveCount,7,true)+'</row>');
        sheetRows.push('<row r="5"></row>');
        sheetRows.push('<row r="6" ht="18" customHeight="1">'+asignacionExcelCell('A6',config.filterText,8,false)+'</row>');
        sheetRows.push('<row r="7" ht="26" customHeight="1">'+headers.map(function(h,i){return asignacionExcelCell(asignacionExcelColName(i)+'7',h,3,false);}).join('')+'</row>');
        rows.forEach(function(row,ri){
            const er=firstDataRow+ri;
            sheetRows.push('<row r="'+er+'" ht="22" customHeight="1">'+row.map(function(v,ci){
                let style=4;
                if (ci === config.statusColumn) style=String(v||'').toLowerCase().indexOf('activ')===0?9:10;
                return asignacionExcelCell(asignacionExcelColName(ci)+er,v,style,false);
            }).join('')+'</row>');
        });
        const widths=(config.widths||headers.map(function(){return 20;})).map(function(w,i){return '<col min="'+(i+1)+'" max="'+(i+1)+'" width="'+w+'" customWidth="1"/>';}).join('');
        const mergeCount = 8;
        const sheetXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'+lastCol+lastRow+'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A8" sqref="A8"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/><cols>'+widths+'</cols><sheetData>'+sheetRows.join('')+'</sheetData><autoFilter ref="A7:'+lastCol+lastRow+'"/><mergeCells count="'+mergeCount+'"><mergeCell ref="A1:'+lastCol+'1"/><mergeCell ref="A2:'+lastCol+'2"/><mergeCell ref="A3:B3"/><mergeCell ref="A4:B4"/><mergeCell ref="C3:D3"/><mergeCell ref="C4:D4"/><mergeCell ref="E3:'+lastCol+'3"/><mergeCell ref="E4:'+lastCol+'4"/></mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="1" fitToWidth="1" fitToHeight="0"/></worksheet>';
        const stylesXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?>'+
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="7"><font><sz val="10"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="9"/><color rgb="FF5E6C84"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF172B4D"/><name val="Calibri"/></font><font><b/><sz val="8"/><color rgb="FF6B778C"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="FF172B4D"/><name val="Calibri"/></font></fonts><fills count="7"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF172B4D"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0EA5A8"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE3FCEF"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFEBE6"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFDDE3EA"/></left><right style="thin"><color rgb="FFDDE3EA"/></right><top style="thin"><color rgb="FFDDE3EA"/></top><bottom style="thin"><color rgb="FFDDE3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="6" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="4" fillId="6" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        const workbookXml='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets><sheet name="'+asignacionXmlEscape(config.sheetName)+'" sheetId="1" r:id="rId1"/></sheets></workbook>';
        const workbookRels='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        const rootRels='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        const contentTypes='<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        const zip=new JSZip(); zip.file('[Content_Types].xml',contentTypes); zip.folder('_rels').file('.rels',rootRels); zip.folder('xl').file('workbook.xml',workbookXml); zip.folder('xl').file('styles.xml',stylesXml); zip.folder('xl').folder('_rels').file('workbook.xml.rels',workbookRels); zip.folder('xl').folder('worksheets').file('sheet1.xml',sheetXml);
        const opts={type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',compression:'DEFLATE'};
        if(typeof zip.generateAsync==='function') return zip.generateAsync(opts);
        if(typeof zip.generate==='function'){try{return Promise.resolve(zip.generate(opts));}catch(e){return Promise.reject(e);}}
        return Promise.reject(new Error('JSZip no soporta la generación XLSX.'));
    }

    function apExportCompaniesExcel() {
        const rows=apFilteredCompanies();
        if(!rows.length){apNotify('warning','Sin datos','No hay empresas para exportar con el filtro actual.');return;}
        const active=rows.filter(function(e){return parseInt(e.estado,10)===1;}).length;
        const c=(apAdminState.data||{}).cliente||{};
        const data=rows.map(function(e){return [e.nombre||'',e.razon_social||'',e.rtn||'',e.correo||'',e.telefono||'',parseInt(e.estado,10)===1?'Activa':'Inactiva'];});
        const promise=apGenericXlsx({title:'IZZY • REPORTE DE EMPRESAS',subtitle:(c.nombre||'Cliente')+' • Empresas del cliente',headers:['Empresa','Razón social','RTN','Correo','Teléfono','Estado'],rows:data,activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:'Estado: '+(apAdminState.companyStatus==='1'?'Activas':apAdminState.companyStatus==='0'?'Inactivas':'Todas')+' • Vista: '+(apAdminState.companyView==='mini'?'Miniatura':'Detalle'),sheetName:'Empresas',widths:[26,32,20,34,18,14]});
        if(!promise){apNotify('error','Excel no disponible','JSZip no está disponible.');return;}
        promise.then(function(blob){asignacionDescargarBlob(blob,'Empresas_'+apReportFileSafe(c.nombre)+'.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',true);}).catch(function(e){console.error(e);apNotify('error','Error al generar Excel','No se pudo generar el reporte de empresas.');});
    }

    function apExportSequencesExcel() {
        const rows=apFilteredSequences();
        if(!rows.length){apNotify('warning','Sin datos','No hay secuencias para exportar con el filtro actual.');return;}
        const active=rows.filter(apSequenceStatus).length;
        const c=(apAdminState.data||{}).cliente||{};
        const data=rows.map(function(r){return [r.documento||'',r.empresa||'',r.cai||'Sin CAI',apFormatSequenceNumber(r),String(r.rango_inicial||'')+' - '+String(r.rango_final||''),(r.fecha_activacion_display||r.fecha_activacion||'')+' - '+(r.fecha_limite_display||r.fecha_limite||''),apSequenceStatus(r)?'Activa':'Inactiva'];});
        const promise=apGenericXlsx({title:'IZZY • REPORTE DE SECUENCIAS',subtitle:(c.nombre||'Cliente')+' • Secuencias de facturación',headers:['Documento','Empresa','CAI','Siguiente','Rango','Vigencia','Estado'],rows:data,activeCount:active,inactiveCount:rows.length-active,statusColumn:6,filterText:'Estado: '+(apBillingState.sequenceStatus==='1'?'Activas':apBillingState.sequenceStatus==='0'?'Inactivas':'Todas')+' • Vista: '+(apBillingState.sequenceView==='mini'?'Miniatura':'Detalle'),sheetName:'Secuencias',widths:[26,26,38,22,24,28,14]});
        if(!promise){apNotify('error','Excel no disponible','JSZip no está disponible.');return;}
        promise.then(function(blob){asignacionDescargarBlob(blob,'Secuencias_'+apReportFileSafe(c.nombre)+'.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',true);}).catch(function(e){console.error(e);apNotify('error','Error al generar Excel','No se pudo generar el reporte de secuencias.');});
    }

    function apGenericPdf(config) {
        if(typeof pdfMake==='undefined'||!pdfMake.createPdf){apNotify('warning','PDF no disponible','La librería PDF no está cargada.');return;}
        if (!(typeof imagen !== 'undefined' && typeof imagen === 'string' && imagen.indexOf('data:image/')===0)) {
            asignacionObtenerLogoPdf(function(logoDataUrl){try{imagen=logoDataUrl;}catch(e){} apGenericPdf(config);}); return;
        }
        const c=(apAdminState.data||{}).cliente||{};
        const body=[[].concat(config.headers.map(function(h){return{text:h,style:'th',fillColor:'#17324D'};}))];
        config.rows.forEach(function(row,i){const fill=i%2===0?'#FFFFFF':'#F7F9FC';body.push(row.map(function(v,ci){const o={text:v||'—',fillColor:fill};if(ci===config.statusColumn){o.color=String(v||'').toLowerCase().indexOf('activ')===0?'#14804A':'#C9372C';o.bold=true;o.alignment='center';}return o;}));});
        const encabezado={table:{widths:[100,'*',150],body:[[
            {border:[false,false,false,false],fillColor:'#17324D',margin:[12,10,0,10],stack:[asignacionPdfLogoPlate(imagen)]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,0,10],stack:[{text:config.title,fontSize:16,bold:true,color:'#FFFFFF'},{text:config.subtitle,fontSize:7.5,color:'#D8E5F0',margin:[0,2,0,0]}]},
            {border:[false,false,false,false],fillColor:'#17324D',margin:[0,10,12,10],stack:[{text:'REPORTE EJECUTIVO',fontSize:6.5,bold:true,color:'#72E2E5',alignment:'right'},{text:asignacionFechaReporte(),fontSize:9,bold:true,color:'#FFFFFF',alignment:'right',margin:[0,3,0,0]},{text:config.rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right',margin:[0,2,0,0]}]}
        ]]},layout:{hLineWidth:function(){return 0;},vLineWidth:function(){return 0;}},margin:[0,0,0,10]};
        const clienteInfo={table:{widths:['*'],body:[[{text:(c.nombre||'Cliente')+'   |   RTN: '+(c.rtn||'—')+'   |   Código: '+(c.codigo_cliente||'—')+'   |   Base: '+(c.db||'—'),fontSize:7.2,bold:true,color:'#17324D',margin:[10,7,10,7],fillColor:'#EEF6FC'}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,7]};
        const filtros={table:{widths:['*'],body:[[{text:config.filterText,fontSize:6.8,color:'#52627A',margin:[10,7,10,7],fillColor:'#F7F9FC'}]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,10]};
        const resumen={table:{widths:['*','*','*'],body:[[
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'REGISTROS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(config.rows.length),fontSize:13,bold:true,color:'#172B4D',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'ACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(config.activeCount),fontSize:13,bold:true,color:'#14804A',margin:[0,2,0,0]}]},
            {fillColor:'#F7F9FC',margin:[8,7,8,7],stack:[{text:'INACTIVOS',fontSize:6.3,bold:true,color:'#6B778C'},{text:String(config.inactiveCount),fontSize:13,bold:true,color:'#C9372C',margin:[0,2,0,0]}]}
        ]]},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .6;},vLineWidth:function(){return .6;}},margin:[0,0,0,12]};
        const table={table:{headerRows:1,widths:config.pdfWidths,body:body},layout:{hLineColor:function(){return '#DDE3EA';},vLineColor:function(){return '#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 5;},paddingRight:function(){return 5;},paddingTop:function(){return 6;},paddingBottom:function(){return 6;}}};
        const doc={pageSize:'LETTER',pageOrientation:'landscape',pageMargins:[28,28,28,34],header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:736,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(currentPage,pageCount){return{margin:[28,8,28,0],columns:[{text:'IZZY • '+config.footer,fontSize:7,color:'#7A869A'},{text:'Página '+currentPage+' de '+pageCount,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[encabezado,clienteInfo,filtros,resumen,{text:'VISTA '+config.viewLabel.toUpperCase(),fontSize:7,bold:true,color:'#17324D',margin:[0,1,0,7]},table],styles:{th:{fontSize:6.4,bold:true,color:'#FFFFFF',alignment:'center'}},defaultStyle:{fontSize:8,color:'#253858'}};
        const pdf=pdfMake.createPdf(doc);
        if(typeof pdf.getDataUrl==='function'){pdf.getDataUrl(function(url){apAbrirVisorPdfPublico(url,config.viewerTitle,config.fileName);});return;}
        if(typeof pdf.getBase64==='function'){pdf.getBase64(function(base64){apAbrirVisorPdfPublico('data:application/pdf;base64,'+base64,config.viewerTitle,config.fileName);});return;}
        apNotify('error','PDF no disponible','La versión actual de pdfMake no permite previsualización compatible.');
    }

    function apExportCompaniesPdf() {
        const rows=apFilteredCompanies(); if(!rows.length){apNotify('warning','Sin datos','No hay empresas para exportar con el filtro actual.');return;}
        const active=rows.filter(function(e){return parseInt(e.estado,10)===1;}).length; const c=(apAdminState.data||{}).cliente||{};
        apGenericPdf({title:'REPORTE DE EMPRESAS',subtitle:'Empresas registradas y estado operativo',headers:['EMPRESA','RAZÓN SOCIAL','RTN','CORREO','TELÉFONO','ESTADO'],rows:rows.map(function(e){return[e.nombre||'',e.razon_social||'',e.rtn||'',e.correo||'',e.telefono||'',parseInt(e.estado,10)===1?'Activa':'Inactiva'];}),activeCount:active,inactiveCount:rows.length-active,statusColumn:5,filterText:'Estado: '+(apAdminState.companyStatus==='1'?'Activas':apAdminState.companyStatus==='0'?'Inactivas':'Todas')+'   |   Vista: '+(apAdminState.companyView==='mini'?'Miniatura':'Detalle'),pdfWidths:[120,150,90,155,85,70],viewLabel:apAdminState.companyView==='mini'?'Miniatura':'Detalle',footer:'Empresas',viewerTitle:'Reporte de empresas',fileName:'Empresas_'+apReportFileSafe(c.nombre)+'.pdf'});
    }

    function apExportSequencesPdf() {
        const rows=apFilteredSequences(); if(!rows.length){apNotify('warning','Sin datos','No hay secuencias para exportar con el filtro actual.');return;}
        const active=rows.filter(apSequenceStatus).length; const c=(apAdminState.data||{}).cliente||{};
        apGenericPdf({title:'REPORTE DE SECUENCIAS',subtitle:'Secuencias de facturación configuradas para el cliente',headers:['DOCUMENTO','EMPRESA','CAI','SIGUIENTE','RANGO','VIGENCIA','ESTADO'],rows:rows.map(function(r){return[r.documento||'',r.empresa||'',r.cai||'Sin CAI',apFormatSequenceNumber(r),String(r.rango_inicial||'')+' - '+String(r.rango_final||''),(r.fecha_activacion_display||r.fecha_activacion||'')+' - '+(r.fecha_limite_display||r.fecha_limite||''),apSequenceStatus(r)?'Activa':'Inactiva'];}),activeCount:active,inactiveCount:rows.length-active,statusColumn:6,filterText:'Estado: '+(apBillingState.sequenceStatus==='1'?'Activas':apBillingState.sequenceStatus==='0'?'Inactivas':'Todas')+'   |   Vista: '+(apBillingState.sequenceView==='mini'?'Miniatura':'Detalle'),pdfWidths:[100,95,160,95,95,120,65],viewLabel:apBillingState.sequenceView==='mini'?'Miniatura':'Detalle',footer:'Secuencias de Facturación',viewerTitle:'Reporte de secuencias',fileName:'Secuencias_'+apReportFileSafe(c.nombre)+'.pdf'});
    }

    $("#ap_btn_excel").off("click.apExcel").on("click.apExcel", apExportAdminExcelPremium);
    $("#ap_btn_pdf").off("click.apPdf").on("click.apPdf", apExportAdminPdfPremium);
    $("#ap_btn_excel_empresas").off("click.apExcelCompanies").on("click.apExcelCompanies", apExportCompaniesExcel);
    $("#ap_btn_pdf_empresas").off("click.apPdfCompanies").on("click.apPdfCompanies", apExportCompaniesPdf);
    $("#ap_btn_excel_secuencias").off("click.apExcelSequences").on("click.apExcelSequences", apExportSequencesExcel);
    $("#ap_btn_pdf_secuencias").off("click.apPdfSequences").on("click.apPdfSequences", apExportSequencesPdf);


    /* =========================================================
       INICIALIZAR
       ========================================================= */
    asignacionState.view = leerTipoVista();
    actualizarBotonesVista();
    sincronizarTamanoPaginaAsignacion();
    inicializarSeccionesPersistentes();
    cargarClientes();
    cargarPlanes();
    cargarSistemas();
    cargarAsignaciones(false);

});

</script>
