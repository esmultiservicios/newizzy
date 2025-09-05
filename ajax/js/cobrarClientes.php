<script>
$(() => {
  // Evitar submit normal
  $('#form_main_cobrar_clientes').on('submit', function (e) {
    e.preventDefault();
    listar_cuentas_por_cobrar_clientes();
  });

  // Primera carga
  listar_cuentas_por_cobrar_clientes();

  // Click del botón (aunque el submit ya cubre este caso)
  $('#form_main_cobrar_clientes #search').on('click', function (e) {
    e.preventDefault();
    listar_cuentas_por_cobrar_clientes();
  });
});
</script>