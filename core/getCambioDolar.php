<?php
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$fecha = date("Y-m-d");
	$data = htmlentities(file_get_contents(WEB_SCRAPING_DOLARES));
	echo $data;
	if (preg_match('|<h2 style="margin: 12px 0 0 0;">(.*?)</h2>|is' , $data , $cap )){
		echo "UF ".$cap[1];
	}else{
		echo 'nada';
	}
	