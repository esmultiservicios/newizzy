<script>
$(document).ready(function() {
    getDepartamentoClientes();
    getEstadoClientes();

    listar_clientes();
});

$('#form_main_clientes #buscar_clientes').on('click', function(e) {
    e.preventDefault();

    listar_clientes();
});
</script>