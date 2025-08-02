//facturasRestantes.js

// Definir SERVERURL si no está definido (por si acaso)
if (typeof SERVERURL === 'undefined') {
    console.error('SERVERURL no está definido. Asegúrate de definirlo en el HTML.');
    var SERVERURL = '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Ocultar navbar-top y navbar-lateral
    const navbarTop = document.querySelector(".sb-topnav");
    const navbarLateral = document.querySelector(".sb-sidenav");
    
    if (navbarTop) navbarTop.style.display = "none";
    if (navbarLateral) navbarLateral.style.display = "none";
    
    // Agregar clase al body
    document.body.classList.add('vista-facturacion-restaurante');
    
    // Evento para el botón volver
    document.getElementById('btn-volver-dashboard').addEventListener('click', function() {
        window.location.href = SERVERURL + 'dashboard/';
    });
    
    // Evento para cerrar sesión
    document.getElementById('btn-cerrar-sesion').addEventListener('click', function(e) {
        e.preventDefault();
        const token = this.getAttribute('data-token'); // Asume que el token está en un atributo data-token
    
        swal({
            content: {
                element: "div",
                attributes: {
                    innerHTML: `
                        <h2 style="color: #f39c12; font-size: 22px; margin-bottom: 15px;">
                            ⚠️ ¿Está seguro?
                        </h2>
                        <p style="font-size: 16px; color: #555;">
                            Está a punto de cerrar su sesión. ¿Seguro que desea continuar? 😟
                        </p>
                    `
                }
            },
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willExit) => {
            if (willExit) {
                salir(token);  // Llamada a la función salir()
            }
        });
    });
    
    function salir(token) {
        $.ajax({
            url: SERVERURL + 'login/cerrarSesion?token=' + token,
            success: function(data) {
                if(data == 1) {
                    window.location.href = SERVERURL + 'login/';
                } else {
                    swal({
                        content: {
                            element: "div",
                            attributes: {
                                innerHTML: `
                                    <h2 style="color: #e74c3c; font-size: 22px; margin-bottom: 15px;">
                                        ❌ Ocurrió un error inesperado
                                    </h2>
                                    <p style="font-size: 16px; color: #555;">
                                        Algo salió mal al cerrar la sesión. ¡No se preocupe! Por favor, intente de nuevo. ⚠️
                                    </p>
                                `
                            }
                        },
                        icon: "error",
                        dangerMode: true,
                        closeOnEsc: false,
                        closeOnClickOutside: false
                    });
                }
            },
            error: function() {
                swal({
                    content: {
                        element: "div",
                        attributes: {
                            innerHTML: `
                                <h2 style="color: #e74c3c; font-size: 22px; margin-bottom: 15px;">
                                    ❌ Ocurrió un error inesperado
                                </h2>
                                <p style="font-size: 16px; color: #555;">
                                    Por favor, intente de nuevo. ⚠️
                                </p>
                            `
                        }
                    },
                    icon: "error",
                    dangerMode: true,
                    closeOnEsc: false,
                    closeOnClickOutside: false
                });        
            }
        });    
    }
    
    // Restaurar al salir
    window.addEventListener("beforeunload", function() {
        if (navbarTop) navbarTop.style.display = "";
        if (navbarLateral) navbarLateral.style.display = "";
        document.body.classList.remove('vista-facturacion-restaurante');
    });

    // =============================================
    // Variables globales
    // =============================================
    let mesaSeleccionada = null;
    let facturaActual = null;
    let productos = [];
    let categorias = [];
    let comandaItems = [];
    let clientes = [];
    let mesas = [];
    let clienteSeleccionado = {
        id: 0,
        nombre: 'CONSUMIDOR FINAL',
        identificacion: ''
    };
    
    // =============================================
    // Elementos del DOM
    // =============================================
    const mesasContainer = document.getElementById('mesas-container');
    const productosContainer = document.getElementById('productos-container');
    const categoriasTabs = document.querySelector('.categorias-tabs');
    const comandaItemsContainer = document.getElementById('comanda-items');
    const subtotalElement = document.getElementById('subtotal');
    const impuestoElement = document.getElementById('impuesto');
    const totalElement = document.getElementById('total');
    const btnNuevaMesa = document.getElementById('btn-nueva-mesa');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnImprimir = document.getElementById('btn-imprimir');
    const btnCerrar = document.getElementById('btn-cerrar');
    const btnLimpiar = document.getElementById('btn-limpiar');
    const buscarProductoInput = document.getElementById('buscar-producto');
    const facturaTitle = document.getElementById('factura-title');
    const mesaSeleccionadaElement = document.getElementById('mesa-seleccionada');
    const clienteInfoElement = document.getElementById('cliente-info');
    const observacionesTextarea = document.getElementById('observaciones');
    
    // Modales
    const modalMesa = document.getElementById('modal-mesa');
    const modalCliente = document.getElementById('modal-cliente');
    const closeModalButtons = document.querySelectorAll('.close');
    const formMesa = document.getElementById('form-mesa');
    
    // =============================================
    // Inicialización
    // =============================================
    init();
    
    function init() {
        cargarMesas();
        cargarCategorias();
        cargarProductos();
        cargarClientes();
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Botón nueva mesa
        btnNuevaMesa.addEventListener('click', mostrarModalMesa);
        
        // Cerrar modales
        closeModalButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                modalMesa.style.display = 'none';
                modalCliente.style.display = 'none';
            });
        });
        
        // Clic fuera del modal para cerrar
        window.addEventListener('click', function(event) {
            if (event.target === modalMesa) {
                modalMesa.style.display = 'none';
            }
            if (event.target === modalCliente) {
                modalCliente.style.display = 'none';
            }
        });
        
        // Formulario de mesa
        formMesa.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarMesa();
        });
        
        // Botón guardar factura
        btnGuardar.addEventListener('click', guardarFactura);
        
        // Botón limpiar comanda
        btnLimpiar.addEventListener('click', limpiarComanda);
        
        // Botón cerrar factura
        btnCerrar.addEventListener('click', cerrarFactura);
        
        // Buscar producto
        buscarProductoInput.addEventListener('input', function() {
            filtrarProductos(this.value);
        });
        
        // Botón cambiar cliente
        document.getElementById('btn-cambiar-cliente').addEventListener('click', mostrarModalCliente);
        
        // Buscar cliente
        document.getElementById('buscar-cliente').addEventListener('input', function() {
            filtrarClientes(this.value);
        });
        
        // Botón buscar cliente
        document.getElementById('btn-buscar-cliente').addEventListener('click', function() {
            filtrarClientes(document.getElementById('buscar-cliente').value);
        });
        
        // Botón nuevo cliente
        document.getElementById('btn-nuevo-cliente').addEventListener('click', mostrarModalNuevoCliente);
    }
    
    // =============================================
    // Funciones para Mesas
    // =============================================
    function cargarMesas() {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=loadMesas'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                mesas = data.mesas;
                renderizarMesas();
            } else {
                showNotify('error', 'Error', 'No se pudieron cargar las mesas');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al cargar las mesas');
        });
    }
    
    function renderizarMesas() {
        mesasContainer.innerHTML = '';
        
        mesas.forEach(mesa => {
            const mesaElement = document.createElement('div');
            mesaElement.className = `mesa-item ${mesa.estado === 'ocupada' ? 'ocupada' : ''}`;
            mesaElement.innerHTML = `
                <div class="mesa-header">
                    <span class="mesa-numero">Mesa ${mesa.numero}</span>
                    <span class="mesa-capacidad">${mesa.capacidad} <i class="fas fa-user"></i></span>
                </div>
                <div class="mesa-info">
                    <span class="mesa-ubicacion"><i class="fas fa-map-marker-alt"></i> ${mesa.ubicacion}</span>
                    <span class="mesa-estado ${mesa.estado}">
                        ${mesa.estado === 'ocupada' ? '<i class="fas fa-times-circle"></i>' : '<i class="fas fa-check-circle"></i>'}
                        ${mesa.estado.toUpperCase()}
                    </span>
                </div>
            `;
            
            mesaElement.addEventListener('click', () => seleccionarMesa(mesa));
            mesasContainer.appendChild(mesaElement);
        });
    }
    
    function seleccionarMesa(mesa) {
        if (mesa.estado === 'ocupada') {
            // Preguntar si desea cargar la factura existente
            if (confirm(`La mesa ${mesa.numero} está ocupada. ¿Desea cargar la factura existente?`)) {
                cargarFacturaMesa(mesa.id);
            }
            return;
        }
        
        mesaSeleccionada = mesa;
        facturaActual = null;
        
        // Actualizar UI
        mesaSeleccionadaElement.textContent = `Mesa: ${mesa.numero}`;
        facturaTitle.textContent = 'Nueva Comanda';
        btnImprimir.disabled = true;
        
        // Marcar mesa como seleccionada
        document.querySelectorAll('.mesa-item').forEach(item => {
            item.classList.remove('seleccionada');
        });
        
        const mesaElement = Array.from(document.querySelectorAll('.mesa-item')).find(el => 
            el.querySelector('.mesa-numero').textContent.includes(mesa.numero)
        );
        
        if (mesaElement) {
            mesaElement.classList.add('seleccionada');
        }
        
        // Limpiar comanda
        limpiarComanda();
    }
    
    function mostrarModalMesa() {
        document.getElementById('numero-mesa').value = '';
        document.getElementById('capacidad-mesa').value = '4';
        document.getElementById('ubicacion-mesa').value = 'Interior';
        modalMesa.style.display = 'block';
    }
    
    function guardarMesa() {
        const numeroMesa = document.getElementById('numero-mesa').value;
        const capacidad = document.getElementById('capacidad-mesa').value;
        const ubicacion = document.getElementById('ubicacion-mesa').value;
        
        if (!numeroMesa) {
            showNotify('error', 'Error', 'El número de mesa es requerido');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'saveMesa');
        formData.append('numero', numeroMesa);
        formData.append('capacidad', capacidad);
        formData.append('ubicacion', ubicacion);
        
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                showNotify('success', 'Éxito', 'Mesa guardada correctamente');
                modalMesa.style.display = 'none';
                cargarMesas();
            } else {
                showNotify('error', 'Error', data.message || 'Error al guardar la mesa');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al guardar la mesa');
        });
    }
    
    // =============================================
    // Funciones para Productos
    // =============================================
    function cargarCategorias() {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=loadCategorias'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                categorias = data.categorias;
                renderizarCategorias();
            } else {
                showNotify('error', 'Error', 'No se pudieron cargar las categorías');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al cargar las categorías');
        });
    }
    
    function renderizarCategorias() {
        categoriasTabs.innerHTML = '';
        
        // Agregar categoría "Todas"
        const todasCategoria = document.createElement('div');
        todasCategoria.className = 'categoria-tab active';
        todasCategoria.textContent = 'Todas';
        todasCategoria.addEventListener('click', () => {
            document.querySelectorAll('.categoria-tab').forEach(tab => tab.classList.remove('active'));
            todasCategoria.classList.add('active');
            filtrarProductos(buscarProductoInput.value);
        });
        categoriasTabs.appendChild(todasCategoria);
        
        // Agregar el resto de categorías
        categorias.forEach(categoria => {
            const categoriaElement = document.createElement('div');
            categoriaElement.className = 'categoria-tab';
            categoriaElement.textContent = categoria.nombre;
            categoriaElement.addEventListener('click', () => {
                document.querySelectorAll('.categoria-tab').forEach(tab => tab.classList.remove('active'));
                categoriaElement.classList.add('active');
                filtrarProductos(buscarProductoInput.value, categoria.id);
            });
            categoriasTabs.appendChild(categoriaElement);
        });
    }
    
    function cargarProductos() {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=loadProductos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                productos = data.productos;
                renderizarProductos(productos);
            } else {
                showNotify('error', 'Error', 'No se pudieron cargar los productos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al cargar los productos');
        });
    }
    
    function renderizarProductos(productosList) {
        productosContainer.innerHTML = '';
        
        // Función para formatear números con separadores de miles
        const formatNumber = (num) => {
            return new Intl.NumberFormat('es-HN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        };
        
        productosList.forEach(producto => {
            const productoElement = document.createElement('div');
            productoElement.className = 'producto-item';
            
            // 1. Contenedor de imagen
            const imagenContainer = document.createElement('div');
            imagenContainer.className = 'producto-imagen-container';
            
            const imagenDiv = document.createElement('div');
            imagenDiv.className = 'producto-imagen';
            
            // Verificar si hay imagen
            if (producto.file_name) {
                const img = document.createElement('img');
                img.src = `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}?${Date.now()}`;
                img.alt = producto.nombre;
                img.className = 'imagen-producto';
                img.loading = 'lazy';
                
                img.onerror = function() {
                    // Si falla la carga, mostrar placeholder
                    this.remove();
                    imagenDiv.classList.add('sin-imagen');
                    imagenDiv.setAttribute('data-nombre', producto.nombre);
                };
                
                imagenDiv.appendChild(img);
            } else {
                // Mostrar placeholder directamente si no hay imagen
                imagenDiv.classList.add('sin-imagen');
                imagenDiv.setAttribute('data-nombre', producto.nombre);
            }
            
            imagenContainer.appendChild(imagenDiv);
            productoElement.appendChild(imagenContainer);
            
            // 2. Contenido del producto (SIEMPRE visible)
            const contenidoDiv = document.createElement('div');
            contenidoDiv.className = 'producto-contenido';
            
            const mostrarMayoreo = producto.cantidad_mayoreo > 0 && producto.precio_mayoreo > 0;
            
            contenidoDiv.innerHTML = `
                <h4 class="producto-nombre">${producto.nombre}</h4>                
                <div class="producto-precios">
                    <div class="precio-regular">
                        <span class="precio-valor">L ${formatNumber(producto.precio_venta)}</span>
                    </div>
                    ${mostrarMayoreo ? `
                    <div class="precio-mayoreo">
                        <span class="mayoreo-info">${producto.cantidad_mayoreo} x L ${formatNumber(producto.precio_mayoreo)}</span>
                    </div>
                    ` : ''}
                </div>
            `;
            
            productoElement.appendChild(contenidoDiv);
            
            // 3. Botón de agregar
            const btnAgregar = document.createElement('button');
            btnAgregar.className = 'btn-agregar';
            btnAgregar.innerHTML = '<i class="fas fa-cart-plus"></i> Agregar';
            productoElement.appendChild(btnAgregar);
            
            // Datos del producto para reutilizar
            const datosProducto = {
                id: producto.productos_id,
                nombre: producto.nombre,
                precio: producto.precio_venta,
                descripcion: producto.descripcion,
                imagen: producto.file_name ? 
                    `${SERVERURL}vistas/plantilla/img/products/${producto.file_name}` : 
                    `${SERVERURL}vistas/plantilla/img/products/image_preview.png`
            };
            
            // Evento para el botón Agregar
            btnAgregar.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita que se active el evento del contenedor
                agregarProductoComanda(datosProducto);
            });
            
            // Evento para el contenedor del producto
            productoElement.addEventListener('click', () => {
                agregarProductoComanda(datosProducto);
            });
            
            productosContainer.appendChild(productoElement);
        });
    }
    
    function filtrarProductos(termino, categoriaId = null) {
        let productosFiltrados = productos;
        
        // Filtrar por término de búsqueda
        if (termino) {
            const terminoLower = termino.toLowerCase();
            productosFiltrados = productosFiltrados.filter(producto => 
                producto.nombre.toLowerCase().includes(terminoLower) ||
                (producto.descripcion && producto.descripcion.toLowerCase().includes(terminoLower))
            );
        }
        
        // Filtrar por categoría si no es "Todas"
        if (categoriaId) {
            productosFiltrados = productosFiltrados.filter(producto => producto.categoria_id == categoriaId);
        }
        
        renderizarProductos(productosFiltrados);
    }
    
    // =============================================
    // Funciones para Comanda
    // =============================================
    function agregarProductoComanda(producto) {
        if (!mesaSeleccionada) {
            showNotify('warning', 'Advertencia', 'Debe seleccionar una mesa primero');
            return;
        }
        
        // Verificar si el producto ya está en la comanda
        const itemExistente = comandaItems.find(item => item.producto.id === producto.id);
        
        if (itemExistente) {
            // Incrementar cantidad
            itemExistente.cantidad += 1;
            itemExistente.total = itemExistente.cantidad * itemExistente.precio;
        } else {
            // Agregar nuevo item
            comandaItems.push({
                producto: producto,
                cantidad: 1,
                precio: producto.precio,
                total: producto.precio
            });
        }
        
        actualizarComandaUI();
    }
    
    function actualizarComandaUI() {
        comandaItemsContainer.innerHTML = '';
        
        // Crear tabla
        const table = document.createElement('table');
        table.className = 'comanda-table';
        
        // Crear encabezados
        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th style="width:40%">Producto</th>
                <th style="width:15%">Cantidad</th>
                <th style="width:15%">P. Unitario</th>
                <th style="width:15%">Subtotal</th>
                <th style="width:15%">Acción</th>
            </tr>
        `;
        table.appendChild(thead);
        
        // Crear cuerpo
        const tbody = document.createElement('tbody');
        
        // Función para formatear números
        const formatNumber = (num) => {
            return new Intl.NumberFormat('es-HN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        };
        
        comandaItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'fade-in';
            row.innerHTML = `
                <td>
                    <div class="comanda-producto-info">
                        <span class="comanda-producto-nombre" title="${item.producto.nombre}">${item.producto.nombre}</span>
                        ${item.producto.descripcion ? `<small class="comanda-producto-desc" title="${item.producto.descripcion}">${item.producto.descripcion}</small>` : ''}
                    </div>
                </td>
                <td class="comanda-item-cantidad">
                    <button class="btn-cantidad" data-index="${index}" data-action="decrement">-</button>
                    <input type="number" min="1" value="${item.cantidad}" data-index="${index}">
                    <button class="btn-cantidad" data-index="${index}" data-action="increment">+</button>
                </td>
                <td class="comanda-item-precio"><span class="moneda">L</span> ${formatNumber(item.precio)}</td>
                <td class="comanda-item-total"><span class="moneda">L</span> ${formatNumber(item.total)}</td>
                <td class="comanda-item-eliminar">
                    <button class="btn-eliminar" data-index="${index}"><i class="fas fa-trash"></i></button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        comandaItemsContainer.appendChild(table);
        
        // Agregar event listeners
        document.querySelectorAll('.btn-cantidad').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                const action = this.getAttribute('data-action');
                actualizarCantidad(index, action);
            });
        });
        
        document.querySelectorAll('.comanda-item-cantidad input').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.getAttribute('data-index'));
                const nuevaCantidad = parseInt(this.value) || 1;
                actualizarCantidadInput(index, nuevaCantidad);
            });
        });
        
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                eliminarItemComanda(index);
            });
        });
        
        calcularTotales();
    }
    
    function actualizarCantidad(index, action) {
        if (index < 0 || index >= comandaItems.length) return;
        
        if (action === 'increment') {
            comandaItems[index].cantidad += 1;
        } else if (action === 'decrement') {
            if (comandaItems[index].cantidad > 1) {
                comandaItems[index].cantidad -= 1;
            } else {
                eliminarItemComanda(index);
                return;
            }
        }
        
        comandaItems[index].total = comandaItems[index].cantidad * comandaItems[index].precio;
        actualizarComandaUI();
    }
    
    function actualizarCantidadInput(index, nuevaCantidad) {
        if (index < 0 || index >= comandaItems.length || nuevaCantidad < 1) return;
        
        comandaItems[index].cantidad = nuevaCantidad;
        comandaItems[index].total = comandaItems[index].cantidad * comandaItems[index].precio;
        actualizarComandaUI();
    }
    
    function eliminarItemComanda(index) {
        if (index < 0 || index >= comandaItems.length) return;
        
        comandaItems.splice(index, 1);
        actualizarComandaUI();
    }
    
    function calcularTotales() {
        const subtotal = comandaItems.reduce((sum, item) => sum + item.total, 0);
        const impuesto = subtotal * 0.15; // 15% de impuesto
        const total = subtotal + impuesto;
        
        // Función para formatear números con separadores de miles y 2 decimales
        const formatNumber = (num) => {
            return new Intl.NumberFormat('es-HN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        };
        
        subtotalElement.textContent = `L ${formatNumber(subtotal)}`;
        impuestoElement.textContent = `L ${formatNumber(impuesto)}`;
        totalElement.textContent = `L ${formatNumber(total)}`;
    }
    
    function limpiarComanda() {
        if (comandaItems.length > 0) {
            swal({
                title: "¿Limpiar comanda?",
                html: `
                    <div style="text-align: center;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f39c12; margin-bottom: 15px;"></i>
                        <p style="font-size: 16px; color: #555;">
                            Está a punto de eliminar todos los items de la comanda actual.<br>
                            ¿Desea continuar?
                        </p>
                    </div>
                `,
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancelar",
                        value: null,
                        visible: true,
                        className: "btn-default"
                    },
                    confirm: {
                        text: "Limpiar",
                        value: true,
                        visible: true,
                        className: "btn-danger"
                    }
                },
                dangerMode: true,
                closeOnClickOutside: false
            }).then((value) => {
                if (value) {
                    comandaItems = [];
                    actualizarComandaUI();
                    observacionesTextarea.value = '';
                    
                    // Opcional: Mostrar notificación de éxito
                    swal({
                        title: "¡Comanda vaciada!",
                        text: "Todos los items fueron eliminados",
                        icon: "success",
                        timer: 1500,
                        buttons: false
                    });
                }
            });
        } else {
            comandaItems = [];
            actualizarComandaUI();
            observacionesTextarea.value = '';
        }
    }
    
    // =============================================
    // Funciones para Clientes
    // =============================================
    function cargarClientes() {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=loadClientes'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                clientes = data.clientes;
                if (modalCliente.style.display === 'block') {
                    renderizarClientes();
                }
            } else {
                showNotify('error', 'Error', 'No se pudieron cargar los clientes');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al cargar los clientes');
        });
    }
    
    function renderizarClientes() {
        const clientesContainer = document.getElementById('clientes-container');
        clientesContainer.innerHTML = '';
        
        // Agregar consumidor final como primera opción
        const consumidorFinal = document.createElement('div');
        consumidorFinal.className = 'cliente-item';
        consumidorFinal.innerHTML = `
            <div class="cliente-nombre">CONSUMIDOR FINAL</div>
            <div class="cliente-identificacion">Cliente genérico</div>
        `;
        consumidorFinal.addEventListener('click', () => {
            clienteSeleccionado = {
                id: 0,
                nombre: 'CONSUMIDOR FINAL',
                identificacion: ''
            };
            clienteInfoElement.textContent = 'Cliente: Consumidor final';
            modalCliente.style.display = 'none';
        });
        clientesContainer.appendChild(consumidorFinal);
        
        // Agregar el resto de clientes
        clientes.forEach(cliente => {
            const clienteElement = document.createElement('div');
            clienteElement.className = 'cliente-item';
            clienteElement.innerHTML = `
                <div class="cliente-nombre">${cliente.nombre}</div>
                <div class="cliente-identificacion">${cliente.identificacion || 'Sin identificación'}</div>
            `;
            
            clienteElement.addEventListener('click', () => {
                clienteSeleccionado = {
                    id: cliente.clientes_id,
                    nombre: cliente.nombre,
                    identificacion: cliente.identificacion
                };
                clienteInfoElement.textContent = `Cliente: ${cliente.nombre}`;
                modalCliente.style.display = 'none';
            });
            
            clientesContainer.appendChild(clienteElement);
        });
    }
    
    function mostrarModalCliente() {
        renderizarClientes();
        document.getElementById('buscar-cliente').value = '';
        modalCliente.style.display = 'block';
    }
    
    function filtrarClientes(termino) {
        const terminoLower = termino.toLowerCase();
        const clientesFiltrados = termino ? 
            clientes.filter(cliente => 
                cliente.nombre.toLowerCase().includes(terminoLower) ||
                (cliente.identificacion && cliente.identificacion.toLowerCase().includes(terminoLower))
            ) : 
            clientes;
        
        const clientesContainer = document.getElementById('clientes-container');
        const items = clientesContainer.querySelectorAll('.cliente-item');
        
        // El primer elemento es siempre el consumidor final
        items[0].style.display = 'block';
        
        // Filtrar el resto de clientes
        for (let i = 1; i < items.length; i++) {
            const nombre = items[i].querySelector('.cliente-nombre').textContent.toLowerCase();
            const identificacion = items[i].querySelector('.cliente-identificacion').textContent.toLowerCase();
            
            if (nombre.includes(terminoLower) || identificacion.includes(terminoLower)) {
                items[i].style.display = 'block';
            } else {
                items[i].style.display = 'none';
            }
        }
    }
    
    function mostrarModalNuevoCliente() {
        // Implementar lógica para mostrar modal de nuevo cliente
        showNotify('info', 'Nuevo Cliente', 'Esta funcionalidad estará disponible pronto');
    }
    
    // =============================================
    // Funciones para Facturas
    // =============================================
    function cargarFacturaMesa(mesaId) {
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=loadFacturaMesa&mesa_id=${mesaId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                facturaActual = data.factura;
                mesaSeleccionada = data.mesa;
                comandaItems = data.items.map(item => ({
                    producto: {
                        id: item.productos_id,
                        nombre: item.nombre_producto,
                        precio: parseFloat(item.precio),
                        descripcion: item.descripcion_producto || ''
                    },
                    cantidad: parseInt(item.cantidad),
                    precio: parseFloat(item.precio),
                    total: parseFloat(item.precio) * parseInt(item.cantidad)
                }));
                
                // Actualizar UI
                mesaSeleccionadaElement.textContent = `Mesa: ${mesaSeleccionada.numero}`;
                facturaTitle.textContent = `Factura #${facturaActual.number}`;
                observacionesTextarea.value = facturaActual.notas || '';
                btnImprimir.disabled = false;
                
                // Actualizar cliente si existe
                if (facturaActual.cliente_id && facturaActual.cliente_nombre) {
                    clienteSeleccionado = {
                        id: facturaActual.cliente_id,
                        nombre: facturaActual.cliente_nombre,
                        identificacion: facturaActual.cliente_identificacion || ''
                    };
                    clienteInfoElement.textContent = `Cliente: ${facturaActual.cliente_nombre}`;
                } else {
                    clienteSeleccionado = {
                        id: 0,
                        nombre: 'CONSUMIDOR FINAL',
                        identificacion: ''
                    };
                    clienteInfoElement.textContent = 'Cliente: Consumidor final';
                }
                
                actualizarComandaUI();
                
                // Marcar mesa como seleccionada
                document.querySelectorAll('.mesa-item').forEach(item => {
                    item.classList.remove('seleccionada');
                    if (item.querySelector('.mesa-numero').textContent.includes(mesaSeleccionada.numero)) {
                        item.classList.add('seleccionada');
                    }
                });
            } else {
                showNotify('error', 'Error', data.message || 'No se pudo cargar la factura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al cargar la factura');
        });
    }
    
    function guardarFactura() {
        if (!mesaSeleccionada) {
            showNotify('warning', 'Advertencia', 'Debe seleccionar una mesa primero');
            return;
        }
        
        if (comandaItems.length === 0) {
            showNotify('warning', 'Advertencia', 'La comanda está vacía');
            return;
        }
        
        const metodoPago = document.querySelector('input[name="metodo-pago"]:checked')?.value || 'efectivo';
        const observaciones = observacionesTextarea.value;
        
        const facturaData = {
            mesa_id: mesaSeleccionada.id,
            cliente_id: clienteSeleccionado.id,
            items: comandaItems.map(item => ({
                producto_id: item.producto.id,
                cantidad: item.cantidad,
                precio: item.precio,
                descripcion: item.producto.descripcion
            })),
            metodo_pago: metodoPago,
            observaciones: observaciones,
            factura_id: facturaActual ? facturaActual.id : null
        };
        
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: facturaActual ? 'updateFactura' : 'saveFactura',
                data: facturaData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                showNotify('success', 'Éxito', facturaActual ? 'Factura actualizada' : 'Factura guardada');
                facturaActual = data.factura;
                facturaTitle.textContent = `Factura #${facturaActual.number}`;
                btnImprimir.disabled = false;
                cargarMesas();
                
                // Enviar comanda a cocina
                enviarComandaACocina();
            } else {
                showNotify('error', 'Error', data.message || 'Error al guardar la factura');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotify('error', 'Error', 'Error al guardar la factura');
        });
    }
    
    function enviarComandaACocina() {
        if (!mesaSeleccionada || comandaItems.length === 0) return;
        
        const comandaData = {
            mesa: mesaSeleccionada.numero,
            items: comandaItems.map(item => ({
                nombre: item.producto.nombre,
                cantidad: item.cantidad,
                observaciones: observacionesTextarea.value
            })),
            hora: new Date().toLocaleTimeString()
        };
        
        fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'enviarComandaCocina',
                data: comandaData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.status) {
                console.error('Error al enviar comanda a cocina:', data.message);
            }
        })
        .catch(error => {
            console.error('Error al enviar comanda a cocina:', error);
        });
    }
    
    function cerrarFactura() {
        if (!facturaActual) {
            showNotify('warning', 'Advertencia', 'No hay factura abierta');
            return;
        }
        
        if (confirm('¿Está seguro que desea cerrar esta factura?')) {
            fetch(`${SERVERURL}core/facturasRestaurante/facturasRestauranteAjax.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=closeFactura&factura_id=${facturaActual.id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    showNotify('success', 'Éxito', 'Factura cerrada correctamente');
                    limpiarComanda();
                    mesaSeleccionada = null;
                    facturaActual = null;
                    mesaSeleccionadaElement.textContent = 'Mesa: No seleccionada';
                    facturaTitle.textContent = 'Nueva Comanda';
                    btnImprimir.disabled = true;
                    clienteSeleccionado = {
                        id: 0,
                        nombre: 'CONSUMIDOR FINAL',
                        identificacion: ''
                    };
                    clienteInfoElement.textContent = 'Cliente: Consumidor final';
                    cargarMesas();
                } else {
                    showNotify('error', 'Error', data.message || 'Error al cerrar la factura');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotify('error', 'Error', 'Error al cerrar la factura');
            });
        }
    }
});