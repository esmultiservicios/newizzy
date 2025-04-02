<script>
$(document).ready(function() {
    getDepartamentoClientes();
    getEstadoClientes();

    var estado = $('#form_main_clientes #estado_clientes').val() === "" ? 1 : $(
        '#form_main_clientes #estado_clientes').val();
    listar_clientes(estado);
});

$('#form_main_clientes #buscar_clientes').on('click', function(e) {
    e.preventDefault();
    var estado = $('#form_main_clientes #estado_clientes').val() === "" ? 1 : $(
        '#form_main_clientes #estado_clientes').val();
    listar_clientes(estado);
});
</script>