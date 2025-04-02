<script>
$(document).ready(function() {
    listar_cuentas_por_cobrar_clientes();
	getBanco(); 
});

$('#form_main_cobrar_clientes #search').on("click", function(e){
	e.preventDefault();
	listar_cuentas_por_cobrar_clientes();
});

</script>