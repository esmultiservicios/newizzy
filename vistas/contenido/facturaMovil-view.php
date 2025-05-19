<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación Móvil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        body {
            background-color: var(--gray-100);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px;
        }

        .product-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--teal);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--indigo);
            border-color: var(--indigo);
        }

        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }

        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }

        .total-display {
            background-color: var(--primary);
            color: white;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .form-control, .selectpicker {
            border-radius: 8px !important;
            border: 1px solid var(--gray-300);
        }

        .form-control:focus, .selectpicker:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 0.25rem rgba(23, 162, 184, 0.25);
        }

        .badge-primary {
            background-color: var(--primary);
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding: 0 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .product-item {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Facturación Móvil</h5>
                        <span class="badge badge-primary" id="factura-number">Nueva Factura</span>
                    </div>
                    <div class="card-body">
                        <form id="factura-form">
                            <!-- Selección de Cliente -->
                            <div class="form-group mb-3">
                                <label for="cliente-select" class="form-label">Cliente</label>
                                <select class="form-control selectpicker" id="cliente-select" data-live-search="true" title="Seleccione un cliente" required>
                                    <!-- Opciones se llenarán con JS -->
                                </select>
                            </div>

                            <!-- Selección de Vendedor -->
                            <div class="form-group mb-3">
                                <label for="vendedor-select" class="form-label">Vendedor</label>
                                <select class="form-control selectpicker" id="vendedor-select" data-live-search="true" title="Seleccione un vendedor" required>
                                    <!-- Opciones se llenarán con JS -->
                                </select>
                            </div>

                            <!-- Tipo de Factura -->
                            <div class="form-group mb-3">
                                <label class="form-label">Tipo de Factura</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="tipo-factura" id="contado" value="1" checked>
                                        <label class="form-check-label" for="contado">Contado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo-factura" id="credito" value="2">
                                        <label class="form-check-label" for="credito">Crédito</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Agregar Productos -->
                            <div class="form-group mb-3">
                                <label for="producto-select" class="form-label">Producto</label>
                                <div class="input-group">
                                    <select class="form-control selectpicker" id="producto-select" data-live-search="true" title="Seleccione un producto">
                                        <!-- Opciones se llenarán con JS -->
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="cantidad" class="form-label">Cantidad</label>
                                    <input type="number" min="1" value="1" class="form-control" id="cantidad">
                                </div>
                                <div class="col-6">
                                    <label for="descuento" class="form-label">Descuento (L.)</label>
                                    <input type="number" min="0" value="0" step="0.01" class="form-control" id="descuento">
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary w-100 mb-3" id="agregar-producto">
                                <i class="fas fa-plus-circle"></i> Agregar Producto
                            </button>

                            <!-- Lista de Productos -->
                            <div class="mb-3">
                                <h6>Productos Agregados</h6>
                                <div id="productos-agregados">
                                    <!-- Productos se agregarán aquí -->
                                    <div class="alert alert-info">No hay productos agregados</div>
                                </div>
                            </div>

                            <!-- Totales -->
                            <div class="total-display text-center">
                                <div>Subtotal: <span id="subtotal">L. 0.00</span></div>
                                <div>ISV: <span id="isv">L. 0.00</span></div>
                                <div>Descuento: <span id="total-descuento">L. 0.00</span></div>
                                <div class="font-weight-bold">Total: <span id="total">L. 0.00</span></div>
                            </div>

                            <!-- Notas -->
                            <div class="form-group mb-3">
                                <label for="notas" class="form-label">Notas</label>
                                <textarea class="form-control" id="notas" rows="2"></textarea>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-danger mb-2" id="cancelar-factura">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-success" id="procesar-factura">
                                    <i class="fas fa-save"></i> Registrar Factura
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Pago -->
    <div class="modal fade" id="pagoModal" tabindex="-1" aria-labelledby="pagoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="pagoModalLabel">Registrar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="pago-form">
                        <input type="hidden" id="factura-id-pago">
                        <div class="form-group mb-3">
                            <label for="monto-pago" class="form-label">Monto a Pagar</label>
                            <input type="number" class="form-control" id="monto-pago" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label for="efectivo-pago" class="form-label">Efectivo Recibido</label>
                            <input type="number" step="0.01" class="form-control" id="efectivo-pago" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="cambio-pago" class="form-label">Cambio</label>
                            <input type="number" class="form-control" id="cambio-pago" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label for="tarjeta-pago" class="form-label">Pago con Tarjeta</label>
                            <input type="number" step="0.01" class="form-control" id="tarjeta-pago" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="registrar-pago">Registrar Pago</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>