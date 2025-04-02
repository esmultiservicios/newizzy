<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [	
		"empresa_id" => $_SESSION['empresa_id_sd']	,
		"privilegio_colaborador" => $_SESSION['privilegio_sd']
	];

	$result = $insMainModel->getEmpresaSelect($datos);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['empresa_id'].'">'.$consulta2['nombre'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}