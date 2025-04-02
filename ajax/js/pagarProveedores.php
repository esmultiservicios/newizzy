<script>
$(document).ready(function() {
    listar_cuentas_por_pagar_proveedores();
    getBancoPurchase();
    getCuentasProveedores();
});

$('#form_main_pagar_proveedores #search').on("click", function(e) {
    e.preventDefault();
    listar_cuentas_por_pagar_proveedores();
});
</script>