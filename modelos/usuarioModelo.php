<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class usuarioModelo extends mainModel{
    /*----------- Modelo para agregar usuario -----------*/
    protected function agregar_usuario_modelo($datos){
        $users_id = mainModel::correlativo("users_id", "users");
        $insert = "INSERT INTO users VALUES('$users_id','".$datos['colaborador_id']."','".$datos['privilegio_id']."','".$datos['nickname']."','".$datos['pass']."','".$datos['correo']."','".$datos['tipo_user']."','".$datos['estado']."','".$datos['fecha_registro']."','".$datos['empresa']."','".$datos['server_customers_id']."')";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;
    }
    
    /*----------- Modelo para validar usuario existente -----------*/
    protected function valid_user_modelo($colaborador_id){
        $query = "SELECT users_id FROM users WHERE colaboradores_id = '$colaborador_id'";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
        return $sql;
    }	

    /*----------- Modelo para validar correo existente -----------*/
    protected function valid_correo_modelo($email){
        $query = "SELECT users_id FROM users WHERE email = '$email'";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
        return $sql;
    }			

    /*----------- Modelo para editar usuario -----------*/
    protected function edit_user_modelo($datos){
        $update = "UPDATE users
        SET 
            tipo_user_id = '".$datos['tipo_user']."',
            privilegio_id = '".$datos['privilegio_id']."',
            empresa_id = '".$datos['empresa_id']."',
            estado = '".$datos['estado']."'
        WHERE users_id = '".$datos['usuarios_id']."'";

        $sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $sql;			
    }
    
    /*----------- Modelo para eliminar usuario -----------*/
    protected function delete_user_modelo($users_id){
        $delete = "DELETE FROM users WHERE users_id = '$users_id'";
        
        $sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
        
        return $sql;			
    }
    
    /*----------- Modelo para validar usuario en bitácora -----------*/
    protected function valid_user_bitacora($user_id){
        $query = "SELECT b.colaboradores_id
            FROM bitacora as b
            INNER JOIN users AS u
            ON b.colaboradores_id = u.colaboradores_id
            WHERE u.users_id = '$user_id'";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }

	/*----------- Modelo para obtener el totla de usuarios extras -----------*/
    protected function getTotalUsuariosExtras(){
        $query = "SELECT user_extra
            FROM plan";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }	

    /*----------- Modelo para contar usuarios activos -----------*/
    protected function getTotalUsuarios(){
        $query = "SELECT COUNT(*) AS 'total_usuarios'
            FROM users
            WHERE estado = 1 AND tipo_user_id NOT IN(1)";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }		

    /*----------- Modelo para obtener configuración del plan -----------*/
    protected function getPlanConfiguracion(){       
        $sql = mainModel::getPlanConfiguracionMainModel();
        
        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return json_decode($row['configuraciones'], true);
        }
        
        return [];
    }
    
    /*----------- Modelo para obtener correos de administradores -----------*/
    protected function getCorrreosAdmin(){
        $query = "SELECT users.email, CONCAT(colaboradores.nombre, ' ', colaboradores.apellido) AS nombre_completo
        FROM colaboradores
        INNER JOIN users ON colaboradores.colaboradores_id = users.colaboradores_id
        WHERE users.privilegio_id IN(1,2) AND users.estado = 1";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }
}