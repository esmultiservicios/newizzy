<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [	
		"empresa_id" => $_SESSION['empresa_id_sd'],
		"privilegio_colaborador" => $_SESSION['privilegio_sd']	
	];

	$result = $insMainModel->getAlmacen($datos);
	
	if($result->num_rows>0){
		echo '<option value=0>Todo</option>';
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['almacen_id'].'">'.$consulta2['almacen'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}