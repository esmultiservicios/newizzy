<?php
    require_once "./modelos/vistasModelo.php";
    
    class vistasControlador extends vistasModelo{
        
        public function getPlantillaControlador(){
            return require_once "./vistas/plantilla/plantilla.php";
        } 

        public function getVistasControlador(){
            if(isset($_GET['views'])){
                $ruta = explode("/", $_GET['views']);
                error_log("Intentando acceder a vista: " . $ruta[0]); // Log para depuración
                $respuesta = vistasModelo::getVistasModelo($ruta[0]);
                error_log("Respuesta del modelo: " . $respuesta); // Log para depuración
            }else{
                $respuesta = "login";
            }
            return $respuesta;
        }
    }