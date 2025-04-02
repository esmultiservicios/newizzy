<?php
    require_once "./modelos/vistasModelo.php";
    
    class vistasControlador extends vistasModelo{
        
        public function getPlantillaControlador(){
            return require_once "./vistas/plantilla/plantilla.php";
        } 

        public function getVistasControlador(){
            if(isset($_GET['views'])){
                $ruta = explode("/", $_GET['views']);//DIVIDIMOS EN PARTES LA VARIABLE
                $respuesta = vistasModelo::getVistasModelo($ruta[0]);
            }else{
                $respuesta = "login";
            }
			
            return $respuesta;
        }
    }
?>	