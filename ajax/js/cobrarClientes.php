<script>
  // cobrarClientes.php
  $(function () {
    // bind del formulario
    $('#form_main_cobrar_clientes').on('submit', function (e) {
      e.preventDefault();
      listar_cuentas_por_cobrar_clientes();
    });

    // botón buscar
    $('#form_main_cobrar_clientes #search').on('click', function (e) {
      e.preventDefault();
      listar_cuentas_por_cobrar_clientes();
    });
  });

  // Lanza la primera carga cuando TODO terminó de cargar (evita carreras de carga)
  $(window).on('load', function () {
    if (typeof listar_cuentas_por_cobrar_clientes === 'function') {
      listar_cuentas_por_cobrar_clientes();
    }
  });
</script>