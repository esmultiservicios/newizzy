<?php
/** Sincronización idempotente de las entidades de la cuenta recién registrada. */
class ProvisioningSync {
    public static function sync(mysqli $main, mysqli $tenant, int $customerId): void {
        $stmt=$main->prepare('SELECT server_customers_id,clientes_id,codigo_cliente,db,planes_id,sistema_id,validar,estado,db_imported FROM server_customers WHERE server_customers_id=? LIMIT 1');
        if (!$stmt) throw new RuntimeException('No se puede consultar la cuenta central.');
        $stmt->bind_param('i',$customerId);$stmt->execute();$source=$stmt->get_result()->fetch_assoc();$stmt->close();
        if (!$source) throw new RuntimeException('Cuenta central no encontrada.');
        if ((int)$source['planes_id']!==6) throw new RuntimeException('El registro no tiene Plan Gratis ID 6.');
        $check=$tenant->query("SHOW COLUMNS FROM server_customers LIKE 'db_imported'");
        if (!$check || $check->num_rows===0) throw new RuntimeException('La plantilla no contiene server_customers.db_imported.');
        // Una DB nueva solo debe contener su propia cuenta; nunca borrar cuentas ajenas.
        $existing=$tenant->query('SELECT server_customers_id FROM server_customers LIMIT 2');
        if (!$existing) throw new RuntimeException('No se pudo validar server_customers.');
        while($r=$existing->fetch_assoc()) if ((int)$r['server_customers_id']!==$customerId) throw new RuntimeException('Plantilla contaminada: contiene otras cuentas.');
        $stmt=$tenant->prepare('INSERT INTO server_customers (server_customers_id,clientes_id,codigo_cliente,db,planes_id,sistema_id,validar,estado,db_imported) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE clientes_id=VALUES(clientes_id),codigo_cliente=VALUES(codigo_cliente),db=VALUES(db),planes_id=VALUES(planes_id),sistema_id=VALUES(sistema_id),validar=VALUES(validar),estado=VALUES(estado),db_imported=VALUES(db_imported)');
        if (!$stmt) throw new RuntimeException('No se puede preparar server_customers del cliente.');
        $sid=(int)$source['server_customers_id'];$cid=(int)$source['clientes_id'];$code=(int)$source['codigo_cliente'];$db=(string)$source['db'];$plan=6;$system=(int)$source['sistema_id'];$validar=(int)$source['validar'];$estado=1;$imported=1;
        $stmt->bind_param('iiisiiiii',$sid,$cid,$code,$db,$plan,$system,$validar,$estado,$imported);
        if (!$stmt->execute()) throw new RuntimeException('No fue posible guardar la cuenta del cliente: '.$stmt->error);$stmt->close();
        $planCheck=$tenant->query('SELECT planes_id FROM planes WHERE planes_id=6 LIMIT 1');
        if (!$planCheck || $planCheck->num_rows!==1) throw new RuntimeException('El catálogo de planes del cliente no contiene el Plan Gratis ID 6.');
        $existingPlan=$tenant->query('SELECT plan_id FROM plan LIMIT 2');
        if (!$existingPlan) throw new RuntimeException('Falta la tabla plan en la plantilla.');
        if ($existingPlan->num_rows>1) throw new RuntimeException('La plantilla contiene múltiples asignaciones de plan.');
        if ($existingPlan->num_rows===1) {
            $r=$existingPlan->fetch_assoc();$pid=(int)$r['plan_id'];
            $stmt=$tenant->prepare('UPDATE plan SET planes_id=6, user_extra=0 WHERE plan_id=?');$stmt->bind_param('i',$pid);
        } else {
            $stmt=$tenant->prepare('INSERT INTO plan (planes_id,user_extra,fecha_registro) VALUES (6,0,NOW())');
        }
        if (!$stmt || !$stmt->execute()) throw new RuntimeException('No fue posible asignar el Plan Gratis.');$stmt->close();
    }
}
