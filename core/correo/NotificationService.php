<?php
// core/correo/NotificationService.php

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/sendEmail.php';

class NotificationService {
    private $mainModel;

    public function __construct() {
        $this->mainModel = new mainModel();
    }

    private function connect($dbName) {
        $dbName = trim((string)$dbName);
        if ($dbName === '') return $this->mainModel->connection();
        if (method_exists($this->mainModel, 'connectionDBLocal')) {
            return $this->mainModel->connectionDBLocal($dbName);
        }
        if (method_exists($this->mainModel, 'connectToDatabase')) {
            return $this->mainModel->connectToDatabase([
                'host' => SERVER,
                'user' => USER,
                'pass' => PASS,
                'name' => $dbName
            ]);
        }
        return null;
    }

    public function currentDbName() {
        if (isset($GLOBALS['db']) && trim((string)$GLOBALS['db']) !== '') return trim((string)$GLOBALS['db']);
        if (isset($_SESSION['db_cliente']) && trim((string)$_SESSION['db_cliente']) !== '') return trim((string)$_SESSION['db_cliente']);
        return defined('DB_MAIN') ? DB_MAIN : '';
    }

    public function mainDbName() {
        return defined('DB_MAIN') ? DB_MAIN : $this->currentDbName();
    }

    public function clientContextFromDb($dbName) {
        $result = ['cliente_nombre' => '', 'server_customers_id' => 0];
        $mainDb = $this->mainDbName();
        if ($mainDb === '' || trim((string)$dbName) === '') return $result;
        $cn = $this->connect($mainDb);
        if (!$cn) return $result;
        try {
            $stmt = $cn->prepare("SELECT sc.server_customers_id, c.nombre FROM server_customers sc LEFT JOIN clientes c ON c.clientes_id=sc.clientes_id WHERE sc.db=? LIMIT 1");
            if ($stmt) {
                $db = trim((string)$dbName);
                $stmt->bind_param('s', $db);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $result['cliente_nombre'] = trim((string)($row['nombre'] ?? ''));
                    $result['server_customers_id'] = (int)($row['server_customers_id'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            error_log('NotificationService clientContextFromDb: '.$e->getMessage());
        } finally {
            $cn->close();
        }
        return $result;
    }

    public function notificationRecipients($dbName) {
        $recipients = [];
        $cn = $this->connect($dbName);
        if (!$cn) return $recipients;
        try {
            $exists = $cn->query("SHOW TABLES LIKE 'notificaciones'");
            if (!$exists || $exists->num_rows === 0) return $recipients;
            $rs = $cn->query("SELECT correo,nombre FROM notificaciones WHERE activo=1 ORDER BY notificaciones_id ASC");
            if ($rs) {
                while ($row = $rs->fetch_assoc()) {
                    $email = strtolower(trim((string)($row['correo'] ?? '')));
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
                    $recipients[$email] = trim((string)($row['nombre'] ?? 'Notificaciones')) ?: 'Notificaciones';
                }
            }
        } catch (Throwable $e) {
            error_log('NotificationService recipients ['.$dbName.']: '.$e->getMessage());
        } finally {
            $cn->close();
        }
        return $recipients;
    }

    public function resolveCompanyId($dbName, $preferred = 0) {
        $preferred = (int)$preferred;
        $cn = $this->connect($dbName);
        if (!$cn) return $preferred > 0 ? $preferred : 0;
        try {
            if ($preferred > 0) {
                $stmt = $cn->prepare("SELECT empresa_id FROM empresa WHERE empresa_id=? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $preferred);
                    $stmt->execute();
                    $ok = $stmt->get_result()->num_rows > 0;
                    $stmt->close();
                    if ($ok) return $preferred;
                }
            }
            $rs = $cn->query("SELECT empresa_id FROM empresa WHERE estado=1 ORDER BY empresa_id ASC LIMIT 1");
            if ($rs && ($row = $rs->fetch_assoc())) return (int)$row['empresa_id'];
        } catch (Throwable $e) {
            error_log('NotificationService resolveCompanyId ['.$dbName.']: '.$e->getMessage());
        } finally {
            $cn->close();
        }
        return 0;
    }

    private function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

    private function isSensitiveKey($key) {
        $key = strtolower((string)$key);
        foreach (['password','pass','contraseña','contrasena','token','secret','clave','credencial'] as $needle) {
            if (strpos($key, $needle) !== false) return true;
        }
        return false;
    }

    private function cleanDetails(array $details, $allowSensitive) {
        $clean = [];
        foreach ($details as $label => $value) {
            if (!$allowSensitive && $this->isSensitiveKey($label)) continue;
            if (is_array($value)) $value = implode(', ', array_map('strval', $value));
            $clean[$label] = $value;
        }
        return $clean;
    }

    private function cleanChanges(array $changes, $allowSensitive) {
        $clean = [];
        foreach ($changes as $label => $value) {
            if (!$allowSensitive && $this->isSensitiveKey($label)) continue;
            $clean[$label] = $value;
        }
        return $clean;
    }

    public function renderEventBody($greeting, $summary, array $details = [], array $changes = [], $note = '') {
        $html = '';
        if (trim((string)$greeting) !== '') {
            $html .= '<p style="margin:0 0 14px;">Hola <strong>'.$this->esc($greeting).'</strong>,</p>';
        }
        $html .= '<p style="margin:0 0 18px;">'.$this->esc($summary).'</p>';

        if ($changes) {
            $html .= '<div style="margin:0 0 18px;padding:16px;background:#F7F9FC;border:1px solid #DDE3EA;border-left:4px solid #0EA5A8;border-radius:8px;">';
            $html .= '<div style="margin-bottom:10px;font-weight:800;color:#17324D;">Cambios realizados</div>';
            foreach ($changes as $label => $pair) {
                $old = is_array($pair) ? ($pair['anterior'] ?? $pair[0] ?? '') : '';
                $new = is_array($pair) ? ($pair['nuevo'] ?? $pair[1] ?? '') : $pair;
                $html .= '<div style="padding:9px 0;border-bottom:1px solid #DDE3EA;">'
                    .'<div style="font-size:12px;font-weight:800;color:#5E6C84;text-transform:uppercase;letter-spacing:.4px;">'.$this->esc($label).'</div>'
                    .'<div style="margin-top:3px;color:#253858;"><span style="color:#6B778C;">'.$this->esc($old !== '' ? $old : '—').'</span> &rarr; <strong style="color:#17324D;">'.$this->esc($new !== '' ? $new : '—').'</strong></div>'
                    .'</div>';
            }
            $html .= '</div>';
        }

        if ($details) {
            $html .= '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;border:1px solid #DDE3EA;border-collapse:collapse;background:#FFFFFF;margin:0 0 18px;">';
            foreach ($details as $label => $value) {
                $html .= '<tr><td style="width:38%;padding:10px 12px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;color:#5E6C84;font-size:13px;font-weight:800;vertical-align:top;">'.$this->esc($label).'</td>'
                    .'<td style="padding:10px 12px;border-bottom:1px solid #DDE3EA;color:#253858;font-size:14px;vertical-align:top;overflow-wrap:anywhere;word-break:break-word;">'.$this->esc($value).'</td></tr>';
            }
            $html .= '</table>';
        }

        if (trim((string)$note) !== '') {
            $html .= '<div style="padding:13px 14px;background:#F7F9FC;border-left:4px solid #17324D;color:#44546A;font-size:13px;line-height:1.55;">'.$this->esc($note).'</div>';
        }
        return $html;
    }

    public function sendToRecipients($dbName, $empresaId, array $recipients, $subject, $summary, array $details = [], array $changes = [], $type = 'info', $greeting = '', $allowSensitive = false, $note = '') {
        if (!$recipients) return ['sent'=>false,'count'=>0,'message'=>'Sin destinatarios válidos.'];
        $details = $this->cleanDetails($details, $allowSensitive);
        $changes = $this->cleanChanges($changes, $allowSensitive);
        $body = $this->renderEventBody($greeting, $summary, $details, $changes, $note);
        try {
            /*
             * IMPORTANTE:
             * $dbName indica de qué base se obtienen los DESTINATARIOS y el
             * contexto de la notificación. No debe cambiar la configuración
             * global de envío de sendEmail.
             *
             * Asignación de Planes se ejecuta desde la base administrativa y
             * utiliza la configuración de correo ya activa en ese contexto.
             * Así podemos notificar a los correos de `notificaciones` de una
             * DB cliente sin exigir que esa DB tenga su propia fila en `correo`
             * y sin modificar el comportamiento general de sendEmail.
             */
            $sender = new sendEmail();

            // El branding/remitente debe corresponder al contexto desde el que
            // se está ejecutando el módulo, no al ID numérico de otra DB.
            $companyId = isset($_SESSION['empresa_id_sd'])
                ? (int)$_SESSION['empresa_id_sd']
                : 0;

            ob_start();
            $sent = $sender->enviarCorreo($recipients, [], $subject, $body, 1, $companyId, [], $type);
            $out = trim((string)ob_get_clean());
            if ((int)$sent !== 1 && $out !== '') error_log('NotificationService mail ['.$dbName.']: '.strip_tags($out));
            return ['sent'=>(int)$sent===1,'count'=>count($recipients),'message'=>$out];
        } catch (Throwable $e) {
            if (ob_get_level() > 0) @ob_end_clean();
            error_log('NotificationService send ['.$dbName.']: '.$e->getMessage());
            return ['sent'=>false,'count'=>count($recipients),'message'=>$e->getMessage()];
        }
    }

    public function notifyAdmins($dbName, $empresaId, $subject, $summary, array $details = [], array $changes = [], $type = 'audit', $note = '') {
        return $this->sendToRecipients($dbName, $empresaId, $this->notificationRecipients($dbName), $subject, $summary, $details, $changes, $type, 'Equipo administrativo', false, $note);
    }

    public function notifyUser($dbName, $empresaId, $email, $name, $subject, $summary, array $details = [], array $changes = [], $type = 'info', $allowSensitive = false, $note = '') {
        $email = strtolower(trim((string)$email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['sent'=>false,'count'=>0,'message'=>'Correo de usuario inválido.'];
        return $this->sendToRecipients($dbName, $empresaId, [$email => ($name ?: 'Usuario IZZY')], $subject, $summary, $details, $changes, $type, $name ?: 'Usuario', $allowSensitive, $note);
    }

    public function notifyClientAndMain($clientDb, $clientEmpresaId, $clientName, $subjectClient, $summaryClient, array $detailsClient = [], array $changesClient = [], $subjectMain = '', $summaryMain = '', array $detailsMain = [], array $changesMain = [], $type = 'audit') {
        $clientDb = trim((string)$clientDb);
        $mainDb = $this->mainDbName();

        if ($subjectMain === '') $subjectMain = $subjectClient;
        if ($summaryMain === '') $summaryMain = $summaryClient;
        if (!$detailsMain) $detailsMain = $detailsClient;

        // Cuando la operación ya ocurre en DB_MAIN no se debe enviar el mismo
        // evento dos veces a la misma lista de notificaciones.
        if ($clientDb === '' || strcasecmp($clientDb, $mainDb) === 0) {
            $main = $this->notifyAdmins(
                $mainDb,
                $clientEmpresaId,
                $subjectMain,
                $summaryMain,
                $detailsMain,
                $changesMain ?: $changesClient,
                $type === 'security' ? 'security' : 'audit'
            );
            return ['client'=>$main, 'main'=>$main];
        }

        $client = $this->notifyAdmins($clientDb, $clientEmpresaId, $subjectClient, $summaryClient, $detailsClient, $changesClient, $type);
        if ($clientName !== '') $detailsMain = ['Cliente'=>$clientName] + $detailsMain;
        $main = $this->notifyAdmins($mainDb, 0, $subjectMain, $summaryMain, $detailsMain, $changesMain ?: $changesClient, 'audit');
        return ['client'=>$client,'main'=>$main];
    }

    public function notifyAffectedUsersByField($dbName, $empresaId, $field, $id, $subject, $summary, array $changes = []) {
        if (!in_array($field, ['privilegio_id','tipo_user_id'], true)) return ['sent'=>0,'failed'=>0];
        $cn = $this->connect($dbName);
        if (!$cn) return ['sent'=>0,'failed'=>0];
        $users = [];
        try {
            $sql = "SELECT u.email, COALESCE(c.nombre,u.email) nombre, u.empresa_id FROM users u LEFT JOIN colaboradores c ON c.colaboradores_id=u.colaboradores_id WHERE u.`{$field}`=? AND u.estado=1";
            $stmt = $cn->prepare($sql);
            if ($stmt) {
                $id=(int)$id; $stmt->bind_param('i',$id); $stmt->execute(); $rs=$stmt->get_result();
                while($r=$rs->fetch_assoc()) $users[]=$r;
                $stmt->close();
            }
        } catch(Throwable $e) { error_log('NotificationService affected users: '.$e->getMessage()); }
        finally { $cn->close(); }
        $sent=0;$failed=0;
        foreach($users as $u){
            $r=$this->notifyUser($dbName,(int)($u['empresa_id']??$empresaId),$u['email'],$u['nombre'],$subject,$summary,[], $changes,'security');
            if(!empty($r['sent']))$sent++;else$failed++;
        }
        return ['sent'=>$sent,'failed'=>$failed];
    }
}
