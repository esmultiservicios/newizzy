<?php
if (isset($_POST["searchText"])) {
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";

    $insMainModel = new mainModel();
    
    $searchText = $_POST["searchText"];

    // Función para buscar clientes por nombre
    function buscarClientes($nombre) {
        global $insMainModel;

        // Realizar la consulta usando el método de mainModel
        $resultados = $insMainModel->getNombreClienteLike($nombre);

        return $resultados;
    }

    // Realizar la búsqueda y enviar los resultados como HTML
    $clientes = buscarClientes($searchText);

    if($clientes->num_rows>0){
        foreach ($clientes as $cliente) {
            echo "<li>" . $cliente["nombre"] . "</li>";
        }
    }else{
        echo "<li style='color: red;'>No se encontraron resultados</li>";
    }
}
?>