<?php
if ($peticionAjax) {
	require_once '../core/mainModel.php';
} else {
	require_once './core/mainModel.php';
}

class empresaModelo extends mainModel
{
	protected function agregar_empresa_modelo($datos)
	{
		$empresa_id = mainModel::correlativo('empresa_id', 'empresa');

		$insert = "INSERT INTO empresa 
						(
							empresa_id, razon_social, nombre, otra_informacion, eslogan, celular, telefono, correo, 
							logotipo, rtn, ubicacion, facebook, sitioweb, horario, estado, colaboradores_id, 
							fecha_registro, firma_documento, MostrarFirma
						) 
						VALUES (
							'$empresa_id', '" . $datos['razon_social'] . "', '" . $datos['empresa'] . "', '" . $datos['otra_informacion'] . "',
							'" . $datos['eslogan'] . "', '" . $datos['celular'] . "', '" . $datos['telefono'] . "', '" . $datos['correo'] . "', 
							'" . $datos['logotipo'] . "', '" . $datos['rtn'] . "', '" . $datos['ubicacion'] . "', '" . $datos['facebook'] . "', 
							'" . $datos['sitioweb'] . "', '" . $datos['horario'] . "', '" . $datos['estado'] . "', '" . $datos['usuario'] . "',
							'" . $datos['fecha_registro'] . "', '" . $datos['firma_documento'] . "', '" . $datos['MostrarFirma'] . "'
						)";

		$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function valid_empresa_modelo($rtn)
	{
		$query = "SELECT empresa_id FROM empresa WHERE rtn = '$rtn'";
		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function getImage($empresa_id)
	{
		$query = "SELECT logotipo, firma_documento FROM empresa WHERE empresa_id = '$empresa_id'";
		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function edit_empresa_modelo($datos)
	{
		if ($datos['cargarFirma'] === true && $datos['cargarLogo'] === true) {
			$update = "UPDATE empresa
				SET
					razon_social = '" . $datos['razon_social'] . "',
					nombre = '" . $datos['empresa'] . "',
					otra_informacion = '" . $datos['otra_informacion'] . "',
					eslogan = '" . $datos['eslogan'] . "',\t\t\t\t
					celular = '" . $datos['celular'] . "',
					telefono = '" . $datos['telefono'] . "',
					correo = '" . $datos['correo'] . "',
					rtn = '" . $datos['rtn'] . "',
					ubicacion = '" . $datos['ubicacion'] . "',
					estado = '" . $datos['estado'] . "',
					facebook = '" . $datos['facebook'] . "',
					sitioweb = '" . $datos['sitioweb'] . "',
					horario = '" . $datos['horario'] . "',
					logotipo = '" . $datos['logotipo'] . "',
					firma_documento = '" . $datos['firma_documento'] . "'
				WHERE empresa_id = '" . $datos['empresa_id'] . "'";
		} else if ($datos['cargarLogo'] === true) {
			$update = "UPDATE empresa
				SET
					razon_social = '" . $datos['razon_social'] . "',
					nombre = '" . $datos['empresa'] . "',
					otra_informacion = '" . $datos['otra_informacion'] . "',
					eslogan = '" . $datos['eslogan'] . "',\t\t\t\t
					celular = '" . $datos['celular'] . "',
					telefono = '" . $datos['telefono'] . "',
					correo = '" . $datos['correo'] . "',
					rtn = '" . $datos['rtn'] . "',
					ubicacion = '" . $datos['ubicacion'] . "',
					estado = '" . $datos['estado'] . "',
					facebook = '" . $datos['facebook'] . "',
					sitioweb = '" . $datos['sitioweb'] . "',
					horario = '" . $datos['horario'] . "',
					logotipo = '" . $datos['logotipo'] . "'
				WHERE empresa_id = '" . $datos['empresa_id'] . "'";
		} else if ($datos['cargarFirma'] === true) {
			$update = "UPDATE empresa
				SET
					razon_social = '" . $datos['razon_social'] . "',
					nombre = '" . $datos['empresa'] . "',
					otra_informacion = '" . $datos['otra_informacion'] . "',
					eslogan = '" . $datos['eslogan'] . "',\t\t\t\t
					celular = '" . $datos['celular'] . "',
					telefono = '" . $datos['telefono'] . "',
					correo = '" . $datos['correo'] . "',
					rtn = '" . $datos['rtn'] . "',
					ubicacion = '" . $datos['ubicacion'] . "',
					estado = '" . $datos['estado'] . "',
					facebook = '" . $datos['facebook'] . "',
					sitioweb = '" . $datos['sitioweb'] . "',
					horario = '" . $datos['horario'] . "',
					firma_documento = '" . $datos['firma_documento'] . "'
				WHERE empresa_id = '" . $datos['empresa_id'] . "'";
		} else {
			$update = "UPDATE empresa
				SET
					razon_social = '" . $datos['razon_social'] . "',
					nombre = '" . $datos['empresa'] . "',
					otra_informacion = '" . $datos['otra_informacion'] . "',
					eslogan = '" . $datos['eslogan'] . "',\t\t\t\t
					celular = '" . $datos['celular'] . "',
					telefono = '" . $datos['telefono'] . "',
					correo = '" . $datos['correo'] . "',
					rtn = '" . $datos['rtn'] . "',
					ubicacion = '" . $datos['ubicacion'] . "',
					estado = '" . $datos['estado'] . "',
					facebook = '" . $datos['facebook'] . "',
					sitioweb = '" . $datos['sitioweb'] . "',
					horario = '" . $datos['horario'] . "'
				WHERE empresa_id = '" . $datos['empresa_id'] . "'";
		}

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function delete_empresa_modelo($empresa_id)
	{
		$delete = "DELETE FROM empresa WHERE empresa_id = '$empresa_id'";

		$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function valid_user_secuencia_user($empresa_id)
	{
		$query = "SELECT empresa_id FROM users WHERE empresa_id = '$empresa_id'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	// Método para obtener el total de perfiles registrados en la empresa
	protected function getTotalEmpresasRegistradas()
	{
		try {
			// Obtener conexión a la base de datos
			$conexion = $this->connection();
			
			// Consulta SQL para contar empresas activas (ajusta según tu esquema de BD)
			$query = "SELECT COUNT(empresa_id) AS total FROM empresa WHERE estado = 1";
			
			// Ejecutar consulta
			$resultado = $conexion->query($query);
			
			if (!$resultado) {
				throw new Exception("Error al contar empresas: " . $conexion->error);
			}
			
			// Obtener el total
			$fila = $resultado->fetch_assoc();
			return (int)$fila['total'];
			
		} catch (Exception $e) {
			error_log("Error en getTotalEmpresasRegistradas: " . $e->getMessage());
			return 0; // Retorna 0 si hay error para no bloquear el sistema
		}
	}
}
