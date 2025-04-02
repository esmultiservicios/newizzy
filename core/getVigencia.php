<?php
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getVigenciaCotizacion();

	if($result->num_rows>0){	
		while($consulta2 = $result->fetch_assoc()){
			echo '<option value="'.$consulta2['vigencia_cotizacion_id'].'">'.$consulta2['valor'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}